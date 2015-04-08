<?php

$lue_data_output_file = "";
$lue_data_output_text = "";
$lue_data_err_file = "";
$lue_data_virheelliset_rivit = array();
$api_output = "";
$api_status = TRUE;

// Ei k�ytet� pakkausta
$compression = FALSE;

// Enabloidaan, ett� Apache flushaa kaiken mahdollisen ruudulle kokoajan.
ini_set('implicit_flush', 1);
ob_implicit_flush(1);

ini_set("memory_limit", "5G");
ini_set("post_max_size", "100M");
ini_set("upload_max_filesize", "100M");
ini_set("mysql.connect_timeout", 600);
ini_set("max_execution_time", 18000);

if (php_sapi_name() == 'cli') {

  $pupe_root_polku = dirname(__FILE__);
  require "{$pupe_root_polku}/inc/connect.inc";
  require "{$pupe_root_polku}/inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $cli = true;
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear".PATH_SEPARATOR."/usr/share/php/");

  $sanakirja_kielet = array("fi" => "Suomi",
    "en" => "Englanti",
    "se" => "Ruotsi",
    "ee" => "Viro",
    "de" => "Saksa",
    "dk" => "Tanska",
    "no" => "Norja",
    "ru" => "Ven�j�");

  if (trim($argv[1]) != '') {
    $kukarow['yhtio'] = mysql_real_escape_string($argv[1]);
    $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
  }
  else {
    die ("Et antanut yhti�t�.\n");
  }

  if (trim($argv[2]) != '') {
    $table = trim($argv[2]);

    // T��lt� voi tulla ties mit� lis�parameja
    if (strpos($table, ".") !== FALSE) {
      $paramit = explode("..", $table);

      // Eka veks, ku siin� on taulun nimi eik� parami
      array_shift($paramit);

      foreach ($paramit as $parami) {
        list($muuttuja, $arvo) = explode(".", $parami);

        // Jotain pient� tietoturvaa kuitenki...
        ${$muuttuja} = preg_replace("/[^a-z_0-9]/i", "", $arvo);
      }
    }
  }
  else {
    die ("Et antanut taulun nime�.\n");
  }

  $kukarow['kuka'] = "cli";
  $kukarow['nimi'] = "cli";

  // Mik� tiedosto k�sitell��n
  if (trim($argv[3]) != '') {
    $path_parts = pathinfo(trim($argv[3]));
    $_FILES['userfile']['name'] = $path_parts['basename'];
    $_FILES['userfile']['type'] = (strtoupper($path_parts['extension']) == 'TXT' or strtoupper($path_parts['extension']) == 'CSV') ? 'text/plain' : (strtoupper($path_parts['extension']) == 'XLS') ? 'application/vnd.ms-excel' : '';
    $_FILES['userfile']['tmp_name'] = $argv[3];
    $_FILES['userfile']['error'] = 0; // UPLOAD_ERR_OK
    $_FILES['userfile']['size'] = filesize($argv[3]);
  }
  else {
    die ("Et antanut tiedoston nime� ja polkua.\n");
  }

  // Logfile, johon kirjoitetaan kaikki output
  if (isset($argv[4]) and trim($argv[4]) != '') {
    $lue_data_output_file = trim($argv[4]);
    $fileparts = pathinfo($lue_data_output_file);

    if (!is_writable($fileparts["dirname"])) {
      die ("Virheellinen hakemisto: ".$fileparts["dirname"]);
    }

    if (file_exists($lue_data_output_file)) {
      die ("Ei voida k�ytt�� olemassaolevaa log-file�: ".$lue_data_output_file);
    }
  }

  // Errorfile, johon kirjoitetaan kaikki vriheelliset rivit
  if (isset($argv[5]) and trim($argv[5]) != '') {
    $lue_data_err_file = trim($argv[5]);
    $fileparts = pathinfo($lue_data_err_file);

    if (!is_writable($fileparts["dirname"])) {
      die ("Virheellinen hakemisto: ".$fileparts["dirname"]);
    }

    if (file_exists($lue_data_err_file)) {
      die ("Ei voida k�ytt�� olemassaolevaa err-file�: ".$lue_data_err_file);
    }
  }
}
else {
  // Laitetaan max time 5H
  ini_set("max_execution_time", 18000);
  if (strpos($_SERVER['SCRIPT_NAME'], "lue_data.php") !== FALSE) {
    require "inc/parametrit.inc";
  }
  $cli = false;
}


// Funktio, jolla tehd��n luedatan output
function lue_data_echo($string, $now = false) {

  global $cli, $lue_data_output_file, $lue_data_output_text, $api_kentat, $api_output;

  if (isset($api_kentat)) {
    $api_output .= $string."\n";
  }
  elseif ($cli === FALSE) {
    if ($now === TRUE) {
      echo $string;
    }
    else {
      $lue_data_output_text .= $string;
    }
  }
  elseif ($lue_data_output_file == "") {
    echo strip_tags($string)."\n";
  }
  elseif ($lue_data_output_file != "") {
    file_put_contents($lue_data_output_file, strip_tags($string)."\n", FILE_APPEND);
  }
  else {
    // Tiukkaa touhua, die!
    die ("Virheelliset parametrit");
  }
}

lue_data_echo("<font class='head'>".t("Datan sis��nluku")."</font><hr>");

// Saako p�ivitt��
if (!$cli and $oikeurow['paivitys'] != '1') {
  if ($uusi == 1) {
    lue_data_echo("<b>".t("Sinulla ei ole oikeutta lis�t�")."</b><br>");
    $uusi = '';
  }
  if ($del == 1) {
    lue_data_echo("<b>".t("Sinulla ei ole oikeuttaa poistaa")."</b><br>");
    $del = '';
    $tunnus = 0;
  }
  if ($upd == 1) {
    lue_data_echo("<b>".t("Sinulla ei ole oikeuttaa muuttaa")."</b><br>");
    $upd = '';
    $uusi = 0;
    $tunnus = 0;
  }
}

if (!isset($table)) $table = '';

$kasitellaan_tiedosto = FALSE;
require "inc/pakolliset_sarakkeet.inc";

if (isset($_FILES['userfile']) and (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE or ($cli and trim($_FILES['userfile']['tmp_name']) != ''))) {

  $kasitellaan_tiedosto = TRUE;

  if ($_FILES['userfile']['size'] == 0) {
    lue_data_echo("<font class='error'><br>".t("Tiedosto on tyhj�")."!</font>");
    $kasitellaan_tiedosto = FALSE;
  }

  $path_parts = pathinfo($_FILES['userfile']['name']);
  $ext = strtoupper($path_parts['extension']);

  lue_data_echo("<font class='message'>".t("Tarkastetaan l�hetetty tiedosto")."...<br><br></font>");

  $retval = tarkasta_liite("userfile", array("XLSX", "XLS", "ODS", "SLK", "XML", "GNUMERIC", "CSV", "TXT", "DATAIMPORT"));

  if ($retval !== TRUE) {
    lue_data_echo("<font class='error'><br>".t("V��r� tiedostomuoto")."!</font>");
    $kasitellaan_tiedosto = FALSE;
  }
}
elseif (isset($api_kentat) and count($api_kentat) > 0) {
  $kasitellaan_tiedosto = TRUE;
}

$muutetut_sopimusrivitunnukset = array();

