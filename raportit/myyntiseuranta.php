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

		echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
			<!--

			function toggleAll(toggleBox) {

				var currForm = toggleBox.form;
				var isChecked = toggleBox.checked;
				var nimi = toggleBox.name;

				for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
					if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,7) == nimi && currForm.elements[elementIdx].value != '".t("Ei valintaa")."') {
						currForm.elements[elementIdx].checked = isChecked;
					}
				}
			}

			//-->
			</script>";

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

			list($tee,$ajotapa,$ajotapanlisa,$yhtiot, $asiakasosasto, $asiakasosasto2, $asiakasryhma, $asiakasryhma2,
			$mul_osasto, $mul_piiri, $mul_try, $asiakas, $toimittaja, $jarjestys, $ruksit, $nimitykset, $kateprossat, $piiyhteensa,
			$osoitetarrat, $ppa, $kka, $vva, $ppl, $kkl, $vvl) = explode("//", $muutparametrit);

			if ($yhtiot != "") $yhtiot_x 			= explode("!!", $yhtiot);
			else unset($yhtiot);
			if ($mul_osasto != "") $mul_osasto_x 	= explode("!!", $mul_osasto);
			else unset($mul_osasto);
			if ($mul_try != "") $mul_try_x 			= explode("!!", $mul_try);
			else unset($mul_try);
			if ($jarjestys != "") $jarjestys_x 		= explode("!!", $jarjestys);
			else unset($jarjestys);
			if ($ruksit != "") $ruksit_x 			= explode("!!", $ruksit);
			else unset($ruksit);
			if ($mul_piiri != "") $mul_piiri_x 		= explode("!!", $mul_piiri);
			else unset($mul_piiri);

			if (count($yhtiot_x) > 0) {
				$yhtiot = array();

				foreach($yhtiot_x as $a) {
					list ($b, $c) = explode("#", $a);

					$yhtiot[$b] = $c;
				}
			}
			if (count($mul_osasto_x) > 0) {
				$mul_osasto = array();

				foreach($mul_osasto_x as $a) {
					list ($b, $c) = explode("#", $a);

					$mul_osasto[$b] = $c;
				}
			}
			if (count($mul_try_x) > 0) {
				$mul_try = array();

				foreach($mul_try_x as $a) {
					list ($b, $c) = explode("#", $a);

					$mul_try[$b] = $c;
				}
			}
			if (count($mul_piiri_x) > 0) {
				$mul_piiri = array();

				foreach($mul_piiri_x as $a) {
					list ($b, $c) = explode("#", $a);

					$mul_piiri[$b] = $c;
				}
			}
			if (count($jarjestys_x) > 0) {
				$jarjestys = array();

				foreach($jarjestys_x as $a) {
					list ($b, $c) = explode("#", $a);

					$jarjestys[$b] = $c;
				}
			}
			if (count($ruksit_x) > 0) {
				$ruksit = array();

				foreach($ruksit_x as $a) {
					list ($b, $c) = explode("#", $a);

					$ruksit[$b] = $c;
				}
			}
		}

		// tutkaillaan saadut muuttujat
		$asiakasosasto   = trim($asiakasosasto);
		$asiakasryhma    = trim($asiakasryhma);
		$asiakas         = trim($asiakas);
		$toimittaja    	 = trim($toimittaja);

		if ($asiakasosasto == "") $asiakasosasto = trim($asiakasosasto2);
		if ($asiakasryhma  == "") $asiakasryhma  = trim($asiakasryhma2);

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

		if ($tee == 'go' and $asiakas != '' or $toimittaja != '') {
			$muutparametrit = $tee."//".$ajotapa."//".$ajotapanlisa."//";

			if (count($yhtiot) > 0) {
				foreach($yhtiot as $a => $b) {
					$muutparametrit .= "$a#".$b."!!";
				}

				$muutparametrit = substr($muutparametrit, 0, -2)."//";
			}
			else {
				$muutparametrit .= "//";
			}

			$muutparametrit .= $asiakasosasto."//".$asiakasosasto2."//".$asiakasryhma."//".$asiakasryhma2."//";

			if (count($mul_osasto) > 0) {
				foreach($mul_osasto as $a => $b) {
					$muutparametrit .= "$a#".$b."!!";
				}

				$muutparametrit = substr($muutparametrit, 0, -2)."//";
			}
			else {
				$muutparametrit .= "//";
			}

			if (count($mul_try) > 0) {
				foreach($mul_try as $a => $b) {
					$muutparametrit .= "$a#".$b."!!";
				}

				$muutparametrit = substr($muutparametrit, 0, -2)."//";
			}
			else {
				$muutparametrit .= "//";
			}

			if (count($mul_piiri) > 0) {
				foreach($mul_piiri as $a => $b) {
					$muutparametrit .= "$a#".$b."!!";
				}

				$muutparametrit = substr($muutparametrit, 0, -2)."//";
			}
			else {
				$muutparametrit .= "//";
			}

			$muutparametrit .= $asiakas."//".$toimittaja."//";

			if (count($jarjestys) > 0) {
				foreach($jarjestys as $a => $b) {
					$muutparametrit .= "$a#".$b."!!";
				}

				$muutparametrit = substr($muutparametrit, 0, -2)."//";
			}
			else {
				$muutparametrit .= "//";
			}

			if (count($ruksit) > 0) {
				foreach($ruksit as $a => $b) {
					$muutparametrit .= "$a#".$b."!!";
				}

				$muutparametrit = substr($muutparametrit, 0, -2)."//";
			}
			else {
				$muutparametrit .= "//";
			}

			$muutparametrit .= $nimitykset."//".$kateprossat."//".$piiyhteensa."//".$osoitetarrat."//".$ppa."//".$kka."//".$vva."//".$ppl."//".$kkl."//".$vvl;
		}

		if ($tee == 'go' and $asiakas != '') {
			$ytunnus = $asiakas;

			require("../inc/asiakashaku.inc");

			if ($ytunnus != '') {
				$asiakas = $ytunnus;
				$ytunnus = "";
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

			foreach ($jarjestys as $arvo) {
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
				if ($ruksit[$i] != "") $apu[] = $ruksit[$i];
			}

			foreach ($apu as $mukaan) {

				if ($mukaan == "ytunnus" and $osoitetarrat == "") {
					if ($group!="") $group .= ",asiakas.tunnus";
					else $group  .= "asiakas.tunnus";
					$select .= "concat_ws(' ', asiakas.ytunnus, asiakas.toim_ovttunnus, asiakas.toim_nimi) ytunnus, ";
					$order  .= "asiakas.ytunnus,";
					$gluku++;
				}
				elseif ($mukaan == "ytunnus" and $osoitetarrat != "") {
					if ($group!="") $group .= ",asiakas.tunnus";
					else $group  .= "asiakas.tunnus";
					$select .= "asiakas.tunnus, concat_ws(' ', asiakas.ytunnus, asiakas.toim_ovttunnus, asiakas.toim_nimi) ytunnus, ";
					$order  .= "asiakas.ytunnus,";
					$gluku++;
				}

				if ($mukaan == "piiri") {
					if ($group!="") $group .= ",asiakas.piiri";
					else $group  .= "asiakas.piiri";
					$select .= "asiakas.piiri piiri, ";
					$order  .= "asiakas.piiri,";
					$gluku++;
				}

				if ($mukaan == "maa") {
					if ($group!="") $group .= ",asiakas.maa";
					else $group  .= "asiakas.maa";
					$select .= "asiakas.maa maa, ";
					$order  .= "asiakas.maa,";
					$gluku++;
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
						$order  .= "tuote.tuoteno, ";
						$gluku++;
					}
					if ($sarjanumerot != '') {
						$select .= "group_concat(concat(tilausrivi.tunnus,'#',tilausrivi.kpl)) sarjanumero, ";
					}
				}

				if ($mukaan == "tuotemyyja") {
					if ($group!="") $group .= ",tuote.myyjanro";
					else $group  .= "tuote.myyjanro";
					$select .= "tuote.myyjanro tuotemyyja, ";
					$order  .= "tuote.myyjanro,";
					$gluku++;
				}

				if ($mukaan == "asiakasmyyja") {
					if ($group!="") $group .= ",asiakas.myyjanro";
					else $group  .= "asiakas.myyjanro";
					$select .= "asiakas.myyjanro asiakasmyyja, ";
					$order  .= "asiakas.myyjanro,";
					$gluku++;
				}

				if ($mukaan == "tuoteostaja") {
					if ($group!="") $group .= ",tuote.ostajanro";
					else $group  .= "tuote.ostajanro";
					$select .= "tuote.ostajanro tuoteostaja, ";
					$order  .= "tuote.ostajanro,";
					$gluku++;
				}

				if ($mukaan == "merkki") {
					if ($group!="") $group .= ",tuote.tuotemerkki";
					else $group  .= "tuote.tuotemerkki";
					$select .= "tuote.tuotemerkki merkki, ";
					$order  .= "tuote.tuotemerkki,";
					$gluku++;
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
				}

			}

			if ($asiakasosasto != "") {
				if ($group!="") $group .= ",asiakas.osasto";
				else $group .= "asiakas.osasto";
				$select .= "asiakas.osasto asos, ";
				$order  .= "asiakas.osasto+0,";
				$gluku++;
			}

			if ($asiakasryhma != "") {
				if ($group!="") $group .= ",asiakas.ryhma";
				else $group .= "asiakas.ryhma";
				$select .= "asiakas.ryhma asry, ";
				$order  .= "asiakas.ryhma+0,";
				$gluku++;
			}

			if ($mul_osasto  != "") {
				if ($group!="") $group .= ",tuote.osasto";
				else $group .= "tuote.osasto";
				$select .= "tuote.osasto tuos, ";
				$order  .= "tuote.osasto+0,";
				$gluku++;
			}

			if ($mul_try != "") {
				if ($group!="") $group .= ",tuote.try";
				else $group .= "tuote.try";
				$select .= "tuote.try tury, ";
				$order  .= "tuote.try+0,";
				$gluku++;
			}

			if ($mul_piiri != "") {
				if ($group!="") $group .= ",asiakas.piiri";
				else $group .= "asiakas.piiri";
				$select .= "asiakas.piiri aspiiri, ";
				$order  .= "asiakas.piiri+0,";
				$gluku++;
			}

			if ($asiakasryhma  == "kaikki") {
				$asiakasryhma = "";
				$asiakasryhmasel = "selected";
			}

			if ($asiakasosasto == "kaikki") {
				$asiakasosasto = "";
				$asiakasosastosel = "selected";
			}

			if ($asiakasryhma  != "") $lisa .= " and asiakas.ryhma     = '$asiakasryhma' ";

			if ($asiakasosasto != "") $lisa .= " and asiakas.osasto    = '$asiakasosasto' ";

			if (count($mul_osasto) > 0) {
				$sel_osasto = "('".str_replace(',','\',\'',implode(",", $mul_osasto))."')";
				$lisa .= " and tuote.osasto in $sel_osasto ";
			}
			if (is_array($mul_try) and count($mul_try) > 0) {
				$sel_tuoteryhma = "('".str_replace(',','\',\'',implode(",", $mul_try))."')";
				$lisa .= " and tuote.try in $sel_tuoteryhma ";
			}
			if (is_array($mul_piiri) and count($mul_piiri) > 0) {
				$sel_piiri = "('".str_replace(',','\',\'',implode(",", $mul_piiri))."')";
				$lisa .= " and asiakas.piiri in $sel_piiri ";
			}


			if ($toimittaja != "") {
				$query = "select tuoteno from tuotteen_toimittajat where yhtio in ($yhtio) and toimittaja='$toimittaja'";
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
				$query = "select group_concat(tunnus) from asiakas where yhtio in ($yhtio) and ytunnus = '$asiakas' $asiakasrajaus";
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

			if ($kateprossat != "") {
				$katelisa = " 0 kateprosnyt, 0 kateprosed, ";
			}
			else {
				$katelisa = "";
			}

			// Jos ei olla valittu mitään
			if ($group == "") {
				$select = "tuote.yhtio, ";
				$group = "lasku.yhtio";
			}

			$vvaa = $vva - '1';
			$vvll = $vvl - '1';

			if ($ajotapa == 'tilausjaauki') {
				$tilauslisa1 = " sum(if(tilausrivi.laadittu >= '$vva-$kka-$ppa 00:00:00'  and tilausrivi.laadittu <= '$vvl-$kkl-$ppl 23:59:59' and tilausrivi.laskutettuaika= '0000-00-00', tilausrivi.varattu+tilausrivi.jt,0)) myykpllaskuttamattanyt, ";
				$tilauslisa2 = " sum(if(tilausrivi.laadittu >= '$vva-$kka-$ppa 00:00:00'  and tilausrivi.laadittu <= '$vvl-$kkl-$ppl 23:59:59' and tilausrivi.laskutettuaika= '0000-00-00', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),0)) myyntilaskuttamattanyt, ";
			}
			else {
				$tilauslisa1 = "";
				$tilauslisa2 = "";
			}

			if ($ajotapanlisa == "erikseen") {
				$tilauslisa3 = ", if(tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt>0, 'Veloitus', 'Hyvitys') rivityyppi";
				$group .= ", rivityyppi";
			}
			else {
				$tilauslisa3 = "";
			}

			$query = "	SELECT $select
						$tilauslisa1
						sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kpl,0)) myykplnyt,
						sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kpl,0)) myykpled,
						$tilauslisa2
						sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta,0)) myyntinyt,
						sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.rivihinta,0)) myyntied,
						round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta,0)) /
						sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.rivihinta,0)),2) myyntiind,
						sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate,0)) katenyt,
						sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kate,0)) kateed,
						round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.kate,0)) /
						sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kate,0)),2) kateind,
						$katelisa
						sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',
						tilausrivi.kate - (tilausrivi.kate*IFNULL(asiakas.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(toimitustapa.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(tuote.kuluprosentti,0)/100),0)) nettokatenyt,
						sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',
						tilausrivi.kate - (tilausrivi.kate*IFNULL(asiakas.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(toimitustapa.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(tuote.kuluprosentti,0)/100),0)) nettokateed,
						round(sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',
						tilausrivi.kate - (tilausrivi.kate*IFNULL(asiakas.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(toimitustapa.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(tuote.kuluprosentti,0)/100),0)) /
						sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',
						tilausrivi.kate - (tilausrivi.kate*IFNULL(asiakas.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(toimitustapa.kuluprosentti,0)/100) - (tilausrivi.kate*IFNULL(tuote.kuluprosentti,0)/100),0)),2) nettokateind
						$tilauslisa3
						FROM lasku use index (yhtio_tila_tapvm)
						JOIN tilausrivi use index ($index) ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.$ouusio=lasku.tunnus and tilausrivi.tyyppi='L'
						LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						LEFT JOIN asiakas use index (PRIMARY) ON asiakas.yhtio=lasku.yhtio and asiakas.tunnus=lasku.liitostunnus
						LEFT JOIN toimitustapa ON lasku.yhtio=toimitustapa.yhtio and lasku.toimitustapa=toimitustapa.selite
						WHERE lasku.yhtio in ($yhtio)
						$asiakasrajaus and
						lasku.tila in ($tila)";

			if ($ajotapa == 'tilausjaauki') {
				$query .= "	and lasku.alatila in ('','A','B','C','D','J','E','F','T','U','X')
							and ((lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00'  and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59') or (lasku.tapvm >= '$vva-$kka-$ppa' and lasku.tapvm <= '$vvl-$kkl-$ppl') or (lasku.tapvm >= '$vvaa-$kka-$ppa' and lasku.tapvm <= '$vvll-$kkl-$ppl'))";
			}
			else {
				$query .= "	and lasku.alatila='X'
							and ((lasku.tapvm >= '$vva-$kka-$ppa'  and lasku.tapvm <= '$vvl-$kkl-$ppl') or (lasku.tapvm >= '$vvaa-$kka-$ppa' and lasku.tapvm <= '$vvll-$kkl-$ppl'))";
			}

			$query .= "	$lisa
						group by $group
						order by $order myyntinyt";

			// ja sitten ajetaan itte query
			if ($query != "") {

				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) > 4000) {
					echo "<font class='message'>".t("Hakutulos oli liian suuri. Tee tarkempi rajaus")."!<br></font>";
					$query = "";
				}

			}

			if ($query != "") {
				
				if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta.php") !== FALSE) {
					if(include('Spreadsheet/Excel/Writer.php')) {

						//keksitään failille joku varmasti uniikki nimi:
						list($usec, $sec) = explode(' ', microtime());
						mt_srand((float) $sec + ((float) $usec * 100000));
						$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

						$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
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

				echo "<table><tr>";

				// echotaan kenttien nimet
				for ($i=0; $i < mysql_num_fields($result); $i++) echo "<th>".t(mysql_field_name($result,$i))."</th>";

				if(isset($workbook)) {
					for ($i=0; $i < mysql_num_fields($result); $i++) $worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
					$excelrivi++;
				}

				echo "</tr>\n";
				$ulos .= "\r\n";

				$edluku = "x"; // katellaan muuttuuko joku arvo
				$myyntinyt = $myyntied = $katenyt = $kateed = $nettokatenyt = $nettokateed = $myyntikplnyt = $myyntikpled = 0;
				$totmyyntinyt = $totmyyntied = $totkatenyt = $totkateed = $totnettokatenyt = $totnettokateed = $totmyyntikplnyt = $totmyyntikpled = 0;

				$tarra_aineisto = "";

				while ($row = mysql_fetch_array($result)) {

					if ($osoitetarrat != "" and $row[0] > 0) {
						$tarra_aineisto .= $row[0].",";
					}

					echo "<tr>";
					// echotaan kenttien sisältö
					for ($i=0; $i < mysql_num_fields($result); $i++) {

						// jos kyseessa on asiakasosasto, haetaan sen nimi
						if (mysql_field_name($result, $i) == "asos") {
							$query = "	SELECT distinct avainsana.selite, ".avain('select')."
										FROM avainsana
										".avain('join','ASOSASTO_')."
										WHERE avainsana.yhtio in ($yhtio) and avainsana.laji='ASIAKASOSASTO' and avainsana.selite='$row[$i]' limit 1";
							$osre = mysql_query($query) or pupe_error($query);
							if (mysql_num_rows($osre) == 1) {
								$osrow= mysql_fetch_array($osre);
								$row[$i] = $row[$i] ." ". $osrow['selitetark'];
							}
						}

						// jos kyseessa on asiakasryhma, haetaan sen nimi
						if (mysql_field_name($result, $i) == "asry") {
							$query = "	SELECT distinct avainsana.selite, ".avain('select')."
										FROM avainsana
										".avain('join','ASRYHMA_')."
										WHERE avainsana.yhtio in ($yhtio) and avainsana.laji='ASIAKASRYHMA' and avainsana.selite='$row[$i]' limit 1";
							$osre = mysql_query($query) or pupe_error($query);
							if (mysql_num_rows($osre) == 1) {
								$osrow= mysql_fetch_array($osre);
								$row[$i] = $row[$i] ." ". $osrow['selitetark'];
							}
						}

						// jos kyseessa on tuoteosasto, haetaan sen nimi
						if (mysql_field_name($result, $i) == "tuos") {
							$query = "	SELECT avainsana.selite, ".avain('select')."
										FROM avainsana
										".avain('join','OSASTO_')."
										WHERE avainsana.yhtio in ($yhtio) and avainsana.laji='OSASTO' and avainsana.selite='$row[$i]' limit 1";
							$osre = mysql_query($query) or pupe_error($query);
							if (mysql_num_rows($osre) == 1) {
								$osrow= mysql_fetch_array($osre);
								$row[$i] = $row[$i] ." ". $osrow['selitetark'];
							}
						}

						// jos kyseessa on tuoteosasto, haetaan sen nimi
						if (mysql_field_name($result, $i) == "tury") {
							$query = "	SELECT avainsana.selite, ".avain('select')."
										FROM avainsana
										".avain('join','TRY_')."
										WHERE avainsana.yhtio in ($yhtio) and avainsana.laji='TRY' and avainsana.selite='$row[$i]' limit 1";
							$osre = mysql_query($query) or pupe_error($query);
							if (mysql_num_rows($osre) == 1) {
								$osrow= mysql_fetch_array($osre);
								$row[$i] = $row[$i] ." ". $osrow['selitetark'];
							}
						}

						// jos kyseessa on ytunnus, haetaan sen nimi
						if (mysql_field_name($result, $i) == "ytunnus") {
							$query = "	SELECT nimi
										FROM asiakas
										WHERE yhtio in ($yhtio) and ytunnus='$row[$i]' and ytunnus!='' limit 1";
							$osre = mysql_query($query) or pupe_error($query);
							if (mysql_num_rows($osre) == 1) {
								$osrow= mysql_fetch_array($osre);
								$row[$i] = $row[$i] ." ". $osrow['nimi'];
							}
						}

						// jos kyseessa on myyjä, haetaan sen nimi
						if (mysql_field_name($result, $i) == "tuotemyyja" or mysql_field_name($result, $i) == "asiakasmyyja") {
							$query = "	SELECT nimi
										FROM kuka
										WHERE yhtio in ($yhtio) and myyja='$row[$i]' and myyja!='0' limit 1";
							$osre = mysql_query($query) or pupe_error($query);
							if (mysql_num_rows($osre) == 1) {
								$osrow= mysql_fetch_array($osre);
								$row[$i] = $row[$i] ." ". $osrow['nimi'];
							}
						}

						// jos kyseessa on ostaja, haetaan sen nimi
						if (mysql_field_name($result, $i) == "tuoteostaja") {
							$query = "	SELECT nimi
										FROM kuka
										WHERE yhtio in ($yhtio) and myyja='$row[$i]' and myyja!='0' limit 1";
							$osre = mysql_query($query) or pupe_error($query);
							if (mysql_num_rows($osre) == 1) {
								$osrow= mysql_fetch_array($osre);
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
									$osrow= mysql_fetch_array($osre);
									$row[$i] .= "<a href='../tilauskasittely/sarjanumeroseuranta.php?sarjanumero_haku=$osrow[sarjanumero]'>".$osrow['sarjanumero']."</a><br>";
								}
							}
							$row[$i] = substr($row[$i], 0, -4);
						}

						// Jos gruupataan enemmän kuin yksi taso niin tehdään välisumma
						if ($gluku > 1) {

							if ($edluku != $row[0] and $edluku != 'x' and $mukaan != 'tuote' and $piiyhteensa == '') {

								$myyntiind = $kateind = $nettokateind = 0;
								if ($myyntied    <> 0) $myyntiind    = round($myyntinyt/$myyntied,2);
								if ($kateed      <> 0) $kateind      = round($katenyt/$kateed,2);
								if ($nettokateed <> 0) $nettokateind = round($nettokatenyt/$nettokateed,2);

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

								echo "<td class='tumma' colspan='$apu'>$edluku ".t("yhteensä")."</th>";

								if ($ajotapa == 'tilausjaauki') {
									echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myykpllaskuttamattanyt))."</td>";
								}

								echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntikplnyt))."</td>";
								echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntikpled))."</td>";

								if ($ajotapa == 'tilausjaauki') {
									echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntilaskuttamattanyt))."</td>";
								}

								echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntinyt))."</td>";
								echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntied))."</td>";
								echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntiind))."</td>";
								echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$katenyt))."</td>";
								echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$kateed))."</td>";
								echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$kateind))."</td>";

								if ($kateprossat != "") {
									if ($myyntinyt != 0) {
										$kprny = round($katenyt / $myyntinyt * 100, 2);
									}
									else {
										$kprny = 0;
									}
									echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$kprny))."</td>";

									if ($myyntied != 0) {
										$kpred = round($kateed / $myyntied * 100, 2);
									}
									else {
										$kpred = 0;
									}
									echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$kpred))."</td>";
								}

								echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokatenyt))."</td>";
								echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokateed))."</td>";
								echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokateind))."</td></tr>\n";

								if(isset($workbook)) {
									$excelsarake=0;

									$worksheet->write($excelrivi, $excelsarake, $edluku." ".t("yhteensä"), $format_bold);

									for($iii = 0; $iii < $apu; $iii++) {
										$excelsarake++;
									}

									if ($ajotapa == 'tilausjaauki') {
										$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myykpllaskuttamattanyt), $format_bold);
										$excelsarake++;
									}

									$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myyntikplnyt), $format_bold);
									$excelsarake++;
									$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myyntikpled), $format_bold);
									$excelsarake++;

									if ($ajotapa == 'tilausjaauki') {
										$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myyntilaskuttamattanyt), $format_bold);
										$excelsarake++;
									}

									$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myyntinyt), $format_bold);
									$excelsarake++;
									$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myyntied), $format_bold);
									$excelsarake++;
									$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myyntiind), $format_bold);
									$excelsarake++;
									$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$katenyt), $format_bold);
									$excelsarake++;
									$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$kateed), $format_bold);
									$excelsarake++;
									$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$kateind), $format_bold);
									$excelsarake++;

									if ($kateprossat != "") {
										if ($myyntinyt != 0) {
											$kprny = round($katenyt / $myyntinyt * 100, 2);
										}
										else {
											$kprny = 0;
										}
										$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$kprny), $format_bold);
										$excelsarake++;

										if ($myyntied != 0) {
											$kpred = round($kateed / $myyntied * 100, 2);
										}
										else {
											$kpred = 0;
										}
										$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$kpred), $format_bold);
										$excelsarake++;
									}

									$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$nettokatenyt), $format_bold);
									$excelsarake++;
									$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$nettokateed), $format_bold);
									$excelsarake++;
									$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$nettokateind), $format_bold);
									$excelrivi++;
								}

								echo "<tr>";

								$myykpllaskuttamattanyt    	= "0";
								$myyntilaskuttamattanyt    	= "0";
								$myyntikplnyt 				= "0";
								$myyntikpled  				= "0";
								$myyntinyt    				= "0";
								$myyntied     				= "0";
								$katenyt      				= "0";
								$kateed       				= "0";
								$nettokatenyt 				= "0";
								$nettokateed  				= "0";
							}
							$edluku = $row[0];
						}

						// hoidetaan pisteet piluiksi!!
						if (mysql_field_type($result,$i) == 'real' or substr(mysql_field_name($result, $i),0 ,4) == 'kate') {
							echo "<td valign='top' align='right'>".sprintf("%.02f",$row[$i])."</td>";

							if(isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $i, sprintf("%.02f",$row[$i]));
							}
						}
						elseif (mysql_field_name($result, $i) == 'sarjanumero') {
							echo "<td valign='top'>$row[$i]</td>";

							if(isset($workbook)) {
								$worksheet->writeString($excelrivi, $i, strip_tags(str_replace("<br>", "\n", $row[$i])));
							}
						}
						else {
							echo "<td valign='top'>$row[$i]</td>";

							if(isset($workbook)) {
								$worksheet->writeString($excelrivi, $i, $row[$i]);
							}
						}
					}

					echo "</tr>\n";
					$excelrivi++;


					$myykpllaskuttamattanyt    	+= $row['myykpllaskuttamattanyt'];
					$myyntilaskuttamattanyt    	+= $row['myyntilaskuttamattanyt'];
					$myyntikplnyt    			+= $row['myykplnyt'];
					$myyntikpled     			+= $row['myykpled'];
					$myyntinyt       			+= $row['myyntinyt'];
					$myyntied        			+= $row['myyntied'];
					$katenyt         			+= $row['katenyt'];
					$kateed          			+= $row['kateed'];
					$nettokatenyt    			+= $row['nettokatenyt'];
					$nettokateed     			+= $row['nettokateed'];

					$totmyykpllaskuttamattanyt  += $row['myykpllaskuttamattanyt'];
					$totmyyntilaskuttamattanyt  += $row['myyntilaskuttamattanyt'];
					$totmyyntikplnyt 			+= $row['myykplnyt'];
					$totmyyntikpled  			+= $row['myykpled'];
					$totmyyntinyt    			+= $row['myyntinyt'];
					$totmyyntied     			+= $row['myyntied'];
					$totkatenyt      			+= $row['katenyt'];
					$totkateed       			+= $row['kateed'];
					$totnettokatenyt 			+= $row['nettokatenyt'];
					$totnettokateed  			+= $row['nettokateed'];

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

					$myyntiind = $kateind = $nettokateind = 0;
					if ($myyntied    <> 0) $myyntiind    = round($myyntinyt/$myyntied,2);
					if ($kateed      <> 0) $kateind      = round($katenyt/$kateed,2);
					if ($nettokateed <> 0) $nettokateind = round($nettokatenyt/$nettokateed,2);

		  		  	echo "<tr><th colspan='$apu'>$edluku ".t("yhteensä")."</td>";

					if ($ajotapa == 'tilausjaauki') {
						echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myykpllaskuttamattanyt))."</td>";
					}

					echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntikplnyt))."</td>";
		  		  	echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntikpled))."</td>";

					if ($ajotapa == 'tilausjaauki') {
						echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntilaskuttamattanyt))."</td>";
					}

					echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntinyt))."</td>";
		  		  	echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntied))."</td>";
		  		  	echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntiind))."</td>";
		  		  	echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$katenyt))."</td>";
		  		  	echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$kateed))."</td>";
		  		  	echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$kateind))."</td>";

					if ($kateprossat != "") {
						if ($myyntinyt != 0) {
							$kprny = round($katenyt / $myyntinyt * 100, 2);
						}
						else {
							$kprny = 0;
						}
						echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$kprny))."</td>";

						if ($myyntied != 0) {
							$kpred = round($kateed / $myyntied * 100, 2);
						}
						else {
							$kpred = 0;
						}
						echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$kpred))."</td>";
					}

					echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokatenyt))."</td>";
					echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokateed))."</td>";
					echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokateind))."</td></tr>\n";

					if(isset($workbook)) {
						$excelsarake=0;

						$worksheet->write($excelrivi, $excelsarake, $edluku." ".t("yhteensä"), $format_bold);

						for($iii = 0; $iii < $apu; $iii++) {
							$excelsarake++;
						}

						if ($ajotapa == 'tilausjaauki') {
							$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myykpllaskuttamattanyt), $format_bold);
							$excelsarake++;
						}

						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myyntikplnyt), $format_bold);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myyntikpled), $format_bold);
						$excelsarake++;

						if ($ajotapa == 'tilausjaauki') {
							$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myyntilaskuttamattanyt), $format_bold);
							$excelsarake++;
						}

						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myyntinyt), $format_bold);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myyntied), $format_bold);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$myyntiind), $format_bold);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$katenyt), $format_bold);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$kateed), $format_bold);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$kateind), $format_bold);
						$excelsarake++;

						if ($kateprossat != "") {
							if ($myyntinyt != 0) {
								$kprny = round($katenyt / $myyntinyt * 100, 2);
							}
							else {
								$kprny = 0;
							}
							$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$kprny), $format_bold);
							$excelsarake++;

							if ($myyntied != 0) {
								$kpred = round($kateed / $myyntied * 100, 2);
							}
							else {
								$kpred = 0;
							}
							$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$kpred), $format_bold);
							$excelsarake++;
						}

						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$nettokatenyt), $format_bold);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$nettokateed), $format_bold);
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$nettokateind), $format_bold);
						$excelrivi++;
					}
				}

				$myyntiind = $kateind = $nettokateind = 0;
				if ($totmyyntied    <> 0) $myyntiind    = round($totmyyntinyt/$totmyyntied,2);
				if ($totkateed      <> 0) $kateind      = round($totkatenyt/$totkateed,2);
				if ($totnettokateed <> 0) $nettokateind = round($totnettokatenyt/$totnettokateed,2);

				echo "<tr><th colspan='$apu'>".t("Kaikki yhteensä")."</td>";

				if ($ajotapa == 'tilausjaauki') {
					echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$totmyykpllaskuttamattanyt))."</td>";
				}

				echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$totmyyntikplnyt))."</td>";
				echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$totmyyntikpled))."</td>";

				if ($ajotapa == 'tilausjaauki') {
					echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$totmyyntilaskuttamattanyt))."</td>";
				}

				echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$totmyyntinyt))."</td>";
				echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$totmyyntied))."</td>";
				echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$myyntiind))."</td>";
				echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$totkatenyt))."</td>";
				echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$totkateed))."</td>";
				echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$kateind))."</td>";

				if ($kateprossat != "") {
					if ($totmyyntinyt != 0) {
						$kprny = round($totkatenyt / $totmyyntinyt * 100, 2);
					}
					else {
						$kprny = 0;
					}
					echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$kprny))."</td>";

					if ($totmyyntied != 0) {
						$kpred = round($totkateed / $totmyyntied * 100, 2);
					}
					else {
						$kpred = 0;
					}
					echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$kpred))."</td>";
				}

				echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$totnettokatenyt))."</td>";
				echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$totnettokateed))."</td>";
				echo "<td class='tumma' align='right'>".str_replace(".", ",", sprintf("%.02f",$nettokateind))."</td></tr>\n";

				echo "</table>";

				if(isset($workbook)) {
					$excelsarake=0;

					$worksheet->write($excelrivi, $excelsarake, t("Kaikki yhteensä"), $format_bold);

					for($iii = 0; $iii < $apu; $iii++) {
						$excelsarake++;
					}

					if ($ajotapa == 'tilausjaauki') {
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$totmyykpllaskuttamattanyt), $format_bold);
						$excelsarake++;
					}

					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$totmyyntikplnyt), $format_bold);
					$excelsarake++;
					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$totmyyntikpled), $format_bold);
					$excelsarake++;

					if ($ajotapa == 'tilausjaauki') {
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$totmyyntilaskuttamattanyt), $format_bold);
						$excelsarake++;
					}

					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$totmyyntinyt), $format_bold);
					$excelsarake++;
					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$totmyyntied), $format_bold);
					$excelsarake++;
					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$totmyyntiind), $format_bold);
					$excelsarake++;
					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$totkatenyt), $format_bold);
					$excelsarake++;
					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$totkateed), $format_bold);
					$excelsarake++;
					$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$totkateind), $format_bold);
					$excelsarake++;

					if ($kateprossat != "") {
						if ($totmyyntinyt != 0) {
							$kprny = round($totkatenyt / $totmyyntinyt * 100, 2);
						}
						else {
							$kprny = 0;
						}
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$kprny), $format_bold);
						$excelsarake++;

						if ($totmyyntied != 0) {
							$kpred = round($totkateed / $totmyyntied * 100, 2);
						}
						else {
							$kpred = 0;
						}
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$kpred), $format_bold);
						$excelsarake++;
					}

					$worksheet->write($excelrivi, $excelsarake, sprintf("%.02f",$totnettokatenyt), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, sprintf("%.02f",$totnettokateed), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, sprintf("%.02f",$totnettokateind), $format_bold);
					$excelrivi++;

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
					echo "<br>";
					echo "<a href='../crm/tarrat.php?tee=&tarra_aineisto=$tarra_aineisto'>".t("Tulosta tarrat")."</a>";
					echo "<br>";
					echo "<br>";
				}
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
			echo "<option value='tilaus' 		$chk2>".t("Tilauksista")."</option>";
			echo "<option value='tilausjaauki'	$chk3>".t("Tilauksista, avoimet huomioiden")."</option>";
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

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Syötä asiakasosasto")."</th>";
			echo "<td><input type='text' name='asiakasosasto' size='10'></td>";

			$query = "	SELECT distinct avainsana.selite, ".avain('select')."
						FROM avainsana
						".avain('join','ASOSASTO_')."
						WHERE avainsana.yhtio in ($yhtio) and avainsana.laji='ASIAKASOSASTO'
						ORDER BY avainsana.selite+0";
			$result = mysql_query($query) or pupe_error($query);

			echo "<td><select name='asiakasosasto2'>";
			echo "<option value=''>".t("Asiakasosasto")."</option>";
			echo "<option value='kaikki' $asiakasosastosel>".t("Kaikki")."</option>";

			while ($row = mysql_fetch_array($result)) {
				if ($asiakasosasto == $row["selite"]) $sel = "selected";
				else $sel = "";
				echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
			}

			echo "</select></td>";
			echo "<th>".t("ja/tai asiakasryhmä")."</th>";
			echo "<td><input type='text' name='asiakasryhma' size='10'></td>";

			$query = "	SELECT distinct avainsana.selite, ".avain('select')."
						FROM avainsana
						".avain('join','ASRYHMA_')."
						WHERE avainsana.yhtio in ($yhtio) and avainsana.laji='ASIAKASRYHMA'
						ORDER BY avainsana.selite+0";
			$result = mysql_query($query) or pupe_error($query);

			echo "<td><select name='asiakasryhma2'>";
			echo "<option value=''>".t("Asiakasryhmä")."</option>";
			echo "<option value='kaikki' $asiakasryhmasel>".t("Kaikki")."</option>";

			while ($row = mysql_fetch_array($result)) {
				if ($asiakasryhma == $row["selite"]) $sel = "selected";
				else $sel = "";
				echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
			}

			echo "</select></td>";
			echo "</tr></table><br>\n";

			echo "<table><tr valign='top'><td><table><tr><td class='back'>";

			// näytetään soveltuvat osastot
			$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','OSASTO_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' order by avainsana.selite+0";
			$res2  = mysql_query($query) or die($query);

			if (mysql_num_rows($res2) > 11) {
				echo "<div style='height:265;overflow:auto;'>";
			}

			echo "<table>";
			echo "<tr><th colspan='2'>".t("Valitse tuoteosasto(t)").":</th></tr>";
			echo "<tr><td><input type='checkbox' name='mul_osa' onclick='toggleAll(this);'></td><td>".t("Ruksaa kaikki")."</td></tr>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_osasto!="") {
					if (in_array($rivi['selite'],$mul_osasto)) {
						$mul_check = 'CHECKED';
					}
				}

				echo "<tr><td><input type='checkbox' name='mul_osasto[]' value='$rivi[selite]' $mul_check></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
			}

			echo "</table>";

			if (mysql_num_rows($res2) > 11) {
				echo "</div>";
			}

			echo "</table>";
			echo "</td>";

			echo "<td><table><tr><td valign='top' class='back'>";

			// näytetään soveltuvat tryt
			$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','TRY_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY' order by avainsana.selite+0";
			$res2  = mysql_query($query) or die($query);

			if (mysql_num_rows($res2) > 11) {
				echo "<div style='height:265;overflow:auto;'>";
			}

			echo "<table>";
			echo "<tr><th colspan='2'>".t("Valitse tuoterymät(t)").":</th></tr>";
			echo "<tr><td><input type='checkbox' name='mul_try' onclick='toggleAll(this);'></td><td>".t("Ruksaa kaikki")."</td></tr>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_try!="") {
					if (in_array($rivi['selite'],$mul_try)) {
						$mul_check = 'CHECKED';
					}
				}

				echo "<tr><td><input type='checkbox' name='mul_try[]' value='$rivi[selite]' $mul_check></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
			}

			echo "</table>";

			if (mysql_num_rows($res2) > 11) {
				echo "</div>";
			}

			echo "</table>";
			echo "</td>";

			echo "<td colspan='3'><table><tr><td valign='top' class='back'>";
			// näytetään sallityt piirit
			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio='$kukarow[yhtio]' and
						laji='piiri'
						$asiakasrajaus_avainsana
						order by jarjestys";
			$res2  = mysql_query($query) or die($query);

			if (mysql_num_rows($res2) > 11) {
				echo "<div style='height:265;overflow:auto;'>";
			}

			echo "<table>";
			echo "<tr><th colspan='2'>".t("Valitse asiakaspiiri(t)").":</th></tr>";
			echo "<tr><td><input type='checkbox' name='mul_pii' onclick='toggleAll(this);'></td><td>".t("Ruksaa kaikki")."</td></tr>";

			while ($rivi = mysql_fetch_array($res2)) {
				$mul_check = '';
				if ($mul_piiri != "") {
					if (in_array($rivi['selite'], $mul_piiri)) {
						$mul_check = 'CHECKED';
					}
				}

				echo "<tr><td><input type='checkbox' name='mul_piiri[]' value='$rivi[selite]' $mul_check></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
			}

			echo "</table>";

			if (mysql_num_rows($res2) > 11) {
				echo "</div>";
			}

			echo "</table>";

			echo "</td>";

			echo "</tr>\n";
			echo "</table><br>";

			// tähän loppuu "perusnäkymä"...

			echo "<table>
			<tr>
			<th>".t("Vain asiakas ytunnus")."</th>
			<td><input type='text' name='asiakas' value='$asiakas'></td>
			</tr>";

			echo "<tr>
			<th>".t("Vain toimittaja ytunnus")."</th>
			<td><input type='text' name='toimittaja' value='$toimittaja'></td>
			</tr>";

			echo "</table><br>";

			// lisärajaukset näkymä..
			if ($ruksit[1]  != '') 		$ruk1chk  = "CHECKED";
			if ($ruksit[2]  != '') 		$ruk2chk  = "CHECKED";
			if ($ruksit[3]  != '') 		$ruk3chk  = "CHECKED";
			if ($ruksit[4]  != '') 		$ruk4chk  = "CHECKED";
			if ($ruksit[5]  != '') 		$ruk5chk  = "CHECKED";
			if ($ruksit[6]  != '') 		$ruk6chk  = "CHECKED";
			if ($ruksit[7]  != '') 		$ruk7chk  = "CHECKED";
			if ($ruksit[8]  != '') 		$ruk8chk  = "CHECKED";
			if ($ruksit[9]  != '') 		$ruk9chk  = "CHECKED";
			if ($ruksit[10] != '') 		$ruk10chk = "CHECKED";
			if ($nimitykset != '')   	$nimchk   = "CHECKED";
			if ($kateprossat != '')  	$katchk   = "CHECKED";
			if ($osoitetarrat != '') 	$tarchk   = "CHECKED";
			if ($piiyhteensa != '')  	$piychk   = "CHECKED";
			if ($sarjanumerot != '')  	$sarjachk = "CHECKED";

			echo "<table>
				<tr>
				<th>".t("Lisärajaus")."</th>
				<th>".t("Prio")."</th>
				<th> x</th>
				</tr>
				<tr>
				<th>".t("Listaa asiakkaittain")."</th>
				<td><input type='text' name='jarjestys[1]' size='2' value='$jarjestys[1]'></td>
				<td><input type='checkbox' name='ruksit[1]' value='ytunnus' $ruk1chk></td>
				<td class='back'>".t("(Rajaa ylhäältä mahdollisimman pieni ryhmä, muuten lista on todella pitkä)")."</td>
				</tr>
				<tr>
				<th>".t("Listaa tuotteittain")."</th>
				<td><input type='text' name='jarjestys[2]' size='2' value='$jarjestys[2]'></td>
				<td><input type='checkbox' name='ruksit[2]' value='tuote' $ruk2chk></td>
				<td class='back'>".t("(Rajaa ylhäältä mahdollisimman pieni ryhmä, muuten lista on todella pitkä)")."</td>
				</tr>
				<tr>
				<th>".t("Listaa piireittäin")."</th>
				<td><input type='text' name='jarjestys[3]' size='2' value='$jarjestys[3]'></td>
				<td><input type='checkbox' name='ruksit[3]' value='piiri' $ruk3chk></td>
				</tr>
				<tr>
				<th>".t("Listaa tuotemyyjittäin")."</th>
				<td><input type='text' name='jarjestys[4]' size='2' value='$jarjestys[4]'></td>
				<td><input type='checkbox' name='ruksit[4]' value='tuotemyyja' $ruk4chk></td>
				</tr>
				<tr>
				<th>".t("Listaa asiakasmyyjittäin")."</th>
				<td><input type='text' name='jarjestys[5]' size='2' value='$jarjestys[5]'></td>
				<td><input type='checkbox' name='ruksit[5]' value='asiakasmyyja' $ruk5chk></td>
				</tr>
				<tr>
				<th>".t("Listaa tuoteostajittain")."</th>
				<td><input type='text' name='jarjestys[6]' size='2' value='$jarjestys[6]'></td>
				<td><input type='checkbox' name='ruksit[6]' value='tuoteostaja' $ruk6chk></td>
				</tr>
				<tr>
				<th>".t("Listaa maittain")."</th>
				<td><input type='text' name='jarjestys[7]' size='2' value='$jarjestys[7]'></td>
				<td><input type='checkbox' name='ruksit[7]' value='maa' $ruk7chk></td>
				</tr>
				<tr>
				<th>".t("Listaa merkeittäin")."</th>
				<td><input type='text' name='jarjestys[8]' size='2' value='$jarjestys[8]'></td>
				<td><input type='checkbox' name='ruksit[8]' value='merkki' $ruk8chk></td>
				</tr>
				<tr>
				<th>".t("Listaa toimittajittain")."</th>
				<td><input type='text' name='jarjestys[9]' size='2' value='$jarjestys[9]'></td>
				<td><input type='checkbox' name='ruksit[9]' value='toimittaja' $ruk9chk></td>
				</tr>
				<tr>
				<th>".t("Listaa tilaustyypeittäin")."</th>
				<td><input type='text' name='jarjestys[10]' size='2' value='$jarjestys[10]'></td>
				<td><input type='checkbox' name='ruksit[10]' value='tilaustyyppi' $ruk10chk></td>
				<td class='back'>".t("(Toimii vain jos ajat raporttia tilauksista)")."</td>
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
				<th>".t("Tulosta osoitetarrat")."</th>
				<td><input type='checkbox' name='osoitetarrat' $tarchk></td>
				<td></td>
				<td class='back'>".t("(Toimii vain jos listaat asiakkaittain)")."</td>
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
			echo "</table>";

			echo "<br>";
			echo "<input type='submit' value='".t("Aja raportti")."'>";
			echo "</form>";
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta.php") !== FALSE) {
			require ("../inc/footer.inc");
		}
	}
?>
