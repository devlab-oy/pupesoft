<?php

/*
 * Siirret‰‰n osto- ja myyntireskontrat
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
$filepath = "/tmp/ledgers_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
$header  = "Invoice type;";
$header .= "Invoice number;";
$header .= "Invoice date;";
$header .= "Customer/Supplier;";
$header .= "Name;";
$header .= "Open amount;";
$header .= "Due date;";
$header .= "Invoice number;";
$header .= "Payer";
$header .= "\n";

fwrite($fp, $header);

// Haetaan avoimet myyntilaskut
$query = "(SELECT
          'SALESINVOICE' tyyppi,
          lasku.laskunro,
          lasku.tapvm,
          if(asiakas.asiakasnro in ('0',''), asiakas.ytunnus, asiakas.asiakasnro) asiakasnro,
          concat_ws(' ', lasku.nimi, lasku.nimitark) nimi,
          lasku.erpcm,
          sum(tiliointi.summa) avoinsaldo
          FROM lasku use index (yhtio_tila_mapvm)
          JOIN asiakas on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
          JOIN tiliointi use index (tositerivit_index) ON (lasku.yhtio = tiliointi.yhtio
            and lasku.tunnus = tiliointi.ltunnus
            and tiliointi.tilino in ('$yhtiorow[myyntisaamiset]', '$yhtiorow[factoringsaamiset]', '$yhtiorow[konsernimyyntisaamiset]')
            and tiliointi.korjattu = ''
            and tiliointi.tapvm <= current_date)
          WHERE lasku.yhtio = '{$yhtio}'
          and lasku.mapvm = '0000-00-00'
          and lasku.tapvm <= current_date
          and lasku.tapvm > '0000-00-00'
          and lasku.tila = 'U'
          and lasku.alatila = 'X'
          GROUP BY 1,2,3,4,5,6)

          UNION

          (SELECT
          'SUPPLIERINVOICE' tyyppi,
          if(lasku.laskunro > 0, lasku.laskunro, if(lasku.viite!='', lasku.viite, lasku.viesti)) laskunro,
          lasku.tapvm,
          if(toimi.toimittajanro in ('0',''), toimi.ytunnus, toimi.toimittajanro) asiakasnro,
          concat_ws(' ', lasku.nimi, lasku.nimitark) nimi,
          lasku.erpcm,
          tiliointi.summa * -1 avoinsaldo
          FROM lasku use index (yhtio_tila_tapvm)
          JOIN toimi on (toimi.yhtio = lasku.yhtio and toimi.tunnus = lasku.liitostunnus)
          JOIN tiliointi use index (tositerivit_index) ON (lasku.yhtio=tiliointi.yhtio
            and lasku.tunnus = tiliointi.ltunnus
            and lasku.tapvm = tiliointi.tapvm
            and tiliointi.tilino IN ('$yhtiorow[ostovelat]','$yhtiorow[konserniostovelat]')
            and tiliointi.korjattu = '' )
          WHERE lasku.yhtio = '{$yhtio}'
          and mapvm = '0000-00-00'
          and lasku.tapvm <= current_date
          and lasku.tapvm > '0000-00-00'
          and tila in ('H','Y','M','P','Q'))

          ORDER BY erpcm, laskunro";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Reskontrarivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  $rivi  = "{$row['tyyppi']};";
  $rivi .= "{$row['laskunro']};";
  $rivi .= "{$row['tapvm']};";
  $rivi .= "{$row['asiakasnro']};";
  $rivi .= pupesoft_csvstring($row['nimi']).";";
  $rivi .= "{$row['avoinsaldo']};";
  $rivi .= "{$row['erpcm']};";
  $rivi .= "{$row['laskunro']};";
  $rivi .= ";";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp);

if (!empty($scp_siirto)) {
  // Pakataan tiedosto
  system("zip {$filepath}.zip $filepath");

  // Siirret‰‰n toiselle palvelimelle
  system("scp {$filepath}.zip $scp_siirto");
}

echo "Valmis.\n";
