<?php
	// katsotaan tuleeko kaikki muuttujat REQUEST:ssa serialisoituna
	if (isset($_REQUEST['kaikki_parametrit_serialisoituna'])) {

		$kaikki_parametrit_serialisoituna = unserialize(urldecode($_REQUEST['kaikki_parametrit_serialisoituna']));
		$kaikki_muuttujat_array = array();

		foreach ($kaikki_parametrit_serialisoituna as $parametri_key => $parametri_value) {
			${$parametri_key} = $parametri_value;
			$_REQUEST[$parametri_key] = $parametri_value;
		}

		unset($_REQUEST['kaikki_parametrit_serialisoituna']);
	}

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta.php") !== FALSE) {
		require ("../inc/parametrit.inc");
	}

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {
		echo "<font class='head'>".t("Myyntiseuranta")."</font><hr>";

		// tehd��n kaikista raportin parametreist� yksi muuttuja serialisoimista varten
		$kaikki_muuttujat_array = array();

		foreach ($_REQUEST as $kaikki_muuttujat_array_key => $kaikki_muuttujat_array_value) {
			if ($kaikki_muuttujat_array_key != "pupesoft_session" and
				$kaikki_muuttujat_array_key != "uusi_kysely" and
				$kaikki_muuttujat_array_key != "tallenna_muutokset" and
				$kaikki_muuttujat_array_key != "poista_kysely" and
				$kaikki_muuttujat_array_key != "aja_kysely") {
				$kaikki_muuttujat_array[$kaikki_muuttujat_array_key] = $kaikki_muuttujat_array_value;
			}

		}

		if(!aja_kysely()) {
			unset($_REQUEST);
		}

		// k�ytet��n slavea
		$useslave = 1;
		require ("inc/connect.inc");

		if(count($_REQUEST) > 0) {
			if(!function_exists("vararvo")) {
				function vararvo($tuoteno, $vv, $kk, $pp) {
					global $kukarow, $yhtiorow;

					$kehahin = 0;

					$query  = "	SELECT tuote.tuoteno, tuote.tuotemerkki, tuote.nimitys, tuote.kehahin, tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm, tuote.sarjanumeroseuranta
								FROM tuote
								WHERE tuote.yhtio 	= '$kukarow[yhtio]'
								and tuote.ei_saldoa = ''
								and tuote.tuoteno 	= '$tuoteno'";
					$result = mysql_query($query) or pupe_error($query);
					$row = mysql_fetch_array($result);

					// Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksil�iden ostohinnoista (ostetut yksil�t jotka eiv�t viel� ole myyty(=laskutettu))
					if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "U" or $row["sarjanumeroseuranta"] == "G") {
						$query	= "	SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
									FROM sarjanumeroseuranta
									LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
									LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio  and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
									WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
									and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
									and sarjanumeroseuranta.myyntirivitunnus != -1
									and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
									and tilausrivi_osto.laskutettuaika != '0000-00-00'";
						$sarjares = mysql_query($query) or pupe_error($query);
						$sarjarow = mysql_fetch_array($sarjares);

						$kehahin = sprintf('%.2f', $sarjarow["kehahin"]);
					}
					else {
						$kehahin = sprintf('%.2f', $row["kehahin"]);
					}

					// tuotteen muutos varastossa annetun p�iv�n j�lkeen
					$query = "	SELECT sum(kpl * if(laji in ('tulo','valmistus'), kplhinta, hinta)) muutoshinta, sum(kpl) muutoskpl
					 			FROM tapahtuma use index (yhtio_tuote_laadittu)
					 			WHERE yhtio = '$kukarow[yhtio]'
					 			and tuoteno = '$row[tuoteno]'
					 			and laadittu > '$vv-$kk-$pp 23:59:59'";
					$mres = mysql_query($query) or pupe_error($query);
					$mrow = mysql_fetch_array($mres);

					// katotaan onko tuote ep�kurantti nyt
					$kerroin = 1;

					if ($row['epakurantti25pvm'] != '0000-00-00') {
						$kerroin = 0.75;
					}
					if ($row['epakurantti50pvm'] != '0000-00-00') {
						$kerroin = 0.5;
					}
					if ($row['epakurantti75pvm'] != '0000-00-00') {
						$kerroin = 0.25;
					}
					if ($row['epakurantti100pvm'] != '0000-00-00') {
						$kerroin = 0;
					}

					// tuotteen m��r� varastossa nyt
					$query = "	SELECT sum(saldo) varasto
								FROM tuotepaikat use index (tuote_index)
								WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
								and tuotepaikat.tuoteno = '$row[tuoteno]'";
					$vres = mysql_query($query) or pupe_error($query);
					$vrow = mysql_fetch_array($vres);

					// arvo historiassa: lasketaan (nykyinen varastonarvo) - muutoshinta
					$muutoshinta = ($vrow["varasto"] * $kehahin * $kerroin) - $mrow["muutoshinta"];

					// saldo historiassa: lasketaan nykyiset kpl - muutoskpl
					$muutoskpl = $vrow["varasto"] - $mrow["muutoskpl"];

					// haetaan tuotteen myydyt kappaleet
					$query  = "	SELECT ifnull(sum(kpl),0) kpl
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
								WHERE yhtio='$kukarow[yhtio]' and tyyppi='L' and tuoteno='$row[tuoteno]' and laskutettuaika <= '$vv-$kk-$pp' and laskutettuaika >= date_sub('$vv-$kk-$pp', INTERVAL 12 month)";
					$xmyyres = mysql_query($query) or pupe_error($query);
					$xmyyrow = mysql_fetch_array($xmyyres);

					// haetaan tuotteen kulutetut kappaleet
					$query  = "	SELECT ifnull(sum(kpl),0) kpl
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
								WHERE yhtio='$kukarow[yhtio]' and tyyppi='V' and tuoteno='$row[tuoteno]' and toimitettuaika <= '$vv-$kk-$pp' and toimitettuaika >= date_sub('$vv-$kk-$pp', INTERVAL 12 month)";
					$xkulres = mysql_query($query) or pupe_error($query);
					$xkulrow = mysql_fetch_array($xkulres);

					// lasketaan varaston kiertonopeus
					if ($muutoskpl > 0) {
						$kierto = round(($xmyyrow["kpl"] + $xkulrow["kpl"]) / $muutoskpl, 2);
					}
					else {
						$kierto = 0;
					}

					return array($muutoshinta, $kierto, $muutoskpl);
				}
			}

			//	Jos k�ytt�j�ll� on valittu piirej� niin sallitaan vain ko. piirin/piirien hakeminen
			if ($kukarow["piirit"] != "")	 {
				$asiakasrajaus = "and lasku.piiri IN ($kukarow[piirit])";
				$asiakasrajaus_avainsana = "and avainsana.selite IN ($kukarow[piirit])";
			}
			else {
				$asiakasrajaus="";
				$asiakasrajaus_avainsana = "";
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

			// tutkaillaan saadut muuttujat
			$ytunnus         = trim($ytunnus);
			$toimittaja    	 = trim($toimittaja);

			// hehe, n�in on helpompi verrata p�iv�m��ri�
			$query  = "SELECT TO_DAYS('$vvl-$kkl-$ppl')-TO_DAYS('$vva-$kka-$ppa') ero";
			$result = mysql_query($query) or pupe_error($query);
			$row    = mysql_fetch_array($result);

			if ($row["ero"] > 365 and $ajotapa != 'tilausauki') {
				echo "<font class='error'>".t("Jotta homma ei menisi liian hitaaksi, niin vuosi on pisin mahdollinen laskentav�li!")."</font><br>";
				$tee = "";
			}

			// haetaan tilauksista
			if ($ajotapa == 'tilaus') {
				$tila		= "'L'";
				$ouusio		= 'otunnus';
				$index		= 'yhtio_otunnus';
				$tyyppi		= "'L'";
			}
			elseif ($ajotapa == 'tilausjaauki' or $ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'tilausauki') {
				$tila		= "'L','N'";
				$ouusio		= 'otunnus';
				$index		= 'yhtio_otunnus';
				$tyyppi		= "'L'";
			}
			elseif ($ajotapa == 'ennakot') {
				$tila		= "'E'";
				$ouusio		= 'otunnus';
				$index		= 'yhtio_otunnus';
				$tyyppi		= "'E'";
			}
			// haetaan laskuista
			else {
				$tila		= "'U'";
				$ouusio		= 'uusiotunnus';
				$index		= 'uusiotunnus_index';
				$tyyppi		= "'L'";
			}

			// jos on joku toimittajajuttu valittuna, niin ei saa valita ku yhen yrityksen
			if ($toimittaja != "" or $mukaan == "toimittaja") {
				if (count($yhtiot) != 1) {
					echo "<font class='error'>".t("Toimittajahauissa voi valita vain yhden yrityksen")."!</font><br>";
					$tee = "";
				}
			}

			// jos ei ole mit��n yrityst� valittuna ei tehd� mit��n
			if (count($yhtiot) == 0) {
				$tee = "";
			}
			else {
				$yhtio  = "";
				foreach ($yhtiot as $apukala) {
					$yhtio .= "'$apukala',";
				}
				$yhtio = substr($yhtio,0,-1);
			}

			// jos joku p�iv�kentt� on tyhj�� ei tehd� mit��n
			if ($ppa == "" or $kka == "" or $vva == "" or $ppl == "" or $kkl == "" or $vvl == "") {
				$tee = "";
			}

			if ($tee == 'go' and ($asiakasnro != '' or $ytunnus != '' or $toimittaja != '')) {
				$muutparametrit = "";

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
			}

			if ($tee == 'go' and ($asiakasnro != '' or $ytunnus != '')) {
				//$ytunnus = $asiakas;

				require ("inc/asiakashaku.inc");

				if ($asiakasnro != "") {
					$ytunnus = "";
				}
				else if ($ytunnus != "") {
					$asiakasnro = "";
				}

				if ($ytunnus != '') {
					$asiakas = $ytunnus;
				}
				else if ($asiakasnro != "") {
					// menn��n ohi
				}
				else {
					$tee 		= "";
					$asiakasid 	= "";
				}
			}

			if ($tee == 'go' and $toimittaja != '') {
				$ytunnus = $toimittaja;

				require("../inc/kevyt_toimittajahaku.inc");

				if ($ytunnus != '') {
					$toimittaja = $ytunnus;
					$ytunnus = '';
				}
				else {
					$tee 			= "";
					$toimittajaid 	= "";
				}
			}

			if ($tee == 'go') {

				$query_ale_lisa = generoi_alekentta('M');

				// no hacking, please.
				$lisa   = "";
				$query  = "";
				$group  = "";
				$order  = "";
				$select = "";
				$gluku  = 0;
				$varastojoin = "";
				$kantaasiakasjoin = "";

				// n�it� k�ytet��n queryss�
				$sel_osasto = "";
				$sel_tuoteryhma = "";

				$apu = array();

				if (count($yhtiot) > 1) {
					if ($group!="") $group .= ",lasku.yhtio";
					else $group .= "lasku.yhtio";
					$select .= "lasku.yhtio yhtio, ";
					$order  .= "lasku.yhtio,";
					$gluku++;
				}

				foreach ($jarjestys as $ind => $arvo) {
					if (trim($arvo) != "") $apu[] = $arvo;
				}

				if (count($apu) == 0) {
					ksort($jarjestys);
				}
				else {
					asort($jarjestys);
				}

				$apu = array();

				foreach ($jarjestys as $i => $arvo) {
					if ($ruksit[$i] != "") {
						$apu[$i] = $ruksit[$i];
					}
				}

				foreach ($apu as $i => $mukaan) {

					if ($mukaan == "asiakasosasto") {
						if ($group!="") $group .= ",asiakas.osasto";
						else $group .= "asiakas.osasto";
						$select .= "asiakas.osasto asos, ";
						$order  .= "asiakas.osasto,";
						$gluku++;
					}

					if ($mukaan == "asiakasryhma") {
						if ($group!="") $group .= ",asiakas.ryhma";
						else $group .= "asiakas.ryhma";
						$select .= "asiakas.ryhma asry, ";
						$order  .= "asiakas.ryhma,";
						$gluku++;
					}

					if ($mukaan == "osasto") {
						if ($group!="") $group .= ",tuote.osasto";
						else $group .= "tuote.osasto";
						$select .= "tuote.osasto tuos, ";
						$order  .= "tuote.osasto,";
						$gluku++;
					}

					if ($mukaan == "tuoteryhma") {
						if ($group!="") $group .= ",tuote.try";
						else $group .= "tuote.try";
						$select .= "tuote.try 'tuoteryhm�', ";
						$order  .= "tuote.try,";
						$gluku++;
					}

					if ($mukaan == "piiri" and $piirivalinta == "asiakas") {
						if ($group!="") $group .= ",asiakas.piiri";
						else $group .= "asiakas.piiri";
						$select .= "asiakas.piiri aspiiri, ";
						$order  .= "asiakas.piiri,";
						$gluku++;
					}

					if ($mukaan == "piiri" and $piirivalinta == "lasku") {
						if ($group!="") $group .= ",lasku.piiri";
						else $group .= "lasku.piiri";
						$select .= "lasku.piiri aspiiri, ";
						$order  .= "lasku.piiri,";
						$gluku++;
					}

					if ($mukaan == "kustannuspaikka") {
						if ($group!="") $group .= ",kustpaikka";
						else $group  .= "kustpaikka";
						$select .= "if(tuote.kustp > 0,tuote.kustp,asiakas.kustannuspaikka) as kustpaikka, ";
						$order  .= "kustpaikka,";
						$gluku++;
					}

					if ($mukaan == "konserni") {
						if ($group != "") {
							$group .= ",konserni";
						}
						else {
							$group  .= "konserni";
						}
						$select .= "asiakas.konserni, ";
						$order  .= "konserni,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and asiakas.konserni = '$rajaus[$i]' ";
						}
					}

					if ($mukaan == "ytunnus") {
						if ($group!="") $group .= ",asiakas.tunnus";
						else $group  .= "asiakas.tunnus";

						if ($osoitetarrat != "") $select .= "asiakas.tunnus astunnus, ";

						if ($ytunnus_mistatiedot != "") {
							$etuliite = "lasku";
						}
						else {
							$etuliite = "asiakas";
						}

						$select .= "$etuliite.ytunnus, $etuliite.toim_ovttunnus, concat_ws('<br>',concat_ws(' ',$etuliite.nimi,$etuliite.nimitark),if($etuliite.toim_nimi!='' and $etuliite.nimi!=$etuliite.toim_nimi,concat_ws(' ',$etuliite.toim_nimi,$etuliite.toim_nimitark),NULL)) nimi, concat_ws('<br>',$etuliite.postitp,if($etuliite.toim_postitp!='' and $etuliite.postitp!=$etuliite.toim_postitp,$etuliite.toim_postitp,NULL)) postitp, ";
						$order  .= "$etuliite.ytunnus,";
						$gluku++;
					}

					if ($mukaan == "asiakasnro") {
						if ($group!="") $group .= ",asiakas.tunnus";
						else $group .= "asiakas.tunnus";
						$select .= "asiakas.asiakasnro, concat_ws('<br>',concat_ws(' ',asiakas.nimi,asiakas.nimitark),if(asiakas.toim_nimi!='' and asiakas.nimi!=asiakas.toim_nimi,concat_ws(' ',asiakas.toim_nimi,asiakas.toim_nimitark),NULL)) 'asiakasnro.nimi', concat_ws('<br>',asiakas.postitp,if(asiakas.toim_postitp!='' and asiakas.postitp!=asiakas.toim_postitp,asiakas.toim_postitp,NULL)) 'asiakasnro.postitp', ";
						$order  .= "asiakas.asiakasnro,";
						$gluku++;
					}

					if ($mukaan == "maa") {
						if ($group!="") $group .= ",lasku.maa";
						else $group  .= "lasku.maa";
						$select .= "lasku.maa maa, ";
						$order  .= "lasku.maa,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and lasku.maa='$rajaus[$i]' ";
						}
					}

					if ($mukaan == "tuote") {
						if ($nimitykset == "") {
							if ($group!="") $group .= ",tuote.tuoteno";
							else $group  .= "tuote.tuoteno";
							$select .= "tuote.tuoteno tuoteno, ";
							$order  .= "tuote.tuoteno,";
							$gluku++;
						}
						else {
							if ($group!="") $group .= ", tuote.tuoteno, tuote.nimitys";
							else $group  .= "tuote.tuoteno, tuote.nimitys";
							$select .= "tuote.tuoteno tuoteno, tuote.nimitys nimitys, ";
							$order  .= "tuote.tuoteno,";
							$gluku++;
						}
						if ($varastonarvo != '') {
							$select .= "0 varastonarvo, 0 kierto, 0 varastonkpl, ";
						}

						if ($rajaus[$i] != "") {
							$lisa .= " and tuote.tuoteno='$rajaus[$i]' ";
						}
					}

					if ($mukaan == "tuotemyyja") {
						if ($group!="") $group .= ",tuote.myyjanro";
						else $group  .= "tuote.myyjanro";
						$select .= "tuote.myyjanro tuotemyyja, ";
						$order  .= "tuote.myyjanro,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and tuote.myyjanro='$rajaus[$i]' ";
						}
					}

					if ($mukaan == "asiakasmyyja") {
						if ($group!="") $group .= ",asiakas.myyjanro";
						else $group  .= "asiakas.myyjanro";
						$select .= "asiakas.myyjanro asiakasmyyja, ";
						$order  .= "asiakas.myyjanro,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and asiakas.myyjanro='$rajaus[$i]' ";
						}
					}

					if ($mukaan == "tuoteostaja") {
						if ($group!="") $group .= ",tuote.ostajanro";
						else $group  .= "tuote.ostajanro";
						$select .= "tuote.ostajanro tuoteostaja, ";
						$order  .= "tuote.ostajanro,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and tuote.ostajanro='$rajaus[$i]' ";
						}
					}

					if ($mukaan == "merkki") {
						if ($group!="") $group .= ",tuote.tuotemerkki";
						else $group  .= "tuote.tuotemerkki";
						$select .= "tuote.tuotemerkki merkki, ";
						$order  .= "tuote.tuotemerkki,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and tuote.tuotemerkki='$rajaus[$i]' ";
						}
					}

					if ($mukaan == "toimittaja") {
						if ($group!="") $group .= ",toimittaja";
						else $group  .= "toimittaja";
						$select .= "(select group_concat(distinct tuotteen_toimittajat.liitostunnus) from tuotteen_toimittajat use index (yhtio_tuoteno) where tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno) toimittaja, ";
						$order  .= "toimittaja,";
						$gluku++;
					}

					if ($mukaan == "tilaustyyppi") {
						if ($group!="") $group .= ",lasku.clearing";
						else $group  .= "lasku.clearing";
						$select .= "lasku.clearing tilaustyyppi, ";
						$order  .= "lasku.clearing,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and lasku.clearing='$rajaus[$i]' ";
						}
					}

					if ($mukaan == "myyja") {
						if ($group!="") $group .= ",lasku.myyja";
						else $group  .= "lasku.myyja";
						$select .= "lasku.myyja myyja, ";
						$order  .= "lasku.myyja,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$query = "	SELECT group_concat(tunnus) tunnus
										FROM kuka
										WHERE yhtio in ($yhtio)
										and (nimi like '%$rajaus[$i]%' or kuka = '$rajaus[$i]')";
							$osre = mysql_query($query) or pupe_error($query);
							$osrow = mysql_fetch_array($osre);

							if ($osrow["tunnus"] != "") {
								$lisa .= " and lasku.myyja in ($osrow[tunnus]) ";
							}
						}
					}

					if ($mukaan == "laskuittain") {
						if ($group!="") $group .= ",lasku.tunnus";
						else $group .= "lasku.tunnus";
						$select .= "if(lasku.laskunro>0,concat('".t("LASKU").":',lasku.laskunro),concat('".t("TILAUS").":',lasku.tunnus)) laskunumero, ";
						$order  .= "laskunumero,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and lasku.laskunro = '$rajaus[$i]' ";
						}
					}

					if ($mukaan == "asiakkaan_tilausnumeroittain") {
						if ($group!="") $group .= ",lasku.asiakkaan_tilausnumero";
						else $group .= "lasku.asiakkaan_tilausnumero";
						$select .= "lasku.asiakkaan_tilausnumero asiakkaan_tilausnumero, ";
						$order  .= "asiakkaan_tilausnumero,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and lasku.asiakkaan_tilausnumero = '$rajaus[$i]' ";
						}
					}

					if ($mukaan == "varastoittain") {
						if ($group!="") $group .= ",varastopaikat.nimitys";
						else $group  .= "varastopaikat.nimitys";
						$select .= "varastopaikat.nimitys Varasto, ";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and varastopaikat.nimitys = '$rajaus[$i]' ";
						}

						$varastojoin = "LEFT JOIN varastopaikat ON varastopaikat.yhtio = tilausrivi.yhtio
										and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
										and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))";
					}

					if ($ajotapa != "lasku" and $mukaan == "kantaasiakkaittain") {
						if ($group != "") $group .= ",kantaasiakas.avainsana";
						else $group  .= "kantaasiakas.avainsana";
						$select .= "kantaasiakas.avainsana Kantaasiakastunnus, ";
						$order  .= "kantaasiakas.avainsana,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and kantaasiakas.avainsana = '$rajaus[$i]' ";
						}

						$kantaasiakasjoin	= " JOIN laskun_lisatiedot lasklisa ON (lasklisa.yhtio = lasku.yhtio AND lasklisa.otunnus = lasku.tunnus) ";
						$kantaasiakasjoin  .= " JOIN asiakkaan_avainsanat kantaasiakas ON (kantaasiakas.yhtio = lasku.yhtio AND kantaasiakas.laji = 'kantaasiakastunnus' AND kantaasiakas.liitostunnus = lasku.liitostunnus AND kantaasiakas.avainsana = lasklisa.kantaasiakastunnus) ";
					}

					if ($mukaan == "maksuehdoittain") {
						if ($group!="") $group .= ",lasku.maksuteksti";
						else $group  .= "lasku.maksuteksti";
						$select .= "lasku.maksuteksti maksuehto, ";
						$order  .= "lasku.maksuteksti,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and lasku.maksuteksti='$rajaus[$i]' ";
						}
					}
				}

				if ($sarjanumerot != '') {
					$select .= "group_concat(concat(tilausrivi.tunnus,'#',tilausrivi.kpl)) sarjanumero, ";
				}

				if ($order != "") {
					$order = substr($order,0,-1);
				}
				else {
					$order = "1";
				}

				if ($tilrivikomm != "") {
					if ($group!="") $group .= ",tilausrivi.tunnus";
					else $group  .= "tilausrivi.tunnus";
					$select .= "tilausrivi.kommentti, ";
					$gluku++;
				}

				if (is_array($mul_oasiakasosasto) and count($mul_oasiakasosasto) > 0) {
					$sel_oasiakasosasto = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_oasiakasosasto))."')";
					$lisa .= " and asiakas.osasto in $sel_oasiakasosasto ";
				}

				if (is_array($mul_asiakasryhma) and count($mul_asiakasryhma) > 0) {
					$sel_asiakasryhma = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_asiakasryhma))."')";
					$lisa .= " and asiakas.ryhma in $sel_asiakasryhma ";
				}

				if (is_array($mul_osasto) and count($mul_osasto) > 0) {
					$sel_osasto = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_osasto))."')";
					$lisa .= " and tuote.osasto in $sel_osasto ";
				}

				if (is_array($mul_try) and count($mul_try) > 0) {
					$sel_tuoteryhma = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_try))."')";
					$lisa .= " and tuote.try in $sel_tuoteryhma ";
				}

				if (is_array($mul_piiri) and count($mul_piiri) > 0) {
					$sel_piiri = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_piiri))."')";

					if ($piirivalinta == "asiakas") {
						$lisa .= " and asiakas.piiri in $sel_piiri ";
					}
					else {
						$lisa .= " and lasku.piiri in $sel_piiri ";
					}
				}

				if (is_array($mul_kustp) and count($mul_kustp) > 0) {
					$sel_kustp = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_kustp))."')";
					$lisa .= " and (asiakas.kustannuspaikka in $sel_kustp or tuote.kustp in $sel_kustp) ";
				}

				if ($toimittaja != "") {
					$query = "SELECT tuoteno from tuotteen_toimittajat where yhtio in ($yhtio) and liitostunnus='$toimittajaid'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) > 0) {
						$lisa .= " and tilausrivi.tuoteno in (";
						while ($toimirow = mysql_fetch_array($result)) {
							$lisa .= "'$toimirow[tuoteno]',";
						}
						$lisa = substr($lisa,0,-1).")";
					}
					else {
						echo "<font class='error'>Toimittajan $toimittaja tuotteita ei l�ytynyt! Ajetaan ajo ilman rajausta!</font><br><br>";
						$toimittaja = "";
					}
				}

				if ($asiakas != "") {
					$query = "SELECT group_concat(tunnus) from asiakas where yhtio in ($yhtio) and ytunnus = '$asiakas' $asiakasrajaus";
					$result = mysql_query($query) or pupe_error($query);
					$asiakasrow = mysql_fetch_array($result);

					if (trim($asiakasrow[0]) != "") {
						$lisa .= " and lasku.liitostunnus in ($asiakasrow[0]) ";
					}
					else {
						echo "<font class='error'>Asiakasta $asiakas ei l�ytynyt! Ajetaan ajo ilman rajausta!</font><br><br>";
						$asiakas = "";
					}
				}
				else if ($asiakasnro != "") {
					$query = "SELECT group_concat(tunnus) from asiakas where yhtio in ($yhtio) and asiakasnro = '$asiakasnro' $asiakasrajaus";
					$result = mysql_query($query) or pupe_error($query);
					$asiakasrow = mysql_fetch_array($result);

					if (trim($asiakasrow[0]) != "") {
						$lisa .= " and lasku.liitostunnus in ($asiakasrow[0]) ";
					}
					else {
						echo "<font class='error'>Asiakasta $asiakasnro ei l�ytynyt! Ajetaan ajo ilman rajausta!</font><br><br>";
						$asiakas = "";
					}
				}

				if (isset($tuotteet_lista) and $tuotteet_lista != '') {
					$tuotteet = explode("\n", $tuotteet_lista);
					$tuoterajaus = "";
					foreach($tuotteet as $tuote) {
						if (trim($tuote) != '') {
							$tuoterajaus .= "'".trim($tuote)."',";
						}
					}
					$lisa .= "and tuote.tuoteno in (".substr($tuoterajaus, 0, -1).") ";
				}

				if (isset($status) and $status != '') {
					$lisa .= " and tuote.status = '".(string) $status."' ";
				}

				if (isset($verkkokaupat) and $verkkokaupat != '') {
					$lisa .= " and lasku.laatija = 'Magento' ";
				}

				$vvaa = $vva - '1';
				$vvll = $vvl - '1';

				if ($naytakaikkityypit != "") {
					$lisa .= " and tuote.tuotetyyppi in ('','R','K','M','N') ";
				}
				else {
					$lisa .= " and tuote.tuotetyyppi in ('','R','K','M') ";
				}

				if ($kateprossat != "") {
					$katelisanyt = " 0 kateprosnyt, ";
					$katelisaed  = " 0 kateprosed, ";
				}
				else {
					$katelisanyt = "";
					$katelisaed  = "";
				}

				if ($nettokateprossat != "") {
					$nettokatelisanyt = " 0 nettokateprosnyt, ";
					$nettokatelisaed  = " 0 nettokateprosed, ";
				}
				else {
					$nettokatelisanyt = "";
					$nettokatelisaed  = "";
				}

				if ($eiOstSarjanumeroita != "") {
					$trlisatiedot = " JOIN tilausrivin_lisatiedot use index (tilausrivitunnus) ON tilausrivin_lisatiedot.yhtio=lasku.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus and tilausrivin_lisatiedot.osto_vai_hyvitys!='O'";
				}
				else {
					$trlisatiedot = "";
				}

				if ($naytaennakko == "") {
					$lisa .= " and tilausrivi.tuoteno !='$yhtiorow[ennakkomaksu_tuotenumero]'";
				}

				// Jos ei olla valittu mit��n
				if ($group == "") {
					$select = "tuote.yhtio, ";
					$group = "lasku.yhtio";
				}

				if ($ajotapanlisa == "erikseen") {
					$tilauslisa3 = ", if(tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt>0,'Veloitus','Hyvitys') rivityyppi";
					$group .= ", rivityyppi";
				}
				else {
					$tilauslisa3 = "";
				}

				$query = "	SELECT $select";

				// Katotaan mist� kohtaa query� alkaa varsinaiset numerosarakkeet (HUOM: toi ', ' pilkkuspace erottaa sarakket toisistaan)
				$data_start_index = substr_count($select, ", ");

				// generoidaan selectit
				if ($kuukausittain != "") {
					$MONTH_ARRAY  	= array(1=> t('Tammikuu'), t('Helmikuu'), t('Maaliskuu'), t('Huhtikuu'), t('Toukokuu'), t('Kes�kuu'), t('Hein�kuu'), t('Elokuu'), t('Syyskuu'), t('Lokakuu'), t('Marraskuu'), t('Joulukuu'));

					$startmonth	= date("Ymd", mktime(0, 0, 0, $kka, 1,  $vva));
					$endmonth 	= date("Ymd", mktime(0, 0, 0, $kkl, 1,  $vvl));

					for ($i = $startmonth;  $i <= $endmonth;) {

						$alku  = date("Y-m-d", mktime(0, 0, 0, substr($i, 4, 2), substr($i, 6, 2),  substr($i, 0, 4)));
						$loppu = date("Y-m-d", mktime(0, 0, 0, substr($i, 4, 2), date("t", mktime(0, 0, 0, substr($i, 4, 2), substr($i, 6, 2),  substr($i, 0, 4))),  substr($i, 0, 4)));

						$alku_ed  = date("Y-m-d", mktime(0, 0, 0, substr($i, 4, 2), substr($i, 6, 2),  substr($i, 0, 4)-1));
						$loppu_ed = date("Y-m-d", mktime(0, 0, 0, substr($i, 4, 2), date("t", mktime(0, 0, 0, substr($i, 4, 2), substr($i, 6, 2),  substr($i, 0, 4))),  substr($i, 0, 4)-1));

						// MYYNTI
						if ($ajotapa == 'tilausjaauki' or $ajotapa == 'tilausauki') {
							$query .= " sum(if(lasku.luontiaika >= '$alku 00:00:00' and lasku.luontiaika <= '$loppu 23:59:59' and tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) '".$MONTH_ARRAY[(substr($i, 4, 2)*1)]." ".substr($i, 0, 4)." ".t("Laskuttamatta")."', ";
						}

						if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
							$query .= " sum(if(lasku.luontiaika >= '$alku 00:00:00' and lasku.luontiaika <= '$loppu 23:59:59', if(tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, tilausrivi.rivihinta), 0)) '".substr($MONTH_ARRAY[(substr($i, 4, 2)*1)], 0, 3)." ".substr($i, 0, 4)." ".t("Myynti")."', ";
						}
						elseif ($ajotapa != 'tilausauki') {
							$query .= " sum(if(tilausrivi.laskutettuaika >= '$alku' and tilausrivi.laskutettuaika <= '$loppu', tilausrivi.rivihinta, 0)) '".substr($MONTH_ARRAY[(substr($i, 4, 2)*1)], 0, 3)." ".substr($i, 0, 4)." ".t("Myynti")."', ";
						}

						if ($ajotapa == 'tilausjaauki') {
							$query .= " sum(if(lasku.luontiaika >= '$alku 00:00:00' and lasku.luontiaika <= '$loppu 23:59:59' and tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) +
										sum(if(tilausrivi.laskutettuaika >= '$alku' and tilausrivi.laskutettuaika <= '$loppu', tilausrivi.rivihinta, 0)) '".substr($MONTH_ARRAY[(substr($i, 4, 2)*1)], 0, 3)." ".substr($i, 0, 4)." ".t("Myyntiyht")."', ";
						}

						if ($piiloed == "") {
							// MYYNTIED
							if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
								$query .= " sum(if(lasku.luontiaika >= '$alku_ed 00:00:00' and lasku.luontiaika <= '$loppu_ed 23:59:59', if(tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, tilausrivi.rivihinta), 0)) '".substr($MONTH_ARRAY[(substr($i, 4, 2)*1)], 0, 3)." ".(substr($i, 0, 4)-1)." ".t("Myynti")."', ";
							}
							elseif ($ajotapa != 'tilausauki')  {
								$query .= " sum(if(tilausrivi.laskutettuaika >= '$alku_ed' and tilausrivi.laskutettuaika <= '$loppu_ed', tilausrivi.rivihinta, 0)) '".substr($MONTH_ARRAY[(substr($i, 4, 2)*1)], 0, 3)." ".(substr($i, 0, 4)-1)." ".t("Myynti")."', ";
							}
						}

						$i = date("Ymd", mktime(0, 0, 0, substr($i, 4, 2)+1, 1,  substr($i, 0, 4)));
					}

					// Vika pilkku pois
					$query = substr($query, 0 , -2);
				}
				else {

					//MYYNTI
					if ($piilota_myynti == "") {
						if ($ajotapa == 'tilausjaauki' or $ajotapa == 'tilausauki') {
							$query .= "sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59' and tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) myyntilaskuttamattanyt, ";
						}

						if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
							$query .= "	sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59', if(tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, tilausrivi.rivihinta), 0)) myyntinyt, ";
						}
						elseif ($ajotapa != 'tilausauki') {
							$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta, 0)) myyntinyt, ";
						}

						if ($ajotapa == 'tilausjaauki') {
							$query .= " sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59' and tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) +
										sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta, 0)) myyntinytyht, ";
						}

						//MYYNTIED
						if ($piiloed == "") {
							if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
								$query .= " sum(if(lasku.luontiaika >= '$vvaa-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvll-$kkl-$ppl 23:59:59', if(tilausrivi.uusiotunnus = 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, tilausrivi.rivihinta), 0)) myyntied, ";

								$query .= "	round(sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59', if(tilausrivi.uusiotunnus = 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, tilausrivi.rivihinta), 0)) /
												sum(if(lasku.luontiaika >= '$vvaa-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvll-$kkl-$ppl 23:59:59', if(tilausrivi.uusiotunnus = 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, tilausrivi.rivihinta), 0)), 2) myyntiind, ";
							}
							elseif ($ajotapa != 'tilausauki')  {
								$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.rivihinta, 0)) myyntied, ";
								$query .= "	round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta, 0)) / sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.rivihinta, 0)), 2) myyntiind, ";
							}
						}
					}

					if ($oikeurow['paivitys'] == 1) {

						if ($piilota_nettokate == "") {
							//NETTOKATE
							if ($ajotapa == 'tilausjaauki' or $ajotapa == 'tilausauki') {
								$query .= " sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59' and tilausrivi.uusiotunnus = 0,
									 		(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)) -
									 		(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(asiakas.kuluprosentti, 0) / 100) -
									 		(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(toimitustapa.kuluprosentti, 0) / 100) -
									 		(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(yhtio.kuluprosentti, 0) / 100) -
									 		(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(tuote.kuluprosentti, 0) / 100), 0)) nettokatelaskuttamattanyt, ";
							}

							if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
								$query .= "	sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59',
											(if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt))) -
											(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(asiakas.kuluprosentti, 0) / 100) -
											(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(toimitustapa.kuluprosentti, 0) / 100) -
											(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(yhtio.kuluprosentti, 0) / 100) -
											(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(tuote.kuluprosentti, 0) / 100), 0)) nettokatenyt, ";
							}
							elseif ($ajotapa != 'tilausauki') {
								$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate - (tilausrivi.rivihinta * IFNULL(asiakas.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(toimitustapa.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(tuote.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(yhtio.kuluprosentti, 0)/100), 0)) nettokatenyt, ";
							}

							if ($ajotapa == 'tilausjaauki') {
								$query .= "sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59' and tilausrivi.uusiotunnus = 0,
									 		(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)) -
									 		(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(asiakas.kuluprosentti, 0) / 100) -
									 		(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(toimitustapa.kuluprosentti, 0) / 100) -
									 		(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(yhtio.kuluprosentti, 0) / 100) -
									 		(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(tuote.kuluprosentti, 0) / 100), 0)) +
											sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate - (tilausrivi.rivihinta * IFNULL(asiakas.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(toimitustapa.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(tuote.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(yhtio.kuluprosentti, 0)/100), 0)) nettokateyhtnyt, ";
							}

							//NETTOKATE ED
							if ($piiloed == "") {

								if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
									$query .= "	sum(if(lasku.luontiaika >= '$vvaa-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvll-$kkl-$ppl 23:59:59',
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt))) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(asiakas.kuluprosentti, 0) / 100) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(toimitustapa.kuluprosentti, 0) / 100) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(yhtio.kuluprosentti, 0) / 100) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(tuote.kuluprosentti, 0) / 100), 0)) nettokateed, ";

									$query .= "	sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59',
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt))) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(asiakas.kuluprosentti, 0) / 100) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(toimitustapa.kuluprosentti, 0) / 100) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(yhtio.kuluprosentti, 0) / 100) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(tuote.kuluprosentti, 0) / 100), 0)) /
												sum(if(lasku.luontiaika >= '$vvaa-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvll-$kkl-$ppl 23:59:59',
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt))) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(asiakas.kuluprosentti, 0) / 100) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(toimitustapa.kuluprosentti, 0) / 100) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(yhtio.kuluprosentti, 0) / 100) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(tuote.kuluprosentti, 0) / 100), 0)) nettokateind, ";
								}
								elseif ($ajotapa != 'tilausauki')  {
									$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.kate - (tilausrivi.rivihinta * IFNULL(asiakas.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(toimitustapa.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(tuote.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(yhtio.kuluprosentti, 0)/100), 0)) nettokateed, ";
									$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',  tilausrivi.kate - (tilausrivi.rivihinta * IFNULL(asiakas.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(toimitustapa.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(tuote.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(yhtio.kuluprosentti, 0)/100), 0)) / sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.kate - (tilausrivi.rivihinta * IFNULL(asiakas.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(toimitustapa.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(tuote.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(yhtio.kuluprosentti, 0)/100), 0)) nettokateind, ";
								}
							}

							//nettokateprossa n�ytet��n vain jos myynti ja nettokate on valittu my�s n�ytett�v�ksi
							if ($piilota_myynti == "" and $piilota_nettokate == "") {
								//NETTOKATEPROS
								$query .= $nettokatelisanyt;

								//NETTOKATEPROSED
								if ($piiloed == "") {
									$query .= $nettokatelisaed;
								}
							}

						}

						if ($piilota_kate == "") {
							//KATE
							if ($ajotapa == 'tilausjaauki' or $ajotapa == 'tilausauki') {
								$query .= "sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59' and tilausrivi.uusiotunnus = 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt), 0)) katelaskuttamattanyt, ";
							}

							if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
								$query .= "	sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59', if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)), 0)) katenyt, ";
							}
							elseif ($ajotapa != 'tilausauki') {
								$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate, 0)) katenyt, ";
							}

							if ($ajotapa == 'tilausjaauki') {
								$query .= " sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59' and tilausrivi.uusiotunnus = 0, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt), 0)) +
											sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate, 0)) kateyhtnyt, ";
							}

							if ($piiloed == "") {
								if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
									$query .= "	sum(if(lasku.luontiaika >= '$vvaa-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvll-$kkl-$ppl 23:59:59', if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)), 0)) kateed, ";
									$query .= "	round(sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59', if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)), 0)) /sum(if(lasku.luontiaika >= '$vvaa-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvll-$kkl-$ppl 23:59:59', if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)), 0)), 2) kateind, ";
								}
								elseif ($ajotapa != 'tilausauki') {
									$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.kate, 0)) kateed, ";
									$query .= "	round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate, 0)) /sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.kate, 0)), 2) kateind, ";
								}
							}
						}

						//kateprossa n�ytet��n vain jos myynti ja kate on valittu my�s n�ytett�v�ksi
						if ($piilota_myynti == "" and $piilota_kate == "") {
							//KATEPROS
							$query .= $katelisanyt;

							//KATEPROSED
							if ($piiloed == "") {
								$query .= $katelisaed;
							}
						}
					}

					if ($piilota_kappaleet == "") {
						//KPL
						if ($ajotapa == 'tilausjaauki' or $ajotapa == 'tilausauki') {
							$query .= "sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59' and tilausrivi.uusiotunnus = 0, tilausrivi.varattu+tilausrivi.jt, 0)) myykpllaskuttamattanyt, ";
						}

						if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
							$query .= "	sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59', if(tilausrivi.uusiotunnus = 0, tilausrivi.varattu+tilausrivi.jt, tilausrivi.kpl), 0)) myykplnyt, ";
						}
						elseif ($ajotapa != 'tilausauki') {
							$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kpl, 0)) myykplnyt, ";
						}

						if ($ajotapa == 'tilausjaauki') {
							$query .= " sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59' and tilausrivi.uusiotunnus = 0, tilausrivi.varattu+tilausrivi.jt, 0)) +
										sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kpl, 0)) myykplyhtnyt, ";
						}

						//KPLED
						if ($piiloed == "") {
							if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
								$query .= "	sum(if(lasku.luontiaika >= '$vvaa-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvll-$kkl-$ppl 23:59:59', if(tilausrivi.uusiotunnus = 0, tilausrivi.varattu+tilausrivi.jt, tilausrivi.kpl), 0)) myykpled,
											round(sum(if(lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59', if(tilausrivi.uusiotunnus = 0, tilausrivi.varattu+tilausrivi.jt, tilausrivi.kpl), 0)) / sum(if(lasku.luontiaika >= '$vvaa-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvll-$kkl-$ppl 23:59:59', if(tilausrivi.kpl = 0, tilausrivi.varattu+tilausrivi.jt, tilausrivi.kpl), 0)), 2) myykplind, ";
							}
							elseif ($ajotapa != 'tilausauki')  {
								$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.kpl, 0)) myykpled,
											round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kpl, 0)) / sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.kpl, 0)), 2) myykplind, ";
							}
						}
					}
					// Vika pilkku ja space pois
					$query = substr($query, 0 , -2);
				}

				$query .= $tilauslisa3;
				$query .= "	FROM lasku use index (yhtio_tila_tapvm)
							JOIN yhtio ON (yhtio.yhtio = lasku.yhtio)
							JOIN tilausrivi use index ($index) ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.$ouusio=lasku.tunnus and tilausrivi.tyyppi=$tyyppi
							$trlisatiedot
							$varastojoin
							$kantaasiakasjoin
							LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno
							LEFT JOIN asiakas use index (PRIMARY) ON asiakas.yhtio=lasku.yhtio and asiakas.tunnus=lasku.liitostunnus
							LEFT JOIN toimitustapa ON lasku.yhtio=toimitustapa.yhtio and lasku.toimitustapa=toimitustapa.selite
							WHERE lasku.yhtio in ($yhtio)
							$asiakasrajaus and
							lasku.tila in ($tila)";

				//yritet��n saada kaikki tarvittavat laskut mukaan
				$lalku  = date("Y-m-d", mktime(0, 0, 0, $kka-1, $ppa,  $vva));
				$lloppu = date("Y-m-d", mktime(0, 0, 0, $kkl+1, $ppl,  $vvl));
				$lalku_ed  = date("Y-m-d", mktime(0, 0, 0, $kka-1, $ppa,  $vva-1));
				$lloppu_ed = date("Y-m-d", mktime(0, 0, 0, $kkl+1, $ppl,  $vvl-1));

				if ($ajotapa == 'tilausjaauki') {
					$query .= "	and lasku.alatila in ('','A','B','C','D','J','E','F','T','U','X')
								and ((lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59') or (lasku.tapvm >= '$vva-$kka-$ppa' and lasku.tapvm <= '$vvl-$kkl-$ppl') ";

					if ($piiloed == "") {
						$query .= " or (lasku.tapvm >= '$vvaa-$kka-$ppa' and lasku.tapvm <= '$vvll-$kkl-$ppl') ";
					}

					$query .= " ) ";
				}
				elseif ($ajotapa == 'tilausjaaukiluonti') {
					$query .= "	and lasku.alatila in ('','A','B','C','D','J','E','F','T','U','X')
								and ((lasku.luontiaika >= '$lalku 00:00:00' and lasku.luontiaika <= '$lloppu 23:59:59') ";

					if ($piiloed == "") {
						$query .= " or (lasku.luontiaika >= '$lalku_ed 00:00:00' and lasku.luontiaika <= '$lloppu_ed 23:59:59') ";
					}

					$query .= " ) ";
				}
				elseif ($ajotapa == 'tilausauki') {
					$query .= "	and lasku.alatila in ('','A','B','C','D','J','E','F','T','U','X')
								and ((lasku.luontiaika >= '$lalku 00:00:00' and lasku.luontiaika <= '$lloppu 23:59:59') ";

					if ($piiloed == "") {
						$query .= " or (lasku.luontiaika >= '$lalku_ed 00:00:00' and lasku.luontiaika <= '$lloppu_ed 23:59:59') ";
					}

					$query .= " ) ";
				}
				elseif ($ajotapa == 'ennakot') {
					$query .= "	and lasku.alatila = 'A'
								and ((lasku.luontiaika >= '$lalku 00:00:00' and lasku.luontiaika <= '$lloppu 23:59:59') ";

					if ($piiloed == "") {
						$query .= " or (lasku.luontiaika >= '$lalku_ed 00:00:00' and lasku.luontiaika <= '$lloppu_ed 23:59:59') ";
					}

					$query .= " ) ";
				}
				else {
					$query .= "	and lasku.alatila='X'
								and ((lasku.tapvm >= '$vva-$kka-$ppa' and lasku.tapvm <= '$vvl-$kkl-$ppl') ";

					if ($piiloed == "") {
						$query .= " or (lasku.tapvm >= '$vvaa-$kka-$ppa' and lasku.tapvm <= '$vvll-$kkl-$ppl') ";
					}

					$query .= " ) ";
				}

				$query .= "	$lisa
							group by $group
							order by $order";

				// ja sitten ajetaan itte query
				if ($query != "") {

					//echo "<pre>$query</pre><br>";

					$result = mysql_query($query) or pupe_error($query);

					$rivilimitti = 1000;

					if ($vain_excel != "") {
						echo "<font class='error'>".t("Tallenna/avaa tulos exceliss�")."!</font><br><br>";
						$rivilimitti = 0;
					}
					else {
						if (mysql_num_rows($result) > $rivilimitti) {
							echo "<br><font class='error'>".t("Hakutulos oli liian suuri")."!</font><br>";
							echo "<font class='error'>".t("Tallenna/avaa tulos exceliss�")."!</font><br><br>";
						}
					}
				}

				if ($query != "") {
					if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta.php") !== FALSE) {
						if(@include('Spreadsheet/Excel/Writer.php')) {

							//keksit��n failille joku varmasti uniikki nimi:
							list($usec, $sec) = explode(' ', microtime());
							mt_srand((float) $sec + ((float) $usec * 100000));
							$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

							$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
							$workbook->setVersion(8);
							$worksheet =& $workbook->addWorksheet('Sheet 1');

							$format_bold =& $workbook->addFormat();
							$format_bold->setBold();

							$excelrivi = 0;
						}
					}

					echo "<table>";
					echo "<tr>
						<th>".t("Kausi nyt")."</th>
						<td>$ppa</td>
						<td>$kka</td>
						<td>$vva</td>
						<th>-</th>
						<td>$ppl</td>
						<td>$kkl</td>
						<td>$vvl</td>
						</tr>\n";
					echo "<tr>
						<th>".t("Kausi ed")."</th>
						<td>$ppa</td>
						<td>$kka</td>
						<td>$vvaa</td>
						<th>-</th>
						<td>$ppl</td>
						<td>$kkl</td>
						<td>$vvll</td>
						</tr>\n";
					echo "</table><br>";

					if (mysql_num_rows($result) <= $rivilimitti) echo "<table><tr>";

					// echotaan kenttien nimet
					for ($i=0; $i < mysql_num_fields($result); $i++) {
						if (mysql_num_rows($result) <= $rivilimitti) echo "<th>".t(mysql_field_name($result,$i))."</th>";
					}

					if(isset($workbook)) {
						for ($i=0; $i < mysql_num_fields($result); $i++) $worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
						$excelrivi++;
					}

					if (mysql_num_rows($result) <= $rivilimitti) echo "</tr>\n";

					$edluku 		= "x";
					$valisummat 	= array();
					$totsummat  	= array();
					$tarra_aineisto = "";

					if (mysql_num_rows($result) > $rivilimitti) {

						require_once ('inc/ProgressBar.class.php');
						$bar = new ProgressBar();
						$elements = mysql_num_rows($result); // total number of elements to process
						$bar->initialize($elements); // print the empty bar
					}

					while ($row = mysql_fetch_array($result)) {
						if (mysql_num_rows($result) > $rivilimitti) $bar->increase();

						$piilosumma = 0;

						for ($i=$data_start_index; $i < mysql_num_fields($result); $i++) {
							if (is_numeric($row[$i])) {
								$piilosumma += $row[$i];
							}
						}

						// N�ytet��n vain jos halutaan n�hd� kaikki rivit tai summa on > 0
						if ($piilotanollarivit == "" or (float) $piilosumma != 0) {
							if ($osoitetarrat != "" and $row["astunnus"] > 0) {
								$tarra_aineisto .= $row["astunnus"].",";
							}

							if (mysql_num_rows($result) <= $rivilimitti) echo "<tr>";

							// echotaan kenttien sis�lt�
							for ($i=0; $i < mysql_num_fields($result); $i++) {

								// jos kyseessa on tuote
								if (mysql_field_name($result, $i) == "tuoteno") {
									$row[$i] = "<a href='../tuote.php?tee=Z&tuoteno=".urlencode($row[$i])."'>$row[$i]</a>";
								}

								// jos kyseessa on asiakasosasto, haetaan sen nimi
								if (mysql_field_name($result, $i) == "asos") {
									$osre = t_avainsana("ASIAKASOSASTO", "", "and avainsana.selite  = '$row[$i]'", $yhtio);
									$osrow = mysql_fetch_array($osre);

									if ($osrow['selite'] == "") {
										$osrow['selite'] = t("Ei asiakasosastoa");
									}

									$serialisoitavat_muuttujat = $kaikki_muuttujat_array;

									// jos asiakasosostoittain ja asiakasryhmitt�in ruksin on chekattu, osastoa klikkaamalla palataan taaksep�in
									if ($ruksit[10] != '' and $ruksit[20] != '') {
										// Nollataan asiakasosasto sek� asiakaryhm�valinnat
										unset($serialisoitavat_muuttujat["mul_oasiakasosasto"]);
										unset($serialisoitavat_muuttujat["mul_asiakasryhma"]);

										// Nollataan asiakasryhm�ruksi sek� tuotettainruksi
										$serialisoitavat_muuttujat["ruksit"][20] = "";
										$serialisoitavat_muuttujat["ruksit"][80] = "";
									}
									else {
										// jos asiakasosostoittain ja asiakasryhmitt�in ei ole chekattu, osastoa klikkaamalla menn��n eteenp�in
										$serialisoitavat_muuttujat["mul_oasiakasosasto"][$i] = $row[$i];
										$serialisoitavat_muuttujat["ruksit"][20] = "asiakasryhma";
									}

									$row[$i] = "<a href='myyntiseuranta.php?kaikki_parametrit_serialisoituna=".urlencode(serialize($serialisoitavat_muuttujat))."'>$osrow[selite] $osrow[selitetark]</a>";
								}

								// jos kyseessa on piiri, haetaan sen nimi
								if (mysql_field_name($result, $i) == "aspiiri") {
									$osre = t_avainsana("PIIRI", "", "and avainsana.selite  = '$row[$i]'", $yhtio);
									$osrow = mysql_fetch_array($osre);

									if ($osrow['selitetark'] != "" and $osrow['selite'] != $osrow['selitetark']) {
										$row[$i] = $row[$i] ." ". $osrow['selitetark'];
									}
								}

								// jos kyseessa on asiakasryhma, haetaan sen nimi
								if (mysql_field_name($result, $i) == "asry") {
									$osre = t_avainsana("ASIAKASRYHMA", "", "and avainsana.selite  = '$row[$i]'", $yhtio);
									$osrow = mysql_fetch_array($osre);

									if ($osrow['selite'] == "") {
										$osrow['selite'] = t("Ei asiakasryhm��");
									}

									$serialisoitavat_muuttujat = $kaikki_muuttujat_array;

									// jos asiakasosastot, asiakasryhm�t ja tuottetain on valittu, menn��n taaksep�in
									if ($ruksit[10] != '' and $ruksit[20] != '' and $ruksit[80] != '') {
										unset($serialisoitavat_muuttujat["mul_asiakasryhma"]);
										$serialisoitavat_muuttujat["ruksit"][80] = "";
									}
									else {
										// jos vain asiakasosastot, asiakasryhm�t ja tuottetain on valittu, menn��n eteenp�in
										$serialisoitavat_muuttujat["mul_asiakasryhma"][$i] = $row[$i];
										$serialisoitavat_muuttujat["ruksit"][20] = "asiakasryhma";
										$serialisoitavat_muuttujat["ruksit"][80] = "tuote";
									}

									$row[$i] = "<a href='myyntiseuranta.php?kaikki_parametrit_serialisoituna=".urlencode(serialize($serialisoitavat_muuttujat))."'>$osrow[selite] $osrow[selitetark]</a>";
								}

								// jos kyseessa on tuoteosasto, haetaan sen nimi
								if (mysql_field_name($result, $i) == "tuos") {
									$osre = t_avainsana("OSASTO", "", "and avainsana.selite  = '$row[$i]'", $yhtio);
									$osrow = mysql_fetch_array($osre);

									if ($osrow['selite'] == "") {
										$osrow['selite'] = t("Ei tuoteosastoa");
									}

									$serialisoitavat_muuttujat = $kaikki_muuttujat_array;

									// jos tuoteosostoittain ja tuoteryhmitt�in ruksin on chekattu, osastoa klikkaamalla palataan taaksep�in
									if ($ruksit[40] != '' and $ruksit[50] != '') {
										// Nollataan asiakasosasto sek� asiakaryhm�valinnat
										unset($serialisoitavat_muuttujat["mul_osasto"]);
										unset($serialisoitavat_muuttujat["mul_try"]);

										// Nollataan tuoteryhm�ruksi sek� tuotettainruksi
										$serialisoitavat_muuttujat["ruksit"][50] = "";
										$serialisoitavat_muuttujat["ruksit"][80] = "";
									}
									else {
										// jos tuoteosostoittain ja tuoteryhmitt�in ei ole chekattu, osastoa klikkaamalla menn��n eteenp�in
										$serialisoitavat_muuttujat["mul_osasto"][$i] = $row[$i];
										$serialisoitavat_muuttujat["ruksit"][50] = "tuoteryhma";
									}

									$row[$i] = "<a href='myyntiseuranta.php?kaikki_parametrit_serialisoituna=".urlencode(serialize($serialisoitavat_muuttujat))."'>$osrow[selite] $osrow[selitetark]</a>";
								}

								// jos kyseessa on tuoteosasto, haetaan sen nimi
								if (mysql_field_name($result, $i) == "tuoteryhm�") {
									$osre = t_avainsana("TRY", "", "and avainsana.selite  = '$row[$i]'", $yhtio);
									$osrow = mysql_fetch_array($osre);

									if ($osrow['selite'] == "") {
										$osrow['selite'] = t("Ei tuoteryhm��");
									}

									$serialisoitavat_muuttujat = $kaikki_muuttujat_array;

									// jos tuoteosastot, tuoteryhm�t ja tuottetain on valittu, menn��n taaksep�in
									if ($ruksit[40] != '' and $ruksit[50] != '' and $ruksit[80] != '') {
										unset($serialisoitavat_muuttujat["mul_try"]);
										$serialisoitavat_muuttujat["ruksit"][80] = "";
									}
									else {
										// jos vain tuoteosastot, tuoteryhm�t ja tuottetain on valittu, menn��n eteenp�in
										$serialisoitavat_muuttujat["mul_try"][$i] = $row[$i];
										$serialisoitavat_muuttujat["ruksit"][40] = "osasto";
										$serialisoitavat_muuttujat["ruksit"][50] = "tuoteryhma";
										$serialisoitavat_muuttujat["ruksit"][80] = "tuote";
									}

									$row[$i] = "<a href='myyntiseuranta.php?kaikki_parametrit_serialisoituna=".urlencode(serialize($serialisoitavat_muuttujat))."'>$osrow[selite] $osrow[selitetark]</a>";
								}

								// jos kyseessa on myyj�, haetaan sen nimi
								if (mysql_field_name($result, $i) == "tuotemyyja" or mysql_field_name($result, $i) == "asiakasmyyja") {
									$query = "	SELECT nimi
												FROM kuka
												WHERE yhtio in ($yhtio)
												and myyja = '$row[$i]'
												AND myyja > 0
												limit 1";
									$osre = mysql_query($query) or pupe_error($query);

									if (mysql_num_rows($osre) == 1) {
										$osrow = mysql_fetch_array($osre);
										$row[$i] = $row[$i] ." ". $osrow['nimi'];
									}
								}

								// jos kyseessa on myyj�, haetaan sen nimi
								if (mysql_field_name($result, $i) == "myyja") {
									$query = "	SELECT nimi
												FROM kuka
												WHERE yhtio in ($yhtio) and tunnus='$row[$i]'";
									$osre = mysql_query($query) or pupe_error($query);

									if (mysql_num_rows($osre) == 1) {
										$osrow = mysql_fetch_array($osre);
										$row[$i] = $osrow['nimi'];
									}
									else {
										$row[$i] = t("Tyhj�");
									}
								}

								// jos kyseessa on ostaja, haetaan sen nimi
								if (mysql_field_name($result, $i) == "tuoteostaja") {
									$query = "	SELECT nimi
												FROM kuka
												WHERE yhtio in ($yhtio)
												and myyja = '$row[$i]'
												AND myyja > 0
												limit 1";
									$osre = mysql_query($query) or pupe_error($query);
									if (mysql_num_rows($osre) == 1) {
										$osrow = mysql_fetch_array($osre);
										$row[$i] = $row[$i] ." ". $osrow['nimi'];
									}
								}

								// jos kyseessa on toimittaja, haetaan nimi/nimet
								if (mysql_field_name($result, $i) == "toimittaja") {
									// fixataan mysql 'in' muotoon
									$toimittajat = "'".str_replace(",","','",$row[$i])."'";
									$query = "	SELECT group_concat(concat_ws(' / ',ytunnus,nimi)) nimi
												FROM toimi
												WHERE yhtio in ($yhtio) and tunnus in ($toimittajat)";
									$osre = mysql_query($query) or pupe_error($query);
									if (mysql_num_rows($osre) == 1) {
										$osrow = mysql_fetch_array($osre);
										$row[$i] = $osrow['nimi'];
									}
								}

								// kateprossa
								if (mysql_field_name($result, $i) == "kateprosnyt") {
									if ($row["myyntinyt"] != 0) {
										$row[$i] = round($row["katenyt"] / abs($row["myyntinyt"]) * 100, 2);
									}
									else {
										if ($ajotapa == "tilausauki" and $row["myyntilaskuttamattanyt"] != 0) {
											$row[$i] = round($row["katenyt"] / abs($row["myyntilaskuttamattanyt"]) * 100, 2);
										}
										else {
											$row[$i] = 0;
										}
									}
								}

								// kateprossa
								if (mysql_field_name($result, $i) == "kateprosed") {
									if ($row["myyntied"] != 0) {
										$row[$i] = round($row["kateed"] / abs($row["myyntied"]) * 100, 2);
									}
									else {
										$row[$i] = 0;
									}
								}

								// nettokateprossa
								if (mysql_field_name($result, $i) == "nettokateprosnyt") {
									if ($row["myyntinyt"] != 0) {
										$row[$i] = round($row["nettokatenyt"] / abs($row["myyntinyt"]) * 100, 2);
									}
									else {
										$row[$i] = 0;
									}
								}

								// nettokateprossa
								if (mysql_field_name($result, $i) == "nettokateprosed") {
									if ($row["myyntied"] != 0) {
										$row[$i] = round($row["nettokateed"] / abs($row["myyntied"]) * 100, 2);
									}
									else {
										$row[$i] = 0;
									}
								}

								// kustannuspaikka
								if (mysql_field_name($result, $i) == "kustpaikka") {
									// n�ytet��n soveltuvat kustannuspaikka
									$query = "	SELECT nimi
												FROM kustannuspaikka
												WHERE yhtio = '$kukarow[yhtio]'
												and tunnus = '$row[$i]'";
									$osre = mysql_query($query) or pupe_error($query);

									if (mysql_num_rows($osre) == 1) {
										$osrow = mysql_fetch_array($osre);
										$row[$i] = $osrow['nimi'];
									}
								}

								// jos kyseessa on sarjanumero
								if (mysql_field_name($result, $i) == "sarjanumero") {
									$sarjat = explode(",", $row[$i]);

									$row[$i] = "";

									foreach($sarjat as $sarja) {
										list($s,$k) = explode("#", $sarja);

										$query = "	SELECT osto_vai_hyvitys
													FROM tilausrivin_lisatiedot
													WHERE yhtio in ($yhtio)
													and tilausrivitunnus = '$s'";
										$rilires = mysql_query($query) or pupe_error($query);
										$rilirow = mysql_fetch_array($rilires);

										if ($k > 0 or ($k < 0 and $rilirow["osto_vai_hyvitys"] == "")) {
											$tunken = "myyntirivitunnus";
										}
										else {
											$tunken = "ostorivitunnus";
										}

										$query = "	SELECT sarjanumero
													FROM sarjanumeroseuranta
													WHERE yhtio in ($yhtio)
													and $tunken=$s";
										$osre = mysql_query($query) or pupe_error($query);

										if (mysql_num_rows($osre) > 0) {
											$osrow = mysql_fetch_array($osre);
											$row[$i] .= "<a href='../tilauskasittely/sarjanumeroseuranta.php?sarjanumero_haku=".urlencode($osrow["sarjanumero"])."' target='_top'>".$osrow['sarjanumero']."</a><br>";
										}
									}
									$row[$i] = substr($row[$i], 0, -4);
								}

								// jos kyseessa on varastonarvo
								if (mysql_field_name($result, $i) == "laskunumero") {
									list($laskalk, $lasklop) = explode(":", $row[$i]);

									$row[$i] = $laskalk.":<a href='{$palvelin2}raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=&laskunro=$lasklop' target='_top'>$lasklop</a>";
								}

								// jos kyseessa on varastonarvo
								if (mysql_field_name($result, $i) == "varastonarvo") {
									list($varvo, $kierto, $varaston_saldo) = vararvo($row["tuoteno"], $vvl, $kkl, $ppl);
									$row[$i] = $varvo;
								}

								// jos kyseessa on varastonkierto
								if (mysql_field_name($result, $i) == "kierto") {
									$row[$i] = $kierto;
								}

								// jos kyseessa on varaston saldo
								if (mysql_field_name($result, $i) == "varastonkpl") {
									$row[$i] = $varaston_saldo;
								}

								// Jos gruupataan enemm�n kuin yksi taso niin tehd��n v�lisumma
								if ($gluku > 1 and $edluku != $row[0] and $edluku != 'x' and $piiyhteensa == '' and strpos($group, ',') !== FALSE and substr($group, 0, 13) != "tuote.tuoteno") {
									$excelsarake = $myyntiind = $kateind = $nettokateind = $myykplind = 0;

									foreach($valisummat as $vnim => $vsum) {
										if ((string) $vsum != '') {
											$vsum = sprintf("%.2f", $vsum);
										}
										if ($vnim == "kateprosnyt") {
											if ($valisummat["myyntinyt"] <> 0) 		$vsum = round($valisummat["katenyt"] / abs($valisummat["myyntinyt"]) * 100, 2);
										}
										if ($vnim == "kateprosed") {
											if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["kateed"] / abs($valisummat["myyntied"]) * 100, 2);
										}
										if ($vnim == "nettokateprosnyt") {
											if ($valisummat["myyntinyt"] <> 0) 		$vsum = round($valisummat["nettokatenyt"] / abs($valisummat["myyntinyt"]) * 100, 2);
										}
										if ($vnim == "nettokateprosed") {
											if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["nettokateed"] / abs($valisummat["myyntied"]) * 100, 2);
										}
										if ($vnim == "myyntiind") {
											if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["myyntinyt"] / $valisummat["myyntied"],2);
										}
										if ($vnim == "kateind") {
											if ($valisummat["kateed"] <> 0) 		$vsum = round($valisummat["katenyt"] / $valisummat["kateed"],2);
										}
										if ($vnim == "nettokateind") {
											if ($valisummat["nettokateed"] <> 0) 	$vsum = round($valisummat["nettokatenyt"] / $valisummat["nettokateed"],2);
										}
										if ($vnim == "myykplind") {
											if ($valisummat["myykpled"] <> 0)		$vsum = round($valisummat["myykplnyt"] / $valisummat["myykpled"],2);
										}

										if (mysql_num_rows($result) <= $rivilimitti) echo "<td class='tumma' align='right'>$vsum</td>";

										if(isset($workbook)) {
											$worksheet->writeNumber($excelrivi, $excelsarake, $vsum);
										}

										$excelsarake++;

									}
									$excelrivi++;
									if (mysql_num_rows($result) <= $rivilimitti) echo "</tr><tr>";

									$valisummat = array();
								}
								$edluku = $row[0];

								// hoidetaan pisteet pilkuiksi!!
								if ((is_numeric($row[$i]) and (mysql_field_type($result,$i) == 'real' or mysql_field_type($result,$i) == 'int' or substr(mysql_field_name($result, $i),0 ,4) == 'kate')) and mysql_field_name($result, $i) != 'astunnus') {
									if (mysql_num_rows($result) <= $rivilimitti) echo "<td valign='top' align='right'>".sprintf("%.02f",$row[$i])."</td>";

									if(isset($workbook)) {
										$worksheet->writeNumber($excelrivi, $i, sprintf("%.02f",$row[$i]));
									}
								}
								elseif (mysql_field_name($result, $i) == 'sarjanumero') {
									if (mysql_num_rows($result) <= $rivilimitti) echo "<td valign='top'>$row[$i]</td>";

									if(isset($workbook)) {
										$worksheet->writeString($excelrivi, $i, strip_tags(str_replace("<br>", "\n", $row[$i])));
									}
								}
								else {
									if (mysql_num_rows($result) <= $rivilimitti) echo "<td valign='top'>$row[$i]</td>";

									if(isset($workbook)) {
										$worksheet->writeString($excelrivi, $i, strip_tags(str_replace("<br>", " / ", $row[$i])));
									}
								}
							}

							if (mysql_num_rows($result) <= $rivilimitti) echo "</tr>\n";
							$excelrivi++;

							for ($i=0; $i < mysql_num_fields($result); $i++) {

								if($i < substr_count($select, ", ")) {
									$valisummat[mysql_field_name($result, $i)] = "";
									$totsummat[mysql_field_name($result, $i)]  = "";
								}
								else {
									$valisummat[mysql_field_name($result, $i)] += $row[mysql_field_name($result, $i)];
									$totsummat[mysql_field_name($result, $i)]  += $row[mysql_field_name($result, $i)];
								}
							}
						}
					}

					$apu = mysql_num_fields($result)-11;

					if ($ajotapanlisa == "erikseen") {
						$apu -= 1;
					}

					if ($ajotapa == 'tilausjaauki') {
						$apu -= 2;
					}

					if ($kateprossat != "") {
						$apu -= 2;
					}

					if ($nettokateprossat != "") {
						$apu -= 2;
					}

					// jos gruupataan enemm�n kuin yksi taso niin tehd��n v�lisumma
					if ($gluku > 1 and $mukaan != 'tuote' and $piiyhteensa == '') {

						if (mysql_num_rows($result) <= $rivilimitti) echo "<tr>";

						$excelsarake = $myyntiind = $kateind = $nettokateind = $myykplind = 0;

						foreach($valisummat as $vnim => $vsum) {
							if ((string) $vsum != '') {
								$vsum = sprintf("%.2f", $vsum);
							}
							if ($vnim == "kateprosnyt") {
								if ($valisummat["myyntinyt"] <> 0) 		$vsum = round($valisummat["katenyt"] / abs($valisummat["myyntinyt"]) * 100, 2);
							}
							if ($vnim == "kateprosed") {
								if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["kateed"] / abs($valisummat["myyntied"]) * 100, 2);
							}
							if ($vnim == "nettokateprosnyt") {
								if ($valisummat["myyntinyt"] <> 0) 		$vsum = round($valisummat["nettokatenyt"] / abs($valisummat["myyntinyt"]) * 100, 2);
							}
							if ($vnim == "nettokateprosed") {
								if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["nettokateed"] / abs($valisummat["myyntied"]) * 100, 2);
							}
							if ($vnim == "myyntiind") {
								if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["myyntinyt"] / $valisummat["myyntied"],2);
							}
							if ($vnim == "kateind") {
								if ($valisummat["kateed"] <> 0) 		$vsum = round($valisummat["katenyt"] / $valisummat["kateed"],2);
							}
							if ($vnim == "nettokateind") {
								if ($valisummat["nettokateed"] <> 0) 	$vsum = round($valisummat["nettokatenyt"] / $valisummat["nettokateed"],2);
							}
							if ($vnim == "myykplind") {
								if ($valisummat["myykpled"] <> 0)		$vsum = round($valisummat["myykplnyt"] / $valisummat["myykpled"],2);
							}

							if (mysql_num_rows($result) <= $rivilimitti) echo "<td class='tumma' align='right'>$vsum</td>";

							if(isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $excelsarake, $vsum);
							}

							$excelsarake++;

						}
						$excelrivi++;
						if (mysql_num_rows($result) <= $rivilimitti) echo "</tr>";
					}

					if (mysql_num_rows($result) <= $rivilimitti) echo "<tr>";

					$excelsarake = $myyntiind = $kateind = $nettokateind = $myykplind = 0;

					foreach($totsummat as $vnim => $vsum) {
						if ((string) $vsum != '') {
							$vsum = sprintf("%.2f", $vsum);
						}
						if ($vnim == "kateprosnyt") {
							if ($totsummat["myyntinyt"] <> 0) 		$vsum = round($totsummat["katenyt"] / abs($totsummat["myyntinyt"]) * 100, 2);
						}
						if ($vnim == "kateprosed") {
							if ($totsummat["myyntied"] <> 0) 		$vsum = round($totsummat["kateed"] / abs($totsummat["myyntied"]) * 100, 2);
						}
						if ($vnim == "nettokateprosnyt") {
							if ($totsummat["myyntinyt"] <> 0) 		$vsum = round($totsummat["nettokatenyt"] / abs($totsummat["myyntinyt"]) * 100, 2);
						}
						if ($vnim == "nettokateprosed") {
							if ($totsummat["myyntied"] <> 0) 		$vsum = round($totsummat["nettokateed"] / abs($totsummat["myyntied"]) * 100, 2);
						}
						if ($vnim == "myyntiind") {
							if ($totsummat["myyntied"] <> 0) 		$vsum = round($totsummat["myyntinyt"] / $totsummat["myyntied"],2);
						}
						if ($vnim == "kateind") {
							if ($totsummat["kateed"] <> 0) 			$vsum = round($totsummat["katenyt"] / $totsummat["kateed"],2);
						}
						if ($vnim == "nettokateind") {
							if ($totsummat["nettokateed"] <> 0) 	$vsum = round($totsummat["nettokatenyt"] / $totsummat["nettokateed"],2);
						}
						if ($vnim == "myykplind") {
							if ($totsummat["myykpled"] <> 0)		$vsum = round($totsummat["myykplnyt"] / $totsummat["myykpled"],2);
						}

						if (mysql_num_rows($result) <= $rivilimitti) echo "<td class='tumma' align='right'>$vsum</td>";

						if(isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $vsum);
							$excelsarake++;
						}
					}
					$excelrivi++;

					if (mysql_num_rows($result) <= $rivilimitti) echo "</tr></table>";

					echo "<br>";

					if(isset($workbook)) {
						// We need to explicitly close the workbook
						$workbook->close();

						echo "<table>";
						echo "<tr><th>".t("Tallenna tulos").":</th>";
						echo "<form method='post' action='$PHP_SELF'>";
						echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
						echo "<input type='hidden' name='kaunisnimi' value='Myyntiseuranta.xls'>";
						echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
						echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
						echo "</table><br>";
					}

					if ($osoitetarrat != "" and $tarra_aineisto != '')  {
						$tarra_aineisto = substr($tarra_aineisto, 0, -1);


						echo "<br><table>";
						echo "<tr><th>".t("Tulosta osoitetarrat").":</th>";
						echo "<form method='post' action='../crm/tarrat.php'>";
						echo "<input type='hidden' name='tee' value=''>";
						echo "<input type='hidden' name='tarra_aineisto' value='$tarra_aineisto'>";
						echo "<td class='back'><input type='submit' value='".t("Siirry")."'></td></tr></form>";
						echo "</table><br>";
					}
				}
				echo "<br><br><hr>";
			}
		}

		if ($lopetus == "") {
			//K�ytt�liittym�
			if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($kkl)) $kkl = date("m");
			if (!isset($vvl)) $vvl = date("Y");
			if (!isset($ppl)) $ppl = date("d");
			if (!isset($yhtio)) $yhtio = "'$kukarow[yhtio]'";

			echo "<br>\n\n\n";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='go'>";

			// t�ss� on t�m� "perusn�kym�" mik� tulisi olla kaikissa myynnin raportoinneissa..

			if ($ajotapa == "lasku") {
				$chk1 = "SELECTED";
			}
			elseif ($ajotapa == "tilaus") {
				$chk2 = "SELECTED";
			}
			elseif ($ajotapa == "tilausjaauki") {
				$chk3 = "SELECTED";
			}
			elseif ($ajotapa == "tilausjaaukiluonti") {
				$chk4 = "SELECTED";
			}
			elseif ($ajotapa == "ennakot") {
				$chk5 = "SELECTED";
			}
			elseif ($ajotapa == "tilausauki") {
				$chk6 = "SELECTED";
			}
			else {
				$chk1 = "SELECTED";
			}

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Valitse ajotapa:")."</th>";

			echo "<td><select name='ajotapa'>";
			echo "<option value='lasku'  				$chk1>".t("Laskuista")." (".t("Laskutus").")</option>";
			echo "<option value='tilaus' 				$chk2>".t("Laskutetuista tilauksista")."</option>";
			echo "<option value='tilausjaauki'			$chk3>".t("Laskutetuista sek� avoimista tilauksista")."</option>";
			echo "<option value='tilausjaaukiluonti'	$chk4>".t("Laskutetuista sek� avoimista tilauksista luontiajalla")." (".t("Myynti").")</option>";
			echo "<option value='ennakot'				$chk5>".t("Lep��m�ss� olevista ennakoista")."</option>";
			echo "<option value='tilausauki'			$chk6>".t("Avoimista tilauksista")."</option>";
			echo "</select></td>";

			echo "</tr>";

			if ($ajotapanlisa == "summattuna") {
				$chk1 = "SELECTED";
			}
			elseif ($ajotapanlisa == "erikseen") {
				$chk2 = "SELECTED";
			}
			else {
				$chk1 = "SELECTED";
			}

			echo "<tr>";
			echo "<th>".t("Ajotavan lis�parametrit:")."</th>";

			echo "<td><select name='ajotapanlisa'>";
			echo "<option value='summattuna'  $chk1>".t("Veloitukset ja hyvitykset summattuina")."</option>";
			echo "<option value='erikseen' 	  $chk2>".t("Veloitukset ja hyvitykset allekkain")."</option>";
			echo "</select></td>";
			echo "</tr>";
			echo "</table><br>";

			$query = "	SELECT *
						FROM yhtio
						WHERE konserni='$yhtiorow[konserni]' and konserni != ''";
			$result = mysql_query($query) or pupe_error($query);

			// voidaan valita listaukseen useita konserniyhti�it�, jos k�ytt�j�ll� on "P�IVITYS" oikeus t�h�n raporttiin
			if (mysql_num_rows($result) > 0 and $oikeurow['paivitys'] != "") {
				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Valitse yhti�")."</th>";

				if (!isset($yhtiot)) $yhtiot = array();

				while ($row = mysql_fetch_array($result)) {
					$sel = "";

					if ($kukarow["yhtio"] == $row["yhtio"] and count($yhtiot) == 0) $sel = "CHECKED";
					if (in_array($row["yhtio"], $yhtiot)) $sel = "CHECKED";

					echo "<td><input type='checkbox' name='yhtiot[]' onchange='submit()' value='$row[yhtio]' $sel>$row[nimi]</td>";
				}

				echo "</tr>";
				echo "</table><br>";
			}
			else {
				echo "<input type='hidden' name='yhtiot[]' value='$kukarow[yhtio]'>";
			}


			if ($ruksit[10]  != '') 	$ruk10chk  	= "CHECKED";
			if ($ruksit[20]  != '') 	$ruk20chk  	= "CHECKED";
			if ($ruksit[30]  != '') 	$ruk30chk  	= "CHECKED";
			if ($ruksit[40]  != '') 	$ruk40chk  	= "CHECKED";
			if ($ruksit[50]  != '') 	$ruk50chk 	= "CHECKED";
			if ($ruksit[55]  != '') 	$ruk55chk 	= "CHECKED";

			if ($piirivalinta == 'lasku' or $piirivalinta == '') {
				$laskuvalintachk  	= "CHECKED";
			}
			else {
				$asiakasvalintachk  = "CHECKED";
			}

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Valitse asiakasosastot").":</th>";
			echo "<th>".t("Valitse asiakasryhm�t").":</th>";
			echo "<th>".t("Valitse asiakaspiirit").":</th></tr>";

			echo "<tr>";
			echo "<td valign='top'>";

			// n�ytet��n soveltuvat asiakasosastot
			$res2 = t_avainsana("ASIAKASOSASTO", "", "", $yhtio);

			echo "<select name='mul_oasiakasosasto[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_oasiakasosasto!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_oasiakasosasto)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei asiakasosastoa")."</option>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_oasiakasosasto!="") {
					if (in_array($rivi['selite'],$mul_oasiakasosasto)) {
						$mul_check = 'SELECTED';
					}
				}

				echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
			}

			echo "</select>";
			echo "</td>";
			echo "<td valign='top'>";

			// n�ytet��n soveltuvat asiakasryhmat
			$res2 = t_avainsana("ASIAKASRYHMA", "", "", $yhtio);

			echo "<select name='mul_asiakasryhma[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_asiakasryhma!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_asiakasryhma)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei asiakasryhm��")."</option>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_asiakasryhma!="") {
					if (in_array($rivi['selite'],$mul_asiakasryhma)) {
						$mul_check = 'SELECTED';
					}
				}

				echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
			}

			echo "</select>";
			echo "</td>";
			echo "<td valign='top'>";

			// n�ytet��n sallityt piirit
			$res2 = t_avainsana("PIIRI", "", $asiakasrajaus_avainsana, $yhtio);

			echo "<select name='mul_piiri[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_piiri!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_piiri)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei asiakaspiiri�")."</option>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_piiri != "") {
					if (in_array($rivi['selite'], $mul_piiri)) {
						$mul_check = 'SELECTED';
					}
				}

				echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
			}

			echo "</select>";
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<th>".t("Prio").": <input type='text' name='jarjestys[10]' size='2' value='$jarjestys[10]'> ".t("Asiakasosastoittain")." <input type='checkbox' name='ruksit[10]' value='asiakasosasto' $ruk10chk></th>";
			echo "<th>".t("Prio").": <input type='text' name='jarjestys[20]' size='2' value='$jarjestys[20]'> ".t("Asiakasryhmitt�in")." <input type='checkbox' name='ruksit[20]' value='asiakasryhma' $ruk20chk></th>";
			echo "<th>".t("Prio").": <input type='text' name='jarjestys[30]' size='2' value='$jarjestys[30]'> ".t("Asiakaspiireitt�in")." <input type='checkbox' name='ruksit[30]' value='piiri' $ruk30chk></th><tr>";
			echo "<th colspan='2'></th><th>".t("Piiri")." <input type='radio' name='piirivalinta' value='lasku' $laskuvalintachk>".t("Laskuilta");
			echo "<input type='radio' name='piirivalinta' value='asiakas' $asiakasvalintachk>".t("Asiakkailta")."</th></tr>";
			echo "</table><br>\n";



			echo "<table><tr>";

			echo "<th>".t("Valitse tuoteosastot").":</th>";
			echo "<th>".t("Valitse tuoteryhm�t").":</th></tr>";

			echo "<tr>";
			echo "<td valign='top'>";

			// n�ytet��n soveltuvat osastot
			// tehd��n avainsana query
			$res2 = t_avainsana("OSASTO");

			echo "<select name='mul_osasto[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_osasto!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_osasto)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoteosastoa")."</option>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_osasto!="") {
					if (in_array($rivi['selite'],$mul_osasto)) {
						$mul_check = 'SELECTED';
					}
				}

				echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
			}

			echo "</select>";

			echo "</td>";
			echo "<td valign='top' class='back'>";

			// n�ytet��n soveltuvat tryt
			// tehd��n avainsana query
			$res2 = t_avainsana("TRY");

			echo "<select name='mul_try[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_try!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_try)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoterym��")."</option>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_try!="") {
					if (in_array($rivi['selite'],$mul_try)) {
						$mul_check = 'SELECTED';
					}
				}

				echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
			}

			echo "</select>";
			echo "</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>".t("Prio").": <input type='text' name='jarjestys[40]' size='2' value='$jarjestys[40]'> ".t("Osastoittain")." <input type='checkbox' name='ruksit[40]' value='osasto' $ruk40chk></th>";
			echo "<th>".t("Prio").": <input type='text' name='jarjestys[50]' size='2' value='$jarjestys[50]'> ".t("Tuoteryhmitt�in")." <input type='checkbox' name='ruksit[50]' value='tuoteryhma' $ruk50chk></th></tr>";

			echo "</table><br>\n";


			echo "<table><tr>";

			echo "<th>".t("Valitse kustannuspaikat").":</th></tr>";

			echo "<tr>";
			echo "<td valign='top'>";

			// n�ytet��n soveltuvat osastot
			$query = "	SELECT tunnus selite, nimi selitetark
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]'
						and kaytossa != 'E'
						and tyyppi = 'K'
						ORDER BY koodi+0, koodi, nimi";
			$res2  = mysql_query($query) or die($query);

			echo "<select name='mul_kustp[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_kustp!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_kustp)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei kustannuspaikkaa")."</option>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_kustp!="") {
					if (in_array($rivi['selite'],$mul_kustp)) {
						$mul_check = 'SELECTED';
					}
				}

				echo "<option value='$rivi[selite]' $mul_check>$rivi[selitetark]</option>";
			}

			echo "</select>";

			echo "</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>".t("Prio").": <input type='text' name='jarjestys[55]' size='2' value='$jarjestys[55]'> ".t("Kustannuspaikoittain")." <input type='checkbox' name='ruksit[55]' value='kustannuspaikka' $ruk55chk></th></tr>";
			echo "</table><br>\n";

			// lis�rajaukset n�kym�..
			if ($ruksit[60]  != '') 		$ruk60chk  				= "CHECKED";
			if ($ruksit[70]  != '') 		$ruk70chk  				= "CHECKED";
			if ($ruksit[80]  != '') 		$ruk80chk  				= "CHECKED";
			if ($ruksit[90]  != '') 		$ruk90chk  				= "CHECKED";
			if ($ruksit[100]  != '') 		$ruk100chk 				= "CHECKED";
			if ($ruksit[110]  != '') 		$ruk110chk 				= "CHECKED";
			if ($ruksit[120]  != '') 		$ruk120chk 				= "CHECKED";
			if ($ruksit[130]  != '') 		$ruk130chk 				= "CHECKED";
			if ($ruksit[140]  != '') 		$ruk140chk 				= "CHECKED";
			if ($ruksit[150] != '') 		$ruk150chk 				= "CHECKED";
			if ($ruksit[160] != '')			$ruk160chk 				= "CHECKED";
			if ($ruksit[170] != '')			$ruk170chk 				= "CHECKED";
			if ($ruksit[180] != '')			$ruk180chk 				= "CHECKED";
			if ($ruksit[190] != '')			$ruk190chk 				= "CHECKED";
			if ($ruksit[200] != '')			$ruk200chk 				= "CHECKED";
			if ($ruksit[210] != '')			$ruk210chk 				= "CHECKED";
			if ($ruksit[220] != '')			$ruk220chk 				= "CHECKED";
			if ($nimitykset != '')   		$nimchk   				= "CHECKED";
			if ($kateprossat != '')  		$katchk   				= "CHECKED";
			if ($nettokateprossat != '')	$nettokatchk			= "CHECKED";
			if ($osoitetarrat != '') 		$tarchk   				= "CHECKED";
			if ($piiyhteensa != '')  		$piychk   				= "CHECKED";
			if ($sarjanumerot != '')  		$sarjachk 				= "CHECKED";
			if ($eiOstSarjanumeroita != '') $sarjachk2 				= "CHECKED";
			if ($kuukausittain != '')		$kuuchk	  				= "CHECKED";
			if ($varastonarvo != '')		$varvochk 				= "CHECKED";
			if ($piiloed != '')				$piiloedchk 			= "CHECKED";
			if ($tilrivikomm != '')			$tilrivikommchk 		= "CHECKED";
			if ($vain_excel != '')			$vain_excelchk 			= "CHECKED";
			if ($piilota_myynti != '')		$piilota_myynti_sel 	= "CHECKED";
			if ($piilota_nettokate != '')	$piilota_nettokate_sel	= "CHECKED";
			if ($piilota_kate != '')		$piilota_kate_sel 		= "CHECKED";
			if ($piilota_kappaleet != '')	$piilota_kappaleet_sel 	= "CHECKED";
			if ($piilotanollarivit != '')	$einollachk 			= "CHECKED";
			if ($naytaennakko != '')		$naytaennakkochk 		= "CHECKED";
			if ($naytakaikkityypit != '')	$naytakaikkityypitchk	= "CHECKED";
			if ($ytunnus_mistatiedot != '')	$ytun_mistatiedot_sel	= "SELECTED";
			if ($verkkokaupat != '') 		$verkkokaupatchk		= "CHECKED";

			echo "<table>
				<tr>
				<th>".t("Lis�rajaus")."</th>
				<th>".t("Prio")."</th>
				<th> x</th>
				<th>".t("Rajaus")."</th>
				</tr>
				<tr>
				<tr>
				<th>".t("Listaa y-tunnuksella")."</th>
				<td><input type='text' name='jarjestys[60]' size='2' value='$jarjestys[60]'></td>
				<td><input type='checkbox' name='ruksit[60]' value='ytunnus' $ruk60chk></td>
				<td><input type='text' name='ytunnus' value='$ytunnus'>
				<select name='ytunnus_mistatiedot'>
				<option value=''>".t("Asiakasrekisterist�")."</option>
				<option value='laskulta' $ytun_mistatiedot_sel>".t("Laskuilta")."</option>
				</select>
				</td>
				</tr>
				<tr>
				<th>".t("Listaa asiakasnumerolla")."</th>
				<td><input type='text' name='jarjestys[70]' size='2' value='$jarjestys[70]'></td>
				<td><input type='checkbox' name='ruksit[70]' value='asiakasnro' $ruk70chk></td>
				<td><input type='text' name='asiakasnro' value='$asiakasnro'></td>
				</tr>
				<tr>
				<th>".t("Listaa tuotteittain")."</th>
				<td><input type='text' name='jarjestys[80]' size='2' value='$jarjestys[80]'></td>
				<td><input type='checkbox' name='ruksit[80]' value='tuote' $ruk80chk></td>
				<td><input type='text' name='rajaus[80]' value='$rajaus[80]'></td>
				</tr>
				<tr>
				<th>".t("Listaa tuotemyyjitt�in")."</th>
				<td><input type='text' name='jarjestys[90]' size='2' value='$jarjestys[90]'></td>
				<td><input type='checkbox' name='ruksit[90]' value='tuotemyyja' $ruk90chk></td>
				<td>";

			$query = "SELECT distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
			$vresult = mysql_query($query) or pupe_error($query);

			echo "<select name='rajaus[90]'>";
			echo "<option value = '' ></option>";

			while ($vrow=mysql_fetch_array($vresult)) {

				if ($rajaus[90] == $vrow['myyja']) {
					$sel = "selected";
				}
				else {
					$sel = "";
				}

				echo "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
			}

			echo "</select>";

			echo "	</td>
				</tr>
				<tr>
				<th>".t("Listaa asiakasmyyjitt�in")."</th>
				<td><input type='text' name='jarjestys[100]' size='2' value='$jarjestys[100]'></td>
				<td><input type='checkbox' name='ruksit[100]' value='asiakasmyyja' $ruk100chk></td>
				<td>";

			$query = "SELECT distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
			$vresult = mysql_query($query) or pupe_error($query);

			echo "<select name='rajaus[100]'>";
			echo "<option value = '' ></option>";

			while ($vrow = mysql_fetch_array($vresult)) {

				if ($rajaus[100] == $vrow['myyja']) {
					$sel = "selected";
				}
				else {
					$sel = "";
				}

				echo "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
			}

			echo "</select>";

			echo "	</td>
				</tr>
				<tr>
				<th>".t("Listaa tuoteostajittain")."</th>
				<td><input type='text' name='jarjestys[110]' size='2' value='$jarjestys[110]'></td>
				<td><input type='checkbox' name='ruksit[110]' value='tuoteostaja' $ruk110chk></td>
				<td>";

			$query = "SELECT distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
			$vresult = mysql_query($query) or pupe_error($query);

			echo "<select name='rajaus[110]'>";
			echo "<option value = '' ></option>";

			while ($vrow=mysql_fetch_array($vresult)) {

				if ($rajaus[110] == $vrow['myyja']) {
					$sel = "selected";
				}
				else {
					$sel = "";
				}

				echo "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
			}

			echo "</select>";

			echo "	</td>
				</tr>
				<tr>
				<th>".t("Listaa maittain")."</th>
				<td><input type='text' name='jarjestys[120]' size='2' value='$jarjestys[120]'></td>
				<td><input type='checkbox' name='ruksit[120]' value='maa' $ruk120chk></td>
				<td><input type='text' name='rajaus[120]' value='$rajaus[120]'></td>
				</tr>
				<tr>
				<th>".t("Listaa merkeitt�in")."</th>
				<td><input type='text' name='jarjestys[130]' size='2' value='$jarjestys[130]'></td>
				<td><input type='checkbox' name='ruksit[130]' value='merkki' $ruk130chk></td>
				<td><input type='text' name='rajaus[130]' value='$rajaus[130]'></td>
				</tr>
				<tr>
				<th>".t("Listaa toimittajittain")."</th>
				<td><input type='text' name='jarjestys[140]' size='2' value='$jarjestys[140]'></td>
				<td><input type='checkbox' name='ruksit[140]' value='toimittaja' $ruk140chk></td>
				<td><input type='text' name='toimittaja' value='$toimittaja'></td>
				</tr>
				<tr>
				<th>".t("Listaa tilaustyypeitt�in")."</th>
				<td><input type='text' name='jarjestys[150]' size='2' value='$jarjestys[150]'></td>
				<td><input type='checkbox' name='ruksit[150]' value='tilaustyyppi' $ruk150chk></td>
				<td><input type='text' name='rajaus[150]' value='$rajaus[150]'></td>
				<td class='back'>".t("(Toimii vain jos ajat raporttia tilauksista)")."</td>
				</tr>
				<tr>
				<th>".t("Listaa myyjitt�in")."</th>
				<td><input type='text' name='jarjestys[160]' size='2' value='$jarjestys[160]'></td>
				<td><input type='checkbox' name='ruksit[160]' value='myyja' $ruk160chk></td>
				<td><input type='text' name='rajaus[160]' value='$rajaus[160]'></td>
				</tr>
				<tr>
				<th>".t("Listaa konsernittain")."</th>
				<td><input type='text' name='jarjestys[170]' size='2' value='$jarjestys[170]'></td>
				<td><input type='checkbox' name='ruksit[170]' value='konserni' $ruk170chk></td>
				<td><input type='text' name='rajaus[170]' value='$rajaus[170]'></td>
				</tr>
				<tr>
				<th>".t("Listaa laskuittain")."</th>
				<td><input type='text' name='jarjestys[180]' size='2' value='$jarjestys[180]'></td>
				<td><input type='checkbox' name='ruksit[180]' value='laskuittain' $ruk180chk></td>
				<td><input type='text' name='rajaus[180]' value='$rajaus[180]'></td>
				</tr>
				<tr>
				<th>".t("Listaa varastoittain")."</th>
				<td><input type='text' name='jarjestys[190]' size='2' value='$jarjestys[190]'></td>
				<td><input type='checkbox' name='ruksit[190]' value='varastoittain' $ruk190chk></td>
				<td><input type='text' name='rajaus[190]' value='$rajaus[190]'></td>
				</tr>
				<tr>
				<th>".t("Listaa kanta-asiakkaittain")."</th>
				<td><input type='text' name='jarjestys[200]' size='2' value='$jarjestys[200]'></td>
				<td><input type='checkbox' name='ruksit[200]' value='kantaasiakkaittain' $ruk200chk></td>
				<td><input type='text' name='rajaus[200]' value='$rajaus[200]'></td>
				<td class='back'>".t("(Toimii vain jos ajat raporttia tilauksista)")."</td>
				</tr>
				<tr>
				<th>".t("Listaa maksuehdoittain")."</th>
				<td><input type='text' name='jarjestys[210]' size='2' value='$jarjestys[210]'></td>
				<td><input type='checkbox' name='ruksit[210]' value='maksuehdoittain' $ruk210chk></td>
				<td><input type='text' name='rajaus[210]' value='$rajaus[210]'></td>
				</tr>
				<tr>
				<th>".t("Listaa asiakkaan tilausnumeroittain")."</th>
				<td><input type='text' name='jarjestys[220]' size='2' value='$jarjestys[220]'></td>
				<td><input type='checkbox' name='ruksit[220]' value='asiakkaan_tilausnumeroittain' $ruk220chk></td>
				<td><input type='text' name='rajaus[220]' value='$rajaus[220]'></td>
				</tr>
				<tr>
				<td class='back'><br></td>
				</tr>
				<tr><th valign='top'>".t("Tuotelista")."<br>(".t("Rajaa n�ill� tuotteilla").")</th><td colspan='3'><textarea name='tuotteet_lista' rows='5' cols='35'>$tuotteet_lista</textarea></td></tr>
				<tr>
				<td class='back'><br></td>
				</tr>
				<tr>
				<th>".t("Piilota myynti")."</th>
				<td><input type='checkbox' name='piilota_myynti' $piilota_myynti_sel></td>
				<td></td>
				</tr>
				<tr>
				<th>".t("Piilota nettokate")."</th>
				<td><input type='checkbox' name='piilota_nettokate' $piilota_nettokate_sel></td>
				<td></td>
				</tr>
				<tr>
				<th>".t("Piilota kate")."</th>
				<td><input type='checkbox' name='piilota_kate' $piilota_kate_sel></td>
				<td></td>
				</tr>
				<tr>
				<th>".t("Piilota kappaleet")."</th>
				<td><input type='checkbox' name='piilota_kappaleet' $piilota_kappaleet_sel></td>
				<td></td>
				</tr>
				<tr>
				<th>".t("Piilota edellisen kauden sarakkeet")."</th>
				<td><input type='checkbox' name='piiloed' $piiloedchk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("Piilota v�lisummat")."</th>
				<td><input type='checkbox' name='piiyhteensa' $piychk></td>
				<td></td>
				</tr>
				<tr>
				<th>".t("N�yt� nettokateprosentit")."</th>
				<td><input type='checkbox' name='nettokateprossat' $nettokatchk></td>
				<td></td>
				<td class='back'>".t("(Toimii vain jos myynti ja nettokate n�ytet��n)")."</td>
				</tr>
				<tr>
				<th>".t("N�yt� kateprosentit")."</th>
				<td><input type='checkbox' name='kateprossat' $katchk></td>
				<td></td>
				<td class='back'>".t("(Toimii vain jos myynti ja kate n�ytet��n)")."</td>
				</tr>
				<tr>
				<th>".t("N�yt� tuotteiden nimitykset")."</th>
				<td><input type='checkbox' name='nimitykset' $nimchk></td>
				<td></td>
				<td class='back'>".t("(Toimii vain jos listaat tuotteittain)")."</td>
				</tr>
				<tr>
				<th>".t("N�yt� sarjanumerot")."</th>
				<td><input type='checkbox' name='sarjanumerot' $sarjachk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("N�yt� vain myydyt sarjanumerot")."</th>
				<td><input type='checkbox' name='eiOstSarjanumeroita' $sarjachk2></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("N�yt� varastonarvo")."</th>
				<td><input type='checkbox' name='varastonarvo' $varvochk></td>
				<td></td>
				<td class='back'>".t("(Toimii vain jos listaat tuotteittain)")."</td>
				</tr>
				<tr>
				<th>".t("N�yt� tilausrivin kommentti")."</th>
				<td><input type='checkbox' name='tilrivikomm' $tilrivikommchk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("Tulosta myynti kuukausittain")."</th>
				<td><input type='checkbox' name='kuukausittain' $kuuchk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("Tulosta osoitetarrat")."</th>
				<td><input type='checkbox' name='osoitetarrat' $tarchk></td>
				<td></td>
				<td class='back'>".t("(Toimii vain jos listaat asiakkaittain)")."</td>
				</tr>
				<tr>
				<th>".t("Raportti vain Exceliin")."</th>
				<td><input type='checkbox' name='vain_excel' $vain_excelchk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("Piilota nollarivit")."</th>
				<td><input type='checkbox' name='piilotanollarivit' $einollachk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("N�yt� my�s ennakkolaskutus")."</th>
				<td><input type='checkbox' name='naytaennakko' $naytaennakkochk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("N�yt� kaikki tuotetyypit")."</th>
				<td><input type='checkbox' name='naytakaikkityypit' $naytakaikkityypitchk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>",t("N�yt� tuotteet statuksella"),"</th>";

			$status_result = t_avainsana("S");

			echo "<td colspan='2'><select name='status'><option value=''>",t("Kaikki"),"</option>";

			while ($statusrow = mysql_fetch_assoc($status_result)) {

				$sel = '';

				if (isset($status) and $status == $statusrow['selite']) $sel = ' SELECTED';

				echo "<option value='$statusrow[selite]'$sel>$statusrow[selite] - $statusrow[selitetark]</option>";
			}

			echo "</select></td></tr>";

			echo "<tr>
				<th>".t("N�yt� vain verkkokauppatilauksia")."</th>
				<td><input type='checkbox' name='verkkokaupat' $verkkokaupatchk></td>
				<td></td>
				<td class='back'></td>
				</tr>";

			echo "</table><br>";

			// p�iv�m��r�rajaus
			echo "<table>";
			echo "<tr>
				<th>".t("Sy�t� alkup�iv�m��r� (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr>\n
				<tr><th>".t("Sy�t� loppup�iv�m��r� (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>
				</tr>\n";
			echo "</table><br>";

			echo nayta_kyselyt("myyntiseuranta");

			echo "<br>";
			echo "<input type='submit' value='".t("Aja raportti")."'>";
			echo "</form>";
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta.php") !== FALSE) {
			require ("../inc/footer.inc");
		}
	}
?>