<?php

	// Tiedoston tallennusta varten
	$lataa_tiedosto = (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == "lataa_tiedosto") ? 1 : 0;
	$_REQUEST["kaunisnimi"] = isset($_REQUEST["kaunisnimi"]) ? str_replace("/", "", $_REQUEST["kaunisnimi"]) : "";

	require ("inc/parametrit.inc");

	// K�ytett�v�t muuttujat
	$alkukk = isset($alkukk) ? trim($alkukk) : "";
	$alkuvv = isset($alkuvv) ? trim($alkuvv) : "";
	$budj_kohtelu = isset($budj_kohtelu) ? trim($budj_kohtelu) : "";
	$budjetointi_taso = isset($budjetointi_taso) ? trim($budjetointi_taso) : "";
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

	// T�m� funkkari palauttaa sy�tetyn tuotteen sen kuukauden myynnin arvon ja kpl-m��r�n
	function tuotteenmyynti($tuoteno, $alkupvm) {

		// Tarvitaan vain tuoteno ja kuukauden ensimm�inen p�iv�
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

	// funkkarin $org_sar passataan alkuper�iset sarakkeet, jota taas k�ytet��n "ohituksessa"
	function piirra_budj_rivi($row, $tryrow = "", $ohitus = "", $org_sar = "") {
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
				$nro = trim($budj_taulunrivit[$row[$budj_sarak]][$ik][$try_ind]) == "" ? "" : (float) trim($budj_taulunrivit[$row[$budj_sarak]][$ik][$try_ind]);
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

					// normaalisti n�ytet��n summaa, mutta tuotteella, mik�li k�sitell��n m��r�� niin n�ytet��n m��r� tai indeksiss� indeksi
					if ($budj_kohtelu == "maara" and $toim == "TUOTE") {
						$nro = $brow['maara'];
					}
					if ($budj_kohtelu == "indeksi" and $toim == "TUOTE") {
						$nro = $brow['indeksi'];
					}
				}
			}

			if ($rivimaara < $maxrivimaara) echo "<td>";

			if (is_array($tryrow)) {
				if ($rivimaara < $maxrivimaara) echo "<input type='text' name = 'luvut[{$row[$budj_sarak]}][{$ik}][{$tryrow["selite"]}]' value='{$nro}' size='8'></td>";
			}
			elseif ($ohitus != "") {
				// mik�li muutat "poikkeus" tai "poikkeus_haku" nimityksi� tai arvoja tai lis��t haaroja, niin muuta/lis�� ne my�s kohtaan riville 258
				// jossa k�sitell��n ne arrayn luonnissa
				// �L� MUUTA "<input type='text' class = '{$row[$budj_sarak]}'"
				// Mik�li teet muutoksia niin muuta my�s jqueryyn riville noin 17

				if ($grouppaus != "") {
					if ($haen == "try" and $passaan == "yksi"){
						echo "<input type='text' class = '{$row[$budj_sarak]}' name = 'luvut[{$row["try"]}][{$ik}][]' value='' size='8'>";
						echo "<input type='hidden' name = 'poikkeus' value='totta'>";
						echo "<input type='hidden' name = 'poikkeus_haku' value='try'>";
					}
					elseif ($haen == "osasto" and $passaan == "yksi") {
						echo "<input type='text' class = '{$row[$budj_sarak]}' name = 'luvut[{$row["osasto"]}][{$ik}][]' value='' size='8'>";
						echo "<input type='hidden' name = 'poikkeus' value='totta'>";
						echo "<input type='hidden' name = 'poikkeus_haku' value='osasto'>";
					}
					elseif ($haen == "try" and $passaan == "kaksi") {
						echo "<input type='text' class = '{$row[$budj_sarak]}' name = 'luvut[{$row["osasto"]},{$row["try"]}][{$ik}][]' value='' size='8'>";
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

	// T�ss� katsotaan mit� sy�tet��n ja kopioidaan kaikkiin samannimisiin ja ID:ll� varustettuihin hidden sy�tt�kenttiin samat arvot.
	// K�ytet��n kokonaisbudjetissa, samasumma per kk / summa jaetaan per kk
	// Mik�li teet muutoksia niin muuta my�s riveille alkaen 1170 !!!
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
		echo "<font class='head'>".t("Budjetin yll�pito tuote")."</font><hr>";

		$budj_taulu = "budjetti_tuote";
		$budj_sarak = "tuoteno";
	}
	elseif ($toim == "TOIMITTAJA") {
		echo "<font class='head'>".t("Budjetin yll�pito toimittaja")."</font><hr>";

		$budj_taulu = "budjetti_toimittaja";
		$budj_sarak = "toimittajan_tunnus";
	}
	elseif ($toim == "ASIAKAS") {
		echo "<font class='head'>".t("Budjetin yll�pito asiakas")."</font><hr>";

		$budj_taulu = "budjetti_asiakas";
		$budj_sarak = "asiakkaan_tunnus";
	}
	else {
		echo "<font class='error'>Anna ohjelmalle alanimi: TUOTE, TOIMITTAJA tai ASIAKAS.</font>";
		exit;
	}

	// Ollaan uploadttu Excel
	if (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE and $budjetointi_taso != "kuukausittain") {
		echo "<font class='error'>".t("Tiedostoja voidaan ajaa sis��n vain kuukausittain aikav�lill�")."!</font><br><br>";
		$tee = "";
	}
	elseif (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE and $budjetointi_taso == "kuukausittain") {
		
		$path_parts = pathinfo($_FILES['userfile']['name']);
		$ext = strtoupper($path_parts['extension']);

		if ($ext != "XLS") {
			die ("<font class='error'><br>".t("Ainoastaan .xls tiedostot sallittuja")."!</font>");
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

		echo "<font class='message'>".t("Tarkastetaan l�hetetty tiedosto")."...<br></font>";

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

		// Huomaa n�m� jos muutat excel-failin sarakkeita!!!!
		if ($toim == "TUOTE") {
			$lukualku = 2;
		}
		elseif ($toim == "TOIMITTAJA") {
			$lukualku = 3;
		}
		elseif ($toim == "ASIAKAS") {
			$lukualku = 4;
		}

		if ($headers[$lukualku] == "Tuoteryhm�") {
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
					$budj_taulunrivit[$liitun][$kasiind][$try] = (isset($data->sheets[0]['cells'][$excei][$excej])) ? trim($data->sheets[0]['cells'][$excei][$excej]) : "";
				}
				else {
					$budj_taulunrivit[$liitun][$kasiind][] = (isset($data->sheets[0]['cells'][$excei][$excej])) ? trim($data->sheets[0]['cells'][$excei][$excej]) : "";
				}

				$insert_rivimaara++;
			}
		}

		if ($insert_rivimaara >= $maxrivimaara) {
			// Vied��n suoraan kantaan
			$luvut = $budj_taulunrivit;
			$submit_button = "OK";

			echo "<font class='error'>".t("HUOM: Maksimirivim��r� ylittyi, rivej� ei n�ytet� ruudulla. Rivit tallennetaan suoraan tietokantaan")."!<br><br></font>";
		}
		else {
			echo "<font class='error'>".t("HUOM: Excel-tiedoston luvut eiv�t viel� tallennettu tietokantaan")."!<br>".t("Klikkaa")." '",t("Tallenna budjettiluvut"),"' ".t("tallentaaksesi luvut")."!</font><br><br></font>";
		}

		$liitostunnukset = substr($liitostunnukset, 0, -1);
	}

	// Lis�t��n/P�ivitet��n budjetti, tarkastukset
	if ($tee == "TALLENNA_BUDJETTI_TARKISTA") {

		// Oletetaan, ett� ei virheit�
		$tee = "TALLENNA_BUDJETTI";

		if (!isset($luvut) or count($luvut) == 0) {
			echo "<font class='error'>".t("Et sy�tt�nyt yht��n lukua")."!<br><br></font>";
			$tee = "";
		}

		if (isset($poikkeus_haku) and $poikkeus_haku != "try" and $poikkeus_haku != "osasto" and $poikkeus_haku != "kummatkin") {
			echo "<font class='error'>".t("Virhe: Budjetinluonnissa on tapahtunut vakava k�sittelyvirhe, keskeytet��n prosessi")."!<br><br></font>";
			$tee = "";
	}

	}

	// Lis�t��n/P�ivitet��n budjetti
	if ($tee == "TALLENNA_BUDJETTI") {

		// Jos halutaan, ett� sy�tetty arvo jaetaan tasan valitulle ryhm�lle, niin lasketaan rajattujen kuukausien lukum��r�
		if (($budj_kohtelu == "euro" or $budj_kohtelu == "maara") and $budjetointi_taso == "summa_jaetaan") {

			$alkaakk = substr($kausi_alku, 0, 4).substr($kausi_alku, 5, 2);
			$loppuukk = substr($kausi_loppu, 0, 4).substr($kausi_loppu, 5, 2);

			$sql = "SELECT PERIOD_DIFF('{$loppuukk}','{$alkaakk}')+1 as jakaja";
			$result = pupe_query($sql);
			$rivi = mysql_fetch_assoc($result);

			$kuukausien_maara = $rivi["jakaja"];
		}

		// muuttuja "poikkeus" tulee aina kun ollaan valittu 'Anna Kokonaisbudjetti Osastolle Tai Tuoteryhm�lle'
		// Normaalisti $luvut[] ensimm�isess� solussa tulee tuotteen tuoteno, nyt sielt� tulee TRY numero.
		// Haetaan kaikki sen tuoteryhm�n tuotteet ja tehd��n arrayksi.

		// otetaan $luvut talteen ja prosessoidaan ne "poikkeus-haarassa"
		$muunto_luvut = $luvut;

		if (isset($poikkeus) and $poikkeus == "totta") {

			unset($luvut); // poistetaan ja alustetaan sek� rakennetaan uudestaan.
			
			// Normaali tapaus miss� on TRY tai OSASTO
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

			// "kummatkin" tarkoittaa ett� tulee sek� TRY ja OSASTO
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

		// t�t� k�ytet��n kun laitetaan koko osastolle/try:lle yksi summa joka jaetaan per tuote per kk
		$tuotteiden_lukumaara = count($luvut);

		foreach ($luvut as $liitostunnus => $rivi) {

			foreach ($rivi as $kausi => $solut) {

				foreach ($solut as $try => $solu) {

					$solu = str_replace(",", ".", trim($solu));
					if ($try == 0) $try = "";

					if ($solu == '!' or is_numeric($solu)) {

						$update_vai_insert = "";

						// Huutomerkill� poistetaan budjetti
						if ($solu == "!") {
							$update_vai_insert = "DELETE";
						}
						else {
							// Katsotaan l�ytyyk� n�ill� tiedoilla jo budjetti
							$query = "	SELECT summa
										FROM $budj_taulu
										WHERE yhtio 	= '{$kukarow["yhtio"]}'
										AND $budj_sarak	= '$liitostunnus'
										AND kausi 		= '$kausi'
										AND try 		= '$try'";
							$result = pupe_query($query);

							if (mysql_num_rows($result) > 0) {
								$budjrow = mysql_fetch_assoc($result);
								// L�ytyy budjetti -> p�ivitet��n
								$update_vai_insert = "UPDATE";
							}
							else {
								// Ei l�ydy budjettia -> lis�t��n
								$update_vai_insert = "INSERT";
							}
						}

						// $solu on k�ytt�j�n sy�tt�m� luku
						// $tall_summa on kantaan tallennettava summa
						// $tall_index on kantaan tallennettava indeksi-luku
						// $tall_maara on kantaan tallennettava kappalem��r�

						$solu = (float) $solu;
						$tall_summa = 0;
						$tall_index = 0;
						$tall_maara = 0;

						// Toimittaja ja asiakasbudjetti tehd��n aina euroissa
						if ($toim == "TOIMITTAJA" or $toim == "ASIAKAS") {
							$tall_summa = $solu;
						}
						// Tuotebudjetissa on muitakin vaihtoehtoja
						elseif ($toim == "TUOTE") {

							// Budjettiluvun voi sy�tt�� eri tasoilla. Budjetointitasoja on: kuukausittain, joka_kk_sama ja summa_jaetaan
							// Kuukausittain tarkoittaa, ett� jokaisen kauden arvo on sy�tetty erikseen
							// Joka_kk_sama tarkoittaa, ett� jokaiselle kaudelle on annettu sama arvo
							// Summa_jaetaan tarkoittaa, ett� sy�tetty arvo jaetaan rajattujen kuukausien ja tuotteiden m��r�n kesken

							if ($budjetointi_taso == "summa_jaetaan") {
								// Jaetaan sy�tty luku kuukausien ja tuotteiden m��r�n mukaan
								$solu = $solu / ($kuukausien_maara * $tuotteiden_lukumaara);
							}
							elseif ($budjetointi_taso != "kuukausittain" and $budjetointi_taso != "joka_kk_sama") {
								// Virheellinen budjetointitaso, ei tehd� mit��n
								$update_vai_insert = "";
							}

							// Kohtelu tarkoittaa, mink�tyyppist� lukua sy�tet��n. Kohteluita on: euro, maara ja indeksi
							if ($budj_kohtelu == "euro") {
								// Sy�tetty arvo summa kentt��n
								$tall_summa = round($solu, 2);
							}
							elseif ($budj_kohtelu == "maara") {
								// Sy�tetty arvo m��r� kentt��n
								$tall_maara = round($solu, 2);
							}
							elseif ($budj_kohtelu == "indeksi") {
								// Sy�tetty arvo indeksi kentt��n, lis�ksi tulee hakea edellisen vuoden vastaavan kauden myynti/kulutus ja kertoa se indeksill�

								// Kausi on muotoa VVVVKK, tehd��n siit� edellisen vuoden vastaavan kauden eka p�iv� muotoon VVVV-KK-PP
								$ed_kausi = (substr($kausi, 0, 4) -1).'-'.substr($kausi, 4, 2).'-01';

								list($myyntihistoriassa, $maarahistoriassa) = tuotteenmyynti($liitostunnus, $ed_kausi);

								$tall_index = round($solu, 2);
								$tall_maara = round($maarahistoriassa * $tall_index, 2);
								$tall_summa = round($myyntihistoriassa * $tall_index, 2);

								if ($tall_summa == 0 or $tall_maara == 0) {
									echo "<font class='error'>$liitostunnus: ".t("Indeksi")." $tall_index";
									if ($tall_summa == 0) echo " ".t("summa");
									if ($tall_summa == 0 and $tall_maara == 0) echo " ".t("ja");
									if ($tall_maara == 0) echo " ".t("m��r�");
									echo " ".t("j�i nollaksi")."!</font><br>";
								}
							}
							else {
								// Virheellinen kohtelu, ei tehd� mit��n
								$update_vai_insert = "";
							}
						}
						// Virheellinen TOIM, ei tehd� mit��n
						else {
							$update_vai_insert = "";
						}

						// Poistetaan tietue jos on sy�tetty huutomerkki
						if ($update_vai_insert == "DELETE") {
							$query = "	DELETE FROM $budj_taulu
										WHERE yhtio 	= '{$kukarow["yhtio"]}'
										AND $budj_sarak	= '$liitostunnus'
										AND kausi 		= '$kausi'
										AND try 		= '$try'";
							$result = pupe_query($query);
							$pois += mysql_affected_rows();
						}
						elseif ($update_vai_insert == "UPDATE") {
							$query	= "	UPDATE $budj_taulu SET
										summa		= '$tall_summa',
										maara		= '$tall_maara',
										indeksi		= '$tall_index',
										muuttaja	= '{$kukarow["kuka"]}',
										muutospvm	= now()
										WHERE yhtio 	= '{$kukarow["yhtio"]}'
										AND $budj_sarak	= '$liitostunnus'
										AND kausi 		= '$kausi'
										AND try 		= '$try'";
							$result = pupe_query($query);
							$paiv += mysql_affected_rows();
						}
						elseif ($update_vai_insert == "INSERT") {
							$query = "	INSERT INTO $budj_taulu SET
										summa 				= '$tall_summa',
										maara				= '$tall_maara',
										yhtio 				= '{$kukarow["yhtio"]}',
										kausi 				= '$kausi',
										$budj_sarak		 	= '$liitostunnus',
										try 				= '$try',
										indeksi				= '$tall_index',
										laatija 			= '{$kukarow["kuka"]}',
										luontiaika 			= now(),
										muutospvm 			= now(),
										muuttaja 			= '{$kukarow["kuka"]}'";
							$result = pupe_query($query);
							$lisaa += mysql_affected_rows();
						}
						else {
							echo "<font class='error'>".t("Virheelliset parametrit")." '$budj_sarak' '$liitostunnus' / '$budj_kohtelu' / '$budjetointi_taso' / '$summabudjetti' / $solu </font><br>";
						}
					}
				}
			}
		}

		echo "<font class='message'>".t("P�ivitin")." $paiv. ".t("Lis�sin")." $lisaa ".t("Poistin")." $pois.</font><br /><br />";
		$tee = "";
	}

	// K�ytt�liittym�
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

		echo "<form method='post' enctype='multipart/form-data' autocomplete='off'>";
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

		// Budjetin aikav�li
		echo "<tr>";
		echo "<th>".t("Anna budjetin aikav�li (kk-vuosi)")."</th>";
		echo "	<td>
				<input type='text' name='alkukk' value='$alkukk' size='3'>-
				<input type='text' name='alkuvv' value='$alkuvv' size='4'>
				&nbsp;
				<input type='text' name='loppukk' value='$loppukk' size='3'>-
				<input type='text' name='loppuvv' value='$loppuvv' size='4'>
				</td>";
		echo "</tr>";

		if ($toim == "TUOTE") {

			// indeksi vai eurom��r�
			echo "<tr>";
			echo "<th>".t("Anna budjetin k�sittelytyyppi")."</th>";
			echo "<td>";

			if ($budj_kohtelu == "indeksi") {
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
			echo "<option value = 'euro'>".t("Budjetti sy�tet��n euroilla")."</option>";
			echo "<option value = 'indeksi' $bkcheck>".t("Budjetti sy�tet��n indekseill�")."</option>";
			echo "<option value = 'maara' $bkcheckb>".t("Budjetti sy�tet��n m��rill�")."</option>";
			echo "</td>";
			echo "</tr>";

			// Mill� tasolla budjetti tehd��n
			echo "<tr>";
			echo "<th>".t("Budjettiluku")."</th>";
			echo "<td>";

			if ($budjetointi_taso == "joka_kk_sama") {
				$btcheck1 = "SELECTED";
				$btcheck2 = "";
			}
			elseif ($budjetointi_taso == "summa_jaetaan") {
				$btcheck1 = "";
				$btcheck2 = "SELECTED";
			}
			else {
				$btcheck1 = "";
				$btcheck2 = "";
			}

			echo "<select name='budjetointi_taso' onchange='submit()';>";
			echo "<option value = 'kuukausittain'>".t("Kuukausittain aikav�lill�")."</option>";
			echo "<option value = 'joka_kk_sama' $btcheck1>".t("Jokaiselle kuukaudelle sama arvo")."</option>";
			echo "<option value = 'summa_jaetaan' $btcheck2>".t("Budjettiluku jaetaan kuukausille tasan")."</option>";
			echo "</td>";
			echo "</tr>";

			// Tuoteosasto tai ryhm�tason budjetti.
			echo "<tr>";
			echo "<th>".t("Anna kokonaisbudjetti osastolle tai tuoteryhm�lle")."</th>";

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
		echo "<th>".t("N�yt� vain rivit, joilla ei ole budjettia")."</th>";
		echo "<td><input type='checkbox' name='onko_ilman_budjettia' $tcheck></td>";
		echo "</tr>";

		if ($toim == "ASIAKAS" or $toim == "TOIMITTAJA") {
			$chk = ($tuoteryhmittain != "") ? "CHECKED" : "";
			echo "<tr><th>",t("Tuoteryhmitt�in"),"</th><td><input type='checkbox' name='tuoteryhmittain' $chk></td></tr>";
		}

		echo "<tr>";
		echo "<th>".t("Lue budjettiluvut tiedostosta")."</th>";
		echo "<td><input type='file' name='userfile'></td>";
		echo "</tr>";

		echo "</table><br>";

		echo t("Budjettiluvun voi poistaa huutomerkill� (!)"),"<br />";
		echo "<br />";

		echo "<input type='submit' name='submit_button' id='submit_button' value='",t("Hae budjetti"),"' /><br>";
		echo "</form>";
	}

	// Oikellisuustarkistukset kun ollaan submitattu k�ytt�liittym�
	if ($submit_button != "") {

		// Oletetaan, ett� ei tule virheit�, nollataan $tee jos tulee virheit�
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

		// Kokonaisbudjetti-tarkastukset
		if ($summabudjetti == "on") {
			if ($budj_kohtelu == "indeksi" and $budjetointi_taso != "joka_kk_sama") {
				echo "<font class='error'>".t("VIRHE: Kokonaisbudjetin voi sy�tt�� indeksiluvulla vain budjetoimalla jokaiselle kuukaudelle saman arvon!")."</font><br>";
				$tee = "";
			}			
			if ($budjetointi_taso == "kuukausittain") {
				echo "<font class='error'>".t("VIRHE: Kokonaisbudjettia ei voida sy�tt�� kuukausittain aikav�lill�!")."</font><br>";
				$tee = "";
			}
		}

		if ($budj_kohtelu == "indeksi" and $budjetointi_taso == "summa_jaetaan") {
			echo "<font class='error'>".t("VIRHE: Et voi jakaa indeksilukua kuukausittain!")."</font><br>";
			$tee = "";
		}

		if (isset($mul_osasto) and isset($mul_try) and count($mul_osasto) > 1 and count($mul_try) >= 1) {
			echo "<font class='error'>".t("VIRHE: Et voi valita useita osastoja ja tuoteryhmi� kerrallaan")."</font>";
			$tee = "";
		}

		if (!@include('Spreadsheet/Excel/Writer.php')) {
			echo "<font class='error'>",t("VIRHE: Pupe-asennuksesi ei tue Excel-kirjoitusta."),"</font><br>";
			$tee = "";
		}
	}

	// Ajetaan raportti
	if ($tee == "AJA_RAPORTTI") {

		//keksit��n failille joku varmasti uniikki nimi:
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
			// Excelist� tulleet asiakkaat ylikirjaavaat muut rajaukset
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
			// mik�li ollaan valittu "Anna Kokonaisbudjetti Osastolle Tai Tuoteryhm�lle"
			// Mik�li luot lis�� taikka muokkaat "haen" ja "passaan" muuttujia, niin korjaa my�s riville 1170 alken piirtoihin.
			// sek� Funktiolle "piirra_budj_rivi"

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
						'' toim_nimi, # N�m� vaan sen takia, ettei tule noticeja tablen piirt�missess� (toimittaja query pit�� olla sama kun asiakasquory alla)
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
			// Haetaan tuoteryhm�t
			$res = t_avainsana("TRY");
			$rivimaara = mysql_num_rows($res)*mysql_num_rows($result);
		}
		else {
			$rivimaara = mysql_num_rows($result);
		}

		if ($rivimaara >= $maxrivimaara) {
			echo "<br><font class='error'>".t("HUOM: Maksimirivim��r� ylittyi, rivej� ei n�ytet� ruudulla. Tallenna Excel-tiedosto")."!</font><br><br>";
		}

		echo "<form method='post' enctype='multipart/form-data' autocomplete='off'>";

		// Laitetaan monivalintalaatikoiden valinnat my�s mukaan
		if (count($mul_try) > 0) {
			foreach ($mul_try as $try) {
				echo "<input type='hidden' name='mul_try[]' value = '$try'>";
			}
		}
		if (count($mul_osasto) > 0) {
			foreach ($mul_osasto as $os) {
				echo "<input type='hidden' name='mul_osasto[]' value = '$os'>";
			}
		}
		#TODO: ei osaa s�ilytt�� dynaamisia tuotekategorioita.
		echo "<input type='hidden' name='tkausi' value = '$tkausi'>";
		echo "<input type='hidden' name='alkukk' value = '$alkukk'>";
		echo "<input type='hidden' name='alkuvv' value = '$alkuvv'>";
		echo "<input type='hidden' name='loppukk' value = '$loppukk'>";
		echo "<input type='hidden' name='loppuvv' value = '$loppuvv'>";
		echo "<input type='hidden' name='tuoteno' value = '$tuoteno'>";
		echo "<input type='hidden' name='budj_kohtelu' value='$budj_kohtelu'>";
		echo "<input type='hidden' name='budjetointi_taso' value='$budjetointi_taso'>";
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
				// n�ytet��n joko tuoteryhm� tai tuoteosasto
				if ($haen == "try") {
					if ($rivimaara < $maxrivimaara) echo "<tr><th>",t("Tuoteryhm�"),"</th>";
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
			if ($rivimaara < $maxrivimaara) echo "<th>",t("Tuoteryhm�"),"</th>";

			$worksheet->write($excelrivi, $excelsarake, t("Tuoteryhm�"), $format_bold);
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

			if (($budjetointi_taso == "joka_kk_sama" or $budjetointi_taso == "summa_jaetaan") and $sarakkeet == 1) {
				// n�ytet��n vain 1 sarake jossa on valitun/sy�tetyn aikakauden
				$vuosi2	= substr($tilikaudetrow['tilikausi_loppu'], 0, 4);
				$kk2	= substr($tilikaudetrow['tilikausi_loppu'], 5, 2);
				echo "<th>".$raja." / ".$vuosi2 ."-".$kk2."</th>";
			}
			elseif ($budjetointi_taso == "joka_kk_sama" or $budjetointi_taso == "summa_jaetaan") {
				// en piirr� turhaan otsikkoja
			}
			else {
		 		if ($rivimaara < $maxrivimaara) echo "<th>$raja</th>";
			}

			$worksheet->write($excelrivi, $excelsarake, $raja, $format_bold);
			$excelsarake++;

			if ($sarakkeet > 24) {
				echo "<font class='error'>".t("VIRHE: Ei voi tehd� yli 2 vuoden budjettia")." !!!</font><br>";
				die();
			}
		}

		if ($rivimaara < $maxrivimaara) echo "</tr>";

		$excelrivi++;
		$xx = 0;

		// t�t� tarvitaan ohituksessa ett� menee oikein.
		$ohituksen_alkuperaiset_sarakkeet = $sarakkeet;

		if (isset($tuoteryhmittain) and $tuoteryhmittain != "") {
			while ($tryrow = mysql_fetch_assoc($res)) {
				while ($row = mysql_fetch_assoc($result)) {
					piirra_budj_rivi($row, $tryrow);
				}

				mysql_data_seek($result, 0);
			}
		}
		elseif ($budjetointi_taso == "joka_kk_sama" or $budjetointi_taso == "summa_jaetaan") {
			while ($row = mysql_fetch_assoc($result)) {
				piirra_budj_rivi($row, '', 'OHITA', $ohituksen_alkuperaiset_sarakkeet);
			}
		}
		elseif (isset($onko_ilman_budjettia) and $onko_ilman_budjettia != "") {
			// Mik�li ollaan valittu k�ytt�liittym�st� "N�yt� Vain Ne Tuotteet Joilla Ei Ole Budjettia"
			// niin ensin haetaan tuotteet ja kausi, luodaan array,
			// sen j�lkeen verrataan $row:n tuoteno luotuun arrayseen. Loopataan kaudet l�pi.
			// Mik�li l�ytyy yksikin kauden p�tk� miss� ei ole arvoa tuotteelle, niin piirret��n
			// Muussa tapauksessa mik�li tuotteella on arvot, niin ei piirret�.

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
