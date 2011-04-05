<?php

function teeValinta($tunken, $lisaaTilausriviTyypit) {
	global $yhtiorow, $kukarow, $era_new_paikka, $era_old_paikka;

	$paivitetty = 0;

	foreach ($era_new_paikka as $rivitunnus => $uusipaikka) {

		//	Jos meill‰ on uusipaikka ja se on eri kuin vanhapaikka kohdistetaan er‰
		if ($uusipaikka != $era_old_paikka[$rivitunnus]) {
			
			$query = "	SELECT *
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$rivitunnus'
						and tyyppi IN ('".implode("','", $lisaaTilausriviTyypit)."')";
			$toimres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($toimres) > 0) {
				$toimrow = mysql_fetch_array($toimres);

				list($myy_hyllyalue, $myy_hyllynro, $myy_hyllyvali, $myy_hyllytaso, $myy_era) = explode("#", $era_new_paikka[$toimrow["tunnus"]]);

				$query = "	DELETE FROM sarjanumeroseuranta
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$toimrow[tuoteno]'
							and myyntirivitunnus = '$toimrow[tunnus]'";
				$sarjares2 = mysql_query($query) or pupe_error($query);

				if ($uusipaikka != "") {
					if ($toimrow["varattu"] > 0) {
						$query = "	SELECT *
									FROM sarjanumeroseuranta
									WHERE yhtio		= '$kukarow[yhtio]'
									and tuoteno		= '$toimrow[tuoteno]'
									and hyllyalue   = '$myy_hyllyalue'
									and hyllynro    = '$myy_hyllynro'
									and hyllytaso   = '$myy_hyllytaso'
									and hyllyvali   = '$myy_hyllyvali'
									and sarjanumero = '$myy_era'
									and myyntirivitunnus = 0
									and ostorivitunnus > 0
									LIMIT 1";
						$lisa_res = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($lisa_res) > 0) {
							$lisa_row = mysql_fetch_array($lisa_res);
							$oslisa = " ostorivitunnus ='$lisa_row[ostorivitunnus]', ";
						}
						else {
							$oslisa = " ostorivitunnus ='', ";
						}

					}
					else {
						$tunken = "ostorivitunnus";
						$oslisa = "";
					}

					$query = "	INSERT into sarjanumeroseuranta
								SET yhtio 		= '$kukarow[yhtio]',
								tuoteno			= '$toimrow[tuoteno]',
								lisatieto 		= '$lisa_row[lisatieto]',
								$tunken 		= '$toimrow[tunnus]',
								$oslisa
								kaytetty		= '$lisa_row[kaytetty]',
								era_kpl			= '',
								laatija			= '$kukarow[kuka]',
								luontiaika		= now(),
								takuu_alku 		= '$lisa_row[takuu_alku]',
								takuu_loppu		= '$lisa_row[takuu_loppu]',
								parasta_ennen	= '$lisa_row[parasta_ennen]',
								hyllyalue   	= '$myy_hyllyalue',
								hyllynro    	= '$myy_hyllynro',
								hyllytaso   	= '$myy_hyllytaso',
								hyllyvali   	= '$myy_hyllyvali',
								sarjanumero 	= '$myy_era'";
					$lisa_res = mysql_query($query) or pupe_error($query);

					$query = "	UPDATE tilausrivi
								SET hyllyalue   = '$myy_hyllyalue',
								hyllynro    	= '$myy_hyllynro',
								hyllytaso   	= '$myy_hyllytaso',
								hyllyvali   	= '$myy_hyllyvali'
								WHERE yhtio 	= '$kukarow[yhtio]'
								and tunnus		= '$toimrow[tunnus]'";
					$lisa_res = mysql_query($query) or pupe_error($query);

					$paivitetty++;
				}
			}
			else {
				echo "Rivi‰ ei voi liitt‰‰ $query<br>";
			}
		}
	}

	return $paivitetty;
}

?>