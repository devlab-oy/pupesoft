<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Valmista tuote").":</font><hr>";

if ($tee != "") {
	require("valmistatuotteita.inc");
}

if ($tee=='') {
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name = 'tee' value='UV'>";
	echo "<table>";
	echo "<tr><th>".t("Valmistettava tuote")."</th><td><input type='text' name = 'tuoteno' value='$tuoteno'></td></tr>";
	echo "<tr><th>".t("M‰‰r‰")."</th><td><input type='text' name = 'atil' value='$atil'></td></tr>";
	echo "<th></th><td><input type='submit' value='".t("Valmista")."'></td>";
	echo "<table>";
}

require("inc/footer.inc");

?>