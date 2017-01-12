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
      echo "VIRHE: Pyydetty� taulua $table ei voida synkronoida, sit� ei ole m��ritelty!";
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
      echo "VIRHE: Pyydetty� taulua $table ei voida synkronoida, sit� ei ole m��ritelty!";
      exit;
    }
    $lajit = "";
  }

  require_once "inc/pakolliset_sarakkeet.inc";

  list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattavat, $saakopoistaa, $oletukset, $eisaaollatyhja) = pakolliset_sarakkeet($table);

  if (count($wherelliset) == 0 and count($pakolliset) == 0) {
    echo "VIRHE: Pyydetty� taulua $table ei voida synkronoida, sit� ei ole m��ritelty!<br>";
    exit;
  }

  // Synkronointi laitetaan p��lle yhti�n parametreiss� antamalla synkronoi -kentt��n arvoksi
  // taulun nimi�, tai avainsanojen lajeja, pilkulla eroteltuna. Lis�ksi yhti�t tulee kuulua
  // samaan konserniin.
  // esim: asiakas,avainsana|ASAVAINSANA,rahtisopimukset
  $query = "SELECT group_concat(concat('\'', yhtio.yhtio ,'\'')) as yhtiot,
            count(distinct yhtio.yhtio) as kpl
            FROM yhtio
            JOIN yhtion_parametrit ON (yhtion_parametrit.yhtio = yhtio.yhtio)
            WHERE konserni = '$yhtiorow[konserni]'
            AND (
              synkronoi = '$table'        or
              synkronoi like '$table,%'   or
              synkronoi like '%,$table,%' or
              synkronoi like '%,$table'   or
              synkronoi like '%,$table|%' or
              synkronoi like '$table|%'
            )";
  $kohderes = pupe_query($query);
  $kohderow = mysql_fetch_array($kohderes);

  if (strlen($kohderow["yhtiot"]) == 0) {
    echo "VIRHE: Pyydetty� taulua $table ei voida synkronoida, yhti� ei l�ydy!<br>$query";
    exit;
  }

  if ($kohderow["kpl"] <= 1) {
    echo "VIRHE: Synkroinointi pit�� olla asetettuna v�hint��n kahdessa yrityksess�.";
    exit;
  }

  if (synkronoi_tarkista_pakolliset($table) === false) {
    echo "VIRHE: synkronoi_ainoastaan virheellinen. Pakolliset kent�t on pakko synkronoida!";
    exit;
  }

  // rakennetaaan kysely, jolla haetaan pakolliset tiedot molemmista yrityksist�
  // ja groupataan niiden mukaan. t�ll� haetaan "duplikaatit", eli tieto on jo synkronissa
  // valittujen yhti�iden v�lill�.
  $group = " group by ";

  $indeksi = array_merge($wherelliset, $pakolliset);
  $indeksi = array_unique($indeksi);

  foreach ($indeksi as $pakollinen) {
    $group .= strtolower($pakollinen).",";
  }

  $group = substr($group, 0, -1);

  // rakennetaan lock tables kysely dynaamisesti
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

  $query = "LOCK TABLES yhtio READ, yhtion_parametrit READ, synclog WRITE, $table WRITE $lisa";
  $abures = pupe_query($query);

  $query = "SELECT group_concat(tunnus) tunnukset
            FROM $table
            WHERE yhtio in ($kohderow[yhtiot])
            $lajit
            $group";
  $abures = pupe_query($query);

  // loopataan synkronoitavat tietueet
  while ($aburow = mysql_fetch_array($abures)) {
    // jos meill� on tietue molemmissa yrityksiss�, valitaan niist� uudempi
    // muutosp�iv�n, luontiajan tai tunnuksen mukaan
    $query = "SELECT *
              FROM $table
              WHERE tunnus in ($aburow[tunnukset])
              ORDER BY if(muutospvm = '0000-00-00 00:00:00', luontiaika, muutospvm) DESC, tunnus DESC
              LIMIT 1";
    $abures1 = pupe_query($query);

    while ($aburow1 = mysql_fetch_assoc($abures1)) {
      // k�ytet��n uudempaa tietuetta masterina, ja tehd��n synkronointi
      // parametrit: yhtio, taulun nimi, master tunnus, master array, pakotus vaikka ei muutoksia
      synkronoi($aburow1["yhtio"], $table, $aburow1["tunnus"], $aburow1, "F");
    }
  }

  $query = "UNLOCK TABLES";
  $abures = pupe_query($query);

  echo "<font class='error'>$table -synkronointi valmis!</font><br><br>";

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
