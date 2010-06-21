<?php
	if ($tee == "file") {
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) == TRUE) {
			$timeparts = explode(" ",microtime());
			$starttime = $timeparts[1].substr($timeparts[0],1);

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$name	= strtoupper($path_parts['filename']);
			$ext	= strtoupper($path_parts['extension']);

			if ($ext != "TXT" and $ext != "CSV" and $ext != "XLS") {
				die ("<font class='error'><br>".t("Ainoastaan .txt, .csv tai .xls tiedostot sallittuja")."!</font>");
			}

			if ($_FILES['userfile']['size']==0) {
				die ("<font class='error'><br>".t("Tiedosto on tyhj‰")."!</font>");
			}

			if (strtoupper($ext)=="XLS") {
				require_once ('excel_reader/reader.php');

				// ExcelFile
				$data = new Spreadsheet_Excel_Reader();

				// Set output Encoding.
				$data->setOutputEncoding('CP1251');
				$data->setRowColOffset(0);
				$data->read($_FILES['userfile']['tmp_name']);
			}
			else {
				$file = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep‰onnistui")."!");
			}

			// luetaan tiedosto loppuun ja tehd‰‰n array koko datasta
			$excelrivi[][] = array();

			if (strtoupper($ext) == "XLS") {
				for ($excei = 0; $excei < $data->sheets[0]['numRows']; $excei++) {
					for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
						$excelrivi[$excei-1][$excej] = $data->sheets[0]['cells'][$excei][$excej];
					}
				}
			}
			else {
				$excei = 0;

				while ($rivi = fgets($file)) {
					// luetaan rivi tiedostosta..
					$poista	 = array("'", "\\");
					$rivi	 = str_replace($poista,"",$rivi);
					$rivi	 = explode("\t", trim($rivi));

					$excej = 0;
					foreach ($rivi as $riv) {
						$excelrivi[$excei][$excej] = $riv;
						$excej++;
					}
					$excei++;
				}
				fclose($file);
			}

			foreach ($excelrivi as $rivi) {

				$tila 	 = '';
				$valinta = '';

				$tuoteno = trim($rivi[0]);
				$kpl	 = str_replace(",", ".", $rivi[1]);

				$toimaika			= "";
				$kerayspvm			= "";
				$hinta				= "";
				$netto				= "";
				$ale				= "";
				$var				= "";
				$alv				= "";
				$paikka				= "";
				$varasto 			= $laskurow["varasto"];
				$rivitunnus			= "";
				$korvaavakielto		= "";
				$varataan_saldoa	= "";
				$var				= "";
				$perhekielto		= "";
				$jtkielto 		 	= $laskurow['jtkielto'];

				if ($kukarow["extranet"] == "") {
					$toimaika 	= $rivi[2];
					$hinta		= str_replace(",", ".", $rivi[3]);
					$ale	  	= str_replace(",", ".", $rivi[4]);
					$netto 	  	= strtoupper(trim($rivi[5]));
					$var  		= strtoupper(trim($rivi[6]));
					$kommentti	= trim($rivi[7]);

					if (($var!='') and ($var!='P') and ($var!='H') and ($var!='J')) {
						echo t("V‰‰r‰ arvo kent‰ss‰ var! Sallitut arvot ovat: P, J ja H").".<br>";
						$var = '';
					}

					if ($netto != '' and $netto != 'N') {
						echo t("V‰‰r‰ arvo kent‰ss‰ netto! Sallitut arvot ovat: tyhj‰ tai N")."<br>";
						$netto = '';
					}
				}

				if ($tuoteno != '' and $kpl != 0) {

					// Tuotehaku ei toimi extranetin puolella
					if (file_exists("../inc/tuotehaku.inc")) {
						require ("../inc/tuotehaku.inc");
					}

					if ($mikrotila_tuotenumero == "koodilla") {
						$query = "	SELECT *
									FROM tuote
									WHERE eankoodi='$tuoteno' and yhtio='$kukarow[yhtio]'";
					}
					else {
						$query = "	SELECT *
									FROM tuote
									WHERE tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
					}

					$rarresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($rarresult) == 1) {

						$trow = mysql_fetch_array($rarresult);

						$ohitus = 0;
						if ($kukarow["extranet"] != "") {
							$query = "SELECT * FROM asiakas where yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[oletus_asiakas]'";
							$asiakastempres = mysql_query($query);
							$asiakastemprow = mysql_fetch_array($asiakastempres);

							$temp_laskurowwi = array();
							$temp_laskurowwi['liitostunnus']	= $asiakastemprow['tunnus'];
							$temp_laskurowwi['ytunnus']			= $asiakastemprow['ytunnus'];
							$temp_laskurowwi['valkoodi']		= $asiakastemprow['valkoodi'];
							$temp_laskurowwi['maa']				= $asiakastemprow['maa'];


							$hinnat = alehinta($temp_laskurowwi, $trow, 1, '', '', '', "hintaperuste,aleperuste");

							if 	($trow["hinnastoon"] == "V" and ($hinnat["hintaperuste"] < 2 or $hinnat["hintaperuste"] > 12) and ($hinnat["aleperuste"] < 5 or $hinnat["aleperuste"] > 8)) {
								$ohitus = 1;
							}
						}

						if ($ohitus == 0) {
							$tuoteno = $trow["tuoteno"];

							if ($toimaika == '') {
								$toimaika = $laskurow["toimaika"];
							}

							if ($hinta == 0) {
								$hinta = "";
							}

							if ($laskurow["tilaustyyppi"] == "E") {
								$varataan_saldoa = "EI";
							}
							else {
								$varataan_saldoa = "";
							}

							//lis‰t‰‰n rivi
							require ("lisaarivi.inc");
						}
						else {
							echo "<font class='message'>".t("Tuotenumeroa")." $tuoteno ".t("ei lˆydy")."!</font><br>";
						}

					}
					else {
						echo "<font class='message'>".t("Tuotenumeroa")." $tuoteno ".t("ei lˆydy")."!</font><br>";
					}
				}
			}

			$tuoteno	= '';
			$kpl		= '';
			$var		= '';
			$hinta		= '';
			$netto		= '';
			$ale		= '';
			$rivitunnus	= '';
			$kommentti	= '';
			$kerayspvm	= '';
			$toimaika	= '';
			$paikka		= '';
			$alv		= '';
			$varasto 	= '';
			$perheid 	= '';
			$perheid2  	= '';
			$tee 		= 'Y';
		}
		else {
			$tuoteno	= '';
			$kpl		= '';
			$var		= '';
			$hinta		= '';
			$netto		= '';
			$ale		= '';
			$rivitunnus	= '';
			$kommentti	= '';
			$kerayspvm	= '';
			$toimaika	= '';
			$paikka		= '';
			$alv		= '';
			$varasto 	= '';
			$perheid 	= '';
			$perheid2  	= '';
			$tee 		= 'Y';
		}
	}

	if ($tee == 'mikrotila') {
		echo "<font class='head'>$otsikko</font><hr>";

		echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='tee' value='file'>

				<font class='message'>".t("Tiedostomuoto").":</font><br><br>

				<table>
				<tr><th colspan='8'>".t("Sarkaineroteltu tekstitiedosto tai Excel-tiedosto").".</th></tr>
				<tr>";

		echo "	<td>".t("Tuoteno")."</td><td>".t("M‰‰r‰")."</td>";

		if ($kukarow["extranet"] == "") {
			echo "<td>".t("Arvioitu toimitusp‰iv‰ vvvv-kk-pp")."</td><td>".t("Hinta")."</td><td>".t("Ale")."</td><td>".t("Netto")."</td><td>".t("Var")."</td><td>".t("Kommentti")."</td>";
		}

		echo "	</tr>
				</table>

				<br>
				<table>
				<tr>";

		echo "
				<th>".t("Valitse tiedosto").":</th>
				<td><input name='userfile' type='file'></td></tr><tr>
				<th>".t("Tuotteet:")."</th>
				<td><select name='mikrotila_tuotenumero'>
				<option value='tuote'>".t("Tuotenumerolla")."
				<option value='koodilla'>".t("EAN-Koodilla")."
				</select></td></tr><tr>
		 		<td colspan='2' class='back'><input type='submit' value='".t("L‰heta")."'></td>
				</tr>
				</table>
				</form>";
	}
?>