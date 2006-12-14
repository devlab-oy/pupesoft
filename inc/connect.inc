<?php

require("salasanat.php");

if(isset($useslave)) {
	$useslave = (int) $useslave;
}
else {
	$useslave = 0;
}

// tarkistetaan onko annettu slave oikea
if ($useslave > 0) {
	// katsotaan onko haluttu useslave setattu, jos ei ole tai se on tyhjהה laitetaan ykkצnen
	if (!isset($slavedb[$useslave])) {
		$useslave = 1;
	}
	elseif ($slavedb[$useslave] == '') {
		$useslave = 1;
	}

	// katsotaan onko haluttu useslave setattu, jos ei ole tai se on tyhjהה laitetaan nolla eli masteri
	if (!isset($slavedb[$useslave])) {
		$useslave = 0;
	}
	elseif ($slavedb[$useslave] == '') {
		$useslave = 0;
	}
}

if ($useslave > 0) {
	$link = mysql_connect($slavedb[$useslave], $slaveuser[$useslave], $slavepass[$useslave]) or die ("Ongelma tietokantapalvelimessa: '$slavedb[$useslave]'");
}
else {
	$link = mysql_connect($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
}

mysql_select_db($dbkanta) or die ("Tietokantaa $dbkanta lצydy palvelimelta (connect.inc)!");

?>