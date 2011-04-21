<?php

if(!function_exists("tuotteen_avainsanattarkista")) {
	function tuotteen_avainsanattarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $alasveto;

		static $chtuoteno, $lajitalteen, $kielitalteen;

		if (mysql_field_name($result, $i) == "tuoteno") {
			$chtuoteno = $t[$i];

			if ($chtuoteno != '') {
				$query = "	SELECT tuoteno
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]'
							AND tuoteno = '$t[$i]'";
				$sresult = pupe_query($query);

				if (mysql_num_rows($sresult) != 1) {

					if (substr($t[$i], -1) != '*') $tuoteno = $t[$i]."*";
					else $tuoteno = $t[$i];

					$kutsuja 		= "yllapito.php";
					$ulos			= "";

					require ("inc/tuotehaku.inc");

					if ($ulos != "") {
						$alasveto[$i] = "<select name='t[$i]'>".$ulos."</select>";
					}

					$virhe[$i] = t("Tuotenumeroa ei löydy!");
				}
			}
			else {
				$virhe[$i] = t("Tuotenumero puuttuu!");
			}
		}

		if (mysql_field_name($result, $i) == "kieli") {
			if ($t[$i] == '') {
				$virhe[$i] = t("Kieltä ei voi jättää tyhjäksi")."!";
			}
			else {
				$query  = "SHOW columns from sanakirja";
				$fields =  pupe_query($query);

				$loytyyko = FALSE;

				while ($apurow = mysql_fetch_array($fields)) {
					if (strtoupper($apurow[0]) == strtoupper($t[$i])) {
						$loytyyko = TRUE;
					}
				}
				
				if (!$loytyyko) {
					$virhe[$i] = t("Virheellinen kieli")."!";
				}
				else {
					$kielitalteen = $t[$i];
				} 
			}						
		}

		if (mysql_field_name($result, $i) == "laji") {
			if ($t[$i] == '') {
				$virhe[$i] = t("Lajia ei voi jättää tyhjäksi")."!";
			}

			// Katsotaan, että samaa avainsanaa ei ole jo perustettu!
			$chtunnari = (int) $tunnus;

			$query = "	SELECT yhtio, tuoteno, laji, kieli
						FROM tuotteen_avainsanat
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$chtuoteno'
						and kieli	= '$kielitalteen'
						and laji 	= '$t[$i]'
						and tunnus != $chtunnari";
			$sresult = pupe_query($query);

			if (mysql_num_rows($sresult) > 0) {
				$virhe[$i] = t("Tuotteen avainsana löytyy jo").": $t[$i]/$kielitalteen!";
			}

			$lajitalteen = $t[$i];
		}

		if (mysql_field_name($result, $i) == "selite") {
			if ($lajitalteen == "SISTOIMITTAJA") {
				// salitaan vaan konserniyhtiö joka ei ole me itse
				$query = "	SELECT *
							FROM yhtio
							WHERE yhtio = '$t[$i]' and konserni='$yhtiorow[konserni]' and yhtio!='$kukarow[yhtio]'";
				$sresult = pupe_query($query);

				if (mysql_num_rows($sresult) == 0) {
					$virhe[$i] = t("Yhtiötä ei löydy. Skarppaa!");
				}
			}
			elseif (strtoupper($lajitalteen) == "OLETUSVALINTA") {
				if(!in_array(substr($t[$i], 0, 3), array("", "!!!", "¡¡¡", "¡¡V"))) {
					$virhe[$i] = t("Tuotteen %s oletusvalinta %s ei kelpaa!", "", $chtuoteno, $t[$i]);
				}
			}

			// jos kyseessä on tuotteen parametri
			if (substr(strtoupper($lajitalteen), 0, 10) == "PARAMETRI_") {
				$query = "	SELECT tunnus
							from tuotteen_avainsanat
							where yhtio = '$kukarow[yhtio]'
							and laji = '$lajitalteen'
							and kieli = '$kielitalteen'
							and tuoteno = '$chtuoteno'
							and tunnus != '$tunnus'";
				$sresult = pupe_query($query);

				if (mysql_num_rows($sresult) > 0) {
					$virhe[$i] = t("Tuotteella on jo parametri"). " ". substr($lajitalteen, 10);
				}
			}

			if (strtoupper($lajitalteen) == "PARAMETRI_VARIAATIO_JAKO") {
				//Onko meillä moniulotteinen variaatio esim. väri ja koko...
				$variaatiot = explode(",", $t[$i]);

				foreach ($variaatiot as $variaatio) {
					$query = "	SELECT tunnus
								from tuotteen_avainsanat
								where yhtio = '$kukarow[yhtio]'
								and kieli = '$yhtiorow[kieli]'
								and laji = 'parametri_$variaatio'
								and tuoteno = '$chtuoteno'";
					$sresult = pupe_query($query);

					if (mysql_num_rows($sresult) != 1) {
						$virhe[$i] .= t("Tuotteella ei ole parametriä"). " '". $variaatio."'";
					}
					if ($variaatio == "variaatio" or $variaatio == "variaatio_jako") {
						$virhe[$i] .= t("Variaatio jako ei voi olla variaatio eikä variaatio_jako"). "!";
					}
				}
			}
		}
	}
}

?>