if ($kasitellaan_tiedosto) {

  $lue_data_autoid = file_exists("lue_data_autoid.php");

  /**
   * K�sitelt�v�n filen nimi *
   */


  $kasiteltava_tiedoto_path = $_FILES['userfile']['tmp_name'];

  if (isset($api_kentat) and count($api_kentat) > 0) {
    $excelrivit = $api_kentat;
  }
  else {
    $excelrivit = pupeFileReader($kasiteltava_tiedoto_path, $ext);
  }

  /**
   * Otetaan tiedoston otsikkorivi *
   */
  $headers = $excelrivit[0];
  $headers = array_map('trim', $headers);
  $headers = array_map('strtoupper', $headers);

  // Unsetatan tyhj�t sarakkeet
  for ($i = (count($headers)-1); $i > 0 ; $i--) {
    if ($headers[$i] != "") {
      break;
    }
    else {
      unset($headers[$i]);
    }
  }

  $taulut      = array();
  $mul_taulut   = array();
  $mul_taulas   = array();
  $taulunotsikot  = array();
  $taulunrivit  = array();

  // Katsotaan onko sarakkeita useasta taulusta
  for ($i = 0; $i < count($headers); $i++) {
    if (strpos($headers[$i], ".") !== FALSE) {

      list($taulu, $sarake) = explode(".", $headers[$i]);
      $taulu = strtolower(trim($taulu));

      // Joinataanko sama taulu monta kertaa?
      if ((isset($mul_taulas[$taulu]) and isset($mul_taulut[$taulu."__".$mul_taulas[$taulu]]) and in_array($headers[$i], $mul_taulut[$taulu."__".$mul_taulas[$taulu]])) or (isset($mul_taulut[$taulu]) and (!isset($mul_taulas[$taulu]) or !isset($mul_taulut[$taulu."__".$mul_taulas[$taulu]])) and in_array($headers[$i], $mul_taulut[$taulu]))) {
        $mul_taulas[$taulu]++;

        $taulu = $taulu."__".$mul_taulas[$taulu];
      }
      elseif (isset($mul_taulas[$taulu]) and isset($mul_taulut[$taulu."__".$mul_taulas[$taulu]])) {
        $taulu = $taulu."__".$mul_taulas[$taulu];
      }

      $taulut[] = $taulu;
      $mul_taulut[$taulu][] = $headers[$i];
    }
    else {
      $taulut[] = $table;
    }
  }

  // T�ss� kaikki taulut jotka l�yty failista
  $unique_taulut = array_unique($taulut);

  // Tutkitaan mill� ehdoilla taulut joinataan kesken��n
  $joinattavat = array();

  $table_tarkenne = '';

  foreach ($unique_taulut as $utaulu) {
    list($taulu, ) = explode(".", $utaulu);
    $taulu = preg_replace("/__[0-9]*$/", "", $taulu);

    if (substr($taulu, 0, 11) == 'puun_alkio_') {
      $taulu = 'puun_alkio';
      $table_tarkenne = substr($taulu, 11);
    }

    list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattava, $saakopoistaa, $oletukset) = pakolliset_sarakkeet($taulu);

    // Laitetaan aina kaikkiin tauluihin
    $joinattava["TOIMINTO"] = $table;

    $joinattavat[$utaulu] = $joinattava;
  }

  // Laitetaan jokaisen taulun otsikkorivi kuntoon
  for ($i = 0; $i < count($headers); $i++) {

    if (strpos($headers[$i], ".") !== FALSE) {
      list($sarake1, $sarake2) = explode(".", $headers[$i]);
      if ($sarake2 != "") $sarake1 = $sarake2;
    }
    else {
      $sarake1 = $headers[$i];
    }

    $sarake1 = strtoupper(trim($sarake1));

    $taulunotsikot[$taulut[$i]][] = $sarake1;

    // Pit��k� t�m� sarake laittaa my�s johonki toiseen tauluun?
    foreach ($joinattavat as $taulu => $joinit) {

      if (strpos($headers[$i], ".") !== FALSE) {
        list ($etu, $taka) = explode(".", $headers[$i]);
        if ($taka == "") $taka = $etu;
      }
      else {
        $taka = $headers[$i];
      }

      if (isset($joinit[$taka]) and (!isset($taulunotsikot[$taulu]) or !in_array($sarake1, $taulunotsikot[$taulu]))) {
        $taulunotsikot[$taulu][] = $sarake1;
      }
    }
  }

  foreach ($taulunotsikot as $taulu => $otsikot) {
    if (count($otsikot) != count(array_unique($otsikot))) {
      lue_data_echo("<font class='error'>$taulu-".t("taulun sarakkeissa ongelmia, ei voida jatkaa")."!</font><br>");
      if ($lue_data_output_file != "") {
        lue_data_echo("## LUE-DATA-EOF ##");
      }
      lue_data_echo($lue_data_output_text, true);
      require "inc/footer.inc";
      exit;
    }
  }

  // Otetaan tuotteiden oletusalv hanskaan
  if (in_array("tuote", $taulut)) {
    $oletus_alvprossa = alv_oletus();
  }

  // rivim��r� exceliss�
  $excelrivimaara = count($excelrivit);

  // sarakem��r� exceliss�
  $excelsarakemaara = count($headers);

  // Luetaan tiedosto loppuun ja tehd��n taulukohtainen array koko datasta, t�ss� kohtaa putsataan jokaisen solun sis�lt� pupesoft_cleanstring -funktiolla
  for ($excei = 1; $excei < $excelrivimaara; $excei++) {
    for ($excej = 0; $excej < $excelsarakemaara; $excej++) {

      $taulunrivit[$taulut[$excej]][$excei-1][] = pupesoft_cleanstring($excelrivit[$excei][$excej]);

      // Pit��k� t�m� sarake laittaa my�s johonki toiseen tauluun?
      foreach ($taulunotsikot as $taulu => $joinit) {

        if (strpos($headers[$excej], ".") !== FALSE) {
          list ($etu, $taka) = explode(".", $headers[$excej]);
          if ($taka == "") $taka = $etu;
        }
        else {
          $taka = $headers[$excej];
        }

        if (in_array($taka, $joinit) and $taulu != $taulut[$excej] and $taulut[$excej] == $joinattavat[$taulu][$taka]) {
          $taulunrivit[$taulu][$excei-1][] = pupesoft_cleanstring($excelrivit[$excei][$excej]);
        }
      }
    }
  }

  if (in_array("tuotteen_toimittajat_pakkauskoot", $taulut)) {

    $chk_tuoteno = $chk_toim_tuoteno = "x";

    foreach ($taulunotsikot["tuotteen_toimittajat_pakkauskoot"] as $key => $column) {

      if (isset($toimitunnusvalinta) and $toimitunnusvalinta != 1) {

        if ($column == "TOIM_TUOTENO_TUNNUS") $chk_tunnus = $key;

        switch ($toimitunnusvalinta) {
        case "2":
          if ($column == "TUOTENUMERO") $chk_tuoteno = $key;
          $toimikentta = "ytunnus";
          $tuotenokentta = "tuoteno";
          break;
        case "3":
          if ($column == "TOIM_TUOTENO") $chk_toim_tuoteno = $key;
          $toimikentta = "ytunnus";
          $tuotenokentta = "toim_tuoteno";
          break;
        case "4":
          if ($column == "TUOTENUMERO") $chk_tuoteno = $key;
          $toimikentta = "toimittajanro";
          $tuotenokentta = "tuoteno";
          break;
        case "5":
          if ($column == "TOIM_TUOTENO") $chk_toim_tuoteno = $key;
          $toimikentta = "toimittajanro";
          $tuotenokentta = "toim_tuoteno";
          break;
        case "6":
          if ($column == "TUOTENUMERO") $chk_tuoteno = $key;
          $toimikentta = "tunnus";
          $tuotenokentta = "tuoteno";
          break;
        case "7":
          if ($column == "TOIM_TUOTENO") $chk_toim_tuoteno = $key;
          $toimikentta = "tunnus";
          $tuotenokentta = "toim_tuoteno";
          break;
        }
      }
    }

    if (is_int($chk_toim_tuoteno) or is_int($chk_tuoteno)) {

      // Vaihdetaan otsikko
      unset($taulunotsikot["tuotteen_toimittajat_pakkauskoot"][$chk_tuoteno]);
      unset($taulunotsikot["tuotteen_toimittajat_pakkauskoot"][$chk_toim_tuoteno]);

      // Muutetaan arvot
      foreach ($taulunrivit["tuotteen_toimittajat_pakkauskoot"] as $ind => $rivit) {
        $chk_tunnus_val = $taulunrivit["tuotteen_toimittajat_pakkauskoot"][$ind][$chk_tunnus];

        if (is_int($chk_tuoteno)) {
          $chk_tuoteno_val = $taulunrivit["tuotteen_toimittajat_pakkauskoot"][$ind][$chk_tuoteno];
        }
        else {
          $chk_tuoteno_val = $taulunrivit["tuotteen_toimittajat_pakkauskoot"][$ind][$chk_toim_tuoteno];
        }

        $query = "SELECT tt.tunnus
                  FROM tuotteen_toimittajat AS tt
                  JOIN toimi ON (toimi.yhtio = tt.yhtio AND toimi.{$toimikentta} = '{$chk_tunnus_val}')
                  WHERE tt.yhtio      = '{$kukarow['yhtio']}'
                  AND tt.{$tuotenokentta} = '{$chk_tuoteno_val}'
                  AND tt.liitostunnus = toimi.tunnus";
        $chk_tunnus_res = pupe_query($query);

        if (mysql_num_rows($chk_tunnus_res) == 1) {
          $chk_tunnus_row = mysql_fetch_assoc($chk_tunnus_res);

          $taulunrivit["tuotteen_toimittajat_pakkauskoot"][$ind][$chk_tunnus] = $chk_tunnus_row['tunnus'];
        }
        else {
          $taulunrivit["tuotteen_toimittajat_pakkauskoot"][$ind][$chk_tunnus] = "";
        }

        unset($taulunrivit["tuotteen_toimittajat_pakkauskoot"][$ind][$chk_tuoteno]);
        unset($taulunrivit["tuotteen_toimittajat_pakkauskoot"][$ind][$chk_toim_tuoteno]);
      }
    }
  }

  if (in_array("tuotteen_toimittajat_tuotenumerot", $taulut)) {

    $chk_tuoteno = $chk_toim_tuoteno = "x";

    foreach ($taulunotsikot["tuotteen_toimittajat_tuotenumerot"] as $key => $column) {

      if (isset($toimitunnusvalinta) and $toimitunnusvalinta != 1) {

        if ($column == "TOIM_TUOTENO_TUNNUS") $chk_tunnus = $key;

        switch ($toimitunnusvalinta) {
        case "2":
          if ($column == "TUOTENUMERO") $chk_tuoteno = $key;
          $toimikentta = "ytunnus";
          $tuotenokentta = "tuoteno";
          break;
        case "3":
          if ($column == "TOIM_TUOTENO") $chk_toim_tuoteno = $key;
          $toimikentta = "ytunnus";
          $tuotenokentta = "toim_tuoteno";
          break;
        case "4":
          if ($column == "TUOTENUMERO") $chk_tuoteno = $key;
          $toimikentta = "toimittajanro";
          $tuotenokentta = "tuoteno";
          break;
        case "5":
          if ($column == "TOIM_TUOTENO") $chk_toim_tuoteno = $key;
          $toimikentta = "toimittajanro";
          $tuotenokentta = "toim_tuoteno";
          break;
        case "6":
          if ($column == "TUOTENUMERO") $chk_tuoteno = $key;
          $toimikentta = "tunnus";
          $tuotenokentta = "tuoteno";
          break;
        case "7":
          if ($column == "TOIM_TUOTENO") $chk_toim_tuoteno = $key;
          $toimikentta = "tunnus";
          $tuotenokentta = "toim_tuoteno";
          break;
        }
      }
    }

    if (is_int($chk_toim_tuoteno) or is_int($chk_tuoteno)) {

      // Vaihdetaan otsikko
      unset($taulunotsikot["tuotteen_toimittajat_tuotenumerot"][$chk_tuoteno]);
      unset($taulunotsikot["tuotteen_toimittajat_tuotenumerot"][$chk_toim_tuoteno]);

      // Muutetaan arvot
      foreach ($taulunrivit["tuotteen_toimittajat_tuotenumerot"] as $ind => $rivit) {
        $chk_tunnus_val = $taulunrivit["tuotteen_toimittajat_tuotenumerot"][$ind][$chk_tunnus];

        if (is_int($chk_tuoteno)) {
          $chk_tuoteno_val = $taulunrivit["tuotteen_toimittajat_tuotenumerot"][$ind][$chk_tuoteno];
        }
        else {
          $chk_tuoteno_val = $taulunrivit["tuotteen_toimittajat_tuotenumerot"][$ind][$chk_toim_tuoteno];
        }

        $query = "SELECT tt.tunnus
                  FROM tuotteen_toimittajat AS tt
                  JOIN toimi ON (toimi.yhtio = tt.yhtio AND toimi.{$toimikentta} = '{$chk_tunnus_val}')
                  WHERE tt.yhtio      = '{$kukarow['yhtio']}'
                  AND tt.{$tuotenokentta} = '{$chk_tuoteno_val}'
                  AND tt.liitostunnus = toimi.tunnus";
        $chk_tunnus_res = pupe_query($query);

        if (mysql_num_rows($chk_tunnus_res) == 1) {
          $chk_tunnus_row = mysql_fetch_assoc($chk_tunnus_res);

          $taulunrivit["tuotteen_toimittajat_tuotenumerot"][$ind][$chk_tunnus] = $chk_tunnus_row['tunnus'];
        }
        else {
          $taulunrivit["tuotteen_toimittajat_tuotenumerot"][$ind][$chk_tunnus] = "";
        }

        unset($taulunrivit["tuotteen_toimittajat_tuotenumerot"][$ind][$chk_tuoteno]);
        unset($taulunrivit["tuotteen_toimittajat_tuotenumerot"][$ind][$chk_toim_tuoteno]);
      }
    }
  }

  // Korjataan spessujoini yhteensopivuus_tuote_lisatiedot/yhteensopivuus_tuote
  if (in_array("yhteensopivuus_tuote", $taulut) and in_array("yhteensopivuus_tuote_lisatiedot", $taulut)) {

    foreach ($taulunotsikot["yhteensopivuus_tuote_lisatiedot"] as $key => $column) {
      if ($column == "TUOTENO") {
        $joinsarake = $key;
        break;
      }
    }

    // Vaihdetaan otsikko
    $taulunotsikot["yhteensopivuus_tuote_lisatiedot"][$joinsarake] = "YHTEENSOPIVUUS_TUOTE_TUNNUS";

    // Tyhjennet��n arvot
    foreach ($taulunrivit["yhteensopivuus_tuote_lisatiedot"] as $ind => $rivit) {
      $taulunrivit["yhteensopivuus_tuote_lisatiedot"][$ind][$joinsarake] = "";
    }
  }

  if (in_array("yhteensopivuus_tuote_sensori", $taulut) and in_array("yhteensopivuus_tuote_sensori_lisatiedot", $taulut)) {

    foreach ($taulunotsikot["yhteensopivuus_tuote_sensori_lisatiedot"] as $key => $column) {
      if ($column == "TUOTENO") {
        $joinsarake = $key;
        break;
      }
    }

    // Vaihdetaan otsikko
    $taulunotsikot["yhteensopivuus_tuote_sensori_lisatiedot"][$joinsarake] = "YHTEENSOPIVUUS_TUOTE_SENSORI_TUNNUS";

    // Tyhjennet��n arvot
    foreach ($taulunrivit["yhteensopivuus_tuote_sensori_lisatiedot"] as $ind => $rivit) {
      $taulunrivit["yhteensopivuus_tuote_sensori_lisatiedot"][$ind][$joinsarake] = "";
    }
  }

  /*
  foreach ($taulunrivit as $taulu => $rivit) {

    list($table_mysql, ) = explode(".", $taulu);
    $table_mysql = preg_replace("/__[0-9]*$/", "", $table_mysql);

    echo "<table>";
    echo "<tr><th>$table_mysql</th>";
    foreach ($taulunotsikot[$taulu] as $key => $column) {
      echo "<th>$key => $column</th>";
    }
    echo "</tr>";
    for ($eriviindex = 0; $eriviindex < count($rivit); $eriviindex++) {
      echo "<tr><th>$table_mysql</th>";
      foreach ($rivit[$eriviindex] as $eriv) {
        echo "<td>$eriv</td>";
      }
      echo "</tr>";
    }
    echo "</table><br>";
  }
  exit;
  */

  // REST-api ei salli etenemispalkkia
  if ((!$cli or $lue_data_output_file != "") and !isset($api_kentat)) {
    require 'inc/ProgressBar.class.php';
  }

  if (isset($toimipaikkavalinta)) {
    $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio'], $toimipaikkavalinta);
  }

  // Otetaan pupen talut haltuun
  $query  = "SHOW TABLES FROM `$dbkanta`";
  $tableresult = pupe_query($query);

  $taulunrivit_keys = array_keys($taulunrivit);

  for ($tril = 0; $tril < count($taulunrivit); $tril++) {

    $taulu = $taulunrivit_keys[$tril];

    $vikaa        = 0;
    $tarkea        = 0;
    $wheretarkea    = 0;
    $kielletty      = 0;
    $lask        = 0;
    $postoiminto    = 'X';
    $table_mysql     = "";
    $tarkyhtio      = "";
    $tarkylisa       = 1;
    $indeksi      = array();
    $indeksi_where    = array();
    $trows        = array();
    $tlength      = array();
    $tdecimal      = array();
    $apu_sarakkeet    = array();
    $rivimaara       = count($taulunrivit[$taulu]);
    $dynaamiset_rivit  = array();

    // Siivotaan joinit ja muut pois tietokannan nimest�
    list($table_mysql, ) = explode(".", $taulu);
    $table_mysql = preg_replace("/__[0-9]*$/", "", $table_mysql);

    if (substr($table_mysql, 0, 11) == 'puun_alkio_') {
      $table_tarkenne = substr($table_mysql, 11);
      $table_mysql = 'puun_alkio';
    }

    // jos tullaan jotenkin hassusti, nmiin ei tehd� mit��n
    if (trim($table_mysql) == "") continue;


    // Katotaan, ett� valittu taulu on validi
    mysql_data_seek($tableresult, 0);

    $validtable = FALSE;

    while ($tables = mysql_fetch_row($tableresult)) {
      if ($tables[0] == $table_mysql) $validtable = TRUE;
    }

    if ($validtable) {

      // Haetaan valitun taulun sarakkeet
      $query = "SHOW COLUMNS FROM $table_mysql";
      $fres  = pupe_query($query);

      while ($row = mysql_fetch_array($fres)) {
        // Pushataan arrayseen kaikki sarakenimet ja tietuetyypit
        $trows[$table_mysql.".".strtoupper($row[0])] = $row[1];

        $tlengthpit = preg_replace("/[^0-9,]/", "", $row[1]);

        if (strpos($tlengthpit, ",") !== FALSE) {
          // Otetaan desimaalien m��r� talteen
          $tdecimal[$table_mysql.".".strtoupper($row[0])] = (int) substr($tlengthpit, strpos($tlengthpit, ",")+1);
          $tlengthpit = substr($tlengthpit, 0, strpos($tlengthpit, ",")+1)+1;
        }

        if (substr($row[1], 0, 7) == "decimal" or substr($row[1], 0, 3) == "int") {
          // Sallitaan my�s miinusmerkki...
          $tlengthpit++;
        }

        $tlength[$table_mysql.".".strtoupper($row[0])] = trim($tlengthpit);
      }

      // N�m� ovat pakollisia dummysarakkeita jotka ohitetaan lopussa automaattisesti!
      if (in_array($table_mysql, array("yhteyshenkilo", "asiakkaan_avainsanat"))) {
        $apu_sarakkeet = array("YTUNNUS");
      }

      if (count($apu_sarakkeet) > 0) {
        foreach ($apu_sarakkeet as $s) {
          $trows[$table_mysql.".".strtoupper($s)] = "";
        }
      }

      if ($table_mysql == 'tullinimike') {
        $tulli_ei_kielta = "";
        $tulli_ei_toimintoa = "";

        if (in_array("KIELI", $taulunotsikot[$taulu]) === FALSE) {
          $tulli_ei_kielta = "PUUTTUU";
          $taulunotsikot[$taulu][] = "KIELI";
        }
        if (in_array("TOIMINTO", $taulunotsikot[$taulu]) === FALSE) {
          $taulunotsikot[$taulu][] = "TOIMINTO";
          $tulli_ei_toimintoa = "PUUTTUU";
        }
      }

      // Otetaan pakolliset, kielletyt, wherelliset ja eiyhtiota tiedot
      list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattavat, $saakopoistaa, $oletukset) = pakolliset_sarakkeet($table_mysql, $taulunotsikot[$taulu]);

      // $trows sis�lt�� kaikki taulun sarakkeet ja tyypit tietokannasta
      // $taulunotsikot[$taulu] sis�lt�� kaikki sarakkeet saadusta tiedostosta

      foreach ($taulunotsikot[$taulu] as $key => $column) {
        if ($column != '') {
          if ($column == "TOIMINTO") {
            //TOIMINTO sarakkeen positio tiedostossa
            $postoiminto = (string) array_search($column, $taulunotsikot[$taulu]);
          }
          else {
            if (!isset($trows[$table_mysql.".".$column]) and $column != "AVK_TUNNUS") {
              lue_data_echo("<font class='error'>".t("Saraketta")." \"$column\" ".t("ei l�ydy")." $table_mysql-".t("taulusta")."!</font><br>");
              $vikaa++;
            }

            // yhtio ja tunnus kentti� ei saa koskaan muokata...
            if ($column == 'YHTIO' or $column == 'TUNNUS') {
              lue_data_echo("<font class='error'>".t("Yhti�- ja tunnussaraketta ei saa muuttaa")." $table_mysql-".t("taulussa")."!</font><br>");
              $vikaa++;
            }

            if (in_array($column, $pakolliset)) {
              // pushataan positio indeksiin, ett� tiedet��n miss� kohtaa avaimet tulevat
              $pos = array_search($column, $taulunotsikot[$taulu]);
              $indeksi[$column] = $pos;
              $tarkea++;
            }

            if (in_array($column, $kielletyt)) {
              // katotaan ettei kiellettyj� sarakkeita muuteta
              $viesti .= t("Sarake").": $column ".t("on kielletty sarake")." $table_mysql-".t("taulussa")."!<br>";
              $kielletty++;
            }

            if (is_array($wherelliset) and in_array($column, $wherelliset)) {
              // katotaan ett� m��ritellyt where lausekkeen ehdot l�ytyv�t
              $pos = array_search($column, $taulunotsikot[$taulu]);
              $indeksi_where[$column] = $pos;
              $wheretarkea++;
            }
          }
        }
        else {
          $vikaa++;
          lue_data_echo("<font class='error'>".t("Tiedostossa on tyhji� sarakkeiden otsikoita")."!</font><br>");
        }
      }
    }
    else {
      $vikaa++;
      lue_data_echo("<font class='error'>".t("VIRHE: Sarakeotsikko viittaa tauluun jota ei ole olemassa").": '$table_mysql'!</font><br>");
    }

    // Oli virheellisi� sarakkeita tai pakollisia ei l�ytynyt..
    if ($vikaa != 0 or $tarkea != count($pakolliset) or $postoiminto == 'X' or $kielletty > 0 or (is_array($wherelliset) and $wheretarkea != count($wherelliset))) {

      if ($vikaa != 0) {
        lue_data_echo("<font class='error'>".t("V��ri� sarakkeita tai yritit muuttaa yhti�/tunnus saraketta")."!</font><br>");
      }

      if ($tarkea != count($pakolliset)) {
        $pakolliset_text = "<font class='error'>".t("Pakollisia/t�rkeit� kentti� puuttuu")."! ( ";

        foreach ($pakolliset as $apupako) {
          $pakolliset_text .= "$apupako ";
        }

        $pakolliset_text .= " ) $table_mysql-".t("taulusta")."!</font><br>";
        lue_data_echo($pakolliset_text);
      }

      if ($postoiminto == 'X') {
        lue_data_echo("<font class='error'>".t("Toiminto sarake puuttuu")."!</font><br>");
      }

      if ($kielletty > 0) {
        lue_data_echo("<font class='error'>".t("Yrit�t p�ivitt�� kiellettyj� sarakkeita")." $table_mysql-".t("taulussa")."!</font><br>$viesti");
      }

      if (is_array($wherelliset) and $wheretarkea != count($wherelliset)) {
        $pakolliset_text = "<font class='error'>".t("Sinulta puuttui jokin pakollisista sarakkeista")." (";

        foreach ($wherelliset as $apupako) {
          $pakolliset_text .= "$apupako ";
        }

        $pakolliset_text .= ") $table_mysql-".t("taulusta")."!</font><br>";
        lue_data_echo($pakolliset_text);
      }

      lue_data_echo("<font class='error'>".t("Virheit� l�ytyi. Ei voida jatkaa")."!<br></font>");
      if ($lue_data_output_file != "") {
        lue_data_echo("## LUE-DATA-EOF ##");
      }

      if (!isset($api_kentat)) {
        lue_data_echo($lue_data_output_text, true);
        require "inc/footer.inc";
        exit;
      }
      else {
        // Jos tullaan api.php:st� ja p��dyt��n virheeseen, t�ll� estet��n ettei menn� for-looppiin riville 650
        // EI voida sanoa EXIT tai DIE koska api.php pit�� menn� loppuun.
        $taulunrivit[$taulu] = array();
        $api_status = FALSE;
      }
    }

    lue_data_echo("<br><font class='message'>".t("Tiedosto ok, aloitetaan p�ivitys")." $table_mysql-".t("tauluun")."...<br></font>");
    lue_data_echo($lue_data_output_text, true);

    $lue_data_output_text   = "";
    $rivilaskuri       = 1;
    $puun_alkio_index_plus   = 0;

    // REST-api ei salli etenemispalkkia
    if ((!$cli or $lue_data_output_file != "") and !isset($api_kentat)) {
      $bar = new ProgressBar();
      $bar->initialize($rivimaara);
    }

    $lisatyt_indeksit = array();

    for ($eriviindex = 0; $eriviindex < ($rivimaara + $puun_alkio_index_plus); $eriviindex++) {

      // Komentorivill� piirret��n progressbar, ellei ole output loggaus p��ll�
      // REST-api skippaa
      if (!isset($api_kentat)) {
        if ($cli and $lue_data_output_file == "") {
          progress_bar($eriviindex, $max_rivit);
        }
        elseif (!$cli or $lue_data_output_file != "") {
          $bar->increase();
        }
      }

      $hylkaa    = 0;
      $tila      = "";
      $tee       = "";
      $epakurpvm = "";
      $eilisataeikamuuteta = "";
      $rivilaskuri++;

      //asiakashinta/asiakasalennus/toimittajahinta/toimittajaalennus spessuja
      $chasiakas_ryhma   = '';
      $chytunnus       = '';
      $chryhma       = '';
      $chtuoteno       = '';
      $chasiakas      = 0;
      $chsegmentti    = 0;
      $chpiiri      = '';
      $chminkpl      = 0;
      $chmaxkpl      = 0;
      $chalennuslaji    = 0;
      $chmonikerta    = "";
      $chalkupvm       = '0000-00-00';
      $chloppupvm     = '0000-00-00';
      $and         = '';
      $tpupque       = '';
      $toimi_liitostunnus = '';
      $chtoimittaja    = '';
      $tuoteno      = '';
      $toim_tuoteno    = '';

      if ($eiyhtiota == "" or $eiyhtiota == "EILAATIJAA") {
        $valinta   = " yhtio = '{$kukarow['yhtio']}'";
      }
      elseif ($eiyhtiota == "TRIP") {
        $valinta   = " tunnus > 0 ";
      }

      if ($table_mysql == 'tullinimike' and $tulli_ei_kielta != "") {
        $taulunrivit[$taulu][$eriviindex][] = "FI";
      }

      if ($table_mysql == 'tullinimike' and $tulli_ei_toimintoa != "") {
        $taulunrivit[$taulu][$eriviindex][] = "LISAA";
      }

      // Rivin toiminto
      $taulunrivit[$taulu][$eriviindex][$postoiminto] = strtoupper(trim($taulunrivit[$taulu][$eriviindex][$postoiminto]));

      //Sallitaan my�s MUOKKAA ja LIS�� toiminnot
      if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == "LIS��")     $taulunrivit[$taulu][$eriviindex][$postoiminto] = "LISAA";
      if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == "MUOKKAA")     $taulunrivit[$taulu][$eriviindex][$postoiminto] = "MUUTA";
      if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == "MUOKKAA/LIS��") $taulunrivit[$taulu][$eriviindex][$postoiminto] = "MUUTA/LISAA";
      if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == "MUOKKAA/LISAA") $taulunrivit[$taulu][$eriviindex][$postoiminto] = "MUUTA/LISAA";
      if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == "MUUTA/LIS��")   $taulunrivit[$taulu][$eriviindex][$postoiminto] = "MUUTA/LISAA";
      if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == "POISTA")     $taulunrivit[$taulu][$eriviindex][$postoiminto] = "POISTA";

      //Jos eri where-ehto array on m��ritelty
      if (is_array($wherelliset)) {
        $indeksi = array_merge($indeksi, $indeksi_where);
        $indeksi = array_unique($indeksi);
      }

      // Lis�t��n taulun oletusarvot, jos ollaan lis��m�ss� uutta tietuetta
      if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == "LISAA") {
        foreach ($oletukset as $oletus_kentta => $oletus_arvo) {
          // Etsit��n taulunotsikot arrayst� KEY, jonka arvo on oletuskentt�
          $oletus_positio = array_keys($taulunotsikot[$taulu], $oletus_kentta, true);

          // Kentt� l�ytyy taulukosta ja se on tyhj�, laitetaan siihen oletusarvo
          // Jos kentt�� EI L�YDY, niin lis�t��n se muiden oletusten kanssa alempana
          if (count($oletus_positio) == 1 and $taulunrivit[$taulu][$eriviindex][$oletus_positio[0]] == "") {
            $taulunrivit[$taulu][$eriviindex][$oletus_positio[0]] = $oletus_arvo;
          }
        }
      }

      $avkmuuttuja = FALSE;

      foreach ($indeksi as $j) {
        if ($taulunotsikot[$taulu][$j] == "TUOTENO") {

          $tuoteno = $taulunrivit[$taulu][$eriviindex][$j];

          $valinta .= " and TUOTENO='$tuoteno'";
        }
        elseif ($table_mysql == 'autoid_lisatieto' and $lue_data_autoid and ($taulunrivit[$taulu][$eriviindex][$postoiminto] == "POISTA" or $taulunrivit[$taulu][$eriviindex][$postoiminto] == "MUUTA") and !in_array($eriviindex, $lisatyt_indeksit)) {
          $tee = "pre_rivi_loop";
          require "lue_data_autoid.php";
        }
        elseif ($taulunotsikot[$taulu][$j] == "TOIM_TUOTENO") {
          $toim_tuoteno = $taulunrivit[$taulu][$eriviindex][$j];

          $valinta .= " and TOIM_TUOTENO='{$toim_tuoteno}'";
        }
        elseif ($table_mysql == 'tullinimike' and strtoupper($taulunotsikot[$taulu][$j]) == "CN") {

          $taulunrivit[$taulu][$eriviindex][$j] = str_replace(' ', '', $taulunrivit[$taulu][$eriviindex][$j]);

          $valinta .= " and cn = '{$taulunrivit[$taulu][$eriviindex][$j]}' ";

          if ($taulunrivit[$taulu][$eriviindex][$j] == '') {
            $tila = 'ohita';
          }
        }
        elseif ($table_mysql == 'extranet_kayttajan_lisatiedot' and strtoupper($taulunotsikot[$taulu][$j]) == "LIITOSTUNNUS" and $liitostunnusvalinta == 2) {
          $query = "SELECT tunnus
                    FROM kuka
                    WHERE yhtio   = '$kukarow[yhtio]'
                    and extranet != ''
                    and kuka      = '{$taulunrivit[$taulu][$eriviindex][$j]}'";
          $apures = pupe_query($query);

          if (mysql_num_rows($apures) == 1) {
            $apurivi = mysql_fetch_assoc($apures);

            $taulunrivit[$taulu][$eriviindex][$j] = $apurivi["tunnus"];

            $valinta .= " and {$taulunotsikot[$taulu][$j]} = '$apurivi[tunnus]' ";
          }
          else {
            // Ei l�ydy, trigger�id��n virhe
            $taulunrivit[$taulu][$eriviindex][$j] = "XXX";
            $valinta .= " and {$taulunotsikot[$taulu][$j]} = 'XXX' ";
          }
        }
        elseif ($table_mysql == 'sanakirja' and $taulunotsikot[$taulu][$j] == "FI") {
          // jos ollaan mulkkaamassa RU ni tehd��n utf-8 -> latin-1 konversio FI kent�ll�
          if (in_array("RU", $taulunotsikot[$taulu])) {
            $taulunrivit[$taulu][$eriviindex][$j] = iconv("UTF-8", "ISO-8859-1", $taulunrivit[$taulu][$eriviindex][$j]);
          }

          $valinta .= " and {$taulunotsikot[$taulu][$j]} = BINARY '{$taulunrivit[$taulu][$eriviindex][$j]}'";
        }
        elseif ($table_mysql == 'tuotepaikat' and $taulunotsikot[$taulu][$j] == "OLETUS") {
          //ei haluta t�t� t�nne
        }
        elseif (in_array($table_mysql, array('yhteensopivuus_tuote_lisatiedot', 'yhteensopivuus_tuote_sensori_lisatiedot'))
          and in_array($taulunotsikot[$taulu][$j], array("YHTEENSOPIVUUS_TUOTE_TUNNUS", "YHTEENSOPIVUUS_TUOTE_SENSORI_TUNNUS"))
          and $taulunrivit[$taulu][$eriviindex][$j] == "") {

          if (in_array("yhteensopivuus_tuote_sensori", $taulut)) {
            $yhteensopivuus_taulun_nimi = "yhteensopivuus_tuote_sensori";

            $_sensori = array_search("SENSORITUOTENO", $taulunotsikot["yhteensopivuus_tuote_sensori"]);
            $_sensori = $taulunrivit["yhteensopivuus_tuote_sensori"][$eriviindex][$_sensori];

            $_sensoriryhma = array_search("SENSORIRYHMA", $taulunotsikot["yhteensopivuus_tuote_sensori"]);
            $_sensoriryhma = $taulunrivit["yhteensopivuus_tuote_sensori"][$eriviindex][$_sensoriryhma];

            $_wherelisa  = "and sensorituoteno = '{$_sensori}' ";
            $_wherelisa .= "and sensoriryhma = '{$_sensoriryhma}'";
          }
          else {
            $yhteensopivuus_taulun_nimi = "yhteensopivuus_tuote";
            $_wherelisa = "";
          }

          $_tyyppi = array_search("TYYPPI", $taulunotsikot[$yhteensopivuus_taulun_nimi]);
          $_atunnus = array_search("ATUNNUS", $taulunotsikot[$yhteensopivuus_taulun_nimi]);
          $_tuoteno = array_search("TUOTENO", $taulunotsikot[$yhteensopivuus_taulun_nimi]);

          $_tyyppi = $taulunrivit[$yhteensopivuus_taulun_nimi][$eriviindex][$_tyyppi];
          $_atunnus = $taulunrivit[$yhteensopivuus_taulun_nimi][$eriviindex][$_atunnus];
          $_tuoteno = $taulunrivit[$yhteensopivuus_taulun_nimi][$eriviindex][$_tuoteno];

          // Hetaan liitostunnus yhteensopivuus_tuote-taulusta
          $apusql = "SELECT tunnus
                     FROM {$yhteensopivuus_taulun_nimi}
                     WHERE yhtio = '$kukarow[yhtio]'
                     and tyyppi  = '{$_tyyppi}'
                     and atunnus = '{$_atunnus}'
                     and tuoteno = '{$_tuoteno}'
                     {$_wherelisa}";
          $apures = pupe_query($apusql);

          if (mysql_num_rows($apures) == 1) {
            $apurivi = mysql_fetch_assoc($apures);

            $taulunrivit[$taulu][$eriviindex][$j] = $apurivi["tunnus"];

            $valinta .= " and {$taulunotsikot[$taulu][$j]} = '$apurivi[tunnus]' ";
          }
        }
        elseif ($table_mysql == 'puun_alkio') {

          // voidaan vaan lis�t� puun alkioita
          if ($taulunrivit[$taulu][$eriviindex][$postoiminto] != "LISAA" and $taulunrivit[$taulu][$eriviindex][$postoiminto] != "POISTA") {
            $tila = 'ohita';
          }

          if ($tila != 'ohita' and $taulunotsikot[$taulu][$j] == "PUUN_TUNNUS") {

            // jos ollaan valittu koodi puun_tunnuksen sarakkeeksi, niin haetaan dynaamisesta puusta tunnus koodilla
            if ($dynaamisen_taulun_liitos == 'koodi') {

              $query_x = "SELECT tunnus
                          FROM dynaaminen_puu
                          WHERE yhtio = '{$kukarow['yhtio']}'
                          AND laji    = '{$table_tarkenne}'
                          AND koodi   = '{$taulunrivit[$taulu][$eriviindex][$j]}'";
              $koodi_tunnus_res = pupe_query($query_x);

              // jos tunnusta ei l�ydy, ohitetaan kyseinen rivi
              if (mysql_num_rows($koodi_tunnus_res) == 0) {
                $tila = 'ohita';
              }
              else {
                $koodi_tunnus_row = mysql_fetch_assoc($koodi_tunnus_res);
                $valinta .= " and puun_tunnus = '{$koodi_tunnus_row['tunnus']}' ";
              }
            }
            else {
              $valinta .= " and puun_tunnus = '{$taulunrivit[$taulu][$eriviindex][$j]}' ";
            }
          }
          elseif ($tila != 'ohita' and $taulunotsikot[$taulu][$j] == "LIITOS") {
            if ($table_tarkenne == 'asiakas' and $dynaamisen_taulun_liitos != '') {

              $query = "SELECT tunnus
                        FROM asiakas
                        WHERE yhtio  = '{$kukarow['yhtio']}'
                        AND laji    != 'P'
                        AND $dynaamisen_taulun_liitos = '{$taulunrivit[$taulu][$eriviindex][$j]}'";
              $asiakkaan_haku_res = pupe_query($query);

              if (mysql_num_rows($asiakkaan_haku_res) > 0) {
                while ($asiakkaan_haku_row = mysql_fetch_assoc($asiakkaan_haku_res)) {

                  $rivi_array_x = array();

                  foreach ($taulunotsikot[$taulu] as $indexi_x => $columnin_nimi_x) {
                    switch ($columnin_nimi_x) {
                    case 'LIITOS':
                      $rivi_array_x[] = $asiakkaan_haku_row['tunnus'];
                      break;
                    default:
                      $rivi_array_x[] = $taulunrivit[$taulu][$eriviindex][$indexi_x];
                    }
                  }

                  array_push($dynaamiset_rivit, $rivi_array_x);
                  $puun_alkio_index_plus++;
                }

                unset($taulunrivit[$taulu][$eriviindex]);

                if ($rivimaara == ($eriviindex+1)) {
                  $dynaamisen_taulun_liitos = '';

                  foreach ($dynaamiset_rivit as $dyn_rivi) {
                    array_push($taulunrivit[$taulu], $dyn_rivi);
                  }
                }

                continue 2;
              }
              else {
                $tila = 'ohita';
              }
            }
            else {
              $valinta .= " and liitos = '{$taulunrivit[$taulu][$eriviindex][$j]}' ";
            }
          }
        }
        elseif ($table_mysql == 'asiakas' and stripos($taulunrivit[$taulu][$eriviindex][$postoiminto], 'LISAA') !== FALSE and $taulunotsikot[$taulu][$j] == "YTUNNUS" and $taulunrivit[$taulu][$eriviindex][$j] == "AUTOM") {

          if ($yhtiorow["asiakasnumeroinnin_aloituskohta"] != "") {
            $apu_asiakasnumero = $yhtiorow["asiakasnumeroinnin_aloituskohta"];
          }
          else {
            $apu_asiakasnumero = 0;
          }

          //jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksil�iv�t tiedot on oltava konsernitasolla-yksil�lliset
          if ($tarkyhtio == "") {
            $query = "SELECT *
                      FROM yhtio
                      JOIN yhtion_parametrit ON yhtion_parametrit.yhtio = yhtio.yhtio
                      where konserni = '$yhtiorow[konserni]'
                      and (synkronoi = 'asiakas' or synkronoi like 'asiakas,%' or synkronoi like '%,asiakas,%' or synkronoi like '%,asiakas')";
            $vresult = pupe_query($query);

            if (mysql_num_rows($vresult) > 0) {
              // haetaan konsernifirmat
              $query = "SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
                        FROM yhtio
                        JOIN yhtion_parametrit ON yhtion_parametrit.yhtio = yhtio.yhtio
                        where konserni = '$yhtiorow[konserni]'
                        and (synkronoi = 'asiakas' or synkronoi like 'asiakas,%' or synkronoi like '%,asiakas,%' or synkronoi like '%,asiakas')";
              $vresult = pupe_query($query);
              $srowapu = mysql_fetch_array($vresult);
              $tarkyhtio = $srowapu["yhtiot"];
            }
            else {
              $tarkyhtio = "'$kukarow[yhtio]'";
            }
          }

          $query = "SELECT MAX(asiakasnro+0) asiakasnro
                    FROM asiakas USE INDEX (asno_index)
                    WHERE yhtio in ($tarkyhtio)
                    AND asiakasnro+0 >= $apu_asiakasnumero";
          $vresult = pupe_query($query);
          $vrow = mysql_fetch_assoc($vresult);

          if ($vrow['asiakasnro'] != '') {
            $apu_ytunnus = $vrow['asiakasnro'] + $tarkylisa;
            $tarkylisa++;
          }
          else {
            $apu_ytunnus = $tarkylisa;
            $tarkylisa++;
          }

          // P�ivitet��n generoitu arvo kaikkiin muuttujiin...
          $taulunrivit[$taulu][$eriviindex][$j] = $apu_ytunnus;

          foreach ($taulunotsikot as $autotaulu => $autojoinit) {
            if (in_array("YTUNNUS", $joinit) and $autotaulu != $taulut[$j] and $taulu == $joinattavat[$autotaulu]["YTUNNUS"]) {
              $taulunrivit[$autotaulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$autotaulu])] = $apu_ytunnus;
            }
          }

          $valinta .= " and {$taulunotsikot[$taulu][$j]} = '$apu_ytunnus' ";
        }
        elseif ($table_mysql == 'auto_vari_korvaavat') {

          if ($taulunotsikot[$taulu][$j] == "AVK_TUNNUS") {
            $valinta = " yhtio = '$kukarow[yhtio]' and tunnus = '{$taulunrivit[$taulu][$eriviindex][$j]}' ";

            $apu_sarakkeet = array("AVK_TUNNUS");
            $avkmuuttuja = TRUE;
          }
          elseif (!$avkmuuttuja) {
            $valinta .= " and {$taulunotsikot[$taulu][$j]} = '{$taulunrivit[$taulu][$eriviindex][$j]}' ";
          }
        }
        elseif ($table_mysql == 'tuotteen_toimittajat' and $taulunotsikot[$taulu][$j] == 'LIITOSTUNNUS') {
          if (isset($toimittajavalinta) and $toimittajavalinta == 3) {
            $tpque = "SELECT tunnus
                      FROM toimi
                      WHERE yhtio        = '{$kukarow['yhtio']}'
                      AND toimittajanro  = '{$taulunrivit[$taulu][$eriviindex][$j]}'
                      AND toimittajanro != ''
                      AND tyyppi        != 'P'";
            $tpres = pupe_query($tpque);
          }
          elseif (isset($toimittajavalinta) and $toimittajavalinta == 2) {
            $tpque = "SELECT tunnus
                      FROM toimi
                      WHERE yhtio  = '{$kukarow['yhtio']}'
                      AND ytunnus  = '{$taulunrivit[$taulu][$eriviindex][$j]}'
                      AND ytunnus != ''
                      AND tyyppi  != 'P'";
            $tpres = pupe_query($tpque);
          }
          else {
            $tpque = "SELECT tunnus
                      FROM toimi
                      WHERE yhtio  = '{$kukarow['yhtio']}'
                      AND tunnus   = '{$taulunrivit[$taulu][$eriviindex][$j]}'
                      AND tyyppi  != 'P'";
            $tpres = pupe_query($tpque);
          }

          if (mysql_num_rows($tpres) != 1) {
            lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Toimittajaa")." '{$taulunrivit[$taulu][$eriviindex][$j]}' ".t("ei l�ydy! Tai samalla ytunnuksella l�ytyy useita toimittajia! Lis�� toimittajan tunnus LIITOSTUNNUS-sarakkeeseen. Rivi� ei p�ivitetty/lis�tty")."! ".t("TUOTENO")." = $tuoteno<br>");
            $tila = 'ohita';
          }
          else {
            $tpttrow = mysql_fetch_array($tpres);

            // Tarvitaan tarkista.inc failissa
            $toimi_liitostunnus = $tpttrow["tunnus"];

            $taulunrivit[$taulu][$eriviindex][$j] = $tpttrow["tunnus"];

            $valinta .= " and {$taulunotsikot[$taulu][$j]} = '$tpttrow[tunnus]' ";
          }
        }
        elseif ($table_mysql == 'laitteen_sopimukset' and $taulunotsikot[$taulu][$j] == 'SOPIMUSRIVIN_TUNNUS') {
          // Otetaan talteen muutetut sopimusrivitunnukset
          $muutetut_sopimusrivitunnukset[$taulunrivit[$taulu][$eriviindex][$j]] = $taulunrivit[$taulu][$eriviindex][$j];
          $valinta .= " and {$taulunotsikot[$taulu][$j]} = '{$taulunrivit[$taulu][$eriviindex][$j]}' ";
        }
        else {
          $valinta .= " and {$taulunotsikot[$taulu][$j]} = '{$taulunrivit[$taulu][$eriviindex][$j]}' ";
        }

        // jos pakollinen tieto puuttuu kokonaan
        if ($taulunrivit[$taulu][$eriviindex][$j] == "" and in_array($taulunotsikot[$taulu][$j], $pakolliset)) {
          $tila = 'ohita';
        }
      }

      if (substr($taulu, 0, 11) == 'puun_alkio_') {
        $valinta .= " and laji = '".substr($taulu, 11)."' ";
      }

      // jos ei ole puuttuva tieto etsit��n rivi�
      if ($tila != 'ohita') {

        // Lis�t��n hardkoodattu lis�ehto, ett� saldon pit�� olla nolla
        if ($table_mysql == 'tuotepaikat' and $taulunrivit[$taulu][$eriviindex][$postoiminto] == 'POISTA') {
          $valinta .= " and saldo = 0 ";
        }

        if (in_array($table_mysql, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri")) and (!in_array("LIITOSTUNNUS", $taulunotsikot[$taulu]) or (in_array("LIITOSTUNNUS", $taulunotsikot[$taulu]) and $taulunrivit[$taulu][$eriviindex][array_search("LIITOSTUNNUS", $taulunotsikot[$taulu])] == ""))) {

          if ((in_array("YTUNNUS", $taulunotsikot[$taulu]) and ($table_mysql == "yhteyshenkilo" or $table_mysql == "asiakkaan_avainsanat")) or (in_array("ASIAKAS", $taulunotsikot[$taulu]) and $table_mysql == "kalenteri")) {

            if ($taulunrivit[$taulu][$eriviindex][array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
              $tpque = "SELECT tunnus
                        from toimi
                        where yhtio  = '$kukarow[yhtio]'
                        and ytunnus  = '".$taulunrivit[$taulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$taulu])]."'
                        and tyyppi  != 'P'";
              $tpres = pupe_query($tpque);
            }
            elseif (($taulunrivit[$taulu][$eriviindex][array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat") {
              $tpque = "SELECT tunnus
                        from asiakas
                        where yhtio = '$kukarow[yhtio]'
                        and ytunnus = '".$taulunrivit[$taulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$taulu])]."'";
              $tpres = pupe_query($tpque);
            }
            elseif ($table_mysql == "kalenteri") {
              $tpque = "SELECT tunnus
                        from asiakas
                        where yhtio = '$kukarow[yhtio]'
                        and ytunnus = '".$taulunrivit[$taulu][$eriviindex][array_search("ASIAKAS", $taulunotsikot[$taulu])]."'";
              $tpres = pupe_query($tpque);
            }

            if (mysql_num_rows($tpres) == 0) {
              if ($taulunrivit[$taulu][$eriviindex][array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
                lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Toimittajaa")." '".$taulunrivit[$taulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("ei l�ydy")."!<br>");
              }
              elseif (($taulunrivit[$taulu][$eriviindex][array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat") {
                lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Asiakasta")." '".$taulunrivit[$taulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("ei l�ydy")."!<br>");
              }
              else {
                lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Asiakasta")." '".$taulunrivit[$taulu][$eriviindex][array_search("ASIAKAS", $taulunotsikot[$taulu])]."' ".t("ei l�ydy")."!<br>");
              }

              $hylkaa++; // ei p�ivitet� t�t� rivi�
            }
            elseif (mysql_num_rows($tpres) == 1) {
              $tpttrow = mysql_fetch_array($tpres);

              //  Liitet��n pakolliset arvot
              if (!in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {
                $taulunotsikot[$taulu][] = "LIITOSTUNNUS";
              }

              $taulunrivit[$taulu][$eriviindex][] = $tpttrow["tunnus"];

              $valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
            }
            else {

              if ($ytunnustarkkuus == 2) {
                $lasind = count($taulunrivit[$taulu][$eriviindex]);

                //  Liitet��n pakolliset arvot
                if (!in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {
                  $taulunotsikot[$taulu][] = "LIITOSTUNNUS";
                }

                $pushlask = 1;

                while ($tpttrow = mysql_fetch_array($tpres)) {

                  $taulunrivit[$taulu][$eriviindex][$lasind] = $tpttrow["tunnus"];

                  if ($pushlask < mysql_num_rows($tpres)) {
                    $taulunrivit[$taulu][] = $taulunrivit[$taulu][$eriviindex];
                  }

                  $pushlask++;
                }

                $valinta .= " and liitostunnus = '{$taulunrivit[$taulu][$eriviindex][$lasind]}' ";
              }
              else {
                if ($taulunrivit[$taulu][$eriviindex][array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
                  lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Toimittaja")." '".$taulunrivit[$taulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("Samalla ytunnuksella l�ytyy useita toimittajia! Lis�� toimittajan tunnus LIITOSTUNNUS-sarakkeeseen")."!<br>");
                }
                elseif (($taulunrivit[$taulu][$eriviindex][array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat") {
                  lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Asiakas")." '".$taulunrivit[$taulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("Samalla ytunnuksella l�ytyy useita asiakkaita! Lis�� asiakkaan tunnus LIITOSTUNNUS-sarakkeeseen")."!<br>");
                }
                else {
                  lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Asiakas")." '".$taulunrivit[$taulu][$eriviindex][array_search("ASIAKAS", $taulunotsikot[$taulu])]."' ".t("Samalla ytunnuksella l�ytyy useita asiakkaita! Lis�� asiakkaan tunnus LIITOSTUNNUS-sarakkeeseen")."!<br>");
                }

                $hylkaa++; // ei p�ivitet� t�t� rivi�
              }
            }
          }
          else {
            lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Rivi� ei voi lis�t� jos ei tiedet� ainakin YTUNNUSTA!")."<br>");
            $hylkaa++;
          }
        }
        elseif (in_array($table_mysql, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri")) and in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {

          if ($taulunrivit[$taulu][$eriviindex][array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
            $tpque = "SELECT tunnus
                      from toimi
                      where yhtio  = '$kukarow[yhtio]'
                      and tunnus   = '".$taulunrivit[$taulu][$eriviindex][array_search("LIITOSTUNNUS", $taulunotsikot[$taulu])]."'
                      and tyyppi  != 'P'";
            $tpres = pupe_query($tpque);
          }
          elseif (($taulunrivit[$taulu][$eriviindex][array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat" or $table_mysql == "kalenteri") {
            $tpque = "SELECT tunnus
                      from asiakas
                      where yhtio = '$kukarow[yhtio]'
                      and tunnus  = '".$taulunrivit[$taulu][$eriviindex][array_search("LIITOSTUNNUS", $taulunotsikot[$taulu])]."'";
            $tpres = pupe_query($tpque);
          }

          if (mysql_num_rows($tpres) != 1) {
            lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Toimittajaa/Asiakasta")." '{$taulunrivit[$taulu][$eriviindex][$r]}' ".t("ei l�ydy! Rivi� ei p�ivitetty/lis�tty")."!<br>");
            $hylkaa++; // ei p�ivitet� t�t� rivi�
          }
          else {
            $tpttrow = mysql_fetch_array($tpres);

            // Lis�t��n ehtoon
            $valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
          }
        }

        $query = "SELECT *
                  FROM $table_mysql
                  WHERE $valinta";
        $fresult = pupe_query($query);

        $valinta_orig = $valinta;

        if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == "MUUTA/LISAA") {
          // Muutetaan jos l�ytyy muuten lis�t��n!
          if (mysql_num_rows($fresult) == 0) {
            $taulunrivit[$taulu][$eriviindex][$postoiminto] = "LISAA";
          }
          else {
            $taulunrivit[$taulu][$eriviindex][$postoiminto] = "MUUTA";
          }
        }
        elseif ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'LISAA' and $table_mysql != $table and mysql_num_rows($fresult) != 0) {
          // joinattaviin tauluhin tehd��n muuta-operaatio jos rivi l�ytyy
          $taulunrivit[$taulu][$eriviindex][$postoiminto] = "MUUTA";
        }
        elseif ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'MUUTA' and $table_mysql != $table and mysql_num_rows($fresult) == 0) {
          // joinattaviin tauluhin tehd��n lisaa-operaatio jos rivi� ei l�ydy
          $taulunrivit[$taulu][$eriviindex][$postoiminto] = "LISAA";
        }
        elseif ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'LISAA' and mysql_num_rows($fresult) != 0) {
          if ($table_mysql != 'asiakasalennus' and $table_mysql != 'asiakashinta' and $table_mysql != 'toimittajaalennus' and $table_mysql != 'toimittajahinta') {
            lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("VIRHE:")." ".t("Rivi on jo olemassa, ei voida perustaa uutta!")."</font> $valinta<br>");
            $tila = 'ohita';
          }
        }
        elseif ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'MUUTA' and mysql_num_rows($fresult) == 0) {
          if ($table_mysql != 'asiakasalennus' and $table_mysql != 'asiakashinta' and $table_mysql != 'toimittajaalennus' and $table_mysql != 'toimittajahinta') {
            lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Rivi� ei voida muuttaa, koska sit� ei l�ytynyt!")."</font> $valinta<br>");
            $tila = 'ohita';
          }
        }
        elseif ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'POISTA') {

          // Sallitut taulut
          if (!$saakopoistaa) {
            lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Rivin poisto ei sallittu!")."</font> $valinta<br>");
            $tila = 'ohita';
          }
          elseif (mysql_num_rows($fresult) == 0) {
            lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Rivi� ei voida poistaa, koska sit� ei l�ytynyt!")."</font> $valinta<br>");
            $tila = 'ohita';
          }
        }
        elseif ($taulunrivit[$taulu][$eriviindex][$postoiminto] != 'MUUTA' and $taulunrivit[$taulu][$eriviindex][$postoiminto] != 'LISAA') {
          lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Rivi� ei voida k�sitell� koska silt� puuttuu toiminto!")."</font> $valinta<br>");
          $tila = 'ohita';
        }
      }
      else {
        lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Pakollista tietoa puuttuu/tiedot ovat virheelliset!")."</font> $valinta<br>");
      }

      // lis�t��n rivi
      if ($tila != 'ohita') {
        if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'LISAA') {
          if ($eiyhtiota == "") {
            $query = "INSERT LOW_PRIORITY into {$table_mysql} SET yhtio='$kukarow[yhtio]', laatija='$kukarow[kuka]', luontiaika=now(), muuttaja='$kukarow[kuka]', muutospvm=now() ";
          }
          elseif ($eiyhtiota == "EILAATIJAA") {
            $query = "INSERT LOW_PRIORITY INTO {$table_mysql} SET yhtio = '{$kukarow['yhtio']}' ";
          }
          elseif ($eiyhtiota == "TRIP") {
            $query = "INSERT LOW_PRIORITY into {$table_mysql} SET laatija='$kukarow[kuka]', luontiaika=now() ";
          }
        }

        if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'MUUTA') {
          if ($eiyhtiota == "") {
            $query = "UPDATE LOW_PRIORITY {$table_mysql} SET yhtio='$kukarow[yhtio]', muuttaja='$kukarow[kuka]', muutospvm=now() ";
          }
          elseif ($eiyhtiota == "EILAATIJAA") {
            $query = "UPDATE LOW_PRIORITY {$table_mysql} SET yhtio = '{$kukarow['yhtio']}' ";
          }
          elseif ($eiyhtiota == "TRIP") {
            $query = "UPDATE LOW_PRIORITY {$table_mysql} SET muuttaja='$kukarow[kuka]', muutospvm=now() ";
          }
        }

        if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'POISTA') {
          $query = "DELETE LOW_PRIORITY FROM $table_mysql ";
        }

        foreach ($taulunotsikot[$taulu] as $r => $otsikko) {

          //  N�it� ei koskaan lis�t�
          if (is_array($apu_sarakkeet) and in_array($otsikko, $apu_sarakkeet)) {
            continue;
          }

          if ($r != $postoiminto) {

            // Avainsanojen perheet kuntoon!
            if ($table_mysql == 'avainsana' and $taulunrivit[$taulu][$eriviindex][$postoiminto] == 'LISAA' and $taulunrivit[$taulu][$eriviindex][array_search("PERHE", $taulunotsikot[$taulut[$r]])] == "AUTOM") {

              $mpquery = "SELECT max(perhe)+1 max
                          FROM avainsana";
              $vresult = pupe_query($mpquery);
              $vrow = mysql_fetch_assoc($vresult);

              $apu_ytunnus = $vrow['max'] + $tarkylisa;
              $tarkylisa++;

              $j = array_search("PERHE", $taulunotsikot[$taulut[$r]]);

              // P�ivitet��n generoitu arvo kaikkiin muuttujiin...
              $taulunrivit[$taulu][$eriviindex][$j] = $apu_ytunnus;

              foreach ($taulunotsikot as $autotaulu => $autojoinit) {
                if (in_array("PERHE", $joinit) and $autotaulu != $taulut[$r] and $taulu == $joinattavat[$autotaulu]["PERHE"]) {
                  $taulunrivit[$autotaulu][$eriviindex][array_search("PERHE", $taulunotsikot[$autotaulu])] = $apu_ytunnus;
                }
              }
            }

            if (substr($trows[$table_mysql.".".$otsikko], 0, 7) == "decimal" or substr($trows[$table_mysql.".".$otsikko], 0, 4) == "real") {

              // Korvataan decimal kenttien pilkut pisteill�...
              $taulunrivit[$taulu][$eriviindex][$r] = str_replace(",", ".", $taulunrivit[$taulu][$eriviindex][$r]);

              $desimaali_talteen = (float) $taulunrivit[$taulu][$eriviindex][$r];

              // Jos MySQL kent�ss� on desimaaleja, py�ristet��n luku sallittuun tarkkuuteen
              if ($tdecimal[$table_mysql.".".$otsikko] > 0 and $desimaali_talteen != -0) {
                $taulunrivit[$taulu][$eriviindex][$r] = round($taulunrivit[$taulu][$eriviindex][$r], $tdecimal[$table_mysql.".".$otsikko]);
              }

              if ($desimaali_talteen != $taulunrivit[$taulu][$eriviindex][$r]) {
                lue_data_echo(t("Huomio rivill�").": $rivilaskuri <font class='message'>".t("Luku py�ristettiin sallittuun tarkkuuteen")." $desimaali_talteen &raquo; {$taulunrivit[$taulu][$eriviindex][$r]}</font><br>");
              }
            }

            if ((int) $tlength[$table_mysql.".".$otsikko] > 0 and strlen($taulunrivit[$taulu][$eriviindex][$r]) > $tlength[$table_mysql.".".$otsikko]
              and !($table_mysql == "tuotepaikat"  and $otsikko == "OLETUS"  and $taulunrivit[$taulu][$eriviindex][$r] == 'XVAIHDA')
              and !($table_mysql == "asiakashinta" and $otsikko == 'ASIAKAS' and $asiakkaanvalinta > 1)) {

              lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("VIRHE").": $otsikko ".t("kent�ss� on liian pitk� tieto")."!</font> {$taulunrivit[$taulu][$eriviindex][$r]}: ".strlen($taulunrivit[$taulu][$eriviindex][$r])." > ".$tlength[$table_mysql.".".$otsikko]."!<br>");
              $hylkaa++; // ei p�ivitet� t�t� rivi�
            }

            if ($table_mysql == 'tuotepaikat' and $otsikko == 'OLETUS' and $taulunrivit[$taulu][$eriviindex][$postoiminto] != 'POISTA') {
              // $tuoteno pit�s olla jo aktivoitu ylh��ll�
              // haetaan tuotteen varastopaikkainfo
              $tpque = "SELECT sum(if (oletus='X',1,0)) oletus, sum(if (oletus='X',0,1)) regular
                        from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
              $tpres = pupe_query($tpque);

              if (mysql_num_rows($tpres) == 0) {
                $taulunrivit[$taulu][$eriviindex][$r] = "X"; // jos yht��n varastopaikkaa ei l�ydy, pakotetaan oletus
                lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Tuotteella")." '$tuoteno' ".t("ei ole yht��n varastopaikkaa, pakotetaan t�st� oletus").".<br>");
              }
              else {
                $tprow = mysql_fetch_array($tpres);
                if ($taulunrivit[$taulu][$eriviindex][$r] == 'XVAIHDA' and $tprow['oletus'] > 0) {
                  //vaihdetaan t�m� oletukseksi
                  lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Tuotteelle")." '$tuoteno' ".t("Vaihdetaan annettu paikka oletukseksi").".<br>");
                }
                elseif ($taulunrivit[$taulu][$eriviindex][$r] != '' and $tprow['oletus'] > 0) {
                  $taulunrivit[$taulu][$eriviindex][$r] = ""; // t�ll� tuotteella on jo oletuspaikka, nollataan t�m�
                  lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Tuotteella")." '$tuoteno' ".t("on jo oletuspaikka, ei p�ivitetty oletuspaikkaa")."!<br>");
                }
                elseif ($taulunrivit[$taulu][$eriviindex][$r] == '' and $tprow['oletus'] == 0) {
                  $taulunrivit[$taulu][$eriviindex][$r] = "X"; // jos yht��n varastopaikkaa ei l�ydy, pakotetaan oletus
                  lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Tuotteella")." '$tuoteno' ".t("ei ole yht��n oletuspaikkaa! T�t� EI PIT�ISI tapahtua! Tehd��n nyt t�st� oletus").".<br>");
                }
              }
            }

            if ($table_mysql == 'tuote' and ($otsikko == 'EPAKURANTTI25PVM' or $otsikko == 'EPAKURANTTI50PVM' or $otsikko == 'EPAKURANTTI75PVM' or $otsikko == 'EPAKURANTTI100PVM') and $taulunrivit[$taulu][$eriviindex][$r] != "") {

              if (trim($taulunrivit[$taulu][$eriviindex][$r]) != '' and trim($taulunrivit[$taulu][$eriviindex][$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI100PVM') {
                $tee = "paalle";
              }

              if ($tee != "paalle" and trim($taulunrivit[$taulu][$eriviindex][$r]) != '' and trim($taulunrivit[$taulu][$eriviindex][$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI75PVM') {
                $tee = "75paalle";
              }

              if ($tee != "paalle" and $tee != "75paalle" and trim($taulunrivit[$taulu][$eriviindex][$r]) != '' and trim($taulunrivit[$taulu][$eriviindex][$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI50PVM') {
                $tee = "puolipaalle";
              }

              if ($tee != "paalle" and $tee != "75paalle" and $tee != "puolipaalle" and trim($taulunrivit[$taulu][$eriviindex][$r]) != '' and trim($taulunrivit[$taulu][$eriviindex][$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI25PVM') {
                $tee = "25paalle";
              }

              if (strtoupper($taulunrivit[$taulu][$eriviindex][$r]) == "PERU") {
                $tee = "peru";
              }

              if ($taulunrivit[$taulu][$eriviindex][$r] == "0000-00-00" or substr(strtoupper($taulunrivit[$taulu][$eriviindex][$r]), 0, 4) == "POIS") {

                if (substr(strtoupper($taulunrivit[$taulu][$eriviindex][$r]), 0, 4) == "POIS" and strlen($taulunrivit[$taulu][$eriviindex][$r]) == 15) {
                  $epakurpvm = substr($taulunrivit[$taulu][$eriviindex][$r], 5);
                }

                $tee = "pois";
              }

              // ei yritet� laittaa uusia tuotteita kurantiksi vaikka kent�t olisikin exceliss�
              if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'LISAA' and $tee == 'pois') {
                $tee = "";
              }

              $eilisataeikamuuteta = "joo";
            }

            if ($table_mysql == 'tuote' and ($otsikko == 'KUSTP' or $otsikko == 'KOHDE' or $otsikko == 'PROJEKTI') and $taulunrivit[$taulu][$eriviindex][$r] != "") {
              // Kustannuspaikkarumba t�nnekin
              $ikustp_tsk = $taulunrivit[$taulu][$eriviindex][$r];
              $ikustp_ok  = 0;

              if ($otsikko == "PROJEKTI") $kptyyppi = "P";
              if ($otsikko == "KOHDE")  $kptyyppi = "O";
              if ($otsikko == "KUSTP")  $kptyyppi = "K";

              if ($ikustp_tsk != "") {
                $ikustpq = "SELECT tunnus
                            FROM kustannuspaikka
                            WHERE yhtio   = '$kukarow[yhtio]'
                            and tyyppi    = '$kptyyppi'
                            and kaytossa != 'E'
                            and nimi      = '$ikustp_tsk'";
                $ikustpres = pupe_query($ikustpq);

                if (mysql_num_rows($ikustpres) == 1) {
                  $ikustprow = mysql_fetch_assoc($ikustpres);
                  $ikustp_ok = $ikustprow["tunnus"];
                }
              }

              if ($ikustp_tsk != "" and $ikustp_ok == 0) {
                $ikustpq = "SELECT tunnus
                            FROM kustannuspaikka
                            WHERE yhtio   = '$kukarow[yhtio]'
                            and tyyppi    = '$kptyyppi'
                            and kaytossa != 'E'
                            and koodi     = '$ikustp_tsk'";
                $ikustpres = pupe_query($ikustpq);

                if (mysql_num_rows($ikustpres) == 1) {
                  $ikustprow = mysql_fetch_assoc($ikustpres);
                  $ikustp_ok = $ikustprow["tunnus"];
                }
              }

              if (is_numeric($ikustp_tsk) and (int) $ikustp_tsk > 0 and $ikustp_ok == 0) {

                $ikustp_tsk = (int) $ikustp_tsk;

                $ikustpq = "SELECT tunnus
                            FROM kustannuspaikka
                            WHERE yhtio   = '$kukarow[yhtio]'
                            and tyyppi    = '$kptyyppi'
                            and kaytossa != 'E'
                            and tunnus    = '$ikustp_tsk'";
                $ikustpres = pupe_query($ikustpq);

                if (mysql_num_rows($ikustpres) == 1) {
                  $ikustprow = mysql_fetch_assoc($ikustpres);
                  $ikustp_ok = $ikustprow["tunnus"];
                }
              }

              if ($ikustp_ok > 0) {
                $taulunrivit[$taulu][$eriviindex][$r] = $ikustp_ok;
              }
            }

            // tehd��n riville oikeellisuustsekkej�
            if ($table_mysql == 'sanakirja' and $otsikko == 'FI') {
              // jos ollaan mulkkaamassa RU ni tehd��n utf-8 -> latin-1 konversio FI kent�ll�
              if (in_array("RU", $taulunotsikot[$taulu])) {
                $taulunrivit[$taulu][$eriviindex][$r] = iconv("UTF-8", "ISO-8859-1", $taulunrivit[$taulu][$eriviindex][$r]);
              }
            }

            if ($lue_data_autoid) {
              $tee = "rivi_loop";
              require "lue_data_autoid.php";
            }

            //tarkistetaan asiakasalennus ja asiakashinta juttuja
            if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajaalennus' or $table_mysql == 'toimittajahinta') {
              if ($otsikko == 'RYHMA' and $taulunrivit[$taulu][$eriviindex][$r] != '') {
                $chryhma = $taulunrivit[$taulu][$eriviindex][$r];
              }

              // Asiakas sarakkeessa on tunnus
              if ($otsikko == 'ASIAKAS' and $asiakkaanvalinta == 1 and $taulunrivit[$taulu][$eriviindex][$r] != "") {
                $chasiakas = $taulunrivit[$taulu][$eriviindex][$r];
              }
              // Asiakas sarakkeessa on toim_ovttunnus (ytunnus pit�� olla setattu) (t�m� on oletus er�ajossa)
              elseif ($otsikko == 'ASIAKAS' and $asiakkaanvalinta == 2 and $taulunrivit[$taulu][$eriviindex][$r] != "") {
                $etsitunnus = "SELECT tunnus
                               FROM asiakas USE INDEX (toim_ovttunnus_index)
                               WHERE yhtio         = '$kukarow[yhtio]'
                               AND toim_ovttunnus  = '{$taulunrivit[$taulu][$eriviindex][$r]}'
                               AND toim_ovttunnus != ''
                               AND ytunnus        != ''
                               AND ytunnus         = '".$taulunrivit[$taulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$taulu])]."'";
                $etsiresult = pupe_query($etsitunnus);

                if (mysql_num_rows($etsiresult) == 1) {
                  $etsirow = mysql_fetch_assoc($etsiresult);

                  // Vaihdetaan asiakas sarakkeeseen tunnus sek� ytunnus tulee nollata (koska ei saa olla molempia)
                  $chasiakas = $etsirow['tunnus'];
                  $chytunnus = "";
                  $taulunrivit[$taulu][$eriviindex][$r] = $etsirow['tunnus'];
                  $taulunrivit[$taulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$taulu])] = "";
                }
                else {
                  $chasiakas = -1;
                }
              }
              // Asiakas sarakkeessa on asiakasnumero
              elseif ($otsikko == 'ASIAKAS' and $asiakkaanvalinta == 3 and $taulunrivit[$taulu][$eriviindex][$r] != "") {
                $etsitunnus = "SELECT tunnus
                               FROM asiakas USE INDEX (asno_index)
                               WHERE yhtio     = '$kukarow[yhtio]'
                               AND asiakasnro  = '{$taulunrivit[$taulu][$eriviindex][$r]}'
                               AND asiakasnro != ''";
                $etsiresult = pupe_query($etsitunnus);

                if (mysql_num_rows($etsiresult) == 1) {
                  $etsirow = mysql_fetch_assoc($etsiresult);

                  // Vaihdetaan asiakas sarakkeeseen tunnus sek� ytunnus tulee nollata (koska ei saa olla molempia)
                  $chasiakas = $etsirow['tunnus'];
                  $chytunnus = "";
                  $taulunrivit[$taulu][$eriviindex][$r] = $etsirow['tunnus'];
                  $taulunrivit[$taulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$taulu])] = "";
                }
                else {
                  $chasiakas = -1;
                }
              }

              if ($otsikko == 'TOIMITTAJA' and (int) $taulunrivit[$taulu][$eriviindex][$r] > 0) {
                $chtoimittaja = $taulunrivit[$taulu][$eriviindex][$r];
              }

              if ($otsikko == 'TUOTENO' and $taulunrivit[$taulu][$eriviindex][$r] != '') {
                $chtuoteno = $taulunrivit[$taulu][$eriviindex][$r];
              }

              if ($otsikko == 'ASIAKAS_RYHMA' and $taulunrivit[$taulu][$eriviindex][$r] != '') {
                $chasiakas_ryhma = $taulunrivit[$taulu][$eriviindex][$r];
              }

              if ($otsikko == 'YTUNNUS' and $taulunrivit[$taulu][$eriviindex][$r] != '') {
                $chytunnus = $taulunrivit[$taulu][$eriviindex][$r];
              }

              if ($otsikko == 'ALKUPVM' and $taulunrivit[$taulu][$eriviindex][$r] != '') {
                $chalkupvm = $taulunrivit[$taulu][$eriviindex][$r];
              }

              if ($otsikko == 'LOPPUPVM' and $taulunrivit[$taulu][$eriviindex][$r] != '') {
                $chloppupvm = $taulunrivit[$taulu][$eriviindex][$r];
              }

              if ($otsikko == 'ASIAKAS_SEGMENTTI' and $segmenttivalinta == '1' and (int) $taulunrivit[$taulu][$eriviindex][$r] > 0) {
                // 1 tarkoittaa dynaamisen puun KOODIA
                $etsitunnus = "SELECT tunnus
                               FROM dynaaminen_puu
                               WHERE yhtio = '$kukarow[yhtio]'
                               AND laji    = 'asiakas'
                               AND koodi   = '{$taulunrivit[$taulu][$eriviindex][$r]}'";
                $etsiresult = pupe_query($etsitunnus);
                $etsirow = mysql_fetch_assoc($etsiresult);

                $chsegmentti = $etsirow['tunnus'];
              }

              if ($otsikko == 'ASIAKAS_SEGMENTTI' and $segmenttivalinta == '2' and (int) $taulunrivit[$taulu][$eriviindex][$r] > 0) {
                // 2 tarkoittaa dynaamisen puun TUNNUSTA
                $chsegmentti = $taulunrivit[$taulu][$eriviindex][$r];
              }

              if ($otsikko == 'PIIRI' and $taulunrivit[$taulu][$eriviindex][$r] != '') {
                $chpiiri = $taulunrivit[$taulu][$eriviindex][$r];
              }

              if ($otsikko == 'MINKPL' and (int) $taulunrivit[$taulu][$eriviindex][$r] > 0) {
                $chminkpl = (int) $taulunrivit[$taulu][$eriviindex][$r];
              }

              if ($otsikko == 'MAXKPL' and (int) $taulunrivit[$taulu][$eriviindex][$r] > 0) {
                $chmaxkpl = (int) $taulunrivit[$taulu][$eriviindex][$r];
              }

              if ($otsikko == 'ALENNUSLAJI' and (int) $taulunrivit[$taulu][$eriviindex][$r] > 0) {
                $chalennuslaji = (int) $taulunrivit[$taulu][$eriviindex][$r];
              }

              if ($otsikko == 'MONIKERTA' and $taulunrivit[$taulu][$eriviindex][$r] != '') {
                $chmonikerta = $taulunrivit[$taulu][$eriviindex][$r];
              }
            }

            //tarkistetaan kuka juttuja
            if ($table_mysql == 'kuka') {
              if ($otsikko == 'SALASANA' and $taulunrivit[$taulu][$eriviindex][$r] != '') {
                $taulunrivit[$taulu][$eriviindex][$r] = md5(trim($taulunrivit[$taulu][$eriviindex][$r]));
              }

              if ($otsikko == 'OLETUS_ASIAKAS' and $taulunrivit[$taulu][$eriviindex][$r] != '') {
                $xquery = "SELECT tunnus
                           FROM asiakas
                           WHERE yhtio = '$kukarow[yhtio]'
                           and tunnus  = '{$taulunrivit[$taulu][$eriviindex][$r]}'";
                $xresult = pupe_query($xquery);

                if (mysql_num_rows($xresult) == 0) {
                  $xquery = "SELECT tunnus
                             FROM asiakas
                             WHERE yhtio = '$kukarow[yhtio]'
                             and ytunnus = '{$taulunrivit[$taulu][$eriviindex][$r]}'";
                  $xresult = pupe_query($xquery);
                }

                if (mysql_num_rows($xresult) == 0) {
                  $xquery = "SELECT tunnus
                             FROM asiakas
                             WHERE yhtio    = '$kukarow[yhtio]'
                             and asiakasnro = '{$taulunrivit[$taulu][$eriviindex][$r]}'";
                  $xresult = pupe_query($xquery);
                }

                if (mysql_num_rows($xresult) == 0) {
                  lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Asiakasta")." '{$taulunrivit[$taulu][$eriviindex][$r]}' ".t("ei l�ydy! Rivi� ei p�ivitetty/lis�tty")."! $otsikko = {$taulunrivit[$taulu][$eriviindex][$r]}<br>");
                  $hylkaa++; // ei p�ivitet� t�t� rivi�
                }
                elseif (mysql_num_rows($xresult) > 1) {
                  lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Asiakasta")." '{$taulunrivit[$taulu][$eriviindex][$r]}' ".t("l�ytyi monia! Rivi� ei p�ivitetty/lis�tty")."! $otsikko = {$taulunrivit[$taulu][$eriviindex][$r]}<br>");
                  $hylkaa++; // ei p�ivitet� t�t� rivi�
                }
                else {
                  $x2row = mysql_fetch_array($xresult);
                  $taulunrivit[$taulu][$eriviindex][$r] = $x2row['tunnus'];
                }
              }
            }

            //muutetaan rivi�, silloin ei saa p�ivitt�� pakollisia kentti�
            if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'MUUTA' and (!in_array($otsikko, $pakolliset) or $table_mysql == 'auto_vari_korvaavat' or $table_mysql == 'asiakashinta' or $table_mysql == 'asiakasalennus' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajasalennus' or ($table_mysql == "tuotepaikat" and $otsikko == "OLETUS" and $taulunrivit[$taulu][$eriviindex][$r] == 'XVAIHDA'))) {
              ///* T�ss� on kaikki oikeellisuuscheckit *///
              if (($table_mysql == 'asiakashinta' and $otsikko == 'HINTA') or ($table_mysql == 'toimittajahinta' and $otsikko == 'HINTA')) {
                if ($taulunrivit[$taulu][$eriviindex][$r] != 0 and $taulunrivit[$taulu][$eriviindex][$r] != '') {
                  $query .= ", $otsikko = '{$taulunrivit[$taulu][$eriviindex][$r]}' ";
                }
                elseif ($taulunrivit[$taulu][$eriviindex][$r] == 0) {
                  lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Hintaa ei saa nollata!")."<br>");
                }
              }
              elseif ($table_mysql == 'avainsana' and $otsikko == 'SELITE') {
                if ($taulunrivit[$taulu][$eriviindex][$r] != 0 and $taulunrivit[$taulu][$eriviindex][$r] != '') {
                  $query .= ", $otsikko = '{$taulunrivit[$taulu][$eriviindex][$r]}' ";
                }
                elseif ($taulunrivit[$taulu][$eriviindex][$r] == 0) {
                  lue_data_echo(t("Virhe rivill�").": $rivilaskuri ".t("Selite ei saa olla tyhj�!")."<br>");
                }
              }
              elseif ($table_mysql=='tuotepaikat' and $otsikko == 'OLETUS' and $taulunrivit[$taulu][$eriviindex][$r] == 'XVAIHDA') {
                //vaihdetaan t�m� oletukseksi
                $taulunrivit[$taulu][$eriviindex][$r] = "X"; // pakotetaan oletus
                //  Selvitet��n vanha oletuspaikka
                $vanha_oletus_query = "SELECT *
                                       FROM tuotepaikat
                                       WHERE tuoteno  = '$tuoteno'
                                       and yhtio      = '$kukarow[yhtio]'
                                       and oletus    != ''";
                $oletusresult = pupe_query($vanha_oletus_query);
                $oletusrow = mysql_fetch_assoc($oletusresult);

                // P�ivitet��n uusi oletuspaikka my�s avoimille ostotilausriveille (jos uusi paikka on samassa varastossa kun vanha)
                $hylly = array(
                  "hyllyalue" => $taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYALUE", $taulunotsikot["tuotepaikat"])],
                  "hyllynro" => $taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYNRO", $taulunotsikot["tuotepaikat"])],
                  "hyllytaso" => $taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYTASO", $taulunotsikot["tuotepaikat"])],
                  "hyllyvali" => $taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYVALI", $taulunotsikot["tuotepaikat"])],
                );
                if (mysql_num_rows($oletusresult) == 1 and kuuluukovarastoon($oletusrow['hyllyalue'], $oletusrow['hyllynro']) == kuuluukovarastoon($hylly['hyllyalue'], $hylly['hyllynro'])) {
                  $uusi_oletus_query = "UPDATE tilausrivi
                                        SET hyllyalue = '$hylly[hyllyalue]',
                                        hyllynro         = '$hylly[hyllynro]',
                                        hyllytaso        = '$hylly[hyllytaso]',
                                        hyllyvali        = '$hylly[hyllyvali]'
                                        WHERE yhtio      = '$kukarow[yhtio]'
                                        AND tuoteno      = '$tuoteno'
                                        AND hyllyalue    = '$oletusrow[hyllyalue]'
                                        AND hyllynro     = '$oletusrow[hyllynro]'
                                        AND hyllytaso    = '$oletusrow[hyllytaso]'
                                        AND hyllyvali    = '$oletusrow[hyllyvali]'
                                        AND tyyppi       = 'O'
                                        AND uusiotunnus  = '0'
                                        AND kpl          = '0'
                                        AND varattu     != '0'";
                  $paivitysresult = pupe_query($uusi_oletus_query);

                  if (mysql_affected_rows() > 0) {
                    lue_data_echo(t("P�ivitettiin %s ostotilausrivin varastopaikkaa.", '', mysql_affected_rows())."<br>");
                  }
                }

                $tpupque = "UPDATE tuotepaikat SET oletus = '' where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";

                $query .= ", $otsikko = '{$taulunrivit[$taulu][$eriviindex][$r]}' ";
              }
              elseif ($table_mysql=='tuotepaikat' and $otsikko == 'OLETUS') {
                //echo t("Virhe rivill�").": $rivilaskuri Oletusta ei voi muuttaa!<br>";
              }
              elseif ($table_mysql == 'tili' and $otsikko == 'OLETUS_ALV' and ($taulunrivit[$taulu][$eriviindex][$r] == "" or $taulunrivit[$taulu][$eriviindex][$r] == "NULL")) {
                $query .= ", $otsikko = NULL ";
              }
              elseif ($table_mysql == 'tuote' and $otsikko == 'MYYNTIHINTA' and $myyntihinnan_paivitys == 1) {
                $query .= ", $otsikko = GREATEST(myyntihinta, {$taulunrivit[$taulu][$eriviindex][$r]})";
              }
              elseif ($eilisataeikamuuteta == "") {
                $query .= ", $otsikko = '{$taulunrivit[$taulu][$eriviindex][$r]}' ";
              }
            }

            //lis�t��n rivi
            if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'LISAA') {
              if ($table_mysql == 'tuotepaikat' and $otsikko == 'OLETUS' and $taulunrivit[$taulu][$eriviindex][$r] == 'XVAIHDA') {
                //vaihdetaan t�m� oletukseksi
                $taulunrivit[$taulu][$eriviindex][$r] = "X"; // pakotetaan oletus

                //  Selvitet��n vanha oletuspaikka
                $vanha_oletus_query = "SELECT *
                                       FROM tuotepaikat
                                       WHERE tuoteno  = '$tuoteno'
                                       and yhtio      = '$kukarow[yhtio]'
                                       and oletus    != ''";
                $oletusresult = pupe_query($vanha_oletus_query);
                $oletusrow = mysql_fetch_assoc($oletusresult);

                // P�ivitet��n uusi oletuspaikka my�s avoimille ostotilausriveille (jos uusi paikka on samassa varastossa kun vanha)
                $hylly = array(
                  "hyllyalue" => $taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYALUE", $taulunotsikot["tuotepaikat"])],
                  "hyllynro" => $taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYNRO", $taulunotsikot["tuotepaikat"])],
                  "hyllytaso" => $taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYTASO", $taulunotsikot["tuotepaikat"])],
                  "hyllyvali" => $taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYVALI", $taulunotsikot["tuotepaikat"])],
                );
                if (mysql_num_rows($oletusresult) == 1 and kuuluukovarastoon($oletusrow['hyllyalue'], $oletusrow['hyllynro']) == kuuluukovarastoon($hylly['hyllyalue'], $hylly['hyllynro'])) {
                  $uusi_oletus_query = "UPDATE tilausrivi
                                        SET hyllyalue = '$hylly[hyllyalue]',
                                        hyllynro         = '$hylly[hyllynro]',
                                        hyllytaso        = '$hylly[hyllytaso]',
                                        hyllyvali        = '$hylly[hyllyvali]'
                                        WHERE yhtio      = '$kukarow[yhtio]'
                                        AND tuoteno      = '$tuoteno'
                                        AND hyllyalue    = '$oletusrow[hyllyalue]'
                                        AND hyllynro     = '$oletusrow[hyllynro]'
                                        AND hyllytaso    = '$oletusrow[hyllytaso]'
                                        AND hyllyvali    = '$oletusrow[hyllyvali]'
                                        AND tyyppi       = 'O'
                                        AND uusiotunnus  = '0'
                                        AND kpl          = '0'
                                        AND varattu     != '0'";
                  $paivitysresult = pupe_query($uusi_oletus_query);

                  if (mysql_affected_rows() > 0) {
                    lue_data_echo(t("P�ivitettiin %s ostotilausrivin varastopaikkaa.", '', mysql_affected_rows())."<br>");
                  }
                }

                $tpupque = "UPDATE tuotepaikat SET oletus = '' where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";

                $query .= ", $otsikko = '{$taulunrivit[$taulu][$eriviindex][$r]}' ";
              }
              elseif (substr($taulu, 0, 11) == 'puun_alkio_') {
                if ($otsikko == 'PUUN_TUNNUS') {
                  if ($dynaamisen_taulun_liitos == 'koodi') {
                    $query_x = "SELECT tunnus
                                FROM dynaaminen_puu
                                WHERE yhtio = '{$kukarow['yhtio']}'
                                AND laji    = '{$table_tarkenne}'
                                AND koodi   = '{$taulunrivit[$taulu][$eriviindex][$r]}'";
                    $koodi_tunnus_res = pupe_query($query_x);
                    $koodi_tunnus_row = mysql_fetch_assoc($koodi_tunnus_res);

                    $query .= ", puun_tunnus = '{$koodi_tunnus_row['tunnus']}' ";
                  }
                  else {
                    $query .= ", puun_tunnus = '{$taulunrivit[$taulu][$eriviindex][$r]}' ";
                  }
                }
                else {
                  $query .= ", $otsikko = '{$taulunrivit[$taulu][$eriviindex][$r]}' ";
                }
              }
              elseif ($table_mysql == 'tili' and $otsikko == 'OLETUS_ALV' and ($taulunrivit[$taulu][$eriviindex][$r] == "" or $taulunrivit[$taulu][$eriviindex][$r] == "NULL")) {
                $query .= ", $otsikko = NULL ";
              }
              elseif ($eilisataeikamuuteta == "") {
                $query .= ", $otsikko = '{$taulunrivit[$taulu][$eriviindex][$r]}' ";
              }
            }
          }
        }

        // tarkistetaan asiakasalennus ja asiakashinta keisseiss� onko t�llanen rivi jo olemassa, sek� toimittajahinta ett� toimittajaalennus
        if ($hylkaa == 0 and ($chasiakas != 0 or $chasiakas_ryhma != '' or $chytunnus != '' or $chpiiri != '' or $chsegmentti != 0 or $chtoimittaja != '') and ($chryhma != '' or $chtuoteno != '') and ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajaalennus')) {
          if ($chasiakas_ryhma != '') {
            $and .= " and asiakas_ryhma = '$chasiakas_ryhma'";
          }
          if ($chytunnus != '') {
            $and .= " and ytunnus = '$chytunnus'";
          }
          if ($chasiakas != 0) {
            $and .= " and asiakas = '$chasiakas'";
          }
          if ($chsegmentti > 0) {
            $and .= " and asiakas_segmentti = '$chsegmentti'";
          }
          if ($chpiiri != '') {
            $and .= " and piiri = '$chpiiri'";
          }

          if ($chryhma != '') {
            $and .= " and ryhma = '$chryhma'";
          }
          if ($chtuoteno != '') {
            $and .= " and tuoteno = '$chtuoteno'";
          }

          if ($chminkpl != 0) {
            $and .= " and minkpl = '$chminkpl'";
          }

          if ($chtoimittaja != '') {
            $and .= " and toimittaja = '$chtoimittaja'";
          }

          if ($table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta') {
            if ($chmaxkpl != 0) {
              $and .= " and maxkpl = '$chmaxkpl'";
            }
          }

          if ($table_mysql == 'asiakasalennus' or $table_mysql == 'toimittajaalennus') {

            if ($chmonikerta != '') {
              $and .= " and monikerta != ''";
            }
            else {
              $and .= " and monikerta  = ''";
            }

            if ($chalennuslaji == 0) {
              $and .= " and alennuslaji = '1'";
            }
            elseif ($chalennuslaji != 0) {
              $and .= " and alennuslaji = '$chalennuslaji'";
            }
          }

          $and .= " and alkupvm = '$chalkupvm' and loppupvm = '$chloppupvm'";
        }

        if (substr($taulu, 0, 11) == 'puun_alkio_' and $taulunrivit[$taulu][$eriviindex][$postoiminto] != 'POISTA') {
          $query .= " , laji = '{$table_tarkenne}' ";
        }

        if ($table_mysql == 'tuotteen_toimittajat' and in_array("TEHDAS_SALDO", $taulunotsikot[$taulu]) and $taulunrivit[$taulu][$eriviindex][$postoiminto] != 'POISTA') {
          $query .= " , tehdas_saldo_paivitetty = now() ";
        }

        // Ollaan lis��m�ss� tietuetta, katsotaan ett� on kaikki oletukset MySQL aliaksista
        // Taulun oletusarvot, jos ollaan lis��m�ss� uutta tietuetta
        if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == "LISAA") {
          foreach ($oletukset as $oletus_kentta => $oletus_arvo) {
            if (stripos($query, ", $oletus_kentta = ") === FALSE) {
              $query .= ", $oletus_kentta = '$oletus_arvo' ";
            }
          }
        }

        // lis�t��n tuote, mutta ei olla speksattu alvia ollenkaan...
        if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'LISAA' and $table_mysql == 'tuote' and stripos($query, ", alv = ") === FALSE) {
          $query .= ", alv = '$oletus_alvprossa' ";
        }

        // Jos on asiakas-taulu, niin populoidaan kaikkien dropdown-menujen arvot, mik�li niit� ei ole annettu.
        if ($table_mysql == 'asiakas' and $taulunrivit[$taulu][$eriviindex][$postoiminto] == 'LISAA') {
          if (stripos($query, ", maksuehto = ") === FALSE) {
            $select_query = "SELECT * FROM maksuehto WHERE yhtio = '{$kukarow['yhtio']}' AND kaytossa='' ORDER BY jarjestys, teksti limit 1";
            $select_result = pupe_query($select_query);
            $select_row = mysql_fetch_assoc($select_result);

            $query .= ", maksuehto = '{$select_row["tunnus"]}' ";
          }

          if (stripos($query, ", toimitustapa = ") === FALSE) {
            $select_query = "SELECT * FROM toimitustapa WHERE yhtio = '{$kukarow['yhtio']}' ORDER BY jarjestys, selite limit 1";
            $select_result = pupe_query($select_query);
            $select_row = mysql_fetch_assoc($select_result);

            $query .= ", toimitustapa = '{$select_row["selite"]}' ";
          }

          if (stripos($query, ", valkoodi = ") === FALSE) {
            $query .= ", valkoodi = '{$yhtiorow["valkoodi"]}' ";
          }

          if (stripos($query, ", kerayspoikkeama = ") === FALSE) {
            $query .= ", kerayspoikkeama = '0' ";
          }

          if (stripos($query, ", laskutusvkopv = ") === FALSE) {
            $query .= ", laskutusvkopv = '0' ";
          }

          if (stripos($query, ", laskutyyppi = ") === FALSE) {
            $query .= ", laskutyyppi = '-9' ";
          }

          if (stripos($query, ", maa = ") === FALSE) {
            $query .= ", maa = '{$yhtiorow["maa"]}' ";
          }

          if (stripos($query, ", kansalaisuus = ") === FALSE) {
            $query .= ", kansalaisuus = '{$yhtiorow["kieli"]}' ";
          }

          if (stripos($query, ", laskutus_maa = ") === FALSE) {
            $query .= ", laskutus_maa = '{$yhtiorow["maa"]}' ";
          }

          if (stripos($query, ", toim_maa = ") === FALSE) {
            $query .= ", toim_maa = '{$yhtiorow["maa"]}' ";
          }

          if (stripos($query, ", kolm_maa = ") === FALSE) {
            $query .= ", kolm_maa = '{$yhtiorow["maa"]}' ";
          }

          if (stripos($query, ", kieli = ") === FALSE) {
            $query .= ", kieli = '{$yhtiorow["kieli"]}' ";
          }

          if (stripos($query, ", chn = ") === FALSE) {
            $query .= ", chn = '100' ";
          }

          if (stripos($query, ", alv = ") === FALSE) {
            //yhti�n oletusalvi!
            $wquery = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji = 'alv' and selitetark != ''";
            $wtres  = pupe_query($wquery);
            $wtrow  = mysql_fetch_array($wtres);

            $query .= ", alv = '{$wtrow["selite"]}' ";
          }

          if (stripos($query, ", asiakasnro = ") === FALSE and $yhtiorow["automaattinen_asiakasnumerointi"] != "") {

            if ($yhtiorow["asiakasnumeroinnin_aloituskohta"] != "") {
              $apu_asiakasnumero = $yhtiorow["asiakasnumeroinnin_aloituskohta"];
            }
            else {
              $apu_asiakasnumero = 0;
            }

            $select_query = "SELECT MAX(asiakasnro+0) asiakasnro
                             FROM asiakas USE INDEX (asno_index)
                             WHERE yhtio = '{$kukarow["yhtio"]}'
                             AND asiakasnro+0 >= $apu_asiakasnumero";
            $select_result = pupe_query($select_query);
            $select_row = mysql_fetch_assoc($select_result);

            if ($select_row['asiakasnro'] != '') {
              $vapaa_asiakasnro = $select_row['asiakasnro'] + 1;
            }

            $query .= ", asiakasnro = '$vapaa_asiakasnro' ";
          }

        }

        // Laitetaan oletuksena asiakashinnalle yhti�n valuutta
        if ($table_mysql == "asiakashinta" and $taulunrivit[$taulu][$eriviindex][$postoiminto] != "POISTA") {
          if (stripos($query, ", valkoodi = ") === FALSE) {
            $query .= ", valkoodi = '{$yhtiorow["valkoodi"]}' ";
          }
        }

        if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'MUUTA') {
          if (($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajaalennus') and $and != "") {
            $query .= " WHERE yhtio = '$kukarow[yhtio]'";
            $query .= $and;
          }
          else {
            $query .= " WHERE ".$valinta;
          }

          $query .= " ORDER BY tunnus";
        }

        if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'POISTA') {
          if (($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajaalennus') and $and != "") {
            $query .= " WHERE yhtio = '$kukarow[yhtio]'";
            $query .= $and;
          }
          else {
            $query .= " WHERE ".$valinta;
          }

          $query .= " LIMIT 1 ";
        }

        //  Tarkastetaan tarkistarivi.incia vastaan..
        //  Generoidaan oikeat arrayt
        $errori     = "";
        $t         = array();
        $virhe       = array();
        $poistolukko  = "LUEDATA";
        $luedata_toiminto = $taulunrivit[$taulu][$eriviindex][$postoiminto];

        // Jos on uusi rivi niin kaikki lukot on auki
        if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'LISAA') {
          $poistolukko = "";
        }

        //  Otetaan talteen query..
        $lue_data_query = $query;

        $tarq = "SELECT *
                 FROM $table_mysql";
        if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajaalennus') {
          $tarq .= " WHERE yhtio = '$kukarow[yhtio]'";
          if (!empty($and)) {
            $tarq .= $and;
            $result = pupe_query($tarq);
          }
          else {
            lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Tarkista rivin ehdot ja sis��nluvun asetukset")."!</font><br>");
          }
        }
        else {
          $tarq .= " WHERE ".$valinta;

          if ($valinta_orig == $valinta) {
            $result = $fresult;
          }
          else {
            $result = pupe_query($tarq);
          }
        }

        // Rivin tiedot niin kuin ne oli ennen p�ivityst�
        $syncres = $result;

        if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'MUUTA' and mysql_num_rows($result) != 1) {
          lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("P�ivitett�v�� rivi� ei l�ytynyt")."!</font><br>");
        }
        elseif ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'LISAA' and mysql_num_rows($result) != 0) {

          if (!empty($and) and ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta' or $table_mysql == 'toimittajahinta' or $table_mysql == 'toimittajaalennus')) {
            lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Rivi� ei lis�tty, koska se l�ytyi jo j�rjestelm�st�")."!</font><br>");
          }
        }
        else {
          $tarkrow = mysql_fetch_array($result);
          $tunnus = $tarkrow["tunnus"];

          // Tehd��n pari injektiota tarkrow-arrayseen
          $tarkrow["luedata_from"] = "LUEDATA";
          $tarkrow["luedata_toiminto"] = $taulunrivit[$taulu][$eriviindex][$postoiminto];

          // Tehd��n oikeellisuustsekit
          for ($i=1; $i < mysql_num_fields($result); $i++) {

            // Tarkistetaan saako k�ytt�j� p�ivitt�� t�t� kentt��
            $Lindexi = array_search(strtoupper(mysql_field_name($result, $i)), $taulunotsikot[$taulu]);

            if (strtoupper(mysql_field_name($result, $i)) == 'TUNNUS') {
              $tassafailissa = TRUE;
            }
            elseif ($Lindexi !== FALSE and array_key_exists($Lindexi, $taulunrivit[$taulu][$eriviindex])) {
              $t[$i] = $taulunrivit[$taulu][$eriviindex][$Lindexi];

              // T�m� rivi on exceliss�
              $tassafailissa = TRUE;
            }
            else {
              $t[$i] = isset($tarkrow[mysql_field_name($result, $i)]) ? $tarkrow[mysql_field_name($result, $i)] : "";

              // T�m� rivi ei oo exceliss�
              $tassafailissa = FALSE;
            }

            // Tarkistetaan vain ne kent�t jotka on t�ss� exceliss�.
            if ($tassafailissa) {

              $funktio = $table_mysql."tarkista";

              if (!function_exists($funktio)) {
                @include "inc/$funktio.inc";
              }

              unset($virhe);

              if (function_exists($funktio)) {
                $funktio($t, $i, $result, $tunnus, $virhe, $tarkrow);
              }

              if (isset($virhe[$i]) and $virhe[$i] != "") {
                switch ($table_mysql) {
                case "tuote":
                  $virheApu = t("Tuote")." ".$tarkrow["tuoteno"].": ";
                  break;
                default:
                  $virheApu = "";
                }

                lue_data_echo(t("Virhe rivill�").": $rivilaskuri <font class='error'>$virheApu".mysql_field_name($result, $i).": ".$virhe[$i]." (".$t[$i].")</font><br>");
                $errori = 1;
              }
            }
          }

          if ($errori != "") {
            $hylkaa++;
          }

          //  Palautetaan vanha query..
          $query = $lue_data_query;

          if ($hylkaa == 0) {

            // tuotepaikkojen oletustyhjennysquery uskalletaan ajaa vasta t�ss�
            if ($tpupque != '') {
              $tpupres = pupe_query($tpupque);
            }

            $tpupque = "";

            // Itse lue_datan p�ivitysquery
            $iresult = pupe_query($query);

            // Synkronoidaan
            if (stripos($yhtiorow["synkronoi"], $table_mysql) !== FALSE) {
              if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'LISAA') {
                $syncrow = array();
                $tunnus  = mysql_insert_id($GLOBALS["masterlink"]);
              }
              else {
                $syncrow = mysql_fetch_array($syncres);
                $tunnus  = $syncrow["tunnus"];
              }

              synkronoi($kukarow["yhtio"], $table_mysql, $tunnus, $syncrow, "");
            }

            // tehd��n ep�kunrattijutut
            if ($tee == "paalle" or $tee == "25paalle" or $tee == "puolipaalle" or $tee == "75paalle" or $tee == "pois" or $tee == "peru") {
              $from = "lue_data";
              require "epakurantti.inc";
            }

            // Tapahtumat tuotepaikoille kuntoon!
            if (($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'POISTA' or $taulunrivit[$taulu][$eriviindex][$postoiminto] == 'LISAA') and $table_mysql == 'tuotepaikat') {
              if ($taulunrivit[$taulu][$eriviindex][$postoiminto] == 'POISTA') {
                $tapahtumaselite = t("Poistettiin tuotepaikka");
                $tapahtumalaji = "poistettupaikka";
              }
              else {
                $tapahtumaselite = t("Lis�ttiin tuotepaikka");
                $tapahtumalaji = "uusipaikka";
              }
              $tapahtumaselite .= " {$taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYALUE", $taulunotsikot["tuotepaikat"])]} {$taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYNRO", $taulunotsikot["tuotepaikat"])]} {$taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYVALI", $taulunotsikot["tuotepaikat"])]} {$taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYTASO", $taulunotsikot["tuotepaikat"])]}";

              //Tehd��n tapahtuma
              $querytapahtuma = "INSERT into tapahtuma set
                                 yhtio     = '$kukarow[yhtio]',
                                 tuoteno   = '{$taulunrivit["tuotepaikat"][$eriviindex][array_search("TUOTENO", $taulunotsikot["tuotepaikat"])]}',
                                 kpl       = '0',
                                 kplhinta  = '0',
                                 hinta     = '0',
                                 hyllyalue = '{$taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYALUE", $taulunotsikot["tuotepaikat"])]}',
                                 hyllynro  = '{$taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYNRO", $taulunotsikot["tuotepaikat"])]}',
                                 hyllytaso = '{$taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYTASO", $taulunotsikot["tuotepaikat"])]}',
                                 hyllyvali = '{$taulunrivit["tuotepaikat"][$eriviindex][array_search("HYLLYVALI", $taulunotsikot["tuotepaikat"])]}',
                                 laji      = '$tapahtumalaji',
                                 selite    = '$tapahtumaselite',
                                 laatija   = '$kukarow[kuka]',
                                 laadittu  = now()";
              $resulttapahtuma = pupe_query($querytapahtuma);
            }

            $lask++;
          }
        }
      }

      // Meill� oli joku virhe
      if ($tila == 'ohita' or $hylkaa > 0) {
        $api_status = FALSE;
        $lue_data_virheelliset_rivit[$rivilaskuri-1] = $excelrivit[$rivilaskuri-1];
      }
    }

    lue_data_echo("<br><font class='message'>".t("P�ivitettiin")." $lask ".t("rivi�")."!</font><br><br>");

    // Kirjoitetaan LOG fileen lopputagi, jotta tiedet��n ett� ajo on valmis
    if ($lue_data_output_file != "") {
      lue_data_echo("## LUE-DATA-EOF ##");

      // Kirjoitetaan viel� loppuun virheelliset rivit
      if (count($lue_data_virheelliset_rivit) > 0) {

        if (include 'Spreadsheet/Excel/Writer.php') {

          $workbook = new Spreadsheet_Excel_Writer($lue_data_err_file);
          $workbook->setVersion(8);
          $worksheet = $workbook->addWorksheet(t('Virheelliset rivit'));

          $format_bold = $workbook->addFormat();
          $format_bold->setBold();

          $excelrivi = 0;
          $excelsarake = 0;

          $worksheet->write($excelrivi, $excelsarake++, ucfirst(t("Alkuper�inen rivinumero")), $format_bold);

          foreach ($excelrivit[0] as $otsikko) {
            $worksheet->write($excelrivi, $excelsarake++, ucfirst($otsikko), $format_bold);
          }

          $excelrivi++;
          $excelsarake = 0;

          foreach ($lue_data_virheelliset_rivit as $rivinro => $lue_data_virheellinen_rivi) {
            $worksheet->writeNumber($excelrivi, $excelsarake++, ($rivinro+1));

            foreach ($lue_data_virheellinen_rivi as $lue_data_virheellinen_sarake) {
              $worksheet->writeString($excelrivi, $excelsarake++, $lue_data_virheellinen_sarake);
            }

            $excelrivi++;
            $excelsarake = 0;
          }

          // We need to explicitly close the workbook
          $workbook->close();
        }
      }
    }
  }
}

