<?php

if(!function_exists("toimittajaalennustarkista")) {
	function toimittajaalennustarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto, $toim;

		static $chytunnus, $chasiakas_ryhma, $chtuoteno, $chryhma, $chminkpl, $chmonikerta, $chalkupvm, $chloppupvm, $chastunn, $chtuoteno_ind, $chsegmentti, $chpiiri, $chalennuslaji;

		if (mysql_field_name($result, $i) == "alennus") {
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
					$virhe[$i] = t("Ytunnuksella ei löydy asiakasta!");
				}
			}
		}


		if (mysql_field_name($result, $i) == "toimittaja") {
			$chastunn = trim($t[$i]);

			// Ei tsekata turhaan kun rivi dellataan luedatasta
			if (($chastunn != '') and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {
				
				unset($result);

				if (is_numeric($chastunn) and $chastunn > 0) {
					$query	= "	SELECT *
								FROM toimi
								WHERE yhtio = '$kukarow[yhtio]'
								and tyyppi != 'P'
								and tunnus = '$chastunn'";
					$result = mysql_query($query) or pupe_error($query);
					
				}

				if (!isset($result) or (isset($result) and mysql_num_rows($result) != 1)) {

					$ytunnus 		= $chastunn;
					$kutsuja 		= "yllapito.php";
					$ulos2 			= "";

					if ($ytunnus != "" and $ytunnus != "0") {
						require("kevyt_toimittajahaku.inc");
					}

					if ($ulos2 != "" and $ytunnus == "") {
						$alasveto[$i] = "<select name='t[$i]'><option value=''>".t("Valitse asiakas")."</option>".$ulos2."</select>";
						$virhe[$i]    = t("Valitse asiakas!");
					}
					elseif ($ytunnus != "") {
						$t[$i]  = $toimittajaid;
						if (strpos($_SERVER['SCRIPT_NAME'], "lue_data.php") === FALSE) $virhe[$i] = t("Asiakas löytyi!");
					}
					else {
						$virhe[$i] = t("Asiakas puuttuu tai sitä ei löydy!");
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

					require ("tuotehaku.inc");

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



		if (mysql_field_name($result, $i) == "minkpl") {
			$t[$i] = str_replace(",", ".", $t[$i]);
			$chminkpl = (int) $t[$i];
		}

		if (mysql_field_name($result, $i) == "monikerta") {
			$t[$i] = trim($t[$i]);
			$chmonikerta = $t[$i];
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

		if (mysql_field_name($result, $i) == "alennuslaji") {
			$chalennuslaji = (int) $t[$i];

			// Ei tsekata turhaan kun rivi dellataan luedatasta
			if (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA") {
				if ($chalennuslaji < 1 or $chalennuslaji > $yhtiorow['myynnin_alekentat']) {
					$virhe[$i] = t("Virheellinen alennuslaji!");
				}
			}
		}

		// Ei tsekata turhaan kun rivi dellataan luedatasta
		if (($chytunnus != ''  or $chastunn != '') and ($chryhma != '' or $chtuoteno != '') and mysql_field_name($result, $i) == 'tunnus' and (!isset($trow["luedata_toiminto"]) or $trow["luedata_toiminto"] != "POISTA")) {

			$and = '';

			if ($chytunnus != '') 		$and .= " and ytunnus = '$chytunnus'";
			if ($chastunn > 0) 			$and .= " and toimittaja = '$chastunn'";
			if ($chryhma != '') 		$and .= " and ryhma = '$chryhma'";
			if ($chtuoteno != '') 		$and .= " and tuoteno = '$chtuoteno'";

			$aquery = "	SELECT ytunnus
						FROM toimittajaalennus
						WHERE yhtio		= '$kukarow[yhtio]'
						$and
						and alkupvm 	= '$chalkupvm'
						and loppupvm 	= '$chloppupvm'
						and minkpl 		= $chminkpl
						and monikerta	= '$chmonikerta'
						and alennuslaji = $chalennuslaji
						and tunnus	   != '$trow[$i]'";
			$dsresult = mysql_query($aquery) or pupe_error($aquery);

			if (mysql_num_rows($dsresult) > 0) {
				// Pitää aina setata myös "tämän" kentän virhe, muuten luedata ei toimi
				$virhe[$chtuoteno_ind] = $virhe[$i] = t("VIRHE: Näillä tiedoilla on jo toimittajaalennus järjestelmässä!");
			}
		}
	}
}

?>