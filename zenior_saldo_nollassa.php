<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
	die (t("Ajo ainoastaan cronista / komentoriviltä!"));
}

if (!isset($argv[1])) {
	die (t("Anna yhtio parametriksi!"));
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

if ($yhtiorow["epakurantoinnin_myyntihintaleikkuri"] != 'Z') {
	die(t("Tämä toiminto on käytettävissä vain, jos yhtiöparametri epakurantoinnin_myyntihintaleikkuri on 'Z'"));
}

// hae nollasaldoiset zeniorpartsit, tarvitaan tuoteno ja avainsanalle tallennettu alkuperäinen hinta

$query  = "	SELECT
					t.tuoteno,
					a.selitetark        as orig_myyntihinta,
                    MAX(t.myyntihinta)  as varahinta,
 					SUM(p.saldo)        as saldosumma
			FROM tuote                  t
            JOIN tuotteen_avainsanat    a ON (t.yhtio = a.yhtio AND t.tuoteno = a.tuoteno)
			JOIN tuotepaikat            p ON (a.yhtio = p.yhtio AND a.tuoteno = p.tuoteno)
			WHERE
                    t.yhtio = '{$kukarow["yhtio"]}'
			  AND   a.laji  = 'zeniorparts'
			GROUP BY 1, 2
			HAVING saldosumma = 0;";

$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {

    // jos talteenotettu hinta ei ole nollaa isompi, otetaan viimeisin myyntihinta
	$hinta = floatval($row["orig_myyntihinta"]) || $row["varahinta"];
    $selite = t("Epäkuranttimuutos") . ": ".t("Tuote")." {$row["tuoteno"]} ".t("päivitetään kurantiksi");

	$t_query = "UPDATE tuote
				SET
					status            = 'T',
					epakurantti25pvm  = '0000-00-00',
					epakurantti50pvm  = '0000-00-00',
					epakurantti75pvm  = '0000-00-00',
					epakurantti100pvm = '0000-00-00',
					myyntihinta       = {$hinta}
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

    $t_query = "INSERT INTO tapahtuma
                SET
                    yhtio   	= '{$kukarow["yhtio"]}',
                    tuoteno 	= '{$row["tuoteno"]}',
                    laji    	= 'Epäkurantti',
                    kpl     	= '0',
                    hinta   	= 0,
                    kplhinta	= 0,
                    selite  	= '$selite',
                    laatija    	= '{$kukarow["kuka"]}',
                    laadittu 	= now()";

    $t_result = pupe_query($t_query);
}
?>
