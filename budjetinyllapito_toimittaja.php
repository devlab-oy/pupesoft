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

		echo "<font class='head'>".t("Budjetin ylläpito toimittaja")."</font><hr>";

		if (!isset($asiakasryhma)) $asiakasryhma = '';
		if (!isset($submit_button)) $submit_button = '';
		if (!isset($tkausi)) $tkausi = '';
		if (!isset($ytunnus)) $ytunnus = '';
		if (!isset($asiakasid)) $asiakasid = '';
		
		if (isset($tee) and $tee == 'file') {
			if (isset($mul_osasto) and $mul_osasto != '') $mul_osasto = unserialize(urldecode($mul_osasto));
			if (isset($mul_try) and $mul_try != '') $mul_try = unserialize(urldecode($mul_try));
		}
		
		/*
		Jos edellinen ja nykyinen on samat, ei päivitetä
		jos edellinen ja nykyinen on eri, päivitetään
		// Jee.... Eiku soodaa
		*/

		if (is_array($luvut) and isset($submit_button) and $submit_button == 'Näytä/Tallenna') {
			$paiv = 0;
			$lisaa = 0;

			foreach ($luvut as $toimittaja_tunnus => $rivi) {

				foreach ($rivi as $kausi => $solu) {
					$solu = str_replace ( ",", ".", $solu);

					if ($solu == '!' or $solu = (float) $solu) {

						if ($solu == '!') $solu = 0;

						$solu = (float) $solu;

						$query = "	SELECT summa
									FROM budjetti_toimittaja
									WHERE yhtio = '$kukarow[yhtio]'
									AND toimittaja_tunnus = '$toimittaja_tunnus'
									AND kausi = '$kausi'
									AND dyna_puu_tunnus = '$kaikki_tunnukset'
									AND osasto = '".implode(",", $mul_osasto)."'
									AND try = '".implode(",", $mul_try)."'";
						$result = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($result) == 1) {

							$budjrow = mysql_fetch_assoc($result);

							if ($budjrow['summa'] != $solu) { //ytest

								if ($solu == 0.00) {
									$query = "	DELETE FROM budjetti_toimittaja
												WHERE yhtio = '$kukarow[yhtio]'
												AND toimittaja_tunnus = '$toimittaja_tunnus'
												AND kausi = '$kausi'
												AND dyna_puu_tunnus = '$kaikki_tunnukset'
												AND osasto = '".implode(",", $mul_osasto)."'
												AND try = '".implode(",", $mul_try)."'";
								}
								else {
									$query	= "	UPDATE budjetti_toimittaja SET
												summa = $solu,
												muuttaja = '$kukarow[kuka]',
												muutospvm = now()
												WHERE yhtio = '$kukarow[yhtio]'
												AND toimittaja_tunnus = '$toimittaja_tunnus'
												AND kausi = '$kausi'
												AND dyna_puu_tunnus = '$kaikki_tunnukset'
												AND osasto = '".implode(",", $mul_osasto)."'
												AND try = '".implode(",", $mul_try)."'";
								}
								$result = mysql_query($query) or pupe_error($query);
								$paiv++;
							}
						}
						else {
							$query = "	INSERT INTO budjetti_toimittaja SET
										summa = $solu,
										yhtio = '$kukarow[yhtio]',
										kausi = '$kausi',
										toimittaja_tunnus = '$toimittaja_tunnus',
										osasto = '".implode(",", $mul_osasto)."',
										try = '".implode(",", $mul_try)."',
										dyna_puu_tunnus = '$kaikki_tunnukset',
										laatija = '$kukarow[kuka]',
										luontiaika = now(),
										muutospvm = now(),
										muuttaja = '$kukarow[kuka]'";
							$result = mysql_query($query) or pupe_error($query);
							$lisaa++;
				
						}
						
					}
				}
			}
			echo "<font class='message'>".t("Päivitin ").$paiv.t(" Lisäsin ").$lisaa."</font><br /><br />";
		}

		if ($ytunnus != '') {

			if (!isset($muutparametrit)) {
				$muutparametrit = $tkausi.'#'.$asiakasryhma.'#'.$kaikki_tunnukset.'#'.urlencode(serialize($mul_osasto)).'#'.urlencode(serialize($mul_try));
			}

			require ("inc/kevyt_toimittajahaku.inc"); // voiskohan ton muuttaa toimittajahauksi ?
		
			echo "<br />";
			if (trim($ytunnus) == '') {
				$submit_button = '';
			}
			else {
				$submit_button = 'asdf';
			}
		}

		if (isset($muutparametrit) and strlen($muutparametrit) > 1) {
			list($tkausi, $asiakasryhma, $kaikki_tunnukset, $mul_osasto, $mul_try) = explode('#', $muutparametrit);
			$mul_osasto = unserialize(urldecode($mul_osasto));
			$mul_try = unserialize(urldecode($mul_try));
		}

		echo "<form method='post'><table>";
		echo "<input type='hidden' name='asiakasid' value='$asiakasid' />";
		echo "<input type='hidden' name='edellinen_mul_osasto' value='".urlencode(serialize($mul_osasto))."'>";
		echo "<input type='hidden' name='edellinen_mul_try' value='".urlencode(serialize($mul_try))."'>";

		echo "<tr><th>",t("Asiakas"),"</th><td><input type='text' name='ytunnus' value='$ytunnus' /></td></tr>";

		$query = "	SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY tilikausi_alku desc";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<tr><th>",t("Tilikausi"),"</th><td><select name='tkausi'>";

		while ($vrow = mysql_fetch_assoc($vresult)) {
			$sel = $tkausi == $vrow['tunnus'] ? ' selected' : '';
			echo "<option value = '$vrow[tunnus]'$sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"])."</option>";
		}

		echo "</select></td></tr>";
		echo "</table>";

		echo t("Budjettiluvun voi poistaa huutomerkillä (!)"),"<br /><br />";

		$avainsana_result = t_avainsana('DYNAAMINEN_PUU', '', " and selite='Tuote' ");

		if (mysql_num_rows($avainsana_result) == 1) { // normaalisti 1
			$monivalintalaatikot = array('DYNAAMINEN_TUOTE');
			$monivalintalaatikot_normaali = array();
		
			require ("tilauskasittely/monivalintalaatikot.inc");
		}

		$monivalintalaatikot = array('OSASTO', 'TRY');
		$monivalintalaatikot_normaali = array();

		require ("tilauskasittely/monivalintalaatikot.inc");

		if (trim($kaikki_tunnukset) != '') {
			if (substr($kaikki_tunnukset, -1, 1) == ',') {
				$kaikki_tunnukset = substr($kaikki_tunnukset, 0, -1);
			}

			echo "<input type='hidden' name='kaikki_tunnukset' value='$kaikki_tunnukset' />";
		}

		echo "<br />";
		echo "<table><tr><td class='back'><input type='submit' name='submit_button' id='submit_button' value='",t("Näytä/Tallenna"),"' /></td></tr></table>";

		if (trim($tkausi) != '') {
			$query = "	SELECT *
						FROM tilikaudet
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$tkausi'";
			$vresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($vresult) == 1) $tilikaudetrow = mysql_fetch_array($vresult);
		}

		if ($submit_button and is_array($tilikaudetrow)) {

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

				for ($excej = 1; $excej = (count($headers)-1); $excej--) {
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

			$worksheet->write($excelrivi, $excelsarake, t("Toimittajan tunnus"), $format_bold);
			$excelsarake++;

			$worksheet->write($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
			$excelsarake++;

			$worksheet->write($excelrivi, $excelsarake, t("toimittajanro"), $format_bold);
			$excelsarake++;

			$worksheet->write($excelrivi, $excelsarake, t("Nimi"), $format_bold);
			$excelsarake++;

			$lisa = '';

			if (trim($asiakasryhma) != '') {
				$lisa .= " and asiakas.ryhma = '".(string) $asiakasryhma."' ";
			}

			if ($ytunnus != '') {
				$lisa .= " and toimi.ytunnus = '$ytunnus' ";
			}

			$query = "	SELECT tunnus toimittaja_tunnus, ytunnus, ytunnus toimittajanro, nimi, nimitark
			 			#,IF(STRCMP(TRIM(CONCAT(toim_nimi, ' ', toim_nimitark)), TRIM(CONCAT(nimi, ' ', nimitark))) != 0, toim_nimi, '') toim_nimi, 
						#IF(STRCMP(TRIM(CONCAT(toim_nimi, ' ', toim_nimitark)), TRIM(CONCAT(nimi, ' ', nimitark))) != 0, toim_nimitark, '') toim_nimitark
						FROM toimi
						WHERE yhtio = '$kukarow[yhtio]'
						$lisa";
			$result = mysql_query($query) or pupe_error($query);

			echo "<br />";
			echo "<table>";

			echo "<tr><th>",t("Ytunnus"),"</th><th>",t("toimittajanro"),"</th><th>",t("Nimi"),"</th>";

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

				$worksheet->write($excelrivi, $excelsarake, $raja, $format_bold);
				$excelsarake++;
			}

			echo "</tr>";

			$excelsarake = 0;
			$excelrivi++;

			$xx = 0;

			while ($row = mysql_fetch_assoc($result)) {

				$worksheet->write($excelrivi, $excelsarake, $row['toimittaja_tunnus']);
				$excelsarake++;

				$worksheet->write($excelrivi, $excelsarake, $row['ytunnus']);
				$excelsarake++;

				$worksheet->write($excelrivi, $excelsarake, $row['toimittajanro']);
				$excelsarake++;

				$worksheet->write($excelrivi, $excelsarake, $row['nimi'].' '.$row['nimitark']);
				$excelsarake++;

				echo "<tr><td>$row[ytunnus]</td><td>$row[toimittajanro]</td><td>$row[nimi] $row[nimitark]<br />$row[toim_nimi] $row[toim_nimitark]</td>";

				for ($k = 0; $k < $j; $k++) {
					$ik = $rajataulu[$k];

					if (is_array($taulunrivit) and $taulunrivit[$xx][0] == $row['toimittaja_tunnus']) {
						$nro = trim($taulunrivit[$xx][$k+4]);
					}
					else {

						$query = "	SELECT *
									FROM budjetti_toimittaja
									WHERE yhtio				= '$kukarow[yhtio]'
									and kausi		 		= '$ik'
									and toimittaja_tunnus	= '$row[toimittaja_tunnus]'
									and dyna_puu_tunnus		= '$kaikki_tunnukset'
									and osasto				= '".implode(",", $mul_osasto)."'
									and try					= '".implode(",", $mul_try)."'";
						$xresult = mysql_query($query) or pupe_error($query);
						//echo $query;
						$nro = '';
						
						if (mysql_num_rows($xresult) == 1) {
							$brow = mysql_fetch_assoc($xresult);
							$nro = $brow['summa'];
						}
					}

					echo "<td><input type='text' name = 'luvut[{$row['toimittaja_tunnus']}][{$ik}]' value='{$nro}' size='8'></td>";

					$worksheet->write($excelrivi, $excelsarake, $nro);
					$excelsarake++;
				}

				echo "</tr>";
				$xx++;

				$excelsarake = 0;
				$excelrivi++;				
			}

			$workbook->close();

			echo "</table></form><br />";

			echo "<form method='post'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Budjettimatriisi_toimittaja.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<table>";
			echo "<tr><th>",t("Tallenna raportti (xls)"),":</th>";
			echo "<td class='back'><input type='submit' value='",t("Tallenna"),"'></td></tr>";
			echo "</table></form><br />";

			echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
			echo "<input type='hidden' name='tee' value='file' />";
			echo "<input type='hidden' name='asiakasryhma' value='$asiakasryhma' />";
			echo "<input type='hidden' name='submit_button' value='joo' />";
			echo "<input type='hidden' name='tkausi' value='$tkausi' />";
			echo "<input type='hidden' name='ytunnus' value='$ytunnus' />";
			echo "<input type='hidden' name='mul_osasto' value='".urlencode(serialize($mul_osasto))."'>";
			echo "<input type='hidden' name='mul_try' value='".urlencode(serialize($mul_try))."'>";

			if (isset($muutparametrit) and strlen($muutparametrit) > 1) {
				echo "<input type='hidden' name='muutparametrit' value='$muutparametrit' />";
			}

			echo "<input type='hidden' name='kaikki_tunnukset' value='$kaikki_tunnukset' />";
			echo "<table>";
			echo "<tr><th>",t("Valitse tiedosto"),"</th><td><input type='file' name='userfile' /></td><td><input type='submit' value='",t("Lähetä"),"' /></td></tr>";
			echo "</table>";
			echo "</form>";
		}
		else {
			echo "</form>";
		}
	}

	require ("inc/footer.inc");

?>