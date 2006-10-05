<?php

// otetaan sisään $tuoteno, $myyntirivitunnus tai $ostorivitunnus 
// ja $from jossa on pika, rivi tai riviosto niin tiedetään mistä tullaan

if (strpos($_SERVER['SCRIPT_NAME'], "sarjanumeroseuranta.php")  !== FALSE) {
	require("../inc/parametrit.inc");
}

echo "<font class='head'>".t("Sarjanumeroseuranta")."</font><hr>";

$tunnuskentta 	= "";
$rivitunnus 	= "";
$hyvitysrivi 	= "";

if ($myyntirivitunnus != "") {
	$tunnuskentta = "myyntirivitunnus";
	$rivitunnus = $myyntirivitunnus;
}

if ($ostorivitunnus != "") {
	$tunnuskentta = "ostorivitunnus";
	$rivitunnus = $ostorivitunnus;
}

if ($rivitunnus != "") {
	// haetaan rivin tiedot
	$query    = "SELECT * FROM tilausrivi WHERE yhtio='$kukarow[yhtio]' and tunnus='$rivitunnus'";
	$sarjares = mysql_query($query) or pupe_error($query);
	$rivirow  = mysql_fetch_array($sarjares);	
}

// jos varattu on nollaa ja kpl ei niin kpl > varattu (esim varastoon viedyt ostotilausrivit)
if ($rivirow["varattu"] == 0 and $rivirow["kpl"] != 0) {
	$rivirow["varattu"] = $rivirow["kpl"];
}

$rivillamaara = $rivirow["varattu"];

// tässä muutetaan myyntirivitunnus ostorivitunnukseksi jos $rivirow["varattu"] eli kappalemäärä on negatiivinen
if ($rivirow["varattu"] < 0 and $tunnuskentta = "myyntirivitunnus") {
	$tunnuskentta = "ostorivitunnus";
	$rivirow["varattu"] = abs($rivirow["varattu"]);
	$hyvitysrivi = "ON";
}

//ollaan poistamassa sarjanumero kokonaan
if ($toiminto == 'poista') {
	$query = "DELETE FROM sarjanumeroseuranta WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$sarjatunnus'";
	$dellares = mysql_query($query) or pupe_error($query);
	
	echo "<font class='message'>".t("Sarjanumero poistettu")."!</font><br><br>";
}

