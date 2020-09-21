<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut lähettävää yhtiötä!\n");
}

if (trim($argv[2]) == '') {
  die ("Et antanut luettavien tiedostojen polkua!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
ini_set("display_errors", 1);

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/smarten/smarten-functions.php";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  exit("VIRHE: Admin käyttäjä ei löydy!\n");
}

$path = trim($argv[2]);
$email = isset($argv[3]) ? trim($argv[3]) : "";

$email_array = array();

$path = rtrim($path, '/').'/';
$handle = opendir($path);

if ($handle === false) {
  exit;
}

while (false !== ($file = readdir($handle))) {
  $full_filepath = $path.$file;
  list($message_type, $message_subtype) = smarten_message_type($full_filepath);

  if ($message_type != 'RECADV') {
    continue;
  }

  $sanoman_kaikki_rivit = '';
  $tilausrivit = $tilausrivit_error = $tilausrivit_success = $kasitellyt_rivitunnukset = array();

  pupesoft_log('smarten_inbound_delivery_confirmation', "Käsitellään sanoma {$file}");

  $xml = simplexml_load_file($full_filepath);

  $saapumisnro = (int) array_shift($xml->xpath('Document/DocumentInfo/RefInfo/SourceDocument[@type="order"]/SourceDocumentNum'));

  $email_array[] = "";
  $email_array[] = t("Saapuminen %s", "", $saapumisnro);
  $email_array[] = "";

  $query = "SELECT tunnus
            FROM lasku
            WHERE yhtio     = '{$yhtio}'
            AND tila        = 'K'
            AND alatila    != 'X'
            AND vanhatunnus = 0
            AND laskunro    = '{$saapumisnro}'";
  $selectres = pupe_query($query);
  $selectrow = mysql_fetch_assoc($selectres);

  $saapumistunnus = (int) $selectrow['tunnus'];

  if ($saapumistunnus == 0) {
    pupesoft_log('smarten_inbound_delivery_confirmation', "Kuittausta odottavaa saapumista ei löydy saapumisnumerolla {$saapumisnro} sanomassa {$file}");

    $email_array[] = t("Kuittausta odottavaa saapumista ei löydy saapumisnumerolla %d", "", $saapumisnro);

    rename($full_filepath, $path."error/".$file);
    continue;
  }

  // Smarten-varastot
  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ulkoinen_jarjestelma = 'S'
            AND ulkoisen_jarjestelman_tunnus != ''";
  $varastores = pupe_query($query);
  $varastot = array();

  while ($varastorow = mysql_fetch_assoc($varastores)) {
    $varastot[$varastorow['ulkoisen_jarjestelman_tunnus']] = $varastorow;
  }

  // Otetaan rivit talteen
  $sanoman_kaikki_rivit = $xml->Document->DocumentItem;

  if (empty($sanoman_kaikki_rivit) or !isset($sanoman_kaikki_rivit->ItemEntry)) {
    pupesoft_log('smarten_inbound_delivery_confirmation', "Sanomassa {$file} ei ollut rivejä");

    $email_array[] = t("Saapumisen %d kuittaussanomassa ei löytynyt rivejä", "", $saapumisnro);

    rename($full_filepath, $path."error/".$file);
    continue;
  }

  # Ei haluta viedä varastoon niitä rivejä, mitkä ei ollu tässä aineistossa mukana
  # Joten laitetaan varastoon = 0
  $query = "UPDATE tilausrivi SET
            varastoon       = 0
            WHERE yhtio     = '{$yhtio}'
            AND tyyppi      = 'O'
            AND kpl         = 0
            AND varattu    != 0
            AND uusiotunnus = '{$saapumistunnus}'";
  $updres = pupe_query($query);

  // Loopataan rivit tilausrivit-arrayseen
  // koska Pupesoftin tilausrivi voi tulla monella aineiston rivillä
  foreach ($sanoman_kaikki_rivit->ItemEntry as $line) {
    $rivitunnus = (int) array_shift($line->xpath('RefInfo/SourceDocument[@type="order"]/SourceDocumentLineItemNum'));
    $tuoteno    = (string) $line->BuyerItemCode;

    foreach ($line->ItemReserve as $stock_item) {
      $kpl = (float) $stock_item->ItemReserveUnit->AmountActual;
      $varastotunnus = (string) $stock_item->Location->WarehouseCode;

      if (!isset($tilausrivit[$rivitunnus])) {
        $tilausrivit[$rivitunnus] = array(
          'kpl'     => $kpl,
          'tuoteno' => $tuoteno,
          'varastot'=> array()
        );
      }
      else {
        $tilausrivit[$rivitunnus]['kpl'] += $kpl;
      }

      // Paikkakohtainen määrä
      if (!isset($tilausrivit[$rivitunnus]['varastot'][$varastotunnus])) {
        $tilausrivit[$rivitunnus]['varastot'][$varastotunnus] = $kpl;
      }
      else {
        $tilausrivit[$rivitunnus]['varastot'][$varastotunnus] += $kpl;
      }
    }
  }



  foreach ($tilausrivit as $rivitunnus => $data) {
    $tuoteno = $data['tuoteno'];
    $kpl = round($data['kpl']);
    $varastomaarat = $data['varastot'];

    $query = "SELECT *
              FROM tilausrivi
              WHERE yhtio     = '{$yhtio}'
              AND tuoteno     = '{$tuoteno}'
              AND tyyppi      = 'O'
              AND kpl         = 0
              AND tunnus      = '{$rivitunnus}'
              AND uusiotunnus = '{$saapumistunnus}'";
    $tilausrivires = pupe_query($query);

    if (mysql_num_rows($tilausrivires) != 1) {
      $tilausrivit_error[] = array(
        'tuoteno' => $tuoteno,
        'smarten_kpl' => $kpl,
        'pupesoft_kpl' => '',
      );

      pupesoft_log('smarten_inbound_delivery_confirmation', "Tilausriviä ei löydy rivitunnuksella {$rivitunnus} ja tuotenumerolla {$tuoteno}.");
      continue;
    }

    $tilausrivirow = mysql_fetch_assoc($tilausrivires);

    if ($kpl < 1) {
      $tilausrivit_error[] = array(
        'tuoteno' => $tuoteno,
        'smarten_kpl' => $kpl,
        'pupesoft_kpl' => $tilausrivirow['varattu'],
      );

      pupesoft_log('smarten_inbound_delivery_confirmation', "Sanoman kpl oli pienempi kuin 1. Rivitunnus {$rivitunnus}.");
      continue;
    }

    if ($kpl > $tilausrivirow['varattu']) {
      $email_array[] = t("Sanomassa tuotteella {$tuoteno} kappalemäärä oli suurempi kuin tietokannassa. Sanomalla {$kpl}, tietokannassa {$tilausrivirow['varattu']}.");
      pupesoft_log('smarten_inbound_delivery_confirmation', "Sanoman kpl suurempi kuin tietokannassa. Rivitunnus {$rivitunnus}, sanoman kpl {$kpl}, tietokannan varattu {$tilausrivirow['varattu']}.");
    }

    if ($kpl < $tilausrivirow['varattu']) {
      $erotus = ($tilausrivirow['varattu'] - $kpl);
      $email_array[] = t("Sanomassa tuotteella {$tuoteno} kappalemäärä oli pienempi kuin tietokannassa. Saavutettiin {$kpl}, saavuttamatta {$erotus}.");
      pupesoft_log('smarten_inbound_delivery_confirmation', "Tilausrivi {$rivitunnus} splitattiin. Saavutettiin {$kpl}, saavuttamatta {$erotus}.");
    }

    foreach ($varastomaarat as $varastotunnus => $varastomaara) {

      $rivitunnus_varastoon = $rivitunnus;

      # Splitataan rivi
      if ($varastomaara < $tilausrivirow['varattu']) {
        $erotus = ($tilausrivirow['varattu'] - $varastomaara);
        $uusi_id = splittaa_tilausrivi($rivitunnus, $varastomaara);

        if ($uusi_id != 0) {
          # Jätetään alkuperäiselle tilausriville jäljelle jäävä kappalemäärä
          # Halutaan että uusi splitattu rivi menee varastoon
          $query = "UPDATE tilausrivi SET
                    varattu = '{$erotus}'
                    WHERE yhtio     = '{$yhtio}'
                    AND tyyppi      = 'O'
                    AND kpl         = 0
                    AND tuoteno     = '{$tuoteno}'
                    AND tunnus      = '{$rivitunnus}'";
          $updres = pupe_query($query);

          $tilausrivirow['varattu'] = $erotus;
          $rivitunnus_varastoon = $uusi_id;
        }
      }
      elseif (isset($kasitellyt_rivitunnukset[$rivitunnus])) {
        // Tämä rivi on jo menossa varastoon, tarvitaan uusi
        $rivitunnus_varastoon = splittaa_tilausrivi($rivitunnus, $varastomaara);
      }

      $hyllylisa = "";
      if (!empty($varastot[$varastotunnus])) {
        $hyllylisa = "hyllyalue = '".$varastot[$varastotunnus]['alkuhyllyalue']."',
                      hyllynro  = '".$varastot[$varastotunnus]['alkuhyllynro']."',
                      hyllyvali = '0',
                      hyllytaso = '0',";
      }

      # Päivitetään varattu ja tuotepaikka ja kohdistetaan rivi
      $query = "UPDATE tilausrivi SET
                varattu         = '{$varastomaara}',
                {$hyllylisa}
                varastoon       = 1
                WHERE yhtio     = '{$yhtio}'
                AND tyyppi      = 'O'
                AND kpl         = 0
                AND tuoteno     = '{$tuoteno}'
                AND tunnus      = '{$rivitunnus_varastoon}'";
      $updres = pupe_query($query);

      // Tämä rivi on jo menossa varastoon...
      $kasitellyt_rivitunnukset[$rivitunnus_varastoon] = $rivitunnus_varastoon;

      if (mysql_affected_rows() == 1) {
        $tilausrivit_success[] = array(
          'tuoteno' => $tuoteno,
          'smarten_kpl' => $varastomaara,
          'pupesoft_kpl' => $tilausrivirow['varattu'],
        );

        // Varmistetaan, että tuotteella on paikka siinä varastossa, johon se on menossa
        $query = "SELECT
                  hyllyalue,
                  hyllynro,
                  hyllyvali,
                  hyllytaso
                  FROM  tilausrivi
                  WHERE yhtio = '{$yhtio}'
                  AND tunnus = '{$rivitunnus}'";
        $paikkares = pupe_query($query);
        $paikkarow = mysql_fetch_assoc($paikkares);

        lisaa_tuotepaikka($tuoteno, $paikkarow['hyllyalue'], $paikkarow['hyllynro'], $paikkarow['hyllyvali'], $paikkarow['hyllytaso'], "Smarten vastaanotto");
      }
      else {
        $tilausrivit_error[] = array(
          'tuoteno' => $tuoteno,
          'smarten_kpl' => $varastomaara,
          'pupesoft_kpl' => $tilausrivirow['varattu'],
        );

        pupesoft_log('smarten_inbound_delivery_confirmation', "Saapumisen {$saapumisnro} sanoman riviä {$rivitunnus} (tuoteno {$tuoteno}) ei päivitetty.");
      }
    }
  }

  pupesoft_log('smarten_inbound_delivery_confirmation', "Saapumiskuittaus saapumiselta {$saapumisnro} vastaanotettu");

  if (count($tilausrivit_success) > 0) {
    pupesoft_log('smarten_inbound_delivery_confirmation', "Aloitetaan varastoonvienti saapumiselle {$saapumisnro}");
    pupesoft_log('smarten_inbound_delivery_confirmation', "Käsiteltäviä rivejä yhteensä ".(count($tilausrivit) - count($tilausrivit_error))." / ".count($tilausrivit));

    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tila = 'K'
              AND tunnus  = '{$saapumistunnus}'";
    $laskures = pupe_query($query);
    $laskurow = mysql_fetch_assoc($laskures);

    # Setataan parametrit varastoon.incille
    $tullaan_automaattikohdistuksesta = true;
    $toiminto = "kalkyyli";
    $otunnus = $saapumistunnus;
    $tee = "varastoon";

    require "tilauskasittely/varastoon.inc";

    $query = "UPDATE lasku SET
              sisviesti3  = 'kuittaus_saapunut_ulkoisesta_jarjestelmasta'
              WHERE yhtio = '{$yhtio}'
              AND tila    = 'K'
              AND tunnus  = '{$saapumistunnus}'";
    $updres = pupe_query($query);

    // params: filename, liitos, liitostunnus, selite, käyttötarkoitus
    tallenna_liite($full_filepath, 'lasku', $saapumistunnus, 'smarten sanoma', 'XML');

    $email_array[] = "";
    $email_array[] = t("Varastoon vietyjä rivejä").":";
    $email_array[] = t("Tuoteno")." ".t("Varastoon")." ".t("Alkuperäinen kappalemäärä");

    foreach ($tilausrivit_success as $success) {
      $email_array[] = "{$success['tuoteno']} {$success['smarten_kpl']} {$success['pupesoft_kpl']}";
    }
  }

  if (count($tilausrivit_error) > 0) {
    $email_array[] = "";
    $email_array[] = t("Epäselviä/virheellisiä rivejä");
    $email_array[] = t("Tuoteno")." ".t("smarten-kappalemäärä")." ".t("Pupesoft-kappalemäärä");

    foreach ($tilausrivit_error as $error) {
      $email_array[] = "{$error['tuoteno']} {$error['smarten_kpl']} {$error['pupesoft_kpl']}";
    }
  }

  rename($full_filepath, $path."done/".$file);
}

# Lähetetään sähköposti per ajo
$params = array(
  'email' => $email,
  'email_array' => $email_array,
  'log_name' => 'smarten_inbound_delivery_confirmation',
);

smarten_send_email($params);
closedir($handle);
