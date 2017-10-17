<?php

if (php_sapi_name() == "cli") {
  mysql_close($link);
  exit;
}

if (isset($formi) and isset($kentta)) {
  echo "<script LANGUAGE='JavaScript'>window.document.$formi.$kentta.focus();</script>";
}

$timeparts = explode(" ", microtime());
$endtime   = $timeparts[1].substr($timeparts[0], 1);
$aika      = round($endtime-$starttime, 4);

if (array_key_exists("password", $_REQUEST)) {
  $_REQUEST["password"] = "";
}

if (array_key_exists("salasana", $_REQUEST)) {
  $_REQUEST["salasana"] = "";
}

if (!isset($verkkokauppa) or $verkkokauppa == "") {
  $suoque = "INSERT INTO suorituskykyloki
             SET yhtio     = '$kukarow[yhtio]',
             skripti       = '".$_SERVER['SCRIPT_NAME']."',
             iposoite      = '".$_SERVER['REMOTE_ADDR']."',
             request       = '".mysql_real_escape_string(serialize($_REQUEST))."',
             suoritusalku  = '".date("Y-m-d H:i:s", $starttime)."',
             suoritusloppu = '".date("Y-m-d H:i:s", $endtime)."',
             suoritusaika  = '$aika',
             laatija       = '$kukarow[kuka]',
             luontiaika    = now()";
  $suores = pupe_query($suoque, $GLOBALS["masterlink"]);
}

$lopeta_body = TRUE;
$piirra_sanakirja = TRUE;

// Piilotetaan koko body
if ((isset($nayta_pdf) and $nayta_pdf == 1) or (isset($lataa_tiedosto) and $lataa_tiedosto == 1)) {
  $lopeta_body = FALSE;
  $piirra_sanakirja = FALSE;
}

// Piilotetaan koko body
if (isset($no_head) and $no_head == "yes") {
  $lopeta_body = FALSE;
  $piirra_sanakirja = FALSE;
}

// Piilotetaan koko body
if (isset($ohje) and $ohje == "off") {
  $piirra_kello = FALSE;
  $piirra_sanakirja = FALSE;
}

// Piilotetaan sanakirja
if (strpos($_SERVER['SCRIPT_NAME'], "sanakirja.php") !== FALSE or !isset($kaannetyt_sanat) or count($kaannetyt_sanat) == 0 or !tarkista_oikeus("sanakirja.php", '', '1')) {
  $piirra_sanakirja = FALSE;
}

if ($lopeta_body) {
  echo "<script>top.document.title = $('font.head').first().text().replace(\":\", \"\")+\" / Pupesoft\";</script>";

  if ($piirra_sanakirja) {
    switch (strtoupper($kukarow["kieli"])) {
    case "EN":
      $kieli  = "en";
      $kindex = 0;
      break;
    case "SE":
      $kieli  = "se";
      $kindex = 1;
      break;
    case "DE":
      $kieli  = "de";
      $kindex = 2;
      break;
    case "DK":
      $kieli  = "dk";
      $kindex = 3;
      break;
    case "EE":
      $kieli  = "ee";
      $kindex = 4;
      break;
    default:
      $kieli  = "en";
      $kindex = 0;
    }

    echo "<br><br>";
    echo "<form name='sanakirjaform' method='post' action='${palvelin2}sanakirja.php'>";
    echo "<input type='hidden' name='kieli[$kindex]' value='$kieli'>";
    echo "<input type='hidden' name='show' value='all'>";
    echo "<input type='hidden' name='etsi_kieli' value='fi'>";
    echo "<input type='hidden' name='tarkkahaku' value='ON'>";
    echo "<input type='hidden' name='hakusana' value='".implode("\n", $kaannetyt_sanat)."'>";
    echo " <a style='cursor:pointer' onclick='sanakirjaform.submit()'><font class='info'>".t("Sanakirja")."</font></a>";
    echo "</form>";
  }

  echo "<br><br>";

  if ($kukarow["extranet"] != "") {
    echo $yhtiorow["web_seuranta"];
  }

  if (isset($GLOBALS["pupe_query_debug"]) and $GLOBALS["pupe_query_debug"] > 0) {

    echo "<font style='font-family: monospace; font-size: 9pt; white-space: nowrap;'>";

    $loppumem = memory_get_usage();
    echo "php: ".sprintf("%.04f", $aika)." sec, ".round((($loppumem - $alkumem) / 1024 / 1024), 2)." mb<br>";
    if (!empty($aika_debug_array)) {
      echo "sql: ".sprintf("%.04f", array_sum($aika_debug_array))." sec, ".sprintf("%.04f", array_sum($aika_debug_array) / count($aika_debug_array))." sec/query (".count($aika_debug_array)." queries)<br>";
    }
    echo "<hr>";

    if (!empty($aika_debug_array)) {
      // Sortataan kestojärjestykseen
      array_multisort($aika_debug_array, SORT_DESC, $quer_debug_array);
    }

    for ($i=0; $i<count($aika_debug_array); $i++) {
      if ($aika_debug_array[$i] > $GLOBALS["pupe_query_debug"]) {
        echo sprintf("%.04f", $aika_debug_array[$i]).": ", query_dump($quer_debug_array[$i]);
      }
    }

    echo "</font>";
    echo "<br><br>";
  }

  echo "</body>";
  echo "</html>";
}

if (isset($GLOBALS["masterlink"])) mysql_close($GLOBALS["masterlink"]);
if (isset($GLOBALS["slavelink"]))  mysql_close($GLOBALS["slavelink"]);
