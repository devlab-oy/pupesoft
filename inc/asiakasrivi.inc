<?php


	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}
	
	if (mysql_field_name($result, $i) == "tilino") {
		if ($ulosarray[$i] != '') {
			$ulos=$ulosarray[$i];
			$jatko = 0;
		}
		else {
			$ulos = "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
			$query = "	SELECT nimi
						FROM tili
						WHERE yhtio='$kukarow[yhtio]' and tilino = '$trow[$i]'";
			$vresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($vresult) == 1) {
				$vrow=mysql_fetch_array($vresult);
				$ulos .= " " . $vrow[nimi] . "</td>";
				$jatko = 0;
			}
		}
	}
	
	if (mysql_field_name($result, $i) == "kustannuspaikka") { //kustannuspaikat
		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and
					tyyppi = 'K'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		
		$sel="";
		if (trim($trow[$i]) == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei kustannuspaikkaa")."";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";

			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kohde") { //kohteet
		$query = "	SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and
						tyyppi = 'O'
						ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		$sel="";
		if (trim($trow[$i]) == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei kohdetta")."";
		
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[tunnus]  - $vrow[nimi]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "projekti") { //projektit

		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and
					tyyppi = 'P'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		$sel="";
		if (trim($trow[$i]) == "") {
			$sel = "selected";
		}
		$ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei projektia")."";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[tunnus] - $vrow[nimi]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;

	}

	if (mysql_field_name($result, $i) == "chn") {

		$sel = array();
		$apu = $trow[$i];
		$sel[$apu] = "SELECTED";

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='100' $sel[100]>".t("Paperilasku")."</option>";
		$ulos .= "<option value='010' $sel[010]>".t("eInvoice")."</option>";
		$ulos .= "<option value='020' $sel[020]>".t("Vienti eInvoice")."</option>";
		$ulos .= "<option value='111' $sel[111]>".t("Elma EDI-inhouse")."</option>";
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
		
		if(!isset($trow[$i])) {
			$trow[$i] = $laji;
		}
		
		if (strtoupper($trow[$i]) == 'H') {
			$sel1 = "SELECTED";
		}
		elseif (strtoupper($trow[$i]) == 'P') {
			$sel2 = "SELECTED";
		}
		else {
			$sel = "SELECTED";
		}
		
		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' $sel>".t("Yritys")."</option>";
		$ulos .= "<option value = 'H' $sel1>".t("Yksityishenkilö")."</option>";
		$ulos .= "<option value = 'P' $sel2>".t("Poistettu / Ei käytössä")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "konserniyhtio") {

		$sela = $selb = '';
		if ($trow[$i] != '') {
			$selb = "SELECTED";
		}
		else {
			$sela = "SELECTED";
		}

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' $sela>".t("Ei")."</option>";
		$ulos .= "<option value = 'o' $selb>".t("Kyllä")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "maksuehto") {
		$query = "	SELECT maksuehto.tunnus, concat_ws(' ', ".avain('selectcon','MEHTOKATXT_').",  ".avain('selectcon2','MEHTOTXT_')."), maksuehto.sallitut_maat smaat
					FROM maksuehto
					".avain('join','MEHTOKATXT_')."
					".avain('join2','MEHTOTXT_')."
					WHERE maksuehto.yhtio  = '$kukarow[yhtio]'
					and maksuehto.kaytossa = ''
					ORDER BY maksuehto.jarjestys, maksuehto.teksti";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel="";
			$sallitut_maat = "";

			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}

			if ($vrow["smaat"] != "") {
				$sallitut_maat = "($vrow[smaat])";
			}

			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1] $sallitut_maat</option>";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakasnro") {

		$ulos = "<td><input type='text' name='$nimi' value='$trow[$i]'>";

		$ulos .= "<select name='asiakasnro_select'>";
		$ulos .= "<option value = ''></option>";

		$apu_asiakasnumero = 1;

		//jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksilöivät tiedot on oltava konsernitasolla-yksilölliset
		$query = "	SELECT *
					FROM yhtio 
					JOIN yhtion_parametrit ON yhtion_parametrit.yhtio = yhtio.yhtio
					where konserni = '$yhtiorow[konserni]' 
					and synkronoi like '%$toim%'";
		$vresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($vresult) > 0) {
			// haetaan konsernifirmat
			$query = "	SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
						FROM yhtio 
						JOIN yhtion_parametrit ON yhtion_parametrit.yhtio = yhtio.yhtio
						where konserni = '$yhtiorow[konserni]' 
						and synkronoi like '%$toim%'";
			$vresult = mysql_query($query) or pupe_error($query);
			$srowapu = mysql_fetch_array($vresult);
			$tarkyhtio = $srowapu["yhtiot"];
		}
		else {
			$tarkyhtio = "'$kukarow[yhtio]'";
		}

		for ($monta = 0; $monta < 5;) {

			$query = "	SELECT asiakasnro
						FROM asiakas USE INDEX (asno_index)
						WHERE yhtio in ($tarkyhtio)
						and asiakasnro='$apu_asiakasnumero'";
			$vresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($vresult) == 0) {
				$ulos .= "<option value = '$apu_asiakasnumero'>$apu_asiakasnumero</option>";
				$monta++;
			}

			$apu_asiakasnumero++;

		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ytunnus") {

		$ulos = "<td><input type='text' name='$nimi' value='$trow[$i]'>";

		if ($alias_set == "VAHITTAISMYYNTI") {

			$ulos .= "<select name='ytunnus_select'>";
			$ulos .= "<option value = ''></option>";

			$apu_asiakasnumero = 1;

			//jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksilöivät tiedot on oltava konsernitasolla-yksilölliset
			$query = "	SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
						FROM yhtio 
						JOIN yhtion_parametrit ON yhtion_parametrit.yhtio=yhtio.yhtio
						where konserni	 = '$yhtiorow[konserni]' 
						and synkronoi like '%$toim%'";
			$vresult = mysql_query($query) or pupe_error($query);			
			$srowapu = mysql_fetch_array($vresult);
			
			if ($srowapu["yhtiot"] != "") {
				$tarkyhtio = $srowapu["yhtiot"];
			}
			else {
				$tarkyhtio = "'$kukarow[yhtio]'";
			}

			for ($monta = 0; $monta < 5;) {

				$query = "	SELECT ytunnus
							FROM asiakas USE INDEX (ytunnus_index)
							WHERE yhtio in ($tarkyhtio)
							and ytunnus = '$apu_asiakasnumero'";
				$vresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($vresult) == 0) {
					$ulos .= "<option value = '$apu_asiakasnumero'>$apu_asiakasnumero</option>";
					$monta++;
				}

				$apu_asiakasnumero++;

			}

			$ulos .= "</select>";
		}

		$ulos .= "</td>";
		$ulos.= "<td class='back'>".t("Voit tarkistaa ytunnuksen")." <a target='YTJ' href='http://ec.europa.eu/taxation_customs/vies/lang.do?fromWhichPage=vieshome&selectedLanguage=".strtoupper($kukarow["kieli"])."'>".t("täältä")."</td>";
		$jatko = 0;
	}


	if (mysql_field_name($result, $i) == "maa") {
		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

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

	if (mysql_field_name($result, $i) == "kansalaisuus") {
		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

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

	if (mysql_field_name($result, $i) == "kolm_maa") {
		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

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

	if (mysql_field_name($result, $i) == "toim_maa") {
		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

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

	if(	mysql_field_name($result, $i) == "laskutus_nimi" or
		mysql_field_name($result, $i) == "laskutus_nimitark" or
		mysql_field_name($result, $i) == "laskutus_osoite" or
		mysql_field_name($result, $i) == "laskutus_postino" or
		mysql_field_name($result, $i) == "laskutus_postitp" or	
		mysql_field_name($result, $i) == "laskutus_maa") {
			
		if($yhtiorow['laskutyyppi'] == 3) {
			if (mysql_field_name($result, $i) == "laskutus_maa") {
				$query = "	SELECT distinct koodi, nimi
							FROM maat
							WHERE nimi != ''
							ORDER BY koodi";
				$vresult = mysql_query($query) or pupe_error($query);
				$ulos = "<td><select name='$nimi'>";

				$ulos .= "<option value = '' ></option>";

				while ($vrow=mysql_fetch_array($vresult)) {
					$sel="";
					if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
						$sel = "selected";
					}
					elseif($trow[$i] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["laskutus_maa"])) {
						$sel = "selected";
					}
					$ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
				}

				$ulos .= "</select></td>";
				$jatko = 0;
			}	
		}
		else {
			$tyyppi = 0;
		}	
	}

	if (mysql_field_name($result, $i) == "toimitustapa") {
		$query = "	SELECT toimitustapa.tunnus, selite
						FROM toimitustapa
						WHERE toimitustapa.yhtio = '$kukarow[yhtio]'
						ORDER BY toimitustapa.jarjestys, toimitustapa.selite";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>".asana('TOIMITUSTAPA_',$vrow['selite'])."";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "alv") {

		//yhtiön oletusalvi!
		$wquery = "select selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
		$wtres  = mysql_query($wquery) or pupe_error($wquery);
		$wtrow  = mysql_fetch_array($wtres);

		$sel00 = "";
		$sel22 = "";

		if (isset($trow[$i])) {
			if ($trow[$i] == 0) {
				$sel00 = 'selected';
				$sel22 ='';
			}
			if ($trow[$i] == $wtrow["selite"]) {
				$sel22 = 'selected';
				$sel00 = '';
			}
		}

		$ulos = " 	<td>
					<select name='$nimi'>
					<option value='$wtrow[selite]' $sel22>".t("Verollinen myynti")."</option>
					<option value='0' $sel00>".t("Veroton myynti")."</option>
					</select>
					</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laskutusvkopv") {
			$sel		= array();
			$pv			= $trow[$i];
			$sel[$pv]	= " selected";

			$ulos = "<td>
				<select name='$nimi'>
				<option value='0' $sel[0]>".t("Ei laskutuspäivää")."</option>
				<option value='2' $sel[2]>".t("Maanantai")."</option>
				<option value='3' $sel[3]>".t("Tiistai")."</option>
				<option value='4' $sel[4]>".t("Keskiviikko")."</option>
				<option value='5' $sel[5]>".t("Torstai")."</option>
				<option value='6' $sel[6]>".t("Perjantai")."</option>
				<option value='7' $sel[7]>".t("Lauantai")."</option>
				<option value='1' $sel[1]>".t("Sunnuntai")."</option>
				<option value='9' $sel[9]>".t("Laskut lähetetään vain ohittamalla laskutusvkopv")."</option>
				</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lahetetyyppi") {

		$query = "	SELECT avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','LAHETETYYPPI_')."
					WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji = 'LAHETETYYPPI'
					ORDER BY avainsana.jarjestys, avainsana.selite";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while($row = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($row[0] == $trow[$i]) $sel = 'selected';
			$ulos .= "<option value='$row[0]' $sel>$row[1]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tilausvahvistus") {
			$query = "	SELECT avainsana.selite, ".avain('select')."
						FROM avainsana
						".avain('join','TV_')."
						WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji = 'TV'
						ORDER BY avainsana.jarjestys, avainsana.selite";
			$vresult = mysql_query($query) or pupe_error($query);

			$ulos = "<td><select name='$nimi'><option value=' '>".t("Ei vahvistusta")."</option>";

			while($row = mysql_fetch_array($vresult)) {
				$sel = "";
				if ($row[0] == $trow[$i]) $sel = 'selected';
				$ulos .= "<option value='$row[0]' $sel>$row[1]</option>";
			}

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
				<select name='$nimi'>
				<option value='0' $sel[0]>".t("Lähetetään asiakkaalle ja myyjälle")."</option>
				<option value='2' $sel[2]>".t("Lähetetään vain myyjälle")."</option>
				<option value='1' $sel[1]>".t("Ei lähetetä ollenkaan")."</option>
				</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "toimitusehto") {

		$query = "	SELECT concat(selite, ' ', selitetark) toimehto
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'TOIMEHTO'
					ORDER BY jarjestys, selite";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei oletustoimitusehtoa")."</option>";

		while ($kirow = mysql_fetch_array($kires)) {
			if ($kirow["toimehto"] == $trow[$i]) $select = 'SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[toimehto]' $select>$kirow[toimehto]</option>";
		}

		$ulos .= "</select></td>";

		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "jtkielto") {

		$sela = $selb = '';
		if ($trow[$i] != '') {
			$selb = "SELECTED";
		}
		else {
			$sela = "SELECTED";
		}

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' $sela>".t("Ei")."</option>";
		$ulos .= "<option value = 'o' $selb>".t("Kyllä")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "jtrivit") {
			$sel		= array();
			$pv			= $trow[$i];
			$sel[$pv]	= " selected";

			$ulos = "<td>
				<select name='$nimi'>
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

		$ulos = "<td><select name='$nimi'>
				<option value=''  $sel1>".t("Kotimaa")."</option>
				<option value='E' $sel2>".t("Vienti EU")."</option>
				<option value='K' $sel3>".t("Vienti ei-EU")."</option>
				</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "luokka") {

			// tässä pitäs selectata aktiivinen, muttakun en tiedä mitä tämän sisältö pitäisi olla.. :)

			$query = "	SELECT avainsana.selite, ".avain('select')."
						FROM avainsana
						".avain('join','ASLUOKKA_')."
						WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji = 'ASIAKASLUOKKA'
						ORDER BY avainsana.jarjestys, avainsana.selite";
			$vresult = mysql_query($query) or pupe_error($query);

			$ulos = "<td><select name='$nimi'><option value=' '>".t("Ei luokkaa")."</option>";

			while($row = mysql_fetch_array($vresult)) {
				$sel = "";
				if ($row[0] == $trow[$i]) $sel = 'selected';
				$ulos .= "<option value='$row[0]' $sel> $row[0] $row[1]</option>";
			}

			$ulos .= "</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ketjutus") {

			$sela = $selb = '';
			if ($trow[$i]=='E') $selb='SELECTED';
			else $sela = "SELECTED";
			$ulos = "<td>
				<select name='$nimi'>
				<option value='' $sela>".t("Laskut saa ketjuttaa")."</option>
				<option value='E' $selb>".t("Ei ketjuteta laskuja")."</option>
				</select></td>";
			$jatko = 0;
	}

	if ((mysql_field_name($result, $i) == "fakta") or (mysql_field_name($result, $i) == "sisviesti1")) {
		$ulos = "<td><textarea cols='45' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
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

	if (mysql_field_name($result, $i) == "kieli") {

			$ulos = "<td><select name='$nimi'>";
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
		$query = "	SELECT avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','ASOSASTO_')."
					WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji = 'ASIAKASOSASTO'
					ORDER BY avainsana.jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Ei osastoa")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ryhma") {
		$query = "	SELECT avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','ASRYHMA_')."
					WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji = 'ASIAKASRYHMA'
					ORDER BY avainsana.jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
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

	if (mysql_field_name($result, $i) == "myyjanro") {

		$query = "select distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Ei myyjää")."</option>";

		if ($lopetus != '') {
			$kukamyyja='';

			$query = "	SELECT myyja
						FROM kuka
						WHERE yhtio='$kukarow[yhtio]' and kuka = '$kukarow[kuka]' and myyja != 0";
			$myyjaresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($myyjaresult) > 0) {
				$myyjarow  = mysql_fetch_array($myyjaresult);
				$kukamyyja = $myyjarow['myyja'];
			}
		}

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
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
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji = 'PIIRI'
					ORDER BY jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
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

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Valitse")."</option>";

		$query = "	SELECT avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','KM_')."
					WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji='KM'
					ORDER BY avainsana.jarjestys, avainsana.selite";
		$vresult = mysql_query($query) or pupe_error($query);

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kauppatapahtuman_luonne") {

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Valitse")."</option>";

		$query = "	SELECT avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','KT_')."
					WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji='KT'
					ORDER BY avainsana.jarjestys, avainsana.selite";
		$vresult = mysql_query($query) or pupe_error($query);

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "poistumistoimipaikka_koodi") {

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = ''>".t("Valitse")."</option>";

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji='TULLI'
					ORDER BY jarjestys, selite";
		$vresult = mysql_query($query) or pupe_error($query);

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
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

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

		$ulos  = "<td><select name='$nimi'>";
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

?>
