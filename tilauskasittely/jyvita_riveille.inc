<?php

/*
	Tänne tarvitaan:
	$jysum joka on loppusumma joka tilaukselle halutaan
	$tilausnumero

	Jos jyvsumma on negatiivinen on se alennuksen määrä
	Jos jyvsumma sisältää % merkin annetaan erikoisalennusta

	$yhtiorow["jyvita_alennus"]
		''  = Alennus jokaisen rivin hintaan
		'P' = Alennus annetaan tuotteella vain projektilla
		'T' = Alennus annetaan tuotteella aina
*/

if (strpos($jysum, "%") !== false) {
	$jysum = str_replace("%","", $jysum);
	$anna_ea = "TOKI";
}

$jysum = round(str_replace(",",".", $jysum), 2);
$jyvitetaan_ristiin = "";

//Lasketaan tilauksen arvot
if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
	$hinta_riv = "(tilausrivi.hinta/$laskurow[vienti_kurssi])";
	$hinta_riv2 = "(tilausrivi.hinta/$laskurow[vienti_kurssi])";
}
else {
	$hinta_riv = "tilausrivi.hinta";
	$hinta_riv2 = "tilausrivi.hinta";
}

// jos radiobutton verottomat hinnat on päällä ja yhtion parametri verolliset myyntihinnat
// eli otetaan verot pois
if ($tilausrivi_alvillisuus == 'E' and $yhtiorow['alv_kasittely'] == '') {
	$hinta_riv .= " / (1 + (tilausrivi.alv / 100)) ";
	$jyvitetaan_ristiin = "X";
}
//jos radiobutton verolliset hinnat on päällä ja yhtion parametri verottomat myyntihinnat
// eli lisätään verot
elseif ($tilausrivi_alvillisuus != 'E' and $yhtiorow['alv_kasittely'] != '') {
	$hinta_riv .= " * (1 + (tilausrivi.alv / 100)) ";
	$jyvitetaan_ristiin = "X";
}

$query_ale_lisa = generoi_alekentta('M');

