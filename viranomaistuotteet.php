<?php 

require ("inc/parametrit.inc");

enable_ajax();

if ($livesearch_tee == "TILIHAKU") {
	livesearch_tilihaku();
	exit;
}

echo "<font class='head'>".t("Viranomaistuotteiden sisäänluku excelistä")."</font><hr>";

flush();

if ($tee == 'PERUSTA') {

	$yc = 0;
	$ic = 0;
	$uc = 0;
	
	for ($riviindex = 0; $riviindex < count($maa); $riviindex++) {
	
		$paivaraha 	= (float) $hinta[$riviindex];
		$tilino		= (int) $tilille[$riviindex];
		$tuotenimitys = "Ulkomaanpäiväraha ".$annettuvuosi." ".trim(preg_replace("/[^a-z\,\.\-\(\) åäöÅÄÖ]/i", "",$maannimi[$riviindex]));
	
		if (trim($maa[$riviindex]) == '' and $erikoisehto[$riviindex] == 'K') {
			$tuoteno 	= "PR-".trim(preg_replace("/[^a-z\,\.\-\(\) åäöÅÄÖ]/i", "",$maannimi[$riviindex]))."-".date('y',mktime(0,0,0,1,6,$annettuvuosi));
		}
		elseif (trim($maa[$riviindex]) != '' and $erikoisehto[$riviindex] == '') {
			$tuoteno 	= "PR-".$maa[$riviindex]."-".date('y',mktime(0,0,0,1,6,$annettuvuosi));
		}
		elseif (trim($maa[$riviindex]) != '' and $erikoisehto[$riviindex] == 'K') { //preg_replace("/[^a-z\,\.\-\(\) åäöÅÄÖ]/i", "", );
			$tuoteno 	= "PR-".$maa[$riviindex]."-".trim(preg_replace("/[^a-z\,\.\-\(\) åäöÅÄÖ]/i", "",$maannimi[$riviindex]))."-".date('y',mktime(0,0,0,1,6,$annettuvuosi));
		}
		else {
			$tuoteno 	= "PR-".trim(preg_replace("/[^a-z\,\.\-\(\) åäöÅÄÖ]/i", "",$maannimi[$riviindex]))."-".date('y',mktime(0,0,0,1,6,$annettuvuosi));
		}
		
		
		$sql_haku 	= "	SELECT tuoteno, tunnus FROM tuote WHERE yhtio = '$kukarow[yhtio]' AND tuoteno LIKE '%$tuoteno%' AND tuotetyyppi IN ('A','B')";
		$results 	= mysql_query($sql_haku) or pupe_error($sql_haku);
		
		if (mysql_num_rows($results) == 1) {
			$row = mysql_fetch_assoc($results);
			$sql_update 	= "	UPDATE tuote SET myyntihinta = '$paivaraha', tilino = '$tilino',muutospvm = now() , muuttaja = '$kukarow[kuka]'  WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '{$row[tunnus]}'";
			$resultupdate 	= mysql_query($sql_update) or pupe_error($sql_update);
			$uc++;
		}
		else {

			$sql_insert 	= "	INSERT INTO tuote (tuoteno, nimitys, myyntihinta, tilino, tuotetyyppi,vienti, yhtio,laatija,luontiaika) VALUES ('$tuoteno','$tuotenimitys' ,'$paivaraha', '$tilino' ,'A','$maa[$riviindex]','$kukarow[yhtio]','$kukarow[kuka]',now())";
			$resultinsert 	= mysql_query($sql_insert) or pupe_error($sql_insert);
			$ic++;
		}
		
		$countteri++;
	}
	
	echo "<br>Lisättiin $countteri ulkomaanpäiväraha-tietoutta tietokantaan, joista uusia $ic ja päivitettiin $uc";
	
}

if ($tee == 'POISTA') {
	
	$edellisetvuodet = $annettuvuosipoista-2000; // jos tahdotaan kuluvavuosi niin arvoksi 2000 
	
	$query = "	SELECT group_concat(tunnus) tunnus FROM tuote where yhtio = '$kukarow[yhtio]' and tuoteno like 'PR-%-$edellisetvuodet' and status !='P' and tuotetyyppi in('A','B')";
	$result =	mysql_query($query) or pupe_error($query);

	$rivi = mysql_fetch_assoc($result);
	
	if ($rivi[tunnus] != '') {
		$updatesql = "	UPDATE tuote set status = 'P' where yhtio = '$kukarow[yhtio]' and tunnus in ($rivi[tunnus])";
		$result =	mysql_query($updatesql) or pupe_error($updatesql);
		
		echo "<br>".t("Poistettiin käytöstä vuoden ennen"). $annettuvuosipoista .t(" olevat päivärahat käytöstä")."<br>";
		unset($tee);
	}
	else {
		echo t("Ei ollut yhtään edellisiltä vuosilta päivärahoja")."<br>";
		unset($tee);
	}
	
}

