<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Tuoteketjujen sisäänluku").":</font><hr>";

if ($oikeurow['paivitys'] != '1') { // Saako päivittää
	if ($uusi == 1) {
		echo "<b>".t("Sinulla ei ole oikeutta lisätä tätä tietoa")."</b><br>";
		$uusi = '';
	}
	if ($del == 1) {
		echo "<b>".t("Sinulla ei ole oikeutta poistaa tätä tietoa")."</b><br>";
		$del = '';
		$tunnus = 0;
	}
	if ($upd == 1) {
		echo "<b>".t("Sinulla ei ole oikeutta muuttaa tätä tietoa")."</b><br>";
		$upd = '';
		$uusi = 0;
		$tunnus = 0;
	}
}

flush();

$vikaa=0;
$tarkea=0;
$lask=0;
$postoiminto = 'X';
$kielletty=0;

if (is_uploaded_file($_FILES['userfile']['tmp_name'])==TRUE) {

	list($name,$ext) = split("\.", $_FILES['userfile']['name']);

	if (strtoupper($ext) !="TXT" and strtoupper($ext)!="CSV")
	{
		die ("<font class='error'><br>".t("Ainoastaa .txt ja .csv tiedostot sallittuja")."!</font>");
	}

	if ($_FILES['userfile']['size']==0)
	{
		die ("<font class='error'><br>".t("Tiedosto oli tyhjä")."!</font>");
	}

	$file=fopen($_FILES['userfile']['tmp_name'],"r") or die ("".t("Tiedoston avaus epäonnistui")."!");

	echo "<font class='message'>".t("Tutkaillaan mitä olet lähettänyt").".<br></font>";

	// luetaan eka rivi tiedostosta..
	$rivi    = fgets($file);
	$otsikot = explode("\t", strtoupper(trim($rivi)));

	// haetaan valitun taulun sarakkeet
	$query = "SHOW COLUMNS FROM $table";
	$fres  = mysql_query($query) or pupe_error($query);

	while ($row=mysql_fetch_array($fres))
	{
		//pushataan arrayseen kaikki sarakenimet ja tietuetyypit
		$trows[] = strtoupper($row[0]);
		$ttype[] = $row[1];
	}

	// määritellään pakolliset sarakkeet
	switch ($table)
	{
		case "korvaavat" :
			$pakolliset = array("TUOTENO");
			$kielletyt = array("");
			break;
		case "tuoteperhe" :
			$pakolliset = array("ISATUOTENO", "TUOTENO");
			$kielletyt = array("TYYPPI", "KERROIN", "HINTAKERROIN", "ALEKERROIN", "EI_NAYTETA");
			break;
		default :
			echo "".t("mitenkäs tälläsen taulun valitsit")."!?!";
			exit;
	}
	// $trows 		sisältää kaikki taulun sarakkeet tietokannasta
	// $otsikot 	sisältää kaikki sarakkeet saadusta tiedostosta

	foreach ($otsikot as $column) {
		$column = strtoupper(trim($column));
		if ($column != '') {
			//laitetaan kaikki paitsi valintasarake talteen.
			if ($column != "TOIMINTO") {
				if (!in_array($column, $trows)) {
					echo "<br><font class='message'>".t("Saraketta")." \"<b>".strtoupper($column)."</b>\" ".t("ei löydy")." $table-taulusta!</font>";
					$vikaa++;
				}
				// yhtio ja tunnus kenttiä ei saa koskaan muokata...
				if (($column=='YHTIO') or ($column=='TUNNUS')) {
					echo "<br><font class='message'>".t("YHTIO ja/tai TUNNUS sarakkeita ei saa muuttaa")."!</font>";
					$vikaa++;
				}
				if (in_array($column, $pakolliset)) {
					$tarkea++;
				}
			}
			if ($column == "TOIMINTO") {
					//TOIMINTO sarakkeen positio tiedostossa
					$postoiminto = (string) array_search($column, $otsikot);
			}
			if (in_array($column, $kielletyt)) {
				// katotaan ettei kiellettyjä sarakkkeita muuteta
				echo "".t("Sarake").": $column ".t("on kielletty sarake")."!<br>";	
				$kielletty++;
			}
		}
	}

	// oli virheellisiä sarakkeita tai pakollisia ei löytynyt..
	if (($vikaa != 0) or  ($tarkea < count($pakolliset)) or ($postoiminto == 'X')) {
		// suljetaan avattu faili.. kilttiä!
		fclose ($file);
		// ja kuollaan pois
		die("<br><br><font class='error'>".t("Virheitä löytyi! Ei voida jatkaa")."!<br></font>");
	}

	if ($kielletty>0) {
		echo "<br><font class='message'>".t("Kiellettyjä löytyi, ei voida jatkaa")."...<br></font>";		
		exit;
	}

	echo "<br><font class='message'>".t("Tiedosto ok, aloitellaan päivitys")."...<br></font>";
	flush();

	// luetaan tiedosto loppuun...
	$rivi = fgets($file);

	while (!feof($file)) {
		// luetaan rivi tiedostosta..
		//poistetaan hipsut kauttaviivat ja spacet
		$poista	 = array("'", "\\"," ");
		$rivi	 = str_replace($poista,"",$rivi);
		$rivi	 = explode("\t", trim($rivi));

		// näin käsitellään korvaavat taulu
		if ($table == "korvaavat") {
			
			$haku = '';
			for($j=0; $j<count($rivi); $j++) {
				//otetaan rivin kaikki tuotenumerot talteen
				$haku .= "'$rivi[$j]',";
			}
			$haku = substr($haku,0,-1);
        	
			$fquery = "	SELECT distinct id
						FROM korvaavat
						WHERE tuoteno in ($haku) and yhtio='$kukarow[yhtio]'";
			$hresult = mysql_query($fquery) or pupe_error($fquery);
        	
			if (mysql_num_rows($hresult) == 0) {
				$fquery = "	SELECT max(id)
							FROM korvaavat
							WHERE yhtio='$kukarow[yhtio]'";
				$fresult = mysql_query($fquery) or pupe_error($fquery);
				$frow =  mysql_fetch_array($fresult);
        	
				$id = $frow[0]+1;
				echo "".t("Ei vielä missään")." $id!<br>";
			}
			elseif (mysql_num_rows($hresult) == 1) {
				$frow =  mysql_fetch_array($hresult);
				$id = $frow[0];
        	
				echo "".t("Löytyi")." $id!<br>";
			}
			else {
				echo "".t("Joku tuotteista")." ($haku) ".t("on jo useassa ketjussa! Korjaa homma")."!<br>";
				$id = 0;
			}
        	
			if ($id > 0) {
				if (strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
					$alku = "INSERT into korvaavat SET YHTIO='$kukarow[yhtio]'";
					$loppu = ", id='$id'";
				}
				else {
					//tuntematon toiminto
					echo "".t("Tuntematon tai puuttuva toiminto")."!<br>";
					unset($rivi);
				}
        	
				for($j=0; $j<count($rivi); $j++) {
					if ($otsikot[$j] != "TOIMINTO" and trim($rivi[$j]) != '') {
						//katotaan, että tuote löytyy
						$tquery = "	SELECT tuoteno
									FROM tuote
									WHERE tuoteno='$rivi[$j]' and yhtio='$kukarow[yhtio]'";
						$tresult = mysql_query($tquery) or pupe_error($tquery);
        	
						if (mysql_num_rows($tresult) > 0) {
							//katotaan, onko tuote jo jossain ketjussa
							$kquery = "	SELECT tuoteno
										FROM korvaavat
										WHERE tuoteno='$rivi[$j]' and id='$id' and yhtio='$kukarow[yhtio]'";
							$kresult = mysql_query($kquery) or pupe_error($kquery);

							if (mysql_num_rows($kresult) == 0) {
								$kysely = ", TUOTENO='$rivi[$j]'";
								$query = $alku.$kysely.$loppu;

								$iresult = mysql_query($query) or pupe_error($query);
								echo "".t("Lisättiin ketjuun")." $id $rivi[$j]!<br>";
							}
							else {
								echo "".t("Tuote")." $rivi[$j] ".t("on jo tässä ketjussa")."!<br>";
							}
						}
						else {
							echo "".t("Tuotetta")." $rivi[$j] ".t("ei löydy")."!<br>";
						}
					}
				}
			}
		}
		
		// näin käsitellään korvaavat taulu
		if ($table == "tuoteperhe") {

			// käydään läpi rivin tiedot, tehdään erroricheckit
			$virhe = 0;
			$isatuote = "";
			
			for($r=0; $r<count($otsikot); $r++) {

				// jos käsitellään isätuote-kenttään
				if (strtoupper(trim($otsikot[$r])) == "ISATUOTENO") {

					$query = "select tunnus from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$rivi[$r]'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0) {
						echo "".t("tuotetta")." $rivi[$r] ".t("ei löydy! rivi hylätty")."<br>";
						$virhe++;
					}
					else {
						$isatuote = $rivi[$r];
					}
					
					$query = "select * from tuoteperhe where yhtio='$kukarow[yhtio]' and isatuoteno='$rivi[$r]' and tyyppi='P'";
					$result = mysql_query($query) or pupe_error($query);
					
					if (mysql_num_rows($result) == 0 and strtoupper(trim($rivi[$postoiminto])) == 'MUUTA') {
						echo "".t("tuoteperhettä ei löydy! ei voida muuttaa")."<br>";
						$virhe++;
					}
					elseif (mysql_num_rows($result) != 0 and strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
						echo "".t("tuoteperhe on jo olemassa! ei voida lisätä")."<br>";
						$virhe++;						
					}
				}

				if (strtoupper(trim($otsikot[$r])) == "TUOTENO") {
					$query = "select tunnus from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$rivi[$r]'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0) {
						echo "".t("tuotetta")." $rivi[$r] ".t("ei löydy! rivi hylätty")."<br>";
						$virhe++;
					}
				}

			} // end for

			// jos ei ole virheitä, lisäillään rivejä
			if ($virhe == 0 and $isatuote != "") {
				
				$lask=0;
				
				// poistetaan eka kaikki.. heh
				$query = "delete from tuoteperhe where yhtio='$kukarow[yhtio]' and isatuoteno='$isatuote' and tyyppi='P'";
				$result = mysql_query($query) or pupe_error($query);
				
				for($r=0; $r<count($otsikot); $r++) {
					
					if (strtoupper(trim($otsikot[$r])) == "TUOTENO") {
						$query  = "insert into tuoteperhe set yhtio='$kukarow[yhtio]', isatuoteno='$isatuote', tuoteno='$rivi[$r]', tyyppi='P'";
						$result = mysql_query($query) or pupe_error($query);
						echo "$query<br>";
						$lask++;
					}
									
				}
			}
		}
		
		$rivi = fgets($file);
	} // end while eof

	fclose($file);

	echo "".t("Päivitettiin")." $lask ".t("riviä")."!";
}

else {
	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<br><br>
			<table border='0'>
			<tr>
				<td>".t("Valitse tietokanna taulu").":</td>
				<td><select name='table'>
					<option value='korvaavat'>".t("Korvaavat")."</option>
					<option value='tuoteperhe'>".t("Tuoteperheet")."</option>
				</select></td>
			</tr>

			<input type='hidden' name='tee' value='file'>

			<tr><td>".t("Valitse tiedosto").":</td>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("Lähetä")."'></td>
			</tr>

			</table>
			</form>";
}

require ("inc/footer.inc");

?>