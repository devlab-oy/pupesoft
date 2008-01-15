<?php

if(!function_exists("asiakasalennustarkista")) {
	function asiakasalennustarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set;
		
		static $chytunnus, $chasiakas_ryhma, $chtuoteno, $chryhma, $chalkupvm, $chloppupvm;
		
		if (mysql_field_name($result, $i) == "tunnus") {
			$tunnus = $t[$i];
		}

		if (mysql_field_name($result, $i) == "alennus") {
			if (trim($t[$i]) == '') {
				$virhe[$i] = "".t("Tieto puuttuu!")."";
			}
		}

		if (mysql_field_name($result, $i) == "ytunnus") {
			$chytunnus = $t[$i];
			if ($chytunnus != '') {
				$query = "	SELECT ytunnus
							FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and ytunnus = '$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($sresult) == 0) {
					$virhe[$i] = "".t("Asiakas puuttuu tai sitä ei löydy!")."";
				}
			}
		}

		if (mysql_field_name($result, $i) == "asiakas_ryhma") {
			$chasiakas_ryhma = $t[$i];
			if ($chasiakas_ryhma != '') {
				$query = "	SELECT tunnus
							FROM avainsana
							WHERE yhtio='$kukarow[yhtio]' and laji = 'ASIAKASRYHMA' and selite = '$chasiakas_ryhma'";
				$sresult = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($sresult) == 0) {
					$virhe[$i] = "".t("Asiakasryhmä puuttuu tai sitä ei löydy!")."";
				}
			}
		}

		if (mysql_field_name($result, $i) == "tuoteno") {
			$chtuoteno = $t[$i];
			if ($chtuoteno != '') {
				$query = "	SELECT tuoteno
							FROM tuote
							WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$t[$i]'";
				$sresult = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($sresult) != 1) {
					$virhe[$i] = "".t("Tuotenumero puuttuu tai sitä ei löydy!")."";
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
					$virhe[$i] = "".t("Aleryhmä puuttuu tai sitä ei löydy!")."";
				}
			}
		}



		if ($chasiakas_ryhma != '' and $chytunnus != '' and mysql_field_name($result, $i) == 'asiakas_ryhma') {
			$virhe[$i] = "".t("Et voi syöttää sekä asiakasta että asiakkaan ryhmää!")."";
		}

		if ($chryhma != '' and $chtuoteno != '' and mysql_field_name($result, $i) == 'ryhma') {
			$virhe[$i] = "".t("Et voi syöttää sekä tuotenumeroa että ryhmää!")."";
		}

		if ($chasiakas_ryhma == '' and $chytunnus == '' and mysql_field_name($result, $i) == 'asiakas_ryhma') {
			$virhe[$i] = "".t("Sinun on annettava joko asiakas tai asiakkaan ryhmä!")."";
		}

		if ($chryhma == '' and $chtuoteno == '' and mysql_field_name($result, $i) == 'ryhma') {
			$virhe[$i] = "".t("Sinun on annettava joko tuote tai ryhmä!")."";
		}



		if (mysql_field_name($result, $i) == "alkupvm") {
			$chalkupvm = $t[$i];
			if ($chalkupvm == '') {
				$chalkupvm = '0000-00-00';
			}
		}

		if (mysql_field_name($result, $i) == "loppupvm") {
			$chloppupvm = $t[$i];
			if ($chloppupvm == '') {
				$chloppupvm = '0000-00-00';
			}
		}



		if (($chasiakas_ryhma != '' or $chytunnus != '') and ($chryhma != '' or $chtuoteno != '') and mysql_field_name($result, $i) == 'loppupvm') {
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
						FROM asiakasalennus
						WHERE yhtio='$kukarow[yhtio]'$and and alkupvm = '$chalkupvm' and loppupvm = '$chloppupvm' and tunnus != '$tunnus'";
			$dsresult = mysql_query($aquery) or pupe_error($aquery);

			if (mysql_num_rows($dsresult) > 0) {
				$virhe[$i] = t("VIRHE: Näillä tiedoilla on jo asiakasalennus järjestelmässä!");
			}						
		}
		
	}
}
	
?>
