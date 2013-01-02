<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys

	if ($i == 0) { //yhtiˆt‰ ei n‰ytet‰
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "css") {
		$ulos = "<td><textarea cols='45' rows='7' name='css'>$trow[$i]</textarea><input type='hidden' name='cssi' value='$i'></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "css_pieni") {
		$ulos = "<td><textarea cols='45' rows='7' name='css_pieni'>$trow[$i]</textarea><input type='hidden' name='csspienii' value='$i'></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "css_extranet") {
		$ulos = "<td><textarea cols='45' rows='7' name='css_extranet'>$trow[$i]</textarea><input type='hidden' name='cssextraneti' value='$i'></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "css_verkkokauppa") {
		$ulos = "<td><textarea cols='45' rows='7' name='css_verkkokauppa'>$trow[$i]</textarea><input type='hidden' name='cssverkkokauppa' value='$i'></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "web_seuranta") {
		$ulos = "<td><textarea cols='45' rows='7' name='web_seuranta'>$trow[$i]</textarea><input type='hidden' name='webseuranta' value='$i'></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "verkkolasku_lah") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";
		$sel4 = "";

		if ($trow[$i] == 'iPost') {
			$sel3 = "selected";
		}
		elseif ($trow[$i] == 'apix') {
			$sel4 = "selected";
		}
		elseif ($trow[$i] == 'finvoice') {
			$sel2 = "selected";
		}
		elseif($trow[$i] == 'maventa') {
			$sel5 = "selected";
		}
		else {
			$sel1 = "selected";
		}

		$ulos .= "<option value = '' $sel1>".t("Pupevoice");
		$ulos .= "<option value = 'finvoice' $sel2>".t("Finvoice");
		$ulos .= "<option value = 'iPost' $sel3>".t("Finvoice iPost");
		$ulos .= "<option value = 'apix' $sel4>".t("Apix-verkkolaskut");
		$ulos .= "<option value = 'maventa' $sel5>".t("Maventa-verkkolaskut");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lahetteen_tulostustapa") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";
		$sel4 = "";

		if ($trow[$i] == 'A') {
			$sel1 = "selected";
		}
		if ($trow[$i] == 'K') {
			$sel2 = "selected";
		}
		if ($trow[$i] == 'B') {
			$sel3 = "selected";
		}
		if ($trow[$i] == 'L') {
			$sel4 = "selected";
		}

		$ulos .= "<option value = 'K' $sel2>".t("Myyntitilausten ker‰yslistojen tulostukseen k‰ytet‰‰n tulostusjonoa");
		$ulos .= "<option value = 'L' $sel4>".t("Myyntitilausten ker‰yslistojen ja l‰hetteen tulostukseen k‰ytet‰‰n tulostusjonoa");
		$ulos .= "<option value = 'A' $sel1>".t("Myyntitilausten ker‰yslistat tulostetaan automaattisesti");
		$ulos .= "<option value = 'B' $sel3>".t("Myyntitilausten ker‰yslistat ja l‰hetteet tulostetaan automaattisesti");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myyntitilin_tulostustapa") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == 'A') {
			$sel1 = "selected";
		}
		if ($trow[$i] == 'K') {
			$sel2 = "selected";
		}

		$ulos .= "<option value = 'K' $sel2>".t("Myyntitilien ker‰yslistojen tulostukseen k‰ytet‰‰n tulostusjonoa")."";
		$ulos .= "<option value = 'A' $sel1>".t("Myyntitilien ker‰yslistat tulostetaan automaattisesti")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "valmistuksen_tulostustapa") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == 'A') {
			$sel1 = "selected";
		}
		if ($trow[$i] == 'K') {
			$sel2 = "selected";
		}

		$ulos .= "<option value = 'K' $sel2>".t("Valmistuksien ker‰yslistojen tulostukseen k‰ytet‰‰n tulostusjonoa")."";
		$ulos .= "<option value = 'A' $sel1>".t("Valmistuksien ker‰yslistat tulostetaan automaattisesti")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "siirtolistan_tulostustapa") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == 'A') {
			$sel1 = "selected";
		}
		if ($trow[$i] == 'K') {
			$sel2 = "selected";
		}

		$ulos .= "<option value = 'K' $sel2>".t("Siirtolistojen tulostukseen k‰ytet‰‰n tulostusjonoa")."";
		$ulos .= "<option value = 'A' $sel1>".t("Siirtolistat tulostetaan automaattisesti")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "pankkitiedostot") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";

		if ($trow[$i] == '') {
			$sel1 = "selected";
		}
		if ($trow[$i] == 'E') {
			$sel2 = "selected";
		}
		if ($trow[$i] == 'F') {
			$sel3 = "selected";
		}

		$ulos .= "<option value = '' $sel1>".t("Pankit ja p‰iv‰t yhdistet‰‰n")."";
		$ulos .= "<option value = 'E' $sel2>".t("Jokainen pankki ja p‰iv‰ omaan tiedostoon")."";
		$ulos .= "<option value = 'F' $sel3>".t("P‰iv‰t yhdistet‰‰n, yksi tiedosto per pankki")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laskutyyppi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = '0'  $sel[0]>".t("Normaali laskupohja");
		$ulos .= "<option value = '2'  $sel[2]>".t("Normaali laskupohja (Tuoteperheet yhdistetty)");
		$ulos .= "<option value = '5'  $sel[5]>".t("Normaali laskupohja (Ei n‰ytet‰ verollista rivihintaa)")."</option>";
		$ulos .= "<option value = '6'  $sel[6]>".t("Normaali laskupohja (Ei n‰ytet‰ tilausnumeroa/toimitusaikaa)")."</option>";
		$ulos .= "<option value = '7'  $sel[7]>".t("Normaali laskupohja (N‰ytet‰‰n tuotteen EAN koodi)")."</option>";
		$ulos .= "<option value = '1'  $sel[1]>".t("Pelkistetty laskupohja 1");
		#$ulos .= "<option value = '3' $sel[3]>".t("Pelkistetty laskupohja 2 (Tuoteperheet yhdistetty)");
		$ulos .= "<option value = '4'  $sel[4]>".t("Pelkistetty laskupohja 3");
		$ulos .= "<option value = '8'  $sel[8]>".t("Ruotsin laskupohja")."</option>";
		$ulos .= "<option value = '10' $sel[10]>".t("Normaali laskupohja ilman rivihintoja (ei laillinen Suomessa)");
		$ulos .= "<option value = '12' $sel[12]>".t("Normaali laskupohja (Tuoteperheet yhdistetty) ilman rivihintoja (ei laillinen Suomessa)");
		$ulos .= "<option value = '13' $sel[13]>".t("Nettohinnat, ei alennuksia, ei verollisia hintoja");
		$ulos .= "<option value = '14' $sel[14]>".t("Nettohinnat, ei alennuksia, ei verollisia hintoja (Tuoteperheet yhdistetty)");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "viivakoodi_laskulle") {
		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
		$ulos .= "<option value = ''>".t("Laskulle ei tulosteta viivakoodia");
		$ulos .= "<option value = 'K' $sel[K]>".t("Laskulle tulostetaan viivakoodi");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "koontilaskut_yhdistetaan") {
		$sel1 = "";
		$sel2 = "";

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		if ($trow[$i] == '') {
			$sel1 = "selected";
		}
		if ($trow[$i] == 'T') {
			$sel2 = "selected";
		}

		$ulos .= "<option value = '' $sel1>".t("Asiakkaan laskutusosoitteella")."</option>";
		$ulos .= "<option value = 'T' $sel2>".t("Asiakkaan laskutusosoitteella ja toimitusosoitteella")."</option>";

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilausvahvistustyyppi") {

			$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

			$apu = $trow[$i];
			$sel = array();
			$sel[$apu] = "selected";

			$ulos .= "<option value = '1' $sel[1]>".t("N‰ytet‰‰n asiakkaan hinta ja alennus")."</option>";
			$ulos .= "<option value = '2' $sel[2]>".t("N‰ytet‰‰n tuotteen myyntihinta ja alennusryhm‰")."</option>";
			$ulos .= "<option value = '3' $sel[3]>".t("Ei n‰ytet‰ hintoja eik‰ alennuksia")."</option>";
			$ulos .= "<option value = '4' $sel[4]>".t("N‰ytet‰‰n asiakkaan hinta")."</option>";
			#$ulos .= "<option value = '5' $sel[5]>".t("Haetaan hinnat toisesta pupesta")."</option>";
			$ulos .= "<option value = '6' $sel[6]>".t("N‰ytet‰‰n asiakkaan hinta ja alennus, modifioitu tulostuspohja")."</option>";

			$ulos .= "</select></td>";
			$jatko = 0;
	} //Jamppa s‰‰t‰‰

	if (mysql_field_name($result, $i) == "tilausvahvistus_lahetys") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = '0' $sel[0]>".t("Tilausvahvistus l‰hetet‰‰n jokaisesta osatilauksesta erikseen");
		$ulos .= "<option value = '1' $sel[1]>".t("Tilausvahvistus l‰hetet‰‰n alkuper‰isest‰ tilauksesta");

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "varastosiirto_tilausvahvistus") {
		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
		$ulos .= "<option value = ''>".t("Varastosiirroista ei l‰hetet‰ vahvistusta");
		$ulos .= "<option value = 'K' $sel[K]>".t("Varastosiirroista l‰hetet‰‰n vahvistus");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ostotilaustyyppi") {

		$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array_fill_keys(array($trow[$i]), 'selected') + array_fill_keys(array('0','A','B','C'), '');

		$ulos .= "<option value = '0' {$sel[0]}>".t("Normaali ostotilauspohja");
		$ulos .= "<option value = 'A' {$sel['A']}>".t("Tulosta nimitykset, piilotetaan toimitusp‰iv‰")."</option>";
		$ulos .= "<option value = 'B' {$sel['B']}>".t("Piilota nimitykset, tulosta toimitusp‰iv‰")."</option>";
		$ulos .= "<option value = 'C' {$sel['C']}>".t("Piilota toimitusp‰iv‰ ja nimitykset")."</option>";
		#$ulos .= "<option value = '1' $sel[1]>".t("Pelkistetty ostotilauspohja");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ostotilaukseen_toimittajan_toimaika") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = (int)$trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = '0' $sel[0]>".t("Ei k‰ytet‰");
		$ulos .= "<option value = '1' $sel[1]>".t("Ehdottaa toimitusaikaa");
		$ulos .= "<option value = '2' $sel[2]>".t("Laittaa automaattisesti toimitusajan");

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ostolaskujen_paivays") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = (int)$trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = '' $sel[0]>".t("Ostolaskulle syˆtet‰‰n vain laskun p‰iv‰");
		$ulos .= "<option value = '1' $sel[1]>".t("Ostolaskulle syˆtet‰‰n laskun p‰iv‰ sek‰ kirjausp‰iv‰");

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ostolaskujen_kurssipaiva") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = (int) $trow[$i];
		$sel = array("0" => "", "1" => "", "2" => "");
		$sel[$apu] = "selected";

		$ulos .= "<option value = '0'  $sel[0]>".t("Ostolaskun valuuttakurssi syˆttˆp‰iv‰m‰‰r‰n mukaan");
		$ulos .= "<option value = '1' $sel[1]>".t("Ostolaskun valuuttakurssi kirjausp‰iv‰m‰‰r‰n mukaan");
		$ulos .= "<option value = '2' $sel[2]>".t("Ostolaskun valuuttakurssi laskun p‰iv‰yksen mukaan");

		$ulos .= "</select></td>";

		$jatko = 0;
	}


	if (mysql_field_name($result, $i) == "tyomaaraystyyppi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = ''>".t("Tuotetiedot, hinnat ja alennukset");
		$ulos .= "<option value = 'O' $sel[O]>".t("Tuotetiedot");
		$ulos .= "<option value = 'P' $sel[P]>".t("Tuotetiedot ja varastopaikka");


		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "viivakoodi_purkulistaan") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = ''>".t("Ei");
		$ulos .= "<option value = 'K' $sel[K]>".t("Kyll‰");


		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "variaatiomyynti") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = ''>".t("Tilausrivien syˆtˆss‰ ei k‰ytet‰ variaatiomatriisia");
		$ulos .= "<option value = 'K' $sel[K]>".t("Tilausrivien syˆtˆss‰ k‰ytet‰‰n variaatiomatriisia");


		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tarjouksen_tuotepaikat") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = ''>".t("Tarjouksella tuotepaikat k‰ytˆss‰");
		$ulos .= "<option value = 'X' $sel[X]>".t("Tarjouksella ei viel‰ valita tuotepaikkoja");


		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tarjouksen_alv_kasittely") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = ''>".t("K‰ytet‰‰n yhtiˆn alv k‰sittely‰")."</option>";
		$ulos .= "<option value = 'S' $sel[S]>".t("Tuotteiden myyntihinnat sis‰lt‰v‰t arvonlis‰veron")."</option>";
		$ulos .= "<option value = 'X' $sel[X]>".t("Tuotteiden myyntihinnat ovat arvonlis‰verottomia")."</option>";


		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rahti_hinnoittelu") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		if ($trow[$i] == '') {
			$trow[$i] = 'A';
		}
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value=''  $sel[A]>".t("Rahtikulu muodostuu painon perusteella")."</option>";
		$ulos .= "<option value='o' $sel[o]>".t("Rahtikulu muodostuu tilauksen hinnan perusteella")."</option>";
		$ulos .= "<option value='P' $sel[P]>".t("Rahtikulu muodostuu tuotteilta lˆytyvien painojen mukaan automaattisesti")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "alv_kasittely") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";
		$sel4 = "";
		$sel5 = "";
		$sel6 = "";

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
		elseif ($trow[$i] == "A") {
			$sel5 = "selected";
		}
		elseif ($trow[$i] == "B") {
			$sel6 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Jos varastosaldo ei riit‰, tilausrivi merkataan puuteriviksi")."</option>";
		$ulos .= "<option value='A' $sel5>".t("Jos varastosaldo ei riit‰, tilausrivi merkataan puutteeksi uusimmalla tuotteella")."</option>";
		$ulos .= "<option value='J' $sel2>".t("Jos varastosaldo ei riit‰, tilausrivi merkataan j‰lkitoimitukseksi")."</option>";
		$ulos .= "<option value='B' $sel6>".t("Jos varastosaldo ei riit‰, tilausrivi merkataan j‰lkitoimitukseksi uusimmalla tuotteella")."</option>";
		$ulos .= "<option value='H' $sel3>".t("Jos varastosaldo ei riit‰, tilausrivi myyd‰‰n normaalisti")."</option>";
		$ulos .= "<option value='O' $sel4>".t("Kaikki myyd‰‰n aina oletuspaikalta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "puute_jt_kerataanko") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Puuterivej‰ ja JT-rivej‰ EI ker‰t‰")."</option>";
		$ulos .= "<option value = 'P' $sel[P]>".t("Puuterivit ker‰t‰‰n")."</option>";
		$ulos .= "<option value = 'J' $sel[J]>".t("JT-rivit ker‰t‰‰n")."</option>";
		$ulos .= "<option value = 'Q' $sel[Q]>".t("Puuterivit ja JT-rivit ker‰t‰‰n")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "saldo_kasittely") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "T") {
			$sel2 = "selected";
		}


		$ulos .= "<option value = ''  $sel1>".t("Myyt‰viss‰-kpl ajasta riippumaton")."</option>";
		$ulos .= "<option value = 'T' $sel2>".t("Myyt‰viss‰-kpl lasketaan ker‰yspvm:n mukaan")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kerataanko_saldottomat") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "S") {
			$sel2 = "selected";
		}


		$ulos .= "<option value = ''  $sel1>".t("Saldottomia tuotteita ei ker‰t‰")."</option>";
		$ulos .= "<option value = 'S' $sel2>".t("Saldottomat tuotteet ker‰t‰‰n")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kerataanko_valmistukset") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array($trow[$i] => "SELECTED");

		$ulos .= "<option value = ''>".t("Valmistukset ker‰t‰‰n")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Valmistuksia ei ker‰t‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kerayslistojen_yhdistaminen") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}
		elseif ($trow[$i] == "T") {
			$sel3 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Asiakkaan tilauksia EI yhdistet‰ yhdelle ker‰yslistalle")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Asiakkaan tilaukset yhdistet‰‰n yhdelle ker‰yslistalle jos mahdollista")."</option>";
		$ulos .= "<option value='T' $sel3>".t("Asiakkaan tilaukset yhdistet‰‰n yhdelle ker‰yslistalle Ytunnuksen mukaan")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "karayksesta_rahtikirjasyottoon") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}
		elseif ($trow[$i] == "H") {
			$sel3 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Ker‰yksest‰ ei siirryt‰ suoraan rahtikirjan syˆttˆˆn")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Ker‰yksest‰ siirryt‰‰n suoraan rahtikirjan syˆttˆˆn")."</option>";
		$ulos .= "<option value='H' $sel3>".t("Ker‰yksest‰ siirryt‰‰n suoraan rahtikirjan syˆttˆˆn, jos halutaan")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rahtikirjojen_esisyotto") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

	if (mysql_field_name($result, $i) == "rahtikirjan_kollit_ja_lajit") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "E") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Yhteen")."</option>";
		$ulos .= "<option value='E' $sel2>".t("Erikseen")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tee_osto_myyntitilaukselta") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array_fill_keys(array('A', 'B', 'C', 'I', 'J', 'Q', 'U', 'V', 'W', 'X', 'Y', 'Z'), '');
		$sel[$trow[$i]] = " SELECTED";

		$ulos .= "<option value=''>".t("Myyntitilaukselta ei voi luoda ostorivej‰")."</option>";
		$ulos .= "<option value='X' {$sel['X']}>".t("Myyntitilaukselta voi luoda ostorivej‰ varastoon")."</option>";
		$ulos .= "<option value='U' {$sel['U']}>".t("Myyntitilaukselta voi luoda ostorivej‰ asiakkaalle")."</option>";
		$ulos .= "<option value='Y' {$sel['Y']}>".t("Myyntitilaukselta voi luoda ostorivej‰ asiakkaalle ja varastoon")."</option>";
		$ulos .= "<option value='A' {$sel['A']}>".t("Myyntitilaukselta voi luoda yhden toimittajan ostorivej‰ varastoon")."</option>";
		$ulos .= "<option value='B' {$sel['B']}>".t("Myyntitilaukselta voi luoda yhden toimittajan ostorivej‰ asiakkaalle")."</option>";
		$ulos .= "<option value='C' {$sel['C']}>".t("Myyntitilaukselta voi luoda yhden toimittajan ostorivej‰ asiakkaalle ja varastoon")."</option>";

		$ulos .= "<option value='V' {$sel['V']}>".t("Myyntitilaukselta voi luoda ostorivej‰ varastoon, jos se on tuotteen takana sallittu")."</option>";
		$ulos .= "<option value='W' {$sel['W']}>".t("Myyntitilaukselta voi luoda ostorivej‰ asiakkaalle ja varastoon, jos se on tuotteen takana sallittu")."</option>";

		$ulos .= "<option value='I' {$sel['I']}>".t("Myyntitilaukselta voi luoda yhden toimittajan ostorivej‰ varastoon, jos se on tuotteen takana sallittu")."</option>";
		$ulos .= "<option value='J' {$sel['J']}>".t("Myyntitilaukselta voi luoda yhden toimittajan ostorivej‰ asiakkaalle ja varastoon, jos se on tuotteen takana sallittu")."</option>";

		$ulos .= "<option value='Z' {$sel['Z']}>".t("Myyntitilauksen voi siirt‰‰ varaston ostotilaukseksi")."</option>";
		$ulos .= "<option value='Q' {$sel['Q']}>".t("Myyntitilauksen voi siirt‰‰ ostotilaukseksi, joka toimitetaan suoraan asiakkaalle")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tee_valmistus_myyntitilaukselta") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Myyntitilaukselta ei voi luoda valmistusrivej‰")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilaukselta voi luoda valmistusrivej‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "automaattinen_jt_toimitus") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos .= "<option value=''>".t("J‰lkitoimitusrivej‰ ei toimiteta eik‰ kohdisteta automaattisesti")."</option>";
		$ulos .= "<option value='K' $sel[K]>".t("J‰lkitoimitusrivit kohdistetaan automaattisesti varastoonviennin yhteydess‰")."</option>";
		$ulos .= "<option value='T' $sel[T]>".t("J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydess‰, jos k‰ytt‰j‰ll‰ on p‰ivitysoikeus JT-selaukseen")."</option>";
		$ulos .= "<option value='J' $sel[J]>".t("J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydess‰")."</option>";
		$ulos .= "<option value='A' $sel[A]>".t("J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydess‰ ker‰ysp‰iv‰n mukaisesti")."</option>";
		$ulos .= "<option value='S' $sel[S]>".t("Ainoastaan suoratoimitetut j‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydess‰")."</option>";
		$ulos .= "<option value='V' $sel[V]>".t("Suoratoimitetut sek‰ myyntitilaukselta varastoon tilatut j‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydess‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "automaattinen_jt_toimitus_valmistus") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos .= "<option value=''>".t("Valmistusrivej‰ ei toimiteta eik‰ kohdisteta automaattisesti")."</option>";
		$ulos .= "<option value='K' $sel[K]>".t("J‰lkitoimitusrivit kohdistetaan automaattisesti valmistuksen yhteydess‰")."</option>";
		$ulos .= "<option value='T' $sel[T]>".t("J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti valmistuksen yhteydess‰, jos k‰ytt‰j‰ll‰ on p‰ivitysoikeus JT-selaukseen")."</option>";
		$ulos .= "<option value='J' $sel[J]>".t("J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti valmistuksen yhteydess‰")."</option>";
		$ulos .= "<option value='S' $sel[S]>".t("Ainoastaan asiakkaalle valmistetut j‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti valmistuksen yhteydess‰")."</option>";
		$ulos .= "<option value='V' $sel[V]>".t("Asiakkaalle sek‰ myyntitilaukselta varastoon valmistetut j‰lkitoimitusrivit kohdistetaan asiakkaalle ja toimitetaan automaattisesti valmistuksen yhteydess‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kerataanko_jos_vain_puute_jt") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos .= "<option value = ''>".t("Tilausta EI ker‰t‰ jos listalla on vain Puuterivej‰ JA/TAI JT-rivej‰")."</option>";
		$ulos .= "<option value = 'P' $sel[P]>".t("Tilaus ker‰t‰‰n jos listalla on vain Puuterivej‰")."</option>";
		$ulos .= "<option value = 'J' $sel[J]>".t("Tilaus ker‰t‰‰n jos listalla on vain JT-rivej‰")."</option>";
		$ulos .= "<option value = 'Q' $sel[Q]>".t("Tilaus ker‰t‰‰n jos listalla on vain Puuterivej‰ JA/TAI JT-rivej‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jt_automatiikka") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos .= "<option value=''>".t("Asiakkaan JT-rivej‰ ei kohdisteta automaattisesti")."</option>";
		$ulos .= "<option value='Y' $sel[Y]>".t("Asiakkaan JT-rivej‰ kohdistetaan automaattisesti sis‰‰ntulevalle EDI-tilaukselle")."</option>";
		$ulos .= "<option value='X' $sel[X]>".t("Asiakkaan JT-rivej‰ kohdistetaan automaattisesti myyntitilaukselle")."</option>";
		$ulos .= "<option value='W' $sel[W]>".t("Asiakkaan JT-rivej‰ kohdistetaan automaattisesti myyntitilaukselle ja sis‰‰ntulevalle EDI-tilaukselle")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "saapumisen_jt_kasittely") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos .= "<option value=''>".t("Ostotilauksen saapuvan m‰‰r‰n ollessa tilattua pienempi, erotukselle ei tehd‰ mit‰‰n")."</option>";
		$ulos .= "<option value='K' $sel[K]>".t("Ostotilauksen saapuvan m‰‰r‰n ollessa tilattua pienempi, erotus siirret‰‰n uudelle riville")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "naytetaan_katteet_tilauksella") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}
		elseif ($trow[$i] == "B") {
			$sel3 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Katetta ei n‰ytet‰ tilauksentekovaiheessa")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Kate n‰ytet‰‰n tilauksentekovaiheessa")."</option>";
		$ulos .= "<option value='B' $sel3>".t("Bruttokate n‰ytet‰‰n tilauksentekovaiheessa ja tuotekyselyss‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "nimityksen_muutos_tilauksella") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

	if (mysql_field_name($result, $i) == "hintapyoristys") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array_fill(2, 5, "");
		$sel[$trow[$i]] = "SELECTED";

		$ulos .= "<option value='2' $sel[2]>".t("Yksikkˆ- ja rivihinnat pyˆristet‰‰n kahteen desimaaliin")."</option>";
		$ulos .= "<option value='3' $sel[3]>".t("Yksikkˆ- ja rivihinnat pyˆristet‰‰n kolmeen desimaaliin")."</option>";
		$ulos .= "<option value='4' $sel[4]>".t("Yksikkˆ- ja rivihinnat pyˆristet‰‰n nelj‰‰n desimaaliin")."</option>";
		$ulos .= "<option value='5' $sel[5]>".t("Yksikkˆ- ja rivihinnat pyˆristet‰‰n viiteen desimaaliin")."</option>";
		$ulos .= "<option value='6' $sel[6]>".t("Yksikkˆ- ja rivihinnat pyˆristet‰‰n kuuteen desimaaliin")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "hintapyoristys_loppunollat") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1="";

		if ($trow[$i] != "") {
			$sel1 = "selected";
		}

		$ulos .= "<option value=''>".t("Yksikkˆ- ja rivihinnan kaikki desimaalit n‰ytet‰‰n")."</option>";
		$ulos .= "<option value='P' $sel1>".t("Yksikkˆ- ja rivihinnoista poistetaan merkityksettˆm‰t loppunollat")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laskunsummapyoristys") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

	if (mysql_field_name($result, $i) == "dynaaminen_kassamyynti") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel			= array();
		$sel[$trow[$i]]	= " selected";

		$ulos .= "<option value=''>".t("Ker‰yspoikkeamia ei k‰sitell‰")."</option>";
		$ulos .= "<option value='o' $sel[o]>".t("Ker‰‰j‰ p‰‰tt‰‰ ker‰yspoikkeaman k‰sittelyst‰")."</option>";
		$ulos .= "<option value='J' $sel[J]>".t("Ker‰‰j‰ p‰‰tt‰‰ ker‰yspoikkeaman k‰sittelyst‰ (Oletuksena JT)")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kerayspoikkeamaviestin_lahetys") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel		= array();
		$sel[$trow[$i]]	= " selected";

		$ulos .= "<option value='0' $sel[0]>".t("Ker‰yspoikkeamaviesti l‰hetet‰‰n asiakkaalle ja myyj‰lle")."</option>";
		$ulos .= "<option value='2' $sel[2]>".t("Ker‰yspoikkeamaviesti l‰hetet‰‰n vain myyj‰lle")."</option>";
		$ulos .= "<option value='1' $sel[1]>".t("Ker‰yspoikkeamaviesti‰ ei l‰hetet‰ ollenkaan")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "keraysvahvistus_lahetys") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel			= array();
		$sel[$trow[$i]]	= " selected";

		$ulos .= "<option value=''>".t("Ker‰ysvahvistusta ei l‰hetet‰")."</option>";
		$ulos .= "<option value='o' $sel[o]>".t("Ker‰ysvahvistus l‰hetet‰‰n asiakkaalle")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "oletus_toimitusehto") {

		$kires = t_avainsana("TOIMEHTO");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
		$ulos .= "<option value=''>".t("Ei oletustoimitusehtoa")."</option>";

		while ($kirow=mysql_fetch_array($kires)) {
			if ($kirow["selite"]." ".$kirow["selitetark"] == $trow[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[selite] $kirow[selitetark]' $select>$kirow[selite] - $kirow[selitetark]</option>";
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "oletus_toimitusehto2") {

		$kires = t_avainsana("TOIMEHTO");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
		$ulos .= "<option value=''>".t("Ei oletustoimitusehtoa vaihtokohteille")."</option>";

		while ($kirow=mysql_fetch_array($kires)) {
			if ($kirow["selite"]." ".$kirow["selitetark"] == $trow[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[selite] $kirow[selitetark]' $select>$kirow[selite] - $kirow[selitetark]</option>";
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lasku_tulostin") {

		$query = "	SELECT *
					from kirjoittimet
					where yhtio = '$kukarow[yhtio]'
					ORDER BY kirjoitin";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
		$ulos .= "<option value=''>".t("Ei kirjoitinta")."</option>";

		while ($kirow=mysql_fetch_array($kires)) {
			if ($kirow["tunnus"]==$trow[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
		}

		if ($trow[$i] == -99) $select = 'SELECTED';
		else $select = '';

		$ulos .= "<option value='-99' $select>".t("Asiakkaan s‰hkˆpostiin")."</option>";

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laskutuskielto") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

	if (mysql_field_name($result, $i) == "splittauskielto") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array($trow[$i] => "SELECTED");

		$ulos .= "<option value = ''>".t("Tilaukset jaetaan osiin varastoittain")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Tilauksia ei jaeta osiin")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Tilaukset jaetaan osiin ker‰ysp‰iv‰n mukaan")."</option>";
		$ulos .= "<option value = 'V' $sel[V]>".t("Tilaukset jaetaan osiin tuotteen ker‰ysvyˆhykkeen mukaan")."</option>";
		$ulos .= "<option value = 'W' $sel[W]>".t("Tilaukset jaetaan osiin varaston hyllypaikan ker‰ysvyˆhykkeen mukaan")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "valmistuksen_etusivu") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array($trow[$i] => "SELECTED");

		$ulos .= "<option value = ''>".t("Valmistuksille ei tulosteta etusivua")."";
		$ulos .= "<option value = 'E' $sel[E]>".t("Valmistuksille tulostetaan etusivu")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jyvita_alennus") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

		$ulos .= "<option value = '' $sel>".t("Kassamyyj‰ saa jyvitt‰‰ myyntitilauksella")."";
		$ulos .= "<option value = 'K' $sel1>".t("Kaikki saa jyvitt‰‰ myyntitilauksella")."";
		$ulos .= "<option value = 'V' $sel2>".t("Vain valitut k‰ytt‰j‰t saa jyvitt‰‰ myyntitilauksella")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rivinumero_syotto") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] != '') {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value = '' $sel>".t("Rivinumeroita ei voi syˆtt‰‰ tilaukselle")."";
		$ulos .= "<option value = 'K' $sel1>".t("Rivinumeroita voi syˆtt‰‰ tilaukselle")."";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "sad_lomake_tyyppi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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
		mysql_field_name($result, $i) == "ostotilauksen_jarjestys" or
		mysql_field_name($result, $i) == "tarjouksen_jarjestys" or
		mysql_field_name($result, $i) == "tyomaarayksen_jarjestys") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "selected";

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
		$ulos .= "<option value = 'M' $sel[M]>".t("K‰ytt‰j‰n asettama j‰rjestys");

		if(	mysql_field_name($result, $i) == "valmistus_kerayslistan_jarjestys" or
			mysql_field_name($result, $i) == "kerayslistan_jarjestys") {
			$ulos .= "<option value = 'S' $sel[S]>".t("Rivit summattuina");
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
		mysql_field_name($result, $i) == "ostotilauksen_jarjestys_suunta" or
		mysql_field_name($result, $i) == "tarjouksen_jarjestys_suunta" or
		mysql_field_name($result, $i) == "tyomaarayksen_jarjestys_suunta") {


		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = 'ASC'  $sel[ASC]>".t("Nouseva");
		$ulos .= "<option value = 'DESC' $sel[DESC]>".t("Laskeva");

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lahetteen_palvelutjatuottet" or
		mysql_field_name($result, $i) == "laskun_palvelutjatuottet" or
		mysql_field_name($result, $i) == "kerayslistan_palvelutjatuottet" or
		mysql_field_name($result, $i) == "valmistus_kerayslistan_palvelutjatuottet" or
		mysql_field_name($result, $i) == "tarjouksen_palvelutjatuottet" or
		mysql_field_name($result, $i) == "tyomaarayksen_palvelutjatuottet") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = ''>".t("Yhdess‰");
		$ulos .= "<option value = 'E' $sel[E]>".t("Erikseen");

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lahete_nouto_allekirjoitus") {

		$sela = $selb = "";

		if ($trow[$i] == 'K') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value='' $sela>".t("Ei tulosteta allekirjoitussivua")."</option>
					<option value='K' $selb>".t("Tulostetaan allekirjoitussivu")."</option>
				</select></td>";

		$jatko = 0;
	}

	if(mysql_field_name($result, $i) == "lomakkeiden_allekirjoitus") {
		$sela = $selb = "";

		if ($trow[$i] == 'X') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value='' $sela>".t("Ei n‰ytet‰ allekirjoitusta myyntilasku, ostotilaus, l‰hete tai tarjous tulosteella")."</option>
					<option value='X' $selb>".t("N‰ytet‰‰n allekirjoitus myyntilasku, ostotilaus, l‰hete ja tarjous tulosteella")."</option> 
				</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lahete_tyyppi_tulostus") {

		$sela = $selb = "";

		if ($trow[$i] == 'K') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value='' $sela>".t("L‰hetetyyppi‰ EI SAA vaihtaa tulostuksessa")."</option>
					<option value='K' $selb>".t("L‰hetetyyppi‰ SAA vaihtaa tulostuksessa")."</option>
				</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilausvahvistus_tyyppi_tulostus") {

		$sela = $selb = "";

		if ($trow[$i] == 'K') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value='' $sela>".t("Tilausvahvistustyyppi‰ EI SAA vaihtaa tulostuksessa")."</option>
					<option value='K' $selb>".t("Tilausvahvistustyyppi‰ SAA vaihtaa tulostuksessa")."</option>
				</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "valmistusten_yhdistaminen") {

		$sela = "";
		$selb = "";

		if ($trow[$i] == 'P') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value='' $sela>".t("Tuotteen yhdistet‰‰n normaalisti")."</option>
					<option value='P' $selb>".t("Myˆs tuoteperheet koitetaan yhdist‰‰")."</option>
				</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tarjouksen_voimaika") {

		$ulos = "<td><input type='text' name='$nimi' value = '$trow[$i]' size='5'> (P‰iv‰‰ luontiajasta)</td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilauksen_yhteyshenkilot") {

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value=''>".t("Vain tilausyhteyshenkilˆ")."</option>
					<option value='P' $sel[P]>".t("Vain tilausyhteyshenkilˆ, pakollinen")."</option>
					<option value='K' $sel[K]>".t("Tekninen yhteyshenkilˆ, kaupallinen yhteyshenkilˆ ja projektip‰‰llikkˆ")."</option>
				</select></td>";


		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tarjouksen_voi_versioida") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Tarjoksesta ei voi tehd‰ versioita")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Tarjouksesta voi tehd‰ versioita")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rekursiiviset_reseptit") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Resepteiss‰ vain yksi taso")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Reseptit k‰sitell‰‰n rekursiivisesti")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rekursiiviset_tuoteperheet") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1 = "";
		$sel2 = "";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		elseif ($trow[$i] == "Y") {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Tuoteperheiss‰ vain yksi taso")."</option>";
		$ulos .= "<option value='Y' $sel2>".t("Tuoteperheet k‰sitell‰‰n rekursiivisesti")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "valmistustapa") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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
		$ulos .= "<option value='I' $sel2>".t("Vientierittely tulostetaan ilman alkuper‰maatietoja")."</option>";
		$ulos .= "<option value='E' $sel3>".t("Vientierittely‰ ei tulosteta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "oletus_lahetekpl") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

		$ulos .= "<option value='0' $sel0>".t("L‰hetteest‰ tulostetaan 0 kopiota.")."</option>";
		$ulos .= "<option value='1' $sel1>".t("L‰hetteest‰ tulostetaan 1 kopiota.")."</option>";
		$ulos .= "<option value='2' $sel2>".t("L‰hetteest‰ tulostetaan 2 kopiota.")."</option>";
		$ulos .= "<option value='3' $sel3>".t("L‰hetteest‰ tulostetaan 3 kopiota.")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "oletus_oslappkpl") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

	if (mysql_field_name($result, $i) == "oletus_rahtikirja_lahetekpl") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

		$ulos .= "<option value='0' $sel0>".t("L‰hetteest‰ tulostetaan 0 kopiota.")."</option>";
		$ulos .= "<option value='1' $sel1>".t("L‰hetteest‰ tulostetaan 1 kopiota.")."</option>";
		$ulos .= "<option value='2' $sel2>".t("L‰hetteest‰ tulostetaan 2 kopiota.")."</option>";
		$ulos .= "<option value='3' $sel3>".t("L‰hetteest‰ tulostetaan 3 kopiota.")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "oletus_rahtikirja_oslappkpl") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Viitett‰ ei saa syˆtt‰‰ k‰sin")."</option>";
		$ulos .= "<option value='X' $sel2>".t("Viitteen saa syˆtt‰‰ k‰sin")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "poistuvat_tuotteet") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("ƒl‰ n‰yt‰ poistuvia tuotteita oletuksena")."</option>";
		$ulos .= "<option value='X' $sel2>".t("N‰yt‰ poistuvat tuotteet oletuksena")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "automaattinen_tuotehaku") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Automaattinen tuotehaku ei k‰ytˆss‰")."</option>";
		$ulos .= "<option value='X' $sel2>".t("Automaattinen tuotehaku k‰ytˆss‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "oslapp_rakir_logo") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value=''  $sel1>".t("Logo tulostetaan rahtikirjaan ja osoitelappuun")."</option>";
		$ulos .= "<option value='X' $sel2>".t("Logoa ei tulosteta rahtikirjaan eik‰ osoitelappuun")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ytunnus_tarkistukset") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

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

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Rahtikirjan syˆtˆss‰ ei syˆtet‰ rahti ja k‰sittelykuluja")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Rahtikirjan syˆtˆss‰ voi syˆtt‰‰ rahti ja k‰sittelykuluja")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myyntitilaus_osatoimitus") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Myyntitilauksesta ei voi tehd‰ osatoimitusta k‰sin")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilauksesta voi tehd‰ osatoimituksia k‰sin")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Tilauksia ei osatoimiteta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "varaako_jt_saldoa") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("J‰lkitoimitusrivit eiv‰t varaa saldoa")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("J‰lkitoimitusrivit varaavat saldoa")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "varastopaikan_lippu") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Myynniss‰ ei n‰ytet‰ vaarastopaikan maan lippu")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Myynniss‰ n‰ytet‰‰n vaarastopaikan maan lippu")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myyntitilaus_asiakasmemo") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Myyntitilauksella ei n‰ytet‰ asiakasmemoa")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilauksella n‰ytet‰‰n asiakasmemo")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myyntitilaus_saatavat") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Myyntitilauksella n‰ytet‰‰n saatavat per asiakas")."</option>";
		$ulos .= "<option value = 'Y' $sel[Y]>".t("Myyntitilauksella n‰ytet‰‰n saatavat per ytunnus")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myyntitilauksen_liitteet") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Myyntitilaukselle ei saa liitt‰‰ liitetiedostoja")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilaukselle saa liitt‰‰ liitetiedostoja")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "logo" or mysql_field_name($result, $i) == "lasku_logo") {
		$tyyppi = 5;
	}

	if (mysql_field_name($result, $i) == "korvaavan_hinta_ylaraja") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Korvaava tuote myyd‰‰n hinnasta riippumatta")."</option>";
		$ulos .= "<option value = '10' $sel[10]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "10%")."</option>";
		$ulos .= "<option value = '20' $sel[20]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "20%")."</option>";
		$ulos .= "<option value = '30' $sel[30]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "30%")."</option>";
		$ulos .= "<option value = '40' $sel[40]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "40%")."</option>";
		$ulos .= "<option value = '50' $sel[50]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "50%")."</option>";
		$ulos .= "<option value = '60' $sel[60]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "60%")."</option>";
		$ulos .= "<option value = '70' $sel[70]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "70%")."</option>";
		$ulos .= "<option value = '80' $sel[80]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "80%")."</option>";
		$ulos .= "<option value = '90' $sel[90]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "90%")."</option>";
		$ulos .= "<option value = '100' $sel[100]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "100%")."</option>";
		$ulos .= "<option value = 'MIN' $sel[MIN]>".sprintf(t('Korvaava tuote myyd‰‰n aina halvemman tuotteen hinnalla, mik‰li tuote on loppu'))."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "korvaavat_hyvaksynta") {
		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
		$ulos .= "<option value='' $sel>".t("Ei. Korvaava tuote lis‰t‰‰n myyntitilaukselle automaattisesti.")."</option>";
		$ulos .= "<option value='K' $sel[K]>".t("Kyll‰. Korvaava tuote lis‰t‰‰n myyntitilauksella vain jos myyj‰ hyv‰ksyy korvaavan tuotteen.")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "monikayttajakalenteri") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = '' $sel>".t("Kyll‰")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Ei")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "automaattinen_asiakasnumerointi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Ei")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Kyll‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakasnumeroinnin_aloituskohta") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos = "<td><input type='text' name='$nimi' value = '$trow[$i]' size='11'></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakkaan_tarkenne") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = '' $sel>".t("Ovttunnus")."</option>";
		$ulos .= "<option value = 'A' $sel[A]>".t("Asiakasnro")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tuotteen_oletuspaikka") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos = "<td><input type='text' name='$nimi' value = '$trow[$i]' size='25'></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "haejaselaa_konsernisaldot") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos .= "<option value = ''>".t("Konserniyhtiˆiden saldoja EI n‰ytet‰ Hae ja Selaa-ohjelmassa")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Konserniyhtiˆiden saldot n‰ytet‰‰n Hae ja Selaa-ohjelmassa")."</option>";
		$ulos .= "<option value = 'S' $sel[S]>".t("Konserniyhtiˆiden saldot n‰ytet‰‰n Hae ja Selaa-ohjelmassa sek‰ tuotekyselyss‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rahtivapaa_alarajasumma") {

		$ulos = "<td><input type='text' name='$nimi' id='$nimi' value='$trow[$i]' size='15'></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jt_rahti") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = 'A' $sel[A]>".t("Ennakko/J‰lkitoimitus on rahtivapaa, jos alkuper‰inen tilaus oli rahtivapaa")."</option>";
		$ulos .= "<option value = 'B' $sel[B]>".t("Ennakot/J‰lkitoimitukset ovat aina rahtivapaita")."</option>";
		$ulos .= "<option value = 'C' $sel[C]>".t("Ennakoihin/J‰lkitoimituksiin lis‰t‰‰n aina rahtikulu")."</option>";
		$ulos .= "<option value = 'D' $sel[D]>".t("Ennakot/J‰lkitoimitukset ovat rahtivapaita, jos tilauksen arvo on yli rahtivapausrajan")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Ensimm‰isen‰ toimitetulle ennakko-/j‰lkitoimitustilaukselle lis‰t‰‰n rahtikulu koko tilauksesta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jt_rivien_kasittely") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Salli kaikki toiminnot")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Est‰ jt-rivien mit‰tˆiminen extranetiss‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilausvahvistus_tallenna") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Tilausvahvistusta ei tallenneta myyntitilauksen liitetiedostoksi")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Tilausvahvistus tallennetaan myyntitilauksen liitetiedostoksi")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laskutuslisa" OR
		mysql_field_name($result, $i) == "kuljetusvakuutus") {

		if ($trow[$i] == 0) {
			$trow[$i] = "";
		}
	}

	if (mysql_field_name($result, $i) == "laskutuslisa_tyyppi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		/*1) lis‰kulu kaikkiin laskuihin 2) kaikki paitsi k‰teinen 3) kaikki paitsi nouto.*/
		$ulos .= "<option value = ''>".t("Ei laskutuslis‰‰")."</option>";
		$ulos .= "<option value = 'A' $sel[A]>".t("Laskutuslis‰ lis‰t‰‰n kaikkiin laskuihin")."</option>";
		$ulos .= "<option value = 'B' $sel[B]>".t("Laskutuslis‰ lis‰t‰‰n kaikkiin paitsi k‰teislaskuihin")."</option>";
		$ulos .= "<option value = 'C' $sel[C]>".t("Laskutuslis‰ lis‰t‰‰n kaikkiin paitsi noutolaskuihin")."</option>";
		$ulos .= "<option value = 'L' $sel[L]>".t("Prosentuaalinen laskutuslis‰ lis‰t‰‰n kaikkiin laskuihin")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Prosentuaalinen laskutuslis‰ lis‰t‰‰n kaikkiin paitsi k‰teislaskuihin")."</option>";
		$ulos .= "<option value = 'N' $sel[N]>".t("Prosentuaalinen laskutuslis‰ lis‰t‰‰n kaikkiin paitsi noutolaskuihin")."</option>";
		$ulos .= "<option value = 'T' $sel[T]>".t("Laskutuslis‰ lis‰t‰‰n kaikkiin laskuihin. K‰ytet‰‰n laskutuslis‰tuotteen asiakashintaa ja alennusta.")."</option>";
		$ulos .= "<option value = 'U' $sel[U]>".t("Laskutuslis‰ lis‰t‰‰n muihin laskuihin paitsi k‰teislaskuihin. K‰ytet‰‰n laskutuslis‰tuotteen asiakashintaa ja alennusta.")."</option>";
		$ulos .= "<option value = 'V' $sel[V]>".t("Laskutuslis‰ lis‰t‰‰n muihin laskuihin paitsi noutolaskuihin. K‰ytet‰‰n laskutuslis‰tuotteen asiakashintaa ja alennusta.")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kuljetusvakuutus_tyyppi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Ei kuljetusvakuutusta")."</option>";
		$ulos .= "<option value = 'A' $sel[A]>".t("Valuuttam‰‰r‰inen kuljetusvakuutus lis‰t‰‰n")."</option>";
		$ulos .= "<option value = 'B' $sel[B]>".t("Prosentuaalinen kuljetusvakuutus lis‰t‰‰n")."</option>";
		$ulos .= "<option value = 'F' $sel[F]>".t("Valuuttam‰‰r‰inen kuljetusvakuutus lis‰t‰‰n. K‰ytet‰‰n kuljetusvakuutustuotteen asiakashintaa ja alennusta")."</option>";
		$ulos .= "<option value = 'G' $sel[G]>".t("Prosentuaalinen kuljetusvakuutus lis‰t‰‰n. K‰ytet‰‰n kuljetusvakuutustuotteen asiakasalennusta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kuljetusvakuutus_koonti") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == "L" ? " selected" : "";

		$ulos .= "<option value = ''>".t("Kuljetusvakuutus muodostuu per tilaus")."</option>";
		$ulos .= "<option value = 'L' {$sel}>".t("Kuljetusvakuutus muodostuu per lasku")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "viikkosuunnitelma") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Viikkosuunnitelma k‰ytˆss‰")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Ei k‰ytet‰ viikkosuunnitelmaa")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kalenterimerkinnat") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Kalenterimerkintˆj‰ lis‰t‰‰n automaattisesti")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Kalenterimerkintˆj‰ EI lis‰t‰ automaattisesti")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tuntikirjausten_erittely") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Tuntikirjauksia ei eritell‰")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Tuntikirjaukset eritell‰‰n")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "pakkaamolokerot") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Ei k‰ytet‰ pakkaamojen lokeroita")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("K‰ytet‰‰n pakkaamojen lokeroita. Tilaukselle jaetaan lokero per pakkaamo")."</option>";
		$ulos .= "<option value = 'L' $sel[L]>".t("K‰ytet‰‰n pakkaamojen lokeroita. Tilaukselle jaetaan vain yksi lokero")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "huomautetaanko_poistuvasta") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Ei")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Kyll‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myytitilauksen_kululaskut") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Myyntitilauksiin ei voida liitt‰‰ kululaskuja")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilauksiin voi liitt‰‰ kululaskuja")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "palvelutuotteiden_kehahinnat") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Palvelutuotteen hankintahintaa ei voi syˆtt‰‰ k‰sin")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Palvelutuotteen hankintahinta voidaan syˆtt‰‰ k‰sin")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rinnakkaisostaja_myynnissa") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Myynniss‰ voi t‰ytt‰‰ rinnakkaisostajan tiedot")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Myynniss‰ ei voi t‰ytt‰‰ rinnakkaisostajan tietoja")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tyomaaraystiedot_tarjouksella") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Tarjoukselle voi lis‰t‰ tyˆm‰‰r‰ystietoja")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Tarjoukselle ei voi lis‰t‰ tyˆm‰‰r‰ystietoja")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "vahinkotiedot_tyomaarayksella") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Tyˆm‰‰r‰ykselle voi lis‰t‰ vahinkotiedot")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Tyˆm‰‰r‰ykselle ei voi lis‰t‰ vahinkotietoja")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilausrivien_toimitettuaika") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Tuotteiden toimitettuaika tulostetaan laskulle normaalisti")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Saldottomien tuotteiden toimitettuajaksi tulostetaan laskulle rivin toimitusaika")."</option>";
		$ulos .= "<option value = 'X' $sel[X]>".t("Kaikkien tuotteiden toimitettuajaksi tulostetaan laskulle rivin toimitusaika")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kehahinarvio_ennen_ensituloa") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Tuotteille ei voi syˆtt‰‰ keskihankintahinta-arviota ennen ensimm‰ist‰ tuloa")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Tuotteille voidaan syˆtt‰‰ keskihankintahinta-arvio ennen ensimm‰ist‰ tuloa")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "konsernivarasto") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Ei k‰ytˆss‰")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("K‰ytˆss‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "livetuotehaku_tilauksella") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Ei k‰ytˆss‰")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("K‰ytˆss‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "livetuotehaku_poistetut") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Poistuvia/poistettuja tuotteita ei n‰ytet‰")."</option>";
		$ulos .= "<option value = 'X' $sel[X]>".t("Poistuvat tuotteet n‰ytet‰‰n")."</option>";
		$ulos .= "<option value = 'Y' $sel[Y]>".t("Poistuvat tuotteet n‰ytet‰‰n sek‰ ep‰kurantit korostetaan")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "naytetaanko_osaston_ja_tryn_selite") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Tuoteosaston/tuoteryhm‰n koodi ja nimi n‰ytet‰‰n")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Tuoteosaston/tuoteryhm‰n nimi n‰ytet‰‰n")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilausvahvistus_jttoimituksista") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("JT-toimituksista ei l‰hetet‰ tilausvahvistusta")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("JT-toimituksista l‰hetet‰‰n tilausvahvistus")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "iltasiivo_mitatoi_ext_tilauksia") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Iltasiivo ei mit‰tˆi kesken olevia extranet-tilauksia")."</option>";
		$ulos .= "<option value = '12' $sel[12]>".t("Iltasiivo mit‰tˆi 12 tuntia vanhoja kesken olevia extranet-tilauksia")."</option>";
		$ulos .= "<option value = '24' $sel[24]>".t("Iltasiivo mit‰tˆi 1 p‰iv‰n vanhoja kesken olevia extranet-tilauksia")."</option>";
		$ulos .= "<option value = '48' $sel[48]>".t("Iltasiivo mit‰tˆi 2 p‰iv‰‰ vanhoja kesken olevia extranet-tilauksia")."</option>";
		$ulos .= "<option value = '168' $sel[168]>".t("Iltasiivo mit‰tˆi 7 p‰iv‰‰ vanhoja kesken olevia extranet-tilauksia")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "extranet_tilaus_varaa_saldoa") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Extranet-tilaus varaa saldoa")."</option>";
		$ulos .= "<option value = '1' $sel[1]>".t("Extranet-tilaus varaa saldoa 1 tunnin")."</option>";
		$ulos .= "<option value = '8' $sel[8]>".t("Extranet-tilaus varaa saldoa 8 tuntia")."</option>";
		$ulos .= "<option value = '24' $sel[24]>".t("Extranet-tilaus varaa saldoa 24 tuntia")."</option>";
		$ulos .= "<option value = '48' $sel[48]>".t("Extranet-tilaus varaa saldoa 48 tuntia")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tuoteperhe_suoratoimitus") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Tuoteperheen lapsituotteet voidaan suoratoimittaa erikseen")."</option>";
		$ulos .= "<option value = 'M' $sel[M]>".t("Tuoteperheen lapsituotteet suoratoimitetaan yhdess‰ ja on sallittu vain myyntitilauksella")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Tuoteperheen lapsituotteet suoratoimitetaan yhdess‰ ja on sallittu myyntitilauksella ja extranetissa")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tuoteperheinfo_lahetteella") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Tuoteperheeseen sis‰ltyv‰t tuotteet listataan l‰hetteell‰ kaikkien tuoteperheeseen kuuluvien rivien kohdalla")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Tuoteperheeseen sis‰ltyvi‰ tuotteita EI listata l‰hetteell‰ kaikkien tuoteperheeseen kuuluvien rivien kohdalla")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tyomaarays_tulostus_lisarivit") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Tyˆm‰‰r‰yksen tulosteeseen ei lis‰t‰ tyhji‰ kirjoitusrivej‰")."</option>";
		$ulos .= "<option value = 'L' $sel[L]>".t("Tyˆm‰‰r‰yksen tulosteeseen lis‰t‰‰n tyhji‰ kirjoitusrivej‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tyomaarays_asennuskalenteri_muistutus") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Tyˆm‰‰r‰yksen asennuskalenterissa ei voi lis‰t‰ muistutusta")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Tyˆm‰‰r‰yksen asennuskalenterissa voi lis‰t‰ muistutuksen")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "saldovirhe_esto_laskutus") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Ei estet‰ laskutusta")."</option>";
		$ulos .= "<option value = 'H' $sel[H]>".t("Mik‰li laskutuksessa tuotteen varastosaldo v‰henee negatiiviseksi, hyl‰t‰‰n KAIKKI tilaukset, joilla on kyseist‰ tuotetta")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Sallitaan tilauksien laskutus luontij‰rjestyksess‰, kunnes tuotteen varastosaldo loppuu")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kehahinvirhe_esto_laskutus") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Ei estet‰ laskutusta")."</option>";
		$ulos .= "<option value = 'N' $sel[N]>".t("Estet‰‰n laskutus mik‰li keskihankintahinta on nolla tai rivin kate on negatiivinen")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kirjanpidon_tarkenteet") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Ei tarkenteita velka-/saatavat-tileille")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Lis‰t‰‰n velka-/saatavat-tileille suurimman velan/saatavan tarkenteet")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "vaihda_asiakas_hintapaiv") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Myyntitilauksen hinnat (paitsi nettohinnat) p‰ivitet‰‰n kun vaihdetaan tilauksen asiakas")."</option>";
		$ulos .= "<option value = 'A' $sel[A]>".t("Myyntitilauksen hinnat (paitsi nettohinnat) p‰ivitet‰‰n vain jos uudella asiakkaalla on asiakashinta/-alennus kun vaihdetaan tilauksen asiakas")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tarjoustyyppi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = '0' $sel[0]>".t("Normaali tarjouspohja");
		$ulos .= "<option value = '2' $sel[2]>".t("Normaali tarjouspohja ei ALV");
		$ulos .= "<option value = '1' $sel[1]>".t("Pelkistetty tarjouspohja");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "hyvaksy_tarjous_tilaustyyppi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Hyv‰ksytyst‰ tarjouksesta luodaan oletuksena myyntitilaus");
		$ulos .= "<option value = 'T' $sel[T]>".t("Hyv‰ksytyst‰ tarjouksesta luodaan oletuksena tyˆm‰‰r‰ys");
		$ulos .= "<option value = 'P' $sel[P]>".t("Hyv‰ksytyst‰ tarjouksesta luodaan oletuksena projekti");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tuoteperhe_kasittely") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Tuoteperheiden is‰-/lapsituote kombinaatiot ovat uniikkeja");
		$ulos .= "<option value = 'M' $sel[M]>".t("Tuoteperheisiin voi lis‰t‰ samaa tuotetta monesti");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "keraysaikarajaus") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = '1'>".t("Ker‰yslistan tulostusjonossa n‰ytet‰‰n seuraavana p‰iv‰n‰ ker‰tt‰v‰t tilaukset")."</option>";
		$ulos .= "<option value = '3' $sel[3]>".t("Ker‰yslistan tulostusjonossa n‰ytet‰‰n seuraavan %s p‰iv‰n aikana ker‰tt‰v‰t tilaukset", "", "3")."</option>";
		$ulos .= "<option value = '5' $sel[5]>".t("Ker‰yslistan tulostusjonossa n‰ytet‰‰n seuraavan %s p‰iv‰n aikana ker‰tt‰v‰t tilaukset", "", "5")."</option>";
		$ulos .= "<option value = '7' $sel[7]>".t("Ker‰yslistan tulostusjonossa n‰ytet‰‰n seuraavan %s p‰iv‰n aikana ker‰tt‰v‰t tilaukset", "", "7")."</option>";
		$ulos .= "<option value = '14' $sel[14]>".t("Ker‰yslistan tulostusjonossa n‰ytet‰‰n seuraavan %s p‰iv‰n aikana ker‰tt‰v‰t tilaukset", "", "14")."</option>";
		$ulos .= "<option value = 'KK' $sel[KK]>".t("Ker‰yslistan tulostusjonossa n‰ytet‰‰n kaikki tilaukset")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "liitetiedostojen_nimeaminen") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Liitetiedostojen nimiin ei lis‰t‰ asiakkaan nime‰")."</option>";
		$ulos .= "<option value = 'N' $sel[N]>".t("Liitetiedostojen nimiin lis‰t‰‰n asiakkaan nimi")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ostoreskontra_kassaalekasittely") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = '0' $sel[0]>".t("Ostoreskontran maksatuksessa k‰ytet‰‰n automaattisesti kassa-alennus vain kassa-alennusp‰iv‰‰n menness‰");
		$ulos .= "<option value = '1' $sel[1]>".t("Ostoreskontran maksatuksessa k‰ytet‰‰n automaattisesti kassa-alennus, vaikka kassa-alennus on er‰‰ntynyt p‰iv‰ sitten");
		$ulos .= "<option value = '2' $sel[2]>".t("Ostoreskontran maksatuksessa k‰ytet‰‰n automaattisesti kassa-alennus, vaikka kassa-alennus on er‰‰ntynyt kaksi p‰iv‰‰ sitten");
		$ulos .= "<option value = '3' $sel[3]>".t("Ostoreskontran maksatuksessa k‰ytet‰‰n automaattisesti kassa-alennus, vaikka kassa-alennus on er‰‰ntynyt kolme p‰iv‰‰ sitten");
		$ulos .= "<option value = '4' $sel[4]>".t("Ostoreskontran maksatuksessa k‰ytet‰‰n automaattisesti kassa-alennus, vaikka kassa-alennus on er‰‰ntynyt nelj‰ p‰iv‰‰ sitten");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myyntilaskun_erapvmlaskenta") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = "";

		if ($trow[$i] != "") {
			$sel = "selected";
		}

		$ulos .= "<option value = ''>".t("Myyntilaskun er‰p‰iv‰ lasketaan aina laskun tapahtumap‰iv‰m‰‰r‰st‰");
		$ulos .= "<option value = 'K' $sel>".t("Myyntilaskun er‰p‰iv‰ voidaan laskea myˆs laskutushetkest‰ kun laskulle syˆtet‰‰n poikkeava tapahtumap‰iv‰m‰‰r‰");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "varastoonvientipaiva") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = "";

		if ($trow[$i] != "") {
			$sel = "selected";
		}

		$ulos .= "<option value = ''>".t("Tavaran varastoonvientip‰iv‰ on saapumisen varastoonvientip‰iv‰");
		$ulos .= "<option value = 'K' $sel>".t("Tavaran varastoonvientip‰iv‰ voidaan syˆtt‰‰ saapumisen varastoonvienniss‰");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "varastopaikkojen_maarittely") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = " selected";

		$ulos .= "<option value = ''>".t("Varastopaikkoja, ja niiden tilavuuksia, ei tarvitse m‰‰ritell‰ etuk‰teen")."</option>";
		$ulos .= "<option value = 'M'{$sel['M']}>".t("Varastopaikat, ja niiden tilavuudet, t‰ytyy m‰‰ritell‰ etuk‰teen")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "suuntalavat") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = " selected";

		$ulos .= "<option value = ''>".t("Suuntalavoja ei k‰ytet‰ tuloutuksessa")."</option>";
		$ulos .= "<option value = 'S'{$sel['S']}>".t("Suuntalavoja k‰ytet‰‰n tuloutuksessa")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kerayserat") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel[$trow[$i]] = " selected";

		$ulos .= "<option value = ''>".t("Ker‰yseri‰ ei k‰ytet‰ ker‰yksess‰")."</option>";
		$ulos .= "<option value = 'K'{$sel['K']}>".t("Ker‰yseri‰ k‰ytet‰‰n ker‰yksess‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myyntiera_pyoristys" ) {

		$sela = $selb = "";

		if ($trow[$i] == 'K') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value = ' ' $sela>".t("Myyntirivin m‰‰r‰‰ ei pyˆristet‰ seuraavaan t‰yteen myyntier‰‰n")."</option>
					<option value = 'K' $selb>".t("Myyntirivin m‰‰r‰ pyˆristet‰‰n seuraavaan t‰yteen myyntier‰‰n")."</option>
				</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ostoera_pyoristys" ) {

		$sela = $selb = "";

		if ($trow[$i] == 'K') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value = ' ' $sela>".t("Ostorivin m‰‰r‰‰ ei pyˆristet‰ seuraavaan t‰yteen ostoer‰‰n")."</option>
					<option value = 'K' $selb>".t("Ostorivin m‰‰r‰ pyˆristet‰‰n seuraavaan t‰yteen ostoer‰‰n")."</option>
				</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "reklamaation_kasittely") {

		$sela = $selb = "";

		if ($trow[$i] == 'U') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value = '' $sela>".t("Reklamaation k‰sittelyss‰ k‰ytet‰‰n lyhyemp‰‰ prosessia")."</option>
					<option value = 'U' $selb>".t("Reklamaation k‰sittelyss‰ k‰ytet‰‰n laajempaa prosessia vastaanotolla, purkulistalla ja vastaanottokuittauksella")."</option>
				</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "reklamaation_kasittely_tuoteperhe") {

		$sela = $selb = "";

		if ($trow[$i] == 'U') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value = '' $sela>".t("Reklamaatiolla k‰ytet‰‰n tuoteperheit‰")."</option>
					<option value = 'U' $selb>".t("Reklamaatiolla ei k‰ytet‰ tuoteperheit‰")."</option>
				</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "reklamaation_hinnoittelu") {

		$sela = $selb = "";

		if ($trow[$i] == 'K') {
			$selb = "selected";
		}
		else {
			$sela = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value = '' $sela>".t("Reklamaatiot hinnoitellaan manuaalisesti")."</option>
					<option value = 'K' $selb>".t("Reklamaation hinnoittelus‰‰nnˆt tulee tallentaa etuk‰teen")."</option>
				</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "vak_kasittely") {

		$sel = "";

		if ($trow[$i] != '') {
			$sel = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value = ''>".t("Normaali VAK-k‰sittely")."</option>
					<option value = 'P' $sel>".t("K‰ytet‰‰n VAK-tietokantaa ja DGD-lomaketta")."</option>
				</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "vak_erittely") {

		$sel = "";

		if ($trow[$i] != '') {
			$sel = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value = ''>".t("Ei tulosteta VAK/ADR-erittely‰ l‰hetteen kanssa")."</option>
					<option value = 'K' $sel>".t("Tulostetaan VAK/ADR-erittely l‰hetteen tulostuksen kanssa")."</option>
				</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "reklamaation_vastaanottovarasto" ) {
		$query = "	SELECT tunnus, nimitys
					FROM varastopaikat
					WHERE yhtio='$kukarow[yhtio]'
					ORDER BY nimitys";
		$sresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value=' '>".t("Ei oletusvarastoa valittu")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow['tunnus']) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[tunnus]' $sel>".$srow['nimitys']."</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myynnin_alekentat") {

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		// Jos lis‰‰t uuden alennuskent‰n, lis‰‰ se myˆs myynnin_alekentat_muokkaus parametriin
		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
		$ulos .= "<option value = '1'{$sel[1]}>".t("Myynniss‰ k‰ytet‰‰n yht‰ alennuskentt‰‰")."</option>";
		$ulos .= "<option value = '2'{$sel[2]}>".t("Myynniss‰ k‰ytet‰‰n kahta alennuskentt‰‰")."</option>";
		$ulos .= "<option value = '3'{$sel[3]}>".t("Myynniss‰ k‰ytet‰‰n kolmea alennuskentt‰‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myynnin_alekentat_muokkaus") {
		$ulos = "<td>";

 		$sel = array_fill_keys(explode(",", $trow[$i]), " checked") + array_fill(1, 3, '');

		$ulos .= "<input type='hidden' name='{$nimi}[]' value='default' />";

		$ulos .= "<input type='checkbox' name='{$nimi}[]' value='1'{$sel[1]}>".t("Ale1 muokkaus")."<br />";
		$ulos .= "<input type='checkbox' name='{$nimi}[]' value='2'{$sel[2]}>".t("Ale2 muokkaus")."<br />";
		$ulos .= "<input type='checkbox' name='{$nimi}[]' value='3'{$sel[3]}>".t("Ale3 muokkaus")."<br />";

		$ulos .= "</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "oston_alekentat") {

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		// Jos lis‰‰t uuden alennuskent‰n, lis‰‰ se myˆs myynnin_alekentat_muokkaus parametriin
		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
		$ulos .= "<option value = '1'{$sel[1]}>".t("Ostossa k‰ytet‰‰n yht‰ alennuskentt‰‰")."</option>";
		$ulos .= "<option value = '2'{$sel[2]}>".t("Ostossa k‰ytet‰‰n kahta alennuskentt‰‰")."</option>";
		$ulos .= "<option value = '3'{$sel[3]}>".t("Ostossa k‰ytet‰‰n kolmea alennuskentt‰‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "intrastat_kaytossa") {

		$apu = $trow[$i];
		$sel = array($apu => "selected");

		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
		$ulos .= "<option value = ''>".t("Viennin intrastattia k‰ytet‰‰n")."</option>";
		$ulos .= "<option value = 'V'{$sel['V']}>".t("Viennin intrastattia ei k‰ytet‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myynti_asiakhin_tallenna") {

		$sel = "";

		if ($trow[$i] != '') {
			$sel = "selected";
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
					<option value = ''>".t("Myyntitilauksella ei voi tallentaa asiakashintoja")."</option>
					<option value = 'K' $sel>".t("Myyntitilauksen hinnat voi tallentaa asiakkaalle asiakashinnoiksi")."</option>
				</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "haejaselaa_saapumispvm") {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("N‰ytet‰‰n jos miss‰‰n varastossa ei ole saldoa");
		$ulos .= "<option value = 'A' $sel[A]>".t("Seuraava saapuminen n‰ytet‰‰n aina");
		$ulos .= "<option value = 'B' $sel[B]>".t("N‰ytet‰‰n jos normivarastossa ei saldoa");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	// t‰ll‰ ominaisuudella voidaan yhdest‰ tilausrivist‰ tehd‰ monta tilausta
	// jos tilausrivill‰ on kappaleena 5, niin siint‰ tehd‰‰n 5 tilausta ja kussakin tilauksessa yksi kappale kyseist‰ tuotetta
	if (mysql_field_name($result, $i) == "tilausrivi_omalle_tilaukselle") {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'K' ? " selected" : '';

		$ulos .= "<option value = ''>".t("Myynniss‰ tilausrivi‰ ei voi pakottaa uudelle tilaukselle")."</option>";
		$ulos .= "<option value = 'K'{$sel}>".t("Myynniss‰ tilausrivin voi pakottaa uudelle tilaukselle")." (".t("tilaus per kpl").")</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jalkilaskenta_kuluperuste") {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Virallisen varastonarvon laskentaan k‰ytet‰‰n saapumiseen liitettyjen huolinta-/rahtilaskujen summaa")."</option>";
		$ulos .= "<option value = 'KP' {$sel["KP"]}>".t("Virallisen varastonarvon laskentaan k‰ytet‰‰n saapumiselle syˆtetty‰ kulusummaa/kuluprosenttia")."</option>";
		$ulos .= "<option value = 'PX' {$sel["PX"]}>".t("Virallisen varastonarvon laskentaan k‰ytet‰‰n kululaskujen summaa, mik‰li kululaskut on syˆtetty, muuten kulusummaa/kuluprosenttia")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'varaston_splittaus') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'K' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("Myyd‰‰n ensin oletuspaikka tyhj‰ksi ja k‰ytet‰‰n sen j‰lkeen muita tuotepaikkoja saldoj‰rjestyksess‰")."</option>";
		$ulos .= "<option value = 'K' {$sel}>".t("Yritet‰‰n myyd‰ kaikki saman varaston sis‰lt‰, ensisijaisesti oletuspaikan varastosta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == 'siirtolistatyyppi') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'A' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("Siirtolistalla ei n‰ytet‰ tuotteen myyntihintaa")."</option>";
		$ulos .= "<option value = 'A' {$sel}>".t("Siirtolistalla n‰ytet‰‰n tuotteen myyntihinta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'tuotteiden_jarjestys_raportoinnissa') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'V' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("Tuotenumeron mukaan");
		$ulos .= "<option value = 'V' $sel>".t("Variaation, v‰rin ja koon mukaan");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'ennakkotilausten_toimitus') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'M' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("Ennakkotilaukset toimitetaan Ennakko-selaus ohjelmalla");
		$ulos .= "<option value = 'M' $sel>".t("Ennakkotilaukset toimitetaan tilauskittain ja/tai Ennakko-selaus ohjelmalla");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'raaka_aineet_valmistusmyynti') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'N' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("N‰ytet‰‰n raaka-aineet valmistusmyynniss‰ (asiakkaallevalmistus)");
		$ulos .= "<option value = 'N' $sel>".t("Ei n‰ytet‰ raaka-aineita valmistusmyynniss‰ (asiakkaallevalmistus)");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'tarkista_eankoodi') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'K' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("Ei tehd‰ tuotteen EAN13-koodin oikeellisuustarkistusta")."</option>";
		$ulos .= "<option value = 'K' $sel>".t("Tarkistetaan tuotteen EAN13-koodin oikeellisuus")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'teeostotilaus_valmistuksen_tulosjonosta') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'K' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("Valmistusten tulostusjonosta ei luoda ostotilauksia");
		$ulos .= "<option value = 'K' $sel>".t("Valmistusten tulostusjonosta voi luoda ostotilauksia");
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'maksukehotus_kentat') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'K' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("N‰ytet‰‰n viimeisin muistutusp‰iv‰ ja perint‰kerrat")."</option>";
		$ulos .= "<option value = 'K' $sel>".t("Ei n‰ytet‰ viimeisint‰ muistutusp‰iv‰‰ eik‰ perint‰kertoja")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'viitemaksujen_kohdistus_sallittu_heitto') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = $trow[$i]*100;
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = '0.00' {$sel[0]}>0.00</option>";
		$ulos .= "<option value = '0.05' {$sel[5]}>0.05</option>";
		$ulos .= "<option value = '0.10' {$sel[10]}>0.10</option>";
		$ulos .= "<option value = '0.15' {$sel[15]}>0.15</option>";
		$ulos .= "<option value = '0.20' {$sel[20]}>0.20</option>";
		$ulos .= "<option value = '0.25' {$sel[25]}>0.25</option>";
		$ulos .= "<option value = '0.30' {$sel[30]}>0.30</option>";
		$ulos .= "<option value = '0.35' {$sel[35]}>0.35</option>";
		$ulos .= "<option value = '0.40' {$sel[40]}>0.40</option>";
		$ulos .= "<option value = '0.45' {$sel[45]}>0.45</option>";
		$ulos .= "<option value = '0.50' {$sel[50]}>0.50</option>";
		$ulos .= "<option value = '0.55' {$sel[55]}>0.55</option>";
		$ulos .= "<option value = '0.60' {$sel[60]}>0.60</option>";
		$ulos .= "<option value = '0.65' {$sel[65]}>0.65</option>";
		$ulos .= "<option value = '0.70' {$sel[70]}>0.70</option>";
		$ulos .= "<option value = '0.75' {$sel[75]}>0.75</option>";
		$ulos .= "<option value = '0.80' {$sel[80]}>0.80</option>";
		$ulos .= "<option value = '0.85' {$sel[85]}>0.85</option>";
		$ulos .= "<option value = '0.90' {$sel[90]}>0.90</option>";
		$ulos .= "<option value = '0.95' {$sel[95]}>0.95</option>";
		$ulos .= "<option value = '1.00' {$sel[100]}>1.00</option>";

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'luottorajan_ylitys') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Myynti‰ ei estet‰, vaikka luottoraja on ylitetty")."</option>";
		$ulos .= "<option value = 'K' {$sel["K"]}>".t("Kaikki myynti estet‰‰n, jos luottoraja on ylitetty")."</option>";
		$ulos .= "<option value = 'E' {$sel["E"]}>".t("Muu kuin myyjien tekem‰ myynti estet‰‰n, jos luottoraja on ylitetty")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'naytayhteensamaarat') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'K' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("Ei n‰ytet‰ kokonaistuotem‰‰r‰‰ l‰hetteell‰, tilausvahvistuksella eik‰ laskulla")."</option>";
		$ulos .= "<option value = 'K' $sel>".t("N‰ytet‰‰n kokonaistuotem‰‰r‰ l‰hetteell‰, tilausvahvistuksella sek‰ laskulla")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'vastaanottoraportti') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'K' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("Vastaanottoraportissa ei n‰ytet‰ keskihankintahintoja")."</option>";
		$ulos .= "<option value = 'K' $sel>".t("Vastaanottoraportissa n‰ytet‰‰n keskihankintahinnat")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "maventa_aikaleima") {
		$tyyppi = 3;
	}

	if (mysql_field_name($result, $i) == 'maksukehotuksen_osoitetiedot') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'K' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("K‰ytet‰‰n asiakkaan virallista osoitetta")."</option>";
		$ulos .= "<option value = 'K' $sel>".t("K‰ytet‰‰n asiakkaan laskutusosoitetta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'ostolaskun_kulutilit') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array_fill_keys(explode(",", $trow[$i]), " selected") + array_fill(1, 3, '');

		$ulos .= "<option value = ''>".t("Ei k‰ytet‰ vaihto-omaisuuslaskun kulutilej‰")."</option>";
		$ulos .= "<option value = '1' {$sel[1]}>".t("K‰ytet‰‰n vaihto-omaisuuslaskun rahdit tili‰")."</option>";
		$ulos .= "<option value = '2' {$sel[2]}>".t("K‰ytet‰‰n vaihto-omaisuuslaskun rahdit ja -kulut tilej‰")."</option>";
		$ulos .= "<option value = '3' {$sel[3]}>".t("K‰ytet‰‰n vaihto-omaisuuslaskun rahdit, -kulut ja -tuoterivikohtaiset kulut tilej‰")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'maksusopimus_toimitus') {
		$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == "X" ? "selected" : "";

		$ulos .= "<option value = ''>".t("Tilauksen voi toimittaa vaikka ennakkolaskuja ei ole maksettu")."</option>";
		$ulos .= "<option value = 'X' {$sel}>".t("Tilauksen voi toimittaa vasta kun ennakkolaskut on maksettu")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'tositteen_tilioinnit') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = "";
		if ($trow[$i] != "") $sel = "SELECTED";

		$ulos .= "<option value = ''>".t("Tositteella n‰ytet‰‰n oletuksena kaikki tiliˆintirivit")."</option>";
		$ulos .= "<option value = 'X' {$sel}>".t("Tositteella n‰ytet‰‰n tiliˆintirivit summattuna per tili/pvm/tarkenne")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'ei_alennuksia_lapsituotteille') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = $trow[$i] == 'X' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("Tuoteperheiden lapsituotteille lasketaan asiakashinta ja -alennus")."</option>";
		$ulos .= "<option value = 'X' $sel>".t("Tuoteperheiden lapsituotteille ei lasketa asiakashintoja eik‰ -alennuksia")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;

	}

	if (mysql_field_name($result, $i) == 'epakurantoinnin_myyntihintaleikkuri') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$sel['Z'] = $trow[$i] == 'Z' ? 'selected' : '';

		$ulos .= "<option value = ''>".t("Hintaa ei leikata")."</option>";
		$ulos .= "<option value = 'Z' {$sel["Z"]}>".t("Myyntihintaa leikataan 25% per taso")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;

	}
