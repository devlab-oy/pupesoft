<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut l�hett�v�� yhti�t�!\n");
}

if (trim($argv[2]) == '') {
  die ("Et antanut luettavien tiedostojen polkua!\n");
}

// lis�t��n includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
ini_set("display_errors", 1);

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/smarten/smarten-functions.php";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t�st� skriptist� kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  exit("VIRHE: Admin k�ytt�j� ei l�ydy!\n");
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
  $tilausrivit = $tilausrivit_error = $tilausrivit_success = array();

  pupesoft_log('smarten_inbound_delivery_confirmation', "K�sitell��n sanoma {$file}");

  $xml = simplexml_load_file($full_filepath);

  $saapumisnro = $xml->xpath('Document/DocumentInfo/RefInfo/SourceDocument[@type="order"]/SourceDocumentNum')[0];

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
    pupesoft_log('smarten_inbound_delivery_confirmation', "Kuittausta odottavaa saapumista ei l�ydy saapumisnumerolla {$saapumisnro} sanomassa {$file}");

    $email_array[] = t("Kuittausta odottavaa saapumista ei l�ydy saapumisnumerolla %d", "", $saapumisnro);

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
    pupesoft_log('smarten_inbound_delivery_confirmation', "Sanomassa {$file} ei ollut rivej�");

    $email_array[] = t("Saapumisen %d kuittaussanomassa ei l�ytynyt rivej�", "", $saapumisnro);

    rename($full_filepath, $path."error/".$file);
    continue;
  }

  # Ei haluta vied� varastoon niit� rivej�, mitk� ei ollu t�ss� aineistossa mukana
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
  // koska Pupesoftin tilausrivi voi tulla monella aineiston rivill�
  foreach ($sanoman_kaikki_rivit->ItemEntry as $line) {
    $rivitunnus = (int) $line->xpath('RefInfo/SourceDocument[@type="order"]/SourceDocumentLineItemNum')[0];
    $tuoteno    = (string) $line->BuyerItemCode;

    foreach ($line->ItemReserve as $stock_item) {
      $kpl = (float) $stock_item->ItemReserveUnit->AmountActual;
      $varastotunnus = (string) $stock_item->Location->WarehouseCode;

      if (!isset($tilausrivit[$rivitunnus])) {
        $tilausrivit[$rivitunnus] = array(
          'kpl'     => $kpl,
          'tuoteno' => $tuoteno,
          'varastotunnus' => $varastotunnus,
        );
      }
      else {
        $tilausrivit[$rivitunnus]['kpl'] += $kpl;
      }
    }
  }

  foreach ($tilausrivit as $rivitunnus => $data) {
    $tuoteno = $data['tuoteno'];
    $kpl = round($data['kpl']);
    $varastotunnus = $data['varastotunnus'];

    $uusi_id = 0;

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

       pupesoft_log('smarten_inbound_delivery_confirmation', "Tilausrivi� ei l�ydy rivitunnuksella {$rivitunnus} ja tuotenumerolla {$tuoteno}.");
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
      $email_array[] = t("Sanomassa tuotteella {$tuoteno} kappalem��r� oli suurempi kuin tietokannassa. Sanomalla {$kpl}, tietokannassa {$tilausrivirow['varattu']}.");

      pupesoft_log('smarten_inbound_delivery_confirmation', "Sanoman kpl suurempi kuin tietokannassa. Rivitunnus {$rivitunnus}, sanoman kpl {$kpl}, tietokannan varattu {$tilausrivirow['varattu']}.");
    }

    # splitataan rivi
    if ($kpl < $tilausrivirow['varattu']) {
      $erotus = ($tilausrivirow['varattu'] - $kpl);
      $uusi_id = splittaa_tilausrivi($rivitunnus, $kpl);

      if ($uusi_id != 0) {
        # J�tet��n alkuper�iselle tilausriville j�ljelle j��v� kappalem��r�
        # Halutaan ett� uusi splitattu rivi menee varastoon
        $query = "UPDATE tilausrivi SET
                  varattu   = '{$erotus}'
                  WHERE yhtio     = '{$yhtio}'
                  AND tyyppi      = 'O'
                  AND kpl         = 0
                  AND tuoteno     = '{$tuoteno}'
                  AND tunnus      = '{$rivitunnus}'";
        $updres = pupe_query($query);

        $email_array[] = t("Sanomassa tuotteella {$tuoteno} kappalem��r� oli pienempi kuin tietokannassa. Saavutettiin {$kpl}, saavuttamatta {$erotus}.");

        pupesoft_log('smarten_inbound_delivery_confirmation', "Tilausrivi {$rivitunnus} splitattiin. Uusi k�ytetty tunnus on {$uusi_id}. Saavutettiin {$kpl}, saavuttamatta {$erotus}.");

        $rivitunnus = $uusi_id;
      }
    }

    $hyllylisa = "";
    if (!empty($varastot[$varastotunnus])) {
      $hyllylisa = "hyllyalue = '".$varastot[$varastotunnus]['alkuhyllyalue']."',
                    hyllynro  = '".$varastot[$varastotunnus]['alkuhyllynro']."',
                    hyllyvali = '0',
                    hyllytaso = '0',";
    }

    # P�ivitet��n varattu ja tuotepaikka ja kohdistetaan rivi
    $query = "UPDATE tilausrivi SET
              varattu         = '{$kpl}',
              {$hyllylisa}
              varastoon       = 1
              WHERE yhtio     = '{$yhtio}'
              AND tyyppi      = 'O'
              AND kpl         = 0
              AND tuoteno     = '{$tuoteno}'
              AND tunnus      = '{$rivitunnus}'";
    $updres = pupe_query($query);

    if (mysql_affected_rows() == 1) {
      $tilausrivit_success[] = array(
        'tuoteno' => $tuoteno,
        'smarten_kpl' => $kpl,
        'pupesoft_kpl' => $tilausrivirow['varattu'],
      );

      // Varmistetaan, ett� tuotteella on paikka siin� varastossa, johon se on menossa
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

      lisaa_tuotepaikka($tuoteno, $paikkarow['hyllyalue'], $paikkarow['hyllynro'], $paikkarow['hyllyvali'], $paikkarow['hyllytaso']);
    }
    else {
      $tilausrivit_error[] = array(
        'tuoteno' => $tuoteno,
        'smarten_kpl' => $kpl,
        'pupesoft_kpl' => $tilausrivirow['varattu'],
      );

      pupesoft_log('smarten_inbound_delivery_confirmation', "Saapumisen {$saapumisnro} sanoman rivi� {$rivitunnus} (tuoteno {$tuoteno}) ei p�ivitetty.");
    }
  }

  pupesoft_log('smarten_inbound_delivery_confirmation', "Saapumiskuittaus saapumiselta {$saapumisnro} vastaanotettu");

  if (count($tilausrivit_success) > 0) {
    pupesoft_log('smarten_inbound_delivery_confirmation', "Aloitetaan varastoonvienti saapumiselle {$saapumisnro}");
    pupesoft_log('smarten_inbound_delivery_confirmation', "K�sitelt�vi� rivej� yhteens� ".(count($tilausrivit) - count($tilausrivit_error))." / ".count($tilausrivit));

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
    $tee = "varastoon";

    require "tilauskasittely/varastoon.inc";

    $query = "UPDATE lasku SET
              sisviesti3  = 'kuittaus_saapunut_ulkoisesta_jarjestelmasta'
              WHERE yhtio = '{$yhtio}'
              AND tila    = 'K'
              AND tunnus  = '{$saapumistunnus}'";
    $updres = pupe_query($query);

    // params: filename, liitos, liitostunnus, selite, k�ytt�tarkoitus
    tallenna_liite($full_filepath, 'lasku', $saapumistunnus, 'smarten sanoma', 'XML');

    $email_array[] = "";
    $email_array[] = t("Varastoon vietyj� rivej�").":";
    $email_array[] = t("Tuoteno")." ".t("Varastoon")." ".t("Alkuper�inen kappalem��r�");

    foreach ($tilausrivit_success as $success) {
      $email_array[] = "{$success['tuoteno']} {$success['smarten_kpl']} {$success['pupesoft_kpl']}";
    }
  }

  if (count($tilausrivit_error) > 0) {
    $email_array[] = "";
    $email_array[] = t("Ep�selvi�/virheellisi� rivej�");
    $email_array[] = t("Tuoteno")." ".t("smarten-kappalem��r�")." ".t("Pupesoft-kappalem��r�");

    foreach ($tilausrivit_error as $error) {
      $email_array[] = "{$error['tuoteno']} {$error['smarten_kpl']} {$error['pupesoft_kpl']}";
    }
  }

  rename($full_filepath, $path."done/".$file);
}

# L�hetet��n s�hk�posti per ajo
$params = array(
  'email' => $email,
  'email_array' => $email_array,
  'log_name' => 'smarten_inbound_delivery_confirmation',
);

smarten_send_email($params);
closedir($handle);
