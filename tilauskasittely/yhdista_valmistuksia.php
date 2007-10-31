<?php
	require ("../inc/parametrit.inc");

	js_popup();
	
	echo "<font class='head'>".t("Yhdistä valmistuksia").":</font><hr>";
	
	if ($tee == 'NAYTATILAUS') {
		require ("../raportit/naytatilaus.inc");
		echo "<hr>";
		$tee = "VALITSE";
	}
	
	if($tee=='YHDISTA') {		
		//Käydään läpi rivit
		if (count($valmistettavat) > 0) {
						
			$query = "	SELECT *
						FROM  lasku						
						WHERE yhtio = '$kukarow[yhtio]' 
						and	tunnus = (SELECT otunnus from tilausrivi WHERE tunnus=$valmistettavat[0])";	
			$result = mysql_query($query) or pupe_error($query);
			$laskurow    = mysql_fetch_array($result);
						
			$query = "	INSERT into
						lasku SET
						clearing			= '',					
						nimi 				= 'Valmistusajo',	
						toimaika 			= now(),
						kerayspvm 			= now(),						
						comments 			= '',						
						viesti 				= '',						
						yhtio 				= '$kukarow[yhtio]',
						varasto 			= '$laskurow[varasto]',											
						hyvaksynnanmuutos 	= '',
						tilaustyyppi 		= 'W', 
						tila				= 'V',
						alatila				= 'J',
						ytunnus				= 'Valmistusajo',
						laatija 			= '$kukarow[kuka]',
						luontiaika			= NOW()";
			$result = mysql_query($query) or pupe_error($query);
			$otunnus = mysql_insert_id();
		
			echo "Luotiin uusi otsikko: $otunnus<br>";
		
			foreach($valmistettavat as $rivitunnus) {							
				//Otetaan alkuperäisen otsikon numero talteen
				$query = "	SELECT otunnus
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]' 
							and tunnus = '$rivitunnus'";
				$result = mysql_query($query) or pupe_error($query);
				$otsikrow = mysql_fetch_array($result);
				
				//Siirretään rivi uudelle otsikolle
				$query = "	UPDATE tilausrivi 
							SET uusiotunnus = otunnus, 
							otunnus = '$otunnus'
							WHERE yhtio = '$kukarow[yhtio]' 
							and perheid = '$rivitunnus'
							and tyyppi in ('V','W')
							and uusiotunnus = 0";
				$result = mysql_query($query) or pupe_error($query);
				
				//Tsekataan onko alkuperäisotsikolla vielä rivejä
				$query = "	SELECT count(*) jaljella
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]' 
							and otunnus = '$otsikrow[otunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				$rivirow = mysql_fetch_array($result);
				
				if ($rivirow["jaljella"] == 0) {
					//päivitetään alkuperäisen otsikon alatila
					$query = "	UPDATE lasku 
								SET alatila = 'Y'
								WHERE yhtio = '$kukarow[yhtio]' 
								and tunnus = '$otsikrow[otunnus]'";
					$result = mysql_query($query) or pupe_error($query);			
				}			
			}
			
			echo "Siirrettin rivit uudelle otsikolle!<br>";
			echo "Valmis!<br><br>";
			$tee = "";
		}
	}
	
	if ($tee == "VALITSE") {
		
		echo "<table>";
				
		$query = "	SELECT 
					GROUP_CONCAT(DISTINCT lasku.tunnus SEPARATOR ', ') 'Tilaus',
					GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR ', ') 'Asiakas/Nimi',
					GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR ', ') 'Ytunnus'					
					FROM tilausrivi, lasku
					LEFT JOIN kuka ON lasku.myyja = kuka.tunnus and lasku.yhtio=kuka.yhtio
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' 
					and	tilausrivi.tunnus in ($valmistettavat)
					and lasku.tunnus=tilausrivi.otunnus
					and lasku.yhtio=tilausrivi.yhtio
					and tilausrivi.uusiotunnus = 0";	
		$result = mysql_query($query) or pupe_error($query);
		$row    = mysql_fetch_array($result);
				
		for ($i=0; $i < mysql_num_fields($result); $i++) {
			echo "<tr><th align='left'>" . t(mysql_field_name($result,$i)) ."</th><td>$row[$i]</td></tr>";
		}
		echo "</table><br>";

		$query = "	SELECT tilausrivi.otunnus, tilausrivi.nimitys, tilausrivi.tuoteno, tilkpl tilattu, if(tyyppi!='L', varattu, 0) valmistetaan, if(tyyppi='L' or tyyppi='D', varattu, 0) valmistettu, 
					toimaika, kerayspvm, tilausrivi.tunnus tunnus, tilausrivi.perheid, tilausrivi.tyyppi, tilausrivi.toimitettuaika
					FROM tilausrivi, tuote
					WHERE 
					tilausrivi.otunnus in ($row[Tilaus])
					and tilausrivi.perheid in ($valmistettavat)
					and tilausrivi.yhtio='$kukarow[yhtio]'
					and tuote.yhtio=tilausrivi.yhtio
					and tuote.tuoteno=tilausrivi.tuoteno
					and tyyppi = 'W'
					and tilausrivi.uusiotunnus = 0
					ORDER BY perheid";
		$presult = mysql_query($query) or pupe_error($query);
		$riveja = mysql_num_rows($presult);
		
		echo "<table border='0' cellspacing='1' cellpadding='2'><tr>";
		echo "<th>".t("Valmistus")."</a></th>";
		echo "<th>".t("Nimitys")."</a></th>";
		echo "<th>".t("Valmiste")."</a></th>";
		echo "<th>".t("Valmistetaan")."</a></th>";
		echo "<th>".t("Keräysaika")."</a></th>";
		echo "<th>".t("Valmistusaika")."</a></th>";
		echo "<th>".t("Yhdistä")."</a></th>";
		echo "</tr>";

		$rivkpl = mysql_num_rows($presult);
		
		$vanhaid = "KALA";

		echo "	<form method='post' action='$PHP_SELF' autocomplete='off'>";
		echo "	<input type='hidden' name='tee' value='YHDISTA'>
				<input type='hidden' name='toim'  value='$toim'>";

		while ($prow = mysql_fetch_array ($presult)) {
			$linkki = "";
			$query = "SELECT fakta2 FROM tuoteperhe WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'R' and isatuoteno = '$prow[tuoteno]' and fakta2 != '' ORDER BY isatuoteno, tuoteno LIMIT 1";
			$faktares = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($faktares) > 0) {
				$faktarow = mysql_fetch_array($faktares);
				$id = uniqid();
				echo "<div id='$id' class='popup' style='width: 400px'>
						<font class='head'>Tuotteen yhdistettävyys</font><br>
						$faktarow[fakta2]<br></div>";						
				$linkki = "<div style='text-align: right; float:right;'>&nbsp;&nbsp;<a href='#' onmouseover=\"popUp(event, '$id');\" onmouseout=\"popUp(event, '$id');\"><img src='../pics/lullacons/info.png' height='13'></a></div>";
			}
			
			echo "<tr>";
			echo "<td>$prow[otunnus]</td>";			
			echo "<td align='right'>".asana('nimitys_',$prow['tuoteno'],$prow['nimitys'])."</td>";
			echo "<td><a href='../tuote.php?tee=Z&tuoteno=$prow[$i]'>$prow[tuoteno]</a>$linkki</td>";
			echo "<td align='right'>$prow[tilattu]</td>";
			echo "<td align='right'>$prow[kerayspvm]</td>";
			echo "<td align='right'>$prow[toimaika]</td>";			
			echo "<td align='center'><input type='checkbox' name='valmistettavat[]' value='$prow[tunnus]' checked></td>";			
			echo "</tr>";						
		}
		
		echo "</table><br>";
		echo "Yhdistä valitut: <input type='submit' value='".t("Yhdistä")."'><br><br>";
	}
	
	// meillä ei ole valittua tilausta
	if ($tee == "") {
		$formi	= "find";
		$kentta	= "etsi";

		// tehdään etsi valinta
		echo "<br><form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim'  value='$toim'>";
		
		echo "<table>";
		echo "<tr>
			<th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr>\n
			<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>
			</tr>\n";
		
		if ($pervalmiste == "OK") {
			$chk = "CHECKED";
		}
		else {
			$chk = "";
		}
		
		echo "<tr>
			<th>".t("Ryhmittele per valmiste").":</th>";
		echo "<td colspan='3'><input type='checkbox' name='pervalmiste' value='OK' $chk onchange='submit();'></td>";
		
		
		echo "<tr>
			<th>".t("Etsi valmistetta/raaka-ainetta").":</th>";
		echo "<td colspan='3'><input type='text' name='etsi' value='$etsi'></td>";
		echo "<td><input type='Submit' value='".t("Etsi")."'></td></form>";
		echo "</table><br><br>";

		$haku='';

		//tässä haku on tehtävä hieman erilailla
		$query = "	SELECT group_concat(distinct perheid separator ',') haku
					from tilausrivi, lasku
					where tilausrivi.yhtio = '$kukarow[yhtio]' 
					and lasku.yhtio = '$kukarow[yhtio]' 
					and lasku.tunnus = tilausrivi.otunnus 
					and lasku.tila 	= 'V' 
					and lasku.alatila = 'J'
					and tilausrivi.toimitettu = ''
					and tilausrivi.varattu != 0
					and tilausrivi.uusiotunnus = 0
					and tilausrivi.tuoteno='$etsi'
					and tilausrivi.tyyppi in ('W','V')";
		$tilre = mysql_query($query) or pupe_error($query);
		$tilro = mysql_fetch_array($tilre);
		
		if ($tilro["haku"] != '') {
			$haku = " and tilausrivi.tunnus in ($tilro[haku])";
		}
					
		
		if (strlen($ojarj) > 0) {
			$jarjestys = " ORDER BY ".$ojarj;
		}
		else {
			$jarjestys = " ORDER BY tuoteno, lasku.tunnus, varattu";
		}
		
		if (checkdate((int) $kka, (int) $ppa, (int) $vva)) {
			$alku = " and tilausrivi.toimaika>='$vva-$kka-$ppa'";
		}
		
		if (checkdate((int) $kkl, (int) $ppl, (int) $vvl)) {
			$loppu = " and tilausrivi.toimaika<='$vvl-$kkl-$ppl'";
		}
		
		if ($haku == "" and $pervalmiste == "OK") {
			$query 		= "	SELECT 
							GROUP_CONCAT(DISTINCT lasku.tunnus ORDER BY lasku.tunnus SEPARATOR '<br>') tunnus, 
							GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR '<br>') nimi,
							GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR '<br>') ytunnus, 
							tilausrivi.tuoteno, 
							sum(tilausrivi.varattu) varattu,
							GROUP_CONCAT(DISTINCT tilausrivi.kerayspvm ORDER BY tilausrivi.kerayspvm SEPARATOR '<br>') kerayspvm,
							GROUP_CONCAT(DISTINCT tilausrivi.toimaika ORDER BY tilausrivi.toimaika SEPARATOR '<br>') toimaika,																					
							GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat,
							lasku.varasto";
			$grouppi	= " GROUP BY lasku.varasto, tilausrivi.tuoteno";
		}
		elseif ($haku != "") {
			$query 		= "	SELECT 
							GROUP_CONCAT(DISTINCT lasku.tunnus ORDER BY lasku.tunnus SEPARATOR '<br>') tunnus, 
							GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR '<br>') nimi,
							GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR '<br>') ytunnus, 
							GROUP_CONCAT(DISTINCT tilausrivi.tuoteno ORDER BY tilausrivi.tuoteno SEPARATOR '<br>') tuoteno, 
							sum(tilausrivi.varattu) varattu,
							GROUP_CONCAT(DISTINCT tilausrivi.kerayspvm ORDER BY tilausrivi.kerayspvm SEPARATOR '<br>') kerayspvm,
							GROUP_CONCAT(DISTINCT tilausrivi.toimaika ORDER BY tilausrivi.toimaika SEPARATOR '<br>') toimaika,																					
							GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat,
							lasku.varasto";
			$grouppi	= " GROUP BY lasku.varasto";
		}
		else {
			$query 		= "	SELECT  							
							lasku.tunnus, 
							lasku.nimi,			
							lasku.ytunnus, 											
							tilausrivi.tuoteno,
							tilausrivi.varattu,
							tilausrivi.kerayspvm,
							tilausrivi.toimaika, 							
							lasku.varasto,
							tilausrivi.tunnus valmistettavat";
			$grouppi	= " ";
		}
		
		$query .= "	from tilausrivi, lasku
					where tilausrivi.yhtio = '$kukarow[yhtio]' 
					and lasku.yhtio = '$kukarow[yhtio]' 
					and lasku.tunnus = tilausrivi.otunnus 
					and lasku.tila 	= 'V' 
					and lasku.alatila = 'J'
					and tilausrivi.toimitettu = ''
					and tilausrivi.varattu != 0
					and tilausrivi.uusiotunnus = 0
					and tilausrivi.tyyppi='W'	
					$alku
					$loppu			
					$haku
					$grouppi
					$jarjestys";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre) > 0) {
			echo "<table>";			
			echo "<tr>";
			echo "<th align='left'><a href = '$PHP_SELF?ojarj=1&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Valmistus")."</a></th>";
			echo "<th align='left'><a href = '$PHP_SELF?ojarj=2&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Asiakas/Varasto")."</a></th>";
			echo "<th align='left'><a href = '$PHP_SELF?ojarj=3&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Ytunnus")."</a></th>";
			echo "<th align='left'><a href = '$PHP_SELF?ojarj=4&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Valmiste")."</a></th>";
			echo "<th align='left'><a href = '$PHP_SELF?ojarj=5&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Kpl")."</a></th>";
			echo "<th align='left'><a href = '$PHP_SELF?ojarj=6&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Keräysaika")."</a></th>";
			echo "<th align='left'><a href = '$PHP_SELF?ojarj=7&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsi=$etsi'>".t("Valmistusaika")."</a></th>";
			echo "</tr>";			
						
			while ($tilrow = mysql_fetch_array($tilre)) {
				
				echo "	<tr>
						<td valign='top'>$tilrow[tunnus]</td>
						<td valign='top'>$tilrow[nimi] $tilrow[nimitark]</td>
						<td valign='top'>$tilrow[ytunnus]</td>
						<td valign='top'>$tilrow[tuoteno]</td>
						<td valign='top'>$tilrow[varattu]</td>
						<td valign='top'>$tilrow[kerayspvm]</td>
						<td valign='top'>$tilrow[toimaika]</td>";
								
				echo "	<form method='post' action='$PHP_SELF'><td class='back'>
						<input type='hidden' name='tee' value='VALITSE'>
						<input type='hidden' name='valmistettavat' value='$tilrow[valmistettavat]'>
						<input type='submit' value='".t("Valitse")."'></td></tr></form>";
			}
			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Yhtään valmistettavaa tilausta/tuotetta ei löytynyt")."...</font>";
		}
	}
	
	require "../inc/footer.inc";
?>