if ($toiminto == "kuvat") {
	
	$polku = "../sarjanumeron_kuvat/";	

	if ($toiminto_tark == "lisaa") {					
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) == TRUE) {
			
			list($name,$ext) = split("\.", $_FILES['userfile']['name']);
	
			if (!(strtoupper($ext) == "JPG" || strtoupper($ext) == "GIF")){
				echo "<font class='error'><br>".t("Ainoastaa .jpg tai .gif tiedostot sallittuja")."!</font>";
			}
			else {
				if ($_FILES['userfile']['size']==0){
					echo "<font class='error'><br>".t("Tiedosto oli tyhjä")."!</font>";
				}
				else {
					move_uploaded_file($_FILES['userfile']['tmp_name'], $polku.$_FILES['userfile']['name']);												 							
											
					$query = "	INSERT into tuotteen_avainsanat
								SET 
								laji 	= 'SARJANUMEROKUVA',
								selite 	= '$name.$ext',								
								tuoteno	= '$sarjanumero',
								yhtio 	= '$kukarow[yhtio]'";
					$result = mysql_query ($query) or pupe_error($query);
					
				}
			}
		}
	}

	if ($toiminto_tark == "poista") {
		$query = "	SELECT selite
					FROM tuotteen_avainsanat
					WHERE yhtio='$kukarow[yhtio]' 
					and laji='SARJANUMEROKUVA'
					and tuoteno= '$sarjanumero'
					and tunnus='$kuvatunnus'";
		$tresult = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($tresult);

		system("rm -f ".$polku.$row["selite"]);
		
		$query = "	DELETE
					FROM tuotteen_avainsanat
					WHERE yhtio='$kukarow[yhtio]' 
					and laji='SARJANUMEROKUVA'
					and tuoteno= '$sarjanumero'	
					and tunnus='$kuvatunnus'";
		$tresult = mysql_query($query) or pupe_error($query);
	}

	$query    = "	SELECT *
					FROM tuotteen_avainsanat
					WHERE yhtio='$kukarow[yhtio]' 
					and laji='SARJANUMEROKUVA'
					and tuoteno= '$sarjanumero'";
	$tresult = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($tresult);
	
	
	echo "	<br><br><table>";		
	
	echo "	<form action='$PHP_SELF' method='post' enctype='multipart/form-data'>
			<input type='hidden' name='tuoteno' value='$tuoteno'>
			<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
			<input type='hidden' name='$tunnuskentta' value='$rivitunnus'>
			<input type='hidden' name='from' value='$from'>
			<input type='hidden' name='otunnus' value='$otunnus'>
			<input type='hidden' name='toiminto' value = 'kuvat'>
			<input type='hidden' name='toiminto_tark' value='lisaa'>
			<input type='hidden' name='sarjanumero' value='$sarjanumero'>
			<tr><th>Liitä kuva</th><td><input name='userfile' type='file'></td><td><input type='submit' value='Lisää'></td></tr>
			</form>
			</table>";
	
	echo "	<br><br><table>";	

	$query    = "	SELECT *
					FROM tuotteen_avainsanat
					WHERE yhtio='$kukarow[yhtio]' 
					and laji='SARJANUMEROKUVA'
					and tuoteno= '$sarjanumero'";
	$kuvares = mysql_query($query) or pupe_error($query);	
			
	while($kuvarow = mysql_fetch_array($kuvares)) {
		echo "<tr><td><a href='$PHP_SELF?toiminto=kuvat&toiminto_tark=poista&kuvatunnus=$kuvarow[tunnus]&$tunnuskentta=$rivitunnus&from=$from&tuoteno=$tuoteno&otunnus=$otunnus&formista=&sarjanumero=$sarjanumero'>Poista kuva</a></td><td><img width='600' src='../sarjanumeron_kuvat/$kuvarow[selite]'></td></tr>";
	}
	echo "</table><br>";

	echo "	<form action='$PHP_SELF' method='post'>
			<input type='hidden' name='tuoteno' value='$tuoteno'>
			<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
			<input type='hidden' name='$tunnuskentta' value='$rivitunnus'>
			<input type='hidden' name='from' value='$from'>
			<input type='hidden' name='otunnus' value='$otunnus'>
			<input type='hidden' name='toiminto' value = ''>
			<input type='hidden' name='sarjanumero' value='$sarjanumero'>
			<input type='submit' value='Takaisin sarjanumeron valintaan'></form>";
}


