<?php

// tarvitaan $kukarow, $tuoteno ja $tee jossa on paalle, puolipaalle tai pois

if ($tuoteno != "") {

	$query = "SELECT tuote.tunnus, tuote.tuoteno, kehahin, epakurantti1pvm, epakurantti2pvm, sum(saldo) saldo
			FROM tuote
			LEFT JOIN tuotepaikat ON tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno
			WHERE tuote.yhtio = '$kukarow[yhtio]' and tuote.tuoteno = '$tuoteno'
			GROUP by 1,2,3,4,5";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 1) {
		echo "<font class='error'>";
		printf (t("Tuote %s ei lˆydy!"), $tuoteno);
		echo "</font><br>";
		$tee='';
	}
	$tuoterow = mysql_fetch_array($result);

	// lasketaan varastonarvo
	$muutos = $tuoterow['saldo'] * $tuoterow['kehahin'];

	// otetaan 'oikea' arvo talteen ihan vaan kaunistelua varten
	$apu2 = $muutos;
	if     ($tuoterow['epakurantti2pvm'] != '0000-00-00')	$apu = 0;
	elseif ($tuoterow['epakurantti1pvm'] != '0000-00-00')	$apu = $muutos / 2;
	else													$apu = $muutos;

	// jos kyseess‰ on puoliep‰kurantti niin puolitetaan
	if (($tuoterow['epakurantti1pvm'] != '0000-00-00') and ($tuoterow['epakurantti2pvm'] == '0000-00-00')) $muutos = $muutos / 2;

}

