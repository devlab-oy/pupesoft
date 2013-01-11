<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("K‰teisotto kassalippaasta")."</font><br/>";

$kassalippaat = hae_kassalippaat();
$kateisoton_luonteeet = hae_kateisoton_luonteet();

echo_kateisotto_form($kassalippaat, $kateisoton_luonteeet);

function hae_kassalippaat() {
	global $kukarow;
	
	$query = "	SELECT *
				FROM kassalipas
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);
	
	$kassalippaat = array();
	while($kassalipas = mysql_fetch_assoc($result)) {
		$kassalippaat[] = $kassalipas;
	}
	
	return $kassalippaat;
}

function hae_kateisoton_luonteet() {
	global $kukarow;

	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio = '{$kukarow['yhtio']}'
				";
	//$result = pupe_query($query);

	$kateisoton_luonteet = array();
	while($kateisoton_luonne = mysql_fetch_assoc($result)) {
		$kateisoton_luonteet[] = $kateisoton_luonne;
	}

	return $kateisoton_luonteet;
}

function echo_kateisotto_form($kassalippaat, $kateisoton_luonteet) {
	echo "<form name='kateisotto' method='POST'>";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Kassalipas")."</th>";
	echo "<td>";
	echo "<select name='kassalipas' id='kassalipas'>";
	foreach ($kassalippaat as $kassalipas) {
		echo "<option value='{$kassalipas['tunnus']}'>{$kassalipas['nimi']}</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Summa")."</th>";
	echo "<td><input type='text' name='summa' id='summa'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Mihin tarkoitukseen k‰teisotto tehd‰‰n")."</th>";
	echo "<td>";
	echo "<select name='kateisoton_luonne' id='kateisoton_luonne'>";
	foreach ($kateisoton_luonteet as $luonne) {
		echo "<option value='{$luonne['tunnus']}'>{$luonne['selite']}</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Yleinen kommentti")."</th>";
	echo "<td><input type='text' name='yleinen_kommentti' id='yleinen_kommentti'></td>";
	echo "</tr>";

	echo "<td class='back'><input name='submit' type='submit' value='".t("L‰het‰")."'></td>";

	echo "</table>";
	echo "</form>";
}
?>
