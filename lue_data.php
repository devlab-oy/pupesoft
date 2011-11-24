<?php

if (php_sapi_name() == 'cli') {
	require('inc/connect.inc');
	require('inc/functions.inc');
	$cli = true;

	ini_set("mysql.connect_timeout", 600);
	ini_set("memory_limit", "1G");

	if (trim($argv[1]) != '') {
		$kukarow['yhtio'] = mysql_real_escape_string($argv[1]);
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
	}
	else {
		die ("Et antanut yhtiötä.\n");
	}

	if (trim($argv[2]) != '') {
		$table = trim($argv[2]);
	}
	else {
		die ("Et antanut taulun nimeä.\n");
	}

	$kukarow['kuka'] = "cli";

	if (trim($argv[3]) != '') {
		$path_parts = pathinfo(trim($argv[3]));
		$_FILES['userfile']['name'] = $path_parts['basename'];
		$_FILES['userfile']['type'] = (strtoupper($path_parts['extension']) == 'TXT' or strtoupper($path_parts['extension']) == 'CSV') ? 'text/plain' : (strtoupper($path_parts['extension']) == 'XLS') ? 'application/vnd.ms-excel' : '';
		$_FILES['userfile']['tmp_name'] = $argv[3];
		$_FILES['userfile']['error'] = 0; // UPLOAD_ERR_OK
		$_FILES['userfile']['size'] = filesize($argv[3]);
	}
	else {
		die ("Et antanut tiedoston nimeä ja polkua.\n");
	}
}
else {
	require ("inc/parametrit.inc");
	$cli = false;
}

// Laitetaan max time 5H
ini_set("max_execution_time", 18000);

if (!$cli) echo "<font class='head'>".t("Datan sisäänluku")."</font><hr>";

if (!$cli and $oikeurow['paivitys'] != '1') { // Saako päivittää
	if ($uusi == 1) {
		echo "<b>".t("Sinulla ei ole oikeutta lisätä")."</b><br>";
		$uusi = '';
	}
	if ($del == 1) {
		echo "<b>".t("Sinulla ei ole oikeuttaa poistaa")."</b><br>";
		$del = '';
		$tunnus = 0;
	}
	if ($upd == 1) {
		echo "<b>".t("Sinulla ei ole oikeuttaa muuttaa")."</b><br>";
		$upd = '';
		$uusi = 0;
		$tunnus = 0;
	}
}

flush();
if (!isset($table)) $table = '';
$kasitellaan_tiedosto = FALSE;

if (isset($_FILES['userfile']) and (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE or ($cli and trim($_FILES['userfile']['tmp_name']) != ''))) {

	$kasitellaan_tiedosto = TRUE;

	require ("inc/pakolliset_sarakkeet.inc");

	if ($_FILES['userfile']['size'] == 0) {
		echo "<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>";
		$kasitellaan_tiedosto = FALSE;
	}

	$path_parts = pathinfo($_FILES['userfile']['name']);
	$ext = strtoupper($path_parts['extension']);

	if (!$cli) echo "<font class='message'>".t("Tarkastetaan lähetetty tiedosto")."...<br><br></font>";

	$retval = tarkasta_liite("userfile", array("XLSX","XLS","ODS","SLK","XML","GNUMERIC","CSV","TXT"));

	if ($retval !== TRUE) {
		echo "<font class='error'><br>".t("Väärä tiedostomuoto")."!</font>";
		$kasitellaan_tiedosto = FALSE;
	}
}

