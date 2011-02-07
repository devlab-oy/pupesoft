<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys

	if ($i == 0) { //yhtiˆt‰ ei n‰ytet‰
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "piiri") {
		$preq = "	SELECT avainsana.selite piiri, avainsana.selitetark selitetark
					FROM avainsana 
					WHERE  avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='piiri'";
		$preres = mysql_query($preq) or pupe_error($preq);
		
		$ulos = "<td><select id='piiri' name='$nimi'>";
		$ulos .= "<option value = ''>".t("Ei piiri‰")."</option>";
				
		while ($srow = mysql_fetch_array($preres)) {
			$sel = '';
			if ($trow[$i] == $srow["piiri"]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[piiri]' $sel>$srow[selitetark]</option>";
		}
		
		$ulos .= "</select></td>\n";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakas_segmentti") {

		$preq = "	SELECT CONCAT(REPEAT('&raquo;', COUNT(parent.tunnus) - 1), ' ', ifnull(node.koodi, ''), ' ', node.nimi) AS name, node.koodi koodi, node.tunnus
					FROM dynaaminen_puu AS node
					JOIN dynaaminen_puu AS parent ON (node.lft BETWEEN parent.lft AND parent.rgt AND parent.laji = node.laji AND parent.yhtio = node.yhtio)
					WHERE node.yhtio = '$kukarow[yhtio]'
					AND node.lft > 1
					AND node.laji = 'asiakas'
					GROUP BY node.tunnus
					ORDER BY node.lft";
		$preres = mysql_query($preq) or pupe_error($preq);

		$ulos = "<td><select id='asiakas_segmentti' name='$nimi'>";
		$ulos .= "<option value=''>".t("Ei asiakassegmentti‰")."</option>";

		while ($prerow = mysql_fetch_array($preres)) {
			$sel = '';
			if ($trow[$i] == $prerow["tunnus"]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$prerow[tunnus]' $sel>$prerow[name]</option>";
		}
		$ulos .= "</select></td>\n";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ryhma") {

		$query = "	SELECT ryhma, if (ryhma!=selite, concat_ws(' - ', ryhma, selite), ryhma) selitetark
					FROM perusalennus
					WHERE yhtio='$kukarow[yhtio]'
					ORDER BY ryhma";
		$sresult = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select id='ryhma' name='$nimi'>";
		$ulos .= "<option value = ''>".t("Ei ryhm‰‰")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($trow[$i] == $srow["ryhma"]) {
				$sel = "selected";
			}
			$ulos .= "<option value='$srow[ryhma]' $sel>$srow[selitetark]</option>";
		}

		$ulos .= "</select>\n";
		$ulos .= " <a href='#' onclick=\"js_open_yllapito('ryhma','toim=perusalennus');\"><img src='pics/lullacons/add.png'>".t("Uusi ryhm‰")."</a></td>\n";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakas_ryhma" and $lukitse_avaimeen != "") {
		$tyyppi = 4;
	}
	elseif (mysql_field_name($result, $i) == "asiakas_ryhma") {

		$vresult = t_avainsana("ASIAKASRYHMA");

		$ulos = "<td><select name='$nimi'>";
		$ulos .= "<option value = '' >".t("Ei ryhm‰‰")."</option>";

		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['selite']) {
				$sel = "selected";
			}
			$ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
		}

		$ulos .= "</select></td>\n";
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