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

// Logitetaan ajo
cron_log();

// Tämä vaatii paljon muistia
error_reporting(E_ALL);
ini_set("memory_limit", "5G");
ini_set("display_errors", 1);
unset($pupe_query_debug);

$tanaan = '2014-04-20';

function queryoneline($query) {
  $query = str_replace(array("\t", "\n", "\r"), " ", $query);
  $query = preg_replace("/  +/", " ", $query);
  return trim($query).";\n";
}

// Saldot Mas (huom, varastopaikat on jo siirretty artr)
$query = "SELECT *
          FROM tuotepaikat
          WHERE tuotepaikat.yhtio = 'atarv'
          AND tuotepaikat.saldo   <> 0";
$query_result = pupe_query($query);

$total_items = mysql_num_rows($query_result);
$current_item = 0;
$korjaus_sql = "";

while ($query_row = mysql_fetch_assoc($query_result)) {

  $current_item++;
  progress_bar($current_item, $total_items);

  $saldo = $query_row['saldo'];
  $tuoteno = strtoupper($query_row['tuoteno']);

  if ($tuoteno == '') {
    continue;
  }

  // Haetaan kehari Örumilta
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
    die("tänne ei voi joutua!");
  }
  else {
    $orum_tuoterow = mysql_fetch_assoc($query_result2);
  }

  $masin_keskihankintahinta = $orum_tuoterow['kehahin'];

  $saldo_query = "SELECT ifnull(sum(saldo), 0) saldo
                  FROM tuotepaikat
                  WHERE yhtio = 'artr'
                  AND tuoteno = '$tuoteno'";
  $saldo_result = pupe_query($saldo_query);
  $saldo_row = mysql_fetch_assoc($saldo_result);
  $uusi_saldo = $saldo_row['saldo'] + $saldo;

  if ($saldo_row['saldo'] != 0 and $uusi_saldo != 0) {
    // Aina Örumin puolelta kehahin_raw, Örumin epäkuranttius ei vaikuta tähän matikkaan
    $orumin_uusi_keskihankintahinta = round((($saldo_row['saldo'] * $orum_tuoterow['kehahin_raw']) + ($saldo * $masin_keskihankintahinta)) / ($uusi_saldo), 6);
  }
  else {
    $orumin_uusi_keskihankintahinta = round($masin_keskihankintahinta, 6);
  }

  // Tsekkaa kuka on ykköstoimittaja, jolta saldot on hommattu
  $query = "SELECT liitostunnus
            FROM tuotteen_toimittajat
            WHERE yhtio = 'atarv'
            AND tuoteno = '$tuoteno'
            ORDER BY if(jarjestys = 0, 999, jarjestys), tunnus
            LIMIT 1";
  $result = pupe_query($query);
  $toimitunnus_row = mysql_fetch_assoc($result);

  // Mas pupessa Örumin tunnus
  if ($toimitunnus_row['liitostunnus'] == '27371') {
    // Jos tuotteen toimittaja on Örum, niin Örum hyvittää tuotteet Masille
    $orum = TRUE;
    $tapahtumalaji = 'laskutus';

    // Käytetään Örumin vanhaa keskihinkintahintaa
    $orumin_uusi_keskihankintahinta = $masin_keskihankintahinta;
  }
  else {
    // Muiden toimittajien tuotteet Örum ostaa Masilta
    $orum = FALSE;
    $tapahtumalaji = 'tulo';
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
  $korjaus_sql .= queryoneline($query2);

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
  $korjaus_sql .= queryoneline($query2);

  if ($orum == FALSE and $tapahtumalaji == 'tulo' and ($orumin_uusi_keskihankintahinta <> 0 or $orum_tuoterow['kehahin'] != 0)) {
    $query2 = "UPDATE tuote SET
               kehahin     = $orumin_uusi_keskihankintahinta,
               vihahin     = $masin_keskihankintahinta,
               vihapvm     = now()
               WHERE yhtio = 'artr'
               and tuoteno = '$tuoteno'";
    $korjaus_sql .= queryoneline($query2);
  }

  $query = "INSERT INTO tuotepaikat SET ";

  for ($i = 0; $i < mysql_num_fields($query_result)-1; $i++) {
    $kentta = mysql_field_name($query_result, $i);

    // tunnusta ja yhtiötä ei monisteta
    if ($kentta == "yhtio" or $kentta == "tunnus") {
      continue;
    }

    $arvo = $query_row[$kentta];
    $query .= "$kentta = '$arvo', ";
  }

  $query .= " yhtio = 'artr' ";
  $query .= " ON duplicate key UPDATE
              muutospvm = now(),
              muuttaja = 'fuusio',
              saldo = saldo + {$query_row['saldo']} ";
  $korjaus_sql .= queryoneline($query);
}

echo "\n\n\n\n\n\n";

// Laitetaan korjaus sql fileen
file_put_contents("konversio-korjaus.sql", $korjaus_sql);
