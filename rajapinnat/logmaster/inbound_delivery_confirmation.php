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

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

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

$path = rtrim($path, '/').'/';
$handle = opendir($path);

if ($handle === false) {
  exit;
}

while (false !== ($file = readdir($handle))) {
  $full_filepath = $path.$file;
  $message_type = logmaster_message_type($full_filepath);

  if ($message_type != 'InboundDeliveryConfirmation') {
    continue;
  }

  $xml = simplexml_load_file($full_filepath);

  pupesoft_log('inbound_delivery_confirmation', "K�sitell��n sanoma {$file}");

  $node = $xml->VendPackingSlip;

  $saapumisnro = (int) $node->PurchId;

  $query = "SELECT tunnus
            FROM lasku
            WHERE yhtio     = '{$yhtio}'
            AND tila        = 'K'
            AND vanhatunnus = 0
            AND laskunro    = '{$saapumisnro}'
            AND sisviesti3  = 'ei_vie_varastoon'";
  $selectres = pupe_query($query);
  $selectrow = mysql_fetch_assoc($selectres);

  $saapumistunnus = (int) $selectrow['tunnus'];

  if ($saapumistunnus == 0) {
    pupesoft_log('inbound_delivery_confirmation', "Kuittausta odottavaa saapumista ei l�ydy saapumisnumerolla {$saapumisnro} sanomassa {$file}");

    continue;
  }

  $sanoman_kaikki_rivit = '';
  // Otetaan talteen Lines-elementti sielt� miss� se on
  if (isset($xml->Lines)) {
    $sanoman_kaikki_rivit = $xml->Lines;
  }

  if (isset($node->Lines)) {
    $sanoman_kaikki_rivit = $node->Lines;
  }

  if (empty($sanoman_kaikki_rivit) or !isset($sanoman_kaikki_rivit->Line)) {
    pupesoft_log('inbound_delivery_confirmation', "Sanomassa {$file} ei ollut rivej�");

    continue;
  }

  $tilausrivit = array();

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
  foreach ($sanoman_kaikki_rivit->Line as $key => $line) {
    $rivitunnus = (int) $line->TransId;
    $tuoteno    = (string) $line->ItemNumber;
    $kpl        = (float) $line->ArrivedQuantity;

    if (!isset($tilausrivit[$rivitunnus])) {
      $tilausrivit[$rivitunnus] = array(
        'tuoteno' => $tuoteno,
        'kpl'     => $kpl
      );
    }
    else {
      $tilausrivit[$rivitunnus]['kpl'] += $kpl;
    }
  }

  foreach ($tilausrivit as $rivitunnus => $data) {
    $tuoteno = $data['tuoteno'];
    $kpl     = $data['kpl'];

    # P�ivitet��n varattu ja kohdistetaan rivi
    $query = "UPDATE tilausrivi SET
              varattu         = '{$kpl}',
              varastoon       = 1
              WHERE yhtio     = '{$yhtio}'
              AND tyyppi      = 'O'
              AND kpl         = 0
              AND tuoteno     = '{$tuoteno}'
              AND tunnus      = '{$rivitunnus}'";
    $updres = pupe_query($query);
  }

  if (count($tilausrivit) > 0) {
    pupesoft_log('inbound_delivery_confirmation', "Aloitetaan varastoonvienti saapumiselle {$saapumisnro}");

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
              sisviesti3  = 'ok_vie_varastoon'
              WHERE yhtio = '{$yhtio}'
              AND tila    = 'K'
              AND tunnus  = '{$saapumistunnus}'";
    $updres = pupe_query($query);

    if (!empty($email)) {
      $body = t("K�sitellyn sanoman tiedostonimi: %s", "", $file)."<br><br>\n\n";
      $body .= t("Saapumiselle %d on kuitattu seuraavia tuotteita", "", $saapumisnro).":<br><br>\n\n";
      $body .= t("Tuoteno")." ".t("Kappaleita")."<br>\n";

      foreach ($tilausrivit as $rivitunnus => $data) {
        $body .= "{$data['tuoteno']} {$data['kpl']}<br>\n";
      }

      $params = array(
        'to' => $email,
        'cc' => '',
        'subject' => t("Saapumisen kuittaus")." - {$saapumisnro}",
        'ctype' => 'html',
        'body' => $body,
      );

      if (pupesoft_sahkoposti($params)) {
        pupesoft_log('inbound_delivery_confirmation', "Kuittaus saapumisesta {$saapumisnro} l�hetetty onnistuneesti s�hk�postiin {$email}");
      }
      else {
        pupesoft_log('inbound_delivery_confirmation', "Kuittaus saapumisesta {$saapumisnro} l�hett�minen ep�onnistui s�hk�postiin {$email}");
      }
    }
  }
  else {
    pupesoft_log('inbound_delivery_confirmation', "P�ivitett�vi� tilausrivej� ei ollut sanomalla {$file}");
  }

  pupesoft_log('inbound_delivery_confirmation', "Saapumiskuittaus saapumiselta {$saapumisnro} vastaanotettu");

  rename($full_filepath, $path."done/".$file);
}

closedir($handle);
