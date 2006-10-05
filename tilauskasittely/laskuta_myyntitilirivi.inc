<?php
	
	if ($tilatapa == "VALITSE") {								
		$sela = "";
		$selb = "";
		
		if ($row["var"] == "A") {
			$sela = "SELECTED";
		}
		if ($row["var"] == "B") {
			$selb = "SELECTED";
		}
		
		
		$paikat = "<option value=''>".t("J‰t‰ myyntitiliin");
		$paikat .= "<option value='A' $sela>".t("Laskuta asiakasta");
		//$paikat .= "<option value='B' $selb>".t("Palauta omaan varastoon");
		
		$paikat = "<select Style='{font-size: 8pt; padding:0;}' name='myyntitilirivit' onchange='submit();'>".$paikat."</select>";
		
		echo "	<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
				<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
				<input type='hidden' name='tila' value = 'MYYNTITILIRIVI'>";							
		echo "<td $class align='left'>$paikat</td>";
		
		//formi suljetaan tilaus_myynti.php failissa
		
		$tilatapa	= "";
	}
	
	if ($tilatapa == "PAIVITA") {
		//P‰ivitet‰‰n is‰n perheid nollaksi jotta voidaan lis‰t‰ lis‰ lis‰varusteita
		$query	= "	select *
					from tilausrivi
					where yhtio = '$kukarow[yhtio]' 
					and otunnus = '$kukarow[kesken]' 
					and tunnus  = '$rivitunnus'";
		$qres = mysql_query($query) or pupe_error($query);
		$qrow = mysql_fetch_array($qres);
		
		if ($qrow["kpl"] != $kpl and (float) $kpl > 0) {
			$isakpl = $qrow["kpl"];
						
			if($qrow["perheid"] != 0) {
				$query	= "	select *
							from tilausrivi
							where yhtio = '$kukarow[yhtio]' 
							and otunnus = '$kukarow[kesken]' 
							and perheid = '$qrow[perheid]'
							order by tunnus";
				$qres = mysql_query($query) or pupe_error($query);
			}
					
			mysql_data_seek($qres,0);
			
			$uuden_perheen_perheid = 0;
			$kopsatut_rivit = array();
						
			while($qrow = mysql_fetch_array($qres)) {
				
				$uusikpl = round(($qrow["kpl"]/$isakpl)*$kpl,2);
				$erotus  = $qrow["kpl"] - $uusikpl;
								
				if ($erotus > 0) {
					$query = "	update tilausrivi set
								kpl = '$uusikpl'
								where yhtio = '$kukarow[yhtio]'
								and otunnus = '$kukarow[kesken]' 
								and tunnus = '$qrow[tunnus]'";
					$updres = mysql_query($query) or pupe_error($query);
				
					
					$fields = mysql_field_name($qres,0);
					$values = "'".$qrow[0]."'";
				
					for($i=1; $i < mysql_num_fields($qres)-1; $i++) { // Ei monisteta tunnusta
				
						$fields .= ", ".mysql_field_name($qres,$i);
				
						switch (mysql_field_name($qres,$i)) {
							case 'laadittu':
								$values .= ", now()";
								break;
							case 'tunnus':
								$values .= ", ''";
								break;
							case 'laatija':
								$values .= ", '$kukarow[kuka]'";
								break;
							case 'kpl':
							case 'tilkpl':
							case 'varattu':
								$values .= ", '$erotus'";
								break;
							default:
								$values .= ", '".$qrow[$i]."'";
						}
					}
					$kysely  = "INSERT into tilausrivi ($fields) VALUES ($values)";
					$insres  = mysql_query($kysely) or pupe_error($kysely);	
					$utunnus = mysql_insert_id($link);		
																																																															
					if ($uuden_perheen_perheid == 0) {
						$uuden_perheen_perheid = $utunnus;
					}
					
					$kopsatut_rivit[] = $utunnus;
				}
			}
			if (count($kopsatut_rivit) > 1) {
				foreach($kopsatut_rivit as $uusitunnu) {
					$query = "	update tilausrivi set
								perheid = '$uuden_perheen_perheid'
								where yhtio = '$kukarow[yhtio]'
								and otunnus = '$kukarow[kesken]' 
								and tunnus = '$uusitunnu'";
					$updres = mysql_query($query) or pupe_error($query);
				}
			}
		}
						
		$query = "	update tilausrivi set
					var = '$myyntitilirivit'
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$kukarow[kesken]' 
					and (tunnus = '$rivitunnus' or perheid='$rivitunnus')";
		$updres = mysql_query($query) or pupe_error($query);
										
		$tila 		= "";
		$tapa 		= "";
		$rivitunnus = "";
		$tilatapa	= "";
	}
		
	if ($tilatapa == "LASKUTA") {
		$query = "	UPDATE tilausrivi 
					SET varattu = kpl
					WHERE yhtio = '$kukarow[yhtio]' 
					and otunnus = '$kukarow[kesken]' 
					and var = 'A'
					and kpl != 0";
		$result = mysql_query($query) or pupe_error($query);
						
		$query	= "	select tunnus
					from varastopaikat
					where yhtio='$kukarow[yhtio]'
					and alkuhyllyalue  = '!!M'
					and loppuhyllyalue = '!!M'";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);
		
		$syot_varasto 	= $row["tunnus"];
		$syot_var 		= "H";
		$tilrivilisa	= " and var='A' and kpl != 0 ";
		
		// Kopsataan valitut rivit uudelle myyntitilaukselle
		require("tilauksesta_myyntitilaus.inc");
		$tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($kukarow["kesken"], $tilrivilisa, $syot_varasto, $syot_var);
		
		echo "$tilauksesta_myyntitilaus<br><br>";
									
		//Laitetaan myyntitilin kappalem‰‰ri‰ kuntoon jotta niit‰ ei voisi laskuttaa vahingossa monta kertaa
		$query = "	UPDATE tilausrivi 
					SET varattu = 0,
					kpl = 0 
					WHERE yhtio = '$kukarow[yhtio]' 
					and otunnus = '$kukarow[kesken]' 
					and var = 'A'
					and kpl != 0";
		$result = mysql_query($query) or pupe_error($query);
						
		$tila 		= "";
		$tapa 		= "";
		$rivitunnus = "";
		$tilatapa	= "";
		
		echo "<font class='message'>".t("Valitut myyntitilirivit siirretty laskutukseen")."!</font><br><br>";
		
		$tilatapa = "LEPAA";						
	}
	
	if ($tilatapa == "LEPAA") {	
		// poistetaan aktiiviset tilaukset jota t‰ll‰ k‰ytt‰j‰ll‰ oli
		$query = "update kuka set kesken='' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		$tee				= '';
		$tilausnumero		= '';
		$laskurow			= '';
		$kukarow['kesken']	= '';
	}

?>