<?php

// lasku taulussa k‰ytet‰‰n kentti‰ seuraavasti:
//
// rahti			= kuluprosentti
// rahti_etu		= eturahti
// rahti_huolinta	= kulusumma (jos lˆytyy niin ohittaa kuluprosentin)

if ($tee == "update") {

	$query = "select * from lasku WHERE tunnus='$otunnus' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array ($result);

	if ($laskurow["erikoisale"] == $erikoisale and $laskurow["rahti_etu"] == $rahti_etu) {
		$kohdistettu = $laskurow["kohdistettu"];
	}
	else {
		echo "<font class='message'>".t("Muutit eturahtia ja/tai erikoisalennusta, kohdistus pit‰‰ tarkastaa uudelleen.")."</font><br><br>";
		$kohdistettu = "";
	}

	// Hoidetaan pilkut pisteiksi....
	$rahti_etu = str_replace ( ",", ".", $rahti_etu);
	$rahti = str_replace ( ",", ".", $rahti);
	$rahti_huolinta = str_replace ( ",", ".", $rahti_huolinta);
	$erikoisale = str_replace ( ",", ".", $erikoisale);
	$toimaika = $vv."-".$kk."-".$pp;

	//Jos k‰ytt‰j‰ on syˆtt‰nyt alvillisen hinnan niin poistetaan alvi
	if ((int) $alv != 0) {
		$rahti_etu = round($rahti_etu/(1+($alv/100)),2);
	}

	$ultilno = $laskurow["ultilno"];

	// kokeillaan arpoa intrastat k‰sittely‰, molemmat maat pit‰‰ olla EU maita
	if ($maa_lahetys != "" and $maa_maara != "") {

		$query = "select distinct koodi from maat where koodi in ('$maa_lahetys','$maa_maara') and eu = 'ON'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 2) {
			if ($maa_lahetys == $yhtiorow["maakoodi"] and $maa_maara != $yhtiorow["maakoodi"]) {
				$ultilno = "-1"; // miinus yks tarkoittaa, ett‰ lis‰tiedot pit‰‰ syˆtt‰‰ ja VIENTI-intrastat pit‰‰ l‰hett‰‰
			}
			elseif ($maa_maara == $yhtiorow["maakoodi"] and $maa_lahetys != $yhtiorow["maakoodi"]) {
				$ultilno = "-2"; // miinus kaks tarkoittaa, ett‰ lis‰tiedot pit‰‰ syˆtt‰‰ ja TUONTI-intrastat pit‰‰ l‰hett‰‰
			}
			else {
				$ultilno = "";
			}
		}

	}

	$query = "	UPDATE lasku
				SET maa_lahetys = '$maa_lahetys',
				maa_maara = '$maa_maara',
				kauppatapahtuman_luonne = '$ktapahtuman_luonne',
				kuljetusmuoto = '$kuljetusmuoto',
				bruttopaino = '$bruttopaino',
				rahti_huolinta = '$rahti_huolinta',
				rahti_etu = '$rahti_etu',
				rahti = '$rahti',
				erikoisale = '$erikoisale',
				toimaika = '$toimaika',
				kohdistettu = '$kohdistettu',
				comments = '$kommentti',
				ultilno = '$ultilno'
				WHERE tunnus='$otunnus' and yhtio='$kukarow[yhtio]'";

	$result = mysql_query($query) or pupe_error($query);

	$toiminto = "";
}

