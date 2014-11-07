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
            AND ss.lisatieto IS NULL
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
            AND ss.lisatieto IS NULL";
  $result = pupe_query($query);

  $rullarivit = array();
  $total_paino = 0;

  while ($row = mysql_fetch_assoc($result)) {

    $index = $row['tilauskoodi'] . ":" .$row['rivinro'];

    $rullarivit[$index][] = $row;
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
      $s .= "MEA+AAE+G+KGM:" . $total_paino ."'";
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

?>
