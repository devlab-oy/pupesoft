<?php

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {

  $_cli = false;

  if (strpos($_SERVER['SCRIPT_NAME'], "inbound_delivery.php") !== false) {
    require "../../inc/parametrit.inc";
  }
}
else {

  $_cli = true;

  date_default_timezone_set('Europe/Helsinki');

  if (trim($argv[1]) == '') {
    die ("Et antanut l‰hett‰v‰‰ yhtiˆt‰!\n");
  }

  if (trim($argv[2]) == '') {
    die ("Et antanut saapumisnumeroa!\n");
  }

  // lis‰t‰‰n includepathiin pupe-root
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));
  error_reporting(E_ALL);

  // otetaan tietokanta connect ja funktiot
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  // Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
  pupesoft_flock();

  $yhtio = mysql_escape_string(trim($argv[1]));
  $yhtiorow = hae_yhtion_parametrit($yhtio);
  $kukarow = hae_kukarow('admin', $yhtio);

  if (!isset($kukarow)) {
    exit("VIRHE: Admin k‰ytt‰j‰ ei lˆydy!\n");
  }

  $pupe_root_polku = dirname(__FILE__);

  $saapumisnro = $argv[2];

  if (!empty($argv[3])) {
    $ordercode = $argv[3];
  }
}

if (!in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'S'))) {
  die ("Saapumisen l‰hett‰minen estetty yhtiˆtasolla!\n");
}

// Tarvitaan:
// $saapumisnro
// ordercode (vapaaehtoinen) (u = new, m = change, p = delete)

$saapumisnro = (int) $saapumisnro;
$ordercode = !isset($ordercode) ? 'U' : $ordercode;

$query = "SELECT *
          FROM lasku
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tila    = 'K'
          AND alatila = ''
          AND vanhatunnus = 0
          AND tunnus  = '{$saapumisnro}'";
$res = pupe_query($query);
$row = mysql_fetch_assoc($res);

if ($row['sisviesti3'] == 'ok_vie_varastoon') {
  pupesoft_log('inbound_delivery', "Saapuminen {$saapumisnro} on jo kuitattu");
  exit;
}

// T‰m‰n saapumisen "p‰‰"-ostotilaus.
$query = "SELECT otunnus, min(toimaika) toimaika, count(*) maara
          FROM tilausrivi
          WHERE yhtio     = '{$kukarow['yhtio']}'
          AND tyyppi      = 'O'
          AND kpl         = 0
          AND varattu     > 0
          AND uusiotunnus = '{$saapumisnro}'
          GROUP BY otunnus
          ORDER BY maara DESC
          LIMIT 1";
$tilasnumero_res = pupe_query($query);
$tilasnumero_row = mysql_fetch_assoc($tilasnumero_res);

// haetaan toimittajan tiedot
$query = "SELECT *
          FROM toimi
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tunnus  = '{$row['liitostunnus']}'";
$toimires = pupe_query($query);
$toimirow = mysql_fetch_assoc($toimires);

// haetaan tilausrivit
$query = "SELECT *
          FROM tilausrivi
          WHERE yhtio     = '{$kukarow['yhtio']}'
          AND tyyppi      = 'O'
          AND kpl         = 0
          AND varattu     > 0
          AND uusiotunnus = '{$saapumisnro}'";
$rivit_res = pupe_query($query);

# Rakennetaan XML
$xml = simplexml_load_string("<?xml version='1.0' encoding='UTF-8'?><Message></Message>");

$header = $xml->addChild('MessageHeader');
$header->addChild('MessageType', 'inboundDelivery');
$header->addChild('Sender',      xml_cleanstring($yhtiorow['nimi']));
$header->addChild('Receiver',    'LogMaster');

$body = $xml->addChild('VendReceiptsList');
$body->addChild('PurchId',          $row['laskunro']);
$body->addChild('ReceiptsListId',   $tilasnumero_row['otunnus']);
$body->addChild('OrderCode',        $ordercode);
$body->addChild('OrderType',        'PO');
$body->addChild('ReceiptsListDate', tv1dateconv($row['luontiaika']));
$body->addChild('DeliveryDate',     tv1dateconv($tilasnumero_row['toimaika']));
$body->addChild('Warehouse',        '');