if ($tee == "") {

	$query = "SELECT *
			  FROM lasku
			  WHERE tunnus = '$otunnus' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array ($result);

	// lasketaan keikan ostolaskujen summat yhteen
	$query = "	select sum(summa) vosumma
				from lasku
				where yhtio='$kukarow[yhtio]'
				and tila='K'
				and vanhatunnus<>0
				and laskunro='$laskurow[laskunro]'
				and vienti in ('C','F','I','J','K','L')";
	$llres = mysql_query($query) or pupe_error($query);
	$llrow = mysql_fetch_array($llres);

	// katotaan paljonko on kohdistamatta
	$jaljella = sprintf("%.2f", $llrow["vosumma"] - $laskurow["summa"]);

	// kaunistellaan
	if ($llrow["vosumma"] == "") $llrow["vosumma"] = "0.00";

	// n‰ytet‰‰n viel‰ laskun tiedot, ettei kohdisteta p‰in berberi‰
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("keikka")."</th>";
	echo "<th>".t("ytunnus")."</th>";
	echo "<th>".t("nimi")."</th>";
	echo "<th>".t("tapvm")."</th>";
	echo "<th>".t("toimitusehto")."</th>";
	echo "<th>".t("ostolaskut")."</th>";
	echo "<th>".t("kohdistettu")."</th>";
	echo "<th>".t("j‰ljell‰")."</th>";
	echo "</tr>";
	echo "<tr><td>$laskurow[laskunro]</td><td>$laskurow[ytunnus]</td><td>$laskurow[nimi]</td><td>$laskurow[tapvm]</td><td>$laskurow[toimitusehto]</td><td>$llrow[vosumma] $laskurow[valkoodi]</td><td>$laskurow[summa] $laskurow[valkoodi]</td><td>$jaljella $laskurow[valkoodi]</td></tr>";
	echo "</table><br>";

	echo "<table>";
	echo "<form action = '$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "	<input type='hidden' name='toiminto' value='lisatiedot'>
			<input type='hidden' name='otunnus' value='$otunnus'>
			<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>
			<input type='hidden' name='tee' value='update'>";
	echo "<tr><td>".t("L‰hetysmaa").":</td><td colspan='3'>
			<select name='maa_lahetys'>";

	$query = "	SELECT distinct koodi, nimi
				FROM maat
				where nimi != ''
				ORDER BY koodi";
	$result = mysql_query($query) or pupe_error($query);
	echo "<option value=''>".t("Valitse")."</option>";

	while($row = mysql_fetch_array($result)){
		$sel = '';
		if($row[0] == $laskurow["maa_lahetys"]) {
			$sel = 'selected';
		}
		echo "<option value='$row[0]' $sel>$row[1]</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	echo "	<tr><td>".t("M‰‰r‰maan koodi").":</td>
			<td colspan='3'><select name='maa_maara'>";

	$query = "	SELECT distinct koodi, nimi
				FROM maat
				where nimi != ''
				ORDER BY koodi";
	$result = mysql_query($query) or pupe_error($query);

	if ($laskurow["maa_maara"] == "") $laskurow["maa_maara"] = $yhtiorow["maakoodi"];

	while ($row = mysql_fetch_array($result)) {
		$sel = '';
		if($row[0] == $laskurow["maa_maara"]) {
			$sel = 'selected';
		}
		echo "<option value='$row[0]' $sel>$row[1]</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	if ($laskurow["tuontipvm"] == '0000-00-00') {
		$pp = date('d');
		$kk = date('m');
		$vv = date('Y');
		$laskurow["tuontipvm"] = $vv."-".$kk."-".$pp;
	}

	$query  = "select * from toimi where yhtio='$kukarow[yhtio]' and ytunnus='$laskurow[ytunnus]'";
	$result = mysql_query($query) or pupe_error($query);
	$toimittaja = mysql_fetch_array($result);

	echo "<tr><td>".t("Kauppatapahtuman luonne").":</td><td colspan='3'>
		<select NAME='ktapahtuman_luonne'>";
	echo "<option value=''>".t("Valitse")."</option>";

	$query = "	SELECT avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','KT_')."
				WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji='KT'
				ORDER BY avainsana.jarjestys, avainsana.selite";
	$result = mysql_query($query) or pupe_error($query);

	while($row = mysql_fetch_array($result)){
		$sel = '';
		if($row[0] == $laskurow["kauppatapahtuman_luonne"]) {
			$sel = 'selected';
		}
		elseif($laskurow["kauppatapahtuman_luonne"] == "0" and $row[0] == $toimittaja["kauppatapahtuman_luonne"]) {
			$sel = "selected";
		}
		echo "<option value='$row[0]' $sel>$row[1]</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	echo "<tr><td>".t("Kuljetusmuoto").":</td><td colspan='3'>
				<select NAME='kuljetusmuoto'>";
	echo "<option value=''>".t("Valitse")."</option>";

	$query = "	SELECT avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','KM_')."
				WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji='KM'
				ORDER BY avainsana.jarjestys, avainsana.selite";
	$result = mysql_query($query) or pupe_error($query);

	while($row = mysql_fetch_array($result)){
		$sel = '';
		if($row[0] == $laskurow["kuljetusmuoto"]) {
			$sel = 'selected';
		}
		elseif($laskurow["kuljetusmuoto"] == "0" and $row[0] == $toimittaja["kuljetusmuoto"]) {
			$sel = "selected";
		}
		echo "<option value='$row[0]' $sel>$row[1]</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	if ($laskurow["rahti"] == 0) {
		// katotaan onko toimittajalla joku defaultti kuluprosentti
		$laskurow["rahti"] = $toimittaja["oletus_kulupros"];
	}

	//Haetaan alvifunktio
	require ('../inc/alvpopup.inc');

	echo "<tr><td>".t("Nettopaino").":</td><td colspan='3'><input type='text' name='bruttopaino' value='$laskurow[bruttopaino]'></td></tr>";
	echo "<tr><td>".t("Eturahti").":</td><td><input type='text' name='rahti_etu' value='$laskurow[rahti_etu]'></td><td>".t("ALV").": ".alv_popup_oletus('alv',0)."</td><td> ".t("vaihto-omaisuuslaskun valuutassa")."</td></tr>";
	echo "<tr><td>".t("Arvioitu kuluprosentti").":</td><td colspan='3'><input type='text' name='rahti' value='$laskurow[rahti]'> n.nn %</td></tr>";
	echo "<tr><td>".t("Kulusumma")." $yhtiorow[valkoodi]:</td><td colspan='3'><input type='text' name='rahti_huolinta' value='$laskurow[rahti_huolinta]'> ".t("ohittaa kuluprosentin")."</td></tr>";
	echo "<tr><td>".t("Erikoisalennus").":</td><td colspan='3'><input type='text' name='erikoisale' value='$laskurow[erikoisale]'></td></tr>";

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

	echo "<tr><td>".t("Toimitusaika").":</td>
			<td>".t("pp")." <input type='text' name='pp' value='$pp' size='3'></td>
			<td>".t("kk")." <input type='text' name='kk' value='$kk' size='3'></td>
			<td>".t("vvvv")." <input type='text' name='vv' value='$vv' size='5'></td></tr>";

	echo "<tr><td>".t("Kommentti").":</td><td colspan='3'><input type='text' name='kommentti' size='40' value='$laskurow[comments]'></td></tr>";

	echo "</table>";


	echo "<br><input type='submit' value='".t("P‰ivit‰ tiedot")."'>";
	echo "</form>";
}

?>