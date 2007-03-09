<?php
function lahetys ($nro, $nimi) {
	global $yritirow, $dbhost, $dbuser, $dbpass, $dbkanta, $tunnus;
	// Kasvatetaan tiedostojen päätettä yhdellä pankkiyhteystietokannassa
	$nro++;
	// Tehdään numerosta kolminumeroinen esitys
	$nroo='';
	if($nro<100){
		$nroo="0";
		if($nro<10){
			$nroo.="0";
		}
	}
	$nro=$nroo.$nro;

	$osoite="/var/www/html/pupesoft/tiedostot";
	$etiedosto="ESI.A";
	$sptiedosto="SIIRTOPYYNTO.$nro";
	$suotiedosto="SUO";
	echo "<pre>";
	// Alustetaan SUO-tiedoston teko
	$suo="perl sanoma.pl $osoite/$suotiedosto SUO $dbhost $dbuser $dbpass $dbkanta $tunnus";
	// Tehdään SUO-tiedosto
	passthru($suo);
	
	//Yhdistetään SUO- ja maksuaineisto
	$suofile = file_get_contents("$osoite/$suotiedosto");
	if(!$suofile) die ("SUO-tiedostoa ei muodostunut!");
	$maksufile = file_get_contents("$nimi");
	if(!$suofile) die ("Maksutiedosto on kateissa!");
	$maksufile = $suofile . $maksufile;
	//Hoidetaan ääkköset
	$oikeat = array("Ä", "Ö", "Å", "ä", "ö", "å");
	$pankin = array("[", "\\", "]", "{", "|", "}");
	$maksufile = str_replace($oikeat, $pankin, $maksufile);
	$maksufile = strtoupper($maksufile);
	file_put_contents("$nimi", $maksufile);
	
	// Alustetaan ESI-tiedoston teko
	$esi="perl sanoma.pl $osoite/$etiedosto ESI $dbhost $dbuser $dbpass $dbkanta $tunnus";
	// Tehdään ESI-tiedosto
	passthru($esi);
	echo "Suojataan ESI-tiedosto $osoite/$etiedosto<br>";
	$esi = "perl sala.pl ESIa $osoite/$etiedosto $dbhost $dbuser $dbpass $dbkanta $tunnus";
	passthru($esi);

	// Jos ei haluta avainvaihtoa, lisätään sanoman perään nolla
	$esifile = file_get_contents("$osoite/$etiedosto");
	if(!$esifile) die ("ESI-tiedostoa ei muodostunut!");	
	$esifile .= "0";
	file_put_contents("$osoite/$etiedosto", $esifile);

	# Alustetaan ja tehdään Siirtopyyntö-tiedosto
	$tili='0';
	$a="perl siirtopyynto.pl LMP300 $osoite/$sptiedosto $tili TESTI 000000";
	echo "$a<br>";
	passthru($a);

	// Alustetaan ja suoritetaan kerta-avaimen muodostus
	$kerta="perl kerta.pl $dbhost $dbuser $dbpass $dbkanta $tunnus";
	echo "$kerta<br>";
	passthru($kerta);

	// Alustetaan VAR-sanoman teko
	$a="perl tiiv.pl $nimi $dbhost $dbuser $dbpass $dbkanta $tunnus";
	echo "$a<br>";
	passthru($a);

	//Alustetaan VAR-sanoman tarkisteen laskenta 
	$a="perl sala.pl VARa $nimi $dbhost $dbuser $dbpass $dbkanta $tunnus";
	echo "$a<br>";
	passthru($a);

	//Yhdistetään siirtopyyntö ja aineisto
	$siirtopyyntofile = file_get_contents("$osoite/$sptiedosto");
	if(!$siirtopyyntofile) die ("SIIRTOPYYNTÖtiedostoa ei muodostunut!");
	$maksufile = file_get_contents("$nimi");
	if(!$maksufile) die ("Aineisto katosi!");
	$maksufile = $siirtopyyntofile . "\n" . $maksufile;	
	file_put_contents("$nimi", $maksufile);
	
# LÄHETETÄÄN PAKETIT FTP:LLÄ
# Tiedostojen nimet

	$esia= "$osoite/$etiedosto";
	$siirto= "$osoite/$sptiedosto";
	$kuittaus = "KUITTAUS.$nro";
	$palaute = "LMPPAL.$nro";
	# FTP-yhteydenotto muuttujat
	$host="solo.merita.fi";
	$log="anonymous";
	$pass="SITE PASSIVE";
	echo "Avataan FTP-yhteys $host";
	$ftp = ftp_connect($host);
	if($ftp) {
	// Jos jokin asia epäonnistuu, katkaistaan heti yhteys
	  echo "\nYhteys muodostettu: $host";
	  $login_ok = ftp_login($ftp,$log,$pass);
	  ftp_pasv($ftp, true);
	  echo "\nLähetetään Esi-sanoma: $esia";
	  if(ftp_put($ftp, "ESI.A", $esia, FTP_ASCII)) {
			echo "\nEsi-sanoman lähetys onnistui. Haetaan vastaus: ESI.P";
			if(ftp_get($ftp, "$osoite/ESI.P", "ESI.P", FTP_ASCII)) {
				echo  "\nEsi-sanoman vastaus saatiin.Lähetetään siirtopyyntö+aineisto: $siirto";
				if(ftp_put($ftp, "SIIRTO", $nimi, FTP_ASCII)) {
					echo "\nAineiston lähetys onnistui. Haetaan kuittaus: $kuittaus";
					if(ftp_get($ftp, "$osoite/$kuittaus", $kuittaus, FTP_ASCII)) {
						echo "\nKuittaus haettu.";
					}
					else{
						echo "\nKuittausta ei saatu.";
					}
					if(ftp_get($ftp, "$osoite/$palaute", $palaute, FTP_ASCII)) {
						echo "\nSaimme palautteen";
					}
					else{
						echo "\nPalautetta ei saatu";
					}
				}
				else{
					echo "\nAineiston lähetys ei onnistunut. Haetaan kuittaus: $kuittaus";
					if(ftp_get($ftp, "$osoite/$kuittaus", $kuittaus, FTP_ASCII)) {
						echo "\nKuittaus saatiin";
					}
					else{
						echo "\nKuittausta ei saatu";
					}
				} 
			}
			else{
					echo "\nVastausta esi-sanomaan ei saatu. $!";
			}
	  }
	  else{
			echo "\nEsi-sanoman lähetys ei onnistunut $!";
	  }
	  echo "\nLopetetaan yhteys.";
	  ftp_quit($ftp);
	}
	else{
		echo "\nEi yhteyttä!";
	}

	# Tulostetaan palaute
	$kuit = file_get_contents("$osoite/$palaute");
	if($kuit) {
		echo "<pre>$kuit</pre>";
	}
	else echo ("\nPalautetiedostoa ei muodostunut!");
	
	# Etsitään uutta Käyttöavainta ESI-tiedostosta
	passthru("perl etsikaytto.pl $osoite/eptiedosto $dbhost $dbuser $dbpass $dbkanta $tunnus");
	return ($nro);
}

