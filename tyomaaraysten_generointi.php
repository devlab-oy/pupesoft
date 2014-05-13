<?php

ini_set("memory_limit", "5G");
set_time_limit(0);

$debug = true;
if (php_sapi_name() != 'cli' and !$debug) {
  die("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

require ("inc/parametrit.inc");
require_once("tilauskasittely/luo_myyntitilausotsikko.inc");
require_once("inc/laite_huolto_functions.inc");

if (trim(empty($argv[1])) and !$debug) {
  echo "Et antanut yhtiötä!\n";
  exit;
}
else {
  $yhtio = $kukarow['yhtio'];
}

if (!$debug) {
  // Parametrit
  $yhtio = pupesoft_cleanstring($argv[1]);
}

// Haetaan yhtiön tiedot
$yhtiorow = hae_yhtion_parametrit($yhtio);

// Haetaan käyttäjän tiedot
$query = "  SELECT *
      FROM kuka
      WHERE yhtio = '$yhtio'
      AND kuka = 'admin'";
$result = pupe_query($query);

if (mysql_num_rows($result) == 0) {
  die("User admin not found");
}

// Adminin oletus, mutta kuka konversio
$kukarow = mysql_fetch_assoc($result);

$request = array(
  'laitteiden_huoltosyklirivit' => $laitteiden_huoltosyklirivit,
);

$laitteiden_huoltosyklirivit = hae_laitteet_ja_niiden_huoltosyklit_joiden_huolto_lahestyy();

list($huollettavien_laitteiden_huoltosyklirivit, $laitteiden_huoltosyklirivit_joita_ei_huolleta) = paata_mitka_huollot_tehdaan($laitteiden_huoltosyklirivit);

generoi_tyomaaraykset_huoltosykleista($huollettavien_laitteiden_huoltosyklirivit, $laitteiden_huoltosyklirivit_joita_ei_huolleta);
