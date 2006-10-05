<?php
if ($eiliittymaa != 'ON') {
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Saatavat")." - $yhtiorow[nimi]</font><hr>";

	echo "<table>";
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='tee' value='NAYTA'>";
	echo "<tr><th>".t("Näytä vain tämä ytunnus").":</th><td><input type='text' name='sytunnus' size ='15' value='$sytunnus'></td></tr>";
	echo "<tr><th>".t("Näytä vain tämä nimi").":</th><td><input type='text' name='nimi' size ='15' value='$nimi'></td></tr>";
	echo "<tr><th>".t("Näytä vain ne joilla saatavaa on yli").":</th><td><input type='text' name='yli' size ='15' value='$yli'></td></tr>";

	$chk ='';

	if ($ylilimiitin != '') {
		$chk = "CHECKED";
	}

	echo "<tr><th>".t("Näytä vain ne joilla luottoraja on ylitetty").":</th><td><input type='checkbox' name='ylilimiitin' value='ON' $chk></td>";
	echo "<td class='back'><input type='submit' value='".t("Näytä")."'></td><td class='back'>".t("Jätä kaikki kentät tyhjäksi jos haluat listata kaikki saatavat").".</td></tr>";
	echo "</form>";
	echo "</table><br>";
}

if ($tee == 'NAYTA' or $eiliittymaa == 'ON') {

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Ytunnus")."</th>";
	echo "<th>".t("Nimi")."</th>";
	echo "<th align='right'>".t("Alle 0 pv")."</th>";
	echo "<th align='right'>".t("0-15 pv")."</th>";
	echo "<th align='right'>".t("16-30 pv")."</th>";
	echo "<th align='right'>".t("31-60 pv")."</th>";
	echo "<th align='right'>".t("Yli 60 pv")."</th>";
	echo "<th align='right'>".t("Kaatotili")."</th>";
	echo "<th align='right'>".t("Yhteensä")."</th>";
	echo "<th align='right'>".t("Luottoraja")."</th>";
	echo "</tr>";

	$lisa = '';

	if ($nimi != '') {
		$lisa .= " and lasku.nimi like '%$nimi%' ";
	}
	if ($sytunnus != '') {
		$lisa .= " and lasku.ytunnus='$sytunnus' ";
	}
	if ($yli != 0) {
		$having = " HAVING ll >= $yli ";
	}
	else {
		$having = " HAVING ll > 0 ";
	}

	$query = "	SELECT
				lasku.ytunnus, lasku.nimi, lasku.liitostunnus, lasku.toim_nimi,
				round(sum(summa-saldo_maksettu),2) ll,
				sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) <= 0, summa-saldo_maksettu, 0)) aa,
				sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) >  0 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 15, summa-saldo_maksettu, 0)) aabb,
				sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 15 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 30, summa-saldo_maksettu, 0)) bb,
				sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 30 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 60, summa-saldo_maksettu, 0)) cc,
				sum(if(TO_DAYS(NOW())-TO_DAYS(erpcm) > 60, summa-saldo_maksettu, 0)) dd
				FROM lasku use index (yhtio_tila_mapvm)
				WHERE tila='U'
				AND alatila='X'
				AND mapvm='0000-00-00'
				AND erpcm != '0000-00-00'
				$lisa
				AND lasku.yhtio='$kukarow[yhtio]'
				GROUP BY 1,2,3
				$having
				order by 1,2,3";
	$result = mysql_query($query) or pupe_error($query);
	
	$aay = 0;
	$aabby = 0;
	$bby = 0;
	$ccy = 0;
	$ddy = 0;
	$kky = 0;
	$lly = 0;
	$ylikolkyt = 0;
	$rivilask = 0;

	while ($row = mysql_fetch_array($result)) {

		$query = "	SELECT luottoraja
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]'
					and tunnus='$row[liitostunnus]'";
		$asresult = mysql_query($query) or pupe_error($query);
		$asrow = mysql_fetch_array($asresult);

		$query = "	SELECT
					sum(summa) summa
					FROM suoritus
					WHERE yhtio='$kukarow[yhtio]'
					and asiakas_tunnus='$row[liitostunnus]'
					and kohdpvm='0000-00-00'";
		$suresult = mysql_query($query) or pupe_error($query);
		$surow = mysql_fetch_array($suresult);

		if (($ylilimiitin == '') or ($ylilimiitin == 'ON' and $row["ll"] > $asrow["luottoraja"] and $asrow["luottoraja"] != '')) {

			if ($row["nimi"] != $row["toim_nimi"]) $row["nimi"] .= "<br>$row[toim_nimi]";

			echo "<tr>";
			echo "<td>$row[ytunnus]</td>";
			echo "<td>$row[nimi]</td>";
			echo "<td align='right'>".str_replace(".",",",$row["aa"])."</td>";
			echo "<td align='right'>".str_replace(".",",",$row["aabb"])."</td>";
			echo "<td align='right'>".str_replace(".",",",$row["bb"])."</td>";
			echo "<td align='right'>".str_replace(".",",",$row["cc"])."</td>";
			echo "<td align='right'>".str_replace(".",",",$row["dd"])."</td>";
			echo "<td align='right'>".str_replace(".",",",$surow["summa"])."</td>";
			echo "<td align='right'>".str_replace(".",",",$row["ll"])."</td>";
			echo "<td align='right'>".str_replace(".",",",$asrow["luottoraja"])."</td>";
			echo "</tr>";

			$aay += $row["aa"];
			$aabby += $row["aabb"];
			$bby += $row["bb"];
			$ccy += $row["cc"];
			$ddy += $row["dd"];
			$kky += $surow["summa"];
			$lly += $row["ll"];
			
			$ylikolkyt += $row["cc"];
			$ylikolkyt += $row["dd"];
			
			$rivilask++;
		}
	}

	if ($eiliittymaa != 'ON' or $rivilask > 1) {
		echo "<tr>";
		echo "<td class='tumma' align='right' colspan='2'>".t("Yhteensä").":</th>";
		echo "<td class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$aay))."</td>";
		echo "<td class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$aabby))."</td>";
		echo "<td class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$bby))."</td>";
		echo "<td class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$ccy))."</td>";
		echo "<td class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$ddy))."</td>";
		echo "<td class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$kky))."</td>";
		echo "<td class='tumma' align='right'>".str_replace(".",",",sprintf('%.2f',$lly))."</td>";
		echo "<td class='tumma'></td>";
		echo "</tr>";
	}
}

echo "</table>";

if ($eiliittymaa != 'ON') {
	require ("../inc/footer.inc");
}

?>