function siirto ($nro) {
	global $dbhost, $dbuser, $dbpass, $dbkanta, $tunnus;
	// Kasvatetaan tiedostojen päätettä yhdellä pankkiyhteystietokannassa
	$nro++;
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
	$tili="";
	echo "Tehdään siirtopyyntö $osoite/$sptiedosto<br>";
	$siirtop="perl siirtopyynto.pl TITO $osoite/$sptiedosto $tili DEMO 000000";
	passthru($siirtop);

	// Alustetaan ESI-tiedoston teko
	$esi="perl sanoma.pl $osoite/$etiedosto ESI $dbhost $dbuser $dbpass $dbkanta $tunnus";
	// Tehdään ESI-tiedosto
	echo "Tehdään ESI-tiedosto $osoite/$etiedosto<br>";
	passthru($esi, $retval);
	if ($retval != 0) die ("ESI sanoman luonti ei onnistu");
	echo "Suojataan ESI-tiedosto $osoite/$etiedosto<br>";
	$esi = "perl sala.pl ESIa $osoite/$etiedosto $dbhost $dbuser $dbpass $dbkanta $tunnus";
	passthru($esi);
	
	// Jos ei haluta avainvaihtoa, lisätään sanoman perään nolla
	$esifile = file_get_contents("$osoite/$etiedosto");
	if(!$esifile) die ("ESI-tiedostoa ei muodostunut!");
	
	$esifile .= "0";
	file_put_contents("$osoite/$etiedosto", $esifile);
	// LÄHETETÄÄN PAKETIT FTP:LLÄ
	// Tiedostojen nimet
	$esia= "$osoite/$etiedosto";
	$esip= "ESI.P";
	$siirto= "$osoite/$sptiedosto";
	$aineisto ="TITO.$nro";
	$kuittaus = "KUITTAUS.$nro";
	// FTP-yhteydenottomuuttujat
	$host="solo.nordea.fi";
	$log="anonymous";
	$pass="SITE PASSIVE";
	echo "Avataan FTP-yhteys $host";
	$ftp = ftp_connect($host);
	if($ftp) {
	// Jos jokin asia epäonnistuu, katkaistaan heti yhteys
	  echo "\nYhteys muodostettu: $host";
	  $login_ok = ftp_login($ftp,$log,$pass);
	  ftp_pasv($ftp, true);
	  echo "\nLähetetään Esi-sanoma: $esia";
	  if(ftp_put($ftp, "ESI.A", $esia, FTP_ASCII)) {
			echo "\nEsi-sanoman lähetys onnistui. Haetaan vastaus: $esip";
			if(ftp_get($ftp, "$osoite/$esip", $esip, FTP_ASCII)) {
					echo  "\nEsi-sanoman vastaus saatiin.Lähetetään siirtopyyntö: $siirto";
					if(ftp_put($ftp, "SIIRTO", $siirto, FTP_ASCII)) {
							echo "\nSiirtopyyntö lähetettiin. Haetaan aineisto: $aineisto";
							if(ftp_get($ftp, "$osoite/$aineisto", $aineisto, FTP_ASCII)) {
									echo "\nAineiston haku onnistui. Haetaan kuittaus: $kuittaus";
									if(ftp_get($ftp, "$osoite/$kuittaus", $kuittaus, FTP_ASCII)) {
											echo "\nKuittaus haettu.";
									}
									else{
											echo "\nKuittausta ei saatu.";
									}
							}
							else{
									echo "\nAineiston haku ei onnistunut.";
									echo "\nHaetaan kuittaus: $kuittaus";
						  			if(ftp_get($ftp, "$osoite/$kuittaus", $kuittaus, FTP_ASCII)) {
											echo "\nKuittaus saatiin";
									}
									else{
											echo "\nKuittausta ei saatu";
									}
							}
					}
					else{
						echo "\nSiirtopyyntö evättiin.";
					}
			}
			else{
					echo "\nVastausta esi-sanomaan ei saatu. $!";
			}
	  }
	  else{
			echo "\nEsi-sanoman lähetys ei onnistunut $!";
	  }
	  echo "\nLopetetaan yhteys.";
	  ftp_quit($ftp);
	}
	else{
			echo "\nEi yhteyttä!";
	}
	return $nro;
}

