<?php

// Kutsutaanko CLI:stä
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
    die ("Et antanut lähettävää yhtiötä!\n");
  }

  if (trim($argv[2]) == '') {
    die ("Et antanut saapumisnumeroa!\n");
  }

  // lisätään includepathiin pupe-root
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
  ini_set("display_errors", 1);

  error_reporting(E_ALL);

  // otetaan tietokanta connect ja funktiot
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  // Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
  pupesoft_flock();

  $yhtio = mysql_escape_string(trim($argv[1]));
  $yhtiorow = hae_yhtion_parametrit($yhtio);
  $kukarow = hae_kukarow('admin', $yhtio);

  if (!isset($kukarow)) {
    exit("VIRHE: Admin käyttäjä ei löydy!\n");
  }

  $pupe_root_polku = dirname(__FILE__);

  $saapumisnro = $argv[2];

  if (!empty($argv[3])) {
    $ordercode = $argv[3];
  }
}

if (!in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'S'))) {
  die ("Saapumisen lähettäminen estetty yhtiötasolla!\n");
}

require "rajapinnat/logmaster/logmaster-functions.php";

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

if ($row['sisviesti3'] != '') {
  pupesoft_log('logmaster_inbound_delivery', "Saapuminen {$saapumisnro} on jo lähetetty");
  exit;
}

// Tämän saapumisen "pää"-ostotilaus.
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

    $palautus = logmaster_send_file($filename);

    if ($palautus == 0) {
      pupesoft_log('logmaster_inbound_delivery', "Siirretiin saapuminen {$row['laskunro']}.");
    }
    else {
      pupesoft_log('logmaster_inbound_delivery', "Saapumisen {$row['laskunro']} siirtäminen epäonnistui.");
    }

    pupesoft_log('logmaster_inbound_delivery', "Saapumisen {$row['laskunro']} luonti onnistui.");

    if ($_cli) {
      echo "\n", t("Tiedoston luonti onnistui"), "\n";
    }
    else {
      echo "<br /><font class='message'>", t("Tiedoston luonti onnistui"), "</font><br />";
    }


    $query = "UPDATE lasku SET
              sisviesti3  = 'lahetetty_ulkoiseen_jarjestelmaan'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tila    = 'K'
              AND tunnus  = '{$saapumisnro}'";
    $updres = pupe_query($query);

    pupesoft_log('logmaster_inbound_delivery', "Saapuminen {$row['laskunro']} lähetetty");
  }
  else {
    pupesoft_log('logmaster_inbound_delivery', "Saapumisen {$row['laskunro']} luonti epäonnistui");

    if ($_cli) {
      echo "\n", t("Tiedoston luonti epäonnistui"), "\n";
    }
    else {
      echo "<br /><font class='error'>", t("Tiedoston luonti epäonnistui"), "</font><br />";
    }
  }
}
else {
  pupesoft_log('logmaster_inbound_delivery', "Saapumisen {$saapumisnro} XML ei luotu, koska yhtään riviä ei löytynyt.");
}
