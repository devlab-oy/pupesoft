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
require "rajapinnat/smarten/smarten-functions.php";

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
  list($message_type, $message_subtype) = smarten_message_type($full_filepath);

  // Tyypin tulee olla INVRPT ja subtyypin tyhj‰
  if ($message_type != 'INVRPT' or $message_subtype != "TRANSFER") {
    continue;
  }

  $xml = simplexml_load_file($full_filepath);

  pupesoft_log('smarten_stock_transfer_report', "K‰sitell‰‰n sanoma {$file}");

  $luontiaika = $xml->Document->DocumentInfo->DateInfo->InventoryDate;

  $lahteet = array();
  $kohteet = array();

  foreach ($xml->Document->DocumentItem->ItemEntry as $line) {

    $item_number = (string) $line->SellerItemCode;
    $lineid = (string) $line->LineItemNum;
    $sourcelineid = (string) $line->RefInfo->SourceDocument->SourceDocumentLineItemNum;
    $maara = (float) $line->AmountActual;
    $varastokoodi = (string) $line->ItemReserve->Location->WarehouseCode;

    if (!empty($sourcelineid)) {
      $lahteet[$lineid] = array(
        'sourcelineid' => $sourcelineid,
        'tuoteno' => $item_number,
        'maara' => $maara,
        'varastokoodi' => $varastokoodi,
      );
    }
    else {
      $kohteet[$lineid] = array(
        'tuoteno' => $item_number,
        'maara' => $maara,
        'varastokoodi' => $varastokoodi,
      );
    }
  }

  foreach ($lahteet as $lahde) {
    $kohde = $kohteet[$lahde['sourcelineid']];

    // Tuote
    $query = "SELECT tuoteno, nimitys
              FROM tuote
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$lahde['tuoteno']}'";
    $tuoteres = pupe_query($query);

    if (mysql_num_rows($tuoteres) == 1) {
      $tuoterow = mysql_fetch_assoc($tuoteres);
    }
    else {
      pupesoft_log('smarten_stock_transfer_report', "VIRHE: Tuntematon tuote {$lahde['tuoteno']}");
      continue;
    }

    // L‰hdepaikka
    $query = "SELECT varastopaikat.*, tuotepaikat.tunnus tuotepaikkatunnus
              FROM varastopaikat
              LEFT JOIN tuotepaikat ON (
                varastopaikat.yhtio = tuotepaikat.yhtio AND
                varastopaikat.tunnus = tuotepaikat.varasto AND
                tuotepaikat.tuoteno = '{$tuoterow['tuoteno']}'
              )
              WHERE varastopaikat.yhtio = '{$kukarow['yhtio']}'
              AND varastopaikat.ulkoinen_jarjestelma = 'S'
              AND varastopaikat.ulkoisen_jarjestelman_tunnus = '{$lahde['varastokoodi']}'";
    $lahdevarastores = pupe_query($query);

    if (mysql_num_rows($lahdevarastores) == 1) {
      $lahdepaikkarow = mysql_fetch_assoc($lahdevarastores);

      if (empty($lahdepaikkarow['tuotepaikkatunnus'])) {
        // Luodaan l‰hdepaikka
        $query = "INSERT INTO tuotepaikat set
                  yhtio     = '$kukarow[yhtio]',
                  tuoteno   = '$tuoterow[tuoteno]',
                  hyllyalue = '$lahdepaikkarow[alkuhyllyalue]',
                  hyllynro  = '$lahdepaikkarow[alkuhyllynro]',
                  hyllytaso = '0',
                  hyllyvali = '0'";
        pupe_query($query);

        $lahdepaikkarow['tuotepaikkatunnus'] = mysql_insert_id($GLOBALS["masterlink"]);
      }
    }
    else {
      pupesoft_log('smarten_stock_transfer_report', "VIRHE: Tuntematon l‰hdevarasto {$lahde['varastokoodi']}");
      continue;
    }

    // Kohdepaikka
    $query = "SELECT varastopaikat.*, tuotepaikat.tunnus tuotepaikkatunnus
              FROM varastopaikat
              LEFT JOIN tuotepaikat ON (
                varastopaikat.yhtio = tuotepaikat.yhtio AND
                varastopaikat.tunnus = tuotepaikat.varasto AND
                tuotepaikat.tuoteno = '{$tuoterow['tuoteno']}'
              )
              WHERE varastopaikat.yhtio = '{$kukarow['yhtio']}'
              AND varastopaikat.ulkoinen_jarjestelma = 'S'
              AND varastopaikat.ulkoisen_jarjestelman_tunnus = '{$kohde['varastokoodi']}'";
    $kohdevarastores = pupe_query($query);
    echo "$query\n";

    if (mysql_num_rows($kohdevarastores) == 1) {
      $kohdepaikkarow = mysql_fetch_assoc($kohdevarastores);

      if (empty($kohdepaikkarow['tuotepaikkatunnus'])) {
        // Luodaan kohdepaikka
        $query = "INSERT INTO tuotepaikat set
                  yhtio     = '$kukarow[yhtio]',
                  tuoteno   = '$tuoterow[tuoteno]',
                  hyllyalue = '$kohdepaikkarow[alkuhyllyalue]',
                  hyllynro  = '$kohdepaikkarow[alkuhyllynro]',
                  hyllytaso = '0',
                  hyllyvali = '0'";
        pupe_query($query);

        $kohdepaikkarow['tuotepaikkatunnus'] = mysql_insert_id($GLOBALS["masterlink"]);
      }
    }
    else {
      pupesoft_log('smarten_stock_transfer_report', "VIRHE: Tuntematon lkohdevarasto {$kohde['varastokoodi']}");
      continue;
    }

    // Tarvitsemme
    // $asaldo  = siirrett‰v‰ m‰‰r‰
    // $mista   = tuotepaikan tunnus josta otetaan
    // $minne   = tuotepaikan tunnus jonne siirret‰‰n
    // $tuoteno = tuotenumero jota siirret‰‰n

    $tee     = "N";
    $toim    = "";
    $kutsuja = "vastaanota.php";
    $asaldo  = $kohde["maara"];
    $mista   = $lahdepaikkarow['tuotepaikkatunnus'];
    $minne   = $kohdepaikkarow['tuotepaikkatunnus'];
    $tuoteno = $tuoterow['tuoteno'];

    require "muuvarastopaikka.php";
  }

  // siirret‰‰n tiedosto done-kansioon
  rename($full_filepath, $path.'done/'.$file);
  pupesoft_log('smarten_stock_transfer_report', "Sanoman {$file} varasosiirrot k‰sitelty");
}

closedir($handle);