if ($kasitellaan_tiedosto) {

	if ($ext == "CSV" or $ext == "TXT") {
		/** Ladataan file **/
		$file = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");

		/** Laitetaan rivit arrayseen **/
		$excelrivit = array();

		$rowIndex = 0;

		while ($rivi = fgets($file)) {
			// luetaan rivi tiedostosta..
			$rivi = explode("\t", str_replace(array("'", "\\"), "", $rivi));

			for ($colIndex = 0; $colIndex < count($rivi); $colIndex++) {
				$excelrivit[$rowIndex][$colIndex] = trim($rivi[$colIndex]);
			}

			$rowIndex++;
		}

		fclose($file);
	}
	else {
		/** PHPExcel kirjasto **/
		require_once "PHPExcel/PHPExcel/IOFactory.php";

		/** Tunnistetaan tiedostomuoto **/
		$inputFileType = PHPExcel_IOFactory::identify($_FILES['userfile']['tmp_name']);

		/** Luodaan readeri **/
		$objReader = PHPExcel_IOFactory::createReader($inputFileType);

		/** Ladataan vain solujen datat (ei formatointeja jne) **/
		$objReader->setReadDataOnly(true);

		/** Ladataan file halutuilla parametreilla **/
		$objPHPExcel = $objReader->load($_FILES['userfile']['tmp_name']);

		/** Laitetaan rivit arrayseen **/
		$excelrivit = array();

		/** Aktivoidaan eka sheetti**/
		$objPHPExcel->setActiveSheetIndex(0);

		/** Loopataan tiedoston rivit **/
		foreach ($objPHPExcel->getActiveSheet()->getRowIterator() as $row) {
		    $cellIterator = $row->getCellIterator();
		    $cellIterator->setIterateOnlyExistingCells(false);

			$rowIndex = ($row->getRowIndex())-1;

		    foreach ($cellIterator as $cell) {
		        $colIndex = (PHPExcel_Cell::columnIndexFromString($cell->getColumn()))-1;

				$excelrivit[$rowIndex][$colIndex] = trim(utf8_decode($cell->getCalculatedValue()));
		    }
		}

		/** Tuhotaan excel oliot **/
		unset($objReader);
		unset($objPHPExcel);
	}

	/** Otetaan tiedoston otsikkorivi **/
	$headers = $excelrivit[0];
	$headers = array_map('trim', $headers);
	$headers = array_map('strtoupper', $headers);

	// Unsetatan tyhjät sarakkeet
	for ($i = (count($headers)-1); $i > 0 ; $i--) {
		if ($headers[$i] != "") {
			break;
		}
		else {
			unset($headers[$i]);
		}
	}

	$taulut			= array();
	$mul_taulut 	= array();
	$mul_taulas 	= array();
	$taulunotsikot	= array();
	$taulunrivit	= array();

	// Katsotaan onko sarakkeita useasta taulusta
	for ($i = 0; $i < count($headers); $i++) {
		if (strpos($headers[$i], ".") !== FALSE) {

			list($taulu, $sarake) = explode(".", $headers[$i]);
			$taulu = strtolower(trim($taulu));

			// Joinataanko sama taulu monta kertaa?
			if ((isset($mul_taulas[$taulu]) and isset($mul_taulut[$taulu."__".$mul_taulas[$taulu]]) and in_array($headers[$i], $mul_taulut[$taulu."__".$mul_taulas[$taulu]])) or (isset($mul_taulut[$taulu]) and (!isset($mul_taulas[$taulu]) or !isset($mul_taulut[$taulu."__".$mul_taulas[$taulu]])) and in_array($headers[$i], $mul_taulut[$taulu]))) {
				$mul_taulas[$taulu]++;

				$taulu = $taulu."__".$mul_taulas[$taulu];
			}
			elseif (isset($mul_taulas[$taulu]) and isset($mul_taulut[$taulu."__".$mul_taulas[$taulu]])) {
				$taulu = $taulu."__".$mul_taulas[$taulu];
			}

			$taulut[] = $taulu;
			$mul_taulut[$taulu][] = $headers[$i];
		}
		else {
			$taulut[] = $table;
		}
	}

	// Tässä kaikki taulut jotka löyty failista
	$unique_taulut = array_unique($taulut);

	// Tutkitaan millä ehdoilla taulut joinataan keskenään
	$joinattavat = array();

	$table_tarkenne = '';

	foreach ($unique_taulut as $utaulu) {
		list($taulu, ) = explode(".", $utaulu);
		$taulu = preg_replace("/__[0-9]*$/", "", $taulu);

		if (substr($taulu, 0, 11) == 'puun_alkio_') {
			$taulu = 'puun_alkio';
			$table_tarkenne = substr($taulu, 11);
		}

		list(, , , , $joinattava, ) = pakolliset_sarakkeet($taulu);

		// Laitetaan aina kaikkiin tauluihin
		$joinattava["TOIMINTO"] = $table;

		$joinattavat[$utaulu] = $joinattava;
	}

	// Laitetaan jokaisen taulun otsikkorivi kuntoon
	for ($i = 0; $i < count($headers); $i++) {

		if (strpos($headers[$i], ".") !== FALSE) {
			list($sarake1, $sarake2) = explode(".", $headers[$i]);
			if ($sarake2 != "") $sarake1 = $sarake2;
		}
		else {
			$sarake1 = $headers[$i];
		}

		$sarake1 = strtoupper(trim($sarake1));

		$taulunotsikot[$taulut[$i]][] = $sarake1;

		// Pitääkö tämä sarake laittaa myös johonki toiseen tauluun?
		foreach ($joinattavat as $taulu => $joinit) {

			if (strpos($headers[$i], ".") !== FALSE) {
				list ($etu, $taka) = explode(".", $headers[$i]);
				if ($taka == "") $taka = $etu;
			}
			else {
				$taka = $headers[$i];
			}

			if (isset($joinit[$taka]) and (!isset($taulunotsikot[$taulu]) or !in_array($sarake1, $taulunotsikot[$taulu]))) {
				$taulunotsikot[$taulu][] = $sarake1;
			}
		}
	}

	foreach ($taulunotsikot as $taulu => $otsikot) {
		if (count($otsikot) != count(array_unique($otsikot))) {
			echo "<font class='error'>$taulu-".t("taulun sarakkeissa ongelmia, ei voida jatkaa")."!</font><br>";

			require ("inc/footer.inc");
			exit;
		}
	}

	// rivimäärä excelissä
	$excelrivimaara = count($excelrivit);

	// sarakemäärä excelissä
	$excelsarakemaara = count($headers);

	// Luetaan tiedosto loppuun ja tehdään taulukohtainen array koko datasta
	for ($excei = 1; $excei < $excelrivimaara; $excei++) {
		for ($excej = 0; $excej < $excelsarakemaara; $excej++) {

			$taulunrivit[$taulut[$excej]][$excei-1][] = trim($excelrivit[$excei][$excej]);

			// Pitääkö tämä sarake laittaa myös johonki toiseen tauluun?
			foreach ($taulunotsikot as $taulu => $joinit) {

				if (strpos($headers[$excej], ".") !== FALSE) {
					list ($etu, $taka) = explode(".", $headers[$excej]);
					if ($taka == "") $taka = $etu;
				}
				else {
					$taka = $headers[$excej];
				}

				if (in_array($taka, $joinit) and $taulu != $taulut[$excej] and $taulut[$excej] == $joinattavat[$taulu][$taka]) {
					$taulunrivit[$taulu][$excei-1][] = trim($excelrivit[$excei][$excej]);
				}
			}
		}

		// Tuhotaan as we go, ku näitä ei enää tarvita...
		unset($excelrivit[$excei]);
	}

	// Korjataan spessujoini yhteensopivuus_tuote_lisatiedot/yhteensopivuus_tuote
	if (in_array("yhteensopivuus_tuote", $taulut) and in_array("yhteensopivuus_tuote_lisatiedot", $taulut)) {

		foreach ($taulunotsikot["yhteensopivuus_tuote_lisatiedot"] as $key => $column) {
			if ($column == "TUOTENO") {
				$joinsarake = $key;
				break;
			}
		}

		// Vaihdetaan otsikko
		$taulunotsikot["yhteensopivuus_tuote_lisatiedot"][$joinsarake] = "YHTEENSOPIVUUS_TUOTE_TUNNUS";

		// Tyhjennetään arvot
		foreach ($taulunrivit["yhteensopivuus_tuote_lisatiedot"] as $ind => $rivit) {
			$taulunrivit["yhteensopivuus_tuote_lisatiedot"][$ind][$joinsarake] = "";
		}
	}

	/*
	foreach ($taulunrivit as $taulu => $rivit) {

		list($table_mysql, ) = explode(".", $taulu);
		$table_mysql = preg_replace("/__[0-9]*$/", "", $table_mysql);

		echo "<table>";
		echo "<tr><th>$table_mysql</th>";
		foreach ($taulunotsikot[$taulu] as $key => $column) {
			echo "<th>$key => $column</th>";
		}
		echo "</tr>";
		for ($eriviindex = 0; $eriviindex < count($rivit); $eriviindex++) {
			echo "<tr><th>$table_mysql</th>";
			foreach ($rivit[$eriviindex] as $eriv) {
				echo "<td>$eriv</td>";
			}
			echo "</tr>";
		}
		echo "</table><br>";
	}
	exit;
	*/

	$taulunrivit_keys = array_keys($taulunrivit);

	for ($tril = 0; $tril < count($taulunrivit); $tril++) {

		$taulu = $taulunrivit_keys[$tril];
		$rivit = $taulunrivit[$taulu];

		$vikaa			= 0;
		$tarkea			= 0;
		$wheretarkea	= 0;
		$kielletty		= 0;
		$lask			= 0;
		$postoiminto	= 'X';
		$table_mysql 	= "";
		$tarkyhtio		= "";
		$tarkylisa 		= 1;
		$indeksi		= array();
		$indeksi_where	= array();
		$trows			= array();
		$tlength		= array();
		$apu_sarakkeet	= array();
		$rivimaara 		= count($rivit);
		$dynaamiset_rivit = array();

		// Siivotaan joinit ja muut pois tietokannan nimestä
		list($table_mysql, ) = explode(".", $taulu);
		$table_mysql = preg_replace("/__[0-9]*$/", "", $table_mysql);

		if (substr($table_mysql, 0, 11) == 'puun_alkio_') {
			$table_tarkenne = substr($table_mysql, 11);
			$table_mysql = 'puun_alkio';
		}

		// jos tullaan jotenkin hassusti, nmiin ei tehdä mitään
		if (trim($table_mysql) == "") continue;

		// Haetaan valitun taulun sarakkeet
		$query = "SHOW COLUMNS FROM $table_mysql";
		$fres  = pupe_query($query);

		while ($row = mysql_fetch_array($fres)) {
			// Pushataan arrayseen kaikki sarakenimet ja tietuetyypit
			$trows[$table_mysql.".".strtoupper($row[0])] = $row[1];

			$tlengthpit = preg_replace("/[^0-9,]/", "", $row[1]);

			if (strpos($tlengthpit, ",") !== FALSE) {
				$tlengthpit = substr($tlengthpit, 0, strpos($tlengthpit, ",")+1)+1;
			}

			$tlength[$table_mysql.".".strtoupper($row[0])] = trim($tlengthpit);
		}

		// Nämä ovat pakollisia dummysarakkeita jotka ohitetaan lopussa automaattisesti!
		if (in_array($table_mysql, array("yhteyshenkilo", "asiakkaan_avainsanat"))) {
			$apu_sarakkeet = array("YTUNNUS");
		}

		if (count($apu_sarakkeet) > 0) {
			foreach($apu_sarakkeet as $s) {
				$trows[$table_mysql.".".strtoupper($s)] = "";
			}
		}

		if ($table_mysql == 'tullinimike') {
			$tulli_ei_kielta = "";
			$tulli_ei_toimintoa = "";

			if (in_array("KIELI", $taulunotsikot[$taulu]) === FALSE) {
				$tulli_ei_kielta = "PUUTTUU";
				$taulunotsikot[$taulu][] = "KIELI";
			}
			if (in_array("TOIMINTO", $taulunotsikot[$taulu]) === FALSE) {
				$taulunotsikot[$taulu][] = "TOIMINTO";
				$tulli_ei_toimintoa = "PUUTTUU";
			}
		}

		// Otetaan pakolliset, kielletyt, wherelliset ja eiyhtiota tiedot
		list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, , $saakopoistaa) = pakolliset_sarakkeet($table_mysql, $taulunotsikot[$taulu]);

		// $trows sisältää kaikki taulun sarakkeet ja tyypit tietokannasta
		// $taulunotsikot[$taulu] sisältää kaikki sarakkeet saadusta tiedostosta
		foreach ($taulunotsikot[$taulu] as $key => $column) {
			if ($column != '') {
				if ($column == "TOIMINTO") {
					//TOIMINTO sarakkeen positio tiedostossa
					$postoiminto = (string) array_search($column, $taulunotsikot[$taulu]);
				}
				else {
					if (!isset($trows[$table_mysql.".".$column]) and $column != "AVK_TUNNUS") {
						echo "<font class='error'>".t("Saraketta")." \"$column\" ".t("ei löydy")." $table_mysql-".t("taulusta")."!</font><br>";
						$vikaa++;
					}

					// yhtio ja tunnus kenttiä ei saa koskaan muokata...
					if ($column == 'YHTIO' or $column == 'TUNNUS') {
						echo "<font class='error'>".t("Yhtiö- ja tunnussaraketta ei saa muuttaa")." $table_mysql-".t("taulussa")."!</font><br>";
						$vikaa++;
					}

					if (in_array($column, $pakolliset)) {
						// pushataan positio indeksiin, että tiedetään missä kohtaa avaimet tulevat
						$pos = array_search($column, $taulunotsikot[$taulu]);
						$indeksi[$column] = $pos;
						$tarkea++;
					}

					if (in_array($column, $kielletyt)) {
						// katotaan ettei kiellettyjä sarakkeita muuteta
						$viesti .= t("Sarake").": $column ".t("on kielletty sarake")." $table_mysql-".t("taulussa")."!<br>";
						$kielletty++;
					}

					if (is_array($wherelliset) and in_array($column, $wherelliset)) {
						// katotaan että määritellyt where lausekkeen ehdot löytyvät
						$pos = array_search($column, $taulunotsikot[$taulu]);
						$indeksi_where[$column] = $pos;
						$wheretarkea++;
					}
				}
			}
			else {
				$vikaa++;
				echo "<font class='error'>".t("Tiedostossa on tyhjiä sarakkeiden otsikoita")."!</font><br>";
			}
		}

		// Oli virheellisiä sarakkeita tai pakollisia ei löytynyt..
		if ($vikaa != 0 or $tarkea != count($pakolliset) or $postoiminto == 'X' or $kielletty > 0 or (is_array($wherelliset) and $wheretarkea != count($wherelliset))) {

			if ($vikaa != 0) {
				echo "<font class='error'>".t("Vääriä sarakkeita tai yritit muuttaa yhtiö/tunnus saraketta")."!</font><br>";
			}

			if ($tarkea != count($pakolliset)) {
				echo "<font class='error'>".t("Pakollisia/tärkeitä kenttiä puuttuu")."! ( ";

				foreach ($pakolliset as $apupako) echo "$apupako ";

				echo " ) $table_mysql-".t("taulusta")."!</font><br>";
			}

			if ($postoiminto == 'X') {
				echo "<font class='error'>".t("Toiminto sarake puuttuu")."!</font><br>";
			}

			if ($kielletty > 0) {
				echo "<font class='error'>".t("Yrität päivittää kiellettyjä sarakkeita")." $table_mysql-".t("taulussa")."!</font><br>$viesti";
			}

			if (is_array($wherelliset) and $wheretarkea != count($wherelliset)) {
				echo "<font class='error'>".t("Sinulta puuttui jokin pakollisista sarakkeista")." (";

				foreach ($wherelliset as $apupako) echo "$apupako ";

				echo ") $table_mysql-".t("taulusta")."!</font><br>";
			}

			echo "<font class='error'>".t("Virheitä löytyi. Ei voida jatkaa")."!<br></font>";
			require ("inc/footer.inc");
			exit;
		}

		if (!$cli) echo "<br><font class='message'>".t("Tiedosto ok, aloitetaan päivitys")." $table_mysql-".t("tauluun")."...<br></font>";
		flush();

		$rivilaskuri = 1;

		$puun_alkio_index_plus = 0;

		$max_rivit = count($rivit);

		for ($eriviindex = 0; $eriviindex < (count($rivit) + $puun_alkio_index_plus); $eriviindex++) {

			if ($cli) progress_bar($eriviindex, $max_rivit);

			$hylkaa    = 0;
			$tila      = "";
			$tee       = "";
			$eilisataeikamuuteta = "";
			$rivilaskuri++;

			//asiakashinta/asiakasalennus spessuja
			$chasiakas_ryhma 	= '';
			$chytunnus 			= '';
			$chryhma 			= '';
			$chtuoteno 			= '';
			$chasiakas			= 0;
			$chsegmentti		= 0;
			$chpiiri			= '';
			$chminkpl			= 0;
			$chmaxkpl			= 0;
			$chalennuslaji		= 0;
			$chmonikerta		= "";
			$chalkupvm 			= '0000-00-00';
			$chloppupvm 		= '0000-00-00';
			$and 				= '';
			$tpupque 			= '';
			$toimi_liitostunnus = '';

			if ($rivilaskuri % 500 == 0) {
				if (!$cli) echo "<font class='message'>Käsitellään riviä: $rivilaskuri</font><br>";
				flush();
			}

			if ($eiyhtiota == "" or $eiyhtiota == "EILAATIJAA") {
				$valinta   = " yhtio = '{$kukarow['yhtio']}'";
			}
			elseif ($eiyhtiota == "TRIP") {
				$valinta   = " tunnus > 0 ";
			}

			// Rakennetaan rivikohtainen array
			$rivi = array();

			foreach ($rivit[$eriviindex] as $eriv) {
				$rivi[] = $eriv;
			}

			if ($table_mysql == 'tullinimike' and $tulli_ei_kielta != "") {
				$rivi[] = "FI";
			}

			if ($table_mysql == 'tullinimike' and $tulli_ei_toimintoa != "") {
				$rivi[] = "LISAA";
			}

			// Rivin toiminto
			$rivi[$postoiminto] = strtoupper(trim($rivi[$postoiminto]));

			//Sallitaan myös MUOKKAA ja LISÄÄ toiminnot
			if ($rivi[$postoiminto] == "LISÄÄ") $rivi[$postoiminto] = "LISAA";
			if ($rivi[$postoiminto] == "MUOKKAA") $rivi[$postoiminto] = "MUUTA";
			if ($rivi[$postoiminto] == "MUOKKAA/LISÄÄ") $rivi[$postoiminto] = "MUUTA/LISAA";
			if ($rivi[$postoiminto] == "MUOKKAA/LISAA") $rivi[$postoiminto] = "MUUTA/LISAA";
			if ($rivi[$postoiminto] == "MUUTA/LISÄÄ") $rivi[$postoiminto] = "MUUTA/LISAA";
			if ($rivi[$postoiminto] == "POISTA") $rivi[$postoiminto] = "POISTA";

			//Jos eri where-ehto array on määritelty
			if (is_array($wherelliset)) {
				$indeksi = array_merge($indeksi, $indeksi_where);
				$indeksi = array_unique($indeksi);
			}

			$avkmuuttuja = FALSE;

			foreach ($indeksi as $j) {
				if ($taulunotsikot[$taulu][$j] == "TUOTENO") {

					$tuoteno = trim($rivi[$j]);

					$valinta .= " and TUOTENO='$tuoteno'";
				}
				elseif ($table_mysql == 'tullinimike' and strtoupper($taulunotsikot[$taulu][$j]) == "CN") {

					$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = str_replace(' ','',$rivi[$j]);

					$valinta .= " and cn='".$rivi[$j]."'";

					if (trim($rivi[$j]) == '') {
						$tila = 'ohita';
					}
				}
				elseif ($table_mysql == 'extranet_kayttajan_lisatiedot' and strtoupper($taulunotsikot[$taulu][$j]) == "LIITOSTUNNUS" and $liitostunnusvalinta == 2) {
					$query = "	SELECT tunnus
								FROM kuka
								WHERE yhtio = '$kukarow[yhtio]'
								and extranet != ''
								and kuka = '$rivi[$j]'";
					$apures = pupe_query($query);

					if (mysql_num_rows($apures) == 1) {
						$apurivi = mysql_fetch_assoc($apures);

						$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = $apurivi["tunnus"];

						$valinta .= " and ".$taulunotsikot[$taulu][$j]."='$apurivi[tunnus]'";
					}
					else {
						// Ei löydy, triggeröidään virhe
						$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = "XXX";
						$valinta .= " and ".$taulunotsikot[$taulu][$j]."='XXX'";
					}
				}
				elseif ($table_mysql == 'sanakirja' and $taulunotsikot[$taulu][$j] == "FI") {
					// jos ollaan mulkkaamassa RU ni tehdään utf-8 -> latin-1 konversio FI kentällä
					if (in_array("RU", $taulunotsikot[$taulu])) {
						$rivi[$j] = iconv("UTF-8", "ISO-8859-1", $rivi[$j]);
					}

					$valinta .= " and ".$taulunotsikot[$taulu][$j]."= BINARY '".trim($rivi[$j])."'";
				}
				elseif ($table_mysql == 'tuotepaikat' and $taulunotsikot[$taulu][$j] == "OLETUS") {
					//ei haluta tätä tänne
				}
				elseif ($table_mysql == 'yhteensopivuus_tuote_lisatiedot' and $taulunotsikot[$taulu][$j] == "YHTEENSOPIVUUS_TUOTE_TUNNUS" and $taulunrivit[$taulu][$eriviindex][$j] == "") {
					// Hetaan liitostunnus yhteensopivuus_tuote-taulusta
					$apusql = "	SELECT tunnus
								FROM yhteensopivuus_tuote
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi  = '{$taulunrivit["yhteensopivuus_tuote"][$eriviindex][array_search("TYYPPI", $taulunotsikot["yhteensopivuus_tuote"])]}'
								and atunnus = '{$taulunrivit["yhteensopivuus_tuote"][$eriviindex][array_search("ATUNNUS", $taulunotsikot["yhteensopivuus_tuote"])]}'
								and tuoteno = '{$taulunrivit["yhteensopivuus_tuote"][$eriviindex][array_search("TUOTENO", $taulunotsikot["yhteensopivuus_tuote"])]}'";
					$apures = pupe_query($apusql);

					if (mysql_num_rows($apures) == 1) {
						$apurivi = mysql_fetch_assoc($apures);

						$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = $apurivi["tunnus"];

						$valinta .= " and ".$taulunotsikot[$taulu][$j]."='$apurivi[tunnus]'";
					}
				}
				elseif ($table_mysql == 'puun_alkio') {

					// voidaan vaan lisätä puun alkioita
					if ($rivi[$postoiminto] != "LISAA" and $rivi[$postoiminto] != "POISTA") {
						$tila = 'ohita';
					}

					if ($tila != 'ohita' and $taulunotsikot[$taulu][$j] == "PUUN_TUNNUS") {

						// jos ollaan valittu koodi puun_tunnuksen sarakkeeksi, niin haetaan dynaamisesta puusta tunnus koodilla
						if ($dynaamisen_taulun_liitos == 'koodi') {

							$query_x = "	SELECT tunnus
											FROM dynaaminen_puu
											WHERE yhtio = '{$kukarow['yhtio']}'
											AND laji = '{$table_tarkenne}'
											AND koodi = '".trim($rivi[$j])."'";
							$koodi_tunnus_res = pupe_query($query_x);

							// jos tunnusta ei löydy, ohitetaan kyseinen rivi
							if (mysql_num_rows($koodi_tunnus_res) == 0) {
								$tila = 'ohita';
							}
							else {
								$koodi_tunnus_row = mysql_fetch_assoc($koodi_tunnus_res);
								$valinta .= " and puun_tunnus = '{$koodi_tunnus_row['tunnus']}' ";
							}
						}
						else {
							$valinta .= " and puun_tunnus = '".trim($rivi[$j])."' ";
						}
					}
					elseif ($tila != 'ohita' and $taulunotsikot[$taulu][$j] == "LIITOS") {
						if ($table_tarkenne == 'asiakas' and $dynaamisen_taulun_liitos != '') {

							$query = "	SELECT tunnus
										FROM asiakas
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND laji != 'P'
										AND $dynaamisen_taulun_liitos = '".trim($rivi[$j])."'";
							$asiakkaan_haku_res = pupe_query($query);

							unset($rivit[$eriviindex]);

							while ($asiakkaan_haku_row = mysql_fetch_assoc($asiakkaan_haku_res)) {

								$rivi_array_x = array();

								foreach ($taulunotsikot[$taulu] as $indexi_x => $columnin_nimi_x) {
									switch ($columnin_nimi_x) {
										case 'LIITOS':
											$rivi_array_x[] = $asiakkaan_haku_row['tunnus'];
											break;
										default:
											$rivi_array_x[] = $rivi[$indexi_x];
									}
								}

								array_push($dynaamiset_rivit, $rivi_array_x);
							}

							$puun_alkio_index_plus++;

							if ($rivimaara == ($eriviindex+1)) {
								$dynaamisen_taulun_liitos = '';

								foreach ($dynaamiset_rivit as $dyn_rivi) array_push($rivit, $dyn_rivi);
							}

							continue 2;
						}
						else {
							$valinta .= " and liitos = '".trim($rivi[$j])."' ";
						}
					}
				}
				elseif ($table_mysql == 'asiakas' and stripos($rivi[$postoiminto], 'LISAA') !== FALSE and $taulunotsikot[$taulu][$j] == "YTUNNUS" and $rivi[$j] == "AUTOM") {

					if ($yhtiorow["asiakasnumeroinnin_aloituskohta"] != "") {
						$apu_asiakasnumero = $yhtiorow["asiakasnumeroinnin_aloituskohta"];
					}
					else {
						$apu_asiakasnumero = 0;
					}

					//jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksilöivät tiedot on oltava konsernitasolla-yksilölliset
					if ($tarkyhtio == "") {
						$query = "	SELECT *
									FROM yhtio
									JOIN yhtion_parametrit ON yhtion_parametrit.yhtio = yhtio.yhtio
									where konserni = '$yhtiorow[konserni]'
									and (synkronoi = 'asiakas' or synkronoi like 'asiakas,%' or synkronoi like '%,asiakas,%' or synkronoi like '%,asiakas')";
						$vresult = pupe_query($query);

						if (mysql_num_rows($vresult) > 0) {
							// haetaan konsernifirmat
							$query = "	SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
										FROM yhtio
										JOIN yhtion_parametrit ON yhtion_parametrit.yhtio = yhtio.yhtio
										where konserni = '$yhtiorow[konserni]'
										and (synkronoi = 'asiakas' or synkronoi like 'asiakas,%' or synkronoi like '%,asiakas,%' or synkronoi like '%,asiakas')";
							$vresult = pupe_query($query);
							$srowapu = mysql_fetch_array($vresult);
							$tarkyhtio = $srowapu["yhtiot"];
						}
						else {
							$tarkyhtio = "'$kukarow[yhtio]'";
						}
					}

					$query = "	SELECT MAX(asiakasnro+0) asiakasnro
								FROM asiakas USE INDEX (asno_index)
								WHERE yhtio in ($tarkyhtio)
								AND asiakasnro+0 >= $apu_asiakasnumero";
					$vresult = pupe_query($query);
					$vrow = mysql_fetch_assoc($vresult);

					if ($vrow['asiakasnro'] != '') {
						$apu_ytunnus = $vrow['asiakasnro'] + $tarkylisa;
						$tarkylisa++;
					}
					else {
						$apu_ytunnus = $tarkylisa;
						$tarkylisa++;
					}

					// Päivitetään generoitu arvo kaikkiin muuttujiin...
					$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = $apu_ytunnus;

					foreach ($taulunotsikot as $autotaulu => $autojoinit) {
						if (in_array("YTUNNUS", $joinit) and $autotaulu != $taulut[$j] and $taulu == $joinattavat[$autotaulu]["YTUNNUS"]) {
							$taulunrivit[$autotaulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$autotaulu])] = $apu_ytunnus;
						}
					}

					$valinta .= " and ".$taulunotsikot[$taulu][$j]."='$apu_ytunnus'";
				}
				elseif ($table_mysql == 'auto_vari_korvaavat') {

					if ($taulunotsikot[$taulu][$j] == "AVK_TUNNUS") {
						$valinta = " yhtio = '$kukarow[yhtio]' and tunnus = '".trim(pupesoft_cleanstring($rivi[$j]))."'";

						$apu_sarakkeet = array("AVK_TUNNUS");
						$avkmuuttuja = TRUE;
					}
					elseif (!$avkmuuttuja) {
						$valinta .= " and ".$taulunotsikot[$taulu][$j]."='".trim(pupesoft_cleanstring($rivi[$j]))."'";
					}
				}
				else {
					$valinta .= " and ".$taulunotsikot[$taulu][$j]."='".trim(pupesoft_cleanstring($rivi[$j]))."'";
				}

				// jos pakollinen tieto puuttuu kokonaan
				if (trim($rivi[$j]) == "" and in_array($taulunotsikot[$taulu][$j], $pakolliset)) {
					$tila = 'ohita';
				}
			}

			if (substr($taulu, 0, 11) == 'puun_alkio_') {
				$valinta .= " and laji = '".substr($taulu, 11)."' ";
			}

			// jos ei ole puuttuva tieto etsitään riviä
			if ($tila != 'ohita') {

				if (in_array($table_mysql, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri")) and (!in_array("LIITOSTUNNUS", $taulunotsikot[$taulu]) or (in_array("LIITOSTUNNUS", $taulunotsikot[$taulu]) and $rivi[array_search("LIITOSTUNNUS", $taulunotsikot[$taulu])] == ""))) {

					if ((in_array("YTUNNUS", $taulunotsikot[$taulu]) and ($table_mysql == "yhteyshenkilo" or $table_mysql == "asiakkaan_avainsanat")) or (in_array("ASIAKAS", $taulunotsikot[$taulu]) and $table_mysql == "kalenteri")) {

						if ($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
							$tpque = "	SELECT tunnus
										from toimi
										where yhtio	= '$kukarow[yhtio]'
										and ytunnus	= '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."'
										and tyyppi != 'P'";
							$tpres = pupe_query($tpque);
						}
						elseif (($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat") {
							$tpque = "	SELECT tunnus
										from asiakas
										where yhtio	= '$kukarow[yhtio]'
										and ytunnus	= '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."'";
							$tpres = pupe_query($tpque);
						}
						elseif ($table_mysql == "kalenteri") {
							$tpque = "	SELECT tunnus
										from asiakas
										where yhtio	= '$kukarow[yhtio]'
										and ytunnus	= '".$rivi[array_search("ASIAKAS", $taulunotsikot[$taulu])]."'";
							$tpres = pupe_query($tpque);
						}

						if (mysql_num_rows($tpres) == 0) {
							if ($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
								echo t("Virhe rivillä").": $rivilaskuri ".t("Toimittajaa")." '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("ei löydy")."!<br>";
							}
							elseif (($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat") {
								echo t("Virhe rivillä").": $rivilaskuri ".t("Asiakasta")." '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("ei löydy")."!<br>";
							}
							else {
								echo t("Virhe rivillä").": $rivilaskuri ".t("Asiakasta")." '".$rivi[array_search("ASIAKAS", $taulunotsikot[$taulu])]."' ".t("ei löydy")."!<br>";
							}

							$hylkaa++; // ei päivitetä tätä riviä
						}
						elseif (mysql_num_rows($tpres) == 1) {
							$tpttrow = mysql_fetch_array($tpres);

							//	Liitetään pakolliset arvot
							if (!in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {
								$taulunotsikot[$taulu][] = "LIITOSTUNNUS";
							}

							$rivi[] = $tpttrow["tunnus"];

							$valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
						}
						else {

							if ($ytunnustarkkuus == 2) {
								$lasind = count($rivi);

								//	Liitetään pakolliset arvot
								if (!in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {
									$taulunotsikot[$taulu][] = "LIITOSTUNNUS";
								}

								$pushlask = 1;

								while ($tpttrow = mysql_fetch_array($tpres)) {

									$rivi[$lasind] = $tpttrow["tunnus"];

									if ($pushlask < mysql_num_rows($tpres)) {
										$rivit[] = $rivi;
									}

									$pushlask++;
								}

								$valinta .= " and liitostunnus='$rivi[$lasind]' ";
							}
							else {
								if ($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
									echo t("Virhe rivillä").": $rivilaskuri ".t("Toimittaja")." '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("Samalla ytunnuksella löytyy useita toimittajia! Lisää toimittajan tunnus LIITOSTUNNUS-sarakkeeseen")."!<br>";
								}
								elseif (($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat") {
									echo t("Virhe rivillä").": $rivilaskuri ".t("Asiakas")." '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("Samalla ytunnuksella löytyy useita asiakkaita! Lisää asiakkaan tunnus LIITOSTUNNUS-sarakkeeseen")."!<br>";
								}
								else {
									echo t("Virhe rivillä").": $rivilaskuri ".t("Asiakas")." '".$rivi[array_search("ASIAKAS", $taulunotsikot[$taulu])]."' ".t("Samalla ytunnuksella löytyy useita asiakkaita! Lisää asiakkaan tunnus LIITOSTUNNUS-sarakkeeseen")."!<br>";
								}

								$hylkaa++; // ei päivitetä tätä riviä
							}
						}
					}
					else {
						echo t("Virhe rivillä").": $rivilaskuri ".t("Riviä ei voi lisätä jos ei tiedetä ainakin YTUNNUSTA!")."<br>";
						$hylkaa++;
					}
				}
				elseif (in_array($table_mysql, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri")) and in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {

					if ($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
						$tpque = "	SELECT tunnus
									from toimi
									where yhtio	= '$kukarow[yhtio]'
									and tunnus	= '".$rivi[array_search("LIITOSTUNNUS", $taulunotsikot[$taulu])]."'
									and tyyppi != 'P'";
						$tpres = pupe_query($tpque);
					}
					elseif (($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat" or $table_mysql == "kalenteri") {
						$tpque = "	SELECT tunnus
									from asiakas
									where yhtio	= '$kukarow[yhtio]'
									and tunnus	= '".$rivi[array_search("LIITOSTUNNUS", $taulunotsikot[$taulu])]."'";
						$tpres = pupe_query($tpque);
					}

					if (mysql_num_rows($tpres) != 1) {
						echo t("Virhe rivillä").": $rivilaskuri ".t("Toimittajaa/Asiakasta")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."!<br>";
						$hylkaa++; // ei päivitetä tätä riviä
					}
					else {
						$tpttrow = mysql_fetch_array($tpres);

						// Lisätään ehtoon
						$valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
					}
				}

				$query = "	SELECT tunnus
							FROM $table_mysql
							WHERE $valinta";
				$fresult = pupe_query($query);

				if ($rivi[$postoiminto] == "MUUTA/LISAA") {
					// Muutetaan jos löytyy muuten lisätään!
					if (mysql_num_rows($fresult) == 0) {
						$rivi[$postoiminto] = "LISAA";
					}
					else {
						$rivi[$postoiminto] = "MUUTA";
					}
				}
				elseif ($rivi[$postoiminto] == 'LISAA' and $table_mysql != $table and mysql_num_rows($fresult) != 0) {
					// joinattaviin tauluhin tehdään muuta-operaatio jos rivi löytyy
					$rivi[$postoiminto] = "MUUTA";
				}
				elseif ($rivi[$postoiminto] == 'MUUTA' and $table_mysql != $table and mysql_num_rows($fresult) == 0) {
					// joinattaviin tauluhin tehdään lisaa-operaatio jos riviä ei löydy
					$rivi[$postoiminto] = "LISAA";
				}
				elseif ($rivi[$postoiminto] == 'LISAA' and mysql_num_rows($fresult) != 0) {
					if ($table_mysql != 'asiakasalennus' and $table_mysql != 'asiakashinta') {
						echo t("Virhe rivillä").": $rivilaskuri <font class='error'>".t("VIRHE:")." ".t("Rivi on jo olemassa, ei voida perustaa uutta!")."</font> $valinta<br>";
						$tila = 'ohita';
					}
				}
				elseif ($rivi[$postoiminto] == 'MUUTA' and mysql_num_rows($fresult) == 0) {
					if ($table_mysql != 'asiakasalennus' and $table_mysql != 'asiakashinta') {
						echo t("Virhe rivillä").": $rivilaskuri <font class='error'>".t("Riviä ei voida muuttaa, koska sitä ei löytynyt!")."</font> $valinta<br>";
						$tila = 'ohita';
					}
				}
				elseif ($rivi[$postoiminto] == 'POISTA') {

					// Sallitut taulut
					if (!$saakopoistaa) {
						echo t("Virhe rivillä").": $rivilaskuri <font class='error'>".t("Rivin poisto ei sallittu!")."</font> $valinta<br>";
						$tila = 'ohita';
					}
					elseif (mysql_num_rows($fresult) == 0) {
						echo t("Virhe rivillä").": $rivilaskuri <font class='error'>".t("Riviä ei voida poistaa, koska sitä ei löytynyt!")."</font> $valinta<br>";
						$tila = 'ohita';
					}
				}
				elseif ($rivi[$postoiminto] != 'MUUTA' and $rivi[$postoiminto] != 'LISAA') {
					echo t("Virhe rivillä").": $rivilaskuri <font class='error'>".t("Riviä ei voida käsitellä koska siltä puuttuu toiminto!")."</font> $valinta<br>";
					$tila = 'ohita';
				}
			}
			else {
				echo t("Virhe rivillä").": $rivilaskuri <font class='error'>".t("Pakollista tietoa puuttuu/tiedot ovat virheelliset!")."</font> $valinta<br>";
			}

			// lisätään rivi
			if ($tila != 'ohita') {
				if ($rivi[$postoiminto] == 'LISAA') {
					if ($eiyhtiota == "") {
						$query = "INSERT into $table_mysql SET yhtio='$kukarow[yhtio]', laatija='$kukarow[kuka]', luontiaika=now(), muuttaja='$kukarow[kuka]', muutospvm=now() ";
					}
					elseif ($eiyhtiota == "EILAATIJAA") {
						$query = "INSERT INTO {$table_mysql} SET yhtio = '{$kukarow['yhtio']}' ";
					}
					elseif ($eiyhtiota == "TRIP") {
						$query = "INSERT into $table_mysql SET laatija='$kukarow[kuka]', luontiaika=now() ";
					}
				}

				if ($rivi[$postoiminto] == 'MUUTA') {
					if ($eiyhtiota == "") {
						$query = "UPDATE $table_mysql SET yhtio='$kukarow[yhtio]', muuttaja='$kukarow[kuka]', muutospvm=now() ";
	      			}
					elseif ($eiyhtiota == "EILAATIJAA") {
						$query = "UPDATE {$table_mysql} SET yhtio = '{$kukarow['yhtio']}' ";
					}
					elseif ($eiyhtiota == "TRIP") {
						$query = "UPDATE $table_mysql SET muuttaja='$kukarow[kuka]', muutospvm=now() ";
	      			}
				}

				if ($rivi[$postoiminto] == 'POISTA') {
					$query = "DELETE FROM $table_mysql ";
				}

				foreach ($taulunotsikot[$taulu] as $r => $otsikko) {

					//	Näitä ei koskaan lisätä
					if (is_array($apu_sarakkeet) and in_array($otsikko, $apu_sarakkeet)) {
						continue;
					}

					if ($r != $postoiminto) {

						// Avainsanojen perheet kuntoon!
						if ($table_mysql == 'avainsana' and $rivi[$postoiminto] == 'LISAA' and $rivi[array_search("PERHE", $taulunotsikot[$taulut[$r]])] == "AUTOM") {

							$mpquery = "SELECT max(perhe)+1 max
										FROM avainsana";
							$vresult = pupe_query($mpquery);
							$vrow = mysql_fetch_assoc($vresult);

							$apu_ytunnus = $vrow['max'] + $tarkylisa;
							$tarkylisa++;

							$j = array_search("PERHE", $taulunotsikot[$taulut[$r]]);

							// Päivitetään generoitu arvo kaikkiin muuttujiin...
							$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = $apu_ytunnus;

							foreach ($taulunotsikot as $autotaulu => $autojoinit) {
								if (in_array("PERHE", $joinit) and $autotaulu != $taulut[$r] and $taulu == $joinattavat[$autotaulu]["PERHE"]) {
									$taulunrivit[$autotaulu][$eriviindex][array_search("PERHE", $taulunotsikot[$autotaulu])] = $apu_ytunnus;
								}
							}
						}

						$rivi[$r] = trim(addslashes($rivi[$r]));

						if (substr($trows[$table_mysql.".".$otsikko],0,7) == "decimal" or substr($trows[$table_mysql.".".$otsikko],0,4) == "real") {
							//korvataan decimal kenttien pilkut pisteillä...
							$rivi[$r] = str_replace(",", ".", $rivi[$r]);
						}

						if ((int) $tlength[$table_mysql.".".$otsikko] > 0 and strlen($rivi[$r]) > $tlength[$table_mysql.".".$otsikko] and ($table_mysql != "tuotepaikat" and $otsikko != "OLETUS" and $rivi[$r] != 'XVAIHDA')) {
							echo t("Virhe rivillä").": $rivilaskuri <font class='error'>".t("VIRHE").": $otsikko ".t("kentässä on liian pitkä tieto")."!</font> $rivi[$r]: ".strlen($rivi[$r])." > ".$tlength[$table_mysql.".".$otsikko]."!<br>";
							$hylkaa++; // ei päivitetä tätä riviä
						}

						if ($table_mysql == 'tuotepaikat' and $otsikko == 'OLETUS') {
							// $tuoteno pitäs olla jo aktivoitu ylhäällä
							// haetaan tuotteen varastopaikkainfo
							$tpque = "	SELECT sum(if (oletus='X',1,0)) oletus, sum(if (oletus='X',0,1)) regular
										from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
							$tpres = pupe_query($tpque);

							if (mysql_num_rows($tpres) == 0) {
								$rivi[$r] = "X"; // jos yhtään varastopaikkaa ei löydy, pakotetaan oletus
								echo t("Virhe rivillä").": $rivilaskuri ".t("Tuotteella")." '$tuoteno' ".t("ei ole yhtään varastopaikkaa, pakotetaan tästä oletus").".<br>";
							}
							else {
								$tprow = mysql_fetch_array($tpres);
								if ($rivi[$r] == 'XVAIHDA' and $tprow['oletus'] > 0) {
									//vaihdetaan tämä oletukseksi
									echo t("Virhe rivillä").": $rivilaskuri ".t("Tuotteelle")." '$tuoteno' ".t("Vaihdetaan annettu paikka oletukseksi").".<br>";
								}
								elseif ($rivi[$r] != '' and $tprow['oletus'] > 0) {
									$rivi[$r] = ""; // tällä tuotteella on jo oletuspaikka, nollataan tämä
									echo t("Virhe rivillä").": $rivilaskuri ".t("Tuotteella")." '$tuoteno' ".t("on jo oletuspaikka, ei päivitetty oletuspaikkaa")."!<br>";
								}
								elseif ($rivi[$r] == '' and $tprow['oletus'] == 0) {
									$rivi[$r] = "X"; // jos yhtään varastopaikkaa ei löydy, pakotetaan oletus
									echo t("Virhe rivillä").": $rivilaskuri ".t("Tuotteella")." '$tuoteno' ".t("ei ole yhtään oletuspaikkaa! Tätä EI PITÄISI tapahtua! Tehdään nyt tästä oletus").".<br>";
								}
							}
						}

						if ($table_mysql == 'tuote' and ($otsikko == 'EPAKURANTTI25PVM' or $otsikko == 'EPAKURANTTI50PVM' or $otsikko == 'EPAKURANTTI75PVM' or $otsikko == 'EPAKURANTTI100PVM')) {

							// $tuoteno pitäs olla jo aktivoitu ylhäällä
							if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI25PVM') {
								$tee = "25paalle";
							}
							elseif ($tee == "") {
								$tee = "pois";
							}

							if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI50PVM') {
								$tee = "puolipaalle";
							}
							elseif ($tee == "") {
								$tee = "pois";
							}

							if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI75PVM') {
								$tee = "75paalle";
							}
							elseif ($tee == "") {
								$tee = "pois";
							}

							if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI100PVM') {
								$tee = "paalle";
							}
							elseif ($tee == "") {
								$tee = "pois";
							}

							// ei yritetä laittaa uusia tuotteita kurantiksi vaikka kentät olisikin excelissä
							if ($rivi[$postoiminto] == 'LISAA' and $tee == 'pois') {
								$tee = "";
							}

							$eilisataeikamuuteta = "joo";
						}

						if ($table_mysql == 'tuote' and ($otsikko == 'KUSTP' or $otsikko == 'KOHDE' or $otsikko == 'PROJEKTI') and $rivi[$r] != "") {
							// Kustannuspaikkarumba tännekin
							$ikustp_tsk = $rivi[$r];
							$ikustp_ok  = 0;

							if ($otsikko == "PROJEKTI") $kptyyppi = "P";
							if ($otsikko == "KOHDE")	$kptyyppi = "O";
							if ($otsikko == "KUSTP")	$kptyyppi = "K";

							if ($ikustp_tsk != "") {
								$ikustpq = "SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '$kukarow[yhtio]'
											and tyyppi = '$kptyyppi'
											and kaytossa != 'E'
											and nimi = '$ikustp_tsk'";
								$ikustpres = pupe_query($ikustpq);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp_ok = $ikustprow["tunnus"];
								}
							}

							if ($ikustp_tsk != "" and $ikustp_ok == 0) {
								$ikustpq = "SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '$kukarow[yhtio]'
											and tyyppi = '$kptyyppi'
											and kaytossa != 'E'
											and koodi = '$ikustp_tsk'";
								$ikustpres = pupe_query($ikustpq);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp_ok = $ikustprow["tunnus"];
								}
							}

							if (is_numeric($ikustp_tsk) and (int) $ikustp_tsk > 0 and $ikustp_ok == 0) {

								$ikustp_tsk = (int) $ikustp_tsk;

								$ikustpq = "SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '$kukarow[yhtio]'
											and tyyppi = '$kptyyppi'
											and kaytossa != 'E'
											and tunnus = '$ikustp_tsk'";
								$ikustpres = pupe_query($ikustpq);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp_ok = $ikustprow["tunnus"];
								}
							}

							if ($ikustp_ok > 0) {
								$rivi[$r] = $ikustp_ok;
								$rivit[$eriviindex][$r]	= $ikustp_ok;
							}
						}

						// tehdään riville oikeellisuustsekkejä
						if ($table_mysql == 'sanakirja' and $otsikko == 'FI') {
							// jos ollaan mulkkaamassa RU ni tehdään utf-8 -> latin-1 konversio FI kentällä
							 if (in_array("RU", $taulunotsikot[$taulu])) {
								$rivi[$r] = iconv("UTF-8", "ISO-8859-1", $rivi[$r]);
							}
						}

						// tehdään riville oikeellisuustsekkejä
						if ($table_mysql == 'tuotteen_toimittajat' and $otsikko == 'TOIMITTAJA' and !in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {

							$tpque = "	SELECT tunnus
										from toimi
										where yhtio	= '$kukarow[yhtio]'
										and ytunnus	= '$rivi[$r]'
										and tyyppi != 'P'";
							$tpres = pupe_query($tpque);

							if (mysql_num_rows($tpres) != 1) {
								$tpque = "	SELECT tunnus
											from toimi
											where yhtio	= '$kukarow[yhtio]'
											and toimittajanro = '$rivi[$r]'
											and toimittajanro != ''
											and tyyppi != 'P'";
								$tpres = pupe_query($tpque);
							}

							if (mysql_num_rows($tpres) != 1) {
								echo t("Virhe rivillä").": $rivilaskuri ".t("Toimittajaa")." '$rivi[$r]' ".t("ei löydy! Tai samalla ytunnuksella löytyy useita toimittajia! Lisää toimittajan tunnus LIITOSTUNNUS-sarakkeeseen. Riviä ei päivitetty/lisätty")."! ".t("TUOTENO")." = $tuoteno<br>";
								$hylkaa++; // ei päivitetä tätä riviä
							}
							else {
								$tpttrow = mysql_fetch_array($tpres);

								// Tarvitaan tarkista.inc failissa
								$toimi_liitostunnus = $tpttrow["tunnus"];

								if ($rivi[$postoiminto] != 'POISTA') {
									$query .= ", liitostunnus='$tpttrow[tunnus]' ";
								}

								$valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
							}
						}
						elseif ($table_mysql == 'tuotteen_toimittajat' and $otsikko == 'LIITOSTUNNUS') {
							$tpque = "	SELECT tunnus
										from toimi
										where yhtio	= '$kukarow[yhtio]'
										and tunnus	= '$rivi[$r]'
										and tyyppi != 'P'";
							$tpres = pupe_query($tpque);

							if (mysql_num_rows($tpres) != 1) {
								echo t("Virhe rivillä").": $rivilaskuri ".t("Toimittajaa")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."! ".t("TUOTENO")." = $tuoteno<br>";
								$hylkaa++; // ei päivitetä tätä riviä
							}
							else {
								$tpttrow = mysql_fetch_array($tpres);

								// Tarvitaan tarkista.inc failissa
								$toimi_liitostunnus = $tpttrow["tunnus"];
								$valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
							}
						}

						//tarkistetaan asiakasalennus ja asiakashinta juttuja
						if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta') {
							if ($otsikko == 'RYHMA' and $rivi[$r] != '') {
								$chryhma = $rivi[$r];
							}

							if ($otsikko == 'ASIAKAS' and (int) $rivi[$r] > 0) {
								$chasiakas = $rivi[$r];
							}

							if ($otsikko == 'TUOTENO' and $rivi[$r] != '') {
								$chtuoteno = $rivi[$r];
							}

							if ($otsikko == 'ASIAKAS_RYHMA' and $rivi[$r] != '') {
								$chasiakas_ryhma = $rivi[$r];
							}

							if ($otsikko == 'YTUNNUS' and $rivi[$r] != '') {
								$chytunnus = $rivi[$r];
							}

							if ($otsikko == 'ALKUPVM' and $rivi[$r] != '') {
								$chalkupvm = $rivi[$r];
							}

							if ($otsikko == 'LOPPUPVM' and $rivi[$r] != '') {
								$chloppupvm = $rivi[$r];
							}

							if ($otsikko == 'ASIAKAS_SEGMENTTI' and $segmenttivalinta == '1' and (int) $rivi[$r] > 0) { // 1 tarkoittaa dynaamisen puun KOODIA
								$etsitunnus = " SELECT tunnus FROM dynaaminen_puu WHERE yhtio='$kukarow[yhtio]' AND laji='asiakas' AND koodi='$rivi[$r]'";
								$etsiresult = pupe_query($etsitunnus);
								$etsirow = mysql_fetch_assoc($etsiresult);

								$chsegmentti = $etsirow['tunnus'];
							}

							if ($otsikko == 'ASIAKAS_SEGMENTTI' and $segmenttivalinta == '2' and (int) $rivi[$r] > 0) { // 2 tarkoittaa dynaamisen puun TUNNUSTA
								$chsegmentti = $rivi[$r];
							}

							if ($otsikko == 'PIIRI' and $rivi[$r] != '') {
								$chpiiri = $rivi[$r];
							}

							if ($otsikko == 'MINKPL' and (int) $rivi[$r] > 0) {
								$chminkpl = (int) $rivi[$r];
							}

							if ($otsikko == 'MAXKPL' and (int) $rivi[$r] > 0) {
								$chmaxkpl = (int) $rivi[$r];
							}

							if ($otsikko == 'ALENNUSLAJI' and (int) $rivi[$r] > 0) {
								$chalennuslaji = (int) $rivi[$r];
							}

							if ($otsikko == 'MONIKERTA' and $rivi[$r] != '') {
								$chmonikerta = trim($rivi[$r]);
							}
						}

						//tarkistetaan kuka juttuja
						if ($table_mysql == 'kuka') {
							if ($otsikko == 'SALASANA' and $rivi[$r] != '') {
								$rivi[$r] = md5(trim($rivi[$r]));
							}

							if ($otsikko == 'OLETUS_ASIAKAS' and $rivi[$r] != '') {
								$xquery = "	SELECT tunnus
											FROM asiakas
											WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$rivi[$r]'";
								$xresult = pupe_query($xquery);

								if (mysql_num_rows($xresult) == 0) {
									$xquery = "	SELECT tunnus
												FROM asiakas
												WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$rivi[$r]'";
									$xresult = pupe_query($xquery);
								}

								if (mysql_num_rows($xresult) == 0) {
									$xquery = "	SELECT tunnus
												FROM asiakas
												WHERE yhtio = '$kukarow[yhtio]' and asiakasnro = '$rivi[$r]'";
									$xresult = pupe_query($xquery);
								}

								if (mysql_num_rows($xresult) == 0) {
									echo t("Virhe rivillä").": $rivilaskuri ".t("Asiakasta")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."! $otsikko = $rivi[$r]<br>";
									$hylkaa++; // ei päivitetä tätä riviä
								}
								elseif (mysql_num_rows($xresult) > 1) {
									echo t("Virhe rivillä").": $rivilaskuri ".t("Asiakasta")." '$rivi[$r]' ".t("löytyi monia! Riviä ei päivitetty/lisätty")."! $otsikko = $rivi[$r]<br>";
									$hylkaa++; // ei päivitetä tätä riviä
								}
								else {
									$x2row = mysql_fetch_array($xresult);
									$rivi[$r] = $x2row['tunnus'];
								}
							}
						}

						//muutetaan riviä, silloin ei saa päivittää pakollisia kenttiä
						if ($rivi[$postoiminto] == 'MUUTA' and (!in_array($otsikko, $pakolliset) or $table_mysql == 'asiakashinta' or $table_mysql == 'asiakasalennus' or ($table_mysql == "tuotepaikat" and $otsikko == "OLETUS" and $rivi[$r] == 'XVAIHDA'))) {
							///* Tässä on kaikki oikeellisuuscheckit *///
							if ($table_mysql == 'asiakashinta' and $otsikko == 'HINTA') {
								if ($rivi[$r] != 0 and $rivi[$r] != '') {
									$query .= ", $otsikko = '$rivi[$r]' ";
								}
								elseif ($rivi[$r] == 0) {
									echo t("Virhe rivillä").": $rivilaskuri ".t("Hintaa ei saa nollata!")."<br>";
								}
							}
							elseif ($table_mysql == 'avainsana' and $otsikko == 'SELITE') {
								if ($rivi[$r] != 0 and $rivi[$r] != '') {
									$query .= ", $otsikko = '$rivi[$r]' ";
								}
								elseif ($rivi[$r] == 0) {
									echo t("Virhe rivillä").": $rivilaskuri ".t("Selite ei saa olla tyhjä!")."<br>";
								}
							}
							elseif ($table_mysql=='tuotepaikat' and $otsikko == 'OLETUS' and $rivi[$r] == 'XVAIHDA') {
								//vaihdetaan tämä oletukseksi
								$rivi[$r] = "X"; // pakotetaan oletus

								$tpupque = "UPDATE tuotepaikat SET oletus = '' where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";

								$query .= ", $otsikko = '$rivi[$r]' ";
							}
							elseif ($table_mysql=='tuotepaikat' and $otsikko == 'OLETUS') {
								//echo t("Virhe rivillä").": $rivilaskuri Oletusta ei voi muuttaa!<br>";
							}
							else {
								if ($eilisataeikamuuteta == "") {
									$query .= ", $otsikko = '$rivi[$r]' ";
								}
					  		}
						}

						//lisätään rivi
						if ($rivi[$postoiminto] == 'LISAA') {
							if ($table_mysql == 'tuotepaikat' and $otsikko == 'OLETUS' and $rivi[$r] == 'XVAIHDA') {
								//vaihdetaan tämä oletukseksi
								$rivi[$r] = "X"; // pakotetaan oletus

								$tpupque = "UPDATE tuotepaikat SET oletus = '' where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";

								$query .= ", $otsikko = '$rivi[$r]' ";
							}
							elseif (substr($taulu, 0, 11) == 'puun_alkio_') {
								if ($otsikko == 'PUUN_TUNNUS') {
									if ($dynaamisen_taulun_liitos == 'koodi') {
										$query_x = "	SELECT tunnus
														FROM dynaaminen_puu
														WHERE yhtio = '{$kukarow['yhtio']}'
														AND laji = '{$table_tarkenne}'
														AND koodi = '".trim($rivi[$r])."'";
										$koodi_tunnus_res = pupe_query($query_x);
										$koodi_tunnus_row = mysql_fetch_assoc($koodi_tunnus_res);

										$query .= ", puun_tunnus = '{$koodi_tunnus_row['tunnus']}' ";
									}
									else {
										$query .= ", puun_tunnus = '{$rivi[$r]}' ";
									}
								}
								else {
									$query .= ", $otsikko = '".trim($rivi[$r])."' ";
								}
							}
							elseif ($eilisataeikamuuteta == "") {
								$query .= ", $otsikko = '$rivi[$r]' ";
							}
						}
					}
				}

				//tarkistetaan asiakasalennus ja asiakashinta keisseissä onko tällanen rivi jo olemassa
				if ($hylkaa == 0 and ($chasiakas != 0 or $chasiakas_ryhma != '' or $chytunnus != '' or $chpiiri != '' or $chsegmentti != 0) and ($chryhma != '' or $chtuoteno != '') and ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta')) {
					if ($chasiakas_ryhma != '') {
						$and .= " and asiakas_ryhma = '$chasiakas_ryhma'";
					}
					if ($chytunnus != '') {
						$and .= " and ytunnus = '$chytunnus'";
					}
					if ($chasiakas > 0) {
						$and .= " and asiakas = '$chasiakas'";
					}
					if ($chsegmentti > 0) {
						$and .= " and asiakas_segmentti = '$chsegmentti'";
					}
					if ($chpiiri != '') {
						$and .= " and piiri = '$chpiiri'";
					}

					if ($chryhma != '') {
						$and .= " and ryhma = '$chryhma'";
					}
					if ($chtuoteno != '') {
						$and .= " and tuoteno = '$chtuoteno'";
					}

					if ($chminkpl != 0) {
						$and .= " and minkpl = '$chminkpl'";
					}

					if ($table_mysql == 'asiakasalennus') {
						if ($chmaxkpl != 0) {
							$and .= " and maxkpl = '$chmaxkpl'";
						}
					}

					if ($table_mysql == 'asiakasalennus') {

						if ($chmonikerta != '') {
							$and .= " and monikerta != ''";
						}
						else {
							$and .= " and monikerta  = ''";
						}

						if ($chalennuslaji == 0) {
							$and .= " and alennuslaji = '1'";
						}
						elseif ($chalennuslaji != 0) {
							$and .= " and alennuslaji = '$chalennuslaji'";
						}
					}

					$and .= " and alkupvm = '$chalkupvm' and loppupvm = '$chloppupvm'";
				}

				if (substr($taulu, 0, 11) == 'puun_alkio_' and $rivi[$postoiminto] != 'POISTA') {
					$query .= " , laji = '{$table_tarkenne}' ";
				}

				if ($rivi[$postoiminto] == 'MUUTA') {
					if (($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta') and $and != "") {
						$query .= " WHERE yhtio = '$kukarow[yhtio]'";
						$query .= $and;
					}
					else {
						$query .= " WHERE ".$valinta;
					}

					$query .= " ORDER BY tunnus";
				}

				if ($rivi[$postoiminto] == 'POISTA') {
					if (($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta') and $and != "") {
						$query .= " WHERE yhtio = '$kukarow[yhtio]'";
						$query .= $and;
					}
					else {
						$query .= " WHERE ".$valinta;
					}

					$query .= " LIMIT 1 ";
				}

				//	Tarkastetaan tarkistarivi.incia vastaan..
				//	Generoidaan oikeat arrayt
				$errori 		= "";
				$t 				= array();
				$virhe 			= array();
				$poistolukko	= "LUEDATA";

				// Jos on uusi rivi niin kaikki lukot on auki
				if ($rivi[$postoiminto] == 'LISAA') {
					$poistolukko = "";
				}

				//	Otetaan talteen query..
				$lue_data_query = $query;

				$tarq = "	SELECT *
							FROM $table_mysql";
				if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta') {
					$tarq .= " WHERE yhtio = '$kukarow[yhtio]'";
					$tarq .= $and;
				}
				else {
					$tarq .= " WHERE ".$valinta;
				}
				$result = pupe_query($tarq);

				if ($rivi[$postoiminto] == 'MUUTA' and mysql_num_rows($result) != 1) {
					echo t("Virhe rivillä").": $rivilaskuri <font class='error'>".t("Päivitettävää riviä ei löytynyt")."!</font><br>";
				}
				elseif ($rivi[$postoiminto] == 'LISAA' and mysql_num_rows($result) != 0) {

					if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta') {
						echo t("Virhe rivillä").": $rivilaskuri <font class='error'>".t("Riviä ei lisätty, koska se löytyi jo järjestelmästä")."!</font><br>";
					}
				}
				else {
					$tarkrow = mysql_fetch_array($result);
					$tunnus = $tarkrow["tunnus"];

					// Teghdään pari injektiota tarkrow-arrayseen
					$tarkrow["luedata_from"] = "LUEDATA";
					$tarkrow["luedata_toiminto"] = $rivi[$postoiminto];

					// Tehdään oikeellisuustsekit
					for ($i=1; $i < mysql_num_fields($result); $i++) {

						// Tarkistetaan saako käyttäjä päivittää tätä kenttää
						$Lindexi = array_search(strtoupper(mysql_field_name($result, $i)), $taulunotsikot[$taulu]);

						if (strtoupper(mysql_field_name($result, $i)) == 'TUNNUS') {
							$tassafailissa = TRUE;
						}
						elseif ($Lindexi !== FALSE and array_key_exists($Lindexi, $rivit[$eriviindex])) {
							$t[$i] = $rivit[$eriviindex][$Lindexi];

							// Tämä rivi on excelissä
							$tassafailissa = TRUE;
						}
						else {

							$t[$i] = isset($tarkrow[mysql_field_name($result, $i)]) ? $tarkrow[mysql_field_name($result, $i)] : "";

							// Tämä rivi ei oo excelissä
							$tassafailissa = FALSE;
						}

						$funktio = $table_mysql."tarkista";

						if (!function_exists($funktio)) {
							@include("inc/$funktio.inc");
						}

						unset($virhe);

						if (function_exists($funktio)) {
							$funktio($t, $i, $result, $tunnus, &$virhe, $tarkrow);
						}

						if ($tassafailissa) {

							$tarkista_sarake = mysql_field_name($result, $i);

							// Oletusaliakset
							$query = "	SELECT *
										FROM avainsana
										WHERE yhtio = '$kukarow[yhtio]'
										and laji = 'MYSQLALIAS'
										and selite = '$table_mysql.$tarkista_sarake'
										and selitetark_2 = ''";
							$al_res = pupe_query($query);
							$pakollisuuden_tarkistus_rivi = mysql_fetch_assoc($al_res);

							if (mysql_num_rows($al_res) != 0 and strtoupper($pakollisuuden_tarkistus_rivi['selitetark_3']) == "PAKOLLINEN") {
								if (((mysql_field_type($result, $i) == 'real' or  mysql_field_type($result, $i) == 'int') and (float) str_replace(",", ".", $t[$i]) == 0) or
								     (mysql_field_type($result, $i) != 'real' and mysql_field_type($result, $i) != 'int' and trim($t[$i]) == "")) {
									$virhe[$i] .= t("Tieto on pakollinen")."!";
									$errori = 1;
								}
							}
						}

						// Ignoorataan virhe jos se ei koske tässä failissa olutta saraketta
						if ($tassafailissa and isset($virhe[$i]) and $virhe[$i] != "") {
							switch ($table_mysql) {
								case "tuote":
									$virheApu = t("Tuote")." ".$tarkrow["tuoteno"].": ";
									break;
								default:
									$virheApu = "";
							}
							echo t("Virhe rivillä").": $rivilaskuri <font class='error'>$virheApu".mysql_field_name($result, $i).": ".$virhe[$i]." (".$t[$i].")</font><br>";
							$errori = 1;
						}
					}

					if ($errori != "") {
						$hylkaa++;
					}

					//	Palautetaan vanha query..
					$query = $lue_data_query;

					if ($hylkaa == 0) {

						// Haetaan rivi niin kuin se oli ennen muutosta
						$syncquery = "	SELECT *
										FROM $table_mysql";

						if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta') {
							$syncquery .= " WHERE yhtio = '$kukarow[yhtio]'";
							$syncquery .= $and;
						}
						else {
							$syncquery .= " WHERE ".$valinta;
						}
						$syncres = pupe_query($syncquery);
						$syncrow = mysql_fetch_array($syncres);

						// tuotepaikkojen oletustyhjennysquery uskalletaan ajaa vasta tässä
						if ($tpupque != '') {
							$tpupres = pupe_query($tpupque);
						}

						$tpupque = "";

						// Itse lue_datan päivitysquery
						$iresult = pupe_query($query);

						// Synkronoidaan
						if ($rivi[$postoiminto] == 'LISAA') {
							$tunnus = mysql_insert_id();
						}
						else {
							$tunnus = $syncrow["tunnus"];
						}

						synkronoi($kukarow["yhtio"], $table_mysql, $tunnus, $syncrow, "");

						// tehdään epäkunrattijutut
						if ($tee == "paalle" or $tee == "25paalle" or $tee == "puolipaalle" or $tee == "75paalle" or $tee == "pois") {
							require("epakurantti.inc");
						}

						$lask++;
					}
				}
			}
		}

		if (!$cli) echo t("Päivitettiin")." $lask ".t("riviä")."!<br><br>";
		else echo "\nPäivitettiin $lask riviä\n";
	}
}

