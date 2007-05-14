<?php

	if (count($tilinoarray[$yritirow['tunnus']]) > 0) {
		foreach ($tilinoarray[$yritirow['tunnus']] as $hyvitystilino) {
		
			$query = "	SELECT maksu_tili,
				 		left(concat_ws(' ', lasku.nimi, nimitark),30) nimi,
				 		left(concat_ws(' ', osoite, osoitetark),20) osoite,
				 		left(concat_ws(' ', postino, postitp),20) postitp,
				 		summa, lasku.valkoodi, viite, viesti, 
						tilinumero, lasku.tunnus, sisviesti2, 
						yriti.tilino ytilino, alatila, kasumma
			  			FROM lasku, yriti
			  			WHERE lasku.yhtio 	= '$kukarow[yhtio]' 
						and tila 			= 'P' 
						and maa 			= '$kotimaa' 
						and yriti.tunnus 	= maksu_tili 
						and yriti.yhtio 	= lasku.yhtio 
						and maksaja 		= '$kukarow[kuka]' 
						and tilinumero		= '$hyvitystilino' 
						and maksu_tili		= '$yritirow[tunnus]' 
						and olmapvm 		= '$pvmrow[olmapvm]'
			  			ORDER BY summa desc";
			$xresult = mysql_query($query) or pupe_error($query);
		
			if (mysql_num_rows($xresult) == 0) {
				echo "<font class='message'>".t("Sopivia laskuja ei löydy. This is bad, very bad")."!</font><br>";
				exit;
			}
		
			// Näin tunnistamme ensimmäisen maksun
			$yritystilino=''; 
		
			while ($laskurow = mysql_fetch_array ($xresult)) {
				// Muutamme hieman ensimmäisen maksun tietoja
				if ($yritystilino == '') { 
					$laskurow["viite"] 		= '';		//Viitenroa ei sallita
					$laskutapahtuma 		= '10';		//Maksutapahtuma
					$laskurow["alatila"] 	= ''; 		//Ei käteisalennusta
					$laskurow["summa"]		= $summaarray[$yritirow['tunnus']] [$laskurow['tilinumero']];
					mysql_data_seek($xresult,0);
				}
				else {
					$laskutapahtuma = '20'; //Erittely
				
					if ($laskurow["summa"] < 0) { //Se on nyt hyvitystä!
						$laskurow["summa"] 		= $laskurow["summa"] * -1;
						$laskurow["kasumma"] 	= $laskurow["kasumma"] * -1;
						$laskutapahtuma 		= '22'; //Hyvityserittely
					}
				}			
				$yritystilino 	= $laskurow["ytilino"];
				$laskunimi1 	= $laskurow["nimi"];
				$laskunimi2 	= $laskurow["osoite"];
				$laskunimi3 	= $laskurow["postitp"];
			
				if ($laskurow["alatila"] == 'K') { // maksetaan käteisalennuksella
					$laskusumma = $laskurow["summa"] - $laskurow["kasumma"];
				}
				else {
					$laskusumma = $laskurow["summa"];
				}
			
				$laskutilno = $laskurow["tilinumero"];
				$laskusis1  = $laskurow["tunnus"];
				$laskusis2  = $laskurow["sisviesti2"];
				$laskuvaluutta = 1;
				$laskutyyppi = 5;
			 	$laskuviesti = $laskurow["viesti"];
			 	
				if (strlen($laskurow["viite"]) > 0) {
					$laskuviesti = $laskurow["viite"];
					$laskutyyppi = 1;
				}
			
				require ("inc/lm03rivi.inc");
			
				if ($laskutapahtuma == '10') {
					$makskpl += 1;
					$makssumma += $laskusumma;
				}
			
				$query = "	UPDATE lasku 
							SET tila = 'Q'
							WHERE tunnus = $laskurow[tunnus]";
				$uresult = mysql_query($query) or pupe_error($query);
			}
		}
	}

?>
