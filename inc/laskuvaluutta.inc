<?php
	echo "<b>".t("Laskut per valuutta")."</b><hr>";
	$query = "SELECT valkoodi, sum(summa),sum(summa * valuu.kurssi), count(*)
		  FROM lasku, valuu
		  WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.yhtio = '$kukarow[yhtio]' and
		      lasku.valkoodi = valuu.nimi and lasku.tila in ('H','Y','M','P','Q')
		  GROUP BY valkoodi
		  ORDER BY valkoodi";

	$result = mysql_query($query) or pupe_error($query);

	echo "<table>";
	echo "<tr><th>".t("Valkoodi")."</th><th>".t("Summa valuutassa")."</th><th>".t("Summa")."</th><th>".t("Kpl")."</th></tr>";
	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		for ($i=0; $i<mysql_num_fields($result); $i++) {
			if (($i > 0) && ($i < 3)) {
				echo "<td align = 'right'>";
				printf("%.2f", $trow[$i]);
				echo "</td>";
			}
			else {
				echo "<td>$trow[$i]</td>";
			}
		}
		echo "</tr>";
	}
	echo "</table><br>";
?>