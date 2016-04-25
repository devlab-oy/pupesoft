<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

$yhtio    = trim($argv[1]);
$kuka     = trim($argv[2]);
$function = trim($argv[3]);
$params   = trim($argv[4]);

if ($yhtio == '') {
  die ("Et antanut yhtiötä!\n");
}
elseif ($kuka == '') {
  die ("Et antanut käyttäjää!\n");
}
elseif ($function == '') {
  die ("Et antanut kutsuttavan funktion nimeä!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

$yhtio    = mysql_real_escape_string($yhtio);
$kuka     = mysql_real_escape_string($kuka);
$function = mysql_real_escape_string($function);
$params   = json_decode($params);

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
  global $yhtiorow, $kukarow;

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
    "liitostunnus"      => $asiakas["tunnus"],
    "maa"               => '',
    "valkoodi"          => '',
    "ytunnus"           => $asiakas["ytunnus"],
    'yhtio_toimipaikka' => $asiakas['toimipaikka']
  );

  $kpl   = 1;
  $netto = "";
  $hinta = "";
  $ale   = array();

  list($hinta, , $ale, , ) = alehinta($laskurow, $tuote, $kpl, $netto, $hinta, $ale);

  preg_match("/XXXHINTAPERUSTE:([0-9]*)/", $GLOBALS["ale_peruste"], $hinta_peruste);
  $hinta_peruste = $hinta_peruste[1];

  preg_match("/XXXALEPERUSTE:([0-9]*)/", $GLOBALS["ale_peruste"], $ale_peruste);
  $ale_peruste = $ale_peruste[1];

  $kokonaisale = 1;
  $maara       = $yhtiorow['myynnin_alekentat'];

  for ($alepostfix = 1; $alepostfix <= $maara; $alepostfix++) {
    $kokonaisale *= (1 - $ale["ale{$alepostfix}"] / 100);
  }

  $hinta = round(($hinta * $kokonaisale), $yhtiorow["hintapyoristys"]);

  return array(
    "hinta"         => $hinta,
    "hinta_peruste" => $hinta_peruste,
    "ale_peruste"   => $ale_peruste,
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

  list($hinta, , $ale, , ) = alehinta($laskurow, $tuote, $kpl, $netto, $hinta, $ale, '', '', '', $asiakasryhma);

  preg_match("/XXXHINTAPERUSTE:([0-9]*)/", $GLOBALS["ale_peruste"], $hinta_peruste);
  $hinta_peruste = $hinta_peruste[1];

  preg_match("/XXXALEPERUSTE:([0-9]*)/", $GLOBALS["ale_peruste"], $ale_peruste);
  $ale_peruste = $ale_peruste[1];

  $kokonaisale = 1;
  $maara       = $yhtiorow['myynnin_alekentat'];

  for ($alepostfix = 1; $alepostfix <= $maara; $alepostfix++) {
    $kokonaisale *= (1 - $ale["ale{$alepostfix}"] / 100);
  }

  $hinta = round(($hinta * $kokonaisale), $yhtiorow["hintapyoristys"]);

  return array(
    "hinta"         => $hinta,
    "hinta_peruste" => $hinta_peruste,
    "ale_peruste"   => $ale_peruste,
  );
}
