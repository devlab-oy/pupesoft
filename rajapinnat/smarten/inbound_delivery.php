<?php

date_default_timezone_set('Europe/Helsinki');

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  $_cli = false;

  if (strpos($_SERVER['SCRIPT_NAME'], "inbound_delivery.php") !== false) {
    require "../../inc/parametrit.inc";
  }
}
else {
  $_cli = true;

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

$query = "SELECT lasku.*
          FROM lasku
          WHERE lasku.yhtio = '{$kukarow['yhtio']}'
          AND lasku.tila    = 'K'
          AND lasku.alatila = ''
          AND lasku.vanhatunnus = 0
          AND lasku.tunnus  = '{$saapumisnro}'";
$res = pupe_query($query);
$row = mysql_fetch_assoc($res);

if ($row['sisviesti3'] != '') {
  pupesoft_log('smarten_inbound_delivery', "Saapuminen {$saapumisnro} on jo l‰hetetty");
  exit;
}

// T‰m‰n saapumisen "p‰‰"-ostotilaus.
$query = "SELECT tilausrivi.otunnus, min(tilausrivi.toimaika) toimaika, count(*) maara
          FROM tilausrivi
          WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
          AND tilausrivi.tyyppi      = 'O'
          AND tilausrivi.kpl         = 0
          AND tilausrivi.varattu     > 0
          AND tilausrivi.uusiotunnus = '{$saapumisnro}'
          GROUP BY tilausrivi.otunnus
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
$query = "SELECT varastopaikat.ulkoinen_jarjestelma,
          varastopaikat.ulkoisen_jarjestelman_tunnus,
          tuote.purkukommentti,
          tilausrivi.*
          FROM tilausrivi
          JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
          LEFT JOIN varastopaikat ON (
            varastopaikat.yhtio   = tilausrivi.yhtio AND
            varastopaikat.tunnus  = tilausrivi.varasto AND
            varastopaikat.tyyppi != 'P'
          )
          WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
          AND tilausrivi.tyyppi      = 'O'
          AND tilausrivi.kpl         = 0
          AND tilausrivi.varattu     > 0
          AND tilausrivi.uusiotunnus = '{$saapumisnro}'";
$rivit_res = pupe_query($query);

while ($rivit_row = mysql_fetch_assoc($rivit_res)) {
  if ($rivit_row['ulkoinen_jarjestelma'] != 'S') {
    pupesoft_log('smarten_inbound_delivery', "Saapuminen {$row['laskunro']} sis‰lt‰‰ muitakin kuin Smarten varaston tuotteita.");
    exit("Saapuminen {$row['laskunro']} sis‰lt‰‰ muitakin kuin Smarten varaston tuotteita.");
  }
}

mysql_data_seek($rivit_res, 0);

# Rakennetaan XML
$xml = simplexml_load_string("<?xml version='1.0' encoding='UTF-8'?><E-Document></E-Document>");

$header = $xml->addChild('Header');
$header->addChild('DateIssued', date('Y-m-d'));
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
$supplierparty->addChild('PartyCode', "5511");
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
$documentinfo->addChild('DocumentNum', $row['laskunro']);

$dateinfo = $documentinfo->addChild('DateInfo');
$dateinfo->addChild('OrderDate', tv1dateconv($tilasnumero_row['luontiaika']));
$dateinfo->addChild('DeliveryDateRequested', tv1dateconv($tilasnumero_row['toimaika']));

$refinfo = $documentinfo->addChild('RefInfo');
$sourcedocument = $refinfo->addChild('SourceDocument');
$sourcedocument->addChild('SourceDocumentNum');

// $documentsumgroup = $document->addChild("DocumentSumGroup");
// $documentsumgroup->addChild("Currency", xml_cleanstring($row['valkoodi']));

$documentitem = $document->addChild('DocumentItem');
$ostotilaukset = array();

while ($rivit_row = mysql_fetch_assoc($rivit_res)) {
  $ostotilaukset[] = $rivit_row['otunnus'];

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

  // T‰m‰ tuote vied‰‰n aina erikoisvarastoon
  if (!empty($rivit_row['purkukommentti']) and !empty($smarten['vas_varasto'])) {
    $location->addChild("WarehouseCode", $smarten['vas_varasto']);
  }
  else {
    $location->addChild("WarehouseCode", $rivit_row['ulkoisen_jarjestelman_tunnus']);
  }
}

$additionalinfo = $document->addChild('AdditionalInfo');

$extension = $additionalinfo->addChild("Extension");
$extension->addAttribute("extensionId", "internalremarks");
$extension->addChild("InfoContent", xml_cleanstring($row['kommentti']));

$xml_chk = (isset($xml->Document->DocumentItem) and isset($xml->Document->DocumentItem->ItemEntry));

if ($xml_chk) {
  $ostotilaukset = array_unique($ostotilaukset);

  $_date = date("Ymd_His");
  $filename = "/home/smarten/out/{$_date}_order_BNNB_{$row['laskunro']}.xml";

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
