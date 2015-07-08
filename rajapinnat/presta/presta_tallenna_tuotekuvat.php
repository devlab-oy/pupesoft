<?php

/**
 * Skriptillä haetaan PrestaShopista kaikki tuotekuvat
 * ja ladataan kuvatiedostot annettuun hakemistoon.
 * Kuvat nimetään oletetulla Pupen tuotenumerolla
 * Ajetaan:
 * php presta_tallenna_tuotekuvat.php yhtio presta downloadhakemisto
 */

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

// Kutsutaanko CLI:stä
if (!$php_cli) {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupe_root_polku=dirname(dirname(dirname(__FILE__)));
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
ini_set("display_errors", 0);

require "inc/connect.inc";
require "inc/functions.inc";

$lock_params = array(
  "locktime" => 5400
);

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock($lock_params);

require_once "{$pupe_root_polku}/rajapinnat/presta/presta_products.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_categories.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_customers.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_customer_groups.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_sales_orders.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_product_stocks.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_shops.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_specific_prices.php";

// Laitetaan unlimited execution time
ini_set("max_execution_time", 0);

if (trim($argv[1]) != '') {
  $yhtio = mysql_real_escape_string($argv[1]);
  $yhtiorow = hae_yhtion_parametrit($yhtio);
  $kukarow = hae_kukarow('admin', $yhtio);

  if ($kukarow === null) {
    die ("\n");
  }
}
else {
  die ("Et antanut yhtiötä.\n");
}

$verkkokauppatyyppi = isset($argv[2]) ? trim($argv[2]) : "";

$hakemisto = isset($argv[3]) ? trim($argv[3]) : "";

if ($verkkokauppatyyppi != "presta") {
  die ("Et antanut verkkokaupan tyyppiä.\n");
}

if ($hakemisto == '' or !file_exists($hakemisto)) {
  die ("Et antanut hakemistoa.\n");
}

echo date("d.m.Y @ G:i:s")." - Haetaan tuotekuvia verkkokaupasta hakemistoon $hakemisto.\n";

if (isset($verkkokauppatyyppi) and $verkkokauppatyyppi == "presta") {
  $presta_products = new PrestaProducts($presta_url, $presta_api_key);

  # Haetaan kaikki Prestashopista kaikkien tuotteiden prestaid:t/tuotenumerot
  $all_products = $presta_products->all_skus();

  foreach ($all_products as $product_id => $pupe_product_code) {
    # Haetaan jokaisen tuotteen tiedot
    $url = $presta_url ."/api/images/products/$product_id";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERPWD, $presta_api_key.':');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Expect:' ));

    $response = curl_exec($ch);
    curl_close($ch);

    # Parsitaan xml:stä array
    $p = xml_parser_create();
    xml_parse_into_struct($p, $response, $product_data, $index);

    # Käydään kaikki tuotetiedot läpi
    foreach ($product_data as $value) {
      $kuvaurli = '';
      # Ainakin testikaupassa kuvaurlit löytyi näin tuotedatan alta
      if ($value['tag'] == "DECLINATION") {
        $kuvaurli = $value['attributes']['XLINK:HREF'];
      }
      # Kuva nimetään tuotekoodilla, lisätään perään randomia jos tunnuksella löytyy jo kuva
      if (!empty($kuvaurli)) {
        $filename = "{$hakemisto}/{$pupe_product_code}.jpg";
        if (file_exists($filename)) {
          $filename = "{$hakemisto}/{$pupe_product_code}%".md5(uniqid(mt_rand(), true)).".jpg";
        }
        # Imaistaan kuva
        exec("curl -s -o '{$filename}' -u {$presta_api_key}: $kuvaurli");
      }
    }
  }
}
else {
  die ("Ei näin");
}

