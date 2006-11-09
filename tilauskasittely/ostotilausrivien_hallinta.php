<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Toimittajan avoimet tilausrivit").":</font><hr>";
	
	if ($ytunnus != '') {
		require ("../inc/kevyt_toimittajahaku.inc");
	}
	
	if ($ytunnus == "") {
		// N‰ytet‰‰n muuten vaan sopivia tilauksia
		echo "<br><table>";
		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<tr><td>".t("Toimittaja").": <input type='text' size='10' name='ytunnus'></td>";
		echo "<td><input type='submit' value='".t("Etsi")."'></td></tr>";
		echo "</form>";
		echo "</table><br>";
	}
	
	
	if($ytunnus != '') {
		$query = "	SELECT max(lasku.tunnus) maxtunnus, GROUP_CONCAT(distinct lasku.tunnus SEPARATOR ', ') tunnukset
					FROM lasku, tilausrivi
					WHERE 
					lasku.liitostunnus = '$toimittajaid'
					and lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'O'
					and lasku.alatila = 'A'
					and tilausrivi.yhtio=lasku.yhtio
					and tilausrivi.otunnus=lasku.tunnus
					and tilausrivi.uusiotunnus=0
					and tilausrivi.tyyppi='O'
					HAVING tunnukset is not null";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) > 0) {		
			$tunnusrow = mysql_fetch_array($result);
			
			//Onko tietty tilaus valittu?
			if ($otunnus != "") {
				$tilaus_otunnukset = $otunnus;
			}
			else {
				$tilaus_otunnukset = $tunnusrow["tunnukset"];
			}
			
			$query = "	SELECT *
						FROM lasku
						WHERE tunnus = '$tunnusrow[maxtunnus]'";
			$aresult = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($aresult);		
		
		
			if ($tee == "KAIKKIPVM") {
				//P‰ivitet‰‰n rivien toimitusp‰iv‰t
				$query = "	UPDATE tilausrivi
							SET toimaika='$toimvv-$toimkk-$toimpp'
							WHERE yhtio='$kukarow[yhtio]' 
							and otunnus in ($tilaus_otunnukset)
							and uusiotunnus=0";
				$result = mysql_query($query) or pupe_error($query);									
			}
			
			if ($tee == "PAIVITARIVI") {								
				foreach($toimaikarivi as $tunnus => $toimaika) {										
					$query = "UPDATE tilausrivi SET toimaika='$toimaika' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and tyyppi='O'";
					$result = mysql_query($query) or pupe_error($query);										
				}
								
				if(isset($poista)) {
					foreach($poista as $tunnus => $kala) {
						$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and tyyppi='O'";
						$result = mysql_query($query) or pupe_error($query);
					}
				}
			}
		}
	}
			
	if (isset($laskurow)) {
		echo "<table width='720' cellpadding='2' cellspacing='1' border='0'>";
	
		echo "<tr><th>".t("Ytunnus")."</th><th colspan='2'>".t("Toimittaja")."</th></tr>";
		echo "<tr><td>$laskurow[ytunnus]</td>
			<td colspan='2'>$laskurow[nimi] $laskurow[nimitark]<br> $laskurow[osoite]<br> $laskurow[postino] $laskurow[postitp]</td></tr>";
	
		echo "<tr><th>".t("Tila")."</th><th>".t("Toimaika")."</th><th>".t("Tilausnumerot")."</th><td class='back'></td></tr>";
		echo "<tr><td>$laskurow[tila]</td><td>$laskurow[toimaika]</td><td>$tunnusrow[tunnukset]</td></tr>";
		echo "</table><br>";	
		
		echo "	<table>
				<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='ytunnus' value = '$ytunnus'>
				<input type='hidden' name='toimittajaid' value = '$toimittajaid'>";
		echo "<tr><th>".t("N‰yt‰ tilaukset")."</th><td>";
		echo "<select name='otunnus' onchange='submit();'>";
		echo "<option value=''>".t("N‰yt‰ kaikki")."</option>";
		
		$tunnukset = split(',',$tunnusrow["tunnukset"]);
		
		foreach($tunnukset as $tunnus) {
			$sel = '';
			if ($otunnus == $tunnus) {
				$sel = "selected";
			}
			echo "<option value='$tunnus' $sel>$tunnus</option>";
		}						
		echo "</select>";				
		echo "</form></td></tr></table><br>";
								
		echo "	<table>
				<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='ytunnus' value = '$ytunnus'>
				<input type='hidden' name='toimittajaid' value = '$toimittajaid'>
				<input type='hidden' name='otunnus' value = '$otunnus'>
				<input type='hidden' name='tee' value = 'KAIKKIPVM'>";
						
		$toimpp = date(j);
		$toimkk = date(n);
		$toimvv = date(Y);			

		echo "<tr><th>".t("P‰ivit‰ rivien toimitusajat").": </th><td valign='middle'>
				<input type='text' name='toimpp' value='$toimpp' size='3'>
				<input type='text' name='toimkk' value='$toimkk' size='3'>
				<input type='text' name='toimvv' value='$toimvv' size='6'></td>
				<td><input type='Submit' value='".t("P‰ivit‰")."'></form></td></tr></table><br>";	
	
		//Haetaan kaikki tilausrivit
		echo "<b>".t("Tilausrivit").":</b><hr>";

		//Listataan tilauksessa olevat tuotteet
		$jarjestys = "tilausrivi.otunnus";

		if (strlen($ojarj) > 0) {
			$jarjestys = $ojarj;
		}

		$query = "	SELECT tilausrivi.otunnus, tilausrivi.tuoteno, tilausrivi.nimitys,
					concat_ws('/',tilkpl,round(tilkpl*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tilattu sis/ulk',
					hinta, ale, round((varattu+jt)*hinta*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*(1-(ale/100)),2) rivihinta, 
					toimaika, tilausrivi.tunnus,
					toim_tuoteno
					FROM tilausrivi
					LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno									
					LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='$toimittajaid'
					WHERE otunnus in ($tilaus_otunnukset)
					and tilausrivi.yhtio='$kukarow[yhtio]'					
					and tilausrivi.uusiotunnus=0
					and tilausrivi.tyyppi='O'
					ORDER BY $jarjestys";
		$presult = mysql_query($query) or pupe_error($query);

		$rivienmaara = mysql_num_rows($presult);

		echo "<table border='0' cellspacing='1' cellpadding='2'><tr>";

		$miinus = 1;

		for ($i = 0; $i < mysql_num_fields($presult)-$miinus; $i++) {
			$apu = $i + 1;
			echo "<th align='left'><a href = '$PHP_SELF?ytunnus=$ytunnus&otunnus=$otunnus&toimittajaid=$toimittajaid&ojarj=$apu'>" . t(mysql_field_name($presult,$i)) ."</a></th>";
		}
		
		echo "<th align='left'>".t("poista")."</th>";
		
		echo "</tr>";

		$yhteensa = 0;
			
		echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
				<!--
		
				function toggleAll(toggleBox) {
		
					var currForm = toggleBox.form;
					var isChecked = toggleBox.checked;
					var nimi = toggleBox.name;
					
					for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {											
						if (currForm.elements[elementIdx].type == 'checkbox') {
							currForm.elements[elementIdx].checked = isChecked;
						}
					}
				}
		
				//-->
				</script>";
			
		echo "	<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='ytunnus' value = '$ytunnus'>
				<input type='hidden' name='toimittajaid' value = '$toimittajaid'>
				<input type='hidden' name='otunnus' value = '$otunnus'>
				<input type='hidden' name='tee' value = 'PAIVITARIVI'>";
				
		while ($prow = mysql_fetch_array ($presult)) {

			$yhteensa += $prow["rivihinta"];

			echo "<tr>";

			for ($i=0; $i < mysql_num_fields($presult)-$miinus; $i++) {
				if (mysql_field_name($presult,$i) == 'tuoteno') {															
					echo "<td><a href='../tuote.php?tee=Z&tuoteno=$prow[$i]'>$prow[$i]</a></td>";
				}
				elseif(mysql_field_name($presult,$i) == 'toimaika') {										
					echo "<td align='right'><input type='text' name='toimaikarivi[$prow[tunnus]]' value='$prow[$i]' size='10'></td>";
				}
				else {
					echo "<td align='right'>$prow[$i]</td>";
				}
			}	
			echo "<td><input type='checkbox' name='poista[$prow[tunnus]]' value='".t("Poista")."'></td>";			
			echo "</tr>";
		}
		echo "<tr>
				<td class='back' colspan='3' align='right'></td>
				<td colspan='3' class='spec'>Tilauksen arvo:</td>
				<td align='right' class='spec'>".sprintf("%.2f",$yhteensa)."</td>
				<td class='back'></td>
				<td align='right'>".t("Ruksaa kaikki").":</td>
				<td><input type='checkbox' onclick='toggleAll(this);'></td>
			</tr>";
		echo "<tr>
				<td class='back' colspan='8'></td>
				<td class='back' colspan='2' align='right'><input type='submit' value='".t("P‰ivit‰ tiedot")."'></td>
			</tr>";	
			
		echo "</form></table>";
	}
		
	require ("../inc/footer.inc");
?>