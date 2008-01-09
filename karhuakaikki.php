<?php

if (isset($argv[1]) and trim($argv[1]) != '') {

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

	// otetaan tietokanta connect
	require ("inc/connect.inc");
	require ("inc/functions.inc");
	
	$query    = "select * from kuka where kuka='$argv[1]' limit 1";
	$kukares = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($kukares) == 0) die("Karhuajaa ei löyry!\n$query\n");
	$kukarow = mysql_fetch_array($kukares);
	
	$query    = "select * from yhtio where yhtio='$kukarow[yhtio]'";
	$yhtiores = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($yhtiores) == 0) die("Frimaa ei löyry!\n");
	$yhtiorow = mysql_fetch_array($yhtiores);

	$query = "	SELECT *
				FROM yhtion_parametrit
				WHERE yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

	if (mysql_num_rows($result) == 1) {
		$yhtion_parametritrow = mysql_fetch_array($result);
				
		// lisätään kaikki yhtiorow arrayseen
		foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
			$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
		}
	}

	$yhteyshenkilo = $kukarow["tunnus"];
	$karhuakaikki = "JOO";
	$tee = "ALOITAKARHUAMINEN";
	
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)));
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)."/inc"));
	chdir("myyntires");
	$outi = require("myyntires/karhu.php");
	
	echo strip_tags(str_replace("<br>", "\n", $outi));
	
}
else {
	echo "Tätä ei saa ajaa kuin komentoriviltä!<br>\n";
}


require("inc/footer.inc");

?>
