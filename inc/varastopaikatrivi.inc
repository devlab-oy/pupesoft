<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	
	if (substr(mysql_field_name($result,$i),0,9) == 'printteri') {

		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi'>";
		
		$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]'";
		$kires = mysql_query($query) or pupe_error($query);

		$ulos .= "<option value=''>".t("Ei kirjoitinta")."</option>";

		while ($kirow=mysql_fetch_array($kires)) {
			if ($kirow["tunnus"]==$row[$i]) $select='SELECTED';
			else $select = '';

			$ulos .= "<option value='$kirow[tunnus]' ".$sel[$kirow["tunnus"]].">$kirow[kirjoitin]</option>";
		}

		$ulos .= "</select></td>";

		$jatko=0;
	}
	
	if (mysql_field_name($result,$i) == 'tyyppi') {
		$sel = array();
		$sel[$trow[$i]] = "selected";

		$ulos = "<td><select name='$nimi'>";
		
		$ulos .= "<option value=''>".t("Normaalivarasto josta kaikki myyvät")."</option>";
		$ulos .= "<option value='V' $sel[V]>".t("Erikoisvarasto josta voi myydä")."</option>";
		$ulos .= "<option value='E' $sel[E]>".t("Erikoisvarasto josta automaattisesti ei myydä")."</option>";
		$ulos .= "</select>";

		$ulos .= "</select></td>";

		$jatko=0;
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
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}
	
	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}
		
	if (mysql_field_name($result, $i) == "tuoteno" and $uusi != 0) {
		$tyyppi = 1;
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
		
?>
