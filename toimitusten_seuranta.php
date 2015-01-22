<?php

if (isset($_POST['task']) and (
  $_POST['task'] == 'nayta_konttierittely' or
  $_POST['task'] == 'nayta_laskutusraportti' or
  $_POST['task'] == 'nayta_lahtoilmoitus' or
  $_POST['task'] == 'hae_pakkalista')) {
  $no_head = "yes";
}

require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

if (isset($_POST['task']) and $_POST['task'] == 'nayta_konttierittely') {

  $pdf_data = unserialize(base64_decode($_POST['parametrit']));

  $sessio = $_POST['session'];
  $logo_url = $_POST['logo_url'];
  $logo_info = pdf_logo($logo_url, $sessio);
  $pdf_data['logodata'] = $logo_info['logodata'];
  $pdf_data['scale'] = $logo_info['scale'];

  $pdf_tiedosto = konttierittely_pdf($pdf_data);

  header("Content-type: application/pdf");
  echo file_get_contents($pdf_tiedosto);
  die;
}

if (isset($_POST['task']) and $_POST['task'] == 'nayta_laskutusraportti') {

  $pdf_data = unserialize(base64_decode($_POST['parametrit']));

  $sessio = $_POST['session'];
  $logo_url = $_POST['logo_url'];
  $logo_info = pdf_logo($logo_url, $sessio);
  $pdf_data['logodata'] = $logo_info['logodata'];
  $pdf_data['scale'] = $logo_info['scale'];
  $pdf_data['vapaa_varastointi'] = $_POST['vapaa_varastointi'];

  $pdf_tiedosto = laskutusraportti_pdf($pdf_data);

  header("Content-type: application/pdf");
  echo file_get_contents($pdf_tiedosto);
  die;
}

if (isset($_POST['task']) and $_POST['task'] == 'nayta_lahtoilmoitus') {

  $pdf_data = unserialize(base64_decode($_POST['parametrit']));

  $sessio = $_POST['session'];
  $logo_url = $_POST['logo_url'];
  $logo_info = pdf_logo($logo_url, $sessio);
  $pdf_data['logodata'] = $logo_info['logodata'];
  $pdf_data['scale'] = $logo_info['scale'];

  $pdf_tiedosto = lahtoilmoitus_pdf($pdf_data);

  header("Content-type: application/pdf");
  echo file_get_contents($pdf_tiedosto);
  die;
}

if (isset($_POST['task']) and $_POST['task'] == 'hae_pakkalista') {

  $pakkalista = unserialize(base64_decode($_POST['pakkalista']));

  $pdf_data = array(
    'pakkalista' => $pakkalista,
    'taara' => $_POST['taara'],
    'kpl' => $_POST['kpl'],
    'paino' => $_POST['paino'],
    'konttinumero' => $_POST['konttinumero'],
    'sinettinumero' => $_POST['sinettinumero']
    );

  $sessio = $_POST['session'];
  $logo_url = $_POST['logo_url'];
  $logo_info = pdf_logo($logo_url, $sessio);
  $pdf_data['logodata'] = $logo_info['logodata'];
  $pdf_data['scale'] = $logo_info['scale'];

  $pdf_tiedosto = pakkalista_pdf($pdf_data);

  header("Content-type: application/pdf");
  echo file_get_contents($pdf_tiedosto);
  die;
}

if (!isset($errors)) $errors = array();

if (isset($task) and $task == 'laivamuutos') {

  if (!empty($uusilaiva)) {

    $vanhat_tiedot = unserialize(base64_decode($vanhat_tiedot));
    $uusilaiva = mysql_real_escape_string($uusilaiva);
    $vanhat_tiedot['transport_name'] = $uusilaiva;
    $uudet_tiedot = serialize($vanhat_tiedot);

    $query = "UPDATE laskun_lisatiedot SET
              matkatiedot = '{$uudet_tiedot}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND konttiviite = '{$konttiviite}'";
    pupe_query($query);

  }
  unset($task);
}

if (isset($task) and $task == 'poistalasku') {

  $query = "SELECT group_concat(tunnus) FROM tilausrivi where otunnus = '{$poistettavatunnus}'";
  $result = pupe_query($query);
  $trtunnarit = mysql_result($result, 0);

  $query = "DELETE FROM lasku where tunnus = '{$poistettavatunnus}'";
  pupe_query($query);

  $query = "DELETE FROM tilausrivi where tunnus IN ({$trtunnarit})";
  pupe_query($query);

  $query = "DELETE FROM tilausrivin_lisatiedot where tilausrivitunnus IN ({$trtunnarit})";
  pupe_query($query);

  header("Location: toimitusten_seuranta.php?rajaus=toimitetut");
  exit;

}


if (isset($task) and $task == 'eu_tilaus') {

  $query = "SELECT group_concat(tilausrivin_lisatiedot.tunnus)
            FROM laskun_lisatiedot
            JOIN tilausrivi
              ON tilausrivi.yhtio = laskun_lisatiedot.yhtio
              AND tilausrivi.otunnus = laskun_lisatiedot.otunnus
            JOIN tilausrivin_lisatiedot
              ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            JOIN sarjanumeroseuranta AS ss
              ON ss.yhtio = tilausrivi.yhtio
              AND ss.myyntirivitunnus = tilausrivi.tunnus
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND laskun_lisatiedot.konttiviite = '$konttiviite'";
  $result = pupe_query($query);
  $rivitunnukset = mysql_result($result, 0);

  $query = "UPDATE tilausrivin_lisatiedot
            SET kontin_mrn = 'EU'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus IN ({$rivitunnukset})";
  pupe_query($query);

  unset($task);
}

if (isset($task) and $task == 'suorita_hylky') {

  $parametrit = hylky_lusaus_parametrit($sarjanumero);
  $parametrit['laji'] = 'hylky';

  $sanoma = laadi_edifact_sanoma($parametrit);

  $query = "UPDATE sarjanumeroseuranta SET
            lisatieto = 'Hylätty'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND sarjanumero = '{$sarjanumero}'";
  pupe_query($query);

  if (laheta_sanoma($sanoma)) {
    $viesti = "UIB: {$sarjanumero} merkitty hylätyksi.";
  }

  if ($hylattavat_kpl > 1) {
    $task = 'hylky';
  }
  else {
    unset($task);
  }
}

if (isset($task) and $task == 'suorita_lusaus') {

  if (empty($uusi_paino)) {
    $errors[$sarjanumero] = t("Syötä uusi paino!");
    $task = 'lusaus';
  }
  elseif (!is_numeric($uusi_paino)) {
    $errors[$sarjanumero] = t("Syötetty arvo ei ole kelvollinen!");
    $task = 'lusaus';
  }
  elseif ($uusi_paino > $vanha_paino) {
    $errors[$sarjanumero] = t("Uusi paino ei voi olla suurempi kun vanha!");
    $task = 'lusaus';
  }
  else {

    $parametrit = hylky_lusaus_parametrit($sarjanumero);

    $parametrit['poistettu_paino'] = $vanha_paino - $uusi_paino;
    $parametrit['paino'] = $uusi_paino;
    $parametrit['laji'] = 'lusaus';

    $sanoma = laadi_edifact_sanoma($parametrit);

    $query = "UPDATE sarjanumeroseuranta SET
              massa = '{$uusi_paino}',
              lisatieto = 'Lusattu'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND sarjanumero = '{$sarjanumero}'";
    pupe_query($query);

    if (laheta_sanoma($sanoma)) {
      $viesti = "UIB: {$sarjanumero} uudeksi painoksi on päivitetty $uusi_paino kg.";
    }

    if ($lusattavat_kpl > 1) {
      $task = 'lusaus';
    }
    else {
      unset($task);
    }
  }
}

