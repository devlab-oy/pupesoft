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

function mikaaineisto ($aineisto) {
	//echo "Aineisto $aineisto<br>";
	if (substr($aineisto,0,4) == 'LM03') return "LMP300";
	elseif (substr($aineisto,0,4) == 'LUM2') return "xxx";
	else return "";
}

function teepankkikonversio ($aineisto) {
	//echo "Aineisto $aineisto<br>";
	$oikeat = array("Ä", "Ö", "Å");
	$pankin = array("[", "\\", "]");
	foreach ($aineisto as $rivi) {
		//echo "'$rivi'<br>";
		$rivi = strtoupper($rivi);
		$rivi = str_replace($oikeat, $pankin, $rivi);
		$uusi[] = $rivi;
	}
	return $uusi;
}

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

function uusikayttoavain ($salattu, $yritirow) {

//alustetaan salaus
	echo "Uusi salattu avain on $salattu Siirtoavain on $yritirow[siirtoavain]<br>";
	$tulos = pack('H*',$yritirow['siirtoavain']);
	$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
	$iv = pack('H*','0000000000000000');
	mcrypt_generic_init($td, $tulos, $iv);
	$tulos = mdecrypt_generic($td, pack('H*',$salattu));
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

	$uusiavain=unpack('H*',$tulos);

	$uusitulos = tarkistapariteetti($uusiavain[1]);
	if ($uusitulos == $uusiavain[1]) {
		//lisäys tietokantaan
		$query = "UPDATE yriti SET kayttoavain='$uusiavain[1]', kasukupolvi=if(kasukupolvi + 1 = 10,1,kasukupolvi+1) WHERE tunnus = '$yritirow[tunnus]'";
		$xres = mysql_query($query) or pupe_error($query);
		return "";
	}
	else return "Uuden käyttöavaimen pariteetti oli virheellinen!";
}

function salattukertaavain ($tunnus) {
	global $testaus;
	$query = "SELECT * FROM yriti WHERE tunnus='$tunnus'";
	$tilires = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($tilires) == 1) {
		$yritirow = mysql_fetch_array($tilires);
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
			elseif($apu==15) $avain.="f";
		}
		echo "Siemen: $siemen Avain: $avain<br>";

		// Muutetaan luvut oikeaan muotoon ja alustetaan salaus
		$avain = pack("H*",$avain);
		$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
		$iv = pack('H*','0000000000000000');
		mcrypt_generic_init($td, $avain, $iv);
		$aika = pack("H*",$aika);

		// Muodostetaan satunnaisluku
		// Salaamalla aikaleima avaimella
		$aika = date('ymdHis');
		$vali = mcrypt_generic($td,$aika);
		$vali = unpack("H*",$vali);

		// XOR:taan välitulos ja siemen
		$vali = meidanxor($vali[1],$siemen);
		$vali = pack("H*",$vali);

		// Salataan XOR avaimella
		$random = mcrypt_generic($td,$vali);
		$random = unpack("H*",$random);

		// Tarkistetaan pariteetti ja saadaan kerta-avain
		$uusikertaavain = tarkistapariteetti($random[1]);
		if ($testaus != '') $uusikertaavain = "5208290ED9BF0B6D";
		echo  "Kerta-avain: $uusikertaavain<br>";

		// Muodostetaan uusi siemen 
		$siemen = meidanxor($siemen,$random[1]);
		$siemen = pack("H*",$siemen);
		$siemen = mcrypt_generic($td,$siemen);
		$siemen = unpack("H*",$siemen);

		// Salataan kerta-avain
		// Muutetaan luvut oikeaan muotoon
		$siirtoavain = pack("H*",$yritirow['siirtoavain']);
		$kerta = pack("H*",$uusikertaavain);

		// Alustetaan salaus
		$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
		$iv = pack('H*','0000000000000000');
		mcrypt_generic_init($td, $siirtoavain, $iv);

		// Salataan kerta-avain siirtoavaimella
		$sal = mcrypt_generic($td,$kerta);
		$sal = unpack("H*",$sal);
		echo "Salattu kerta-avain: $sal[1] Uusi siemen $siemen[1]<br>";

		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		// Tallennetaan tietokantaan
		$query = "UPDATE yriti SET kertaavain='$uusikertaavain', salattukerta='$sal[1]', siemen='$siemen[1]' WHERE tunnus='$tunnus'";
		$updres = mysql_query($query) or pupe_error($query);
		$yritirow['kertaavain'] = $uusikertaavain;
		$yritirow['salattukerta'] = $sal[1];
		return $yritirow;
	}
}


