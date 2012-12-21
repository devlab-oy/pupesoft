<?php

	echo "<font class='head'>".t("Avoimet laskut per eräpvm")."</font><hr>";

	$sel1 = $sel2 = $sel3 = "";

	if ($aika == 'pv') {
		$sel1 = 'SELECTED';
	}
	if ($aika == 'vi') {
		$sel2 = "SELECTED";
	}
	if ($aika == 'kk') {
		$sel3 = "SELECTED";
	}

	echo "<form action = 'raportit.php' method='post'>
			<input type = 'hidden' name = 'toim' value = 'laskuerapvm'>
			<input type = 'hidden' name = 'tee' value = '1'>
			<table><tr>
			<td>".t("Laskut per eräpäivä")."</td>
			<td><select name='aika'>
			<option value = 'pv' $sel1>".t("Päivä")."
			<option value = 'vi' $sel2>".t("Viikko")."
			<option value = 'kk' $sel3>".t("Kuukausi")."
			</select></td>
			<td class='back'><input type = 'submit' value = '".t("Näytä")."'></td>
			</tr></table></form><br>";


	if ($aika != '') {

		if ($aika == 'pv') {
			$tapa = 'erpcm';
		}
		if ($aika == 'vi') {
			$tapa = "YEARWEEK(erpcm)";
		}
		if ($aika == 'kk') {
			$tapa = "LEFT(erpcm,7)";
		}
		$query = "	SELECT $tapa aika, sum(summa * valuu.kurssi) summa, count(*) kpl
					FROM lasku, valuu
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and valuu.yhtio = '$kukarow[yhtio]'
					and lasku.valkoodi = valuu.nimi
					and lasku.tila in ('H','M','P','Q')
					GROUP BY Aika
					ORDER BY Aika";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr><th>".t("Jakso")."</th><th>".t("Eräpvm")."</th><th>".t("Summa")."</th><th>".t("Kpl")."</th></tr>";

		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";


			if ($aika == 'pv') {
				echo "<td>".t("Päivä").": ";
				echo "<td align='right'><a href = raportit.php?toim=laskuhaku&tee=E&pvm=$trow[aika]&lopetus=$PHP_SELF////toim=$toim//tee=$tee//aika=$aika>".tv1dateconv($trow["aika"])."</td>";
			}
			if ($aika == 'vi') {
				echo "<td>".t("Viikko").": ";
				echo "<td>".substr($trow["aika"], 4, 2)." - ".substr($trow["aika"], 0, 4)."</td>";
			}
			if ($aika == 'kk') {
				echo "<td>".t("Kuukausi").": ";
				echo "<td>".substr($trow["aika"], 5, 2)." - ".substr($trow["aika"], 0, 4)."</td>";
			}


			echo "<td style='text-align: right;'>".sprintf("%.2f", $trow["summa"])."</td>";
			echo "<td style='text-align: right;'>$trow[kpl]</td>";
			echo "</tr>";
		}
		echo "</table><br>";
	}
