<?php

function ekakayttoavain ($tunnus) {

	$query = "SELECT siirtoavain FROM yriti WHERE tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);	
	if (mysql_num_rows($result) != 1) {
		return "Siirtoavain, $tunnus, ei löytynyt tietokannasta ";
		exit;
	}
	$resultrow = mysql_fetch_array($result);


//Alustetaan salaus

	$tulos = $resultrow['siirtoavain'];
	//echo "\n$tulos";

	$tulos = pack('H*',$tulos);
	$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
	$iv = pack('H*','0000000000000000');
	mcrypt_generic_init($td, $tulos, $iv);
	$tulos = mdecrypt_generic($td, pack('H*','0000000000000000'));
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

	$tulos=unpack('H*',$tulos);
	$kayttoavain = tarkistapariteetti($tulos[1]);

//Lisätään käyttöavain ja sukupolvi tietokantaan
//echo "\nlopullinen tulos '$tulos[1]'\n";

	$query = "UPDATE yriti SET kayttoavain='$kayttoavain', kasukupolvi=0 WHERE tunnus = '$tunnus'";
	$xres = mysql_query($query) or pupe_error($query);
}

// Haetaan siirtoavain tietokannasta

function uusikayttoavain ($tunnus, $salattu) {

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
	$iv = pack('H*','0000000000000000');
	mcrypt_generic_init($td, $tulos, $iv);

	$tulos = mdecrypt_generic($td, pack('H*',$salattu));
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

	$tulos=unpack('H*',$tulos);

	//lisäys tietokantaan

	$query = "UPDATE yriti SET kayttoavain='$tulos[1]', kasukupolvi=if(kasukupolvi + 1 = 10,0,kasukupolvi+1) WHERE tunnus = '$tunnus'";
	$xres = mysql_query($query) or pupe_error($query);
	return "";
}

function salaa ($jono, $arg, $kayttoavain) {
	$palautus = $jono;
	//Alustetaan kryptaus käyttöavaimella  
	$kayttoavain = pack("H*",$kayttoavain);
	$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
	//$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), pack('H*','0000000000000000'));
	$iv=pack('H*','0000000000000000');
	mcrypt_generic_init($td, $kayttoavain, $iv);

	//Jaetaan 8 merkin lohkoiksi ja sijoitetaan taulukkoon
	$k=0;

	while(strlen($jono) > 0) {
		$data[$k] = substr($jono,0,8);
		$jono = substr($jono,8);
		$k++;
		//echo "$jono\n";
	}

	//Jos viimeinen lohko jää vajaaksi täytetään se binääri nollilla
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
	// Tehdään tarvittavat temput tietyille tiedostotyypeille
	if (($arg == "ESIa") or ($arg == "VARa")) {
		if($arg == "VARa"){
			$palautus = ">>VAR" . $palautus;
		}
		$palautus .= strtoupper($tark[1]);
		return $palautus;
	}

	if (($arg == "ESIp") or ($arg == "PTE")) {
		$tarkiste =  $data[18];
		$tarkiste .= $data[19];
		echo "Tarkiste: $tarkiste<br>";
		if (strtoupper($tark[1]) == strtoupper($tarkiste)){
			return ""; // ok!
		}
		else {
			return "Tarkisteet eivät täsmää!";
		}
	}
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

}

function tarkistapariteetti($jono) {

// Jaetaan luku kahden tavun lohkoihin ja sijoitetaan lohkot taulukkoon

	$k=0;

	while(strlen($jono) > 0) {
		$data[$k] = substr($jono,0,2);
		$jono = substr($jono,2);
		$k++;
		//echo "$jono\n";
	}

	$jono="";
	//echo "Jonon koko on " . sizeof($data) . "\n";

	// Tutkitaan lohko kerrallaan lohkon pariteetit
	for($j = 0; $j < 8; $j++) {
		$i = 0;
		$apu = $data[$j];
		//echo "$j Tarkistettava $apu -->";
		// Muutetaan lohko oikeaan muotoon
		$apu = hexdec($apu);
		$apu = decbin($apu);
		//echo " $apu";
		// Lasketaan ykkösten määrä
		$i = 0;
		for($m = 0; $m < 8; $m++) {
			if ($apu[$m] == '1') $i++;
		}

		// Jos ykkösiä parillinen määrä muutetaan viimeistä merkkiä
		if(!($i % 2)){
			//echo " on parillinen ja koko on ". strlen($apu);
			$vmerkki = strlen($apu) - 1;
			// Jos viimeinen on ykkönen niin muutetaan se nollaksi
			if($apu[$vmerkki] == 1) {
				$apu[$vmerkki] = 0;
			}
			// Jos viimeinen on nolla muutetaan se ykköseksi
			else {
				$apu[$vmerkki] = 1;
			}
		}

		// Muutetaan lohko oikeaan muotoon
		//echo " lopullinen on $apu";
		$apu = bindec($apu);
		$apu = dechex($apu);
		if (strlen($apu) == 1) $apu = "0" . $apu;
		$data[$j] = $apu;
		//echo " --> $apu\n";;
	}

	// Yhdistetään lohkot takaisin luvuksi
	for($i = 0; $i <= sizeof($data); $i++) {
		$jono .= $data[$i];
	}
	//echo "Lopullinen tulos $jono\n";
	//Palautetaan luku
	return $jono;
}

