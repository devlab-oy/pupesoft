<?php

	require ("inc/parametrit.inc");

	if ($tee == "NAYTA") {
		echo "<font class='head'>Siivoa tilaukset-listaa:</font><hr>";
		
		require("raportit/naytatilaus.inc");
		echo "<br><br>";
		echo "<a href='javascript:history.back()'>Takaisin</a><br><br>";
		exit;
	}
	
	
	
	if ($tee == 'CLEAN') {
		if (count($valittutil) != 0) {
			foreach ($valittutil as $rastit) {	
				$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa tilaus_siivo.php (1)")."<br>";
							
				$query = "	UPDATE lasku 
							SET tila = 'D',
							alatila  = 'N',
							comments = '$komm'
							WHERE yhtio = '$kukarow[yhtio]'
							and lasku.tila    = 'N'
							and lasku.alatila = ''
							and lasku.tunnus  = '$rastit'";
				$result = mysql_query($query) or pupe_error($query);
				
				if (mysql_affected_rows() == 1) {
					$query = "	UPDATE tilausrivi 
								SET tyyppi  = 'D'
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi  = 'L'
								and otunnus = '$rastit'";
					$result = mysql_query($query) or pupe_error($query);
				
				}
			}
		}
		
		if (count($valitturiv) != 0) {
			foreach ($valitturiv as $rastit) {
				$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa tilaus_siivo.php (2)")."<br>";

				$query = "	UPDATE lasku 
							SET tila = 'D',
							alatila  = 'N',
							comments = '$komm'
							WHERE yhtio = '$kukarow[yhtio]'
							and lasku.tila in ('L','N')
							and alatila     != 'X'
							and lasku.tunnus = '$rastit'";
				$result = mysql_query($query) or pupe_error($query);
				
				if (mysql_affected_rows() == 1) {
					$query = "	UPDATE tilausrivi 
								SET tyyppi  = 'D'
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi  = 'L'
								and otunnus = '$rastit'";
					$result = mysql_query($query) or pupe_error($query);
					
				}
			}
		}				
	}
	
	
	
	
	echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
			<!--

			function toggleAll(toggleBox) {

				var currForm = toggleBox.form;
				var isChecked = toggleBox.checked;

				for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
					if (currForm.elements[elementIdx].type == 'checkbox') {
						currForm.elements[elementIdx].checked = isChecked;
					}
				}
			}

			//-->
			</script>";
	
	
	echo "<font class='head'>Siivoa tilaukset-listaa:</font><hr>";
	
		
	//keskenolevat tilaukset
	$query = "	SELECT lasku.*, tilausrivi.otunnus otunnus, concat(if(kuka.kassamyyja!='', 'Kassa',''), ' ', if(extranet!='', 'Extranet','')) kassamyyja
				FROM lasku
				LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and lasku.laatija=kuka.kuka
				LEFT JOIN tilausrivi ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
				WHERE lasku.yhtio = '$kukarow[yhtio]' 
				and lasku.tila = 'N'
				and lasku.alatila = ''
				and lasku.luontiaika < date_sub(now(),interval 1 day)
				GROUP BY lasku.tunnus
				HAVING otunnus is not null
				ORDER BY lasku.luontiaika";
	$res = mysql_query($query) or pupe_error($query);
	
	
	echo "<table>";
	echo "<form method='POST' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='CLEAN'>";
	echo "<tr><td colspan='8' class='back'>Keskenolevat tilaukset joilla on rivejä (".mysql_num_rows($res)."kpl):</td></tr>";
	
	echo "<tr>
			<th>Tunnus:</th>
			<th>Tila:</th>
			<th>Alatila:</th>
			<th>Nimi:</th>
			<th>Vienti:</th>
			<th>Kassa/Extranet:</th>
			<th>Luontiaika:</th>
			<th>Mitätöi:</th>
			<tr>";					
	
	while ($laskurow = mysql_fetch_array($res)) {
		
		$ero="td";
		if ($tunnus==$laskurow["tunnus"]) $ero="th";
		
		echo "<tr><$ero><a href='$PHP_SELF?tee=NAYTA&tunnus=$laskurow[tunnus]'>$laskurow[tunnus]</a></$ero>";
		echo "<$ero>$laskurow[tila]</$ero>";
		echo "<$ero>$laskurow[alatila]</$ero>";
		echo "<$ero>$laskurow[nimi]</$ero>";
		echo "<$ero>$laskurow[vienti]</$ero>";
		echo "<$ero>$laskurow[kassamyyja]</$ero>";
		echo "<$ero>$laskurow[luontiaika]</$ero>";
		echo "<$ero><input type='checkbox' value='$laskurow[tunnus]' name='valittutil[]'></$ero></tr>";		

	}
	
	
	echo "<tr><td colspan='8' class='back'><br><br></td></tr>";
	
	
	//rivittömät otsikot
	$query = "	SELECT lasku.*, tilausrivi.tunnus tilausrivi_tunnus, concat(if(kuka.kassamyyja!='', 'Kassa',''), ' ', if(extranet!='', 'Extranet','')) kassamyyja
				FROM lasku use index (yhtio_tila_luontiaika)
				LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and lasku.laatija = kuka.kuka
				LEFT JOIN tilausrivi ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
				WHERE lasku.yhtio = '$kukarow[yhtio]' 
				and lasku.tila in ('L','N')
				and alatila != 'X'
				and lasku.luontiaika < date_sub(now(),interval 1 day)
				and lasku.luontiaika > date_sub(now(),interval 180 day)
				HAVING tilausrivi_tunnus is null
				ORDER BY lasku.luontiaika";
	$res = mysql_query($query) or pupe_error($query);		
	
	echo "<tr><td colspan='8' class='back'>Rivittömät otsikot (".mysql_num_rows($res)."kpl):</tr>";
	echo "<tr>
			<th>Tunnus:</th>
			<th>Tila:</th>
			<th>Alatila:</th>
			<th>Nimi:</th>
			<th>Vienti:</th>
			<th>Kassamyyjä:</th>
			<th>Luontiaika:</th>
			<th>Mitätöi:</th>
			<tr>";					
	
	while ($laskurow = mysql_fetch_array($res)) {

		$ero="td";
		if ($tunnus==$laskurow["tunnus"]) $ero="th";
		
		echo "<tr><$ero><a href='$PHP_SELF?tee=NAYTA&tunnus=$laskurow[tunnus]'>$laskurow[tunnus]</a></$ero>";
		echo "<$ero>$laskurow[tila]</$ero>";
		echo "<$ero>$laskurow[alatila]</$ero>";
		echo "<$ero>$laskurow[nimi]</$ero>";
		echo "<$ero>$laskurow[vienti]</$ero>";
		echo "<$ero>$laskurow[kassamyyja]</$ero>";
		echo "<$ero>$laskurow[luontiaika]</$ero>";
		echo "<$ero><input type='checkbox' value='$laskurow[tunnus]' name='valitturiv[]'></$ero></tr>";		

	}
	
	echo "<tr><td colspan='8' class='back'><br><br></td></tr>";
	
	echo "<tr><td colspan='7'>Ruksaa kaikki:</td>";
	echo "<td><input type='checkbox' name='chbox' onclick='toggleAll(this)'></td></tr>";
			
	echo "</table><br><br>";
	echo "<input type='submit' value='".t("Mitätöi valitut tilaukset")."'></form>";
		
		
	require("inc/footer.inc");
?>
