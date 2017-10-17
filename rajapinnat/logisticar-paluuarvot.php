<?php

// Päivitetään Logisticar paluuarvot Pupesoftiin.
// Tarvitaan asetukset salasanat.php -tiedostoon:
//
// $logisticar = array(
//   'yhtiokoodi' => array(
//     'paluuarvot' => array(
//       'dest_dir' => '/tmp/paluuarvot',
//       'ftp_host' => '10.0.1.2',
//       'ftp_pass' => 'foo',
//       'ftp_user' => 'bar',
//       'src_dir'  => 'paluuarvot',
//     ),
//   ),
// );

$pupe_root_polku = dirname(dirname(__FILE__));

date_default_timezone_set('Europe/Helsinki');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$pupe_root_polku);
ini_set('max_execution_time', 0); // unlimited execution time
ini_set('memory_limit', '5G');

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä\n");
}

if (empty($argv[1])) {
  die("Et antanut yhtiötä\n");
}

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

if (empty($logisticar[$yhtio]['paluuarvot']['dest_dir'])) die("dest_dir ei asetettu\n");
if (empty($logisticar[$yhtio]['paluuarvot']['ftp_host'])) die("ftp_host ei asetettu\n");
if (empty($logisticar[$yhtio]['paluuarvot']['ftp_pass'])) die("ftp_pass ei asetettu\n");
if (empty($logisticar[$yhtio]['paluuarvot']['ftp_user'])) die("ftp_user ei asetettu\n");
if (empty($logisticar[$yhtio]['paluuarvot']['src_dir']))  die("src_dir ei asetettu\n");

// Yliajetaan argv[1], ja asetataan tarvittavat muuttujat, jotta ftp-get toimii
$key = 'logisticar_paluuarvot';
$argv[1] = $key;
$ftpget_dest = array($key => $logisticar[$yhtio]['paluuarvot']['dest_dir']);
$ftpget_host = array($key => $logisticar[$yhtio]['paluuarvot']['ftp_host']);
$ftpget_user = array($key => $logisticar[$yhtio]['paluuarvot']['ftp_user']);
$ftpget_pass = array($key => $logisticar[$yhtio]['paluuarvot']['ftp_pass']);
$ftpget_path = array($key => $logisticar[$yhtio]['paluuarvot']['src_dir']);

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

if (!empty($syy)) {
  pupesoft_log("logisticar_paluuarvo", $syy);
}

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
