<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if ($argv[1] == '') {
  echo "Anna kuka!!!\n";
  die;
}

// otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

if ($argv[2] != "") {
  $lpvm_aikaa = $argv[2];
}

if ($argv[3] != "") {
  $kpvm_aikaa = $argv[3];
}

$query    = "SELECT * from kuka where kuka='$argv[1]' limit 1";
$kukares = pupe_query($query);
if (mysql_num_rows($kukares) == 0) die("Karhuajaa ei löyry!\n$query\n");
$kukarow = mysql_fetch_array($kukares);

$query    = "SELECT * from yhtio where yhtio='$kukarow[yhtio]'";
$yhtiores = pupe_query($query);
if (mysql_num_rows($yhtiores) == 0) die("Firmaa ei löyry!\n");
$yhtiorow = mysql_fetch_array($yhtiores);

$query = "SELECT *
          FROM yhtion_parametrit
          WHERE yhtio='$kukarow[yhtio]'";
$result = pupe_query($query);

if (mysql_num_rows($result) == 1) {
  $yhtion_parametritrow = mysql_fetch_array($result);

  // lisätään kaikki yhtiorow arrayseen
  foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
    $yhtiorow[$parametrit_nimi] = $parametrit_arvo;
  }
}

$yhteyshenkilo = $kukarow["tunnus"];
$karhuakaikki = "JOO";
$tee = "ALOITAKARHUAMINEN";

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)));
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)."/inc"));
chdir("myyntires");

$outi = require "myyntires/karhu.php";

echo strip_tags(str_replace("<br>", "\n", $outi));

require "inc/footer.inc";
