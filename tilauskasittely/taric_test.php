<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>Hae tuotteen tulliprossa (veroperustetieto):</font><hr>";


echo "<form method='post' action='$PHP_SELF'>
	<br><br>
	<table>
	<tr><td>Syötä tuotenumero:</td>
		<td><input type='text' name='tuoteno' value = '$tuoteno'></td>
		<td class='back'><input type='submit' value='Hae'></td>
	</tr>
	</table>
	</form><br><br>";


if ($tuoteno != '') {

	$query = "	SELECT *
				FROM tuote
				WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$tuoteno'";
	$result = mysql_query($query) or pupe_error($query);
	$tuorow = mysql_fetch_array($result);

	echo "Tuotteen tiedot: $tuoteno, Tullinimike1: $tuorow[tullinimike1], Tullinimike2: $tuorow[tullinimike2], Alkuperämaa: $tuorow[alkuperamaa]<br><br>";

	require("taric_veroperusteet.inc");
}

require ("../inc/footer.inc");

?>