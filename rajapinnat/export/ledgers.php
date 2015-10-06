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
$filepath1 = "/tmp/accounts_receivable_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp1 = fopen($filepath1, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath1\n");
}

$filepath2 = "/tmp/accounts_payable_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp2 = fopen($filepath2, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath2\n");
}

// Otsikkotieto
$header  = "Invoice number;";
$header .= "Invoice date;";
$header .= "Customer;";
$header .= "Name;";
$header .= "Open amount $yhtiorow[valkoodi];";
$header .= "Open amount in invoice currency;";
$header .= "Currency;";
$header .= "Due date;";
$header .= "Cash discount due date;";
$header .= "Cash discount amount $yhtiorow[valkoodi];";
$header .= "Cash discount amount in invoice currency;";
$header .= "Payer";
$header .= "\n";

fwrite($fp1, $header);

$header = str_replace("Customer;", "Supplier;", $header);
fwrite($fp2, $header);

// Haetaan avoimet myyntilaskut
$query = "(SELECT
          'SALESINVOICE' tyyppi,
          lasku.laskunro,
          lasku.tapvm,
          if(lasku.kapvm=0, '', lasku.kapvm) kapvm,
          if(asiakas.asiakasnro in ('0',''), asiakas.ytunnus, asiakas.asiakasnro) asiakasnro,
          concat_ws(' ', lasku.nimi, lasku.nimitark) nimi,
          lasku.erpcm,
          lasku.valkoodi,
          if(lasku.kasumma=0, '', lasku.kasumma) kasumma_valuutassa,
          if(lasku.kasumma=0, '', round(lasku.kasumma * lasku.vienti_kurssi, 2)) kasumma,
          sum(tiliointi.summa) avoinsaldo,
          sum(lasku.summa_valuutassa-lasku.saldo_maksettu_valuutassa) laskuavoinsaldo_valuutassa
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
          GROUP BY 1,2,3,4,5,6,7,8,9)

          UNION

          (SELECT
          'SUPPLIERINVOICE' tyyppi,
          if(lasku.laskunro > 0, lasku.laskunro, if(lasku.viite!='', lasku.viite, lasku.viesti)) laskunro,
          lasku.tapvm,
          if(lasku.kapvm=0, '', lasku.kapvm) kapvm,
          if(toimi.toimittajanro in ('0',''), toimi.ytunnus, toimi.toimittajanro) asiakasnro,
          concat_ws(' ', lasku.nimi, lasku.nimitark) nimi,
          lasku.erpcm,
          lasku.valkoodi,
          if(lasku.kasumma=0, '', lasku.kasumma) kasumma_valuutassa,
          if(lasku.kasumma=0, '', round(lasku.kasumma * lasku.vienti_kurssi, 2)) kasumma,
          tiliointi.summa * -1 avoinsaldo,
          lasku.summa laskuavoinsaldo_valuutassa
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

          ORDER BY tyyppi, erpcm, laskunro";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Reskontrarivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  $rivi  = pupesoft_csvstring($row['laskunro']).";";
  $rivi .= "{$row['tapvm']};";
  $rivi .= pupesoft_csvstring($row['asiakasnro']).";";
  $rivi .= pupesoft_csvstring($row['nimi']).";";
  $rivi .= "{$row['avoinsaldo']};";
  $rivi .= "{$row['laskuavoinsaldo_valuutassa']};";
  $rivi .= "{$row['valkoodi']};";
  $rivi .= "{$row['erpcm']};";
  $rivi .= "{$row['kapvm']};";
  $rivi .= "{$row['kasumma']};";
  $rivi .= "{$row['kasumma_valuutassa']};";
  $rivi .= ";";
  $rivi .= "\n";

  if ($row['tyyppi'] == "SALESINVOICE") {
    fwrite($fp1, $rivi);
  }
  else {
    fwrite($fp2, $rivi);
  }

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp1);
fclose($fp2);

if (!empty($scp_siirto)) {
  // Pakataan tiedosto
  system("zip {$filepath1}.zip $filepath1");
  system("zip {$filepath2}.zip $filepath2");

  // Siirret‰‰n toiselle palvelimelle
  system("scp {$filepath1}.zip {$filepath2}.zip $scp_siirto");
}

echo "Valmis.\n";
