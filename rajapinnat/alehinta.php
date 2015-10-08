<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

$yhtio       = trim($argv[1]);
$kohdetyyppi = trim($argv[2]);
$kohde       = trim($argv[3]);
$tuote       = trim($argv[4]);

if ($yhtio == '') {
  die ("Et antanut yhtiötä!\n");
}
elseif ($kohdetyyppi == '') {
  die ("Et antanut kohdetyyppiä!\n");
}
elseif ($kohde == '') {
  die ("Et antanut kohdetta!\n");
}
elseif ($tuote == '') {
  die ("Et antanut tuotetta!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "../inc/connect.inc";
require "../inc/functions.inc";

$yhtio       = mysql_real_escape_string($yhtio);
$kohde       = mysql_real_escape_string($kohde);
$kohdetyyppi = mysql_real_escape_string($kohdetyyppi);
$tuote       = mysql_real_escape_string($tuote);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

switch ($kohdetyyppi) {
case "asiakas":
  $alehinta = alehinta_asiakas($kohde, $tuote);
  break;
default:
  die ("Kohdetyyppi on virheellinen");
}

echo json_encode($alehinta);

function alehinta_asiakas($asiakas, $tuote) {
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

  list($hinta, , , ,) = alehinta($laskurow, $tuote, $kpl, $netto, $hinta, $ale);

  return array(
    "hinta" => $hinta,
  );
}
