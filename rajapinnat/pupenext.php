<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!\n");
}

$yhtio    = trim($argv[1]);
$kuka     = trim($argv[2]);
$function = trim($argv[3]);
$params   = trim($argv[4]);

if ($yhtio == '') {
  die ("Et antanut yhti�t�!\n");
}
elseif ($kuka == '') {
  die ("Et antanut k�ytt�j��!\n");
}
elseif ($function == '') {
  die ("Et antanut kutsuttavan funktion nime�!\n");
}

// lis�t��n includepathiin pupe-root
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
