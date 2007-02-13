<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys

	if ($i == 0) { //yhtiˆt‰ ei n‰ytet‰
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

		$ulos .= "<option value = 'A' $sel1>".t("L‰hetteet tulostetaan automaattisesti")."";
		$ulos .= "<option value = 'K' $sel2>".t("L‰hetteet tulostetaan k‰sin")."";
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

		$ulos .= "<option value = '' $sel1>".t("Pankit ja p‰iv‰t yhdistet‰‰n")."";
		$ulos .= "<option value = 'E' $sel2>".t("Jokainen pankki ja p‰iv‰ omaan tiedostoon")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laskutyyppi") {

		$ulos = "<td><select name='$nimi'>";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = '0' $sel[0]>".t("Normaali lasku");
		$ulos .= "<option value = '1' $sel[1]>".t("Pelkistetty lasku");
		$ulos .= "<option value = '2' $sel[2]>".t("Normaali lasku (Tuoteperheet yhdistetty)");
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

		$ulos .= "<option value='' $sel1>".t("Tuotteiden myyntihinnat sis‰lt‰v‰t arvonlis‰veron")."</option>";
		$ulos .= "<option value='o' $sel2>".t("Tuotteiden myyntihinnat ovat arvonlis‰verottomia")."</option>";
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

		$ulos .= "<option value=''  $sel1>".t("Asiakashinnoille ei anneta mit‰‰n alennuksia")."</option>";
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

		$ulos .= "<option value=''  $sel1>".t("Jos varastosaldo ei riit‰, tilausrivi merkataan puuteriviksi")."</option>";
		$ulos .= "<option value='J' $sel2>".t("Jos varastosaldo ei riit‰, tilausrivi merkataan j‰lkitoimitukseksi")."</option>";
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

		$ulos .= "<option value = ''  $sel1>".t("Puuterivej‰ ja JT-rivej‰ EI ker‰t‰")."</option>";
		$ulos .= "<option value = 'P' $sel2>".t("Puuterivit ker‰t‰‰n")."</option>";
		$ulos .= "<option value = 'J' $sel3>".t("JT-rivit ker‰t‰‰n")."</option>";
		$ulos .= "<option value = 'Q' $sel4>".t("Puuterivit ja JT-rivit ker‰t‰‰n")."</option>";
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

		$ulos .= "<option value=''  $sel1>".t("Asiakkaan tilauksia EI yhdistet‰ yhdelle ker‰yslistalle")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Asiakkaan tilaukset yhdistet‰‰n yhdelle ker‰yslistalle jos mahdollista")."</option>";
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

		$ulos .= "<option value=''  $sel1>".t("Ker‰yksest‰ ei siirryt‰ suoraan rahtikirjan syˆttˆˆn")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Ker‰yksest‰ siirryt‰‰n suoraan rahtikirjan syˆttˆˆn")."</option>";
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

		$ulos .= "<option value=''  $sel1>".t("Ei rahtikirjojen esisyˆttˆ‰")."</option>";
		$ulos .= "<option value='M' $sel2>".t("Rahtikirjojen esisyˆttˆ mahdollista myynnin yhteydess‰")."</option>";
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

		$ulos .= "<option value=''  $sel1>".t("Myyntitilaukselta ei voi luoda ostorivej‰")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Myyntitilaukselta voi luoda ostorivej‰ ja ostotilaus")."</option>";
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

		$ulos .= "<option value = ''  $sel1>".t("Tilausta EI ker‰t‰ jos listalla on vain Puuterivej‰ JA/TAI JT-rivej‰")."</option>";
		$ulos .= "<option value = 'P' $sel2>".t("Tilaus ker‰t‰‰n jos listalla on vain Puuterivej‰")."</option>";
		$ulos .= "<option value = 'J' $sel3>".t("Tilaus ker‰t‰‰n jos listalla on vain JT-rivej‰")."</option>";
		$ulos .= "<option value = 'Q' $sel4>".t("Tilaus ker‰t‰‰n jos listalla on vain Puuterivej‰ JA/TAI JT-rivej‰")."</option>";
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

		$ulos .= "<option value=''  $sel1>".t("JT-rivej‰ ei lis‰t‰ automaattisesti")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("JT-rivej‰ lis‰t‰‰n automaattisesti")."</option>";
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

		$ulos .= "<option value=''  $sel1>".t("Katetta ei n‰ytet‰ tilauksentekovaiheessa")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Kate n‰ytet‰‰n tilauksentekovaiheessa")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "nimityksen_muutos_tilauksella") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Tuotteen nimityst‰ ei voi muokata tilauksentekovaiheessa")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Tuotteen nimityst‰ voi muokata tilauksentekovaiheessa")."</option>";
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

		$ulos .= "<option value='' $sel1>".t("Laskun loppusummaa ei pyˆristet‰")."</option>";
		$ulos .= "<option value='o' $sel2>".t("Laskun loppusumma pyˆristet‰‰n lahimp‰‰n kokonaislukuun")."</option>";
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

		$ulos .= "<option value='' $sel1>".t("Rivi‰ ei automaattisesti myyd‰ konserniyhtiˆn varastosta")."</option>";
		$ulos .= "<option value='o' $sel2>".t("Jos oma saldo ei riit‰ rivi yritet‰‰n myyd‰ konserniyhtiˆn varastosta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "dynaaminen_kassamyynti") {

		$ulos = "<td><select name='$nimi'>";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value='' $sel1>".t("Normaalimyyj‰ ei toimi kassamyyj‰n‰")."</option>";
		$ulos .= "<option value='o' $sel2>".t("Normaalimyyj‰ voi toimia tarpeen mukaan kassamyyj‰n‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "kerayspoikkeama_kasittely") {

		$ulos = "<td><select name='$nimi'>";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value='' $sel1>".t("Ker‰yspoikkeamia ei k‰sitell‰")."</option>";
		$ulos .= "<option value='o' $sel2>".t("Ker‰‰j‰ p‰‰tt‰‰ ker‰yspoikkeaman k‰sittelyst‰")."</option>";
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

		$ulos .= "<option value = '0' $sel1>".t("Ei k‰ytet‰ laskutuskieltoa")."";
		$ulos .= "<option value = '1' $sel2>".t("K‰ytet‰‰n laskutuskieltoa")."";
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

		$ulos .= "<option value = '' $sel>".t("Alennus jyvitet‰‰n tilausriveille")."";
		$ulos .= "<option value = 'T' $sel1>".t("Alennus jyvitet‰‰n aina tuotteella")."";
		$ulos .= "<option value = 'P' $sel2>".t("Alennus jyvitet‰‰n Projektilla tuotteella")."";
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

		$ulos .= "<option value = '' $sel>".t("Kassamyyj‰ saa hyvitt‰‰ myyntitilauksella")."";
		$ulos .= "<option value = 'K' $sel1>".t("Kaikki saa hyvitt‰‰ myyntitilauksella")."";
		$ulos .= "<option value = 'V' $sel2>".t("Vain valitut k‰ytt‰j‰t saa hyvitt‰‰ myyntitilauksella")."";
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

	if (mysql_field_name($result, $i) == "lahetteen_jarjestys") {

		$ulos = "<td><select name='$nimi'>";

		$sela = $trow[$i];
		$sel = array();
		$sel[$sela] = "selected";

		$ulos .= "<option value = '0' $sel[0]>".t("Varastopaikkaj‰rjestys, tuoteperheet pidet‰‰n yhdess‰, erikoistuotteet loppuun");
		$ulos .= "<option value = '1' $sel[1]>".t("Varastopaikkaj‰rjestys, tuoteperheet pidet‰‰n yhdess‰");
		$ulos .= "<option value = '2' $sel[2]>".t("Varastopaikkaj‰rjestys, erikoistuotteet loppuun");
		$ulos .= "<option value = '3' $sel[3]>".t("Varastopaikkaj‰rjestys");
		$ulos .= "<option value = '4' $sel[4]>".t("Tuotenumeroj‰rjestys, tuoteperheet pidet‰‰n yhdess‰, erikoistuotteet loppuun");
		$ulos .= "<option value = '5' $sel[5]>".t("Tuotenumeroj‰rjestys, tuoteperheet pidet‰‰n yhdess‰");
		$ulos .= "<option value = '6' $sel[6]>".t("Tuotenumeroj‰rjestys, erikoistuotteet loppuun");
		$ulos .= "<option value = '7' $sel[7]>".t("Tuotenumeroj‰rjestys");
		$ulos .= "<option value = '8' $sel[8]>".t("Tilausj‰rjestys, tuoteperheet pidet‰‰n yhdess‰");
		$ulos .= "<option value = '9' $sel[9]>".t("Tilausj‰rjestys");

		$ulos .= "</select></td>";

		$jatko = 0;
	}


?>