if (isset($task) and ($task == 'sinetoi' or $task == 'korjaa')) {

  if (empty($konttinumero)) {
    $errors['konttinumero'] = t("Syötä konttinumero!");
  }

  if (empty($sinettinumero)) {
    $errors['sinettinumero'] = t("Syötä sinettinumero!");
  }

  if (empty($taara)) {
    $errors['taara'] = t("Syötä taarapaino!");
  }

  if (!is_numeric($taara)) {
    $errors['taara'] = t("Epäkelpo taarapaino!");
  }

  if (empty($isokoodi)) {
    $errors['isokoodi'] = t("Syötä ISO-koodi!");
  }

  if (strlen($konttinumero) > 17) {
    $errors['konttinumero'] = t("Konttinumero saa olla korkeintaan 17 merkkiä pitkä.");
  }

  if (strlen($sinettinumero) > 10) {
    $errors['sinettinumero'] = t("Sinettinumero saa olla korkeintaan 10 merkkiä pitkä.");
  }

  if (count($errors) == 0) {

    if ($task == 'sinetoi') {
      $korjaus = false;
    }
    else {
      $korjaus = true;
    }

    $kontit = kontitustiedot($konttiviite, $temp_konttinumero);

    $kontin_kilot = $kontit[$temp_konttinumero]['paino'];

    $lista = $kontit[$temp_konttinumero]['lista'];

    $query = "UPDATE tilausrivi SET
              toimitettu = '{$kukarow['kuka']}',
              toimitettuaika = NOW()
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus IN ({$lista})";
    pupe_query($query);

    $konttinumero = mysql_real_escape_string($konttinumero);
    $sinettinumero = mysql_real_escape_string($sinettinumero);
    $taara = mysql_real_escape_string($taara);
    $isokoodi = mysql_real_escape_string($isokoodi);

    $query = "UPDATE tilausrivin_lisatiedot SET
              konttinumero      = '{$konttinumero}',
              sinettinumero     = '{$sinettinumero}',
              kontin_kilot      = '{$kontin_kilot}',
              kontin_taarapaino = '{$taara}',
              kontin_isokoodi   = '{$isokoodi}'
              WHERE yhtio       = '{$kukarow['yhtio']}'
              AND tilausrivitunnus IN ({$lista})";
    pupe_query($query);

    $parametrit = kontitus_parametrit($lista, $korjaus);

    if ($parametrit) {
      $parametrit['kontitus_info']['konttinumero'] = $konttinumero;
      $parametrit['kontitus_info']['sinettinumero'] = $sinettinumero;

      if ($korjaus) {

        $tilaukset = array_keys($parametrit['tilaukset']);
        $tilaukset = implode(",", $tilaukset);

        $query = "SELECT filename
                  FROM liitetiedostot
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND kayttotarkoitus = 'kontitussanoma'
                  AND selite = '{$temp_konttinumero}'
                  AND liitostunnus IN ({$tilaukset})";
        $result = pupe_query($query);

        $liite_info = mysql_fetch_array($result);

        $parametrit['sanomanumero'] = $liite_info['filename'];

      }

      $sanoma = laadi_edifact_sanoma($parametrit, $korjaus);
    }

    if (laheta_sanoma($sanoma)) {

      $query = "SELECT group_concat(DISTINCT lasku.tunnus) AS tunnukset
                FROM tilausrivin_lisatiedot AS trlt
                JOIN tilausrivi AS tr
                  ON tr.yhtio = trlt.yhtio
                  AND tr.tunnus = trlt.tilausrivitunnus
                JOIN lasku
                  ON lasku.yhtio = tr.yhtio
                  AND lasku.tunnus = tr.otunnus
                WHERE trlt.yhtio = '{$kukarow['yhtio']}'
                AND trlt.konttinumero = '{$konttinumero}'
                AND trlt.sinettinumero = '{$sinettinumero}'";
      $result = pupe_query($query);

      $tunnukset = mysql_result($result, 0);
      $tunnukset = explode(',', $tunnukset);

      $filesize = strlen($sanoma);
      $liitedata = mysql_real_escape_string($sanoma);

      foreach ($tunnukset as $tunnus) {

        // tarkistetaan onko vastaava sanoma jo liitetiedostona
        $query = "SELECT tunnus
                  FROM liitetiedostot
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND filename = '{$parametrit['sanomanumero']}'
                  AND kayttotarkoitus = 'kontitussanoma'
                  AND liitostunnus = '{$tunnus}'";
        $vastaavuusresult = pupe_query($query);
        $osumia = mysql_num_rows($vastaavuusresult);

        if ($osumia == 0) {

          $query = "INSERT INTO liitetiedostot SET
                    yhtio           = '{$kukarow['yhtio']}',
                    liitos          = 'lasku',
                    liitostunnus    = '$tunnus',
                    selite          = '{$konttinumero}',
                    laatija         = '{$kukarow['kuka']}',
                    luontiaika      = NOW(),
                    data            = '{$liitedata}',
                    filename        = '{$parametrit['sanomanumero']}',
                    filesize        = '$filesize',
                    filetype        = 'text/plain',
                    kayttotarkoitus = 'kontitussanoma'";
          pupe_query($query);

        }
        elseif ($korjaus){

          $korvattava = mysql_result($vastaavuusresult, 0);

          $query = "UPDATE liitetiedostot SET
                    data        = '$liitedata',
                    muutospvm   = NOW(),
                    selite      = '{$konttinumero}',
                    muuttaja    = '{$kukarow['kuka']}',
                    filename    = '{$parametrit['sanomanumero']}',
                    filesize    = '$filesize'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND  tunnus = '{$korvattava}'";
          pupe_query($query);
        }
      }

      $lahetys = 'OK';
    }
    else {
      $lahetys = 'EI';
    }

    unset($task);
  }
  else {
    $task = 'anna_konttitiedot';
  }

}

if (isset($task) and $task == 'laheta_satamavahvistus') {

  $parametrit = satamavahvistus_parametrit($konttiviite);

  $sarjanumerot = array_keys($parametrit['rullat']);
  $sarjanumero_string = implode(",", $sarjanumerot);

  $lahtoajat = explode(".", $lahtopvm);

  $lahtopaiva = $lahtoajat[0];
  $lahtokuu = $lahtoajat[1];
  $lahtovuosi = $lahtoajat[2];

  $koko_lahtoaika = $lahtovuosi.$lahtokuu.$lahtopaiva.$lahtotunti.$lahtominuutti;

  $parametrit['matka_info']['lahtoaika'] = $koko_lahtoaika;

  if ($parametrit) {
    $sanoma = laadi_edifact_sanoma($parametrit);
  }
  else{
    echo 'virhe<br>';die;
  }

  if (laheta_sanoma($sanoma)) {

    $sv_pvm = $lahtovuosi.'-'.$lahtokuu.'-'.$lahtopaiva.' '.$lahtotunti.':'.$lahtominuutti.':00';

    $query = "UPDATE laskun_lisatiedot SET
              satamavahvistus_pvm = '{$sv_pvm}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND konttiviite = '{$konttiviite}'";
    pupe_query($query);

    $query = "UPDATE sarjanumeroseuranta SET
              lisatieto = 'Toimitettu'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND sarjanumero IN  ({$sarjanumero_string})";
    pupe_query($query);

    $filesize = strlen($sanoma);
    $liitedata = mysql_real_escape_string($sanoma);

    foreach ($parametrit['laskutunnukset'] as $tunnus) {

      $query = "SELECT tunnus
                FROM liitetiedostot
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND liitos = 'lasku'
                AND liitostunnus = '{$tunnus}'
                AND kayttotarkoitus = 'satamavahvistus'
                AND selite = '{$konttiviite}'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {

        $query = "INSERT INTO liitetiedostot SET
                  yhtio           = '{$kukarow['yhtio']}',
                  liitos          = 'lasku',
                  liitostunnus    = '$tunnus',
                  selite          = '{$konttiviite}',
                  laatija         = '{$kukarow['kuka']}',
                  luontiaika      = NOW(),
                  data            = '{$liitedata}',
                  filename        = '{$parametrit['sanomanumero']}',
                  filesize        = '$filesize',
                  filetype        = 'text/plain',
                  kayttotarkoitus = 'satamavahvistus'";
        pupe_query($query);

      }

      $query = "UPDATE lasku SET
                alatila = 'D'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$tunnus}'";
      pupe_query($query);

    }

    foreach ($parametrit['rullat'] as $rulla) {

      $query = "UPDATE tuotepaikat SET
                saldo = saldo - 1
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$rulla['tuoteno']}'
                AND hyllyalue = '{$rulla['hyllyalue']}'
                AND hyllynro = '{$rulla['hyllynro']}'";
      pupe_query($query);

      $query = "UPDATE tilausrivi SET
                varattu = 0,
                laskutettu = '{$kukarow['kuka']}',
                laskutettuaika = NOW()
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$rulla['tilausrivitunnus']}'";
      pupe_query($query);

    }
  }
  else{
    echo "Lähetys epäonnistui!";
  }

  unset($task);
}

