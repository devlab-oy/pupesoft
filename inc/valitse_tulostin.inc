<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	echo "<form method='post' action='$PHP_SELF'>
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
		<input type='hidden' name='tilausnumero' value='$tilausnumero'>
		<input type='hidden' name='saldot' value='$saldot'>
		<input type='hidden' name='toimas' value='$toimas'>
		<input type='hidden' name='ei_inventointi' value='$ei_inventointi'>
		<input type='hidden' name='prosmuutos' value='$prosmuutos'>
		<input type='hidden' name='kplmuutos' value='$kplmuutos'>
		<input type='hidden' name='tuotemerkki' value='$tuotemerkki'>
		";

	echo "<table>";
	if ($toim == "LASKU") {
		echo "<tr><td>".t("Tulosta laskusta käteiskuitti")."</td><td><input type='checkbox' name='kateiskuitti'></td></tr>";
		echo "<tr><td>".t("Tulosta kassa-alet")."</td><td><input type='checkbox' name='kassaalet'></td></tr>";
	}
	if ($toim == "OSTO" or $tulostimet[0] == "Ostotilaus") {
		echo "<tr><td>".t("Tulosta nimitykset ostotilaukselle")."</td><td><input type='checkbox' name='nimitykset'></td></tr>";
		$mista = 'toimi';
	}
    else {
        $mista = 'asiakas';
    }
	echo "<tr><td>".t("Kpl")."</td><td><input type='text' name='kappaleet' size='3'></td></tr>";

	echo "<tr><td>".t("Valitse kieli")."</td>";
	///* Haetaan asiakkaan kieli *///
	if ($otunnus != '' or $laskunro != '') {
		$querykieli = "	SELECT ytunnus
						FROM lasku WHERE";

		if ($otunnus > 0) {
			$querykieli .= " tunnus='$otunnus' ";
		}
		if ($laskunro > 0 and $toim != "OSTO") {
			if ($otunnus > 0) {
				$querykieli .= " and laskunro='$laskunro' ";
			}
			else {
				$querykieli .= " tila='U' and laskunro='$laskunro' ";
			}
		}
		$querykieli .= " AND yhtio ='$kukarow[yhtio]'";

		$resultkieli = mysql_query($querykieli) or pupe_error($querykieli);
		$asrow = mysql_fetch_array($resultkieli);

		$querykieli = "	SELECT kieli, email
						FROM $mista
						WHERE
						ytunnus='$asrow[ytunnus]'
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
		if ($apurow[0] != "tunnus" and $apurow[0] != "aikaleima" and $apurow[0] != "kysytty") {
			$query = "select distinct nimi from maat where koodi='$apurow[0]'";
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
		
		if ($toim == 'TARJOUS' and $asrow['email'] != '') {
			echo "<option value='asiakasemail:$asrow[email]'>".t("Asiakkaan sähköpostiin")."</option>";
		}
		
		$querykieli = "	select *
					from kirjoittimet
					where yhtio='$kukarow[yhtio]'
					ORDER BY kirjoitin";
		$kires = mysql_query($querykieli) or pupe_error($querykieli);

		while ($kirow=mysql_fetch_array($kires)) {
			echo "<option value='$kirow[komento]'>$kirow[kirjoitin]</option>";
		}

		echo "</select></td>";
		echo "</tr>";

		if ($tulostin == 'Purkulista') {
			echo "<tr><td rowspan='3'>".t("Järjestele purkulista")."</td><td><input type='radio' name='sorttaus' value='tuote'> ".t("tuotenumeron mukaan")."</td></tr>";
			echo "<tr><td><input type='radio' name='sorttaus' value='toim_tuote' CHECKED> ".t("toimittajan tuotenumeron mukaan")."</td></tr>";
			echo "<tr><td><input type='radio' name='sorttaus' value='paikka'> ".t("varastopaikan mukaan")."</td></tr>";

			echo "<tr><td rowspan='3'>".t("Tulostettavat rivit")."</td><td><input type='radio' name='mitkarivit' value='kaikki'> ".t("kaikki rivit")."</td></tr>";
			echo "<tr><td><input type='radio' name='mitkarivit' value='viematta' CHECKED> ".t("saldoille viemättä olevat rivit")."</td></tr>";
			echo "<tr><td><input type='radio' name='mitkarivit' value='viedyt'> ".t("saldoille viedyt rivit")."</td></tr>";
		}

	}



	echo "</table><br><input type='submit' value='".t("Tulosta")."'></form>";
	//lopetetaan tähän, muuten esim rivit.inc nollaa kukarow[kesken] muuttujan ja moni muukin asia toimisi kovin heikosti...
	require("footer.inc");
	exit;
?>