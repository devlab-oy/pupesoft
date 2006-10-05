<?php
	$tiliotesaantotilino = $yhtiorow['selvittelytili']; //Oletus
	$tiliointisaantorow['tilino'] = $yhtiorow['selvittelytili'];
	$query = "SELECT * FROM tiliotesaanto
					WHERE yhtio = '$yritirow[yhtio]' and erittely='' and pankkitili = '$pankkitilino' and
							koodi = '$koodi' and koodiselite = '$koodiselite' and
							nimitieto = '$maksaa' and locate(selite, '$vientiselite') > 0";
	$tiliotesaantoresult = mysql_query($query) or pupe_error($query);
	//echo "$query (".mysql_num_rows($tiliotesaantoresult).") -->";
	if (mysql_num_rows($tiliotesaantoresult) > 0) { // Jotain löytyy
		if (mysql_num_rows($tiliotesaantoresult) == 1) { // WOW! Täydellinen osuma
			$tiliointisaantorow=mysql_fetch_array ($tiliotesaantoresult);
			//echo "Sääntö on $tiliointisaantorow[tilino]<br>";
			$tiliotesaantotilino = $tiliointisaantorow['tilino'];
		}
		else {
			echo "".t("Monta sopivaa sääntöä PANIC")."!";
			exit;
		}
	}
	else { // Ei löytynyt höllätään
		$query = "SELECT * FROM tiliotesaanto
					WHERE yhtio = '$yritirow[yhtio]' and erittely='' and pankkitili = '$pankkitilino' and
							koodi = '$koodi' and koodiselite = '$koodiselite' and
							nimitieto = '$maksaa' and selite = ''";
		$tiliotesaantoresult = mysql_query($query) or pupe_error($query);
		//echo "$query (".mysql_num_rows($tiliotesaantoresult).") -->";
		if (mysql_num_rows($tiliotesaantoresult) > 0) { // Jotain löytyy
			if (mysql_num_rows($tiliotesaantoresult) == 1) { // WOW! Täydellinen osuma
				$tiliointisaantorow=mysql_fetch_array ($tiliotesaantoresult);
				//echo "Sääntö on $tiliointisaantorow[tilino]<br>";
				$tiliotesaantotilino = $tiliointisaantorow['tilino'];
			}
			else {
				echo "".t("Monta sopivaa sääntöä PANIC")."!";
				exit;
			}
		}
		else {
			$query = "SELECT * FROM tiliotesaanto
					WHERE yhtio = '$yritirow[yhtio]' and erittely='' and pankkitili = '$pankkitilino' and
							koodi = '$koodi' and koodiselite = '$koodiselite' and
							nimitieto = '' and selite = ''";
			$tiliotesaantoresult = mysql_query($query) or pupe_error($query);
			//echo "$query (".mysql_num_rows($tiliotesaantoresult).") -->";
			if (mysql_num_rows($tiliotesaantoresult) > 0) { // Jotain löytyy
				if (mysql_num_rows($tiliotesaantoresult) == 1) { // WOW! Täydellinen osuma
					$tiliointisaantorow=mysql_fetch_array ($tiliotesaantoresult);
					//echo "Sääntö on $tiliointisaantorow[tilino]<br>";
					$tiliotesaantotilino = $tiliointisaantorow['tilino'];
				}
				else {
					echo "".t("Monta sopivaa sääntöä PANIC")."!";
					exit;
				}
			}
			else {
				$query = "SELECT * FROM tiliotesaanto
					WHERE yhtio = '$yritirow[yhtio]' and erittely='' and pankkitili = '$pankkitilino' and
							koodi = '$koodi' and koodiselite = '' and
							nimitieto = '' and selite = ''";
				$tiliotesaantoresult = mysql_query($query) or pupe_error($query);
				//echo "$query (".mysql_num_rows($tiliotesaantoresult).") -->";
				if (mysql_num_rows($tiliotesaantoresult) > 0) { // Jotain löytyy
					if (mysql_num_rows($tiliotesaantoresult) == 1) { // WOW! Täydellinen osuma
						$tiliointisaantorow=mysql_fetch_array ($tiliotesaantoresult);
						//echo "Sääntö on $tiliointisaantorow[tilino]<br>";
						$tiliotesaantotilino = $tiliointisaantorow['tilino'];
					}
					else {
						echo "".t("Monta sopivaa sääntöä PANIC")."!";
						exit;
					}
				}
				else {
					//echo "Mitään ei löydy '$maksaa' '$koodi' '$koodiselite'";
				}
			}
		}
	}
?>
