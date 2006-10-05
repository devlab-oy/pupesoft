<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys

	if ($i == 0) { //yhtiˆt‰ ei n‰ytet‰
		$tyyppi = 0;
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

	if (mysql_field_name($result, $i) == "konserniyhtio") {
		$sel = '';
		if ($trow[$i] == 'o') {
			$sel = "checked";
		}
		$ulos = "<td><input type='checkbox' name='$nimi' $sel>";
		$ulos .= "</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "maksuehto") {
		$query = "	SELECT tunnus, concat_ws(' ', kassa_teksti, teksti)
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY jarjestys, teksti";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_row($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakasnro") {

		$query = "	SELECT lpad(ytunnus,15,0) as ytunnus
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]'
					and ytunnus < 100000
					ORDER BY ytunnus";
		$vresult = mysql_query($query) or pupe_error($query);

		$edytunnus = 0;

		while ($vrow = mysql_fetch_array($vresult)) {
			$ytunnus = $vrow["ytunnus"];

			if ($ytunnus - $edytunnus > 1000) {

				$vapaa = $edytunnus+1;

				$tunnukset .= $vapaa."#";
			}

			$edytunnus = $ytunnus;

		}

		$query = "	SELECT max(lpad(ytunnus,15,0)) as ytunnus
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]'
					and ytunnus < 100000";
		$vresult = mysql_query($query) or pupe_error($query);
		$vrow = mysql_fetch_array($vresult);
		$vapaa = $vrow['ytunnus']+1;

		$tunnukset .= $vapaa."#";

		$tunnukset = substr($tunnukset,0,-1);
		$tunnukset = split("#",$tunnukset);

		$ulos = "<td><input type='text' name='$nimi' value='$trow[$i]'>";

		$ulos .= "<select name='asiakasnro_select'>";
		$ulos .= "<option value = ''></option>";

		foreach($tunnukset as $xtunnus) {

			$xtunnus = (int) $xtunnus;

			$ulos .= "<option value = '$xtunnus'>$xtunnus</option>";
		}

		$ulos .= "</select></td>";
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
			elseif($trow[$i] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["maakoodi"])) {
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
			elseif($trow[$i] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["maakoodi"])) {
				$sel = "selected";
			}
			$ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	if (mysql_field_name($result, $i) == "toimitustapa") {
		$query = "	SELECT tunnus, selite
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY jarjestys, selite";
		$vresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite]";
		}
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "alv") {

		//yhtiˆn oletusalvi!
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
				<option value='0' $sel[0]>".t("Ei laskutusp‰iv‰‰")."</option>
				<option value='2' $sel[2]>".t("Maanantai")."</option>
				<option value='3' $sel[3]>".t("Tiistai")."</option>
				<option value='4' $sel[4]>".t("Keskiviikko")."</option>
				<option value='5' $sel[5]>".t("Torstai")."</option>
				<option value='6' $sel[6]>".t("Perjantai")."</option>
				<option value='7' $sel[7]>".t("Lauantai")."</option>
				<option value='1' $sel[1]>".t("Sunnuntai")."</option>
				</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lahetetyyppi") {

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'LAHETETYYPPI'
					ORDER BY jarjestys, selite";
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
			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'TV'
						ORDER BY jarjestys, selite";
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
			$sel		= array();
			$pv			= $trow[$i];
			$sel[$pv]	= " selected";

			$ulos = "<td>
				<select name='$nimi'>
				<option value='0' $sel[0]>".t("L‰hetet‰‰n")."</option>
				<option value='1' $sel[1]>".t("Ei l‰hetet‰")."</option>
				</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "toimitusehto") {

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]'
					and laji = 'TOIMEHTO'
					ORDER BY jarjestys, selite";
		$tresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'><option value=''>".t("Tyhj‰")."</option>";

		while($row = mysql_fetch_array($tresult)) {

			$sel = "";
			if ($row["selite"] == $trow[$i]) {
				$sel = 'selected';
			}

			$ulos .= "<option value='$row[selite]' $sel>$row[selite]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "jtrivit") {
			$sel		= array();
			$pv			= $trow[$i];
			$sel[$pv]	= " selected";

			$ulos = "<td>
				<select name='$nimi'>
				<option value='0' $sel[0]>".t("N‰ytet‰‰n")."</option>
				<option value='1' $sel[1]>".t("Ei n‰ytet‰")."</option>
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

			// t‰ss‰ pit‰s selectata aktiivinen, muttakun en tied‰ mit‰ t‰m‰n sis‰ltˆ pit‰isi olla.. :)

			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'ASIAKASLUOKKA'
						ORDER BY jarjestys, selite";
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

			$sel='';
			if ($trow[$i]=='E') $sel='SELECTED';

			$ulos = "<td>
				<select name='$nimi'>
				<option value=''      >".t("Laskut saa ketjuttaa")."</option>
				<option value='E' $sel>".t("Ei ketjuteta laskuja")."</option>
				</select></td>";
			$jatko = 0;
	}

	if ((mysql_field_name($result, $i) == "fakta") or
		(mysql_field_name($result, $i) == "sisviesti1")) {
		$ulos = "<td><textarea cols='45' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kieli") {

			$ulos = "<td><select name='$nimi'>";
			$sel  = "";

			$query  = "show columns from sanakirja";
			$fields =  mysql_query($query);

			while ($apurow = mysql_fetch_array($fields)) {
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

			$ulos .= "</select></td>";

			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "osasto") {
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji = 'ASIAKASOSASTO'
					ORDER BY jarjestys";
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
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio='$kukarow[yhtio]' and laji = 'ASIAKASRYHMA'
					ORDER BY jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Ei ryhm‰‰")."</option>";

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

	if (mysql_field_name($result, $i) == "myyjanro") {

		$query = "select distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
		$vresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Ei myyj‰‰")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['myyja']) {
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
		$ulos .= "<option value = '' >".t("Ei piiri‰")."</option>";

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

	if (mysql_field_name($result, $i) == "sisamaan_kuljetusmuoto" or mysql_field_name($result, $i) == "kuljetusmuoto") {

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Valitse")."</option>";

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji='KM'
					ORDER BY jarjestys, selite";
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

		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji='KT'
					ORDER BY jarjestys, selite";
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

		$ulos .= "<option value='1' $chk1>".t("Kyll‰")."</option>";
		$ulos .= "<option value='0' $chk2>".t("Ei")."</option>";

		$ulos .= "</select></td>";
		$jatko = 0;
	}

?>
