<?php

	if ((mysql_field_name($result, $i) == "nimi") or
		(mysql_field_name($result, $i) == "maakoodi") or
		(mysql_field_name($result, $i) == "ytunnus") or
		(mysql_field_name($result, $i) == "pankkinimi1")) {
		if (trim($t[$i]) == '') {
			$virhe[$i] = "".t("Tieto puuttuu")."";
			$errori = 1;
		}
		if (mysql_field_name($result, $i) == "maakoodi") {
			$t[$i] = strtoupper($t[$i]);
			$tmp_maakoodi = $t[$i];
		}
	}
	
	if (mysql_field_name($result, $i) == "tilino") {
		if ($t[$i] != '') {
			$query = "	SELECT nimi
				FROM tili
				WHERE yhtio='$kukarow[yhtio]' and tilino = '$t[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($sresult) != 1) {
				$virhe[$i] = "".t("Tilia ei loydy")."";
				$errori = 1;
			}
		}
	}
	
	if (mysql_field_name($result, $i) == "tilinumero") {
		if ($tmp_maakoodi == strtoupper($yhtiorow['maakoodi'])) {
			if ((int)trim($t[$i]) != 0) {
				$pankkitili = $t[$i];
				if ($tmp_maakoodi == 'FI') {
					require 'pankkitilinoikeellisuus.php';
					if ($pankkitili == '') {
						$virhe[$i] = "".t("Pankkitili on virheellinen")."";
						$errori = 1;
					}
					else {
						$t[$i]=$pankkitili;
					}
				}
			}
			else {
				$virhe[$i] = "".t("Tieto puuttuu")."";
				$errori = 1;
			}
		}
	}
	
	if (mysql_field_name($result, $i) == "ultilno") {
		if ($tmp_maakoodi != strtoupper($yhtiorow['maakoodi'])) {
			if ($t[$i] == '') {
				$virhe[$i] = "".t("Tieto puuttuu")."";
				$errori = 1;
			}
		}
		//Vaaditaan isot kirjaimet
		$t[$i] = strtoupper($t[$i]);
	}

	if (mysql_field_name($result, $i) == "swift") {
		//Vaaditaan isot kirjaimet
		$t[$i] = strtoupper($t[$i]);
	}

	if ((mysql_field_name($result, $i) == "ytunnus") and (trim($t[$i])!='')) {
		$ytunnus = trim($t[$i]);
		
		if ($tmp_maakoodi=='FI') {

			$hetu = $ytunnus; // hetu talteen
			require ("tarkistaytunnus.inc");

			if ($ok==0) {
				
				// katotaan oliko kyseessä hetu
				require ("tarkistahetu.inc");
				
				// ei ollu oikee hetukaan
				if ($hetuok == 1) {
					$virhe[$i] = t("Virheellinen ytunnus/hetu!")." $t[$i] -> $ytunnus. ".t("Voit tarkistaa ytunnuksen")." <a target='uusiytj' href='http://www.ytj.fi/yrit_sel2.asp?kielikoodi=1'>".t("täältä")."</a>.";
					$errori = 1;
				}
			}
			else {
				$t[$i] = $ytunnus;
			}
		}
		else { // ulkomaalainen saa olla mitä se haluaa
			$t[$i] = $ytunnus;
		}
		$tmp_ytunnus=$t[$i];
	}
	
	if (mysql_field_name($result, $i) == "ovttunnus") {
		if ($tmp_maakoodi=='FI') {
			if (trim($t[$i])=='') {
				// täytetään ovttunnus jos sitä ei ole
				$t[$i] = "0037". sprintf("%08.0f",$tmp_ytunnus);
			}
			
			$query   = "select ytunnus, tunnus from toimi where yhtio='$kukarow[yhtio]' and ovttunnus='$t[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);
			
			if ($tunnus == "") { // ollaan lisäämässä uutta
				$raja=0;
			}
			else {
				$raja=1;
			}
			
			if (mysql_num_rows($sresult)>0) {
				$srowapu = mysql_fetch_array($sresult);
				// ei löydetty itteämme
				if ($srowapu['tunnus'] != $tunnus) {
					$virhe[$i] = "".t("Samalla ytunnuksella on useita toimittajia! Lisää tarkenne ovt-tunnkseen.")."";
					$errori = 1;
				}
			}
		}
	}
	
	if (mysql_field_name($result, $i) == "email" and trim($t[$i]) != '') {
		// katotaan löytyykö email osoitteen domain...
		list($nimi, $domain) = split("@", trim($t[$i]));

		if ($domain != "") {
			exec("host -t MX $domain | grep \"not found\"", $output);
		}

		if (count($output) > 0 or $domain == "") {
			$virhe[$i] = t("Sähköpostin domain ei löydy")."! ($domain)";
			$errori = 1;
		}

	}
	
	if (mysql_field_name($result, $i) == "tyyppi") {
		$toimtyyppi = $t[$i]; // otetaan tää talteen
	}
	
	if (mysql_field_name($result, $i) == "tyyppi_tieto") {
		
		if ($toimtyyppi == "S") {
			$query = "select * from yhtio where konserni='$yhtiorow[konserni]' and yhtio='$t[$i]' and yhtio!='$kukarow[yhtio]'";
			$tyychkre = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tyychkre) == 0) {
				$virhe[$i] = "Väärä yhtiötunnus: $t[$i]!";
				$errori = 1;
			}
		}

		if ($toimtyyppi == "O" and trim($t[$i]) == "") {
			$virhe[$i] = "Tieto puuttuu!";
			$errori = 1;
		}

	}

?>
