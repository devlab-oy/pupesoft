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

	if (mysql_field_name($result, $i) == "tulosseuranta_taso") {

		$sel = array();
		$sel[$trow[$i]] = "SELECTED";

		$query = "SELECT * from taso where yhtio='$kukarow[yhtio]' and tyyppi='B' order by taso+1";
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
					ORDER BY koodi+0, koodi, nimi";
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

	if (mysql_field_name($result, $i) == "toimijaliitos") {
		$sel = "";
		if ($trow[$i] != "") $sel = "SELECTED";

		$ulos = "<td><select name='$nimi'>\n";
		$ulos .= "<option value = ''>".t("Ei liitospakkoa")."</option>\n";
		$ulos .= "<option value = 'A' $sel>".t("Muistiotositteelle on liitettävä asiakas tai toimittaja")."</option>\n";
		$ulos .= "</select></td>\n";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "tiliointi_tarkistus") {

		$apu = $trow[$i];
		$sel = array();
		$sel[$apu] = "selected";

		$ulos = "<td><select name='$nimi'>\n";
		$ulos .= "<option value = '0' $sel[0]>".t("Ei pakollisia kenttiä")."</option>\n";
		$ulos .= "<option value = '1' $sel[1]>".t("pakollisia kenttiä tiliöinnissä on kustannuspaikka")."</option>\n";
		$ulos .= "<option value = '2' $sel[2]>".t("pakollisia kenttiä tiliöinnissä on kustannuspaikka, kohde")."</option>\n";
		$ulos .= "<option value = '3' $sel[3]>".t("pakollisia kenttiä tiliöinnissä on kustannuspaikka, projekti")."</option>\n";
		$ulos .= "<option value = '4' $sel[4]>".t("pakollisia kenttiä tiliöinnissä on kustannuspaikka, kohde ja projekti")."</option>\n";
		$ulos .= "<option value = '5' $sel[5]>".t("pakollisia kenttiä tiliöinnissä on kohde")."</option>\n";
	 	$ulos .= "<option value = '6' $sel[6]>".t("pakollisia kenttiä tiliöinnissä on kohde, projekti")."</option>\n";
		$ulos .= "<option value = '7' $sel[7]>".t("pakollisia kenttiä tiliöinnissä on projekti")."</option>\n";
		$ulos .= "</select></td>\n";
		$jatko = 0;
	}

	if (mysql_field_name($result, $i) == "manuaali_esto") {
		$sel = "";
		if ($trow[$i] != "") $sel = "SELECTED";

		$ulos = "<td><select name='$nimi'>\n";
		$ulos .= "<option value = ''>".t("Tiliöinti muokattavissa")."</option>\n";
		$ulos .= "<option value = 'X' $sel>".t("Tiliöinnin manuaalinen muokkaus estetty")."</option>\n";
		$ulos .= "</select></td>\n";
		$jatko = 0;
	}
?>