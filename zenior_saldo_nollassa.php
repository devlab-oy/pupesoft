<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
	die ("Ajo ainoastaan cronista / komentoriviltä!");
}

if (!isset($argv[1])) {
	die ("Anna yhtio parametriksi!");
}

$pupe_root_polku = dirname(__FILE__);

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
ini_set("display_errors", 0);

// Otetaan tietokanta connect
require("inc/connect.inc");
require("inc/functions.inc");

// Tehdään oletukset
$kukarow['yhtio'] = $argv[1];
$kukarow['kuka'] = "crond";
$yhtiorow = hae_yhtion_parametrit($argv[1]);

if ($yhtiorow["epakurantoinnin_erityiskasittely"] != 'Z') {
	die("Tämä toiminto on käytettävissä vain, jos yhtiöparametri epakurantoinnin_erityiskasittely on 'Z'");
}

// hae nollasaldoiset zeniorpartsit, tarvitaan tuoteno ja alkuperäinen hinta

$query  = "	SELECT
					a.tuoteno,
					a.selitetark as orig_myyntihinta,
 					sum(p.saldo) as saldosumma
			FROM tuotteen_avainsanat a
			JOIN tuotepaikat p ON (a.yhtio = p.yhtio AND a.tuoteno = p.tuoteno)
			WHERE a.yhtio = '{$kukarow["yhtio"]}'
				AND laji = 'zeniorparts'
			GROUP BY 1, 2
			HAVING saldosumma = 0;";

$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {

	$row["orig_myyntihinta"] = floatval($row["orig_myyntihinta"]) + 0;


	$t_query = "UPDATE tuote
				SET
					status            = 'T',
					epakurantti25pvm  = '0000-00-00',
					epakurantti50pvm  = '0000-00-00',
					epakurantti75pvm  = '0000-00-00',
					epakurantti100pvm = '0000-00-00',
					myyntihinta       = {$row["orig_myyntihinta"]}
				WHERE
					yhtio = '{$kukarow["yhtio"]}'
					AND tuoteno = '{$row["tuoteno"]}';";
	$t_result = pupe_query($t_query);

	$t_query = "DELETE FROM tuotteen_avainsanat
				WHERE
					yhtio = '{$kukarow["yhtio"]}'
					AND laji = 'zeniorparts'
					AND tuoteno = '{$row["tuoteno"]}';";
	$t_result = pupe_query($t_query);

}
?>