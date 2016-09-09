<?php

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

// Ei k‰ytet‰ pakkausta
$compression = FALSE;

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

require "../inc/parametrit.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}
else {

  echo "<font class='head'>", t("Hinnoittelupoikkeamat-raportti"), "</font><hr />";

  $app = !isset($app) ? 1 : (int) $app;
  $akk = !isset($akk) ? (int) date("m", mktime(0, 0, 0, date("m"), 0, date("Y"))) : (int) $akk;
  $avv = !isset($avv) ? (int) date("Y", mktime(0, 0, 0, date("m"), 0, date("Y"))) : (int) $avv;

  $lpp = !isset($lpp) ? (int) date("d", mktime(0, 0, 0, date("m"), 0, date("Y"))) : (int) $lpp;
  $lkk = !isset($lkk) ? (int) date("m", mktime(0, 0, 0, date("m"), 0, date("Y"))) : (int) $lkk;
  $lvv = !isset($lvv) ? (int) date("Y", mktime(0, 0, 0, date("m"), 0, date("Y"))) : (int) $lvv;

  if (!isset($myyja)) $myyja = 0;
  if (!isset($tee)) $tee = '';
  if (!isset($eropros_vahintaan)) $eropros_vahintaan = 3;

  // Tarkistetaan viel‰ p‰iv‰m‰‰r‰t
  if (!checkdate($akk, $app, $avv)) {
    echo "<font class='error'>", t("VIRHE: Alkup‰iv‰m‰‰r‰ on virheellinen"), "!</font><br />";
    $tee = "";
  }

  if (!checkdate($lkk, $lpp, $lvv)) {
    echo "<font class='error'>", t("VIRHE: Loppup‰iv‰m‰‰r‰ on virheellinen"), "!</font><br />";
    $tee = "";
  }

  if ($tee != "" and strtotime("{$avv}-{$akk}-{$app}") > strtotime("{$lvv}-{$lkk}-{$lpp}")) {
    echo "<font class='error'>", t("VIRHE: Alkup‰iv‰m‰‰r‰ on suurempi kuin loppup‰iv‰m‰‰r‰"), "!</font><br />";
    $tee = "";
  }

  echo "<form method='post' action=''>";
  echo "<table>";

  echo "<tr>";
  echo "<th>", t("Alkup‰iv‰m‰‰r‰"), "</th>";
  echo "<td><input type='text' name='app' value='{$app}' size='5' />";
  echo "<input type='text' name='akk' value='{$akk}' size='5' />";
  echo "<input type='text' name='avv' value='{$avv}' size='5' /></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>", t("Loppup‰iv‰m‰‰r‰"), "</th>";
  echo "<td><input type='text' name='lpp' value='{$lpp}' size='5' />";
  echo "<input type='text' name='lkk' value='{$lkk}' size='5' />";
  echo "<input type='text' name='lvv' value='{$lvv}' size='5' /></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>", t("Myyj‰")."</th>";
  echo "<td><select name='myyja'>";
  echo "<option value='0'>", t("Valitse"), "</option>";

  $query = "SELECT tunnus, kuka, nimi, myyja
            FROM kuka
            WHERE yhtio   = '{$kukarow['yhtio']}'
            AND extranet  = ''
            AND nimi     != ''
            AND myyja    != ''
            ORDER BY nimi";
  $myyjares = pupe_query($query);

  while ($myyjarow = mysql_fetch_assoc($myyjares)) {

    $sel = $myyjarow['tunnus'] == $myyja ? " selected" : "";

    echo "<option value='{$myyjarow['tunnus']}'{$sel}>{$myyjarow['nimi']}</option>";
  }

  echo "</select></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>", t("Ero % v‰hint‰‰n"), "</th>";
  echo "<td style='vertical-align: middle;'><input type='text' name='eropros_vahintaan' value='{$eropros_vahintaan}' maxlength='6' size='7' /> %</td>";
  echo "</tr>";

  echo "<tr><td class='back' colspan='2'>";
  echo "<input type='hidden' name='tee' value='hae' />";
  echo "<input type='submit' value='", t("Hae"), "' />";
  echo "</td></tr>";

  echo "</table>";
  echo "</form>";

  if ($tee == 'hae') {

    $myyja = (int) $myyja;
    $eropros_vahintaan = (float) str_replace(",", ".", $eropros_vahintaan);

    $myyjalisa = $myyja != 0 ? "AND lasku.myyja = '{$myyja}'" : "";

    // Haetaan laskutetut tilaukset
    $query = "SELECT lasku.*, IFNULL(kuka.nimi, '".t("Ei myyj‰‰")."') AS myyja, TRIM(CONCAT(lasku.ytunnus, ' ', lasku.nimi, ' ', lasku.nimitark)) AS nimi
              FROM lasku
              LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.tunnus = lasku.myyja)
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.tila    = 'L'
              AND lasku.alatila = 'X'
              AND lasku.tapvm   >= '{$avv}-{$akk}-{$app}'
              AND lasku.tapvm   <= '{$lvv}-{$lkk}-{$lpp}'
              {$myyjalisa}
              ORDER BY lasku.myyja, lasku.tapvm, lasku.tunnus";
    $laskures = pupe_query($query);

    if (mysql_num_rows($laskures) == 0) {

      echo "<br /><font class='info'>", t("Yht‰‰n tilausta ei lˆytynyt"), "!</font><br />";

      require "inc/footer.inc";
      exit;
    }

    flush();

    require 'inc/ProgressBar.class.php';

    include 'inc/pupeExcel.inc';

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);

    $excelrivi    = 0;
    $excelsarake = 0;

    $data = array();

    $i = 0;

    $tuotteiden_alehinnat = array();

    $bar = new ProgressBar();
    $bar->initialize(mysql_num_rows($laskures));

    while ($laskurow = mysql_fetch_assoc($laskures)) {

      $bar->increase();

      $x = 1;

      $rahtinro_tuoteno_lisa = "'{$yhtiorow['rahti_tuotenumero']}'";
      $rahtinro_tuoteno_lisa = lisaa_vaihtoehtoinen_rahti_merkkijonoon($rahtinro_tuoteno_lisa);

      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$laskurow['tunnus']}'
                AND tyyppi  = 'L'
                AND kpl+varattu > 0
                AND tuoteno NOT IN (
                  {$rahtinro_tuoteno_lisa},
                  '{$yhtiorow['jalkivaatimus_tuotenumero']}',
                  '{$yhtiorow['erilliskasiteltava_tuotenumero']}',
                  '{$yhtiorow['kasittelykulu_tuotenumero']}',
                  '{$yhtiorow['maksuehto_tuotenumero']}',
                  '{$yhtiorow['ennakkomaksu_tuotenumero']}',
                  '{$yhtiorow['alennus_tuotenumero']}',
                  '{$yhtiorow['laskutuslisa_tuotenumero']}',
                  '{$yhtiorow['kuljetusvakuutus_tuotenumero']}'
                )";
      $tilausrivires = pupe_query($query);

      $num_rows = mysql_num_rows($tilausrivires);

      while ($tilausrivirow = mysql_fetch_assoc($tilausrivires)) {

        $alet = generoi_alekentta_php($tilausrivirow, 'M', 'kerto');

        $tilausrivirow['hinta'] = $tilausrivirow['hinta'] * $alet;

        $_chk = $laskurow['liitostunnus'].'####'.$tilausrivirow['tuoteno'].'####'.$tilausrivirow['kpl'];

        if (!isset($tuotteiden_alehinnat[$_chk])) {
          $query = "SELECT *
                    FROM tuote
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tuoteno = '{$tilausrivirow['tuoteno']}'";
          $tres = pupe_query($query);
          $trow = mysql_fetch_assoc($tres);

          list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, $tilausrivirow['kpl'], '', '', array());

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            $lis_hinta *= (1 - $lis_ale_kaikki['ale'.$alepostfix] / 100);
          }

          $tuotteiden_alehinnat[$_chk] = $lis_hinta;
        }
        else {
          $lis_hinta = $tuotteiden_alehinnat[$_chk];
        }

        $ero = $tilausrivirow['hinta'] - $lis_hinta;

        if ($ero >= 0) continue;

        $eropros = $tilausrivirow['hinta'] == 0 ? 100 : abs(round((($ero) / $tilausrivirow['hinta']) * 100, 2));

        if ($eropros_vahintaan > $eropros) continue;

        $data[$i]['myyj‰'] = $laskurow['myyja'];
        $data[$i]['tilaus'] = $laskurow['tunnus'];
        $data[$i]['rivej‰'] = $num_rows;
        $data[$i]['asiakas'] = $laskurow['nimi'];
        $data[$i]['sis‰inen_kommentti'] = $laskurow['sisviesti3'];

        $lis_hinta = hintapyoristys($lis_hinta);
        $tilausrivirow['hinta'] = hintapyoristys($tilausrivirow['hinta']);

        $data[$i]['tuoteno']     = $tilausrivirow['tuoteno'];
        $data[$i]['nimitys']     = $tilausrivirow['nimitys'];
        $data[$i]['kpl']       = sprintf("%.2f", $tilausrivirow['kpl']);
        $data[$i]['koneen_hinta']   = sprintf("%.2f", $lis_hinta);
        $data[$i]['hinta']       = sprintf("%.2f", $tilausrivirow['hinta']);
        $data[$i]['eropros']     = sprintf("%.2f", $eropros);
        $data[$i]['ero']       = sprintf("%.2f", round($ero * $tilausrivirow['kpl'], 2));
        $data[$i]['kate']       = sprintf("%.2f", round(100*$tilausrivirow['kate']/$tilausrivirow['rivihinta'], 2));

        $i++;
        $x++;
      }
    }

    if (count($data) > 0) {

      flush();

      echo "<br /><br /><table><tr>";

      $otsikot = "";

      foreach (array_keys($data[0]) as $key) {

        switch ($key) {
        case 'eropros':
          $otsikko = "ero %";
          break;
        case 'sis‰inen_kommentti':
          $otsikko = "sis‰inen kommentti";
          break;
        case 'koneen_hinta':
          $otsikko = "koneen hinta";
          break;
        case 'kate':
          $otsikko = "Kate %";
          break;
        default:
          $otsikko = $key;
        }

        $otsikko = ucfirst(t($otsikko));

        $worksheet->writeString($excelrivi, $excelsarake, $otsikko, $format_bold);
        $excelsarake++;

        $otsikot .= "<th>{$otsikko}</th>";
      }

      echo $otsikot;

      echo "</tr>";

      $excelsarake = 0;
      $excelrivi++;

      $user = '';
      $total_user = 0;
      $total = 0;

      $ed_tilaus = 0;
      $tilaus = 0;
      $odd = '';

      foreach ($data as $set) {

        echo "<tr>";

        if ($set['tilaus'] != $ed_tilaus and $ed_tilaus != 0) {
          $odd = $odd == '' ? 'spec' : '';
        }

        $ed_tilaus = $set['tilaus'];

        foreach ($set as $k => $v) {

          if ($k == 'myyj‰' and $user != '' and $v != '' and $user != $v) {
            echo "<tr>";
            echo "<th>{$user} ", t("Yhteens‰"), "</th>";
            echo "<th colspan='11' style='text-align: right;'>{$total_user}</th>";
            echo "<th></th>";
            echo "</tr>";

            echo "<tr><td class='back' colspan='12'>&nbsp;</tr>";
            echo "<tr>{$otsikot}</tr>";

            $total_user = 0;

            echo "<tr>";
          }

          if ($excelsarake > 6) $worksheet->writeNumber($excelrivi, $excelsarake, $v);
          else $worksheet->write($excelrivi, $excelsarake, $v);

          $excelsarake++;

          $stylelisa = $excelsarake > 7 ? " style='text-align: right;' " : "";

          echo "<td class='{$odd}' {$stylelisa}>{$v}</td>";

          if ($k == 'myyj‰' and $v != '') $user = $v;
          if ($k == 'ero' and $user != '') {
            $total_user += $v;
            $total += $v;
          }
        }

        echo "</tr>";

        $excelsarake = 0;
        $excelrivi++;
      }

      echo "<tr>";
      echo "<th>{$user} ", t("Yhteens‰"), "</th>";
      echo "<th colspan='11' style='text-align: right;'>{$total_user}</th>";
      echo "<th></th>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>", t("Kaikki yhteens‰"), "</th>";
      echo "<th colspan='11' style='text-align: right;'>{$total}</th>";
      echo "<th></th>";
      echo "</tr>";

      echo "</table>";

      $excelnimi = $worksheet->close();

      echo "<br />";
      echo "<form method='post' class='multisubmit'>";
      echo "<table>";
      echo "<tr><th>", t("Tallenna raportti (xlsx)"), ":</th>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='", t("Hinnoittelupoikkeamat-raportti"), ".xlsx'>";
      echo "<input type='hidden' name='tmpfilenimi' value='{$excelnimi}'>";
      echo "<td class='back'><input type='submit' value='", t("Tallenna"), "'></td></tr>";
      echo "</table>";
      echo "</form><br />";

    }
  }

  require "inc/footer.inc";
}
