<?php

require("../inc/parametrit.inc");

echo "<font class='head'>".t("Hyvityslaskujen kappalemäärien seuranta").":</font><hr>";

if (!$vva) {
	$vva = date('Y');
}

echo "<table>";
echo "<form name='hyvityskpl' action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<tr><th>".t("Syötä vuosi (vvvv)")."</th><td><input type='text' name='vva' value='$vva' size='5'></td><td class='back'><input type='submit' name='submit' value='Hae'></td></tr>";
echo "</form>";
echo "</table>";

$summa = 0;

if (isset($submit)) {

	echo "<table>";
	echo "<tr><th></th><th>".t("Tammikuu")."</th><th>".t("Helmikuu")."</th><th>".t("Maaliskuu")."</th><th>".t("Huhtikuu")."</th><th>".t("Toukokuu")."</th><th>".t("Kesäkuu")."</th><th>".t("Heinäkuu")."</th><th>".t("Elokuu")."</th><th>".t("Syyskuu")."</th><th>".t("Lokakuu")."</th><th>".t("Marraskuu")."</th><th>".t("Joulukuu")."</th><th>".t("Yhteensä")."</th></tr>";
	echo "<tr><th>".t("Kappalemäärä").":</th>";
	
	$query = "	SELECT SUM(IF(summa<0, 1, 0)) as kpl, LEFT(tapvm, 7) as tapvm, SUM(IF(arvo<0,arvo,0)) as arvo
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' AND tila = 'U' AND alatila = 'X' AND tapvm >= '$vva-01-01' AND tapvm <= '$vva-12-31'
				GROUP BY 2
				ORDER BY 2";

	$hyvityslaskures = mysql_query($query) or pupe_error($query);

	while ($hyvityslaskurow = mysql_fetch_array($hyvityslaskures)) {
		$hyvityslasku[] = $hyvityslaskurow;
	}

	$hyvitysarvo = array();

	for ($i = 0; $i < 12; $i++) {
		if ($hyvityslasku[$i] != null) {
			$summa += $hyvityslasku[$i]['kpl'];
			echo "<td align='right'>".$hyvityslasku[$i]['kpl']."</td>";
		} else {
			echo "<td align='right'>0</td>";
		}
	}
	echo "<td align='right'>$summa</td></tr>";
	
	echo "<tr><th>".t("Arvo").":</th>";
	for ($i = 0; $i < 12; $i++) {
		if ($hyvityslasku[$i] != null) {
			$summa += $hyvityslasku[$i]['arvo'];
			echo "<td align='right'>".$hyvityslasku[$i]['arvo']."</td>";
		} else {
			echo "<td align='right'>0</td>";
		}
	}
	echo "<td align='right'>$summa</td></tr>";
	echo "</table>";
}

require("../inc/footer.inc");

?>