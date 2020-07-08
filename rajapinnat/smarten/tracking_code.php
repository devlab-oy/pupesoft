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

// lis‰t‰‰n includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
ini_set("display_errors", 1);

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/smarten/smarten-functions.php";
require "rajapinnat/woo/woo-functions.php";
require "rajapinnat/mycashflow/mycf_toimita_tilaus.php";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  die ("K‰ytt‰j‰‰ ei admin lˆytynyt!\n");
}

$path = trim($argv[2]);
$path = rtrim($path, '/').'/';

$error_email = trim($argv[3]);
$email_array = array();

$_magento_kaytossa = (!empty($magento_api_tt_url) and !empty($magento_api_tt_usr) and !empty($magento_api_tt_pas));

$handle = opendir($path);

if ($handle === false) {
  die ("Hakemistoa {$path} ei lˆydy!\n");
}

while (false !== ($file = readdir($handle))) {
  $full_filepath = $path.$file;
  list($message_type, $message_subtype) = smarten_message_type($full_filepath);

  if ($message_type != 'invoice') {
    continue;
  }

  $xml = simplexml_load_file($full_filepath);

  pupesoft_log('smarten_tracking_code', "K‰sitell‰‰n sanoma {$file}");

  $otunnus = (int) array_shift($xml->xpath('Document/DocumentInfo/RefInfo/SourceDocument[@type="desorder"]/SourceDocumentNum'));

  $tracking_code = "";

  $parcel_ids = $xml->xpath('Document/DocumentInfo/RefInfo/SourceDocument[@type="ParcelID"]');
  $seurantakoodit = array();
  
  foreach ($parcel_ids as $parcel_id) {
    $seurantakoodit[$otunnus][] = trim((string) $parcel_id->SourceDocumentNum);
  }

  // Lˆytyikˆ rahtikirjat?
  $rakir_loytyi = TRUE;

  foreach ($seurantakoodit as $tilausnumero => $koodit) {
    $koodit        = array_unique($koodit);
    $seurantakoodi = implode(' ', $koodit);

    // Tsekataan, ett‰ koodi ei ole viel‰ Pupessa
    $query = "SELECT *
              FROM rahtikirjat
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND otsikkonro = '{$tilausnumero}'
              AND rahtikirjanro like '%$seurantakoodi%'";
    $tsekres = pupe_query($query);

    if (mysql_num_rows($tsekres)) {
      pupesoft_log('smarten_tracking_code', "Seurantakoodi lˆytyi jo Pupesta {$tilausnumero}/{$seurantakoodi}");
    }
    else {
    
      $query = "UPDATE rahtikirjat SET
                rahtikirjanro  = trim(concat(ifnull(rahtikirjanro, ''), ' ', '{$seurantakoodi}')),
                tulostettu     = now()
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND tulostettu = '0000-00-00 00:00:00'
                AND otsikkonro = '{$tilausnumero}'";
      pupe_query($query);

      if (mysql_affected_rows() == 0) {
        pupesoft_log('smarten_tracking_code', "Ei lˆydetty rahtikirjaa tilaukselle {$tilausnumero}");
        $rakir_loytyi = FALSE;
        continue;
      }

      $query = "SELECT SUM(kilot) kilotyht
                FROM rahtikirjat
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND otsikkonro = '{$tilausnumero}'";
      $kilotres = pupe_query($query);
      $kilotrow = mysql_fetch_assoc($kilotres);

      $params = array(
        'otunnukset' => $tilausnumero,
        'kilotyht' => $kilotrow['kilotyht']
      );

      paivita_rahtikirjat_tulostetuksi_ja_toimitetuksi($params);

      pupesoft_log('smarten_tracking_code', "Tilauksen {$tilausnumero} seurantakoodisanoma k‰sitelty");

      // Merkaatan woo-commerce tilaukset toimitetuiksi kauppaan
      $woo_params = array(
        "pupesoft_tunnukset" => array($tilausnumero),
        "tracking_code" => $seurantakoodi,
      );

      woo_commerce_toimita_tilaus($woo_params);

      // Merkaatan MyCashflow tilaukset toimitetuiksi kauppaan
      $mycf_params = array(
        "pupesoft_tunnukset" => array($tilausnumero),
        "tracking_code" => $seurantakoodi,
      );

      mycf_toimita_tilaus($mycf_params);

      // Jos Magento on k‰ytˆss‰, merkataan tilaus toimitetuksi Magentoon kun rahtikirja tulostetaan
      if ($_magento_kaytossa) {

        $query = "SELECT toimitustapa
                  FROM rahtikirjat
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND otsikkonro = '{$tilausnumero}'";
        $chk_res = pupe_query($query);

        if (mysql_num_rows($chk_res) > 0) {
          $chk_row = mysql_fetch_assoc($chk_res);

          $query = "SELECT *
                    FROM toimitustapa
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND selite  = '{$chk_row['toimitustapa']}'";
          $toitares = pupe_query($query);
          $toitarow = mysql_fetch_assoc($toitares);

          $query = "SELECT asiakkaan_tilausnumero, tunnus
                    FROM lasku
                    WHERE yhtio                 = '{$kukarow['yhtio']}'
                    AND tunnus                  = '{$tilausnumero}'
                    AND ohjelma_moduli          = 'MAGENTO'
                    AND asiakkaan_tilausnumero  != ''
                    AND laatija                 = 'Magento'";
          $mageres = pupe_query($query);

          while ($magerow = mysql_fetch_assoc($mageres)) {

            pupesoft_log('smarten_tracking_code', "P‰ivitet‰‰n tilaus {$magerow["tunnus"]} toimitetuksi Magentoon");

            $magento_api_met = $toitarow['virallinen_selite'] != '' ? $toitarow['virallinen_selite'] : $toitarow['selite'];
            $magento_api_rak = $seurantakoodi;
            $magento_api_ord = $magerow["asiakkaan_tilausnumero"];
            $magento_api_laskutunnus = $magerow["tunnus"];

            require "magento_toimita_tilaus.php";
          }
        }
      }
    }
  }
}

closedir($handle);
