<?php

// Haetaan siirtoavain tietokannasta

function uusikayttoavain ($tunnus, $salattu); {

	$query = "SELECT siirtoavain FROM yriti WHERE tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($result) == 1) {
		return "Siirtoavain, $tunnus, ei löytynyt tietokannasta ";
		exit;
	}
	$resultrow = mysql_fetch_array($result);

	$tulos = tarkistapariteetti($tulos);
	//echo "\n$tulos";

//alustetaan salaus

	$tulos = pack('H*',$tulos);
	$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
	$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), pack('H*','0000000000000000'));
	mcrypt_generic_init($td, $tulos, $iv);

	$tulos = mdecrypt_generic($td, pack('H*',$salattu));
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

	$tulos=unpack('H*',$tulos);

	//lisäys tietokantaan

	$query = "UPDATE yriti SET kayttoavain='$tulos[1]', kasukupolvi=if(kasukupolvi + 1 = 10,1,kasukupolvi+1) WHERE tunnus = '$tunnus'";
	$xres = mysql_query($query) or pupe_error($query);
	return "";
}
