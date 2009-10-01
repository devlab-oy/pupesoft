<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
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
		<input type='hidden' name='sorttauskentan_jarjestys4' value='$sorttauskentan_jarjestys4'>";

	if ($nappikeikalle == 'menossa') {
		echo "<input type='hidden' name='nappikeikalla' value='ollaan'>";
	}

	echo "<table>";

	if ($toim == "LASKU") {
		echo "<tr><td>".t("Tulosta laskusta käteiskuitti")."</td><td><input type='checkbox' name='kateiskuitti'></td></tr>";
		echo "<tr><td>".t("Tulosta kassa-alet")."</td><td><input type='checkbox' name='kassaalet'></td></tr>";
	}

	$mista = 'asiakas';

	if ($toim == "OSTO" or $tulostimet[0] == "Ostotilaus") {
		echo "<tr><td>".t("Tulosta nimitykset ostotilaukselle")."</td><td><input type='checkbox' name='nimitykset'></td></tr>";
		$mista = 'toimi';
	}

	if ($toim == "LAHETE" and $yhtiorow["lahete_tyyppi_tulostus"] != '' and $otunnus != '') {
		echo "<tr><td>".t("Tulosta")."</td><td><select name='sellahetetyyppi'>";

		$query2 = "	SELECT lahetetyyppi
					FROM lasku
					JOIN asiakas on lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus
					WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = '$otunnus'";
		$vresult2 = mysql_query($query2) or pupe_error($query2);
		$row2 = mysql_fetch_array($vresult2);

		$vresult = t_avainsana("LAHETETYYPPI");

		while($row = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($row["selite"] == $row2["lahetetyyppi"]) $sel = 'selected';

			echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
		}

		echo "</select></td></tr>";

		$mista = 'asiakas';
	}

	if ($toim == "TILAUSVAHVISTUS" and $yhtiorow["tilausvahvistus_tyyppi_tulostus"] != '' and $otunnus != '') {

		$query2 = "	SELECT tilausvahvistus
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$otunnus'";
		$vresult2 = mysql_query($query2) or pupe_error($query2);
		$row2 = mysql_fetch_array($vresult2);

		$vresult = t_avainsana("TV", "", " and avainsana.selite like '%K%'");

		echo "<tr><td>".t("Tulosta")."</td><td><select name='seltvtyyppi'>";
		echo "<option value=''>".t("Oletus")."</option>";

		while($row = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($row["selite"] == $row2["tilausvahvistus"]) $sel = 'selected';

			echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
		}

		echo "</select></td></tr>";
	}

	echo "<tr><td>".t("Kpl")."</td><td><input type='text' name='kappaleet' size='3'></td></tr>";

	echo "<tr><td>".t("Valitse kieli")."</td>";

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
		$resultkieli = mysql_query($querykieli) or pupe_error($querykieli);
		$asrow = mysql_fetch_array($resultkieli);

		$querykieli = "	SELECT kieli, email
						FROM $mista
						WHERE
						tunnus = '$asrow[liitostunnus]'
						AND yhtio ='$kukarow[yhtio]'";
		$resultkieli = mysql_query($querykieli) or pupe_error($querykieli);
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
	$fields =  mysql_query($query);

	echo "<td><select name='kieli'>";

	while ($apurow = mysql_fetch_array($fields)) {
		if (strlen($apurow[0]) == 2) {
			$query = "SELECT distinct nimi from maat where koodi='$apurow[0]'";
			$maare = mysql_query($query);

			$maaro = mysql_fetch_array($maare);

			$maa   = strtolower($maaro["nimi"]);
			if ($maa=="") $maa = $apurow[0];

			$makkoodi = $apurow[0];

			echo "<option value='$makkoodi' $sel[$makkoodi]>".t("$maa")."</option>";
		}
	}
	echo "</select></td></tr>";

	foreach($tulostimet as $tulostin) {
		echo "<tr><td>".t("Valitse")." '".t("$tulostin")."' ".t("tulostuspaikka")."</td>";
		echo "<td><select name='komento[$tulostin]'>";
		echo "<option value=''>".t("Ei kirjoitinta")."</option>";
		echo "<option value='PDF_RUUDULLE'>".t('PDF Ruudulle')."</option>";

		if (($toim == 'TARJOUS' or $toim == 'MYYNTISOPIMUS') and $asrow['email'] != '') {
			echo "<option value='asiakasemail$asrow[email]'>".t("Asiakkaan sähköpostiin").": $asrow[email]</option>";
		}

		$querykieli = "	SELECT *
						from kirjoittimet
						where yhtio='$kukarow[yhtio]'
						ORDER BY kirjoitin";
		$kires = mysql_query($querykieli) or pupe_error($querykieli);

		while ($kirow=mysql_fetch_array($kires)) {

			$sel = "";
			if ($kirow["tunnus"] == $kukarow["kirjoitin"]) {
				$sel = "SELECTED";
			}

			echo "<option value='$kirow[komento]'$sel>$kirow[kirjoitin]</option>";
		}

		echo "</select></td>";
		echo "</tr>";

		if ($tulostin == 'Työmääräys' and $kukarow['hinnat'] == 0) {

			if ($yhtiorow["tyomaaraystyyppi"] == "") {
				$seltt = "CHECKED";
			}
			else {
				$seltp = "CHECKED";
			}

			echo "<tr><td rowspan='2'>".t("Työmääräystyyppi")."</td><td><input type='radio' name='tyomtyyppi' value='N' $seltt> ".t("näytetään hinnat")."</td></tr>";
			echo "<tr><td><input type='radio' name='tyomtyyppi' value='P' $seltp> ".t("hintoja ei näytetä")."</td></tr>";
		}

		if ($tulostin == 'Purkulista') {
			echo "<tr><td rowspan='3'>".t("Järjestele purkulista")."</td><td><input type='radio' name='sorttaus' value='tuote'> ".t("tuotenumeron mukaan")."</td></tr>";
			echo "<tr><td><input type='radio' name='sorttaus' value='toim_tuote' CHECKED> ".t("toimittajan tuotenumeron mukaan")."</td></tr>";
			echo "<tr><td><input type='radio' name='sorttaus' value='paikka'> ".t("varastopaikan mukaan")."</td></tr>";

			if ($yhtiorow['viivakoodi_purkulistaan'] == 'K') {
				$span = 4;
			}
			else {
				$span = 3;
			}

			echo "<tr><td rowspan='$span'>".t("Tulostettavat rivit")."</td><td><input type='radio' name='mitkarivit' value='kaikki'> ".t("kaikki rivit")."</td></tr>";
			echo "<tr><td><input type='radio' name='mitkarivit' value='viematta' CHECKED> ".t("saldoille viemättä olevat rivit")."</td></tr>";
			echo "<tr><td><input type='radio' name='mitkarivit' value='viedyt'> ".t("saldoille viedyt rivit")."</td></tr>";
			if ($yhtiorow['viivakoodi_purkulistaan'] == 'K') {
				echo "<tr><td><input type='radio' name='mitkarivit' value='koodittomat'> ".t("Viivakoodittomat rivit")."</td></tr>";
			}
		}
	}

	echo "</table><br><input type='submit' name='tulosta' value='".t("Tulosta")."'></form>";

	//lopetetaan tähän, muuten esim rivit.inc nollaa kukarow[kesken] muuttujan ja moni muukin asia toimisi kovin heikosti...
	require("footer.inc");
	exit;
?>