if (!isset($task)) {

  echo "<font class='head'>".t("Toimitusten seuranta")."</font><hr><br>";

  if (!isset($rajaus)) {
    $rajaus = 'aktiiviset';
  }

  switch ($rajaus) {

  case 'tulevat':

    $query = "SELECT GROUP_CONCAT(DISTINCT lasku.tunnus)
              FROM lasku
              LEFT OUTER JOIN tilausrivi
                ON tilausrivi.yhtio = lasku.yhtio
                AND tilausrivi.otunnus = lasku.tunnus
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND tilausrivi.tunnus IS NULL";
    $result = pupe_query($query);
    $rullattomat = mysql_result($result, 0);

    $rajauslisa = " AND lasku.alatila != 'D' AND lasku.tunnus IN ({$rullattomat}) ";
    $rajauslisa2 = "";
    $disable1 = 'disabled';
    break;

  case 'aktiiviset':

    $rajauslisa = " AND lasku.alatila != 'D' AND ss.sarjanumero IS NOT NULL ";
    $disable2 = 'disabled';
    break;

  case 'kontitetut':

    $query = "SELECT lasku.tunnus,
              group_concat(tilausrivi.tunnus) AS rivitunnukset,
              group_concat(DISTINCT IF(tilausrivi.laskutettu = '', 'kontittamatta', 'kontitettu')) AS tilanne
              FROM lasku LEFT OUTER JOIN tilausrivi ON tilausrivi.otunnus = lasku.tunnus
              WHERE lasku.yhtio = 'rplog' AND lasku.tilaustyyppi = 'N'
              AND tilausrivi.tunnus IS NOT NULL
              GROUP BY lasku.tunnus
              HAVING tilanne = 'kontitettu'";
    $result = pupe_query($query);

    $kontitetut = array();

    while ($lasku = mysql_fetch_assoc($result)) {
      $kontitetut[] = $lasku['tunnus'];
    }

    $kontitetut = implode(",", $kontitetut);

    $rajauslisa = " AND lasku.alatila != 'D' AND lasku.tunnus IN ({$kontitetut}) ";
    $disable3 = 'disabled';
    break;

  case 'toimitetut':
    $rajauslisa = " AND lasku.alatila = 'D' AND ss.sarjanumero IS NOT NULL ";
    $disable4 = 'disabled';
    break;

  case 'kaikki':
    $rajauslisa = '';
    $disable5 = 'disabled';
    break;

  default:
    $rajauslisa = '';
    break;
  }

  echo t("Näytä");
  echo "&nbsp;";
  echo "<form method='post'>";
  echo "<input type='hidden' name='rajaus' value='tulevat' />";
  echo "<input type='submit' {$disable1} value='" .t("Tulevat") ."'>";
  echo "</form>";
  echo "&nbsp;";
  echo "<form method='post'>";
  echo "<input type='hidden' name='rajaus' value='aktiiviset' />";
  echo "<input type='submit' {$disable2} value='" .t("Aktiiviset") ."'>";
  echo "</form>";
  echo "&nbsp;";
  echo "<form method='post'>";
  echo "<input type='hidden' name='rajaus' value='kontitetut' />";
  echo "<input type='submit' {$disable3} value='" .t("Kontitetut") ."'>";
  echo "</form>";
  echo "&nbsp;";
  echo "<form method='post'>";
  echo "<input type='hidden' name='rajaus' value='toimitetut' />";
  echo "<input type='submit' {$disable4} value='" .t("Toimitetut") ."'>";
  echo "</form>";
  echo "&nbsp;";
  echo "<form method='post'>";
  echo "<input type='hidden' name='rajaus' value='kaikki' />";
  echo "<input type='submit' {$disable5} value='" .t("Kaikki") ."'>";
  echo "</form><br><br>";

  $query = "SELECT lasku.asiakkaan_tilausnumero,
            lasku.sisviesti1 AS ohje,
            laskun_lisatiedot.matkakoodi,
            laskun_lisatiedot.konttiviite,
            laskun_lisatiedot.konttimaara,
            laskun_lisatiedot.matkatiedot,
            laskun_lisatiedot.konttityyppi,
            laskun_lisatiedot.satamavahvistus_pvm,
            laskun_lisatiedot.rullamaara,
            lasku.toimaika,
            lasku.tila,
            lasku.alatila,
            lasku.tunnus,
            trlt.konttinumero,
            GROUP_CONCAT(DISTINCT trlt.asiakkaan_rivinumero) AS tilausrivit,
            COUNT(tilausrivi.tunnus) AS rullat,
            SUM(IF(trlt.sinettinumero != '', 1, 0)) AS kontti_vahvistettu,
            SUM(IF(tilausrivi.var = 'P', 1, 0)) AS tulouttamatta,
            SUM(IF(tilausrivi.keratty = '', 1, 0)) AS kontittamatta,
            SUM(IF(tilausrivi.toimitettu = '', 1, 0)) AS toimittamatta,
            SUM(IF(trlt.kontin_mrn = '', 1, 0)) AS mrn_vastaanottamatta,
            SUM(IF(ss.lisatieto = 'Hylättävä', 1, 0)) AS hylattavat,
            SUM(IF(ss.lisatieto = 'Hylätty', 1, 0)) AS hylatyt,
            SUM(IF(ss.lisatieto = 'Lusattava', 1, 0)) AS lusattavat,
            SUM(IF(ss.lisatieto = 'Lusattu', 1, 0)) AS lusatut,
            SUM(IF(ss.lisatieto = 'Ylijaama', 1, 0)) AS ylijaama,
            SUM(IF(ss.lisatieto = 'Siirretty', 1, 0)) AS siirretty
            FROM lasku
            JOIN laskun_lisatiedot
              ON laskun_lisatiedot.yhtio = lasku.yhtio
              AND laskun_lisatiedot.otunnus = lasku.tunnus
            LEFT JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            LEFT JOIN tilausrivin_lisatiedot AS trlt
              ON trlt.yhtio = lasku.yhtio
              AND trlt.tilausrivitunnus = tilausrivi.tunnus
            LEFT JOIN sarjanumeroseuranta AS ss
              ON ss.yhtio = lasku.yhtio
              AND ss.myyntirivitunnus = tilausrivi.tunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tilaustyyppi = 'N'
            {$rajauslisa}
            AND laskun_lisatiedot.konttiviite != ''
            GROUP BY lasku.asiakkaan_tilausnumero, laskun_lisatiedot.konttiviite
            ORDER BY toimaika, konttiviite";
  $result = pupe_query($query);

  $tilaukset = array();

  $viitteet = array();

  if (mysql_num_rows($result) > 0) {

    while ($rivi = mysql_fetch_assoc($result)) {
      $viitteet[] = $rivi['konttiviite'];
      $tilaukset[$rivi['asiakkaan_tilausnumero'].$rivi['konttiviite']] = $rivi;
    }

    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Tilauskoodi")."</th>";
    echo "<th>".t("Matkakoodi")."</th>";
    echo "<th>".t("Laiva")."</th>";
    echo "<th>".t("Lähtöpäivä")."</th>";
    echo "<th>".t("Konttiviite")."</th>";
    echo "<th>".t("Rullien määrä")."</th>";
    echo "<th>".t("Ohje")."</th>";
    echo "<th>".t("Tapahtumat")."</th>";
    echo "<th>".t("Kontit")."</th>";
    echo "<th class='back'></th>";
    echo "</tr>";

    foreach ($tilaukset as $key => $tilaus) {

      $kontit_sinetointivalmiit = false;

      $query = "SELECT group_concat(otunnus)
                FROM laskun_lisatiedot
                WHERE yhtio = '{$yhtiorow['yhtio']}'
                AND konttiviite = '{$tilaus['konttiviite']}'";
      $result = pupe_query($query);
      $konttiviitteen_alaiset_tilaukset = mysql_result($result, 0);

      $query = "SELECT count(tilausrivi.tunnus) AS vahvistettu
                FROM tilausrivi
                JOIN tilausrivin_lisatiedot AS trlt
                  ON trlt.yhtio = tilausrivi.yhtio
                  AND trlt.tilausrivitunnus = tilausrivi.tunnus
                WHERE tilausrivi.yhtio = '{$yhtiorow['yhtio']}'
                AND tilausrivi.otunnus IN ({$konttiviitteen_alaiset_tilaukset})
                AND trlt.sinettinumero != ''";
      $result = pupe_query($query);
      $vahvistettu = mysql_result($result, 0);

      if ($vahvistettu > 0) {
        $kontit_sinetointivalmiit = true;
      }

      $poikkeukset = array();

      $viitelasku = array_count_values($viitteet);
      $tilauksia_viitteella = $viitelasku[$tilaus['konttiviite']];

      if ($tilaus['konttiviite'] == 'bookkaukseton') {
        $tilauksia_viitteella = 1;
      }

      $id = md5($tilaus['konttiviite']);

      if (in_array($tilaus['konttiviite'], $kasitellyt_konttiviitteet)) {
        $konttiviite_kasitelty = true;
      }
      else{
       $konttiviite_kasitelty = false;
      }

      $query = "SELECT tunnus
                FROM liitetiedostot
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND liitos = 'lasku'
                AND liitostunnus = '{$tilaus['tunnus']}'
                AND kayttotarkoitus = 'bookkaussanoma'";
      $result = pupe_query($query);

      $sanoma_numero = 1;

      $tapahtumat = "";

      while ($sanoma = mysql_fetch_assoc($result)) {
        $tapahtumat .= "&bull; <a href='view.php?id={$sanoma['tunnus']}' target='_blank'>{$sanoma_numero}. bookkaussanoma</a> haettu<br>";
        $sanoma_numero++;
      }

      echo "<tr>";

      echo "<td valign='top'>";
      echo $tilaus['asiakkaan_tilausnumero'];
      echo "</td>";

      if (!$konttiviite_kasitelty) {

        echo "<td valign='top' rowspan='{$tilauksia_viitteella}'>";
        echo $tilaus['matkakoodi'];
        echo "</td>";
      }

      if (!$konttiviite_kasitelty) {

        echo "<td valign='top' rowspan='{$tilauksia_viitteella}'>";

        $matkatiedot = unserialize($tilaus['matkatiedot']);
        $vanhat_tiedot = base64_encode($tilaus['matkatiedot']);

        echo "<a style='cursor:pointer;' id='uusilaivanappi{$id}' class='uusilaivanappi'>";
        echo $matkatiedot['transport_name'];
        echo "</a>";

        echo "<div style='display:none;' class='uusilaivaformi' id='uusilaivaformi{$id}'>";
        echo "<form method='post' action='toimitusten_seuranta.php?rajaus={$rajaus}'>";
        echo "<input type='hidden' name='task' value='laivamuutos' />";
        echo "<input type='hidden' name='vanhat_tiedot' value='{$vanhat_tiedot}' />";
        echo "<input type='hidden' name='konttiviite' ";
        echo "value='{$tilaus['konttiviite']}' />";
        echo "<input type='text' class='uusilaivainput' name='uusilaiva' style='width:100px' /><br>";
        echo "<input type='submit' value='". t("Muuta") ."' />&nbsp;";
        echo "<img src='{$palvelin2}pics/lullacons/stop.png' alt='Peru' title='Peru' class='uusilaivaperu' style='position:relative; top:4px;'>";
        echo "</form></div>";
        echo "</td>";

        if ($tilaus['konttiviite'] != 'bookkaukseton') {
          $kasitellyt_konttiviitteet[] = $tilaus['konttiviite'];
        }
      }

      if (!$konttiviite_kasitelty) {
        echo "<td valign='top' rowspan='{$tilauksia_viitteella}'>";

        if ($tilaus['matkakoodi'] == 'bookkaukseton') {
          # code...
        }
        elseif ($tilaus['satamavahvistus_pvm'] == '0000-00-00 00:00:00') {
          echo date("j.n.Y", strtotime($tilaus['toimaika']));
        }
        else {
          echo date("j.n.Y H:i", strtotime($tilaus['satamavahvistus_pvm']));
        }
        echo "</td>";
      }

      if (!$konttiviite_kasitelty) {

        echo "<td valign='top' rowspan='{$tilauksia_viitteella}'>";
        echo $tilaus['konttiviite'];
        echo "</td>";
      }

      if ($tilaus['rullat'] == 0 and $tilaus['matkakoodi'] != 'bookkaukseton') {
        $rullamaara = $tilaus['rullamaara'] . t(" kpl") . " (" . t("Ennakkoarvio") . ")";
      }
      else {

        $rullamaara = $tilaus['rullat'] . t(" kpl");

        if ($tilaus['matkakoodi'] != 'bookkaukseton') {
          $rullamaara .= "<br>" . t("Ennakkoarvio: ") . $tilaus['rullamaara'] . t(" kpl");
        }

        $poikkeukset = array(
          'odottaa hylkäystä' => $tilaus['hylattavat'],
          'hylatty' => $tilaus['hylatyt'],
          'odottaa lusausta' => $tilaus['lusattavat'],
          'lusattu' => $tilaus['lusatut'],
          'ylijäämä' => $tilaus['ylijaama'],
          'siirretty' => $tilaus['siirretty']
          );

        if ($tilaus['tilausrivit'] == '') {
          $tilaus['tilausrivit'] = 0;
        }

        $query = "SELECT tilausrivi.toimitettu, trlt.rahtikirja_id
                  FROM tilausrivi
                  JOIN tilausrivin_lisatiedot AS trlt
                    ON trlt.yhtio = tilausrivi.yhtio
                    AND trlt.tilausrivitunnus = tilausrivi.tunnus
                  WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                  AND tilausrivi.tyyppi = 'O'
                  AND trlt.asiakkaan_rivinumero IN ({$tilaus['tilausrivit']})
                  AND trlt.asiakkaan_tilausnumero = '{$tilaus['asiakkaan_tilausnumero']}'";
        $result = pupe_query($query);

        $kuitattu = $kuittaamatta = 0;
        $rahtikirjat = array();

        // katsotaan onko rahtikirja(t) kuitattu ja kuinka monta rahtikirjaa
        while ($rulla = mysql_fetch_assoc($result)) {
          if ($rulla['toimitettu'] == '' ) {
            $kuittaamatta++;
          }
          else {
            $kuitattu++;
          }
          $rahtikirjat[] = $rulla['rahtikirja_id'];
        }

        $rahtikirjat = array_count_values($rahtikirjat);
        $rahtikirjat = count($rahtikirjat);

        if ($rahtikirjat > 0) {
          $tapahtumat .= "&bull; <a href='saapuva_rahti.php?tnum={$tilaus['asiakkaan_tilausnumero']}'>" . $rahtikirjat ." kpl rahtikirjasanomia</a> haettu<br>";
        }

        if ($kuittaamatta == 0) {
          $tapahtumat .= "&bull; " .  t("Rahti kuitattu saapuneeksi") . "<br>";
        }
        elseif ($kuitattu > 0) {
          $tapahtumat .= "&bull; " .  t("Osa rahdista kuitattu saapuneeksi") . "<br>";
        }

        if ($tilaus['tulouttamatta'] == 0) {

          $tapahtumat .= "&bull; " .  t("Rullat viety varastoon") . "<br>";

          if (($tilaus['kontittamatta'] - $tilaus['ylijaama'] - $tilaus['hylatyt']) == 0) {
            $tapahtumat .= "&bull; " .  t("Rullat kontitettu") . "<br>";
          }
          elseif ($tilaus['kontittamatta'] < ($tilaus['rullat'] - $tilaus['ylijaama'] - $tilaus['hylatyt'])) {
            $tapahtumat .= "&bull; " .  t("Osa rullista kontitettu") . "<br>";

            $query = "SELECT group_concat(tilausrivi.tunnus) AS riveja
                      FROM tilausrivi
                      JOIN tilausrivin_lisatiedot AS trlt
                        ON trlt.yhtio = tilausrivi.yhtio
                        AND trlt.tilausrivitunnus = tilausrivi.tunnus
                      JOIN sarjanumeroseuranta AS ss
                        ON ss.yhtio = tilausrivi.yhtio
                        AND ss.myyntirivitunnus = tilausrivi.tunnus
                      WHERE tilausrivi.yhtio = '{$yhtiorow['yhtio']}'
                      AND tilausrivi.otunnus IN ({$konttiviitteen_alaiset_tilaukset})
                      AND trlt.sinettinumero != ''
                      AND (ss.lisatieto IS NULL OR ss.lisatieto = 'Lusaus')";
            $result = pupe_query($query);

            if (mysql_num_rows($result) > 0) {
              $konttiviitteesta_vahvistettu = mysql_result($result, 0);
            }
            else{
              $konttiviitteesta_vahvistettu = false;
            }

          }

          if (($tilaus['toimittamatta'] - $tilaus['ylijaama'] - $tilaus['hylatyt']) == 0) {
            $tapahtumat .= "&bull; " .  t("Kontit sinetöity") . "<br>";
          }
          elseif ($tilaus['toimittamatta'] < $tilaus['rullat']) {
            $tapahtumat .= "&bull; " .  t("Osa konteista sinetöity") . "<br>";
          }

         if (($tilaus['mrn_vastaanottamatta'] - $tilaus['ylijaama'] - $tilaus['hylatyt']) == 0) {
            $tapahtumat .= "&bull; " .  t("MRN-numerot vastaanotettu") . "<br>";
          }
          elseif ($tilaus['mrn_vastaanottamatta']  < $tilaus['rullat']) {
           $tapahtumat .= "&bull; " .  t("Osa MRN-numeroista vastaanotettu") . "<br>";
          }

        }
        elseif ($tilaus['tulouttamatta'] < $tilaus['rullat']) {
          $tapahtumat .= "&bull; " .  t("Osa rullista viety varastoon") . "<br>";
        }
      }

      echo "<td valign='top' align='center'>";
      echo $rullamaara;

      if (array_sum($poikkeukset) > 0) {
        echo "<br>Joista:";
        echo "<div style='text-align:left'>";

        foreach ($poikkeukset as $poikkeus => $kpl) {
          if ($kpl > 0) {

            switch ($poikkeus) {
              case 'odottaa hylkäystä':

                echo "<form method='post' name='hylkyform'>";
                echo "<input type='hidden' name='task' value='hylky' />";
                echo "<input type='hidden' name='tilausnumero' ";
                echo "value='{$tilaus['asiakkaan_tilausnumero']}' />";
                echo "</form>";

                echo "&bull; <a style='cursor:pointer; text-decoration:underline;' ";
                echo "onclick='document.forms[\"hylkyform\"].submit();'>";
                echo $kpl . " " . $poikkeus . "</a><br>";

                break;

              case 'odottaa lusausta':

                echo "<form method='post' name='lusausform'>";
                echo "<input type='hidden' name='task' value='lusaus' />";
                echo "<input type='hidden' name='tilausnumero' ";
                echo "value='{$tilaus['asiakkaan_tilausnumero']}' />";
                echo "</form>";

                echo "&bull; <a style='cursor:pointer; text-decoration:underline;' ";
                echo "onclick='document.forms[\"lusausform\"].submit();'>";
                echo $kpl . " " . $poikkeus . "</a><br>";

                break;

              default:
                echo "&bull; " . $kpl . " " . $poikkeus . "<br>";
                break;
            }
          }
        }
        echo "</div>";
      }

      echo "</td>";

      if (!$konttiviite_kasitelty) {
        echo "<td valign='top' style='width:100px;' rowspan='{$tilauksia_viitteella}'>";
        echo $tilaus['ohje'];
        echo "</td>";
      }

      echo "<td valign='top'>";
      echo $tapahtumat;
      echo "</td>";

      if ($konttiviite_kasitelty) {
        //echo "<td valign='top' align='center'>";
        //echo t("Sama konttiviite kuin yllä.");
        //echo "</td>";
      }
      elseif ($tilaus['matkakoodi'] == 'bookkaukseton') {
        echo "<td valign='top' rowspan='{$tilauksia_viitteella}' align='center'>";
        echo t("Ei tietoa");
        echo "</td>";
      }
      elseif (!$kontit_sinetointivalmiit or $tilaus['rullat'] == 0) {
        echo "<td valign='top' rowspan='{$tilauksia_viitteella}' align='center'>";
        echo $tilaus['konttimaara'] . " kpl (ennakkoarvio)";
        echo "</td>";
      }
      else {
        echo "<td valign='top' rowspan='{$tilauksia_viitteella}' align='right'>";

        $kontit = kontitustiedot($tilaus['konttiviite']);

        if ($kontit) {

          $kesken = 0;

          asort($kontit);

          $v = $kontit[''];
          unset($kontit['']);

          if (count($v) > 0) {
            $kontit[] = $v;
          }

          $mrn_tullut = true;

          foreach ($kontit as $konttinumero => $kontti) {

            if ($kontti['konttinumero'] == '') {
              echo "<div style='margin:0 5px 8px 5px; padding:5px; border-bottom:1px solid grey;'>";
              echo t("Konttiviitteestä "), $kontti['kpl'], t(" rullaa kontittamatta");
              echo "</div>";
              $kesken++;
              continue;
            }

            $temp_array = explode("/", $konttinumero);
            $_konttinumero = $konttinumero;

            echo "<div style='margin:0 5px 8px 5px; padding:5px; border-bottom:1px solid grey;'>";
            echo "{$_konttinumero}. ({$kontti['kpl']} kpl, {$kontti['paino']} kg)&nbsp;&nbsp;";

            if ($kontti['sinettinumero'] == '') {
              echo t("Kontitusta ei ole vielä vahvistettu"), '<br>';
              $kesken++;
            }
            elseif ($kontti['sinettinumero'] == 'X') {
              echo "<form method='post'>";
              echo "<input type='hidden' name='task' value='anna_konttitiedot' />";
              echo "<input type='hidden' name='temp_konttinumero' value='{$konttinumero}' />";
              echo "<input type='hidden' name='paino' value='{$kontti['paino']}' />";
              echo "<input type='hidden' name='rullia' value='{$kontti['kpl']}' />";
              echo "<input type='hidden' name='sinetoitava_konttiviite' value='{$tilaus['konttiviite']}' />";
              echo "<input type='submit' value='". t("Sinetöi") ."' />";
              echo "</form>";
              $kesken++;
            }
            elseif ($tilaus['satamavahvistus_pvm'] == '0000-00-00 00:00:00') {
              echo "<button type='button' disabled>" . t("Sinetöity") . "</button>";
              echo "<form method='post'>";
              echo "<input type='hidden' name='task' value='korjaa_konttitiedot' />";
              echo "<input type='hidden' name='temp_konttinumero' value='{$konttinumero}' />";
              echo "<input type='hidden' name='konttinumero' value='{$kontti['konttinumero']}' />";
              echo "<input type='hidden' name='sinettinumero' value='{$kontti['sinettinumero']}' />";
              echo "<input type='hidden' name='taara' value='{$kontti['taara']}' />";
              echo "<input type='hidden' name='isokoodi' value='{$kontti['isokoodi']}' />";
              echo "<input type='hidden' name='paino' value='{$kontti['paino']}' />";
              echo "<input type='hidden' name='rullia' value='{$kontti['kpl']}' />";
              echo "<input type='hidden' name='sinetoitava_konttiviite' value='{$tilaus['konttiviite']}' />";
              echo "<input type='submit' value='". t("Korjaa") ."' />";
              echo "</form>";
            }
            else {
              echo "<button type='button' disabled>" . t("Sinetöity") . "</button>";
            }

            if ($kontti['sinettinumero'] != '') {

              js_openFormInNewWindow();

              $session = mysql_real_escape_string($_COOKIE["pupesoft_session"]);
              $logo_url = $palvelin2."view.php?id=".$yhtiorow["logo"];

              echo "&nbsp;<form method='post' id='hae_pakkalista{$_konttinumero}'>";
              echo "<input type='hidden' name='task' value='hae_pakkalista' />";
              echo "<input type='hidden' name='pakkalista' value='{$kontti['pakkalista']}' />";
              echo "<input type='hidden' name='tee' value='XXX' />";
              echo "<input type='hidden' name='konttinumero' value='{$konttinumero}' />";
              echo "<input type='hidden' name='sinettinumero' value='{$kontti['sinettinumero']}' />";
              echo "<input type='hidden' name='paino' value='{$kontti['paino']}' />";
              echo "<input type='hidden' name='session' value='{$session}' />";
              echo "<input type='hidden' name='logo_url' value='{$logo_url}' />";
              echo "<input type='hidden' name='taara' value='{$kontti['taara']}' />";
              echo "<input type='hidden' name='kpl' value='{$kontti['kpl']}' />";
              echo "<input type='hidden' name='konttiviite' value='{$tilaus['konttiviite']}' />";
              echo "</form>";
              echo "<button onClick=\"js_openFormInNewWindow('hae_pakkalista{$_konttinumero}', 'Pakkalista'); return false;\" />";
              echo t("Pakkalista");
              echo "</button>";

              if ($kontti['mrn'] == 'EU') {

                echo "<div style='text-align:center; margin:8px 0'>";
                echo t("EU:n sisäinen tilaus");
                echo "</div>";
              }
              elseif ($kontti['mrn'] != '') {

                echo "<div style='text-align:center; margin:8px 0'>MRN: ";
                echo "<input type='text'  value='{$kontti['mrn']}' readonly>";
                echo "</div>";
              }
              else {

                echo "<div style='text-align:center; margin:8px 0'>";
                echo t("Odotetaan MRN-numeroa");
                echo "</div>";

                $mrn_tullut = false;
              }
            }
            echo "</div>";
          }

          if ($kesken == 0 and !$mrn_tullut) {

            echo "<div style='text-align:center; margin:8px 0'>";
            echo "<form method='post' action='toimitusten_seuranta.php?rajaus={$rajaus}'>";
            echo "<input type='hidden' name='task' value='eu_tilaus' />";
            echo "<input type='hidden' name='konttiviite' value='{$tilaus['konttiviite']}' />";
            echo "<input type='submit' value='". t("EU-tilaus") ."' />&nbsp;";
            echo "</form>";
            echo "</div>";
          }

          if ($kesken == 0 and $mrn_tullut and $tilaus['satamavahvistus_pvm'] != '0000-00-00 00:00:00') {

            echo "<div style='text-align:center;margin:10px 0;'><button type='button' disabled>";
            echo t("Satamavahvistus lähetetty");
            echo "</button>";
          }
          elseif ($kesken == 0 and $mrn_tullut) {

            echo "
              <div style='text-align:center;margin:10px 0;'>
              <form method='post'>
              <input type='hidden' name='konttiviite' value='{$tilaus['konttiviite']}' />
              <input type='hidden' name='matkakoodi' value='{$tilaus['matkakoodi']}' />
              <input type='hidden' name='lahtopvm_arvio' value='{$tilaus['toimaika']}' />
              <input type='hidden' name='task' value='tee_satamavahvistus' />
              <input type='submit' value='". t("Tee satamavahvistus") ."' />
              </form>
              </div>";
          }

          if ($kesken == 0 and $mrn_tullut) {

            $parametrit = lahtoilmoitus_parametrit($tilaus['konttiviite']);
            $parametrit = serialize($parametrit);
            $parametrit = base64_encode($parametrit);

            $session = mysql_real_escape_string($_COOKIE["pupesoft_session"]);
            $logo_url = $palvelin2."view.php?id=".$yhtiorow["logo"];

            echo "
            <div style='text-align:center;margin:10px 0;'>
            <form method='post' id='nayta_lahtoilmoitus{$id}'>
            <input type='hidden' name='parametrit' value='{$parametrit}' />
            <input type='hidden' name='task' value='nayta_lahtoilmoitus' />
            <input type='hidden' name='session' value='{$session}' />
            <input type='hidden' name='logo_url' value='{$logo_url}' />
            <input type='hidden' name='tee' value='XXX' />
            </form>
            <button onClick=\"js_openFormInNewWindow('nayta_lahtoilmoitus{$id}',
             'Satamavahvistus'); return false;\" />";

            echo t("Näytä lähtöilmoitus");
            echo "</button></div>";

            $parametrit = konttierittely_parametrit($tilaus['konttiviite']);
            $tonnit = $parametrit['total_paino'] / 1000;
            $parametrit = serialize($parametrit);
            $parametrit = base64_encode($parametrit);

            echo "
            <div style='text-align:center;margin:10px 0;'>
            <form method='post' id='nayta_konttierittely{$id}'>
            <input type='hidden' name='parametrit' value='{$parametrit}' />
            <input type='hidden' name='task' value='nayta_konttierittely' />
            <input type='hidden' name='session' value='{$session}' />
            <input type='hidden' name='logo_url' value='{$logo_url}' />
            <input type='hidden' name='tee' value='XXX' />
            </form>
            <button onClick=\"js_openFormInNewWindow('nayta_konttierittely{$id}',
             'Satamavahvistus'); return false;\" />";

            echo t("Näytä konttierittely");
            echo "</button></div>";

            if ($tilaus['satamavahvistus_pvm'] != '0000-00-00 00:00:00') {

              $query = "SELECT *
                        FROM lasku
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND asiakkaan_tilausnumero = '{$tilaus['konttiviite']}'
                        AND sisviesti1 = 'konttiviitelasku'";
              $result = pupe_query($query);

              if (mysql_num_rows($result) > 0) {
                $nappi = t("Laskutusraportti");
                $laadittu = 'joo';
              }
              else {
                $nappi = t("Laadi laskutusraportti");
                $laadittu = 'ei';
              }

              echo "
              <div style='text-align:center'>
              <form method='post'>
              <input type='hidden' name='tonnit' value='{$tonnit}' />
              <input type='hidden' name='task' value='laadi_laskutusraportti' />
              <input type='hidden' name='laadittu' value='{$laadittu}' />
              <input type='hidden' name='konttiviite' value='{$tilaus['konttiviite']}' />
              <input type='submit' value='{$nappi}' />
              </form></div>";

              if ($laadittu == 'joo') {

                $poistettavatunnus = mysql_fetch_assoc($result);

                  echo "
                  <div style='text-align:center; padding-top:6px;'>
                  <form method='post'>
                  <input type='hidden' name='task' value='poistalasku' />
                  <input type='hidden' name='poistettavatunnus' value='{$poistettavatunnus['tunnus']}' />
                  <input type='submit' value='laadi uudestaan' />
                  </form></div>";

              }



            }



            echo "</div>";
          }

          echo "</td>";

          if ($tilaus['konttiviite'] != 'bookkaukseton') {
            $kasitellyt_konttiviitteet[] = $tilaus['konttiviite'];
          }
        }
      }
      echo "</tr>";
    }
    echo "</table>";
  }
  else {
    echo "Ei tilauksia...";
  }
}

