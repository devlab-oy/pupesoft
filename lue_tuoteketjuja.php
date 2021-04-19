<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Tuoteketjujen sis��nluku").":</font><hr>";

if ($oikeurow['paivitys'] != '1') { // Saako p�ivitt��
	if ($uusi == 1) {
		echo "<b>",t("Sinulla ei ole oikeutta lis�t� t�t� tietoa"),"</b><br>";
		$uusi = '';
	}
	if ($del == 1) {
		echo "<b>",t("Sinulla ei ole oikeutta poistaa t�t� tietoa"),"</b><br>";
		$del = '';
		$tunnus = 0;
	}
	if ($upd == 1) {
		echo "<b>",t("Sinulla ei ole oikeutta muuttaa t�t� tietoa"),"</b><br>";
		$upd = '';
		$uusi = 0;
		$tunnus = 0;
	}
}

flush();

$vikaa			= 0;
$tarkea			= 0;
$lask			= 0;
$postoiminto 	= 'X';
$kielletty		= 0;
$table_apu		= '';

if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

	$path_parts = pathinfo($_FILES['userfile']['name']);
	$ext = $path_parts['extension'];

	if (strtoupper($ext) != "TXT" and strtoupper($ext) != "CSV") {
		die ("<font class='error'><br>".t("Ainoastaan .txt ja .csv tiedostot sallittuja")."!</font>");
	}

	if ($_FILES['userfile']['size'] == 0) {
		die ("<font class='error'><br>".t("Tiedosto oli tyhj�")."!</font>");
	}

	$file = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep�onnistui")."!");

	echo "<font class='message'>",t("Tutkaillaan mit� olet l�hett�nyt"),".<br></font>";

	// luetaan eka rivi tiedostosta..
	$rivi    = fgets($file);
	$otsikot = explode("\t", strtoupper(trim($rivi)));

	$table_apu = $table;
	$table = ($table == 'tuoteresepti') ? 'tuoteperhe' : $table;

	// haetaan valitun taulun sarakkeet
	$query = "SHOW COLUMNS FROM $table";
	$fres  = mysql_query($query) or pupe_error($query);

	while ($row = mysql_fetch_array($fres)) {
		//pushataan arrayseen kaikki sarakenimet ja tietuetyypit
		$trows[] = strtoupper($row[0]);
		$ttype[] = $row[1];
	}

	// m��ritell��n pakolliset sarakkeet
	// tuoteresepteiss� k�ytet��n tuoteperheen pakollisia sarakkeita
	switch ($table) {
		case "korvaavat" :
			$pakolliset = array("TUOTENO");
			$kielletyt = array("");
			break;
		case "tuoteperhe" :
			$pakolliset = array("ISATUOTENO", "TUOTENO");
			$kielletyt = array("TYYPPI", "KERROIN", "HINTAKERROIN", "ALEKERROIN", "EI_NAYTETA");
			break;
		default :
			echo t("mitenk�s t�ll�sen taulun valitsit"),"!?!";
			exit;
	}
	// $trows 		sis�lt�� kaikki taulun sarakkeet tietokannasta
	// $otsikot 	sis�lt�� kaikki sarakkeet saadusta tiedostosta

	foreach ($otsikot as $column) {
		$column = strtoupper(trim($column));
		if ($column != '') {
			//laitetaan kaikki paitsi valintasarake talteen.
			if ($column != "TOIMINTO") {
				if (!in_array($column, $trows)) {
					echo "<br><font class='message'>",t("Saraketta")," \"<b>",strtoupper($column),"</b>\" ",t("ei l�ydy")," $table-taulusta!</font>";
					$vikaa++;
				}
				// yhtio ja tunnus kentti� ei saa koskaan muokata...
				if (($column == 'YHTIO') or ($column == 'TUNNUS')) {
					echo "<br><font class='message'>",t("YHTIO ja/tai TUNNUS sarakkeita ei saa muuttaa"),"!</font>";
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
				// katotaan ettei kiellettyj� sarakkkeita muuteta
				echo t("Sarake"),": $column ",t("on kielletty sarake"),"!<br>";
				$kielletty++;
			}
		}
	}

	// oli virheellisi� sarakkeita tai pakollisia ei l�ytynyt..
	if (($vikaa != 0) or  ($tarkea < count($pakolliset)) or ($postoiminto == 'X')) {
		// suljetaan avattu faili.. kiltti�!
		fclose ($file);
		// ja kuollaan pois
		die("<br><br><font class='error'>".t("Virheit� l�ytyi! Ei voida jatkaa")."!<br></font>");
	}

	if ($kielletty>0) {
		echo "<br><font class='message'>",t("Kiellettyj� l�ytyi, ei voida jatkaa"),"...<br></font>";
		exit;
	}

	echo "<br><font class='message'>",t("Tiedosto ok, aloitellaan p�ivitys"),"...<br></font>";
	flush();

	// luetaan tiedosto loppuun...
	$rivi = fgets($file);

	while (!feof($file)) {
		// luetaan rivi tiedostosta..
		//poistetaan hipsut kauttaviivat ja spacet
		$poista	 = array("'", "\\", " ");
		$rivi	 = str_replace($poista, "", $rivi);
		$rivi	 = explode("\t", trim($rivi));

		// n�in k�sitell��n korvaavat taulu
		if ($table == "korvaavat") {

			$haku = '';
			for ($j = 0; $j < count($rivi); $j++) {
				//otetaan rivin kaikki tuotenumerot talteen
				$haku .= "'$rivi[$j]',";
			}
			$haku = substr($haku, 0, -1);

			$fquery = "	SELECT distinct id
						FROM korvaavat
						WHERE tuoteno in ($haku) and yhtio = '{$kukarow['yhtio']}'";
			$hresult = mysql_query($fquery) or pupe_error($fquery);

			if (mysql_num_rows($hresult) == 0) {
				$fquery = "	SELECT max(id)
							FROM korvaavat
							WHERE yhtio = '{$kukarow['yhtio']}'";
				$fresult = mysql_query($fquery) or pupe_error($fquery);
				$frow =  mysql_fetch_array($fresult);

				$id = $frow[0] + 1;
				echo t("Ei viel� miss��n")," $id!<br>";
			}
			elseif (mysql_num_rows($hresult) == 1) {
				$frow =  mysql_fetch_array($hresult);
				$id = $frow[0];

				echo t("L�ytyi")," $id!<br>";
			}
			else {
				echo t("Joku tuotteista")," ($haku) ",t("on jo useassa ketjussa! Korjaa homma"),"!<br>";
				$id = 0;
			}

			if ($id > 0) {
				if (strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
					$alku 		= "INSERT into korvaavat SET yhtio = '{$kukarow['yhtio']}'";
					$loppu 		= ", id='$id'";
					$toiminto 	= "LISAA";
				}
				elseif (strtoupper(trim($rivi[$postoiminto])) == 'POISTA') {
					$alku 		= "DELETE from korvaavat where yhtio = '{$kukarow['yhtio']}' ";
					$loppu 		= " and id='$id' ";
					$toiminto 	= "POISTA";
				}
				else {
					//tuntematon toiminto
					echo t("Tuntematon tai puuttuva toiminto"),"!<br>";
					unset($rivi);
					$toiminto 	= "";
				}

				for ($j = 0; $j < count($rivi); $j++) {
					if ($otsikot[$j] != "TOIMINTO" and trim($rivi[$j]) != '') {
						//katotaan, ett� tuote l�ytyy
						$tquery = "	SELECT tuoteno
									FROM tuote
									WHERE tuoteno='$rivi[$j]' and yhtio = '{$kukarow['yhtio']}'";
						$tresult = mysql_query($tquery) or pupe_error($tquery);

						if (mysql_num_rows($tresult) > 0) {
							//katotaan, onko tuote jo jossain ketjussa
							$kquery = "	SELECT tuoteno
										FROM korvaavat
										WHERE tuoteno='$rivi[$j]' and id = '$id' and yhtio = '{$kukarow['yhtio']}'";
							$kresult = mysql_query($kquery) or pupe_error($kquery);

							if ((mysql_num_rows($kresult) == 0 and $toiminto != 'POISTA') or (mysql_num_rows($kresult) == 1 and $toiminto == 'POISTA')) {

								if($toiminto != 'POISTA') {
									$kysely = ", tuoteno='$rivi[$j]'";
								}
								else {
									$kysely = " and tuoteno='$rivi[$j]'";
								}

								$query = $alku.$kysely.$loppu;
								$iresult = mysql_query($query) or pupe_error($query);

								if($toiminto != 'POISTA') {
									echo t("Lis�ttiin ketjuun")," $id {$rivi[$j]}!<br>";
								}
								else {
									echo t("Poistettiin ketjusta")," $id {$rivi[$j]}!<br>";
								}
							}
							else {
								echo t("Tuote")," {$rivi[$j]} ",t("on jo t�ss� ketjussa"),"!<br>";
							}
						}
						else {
							echo t("Tuotetta")," {$rivi[$j]} ",t("ei l�ydy"),"!<br>";
						}
					}
				}
			}
		}

		// n�in k�sitell��n korvaavat taulu
		if ($table == "tuoteperhe") {

			// k�yd��n l�pi rivin tiedot, tehd��n erroricheckit
			$virhe = 0;
			$isatuote = "";

			// tuoteresepteiss� tyyppi pit�� olla R, tuoteperheiss� P
			$tyyppi = ($table_apu == 'tuoteresepti') ? 'R' : 'P';

			for ($r = 0; $r < count($otsikot); $r++) {

				// jos k�sitell��n is�tuote-kentt��n
				if (strtoupper(trim($otsikot[$r])) == "ISATUOTENO") {

					$query = "	SELECT tunnus
								FROM tuote
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '{$rivi[$r]}'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0) {
						echo t("tuotetta")," {$rivi[$r]} ",t("ei l�ydy! rivi hyl�tty"),"<br>";
						$virhe++;
					}
					else {
						$isatuote = $rivi[$r];
					}

					$query = "	SELECT *
								FROM tuoteperhe
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND isatuoteno = '{$rivi[$r]}'
								AND tyyppi = '$tyyppi'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0 and strtoupper(trim($rivi[$postoiminto])) == 'MUUTA') {
						echo t("tuoteperhett� ei l�ydy! ei voida muuttaa"),"<br>";
						$virhe++;
					}
					elseif (mysql_num_rows($result) != 0 and strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
						echo t("tuoteperhe on jo olemassa! ei voida lis�t�"),"<br>";
						$virhe++;
					}
				}

				if (strtoupper(trim($otsikot[$r])) == "TUOTENO") {
					$query = "	SELECT tunnus
								FROM tuote
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '{$rivi[$r]}'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 0) {
						echo t("tuotetta")," {$rivi[$r]} ",t("ei l�ydy! rivi hyl�tty"),"<br>";
						$virhe++;
					}
				}
			} // end for

			// jos ei ole virheit�, lis�ill��n rivej�
			if ($virhe == 0 and $isatuote != "") {

				$lask = 0;

				// poistetaan eka kaikki.. heh
				$query = "	DELETE FROM tuoteperhe
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND isatuoteno = '$isatuote'
							AND tyyppi = '$tyyppi'";
				$result = mysql_query($query) or pupe_error($query);

				for ($r = 0; $r < count($otsikot); $r++) {

					if (strtoupper(trim($otsikot[$r])) == "TUOTENO") {
						$query  = "	INSERT INTO tuoteperhe SET
									yhtio = '{$kukarow['yhtio']}',
									isatuoteno = '$isatuote',
									tuoteno = '{$rivi[$r]}',
									tyyppi = '$tyyppi'";
						$result = mysql_query($query) or pupe_error($query);
						$lask++;
					}

				}
			}
		}

		$rivi = fgets($file);
	} // end while eof

	fclose($file);
	echo t("P�ivitettiin")," $lask ",t("rivi�"),"!";
}
else {
	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<br><br>
			<table border='0'>
			<tr>
				<td>",t("Valitse tietokanna taulu"),":</td>
				<td><select name='table'>
					<option value='korvaavat'>",t("Korvaavat"),"</option>
					<option value='tuoteperhe'>",t("Tuoteperheet"),"</option>
					<option value='tuoteresepti'>",t("Tuotereseptit"),"</option>
				</select></td>
			</tr>

			<input type='hidden' name='tee' value='file'>

			<tr><td>",t("Valitse tiedosto"),":</td>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='",t("L�het�"),"'></td>
			</tr>

			</table>
			</form>";
}

require ("inc/footer.inc");

?>
