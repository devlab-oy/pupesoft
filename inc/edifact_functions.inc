<?php

function rullat_ja_kontit($konttiviite, $konttimaara = false) {
  global $kukarow, $yhtiorow;

  // haetaan kaikki konttiviitteen alaiset rullat
  $query = "SELECT GROUP_CONCAT(lasku.tunnus), laskun_lisatiedot.konttimaara
            FROM laskun_lisatiedot
            JOIN lasku
              ON lasku.yhtio = laskun_lisatiedot.yhtio
              AND lasku.tunnus = laskun_lisatiedot.otunnus
            WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
            AND laskun_lisatiedot.konttiviite = '{$konttiviite}'
            GROUP BY lasku.yhtio";
  $result = pupe_query($query);
  $laskujen_tunnukset = mysql_result($result, 0, 0);
  $bookattu_konttimaara = mysql_result($result, 0, 1);

  if ($konttimaara) {
    $bookattu_konttimaara = $konttimaara;
  }

  if (mysql_num_rows($result) == 0) {
    return false;
  }

  $query = "SELECT ss.sarjanumero,
            tr.tunnus,
            tr.keratty,
            ss.massa as paino,
            trlt.juoksu,
            trlt.asiakkaan_rivinumero as rivinro,
            trlt.konttinumero,
            trlt.sinettinumero,
            la.sisviesti1 as ohje,
            ss.hyllyalue,
            ss.hyllynro,
            concat(ss.hyllyalue, '-', ss.hyllynro) AS paikka,
            concat(la.asiakkaan_tilausnumero,trlt.asiakkaan_rivinumero,ss.hyllyalue,ss.hyllynro) AS group_class,
            la.asiakkaan_tilausnumero
            FROM lasku AS la
            JOIN tilausrivi AS tr
              ON tr.yhtio = la.yhtio AND tr.otunnus = la.tunnus
            JOIN tilausrivin_lisatiedot AS trlt
              ON trlt.yhtio = tr.yhtio AND trlt.tilausrivitunnus = tr.tunnus
            JOIN sarjanumeroseuranta AS ss
              ON ss.yhtio = tr.yhtio AND ss.myyntirivitunnus = tr.tunnus
            WHERE la.yhtio = '{$kukarow['yhtio']}'
            AND la.tunnus IN ({$laskujen_tunnukset})
            AND (ss.lisatieto IS NULL OR ss.lisatieto = 'Lusattu' OR ss.lisatieto = 'Toimitettu' OR ss.lisatieto = 'Siirretty')
            ORDER BY group_class";
  $result = pupe_query($query);

  $kontit = array();
  $kontitetut = array();
  $kontittamattomat = array();

  $ryhma_laskuri = array();

  $kontti = 1;
  $kilot = 0;

  while ($rulla = mysql_fetch_assoc($result)) {

    if ($rulla['konttinumero'] != '') {
      $kontitetut[] = $rulla;
    }
    else{
      $kontittamattomat[] = $rulla;
      $ryhmalaskuri[$rulla['group_class']]++;
    }
  }

  foreach ($kontitetut as $key => $rulla) {
    $kontit[$rulla['konttinumero']] = 0;
  }

  $konttimaara = count($kontit);
  $konttinimet = array_keys($kontit);

  while ($konttimaara < $bookattu_konttimaara) {

    $uusi_key = (int) $konttimaara + 1;

    while (in_array($uusi_key, $konttinimet)) {
      $uusi_key = $uusi_key + 1;
    }

    $kontit[$uusi_key] = 0;
    $konttinimet = array_keys($kontit);
    $konttimaara++;
  }

  $rullat_ja_kontit = array(
    'kontittamattomat' => $kontittamattomat,
    'kontitetut' => $kontitetut,
    'sinetoidyt' => $sinetoidyt,
    'kontit' => $kontit,
    'ryhma_laskuri' => $ryhmalaskuri
  );

  return $rullat_ja_kontit;
}

function laheta_sanoma($sanoma) {

  if ($sanoma == '') {
    return false;
  }

  global $kukarow, $ftp_info;

  $host = $ftp_info['host'];
  $user = $ftp_info['user'];
  $pass = $ftp_info['pass'];

  $yhteys = ftp_connect($host);

  $login = ftp_login($yhteys, $user, $pass);

  if ((!$yhteys) || (!$login)) {
    die('ei yhteyttä');
  }

  ftp_chdir($yhteys, 'in-prod');
  ftp_pasv($yhteys, true);

  $file = fopen('php://temp', 'r+');
  fwrite($file, $sanoma);
  rewind($file);

  if (strpos($sanoma, "UNH+1+RECADV") == true) {
   $extension = ".RAD";
  }
  elseif (strpos($sanoma, "UNH+1+DESADV") == true) {
   $extension = ".DAD";
  }
  elseif (strpos($sanoma, "UNH+1+INVRPT") == true) {
   $extension = ".IRP";
  }

  $nimi = time().$extension;

  if (ftp_fput($yhteys, $nimi, $file, FTP_ASCII)) {

    ftp_close($yhteys);
    return true;
  }
  return false;
}

function kuittaus_parametrit($hakunumero) {
  global $kukarow;

  $query = "SELECT tilausrivin_lisatiedot.rahtikirja_id
            FROM sarjanumeroseuranta
            JOIN tilausrivi
              ON tilausrivi.yhtio = sarjanumeroseuranta.yhtio
              AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
            JOIN tilausrivin_lisatiedot
              ON tilausrivin_lisatiedot.yhtio = sarjanumeroseuranta.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            WHERE sarjanumeroseuranta.sarjanumero = '{$hakunumero}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $rahtikirjanumero = mysql_result($result, 0);
  }
  else {
    $rahtikirjanumero = $hakunumero;
  }

  $query = "SELECT lasku.ovttunnus,
            lasku.tunnus AS laskutunnus,
            sarjanumeroseuranta.sarjanumero,
            sarjanumeroseuranta.massa AS paino,
            sarjanumeroseuranta.leveys,
            tilausrivi.tuoteno,
            tilausrivi.toimitettuaika,
            tilausrivi.tunnus,
            tilausrivin_lisatiedot.juoksu,
            tilausrivin_lisatiedot.kuljetuksen_rekno,
            tilausrivin_lisatiedot.tilauksen_paino,
            tilausrivin_lisatiedot.asiakkaan_rivinumero,
            tilausrivin_lisatiedot.asiakkaan_tilausnumero
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot
              ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            JOIN lasku
              ON lasku.yhtio = tilausrivi.yhtio
              AND lasku.tunnus = tilausrivi.otunnus
            JOIN sarjanumeroseuranta
              ON sarjanumeroseuranta.yhtio = lasku.yhtio
              AND sarjanumeroseuranta.ostorivitunnus = tilausrivi.tunnus
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivin_lisatiedot.rahtikirja_id = '{$rahtikirjanumero}'
            ORDER BY tilausrivin_lisatiedot.juoksu";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return false;
  }

  $rivitunnukset = "";

  while ($row = mysql_fetch_assoc($result)) {

    if ($row['toimitettuaika'] != "0000-00-00 00:00:00") {
      return $rahtikirjanumero;
    }

    $pakkaukset[] = $row;

    $vastaanottajan_ovt = $row['ovttunnus'];

    $laskutunnus = $row['laskutunnus'];

    $tilaus_ja_rivi = $row['asiakkaan_tilausnumero'] .':'. $row['asiakkaan_rivinumero'];

    if (!isset($tilaukset[$tilaus_ja_rivi])) {
      $rekno = $row['kuljetuksen_rekno'];
      $tilaukset[$tilaus_ja_rivi] = array('paino' => $row['tilauksen_paino'], 'id' => $row['asiakkaan_tilausnumero'], 'rivi' => $row['asiakkaan_rivinumero']);
    }

    $rivitunnukset .= $row['tunnus'] . ",";
  }

  $rivitunnukset = rtrim($rivitunnukset, ",");

  $update_query = "UPDATE tilausrivi SET
                   toimitettuaika = NOW(),
                   toimitettu = '{$rekno}'
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND tunnus IN ({$rivitunnukset})";
  pupe_query($update_query);

  foreach ($tilaukset as $key => $tilaus) {
    foreach ($pakkaukset as $pakkaus) {
      if ($pakkaus['asiakkaan_tilausnumero'].$pakkaus['asiakkaan_rivinumero'] == $tilaus['id'].$tilaus['rivi']) {

        $pakkaus['juoksu'] = $pakkaus['juoksu'];
        $pakkaus['paino'] = (int) $pakkaus['paino'];
        $pakkaus['leveys'] = (int) $pakkaus['leveys'];
        unset($pakkaus['ovttunnus'], $pakkaus['asiakkaan_tilausnumero'], $pakkaus['kommentti']);

        $tilaukset[$key]['pakkaukset'][] = $pakkaus;
      }
    }
  }

  $sanomaid = "RP" . time();

  $varasto_info  = array(
    'koodi1' => 'koodi-1',
    'koodi2' => 'koodi-2',
    'koodi3' => 'koodi-3',
    'kaupunki' => 'KOTKA'
  );

  $parametrit = array(
    'laskutunnus' => $laskutunnus,
    'laji' => 'kuittaus',
    'tyyppi' => 'RECADV',
    'rekisterinumero' => $rekno,
    'lahettajan_ovt' => $yhtiorow['ovttunnus'],
    'vastaanottajan_ovt' => $vastaanottajan_ovt,
    'sanomaid' => $sanomaid,
    'sanomanumero' => $rahtikirjanumero,
    'tilaukset' => $tilaukset,
    'varasto_info' => $varasto_info
  );

  return $parametrit;

}

function hylky_lusaus_parametrit($parametrit) {
  global $kukarow, $yhtiorow;

  if ($parametrit['laji'] == 'hylky') {
    $lisatieto = 'Hylättävä';
  }
  elseif ($parametrit['laji'] == 'lusaus') {
   $lisatieto = 'Lusattava';
  }
  else {
    return false;
  }

  $query = "SELECT lasku.ovttunnus,
            tilausrivi.kommentti,
            varastopaikat.tunnus AS varastokoodi,
            varastopaikat.nimitys AS varastonimi,
            sarjanumeroseuranta.massa AS paino,
            tilausrivin_lisatiedot.juoksu,
            tilausrivin_lisatiedot.asiakkaan_rivinumero,
            tilausrivin_lisatiedot.asiakkaan_tilausnumero AS tilauskoodi
            FROM sarjanumeroseuranta
            JOIN tilausrivi ON tilausrivi.yhtio = sarjanumeroseuranta.yhtio
              AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
            JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            JOIN lasku ON lasku.yhtio = lasku.yhtio
              AND lasku.tunnus = tilausrivi.otunnus
            JOIN varastopaikat ON varastopaikat.yhtio = tilausrivi.yhtio
              AND varastopaikat.tunnus = tilausrivi.varasto
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND sarjanumeroseuranta.sarjanumero = '{$parametrit['sarjanumero']}'
            AND sarjanumeroseuranta.lisatieto = '{$lisatieto}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return false;
  }

  $row = mysql_fetch_assoc($result);
  $kommentti_info = explode("#", $row['kommentti']);
  $sanomaid = "RP" . time();
  $sanomanumero = time();
  $juoksunumero = $row['juoksu'];
  $paino = (int) $row['paino'];

  $parametrit = array(
    'tyyppi' => 'INVRPT',
    'lahettajan_ovt' => $yhtiorow['ovttunnus'],
    'vastaanottajan_ovt' => $row['ovttunnus'],
    'sanomaid' => $sanomaid,
    'sanomanumero' => $sanomanumero,
    'sarjanumero' => $parametrit['sarjanumero'],
    'juoksunumero' => $juoksunumero,
    'varastonimi' => $row['varastonimi'],
    'varastokoodi' => $row['varastokoodi'],
    'tilauskoodi' => $row['tilauskoodi'],
    'tilausrivi' => $row['asiakkaan_rivinumero'],
    'paino' => $paino,
    'laji' => $parametrit['laji']
  );

  return $parametrit;
}

function kontitus_parametrit($rullat_kontissa, $korjaus = false) {
  global $kukarow, $yhtiorow;

  $query = "SELECT ss.sarjanumero,
            tr.tunnus,
            tr.keratty,
            tr.toimitettu,
            ss.massa as paino,
            trlt.juoksu,
            trlt.asiakkaan_rivinumero as rivinro,
            lasku.asiakkaan_tilausnumero as tilauskoodi,
            laskun_lisatiedot.matkakoodi,
            laskun_lisatiedot.konttiviite,
            lasku.tunnus AS laskutunnus
            FROM tilausrivi AS tr
            JOIN tilausrivin_lisatiedot AS trlt
              ON trlt.yhtio = tr.yhtio
              AND trlt.tilausrivitunnus = tr.tunnus
            JOIN sarjanumeroseuranta AS ss
              ON ss.yhtio = tr.yhtio
              AND ss.myyntirivitunnus = tr.tunnus
            JOIN lasku
              ON lasku.yhtio = tr.yhtio
              AND lasku.tunnus = tr.otunnus
            JOIN laskun_lisatiedot
              ON laskun_lisatiedot.yhtio = lasku.yhtio
              AND laskun_lisatiedot.otunnus = lasku.tunnus
            WHERE tr.yhtio = '{$kukarow['yhtio']}'
            AND tr.tunnus IN ({$rullat_kontissa})
            AND (ss.lisatieto IS NULL OR ss.lisatieto = 'Lusattu' OR ss.lisatieto = 'Toimitettu')";
  $result = pupe_query($query);

  $tilaukset = array();
  $rullarivit = array();
  $rivipainot = array();
  $total_paino = 0;

  while ($row = mysql_fetch_assoc($result)) {

    $index = $row['tilauskoodi'] . ":" .$row['rivinro'];

    $laskutunnus = $row['laskutunnus'];

    $rullarivit[$index][] = $row;

    $tilaukset[$laskutunnus]['laskutunnus'] = $laskutunnus;

    $rivipainot[$index] = $rivipainot[$index] + $row['paino'];

    $total_paino = $total_paino + $row['paino'];

    $matkakoodi = $row['matkakoodi'];
    $konttiviite = $row['konttiviite'];
  }

  $kontitus_info  = array(
    'lastaaja_ovt' => $yhtiorow['ovttunnus'],
    'omistaja' => $yhtiorow['nimi'],
    'matkakoodi' => $matkakoodi,
    'viite' => $konttiviite,
    'sinetoija' => $yhtiorow['nimi']
  );

  $sanomaid = "RP" . time();
  $sanomanumero = time();

  $parametrit = array(
    'laji' => 'kontitus',
    'tyyppi' => 'DESADV',
    'lahettajan_ovt' => $yhtiorow['ovttunnus'],
    'vastaanottajan_ovt' => '003708274241',
    'sanomaid' => $sanomaid,
    'sanomanumero' => $sanomanumero,
    'rullarivit' => $rullarivit,
    'rivipainot' => $rivipainot,
    'tilauskoodi' => $konttiviite,
    'kontitus_info' => $kontitus_info,
    'total_paino' => $total_paino,
    'tilaukset' => $tilaukset
  );

  return $parametrit;
}

