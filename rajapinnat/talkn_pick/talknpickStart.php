<?php

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut l�hett�v�� yhti�t�!\n");
}

// lis�t��n includepathiin pupe-root
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))));
ini_set("display_errors", 1);

error_reporting(E_ALL);

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
require "talknpick-functions.php";

// Sallitaan vain yksi instanssi t�st� skriptist� kerrallaan
pupesoft_flock();

$yhtio = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

if (!isset($kukarow)) {
  exit("VIRHE: Admin k�ytt�j� ei l�ydy!\n");
}

// N�yt� yhti�t
list($code, $response) = talknpick_list_companies();

echo "Companies:\n";

foreach ($response as $company) {
  echo "$company[companyID], $company[name]\n";
}

echo "\n\n";
echo "Warehouses:\n";

// N�yt� varastot
list($code, $response) = talknpick_list_warehouses();

foreach ($response as $warehouse) {
  echo "$warehouse[warehouseID], $warehouse[companyID], $warehouse[name]\n";
}

talknpick_delete_all_tasks();
talknpick_delete_all_orders();
talknpick_delete_all_products();
