<?php

require ("inc/parametrit.inc");

echo "<font class='head'>Korjaa avoimia myyntilaskuja - $yhtiorow[nimi]</font><hr>";

// tullaan tyhjill‰ arvoilla, hyp‰t‰‰n seuraavaan
if ($sappl == "" and $sakkl == "" and $savvl == "" and $samaksettu == "" and $tee == "paivita") {
	$tee = "";
}

if ($tee == "paivita") {

	$error = 0;

	if ($sappl != "" or $sakkl != "" or $savvl != "") {

		$sakkl = (int) $sakkl;
		$sappl = (int) $sappl;
		$savvl = (int) $savvl;

		if (!checkdate($sakkl,$sappl,$savvl)) {
			echo "<font class='error'>virheellinen p‰iv‰m‰‰r‰!</font><br>";
			$error = 1;
		}
	}

	$samaksettu = (float) str_replace(",", ".", $samaksettu);

	if ($samaksettu >= $summa and $sappl == "" and $sakkl == "" and $savvl == "") {
		echo "<font class='error'>lasku kokonaan maksettu, merkkaa mapvm!</font><br>";
		$error = 1;
	}

	$sakkl = (int) $sakkl;
	$sappl = (int) $sappl;
	$savvl = (int) $savvl;

	if ($error == 1) {
		$tee = "";
		$laskunro--;
		$ytunnus--;
	}

}

if ($tee == "paivita") {
	$query = "update lasku set mapvm='$savvl-$sakkl-$sappl', saldo_maksettu='$samaksettu', comments=concat(comments, ' $kukarow[kuka] korjasi ', now(), ' (korjaa_avoimia_myyntilaskuja).') where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	echo "<font class='message'>Lasku $laskunro p‰ivitetty!</font><br>";
}

if ($ytunnus != "") {

	$query = "	SELECT *, summa-saldo_maksettu maksamatta
				FROM lasku use index (yhtio_tila_mapvm)
				WHERE tila = 'u' and
				alatila	= 'x' and
				mapvm = '0000-00-00' and
				erpcm != '0000-00-00' and
				tapvm <= '$tavvl-$takkl-$tappl' and
				yhtio = '$kukarow[yhtio]' and
				ytunnus + 0 > '$ytunnus' and
				laskunro > '$laskunro'
				HAVING maksamatta > 0
				ORDER BY ytunnus + 0, laskunro
				LIMIT 1";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		$row = mysql_fetch_array($result);

		echo "<table>";
		echo "<tr>";
		echo "<th>laskunro</th>";
		echo "<th>ytunnus</th>";
		echo "<th>nimi</th>";
		echo "<th>toim_nimi</th>";
		echo "<th>summa</th>";
		echo "<th>maksettu</th>";
		echo "<th>maksamatta</th>";
		echo "<th>valkoodi</th>";
		echo "<th>mapvm</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<td>$row[laskunro]</td>";
		echo "<td>$row[ytunnus]</td>";
		echo "<td>$row[nimi]</td>";
		echo "<td>$row[toim_nimi]</td>";
		echo "<td>$row[summa]</td>";
		echo "<td>$row[saldo_maksettu]</td>";
		echo "<td>$row[maksamatta]</td>";
		echo "<td>$row[valkoodi]</td>";
		echo "<td>$row[mapvm]</td>";
		echo "</tr>";
		echo "</table>";

		echo "<br>";

		echo "<form action='$PHP_SELF' method='post' name='kala'>";
		echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
		echo "<input type='hidden' name='tee' value='paivita'>";
		echo "<input type='hidden' name='tunnus' value='$row[tunnus]'>";
		echo "<input type='hidden' name='summa' value='$row[summa]'>";
		echo "<input type='hidden' name='laskunro' value='$row[laskunro]'>";
		echo "<input type='hidden' name='tappl' value='$tappl'>";
		echo "<input type='hidden' name='takkl' value='$takkl'>";
		echo "<input type='hidden' name='tavvl' value='$tavvl'>";

		if ($row["saldo_maksettu"] == 0) $row["saldo_maksettu"] = "";

		echo "<table>";
		echo "<tr><th>Saldo maksettu</th><td valign='top'><input type='text' name='samaksettu' value='$row[saldo_maksettu]'></td></tr>";
		echo "<tr><th>Mapvm ppkkvv</th><td valign='top'><input type='text' name='sappl' size='3'><input type='text' name='sakkl' size='3'><input type='text' name='savvl' size='5'></td><td><input type='submit' value='p‰ivit‰'></td></tr>";
		echo "</table>";

		echo "</form>";

		$formi = "kala";
		$kentta = "samaksettu";
	}

}
else {

	echo "<form action='$PHP_SELF' method='post' name='kala'>";
	echo "<input type='hidden' name='tee' value=''>";
	echo "<table>";
	echo "<tr><th>Aloita ytunnuksesta:</th><td valign='top'><input type='text' name='ytunnus' size ='15'></td></tr>";
	echo "<tr><th>Tapvm takaraja ppkkvv</th><td valign='top'><input type='text' name='tappl' size='3' value='01'><input type='text' name='takkl' size='3' value='10'><input type='text' name='tavvl' size='5' value='2007'></td><td><input type='submit' value='go'></td></tr>";
	echo "</table>";
	echo "</form>";

	$formi = "kala";
	$kentta = "ytunnus";

}

require ("inc/footer.inc");

?>