function lahtoilmoitus_parametrit($konttiviite) {
  global $kukarow, $yhtiorow;

  $query = "SELECT
            concat(lasku.asiakkaan_tilausnumero, ':', asiakkaan_rivinumero) AS tilaus_ja_rivi,
            count(tilausrivi.tunnus) AS rullia,
            group_concat(DISTINCT tilausrivin_lisatiedot.konttinumero) kontit,
            sum(sarjanumeroseuranta.massa) AS paino,
            laskun_lisatiedot.konttityyppi,
            laskun_lisatiedot.matkakoodi,
            laskun_lisatiedot.satamavahvistus_pvm AS lahtoaika,
            laskun_lisatiedot.matkatiedot
            FROM lasku
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            JOIN tilausrivin_lisatiedot
              ON tilausrivin_lisatiedot.yhtio = lasku.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            JOIN laskun_lisatiedot
              ON laskun_lisatiedot.yhtio = lasku.yhtio
              AND laskun_lisatiedot.otunnus = lasku.tunnus
            JOIN sarjanumeroseuranta
              ON sarjanumeroseuranta.yhtio = lasku.yhtio
              AND sarjanumeroseuranta.myyntirivitunnus = tilausrivi.tunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND laskun_lisatiedot.konttiviite = '{$konttiviite}'
            AND tilausrivin_lisatiedot.konttinumero != ''
            GROUP BY lasku.asiakkaan_tilausnumero, asiakkaan_rivinumero";
  $result = pupe_query($query);

  $konttilista = '';

  while ($tilausrivi = mysql_fetch_assoc($result)) {

    $konttityyppi = $tilausrivi['konttityyppi'];
    $matkakoodi = $tilausrivi['matkakoodi'];
    $lahtopaiva = $tilausrivi['lahtoaika'];

    $matkatiedot = unserialize($tilausrivi['matkatiedot']);
    $tilausrivi['laiva'] = $matkatiedot['transport_name'];

    $konttilista .= $tilausrivi['kontit'] . ",";

    $tilausrivit[] = $tilausrivi;
  }

  $konttilista = rtrim($konttilista,',');
  $konttilista = explode(',', $konttilista);
  $konttilista = array_unique($konttilista);
  $konttimaara = count($konttilista);

  if ($lahtopaiva != '0000-00-00 00:00:00') {
    $lahtopaiva = date("d.m.Y H:i", strtotime($lahtopaiva));
  }
  else {
    $lahtopaiva = 'X';
  }

  $parametrit['konttimaara'] = $konttimaara;
  $parametrit['konttityyppi'] = $konttityyppi;
  $parametrit['matkakoodi'] = $matkakoodi;
  $parametrit['lahtopaiva'] = $lahtopaiva;
  $parametrit['tilausrivit'] = $tilausrivit;

  return $parametrit;
}

function konttierittely_parametrit($konttiviite) {
  global $kukarow;

  $query = "SELECT
            tilausrivin_lisatiedot.konttinumero,
            tilausrivin_lisatiedot.kontin_mrn,
            tilausrivin_lisatiedot.sinettinumero,
            tilausrivin_lisatiedot.asiakkaan_tilausnumero,
            tilausrivin_lisatiedot.asiakkaan_rivinumero,
            count(tilausrivi.tunnus) AS rullia,
            sum(sarjanumeroseuranta.massa) AS paino
            FROM lasku
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            JOIN tilausrivin_lisatiedot
              ON tilausrivin_lisatiedot.yhtio = lasku.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            JOIN laskun_lisatiedot
              ON laskun_lisatiedot.yhtio = lasku.yhtio
              AND laskun_lisatiedot.otunnus = lasku.tunnus
            JOIN sarjanumeroseuranta
              ON sarjanumeroseuranta.yhtio = lasku.yhtio
              AND sarjanumeroseuranta.myyntirivitunnus = tilausrivi.tunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND laskun_lisatiedot.konttiviite = '{$konttiviite}'
            AND tilausrivin_lisatiedot.konttinumero != ''
            GROUP BY konttinumero, asiakkaan_tilausnumero, asiakkaan_rivinumero";
  $result = pupe_query($query);

  $kontit = array();
  $total_paino = 0;
  $total_rullat = 0;

  while ($rivi = mysql_fetch_assoc($result)) {

    $total_paino += $rivi['paino'];
    $total_rullat += $rivi['rullia'];

    $kontit[$rivi['konttinumero']]['mrn'] = $rivi['kontin_mrn'];
    $kontit[$rivi['konttinumero']]['sinetti'] =$rivi['sinettinumero'];

    $kontit[$rivi['konttinumero']]['paino'] += $rivi['paino'];
    $kontit[$rivi['konttinumero']]['rullia'] += $rivi['rullia'];

    $kontit[$rivi['konttinumero']][$rivi['asiakkaan_tilausnumero']]['paino'] += $rivi['paino'];
    $kontit[$rivi['konttinumero']][$rivi['asiakkaan_tilausnumero']]['rullia'] += $rivi['rullia'];

    $kontit[$rivi['konttinumero']][$rivi['asiakkaan_tilausnumero']][$rivi['asiakkaan_rivinumero']]['paino'] += $rivi['paino'];
    $kontit[$rivi['konttinumero']][$rivi['asiakkaan_tilausnumero']][$rivi['asiakkaan_rivinumero']]['rullia'] += $rivi['rullia'];
  }

  $parametrit['kontit'] = $kontit;
  $parametrit['konttiviite'] = $konttiviite;
  $parametrit['total_paino'] = $total_paino;
  $parametrit['konttimaara'] = count($kontit);
  $parametrit['total_rullat'] = $total_rullat;
  return $parametrit;
}

function satamavahvistus_parametrit($konttiviite) {
  global $kukarow, $yhtiorow;

  $query = "SELECT group_concat(otunnus),
            matkakoodi,
            matkatiedot
            FROM laskun_lisatiedot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND konttiviite = '{$konttiviite}'";
  $result = pupe_query($query);
  $tunnukset = mysql_result($result, 0);
  $matkakoodi = mysql_result($result, 0, 1);
  $matkatiedot = mysql_result($result, 0, 2);

  $matka_info = unserialize($matkatiedot);
  $rullat = array();
  $kontit = array();

  $query = "SELECT trlt.konttinumero,
            trlt.sinettinumero,
            trlt.kontin_kilot,
            trlt.kontin_taarapaino,
            ss.sarjanumero,
            ss.tuoteno,
            ss.hyllyalue,
            ss.hyllynro,
            tilausrivi.tunnus AS tilausrivitunnus
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot AS trlt
              ON trlt.yhtio = tilausrivi.yhtio
              AND trlt.tilausrivitunnus = tilausrivi.tunnus
            JOIN sarjanumeroseuranta AS ss
              ON ss.yhtio = tilausrivi.yhtio
              AND ss.myyntirivitunnus = tilausrivi.tunnus
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus IN ({$tunnukset})
            AND (ss.lisatieto IS NULL OR ss.lisatieto = 'Lusattu' OR ss.lisatieto = 'Toimitettu')";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    if ($row['kontin_kilot'] == 0) {
      return false;
    }
    if (!isset($kontit[$row['konttinumero']])) {

      $kontit[$row['konttinumero']] = array(
          'sinettinumero' => $row['sinettinumero'],
          'paino' => $row['kontin_kilot'],
          'taara' => $row['kontin_taarapaino']
        );
    }
    $rullat[$row['sarjanumero']] = $row;
  }

  $sanomaid = "RP" . time();
  $sanomanumero = time();

  $laskutunnukset = explode(',', $tunnukset);

  $parametrit = array(
    'laji' => 'satamavahvistus',
    'tyyppi' => 'DESADV',
    'lahettajan_ovt' => $yhtiorow['ovttunnus'],
    'vastaanottajan_ovt' => '003708274241',
    'sanomaid' => $sanomaid,
    'sanomanumero' => $sanomanumero,
    'kontit' => $kontit,
    'rullat' => $rullat,
    'tilauskoodi' => $konttiviite,
    'matkakoodi' => $matkakoodi,
    'matka_info' => $matka_info,
    'laskutunnukset' => $laskutunnukset
  );

  return $parametrit;
}

function hae_varastointikaudet($konttiviite) {
  global $kukarow;

  $query = "SELECT
            group_concat(DISTINCT lasku.asiakkaan_tilausnumero) AS tilausnumerot,
            SUM(ss.massa) AS total_paino,
            DATE(otr.toimitettuaika) AS sisaan,
            DATE(tilausrivi.toimitettuaika) AS ulos
            FROM laskun_lisatiedot
            JOIN lasku
              ON lasku.yhtio = laskun_lisatiedot.yhtio
              AND lasku.tunnus = laskun_lisatiedot.otunnus
            JOIN tilausrivi
              ON tilausrivi.yhtio = laskun_lisatiedot.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            JOIN tilausrivin_lisatiedot
              ON tilausrivin_lisatiedot.yhtio = laskun_lisatiedot.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            JOIN sarjanumeroseuranta AS ss
              ON ss.yhtio = laskun_lisatiedot.yhtio
              AND ss.myyntirivitunnus = tilausrivi.tunnus
            JOIN tilausrivi AS otr
              ON otr.yhtio = ss.yhtio
              AND otr.tunnus = ss.ostorivitunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND ss.lisatieto != 'Hylätty'
            AND ss.lisatieto != 'Ylijaama'
            AND laskun_lisatiedot.konttiviite = '{$konttiviite}'
            GROUP BY DATE(otr.toimitettuaika), DATE(tilausrivi.toimitettuaika)";
  $result = pupe_query($query);

  $varastointikaudet = array();

  while ($kausi = mysql_fetch_assoc($result)) {

    $sisaan = new DateTime($kausi['sisaan']);
    $ulos = new DateTime($kausi['ulos']);
    $ero = $ulos->diff($sisaan)->format("%a") + 1;

    $kausi['varastointipaivat'] = $ero;

    $kausi['total_paino'] = (int) $kausi['total_paino'] / 1000;

    $varastointikaudet[] = $kausi;
  }

  return $varastointikaudet;
}

function laskutusraportti_parametrit($konttiviite) {
  global $kukarow;

  $rivit = array();

  $tuotequery = "SELECT *
                 FROM tuote
                 WHERE yhtio = '{$kukarow['yhtio']}'
                 AND tuoteno = 'VARASTOINTI'";
  $tuoteresult = pupe_query($tuotequery);
  $trow = mysql_fetch_assoc($tuoteresult);

  $varastointihinta = $trow['myyntihinta'];

  $query = "SELECT lasku.asiakkaan_tilausnumero,
            tilausrivi.toimitettuaika
            FROM lasku
            JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus
            JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND laskun_lisatiedot.konttiviite = '{$konttiviite}'
            GROUP BY lasku.tunnus";
  $result = pupe_query($query);

  $viimeinen_sinetointi = '0000-00-00 00:00:00';
  $tilaukset = array();
  while ($rivi = mysql_fetch_assoc($result)) {
    $tilaukset[] = $rivi;

    if ($rivi['toimitettuaika'] > $viimeinen_sinetointi) {
      $viimeinen_sinetointi = $rivi['toimitettuaika'];
    }
  }

  $query = "SELECT tilausrivi.*
            FROM tilausrivi
            JOIN lasku
              ON lasku.yhtio = tilausrivi.yhtio
              AND lasku.tunnus = tilausrivi.otunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.asiakkaan_tilausnumero = '{$konttiviite}'";
  $result = pupe_query($query);

  while ($rivi = mysql_fetch_assoc($result)) {

    $sisaan = new DateTime($rivi['toimaika']);
    $ulos = new DateTime($rivi['kerayspvm']);
    $ero = $ulos->diff($sisaan)->format("%a") + 1;

    $rivi['varastopaivat'] = $ero;

    $rivit[] = $rivi;
  }

  $parametrit = array(
    'rivit' => $rivit,
    'konttiviite' => $konttiviite,
    'varastointihinta' => $varastointihinta,
    'tilaukset' => $tilaukset,
    'sinetointi' => $viimeinen_sinetointi
    );

  return $parametrit;
}

