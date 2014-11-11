<?php

function rullat_ja_kontit($konttiviite, $maxkg) {
  global $kukarow;

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
            la.sisviesti1 as ohje,
            ss.hyllyalue,
            ss.hyllynro,
            concat(ss.hyllyalue, '-', ss.hyllynro) AS paikka,
            concat(la.asiakkaan_tilausnumero, ss.hyllyalue, '-', ss.hyllynro) AS group_class,
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
            AND (ss.lisatieto IS NULL OR ss.lisatieto = 'Lusattu')
            ORDER BY asiakkaan_tilausnumero, paikka";
  $result = pupe_query($query);

  $kontit = array();
  $kontitetut = array();
  $kontittamattomat = array();

  $ryhma_laskuri = array();

  $kontti = 1;
  $kilot = 0;

  while ($rulla = mysql_fetch_assoc($result)) {

    if ($kilot + $rulla['paino'] >= $maxkg) {
      $kontti++;
      $kilot = 0;
    }

    $kontit[$kontti] = 0;
    $kilot = $kilot + $rulla['paino'];

    if ($rulla['konttinumero'] != '') {
      $kontitetut[] = $rulla;
    }
    else{
      $kontittamattomat[] = $rulla;
      $ryhmalaskuri[$rulla['group_class']]++;
    }
  }

  $konttimaara = count($kontit);

  if ($konttimaara < $bookattu_konttimaara) {
    while ($konttimaara < $bookattu_konttimaara) {
      end($kontit);
      $vika_kontti = key($kontit);
      $kontit[$vika_kontti + 1] = 0;
      $konttimaara = count($kontit);
    }
  }

  $rullat_ja_kontit = array(
    'kontittamattomat' => $kontittamattomat,
    'kontitetut' => $kontitetut,
    'kontit' => $kontit,
    'ryhma_laskuri' => $ryhmalaskuri
  );

  return $rullat_ja_kontit;
}

