<?php
	
			require ("../inc/parametrit.inc");

			//haetaan kaikki uuden systeemin laskut
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio='$kukarow[yhtio]' and tunnus > 1314308 and tila='L' and alatila!='V' and alatila!='X' and yhtio='arwi' and vienti=''
						ORDER BY laatija, tunnus";
			$result = mysql_query($query) or pupe_error($query);

			$lask1=1;
			$lask2=1;
			$lask3=0;
			$lask4=1;
			echo "<pre>";
			echo sprintf('%04d',0000)." Selite      | ".sprintf('%15.15s',"Tilaus")." | ".sprintf('%6.6s',"Laatija")." | ".sprintf('%15s',"Tuoteno")." | ".sprintf('%30.30s',"Nimitys")." | ".sprintf('%-10s',"Hinta1")." | ".sprintf('%-10s',"Hinta2")." | ".sprintf('%1s',"V")." | ".sprintf('%1s',"N")." | ".sprintf('%-5s',"ALE1")." | ".sprintf('%-5s',"ALE2")." |<br>";
			
			while ($laskurow = mysql_fetch_array($result)) {
				//tiedot tilausrivilt‰
				$query = "	SELECT *
							FROM tilausrivi
							WHERE yhtio='$kukarow[yhtio]' and otunnus='$laskurow[tunnus]'
							ORDER BY laadittu";
				$tresult = mysql_query($query) or pupe_error($query);
      	
				while($tilausrivirow = mysql_fetch_array($tresult)) {				
					//tiedot tuotteelta
					$query = "	SELECT *
								FROM tuote
								WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tilausrivirow[tuoteno]'";
					$turesult = mysql_query($query) or pupe_error($query);
					$tuoterow = mysql_fetch_array($turesult);
					
					//alehinta.incin vaatimuksia
					$trow		= $tuoterow;
					$kpl 		= $tilausrivirow['varattu'];
					$hinta 		= 0;
					$ale       	= 0;
					
					if (strtoupper($tilausrivirow["netto"]) == 'N') {
						$netto = 1;
					}
					else {
						$netto = 0;
					}
					require ("../inc/alehinta.inc");
					
					if ($tilausrivirow["hinta"] != $hinta 
						and !($tilausrivirow["hinta"]!=0 and $tilausrivirow["netto"]!='') 
						and $tilausrivirow["var"] != 'P' 
						and $hinta > 0
						and $tilausrivirow["hinta"] == 0) {
						echo sprintf('%04d',$lask1)." V‰‰r‰ hinta | ".sprintf('%15.15s',$tilausrivirow["otunnus"])." | ".sprintf('%6.6s',$laskurow["laatija"])." | ".sprintf('%15s',$tilausrivirow["tuoteno"])." | ".sprintf('%30.30s',$tilausrivirow["nimitys"])." | ".sprintf('%10s',$tilausrivirow["hinta"])." | ".sprintf('%10s',$hinta)." | ".sprintf('%1s',$tilausrivirow["var"])." | ".sprintf('%1s',$tilausrivirow["netto"])." | ".sprintf('%02.2f',$tilausrivirow["ale"])." | ".sprintf('%02.2f',$ale)." |<br>";
						$lask1++;
						
						//p‰ivitet‰‰n ale
						$query = "	UPDATE tilausrivi set hinta='$hinta'
									WHERE tunnus='$tilausrivirow[tunnus]'
									LIMIT 1";
						//$aleresult = mysql_query($query) or pupe_error($query);
						
						
					}
					else {
						//echo "$lask2. Hinta taitaa olla oikea $tilausrivirow[hinta] | $hinta | $tilausrivirow[netto]<br>";
						$lask2++;
					}
					if ($tilausrivirow["ale"] != $ale and $ale > $tilausrivirow["ale"]) {
						//p‰ivitet‰‰n ale
						$query = "	UPDATE tilausrivi set ale='$ale'
									WHERE tunnus='$tilausrivirow[tunnus]'
									LIMIT 1";
						//$aleresult = mysql_query($query) or pupe_error($query);
						
						echo sprintf('%04d',$lask4)." V‰‰r‰ ALE   | ".sprintf('%15.15s',$tilausrivirow["otunnus"])." | ".sprintf('%6.6s',$laskurow["laatija"])." | ".sprintf('%15s',$tilausrivirow["tuoteno"])." | ".sprintf('%30.30s',$tilausrivirow["nimitys"])." | ".sprintf('%10s',$tilausrivirow["hinta"])." | ".sprintf('%10s',$hinta)." | ".sprintf('%1s',$tilausrivirow["var"])." | ".sprintf('%1s',$tilausrivirow["netto"])." | ".sprintf('%02.2f',$tilausrivirow["ale"])." | ".sprintf('%02.2f',$ale)." |<br>";
						$lask4++;                                                                                                      
					}
					
					$lask3++;
				}			
			}
			
			echo "</pre>";
			echo "<br><br>$lask3 Rivi‰ checkattu!<br><br>";
			
			require ("../inc/footer.inc");
?>
