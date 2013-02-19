<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "tilino" or
		mysql_field_name($result, $i) == "tilino_eu" or
		mysql_field_name($result, $i) == "tilino_ei_eu" or
		mysql_field_name($result, $i) == "tilino_kaanteinen" or
		mysql_field_name($result, $i) == "tilino_marginaali" or
		mysql_field_name($result, $i) == "tilino_osto_marginaali" or
		mysql_field_name($result, $i) == "tilino_triang") {

		$ulos = "<td>".livesearch_kentta("mainform", "TILIHAKU", $nimi, 270, $trow[$i], "EISUBMIT")."</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kustannuspaikka") { //kustannuspaikat
		$query = "	SELECT tunnus, nimi, koodi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and kaytossa != 'E'
					and tyyppi = 'K'
					ORDER BY koodi+0, koodi, nimi";
		$vresult = pupe_query($query);

		$sel="";
		if (trim($trow[$i]) == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300)."><option value='' $sel>".t("Ei kustannuspaikkaa")."";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";

			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kohde") { //kohteet
		$query = "	SELECT tunnus, nimi, koodi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]'
						and kaytossa != 'E'
						and tyyppi = 'O'
						ORDER BY koodi+0, koodi, nimi";
		$vresult = pupe_query($query);

		$sel="";
		if (trim($trow[$i]) == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300)."><option value='' $sel>".t("Ei kohdetta")."";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "projekti") { //projektit

		$query = "	SELECT tunnus, nimi, koodi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and kaytossa != 'E'
					and tyyppi = 'P'
					ORDER BY koodi+0, koodi, nimi";
		$vresult = pupe_query($query);

		$sel="";
		if (trim($trow[$i]) == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300)."><option value='' $sel>".t("Ei projektia")."";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;

	}

	if (mysql_field_name($result, $i) == "chn") {

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value='100' $sel[100]>".t("Verkkolasku, tulostuspalvelu")."</option>";
		$ulos .= "<option value='010' $sel[010]>".t("Verkkolasku")."</option>";
		#$ulos .= "<option value='001' $sel[001]>".t("Itella Pupevoice: EDI, sähköinen lasku")."</option>";
		$ulos .= "<option value='020' $sel[020]>".t("Vienti-Verkkolasku (EU)")."</option>";
		$ulos .= "<option value='030' $sel[030]>".t("Vienti-Verkkolasku, tulostuspalvelu (EU)")."</option>";
		$ulos .= "<option value='111' $sel[111]>".t("Itella EDI: EIH-1.4 sähköinen lasku")."</option>";
		$ulos .= "<option value='112' $sel[112]>".t("Pupesoft-Finvoice: Verkkolasku Pupesoftista-Pupesoftiin")."</option>";
		$ulos .= "<option value='666' $sel[666]>".t("Sähköposti")."</option>";
		$ulos .= "<option value='667' $sel[667]>".t("Paperilasku, tulostetaan manuaalisesti")."</option>";
		$ulos .= "<option value='999' $sel[999]>".t("Laskutuskielto, laskutusta ei tehdä")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laatija" or
		mysql_field_name($result, $i) == "muutospvm" or
		mysql_field_name($result, $i) == "luontiaika") {
		$tyyppi = 2;
	}

	if (mysql_field_name($result, $i) == "laji") {

		$sel  = '';
		$sel1 = '';
		$sel2 = '';
		$sel3 = '';

		if(!isset($trow[$i])) {
			$trow[$i] = $laji;
		}

		if (strtoupper($trow[$i]) == 'H') {
			$sel1 = "SELECTED";
		}
		elseif (strtoupper($trow[$i]) == 'R') {
			$sel3 = "SELECTED";
		}
		elseif (strtoupper($trow[$i]) == 'P') {
			$sel2 = "SELECTED";
		}
		elseif (strtoupper($trow[$i]) == 'K') {
			$sel4 = "SELECTED";
		}
		else {
			$sel = "SELECTED";
		}

		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' $sel>".t("Yritys")."</option>";
		$ulos .= "<option value = 'K' $sel4>".t("Korjaamo")."</option>";
		$ulos .= "<option value = 'H' $sel1>".t("Yksityishenkilö")."</option>";
		$ulos .= "<option value = 'R' $sel3>".t("Prospekti")."</option>";
		$ulos .= "<option value = 'P' $sel2>".t("Poistettu / Ei käytössä")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "konserniyhtio") {

		if ($yhtiorow["konserni"] != "" or $trow[$i] != '') {
			$sela = $selb = '';
			if ($trow[$i] != '') {
				$selb = "SELECTED";
			}
			else {
				$sela = "SELECTED";
			}

			$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
			$ulos .= "<option value = '' $sela>".t("Ei")."</option>";
			$ulos .= "<option value = 'o' $selb>".t("Kyllä")."</option>";
			$ulos .= "</select></td>";
			$jatko = 0;
		}
		else {
			$tyyppi = 0;
		}
	}

	if (mysql_field_name($result, $i) == "maksuehto") {
		$query = "	SELECT *
					FROM maksuehto
					WHERE yhtio  = '$kukarow[yhtio]'
					and kaytossa = ''
					ORDER BY jarjestys, teksti";
		$vresult = pupe_query($query);

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		while ($vrow = mysql_fetch_assoc($vresult)) {
			$sel="";
			$sallitut_maat = "";

			if ($trow[$i] == $vrow["tunnus"]) {
				$sel = "selected";
			}

			if ($vrow["sallitut_maat"] != "") {
				$sallitut_maat = "($vrow[sallitut_maat])";
			}

			$ulos .= "<option value = '$vrow[tunnus]' $sel>".t_tunnus_avainsanat($vrow, "teksti", "MAKSUEHTOKV")." $sallitut_maat</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakasnro") {
		$ulos  = "<td>";
		$ulos .= "<div id='asiakasnumero_etsi'>";

		if ($yhtiorow["automaattinen_asiakasnumerointi"] != "" and $trow[$i] == "") {
			if ($yhtiorow["asiakasnumeroinnin_aloituskohta"] != "") {
				$apu_asiakasnumero = $yhtiorow["asiakasnumeroinnin_aloituskohta"];
			}
			else {
				$apu_asiakasnumero = 0;
			}

			//jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksilöivät tiedot on oltava konsernitasolla-yksilölliset
			$query = "	SELECT *
						FROM yhtio
						JOIN yhtion_parametrit ON yhtion_parametrit.yhtio = yhtio.yhtio
						where konserni = '$yhtiorow[konserni]'
						and (synkronoi = '$toim' or synkronoi like '$toim,%' or synkronoi like '%,$toim,%' or synkronoi like '%,$toim')";
			$vresult = pupe_query($query);

			if (mysql_num_rows($vresult) > 0) {
				// haetaan konsernifirmat
				$query = "	SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
							FROM yhtio
							JOIN yhtion_parametrit ON yhtion_parametrit.yhtio = yhtio.yhtio
							where konserni = '$yhtiorow[konserni]'
							and (synkronoi = '$toim' or synkronoi like '$toim,%' or synkronoi like '%,$toim,%' or synkronoi like '%,$toim')";
				$vresult = pupe_query($query);
				$srowapu = mysql_fetch_array($vresult);
				$tarkyhtio = $srowapu["yhtiot"];
			}
			else {
				$tarkyhtio = "'$kukarow[yhtio]'";
			}

			$query = "	SELECT MAX(asiakasnro+0) asiakasnro
						FROM asiakas USE INDEX (asno_index)
						WHERE yhtio in ($tarkyhtio)
						AND asiakasnro+0 >= $apu_asiakasnumero";
			$vresult = pupe_query($query);
			$vrow = mysql_fetch_assoc($vresult);

			if ($vrow['asiakasnro'] != '') {
				$apu_asiakasnumero = $vrow['asiakasnro'] + 1;
			}

			$trow[$i] = $apu_asiakasnumero;
		}

		$ulos .= "<input type='text' name='$nimi' id='$nimi' value='$trow[$i]'>";
		$ulos .= "<input type='button' name='etsi_vapasno' id='etsi_vapasno' value='".t("Seuraava vapaa")."' onClick='javascript:document.getElementById(\"etsi_vapasno\").value = \"".t("Hakua suoritetaan").".\";javascript:document.getElementById(\"$nimi\").disabled = true;javascript:document.getElementById(\"etsi_vapasno\").disabled = true;javascript:ajaxPost(\"mainform\", \"?haku=asiakasnro&ohje=off&pointer=$i\", \"asiakasnumero_etsi\", false, false)'>";
		$ulos .= "</div>";
		$ulos .= "</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ytunnus") {

		$ulos = "<td>";
		$ulos .= "<div id='ytunnus_etsi'>";
		$ulos .= "<input type='text' name='$nimi' id='$nimi' value='$trow[$i]'>";

		if ($alias_set == "VAHITTAISMYYNTI" or $laji == "R") {
			$ulos .= "<input type='button' name='etsi_vapytunnus' id='etsi_vapytunnus' value='".t("Seuraava vapaa")."' onClick='javascript:document.getElementById(\"etsi_vapytunnus\").value = \"".t("Hakua suoritetaan").".\";javascript:document.getElementById(\"$nimi\").disabled = true;javascript:document.getElementById(\"etsi_vapytunnus\").disabled = true;javascript:ajaxPost(\"mainform\", \"?haku=ytunnus&ohje=off&pointer=$i\", \"ytunnus_etsi\", false, false)'>";
		}

		if ($alias_set != "VAHITTAISMYYNTI") {
			$ulos .= " <a target='VIES' href='http://ec.europa.eu/taxation_customs/vies/?locale=".strtolower($kukarow["kieli"])."'>".t("Tarkista VIES")."</a>";
			$ulos .= " <a target='YTJ' href='http://www.ytj.fi/yrityshaku.aspx?path=1547%3b1631%3b1678&kielikoodi=1".strtoupper($kukarow["kieli"])."'>".t("Tarkista YTJ")."</a>";
		}

		$ulos .= "</div>";
		$ulos .= "</td>";
		$jatko = 0;
	}


	if (mysql_field_name($result, $i) == "maa" or
		mysql_field_name($result, $i) == "kansalaisuus" or
		mysql_field_name($result, $i) == "kolm_maa" or
		mysql_field_name($result, $i) == "toim_maa" or
		mysql_field_name($result, $i) == "laskutus_maa") {

		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = pupe_query($query);
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		$ulos .= "<option value = '' ></option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
				$sel = "selected";
			}
			elseif($trow[$i] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["maa"])) {
				$sel = "selected";
			}
			$ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "toimitustapa") {
		$query = "	SELECT *
					FROM toimitustapa
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY jarjestys, selite";
		$vresult = pupe_query($query);
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>".t_tunnus_avainsanat($vrow, "selite", "TOIMTAPAKV")."</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "alv") {

		//yhtiön oletusalvi!
		$wquery = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
		$wtres  = pupe_query($wquery);
		$wtrow  = mysql_fetch_array($wtres);

		$sel_veroton 	= "";
		$sel_verollinen = "";

		if (isset($trow[$i])) {
			if ($trow[$i] == 0) {
				$sel_veroton 	= 'selected';
				$sel_verollinen ='';
			}
			if ($trow[$i] == $wtrow["selite"]) {
				$sel_verollinen = 'selected';
				$sel_veroton 	= '';
			}
		}

		$ulos = " 	<td>
					<select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">
					<option value='$wtrow[selite]' $sel_verollinen>".t("Verollinen myynti")."</option>
					<option value='0' $sel_veroton>".t("Veroton myynti")."</option>
					</select>
					</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laskutusvkopv") {
			$sel		= array();
			$pv			= $trow[$i];
			$sel[$pv]	= " selected";

			$ulos = "<td>
				<select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">
				<option value='0'  {$sel[0]}>".t("Kaikki päivät")."</option>
				<option value='2'  {$sel[2]}>".t("Maanantai")."</option>
				<option value='3'  {$sel[3]}>".t("Tiistai")."</option>
				<option value='4'  {$sel[4]}>".t("Keskiviikko")."</option>
				<option value='5'  {$sel[5]}>".t("Torstai")."</option>
				<option value='6'  {$sel[6]}>".t("Perjantai")."</option>
				<option value='7'  {$sel[7]}>".t("Lauantai")."</option>
				<option value='1'  {$sel[1]}>".t("Sunnuntai")."</option>
				<option value='-1' {$sel[-1]}>".t("Kuukauden viimeinen päivä")."</option>
				<option value='-2' {$sel[-2]}>".t("Kuukauden ensimmäinen päivä")."</option>
				<option value='-3' {$sel[-3]}>".t("Kuukauden keskimmäinen päivä")."</option>
				<option value='-4' {$sel[-4]}>".t("Kuukauden keskimmäinen ja viimeinen päivä")."</option>
				<option value='-5' {$sel[-5]}>".t("Kuukauden ensimmäinen ja keskimmäinen päivä")."</option>
				<option value='9'  {$sel[9]}>".t("Laskut lähetetään vain ohittamalla laskutusvkopv")."</option>
				</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lahetetyyppi") {
		$vresult = t_avainsana("LAHETETYYPPI");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value=''>".t("Oletus")."</option>";

		while($row = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($row["selite"] == $trow[$i]) $sel = 'selected';
			$ulos .= "<option value='$row[selite]' $sel>$row[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilausvahvistus") {
			$vresult = t_avainsana("TV");

			$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300)."><option value=' '>".t("Ei vahvistusta")."</option>";

			while($row = mysql_fetch_array($vresult)) {
				$sel = "";
				if ($row["selite"] == $trow[$i]) $sel = 'selected';
				$ulos .= "<option value='$row[selite]' $sel>$row[selitetark]</option>";
			}

			$ulos .= "</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilausvahvistus_jttoimituksista") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Käytetään yhtiön oletusta")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("JT-toimituksista ei lähetetä tilausvahvistusta")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("JT-toimituksista lähetetään tilausvahvistus")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "toimitusvahvistus") {
			$vresult = t_avainsana("TOIMVAHVISTUS");

			$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300)."><option value=''>".t("Ei lähetetä")."</option>";

			while($row = mysql_fetch_array($vresult)) {
				$sel = "";
				if ($row["selite"] == $trow[$i]) $sel = 'selected';
				$ulos .= "<option value='$row[selite]' $sel>$row[selitetark]</option>";
			}

			$ulos .= "</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilausvahvistus_yhteyshenkilolle") {


		if ($trow[$i] == "K") {
			$sel = "SELECTED";
		}


		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value='' >".t("Ei lähetetä")."</option>";
		$ulos .= "<option value='K' $sel>".t("Lähetetään")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kerayspoikkeama") {
			$sel			= array();

			if ($trow[$i] == "") {
				$sel[$yhtiorow["kerayspoikkeamaviestin_lahetys"]] = " selected";
			}
			else {
				$sel[$trow[$i]]	= " selected";
			}

			$ulos = "<td>
				<select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">
				<option value='0' $sel[0]>".t("Lähetetään asiakkaalle ja myyjälle")."</option>
				<option value='2' $sel[2]>".t("Lähetetään vain myyjälle")."</option>
				<option value='1' $sel[1]>".t("Ei lähetetä ollenkaan")."</option>
				</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "toimitusehto") {
		$kires = t_avainsana("TOIMEHTO");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value=''>".t("Ei oletustoimitusehtoa")."</option>";

		while ($kirow = mysql_fetch_array($kires)) {
			if (trim($kirow["selite"]." ".$kirow["selitetark"]) == $trow[$i]) $select = 'SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[selite] $kirow[selitetark]' $select>$kirow[selite] $kirow[selitetark]</option>";
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jtkielto") {

		$jtksel[$trow[$i]] = "SELECTED";

		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = ''>".t("Asiakkaalle lähetetään jälkitoimituksia normaalisti")."</option>";
		$ulos .= "<option value = 'Y' $jtksel[Y]>".t("Asiakkaan jälkitoimituksia ei yhdistetä JT-selauksessa")."</option>";
		$ulos .= "<option value = 'o' $jtksel[o]>".t("Asiakkaalle ei läheteä jälkitoimituksia")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jtrivit") {
			$sel		= array();
			$pv			= $trow[$i];
			$sel[$pv]	= " selected";

			$ulos = "<td>
				<select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">
				<option value='0' $sel[0]>".t("Näytetään")."</option>
				<option value='1' $sel[1]>".t("Ei näytetä")."</option>
				</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "vienti") {

		$sel1 = '';
		$sel2 = '';
		$sel3 = '';

		if ($trow[$i] == '') {
			$sel1 = 'SELECTED';
		}
		if ($trow[$i] == 'E') {
			$sel2 = 'SELECTED';
		}
		if ($trow[$i] == 'K') {
			$sel3 = 'SELECTED';
		}

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">
				<option value=''  $sel1>".t("Kotimaa")."</option>
				<option value='E' $sel2>".t("Vienti EU")."</option>
				<option value='K' $sel3>".t("Vienti ei-EU")."</option>
				</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "luokka") {
			$vresult = t_avainsana("ASIAKASLUOKKA");

			$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300)."><option value=' '>".t("Ei luokkaa")."</option>";

			while($row = mysql_fetch_array($vresult)) {
				$sel = "";
				if ($row["selite"] == $trow[$i]) $sel = 'selected';
				$ulos .= "<option value='$row[selite]' $sel>$row[selite] - $row[selitetark]</option>";
			}

			$ulos .= "</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ketjutus") {

			$sela = $selb = '';
			if ($trow[$i]=='E') $selb='SELECTED';
			else $sela = "SELECTED";
			$ulos = "<td>
				<select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">
				<option value='' $sela>".t("Laskut saa ketjuttaa")."</option>
				<option value='E' $selb>".t("Ei ketjuteta laskuja")."</option>
				</select></td>";
			$jatko = 0;
	}

	if ((mysql_field_name($result, $i) == "fakta") or (mysql_field_name($result, $i) == "sisviesti1") or (mysql_field_name($result, $i) == "myynti_kommentti1")) {
		$ulos = "<td><textarea cols='45' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laskunsummapyoristys") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

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

	if (mysql_field_name($result, $i) == "laskutuslisa") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		$sel1="";
		$sel2="";

		if ($trow[$i] == "") {
			$sel1 = "selected";
		}
		else {
			$sel2 = "selected";
		}

		$ulos .= "<option value='' $sel1>".t("Yhtiön oletus")."</option>";
		$ulos .= "<option value='o' $sel2>".t("Ei lisätä")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kieli") {

			$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
			$sel  = "";

			$query  = "show columns from sanakirja";
			$fields =  mysql_query($query);

			while ($apurow = mysql_fetch_array($fields)) {
				if (strlen($apurow[0]) == 2) {
					$sel = "";
					if ($trow[$i] == $apurow[0]) {
						$sel = "selected";
					}
					elseif ($trow[$i] == "" and $apurow[0] == $yhtiorow["kieli"]) {
						$sel = "selected";
					}

					if ($apurow[0] != "tunnus") {
						$query = "select distinct nimi from maat where koodi='$apurow[0]'";
						$maare = mysql_query($query);
						$maaro = mysql_fetch_array($maare);
						$maa   = strtolower($maaro["nimi"]);
						if ($maa=="") $maa = $apurow[0];
						$ulos .= "<option value='$apurow[0]' $sel>".t("$maa")."</option>";
					}
				}
			}

			$ulos .= "</select></td>";

			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "osasto") {
		$vresult = t_avainsana("ASIAKASOSASTO");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Ei osastoa")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ryhma") {
		$vresult = t_avainsana("ASIAKASRYHMA");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Ei ryhmää")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}

			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tila") {
		$vresult = t_avainsana("ASIAKASTILA");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Ei tilaa")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}

			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myyjanro") {

		$query = "SELECT distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
		$vresult = pupe_query($query);

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Ei myyjää")."</option>";

		if ($lopetus != '' and (int) $tunnus == 0) {
			$kukamyyja = '';
			$query = "	SELECT myyja
						FROM kuka
						WHERE yhtio='$kukarow[yhtio]'
						and kuka = '$kukarow[kuka]'
						and myyja > 0";
			$myyjaresult = pupe_query($query);

			if (mysql_num_rows($myyjaresult) > 0) {
				$myyjarow  = mysql_fetch_array($myyjaresult);
				$kukamyyja = $myyjarow['myyja'];
			}
		}

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($trow[$i] == $vrow['myyja']) {
				$sel = "selected";
			}
			elseif ($kukamyyja != '' and $kukamyyja == $vrow['myyja']) {
				$sel = "selected";
			}

			$ulos .= "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "piiri") {
		$vresult = t_avainsana("PIIRI");

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Ei piiriä")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "sisamaan_kuljetusmuoto" or mysql_field_name($result, $i) == "kuljetusmuoto") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Valitse")."</option>";

		$vresult = t_avainsana("KM");

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kauppatapahtuman_luonne") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = '' >".t("Valitse")."</option>";

		$vresult = t_avainsana("KT");

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "poistumistoimipaikka_koodi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = ''>".t("Valitse")."</option>";

		$vresult = t_avainsana("TULLI");

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "valkoodi") {
		$query = "	SELECT nimi, tunnus
	                FROM valuu
	             	WHERE yhtio = '$kukarow[yhtio]'
	               	ORDER BY jarjestys";
		$vresult = pupe_query($query);

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['nimi']) {
					$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[nimi]' $sel>$vrow[nimi]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kontti") {

		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = ''>".t("Valitse")."</option>";

		$chk1 = '';
		$chk2 = '';

		if ($trow[$i] == "1") {
			$chk1 = 'selected';
		}
		if ($trow[$i] == "0") {
			$chk2 = 'selected';
		}

		$ulos .= "<option value='1' $chk1>".t("Kyllä")."</option>";
		$ulos .= "<option value='0' $chk2>".t("Ei")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rahtivapaa") {

		$chk1 = '';
		$chk2 = '';

		if ($trow[$i] != '') {
			$chk2 = 'selected';
			$chk1 = '';
		}
		else {
			$chk1 = 'selected';
			$chk2 = '';
		}

		$ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
		$ulos .= "<option value = ''$chk1>".t("Ei")."</option>";
		$ulos .= "<option value = 'o'$chk2>".t("Kyllä")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rahtivapaa_alarajasumma") {

		$ulos = "<td><input type='text' name='$nimi' id='$nimi' value='$trow[$i]' size='15'></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kuljetusvakuutus_tyyppi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Yhtiön/toimitustavan oletus")."</option>";
		$ulos .= "<option value = 'E' $sel[E]>".t("Ei kuljetusvakuutusta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laskutyyppi") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos .= "<option value = '-9'>".t("Oletus")."</option>";
		$ulos .= "<option value = '0' $sel[0]>".t("Normaali laskupohja");
		$ulos .= "<option value = '2' $sel[2]>".t("Normaali laskupohja (Tuoteperheet yhdistetty)");
		$ulos .= "<option value = '5' $sel[5]>".t("Normaali laskupohja (Ei näytetä verollista rivihintaa)")."</option>";
		$ulos .= "<option value = '6' $sel[6]>".t("Normaali laskupohja (Ei näytetä tilausnumeroa/toimitusaikaa)")."</option>";
		$ulos .= "<option value = '7' $sel[7]>".t("Normaali laskupohja (Näytetään tuotteen EAN koodi ja asiakaskommentti)")."</option>";
		$ulos .= "<option value = '1' $sel[1]>".t("Pelkistetty laskupohja 1");
		#$ulos .= "<option value = '3' $sel[3]>".t("Pelkistetty laskupohja 2 (Tuoteperheet yhdistetty)");
		$ulos .= "<option value = '4' $sel[4]>".t("Pelkistetty laskupohja 3");
		$ulos .= "<option value = '10' $sel[10]>".t("Normaali laskupohja ilman rivihintoja (ei laillinen Suomessa)");
		$ulos .= "<option value = '12' $sel[12]>".t("Normaali laskupohja (Tuoteperheet yhdistetty) ilman rivihintoja (ei laillinen Suomessa)");
		$ulos .= "<option value = '13' $sel[13]>".t("Nettohinnat, ei alennuksia, ei verollisia hintoja");
		$ulos .= "<option value = '14' $sel[14]>".t("Nettohinnat, ei alennuksia, ei verollisia hintoja (Tuoteperheet yhdistetty)");
		$ulos .= "<option value = '15' $sel[15]>".t("SVH, Nettohinnat, ei alennuksia, ei verollisia hintoja");
		$ulos .= "<option value = '16' $sel[16]>".t("SVH, Nettohinnat, ei alennuksia, ei verollisia hintoja (Tuoteperheet yhdistetty)");

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lahetteen_jarjestys" or
		mysql_field_name($result, $i) == "laskun_jarjestys") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = ''>".t("Yhtiön oletus")."</option>";
		$ulos .= "<option value = '0' $sel[0]>".t("Varastopaikkajärjestys, tuoteperheet pidetään yhdessä, erikoistuotteet loppuun")."</option>";
		$ulos .= "<option value = '1' $sel[1]>".t("Varastopaikkajärjestys, tuoteperheet pidetään yhdessä")."</option>";
		$ulos .= "<option value = '2' $sel[2]>".t("Varastopaikkajärjestys, erikoistuotteet loppuun")."</option>";
		$ulos .= "<option value = '3' $sel[3]>".t("Varastopaikkajärjestys")."</option>";
		$ulos .= "<option value = '4' $sel[4]>".t("Tuotenumerojärjestys, tuoteperheet pidetään yhdessä, erikoistuotteet loppuun")."</option>";
		$ulos .= "<option value = '5' $sel[5]>".t("Tuotenumerojärjestys, tuoteperheet pidetään yhdessä")."</option>";
		$ulos .= "<option value = '6' $sel[6]>".t("Tuotenumerojärjestys, erikoistuotteet loppuun")."</option>";
		$ulos .= "<option value = '7' $sel[7]>".t("Tuotenumerojärjestys")."</option>";
		$ulos .= "<option value = '8' $sel[8]>".t("Tilausjärjestys, tuoteperheet pidetään yhdessä")."</option>";
		$ulos .= "<option value = '9' $sel[9]>".t("Tilausjärjestys")."</option>";
		$ulos .= "<option value = 'M' $sel[M]>".t("Käyttäjän asettama järjestys")."</option>";

		if(	mysql_field_name($result, $i) == "valmistus_kerayslistan_jarjestys" or
			mysql_field_name($result, $i) == "kerayslistan_jarjestys") {
			$ulos .= "<option value = 'S' $sel[S]>".t("Rivit summattuina");
		}
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lahetteen_jarjestys_suunta" or
		mysql_field_name($result, $i) == "laskun_jarjestys_suunta") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos .= "<option value = ''>".t("Yhtiön oletus")."</option>";
		$ulos .= "<option value = 'ASC'  $sel[ASC]>".t("Nouseva")."</option>";
		$ulos .= "<option value = 'DESC' $sel[DESC]>".t("Laskeva")."</option>";

		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "extranet_tilaus_varaa_saldoa") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Yhtiön oletus")."</option>";
		$ulos .= "<option value = 'X'>".t("Extranet-tilaus varaa saldoa")."</option>";
		$ulos .= "<option value = '1' $sel[1]>".t("Extranet-tilaus varaa saldoa 1 tunnin")."</option>";
		$ulos .= "<option value = '8' $sel[8]>".t("Extranet-tilaus varaa saldoa 8 tuntia")."</option>";
		$ulos .= "<option value = '24' $sel[24]>".t("Extranet-tilaus varaa saldoa 24 tuntia")."</option>";
		$ulos .= "<option value = '48' $sel[48]>".t("Extranet-tilaus varaa saldoa 48 tuntia")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "myyntikielto") {

		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Asiakas ei ole myyntikiellossa")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Asiakas on myyntikiellossa")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "luottoraja" and $yhtiorow["myyntitilaus_saatavat"] == "Y") {

		$ulos = "<td>";

		if (tarkista_oikeus("luotonhallinta.php")) {
			$ulos .= "<a target='Luotonhallinta' href='${palvelin2}myyntires/luotonhallinta.php?tee=1&ytunnus=$trow[ytunnus]'>".t("Luotonhallinta")."</a>";
		}
		else {
			$ulos .= t("Luottorajat ovat per ytunnus, käytä Luotonhallinta -sovellusta");
		}

		$ulos .= "</td>";

		$jatko = 0;
	}


	if (mysql_field_name($result, $i) == "laani" or
		mysql_field_name($result, $i) == "kunta") {

		$sresult = t_avainsana(mysql_field_name($result, $i));
		$sarnimi = mysql_field_name($result, $i);

		// jos avainsanoja on perustettu tehdään dropdown
		if (mysql_num_rows($sresult) > 0) {
			$ulos = "<td><select id='$sarnimi' name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

			$ulos .= "<option value = ''>".t("Ei valintaa")."</option>";

			while ($srow = mysql_fetch_array($sresult)) {
				$sel = '';
				if ($trow[$i] == $srow["selite"] and $srow["selite"] != "") {
					$sel = "selected";
				}

				$ulos .= "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
			}

			$ulos .= "</select>";
			$ulos .= " <a href='#' onclick=\"js_open_yllapito('$sarnimi','toim=avainsana&lukitse_laji=$sarnimi');\"><img src='pics/lullacons/add.png'>".t("Uusi")." ".t(mysql_field_name($result, $i))."</a></td>";

			$ulos .= "</td>";
			$jatko = 0;
		}
	}

	if (mysql_field_name($result, $i) == "luottovakuutettu") {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Asiakas ei ole luottovakuutettu")."</option>";
		$ulos .= "<option value = 'K' $sel[K]>".t("Asiakas on luottovakuutettu")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "panttitili") {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		$sel = trim($trow[$i]) == 'K' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("Ei käytössä")."</option>";
		$ulos .= "<option value = 'K'{$sel}>".t("Käytössä")."</option>";
		$ulos .= "</select>";
		
		$query = "	SELECT sum(hinta) as hinta
					FROM panttitili
					WHERE panttitili.yhtio = '{$kukarow['yhtio']}'
					AND panttitili.asiakas = '{$trow['tunnus']}'";
		$panttitili_result = pupe_query($query);
		$panttitili_row = mysql_fetch_assoc($panttitili_result);

		$ulos .= " <font class='message'>".t("Asiakkaan panttitililla yhteensä").": ".str_replace('.', ',', str_replace(',', ' ', number_format($panttitili_row['hinta'], 2)))." EUR</font>";
		$ulos .= "</td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'myynninseuranta') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

		$sel = trim($trow[$i]) == 'E' ? ' selected' : '';

		$ulos .= "<option value = ''>".t("Asiakas otetaan huomioon myynninseurannassa")."</option>";
		$ulos .= "<option value = 'E'{$sel}>".t("Asiakasta ei oteta huomioon myynninseurannassa")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'maksusopimus_toimitus') {
		$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";

		$sel = array_fill_keys(array($trow[$i]), 'selected') + array('S' => '', 'X' => '');

		$ulos .= "<option value = ''>".t("Yhtiön oletus")."</option>";
		$ulos .= "<option value = 'S' {$sel['S']}>".t("Tilauksen voi toimittaa vaikka ennakkolaskuja ei ole maksettu")."</option>";
		$ulos .= "<option value = 'X' {$sel['X']}>".t("Tilauksen voi toimittaa vasta kun ennakkolaskut on maksettu")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "keraysvahvistus_lahetys") {

		$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array_fill_keys(array($trow[$i]), ' selected') + array_fill_keys(array('E', 'o', 'k', 'L', 'M', 'N', 'Q', 'P'), '');

		$ulos .= "<option value=''>".t("Käytetään yhtiön oletusta")."</option>";
		$ulos .= "<option value='E'{$sel['E']}>".t("Keräysvahvistusta/sähköisetä lähetettä ei lähetetä")."</option>";
		$ulos .= "<option value='o'{$sel['o']}>".t("Keräysvahvistus/sähkoinen lähete lähetetään asiakkaalle jokaisesta toimituksesta erikseen")."</option>";
		$ulos .= "<option value='k'{$sel['k']}>".t("Keräysvahvistus/sähkoinen lähete lähetetään asiakkaalle kun koko alkuperäinen tilaus on kerätty")."</option>";
		$ulos .= "<option value='L'{$sel['L']}>".t("Keräysvahvistus/paperinen lähete tulostetaan asiakkaalle kun koko alkuperäinen tilaus on kerätty")."</option>";
		$ulos .= "<option value='M'{$sel['M']}>".t("Keräysvahvistus/paperinen & sähkoinen lähete lähetetään asiakkaalle kun koko alkuperäinen tilaus on kerätty")."</option>";
		$ulos .= "<option value='N'{$sel['N']}>".t("Keräysvahvistus/sähkoinen lähete lähetetään asiakkaalle kun koko alkuperäinen tilaus (myös JT-rivit) on kerätty")."</option>";
		$ulos .= "<option value='Q'{$sel['Q']}>".t("Keräysvahvistus/paperinen lähete tulostetaan asiakkaalle kun koko alkuperäinen tilaus (myös JT-rivit) on kerätty")."</option>";
		$ulos .= "<option value='P'{$sel['P']}>".t("Keräysvahvistus/paperinen & sähkoinen lähete lähetetään asiakkaalle kun koko alkuperäinen tilaus (myös JT-rivit) on kerätty")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'maksukehotuksen_osoitetiedot') {
		$ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "selected";

		$ulos .= "<option value = ''>".t("Käytetään yhtiön oletuksia")."</option>";
		$ulos .= "<option value = 'A' $sel[A]>".t("Käytetään asiakkaan virallista osoitetta")."</option>";
		$ulos .= "<option value = 'B' $sel[B]>".t("Käytetään asiakkaan laskutusosoitetta")."</option>";
		$ulos .= "</select></td>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == 'kerayserat') {

		if ($yhtiorow['kerayserat'] == 'A') {
			$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";

			$sel = trim($trow[$i]) == 'A' ? ' selected' : '';

			$ulos .= "<option value = ''>".t("Keräyseriä ei käytetä")."</option>";
			$ulos .= "<option value = 'A'{$sel}>".t("Keräyserät käytössä")."</option>";
			$ulos .= "</select></td>";

			$jatko = 0;
		}
		else {
			$tyyppi = 0;
		}
	}
