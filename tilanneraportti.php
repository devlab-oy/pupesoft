<?php

	require("inc/parametrit.inc");
	echo "<font class='head'>Tilanneraportti</font><hr><br>";

	if ($valittu != "") {
		$query = "SELECT nimi from yhtio where yhtio='$valittu'";
		$result = mysql_query($query) or pupe_error($query);
		$yrow=mysql_fetch_array($result);
		echo "<table><tr><th colspan = '2'>$yrow[nimi]</td></tr><tr><td>";
		echo "<font class='head'>Yrityksen perustietojen määrä</font><br><br>";
		$taulut[]="asiakas";
		$taulut[]="toimi";
		$taulut[]="tuote";
		$taulut[]="lasku";
		echo "<table><tr><th>Taulu</th><th>Yhtiö kpl</th><th>Yhtiö %</th><th>Keskimäärä</th></tr>";
		foreach ($taulut as $taulu) {
			$query = "SELECT count(distinct(yhtio)) maara, count(*) kpl from $taulu";
			$xresult = mysql_query($query) or pupe_error($query);
			$query = "SELECT count(*) kpl from $taulu where yhtio='$valittu'";
			$result = mysql_query($query) or pupe_error($query);

			$totaali = 0;
			$xrow=mysql_fetch_array($xresult);
			$row=mysql_fetch_array($result);
			$keskiarvo=round($xrow['kpl']/$xrow['maara'],0);
			echo "<tr><td>$taulu</td><td align='right'>$row[kpl]</td><td align='right'>".round($row['kpl']/$keskiarvo*100,0)."</td><td align='right'>$keskiarvo</td></tr>";
		}
		echo "</table>";
		
		echo "</td>";
		echo "<td>";
		
		echo "<font class='head'>Yrityksen tapahtumien laatijat</font><br><br>";
		$query = "select laatija, count(*) kpl from lasku where yhtio='$valittu' group by 1";
		$result = mysql_query($query) or pupe_error($query);
		echo "<table><tr><th>Tekijä</th><th>Kpl</th><th>Osuus %</th></tr>";
		$totaali = 0;
		while ($row=mysql_fetch_array($result)) {
			$totaali += $row['kpl'];
		}
		mysql_data_seek($result,0);
		while ($row=mysql_fetch_array($result)) {
			$osuus = $row['kpl']/$totaali*100;
			echo "<tr><td>$row[laatija]</td><td align='right'>$row[kpl]</td><td align='right'>".round($osuus,0)."</td></tr>";
		}
		echo "</table>";
		
		echo "</td></tr></table>";
	}


	echo "<form action=''><select name='valittu'>";

	$query = "SELECT yhtio, nimi FROM yhtio ORDER BY nimi";

	$result = mysql_query($query) or pupe_error($query);
	
	while ($row=mysql_fetch_array($result)) {
		echo "<option value='$row[yhtio]'>$row[nimi]</option>";
	}
	echo "</select><input type='submit' value='Valitse'></form>";

	require("inc/footer.inc");

?>
