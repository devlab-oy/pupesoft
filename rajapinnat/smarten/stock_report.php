<?php

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut l‰hett‰v‰‰ yhtiˆt‰!\n");
}

if (trim($argv[2]) == '') {
  die ("Et antanut luettavien tiedostojen polkua!\n");
}

if (trim($argv[3]) == '') {
  die ("Et antanut s‰hkˆpostiosoitetta!\n");
}

// lis‰t‰‰n includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
ini_set("display_errors", 1);
ini_set("memory_limit", "2G");

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/smarten/smarten-functions.php";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  exit("VIRHE: Admin k‰ytt‰j‰ ei lˆydy!\n");
}

$path = trim($argv[2]);
$error_email = trim($argv[3]);
$email_array = array();

$path = rtrim($path, '/').'/';
$handle = opendir($path);

if ($handle === false) {
  exit;
}

while (false !== ($file = readdir($handle))) {
  $full_filepath = $path.$file;
  list($message_type, $message_subtype) = smarten_message_type($full_filepath);

  // Tyypin tulee olla INVRPT ja subtyypin tyhj‰
  if ($message_type != 'INVRPT' or !in_array($message_subtype, array('','CORRMVM','CORRADJ'))) {
    continue;
  }

  $xml = simplexml_load_file($full_filepath);

  pupesoft_log('smarten_stock_report', "K‰sitell‰‰n sanoma {$file}");

  $luontiaika = (string) $xml->Document->DocumentInfo->DateInfo->IssueDate;

  $saldoeroja = array();
  $saldosummat = array();
  $tuotteet_varasto = array();

  foreach ($xml->Document->DocumentItem->ItemEntry as $line) {
    $item_number = (string) $line->SellerItemCode;

    foreach ($line->ItemReserve as $stock_item) {
      $smartenmaara = (float) $stock_item->ItemReserveUnit->AmountActual;
      $varastotunnus = (string) $stock_item->Location->WarehouseCode;

      // Pidet‰‰n yll‰ tuote-/varastokohtaista saldosummaa
      if (!empty($saldosummat[$item_number][$varastotunnus])) {
        $saldosummat[$item_number][$varastotunnus] += $smartenmaara;
      }
      else {
        $saldosummat[$item_number][$varastotunnus] = $smartenmaara;
      }
    }
  }

  foreach ($saldosummat as $item_number => $varastot) {
    foreach($varastot as $varastotunnus => $smartenmaara) {
      $query = "SELECT tuoteno, nimitys
                FROM tuote
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$item_number}'";
      $tuoteres = pupe_query($query);
      $tuoterow = mysql_fetch_assoc($tuoteres);

      if (mysql_num_rows($tuoteres) == 0) {
        $saldoeroja[] = array(
          "varasto"   => "",
          "item"      => $item_number,
          "smarten"   => $smartenmaara,
          "nimitys"   => "",
          "pupe"      => "",
          "tuoteno"   => "",
          "lisatieto" => "Tuotetta {$item_number} ei lˆydy",
        );

        continue;
      }

      $query = "SELECT *
                FROM varastopaikat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND ulkoinen_jarjestelma = 'S'
                AND ulkoisen_jarjestelman_tunnus = '$varastotunnus'";
      $varastores = pupe_query($query);

      if (mysql_num_rows($varastores) == 1) {
        $varastorow = mysql_fetch_assoc($varastores);
      }
      else {
        $saldoeroja[] = array(
          "varasto"   => "",
          "item"      => $item_number,
          "smarten"   => $smartenmaara,
          "nimitys"   => $tuoterow['nimitys'],
          "pupe"      => "",
          "tuoteno"   => $tuoterow["tuoteno"],
          "lisatieto" => "Ulkoisen j‰rjestelm‰n varastotunnusta ei lˆydy: {$varastotunnus}",
        );

        continue;
      }

      $query = "SELECT *, concat_ws('-', hyllyalue, hyllynro, hyllyvali, hyllytaso) AS tuotepaikka
                FROM tuotepaikat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND varasto = '{$varastorow['tunnus']}'
                AND tuoteno = '{$tuoterow['tuoteno']}'
                ORDER BY oletus DESC, prio, hyllyalue, hyllynro, hyllytaso, hyllypaikka";
      $tuotepaikat = pupe_query($query);

      if (mysql_num_rows($tuotepaikat) == 0) {
        // Tuotteella ei viel‰ ole paikkaa t‰ss‰ varastossa, perustetaan se
        $uusi_paikka = lisaa_tuotepaikka($tuoterow["tuoteno"], $varastorow["alkuhyllyalue"], $varastorow["alkuhyllynro"], '0', '0', 'Lis‰ttiin tuotepaikka generoinnissa');

        $query = "SELECT *, concat_ws('-', hyllyalue, hyllynro, hyllyvali, hyllytaso) AS tuotepaikka
                  FROM tuotepaikat
                  WHERE tunnus = '{$uusi_paikka['tuotepaikan_tunnus']}'";
        $tuotepaikat = pupe_query($query);
      }

      if (in_array($message_subtype, array('CORRMVM','CORRADJ'))) {
        // Muutetaan paikan saldoa
        $tuotepaikka_row = mysql_fetch_assoc($tuotepaikat);
        $tuotepaikka = $tuotepaikka_row['tuotepaikka'];

        if ($smartenmaara > 0) {
          $uusi_maara = "+".$smartenmaara;
        }
        else {
          $uusi_maara = "-".abs($smartenmaara);
        }

        $tuoteno = $tuoterow['tuoteno'];
        $hylly = array($tuoterow['tuoteno'], $tuotepaikka_row['hyllyalue'], $tuotepaikka_row['hyllynro'], $tuotepaikka_row['hyllyvali'], $tuotepaikka_row['hyllytaso']);
        $hash = implode('###', $hylly);
        $tuote = array($tuotepaikka_row['tunnus'] => $hash);
        $maara = array($tuotepaikka_row['tunnus'] => $uusi_maara);
        $tee = 'VALMIS';
        $lisaselite = t("Smarten").": $message_subtype";
        $mobiili = 'YES';

        require 'inventoi.php';
      }
      else {
        list($saldo, $hyllyssa, $myytavissa, $devnull) = saldo_myytavissa($tuoterow["tuoteno"], "KAIKKI", $varastorow['tunnus']);

        // Ker‰t‰‰n distinct tuote / varasto
        $tuotteet_varasto[$varastorow['tunnus']][$tuoterow['tuoteno']] = $tuoterow['tuoteno'];
        
        // Etuk‰teen maksetut tilaukset, jotka ovat ker‰‰m‰tt‰ mutta tilaus jo laskutettu
        // Lasketaan ne mukaan Pupen hyllyss‰ m‰‰r‰‰n, koska saldo_myytavissa ei huomioi niit‰
        $query = "SELECT ifnull(sum(tilausrivi.kpl), 0) AS keraamatta
                  FROM tilausrivi
                  INNER JOIN lasku on (lasku.yhtio = tilausrivi.yhtio
                    AND lasku.tunnus          = tilausrivi.otunnus
                    AND lasku.mapvm          != '0000-00-00'
                    AND lasku.chn             = '999')
                  WHERE tilausrivi.yhtio      = '{$kukarow['yhtio']}'
                  AND tilausrivi.tyyppi       = 'L'
                  AND tilausrivi.var         != 'P'
                  AND tilausrivi.keratty      = ''
                  AND tilausrivi.kerattyaika  = '0000-00-00 00:00:00'
                  AND tilausrivi.tuoteno      = '{$tuoterow['tuoteno']}'";
        $ker_result = pupe_query($query);
        $ker_rivi = mysql_fetch_assoc($ker_result);

        $hyllyssa += $ker_rivi['keraamatta'];

        // Vertailukonversio
        $a = (int) $smartenmaara * 10000;
        $b = (int) $hyllyssa * 10000;

        if ($a != $b) {
          $lisatieto = "";
          $saldo_muuttui = 0;

          while ($tuotepaikka_row = mysql_fetch_array($tuotepaikat)) {
            $tuotepaikka = $tuotepaikka_row['tuotepaikka'];

            if ($lisatieto == "") {
              $uusi_maara = $smartenmaara;
              $lisatieto = t("Inventoitu tuotepaikalle") . " {$tuotepaikka}";
            }
            else {
              $uusi_maara = 0;
              $lisatieto .= ", " . t("paikan") . " {$tuotepaikka} " . t("saldo nollattu");
            }

            $tuoteno = $tuoterow['tuoteno'];
            $hylly = array($tuoterow['tuoteno'], $tuotepaikka_row['hyllyalue'], $tuotepaikka_row['hyllynro'], $tuotepaikka_row['hyllyvali'], $tuotepaikka_row['hyllytaso']);
            $hash = implode('###', $hylly);
            $tuote = array($tuotepaikka_row['tunnus'] => $hash);
            $maara = array($tuotepaikka_row['tunnus'] => $uusi_maara);
            $tee = 'VALMIS';
            $lisaselite = t("Smarten saldoraportti");            
            $mobiili = 'YES';
            $smarten_inventointi = TRUE;

            require 'inventoi.php';
            
            // Muuttuiko saldo oikeasti? $smarten_inventointi falsetetaan jos erotus on nolla
            if ($smarten_inventointi) {
              $saldo_muuttui++;
            }
          }

          if ($saldo_muuttui > 0) {            
            $saldoeroja[] = array(
              "varasto"   => $varastorow['tunnus'] . " " . $varastorow['nimitys'],
              "item"      => $item_number,
              "smarten"   => $smartenmaara,
              "nimitys"   => $tuoterow['nimitys'],
              "pupe"      => $hyllyssa,
              "tuoteno"   => $tuoterow['tuoteno'],
              "lisatieto" => $lisatieto,
            );
          }
        }
      }
    }
  }

  // Nollataan kaikki tuotteet jotka eiv‰t olleet mukana rapparilla
  if (empty($message_subtype)) {
    foreach ($tuotteet_varasto as $varastotunnus => $tuotteet) {
      $tuotekoodit = implode("','", $tuotteet);
      $query = "SELECT tuotepaikat.*, 
                concat_ws('-', tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) AS tuotepaikka,
                tuote.nimitys tuotenimi,
                varastopaikat.nimitys varastonimi
                FROM tuotepaikat
                LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio AND varastopaikat.tunnus = tuotepaikat.varasto)
                LEFT JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno)
                WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
                AND tuotepaikat.varasto = '{$varastotunnus}'
                AND tuotepaikat.tuoteno not in ('{$tuotekoodit}')
                AND tuotepaikat.saldo != 0";
      $tpres = pupe_query($query);
      
      while ($tuotepaikka_row = mysql_fetch_assoc($tpres)) {
        $tuotepaikka = $tuotepaikka_row['tuotepaikka'];

        $uusi_maara = 0;
        $lisatieto  = t("Paikan") . " {$tuotepaikka} " . t("saldo nollattu");

        $tuoteno = $tuotepaikka_row['tuoteno'];
        $hylly = array($tuotepaikka_row['tuoteno'], $tuotepaikka_row['hyllyalue'], $tuotepaikka_row['hyllynro'], $tuotepaikka_row['hyllyvali'], $tuotepaikka_row['hyllytaso']);
        $hash = implode('###', $hylly);
        $tuote = array($tuotepaikka_row['tunnus'] => $hash);
        $maara = array($tuotepaikka_row['tunnus'] => $uusi_maara);
        $tee = 'VALMIS';
        $lisaselite = t("Smarten saldoraportti");            
        $mobiili = 'YES';
        $smarten_inventointi = TRUE;
        
        require 'inventoi.php';
        
        // Muuttuiko saldo oikeasti? $smarten_inventointi falsetetaan jos erotus on nolla
        if ($smarten_inventointi) {
          $saldoeroja[] = array(
            "varasto"   => $varastotunnus . " " . $tuotepaikka_row['varastonimi'],
            "item"      => $tuotepaikka_row['tuoteno'],
            "smarten"   => 0,
            "nimitys"   => $tuotepaikka_row['tuotenimi'],
            "pupe"      => $tuotepaikka_row['saldo'],
            "tuoteno"   => $tuotepaikka_row['tuoteno'],
            "lisatieto" => $lisatieto,
          );
        }
      }
    }
  } 

  if (count($saldoeroja) > 0) {

    $email_array[] = t("Seuraavien tuotteiden saldovertailuissa on havaittu eroja").":";
    $email_array[] = t("Varasto").";".t("Smarten-tuoteno").";".t("Tuoteno").";".t("Nimitys").";".t("Smarten-kpl").";".t("Pupesoft-kpl").";".t("Lis‰tieto");

    foreach ($saldoeroja as $ero) {
      $email_array[] = "{$ero['varasto']};{$ero['item']};{$ero['tuoteno']};{$ero['nimitys']};{$ero['smarten']};{$ero['pupe']};{$ero['lisatieto']}";
    }

    pupesoft_log('smarten_stock_report', "Sanoman {$file} saldovertailussa oli eroja.");
  }
  else {
    pupesoft_log('smarten_stock_report', "Sanoman {$file} saldovertailussa ei ollut eroja.");
  }

  $params = array(
    'email' => $error_email,
    'email_array' => $email_array,
    'log_name' => 'smarten_stock_report',
  );

  smarten_send_email($params);

  // siirret‰‰n tiedosto done-kansioon
  rename($full_filepath, $path.'done/'.$file);
  pupesoft_log('smarten_stock_report', "Sanoman {$file} saldovertailu k‰sitelty");
}

closedir($handle);
