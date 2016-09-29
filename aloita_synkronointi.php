<?php

require "inc/parametrit.inc";

// Laitetaan max time 5H
ini_set("max_execution_time", 18000);
ini_set("mysql.connect_timeout", 600);

echo "<font class='head'>".t("Alkusynkronointi")."</font><hr>";

if (!isset($tee)) $tee = "";

if ($tee == "SYNK") {

  //  Onko mahdollista synkronoida?
  if (substr($table, 0, 9) == "avainsana") {
    if (strpos($yhtiorow["synkronoi"], substr($table, 0, 9)) === false) {
      echo "VIRHE: Pyydettyä taulua $table ei voida synkronoida, sitä ei ole määritelty!";
      exit;
    }

    $table = substr($table, 0, 9);

    $abulisa = preg_match("/(^|,)(avainsana\|*([\|a-zA-Z_\-]*))($|,)/i", $yhtiorow["synkronoi"], $regs);

    if ($regs[3] != "") {
      $la = explode("|", $regs[3]);

      $lajit  = " and laji in (";

      foreach ($la as  $l) {
        $lajit .= "'$l',";
      }
      $lajit = substr($lajit, 0, -1);

      $lajit .= ")";
    }
  }
  else {
    if (strpos($yhtiorow["synkronoi"], $table) === false or $table == "") {
      echo "VIRHE: Pyydettyä taulua $table ei voida synkronoida, sitä ei ole määritelty!";
      exit;
    }
    $lajit = "";
  }

  require_once "inc/pakolliset_sarakkeet.inc";

  list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattavat, $saakopoistaa, $oletukset, $eisaaollatyhja) = pakolliset_sarakkeet($table);

  if (count($wherelliset) == 0 and count($pakolliset) == 0) {
    echo "VIRHE: Pyydettyä taulua $table ei voida synkronoida, sitä ei ole määritelty!<br>";
    exit;
  }
  else {
    //  Tehdään kysely
    $query = "SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
              FROM yhtio
              JOIN yhtion_parametrit ON (yhtion_parametrit.yhtio = yhtio.yhtio)
              WHERE konserni = '$yhtiorow[konserni]'
              AND (synkronoi = '$table' or synkronoi like '$table,%' or synkronoi like '%,$table,%' or synkronoi like '%,$table' or synkronoi like '%,$table|%' or synkronoi like '$table|%')";
    $kohderes = pupe_query($query);
    $kohderow = mysql_fetch_array($kohderes);

    if (strlen($kohderow["yhtiot"]) == 0) {
      echo "VIRHE: Pyydettyä taulua $table ei voida synkronoida, yhtiö ei löydy!<br>$query";
      exit;
    }

    $group = " group by ";

    $indeksi = array_merge($wherelliset, $pakolliset);
    $indeksi = array_unique($indeksi);

    foreach ($indeksi as $pakollinen) {
      $group .= strtolower($pakollinen).",";
    }

    $group = substr($group, 0, -1);

    $lisa = "";

    if ($table == "asiakas") {
      $lisa = ", maksuehto READ, toimitustapa READ";
    }

    if ($table == "tuotteen_toimittajat") {
      $lisa = ", tuote READ, toimi READ";
    }

    if ($table == "tuote") {
      $lisa = ", valuu READ";
    }

    if ($table != "avainsana") {
      $lisa .= ", avainsana READ";
    }


    $query = "LOCK TABLES yhtio READ, yhtion_parametrit READ,
              synclog WRITE, $table WRITE $lisa";
    $abures = pupe_query($query);

    $query = "SELECT group_concat(tunnus) tunnukset
              FROM $table
              WHERE yhtio in ($kohderow[yhtiot])
              $lajit
              $group";
    $abures = pupe_query($query);

    while ($aburow = mysql_fetch_array($abures)) {
      $query = "SELECT *
                FROM $table
                WHERE tunnus in ($aburow[tunnukset])
                ORDER BY if(muutospvm = '0000-00-00 00:00:00', luontiaika, muutospvm) DESC
                LIMIT 1";
      $abures1 = pupe_query($query);

      while ($aburow1 = mysql_fetch_assoc($abures1)) {
        synkronoi($aburow1["yhtio"], $table, $aburow1["tunnus"], $aburow1, "F");
      }
    }

    $query = "UNLOCK TABLES";
    $abures = pupe_query($query);

    echo "<font class='error'>$table -synkronointi valmis!</font><br><br>";
  }

  $tee = "";
}

if ($tee == "") {
  $synkattavat = explode(',', $yhtiorow["synkronoi"]);

  echo "<form method='post'>
      <input type='hidden' name='tee' value='SYNK'>";

  echo "<select name='table'>";

  foreach ($synkattavat as $synk) {
    echo "<option value='$synk'>$synk</option>";
  }

  echo "</select><br><br>";
  echo "<input type='submit' value='".t("Synkronoi")."'></form><br><br>";
}

require "inc/footer.inc";