$vendor = $body->addChild('Vendor');
$vendor->addChild('VendAccount',  xml_cleanstring($toimirow['toimittajanro']));
$vendor->addChild('VendName',     xml_cleanstring($row['nimi']));
$vendor->addChild('VendStreet',   xml_cleanstring($row['osoite']));
$vendor->addChild('VendPostCode', xml_cleanstring($row['postino']));
$vendor->addChild('VendCity',     xml_cleanstring($row['postitp']));
$vendor->addChild('VendCountry',  xml_cleanstring($row['maa']));
$vendor->addChild('VendInfo',     '');

$purchaser = $body->addChild('Purchaser');
$purchaser->addChild('PurcAccount',  xml_cleanstring($yhtiorow['ytunnus']));
$purchaser->addChild('PurcName',     xml_cleanstring($yhtiorow['nimi']));
$purchaser->addChild('PurcStreet',   xml_cleanstring($yhtiorow['osoite']));
$purchaser->addChild('PurcPostCode', xml_cleanstring($yhtiorow['postino']));
$purchaser->addChild('PurcCity',     xml_cleanstring($yhtiorow['postitp']));
$purchaser->addChild('PurcCountry',  xml_cleanstring($yhtiorow['maa']));

$i = 1;
$ostotilaukset = array();

while ($rivit_row = mysql_fetch_assoc($rivit_res)) {
  $ostotilaukset[] = $rivit_row['otunnus'];

  $lines = $body->addChild('Lines');
  $line = $lines->addChild('Line');
  $line->addAttribute('No', $i);
  $line->addChild('TransId',         xml_cleanstring($rivit_row['tunnus']));
  $line->addChild('ItemNumber',      xml_cleanstring($rivit_row['tuoteno']));
  $line->addChild('OrderedQuantity', xml_cleanstring($rivit_row['varattu']));
  $line->addChild('Unit',            xml_cleanstring($rivit_row['yksikko']));
  $line->addChild('Price',           xml_cleanstring($rivit_row['hinta']));
  $line->addChild('CurrencyCode',    xml_cleanstring($row['valkoodi']));
  $line->addChild('RowInfo',         xml_cleanstring($rivit_row['kommentti']));

  $i++;
}

$xml_chk = (isset($xml->VendReceiptsList) and isset($xml->VendReceiptsList->Lines));

if ($xml_chk) {
  $ostotilaukset = array_unique($ostotilaukset);

  $_name = substr("in_{$row['laskunro']}_".implode('_', $ostotilaukset), 0, 25);
  $filename = $pupe_root_polku."/dataout/{$_name}.xml";

  if (file_put_contents($filename, $xml->asXML())) {
    // L‰hetet‰‰n aina UTF-8 muodossa
    $ftputf8 = true;

    if ($_cli) {
      echo "\n", t("Tiedoston luonti onnistui"), "\n";
    }
    else {
      echo "<br /><font class='message'>", t("Tiedoston luonti onnistui"), "</font><br />";
    }

    $ftphost = $logmaster['host'];
    $ftpuser = $logmaster['user'];
    $ftppass = $logmaster['pass'];
    $ftppath = $logmaster['path'];

    $ftpfile = realpath($filename);

    require "inc/ftp-send.inc";

    $query = "UPDATE lasku SET
              sisviesti3  = 'ei_vie_varastoon'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tila    = 'K'
              AND tunnus  = '{$saapumisnro}'";
    $updres = pupe_query($query);

    pupesoft_log('inbound_delivery', "Saapuminen {$row['laskunro']} l‰hetetty");
  }
  else {
    pupesoft_log('inbound_delivery', "Saapumisen {$row['laskunro']} l‰hetys ep‰onnistui");

    if ($_cli) {
      echo "\n", t("Tiedoston luonti ep‰onnistui"), "\n";
    }
    else {
      echo "<br /><font class='error'>", t("Tiedoston luonti ep‰onnistui"), "</font><br />";
    }
  }
}
