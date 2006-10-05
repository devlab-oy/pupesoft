<?php

if (!isset($palvelin)) {
	require("salasanat.php");
}

if (isset($useslave)) {

	$useslave = (int) $useslave;

	if ($useslave != 0) {

		if (!isset($slavedb[$useslave])) {
			$useslave = 1;
		}
		elseif ($slavedb[$useslave] == '') {
			$useslave = 1;
		}

		if (!isset($slavedb[$useslave])) {
			$useslave = 0;
		}
		elseif ($slavedb[$useslave] == '') {
			$useslave = 0;
		}

	}

	if ($useslave > 0) {
		$link = mysql_connect ($slavedb[$useslave], $slaveuser[$useslave], $slavepass[$useslave]) or die ("Ongelma tietokantapalvelimessa: '$slavedb[$useslave]'");
		mysql_select_db ($dbkanta) or die ("Tietokantaa $dbkanta löydy palvelimelta!");
		//echo "Käytössä slave[$useslave]<br>";
	}

}

if (!isset($link)) {
	$link = mysql_connect ($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
	mysql_select_db ($dbkanta) or die ("Tietokantaa $dbkanta löydy palvelimelta!");
	//echo "Käytössä master<br>";
}

?>