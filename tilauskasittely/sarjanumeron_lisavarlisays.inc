<?php

	if (!function_exists('lisavarlisays')) {
		function lisavarlisays ($sarjatun, $lisatty_tun) {
			global $kukarow, $yhtiorow;
			
			$query 	= "	select *
						from lasku
						where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
			$result  	= mysql_query($query) or pupe_error($query);
			$laskurow   = mysql_fetch_array($result);
			
			//Tutkitaan lisävarusteita
			$query = "	SELECT tilausrivi_osto.perheid2
						FROM sarjanumeroseuranta 
						JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus and tilausrivi_osto.tunnus=tilausrivi_osto.perheid2
						WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]' 
						and sarjanumeroseuranta.tunnus  = '$sarjatun'";
			$sarjares = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sarjares) == 1) {
				$sarjarow = mysql_fetch_array($sarjares);
						
				// Ostorivi
				$query = "	SELECT if(kpl!=0, kpl, tilkpl) kpl
							FROM tilausrivi 
							WHERE yhtio  = '$kukarow[yhtio]' 
							and tunnus   = '$sarjarow[perheid2]'
							and perheid2!= 0";
				$sarjares = mysql_query($query) or pupe_error($query);
				$ostorow = mysql_fetch_array($sarjares);
								
				// Haetaan muut lisävarusteet
				$query = "	SELECT tuoteno, round(kpl/$ostorow[kpl]) kpl, round(tilkpl/$ostorow[kpl]) tilkpl, tilkpl sistyomaarayskpl, var, 
							tyyppi, concat_ws('#', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka, tilaajanrivinro, perheid2
							FROM tilausrivi use index (yhtio_perheid2)
							WHERE yhtio  = '$kukarow[yhtio]' 
							and perheid2 = '$sarjarow[perheid2]'
							and tyyppi  != 'D'
							and tunnus  != '$sarjarow[perheid2]'
							and perheid2!= 0";
				$sarjares1 = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sarjares1) > 0) {
					$query = "	SELECT tunnus, perheid2, (varattu+jt) kpl
								FROM tilausrivi 
								WHERE yhtio  = '$kukarow[yhtio]' 
								and tunnus   = '$lisatty_tun'";
					$myyres = mysql_query($query) or pupe_error($query);
					$myyrow = mysql_fetch_array($myyres);

					if (abs($myyrow["kpl"]) != 1) {
						return t("VIRHE: Jos tuotteella on lisävausteita on tilausrivin tilattumäärän oltava 1!");
					}

					if($myyrow["perheid2"] == 0) {
						$query = "	UPDATE tilausrivi
						 			SET perheid2 = tunnus
									WHERE yhtio  = '$kukarow[yhtio]' 
									and tunnus   = '$lisatty_tun'";
						$myyres = mysql_query($query) or pupe_error($query);

						$myyrow["perheid2"] = $myyrow["tunnus"];	
					}
						
					while ($sarjarow = mysql_fetch_array($sarjares1)) {

						// haetaan tuotteen tiedot
						$query = "	select *
									from tuote
									where yhtio = '$kukarow[yhtio]'
									and tuoteno = '$sarjarow[tuoteno]'";
						$tuoteres = mysql_query($query);

						if (mysql_num_rows($tuoteres) == 0) {
							echo "<font class='error'>Tuotetta $sarjarow[tuoteno] ei löydy!</font><br>";
						}
						else {
							// tuote löytyi ok, lisätään rivi
							$trow = mysql_fetch_array($tuoteres);

							$ytunnus         = $laskurow["ytunnus"];
			
							if ($sarjarow["tyyppi"] == 'G') {
								$kpl		 = $sarjarow["sistyomaarayskpl"];
								$var		 = "";
							}
							elseif ($sarjarow["var"] != 'P') {
								$kpl		 = $sarjarow["kpl"];
								$var		 = "";
							}
							else {
								$kpl		 = $sarjarow["tilkpl"];
								$var		 = "P";	
							}
			
							$tuoteno         = $sarjarow["tuoteno"];
							$perheid2		 = $myyrow["perheid2"];
			
							//Tutkitaan vielä ettei tämä lisävaruste ole jo tän tuotteen alla
							$query = "	SELECT tunnus
										FROM tilausrivi use index (yhtio_perheid2) 
										WHERE yhtio  = '$kukarow[yhtio]' 
										and tuoteno  = '$tuoteno'
										and perheid2 = '$perheid2'";
							$tarkres = mysql_query($query) or pupe_error($query);
			 
							if (mysql_num_rows($tarkres) == 0) {
			
								$toimaika 	     = $laskurow["toimaika"];
								$kerayspvm	     = $laskurow["kerayspvm"];
								$hinta 		     = "";
								$netto 		     = "";
								$ale 		     = "";
								$alv		     = "";	
								$paikka			 = $sarjarow["paikka"];
								$varasto 	     = "";
								$rivitunnus		 = "";
								$korvaavakielto	 = "";
								$varataan_saldoa = "";
								$perheid 		 = 0;
								
								// Jos lisävaruste on pultattu kiinni laitteeseen sisäisellä työmääräyksellä
								// niin silloin, jos lisävauste on sarjanumeroitava, niin sarjanumeron tunnus tulee
								// lisävarusterivin tilaajanrivinro-kentässä
								if ($sarjarow["tyyppi"] == 'G' and $sarjarow["tilaajanrivinro"] > 0) {
									$query = "	SELECT tunnus, myyntirivitunnus
												FROM sarjanumeroseuranta 
												WHERE yhtio	= '$kukarow[yhtio]' 
												and tunnus  = '$sarjarow[tilaajanrivinro]'
												and siirtorivitunnus = '$sarjarow[perheid2]'";
									$tarkres = mysql_query($query) or pupe_error($query);
									
									if (mysql_num_rows($tarkres) == 1) {
										$myy_sarjatunnus = $sarjarow["tilaajanrivinro"];
									}
								}
								
								// jos meillä on ostoskori muuttujassa numero, niin halutaan lisätä tuotteita siihen ostoskoriin
								if (is_numeric($ostoskori)) {
									lisaa_ostoskoriin ($ostoskori, $laskurow["liitostunnus"], $tuoteno, $kpl);
									$kukarow["kesken"] = "";
								}
								elseif (file_exists("../tilauskasittely/lisaarivi.inc")) {
									require ("../tilauskasittely/lisaarivi.inc");
								}
								else {
									require ("lisaarivi.inc");
								}

								echo "<font class='message'>Lisättiin $kpl kpl tuotetta $tuoteno.</font><br>";
							}
						}
					}
					
					echo "<br><br>";
				}
			}
			
			return "OK";
		}
	}

?>
