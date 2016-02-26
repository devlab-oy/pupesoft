<?php

/*
 * Siirret‰‰n varastotapahtumat Relexiin
 * 2.1 INVENTORY TRANSACTIONS
*/

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 2;

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

// Logitetaan ajo
cron_log();

$ajopaiva  = date("Y-m-d");
$paiva_ajo = FALSE;
$weekly_ajo = FALSE;
$kuukausi_ajo = FALSE;
$ftppath = "/data/input";

if (isset($argv[2]) and $argv[2] != '') {
  if (is_numeric($argv[2])) {
    $kuukausi_ajo = TRUE;
    $vuosi = pupesoft_cleannumber($argv[2]);

    if (isset($argv[3]) and is_numeric($argv[3])) {
      $kuukausi = sprintf('%02d', pupesoft_cleannumber($argv[3]));
    }
  }
  else {
    if (strpos($argv[2], "-") !== FALSE) {
      list($y, $m, $d) = explode("-", $argv[2]);
      if (is_numeric($y) and is_numeric($m) and is_numeric($d) and checkdate($m, $d, $y)) {
        $ajopaiva = $argv[2];
      }
    }

    if (strtoupper($argv[2]) == 'WEEKLY') {
      $weekly_ajo = TRUE;
    }
    else {
      $paiva_ajo = TRUE;
    }
  }
}

if (isset($argv[4]) and trim($argv[4]) != '') {
  $ftppath = trim($argv[4]);
}

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

$tuoterajaus = rakenna_relex_tuote_parametrit();

// Tallennetaan rivit tiedostoon
if ($kuukausi_ajo) {
  $filepath = "/tmp/history_{$vuosi}{$kuukausi}_input_transactions_{$yhtio}.csv";
}
elseif ($weekly_ajo) {
  $filepath = "/tmp/input_transactions_weekly_{$yhtio}_{$ajopaiva}.csv";
}
else {
  $filepath = "/tmp/input_transactions_{$yhtio}_{$ajopaiva}.csv";
}

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

// Haetaan aika jolloin t‰m‰ skripti on viimeksi ajettu
$datetime_checkpoint = cron_aikaleima("RELEX_TRAN_CRON");

// Otetaan mukaan vain edellisen ajon j‰lkeen tehdyt tapahtumat
if ($paiva_ajo and $datetime_checkpoint != "") {
  $tapahtumarajaus = " AND tapahtuma.laadittu > '$datetime_checkpoint' ";
  $kerivirajaus    = " AND tilausrivi.kerattyaika > '$datetime_checkpoint' ";
}
elseif ($paiva_ajo) {
  $tapahtumarajaus = " AND tapahtuma.laadittu >= date_sub(now(), interval 24 HOUR) ";
  $kerivirajaus    = " AND tilausrivi.kerattyaika >= date_sub(now(), interval 24 HOUR) ";
}

if ($kuukausi_ajo) {

  // Kuukauden vika p‰iv‰
  $vikapaiva = date("t", mktime(0, 0, 0, $kuukausi, 1, $vuosi));

  $tapahtumarajaus = " AND tapahtuma.laadittu >= '$vuosi-$kuukausi-01 00:00:00'
                       AND tapahtuma.laadittu <= '$vuosi-$kuukausi-$vikapaiva 23:59:59' ";

  $kerivirajaus    = " AND tilausrivi.kerattyaika >= '$vuosi-$kuukausi-01 00:00:00'
                       AND tilausrivi.kerattyaika <= '$vuosi-$kuukausi-$vikapaiva 23:59:59'";

}

// Haetaan tapahtumista:
//  varastosiirrot (paitsi ker‰tyt siirtolistarivit, eik‰ sis‰isi‰ siirtoja, eik‰ kirjanpidollisia siirtoja)
//  tulot
//  valmistukset
//  hyvitysrivit

// Haetaan tilausriveilt‰:
//  ker‰tyt myyntirivit (ei normihyv‰reit‰, mutta ker‰tyt reklamatiot kyll‰)
//  ker‰tyt siirtorivit (paitsi sis‰iset siirrot, eik‰ kirjanpidollisia siirtoja)
//  ker‰tyt kulutukset

