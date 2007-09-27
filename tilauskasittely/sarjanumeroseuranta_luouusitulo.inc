<?php

	//Kysytään käyttäjältä sopiva ostohinta tuotteelle
	if ($luousitee == "") {
		$kehahin = 0;

		// Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksilöiden ostohinnoista (ostetut yksilöt jotka eivät vielä ole myyty(=laskutettu)
		$query	= "	SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
					FROM sarjanumeroseuranta
					LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
					WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]' 
					and sarjanumeroseuranta.tuoteno = '$tuoteno'
					and sarjanumeroseuranta.myyntirivitunnus != -1
					and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
					and tilausrivi_osto.laskutettuaika != '0000-00-00'";
		$result = mysql_query($query) or pupe_error($query);
		$ikrow  = mysql_fetch_array($result);
			
		$kehahin = sprintf('%.2f', $ikrow["kehahin"]);
		
		if ($kehahin == 0) {
			$query = "	SELECT kehahin
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
			$result = mysql_query($query) or pupe_error($query);
			$ikrow  = mysql_fetch_array ($result);
			
			$kehahin = sprintf('%.2f', $ikrow["kehahin"]);
		}
		
		
		echo "<font class='head'>".t("Sarjanumeroseuranta")."</font><hr>";
		
		echo "<table>";
		echo "<tr><th>Inventoidaan tulotapahtuma tuotteelle:</th><td>$tuoteno</td></tr>";
		echo "<tr><th>Tuotteen keskimääräinen hankintahinta:</th><td>$kehahin</td></tr>";
		
		echo "<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='tuoteno'		value='$tuoteno'>                          
				<input type='hidden' name='luousitee'	value='DOIT'>                              
				<input type='hidden' name='toiminto'	value='luouusitulo'>                       
				<input type='hidden' name='hyllyalue'	value='$hyllyalue'>                        
				<input type='hidden' name='hyllynro'	value='$hyllynro'>                         
				<input type='hidden' name='hyllyvali'	value='$hyllyvali'>                        
				<input type='hidden' name='hyllytaso'	value='$hyllytaso'>                        
				<input type='hidden' name='from'		value='INVENTOINTI'>                       
				<input type='hidden' name='lopetus'		value='$lopetus'>";
				
		echo "<tr><th>Inventoitavan yksilön hankintahinta:</th><td><input type='text' name='kehahin' size='10'	value='$kehahin'></td>
				<td class='back'><input type='submit' value='".t("Jatka")."'></td></tr>";
				
		echo "</table>";
			
		require ("../inc/footer.inc");
		exit;
	}
	else {
		// Inventoiduille sarjanumerotuotteille luodaan K I otsikko per päivä
		$query  = "	SELECT * 
					FROM lasku 
					WHERE yhtio = '$kukarow[yhtio]' and tila='K' and alatila='I' and tapvm=now()";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {	
			// haetaan seuraava vapaa keikkaid
			$query  = "SELECT max(laskunro)+1 from lasku where yhtio='$kukarow[yhtio]' and tila='K'";
			$result = mysql_query($query) or pupe_error($query);
			$row    = mysql_fetch_array($result);
			$lid	= $row[0];

			$query = "	INSERT into lasku set
						yhtio        = '$kukarow[yhtio]',
						laskunro     = '$lid',
						ytunnus	     = 'Invent',
						nimi         = '".t("Inventointi")."',
						liitostunnus = '0',
						tila         = 'K',
						alatila      = 'I',
						tapvm		 = now(),
						luontiaika	 = now(),
						laatija		 = 'Invent'";
			$result = mysql_query($query) or pupe_error($query);
			$lid = mysql_insert_id();
		
			$query  = "	SELECT *
						from lasku
						where tunnus	= '$lid'
						and yhtio		= '$kukarow[yhtio]'
						and tila		= 'K'
						and alatila     = 'I'
						and tapvm		= now()";
			$result = mysql_query($query) or pupe_error($query);
			$ikrow = mysql_fetch_array($result);
		}
		else {
			$ikrow = mysql_fetch_array($result);	
		}

		//Haetaan tuotteen tiedot
		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tuoteno'";
		$result  = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);
	
		$query = "	INSERT into tilausrivi set
					varattu 		= '1',
					tilkpl 			= '1',
					kpl				= '1',
					jt				= '0',
					hinta			= '$kehahin',
					rivihinta		= '$kehahin',
					hyllyalue		= '$hyllyalue',
					hyllynro 		= '$hyllynro',
					hyllyvali 		= '$hyllyvali',
					hyllytaso 		= '$hyllytaso',
					uusiotunnus		= '$ikrow[tunnus]',
					otunnus 		= '$ikrow[tunnus]',
					var				= '',
					keratty			= 'Invent',
					kerattyaika		= '',
					kerayspvm 		= '',
					laatija			= 'Invent',
					laadittu		= now(),
					toimaika 		= now(),
					laskutettu		= 'Invent',
					laskutettuaika	= now(),
					yhtio 			= '$kukarow[yhtio]',
					tuoteno 		= '$tuoteno',
					ale 			= '0',
					netto 			= '',
					yksikko 		= '$trow[yksikko]',
					try 			= '$trow[try]',
					osasto 			= '$trow[osasto]',
					alv 			= '0',
					nimitys 		= '$trow[nimitys]',
					tyyppi 			= 'O',
					kommentti 		= '$kukarow[nimi] lisäsi inventoimalla.'";
		$result = mysql_query($query) or pupe_error($query);
		$lid = mysql_insert_id();

		$ostorivitunnus = $lid;
		$toiminto 		= "";
	}
?>