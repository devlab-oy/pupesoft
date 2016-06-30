<?php

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {

  $_cli = false;

  if (strpos($_SERVER['SCRIPT_NAME'], "saapuminen_ulkoiseen_jarjestelmaan.php") !== false) {
    require "inc/parametrit.inc";
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

  // otetaan tietokanta connect ja funktiot
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  // Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
  pupesoft_flock();

  $yhtio = mysql_escape_string(trim($argv[1]));
  $yhtiorow = hae_yhtion_parametrit($yhtio);

  // Haetaan kukarow
  $query = "SELECT *
            FROM kuka
            WHERE yhtio = '{$yhtio}'
            AND kuka    = 'admin'";
  $kukares = pupe_query($query);

  if (mysql_num_rows($kukares) != 1) {
    exit("VIRHE: Admin k‰ytt‰j‰ ei lˆydy!\n");
  }

  $kukarow = mysql_fetch_assoc($kukares);

  $pupe_root_polku = dirname(__FILE__);

  $saapumisnro = $argv[2];

  if (!empty($argv[3])) {
    $ordercode = $argv[3];
  }
}

$ftp_chk = (!empty($ftp_logmaster_host) and !empty($ftp_logmaster_user));
$ftp_chk = ($ftp_chk and !empty($ftp_logmaster_pass) and !empty($ftp_logmaster_path));

if (!$ftp_chk) {
  die ("FTP-tiedot ovat puutteelliset!\n");
}

// Tarvitaan:
// $saapumisnro
// ordercode (vapaaehtoinen) (u = new, m = change, p = delete)

$saapumisnro = (int) $saapumisnro;
$ordercode = !isset($ordercode) ? 'U' : $ordercode;

$encoding = PUPE_UNICODE ? 'UTF-8' : 'ISO-8859-1';

$xmlstr  = "<?xml version='1.0' encoding='{$encoding}'?>";
$xmlstr .= '<Message>';
$xmlstr .= '</Message>';

$xml = new SimpleXMLElement($xmlstr);

$query = "SELECT *
          FROM lasku
          WHERE yhtio     = '{$kukarow['yhtio']}'
          AND tila        = 'K'
          AND alatila     = ''
          AND vanhatunnus = 0
          AND tunnus      = '{$saapumisnro}'";
$res = pupe_query($query);
$row = mysql_fetch_assoc($res);

if ($row['sisviesti3'] == 'ok_vie_varastoon') {
  pupesoft_log('inbound_delivery', "Saapuminen {$saapumisnro} on jo kuitattu");

  exit;
}

$header = $xml->addChild('MessageHeader');

$header->addChild('MessageType', 'inboundDelivery');
$header->addChild('Sender', utf8_encode($yhtiorow['nimi']));
$header->addChild('Receiver', 'LogMaster');

$body = $xml->addChild('VendReceiptsList');
$body->addChild('PurchId', $row['laskunro']);
$body->addChild('ReceiptsListId', '');

// U = new
// M = change
// P = delete
$body->addChild('OrderCode', $ordercode);
$body->addChild('OrderType', 'PO');
$body->addChild('ReceiptsListDate', tv1dateconv($row['luontiaika']));
$body->addChild('DeliveryDate', $row['toimaika']);
$body->addChild('Warehouse', '');

$query = "SELECT *
          FROM toimi
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tunnus  = '{$row['liitostunnus']}'";
$toimires = pupe_query($query);
$toimirow = mysql_fetch_assoc($toimires);

$vendor = $body->addChild('Vendor');
$vendor->addChild('VendAccount',  $toimirow['toimittajanro']);
$vendor->addChild('VendName',     utf8_encode($row['nimi']));
$vendor->addChild('VendStreet',   utf8_encode($row['osoite']));
$vendor->addChild('VendPostCode', $row['postino']);
$vendor->addChild('VendCity',     utf8_encode($row['postitp']));
$vendor->addChild('VendCountry',  utf8_encode($row['maa']));
$vendor->addChild('VendInfo', '');

$purchaser = $body->addChild('Purchaser');
$purchaser->addChild('PurcAccount',  $yhtiorow['ytunnus']);
$purchaser->addChild('PurcName',     utf8_encode($yhtiorow['nimi']));
$purchaser->addChild('PurcStreet',   utf8_encode($yhtiorow['osoite']));
$purchaser->addChild('PurcPostCode', $yhtiorow['postino']);
$purchaser->addChild('PurcCity',     utf8_encode($yhtiorow['postitp']));
$purchaser->addChild('PurcCountry',  utf8_encode($yhtiorow['maa']));

$query = "SELECT *
          FROM tilausrivi
          WHERE yhtio     = '{$kukarow['yhtio']}'
          AND tyyppi      = 'O'
          AND kpl         = 0
          AND varattu     > 0
          AND uusiotunnus = '{$saapumisnro}'";
$rivit_res = pupe_query($query);

$i = 1;
$ostotilaukset = array();

while ($rivit_row = mysql_fetch_assoc($rivit_res)) {

  $ostotilaukset[] = $rivit_row['otunnus'];

  $lines = $body->addChild('Lines');

  $line = $lines->addChild('Line');
  $line->addAttribute('No', $i);

  $line->addChild('TransId',         $rivit_row['tunnus']);
  $line->addChild('ItemNumber',      utf8_encode($rivit_row['tuoteno']));
  $line->addChild('OrderedQuantity', $rivit_row['varattu']);
  $line->addChild('Unit',            utf8_encode($rivit_row['yksikko']));
  $line->addChild('Price',           $rivit_row['hinta']);
  $line->addChild('CurrencyCode',    utf8_encode($row['valkoodi']));
  $line->addChild('RowInfo',         utf8_encode($rivit_row['kommentti']));

  $i++;
}

$xml_chk = (isset($xml->VendReceiptsList) and isset($xml->VendReceiptsList->Lines));

if ($xml_chk and $ftp_chk) {

  $ostotilaukset = array_unique($ostotilaukset);

  $_name = substr("in_{$row['laskunro']}_".implode('_', $ostotilaukset), 0, 25);
  $filename = $pupe_root_polku."/dataout/{$_name}.xml";

  if (file_put_contents($filename, $xml->asXML())) {

    // L‰hetet‰‰n UTF-8 muodossa jos PUPE_UNICODE on true
    $ftputf8 = PUPE_UNICODE;

    if ($_cli) {
      echo "\n", t("Tiedoston luonti onnistui"), "\n";
    }
    else {
      echo "<br /><font class='message'>", t("Tiedoston luonti onnistui"), "</font><br />";
    }

    $ftphost = $ftp_logmaster_host;
    $ftpuser = $ftp_logmaster_user;
    $ftppass = $ftp_logmaster_pass;
    $ftppath = $ftp_logmaster_path;
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
