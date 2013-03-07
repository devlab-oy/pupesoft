<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali k‰sittely
	$tyyppi = 1; // oletetaan rivin n‰kyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_type($result, $i) == "blob") {
		$ulos = "<td><textarea cols='50' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "asiakas" and $lukitse_avaimeen != "") {
		// Jos t‰m‰ on liitetty tunnuksella:
		if ($trow[$i] > 0) {
			$ulos = "<input type='hidden' name='$nimi' value='$trow[$i]'>";
		}
		// Jos t‰‰ on uusi itemi
		elseif ((int) $tunnus == 0) {
			$ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
		}

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
			if ($trow[$i] == 0) $trow[$i] = "";

			// Ei maxlength:i‰ koska silloin hakutoiminto ei v‰ltt‰m‰tt‰ toimi
			$ulos = "<td nowrap><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size'></td>";
			$jatko = 0;
		}
	}
   	if (mysql_field_name($result, $i) == "tyyppi") {
        $preq = "	SELECT avainsana.selite tyyppi, avainsana.selitetark selitetark
					FROM avainsana
					WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='kohde_tyyppi'";
		$preres = mysql_query($preq) or pupe_error($preq);

        if (mysql_num_rows($preres) > 0) {
            $ulos = "<td><select id='tyyppi' name='$nimi'>";
            $ulos .= "<option value = ''>".t("Ei tyyppi‰")."</option>";

            while ($srow = mysql_fetch_array($preres)) {
                $sel = (strtoupper($trow[$i]) == strtoupper($srow["tyyppi"])) ? "selected" : "";
                $ulos .= "<option value='$srow[tyyppi]' $sel>$srow[selitetark]</option>";
            }

            $ulos .= "</select></td>\n";
            $jatko = 0;
        }
        else {
            $ulos = "";
            $tyyppi = 0;
            $jatko = 0;
        }
	}

?>