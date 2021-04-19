<?
function aineistonnouto ($yritirow, $aineisto, $pvm) {
	global $testaus;

	$nro = sprintf ('%03d', $yritirow['nro']);

	$pankki = substr($yritirow['tilino'],0,1);
	if ($pankki == '') $pankki = '2';
	if ($pankki == '1') $pankki='2';

	// TIEDOSTONIMET
	$osoite = "/var/www/html/pupesoft/dataout";
	$etiedosto = "esi.a";
	$eptiedosto = "esi.p";
	$sptiedosto = "siirtopyynto";
	$kuittausnimi = "kuittaus";

	echo "Tehd��n siirtopyynt� $osoite/$sptiedosto<br>";

	$siirtopyynto = siirtopyynto($pankki, $yritirow['tilino'], $aineisto, "DEMO", $pvm, $nro);
	echo strlen($siirtopyynto). "-->" . $siirtopyynto."<br>";

	echo "Alustetaan ESI-tiedoston teko<br>";
	$esi = sanoma($yritirow, "ESI", "");
	//echo strlen($esi). "-->" . $esi."<br>";

	if ($testaus != '') {
		$esi =">>ESI161120 0000KERMIT      3.01SMH003701234567             99910000011111111        00941015073000001                                          ";
		echo strlen($esi). "-->" . $esi."<br>";
		if (strtoupper($yritirow['kayttoavain']) != "AEBAE983D6406D07") echo "K�ytt�avain on virheellinen $yritirow[kayttoavain]<br>";
		else echo "K�ytt�avain on oikein<br>";
	}

	echo "Suojataan ESI-tiedosto '$yritirow[kayttoavain]'<br>";
	$esi = salaa($esi, "ESIa", $yritirow['kayttoavain']);
	//echo strlen($esi). "-->" . $esi."<br>";

	if ($testaus != '') {
		if (substr($esi,-16) != '4B69B6DD4F72C75B') echo "Suojaus ei onnistu ".substr($esi,-16)."<br>";
			else echo "Suojaus ok<br>";
	}

	// Jos ei haluta avainvaihtoa, lis�t��n sanoman per��n nolla	
	$esi .= "0";
	echo "Ei avainvaihtoa<br>";
	//echo strlen($esi). "-->" . $esi."<br>";

	// Tiedostojen nimet
	$omaesia= $osoite . "/" . $yritirow['tunnus'] . "-" . $etiedosto;
	$omaesip= $osoite . "/" . $yritirow['tunnus'] . "-" . $eptiedosto;
	$omasiirto= $osoite . "/" . $yritirow['tunnus'] . "-" . $sptiedosto . "." . $nro;
	$omaaineisto = $osoite . "/" . $yritirow['tunnus'] . "-" . strtolower($aineisto) . "." . $nro ;
	$omakuittaus = $osoite . "/" . $yritirow['tunnus'] . "-" . $kuittausnimi . "." . $nro ;

	//Nordea
	if ($pankki == '2') {
		$pankinesia = "ESI.A";
		$pankinesip= "ESI.P";
		$pankinsiirto = "SIIRTO";
		$pankinaineisto = $aineisto.".".$nro;
		$pankinkuittaus = "KUITTAUS." . $nro;
	}
	//Sampopankki
	if ($pankki == '8') {
		$pankinesia = "asiakas";
		$pankinesip= "kuittaus";
		$pankinsiirto = "sovellus";
		$pankinaineisto = "aineisto";
		$pankinkuittaus = "kuittaus";
		$lopetus = "//SIGNOFF\n";
		$yhteydenlopetus = $osoite ."/" . $yritirow['tunnus'] . "-lopetustiedosto";
		file_put_contents($yhteydenlopetus,$lopetus);
	}

	file_put_contents($omaesia, $esi);
	file_put_contents($omasiirto,$siirtopyynto);

	// FTP-yhteydenottomuuttujat
	if ($pankki == '2') {
		$host="solo.nordea.fi";
		$log="anonymous";
		$pass="SITE PASSIVE";
		//$pass = "pupesoft test";
		$passiivi = 0;
		//$passiivi = 1;
		$sitekomento = '';
		//$sitekomento = 'PASSIVE';
	}
	if ($pankki == '8') {
		$host="ftplinkki.sampopankki.fi";
		$log="anonymous";
		$pass="pupesofttestaus";
		$passiivi = 1;
		$sitekomento = '';
	}

	echo "Avataan FTP-yhteys $host<br>";
	//exit;
	$ftp = ftp_connect($host);
	if($ftp) {
		// Jos jokin asia ep�onnistuu, katkaistaan heti yhteys
		echo "Yhteys muodostettu: $host<br>";
		$login_ok = ftp_login($ftp,$log,$pass);
		if ($sitekomento != '') {
			if (ftp_site($ftp, $sitekomento)) {
				echo "Site-komento onnistui<br>";
			} else {
				echo 'Site-komento ep�onnistui<br>';
			}
		}
		if ($passiivi == 1) {
			if(ftp_pasv($ftp, true)) {
				echo "Passiivi-komento onnistui<br>";
			} else {
				echo "Passiivi-komento ep�onnistui<br>";
			}
		}
		echo "L�hetet��n Esi-sanoma: $omaesia<br>";
		if(ftp_put($ftp, $pankinesia, $omaesia, FTP_ASCII)) {
			echo "Esi-sanoman l�hetys onnistui.<br>Haetaan vastaus: $omaesip<br>";
			if(ftp_get($ftp, $omaesip, $pankinesip, FTP_ASCII)) {
				echo  "Esi-sanoman vastaus saatiin.<br>";
				$tulos=kasitteleesip($omaesip, $yritirow);
				if ($tulos == '') {
					echo "L�hetet��n siirtopyynt�: $omasiirto<br>";
					if(ftp_put($ftp, $pankinsiirto, $omasiirto, FTP_ASCII)) {
						echo "Siirtopyynt� l�hetettiin.<br>Haetaan aineisto: $omaaineisto<br>";
						if(ftp_get($ftp, $omaaineisto, $pankinaineisto, FTP_ASCII)) {
							echo "Aineiston haku onnistui.<br>";
						}
						else {
							echo "Aineiston haku ei onnistunut.<br>";
						}
					}
					else {
						echo "Siirtopyynt� ev�ttiin<br>";
					}
				}
				else {
					echo "Pankki vastasti ESIp-tiedostolla, mutta todennus ei onnistunut<br>";
				}
			}
			else {
				echo "Vastausta esi-sanomaan ei saatu<br>";
			}
		}
		else {
			echo "Esi-sanoman l�hetys ei onnistunut<br>";
		}
		echo "Lopetetaan yhteys<br>";

		// Sampo haluaa lopetustiedoston
		if ($pankki=='8') {
			if(ftp_put($ftp, "lopetus", $yhteydenlopetus, FTP_ASCII)) {
				echo "Pyydettiin yhteydenlopetus.<br>";
			}
			else {
				echo "Yhteydenlopetuspyynt� ep�onnistui.<br>";
			}
		}
		ftp_quit($ftp);
	}
	else {
		echo "Ei yhteytt�!<br>";
	}
	return "";
}

