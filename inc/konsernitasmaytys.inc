<?php
$query = "select * from yhtio where konserni='$yhtiorow[konserni]'";
$konserniresult = mysql_query($query) or pupe_error($query);
echo "<table>";

while ($konsernirow=mysql_fetch_array ($konserniresult)) {
	$query = "select * from yhtio where ytunnus='$yhtiorow[konserni]'";
	$riviresult = mysql_query($query) or pupe_error($query);

	echo "<tr><th colspan='4'>$konsernirow[nimi] ($konsernirow[ytunnus])</th></tr>";		
	$query = "select lasku.ytunnus, lasku.nimi, sum(tiliointi.summa) myyntisaamiset
	from tiliointi, lasku
	where tiliointi.yhtio='$konsernirow[yhtio]' and tilino = '$konsernirow[konsernimyyntisaamiset]' and tiliointi.tapvm > '2007-08-31' and korjattu='' and tiliointi.yhtio=lasku.yhtio and tiliointi.ltunnus=lasku.tunnus
	group by 1";

	$result = mysql_query($query) or pupe_error($query);
	echo "<tr>";
	for ($i = 0; $i < mysql_num_fields($result); $i++) {
		echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
	}
	echo "<th></th>";
	
	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		for ($i=0; $i<mysql_num_fields($result); $i++) {
			echo "<td>$trow[$i]</td>";
		}
		$query = "select * from yhtio where nimi like '%$trow[nimi]'";

		$riviyhtioresult = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($riviyhtioresult) == 1) {
			$riviyhtiorow=mysql_fetch_array ($riviyhtioresult);
			$query = "select sum(tiliointi.summa) ostovelat
						from tiliointi, lasku
						where tiliointi.yhtio='$riviyhtiorow[yhtio]' and tilino = '$riviyhtiorow[konserniostovelat]' and tiliointi.tapvm > '2007-08-31' and korjattu='' and tiliointi.yhtio=lasku.yhtio and tiliointi.ltunnus=lasku.tunnus and lasku.nimi = '$konsernirow[nimi]'";
			$ostovelatresult = mysql_query($query) or pupe_error($query);
			$ostovelatrow=mysql_fetch_array ($ostovelatresult);
			echo "<td>$ostovelatrow[ostovelat]</td>";
		}
		else {
			echo "<td>N/A</td>";
		}
		echo "</tr>";
	}
}
echo "</table>";
?>