echo "<script type='text/javascript'>

  $('.uusilaivaperu').click(function() {
    $('.uusilaivaformi').hide();
    $('.uusilaivainput').val('');
  });

  $('.uusilaivanappi').bind('click',function(){
    var id = $(this).attr('id').replace('nappi', 'formi');
    $('.uusilaivaformi').hide();
    $('#'+id).show();
  });

</script>";

if (isset($task) and ($task == 'anna_konttitiedot' or $task == 'korjaa_konttitiedot')) {

  if ($task == 'anna_konttitiedot') {
    $uusi_task = 'sinetoi';
  }
  else {
    $uusi_task = 'korjaa';
  }

  echo "<a href='toimitusten_seuranta.php'>« " . t("Palaa toimitusten seurantaan") . "</a><br><br>";
  echo "<font class='head'>".t("Kontin sinetöinti")."</font></a><hr><br>";

  echo "
  <form method='post'>
  <input type='hidden' name='task' value='{$uusi_task}' />
  <input type='hidden' name='rullia' value='{$rullia}' />
  <input type='hidden' name='paino' value='{$paino}' />
  <input type='hidden' name='konttiviite' value='{$sinetoitava_konttiviite}' />
  <input type='hidden' name='temp_konttinumero' value='{$temp_konttinumero}' />
  <table>
  <tr>
    <th>" . t("Konttinumero") ."</th>
    <td><input type='text' name='konttinumero' value='{$konttinumero}' /></td>
    <td class='back error'>{$errors['konttinumero']}</td>
  </tr>
  <tr>
    <th>" . t("Sinettinumero") ."</th>
    <td><input type='text' name='sinettinumero' value='{$sinettinumero}' /></td>
    <td class='back error'>{$errors['sinettinumero']}</td>
  </tr>
  <tr>
    <th>" . t("Taarapaino") ." (kg)</th>
    <td><input type='text' name='taara' value='{$taara}' /></td>
    <td class='back error'>{$errors['taara']}</td>
  </tr>
  <tr>
    <th>" . t("ISO-koodi") ."</th>
    <td><input type='text' name='isokoodi' value='{$isokoodi}' /></td>
    <td class='back error'>{$errors['isokoodi']}</td>
  </tr>
  <tr>
    <th>" . t("Rullien määrä") ."</th>
    <td>{$rullia} kpl</td>
    <td class='back'></td>
  </tr>
  <tr>
    <th>" . t("Paino") ."</th>
    <td>{$paino}</td>
    <td class='back'></td>
  </tr>
  <tr>
    <th></th>
    <td align='right'><input type='submit' value='". t("Sinetöi") ."' /></td>
    <td class='back'></td>
  </tr>
  </table>
  </form>";

}