if ($tee == "paalle" or $tee == "puolipaalle" or $tee == "pois") {

	// jos ollaan merkkaamassa puoli- tai t‰ysep‰kurantiksi k‰‰nnet‰‰n etumerkki
	if ($tee == 'paalle')      $muutos = $muutos * -1;
	if ($tee == 'puolipaalle') $muutos = $muutos / 2 * -1;

	if ($tee == 'pois' or $tee == 'paalle' or $tee == 'puolipaalle') {

		// katotaan onko tilauksessa
		$query = "	SELECT sum(varattu) varattu
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
					WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoterow[tuoteno]' and varattu>0 and tyyppi='O'";
		$tilre = mysql_query($query) or pupe_error($query);
		$tilro = mysql_fetch_array($tilre);

		if ($tilro["varattu"] != 0) {
			echo "<font class='error'>".t("Tuotetta on tilauksessa!")." ".t("Ei voida laittaa ep‰kurantiksi!")."</font><br>";
			$tee = "";
		}

		// katotaan onko liitetty keikkaan jonka virallista varastonarvoa ei ole laskettu
		$query = "	SELECT laskunro
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
					JOIN lasku on lasku.yhtio = tilausrivi.yhtio and
					lasku.tunnus = tilausrivi.uusiotunnus and
					lasku.tila ='K' and
					lasku.alatila != 'X' and
					lasku.kohdistettu != 'X'
					WHERE tilausrivi.yhtio='$kukarow[yhtio]' and
					tuoteno='$tuoterow[tuoteno]' and varattu=0 and tyyppi='O'";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre) != 0) {
			$tilro = mysql_fetch_array($tilre);
			echo "<font class='error'>".t("Tuotetta on viem‰tt‰ varastoon keikalla")." $tilro[laskunro]. ".t("Ei voida laittaa ep‰kurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida aktivoida tuotetta kurantiksi jos se on jo kurantti
		if (($tee == 'pois')        and ($tuoterow['epakurantti1pvm'] == '0000-00-00') and ($tuoterow['epakurantti2pvm'] == '0000-00-00')) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote v‰‰r‰ss‰ tilassa!")." ".t("Ei voida laittaa kurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa puoliep‰kurantiksi jos tuote on puoliep‰kurantti tai t‰ysep‰kurantti
		if (($tee == 'puolipaalle') and (($tuoterow['epakurantti1pvm'] != '0000-00-00') or ($tuoterow['epakurantti2pvm'] != '0000-00-00'))) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote v‰‰r‰ss‰ tilassa!")." ".t("Ei voida laittaa puoliep‰kurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa t‰ysep‰kurantiksi jos tuote on jo t‰ysep‰kurantti
		if (($tee == 'paalle')      and ($tuoterow['epakurantti2pvm'] != '0000-00-00')) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote v‰‰r‰ss‰ tilassa!")." ".t("Ei voida laittaa ep‰kurantiksi!")."</font><br>";
			$tee = "";
		}

		if ($tee == 'pois') { ///* P‰ivitet‰‰n tuote kurantiksi *///
			$query = "	UPDATE tuote set
						epakurantti1pvm = '0000-00-00',
						epakurantti2pvm = '0000-00-00'
						WHERE
						yhtio  = '$kukarow[yhtio]' and
						tuoteno = '$tuoterow[tuoteno]'";
			$result = mysql_query($query) or pupe_error($query);
		}

		if ($tee == 'paalle') { ///* P‰ivitet‰‰n tuote t‰ysep‰kurantiksi *///

			if ($tuoterow['epakurantti1pvm'] == '0000-00-00') { // jos tuote ei ole puoliep‰kurantti, p‰ivitet‰‰n sama p‰iv‰ t‰nnekkin
				$lisa = " epakurantti1pvm = now(), ";
			}
			else {
				$lisa = "";
			}

			$query = "	UPDATE tuote set
						$lisa
						epakurantti2pvm = now()
						WHERE
						yhtio  = '$kukarow[yhtio]' and
						tuoteno = '$tuoterow[tuoteno]'";
			$result = mysql_query($query) or pupe_error($query);
		}

		if ($tee == 'puolipaalle') { ///* P‰ivitet‰‰n tuote puoliep‰kurantiksi *///
			$query = "	UPDATE tuote set
						epakurantti1pvm = now(),
						epakurantti2pvm = '0000-00-00'
						WHERE
						yhtio  = '$kukarow[yhtio]' and
						tuoteno = '$tuoterow[tuoteno]'";
			$result = mysql_query($query) or pupe_error($query);
		}

		// jos ollaan laittamassa tuotetta puoliep‰kurantiksi, puoliep‰kuranttia t‰ysep‰kurantiksi tai laittamassa puoliep‰kuranttia kurantiki puolitetaan kehahin
		if ($tee == 'puolipaalle') $tuoterow["kehahin"] = $tuoterow["kehahin"] / 2;
		if ($tee == 'paalle' and $tuoterow['epakurantti1pvm'] != '0000-00-00') $tuoterow["kehahin"] = $tuoterow["kehahin"] / 2;
		if ($tee == 'pois' and $tuoterow['epakurantti2pvm'] == '0000-00-00') $tuoterow["kehahin"] = $tuoterow["kehahin"] / 2;

		// jos ollaan laittamassa tuotetta ep‰kurantiksi niin k‰‰nnet‰‰n saldon etumerkki
		if ($tee == 'paalle' or $tee == 'puolipaalle') $tuoterow["saldo"] = $tuoterow["saldo"] * -1;

		// jos meill‰ on viel‰ t‰‰ll‰ tee muuttuja, ni voidaan teh‰ tapahtuma
		if ($tee != "") {

			///* Tehd‰‰n tapahtuma *///
			$query = "	INSERT into tapahtuma set
						yhtio   = '$kukarow[yhtio]',
						tuoteno = '$tuoterow[tuoteno]',
						laji    = 'Ep‰kurantti',
						kpl     = '$tuoterow[saldo]',
						hinta   = '$tuoterow[kehahin]',
						kplhinta= '$tuoterow[kehahin]',
						selite  = '$tee. ".t("Keskihankintahinta").": $tuoterow[kehahin] ".t("Saldo").": $tuoterow[saldo] -> $muutos',
						laatija    = '$kukarow[kuka]',
						laadittu = now()";
			$result = mysql_query($query) or pupe_error($query);

			///* Kirjanpito *///
			if ($muutos <> 0) {

				$query = "	INSERT into lasku set
							yhtio      = '$kukarow[yhtio]',
							tapvm      = now(),
							tila       = 'X',
							laatija    = '$kukarow[kuka]',
							luontiaika = now()";

				$result = mysql_query($query) or pupe_error($query);
				$laskuid = mysql_insert_id($link);

				$query = "INSERT into tiliointi set
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$yhtiorow[varasto]',
							kustp    = '',
							tapvm    = now(),
							summa    = '$muutos',
							vero     = '0',
							lukko    = '',
							selite   = 'Tuote $tuoterow[tuoteno] ep‰kurantttimuutos',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);

				$query = "INSERT into tiliointi set
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$yhtiorow[varastonmuutos]',
							kustp    = '',
							tapvm    = now(),
							summa    = $muutos * -1,
							vero     = '0',
							lukko    = '',
							selite   = 'Tuote $tuoterow[tuoteno] ep‰kuranttimuutos',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);
			}

			echo "<font class='message'>";
			echo t("Tuote")." $tuoteno ok!<br>";
			echo t("Varastonarvoa muutettiin")." $muutos<br>";
			echo "</font><br>";

		}
	}
}

?>