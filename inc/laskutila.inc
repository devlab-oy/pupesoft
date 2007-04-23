<?php
	echo "<font class='message'>".t("Laskut per tila")."</font><hr>";
	flush();

	$query = "	SELECT tila, alatila, round(sum(summa * valuu.kurssi),2) summa, count(*) kpl
				FROM lasku use index (tila_index)
				LEFT JOIN valuu ON valuu.yhtio = lasku.yhtio and lasku.valkoodi	= valuu.nimi
				WHERE lasku.yhtio	= '$kukarow[yhtio]' 
				and lasku.tila in ('H','Y','M','P','Q')
				GROUP BY tila, alatila";
	$result = mysql_query($query) or pupe_error($query);
	
	echo "<table>";
	echo "<tr><th>".t("Tila")."</th><th>".t("Summa")."</th><th>".t("Kpl")."</th></tr>";

	while ($trow=mysql_fetch_array($result)) {
		$laskutyyppi = $trow['tila'];
		$alatila     = $trow['alatila'];
		require "inc/laskutyyppi.inc";

		echo "<tr>";
		echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";
		echo "<td style='text-align: right;'>$trow[summa]</td>";
		echo "<td style='text-align: right;'>$trow[kpl]</td>";
		echo "</tr>";
	}

	echo "</table><br>";
?>