function laadi_edifact_sanoma($parametrit, $korjaus = false) {
  global $kukarow;

  //validoidaan parametrit ja setataan muita muuttujia
  //
  extract($parametrit);

  if ($korjaus) {
    $merkityskoodi = '5';
  }
  else {
    $merkityskoodi = '9';
  }

  switch ($laji) {
    case 'kuittaus':
      $lajikoodi = '632';
      $assosiaatiokoodi = 'EXGR13';
      break;
    case 'lusaus':
      $lajikoodi = '6';
      $assosiaatiokoodi = 'EXSC13';
      break;
    case 'hylky':
      $lajikoodi = '34';
      $assosiaatiokoodi = 'EXSC13';
      break;
    case 'kontitus':
      $lajikoodi = '833';
      $assosiaatiokoodi = 'EXTU11';
      break;
    case 'rekkakontitus':
      $lajikoodi = '15';
      $assosiaatiokoodi = 'EXWL16';
      break;
    case 'satamavahvistus':
        $lajikoodi = '122';
        $assosiaatiokoodi = 'EXPC13';
        break;
    default:
      return NULL;
      break;
  }

  // UNA -segmentti - (sama kaikissa sanomissa, ei lasketa segmenttilukemaan)
  //$s = "UNA:+.? '";

  $s = '';

  // UNB -segmentti (ei lasketa segmenttilukemaan)
  $s .= "UNB+UNOC:2+";
  $s .= $lahettajan_ovt . ":30+";
  $s .= $vastaanottajan_ovt . ":30+";
  $s .= date("ydm:Hi");
  $s .= "+" . $sanomaid . "'";

  // UNH -segmentti
  $s .= "UNH+1+" .  $tyyppi . ":D:97B:UN:" . $assosiaatiokoodi . "'";
  $segmentit = 1;

  // BGM -segmentti
  $etuliite = ($tyyppi == 'RECADV') ? 'GRI' : '';
  $s .= "BGM+" .  $lajikoodi . "+" . $etuliite . $sanomanumero . "+" . $merkityskoodi . "'";
  $segmentit++;

  // DTM -segmentti
  $s .= "DTM+137:";
  $s .= date("YmdHi");
  $s .= ":203'";
  $segmentit++;

  if ($laji == 'kuittaus') {
    // DTM -segmentti
    $s .= "DTM+178:";
    $s .= date("YmdHi");
    $s .= ":203'";
    $segmentit++;

    // DTM -segmentti
    $s .= "DTM+163:";
    $s .= date("YmdHi");
    $s .= ":203'";
    $segmentit++;

    // DTM -segmentti
    $s .= "DTM+413:";
    $s .= date("YmdHi");
    $s .= ":203'";
    $segmentit++;
  }

  if ($laji == 'satamavahvistus') {
    // RFF+VON -segmenttilaheta
    $s .= "RFF+VON:" . $matkakoodi . "'";
    $segmentit++;
  }

  if ($laji == 'kontitus') {
    // RFF+VON -segmentti
    $s .= "RFF+VON:" . $kontitus_info['matkakoodi'] . "'";
    $segmentit++;
  }
  elseif ($laji == 'rekkakontitus') {
    // TDT -segmentti
    $s .= "TDT+20++3++". $kuljettaja_info['koodi'] ."::86:". $kuljettaja_info['nimi'] ."+++" . $kuljettaja_info['auto_rekno'] . "'";
    $segmentit++;

    // LOC -segmentti
    $s .= "LOC+8+XXX::86:". $kuljettaja_info['maaranpaa'] ."'";
    $segmentit++;
  }

  if ($laji == 'kontitus' and !empty($kontitus_info['viite'])) {
    // RFF+AKC -segmentti
    $s .= "RFF+AKC:" . $kontitus_info['viite'] . "'";
    $segmentit++;
  }

  if ($laji == 'kuittaus') {
    // RFF+AAM -segmentti
    $s .= "RFF+AAM:" . $sanomanumero . "'";
    $segmentit++;
  }

  if ($tyyppi == 'INVRPT') {
    // RFF+CU -segmentti
    $s .= "RFF+CU:" . $tilauskoodi . ":" . $tilausrivi . "'";
    $segmentit++;

    // NAD+GG  -segmentti
    $s .= "NAD+GG+" . $varastonimi . ":100'";
    $segmentit++;

    // LOC+18  -segmentti
    $s .= "LOC+18+" . $varastokoodi . ":ZZZ'";
    $segmentit++;

    // LIN  -segmentti
    $s .= "LIN+1+9++++ZSG'";
    $segmentit++;
  }

  if ($tyyppi == 'RECADV') {
    // NAD+Z99  -segmentti
    $s .= "NAD+Z99'";
    $segmentit++;
  }

  if ($laji == 'kontitus' and !empty($kontitus_info['omistaja'])) {
    // NAD+CW  -segmentti
    $s .= "NAD+CW+00370:100++" . $kontitus_info['omistaja'] . "'";
    $segmentit++;
  }

  if ($laji == 'kontitus') {
    // NAD+ZST+KCT  -segmentti
    $s .= "NAD+ZST+KCT'";
    $segmentit++;
  }

  if ($laji == 'kontitus' and !empty($kontitus_info['lastaaja_ovt'])) {
    // NAD+LP -segmentti (3. osapuoli lastaajan OVT)
    $s .= "NAD+LP+" . $kontitus_info['lastaaja_ovt'] . "'";
    $segmentit++;
  }

  if ($laji == 'kontitus') {
    // EQD+CN -segmentti (kontti-infoa)
    $s .= "EQD+CN+" . $kontitus_info['konttinumero'] . "+42GP::86+++5'";
    $segmentit++;

    // kokonaispaino
    $s .= "MEA+AAE+ACX+KGM:" . $total_paino ."'";
    $segmentit++;

    // sinetti
    $s .= "SEL+" . $kontitus_info['sinettinumero'] ."+TO:::" . $kontitus_info['sinetoija'] . "'";
    $segmentit++;
  }
  elseif ($laji == 'rekkakontitus') {

    // EQD+CN -segmentti (kontti-infoa)
    $s .= "EQD+TE+". $kuljettaja_info['traileri_rekno'] ."+::TE+++5'";
    $segmentit++;

  }

  if ($tyyppi == 'RECADV' and !empty($varasto_info['koodi1'])) {
    // LOC -segmentti
    $s .= "LOC+18+" . $varasto_info['koodi1'] . "::6:";
    $s .= $varasto_info['kaupunki'] . "+";
    $s .= $varasto_info['koodi2'] . ":::";
    $s .= $varasto_info['koodi3'] . "'";
    $segmentit++;
  }

  if ($laji == 'satamavahvistus') {
    // matkatietoa
    $s .= "TDT+20++1++" . $matka_info['carrier_id'] .":100:ZZZ+++" . $matka_info['transport_id'] .":103::" . $matka_info['transport_name'] ."'";
    $segmentit++;

    // LOC -segmentti lähtöpaikka
    $s .= "LOC+5+" . $matka_info['lahtopaikka_id'] ."::6:" . $matka_info['lahtopaikka_nimi'] ."'";
    $segmentit++;

    // DTM -segmentti lähtöaika
    $s .= "DTM+186:";
    $s .= $matka_info['lahtoaika'];
    $s .= ":203'";
    $segmentit++;

    // LOC -segmentti määränpää
    $s .= "LOC+8+" . $matka_info['valisatama_id'] ."'";
    $segmentit++;

    // TDT -segmentti jatkokuljetus
    $s .= "TDT+30++1++::ZZZ+++:::" . $matka_info['jatko_transport_name'] ."'";
    $segmentit++;

    // LOC -segmentti määränpäätarkenne
    $s .= "LOC+8+" . $matka_info['maaranpaa_id'] ."'";
    $segmentit++;

    foreach ($kontit as $konttiviite => $info) {

      // EQD+CN -segmentti konttinumero
      $s .= "EQD+CN+" . $konttiviite . "+" . $iso_koodi ."::86+++5'";
      $segmentit++;

      // MEA+AAE+ACX -segmentti rullien paino
      $s .= "MEA+AAE+ACX+KGM:" . $info['paino'] . "'";
      $segmentit++;

      // MEA+AAE+T -segmentti kontin paino
      $s .= "MEA+AAE+T+KGM:" . $info['taara'] . "'";
      $segmentit++;

      // SEL -segmentti sinettinumero
      $s .= "SEL+" . $info['sinettinumero'] . "+TO:::STEVECO'";
      $segmentit++;

      // HAN -segmentti
      $s .= "HAN+33::86'";
      $segmentit++;

    }
  }

  if ($tyyppi == 'RECADV') {
    // TDT -segmentti (kuitattavan kuljetusauton rekisterinumero)
    $s .= "TDT+20++3+++++" . $rekisterinumero . ":146'";
    $segmentit++;
  }

  if ($tyyppi == 'INVRPT') {
    // RFF+ALE  -segmentti
    $s .= "RFF+ALE:" . $sanomanumero . "'";
    $segmentit++;

    // DTM -segmentti
    $s .= "DTM+351:";
    $s .= date("YmdHi");
    $s .= ":203'";
    $segmentit++;

    // INV -segmentti
    $s .= "INV+999'";
    $segmentit++;
  }

  if ($laji == 'lusaus') {
    // QTY  -segmentit (poistettu paino ja millit)
    $s .= "QTY+11:" . $poistettu_paino . ":KGM'";
    $segmentit++;

    // ilmeisesti ei tulla muuttamaan halkaisijaa
    // $s .= "QTY+11:" . $poistettu_halkaisija . ":MMT'";
    // $segmentit++;
  }

  if ($tyyppi == 'INVRPT') {
    // GIN -segmentit
    $s .= "GIN+ZPI+" . $juoksunumero . "'";
    $s .= "GIN+ZUN+" . $sarjanumero . "'";
    $segmentit += 2;
  }

  if ($laji == 'lusaus') {
    // STS+G  -segmenti
    $s .= "STS+G:ZSN+2::86+1::86+1::86+7::86'";
    $segmentit++;
  }

  if ($laji == 'hylky') {
    // STS+X  -segmenti
    $s .= "STS+X:ZSN+7::86+3::86+1::86+7::86'";
    $segmentit++;
  }

  if ($tyyppi == 'INVRPT') {
    // GIN -segmentit
    $s .= "STS+SUS:ZSP'";
    $s .= "CPS+PKG'";
    $s .= "PAC+1++RL'";
    $s .= "MEA+AAE+G+KGM:" . $paino . "'";
    $segmentit += 4;
  }

  if ($laji == 'kontitus' or $laji == 'rekkakontitus') {

    foreach ($rullarivit as $key => $rullarivi) {

      $s .= "CPS+MOL++3'";
      $segmentit++;

      $s .= "PAC+" . count($rullarivi) . "++RL'";
      $segmentit++;

      // kokonaispaino
      $s .= "MEA+AAE+G+KGM:" . $rivipainot[$key] ."'";
      $segmentit++;

      $s .= "LIN+999'";
      $segmentit++;

      // DTM -segmentti
      $s .= "DTM+411:";
      $s .= date("YmdHi");
      $s .= ":203'";
      $segmentit++;

      $s .= "RFF+CU:" . $key . "'";
      $segmentit++;

      $s .= "RFF+TN:1'";
      $segmentit++;

      foreach ($rullarivi as $key => $rulla) {

          $s .= "CPS+PKG++3'";
          $segmentit++;

          $s .= "PAC+999'";
          $segmentit++;

          $s .= "MEA+AAE+G+KGM:" . (int) $rulla['paino'] . "'";
          $segmentit++;

          $s .= "PCI+999'";
          $segmentit++;

          $s .= "GIN+ZPI+" . $rulla['juoksu'] ."'";
          $segmentit++;

          $s .= "GIN+ZUN+" . $rulla['sarjanumero'] . "'";
          $segmentit++;

      }
    }
  }

  if ($laji == 'kuittaus') {

    foreach ($tilaukset as $tilaus) {

      // CPS+MOL -segmentti
      $s .= "CPS+MOL++3'";
      $segmentit++;

      // PAC -segmentti
      $s .= "PAC+" . sizeof($tilaus['pakkaukset']) . "'";
      $segmentit++;

      // LIN -segmentti
      $s .= "LIN+999'";
      $segmentit++;

      // QTY -segmentti (vastaanotetun tilauksen kokonaispaino)
      $s .= "QTY+48:" . $tilaus['paino'] . ":KGM'";
      $segmentit++;

      // CDI -segmentti
      $s .= "CDI+1+ZUL::86'";
      $segmentit++;

      // RFF -segmentti
      $s .= "RFF+CU:" . $tilaus['id'] . ":" . $tilaus['rivi'] . "'";
      $segmentit++;

      foreach ($tilaus['pakkaukset'] as  $pakkaus) {

        // CPS+PKG -segmentti
        $s .= "CPS+PKG++3'";
        $segmentit++;

        // PAC -segmentti
        $s .= "PAC+999'";
        $segmentit++;

        // PCI -segmentti
        $s .= "PCI+999'";
        $segmentit++;

        // GIN+ZPI -segmentti
        $s .= "GIN+ZPI+" . $pakkaus['juoksu'] . "'";
        $segmentit++;

        // GIN+ZUN -segmentti
        $s .= "GIN+ZUN+" . $pakkaus['sarjanumero'] . "'";
        $segmentit++;

        // LIN -segmentti
        $s .= "LIN+999'";
        $segmentit++;

        // QTY -segmentti
        $s .= "QTY+48:" . $pakkaus['paino'] . ":KGM'";
        $segmentit++;

      }
    }
  }

  $segmentit++;

  // UNT -segmentti
  $s .= "UNT+" . $segmentit . "+1'";

  // UNZ -segmentti (ei laskettu segmenttilukemaan)
  $s .= "UNZ+1+" . $sanomaid . "'";

  return $s;

}

