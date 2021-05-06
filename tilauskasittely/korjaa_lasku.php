<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Korjaa lasku").":<hr></font>";

		echo "<form method='post' action='/pupesoft/tarkista_laskut.php'>";
		echo "<input type='hidden' name='tee' value='valitse'>";
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Anna tilausnumero").":</th>";
		echo "<td><input type='text' name='tunnus'></td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";

	require ("../inc/footer.inc");

?>