//halutaan muuttaa sarjanumeron tietoja
if ($toiminto == 'muuta') {
	if ($toiminto2 == "") {				
		$query = "SELECT * FROM sarjanumeroseuranta WHERE yhtio='$kukarow[yhtio]' and tunnus='$sarjatunnus'";
		$muutares = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($muutares) == 1) {
			
			$muutarow = mysql_fetch_array($muutares);
			
			echo "<br><table>";
			echo "<tr><th colspan='2'>".t("Muuta sarjanumerotietoja").":</th></tr>";
			echo "<tr><th>".t("Sarjanumero")."</th>";
			echo "	<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='tuoteno' value='$tuoteno'>
					<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
					<input type='hidden' name='$tunnuskentta' value='$rivitunnus'>			
					<input type='hidden' name='from' value='$from'>
					<input type='hidden' name='otunnus' value='$otunnus'>
					<input type='hidden' name='toiminto' value='muuta'>
					<input type='hidden' name='toiminto2' value='paivita'>
					<input type='hidden' name='sarjatunnus' value='$sarjatunnus'>";
			echo "<td>$muutarow[sarjanumero]</td></tr>";
			echo "<tr><th>".t("Lisätieto")."</th><td><input type='text' size='30' name='lisatieto' value='$muutarow[lisatieto]'></td>";
			echo "<td class='back'><input type='submit' value='".t("Päivitä")."'></form></td>";
			echo "</tr></table>";	
					
			$tuoteno 		= "";
			$sarjanumero	= "";
		}
		else {
			echo t("Muutettava sarjanumero on kadonnut")."!!!!<br>";
		}
	}
	else {
		$query = "	UPDATE sarjanumeroseuranta
					set lisatieto = '$lisatieto' 
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$sarjatunnus'";
		$sarjares = mysql_query($query) or pupe_error($query);
		
		echo "<font class='message'>".t("Pävitettiin sarjanumeron tiedot")."!</font><br><br>";
		
		$sarjanumero	= "";
		$lisatieto		= "";
	}	
}

// ollaan syötetty uusi tai muutetaan sarjanumero 
if ($toiminto == 'lisaa' and $sarjanumero != "") {
	
	$query = "SELECT * FROM sarjanumeroseuranta WHERE yhtio='$kukarow[yhtio]' and sarjanumero='$sarjanumero'";
	$sarjares = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($sarjares) == 0) {
		//jos ollaan syötetty kokonaan uusi sarjanuero
		$query = "insert into sarjanumeroseuranta (yhtio, tuoteno, sarjanumero, lisatieto, $tunnuskentta) VALUES ('$kukarow[yhtio]','$tuoteno','$sarjanumero','$lisatieto','')";
		$sarjares = mysql_query($query) or pupe_error($query);
			
		echo "<font class='message'>".t("Lisättiin sarjanumero")." $sarjanumero.</font><br><br>";
		
		$sarjanumero	= "";
		$lisatieto		= "";
	}
	else {
		$sarjarow = mysql_fetch_array($sarjares);
		echo "<font class='error'>".t("Sarjanumero löytyy jo tuotteelta")." $sarjarow[tuoteno]/$sarjanumero.</font><br><br>";
	}
}

