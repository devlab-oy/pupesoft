<?php

if(!function_exists("toimittajahintatarkista")) {
	function toimittajahintatarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto;

		static $chytunnus, $chtuoteno, $chryhma, $chminkpl, $chmaxkpl, $chalkupvm, $chloppupvm, $chastunn, $chtuoteno_ind;

		if (mysql_field_name($result, $i) == "hinta") {
			if (trim($t[$i]) == '') {
				$virhe[$i] = t("Tieto puuttuu!");
			}
		}

		if (mysql_field_name($result, $i) == "ytunnus") {
			$chytunnus = trim($t[$i]);

			// Ei tsekata turhaan kun rivi dellataan luedatasta
			if ($chytunnus != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
				$query = "	SELECT ytunnus
							FROM toimi
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi != 'P'
							and ytunnus = '$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) == 0) {
					$virhe[$i] = t("Ytunnuksella ei löydy toimittajaa!");
				}
			}
		}

		if (mysql_field_name($result, $i) == "toimittaja") {
			$chastunn = trim($t[$i]);

			// Ei tsekata turhaan kun rivi dellataan luedatasta
			if ($chastunn != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {

				unset($chasresult);

				if (is_numeric($chastunn) and $chastunn > 0) {
					$query	= "	SELECT *
								FROM toimi
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi != 'P'
								and tunnus = '$chastunn'";
					$chasresult = mysql_query($query) or pupe_error($query);
				}

				if (!isset($chasresult) or (isset($chasresult) and mysql_num_rows($chasresult) != 1)) {

					$ytunnus 		= $chastunn;
					$kutsuja 		= "yllapito.php";
					$ulos2 			= "";

					if ($ytunnus != "" and $ytunnus != "0") {
						require("inc/kevyt_toimittajahaku.inc");
					}

					if ($ulos2 != "" and $ytunnus == "") {
						$alasveto[$i] = "<select name='t[$i]'><option value=''>".t("Valitse toimittaja")."</option>".$ulos2."</select>";
						$virhe[$i]    = t("Valitse toimittaja!");
					}
					elseif ($ytunnus != "" and $toimittajaid > 0) {
						$t[$i]  = $toimittajaid;
						if (strpos($_SERVER['SCRIPT_NAME'], "lue_data.php") === FALSE) $virhe[$i] = t("toimittaja löytyi!");
					}
					else {
						$virhe[$i] = t("toimittaja puuttuu tai sitä ei löydy!");
					}
				}
			}
		}

		if (mysql_field_name($result, $i) == "tuoteno") {
			$chtuoteno = $t[$i];
			$chtuoteno_ind = $i;

			// Ei tsekata turhaan kun rivi dellataan luedatasta
			if ($chtuoteno != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
				$query = "	SELECT tuoteno
							FROM tuote
							WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) != 1) {

					if (substr($t[$i], -1) != '*') $tuoteno = $t[$i]."*";
					else $tuoteno = $t[$i];

					$kutsuja 		= "yllapito.php";
					$ulos			= "";

					require ("inc/tuotehaku.inc");

					if ($ulos != "") {
						$alasveto[$i] = "<select name='t[$i]'>".$ulos."</select>";
					}

					$virhe[$i] = t("Tuotenumero puuttuu tai sitä ei löydy!");
				}
			}
		}

		if (mysql_field_name($result, $i) == "ryhma") {
			$chryhma = $t[$i];

			// Ei tsekata turhaan kun rivi dellataan luedatasta
			if ($chryhma != '' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
				$query = "	SELECT tunnus
							FROM perusalennus
							WHERE yhtio='$kukarow[yhtio]' and ryhma = '$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) != 1) {
					$virhe[$i] = t("Aleryhmä puuttuu tai sitä ei löydy!");
				}
			}
		}

		if (mysql_field_name($result, $i) == 'tunnus') {

			$rajlask = 0;

			if ($chytunnus != '') $rajlask++;
			if ($chastunn > 0) $rajlask++;

			if ($rajlask > 1 or $rajlask == 0) {
				$virhe[$chtuoteno_ind] = $virhe[$i] = t("Valitse toimittaja tai ytunnus!");
			}
		}

		if ($chryhma == '' and $chtuoteno == '' and mysql_field_name($result, $i) == 'ryhma') {
			$virhe[$i] = t("Sinun on annettava joko tuote tai ryhmä!");
		}

		if (mysql_field_name($result, $i) == "minkpl") {
			$t[$i] = str_replace(",", ".", $t[$i]);
			$chminkpl = (int) $t[$i];
		}

		if (mysql_field_name($result, $i) == "maxkpl") {
			$t[$i] = str_replace(",", ".", $t[$i]);
			$chmaxkpl = (int) $t[$i];
		}

		if (mysql_field_name($result, $i) == "alkupvm") {
			$chalkupvm = $t[$i];

			if ($chalkupvm == '') {
				$chalkupvm = '0000-00-00';
			}

			// Ei tsekata turhaan kun rivi dellataan luedatasta
			if ($chalkupvm != '0000-00-00' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
				$pp = substr($chalkupvm, 8, 2);
				$kk = substr($chalkupvm, 5, 2);
				$vv = substr($chalkupvm, 0, 4);

				if (!checkdate($kk, $pp, $vv)) {
					$virhe[$i] = t("Alkupäivämäärä virheellinen!");
				}
			}
		}

		if (mysql_field_name($result, $i) == "loppupvm") {
			$chloppupvm = $t[$i];

			if ($chloppupvm == '') {
				$chloppupvm = '0000-00-00';
			}

			// Ei tsekata turhaan kun rivi dellataan luedatasta
			if ($chloppupvm != '0000-00-00' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
				$pp = substr($chloppupvm, 8, 2);
				$kk = substr($chloppupvm, 5, 2);
				$vv = substr($chloppupvm, 0, 4);

				if (!checkdate($kk, $pp, $vv)) {
					$virhe[$i] = t("Loppupäivämäärä virheellinen!");
				}
			}
		}

		if (($chytunnus != '' or $chastunn != '') and ($chryhma != '' or $chtuoteno != '') and mysql_field_name($result, $i) == 'tunnus' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {

			$and = '';

			if ($chytunnus != '') 		$and .= " and ytunnus = '$chytunnus'";
			if ($chastunn > 0) 			$and .= " and toimittaja = '$chastunn'";

			if ($chryhma != '') 		$and .= " and ryhma = '$chryhma'";
			if ($chtuoteno != '') 		$and .= " and tuoteno = '$chtuoteno'";

			$aquery = "	SELECT ytunnus
						FROM toimittajahinta
						WHERE yhtio='$kukarow[yhtio]'
						$and
						and alkupvm = '$chalkupvm'
						and loppupvm = '$chloppupvm'
						and minkpl = $chminkpl
						and maxkpl = $chmaxkpl
						and tunnus != '$trow[$i]'";
			$dsresult = mysql_query($aquery) or pupe_error($aquery);

			if (mysql_num_rows($dsresult) > 0) {
				// Pitää aina setata myös "tämän" kentän virhe, muuten luedata ei toimi
				$virhe[$chtuoteno_ind] = $virhe[$i] = t("VIRHE: Näillä tiedoilla on jo toimittajahinta järjestelmässä!");
			}
		}
	}
}

?>