<?php
	// $lisa = Monivalintalaatikosta tuleva muuttuja,
	// Muut sielt‰ olevat muuttujat ovat MUL_ alkuisia, esim mul_try, mul_osasto
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
		enable_jquery();
		echo " <script type='text/javascript'>
		   			$(document).ready(function() {
		    			$('input[name^=luvut]').keyup(function() {
		     				var id = $(this).attr('class');
						//	console.log('id '+id);
						//	console.log('val '+$(this).val());
						//	console.log('name '+$('input[id^='+id+'_]').attr('name'));
		     				$('input[id^='+id+'_]').val($(this).val());
		    			});
		   			});
		  		</script>";

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

		function tuotteenmyynti($tuoteno, $alkupvm) {
			// Tarvitaan vain tuoteno ja kuukauden ensimm‰inen p‰iv‰,
			// niin funkkari palauttaa sen kuun myynnin arvon.
			global $kukarow, $toim;

			$query = " 	SELECT round(sum(rivihinta),2) summa
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						WHERE yhtio	= '{$kukarow["yhtio"]}'
						AND tyyppi	= 'L'
						AND tuoteno	= '{$tuoteno}'
						AND laskutettuaika >= '{$alkupvm}'
						AND laskutettuaika <= last_day('{$alkupvm}')";
			$result = pupe_query($query);
			$rivi = mysql_fetch_assoc($result);

			if ($rivi["summa"] !=0 ) {
				return $rivi["summa"];
			}
			else {
				return 0.00;
			}

		}

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
				if ($headers[$lukualku] == "TYYPPI") {
					$apuva = "on";
					$lukualku = 2;
				}
				else {
					$lukualku = 2;
				}
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
					elseif ($apuva != "") {
						if (trim($data->sheets[0]['cells'][$excei][$excej]) == "ind") {
							// emme tee mit‰‰n
						}
						else {
							$budj_taulunrivit[$liitun][$kasiind][] = trim($data->sheets[0]['cells'][$excei][$excej]);
						}
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

			if (($budj_kohtelu == "euro" and $budj_taso == "summataso") or $summabudjetti=="on") {
				if ($alkukk =="" and $loppukk =="" and $tkausi !="") {
					$query = "SELECT * from tilikaudet where yhtio='{$kukarow["yhtio"]}' and tunnus = '{$tkausi}'";
					
					$result = pupe_query($query);
					$tilikausirivi = mysql_fetch_assoc($result);

					// uusi ja parempi
					$alkaakk	= substr($tilikausirivi["tilikausi_alku"],0,4).substr($tilikausirivi["tilikausi_alku"],5,2);
					$loppuukk	= substr($tilikausirivi["tilikausi_alku"],0,4).substr($tilikausirivi["tilikausi_loppu"],5,2);
					
					$sql = "SELECT PERIOD_DIFF('{$loppuukk}','{$alkaakk}')+1 as jakaja";
					$result = pupe_query($sql);
					$rivi = mysql_fetch_assoc($result);
					
					$jakaja = $rivi["jakaja"];
				}
				else {
					// uusi ja parempi
					if($alkukk <10) $alkukk ="0".$alkukk;
					if($loppukk <10) $loppukk ="0".$loppukk;
					
					$alkaakk	= $alkuvv.$alkukk;
					$loppuukk	= $loppuvv.$loppukk;
					
					$sql = "SELECT PERIOD_DIFF('{$loppuukk}','{$alkaakk}')+1 as jakaja";
					query_dump($sql);
					
					$result = pupe_query($sql);
					$rivi = mysql_fetch_assoc($result);
					
					$jakaja = $rivi["jakaja"];
				}
			}
			
			// Tiedet‰‰n ett‰ tulee poikkeus ja sen arvo on "totta"
			// Normaalisti $luvut[] ensimm‰isess‰ solussa tulee tuotteen tuoteno, nyt sielt‰ tulee TRY numero.
			// Haetaan kaikki sen tuoteryhm‰n tuotteet ja jollain vitun taikatempulla vedet‰‰n arrayksi ja t‰st‰ eteenp‰in kauniina paskana.
			// otetaan alkuper‰inen $luvut talteen.
			$backup_luvut = $luvut;
			
			if ($poikkeus == "totta") {
				
				unset($luvut); // poistetaan ja alustetaan.
				
				// Normaali tapaus miss‰ on TRY tai OSASTO
				if ($poikkeus_haku == "try" or $poikkeus_haku == "osasto") {
					foreach ($backup_luvut as $litunnus => $rivit) {
					
						$query = "	SELECT DISTINCT tuote.tuoteno, tuote.nimitys, tuote.try, tuote.osasto 
									FROM tuote 
									WHERE tuote.yhtio = 'mast' 
									AND tuote.status != 'P' 
									and tuote.{$poikkeus_haku} = '$litunnus' ";
						$result = pupe_query($query);
					
						while ($tuoterivi = mysql_fetch_assoc($result)) {
							foreach ($rivit as $kausi => $arvo) {
								$luvut[$tuoterivi["tuoteno"]][$kausi][0] = $arvo[0];
							}
						}
					}
				}
				elseif ($poikkeus_haku == "kummatkin") {
						foreach ($backup_luvut as $litunnus => $rivit) {
							
							$palaset = explode(",",$litunnus);
							$osasto_tunnus 	= trim($palaset[0]);
							$try_tunnus		= trim($palaset[1]);
							
							$query = "	SELECT DISTINCT tuote.tuoteno, tuote.nimitys, tuote.try, tuote.osasto 
										FROM tuote 
										WHERE tuote.yhtio	= 'mast' 
										AND tuote.status	!= 'P' 
										and tuote.osasto	= '$osasto_tunnus'
										and tuote.try 		= '$try_tunnus'";
							$result = pupe_query($query);

							while ($tuoterivi = mysql_fetch_assoc($result)) {
								foreach ($rivit as $kausi => $arvo) {
									$luvut[$tuoterivi["tuoteno"]][$kausi][0] = $arvo[0];
								}
							}
						}
				}
			}
			

			$paiv  = 0;
			$lisaa = 0;

			// t‰t‰ k‰ytet‰‰n kun laitetaan koko osastolle/try:lle yksi summa joka jaetaan per tuote per kk
			$tuotteiden_lukumaara = count($luvut);

			foreach ($luvut as $liitostunnus => $rivi) {
				foreach ($rivi as $kausi => $solut) {
					foreach ($solut as $try => $solu) {

						$solu = str_replace(",", ".", $solu);
						if ($try == 0) $try = "";

						if ($solu == '!' or $solu = (float) $solu or $summabudjetti == "on") {

							if ($solu == '!') $solu = 0;

							$solu = (float) $solu;

							if ($summabudjetti > 1.00 and $solu > 0.00) {
								$solu = 0.00;
							}

							$query = "	SELECT summa
										FROM $budj_taulu
										WHERE yhtio 			= '$kukarow[yhtio]'
										AND $budj_sarak		 	= '$liitostunnus'
										AND kausi 				= '$kausi'
										AND dyna_puu_tunnus 	= ''
										AND osasto 				= ''
										AND try 				= '$try'";
							$result = pupe_query($query);

							if (mysql_num_rows($result) > 0) {

								$budjrow = mysql_fetch_assoc($result);

								if ($budjrow['summa'] != $solu) {
									$tall_index = 1.00;

									if ($budj_kohtelu == "euro" and $budj_taso == "summataso" and $summabudjetti == "on") {
										$jaettava = $solu;
										$flipmuuttuja = $jaettava/($jakaja*$tuotteiden_lukumaara);
										$solu = round($flipmuuttuja,2);
									}

									if ($budj_kohtelu == "euro" and $budj_taso == "summataso") {
										$jaettava = $solu;
										$flipmuuttuja = $jaettava/$jakaja;
										$solu = round($flipmuuttuja,2);
									}

									
									if ($budj_kohtelu == "isoi" and $solu > 0.00) {

										$edvuosi = substr($kausi,0,4)-1;
										$haettavankkpvm = $edvuosi.'-'.substr($kausi,4,2).'-01';
										$myyntihistoriassa = tuotteenmyynti($liitostunnus, $haettavankkpvm);
										if ($myyntihistoriassa == 0.00) {
											$tall_index = 0;
										}
										else {
											$tall_index = $solu;
										}
										$solu = $myyntihistoriassa * $solu;
									}

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
													indeksi = $tall_index,
													muuttaja = '$kukarow[kuka]',
													muutospvm = now()
													WHERE yhtio 			= '$kukarow[yhtio]'
													AND $budj_sarak		 	= '$liitostunnus'
													AND kausi 				= '$kausi'
													AND dyna_puu_tunnus 	= ''
													AND osasto 				= ''
													AND try 				= '$try'";
									}
									$result = pupe_query($query);
									$paiv++;
								}
							}
							else {

								$tall_index = 1;

								if ($budj_kohtelu == "euro" and $budj_taso == "summataso" and $summabudjetti == "on") {
									$jaettava = $solu;
									$flipmuuttuja = $jaettava/($jakaja*$tuotteiden_lukumaara);
									$solu = round($flipmuuttuja,2);
								}

								if ($budj_kohtelu == "euro" and $budj_taso == "summataso") {
									$jaettava = $solu;
									$flipmuuttuja = $jaettava/$jakaja;
									$solu = round($flipmuuttuja,2);
								}
								

								if ($budj_kohtelu == "isoi" and $solu > 0.00) {
									// alter table budjetti_tuote add column indeksi decimal(5,2) NOT NULL default 0.00 after summa;

									$edvuosi = substr($kausi,0,4)-1;
									$haettavankkpvm = $edvuosi.'-'.substr($kausi,4,2).'-01';
									$myyntihistoriassa = tuotteenmyynti($liitostunnus, $haettavankkpvm);
									if ($myyntihistoriassa == 0.00) {
										$tall_index = 0;
									}
									else {
										$tall_index = $solu;
									}

									$solu = $myyntihistoriassa * $solu;
								}

								if ($solu != 0.00 and $tall_index > 0) {
									$query = "	INSERT INTO $budj_taulu SET
												summa 				= $solu,
												yhtio 				= '$kukarow[yhtio]',
												kausi 				= '$kausi',
												$budj_sarak		 	= '$liitostunnus',
												osasto 				= '',
												try 				= '$try',
												dyna_puu_tunnus 	= '',
												indeksi				= '$tall_index',
												laatija 			= '$kukarow[kuka]',
												luontiaika 			= now(),
												muutospvm 			= now(),
												muuttaja 			= '$kukarow[kuka]'";
									$result = pupe_query($query);
									$lisaa++;
								}
								elseif ($toim == "TUOTE" and $budj_kohtelu == "isoi") {
									echo "<p class='error'>Virhe: tuotteella $liitostunnus ei lˆytynyt myynti‰ ".substr($kausi,4,2)."/".$edvuosi."</p>";
								}
								else {
									echo "<p class='error'>Virhe: Tˆrm‰ttiin virheeseen ja emme tallentaneet tiedoa {$budj_taulu}-tauluun</p>";
								}
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
		$vresult = pupe_query($query);

		echo "<tr><th>",t("Tilikausi"),"</th><td><select name='tkausi'>";

		while ($vrow = mysql_fetch_assoc($vresult)) {
			$sel = $tkausi == $vrow['tunnus'] ? ' selected' : '';
			echo "<option value = '$vrow[tunnus]'$sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"])."</option>";
		}

		echo "</select></td></tr>";

		if ($toim == "TUOTE") {
			
			// Budjetin aikav‰li
			// Ei ole relevanttia syˆtt‰‰ p‰iv‰m‰‰r‰‰ p‰iv‰ntarkkuudella kun budjetti on kk tasoa. 
			// Laitetaan hiddeniin arvo 1 niin p‰iv‰m‰‰r‰ checkist‰ p‰‰st‰‰n siististi l‰pi.
			echo "<tr>";
			echo "<th>".t("Anna budjetin aikav‰li (kk-vuosi)")."</th>";
			echo "	<td>
					<input type='hidden' name='alkupp' value='1' size='1'>
					<input type='text' name='alkukk' value='$alkukk' size='1'>-
					<input type='text' name='alkuvv' value='$alkuvv' size='3'>
					&nbsp;
					<input type='hidden' name='loppupp' value='1' size='1'>
					<input type='text' name='loppukk' value='$loppukk' size='1'>-
					<input type='text' name='loppuvv' value='$loppuvv' size='3'>
					</td>";
			echo "</tr>";

			// indeksi vai eurom‰‰r‰
			echo "<tr>";
			echo "<th>".t("Anna budjetin k‰sittelytyyppi")."</th>";
			echo "<td>";

			if ($budj_kohtelu == "isoi") {
				$bkcheck = "SELECTED";
			}
			else {
				$bkcheck = "";
			}

			echo "<select name='budj_kohtelu' onchange='submit()';>";
			echo "<option value = 'euro'>".t("Eurom‰‰r‰inen")."</option>";
			echo "<option value = 'isoi' $bkcheck>".t("Indeksi kohtelu")."</option>";
			echo "</td>";
			echo "</tr>";

			// Kuukausittain vai samatasokk
			echo "<tr>";
			echo "<th>".t("Budjettiluku")."</th>";
			echo "<td>";

			if ($budj_taso == "samatasokk") {
				$btcheck1 = "SELECTED";
			}
			elseif ($budj_taso == "summataso") {
				$btcheck2 = "SELECTED";
			}
			else {
				$btcheck1 = $btcheck2 = "";
			}

			echo "<select name='budj_taso' onchange='submit()';>";
			echo "<option value = ''>".t("Kuukausittain aikav‰lill‰")."</option>";
			echo "<option value = 'samatasokk' $btcheck1>".t("Jokaiselle kuukaudelle sama arvo")."</option>";
			echo "<option value = 'summataso' $btcheck2>".t("Summa jaetaan kuukausille tasan")."</option>";
			echo "</td>";
			echo "</tr>";

			// Tuoteosasto tai ryhm‰tason budjetti.

			echo "<tr>";
			echo "<th>".t("Anna kokonaisbudjetti osastolle tai tuoteryhm‰lle")."</th>";
			if ($summabudjetti) $scheck = 'CHECKED';
			echo "<td><input type='checkbox' name='summabudjetti' onchange='submit()'; $scheck></td>";
		}

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

				echo "<tr>";
				echo "<th>".t("N‰yt‰ vain ne tuotteet joilla ei ole budjettia")."</th>";
				if ($tuotteetilmanbudjettia) $tcheck = 'CHECKED';
				echo "<td><input type='checkbox' name='tuotteetilmanbudjettia' $tcheck></td>";
			}
			elseif ($toim == "TOIMITTAJA") {
				echo "<tr><th>",t("Valitse toimittaja"),"</th>";

				if ($toimittajaid > 0) {
					$query = "	SELECT *
								from toimi
								where yhtio = '$kukarow[yhtio]'
								and tunnus = '$toimittajaid'";
					$result = pupe_query($query);
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
					$result = pupe_query($query);
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
		
		if (count($mul_osasto) >1 and count($mul_try) >=1) {
			echo "<p class='error'>Virhe!! Et voi valita useita osastoja ja tuoteryhmi‰ kerrallaan</p>";
			die();
		}

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

			if (checkdate($alkukk, $alkupp, $alkuvv) and checkdate($loppukk, $loppupp, $loppuvv)) {

				$tilikaudetrow["tilikausi_alku"]	= $alkuvv.'-'.sprintf('%02.2s',$alkukk).'-'.sprintf('%02.2s',$alkupp);
				$tilikaudetrow["tilikausi_loppu"]	= $loppuvv.'-'.sprintf('%02.2s',$loppukk).'-'.sprintf('%02.2s',$loppupp);
				
			}
			else {
				$vresult = pupe_query($query);
				if (mysql_num_rows($vresult) == 1) $tilikaudetrow = mysql_fetch_array($vresult);
			}

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

			// Ilmoitetaan virheest‰ mik‰li on v‰‰ri‰ valintoja
			if ($budj_taso == "" and $summabudjetti == "on") {
				echo "<p class='error'>".t("Virhe!! Ei voida valita kokonaisbudjettia sek‰ jakoa kuukausittain!! Valitse Budjettiluvusta toinen vaihtoehto")."</p>";
				exit;
			}

			if ($budj_kohtelu == "isoi" and $budj_taso == "summataso" and $summabudjetti == "on") {
				echo "<p class='error'>".t("Virhe!! Ei voida valita indeksi‰ kokonaisbudjetille joka jaetaan per kk. Valitse Budjettiluvusta Sama luku joka kuukaudelle")."</p>";
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
			
			if ($summabudjetti) {
				
				$selectlisa = " , avainsana.selitetark";

				if (strpos($lisa,'tuote.osasto') != 0 and strpos($lisa, 'tuote.try') === FALSE) {
					$grouppaus = " group by tuote.osasto";
					$haen = "osasto";
					$passaan = "yksi";	
				}
				elseif (strpos($lisa,'tuote.osasto') != 0 and strpos($lisa, 'tuote.try') != 0) {
					$grouppaus = " group by tuote.try";	
					$haen = "try";
					$passaan = "kaksi";
				}
				else {
					$grouppaus = " group by tuote.try";	
					$haen = "try";
					$passaan = "yksi";
				}
				$joinlisa = " JOIN avainsana on (tuote.yhtio = avainsana.yhtio and tuote.{$haen} = avainsana.selite and avainsana.laji='{$haen}' and avainsana.kieli='{$yhtiorow["kieli"]}') ";
				
			}
	
			if ($toim == "TUOTE") {
				$query = "	SELECT DISTINCT tuote.tuoteno, tuote.nimitys, tuote.try, tuote.osasto $selectlisa
							FROM tuote
							$lisa_parametri
							{$lisa_dynaaminen["tuote"]}
							{$joinlisa}
							WHERE tuote.yhtio = '{$kukarow['yhtio']}'
							AND tuote.status != 'P'
							$lisa
							{$grouppaus}";
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

			$result = pupe_query($query);

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
				if ($grouppaus) {
					if ($haen == "try") {
						if ($rivimaara < $maxrivimaara) echo "<tr><th>",t("Tuoteryhm‰"),"</th>";
					}
					else {
						if ($rivimaara < $maxrivimaara) echo "<tr><th>",t("Tuoteosasto"),"</th>";
					}
				}
				else {
					if ($rivimaara < $maxrivimaara) echo "<tr><th>",t("Tuote"),"</th>";
				}	
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
			$alkuraja	= substr($tilikaudetrow['tilikausi_alku'], 0, 4)."-".substr($tilikaudetrow['tilikausi_alku'], 5, 2);
			$rajataulu 	= array();
			$sarakkeet	= 0;


			while ($raja < substr($tilikaudetrow['tilikausi_loppu'], 0, 7)) {

				$vuosi 	= substr($tilikaudetrow['tilikausi_alku'], 0, 4);
				$kk 	= substr($tilikaudetrow['tilikausi_alku'], 5, 2);
				$kk 	+= $sarakkeet;

				if ($kk > 12) {
					$vuosi++;
					$kk -= 12;			
				}

				if ($kk < 10) $kk = '0'.$kk;

				$rajataulu[$sarakkeet] = $vuosi.$kk;
				$sarakkeet++;

				$raja = $vuosi."-".$kk;
				$aikavalinkaudet[] = $vuosi."".$kk;

				if (($budj_taso == "samatasokk" or $budj_taso == "summataso") and $sarakkeet == 1) {
					$vuosi2	= substr($tilikaudetrow['tilikausi_loppu'], 0, 4);
					$kk2	= substr($tilikaudetrow['tilikausi_loppu'], 5, 2);
					echo "<th>".$raja." / ".$vuosi2 ."-".$kk2."</th>";
				}
				elseif ($budj_taso == "samatasokk" or $budj_taso == "summataso") {
					// en piirr‰ turhaan otsikkoja
				}
				else {
			 		if ($rivimaara < $maxrivimaara) echo "<th>$raja</th>";
				}
				$worksheet->write($excelrivi, $excelsarake, $raja, $format_bold);
				$excelsarake++;
				
				if ($sarakkeet > 24) {
					echo "<p class='error'>VIRHE !!!! ET voi tehd‰ yli 2 vuoden budjettia !!!<br></p>";
					die();
				}
			}

			if ($rivimaara < $maxrivimaara) echo "</tr>";

			$excelrivi++;
			$xx = 0;

			// t‰t‰ tarvitaan ohituksessa ett‰ menee oikein.
			$ssarakkeet = $sarakkeet;

			function piirra_budj_rivi ($row, $tryrow = "",$ohitus="", $org_sar="") {
				global $kukarow, $toim, $worksheet, $excelrivi, $budj_taulu, $rajataulu, $budj_taulunrivit, $xx, $budj_sarak, $sarakkeet, $rivimaara, $maxrivimaara, $grouppaus, $haen, $passaan;

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
						
						if ($grouppaus != "") {
								if ($rivimaara < $maxrivimaara) echo "<tr><td>$row[selitetark]</td>";
						}
						else {
								if ($rivimaara < $maxrivimaara) echo "<tr><td>$row[tuoteno] $row[nimitys]</td>";
						}
					
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

				if ($ohitus != "") {
					$sarakkeet = 1;
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

						$xresult = pupe_query($query);
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
					elseif ($ohitus != "") {
						if ($grouppaus != "") {
							if ($haen == "try" and $passaan == "yksi"){
								echo "<input type='text' class = '{$row[$budj_sarak]}' name = 'luvut[{$row["try"]}][{$ik}][]' value='{$nro}' size='8'>";
								echo "<input type='hidden' name = 'poikkeus' value='totta'>";
								echo "<input type='hidden' name = 'poikkeus_haku' value='try'>";
							}
							elseif ($haen == "osasto" and $passaan == "yksi") {
								echo "<input type='text' class = '{$row[$budj_sarak]}' name = 'luvut[{$row["osasto"]}][{$ik}][]' value='{$nro}' size='8'>";
								echo "<input type='hidden' name = 'poikkeus' value='totta'>";
								echo "<input type='hidden' name = 'poikkeus_haku' value='osasto'>";
							}
							elseif ($haen == "try" and $passaan == "kaksi") {
								echo "<input type='text' class = '{$row[$budj_sarak]}' name = 'luvut[{$row["osasto"]},{$row["try"]}][{$ik}][]' value='{$nro}' size='8'>";
								echo "<input type='hidden' name = 'poikkeus' value='totta'>";
								echo "<input type='hidden' name = 'poikkeus_haku' value='kummatkin'>";
							}
						}
						else {
							echo "<input type='text' class = '{$row[$budj_sarak]}' name = 'luvut[{$row[$budj_sarak]}][{$ik}][]' value='{$nro}' size='8'>";
						}
						
						for ($a = 1; $a < $org_sar; $a++) {
							$ik = $rajataulu[$a];
							if ($grouppaus != "") {
								if ($haen == "try" and $passaan == "yksi"){
									echo "<input type='hidden' id = '{$row[$budj_sarak]}_{$ik}' name = 'luvut[{$row["try"]}][{$ik}][]' value='{$nro}' size='8'>";
								}
								elseif ($haen == "osasto" and $passaan == "yksi") {
									echo "<input type='hidden' id = '{$row[$budj_sarak]}_{$ik}' name = 'luvut[{$row["osasto"]}][{$ik}][]' value='{$nro}' size='8'>";
								}
								elseif ($haen == "try" and $passaan == "kaksi") {
									echo "<input type='hidden' id = '{$row[$budj_sarak]}_{$ik}' name = 'luvut[{$row["osasto"]},{$row["try"]}][{$ik}][]' value='{$nro}' size='8'>";
								}
							}
							else {
								echo "<input type='hidden' id = '{$row[$budj_sarak]}_{$ik}' name = 'luvut[{$row[$budj_sarak]}][{$ik}][]' value='{$nro}' size='8'>";
							}
						}
						
						echo "</td>";
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
			elseif ($budj_taso == "samatasokk" or $budj_taso == "summataso") {

				while ($row = mysql_fetch_assoc($result)) {
					piirra_budj_rivi($row,'','OHITA',$ssarakkeet);
				}
			}
			elseif ($tuotteetilmanbudjettia) {
				$bquery = "SELECT distinct tuoteno, kausi from budjetti_tuote where yhtio='$kukarow[yhtio]'";
				$bres = pupe_query($bquery);

				while ($brivi = mysql_fetch_assoc($bres)) {
					$bud[$brivi["tuoteno"]][$brivi["kausi"]] = $brivi["kausi"];
				}

				while ($row = mysql_fetch_assoc($result)) {

					if (!isset($bud[$row['tuoteno']])) {
						piirra_budj_rivi($row);
					}
					elseif (isset($bud[$row['tuoteno']])) {
						foreach ($aikavalinkaudet as $aikavali) {
							if ($bud[$row['tuoteno']][$aikavali] == "") {
								piirra_budj_rivi($row);
								break;
							}
						}
					}

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