lue_data_echo("<br>".$lue_data_output_text, true);

if (!$cli and !isset($api_kentat)) {
  // Taulut, jota voidaan k�sitell�
  $taulut = array(
    'abc_parametrit'                  => 'ABC-parametrit',
    'asiakas'                         => 'Asiakas',
    'asiakasalennus'                  => 'Asiakasalennukset',
    'asiakashinta'                    => 'Asiakashinnat',
    'asiakaskommentti'                => 'Asiakaskommentit',
    'asiakkaan_avainsanat'            => 'Asiakkaan avainsanat',
    'avainsana'                       => 'Avainsanat',
    'budjetti'                        => 'Budjetti',
    'dynaaminen_puu_avainsanat'      => 'Dynaamisen puun avainsanat',
    'etaisyydet'                      => 'Et�isyydet varastosta',
    'extranet_kayttajan_lisatiedot'   => 'Extranet-k�ytt�j�n lis�tietoja',
    'hinnasto'                        => 'Hinnasto',
    'kalenteri'                       => 'Kalenteritietoja',
    'kuka'                            => 'K�ytt�j�tietoja',
    'kustannuspaikka'                 => 'Kustannuspaikat',
    'lahdot'                    => 'L�hd�t',
    'laite'                           => 'Laiterekisteri',
    'laitteen_sopimukset'             => 'Laitteen sopimukset',
    'liitetiedostot'                  => 'Liitetiedostot',
    'maksuehto'                       => 'Maksuehto',
    'pakkaus'                         => 'Pakkaustiedot',
    'perusalennus'                    => 'Perusalennukset',
    'puun_alkio_asiakas'              => 'Asiakas-segmenttiliitokset',
    'puun_alkio_tuote'                => 'Tuote-segmenttiliitokset',
    'rahtikirjanumero'          => 'LOGY-rahtikirjanumerot',
    'rahtimaksut'                     => 'Rahtimaksut',
    'rahtisopimukset'                 => 'Rahtisopimukset',
    'rekisteritiedot'                 => 'Rekisteritiedot',
    'sanakirja'                       => 'Sanakirja',
    'sarjanumeron_lisatiedot'         => 'Sarjanumeron lis�tiedot',
    'taso'                            => 'Tilikartan rakenne',
    'tili'                            => 'Tilikartta',
    'todo'                            => 'Todo-lista',
    'toimi'                           => 'Toimittaja',
    'toimittajaalennus'               => 'Toimittajan alennukset',
    'toimittajahinta'                 => 'Toimittajan hinnat',
    'toimitustapa'                    => 'Toimitustavat',
    'toimitustavan_lahdot'            => 'Toimitustavan l�hd�t',
    'tullinimike'                     => 'Tullinimikkeet',
    'tuote'                           => 'Tuote',
    'tuotepaikat'                     => 'Tuotepaikat',
    'tuoteperhe'                      => 'Tuoteperheet',
    'tuotteen_alv'                    => 'Tuotteiden ulkomaan ALV',
    'tuotteen_avainsanat'             => 'Tuotteen avainsanat',
    'tuotteen_orginaalit'             => 'Tuotteiden originaalit',
    'tuotteen_toimittajat'            => 'Tuotteen toimittajat',
    'tuotteen_toimittajat_pakkauskoot' => 'Tuotteen toimittajan pakkauskoot',
    'tuotteen_toimittajat_tuotenumerot' => 'Tuotteen toimittajan vaihtoehtoiset tuotenumerot',
    'vak'                             => 'VAK/ADR-tietoja',
    'vak_imdg'                        => 'VAK/IMDG-tietoja',
    'varaston_hyllypaikat'            => 'Varaston hyllypaikat',
    'yhteyshenkilo'                   => 'Yhteyshenkil�t',
    'yhtion_toimipaikat'              => 'Toimipaikka',
  );

  // Yhti�kohtaisia
  if (table_exists('auto_vari_tuote')) {
    $taulut['auto_vari']              = 'Autov�ri-datat';
    $taulut['auto_vari_tuote']        = 'Autov�ri-v�rikirja';
    $taulut['auto_vari_korvaavat']    = 'Autov�ri-korvaavat';
  }

  if (table_exists('yhteensopivuus_tuote')) {
    $taulut['autodata']                        = 'Autodatatiedot';
    $taulut['autodata_tuote']                  = 'Autodata tuotetiedot';
    $taulut['yhteensopivuus_auto']             = 'Yhteensopivuus automallit';
    $taulut['yhteensopivuus_auto_2']           = 'Yhteensopivuus automallit 2';
    $taulut['yhteensopivuus_mp']               = 'Yhteensopivuus mp-mallit';
    $taulut['yhteensopivuus_rekisteri']        = 'Yhteensopivuus rekisterinumerot';
    $taulut['yhteensopivuus_tuote']            = 'Yhteensopivuus tuotteet';
    $taulut['yhteensopivuus_tuote_lisatiedot'] = 'Yhteensopivuus tuotteet lis�tiedot';
    $taulut['yhteensopivuus_tuote_sensori']    = 'Yhteensopivuus tuotteet sensorit';
    $taulut['yhteensopivuus_tuote_sensori_lisatiedot'] = 'Yhteensopivuus tuotteet sensorit lis�tiedot';
    $taulut['rekisteritiedot_lisatiedot']      = 'Rekisteritiedot lisatiedot';
    $taulut['yhteensopivuus_valmistenumero']   = 'Yhteensopivuus valmistenumero';
  }

  if (table_exists('td_pc') and table_exists('autoid_lisatieto') and file_exists("lue_data_autoid.php")) {
    $taulut['autoid_lisatieto'] = 'Autoid-lisatieto';
  }

  // Taulut aakkosj�rjestykseen
  asort($taulut);

  // Selectoidaan aktiivi
  $sel = array_fill_keys(array($table), " selected") + array_fill_keys($taulut, '');

  echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='tee' value='file'>";
  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Valitse tietokannan taulu").":</th>";
  echo "<td>";
  echo "<select name='table' onchange='submit();'>";

  $_taulu = '';

  // Jos tullaan linkist� jossa halutaan muokata vain tietty� taulua
  if (isset($taulurajaus)) {
    $validi = $taulut[$taulurajaus];
    // Tarkistetaan ett� taulu on m��ritelty
    if (!empty($validi)) {
      echo "<option value='$taulurajaus' selected>".t("$validi")."</option>";
    }
    else {
      echo "<option value='' selected>".t("Ei valintaa")."</option>";
    }

  }
  else {
    foreach ($taulut as $taulu => $nimitys) {
      echo "<option value='$taulu' {$sel[$taulu]}>".t($nimitys)."</option>";
      if (trim($sel[$taulu]) == 'selected') {
        $_taulu = $taulu;
      }
    }
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  // Tiettyjen taulujen spessuvalinnat
  require "inc/luedata_ja_dataimport_spessuvalinnat.inc";

  // Taulujen pakolliset sarakkeet ym kuvauksia.
  require "inc/pakolliset_sarakkeet.inc";

  if (empty($_taulu)) {
    $taulut = array_flip($taulut);
    $_taulu = array_shift($taulut);
  }

  list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattavat, $saakopoistaa, $oletukset) = pakolliset_sarakkeet($_taulu);

  echo "  <tr><td class='tumma'>".t("Tietokantataulun pakolliset tiedot").":</td>";
  echo "  <td>".strtolower(implode(", ", $pakolliset))."</td></tr>";

  if (!empty($wherelliset)) {
    echo "  <tr><td class='tumma'>".t("Sarakkeet jotka pit�� aineistossa kertoa").":</td>";
    echo "  <td>".strtolower(implode(", ", $wherelliset))."</td></tr>";
  }

  if (!empty($kielletyt)) {
    echo "  <tr><td class='tumma'>".t("Sarakkeet joita ei saa aineistossa kertoa").":</td>";
    echo "  <td>".strtolower(implode(", ", $kielletyt))."</td></tr>";
  }

  echo "  <tr><th>".t("Valitse tiedosto").":</th>
        <td><input name='userfile' type='file'></td>
      <td class='back'><input type='submit' name='laheta' value='".t("L�het�")."'></td>
      </tr>

      </table>
    </form>
    <br>";
}
// Jos on muutettu sopimusrivitunnuksia niin ajetaan sopimusrivien p�ivitysfunktio
if (count($muutetut_sopimusrivitunnukset) > 0) {
  paivita_sopimusrivit($muutetut_sopimusrivitunnukset);
}

if (!isset($api_kentat)) require "inc/footer.inc";
