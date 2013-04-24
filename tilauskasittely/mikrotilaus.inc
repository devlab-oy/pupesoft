<?php

	/* FILEFORMAATTI: (ilman sarakeotsikoita)
	Tuoteno (pakollinen)
	M‰‰r‰ (pakollinen)
	Arvioitu toimitusp‰iv‰ vvvv-kk-pp
	Hinta
	Ale
	Netto
	Var
	Kommentti
	*/

	/* VAIHTOEHTOINEN FILEFORMAATTI: (sarakeotsikoilla)
	Tuoteno (pakollinen)
	M‰‰r‰ (pakollinen)
	Arvioitu toimitusp‰iv‰ vvvv-kk-pp
	Nimitys
	Hinta
	Ale
	Netto
	Var
	Kommentti
	*/

	if ($tee == "file") {
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
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
			$excelrivi = array();

			if (strtoupper($ext) == "XLS") {
				for ($excei = 0; $excei < $data->sheets[0]['numRows']; $excei++) {
					for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
						$excelrivi[$excei][$excej] = $data->sheets[0]['cells'][$excei][$excej];
					}
				}
			}
			else {
				$excei = 0;

				while ($rivi = fgets($file)) {
					// luetaan rivi tiedostosta..
					$rivi = explode("\t", pupesoft_cleanstring($rivi));

					$excej = 0;
					foreach ($rivi as $riv) {
						$excelrivi[$excei][$excej] = $riv;
						$excej++;
					}
					$excei++;
				}
				fclose($file);
			}

			$filetyyppi = "";
			$rivilask1 = 0;

			# Tiedostosta p‰ivitys
			foreach ($excelrivi as $rivi) {
				$rivilask1++;

				# tila==G, alatila==V
				if ($toim == "MYYNTITILI" and $laskurow['tila'] == "G" and $laskurow['alatila'] == "V" and $laskurow['tilaustyyppi'] == 'M') {
					$var = $rivi[7];
					$tuoteno = $rivi[0];
					$kpl = $rivi[1];
					$rivitunnus = 0;

					# Tarkistukset, var ja kpl
					if ($var != 'A' and $var != 'B'	) {
						echo "<font class='error'>".t("V‰‰r‰ arvo kent‰ss‰ var! Sallitut arvot ovat: A tai B").". ".t("Rivill‰").": $rivilask1. '$var'"."</font><br><br>";
						continue;
					}

					$query = "	SELECT tunnus
								FROM tilausrivi
								WHERE yhtio='$kukarow[yhtio]'
								AND tyyppi = 'G'
								AND var = ''
								AND otunnus='$kukarow[kesken]'
								AND tuoteno='$tuoteno'";

					$result = pupe_query($query);

					if (mysql_num_rows($result) == 1) {
						$row = mysql_fetch_assoc($result);
						$rivitunnus = $row['tunnus'];
					}
					else {
						$query = "	SELECT tunnus
									FROM tilausrivi
									WHERE yhtio='$kukarow[yhtio]'
									AND tyyppi = 'G'
									AND var = ''
									AND otunnus='$kukarow[kesken]'
									AND tuoteno='$tuoteno'
									AND kpl = '$kpl'";

						$result = pupe_query($query);

						if (mysql_num_rows($result) == 1) {
							$row = mysql_fetch_assoc($result);
							$rivitunnus = $row['tunnus'];
						}
					}

					if ($rivitunnus != 0) {
						$tilatapa = "PAIVITA";
						$myyntitilirivit = $var;
						require("laskuta_myyntitilirivi.inc");
					}
					else {
						echo "<font class='error'>".t("Tuotetta ei osattu valita")."</font><br>";
					}
				}
				else {

					$tila 	 = '';
					$valinta = '';

					//Detektoidaan tiedostomuoto
					if ($filetyyppi == ""
						and stripos($rivi[0], "tuote") !== FALSE
						and (stripos($rivi[1], "kpl") !== FALSE or stripos($rivi[1], "m‰‰r‰") !== FALSE)
						and stripos($rivi[2], "p‰iv‰") !== FALSE
						and stripos($rivi[3], "nimi") !== FALSE) {

						$filetyyppi = "KAKKONEN";

						$ind_Tuoteno 	= 0;
						$ind_Maara 		= 1;
						$ind_Arvioitu 	= 2;
						$ind_Hinta 		= 4;
						$ind_Ale 		= 5;
						$ind_Netto 		= 6;
						$ind_Var 		= 7;
						$ind_Kommentti	= 8;

						// Skipataan otsikot
						continue;
					}
					elseif ($filetyyppi == ""
						and stripos($rivi[0], "tuote") !== FALSE
						and (stripos($rivi[1], "kpl") !== FALSE or stripos($rivi[1], "m‰‰r‰") !== FALSE)
						and stripos($rivi[2], "p‰iv‰") !== FALSE
						and stripos($rivi[3], "hinta") !== FALSE) {

						$filetyyppi = "YKSPISTEVITONEN";

						$ind_Tuoteno 	= 0;
						$ind_Maara 		= 1;
						$ind_Arvioitu 	= 2;
						$ind_Hinta 		= 3;
						$ind_Ale 		= 4;
						$ind_Netto 		= 5;
						$ind_Var 		= 6;
						$ind_Kommentti	= 7;

						// Skipataan otsikot
						continue;
					}
					elseif ($filetyyppi == "") {

						$filetyyppi = "YKK÷NEN";

						$ind_Tuoteno 	= 0;
						$ind_Maara 		= 1;
						$ind_Arvioitu 	= 2;
						$ind_Hinta 		= 3;
						$ind_Ale 		= 4;
						$ind_Netto 		= 5;
						$ind_Var 		= 6;
						$ind_Kommentti	= 7;
					}

					$tuoteno = trim($rivi[$ind_Tuoteno]);
					$kpl	 = str_replace(",", ".", $rivi[$ind_Maara]);

					$toimaika			= "";
					$kerayspvm			= "";
					$hinta				= "";
					$netto				= "";
					$var				= "";
					$alv				= "";
					$paikka				= "";
					$varasto 			= $laskurow["varasto"];
					$rivitunnus			= "";
					$korvaavakielto		= "";
					$varataan_saldoa	= "";
					$perhekielto		= "";
					$jtkielto 		 	= $laskurow['jtkielto'];

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						${'ale'.$alepostfix} = "";
					}

					if ($kukarow["extranet"] == "") {
						$toimaika 	= $rivi[$ind_Arvioitu];
						$hinta		= str_replace(",", ".", $rivi[$ind_Hinta]);
						$ale1	  	= str_replace(",", ".", $rivi[$ind_Ale]);
						$netto 	  	= strtoupper(trim($rivi[$ind_Netto]));
						$var  		= strtoupper(trim($rivi[$ind_Var]));
						$kommentti	= trim($rivi[$ind_Kommentti]);

						if ($toim == "MYYNTITILI") {
							echo "KISSA";
						}
						else if (($var!='') and ($var!='P') and ($var!='H') and ($var!='J')) {
							echo t("V‰‰r‰ arvo kent‰ss‰ var! Sallitut arvot ovat: P, J ja H").". ".t("Rivill‰").": $rivilask1. '$var'<br>";
							$var = '';
						}

						if ($netto != '' and $netto != 'N') {
							echo t("V‰‰r‰ arvo kent‰ss‰ netto! Sallitut arvot ovat: tyhj‰ tai N").". ".t("Rivill‰").": $rivilask1. '$netto'<br>";
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

						$rarresult = pupe_query($query);

						if (mysql_num_rows($rarresult) == 1) {

							$trow = mysql_fetch_array($rarresult);

							$ohitus = 0;
							if ($kukarow["extranet"] != "") {
								$query = "SELECT * FROM asiakas where yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[oletus_asiakas]'";
								$asiakastempres = pupe_query($query);
								$asiakastemprow = mysql_fetch_array($asiakastempres);

								$temp_laskurowwi = array();
								$temp_laskurowwi['liitostunnus']	= $asiakastemprow['tunnus'];
								$temp_laskurowwi['ytunnus']			= $asiakastemprow['ytunnus'];
								$temp_laskurowwi['valkoodi']		= $asiakastemprow['valkoodi'];
								$temp_laskurowwi['maa']				= $asiakastemprow['maa'];


								$hinnat = alehinta($temp_laskurowwi, $trow, 1, '', '', '', "hintaperuste,aleperuste");

								$onko_asiakkaalla_alennuksia = FALSE;

								for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
									if (isset($hinnat["aleperuste"]["ale".$alepostfix]) and $hinnat["aleperuste"]["ale".$alepostfix] !== FALSE and $hinnat["aleperuste"]["ale".$alepostfix] < 13) {
										$onko_asiakkaalla_alennuksia = TRUE;
										break;
									}
								}

								// Jos tuote n‰ytet‰‰n vain jos asiakkaalla on asiakasalennus tai asiakahinta niin skipataan se jos alea tai hintaa ei lˆydy
								if ($trow["hinnastoon"] == "V" and (($hinnat["hintaperuste"] > 13 or $hinnat["hintaperuste"] === FALSE) and $onko_asiakkaalla_alennuksia === FALSE)) {
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

								// Ennakkotilaukset, Tarjoukset, Yll‰pitosopimukset ja Valmistukset eiv‰t tee saldotsekki‰
								if ($laskurow["tilaustyyppi"] == "E" or $laskurow["tilaustyyppi"] == "T" or $laskurow["tilaustyyppi"] == "0" or $laskurow["tila"] == "V") {
									$varataan_saldoa = "EI";
								}
								else {
									$varataan_saldoa = "";
								}

								//lis‰t‰‰n rivi
								if ($kukarow["extranet"] == "" and $tila == "MYYNTITILIRIVI") {
									$tilatapa = "PAIVITA";
									require("laskuta_myyntitilirivi.inc");
								}
								else {
									require ("lisaarivi.inc");
								}
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
			}

			$tuoteno	= '';
			$kpl		= '';
			$var		= '';
			$hinta		= '';
			$netto		= '';
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

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				${'ale'.$alepostfix} = '';
			}
		}
		else {
			$tuoteno	= '';
			$kpl		= '';
			$var		= '';
			$hinta		= '';
			$netto		= '';
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

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				${'ale'.$alepostfix} = '';
			}
		}
	}

	if ($tee == 'mikrotila') {
		echo "<font class='head'>$otsikko</font><hr>";

		echo "<form method='post' name='sendfile' enctype='multipart/form-data'>
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