if (isset($task) and $task == 'tee_satamavahvistus') {

  $lahtopvm_arvio = date("d.m.Y", strtotime($lahtopvm_arvio));

  echo "
    <script>
      $(function($){
         $.datepicker.regional['fi'] = {
                     closeText: 'Sulje',
                     prevText: '&laquo;Edellinen',
                     nextText: 'Seuraava&raquo;',
                     currentText: 'T&auml;n&auml;&auml;n',
             monthNames: ['Tammikuu','Helmikuu','Maaliskuu','Huhtikuu','Toukokuu','Kes&auml;kuu',
              'Hein&auml;kuu','Elokuu','Syyskuu','Lokakuu','Marraskuu','Joulukuu'],
              monthNamesShort: ['Tammi','Helmi','Maalis','Huhti','Touko','Kes&auml;',
              'Hein&auml;','Elo','Syys','Loka','Marras','Joulu'],
                      dayNamesShort: ['Su','Ma','Ti','Ke','To','Pe','Su'],
                      dayNames: ['Sunnuntai','Maanantai','Tiistai','Keskiviikko','Torstai','Perjantai','Lauantai'],
                      dayNamesMin: ['Su','Ma','Ti','Ke','To','Pe','La'],
                      weekHeader: 'Vk',
              dateFormat: 'dd.mm.yy',
                      firstDay: 1,
                      isRTL: false,
                      showMonthAfterYear: false,
                      yearSuffix: ''};
          $.datepicker.setDefaults($.datepicker.regional['fi']);
      });

      $(function() {
        $('#lahtopvm').datepicker();
      });
      </script>
  ";

  echo "<a href='toimitusten_seuranta.php'>« " . t("Palaa toimitusten seurantaan") . "</a><br><br>";
  echo "<font class='head'>".t("Satamavahvistus")."</font><hr><br>";
  echo "
    <form method='post'>
    <input type='hidden' name='konttiviite' value='{$konttiviite}' />
    <table>
    <tr><th>" . t("Matkakoodi") ."</th><td>{$matkakoodi}</td></tr>
    <tr><th>" . t("Konttiviite") ."</th><td>{$konttiviite}</td></tr>
    <tr><th>" . t("Lähtöpäivä") ."</th><td><input type='text' id='lahtopvm' name='lahtopvm' value='{$lahtopvm_arvio}' /></td></tr>
    <tr><th>" . t("Lähtöaika") ."</th><td>";

  echo "<select name='lahtotunti'>";
  echo "<option>Tunti</option>";
  $h = 0;
  while ($h <= 23) {
    $_h = str_pad($h,2,"0",STR_PAD_LEFT);
    echo "<option value='{$_h}'>{$_h}</option>";
    $h++;
  }
  echo "</select>";

  echo " : ";

  echo "<select name='lahtominuutti'>";
  echo "<option>Minuutti</option>";
  $m = 0;
  while ($m <= 59) {
    $_m = str_pad($m,2,"0",STR_PAD_LEFT);
    echo "<option value='{$_m}'>{$_m}</option>";
    $m++;
  }
  echo "</select>";

  echo "
  </td></tr>
  <tr><th></th><td align='right'><input type='submit' value='". t("Lähetä satamavahvistus") ."' /></td></tr>
  </table>
  <input type='hidden' name='task' value='laheta_satamavahvistus' />
  </form>";
}

