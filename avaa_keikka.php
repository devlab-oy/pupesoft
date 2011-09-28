<?php

require ("inc/parametrit.inc");

print "<font class='head'>Avaa keikka</font><hr>";

if ($tee == "avaa") {

	$query = "	UPDATE lasku
				SET alatila = '',
				kohdistettu = 'K'
				WHERE yhtio = '$kukarow[yhtio]' and
				tila = 'K' and
				laskunro = '$keikka' and
				tunnus = '$tunnus' and
				vanhatunnus = 0";
	$res = mysql_query($query) or pupe_error($query);

	if (mysql_affected_rows() != 1) {
		echo "<font class='error'>Keikan avaus epäonnistui!</font>";
		$tee = "etsi";
	}
	else {
		echo "<font class='message'>Keikka avattu!</font>";
		$tee = "";
	}

	echo "<br><br>";
}

if ($tee == "etsi") {

	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and
				tila = 'K' and
				laskunro = '$keikka' and
				vanhatunnus = 0 and
				alatila = 'X' and
				kohdistettu = 'X'";
	$res = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($res) == 1) {

		$row = mysql_fetch_array($res);

		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tee' value='avaa'>";
		echo "<input type='hidden' name='tunnus' value='$row[tunnus]'>";
		echo "<input type='hidden' name='keikka' value='$row[laskunro]'>";

		echo "<table>";

		echo "<tr>";
		echo "<th>keikka</th>";
		echo "<th>ytunnus</th>";
		echo "<th>nimi</th>";
		echo "<th>tapvm</th>";
		echo "<th></th>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>$row[laskunro]</td>";
		echo "<td>$row[ytunnus]</td>";
		echo "<td>$row[nimi]</td>";
		echo "<td>$row[tapvm]</td>";
		echo "<td><input type='submit' value='Avaa'></td>";
		echo "</tr>";

		echo "</table><br>";

		echo "</form>";
	}
	else {
		echo "<font class='error'>Keikkaa $keikka ei löytynyt!</font>";
	}

}

echo "<form action='$PHP_SELF' method='post'>";
echo "<input type='hidden' name='tee' value='etsi'>";

echo "<table>";
echo "<tr>";
echo "<th>Syötä keikkanumero: </th>";
echo "<td><input type='text' name='keikka'></td>";
echo "</tr>";
echo "</table>";

echo "<br><input type='submit' value='Etsi keikka'>";
echo "</form>";

require ("inc/footer.inc");

?>