<?php

	require ("functions.inc");

	$tuoteno = trim ($_GET['tuoteno']);
	$maara = (int) $_GET['maara'];
	$kukarow["yhtio"] = "artr";
	
	if ($tuoteno != '') {

		$con = mysql_pconnect("d60.arwidson.fi", "pupeweb","web1") or die("Tietokantaongelma1!");
		mysql_select_db("pupesoft") or die ("Tietokantaongelma2!");

		$query = "select * from tuote WHERE yhtio='artr' and tuoteno='$tuoteno'";
		$result = mysql_query($query) or die($query);
		
		if (mysql_num_rows($result) == 1) {

			// katotaan paljonko on myytävissä
			$myytavissa = saldo_myytavissa($tuoteno);

			// jos meillä on tarpeeksi myytävää
			if ($myytavissa >= $maara and $myytavissa > 0) {
				echo "SAATAVUUS=1\n";
			}
			elseif ($myytavissa > 0) {
				echo "SAATAVUUS=2\n";
			}
			else {
				echo "SAATAVUUS=0\n";
			}

			// haetaan korvaavia tuotteita
			$query  = "select * from korvaavat use index (yhtio_tuoteno) where yhtio='artr' and tuoteno='$tuoteno'";
			$kores  = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($kores) > 0) {

				$kkrow  = mysql_fetch_array($kores);
				$query  = "	select tuoteno from korvaavat use index (yhtio_id)
							where yhtio='artr' and id='$kkrow[id]' 
							order by jarjestys, tuoteno";
				$kores  = mysql_query($query) or pupe_error($query);
				$nexti  = 0;

				while ($korow = mysql_fetch_array($kores)) {
					if ($nexti == 1) {
						echo "KORVAAVA=$korow[tuoteno]\n";
						$nexti = 2; // muutetaan lippu niin tiedetään että seuraava löyty
						break;
					}
					if ($korow['tuoteno'] == $tuoteno) {
						$nexti = 1; // meidän tulee ottaa seuraava tuote, koska se on tämän tuotteen jälkeen seuraava korvaava
					}
				}
				
				// ei löydetty nextiä vaikka ois pitäny, oltiin ilmeisesti sitte vikassa tuotteessa, haetaan eka korvaava
				if ($nexti == 1) {
					$query = "	select tuoteno from korvaavat use index (yhtio_id)
								where yhtio='artr' and id='$kkrow[id]' and tuoteno!='$tuoteno'
								order by jarjestys, tuoteno
								limit 1";
					$kores  = mysql_query($query) or pupe_error($query);
					
					if (mysql_num_rows($kores) == 1) {
						$korow = mysql_fetch_array($kores);
						echo "KORVAAVA=$korow[tuoteno]\n";
					}
				}
			}
			
		} // end löytyykö
		else {
			// tuotetta ei löydy
			echo "SAATAVUUS=-1\n";
		}
	} // end if tuoteno

?>