function kasittele_bookkaussanoma($edi_data) {
  global $kukarow, $yhtiorow;

  $edi_data = str_replace("\n", "", $edi_data);
  $liitedata = $edi_data;
  $edi_data = str_replace("?'", "#%#", $edi_data);
  $edi_data = explode("'", $edi_data);

  $rivimaara = count($edi_data);

  $rahti = array();
  $pakkaukset = array();
  $tilaukset = array();

  foreach ($edi_data as $key => $rivi) {

    trim($rivi);

    $rivi = str_replace("#%#", " ", $rivi);

    if (substr($rivi, 0, 3) == 'UNB') {
      $osat = explode("+", $rivi);

      /* näillä ei nyt olekaan vielä käyttöä
      $vastaanottaja_ovt_info = $osat[3];
      $vastaanottaja_ovt_info_osat = explode(":", $vastaanottaja_ovt_info);
      $vastaanottaja_ovt = $vastaanottaja_ovt_info_osat[0];

      $lahettaja_ovt_info = $osat[2];
      $lahettaja_ovt_info_osat = explode(":", $lahettaja_ovt_info);
      $lahettaja_ovt = $lahettaja_ovt_info_osat[0];
      */

      $sanoma_id = $osat[5];
    }

    // katsotaan onko viesti alkuperäinen vai korvaava vai peruutus (9 vai 5 vai 1)
    // tulee ehkä olemaan oleellinen tieto
    if (substr($rivi, 0, 3) == 'BGM') {
      $osat = explode("+", $rivi);
      $matkakoodi = $osat[2];
      $tyyppi = $osat[3];
    }

    if (substr($rivi, 0, 7) == 'RFF+VON' and !isset($konttiviite)) {
      $osat = explode("+", $rivi);
      $konttiviite_info = $osat[1];
      $konttiviite_info_osat = explode(":", $konttiviite_info);
      $konttiviite = $konttiviite_info_osat[1];
    }

    if (substr($rivi, 0, 6) == "RFF+CU" and !isset($tilausnro)) {
      $osat = explode("+", $rivi);
      $tilaus_info = $osat[1];
      $tilaus_info_osat = explode(":", $tilaus_info);
      $tilausnro = $tilaus_info_osat[1];
      $rivinro = $tilaus_info_osat[2];
    }

    if (substr($rivi, 0, 6) == 'TDT+20') {

      $osat = explode("+", $rivi);

      $carrier_id = $osat[5];

      $transport_info = $osat[8];
      $transport_info_osat = explode(":", $transport_info);
      $transport_id = $transport_info_osat[0];
      $transport_name = $transport_info_osat[3];

      $valmis = false;
      $luetaan = $key;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 5) == "LOC+5") {
          $osat = explode("+", $edi_data[$luetaan]);
          $lahtopaikka_info = $osat[2];
          $lahtopaikka_info_osat = explode(":", $lahtopaikka_info);
          $lahtopaikka_id = $lahtopaikka_info_osat[0];
          $lahtopaikka_nimi = $lahtopaikka_info_osat[3];
        }

        if (substr($edi_data[$luetaan], 0, 5) == "LOC+8") {
          $osat = explode("+", $edi_data[$luetaan]);
          $valisatama_info = $osat[2];
          $valisatama_info_osat = explode(":", $valisatama_info);
          $valisatama_id = $valisatama_info_osat[0];
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 6) == "TDT+30" or $luetaan >= $rivimaara) {
          $valmis = true;
        }
      }
    }


    if (substr($rivi, 0, 6) == 'TDT+30') {

      $osat = explode("+", $rivi);

      $jatko_transport_info = $osat[8];
      $jatko_transport_info_osat = explode(":", $jatko_transport_info);
      $jatko_transport_id = $jatko_transport_info_osat[0];
      $jatko_transport_name = $jatko_transport_info_osat[3];

      $valmis = false;
      $luetaan = $key;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 5) == "LOC+8") {

          $osat = explode("+", $edi_data[$luetaan]);
          $maaranpaa_info = $osat[2];
          $maaranpaa_info_osat = explode(":", $maaranpaa_info);
          $maaranpaa_id = $maaranpaa_info_osat[0];
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "TDT+30" or $luetaan >= $rivimaara) {
          $valmis = true;
        }
      }
    }

    if (substr($rivi, 0, 7) == "DTM+133" and !isset($lahtopvm)) {
      $osat = explode("+", $rivi);
      $lahto_info = $osat[1];
      $lahto_info_osat = explode(":", $lahto_info);
      $lahtoaika = $lahto_info_osat[1];
      $vuosi = substr($lahtoaika, 0, 4);
      $kuu = substr($lahtoaika, 4, 2);
      $paiva = substr($lahtoaika, 6, 2);
      $lahtopvm = $vuosi."-".$kuu."-".$paiva;
    }

    if (substr($rivi, 0, 7) == "FTX+TRA" and !isset($ohje)) {
      $osat = explode("+", $rivi);
      $ohje = $osat[4];
    }

    if (substr($rivi, 0, 6) == 'EQD+CN') {
      $osat = explode("+", $rivi);
      $konttityyppi = $osat[3];
    }

    if (substr($rivi, 0, 3) == 'EQN') {
      $osat = explode("+", $rivi);
      $konttimaara = $osat[1];
    }

    if (substr($rivi, 0, 3) == 'GID') {
      $osat = explode("+", $rivi);
      $rulla_info = $osat[2];
      $rulla_info_osat = explode(":", $rulla_info);
      $rullamaara = $rulla_info_osat[0];
    }
  }

  $matkatiedot = array(
    'carrier_id' => $carrier_id,
    'transport_id' => $transport_id,
    'transport_name' => $transport_name,
    'lahtopaikka_id' => $lahtopaikka_id,
    'lahtopaikka_nimi' => $lahtopaikka_nimi,
    'valisatama_id' => $valisatama_id,
    'jatko_transport_id' => $jatko_transport_id,
    'jatko_transport_name' => $jatko_transport_name,
    'maaranpaa_id' => $maaranpaa_id
    );

  if ($tyyppi == 1) {

    $peruutusparametrit = array(
      'rullamaara' => $rullamaara,
      'tilausnro' => $tilausnro,
      'rivinro' => $rivinro,
      'konttiviite' => $konttiviite,
      'matkakoodi' => $matkakoodi
      );

    peruuta_bookkaus($peruutusparametrit);
    return false;
  }

  if ($tyyppi == 5) {

    $kombo = $tilausnro.":".$rivinro;

    // katsotaan onko löytyykö alkuperäinen
    $query = "SELECT liitostunnus, tunnus, data
              FROM liitetiedostot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND kayttotarkoitus = 'bookkaussanoma'
              AND selite = '{$kombo}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {

      // käsitellään niin kuin olisi alkuperäinen
      $tyyppi = 9;
    }
    else {

      $liiteinfo = mysql_fetch_assoc($result);

      $filesize = strlen($liitedata);
      $uusi_liitedata = mysql_real_escape_string($liitedata);

      $muutosparametrit = array(
        'filesize' => $filesize,
        'uusi_liitedata' => $uusi_liitedata,
        'laskutunnus' => $liiteinfo['liitostunnus'],
        'liitetunnus' => $liiteinfo['tunnus'],
        'liitedata' => $liiteinfo['data'],
        'lahtopvm' => $lahtopvm,
        'ohje' => $ohje,
        'rullamaara' => $rullamaara,
        'tilausnro' => $tilausnro,
        'rivinro' => $rivinro,
        'konttiviite' => $konttiviite,
        'konttityyppi' => $konttityyppi,
        'matkakoodi' => $matkakoodi,
        'matkatiedot' => $matkatiedot,
        'konttimaara' => $konttimaara,
        'sanoma_id' => $sanoma_id
        );

      muuta_bookkaus($muutosparametrit);
      return false;
    }
  }

  $matkatiedot = serialize($matkatiedot);
  $matkatiedot = mysql_real_escape_string($matkatiedot);

  // tässä vaiheessa vastaanottaja on aina steveco
  $asiakas_id = 106;

  // tarkistetaan onko tämä sanoma jostakin syystä jo käsitelty
  $query = "SELECT tunnus
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND filename = '{$sanoma_id}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 0) {
    return false;
  }

  // katsotaan onko tilauksesta luotu jo myyntitilaus
  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND asiakkaan_tilausnumero = '{$tilausnro}'
            AND tilaustyyppi = 'N'";
  $result = pupe_query($query);

  $kukarow['kesken'] = 0;

  $tarvitaan_uusi_tilaus = false;
  $tunnus_johon_liitetaan = 0;
  $kviitteet = array();

  if (mysql_num_rows($result) == 1) {
    $lasku = mysql_fetch_assoc($result);
    $tunnus_johon_liitetaan = $lasku['tunnus'];
  }
  elseif (mysql_num_rows($result) > 1) {

    // tutkitaan jakautuuko tilaus moneen konttiviitteeseen
    $kv_query = "SELECT laskun_lisatiedot.konttiviite,
                 lasku.tunnus
                 FROM lasku
                 JOIN laskun_lisatiedot
                  ON laskun_lisatiedot.yhtio = lasku.yhtio
                  AND laskun_lisatiedot.otunnus = lasku.tunnus
                 WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                 AND lasku.asiakkaan_tilausnumero = '{$tilausnro}'
                 AND lasku.tilaustyyppi = 'N'";
    $kv_result = pupe_query($kv_query);

    while ($laskuinfo = mysql_fetch_assoc($kv_result)) {
      $kviitteet[] = $laskuinfo;
    }
  }

  if (count($kviitteet) == 1 and $kviitteet[0]['konttiviite'] != $konttiviite and $kviitteet[0]['konttiviite'] != 'bookkaukseton') {
    $tarvitaan_uusi_tilaus = true;
  }

  if (count($kviitteet) > 1) {
    $tarvitaan_uusi_tilaus = true;
    foreach ($kviitteet as $kviite) {
      if ($kviite['konttiviite'] == $konttiviite) {
        $tunnus_johon_liitetaan = $kviite['tunnus'];
        $tarvitaan_uusi_tilaus = false;
        break;
      }
    }
  }

  $tarvitaan_uusi_tilaus = true;

  if (mysql_num_rows($result) == 0 or $tarvitaan_uusi_tilaus) {

    require_once "tilauskasittely/luo_myyntitilausotsikko.inc";

    $tunnus = luo_myyntitilausotsikko('RIVISYOTTO', $asiakas_id);

    $update_query = "UPDATE lasku SET
                     asiakkaan_tilausnumero = '{$tilausnro}',
                     sisviesti1 = '{$ohje}',
                     toimaika = '{$lahtopvm}'
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus = '{$tunnus}'";
    pupe_query($update_query);

    $update_query = "UPDATE laskun_lisatiedot SET
                     konttiviite  = '{$konttiviite}',
                     konttimaara  = '{$konttimaara}',
                     konttityyppi = '{$konttityyppi}',
                     matkakoodi   = '{$matkakoodi}',
                     rullamaara   = '{$rullamaara}',
                     matkatiedot  = '{$matkatiedot}'
                     WHERE yhtio  = '{$kukarow['yhtio']}'
                     AND otunnus  = '{$tunnus}'";
    pupe_query($update_query);

    // katsotaan onko tilaukseen kuuluvia rullia ostotilauksilla
    $query = "SELECT tr.toimitettuaika, trlt.*
              FROM tilausrivin_lisatiedot AS trlt
              JOIN tilausrivi AS tr
                ON tr.yhtio = trlt.yhtio
                AND tr.tunnus = trlt.tilausrivitunnus
              JOIN sarjanumeroseuranta AS ss
                ON ss.yhtio = trlt.yhtio
                AND ss.ostorivitunnus = trlt.tilausrivitunnus
              WHERE ss.yhtio = '{$kukarow['yhtio']}'
              AND asiakkaan_tilausnumero = '{$tilausnro}'
              AND asiakkaan_rivinumero = '{$rivinro}'
              AND ss.myyntirivitunnus = 0";
    $result = pupe_query($query);

    $rullat = array();

    while ($rulla = mysql_fetch_assoc($result)) {
      $rullat[] = $rulla;
    }

    if (count($rullat) > 0) {

      $laskuquery = "SELECT *
                     FROM lasku
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus = '{$tunnus}'";
      $laskuresult = pupe_query($laskuquery);
      $laskurow = mysql_fetch_assoc($laskuresult);

      $kukarow['kesken'] = $laskurow['tunnus'];

      // haetaan tuotteen tiedot
      $tuotequery = "SELECT *
                     FROM tuote
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tuoteno = '123'";
      $tuoteresult = pupe_query($tuotequery);

      $trow = mysql_fetch_assoc($tuoteresult);

      $kpl = 1;

      if ($rulla['toimitettuaika'] == '0000-00-00 00:00:00') {
        $var = 'P';
      }
      else {
        $var = '';
      }

      foreach ($rullat as $rulla) {

        require "tilauskasittely/lisaarivi.inc";

        $update_query = "UPDATE tilausrivi
                         SET var2 = 'OK'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tunnus = '{$lisatty_tun}'";
        pupe_query($update_query);

        $update_query = "UPDATE sarjanumeroseuranta
                         SET myyntirivitunnus = '{$lisatty_tun}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND ostorivitunnus = '{$rulla['tilausrivitunnus']}'";
        pupe_query($update_query);

        $update_query = "UPDATE tilausrivin_lisatiedot SET
                         juoksu = '{$rulla['juoksu']}',
                         tilauksen_paino = '{$rulla['tilauksen_paino']}',
                         asiakkaan_tilausnumero = '{$rulla['asiakkaan_tilausnumero']}',
                         asiakkaan_rivinumero = '{$rulla['asiakkaan_rivinumero']}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tilausrivitunnus = '{$lisatty_tun}'";
        pupe_query($update_query);
      }
    }
  }
  else {

    if ($tunnus_johon_liitetaan > 0) {

      $query = "SELECT lasku.tunnus,
                laskun_lisatiedot.rullamaara
                FROM lasku
                JOIN laskun_lisatiedot
                  ON laskun_lisatiedot.yhtio = lasku.yhtio
                  AND laskun_lisatiedot.otunnus = lasku.tunnus
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND lasku.tunnus = '{$tunnus_johon_liitetaan}'";
      $result = pupe_query($query);

    }
    else {

      $query = "SELECT lasku.tunnus,
                laskun_lisatiedot.rullamaara
                FROM lasku
                JOIN laskun_lisatiedot
                  ON laskun_lisatiedot.yhtio = lasku.yhtio
                  AND laskun_lisatiedot.otunnus = lasku.tunnus
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND lasku.asiakkaan_tilausnumero = '{$tilausnro}'
                AND lasku.tilaustyyppi = 'N'";
      $result = pupe_query($query);

    }

    $laskuinfo = mysql_fetch_assoc($result);
    $tunnus = $laskuinfo['tunnus'];
    $rullamaara = $rullamaara + $laskuinfo['rullamaara'];

    $update_query = "UPDATE laskun_lisatiedot SET
                      konttiviite  = '{$konttiviite}',
                      konttimaara  = '{$konttimaara}',
                      konttityyppi = '{$konttityyppi}',
                      matkakoodi   = '{$matkakoodi}',
                      rullamaara   = '{$rullamaara}',
                      matkatiedot  = '{$matkatiedot}'
                      WHERE yhtio  = '{$kukarow['yhtio']}'
                      AND otunnus  = '{$tunnus}'";
    pupe_query($update_query);

    $update_query = "UPDATE lasku SET
                     sisviesti1 = '{$ohje}',
                     toimaika = '{$lahtopvm}'
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus = '{$tunnus}'";
    pupe_query($update_query);

    // katsotaan onko tilaukseen kuuluvia rullia myyntitilauksella
    $rquery = "SELECT *
              FROM tilausrivin_lisatiedot
              JOIN sarjanumeroseuranta AS ss
                ON ss.yhtio = tilausrivin_lisatiedot.yhtio
                AND ss.myyntirivitunnus = tilausrivin_lisatiedot.tilausrivitunnus
              WHERE ss.yhtio = '{$kukarow['yhtio']}'
              AND asiakkaan_tilausnumero = '{$tilausnro}'
              AND asiakkaan_rivinumero = '{$rivinro}'";
    $rresult = pupe_query($rquery);

    $myyntirullat = array();

    while ($rulla = mysql_fetch_assoc($rresult)) {
      $myyntirullat[] = $rulla;
    }

    // katsotaan onko tilaukseen kuuluvia rullia ostotilauksilla
    $rquery = "SELECT *
              FROM tilausrivin_lisatiedot
              JOIN sarjanumeroseuranta AS ss
                ON ss.yhtio = tilausrivin_lisatiedot.yhtio
                AND ss.ostorivitunnus = tilausrivin_lisatiedot.tilausrivitunnus
              WHERE ss.yhtio = '{$kukarow['yhtio']}'
              AND asiakkaan_tilausnumero = '{$tilausnro}'
              AND asiakkaan_rivinumero = '{$rivinro}'";
    $rresult = pupe_query($rquery);

    $ostorullat = array();

    while ($rulla = mysql_fetch_assoc($rresult)) {
      $ostorullat[] = $rulla;
    }

    if (count($myyntirullat) == 0 and count($ostorullat) > 0) {

      $laskuquery = "SELECT *
                     FROM lasku
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus = '{$tunnus}'";
      $laskuresult = pupe_query($laskuquery);
      $laskurow = mysql_fetch_assoc($laskuresult);

      $kukarow['kesken'] = $laskurow['tunnus'];

      // haetaan tuotteen tiedot
      $tuotequery = "SELECT *
                     FROM tuote
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tuoteno = '123'";
      $tuoteresult = pupe_query($tuotequery);

      $trow = mysql_fetch_assoc($tuoteresult);

      $kpl = 1;
      $var = '';

      foreach ($rullat as $rulla) {

        require "tilauskasittely/lisaarivi.inc";

        $update_query = "UPDATE tilausrivi
                         SET var2 = 'OK'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tunnus = '{$lisatty_tun}'";
        pupe_query($update_query);

        $update_query = "UPDATE sarjanumeroseuranta
                         SET myyntirivitunnus = '{$lisatty_tun}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND ostorivitunnus = '{$rulla['tilausrivitunnus']}'";
        pupe_query($update_query);

        $update_query = "UPDATE tilausrivin_lisatiedot SET
                         juoksu = '{$rulla['juoksu']}',
                         tilauksen_paino = '{$rulla['tilauksen_paino']}',
                         asiakkaan_tilausnumero = '{$rulla['asiakkaan_tilausnumero']}',
                         asiakkaan_rivinumero = '{$rulla['asiakkaan_rivinumero']}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tilausrivitunnus = '{$lisatty_tun}'";
        pupe_query($update_query);
      }
    }
  }

  $filesize = strlen($liitedata);
  $liitedata = mysql_real_escape_string($liitedata);

  // tarkistetaan onko vastaava sanoma jo liitetiedostona
  $query = "SELECT tunnus
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND selite = '{$tilausnro}:{$rivinro}'
            AND kayttotarkoitus = 'bookkaussanoma'";
  $vastaavuusresult = pupe_query($query);
  $osumia = mysql_num_rows($vastaavuusresult);

  if ($osumia == 0) {

    $query = "INSERT INTO liitetiedostot SET
              yhtio           = '{$kukarow['yhtio']}',
              liitos          = 'lasku',
              liitostunnus    = '{$tunnus}',
              selite          = '{$tilausnro}:{$rivinro}',
              laatija         = '{$kukarow['kuka']}',
              luontiaika      = NOW(),
              data            = '{$liitedata}',
              filename        = '{$sanoma_id}',
              filesize        = '{$filesize}',
              filetype        = 'text/plain',
              kayttotarkoitus = 'bookkaussanoma'";
    pupe_query($query);

  }

  $query = "UPDATE kuka
            SET kesken = 0
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND kuka = '{$kukarow['kuka']}'";
  pupe_query($query);
}