function salaa ($jono, $arg, $avain) {

	$palautus = $jono;
	//Alustetaan kryptaus avaimella
	$kayttoavain = pack("H*",$avain);
	$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
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
	$tark = unpack("H*", $text);

	// Tulostellaan arvot
	// Tehdään tarvittavat temput tietyille tiedostotyypeille
	if (($arg == "ESIa") or ($arg == "VAR")) {
		$palautus .= strtoupper($tark[1]);
		return $palautus;
	}

	if (($arg == "ESIp") or ($arg == "PTE")) {
		$tarkiste =  $data[18];
		$tarkiste .= $data[19];
		echo "Pankintarkiste: $tarkiste Meidän laskema: " .strtoupper($tark[1]). "<br>";
		if (strtoupper($tark[1]) == strtoupper($tarkiste)){
			return ""; // ok!
		}
		else {
			return "Tarkisteet eivät täsmää!";
		}
	}

	if ($arg == 'tiiviste') {
		return $tark[1];
	}

	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

}

function tiiviste ($jono, $kertaavain) {
	global $testaus;

	if ($testaus != '') {
		$jono = array();
		$jono[]="1921030  259018000000140111111116100000000121MATTI MEIKÄLÄINEN  010101001A";
		$jono[]="1921030  259018000000140111111116100000000321MAIJA MEIK#L#INEN  010201001A";
		$jono[]="1921030  259018000000140111111116100000000421Ä. Ö. ÅLAND        010101001A";
		$jono[]="1921030  259018000000140111111116100000000521#. @. \$LAND        010101001A";
		$jono[]="4921030                0111111116100000001384                   000004";
	}

	//Poistetaan loppu whitespace
	$uusijono="";
	echo "<pre>";
	foreach ($jono as $rivi) {
		echo "'$rivi'<br>";
		$uusijono .= rtrim($rivi);
	}
	echo "</pre>";

	$oikeat = array("[", "\\", "]", "{", "|", "}", "Ä", "Ö", "Å", "#", "$", "@");
	$pankin = array(" ", " ", " ", " ", " ", " ", " ", " ", " ", " ", " ", " ");
	$uusijono = str_replace($oikeat, $pankin, $uusijono);

	echo "Uusijono --> $uusijono<br>";

	$tiiviste = salaa($uusijono, 'tiiviste', $kertaavain);

	echo "Tiiviste: $tiiviste<br>";

	if ($testaus != '' and strtoupper($tiiviste) != '4954F0194C2B696D')
		echo "Tiiviste pielessä po 4954F0194C2B696D<br>" ;
	else
		echo "Tiiviste on oikein!<br>";

	return $tiiviste;
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
			else { 
			 return "Sukupolvinumero, $suku, ei täsmää tietokannan kanssa!";
			}
		}
		else {
			return "Pankkitili on kadonnut";
		}
		return "";
	}
	else {
		return "Tarkiste ei täsmää! $tulos[1]";
	}
}

