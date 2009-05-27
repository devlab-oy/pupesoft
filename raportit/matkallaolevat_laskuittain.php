<?php

	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Matkallaolevat laskuittain")."</font><hr>";

	$query = "	SELECT lasku.tunnus, lasku.nimi, lasku.summa, lasku.valkoodi, tiliointi.tapvm, sum(tiliointi.summa) matkalla 
				from lasku 
				join tiliointi on tiliointi.yhtio=lasku.yhtio and tiliointi.ltunnus=lasku.tunnus and tilino = '$yhtiorow[matkalla_olevat]' and korjattu=''
				where lasku.yhtio = '$kukarow[yhtio]' 
				and lasku.tila in ('H', 'Y', 'M', 'P', 'Q', 'X') 
				group by 1, 2, 3, 4
				having matkalla != 0 
				order by lasku.nimi, lasku.tapvm, lasku.summa";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Pvm")."</th>";
		echo "<th>".t("Summa")."</th>";
		echo "<th>".t("Valuutta")."</th>";					
		echo "<th>".t("Matkalla")."</th>";
		echo "<th>".t("Valuutta")."</th>";
		echo "</tr>";

		$summa = 0;
		$alvsumma=array();
		while ($row = mysql_fetch_array($result)) {
			echo "<tr class='aktiivi'>";
			echo "<td>$row[nimi]</td>";
			echo "<td>".tv1dateconv($row["tapvm"])."</td>";
			echo "<td align='right'>$row[summa]</td>";		
			echo "<td align='right'>$row[valkoodi]</td>";				
			echo "<td align='right'>$row[matkalla]</td>";
			echo "<td align='right'>$yhtiorow[valkoodi]</td>";
			echo "</tr>";
			$summa += $row["matkalla"];
		}

		echo "<tr>";
		echo "<th colspan='4'>".t("Yhteensä")."</th>";
		echo "<th style='text-align:right;'>". sprintf("%.02f", $summa)."</td>";
		echo "<th></th>";
		echo "</tr>";

		echo "</table>";
	}
	
	require ("../inc/footer.inc");

?>