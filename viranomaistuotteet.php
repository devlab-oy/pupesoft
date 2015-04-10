<?php

require ("inc/parametrit.inc");

enable_ajax();

if ($livesearch_tee == "TILIHAKU") {
	livesearch_tilihaku();
	exit;
}

echo "<font class='head'>".t("Viranomaistuotteiden p�ivitys")."</font><hr>";

flush();

if ($tee == 'PERUSTA') {

	$yc = 0;
	$ic = 0;
	$uc = 0;

	for ($riviindex = 0; $riviindex < count($maa); $riviindex++) {

		$paivaraha 	= (float) $hinta[$riviindex];
		$tilino		= (int) $tilille[$riviindex];

		$tuotenimitys = "Ulkomaanp�iv�raha ".$annettuvuosi." ".trim(preg_replace("/[^a-z\,\.\-\(\) ������]/i", "", $maannimi[$riviindex]));

		if (trim($maa[$riviindex]) == '' and $erikoisehto[$riviindex] == 'K') {
			$tuoteno = "PR-".trim(preg_replace("/[^a-z\,\.\-\(\) ������]/i", "",$maannimi[$riviindex]))."-".date('y',mktime(0,0,0,1,6,$annettuvuosi));
		}
		elseif (trim($maa[$riviindex]) != '' and $erikoisehto[$riviindex] == '') {
			$tuoteno = "PR-".$maa[$riviindex]."-".date('y',mktime(0,0,0,1,6,$annettuvuosi));
		}
		elseif (trim($maa[$riviindex]) != '' and $erikoisehto[$riviindex] == 'K') {
			$tuoteno = "PR-".$maa[$riviindex]."-".trim(preg_replace("/[^a-z\,\.\-\(\) ������]/i", "",$maannimi[$riviindex]))."-".date('y',mktime(0,0,0,1,6,$annettuvuosi));
		}
		else {
			$tuoteno = "PR-".trim(preg_replace("/[^a-z\,\.\-\(\) ������]/i", "",$maannimi[$riviindex]))."-".date('y',mktime(0,0,0,1,6,$annettuvuosi));
		}

		$query  = "	INSERT INTO tuote SET
					tuoteno			= '$tuoteno',
					nimitys         = '$tuotenimitys',
					alv             = '0',
					kommentoitava   = '',
					kuvaus          = '50',
					myyntihinta     = '$paivaraha',
					tuotetyyppi     = 'A',
					status			= 'A',
					tilino 			= '$tilino',
					vienti          = '$maa[$riviindex]',
					yhtio			= '$kukarow[yhtio]',
					laatija			= '$kukarow[kuka]',
					luontiaika		= now()
					ON DUPLICATE KEY UPDATE
					nimitys         = '$tuotenimitys',
					alv             = '0',
					kommentoitava   = '',
					kuvaus          = '50',
					myyntihinta     = '$paivaraha',
					tuotetyyppi     = 'A',
					status			= 'A',
					tilino 			= '$tilino',
					vienti          = '$maa[$riviindex]',
					muuttaja		= '$kukarow[kuka]',
					muutospvm		= now()";
		$result = mysql_query($query) or pupe_error($query);
	}

	echo "<br>".t("Ukomaanp�iv�rahat lis�tty kantaan")."<br><br><br>";
	unset($tee);
}

if ($tee == 'POISTA') {
	$query = "	UPDATE tuote
				SET status = 'P'
				WHERE yhtio = '$kukarow[yhtio]'
				and tuotetyyppi = 'A'
				and (tuoteno like 'PR%' OR tuoteno like 'PPR%')
				and right(tuoteno, 2) > 0
				and right(tuoteno, 2) < $annettuvuosipoista";
	$result = mysql_query($query) or pupe_error($query);

	echo "<br>".t("Vanhat p�iv�rahat poistettu k�yt�st�")."<br><br><br>";
	unset($tee);
}

