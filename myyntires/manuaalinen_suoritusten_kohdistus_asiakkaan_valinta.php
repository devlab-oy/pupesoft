<?php

// estetään sivun lataus suoraan
if (!empty($HTTP_GET_VARS["oikeus"]) ||
    !empty($HTTP_POST_VARS["oikeus"]) ||
    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
    !isset($oikeus)) {

  echo "<p>".t("Kielletty toiminto")."!</p>";
  exit;
}

require_once "../inc/tilinumero.inc";

echo "<font class='head'>".t("Manuaalinen suoritusten kohdistaminen")."</font><hr>";

echo "<form action='$PHP_SELF' method='POST'>";
echo "<input type='hidden' name='tila' value=''>";

$query = "	SELECT distinct suoritus.tilino, nimi, yriti.valkoodi
			FROM suoritus, yriti
			WHERE suoritus.yhtio = '$kukarow[yhtio]'
			AND kohdpvm = '0000-00-00'
			and yriti.yhtio=suoritus.yhtio
			and yriti.tilino=suoritus.tilino
			ORDER BY nimi";
$result = mysql_query($query) or pupe_error($query);

echo "<table>";
echo "<tr><th>".t("Näytä vain suoritukset tililtä")."</th>";
echo "<td><select name='tilino' onchange='submit()'>";
echo "<option value=''>".t("Kaikki")."</option>\n";

while ($row = mysql_fetch_array($result)) {
	$sel = '';
	if ($tilino == $row[0]) $sel = 'selected';
	echo "<option value='$row[0]' $sel>$row[nimi] ".tilinumero_print($row['tilino'])." $row[valkoodi]</option>\n";
}
echo "</select></td></tr>";

$query = "	SELECT distinct valkoodi
			FROM suoritus
			WHERE yhtio = '$kukarow[yhtio]'
			AND kohdpvm = '0000-00-00'
			ORDER BY valkoodi";
$vresult = mysql_query($query) or pupe_error($query);

echo "<tr><th>".t("Näytä vain suoritukset valuutassa")."</th>";
echo "<td><select name='valuutta' onchange='submit()'>";
echo "<option value=''>".t("Kaikki")."</option>\n";

while ($vrow = mysql_fetch_array($vresult)) {
	$sel = "";
	if ($valuutta == $vrow[0]) $sel = "selected";
	echo "<option value = '$vrow[0]' $sel>$vrow[0]</option>";
}

echo "</select></td></tr>";

$query = "	SELECT distinct a.maa
			FROM suoritus s, asiakas a
			WHERE s.asiakas_tunnus <> 0
			AND s.asiakas_tunnus = a.tunnus
			AND s.yhtio ='$kukarow[yhtio]'
			AND a.yhtio = s.yhtio
			AND kohdpvm = '0000-00-00'
			AND ltunnus != 0
			ORDER BY a.maa";
$vresult = mysql_query($query) or pupe_error($query);

echo "<tr><th>".t("Näytä vain suoritukset maasta")."</th>";
echo "<td><select name='maakoodi' onchange='submit()'>";
echo "<option value='' >".t("Kaikki")."</option>";

while ($vrow = mysql_fetch_array($vresult)) {
	$sel = "";
	if ($maakoodi == $vrow[0]) $sel = "selected";
	echo "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[0])."</option>";
}

echo "</select></td><tr>";
echo "</table>";
echo "</form><br>";

$lisa = "";

if ($tilino != "") {
	$lisa .= " and s.tilino = '$tilino' ";
}

if ($valuutta != "") {
	$lisa .= " and s.valkoodi = '$valuutta' ";
}

if ($maakoodi != "") {
	$lisa .= " and a.maa = '$maakoodi' ";
}

$query = "	SELECT a.nimi nimi, a.ytunnus ytunnus, s.asiakas_tunnus tunnus, COUNT(s.asiakas_tunnus) maara, sum(if(s.viite>0, 1,0)) viitteita
			FROM suoritus s, asiakas a
			WHERE s.asiakas_tunnus<>0
			AND s.asiakas_tunnus=a.tunnus
			AND s.yhtio ='$kukarow[yhtio]'
			AND a.yhtio ='$kukarow[yhtio]'
			AND kohdpvm='0000-00-00'
			AND ltunnus!=0
			$lisa
			GROUP BY s.asiakas_tunnus
			ORDER BY a.nimi";
$result = mysql_query($query) or pupe_error($query);

echo "	<table>
		<tr>
		<th>Asiakas</th>
		<th>".t("Kohdistamattomia suorituksia")."</th>
		<th>".t("Avoimia laskuja")."
		</tr>";

while ($asiakas = mysql_fetch_array($result)) {

	// Onko asiakkaalla avoimia laskuja???
	$query = "	SELECT COUNT(*) maara
				FROM lasku
				WHERE yhtio ='$kukarow[yhtio]'
				and mapvm='0000-00-00'
				and tila = 'U'
				and ytunnus = '$asiakas[ytunnus]'";
	$lresult = mysql_query($query) or pupe_error($query);
	$lasku = mysql_fetch_array ($lresult);

	echo "<tr>
			<td>$asiakas[nimi]</td>
			<td>$asiakas[maara] / $asiakas[viitteita]</td>
			<td>$lasku[maara]</td>";

	echo "<form action='$PHP_SELF' method='POST'>";
	echo "<input type='hidden' name='tila' value='suorituksenvalinta'>";
	echo "<input type='hidden' name='asiakas_tunnus' value='$asiakas[tunnus]'>";
	echo "<td class='back'><input type='submit' value='".t("Valitse")."'></td>";
	echo "</form>";

	echo "<form action='$PHP_SELF' method='POST'>";
	echo "<input type='hidden' name='tila' value='suorituksenvalinta'>";
	echo "<input type='hidden' name='asiakas_tunnus' value='$asiakas[tunnus]'>";
	echo "<input type='hidden' name='asiakas_nimi' value='$asiakas[nimi]'>";
	echo "<td class='back'><input type='submit' value='".t("Valitse nimellä")."'></td>";
	echo "</form></tr>";
}

echo "</table>";

?>
