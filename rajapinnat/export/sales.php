<?php

/*
 * Siirret‰‰n myynnit
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

$scp_siirto = "";

if (!empty($argv[2])) {
  $scp_siirto = $argv[2];
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/sales_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
$header  = "Invoice date;";
$header .= "Customer number;";
$header .= "Customer country;";
$header .= "Customer region;";
$header .= "Customer group;";
$header .= "Payer number;";
$header .= "Invoice number;";
$header .= "Item number;";
$header .= "Item quantity;";
$header .= "Turnover in local currency;";
$header .= "Standard costs in local currency;";
$header .= "Customer name;";
$header .= "Sales man name";
$header .= "\n";

fwrite($fp, $header);

$rajaus = " AND lasku.laskutettu >= '2013-01-01 00:00:00'";

// Haetaan aika jolloin t‰m‰ skripti on viimeksi ajettu
$datetime_checkpoint = cron_aikaleima("CGNS_SALES_CRON");

// Otetaan mukaan vain edellisen ajon j‰lkeen tehdyt tapahtumat
if ($datetime_checkpoint != "") {
  $rajaus = " AND lasku.laskutettu > '$datetime_checkpoint' ";
}

// Haetaan tapahtumat
$query = "SELECT lasku.laskunro,
          if(asiakas.asiakasnro in ('0',''), asiakas.ytunnus, asiakas.asiakasnro) asiakasnro,
          asiakas.maa,
          lasku.myyja,
          asiakas.piiri,
          asiakas.osasto,
          concat_ws(' ', lasku.nimi, lasku.nimitark) nimi,
          tilausrivi.laskutettuaika,
          tilausrivi.tuoteno,
          tilausrivi.kpl,
          tilausrivi.rivihinta,
          tilausrivi.kate,
          round((tilausrivi.kate-tilausrivi.rivihinta)*-1, 2) keha
          FROM lasku
          INNER JOIN tilausrivi ON (lasku.yhtio = tilausrivi.yhtio
            AND lasku.tunnus      = tilausrivi.otunnus
            AND tilausrivi.tyyppi = 'L')
          INNER JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
            AND tuote.tuoteno     = tilausrivi.tuoteno)
          INNER JOIN asiakas USE INDEX (PRIMARY) ON (asiakas.yhtio = lasku.yhtio
            AND asiakas.tunnus    = lasku.liitostunnus)
          WHERE lasku.yhtio       = '{$yhtio}'
          AND lasku.tila          = 'L'
          AND lasku.alatila       = 'X'
          {$rajaus}
          ORDER BY lasku.tapvm, lasku.laskunro, tilausrivi.tuoteno";
$res = pupe_query($query);

$datetime_checkpoint_uusi = date('Y-m-d H:i:s');

// Tallennetaan aikaleima
cron_aikaleima("CGNS_SALES_CRON", $datetime_checkpoint_uusi);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Tapahtumarivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  //etsit‰‰n myyj‰n nimi
  $query  = "SELECT nimi
             from kuka
             where tunnus = '$row[myyja]'
             and yhtio    = '{$yhtio}'";
  $myyres = pupe_query($query);
  $myyrow = mysql_fetch_assoc($myyres);

  $rivi  = "{$row['laskutettuaika']};";
  $rivi .= "{$row['asiakasnro']};";
  $rivi .= "{$row['maa']};";

  $osre = t_avainsana("PIIRI", "", "and avainsana.selite  = '{$row['piiri']}'");
  $osrow = mysql_fetch_assoc($osre);
  $rivi .= pupesoft_csvstring($row['piiri']." ".$osrow['selitetark']).";";

  $osre = t_avainsana("ASIAKASOSASTO", "", "and avainsana.selite  = '{$row['osasto']}'");
  $osrow = mysql_fetch_assoc($osre);
  $rivi .= pupesoft_csvstring($row['osasto']." ".$osrow['selitetark']).";";
  $rivi .= "{$row['asiakasnro']};";
  $rivi .= "{$row['laskunro']};";
  $rivi .= pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= "{$row['kpl']};";
  $rivi .= "{$row['rivihinta']};";
  $rivi .= "{$row['keha']};";
  $rivi .= pupesoft_csvstring($row['nimi']).";";
  $rivi .= pupesoft_csvstring($myyrow['nimi']);
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp);

if (!empty($scp_siirto)) {
  // Siirret‰‰n toiselle palvelimelle
  system("scp {$filepath} $scp_siirto");
}

echo "Valmis.\n";
