<?php

require("salasanat.php");

if (isset($useslave)) {
	$useslave = (int) $useslave;
}
else {
	$useslave = 0;
}

// tarkistetaan onko annettu slave oikea
if ($useslave > 0) {
	// katsotaan onko haluttu useslave setattu, jos ei ole tai se on tyhjää laitetaan ykkönen
	if (!isset($slavedb[$useslave])) {
		$useslave = 1;
	}
	elseif ($slavedb[$useslave] == '') {
		$useslave = 1;
	}

	// katsotaan onko haluttu useslave setattu, jos ei ole tai se on tyhjää laitetaan nolla eli masteri
	if (!isset($slavedb[$useslave])) {
		$useslave = 0;
	}
	elseif ($slavedb[$useslave] == '') {
		$useslave = 0;
	}
}

// Käytetään slave kantaa
if ($useslave > 0) {
	// Tässä tapauksessa käytetään MYÖS master-kantaa koska esim excel-library tarvii master-yhteyden
	$masterlink = mysql_connect($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
	mysql_select_db($dbkanta, $masterlink) or die ("Tietokantaa $dbkanta ei löydy palvelimelta $dbhost! (connect.inc)");
	mysql_set_charset("latin1", $masterlink);
	mysql_query("set group_concat_max_len=1000000", $masterlink);

	$link = mysql_connect($slavedb[$useslave], $slaveuser[$useslave], $slavepass[$useslave]) or die ("Ongelma tietokantapalvelimessa: '$slavedb[$useslave]'");
}
else {
	$link = mysql_connect($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
}

mysql_select_db($dbkanta, $link) or die ("Tietokantaa $dbkanta ei löydy palvelimelta $dbhost! (connect.inc)");
mysql_set_charset("latin1", $link);
mysql_query("set group_concat_max_len=1000000", $link);

// Jos slavea pyydettiin, mutta sitä ei käytetän ja halutiin tämäkin, niin tehdään näin
if (isset($usemastertoo) and $usemastertoo > 0 and !isset($masterlink)) $masterlink = $link;
