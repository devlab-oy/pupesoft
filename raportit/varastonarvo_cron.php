<?php

	// Kutsutaanko CLI:st�
	if (php_sapi_name() != 'cli') {
		die ("T�t� scripti� voi ajaa vain komentorivilt�!");
	}

	require_once("../inc/functions.inc");
	require_once("../inc/connect.inc");

	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
	error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	ini_set("display_errors", 0);

	if ($argv[1] == '') {
		die ("Yhti� on pakollinen tieto!\n");
	}

	if ($argv[0] == 'varastonarvo_cron.php' and $argv[1] != '') {

		$kukarow['yhtio'] = mysql_real_escape_string($argv[1]);

		$query    = "SELECT * from yhtio where yhtio = '$kukarow[yhtio]'";
		$yhtiores = mysql_query($query) or die($query);

		if (mysql_num_rows($yhtiores)==1) {
			$yhtiorow = mysql_fetch_array($yhtiores);
		}
		else {
			die ("Yhti� $kukarow[yhtio] ei l�ydy!");
		}

		$query = "	SELECT group_concat(distinct tunnus order by tunnus) varastot
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'";
		$varastores = mysql_query($query) or die ("Varastonhaussa tapahtui virhe!\n".mysql_error()."\n");
		$varastorow = mysql_fetch_array($varastores);

		if ($varastorow["varastot"] != "") {
			exec("php varastonarvo-super.php $kukarow[yhtio] $varastorow[varastot] laskut@arwidson.fi");
		}
		else {
			die ("Yht��n varastoa ei l�ytynyt!\n");
		}
	}

?>