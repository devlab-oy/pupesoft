<?php

require("../inc/parametrit.inc");

echo "<font class='head'>".t("Hyvityslaskujen kappalem��rien seuranta").":</font><hr>";

if (!$vva) {
	$vva = date('Y');
}

echo "<table>";
echo "<form name='hyvityskpl' action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<tr><th>".t("Sy�t� vuosi (vvvv)")."</th><td><input type='text' name='vva' value='$vva' size='5'></td><td class='back'><input type='submit' name='submit' value='Hae'></td></tr>";
echo "</form>";
echo "</table>";

$summa = 0;

if (isset($submit)) {

	echo "<table>";
	echo "<tr><th></th><th>",t("Tammikuu"),"</th><th>",t("Helmikuu"),"</th><th>",t("Maaliskuu"),"</th><th>",t("Huhtikuu"),"</th><th>",t("Toukokuu"),"</th><th>",t("Kes�kuu"),"</th><th>",t("Hein�kuu"),"</th><th>",t("Elokuu"),"</th><th>",t("Syyskuu"),"</th><th>",t("Lokakuu"),"</th><th>",t("Marraskuu"),"</th><th>",t("Joulukuu"),"</th><th>",t("Yhteens�"),"</th></tr>";
	echo "<tr><th>",t("Normaalit laskut"),"</th>";
	
	$query = "	SELECT SUM(IF(summa>0, 1, 0)) as kpl, LEFT(tapvm, 7) as tapvm, SUM(IF(arvo>0, arvo, 0)) as arvo
				FROM lasku
				WHERE yhtio='$kukarow[yhtio]' AND tila='U' AND alatila='X' AND tapvm >= '$vva-01-01' AND tapvm <= '$vva-12-31'
				GROUP BY 2
				ORDER BY 2";
	$laskures = mysql_query($query) or pupe_error($query);
	
	while ($laskurow = mysql_fetch_array($laskures)) {
		$lasku[] = $laskurow;
	}
	
	$arvo = array();
	$kplsumma = 0;
	
	for ($i = 0; $i < 12; $i++) {
		if ($lasku[$i] != null) {
			$kplsumma += $lasku[$i]['kpl'];
			echo "<td align='right'>",$lasku[$i]['kpl'],"</td>";
		} else {
			echo "<td align='right'>0</td>";
		}
	}
	
	echo "<td align='right'>$kplsumma</td></tr>";
	
	echo "<tr><th>",t("Arvo"),"</th>";
	for ($i = 0; $i < 12; $i++) {
		if ($lasku[$i] != null) {
			$arvosumma += $lasku[$i]['arvo'];
			echo "<td align='right'>",$lasku[$i]['arvo'],"</td>";
		} else {
			echo "<td align='right'>0</td>";
		}
	}
	echo "<td align='right'>$arvosumma</td></tr>";
	
	echo "<tr><td class='back'>&nbsp;</td></tr>";

	echo "<tr><th>",t("Hyvityslaskut"),"</th>";
	
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
	$hyvityssumma = 0;

	for ($i = 0; $i < 12; $i++) {
		if ($hyvityslasku[$i] != null) {
			$hyvityssumma += $hyvityslasku[$i]['kpl'];
			echo "<td align='right'>",$hyvityslasku[$i]['kpl'],"</td>";
		} else {
			echo "<td align='right'>0</td>";
		}
	}
	echo "<td align='right'>$hyvityssumma</td></tr>";
	
	echo "<tr><th>",t("Hyvityslaskujen arvo"),"</th>";
	for ($i = 0; $i < 12; $i++) {
		if ($hyvityslasku[$i] != null) {
			$summa += $hyvityslasku[$i]['arvo'];
			echo "<td align='right'>",$hyvityslasku[$i]['arvo'],"</td>";
		} else {
			echo "<td align='right'>0</td>";
		}
	}
	echo "<td align='right'>$summa</td></tr>";

	echo "<tr><td class='back'>&nbsp;</td></tr>";

	echo "<tr><th>",t("Hyvityslaskujen")," %</th>";
	
	for ($i = 0; $i < 12; $i++) {
		$laskujen_prosenttiosuus = ($hyvityslasku[$i]['kpl'] / $lasku[$i]['kpl']) * 100;
		echo "<td align='right'>",round($laskujen_prosenttiosuus, 2)," %</td>";
	}
	$summien_prosenttiosuus = ($hyvityssumma / $kplsumma) * 100;
	echo "<td align='right'>",round($summien_prosenttiosuus, 2)," %</td></tr>";
	
	echo "<tr><th>",t("Hyvityslaskujen arvojen")," %</th>";
	
	$summien_prosenttiosuus = 0;
	
	for ($i = 0; $i < 12; $i++) {
		$arvojen_prosenttiosuus = (abs($hyvityslasku[$i]['arvo']) / abs($lasku[$i]['arvo'])) * 100;
		echo "<td align='right'>",round($arvojen_prosenttiosuus, 2)," %</td>";
	}
	
	$summien_prosenttiosuus = (abs($summa) / abs($arvosumma)) * 100;
	echo "<td align='right'>",round($summien_prosenttiosuus, 2)," %</td></tr>";

	echo "</table>";
}

require("../inc/footer.inc");

?>