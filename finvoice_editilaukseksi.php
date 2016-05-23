<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut yhti�t�!\n");
}

if (trim($argv[2]) == '') {
  die ("Et antanut luettavien tiedostojen polkua!\n");
}

// lis�t��n includepathiin pupe-root
ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/edi.php";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t�st� skriptist� kerrallaan
pupesoft_flock();

$yhtio    = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtio);

$path = trim($argv[2]);
$path = substr($path, -1) != '/' ? $path . '/' : $path;

$files = glob("{$path}/*.xml");

foreach ($files as $file) {
  $file = file_get_contents($file);
  $xml = simplexml_load_string($file);

  require "inc/verkkolasku-in-finvoice.inc";

  $ovt_tunnus = $laskuttajan_ovt;
  $verkkokauppa_asiakasnro = $yhtio;
  $pupesoft_tilaustyyppi = "E";

  $order = array(
    "grand_total"  => $laskun_summa_eur,
    "increment_id" => $laskun_numero
  );

  Edi::create($order, "finvoice");
}
