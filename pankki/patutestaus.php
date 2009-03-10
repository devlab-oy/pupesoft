<?php
	require ("siirtoa.php");
	require ("../inc/parametrit.inc");
	echo "<font class='head'>".t("Siirtoavaimen päivitys")."<hr></font>";
	if ($tunnus != '') {
		echo "<font class='error'>".ekasiirtoavain($ekapala,$tokapala,$tarkiste,$tunnus)."</font><br><br>";
	}
	echo "<form action = '' method='post'><table><tr><th>".t("Pankkitili");
	$query = "SELECT * FROM yriti WHERE yhtio = '$kukarow[yhtio]'";
	$vresult = mysql_query($query) or pupe_error($query);
	echo "</td><td><select name='tunnus'>";
	while ($vrow=mysql_fetch_array($vresult)) {
		echo "<option value = '$vrow[tunnus]'>$vrow[nimi]";
	}
	echo "</select></td></tr>";
	echo "<tr><th>".t("Avain1")."</th>";
	echo "<td><input type='text' name = 'ekapala'></td></tr>";
	echo "<tr><th>".t("Avain2")."</th>";
	echo "<td><input type='text' name = 'tokapala'></td></tr>";
	echo "<tr><th>".t("Tarkiste")."</th>";
	echo "<td><input type='text' name = 'tarkiste'></td></tr></table>";
	echo "<tr><th></th><td><input type='submit' name = 'Päivitä'></td></tr></table></form>";
	require ("../inc/footer.inc");
?>
