<?php

	require('inc/parametrit.inc');
	
	echo "<font class='head'>".t("Korjaa sarjanumeromyyyntejä").":</font><hr><br>";
	
	if ($tee == "PAIVITA" and checkdate(substr($paivamaara,5,2), substr($paivamaara,8,2), substr($paivamaara,0,4))) {
		
		$query = "	SELECT distinct tilausrivi.tuoteno, sarjanumeroseuranta.ostorivitunnus
					FROM tilausrivi 
					JOIN tuote on tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.sarjanumeroseuranta = 'S'
					JOIN sarjanumeroseuranta ON tilausrivi.yhtio = sarjanumeroseuranta.yhtio and tilausrivi.tuoteno = sarjanumeroseuranta.tuoteno and tilausrivi.tunnus = sarjanumeroseuranta.myyntirivitunnus
					WHERE tilausrivi.yhtio	= '$kukarow[yhtio]'
					and tilausrivi.tyyppi	= 'L'
					and tilausrivi.laskutettuaika >= '$paivamaara'
					order by sarjanumeroseuranta.sarjanumero";
		$vresult = mysql_query($query) or pupe_error($query);

		while ($vrow = mysql_fetch_array($vresult)) {
			// Haetaan sarjanumeron ostohinta
			$ostohinta = sarjanumeron_ostohinta("ostorivitunnus", $vrow["ostorivitunnus"]);	
			
			echo "jalkilaskentafunktio($vrow[tuoteno], , $ostohinta, $vrow[ostorivitunnus]);<br>";
									
			jalkilaskentafunktio($vrow["tuoteno"], "", $ostohinta, $vrow["ostorivitunnus"]);
		}	
			
		
		/*
		$query = "	SELECT tilausrivi.tuoteno, tilausrivin_lisatiedot.osto_vai_hyvitys, sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.kaytetty, tilausrivi.tunnus, sarjanumeroseuranta.tunnus sarjatunnus, sarjanumeroseuranta.myyntirivitunnus
					FROM tilausrivi 
					JOIN tuote on tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.sarjanumeroseuranta = 'S'
					JOIN sarjanumeroseuranta ON tilausrivi.yhtio = sarjanumeroseuranta.yhtio and tilausrivi.tuoteno = sarjanumeroseuranta.tuoteno and tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.yhtio			= '$kukarow[yhtio]'
					and tilausrivi.tyyppi			= 'L'
					and tilausrivi.kpl 				< 0
					and tilausrivi.laskutettuaika  >= '$paivamaara'
					HAVING tilausrivin_lisatiedot.osto_vai_hyvitys is null or tilausrivin_lisatiedot.osto_vai_hyvitys != 'O'
					order by sarjanumeroseuranta.sarjanumero";
		$vresult = mysql_query($query) or pupe_error($query);

		while ($vrow = mysql_fetch_array($vresult)) {
			// Haetaan hyvitettävien myyntirivien kautta alkuperäiset ostorivit
			$query  = "	SELECT sarjanumeroseuranta.*, tilausrivi.rivihinta/tilausrivi.kpl ostohinta, tilausrivi.tunnus
						FROM sarjanumeroseuranta
						JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tuoteno=sarjanumeroseuranta.tuoteno and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
						WHERE sarjanumeroseuranta.yhtio 	= '$kukarow[yhtio]' 
						and sarjanumeroseuranta.tuoteno 	= '$vrow[tuoteno]'
						and sarjanumeroseuranta.sarjanumero = '$vrow[sarjanumero]'
						and sarjanumeroseuranta.kaytetty 	= '$vrow[kaytetty]'
						and sarjanumeroseuranta.myyntirivitunnus > 0
						and sarjanumeroseuranta.ostorivitunnus   > 0
						and sarjanumeroseuranta.ostorivitunnus != '$vrow[tunnus]'
						ORDER BY sarjanumeroseuranta.tunnus DESC
						LIMIT 1";
			$sarjares1 = mysql_query($query) or pupe_error($query);
			
			
			if (mysql_num_rows($sarjares1) == 1) {
				$sarjarow1 = mysql_fetch_array($sarjares1);						
					
				echo "$vrow[tuoteno] $vrow[ostohinta] <--> $sarjarow1[ostohinta]<br>";

				//Tehdään sarjanumerotauluun uusi vapaa sarjanumero-olio
				$query = "	UPDATE sarjanumeroseuranta
							SET myyntirivitunnus=ostorivitunnus, ostorivitunnus='$sarjarow1[ostorivitunnus]'
							WHERE yhtio = '$kukarow[yhtio]' 
							and tunnus 	= '$vrow[sarjatunnus]'";
				$sarjares1 = mysql_query($query) or pupe_error($query);
			
				$query = "	INSERT into sarjanumeroseuranta 
							(yhtio, tuoteno, sarjanumero, lisatieto, ostorivitunnus, myyntirivitunnus, kaytetty, laatija, luontiaika, takuu_alku, takuu_loppu)
							VALUES ('$kukarow[yhtio]','$sarjarow1[tuoteno]','$sarjarow1[sarjanumero]','$sarjarow1[lisatieto]','$sarjarow1[ostorivitunnus]', '$vrow[myyntirivitunnus]', '$sarjarow1[kaytetty]','$kukarow[kuka]',now(),'$sarjarow1[takuu_alku]','$sarjarow1[takuu_loppu]')";
				$sarjares1 = mysql_query($query) or pupe_error($query);			
			}
			else {
				echo "Ostoriviä ei löytynyt: $vrow[tuoteno] $vrow[sarjanumero]<br>";
			}
		}
		*/
		
		/*
		$query  = "	SELECT tilausrivi.otunnus, tilausrivi.tunnus myyntitunnus, tilausrivi.tuoteno, tilausrivi.nimitys,
					round(tilausrivi.rivihinta/tilausrivi.kpl,2) rivihinta, 
					round(tilausrivi.kate/tilausrivi.kpl,2) kate, 
					round(ostorivi.rivihinta/ostorivi.kpl,2) ostohinta,
					ostorivi.tunnus ostotunnus, 
					if(ostorivi.tyyppi='O', ostorivi.uusiotunnus, ostorivi.otunnus) ostotilaus, 
					sarjanumeroseuranta.sarjanumero sarjanumero, sarjanumeroseuranta.tunnus sarjatunnus,
					tilausrivi.kpl, myyntilasku.viesti, tilausrivin_lisatiedot.osto_vai_hyvitys, 
					if(sarjanumeroseuranta.kaytetty='' or sarjanumeroseuranta.kaytetty is null, 'Uusi', 'Käytetty') kaytetty,
					tuote.kehahin,
					(select count(*) from sarjanumeroseuranta css where css.yhtio=tilausrivi.yhtio and css.tuoteno=tilausrivi.tuoteno and css.myyntirivitunnus=tilausrivi.tunnus) css
					FROM tilausrivi
					JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					LEFT JOIN sarjanumeroseuranta ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tuoteno=sarjanumeroseuranta.tuoteno and tilausrivi.tunnus=sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi ostorivi ON ostorivi.yhtio=sarjanumeroseuranta.yhtio and ostorivi.tunnus=sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN lasku myyntilasku ON myyntilasku.yhtio=tilausrivi.yhtio and myyntilasku.tunnus=tilausrivi.otunnus
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi = 'L' 
					and tilausrivi.kpl > 0
					and tilausrivi.laskutettuaika >= '$paivamaara'
					having ostotunnus is null
					order by sarjanumero, otunnus";
		$res = mysql_query($query) or pupe_error($query);
		
		$lask = 1;
		
		while($vrow = mysql_fetch_array($res)) {
			if ((float) $vrow["ostohinta"] == 0 and $vrow["kehahin"] > 0) {
				echo "$lask $vrow[tuoteno] $vrow[nimitys] $vrow[rivihinta] ";
				echo "<font style='color: 00FF00;'>$vrow[kehahin]</font> ";
				echo "<br>";
				$lask++;
				
				// Luodaan ostorivi
				// Otunnus 307117
				// Uusiotunnus 313901

				// lisätään ostorivi
				$query = "	INSERT into tilausrivi set
							yhtio 			= '$kukarow[yhtio]',
							tuoteno 		= '$vrow[tuoteno]',
							varattu 		= '0',
							yksikko 		= 'KPL',
							kpl 			= '1',
							tilkpl 			= '1',
							jt				= '0',
							ale 			= '0',
							alv 			= '0',
							netto			= '',
							hinta 			= '$vrow[kehahin]',
							laatija			= '$kukarow[kuka]',
							laadittu		= now(),
							keratty			= '$kukarow[kuka]',
							kerattyaika		= now(),
							toimitettu		= '$kukarow[kuka]',
							toimitettuaika	= now(),
							laskutettu		= '$kukarow[kuka]',
							laskutettuaika	= now(),
							toimaika		= now(),
							kerayspvm		= now(),
							otunnus 		= '307117',
							uusiotunnus		= '313901',
							tyyppi 			= 'O',
							kommentti 		= 'Sarjanumeroiden kehahin-inout-korjaus',
							var 			= '',
							try				= '',
							osasto			= '',
							perheid			= '',
							perheid2		= '',
							nimitys 		= '$vrow[nimitys]',
							jaksotettu		= '',
							rivihinta		= '$vrow[kehahin]'";
				$result = mysql_query($query) or die($query);
				$lisatty_tun = mysql_insert_id();
				
				$query = "	UPDATE sarjanumeroseuranta
							SET ostorivitunnus = '$lisatty_tun'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$vrow[sarjatunnus]'";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
		*/

		$tee = "";
	}
	
	if ($tee == "") {
		
		echo "<br><br>";
		echo "Syötä päivämäärä josta korjataan:<br>";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='PAIVITA'>";
		echo "<input type='text' name='paivamaara' size='15'>";
		echo "<input type='submit' value='Korjaa'>";
		echo "</form>";
		
		/*
		echo "<br><br>";
		echo "Syötä korjattava tilaus:<br>";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='PAIVITA'>";
		echo "<input type='text' name='otunnus' size='10'>";
		echo "<input type='submit' value='Korjaa'>";
		echo "</form>";
		*/
	}
	
	require ("inc/footer.inc");
	
?>
