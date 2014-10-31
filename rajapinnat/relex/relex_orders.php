<?php

/*
 * Siirret��n avoimet ostotilaukset ja myyntitilaukset Relexiin
 * 2.2 OPEN ORDERS
*/

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhti� on annettava!!");
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

$ajopaiva  = date("Y-m-d");
$paiva_ajo = FALSE;

if (isset($argv[2]) and $argv[2] != '') {
  $paiva_ajo = TRUE;

  if ($argv[2] == "edpaiva") {
    $ajopaiva = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  }
}

// Yhti�
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

$tuoterajaus = " AND tuote.status not in ('P','E')
                 AND tuote.ei_saldoa    = ''
                 AND tuote.tuotetyyppi  = '' ";

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/input_orders_{$yhtio}_$ajopaiva.csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep�onnistui: $filepath\n");
}

// Otsikkotieto
$header = "location;product;clean_product;type;quantity;estimated_date;value;reference;order_row;order_type;partner_code\n";
fwrite($fp, $header);

/*
 Transaktiotyypit:
 SALES_ORDER = Open sales order (can be linked to SALE with a reference number)
 ORDER = Open purchase order (can be linked to DELIVERY with a reference number)
*/

function add_open_orders_line($fp, $row) {
  $rivi  = "{$row['maa']}-{$row['varasto']};";                       // Inventory location code
  $rivi .= "{$row['maa']}-".pupesoft_csvstring($row['tuoteno']).";"; // Item code
  $rivi .= pupesoft_csvstring($row['tuoteno']).";";                  // Clean Item code
  $rivi .= "{$row['type']};";                                        // Open order type
  $rivi .= "{$row['maara']};";                                       // Open order quantity in inventory units
  $rivi .= "{$row['toimituspaiva']};";                               // Estimated delivery date of the order
  $rivi .= ";";                                                      // Open order value in currency
  $rivi .= ";";                                                      // Sales or purchase order number
  $rivi .= ";";                                                      // Sales or purchase order row number
  $rivi .= ";";                                                      // Additional order type that can be used to distinct normal sales and deliveries from special sales and deliveries
  $rivi .= "{$row['partner']}";                                      // Customer for sales orders and Supplier for purchase orders
  $rivi .= "\n";

  fwrite($fp, $rivi);
}

// Haetaan avoimet ostot : myynnit ja kulutukset : varastosiirrot ja valmistukset
$query = "(SELECT
           tilausrivi.laadittu,
           yhtio.maa,
           tilausrivi.varasto,
           tilausrivi.tuoteno,
           tilausrivi.tyyppi,
           tilausrivi.varattu maara,
           tilausrivi.toimaika toimituspaiva,
           tilausrivi.keratty,
           lasku.liitostunnus partner,
           '' vastaanottovarasto,
           '' sisainen_siirto
           FROM tilausrivi USE INDEX (yhtio_tyyppi_laskutettuaika)
           JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
           JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
             AND tuote.tuoteno            = tilausrivi.tuoteno
             {$tuoterajaus})
           JOIN yhtio ON (tilausrivi.yhtio = yhtio.yhtio)
           WHERE tilausrivi.yhtio         = '$yhtio'
           AND tilausrivi.varattu        != 0
           AND tilausrivi.tyyppi          = 'O'
           AND tilausrivi.laskutettuaika  = 0)

           UNION

           (SELECT
           tilausrivi.laadittu,
           yhtio.maa,
           tilausrivi.varasto,
           tilausrivi.tuoteno,
           tilausrivi.tyyppi,
           tilausrivi.varattu+tilausrivi.jt maara,
           tilausrivi.toimaika toimituspaiva,
           tilausrivi.keratty,
           lasku.liitostunnus partner,
           '' vastaanottovarasto,
           '' sisainen_siirto
           FROM tilausrivi USE INDEX (yhtio_tyyppi_kerattyaika)
           JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
           JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
             AND tuote.tuoteno            = tilausrivi.tuoteno
             {$tuoterajaus})
           JOIN yhtio ON (tilausrivi.yhtio = yhtio.yhtio)
           WHERE tilausrivi.yhtio         = '$yhtio'
           AND tilausrivi.varattu        != 0
           AND tilausrivi.tyyppi          in ('L','V')
           AND tilausrivi.kerattyaika     = 0)

           UNION

           (SELECT
           tilausrivi.laadittu,
           yhtio.maa,
           tilausrivi.varasto,
           tilausrivi.tuoteno,
           tilausrivi.tyyppi,
           tilausrivi.varattu+tilausrivi.jt maara,
           tilausrivi.toimaika toimituspaiva,
           tilausrivi.keratty,
           lasku.liitostunnus partner,
           lasku.clearing vastaanottovarasto,
           if (tilausrivi.tyyppi = 'G' and tilausrivi.varasto = lasku.clearing, 1, 0) sisainen_siirto
           FROM tilausrivi USE INDEX (yhtio_tyyppi_toimitettuaika)
           JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
           JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
             AND tuote.tuoteno            = tilausrivi.tuoteno
             {$tuoterajaus})
           JOIN yhtio ON (tilausrivi.yhtio = yhtio.yhtio)
           WHERE tilausrivi.yhtio         = '$yhtio'
           AND tilausrivi.varattu        != 0
           AND tilausrivi.tyyppi          in ('G','W','M')
           AND tilausrivi.toimitettuaika  = 0
           HAVING sisainen_siirto = 0)

           ORDER BY laadittu";
$res = pupe_query($query);

// Kerrotaan montako rivi� k�sitell��n
$rows = mysql_num_rows($res);

echo "Tilausrivej� {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  if ($row['partner'] > 0) {
    $row['partner'] = $row['maa']."-".$row['partner'];
  }

  // M��ritell��n transaktiotyyppi
  if ($row['tyyppi'] == "G") {
    // Siirtorivit laitetaan failiin avoimena myyntin� l�hdevarastosta ja avoimena ostona kohdevarastoon.
    // Kun siirtorivi on ker�tty, niin se ei en�� n�y avoimena myyntin�, pelk�st��n ostona kohdevarastoon.
    if ($row['keratty'] == "") {
      // Kirjataan "myyntirivi"
      $myyntirow = $row;
      $myyntirow['type'] = "SALES_ORDER";
      $myyntirow['maara'] *= -1;

      add_open_orders_line($fp, $myyntirow);
    }

    // Kirjataan "ostorivi"
    $row['type'] = "ORDER";
    $row['varasto'] = $row['vastaanottovarasto'];
  }
  elseif ($row['tyyppi'] == "L" or $row['tyyppi'] == "V") {
    $row['type'] = "SALES_ORDER";
    $row['maara'] *= -1; // Quantity sign defines the transaction direction as seen by the warehouse, e.g. outgoing sales order is sent as negative quantity
  }
  else {
    $row['type'] = "ORDER";
  }

  add_open_orders_line($fp, $row);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K�sitell��n rivi� {$k_rivi}\n";
  }
}

fclose($fp);

// Tehd��n FTP-siirto
if ($paiva_ajo and !empty($relex_ftphost)) {
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftppath = "/data/input";
  $ftpfile = $filepath;
  require "inc/ftp-send.inc";
}

echo "Valmis.\n";
