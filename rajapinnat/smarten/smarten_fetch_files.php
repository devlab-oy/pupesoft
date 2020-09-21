<?php

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut yhtiötä!\n");
}

if (trim($argv[2]) == '') {
  die ("Et antanut polkua!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
ini_set("display_errors", 1);

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$path = rtrim($argv[2], '/').'/';
$handle = opendir($path);

if ($handle === false) {
  pupesoft_log('smarten_fetch_files', "Polku virheellinen {$path}!");
  exit;
}

$ftphost = $smarten['host'];
$ftpuser = $smarten['user'];
$ftppass = $smarten['pass'];
$ftpport = $smarten['port'];
$ftpskey = $smarten['skey'];

$ftppath = $smarten['path_from'];
$ftpdest = $path;
$ftpsdel = TRUE;

require 'sftp-get.php';

pupesoft_log('smarten_fetch_files', "Uusimmat tiedostot haettu.");