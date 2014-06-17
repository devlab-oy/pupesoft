<?php

/*
 * HOW TO CLI:
 * php jalkitoimitus_cron.php yhtio 139 140 141
 */

if (php_sapi_name() != 'cli') {
    die();
}

// otetaan includepath aina rootista
$pupe_root_polku = dirname(dirname(__FILE__));
ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . $pupe_root_polku . PATH_SEPARATOR . "/usr/share/pear");
//error_reporting(E_WARNING);
ini_set("display_errors", 0);

// otetaan tietokantayhteys ja funkkarit
require("inc/connect.inc");
require("inc/functions.inc");

$yhtio = $argv[1];
$varastot = array_slice($argv, 2);

if (empty($yhtio) or empty($varastot)) {
    echo "\nUsage: php " . basename($argv[0]) . " yhtio varasto varasto\n\n";
    die();
}

$oikeurow = array(
    'paivitys' => 1
);
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow = hae_kukarow('admin', $yhtio);

//poimitaan JT-rivit
jt_toimita('', '', $varastot, array(), array(), 'tosi_automaaginen', '');

//toimitettaan poimitut JT-rivit
jt_toimita("", "", "", "", "", "dummy", "TOIMITA", '', '', "");