function sanoma ($tilirow, $tyyppi, $aikaleima) {

// Sanomatunnus
$file=">>";
$file .= sprintf ('%-3.3s',$tyyppi);

// Sanomapituus
if($tyyppi == "SUO")
	$file.="128";

else
	$file.="161";

// Versio
$file.="120";

// Onnistumiskoodi + ilmoituskoodi
$file.=" 0000";

// Ohjelmisto
// Nimi
$file.= sprintf ('%-12.12s', "PUPESOFT"); //Pituus 12

// Versio
$file.= sprintf ('%-4.4s', "0099"); //Pituus 4

// Turvamenetelmä
if($tyyppi == "SUO")
	$file.="SKH";
else
	$file.="SMH";

// Vastaanottaja
$file.=sprintf ('%-25.25s', $tilirow['pankki']);


// Lähettäjä
$file.=sprintf ('%-25.25s', $tilirow['asiakas']);

// Siirto-ja käyttöavainten sukupolvi numerot
$file.=sprintf ('%-1.1s', $tilirow['sasukupolvi']);
$file.=sprintf ('%-1.1s', $tilirow['kasukupolvi']);

// Aikaleima ja leimanumero

if ($aikaleima == '') $aikaleima = date('ymdHis')."000"; 

if (substr($tilirow['tilino'],0,1) == '8') {
	//Sampo haluaa eri aikaleiman :)
	if (($tyyppi == 'SUO')or ($tyyppi == 'VAR')) {
		$aikaleimaloppu = substr($aikaleima,-3);
		$aikaleimaloppu++;
		$aikaleima = substr($aikaleima,0,12) . sprintf ('%03d', $aikaleimaloppu);
	}
}

$file.=$aikaleima;

// Suojausalue
	if($tyyppi == "ESI")
	$file.=" ";
else
	$file.="S";

$file.= sprintf ('%-9.9s', " "); // 9

// Salattu kerta-avain
if($tyyppi == "ESI")
	$file.=sprintf ('%-16.16s', " "); // 16
else
	$file.=sprintf ('%-16.16s', strtoupper($tilirow['salattukerta']));

// Tiivisteen paikka
if($tyyppi == "ESI")
	$file.=sprintf ('%-16.16s', " "); // 16

return $file;

}

function siirtopyynto ($pankki, $tilinumero, $aineisto, $salasana ,$paivays, $nro) {

	if ($paivays == '') $paivays = '000000';

	if ($pankki=='2') {
		$apu= sprintf ('%-17.17s', "SIIRTOPYYNTO");
		$apu.= sprintf ('%-10.10s', "");
		$apu.= sprintf ('%-10.10s', $aineisto);
		$apu.= sprintf ('%-18.18s', "");  // Tässä voisi olla tilinumero
		$apu.= sprintf ('%-10.10s', "");  // Tässä voisi olla salasana
		$apu.= sprintf ('%-6.6s', $paivays);
		$apu .= " 9979 " . "999\n";
	}
	elseif ($pankki=='8') {
		if ($paivays = '000000') $paivays = date ('md') . sprintf('%02d', $nro);;
		if ($aineisto == 'STATUS') {
			$apu = "//SOVELLUS=KTON,ERA=000000,KYS\r\n";
			$apu .= "//EOF\r\n";
		}
		if ($aineisto == 'TITO') {
			$apu = "//SOVELLUS=KTON,ERA=000000\r\n";
			$apu .= "//EOF\r\n";
		}
		if ($aineisto == 'VKEUR') {
			$apu = "//SOVELLUS=VKEN,ERA=000000\r\n";
			$apu .= "//EOF\r\n";
		}
		if ($aineisto == 'xxx') {
			$apu = "//SOVELLUS=UM2L,ERA=" . $paivays . "\r\n";
		}
		if ($aineisto == 'LMP300') {
			$apu = ">>SOVELLUS=OL2L,ERA=" . $paivays . "\r\n";
		}
	}
	else
		$apu='';

	return $apu;
}

function kasitteleesip ($tiedostonnimi, $yritirow) {

	$tiedot = file_get_contents($tiedostonnimi);
	echo "Pankki vastasi --> " . substr($tiedot,177,60) . "<br>";
	$tulos = salaa($tiedot, 'ESIp', $yritirow['kayttoavain']);
	if ($tulos != '') {
		echo $tulos . "<br>";
	}
	else {
		echo "ESIp-sanoman tunnisteet ovat oikein<br>";
	}
	if (substr($tiedot,160,1) == 1) {
		echo "Käyttöavaimen vaihto!<br>";
		$tulos = uusikayttoavain(substr($tiedot,161,16), $yritirow);
		if ($tulos != '') echo $tulos."<br>";
		else echo "Käyttöavaimen päivitys onnistui<br>";
	}
	if (substr($tiedot,11,1) == 'K') {
		echo "ESI-aineisto hyväksyttiin<br>";
		return "";
	}
	else {
		return "ESI-aineisto hylättiin<br>";
	}
}




?>
