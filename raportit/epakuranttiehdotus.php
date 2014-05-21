<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Ei käytetä pakkausta
$compression = FALSE;

if (isset($_REQUEST["tee"])) {
  if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/","",$_REQUEST["kaunisnimi"]);
}

require ("../inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}
else {

  echo "<font class='head'>".t("Epäkuranttiehdotus")."</font><hr>";

  // nollataan muuttujat
  $epakuranttipvm = "";
  $tuote_epa_rajaus = "";
  $chk1 = "";
  $chk2 = "";
  $chk3 = "";
  $chk4 = "";

  if (!isset($tyyppi)) $tyyppi  = "";
  if (!isset($tuotetyyppi)) $tuotetyyppi  = "";

  if ($tyyppi == '25') $chk1 = "selected";
  if ($tyyppi == 'puoli') $chk2 = "selected";
  if ($tyyppi == '75') $chk3 = "selected";
  if ($tyyppi == 'taysi') $chk4 = "selected";

  if ($tuotetyyppi == '25') $tchk1 = "selected";
  if ($tuotetyyppi == 'puoli') $tchk2 = "selected";
  if ($tuotetyyppi == '75') $tchk3 = "selected";
  if ($tuotetyyppi == 'taysi') $tchk4 = "selected";

  // defaultteja
  if (!isset($alkupvm))  $alkupvm  = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
  if (!isset($loppupvm)) $loppupvm = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
  if (!isset($taysraja)) $taysraja = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
  if (!isset($raja))     $raja = "0.5";

  // errorcheckejä
  if (!checkdate(substr($alkupvm,5,2), substr($alkupvm,8,2), substr($alkupvm,0,4))) {
    echo "<font class='error'>".t("Virheellinen päivämäärä")." $alkupvm!</font><br><br>";
    unset($subnappi);
  }

  if (!checkdate(substr($loppupvm,5,2), substr($loppupvm,8,2), substr($loppupvm,0,4))) {
    echo "<font class='error'>".t("Virheellinen päivämäärä")." $loppupvm!</font><br><br>";
    unset($subnappi);
  }

  if (!checkdate(substr($taysraja,5,2), substr($taysraja,8,2), substr($taysraja,0,4))) {
    echo "<font class='error'>".t("Virheellinen päivämäärä")." $taysraja!</font><br><br>";
    unset($subnappi);
  }

  if ($tyyppi == "25" and $tuotetyyppi != "") {
    echo "<font class='error'>".t("VIRHE: Liian tiukka tuoterajaus")."!</font><br><br>";
    unset($subnappi);
  }
  elseif ($tyyppi == "puoli" and ($tuotetyyppi == "puoli" or $tuotetyyppi == "75" or $tuotetyyppi == "taysi")) {
    echo "<font class='error'>".t("VIRHE: Liian tiukka tuoterajaus")."!</font><br><br>";
    unset($subnappi);
  }
  elseif ($tyyppi == "75" and ($tuotetyyppi == "75" or $tuotetyyppi == "taysi")) {
    echo "<font class='error'>".t("VIRHE: Liian tiukka tuoterajaus")."!</font><br><br>";
    unset($subnappi);
  }

  echo "<form name='epakurantti' method='post' autocomplete='off'>";
  echo "<table>";

  echo "<tr>";
  echo "<th>".t("Valitse ehdotus").":</th>";
  echo "<td>";
  echo "<select name='tyyppi'>";
  echo "<option $chk1 value='25'>25% ".t("epäkuranttiehdotus")."</option>";
  echo "<option $chk2 value='puoli'>".t("Puoliepäkuranttiehdotus")."</option>";
  echo "<option $chk3 value='75'>75% ".t("epäkuranttiehdotus")."</option>";
  echo "<option $chk4 value='taysi'>".t("Täysepäkuranttiehdotus")."</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Tuoterajaus").":</th>";
  echo "<td>";
  echo "<select name='tuotetyyppi'>";
  echo "<option value=''>".t("Näytä kaikki tuotteet")."</option>";
  echo "<option $tchk1 value='25'>".t("Näytä vain 25% epäkurantit")."</option>";
  echo "<option $tchk2 value='puoli'>".t("Näytä vain puoliepäkurantit")."</option>";
  echo "<option $tchk3 value='75'>".t("Näytä vain 75% epäkurantit")."</option>";
  echo "<option $tchk4 value='taysi'>".t("Näytä vain täysepäkurantit")."</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Valitse alku- ja loppupäivä").":</th>";
  echo "<td><input type='text' name='alkupvm'  value='$alkupvm'> - <input type='text' name='loppupvm' value='$loppupvm'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Anna epäkuranttiusraja (kierto)").":</th>";
  echo "<td><input type='text' name='raja' value='$raja'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Anna epäkuranttitason alaraja pvm").":</th>";
  echo "<td><input type='text' name='taysraja' value='$taysraja'><br>".t("Tuote on pitänyt laittaa edelliselle epäkuranttiustasolle ennen tätä päivää, jotta ehdotetaan seuraavaan epäkuranttitasoon")."<br>".t("Rajaus ei koske 25% epäkuranttiehdotusta")."</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Anna osasto ja/tai tuoteryhmä").":</th>";
  echo "<td nowrap>";

  $monivalintalaatikot = array("OSASTO", "TRY");
  $monivalintalaatikot_normaali = array();

  require ("tilauskasittely/monivalintalaatikot.inc");

  echo "</td>";
  echo "</tr>";

  echo "</table>";
  echo "<br><input type='submit' name='subnappi' value='".t("Aja raportti")."'>";
  echo "</form><br><br>";

  if (isset($subnappi) and $subnappi != '') {

    $msg  = "";

    if (isset($lisa) and $lisa != '') {
      if (count($mul_osasto) > 0) {
        $msg .= " ".t("Osasto").": ";

        foreach ($mul_osasto as $osasto) {
          $msg .= "$osasto, ";
        }

        $msg = substr($msg, 0, -2);
      }

      if (count($mul_try) > 0) {
        $msg .= " ".t("Tuoteryhmä").": ";

        foreach ($mul_try as $try) {
          $msg .= "$try, ";
        }

        $msg = substr($msg, 0, -2);
      }
    }

    if ($tyyppi == '25') {
      // 25epäkurantteja etsittäessä tuote ei saa olla puoli eikä täysiepäkurantti
      $epakuranttipvm = "and epakurantti25pvm='0000-00-00' and epakurantti50pvm='0000-00-00' and epakurantti75pvm='0000-00-00' and epakurantti100pvm='0000-00-00'";
      echo "<font class='message'>".t("25% epäkuranttiehdotus, myydyt kappaleet")." $alkupvm - $loppupvm, ".t("kiertoraja")." $raja$msg. ".t("Viimeinen saapuminen ennen")." $alkupvm.</font><br><br>";
    }

    if ($tyyppi == 'puoli') {
      // puoliepäkurantteja etsittäessä tuote ei saa olla puoli eikä täysiepäkurantti
      $epakuranttipvm = "and epakurantti50pvm='0000-00-00' and epakurantti75pvm='0000-00-00' and epakurantti100pvm='0000-00-00'";
      echo "<font class='message'>".t("Puoliepäkuranttiehdotus, myydyt kappaleet")." $alkupvm - $loppupvm, ".t("kiertoraja")." $raja$msg. ".t("Viimeinen saapuminen ennen")." $alkupvm.</font><br><br>";
    }

    if ($tyyppi == '75') {
      // 75epäkurantteja etsittäessä tuote ei saa olla puoli eikä täysiepäkurantti
      $epakuranttipvm = "and epakurantti75pvm='0000-00-00' and epakurantti100pvm='0000-00-00'";
      echo "<font class='message'>".t("75% epäkuranttiehdotus, myydyt kappaleet")." $alkupvm - $loppupvm, ".t("kiertoraja")." $raja$msg. ".t("Viimeinen saapuminen ennen")." $alkupvm.</font><br><br>";
    }

    if ($tyyppi == 'taysi') {
      // täysiepäkurantteja etsittäessä tuotteen pitää olla puoliepäkurantti mutta ei täysepäkurantti
      $epakuranttipvm = "and epakurantti100pvm='0000-00-00'";
      echo "<font class='message'>".t("Täysiepäkuranttiehdotus, myydyt kappaleet")." $alkupvm - $loppupvm, ".t("kiertoraja")." $raja$msg. ".t("Viimeinen saapuminen ennen")." $alkupvm.</font><br><br>";
    }

    if ($tuotetyyppi == "25") {
      $tuote_epa_rajaus = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm = '0000-00-00' and epakurantti75pvm = '0000-00-00' and epakurantti100pvm = '0000-00-00'";
    }

    if ($tuotetyyppi == "puoli") {
      $tuote_epa_rajaus = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm != '0000-00-00' and epakurantti75pvm = '0000-00-00' and epakurantti100pvm = '0000-00-00'";
    }

    if ($tuotetyyppi == "75") {
      $tuote_epa_rajaus = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm != '0000-00-00' and epakurantti75pvm != '0000-00-00' and epakurantti100pvm = '0000-00-00'";
    }

    if ($tuotetyyppi == "taysi") {
      $tuote_epa_rajaus = "and epakurantti25pvm != '0000-00-00' and epakurantti50pvm != '0000-00-00' and epakurantti75pvm != '0000-00-00' and epakurantti100pvm != '0000-00-00'";
    }

    // etsitään saldolliset tuotteet
    $query  = "SELECT tuote.tuoteno,
               tuote.osasto,
               tuote.try,
               tuote.myyntihinta,
               tuote.nimitys,
               tuote.tahtituote,
               tuote.status,
               tuote.hinnastoon,
               round(if(epakurantti75pvm = '0000-00-00', if(epakurantti50pvm = '0000-00-00', if(epakurantti25pvm = '0000-00-00', kehahin, kehahin * 0.75), kehahin * 0.5), kehahin * 0.25), 6) kehahin,
               tuote.vihapvm,
               epakurantti25pvm,
               epakurantti50pvm,
               epakurantti75pvm,
               tuote.tuotemerkki,
               tuote.myyjanro,
               tuote.sarjanumeroseuranta,
               sum(saldo) saldo
               FROM tuote
               JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
               WHERE tuote.yhtio             = '$kukarow[yhtio]'
               AND tuote.ei_saldoa           = ''
               AND tuote.tuotetyyppi         NOT IN ('A', 'B')
               AND tuote.sarjanumeroseuranta NOT IN ('S','U','G')
               $epakuranttipvm
               $tuote_epa_rajaus
               $lisa
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16
               HAVING saldo != 0
               ORDER BY tuote.tuoteno";
    $result = mysql_query($query) or pupe_error($query);

    echo "<br><font class='message'>".t("Löytyi")." ".mysql_num_rows($result)." ".t("sopivaa tuotetta. Lasketaan ehdotus.")."</font><br>";

    flush();

    $yhteensopivuus_table_check = table_exists("yhteensopivuus_tuote");

    $elements = mysql_num_rows($result); // total number of elements to process

    include('inc/pupeExcel.inc');

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi    = 0;
    $excelsarake = 0;

    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("osasto")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("try")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("tuotemerkki")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("määrä")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("myytävissä")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("kierto")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("tahtituote")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("status")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("hinnastoon")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("eka saapuminen")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("vika saapuminen")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("hinta")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("kehahin")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("tuoteno")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("nimitys")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("toimittaja")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("myyja")), $format_bold);
    $excelsarake++;

    if ($yhteensopivuus_table_check) {
      $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("yhteensopivuus")), $format_bold);
      $excelsarake++;
    }

    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("epakurantti25pvm")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("epakurantti50pvm")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("epakurantti75pvm")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("epakurantti100pvm")), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("toiminto")), $format_bold);
    $excelsarake++;

    if ($elements > 0) {
      require_once ('inc/ProgressBar.class.php');
      $bar = new ProgressBar();
      $bar->initialize($elements); // print the empty bar
    }

    $excelrivi++;
    $excelsarake = 0;
    $laskuri    = 0;

    $myyja_array = array();

    $query = "SELECT myyja, nimi
              FROM kuka
              WHERE yhtio  = '$kukarow[yhtio]'
              AND myyja    > 0
              AND extranet = ''";
    $myyjares = mysql_query($query) or pupe_error($query);

    while ($myyjarow = mysql_fetch_assoc($myyjares)) {
      $myyja_array[$myyjarow['myyja']] = $myyjarow['nimi'];
    }

    list($vv2,$kk2,$pp2) = explode("-", $alkupvm);  // $alaraja (myyntirajauksen alku pvm)
    list($vv4,$kk4,$pp4) = explode("-", $taysraja);  // $epa2raja (pvm jolloin tuote on laitettu edelliselle epäkurtasolle)

    $alaraja  = (int) date('Ymd',mktime(0,0,0,$kk2,$pp2,$vv2));
    $epa2raja = (int) date('Ymd',mktime(0,0,0,$kk4,$pp4,$vv4));

    while ($row = mysql_fetch_assoc($result)) {

      $bar->increase();

      $epispvm = "0000-00-00";

      if ($row["epakurantti75pvm"] != "0000-00-00") {
        $epispvm = $row["epakurantti75pvm"];
      }
      elseif ($row["epakurantti50pvm"] != "0000-00-00") {
        $epispvm = $row["epakurantti50pvm"];
      }
      elseif ($row["epakurantti25pvm"] != "0000-00-00") {
        $epispvm = $row["epakurantti25pvm"];
      }

      // jos meillä on tuotteen vihapvm käytetään sitä, muuten eka from 70s...
      if ($row["vihapvm"] == "0000-00-00") $row["vihapvm"] = '1970-01-01';

      // haetaan eka ja vika saapumispäivä
      $query  = "SELECT
                 date_format(ifnull(min(laadittu),'1970-01-01'),'%Y-%m-%d') min,
                 date_format(ifnull(max(laadittu),'1970-01-01'),'%Y-%m-%d') max
                 FROM tapahtuma
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND tuoteno = '$row[tuoteno]'
                 AND selite  not like '%alkusaldo%'
                 AND laji    in ('tulo', 'valmistus')";
      $tapres = mysql_query($query) or pupe_error($query);
      $taprow = mysql_fetch_assoc($tapres);

      // verrataan vähän päivämääriä. onpa ikävää PHP:ssä!
      list($vv1,$kk1,$pp1) = explode("-", $taprow["max"]);  // $saapunut (viimeisen tulon pvm)
      $saapunut = (int) date('Ymd',mktime(0,0,0,$kk1,$pp1,$vv1));

      list($vv3,$kk3,$pp3) = explode("-", $epispvm);      // $epaku1pv (viimeisin epäkurantti pvm)
      $epaku1pv = (int) date('Ymd',mktime(0,0,0,$kk3,$pp3,$vv3));

      // Jos tuotetta on tullut myyntirajauksen aikana, ei ehdota sitä epäkurantiksi.
      // Lisäksi jos kyseessä on joku muu kuin 25% epäkuranttiajo, pitää viimeisin epäkuranttipvm olla pienempi kuin täysepäkuranttisuuden alaraja pvm
       if (($saapunut < $alaraja) and ($epaku1pv < $epa2raja or $tyyppi == '25')) {

        $query = "SELECT group_concat(distinct toimi.ytunnus separator '/') toimittaja
                  FROM tuotteen_toimittajat
                  JOIN toimi ON toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus
                  WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
                  and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'";
        $toimittajares = mysql_query($query) or pupe_error($query);
        $toimittajarow = mysql_fetch_assoc($toimittajares);

        // haetaan tuotteen myydyt kappaleet
        $query  = "SELECT ifnull(sum(kpl),0) kpl
                   FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
                   WHERE yhtio        = '$kukarow[yhtio]'
                   and tyyppi         = 'L'
                   and tuoteno        = '$row[tuoteno]'
                   and laskutettuaika >= '$alkupvm'
                   and laskutettuaika <= '$loppupvm'";
        $myyres = mysql_query($query) or pupe_error($query);
        $myyrow = mysql_fetch_assoc($myyres);

        // haetaan tuotteen kulutetut kappaleet
        $query  = "SELECT ifnull(sum(kpl),0) kpl
                   FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
                   WHERE yhtio        = '$kukarow[yhtio]'
                   and tyyppi         = 'V'
                   and tuoteno        = '$row[tuoteno]'
                   and toimitettuaika >= '$alkupvm'
                   and toimitettuaika <= '$loppupvm'";
        $kulres = mysql_query($query) or pupe_error($query);
        $kulrow = mysql_fetch_assoc($kulres);

        // haetaan tuotteen ennakkopoistot
        $query  = "SELECT ifnull(sum(varattu),0) ennpois
                   FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
                   WHERE yhtio = '$kukarow[yhtio]'
                   and tuoteno = '$row[tuoteno]'
                   and tyyppi  = 'L'
                   and varattu <> 0";
        $ennres = mysql_query($query) or pupe_error($query);
        $ennrow = mysql_fetch_assoc($ennres);

        // lasketaan saldo (myytävissä)
        $saldo = $row["saldo"] - $ennrow["ennpois"];

        // lasketaan varaston kiertonopeus
        if ($saldo > 0) {
          $kierto = round(($myyrow["kpl"] + $kulrow["kpl"]) / $saldo, 2);
        }
        else {
          $kierto = 0;
        }

        // typecast
        $raja = (float) str_replace(",",".", $raja);

        if ($yhteensopivuus_table_check) {
          $query = "SELECT count(yhteensopivuus_rekisteri.tunnus) maara
                    FROM yhteensopivuus_tuote, yhteensopivuus_rekisteri
                    WHERE yhteensopivuus_tuote.yhtio = yhteensopivuus_rekisteri.yhtio
                    AND yhteensopivuus_tuote.atunnus = yhteensopivuus_rekisteri.autoid
                    AND yhteensopivuus_tuote.yhtio   = '$kukarow[yhtio]'
                    AND yhteensopivuus_tuote.tuoteno = '$row[tuoteno]'";
          $yhteensopivuus_res = mysql_query($query) or pupe_error($query);
          $yhteensopivuus_row = mysql_fetch_assoc($yhteensopivuus_res);
        }

        // katellaan ollaanko alle rajan
        if ($kierto < $raja or ($raja == 0 and $kierto <= 0)) {

          $laskuri++;

          $worksheet->writeString($excelrivi, $excelsarake, $row['osasto']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $row['try']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $row['tuotemerkki']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, str_replace(".",",",$myyrow['kpl']+$kulrow['kpl']));
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, str_replace(".",",",$saldo));
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, str_replace(".",",",$kierto));
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $row['tahtituote']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $row['status']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $row['hinnastoon']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $taprow['min']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $taprow['max']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, str_replace(".",",",$row['myyntihinta']));
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, str_replace(".",",",$row['kehahin']));
          $excelsarake++;

          $tuotensarake = $excelsarake;

          $worksheet->writeString($excelrivi, $excelsarake, $row['tuoteno']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, t_tuotteen_avainsanat($row, 'nimitys'));
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $toimittajarow['toimittaja']);
          $excelsarake++;

          if ($row['myyjanro'] > 0 and isset($myyja_array[$row['myyjanro']])) {
            $worksheet->writeString($excelrivi, $excelsarake, $myyja_array[$row['myyjanro']]);
            $excelsarake++;
          }
          else {
            $worksheet->writeString($excelrivi, $excelsarake, '');
            $excelsarake++;
          }

          if ($yhteensopivuus_table_check) {
            $worksheet->writeString($excelrivi, $excelsarake, $yhteensopivuus_row["maara"]);
            $excelsarake++;
          }

          if ($row['epakurantti25pvm'] == '0000-00-00') $row['epakurantti25pvm'] = "";
          if ($row['epakurantti50pvm'] == '0000-00-00') $row['epakurantti50pvm'] = "";
          if ($row['epakurantti75pvm'] == '0000-00-00') $row['epakurantti75pvm'] = "";

          $worksheet->writeString($excelrivi, $excelsarake, $row['epakurantti25pvm']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $row['epakurantti50pvm']);
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, $row['epakurantti75pvm']);
          $excelsarake++;

          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "muuta");

          $excelsarake = 0;
          $excelrivi++;

          // Näytetään varastossa olevat erät/sarjanumerot
          if ($row["sarjanumeroseuranta"] == "V" or $row['sarjanumeroseuranta'] == 'T') {

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
                       and sarjanumeroseuranta.tuoteno           = '$row[tuoteno]'
                       and sarjanumeroseuranta.myyntirivitunnus != -1
                       and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
                       and tilausrivi_osto.laskutettuaika       != '0000-00-00'";
            $sarjares = pupe_query($query);

            while ($sarjarow = mysql_fetch_assoc($sarjares)) {
              $worksheet->writeString($excelrivi, $tuotensarake, t("S:nro"));
              $worksheet->writeString($excelrivi, $tuotensarake+1, $sarjarow["sarjanumero"]);
              $excelrivi++;
            }
          }
          elseif ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F") {

            $query  = "SELECT sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.parasta_ennen, sarjanumeroseuranta.lisatieto,
                       sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso,
                       sarjanumeroseuranta.era_kpl kpl,
                       sarjanumeroseuranta.tunnus sarjatunnus
                       FROM sarjanumeroseuranta
                       LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                       WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
                       and sarjanumeroseuranta.tuoteno           = '$row[tuoteno]'
                       and sarjanumeroseuranta.myyntirivitunnus  = 0
                       and sarjanumeroseuranta.era_kpl          != 0
                       and tilausrivi_osto.laskutettuaika       != '0000-00-00'";
            $sarjares = pupe_query($query);

            while ($sarjarow = mysql_fetch_assoc($sarjares)) {
              $worksheet->writeString($excelrivi, $tuotensarake, t("E:nro"));
              $worksheet->writeString($excelrivi, $tuotensarake+1, "$sarjarow[sarjanumero] ($sarjarow[kpl])");
              $excelrivi++;
            }
          }


        }
      } // end saapunut ennen alarajaa
    }

    $excelnimi = $worksheet->close();

    echo "<br/>";
    echo "<font class='message'>".t("Ehdotuksessa %s tuotetta.", "", $laskuri)."</font>";
    echo "<br/>";
    echo "<br/>";

    echo "<table>";
    echo "<tr><th>".t("Tallenna raportti (xlsx)").":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='".t("Epakuranttiraportti").".xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
    echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
    echo "</table><br>";

  }

  // kursorinohjausta
  $formi  = "epakurantti";
  $kentta = "osasto";

  require ("../inc/footer.inc");
}
