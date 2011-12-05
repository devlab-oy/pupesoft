<?php

	// Tiedoston tallennusta varten
	$lataa_tiedosto = (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == "lataa_tiedosto") ? 1 : 0;
	$_REQUEST["kaunisnimi"] = isset($_REQUEST["kaunisnimi"]) ? str_replace("/", "", $_REQUEST["kaunisnimi"]) : "";

	require ("inc/parametrit.inc");

	// Käytettävät muuttujat
	$alkukk = isset($alkukk) ? trim($alkukk) : "";
	$alkuvv = isset($alkuvv) ? trim($alkuvv) : "";
	$budj_kohtelu = isset($budj_kohtelu) ? trim($budj_kohtelu) : "";
	$budj_taso = isset($budj_taso) ? trim($budj_taso) : "";
	$loppukk = isset($loppukk) ? trim($loppukk) : "";
	$loppuvv = isset($loppuvv) ? trim($loppuvv) : "";
	$muutparametrit = isset($muutparametrit) ? trim($muutparametrit) : "";
	$onko_ilman_budjettia = isset($onko_ilman_budjettia) ? trim($onko_ilman_budjettia) : "";
	$submit_button = isset($submit_button) ? trim($submit_button) : "";
	$summabudjetti = isset($summabudjetti) ? trim($summabudjetti) : "";
	$tee = isset($tee) ? trim($tee) : "";
	$tuoteno = isset($tuoteno) ? mysql_real_escape_string(trim($tuoteno)) : "";
	$tuoteryhmittain = isset($tuoteryhmittain) ? trim($tuoteryhmittain) : "";
	$lisa = isset($lisa) ? trim($lisa) : "";
	$lisa_parametri = isset($lisa_parametri) ? trim($lisa_parametri) : "";
	$lisa_dynaaminen = isset($lisa_dynaaminen) ? $lisa_dynaaminen : array("tuote" => "", "asiakas" => "");
	$ytunnus = isset($ytunnus) ? trim($ytunnus) : "";
	$asiakasid = isset($asiakasid) ? (int) $asiakasid : 0;
	$toimittajaid = isset($toimittajaid) ? (int) $toimittajaid : 0;

	$maxrivimaara = 64000;

	// Tiedoston tallennusta varten
	if ($tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	// Tämä funkkari palauttaa syötetyn tuotteen sen kuukauden myynnin arvon ja kpl-määrän
	function tuotteenmyynti($tuoteno, $alkupvm) {

		// Tarvitaan vain tuoteno ja kuukauden ensimmäinen päivä
		global $kukarow;

		$query = " 	SELECT ifnull(round(sum(if(tyyppi='L', rivihinta, 0)), 2), 0) summa,
					ifnull(round(sum(kpl), 0), 0) maara
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
					WHERE yhtio	= '{$kukarow["yhtio"]}'
					AND tyyppi in ('L','V')
					AND tuoteno	= '{$tuoteno}'
					AND laskutettuaika >= '{$alkupvm}'
					AND laskutettuaika <= last_day('{$alkupvm}')";
		$result = pupe_query($query);
		$rivi = mysql_fetch_assoc($result);

		return array($rivi["summa"], $rivi["maara"]);
	}

	// funkkarin $org_sar passataan alkuperäiset sarakkeet, jota taas käytetään "ohituksessa"
	function piirra_budj_rivi ($row, $tryrow = "", $ohitus = "", $org_sar = "") {
		global $kukarow, $toim, $worksheet, $excelrivi, $budj_taulu, $rajataulu, $budj_taulunrivit, $xx, $budj_sarak, $sarakkeet, $rivimaara, $maxrivimaara, $grouppaus, $haen, $passaan, $budj_kohtelu;

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
							WHERE yhtio			= '$kukarow[yhtio]'
							and kausi		 	= '$ik'
							and $budj_sarak		= '$row[$budj_sarak]'
							and dyna_puu_tunnus	= ''
							and osasto			= ''
							and try				= '$try'";
				$xresult = pupe_query($query);
				$nro = '';

				if (mysql_num_rows($xresult) == 1) {
					$brow = mysql_fetch_assoc($xresult);
					$nro = $brow['summa'];

					// normaalisti näytetään summaa, mutta tuotteella, mikäli käsitellään määrää niin näytetään määrä tai indeksissä indeksi
					if ($budj_kohtelu == "maara" and $toim == "TUOTE") {
						$nro = $brow['maara'];
					}
					if ($budj_kohtelu == "isoi" and $toim == "TUOTE") {
						$nro = $brow['indeksi'];
					}
				}
			}

			if ($rivimaara < $maxrivimaara) echo "<td>";

			if (is_array($tryrow)) {
				if ($rivimaara < $maxrivimaara) echo "<input type='text' name = 'luvut[{$row[$budj_sarak]}][{$ik}][{$tryrow["selite"]}]' value='{$nro}' size='8'></td>";
			}
			elseif ($ohitus != "") {
				// mikäli muutat "poikkeus" tai "poikkeus_haku" nimityksiä tai arvoja tai lisäät haaroja, niin muuta/lisää ne myös kohtaan riville 258
				// jossa käsitellään ne arrayn luonnissa
				// ÄLÄ MUUTA "<input type='text' class = '{$row[$budj_sarak]}'"
				// Mikäli teet muutoksia niin muuta myös jqueryyn riville noin 17

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

	// Tässä katsotaan mitä syötetään ja kopioidaan kaikkiin samannimisiin ja ID:llä varustettuihin hidden syöttökenttiin samat arvot.
	// Käytetään kokonaisbudjetissa, samasumma per kk / summa jaetaan per kk
	// Mikäli teet muutoksia niin muuta myös riveille alkaen 1170 !!!
	echo "<script src='{$palvelin2}/inc/jquery.min.js'></script>";
	echo "<script type='text/javascript'>";
	echo "$(document).ready(function() {";
	echo " $('input[name^=luvut]').keyup(function() {";
	echo "  var id = $(this).attr('class');";
	echo "  $('input[id^='+id+'_]').val($(this).val());";
	echo " });";
	echo "});";
	echo "</script>";

	// Ohjelman headerit
	if ($toim == "TUOTE") {
		echo "<font class='head'>".t("Budjetin ylläpito tuote")."</font><hr>";

		$budj_taulu = "budjetti_tuote";
		$budj_sarak = "tuoteno";
	}
	elseif ($toim == "TOIMITTAJA") {
		echo "<font class='head'>".t("Budjetin ylläpito toimittaja")."</font><hr>";

		$budj_taulu = "budjetti_toimittaja";
		$budj_sarak = "toimittajan_tunnus";
	}
	elseif ($toim == "ASIAKAS") {
		echo "<font class='head'>".t("Budjetin ylläpito asiakas")."</font><hr>";

		$budj_taulu = "budjetti_asiakas";
		$budj_sarak = "asiakkaan_tunnus";
	}
	else {
		echo "<font class='error'>Anna ohjelmalle alanimi: TUOTE, TOIMITTAJA tai ASIAKAS.</font>";
		exit;
	}

	// Ollaan uploadttu Excel
	if (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

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

		echo "<font class='message'>".t("Tarkastetaan lähetetty tiedosto")."...<br></font>";

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

		// Huomaa nämä jos muutat excel-failin sarakkeita!!!!
		if ($toim == "TUOTE") {
			$lukualku = 2;
		}
		elseif ($toim == "TOIMITTAJA") {
			$lukualku = 3;
		}
		elseif ($toim == "ASIAKAS") {
			$lukualku = 4;
		}

		if ($headers[$lukualku] == "Tuoteryhmä") {
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
			// Viedään suoraan kantaan
			$luvut = $budj_taulunrivit;
			$submit_button = "OK";

			echo "<font class='error'>".t("HUOM: Maksimirivimäärä ylittyi, rivejä ei näytetä ruudulla. Rivit tallennetaan suoraan tietokantaan")."!<br><br></font>";
		}
		else {
			echo "<font class='error'>".t("HUOM: Excel-tiedoston luvut eivät vielä tallennettu tietokantaan")."!<br>".t("Klikkaa")." '",t("Näytä/Tallenna"),"' ".t("tallentaaksesi luvut")."!<br></font>".t("Tiedosto ok")."!<br><br></font>";
		}

		$liitostunnukset = substr($liitostunnukset, 0, -1);
	}

	// Lisätään/Päivitetään budjetti, tarkastukset
	if ($tee == "TALLENNA_BUDJETTI_TARKISTA") {

		// Oletetaan, että ei virheitä
		$tee = "TALLENNA_BUDJETTI";

		if (!isset($luvut) or count($luvut) == 0) {
			echo "<font class='error'>".t("Et syöttänyt yhtään lukua")."!<br><br></font>";
			$tee = "";
		}

		if (isset($poikkeus_haku) and $poikkeus_haku != "try" and $poikkeus_haku != "osasto" and $poikkeus_haku != "kummatkin") {
			echo "<font class='error'>".t("Virhe: Budjetinluonnissa on tapahtunut vakava käsittelyvirhe, keskeytetään prosessi")."!<br><br></font>";
			$tee = "";
		}

	}

	// Lisätään/Päivitetään budjetti
	if ($tee == "TALLENNA_BUDJETTI") {
		// Seuraavassa käsitellään tilikautta josta erotuksesta saadaan jakaja.
		// muuttujaa '$jakaja' käytetään sekä "update" että "insert" puolella.
		// $jakaja on kuukausien lukumäärä

		if ((($budj_kohtelu == "euro" or $budj_kohtelu == "maara") and $budj_taso == "summataso") or $summabudjetti == "on") {

			$alkaakk = substr($kausi_alku, 0, 4).substr($kausi_alku, 5, 2);
			$loppuukk = substr($kausi_loppu, 0, 4).substr($kausi_loppu, 5, 2);

			$sql = "SELECT PERIOD_DIFF('{$loppuukk}','{$alkaakk}')+1 as jakaja";
			$result = pupe_query($sql);
			$rivi = mysql_fetch_assoc($result);

			$jakaja = $rivi["jakaja"];
		}

		// muuttuja "poikkeus" tulee aina kun ollaan valittu 'Anna Kokonaisbudjetti Osastolle Tai Tuoteryhmälle'
		// Normaalisti $luvut[] ensimmäisessä solussa tulee tuotteen tuoteno, nyt sieltä tulee TRY numero.
		// Haetaan kaikki sen tuoteryhmän tuotteet ja tehdään arrayksi.

		// otetaan $luvut talteen ja prosessoidaan ne "poikkeus-haarassa"
		$muunto_luvut = $luvut;

		if (isset($poikkeus) and $poikkeus == "totta") {

			unset($luvut); // poistetaan ja alustetaan sekä rakennetaan uudestaan.

			// Normaali tapaus missä on TRY tai OSASTO
			if ($poikkeus_haku == "try" or $poikkeus_haku == "osasto") {
				foreach ($muunto_luvut as $litunnus => $rivit) {

					$query = "	SELECT DISTINCT tuote.tuoteno, tuote.nimitys, tuote.try, tuote.osasto
								FROM tuote
								WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
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

			// "kummatkin" tarkoittaa että tulee sekä TRY ja OSASTO
			if ($poikkeus_haku == "kummatkin") {
				foreach ($muunto_luvut as $litunnus => $rivit) {

					$palaset = explode(",",$litunnus);
					$osasto_tunnus 	= trim($palaset[0]);
					$try_tunnus		= trim($palaset[1]);

					$query = "	SELECT DISTINCT tuote.tuoteno, tuote.nimitys, tuote.try, tuote.osasto
								FROM tuote
								WHERE tuote.yhtio	= '{$kukarow["yhtio"]}'
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
		$pois  = 0;
		$lisaa = 0;

		// tätä käytetään kun laitetaan koko osastolle/try:lle yksi summa joka jaetaan per tuote per kk
		$tuotteiden_lukumaara = count($luvut);

		foreach ($luvut as $liitostunnus => $rivi) {

			foreach ($rivi as $kausi => $solut) {

				foreach ($solut as $try => $solu) {

					$solu = str_replace(",", ".", trim($solu));
					if ($try == 0) $try = "";

					if ($solu == '!' or is_numeric($solu) or $summabudjetti == "on") {

						$update_vai_insert = "";

						// Huutomerkillä poistetaan budjetti
						if ($solu == "!") {
							$update_vai_insert = "DELETE";
						}
						else {
							// Katsotaan löytyykö näillä tiedoilla jo budjetti
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
									// Löytyy budjetti, mutta se on eri -> päivitetään
									$update_vai_insert = "UPDATE";
								}
								else {
									// Löytyy budjetti, mutta se on sama -> ei tehdä mitään
									continue;
								}
							}
							else {
								// Ei löydy budjettia -> lisätään
								$update_vai_insert = "INSERT";
							}
						}

						// $solu on summa
						// $tall_index on kantaan tallennettava indek-luku, oletus on 1
						// $tall_maara on kantaan tallennettava kappalemäärä tuotteita
						$solu = (float) $solu;
						$tall_index = 1.00;
						$tall_maara = 0;

						// Jokainen kombinaatio pitää laittaa erikseen, tai tulee virhe-ilmoitus.
						// Tämä on tärkeä tehdä näin, niin voidaan ylläpitää tulevaisuudessa erilaisia kombinaatioita paremmin.

						if ($budj_kohtelu == "euro" and $budj_taso == "summataso" and $summabudjetti == "on") {
							$jaettava = $solu;
							$flipmuuttuja = $jaettava/($jakaja*$tuotteiden_lukumaara);
							$solu = round($flipmuuttuja,2);
						}
						elseif ($budj_kohtelu == "euro" and $budj_taso == "") {
							//
						}
						elseif ($budj_kohtelu == "maara" and $budj_taso == "samatasokk" and $summabudjetti == "on") {
							//
						}
						elseif ($budj_kohtelu == "maara" and $budj_taso == "summataso" and $summabudjetti == "on") {
							$jaettava = $solu;
							$flipmuuttuja = $jaettava/($jakaja*$tuotteiden_lukumaara);
							$tall_maara = round($flipmuuttuja,0);
							$solu = 0;
						}
						elseif ($budj_kohtelu == "maara" and $budj_taso == "") {
							// flipataan solu määräksi ja solu tyhjäksi.
							$tall_maara = $solu;
							$solu = 0.00;
						}
						elseif ($budj_kohtelu == "maara" and $budj_taso == "samatasokk") {
							// flipataan solu määräksi ja solu tyhjäksi.
							$tall_maara = $solu;
							$solu = 0.00;
						}
						elseif ($budj_kohtelu == "euro" and $budj_taso == "samatasokk") {
							// $solu on solu, muut tyhjää
							$tall_maara = "";
							$tall_index = "";
						}
						elseif ($budj_kohtelu == "euro"  and $budj_taso == "summataso") {
							$jaettava = $solu;
							$flipmuuttuja = $jaettava/$jakaja;
							$solu = round($flipmuuttuja,2);
						}
						elseif ($budj_kohtelu == "isoi" and $solu > 0.00) {

							$edvuosi = substr($kausi, 0, 4)-1;
							$haettavankkpvm = $edvuosi.'-'.substr($kausi, 4, 2).'-01';
							
							list($myyntihistoriassa, $maarahistoriassa) = tuotteenmyynti($liitostunnus, $haettavankkpvm);

							if ($myyntihistoriassa == 0.00) {
								$tall_index = 0;
								$tall_maara = 0;
							}
							else {
								$tall_index = $solu;
							}
							$tall_maara = $maarahistoriassa * $solu;
							$solu = $myyntihistoriassa * $solu;
						}
						elseif ($solu == 0) {
							// poistohaara
						}
						elseif ($toim == "TOIMITTAJA" or $toim == "ASIAKAS") {
							// perushaara toimittaja ja asiakasbudjetille, ei tehdä mitään. 
						}
						else {
							echo "<font class='error'>".t("Virhe 2: Törmättiin virheeseen ja emme tallentaneet tietoa %s-tauluun",$kukarow["kieli"],$budj_taulu)." '$budj_kohtelu' / '$budj_taso' / '$summabudjetti' / $solu </font><br>";
							break 3;
						}

						// Poistetaan tietue jos on huutomerkki tai jos summa on nolla!
						if ($update_vai_insert == "DELETE" or $solu == 0) {
							$query = "	DELETE FROM $budj_taulu
										WHERE yhtio 			= '$kukarow[yhtio]'
										AND $budj_sarak		 	= '$liitostunnus'
										AND kausi 				= '$kausi'
										AND dyna_puu_tunnus 	= ''
										AND osasto 				= ''
										AND try 				= '$try'";
							$result = pupe_query($query);
							$pois += mysql_affected_rows();
						}
						elseif ($update_vai_insert == "UPDATE") {
							$query	= "	UPDATE $budj_taulu SET
										summa = $solu,
										maara = '$tall_maara',
										indeksi = '$tall_index',
										muuttaja = '$kukarow[kuka]',
										muutospvm = now()
										WHERE yhtio 			= '$kukarow[yhtio]'
										AND $budj_sarak		 	= '$liitostunnus'
										AND kausi 				= '$kausi'
										AND dyna_puu_tunnus 	= ''
										AND osasto 				= ''
										AND try 				= '$try'";
							$result = pupe_query($query);
							$paiv += mysql_affected_rows();
						}
						elseif ($update_vai_insert == "INSERT") {
							$query = "	INSERT INTO $budj_taulu SET
										summa 				= $solu,
										maara				= '$tall_maara',
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
							$lisaa += mysql_affected_rows();
						}
						else {
							echo "<font class='error'>".t("Ohitettiin $budj_sarak $liitostunnus", $kukarow["kieli"])."</font><br>";
						}
					}
				}
			}
		}

		echo "<font class='message'>".t("Päivitin")." $paiv. ".t("Lisäsin")." $lisaa ".t("Poistin")." $pois.</font><br /><br />";
		$tee = "";
	}

	// Käyttöliittymä
	if ($tee == "") {

		// Muutparametrit toimittaja-/asiakashakua varten
		if ($muutparametrit != "") {
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

		// Toimittajahaku
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

		// Asiakashaku
		if ($toim == "ASIAKAS" and $ytunnus != '' and $asiakasid == 0) {

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

		echo "<form method='post' enctype='multipart/form-data'>";
		echo "<input type='hidden' name='toim' value='$toim'>";

		echo "<table>";

		$query = "	SELECT *
					FROM tilikaudet
					WHERE yhtio = '{$kukarow["yhtio"]}'
					ORDER BY tilikausi_alku DESC";
		$vresult = pupe_query($query);

		echo "<tr>";
		echo "<th>",t("Tilikausi"),"</th>";
		echo "<td><select name='tkausi'>";

		while ($vrow = mysql_fetch_assoc($vresult)) {
			$sel = $tkausi == $vrow['tunnus'] ? ' selected' : '';
			echo "<option value = '$vrow[tunnus]'$sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"])."</option>";
		}

		echo "</select></td>";
		echo "</tr>";

		// Budjetin aikaväli
		echo "<tr>";
		echo "<th>".t("Anna budjetin aikaväli (kk-vuosi)")."</th>";
		echo "	<td>
				<input type='text' name='alkukk' value='$alkukk' size='3'>-
				<input type='text' name='alkuvv' value='$alkuvv' size='4'>
				&nbsp;
				<input type='text' name='loppukk' value='$loppukk' size='3'>-
				<input type='text' name='loppuvv' value='$loppuvv' size='4'>
				</td>";
		echo "</tr>";

		if ($toim == "TUOTE") {

			// indeksi vai euromäärä
			echo "<tr>";
			echo "<th>".t("Anna budjetin käsittelytyyppi")."</th>";
			echo "<td>";

			if ($budj_kohtelu == "isoi") {
				$bkcheck = "SELECTED";
				$bkcheckb = "";
			}
			elseif ($budj_kohtelu == "maara") {
				$bkcheck = "";
				$bkcheckb = "SELECTED";
			}
			else {
				$bkcheck = "";
				$bkcheckb = "";
			}

			echo "<select name='budj_kohtelu' onchange='submit()';>";
			echo "<option value = 'euro'>".t("Budjetti syötetään euroilla")."</option>";
			echo "<option value = 'isoi' $bkcheck>".t("Budjetti syötetään indekseillä")."</option>";
			echo "<option value = 'maara' $bkcheckb>".t("Budjetti syötetään määrillä")."</option>";
			echo "</td>";
			echo "</tr>";

			// Kuukausittain vai samatasokk
			echo "<tr>";
			echo "<th>".t("Budjettiluku")."</th>";
			echo "<td>";

			if ($budj_taso == "samatasokk") {
				$btcheck1 = "SELECTED";
				$btcheck2 = "";
			}
			elseif ($budj_taso == "summataso") {
				$btcheck1 = "";
				$btcheck2 = "SELECTED";
			}
			else {
				$btcheck1 = "";
				$btcheck2 = "";
			}

			echo "<select name='budj_taso' onchange='submit()';>";
			echo "<option value = ''>".t("Kuukausittain aikavälillä")."</option>";
			echo "<option value = 'samatasokk' $btcheck1>".t("Jokaiselle kuukaudelle sama arvo")."</option>";
			echo "<option value = 'summataso' $btcheck2>".t("Summa jaetaan kuukausille tasan")."</option>";
			echo "</td>";
			echo "</tr>";
			
			// Tuoteosasto tai ryhmätason budjetti.
			echo "<tr>";
			echo "<th>".t("Anna kokonaisbudjetti osastolle tai tuoteryhmälle")."</th>";

			$scheck = ($summabudjetti != "") ? "CHECKED": "";
			echo "<td><input type='checkbox' name='summabudjetti' $scheck></td>";
			echo "</tr>";
			
			echo "<tr><th>",t("Valitse tuote"),"</th>";
			echo "<td><input type='text' name='tuoteno' value='$tuoteno' /></td></tr>";

			echo "<tr><th>".t("tai rajaa tuotekategorialla")."</th><td>";

			$monivalintalaatikot = array('DYNAAMINEN_TUOTE', 'OSASTO', 'TRY');
			$monivalintalaatikot_normaali = array();

			require ("tilauskasittely/monivalintalaatikot.inc");

			echo "</td></tr>";
		}

		if ($toim == "TOIMITTAJA") {
			echo "<tr>";
			echo "<th>",t("Valitse toimittaja"),"</th>";

			if ($toimittajaid > 0) {
				$query = "	SELECT *
							FROM toimi
							WHERE yhtio = '{$kukarow["yhtio"]}'
							AND tunnus = '$toimittajaid'";
				$result = pupe_query($query);
				$toimirow = mysql_fetch_assoc($result);

				echo "<td>{$toimirow["nimi"]} {$toimirow["nimitark"]}";
				echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'></td>";
			}
			else {
				echo "<td><input type='text' name='ytunnus' value='$ytunnus'></td>";
			}

			echo "</tr>";
		}

		if ($toim == "ASIAKAS") {
			echo "<tr>";
			echo "<th>",t("Valitse asiakas"),"</th>";

			if ($asiakasid > 0) {
				$query = "	SELECT *
							FROM asiakas
							WHERE yhtio = '{$kukarow["yhtio"]}'
							AND tunnus = '$asiakasid'";
				$result = pupe_query($query);
				$asiakasrow = mysql_fetch_assoc($result);

				echo "<td>$asiakasrow[nimi] $asiakasrow[nimitark]<br>";
				echo "$asiakasrow[toim_nimi] $asiakasrow[toim_nimitark]";
				echo "<input type='hidden' name='asiakasid' value='$asiakasid' /></td>";
			}
			else {
				echo "<td><input type='text' name='ytunnus' value='$ytunnus' /></td>";
			}

			echo "</tr>";
			echo "<tr><th>".t("tai rajaa asiakaskategorialla")."</th><td>";

			$monivalintalaatikot = array('DYNAAMINEN_ASIAKAS', '<br>ASIAKASOSASTO', 'ASIAKASRYHMA');
			$monivalintalaatikot_normaali = array();

			require ("tilauskasittely/monivalintalaatikot.inc");

			echo "</td></tr>";
		}

		$tcheck = ($onko_ilman_budjettia != "") ? "CHECKED" : "";

		echo "<tr>";
		echo "<th>".t("Näytä vain rivit, joilla ei ole budjettia")."</th>";
		echo "<td><input type='checkbox' name='onko_ilman_budjettia' $tcheck></td>";
		echo "</tr>";

		if ($toim == "ASIAKAS" or $toim == "TOIMITTAJA") {
			$chk = ($tuoteryhmittain != "") ? "CHECKED" : "";
			echo "<tr><th>",t("Tuoteryhmittäin"),"</th><td><input type='checkbox' name='tuoteryhmittain' $chk></td></tr>";
		}

		echo "</table><br>";

		echo t("Budjettiluvun voi poistaa huutomerkillä (!)"),"<br />";
		echo "<br />";

		echo "<input type='submit' name='submit_button' id='submit_button' value='",t("Hae budjetti"),"' /><br>";
		echo "</form>";
	}

	// Oikellisuustarkistukset kun ollaan submitattu käyttöliittymä
	if ($submit_button != "") {

		// Oletetaan, että ei tule virheitä, nollataan $tee jos tulee virheitä
		$tee = "AJA_RAPORTTI";

		if ($alkukk != "" or $alkuvv != "" or $loppukk != "" or $loppuvv != "") {

			$alkukk = (int) $alkukk;
			$alkuvv = (int) $alkuvv;

			$loppukk = (int) $loppukk;
			$loppuvv = (int) $loppuvv;

			if (!checkdate($alkukk, 1, $alkuvv) or !checkdate($loppukk, 1, $loppuvv)) {
				echo "<font class='error'>".t("Virheellinen kausi")."!</font><br>";
				$tee = "";
			}

			$tilikaudetrow["tilikausi_alku"]  = date("Y-m-d", mktime(0, 0, 0, $alkukk, 1, $alkuvv));
			$tilikaudetrow["tilikausi_loppu"] = date("Y-m-d", mktime(0, 0, 0, $loppukk+1, 0, $loppuvv));

			if ($tilikaudetrow["tilikausi_alku"] > $tilikaudetrow["tilikausi_loppu"]) {
				echo "<font class='error'>".t("Virheellinen kausi")."!</font><br>";
				$tee = "";
			}
		}
		else {
			$query = "	SELECT *
						FROM tilikaudet
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  = '$tkausi'";
			$vresult = pupe_query($query);

			if (mysql_num_rows($vresult) == 1) {
				$tilikaudetrow = mysql_fetch_array($vresult);
			}
			else {
				echo "<font class='error'>".t("Virheellinen kausi")."!</font><br>";
				$tee = "";
			}
		}

		if ($toim == "TUOTE" and $tuoteno == "" and $lisa_dynaaminen["tuote"] == "" and $lisa == "" and $lisa_parametri == "") {
			echo "<font class='error'>".t("On valittava tuote tai tuotekategoria")."! $lisa tai $lisa_parametri</font><br>";
			$tee = "";
		}

		if ($toim == "ASIAKAS" and $asiakasid == "" and $lisa_dynaaminen["asiakas"] == "" and $lisa == "" and $lisa_parametri == "") {
			echo "<font class='error'>".t("On valittava asiakas tai asiakaskategoria")."!";
			$tee = "";
		}

		if ($toim == "TOIMITTAJA" and $toimittajaid == "") {
			echo "<font class='error'>".t("On valittava toimittaja")."!</font><br>";
			$tee = "";
		}

		if ($budj_taso == "" and $summabudjetti == "on") {
			if ($budj_kohtelu == "maara") {
				echo "<font class='error'>".t("VIRHE: Ei voida valita Määrää kokonaisbudjetille joka jaetaan per kk. Valitse Budjettiluvusta Sama luku joka kuukaudelle")."</font><br>";
			}
			else {
				echo "<font class='error'>".t("VIRHE: Ei voida valita kokonaisbudjettia sekä jakoa kuukausittain!! Valitse Budjettiluvusta toinen vaihtoehto")."</font><br>";
			}
			$tee = "";
		}

		if ($budj_kohtelu == "isoi" and $budj_taso == "summataso" and $summabudjetti == "on") {
			echo "<font class='error'>".t("VIRHE: Ei voida valita indeksiä kokonaisbudjetille joka jaetaan per kk. Valitse Budjettiluvusta Sama luku joka kuukaudelle")."</font><br>";
			$tee = "";
		}

		if ($budj_kohtelu == "isoi" and $budj_taso == "summataso") {
			echo "<font class='error'>".t("VIRHE: Ei voida valita indeksiä sekä jakoa kuukausittain!! Valitse Budjettiluvusta toinen vaihtoehto")."</font><br>";
			$tee = "";
		}

		if (isset($mul_osasto) and isset($mul_try) and count($mul_osasto) > 1 and count($mul_try) >= 1) {
			echo "<font class='error'>".t("VIRHE: Et voi valita useita osastoja ja tuoteryhmiä kerrallaan")."</font>";
			$tee = "";
		}

		if (!@include('Spreadsheet/Excel/Writer.php')) {
			echo "<font class='error'>",t("VIRHE: Pupe-asennuksesi ei tue Excel-kirjoitusta."),"</font><br>";
			$tee = "";
		}
	}

	// Ajetaan raportti
	if ($tee == "AJA_RAPORTTI") {

		//keksitään failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

		$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
		$workbook->setVersion(8);
		$worksheet =& $workbook->addWorksheet('Sheet 1');

		$format_bold =& $workbook->addFormat();
		$format_bold->setBold();

		$excelrivi		= 0;
		$excelsarake	= 0;
		$haen			= "";
		$passaan		= "";
		$selectlisa		= "";
		$grouppaus		= "";

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

		if ($toim == "TOIMITTAJA" and $toimittajaid > 0) {
			$lisa .= " and toimi.tunnus = '$toimittajaid' ";
		}

		if ($toim == "ASIAKAS" and $asiakasid > 0) {
			$lisa .= " and asiakas.tunnus = '$asiakasid' ";
		}

		if ($toim == "TUOTE" and $tuoteno != "") {
			$lisa .= " and tuote.tuoteno = '$tuoteno' ";
		}

		if (isset($liitostunnukset) and $liitostunnukset != "") {
			// Excelistä tulleet asiakkaat ylikirjaavaat muut rajaukset
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

		if ($summabudjetti == "on") {
			// mikäli ollaan valittu "Anna Kokonaisbudjetti Osastolle Tai Tuoteryhmälle"
			// Mikäli luot lisää taikka muokkaat "haen" ja "passaan" muuttujia, niin korjaa myös riville 1170 alken piirtoihin.
			// sekä Funktiolle "piirra_budj_rivi"

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
						{$lisa_parametri}
						{$lisa_dynaaminen["tuote"]}
						{$joinlisa}
						WHERE tuote.yhtio = '{$kukarow['yhtio']}'
						AND tuote.status != 'P'
						{$lisa}
						{$grouppaus}";
		}
		elseif ($toim == "TOIMITTAJA") {
			$query = "	SELECT DISTINCT toimi.tunnus toimittajan_tunnus, 
						toimi.ytunnus, 
						toimi.ytunnus asiakasnro, 
						toimi.nimi, 
						toimi.nimitark,
						'' toim_nimi, # Nämä vaan sen takia, ettei tule noticeja tablen piirtämissessä (toimittaja query pitää olla sama kun asiakasquory alla)
						'' toim_nimitark
						FROM toimi
						WHERE toimi.yhtio = '{$kukarow["yhtio"]}'
						$lisa";
		}
		elseif ($toim == "ASIAKAS") {
			$query = "	SELECT DISTINCT asiakas.tunnus asiakkaan_tunnus, 
						asiakas.ytunnus, 
						asiakas.asiakasnro, 
						asiakas.nimi, 
						asiakas.nimitark,
						IF(STRCMP(TRIM(CONCAT(asiakas.toim_nimi, ' ', asiakas.toim_nimitark)), TRIM(CONCAT(asiakas.nimi, ' ', asiakas.nimitark))) != 0, asiakas.toim_nimi, '') toim_nimi,
						IF(STRCMP(TRIM(CONCAT(asiakas.toim_nimi, ' ', asiakas.toim_nimitark)), TRIM(CONCAT(asiakas.nimi, ' ', asiakas.nimitark))) != 0, asiakas.toim_nimitark, '') toim_nimitark
						FROM asiakas
						{$lisa_dynaaminen["asiakas"]}
						WHERE asiakas.yhtio = '{$kukarow["yhtio"]}'
						$lisa";
		}

		$result = pupe_query($query);

		echo "<br><font class='message'>Budjettiluvut</font><br>";
		echo "<hr />";

		if (isset($tuoteryhmittain) and $tuoteryhmittain != "") {
			// Haetaan tuoteryhmät
			$res = t_avainsana("TRY");
			$rivimaara = mysql_num_rows($res)*mysql_num_rows($result);
		}
		else {
			$rivimaara = mysql_num_rows($result);
		}

		if ($rivimaara >= $maxrivimaara) {
			echo "<br><font class='error'>".t("HUOM: Maksimirivimäärä ylittyi, rivejä ei näytetä ruudulla. Tallenna Excel-tiedosto")."!</font><br><br>";
		}

		echo "<form method='post' enctype='multipart/form-data' autocomplete='off'>";
		echo "<input type='hidden' name='budj_kohtelu' value='$budj_kohtelu'>";
		echo "<input type='hidden' name='budj_taso' value='$budj_taso'>";
		echo "<input type='hidden' name='kausi_alku' value='{$tilikaudetrow["tilikausi_alku"]}'>";
		echo "<input type='hidden' name='kausi_loppu' value='{$tilikaudetrow["tilikausi_loppu"]}'>";
		echo "<input type='hidden' name='onko_ilman_budjettia' value='$onko_ilman_budjettia'>";
		echo "<input type='hidden' name='summabudjetti' value='$summabudjetti'>";
		echo "<input type='hidden' name='tee' value='TALLENNA_BUDJETTI_TARKISTA'>";
		echo "<input type='hidden' name='toim' value='$toim'>";

		echo "<input type='submit' name='tallennus' id='tallennus' value='",t("Tallenna budjettiluvut"),"' />";
		echo "<br><br>";

		if ($rivimaara < $maxrivimaara) echo "<table>";

		if ($toim == "TUOTE") {
			if ($grouppaus != "") {
				// näytetään joko tuoteryhmä tai tuoteosasto
				if ($haen == "try") {
					if ($rivimaara < $maxrivimaara) echo "<tr><th>",t("Tuoteryhmä"),"</th>";
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

		if (isset($tuoteryhmittain) and $tuoteryhmittain != "") {
			if ($rivimaara < $maxrivimaara) echo "<th>",t("Tuoteryhmä"),"</th>";

			$worksheet->write($excelrivi, $excelsarake, t("Tuoteryhmä"), $format_bold);
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
				// näytetään vain 1 sarake jossa on valitun/syötetyn aikakauden
				$vuosi2	= substr($tilikaudetrow['tilikausi_loppu'], 0, 4);
				$kk2	= substr($tilikaudetrow['tilikausi_loppu'], 5, 2);
				echo "<th>".$raja." / ".$vuosi2 ."-".$kk2."</th>";
			}
			elseif ($budj_taso == "samatasokk" or $budj_taso == "summataso") {
				// en piirrä turhaan otsikkoja
			}
			else {
		 		if ($rivimaara < $maxrivimaara) echo "<th>$raja</th>";
			}

			$worksheet->write($excelrivi, $excelsarake, $raja, $format_bold);
			$excelsarake++;

			if ($sarakkeet > 24) {
				echo "<font class='error'>".t("VIRHE: Ei voi tehdä yli 2 vuoden budjettia")." !!!</font><br>";
				die();
			}
		}

		if ($rivimaara < $maxrivimaara) echo "</tr>";

		$excelrivi++;
		$xx = 0;

		// tätä tarvitaan ohituksessa että menee oikein.
		$ohituksen_alkuperaiset_sarakkeet = $sarakkeet;

		if (isset($tuoteryhmittain) and $tuoteryhmittain != "") {
			while ($tryrow = mysql_fetch_assoc($res)) {
				while ($row = mysql_fetch_assoc($result)) {
					piirra_budj_rivi($row, $tryrow);
				}

				mysql_data_seek($result, 0);
			}
		}
		elseif ($budj_taso == "samatasokk" or $budj_taso == "summataso") {
			while ($row = mysql_fetch_assoc($result)) {
				piirra_budj_rivi($row, '', 'OHITA', $ohituksen_alkuperaiset_sarakkeet);
			}
		}
		elseif (isset($onko_ilman_budjettia) and $onko_ilman_budjettia != "") {
			// Mikäli ollaan valittu käyttöliittymästä "Näytä Vain Ne Tuotteet Joilla Ei Ole Budjettia"
			// niin ensin haetaan tuotteet ja kausi, luodaan array,
			// sen jälkeen verrataan $row:n tuoteno luotuun arrayseen. Loopataan kaudet läpi.
			// Mikäli löytyy yksikin kauden pätkä missä ei ole arvoa tuotteelle, niin piirretään
			// Muussa tapauksessa mikäli tuotteella on arvot, niin ei piirretä.

			$bquery = "	SELECT distinct tuoteno, kausi
						FROM budjetti_tuote
						WHERE yhtio = '{$kukarow["yhtio"]}'";
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
						if (!isset($bud[$row['tuoteno']][$aikavali]) or $bud[$row['tuoteno']][$aikavali] == "") {
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

		if ($rivimaara < $maxrivimaara) echo "</table>";

		$workbook->close();

		echo "<br><input type='submit' name='tallenna_budjetti' id='tallenna_budjetti' value='",t("Tallenna budjettiluvut"),"' />";
		echo "</form>";

		echo "<br><br><font class='message'>Budjettiluvut Excel muodossa</font><br>";

		echo "<hr>";

		echo "<form method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='kaunisnimi' value='Budjettimatriisi_$toim.xls'>";
		echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
		echo "<input type='submit' value='",t("Hae tiedosto"),"'>";
		echo "</form>";
		echo "<br><br>";
	}

	require("inc/footer.inc");
