<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Sanakirja")."</font><hr>";


echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<table><tr>";
echo "<th>".t("Valitse k‰‰nnett‰v‰t kielet")."</th>";
echo "<td><table>";

$query  = "show columns from sanakirja";
$fields =  mysql_query($query);

$r = 0;

while ($apurow = mysql_fetch_array($fields)) {

	if ($apurow[0] != "tunnus" and $apurow[0] != "fi") {
		$query = "select distinct nimi from maat where koodi='$apurow[0]'";
		$maare = mysql_query($query);
		$maaro = mysql_fetch_array($maare);
		$maa   = strtolower($maaro["nimi"]);
		if ($maa=="") $maa = $apurow[0];

		$sels = '';
		if ($kieli[$r] == $apurow[0]) {
			$sels = "CHECKED";
		}

		echo "<tr><td>".t("$maa")."</td><td><input type='checkbox' name='kieli[$r]' value='$apurow[0]' $sels></td></tr>";
	}
	$r++;
}

echo "</table></td>";
echo "</tr><tr>";

if ($show == "all") {
	$sel1 = 'CHECKED';
}
else {
	$sel2 = 'CHECKED';
}


echo "<th>".t("N‰yt‰ kaikki lauseet")."</th>";
echo "<td><input type='radio' name='show' value='all' $sel1></td>";
echo "</tr><tr>";
echo "<th>".t("N‰yt‰ vain k‰‰nt‰m‰ttˆm‰t lauseet")."</th>";
echo "<td><input type='radio' name='show' value='empty' $sel2></td>";
echo "</tr>";

echo "<tr><td class='back'><br></td></tr>";

echo "<tr><th>".t("Mill‰ kielell‰ etsit‰‰n")."</th>";
echo "<td><select name='etsi_kieli'>";

echo "<option value=''>".t("Ei etsit‰")."</option>";

$query  = "show columns from sanakirja";
$fields =  mysql_query($query);

while ($apurow = mysql_fetch_array($fields)) {

	if ($apurow[0] != "tunnus") {
		$query = "select distinct nimi from maat where koodi='$apurow[0]'";
		$maare = mysql_query($query);
		$maaro = mysql_fetch_array($maare);
		$maa   = strtolower($maaro["nimi"]);
		if ($maa=="") $maa = $apurow[0];

		$sel = '';

		if ($apurow[0] == $etsi_kieli) $sel = "SELECTED";

		echo "<option value='$apurow[0]' $sel>".t("$maa")."</option>";
	}
}

echo "</select></td>";
echo "</tr>";
echo "<tr><th>Hakusana</th><td class='back'><input type='text' name='hakusana' value='$hakusana'></td>";
echo "<td class='back'><input name='nappi1' type='submit' value='".t("Hae")."'></td>";
echo "</tr>";
echo "</table><br>";



if ($maxtunnus > 0) {

	$query  = "show columns from sanakirja";
	$fields =  mysql_query($query);

	// k‰yd‰‰n l‰pi mahdolliset kielet
	while ($apurow = mysql_fetch_array($fields)) {
		// kieli ei saa olla tunnus tai fi
		if ($apurow[0] != "tunnus" and $apurow[0] != "fi") {
			// loopataan kunnes p‰‰st‰‰n maxtunnukseen
			for ($i=0; $i<=$maxtunnus; $i++) {
				// eli etsit‰‰n kielen nimisesta arraysta indexill‰ $i
				if (${$apurow[0]}[$i] != "") {
					$value = addslashes(trim(${$apurow[0]}[$i])); // spacella pystyy tyhjent‰m‰‰n kent‰n, mutta ei tallenneta sit‰
					$query = "update sanakirja set $apurow[0]='$value' where tunnus='$i'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}
		}
	}

	echo "<font class='message'>".t("Sanakirja p‰ivitetty!")."</font><br><br>";
	$tee = "";
}


// jos meill‰ on onnistuneesti valittu kieli
if (sizeof($kieli) > 0) {

	$query  = "select tunnus, fi ";

	foreach ($kieli as $ki) {
		$query .= ", $ki";
	}

	$query .= " from sanakirja ";


	if ($etsi_kieli != '' or $show == 'empty') {
		$query .= " where ";
	}

	if ($etsi_kieli != '') {
		$query .= " $etsi_kieli like '%$hakusana%' ";
	}

	if ($show == "empty") {

		if ($etsi_kieli != '') {
			$query .= " and ";
		}

		$query .= " (fi='' ";
		foreach ($kieli as $ki) {
			$query .= "or $ki='' ";
		}
		$query .= ")";
	}

	$query .= " ORDER BY fi ";

	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='paivita'>";

		echo "<table>";

		echo "<tr>";
		for ($i=1; $i<mysql_num_fields($result); $i++) echo "<th>".mysql_field_name($result, $i)."</th>";
		echo "</tr>";
		
		$laskkaannos = 0;
		
		while ($row = mysql_fetch_array($result)) {
			echo "<tr>";
			echo "<td>$row[fi]</td>";

			if ($row['tunnus'] > $maxtunnus) $maxtunnus = $row['tunnus'];

			for ($i=2; $i<mysql_num_fields($result); $i++) {
				echo "<td><input type='text' size='30' name='".mysql_field_name($result, $i)."[$row[tunnus]]' value='".htmlspecialchars($row[$i],ENT_QUOTES)."'></td>";
			}
			
			$laskkaannos++;
			
			echo "</tr>";
		}
		
		echo "<tr><th>Yhteens‰ k‰‰nt‰m‰tt‰ $laskkaannos</th></tr>";

		echo "</table>";
		echo "<input type='hidden' name='maxtunnus' value='$maxtunnus'>";
		echo "<br><input name='nappi2' type='submit' value='".t("P‰ivit‰")."'>";
	}
	else {
		echo "<font class='message'>".t("Haulla ei lˆytynyt yht‰‰n rivi‰!")."</font>";
	}
}
else {
	echo "<br><br><font class='error'>".t("Valitse k‰‰nnett‰v‰ kieli!")."</font><br><br>";
}

require ("inc/footer.inc");

?>