if (!$cli) {

	$indx = array(
		'asiakas',
		'asiakasalennus',
		'asiakashinta',
		'asiakaskommentti',
		'asiakkaan_avainsanat',
		'abc_parametrit',
		'autodata',
		'autodata_tuote',
		'avainsana',
		'budjetti',
		'etaisyydet',
		'hinnasto',
		'kalenteri',
		'kustannuspaikka',
		'liitetiedostot',
		'maksuehto',
		'pakkaus',
		'perusalennus',
		'rahtimaksut',
		'rahtisopimukset',
		'rekisteritiedot',
		'sanakirja',
		'sarjanumeron_lisatiedot',
		'taso',
		'tili',
		'todo',
		'toimi',
		'toimitustapa',
		'tullinimike',
		'tuote',
		'tuotepaikat',
		'tuoteperhe',
		'tuotteen_alv',
		'tuotteen_avainsanat',
		'tuotteen_orginaalit',
		'tuotteen_toimittajat',
		'vak',
		'yhteensopivuus_auto',
		'yhteensopivuus_auto_2',
		'yhteensopivuus_mp',
		'yhteensopivuus_rekisteri',
		'yhteensopivuus_tuote',
		'yhteensopivuus_tuote_lisatiedot',
		'yhteyshenkilo',
		'kuka',
		'extranet_kayttajan_lisatiedot',
		'auto_vari',
		'auto_vari_tuote',
		'auto_vari_korvaavat'
	);

	$dynaamiset_avainsanat_result = t_avainsana('DYNAAMINEN_PUU', '', " and selite != '' ");

	while ($dynaamiset_avainsanat_row = mysql_fetch_assoc($dynaamiset_avainsanat_result)) {
		$indx[] = 'puun_alkio_'.strtolower($dynaamiset_avainsanat_row['selite']);
	}

	$sel = array_fill_keys(array($table), " selected") + array_fill_keys($indx, '');

	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action=''>
			<input type='hidden' name='tee' value='file'>
			<table>
			<tr>
				<td>".t("Valitse tietokannan taulu").":</td>
				<td><select name='table' onchange='submit();'>
					<option value='asiakas' {$sel['asiakas']}>".t("Asiakas")."</option>
					<option value='asiakasalennus' {$sel['asiakasalennus']}>".t("Asiakasalennukset")."</option>
					<option value='asiakashinta' {$sel['asiakashinta']}>".t("Asiakashinnat")."</option>
					<option value='asiakaskommentti' {$sel['asiakaskommentti']}>".t("Asiakaskommentit")."</option>
					<option value='asiakkaan_avainsanat' {$sel['asiakkaan_avainsanat']}>".t("Asiakkaan avainsanat")."</option>
					<option value='abc_parametrit' {$sel['abc_parametrit']}>".t("ABC-parametrit")."</option>
					<option value='autodata' {$sel['autodata']}>".t("Autodatatiedot")."</option>
					<option value='autodata_tuote' {$sel['autodata_tuote']}>".t("Autodata tuotetiedot")."</option>
					<option value='avainsana' {$sel['avainsana']}>".t("Avainsanat")."</option>
					<option value='budjetti' {$sel['budjetti']}>".t("Budjetti")."</option>
					<option value='etaisyydet' {$sel['etaisyydet']}>".t("Etäisyydet varastosta")."</option>
					<option value='hinnasto' {$sel['hinnasto']}>".t("Hinnasto")."</option>
					<option value='kalenteri' {$sel['kalenteri']}>".t("Kalenteritietoja")."</option>
					<option value='kustannuspaikka' {$sel['kustannuspaikka']}>".t("Kustannuspaikat")."</option>
					<option value='liitetiedostot' {$sel['liitetiedostot']}>".t("Liitetiedostot")."</option>
					<option value='maksuehto' {$sel['maksuehto']}>".t("Maksuehto")."</option>
					<option value='pakkaus' {$sel['pakkaus']}>".t("Pakkaustiedot")."</option>
					<option value='perusalennus' {$sel['perusalennus']}>".t("Perusalennukset")."</option>
					<option value='rahtimaksut' {$sel['rahtimaksut']}>".t("Rahtimaksut")."</option>
					<option value='rahtisopimukset' {$sel['rahtisopimukset']}>".t("Rahtisopimukset")."</option>
					<option value='rekisteritiedot' {$sel['rekisteritiedot']}>".t("Rekisteritiedot")."</option>
					<option value='sanakirja' {$sel['sanakirja']}>".t("Sanakirja")."</option>
					<option value='sarjanumeron_lisatiedot' {$sel['sarjanumeron_lisatiedot']}>".t("Sarjanumeron lisätiedot")."</option>
					<option value='taso' {$sel['taso']}>".t("Tilikartan rakenne")."</option>
					<option value='tili' {$sel['tili']}>".t("Tilikartta")."</option>
					<option value='todo' {$sel['todo']}>".t("Todo-lista")."</option>
					<option value='toimi' {$sel['toimi']}>".t("Toimittaja")."</option>
					<option value='toimitustapa' {$sel['toimitustapa']}>".t("Toimitustapoja")."</option>
					<option value='tullinimike' {$sel['tullinimike']}>".t("Tullinimikeet")."</option>
					<option value='tuote' {$sel['tuote']}>".t("Tuote")."</option>
					<option value='tuotepaikat' {$sel['tuotepaikat']}>".t("Tuotepaikat")."</option>
					<option value='tuoteperhe' {$sel['tuoteperhe']}>".t("Tuoteperheet")."</option>
					<option value='tuotteen_alv' {$sel['tuotteen_alv']}>".t("Tuotteiden ulkomaan ALV")."</option>
					<option value='tuotteen_avainsanat' {$sel['tuotteen_avainsanat']}>".t("Tuotteen avainsanat")."</option>
					<option value='tuotteen_orginaalit' {$sel['tuotteen_orginaalit']}>".t("Tuotteiden originaalit")."</option>
					<option value='tuotteen_toimittajat' {$sel['tuotteen_toimittajat']}>".t("Tuotteen toimittajat")."</option>
					<option value='vak' {$sel['vak']}>".t("VAK-tietoja")."</option>
					<option value='yhteensopivuus_auto' {$sel['yhteensopivuus_auto']}>".t("Yhteensopivuus automallit")."</option>
					<option value='yhteensopivuus_auto_2' {$sel['yhteensopivuus_auto_2']}>".t("Yhteensopivuus automallit 2")."</option>
					<option value='yhteensopivuus_mp' {$sel['yhteensopivuus_mp']}>".t("Yhteensopivuus mp-mallit")."</option>
					<option value='yhteensopivuus_rekisteri' {$sel['yhteensopivuus_rekisteri']}>".t("Yhteensopivuus rekisterinumerot")."</option>
					<option value='yhteensopivuus_tuote' {$sel['yhteensopivuus_tuote']}>".t("Yhteensopivuus tuotteet")."</option>
					<option value='yhteensopivuus_tuote_lisatiedot' {$sel['yhteensopivuus_tuote_lisatiedot']}>".t("Yhteensopivuus tuotteet lisätiedot")."</option>
					<option value='yhteyshenkilo' {$sel['yhteyshenkilo']}>".t("Yhteyshenkilöt")."</option>
					<option value='kuka' {$sel['kuka']}>".t("Käyttäjätietoja")."</option>
					<option value='extranet_kayttajan_lisatiedot' {$sel['extranet_kayttajan_lisatiedot']}>".t("Extranet-käyttäjän lisätietoja")."</option>
					<option value='varaston_hyllypaikat' {$sel['varaston_hyllypaikat']}>".t("Varaston hyllypaikat")."</option>
					<option value='toimitustavan_lahdot' {$sel['toimitustavan_lahdot']}>".t("Toimitustavan lähdöt")."</option>";

	$dynaamiset_avainsanat_result = t_avainsana('DYNAAMINEN_PUU', '', " and selite != '' ");
	$dynaamiset_avainsanat = '';

	if ($kukarow['yhtio'] == 'mast') {
		echo "<option value='auto_vari' $sel[auto_vari]>".t("Autoväri-datat")."</option>";
		echo "<option value='auto_vari_tuote' $sel[auto_vari_tuote]>".t("Autoväri-värikirja")."</option>";
		echo "<option value='auto_vari_korvaavat' $sel[auto_vari_korvaavat]>".t("Autoväri-korvaavat")."</option>";
	}

	while ($dynaamiset_avainsanat_row = mysql_fetch_assoc($dynaamiset_avainsanat_result)) {
		if ($table == 'puun_alkio_'.strtolower($dynaamiset_avainsanat_row['selite'])) {
			$dynaamiset_avainsanat = 'puun_alkio_'.strtolower($dynaamiset_avainsanat_row['selite']);
		}

		echo "<option value='puun_alkio_".strtolower($dynaamiset_avainsanat_row['selite'])."' ",$sel['puun_alkio_'.strtolower($dynaamiset_avainsanat_row['selite'])],">Dynaaminen_",strtolower($dynaamiset_avainsanat_row['selite']),"</option>";
	}

	echo "	</select></td></tr>";

		if (in_array($table, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri"))) {
			echo "<tr><td>".t("Ytunnus-tarkkuus").":</td>
					<td><select name='ytunnustarkkuus'>
					<option value=''>".t("Päivitetään vain, jos Ytunnuksella löytyy yksi rivi")."</option>
					<option value='2'>".t("Päivitetään kaikki syötetyllä Ytunnuksella löytyvät asiakkaat")."</option>
					</select></td>
			</tr>";
		}

		if (trim($dynaamiset_avainsanat) != '' and $table == $dynaamiset_avainsanat) {
			echo "	<tr><td>",t("Valitse liitos"),":</td>
					<td><select name='dynaamisen_taulun_liitos'>";

			if ($table == 'puun_alkio_asiakas') {
				echo "	<option value=''>",t("Asiakkaan tunnus"),"</option>
						<option value='ytunnus'>",t("Asiakkaan ytunnus"),"</option>
						<option value='toim_ovttunnus'>",t("Asiakkaan toimitusosoitteen ovttunnus"),"</option>
						<option value='asiakasnro'>",t("Asiakkaan asiakasnumero"),"</option>";
			}
			else {
				echo "	<option value=''>",t("Puun alkion tunnus"),"</option>
						<option value='koodi'>",t("Puun alkion koodi"),"</option>";
			}

			echo "</select></td></tr>";
		}

		if (in_array($table, array("asiakasalennus", "asiakashinta"))) {
			echo "<tr><td>".t("Segmentin valinta").":</td>
					<td><select name='segmenttivalinta'>
					<option value='1'>".t("Valitaan käytettäväksi asiakas-segmentin koodia")."</option>
					<option value='2'>".t("Valitaan käytettäväksi asiakas-segmentin tunnusta ")."</option>
					</select></td>
			</tr>";
		}

	if ($table == "extranet_kayttajan_lisatiedot") {
		echo "<tr><td>".t("Liitostunnus").":</td>
				<td><select name='liitostunnusvalinta'>
				<option value='1'>".t("Liitostunnus-sarakkeessa liitostunnus")."</option>
				<option value='2'>".t("Liitostunnus-sarakkeessa käyttäjänimi")."</option>
				</select></td>
		</tr>";
	}

		echo "	<tr><td>".t("Valitse tiedosto").":</td>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("Lähetä")."'></td>
			</tr>

			</table>
			</form>";
}

require ("inc/footer.inc");

?>