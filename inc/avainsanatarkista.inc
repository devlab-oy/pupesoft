<?php

if(!function_exists("avainsanatarkista")) {
	function avainsanatarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $ulosarray, $varastot_array;

		static $seliteerrori, $laji;

		if (mysql_field_name($result, $i) == "selite" and trim($t[$i]) == '') {
			$seliteerrori = 1;
		}

		if (mysql_field_name($result, $i) == "laji") {
			$laji = trim($t[$i]);
		}

		if (mysql_field_name($result, $i) == "selite" and strtoupper($laji) == "ALVULK") {
			//	ALV pitää olla pisteellä, ei pilkulla
			$t[$i] = round((float) str_replace(",", ".", $t[$i]), 2);
		}

		if (mysql_field_name($result, $i) == "selite" and strlen($t[$i]) > 3 and $laji == 'Y') {
			$virhe[$i] = t("Selite on liian pitkä, maksimiputuus on kolme merkkiä")."!";
		}

		if (mysql_field_name($result, $i) == "selite" and !is_numeric($t[$i]) and in_array($laji, array("TRY", "OSASTO", "PIIRI"))) {
			$virhe[$i] = t("Selitteen on oltava numeerinen")."!";
		}

		if (mysql_field_name($result, $i) == "selite" and $laji == 'VAKIOVIESTI' and trim($t[$i]) == '') {
			$virhe[$i] = t("Selite on pakollinen");
		}

		if ($seliteerrori == 1 and mysql_field_name($result, $i) == "selitetark" and trim($t[$i]) == '' and $laji != 'VAKIOVIESTI') {
			$virhe[$i] = t("Selite ja selitetark ei voi olla tyhjiä")."!";
		}

		if (mysql_field_name($result, $i) == "selite" and $laji == "PARAMETRI") {
			preg_match_all('/([a-zA-Z_])+/', $t[$i], $match);

			if ($match[0][0] != $t[$i]) {
				$virhe[$i] = t("Vain aakkoset ja alaviivat ovat sallittuja merkkejä");
			}
		}

		if (mysql_field_name($result, $i) == 'selitetark' and $laji == "VARASTORYHMA" and !is_array($varastot_array) and count($varastot_array) == 0) {
			$virhe[$i] = t("Et valinnut yhtään varastoa")."!";
		}
		elseif (mysql_field_name($result, $i) == 'selitetark' and $laji == 'VARASTORYHMA') {
			if (is_array($varastot_array) and count($varastot_array) > 0) {
				$varastot = '';
				foreach($varastot_array as $varasto_apu) {
					$varastot .= "$varasto_apu,";
				}
				$varastot = substr($varastot, 0, -1);
				$t[$i] = $varastot;
			}
		}
	}
}

?>