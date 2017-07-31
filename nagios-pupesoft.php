<?php

require "inc/salasanat.php";

$STATE_OK        = 0;
$STATE_WARNING   = 1;
$STATE_CRITICAL  = 2;
$STATE_UNKNOWN   = 3;
$STATE_DEPENDENT = 4;

if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' or $_SERVER['REMOTE_ADDR'] == '::1' or $_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR'] or $_SERVER['REMOTE_ADDR'] == '185.11.208.68') {

  if ($_GET["tee"] == "MYSQL") {
    $link = mysql_connect($dbhost, $dbuser, $dbpass) or die ("CRITICAL - mysql_connect() failed $STATE_CRITICAL");
    mysql_select_db($dbkanta) or die ("CRITICAL - mysql_select_db() failed $STATE_CRITICAL");

    echo "OK - Mysql connection ok $STATE_OK";
    exit;
  }

  if ($_GET["tee"] == "CONNECTION_USAGE_SLAVE") {

    if (isset($nagios_slavedb)) {
      $slaveprefix = "nagios_";
    }
    else {
      $slaveprefix = "";
    }

    if (!isset(${$slaveprefix."slavedb"})) {
      echo "CRITICAL - Slave username/password/database not set $STATE_CRITICAL";
      exit;
    }

    $critical = array();
    $warning  = array();

    foreach (${$slaveprefix."slavedb"} as $si => $devnull) {
      if (isset(${$slaveprefix."slavedb"}[$si]) and ${$slaveprefix."slavedb"}[$si] != "" and isset(${$slaveprefix."slaveuser"}[$si]) and ${$slaveprefix."slaveuser"}[$si] != "" and isset(${$slaveprefix."slavepass"}[$si]) and ${$slaveprefix."slavepass"}[$si] != "") {
        $link = mysql_connect(${$slaveprefix."slavedb"}[$si], ${$slaveprefix."slaveuser"}[$si], ${$slaveprefix."slavepass"}[$si]) or die ("CRITICAL - mysql_connect() failed on slave$si $STATE_CRITICAL");
        mysql_select_db($dbkanta) or die ("CRITICAL - mysql_select_db() failed on slave$si $STATE_CRITICAL");

        $query = "SHOW /*!50000 GLOBAL */ VARIABLES like 'max_connections'";
        $res = mysql_query($query) or die(mysql_error());
        $row = mysql_fetch_assoc($res);

        $max_connections = (int) $row["Value"];

        $query = "SHOW /*!50000 GLOBAL */ STATUS like 'max_used_connections'";
        $res = mysql_query($query) or die(mysql_error());
        $row = mysql_fetch_assoc($res);

        $max_used_connections = (int) $row["Value"];

        $used_percentage = round($max_used_connections / $max_connections * 100);

        // Nostetaan virhe jos tilanne on huolestuttava
        if ($used_percentage >= 60 and $used_percentage < 80) {
          $warning[] = "WARNING - Slave$si highest usage of available connections: {$used_percentage}% ({$max_used_connections}/{$max_connections})";
        }
        elseif ($used_percentage > 80) {
          $critical[] = "CRITICAL - Slave$si highest usage of available connections: {$used_percentage}% ({$max_used_connections}/{$max_connections})";
        }
      }
      else {
        echo "CRITICAL - Slave$si username/password/database not set $STATE_CRITICAL";
        exit;
      }
    }

    if (count($critical) > 0) {
      echo implode(" ", array_merge($critical, $warning))." ".$STATE_CRITICAL;
    }
    elseif (count($warning) > 0) {
      echo implode(" ", $warning)." ".$STATE_WARNING;
    }
    else {
      echo "OK - Highest usage of available connections on slaves is normal $STATE_OK";
    }
    exit;
  }

  if ($_GET["tee"] == "CONNECTION_USAGE") {

    $link = mysql_connect($dbhost, $dbuser, $dbpass) or die ("CRITICAL - mysql_connect() failed $STATE_CRITICAL");
    mysql_select_db($dbkanta) or die ("CRITICAL - mysql_select_db() failed $STATE_CRITICAL");

    $query = "SHOW /*!50000 GLOBAL */ VARIABLES like 'max_connections'";
    $res = mysql_query($query) or die(mysql_error());
    $row = mysql_fetch_assoc($res);

    $max_connections = (int) $row["Value"];

    $query = "SHOW /*!50000 GLOBAL */ STATUS like 'max_used_connections'";
    $res = mysql_query($query) or die(mysql_error());
    $row = mysql_fetch_assoc($res);

    $max_used_connections = (int) $row["Value"];

    $used_percentage = round($max_used_connections / $max_connections * 100);

    // Nostetaan virhe jos tilanne on huolestuttava
    if ($used_percentage >= 60 and $used_percentage < 80) {
      echo "WARNING - Highest usage of available connections: {$used_percentage}% ({$max_used_connections}/{$max_connections}) $STATE_WARNING";
      exit;
    }
    elseif ($used_percentage > 80) {
      echo "CRITICAL - Highest usage of available connections: {$used_percentage}% ({$max_used_connections}/{$max_connections}) $STATE_CRITICAL";
      exit;
    }
    else {
      echo "OK - Highest usage of available connections: {$used_percentage}% ({$max_used_connections}/{$max_connections}) $STATE_OK";
      exit;
    }
  }

  if ($_GET["tee"] == "SLAVE_STATUS") {
    if (isset($nagios_slavedb)) {
      $slaveprefix = "nagios_";
    }
    else {
      $slaveprefix = "";
    }

    if (!isset(${$slaveprefix."slavedb"})) {
      echo "CRITICAL - Slave username/password/database not set $STATE_CRITICAL";
      exit;
    }

    $critical = array();
    $warning  = array();

    foreach (${$slaveprefix."slavedb"} as $si => $devnull) {
      if (isset(${$slaveprefix."slavedb"}[$si]) and ${$slaveprefix."slavedb"}[$si] != "" and isset(${$slaveprefix."slaveuser"}[$si]) and ${$slaveprefix."slaveuser"}[$si] != "" and isset(${$slaveprefix."slavepass"}[$si]) and ${$slaveprefix."slavepass"}[$si] != "") {

        $link = mysql_connect(${$slaveprefix."slavedb"}[$si], ${$slaveprefix."slaveuser"}[$si], ${$slaveprefix."slavepass"}[$si]) or die ("CRITICAL - mysql_connect() failed on slave$si $STATE_CRITICAL");
        mysql_select_db($dbkanta) or die ("CRITICAL - mysql_select_db() failed on slave$si $STATE_CRITICAL");

        $query = "SHOW /*!50000 SLAVE */ STATUS";
        $res = mysql_query($query) or die(mysql_error());
        $row = mysql_fetch_assoc($res);

        if ($row["Slave_IO_Running"] != "Yes") {
          $critical[] = "CRITICAL - Slave$si IO Not running";
        }
        elseif ($row["Slave_SQL_Running"] != "Yes") {
          $critical[] = "CRITICAL - Slave$si SQL Not running";
        }
        elseif ($row["Seconds_Behind_Master"] == "NULL") {
          $critical[] = "CRITICAL - Slave$si Seconds_Behind_Master is NULL";
        }
        elseif ($row["Seconds_Behind_Master"] > 600) {
          $critical[] = "CRITICAL - Slave$si 10 minutes behind master";
        }
        elseif ($row["Seconds_Behind_Master"] > 300) {
          $warning[] = "WARNING - Slave$si 5 minutes behind master";
        }
      }
      else {
        echo "CRITICAL - Slave$si username/password/database not set $STATE_CRITICAL";
        exit;
      }
    }

    if (count($critical) > 0) {
      echo implode(" ", array_merge($critical, $warning))." ".$STATE_CRITICAL;
    }
    elseif (count($warning) > 0) {
      echo implode(" ", $warning)." ".$STATE_WARNING;
    }
    else {
      echo "OK - Slaves OK $STATE_OK";
    }
    exit;
  }

  if ($_GET["tee"] == "VERKKOLASKU_FTP_STATUS") {
    $pupe_root_polku = dirname(__FILE__);

    function tsekkaa_verkkolaskufile($kansio) {
      if ($handle = opendir($kansio)) {
        while (($lasku = readdir($handle)) !== FALSE) {
          // Yli vuorokauden vanha laskufile, laitetaan tästä erroria
          if ((substr(strtoupper($lasku), -4) == '.XML' or substr(strtoupper($lasku), -4) == '.EDI') and mktime()-filemtime($kansio.$lasku) > 86400) {
            echo "CRITICAL - Verkkolaskujen FTP-lahetys jumissa $STATE_CRITICAL";
            exit;
          }
        }

        closedir($handle);
      }
    }

    // PUPEVOICE
    tsekkaa_verkkolaskufile("{$pupe_root_polku}/dataout/pupevoice_error/");

    // IPOST FINVOICE
    tsekkaa_verkkolaskufile("{$pupe_root_polku}/dataout/ipost_error/");

    // ELMAEDI
    tsekkaa_verkkolaskufile("{$pupe_root_polku}/dataout/elmaedi_error/");

    // PUPESOFT-FINVOICE
    tsekkaa_verkkolaskufile("{$pupe_root_polku}/dataout/sisainenfinvoice_error/");

    echo "OK - Verkkolaskujen FTP-lahetys OK $STATE_OK";
    exit;
  }

  if ($_GET["tee"] == "KARDEX_SSCC_JONO") {
    if (isset($kardex_sscc) and $kardex_sscc != "") {
      if ($handle = opendir($kardex_sscc)) {
        while (($file = readdir($handle)) !== FALSE) {
          if (is_file($kardex_sscc."/".$file) and mktime()-filemtime($kardex_sscc."/".$file) >= 300) {
            echo "CRITICAL - Kardexfile ($file) over 5 minutes old $STATE_CRITICAL";
            exit;
          }
        }

        echo "OK - Kardex SSCC queue OK $STATE_OK";
        exit;
      }
      else {
        echo "CRITICAL - $kardex_sscc directory could not be opened $STATE_CRITICAL";
        exit;
      }
    }
    else {
      echo "CRITICAL - $kardex_sscc directory not set $STATE_CRITICAL";
      exit;
    }
  }

  if ($_GET["tee"] == "HA_PROXY") {

    if (!isset($haproxy)) {
      echo "CRITICAL - HaProxy username/password not set $STATE_CRITICAL";
      exit;
    }

    $critical = array();
    $warning  = array();

    foreach ($haproxy as $si => $devnull) {
      if (isset($haproxy[$si]) and $haproxy[$si] != "" and isset($haproxyuser[$si]) and $haproxyuser[$si] != "" and isset($haproxypass[$si]) and $haproxypass[$si] != "") {

        $link = mysql_connect($haproxy[$si], $haproxyuser[$si], $haproxypass[$si]) or die ("CRITICAL - mysql_connect() failed on HaProxy$si $STATE_CRITICAL");
        mysql_select_db($dbkanta) or die ("CRITICAL - mysql_select_db() failed on HaProxy$si $STATE_CRITICAL");

        $query = "SELECT 1+1 as summa";
        $res = mysql_query($query) or die(mysql_error());
        $row = mysql_fetch_assoc($res);

        // Nostetaan virhe jos tilanne on huolestuttava
        if ($row["summa"] != 2) {
          $critical[] = "CRITICAL - No MySQL connection from HaProxy$si";
        }
      }
      else {
        echo "CRITICAL - HaProxy$si username/password not set $STATE_CRITICAL";
        exit;
      }
    }

    if (count($critical) > 0) {
      echo implode(" ", array_merge($critical, $warning))." ".$STATE_CRITICAL;
    }
    elseif (count($warning) > 0) {
      echo implode(" ", $warning)." ".$STATE_WARNING;
    }
    else {
      echo "OK - HaProxy$si running $STATE_OK";
    }
    exit;
  }

  if (!isset($_GET["tee"]) or $_GET["tee"] == "") {

    $errorlog = exec("tail -n 1 /home/nagios/nagios-pupesoft.log");

    if (trim($errorlog) != "") {
      echo "CRITICAL - PUPESOFT VIRHE! $errorlog $STATE_CRITICAL";
      exit;
    }

    echo "OK - Pupesoft OK $STATE_OK";
  }
}
else {
  echo "CRITICAL - Permission denied $STATE_CRITICAL";
  exit;
}