function kasittele_rahtikirjasanoma($edi_data) {
  global $kukarow, $yhtiorow;

  $edi_data = str_replace("\n", "", $edi_data);

  // otetaan talteen liitetiedoston lisäämistä varten
  $filesize = strlen($edi_data);
  $liitedata = mysql_real_escape_string($edi_data);

  $edi_data = explode("'", $edi_data);

  $rivimaara = count($edi_data);

  // luetaan kaikki rivit
  foreach ($edi_data as $rivi => $value) {

    if (substr($value, 0, 3) == 'UNB') {

      $osat = explode("+", $value);

      $lahettaja_id_info = $osat[2];
      $lahettaja_id_info_osat = explode(":", $lahettaja_id_info);
      $lahettaja_id = $lahettaja_id_info_osat[0];

      $vastaanottaja_id_info = $osat[3];
      $vastaanottaja_id_info_osat = explode(":", $vastaanottaja_id_info);
      $vastaanottaja_id = $vastaanottaja_id_info_osat[0];

      $sanoma_id = $osat[5];

      $valmis = false;
      $luetaan = $rivi;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 3) == "BGM") {
          $osat = explode("+", $edi_data[$luetaan]);
          $rahtikirja_id = $osat[2];
          $tyyppi = $osat[3];
        }

        if (substr($edi_data[$luetaan], 0, 6) == "NAD+FX") {
          $osat = explode("+", $edi_data[$luetaan]);
          $vastaanottaja_info = $osat[2];
          $vastaanottaja_info_osat = explode(":", $vastaanottaja_info);
          $vastaanottaja = $vastaanottaja_info_osat[0];
        }

        if (substr($edi_data[$luetaan], 0, 6) == "NAD+CZ") {
          $osat = explode("+", $edi_data[$luetaan]);
          $lahettaja = $osat[4];
        }

        if (substr($edi_data[$luetaan], 0, 3) == "TDT") {
          $osat = explode("+", $edi_data[$luetaan]);
          $kuljettaja_info = $osat[5];
          $kuljettaja_info_osat = explode(":", $kuljettaja_info);
          $kuljettaja = $kuljettaja_info_osat[3];
          $rekno = $osat[8];
        }

        /*
        if (substr($edi_data[$luetaan], 0, 3) == "EQD+RR") {
          $osat = explode("+", $edi_data[$luetaan]);
          $kuljettaja_info = $osat[5];
          $kuljettaja_info_osat = explode(":", $kuljettaja_info);
          $kuljettaja = $kuljettaja_info_osat[3];
          $rekno = $osat[8];
        }

        */

        if (substr($edi_data[$luetaan], 0, 5) == "LOC+8") {
          $osat = explode("+", $edi_data[$luetaan]);
          $paamaara_info = $osat[2];
          $paamaara_info_osat = explode(":", $paamaara_info);
          $paamaara = $paamaara_info_osat[3];

          /*
          // haetaan varaston tiedot
          $query = "SELECT tunnus
                    FROM varastopaikat
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND locate(nimitys, '{$paamaara}') > 0
                    LIMIT 1";
          $varastores = pupe_query($query);
          $varasto_id = mysql_result($varastores,0);
          */
          $varasto_id = 101;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "DTM+132") {
          $osat = explode("+", $edi_data[$luetaan]);
          $toimitusaika_info = $osat[1];
          $toimitusaika_info_osat = explode(":", $toimitusaika_info);
          $toimitusaika = $toimitusaika_info_osat[1];
          $vuosi = substr($toimitusaika, 0,4);
          $kuu = substr($toimitusaika, 4,2);
          $paiva = substr($toimitusaika, 6,2);
          $toimitusaika = $vuosi.'-'.$kuu.'-'.$paiva;
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "CPS+MOL" or $luetaan >= $rivimaara) {
          $valmis = true;
        }
      }

      $rahti = array(
        'sanoma_id' => $sanoma_id,
        'rahtikirja_id' => $rahtikirja_id,
        'tyyppi' => $tyyppi,
        'sender_id' => $lahettaja_id,
        'recipient_id' => $vastaanottaja_id,
        'vastaanottaja' => $vastaanottaja,
        'lahettaja' => $lahettaja,
        'kuljettaja' => $kuljettaja,
        'rekisterinumero' => $rekno,
        'paamaara' => $paamaara,
        'varasto_id' => $varasto_id,
        'toimitusaika' => $toimitusaika
        );
    }

    if (substr($value, 0, 7) == 'CPS+MOL') {

      $valmis = false;
      $luetaan = $rivi;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 15) == "MEA+AAE+AAL+KGM") {
          $osat = explode("+", $edi_data[$luetaan]);
          $paino_info = $osat[3];
          $paino_info_osat = explode(":", $paino_info);
          $_paino = $paino_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 6) == "RFF+CU") {
          $osat = explode("+", $edi_data[$luetaan]);
          $tilaus_info = $osat[1];
          $tilaus_info_osat = explode(":", $tilaus_info);
          $_tilausnro = $tilaus_info_osat[1];
          $_rivi = $tilaus_info_osat[2];
        }

        if (substr($edi_data[$luetaan], 0, 7) == "RFF+VON") {
          $osat = explode("+", $edi_data[$luetaan]);
          $matka_info = $osat[1];
          $matka_info_osat = explode(":", $matka_info);
          $_matkakoodi = $matka_info_osat[1];

          if ($_matkakoodi === NULL) {
            $_matkakoodi = 'bookkaukseton';
          }

          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "CPS+PKG" or $luetaan >= $rivimaara) {

          if ($_matkakoodi === NULL) {
            $_matkakoodi = 'bookkaukseton';
          }

          $valmis = true;
        }

      }

    }

    if (substr($value, 0, 7) == 'CPS+PKG') {

      $valmis = false;
      $luetaan = $rivi;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 15) == "MEA+AAE+AAL+KGM") {
          $osat = explode("+", $edi_data[$luetaan]);
          $paino_info = $osat[3];
          $paino_info_osat = explode(":", $paino_info);
          $paino = $paino_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 14) == "MEA+AAE+DI+MMT") {
          $osat = explode("+", $edi_data[$luetaan]);
          $halkaisija_info = $osat[3];
          $halkaisija_info_osat = explode(":", $halkaisija_info);
          $halkaisija = $halkaisija_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 14) == "MEA+AAE+WD+MMT") {
          $osat = explode("+", $edi_data[$luetaan]);
          $leveys_info = $osat[3];
          $leveys_info_osat = explode(":", $leveys_info);
          $leveys = $leveys_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 7) == "GIN+ZUN") {
          $osat = explode("+", $edi_data[$luetaan]);
          $sarjanumero = $osat[2];
        }

        if (substr($edi_data[$luetaan], 0, 7) == "GIN+ZPI") {
          $osat = explode("+", $edi_data[$luetaan]);
          $juoksu = $osat[2];
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "CPS+PKG" or $luetaan >= $rivimaara) {
          $valmis = true;
        }
      }

      $tuoteno = '123';

      $rullat[] = array(
        'paino' => $paino,
        'halkaisija' => $halkaisija,
        'leveys' => $leveys,
        'tuoteno' => $tuoteno,
        'juoksu' => $juoksu,
        'sarjanumero' => $sarjanumero,
        'matkakoodi' => $_matkakoodi,
        'tilausnro' => $_tilausnro,
        'rivinro' => $_rivi,
        'tilauksen_paino' => $_paino
        );

    }
  }// rivit luettu

  $rahti['rullat'] = $rullat;

  $data = $rahti;

  // tarkistetaan onko tämä sanoma jo käsitelty
  $query = "SELECT *
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND (filename = '{$data['sanoma_id']}' OR selite = '{$data['rahtikirja_id']}')
            AND kayttotarkoitus = 'rahtikirjasanoma'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 0) {

    $liiterivi = mysqL_fetch_assoc($result);

    $query = "SELECT GROUP_CONCAT(DISTINCT tilausrivin_lisatiedot.asiakkaan_tilausnumero SEPARATOR '\',\'') AS tilausnumerot
              FROM lasku
              JOIN tilausrivi
                ON tilausrivi.yhtio = lasku.yhtio
                AND tilausrivi.otunnus = lasku.tunnus
              JOIN tilausrivin_lisatiedot
                ON tilausrivin_lisatiedot.yhtio = lasku.yhtio
                AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.tunnus = '{$liiterivi['liitostunnus']}'";
    $result = pupe_query($query);
    $laskuinfo = mysql_fetch_assoc($result);

    if (is_null($laskuinfo['tilausnumerot'])) {
      return false;
    }

    $query = "SELECT lasku.*,
              count(tilausrivi.tunnus) AS rullia
              FROM lasku
              LEFT JOIN tilausrivi
                ON tilausrivi.yhtio = lasku.yhtio
                AND tilausrivi.otunnus = lasku.tunnus
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.asiakkaan_tilausnumero IN ('{$laskuinfo['tilausnumerot']}')
              GROUP BY lasku.tunnus";
    $result = pupe_query($query);

    $ilman_rahtikirjaa_tulleet_bookkaukset = array();

    if (mysql_num_rows($result) > 0) {

      while ($lasku = mysql_fetch_assoc($result)) {
        if ($lasku['rullia'] == 0) {
          $ilman_rahtikirjaa_tulleet_bookkaukset[] = $lasku;
        }
      }
    }

    if (count($ilman_rahtikirjaa_tulleet_bookkaukset) < 1) {

      if ($data['tyyppi'] == 7) {
        $lisataan_rullia_vain_myyntitilaukseen = true;
      }
      else {
        return false;
      }

    }
    else {
      $lisataan_rullia_vain_myyntitilaukseen = true;
    }
  }

  if (!$lisataan_rullia_vain_myyntitilaukseen) {

    require_once "inc/luo_ostotilausotsikko.inc";

    // haetaan toimittajan tiedot
    $query = "SELECT *
              FROM toimi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND REPLACE(nimi, ' ', '') = REPLACE('{$data['lahettaja']}', ' ', '')";
    $toimres = pupe_query($query);
    $toimrow = mysql_fetch_assoc($toimres);

    $params = array(
      'liitostunnus' => $toimrow['tunnus'],
      'nimi' => $toimrow['nimi'],
      'myytil_toimaika' => $data['toimitusaika'],
      'varasto' => $data['varasto_id'],
      'osoite' => $toimrow['osoite'],
      'postino' => $toimrow['postino'],
      'postitp' => $toimrow['postitp'],
      'maa' => $toimrow['maa'],
      'uusi_ostotilaus' => 'JOO'
    );

    $laskurow = luo_ostotilausotsikko($params);

    // tarkistetaan onko vastaava sanoma jo liitetiedostona
    $query = "SELECT tunnus
              FROM liitetiedostot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND CONCAT(liitostunnus,selite) = '{$laskurow['tunnus']}{$data['rahtikirja_id']}'
              AND kayttotarkoitus = 'rahtikirjasanoma'";
    $vastaavuusresult = pupe_query($query);

    if (mysql_num_rows($vastaavuusresult) == 0) {

      $query = "INSERT INTO liitetiedostot set
                yhtio           = '{$kukarow['yhtio']}',
                liitos          = 'lasku',
                selite          = '{$data['rahtikirja_id']}',
                liitostunnus    = '{$laskurow['tunnus']}',
                laatija         = '{$kukarow['kuka']}',
                luontiaika      = now(),
                data            = '$liitedata',
                filename        = '{$data['sanoma_id']}',
                filesize        = '{$filesize}',
                filetype        = 'text/plain',
                kayttotarkoitus = 'rahtikirjasanoma'";
      pupe_query($query);

    }
    elseif ($data['tyyppi'] == 5) {

      $korvattava = mysql_result($vastaavuusresult, 0);

      $query = "UPDATE liitetiedostot SET
                data        = '$liitedata',
                muutospvm   = NOW(),
                muuttaja    = '{$kukarow['kuka']}',
                filename    = '{$data['sanoma_id']}',
                filesize    = '{$filesize}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND  tunnus = '{$korvattava}'";
      pupe_query($query);
    }
  }

  $ostolaskurow = $laskurow;

  foreach ($data['rullat'] as $rulla) {

    $query = "SELECT tunnus
              FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND sarjanumero = '{$rulla['sarjanumero']}'";
    $sarjares = pupe_query($query);

    // haetaan tuotteen tiedot
    $query = "SELECT *
              FROM tuote
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$rulla['tuoteno']}'";
    $tuoteres = pupe_query($query);

    $trow = mysql_fetch_assoc($tuoteres);
    $kpl = 1;
    $kerayspvm = $toimaika = $data['toimitusaika'];
    $toim = '';
    $hinta = 0;
    $var = 'P';
    $kutsuja = '';
    $kpl2 = 0;
    $toimittajan_tunnus = '';

    if (mysql_num_rows($sarjares) == 0) {

      $laskurow = $ostolaskurow;
      $kukarow['kesken'] = $laskurow['tunnus'];

      require "tilauskasittely/lisaarivi.inc";

      $query = "UPDATE tilausrivin_lisatiedot SET
                rahtikirja_id = '{$data['rahtikirja_id']}',
                juoksu = '{$rulla['juoksu']}',
                tilauksen_paino = '{$rulla['tilauksen_paino']}',
                kuljetuksen_rekno = '{$data['rekisterinumero']}',
                asiakkaan_tilausnumero = '{$rulla['tilausnro']}',
                asiakkaan_rivinumero = '{$rulla['rivinro']}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tilausrivitunnus = '{$lisatty_tun}'";
      pupe_query($query);

      $query = "INSERT INTO sarjanumeroseuranta SET
                yhtio           = '{$kukarow['yhtio']}',
                tuoteno         = '{$rulla['tuoteno']}',
                sarjanumero     = '{$rulla['sarjanumero']}',
                massa           = '{$rulla['paino']}',
                leveys          = '{$rulla['leveys']}',
                halkaisija      = '{$rulla['halkaisija']}',
                ostorivitunnus  = '{$lisatty_tun}',
                era_kpl         = '1',
                laatija         = '{$kukarow['kuka']}',
                luontiaika      = NOW()";
      pupe_query($query);

      // katsotaan onko tilauksesta luotu jo myyntitilaus
      $query = "SELECT lasku.*, laskun_lisatiedot.matkakoodi, laskun_lisatiedot.konttiviite
                FROM lasku
                JOIN laskun_lisatiedot
                  ON laskun_lisatiedot.yhtio = lasku.yhtio
                  AND laskun_lisatiedot.otunnus = lasku.tunnus
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND lasku.asiakkaan_tilausnumero = '{$rulla['tilausnro']}'
                AND lasku.tilaustyyppi = 'N'";
      $result = pupe_query($query);

      $laskurow = false;

      if (mysql_num_rows($result) > 0) {
        while($tilaus = mysql_fetch_assoc($result)) {

          if ($tilaus['matkakoodi'] == $rulla['matkakoodi']) {
            $laskurow = $tilaus;
            break;
          }
        }
      }

      if (!$laskurow and $rulla['matkakoodi'] == "bookkaukseton") {

        $kukarow['kesken'] = 0;

        require_once "tilauskasittely/luo_myyntitilausotsikko.inc";

        $tunnus = luo_myyntitilausotsikko('RIVISYOTTO', 106);

        $update_query = "UPDATE lasku SET
                         asiakkaan_tilausnumero = '{$rulla['tilausnro']}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tunnus = '{$tunnus}'";
        pupe_query($update_query);

        $update_query = "UPDATE laskun_lisatiedot SET
                         konttiviite = 'bookkaukseton',
                         matkakoodi = 'bookkaukseton'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND otunnus = '{$tunnus}'";
        pupe_query($update_query);

        $laskuquery = "SELECT *
                       FROM lasku
                       WHERE yhtio = '{$kukarow['yhtio']}'
                       AND tunnus = '{$tunnus}'";
        $laskuresult = pupe_query($laskuquery);
        $laskurow = mysql_fetch_assoc($laskuresult, 0);

      }

      if ($laskurow) {

        $kukarow['kesken'] = $laskurow['tunnus'];
        $var = 'P';
        require "tilauskasittely/lisaarivi.inc";

        $update_query = "UPDATE lasku SET
                         tila = 'N',
                         alatila = ''
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tunnus = '{$laskurow['tunnus']}'";
        pupe_query($update_query);

        $update_query = "UPDATE tilausrivin_lisatiedot SET
                         juoksu = '{$rulla['juoksu']}',
                         tilauksen_paino = '{$rulla['tilauksen_paino']}',
                         asiakkaan_tilausnumero = '{$rulla['tilausnro']}',
                         asiakkaan_rivinumero = '{$rulla['rivinro']}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tilausrivitunnus = '{$lisatty_tun}'";
        pupe_query($update_query);

        $update_query = "UPDATE tilausrivi
                         SET var2 = 'OK'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tunnus = '{$lisatty_tun}'";
        pupe_query($update_query);

        $update_query = "UPDATE sarjanumeroseuranta
                         SET myyntirivitunnus = '{$lisatty_tun}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND sarjanumero = '{$rulla['sarjanumero']}'";
        pupe_query($update_query);

      }
    }
    elseif ($lisataan_rullia_vain_myyntitilaukseen) {

      if ($rulla['tilausnro'] == $ilman_rahtikirjaa_tulleet_bookkaukset[0]['asiakkaan_tilausnumero']) {

        $kukarow['kesken'] = $tunnus = $ilman_rahtikirjaa_tulleet_bookkaukset[0]['tunnus'];
        $laskurow = $ilman_rahtikirjaa_tulleet_bookkaukset[0];

        $var = 'P';
        require "tilauskasittely/lisaarivi.inc";

        $update_query = "UPDATE lasku SET
                         tila = 'N',
                         alatila = ''
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tunnus = '{$tunnus}'";
        pupe_query($update_query);

        $update_query = "UPDATE tilausrivin_lisatiedot SET
                         juoksu = '{$rulla['juoksu']}',
                         tilauksen_paino = '{$rulla['tilauksen_paino']}',
                         asiakkaan_tilausnumero = '{$rulla['tilausnro']}',
                         asiakkaan_rivinumero = '{$rulla['rivinro']}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tilausrivitunnus = '{$lisatty_tun}'";
        pupe_query($update_query);

        $update_query = "UPDATE tilausrivi
                         SET var2 = 'OK'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tunnus = '{$lisatty_tun}'";
        pupe_query($update_query);

        $update_query = "UPDATE sarjanumeroseuranta
                         SET myyntirivitunnus = '{$lisatty_tun}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND sarjanumero = '{$rulla['sarjanumero']}'";
        pupe_query($update_query);
      }
      else {

        $query = "SELECT *
                  FROM lasku
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND asiakkaan_tilausnumero = '{$rulla['tilausnro']}'
                  AND tilaustyyppi = 'N'";
        $result = pupe_query($query);
        $laskurow = mysqL_fetch_assoc($result);

        if ($laskurow) {

          $query = "SELECT tunnus
                    FROM sarjanumeroseuranta
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND sarjanumero = '{$rulla['sarjanumero']}'
                    AND myyntirivitunnus = 0";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 1) {

            $kukarow['kesken'] = $laskurow['tunnus'];

            require "tilauskasittely/lisaarivi.inc";

            $update_query = "UPDATE tilausrivin_lisatiedot SET
                             juoksu = '{$rulla['juoksu']}',
                             tilauksen_paino = '{$rulla['tilauksen_paino']}',
                             asiakkaan_tilausnumero = '{$rulla['tilausnro']}',
                             asiakkaan_rivinumero = '{$rulla['rivinro']}'
                             WHERE yhtio = '{$kukarow['yhtio']}'
                             AND tilausrivitunnus = '{$lisatty_tun}'";
            pupe_query($update_query);

            $update_query = "UPDATE tilausrivi
                             SET var2 = 'OK'
                             WHERE yhtio = '{$kukarow['yhtio']}'
                             AND tunnus = '{$lisatty_tun}'";
            pupe_query($update_query);

            $update_query = "UPDATE sarjanumeroseuranta
                             SET myyntirivitunnus = '{$lisatty_tun}'
                             WHERE yhtio = '{$kukarow['yhtio']}'
                             AND sarjanumero = '{$rulla['sarjanumero']}'";
            pupe_query($update_query);

          }
        }
      }
    }
  }

  $query = "UPDATE kuka
            SET kesken = 0
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND kuka = '{$kukarow['kuka']}'";
  pupe_query($query);

}

