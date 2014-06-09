<?php

/*
 * Siirretään avoimet ostotilaukset ja myyntitilaukset Relexiin
 * 2.2 OPEN ORDERS
*/

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiö on annettava!!");
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Yhtiö
$yhtio = mysql_real_escape_string($argv[1]);

// Tallannetan rivit tiedostoon
$filepath = "/tmp/input_orders_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus epäonnistui: $filepath\n");
}

// Otsikkotieto
$header = "location;product;type;quantity;estimated_date;value;reference;order_row;order_type;partner_code\n";
fwrite($fp, $header);

/*
 Transaktiotyypit:
 SALES_ORDER = Open sales order (can be linked to SALE with a reference number)
 ORDER = Open purchase order (can be linked to DELIVERY with a reference number)
*/

// Haetaan avoimet ostot ja myynnit
$query = "SELECT
          varastopaikat.nimitys varasto,
          tilausrivi.tuoteno tuote,
          tilausrivi.tyyppi tyyppi,
          tilausrivi.varattu+tilausrivi.jt maara,
          tilausrivi.toimaika toimituspaiva
          FROM tilausrivi
          JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
            AND tuote.tuoteno     = tilausrivi.tuoteno
            AND tuote.status     != 'P'
            AND tuote.ei_saldoa   = ''
            AND tuote.tuotetyyppi = ''
            AND tuote.ostoehdotus = '')
          JOIN varastopaikat ON (varastopaikat.tunnus = tilausrivi.varasto)
          WHERE tilausrivi.yhtio        = '$yhtio'
          AND tilausrivi.varattu       != 0
          AND tilausrivi.tyyppi        IN ('L','O')
          AND tilausrivi.laskutettuaika = '0000-00-00'
          ORDER BY tilausrivi.laadittu";
$res = pupe_query($query);

// Kerrotaan montako riviä käsitellään
$rows = mysql_num_rows($res);

echo "Tilausrivejä {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  // Määritellään transaktiotyyppi
  if ($row['tyyppi'] == "L") {
    $type = "SALES_ORDER";

    $row['maara'] *= -1; // Quantity sign defines the transaction direction as seen by the warehouse, e.g. outgoing sales order is sent as negative quantity
  }
  else {
    $type = "ORDER";
  }

  $rivi  = "{$row['varasto']};";        // Inventory location code
  $rivi .= "{$row['tuote']};";          // Item code
  $rivi .= "{$type};";                  // Open order type
  $rivi .= "{$row['maara']};";          // Open order quantity in inventory units
  $rivi .= "{$row['toimituspaiva']};";  // Estimated delivery date of the order
  $rivi .= ";";                         // Open order value in currency
  $rivi .= ";";                         // Sales or purchase order number
  $rivi .= ";";                         // Sales or purchase order row number
  $rivi .= ";";                         // Additional order type that can be used to distinct normal sales and deliveries from special sales and deliveries
  $rivi .= ";";                         // Customer for sales orders and Supplier for purchase orders
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "Käsitellään riviä {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
