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

	if (mysql_field_name($result, $i) == "css_verkkokauppa") {
		$ulos = "<td><textarea cols='55' rows='7' name='css_verkkokauppa'>$trow[$i]</textarea><input type='hidden' name='cssverkkokauppa' value='$i'></td>";
		$jatko = 0;
	} 

	if (mysql_field_name($result, $i) == "verkkolasku_lah") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == 'finvoice') {
			$sel2 = "selected";
		}
		else {
			$sel1 = "selected";
		}

		$ulos .= "<option value = '' $sel1>".t("Pupevoice")."";
		$ulos .= "<option value = 'finvoice' $sel2>".t("Finvoice")."";
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

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = '0' $sel[0]>".t("Normaali laskupohja");
		$ulos .= "<option value = '2' $sel[2]>".t("Normaali laskupohja (Tuoteperheet yhdistetty)");		
		$ulos .= "<option value = '1' $sel[1]>".t("Pelkistetty laskupohja 1");
		$ulos .= "<option value = '3' $sel[3]>".t("Pelkistetty laskupohja 2 (Tuoteperheet yhdistetty)");		
		$ulos .= "<option value = '4' $sel[4]>".t("Pelkistetty laskupohja 3");		

		
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilausvahvistustyyppi") {

		$ulos = "<td><select name='$nimi'>";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = '0' $sel[0]>".t("Normaali tilausvahvistuspohja");
		$ulos .= "<option value = '3' $sel[3]>".t("Pelkistetty tilausvahvistuspohja 2 (Tuoteperheet yhdistetty)");

		
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ostotilaustyyppi") {

		$ulos = "<td><select name='$nimi'>";

		$apu = (int)$trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = '0' $sel[0]>".t("Normaali ostotilauspohja");
		$ulos .= "<option value = '1' $sel[1]>".t("Pelkistetty ostotilauspohja");		

		
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "tyomaaraystyyppi") {

		$ulos = "<td><select name='$nimi'>";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = ''>".t("Normaali työmääräyspohja");
		$ulos .= "<option value = 'P' $sel[P]>".t("Pelkistetty työmääräyspohja");		

		
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
		elseif ($trow[$i] == "H") {
			$sel3 = "selected";
		}
		elseif ($trow[$i] == "O") {
			$sel4 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Jos varastosaldo ei riitä, tilausrivi merkataan puuteriviksi")."</option>";
		$ulos .= "<option value='J' $sel2>".t("Jos varastosaldo ei riitä, tilausrivi merkataan jälkitoimitukseksi")."</option>";
		$ulos .= "<option value='H' $sel3>".t("Jos varastosaldo ei riitä, tilausrivi myydään normaalisti")."</option>";
		$ulos .= "<option value='O' $sel4>".t("Kaikki myydään aina oletuspaikalta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "puute_jt_kerataanko") {

		$ulos = "<td><select name='$nimi'>";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Puuterivejä ja JT-rivejä EI kerätä")."</option>";
		$ulos .= "<option value = 'P' $sel[P]>".t("Puuterivit kerätään")."</option>";
		$ulos .= "<option value = 'J' $sel[J]>".t("JT-rivit kerätään")."</option>";
		$ulos .= "<option value = 'Q' $sel[Q]>".t("Puuterivit ja JT-rivit kerätään")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "saldo_kasittely") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "T") {
			$sel2 = "selected";
		}
		

		$ulos .= "<option value = ''  $sel1>".t("Myytävissä-kpl ajasta riippumaton")."</option>";
		$ulos .= "<option value = 'T' $sel2>".t("Myytävissä-kpl lasketaan keräyspvm:n mukaan")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "kerataanko_saldottomat") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "S") {
			$sel2 = "selected";
		}
		

		$ulos .= "<option value = ''  $sel1>".t("Saldottomia tuotteita ei kerätä")."</option>";
		$ulos .= "<option value = 'S' $sel2>".t("Saldottomat tuotteet kerätään")."</option>";
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

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos .= "<option value=''>".t("Myyntitilaukselta ei voi luoda ostorivejä")."</option>";
		$ulos .= "<option value='Y' $sel[Y]>".t("Myyntitilaukselta voi luoda ostorivejä asiakkaalle ja varastoon")."</option>";
		$ulos .= "<option value='V' $sel[V]>".t("Myyntitilaukselta voi luoda ostorivejä varastoon")."</option>";
		$ulos .= "<option value='Z' $sel[Z]>".t("Myyntitilaukselta voi luoda ostorivejä tai ostotilaus asiakkaalle ja varastoon")."</option>";
		$ulos .= "<option value='X' $sel[X]>".t("Myyntitilaukselta voi luoda ostorivejä tai ostotilaus varastoon")."</option>";
		
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "automaattinen_jt_toimitus") {

		$ulos = "<td><select name='$nimi'>";

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos .= "<option value=''>".t("Jälkitoimitusrivejä ei toimiteta eikä kohdisteta automaattisesti")."</option>";
		$ulos .= "<option value='K' $sel[K]>".t("Jälkitoimitusrivit kohdistetaan automaattisesti varastoonviennin yhteydessä")."</option>";
		$ulos .= "<option value='T' $sel[T]>".t("Jälkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydessä")."</option>";		
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kerataanko_jos_vain_puute_jt") {

		$ulos = "<td><select name='$nimi'>";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Tilausta EI kerätä jos listalla on vain Puuterivejä JA/TAI JT-rivejä")."</option>";
		$ulos .= "<option value = 'P' $sel[P]>".t("Tilaus kerätään jos listalla on vain Puuterivejä")."</option>";
		$ulos .= "<option value = 'J' $sel[J]>".t("Tilaus kerätään jos listalla on vain JT-rivejä")."</option>";
		$ulos .= "<option value = 'Q' $sel[Q]>".t("Tilaus kerätään jos listalla on vain Puuterivejä JA/TAI JT-rivejä")."</option>";
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

		$ulos .= "<option value=''  $sel1>".t("Tuotteen nimitystä ei voi muokata tilauksentekovaiheessa")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Tuotteen nimitystä voi muokata tilauksentekovaiheessa")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "hintapyoristys") {

		$ulos = "<td><select name='$nimi'>";

		$sel1="";
		$sel2="";
		$sel3="";

		if ($trow[$i] == "4") {
			$sel2 = "selected";
		}
		elseif ($trow[$i] == "6") {
			$sel3 = "selected";
		}
		else {
			$sel1 = "selected";
		}

		$ulos .= "<option value='2' $sel1>".t("Hinta pyöristetään kahteen desimaaliin")."</option>";
		$ulos .= "<option value='4' $sel2>".t("Hinta pyöristetään neljään desimaaliin")."</option>";
		$ulos .= "<option value='6' $sel3>".t("Hinta pyöristetään kuuteen desimaaliin")."</option>";
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

		$ulos .= "<option value='' $sel1>".t("Normaalimyyjä ei toimi kassamyyjänä")."</option>";
		$ulos .= "<option value='o' $sel2>".t("Normaalimyyjä voi toimia tarpeen mukaan kassamyyjänä")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "kerayspoikkeama_kasittely") {

		$ulos = "<td><select name='$nimi'>";

		$sel			= array();
		$sel[$trow[$i]]	= " selected";

		$ulos .= "<option value=''>".t("Keräyspoikkeamia ei käsitellä")."</option>";
		$ulos .= "<option value='o' $sel[o]>".t("Kerääjä päättää keräyspoikkeaman käsittelystä")."</option>";
		$ulos .= "<option value='J' $sel[J]>".t("Kerääjä päättää keräyspoikkeaman käsittelystä (Oletuksena JT)")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "kerayspoikkeamaviestin_lahetys") {

		$ulos = "<td><select name='$nimi'>";
		
		$sel		= array();
		$sel[$trow[$i]]	= " selected";

		$ulos .= "<option value='0' $sel[0]>".t("Keräyspoikkeamaviesti lähetetään asiakkaalle ja myyjälle")."</option>";
		$ulos .= "<option value='2' $sel[2]>".t("Keräyspoikkeamaviesti lähetetään vain myyjälle")."</option>";
		$ulos .= "<option value='1' $sel[1]>".t("Keräyspoikkeamaviestiä ei lähetetä ollenkaan")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "oletus_toimitusehto") {

		$query = "	SELECT concat(selite, ' ', selitetark) toimehto
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'TOIMEHTO'
					ORDER BY jarjestys, selite";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei oletustoimitusehtoa")."</option>";

		while ($kirow=mysql_fetch_array($kires)) {
			if ($kirow["toimehto"]==$trow[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[toimehto]' $select>$kirow[toimehto]</option>";
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "oletus_toimitusehto2") {

		$query = "	SELECT concat(selite, ' ', selitetark) toimehto
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'TOIMEHTO'
					ORDER BY jarjestys, selite";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei oletustoimitusehtoa vaihtokohteille")."</option>";

		while ($kirow=mysql_fetch_array($kires)) {
			if ($kirow["toimehto"]==$trow[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[toimehto]' $select>$kirow[toimehto]</option>";
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
	
	if (mysql_field_name($result, $i) == "splittauskielto") {

		$ulos = "<td><select name='$nimi'>";

		$sel = array($trow[$i] => "SELECTED");

		$ulos .= "<option value = ''>".t("Tilaukset splitataan varastoittain")."";
		$ulos .= "<option value = 'E' $sel[E]>".t("Tilauksia ei oletuksena splitata varastoittain")."";
		$ulos .= "<option value = 'K' $sel[K]>".t("Tilaukset splitataan keräyspäivien mukaan toimituksiin")."";
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

		$ulos .= "<option value = '' $sel>".t("Kassamyyjä saa jyvittää myyntitilauksella")."";
		$ulos .= "<option value = 'K' $sel1>".t("Kaikki saa jyvittää myyntitilauksella")."";
		$ulos .= "<option value = 'V' $sel2>".t("Vain valitut käyttäjät saa jyvittää myyntitilauksella")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	
	if (mysql_field_name($result, $i) == "rivinumero_syotto") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] != '') {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value = '' $sel>".t("Rivinumeoita ei voi syöttää tilaukselle")."";
		$ulos .= "<option value = 'K' $sel1>".t("Rivinumeoita voi syöttää tilaukselle")."";
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

	if (mysql_field_name($result, $i) == "lahetteen_jarjestys" or 
		mysql_field_name($result, $i) == "laskun_jarjestys" or 
		mysql_field_name($result, $i) == "tilauksen_jarjestys" or  
		mysql_field_name($result, $i) == "kerayslistan_jarjestys" or 
		mysql_field_name($result, $i) == "valmistus_kerayslistan_jarjestys" or
		mysql_field_name($result, $i) == "laskun_jarjestys" or
		mysql_field_name($result, $i) == "ostotilauksen_jarjestys") {

		$ulos = "<td><select name='$nimi'>";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = '0' $sel[0]>".t("Varastopaikkajärjestys, tuoteperheet pidetään yhdessä, erikoistuotteet loppuun");
		$ulos .= "<option value = '1' $sel[1]>".t("Varastopaikkajärjestys, tuoteperheet pidetään yhdessä");
		$ulos .= "<option value = '2' $sel[2]>".t("Varastopaikkajärjestys, erikoistuotteet loppuun");
		$ulos .= "<option value = '3' $sel[3]>".t("Varastopaikkajärjestys");
		$ulos .= "<option value = '4' $sel[4]>".t("Tuotenumerojärjestys, tuoteperheet pidetään yhdessä, erikoistuotteet loppuun");
		$ulos .= "<option value = '5' $sel[5]>".t("Tuotenumerojärjestys, tuoteperheet pidetään yhdessä");
		$ulos .= "<option value = '6' $sel[6]>".t("Tuotenumerojärjestys, erikoistuotteet loppuun");
		$ulos .= "<option value = '7' $sel[7]>".t("Tuotenumerojärjestys");
		$ulos .= "<option value = '8' $sel[8]>".t("Tilausjärjestys, tuoteperheet pidetään yhdessä");
		$ulos .= "<option value = '9' $sel[9]>".t("Tilausjärjestys");
		$ulos .= "<option value = 'M' {$sel["M"]}>".t("Käyttäjän asettama järjestys");		
		
		if(	mysql_field_name($result, $i) == "valmistus_kerayslistan_jarjestys" or
			mysql_field_name($result, $i) == "kerayslistan_jarjestys") {
			$ulos .= "<option value = 'S' {$sel["S"]}>".t("Rivit summattuina");	
		}
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "lahetteen_jarjestys_suunta" or 
		mysql_field_name($result, $i) == "laskun_jarjestys_suunta" or 
		mysql_field_name($result, $i) == "tilauksen_jarjestys_suunta" or  
		mysql_field_name($result, $i) == "kerayslistan_jarjestys_suunta" or 
		mysql_field_name($result, $i) == "valmistus_kerayslistan_jarjestys_suunta" or
		mysql_field_name($result, $i) == "laskun_jarjestys_suunta" or
		mysql_field_name($result, $i) == "ostotilauksen_jarjestys_suunta") {
	

		$ulos = "<td><select name='$nimi'>";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = 'ASC'  $sel[ASC]>".t("Nouseva");
		$ulos .= "<option value = 'DESC' $sel[DESC]>".t("Laskeva");
		
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "lahete_nouto_allekirjoitus") {

		$sela = "";
		$selb = "";

		if ($trow[$i] == 'K') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}
		
		$ulos = "<td><select name='$nimi'>
					<option value='' $sela>".t("Ei tulosteta allekirjoitussivua")."</option>
					<option value='K' $selb>".t("Tulostetaan allekirjoitussivu")."</option>		
				</select><td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "valmistusten_yhdistaminen") {

		$sela = "";
		$selb = "";

		if ($trow[$i] == 'K') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}
		
		$ulos = "<td><select name='$nimi'>
					<option value='' $sela>".t("Tuotteen yhdistetään normaalisti")."</option>
					<option value='P' $selb>".t("Myös tuoteperheet koitetaan yhdistää")."</option>		
				</select><td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tarjouksen_voimaika") {

		$ulos = "<td><input type='text' name='$nimi' value = '$trow[$i]' size='5'> (Päivää luontiajasta)</td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilauksen_yhteyshenkilot") {

		$sela = "";
		$selb = "";

		if ($trow[$i] == 'K') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}
		
		$ulos = "<td><select name='$nimi'>
					<option value='' $sela>".t("Ei yhteyshenkilöitä")."</option>
					<option value='K' $selb>".t("Tilaukselle saa liittää yhteyshenkilöitä")."</option>		
				</select><td>";
		

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilauksen_seuranta") {

		$sela = "";
		$selb = "";

		if ($trow[$i] == 'K') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}
		
		$ulos = "<td><select name='$nimi'>
					<option value='' $sela>".t("Ei tilauksen seurantaluokkia")."</option>
					<option value='K' $selb>".t("Tilaukset lajitellaan seurantaluokkiin")."</option>		
				</select><td>";
		

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilauksen_kohteet") {

		$sela = "";
		$selb = "";

		if ($trow[$i] == 'K') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}
		
		$ulos = "<td><select name='$nimi'>
					<option value='' $sela>".t("Tilauksille ei liitetä kohteita")."</option>
					<option value='K' $selb>".t("Tilaukset liitetään asiakkaan kohde ja tilausriville positio")."</option>
				</select><td>";
		

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tarjouksen_voi_versioida") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Tarjoksesta ei voi tehdä versioita")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Tarjouksesta voi tehdä versioita")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rekursiiviset_reseptit") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Resepteissä vain yksi taso")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Reseptit käsitellään rekursiivisesti")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "valmistustapa") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Normaali valmistus")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Yksinkertaisempi valmistus")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rahtikirjan_kopiomaara") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == 3) {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == 4) {
			$sel2 = "selected";
		}

		$ulos .= "<option value='3' $sel1>".t("Rahtikirjasta tulostetaan 3 kopiota.")."</option>";
		$ulos .= "<option value='4' $sel2>".t("Rahtikirjasta tulostetaan 4 kopiota.")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "vienti_erittelyn_tulostus") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "I") {
			$sel2 = "selected";
		}
		elseif ($trow[$i] == "E") {
			$sel3 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Vientierittely tulostetaan")."</option>";
		$ulos .= "<option value='I' $sel2>".t("Vientierittely tulostetaan ilman alkuperämaatietoja")."</option>";
		$ulos .= "<option value='E' $sel3>".t("Vientierittelyä ei tulosteta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "epakur_kehahin_paivitys") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] != 'X') {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value='' $sel1>".t("Epäkuranttiuspoisto EI MUUTA kehahintaa.")."</option>";
		$ulos .= "<option value='X' $sel2>".t("Epäkuranttiuspoisto MUUTTAA kehahintaa.")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "oletus_lahetekpl") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == 0) {
			$sel0 = "selected";
		}
		elseif ($trow[$i] == 1) {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == 2) {
			$sel2 = "selected";
		}
		elseif ($trow[$i] == 3) {
			$sel3 = "selected";
		}

		$ulos .= "<option value='0' $sel0>".t("Lähetteestä tulostetaan 0 kopiota.")."</option>";
		$ulos .= "<option value='1' $sel1>".t("Lähetteestä tulostetaan 1 kopiota.")."</option>";
		$ulos .= "<option value='2' $sel2>".t("Lähetteestä tulostetaan 2 kopiota.")."</option>";
		$ulos .= "<option value='3' $sel3>".t("Lähetteestä tulostetaan 3 kopiota.")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "oletus_oslappkpl") {

		$ulos = "<td><select name='$nimi'>";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == 0) {
			$sel0 = "selected";
		}
		elseif ($trow[$i] == 1) {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == 2) {
			$sel2 = "selected";
		}
		elseif ($trow[$i] == 3) {
			$sel3 = "selected";
		}

		$ulos .= "<option value='0' $sel0>".t("Osoitelapusta tulostetaan 0 kopiota.")."</option>";
		$ulos .= "<option value='1' $sel1>".t("Osoitelapusta tulostetaan 1 kopiota.")."</option>";
		$ulos .= "<option value='2' $sel2>".t("Osoitelapusta tulostetaan 2 kopiota.")."</option>";
		$ulos .= "<option value='3' $sel3>".t("Osoitelapusta tulostetaan 3 kopiota.")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "viitteen_kasinsyotto") {

		$ulos = "<td><select name='$nimi'>";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Viitettä ei saa syöttää käsin")."</option>";
		$ulos .= "<option value='X' $sel2>".t("Viitteen saa syöttää käsin")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "poistuvat_tuotteet") {
		
		$ulos = "<td><select name='$nimi'>";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Älä näytä poistuvia tuotteita oletuksena")."</option>";
		$ulos .= "<option value='X' $sel2>".t("Näytä poistuvat tuotteet oletuksena")."</option>";
		$ulos .= "</select></td>";
		
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "oslapp_rakir_logo") {
		
		$ulos = "<td><select name='$nimi'>";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Logoa ei tulosteta rahtikirjaan eikä osoitelappuun")."</option>";
		$ulos .= "<option value='X' $sel2>".t("Logo tulostetaan rahtikirjaan ja osoitelappuun")."</option>";
		$ulos .= "</select></td>";
		
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "ytunnus_tarkistukset") {

		$ulos = "<td><select name='$nimi'>";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Tarkistetaan kotimaiset toimittajat ja asiakkaat")."</option>";
		$ulos .= "<option value = 'T' $sel[T]>".t("Tarkistetaan vain kotimaiset toimittajat")."</option>";
		$ulos .= "<option value = 'A' $sel[A]>".t("Tarkistetaan vain kotimaiset asiakkaat")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Ei ytunnus tarkistuksia")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "rahti_ja_kasittelykulut_kasin") {

		$ulos = "<td><select name='$nimi'>";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Rahtikirjan syötössä ei syötetä rahti ja käsittelykuluja")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Rahtikirjan syötössä voi syöttää rahti ja käsittelykuluja")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "myyntitilaus_osatoimitus") {

		$ulos = "<td><select name='$nimi'>";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Myyntitilausta ei voi splitata käsin")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilauksen voi splitata käsin")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "myyntitilaus_asiakasmemo") {

		$ulos = "<td><select name='$nimi'>";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Myyntitilauksella ei näytetä asiakasmemoa")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilauksella näytetään asiakasmemo")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
?>
