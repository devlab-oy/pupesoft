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
    die;
}

$yhtiorow = hae_yhtion_parametrit($yhtio);

// Haetaan käyttäjän tiedot
$query = "	SELECT *
            FROM kuka
            WHERE yhtio = '{$yhtio}'
            AND kuka = 'admin'";
$result = pupe_query($query);

if (mysql_num_rows($result) == 0) {
    die("User admin not found");
}

// Adminin oletus
$kukarow = mysql_fetch_assoc($result);


jt_toimita('', '', $varastot, array(), array(), 'tosi_automaaginen', '', '', '', '', '', 'j');
