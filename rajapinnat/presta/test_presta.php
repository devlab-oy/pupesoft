<?php

ini_set("memory_limit", "5G");
ini_set("max_execution_time", 0);
//86400 = 1day
set_time_limit(86400);

$compression = FALSE;

require "../../inc/parametrit.inc";
require_once "rajapinnat/presta/presta_products.php";
require_once "rajapinnat/presta/presta_categories.php";
require_once "rajapinnat/presta/presta_customers.php";
require_once "rajapinnat/presta/presta_customer_groups.php";
require_once "rajapinnat/presta/presta_sales_orders.php";
require_once 'rajapinnat/presta/presta_product_stocks.php';
require_once 'rajapinnat/presta/presta_shops.php';
require_once 'rajapinnat/presta/presta_specific_prices.php';
require_once 'rajapinnat/presta/presta_addresses.php';
require_once 'rajapinnat/presta/presta_functions.php';

if (!isset($action)) {
  $action = '';
}
if (!isset($synkronointi_tyyppi)) {
  $synkronointi_tyyppi = '';
}
if (!isset($presta_url)) {
  die('Presta url puuttuu');
}
if (!isset($presta_api_key)) {
  die('Presta api key puuttuu');
}
if (!isset($presta_edi_folderpath)) {
  die('Presta edi folder path puuttuu');
}
if (!isset($yhtiorow)) {
  die('Yhtiorow puuttuu');
}
if (!isset($presta_home_category_id)) {
  $presta_home_category_id = 2;
}

$request = array(
  'action'              => $action,
  'synkronointi_tyyppi' => $synkronointi_tyyppi,
);

$request['synkronointi_tyypit'] = array(
  'kaikki'        => t('Kaikki'),
  'kategoriat'    => t('Kategoriat'),
  'tuotteet'      => t('Tuotteet ja tuotekuvat'),
  'asiakasryhmat' => t('Asiakasryhmät'),
  'asiakkaat'     => t('Asiakkaat'),
  'asiakashinnat' => t('Asiakashinnat'),
  'tilaukset'     => t('Tilauksien haku'),
);

if ($request['action'] == 'sync') {
  echo_kayttoliittyma($request);
  echo "<br/>";

  $synkronoi = array();

  if ($request['synkronointi_tyyppi'] == 'kaikki') {
    foreach ($request['synkronointi_tyypit'] as $k => $s) {
      if ($k != 'kaikki') {
        $synkronoi[] = $k;
      }
    }
  }
  else {
    $synkronoi[] = $request['synkronointi_tyyppi'];
  }

  $ok = true;
  if ($ok and in_array('kategoriat', $synkronoi)) {
    $kategoriat = hae_kategoriat();
    $presta_categories = new PrestaCategories($presta_url, $presta_api_key, $presta_home_category_id);
    $ok = $presta_categories->sync_categories($kategoriat);
  }

  if ($ok and in_array('tuotteet', $synkronoi)) {
    $tuotteet = hae_tuotteet();

    $presta_products = new PrestaProducts($presta_url, $presta_api_key, $presta_home_category_id);
    if (isset($presta_dynaamiset_tuoteparametrit) and count($presta_dynaamiset_tuoteparametrit) > 0) {
      $presta_products->set_dynamic_fields($presta_dynaamiset_tuoteparametrit);
    }

    if (isset($presta_ohita_tuoteparametrit) and count($presta_ohita_tuoteparametrit) > 0) {
      $presta_products->set_removable_fields($presta_ohita_tuoteparametrit);
    }
    if (isset($presta_ohita_tuotekuvat) and !empty($presta_ohita_tuotekuvat)) {
      $presta_products->set_image_sync($presta_ohita_tuotekuvat);
    }
    if (isset($presta_ohita_kategoriat) and !empty($presta_ohita_kategoriat)) {
      $presta_products->set_category_sync($presta_ohita_kategoriat);
    }
    $ok = $presta_products->sync_products($tuotteet);
  }

  if ($ok and in_array('asiakasryhmat', $synkronoi)) {
    $groups = hae_asiakasryhmat();
    $presta_customer_groups = new PrestaCustomerGroups($presta_url, $presta_api_key);
    $ok = $presta_customer_groups->sync_groups($groups);
  }

  if ($ok and in_array('asiakkaat', $synkronoi)) {
    $asiakkaat = hae_asiakkaat1();
    $presta_customer = new PrestaCustomers($presta_url, $presta_api_key);
    $ok = $presta_customer->sync_customers($asiakkaat);
  }

  if ($ok and in_array('asiakashinnat', $synkronoi)) {
    $hinnat = hae_asiakashinnat();
    $presta_prices = new PrestaSpecificPrices($presta_url, $presta_api_key);
    $presta_prices->sync_prices($hinnat);
  }

  if ($ok and in_array('tilaukset', $synkronoi)) {
    $presta_orders = new PrestaSalesOrders($presta_url, $presta_api_key);
    $presta_orders->set_edi_filepath($presta_edi_folderpath);
    $presta_orders->set_yhtiorow($yhtiorow);
    $presta_orders->transfer_orders_to_pupesoft();
  }
}
else {
  echo_kayttoliittyma($request);
}

require 'inc/footer.inc';

function echo_kayttoliittyma($request) {
  global $kukarow, $yhtiorow;

  echo "<form action='' method='POST'>";
  echo "<input type='hidden' name='action' value='sync' />";
  echo "<table>";

  echo "<tr>";
  echo "<th>" . t('Synkronoi') . "</th>";
  echo "<td>";
  echo "<select name='synkronointi_tyyppi'>";
  foreach ($request['synkronointi_tyypit'] as $synkronointi_tyyppi => $selitys) {
    $sel = "";
    if ($request['synkronointi_tyyppi'] == $synkronointi_tyyppi) {
      $sel = "SELECTED";
    }
    echo "<option value='{$synkronointi_tyyppi}' {$sel}>{$selitys}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<input type='submit' value='" . t('Lähetä') . "' />";
  echo "</form>";
}