if (isset($task) and $task == 'hylky') {

  $query = "SELECT ss.tunnus,
            ss.massa,
            ss.sarjanumero,
            lasku.asiakkaan_tilausnumero
            FROM lasku
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            JOIN sarjanumeroseuranta AS ss
              ON ss.yhtio = tilausrivi.yhtio
              AND ss.myyntirivitunnus = tilausrivi.tunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.asiakkaan_tilausnumero = '{$tilausnumero}'
            AND ss.lisatieto = 'Hylättävä'";
  $result = pupe_query($query);

  echo "<a href='toimitusten_seuranta.php'>« " . t("Palaa toimitusten seurantaan") . "</a><br><br>";
  echo "<font class='head'>".t("Rullien hylkääminen")."</font></a><hr><br>";

  $hylattavat_kpl = mysql_num_rows($result);

  while ($hylattava = mysql_fetch_assoc($result)) {

    if (isset($errors[$hylattava['sarjanumero']])) {
      $hylky_error = $errors[$hylattava['sarjanumero']];
    }
    else{
      $hylky_error = '';
    }

    echo "
    <form method='post'>
    <input type='hidden' name='task' value='suorita_hylky' />
    <input type='hidden' name='hylattavat_kpl' value='{$hylattavat_kpl}' />
    <input type='hidden' name='tilausnumero' value='{$hylattava['asiakkaan_tilausnumero']}' />
    <input type='hidden' name='sarjanumero' value='{$hylattava['sarjanumero']}' />
    <table>
    <tr><th>" . t("Tilausnumero") ."</th><td>{$hylattava['asiakkaan_tilausnumero']}</td><td class='back'></td></tr>
    <tr><th>" . t("UIB") ."</th><td>{$hylattava['sarjanumero']}</td><td class='back'></td></tr>
    <tr><th></th><td align='right'><input type='submit' value='". t("Vahvista hylkäys") ."' /></td><td class='back'></td></tr>
    </table>
    </form><br>";
  }
}

