<?php

/*
  Uusista tuotteista raportti sähköpostitse
  php uudet_tuotteet_cron.php yhtio test@test.com
*/

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiö on annettava!!");
}

if (!isset($argv[2]) or $argv[2] == '') {
  die("Sähköpostiosoite on annettava!!");
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

$pupe_root_polku = dirname(dirname(__FILE__));
require "{$pupe_root_polku}/inc/connect.inc";
require "{$pupe_root_polku}/inc/functions.inc";

// Logitetaan ajo
cron_log();

// Yhtiö
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Haetaan aika jolloin tämä skripti on viimeksi ajettu
$datetime_checkpoint = cron_aikaleima("UUSITUOTE_CRON");

// Otetaan mukaan vain edellisen ajon jälkeen muuttuneet
if ($datetime_checkpoint != "") {
  $rajaus = " AND luontiaika > '$datetime_checkpoint'";
}
else {
  $rajaus = " AND luontiaika >= date_sub(now(), interval 24 HOUR)";
}

// Haetaan data
$query = "SELECT *
          FROM tuote
          WHERE yhtio = '$yhtio'
          {$rajaus}
          ORDER BY tuoteno";
$res = pupe_query($query);

// Tallennetaan aikaleima
cron_aikaleima("UUSITUOTE_CRON", date('Y-m-d H:i:s'));
$loppuaika = date("d.m.Y @ G:i:s");

include $pupe_root_polku."/inc/pupeExcel.inc";

$worksheet    = new pupeExcel();
$excelrivi    = 0;

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo date("d.m.Y @ G:i:s") . ": ".sprintf(t("Uusia tuotteita %s kappaletta!"), $rows)."\n";

if ($rows > 0) {
  $excelsarake = 0;
  $worksheet->writeString($excelrivi, $excelsarake, t("Uudet tuotteet ajalta"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $datetime_checkpoint);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, "-");
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $loppuaika);
  $excelrivi++;
  $excelsarake = 0;
  $worksheet->writeString($excelrivi, $excelsarake, $yhtiorow['nimi']);
  $excelrivi++;
  $excelrivi++;
  $excelsarake = 0;
  $worksheet->writeString($excelrivi, $excelsarake, t("Tuotenumero"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Nimitys"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Myyntihinta"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Tuotemerkki"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Laatija"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Luontiaika"));
  $excelrivi++;
}

while ($row = mysql_fetch_assoc($res)) {

  $excelsarake = 0;
  $worksheet->writeString($excelrivi, $excelsarake, $row["tuoteno"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["nimitys"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["myyntihinta"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["tuotemerkki"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["laatija"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["luontiaika"]);
  $excelsarake++;
  $excelrivi++;
}

$excelnimi = $worksheet->close();
$niminimi = date("Ymd-Gis")."-".t("uudet_tuotteet").".xlsx";
rename("/tmp/".$excelnimi, "/tmp/".$niminimi);
$filename = "/tmp/".$niminimi;

$parametri = array(
  "to"           => $argv[2],
  "subject"      => $yhtiorow['nimi'] . " - " .t("Uudet tuotteet") . " {$datetime_checkpoint} - {$loppuaika}",
  "ctype"        => "text",
  "body"         => t("Liitteenä uudet tuotteet").".",
  "attachements" => array(0 =>
    array(
      "filename" => $filename,
    )),
);

pupesoft_sahkoposti($parametri);

echo date("d.m.Y @ G:i:s") . ": ".t("Uudet tuotteet -aineisto valmis").".\n\n";
