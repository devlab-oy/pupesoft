<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Tuoteketjujen sis��nluku")."</font><hr>";

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
$taulunrivit	= array();

if (isset($_FILES['userfile']['tmp_name']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

	$path_parts = pathinfo($_FILES['userfile']['name']);
	$ext = strtoupper($path_parts['extension']);

	if ($ext != "TXT" and $ext != "XLS" and $ext != "CSV") {
		die ("<font class='error'><br>".t("Ainoastaan .txt, .csv tai .xls tiedostot sallittuja")."!</font>");
	}

	if ($_FILES['userfile']['size'] == 0) {
		die ("<font class='error'><br>".t("Tiedosto on tyhj�")."!</font>");
	}

	if ($ext == "XLS") {
		require_once ('excel_reader/reader.php');

		// ExcelFile
		$data = new Spreadsheet_Excel_Reader();

		// Set output Encoding.
		$data->setOutputEncoding('CP1251');
		$data->setRowColOffset(0);
		$data->read($_FILES['userfile']['tmp_name']);
	}

	// luetaan eka rivi tiedostosta..
	if ($ext == "XLS") {
		$headers = array();

		for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
			$headers[] = strtoupper(trim($data->sheets[0]['cells'][0][$excej]));
		}

		for ($excej = (count($headers)-1); $excej > 0 ; $excej--) {
			if ($headers[$excej] != "") {
				break;
			}
			else {
				unset($headers[$excej]);
			}
		}
	}
	else {
		$file	 = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep�onnistui")."!");

		$rivi    = fgets($file);
		$headers = explode("\t", strtoupper(trim($rivi)));
	}

	echo "<font class='message'>".t("Tarkastetaan l�hetetty tiedosto")."...<br><br></font>";

	$table_apu = $table;
	$table = ($table != 'korvaavat' and $table != "vastaavat") ? 'tuoteperhe' : $table;

	// haetaan valitun taulun sarakkeet
	$query = "SHOW COLUMNS FROM $table";
	$fres  = pupe_query($query);

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
		case "vastaavat" :
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
	// $trows 	sis�lt�� kaikki taulun sarakkeet tietokannasta
	// $headers sis�lt�� kaikki sarakkeet saadusta tiedostosta

	foreach ($headers as $column) {

		$column = strtoupper(trim($column));

		if ($column != '') {
			//laitetaan kaikki paitsi valintasarake talteen.
			if ($column != "TOIMINTO") {
				if (!in_array($column, $trows)) {
					echo "<br><font class='message'>",t("Saraketta")," \"<b>",strtoupper($column),"</b>\" ",t("ei l�ydy")," $table-taulusta!</font>";
					$vikaa++;
				}

				// yhtio ja tunnus kentti� ei saa koskaan muokata...
				if ($column == 'YHTIO' or $column == 'TUNNUS') {
					echo "<br><font class='message'>",t("YHTIO ja/tai TUNNUS sarakkeita ei saa muuttaa"),"!</font>";
					$vikaa++;
				}

				if (in_array($column, $pakolliset)) {
					$tarkea++;
				}
			}

			if ($column == "TOIMINTO") {
				//TOIMINTO sarakkeen positio tiedostossa
				$postoiminto = (string) array_search($column, $headers);
			}

			if (in_array($column, $kielletyt)) {
				// katotaan ettei kiellettyj� sarakkkeita muuteta
				echo t("Sarake"),": $column ",t("on kielletty sarake"),"!<br>";
				$kielletty++;
			}
		}
	}

	// oli virheellisi� sarakkeita tai pakollisia ei l�ytynyt..
	if ($vikaa != 0 or $tarkea < count($pakolliset)) {
		die("<br><br><font class='error'>".t("VIRHE: Pakollisisa sarakkeita puuttuu! Ei voida jatkaa")."!<br></font>");
	}
	
	// oli virheellisi� sarakkeita tai pakollisia ei l�ytynyt..
	if ($postoiminto == 'X') {
		die("<br><br><font class='error'>".t("VIRHE: Toiminto-sarake puuttuu! Ei voida jatkaa")."!<br></font>");
	}

	if ($kielletty > 0) {
		echo "<br><font class='message'>",t("Kiellettyj� l�ytyi, ei voida jatkaa"),"...<br></font>";
		exit;
	}

	echo "<font class='message'>",t("Tiedosto ok, aloitellaan p�ivitys"),"...<br><br></font>";
	flush();

	// Luetaan tiedosto loppuun ja tehd��n taulukohtainen array koko datasta
	if ($ext == "XLS") {
		for ($excei = 1; $excei < $data->sheets[0]['numRows']; $excei++) {
			for ($excej = 0; $excej < count($headers); $excej++) {
				$taulunrivit[$excei-1][] = trim($data->sheets[0]['cells'][$excei][$excej]);
			}
		}
	}
	else {

		$excei = 0;

		while ($rivi = fgets($file)) {
			// luetaan rivi tiedostosta..
			$rivi = explode("\t", str_replace(array("'", "\\"), "", $rivi));

			for ($excej = 0; $excej < count($rivi); $excej++) {
				$taulunrivit[$excei][] = trim($rivi[$excej]);
			}

			$excei++;
		}
		fclose($file);
	}

	/*
	echo "<table>";
	echo "<tr>";

	foreach ($headers as $key => $column) {
		echo "<th>$key => $column</th>";
	}

	echo "</tr>";

	foreach ($taulunrivit as $rivi) {
		echo "<tr>";

		for ($eriviindex = 0; $eriviindex < count($rivi); $eriviindex++) {
			echo "<td>$eriviindex => $rivi[$eriviindex]</td>";
		}
	}

	echo "</table><br>";
	#exit;
	*/

	// luetaan tiedosto loppuun...
	foreach ($taulunrivit as $rivinumero => $rivi) {

		// n�in k�sitell��n korvaavat taulu
		if ($table == "korvaavat" or $table == "vastaavat") {

			$haku = '';
			for ($j = 0; $j < count($rivi); $j++) {
				//otetaan rivin kaikki tuotenumerot talteen
				if ($headers[$j] == "TUOTENO" and $rivi[$j] != "") {
					$haku .= "'$rivi[$j]',";
				}
			}
			$haku = substr($haku, 0, -1);

			if ($haku == "") continue;

			$fquery = "	SELECT distinct id
						FROM $table
						WHERE tuoteno in ($haku)
						and yhtio = '{$kukarow['yhtio']}'";
			$hresult = pupe_query($fquery);

			if (mysql_num_rows($hresult) == 0) {
				$fquery = "	SELECT max(id)
							FROM $table
							WHERE yhtio = '{$kukarow['yhtio']}'";
				$fresult = pupe_query($fquery);
				$frow =  mysql_fetch_array($fresult);

				$id = $frow[0] + 1;
				#echo t("Ei viel� miss��n")," $id!<br>";
			}
			elseif (mysql_num_rows($hresult) == 1) {
				$frow =  mysql_fetch_array($hresult);
				$id = $frow[0];
				#echo t("L�ytyi")," $id!<br>";
			}
			else {
				echo t("Joku tuotteista")," ($haku) ",t("on jo useassa ketjussa! Korjaa homma"),"!<br>";
				$id = 0;
			}

			if ($id > 0) {
				if (strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
					$alku 		= "INSERT into $table SET yhtio = '{$kukarow['yhtio']}'";
					$loppu 		= ", id='$id'";
					$toiminto 	= "LISAA";
				}
				elseif (strtoupper(trim($rivi[$postoiminto])) == 'MUUTA') {
					$toiminto 	= "MUUTA";
				}
				elseif (strtoupper(trim($rivi[$postoiminto])) == 'POISTA') {
					$alku 		= "DELETE from $table where yhtio = '{$kukarow['yhtio']}' ";
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
					if ($headers[$j] == "TUOTENO" and trim($rivi[$j]) != '') {

						$jarjestys = 0;

						// Katotaan onko seuraava sarake j�rjestys
						if ($headers[$j+1] == "JARJESTYS") {
							$jarjestys = $taulunrivit[$rivinumero][$j+1];
						}

						//katotaan, ett� tuote l�ytyy
						$tquery = "	SELECT tuoteno
									FROM tuote
									WHERE tuoteno = '$rivi[$j]'
									and yhtio = '{$kukarow['yhtio']}'";
						$tresult = pupe_query($tquery);

						if (mysql_num_rows($tresult) > 0) {
							//katotaan, onko tuote jo jossain ketjussa
							$kquery = "	SELECT tuoteno
										FROM $table
										WHERE tuoteno = '$rivi[$j]'
										and id = '$id'
										and yhtio = '{$kukarow['yhtio']}'";
							$kresult = pupe_query($kquery);

							if ((mysql_num_rows($kresult) == 0 and $toiminto == 'LISAA') or (mysql_num_rows($kresult) == 1 and $toiminto == 'POISTA')) {

								if ($toiminto == 'LISAA') {
									if ($jarjestys == 0) {
										$kquery = "	SELECT max(jarjestys) jarjestys
													FROM $table
													WHERE yhtio = '{$kukarow['yhtio']}'
													and id = '$id'";
										$iresult = pupe_query($kquery);
										$irow = mysql_fetch_assoc($iresult);

										$jarjestys = (int) $irow["jarjestys"] + 1;
									}

									$kysely = ", tuoteno='$rivi[$j]', jarjestys='$jarjestys', laatija='$kukarow[kuka]', luontiaika=now(), muuttaja='$kukarow[kuka]', muutospvm=now() ";
								}
								else {
									$kysely = " and tuoteno='$rivi[$j]' ";
								}

								$query = $alku.$kysely.$loppu;
								$iresult = pupe_query($query);

								if ($toiminto == 'LISAA') {
									echo t("Lis�ttiin ketjuun")," $id {$rivi[$j]}!<br>";
								}
								else {
									echo t("Poistettiin ketjusta")," $id {$rivi[$j]}!<br>";
								}
							}
							elseif ($toiminto == "MUUTA" and $jarjestys > 0) {
								// Korjataan muut j�rjestykset ja tehd��n tilaa p�ivitett�v�lle tuotteelle
								$kquery = "	SELECT tunnus, if(jarjestys=0, 999, jarjestys) jarj
											FROM $table
											WHERE yhtio = '{$kukarow['yhtio']}'
											and id = '$id'
											and tuoteno != '$rivi[$j]'
											and (jarjestys >= $jarjestys or jarjestys = 0)
											ORDER BY jarj, tuoteno";
								$iresult = pupe_query($kquery);

								$siirtojarj = $jarjestys+1;

								while ($irow = mysql_fetch_assoc($iresult)) {
									$kquery = "	UPDATE $table
												SET jarjestys = $siirtojarj,
												muuttaja = '$kukarow[kuka]',
												muutospvm = now()
												WHERE tunnus = '$irow[tunnus]'";
									$updres = pupe_query($kquery);

									$siirtojarj++;
								}

								$kquery = "	UPDATE $table
											SET jarjestys = $jarjestys,
											muuttaja = '$kukarow[kuka]',
											muutospvm = now()
											WHERE tuoteno = '$rivi[$j]'
											and id = '$id'
											and yhtio = '{$kukarow['yhtio']}'";
								$iresult = pupe_query($kquery);

								$lask++;
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
			$tyyppi = "";
			if ($table_apu == 'tuoteperhe') $tyyppi = 'P';
			if ($table_apu == 'tuoteresepti') $tyyppi = 'R';
			if ($table_apu == 'vsuunnittelu') $tyyppi = 'S';
			if ($table_apu == 'osaluettelo') $tyyppi = 'O';
			if ($table_apu == 'tuotekooste') $tyyppi = 'V';
			if ($table_apu == 'lisavaruste') $tyyppi = 'L';

			for ($r = 0; $r < count($headers); $r++) {

				// jos k�sitell��n is�tuote-kentt��n
				if (strtoupper(trim($headers[$r])) == "ISATUOTENO") {

					$query = "	SELECT tunnus
								FROM tuote
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '{$rivi[$r]}'";
					$result = pupe_query($query);

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
					$result = pupe_query($query);

					if (mysql_num_rows($result) == 0 and strtoupper(trim($rivi[$postoiminto])) == 'MUUTA') {
						echo t("tuoteperhett� ei l�ydy! ei voida muuttaa"),"<br>";
						$virhe++;
					}
					elseif (mysql_num_rows($result) != 0 and strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
						echo t("tuoteperhe on jo olemassa! ei voida lis�t�"),"<br>";
						$virhe++;
					}
				}

				if (strtoupper(trim($headers[$r])) == "TUOTENO") {
					$query = "	SELECT tunnus
								FROM tuote
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '{$rivi[$r]}'";
					$result = pupe_query($query);

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
				$result = pupe_query($query);

				for ($r = 0; $r < count($headers); $r++) {

					if (strtoupper(trim($headers[$r])) == "TUOTENO") {
						$query  = "	INSERT INTO tuoteperhe SET
									yhtio 		= '{$kukarow['yhtio']}',
									isatuoteno 	= '$isatuote',
									tuoteno 	= '{$rivi[$r]}',
									tyyppi 		= '$tyyppi',
									laatija 	= '$kukarow[kuka]',
									luontiaika 	= now(),
									muuttaja 	= '$kukarow[kuka]',
									muutospvm 	= now()";
						$result = pupe_query($query);
						$lask++;
					}
				}
			}
		}
	}

	echo t("P�ivitettiin")," $lask ",t("rivi�"),"!";
}
else {
	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<table border='0'>
			<tr>
				<th>",t("Valitse tietokannan taulu"),":</th>
				<td><select name='table'>
					<option value='korvaavat'>",t("Korvaavat tuotteet"),"</option>
					<option value='vastaavat'>",t("Vastaavat tuotteet"),"</option>
					<option value='tuoteperhe'>",t("Tuoteperheet"),"</option>
					<option value='tuoteresepti'>",t("Tuotereseptit"),"</option>
					<option value='osaluettelo'>",t("Tuotteen osaluettelo"),"</option>
					<option value='tuotekooste'>",t("Tuotteen koosteluettelo"),"</option>
					<option value='lisavaruste'>",t("Tuotteen lis�varusteet"),"</option>
					<option value='vsuunnittelu'>",t("Samankaltaiset valmisteet"),"</option>
				</select></td>
			</tr>

			<input type='hidden' name='tee' value='file'>

			<tr><th>",t("Valitse tiedosto"),":</th>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='",t("L�het�"),"'></td>
			</tr>

			</table>
			</form>";
}

require ("inc/footer.inc");

?>