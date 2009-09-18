<?php

function meidanxor ($eka, $toka) {

	$tulos = '';
	for($i = 0; $i < 8; $i++) {

		$pala1 = hexdec(substr($eka, 2*$i, 2));
		$pala2 = hexdec(substr($toka, 2*$i, 2));

		$uusipala = $pala1 ^ $pala2;
		$uusipala = dechex($uusipala);
		if (strlen($uusipala) == 1) $uusipala = "0".$uusipala;
		
		//echo "\n$pala1 xor $pala2 = $uusipala";
		
		$tulos .= $uusipala;
	}
	return $tulos;
}

function ekakayttoavain ($tunnus) {

	$query = "SELECT siirtoavain FROM yriti WHERE tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);	
	if (mysql_num_rows($result) != 1) {
		return "Siirtoavain, $tunnus, ei lˆytynyt tietokannasta ";
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

//Lis‰t‰‰n k‰yttˆavain ja sukupolvi tietokantaan
//echo "\nlopullinen tulos '$tulos[1]'\n";

	$query = "UPDATE yriti SET kayttoavain='$kayttoavain', kasukupolvi=0 WHERE tunnus = '$tunnus'";
	$xres = mysql_query($query) or pupe_error($query);
}

function uusikayttoavain ($tunnus, $salattu) {

	$query = "SELECT siirtoavain FROM yriti WHERE tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($result) == 1) {
		return "Siirtoavain, $tunnus, ei lˆytynyt tietokannasta ";
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

	//lis‰ys tietokantaan

	$query = "UPDATE yriti SET kayttoavain='$tulos[1]', kasukupolvi=if(kasukupolvi + 1 = 10,0,kasukupolvi+1) WHERE tunnus = '$tunnus'";
	$xres = mysql_query($query) or pupe_error($query);
	return "";
}

function salattukertaavain ($tunnus) {

	$query = "SELECT * FROM yriti WHERE tunnus='$tunnus'";
	$tilires = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($tilires) == 1) {
		$yritirow = mysql_fetch_array($tilires);

		echo "Siirtoavain: $yritirow[siirtoavain]<br>";

		$siemen = $yritirow['siemen'];

		echo "Siemen: $siemen<br>";
		if ($siemen== '') {
			for($i=0;$i<16;$i++){
				$apu = mt_rand(0, 16);
				if($apu<10) $siemen.=$apu;
				elseif($apu==10) $siemen.="a";
				elseif($apu==11) $siemen.="b";
				elseif($apu==12) $siemen.="c";
				elseif($apu==13) $siemen.="d";
				elseif($apu==14) $siemen.="e";
				elseif($apu==15) $siemen.="f";
			}
		}
			for($i=0;$i<16;$i++){
				$apu = mt_rand(0, 16);
				if($apu<10) $avain.=$apu;
				elseif($apu==10) $avain.="a";
				elseif($apu==11) $avain.="b";
				elseif($apu==12) $avain.="c";
				elseif($apu==13) $avain.="d";
				elseif($apu==14) $avain.="e";
				elseif($apu==15)	$avain.="f";
			}
		echo "Siemen: $siemen Avain: $avain<br>";

		# Muutetaan luvut oikeaan muotoon ja alustetaan salaus
		$avain = pack("H*",$avain);
		$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
		$iv = pack('H*','0000000000000000');
		mcrypt_generic_init($td, $avain, $iv);
		$aika = pack("H*",$aika);

		# Muodostetaan satunnaisluku
		# Salaamalla aikaleima avaimella
		$aika = date('ymdHis');
		$vali = mcrypt_generic($td,$aika);
		$vali = unpack("H*",$vali);

		# XOR:taan v‰litulos ja siemen
		$vali = meidanxor($vali[1],$siemen);
		$vali = pack("H*",$vali);

		# Salataan XOR avaimella
		$random = mcrypt_generic($td,$vali);
		$random = unpack("H*",$random);

		# Tarkistetaan pariteetti ja saadaan kerta-avain
		$kerta = tarkistapariteetti($random[1]);
		$uusikertaavain = $kerta;
		echo  "Kerta-avain: $uusikertaavain<br>";

		# Muodostetaan uusi siemen 
		$siemen = meidanxor($siemen,$random[1]);
		$siemen = pack("H*",$siemen);
		$siemen = mcrypt_generic($td,$siemen);
		$siemen = unpack("H*",$siemen);

		# Salataan kerta-avain 
		# Muutetaan luvut oikeaan muotoon
		$siirtoavain = pack("H*",$yritirow['siirtoavain']);
		$kerta = pack("H*",$kerta);

		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		# Alustetaan salaus
		$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
		$iv = pack('H*','0000000000000000');
		mcrypt_generic_init($td, $siirtoavain, $iv);

		# Salataan kerta-avain siirtoavaimella
		$sal = mcrypt_generic($td,$kerta);
		$sal = unpack("H*",$sal);
		echo "Salattu kerta-avain: $sal[1]<br>";

		# Tallennetaan tietokantaan
		$query = "UPDATE yriti SET kertaavain='$uusikertaavain', salattukerta='$sal[1]', siemen='$siemen[1]' WHERE tunnus='$tunnus'";
		$updres = mysql_query($query) or pupe_error($query);
	}
}


