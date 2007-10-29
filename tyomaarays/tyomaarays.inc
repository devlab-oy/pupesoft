<?php

	if (($tee ==  "VALMIS" or $tee == "LEPAA") and $toim != "REKLAMAATIO") {
		//tulostetaan tilaus kun se on valmis
		$otunnus = $kukarow["kesken"];

		if (count($komento) == 0) {
			echo "<font class='head'>Työmääräys:</font><hr>";
			
			$tulostimet[0] = "Työmääräys";
			require("../inc/valitse_tulostin.inc");
		}
		
		if ($laskurow["tila"] == 'U') {
			$where = " uusiotunnus='$laskurow[tunnus]' ";
		}
		else {
			$where = " otunnus='$laskurow[tunnus]' ";
		}
		
		require_once ("tulosta_tyomaarays.inc");

		$otunnus = $laskurow["tunnus"];
		
		//hatetaan asiakkaan tiedot
		$query = "  SELECT lahetetyyppi, luokka, puhelin, if(asiakasnro!='', asiakasnro, ytunnus) asiakasnro
					FROM asiakas
					WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$asrow = mysql_fetch_array($result);
		
		// katotaan miten halutaan sortattavan
		$sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);
		
		//työmääräyksen rivit
		$query = "  SELECT tilausrivi.*,
					round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),'$yhtiorow[hintapyoristys]') rivihinta,
					tuote.sarjanumeroseuranta,
					$sorttauskentta,
					if (tuotetyyppi='K', 1, 0) tuotetyyppi
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					WHERE tilausrivi.otunnus = '$otunnus'
					and tilausrivi.yhtio 	= '$kukarow[yhtio]'
					and tilausrivi.yhtio 	= tuote.yhtio
					and tilausrivi.tuoteno  = tuote.tuoteno
					ORDER BY tuotetyyppi, sorttauskentta $yhtiorow[lahetteen_jarjestys_suunta], tilausrivi.tunnus";
		$result = mysql_query($query) or pupe_error($query);

		$tilausnumeroita = $otunnus;
		
		//generoidaan rivinumerot
		$rivinumerot = array();

		$kal = 1;

		while ($row = mysql_fetch_array($result)) {
			$rivinumerot[$row["tunnus"]] = $kal;
			$kal++;
		}

		mysql_data_seek($result,0);

		unset($pdf);
		unset($page);

		$sivu  = 1;
		$paino = 0;
		

		// Aloitellaan lähetteen teko
		$page[$sivu] = alku($tyyppi);

		while ($row = mysql_fetch_array($result)) {
			rivi($page[$sivu], $tyyppi);
		}

		loppu($page[$sivu], 1);
		
		//tulostetaan sivu
		print_pdf($komento["Työmääräys"]);
	}

	if (($toim == "TYOMAARAYS" or $toim == "REKLAMAATIO") and $tee == 'LEPAA') {
		$query = "UPDATE kuka SET kesken=0 WHERE session='$session'";
		$result = mysql_query($query) or pupe_error($query);

		$tee				= '';
		$tilausnumero		= '';
		$laskurow			= '';
		$kukarow['kesken']	= '';
		
		if ($lopetus != '') {
			// Jotta urlin parametrissa voisi päässätä toisen urlin parametreineen
			$lopetus = str_replace('////','?', $lopetus);
			$lopetus = str_replace('//','&',  $lopetus);

			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$lopetus'>";
			exit;
		}

	}

	if ($toim == "TYOMAARAYS" and $tee == 'VALMIS') {	
		$query  = "	UPDATE lasku 
					SET tila='L'	
					WHERE tunnus='$laskurow[tunnus]'
					and tila='A'
					and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	
		//Katotaan löytyykö tilausrivejä
		$query = "select * from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$laskurow[tunnus]'";
		$keres = mysql_query($query) or pupe_error($query);
		
		//Yhtään riviä ei ole
		if(mysql_num_rows($keres) == 0) {
			$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$result = mysql_query($query) or pupe_error($query);
			
			// tilaus ei enää kesken...
			$query	= "update kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			require("tilaus-valmis.inc");
		}
		$tee = "";
	}
	
	if ($toim == "REKLAMAATIO" and $tee == 'VALMIS') {
		$query  = "	UPDATE lasku 
					SET tila='L'	
					WHERE tunnus='$laskurow[tunnus]'
					and tila='C'
					and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	
		//Katotaan löytyykö tilausrivejä
		$query = "select * from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$laskurow[tunnus]'";
		$keres = mysql_query($query) or pupe_error($query);
		
		//Yhtään riviä ei ole
		if(mysql_num_rows($keres) == 0) {
			$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$result = mysql_query($query) or pupe_error($query);
			
			// tilaus ei enää kesken...
			$query	= "update kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			require("tilaus-valmis.inc");
		}
		$tee = "";
	}
	
	if ($toim == "SIIRTOTYOMAARAYS" and $tee == 'VALMIS') {	
		$query  = "	UPDATE lasku 
					SET alatila = 'J'	
					WHERE tunnus = '$laskurow[tunnus]'
					and tila = 'S'
					and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	
		//Katotaan löytyykö tilausrivejä
		$query = "select * from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$laskurow[tunnus]'";
		$keres = mysql_query($query) or pupe_error($query);
		
		//Yhtään riviä ei ole
		if(mysql_num_rows($keres) == 0) {
			$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$result = mysql_query($query) or pupe_error($query);
			
			// tilaus ei enää kesken...
			$query	= "update kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			require("tilaus-valmis-siirtolista.inc");
		}
				
		$tee = "";
	}
						
	if ($toim == "TYOMAARAYS" and $tee == 'HUOLTO' and $malli != '' and $huolto != '') {
	
		$query = "	SELECT tuoteno, maara
					FROM huollot
					WHERE yhtio='$kukarow[yhtio]' and malli='$malli' and huolto='$huolto'";
		$result = mysql_query ($query) or pupe_error($query);		
		
		if (mysql_num_rows($result) > 0) {
			//keksitään uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$pdffilenimi = "/tmp/MiKrOtIlAuS-".md5(uniqid(mt_rand(), true)).".txt";
			
			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w");
					
			while($row = mysql_fetch_array($result)) {
				fwrite($fh, "$row[tuoteno]\t$row[maara]\n");
			}
			fclose($fh);
			
			$_FILES['userfile']['tmp_name'] = $pdffilenimi;
			$_FILES['userfile']['size'] = 100;
			$_FILES['userfile']['name'] = $pdffilenimi;
			$tee = "file";
		}
		else {
			$tee = "";
		}
	}
	elseif ($toim == "TYOMAARAYS" and $tee == 'HUOLTO') {
		$tee = "";
	}
	
	if ($toim == "TYOMAARAYS" and $tee == "VAHINKO") {

		$polku = "kuvat/";
		
		if ($tila == "") {
			
			echo "<font class='head'>Työmääräyksen $kukarow[kesken] vahinkotiedot:</font><hr>";
			
			$query = "	select *
						from vahinkotiedot
						where yhtio = '$kukarow[yhtio]' 
						and otunnus = '$kukarow[kesken]'";
			$tresult = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($tresult);
			
			
			echo "	<br><br><table>";
			echo "	<form action='$PHP_SELF' method='post' enctype='multipart/form-data'>
					<input type='hidden' name='tee' value = 'VAHINKO'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='vahtunnus' value='$row[tunnus]'>
					<input type='hidden' name='tila' value = 'LISAA'>				
					
					
					<tr><th>Vak.yht. korjattava:</th><td><input type='text' name='vakuutus' size='38' value='$row[vakuutus]'></td></tr>
					<tr><th>Tapahtuma:</th><td><input type='text' name='tapahtuma' size='38' value='$row[tapahtuma]'></td></tr>
					<tr><th>Vahinkopäivä:</th><td><input type='text' name='paivamaara' size='38' value='$row[paivamaara]'></td></tr>
					<tr><th>Vastapuoli:</th><td><input type='text' name='vastapuoli' size='38' value='$row[vastapuoli]'></td></tr>
					<tr><th>Tarkastaja:</th><td><input type='text' name='tarkastaja' size='38' value='$row[tarkastaja]'></td></tr>
					<tr><th>Lupa/omavastuu:</th><td><input type='text' name='omavastuu_lupa' size='38' value='$row[omavastuu_lupa]'></td></tr>
					<tr><th>Korjausaika:</th><td><input type='text' name='korjausaika' size='38' value='$row[korjausaika]'></td></tr>
					
					<tr><th>Liitä kuva 1:</th><td>$row[kuva1] <input name='userfile1' type='file'></td></tr>
					<tr><th>Liitä kuva 2:</th><td>$row[kuva2] <input name='userfile2' type='file'></td></tr>
					<tr><th>Liitä kuva 3:</th><td>$row[kuva3] <input name='userfile3' type='file'></td></tr>
					<tr><th>Liitä kuva 4:</th><td>$row[kuva4] <input name='userfile4' type='file'></td></tr>
					<tr><th>Liitä kuva 5:</th><td>$row[kuva5] <input name='userfile5' type='file'></td><td><input type='submit' value='Lisää'></td></tr>
					</form>
					</table><br><br>";
					
					if ($row["kuva1"] != '')
						echo "<img src='kuvat/$row[kuva1]'><br>";
					
					if ($row["kuva2"] != '')
						echo "<img src='kuvat/$row[kuva2]'><br>";
					
					if ($row["kuva3"] != '')
						echo "<img src='kuvat/$row[kuva3]'><br>";
					
					if ($row["kuva4"] != '')
						echo "<img src='kuvat/$row[kuva4]'><br>";
					
					if ($row["kuva5"] != '')
						echo "<img src='kuvat/$row[kuva5]'>";
		
		}
		

		if ($tila == "LISAA") {
			
			if ($vahtunnus > 0) {
				$query = "	UPDATE vahinkotiedot 
							SET
							vakuutus = '$vakuutus',
							tapahtuma = '$tapahtuma',
							paivamaara = '$paivamaara',
							vastapuoli = '$vastapuoli',
							tarkastaja = '$tarkastaja',
							omavastuu_lupa = '$omavastuu_lupa',
							korjausaika = '$korjausaika'
							WHERE
							yhtio = '$kukarow[yhtio]'
							and otunnus = '$kukarow[kesken]'
							and tunnus = '$vahtunnus'";
			}
			else {
				$query = "	INSERT into vahinkotiedot 
							SET
							yhtio = '$kukarow[yhtio]',
							vakuutus = '$vakuutus',
							tapahtuma = '$tapahtuma',
							paivamaara = '$paivamaara',
							vastapuoli = '$vastapuoli',
							tarkastaja = '$tarkastaja',
							omavastuu_lupa = '$omavastuu_lupa',
							korjausaika = '$korjausaika',
							otunnus = '$kukarow[kesken]'";
			}
			$result = mysql_query($query) or pupe_error($query);
			
			for ($i=1; $i<=5; $i++) {
				if (is_uploaded_file($_FILES['userfile'.$i]['tmp_name']) == TRUE) {
					
					list($name,$ext) = split("\.", $_FILES['userfile'.$i]['name']);
			
					if (!(strtoupper($ext) == "JPG" || strtoupper($ext) == "GIF")){
						echo "<font class='error'><br>".t("Ainoastaa .jpg tai .gif tiedostot sallittuja")."!</font>";
					}
					else {
						if ($_FILES['userfile'.$i]['size']==0){
							echo "<font class='error'><br>".t("Tiedosto oli tyhjä")."!</font>";
						}
						else {
							move_uploaded_file($_FILES['userfile'.$i]['tmp_name'], $polku.$_FILES['userfile'.$i]['name']);												 							
														
							$query = "	UPDATE vahinkotiedot 
										SET
										kuva$i 	= '$name.$ext'
										WHERE
										yhtio 	= '$kukarow[yhtio]'
										and otunnus = '$kukarow[kesken]'";
							$result = mysql_query ($query) or pupe_error($query);
						}
					}
				}
			}
			$tee = "";
		}	
	}
	
	if ($toim == "TYOMAARAYS" and $tee_tyomaarays == "MAARAAIKAISHUOLLOT") {	
		//määräaikaishuollot
		echo "<table>";
		echo "<form action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tee' value='HUOLTO'>";
		
		echo "<tr><th>Valitse malli:</th>";
		echo "<td><select name='malli'><option value='menu'>Valitse malli </option>";
		
		$query = "	SELECT distinct malli
					FROM huollot
					WHERE yhtio='$kukarow[yhtio]'
					ORDER by malli";
		$result = mysql_query ($query) or pupe_error($query);
		
		while($trow = mysql_fetch_array($result)) {
			
			$sel = "";
			
			if ($laskurow["merkki"] == $trow["malli"] or $malli == $trow["malli"]) {
				$sel = "SELECTED";
				$malli = $trow["malli"];
			}
		
			echo "<option value='$trow[malli]' $sel>$trow[malli]</option>";
		}
		echo "</select></td>";
		
		if ($malli != '') {
			$query = "	SELECT distinct malli, huolto
						FROM huollot
						WHERE yhtio='$kukarow[yhtio]' and malli='$malli'
						ORDER by malli, huolto";
			$result = mysql_query ($query) or pupe_error($query);
			
			if(mysql_num_rows($result) != 0){			
				echo "<td><select name='huolto'>";
				
				while($trow = mysql_fetch_array($result)) {
			
					$sel = "";
					
					if ($huolto == $trow["huolto"]) {
						$sel = "SELECTED";
					}
				
					echo "<option value='$trow[huolto]' $sel>$trow[huolto]</option>";
				}
				
				echo "</td>";
			}
			echo "</select></td><td><input type='Submit' value = 'Lisää'></form></td></tr>";
		}
		else {
			echo "<td><input type='Submit' value = 'Valitse'></form></td></tr>";
		}
		
		echo "</table><br>";
	}	
			
?>