if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE and isset($annettuvuosi) and $annettuvuosi != 0 and isset($tilinumero) and trim($tilinumero) != '' and $tee == 'LUO') {

	$path_parts = pathinfo($_FILES['userfile']['name']);
	$ext = strtoupper($path_parts['extension']);

	if ($ext != "XLS") {
		die ("<font class='error'><br>".t("Ainoastaan .txt, .csv tai .xls tiedostot sallittuja")."!</font>");
	}

	if ($_FILES['userfile']['size'] == 0) {
		die ("<font class='error'><br>".t("Tiedosto on tyhj�")."!</font>");
	}

	require_once ('excel_reader/reader.php');

	// ExcelFile
	$data = new Spreadsheet_Excel_Reader();

	// Set output Encoding.
	$data->setOutputEncoding('CP1251');
	$data->setRowColOffset(0);
	$data->read($_FILES['userfile']['tmp_name']);


	echo "<font class='message'>".t("Tarkastetaan l�hetetty tiedosto")."...<br><br></font>";
	echo "<form method='post' action='$PHP_SELF'>";

	// luetaan eka rivi tiedostosta..
	$headers = array();

	for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
		$headers[] = strtoupper(trim($data->sheets[0]['cells'][0][$excej]));
	}

	// Poistetaan tyhj�t headerit oikealta
	for ($excej = 0; $excej = (count($headers)-1); $excej--) {
		if ($headers[$excej] != "") {
			break;
		}
		else {
			unset($headers[$excej]);
		}
	}

	// Luetaan tiedosto loppuun ja tehd��n taulukohtainen array koko datasta
	for ($excei = 1; $excei < $data->sheets[0]['numRows']; $excei++) {
		for ($excej = 0; $excej < count($headers); $excej++) {
			$taulunrivit[$taulut[$excej]][$excei-1][] = trim($data->sheets[0]['cells'][$excei][$excej]);
		}
	}

	foreach ($taulunrivit as $taulu => $rivit) {

		echo "<table>";
		echo "<tr>";
		foreach ($taulunotsikot[$taulu] as $key => $column) {
			echo "<th>$column</th>";
		}
		echo "<th colspan='5'>".t("Tuotteet")."</th>";
		echo "</tr>";

		for ($eriviindex = 0; $eriviindex < count($rivit); $eriviindex++) {
			echo "<tr>";
			foreach ($rivit[$eriviindex] as $pyll => $eriv) {
				if ($pyll == 0) {
					$query = "	SELECT koodi from maat where nimi like '%$eriv%' limit 1";
					$res = mysql_query($query) or pupe_error($query);
					$row = mysql_fetch_assoc($res);
					$calc = mysql_num_rows($res);

					echo "<td>";

					$query2 = "	SELECT distinct koodi, nimi from maat having nimi !='' order by koodi,nimi ";
					$res2 = mysql_query($query2) or pupe_error($query2);

					echo "<select name='maa[$eriviindex]' >";
					echo "<option value = ''>".t("VIRHE: Maatunnusta ei l�ytynyt")."!</option>";

					while ($vrow = mysql_fetch_assoc($res2)) {
						$sel="";
						if (strtoupper($vrow['koodi']) == strtoupper($row['koodi'])) {
							$sel = "selected";
						}
						echo "<option value = '$vrow[koodi]' $sel>$vrow[nimi]</option>";
					}

					echo "</select></td>";

					echo "<td><input type='checkbox' name='erikoisehto[$eriviindex]' value='K'> ".t("Lis�� maakoodi tuotenumeroon");
					echo "<input type='hidden' name='maannimi[$eriviindex]' value='$eriv'></td>";
					echo "<td>".t("Ulkomaanp�iv�raha")." $annettuvuosi $eriv</td>";
				}
				else {
					echo "<td><input type='hidden' name='hinta[$eriviindex]' value='$eriv' />$eriv</td>";
				}
			}

			echo "<td><input type='text' name='tilille[$eriviindex]' value='$tilinumero' />";
			echo "</td>";
			echo "</tr>";
		}
		echo "</table><br>";
	}

	echo "<table>";
	echo "<tr colspan='3'>";
	echo "<td class='back'><input type='submit' name='perusta' value='".t("Perusta ulkomaanp�iv�rahat")."' />";
	echo "<input type='hidden' name='tee' value='PERUSTA' >";
	echo "<input type='hidden' name='annettuvuosi' value='$annettuvuosi' >";
	echo"</td></tr></table>";
	echo "<br><br>";
	echo "</form>";
}

if ($tee == 'LUO' and (trim($tilinumero) == '' or trim($annettuvuosi) == '')) {
	echo t("Virhe: Joko tiedosto puuttui, tilinumero puuttui tai vuosi puuttui");
	unset($tee);
}

