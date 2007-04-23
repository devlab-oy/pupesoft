<?php
	echo "<b>".t("Laskut per valuutta")."</b><hr>";
	
	$query = "	SELECT valkoodi, sum(summa) summa, sum(summa * valuu.kurssi) kotisumma, count(*) kpl
		  		FROM lasku
				LEFT JOIN valuu ON valuu.yhtio = lasku.yhtio and lasku.valkoodi	= valuu.nimi
		  		WHERE lasku.yhtio = '$kukarow[yhtio]' 
				and lasku.tila in ('H','Y','M','P','Q')
		  		GROUP BY valkoodi
		  		ORDER BY valkoodi";
	$result = mysql_query($query) or pupe_error($query);

	echo "<table>";
	echo "<tr><th>".t("Valkoodi")."</th><th>".t("Summa valuutassa")."</th><th>".t("Summa")."</th><th>".t("Kpl")."</th></tr>";
	
	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		
		echo "<td>$trow[valkoodi]</td>";
		echo "<td style='text-align: right;'>".sprintf("%.2f", $trow["summa"])."</td>";
		echo "<td style='text-align: right;'>".sprintf("%.2f", $trow["kotisumma"])."</td>";
		echo "<td style='text-align: right;'>$trow[kpl]</td>";
		echo "</tr>";
	}
	echo "</table><br>";
?>