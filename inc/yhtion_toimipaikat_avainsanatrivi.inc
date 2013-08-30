<?php

	$ulos = "";
	$jatko = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	if ($i == 0) { //yhtiötä ei näytetä
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "yhtion_toimipaikan_tunnus") {

		$tyyppi = 4;

		if ($lukitse_avaimeen != "") {
			$ulos = "<input type='hidden' name='$nimi' value='$lukitse_avaimeen'>";
			$jatko = 0;
		}
	}

	if (mysql_field_name($result, $i) == "laji") {
		$vresult = t_avainsana("YTAVAINSANA");

		$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300)." onchange='submit();'>";
		$ulos .= "<option value=''>".t("Oletus")."</option>";

		while($row = mysql_fetch_assoc($vresult)) {
			$sel = "";

			if ($row["selite"] == $trow[$i]) {
				$sel = 'selected';
				$yhtion_toimipaikat_avainsanat_laji = $row['selite'];
			}

			$ulos .= "<option value='{$row['selite']}' {$sel}>{$row['selitetark']}</option>";
		}

		$ulos .= "</select></td>";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "avainsana") {

		if ($yhtion_toimipaikat_avainsanat_laji == 'toimittaja') {

			$query = "	SELECT nimi, tunnus
						FROM toimi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND sahkoinen_tilausliitanta = 'F'";
			$toimi_chk_res = pupe_query($query);

			if (mysql_num_rows($toimi_chk_res) > 0) {

				$ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300)." onchange='submit();'>";
				$ulos .= "<option value=''>".t("Valitse toimittaja")."</option>";

				while($row = mysql_fetch_assoc($toimi_chk_res)) {
					$sel = "";

					if ($row["tunnus"] == $trow[$i]) {
						$sel = 'selected';
					}

					$ulos .= "<option value='{$row['tunnus']}' {$sel}>{$row['nimi']}</option>";
				}

				$ulos .= "</select></td>";
			}
			else {
				$ulos = "<td><input type='text' name='{$nimi}' value='{$trow[$i]}'></td>";
			}
		}

		$jatko = 0;
	}
