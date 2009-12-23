<?php

	$ulos = "";
	$jatko = 1; 	// oletetaan normaali käsittely
	$tyyppi = 1; 	// oletetaan rivin näkyvyys

	if ($i == 0) { 	//yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "ryhma") {
		$query = "	SELECT ryhma, if (ryhma!=selite, concat_ws(' - ', ryhma, selite), ryhma) selitetark
					FROM perusalennus
					WHERE yhtio='$kukarow[yhtio]'
					ORDER BY ryhma";
		$sresult = mysql_query($query) or pupe_error($query);
		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option id='option_$nimi' value = ''>".t("Ei ryhmää")."</option>";
				
		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow["ryhma"]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[ryhma]' $sel>$srow[selitetark]</option>";
		}
		
		$ulos .= "</select>";
		$ulos .= " <a href='#' onclick=\"teekentta('iframe_$nimi');\"><img src='pics/lullacons/database-option-add.png'>".t("Uusi ryhmä")."</a><div id='iframe_$nimi' style='display: inline;'></div></td>";
		
		echo "
		<script LANGUAGE='JavaScript'>
			function teekentta(tdid){
				var currenttd = document.getElementById(tdid);
				currenttd.innerHTML = \"<br><br><iframe id='perusalennus_iframe' name='perusalennus_iframe' src='yllapito.php?toim=perusalennus&uusi=1&laji=\"+tdid+\"&from=yllapito&ohje=off' style='width: 500px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>\";
			}
		</script>";

		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakas_ryhma" and $lukitse_avaimeen != "") {
		$tyyppi = 4;
	}
	elseif (mysql_field_name($result, $i) == "asiakas_ryhma") {

		$vresult = t_avainsana("ASIAKASRYHMA");

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
			$ulos .= "<option value = '$vrow[nimi]' $sel>$vrow[nimi]";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "laji") {

		$chkn = array();
		$chkn[$trow[$i]] = "SELECTED";

		$ulos  = "<td><select name='$nimi'>";
		$ulos .= "<option value=''>".t("Yhtiön oletus")."</option>";
		$ulos .= "<option value='N' $chkn[N]>".t("N-Nettohinta")."</option>";
		$ulos .= "<option value='E' $chkn[E]>".t("E-Nettohinta")."</option>";
		$ulos .= "<option value='B' $chkn[B]>".t("Bruttohinta")."</option>";
		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ytunnus" and $lukitse_avaimeen != "") {
		$tyyppi = 4;
	}
	elseif (mysql_field_name($result, $i) == "ytunnus" and isset($alasveto[$i])) {
		$ulos = "<td>".$alasveto[$i]."</td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakas" and $lukitse_avaimeen != "") {
		$ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
		$tyyppi = 4;
		$jatko = 0;
	}
	elseif (mysql_field_name($result, $i) == "asiakas" and isset($alasveto[$i])) {
		$ulos = "<td>".$alasveto[$i]."</td>";
		$jatko = 0;
	}
	elseif (mysql_field_name($result, $i) == "asiakas") {
		if ((int) $trow[$i] > 0) {
			$query	= "	SELECT *
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$trow[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);
			$ssize = strlen($trow[$i])+2;

			$ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$ssize' maxlength='$maxsize'> <table><tr><td>$srow[nimi] $srow[nimitark]</td></tr><tr><td>$srow[toim_nimi] $srow[toim_nimitark]</td></tr></table></td>";
			$jatko = 0;
		}
		else {
			$ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '' size='$size' maxlength='$maxsize'></td>";
			$jatko = 0;
		}
	}

	if (mysql_field_name($result, $i) == "alkupvm" or mysql_field_name($result, $i) == "loppupvm") {
		$tyyppi = 1.5;
	}

	if (mysql_field_name($result, $i) == "tuoteno" and isset($alasveto[$i])) {
		$ulos = "<td>".$alasveto[$i]."</td>";
		$jatko = 0;
	}
?>