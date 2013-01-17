<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>",t("Hinnoittelupoikkeamat-raportti"),"</font><hr />";

	if (!isset($app)) $app = "01";
	if (!isset($akk)) $akk = date("m", mktime(0, 0, 0, date("m"), 0, date("Y")));
	if (!isset($avv)) $avv = date("Y", mktime(0, 0, 0, date("m"), 0, date("Y")));

	if (!isset($lpp)) $lpp = date("d", mktime(0, 0, 0, date("m"), 0, date("Y")));
	if (!isset($lkk)) $lkk = date("m", mktime(0, 0, 0, date("m"), 0, date("Y")));
	if (!isset($lvv)) $lvv = date("Y", mktime(0, 0, 0, date("m"), 0, date("Y")));

	echo "<table>";

	echo "<tr>";
	echo "<th>",t("Alkup‰iv‰m‰‰r‰"),"</th>";
	echo "<td><input type='text' name='app' value='{$app}' size='5' />";
	echo "<input type='text' name='akk' value='{$akk}' size='5' />";
	echo "<input type='text' name='avv' value='{$avv}' size='5' /></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>",t("Loppup‰iv‰m‰‰r‰"),"</th>";
	echo "<td><input type='text' name='lpp' value='{$lpp}' size='5' />";
	echo "<input type='text' name='lkk' value='{$lkk}' size='5' />";
	echo "<input type='text' name='lvv' value='{$lvv}' size='5' /></td>";
	echo "</tr>";

	echo "</table>";

	require("inc/footer.inc");
