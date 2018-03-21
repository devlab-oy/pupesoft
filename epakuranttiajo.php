<?php

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {

  if (isset($_POST["tee"])) {
    if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
    if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }

  require "inc/parametrit.inc";

  if (isset($tee) and $tee == "lataa_tiedosto") {
    readfile("/tmp/".$tmpfilenimi);
    exit;
  }

  if (!isset($ajo_tee) or $ajo_tee != "NAYTAPV") {
    echo "<font class='head'>".t("Ep‰kuranttiajo")."</font><hr>";
    echo "<br><form method='post'>";
    echo "<input type = 'hidden' name = 'ajo_tee' value = 'NAYTA'>";
    echo "<input type = 'submit' value = '".t("N‰yt‰ ep‰kurantoitavat tuotteet")."'>";
    echo "</form><br>";
    echo "<br><br><br>";
  }

  echo "<font class='head'>".t("Tutki ep‰kurantoitavia tuotteita")."</font><hr>";
  echo "<br><form method='post'>";
  echo "<input type = 'hidden' name = 'ajo_tee' value = 'NAYTAPV'>";
  echo "<table>";
  echo "<tr><th>".t("P‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppa' value='$ppa' size='3'></td>
    <td><input type='text' name='kka' value='$kka' size='3'></td>
    <td><input type='text' name='vva' value='$vva' size='5'></td>
    </tr><tr>";
  echo "</table><br>";
  echo "<input type = 'submit' value = '".t("N‰yt‰ tuotteet")."'>";
  echo "</form><br><br>";


  $php_cli     = FALSE;
  $kaikkiepakur = "";
}
else {
  if (!isset($argv[1])) {
    die ("Anna yhtio parametriksi!");
  }

  $pupe_root_polku = dirname(__FILE__);

  // Otetaan includepath aina rootista
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  // Otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  // Tehd‰‰n oletukset
  $kukarow['yhtio'] = $argv[1];
  $kukarow['kuka'] = "admin";
  $yhtiorow = hae_yhtion_parametrit($argv[1]);

  $php_cli     = TRUE;
  $kaikkiepakur  = "";

  if (isset($argv[2]) and in_array($argv[2], array("25paalle", "puolipaalle", "75paalle", "paalle"))) {
    $kaikkiepakur = $argv[2];
  }
}

if (isset($ajo_tee) and $ajo_tee == "NAYTAPV") {

  $kka = sprintf("%02d", $kka);
  $ppa = sprintf("%02d", $ppa);

  $syotetty_paiva = (int) date("U", mktime(0, 0, 0, $kka, $ppa, $vva));

  if ($syotetty_paiva <= time()) {
    echo t("VIRHE: Syˆtetty p‰iv‰m‰‰r‰ on oltava tulevaisuudessa")."!";
    $ajo_tee = "";
  }
}

if ($php_cli or (isset($ajo_tee) and ($ajo_tee == "NAYTA" or $ajo_tee == "NAYTAPV" or $ajo_tee == "EPAKURANTOI"))) {

  // Viimeisimm‰st‰ tulosta ja laskutuksesta n‰in monta p‰iv‰‰ HOUM: t‰n voi laittaa myˆs salasanat.php:seen.
  if (!isset($epakurtasot_array)) $epakurtasot_array = array("100%" => 913, "75%" => 820, "50%" => 730, "25%" => 547);

  // T‰m‰ p‰iv‰
  $today = time();

  // Tehd‰‰n kaikki tapahtumat samalle tositteelle!
  $tapahtumat_samalle_tositteelle = "kylla";
  $laskuid = 0;
  $epa_tuotemaara = 0;

  // Haetaan kaikki saldolliset tuotteet
  $query  = "SELECT tuote.tuoteno,
             tuote.nimitys,
             tuote.try,
             tuote.epakurantti25pvm,
             tuote.epakurantti50pvm,
             tuote.epakurantti75pvm,
             tuote.epakurantti100pvm,
             if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) kehahin,
             tuote.kehahin bruttokehahin,
             tuote.luontiaika,
             tuote.vihapvm,
             tuote.sarjanumeroseuranta,
             sum(tuotepaikat.saldo) saldo,
             varastopaikat.epakurantointi
             FROM tuote
             JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio AND tuotepaikat.tuoteno = tuote.tuoteno)
             JOIN varastopaikat ON (varastopaikat.yhtio = tuote.yhtio AND varastopaikat.tunnus = tuotepaikat.varasto)
             WHERE tuote.yhtio             = '$kukarow[yhtio]'
             AND tuote.ei_saldoa           = ''
             AND tuote.epakurantti100pvm   = '0000-00-00'
             AND tuote.sarjanumeroseuranta NOT IN ('S','G')
             AND varastopaikat.epakurantointi = ''
             GROUP BY 1,2,3,4,5,6,7,8,9,10
             HAVING saldo > 0
             ORDER BY tuoteno";
  $epakurantti_result = pupe_query($query);

  include 'inc/pupeExcel.inc';

  $worksheet    = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi    = 0;
  $excelsarake = 0;

  if (!$php_cli) {

    if ($ajo_tee == "NAYTAPV") {
      // Simuloidaan tulevaisuuteen
      $today = $syotetty_paiva;
    }

    echo "<br><table>";
    echo "<tr>";
    echo "<th>".t("Tuote")."</th>";
    echo "<th>".t("Nimitys")."</th>";
    echo "<th>".t("Try")."</th>";
    echo "<th>".t("Viimeisin saapuminen")."</th>";
    echo "<th>".t("Viimeisin laskutus")."</th>";
    echo "<th>".t("Viim. tapahtuma")."</th>";
    echo "<th>".t("Ep‰kurattitaso")."</th>";
    echo "<th>".t("Saldo")."</th>";
    echo "<th>".t("Kehahin")."</th>";
    echo "<th>".t("Varastonarvo")."</th>";
    echo "<th>".t("Uusi varastonarvo")."</th>";

    if (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI") {
      echo "<th></th>";
    }

    echo "</tr>";
  }

  $worksheet->writeString($excelrivi, $excelsarake++, t("Tuote"), $format_bold);
  $worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"), $format_bold);
  $worksheet->writeString($excelrivi, $excelsarake++, t("Try"), $format_bold);
  $worksheet->writeString($excelrivi, $excelsarake++, t("Viimeisin saapuminen"), $format_bold);
  $worksheet->writeString($excelrivi, $excelsarake++, t("Viimeisin laskutus"), $format_bold);
  $worksheet->writeString($excelrivi, $excelsarake++, t("Viim. tapahtuma"), $format_bold);
  $worksheet->writeString($excelrivi, $excelsarake++, t("Ep‰kurattitaso"), $format_bold);
  $worksheet->writeString($excelrivi, $excelsarake++, t("Saldo"), $format_bold);
  $worksheet->writeString($excelrivi, $excelsarake++, t("Kehahin"), $format_bold);
  $worksheet->writeString($excelrivi, $excelsarake++, t("Varastonarvo"), $format_bold);
  $worksheet->writeString($excelrivi, $excelsarake++, t("Uusi varastonarvo"), $format_bold);

  $excelrivi++;
  $excelsarake = 0;

  $vararvot_nyt = 0;
  $vararvot_sit = 0;

  while ($epakurantti_row = mysql_fetch_assoc($epakurantti_result)) {

    if ($php_cli and $kaikkiepakur != "") {
      $tee    = $kaikkiepakur;
      $tuoteno = $epakurantti_row["tuoteno"];

      require "epakurantti.inc";

      echo "Tuotteen $epakurantti_row[tuoteno], laitetaan $tee epakurantiksi. Varastonmuutos $varaston_muutos $yhtiorow[valkoodi].\n";
    }
    else {
      // Otetaan outputti bufferiin
      if ($php_cli or (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI")) {
        ob_start();
      }

      $_vaihda_kehahin_selite = t("Keskihankintahinnan muutos");

      // Haetaan tuotteen viimeisin tulo
      // Eliminoidaan konversioiden alkusaldot ja kehahin muutokset selitteell‰ (epakurantti.inc ~664)
      $query  = "SELECT tapahtuma.laadittu
                 FROM tapahtuma
                 JOIN varastopaikat ON (varastopaikat.yhtio = tapahtuma.yhtio AND varastopaikat.tunnus = tapahtuma.varasto)
                 WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
                 AND tapahtuma.laji    in ('tulo', 'valmistus')
                 AND tapahtuma.tuoteno = '$epakurantti_row[tuoteno]'
                 AND tapahtuma.selite  not like '%alkusaldo%'
                 AND tapahtuma.selite  not like 'Keskihankintahinnan muutos:%'
                 AND tapahtuma.selite  not like '{$_vaihda_kehahin_selite}:%'
                 AND varastopaikat.epakurantointi = ''
                 ORDER BY laadittu DESC
                 LIMIT 1";
      $tapres = pupe_query($query);

      if (!$tulorow = mysql_fetch_assoc($tapres)) {

        $_luontiaika            = substr($epakurantti_row["luontiaika"], 0, 10);
        $_luontiaika_check      = ($_luontiaika != "0000-00-00");
        $_vihapvm_check         = ($epakurantti_row["vihapvm"] != "0000-00-00");
        $_luontiaika_konversio  = ($_luontiaika > $epakurantti_row["vihapvm"]);

        if ($_luontiaika_check and $_vihapvm_check and $_luontiaika_konversio) {
          // Jos ei lˆydy tuloa, laitetaan tuotteen vihapvm
          // (mik‰li se on pienempi kuin luontiaika, konversiotapauksia varten)
          $tulorow = array("laadittu" => $epakurantti_row["vihapvm"]);
        }
        elseif ($_luontiaika_check) {
          // Jos ei lˆydy tuloa, laitetaan tuotteen luontiaika
          $tulorow = array("laadittu" => $_luontiaika);
        }
        else {
          // Jos ei lˆydy tuloa eik‰ tuotteen luontiaikaa, niin laitetaan jotain vanhaa
          $tulorow = array("laadittu" => "1970-01-01");
        }
      }

      // Haetaan tuotteen viimeisin laskutus (ei huomioida hyvityksi‰)
      // Ei myˆsk‰‰n huomioida fuusioissa muodostuneita tapahtumia (rivitunnus 0 tai -1)
      $query  = "SELECT tapahtuma.laadittu
                 FROM tapahtuma
                 JOIN varastopaikat ON (varastopaikat.yhtio = tapahtuma.yhtio AND varastopaikat.tunnus = tapahtuma.varasto)
                 WHERE tapahtuma.yhtio    = '$kukarow[yhtio]'
                 AND tapahtuma.laji       in ('laskutus', 'kulutus')
                 AND tapahtuma.tuoteno    = '$epakurantti_row[tuoteno]'
                 AND tapahtuma.kpl        < 0
                 AND tapahtuma.rivitunnus not in (0, -1)
                 AND varastopaikat.epakurantointi = ''
                 ORDER BY laadittu DESC
                 LIMIT 1;";
      $tapres = pupe_query($query);

      if (!$laskutusrow = mysql_fetch_assoc($tapres)) {
        // Jos ei lˆydy laskua, laitetaan jotain vanhaa
        $laskutusrow = array("laadittu" => "1970-01-01");
      }

      list($vv1, $kk1, $pp1) = explode("-", substr($tulorow["laadittu"], 0, 10));
      list($vv2, $kk2, $pp2) = explode("-", substr($laskutusrow["laadittu"], 0, 10));

      $viimeinen_tulo = (int) date("U", mktime(0, 0, 0, $kk1, $pp1, $vv1));
      $viimeinen_laskutus = (int) date("U", mktime(0, 0, 0, $kk2, $pp2, $vv2));

      // Lasketaan monta p‰iv‰‰ on kulunut viimeisest‰ tulosta / laskutuksesta
      $tulo = ($today - $viimeinen_tulo) / 60 / 60 / 24;
      $lasku = ($today - $viimeinen_laskutus) / 60 / 60 / 24;

      $tuoteno   = $epakurantti_row["tuoteno"];
      $tee     = "";
      $mikataso   = 0;

      // viimeisin tulo yli $epakurtasot_array["100%"] p‰iv‰‰ sitten --> 100% ep‰kurantiksi
      if (isset($epakurtasot_array["100%"]) and $epakurtasot_array["100%"] > 0 and $tulo > $epakurtasot_array["100%"] and $lasku > $epakurtasot_array["100%"] and $epakurantti_row["epakurantti100pvm"] == "0000-00-00") {

        if ($php_cli or (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI")) {
          $tee = "paalle";
          require "epakurantti.inc";
        }

        $mikataso = 100;
      }
      // viimeisin tulo yli $epakurtasot_array["75%"] p‰iv‰‰ sitten --> 75% ep‰kurantiksi
      elseif (isset($epakurtasot_array["75%"]) and $epakurtasot_array["75%"] > 0 and $tulo > $epakurtasot_array["75%"] and $lasku > $epakurtasot_array["75%"] and $epakurantti_row["epakurantti75pvm"] == "0000-00-00") {

        if ($php_cli or (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI")) {
          $tee = "75paalle";
          require "epakurantti.inc";
        }

        $mikataso = 75;
      }
      // viimeisin tulo yli $epakurtasot_array["50%"] p‰iv‰‰ sitten --> 50% ep‰kurantiksi
      elseif (isset($epakurtasot_array["50%"]) and $epakurtasot_array["50%"] > 0 and $tulo > $epakurtasot_array["50%"] and $lasku > $epakurtasot_array["50%"] and $epakurantti_row["epakurantti50pvm"] == "0000-00-00") {

        if ($php_cli or (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI")) {
          $tee = "puolipaalle";
          require "epakurantti.inc";
        }

        $mikataso = 50;
      }
      // viimeisin tulo yli $epakurtasot_array["25%"] p‰iv‰‰ sitten --> 25% ep‰kurantiksi
      elseif (isset($epakurtasot_array["25%"]) and $epakurtasot_array["25%"] > 0 and $tulo > $epakurtasot_array["25%"] and $lasku > $epakurtasot_array["25%"] and $epakurantti_row["epakurantti25pvm"] == "0000-00-00") {

        if ($php_cli or (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI")) {
          $tee = "25paalle";
          require "epakurantti.inc";
        }

        $mikataso = 25;
      }

      if ($php_cli or (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI")) {
        $viesti = ob_get_contents();
        ob_end_clean();
      }

      if ($mikataso > 0) {

        if (!$php_cli) echo "<tr><td><a target='Tuotekysely' href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($epakurantti_row['tuoteno'])."'>{$epakurantti_row['tuoteno']}</a>";

        // N‰ytet‰‰n varastossa olevat er‰t/sarjanumerot
        if ($epakurantti_row["sarjanumeroseuranta"] == "V" or $epakurantti_row['sarjanumeroseuranta'] == 'T') {

          $query  = "SELECT sarjanumeroseuranta.*, sarjanumeroseuranta.tunnus sarjatunnus,
                     tilausrivi_osto.tunnus osto_rivitunnus,
                     tilausrivi_osto.perheid2 osto_perheid2,
                     tilausrivi_osto.nimitys nimitys,
                     lasku_myynti.nimi myynimi
                     FROM sarjanumeroseuranta
                     LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
                     LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                     LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.uusiotunnus
                     LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
                     WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
                     and sarjanumeroseuranta.tuoteno           = '$epakurantti_row[tuoteno]'
                     and sarjanumeroseuranta.myyntirivitunnus != -1
                     and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
                     and tilausrivi_osto.laskutettuaika       != '0000-00-00'";
          $sarjares = pupe_query($query);

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            if (!$php_cli) echo "<br>".t("S:nro")."$sarjarow[sarjanumero]";
          }
        }
        elseif ($epakurantti_row["sarjanumeroseuranta"] == "E" or $epakurantti_row["sarjanumeroseuranta"] == "F") {

          $query  = "SELECT sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.parasta_ennen, sarjanumeroseuranta.lisatieto,
                     sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso,
                     sarjanumeroseuranta.era_kpl kpl,
                     sarjanumeroseuranta.tunnus sarjatunnus
                     FROM sarjanumeroseuranta
                     LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                     WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
                     and sarjanumeroseuranta.tuoteno           = '$epakurantti_row[tuoteno]'
                     and sarjanumeroseuranta.myyntirivitunnus  = 0
                     and sarjanumeroseuranta.era_kpl          != 0
                     and tilausrivi_osto.laskutettuaika       != '0000-00-00'";
          $sarjares = pupe_query($query);

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            if (!$php_cli) echo "<br>".t("E:nro")." $sarjarow[sarjanumero] ($sarjarow[kpl])";
          }
        }

        if (!$php_cli) echo "</td>";

        $tuotensarake = $excelsarake;

        $worksheet->writeString($excelrivi, $excelsarake++, $epakurantti_row['tuoteno']);


        if (!$php_cli) echo "<td>{$epakurantti_row['nimitys']}</td>";
        $worksheet->writeString($excelrivi, $excelsarake++, $epakurantti_row['nimitys']);

        $try = t_avainsana("TRY", '', "and selite = '{$epakurantti_row['try']}'", '', '', "selitetark");

        $try_teksti = $try != "" ? "{$epakurantti_row['try']} - $try" : $epakurantti_row['try'];

        if (!$php_cli) echo "<td>{$try_teksti}</td>";
        $worksheet->writeString($excelrivi, $excelsarake++, $try_teksti);

        if ($tulorow['laadittu'] == "1970-01-01") {
          if (!$php_cli) echo "<td></td>";
          $worksheet->writeString($excelrivi, $excelsarake++, "");
        }
        else {
          if (!$php_cli) echo "<td>".tv1dateconv($tulorow['laadittu'])."</td>";
          $worksheet->writeDate($excelrivi, $excelsarake++, $tulorow['laadittu']);
        }

        if ($laskutusrow['laadittu'] == "1970-01-01") {
          if (!$php_cli) echo "<td></td>";
          $worksheet->writeString($excelrivi, $excelsarake++, "");
        }
        else {
          if (!$php_cli) echo "<td>".tv1dateconv($laskutusrow['laadittu'])."</td>";
          $worksheet->writeDate($excelrivi, $excelsarake++, $laskutusrow['laadittu']);
        }

        if ($mikataso == 100) {
          $ekk = round($epakurtasot_array["100%"] / (365/12), 1);
        }
        elseif ($mikataso == 75) {
          $ekk = round($epakurtasot_array["75%"] / (365/12), 1);
        }
        elseif ($mikataso == 50) {
          $ekk = round($epakurtasot_array["50%"] / (365/12), 1);
        }
        elseif ($mikataso == 25) {
          $ekk = round($epakurtasot_array["25%"] / (365/12), 1);
        }

        if (!$php_cli) echo "<td>".t("Yli %s kk sitten", "", $ekk)."</td>";
        $worksheet->writeString($excelrivi, $excelsarake++, t("Yli %s kk sitten", "", $ekk));

        if (!$php_cli) echo "<td align='right'>{$mikataso}%</td>";
        $worksheet->writeString($excelrivi, $excelsarake++, $mikataso."%");

        if (!$php_cli) echo "<td align='right'>{$epakurantti_row['saldo']}</td>";
        $worksheet->writeNumber($excelrivi, $excelsarake++, $epakurantti_row['saldo']);

        if (!$php_cli) echo "<td align='right'>".round($epakurantti_row['kehahin'], 2)."</td>";
        $worksheet->writeNumber($excelrivi, $excelsarake++, round($epakurantti_row['kehahin'], 2));

        $vararvo_nyt = $vararvo_sit = round($epakurantti_row['kehahin']*$epakurantti_row['saldo'], 2);

        if (!$php_cli) echo "<td align='right'>{$vararvo_nyt}</td>";
        $worksheet->writeNumber($excelrivi, $excelsarake++, $vararvo_nyt);

        if ($tee != "" or $ajo_tee == "NAYTA" or $ajo_tee == "NAYTAPV") {
          if ($mikataso == 100) {
            $vararvo_sit = 0;
          }
          elseif ($mikataso == 75) {
            $vararvo_sit = round($epakurantti_row['bruttokehahin']*0.25*$epakurantti_row['saldo'], 2);
          }
          elseif ($mikataso == 50) {
            $vararvo_sit = round($epakurantti_row['bruttokehahin']*0.5*$epakurantti_row['saldo'], 2);
          }
          elseif ($mikataso == 25) {
            $vararvo_sit = round($epakurantti_row['bruttokehahin']*0.75*$epakurantti_row['saldo'], 2);
          }
        }

        if (!$php_cli) echo "<td align='right'>{$vararvo_sit}</td>";
        $worksheet->writeNumber($excelrivi, $excelsarake++, $vararvo_sit);

        if ($php_cli or (isset($ajo_tee) and $ajo_tee == "EPAKURANTOI")) {
          if (!$php_cli) echo "<td>$viesti</td>";
          $worksheet->writeString($excelrivi, $excelsarake++, strip_tags($viesti));
        }

        if (!$php_cli) echo "</tr>";

        $excelrivi++;
        $excelsarake = 0;

        if ($epakurantti_row["sarjanumeroseuranta"] == "V" or $epakurantti_row['sarjanumeroseuranta'] == 'T') {

          mysql_data_seek($sarjares, 0);

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            $worksheet->writeString($excelrivi, $tuotensarake, t("S:nro"));
            $worksheet->writeString($excelrivi, $tuotensarake+1, $sarjarow["sarjanumero"]);
            $excelrivi++;
          }
        }
        elseif ($epakurantti_row["sarjanumeroseuranta"] == "E" or $epakurantti_row["sarjanumeroseuranta"] == "F") {
          mysql_data_seek($sarjares, 0);

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            $worksheet->writeString($excelrivi, $tuotensarake, t("E:nro"));
            $worksheet->writeString($excelrivi, $tuotensarake+1, "$sarjarow[sarjanumero] ($sarjarow[kpl])");
            $excelrivi++;
          }
        }

        $vararvot_nyt += $vararvo_nyt;
        $vararvot_sit += $vararvo_sit;

        $epa_tuotemaara++;

      }
    }
  }

  if ($epa_tuotemaara > 0) {

    if (!$php_cli) echo "<tr><td class='tumma' colspan='9'>".t("Yhteens‰").":</td>";
    $worksheet->writeString($excelrivi, 8, t("Yhteens‰"));

    if (!$php_cli) echo "<td class='tumma' align='right'>$vararvot_nyt</td>";
    $worksheet->writeNumber($excelrivi, 9, $vararvot_nyt);

    if (!$php_cli) echo "<td class='tumma' align='right'>$vararvot_sit</td>";
    $worksheet->writeNumber($excelrivi, 10, $vararvot_sit);

    if (!$php_cli and isset($ajo_tee) and $ajo_tee == "EPAKURANTOI") {
      echo "<td class='tumma'></td>";
    }

    if (!$php_cli) echo "</tr>";
    $excelrivi++;

    if (!$php_cli) echo "<tr><td class='tumma' colspan='10'>".t("Ep‰kuranttimuutos yhteens‰").":</td>";
    $worksheet->writeString($excelrivi, 8, t("Ep‰kuranttimuutos yhteens‰"));

    if (!$php_cli) echo "<td class='tumma' align='right'>", ($vararvot_sit-$vararvot_nyt), "</td>";
    $worksheet->writeNumber($excelrivi, 10, ($vararvot_sit-$vararvot_nyt));

    if (!$php_cli and isset($ajo_tee) and $ajo_tee == "EPAKURANTOI") {
      echo "<td class='tumma'></td>";
    }

    if (!$php_cli) {
      echo "</tr>";
      echo "</table>";
    }

    $excelnimi = $worksheet->close();

    if (!$php_cli) {
      echo "<br><br><table>";
      echo "<tr><th>".t("Tallenna tulos").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='Epakurantit.xlsx'>";
      echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna excel")."'></td></tr></form>";
      echo "</table><br>";

      if ($ajo_tee != "NAYTAPV") {
        echo "<br><br><form name = 'valinta' method='post'>";
        echo "<input type = 'hidden' name = 'ajo_tee' value = 'EPAKURANTOI'>";
        echo "<input type = 'submit' value = '".t("Tee ep‰kuranttiusp‰ivitykset")."'>";
        echo "</form><br>";
      }

      require "inc/footer.inc";
    }
    else {
      // S‰hkˆpostin l‰hetykseen parametrit
      $parametri = array( "to"       => $yhtiorow['talhal_email'],
        "cc"       => "",
        "subject"    => t("Ep‰kuranttiajo"),
        "ctype"      => "text",
        "body"      => t("Liitteen‰ ep‰kuranttiajon raportti").".",
        "attachements"  => array(0   => array(
            "filename"    => "/tmp/".$excelnimi,
            "newfilename"  => "Epakuranttiajo.xlsx",
            "ctype"      => "EXCEL"),
        )
      );
      $boob = pupesoft_sahkoposti($parametri);
    }
  }
}
