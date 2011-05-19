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

		if (!isset($toim)) $toim = '';
		if (!isset($tkausi)) $tkausi = '';
		if (!isset($ytunnus)) $ytunnus = '';
		if (!isset($asiakasid)) $asiakasid = 0;
		if (!isset($toimittajaid)) $toimittajaid = 0;
		if (!isset($submit_button)) $submit_button = '';
		if (!isset($budj_taulunrivit)) $budj_taulunrivit = array();

		if (!isset($liitostunnukset)) $liitostunnukset = '';
		else $liitostunnukset = urldecode($liitostunnukset);

		$maxrivimaara = 1000;

		if (isset($vaihdaasiakas)) {
			$ytunnus 		 = "";
			$asiakasid 		 = 0;
			$toimittajaid	 = 0;
			$liitostunnukset = "";
		}

		if ($toim == "TUOTE") {
			echo "<font class='head'>".t("Budjetin yll‰pito tuote")."</font><hr>";

			$budj_taulu = "budjetti_tuote";
			$budj_sarak = "tuoteno";
		}
		elseif ($toim == "TOIMITTAJA") {
			echo "<font class='head'>".t("Budjetin yll‰pito toimittaja")."</font><hr>";

			$budj_taulu = "budjetti_toimittaja";
			$budj_sarak = "toimittajan_tunnus";
		}
		elseif ($toim == "ASIAKAS") {
			echo "<font class='head'>".t("Budjetin yll‰pito asiakas")."</font><hr>";

			$budj_taulu = "budjetti_asiakas";
			$budj_sarak = "asiakkaan_tunnus";
		}
		else {
			exit;
		}

		if (isset($muutparametrit)) {
			foreach (explode("##", $muutparametrit) as $muutparametri) {
				list($a, $b) = explode("=", $muutparametri);

				if (strpos($a, "[") !== FALSE) {
					$i = substr($a, strpos($a, "[")+1, strpos($a, "]")-(strpos($a, "[")+1));
					$a = substr($a, 0, strpos($a, "["));

					${$a}[$i] = $b;
				}
				else {
					${$a} = $b;
				}
			}
		}

		if (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$ext = strtoupper($path_parts['extension']);

			if ($ext != "XLS") {
				die ("<font class='error'><br>".t("Ainoastaan .xls tiedostot sallittuja")."!</font>");
			}

			if ($_FILES['userfile']['size'] == 0) {
				die ("<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>");
			}

			require_once ('excel_reader/reader.php');

			// ExcelFile
			$data = new Spreadsheet_Excel_Reader();

			// Set output Encoding.
			$data->setOutputEncoding('CP1251');
			$data->setRowColOffset(0);
			$data->read($_FILES['userfile']['tmp_name']);

			echo "<font class='message'>".t("Tarkastetaan l‰hetetty tiedosto")."...<br></font>";

			$headers	 		= array();
			$budj_taulunrivit 	= array();
			$liitostunnukset 	= "";

			for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
				$headers[] = trim($data->sheets[0]['cells'][0][$excej]);
			}

			for ($excej = (count($headers)-1); $excej > 0 ; $excej--) {
				if ($headers[$excej] != "") {
					break;
				}
				else {
					unset($headers[$excej]);
				}
			}

			// Huomaa n‰m‰ jos muutat excel-failin sarakkeita!!!!
			if ($toim == "TUOTE") {
				$lukualku = 2;
			}
			elseif ($toim == "TOIMITTAJA") {
				$lukualku = 3;
			}
			elseif ($toim == "ASIAKAS") {
				$lukualku = 4;
			}

			if ($headers[$lukualku] == "Tuoteryhm‰") {
				$lukualku++;
				$tuoteryhmittain = "on";
			}

			$insert_rivimaara = 0;

			for ($excei = 1; $excei < $data->sheets[0]['numRows']; $excei++) {

				$liitun = $data->sheets[0]['cells'][$excei][0];

				$liitostunnukset .= "'$liitun',";

				if ($tuoteryhmittain != "") {
					$try = $data->sheets[0]['cells'][$excei][$lukualku-1];
				}

				for ($excej = $lukualku; $excej < count($headers); $excej++) {
					$kasiind = str_replace("-", "", $headers[$excej]);

					if ($tuoteryhmittain != "") {
						$budj_taulunrivit[$liitun][$kasiind][$try] = trim($data->sheets[0]['cells'][$excei][$excej]);
					}
					else {
						$budj_taulunrivit[$liitun][$kasiind][] = trim($data->sheets[0]['cells'][$excei][$excej]);
					}

					$insert_rivimaara++;
				}
			}

			if ($insert_rivimaara >= $maxrivimaara) {
				// Vied‰‰n suoraan kantaan
				$luvut = $budj_taulunrivit;
				$submit_button = "OK";

				echo "<font class='error'>".t("HUOM: Maksimirivim‰‰r‰ ylittyi, rivej‰ ei n‰ytet‰ ruudulla. Rivit tallennetaan suoraan tietokantaan")."!<br><br></font>";
			}
			else {
				echo "<font class='error'>".t("HUOM: Excel-tiedoston luvut eiv‰t viel‰ tallennettu tietokantaan")."!<br>".t("Klikkaa")." '",t("N‰yt‰/Tallenna"),"' ".t("tallentaaksesi luvut")."!<br></font>".t("Tiedosto ok")."!<br><br></font>";
			}

			$liitostunnukset = substr($liitostunnukset, 0, -1);
		}

		if (isset($luvut) and count($luvut) > 0 and $submit_button != '') {
			$paiv  = 0;
			$lisaa = 0;

			foreach ($luvut as $liitostunnus => $rivi) {
				foreach ($rivi as $kausi => $solut) {
					foreach ($solut as $try => $solu) {

						if ($try == 0) $try = "";

						$solu = str_replace(",", ".", $solu);

						if ($solu == '!' or $solu = (float) $solu) {

							if ($solu == '!') $solu = 0;

							$solu = (float) $solu;

							$query = "	SELECT summa
										FROM $budj_taulu
										WHERE yhtio 			= '$kukarow[yhtio]'
										AND $budj_sarak		 	= '$liitostunnus'
										AND kausi 				= '$kausi'
										AND dyna_puu_tunnus 	= ''
										AND osasto 				= ''
										AND try 				= '$try'";
							$result = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($result) > 0) {

								$budjrow = mysql_fetch_assoc($result);

								if ($budjrow['summa'] != $solu) {

									if ($solu == 0.00) {
										$query = "	DELETE FROM $budj_taulu
													WHERE yhtio 			= '$kukarow[yhtio]'
													AND $budj_sarak		 	= '$liitostunnus'
													AND kausi 				= '$kausi'
													AND dyna_puu_tunnus 	= ''
													AND osasto 				= ''
													AND try 				= '$try'";
									}
									else {
										$query	= "	UPDATE $budj_taulu SET
													summa = $solu,
													muuttaja = '$kukarow[kuka]',
													muutospvm = now()
													WHERE yhtio 			= '$kukarow[yhtio]'
													AND $budj_sarak		 	= '$liitostunnus'
													AND kausi 				= '$kausi'
													AND dyna_puu_tunnus 	= ''
													AND osasto 				= ''
													AND try 				= '$try'";
									}
									$result = mysql_query($query) or pupe_error($query);
									$paiv++;
								}
							}
							else {
								$query = "	INSERT INTO $budj_taulu SET
											summa 				= $solu,
											yhtio 				= '$kukarow[yhtio]',
											kausi 				= '$kausi',
											$budj_sarak		 	= '$liitostunnus',
											osasto 				= '',
											try 				= '$try',
											dyna_puu_tunnus 	= '',
											laatija 			= '$kukarow[kuka]',
											luontiaika 			= now(),
											muutospvm 			= now(),
											muuttaja 			= '$kukarow[kuka]'";
								$result = mysql_query($query) or pupe_error($query);
								$lisaa++;
							}
						}
					}
				}
			}
			echo "<font class='message'>".t("P‰ivitin")." $paiv. ".t("Lis‰sin")." $lisaa.</font><br /><br />";
		}

		if ($toim == "TOIMITTAJA" and $ytunnus != '' and $toimittajaid == 0) {

			$muutparametrit = "";

			unset($_POST["toimittajaid"]);

			foreach ($_POST as $key => $value) {
				if (is_array($value)) {
					foreach ($value as $a => $b) {
						$muutparametrit .= $key."[".$a."]=".$b."##";
					}
				}
				else {
					$muutparametrit .= $key."=".$value."##";
				}
			}

			require ("inc/kevyt_toimittajahaku.inc");

			echo "<br />";

			if (trim($ytunnus) == '') {
				$submit_button = '';
			}
			else {
				$submit_button = 'OK';
			}
		}
		elseif ($toim == "ASIAKAS" and $ytunnus != '' and $asiakasid == 0) {

			$muutparametrit = "";

			unset($_POST["asiakasid"]);

			foreach ($_POST as $key => $value) {
				if (is_array($value)) {
					foreach ($value as $a => $b) {
						$muutparametrit .= $key."[".$a."]=".$b."##";
					}
				}
				else {
					$muutparametrit .= $key."=".$value."##";
				}
			}

			require ("inc/asiakashaku.inc");

			echo "<br />";

			if (trim($ytunnus) == '') {
				$submit_button = '';
			}
			else {
				$submit_button = 'OK';
			}
		}

		if ($asiakasid > 0 or $toimittajaid > 0 or $liitostunnukset != "") {
			if ($toim == "TOIMITTAJA") {
				echo "<form method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='submit' name='vaihdaasiakas' value='",t("Vaihda tomittaja / nollaa excelrajaus"),"' />
						</form><br><br>";
			}
			elseif ($toim == "ASIAKAS") {
				echo "<form method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='submit' name='vaihdaasiakas' value='",t("Vaihda asiakas / nollaa excelrajaus"),"' />
						</form><br><br>";
			}
		}

		echo "<form method='post' enctype='multipart/form-data'>
				<input type='hidden' name='toim' value='$toim'>";

		echo "<table>";

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

		if ($liitostunnukset != "") {
			echo "<tr><th>",t("Rajaus"),"</th><td>".t("Excel-tiedostosta")."</td>";
			echo "<input type='hidden' name='liitostunnukset' value='".urlencode($liitostunnukset)."'>";
		}
		else {
			if ($toim == "TUOTE") {
				echo "<tr><th>",t("Valitse tuote"),"</th>";
				echo "<td><input type='text' name='tuoteno' value='$tuoteno' /></td></tr>";

				echo "<tr><th>".t("tai rajaa tuotekategorialla")."</th><td>";

				$monivalintalaatikot = array('DYNAAMINEN_TUOTE', 'OSASTO', 'TRY');
				$monivalintalaatikot_normaali = array();

				require ("tilauskasittely/monivalintalaatikot.inc");

				echo "</td></tr>";
			}
			elseif ($toim == "TOIMITTAJA") {
				echo "<tr><th>",t("Valitse toimittaja"),"</th>";

				if ($toimittajaid > 0) {
					$query = "	SELECT *
								from toimi
								where yhtio = '$kukarow[yhtio]'
								and tunnus = '$toimittajaid'";
					$result = mysql_query($query) or pupe_error($query);
					$toimirow = mysql_fetch_assoc($result);

					echo "<td>$toimirow[nimi] $toimirow[nimitark]<br>
							$toimirow[toim_nimi] $toimirow[toim_nimitark]
							<input type='hidden' name='toimittajaid' value='$toimittajaid' /></td>";
				}
				else {
					echo "<td><input type='text' name='ytunnus' value='$ytunnus' /></td></tr>";
				}
			}
			elseif ($toim == "ASIAKAS") {
				echo "<tr><th>",t("Valitse asiakas"),"</th>";

				if ($asiakasid > 0) {
					$query = "	SELECT *
								from asiakas
								where yhtio = '$kukarow[yhtio]'
								and tunnus = '$asiakasid'";
					$result = mysql_query($query) or pupe_error($query);
					$asiakasrow = mysql_fetch_assoc($result);

					echo "<td>$asiakasrow[nimi] $asiakasrow[nimitark]<br>
							$asiakasrow[toim_nimi] $asiakasrow[toim_nimitark]
							<input type='hidden' name='asiakasid' value='$asiakasid' /></td>";
				}
				else {
					echo "<td><input type='text' name='ytunnus' value='$ytunnus' /></td></tr>";
				}

				echo "<tr><th>".t("tai rajaa asiakaskategorialla")."</th><td>";

				$monivalintalaatikot = array('DYNAAMINEN_ASIAKAS', '<br>ASIAKASOSASTO', 'ASIAKASRYHMA');
				$monivalintalaatikot_normaali = array();

				require ("tilauskasittely/monivalintalaatikot.inc");

				echo "</td></tr>";
			}
		}

		if ($toim == "ASIAKAS" or $toim == "TOIMITTAJA") {
			$chk = "";

			if ($tuoteryhmittain != "") {
				$chk = "CHECKED";
			}

			echo "<tr><th>",t("Tuoteryhmitt‰in"),"</th><td><input type='checkbox' name='tuoteryhmittain' $chk></td></tr>";
		}

		echo "<tr><th>",t("Lue budjettiluvut tiedostosta"),"</th><td><input type='file' name='userfile' /></td>";
		echo "</table><br>";

		echo t("Budjettiluvun voi poistaa huutomerkill‰ (!)"),"<br />";

		echo "<br />";
		echo "<input type='submit' name='submit_button' id='submit_button' value='",t("N‰yt‰/Tallenna"),"' /><br>";

		if (!isset($lisa)) {
			$lisa = "";
		}
		if (!isset($lisa_dynaaminen)) {
			$lisa_dynaaminen = array("tuote" => "", "asiakas" => "");
		}
		if (!isset($lisa_parametri)) {
			$lisa_parametri = "";
		}

		if (trim($tkausi) != '') {
			$query = "	SELECT *
						FROM tilikaudet
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  = '$tkausi'";
			$vresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($vresult) == 1) $tilikaudetrow = mysql_fetch_array($vresult);
		}

		if ($toimittajaid > 0) {
			$lisa .= " and toimi.tunnus = $toimittajaid ";
		}

		if ($asiakasid > 0) {
			$lisa .= " and asiakas.tunnus = $asiakasid ";
		}

		if ($submit_button != "" and ($tuoteno != "" or $asiakasid > 0 or $toimittajaid > 0 or $lisa != "" or $lisa_parametri != "" or ($toim == "TUOTE" and $lisa_dynaaminen["tuote"] != "") or ($toim == "ASIAKAS" and $lisa_dynaaminen["asiakas"]) or $liitostunnukset != "") and is_array($tilikaudetrow)) {

			if (!@include('Spreadsheet/Excel/Writer.php')) {
				echo "<font class='error'>",t("VIRHE: Pupe-asennuksesi ei tue Excel-kirjoitusta."),"</font><br>";
				exit;
			}

			//keksit‰‰n failille joku varmasti uniikki nimi:
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

			if ($toim == "TUOTE") {
				$worksheet->write($excelrivi, $excelsarake, t("Tuote"), $format_bold);
				$excelsarake++;
			}
			elseif ($toim == "TOIMITTAJA") {
				$worksheet->write($excelrivi, $excelsarake, t("Toimittajan tunnus"), $format_bold);
				$excelsarake++;
			}
			elseif ($toim == "ASIAKAS") {
				$worksheet->write($excelrivi, $excelsarake, t("Asiakkaan tunnus"), $format_bold);
				$excelsarake++;
			}

			if ($toim == "ASIAKAS" or $toim == "TOIMITTAJA") {
				$worksheet->write($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
				$excelsarake++;
			}

			if ($toim == "ASIAKAS") {
				$worksheet->write($excelrivi, $excelsarake, t("Asiakasnro"), $format_bold);
				$excelsarake++;
			}

			$worksheet->write($excelrivi, $excelsarake, t("Nimi"), $format_bold);
			$excelsarake++;

			if ($liitostunnukset != "") {
				// Excelist‰ tulleet asiakkaat ylikirjaavaat muut rajaukset
				if ($toim == "TUOTE") {
					$lisa = " and tuote.tuoteno in ($liitostunnukset) ";
				}
				elseif ($toim == "TOIMITTAJA") {
					$lisa = " and toimi.tunnus in ($liitostunnukset) ";
				}
				elseif ($toim == "ASIAKAS") {
					$lisa = " and asiakas.tunnus in ($liitostunnukset) ";
				}

				$lisa_parametri  = "";
				$lisa_dynaaminen = array("tuote" => "", "asiakas" => "");
			}

			if ($toim == "TUOTE" and $tuoteno != "") {
				$lisa .= " and tuote.tuoteno='$tuoteno' ";
			}

			if ($toim == "TUOTE") {
				$query = "	SELECT DISTINCT tuote.tuoteno, tuote.nimitys
							FROM tuote
							$lisa_parametri
							{$lisa_dynaaminen["tuote"]}
							WHERE tuote.yhtio = '{$kukarow['yhtio']}'
							AND tuote.status != 'P'
							$lisa";
			}
			elseif ($toim == "TOIMITTAJA") {
				$query = "	SELECT DISTINCT toimi.tunnus toimittajan_tunnus, toimi.ytunnus, toimi.ytunnus toimittajanro, toimi.nimi, toimi.nimitark
							FROM toimi
							WHERE toimi.yhtio = '$kukarow[yhtio]'
							$lisa";
			}
			elseif ($toim == "ASIAKAS") {
				$query = "	SELECT DISTINCT asiakas.tunnus asiakkaan_tunnus, asiakas.ytunnus, asiakas.asiakasnro, asiakas.nimi, asiakas.nimitark,
							IF(STRCMP(TRIM(CONCAT(asiakas.toim_nimi, ' ', asiakas.toim_nimitark)), TRIM(CONCAT(asiakas.nimi, ' ', asiakas.nimitark))) != 0, asiakas.toim_nimi, '') toim_nimi,
							IF(STRCMP(TRIM(CONCAT(asiakas.toim_nimi, ' ', asiakas.toim_nimitark)), TRIM(CONCAT(asiakas.nimi, ' ', asiakas.nimitark))) != 0, asiakas.toim_nimitark, '') toim_nimitark
							FROM asiakas
							{$lisa_dynaaminen["asiakas"]}
							WHERE asiakas.yhtio = '$kukarow[yhtio]'
							$lisa";
			}

			$result = mysql_query($query) or pupe_error($query);

			echo "<br />";

			if ($tuoteryhmittain != "") {
				// Haetaan tuoteryhm‰t
				$res = t_avainsana("TRY");
				$rivimaara = mysql_num_rows($res)*mysql_num_rows($result);
			}
			else {
				$rivimaara = mysql_num_rows($result);
			}

			if ($rivimaara >= $maxrivimaara) {
				echo "<br><font class='error'>".t("HUOM: Maksimirivim‰‰r‰ ylittyi, rivej‰ ei n‰ytet‰ ruudulla. Tallenna Excel-tiedosto")."!</font><br><br>";
			}

			if ($rivimaara < $maxrivimaara) echo "<table>";

			if ($toim == "TUOTE") {
				if ($rivimaara < $maxrivimaara) echo "<tr><th>",t("Tuote"),"</th>";
			}
			elseif ($toim == "TOIMITTAJA") {
				if ($rivimaara < $maxrivimaara) echo "<tr><th>",t("Toimittaja"),"</th>";
			}
			elseif ($toim == "ASIAKAS") {
				if ($rivimaara < $maxrivimaara) echo "<tr><th>",t("Asiakas"),"</th>";
			}

			if ($tuoteryhmittain != "") {
				if ($rivimaara < $maxrivimaara) echo "<th>",t("Tuoteryhm‰"),"</th>";

				$worksheet->write($excelrivi, $excelsarake, t("Tuoteryhm‰"), $format_bold);
				$excelsarake++;
			}

			$raja 		= '0000-00';
			$rajataulu 	= array();
			$sarakkeet	= 0;

			while ($raja < substr($tilikaudetrow['tilikausi_loppu'], 0, 7)) {

				$vuosi 	= substr($tilikaudetrow['tilikausi_alku'], 0, 4);
				$kk 	= substr($tilikaudetrow['tilikausi_alku'], 5, 2);
				$kk += $sarakkeet;

				if ($kk > 12) {
					$vuosi++;
					$kk -= 12;
				}

				if ($kk < 10) $kk = '0'.$kk;

				$rajataulu[$sarakkeet] = $vuosi.$kk;
				$sarakkeet++;

				$raja = $vuosi."-".$kk;

			 	if ($rivimaara < $maxrivimaara) echo "<th>$raja</th>";

				$worksheet->write($excelrivi, $excelsarake, $raja, $format_bold);
				$excelsarake++;
			}

			if ($rivimaara < $maxrivimaara) echo "</tr>";

			$excelrivi++;
			$xx = 0;

			function piirra_budj_rivi ($row, $tryrow = "") {
				global $kukarow, $toim, $worksheet, $excelrivi, $budj_taulu, $rajataulu, $budj_taulunrivit, $xx, $budj_sarak, $sarakkeet, $rivimaara, $maxrivimaara;

				$excelsarake = 0;

				$worksheet->write($excelrivi, $excelsarake, $row[$budj_sarak]);
				$excelsarake++;

				if ($toim == "ASIAKAS" or $toim == "TOIMITTAJA") {
					$worksheet->writeString($excelrivi, $excelsarake, $row['ytunnus']);
					$excelsarake++;
				}

				if ($toim == "ASIAKAS") {
					$worksheet->writeString($excelrivi, $excelsarake, $row['asiakasnro']);
					$excelsarake++;
				}

				if ($toim == "TUOTE") {
					$worksheet->writeString($excelrivi, $excelsarake, $row['nimitys']);
					$excelsarake++;
					if ($rivimaara < $maxrivimaara) echo "<tr><td>$row[tuoteno] $row[nimitys]</td>";
				}
				elseif ($toim == "ASIAKAS" or $toim == "TOIMITTAJA") {
					$worksheet->writeString($excelrivi, $excelsarake, $row['nimi'].' '.$row['nimitark']);
					$excelsarake++;
					if ($rivimaara < $maxrivimaara) echo "<tr><td>$row[ytunnus] $row[asiakasnro]<br>$row[nimi] $row[nimitark]<br>$row[toim_nimi] $row[toim_nimitark]</td>";
				}

				if (is_array($tryrow)) {
					if ($rivimaara < $maxrivimaara) echo "<td>$tryrow[selite] $tryrow[selitetark]</td>";

					$worksheet->write($excelrivi, $excelsarake, $tryrow["selite"]);
					$excelsarake++;

					$try = $tryrow["selite"];
					$try_ind = $try;
				}
				else {
					$try = "";
					$try_ind = 0;
				}

				for ($k = 0; $k < $sarakkeet; $k++) {
					$ik = $rajataulu[$k];

					if (isset($budj_taulunrivit[$row[$budj_sarak]][$ik][$try_ind])) {
						$nro = (float) trim($budj_taulunrivit[$row[$budj_sarak]][$ik][$try_ind]);
					}
					else {
						$query = "	SELECT *
									FROM $budj_taulu
									WHERE yhtio				= '$kukarow[yhtio]'
									and kausi		 		= '$ik'
									and $budj_sarak			= '$row[$budj_sarak]'
									and dyna_puu_tunnus		= ''
									and osasto				= ''
									and try					= '$try'";
						$xresult = mysql_query($query) or pupe_error($query);
						$nro = '';

						if (mysql_num_rows($xresult) == 1) {
							$brow = mysql_fetch_assoc($xresult);
							$nro = $brow['summa'];
						}
					}

					if ($rivimaara < $maxrivimaara) echo "<td>";

					if (is_array($tryrow)) {
						if ($rivimaara < $maxrivimaara) echo "<input type='text' name = 'luvut[{$row[$budj_sarak]}][{$ik}][{$tryrow["selite"]}]' value='{$nro}' size='8'></td>";
					}
					else {
						if ($rivimaara < $maxrivimaara) echo "<input type='text' name = 'luvut[{$row[$budj_sarak]}][{$ik}][]' value='{$nro}' size='8'>";
					}

					if ($rivimaara < $maxrivimaara) echo "</td>";

					$worksheet->write($excelrivi, $excelsarake, $nro);
					$excelsarake++;
				}

				if ($rivimaara < $maxrivimaara) echo "</tr>";

				$xx++;
				$excelrivi++;
			}

			if ($tuoteryhmittain != "") {
				while ($tryrow = mysql_fetch_assoc($res)) {
					while ($row = mysql_fetch_assoc($result)) {
						piirra_budj_rivi($row, $tryrow);
					}

					mysql_data_seek($result, 0);
				}
			}
			else {
				while ($row = mysql_fetch_assoc($result)) {
					piirra_budj_rivi($row);
				}
			}

			$workbook->close();

			if ($rivimaara < $maxrivimaara) echo "</table>";

			echo "</form><br />";

			echo "<form method='post'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Budjettimatriisi_$toim.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<table>";
			echo "<tr><th>",t("Tallenna raportti (xls)"),":</th>";
			echo "<td class='back'><input type='submit' value='",t("Tallenna"),"'></td></tr>";
			echo "</table></form><br />";

		}
		else {
			echo "</form>";
		}
	}

	require ("inc/footer.inc");

?>