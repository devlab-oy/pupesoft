<?php

require ("inc/parametrit.inc");

echo "<font class='head'>Yhteystiedot</font><hr>";

	if ($sort != '') {
		$sortlisa = "?sort=".$sort;
	}

	
	
	if ($sort == "nimi")				$sort = "order by kuka.nimi,kuka.yhtio";
	elseif ($sort == "puhno")		$sort = "order by kuka.puhno,kuka.yhtio";
	elseif ($sort == "eposti")		$sort = "order by kuka.eposti,kuka.yhtio";
	elseif ($sort == "osasto")		$sort = "order by kuka.osasto,kuka.yhtio";
	elseif ($sort == "yhtio")			$sort = "order by kuka.yhtio,kuka.nimi";
	else 								$sort = "order by kuka.yhtio,kuka.nimi"; 

		
	$lisa = "";
	
	if ($nimi_haku != "") {
		$lisa .= " and kuka.nimi like '%$nimi_haku%' ";
	}
	if ($puhno_haku != "") {
		$lisa .= " and kuka.puhno like '%$puhno_haku%' ";
	}
	if ($eposti_haku != "") {
		$lisa .= " and kuka.eposti like '%$eposti_haku%' ";
	}
	if ($osasto_haku != "") {
		$lisa .= " and kuka.osasto like '%$osasto_haku%' ";
	}
	if ($yhtio_haku != "") {
		$lisa .= " and yhtio.nimi like '%$yhtio_haku%' ";
	}	
	
	if ($yhtiorow["konserni"] != "") {
		$yhtiolisa = "yhtio.konserni = '$yhtiorow[konserni]' and yhtio.konserni != ''";
	}
	else {
		$yhtiolisa = "yhtio.yhtio = '$kukarow[yhtio]'";
	}
	
	$query = " 	SELECT kuka.nimi, group_concat(DISTINCT kuka.puhno SEPARATOR ' ') puhno, group_concat(DISTINCT kuka.eposti SEPARATOR ' ') eposti, group_concat(DISTINCT kuka.osasto SEPARATOR ' ') osasto, group_concat(DISTINCT yhtio.nimi SEPARATOR ' ') yhtio
				FROM kuka
				JOIN yhtio ON kuka.yhtio = yhtio.yhtio and $yhtiolisa
				WHERE extranet = ''
				and	(puhno != '' or eposti != '')
				$lisa
				group by kuka.kuka
				$sort";
	$result = mysql_query($query) or pupe_error($query);

	echo "<table>";
	echo "<form name='haku' action='yhteystiedot.php' method='post'>";
	echo "<tr>
			<th><a href='?sort=nimi&nimi_haku=$nimi_haku&puhno_haku=$puhno_haku&eposti_haku=$eposti_haku&osasto_haku=$osasto_haku&yhtio_haku=$yhtio_haku'>nimi</a></th>
			<th><a href='?sort=puhno&nimi_haku=$nimi_haku&puhno_haku=$puhno_haku&eposti_haku=$eposti_haku&osasto_haku=$osasto_haku&yhtio_haku=$yhtio_haku'>puhno</a></th>
			<th><a href='?sort=eposti&nimi_haku=$nimi_haku&puhno_haku=$puhno_haku&eposti_haku=$eposti_haku&osasto_haku=$osasto_haku&yhtio_haku=$yhtio_haku'>eposti</a></th>
			<th><a href='?sort=osasto&nimi_haku=$nimi_haku&puhno_haku=$puhno_haku&eposti_haku=$eposti_haku&osasto_haku=$osasto_haku&yhtio_haku=$yhtio_haku'>osasto</a></th>
			<th><a href='?sort=yhtio&nimi_haku=$nimi_haku&puhno_haku=$puhno_haku&eposti_haku=$eposti_haku&osasto_haku=$osasto_haku&yhtio_haku=$yhtio_haku'>yhtiö</a></th>
		</tr>";
		
	echo "<input type='hidden' name='sort' value = '$sort'>";
	echo "<tr>";
	echo "<td><input type='text' size='30' name='nimi_haku' 		value='$nimi_haku'></td>";
	echo "<td><input type='text' size='30' name='puhno_haku' 		value='$puhno_haku'></td>";
	echo "<td><input type='text' size='30' name='eposti_haku' 		value='$eposti_haku'></td>";
	echo "<td><input type='text' size='20' name='osasto_haku' 		value='$osasto_haku'></td>";
	echo "<td><input type='text' size='20' name='yhtio_haku' 		value='$yhtio_haku'></td>";
	echo "<td><input type='submit' value='Hae'></td>";
	echo "</tr>";
	echo "</form>";
	
	while ($rivi = mysql_fetch_array($result)) {
		echo "<tr>";
		echo "<td>$rivi[nimi]</td>";
		echo "<td>$rivi[puhno]</td>";
		echo "<td>$rivi[eposti]</td>";
		echo "<td>$rivi[osasto]</td>";
		echo "<td>$rivi[yhtio]</td>";
		echo "</tr>";
	}  

	echo "</table>";

	

require ("inc/footer.inc");

?>