if (isset($task) and $task == 'lusaus') {

  $query = "SELECT ss.tunnus,
            ss.massa,
            ss.sarjanumero,
            lasku.asiakkaan_tilausnumero
            FROM lasku
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            JOIN sarjanumeroseuranta AS ss
              ON ss.yhtio = tilausrivi.yhtio
              AND ss.myyntirivitunnus = tilausrivi.tunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.asiakkaan_tilausnumero = '{$tilausnumero}'
            AND ss.lisatieto = 'Lusattava'";
  $result = pupe_query($query);

  echo "<a href='toimitusten_seuranta.php'>« " . t("Palaa toimitusten seurantaan") . "</a><br><br>";
  echo "<font class='head'>".t("Rullien lusaus")."</font></a><hr><br>";

  if (isset($viesti)) {
    echo $viesti;
  }

  $lusattavat_kpl = mysql_num_rows($result);

  while ($lusattava = mysql_fetch_assoc($result)) {

    if (isset($errors[$lusattava['sarjanumero']])) {
      $lusaus_error = $errors[$lusattava['sarjanumero']];
    }
    else{
      $lusaus_error = '';
    }

    $vanha_paino = (int) $lusattava['massa'];

    echo "
    <form method='post'>
    <input type='hidden' name='task' value='suorita_lusaus' />
    <input type='hidden' name='lusattavat_kpl' value='{$lusattavat_kpl}' />
    <input type='hidden' name='tilausnumero' value='{$lusattava['asiakkaan_tilausnumero']}' />
    <input type='hidden' name='sarjanumero' value='{$lusattava['sarjanumero']}' />
    <input type='hidden' name='vanha_paino' value='{$lusattava['massa']}' />
    <table>
    <tr><th>" . t("Tilausnumero") ."</th><td>{$lusattava['asiakkaan_tilausnumero']}</td><td class='back'></td></tr>
    <tr><th>" . t("UIB") ."</th><td>{$lusattava['sarjanumero']}</td><td class='back'></td></tr>
    <tr><th>" . t("Vanha paino") ."</th><td>{$vanha_paino} kg</td><td class='back'></td></tr>
    <tr><th>" . t("Uusi paino") ."</th><td><input type='text' name='uusi_paino' /></td><td class='back error'>{$lusaus_error}</td></tr>
    <tr><th></th><td align='right'><input type='submit' value='". t("Suorita lusaus") ."' /></td><td class='back'></td></tr>
    </table>
    </form><br>";

  }
}

if (isset($task) and $task == 'luo_laskutusraportti' and !isset($vahvista_muutos_submit)) {

  $uusi_nimike = false;
  $edit_nimike = false;

  if (isset($edit)) {
    $edit_tunnus = key($edit);
    $edit_nimike = true;
    $task = 'laadi_laskutusraportti';
  }
  elseif (isset($delete)) {

    $tunnus = key($delete);
    pupe_query("DELETE FROM tilausrivi WHERE tunnus = '{$tunnus}'");
    $task = 'laadi_laskutusraportti';
  }
  elseif (isset($lisaa_nimike_submit)) {
    $uusi_nimike = true;
    $task = 'laadi_laskutusraportti';
  }
  elseif (isset($vahvista_nimike_submit)) {

    $laskuquery = "SELECT *
                   FROM lasku
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND asiakkaan_tilausnumero = '{$konttiviite}'";
    $laskuresult = pupe_query($laskuquery);
    $laskurow = mysql_fetch_assoc($laskuresult);

    $kukarow['kesken'] = $laskurow['tunnus'];

    // haetaan tuotteen tiedot
    $tuotequery = "SELECT *
                   FROM tuote
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND tunnus = '{$lisattava_nimike}'";
    $tuoteresult = pupe_query($tuotequery);
    $trow = mysql_fetch_assoc($tuoteresult);

    $kpl = $lisattava_kpl;

    require "tilauskasittely/lisaarivi.inc";

    $task = 'laadi_laskutusraportti';
  }
  else {

    echo "<a href='toimitusten_seuranta.php?rajaus=toimitetut'>« " . t("Palaa toimitusten seurantaan") . "</a><br><br>";
    echo "<font class='head'>".t("Laskutusraportti luotu")."</font></a><hr><br>";

    $parametrit = laskutusraportti_parametrit($konttiviite);
    $parametrit['tonnit'] = $tonnit;
    $parametrit = serialize($parametrit);
    $parametrit = base64_encode($parametrit);

    js_openFormInNewWindow();

    echo "
    <form method='post' id='nayta_laskutusraportti'>
    <input type='hidden' name='parametrit' value='{$parametrit}' />
    <input type='hidden' name='task' value='nayta_laskutusraportti' />
    <input type='hidden' name='session' value='{$session}' />
    <input type='hidden' name='vapaa_varastointi' value='{$vapaa_varastointi}' />
    <input type='hidden' name='logo_url' value='{$logo_url}' />
    <input type='hidden' name='tee' value='XXX' />
    <table>
    <tr><th>" . t("Asiakas") ."</th><td>Kotka Mills</td><td class='back'></td></tr>
    <tr><th>" . t("Konttiviite") ."</th><td>{$konttiviite}</td><td class='back'></td></tr>
    <tr><th>" . t("Tonnit") ."</th><td>{$tonnit}</td><td class='back'></td></tr>
    <tr><th>" . t("Raportti luotu") ."</th><td align='right'>";

    echo "<button onClick=\"js_openFormInNewWindow('nayta_laskutusraportti', 'Laskutusraportti'); return false;\" />";
    echo t("Lataa pdf");
    echo "</button>";

    echo "
    </td><td class='back'></td></tr>
    </table>
    </form>";
  }
}

if (isset($vahvista_muutos_submit)) {

  if (!empty($muutettava_kpl)) {

    $uusikpl = mysql_real_escape_string($muutettava_kpl);
    $tunnus = key($vahvista_muutos_submit);
    $query = "UPDATE tilausrivi
              SET tilkpl = '{$uusikpl}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$tunnus}'";
    pupe_query($query);
  }
  unset($vahvista_muutos_submit);
  $task = 'laadi_laskutusraportti';
  $laadittu = 'joo';
}


