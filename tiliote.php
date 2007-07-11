<?php

	$ok = 0;

	// tehdään tällänen häkkyrä niin voidaan scriptiä kutsua vaikka perlistä..
	if ((trim($argv[1])=='perl') and (trim($argv[2])!='')) {

		if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

		require ("inc/connect.inc");
		require ("inc/functions.inc");

		$userfile = trim($argv[2]);
		$filenimi = $userfile;

		$ok = 1;
	}
	else {
		require ("inc/parametrit.inc");

		echo "<font class='head'>Tiliotteen, LMP:n, kurssien ja viitemaksujen käsittely</font><hr><br><br>";
	}

	// katotaan onko faili uploadttu
	if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
		$userfile = $_FILES['userfile']['name'];
		$filenimi = $_FILES['userfile']['tmp_name'];
		$ok = 1;
	}

	if ($ok == 1) {

		$fd = fopen ($filenimi, "r");

		if (!($fd)) {
			echo "<font class='message'>Tiedosto '$filenimi' ei auennut!</font>";
			exit;
		}
		$tietue = fgets($fd);	
		
		// luetaanko kursseja?
		if (substr($tietue, 5, 12) == 'Tilivaluutan') {
			// luetaan sisään kurssit
			lue_kurssit($filenimi, $fd);
			fclose($fd);
			require 'inc/footer.inc';
			die();
		}
		
		$query= "LOCK TABLE tiliotedata WRITE, yriti READ";
		$tiliotedataresult = mysql_query($query) or pupe_error($query);

		// Etsitään aineistonumero
		$query = "SELECT max(aineisto)+1 aineisto FROM tiliotedata";
		$aineistores = mysql_query($query) or pupe_error($query);
		$aineistorow = mysql_fetch_array($aineistores);

		
		$xtyyppi = 0;
		$virhe	 = 0;	
		
		while (!feof($fd)) {
			$tietue = str_replace ("{", "ä", $tietue);
			$tietue = str_replace ("|", "ö", $tietue);
			$tietue = str_replace ("}", "å", $tietue);
			$tietue = str_replace ("[", "Ä", $tietue);
			$tietue = str_replace ("\\","Ö", $tietue);
			$tietue = str_replace ("]", "Å", $tietue);
			$tietue = str_replace ("'", " ", $tietue);

			if (substr($tietue,0,3) == 'T00' or substr($tietue,0,3) == 'T03' or substr($tietue,0,1) == '0') {
				// Konekielinen tiliote
				if (substr($tietue,0,3) == 'T00') {
					$xtyyppi 	= 1;
					$alkupvm 	= dateconv(substr($tietue,26,6));
					$loppupvm 	= dateconv(substr($tietue,32,6));
					$tilino 	= substr($tietue, 9, 14);
				}

				// Laskujen maksupalvelu LMP
				if (substr($tietue,0,3) == 'T03') {
					$xtyyppi 	= 2;
					$alkupvm	= substr($tietue,38,6);
					$loppupvm 	= $alkupvm;
					$tilino 	= substr($tietue, 9, 14);
				}

				// Saapuvat viitemaksut
				if(substr($tietue,0,1) == '0') {
					$xtyyppi 	= 3;
					$alkupvm	= "20".dateconv(substr($tietue,1,6));
					$loppupvm 	= $alkupvm;

					//Luetaan tilinumero seuraavalta riviltä ja siirretään pointteri takaisin nykypaikkaan
					$pointterin_paikka = ftell($fd);
					$tilino 	= fgets($fd);
					$tilino 	= substr($tilino,1,14);
					fseek($fd, $pointterin_paikka);
				}

				$query = "	SELECT *
							FROM yriti
							WHERE tilino = '$tilino'";
				$yritiresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($yritiresult) != 1) {
					echo "<font class='error'> Tiliä '$tilino' ei löytynyt!</font><br>";
					$xtyyppi = 0;
					$virhe++;
				}
				else {
					$yritirow = mysql_fetch_array ($yritiresult);
				}

				// Onko tämä aineisto jo ajettu?
				$query= "	SELECT *
							FROM tiliotedata
							WHERE tilino = '$tilino'
							and alku 	 = '$alkupvm'
							and loppu 	 = '$loppupvm'
							and tyyppi 	 = $xtyyppi";
				$tiliotedatares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($tiliotedatares) > 0) {
					$tiliotedatarow = mysql_fetch_array($tiliotedatares);
					
					if ($tiliotedatarow["aineisto"] == $aineistorow["aineisto"]) {
						echo "<font class='error'>Aineisto esiintyy tiedostossa moneen kertaan.<br>Tiedosto viallinen, ei voida jatkaa, ota yhteyttä helpdeskiin!<br>Tili = $tilino Ajalta $alkupvm - $loppupvm Yritystunnus $yritirow[yhtio]</font><br><br>";	
					}
					else {
						echo "<font class='error'>Tämä aineisto on jo aiemmin käsitelty! Tili = $tilino Ajalta $alkupvm - $loppupvm Yritystunnus $yritirow[yhtio]</font><br><br>";
					}
					
					$xtyyppi=0;
					$virhe++;
				}
			}

			if ($xtyyppi > 0 and $xtyyppi <= 3) {
				// Kirjoitetaan tiedosto kantaan
				$query = "INSERT into tiliotedata (yhtio, aineisto, tilino, alku, loppu, tyyppi, tieto) values ('$yritirow[yhtio]', '$aineistorow[aineisto]', '$tilino', '$alkupvm', '$loppupvm', '$xtyyppi', '$tietue')";
				$tiliotedataresult = mysql_query($query) or pupe_error($query);
			}

			$tietue = fgets($fd, 4096);
		}
		
		
		//Jos meillä tuli virheitä 
		if ($virhe > 0) {
			echo "<font class='error'>Aineisto oli virheellinen. Sitä ei voitu tallentaa järjestelmään.</font>";
			
			//Poistetaan aineistot tiliotedatasta
			$query = "delete from tiliotedata where aineisto ='$aineistorow[aineisto]'";
			$tiliotedataresult = mysql_query($query) or pupe_error($query);
			
			require("inc/footer.inc");	
			exit;
		}
		
		$query = "UNLOCK TABLES";
		$tiliotedataresult = mysql_query($query) or pupe_error($query);


		// Käsitellään uudet tietueet
		$query = "	SELECT *
					FROM tiliotedata
					WHERE aineisto = '$aineistorow[0]'
					ORDER BY tunnus";
		$tiliotedataresult = mysql_query($query) or pupe_error($query);

		while ($tiliotedatarow = mysql_fetch_array($tiliotedataresult)) {
			$tietue = $tiliotedatarow['tieto'];

			if ($tiliotedatarow['tyyppi'] == 1) {
				require("inc/tiliote.inc");
			}
			if ($tiliotedatarow['tyyppi'] == 2) {
				require("inc/LMP.inc");
			}
			if ($tiliotedatarow['tyyppi'] == 3) {
				require("inc/viitemaksut.inc");
			}
		}

		if ($xtyyppi == 1) {
			//echo "nyt PITÄISI syntyä vastavienti<br>";
			$tkesken = 0;
			echo "<tr><td colspan = '6'>";
			$maara = $vastavienti;

			require("inc/teeselvittely.inc");

			echo "</td></tr>";
			echo "</table>";
		}

		if ($xtyyppi == 2) {
			$tkesken = 0;
			$maara = $vastavienti;

			require("inc/teeselvittely.inc");

			echo "</table>";
		}

		if ($xtyyppi == 3) {
			require("inc/viitemaksut_kohdistus.inc");
		}
	}
	else {
		echo "<form enctype='multipart/form-data' name='sendfile' action='$PHP_SELF' method='post'>";

		echo "<table>";
		echo "<tr><th>Pankin aineisto:</th>
				<td><input type='file' name='userfile'></td>
				<td><input type='submit' value='Käsittele tiedosto'></td></tr>";
		echo "</table>";

		echo "</form>";
	}

	require("inc/footer.inc");