function siirtoavain($osa1, $osa2, $tarkiste, $suku, $tunnus) {

	$osa1 = strtolower(str_replace(' ','',$osa1));
	$osa2 = strtolower(str_replace(' ','',$osa2));
	$tarkiste = strtolower(str_replace(' ','',$tarkiste));

	$pa1 = tarkistapariteetti($osa1);
	$pa2 = tarkistapariteetti($osa2);

	if(($pa1 != $osa1) or ($pa2 != $osa2)){
		return "Pariteetin tarkastus ei mennyt läpi. Pariteetti asetettu väärin!";
		exit;
	}

	// xor on vähän vaikea?
	$tulos = '';
	for($i = 0; $i < 8; $i++) {

		$pala1 = hexdec(substr($pa1, 2*$i, 2));
		$pala2 = hexdec(substr($pa2, 2*$i, 2));

		$uusipala = $pala1 ^ $pala2;
		$uusipala = dechex($uusipala);
		if (strlen($uusipala) == 1) $uusipala = "0".$uusipala;
		
		//echo "\n$pala1 xor $pala2 = $uusipala";
		
		$tulos .= $uusipala;
	}

	$tulos = tarkistapariteetti($tulos);
	$siirtoavain = $tulos;
	//echo "\n$tulos";

	$tulos = pack('H*',$tulos);
	$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
	$iv = pack('H*','0000000000000000');
	mcrypt_generic_init($td, $tulos, $iv);
	$tulos = mcrypt_generic($td, pack('H*','0000000000000000'));
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

	$tulos=unpack('H*',$tulos);

	//echo "\nlopullinen tulos '$tulos[1]'\n";
	
	if (substr($tulos[1],0,6) == $tarkiste) {
		$query = "SELECT sasukupolvi FROM yriti WHERE tunnus='$tunnus'";
		$tilires = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($tilires) == 1) {
			$tilirow = mysql_fetch_array($tilires);
			if ($suku >= $tilirow['sassukupolvi']) {
				$query = "UPDATE yriti SET siirtoavain='$siirtoavain', sasukupolvi='$suku' WHERE tunnus = '$tunnus'";
				$xres = mysql_query($query) or pupe_error($query);
			}
		}
		else {
			return "Sukupolvinumero, $suku, ei täsmää tietokannan kanssa!";
			exit;
		}
		return "";
		exit;
	}
	else {
		return "Tarkiste ei täsmää! $tulos[1]";
	}
}

function sanoma ($tilirow, $tyyppi) {

# Sanoma tunnus
$file=">>";
$file .= sprintf ('%-3.3s',$tyyppi);

# Sanoma pituus
if($tyyppi == "SUO")
	$file.="128";

else
	$file.="161";

# Versio
$file.="120";

# Onnistumiskoodi + ilmoituskoodi
$file.=" 0000";

# Ohjelmisto
# Nimi
$file.= sprintf ('%-12.12s', "PUPESOFT"); //Pituus 12

# Versio
$file.= sprintf ('%-4.4s', "0099"); //Pituus 4

# Turvamenetelmä
if($tyyppi == "SUO")
	$file.="SKH";
else
	$file.="SMH";

# Vastaanottaja
$file.=sprintf ('%-25.25s', $tilirow['pankki']);


# Lähettäjä
$file.=sprintf ('%-25.25s', $tilirow['asiakas']);

# Siirto-ja käyttöavainten sukupolvi numerot
$file.=sprintf ('%-1.1s', $tilirow['sasukupolvi']);
$file.=sprintf ('%-1.1s', $tilirow['kasukupolvi']);

# Aikaleima ja leima numero
$file.= date('ymdHis');
$file.="000";

# Suojausalue
if($tyyppi == "ESI")
	$file.=" ";

else
	$file.="S";

$file.= sprintf ('%-9.9s', " "); // 9


# Salattu kerta-avain
if($tyyppi == "ESI")
	$file.=sprintf ('%-16.16s', " "); // 16
else
	$file.=sprintf ('%-16.16s', $tilirow['salattukerta']);


#Tiivisteen paikka
if($tyyppi == "ESI")
	$file.=sprintf ('%-16.16s', " ");; // 16

return $file;

}

function siirtopyynto ($pankki, $tilinumero, $aineisto, $salasana ,$paivays) {

	if ($pankki=='2') {
		$apu= sprintf ('%-17.17s', "SIIRTOPYYNTO");
		$apu.= sprintf ('%-10.10s', "");
		$apu.= sprintf ('%-10.10s', "$aineisto");
		$apu.= sprintf ('%-18.18s', $tilinumero);
		$apu.= sprintf ('%-10.10s', $salasana);
		$apu.= sprintf ('%-6.6s', $paivays);
		$apu .= " 9979 " . "999";
	}
	elseif ($pankki=='8') {
		if ($aineisto == 'STATUS') {
			$apu = "//SOVELLUS=xxxx,ERA=000000,KYS\n";
			$apu .= "//EOF\n";
		}
		if ($aineisto == 'TITO') {
			$apu = "//SOVELLUS=KTON,ERA=000000\n";
			$apu .= "//EOF\n";
		}
	}
	else
		$apu='';
	
	return $apu;
}

function kasitteleesip ($tiedostonnimi) {

	$tiedot = file_get_contents($tiedostonnimi);
	echo "Pankki vastasi --> " . substr($tiedot,177,60) . "<br>";

}




?>
