<?php

if (php_sapi_name() != 'cli') {
  die('clionly!');
}

if (!isset($argv[1])) {
  echo "Anna Puperoot\n";
  exit(1);
}

$pupesoft_root = $argv[1];

if (!is_dir($pupesoft_root) or !is_file("{$pupesoft_root}/inc/salasanat.php")) {
  echo "Pupesoft root missing!";
  exit(1);
}

// Pupesoft root include_pathiin
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupesoft_root.PATH_SEPARATOR."/usr/share/pear");

// Otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";
require "tilauskasittely/luo_myyntitilausotsikko.inc";

// Logitetaan ajo
cron_log();

// Tämä vaatii paljon muistia
error_reporting(E_ALL);
ini_set("memory_limit", "5G");
ini_set("display_errors", 1);
unset($pupe_query_debug);

// todo, noi vois echottaa jos sinne tulee jotain?
$ongelmia_tuotteiden_etsinnassa = array();
$uusi_saldo_menee_nollaksi = array();
$ykkostoimittajaa_ei_loydy = array();
$uusi_keskihankintahinta_menee_nollaksi = array();

$tanaan = '2014-04-20';

/* Testausta varten

delete from tapahtuma where yhtio = 'artr' and laatija = 'fuusio' and tuoteno in ('+1','+11-48','+10');
update tuotepaikat set yhtio = 'atarv' where yhtio = 'artr' and tuoteno in ('+1','+11-48','+10') and hyllyalue in ('#M81','#M77','#M87','#M79');
delete from lasku where yhtio = 'artr' and laatija = 'admin' and tila in ('U','L') and viesti = 'liiketoimintakauppa' and luontiaika > '2014-04-26';
delete from lasku where yhtio = 'artr' and laatija = 'fuusio' and tila = 'H' and viesti = 'liiketoimintakauppa' and luontiaika > '2014-04-26';
delete from liitetiedostot where yhtio = 'artr' and liitos in ('lasku');
delete from tiliointi where yhtio = 'artr' and laatija = 'fuusio' and selite = 'Liiketoimintakauppa' and tapvm >= '2014-04-26';
update tuote set kehahin = '5' where yhtio = 'artr' and tuoteno in ('+11-48');
delete from tuote where yhtio = 'artr' and tuoteno like 'LIIKETOIMINTAKAUPPA%';
delete from tilausrivi where yhtio = 'artr' and tuoteno like 'LIIKETOIMINTAKAUPPA%' and tyyppi = 'L';
delete from tapahtuma where yhtio = 'artr' and tuoteno like 'LIIKETOIMINTAKAUPPA%' and laji = 'laskutus';

*/

