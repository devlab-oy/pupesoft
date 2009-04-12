<?
function siirto ($yritirow, $aineiisto, $pvm) {
	// Kasvatetaan tiedostojen päätettä yhdellä pankkiyhteystietokannassa
	$nro = $yritirow['nro'] +1;
	// Tehdään numerosta kolminumeroinen esitys
	$nroo='';
	if($nro<100){
		$nroo="0";
		if($nro<10){
			$nroo.="0";
		}
	}
	$nro=$nroo.$nro;
	
	// TIEDOSTONIMET
	echo "<pre>";
	$osoite="/var/www/html/pupesoft/tiedostot";
	$etiedosto="ESI.A";
	$sptiedosto="SIIRTOPYYNTO.$nro";
	// Alustetaan ja tehdään Siirtopyyntö-tiedosto
	// Siirtopyyntöön lisätään tilinumero, jolta tiliote haetaan
	//$tili="195030-10";
	$tili=$yriti['tilino'];
	echo "Tehdään siirtopyyntö $osoite/$sptiedosto<br>";
	if ($pvm == '') $pvm ='000000';

	$siirtopyynto = siirtopyynto($tili, DEMO, $pvm);

	// Alustetaan ESI-tiedoston teko
	$esi="perl sanoma.pl $osoite/$etiedosto ESI $dbhost $dbuser $dbpass $dbkanta $tunnus";
	// Tehdään ESI-tiedosto
	echo "Tehdään ESI-tiedosto $osoite/$etiedosto<br>";
	passthru($esi, $retval);
	if ($retval != 0) die ("ESI sanoman luonti ei onnistu");

	echo "Suojataan ESI-tiedosto $esi<br>";
	$esi = salaa($esi, ESIa, $yritirow['kayttoavain']);
	
	// Jos ei haluta avainvaihtoa, lisätään sanoman perään nolla	
	$esi .= "0";
	
	file_put_contents("$osoite/$etiedosto", $esi);
	// LÄHETETÄÄN PAKETIT FTP:LLÄ
	// Tiedostojen nimet
	$esia= "$osoite/$etiedosto";
	$esip= "ESI.P";
	$siirto= "$osoite/$sptiedosto";
	$aineisto = $aineisto.$nro";
	$kuittaus = "KUITTAUS".$nro;
	// FTP-yhteydenottomuuttujat
	$host="solo.nordea.fi";
	$log="anonymous";
	$pass="SITE PASSIVE";
	echo "Avataan FTP-yhteys $host";
	$ftp = ftp_connect($host);
	if($ftp) {
	// Jos jokin asia epäonnistuu, katkaistaan heti yhteys
	  echo "Yhteys muodostettu: $host<br>";
	  $login_ok = ftp_login($ftp,$log,$pass);
	  ftp_pasv($ftp, true);
	  echo "Lähetetään Esi-sanoma: $esia<br>";
	  if(ftp_put($ftp, "ESI.A", $esia, FTP_ASCII)) {
			echo "Esi-sanoman lähetys onnistui. Haetaan vastaus: $esip<br>";
			if(ftp_get($ftp, "$osoite/$esip", $esip, FTP_ASCII)) {
					echo  "Esi-sanoman vastaus saatiin.Lähetetään siirtopyyntö: $siirto<br>";
					if(ftp_put($ftp, "SIIRTO", $siirto, FTP_ASCII)) {
							echo "Siirtopyyntö lähetettiin. Haetaan aineisto: $aineisto<br>";
							if(ftp_get($ftp, "$osoite/$aineisto", $aineisto, FTP_ASCII)) {
									echo "Aineiston haku onnistui. Haetaan kuittaus: $kuittaus<br>";
									if(ftp_get($ftp, "$osoite/$kuittaus", $kuittaus, FTP_ASCII)) {
											echo "Kuittaus haettu<br>";
									}
									else{
											echo "Kuittausta ei saatu<br>";
									}
							}
							else{
									echo "Aineiston haku ei onnistunut.<br>";
									echo "Haetaan kuittaus: $kuittaus<br>";
						  			if(ftp_get($ftp, "$osoite/$kuittaus", $kuittaus, FTP_ASCII)) {
											echo "Kuittaus saatiin<br>";
									}
									else{
											echo "Kuittausta ei saatu<br>";
									}
							}
					}
					else{
						echo "Siirtopyyntö evättiin<br>";
					}
			}
			else{
					echo "Vastausta esi-sanomaan ei saatu<br>";
			}
	  }
	  else{
			echo "Esi-sanoman lähetys ei onnistunut<br>";
	  }
	  echo "<br>Lopetetaan yhteys";
	  ftp_quit($ftp);
	}
	else{
			echo "Ei yhteyttä!<br>";
	}
	return "";
}
?>