function salaa ($jono, $arg, $kayttoavain) {

	$palautus = $jono;
	//Alustetaan kryptaus k‰yttˆavaimella  
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

	//Jos viimeinen lohko j‰‰ vajaaksi t‰ytet‰‰n se bin‰‰ri nollilla
	$k--;
	if (strlen($data[$k]) != 8) $data[$k] = str_pad($data[$k], 8, "\0");

	// Jonosta tutkitaan osa vain tarkisteeseen saakka!

	if (($arg == "ESIp") or ($arg == "PTE")) $k = 17;


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
	if (($arg == "ESIa") or ($arg == "VAR")) {
		$palautus .= strtoupper($tark[1]);
		return $palautus;
	}

	if (($arg == "ESIp") or ($arg == "PTE")) {
		$tarkiste =  $data[18];
		$tarkiste .= $data[19];
		echo "Pankintarkiste: $tarkiste Meid‰n laskema: " .strtoupper($tark[1]). "<br>";
		if (strtoupper($tark[1]) == strtoupper($tarkiste)){
			return ""; // ok!
		}
		else {
			return "Tarkisteet eiv‰t t‰sm‰‰!";
		}
	}
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

}

function tiiviste ($jono, $kertaavain) {

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


	//Alustetaan kryptaus kertaavaimella  
	$kertaavain = pack("H*",$kertaavain);
	$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
	$iv=pack('H*','0000000000000000');
	mcrypt_generic_init($td, $kertaavain, $iv);
	
	$text = $data[0];
	$text = mcrypt_generic($td, $text);

	for($i=1; $i<=$k; $i++){
		$ortext=$text ^ $data[$i];
		$text = mcrypt_generic($td, $ortext);
	}

	//Muutetaan salattu teksti Heksoiksi
	$tiivisteet = unpack("H*", $text);
	$tiiviste = strtoupper($tiivisteet[1]);

	echo "Tiiviste: $tiiviste<br>";
	return $tiiviste;

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
		// Lasketaan ykkˆsten m‰‰r‰
		$i = 0;
		for($m = 0; $m < 8; $m++) {
			if ($apu[$m] == '1') $i++;
		}

		// Jos ykkˆsi‰ parillinen m‰‰r‰ muutetaan viimeist‰ merkki‰
		if(!($i % 2)){
			//echo " on parillinen ja koko on ". strlen($apu);
			$vmerkki = strlen($apu) - 1;
			// Jos viimeinen on ykkˆnen niin muutetaan se nollaksi
			if($apu[$vmerkki] == 1) {
				$apu[$vmerkki] = 0;
			}
			// Jos viimeinen on nolla muutetaan se ykkˆseksi
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

	// Yhdistet‰‰n lohkot takaisin luvuksi
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
		return "Pariteetin tarkastus ei mennyt l‰pi. Pariteetti asetettu v‰‰rin!";
		exit;
	}

	// xor on v‰h‰n vaikea?
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
			else { 
			 return "Sukupolvinumero, $suku, ei t‰sm‰‰ tietokannan kanssa!";
			}
		}
		else {
			return "Pankkitili on kadonnut";
		}
		return "";
	}
	else {
		return "Tarkiste ei t‰sm‰‰! $tulos[1]";
	}
}

function sanoma ($tilirow, $tyyppi, $aikaleima) {

# Sanomatunnus
$file=">>";
$file .= sprintf ('%-3.3s',$tyyppi);

# Sanomapituus
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

# Turvamenetelm‰
if($tyyppi == "SUO")
	$file.="SKH";
else
	$file.="SMH";

# Vastaanottaja
$file.=sprintf ('%-25.25s', $tilirow['pankki']);


# L‰hett‰j‰
$file.=sprintf ('%-25.25s', $tilirow['asiakas']);

# Siirto-ja k‰yttˆavainten sukupolvi numerot
$file.=sprintf ('%-1.1s', $tilirow['sasukupolvi']);
$file.=sprintf ('%-1.1s', $tilirow['kasukupolvi']);

# Aikaleima ja leimanumero
if (strlen($aikaleima) == 15) $file.=$aikaleima;
elseif (strlen($aikaleima) == 12) $file.=$aikaleima . "000"; 
else {
	$file.= date('ymdHis');
	$file.="000";
}

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
	$file.=sprintf ('%-16.16s', strtoupper($tilirow['salattukerta']));


#Tiivisteen paikka
if($tyyppi == "ESI")
	$file.=sprintf ('%-16.16s', " ");; // 16

return $file;

}

function siirtopyynto ($pankki, $tilinumero, $aineisto, $salasana ,$paivays) {

	if ($pvm == '') $pvm = '000000';

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
			$apu = "//SOVELLUS=KTON,ERA=000000,KYS\n";
			$apu .= "//EOF\n";
		}
		if ($aineisto == 'TITO') {
			$apu = "//SOVELLUS=KTON,ERA=000000\n";
			$apu .= "//EOF\n";
		}
		if ($aineisto == 'VKEUR') {
			$apu = "//SOVELLUS=VKEN,ERA=000000\n";
			$apu .= "//EOF\n";
		}
		if ($aineisto == 'xxx') {
			$apu = "//SOVELLUS=UM2L,ERA=" . $paivays . "\n";
		}
		if ($aineisto == 'LMP300') {
			$apu = "//SOVELLUS=OL2L,ERA=" . $paivays . "\n";
		}
	}
	else
		$apu='';
	
	return $apu;
}

function kasitteleesip ($tiedostonnimi, $kayttoavain) {

	$tiedot = file_get_contents($tiedostonnimi);
	echo "Pankki vastasi --> " . substr($tiedot,177,60) . "<br>";
	$tulos = salaa($tiedot, 'ESIp', $kayttoavain);
	if ($tulos != '') {
		echo $tulos . "<br>";
	}
	else {
		echo "ESIp-sanoman tunnisteet ovat oikein<br>";
	}
	if (substr($tiedot,11,1) == 'K') {
		echo "ESI-aineisto hyv‰ksyttiin<br>";
		return "";
	}
	else {
		return "ESI-aineisto hyl‰ttiin<br>";
	}
}




?>
