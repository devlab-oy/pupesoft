<?php

if (!function_exists("puun_alkiotarkista")) {
	function puun_alkiotarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto, $table_tarkenne;
		static $tem_laji;

		if (mysql_field_name($result, $i) == "laji") {
			$tem_laji = $t[$i];

			if ($t[$i] == '') {
				$virhe[$i] = t("Lajia ei voi jättää tyhjäksi")."!";
			}
		}

		if (mysql_field_name($result, $i) == "puun_tunnus") {
			if ($t[$i] == '') {
				$virhe[$i] = t("Puun tunnusta ei voi jättää tyhjäksi")."!";
			}
		}

		if ((strtoupper($tem_laji) == 'ASIAKAS' or strtoupper($table_tarkenne) == 'ASIAKAS') and mysql_field_name($result, $i) == "liitos") {
			$query = "	SELECT tunnus
						FROM asiakas
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND laji != 'P'
						AND tunnus = '$t[$i]'";
			$asiakkaan_haku_res = pupe_query($query);

			if (mysql_num_rows($asiakkaan_haku_res) == 0) {
				$virhe[$i] = t("Tunnuksella ei löydy asiakasta")."!";
			}
		}
	}
}
