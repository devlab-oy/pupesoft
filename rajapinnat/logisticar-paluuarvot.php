<?php

// Päivitetään Logisticar paluuarvot Pupesoftiin.
// Tarvitaan asetukset salasanat.php -tiedostoon:
//
// $ftpget_dest = array('logisticar_paluuarvot' => '/tmp/paluuarvot');
// $ftpget_host = array('logisticar_paluuarvot' => '10.0.1.2');
// $ftpget_user = array('logisticar_paluuarvot' => 'foo');
// $ftpget_pass = array('logisticar_paluuarvot' => 'bar');
// $ftpget_path = array('logisticar_paluuarvot' => 'paluuarvot');

date_default_timezone_set('Europe/Helsinki');
ini_set("max_execution_time", 0); // unlimited execution time

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä\n");
}

if (empty($argv[1])) {
  die("Et antanut yhtiötä\n");
}

$pupe_root_polku = dirname(dirname(__FILE__));

require "{$pupe_root_polku}/inc/connect.inc";
require "{$pupe_root_polku}/inc/functions.inc";

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string($argv[1]);
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if ($kukarow === null) {
  die("Admin käyttäjää ei löydy\n");
}

// Yliajetaan argv[1], jotta ftp-get toimii
$key = 'logisticar_paluuarvot';
$argv[1] = $key;

if (empty($ftpget_dest[$key]) or empty($ftpget_host[$key]) or empty($ftpget_user[$key]) or empty($ftpget_pass[$key]) or empty($ftpget_path[$key])) {
  die("logisticar_paluuarvot ftp-get tiedot ei ole asetettu\n");
}

// Katostaan, että dirikat löytyy
$work_directory = $ftpget_dest[$key];
$ok_directory = "{$work_directory}/ok";
$handle = opendir($work_directory);

if ($handle === false or !is_writeable($work_directory)) {
  die("{$work_directory} hakemisto ei löydy\n");
}

if (!is_dir($ok_directory) or !is_writeable($ok_directory)) {
  die("{$ok_directory} hakemisto ei löydy\n");
}

pupesoft_log("logisticar_paluuarvo", "Haetaan tiedostoja");

// Haetaan kaikki tiedostot
require 'ftp-get.php';

// Loopataan hakemisto, tuliko tiedostoja
while (($file = readdir($handle)) !== false) {
  $filepath = "{$work_directory}/{$file}";
  $ok_filepath = "{$ok_directory}/{$file}";

  // Ei käsitellä, jos ei ole tiedosto
  if (!is_file($filepath)) {
    continue;
  }

  $fh = fopen($filepath, "r");

  // Ei käsitellä, jos tiedoston avaus epäonnistui
  if ($fh === false) {
    continue;
  }

  pupesoft_log("logisticar_paluuarvo", "Käsitellään {$file}");

  // Loopataan rivit läpi
  while (($rivi = fgets($fh)) !== false) {
    logisticar_kasittele_paluuarvo_rivi($rivi);
  }

  // Siirretään tiedosto ok -hakemistoon
  rename($filepath, $ok_filepath);
}

function logisticar_kasittele_paluuarvo_rivi($rivi) {
  global $kukarow, $yhtiorow;

  $rivi_array      = explode("\t", $rivi);
  $tuoteno         = pupesoft_cleanstring($rivi_array[0]);
  $varasto         = pupesoft_cleannumber($rivi_array[1]);
  $varmuus_varasto = pupesoft_cleannumber($rivi_array[2]);
  $osto_era        = pupesoft_cleannumber($rivi_array[3]);
  $halytysraja     = pupesoft_cleannumber($rivi_array[4]);
  $tahtituote      = pupesoft_cleanstring($rivi_array[5]);
  $status          = pupesoft_cleanstring($rivi_array[6]);

  pupesoft_log("logisticar_paluuarvo", "Päivitetään tuote {$tuoteno}: halytysraja {$halytysraja}, status {$status}, tahtituote {$tahtituote}, varmuus_varasto {$varmuus_varasto}, osto_era {$osto_era}");

  // päivitetään tiedot tuotteelle
  $query = "UPDATE tuote SET
            halytysraja = {$halytysraja},
            status = '{$status}',
            tahtituote = '{$tahtituote}',
            varmuus_varasto = {$varmuus_varasto}
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$tuoteno}'";
  pupe_query($query);

  // pävitetään tuotteen toimittajien tiedot
  $query = "UPDATE tuotteen_toimittajat SET
            osto_era = $osto_era
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$tuoteno}'";
  pupe_query($query);
}

pupesoft_log("logisticar_paluuarvo", "Käsittely valmis");
