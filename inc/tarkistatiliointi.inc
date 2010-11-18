<?php

	$ok 	= 0;
	$summa 	= (float) $summa;
	$tili 	= trim($tili);
	$ulos 	= "";

	if ($summa != 0 or strlen($tili) > 0) {

		// Onko tilinumero alphaa ja pitäisi muuttaa nimi numeroksi?
		if (strlen($tili) > 0 and !is_numeric($tili)) {

			$query = "	SELECT tilino, nimi
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						AND nimi LIKE '%$tili%'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				$ok = 1;
				$virhe = t("Yksikään tili ei sovi");
			}
			else {
				$ok = 1;
				$virhe = t("Useita tilejä löytyi");
				$ulos = "<select name='$selausnimi'>";

				while ($xrow = mysql_fetch_array($result)) {
					$ulos .= "<option value='$xrow[tilino]'>$xrow[tilino] $xrow[nimi]</option>";
				}

				$ulos .= "<option value=''>".t("Ei valintaa")."</option>";
				$ulos .= "</select>";
			}
		}

		if ($ok == 0 and strlen($tili) > 0) {

			$query = "	SELECT tilino, toimijaliitos, kustp, kohde, projekti
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						AND tilino = '$tili'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				$ok = 1;
				$virhe = t("Tuntematon tili")." '$tili'";
			}
			else {
				$xrow = mysql_fetch_array($result);
				$tili = $xrow['tilino'];
			}

			// Jos muistiotosite ja tilille on liitettävä asiakas tai toimittaja
			if ($tositetila == "X" and $xrow["toimijaliitos"] != "" and (int) $tositeliit == 0) {
				$virhe = t("Tositteeseen on liitettävä asiakas tai toimittaja")."!";
				$ok = 1;
			}
			
			if ($xrow['kustp'] != 0 and isset($kustp) and $kustp == 0) {
				$virhe = t("Pakollinen kustannuspaikka puuttui")."!";
				$ok = 1;
				$kustp = $xrow['kustp'];
			}

			if ($xrow['kohde'] != 0 and isset($kohde) and $kohde == 0) {
				$virhe = t("Pakollinen kohde puuttui")."!";
				$ok = 1;
				$kohde = $xrow['kohde'];
			}

			if ($xrow['projekti'] != 0 and isset($projekti) and $projekti == 0) {
				$virhe = t("Pakollinen projekti puuttui")."!";
				$ok = 1;
				$projekti = $xrow['projekti'];
			}

		}
	}

	if ($summa == 0 and $mistatullaan != 'ulask.php') {
		$virhe = t("Summa puuttuu")."!";
		$ok = 1;
	}

	if (strlen($tili) == 0) {
		$virhe = t("Tili puuttuu")."!";
		$ok = 1;
	}

?>