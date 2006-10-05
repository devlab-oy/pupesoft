<?php
	if ($tee == '') {
		echo "<form action = 'raportit.php' method='post'>
				<input type = 'hidden' name = 'toim' value = 'laskuerapvm'>
				<input type = 'hidden' name = 'tee' value = '1'>
				<table><tr>
				<td>".t("Laskut per eräpäivä")."</td>
				<td><select name='aika'>
				<option value = 'pv'>".t("Päivä")."
				<option value = 'vi'>".t("Viikko")."
				<option value = 'kk'>".t("Kuukausi")."
				</select></td>
				<td><input type = 'submit' value = '".t("Näytä")."'></td>
				</tr></table></form>";
		exit;
	}

	echo "<b>".t("Laskut per eräpvm")."</b><hr>";
	if ($aika == 'pv') {
		$tapa = 'erpcm';
	}
	if ($aika == 'vi') {
		$tapa = "YEARWEEK(erpcm)";
	}
	if ($aika == 'kk') {
		$tapa = "LEFT(erpcm,7)";
	}
	$query = "SELECT $tapa Aika, sum(summa * valuu.kurssi), count(*)
				FROM lasku, valuu
				WHERE lasku.yhtio = '$kukarow[yhtio]' and valuu.yhtio = '$kukarow[yhtio]' and
				lasku.valkoodi = valuu.nimi and lasku.tila in ('H','Y','M','P','Q')
				GROUP BY Aika
				ORDER BY Aika";

	$result = mysql_query($query) or pupe_error($query);

	echo "<table>";
	echo "<tr><th>".t("Eräpvm")."</th><th>".t("Summa")."</th><th>".t("Kpl")."</th></tr>";
	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		for ($i=0; $i<mysql_num_fields($result); $i++) {
			if ($i < 2) {
				if ($i == 0) {
					if ($aika == 'pv') {
						echo "<td align='right'><a href = raportit.php?toim=laskuhaku&tee=E&pvm=$trow[0]>$trow[$i]</td>";
					}
					else {
						echo "<td align='right'>$trow[$i]</td>";
					}
				}
				else {
					echo "<td align='right'>";
					printf("%.2f", $trow[$i]);
					echo "</td>";
				}
			}
			else {
				echo "<td>$trow[$i]</td>";
			}
		}
		echo "</tr>";
	}
	echo "</table><br>";
?>