require ("../inc/parametrit.inc");
echo "<font class=head>".t("Pankkiyhteys")."</font><hr>";

if (is_array($tilit)) {
	foreach($tilit as $tili) {
		list($maksu_tili, $olmapvm) = split("#",$tili);
		$tunnus=$maksu_tili;
		$query="SELECT * FROM yriti WHERE tunnus = '$maksu_tili'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 1) {
			$yrow = mysql_fetch_array($result);
			require ("makai.inc");
			$nro=lahetys ($yrow['nro'],$nimi);
			$query="UPDATE yriti SET nro = '$nro' WHERE tunnus = '$maksu_tili'";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			echo "Tili katosi $maksu_tili";
			exit;
		} 
	}
}

if (isset($maksu_tunnus)) {
	$tunnus=$maksu_tunnus;
	if ($tee=='S') {
		$osa1=strtolower($osa1);
		$osa2=strtolower($osa2);
		$osa3=strtolower($osa3);
		echo "<pre>";
		$tulos = passthru("perl siirtoa.pl $osa1 $osa2 $osa3 0 $dbhost $dbuser $dbpass $dbkanta $tunnus");
		echo "</pre>";
		if ($tulos!='') {
			die ("Siirtoavaimen päivitys ei onnistu");
		}
		else echo "<font class='message'>Siirtoavain päivitettiin</font><br>";
	}
	
	$query="SELECT * FROM yriti WHERE tunnus = '$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$yritirow = mysql_fetch_array($result);
	if ($yritirow['siirtoavain'] != '') { 
		if ($yritirow['kayttoavain'] == '') {
			echo "<pre>";
			$tulos = passthru("perl ekakaytto.pl $dbhost $dbuser $dbpass $dbkanta $tunnus");
			echo "</pre>";
			if ($tulos != '') {
				die ("Käyttöavaimen päivitys ei onnistu");
			}
			else echo "<font class='message'>Käyttöavain päivitettiin</font><br>";
		}
		$query="SELECT * FROM yriti WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$yritirow = mysql_fetch_array($result);
		$nro=siirto($yritirow['nro']);
		$query="UPDATE yriti SET nro = '$nro' WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
	}
	else {
		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
		<input type = 'hidden' name = 'tunnus' value = '$tunnus'>
		<input type = 'hidden' name = 'tee' value = 'S'>
		<table>
		<tr><th>Anna avaimen ensimminen osa</th>
		<td><input type = 'text' name = 'osa1' value=''></td></tr>
		<tr><th>Anna avaimen toinen osa</th>
		<td><input type = 'text' name = 'osa2' value=''></td></tr>
		<tr><th>Anna tarkiste</th>
		<td><input type = 'text' name = 'osa3' value=''></td></tr>
		<tr><td></td></td><td><input type = 'submit' value = 'Valitse'></td></tr>
		</table>
		</form>";
	}
}
	
