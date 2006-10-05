<?php
	echo "<font class='message'>".t("Laskut per tila")."</font><hr>";
	flush();

	$query = "SELECT tila, alatila, round(sum(summa * valuu.kurssi),2) summa, count(*) kpl
				FROM lasku, valuu
				WHERE lasku.yhtio='$kukarow[yhtio]' and valuu.yhtio='$kukarow[yhtio]' and lasku.valkoodi=valuu.nimi
				GROUP BY tila, alatila";

	$result = mysql_query($query) or pupe_error($query);

	echo "<table>";
	echo "<tr><th>".t("tila")."</th><th>".t("summa")."</th><th>".t("kpl")."</th></tr>";

	while ($trow=mysql_fetch_array($result))
	{
		$laskutyyppi = $trow['tila'];
		$alatila     = $trow['alatila'];
		require "inc/laskutyyppi.inc";

		echo "<tr>";
		echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";
		echo "<td>$trow[summa]</td>";
		echo "<td><a href = 'raportit.php?toim=laskuhaku&tee=Q&itila=$trow[tila]&ialatila=$trow[alatila]'>$trow[kpl]</td>";
		echo "</tr>";
	}

	echo "</table><br>";
?>