if (isset($task) and $task == 'laadi_laskutusraportti') {

  if ($laadittu == 'ei')  {

    $kukarow['kesken'] = 0;

    require_once "tilauskasittely/luo_myyntitilausotsikko.inc";

    $tunnus = luo_myyntitilausotsikko('RIVISYOTTO', 102);

    $update_query = "UPDATE lasku SET
                     asiakkaan_tilausnumero = '{$konttiviite}',
                     sisviesti1 = 'konttiviitelasku',
                     toimaika = now()
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus = '{$tunnus}'";
    pupe_query($update_query);

    // ehkä asiakaskohtaiseksi tulevaisuudessa...
    $vapaa_varastointi = 7;

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
                   AND tuoteno = 'VARASTOINTI'";
    $tuoteresult = pupe_query($tuotequery);
    $trow = mysql_fetch_assoc($tuoteresult);

    $varastointikaudet = hae_varastointikaudet($konttiviite);

    foreach ($varastointikaudet as $kausi) {

      $varastointipaivat = $kausi['varastointipaivat'] - $vapaa_varastointi;

      if ($varastointipaivat > 0) {
        $kpl = $varastointipaivat;
        $hinta = $varastointipaivat * $kausi['total_paino'] * $trow['myyntihinta'];

        require "tilauskasittely/lisaarivi.inc";

        $sisaan = $kausi['sisaan'];
        $ulos = $kausi['ulos'];

        $update_query = "UPDATE tilausrivi SET
                         toimaika = '{$sisaan}',
                         kerayspvm = '{$ulos}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tunnus = '{$lisatty_tun}'";
        pupe_query($update_query);
      }
    }
    header("Location: toimitusten_seuranta.php?task=laadi_laskutusraportti&laadittu=joo&tonnit={$tonnit}&konttiviite={$konttiviite}");
    exit;
  }

  $query = "SELECT tilausrivi.*
            FROM lasku
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND asiakkaan_tilausnumero = '{$konttiviite}'
            AND sisviesti1 = 'konttiviitelasku'";
  $result = pupe_query($query);

  $nimikkeet = array();
  $nimikenimet = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    $nimikkeet[] = $rivi;
    $nimikenimet[] = $rivi['nimitys'];
  }

  echo "<a href='toimitusten_seuranta.php?rajaus=toimitetut'>« " . t("Palaa toimitusten seurantaan") . "</a><br><br>";
  echo "<font class='head'>".t("Laadi laskutusraportti")."</font></a><hr><br>";

  echo "
  <form method='post' id='luo_laskutusraportti'>
  <input type='hidden' name='task' value='luo_laskutusraportti' />
  <input type='hidden' id='hidden_tonnit' name='tonnit' value='{$tonnit}' />
  <input type='hidden' name='konttiviite' value='{$konttiviite}' />
  <table>
  <tr><th>" . t("Asiakas") ."</th><td align='right'>Kotka Mills</td><td class='back'></td></tr>
  <tr><th>" . t("Konttiviite") ."</th><td align='right'>{$konttiviite}</td><td class='back'></td></tr>
  <tr><th>" . t("Tonnit") ."</th><td align='right'>{$tonnit}</td><td class='back'></td></tr>
  <tr><th style='text-align:center' colspan='2'>" . t("Myydyt nimikkeet") ."</th><td class='back'></td></tr>";

  foreach ($nimikkeet as $nimike) {

    if (isset($edit_tunnus) and $nimike['tunnus'] == $edit_tunnus)  {

        $query = "SELECT *
                  FROM tilausrivi
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus = '{$edit_tunnus}'";
        $result = pupe_query($query);
        $tilausrivi = mysql_fetch_assoc($result);

        switch ($tilausrivi['yksikko']) {
          case 'KPL':
            $yks = "kpl.";
            break;

          case 'H':
            $yks = "h.";
            break;

          default:
            # code...
            break;
        }

        echo "
        <tr><th>" . $nimike['nimitys'] . "</th><td align='right'>";

        echo "
        <input type='text' name='muutettava_kpl' size='5' value='{$tilausrivi['tilkpl']}' /> {$yks}
        </td><td class='back'><input type='submit' name='vahvista_muutos_submit[{$tilausrivi['tunnus']}]' value='". t("Vahvista") ."' /></td></tr>";

    }
    else {

      if ($nimike['yksikko'] == 'PVA') {
        $yksikko = t("vrk.");
      }
      elseif ($nimike['yksikko'] == 'KPL') {
        $yksikko = t("kpl.");
      }
      elseif ($nimike['yksikko'] == 'TON') {
        $yksikko = t("t.");
      }
      elseif ($nimike['yksikko'] == 'H') {
        $yksikko = t("h.");
      }

      if ($nimike['nimitys'] == "Varastointi") {

        $tuotequery = "SELECT *
                       FROM tuote
                       WHERE yhtio = '{$kukarow['yhtio']}'
                       AND tuoteno = 'VARASTOINTI'";
        $tuoteresult = pupe_query($tuotequery);
        $trow = mysql_fetch_assoc($tuoteresult);

        $tonnit = $nimike['hinta'] / $nimike['tilkpl'] / $trow['myyntihinta'];

        $sisaan = date("j.n.Y", strtotime($nimike['toimaika']));
        $ulos = date("j.n.Y", strtotime($nimike['kerayspvm']));

        $teksti = (int) $nimike['tilkpl'] . " " . t("vrk.") . " | ";

        $teksti .=  $sisaan . " &mdash; " . $ulos . " | ";

        $teksti .= $tonnit . " t. - " . number_format((float)$nimike['hinta'], 3, '.', '') . " €";
      }
      elseif ($nimike['yksikko'] == 'TON') {

        $teksti = $tonnit . " " . $yksikko . " - " . number_format((float)($nimike['hinta'] * $tonnit), 2, '.', '') . " €";
      }
      else {
        $teksti =  (int) $nimike['tilkpl'] . " " .  $yksikko . " - ". $nimike['tilkpl'] * $nimike['hinta'] ." €";
      }

      echo "<tr><th>" . $nimike['nimitys'] ."</th><td align='right'>";
      echo $teksti;
      echo "</td><td class='back'>";

      if ($nimike['yksikko'] != "TONNI" and $nimike['tuoteno'] != "VARASTOINTI") {
        echo "<input type='submit' name='edit[{$nimike['tunnus']}]' value='" . t("Muokkaa") . "' />";
      }

      if ($nimike['tuoteno'] != "VARASTOINTI") {
        echo "<input type='submit' name='delete[{$nimike['tunnus']}]' value='" . t("Poista") . "' />";
      }

      echo "</td></tr>";

    }

  }

  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND mallitarkenne = 'varastointinimike'";
  $result = pupe_query($query);

  $lisattavat_nimikkeet = array();

  while ($nimike = mysql_fetch_assoc($result)) {
   if (!in_array($nimike['nimitys'], $nimikenimet)) {
      $lisattavat_nimikkeet[] = $nimike;
    }
  }

  if ($uusi_nimike) {
    echo "
    <tr><th>" . t("Lisää nimike") ."</th><td align='right'><select name='lisattava_nimike' id='nimikevalinta' style='width:190px;'>";

    echo "<option value='0'>Valitse nimike</option>";

    foreach ($lisattavat_nimikkeet as $nimike) {

      switch ($nimike['yksikko']) {
        case 'KPL':
          $txt = $nimike['nimitys'] . " " . "(kpl.)";
          break;

        case 'TON':
          $txt = $nimike['nimitys'] . " " . "(t.)";
          break;

        case 'H':
          $txt = $nimike['nimitys'] . " " . "(h.)";
          break;

        case 'MET':
          $txt = $nimike['nimitys'] . " " . "(m.)";
          break;

        default:
          # code...
          break;
      }

      echo "<option value='{$nimike['tunnus']}'>{$txt}</option>";
    }

    echo "<select />&nbsp;&nbsp;&nbsp;";
    echo "<input style='visibility:hidden; width:50px;' id='kplvalinta' type='text' name='lisattava_kpl' /> <div style='display:inline-block;width:40px; text-align:center;' id='nimikeyksikko'></div>";
    echo "<span id='nimikelisaysnappi' style='visibility:hidden'><input type='submit' name='vahvista_nimike_submit' value='". t("Lisää") ."' /></span></td><td class='back'></td></tr>";

  }
  elseif (count($lisattavat_nimikkeet) > 0) {

    echo "
    <tr><th>" . t("Nimikkeen lisäys") ."</th><td align='right'><input type='submit' name='lisaa_nimike_submit' value='". t("Lisää nimike") ."' /></td><td class='back'></td></tr>";
  }

  echo "
  <tr><th></th><td align='right' width='350'><input type='submit' value='Luo raportti' /></td><td class='back'></td></tr>
  </table>
  </form>";
}

echo "
<script type='text/javascript'>

  $('#nimikevalinta').bind('change',function(){

    var txt = $('#nimikevalinta option:selected').text();

    if (txt.indexOf('(t.)') >= 0) {

      var value = $('#hidden_tonnit').val();

      $('#kplvalinta').prop('readonly', true);
      $('#kplvalinta').val(value);
      $('#nimikeyksikko').text('t.');
      $('#kplvalinta').css('visibility', 'visible');
      $('#nimikelisaysnappi').css('visibility', 'visible');
    }
    else if (txt.indexOf('(kpl.)') >= 0) {

      $('#kplvalinta').prop('readonly', false);
      $('#kplvalinta').val('');
      $('#nimikeyksikko').text('kpl.');
      $('#kplvalinta').css('visibility', 'visible');
      $('#nimikelisaysnappi').css('visibility', 'visible');
    }
    else if (txt.indexOf('(h.)') >= 0) {

      $('#kplvalinta').prop('readonly', false);
      $('#kplvalinta').val('');
      $('#nimikeyksikko').text('h.');
      $('#kplvalinta').css('visibility', 'visible');
      $('#nimikelisaysnappi').css('visibility', 'visible');
    }
    else if (txt.indexOf('(m.)') >= 0) {

      $('#kplvalinta').prop('readonly', false);
      $('#kplvalinta').val('');
      $('#nimikeyksikko').text('m.');
      $('#kplvalinta').css('visibility', 'visible');
      $('#nimikelisaysnappi').css('visibility', 'visible');
    }
    else {

      $('#nimikeyksikko').text('');
      $('#kplvalinta').css('visibility', 'hidden');
      $('#nimikelisaysnappi').css('visibility', 'hidden');
    }

  });

</script>";

require "inc/footer.inc";