$query_ale_lisa = generoi_alekentta('M');

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
           tapahtuma.tunnus as sorttaustunnus,
           if (lasku.tila is not null and lasku.tila = 'G' and tapahtuma.kpl < 0, 1, 0) keratty_siirto,
           if (tapahtuma.laji = 'laskutus' and (tapahtuma.kpl < 0 or tapahtuma.kpl > 0 and lasku.tilaustyyppi = 'R'), 1, 0) keratty_myynti,
           if (tapahtuma.laji = 'siirto' and (tilausrivi.varasto = lasku.clearing or lasku.chn = 'KIR'), 1, 0) sisainen_tai_kir_siirto
           FROM tapahtuma
           JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio
             AND tuote.tuoteno     = tapahtuma.tuoteno
             {$tuoterajaus})
           JOIN yhtio ON (tapahtuma.yhtio = yhtio.yhtio)
           LEFT JOIN tilausrivi USE INDEX (PRIMARY) ON (tilausrivi.yhtio = tapahtuma.yhtio and tilausrivi.tunnus = tapahtuma.rivitunnus)
           LEFT JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
           WHERE tapahtuma.yhtio   = '$yhtio'
           AND tapahtuma.laji      in ('laskutus', 'tulo', 'siirto', 'valmistus','inventointi')
           AND tapahtuma.kpl      != 0
           {$tapahtumarajaus}
           HAVING keratty_siirto = 0 AND keratty_myynti = 0 AND sisainen_tai_kir_siirto = 0)

           UNION

           (SELECT
           yhtio.maa,
           tilausrivi.kerattyaika laadittu,
           date_format(tilausrivi.kerattyaika, '%Y-%m-%d') pvm,
           if (tilausrivi.tyyppi = 'L' and lasku.varastosiirto_tunnus > 0, kirjanpidollinen_siirto.varasto, tilausrivi.varasto) varasto,
           tilausrivi.tuoteno,
           if (tilausrivi.tyyppi='G', 'siirtolista', 'myynti') laji,
           (tilausrivi.kpl+tilausrivi.varattu) * -1 kpl,
           if (tilausrivi.tyyppi='G', tuote.kehahin, round(tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * {$query_ale_lisa}, 2)) kplhinta,
           lasku.tilaustyyppi,
           lasku.varasto lahdevarasto,
           lasku.clearing vastaanottovarasto,
           lasku.liitostunnus,
           tilausrivi.tunnus as sorttaustunnus,
           '' keratty_siirto,
           '' keratty_myynti,
           if (tilausrivi.tyyppi = 'G' and (tilausrivi.varasto = lasku.clearing or lasku.chn = 'KIR'), 1, 0) sisainen_tai_kir_siirto
           FROM tilausrivi
           JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
             AND tuote.tuoteno     = tilausrivi.tuoteno
             {$tuoterajaus})
           JOIN yhtio ON (tilausrivi.yhtio = yhtio.yhtio)
           JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
           LEFT JOIN lasku kirjanpidollinen_siirto USE INDEX (PRIMARY) ON (lasku.yhtio = kirjanpidollinen_siirto.yhtio and lasku.varastosiirto_tunnus = kirjanpidollinen_siirto.tunnus and lasku.varastosiirto_tunnus > 0)
           WHERE tilausrivi.yhtio  = '$yhtio'
           AND tilausrivi.tyyppi   IN ('L','G','V')
           AND (tilausrivi.varattu+tilausrivi.kpl > 0 OR (tilausrivi.varattu+tilausrivi.kpl < 0 and lasku.tilaustyyppi = 'R'))
           {$kerivirajaus}
           HAVING sisainen_tai_kir_siirto = 0)

           ORDER BY laadittu, tuoteno, sorttaustunnus";
$res = pupe_query($query);

if ($kuukausi_ajo) {
  $datetime_checkpoint_uusi = "$vuosi-$kuukausi-$vikapaiva 23:59:59";

  if (strtotime($datetime_checkpoint_uusi) > strtotime(date('Y-m-d H:i:s'))) {
    $datetime_checkpoint_uusi = date('Y-m-d H:i:s');
  }
}
else {
  $datetime_checkpoint_uusi = date('Y-m-d H:i:s');
}

// Tallennetaan aikaleima
cron_aikaleima("RELEX_TRAN_CRON", $datetime_checkpoint_uusi);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo date("d.m.Y @ G:i:s") . ": Relex Tapahtumarivej‰ {$rows} kappaletta.\n";

$relex_transactions = array();

while ($row = mysql_fetch_assoc($res)) {
  $relex_transactions[] = $row;
}

$k_rivi = 0;

foreach ($relex_transactions as $row) {

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

  // Tsekataan manuaalisiirron kohde
  if ($type == "TRANSFER" and $row['kpl'] < 0 and empty($partner)) {
    $row_seuraava = $relex_transactions[$k_rivi+1];

    if ($row_seuraava['tuoteno'] == $row['tuoteno'] and ($row_seuraava['kpl']*-1) == $row['kpl']) {
      //Jos varastonsis‰inen sirto niin ei lis‰t‰ failiin
      if ($row['varasto'] == $row_seuraava['varasto']) {
        $k_rivi++;
        continue;
      }

      $partner = "{$row_seuraava['maa']}-{$row_seuraava['varasto']}";
    }
  }

  // Tsekataan manuaalisiirron l‰hde
  if ($type == "TRANSFER" and $row['kpl'] > 0 and empty($partner)) {
    $row_edellinen = $relex_transactions[$k_rivi-1];

    if ($row_edellinen['tuoteno'] == $row['tuoteno'] and ($row_edellinen['kpl']*-1) == $row['kpl']) {
      //Jos varastonsis‰inen sirto niin ei lis‰t‰ failiin
      if ($row['varasto'] == $row_edellinen['varasto']) {
        $k_rivi++;
        continue;
      }

      $partner = "{$row_edellinen['maa']}-{$row_edellinen['varasto']}";
    }
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
}

fclose($fp);

// Tehd‰‰n FTP-siirto
if (($paiva_ajo or $weekly_ajo) and !empty($relex_ftphost)) {
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftpfile = $filepath;
  require "inc/ftp-send.inc";
}

echo date("d.m.Y @ G:i:s") . ": Relext tapahtumat valmis.\n";
