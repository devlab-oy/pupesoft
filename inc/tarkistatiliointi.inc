<?php
	$ok = 0;
	if ((strlen($summa) > 0) || (strlen($tili) > 0)) {
// Onko tilinumero alphaa ja pitäisi nuuttaa nimi numeroksi??
		if ((($tili + 0) == 0) && (strlen($tili) > 0)) { //Joku kirjainyhdeistelmä löytyi
			$tili = "%" . $tili . "%";
			$query = "SELECT tilino, nimi
                                  FROM tili
                                  WHERE yhtio = '$kukarow[yhtio]' and nimi like '$tili'";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) == 0) {
				$ok = 1;
				$virhe = "".t("Yksikään tili ei sovi")."";
			}
			else {
				$ok = 1;
				$virhe = "".t("Useita tilejä löytyi")."";
				$ulos = "<select name='$selausnimi'>";
				while ($xrow = mysql_fetch_array ($result)) {
					$ulos .= "<option value='$xrow[tilino]'>$xrow[tilino] $xrow[nimi]";
				}
				$ulos .= "<option value = ''>".t("Ei valintaa")."";
				$ulos .= "</select>";
			}
		}
		if (($ok == 0) && (strlen($tili) > 0)) {
			$summa = $summa + 0;
			$query = "SELECT tilino, oletusalv
		        		  FROM tili
		          		  WHERE yhtio = '$kukarow[yhtio]' and tilino = '$tili'";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) == 0) {
				$ok = 1;
				$virhe = "".t("Tuntematon tili")." '$tili'";
			}
			else {
				$xrow=mysql_fetch_array($result);
				$tili = $xrow['tilino'];
				// Jos alvit on hardkoodattu tileihin (hui, kauheaa, mutta joku niin tahtoo), niin salasanat.php:ssä
				// on määritettävä $hardcoded_alv=1;
				if ($hardcoded_alv==1) {
					if ($xrow['oletusalv'] == 0) {
						$virhe = t('Tililtä puuttuu oletusalv eikä sitä voida käyttää');
						$ok = 1;
					}
					else {
						if ($xrow['oletusalv'] == 99) $xrow['oletusalv']=0;
						$vero = $xrow['oletusalv'];
					}
				}
			}
		}
	}

	$summa += 0;
	if ($summa == 0) {
		$virhe = "".t("Summa puuttuu")."!";
		$ok = 1;
	}

	if (strlen($tili) == 0) {
		$virhe = "".t("Tili puuttuu")."!";
		$ok = 1;
	}
?>
