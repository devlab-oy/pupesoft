<?php

/*
 * Siirret‰‰n varastotapahtumat Relexiin
 * 2.1 INVENTORY TRANSACTIONS
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

ini_set("memory_limit", "1G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

// Tallannetan rivit tiedostoon
$filepath = "/tmp/input_transactions_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
$header = "date;location;product;type;quantity;value;sales_purchase_price;reference;order_row;order_type;partner_code\n";
fwrite($fp, $header);

/*
 Transaktiotyypit:
 SALE = Goods sold from stock (or customer returned the goods->reversal)
 INTERNAL_SALE = Company internal sales from e.g. central warehouse to stores
 DELIVERY = Goods received into stock (or goods returned to supplier->reversal).
 TRANSFER = Stock transfer from one inventory location to another
 SPOILAGE = Scrapped or spoiled from inventory (most relevant with fresh products)
 INVENTORY_CHECK = Inventory balance correction based on inventory checking
 ADJUSTMENT = Inventory balance correction (similar to INVENTORY_CHECK)
 LOST_SALE = Lost sales
 SPECIAL_DELIVERY = Used to distinct normal deliveries from quick special deliveries
*/

// Haetaan tapahtumat
$query = "SELECT
          date_format(tapahtuma.laadittu, '%Y-%m-%d') pvm,
          varastopaikat.nimitys varasto,
          tapahtuma.tuoteno tuote,
          tapahtuma.laji laji,
          tapahtuma.kpl maara
          FROM tapahtuma
          JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio
            AND tuote.tuoteno     = tapahtuma.tuoteno
            AND tuote.status     != 'P'
            AND tuote.ei_saldoa   = ''
            AND tuote.tuotetyyppi = ''
            AND tuote.ostoehdotus = '')
          JOIN varastopaikat ON (varastopaikat.tunnus = tapahtuma.varasto)
          WHERE tapahtuma.yhtio   = '$yhtio'
          AND tapahtuma.laji     in ('tulo', 'laskutus', 'siirto', 'valmistus', 'kulutus','inventointi')
          AND tapahtuma.laadittu >= date_sub(now(), interval 1 year)
          ORDER BY tapahtuma.laadittu, tapahtuma.tuoteno
          LIMIT 1000";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Tapahtumarivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  // M‰‰ritell‰‰n transaktiotyyppi
  switch (strtolower($row['laji'])) {
  case 'tulo':
    $type = "DELIVERY";
    break;
  case 'laskutus':
    $type = "SALE";
    break;
  case 'siirto':
    $type = "TRANSFER";
    break;
  case 'inventointi':
    $type = "INVENTORY_CHECK";
    break;
  case 'valmistus':
    $type = "DELIVERY";
    break;
  case 'kulutus':
    $type = "SALE";
  }

  $rivi  = "{$row['pvm']};";     // Transaction posting date
  $rivi .= "{$row['varasto']};"; // Inventory location code
  $rivi .= "{$row['tuote']};";   // Item code
  $rivi .= "{$type};";           // Transaction type
  $rivi .= "{$row['maara']};";   // Transaction quantity in inventory units
  $rivi .= ";";                  // Transaction value in currency
  $rivi .= ";";                  // Purchase cost of sales transaction
  $rivi .= ";";                  // Sales or purchase order number
  $rivi .= ";";                  // Sales or purchase order row number
  $rivi .= ";";                  // Additional subtype for sales and delivery transactions
  $rivi .= ";";                  // Customer for sales transactions, Supplier for incoming deliveries, Sending/receiving warehouse for stock transfers
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
