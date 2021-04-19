<?php

function salaa ($jono, $arg, $kayttoavain) {
	$palautus = $jono;
	//Alustetaan kryptaus k‰yttˆavaimella  
	$kayttoavain = pack("H*",$kayttoavain);
	$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
	$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), pack('H*','0000000000000000'));
	mcrypt_generic_init($td, $kayttoavain, $iv);

	//Jaetaan 8 merkin lohkoiksi ja sijoitetaan taulukkoon
	$k=0;

	while(strlen($jono) > 0) {
		$data[$k] = substr($jono,0,8);
		$jono = substr($jono,8);
		$k++;
		//echo "$jono\n";
	}

	//Jos viimeinen lohko j‰‰ vajaaksi t‰ytet‰‰n se bin‰‰ri nollilla
	$k--;
	if (strlen($data[$k]) != 8) $data[$k] = str_pad($data[$k], 8, "\0");

	//Salataan lohko, XOR:taan salattu lohko seuraavan lohkon kanssa ja 
	//salataan se, ja XOR:taan seuraavan lohkon kanssa jne.

	$text = $data[0];
	$text = mcrypt_generic($td, $text);

	for($i=1; $i<=$k; $i++){
		$ortext=$text ^ $data[$i];
		$text = mcrypt_generic($td, $ortext);
	}

	// Muutetaan salattu teksti Heksoiksi
	$tark = unpack("H*","$text");

	// Tulostellaan arvot
	// Tehd‰‰n tarvittavat temput tietyille tiedostotyypeille
	if (($arg == "ESIa") or ($arg == "VARa")) {
		if($arg == "VARa"){
			$palautus = ">>VAR" . $palautus;
		}
		$palautus .= $tark[1];
		return $palautus;
	}

	if (($arg == "ESIp") or ($arg == "PTE")) {
		$tarkiste =  $data[18];
		$tarkiste .= $data[19];
		echo "Tarkiste: $tarkiste<br>";
		if ($tark[1] == $tarkiste){
			return ""; // ok!
		}
		else {
			return "Tarkisteet eiv‰t t‰sm‰‰!";
		}
	}
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

}
