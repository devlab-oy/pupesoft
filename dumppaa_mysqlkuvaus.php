<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

if ($php_cli) {
  // otetaan includepath aina rootista
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  // otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  // dummy yhtio (menee ainoastaan filenimeen)
  $kukarow["yhtio"] = "crond";
}
else {
  require "inc/parametrit.inc";
}

//Hardcoodataan failin nimi /tmp diririkkaan
$tmpfilenimi = $kukarow["yhtio"]."_mysqlkuvays.sql";

if (!$php_cli) echo "<font class='head'>".t("SQL-tietokantarakenne").":</font><hr>";

$ulos = array();

// Jos ollaan annettu poikkeava MySQL portti hostnamessa, pitää se erotella komentorivityökalua varten
if (strpos($dbhost, ":") !== false) {
  list($dbhost, $dbport) = explode(":", $dbhost);
}
else {
  $dbport = 3306;
}

// /usr/bin/mysqldump --> toimii ainakin fedorassa ja ubuntussa by default
if (file_exists("/usr/bin/mysqldump")) {
  $mysql_dump_path = "/usr/bin/mysqldump";
}
elseif (file_exists("/usr/local/bin/mysqldump")) {
  $mysql_dump_path = "/usr/local/bin/mysqldump";
}
else {
  $mysql_dump_path = "mysqldump";
}

exec("$mysql_dump_path --user=$dbuser --host=$dbhost --port=$dbport --password=$dbpass --no-data $dbkanta", $ulos);

if (!$toot = fopen("/tmp/".$tmpfilenimi, "w")) die("Filen /tmp/$tmpfilenimi luonti epäonnistui!");

foreach ($ulos as $print) {
  // poistetaan mysql-sarakkeen kommentti koska se kaataa sqlupdate-ohjelman
  $print = preg_replace("/ COMMENT '[^']*',/", ",", $print);

  fputs($toot, $print."\n");
}

$curlfile = "/tmp/".$tmpfilenimi;

// Löytyykö alttereita?
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://pupeapi.sprintit.fi/sqlupdate.php");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, array('tee' => "remotefile", 'userfile' => "@$curlfile"));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
$result = curl_exec($ch);

unlink($curlfile);

if ($result === FALSE) {
  echo "<font class='error'>VIRHE:</font><br>\n";
  echo curl_errno($ch) . " - " . curl_error($ch) . "</font><br>";
  exit(1);
}
curl_close($ch);

$alterit = trim($result);

// Löytyykö custom updateja?
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://pupeapi.sprintit.fi/sqlupdate.sql");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
$result = curl_exec($ch);

if ($result === FALSE) {
  echo "<font class='error'>VIRHE:</font><br>\n";
  echo curl_errno($ch) . " - " . curl_error($ch) . "</font><br>";
  exit(1);
}
curl_close($ch);

$updatet = trim($result);

// Yhdistetään
$result = $alterit."\n".$updatet;

// Poistetaan vielä tuplaspacet jos sellasia on
$result = trim(preg_replace("/ {2,}/", " ", $result));

if ($php_cli and $result != "") {
  echo $result;
}
elseif (!$php_cli) {

  if ($result != "") {
    echo t("Tarvittavat muutokset");
    echo ":<hr>";
    echo "<pre>$result</pre>";
  }
  else {
    echo t("Tietokanta ajantasalla!");
  }

  require "inc/footer.inc";
}
