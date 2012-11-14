<?php

// lasku taulussa k‰ytet‰‰n kentti‰ seuraavasti:
//
// rahti			= kuluprosentti
// rahti_etu		= eturahti
// rahti_huolinta	= kulusumma (jos lˆytyy niin ohittaa kuluprosentin)

if ($tee == "update") {

	$query = "SELECT * from lasku WHERE tunnus='$otunnus' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array ($result);

	if (!isset($kaikkivarastossayhteensa)) $kaikkivarastossayhteensa = 0;

	list (,,,,,,,,$llrow,,,,,,,) = tsekit($laskurow, $kaikkivarastossayhteensa);

	// Tsekki tehd‰‰n vain jos vaihto-omaisuus laskuja on liitetty keikkaan!
	if ($llrow["volasku"] > 0 and abs($rahti_etu) > abs($llrow['vosumma_valuutassa'])) {
		echo "<font class='message'>".t("VIRHE: Eturahti ei saa olla suurempi kuin laskujen yhteissumma")."!</font><br><br>";

		$tee = "";
		$toiminto = "lisatiedot";

		if ($nappikeikalla == 'ollaan') {
			$nappikeikalle = 'menossa';
			$nappikeikalla = '';
		}
	}
}

if ($tee == "update") {
	if ($laskurow["erikoisale"] == $erikoisale and $laskurow["rahti_etu"] == $rahti_etu and $laskurow["rahti_etu_alv"] == $rahti_etu_alv) {
		$kohdistettu = $laskurow["kohdistettu"];
	}
	else {
		$kohdistettu = "";
	}

	if ($nappikeikalla != 'ollaan') {
		echo "<font class='message'>".t("Muutit eturahtia ja/tai erikoisalennusta, kohdistus pit‰‰ tarkastaa uudelleen.")."</font><br><br>";
	}
	else {
		echo "<font class='message'>".t("Muutit eturahtia ja/tai erikoisalennusta, muistathan painaa kohdistus valmis, jotta muutokset huomioidaan.")."<br></font>";
	}

	// Hoidetaan pilkut pisteiksi....
	$rahti_etu 		= str_replace ( ",", ".", $rahti_etu);
	$rahti 			= str_replace ( ",", ".", $rahti);
	$rahti_huolinta = str_replace ( ",", ".", $rahti_huolinta);
	$erikoisale 	= str_replace ( ",", ".", $erikoisale);
	$bruttopaino 	= str_replace ( ",", ".", $bruttopaino);

	$toimaika 		= $vv."-".$kk."-".$pp;
	$ultilno 		= tarvitaanko_intrastat($maa_lahetys, $maa_maara);

	$query = "	UPDATE lasku
				SET maa_lahetys = '$maa_lahetys',
				maa_maara 		= '$maa_maara',
				kauppatapahtuman_luonne = '$ktapahtuman_luonne',
				kuljetusmuoto 	= '$kuljetusmuoto',
				bruttopaino 	= '$bruttopaino',
				rahti_huolinta 	= '$rahti_huolinta',
				rahti_etu 		= '$rahti_etu',
				rahti_etu_alv 	= '$rahti_etu_alv',
				rahti 			= '$rahti',
				erikoisale 		= '$erikoisale',
				toimaika 		= '$toimaika',
				kohdistettu 	= '$kohdistettu',
				comments	 	= '$kommentti',
				ultilno 		= '$ultilno'
				WHERE tunnus = '$otunnus' and yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	$query_erikoisale = "	UPDATE tilausrivi
							SET erikoisale = '{$erikoisale}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND uusiotunnus = '{$otunnus}'
							AND netto  != 'N'
							AND tyyppi != 'D'";
	$upd_erikoisale_res = pupe_query($query_erikoisale);

	$toiminto = "";
}

