<?php

/*
 * Siirret‰‰n avoimet ostotilaukset ja myyntitilaukset Relexiin
 * 2.2 OPEN ORDERS
*/

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiˆ on annettava!!");
}

$paiva_ajo = FALSE;

if (isset($argv[2]) and $argv[1] != '') {
  $paiva_ajo = TRUE;
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/input_orders_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
$header = "location;product;clean_product;type;quantity;estimated_date;value;reference;order_row;order_type;partner_code\n";
fwrite($fp, $header);

/*
 Transaktiotyypit:
 SALES_ORDER = Open sales order (can be linked to SALE with a reference number)
 ORDER = Open purchase order (can be linked to DELIVERY with a reference number)
*/

// Haetaan avoimet ostot ja myynnit
$query = "SELECT
          yhtio.maa,
          tilausrivi.varasto,
          tilausrivi.tuoteno,
          tilausrivi.tyyppi,
          tilausrivi.varattu+tilausrivi.jt maara,
          tilausrivi.toimaika toimituspaiva,
          lasku.liitostunnus partner
          FROM tilausrivi
          JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
          JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
            AND tuote.tuoteno     = tilausrivi.tuoteno
            AND tuote.status     != 'P'
            AND tuote.ei_saldoa   = ''
            AND tuote.tuotetyyppi = ''
            AND tuote.ostoehdotus = '')
          JOIN yhtio ON (tilausrivi.yhtio = yhtio.yhtio)
          WHERE tilausrivi.yhtio        = '$yhtio'
          AND tilausrivi.varattu       != 0
          AND tilausrivi.tyyppi        IN ('L','O')
          AND tilausrivi.laskutettuaika = '0000-00-00'
          ORDER BY tilausrivi.laadittu";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Tilausrivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  // M‰‰ritell‰‰n transaktiotyyppi
  if ($row['tyyppi'] == "L") {
    $type = "SALES_ORDER";

    $row['maara'] *= -1; // Quantity sign defines the transaction direction as seen by the warehouse, e.g. outgoing sales order is sent as negative quantity
  }
  else {
    $type = "ORDER";
  }

  if ($row['partner'] > 0) {
     $row['partner'] = $row['maa']."-".$row['partner'];
  }

  $rivi  = "{$row['maa']}-{$row['varasto']};";                       // Inventory location code
  $rivi .= "{$row['maa']}-".pupesoft_csvstring($row['tuoteno']).";"; // Item code
  $rivi .= pupesoft_csvstring($row['tuoteno']).";";                  // Clean Item code
  $rivi .= "{$type};";                                               // Open order type
  $rivi .= "{$row['maara']};";                                       // Open order quantity in inventory units
  $rivi .= "{$row['toimituspaiva']};";                               // Estimated delivery date of the order
  $rivi .= ";";                                                      // Open order value in currency
  $rivi .= ";";                                                      // Sales or purchase order number
  $rivi .= ";";                                                      // Sales or purchase order row number
  $rivi .= ";";                                                      // Additional order type that can be used to distinct normal sales and deliveries from special sales and deliveries
  $rivi .= "{$row['partner']}";                                      // Customer for sales orders and Supplier for purchase orders
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp);

// Tehd‰‰n FTP-siirto
if ($paiva_ajo and !empty($relex_ftphost)) {
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftppath = "/data/input";
  $ftpfile = $filepath;
  require("inc/ftp-send.inc");
}

echo "Valmis.\n";
