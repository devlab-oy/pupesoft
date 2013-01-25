<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if (mysql_field_name($result, $i) == "tunnus" or
	    mysql_field_name($result, $i) == "yhtio") {
		$tyyppi = 0;
	}

	if (mysql_field_type($result, $i) == "blob") {
		$ulos = "<td><textarea cols='50' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
		$jatko = 0;
	}

    if (mysql_field_name($result, $i) == "asiakas") {
        $as_query = "SELECT nimi FROM asiakas WHERE yhtio = '{$kukarow["yhtio"]}' AND tunnus = {$trow["$i"]};";
        $as_result = pupe_query($as_query);

        $as_row = mysql_fetch_assoc($as_result);

        $ulos = "<td>{$as_row["nimi"]}</td>";

		$tyyppi = 2;
        $jatko = 0;
	}


   	if (mysql_field_name($result, $i) == "tyyppi") {
        $preq = "	SELECT avainsana.selite tyyppi, avainsana.selitetark selitetark
					FROM avainsana
					WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='kohde_tyyppi'";
		$preres = mysql_query($preq) or pupe_error($preq);

        if (mysql_num_rows($preres) > 0) {
            $ulos = "<td><select id='tyyppi' name='$nimi'>";
            $ulos .= "<option value = ''>".t("Ei tyyppiä")."</option>";

            while ($srow = mysql_fetch_array($preres)) {
                $sel = '';
                if (strtoupper($trow[$i]) == strtoupper($srow["tyyppi"])) {
                    $sel = "selected";
                }
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