if ($tee == "synkronoi") {

	$ok = FALSE;

	if ($file = fopen("http://api.devlab.fi/referenssiviranomaistuotteet.sql","r")) {
		$ok = TRUE;
	}
	elseif ($file = fopen("http://10.0.1.2/referenssiviranomaistuotteet.sql","r")) {
		$ok = TRUE;
	}

	if (!$ok) {
		echo t("Tiedoston avaus ep�onnistui")."!";
		require ("inc/footer.inc");
		exit;
	}

	echo "<br><br>";

	// Eka rivi roskikseen
	$rivi = fgets($file);

	echo t("Lis�t��n uudet viranomaistuotteet tietokantaan")."...<br>";

	while ($rivi = fgets($file)) {
		list($tuoteno, $nimitys, $alv, $kommentoitava, $kuvaus, $myyntihinta, $tuotetyyppi, $vienti) = explode("\t", trim($rivi));

		if (strpos($nimitys, "Ulkomaanp�iv�raha") !== FALSE) {
			$tilino = $ulkomaantilinumero;
		}
		else {
			$tilino = $kotimaantilinumero;
		}

		$query  = "	INSERT INTO tuote SET
					tuoteno			= '$tuoteno',
					nimitys         = '$nimitys',
					alv             = '$alv',
					kommentoitava   = '$kommentoitava',
					kuvaus          = '$kuvaus',
					myyntihinta     = '$myyntihinta',
					tuotetyyppi     = '$tuotetyyppi',
					status			= 'A',
					tilino 			= '$tilino',
					vienti          = '$vienti',
					yhtio			= '$kukarow[yhtio]',
					laatija			= '$kukarow[kuka]',
					luontiaika		= now()
					ON DUPLICATE KEY UPDATE
					nimitys         = '$nimitys',
					alv             = '$alv',
					kommentoitava   = '$kommentoitava',
					kuvaus          = '$kuvaus',
					myyntihinta     = '$myyntihinta',
					tuotetyyppi     = '$tuotetyyppi',
					status			= 'A',
					tilino 			= '$tilino',
					vienti          = '$vienti',
					muuttaja		= '$kukarow[kuka]',
					muutospvm		= now()";
		$result = mysql_query($query) or pupe_error($query);
	}

	fclose($file);

	echo t("P�ivitys referenssist� valmis")."...<br><br><br>";
	unset($tee);
}

if ($tee == '') {
	echo "<br><form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>";

	echo t("Lue ulkomaanp�iv�rahat tiedostosta").":<br><br>";
	echo "<table>";
	echo "<tr><th>".t("Valitse tiedosto").":</th>";
	echo "<td><input name='userfile' type='file'></td>";
	echo "<td class='back'><input type='submit' value='".t("Jatka")."'></td>";

	echo "<tr><th>".t("Tili (Kirjanpito)")."</th>";
	echo "<td width='200' valign='top'>".livesearch_kentta("sendfile", "TILIHAKU", "tilinumero", 170, $tilinumero, "EISUBMIT")." $tilinimi\n";
	echo "<input type='hidden' name='tee' value='LUO'></td>";
	echo "</tr>";
	echo "<tr><th>".t("Anna vuosi")."</th><td><input type='text' name='annettuvuosi' value='".date('Y')."' size='4'></td>";
	echo "</table>";
	echo "</form><br><br>";

	echo t("Poista vanhat p�iv�rahat").":<br><br>";
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<table>";
	echo "<tr><th>".t("Poista edellisten vuosien p�iv�rahat k�yt�st�")."</th>";
	echo "<td><input type='submit' value='".t("Poista")."'></td>";
	echo "<input type='hidden' name='tee' value='POISTA'><input type='hidden' name='annettuvuosipoista' value='".date('y')."'><tr>";
	echo "</table>";
	echo "</form><br><br>";

	echo t("P�ivit� j�rjestelm�n p�iv�rahat").":<br><br>";
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<table>";
	echo "<tr><th>".t("Tili (Kirjanpito)")." ".t("Kotimaanp�iv�rahat")."</th><td width='200' valign='top'>".livesearch_kentta("sendfile", "TILIHAKU", "kotimaantilinumero", 170, $kotimaantilinumero, "EISUBMIT")."</td></tr>";
	echo "<tr><th>".t("Tili (Kirjanpito)")." ".t("Ulkomaanp�iv�rahat")."</th><td width='200' valign='top'>".livesearch_kentta("sendfile", "TILIHAKU", "ulkomaantilinumero", 170, $ulkomaantilinumero, "EISUBMIT")."</td></tr>";
	echo "<tr><th>".t("Nouda uusimmat p�iv�rahat")."</th>";
	echo "<td><input type='submit' value='".t("Nouda")."'></td>";
	echo "<input type='hidden' name='tee' value='synkronoi'><tr>";
	echo "</table>";
	echo "</form>";
}

require ("inc/footer.inc");

?>