if ($tee == "") {

	$query = "SELECT *
			  FROM lasku
			  WHERE tunnus = '$otunnus' and yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array ($result);

	if (!isset($kaikkivarastossayhteensa)) $kaikkivarastossayhteensa = 0;

	list (,,,,,,,,$llrow,,,,,,,) = tsekit($laskurow, $kaikkivarastossayhteensa);

	// haetaan toimittajan tiedot
	$query  = "SELECT * FROM toimi WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$laskurow[liitostunnus]'";
	$result = mysql_query($query) or pupe_error($query);
	$toimittaja = mysql_fetch_array($result);

	// katotaan paljonko on kohdistamatta
	$jaljella = sprintf("%.2f", $llrow["voverosumma_valuutassa"] - $laskurow["summa"]);

	// kaunistellaan
	if ($llrow["voverosumma_valuutassa"] == "") $llrow["voverosumma_valuutassa"] = "0.00";

	// n‰ytet‰‰n viel‰ laskun tiedot, ettei kohdisteta p‰in berberi‰
	echo "<br><table>";
	echo "<tr>";
	echo "<th>".t("saapuminen")."</th>";
	echo "<th>".t("ytunnus")."</th>";
	echo "<th>".t("nimi")."</th>";
	echo "<th>".t("tapvm")."</th>";
	echo "<th>".t("toimitusehto")."</th>";
	echo "<th>".t("ostolaskut")."</th>";
	echo "<th>".t("kohdistettu")."</th>";
	echo "<th>".t("j‰ljell‰")."</th>";
	echo "</tr>";
	echo "<tr>
			<td>$laskurow[laskunro]</td>
			<td>$laskurow[ytunnus]</td>
			<td>$laskurow[nimi]</td>
			<td>$laskurow[tapvm]</td>
			<td>$laskurow[toimitusehto]</td>
			<td>$llrow[voverosumma_valuutassa] $laskurow[valkoodi]</td>
			<td>$laskurow[summa] $laskurow[valkoodi]</td>
			<td>$jaljella $laskurow[valkoodi]</td></tr>";
	echo "</table><br>";

	echo "<table>";
	echo "<form method='post'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "	<input type='hidden' name='toiminto' value='lisatiedot'>
			<input type='hidden' name='otunnus' value='$otunnus'>
			<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>
			<input type='hidden' name='tee' value='update'>";

	if ($nappikeikalle == 'menossa') {
		echo "<input type='hidden' name='nappikeikalla' value='ollaan'>";
	}

	echo "<tr><th>".t("L‰hetysmaa").":</th><td colspan='3'>
			<select name='maa_lahetys'>";

	$query = "	SELECT distinct koodi, nimi
				FROM maat
				where nimi != ''
				ORDER BY koodi";
	$result = mysql_query($query) or pupe_error($query);
	echo "<option value=''>".t("Valitse")."</option>";

	while($row = mysql_fetch_array($result)) {
		$sel = '';

		if ($row['koodi'] == $laskurow["maa_lahetys"]) {
			$sel = 'selected';
		}
		elseif ($laskurow["maa_lahetys"] == "" and $row['koodi'] == $toimittaja["maa_lahetys"]) {
			$sel = "selected";
		}
		elseif ($laskurow["maa_lahetys"] == "" and $toimittaja['maa_lahetys'] == '' and $row['koodi'] == $toimittaja['maa']) {
			$sel = "selected";
		}

		echo "<option value='{$row['koodi']}' $sel>{$row['nimi']}</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	echo "	<tr><th>".t("M‰‰r‰maan koodi").":</th>
			<td colspan='3'><select name='maa_maara'>";

	$query = "	SELECT distinct koodi, nimi
				FROM maat
				where nimi != ''
				ORDER BY koodi";
	$result = mysql_query($query) or pupe_error($query);

	if ($laskurow["maa_maara"] == "") {
		$query = "	SELECT varastopaikat.maa, count(*) kpl
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.uusiotunnus = lasku.tunnus)
					JOIN varastopaikat ON (varastopaikat.yhtio = lasku.yhtio
						AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
						AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0')))
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tunnus = '$laskurow[tunnus]'
					GROUP BY varastopaikat.maa
					ORDER BY kpl DESC
					LIMIT 1";
		$maaresult = mysql_query($query) or pupe_error($query);

		if ($maarow = mysql_fetch_array($maaresult)) {
			$laskurow["maa_maara"] = $maarow["maa"];
		}
		else {
			$laskurow["maa_maara"] = $yhtiorow["maa"];
		}
	}

	while ($row = mysql_fetch_array($result)) {
		$sel = '';
		if ($row['koodi'] == $laskurow["maa_maara"]) {
			$sel = 'selected';
		}
		echo "<option value='{$row['koodi']}' $sel>{$row['nimi']}</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	if ($laskurow["tuontipvm"] == '0000-00-00') {
		$pp = date('d');
		$kk = date('m');
		$vv = date('Y');
		$laskurow["tuontipvm"] = $vv."-".$kk."-".$pp;
	}

	echo "<tr><th>".t("Kauppatapahtuman luonne").":</th><td colspan='3'><select name='ktapahtuman_luonne'>";
	echo "<option value=''>".t("Valitse")."</option>";

	$result = t_avainsana("KT");

	while($row = mysql_fetch_array($result)){
		$sel = '';
		if($row["selite"] == $laskurow["kauppatapahtuman_luonne"]) {
			$sel = 'selected';
		}
		elseif($laskurow["kauppatapahtuman_luonne"] == "0" and $row["selite"] == $toimittaja["kauppatapahtuman_luonne"]) {
			$sel = "selected";
		}
		echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	echo "<tr><th>".t("Kuljetusmuoto").":</th><td colspan='3'>
				<select NAME='kuljetusmuoto'>";
	echo "<option value=''>".t("Valitse")."</option>";

	$result = t_avainsana("KM");

	while($row = mysql_fetch_array($result)){
		$sel = '';
		if($row["selite"] == $laskurow["kuljetusmuoto"]) {
			$sel = 'selected';
		}
		elseif($laskurow["kuljetusmuoto"] == "0" and $row["selite"] == $toimittaja["kuljetusmuoto"]) {
			$sel = "selected";
		}
		echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	if ($laskurow["rahti"] == 0) {
		// katotaan onko toimittajalla joku defaultti kuluprosentti
		$laskurow["rahti"] = $toimittaja["oletus_kulupros"];
	}

	if ($laskurow["bruttopaino"] == 0) {
		$query  = "	SELECT round(sum(tuotemassa*(varattu+kpl)),2) massa, sum(varattu+kpl) kpl, sum(if(tuotemassa!=0, varattu+kpl, 0)) kplok
					FROM tilausrivi
					JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.uusiotunnus = '$laskurow[tunnus]'";
		$painoresult = mysql_query($query) or pupe_error($query);
		$painorow = mysql_fetch_array($painoresult);

		if ($painorow["kpl"] > 0) {
			$osumapros = round($painorow["kplok"] / $painorow["kpl"] * 100, 2);
		}
		else {
			$osumapros = "N/A";
		}

		$laskurow["bruttopaino"] = $painorow["massa"];

		$lisamassa = "<font class='message'>".sprintf(t("Tilauksen paino tuoterekisterin tietojen mukaan on: %s kg, %s %%:lle kappaleista on annettu paino."),$painorow["massa"],$osumapros)."</font><br>";
	}

	if (abs($laskurow['rahti_etu']) > abs($llrow['vosumma_valuutassa'])) {
		$lisatiedot = "<font class='message'>".t("VIRHE: Eturahti ei saa olla suurempi kuin laskujen yhteissumma")."!</font><br>";
	}

	echo "<tr><th valign='top'>".t("Nettopaino").":</th><td valign='top' colspan='3'><input type='text' name='bruttopaino' value='$laskurow[bruttopaino]'></td><td valign='top' class='back' width='450'>$lisamassa</td></tr>";
	echo "<tr><th valign='top'>".t("Veroton")." ".t("Eturahti").":</th><td valign='top'><input type='text' name='rahti_etu' value='$laskurow[rahti_etu]'></td><td colspan='2' valign='top' nowrap>".t("Eturahdin ALV").": ".alv_popup('rahti_etu_alv', $laskurow["rahti_etu_alv"])."</td><td class='back'> $lisatiedot </td></tr>";
	echo "<tr><th valign='top'>".t("Arvioitu kuluprosentti").":</th><td valign='top' colspan='3'><input type='text' name='rahti' value='$laskurow[rahti]'> n.nn %</td></tr>";
	echo "<tr><th valign='top'>".t("Kulusumma")." $yhtiorow[valkoodi]:</th><td valign='top' colspan='3'><input type='text' name='rahti_huolinta' value='$laskurow[rahti_huolinta]'> ".t("ohittaa kuluprosentin")."</td></tr>";
	echo "<tr><th valign='top'>".t("Erikoisalennus").":</th><td valign='top' colspan='3'><input type='text' name='erikoisale' value='$laskurow[erikoisale]'></td></tr>";

	if ($laskurow["toimaika"] != '0000-00-00') {
		$vv = substr($laskurow["toimaika"],0,4);
		$kk = substr($laskurow["toimaika"],5,2);
		$pp = substr($laskurow["toimaika"],8,2);
	}
	else {
		$vv = date("Y");
		$kk = date("m");
		$pp = date("d");
	}

	echo "<tr><th>".t("Toimitusaika").":</th>
			<td>".t("pp")." <input type='text' name='pp' value='$pp' size='3'></td>
			<td>".t("kk")." <input type='text' name='kk' value='$kk' size='3'></td>
			<td>".t("vvvv")." <input type='text' name='vv' value='$vv' size='5'></td></tr>";

	echo "<tr><th>".t("Kommentti").":</th><td colspan='3'><input type='text' name='kommentti' size='40' value='$laskurow[comments]'></td></tr>";

	echo "</table>";


	echo "<br><input type='submit' value='".t("P‰ivit‰ tiedot")."'>";
	echo "</form>";
}

?>