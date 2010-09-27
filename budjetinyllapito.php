<?php

if (isset($_REQUEST["tee"])) {
	if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/","",$_REQUEST["kaunisnimi"]);
}

require ("inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}
else {

	echo "<font class='head'>",t("Budjetin ylläpito"),"</font><hr>";

	if (is_array($luvut)) {
		$paiv = 0;
		$lisaa = 0;

		foreach ($luvut as $rind => $rivi) {

			foreach ($rivi as $sind => $solu) {

				$solu = str_replace ( ",", ".", $solu);

				if ($solu == '!' or $solu = (float) $solu) {

					if ($solu == '!') $solu = 0;

					$solu = (float) $solu;

					$query = "	SELECT summa
								FROM budjetti
								WHERE yhtio = '$kukarow[yhtio]'
								AND kausi = '$sind'
								AND taso = '$rind'
								AND tili = ''
								AND kustp = '$vkustp'
								AND kohde = '$vkohde'
								AND projekti = '$vproj'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) == 1) {

						$budjrow = mysql_fetch_assoc($result);

						if ($budjrow['summa'] != $solu) {

							if ($solu == 0.00) {
								$query = "	DELETE FROM budjetti
											WHERE yhtio = '$kukarow[yhtio]'
											AND kausi = '$sind'
											AND taso = '$rind'
											AND tili = ''
											AND kustp = '$vkustp'
											AND kohde = '$vkohde'
											AND projekti = '$vproj'";
							}
							else {
								$query	= "	UPDATE budjetti SET
											summa = $solu
											WHERE yhtio = '$kukarow[yhtio]'
											AND kausi = '$sind'
											AND taso = '$rind'
											AND tili = ''
											AND kustp = '$vkustp'
											AND kohde = '$vkohde'
											AND projekti = '$vproj'";
							}
							$result = mysql_query($query) or pupe_error($query);
							$paiv++;
						}
					}
					else {
						$query = "	INSERT INTO budjetti SET
									summa = $solu,
									yhtio = '$kukarow[yhtio]',
									kausi = '$sind',
									taso = '$rind',
									tili = '',
									kustp = '$vkustp',
									kohde = '$vkohde',
									projekti = '$vproj'";
						$result = mysql_query($query) or pupe_error($query);
						$lisaa++;
					}
				}
			}

			foreach ($tilien_luvut[$rind] as $tind => $rivi) {
				foreach ($rivi as $sind => $solu) {

					$solu = str_replace ( ",", ".", $solu);

					if ($solu == '!' or $solu = (float) $solu) {

						if ($solu == '!') $solu = 0;

						$solu = (float) $solu;

						$query = "	SELECT summa
									FROM budjetti
									WHERE yhtio = '$kukarow[yhtio]'
									AND kausi = '$sind'
									AND taso = '$rind'
									AND tili = '$tind'
									AND kustp = '$vkustp'
									AND kohde = '$vkohde'
									AND projekti = '$vproj'";
						$result = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($result) == 1) {

							$budjrow = mysql_fetch_assoc($result);

							if ($budjrow['summa'] != $solu) {

								if ($solu == 0.00) {
									$query = "	DELETE FROM budjetti
												WHERE yhtio = '$kukarow[yhtio]'
												AND kausi = '$sind'
												AND taso = '$rind'
												AND tili = '$tind'
												AND kustp = '$vkustp'
												AND kohde = '$vkohde'
												AND projekti = '$vproj'";
								}
								else {
									$query	= "	UPDATE budjetti SET
												summa = $solu
												WHERE yhtio = '$kukarow[yhtio]'
												AND kausi = '$sind'
												AND taso = '$rind'
												AND tili = '$tind'
												AND kustp = '$vkustp'
												AND kohde = '$vkohde'
												AND projekti = '$vproj'";
								}
								$result = mysql_query($query) or pupe_error($query);
								$paiv++;
							}
						}
						else {
							$query = "	INSERT INTO budjetti SET
										summa = $solu,
										yhtio = '$kukarow[yhtio]',
										kausi = '$sind',
										taso = '$rind',
										tili = '$tind',
										kustp = '$vkustp',
										kohde = '$vkohde',
										projekti = '$vproj'";
							$result = mysql_query($query) or pupe_error($query);
							$lisaa++;
						}
					}
				}
			}
		}
		echo "<font class='message'>".t("Päivitin ").$paiv.t(" Lisäsin ").$lisaa."</font><br><br>";
	}

	if (isset($tyyppi)) {
		$sel1="";
		$sel2="";
		$sel3="";
		$sel4="";
		switch ($tyyppi) {
			case (1): $sel1 = 'selected';
			case (2): $sel2 = 'selected';
			case (3): $sel3 = 'selected';
			case (4): $sel4 = 'selected';
		}
	}

	echo "<table>";
	echo "<form method='post'>
			<table><tr>
			<th>".t("Tyyppi")."</th>
			<td><select name = 'tyyppi'>
			<option value='4' $sel4>".t("Tuloslaskelma")."
			<option value='2' $sel2>".t("Vastattavaa")."
			<option value='1' $sel1>".t("Vastaavaa")."
			</select></td></tr>
			<tr>";
	echo "<tr><th>".t("Tilikausi");

	$query = "	SELECT *
				FROM tilikaudet
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tilikausi_alku desc";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "</th><td><select name='tkausi'>";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($tkausi == $vrow['tunnus']) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"])."</option>";
	}

	echo "</select></th></tr>";
	echo "<tr><th>".t("Kustannuspaikka")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'K'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='kustp'><option value=' '>".t("Ei valintaa")."";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($kustp == $vrow['tunnus']) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[tunnus] $vrow[nimi]";
	}
	echo "</select></td>";
	echo "</tr>";
	echo "<tr><th>".t("Kohde")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'O'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='kohde'><option value=' '>Ei valintaa";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($kohde == $vrow['tunnus']) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
	}

	echo "</select></td>";
	echo "</tr>";
	echo "<tr><th>".t("Projekti")."</th>";

	$query = "	SELECT tunnus, nimi
				FROM kustannuspaikka
				WHERE yhtio = '$kukarow[yhtio]'
				and kaytossa != 'E'
				and tyyppi = 'P'
				ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='proj'><option value=' '>".t("Ei valintaa")."";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($proj == $vrow['tunnus']) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
	}

	echo "</select></td></tr></table>";
	echo t("Budjettiluvun voi poistaa huutomerkillä (!)")."<br><br>";
	echo "<input type='submit' VALUE='".t("Näytä/Tallenna")."'>";

	echo "</table>";

	if (isset($tkausi)) {
		$query = "	SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$tkausi'";
		$vresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($vresult) == 1)
			$tilikaudetrow=mysql_fetch_array($vresult);
	}

	if (is_array($tilikaudetrow)) {

		if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

			require ("inc/pakolliset_sarakkeet.inc");

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$ext = strtoupper($path_parts['extension']);

			if ($ext != "XLS") {
				die ("<font class='error'><br>".t("Ainoastaan .xls tiedostot sallittuja")."!</font>");
			}

			if ($_FILES['userfile']['size'] == 0) {
				die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
			}

			require_once ('excel_reader/reader.php');

			// ExcelFile
			$data = new Spreadsheet_Excel_Reader();

			// Set output Encoding.
			$data->setOutputEncoding('CP1251');
			$data->setRowColOffset(0);
			$data->read($_FILES['userfile']['tmp_name']);

			echo "<br /><br /><font class='message'>".t("Tarkastetaan lähetetty tiedosto")."...<br><br></font>";

			$headers = array();
			$taulunotsikot	= array();
			$taulunrivit	= array();

			for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
				$headers[] = strtoupper(trim($data->sheets[0]['cells'][0][$excej]));
			}

			for ($excej = 3; $excej = (count($headers)-1); $excej--) {
				if ($headers[$excej] != "") {
					break;
				}
				else {
					unset($headers[$excej]);
				}
			}

			for ($excei = 1; $excei < $data->sheets[0]['numRows']; $excei++) {
				for ($excej = 0; $excej < count($headers); $excej++) {

					$taulunrivit[$excei-1][] = trim($data->sheets[0]['cells'][$excei][$excej]);

					// Pitääkö tämä sarake laittaa myös johonki toiseen tauluun?
					foreach ($taulunotsikot as $taulu => $joinit) {
						$taulunrivit[$excei-1][] = trim($data->sheets[0]['cells'][$excei][$excej]);
					}
				}
			}
		}
		else {
			unset($taulunrivit);
		}
			
		if (!@include('Spreadsheet/Excel/Writer.php')) {
			echo "<font class='error'>",t("VIRHE: Pupe-asennuksesi ei tue Excel-kirjoitusta."),"</font><br>";
			exit;
		}

		//keksitään failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

		$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
		$workbook->setVersion(8);
		$worksheet =& $workbook->addWorksheet('Sheet 1');

		$format_bold =& $workbook->addFormat();
		$format_bold->setBold();

		$excelrivi 	 = 0;
		$excelsarake = 0;

		$worksheet->writeString($excelrivi, $excelsarake, t("Tili / Taso"), $format_bold);
		$excelsarake++;

		$worksheet->writeString($excelrivi, $excelsarake, t("Tilinumero / Tasonumero"), $format_bold);
		$excelsarake++;

		$worksheet->writeString($excelrivi, $excelsarake, t("Tilin / Tason nimi"), $format_bold);
		$excelsarake++;

		//Parametrit mihin tämä taulukko liittyy
		echo "<input type='hidden' name = 'vkustp' value='$kustp'>";
		echo "<input type='hidden' name = 'vkohde' value='$kohde'>";
		echo "<input type='hidden' name = 'vproj' value='$proj'>";
		echo "<table>";
		echo "<tr><td class='back'></td>";

		$raja = '0000-00';
		$rajataulu = array();
		$j = 0;

		while ($raja < substr($tilikaudetrow['tilikausi_loppu'], 0, 7)) {
			$vuosi = substr($tilikaudetrow['tilikausi_alku'], 0, 4);
			$kk = substr($tilikaudetrow['tilikausi_alku'], 5, 2);
			$kk += $j;

			if ($kk > 12) {
				$vuosi++;
				$kk -= 12;
			}

			if ($kk < 10) $kk = '0'.$kk;

			$raja = $vuosi."-".$kk;
			$rajataulu[$j] = $vuosi.$kk;

		 	echo "<th>$raja</th>";
		 	$j++;

			$worksheet->writeString($excelrivi, $excelsarake, $raja, $format_bold);
			$excelsarake++;
		}
		echo "</tr>";

		$excelsarake = 0;
		$excelrivi++;

		$tasotyyppi = "U";
		$tilityyppi = "ulkoinen_taso";

		if ($tyyppi == 4) { // Sisäinen tuloslaskelma!!!
			$tasotyyppi = "S";
			$tyyppi = 3;
			$tilityyppi = "sisainen_taso";
		}

		$query = "	SELECT taso.*
					FROM taso
					WHERE taso.yhtio = '$kukarow[yhtio]'
					AND taso.taso LIKE '$tyyppi%'
					AND taso.tyyppi = '$tasotyyppi'";
		$vresult = mysql_query($query) or pupe_error($query);

		$xx = 0;
		$z = 0;

		while ($tasorow = mysql_fetch_array($vresult)) {

			$worksheet->writeString($excelrivi, $excelsarake, "TASO");
			$excelsarake++;

			$worksheet->writeString($excelrivi, $excelsarake, $tasorow['taso']);
			$excelsarake++;

			$worksheet->writeString($excelrivi, $excelsarake, $tasorow['nimi']);
			$excelsarake++;

			echo "<tr><td>$tasorow[taso] $tasorow[nimi]</td>";

			for ($k = 0; $k < $j; $k++) {
				$itaso = $tasorow['taso'];
				$ik = $rajataulu[$k];

				if (is_array($taulunrivit) and $taulunrivit[$xx][0] == "TASO" and $taulunrivit[$xx][1] == $tasorow['taso'] and $taulunrivit[$xx][2] == $tasorow['nimi']) {
					$nro = trim($taulunrivit[$xx][$k+3]);
				}
				else {
					$query = "	SELECT *
								from budjetti
								where yhtio			= '$kukarow[yhtio]'
								and kausi 			= '$ik'
								and taso 			= '$itaso'
								and tili			= ''
								and kustp			= '$kustp'
								and kohde			= '$kohde'
								and projekti		= '$proj'";
					$xresult = mysql_query($query) or pupe_error($query);

					$nro='';

					if (mysql_num_rows($xresult) == 1) {
						$brow = mysql_fetch_array($xresult);
						$nro = $brow['summa'];
					}
				}

				echo "<td><input type='text' name = 'luvut[$itaso][$ik]' value='$nro' size='8'></td>";

				$worksheet->write($excelrivi, $excelsarake, $nro);
				$excelsarake++;
			}
			echo "</tr>";

			$excelsarake = 0;
			$excelrivi++;

			$query = "	SELECT tilino, nimi
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						AND $tilityyppi = '$tasorow[taso]'
						group by 1,2";
			$tiliresult = mysql_query($query) or pupe_error($query);

			$yy = $xx + 1;

			while ($tilirow = mysql_fetch_assoc($tiliresult)) {

				$worksheet->writeString($excelrivi, $excelsarake, "TILI");
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, $tilirow['tilino']);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, $tilirow['nimi']);
				$excelsarake++;

				echo "<tr><td>$tilirow[tilino] $tilirow[nimi]</td>";

				for ($k=0; $k < $j; $k++) {
					$itaso = $tasorow['taso'];
					$ik = $rajataulu[$k];

					if (is_array($taulunrivit) and $taulunrivit[$yy][0] == "TILI" and $taulunrivit[$yy][1] == $tilirow['tilino'] and $taulunrivit[$yy][2] == $tilirow['nimi']) {
						$nro = trim($taulunrivit[$yy][$k+3]);
					}
					else {
						$query = "	SELECT *
									from budjetti
									where yhtio		= '$kukarow[yhtio]'
									and kausi 		= '$ik'
									and taso 		= '$itaso'
									and tili		= '$tilirow[tilino]'
									and kustp		= '$kustp'
									and kohde		= '$kohde'
									and projekti	= '$proj'";
						$xresult = mysql_query($query) or pupe_error($query);
						$nro='';

						if (mysql_num_rows($xresult) == 1) {
							$brow = mysql_fetch_array($xresult);
							$nro = $brow['summa'];
						}
					}
					echo "<td><input type='text' name = 'tilien_luvut[$itaso][$tilirow[tilino]][$ik]' value='$nro' size='8'></td>";

					$worksheet->write($excelrivi, $excelsarake, $nro);
					$excelsarake++;
				}

				echo "</tr>";

				$excelsarake = 0;
				$excelrivi++;				

				$yy++;
			}

			$query = "	SELECT SUM(IF(tilino != '', 1, 0)) rivit
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						AND $tilityyppi = '$tasorow[taso]'";
			$tiliresult = mysql_query($query) or pupe_error($query);
			$tilicount = mysql_fetch_assoc($tiliresult);

			if (trim($tilicount['rivit']) != '') {
				$xx = $xx + $tilicount['rivit'];
			}

			$xx++;

			$excelsarake = 0;
		}

		echo "</form></table>";

		$workbook->close();

		echo "<form method='post'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='kaunisnimi' value='Budjettimatriisi.xls'>";
		echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
		echo "<table>";
		echo "<tr><th>",t("Tallenna raportti (xls)"),":</th>";
		echo "<td class='back'><input type='submit' value='",t("Tallenna"),"'></td></tr>";
		echo "</table></form><br />";

		echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
		echo "<input type='hidden' name='tee' value='file'>";
		echo "<input type='hidden' name='vkustp' value='$kustp'>";
		echo "<input type='hidden' name='vkohde' value='$kohde'>";
		echo "<input type='hidden' name='vproj' value='$proj'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='tkausi' value='$tkausi'>";
		echo "<table>";
		echo "<tr><th>",t("Valitse tiedosto"),"</th><td><input type='file' name='userfile' /></td><td><input type='submit' value='",t("Lähetä"),"' /></td></tr>";
		echo "</table>";
		echo "</form>";

	}

	require ("inc/footer.inc");
}
?>
