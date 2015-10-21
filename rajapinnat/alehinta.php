<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!\n");
}

$yhtio       = trim($argv[1]);
$kuka        = trim($argv[2]);
$kohdetyyppi = trim($argv[3]);
$kohde       = trim($argv[4]);
$tuote       = trim($argv[5]);

if ($yhtio == '') {
  die ("Et antanut yhti�t�!\n");
}
elseif ($kuka == '') {
  die ("Et antanut k�ytt�j��!\n");
}
elseif ($kohdetyyppi == '') {
  die ("Et antanut kohdetyyppi�!\n");
}
elseif ($kohde == '') {
  die ("Et antanut kohdetta!\n");
}
elseif ($tuote == '') {
  die ("Et antanut tuotetta!\n");
}

// lis�t��n includepathiin pupe-root
ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "../inc/connect.inc";
require "../inc/functions.inc";

$yhtio       = mysql_real_escape_string($yhtio);
$kuka        = mysql_real_escape_string($kuka);
$kohde       = mysql_real_escape_string($kohde);
$kohdetyyppi = mysql_real_escape_string($kohdetyyppi);
$tuote       = mysql_real_escape_string($tuote);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow($kuka, $yhtiorow['yhtio']);

switch ($kohdetyyppi) {
case "asiakas":
  $alehinta = alehinta_asiakas($kohde, $tuote);
  break;
case "asiakasryhma":
  $alehinta = alehinta_asiakasryhma($kohde, $tuote);
  break;
default:
  die ("Kohdetyyppi on virheellinen");
}

echo json_encode($alehinta);

function alehinta_asiakas($asiakas, $tuote) {
  global $yhtiorow;

  $tuote = "SELECT *
            FROM tuote
            WHERE tunnus = {$tuote}";

  $tuote = pupe_query($tuote);
  $tuote = mysql_fetch_assoc($tuote);

  $asiakas = "SELECT *
              FROM asiakas
              WHERE tunnus = {$asiakas}";

  $asiakas = pupe_query($asiakas);
  $asiakas = mysql_fetch_assoc($asiakas);

  $laskurow = array(
    "liitostunnus" => $asiakas["tunnus"],
    "ytunnus"      => $asiakas["ytunnus"],
  );

  $kpl   = 1;
  $netto = "";
  $hinta = "";
  $ale   = array();

  list($hinta, , $ale, ,) = alehinta($laskurow, $tuote, $kpl, $netto, $hinta, $ale);

  $kokonaisale = 1;
  $maara       = $yhtiorow['myynnin_alekentat'];

  for ($alepostfix = 1; $alepostfix <= $maara; $alepostfix++) {
    $kokonaisale *= (1 - $ale["ale{$alepostfix}"] / 100);
  }

  $hinta = round(($hinta * $kokonaisale), $yhtiorow["hintapyoristys"]);

  return array(
    "hinta" => $hinta,
  );
}

function alehinta_asiakasryhma($asiakasryhma, $tuote) {
  global $yhtiorow;

  $tuote = "SELECT *
            FROM tuote
            WHERE tunnus = {$tuote}";

  $tuote = pupe_query($tuote);
  $tuote = mysql_fetch_assoc($tuote);

  $laskurow = array();
  $kpl      = 1;
  $netto    = "";
  $hinta    = "";
  $ale      = array();

  list($hinta, , $ale, ,) = alehinta($laskurow, $tuote, $kpl, $netto, $hinta, $ale, '', '', '', $asiakasryhma);

  $kokonaisale = 1;
  $maara       = $yhtiorow['myynnin_alekentat'];

  for ($alepostfix = 1; $alepostfix <= $maara; $alepostfix++) {
    $kokonaisale *= (1 - $ale["ale{$alepostfix}"] / 100);
  }

  $hinta = round(($hinta * $kokonaisale), $yhtiorow["hintapyoristys"]);

  return array(
    "hinta" => $hinta,
  );
}
