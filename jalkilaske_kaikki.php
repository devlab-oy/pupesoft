#!/usr/bin/php
<?php

if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

$paiva = "2006-10-01";						// mistä eteenpäin lasketaan
$kukarow["yhtio"] = "artr";					// mikä lafka
$kukarow['kuka'] = "mutantti";				// kuka korjaa
chdir("/Users/joni/Sites/devlab/pupesoft"); // pupedirikka

require ("inc/connect.inc");
require ("inc/functions.inc");

$query    = "SELECT * FROM yhtio WHERE yhtio = '$kukarow[yhtio]'";
$yhtiores = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($yhtiores) == 1) {
	$yhtiorow = mysql_fetch_array($yhtiores);

	// haetaan yhtiön parametrit
	$query = "	SELECT *
				FROM yhtion_parametrit
				WHERE yhtio = '$yhtiorow[yhtio]'";
	$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

	if (mysql_num_rows($result) == 1) {
		$yhtion_parametritrow = mysql_fetch_array($result);
		// lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
		foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
			$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
		}
	}
}
else {
	die;
}

$query = "	SELECT * 
			FROM tuote 
			WHERE yhtio = '$kukarow[yhtio]' and 
			epakurantti25pvm = '0000-00-00' and 
			epakurantti50pvm = '0000-00-00' and 
			epakurantti75pvm = '0000-00-00' and 
			epakurantti100pvm = '0000-00-00' and
			ei_saldoa = ''";
$tuores = mysql_query($query) or pupe_error($query);

$tuoteyht = mysql_num_rows($tuores);
$tuotenow = 0;
$laskettu = 0;

echo "\n";
echo "Jälkilaske kaikki tulot\n";
echo "-----------------------\n";

while ($tuote = mysql_fetch_array($tuores)) {

	$query = "	SELECT * 
				FROM tapahtuma 
				WHERE yhtio = '$kukarow[yhtio]' and 
				laji = 'tulo' and 
				tuoteno = '$tuote[tuoteno]' and 
				laadittu >= '$paiva' 
				ORDER BY laadittu 
				LIMIT 1";
	$tapres = mysql_query($query) or pupe_error($query);

	if ($tapahtuma = mysql_fetch_array($tapres)) {

		$query = "SELECT * FROM tilausrivi WHERE tunnus = '$tapahtuma[rivitunnus]'";
		$tilres = mysql_query($query) or pupe_error($query);

		if ($tilausrivi = mysql_fetch_array($tilres)) {
			jalkilaskentafunktio($tilausrivi["tuoteno"], $tilausrivi["laskutettuaika"], $tapahtuma["kplhinta"], $tilausrivi["tunnus"]);
			$laskettu++;
		}

	}

	$tuotenow++;
	echo " $tuotenow/$tuoteyht ($laskettu jälkilaskettu)\r";

}

echo "\n";

?>