<?php

	if (isset($_REQUEST["komento"]) and in_array("PDF_RUUDULLE", $_REQUEST["komento"])) {
		$_REQUEST["tee"] = $_POST["tee"] = $_GET["tee"] = "NAYTATILAUS";
	}

	if ((isset($_REQUEST["tee"]) and $_REQUEST["tee"] == 'NAYTATILAUS') or
		(isset($_POST["tee"]) and $_POST["tee"] == 'NAYTATILAUS') or
		(isset($_GET["tee"]) and $_GET["tee"] == 'NAYTATILAUS')) $nayta_pdf = 1; //Generoidaan .pdf-file

	if (@include("../inc/parametrit.inc"));
	elseif (@include("parametrit.inc"));
	else exit;

	if (!isset($logistiikka_yhtio)) 	$logistiikka_yhtio = "";
	if (!isset($logistiikka_yhtiolisa)) $logistiikka_yhtiolisa = "";
	if (!isset($asiakasid)) 			$asiakasid = "";
	if (!isset($toimittajaid)) 			$toimittajaid = "";
	if (!isset($tee)) 					$tee = "";
	if (!isset($laskunro)) 				$laskunro = "";
	if (!isset($ytunnus)) 				$ytunnus = "";
	if (!isset($lopetus)) 				$lopetus = "";
	if (!isset($toim) or $toim == "") 	$toim = "LASKU";
	if (!isset($kieli) or $kieli == "") $kieli = $yhtiorow["kieli"];
	if (!isset($tila)) 					$tila = "";
	if (!isset($tunnus)) 				$tunnus = "";
	if (!isset($laskunroloppu)) 		$laskunroloppu = "";

	if ($toim == "OSOITELAPPU") {
		//osoitelapuille on v�h�n eri p�iv�m��r�vaatimukset kuin muilla
		if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	}
	elseif ($toim == "TARJOUS" or $toim == "TARJOUS!!!VL" or $toim == "TARJOUS!!!BR" or $toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL" or $toim == "MYYNTISOPIMUS!!!BR" or $toim == "OSAMAKSUSOPIMUS" or $toim == "LUOVUTUSTODISTUS" or $toim == "VAKUUTUSHAKEMUS" or $toim == "REKISTERIILMOITUS") {
		//N�iss� kaupoissa voi kest�� v�h�n kauemmin
		if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
		if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
		if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
	}
	else {
		if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	}

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '' and ($toim == "LAHETE" or $toim == "OSOITELAPPU" or $toim == "KERAYSLISTA")) {
		$logistiikka_yhtio = $konsernivarasto_yhtiot;
		$logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

		if ($lasku_yhtio != '') {
			$kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);
			$yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
		}
	}
	else {
		$logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
	}

	if ($tee == 'NAYTAHTML') {
		if ($logistiikka_yhtio != '' and $konsernivarasto_yhtiot != '') {
			echo "<font class='head'>",t("Yhti�n")," $yhtiorow[nimi] ",t("tilaus")," $tunnus:</font><hr>";
		}
		else {
			echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
		}

		require ("raportit/naytatilaus.inc");

		echo "<br><br>";
		$tee = "ETSILASKU";
	}

	if ($toim == 'OSTO' or $toim == 'HAAMU' or $toim == 'PURKU' or $toim == 'TARIFFI') {
		$query_ale_lisa = generoi_alekentta('O');
	}
	else {
		$query_ale_lisa = generoi_alekentta('M');
	}

	if ($toim == "OSTO") {
		$fuse = t("Ostotilaus");
	}
	if ($toim == "HAAMU") {
		$fuse = t("Ostotilaus haamu");
	}
	if ($toim == "PURKU") {
		$fuse = t("Purkulista");
	}
	if ($toim == "VASTAANOTETUT") {
		$fuse = t("Vastaanotetut");
	}
	if ($toim == "TARIFFI") {
		$fuse = t("Tariffilista");
	}
	if ($toim == "SIIRTOLISTA") {
		$fuse = t("Siirtolista");
	}
	if ($toim == "VALMISTUS") {
		$fuse = t("Valmistus");
	}
	if ($toim == "LASKU") {
		$fuse = t("Lasku");
	}
	if ($toim == "TUOTETARRA") {
		$fuse = t("Tuotetarra");
	}
	if ($toim == "TILAUSTUOTETARRA") {
		$fuse = t("Tilauksen tuotetarrat");
	}
	if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
		$fuse = t("Ty�m��r�ys");
	}
	if ($toim == "SAD") {
		$fuse = t("Sad-lomake");
	}
	if ($toim == "LAHETE") {
		$fuse = t("L�hete");
	}
	if ($toim == "DGD") {
		$fuse = "DGD - Multimodal Dangerous Goods Form";
	}
	if ($toim == "PAKKALISTA") {
		//	T�m� on about yhdistetty vienti-erittely ja l�hete
		$fuse = t("Pakkalista");
	}
	if ($toim == "KERAYSLISTA") {
		$fuse = t("Ker�yslista");
	}
	if ($toim == "OSOITELAPPU") {
		$fuse = t("Osoitelappu");
	}
	if ($toim == "VIENTIERITTELY") {
		$fuse = t("Vientierittely");
	}
	if ($toim == "PROFORMA") {
		$fuse = t("Proforma");
	}
	if ($toim == "TILAUSVAHVISTUS") {
		$fuse = t("Tilausvahvistus");
	}
	if ($toim == "YLLAPITOSOPIMUS") {
		$fuse = t("Yll�pitosopimus");
	}
	if ($toim == "TARJOUS" or $toim == "TARJOUS!!!VL" or $toim == "TARJOUS!!!BR") {
		$fuse = t("Tarjous");
	}
	if ($toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL" or $toim == "MYYNTISOPIMUS!!!BR") {
		$fuse = t("Myyntisopimus");
	}
	if ($toim == "OSAMAKSUSOPIMUS") {
		$fuse = t("Osamaksusopimus");
	}
	if ($toim == "LUOVUTUSTODISTUS") {
		$fuse = t("Luovutustodistus");
	}
	if ($toim == "VAKUUTUSHAKEMUS") {
		$fuse = t("Vakuutushakemus");
	}
	if ($toim == "REKISTERIILMOITUS") {
		$fuse = t("Rekister�inti-ilmoitus");
	}
	if ($toim == "REKLAMAATIO") {
		$fuse = t("Reklamaatio/Purkulista");
	}

	if (isset($muutparametrit) and $muutparametrit != '') {
		$muut = explode('/',$muutparametrit);

		$vva 		= $muut[0];
		$kka 		= $muut[1];
		$ppa 		= $muut[2];
		$vvl 		= $muut[3];
		$kkl		= $muut[4];
		$ppl 		= $muut[5];
	}

	if ($tee != 'NAYTATILAUS') {
		echo "<font class='head'>".sprintf(t("Tulosta %s kopioita"), $fuse).":</font><hr><br>";
	}

	if ($laskunro > 0 and $laskunroloppu > 0 and $laskunro < $laskunroloppu) {
		$tee = "TULOSTA";

		$tulostukseen = array();

		for($las = $laskunro; $las<=$laskunroloppu; $las++) {
			//hateaan laskun kaikki tiedot
			$query = "  SELECT tunnus
						FROM lasku
						WHERE tila		= 'U'
						and alatila		= 'X'
						and laskunro	= '$las'
						and yhtio 		= '$kukarow[yhtio]'";
			$rrrresult = pupe_query($query);

			if ($laskurow = mysql_fetch_assoc($rrrresult)) {
				$tulostukseen[] = $laskurow["tunnus"];
			}
		}
		$laskunro		= "";
		$laskunroloppu	= "";
	}

	// Extranettaajat voivat ottaa kopioita omista laskuistaan ja l�hetteist��n
	if ($kukarow["extranet"] != "") {
		if ($kukarow["oletus_asiakas"] > 0 and ($toim == "LAHETE" or $toim == "LASKU" or $toim == "TILAUSVAHVISTUS")) {
			$query  = "	SELECT *
						FROM asiakas
						WHERE yhtio	= '$kukarow[yhtio]'
						and tunnus  = '$kukarow[oletus_asiakas]'";
			$vieres = pupe_query($query);

			if (mysql_num_rows($vieres) == 1) {
				$asiakasrow = mysql_fetch_assoc($vieres);

				$asiakasid 	= $asiakasrow["tunnus"];
				$ytunnus	= $asiakasrow["ytunnus"];
			}
			else {
				exit;
			}
		}
		else {
			// Extranet kaatuu t�h�n
			exit;
		}
	}
	elseif (($tee == "" or $tee == 'ETSILASKU') and $toim != 'SIIRTOLISTA'){

		$muutparametrit = $vva."/".$kka."/".$ppa."/".$vvl."/".$kkl."/".$ppl;

		if ($ytunnus != '' or (int) $asiakasid > 0 or (int) $toimittajaid > 0) {
			if ($toim == 'OSTO' or $toim == 'PURKU' or $toim == 'TARIFFI' or $toim == 'TUOTETARRA' or $toim == 'VASTAANOTETUT' or $toim == 'HAAMU') {
				require ("../inc/kevyt_toimittajahaku.inc");
			}
			else {
				if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '' and ($toim == "LAHETE" or $toim == "OSOITELAPPU" or $toim == "KERAYSLISTA")) {
					$konserni = $yhtiorow['konserni'];
				}
				require ("inc/asiakashaku.inc");
			}

			if ($ytunnus == "") {
				$tee = "";
			}
		}
	}

	if (($tee == "" or $tee == 'ETSILASKU') and $toim == 'SIIRTOLISTA'){
		if ($lahettava_varasto != '' or $vastaanottava_varasto != '') {
			$tee = "ETSILASKU";
		}
		else {
			$tee = "";
		}

		if ($otunnus > 0) {
			$tee = 'ETSILASKU';
		}
	}

	if ($tee != 'NAYTATILAUS') {

		js_popup(-100);

		//sy�tet��n tilausnumero
		echo "<form method='post' action='$PHP_SELF' autocomplete='off' name='hakuformi'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tee' value='ETSILASKU'>";

		echo "<table>";

		if (trim($toim) == "SIIRTOLISTA") {

			echo "<tr><th>".t("L�hett�v� varasto")."</th><td colspan='3'>";

			$query = "	SELECT *
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]' order by nimitys";
			$vtresult = pupe_query($query);

			echo "<select name='lahettava_varasto'>";
			echo "<option value=''>".t("Valitse varasto")."</option>";

			while ($vrow = mysql_fetch_assoc($vtresult)) {
				if ($lahettava_varasto == $vrow["tunnus"]) {
					$sel = "SELECTED";
				}
				else {
					$sel = "";
				}

				echo "<option value='$vrow[tunnus]' $sel>$vrow[maa] $vrow[nimitys]</option>";
			}
			echo "</select>";

			echo "</td></tr>";

			echo "<tr><th>".t("Vastaanottava varasto")."</th><td colspan='3'>";

			$query = "	SELECT *
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]'
						order by nimitys";
			$vtresult = pupe_query($query);

			echo "<select name='vastaanottava_varasto'>";
			echo "<option value=''>".t("Valitse varasto")."</option>";

			while ($vrow = mysql_fetch_assoc($vtresult)) {
				if ($vastaanottava_varasto == $vrow["tunnus"]) {
					$sel = "SELECTED";
				}
				else {
					$sel = "";
				}
				echo "<option value='$vrow[tunnus]' $sel>$vrow[maa] $vrow[nimitys]</option>";
			}
			echo "</select>";

			echo "</td></tr>";

			echo "<tr><th>".t("Tilausnumero")."</th><td colspan='3'><input type='text' size='15' name='otunnus'></td></tr>";
		}
		else {
			if (((int) $asiakasid > 0 or (int) $toimittajaid > 0)) {
				if ($toim == "OSTO" or $toim == "PURKU" or $toim == "TARIFFI" or $toim == "TUOTETARRA" or $toim == 'VASTAANOTETUT' or $toim == "HAAMU") {
					echo "<th>".t("Toimittajan nimi")."</th><td colspan='3'>$toimittajarow[nimi]<input type='hidden' name='toimittajaid' value='$toimittajaid'></td>";

					if ($kukarow["extranet"] == "") {
						echo "<td><a href='$PHP_SELF?lopetus=$lopetus&toim=$toim&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka'>".t("Vaihda toimittaja")."</a></td>";
					}

					echo "</tr>";
				}
				else {
					echo "<th>".t("Asiakas")."</th><td colspan='3'>$asiakasrow[nimi]<input type='hidden' name='asiakasid' value='$asiakasid'></td>";

					if ($kukarow["extranet"] == "") {
						echo "<td><a href='$PHP_SELF?lopetus=$lopetus&toim=$toim&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka'>".t("Vaihda asiakas")."</a></td>";
					}

					echo "</tr>";
				}
			}
			else {
				if ($toim == "OSTO" or $toim == "PURKU" or $toim == "TARIFFI" or $toim == "TUOTETARRA" or $toim == 'VASTAANOTETUT' or $toim == "HAAMU") {
					echo "<th>".t("Toimittajan nimi")."</th><td colspan='3'><input type='text' name='ytunnus' value='$ytunnus' size='15'></td></tr>";
				}
				else {
					echo "<th>".t("Asiakas")."</th><td colspan='3'><input type='text' name='ytunnus' value='$ytunnus' size='15'> ",asiakashakuohje(),"</td></tr>";
				}

				$formi  = 'hakuformi';
				$kentta = 'ytunnus';
			}

			echo "<tr><th>".t("Tilausnumero")."</th><td colspan='3'><input type='text' size='15' name='otunnus'></td></tr>";
			echo "<tr>";

			if ($toim == "PURKU" or $toim == "TARIFFI") {
				echo "<th>".t("Keikkanumero")."</th>";
			}
			else {
				echo "<th>".t("Laskunumero")."</th>";
			}

			echo "<td colspan='3'><input type='text' size='15' name='laskunro'></td>";

			if ($kukarow["extranet"] == "") {
				echo "<td colspan='3'><input type='text' size='15' name='laskunroloppu'></td>";
			}

			echo "</tr>";
		}

		echo "<tr><th>".t("Alkup�iv�m��r� (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr><tr><th>".t("loppup�iv�m��r� (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>
				<td class='back'><input type='submit' value='".t("Etsi")."'></td>";
		echo "</table>";
	    echo "</form><br>";
	}

	if ($tee == "ETSILASKU") {

		// ekotetaan javascripti� jotta saadaan pdf:�t uuteen ikkunaan
		js_openFormInNewWindow();

		$where1 		= "";
		$where2 		= "";
		$where3 		= "";

		if (isset($asiakasrow)) {
			$wherenimi = "	and lasku.nimi			= '$asiakasrow[nimi]'
						 	and lasku.nimitark		= '$asiakasrow[nimitark]'
						 	and lasku.osoite		= '$asiakasrow[osoite]'
						 	and lasku.postino		= '$asiakasrow[postino]'
						 	and lasku.postitp		= '$asiakasrow[postitp]'
						 	and lasku.toim_nimi		= '$asiakasrow[toim_nimi]'
						 	and lasku.toim_nimitark	= '$asiakasrow[toim_nimitark]'
						 	and lasku.toim_osoite	= '$asiakasrow[toim_osoite]'
						 	and lasku.toim_postino	= '$asiakasrow[toim_postino]'
					 	 	and lasku.toim_postitp	= '$asiakasrow[toim_postitp]' ";
		}

		if ($toim == "REKLAMAATIO" and $yhtiorow['reklamaation_kasittely'] == 'U') {
			$where1 .= " lasku.tila in ('C','L') ";

			$where2 .= " and lasku.alatila in('C','D','X')";

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'
						 and lasku.ytunnus = '$ytunnus'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "REKLAMAATIO" and $yhtiorow['reklamaation_kasittely'] == '') {
			$where1 .= " lasku.tila in ('L','N','C') and lasku.tilaustyyppi = 'R' ";

			$where2 .= " and lasku.alatila in ('','A','B','C','J','D') ";

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "OSTO") {
			//ostotilaus kyseess�, ainoa paperi joka voidaan tulostaa on itse tilaus
			$where1 .= " lasku.tila = 'O' ";

			if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "HAAMU") {
			//ostotilaus kyseess�, ainoa paperi joka voidaan tulostaa on itse tilaus
			$where1 .= " lasku.tila IN ('D', 'O') and lasku.tilaustyyppi = 'O' ";

			if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "PURKU") {
			//ostolasku jolle on kohdistettu rivej�. T�lle oliolle voidaan tulostaa purkulista
			$where1 .= " lasku.tila = 'K' ";

			if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "VASTAANOTETUT") {

			$where1 = " lasku.tila = 'G' and alatila in ('V','X') ";

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "TARIFFI") {
			//ostolasku jolle on kohdistettu rivej�. T�lle oliolle voidaan tulostaa tariffilista
			$where1 .= " lasku.tila = 'K' and lasku.kohdistettu in ('K','X') ";

			if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "TUOTETARRA") {
			//Ostolasku, tuotetarrat. T�lle oliolle voidaan tulostaa tuotetarroja
			$where1 .= " lasku.tila = 'K' and lasku.kohdistettu in ('K','X') ";

			if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "TILAUSTUOTETARRA") {
			//Ostolasku, tuotetarrat. T�lle oliolle voidaan tulostaa tuotetarroja
			$where1 .= " lasku.tila in ('L','V','W','N')";

			if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "SIIRTOLISTA") {
			//ostolasku jolle on kohdistettu rivej�. T�lle oliolle voidaan tulostaa siirtolista
			$where1 .= " lasku.tila = 'G' ";

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
					     and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if ($lahettava_varasto != '') {
				$where2 .= " and lasku.varasto = '$lahettava_varasto'";
			}
			if ($vastaanottava_varasto != '') {
				$where2 .= " and lasku.clearing = '$vastaanottava_varasto'";
			}

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "VALMISTUS") {
			//valmistuslista
			$where1 .= " lasku.tila = 'V' ";

			if (strlen($ytunnus) > 0 and $ytunnus{0} == '�') {
				$where2 .= $wherenimi;
			}
			elseif ($asiakasid > 0) {
				$where2 .= " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "LASKU") {
			//myyntilasku. T�lle oliolle voidaan tulostaa laskun kopio
			$where1 .= " lasku.tila = 'U' ";

			if (strlen($ytunnus) > 0 and $ytunnus{0} == '�') {
				$where2 .= $wherenimi;
			}
			elseif ($asiakasid > 0) {
				$where2 .= " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where3 .= " and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
						 and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59' ";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_tapvm) ";
		}
		if ($toim == "SAD") {
			//myyntilasku. T�lle oliolle voidaan tulostaa laskun kopio
			$where1 .= " lasku.tila = 'U' and lasku.vienti = 'K' ";

			if (strlen($ytunnus) > 0 and $ytunnus{0} == '�') {
				$where2 .= $wherenimi;
			}
			elseif ($asiakasid > 0) {
				$where2 .= " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where3 .= " and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
						 and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_tapvm) ";
		}
		if ($toim == "LAHETE" or $toim == "PAKKALISTA" or $toim == "DGD") {
			//myyntitilaus. Tulostetaan l�hete.
			$where1 .= " lasku.tila in ('L','N','V','G') ";

			if (strlen($ytunnus) > 0 and $ytunnus{0} == '�') {
				$where2 .= $wherenimi;
			}
			elseif ($asiakasid > 0) {
				$where2 .= " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "KERAYSLISTA") {

			//myyntitilaus. Tulostetaan l�hete.
			$where1 .= " lasku.tila in ('L','N','V') ";

			if (strlen($ytunnus) > 0 and $ytunnus{0} == '�') {
				$where2 .= $wherenimi;
			}
			elseif ($asiakasid > 0) {
				$where2 .= " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "OSOITELAPPU") {
			//myyntitilaus. Tulostetaan osoitelappuja.
			$where1 .= " lasku.tila in ('L','G') ";

			if (strlen($ytunnus) > 0 and $ytunnus{0} == '�') {
				$where2 .= $wherenimi;
			}
			elseif ($asiakasid > 0) {
				$where2 .= " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "VIENTIERITTELY") {
			//myyntitilaus. Tulostetaan vientieruttely.
			$where1 .= " lasku.tila = 'U' and lasku.vienti != '' ";

			if (strlen($ytunnus) > 0 and $ytunnus{0} == '�') {
				$where2 .= $wherenimi;
			}
			elseif ($asiakasid > 0) {
				$where2 .= " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where3 .= " and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
						 and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_tapvm) ";
		}
		if ($toim == "PROFORMA") {
			//myyntitilaus. Tulostetaan proforma.
			$where1 .= " lasku.tila in ('L','N','V','E')";

			if (strlen($ytunnus) > 0 and $ytunnus{0} == '�') {
				$where2 .= $wherenimi;
			}
			elseif ($asiakasid > 0) {
				$where2 .= " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "TILAUSVAHVISTUS") {
			//myyntitilaus.
			$where1 .= " lasku.tila in ('E','N','L','R','A','V')";

			if (strlen($ytunnus) > 0 and $ytunnus{0} == '�') {
				$where2 .= $wherenimi;
			}
			elseif ($asiakasid > 0) {
				$where2 .= " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "YLLAPITOSOPIMUS") {
			//myyntitilaus.
			$where1 .= " lasku.tila in ('0')";

			if (strlen($ytunnus) > 0 and $ytunnus{0} == '�') {
				$where2 .= $wherenimi;
			}
			elseif ($asiakasid > 0) {
				$where2 .= " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "TARJOUS" or $toim == "TARJOUS!!!VL" or $toim == "TARJOUS!!!BR" or $toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL" or $toim == "MYYNTISOPIMUS!!!BR" or $toim == "OSAMAKSUSOPIMUS" or $toim == "LUOVUTUSTODISTUS" or $toim == "VAKUUTUSHAKEMUS" or $toim == "REKISTERIILMOITUS") {
			// Tulostellaan venemyyntiin liittyvi� osia
			$where1 .= " lasku.tila in ('L','T','N','A') ";

			if (strlen($ytunnus) > 0 and $ytunnus{0} == '�') {
				$where2 .= $wherenimi;
			}
			elseif ($asiakasid > 0) {
				$where2 .= " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}
		if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
			/// Ty�m��r�ys
			$where1 .= " lasku.tila in ('L','A','N','S','T')";

			if (strlen($ytunnus) > 0 and $ytunnus{0} == '�') {
				$where2 .= $wherenimi;
			}
			elseif ($asiakasid > 0) {
				$where2 .= " and lasku.liitostunnus  = '$asiakasid'";
			}

			$where2 .= " and lasku.tilaustyyppi in ('A')";

			$where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
						 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (yhtio_tila_luontiaika) ";
		}

		if ($laskunro > 0) {
			$where2 .= " and lasku.laskunro = '$laskunro' ";

			$where3 = "";

			if (!isset($jarj)) $jarj = " lasku.tunnus desc";
			$use = " use index (lasno_index) ";
		}

		if ($otunnus > 0) {
			//katotaan l�ytyyk� lasku ja sen kaikki tilaukset
			$query = "  SELECT laskunro
						FROM lasku
						WHERE tunnus = '$otunnus' and lasku.$logistiikka_yhtiolisa";
			$laresult = pupe_query($query);
			$larow = mysql_fetch_assoc($laresult);

			if ($larow["laskunro"] > 0 and $toim != 'DGD') {
				$where2 .= " and lasku.laskunro = '$larow[laskunro]' ";

				$where3 = "";

				if (!isset($jarj)) $jarj = " lasku.tunnus desc";
				$use = " use index (lasno_index) ";
			}
			else {
				$where2 .= " and lasku.tunnus = '$otunnus' ";

				$where3 = "";

				if (!isset($jarj)) $jarj = " lasku.tunnus desc";
				$use = " use index (PRIMARY) ";
			}
		}

		// Mihin j�rjestykseen laitetaan
		if ($jarj != ''){
			$jarj = "ORDER BY $jarj";
		}

		if ($toim != "HAAMU") {
			$where4 = " and lasku.tila != 'D' ";
		}
		else {
			$where4 = "";
		}

		$joinlisa = "";

		if ($toim == "DGD") {
			$joinlisa = "JOIN rahtikirjat ON (rahtikirjat.yhtio = lasku.yhtio and rahtikirjat.otsikkonro=lasku.tunnus)";
		}

		// Etsit��n muutettavaa tilausta
		$query = "  SELECT distinct
					lasku.tunnus,
					if (lasku.laskunro=0, '', lasku.laskunro) laskunro,
					lasku.ytunnus,
					lasku.nimi,
					lasku.nimitark,
					if (lasku.tapvm = '0000-00-00', left(lasku.luontiaika,10), lasku.tapvm) pvm,
					lasku.toimaika,
					if (kuka.nimi !='' and kuka.nimi is not null, kuka.nimi, lasku.laatija) laatija,
					lasku.summa,
					lasku.toimaika Toimitusaika,
					lasku.tila,
					lasku.alatila,
					lasku.yhtio,
					lasku.yhtio_nimi,
					lasku.erikoisale,
					lasku.liitostunnus
					FROM lasku $use
					LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.laatija
					$joinlisa
					WHERE $where1 $where2 $where3
					and lasku.$logistiikka_yhtiolisa
					$where4
					$jarj";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {

			if ($kukarow["extranet"] == "") {

				echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
						<!--

						function toggleAll(toggleBox) {

							var currForm = toggleBox.form;
							var isChecked = toggleBox.checked;
							var nimi = toggleBox.name;

							for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
								if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,8) == nimi) {
									currForm.elements[elementIdx].checked = isChecked;
								}
							}
						}

						//-->
						</script>";

				echo "	<form method='post' action='$PHP_SELF'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='tee' value='$tee'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tila' value='monta'>
						<input type='hidden' name='ytunnus' value='$ytunnus'>
						<input type='hidden' name='asiakasid' value='$asiakasid'>
						<input type='hidden' name='toimittajaid' value='$toimittajaid'>
						<input type='hidden' name='ppa' value='$ppa'>
						<input type='hidden' name='kka' value='$kka'>
						<input type='hidden' name='vva' value='$vva'>
						<input type='hidden' name='ppl' value='$ppl'>
						<input type='hidden' name='kkl' value='$kkl'>
						<input type='hidden' name='vvl' value='$vvl'>
						<input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]'>
						<input type='hidden' name='mista' value='tulostakopio'>
						<input type='submit' value='".t("Tulosta useita kopioita")."'></form><br>";
			}
			echo "<table><tr>";

			if ($logistiikka_yhtio != '') {
				echo "<th valign='top'>",t("Yhti�"),"</th>";
			}

			echo "<th valign='top'><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&toim=$toim&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=lasku.tunnus'>".t("Tilausnro")."</a><br><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&toim=$toim&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=lasku.laskunro'>".t("Laskunro")."</a></th>";
			echo "<th valign='top'><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&toim=$toim&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=lasku.ytunnus'>".t("Ytunnus")."</a><br><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&toim=$toim&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=lasku.nimi'>".t("Nimi")."</a></th>";
			echo "<th valign='top'><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&toim=$toim&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=pvm'>".t("Pvm")."</a><br><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&toim=$toim&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=lasku.toimaika'>".t("Toimaika")."</a></th>";
			echo "<th valign='top'><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&toim=$toim&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=lasku.laatija'>".t("Laatija")."</a></th>";

			if ($kukarow['hinnat'] == 0) {
				echo "<th valign='top'><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&toim=$toim&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=summa'>".t("Summa")."</a></th>";
			}

			echo "<th valign='top'><a href='$PHP_SELF?tee=$tee&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka&toim=$toim&ytunnus=$ytunnus&asiakasid=$asiakasid&toimittajaid=$toimittajaid&jarj=lasku.tila,lasku.alatila'>".t("Tyyppi")."</a></th>";

			if ($tila == 'monta') {
				echo "<th valign='top'>".t("Tulosta")."</th>";

				echo "  <form method='post' name='tulosta' action='$PHP_SELF' autocomplete='off'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='mista' value='tulostakopio'>
						<input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]'>
						<input type='hidden' name='tee' value='TULOSTA'>";
			}

			echo "</tr>";

			while ($row = mysql_fetch_assoc($result)) {
				echo "<tr>";

				$ero="td";
				if ($tunnus == $row['tunnus']) $ero="th";

				echo "<tr>";
				if ($logistiikka_yhtio != '') echo "<$ero valign='top'>$row[yhtio_nimi]</$ero>";
				echo "<$ero valign='top'>$row[tunnus]<br>";
				if ($row['tila'] == "U" and tarkista_oikeus("muutosite.php")) {
					echo "<a href = '{$palvelin2}muutosite.php?tee=E&tunnus=$row[tunnus]&lopetus=$PHP_SELF////asiakasid=$asiakasid//ytunnus=$ytunnus//kka=$kka//vva=$vva//ppa=$ppa//kkl=$kkl//vvl=$vvl//ppl=$ppl//toim=$toim//tee=$tee//otunnus=$otunnus//laskunro=$laskunro//laskunroloppu=$laskunroloppu'>$row[laskunro]</a>";
				}
				else {
					echo "$row[laskunro]";
				}
				echo "</$ero>";
				echo "<$ero valign='top'>$row[ytunnus]<br>$row[nimi]<br>$row[nimitark]</$ero>";
				echo "<$ero valign='top'>".tv1dateconv($row["pvm"])."<br>".tv1dateconv($row["toimaika"])."</$ero>";
				echo "<$ero valign='top'>$row[laatija]</$ero>";

				if ($kukarow['hinnat'] == 0) {
					if ($toim != "LASKU" and $row["summa"] == 0) {

						if ($toim == "OSTO") {
							$kerroinlisa1 = " * if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) ";
							$kerroinlisa2 = " LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='$row[liitostunnus]' ";
						}
						else {
							$kerroinlisa1 = "";
							$kerroinlisa2 = "";
						}

						$query = "  SELECT round(sum(tilausrivi.hinta $kerroinlisa1 * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}), 2) summa
									FROM tilausrivi
									$kerroinlisa2
									WHERE tilausrivi.yhtio = '$row[yhtio]'
									and tilausrivi.otunnus = '$row[tunnus]'
									and tilausrivi.tyyppi not in ('D','V')";
						$sumres = pupe_query($query);
						$sumrow = mysql_fetch_assoc($sumres);

						echo "<$ero valign='top' align='right'>$sumrow[summa]</$ero>";
					}
					else {
						echo "<$ero valign='top' align='right'>$row[summa]</$ero>";
					}
				}

				$laskutyyppi = $row["tila"];
				$alatila     = $row["alatila"];

				//tehd��n selv�kielinen tila/alatila
				if (file_exists("../inc/laskutyyppi.inc")) {
					require('../inc/laskutyyppi.inc');
				}
				elseif (file_exists("inc/laskutyyppi.inc")) {
					require('inc/laskutyyppi.inc');
				}
				else {
					require('laskutyyppi.inc');
				}

				echo "<$ero valign='top'>".t("$laskutyyppi")." ".t("$alatila")."</$ero>";

				if ($tila != 'monta') {
					echo "<td class='back' valign='top'>";

					if ($kukarow["extranet"] == "") {
						echo "	<form method='post' action='$PHP_SELF'>
								<input type='hidden' name='lopetus' value='$lopetus'>
								<input type='hidden' name='tee' value='NAYTAHTML'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tunnus' value='$row[tunnus]'>
								<input type='hidden' name='ytunnus' value='$ytunnus'>
								<input type='hidden' name='asiakasid' value='$asiakasid'>
								<input type='hidden' name='toimittajaid' value='$toimittajaid'>
								<input type='hidden' name='otunnus' value='$otunnus'>
								<input type='hidden' name='laskunro' value='$laskunro'>
								<input type='hidden' name='ppa' value='$ppa'>
								<input type='hidden' name='kka' value='$kka'>
								<input type='hidden' name='vva' value='$vva'>
								<input type='hidden' name='ppl' value='$ppl'>
								<input type='hidden' name='kkl' value='$kkl'>
								<input type='hidden' name='vvl' value='$vvl'>
								<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
								<input type='hidden' name='mista' value='tulostakopio'>
								<input type='submit' value='".t("N�yt� ruudulla")."'></form>
								<br>";
					}

					echo "<form id='tulostakopioform_$row[tunnus]' name='tulostakopioform_$row[tunnus]' method='post' action='$PHP_SELF' autocomplete='off'>
							<input type='hidden' name='lopetus' value='$lopetus'>
							<input type='hidden' name='otunnus' value='$row[tunnus]'>
							<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tee' value='NAYTATILAUS'>
							<input type='hidden' name='mista' value='tulostakopio'>
							<input type='submit' value='".t("N�yt� pdf")."' onClick=\"js_openFormInNewWindow('tulostakopioform_$row[tunnus]', 'tulostakopio_$row[tunnus]'); return false;\"></form>";

					if ($kukarow["extranet"] == "") {
						echo "<br>
							<form method='post' action='$PHP_SELF' autocomplete='off'>
							<input type='hidden' name='otunnus' value='$row[tunnus]'>
							<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
							<input type='hidden' name='lopetus' value='$lopetus'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tee' value='TULOSTA'>
							<input type='hidden' name='mista' value='tulostakopio'>
							<input type='submit' value='".t("Tulosta")."'></form>";
					}

					echo "</td>";
				}

				if ($tila == 'monta') {
					echo "<td valign='top'><input type='checkbox' name='tulostukseen[]' value='$row[tunnus]'></td>";
				}

				echo "</tr>";
			}

			if ($tila == 'monta') {
				echo "<tr><td colspan='6' class='back' align='right'>".t("Ruksaa kaikki").":</td><td class='back'><input type='checkbox' name='tulostuk' onclick='toggleAll(this)'></td></tr>";
				echo "<tr><td colspan='8' class='back'></td><td class='back'><input type='submit' value='".t("Tulosta")."'></form></td></tr>";
			}

			echo "</table>";
		}
		else {
			echo "<font class='error'>".t("Ei tilauksia")."...</font><br><br>";
		}
	}

	if ($tee == "TULOSTA" or $tee == 'NAYTATILAUS') {

		if (!isset($kappaleet)) $kappaleet = 0;

		//valitaan tulostin heti alkuun, jos se ei ole viel� valittu
		if ($toim == "OSTO" or $toim == "HAAMU") {
			$tulostimet[0] = 'Ostotilaus';
			if ($kappaleet > 0 and $komento["Ostotilaus"] != 'email') {
				$komento["Ostotilaus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "PURKU") {
			$tulostimet[0] = 'Purkulista';
			if ($kappaleet > 0 and $komento["Purkulista"] != 'email') {
				$komento["Purkulista"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "VASTAANOTETUT") {
			$tulostimet[0] = 'Vastaanotetut';
			if ($kappaleet > 0 and $komento["Vastaanotetut"] != 'email') {
				$komento["Vastaanotetut"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "TARIFFI") {
			$tulostimet[0] = 'Tariffilista';
			if ($kappaleet > 0 and $komento["Tariffilista"] != 'email') {
				$komento["Tariffilista"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "LASKU") {
			$tulostimet[0] = 'Lasku';
			if ($kappaleet > 0 and $komento["Lasku"] != 'email') {
				$komento["Lasku"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "TUOTETARRA") {
			$tulostimet[0] = 'Tuotetarrat';
			if ($kappaleet > 0 and $komento["Tuotetarrat"] != 'email') {
				$komento["Tuotetarrat"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "TILAUSTUOTETARRA") {
			$tulostimet[0] = 'Tilauksen tuotetarrat';
			if ($kappaleet > 0 and $komento["Tilauksen tuotetarrat"] != 'email') {
				$komento["Tilauksen tuotetarrat"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "SIIRTOLISTA") {
			$tulostimet[0] = 'Siirtolista';
			if ($kappaleet > 0 and $komento["Siirtolista"] != 'email') {
				$komento["Siirtolista"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "VALMISTUS") {
			$tulostimet[0] = 'Valmistus';
			if ($kappaleet > 0 and $komento["Valmistus"] != 'email') {
				$komento["Valmistus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "SAD") {
			$tulostimet[0] = 'SAD-lomake';

			if ($yhtiorow["sad_lomake_tyyppi"] == "T") {
				$tulostimet[1] = 'SAD-lomake lis�sivu';
			}

			if ($kappaleet > 0 and $komento["SAD-lomake"] != 'email') {
				$komento["SAD-lomake"] .= " -# $kappaleet ";
			}

			if ($yhtiorow["sad_lomake_tyyppi"] == "T") {
				if ($kappaleet > 0 and $komento["SAD-lomake lis�sivu"] != 'email') {
					$komento["SAD-lomake lis�sivu"] .= " -# $kappaleet ";
				}
			}
		}
		if ($toim == "LAHETE") {
			$tulostimet[0] = 'L�hete';
			if ($kappaleet > 0 and $komento["L�hete"] != 'email') {
				$komento["L�hete"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "DGD") {
			$tulostimet[0] = 'DGD';
			if ($kappaleet > 0 and $komento["DGD"] != 'email') {
				$komento["DGD"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "PAKKALISTA") {
			$tulostimet[0] = 'Pakkalista';
			if ($kappaleet > 0 and $komento["pakkalista"] != 'email') {
				$komento["Pakkalista"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "KERAYSLISTA") {
			$tulostimet[0] = 'Ker�yslista';
			if ($kappaleet > 0 and $komento["Ker�yslista"] != 'email') {
				$komento["Ker�yslista"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "OSOITELAPPU") {
			$tulostimet[0] = 'Osoitelappu';
			if ($kappaleet > 0 and $komento["Osoitelappu"] != 'email') {
				$komento["Osoitelappu"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "VIENTIERITTELY") {
			$tulostimet[0] = 'Vientierittely';
			if ($kappaleet > 0 and $komento["Vientierittely"] != 'email') {
				$komento["Vientierittely"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "PROFORMA" and $komento["Lasku"] != 'email') {
			$tulostimet[0] = 'Lasku';
			if ($kappaleet > 0) {
				$komento["Lasku"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "TILAUSVAHVISTUS" and $komento["Lasku"] != 'email') {
			$tulostimet[0] = 'Tilausvahvistus';
			if ($kappaleet > 0) {
				$komento["Tilausvahvistus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "YLLAPITOSOPIMUS" and $komento["Lasku"] != 'email') {
			$tulostimet[0] = 'Yllapitosopimus';
			if ($kappaleet > 0) {
				$komento["Yllapitosopimus"] .= " -# $kappaleet ";
			}
		}
		if (($toim == "TARJOUS"  or $toim == "TARJOUS!!!VL" or $toim == "TARJOUS!!!BR") and $komento["Tarjous"] != 'email' and substr($komento["Tarjous"],0,12) != 'asiakasemail') {
			$tulostimet[0] = 'Tarjous';
			if ($kappaleet > 0) {
				$komento["Tarjous"] .= " -# $kappaleet ";
			}
		}
		if (($toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL" or $toim == "MYYNTISOPIMUS!!!BR") and $komento["Myyntisopimus"] != 'email') {
			$tulostimet[0] = 'Myyntisopimus';
			if ($kappaleet > 0) {
				$komento["Myyntisopimus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "OSAMAKSUSOPIMUS" and $komento["Osamaksusopimus"] != 'email') {
			$tulostimet[0] = 'Osamaksusopimus';
			if ($kappaleet > 0) {
				$komento["Osamaksusopimus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "LUOVUTUSTODISTUS" and $komento["Luovutustodistus"] != 'email') {
			$tulostimet[0] = 'Luovutustodistus';
			if ($kappaleet > 0) {
				$komento["Luovutustodistus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "VAKUUTUSHAKEMUS" and $komento["Vakuutushakemus"] != 'email') {
			$tulostimet[0] = 'Vakuutushakemus';
			if ($kappaleet > 0) {
				$komento["Vakuutushakemus"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "REKISTERIILMOITUS" and $komento["Rekister�inti_ilmoitus"] != 'email') {
			$tulostimet[0] = 'Rekister�inti_ilmoitus';
			if ($kappaleet > 0) {
				$komento["Rekister�inti_ilmoitus"] .= " -# $kappaleet ";
			}
		}
		if (($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") and $komento["Ty�m��r�ys"] != 'email') {
			$tulostimet[0] = 'Ty�m��r�ys';
			if ($kappaleet > 0) {
				$komento["Ty�m��r�ys"] .= " -# $kappaleet ";
			}
		}
		if ($toim == "REKLAMAATIO") {
			$tulostimet[0] = 'Ker�yslista';
			if ($kappaleet > 0 and $komento["Ker�yslista"] != 'email') {
				$komento["Ker�yslista"] .= " -# $kappaleet ";
			}
		}
		if (isset($tulostukseen)) {
			$tilausnumero = implode(",", $tulostukseen);
		}

		if ($otunnus == '' and $laskunro == '' and $tilausnumero == '') {
			echo "<font class='error'>".t("VIRHE: Et valinnut mit��n tulostettavaa")."!</font>";
			exit;
		}

		if ((!isset($komento) or count($komento) == 0) and $tee == 'TULOSTA') {
			require("inc/valitse_tulostin.inc");
		}

		//hateaan laskun kaikki tiedot
		$query = "  SELECT *
					FROM lasku
					WHERE";

		if (isset($tilausnumero) and $tilausnumero != '') {
			$query .= " tunnus in ($tilausnumero) ";
		}
		else {
			if ($otunnus > 0) {
				$query .= " tunnus='$otunnus' ";
			}
			if ($laskunro > 0) {
				if ($otunnus > 0) {
					$query .= " and laskunro='$laskunro' ";
				}
				else {
					$query .= " tila='U' and laskunro='$laskunro' ";
				}
			}
		}

		$query .= " AND yhtio ='$kukarow[yhtio]'";
		$rrrresult = pupe_query($query);

		while ($laskurow = mysql_fetch_assoc($rrrresult)) {

			if ($toim == "OSTO") {
				$otunnus = $laskurow["tunnus"];
				$mista = 'tulostakopio';
				require('tulosta_ostotilaus.inc');
				$tee = '';
			}

			if ($toim == "HAAMU") {
				$otunnus = $laskurow["tunnus"];
				$mista = 'tulostakopio';
				require('tulosta_ostotilaus.inc');
				$tee = '';
			}

			if ($toim == "PURKU") {
				$otunnus = $laskurow["tunnus"];
				$mista = 'tulostakopio';
				require('tulosta_purkulista.inc');
				$tee = '';
			}

			if ($toim == "VASTAANOTETUT") {
				$otunnus = $laskurow["tunnus"];
				$mista = 'vastaanota';
				require('tulosta_purkulista.inc');
				$tee = '';
			}

			if ($toim == "TUOTETARRA") {
				$otunnus = $laskurow["tunnus"];
				require('tulosta_tuotetarrat.inc');
				$tee = '';
			}

			if ($toim == "TILAUSTUOTETARRA") {
				$otunnus = $laskurow["tunnus"];
				require('tulosta_tilaustuotetarrat.inc');
				tulosta_tilaustuotetarrat($otunnus, 0, $komento["Tilauksen tuotetarrat"], $tee);
				$tee = '';
			}

			if ($toim == "TARIFFI") {
				$otunnus = $laskurow["tunnus"];
				require('tulosta_tariffilista.inc');
				$tee = '';
			}

			if ($toim == "SAD") {
				$uusiotunnus = $laskurow["tunnus"];

				if ($yhtiorow["sad_lomake_tyyppi"] == "T" and $tee != 'NAYTATILAUS') {

					require('tulosta_sadvientiilmo_teksti.inc');

					if ($paalomake != '') {
						lpr($paalomake,0, $komento["SAD-lomake"]);

						echo t("SAD-lomake tulostuu")."...<br>";

						if ($lisalomake != "") {
							lpr($lisalomake,0, $komento["SAD-lomake lis�sivu"]);

							echo t("SAD-lomakkeen lis�sivu tulostuu")."...<br>";
						}
					}
				}
				else {
					require_once('pdflib/phppdflib.class.php');

					require('tulosta_sadvientiilmo.inc');

					//keksit��n uudelle failille joku varmasti uniikki nimi:
					list($usec, $sec) = explode(' ', microtime());
					mt_srand((float) $sec + ((float) $usec * 100000));
					$pdffilenimi = "/tmp/SAD_Lomake_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

					//kirjoitetaan pdf faili levylle..
					$fh = fopen($pdffilenimi, "w");
					if (fwrite($fh, $pdf2->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
					fclose($fh);

					// itse print komento...
					if ($komento["SAD-lomake"] == 'email') {
						$liite = $pdffilenimi;

						$kutsu = t("SAD-lomake", $kieli);

						if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
							$kutsu .= " ".trim($laskurow["nimi"]);
						}

						require("../inc/sahkoposti.inc");
					}
					elseif ($tee == 'NAYTATILAUS') {
						//Ty�nnet��n tuo pdf vaan putkeen!
						echo file_get_contents($pdffilenimi);
					}
					elseif ($komento["SAD-lomake"] != '' and $komento["SAD-lomake"] != 'edi') {
						$line = exec($komento["SAD-lomake"]." ".$pdffilenimi);
					}

					//poistetaan tmp file samantien kuleksimasta...
					system("rm -f $pdffilenimi");

					if ($tee != 'NAYTATILAUS') {
						echo t("SAD-lomake tulostuu")."...<br>";
					}
				}

				$tee = '';
			}

			if ($toim == "VIENTIERITTELY") {
				$uusiotunnus = $laskurow["tunnus"];

				require_once('pdflib/phppdflib.class.php');
				require('tulosta_vientierittely.inc');

				//keksit��n uudelle failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$pdffilenimi = "/tmp/Vientierittely_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

				//kirjoitetaan pdf faili levylle..
				$fh = fopen($pdffilenimi, "w");
				if (fwrite($fh, $Xpdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
				fclose($fh);

				// itse print komento...
				if ($komento["Vientierittely"] == 'email') {
					$liite = $pdffilenimi;

					$kutsu = t("Vientierittely", $kieli);

					if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
						$kutsu .= " ".trim($laskurow["nimi"]);
					}

					require("../inc/sahkoposti.inc");
				}
				elseif ($tee == 'NAYTATILAUS') {
					//Ty�nnet��n tuo pdf vaan putkeen!
					echo file_get_contents($pdffilenimi);
				}
				elseif ($komento["Vientierittely"] != '' and $komento["Vientierittely"] != 'edi') {
					$line = exec($komento["Vientierittely"]." ".$pdffilenimi);
				}
				//poistetaan tmp file samantien kuleksimasta...
				system("rm -f $pdffilenimi");

				if ($tee != 'NAYTATILAUS') {
					echo t("Vientierittely tulostuu")."...<br>";
				}

				$tee = '';
			}

			if ($toim == "LASKU" or $toim == 'PROFORMA') {

				if (@include_once("tilauskasittely/tulosta_lasku.inc"));
				elseif (@include_once("tulosta_lasku.inc"));
				else exit;

				tulosta_lasku($laskurow["tunnus"], $kieli, $tee, $toim, $komento["Lasku"]);

				if ($tee != 'NAYTATILAUS') {
					echo t("Lasku tulostuu")."...<br>";
					$tee = '';
				}
			}

			if ($toim == "TILAUSVAHVISTUS" or $toim == "YLLAPITOSOPIMUS") {

				if (isset($seltvtyyppi) and $seltvtyyppi != "") {
					$laskurow['tilausvahvistus'] = $seltvtyyppi;
				}

				$params_tilausvahvistus = array(
				'tee'						=> $tee,
				'toim'						=> $toim,
				'kieli'						=> $kieli,
				'komento'					=> $komento,
				'laskurow'					=> $laskurow,
				'naytetaanko_rivihinta'		=> $naytetaanko_rivihinta,
				'extranet_tilausvahvistus'	=> $extranet_tilausvahvistus,
				);

				laheta_tilausvahvistus($params_tilausvahvistus);

				$tee = '';
			}

			if ($toim == "TARJOUS" or $toim == "TARJOUS!!!VL" or $toim == "TARJOUS!!!BR") {
				$otunnus = $laskurow["tunnus"];
				list ($toimalku, $hinnat) = explode("!!!", $toim);

				require_once ("tulosta_tarjous.inc");

				tulosta_tarjous($otunnus, $komento["Tarjous"], $kieli, $tee, $hinnat, $verolliset_verottomat_hinnat, $naytetaanko_rivihinta);

				$tee = '';
			}

			if ($toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL" or $toim == "MYYNTISOPIMUS!!!BR") {

				$otunnus = $laskurow["tunnus"];
				list ($toimalku, $hinnat) = explode("!!!", $toim);

				require_once ("tulosta_myyntisopimus.inc");

				tulosta_myyntisopimus($otunnus, $komento["Myyntisopimus"], $kieli, $tee, $hinnat);

				$tee = '';
			}

			if ($toim == "OSAMAKSUSOPIMUS") {

				$otunnus = $laskurow["tunnus"];

				require_once ("tulosta_osamaksusoppari.inc");

				tulosta_osamaksusoppari($otunnus, $komento["Osamaksusopimus"], $kieli, $tee);

				$tee = '';
			}

			if ($toim == "LUOVUTUSTODISTUS") {

				$otunnus = $laskurow["tunnus"];

				require_once ("tulosta_luovutustodistus.inc");

				tulosta_luovutustodistus($otunnus, $komento["Luovutustodistus"], $kieli, $tee);

				$tee = '';
			}

			if ($toim == "VAKUUTUSHAKEMUS") {

				$otunnus = $laskurow["tunnus"];

				require_once ("tulosta_vakuutushakemus.inc");

				tulosta_vakuutushakemus($otunnus, $komento["Vakuutushakemus"], $kieli, $tee);

				$tee = '';
			}

			if ($toim == "REKISTERIILMOITUS") {

				$otunnus = $laskurow["tunnus"];

				require_once ("tulosta_rekisteriilmoitus.inc");

				tulosta_rekisteriilmoitus($otunnus, $komento["Rekister�inti_ilmoitus"], $kieli, $tee);

				$tee = '';
			}

			if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
				//Tehd��n joini
				$query = "  SELECT tyomaarays.*, lasku.*
							FROM lasku
							LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
							WHERE lasku.yhtio='$kukarow[yhtio]'
							and lasku.tunnus='$laskurow[tunnus]'";
				$result = pupe_query($query);
				$laskurow = mysql_fetch_assoc($result);

				require_once ("tyomaarays/tulosta_tyomaarays.inc");

				$otunnus = $laskurow["tunnus"];

				$sorttauskentta = generoi_sorttauskentta($asrow["tyomaarayksen_jarjestys"]);
				$order_sorttaus = $asrow["tyomaarayksen_jarjestys_suunta"];

				if ($yhtiorow["tyomaarayksen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
				else $pjat_sortlisa = "";

				//ty�m��r�yksen rivit
				$query = "  SELECT tilausrivi.*,
							round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
							$sorttauskentta,
							if (tuote.tuotetyyppi='K','2 Ty�t','1 Muut') tuotetyyppi,
							if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
							tuote.sarjanumeroseuranta
							FROM tilausrivi
							JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
							JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
							WHERE tilausrivi.otunnus = '$otunnus'
							and tilausrivi.yhtio 	= '$kukarow[yhtio]'
							and tilausrivi.yhtio 	= tuote.yhtio
							and tilausrivi.tuoteno  = tuote.tuoteno
							ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
				$result = pupe_query($query);

				$tilausnumeroita = $otunnus;

				//generoidaan rivinumerot
				$rivinumerot = array();

				$kal = 1;

				while ($row = mysql_fetch_assoc($result)) {
					$rivinumerot[$row["tunnus"]] = $kal;
					$kal++;
				}

				mysql_data_seek($result,0);

				unset($pdf);
				unset($page);

				$sivu  = 1;
				$paino = 0;

				if ($toim == "SIIRTOTYOMAARAYS") {
					$tyyppi = "SISAINEN";
				}
				elseif ($tyomtyyppi == "O" or $kukarow['hinnat'] != 0) {
					$tyyppi = "O";
				}
				elseif ($tyomtyyppi == "P") {
					$tyyppi = "P";
				}
				elseif ($tyomtyyppi == "A") {
					$tyyppi = "";
				}
				else {
					$tyyppi = $yhtiorow["tyomaaraystyyppi"];
				}

				// Aloitellaan l�hetteen teko
				$page[$sivu] = tyomaarays_alku($tyyppi);

				while ($row = mysql_fetch_assoc($result)) {
					tyomaarays_rivi($page[$sivu], $tyyppi);
				}

				if ($yhtiorow['tyomaarays_tulostus_lisarivit'] == 'L') {
					tyomaarays_loppu_lisarivit($page[$sivu], 1);
				}
				else {
					tyomaarays_loppu($page[$sivu], 1);
				}

				//tulostetaan sivu
				tyomaarays_print_pdf($komento["Ty�m��r�ys"]);
				$tee = '';
			}

			if ($toim == "VALMISTUS") {

				// katotaan miten halutaan sortattavan
				// haetaan asiakkaan tietojen takaa sorttaustiedot
				$order_sorttaus = '';

				$asiakas_apu_query = "	SELECT lahetteen_jarjestys, lahetteen_jarjestys_suunta, email
										FROM asiakas
										WHERE yhtio = '$kukarow[yhtio]'
										and tunnus = '$laskurow[liitostunnus]'";
				$asiakas_apu_res = pupe_query($asiakas_apu_query);

				if (mysql_num_rows($asiakas_apu_res) == 1) {
					$asiakas_apu_row = mysql_fetch_array($asiakas_apu_res);
					$sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["lahetteen_jarjestys"] != "" ? $asiakas_apu_row["lahetteen_jarjestys"] : $yhtiorow["lahetteen_jarjestys"]);
					$order_sorttaus = $asiakas_apu_row["lahetteen_jarjestys_suunta"] != "" ? $asiakas_apu_row["lahetteen_jarjestys_suunta"] : $yhtiorow["lahetteen_jarjestys_suunta"];
				}
				else {
					$sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);
					$order_sorttaus = $yhtiorow["lahetteen_jarjestys_suunta"];
				}

				if ($yhtiorow["lahetteen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
				else $pjat_sortlisa = "";

				$query = " 	SELECT tilausrivi.*,
							$sorttauskentta,
							if (tuote.tuotetyyppi='K','2 Ty�t','1 Muut') tuotetyyppi
							FROM tilausrivi use index (yhtio_otunnus)
							JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.var in ('','H')
							and tilausrivi.tyyppi != 'D'
							ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
				$result = pupe_query($query);

				require_once ("tulosta_valmistus.inc");

				$tilausnumeroita = $laskurow["tunnus"];

				//generoidaan rivinumerot
				$rivinumerot = array();

				$kal = 1;

				while ($row = mysql_fetch_assoc($result)) {
					$rivinumerot[$row["tunnus"]] = $kal;
					$kal++;
				}

				mysql_data_seek($result,0);

				unset($pdf);
				unset($page);

				$sivu  = 1;
				$paino = 0;

				// Aloitellaan l�hetteen teko
				$page[$sivu] = alku_valm($tyyppi);

				//	Koontisivu
				if ($yhtiorow["valmistuksen_etusivu"] != "") {
					while ($row = mysql_fetch_assoc($result)) {
						if (in_array($row["tyyppi"], array("W", "L"))) {
							rivi_valm($page[$sivu], "ETUSIVU");
						}
					}

					mysql_data_seek($result,0);
				}

				while ($row = mysql_fetch_assoc($result)) {
					rivi_valm($page[$sivu], $tyyppi);
				}

				loppu_valm($page[$sivu], 1);
				print_pdf_valm($komento["Valmistus"]);

				$tee = '';
			}

			if ($toim == "LAHETE" or $toim == "PAKKALISTA") {

				$params = array(
					'laskurow'					=> $laskurow,
					'sellahetetyyppi' => $sellahetetyyppi,
					'extranet_tilausvahvistus' => $extranet_tilausvahvistus,
					'naytetaanko_rivihinta'		=> $naytetaanko_rivihinta,
					'tee'						=> $tee,
					'toim'						=> $toim,
					'query_ale_lisa' => $query_ale_lisa,
					'komento' => $komento
					);

				pupesoft_tulosta_lahete($params);

				$tee = '';
			}

			if ($toim == "DGD") {

				$otunnus = $laskurow["tunnus"];

				require_once("tilauskasittely/tulosta_dgd.inc");

				$params_dgd = array(
				'kieli'			=> $kieli,
				'laskurow'		=> $laskurow,
				'page'			=> NULL,
				'pdf'			=> NULL,
				'row'			=> NULL,
				'sivu'			=> 0,
				'tee'			=> $tee,
				'toim'			=> $toim,
				'norm'			=> $norm,
				);

				// Aloitellaan DGD:n teko
				$params_dgd = alku_dgd($params_dgd);
				$params_dgd = rivi_dgd($params_dgd);
				$params_dgd = loppu_dgd($params_dgd);

				//tulostetaan sivu
				$params_dgd["komento"] = $komento["DGD"];
				print_pdf_dgd($params_dgd);

				$tee = '';
			}

			if ($toim == "KERAYSLISTA" or $toim == "SIIRTOLISTA" or $toim == "REKLAMAATIO") {

				require_once ("tulosta_lahete_kerayslista.inc");

				$otunnus = $laskurow["tunnus"];
				$tilausnumeroita = $otunnus;

				//haetaan asiakkaan tiedot
				$query = "  SELECT lahetetyyppi, luokka, puhelin, if (asiakasnro!='', asiakasnro, ytunnus) asiakasnro
							FROM asiakas
							WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
				$result = pupe_query($query);
				$asrow = mysql_fetch_assoc($result);

				$query = "	SELECT ulkoinen_jarjestelma
							FROM varastopaikat
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$laskurow[varasto]'";
				$result = pupe_query($query);
				$varastorow = mysql_fetch_assoc($result);

				$select_lisa 		= "";
				$where_lisa 		= "";
				$lisa1 				= "";
				$pjat_sortlisa 		= "";
				$kerayslistatyyppi 	= "";

				if ($varastorow["ulkoinen_jarjestelma"] == "G") {
					$kerayslistatyyppi = "EXCEL2";
				}
				elseif ($varastorow["ulkoinen_jarjestelma"] == "C") {
					$kerayslistatyyppi = "EXCEL1";
				}

				// ker�yslistalle ei oletuksena tulosteta saldottomia tuotteita
				if ($yhtiorow["kerataanko_saldottomat"] == '') {
					$lisa1 = " and tuote.ei_saldoa = '' ";
				}

				if ($laskurow["tila"] == "V") {
					$sorttauskentta = generoi_sorttauskentta($yhtiorow["valmistus_kerayslistan_jarjestys"]);
					$order_sorttaus = $yhtiorow["valmistus_kerayslistan_jarjestys_suunta"];

					if ($yhtiorow["valmistus_kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

					// Summataan rivit yhteen (HUOM! unohdetaan kaikki perheet!)
					if ($yhtiorow["valmistus_kerayslistan_jarjestys"] == "S") {
						$select_lisa = "sum(tilausrivi.kpl) kpl, sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, '' perheid, '' perheid2, ";
						$where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
					}
				}
				else {
					$sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
					$order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

					if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

					// Summataan rivit yhteen (HUOM! unohdetaan kaikki perheet!)
					if ($yhtiorow["kerayslistan_jarjestys"] == "S") {
						$select_lisa = "sum(tilausrivi.kpl) kpl, sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, '' perheid, '' perheid2, ";
						$where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
					}
				}

				//ker�yslistan rivit
				$query = "  SELECT tilausrivi.*,
							tuote.sarjanumeroseuranta,
							$select_lisa
							$sorttauskentta,
							if (tuote.tuotetyyppi='K','2 Ty�t','1 Muut') tuotetyyppi,
							if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
							tuote.sarjanumeroseuranta,
							tuote.eankoodi
							FROM tilausrivi
							JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
							WHERE tilausrivi.otunnus = '$otunnus'
							and tilausrivi.yhtio 	= '$kukarow[yhtio]'
							$lisa1
							$where_lisa
							ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
				$riresult = pupe_query($query);

				//generoidaan rivinumerot
				$rivinumerot = array();

				$kal = 1;

				while ($row = mysql_fetch_assoc($riresult)) {
					$rivinumerot[$row["tunnus"]] = $kal;
					$kal++;
				}

				mysql_data_seek($riresult,0);

				if ($toim == "SIIRTOLISTA") {
					$tyyppi = "SIIRTOLISTA";
				}
				elseif ($toim == "REKLAMAATIO") {
					$tyyppi = "REKLAMAATIO";
				}
				else {
					$tyyppi = "";
				}

				$params_kerayslista = array(
				'asrow'           	=> $asrow,
				'boldi'           	=> $boldi,
				'iso'             	=> $iso,
				'kala'            	=> 0,
				'kieli'           	=> $kieli,
				'komento'			=> '',
				'laskurow'        	=> $laskurow,
				'norm'            	=> $norm,
				'page'            	=> NULL,
				'paino'           	=> 0,
				'pdf'             	=> NULL,
				'perheid'         	=> 0,
				'perheid2'        	=> 0,
				'pieni'           	=> $pieni,
				'pieni_boldi'     	=> $pieni_boldi,
				'rectparam'       	=> $rectparam,
				'rivinkorkeus'    	=> $rivinkorkeus,
				'rivinumerot'    	=> $rivinumerot,
				'row'             	=> NULL,
				'sivu'            	=> 1,
				'tee'             	=> $tee,
				'thispage'			=> NULL,
				'tilausnumeroita' 	=> $tilausnumeroita,
				'toim'            	=> $toim,
				'tots'            	=> 0,
				'tyyppi'		  	=> $tyyppi,
				'kerayslistatyyppi'	=> $kerayslistatyyppi);

				// Aloitellaan ker�yslistan teko
				$params_kerayslista = alku_kerayslista($params_kerayslista);

				while ($row = mysql_fetch_assoc($riresult)) {
					$params_kerayslista["row"] = $row;
					$params_kerayslista = rivi_kerayslista($params_kerayslista);
				}

				$params_kerayslista["tots"] = 1;
				$params_kerayslista = loppu_kerayslista($params_kerayslista);

				if ($toim == "SIIRTOLISTA" and isset($komento["Siirtolista"])) {
					$params_kerayslista["komento"] = $komento["Siirtolista"];
				}
				elseif (isset($komento["Ker�yslista"])) {
					$params_kerayslista["komento"] = $komento["Ker�yslista"];
				}

				//tulostetaan sivu
				print_pdf_kerayslista($params_kerayslista);

				$tee = '';
			}

			if ($toim == "OSOITELAPPU") {
				$tunnus = $laskurow["tunnus"];
				$oslapp = $komento["Osoitelappu"];

				$query = "  SELECT GROUP_CONCAT(DISTINCT if (tunnusnippu>0, concat(tunnusnippu,'/',tunnus),tunnus) ORDER BY tunnus SEPARATOR ', ') tunnukset
							FROM lasku
							WHERE yhtio='$kukarow[yhtio]' and tila='L' and kerayslista='$laskurow[kerayslista]' and kerayslista != 0";
				$toimresult = pupe_query($query);
				$toimrow = mysql_fetch_assoc($toimresult);

				$tilausnumeroita = $toimrow["tunnukset"];

				if ($oslapp != '' or $tee == 'NAYTATILAUS') {

					$query = "SELECT osoitelappu FROM toimitustapa WHERE yhtio = '$kukarow[yhtio]' and selite = '$laskurow[toimitustapa]'";
					$oslares = pupe_query($query);
					$oslarow = mysql_fetch_assoc($oslares);

					if ($oslarow['osoitelappu'] == 'intrade') {
						require('osoitelappu_intrade_pdf.inc');
					}
					else {
						require ("osoitelappu_pdf.inc");

					}
				}
				$tee = '';
			}

			// Siirryt��n takaisin sielt� mist� tultiin
			if ($lopetus != '' and !isset($nayta_pdf)) {
				lopetus($lopetus, "META");
			}
		}
	}

	if (@include("inc/footer.inc"));
	elseif (@include("footer.inc"));
	else exit;
?>