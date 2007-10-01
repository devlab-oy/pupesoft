<?php

	echo "<font class='head'>".t("Laskut joita ei ole liitetty keikkoihin")."</font><hr>";

	if (!isset($app)) $app = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
	if (!isset($akk)) $akk = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
	if (!isset($avv)) $avv = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
	if (!isset($lpp)) $lpp = date("d");
	if (!isset($lkk)) $lkk = date("m");
	if (!isset($lvv)) $lvv = date("Y");

	echo "<form>";
 	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Alkupäivä")."</th>";
	echo "<td><input type='text' name='app' size='5' value='$app'></td>";
	echo "<td><input type='text' name='akk' size='5' value='$akk'></td>";
	echo "<td><input type='text' name='avv' size='5' value='$avv'></td>";
	echo "</tr><tr>";
	echo "<th>".t("Loppupäivä")."</th>";
	echo "<td><input type='text' name='lpp' size='5' value='$lpp'></td>";
	echo "<td><input type='text' name='lkk' size='5' value='$lkk'></td>";
	echo "<td><input type='text' name='lvv' size='5' value='$lvv'></td><td class='back'><input type='submit' value='".t("Hae laskut")."'></td>";
	echo "</tr>";
	echo "</table>";

	
	echo "</form>";

	$query = "	SELECT lasku.ytunnus, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.summa*(if(lasku.maksu_kurssi=0, lasku.vienti_kurssi, lasku.maksu_kurssi)) 'summaeur', lasku.tapvm, lasku.vienti, lasku2.tunnus, lasku.tunnus latunnus
				FROM lasku
				LEFT JOIN lasku AS lasku2 on (lasku2.yhtio = lasku.yhtio and lasku2.vanhatunnus = lasku.tunnus and lasku2.tila = 'K')
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila in ('H','Y','M','P','Q') and
				lasku.vienti in ('B','C','J','E','F','K','H','I','L') and
				lasku.tapvm >= '$avv-$akk-$app' and
				lasku.tapvm <= '$lvv-$lkk-$lpp'
				HAVING lasku2.tunnus IS NULL
				ORDER BY lasku.tapvm, lasku.valkoodi, lasku.summa";
	$result = mysql_query($query) or pupe_error($query);

	echo "<br><table>";
	echo "<tr>";
	echo "<th>".t("Ytunnus")."</th>";
	echo "<th>".t("Nimi")."</th>";
	echo "<th>".t("Summa valuutassa")."</th>";
	echo "<th>".t("Summa")."</th>";
	echo "<th>".t("Tapvm")."</th>";
	echo "<th>".t("Tyyppi")."</th>";
	echo "</tr>";

	$yhteesa = 0;

	while ($trow = mysql_fetch_array($result)) {

		$vienti = $trow["vienti"];
		if ($trow["vienti"] == 'B') $vienti = t('Kotimaa huolinta/rahti');
		if ($trow["vienti"] == 'C') $vienti = t('Kotimaa vaihto-omaisuus');
		if ($trow["vienti"] == 'J') $vienti = t('Kotimaa raaka-aine');
		if ($trow["vienti"] == 'E') $vienti = t('EU huolinta/rahti');
		if ($trow["vienti"] == 'F') $vienti = t('EU vaihto-omaisuus');
		if ($trow["vienti"] == 'K') $vienti = t('EU raaka-aine');
		if ($trow["vienti"] == 'H') $vienti = t('ei-EU huolinta/rahti');
		if ($trow["vienti"] == 'I') $vienti = t('ei-EU vaihto-omaisuus');
		if ($trow["vienti"] == 'L') $vienti = t('ei-EU raaka-aine');

		echo "<tr class='aktiivi'>";
		echo "<td><a href='$palvelin2","muutosite.php?tee=E&tunnus=$trow[latunnus]'>$trow[ytunnus]</a></td>";
		echo "<td>$trow[nimi]</td>";
		echo "<td style='text-align: right;'>".sprintf("%.2f", $trow["summa"])." $trow[valkoodi]</td>";
		echo "<td style='text-align: right;'>".sprintf("%.2f", $trow["summaeur"])." $yhtiorow[valkoodi]</td>";
		echo "<td>".tv1dateconv($trow["tapvm"])."</td>";
		echo "<td>$vienti</td>";
		echo "</tr>";

		$yhteesa += $trow["summaeur"];
	}

	echo "<tr>";
	echo "<th colspan='3'>".t("Yhteensä")."</th>";
	echo "<th style='text-align: right;'>".round($yhteesa,2)." $yhtiorow[valkoodi]</th>";
	echo "<th colspan='2'></th>";
	echo "</tr>";
	echo "</table><br>";

?>