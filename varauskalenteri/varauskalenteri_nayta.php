<?php
	
	
	$query = "	select *
				from kalenteri
				where tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);
		
	echo "<br><table>";
	echo "<tr><th colspan='2'>Tilaisuuden tarkemmat tiedot:</th></tr>";
	
	echo "<tr><th>Alku:</th><td>$row[pvmalku]</td></tr>";
	echo "<tr><th>Loppu:</th><td>$row[pvmloppu]</td></tr>";
	echo "<tr><th>Kuka:</th><td>$row[kuka]</td></tr>";
	
	
	echo "<tr><th>Yhtio:</th><td>$row[kentta01]</td></tr>";
	echo "<tr><th>Osasto:</th><td>$row[kentta02]</td></tr>";
	echo "<tr><th>Tilaisuus:</th><td>$row[kentta03] $row[kentta04]</td></tr>";
	echo "<tr><th>Lis‰tiedot:</th><td><pre>$row[kentta05]</pre></td></tr>";
	echo "<tr><th>Is‰nn‰t:</th><td><pre>$row[kentta06]</pre></td></tr>";
	echo "<tr><th>Vieraat:</th><td><pre>$row[kentta07]</pre></td></tr>";
	echo "<tr><th>Vieraslukum‰‰r‰:</th><td><pre>$row[kentta08]</pre></td></tr>";
	echo "<tr><th>Juomatoivomus:</th><td><pre>$row[kentta10]</pre></td></tr>";
	echo "</table>";
	
	echo "<br><br><a href='javascript:history.back()'>".t("Takaisin kalenteriin")."</a>";
?>