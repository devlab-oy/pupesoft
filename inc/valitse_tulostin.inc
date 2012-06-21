<?php

	echo "<form method='post' action='$PHP_SELF?$ulisa'>
		<input type='hidden' name='lopetus' value='$lopetus'>
		<input type='hidden' name='toim' value='$toim'>
		<input type='hidden' name='tila' value='$tila'>
		<input type='hidden' name='raportti' value='$raportti'>
		<input type='hidden' name='top' value='$top'>
		<input type='hidden' name='tee' value='$tee'>
		<input type='hidden' name='otunnus' value='$otunnus'>
		<input type='hidden' name='laskunro' value='$laskunro'>
		<input type='hidden' name='ppa' value='$ppa'>
		<input type='hidden' name='kka' value='$kka'>
		<input type='hidden' name='vva' value='$vva'>
		<input type='hidden' name='ppl' value='$ppl'>
		<input type='hidden' name='kkl' value='$kkl'>
		<input type='hidden' name='vvl' value='$vvl'>
		<input type='hidden' name='try' value='$try'>
		<input type='hidden' name='osasto' value='$osasto'>
		<input type='hidden' name='toiminto' value='$toiminto'>
		<input type='hidden' name='toimittajaid' value='$toimittajaid'>
		<input type='hidden' name='arvomatikka' value='$arvomatikka'>
		<input type='hidden' name='naytainvtuot' value='$naytainvtuot'>
		<input type='hidden' name='tuoteno' value='$tuoteno'>
		<input type='hidden' name='ahyllyalue' value='$ahyllyalue'>
		<input type='hidden' name='ahyllynro'  value='$ahyllynro'>
		<input type='hidden' name='ahyllyvali' value='$ahyllyvali'>
		<input type='hidden' name='ahyllytaso' value='$ahyllytaso'>
		<input type='hidden' name='lhyllyalue' value='$lhyllyalue'>
		<input type='hidden' name='lhyllynro'  value='$lhyllynro'>
		<input type='hidden' name='lhyllyvali' value='$lhyllyvali'>
		<input type='hidden' name='lhyllytaso' value='$lhyllytaso'>
		<input type='hidden' name='naytasaldo' value='$naytasaldo'>
		<input type='hidden' name='toimittaja' value='$toimittaja'>
		<input type='hidden' name='asiakasid' value='$asiakasid'>
		<input type='hidden' name='tilausnumero' value='$tilausnumero'>
		<input type='hidden' name='saldot' value='$saldot'>
		<input type='hidden' name='toimas' value='$toimas'>
		<input type='hidden' name='ei_inventointi' value='$ei_inventointi'>
		<input type='hidden' name='prosmuutos' value='$prosmuutos'>
		<input type='hidden' name='kplmuutos' value='$kplmuutos'>
		<input type='hidden' name='tuotemerkki' value='$tuotemerkki'>
		<input type='hidden' name='rajaus' value='$rajaus'>
		<input type='hidden' name='jarjestys' value='$jarjestys'>
		<input type='hidden' name='debug' value='$debug'>
		<input type='hidden' name='naytanimitys' value='$naytanimitys'>
		<input type='hidden' name='ippa' value='$ippa'>
		<input type='hidden' name='ikka' value='$ikka'>
		<input type='hidden' name='ivva' value='$ivva'>
		<input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]'>
		<input type='hidden' name='sorttauskentan_jarjestys1' value='$sorttauskentan_jarjestys1'>
		<input type='hidden' name='sorttauskentan_jarjestys2' value='$sorttauskentan_jarjestys2'>
		<input type='hidden' name='sorttauskentan_jarjestys3' value='$sorttauskentan_jarjestys3'>
		<input type='hidden' name='sorttauskentan_jarjestys4' value='$sorttauskentan_jarjestys4'>
		<input type='hidden' name='kertakassa' value='$kertakassa'>
		<input type='hidden' name='otunnukset_array' value='$otunnukset_array'>
		<input type='hidden' name='varastoonvaikutus' value='$varastoonvaikutus'>
		<input type='hidden' name='varastonarvo' value='$varastonarvo'>
		<input type='hidden' name='kerayseran_numero' value='$kerayseran_numero'>
		<input type='hidden' name='kerayseran_tilaukset' value='$kerayseran_tilaukset'>
		<input type='hidden' name='reservipaikka' value='{$reservipaikka}' />";

	if ($nappikeikalle == 'menossa') {
		echo "<input type='hidden' name='nappikeikalla' value='ollaan'>";
	}

	echo "<table>";

	$mista = 'asiakas';
	$valittu = "";

	if ($toim == "OSTO" or $tulostimet[0] == "Ostotilaus") {
		echo "<tr><th>".t("Tulosta nimitykset ostotilaukselle")."</th><td><input type='checkbox' name='nimitykset'></td></tr>";

		$mista = 'toimi';

		$editsql = "	SELECT *
						FROM toimi
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus = '$toimittajaid'";
		$edires = pupe_query($editsql);
		$editoimrow = mysql_fetch_assoc($edires);
	}

	if ($toim == "LAHETE" and $yhtiorow["lahete_tyyppi_tulostus"] != '' and $otunnus != '') {
		echo "<tr><th>".t("Tulosta")."</th><td><select name='sellahetetyyppi'>";

		$lahetetyyppi = pupesoft_lahetetyyppi($otunnus);

		$vresult = t_avainsana("LAHETETYYPPI");

		while($row = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($row["selite"] == $lahetetyyppi) $sel = 'selected';

			echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
		}

		echo "</select></td></tr>";

		$mista = 'asiakas';
	}

	if ($toim == "TILAUSVAHVISTUS" and $yhtiorow["tilausvahvistus_tyyppi_tulostus"] != '' and $otunnus != '') {

		$query2 = "	SELECT tilausvahvistus
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$otunnus'";
		$vresult2 = pupe_query($query2);
		$row2 = mysql_fetch_array($vresult2);

		if ($kukarow['extranet'] != "" and $kukarow['hyvaksyja'] != '') {
			$naytatvale = 2;
		}
		elseif (strpos($row2['tilausvahvistus'], '7') !== FALSE) {
			$naytatvale = 7; // jos meill‰ on tilausvahvistuksessa seiska, niin tehd‰‰n EAN tilausvahvistus
		}
		elseif (strpos($row2['tilausvahvistus'], '6') !== FALSE) {
			$naytatvale = 6; // jos meill‰ on tilausvahvistuksessa kutonen, niin tehd‰‰n mefa customoitu tilausvahvistus
		}
		elseif (strpos($row2['tilausvahvistus'], '5') !== FALSE) {
			$naytatvale = 5; // jos mell‰ on tilausvahvistuksessa vitonen, niin haetaan hinnat toisesta pupesta
		}
		elseif (strpos($row2['tilausvahvistus'], '4') !== FALSE) {
			$naytatvale = 4; // jos mell‰ on tilausvahvistuksessa nelonen, ei haluta n‰hd‰ alennuksia, n‰ytet‰‰n tilausrivin hinta ja rivihinta
		}
		elseif (strpos($row2['tilausvahvistus'], '3') !== FALSE) {
			$naytatvale = 3; // jos mell‰ on tilausvahvistuksessa kolmonen, ei haluta n‰hd‰ hintoja, pelk‰st‰‰n kpl-m‰‰r‰t
		}
		elseif (strpos($row2['tilausvahvistus'], '2') !== FALSE) {
			$naytatvale = 2; // jos mell‰ on tilausvahvistuksessa kakkonen, ei haluta n‰h‰ aleja
		}
		elseif (strpos($row2['tilausvahvistus'], '1') !== FALSE) {
			$naytatvale = 1; // jos mell‰ on tilausvahvistuksessa ykkˆnen, halutaan n‰h‰ alet
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '7') !== FALSE) {
			$naytatvale = 7; // jos meill‰ on tilausvahvistuksessa seiska, niin tehd‰‰n EAN tilausvahvistus
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '6') !== FALSE) {
			$naytatvale = 6; // jos meill‰ on tilausvahvistuksessa kutonen, niin tehd‰‰n customoitu tilausvahvistus
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '5') !== FALSE) {
			$naytatvale = 5; // jos mell‰ on tilausvahvistuksessa vitonen, niin haetaan hinnat toisesta pupesta
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '4') !== FALSE) {
			$naytatvale = 4; // jos mell‰ on tilausvahvistuksessa nelonen, ei haluta n‰hd‰ alennuksia, n‰ytet‰‰n tilausrivin hinta ja rivihinta
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '3') !== FALSE) {
			$naytatvale = 3; // jos mell‰ on tilausvahvistuksessa kolmonen, ei haluta n‰hd‰ hintoja, pelk‰st‰‰n kpl-m‰‰r‰t
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '2') !== FALSE) {
			$naytatvale = 2; // jos mell‰ on tilausvahvistuksessa kakkonen, ei haluta n‰h‰ aleja
		}
		else {
			$naytatvale = 1; // halutaan n‰h‰ alet
		}

		$tvsel[$naytatvale] = "SELECTED";

		echo "<tr><th>".t("Tyyppi")."</th><td><select name='seltvtyyppi'>";
		echo "<option value = '1' $tvsel[1]>".t("N‰ytet‰‰n asiakkaan hinta ja alennus")."</option>";
		echo "<option value = '2' $tvsel[2]>".t("N‰ytet‰‰n tuotteen myyntihinta ja alennusryhm‰")."</option>";
		echo "<option value = '3' $tvsel[3]>".t("Ei n‰ytet‰ hintoja eik‰ alennuksia")."</option>";
		echo "<option value = '4' $tvsel[4]>".t("N‰ytet‰‰n asiakkaan hinta")."</option>";
		echo "<option value = '5' $tvsel[5]>".t("Haetaan hinnat toisesta pupesta")."</option>";
		echo "<option value = '6' $tvsel[6]>".t("N‰ytet‰‰n asiakkaan hinta ja alennus, modifioitu tulostuspohja")."</option>";
		echo "<option value = '7' $tvsel[7]>".t("EAN tilausvahvistus")."</option>";
		echo "</select></td></tr>";
	}

	if ($toim == "TARJOUS") {
		echo "<tr><th>",t("N‰ytet‰‰nkˆ verolliset vai verottomat hinnat"),"</th><td><select name='verolliset_verottomat_hinnat'>";
		echo "<option value=''>",t("Haetaan yhtiˆn parametreist‰"),"</option>";
		echo "<option value='verolliset'>",t("Verolliset"),"</option>";
		echo "<option value='verottomat'>",t("Verottomat"),"</option>";
		echo "</select></td></tr>";
	}

	if ($toim == "TARJOUS" or $toim == "TILAUSVAHVISTUS") {
		echo "<tr><th>",t("N‰ytet‰‰nkˆ rivihinta"),"</th><td><select name='naytetaanko_rivihinta'>";
		echo "<option value=''>",t("N‰ytet‰‰n rivihinta"),"</option>";
		echo "<option value='ei_rivihintaa'>",t("Ei n‰ytet‰ rivihintaa"),"</option>";
		echo "</select></td></tr>";
	}

	echo "<tr><th>".t("Kpl")."</th><td><input type='text' name='kappaleet' size='3'></td></tr>";

	echo "<tr><th>".t("Valitse kieli")."</th>";

	///* Haetaan asiakkaan kieli *///
	if ($otunnus != '' or $laskunro != '') {

		$querykieli = "	SELECT liitostunnus
						FROM lasku
						WHERE";

		if ($otunnus > 0) {
			$querykieli .= " tunnus='$otunnus' ";
		}
		elseif ($laskunro > 0 and $toim != "OSTO") {
			if ($otunnus > 0) {
				$querykieli .= " and laskunro='$laskunro' ";
			}
			else {
				$querykieli .= " tila='U' and laskunro='$laskunro' ";
			}
		}
		else {
			$querykieli .= " and tunnus='$kukarow[kesken]' ";
		}

		$querykieli .= " AND yhtio ='$kukarow[yhtio]'";
		$resultkieli = pupe_query($querykieli);
		$asrow = mysql_fetch_array($resultkieli);

		$querykieli = "	SELECT kieli, email
						FROM $mista
						WHERE
						tunnus = '$asrow[liitostunnus]'
						AND yhtio ='$kukarow[yhtio]'";
		$resultkieli = pupe_query($querykieli);
		$asrow = mysql_fetch_array($resultkieli);

		if ($asrow["kieli"] != '') {
			$sel[$asrow["kieli"]] = "SELECTED";
		}
		else {
			$sel[$yhtiorow["kieli"]] = "SELECTED";
		}
	}
	else {
		$sel[$yhtiorow["kieli"]] = "SELECTED";
	}

	$query  = "	SHOW columns
				FROM sanakirja";
	$fields =  pupe_query($query);

	echo "<td><select name='kieli'>";

	while ($apurow = mysql_fetch_array($fields)) {
		if (strlen($apurow[0]) == 2) {
			$query = "SELECT DISTINCT nimi FROM maat WHERE koodi = '{$apurow[0]}'";
			$maare = pupe_query($query);

			$maaro = mysql_fetch_array($maare);

			$maa   = strtolower($maaro["nimi"]);
			if ($maa=="") $maa = $apurow[0];

			$makkoodi = $apurow[0];

			echo "<option value='$makkoodi' $sel[$makkoodi]>".t("$maa")."</option>";
		}
	}

	echo "</select></td></tr>";

	foreach ($tulostimet as $tulostin) {

		if ($tulostin == 'Purkulista' and $yhtiorow['suuntalavat'] == 'S' and $toim != 'REKLAMAATIO') {

			$valitut_suuntalavat = array();
			$valitut_suuntalisa  = "";

			if (isset($valitut_lavat) and $valitut_lavat != "") {
				$valitut_suuntalavat = explode(",", $valitut_lavat);
				$valitut_suuntalisa = " AND suuntalavat.tunnus in (".mysql_real_escape_string($valitut_lavat).")";
			}

			$query = "	SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc
						FROM suuntalavat
						JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus)
						WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
						AND suuntalavat.tila 	= 'S'
						AND (suuntalavat.kaytettavyys = 'L' OR (suuntalavat.kaytettavyys = 'Y' AND suuntalavat_saapuminen.saapuminen = '{$otunnus}'))
						{$valitut_suuntalisa}";
			$suuntalavat_res = pupe_query($query);

			if (mysql_num_rows($suuntalavat_res) == 0) continue;
		}

		echo "<tr><th>".t("Valitse")." '".t("$tulostin")."' ".t("tulostuspaikka")."</th>";
		echo "<td><select name='komento[$tulostin]'>";
		echo "<option value=''>".t("Ei kirjoitinta")."</option>";
		echo "<option value='PDF_RUUDULLE'>".t('PDF Ruudulle')."</option>";

		if (($toim == 'TARJOUS' or $toim == 'MYYNTISOPIMUS') and $asrow['email'] != '') {
			echo "<option value='asiakasemail$asrow[email]'>".t("Asiakkaan s‰hkˆpostiin").": $asrow[email]</option>";
		}

		if (isset($editoimrow) and $editoimrow["edi_kuvaus"] != '' and (preg_match("/(excel|wabco)/", $editoimrow['edi_kuvaus']) or ($editoimrow["edi_palvelin"] != '' and $editoimrow["edi_kayttaja"] != '' and $editoimrow["edi_salasana"] != '' and $editoimrow["edi_polku"] != ''))) {
			$rajaus = "";
			$valittu = "edi";
		}
		else {
			$rajaus = " and komento != 'edi' ";
		}

		$querykieli = "	SELECT *
						from kirjoittimet
						where yhtio='$kukarow[yhtio]'
						$rajaus
						ORDER BY kirjoitin";
		$kires = pupe_query($querykieli);

		while ($kirow=mysql_fetch_array($kires)) {

			$sel = "";
			if ($kirow["tunnus"] == $kukarow["kirjoitin"] and $valittu != "edi") {
				$sel = "SELECTED";
			}
			if ($kirow["komento"] == "edi" and $valittu == "edi") {
				$sel = "SELECTED";
			}

			echo "<option value='$kirow[komento]'$sel>$kirow[kirjoitin]</option>";
		}

		echo "</select></td>";
		echo "</tr>";

		if ($tulostin == 'Tyˆm‰‰r‰ys' and $kukarow['hinnat'] == 0) {

			echo "<tr><th rowspan='2'>".t("Tyˆm‰‰r‰ystyyppi")."</th><td>";
			echo "<select name='tyomtyyppi'>";

			$sel = array();
			$sel[$yhtiorow["tyomaaraystyyppi"]] = "selected";

			echo "<option value = 'A'>".t("Tuotetiedot, hinnat ja alennukset");
			echo "<option value = 'O' $sel[O]>".t("Tuotetiedot");
			echo "<option value = 'P' $sel[P]>".t("Tuotetiedot ja varastopaikka");
			echo "</select>";
			echo "</td></tr>";
		}

		if ($tulostin == 'Purkulista') {

			$colspanni = 3;

			if ($yhtiorow['suuntalavat'] == 'S' and $toim != 'REKLAMAATIO') {
				echo "<tr><th>",t("Valitse suuntalavat"),"</th><td>";

				while ($suuntalavat_row = mysql_fetch_assoc($suuntalavat_res)) {
					$ched = (in_array($suuntalavat_row["tunnus"], $valitut_suuntalavat)) ? "CHECKED" : "";

					echo "<input type='checkbox' name='suuntalavat[]' value='{$suuntalavat_row['tunnus']}' $ched> {$suuntalavat_row['sscc']}<br />";
				}

				echo "</td></tr>";

				$colspanni = 4;
			}

			echo "<tr><th rowspan='$colspanni'>".t("J‰rjestele purkulista")."</th>";

			if ($yhtiorow['suuntalavat'] == 'S' and $toim != 'REKLAMAATIO') {
				echo "<td><input type='radio' name='sorttaus' value='indeksi' checked='checked' />",t("hyllypaikan indeksin mukaan"),"</td></tr><tr>";
				$checked = '';
			}
			else {
				$checked = " checked='checked'";
			}

			echo "<td><input type='radio' name='sorttaus' value='tuote'> ".t("tuotenumeron mukaan")."</td></tr>";

			echo "<tr><td><input type='radio' name='sorttaus' value='toim_tuote' {$checked}> ".t("toimittajan tuotenumeron mukaan")."</td></tr>";
			echo "<tr><td><input type='radio' name='sorttaus' value='paikka'> ".t("varastopaikan mukaan")."</td></tr>";

			if ($yhtiorow['viivakoodi_purkulistaan'] == 'K') {
				$span = 4;
			}
			else {
				$span = 3;
			}

			echo "<tr><th rowspan='$span'>".t("Tulostettavat rivit")."</th><td><input type='radio' name='mitkarivit' value='kaikki'> ".t("kaikki rivit")."</td></tr>";
			echo "<tr><td><input type='radio' name='mitkarivit' value='viematta' CHECKED> ".t("saldoille viem‰tt‰ olevat rivit")."</td></tr>";
			echo "<tr><td><input type='radio' name='mitkarivit' value='viedyt'> ".t("saldoille viedyt rivit")."</td></tr>";
			if ($yhtiorow['viivakoodi_purkulistaan'] == 'K') {
				echo "<tr><td><input type='radio' name='mitkarivit' value='koodittomat'> ".t("Viivakoodittomat rivit")."</td></tr>";
			}
		}
	}

	if (isset($laskurow) and $laskurow['kirjoitin'] == "edi") {
		echo "<tr><td colspan='2' class='back'><font class='error'>".t("HUOM: EDI-tiedosto on jo kerran l‰hetetty toimittajalle")."!</font></td><tr>";
	}

	echo "</table><br><input type='submit' name='tulosta' value='".t("Tulosta")."'></form>";

	if (strpos($_SERVER['SCRIPT_NAME'], "keikka.php") === FALSE) {
		//lopetetaan t‰h‰n, muuten esim rivit.inc nollaa kukarow[kesken] muuttujan ja moni muukin asia toimisi kovin heikosti...
		require("inc/footer.inc");
		exit;
	}
?>