function laheta_sanoma($sanoma){

  if ($sanoma == '') {
    return false;
  }
  else {
    return true;
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

  ftp_chdir($yhteys, 'in-test');
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

function kuittaus_parametrit($hakunumero, $tyyppi) {
  global $kukarow, $yhtiorow;

  if ($tyyppi == 'R') {
    $rahtikirjanumero = $hakunumero;
  }
  else {
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

    if (mysql_num_rows($result) == 0) {
      return false;
    }
    else {
      $rahtikirjanumero = mysql_result($result, 0);
    }
  }

  $query = "SELECT lasku.ovttunnus,
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

function hylky_lusaus_parametrit($sarjanumero) {
  global $kukarow, $yhtiorow;

  $query = "SELECT lasku.ovttunnus,
            lasku.asiakkaan_tilausnumero AS tilauskoodi,
            tilausrivi.kommentti,
            varastopaikat.tunnus AS varastokoodi,
            varastopaikat.nimitys AS varastonimi,
            sarjanumeroseuranta.massa AS paino,
            tilausrivin_lisatiedot.juoksu,
            tilausrivin_lisatiedot.asiakkaan_rivinumero
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
            AND sarjanumeroseuranta.sarjanumero = '{$sarjanumero}'
            AND sarjanumeroseuranta.lisatieto = 'Lusattava'
            AND tilausrivin_lisatiedot.sinettinumero = ''";
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
    'sarjanumero' => $sarjanumero,
    'juoksunumero' => $juoksunumero,
    'varastonimi' => $row['varastonimi'],
    'varastokoodi' => $row['varastokoodi'],
    'tilauskoodi' => $row['tilauskoodi'],
    'tilausrivi' => $row['asiakkaan_rivinumero'],
    'paino' => $paino
  );

  return $parametrit;
}

function kontitus_parametrit($rullat_kontissa) {
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
            laskun_lisatiedot.konttiviite
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
            AND (ss.lisatieto IS NULL OR ss.lisatieto = 'Lusattu')";
  $result = pupe_query($query);

  $rullarivit = array();
  $total_paino = 0;

  while ($row = mysql_fetch_assoc($result)) {

    $index = $row['tilauskoodi'] . ":" .$row['rivinro'];

    $rullarivit[$index][] = $row;

    $rullarivit[$index]['rivi_paino'] = $rullarivit[$index]['rivi_paino'] + $row['paino'];

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
    'tilauskoodi' => $konttiviite,
    'kontitus_info' => $kontitus_info,
    'total_paino' => $total_paino
  );

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

  $kontit = array();

  $query = "SELECT trlt.konttinumero,
            trlt.sinettinumero,
            trlt.kontin_kilot,
            trlt.kontin_taarapaino
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot AS trlt
              ON trlt.yhtio = tilausrivi.yhtio
              AND trlt.tilausrivitunnus = tilausrivi.tunnus
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus IN ({$tunnukset})";
  $result = pupe_query($query);

  $kontit = array();

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
  }

  $sanomaid = "RP" . time();
  $sanomanumero = time();

  $parametrit = array(
    'laji' => 'satamavahvistus',
    'tyyppi' => 'DESADV',
    'lahettajan_ovt' => $yhtiorow['ovttunnus'],
    'vastaanottajan_ovt' => '003708274241',
    'sanomaid' => $sanomaid,
    'sanomanumero' => $sanomanumero,
    'kontit' => $kontit,
    'tilauskoodi' => $konttiviite,
    'matkakoodi' => $matkakoodi,
    'matka_info' => $matka_info
  );

  return $parametrit;
}

function laadi_edifact_sanoma($parametrit) {
  global $kukarow;

  //validoidaan parametrit ja setataan muita muuttujia
  //
  extract($parametrit);

  switch ($laji) {
    case 'kuittaus':
      $lajikoodi = '632';
      $assosiaatiokoodi = 'EXGR13';
      $merkityskoodi = '9';
      break;
    case 'lusaus':
      $lajikoodi = '6';
      $assosiaatiokoodi = 'EXSC13';
      $merkityskoodi = '5';
      break;
    case 'hylky':
      $lajikoodi = '34';
      $assosiaatiokoodi = 'EXSC13';
      $merkityskoodi = '5';
      break;
    case 'kontitus':
      $lajikoodi = '833';
      $assosiaatiokoodi = 'EXTU11';
      $merkityskoodi = '9';
      break;
    case 'satamavahvistus':
        $lajikoodi = '122';
        $assosiaatiokoodi = 'EXPC13';
        $merkityskoodi = '9';
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
    // RFF+VON -segmentti
    $s .= "RFF+VON:" . $matkakoodi . "'";
    $segmentit++;
  }

  if ($laji == 'kontitus') {
    // RFF+VON -segmentti
    $s .= "RFF+VON:" . $kontitus_info['matkakoodi'] . "'";
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

  if ($laji == 'kontitus') {

    foreach ($rullarivit as $key => $rullarivi) {

      $s .= "CPS+MOL++3'";
      $segmentit++;

      $s .= "PAC+" . count($rullarivi) . "++RL'";
      $segmentit++;

      // kokonaispaino
      $s .= "MEA+AAE+G+KGM:" . $rullarivi['rivi_paino'] ."'";
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


      foreach ($rullarivi as $rulla) {
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
  global $kukarow;

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

    $rivi = str_replace("#%#", "'", $rivi);

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

    // katsotaan onko viesti alkuperäinen vai korvaava (9 vai 5)
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

  if (mysql_num_rows($result) == 0) {

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
    $query = "SELECT *
              FROM tilausrivin_lisatiedot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND asiakkaan_tilausnumero = '{$tilausnro}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {

      $laskuquery = "SELECT *
                     FROM lasku
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus = '{$tunnus}'";
      $laskuresult = pupe_query($laskuquery);
      $laskurow = mysql_fetch_assoc($laskuresult, 0);

      $kukarow['kesken'] = $laskurow['tunnus'];

      // haetaan tuotteen tiedot
      $tuotequery = "SELECT *
                     FROM tuote
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tuoteno = '123'";
      $tuoteresult = pupe_query($tuotequery);

      $trow = mysql_fetch_assoc($tuoteres);
      $kpl = 1;
      $var = 'P';

      while ($rulla = mysql_fetch_assoc($result)) {

        require "lisaarivi.inc";

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
                         asiakkaan_tilausnumero = '{$rulla['asiakkaan_tilausnumero']}'
                         asiakkaan_rivinumero = '{$rulla['asiakkaan_rivinumero']}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tilausrivitunnus = '{$lisatty_tun}'";
        pupe_query($update_query);
      }
    }
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
              AND lasku.tila = 'N'";
    $result = pupe_query($query);
    $laskuinfo = mysql_fetch_assoc($result);

    $tunnus = $laskuinfo['tunnus'];

    $rullamaara = $rullamaara + $laskuinfo['rullamaara'];

    $update_query = "UPDATE laskun_lisatiedot SET
                     konttiviite  = '{$konttiviite}',
                     konttimaara  = '{$konttimaara}',
                     konttityyppi = '{$konttityyppi}',
                     matkakoodi   = '{$matkakoodi}',
                     rullamaara   = '{$rullamaara}'
                     WHERE yhtio  = '{$kukarow['yhtio']}'
                     AND otunnus  = '{$tunnus}'";
    pupe_query($update_query);
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
              liitostunnus    = '$tunnus',
              selite          = '{$tilausnro}:{$rivinro}',
              laatija         = '{$kukarow['kuka']}',
              luontiaika      = NOW(),
              data            = '{$liitedata}',
              filename        = '{$sanoma_id}',
              filesize        = '$filesize',
              filetype        = 'text/plain',
              kayttotarkoitus = 'bookkaussanoma'";
    pupe_query($query);

  }
  elseif ($tyyppi == 5){

    $korvattava = mysql_result($vastaavuusresult, 0);

    $query = "UPDATE liitetiedostot SET
              data        = '$liitedata',
              muutospvm   = NOW(),
              muuttaja    = '{$kukarow['kuka']}',
              filename    = '{$sanoma_id}',
              filesize    = '$filesize'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND  tunnus = '{$korvattava}'";
    pupe_query($query);
  }

  $query = "UPDATE kuka
            SET kesken = 0
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND kuka = '{$kukarow['kuka']}'";
  pupe_query($query);

}

function kasittele_rahtikirjasanoma($edi_data) {
  global $kukarow;

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

        if (substr($edi_data[$luetaan], 0, 5) == "LOC+8") {
          $osat = explode("+", $edi_data[$luetaan]);
          $paamaara_info = $osat[2];
          $paamaara_info_osat = explode(":", $paamaara_info);
          $paamaara = $paamaara_info_osat[3];

          // haetaan varaston tiedot
          $query = "SELECT tunnus
                    FROM varastopaikat
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND locate(nimitys, '{$paamaara}') > 0
                    LIMIT 1";
          $varastores = pupe_query($query);
          $varasto_id = mysql_result($varastores,0);
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
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "CPS+PKG" or $luetaan >= $rivimaara) {
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
        'tilausnro' => $_tilausnro,
        'rivinro' => $_rivi,
        'tilauksen_paino' => $_paino
        );

    }
  }// rivit luettu

  $rahti['rullat'] = $rullat;

  $data = $rahti;

  // tarkistetaan onko tämä sanoma jostakin syystä jo käsitelty
  $query = "SELECT tunnus
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND filename = '{$data['sanoma_id']}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 0) {
    return false;
  }

  require_once "../inc/luo_ostotilausotsikko.inc";

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
    'edi' => 'X'
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

  $ostolaskurow = $laskurow;

  foreach ($data['rullat'] as $rulla) {

    $query = "SELECT tunnus
              FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND sarjanumero = '{$rulla['sarjanumero']}'";
    $sarjares = pupe_query($query);

    if (mysql_num_rows($sarjares) == 0) {

      // haetaan tuotteen tiedot
      $query = "SELECT *
                FROM tuote
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$rulla['tuoteno']}'";
      $tuoteres = pupe_query($query);

      $trow = mysql_fetch_assoc($tuoteres);
      $kpl = 1;
      $kerayspvm = $toimaika = $data['toimitusaika'];

      $laskurow = $ostolaskurow;
      $kukarow['kesken'] = $laskurow['tunnus'];
      unset($var);
      require "lisaarivi.inc";

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
      $query = "SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND asiakkaan_tilausnumero = '{$rulla['tilausnro']}'
                AND tilaustyyppi = 'N'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) != 0) {

       $laskurow = mysql_fetch_assoc($result);
       $kukarow['kesken'] = $laskurow['tunnus'];
       $var = 'P';
       require "lisaarivi.inc";

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

  $query = "UPDATE kuka
            SET kesken = 0
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND kuka = '{$kukarow['kuka']}'";
  pupe_query($query);

}

function kasittele_iftsta($edi_data) {
  global $kukarow;

  $edi_data = str_replace("\n", "", $edi_data);
  $liitedata = $edi_data;
  $edi_data = str_replace("?'", "#%#", $edi_data);
  $edi_data = explode("'", $edi_data);

  $rivimaara = count($edi_data);


  foreach ($edi_data as $key => $rivi) {

    trim($rivi);

    $rivi = str_replace("#%#", "'", $rivi);

    // katsotaan onko viesti alkuperäinen vai korvaava (9 vai 5)
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

?>
