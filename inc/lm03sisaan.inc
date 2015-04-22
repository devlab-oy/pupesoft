<?php
	function tarkistaTilinumero($tilinumero) {
		// tarkistetaan, ett‰ tilinumero alkaa 'FI'
		if (substr($tilinumero, 0, 2) ==  'FI' ) {
		
			$tilinumero = trim($tilinumero);  // Lis‰tty 13.9.2013

			// tarkistetaan, ett‰ tilinumero lˆytyy kannasta (Muokattu 31.7.2013, muutettu taulun nimi pankkitili -> TAMK_pankkitili)
			$query = "SELECT 	*
						FROM 	TAMK_pankkitili
						WHERE 	tilinro = '$tilinumero' AND yhtio = 'pankk' 
						";
						
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result) == 1) {
				$return = mysql_fetch_array($result);
				return $return;
			} 
			else {
				return 129; //Tilinumeroa ei lˆydy.
			}
		}
		else {
			// hyv‰ksyt‰‰n
			return 128; // Ulkoinen tilinumero 
		}
	}

	function kasittelelm03($fd) {
		global $yhtiorow, $kukarow;
		$ok=0;
		$tietue = fgets($fd);
		while ((!feof($fd)) and $ok==0) {

			if (substr($tietue, 0, 4) == "LM03") {
			
				if (substr($tietue, 0, 6) == "LM0319" || substr($tietue, 0, 6) == "LM0329") { // Viestitietue
					$maksajanNimi = substr($tietue, 24, 30);  // Muokattu 13.9.2013, 20 -> 24
					$maksuquery .= ", maksajanNimi = '$maksajanNimi'";

					$result = mysql_query($maksuquery) or pupe_error($maksuquery);
					unset($maksuquery);
					
					/* Lis‰tty 19.11.2013, lis‰tty ehto ettei tule turhia virheit‰ */
					if (isset($lisa_query))
					{
						$toinen_result = mysql_query($lisa_query) or pupe_error($lisa_query);  // Lis‰tty 1.11.2013
						unset($lisa_query);  // Lis‰tty 1.11.2013
					}
					/* Lis‰ys p‰‰ttyy 19.11.2013 */
				}
				if($maksuquery){
					$maksuquery .= ", maksajanNimi = '$maksajanNimi'";

					$result = mysql_query($maksuquery) or pupe_error($maksuquery);
					unset($maksuquery);
					
					/* Lis‰tty 19.11.2013, lis‰tty ehto ettei tule turhia virheit‰ */
					if (isset($lisa_query))
					{
						$toinen_result = mysql_query($lisa_query) or pupe_error($lisa_query);  // Lis‰tty 1.11.2013
						unset($lisa_query);  // Lis‰tty 1.11.2013
					}
					/* Lis‰ys p‰‰ttyy 19.11.2013 */
				}
			
				if (substr($tietue, 0, 6) == "LM0300") { // Otsikko
					$maksaja = substr($tietue, 6, 18); // Muokattu 13.9.2013, 14 -> 18
					// tarkistetaan, ett‰ tilinumero on oikeanlainen
					$maksajaPaluu = tarkistaTilinumero($maksaja);
					// jos palautuu taulu, otetaan nimi talteen
					if (is_array($maksajaPaluu)) {
						
						/* Lis‰tty 20.9.2013, ehto jolla asetetaan oikea nimi maksajalle, jos kyseess‰ on Myyr‰n toimipiste */
						if($maksajaPaluu[ 'omistaja' ] == "Kauppakeskus Myyr‰ Ty")
						{
							$uusi_maksaja = substr($tietue, 304);
							$uusi_maksaja = trim($uusi_maksaja);
							$maksajaPaluu[ 'omistaja' ] = $uusi_maksaja;
						}
						/* Lis‰ys p‰‰ttyy 20.9.2013 */
						
						$maksajanNimi = $maksajaPaluu[ 'omistaja' ];
					} else {
						//echo $maksajaPaluu;
						if ($maksajaPaluu == 129) {
							print 'Maksajan tilinumero on virheellinen.';
						}
					}
					
					$tapvmx = substr($tietue, 45, 6);  // Muokattu 13.9.2013, muutettu 41 -> 45
					//$tapvm = substr($tapvmx,4,2)."-".substr($tapvmx,2,2)."-20".substr($tapvmx,0,2);
					$tapvm = "20" . substr($tapvmx,0,2) . "-" . substr($tapvmx,2,2) . "-" . substr($tapvmx,4,2);
				}				
				
				if (substr($tietue, 0, 6) == "LM0310" || substr($tietue, 0, 6) == "LM0320") { // Tavallinen maksu tai erittelytapahtuma
					$saaja = substr($tietue, 94, 18); // Muokattu 13.9.2013, 14 -> 18 ja 90 -> 94
					// tarkistetaan, ett‰ tilinumero on oikeanlainen
					$saajaPaluu = tarkistaTilinumero($saaja);
					// jos palautuu taulu, otetaan nimi talteen
					if (is_array($saajaPaluu)) {
						$saajanNimi = $saajaPaluu[ 'omistaja' ];
					} else {
						//echo $saajaPaluu;
						if ($saajaPaluu == 129) {
							print 'Saajan tilinumero on virheellinen.';
						}
					}
					
					$summa = substr($tietue, 195, 12) / 100;  // Muokattu 13.9.2013, muutettu 186 -> 195

					if (substr($tietue, 5, 1) == 2) { // hyvityslasku
						$summa = $summa * -1;
					}
					$saajanNimi = substr($tietue, 24, 30);  // Muokattu 13.9.2013, muutettu 20 -> 21. Muokattu 20.11.2013, v‰hennetty nimen pituutta 70 -> 30 ja aloitus kohta 21 -> 24.
					// poistetaan ylim‰‰r‰iset v‰lit
					$saajanNimi = preg_replace('/\h+/', ' ', $saajanNimi);
					$saajanNimi = trim($saajanNimi);  // Lis‰tty 20.11.2013, trimmataan kaikki ylim‰‰r‰iset v‰lit alusta ja lopusta pois.
					
					//echo "maksaja $maksaja, saaja $saaja, summa $summa<br>";
					$arkistotunnus = uniqid('41n0P');
					
					// Muokattu 31.7.2013, vaihdettu taulun nimi pankkitapahtuma -> TAMK_pankkitapahtuma
					$maksuquery = "	INSERT INTO TAMK_pankkitapahtuma SET 
									yhtio = 'pankk'
									, saajanNimi = '$saajanNimi'
									, saaja = '$saaja'
									, maksaja='$maksaja'
									, summa='$summa'
									, tapvm=if('$tapvm' < now(), now(), '$tapvm')
									, kurssi=1
									, valkoodi = 'EUR'
									, arkistotunnus = '$arkistotunnus'
									, laatija='$kukarow[kuka]'
									, luontiaika=now()
								";
					
					// jos maksajan tai saajan tilinumero on virheellinen, laitetaan virhekoodi 9 eiVaikutaSaldoon -kentt‰‰n
					if (($maksajaPaluu == 129) || ($saajaPaluu == 129)) {
						$maksuquery .= ", eiVaikutaSaldoon = 9";
					}
					else if (substr($tietue, 4, 1) == 2) { // erittelytapahtuma, ei vaikutusta tilin saldoon
						$maksuquery .= ", eiVaikutaSaldoon = 1";
					} 
					
					if (substr($tietue, 115, 1) == 1) { // viite. Muokattu 31.10.2013, muutettu 107 -> 115
						$viite = ltrim(substr($tietue, 116, 20),'0');  // Lis‰tty ja muokattu 31.10.2013. Muokattu 108 -> 116, lis‰tty ltrim jolla poistetaan "turhat" nollat
						$maksuquery .= ", viite = '" . $viite . "'";
					}
					
					/* Lis‰tty 1.11.2013, t‰ll‰ mahdollistetaan se, ett‰ laskujen siirtyess‰ Ainopankkiin ne katoavat ns. "Saatavat" listalta (lasku merkataan maksetuksi) */					
					if (!empty($yhtio) and $yhtio != FALSE)
					{
						
						$query = "SELECT yhtio, maksaja, tila, alatila, summa, viite, laskunro, ytunnus, tunnus
								  FROM lasku
								  WHERE tila = 'U'
								  AND alatila = 'X'
								  AND viite = '$viite'
								  AND yhtio = '$yhtiorow[yhtio]'";
								  
						$result = mysql_query($query);
						$apu = mysql_fetch_assoc($result);
						
						if (!empty($apu) and $apu != FALSE)
						{
							$laskunro = $apu['laskunro'];
							$tunnus = (int) $apu['tunnus'];
							
							$tunnus = $tunnus + 1;
							
							$lisa_query = "UPDATE lasku ";
							$lisa_query .= "SET tila = 'Y', alatila = '', maksuaika = now()
										  WHERE tunnus = '$tunnus'
										  AND laskunro = '$laskunro'
										  AND viite = '$viite'
										  AND summa = '$summa'";
							
						}
					}
					/* Lis‰ys p‰‰ttyy 1.11.2013 */
				}
				if (substr($tietue, 0, 6) == "LM0390") { // Tiedosto loppu
					//echo "** the end **<br>";
				}
				$tietue = fgets($fd);
			}
			else {
				echo "<font class='errer'>V‰‰r‰n tyyppinen tiedosto. Se ei ala LM03<br></font>";
				$ok = 1;
			}
		}
	}
?>
