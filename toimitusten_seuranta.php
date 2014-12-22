<?php

require 'inc/edifact_functions.inc';

if (isset($_POST['task']) and $_POST['task'] == 'nayta_laskutusraportti') {

  $pdf_data = unserialize(base64_decode($_POST['parametrit']));

  $sessio = $_POST['session'];
  $logo_url = $_POST['logo_url'];
  $logo_info = pdf_logo($logo_url, $sessio);

  $pdf_data['logodata'] = $logo_info['logodata'];
  $pdf_data['scale'] = $logo_info['scale'];

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

require "inc/parametrit.inc";

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

if (isset($task) and $task == 'suorita_hylky') {

  $parametrit = hylky_lusaus_parametrit($sarjanumero);
  $parametrit['laji'] = 'hylky';

  $sanoma = laadi_edifact_sanoma($parametrit);

  $query = "UPDATE sarjanumeroseuranta SET
            lisatieto = 'Hyl�tty'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND sarjanumero = '{$sarjanumero}'";
  pupe_query($query);

  if (laheta_sanoma($sanoma)) {
    $viesti = "UIB: {$sarjanumero} merkitty hyl�tyksi.";
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
    $errors[$sarjanumero] = t("Sy�t� uusi paino!");
    $task = 'lusaus';
  }
  elseif (!is_numeric($uusi_paino)) {
    $errors[$sarjanumero] = t("Sy�tetty arvo ei ole kelvollinen!");
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
      $viesti = "UIB: {$sarjanumero} uudeksi painoksi on p�ivitetty $uusi_paino kg.";
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
    $errors['konttinumero'] = t("Sy�t� konttinumero!");
  }

  if (empty($sinettinumero)) {
    $errors['sinettinumero'] = t("Sy�t� sinettinumero!");
  }

  if (empty($taara)) {
    $errors['taara'] = t("Sy�t� taarapaino!");
  }

  if (!is_numeric($taara)) {
    $errors['taara'] = t("Ep�kelpo taarapaino!");
  }

  if (empty($isokoodi)) {
    $errors['isokoodi'] = t("Sy�t� ISO-koodi!");
  }

  if (strlen($konttinumero) > 17) {
    $errors['konttinumero'] = t("Konttinumero saa olla korkeintaan 17 merkki� pitk�.");
  }

  if (strlen($sinettinumero) > 10) {
    $errors['sinettinumero'] = t("Sinettinumero saa olla korkeintaan 10 merkki� pitk�.");
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
    echo "L�hetys ep�onnistui!";
  }

  unset($task);
}

if (!isset($task)) {

  $konttiviite_kasitelty = array();

  echo "<font class='head'>".t("Toimitusten seuranta")."</font><hr><br>";

  if (!isset($rajaus)) {
    $rajaus = 'aktiiviset';
  }

  $disable1 = $disable2 = $disable3 = '';

  switch ($rajaus) {
  case 'kaikki':
    $rajauslisa = '';
    $disable1 = 'disabled';
    break;

  case 'aktiiviset':
    $rajauslisa = " AND lasku.alatila != 'D' ";
    $disable2 = 'disabled';
    break;

  case 'toimitetut':
    $rajauslisa = " AND lasku.alatila = 'D' ";
    $disable3 = 'disabled';
    break;

  default:
    $rajauslisa = '';
    break;
  }

  echo t("N�yt�");
  echo "&nbsp;";
  echo "<form method='post'>";
  echo "<input type='hidden' name='rajaus' value='aktiiviset' />";
  echo "<input type='submit' {$disable2} value='" .t("Aktiiviset") ."'>";
  echo "</form>";
  echo "&nbsp;";
  echo "<form method='post'>";
  echo "<input type='hidden' name='rajaus' value='toimitetut' />";
  echo "<input type='submit' {$disable3} value='" .t("Toimitetut") ."'>";
  echo "</form>";
  echo "&nbsp;";
  echo "<form method='post'>";
  echo "<input type='hidden' name='rajaus' value='kaikki' />";
  echo "<input type='submit' {$disable1} value='" .t("Kaikki") ."'>";
  echo "</form><br><br>";

  $query = "SELECT lasku.asiakkaan_tilausnumero,
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
            COUNT(tilausrivi.tunnus) AS rullat,
            SUM(IF(tilausrivi.var = 'P', 1, 0)) AS tulouttamatta,
            SUM(IF(tilausrivi.keratty = '', 1, 0)) AS kontittamatta,
            SUM(IF(tilausrivi.toimitettu = '', 1, 0)) AS toimittamatta,
            SUM(IF(trlt.kontin_mrn = '', 1, 0)) AS mrn_vastaanottamatta,
            SUM(IF(ss.lisatieto = 'Hyl�tt�v�', 1, 0)) AS hylattavat,
            SUM(IF(ss.lisatieto = 'Hyl�tty', 1, 0)) AS hylatyt,
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
              AND tilausrivi.tyyppi = 'L'
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
    echo "<th>".t("L�ht�p�iv�")."</th>";
    echo "<th>".t("Konttiviite")."</th>";
    echo "<th>".t("Rullien m��r�")."</th>";
    echo "<th>".t("Tapahtumat")."</th>";
    echo "<th>".t("Kontit")."</th>";
    echo "<th class='back'></th>";
    echo "</tr>";

    foreach ($tilaukset as $key => $tilaus) {

      $poikkeukset = array();

      $viitelasku = array_count_values($viitteet);
      $tilauksia_viitteella = $viitelasku[$tilaus['konttiviite']];

      $id = md5($tilaus['konttiviite']);

      if (in_array($tilaus['konttiviite'], $kasitellyt_konttivitteet)) {
        $konttiviite_kasitelty = true;
      }
      else{
       $konttiviite_kasitelty = false;
      }

      $kontit_sinetointivalmiit = false;

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

        $kasitellyt_konttivitteet[] = $tilaus['konttiviite'];
      }

      if (!$konttiviite_kasitelty) {

        echo "<td valign='top' rowspan='{$tilauksia_viitteella}'>";
        echo date("j.n.Y", strtotime($tilaus['toimaika']));
        echo "</td>";

      }

      if (!$konttiviite_kasitelty) {

        echo "<td valign='top' rowspan='{$tilauksia_viitteella}'>";
        echo $tilaus['konttiviite'];
        echo "</td>";

      }

      if ($tilaus['rullat'] == 0) {
        $rullamaara = $tilaus['rullamaara'] . " (" . t("Ennakkoarvio") . ")";
      }
      else {

        $rullamaara = $tilaus['rullat'];

        $poikkeukset = array(
          'odottaa hylk�yst�' => $tilaus['hylattavat'],
          'hylatty' => $tilaus['hylatyt'],
          'odottaa lusausta' => $tilaus['lusattavat'],
          'lusattu' => $tilaus['lusatut'],
          'ylij��m�' => $tilaus['ylijaama'],
          'siirretty' => $tilaus['siirretty'],
          );

        $query = "SELECT tilausrivi.toimitettu, trlt.rahtikirja_id
                  FROM tilausrivi
                  JOIN tilausrivin_lisatiedot AS trlt
                    ON trlt.yhtio = tilausrivi.yhtio
                    AND trlt.tilausrivitunnus = tilausrivi.tunnus
                  WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                  AND tilausrivi.tyyppi = 'O'
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

          $query = "SELECT group_concat(otunnus)
                    FROM laskun_lisatiedot
                    WHERE yhtio = '{$yhtiorow['yhtio']}'
                    AND konttiviite = '{$tilaus['konttiviite']}'";
          $result = pupe_query($query);
          $konttiviitteen_alaiset_tilaukset = mysql_result($result, 0);

          $tapahtumat .= "&bull; " .  t("Rullat viety varastoon") . "<br>";

          if (($tilaus['kontittamatta'] - $tilaus['ylijaama'] - $tilaus['hylatyt']) == 0) {
            $tapahtumat .= "&bull; " .  t("Rullat kontitettu") . "<br>";

            $query = "SELECT count(tilausrivi.tunnus) AS riveja
                      FROM tilausrivi
                      JOIN sarjanumeroseuranta AS ss
                        ON ss.yhtio = tilausrivi.yhtio
                        AND ss.myyntirivitunnus = tilausrivi.tunnus
                      WHERE tilausrivi.yhtio = '{$yhtiorow['yhtio']}'
                      AND tilausrivi.otunnus IN ({$konttiviitteen_alaiset_tilaukset})
                      AND tilausrivi.keratty != ''
                      AND (ss.lisatieto IS NULL OR ss.lisatieto = 'Lusaus')";
            $result = pupe_query($query);
            $konttiviitteesta_kontittamatta = mysql_result($result, 0);

            if ($konttiviitteesta_kontittamatta != 0) {
              $kontit_sinetointivalmiit = true;
            }

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
            $tapahtumat .= "&bull; " .  t("Kontit sinet�ity") . "<br>";
          }
          elseif ($tilaus['toimittamatta'] < $tilaus['rullat']) {
            $tapahtumat .= "&bull; " .  t("Osa konteista sinet�ity") . "<br>";
          }

          $mrn_tullut = false;

         if (($tilaus['mrn_vastaanottamatta'] - $tilaus['ylijaama'] - $tilaus['hylatyt']) == 0) {
            $tapahtumat .= "&bull; " .  t("MRN-numerot vastaanotettu") . "<br>";
            $mrn_tullut = true;
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
      echo $rullamaara . " kpl.";

      if (array_sum($poikkeukset) > 0) {
        echo "<br>Joista:";
        echo "<div style='text-align:left'>";

        foreach ($poikkeukset as $poikkeus => $kpl) {
          if ($kpl > 0) {

            switch ($poikkeus) {
              case 'odottaa hylk�yst�':

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

      echo "<td valign='top'>";
      echo $tapahtumat;
      echo "</td>";

      if ($konttiviite_kasitelty) {
        //echo "<td valign='top' align='center'>";
        //echo t("Sama konttiviite kuin yll�.");
        //echo "</td>";
      }
      elseif (!$kontit_sinetointivalmiit) {
        echo "<td valign='top' rowspan='{$tilauksia_viitteella}' align='center'>";
        echo $tilaus['konttimaara'] . " kpl (ennakkoarvio)";
        echo "</td>";
      }
      else {
        echo "<td valign='top' rowspan='{$tilauksia_viitteella}' align='right'>";

        $kontit = kontitustiedot($tilaus['konttiviite']);

        $kesken = 0;

        foreach ($kontit as $konttinumero => $kontti) {

          if ($kontti['konttinumero'] == '') {

            echo $kontti['kpl'], " rullaa kontittamatta<hr>";
            $kesken++;
            continue;
          }

          $temp_array = explode("/", $konttinumero);
          $_konttinumero = $temp_array[0];

          echo "<div style='margin:0 5px 8px 5px; padding:5px; border-bottom:1px solid grey;'>";
          echo "{$_konttinumero}. ({$kontti['kpl']} kpl, {$kontti['paino']} kg)&nbsp;&nbsp;";

          if ($kontti['sinettinumero'] == '') {
            echo t("Kontitusta ei ole viel� vahvistettu"), '<br>';
            $kesken++;
          }
          elseif ($kontti['sinettinumero'] == 'X') {
            echo "<form method='post'>";
            echo "<input type='hidden' name='task' value='anna_konttitiedot' />";
            echo "<input type='hidden' name='temp_konttinumero' value='{$konttinumero}' />";
            echo "<input type='hidden' name='paino' value='{$kontti['paino']}' />";
            echo "<input type='hidden' name='rullia' value='{$kontti['kpl']}' />";
            echo "<input type='hidden' name='sinetoitava_konttiviite' value='{$tilaus['konttiviite']}' />";
            echo "<input type='submit' value='". t("Sinet�i") ."' />";
            echo "</form>";
            $kesken++;
          }
          else {
            echo "<button type='button' disabled>" . t("Sinet�ity") . "</button>";
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

            if ($kontti['mrn'] != '') {
              echo "<div style='text-align:center; margin:6px 0'>MRN: ";
              echo "<input type='text'  value='{$kontti['mrn']}' readonly>";
              echo "</div>";

            }
          }

          echo "</div>";
        }

        if ($kesken == 0 and $mrn_tullut and $tilaus['satamavahvistus_pvm'] != '0000-00-00 00:00:00') {

          echo "
          <div style='text-align:center;margin:10px 0;'>
            <button type='button' disabled>" . t("Satamavahvistus l�hetetty") . "</button>";

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
            </form>";
        }

        if ($kesken == 0 and $mrn_tullut) {

          $parametrit = lahtoilmoitus_parametrit($tilaus['konttiviite']);
          $parametrit = serialize($parametrit);
          $parametrit = base64_encode($parametrit);

          $session = mysql_real_escape_string($_COOKIE["pupesoft_session"]);
          $logo_url = $palvelin2."view.php?id=".$yhtiorow["logo"];

          echo "
          <form method='post' id='nayta_lahtoilmoitus{$id}'>
          <input type='hidden' name='parametrit' value='{$parametrit}' />
          <input type='hidden' name='task' value='nayta_lahtoilmoitus' />
          <input type='hidden' name='session' value='{$session}' />
          <input type='hidden' name='logo_url' value='{$logo_url}' />
          <input type='hidden' name='tee' value='XXX' />
          </form>
          <button onClick=\"js_openFormInNewWindow('nayta_lahtoilmoitus{$id}',
           'Satamavahvistus'); return false;\" />";

          echo t("N�yt� l�ht�ilmoitus");
          echo "</button></div>";

          if ($tilaus['satamavahvistus_pvm'] != '0000-00-00 00:00:00') {

            $parametrit = laskutusraportti_parametrit($tilaus['konttiviite']);
            $parametrit = serialize($parametrit);
            $parametrit = base64_encode($parametrit);

            echo "
            <div style='text-align:center'>
            <form method='post' id='nayta_laskutusraportti{$id}'>
            <input type='hidden' name='parametrit' value='{$parametrit}' />
            <input type='hidden' name='task' value='nayta_laskutusraportti' />
            <input type='hidden' name='session' value='{$session}' />
            <input type='hidden' name='logo_url' value='{$logo_url}' />
            <input type='hidden' name='tee' value='XXX' />
            </form>
            <button onClick=\"js_openFormInNewWindow('nayta_laskutusraportti{$id}',
             'Satamavahvistus'); return false;\" />";

            echo t("Laskutusraportti");
            echo "</button></div>";
          }

          echo "</div>";
        }

        echo "</td>";
        $kasitellyt_konttivitteet[] = $tilaus['konttiviite'];
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

  echo "<a href='toimitusten_seuranta.php'>� " . t("Palaa toimitusten seurantaan") . "</a><br><br>";
  echo "<font class='head'>".t("Kontin sinet�inti")."</font></a><hr><br>";

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
    <th>" . t("Rullien m��r�") ."</th>
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
    <td align='right'><input type='submit' value='". t("Sinet�i") ."' /></td>
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

  echo "<a href='toimitusten_seuranta.php'>� " . t("Palaa toimitusten seurantaan") . "</a><br><br>";
  echo "<font class='head'>".t("Satamavahvistus")."</font><hr><br>";
  echo "
    <form method='post'>
    <input type='hidden' name='konttiviite' value='{$konttiviite}' />
    <table>
    <tr><th>" . t("Matkakoodi") ."</th><td>{$matkakoodi}</td></tr>
    <tr><th>" . t("Konttiviite") ."</th><td>{$konttiviite}</td></tr>
    <tr><th>" . t("L�ht�p�iv�") ."</th><td><input type='text' id='lahtopvm' name='lahtopvm' value='{$lahtopvm_arvio}' /></td></tr>
    <tr><th>" . t("L�ht�aika") ."</th><td>";

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
  <tr><th></th><td align='right'><input type='submit' value='". t("L�het� satamavahvistus") ."' /></td></tr>
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
            AND ss.lisatieto = 'Hyl�tt�v�'";
  $result = pupe_query($query);

  echo "<a href='toimitusten_seuranta.php'>� " . t("Palaa toimitusten seurantaan") . "</a><br><br>";
  echo "<font class='head'>".t("Rullien hylk��minen")."</font></a><hr><br>";

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
    <tr><th></th><td align='right'><input type='submit' value='". t("Vahvista hylk�ys") ."' /></td><td class='back'></td></tr>
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

  echo "<a href='toimitusten_seuranta.php'>� " . t("Palaa toimitusten seurantaan") . "</a><br><br>";
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

require "inc/footer.inc";