if (!isset($tunnus)) {
	// Maksupäivät
	
	$query = "UPDATE lasku set olmapvm=now() where yhtio='$kukarow[yhtio]' and tila = 'P' 
					and maakoodi = 'fi' and maksaja = '$kukarow[kuka]' and olmapvm < now()";
	$result = mysql_query($query) or pupe_error($query);
					
	$query = "SELECT maksu_tili, yriti.tilino, olmapvm, sum(if(alatila='K', summa-kasumma, summa)), count(*)
			FROM lasku, yriti
			WHERE lasku.yhtio = '$kukarow[yhtio]' 
			and tila = 'P' 
			and maakoodi = 'fi' 
			and maksaja = '$kukarow[kuka]'
			and lasku.yhtio=yriti.yhtio
			and lasku.maksu_tili=yriti.tunnus
			GROUP BY 1,2";
	$result = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($result) != 0) {
		echo "Lähetä kotimaan maksuja<br><form name = 'maksut' action = '$PHP_SELF' method='post'>";
		echo "<table>";
		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
		    for ($i=0; $i<mysql_num_fields($result); $i++) {
		    	if ($i==0) {
		    		echo "<td><input type='checkbox' name = 'tilit[]' value='$trow[0]#$trow[2]' checked></td>";
		    	}
		    	else
		    		echo "<td>$trow[$i]</td>";
		    }
		    echo "</tr>";
	    }
	    echo "</table><input type = 'submit' value = 'Lähetä'></form><hr>";
	}
	else {
		echo "<br><font class='message'>Ei siirrettäviä maksuaineistoja</font><br><br>";
	}
	
	$query = "	SELECT tunnus, nimi, tilino
				FROM yriti
				WHERE yhtio = '$kukarow[yhtio]' and (tilino like '1%' or tilino like '2%')
				ORDER BY nimi";
	$result = mysql_query($query) or pupe_error($query);
	
	if(mysql_num_rows($result) == 0) {
		echo "<font class = 'error'>".t("Ei tuettuja pankkitilejä!")."'$kukarow[yhtio]'</font>";
	}
	else {
	
		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
		<table><tr><td>Nouda tiliote</td><td><select name='maksu_tunnus'>";
		while ($row=mysql_fetch_array($result)) {
			$sel="";
			if ($tunnus == $row['tunnus']) {
				$sel = "selected";
			}
			echo "<option value = '$row[tunnus]' $sel>$row[nimi] ($row[tilino])";
		}
		echo "</select></td></tr></table><input type = 'submit' value = 'Valitse'></form>";
	}
}
require ("../inc/footer.inc");
?>