// ollaan valittu joku tunnus listasta ja halutaan liittää se tilausriviin tai poistaa se tilausriviltä
if ($rivitunnus != "" and $formista == "kylla") {	
	// jos olemme ruksanneet vähemmän tai yhtäpaljon kuin tuotteita on rivillä, voidaan päivittää muutokset
	if ($rivirow["varattu"] >= count($sarjat)) {
		
		// poistetaan tää rivi kaikilta muilta sarjanumeroilta
		$query = "update sarjanumeroseuranta set $tunnuskentta='' WHERE yhtio='$kukarow[yhtio]' and $tunnuskentta='$rivitunnus'";
		$sarjares = mysql_query($query) or pupe_error($query);

		//jos mikään ei ole ruksattu niin ei tietenkään halutakkaan lisätä mitään sarjanumeroa
		if (count($sarjat) > 0) {
			foreach ($sarjat as $sarjatunnus) {
				$query = "update sarjanumeroseuranta set $tunnuskentta='$rivitunnus' WHERE yhtio='$kukarow[yhtio]' and tunnus='$sarjatunnus'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
		}
	}
	else {
		echo "<font class='error'>".sprintf(t('Riviin voi liittää enintään %s sarjanumeroa'), abs($rivirow["varattu"])).".</font><br><br>";
	}
}

// ollaan tulossa myyntitilauksesta, näytetään tuotteen kaikki sarjanumerot
if ($rivitunnus != "" and $tuoteno != "" and $toiminto != 'kuvat') {
	
	// Katsotaan onko sarjanumerot vielä käytössä, tilausrivi on voitu poistaa
	$query = "	SELECT sarjanumeroseuranta.tunnus sarjatunnus, tilausrivi.tunnus rivitunnus
				FROM sarjanumeroseuranta
				LEFT JOIN tilausrivi ON sarjanumeroseuranta.yhtio=tilausrivi.yhtio and sarjanumeroseuranta.myyntirivitunnus=tilausrivi.tunnus and tilausrivi.tyyppi!='D'
				WHERE sarjanumeroseuranta.yhtio='$kukarow[yhtio]' 
				and sarjanumeroseuranta.tuoteno='$tuoteno'
				HAVING tilausrivi.tunnus is null";
	$sres = mysql_query($query) or pupe_error($query);
	
	while($srow = mysql_fetch_array($sres)) {
		$query = "update sarjanumeroseuranta set myyntirivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tunnus='$srow[sarjatunnus]'";
		$sarjares = mysql_query($query) or pupe_error($query);
	}
	
	// Katsotaan onko sarjanumerot vielä käytössä, tilausrivi on voitu poistaa
	$query = "	SELECT sarjanumeroseuranta.tunnus sarjatunnus, tilausrivi.tunnus rivitunnus
				FROM sarjanumeroseuranta
				LEFT JOIN tilausrivi ON sarjanumeroseuranta.yhtio=tilausrivi.yhtio and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus and tilausrivi.tyyppi!='D'
				WHERE sarjanumeroseuranta.yhtio='$kukarow[yhtio]' 
				and sarjanumeroseuranta.tuoteno='$tuoteno'
				HAVING tilausrivi.tunnus is null";
	$sres = mysql_query($query) or pupe_error($query);
	
	while($srow = mysql_fetch_array($sres)) {
		$query = "update sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tunnus='$srow[sarjatunnus]'";
		$sarjares = mysql_query($query) or pupe_error($query);
	}

	if (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "KERAA") and $hyvitysrivi != "ON") {
		// Haetaan vain sellaiset sarjanumerot jotka on ostettu/viety onnistuneesti varastoon
		$query    = "	SELECT sarjanumeroseuranta.*, lasku.tunnus otunnus
						FROM sarjanumeroseuranta
						LEFT JOIN tilausrivi ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus
						LEFT JOIN lasku ON lasku.yhtio=sarjanumeroseuranta.yhtio and lasku.tunnus=tilausrivi.otunnus
						WHERE sarjanumeroseuranta.yhtio='$kukarow[yhtio]' 
						and sarjanumeroseuranta.tuoteno='$tuoteno'
						and sarjanumeroseuranta.myyntirivitunnus in (0,$rivitunnus)
						HAVING otunnus is null or otunnus != '$rivirow[otunnus]'
						order by sarjanumero";
	}
	elseif($from == "riviosto" or $from == "kohdista" or (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "KERAA") and $hyvitysrivi == "ON")) {
		// Haetaan vain sellaiset sarjanumerot jotka on vielä vapaita
		$query    = "	SELECT sarjanumeroseuranta.*, tilausrivi.varattu, tilausrivi.kpl, lasku.tunnus otunnus
						FROM sarjanumeroseuranta
						LEFT JOIN tilausrivi ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus
						LEFT JOIN lasku ON lasku.yhtio=sarjanumeroseuranta.yhtio and lasku.tunnus=tilausrivi.otunnus
						WHERE sarjanumeroseuranta.yhtio='$kukarow[yhtio]' 
						and sarjanumeroseuranta.tuoteno='$tuoteno'
						and sarjanumeroseuranta.ostorivitunnus in (0,$rivitunnus)
						order by sarjanumero";	
	}
	$sarjares = mysql_query($query) or pupe_error($query);

	echo "<table>";
	echo "<tr><th>".t("Tuotenumero")."</th><td>$tuoteno $rivirow[nimitys]</td></tr>";
	echo "<tr><th>".t("Määrä")."</th><td>$rivillamaara $rivirow[yksikko]</td></tr>";	
	echo "</table><br>";
	
	if (mysql_num_rows($sarjares) > 0) {

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Sarjanumero")."</th>";
		echo "<th>".t("Ostotilaus")."</th>";
		echo "<th>".t("Myyntitilaus")."</th>";
		echo "<th>".t("Lisätieto")."</th>";
		echo "<th>".t("Valitse")."</th>";
		echo "<th>".t("Muokkaa")."</th>";
		echo "<th>".t("Poista")."</th>";
		echo "<th>".t("Kuvat")."</th>";
		echo "</tr>";
		
		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='$tunnuskentta' value='$rivitunnus'>";
		echo "<input type='hidden' name='from' value='$from'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>";
		echo "<input type='hidden' name='otunnus' value='$otunnus'>";
		echo "<input type='hidden' name='formista' value='kylla'>";

		while ($sarjarow = mysql_fetch_array($sarjares)) {
									
			echo "<tr>";
			echo "<td>$sarjarow[sarjanumero]</td>";
			
			if ($sarjarow["ostorivitunnus"] == 0) {
				$sarjarow["ostorivitunnus"] = "";
			}
			if ($sarjarow["myyntirivitunnus"] == 0) {
				$sarjarow["myyntirivitunnus"] = "";
			}
			
			echo "<td>$sarjarow[ostorivitunnus]</td>";						
									
			echo "<td>$sarjarow[myyntirivitunnus]</td>";
			
			//echo "<td>$sarjarow[lisatieto]</td>";

			echo "<td>";
			if ($sarjarow["ostorivitunnus"] != 0) {
				//Haetaan myös ns. lisävarusteperheet.
				
				$query = "	select otunnus, perheid
							from tilausrivi 
							where yhtio = '$kukarow[yhtio]' 							
							and tyyppi != 'D'
							and tunnus	= '$sarjarow[ostorivitunnus]'
							and perheid	!= 0";
				$tilrivires = mysql_query($query) or pupe_error($query);
				
				if (mysql_num_rows($tilrivires) > 0) {
				
					$tilrivirow = mysql_fetch_array($tilrivires);
					
					$query = "	select * 
								from tilausrivi 
								where yhtio = '$kukarow[yhtio]' 							
								and tyyppi != 'D'
								and otunnus = '$tilrivirow[otunnus]'
								and perheid = '$tilrivirow[perheid]'
								order by tunnus";
					$tilrivires = mysql_query($query) or pupe_error($query);
					
					$pid = 0;
					
					while ($tilrivirow = mysql_fetch_array($tilrivires)) {
						echo "$tilrivirow[tuoteno] $tilrivirow[nimitys]<br>";
					}
				}
			}
			echo "</td>";


			if ($sarjarow[$tunnuskentta] == 0 or $sarjarow[$tunnuskentta] == $rivitunnus) {
				$chk = "";
				if ($sarjarow[$tunnuskentta] == $rivitunnus){
					$chk="CHECKED";					
				}
				
				
				if ($tunnuskentta == "ostorivitunnus" and $sarjarow["kpl"] != 0) {
					echo "<td>".t("Lukittu")."</td>";
				}
				elseif (	($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "KERAA") or 
						($from == "riviosto" or $from == "kohdista")) {
					
					echo "<td><input type='checkbox' name='sarjat[]' value='$sarjarow[tunnus]' $chk onclick='submit()'></td>";
				}
				
			}
			
			//jos saa muuttaa niin näytetään muokkaa linkki			
			echo "<td><a href='$PHP_SELF?toiminto=muuta&$tunnuskentta=$rivitunnus&from=$from&tuoteno=$tuoteno&otunnus=$otunnus&formista=&sarjatunnus=$sarjarow[tunnus]'>".t("Muokkaa")."</a></td>";
			
			if ($sarjarow['ostorivitunnus'] == "" and $sarjarow['myyntirivitunnus'] == "") {
				echo "<td><a href='$PHP_SELF?toiminto=poista&$tunnuskentta=$rivitunnus&from=$from&tuoteno=$tuoteno&otunnus=$otunnus&formista=&sarjatunnus=$sarjarow[tunnus]'>".t("Poista")."</a></td>";
			}
			else {
				echo "<td></td>";
			}
			
			echo "<td><a href='$PHP_SELF?toiminto=kuvat&$tunnuskentta=$rivitunnus&from=$from&tuoteno=$tuoteno&otunnus=$otunnus&formista=&sarjanumero=$sarjarow[sarjanumero]'>".t("Lisää kuva")."</a></td>";							
			
			$query    = "	SELECT *
							FROM tuotteen_avainsanat
							WHERE yhtio='$kukarow[yhtio]' 
							and laji='SARJANUMEROKUVA'
							and tuoteno= '$sarjarow[sarjanumero]'";
			$kuvares = mysql_query($query) or pupe_error($query);	
			
			while($kuvarow = mysql_fetch_array($kuvares)) {
				echo "<td><a href='$PHP_SELF?toiminto=kuvat&$tunnuskentta=$rivitunnus&from=$from&tuoteno=$tuoteno&otunnus=$otunnus&formista=&sarjanumero=$sarjarow[sarjanumero]'><img border='0' height='100' src='../sarjanumeron_kuvat/$kuvarow[selite]'></a></td>";
			}	
			
			echo "</tr>";	
		}				
		echo "</form>";
		echo "</table>";
		
	}
	
	if ($toiminto== '') {
		$sarjanumero = '';
		$lisatieto = '';
		$chk = '';
	}
	
	echo "<br><table>";
	echo "<tr><th colspan='2'>".t("Lisää uusi sarjanumero")."</th></tr>";
	echo "<tr><th>".t("Sarjanumero")."</th>";
									
	echo "	<form action='$PHP_SELF#$rivitunnus' method='post'>
			<input type='hidden' name='tuoteno' value='$tuoteno'>
			<input type='hidden' name='$tunnuskentta' value='$rivitunnus'>
			<input type='hidden' name='from' value='$from'>
			<input type='hidden' name='otunnus' value='$otunnus'>
			<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
			<input type='hidden' name='toiminto' value='lisaa'>";
	echo "<td><input type='text' size='30' name='sarjanumero' value='$sarjanumero'></td></tr>";
	echo "<tr><th>".t("Lisätieto")."</th><td><input type='text' size='30' name='lisatieto' value='$lisatieto'></td>";
	echo "<td class='back'><input type='submit' value='".t("Lisää")."'></form></td>";
	echo "</tr></table>";
}

echo "<br>";

if ($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS") {
	echo "<form method='post' action='tilaus_myynti.php'>
		<input type='hidden' name='toim' value='$from'>
		<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
		<input type='submit' value='".t("Takaisin tilaukselle")."'>
		</form>";
}

if ($from == "riviosto") {
	echo "<form method='post' action='tilaus_osto.php'>
		<input type='hidden' name='tee' value='Y'>
		<input type='hidden' name='aktivoinnista' value='true'>
		<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
		<input type='submit' value='".t("Takaisin tilaukselle")."'>
		</form>";
}

if ($from == "kohdista") {
	echo "<form method='post' action='keikka.php'>
		<input type='hidden' name='toiminto' value='kohdista'>
		<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
		<input type='hidden' name='otunnus' value='$otunnus'>
		<input type='submit' value='".t("Takaisin keikkaan")."'>
		</form>";
}

if ($from == "KERAA") {
	echo "<form method='post' action='keraa.php'>
		<input type='hidden' name='id' value='$otunnus'>
		<input type='submit' value='".t("Takaisin keräykseen")."'>
		</form>";
}

require ("../inc/footer.inc");

?>