// Saldot Mas (huom, varastopaikat on jo siirretty artr)
$query = "SELECT tuotepaikat.tunnus AS tuotepaikkatunnus,
          tuotepaikat.tuoteno,
          tuotepaikat.saldo,
          tuotepaikat.hyllyalue,
          tuotepaikat.hyllynro,
          tuotepaikat.hyllyvali,
          tuotepaikat.hyllytaso,
          round(if (tuote.epakurantti100pvm = '0000-00-00',
              if (tuote.epakurantti75pvm = '0000-00-00',
                if (tuote.epakurantti50pvm = '0000-00-00',
                  if (tuote.epakurantti25pvm = '0000-00-00',
                    tuote.kehahin,
                  tuote.kehahin * 0.75),
                tuote.kehahin * 0.5),
              tuote.kehahin * 0.25),
            0),
          6) kehahin,
          ifnull(varastopaikat.toimipaikka, 'tuntematon') toimipaikka,
          ifnull(varastopaikat.nimitys, 'tuntematon') nimitys
          FROM tuotepaikat
          INNER JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio
            AND tuote.tuoteno     = tuotepaikat.tuoteno
            AND tuote.ei_saldoa   = '')
          LEFT JOIN varastopaikat ON (varastopaikat.yhtio = 'artr'
            AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0'))
                                  <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
            AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0'))
                                  >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')))
          WHERE tuotepaikat.yhtio = 'atarv'
          AND tuotepaikat.saldo   <> 0";
$query_result = pupe_query($query);

$path_orum = "/tmp/liiketoimintakauppa_".date("YmdHms")."_orum.txt";
$path_muut = "/tmp/liiketoimintakauppa_".date("YmdHms")."_muut.txt";

// tallennetaan tiedostot Örum ja muut
$fp_orum = fopen($path_orum, 'w+');
$fp_muut = fopen($path_muut, 'w+');

$out = "Toimipaikka\tTuoteno\tMäärä\tHinta\tRivihinta\tTuotepaikka\n";

if (! fwrite($fp_orum, $out . "\n")) {
  echo "Failed writing row.\n";
  die();
}

if (! fwrite($fp_muut, $out . "\n")) {
  echo "Failed writing row.\n";
  die();
}

$total_items = mysql_num_rows($query_result);
$current_item = 0;

while ($query_row = mysql_fetch_assoc($query_result)) {

  $current_item++;
  progress_bar($current_item, $total_items);

  $saldo = $query_row['saldo'];
  $tuoteno = strtoupper($query_row['tuoteno']);
  $masin_keskihankintahinta = $query_row['kehahin'];

  // Tsekataa onko tuote jo artr:ssä
  $query = "SELECT tuote.tuoteno,
            tuote.ei_saldoa,
            round(if (tuote.epakurantti100pvm = '0000-00-00',
                if (tuote.epakurantti75pvm = '0000-00-00',
                  if (tuote.epakurantti50pvm = '0000-00-00',
                    if (tuote.epakurantti25pvm = '0000-00-00',
                      tuote.kehahin,
                    tuote.kehahin * 0.75),
                  tuote.kehahin * 0.5),
                tuote.kehahin * 0.25),
              0),
            6) kehahin,
            tuote.kehahin kehahin_raw
            FROM tuote
            WHERE tuote.yhtio = 'artr'
            AND tuote.tuoteno = '$tuoteno'";
  $query_result2 = pupe_query($query);

  if (mysql_num_rows($query_result2) == 0) {
    $query2 = "INSERT INTO tuote SET
               tuoteno     = '$tuoteno',
               nimitys     = '{$query_row['nimitys']}',
               lyhytkuvaus = 'Fuusiossa siirretty tuote liiketoimintakaupan vuoksi',
               status      = 'P',
               alv         = '24',
               try         = '999997',
               osasto      = '999997',
               yhtio       = 'artr',
               laatija     = 'fuusio',
               luontiaika  = now()";
    pupe_query($query2);

    $orum_tuoterow = array(
      'kehahin' => 0,
      'kehahin_raw' => 0,
      'tuoteno' => $tuoteno,
      'ei_saldoa' => '',
    );

    $query2 = "INSERT INTO tuotepaikat SET
               tuoteno    = '$tuoteno',
               hyllyalue  = 'T',
               hyllynro   = '0',
               hyllyvali  = '0',
               hyllytaso  = '0',
               oletus     = 'X',
               yhtio      = 'artr',
               laatija    = 'fuusio',
               luontiaika = now()";
    pupe_query($query2);
  }
  else {
    $orum_tuoterow = mysql_fetch_assoc($query_result2);
  }

  $saldo_query = "SELECT ifnull(sum(saldo), 0) saldo
                  FROM tuotepaikat
                  WHERE yhtio = 'artr'
                  AND tuoteno = '$tuoteno'";
  $saldo_result = pupe_query($saldo_query);
  $saldo_row = mysql_fetch_assoc($saldo_result);

  $uusi_saldo = $saldo_row['saldo'] + $saldo;

  // echotellaan jos jostain syystä saldo on pohjalla negatiivinen, niin voidaan tarkistaa mikä juttu on kyseessä
  if ($uusi_saldo == 0 and $saldo <> 0) {
    $uusi_saldo_menee_nollaksi[$tuoteno] = $tuoteno;
  }

  if ($saldo_row['saldo'] != 0 and $uusi_saldo != 0) {
    // Aina Örumin puolelta kehahin_raw, Örumin epäkuranttius ei vaikuta tähän matikkaan
    $orumin_uusi_keskihankintahinta = round((($saldo_row['saldo'] * $orum_tuoterow['kehahin_raw']) + ($saldo * $masin_keskihankintahinta)) / ($uusi_saldo), 6);
  }
  else {
    $orumin_uusi_keskihankintahinta = round($masin_keskihankintahinta, 6);
  }

  $out = $query_row['nimitys']."\t".$tuoteno."\t".$saldo."\t".$masin_keskihankintahinta."\t".$masin_keskihankintahinta*$saldo."\t".$query_row['hyllyalue']."-".$query_row['hyllynro']."-".$query_row['hyllyvali']."-".$query_row['hyllytaso'];

  // Tsekkaa kuka on ykköstoimittaja, jolta saldot on hommattu
  $query = "SELECT liitostunnus
            FROM tuotteen_toimittajat
            WHERE yhtio = 'atarv'
            AND tuoteno = '$tuoteno'
            ORDER BY if(jarjestys = 0, 999, jarjestys), tunnus
            LIMIT 1";
  $result = pupe_query($query);
  $toimitunnus_row = mysql_fetch_assoc($result);

  if (mysql_num_rows($result) == 0) {
    $ykkostoimittajaa_ei_loydy[$tuoteno] = $tuoteno;
  }

  // Mas pupessa Örumin tunnus
  if ($toimitunnus_row['liitostunnus'] == '27371') {
    // Jos tuotteen toimittaja on Örum, niin Örum hyvittää tuotteet Masille
    $orum = TRUE;
    $tapahtumalaji = 'laskutus';

    // Käytetään Örumin vanhaa keskihinkintahintaa
    $orumin_uusi_keskihankintahinta = $orum_tuoterow['kehahin'];

    if (! fwrite($fp_orum, $out . "\n")) {
      echo "Failed writing row.\n";
      die();
    }
  }
  else {
    // Muiden toimittajien tuotteet Örum ostaa Masilta
    $orum = FALSE;
    $tapahtumalaji = 'tulo';

    if (! fwrite($fp_muut, $out . "\n")) {
      echo "Failed writing row.\n";
      die();
    }
  }

  $query2 = "INSERT INTO tapahtuma SET
             hinta      = $orumin_uusi_keskihankintahinta,
             kpl        = $saldo,
             kplhinta   = $masin_keskihankintahinta,
             laadittu   = now(),
             laatija    = 'fuusio',
             laji       = '$tapahtumalaji',
             rivitunnus = '0',
             hyllyalue  = '{$query_row['hyllyalue']}',
             hyllynro   = '{$query_row['hyllynro']}',
             hyllyvali  = '{$query_row['hyllyvali']}',
             hyllytaso  = '{$query_row['hyllytaso']}',
             selite     = 'Liiketoimintakauppa Mas Orum',
             tuoteno    = '$tuoteno',
             yhtio      = 'artr'";
  $result2 = pupe_query($query2);

  $query2 = "INSERT INTO tapahtuma SET
             hinta      = $orumin_uusi_keskihankintahinta,
             kpl        = $saldo * -1,
             kplhinta   = $masin_keskihankintahinta,
             laadittu   = now(),
             laatija    = 'fuusio',
             laji       = '$tapahtumalaji',
             rivitunnus = '0',
             hyllyalue  = '{$query_row['hyllyalue']}',
             hyllynro   = '{$query_row['hyllynro']}',
             hyllyvali  = '{$query_row['hyllyvali']}',
             hyllytaso  = '{$query_row['hyllytaso']}',
             selite     = 'Liiketoimintakauppa Mas Orum vastakirjaus',
             tuoteno    = '$tuoteno',
             yhtio      = 'artr'";
  $result2 = pupe_query($query2);

  if ($orum == FALSE and $tapahtumalaji == 'tulo' and ($orumin_uusi_keskihankintahinta <> 0 or $orum_tuoterow['kehahin'] != 0)) {
    $query2 = "UPDATE tuote SET
               kehahin     = $orumin_uusi_keskihankintahinta,
               vihahin     = $masin_keskihankintahinta,
               vihapvm     = now()
               WHERE yhtio = 'artr'
               and tuoteno = '$tuoteno'";
    $result2 = pupe_query($query2);
  }

  if ($orumin_uusi_keskihankintahinta < 0) {
    $uusi_keskihankintahinta_menee_nollaksi[$tuoteno] = $tuoteno;
  }

  $query2 = "UPDATE tuotepaikat SET
             yhtio       = 'artr',
             oletus      = ''
             WHERE yhtio = 'atarv'
             AND tunnus  = '{$query_row['tuotepaikkatunnus']}'";
  $result2 = pupe_query($query2);
}

echo "\n\n\n\n\n\n";

fclose($fp_orum);
fclose($fp_muut);

$uusi_saldo_menee_nollaksi = array_unique($uusi_saldo_menee_nollaksi);
$ongelmia_tuotteiden_etsinnassa = array_unique($ongelmia_tuotteiden_etsinnassa);
$ykkostoimittajaa_ei_loydy = array_unique($ykkostoimittajaa_ei_loydy);
$uusi_keskihankintahinta_menee_nollaksi = array_unique($uusi_keskihankintahinta_menee_nollaksi);

sort($uusi_saldo_menee_nollaksi);
sort($ongelmia_tuotteiden_etsinnassa);
sort($ykkostoimittajaa_ei_loydy);
sort($uusi_keskihankintahinta_menee_nollaksi);

if (count($uusi_saldo_menee_nollaksi) > 0) {
  echo "\nUusi saldo menee nollaksi: ";
  foreach ($uusi_saldo_menee_nollaksi as $tuoteno) {
    echo "\n$tuoteno ";
  }
  echo "\n";
}

if (count($ongelmia_tuotteiden_etsinnassa) > 0) {
  echo "\nOngelmia tuotteiden etsinnassa (saldoton): ";
  foreach ($ongelmia_tuotteiden_etsinnassa as $tuoteno) {
    echo "\n$tuoteno ";
  }
  echo "\n";
}

if (count($ykkostoimittajaa_ei_loydy) > 0) {
  echo "\nYkköstoimittajaa ei löydy: ";
  foreach ($ykkostoimittajaa_ei_loydy as $tuoteno) {
    echo "\n$tuoteno ";
  }
  echo "\n";
}

if (count($uusi_keskihankintahinta_menee_nollaksi) > 0) {
  echo "\nUusi keskihankintahinta menee nollaksi: ";
  foreach ($uusi_keskihankintahinta_menee_nollaksi as $tuoteno) {
    echo "\n$tuoteno ";
  }
  echo "\n";
}
