<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "nouto") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei noutoa")."</option>";
		$ulos .= "<option value='o' $sel>".t("Asiakas noutaa")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "lauantai") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei lauantaijakelua")."</option>";
		$ulos .= "<option value='o' $sel>".t("Lauantaijakelu")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "multi_jv") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei käytössä")."</option>";
		$ulos .= "<option value='o' $sel>".t("Ei käytössä")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kuljyksikko") {

		$sel='';
		if ($trow[$i]!='') $sel = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei")."</option>";
		$ulos .= "<option value='o' $sel>".t("Kyllä")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "merahti") {
		$sel1='';
		$sel2='';
		if ($trow[$i]=='K')
			$sel1 = 'SELECTED';
		else
			$sel2 = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='K' $sel1>".t("Lähettäjä")."</option>";
		$ulos .= "<option value=''  $sel2>".t("Vastaanottaja")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tulostustapa") {
			$sel		= array();
			$pv			= $trow[$i];
			$sel[$pv]	= " selected";

			$ulos = "<td>
				<select name='$nimi'>
				<option value='E' $sel[E]>".t("Erätulostus")."</option>
				<option value='H' $sel[H]>".t("Hetitulostus")."</option>
				<option value='K' $sel[K]>".t("Koonti-hetitulostus")."</option>
				<option value='L' $sel[L]>".t("Koonti-erätulostus")."</option>
				<option value='X' $sel[X]>".t("Rahtikirjansyöttö ja -tulostus ohitetaan")."</option>
				</select></td>";
			$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "extranet") {
		$sel1='';
		$sel2='';
		if ($trow[$i]=='')
			$sel1 = 'SELECTED';
		else
			$sel2 = 'SELECTED';

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''  $sel1>".t("Toimitustapa näytetään vain myynnissä")."</option>";
		$ulos .= "<option value='K' $sel2>".t("Toimitustapa näytetään vain extranetissä")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "rahtikirja") {

		$query = "	SELECT avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','RAHTIKIRJA_')."
					WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji = 'RAHTIKIRJA'
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

	if (mysql_field_name($result, $i) == "jvkielto") {

		$sel = $sela = '';
		if ($trow[$i]!='') $sel = 'SELECTED';
		else $sela = 'SELECTED';
		
		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='' $sela>".t("Saa toimittaa jälkivaatimuksia")."</option>";
		$ulos .= "<option value='o' $sel>".t("Ei saa toimittaa jälkivaatimuksia")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "vak_kielto") {

		$sel = $sela = '';
		if ($trow[$i] != '') {
			$sel = 'selected';
		}
		else {
			$sela = 'selected';
		}
		
		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value='' $sela>".t("Ei")."</option>";
		$ulos .= "<option value='K' $sel>".t("Kyllä")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

?>
