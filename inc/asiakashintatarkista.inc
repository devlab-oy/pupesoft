<?php

if(!function_exists("asiakashintatarkista")) {
	function asiakashintatarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto;

		static $chytunnus, $chasiakas_ryhma, $chtuoteno, $chryhma, $chminkpl, $chmaxkpl, $chalkupvm, $chloppupvm, $chastunn, $chtuoteno_ind;

		if (mysql_field_name($result, $i) == "hinta") {
			if (trim($t[$i]) == '') {
				$virhe[$i] = t("Tieto puuttuu!");
			}
		}

		if (mysql_field_name($result, $i) == "ytunnus") {
			$chytunnus = trim($t[$i]);

			if ($chytunnus != '') {
				$query = "	SELECT ytunnus
							FROM asiakas
							WHERE yhtio='$kukarow[yhtio]'
							and laji != 'P'
							and ytunnus = '$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) == 0) {

					$ytunnus 		= $chytunnus;
					$kutsuja 		= "yllapito.php";
					$ulos 			= "";
					require("asiakashaku.inc");

					if ($ulos != "" and $ytunnus == "") {
						$alasveto[$i] = "<select name='t[$i]'><option value=''>".t("Valitse asiakas")."</option>".$ulos."</select>";
						$virhe[$i]    = t("Valitse asiakas!");
					}
					elseif ($ytunnus != "") {
						$t[$i]  = $ytunnus;
						if (strpos($_SERVER['SCRIPT_NAME'], "lue_data.php") === FALSE) $virhe[$i] = t("Asiakas löytyi!");
					}
					else {
						$virhe[$i] = t("Asiakas puuttuu tai sitä ei löydy!");
					}
				}
			}
		}

		if (mysql_field_name($result, $i) == "asiakas") {
			$chastunn = trim($t[$i]);

			if ((int) $chastunn == 0) {
				$chastunn = "";
			}

			if ($chastunn != '') {

				unset($result);

				if (is_numeric($chastunn)) {
					$query	= "	SELECT *
								FROM asiakas
								WHERE yhtio = '$kukarow[yhtio]'
								and laji != 'P'
								and tunnus = '$chastunn'";
					$result = mysql_query($query) or pupe_error($query);
				}

				if (!isset($result) or mysql_num_rows($result) != 1) {

					$ytunnus 		= $chastunn;
					$kutsuja 		= "yllapito.php";
					$ulos2 			= "";
					require("asiakashaku.inc");

					if ($ulos2 != "" and $ytunnus == "") {
						$alasveto[$i] = "<select name='t[$i]'><option value=''>".t("Valitse asiakas")."</option>".$ulos2."</select>";
						$virhe[$i]    = t("Valitse asiakas!");
					}
					elseif ($ytunnus != "") {
						$t[$i]  = $asiakasid;
						if (strpos($_SERVER['SCRIPT_NAME'], "lue_data.php") === FALSE) $virhe[$i] = t("Asiakas löytyi!");
					}
					else {
						$virhe[$i] = t("Asiakas puuttuu tai sitä ei löydy!");
					}
				}
			}
		}

		if (mysql_field_name($result, $i) == "asiakas_ryhma") {
			$chasiakas_ryhma = trim($t[$i]);

			if ($chasiakas_ryhma != '') {
				$sresult = t_avainsana("ASIAKASRYHMA", "", "and avainsana.selite = '$chasiakas_ryhma'");

				if (mysql_num_rows($sresult) == 0) {
					$virhe[$i] = t("Asiakasryhmä puuttuu tai sitä ei löydy!");
				}
			}
		}

		if (mysql_field_name($result, $i) == "tuoteno") {
			$chtuoteno = $t[$i];
			$chtuoteno_ind = $i;

			if ($chtuoteno != '') {
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
			if ($chryhma != '') {
				$query = "	SELECT tunnus
							FROM perusalennus
							WHERE yhtio='$kukarow[yhtio]' and ryhma = '$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) != 1) {
					$virhe[$i] = t("Aleryhmä puuttuu tai sitä ei löydy!");
				}
			}
		}

		$rajlask = 0;
		if ($chasiakas_ryhma != '') $rajlask++;
		if ($chytunnus != '') $rajlask++;
		if ((int) $chastunn > 0) $rajlask++;

		if ($rajlask > 1 and $chasiakas_ryhma != "" and mysql_field_name($result, $i) == 'asiakas_ryhma') {
			$virhe[$i] = t("Et voi syöttää sekä asiakasta että asiakkaan ryhmää!");
		}

		if ($chryhma != '' and $chtuoteno != '' and mysql_field_name($result, $i) == 'ryhma') {
			$virhe[$i] = t("Et voi syöttää sekä tuotenumeroa että ryhmää!");
		}

		if ($chasiakas_ryhma == '' and $chytunnus == '' and $chastunn == '' and mysql_field_name($result, $i) == 'asiakas_ryhma') {
			$virhe[$i] = t("Sinun on annettava joko asiakas tai asiakkaan ryhmä!");
		}

		if ($chryhma == '' and $chtuoteno == '' and mysql_field_name($result, $i) == 'ryhma') {
			$virhe[$i] = t("Sinun on annettava joko tuote tai ryhmä!");
		}

		if (mysql_field_name($result, $i) == "minkpl") {

			$t[$i] = str_replace(",", ".", $t[$i]);

			$chminkpl = $t[$i];

			if ($chminkpl == '') {
				$chminkpl = 0;
			}
		}

		if (mysql_field_name($result, $i) == "maxkpl") {

			$t[$i] = str_replace(",", ".", $t[$i]);

			$chmaxkpl = $t[$i];

			if ($chmaxkpl == '') {
				$chmaxkpl = 0;
			}
		}

		if (mysql_field_name($result, $i) == "alkupvm") {
			$chalkupvm = $t[$i];

			if ($chalkupvm == '') {
				$chalkupvm = '0000-00-00';
			}
			elseif ($chalkupvm != '0000-00-00') {
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
			elseif ($chloppupvm != '0000-00-00') {
				$pp = substr($chloppupvm, 8, 2);
				$kk = substr($chloppupvm, 5, 2);
				$vv = substr($chloppupvm, 0, 4);

				if (!checkdate($kk, $pp, $vv)) {
					$virhe[$i] = t("Loppupäivämäärä virheellinen!");
				}
			}
		}

		if (($chasiakas_ryhma != '' or $chytunnus != '') and ($chryhma != '' or $chtuoteno != '') and mysql_field_name($result, $i) == 'tunnus') {

			$and = '';
			if ($chasiakas_ryhma != '') {
				$and .= " and asiakas_ryhma = '$chasiakas_ryhma'";
			}
			if ($chytunnus != '') {
				$and .= " and ytunnus = '$chytunnus'";
			}
			if ($chryhma != '') {
				$and .= " and ryhma = '$chryhma'";
			}
			if ($chtuoteno != '') {
				$and .= " and tuoteno = '$chtuoteno'";
			}

			$aquery = "	SELECT ytunnus
						FROM asiakashinta
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
				$virhe[$chtuoteno_ind] = $virhe[$i] = t("VIRHE: Näillä tiedoilla on jo asiakashinta järjestelmässä!");
			}
		}
	}
}

?>