if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE and isset($annettuvuosi) and $annettuvuosi != 0 and isset($tilinumero) and trim($tilinumero) != '' and $tee == 'LUO') {

	//require ("inc/pakolliset_sarakkeet.inc");

	$path_parts = pathinfo($_FILES['userfile']['name']);
	$ext = strtoupper($path_parts['extension']);

	if ($ext != "TXT" and $ext != "XLS" and $ext != "CSV") {
		die ("<font class='error'><br>".t("Ainoastaan .txt, .csv tai .xls tiedostot sallittuja")."!</font>");
	}

	if ($_FILES['userfile']['size'] == 0) {
		die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
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

	echo "<font class='message'>".t("Tarkastetaan lähetetty tiedosto")."...<br><br></font>";
	echo "<form method='post' action='$PHP_SELF'>";
	
	// luetaan eka rivi tiedostosta..
	if ($ext == "XLS") {
		$headers = array();

		for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
			$headers[] = strtoupper(trim($data->sheets[0]['cells'][0][$excej]));
		}

		for ($excej = 0; $excej = (count($headers)-1); $excej--) {
			if ($headers[$excej] != "") {
				break;
			}
			else {
				unset($headers[$excej]);
			}
		}
	}
	else {
		die("<font class='error'><br>".t("VIRHE: Vain excel-tiedostot sallitaan")."!</font>") ;
	}

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

	}

	// Luetaan tiedosto loppuun ja tehdään taulukohtainen array koko datasta
	if ($ext == "XLS") {
		for ($excei = 1; $excei < $data->sheets[0]['numRows']; $excei++) {
			for ($excej = 0; $excej < count($headers); $excej++) {

				$taulunrivit[$taulut[$excej]][$excei-1][] = trim($data->sheets[0]['cells'][$excei][$excej]);

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
						$taulunrivit[$taulu][$excei-1][] = trim($data->sheets[0]['cells'][$excei][$excej]);
					}
				}
			}
		}
	}
	
	$query = "	SELECT tilino FROM tuote WHERE tuoteno LIKE 'PR%' LIMIT 1";
	$res = mysql_query($query);
	$tilirow = mysql_fetch_assoc($res);
	
	
	foreach ($taulunrivit as $taulu => $rivit) {

		echo "<table>";
		echo "<tr>";
		foreach ($taulunotsikot[$taulu] as $key => $column) {
			echo "<th>$column</th>";
		}
		echo "<th>Oletustilille</th>";
		
		echo "</tr>";
		for ($eriviindex = 0; $eriviindex < count($rivit); $eriviindex++) {
			echo "<tr>";
			foreach ($rivit[$eriviindex] as $pyll => $eriv ) {
			
				if ($pyll == 0) {
					$query = "	SELECT koodi from maat where nimi like '%$eriv%' limit 1";
					$res = mysql_query($query) or pupe_error($query);
					$row = mysql_fetch_assoc($res);
					$calc = mysql_num_rows($res);
					
					echo "<td>";

						$query2 = "	SELECT distinct koodi, nimi from maat having nimi !='' order by koodi,nimi ";
						$res2 = mysql_query($query2) or pupe_error($query2);
						
						echo "<select name='maa[$eriviindex]' >";
						echo "<option value = ''>EI LÖYTYNYT SUORAAN KANNASTA MAATUNNUSTA !!!</option>";
						
						while ($vrow = mysql_fetch_array($res2)) {
							$sel="";
							if (strtoupper($vrow['koodi']) == strtoupper($row['koodi'])) {
								$sel = "selected";
							}
							echo "<option value = '".$vrow[koodi]."' $sel>".$vrow[koodi]." ".$vrow[nimi]."</option>";
						}
						echo "</select>";
				
					
					
					$generoitu = "PR-".$row['koodi']."-".date('y',mktime(0,0,0,1,6,$annettuvuosi));
					$nimitys = "Ulkomaanpäiväraha ".$annettuvuosi." ".$eriv;
					
					echo "<input type='checkbox' name='erikoisehto[$eriviindex]' value='K'>Laitetaan maakoodi mukaan tuotenumeroon!";
					echo "<input type='hidden' name='maannimi[$eriviindex]' value='$eriv'>";
					echo " $nimitys</td>";
				}
				else {
					echo "<td><input type='hidden' name='hinta[$eriviindex]' value='$eriv' /> $eriv </td>";
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
	echo "<td class='back'><input type='submit' name='perusta' value='perusta ulkomaanpäivärahat vuodelle' />";
	echo "<input type='hidden' name='tee' value='PERUSTA' >";
	echo "<input type='hidden' name='annettuvuosi' value='$annettuvuosi' >";
	echo"</td></tr></table>";
	echo "<br><br><br><br><br><br><br><br>";
	echo "</form>";
}


if ($tee == 'LUO' and (trim($tilinumero) == '' or trim($annettuvuosi) == '')) {
	echo t("Virhe: Joko tiedosto puuttui, tilinumero puuttui tai vuosi puuttui");
	unset($tee);
}



if ($tee == '') {
	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>";
	echo "<table>";
	echo "<tr><th>".t("Valitse tiedosto").":</th>";
	echo "<td><input name='userfile' type='file'></td>";
	echo "<td class='back'><input type='submit' value='".t("Lähetä")."'></td>";

	echo "<tr><th>".t("Anna oletustilinumero johon viranomaistuotteet viedään")."</th>";
	echo "<td width='200' valign='top'>".livesearch_kentta("sendfile", "TILIHAKU", "tilinumero", 170, $tilinumero, "EISUBMIT")." $tilinimi\n";
	echo "<input type='hidden' name='tee' value='LUO'></td>";
	echo "</tr>";
	echo "<tr><th>Anna vuosi </th><td><input type='text' name='annettuvuosi' value='".date('Y')."' size='4'></td>";
	echo "</table>";
	echo "</form><br><br>";

	echo "<form method='post' action='$PHP_SELF'>";
	echo "<table>";
	echo "<tr><th>".t("Poista edelliset ennen vuotta")." ".date('Y')." ".t("ulkomaanpäivärahat käytöstä")."</th>";
	echo "<td><input type='submit' value='".t("Poista")."'></td>";
	echo "<input type='hidden' name='tee' value='POISTA'><input type='hidden' name='annettuvuosipoista' value='".date('Y')."' size='4'><tr>";
	echo "</table>";
	echo "</form>";

}
require ("inc/footer.inc");
?>