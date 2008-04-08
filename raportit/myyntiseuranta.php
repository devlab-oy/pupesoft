<?php


	// käytetään slavea
	$useslave = 1;

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

		if(!aja_kysely()) {
			unset($_POST);
		}

		if(count($_POST) > 0) {
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

					// Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksilöiden ostohinnoista (ostetut yksilöt jotka eivät vielä ole myyty(=laskutettu))
					if ($row["sarjanumeroseuranta"] == "S") {
						$query	= "	SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
									FROM sarjanumeroseuranta
									LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
									LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
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

					// tuotteen muutos varastossa annetun päivän jälkeen
					$query = "	SELECT sum(kpl * if(laji in ('tulo','valmistus'), kplhinta, hinta)) muutoshinta, sum(kpl) muutoskpl
					 			FROM tapahtuma use index (yhtio_tuote_laadittu)
					 			WHERE yhtio = '$kukarow[yhtio]'
					 			and tuoteno = '$row[tuoteno]'
					 			and laadittu > '$vv-$kk-$pp 23:59:59'";
					$mres = mysql_query($query) or pupe_error($query);
					$mrow = mysql_fetch_array($mres);

					// katotaan onko tuote epäkurantti nyt
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

					// tuotteen määrä varastossa nyt
					$query = "	SELECT sum(saldo) varasto
								FROM tuotepaikat use index (tuote_index)
								WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
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

					return array($muutoshinta, $kierto);
				}
			}

			//	Jos käyttäjällä on valittu piirejä niin sallitaan vain ko. piirin/piirien hakeminen
			if($kukarow["piirit"] != "")	 {
				$asiakasrajaus = "and piiri IN ($kukarow[piirit])";
				$asiakasrajaus_avainsana = "and selite IN ($kukarow[piirit])";
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

			// hehe, näin on helpompi verrata päivämääriä
			$query  = "SELECT TO_DAYS('$vvl-$kkl-$ppl')-TO_DAYS('$vva-$kka-$ppa') ero";
			$result = mysql_query($query) or pupe_error($query);
			$row    = mysql_fetch_array($result);

			if ($row["ero"] > 365) {
				echo "<font class='error'>".t("Jotta homma ei menisi liian hitaaksi, niin vuosi on pisin mahdollinen laskentaväli!")."</font><br>";
				$tee = "";
			}

			// haetaan tilauksista
			if ($ajotapa == 'tilaus') {
				$tila		= "'L'";
				$ouusio		= 'otunnus';
				$index		= 'yhtio_otunnus';
			}
			elseif ($ajotapa == 'tilausjaauki') {
				$tila		= "'L','N'";
				$ouusio		= 'otunnus';
				$index		= 'yhtio_otunnus';
			}
			// haetaan laskuista
			else {
				$tila		= "'U'";
				$ouusio		= 'uusiotunnus';
				$index		= 'uusiotunnus_index';
			}

			// jos on joku toimittajajuttu valittuna, niin ei saa valita ku yhen yrityksen
			if ($toimittaja != "" or $mukaan == "toimittaja") {
				if (count($yhtiot) != 1) {
					echo "<font class='error'>".t("Toimittajahauissa voi valita vain yhden yrityksen")."!</font><br>";
					$tee = "";
				}
			}

			// jos ei ole mitään yritystä valittuna ei tehdä mitään
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

			// jos joku päiväkenttä on tyhjää ei tehdä mitään
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

				require("../inc/asiakashaku.inc");

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
					// mennään ohi
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
				}
				else {
					$tee 			= "";
					$toimittajaid 	= "";
				}
			}

			if ($tee == 'go') {

				// no hacking, please.
				$lisa   = "";
				$query  = "";
				$group  = "";
				$order  = "";
				$select = "";
				$gluku  = 0;

				// näitä käytetään queryssä
				$sel_osasto = "";
				$sel_tuoteryhma = "";

				$apu = array();

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
						$select .= "tuote.try 'tuoteryhmä', ";
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
						$select .= "if(tuote.kustp != '',tuote.kustp,asiakas.kustannuspaikka) as kustpaikka, ";
						$order  .= "kustpaikka,";
						$gluku++;
					}

					if ($mukaan == "ytunnus" and $osoitetarrat == "") {
						if ($group!="") $group .= ",asiakas.tunnus";
						else $group  .= "asiakas.tunnus";
						$select .= "asiakas.ytunnus, asiakas.toim_ovttunnus, concat_ws('<br>',concat_ws(' ',asiakas.nimi,asiakas.nimitark),if(asiakas.toim_nimi!='' and asiakas.nimi!=asiakas.toim_nimi,concat_ws(' ',asiakas.toim_nimi,asiakas.toim_nimitark),NULL)) nimi, concat_ws('<br>',asiakas.postitp,if(asiakas.toim_postitp!='' and asiakas.postitp!=asiakas.toim_postitp,asiakas.toim_postitp,NULL)) postitp, ";
						$order  .= "asiakas.ytunnus,";
						$gluku++;
					}
					elseif ($mukaan == "ytunnus" and $osoitetarrat != "") {
						if ($group!="") $group .= ",asiakas.tunnus";
						else $group  .= "asiakas.tunnus";
						$select .= "asiakas.tunnus astunnus, concat_ws(' ',asiakas.ytunnus,asiakas.toim_ovttunnus,asiakas.toim_nimi) ytunnus, ";
						$order  .= "asiakas.ytunnus,";
						$gluku++;
					}

					if ($mukaan == "asiakasnro") {
						if ($group!="") $group .= ",asiakas.tunnus";
						else $group .= "asiakas.tunnus";
						$select .= "asiakas.asiakasnro, concat_ws('<br>',concat_ws(' ',asiakas.nimi,asiakas.nimitark),if(asiakas.toim_nimi!='' and asiakas.nimi!=asiakas.toim_nimi,concat_ws(' ',asiakas.toim_nimi,asiakas.toim_nimitark),NULL)) nimi, concat_ws('<br>',asiakas.postitp,if(asiakas.toim_postitp!='' and asiakas.postitp!=asiakas.toim_postitp,asiakas.toim_postitp,NULL)) postitp, ";
						$order  .= "asiakas.asiakasnro,";
						$gluku++;
					}

					if ($mukaan == "maa") {
						if ($group!="") $group .= ",asiakas.maa";
						else $group  .= "asiakas.maa";
						$select .= "asiakas.maa maa, ";
						$order  .= "asiakas.maa,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and asiakas.maa='$rajaus[$i]' ";
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
							if ($group!="") $group .= ",tuote.tuoteno, tuote.nimitys";
							else $group  .= "tuote.tuoteno, tuote.nimitys";
							$select .= "tuote.tuoteno tuoteno, tuote.nimitys nimitys, ";
							$order  .= "tuote.tuoteno,";
							$gluku++;
						}
						if ($sarjanumerot != '') {
							$select .= "group_concat(concat(tilausrivi.tunnus,'#',tilausrivi.kpl)) sarjanumero, ";
						}
						if ($varastonarvo != '') {
							$select .= "0 varastonarvo, 0 kierto, ";
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
						$select .= "(select group_concat(distinct toimittaja) from tuotteen_toimittajat use index (yhtio_tuoteno) where tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno) toimittaja, ";
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
					$query = "SELECT tuoteno from tuotteen_toimittajat where yhtio in ($yhtio) and toimittaja='$toimittaja'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) > 0) {
						$lisa .= " and tilausrivi.tuoteno in (";
						while ($toimirow = mysql_fetch_array($result)) {
							$lisa .= "'$toimirow[tuoteno]',";
						}
						$lisa = substr($lisa,0,-1).")";
					}
					else {
						echo "<font class='error'>Toimittajan $toimittaja tuotteita ei löytynyt! Ajetaan ajo ilman rajausta!</font><br><br>";
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
						echo "<font class='error'>Asiakasta $asiakas ei löytynyt! Ajetaan ajo ilman rajausta!</font><br><br>";
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
						echo "<font class='error'>Asiakasta $asiakasnro ei löytynyt! Ajetaan ajo ilman rajausta!</font><br><br>";
						$asiakas = "";
					}
				}


				$vvaa = $vva - '1';
				$vvll = $vvl - '1';

				if ($kateprossat != "") {
					$katelisanyt = " 0 kateprosnyt, ";
					$katelisaed  = " 0 kateprosed, ";
				}
				else {
					$katelisanyt = "";
					$katelisaed  = "";
				}

				// Jos ei olla valittu mitään
				if ($group == "") {
					$select = "tuote.yhtio, ";
					$group = "lasku.yhtio";
				}

				if ($ajotapanlisa == "erikseen") {
					$tilauslisa3 = ", if(tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt>0, 'Veloitus', 'Hyvitys') rivityyppi";
					$group .= ", rivityyppi";
				}
				else {
					$tilauslisa3 = "";
				}

				$query = "	SELECT $select";

				// generoidaan selectit
				if ($kuukausittain != "") {
					$MONTH_ARRAY  	= array(1=> t('Tammikuu'),t('Helmikuu'),t('Maaliskuu'),t('Huhtikuu'),t('Toukokuu'),t('Kesäkuu'),t('Heinäkuu'),t('Elokuu'),t('Syyskuu'),t('Lokakuu'),t('Marraskuu'),t('Joulukuu'));

					$startmonth	= date("Ymd",mktime(0, 0, 0, $kka, 1,  $vva));
					$endmonth 	= date("Ymd",mktime(0, 0, 0, $kkl, 1,  $vvl));

					for ($i = $startmonth;  $i <= $endmonth;) {

						$alku  = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)));
						$loppu = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), date("t", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4))),  substr($i,0,4)));

						$alku_ed  = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)-1));
						$loppu_ed = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), date("t", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4))),  substr($i,0,4)-1));

						if ($ajotapa == 'tilausjaauki') {
							$query .= "sum(if(tilausrivi.laadittu >= '$alku 00:00:00'  and tilausrivi.laadittu <= '$loppu 23:59:59' and tilausrivi.laskutettuaika= '0000-00-00', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),0)) '".$MONTH_ARRAY[(substr($i,4,2)*1)]." ".substr($i,0,4)." ".t("Laskuttamatta")."',";
						}

						$query .= " sum(if(tilausrivi.laskutettuaika >= '$alku'  and tilausrivi.laskutettuaika <= '$loppu', tilausrivi.rivihinta,0)) '".substr($MONTH_ARRAY[(substr($i,4,2)*1)],0,3)." ".substr($i,0,4)." ".t("Myynti")."',";

						if ($piiloed == "") {
							$query .= " sum(if(tilausrivi.laskutettuaika >= '$alku_ed'  and tilausrivi.laskutettuaika <= '$loppu_ed', tilausrivi.rivihinta,0)) '".substr($MONTH_ARRAY[(substr($i,4,2)*1)],0,3)." ".(substr($i,0,4)-1)." ".t("Myynti")."',";
						}

						$i = date("Ymd",mktime(0, 0, 0, substr($i,4,2)+1, 1,  substr($i,0,4)));
					}

					// Vika pilkku pois
					$query = substr($query, 0 ,-1);
				}
				else {

					if ($ajotapa == 'tilausjaauki') {
						$query .= "sum(if(tilausrivi.laadittu >= '$vva-$kka-$ppa 00:00:00'  and tilausrivi.laadittu <= '$vvl-$kkl-$ppl 23:59:59' and tilausrivi.laskutettuaika= '0000-00-00', tilausrivi.varattu+tilausrivi.jt,0)) myykpllaskuttamattanyt, ";
					}

					$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kpl,0)) myykplnyt, ";

					if ($piiloed == "") {
						$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kpl,0)) myykpled, ";
					}

					if ($ajotapa == 'tilausjaauki') {
						$query .= "sum(if(tilausrivi.laadittu >= '$vva-$kka-$ppa 00:00:00'  and tilausrivi.laadittu <= '$vvl-$kkl-$ppl 23:59:59' and tilausrivi.laskutettuaika= '0000-00-00', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),0)) myyntilaskuttamattanyt, ";
					}

					$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta,0)) myyntinyt, ";

					if ($piiloed == "") {
						$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.rivihinta,0)) myyntied, ";
						$query .= "	round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta,0)) / sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.rivihinta,0)),2) myyntiind, ";
					}

					$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate,0)) katenyt, ";

					if ($piiloed == "") {
						$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kate,0)) kateed, ";
						$query .= "	round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate,0)) /sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kate,0)),2) kateind, ";
					}

					$query .= $katelisanyt;

					if ($piiloed == "") {
						$query .= $katelisaed;
					}

					$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate - (tilausrivi.kate*IFNULL(asiakas.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(toimitustapa.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(tuote.kuluprosentti,0)/100),0)) nettokatenyt ";

					if ($piiloed == "") {
						$query .= ", ";
						$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.kate - (tilausrivi.kate*IFNULL(asiakas.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(toimitustapa.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(tuote.kuluprosentti,0)/100),0)) nettokateed, ";
						$query .= "	sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate - (tilausrivi.kate*IFNULL(asiakas.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(toimitustapa.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(tuote.kuluprosentti,0)/100),0)) / sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.kate - (tilausrivi.kate*IFNULL(asiakas.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(toimitustapa.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(tuote.kuluprosentti,0)/100),0)) nettokateind ";
					}
				}

				$query .= $tilauslisa3;
				$query .= "	FROM lasku use index (yhtio_tila_tapvm)
							JOIN tilausrivi use index ($index) ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.$ouusio=lasku.tunnus and tilausrivi.tyyppi='L'
							LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno
							LEFT JOIN asiakas use index (PRIMARY) ON asiakas.yhtio=lasku.yhtio and asiakas.tunnus=lasku.liitostunnus
							LEFT JOIN toimitustapa ON lasku.yhtio=toimitustapa.yhtio and lasku.toimitustapa=toimitustapa.selite
							WHERE lasku.yhtio in ($yhtio)
							$asiakasrajaus and
							lasku.tila in ($tila)";

				if ($ajotapa == 'tilausjaauki') {
					$query .= "	and lasku.alatila in ('','A','B','C','D','J','E','F','T','U','X')
								and ((lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00'  and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59') or (lasku.tapvm >= '$vva-$kka-$ppa' and lasku.tapvm <= '$vvl-$kkl-$ppl') ";

					if ($piiloed == "") {
						$query .= " or (lasku.tapvm >= '$vvaa-$kka-$ppa' and lasku.tapvm <= '$vvll-$kkl-$ppl') ";
					}

					$query .= " ) ";
				}
				else {
					$query .= "	and lasku.alatila='X'
								and ((lasku.tapvm >= '$vva-$kka-$ppa'  and lasku.tapvm <= '$vvl-$kkl-$ppl') ";

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

					$result = mysql_query($query) or pupe_error($query);

					$rivilimitti = 1000;

					if($vain_excel != "") {
						echo "<font class='error'>".t("Tallenna/avaa tulos excelissä")."!</font><br><br>";
						$rivilimitti = 0;
					}
					else {
						if (mysql_num_rows($result) > $rivilimitti) {
							echo "<br><font class='error'>".t("Hakutulos oli liian suuri")."!</font><br>";
							echo "<font class='error'>".t("Tallenna/avaa tulos excelissä")."!</font><br><br>";
						}
					}
				}

				if ($query != "") {
					if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta.php") !== FALSE) {
						if(@include('Spreadsheet/Excel/Writer.php')) {

							//keksitään failille joku varmasti uniikki nimi:
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

						if ($osoitetarrat != "" and $row["astunnus"] > 0) {
							$tarra_aineisto .= $row["astunnus"].",";
						}

						if (mysql_num_rows($result) <= $rivilimitti) echo "<tr>";

						// echotaan kenttien sisältö
						for ($i=0; $i < mysql_num_fields($result); $i++) {

							// jos kyseessa on asiakasosasto, haetaan sen nimi
							if (mysql_field_name($result, $i) == "asos") {
								$query = "	SELECT distinct avainsana.selite, ".avain('select')."
											FROM avainsana
											".avain('join','ASOSASTO_')."
											WHERE avainsana.yhtio in ($yhtio) and avainsana.laji='ASIAKASOSASTO' and avainsana.selite='$row[$i]'
											limit 1";
								$osre = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($osre) == 1) {
									$osrow = mysql_fetch_array($osre);
									$row[$i] = $row[$i] ." ". $osrow['selitetark'];
								}
							}

							// jos kyseessa on asiakasryhma, haetaan sen nimi
							if (mysql_field_name($result, $i) == "asry") {
								$query = "	SELECT distinct avainsana.selite, ".avain('select')."
											FROM avainsana
											".avain('join','ASRYHMA_')."
											WHERE avainsana.yhtio in ($yhtio) and avainsana.laji='ASIAKASRYHMA' and avainsana.selite='$row[$i]'
											limit 1";
								$osre = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($osre) == 1) {
									$osrow = mysql_fetch_array($osre);
									$row[$i] = $row[$i] ." ". $osrow['selitetark'];
								}
							}

							// jos kyseessa on tuoteosasto, haetaan sen nimi
							if (mysql_field_name($result, $i) == "tuos") {
								$query = "	SELECT avainsana.selite, ".avain('select')."
											FROM avainsana
											".avain('join','OSASTO_')."
											WHERE avainsana.yhtio in ($yhtio) and avainsana.laji='OSASTO' and avainsana.selite='$row[$i]'
											limit 1";
								$osre = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($osre) == 1) {
									$osrow = mysql_fetch_array($osre);
									$row[$i] = $row[$i] ." ". $osrow['selitetark'];
								}
							}

							// jos kyseessa on tuoteosasto, haetaan sen nimi
							if (mysql_field_name($result, $i) == "tuoteryhmä") {
								$query = "	SELECT avainsana.selite, ".avain('select')."
											FROM avainsana
											".avain('join','TRY_')."
											WHERE avainsana.yhtio in ($yhtio) and avainsana.laji='TRY' and avainsana.selite='$row[$i]'
											limit 1";
								$osre = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($osre) == 1) {
									$osrow = mysql_fetch_array($osre);
									$row[$i] = $row[$i] ." ". $osrow['selitetark'];
								}
							}

							// jos kyseessa on myyjä, haetaan sen nimi
							if (mysql_field_name($result, $i) == "tuotemyyja" or mysql_field_name($result, $i) == "asiakasmyyja") {
								$query = "	SELECT nimi
											FROM kuka
											WHERE yhtio in ($yhtio) and myyja='$row[$i]' and myyja!='0' limit 1";
								$osre = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($osre) == 1) {
									$osrow = mysql_fetch_array($osre);
									$row[$i] = $row[$i] ." ". $osrow['nimi'];
								}
							}

							// jos kyseessa on myyjä, haetaan sen nimi
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
									$row[$i] = t("Tyhjä");
								}
							}

							// jos kyseessa on ostaja, haetaan sen nimi
							if (mysql_field_name($result, $i) == "tuoteostaja") {
								$query = "	SELECT nimi
											FROM kuka
											WHERE yhtio in ($yhtio) and myyja='$row[$i]' and myyja!='0' limit 1";
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
								$query = "	SELECT group_concat(concat_ws('/',ytunnus,nimi)) nimi
											FROM toimi
											WHERE yhtio in ($yhtio) and ytunnus in ($toimittajat)";
								$osre = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($osre) == 1) {
									$osrow = mysql_fetch_array($osre);
									$row[$i] = $osrow['nimi'];
								}
							}

							// kateprossa
							if (mysql_field_name($result, $i) == "kateprosnyt") {
								if ($row["myyntinyt"] != 0) {
									$row[$i] = round($row["katenyt"] / $row["myyntinyt"] * 100, 2);
								}
								else {
									$row[$i] = 0;
								}
							}

							// kateprossa
							if (mysql_field_name($result, $i) == "kateprosed") {
								if ($row["myyntied"] != 0) {
									$row[$i] = round($row["kateed"] / $row["myyntied"] * 100, 2);
								}
								else {
									$row[$i] = 0;
								}
							}

							// kustannuspaikka
							if (mysql_field_name($result, $i) == "kustpaikka") {
								// näytetään soveltuvat kustannuspaikka
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

									if ($k < 0) {
										$tunken = "ostorivitunnus";
									}
									else {
										$tunken = "myyntirivitunnus";
									}

									$query = "	SELECT sarjanumero
												FROM sarjanumeroseuranta
												WHERE yhtio in ($yhtio)
												and $tunken=$s";
									$osre = mysql_query($query) or pupe_error($query);

									if (mysql_num_rows($osre) > 0) {
										$osrow = mysql_fetch_array($osre);
										$row[$i] .= "<a href='../tilauskasittely/sarjanumeroseuranta.php?sarjanumero_haku=".urlencode($osrow["sarjanumero"])."'>".$osrow['sarjanumero']."</a><br>";
									}
								}
								$row[$i] = substr($row[$i], 0, -4);
							}

							// jos kyseessa on varastonarvo
							if (mysql_field_name($result, $i) == "varastonarvo") {
								list($varvo, $kierto) = vararvo($row["tuoteno"], $vvl, $kkl, $ppl);

								$row[$i] = $varvo;
							}

							// jos kyseessa on varastonkierto
							if (mysql_field_name($result, $i) == "kierto") {
								$row[$i] = $kierto;
							}

							// Jos gruupataan enemmän kuin yksi taso niin tehdään välisumma
							if ($gluku > 1 and $edluku != $row[0] and $edluku != 'x' and $piiyhteensa == '' and strpos($group, ',') !== FALSE and substr($group, 0, 13) != "tuote.tuoteno") {
								$excelsarake = $myyntiind = $kateind = $nettokateind = 0;

								foreach($valisummat as $vnim => $vsum) {
									if ((string) $vsum != '') {
										$vsum = sprintf("%.2f", $vsum);
									}

									if ($vnim == "kateprosnyt") {
										if ($valisummat["myyntinyt"] <> 0) 		$vsum = round($valisummat["katenyt"] / $valisummat["myyntinyt"] * 100, 2);
									}
									if ($vnim == "kateprosed") {
										if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["kateed"] / $valisummat["myyntied"] * 100, 2);
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

							// hoidetaan pisteet piluiksi!!
							if (is_numeric($row[$i]) and (mysql_field_type($result,$i) == 'real' or mysql_field_type($result,$i) == 'int' or substr(mysql_field_name($result, $i),0 ,4) == 'kate')) {
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

					// jos gruupataan enemmän kuin yksi taso niin tehdään välisumma
					if ($gluku > 1 and $mukaan != 'tuote' and $piiyhteensa == '') {

						if (mysql_num_rows($result) <= $rivilimitti) echo "<tr>";

						$excelsarake = $myyntiind = $kateind = $nettokateind = 0;

						foreach($valisummat as $vnim => $vsum) {
							if ((string) $vsum != '') {
								$vsum = sprintf("%.2f", $vsum);
							}

							if ($vnim == "kateprosnyt") {
								if ($valisummat["myyntinyt"] <> 0) 		$vsum = round($valisummat["katenyt"] / $valisummat["myyntinyt"] * 100, 2);
							}
							if ($vnim == "kateprosed") {
								if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["kateed"] / $valisummat["myyntied"] * 100, 2);
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

					$excelsarake = $myyntiind = $kateind = $nettokateind = 0;

					foreach($totsummat as $vnim => $vsum) {
						if ((string) $vsum != '') {
							$vsum = sprintf("%.2f", $vsum);
						}

						if ($vnim == "kateprosnyt") {
							if ($totsummat["myyntinyt"] <> 0) 		$vsum = round($totsummat["katenyt"] / $totsummat["myyntinyt"] * 100, 2);
						}
						if ($vnim == "kateprosed") {
							if ($totsummat["myyntied"] <> 0) 		$vsum = round($totsummat["kateed"] / $totsummat["myyntied"] * 100, 2);
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
			//Käyttöliittymä
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

			// tässä on tämä "perusnäkymä" mikä tulisi olla kaikissa myynnin raportoinneissa..

			if ($ajotapa == "lasku") {
				$chk1 = "SELECTED";
			}
			elseif ($ajotapa == "tilaus") {
				$chk2 = "SELECTED";
			}
			elseif ($ajotapa == "tilausjaauki") {
				$chk3 = "SELECTED";
			}
			else {
				$chk1 = "SELECTED";
			}

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Valitse ajotapa:")."</th>";

			echo "<td><select name='ajotapa'>";
			echo "<option value='lasku'  		$chk1>".t("Laskuista")."</option>";
			echo "<option value='tilaus' 		$chk2>".t("Laskutetuista tilauksista")."</option>";
			echo "<option value='tilausjaauki'	$chk3>".t("Laskutetuista sekä avoimista tilauksista")."</option>";
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
			echo "<th>".t("Ajotavan lisäparametrit:")."</th>";

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

			// voidaan valita listaukseen useita konserniyhtiöitä, jos käyttäjällä on "PÄIVITYS" oikeus tähän raporttiin
			if (mysql_num_rows($result) > 0 and $oikeurow['paivitys'] != "") {
				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Valitse yhtiö")."</th>";

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
			echo "<th>".t("Valitse asiakasryhmät").":</th>";
			echo "<th>".t("Valitse asiakaspiirit").":</th></tr>";

			echo "<tr>";
			echo "<td valign='top'>";

			// näytetään soveltuvat asiakasosastot
			$query = "	SELECT distinct avainsana.selite, ".avain('select')."
						FROM avainsana
						".avain('join','ASOSASTO_')."
						WHERE avainsana.yhtio in ($yhtio) and avainsana.laji='ASIAKASOSASTO'
						ORDER BY avainsana.jarjestys, avainsana.selite";
			$res2  = mysql_query($query) or die($query);

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

			// näytetään soveltuvat asiakasryhmat
			$query = "	SELECT distinct avainsana.selite, ".avain('select')."
						FROM avainsana
						".avain('join','ASRYHMA_')."
						WHERE avainsana.yhtio in ($yhtio) and avainsana.laji='ASIAKASRYHMA'
						ORDER BY avainsana.jarjestys, avainsana.selite";
			$res2  = mysql_query($query) or die($query);

			echo "<select name='mul_asiakasryhma[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_asiakasryhma!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_asiakasryhma)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei asiakasryhmää")."</option>";

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

			// näytetään sallityt piirit
			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio='$kukarow[yhtio]' and
						laji='piiri'
						$asiakasrajaus_avainsana
						order by jarjestys, selite";
			$res2  = mysql_query($query) or die($query);

			echo "<select name='mul_piiri[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_piiri!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_piiri)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei asiakaspiiriä")."</option>";

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
			echo "<th>".t("Prio").": <input type='text' name='jarjestys[20]' size='2' value='$jarjestys[20]'> ".t("Asiakasryhmittäin")." <input type='checkbox' name='ruksit[20]' value='asiakasryhma' $ruk20chk></th>";
			echo "<th>".t("Prio").": <input type='text' name='jarjestys[30]' size='2' value='$jarjestys[30]'> ".t("Aiakaspiireittäin")." <input type='checkbox' name='ruksit[30]' value='piiri' $ruk30chk></th><tr>";
			echo "<th colspan='2'></th><th>".t("Piiri")." <input type='radio' name='piirivalinta' value='lasku' $laskuvalintachk>".t("Laskuilta");
			echo "<input type='radio' name='piirivalinta' value='asiakas' $asiakasvalintachk>".t("Asiakkailta")."</th></tr>";
			echo "</table><br>\n";



			echo "<table><tr>";

			echo "<th>".t("Valitse tuoteosastot").":</th>";
			echo "<th>".t("Valitse tuoteryhmät").":</th></tr>";

			echo "<tr>";
			echo "<td valign='top'>";

			// näytetään soveltuvat osastot
			$query = "	SELECT avainsana.selite, ".avain('select')."
						FROM avainsana ".avain('join','OSASTO_')."
						WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO'
						order by avainsana.jarjestys, avainsana.selite";
			$res2  = mysql_query($query) or die($query);

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

			// näytetään soveltuvat tryt
			$query = "	SELECT avainsana.selite, ".avain('select')."
						FROM avainsana ".avain('join','TRY_')."
						WHERE avainsana.yhtio='$kukarow[yhtio]'
						and avainsana.laji='TRY'
						order by avainsana.jarjestys, avainsana.selite";
			$res2  = mysql_query($query) or die($query);

			echo "<select name='mul_try[]' multiple='TRUE' size='10' style='width:100%;'>";

			$mul_check = '';
			if ($mul_try!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_try)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoterymää")."</option>";

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
			echo "<th>".t("Prio").": <input type='text' name='jarjestys[50]' size='2' value='$jarjestys[50]'> ".t("Tuoteryhmittäin")." <input type='checkbox' name='ruksit[50]' value='tuoteryhma' $ruk50chk></th></tr>";

			echo "</table><br>\n";


			echo "<table><tr>";

			echo "<th>".t("Valitse kustannuspaikat").":</th></tr>";

			echo "<tr>";
			echo "<td valign='top'>";

			// näytetään soveltuvat osastot
			$query = "	SELECT tunnus selite, nimi selitetark
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = 'K'
						ORDER BY nimi";
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

			// lisärajaukset näkymä..
			if ($ruksit[60]  != '') 	$ruk60chk  	= "CHECKED";
			if ($ruksit[70]  != '') 	$ruk70chk  	= "CHECKED";
			if ($ruksit[80]  != '') 	$ruk80chk  	= "CHECKED";
			if ($ruksit[90]  != '') 	$ruk90chk  	= "CHECKED";
			if ($ruksit[100]  != '') 	$ruk100chk 	= "CHECKED";
			if ($ruksit[110]  != '') 	$ruk110chk 	= "CHECKED";
			if ($ruksit[120]  != '') 	$ruk120chk 	= "CHECKED";
			if ($ruksit[130]  != '') 	$ruk130chk 	= "CHECKED";
			if ($ruksit[140]  != '') 	$ruk140chk 	= "CHECKED";
			if ($ruksit[150] != '') 	$ruk150chk 	= "CHECKED";
			if ($ruksit[160] != '')		$ruk160chk 	= "CHECKED";

			if ($nimitykset != '')   	$nimchk   	= "CHECKED";
			if ($kateprossat != '')  	$katchk   	= "CHECKED";
			if ($osoitetarrat != '') 	$tarchk   	= "CHECKED";
			if ($piiyhteensa != '')  	$piychk   	= "CHECKED";
			if ($sarjanumerot != '')  	$sarjachk 	= "CHECKED";
			if ($kuukausittain != '')	$kuuchk	  	= "CHECKED";
			if ($varastonarvo != '')	$varvochk 	= "CHECKED";
			if ($piiloed != '')			$piiloedchk = "CHECKED";
			if ($tilrivikomm != '')		$tilrivikommchk = "CHECKED";
			if ($vain_excel != '')		$vain_excelchk 	= "CHECKED";

			echo "<table>
				<tr>
				<th>".t("Lisärajaus")."</th>
				<th>".t("Prio")."</th>
				<th> x</th>
				<th>".t("Rajaus")."</th>
				</tr>
				<tr>
				<tr>
				<th>".t("Listaa y-tunnuksella")."</th>
				<td><input type='text' name='jarjestys[60]' size='2' value='$jarjestys[60]'></td>
				<td><input type='checkbox' name='ruksit[60]' value='ytunnus' $ruk60chk></td>
				<td><input type='text' name='ytunnus' value='$ytunnus'></td>
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
				<th>".t("Listaa tuotemyyjittäin")."</th>
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
				<th>".t("Listaa asiakasmyyjittäin")."</th>
				<td><input type='text' name='jarjestys[100]' size='2' value='$jarjestys[100]'></td>
				<td><input type='checkbox' name='ruksit[100]' value='asiakasmyyja' $ruk100chk></td>
				<td>";

			$query = "SELECT distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
			$vresult = mysql_query($query) or pupe_error($query);

			echo "<select name='rajaus[100]'>";
			echo "<option value = '' ></option>";

			while ($vrow=mysql_fetch_array($vresult)) {

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
				<th>".t("Listaa merkeittäin")."</th>
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
				<th>".t("Listaa tilaustyypeittäin")."</th>
				<td><input type='text' name='jarjestys[150]' size='2' value='$jarjestys[150]'></td>
				<td><input type='checkbox' name='ruksit[150]' value='tilaustyyppi' $ruk150chk></td>
				<td><input type='text' name='rajaus[150]' value='$rajaus[150]'></td>
				<td class='back'>".t("(Toimii vain jos ajat raporttia tilauksista)")."</td>
				</tr>
				<tr>
				<th>".t("Listaa myyjittäin")."</th>
				<td><input type='text' name='jarjestys[160]' size='2' value='$jarjestys[160]'></td>
				<td><input type='checkbox' name='ruksit[160]' value='myyja' $ruk160chk></td>
				<td><input type='text' name='rajaus[160]' value='$rajaus[160]'></td>
				</tr>
				<tr>
				<td class='back'><br></td>
				</tr>
				<tr>
				<th>".t("Näytä tuotteiden nimitykset")."</th>
				<td><input type='checkbox' name='nimitykset' $nimchk></td>
				<td></td>
				<td class='back'>".t("(Toimii vain jos listaat tuotteittain)")."</td>
				</tr>
				<tr>
				<th>".t("Näytä sarjanumerot")."</th>
				<td><input type='checkbox' name='sarjanumerot' $sarjachk></td>
				<td></td>
				<td class='back'>".t("(Toimii vain jos listaat tuotteittain)")."</td>
				</tr>
				<tr>
				<th>".t("Näytä varastonarvo")."</th>
				<td><input type='checkbox' name='varastonarvo' $varvochk></td>
				<td></td>
				<td class='back'>".t("(Toimii vain jos listaat tuotteittain)")."</td>
				</tr>
				<tr>
				<th>".t("Näytä kateprosentit")."</th>
				<td><input type='checkbox' name='kateprossat' $katchk></td>
				<td></td>
				</tr>
				<tr>
				<th>".t("Piilota välisummat")."</th>
				<td><input type='checkbox' name='piiyhteensa' $piychk></td>
				<td></td>
				</tr>
				<tr>
				<th>".t("Tulosta myynti kuukausittain")."</th>
				<td><input type='checkbox' name='kuukausittain' $kuuchk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("Piilota edellisen kauden sarakkeet")."</th>
				<td><input type='checkbox' name='piiloed' $piiloedchk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("Näytä tilausrivin kommentti")."</th>
				<td><input type='checkbox' name='tilrivikomm' $tilrivikommchk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>".t("Tulosta osoitetarrat")."</th>
				<td><input type='checkbox' name='osoitetarrat' $tarchk></td>
				<td></td>
				<td class='back'>".t("(Toimii vain jos listaat asiakkaittain)")."</td>
				</tr>
				<th>".t("Raportti vain Exceliin")."</th>
				<td><input type='checkbox' name='vain_excel' $vain_excelchk></td>
				<td></td>
				<td class='back'></td>
				</tr>
				</table><br>";

			// päivämäärärajaus
			echo "<table>";
			echo "<tr>
				<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr>\n
				<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
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
