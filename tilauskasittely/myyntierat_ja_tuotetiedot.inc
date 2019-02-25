<?php

if ($yhtiorow['tilauksen_myyntieratiedot'] == '') die(t("Yhtiˆn parametrit - Tilauksen_myyntier‰tiedot ei ole aktiivinen"));

if (isset($_COOKIE['avainsanalaji_keksi'])) $lajit = unserialize(urldecode($_COOKIE['avainsanalaji_keksi']));

if (isset($_POST['piirtele_valikko'])) {
  echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
        $(function() {
          $('.check_all').on('click', function() {
            var id = $(this).val();

            if ($(this).is(':checked')) {
              $('.'+id).attr('checked', true);
            }
            else {
              $('.'+id).attr('checked', false);
            }
          });
        });
      </script>";

  // piirrell‰‰n tuotteen_avainsanat-valikko
  echo "<br><br>";

  $query = "SELECT laji, selite, selitetark
            FROM avainsana
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND laji    in ('PARAMETRI', 'LISATIETO')
            ORDER BY laji, selite";
  $result = pupe_query($query);

  echo "<form>";
  echo "<table width='600'>";
  echo "<tr><th>".t("Valitse avainsanat sek‰ lis‰kielet")."<br><br><input type='checkbox' class='check_all' value='lajiboksit'>".t("Valitse Kaikki")."</th><td>";

  $query = "SELECT DISTINCT kieli
            FROM tuotteen_avainsanat
            WHERE yhtio='{$yhtiorow['yhtio']}'
            AND kieli != '{$yhtiorow['kieli']}'";
  $ressu = pupe_query($query);

  echo "<input type='checkbox' name='kielet[]' value='{$row['kieli']}' checked disabled> ".strtoupper($yhtiorow['kieli'])."<br>";
  while ($row = mysql_fetch_assoc($ressu)) {
    echo "<input type='checkbox' name='kielet[]' value='{$row['kieli']}'> ".strtoupper($row['kieli'])."<br>";
  }
  echo "</td></tr>";

  $secretcounter = 0;
  $eka_ajo = true;

  while ($row = mysql_fetch_assoc($result)) {
    $nollaus = false;
    if ($secretcounter == 0) echo "<tr>";
    $tsekk = "";

    foreach ($lajit as $osuma) {
      if ($osuma == $row['selite']) {
        $tsekk = "CHECKED";
        break;
      }
    }

    echo "<td align='left' valign='top' nowrap><input type='checkbox' class='lajiboksit' name='lajit[]' value='{$row['laji']}_{$row['selite']}' $tsekk>{$row['laji']} {$row['selitetark']}</td>";

    if ($secretcounter == 2) {
      echo "</tr>";
      $secretcounter = 0;
      $nollaus = true;
    }

    $eka_ajo = false;
    if (!$nollaus) $secretcounter++;
  }

  echo "<br><br>";
  echo "  <tr>
          <td align='left' class='back' valign='top'>
        <form name='aja_ja_tallenna' method='post'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='otunnus' value='$tilausnumero'>
        <input type='hidden' name='tilausnumero' value='$tilausnumero'>
        <input type='hidden' name='mista' value = '$mista'>
        <input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='naantali' value='KIVAPAIKKA'>
        <input type='submit' name='aja_ja_tallenna' value='".t("Valmis")."'>
        </form>
        </td>
      </tr>";
  echo "</table>";
  echo "</form>";
}
elseif (isset($lajit) and count($lajit) > 0) {
  // t‰‰ll‰ ajellaan rapsa ja tallennetaan henkseliin

  include 'inc/pupeExcel.inc';

  $worksheet    = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi    = 0;
  $excelsarake = 0;

  //piirret‰‰n defaulttisarakkeiden headerit
  // Tuote
  $worksheet->write($excelrivi, $excelsarake++, t("Tuotenumero"),       $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Ean-koodi"),         $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Nimitys"),           $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Tuotemerkki"),       $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("M‰‰r‰"),             $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Myyntihinta"),       $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Myym‰l‰hinta"),      $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Alennusprosentti"),  $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Alennettu hinta"),   $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Alv"),               $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Myyntier‰"),         $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Yksikkˆ"),           $format_bold);
  // Asiakas
  $worksheet->write($excelrivi, $excelsarake++, t("Asiakashinta"),        $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Asiakkaan valuutta"),  $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Asiakkaan kommentti"), $format_bold);
  // Tuote jatkuu
  $worksheet->write($excelrivi, $excelsarake++, t("Tullinimike"),   $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Lyhytkuvaus"),   $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Kuvaus"),        $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Tuotesyvyys"),   $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Tuoteleveys"),   $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Tuotekorkeus"),  $format_bold);
  $worksheet->write($excelrivi, $excelsarake++, t("Tuotemassa"),    $format_bold);
  // Tuotteen toimittajat
  $worksheet->write($excelrivi, $excelsarake++, t("Alkuper‰maa"),   $format_bold);

  $excelrivi++;

  if (!isset($kielet)) $kielet = array();
  array_unshift($kielet, $yhtiorow['kieli']);

  mysql_data_seek($tilausrivit_talteen, 0);
  $rows = array();

  while ($row = mysql_fetch_array($tilausrivit_talteen)) {
    $rows[] = $row;
  }

  $eka_tuote = TRUE;

  // Haetaan asiakkaan tiedot
  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$laskurow['liitostunnus']}'";
  $asiakasressi = pupe_query($query);
  $asiakasrowi = mysql_fetch_assoc($asiakasressi);

  //loopataan myyntier‰tiedot per tilausrivi
  foreach ($rows as $tilausrivi) {
    // Haetaan tuotteen tiedot
    $query = "SELECT *
              FROM tuote
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tilausrivi['tuoteno']}'";
    $tuoteressi = pupe_query($query);
    $tuoterowi = mysql_fetch_assoc($tuoteressi);

    // Haetaan kaikki tuotteen alkuper‰maat
    $query = "SELECT group_concat(DISTINCT alkuperamaa) kaikkimaat
              FROM tuotteen_toimittajat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tilausrivi['tuoteno']}'";
    $toimittajaressi = pupe_query($query);
    $alkuperamaat = mysql_fetch_assoc($toimittajaressi);

    // Haetaan asiakkaan tuotenumero
    $query = "SELECT *
              FROM asiakaskommentti
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tilausrivi['tuoteno']}'
              AND tyyppi  = ''";
    $spessuressi = pupe_query($query);
    $spessurowi = mysql_fetch_assoc($spessuressi);

    $excelsarake = 0;

    //tuoteno
    $worksheet->write($excelrivi, $excelsarake++, $tuoterowi['tuoteno']);
    // ean
    $worksheet->write($excelrivi, $excelsarake++, $tuoterowi['eankoodi']);
    // nimitys
    $worksheet->write($excelrivi, $excelsarake++, $tuoterowi['nimitys']);
    // tuotemerkki
    $worksheet->write($excelrivi, $excelsarake++, $tuoterowi['tuotemerkki']);
    // tilausrivin kpl
    if (in_array($tilausrivi["var"], array('S', 'U', 'T', 'R', 'J'))) {
      if ($yhtiorow["varaako_jt_saldoa"] == "") {
        $kpl_ruudulle = $tilausrivi['jt'] * 1;
      }
      else {
        $kpl_ruudulle = ($tilausrivi['jt']+$tilausrivi['varattu']) * 1;
      }
    }
    elseif ($tilausrivi["var"] == 'P' or ($kukarow['extranet'] != '' and $tilausrivi['positio'] == 'Ei varaa saldoa')) {
      $kpl_ruudulle = $tilausrivi['tilkpl'] * 1;
    }
    else {
      $kpl_ruudulle = $tilausrivi['varattu'] * 1;
    }
    $worksheet->write($excelrivi, $excelsarake++, $kpl_ruudulle);
    // myyntihinta
    $worksheet->write($excelrivi, $excelsarake++, str_replace(".", ",", hintapyoristys($tuoterowi['myyntihinta'])));
    // myym‰l‰hinta
    $worksheet->write($excelrivi, $excelsarake++, str_replace(".", ",", hintapyoristys($tuoterowi['myymalahinta'])));
    // ale1
    $worksheet->write($excelrivi, $excelsarake++, str_replace(".", ",", $tilausrivi['ale1']));
    // alennettu hinta
    $alehinta = $tilausrivi['hinta'] * (1 - $tilausrivi['ale1'] / 100);
    $worksheet->write($excelrivi, $excelsarake++, str_replace(".", ",", hintapyoristys($alehinta)));
    // alv
    $worksheet->write($excelrivi, $excelsarake++, str_replace(".", ",", $tuoterowi['alv']));
    // myyntier‰
    $worksheet->write($excelrivi, $excelsarake++, str_replace(".", ",", $tuoterowi['myynti_era']));
    // yksikkˆ
    $worksheet->write($excelrivi, $excelsarake++, $tuoterowi['yksikko']);

    // asiakashinta
    $worksheet->write($excelrivi, $excelsarake++, str_replace(".", ",", hintapyoristys($tilausrivi['hinta'])));
    // asiakkaan valuutta
    $worksheet->write($excelrivi, $excelsarake++, $laskurow['valkoodi']);
    // asiakkaan tilausnro
    $worksheet->write($excelrivi, $excelsarake++, $spessurowi['kommentti']);

    // tullinimike1
    $worksheet->write($excelrivi, $excelsarake++, $tuoterowi['tullinimike1']);
    // lyhytkuvaus
    $worksheet->write($excelrivi, $excelsarake++, $tuoterowi['lyhytkuvaus']);
    // kuvaus
    $worksheet->write($excelrivi, $excelsarake++, $tuoterowi['kuvaus']);
    // tuotesyvyys
    $worksheet->write($excelrivi, $excelsarake++, str_replace(".", ",", $tuoterowi['tuotesyvyys']));
    // tuoteleveys
    $worksheet->write($excelrivi, $excelsarake++, str_replace(".", ",", $tuoterowi['tuoteleveys']));
    // tuotekorkeus
    $worksheet->write($excelrivi, $excelsarake++, str_replace(".", ",", $tuoterowi['tuotekorkeus']));
    // tuotemassa
    $worksheet->write($excelrivi, $excelsarake++, str_replace(".", ",", $tuoterowi['tuotemassa']));

    //alkuper‰maa/t
    $worksheet->write($excelrivi, $excelsarake++, $alkuperamaat['kaikkimaat']);

    foreach ($lajit as $asanalaji) {

      // siivotaan datasta ylim‰‰r‰inen "parametri_" mik‰li
      // avainsanoissa selitteess‰ on annettu ylim‰‰r‰inen "parametri_"
      $asanalaji = strtolower($asanalaji);
      $pos = strpos($asanalaji, "_");
      $_laji = substr($asanalaji, 0, $pos);
      $asanalaji2 = "";

      // jos kaksi per‰kk‰ist‰ "parametri_" niin siivotaan toinen pois
      if (substr($asanalaji, $pos + 1, strlen($_laji)) == $_laji) {
        $asanalaji2 = substr($asanalaji, $pos + 1);
      }

      foreach ($kielet as $kieli) {
        // etsit‰‰n tuotteen avainsanat per valittu laji ja kieli
        $query = "SELECT *
                  FROM tuotteen_avainsanat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$tilausrivi['tuoteno']}'
                  AND laji    IN ('{$asanalaji}', '{$asanalaji2}')
                  AND kieli   = '{$kieli}'";
        $result = pupe_query($query);

        //laitetaan uusi headeri per valittu kieli vain ekalla rivill‰
        if ($eka_tuote) $worksheet->write(0, $excelsarake, !empty($asanalaji2) ? $asanalaji2 : $asanalaji." ".strtoupper($kieli), $format_bold);

        $asanase = mysql_fetch_assoc($result);
        if (is_numeric($asanase['selite'])) $asanase['selite'] = str_replace(".", ",", $asanase['selite']);
        $worksheet->write($excelrivi, $excelsarake++, $asanase['selite']);
      }
    }

    $eka_tuote = FALSE;

    $excelrivi++;
  }

  $excelnimi = $worksheet->close();

  if (isset($excelnimi)) {
    echo "<br><br><table>";
    echo "<tr><th>", t("Tallenna tulos"), ":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tappi' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Tuotetiedot.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='{$excelnimi}'>";
    echo "<td class='back'><input type='submit' value='", t("Tallenna"), "'></td></tr></form>";
    echo "</table><br>";
  }
  else {
    echo t("Tallennus ep‰onnistui")."!<br>";
  }
}
