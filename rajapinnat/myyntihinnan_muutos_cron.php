<?php

/*
  Myyntihinnan muutoksista raportti s�hk�postitse
  php myyntihinnan_muutos_cron.php yhtio test@test.com
*/

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhti� on annettava!!");
}

if (!isset($argv[2]) or $argv[2] == '') {
  die("S�hk�postiosoite on annettava!!");
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

$pupe_root_polku = dirname(dirname(__FILE__));
require "{$pupe_root_polku}/inc/connect.inc";
require "{$pupe_root_polku}/inc/functions.inc";

// Logitetaan ajo
cron_log();

// Yhti�
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

if ($yhtiorow['myyntihinnan_muutoksien_logitus'] != 'x') {
  die ("T�t� scripti� voi ajaa vain jos myyntihinnan_muutoksien_logitus on p��ll�");
}

// Haetaan aika jolloin t�m� skripti on viimeksi ajettu
$datetime_checkpoint = cron_aikaleima("MYHIN_CRON");

// Otetaan mukaan vain edellisen ajon j�lkeen muuttuneet
if ($datetime_checkpoint != "") {
  $rajaus = " AND changelog.luontiaika > '$datetime_checkpoint'";
}
else {
  $rajaus = " AND changelog.luontiaika >= date_sub(now(), interval 24 HOUR)";
}

$loppuaika = date("d.m.Y @ G:i:s");

// Haetaan data
$query = "SELECT changelog.key, max(changelog.luontiaika) AS luontiaika, tuote.nimitys, tuote.tuoteno,
          SUBSTRING_INDEX(GROUP_CONCAT(changelog.old_value_str ORDER BY changelog.luontiaika), ',', 1 ) as min_old_value_str,
          SUBSTRING_INDEX(GROUP_CONCAT(changelog.new_value_str ORDER BY changelog.luontiaika DESC), ',', 1 ) as max_new_value_str
          FROM changelog
          JOIN tuote ON (changelog.yhtio = tuote.yhtio and changelog.key = tuote.tunnus)
          WHERE changelog.yhtio = '$yhtio'
          AND changelog.table = 'tuote'
          AND changelog.field = 'myyntihinta'
          {$rajaus}
          GROUP BY changelog.key
          HAVING min_old_value_str <> max_new_value_str
          ORDER BY tuote.tuoteno, changelog.id";
$res = pupe_query($query);


// Tallennetaan aikaleima
cron_aikaleima("MYHIN_CRON", date('Y-m-d H:i:s'));
$loppuaika = cron_aikaleima("MYHIN_CRON");

include $pupe_root_polku."/inc/pupeExcel.inc";

$worksheet    = new pupeExcel();
$excelrivi    = 0;

// Kerrotaan montako rivi� k�sitell��n
$rows = mysql_num_rows($res);

echo date("d.m.Y @ G:i:s") . ": ".sprintf(t("Tuotteen myyntihinnan muutosrivej� %s kappaletta!"), $rows)."\n";

if ($rows > 0) {
  $excelsarake = 0;
  $worksheet->writeString($excelrivi, $excelsarake, t("Tuotteen myyntihinnan muutokset ajalta"));
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
  $worksheet->writeString($excelrivi, $excelsarake, t("Vanha myyntihinta"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Uusi myyntihinta"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Muutosaika"));
  $excelrivi++;
}

while ($row = mysql_fetch_assoc($res)) {

  $excelsarake = 0;
  $worksheet->writeString($excelrivi, $excelsarake, $row["tuoteno"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["nimitys"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["min_old_value_str"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["max_new_value_str"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["luontiaika"]);
  $excelsarake++;
  $excelrivi++;
}

$excelnimi = $worksheet->close();
$niminimi = date("Ymd-Gis")."-".t("tuotteen_myyntihinnan_muutokset").".xlsx";
rename("/tmp/".$excelnimi, "/tmp/".$niminimi);
$filename = "/tmp/".$niminimi;

$parametri = array(
  "to"           => $argv[2],
  "subject"      => $yhtiorow['nimi'] . " - " .t("Tuotteen myyntihinnan muutokset") . " {$datetime_checkpoint} - {$loppuaika}",
  "ctype"        => "text",
  "body"         => t("Liitteen� tuotteen myyntihintojen muutokset").".",
  "attachements" => array(0 =>
    array(
      "filename" => $filename,
    )),
);

$boob = pupesoft_sahkoposti($parametri);

// Poistetaan lasku hakemistosta jos s�hk�postin l�hetys onnistui
if ($boob) {
  unlink($filename);
}

echo date("d.m.Y @ G:i:s") . ": ".t("Tuotteen myyntihinnan muutokset -aineisto valmis").".\n\n";