function lue_kurssit($file, $handle) {
	global $yhtiorow, $kukarow;
	
	// luetaan koko file arrayhyn
	$rivit = file($file);
	
	// 2 ekaa riviä pois
	array_shift($rivit);
	array_shift($rivit);
	
	$datetime = date('Y-m-d H:i:s');
	
	$valuutat = array();
	foreach ($rivit as $rivi) {
		// valuutan nimi
		$valuutta = substr($rivi, 0, 3);
		
		if (in_array($valuutta, $valuutat)) {
			continue;
		}
		
		// itse kurssi 
		$kurssi = (float) str_replace(',', '.', trim(substr($rivi, 5, 15)));
		
		// suhde euroon
		$kurssi = round(1 / $kurssi, 6);
		
		$query = "update valuu set kurssi='$kurssi', muutospvm = '$datetime', muuttaja = '{$kukarow['kuka']}'
				where yhtio='{$kukarow['yhtio']}' and nimi='$valuutta'";
				
		$result = mysql_query($query) or pupe_error($query);
		
		// tämä valuutta on nyt päivitetty!
		$valuutat[] = $valuutta;
		
		echo "<font class='message'>Haettiin kurssi valuutalle $valuutta: $kurssi</font>";

		if (mysql_affected_rows() != 0) {
			echo "<font class='message'> ... Kurssi päivitetty.</font>";
		}
		echo "<br>";
	}
}
?>
