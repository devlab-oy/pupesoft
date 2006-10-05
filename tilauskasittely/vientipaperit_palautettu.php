<?php
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Vientitipapereiden palautukset")."</font><hr>";

	if ($tee == 'L') {
			$query = " UPDATE lasku
								SET vientipaperit_palautettu='K'
								WHERE tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";					
		$result = mysql_query($query) or pupe_error($query);
		$tee = '';
	}
	
	// meill‰ ei ole valittua tilausta
	if ($tee == '') {
		$formi="find";
		$kentta="etsi";

		// tehd‰‰n etsi valinta
		echo "<table>";
		echo "<form action='$PHP_SELF' name='find' method='post'>
					<tr><td>".t("Etsi tilausta (asiakkaan nimell‰ tai laskunumerolla)").":</td><td><input type='text' name='etsi' value='$etsi'></td></tr>";
		
		if ($vaiht == 'palauttamattomat')
			$chk = "CHECKED";
								
		echo "<tr><td>".t("N‰yt‰ vain palauttamattomat")."</td><td><input type='checkbox' name='vaiht' value='palauttamattomat' $chk></td>
					<td class='back'><input type='Submit' value='".t("Etsi")."'></td></form></tr></table>";
		$haku='';
		if (is_string($etsi))  $haku=" and nimi LIKE '%$etsi%' ";
		if (is_numeric($etsi)) $haku=" and laskunro='$etsi' ";

		if ($vaiht == 'palauttamattomat') {
			 $haku2 = " and vientipaperit_palautettu='' ";
		}
		
		//listataan vientilaskut
		$query = "select laskunro, nimi asiakas, ytunnus, tapvm, tullausnumero, laatija, vienti, vientipaperit_palautettu, tunnus 
							from lasku where yhtio='$kukarow[yhtio]' and tila='U' and vienti in ('K','E')
							$haku  $haku2
							ORDER BY laskunro
							LIMIT 50";
		$tilre = mysql_query($query) or pupe_error($query);

		echo "<br><table>";
		
	 	if (mysql_num_rows($tilre) > 0) {
			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($tilre)-1; $i++)
				echo "<th align='left'>".t(mysql_field_name($tilre,$i))."</th>";
			echo "</tr>";
				
			while ($tilrow = mysql_fetch_array($tilre))
			{	
				echo "<tr>";
				for ($i=0; $i < mysql_num_fields($tilre)-2; $i++)
					echo "<td align='left'>".$tilrow[$i]."</td>";	
				
					if ($tilrow["vientipaperit_palautettu"] == "K") {
						echo "<td>".t("Paperit on palautettu")."!</td>";
					}
				  else {
						echo "<form action='$PHP_SELF' method='post'>
									<input type='hidden' name='etsi' value='$etsi'>
									<input type='hidden' name='tee' value='L'>
									<input type='hidden' name='tunnus' value='$tilrow[tunnus]'>
									<td><input type='Submit' value='".t("Palauta paperit")."'></form>";
						echo "</td>";
					}
				echo "</tr>";
			}
			
		}
		else {
			echo "<tr>";
			echo "<th colspan='5'>".t("Ei palauttamattomia vientipapereita")."!</th>";
			echo "</tr>";		
		}
		echo "</table>";
	}
	
	require "../inc/footer.inc";
?>
