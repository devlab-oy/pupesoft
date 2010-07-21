<?php

	$ok = 0;
	$summa = (float) $summa;

	if ($summa != 0 or strlen($tili) > 0) {

		// Onko tilinumero alphaa ja pitäisi muuttaa nimi numeroksi?
		if (!is_numeric($tili)) {

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

			$query = "	SELECT tilino
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