function kasittele_iftsta($edi_data) {
  global $kukarow, $yhtiorow;

  $edi_data = str_replace("\n", "", $edi_data);
  $liitedata = $edi_data;
  $edi_data = str_replace("?'", "#%#", $edi_data);
  $edi_data = explode("'", $edi_data);

  $rivimaara = count($edi_data);

  foreach ($edi_data as $key => $rivi) {

    trim($rivi);

    $rivi = str_replace("#%#", "'", $rivi);

    // katsotaan onko viesti alkuperäinen vai korvaava vai peruutus (9 vai 5 vai 1)
    // tulee ehkä olemaan oleellinen tieto
    if (substr($rivi, 0, 3) == 'BGM') {
      $osat = explode("+", $rivi);
      $matkakoodi = $osat[2];
      $tyyppi = $osat[3];
    }

    if (substr($rivi, 0, 6) == 'EQD+CN') {
      $osat = explode("+", $rivi);
      $konttinumero = $osat[2];
    }

    if (substr($rivi, 0, 7) == 'RFF+ZMR' and !isset($konttiviite)) {
      $osat = explode("+", $rivi);
      $mrn_info = $osat[1];
      $mrn_info_osat = explode(":", $mrn_info);
      $mrn = $mrn_info_osat[1];
    }
  }

  $query = "SELECT group_concat(otunnus)
            FROM laskun_lisatiedot
            WHERE matkakoodi = '{$matkakoodi}'";
  $result = pupe_query($query);
  $laskutunnukset = mysql_result($result, 0);

  $query = "SELECT group_concat(trlt.tunnus)
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot AS trlt
              ON trlt.yhtio = tilausrivi.yhtio
              AND trlt.tilausrivitunnus = tilausrivi.tunnus
            WHERE tilausrivi.otunnus IN ({$laskutunnukset})
            AND trlt.konttinumero = '{$konttinumero}'";
  $result = pupe_query($query);
  $tunnukset = mysql_result($result, 0);

  $update_query = "UPDATE tilausrivin_lisatiedot SET
                   kontin_mrn  = '{$mrn}'
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND tunnus IN ({$tunnukset})";
  pupe_query($update_query);

}

function kontitustiedot($konttiviite, $konttinumero = false) {
  global $kukarow;

  if ($konttinumero) {
    $rajaus = "AND trlt.konttinumero = '{$konttinumero}'";
  }
  else{
    $rajaus = '';
  }

  $query = "SELECT tilausrivi.tunnus,
            ss.massa,
            trlt.konttinumero,
            trlt.juoksu,
            trlt.sinettinumero,
            trlt.kontin_taarapaino,
            trlt.kontin_isokoodi,
            trlt.kontin_mrn,
            ss.sarjanumero,
            lasku.asiakkaan_tilausnumero,
            laskun_lisatiedot.konttimaara
            FROM laskun_lisatiedot
            JOIN lasku
              ON lasku.yhtio = laskun_lisatiedot.yhtio
              AND lasku.tunnus = laskun_lisatiedot.otunnus
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            JOIN tilausrivin_lisatiedot AS trlt
              ON trlt.yhtio = tilausrivi.yhtio
              AND trlt.tilausrivitunnus = tilausrivi.tunnus
            JOIN sarjanumeroseuranta AS ss
              ON ss.yhtio = tilausrivi.yhtio
              AND ss.myyntirivitunnus = tilausrivi.tunnus
            WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
            AND (ss.lisatieto IS NULL OR ss.lisatieto = 'Lusattu' OR ss.lisatieto = 'Toimitettu' OR ss.lisatieto = 'Siirretty')
            {$rajaus}
            AND laskun_lisatiedot.konttiviite = '{$konttiviite}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return false;
  }

  $kontit = array();

  while ($rulla = mysql_fetch_assoc($result)) {
    $kontit[$rulla['konttinumero']][] = $rulla;
  }

  foreach ($kontit as $konttinumero => $kontti) {

    $konttipaino = 0;
    $konttilista = '';
    $pakkalista = array();

    foreach ($kontti as $rulla) {
      $konttipaino = $konttipaino + $rulla['massa'];
      $konttilista .= $rulla['tunnus'] . ',';
      $sinettinumero = $rulla['sinettinumero'];
      $konttinumero = $rulla['konttinumero'];
      $isokoodi = $rulla['kontin_isokoodi'];
      $taara = $rulla['kontin_taarapaino'];
      $mrn = $rulla['kontin_mrn'];

      $pakkalista[] = "UIB: " . $rulla['sarjanumero'] . " - " . (INT) $rulla['massa'] . " kg - " .$rulla['asiakkaan_tilausnumero'] . " - Roll Number: {$rulla['juoksu']}\n";

      //$pakkalista .= "UIB: " . $rulla['sarjanumero'] . " - " . (INT) $rulla['massa'] . " kg - " .$rulla['asiakkaan_tilausnumero'] . "\n";
    }

    $pakkalista = serialize($pakkalista);
    $pakkalista = base64_encode($pakkalista);

    $konttilista = rtrim($konttilista, ',');

    $kontit[$konttinumero]['lista'] = $konttilista;
    $kontit[$konttinumero]['paino'] = $konttipaino;
    $kontit[$konttinumero]['mrn'] = $mrn;
    $kontit[$konttinumero]['kpl'] = count($kontti);
    $kontit[$konttinumero]['sinettinumero'] = $sinettinumero;
    $kontit[$konttinumero]['konttinumero'] = $konttinumero;
    $kontit[$konttinumero]['taara'] = $taara;
    $kontit[$konttinumero]['isokoodi'] = $isokoodi;
    $kontit[$konttinumero]['pakkalista'] = $pakkalista;
  }

  return $kontit;
}

function hae_hylly($tuotepaikka, $tulli = false) {

  $paikkamerkit = str_split($tuotepaikka);
  $x = 0;
  $valitut = array();

  if (!ctype_alpha($paikkamerkit[0])) {
    return false;
  }
  else {
    $eka = array_shift($paikkamerkit);
    $valitut[$x] = $eka;

    foreach ($paikkamerkit as $merkki) {

      if (ctype_alpha($merkki)) {
        if (ctype_alpha($valitut[$x])) {
          $valitut[$x] .= $merkki;
        }
        else {
          $x++;
          $valitut[$x] = $merkki;
        }
      }
      elseif (ctype_digit($merkki)) {
        if (ctype_digit($valitut[$x])) {
          $valitut[$x] .= $merkki;
        }
        else {
          $x++;
          $valitut[$x] = $merkki;
        }
      }
      else {
        $x++;
      }
    }

    $valitut = array_values($valitut);

    if (!ctype_digit($valitut[1])) {
      return false;
    }

    $hylly = array(
      "hyllyalue" => strtoupper($valitut[0]),
      "hyllynro"   => $valitut[1],
      "hyllyvali" => '0',
      "hyllytaso" => '0'
    );

    if (!$tulli) {
      if (!in_array($hylly['hyllyalue'], array("A","B"))) {
        return false;
      }
    }
    else {

      // TODO: käyvät rajat tullipuolelle
    }

    if ($hylly['hyllynro'] < 1 or $hylly['hyllynro'] > 21) {
      return false;
    }

    return $hylly;
  }
}

function paivita_tilausrivit_ja_sarjanumeroseuranta($ostorivitunnus, $hylly, $vaihto = false) {
  global $kukarow;

  $query = "SELECT myyntirivitunnus
            FROM sarjanumeroseuranta
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ostorivitunnus = '{$ostorivitunnus}'";
  $result = pupe_query($query);
  $myyntirivitunnus = mysql_result($result,0);

  $query = "SELECT varasto
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$ostorivitunnus}'";
  $result = pupe_query($query);
  $varasto = mysql_result($result,0);

  $hyllyalue = strtoupper($hylly['hyllyalue']);
  $hyllynro  = strtoupper($hylly['hyllynro']);
  $hyllyvali = strtoupper($hylly['hyllyvali']);
  $hyllytaso = strtoupper($hylly['hyllytaso']);

  $query = "UPDATE tilausrivi SET
            hyllyalue = '{$hyllyalue}',
            hyllynro = '{$hyllynro}',
            hyllyvali = '{$hyllyvali}',
            hyllytaso = '{$hyllytaso}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$ostorivitunnus}'";
  $result = pupe_query($query);

  $query = "UPDATE tilausrivi SET
            var = '',
            varattu = 1,
            hyllyalue = '{$hyllyalue}',
            hyllynro = '{$hyllynro}',
            hyllyvali = '{$hyllyvali}',
            hyllytaso = '{$hyllytaso}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$myyntirivitunnus}'";
  $result = pupe_query($query);

  $query = "UPDATE sarjanumeroseuranta SET
            hyllyalue = '{$hyllyalue}',
            hyllynro = '{$hyllynro}',
            hyllyvali = '{$hyllyvali}',
            hyllytaso = '{$hyllytaso}',
            varasto = '{$varasto}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ostorivitunnus = '{$ostorivitunnus}'";
  $result = pupe_query($query);

  if ($vaihto == true) {
    $query = "UPDATE tilausrivi SET
              uusiotunnus = 0
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$ostorivitunnus}'";
    $result = pupe_query($query);
  }

}
function hae_rullat($rahtikirja_id) {
  global $kukarow;

  $query = "SELECT
            ss.sarjanumero,
            tr.uusiotunnus,
            tr.tunnus,
            tr.otunnus,
            ss.hyllyalue AS alue,
            ss.hyllynro AS nro,
            ss.hyllyvali AS vali,
            ss.hyllytaso AS taso,
            ss.massa AS paino,
            trlt.asiakkaan_tilausnumero AS tno
            FROM tilausrivi AS tr
            JOIN tilausrivin_lisatiedot AS trlt
              ON trlt.yhtio = tr.yhtio
              AND trlt.tilausrivitunnus = tr.tunnus
            JOIN sarjanumeroseuranta AS ss
              ON ss.yhtio = tr.yhtio
              AND ss.ostorivitunnus = tr.tunnus
            WHERE tr.yhtio = '{$kukarow['yhtio']}'
            AND tr.tyyppi = 'O'
            AND trlt.rahtikirja_id = '{$rahtikirja_id}'";
  $result = pupe_query($query);

  $viematta = 0;

  while ($rulla = mysql_fetch_assoc($result)) {
    $rullat[] = $rulla;
    if ($rulla['uusiotunnus'] == 0) {
      $viematta++;
    }
    $laskutunnus = $rulla['otunnus'];
  }

  if ($viematta == 0) {
    $query = "UPDATE lasku
              SET alatila = 'X'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$laskutunnus}'";
    pupe_query($query);
  }