$query = "	SELECT
			sum($hinta_riv * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) koko_summa,
			sum($hinta_riv2 * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) koko_oma_summa,
			sum(if(varattu+jt>0, $hinta_riv * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) plus_summa,
			sum(if(varattu+jt<0, $hinta_riv * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) nega_summa
			FROM tilausrivi
			WHERE yhtio = '$kukarow[yhtio]'
			and otunnus = '$tilausnumero'
			and rivihinta = 0
			and uusiotunnus = 0";
$result = mysql_query($query) or pupe_error($query);
$tarow = mysql_fetch_array($result);

$plus_summa = $tarow["plus_summa"];
$nega_summa = $tarow["nega_summa"];

if ($anna_ea == "TOKI" and ($jysum < 0 or $jysum >= 100)) {
	echo "<font class='error'>".t("Erikoisalennus tulee olla")." 0 - 100%!!</font><br>";
}
elseif ($jysum == 0) {
	if ($yhtiorow["jyvita_alennus"] == "") {
		// Nollataan jyvitys
		$query = "UPDATE lasku SET hinta = '0' WHERE tunnus = '$kukarow[kesken]' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$laskurow["hinta"] = 0;
	}
	else {
		echo "<font class='error'>".t("Jyvitettävä summa ei saa olla nolla")."!</font><br>";
	}
}
elseif ($jysum < 0 and $nega_summa >= 0) {
	echo "<font class='error'>".t("Et voi jyvittää summaa negatiiviseksi jos sinulla ei ole hyvitysrivejä")."!</font><br>";
}
elseif ($jysum > 0 and $plus_summa <= 0) {
	echo "<font class='error'>".t("Et voi jyvittää summaa positiiviseksi jos sinulla ei ole veloitusrivejä")."!</font><br>";
}
else {

	if ($anna_ea == "TOKI") {
		// 	Annetaan erikoisale
		$query = "UPDATE lasku SET erikoisale = '$jysum' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero' and tilaustyyppi = 'N' and tila IN ('V','N','G','L') and alatila NOT IN ('V','X')";
		$upresult = mysql_query($query) or pupe_error($query);

		$laskurow["erikoisale"] = $jysum;
	}
	elseif ($yhtiorow["jyvita_alennus"] == "T" or ($yhtiorow["jyvita_alennus"] == "P" and $toim == "PROJEKTI")) {
		// 	Alennus lisätään alennustuotteelle
		//	TODO tämä on osittain turhaa koska projektia ei ole vielä köytössä, kohta on!
		$query = "SELECT * FROM tuote WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$yhtiorow[alennus_tuotenumero]'";
		$rhire = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($rhire) == 1) {
			$trow  = mysql_fetch_array($rhire);

			$hinta		 = round(($plus_summa+$nega_summa - $jysum), $yhtiorow['hintapyoristys']);
			$trow["alv"] = $laskurow["alv"];
			$nimitys	 = $trow["nimitys"];

			list($hinta, $alv) = alv($laskurow, $trow, $hinta, '', '');

			if ($laskurow["tila"] == "T") {
				$rivityyppi = "T";
			}
			else {
				$rivityyppi = "L";
			}

			$query  = "INSERT INTO tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) VALUES (now(), '$hinta', 'N', '-1', '-1', '$tilausnumero', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', '$rivityyppi', '$alv', '')";
			$addtil = mysql_query($query) or pupe_error($query);
		}
		else {
			echo t("VIRHE: Alennusta ei voitu antaa alennustuote puuttuu")."!<br>";
		}
	}
	else {

		// Jyvitetään riveille uusihinta ja tallennetaan syötetty summa lasku.hinta-kenttään
		// huomioidaan hyvitysrivit ja ei muuteta niitten hintoja

		$boob_jyva = 0;

		$ale_query_select_lisa = generoi_alekentta_select('erikseen', 'M');

		if ($jysum < 0 and $nega_summa != 0) {
			// jos jyvityssumma on miinusta ja meillä on hyvitysrivejä, kosketaan vain hyvitysriveihin
			$halu_summa = $jysum - $plus_summa;
			$jypro = $halu_summa / $nega_summa;

			// Rullataan tilausrivejä
			$query = "	SELECT tunnus, tuoteno, hinta, {$ale_query_select_lisa} tilkpl
						FROM tilausrivi
						WHERE yhtio 	 = '$kukarow[yhtio]'
						and varattu + jt < 0
						and otunnus 	 = '$tilausnumero'
						and rivihinta 	 = 0
						and uusiotunnus  = 0
						ORDER BY hinta";
		}
		elseif ($jysum > 0 and $plus_summa != 0) {
			// jos jyvityssumma on plussaa ja meillä on veloitusrivejä, kosketaan vain veloitusriveihin
			$halu_summa = $jysum - $nega_summa;
			$jypro = $halu_summa / $plus_summa;

			// Rullataan tilausrivejä
			$query = "	SELECT tunnus, tuoteno, hinta, {$ale_query_select_lisa} tilkpl
						FROM tilausrivi
						WHERE yhtio 	 = '$kukarow[yhtio]'
						and varattu + jt > 0
						and otunnus 	 = '$tilausnumero'
						and rivihinta 	 = 0
						and uusiotunnus  = 0
						ORDER BY hinta";
		}
		else {
			echo "<font class='error'>".t("Jyvitys epäonnistui")."!</font><br>";
			$boob_jyva = 1;
		}

		if ($boob_jyva == 0) {
			$result = mysql_query($query) or pupe_error($query);

			while ($tirow = mysql_fetch_array($result)) {
				$uushinta = round($tirow['hinta'] * $jypro, $yhtiorow['hintapyoristys']);

				$query = "UPDATE tilausrivi SET hinta = '$uushinta' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tirow[tunnus]'";
				$upresult = mysql_query($query) or pupe_error($query);
			}

			//  Päivitetään laskulle hinta-kenttään syötetty summa, mutta vain siinä tapauksessa, että syötetty summa on verollinen.
			if ($yhtiorow['alv_kasittely'] == '' or ($yhtiorow['alv_kasittely'] != '' and $jyvitetaan_ristiin == "X")) {
				$query = "UPDATE lasku SET hinta = '$jysum' WHERE tunnus = '$kukarow[kesken]' and yhtio = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);

				$laskurow["hinta"] = $jysum;
			}
		}
	}
}

$jysum	 = '';
$tila	 = '';
$tee	 = '';
$kiekat  = "";
$summa 	 = "";
$anna_ea = "";
$hinta	 = "";

?>