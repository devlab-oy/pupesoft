<?php

ini_set("memory_limit", "5G");
set_time_limit(0);

$debug = true;
if (php_sapi_name() == 'cli') {

  $pupe_root_polku = dirname(__FILE__);
  ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . $pupe_root_polku . PATH_SEPARATOR . "/usr/share/pear");
  error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
  ini_set("display_errors", 0);

  require("inc/connect.inc");
  require("inc/functions.inc");

  if (trim(empty($argv[1]))) {
    echo "Et antanut yhtiötä!\n";
    exit;
  }

  $yhtio = pupesoft_cleanstring($argv[1]);

  $kukarow = hae_kukarow('admin', $yhtio);

  $debug = false;
}
else {
  require ("inc/parametrit.inc");

  $yhtio = $kukarow['yhtio'];
}

require_once("tilauskasittely/luo_myyntitilausotsikko.inc");
require_once("inc/laite_huolto_functions.inc");
require_once("inc/ProgressBar.class.php");

// Haetaan yhtiön tiedot
$yhtiorow = hae_yhtion_parametrit($yhtio);

$laitteiden_huoltosyklirivit = hae_laitteet_ja_niiden_huoltosyklit_joiden_huolto_lahestyy();

list($huollettavien_laitteiden_huoltosyklirivit, $laitteiden_huoltosyklirivit_joita_ei_huolleta) = paata_mitka_huollot_tehdaan($laitteiden_huoltosyklirivit);

$tyomaarays_kpl = generoi_tyomaaraykset_huoltosykleista($huollettavien_laitteiden_huoltosyklirivit, $laitteiden_huoltosyklirivit_joita_ei_huolleta);

if (php_sapi_name() == 'cli') {
  echo "Työmääräyksiä tuli {$tyomaarays_kpl}\n";
}