return $rullat;
}

function tarkista_sarjanumero($sarjanumero) {
  global $kukarow;

  $query = "SELECT sarjanumeroseuranta.*,
            sarjanumeroseuranta.massa as paino,
            tilausrivi.toimitettuaika
            FROM sarjanumeroseuranta
            JOIN tilausrivi
             ON tilausrivi.yhtio = sarjanumeroseuranta.yhtio
             AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
            WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
            AND sarjanumeroseuranta.sarjanumero = '{$sarjanumero}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return false;
  }
  else {
    return $result;
  }
}

function muuta_bookkaus($muutosparametrit) {
  global $kukarow;

  extract($muutosparametrit);

  $matkatiedot = serialize($matkatiedot);
  $matkatiedot = mysql_real_escape_string($matkatiedot);

  preg_match('~RFF\+CU:(.*?)\'~', $liitedata, $_tilausnumero);

  if ($_tilausnumero[1] == $tilausnro.":".$rivinro) {
    preg_match('~GID\+\+(.*?):RL~', $liitedata, $maara);
    $alkuperainen_maara = $maara[1];
  }

  if (isset($alkuperainen_maara)) {

    $query = "SELECT rullamaara
              FROM laskun_lisatiedot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$laskutunnus}'";
    $result = pupe_query($query);
    $yhteensa = mysql_result($result, 0);

    $uusi_maara = $yhteensa - $alkuperainen_maara + $rullamaara;

    $update_query = "UPDATE laskun_lisatiedot SET
                     konttiviite  = '{$konttiviite}',
                     konttimaara  = '{$konttimaara}',
                     konttityyppi = '{$konttityyppi}',
                     matkakoodi   = '{$matkakoodi}',
                     rullamaara   = '{$uusi_maara}',
                     matkatiedot  = '{$matkatiedot}'
                     WHERE yhtio  = '{$kukarow['yhtio']}'
                     AND otunnus  = '{$laskutunnus}'";
    pupe_query($update_query);
  }

  $update_query = "UPDATE lasku SET
                   sisviesti1 = '{$ohje}',
                   toimaika = '{$lahtopvm}'
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND tunnus = '{$laskutunnus}'";
  pupe_query($update_query);

  $update_query = "UPDATE liitetiedostot SET
            data         = '{$uusi_liitedata}',
            muutospvm    = NOW(),
            muuttaja     = '{$kukarow['kuka']}',
            filename     = '{$sanoma_id}',
            filesize     = '$filesize'
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND  tunnus  = '{$liitetunnus}'";
  pupe_query($update_query);
}


function peruuta_bookkaus($peruutusparametrit) {
  global $kukarow;

  extract($peruutusparametrit);

  $kombo = $tilausnro.":".$rivinro;

  $query = "SELECT
            count(tr.tunnus) AS rullia,
            lasku.asiakkaan_tilausnumero,
            lasku.tunnus
            FROM lasku
            LEFT JOIN tilausrivi AS tr
              ON tr.yhtio = lasku.yhtio
              AND tr.otunnus = lasku.tunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND asiakkaan_tilausnumero = '{$tilausnro}'
            AND tilaustyyppi = 'N'
            GROUP BY lasku.tunnus";
  $result = pupe_query($query);
  $laskuinfo = mysql_fetch_assoc($result);

  if (mysql_num_rows($result) == 1 and $laskuinfo['rullia'] == 0) {

    // Vain yksi lasku, ei rullia, tutkitaan lisää...
    $query = "SELECT
              group_concat(DISTINCT liitostunnus) AS laskutunnukset,
              SUM(IF(selite = '{$kombo}', 1, 0 )) AS omarivi,
              SUM(IF(selite LIKE '{$tilausnro}:%', 1, 0 )) AS rivit
              FROM liitetiedostot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND kayttotarkoitus = 'bookkaussanoma'
              AND selite LIKE '{$tilausnro}:%'";
    $result = pupe_query($query);

    $liiteinfo = mysql_fetch_assoc($result);

    // yksi tilaus ja yksi rivi: poistetaan...
    if ($liiteinfo['omarivi'] == 1 and $liiteinfo['rivit'] == 1) {

      $poisto = "DELETE FROM lasku
                 WHERE yhtio = '{$kukarow['yhtio']}'
                 AND tunnus = '{$liiteinfo['laskutunnukset']}'";
      pupe_query($poisto);

      $poisto = "DELETE FROM laskun_lisatiedot
                 WHERE yhtio = '{$kukarow['yhtio']}'
                 AND otunnus = '{$liiteinfo['laskutunnukset']}'";
      pupe_query($poisto);

      $query = "DELETE FROM liitetiedostot
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND selite = '{$kombo}'";
      pupe_query($query);
    }
    elseif ($liiteinfo['omarivi'] == 1 and $liiteinfo['rivit'] > 1) {

      // myös muita rivejä, vähennetään tämän rivin rullat kokonaismäärästä
      $query = "SELECT rullamaara
                FROM laskun_lisatiedot
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$liiteinfo['laskutunnukset']}'";
      $result = pupe_query($query);
      $rullainfo = mysql_fetch_assoc($result);

      if ($rullainfo['rullamaara'] > $rullamaara) {

        $uusi_rullamaara = $rullainfo['rullamaara'] - $rullamaara;

        $query = "UPDATE laskun_lisatiedot
                  SET rullamaara = '{$uusi_rullamaara}'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND otunnus = '{$liiteinfo['laskutunnukset']}'";
        pupe_query($query);
      }
    }
    else {

      // jokin on vialla, ei tehdä mitään
      return false;
    }
  }
  elseif (mysql_num_rows($result) == 1 and $laskuinfo['rullia'] > 0) {

    // yksi lasku, rullia, muutetaan tilaus bookkauksettomaksi
    $muutos = "UPDATE lasku
                SET sisviesti1 = ''
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$laskuinfo['tunnus']}'";
    pupe_query($muutos);

    $muutos = "UPDATE laskun_lisatiedot SET
                konttiviite = 'bookkaukseton',
                rullamaara = 0,
                konttimaara = 0,
                konttityyppi = '',
                matkatiedot = '',
                matkakoodi = 'bookkaukseton'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$laskuinfo['tunnus']}'";
    pupe_query($muutos);

    $query = "DELETE FROM liitetiedostot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND selite = '{$kombo}'";
    pupe_query($query);

  }
  else {
    return false;
  }
}

function hae_saapumistiedot($saapumiskoodi) {
  global $kukarow;

  $query = "SELECT tilausrivi.*, lasku.liitostunnus, lasku.varasto
            FROM lasku
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND asiakkaan_tilausnumero = '{$saapumiskoodi}'
            ORDER BY uusiotunnus";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return false;
  }
  else {

    $kaikki_viety = true;
    $rivit = array();
    while ($rivi = mysql_fetch_assoc($result)) {

      if ($rivi['uusiotunnus'] == 0) {
        $kaikki_viety = false;
      }

      $rivit[] = $rivi;
    }

    $saapumistiedot = array(
      'rivit' => $rivit,
      'kaikki_viety' => $kaikki_viety
    );

    return $saapumistiedot;
  }
}

function pakkalista_pdf($pdf_data) {

  extract($pdf_data);

  //PDF:n luonti ja defaultit
  require_once("pdflib/phppdflib.class.php");

  //PDF parametrit
  $pdf = new pdffile;
  $pdf->set_default('margin-top',   0);
  $pdf->set_default('margin-bottom',   0);
  $pdf->set_default('margin-left',   0);
  $pdf->set_default('margin-right',   0);

  //fontit
  $pieni["height"] = 11;
  $pieni["font"] = "Times-Roman";

  $bold["height"] = 11;
  $bold["font"] = "Times-Bold";

  // sitten aletaan piirtämään itse PDF sisältöä
  $sivu = $pdf->new_page("a4");

  $sivunro = 1;
  // lasketaan sivumäärä
  $riveja = count($pakkalista) + 4;
  $sivuja = ceil($riveja / 49);

  $image = $pdf->png_embed($logodata);
  $logoparam['scale'] = $pdf_data['scale'];
  $pdf->image_place($image, 780, 420, $sivu, $logoparam);

  $x = 40;
  $y = 800;

  $xx = array(20,580);

  $pdf->draw_text($x, $y, "PACKING LIST", $sivu, $bold);
  $y -= 20;
  $pdf->draw_text($x, $y, 'Container Number', $sivu, $bold);
  $pdf->draw_text($x+100, $y, $konttinumero, $sivu, $bold);
  $y -= 15;
  $pdf->draw_text($x, $y, 'Seal Number', $sivu, $bold);
  $pdf->draw_text($x+100, $y, $sinettinumero, $sivu, $bold);
  $y -= 15;
  $pdf->draw_text($x, $y, 'Weight', $sivu, $bold);
  $pdf->draw_text($x+100, $y, $paino, $sivu, $bold);
  $y -= 15;
  $pdf->draw_text($x, $y, 'Tare Weight', $sivu, $bold);
  $pdf->draw_text($x+100, $y, $taara, $sivu, $bold);
  $y -= 15;
  $pdf->draw_text($x, $y, 'Pcs.', $sivu, $bold);
  $pdf->draw_text($x+100, $y, $kpl, $sivu, $bold);
  $pdf->draw_text($x+500, $y, "Page {$sivunro}/{$sivuja}", $sivu, $bold);

  $y -= 15;
  $yy[0] = $yy[1] = $y;
  $pdf->draw_line($xx, $yy, $sivu);
  $y -= 20;

  foreach ($pakkalista as $rulla) {

    if ($y < 20) {
      $sivu = $pdf->new_page("a4");
      $y = 800;

      $sivunro++;

      $pdf->draw_text($x, $y, "PACKING LIST", $sivu, $bold);
      $y -= 20;
      $pdf->draw_text($x, $y, 'Container Number', $sivu, $bold);
      $pdf->draw_text($x+100, $y, $konttinumero, $sivu, $bold);
      $pdf->draw_text($x+500, $y, "Page {$sivunro}/{$sivuja}", $sivu, $bold);
      $y -= 15;
      $yy[0] = $yy[1] = $y;
      $pdf->draw_line($xx, $yy, $sivu);
      $y -= 20;

    }

    $pdf->draw_text($x, $y, $rulla, $sivu, $bold);
    $y -= 15;

  }

  //keksitään uudelle failille joku varmasti uniikki nimi:
  $pdffilenimi = "/tmp/kuitti-".md5(uniqid(rand(),true)).".pdf";

  //kirjoitetaan pdf faili levylle..
  $fh = fopen($pdffilenimi, "w");
  if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
  fclose($fh);

  return $pdffilenimi;

}

function konttierittely_pdf($pdf_data) {

  extract($pdf_data);

  //PDF:n luonti ja defaultit
  require_once("pdflib/phppdflib.class.php");

  //PDF parametrit
  $pdf = new pdffile;
  $pdf->set_default('margin-top',   0);
  $pdf->set_default('margin-bottom',   0);
  $pdf->set_default('margin-left',   0);
  $pdf->set_default('margin-right',   0);

  //fontit
  $pieni["height"] = 11;
  $pieni["font"] = "Times-Roman";

  $bold["height"] = 11;
  $bold["font"] = "Times-Bold";

  // sitten aletaan piirtämään itse PDF sisältöä
  $sivu = $pdf->new_page("a4");

  $sivunro = 1;
  // lasketaan sivumäärä
  $riveja = count($pakkalista) + 4;
  $sivuja = ceil($riveja / 49);

  $image = $pdf->png_embed($logodata);
  $logoparam['scale'] = $pdf_data['scale'];
  $pdf->image_place($image, 780, 420, $sivu, $logoparam);

  $x = 40;
  $y = 800;

  $xx = array(20,580);

  $pdf->draw_text($x, $y, "CONTAINER SPECIFICATION", $sivu, $bold);
  $y -= 20;
  $pdf->draw_text($x, $y, 'Container Ref.:', $sivu, $bold);
  $pdf->draw_text($x+100, $y, $konttiviite, $sivu, $bold);
  $y -= 15;
  $pdf->draw_text($x, $y, 'Total Weight:', $sivu, $bold);
  $pdf->draw_text($x+100, $y, $total_paino." kg", $sivu, $bold);
  $y -= 15;
  $pdf->draw_text($x, $y, 'Containers:', $sivu, $bold);
  $pdf->draw_text($x+100, $y, $konttimaara. " pcs.", $sivu, $bold);
  $y -= 15;
  $pdf->draw_text($x, $y, 'Rolls:', $sivu, $bold);
  $pdf->draw_text($x+100, $y, $total_rullat." pcs", $sivu, $bold);
  $y -= 15;

  foreach ($kontit as $konttiviite => $tilaukset) {

    $tarvittava_tila = 85;
    $tilauksia = count($tilaukset);
    $tarvittava_tila = 85 + ($tilauksia * 15);

    if ($y < $tarvittava_tila) {
      $sivu = $pdf->new_page("a4");
      $y = 800;
    }

    $yy[0] = $yy[1] = $y;
    $pdf->draw_line($xx, $yy, $sivu);
    $y -= 20;

    $pdf->draw_text($x, $y, 'Container:', $sivu, $bold);
    $pdf->draw_text($x+60, $y, $konttiviite, $sivu, $bold);
    $pdf->draw_text($x+160, $y, $tilaukset['paino']. ' kg / '.$tilaukset['rullia']. ' rolls', $sivu, $bold);
    $y -= 15;

    $pdf->draw_text($x, $y, 'MRN:', $sivu, $bold);
    $pdf->draw_text($x+60, $y, $tilaukset['mrn'], $sivu, $bold);
    $y -= 15;

    $pdf->draw_text($x, $y, 'Seal:', $sivu, $bold);
    $pdf->draw_text($x+60, $y, $tilaukset['sinetti'], $sivu, $bold);
    $y -= 20;

    unset($tilaukset['rullia']);
    unset($tilaukset['paino']);
    unset($tilaukset['mrn']);
    unset($tilaukset['sinetti']);

    $pdf->draw_text($x, $y, 'Orders:', $sivu, $bold);
    $y -= 15;

    foreach ($tilaukset as $tilausnumero => $rivit) {

      unset($rivit['rullia']);
      unset($rivit['paino']);

      foreach ($rivit as $rivinumero => $rivi) {
        $pdf->draw_text($x, $y, $tilausnumero.":".$rivinumero, $sivu, $bold);
        $pdf->draw_text($x+100, $y, $rivi['paino']. ' kg / '.$rivi['rullia']. ' rolls', $sivu, $bold);
        $y -= 15;
      }
    }
  }

  //keksitään uudelle failille joku varmasti uniikki nimi:
  $pdffilenimi = "/tmp/kuitti-".md5(uniqid(rand(),true)).".pdf";

  //kirjoitetaan pdf faili levylle..
  $fh = fopen($pdffilenimi, "w");
  if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
  fclose($fh);

  return $pdffilenimi;
}

