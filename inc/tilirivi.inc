<?php

	$ulos   = "";
	$jatko  = 1; // oletetaan normaali käsittely
	$tyyppi = 1; // oletetaan rivin näkyvyys

	// yhtiötä ei näytetä
	if ($i == 0) {
		$tyyppi = 0;
	}

	if (mysql_field_name($result, $i) == "sisainen_taso") {

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$query = "SELECT * from taso where yhtio='$kukarow[yhtio]' and tyyppi='S' order by taso";
		$tasoresu = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>\n";
		$ulos .= "<option value = ''>Ei tasoa</option>\n";

		while ($tasorow = mysql_fetch_array($tasoresu)) {
			$tasoapu = $tasorow["taso"];
			$ulos .= "<option value = '$tasorow[taso]' $sel[$tasoapu]>$tasorow[taso] - $tasorow[nimi]</option>\n";
		}

		$ulos .= "</select></td>\n";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "ulkoinen_taso") {

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$query = "SELECT * from taso where yhtio='$kukarow[yhtio]' and tyyppi='U' order by taso";
		$tasoresu = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>\n";
		$ulos .= "<option value = ''>Ei tasoa</option>\n";

		while ($tasorow = mysql_fetch_array($tasoresu)) {
			$tasoapu = $tasorow["taso"];
			$ulos .= "<option value = '$tasorow[taso]' $sel[$tasoapu]>$tasorow[taso] - $tasorow[nimi]</option>\n";
		}

		$ulos .= "</select></td>\n";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "alv_taso") {

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$query = "SELECT * from taso where yhtio='$kukarow[yhtio]' and tyyppi='A' order by taso";
		$tasoresu = mysql_query($query) or pupe_error($query);

		$ulos = "<td><select name='$nimi'>\n";
		$ulos .= "<option value = ''>Ei tasoa</option>\n";

		while ($tasorow = mysql_fetch_array($tasoresu)) {
			$tasoapu = $tasorow["taso"];
			$ulos .= "<option value = '$tasorow[taso]' $sel[$tasoapu]>$tasorow[taso] - $tasorow[nimi]</option>\n";
		}

		$ulos .= "</select></td>\n";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "kustp" or mysql_field_name($result, $i) == "kohde" or mysql_field_name($result, $i) == "projekti") {

		$kustp_tyyppi = "K";
		if (mysql_field_name($result, $i) == "kohde") $kustp_tyyppi = "O";
		if (mysql_field_name($result, $i) == "projekti") $kustp_tyyppi = "P";

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$query = "	SELECT tunnus, nimi, koodi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = '$kustp_tyyppi'
					and kaytossa != 'E'
					ORDER BY nimi";
		$tasoresu = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tasoresu) > 0) {
			$ulos = "<td>";
			$ulos .= "<select name='$nimi'>";
			$ulos .= "<option value=''>".t("Ei oletusta")."</option>";

			while ($kustannuspaikkarow = mysql_fetch_array($tasoresu)) {
				$selapu = $kustannuspaikkarow["tunnus"];
				$ulos .= "<option value='$kustannuspaikkarow[tunnus]' $sel[$selapu]>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]</option>";
			}
			$ulos .= "</select>";
			$ulos .= "</td>";
			$jatko = 0;
		}
		else {
			$tyyppi = 4;
		}
	}

	if (mysql_field_name($result, $i) == "toiminto") {
		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$ulos = "<td><select name='$nimi'>\n";
		$ulos .= "<option value = ''>".t("Ei liitospakkoa")."</option>\n";
		$ulos .= "<option value = 'A' $sel[A]>".t("Muistiotositteelle on liitettävä asiakas tai toimittaja")."</option>\n";
		$ulos .= "</select></td>\n";
		$jatko = 0;
	}
?>