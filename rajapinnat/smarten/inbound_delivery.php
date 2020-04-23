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
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
  ini_set("display_errors", 1);

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

require "rajapinnat/smarten/smarten-functions.php";

// Tarvitaan:
// $saapumisnro
// ordercode (vapaaehtoinen) (u = new, m = change, p = delete)

$saapumisnro = (int) $saapumisnro;
$ordercode = !isset($ordercode) ? 'U' : $ordercode;

$query = "SELECT lasku.*, varastopaikat.ulkoisen_jarjestelman_tunnus
          FROM lasku
          INNER JOIN varastopaikat
            ON (varastopaikat.yhtio = lasku.yhtio AND varastopaikat.tunnus = lasku.varasto)
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tila    = 'K'
          AND alatila = ''
          AND vanhatunnus = 0
          AND tunnus  = '{$saapumisnro}'";
$res = pupe_query($query);
$row = mysql_fetch_assoc($res);

if ($row['sisviesti3'] != '') {
  pupesoft_log('smarten_inbound_delivery', "Saapuminen {$saapumisnro} on jo l‰hetetty");
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
$query = "SELECT varastopaikat.*, tilausrivi.*
          FROM tilausrivi
          JOIN varastopaikat ON (
            varastopaikat.yhtio   = tilausrivi.yhtio AND
            varastopaikat.tunnus  = tilausrivi.varasto AND
            varastopaikat.tyyppi != 'P' AND
            varastopaikat.ulkoinen_jarjestelma = 'S'
          )
          WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
          AND tilausrivi.tyyppi      = 'O'
          AND kpl         = 0
          AND varattu     > 0
          AND uusiotunnus = '{$saapumisnro}'";
$rivit_res = pupe_query($query);
$varasto_check = array();

while ($rivit_row = mysql_fetch_array($rivit_res)) {
  $varasto_check[$rivit_row['ulkoinen_jarjestelma']]++;
}

mysql_data_seek($rivit_res, 0);

if ($varasto_check['L'] == 0 and $varasto_check['P'] == 0 and $varasto_check['S'] > 0) {
  $uj_nimi = "Smarten";
}
else {
  pupesoft_log('smarten_inbound_delivery', "Saapuminen {$row['laskunro']} sis‰lt‰‰ muitakin kuin Smarten varaston tuotteita.");
  exit("Saapuminen {$row['laskunro']} sis‰lt‰‰ muitakin kuin Smarten varaston tuotteita.");
}

# Rakennetaan XML
$xml = simplexml_load_string("<?xml version='1.0' encoding='UTF-8'?><E-Document></E-Document>");

$header = $xml->addChild('Header');
$header->addChild('DateIssued', 'inboundDelivery');   // CHECK: Current time
$header->addChild('SenderGLN', "BNNB");
$header->addChild('ReceiverGLN', 'smarten');

$document = $xml->addChild('Document');
$document->addChild('DocumentType', 'order');
$document->addChild('DocumentFunction', 'original');

$documentparties = $document->addChild('DocumentParties');

$buyerparty = $documentparties->addChild('BuyerParty');
$buyerparty->addChild('PartyCode', 'BNNB');
$buyerparty->addChild('Name', xml_cleanstring($yhtiorow['nimi']));

//$payerparty = $documentparties->addChild('PayerParty');
//$payerparty->addChild('PartyCode', 'BNNB');
//$payerparty->addChild('Name', 'Partner name');

$supplierparty = $documentparties->addChild('SupplierParty');
$supplierparty->addChild('PartyCode', "2818");
$supplierparty->addChild('Name', xml_cleanstring(trim($row['nimi'] . " " . $row['nimitark'])));

$contactdata = $supplierparty->addChild("ContactData");

$actualaddress = $contactdata->addChild("ActualAddress");
$actualaddress->addChild("Address1", xml_cleanstring($row['osoite']));
$actualaddress->addChild("City", xml_cleanstring($row['postitp']));
$actualaddress->addChild("PostalCode", xml_cleanstring($row['postino']));
$actualaddress->addChild("CountryCode", xml_cleanstring($row['maa']));

$documentinfo = $document->addChild('DocumentInfo');
$documentinfo->addChild('DocumentSubType');
$documentinfo->addChild('DocumentName', "PurchaseOrder");
$documentinfo->addChild('DocumentNum', $tilasnumero_row['otunnus']);

$dateinfo = $documentinfo->addChild('DateInfo');
$dateinfo->addChild('OrderDate', tv1dateconv($row['luontiaika']));
$dateinfo->addChild('DeliveryDateRequested', tv1dateconv($tilasnumero_row['toimaika']));

$refinfo = $documentinfo->addChild('RefInfo');
$sourcedocument = $refinfo->addChild('SourceDocument');
$sourcedocument->addChild('SourceDocumentNum');

// $documentsumgroup = $document->addChild("DocumentSumGroup");
// $documentsumgroup->addChild("Currency", xml_cleanstring($row['valkoodi']));

$documentitem = $document->addChild('DocumentItem');

$varastotunnus = xml_cleanstring($row['ulkoisen_jarjestelman_tunnus']);
if (empty($varastotunnus)) {
  $varastotunnus = xml_cleanstring($row['varasto']);
}

while ($rivit_row = mysql_fetch_assoc($rivit_res)) {
  $itementry = $documentitem->addChild('ItemEntry');
  $itementry->addChild("LineItemNum", $rivit_row['tunnus']);
  $itementry->addChild("GTIN", $rivit_row['ean']);
  $itementry->addChild("BuyerItemCode", xml_cleanstring($rivit_row['tuoteno']));
  $itementry->addChild("ItemDescription", xml_cleanstring($rivit_row['nimitys']));

  $itemreserve = $itementry->addChild("ItemReserve");

  $itemreserveunit = $itemreserve->addChild("ItemReserveUnit");
  // $itemreserveunit->addChild("ItemUnit", xml_cleanstring($rivit_row['yksikko']));
  $itemreserveunit->addChild("AmountActual", xml_cleanstring($rivit_row['varattu']));

  $location = $itemreserve->addChild("Location");
  $location->addChild("WarehouseCode", $varastotunnus);
}

$additionalinfo = $document->addChild('AdditionalInfo');

$extension = $additionalinfo->addChild("Extension");
$extension->addAttribute("extensionId", "internalremarks");
$extension->addChild("InfoContent", xml_cleanstring($row['kommentti']));







/*
$body = $xml->addChild('VendReceiptsList');
$body->addChild('PurchId',          $row['laskunro']);
$body->addChild('ReceiptsListId',   $tilasnumero_row['otunnus']);
$body->addChild('OrderCode',        $ordercode);
$body->addChild('OrderType',        'PO');
$body->addChild('ReceiptsListDate', tv1dateconv($row['luontiaika']));
$body->addChild('DeliveryDate',     tv1dateconv($tilasnumero_row['toimaika']));
if ($uj_nimi == "Velox") {
  $body->addChild('Comments',        xml_cleanstring($row['comments']));
}
$body->addChild('Warehouse',        xml_cleanstring($row['ulkoisen_jarjestelman_tunnus']));

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
  $line->addChild('ItemNumber',      xml_cleanstring($rivit_row['tuoteno'], 32));
  $line->addChild('OrderedQuantity', xml_cleanstring($rivit_row['varattu']));
  $line->addChild('Unit',            xml_cleanstring($rivit_row['yksikko']));
  $line->addChild('Price',           xml_cleanstring($rivit_row['hinta']));
  $line->addChild('CurrencyCode',    xml_cleanstring($row['valkoodi']));
  $line->addChild('RowInfo',         xml_cleanstring($rivit_row['kommentti']));

  $i++;
}

$xml_chk = (isset($xml->VendReceiptsList) and isset($xml->VendReceiptsList->Lines));
*/

if ($xml_chk) {
  $ostotilaukset = array_unique($ostotilaukset);

  $_name = substr("in_{$row['laskunro']}_".implode('_', $ostotilaukset), 0, 25);
  $filename = $pupe_root_polku."/dataout/{$_name}.xml";

  if (file_put_contents($filename, $xml->asXML())) {

    $palautus = smarten_send_file($filename);

    if ($palautus == 0) {
      pupesoft_log('smarten_inbound_delivery', "Siirretiin saapuminen {$row['laskunro']}.");
    }
    else {
      pupesoft_log('smarten_inbound_delivery', "Saapumisen {$row['laskunro']} siirt‰minen ep‰onnistui.");
    }

    pupesoft_log('smarten_inbound_delivery', "Saapumisen {$row['laskunro']} luonti onnistui.");

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

    pupesoft_log('smarten_inbound_delivery', "Saapuminen {$row['laskunro']} l‰hetetty");
  }
  else {
    pupesoft_log('smarten_inbound_delivery', "Saapumisen {$row['laskunro']} luonti ep‰onnistui");

    if ($_cli) {
      echo "\n", t("Tiedoston luonti ep‰onnistui"), "\n";
    }
    else {
      echo "<br /><font class='error'>", t("Tiedoston luonti ep‰onnistui"), "</font><br />";
    }
  }
}
else {
  pupesoft_log('smarten_inbound_delivery', "Saapumisen {$saapumisnro} XML ei luotu, koska yht‰‰n rivi‰ ei lˆytynyt.");
}
