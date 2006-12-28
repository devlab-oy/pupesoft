<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "css") {
		$ulos = "<td><textarea cols='55' rows='7' name='css'>$trow[$i]</textarea><input type='hidden' name='cssi' value='$i'></td>";
		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == "css_pieni") {
		$ulos = "<td><textarea cols='55' rows='7' name='css_pieni'>$trow[$i]</textarea><input type='hidden' name='csspienii' value='$i'></td>";
		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == "css_extranet") {
		$ulos = "<td><textarea cols='55' rows='7' name='css_extranet'>$trow[$i]</textarea><input type='hidden' name='cssextraneti' value='$i'></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "verkkolasku_lah") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == 'iban+soap') {
			$sel1 = "selected";
		}
		if ($trow[$i] == 'ovt+soap') {
			$sel2 = "selected";
		}
		else {
			$sel = "selected";
		}

		$ulos .= "<option value = '' $sel>".t("eInvoice")."";
		$ulos .= "<option value = 'iban+soap' $sel1>".t("Finvoice + soap IBAN-tunnuksella")."";
		$ulos .= "<option value = 'ovt+soap' $sel2>".t("Finvoice + soap ovttunnuksella")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lahetteen_tulostustapa") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == 'A') {
			$sel1 = "selected";
		}
		if ($trow[$i] == 'K') {
			$sel2 = "selected";
		}

		$ulos .= "<option value = 'A' $sel1>".t("Lähetteet tulostetaan automaattisesti")."";
		$ulos .= "<option value = 'K' $sel2>".t("Lähetteet tulostetaan käsin")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "pankkitiedostot") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == '') {
			$sel1 = "selected";
		}
		if ($trow[$i] == 'E') {
			$sel2 = "selected";
		}

		$ulos .= "<option value = '' $sel1>".t("Pankit ja päivät yhdistetään")."";
		$ulos .= "<option value = 'E' $sel2>".t("Jokainen pankki ja päivä omaan tiedostoon")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laskutyyppi") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == '0') {
			$sel1 = "selected";
		}
		if ($trow[$i] == '1') {
			$sel2 = "selected";
		}

		$ulos .= "<option value = '0' $sel1>".t("Normaali lasku")."";
		$ulos .= "<option value = '1' $sel2>".t("Pelkistetty lasku")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rahti_hinnoittelu") {

		$ulos = "<td><select name='$nimi'>";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value='' $sel1>".t("Rahtikulu muodostuu painon perusteella")."</option>";
		$ulos .= "<option value='o' $sel2>".t("Rahtikulu muodostuu tilauksen hinnan perusteella")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "alv_kasittely") {

		$ulos = "<td><select name='$nimi'>";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value='' $sel1>".t("Tuotteiden myyntihinnat sisältävät arvonlisäveron")."</option>";
		$ulos .= "<option value='o' $sel2>".t("Tuotteiden myyntihinnat ovat arvonlisäverottomia")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakashinta_netto") {

		$ulos = "<td><select name='$nimi'>";

		$sel1="";
		$sel2="";
		$sel3="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "E") {
			$sel2 = "selected";
		}
		elseif ($trow[$i] == "B") {
			$sel3 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Asiakashinnoille ei anneta mitään alennuksia")."</option>";
		$ulos .= "<option value='E' $sel2>".t("Asiakashinnoille annetaan vain erikoisalennus")."</option>";
		$ulos .= "<option value='B' $sel3>".t("Asiakashinnoille annetaan kaikki alennukset")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "puute_jt_oletus") {

		$ulos = "<td><select name='$nimi'>";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "J") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Jos varastosaldo ei riitä, tilausrivi merkataan puuteriviksi")."</option>";
		$ulos .= "<option value='J' $sel2>".t("Jos varastosaldo ei riitä, tilausrivi merkataan jälkitoimitukseksi")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "puute_jt_kerataanko") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";
		$sel4 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "P") {
			$sel2 = "selected";
		}
		elseif ($trow[$i] == "J") {
			$sel3 = "selected";
		}
		elseif ($trow[$i] == "Q") {
			$sel4 = "selected";
		}

		$ulos .= "<option value = ''  $sel1>".t("Puuterivejä ja JT-rivejä EI kerätä")."</option>";
		$ulos .= "<option value = 'P' $sel2>".t("Puuterivit kerätään")."</option>";
		$ulos .= "<option value = 'J' $sel3>".t("JT-rivit kerätään")."</option>";
		$ulos .= "<option value = 'Q' $sel4>".t("Puuterivit ja JT-rivit kerätään")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kerayslistojen_yhdistaminen") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Asiakkaan tilauksia EI yhdistetä yhdelle keräyslistalle")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Asiakkaan tilaukset yhdistetään yhdelle keräyslistalle jos mahdollista")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "karayksesta_rahtikirjasyottoon") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Keräyksestä ei siirrytä suoraan rahtikirjan syöttöön")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Keräyksestä siirrytään suoraan rahtikirjan syöttöön")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rahtikirjojen_esisyotto") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "M") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Ei rahtikirjojen esisyöttöä")."</option>";
		$ulos .= "<option value='M' $sel2>".t("Rahtikirjojen esisyöttö mahdollista myynnin yhteydessä")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tee_osto_myyntitilaukselta") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Myyntitilaukselta ei voi luoda ostorivejä")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Myyntitilaukselta voi luoda ostorivejä ja ostotilaus")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kerataanko_jos_vain_puute_jt") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";
		$sel4 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "P") {
			$sel2 = "selected";
		}
		elseif ($trow[$i] == "J") {
			$sel3 = "selected";
		}
		elseif ($trow[$i] == "Q") {
			$sel4 = "selected";
		}

		$ulos .= "<option value = ''  $sel1>".t("Tilausta EI kerätä jos listalla on vain Puuterivejä JA/TAI JT-rivejä")."</option>";
		$ulos .= "<option value = 'P' $sel2>".t("Tilaus kerätään jos listalla on vain Puuterivejä")."</option>";
		$ulos .= "<option value = 'J' $sel3>".t("Tilaus kerätään jos listalla on vain JT-rivejä")."</option>";
		$ulos .= "<option value = 'Q' $sel4>".t("Tilaus kerätään jos listalla on vain Puuterivejä JA/TAI JT-rivejä")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jt_automatiikka") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("JT-rivejä ei lisätä automaattisesti")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("JT-rivejä lisätään automaattisesti")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "naytetaan_katteet_tilauksella") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Katetta ei näytetä tilauksentekovaiheessa")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Kate näytetään tilauksentekovaiheessa")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laskunsummapyoristys") {

		$ulos = "<td><select name='$nimi'>";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value='' $sel1>".t("Laskun loppusummaa ei pyöristetä")."</option>";
		$ulos .= "<option value='o' $sel2>".t("Laskun loppusumma pyöristetään lahimpään kokonaislukuun")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "suoratoim_automaatio") {

		$ulos = "<td><select name='$nimi'>";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value='' $sel1>".t("Riviä ei automaattisesti myydä konserniyhtiön varastosta")."</option>";
		$ulos .= "<option value='o' $sel2>".t("Jos oma saldo ei riitä rivi yritetään myydä konserniyhtiön varastosta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "oletus_toimitusehto") {

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'TOIMEHTO'
					ORDER BY jarjestys, selite";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei oletustoimitusehtoa")."</option>";

		while ($kirow=mysql_fetch_array($kires)) {
			if ($kirow["selite"]==$trow[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[selite]' $select>$kirow[selitetark]</option>";
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "oletus_toimitusehto2") {

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'TOIMEHTO'
					ORDER BY jarjestys, selite";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei oletustoimitusehtoa vaihtokohteille")."</option>";

		while ($kirow=mysql_fetch_array($kires)) {
			if ($kirow["selite"]==$trow[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[selite]' $select>$kirow[selitetark]</option>";
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kerailylistatyyppi") {

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'KERAYSLISTA'
					ORDER BY jarjestys, selite";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while ($kirow=mysql_fetch_array($kires)) {
			if ($kirow["selite"]==$trow[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[selite]' $select>$kirow[selitetark]</option>";
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lasku_tulostin") {

		$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' ORDER BY kirjoitin";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei kirjoitinta")."</option>";

		while ($kirow=mysql_fetch_array($kires))
		{
			if ($kirow["tunnus"]==$trow[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laskutuskielto") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == '0') {
			$sel1 = "selected";
		}
		if ($trow[$i] == '1') {
			$sel2 = "selected";
		}

		$ulos .= "<option value = '0' $sel1>".t("Ei käytetä laskutuskieltoa")."";
		$ulos .= "<option value = '1' $sel2>".t("Käytetään laskutuskieltoa")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jyvita_alennus") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == 'T') {
			$sel1 = "selected";
		}
		if ($trow[$i] == 'P') {
			$sel2 = "selected";
		}
		else {
			$sel = "selected";
		}

		$ulos .= "<option value = '' $sel>".t("Alennus jyvitetään tilausriveille")."";
		$ulos .= "<option value = 'T' $sel1>".t("Alennus jyvitetään aina tuotteella")."";
		$ulos .= "<option value = 'P' $sel2>".t("Alennus jyvitetään Projektilla tuotteella")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "salli_jyvitys_myynnissa") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == 'K') {
			$sel1 = "selected";
		}
		if ($trow[$i] == 'V') {
			$sel2 = "selected";
		}
		else {
			$sel = "selected";
		}

		$ulos .= "<option value = '' $sel>".t("Kassamyyjä saa hyvittää myyntitilauksella")."";
		$ulos .= "<option value = 'K' $sel1>".t("Kaikki saa hyvittää myyntitilauksella")."";
		$ulos .= "<option value = 'V' $sel2>".t("Vain valitut käyttäjät saa hyvittää myyntitilauksella")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "sad_lomake_tyyppi") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == 'T') {
			$sel2 = "selected";
		}
		else {
			$sel1 = "selected";
		}

		$ulos .= "<option value = 'P' $sel1>".t("SAD-lomake pdf-muodossa")."";
		$ulos .= "<option value = 'T' $sel2>".t("SAD-lomake tekstimuodossa")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}


?>
