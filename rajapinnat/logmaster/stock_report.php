<?php

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut l‰hett‰v‰‰ yhtiˆt‰!\n");
}

if (trim($argv[2]) == '') {
  die ("Et antanut luettavien tiedostojen polkua!\n");
}

if (trim($argv[3]) == '') {
  die ("Et antanut s‰hkˆpostiosoitetta!\n");
}

// lis‰t‰‰n includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
ini_set("display_errors", 1);
ini_set("memory_limit", "2G");

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  exit("VIRHE: Admin k‰ytt‰j‰ ei lˆydy!\n");
}

$path = trim($argv[2]);
$error_email = trim($argv[3]);
$email_array = array();

$path = rtrim($path, '/').'/';
$handle = opendir($path);

if ($handle === false) {
  exit;
}

while (false !== ($file = readdir($handle))) {
  $full_filepath = $path.$file;
  $message_type = logmaster_message_type($full_filepath);

  if ($message_type != 'StockReport') {
    continue;
  }

  $xml = simplexml_load_file($full_filepath);

  pupesoft_log('logmaster_stock_report', "K‰sitell‰‰n sanoma {$file}");

  // tuki vain yhdelle Logmaster-varastolle
  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio              = '{$kukarow['yhtio']}'
            AND ulkoinen_jarjestelma = 'P'
            LIMIT 1";
  $varastores = pupe_query($query);
  $varastorow = mysql_fetch_assoc($varastores);

  $luontiaika = $xml->InvCounting->TransDate;

  unset($xml->InvCounting->TransDate);

  $saldoeroja = array();

  foreach ($xml->InvCounting->Line as $line) {
    $item_number               = $line->ItemNumber;
    $kpl                       = (float) $line->Quantity;
    $logmaster_itemnumberfield = logmaster_field('ItemNumber');

    $query = "SELECT tuoteno, nimitys
              FROM tuote
              WHERE yhtio  = '{$kukarow['yhtio']}'
              AND {$logmaster_itemnumberfield} = '{$item_number}'";
    $tuoteres = pupe_query($query);
    $tuoterow = mysql_fetch_assoc($tuoteres);

    list($saldo, $hyllyssa, $myytavissa, $devnull) = saldo_myytavissa($tuoterow["tuoteno"], "KAIKKI", $varastorow['tunnus']);

    // Etuk‰teen maksetut tilaukset, jotka ovat ker‰‰m‰tt‰ mutta tilaus jo laskutettu
    // Lasketaan ne mukaan Pupen hyllyss‰ m‰‰r‰‰n, koska saldo_myytavissa ei huomioi niit‰
    $query = "SELECT ifnull(sum(tilausrivi.kpl), 0) AS keraamatta
              FROM tilausrivi
              INNER JOIN lasku on (lasku.yhtio = tilausrivi.yhtio
                AND lasku.tunnus          = tilausrivi.otunnus
                AND lasku.mapvm          != '0000-00-00'
                AND lasku.chn             = '999')
              WHERE tilausrivi.yhtio      = '{$kukarow['yhtio']}'
              AND tilausrivi.tyyppi       = 'L'
              AND tilausrivi.var         != 'P'
              AND tilausrivi.keratty      = ''
              AND tilausrivi.kerattyaika  = '0000-00-00 00:00:00'
              AND tilausrivi.tuoteno      = '{$tuoterow['tuoteno']}'";
    $ker_result = pupe_query($query);
    $ker_rivi = mysql_fetch_assoc($ker_result);

    $hyllyssa += $ker_rivi['keraamatta'];

    // Vertailukonversio
    $a = (int) $kpl * 10000;
    $b = (int) $hyllyssa * 10000;

    if ($a != $b) {
      $saldoeroja[] = array(
        "item"      => $item_number,
        "logmaster" => $kpl,
        "nimitys"   => $tuoterow['nimitys'],
        "pupe"      => $hyllyssa,
        "tuoteno"   => $tuoterow['tuoteno'],
      );
    }
  }

  if (count($saldoeroja) > 0) {

    $email_array[] = t("Seuraavien tuotteiden saldovertailuissa on havaittu eroja").":";
    $email_array[] = t("Logmaster-tuoteno").";".t("Tuoteno").";".t("Nimitys").";".t("Logmaster-kpl").";".t("Pupesoft-kpl");

    foreach ($saldoeroja as $ero) {
      $email_array[] = "{$ero['item']};{$ero['tuoteno']};{$ero['nimitys']};{$ero['logmaster']};{$ero['pupe']}";
    }

    pupesoft_log('logmaster_stock_report', "Sanoman {$file} saldovertailussa oli eroja.");
  }
  else {
    pupesoft_log('logmaster_stock_report', "Sanoman {$file} saldovertailussa ei ollut eroja.");
  }

  $params = array(
    'email' => $error_email,
    'email_array' => $email_array,
    'log_name' => 'logmaster_stock_report',
  );

  logmaster_send_email($params);

  // siirret‰‰n tiedosto done-kansioon
  rename($full_filepath, $path.'done/'.$file);

  pupesoft_log('logmaster_stock_report', "Sanoman {$file} saldovertailu k‰sitelty");
}

closedir($handle);
