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

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

$ajopaiva  = date("Y-m-d");
$paiva_ajo = FALSE;

if (isset($argv[2]) and $argv[2] != '') {
  $paiva_ajo = TRUE;

  if ($argv[2] == "edpaiva") {
    $ajopaiva = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  }
}

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/input_transactions_{$yhtio}_$ajopaiva.csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
$header = "date;location;product;clean_product;type;quantity;value;sales_purchase_price;reference;order_row;order_type;partner_code\n";
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

$tapahtumarajaus = "";
$kerivirajaus    = " AND tilausrivi.kerattyaika > 0 ";

// Otetaan mukaan vain viimeisen vuorokauden j‰lkeen tehdyt
if ($paiva_ajo) {
  $tapahtumarajaus = " AND tapahtuma.laadittu >= date_sub(now(), interval 24 HOUR) ";
  $kerivirajaus    = " AND tilausrivi.kerattyaika >= date_sub(now(), interval 24 HOUR) ";
}

// Haetaan tapahtumista:
//  varastosiirrot (paitsi ker‰tyt siirtolistarivit)
//  tulot
//  valmistukset
//  hyvitysrivit

// Haetaan tilausriveilt‰:
//  ker‰tyt myyntirivit (ei normihyv‰reit‰, mutta ker‰tyt reklamatiot kyll‰)
//  ker‰tyt siirtorivit
//  ker‰tyt kulutukset

$query = "(SELECT
          yhtio.maa,
          tapahtuma.laadittu laadittu,
          date_format(tapahtuma.laadittu, '%Y-%m-%d') pvm,
          tapahtuma.varasto,
          tapahtuma.tuoteno,
          tapahtuma.laji,
          tapahtuma.kpl,
          if (tapahtuma.laji = 'tulo', tapahtuma.kplhinta, tapahtuma.hinta) kplhinta,
          lasku.tilaustyyppi,
          lasku.varasto lahdevarasto,
          lasku.clearing vastaanottovarasto,
          lasku.liitostunnus,
          if (lasku.tila is not null and lasku.tila = 'G' and tapahtuma.kpl < 0, 1, 0) keratty_siirto,
          if (tapahtuma.laji = 'laskutus' and (tapahtuma.kpl < 0 or tapahtuma.kpl > 0 and lasku.tilaustyyppi = 'R'), 1, 0) keratty_myynti
          FROM tapahtuma
          JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio
            AND tuote.tuoteno      = tapahtuma.tuoteno
            AND tuote.status      != 'P'
            AND tuote.ei_saldoa    = ''
            AND tuote.tuotetyyppi  = ''
            AND tuote.ostoehdotus  = '')
          JOIN yhtio ON (tapahtuma.yhtio = yhtio.yhtio)
          LEFT JOIN tilausrivi USE INDEX (PRIMARY) ON (tilausrivi.yhtio = tapahtuma.yhtio and tilausrivi.tunnus = tapahtuma.rivitunnus)
          LEFT JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
          WHERE tapahtuma.yhtio = '$yhtio'
          AND tapahtuma.laji in ('laskutus', 'tulo', 'siirto', 'valmistus','inventointi')
          AND tapahtuma.kpl != 0
          {$tapahtumarajaus}
          HAVING keratty_siirto = 0 AND keratty_myynti = 0)

          UNION

          (SELECT
          yhtio.maa,
          tilausrivi.kerattyaika laadittu,
          date_format(tilausrivi.kerattyaika, '%Y-%m-%d') pvm,
          tilausrivi.varasto,
          tilausrivi.tuoteno,
          if (tilausrivi.tyyppi='G', 'siirtolista', 'myynti') laji,
          (tilausrivi.kpl+tilausrivi.varattu) * -1 kpl,
          tilausrivi.hinta kplhinta,
          lasku.tilaustyyppi,
          lasku.varasto lahdevarasto,
          lasku.clearing vastaanottovarasto,
          lasku.liitostunnus,
          '' keratty_siirto,
          '' keratty_myynti
          FROM tilausrivi
          JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
            AND tuote.tuoteno      = tilausrivi.tuoteno
            AND tuote.status      != 'P'
            AND tuote.ei_saldoa    = ''
            AND tuote.tuotetyyppi  = ''
            AND tuote.ostoehdotus  = '')
          JOIN yhtio ON (tilausrivi.yhtio = yhtio.yhtio)
          JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
          WHERE tilausrivi.yhtio = '$yhtio'
          AND tilausrivi.tyyppi IN ('L','G','V')
          AND (tilausrivi.varattu+tilausrivi.kpl > 0 OR (tilausrivi.varattu+tilausrivi.kpl < 0 and lasku.tilaustyyppi = 'R'))
          {$kerivirajaus})
          ORDER BY laadittu, tuoteno";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Tapahtumarivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  // Rivin arvo
  $arvo = abs(round($row['kplhinta']*$row['kpl'], 2));

  // M‰‰ritell‰‰n transaktiotyyppi
  switch (strtolower($row['laji'])) {
  case 'tulo':
    $type    = "DELIVERY";
    $partner = $row['liitostunnus'];
    break;
  case 'myynti':
  case 'laskutus':
    $type    = "SALE";
    $partner = $row['liitostunnus'];
    break;
  case 'siirto':
    // vastaanotetut siirtolistat ja manuaalisiirrot
    $type    = "TRANSFER";
    $partner = $row['lahdevarasto'];
    break;
  case 'siirtolista':
    // ker‰tyt siirtolistat
    $type    = "TRANSFER";
    $partner = $row['vastaanottovarasto'];
    break;
  case 'inventointi':
    $type    = "INVENTORY_CHECK";
    $partner = "";
    break;
  case 'valmistus':
    $type    = "DELIVERY";

    if ($row["tilaustyyppi"] == "V") {
      // Asiakkaallevalmistus
      $partner = $row['liitostunnus'];
    }
    else {
      // Varastoonvalmistus
      $partner = $row['vastaanottovarasto'];
    }
    break;
  case 'kulutus':
    $type    = "SALE";
    if ($row["tilaustyyppi"] == "V") {
      // Asiakkaallevalmistus
      $partner = $row['liitostunnus'];
    }
    else {
      // Varastoonvalmistus
      $partner = $row['vastaanottovarasto'];
    }
  }

  if ($partner > 0) {
    $partner = $row['maa']."-".$partner;
  }

  $rivi  = "{$row['pvm']};";                                         // Transaction posting date
  $rivi .= "{$row['maa']}-{$row['varasto']};";                       // Inventory location code
  $rivi .= "{$row['maa']}-".pupesoft_csvstring($row['tuoteno']).";"; // Item code
  $rivi .= pupesoft_csvstring($row['tuoteno']).";";                  // Clean Item code
  $rivi .= "{$type};";                                               // Transaction type
  $rivi .= "{$row['kpl']};";                                         // Transaction quantity in inventory units
  $rivi .= "{$arvo};";                                               // Transaction value in currency
  $rivi .= ";";                                                      // Purchase cost of sales transaction
  $rivi .= ";";                                                      // Sales or purchase order number
  $rivi .= ";";                                                      // Sales or purchase order row number
  $rivi .= ";";                                                      // Additional subtype for sales and delivery transactions
  $rivi .= "{$partner}";                                             // Customer for sales transactions, Supplier for incoming deliveries, Sending/receiving warehouse for stock transfers
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
  require "inc/ftp-send.inc";
}

echo "Valmis.\n";
