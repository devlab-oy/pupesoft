<?php

/*
  Tuloutetuista tuotteista aineisto
  php tuloutetut_tuotteet_cron.php yhtio test@test.com
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
$datetime_checkpoint = cron_aikaleima("TULOUTETTU_CRON");

// Otetaan mukaan vain edellisen ajon jälkeen muuttuneet
if ($datetime_checkpoint != "") {
  $rajaus = " AND tilausrivi.laskutettuaika > '$datetime_checkpoint'";
}
else {
  $rajaus = " AND tilausrivi.laskutettuaika >= date_sub(now(), interval 24 HOUR)";
}

$loppuaika = date("d.m.Y @ G:i:s");

// Haetaan data
$query = "SELECT
          tilausrivi.tuoteno,
          tilausrivi.nimitys,
          tilausrivi.kpl,
          tilausrivi.laskutettu,
          tapahtuma.laadittu,
          tapahtuma.selite,
          varastopaikat.nimitys as varastonnimi,
          tapahtuma.selite as tapahtumaselite
          FROM tilausrivi
          JOIN tapahtuma ON (
          tapahtuma.yhtio = tilausrivi.yhtio
          AND tapahtuma.rivitunnus = tilausrivi.tunnus
          AND tapahtuma.laji = 'tulo')
          JOIN varastopaikat ON (
          varastopaikat.yhtio = tilausrivi.yhtio
          AND varastopaikat.tunnus = tilausrivi.varasto)
          WHERE tilausrivi.yhtio = '$yhtio'
          AND tilausrivi.tyyppi = 'O'
          AND tilausrivi.uusiotunnus > 0
          AND tilausrivi.kpl <> 0
          AND tilausrivi.varattu = 0
          {$rajaus}
          ORDER BY tapahtuma.laadittu, tapahtuma.tuoteno";
$res = pupe_query($query);

// Tallennetaan aikaleima
cron_aikaleima("TULOUTETTU_CRON", date('Y-m-d H:i:s'));
$loppuaika = date("d.m.Y @ G:i:s");

include $pupe_root_polku."/inc/pupeExcel.inc";

$worksheet    = new pupeExcel();
$excelrivi    = 0;

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo date("d.m.Y @ G:i:s") . ": ".sprintf(t("Tuloutettuja tuotteita %s kappaletta!"), $rows)."\n";

if ($rows > 0) {
  $excelsarake = 0;
  $worksheet->writeString($excelrivi, $excelsarake, t("Tuloutetut tuotteet ajalta"));
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
  $worksheet->writeString($excelrivi, $excelsarake, t("Kappale"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Tulouttaja"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Tuloutettuaika"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Varaston tunnus"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Lisätieto"));
  $excelrivi++;
}

while ($row = mysql_fetch_assoc($res)) {

  $excelsarake = 0;
  $worksheet->writeString($excelrivi, $excelsarake, $row["tuoteno"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["nimitys"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["kpl"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["laskutettu"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["laadittu"]);
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, $row["varastonnimi"]);
  $excelsarake++;

  // siivotaan rivinvaihdot pois
  //$row["tapahtumaselite"] = str_replace("<br>", " ", $row["tapahtumaselite"]);

  // siivotaan selitteestä kaikki hintoihin liittyvä veke,
  // yhtiön kielellä ja käyttäjän kielellä
  $_jl_text = t("Jälkilaskennan ostohinta");
  $_ot_text = t("Ostohinta");
  $_jl_text2 = t("Jälkilaskennan ostohinta", $yhtiorow['kieli']);
  $_ot_text2 = t("Ostohinta", $yhtiorow['kieli']);

  $selite = preg_replace("/({$_jl_text}: [0-9\.]*|, {$_ot_text}:[0-9\. ]*)/", "", $row["tapahtumaselite"]);
  $selite = preg_replace("/({$_jl_text2}: [0-9\.]*|, {$_ot_text2}:[0-9\. ]*)/", "", $selite);
  $selite = preg_replace("/(\([0-9\.\-\> ]*\)|\[[0-9\.\-]*\])/", "", $selite);
  $selite = preg_replace("/(,<br>|<br><br>)/", "", $selite);
  $selite = trim($selite);

  $worksheet->writeString($excelrivi, $excelsarake, $selite);
  $excelsarake++;
  $excelrivi++;
}

$excelnimi = $worksheet->close();
$niminimi = date("Ymd-Gis")."-".t("tuloutetut_tuotteet").".xlsx";
rename("/tmp/".$excelnimi, "/tmp/".$niminimi);
$filename = "/tmp/".$niminimi;

$parametri = array(
  "to"           => $argv[2],
  "subject"      => $yhtiorow['nimi'] . " - " .t("Tuloutetut tuotteet") . " {$datetime_checkpoint} - {$loppuaika}",
  "ctype"        => "text",
  "body"         => t("Liitteenä tuloutetut tuotteet").".",
  "attachements" => array(0 =>
    array(
      "filename" => $filename,
    )),
);

pupesoft_sahkoposti($parametri);

echo date("d.m.Y @ G:i:s") . ": ".t("Tuloutetut tuotteet -aineisto valmis").".\n\n";