function aineistonlahetys ($yritirow, $aineisto, $pvm, $lahetettava) {

	$nro = sprintf ('%03d', $yritirow['nro']);

	//Tehd��n uniikki aikaleima
	$aikaleima = date("ymdHis") . $nro;

	$pankki = substr($yritirow['tilino'],0,1);
	if ($pankki == '') $pankki = '8';
	if ($pankki == '1') $pankki='2';

	// TIEDOSTONIMET
	$osoite = "/var/www/html/pupesoft/dataout";
	$etiedosto = "esi.a";
	$eptiedosto = "esi.p";
	$sptiedosto = "siirtopyynto";
	$aineistonimi = "aineisto"; // Vain Nordea
	$kuittausnimi = "kuittaus";

	$siirtopyynto = siirtopyynto($pankki, $yritirow['tilino'], $aineisto, "", $pvm, $nro); //4s parametri voi olla DEMO
	//echo strlen($siirtopyynto). "-->" . $siirtopyynto."<br>";

	echo "Alustetaan ESI-tiedoston teko<br>";
	$esi = sanoma($yritirow, "ESI", $aikaleima);
	//echo strlen($esi). "-->" . $esi."<br>";

	echo "Suojataan ESI-tiedosto '$yritirow[kayttoavain]'<br>";
	$esi = salaa($esi, "ESIa", $yritirow['kayttoavain']);
	//echo strlen($esi). "-->" . $esi."<br>";

	// Jos ei haluta avainvaihtoa, lis�t��n sanoman per��n nolla	
	$esi .= "0";
	echo "Ei avainvaihtoa<br>";
	//echo strlen($esi). "-->" . $esi."<br>";

	// Tiedostojen nimet
	$omaesia= $osoite . "/" . $yritirow['tunnus'] . "-" . $etiedosto;
	$omaesip= $osoite . "/" . $yritirow['tunnus'] . "-" . $eptiedosto;
	$omasiirto= $osoite . "/" . $yritirow['tunnus'] . "-" . $sptiedosto . "." . $nro;
	$omaaineisto = $osoite . "/" . $yritirow['tunnus'] . "-" . strtolower($aineisto) . "." . $nro ;
	$omakuittaus = $osoite . "/" . $yritirow['tunnus'] . "-" . $kuittausnimi . "." . $nro ;
	$omaaineistodata = $osoite . "/" . $yritirow['tunnus'] . "-" . $aineistonimi . "." . $nro ;

	//Nordea
	if ($pankki == '2') {
		$pankinesia = "ESI.A";
		$pankinesip= "ESI.P";
		$pankinsiirto = "SIIRTO";
		$pankinaineisto = $aineisto."TUN.".$nro;
		$pankinaineistodata = $aineisto . "." . $nro;
		$pankinkuittaus = "KUITTAUS." . $nro;
	}
	//Sampopankki
	if ($pankki == '8') {
		$pankinesia = "asiakas";
		$pankinesip= "kuittaus";
		$pankinsiirto = "sovellus";
		$pankinaineisto = "aineisto";
		$pankinaineistodata = "aineisto";
		$pankinkuittaus = "kuittaus";
		$lopetus = "//SIGNOFF\n";
		$yhteydenlopetus = $osoite ."/" . $yritirow['tunnus'] . "-lopetustiedosto";
		file_put_contents($yhteydenlopetus,$lopetus);
	}

	echo "Tehd��n siirtopyynt� $omaaineisto ja aineistotiedosto $omaaineistodata<br>";

	//Tehd��n t�lle aineistolle kertaavain
	$yritirow = salattukertaavain($yritirow['tunnus']);

	//Tehd��n SUO-tietue
	$aineistodata = sanoma($yritirow, "SUO", $aikaleima) . "\r\n";

	//Lasketaan tiiviste aineistolle
	$tiiviste = tiiviste($lahetettava, $yritirow['kertaavain']);

	//Tehd��n VAR-tietue
	$var= sanoma($yritirow, "VAR", $aikaleima);

	//Kootaan tiedosto paloistaan
	foreach ($lahetettava as $rivi) {
		//echo "'$rivi'<br>";
		$aineistodata .= rtrim($rivi, "\r\n") . "\r\n";
	}
	$aineistodata .= $var . $tiiviste;

	echo "Suojataan VAR-tiedosto '$yritirow[kayttoavain]'<br>";
	$aineistodata = salaa($aineistodata, "VAR", $yritirow['kayttoavain']) . "\r\n";

	//echo "<pre>$aineistodata</pre><br>";
	//echo "<pre>$siirtopyynto</pre><br>";

	file_put_contents($omasiirto,$siirtopyynto);
	file_put_contents($omaaineistodata,$aineistodata);

	file_put_contents($omaesia, $esi);

	// FTP-yhteydenottomuuttujat
	if ($pankki == '2') {
		$host="solo.nordea.fi";
		$log="anonymous";
		$pass="Pupesoft test";
		$passiivi = 0;
	}
	if ($pankki == '8') {
		$host="ftplinkki.sampopankki.fi";
		$log="anonymous";
		$pass="pupesofttestaus";
		$passiivi = 1;
	}

	echo "Avataan FTP-yhteys $host<br>";
	exit;
	$ftp = ftp_connect($host);
	if($ftp) {
		// Jos jokin asia ep�onnistuu, katkaistaan heti yhteys
		echo "Yhteys muodostettu: $host<br>";
		$login_ok = ftp_login($ftp,$log,$pass);
		if ($passiivi == 1) ftp_pasv($ftp, true);
		echo "L�hetet��n Esi-sanoma: $omaesia<br>";
		if(ftp_put($ftp, $pankinesia, $omaesia, FTP_ASCII)) {
			echo "Esi-sanoman l�hetys onnistui.<br>Haetaan vastaus: $omaesip<br>";
			if(ftp_get($ftp, $omaesip, $pankinesip, FTP_ASCII)) {
				echo  "Esi-sanoman vastaus saatiin.<br>";
				$tulos=kasitteleesip($omaesip, $yritirow);
				if ($tulos == '') {
					echo "L�hetet��n siirtopyynt�: $omasiirto<br>";
					if(ftp_put($ftp, $pankinsiirto, $omasiirto, FTP_ASCII)) {
						echo "Siirtopyynt� l�hetettiin.<br>L�hetet��n aineisto: $omaaineistodata<br>";
						if(ftp_put($ftp, $pankinaineistodata, $omaaineistodata, FTP_ASCII)) {
							echo "Aineistodata l�hetettiin<br>Haetaan kuittaus: $omakuittaus<br>";
						}
						else {
							echo "Aineistodata on virheellist� tai se muusta syyst� hyl�ttiin.<br>";
						}

					}
					else {
						echo "Siirtopyynt� ev�ttiin<br>";
					}
					if(ftp_get($ftp, $omakuittaus, $pankinkuittaus, FTP_ASCII)) {
						echo "Kuittaus haettu<br>";
						echo "Pankin vastaus --> ".substr(file_get_contents($omakuittaus),177,60)."<br>";
					}
					else {
						echo "Kuittausta ei saatu<br>";
					}
				}
				else {
					echo "Pankki vastasti ESIp-tiedostolla, mutta todennus ei onnistunut<br>";
				}
			}
			else {
				echo "Vastausta esi-sanomaan ei saatu<br>";
			}
		}
		else {
			echo "Esi-sanoman l�hetys ei onnistunut<br>";
		}
		echo "Lopetetaan yhteys<br>";

		// Sampo haluaa lopetustiedoston
		if ($pankki=='8') {
			if(ftp_put($ftp, "lopetus", $yhteydenlopetus, FTP_ASCII)) {
				echo "Pyydettiin yhteydenlopetus.<br>";
			}
			else {
				echo "Yhteydenlopetuspyynt� ep�onnistui.<br>";
			}
		}
		ftp_quit($ftp);
	}
	else {
		echo "Ei yhteytt�!<br>";
	}
	return "";
}
?>