function lahtoilmoitus_pdf($pdf_data) {

  extract($pdf_data);

  //PDF:n luonti ja defaultit
  require_once("pdflib/phppdflib.class.php");

  //PDF parametrit
  $pdf = new pdffile;
  $pdf->set_default('margin-top',   0);
  $pdf->set_default('margin-bottom',   0);
  $pdf->set_default('margin-left',   0);
  $pdf->set_default('margin-right',   0);

  //fontit
  $pieni["height"] = 11;
  $pieni["font"] = "Times-Roman";

  $bold["height"] = 11;
  $bold["font"] = "Times-Bold";

  // sitten aletaan piirtämään itse PDF sisältöä
  $sivu = $pdf->new_page("a4");

  $image = $pdf->png_embed($logodata);
  $logoparam['scale'] = $pdf_data['scale'];
  $pdf->image_place($image, 780, 420, $sivu, $logoparam);

  $x = 40;
  $y = 800;

  $xx = array(20,580);

  $pdf->draw_text($x, $y, 'PORTCONF', $sivu, $bold);

  $y -= 25;
  $yy[0] = $yy[1] = $y;
  $pdf->draw_line($xx, $yy, $sivu);
  $y -= 20;

  $pdf->draw_text($x, $y, 'Voyage id:', $sivu, $bold);
  $pdf->draw_text($x+60, $y, $matkakoodi, $sivu, $bold);

  if ($lahtopaiva != 'X') {
    $pdf->draw_text($x+200, $y, 'Date of departure:', $sivu, $bold);
    $pdf->draw_text($x+300, $y, $lahtopaiva, $sivu, $bold);
  }

  $y -= 15;
  $yy[0] = $yy[1] = $y;
  $pdf->draw_line($xx, $yy, $sivu);
  $y -= 20;

  $pdf->draw_text($x, $y, 'Order number:', $sivu, $bold);
  $pdf->draw_text($x+100, $y, 'Feeder vessel', $sivu, $bold);
  $pdf->draw_text($x+260, $y, 'RLS', $sivu, $bold);
  $pdf->draw_text($x+350, $y, 'TON', $sivu, $bold);
  $pdf->draw_text($x+440, $y, 'Containers', $sivu, $bold);

  $y -= 10;
  $yy[0] = $yy[1] = $y;
  $pdf->draw_line($xx, $yy, $sivu);
  $y -= 20;

  foreach ($tilausrivit as $tilausrivi) {

    if ($y < 20) {
      $sivu = $pdf->new_page("a4");
      $y = 800;
    }

    $pdf->draw_text($x, $y, $tilausrivi['tilaus_ja_rivi'], $sivu, $bold);
    $pdf->draw_text($x+100, $y, $tilausrivi['laiva'], $sivu, $bold);
    $pdf->draw_text($x+260, $y, $tilausrivi['rullia'], $sivu, $bold);
    $pdf->draw_text($x+350, $y, $tilausrivi['paino'] / 1000, $sivu, $bold);

    $y -= 15;

  }

  $y += 15;
  $pdf->draw_text($x+440, $y, "{$konttimaara} x {$konttityyppi}", $sivu, $bold);
  $y -= 30;

  //keksitään uudelle failille joku varmasti uniikki nimi:
  $pdffilenimi = "/tmp/kuitti-".md5(uniqid(rand(),true)).".pdf";

  //kirjoitetaan pdf faili levylle..
  $fh = fopen($pdffilenimi, "w");
  if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
  fclose($fh);

  return $pdffilenimi;
}

function varastoraportti_pdf($pdf_data) {

  extract($pdf_data);

  //PDF:n luonti ja defaultit
  require_once("pdflib/phppdflib.class.php");

  //PDF parametrit
  $pdf = new pdffile;
  $pdf->set_default('margin-top',   0);
  $pdf->set_default('margin-bottom',   0);
  $pdf->set_default('margin-left',   0);
  $pdf->set_default('margin-right',   0);

  //fontit
  $pieni["height"] = 10;
  $pieni["font"] = "Times-Roman";

  $bold["height"] = 11;
  $bold["font"] = "Times-Bold";

  $iso["height"] = 20;
  $iso["font"] = "Times-Bold";

  // sitten aletaan piirtämään itse PDF sisältöä
  $sivu = $pdf->new_page("a4");

  $image = $pdf->png_embed($logodata);
  $logoparam['scale'] = $pdf_data['scale'];
  $pdf->image_place($image, 780, 420, $sivu, $logoparam);

  $x = 30;
  $y = 800;

  $xx = array(20,580);

  $pdf->draw_text($x, $y, 'VARASTORAPORTTI', $sivu, $iso);
  $y -= 15;
  $pdf->draw_text($x, $y, date("j.n.Y H:i", strtotime($hetki)), $sivu, $bold);
  $y -= 15;
  $pdf->draw_text($x, $y, "Rullien kokonaismäärä: " . $totalmaara . " kpl.", $sivu, $bold);
  $y -= 15;
  $pdf->draw_text($x, $y, "Rullien kokonaispaino: " . $totalpaino . " kg.", $sivu, $bold);

  $y -= 50;

  $pdf->draw_text($x, $y, 'Paikka', $sivu, $bold);
  $pdf->draw_text($x+120, $y, 'Tilaus:rivi', $sivu, $bold);
  $pdf->draw_text($x+270, $y, 'Paino', $sivu, $bold);
  $pdf->draw_text($x+400, $y, 'Kpl.', $sivu, $bold);

  $y -= 5;
  $yy[0] = $yy[1] = $y;
  $pdf->draw_line($xx, $yy, $sivu);
  $y -= 20;

  foreach ($paikat as $paikka => $tilaukset) {

    $riveja = count($tilaukset);
    $tarvittava_tila = $riveja * 20 + 20;

    if ($y < $tarvittava_tila) {
      $sivu = $pdf->new_page("a4");
      $y = 800;
    }

    $pdf->draw_text($x, $y-5, $paikka, $sivu, $iso);

    $tilauspaino = 0;
    $tilausrullia = 0;
    foreach ($tilaukset as $tilaus => $rullat) {

      if ($y < 20) {
        $sivu = $pdf->new_page("a4");
        $y = 800;
      }

        $pdf->draw_text($x+120, $y, $tilaus, $sivu, $pieni);

        $paino = 0;
        $rullia = 0;
        foreach ($rullat as $rulla) {
          $paino += $rulla['massa'];
          $rullia++;
          $tilauspaino += $rulla['massa'];
          $tilausrullia++;
      }

      $pdf->draw_text($x+270, $y, $paino, $sivu, $pieni);
      $pdf->draw_text($x+400, $y, $rullia, $sivu, $pieni);

      $y -= 20;
    }
    $pdf->draw_text($x+120, $y, "Yhteensä", $sivu, $bold);
    $pdf->draw_text($x+270, $y, $tilauspaino, $sivu, $bold);
    $pdf->draw_text($x+400, $y, $tilausrullia, $sivu, $bold);

    $y -= 5;
    $yy[0] = $yy[1] = $y;
    $pdf->draw_line($xx, $yy, $sivu);
    $y -= 20;
  }

  //keksitään uudelle failille joku varmasti uniikki nimi:
  $pdffilenimi = "/tmp/kuitti-".md5(uniqid(rand(),true)).".pdf";

  //kirjoitetaan pdf faili levylle..
  $fh = fopen($pdffilenimi, "w");
  if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
  fclose($fh);

  return $pdffilenimi;
}

function laskutusraportti_pdf($pdf_data) {

  extract($pdf_data);

  //PDF:n luonti ja defaultit
  require_once("pdflib/phppdflib.class.php");

  //PDF parametrit
  $pdf = new pdffile;
  $pdf->set_default('margin-top',   0);
  $pdf->set_default('margin-bottom',   0);
  $pdf->set_default('margin-left',   0);
  $pdf->set_default('margin-right',   0);

  //fontit
  $pieni["height"] = 10;
  $pieni["font"] = "Times-Roman";

  $bold["height"] = 11;
  $bold["font"] = "Times-Bold";

  $iso["height"] = 20;
  $iso["font"] = "Times-Bold";

  // sitten aletaan piirtämään itse PDF sisältöä
  $sivu = $pdf->new_page("a4");

  $image = $pdf->png_embed($logodata);
  $logoparam['scale'] = $pdf_data['scale'];
  $pdf->image_place($image, 780, 420, $sivu, $logoparam);

  $x = 30;
  $y = 800;

  $xx = array(20,580);

  $pdf->draw_text($x, $y, "LASKUTUSRAPORTTI", $sivu, $iso);
  $pvm = date("j.n.Y H:i");
  $y -= 20;
  $pdf->draw_text($x, $y, $pvm, $sivu, $bold);

  $y -= 15;
  $pdf->draw_text($x, $y, "Konttiviite:", $sivu, $bold);
  $pdf->draw_text($x+90, $y, "{$konttiviite}", $sivu, $bold);

  $y -= 15;
  $pdf->draw_text($x, $y, "Lastauspäivä:", $sivu, $bold);
  $pdf->draw_text($x+90, $y, date("j.n.Y", strtotime($sinetointi)) , $sivu, $bold);

  $y -= 15;

  if (count($tilaukset) > 1) {

    $pdf->draw_text($x, $y, "Tilausnumerot:", $sivu, $bold);
    $y -= 15;

    foreach ($tilaukset as $tilaus) {

      $pdf->draw_text($x, $y, "{$tilaus['asiakkaan_tilausnumero']}", $sivu, $bold);
      $y -= 15;
    }
  }
  else {

    $pdf->draw_text($x, $y, "Tilausnumero:", $sivu, $bold);
    $pdf->draw_text($x+90, $y, "{$tilaukset[0]['asiakkaan_tilausnumero']}", $sivu, $bold);
    $y -= 15;
  }

  $y -= 15;

  $pdf->draw_text($x, $y, t("NIMIKE"), $sivu, $bold);
  $pdf->draw_text($x+220, $y, t("MÄÄRÄ"), $sivu, $bold);
  $pdf->draw_text($x+440, $y, t("HINTA"), $sivu, $bold);

  $y -= 10;
  $yy[0] = $yy[1] = $y;
  $pdf->draw_line($xx, $yy, $sivu);
  $y -= 20;

  $total_hinta = 0;

  foreach ($rivit as $rivi) {

    if ($y < 100) {
      $sivu = $pdf->new_page("a4");
      $y = 800;
    }

    $pdf->draw_text($x, $y, $rivi['nimitys'], $sivu, $pieni);

    if ($rivi['tuoteno'] == 'VARASTOINTI') {


      $sisaan = date("j.n.Y", strtotime($rivi['toimaika']));
      $ulos = date("j.n.Y", strtotime($rivi['kerayspvm']));

      $_tonnit = $rivi['hinta'] / $rivi['tilkpl'] / $varastointihinta;

      $txt  = $sisaan . " - " . $ulos . " | ";
      $txt .= $rivi['varastopaivat'] - 7 . " vrk. - {$_tonnit} t.";

      $pdf->draw_text($x+180, $y, $txt, $sivu, $pieni);

      $hinta = $rivi['hinta'];
      $pdf->draw_text($x+440, $y, chr(128).number_format((float)($rivi['hinta']), 3, '.', ''), $sivu, $pieni);


    }
    elseif ($rivi['yksikko'] == 'TON') {

      $pdf->draw_text($x+220, $y, $tonnit . " t.", $sivu, $pieni);

      $hinta = $rivi['hinta'] * $tonnit;
      $pdf->draw_text($x+440, $y, chr(128).$hinta, $sivu, $pieni);

    }
    else {

      $pdf->draw_text($x+220, $y, $rivi['tilkpl'] . " kpl.", $sivu, $pieni);

      $hinta = $rivi['hinta'] * $rivi['tilkpl'];
      $pdf->draw_text($x+440, $y, chr(128).$hinta, $sivu, $pieni);

    }

    $y -= 15;


    $yy[0] = $yy[1] = $y;
    $pdf->draw_line($xx, $yy, $sivu);
    $y -= 20;

    $total_hinta +=  $hinta;
  }

  $pdf->draw_text($x, $y, t("Hinta yhteensä:"), $sivu, $bold);
  $pdf->draw_text($x+80, $y, chr(128).number_format((float)($total_hinta), 3, '.', ''), $sivu, $bold);
  $y -= 15;

  //keksitään uudelle failille joku varmasti uniikki nimi:
  $pdffilenimi = "/tmp/kuitti-".md5(uniqid(rand(),true)).".pdf";

  //kirjoitetaan pdf faili levylle..
  $fh = fopen($pdffilenimi, "w");
  if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
  fclose($fh);

  return $pdffilenimi;
}

function pdf_logo() {
  global $kukarow, $yhtiorow;

  $query = "SELECT data
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus='{$yhtiorow['logo']}'";
  $liiteres = pupe_query($query);
  $liiterow = mysql_fetch_assoc($liiteres);
  $string = $liiterow["data"];

  $image = imagecreatefromstring($string);
  $file = "/tmp/".md5(uniqid(rand(),true)).".png";

  $tt = imagepng($image, $file);

  $file_info = getimagesize($file);

  $scale = 150 / $file_info[0];
  $logodata = file_get_contents($file);

  $return = array('logodata' => $logodata, 'scale' => $scale);

  return $return;
}

function varastotilanne($hetki = false) {
  global $kukarow;

  if (!$hetki) {
    $hetki = date("Y-m-d H:i:s");
  }

  $query = "SELECT
            concat(ss.hyllyalue, ss.hyllynro) AS varastopaikka,
            ss.massa,
            concat(otrlt.asiakkaan_tilausnumero, ':', otrlt.asiakkaan_rivinumero) AS tilaus
            FROM sarjanumeroseuranta AS ss
            JOIN tilausrivi AS otr
              ON otr.yhtio = ss.yhtio
              AND otr.tunnus = ss.ostorivitunnus
            JOIN tilausrivin_lisatiedot AS otrlt
              ON otrlt.yhtio = ss.yhtio
              AND otrlt.tilausrivitunnus = otr.tunnus
            LEFT JOIN tilausrivi AS mtr
              ON mtr.yhtio = ss.yhtio
              AND mtr.tunnus = ss.myyntirivitunnus
            LEFT JOIN tilausrivin_lisatiedot AS mtrlt
              ON mtrlt.yhtio = ss.yhtio
              AND mtrlt.tilausrivitunnus = mtr.tunnus
            WHERE ss.yhtio = '{$kukarow['yhtio']}'
            AND ss.varasto IS NOT NULL
            AND otr.toimitettuaika <= '{$hetki}'
            AND ((mtr.toimitettuaika >= '{$hetki}' OR mtr.toimitettuaika = '0000-00-00 00:00:00') OR mtr.toimitettuaika IS NULL)
            GROUP BY mtrlt.tunnus
            ORDER BY ss.hyllyalue, CAST(ss.hyllynro AS SIGNED INTEGER) ASC";
  $result = pupe_query($query);

  $paikat = array();
  $totalpaino = 0;

  while ($rulla = mysql_fetch_assoc($result)) {
    $paikat[$rulla['varastopaikka']][$rulla['tilaus']][] = $rulla;
    $totalpaino += $rulla['massa'];
  }

  $totalmaara = mysql_num_rows($result);

  $return = array(
    'paikat' => $paikat,
    'totalpaino' => $totalpaino,
    'totalmaara' => $totalmaara,
    'hetki' => $hetki
  );

  return $return;

}

?>
