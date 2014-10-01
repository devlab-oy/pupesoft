<?php

function kuittaus_parametrit($rahtikirjanumero) {
  global $kukarow, $yhtiorow;

  $query = "SELECT lasku.ovttunnus,
            lasku.asiakkaan_tilausnumero,
            sarjanumeroseuranta.sarjanumero,
            sarjanumeroseuranta.massa AS paino,
            sarjanumeroseuranta.leveys,
            tilausrivi.tuoteno,
            tilausrivi.kommentti
            FROM tilausrivi
            JOIN lasku ON lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus
            JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = lasku.yhtio AND sarjanumeroseuranta.ostorivitunnus = tilausrivi.tunnus
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.kommentti LIKE '{$rahtikirjanumero}#%'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return false;
  }

  while ($row = mysql_fetch_assoc($result)) {
    $pakkaukset[] = $row;
    if (!isset($tilaukset[$row['asiakkaan_tilausnumero']])) {
      $kommentti_info = explode("#", $row['kommentti']);
      $rekno = $kommentti_info[3];
      $tilaukset[$row['asiakkaan_tilausnumero']] = array('paino' => $kommentti_info[2], 'id' => $row['asiakkaan_tilausnumero']);
    }
  }

  foreach ($tilaukset as $key => $tilaus) {
    foreach ($pakkaukset as $pakkaus) {
      if ($pakkaus['asiakkaan_tilausnumero'] == $tilaus['id']) {

        $kommentti_info = explode("#", $pakkaus['kommentti']);
        $pakkaus['juoksu'] = $kommentti_info[1];
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
    'kaupunki' => 'KOTKA');

  $parametrit = array(
    'laji' => 'kuittaus',
    'tyyppi' => 'RECADV',
    'rekisterinumero' => $rekno,
    'lahettajan_ovt' => $yhtiorow['ovttunnus'],
    'vastaanottajan_ovt' => $row['ovttunnus'],
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
            sarjanumeroseuranta.massa AS paino
            FROM sarjanumeroseuranta
            JOIN tilausrivi ON tilausrivi.yhtio = sarjanumeroseuranta.yhtio AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
            JOIN lasku ON lasku.yhtio = lasku.yhtio AND lasku.tunnus = tilausrivi.otunnus
            JOIN varastopaikat ON varastopaikat.yhtio = tilausrivi.yhtio AND varastopaikat.tunnus = tilausrivi.varasto
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND sarjanumeroseuranta.sarjanumero = '{$sarjanumero}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return false;
  }

  $row = mysql_fetch_assoc($result);
  $kommentti_info = explode("#", $row['kommentti']);
  $sanomaid = "RP" . time();
  $sanomanumero = time();
  $juoksunumero = $kommentti_info[1];
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
    'paino' => $paino
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
    default:
      return NULL;
      break;
  }

  // UNA -segmentti - (sama kaikissa sanomissa, ei lasketa segmenttilukemaan)
  $s = "UNA:+.? '";

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
  $s .= date("YdmHi");
  $s .= ":203'";
  $segmentit++;

  if ($laji == 'kuittaus') {
    // DTM -segmentti
    $s .= "DTM+178:";
    $s .= date("YdmHi");
    $s .= ":203'";
    $segmentit++;

    // DTM -segmentti
    $s .= "DTM+163:";
    $s .= date("YdmHi");
    $s .= ":203'";
    $segmentit++;

    // DTM -segmentti
    $s .= "DTM+413:";
    $s .= date("YdmHi");
    $s .= ":203'";
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
    $s .= "RFF+CU:" . $tilauskoodi . ":1'";
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
    $s .= "NAD+LP" . $kontitus_info['lastaaja_ovt'] . "'";
    $segmentit++;
  }

  if ($laji == 'kontitus' and !empty($kontitus_info['numero'])) {
    // EQD+CN -segmentti (kontti-infoa)
    $s .= "EQD+CN" . $kontitus_info['numero'] . "+42GP::86+++5";
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
    $s .= date("YdmHi");
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
    // STS+ACT  -segmenti
    $s .= "STS+ACT:ZSN+2::86+1::86+1::86+7::86'";
    $segmentit++;
  }

  if ($laji == 'hylky') {
    // STS+ACT  -segmenti
    $s .= "STS+RJC:ZSN+7::86+3::86+1::86+7::86'";
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
      $s .= "RFF+CU:" . $tilaus['id'] . ":1'";
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
