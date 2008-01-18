<?php	

$toimerrorit  = array();
$toim_valinta = array();

if ($toimtunnus == "") $toimtunnus = 0;

$haku1 = "";

if ($toimittajatunnus != "") {
	$haku1 = " and tunnus='$toimittajatunnus' ";
}
else {
	$haku1 = " and ytunnus='$toimittaja' ";
}

$query = "SELECT * from toimi where yhtio='$kukarow[yhtio]' $haku1";
$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result) == 0) {

	$toimerrorit[$toimtunnus]["toimittaja"] = "<font class='error'>".t("Virheellinen ytunnus").": $toimittaja!</font>";

	$haku1 = "";

	if (is_numeric($toimittaja)) {
		$haku1 = " and ytunnus like '$toimittaja%' ";
	}
	elseif (trim($toimittaja) != "") {
		$haku1 = " and nimi like '%$toimittaja%' ";
	}

	$query = "SELECT tunnus, ytunnus, nimi, nimitark, postino, postitp, if(maa='$yhtiorow[maa]', tilinumero, ultilno) tilinumero from toimi where yhtio='$kukarow[yhtio]' $haku1";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		$toim_valinta[$toimtunnus] = "<select name='toimittajatunnus'>";

		while ($toimrows = mysql_fetch_array($result)) {
			$toim_valinta[$toimtunnus] .= "<option value='$toimrows[tunnus]'>$toimrows[ytunnus] $toimrows[nimi] $toimrows[nimitark] $toimrows[postino] $toimrows[postitp] $toimrows[tilinumero]</option>";
		}

		$toim_valinta[$toimtunnus] .= "</select>";

		if (mysql_num_rows($result) > 1) {
			$toimerrorit[$toimtunnus]["toimittaja"] = "<font class='error'>".t("Löytyi useita toimittajia samalla ytunnuksella").": $toimittaja!</font>";
		}
	}
}
elseif(mysql_num_rows($result) > 1) {

	$toim_valinta[$toimtunnus] = "<select name='toimittajatunnus'>";

	while ($toimrows = mysql_fetch_array($result)) {
		$toim_valinta[$toimtunnus] .= "<option value='$toimrows[tunnus]'>$toimrows[ytunnus] $toimrows[nimi] $toimrows[nimitark]</option>";
	}

	$toim_valinta[$toimtunnus] .= "</select>";

	$toimerrorit[$toimtunnus]["toimittaja"] = "<font class='error'>".t("Löytyi useita toimittajia samalla ytunnuksella").": $toimittaja!</font>";
}
else {
	$tuotoimirow = mysql_fetch_array($result);
	$toimittajatunnus = $tuotoimirow["tunnus"];
}

$query = "SELECT * from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and liitostunnus='$tuotoimirow[tunnus]' and tuoteno='$tuoteno' and tunnus!='$toimtunnus'";
$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result) > 0 and $lue_datasta != 'MUUTA') {
	$toimerrorit[$toimtunnus]["toimittaja"] = "<font class='error'>".t("Tämä toimittaja on jo tällä tuotteella")."!</font>";
}

if ($alkuperamaa == "") {
	$toimerrorit[$toimtunnus]["alkuperamaa"] = "<font class='error'>".t("Tieto puuttuu")."!</font>";
}

if ($toim_tuoteno == "" and $tuotoimirow["tyyppi"] != "") {
	$toimerrorit[$toimtunnus]["toim_tuoteno"] = "<font class='error'>".t("Online/sisäinen toimittaja vaatii aina toimittajan tuotenumeron")."!</font>";
}

if (count($toimerrorit) > 0) {
	if ($tee == "insert") $tee = "uusi";
	else $tee = "";
}

?>