<?php

// näin kuollaan mysql errorista...
if (!function_exists("pupe_error")) {
	// otetaan parametriksi query
	function pupe_error($query) {
		// tarvitaan yhtiörowta ja kukarowta
		global $yhtiorow, $kukarow, $toim;

		// trimmataan, tabit, rivinvaihdot ja tuplaspacet pois querystä..
		$query = trim($query);
		$query = str_replace("\t", " ",$query);
		$query = str_replace("\n", "", $query);
		$query = str_replace("\r", "", $query);
		$query = ereg_replace("  +", " ", $query);

		$debuggi = array_reverse(debug_backtrace());

		// tehdään errorimessage
		$puperror  = "User:   {$kukarow["nimi"]} ({$kukarow["kuka"]}) @ {$yhtiorow["nimi"]} ({$yhtiorow["yhtio"]})\n\n";

		$nro = 1;
		foreach ($debuggi as $debuggii) {
			$puperror .= "File $nro:   {$debuggii["file"]}\n";
			$puperror .= "Line $nro:   {$debuggii["line"]}\n";
			$nro++;
		}

		$puperror .= "\n";
		$puperror .= "Script: {$_SERVER["PHP_SELF"]}\n";
		$puperror .= "Toim:   $toim\n\n";
		$puperror .= "Error:  ".mysql_error()."\n\n";
		$puperror .= "$query\n\n";

		// lähetetään se meilitse adminille
		mail($yhtiorow['admin_email'], $yhtiorow['nimi']." - SQL Error", $puperror, "From: $yhtiorow[postittaja_email]\n", "-f $yhtiorow[postittaja_email]");

		// kuollaan pois
		exit(nl2br($puperror));
	}
}

if (!function_exists("tv1dateconv")) {
	function tv1dateconv($date, $pitka = "") {

		global $laskurow;

		//kääntää mysqln vvvv-kk-pp muodon muotoon pp.kk.vvvv
		//2007-05-09 12:18:18
		if (strlen($date) > 10 and $pitka != "") {
			$jatko = substr($date,10, 6);
		}
		else {
			$jatko = "";
		}

		if ($date == "0000-00-00" or $date == "0000-00-00 00:00:00") {
			return "";
		}
		elseif ($laskurow["maa"] == "SE") {
			return substr($date,0,4)."-".substr($date,5,2)."-".substr($date,8,2).$jatko;
		}
		else {
			return substr($date,8,2).".".substr($date,5,2).".".substr($date,0,4).$jatko;
		}

	}
}

if (!function_exists("tv2dateconv")) {
	function tv2dateconv($date) {
		//kääntää mysqln vvvv-kk-pp muodon muotoon vvvvkkpp
		return substr($date,0,4).substr($date,5,2).substr($date,8,2);
	}
}

if (!function_exists("dateconv")) {
	function dateconv ($date) {
		//kääntää vvkkmm muodon muotoon vv-kk-mm
		return substr($date,0,2). "-" . substr($date,2,2) . "-". substr($date,4,2);
	}
}

if (!function_exists("t")) {
	function t($stringi, $kieli = "") {
		// tarvitaan yhtiörowta, kukarowta ja tieto slaven:n käytöstä
		global $yhtiorow, $kukarow, $useslave, $link;

		require("salasanat.php");

		if (isset($slavedb)) {
			if ($slavedb[$useslave]=='') $useslave = 0;
		}
		else {
			$useslave = 0;
		}

		if ($useslave > 0) { //Voi olla, että käytämme slavea ja INSERT ei siis onnistu
			$link = mysql_connect ($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
			mysql_select_db ($dbkanta) or die ("Tietokantaa $dbkanta löydy palvelimelta (functions.inc 1)!");
		}

		if (trim($kieli) != '') {
			$indeksi = trim(strtolower($kieli));
		}
		elseif (trim($kukarow["kieli"]) != '') {
			$indeksi = trim(strtolower($kukarow["kieli"]));
		}
		else {
			$indeksi = "fi";
		}

		if ($indeksi != 'fi') {
			$sanakirjaquery  = "SELECT fi, $indeksi, tunnus FROM sanakirja WHERE fi = BINARY '$stringi'";
			$sanakirjaresult = mysql_query($sanakirjaquery, $link) or pupe_error($sanakirjaquery);

			if (mysql_num_rows($sanakirjaresult) > 0) {
				$sanakirjarow = mysql_fetch_array($sanakirjaresult);

				if (isset($sanakirjarow[$indeksi]) and trim($sanakirjarow[$indeksi]) != '') {
					$stringi = $sanakirjarow[$indeksi];
				}
				else {
					$stringi = $sanakirjarow["fi"];
				}

				//Päivitetään aikaleima
				$sanakirjaqueryupd  = "UPDATE sanakirja SET aikaleima=now(), kysytty=kysytty+1 WHERE tunnus='$sanakirjarow[tunnus]'";
				$sanakirjaresultupd = mysql_query($sanakirjaqueryupd, $link) or pupe_error($sanakirjaqueryupd);
			}
			else {
				$sanakirjaquery  = "INSERT INTO sanakirja SET fi = '$stringi', aikaleima=now(), kysytty=1, laatija='$kukarow[kuka]', luontiaika=now()";
				$sanakirjaresult = mysql_query($sanakirjaquery, $link) or pupe_error($sanakirjaquery);
			}
		}

		if ($useslave > 0) { //Palautetaan slave käyttöön
			$link = mysql_connect ($slavedb[$useslave], $slaveuser[$useslave], $slavepass[$useslave]) or die("Ongelma tietokantapalvelimessa: '$slavedb[$useslave]'");
			mysql_select_db ($dbkanta) or die ("Tietokantaa $dbkanta löydy palvelimelta (functions.inc 2)!");
		}

		//	Palautetaan muotoiltu stringi!!!
		if(func_num_args()>2) {
		    $arg = func_get_args();
			return sprintf($stringi, $arg[2],$arg[3],$arg[4],$arg[5],$arg[6]);
		}
		else {
			return $stringi;
		}
	}
}

if (!function_exists("maa")) {
	function maa($code, $kieli="") {

		$query = sprintf("SELECT nimi FROM maat where koodi = '%s' LIMIT 1", mysql_real_escape_string(substr($code, 0 ,2)));
		$res = mysql_query($query) or pupe_error($query);
		$maa = mysql_fetch_array($res);

		// otetaan pois maa koodi
		if($kieli=="") {
			$kieli = $GLOBALS["kieli"];
		}

		return ucfirst(strtolower(substr(t($maa['nimi'], $kieli), 5)));
	}
}

if (!function_exists("alias")) {
	function alias($stringi, $taulu, $setti = '') {

		// tarvitaan yhtiörowta, kukarowta
		global $yhtiorow, $kukarow;

		$hakustringi = $taulu.".".$stringi;

		$aliasquery  = "SELECT selitetark FROM avainsana WHERE yhtio = '$kukarow[yhtio]' and selitetark_2 = '$setti' and selite = '$hakustringi'";
		$aliasresult = mysql_query($aliasquery) or pupe_error($aliasquery);

		if (mysql_num_rows($aliasresult) > 0) {
			$aliasrow = mysql_fetch_array($aliasresult);

			$stringi = $aliasrow['selitetark'];

		}

		$stringi = t($stringi);

		return $stringi;
	}
}

if (!function_exists("kuuluukovarastoon")) {
	function kuuluukovarastoon($hyllyalue, $hyllynro, $varasto = '', $yhtio = '') {
		global $kukarow, $yhtiorow;

		$varastolisa = "";

		// voidaan zekata onko varastoalue jossain tietyssä varastossa...
		if ($varasto != "") {
			$varastolisa = " and tunnus='$varasto'";
		}

		//Jos yhtiö tulee parametrinä niin katsotaa, ettei se ole ihan mitä sattuu
		if ($yhtio != "") {
			$query	= "	SELECT GROUP_CONCAT(distinct yhtio) yhtiot
						from yhtio
						where yhtio='$kukarow[yhtio]' or (konserni = '$yhtiorow[konserni]' and konserni != '')";
			$pres = mysql_query($query) or pupe_error($query);
			$prow = mysql_fetch_array($pres);

			$yhtiot = explode(",", $prow["yhtiot"]);

			if (in_array($yhtio, $yhtiot)) {
				$yhtiolisa = $yhtio;
			}
			else {
				$yhtiolisa = $kukarow["yhtio"];
			}
		}
		else {
			$yhtiolisa = $kukarow["yhtio"];
		}

		$query = "	SELECT tunnus
					FROM varastopaikat
					WHERE
					concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper('$hyllyalue'), 5, '0'),lpad(upper('$hyllynro'), 5, '0')) and
					concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper('$hyllyalue'), 5, '0'),lpad(upper('$hyllynro'), 5, '0')) and
					yhtio = '$yhtiolisa'
					$varastolisa";
		$varcheckres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($varcheckres) == 0) {
			return 0;
		}
		else {
			$varcheckrow = mysql_fetch_array($varcheckres);
			return $varcheckrow['tunnus'];
		}
	}
}

if (!function_exists("pdf_substr")) {
	function pdf_substr($str, $len, $pdf, $param) {

		for($s = strlen($str); $s > 0; $s--) {
			if($pdf->strlen($str, $param) > $len) {
				$str = substr($str, 0, $s);
			}
			else {
				break;
			}
		}

		return $str;
	}
}

if (!function_exists("mm_pt")) {
	function mm_pt($millimetreja) {
		$pointseja = round($millimetreja / 0.3527777778,2);
		return $pointseja;
	}
}

if (!function_exists("pt_mm")) {
	function pt_mm($pointseja) {
		$millimetreja = round($pointseja * 0.3527777778,2);
		return $millimetreja;
	}
}

if (!function_exists("table_exists")) {
	function table_exists($taulu) {
		global $dbkanta;

		//	Ei kaaduta errorista
		$query = "show tables where tables_in_$dbkanta = '$taulu';";
		$result = mysql_query($query);
		if (mysql_num_rows($result) == 1) {
			return true;
		}
		else {
			return false;
		}
	}
}

if (!function_exists("viikonpaiva")) {
	function viikonpaiva($day="", $now="") {

	  $now = $now ? $now : "now";
	  $day = $day ? $day : "now";

	  $rel = date("N", strtotime($day)) - date("N");

	  $time = strtotime("$rel days", strtotime($now));

	  return date("Y-m-d", $time);

	}
}


if (!function_exists("saldo_myytavissa")) {
	function saldo_myytavissa($tuoteno, $tyyppi = '', $varasto = 0, $yhtio = '', $hyllyalue = '', $hyllynro = '', $hyllyvali = '', $hyllytaso = '', $maa = '', $pvm = '', $era = '') {

		// Tämä funktio palauttaa myytävissä olevan saldon sallituista varastoista (tyyppi='')
		// Laskuttamattomia hyvityksiä ei katsota myytäviksi vaan ne pitää laskuttaa ennenkuin näkyvät täällä (varattu > 0)

		global $kukarow, $yhtiorow;

		$varasto = (int) $varasto;

		$query  = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
		$result = mysql_query($query) or die($query.mysql_error());
		$tuote  = mysql_fetch_array($result);

		if (mysql_num_rows($result) == 0) {
			$saldo			= FALSE;
			$hyllyssa		= FALSE;
			$myytavissa		= FALSE;
		}
		elseif ($tuote["ei_saldoa"] != "") {
			$saldo			= 0;
			$hyllyssa		= 0;
			$myytavissa		= 0;
		}
		else {
			//Jos yhtiö tulee parametrinä niin katsotaan, ettei se ole ihan mitä sattuu ja lasketaan sen yhtiön saldo
			if ($yhtio != "") {
				$query	= "	SELECT GROUP_CONCAT(distinct yhtio) yhtiot
							from yhtio
							where yhtio='$kukarow[yhtio]' or (konserni = '$yhtiorow[konserni]' and konserni != '')";
				$pres = mysql_query($query) or pupe_error($query);
				$prow = mysql_fetch_array($pres);

				$yhtiot = explode(",", $prow["yhtiot"]);

				if (in_array($yhtio, $yhtiot) and $yhtio != $kukarow["yhtio"]) {
					// Jos yhtiö on joku konserniyhtiöistä niin tutkitaan suoratoimitus juttuja
					$query = "	select tyyppi_tieto, liitostunnus, toim_tuoteno
								from tuotteen_toimittajat, toimi
								where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
								and tuotteen_toimittajat.tuoteno = '$tuoteno'
								and toimi.yhtio         = tuotteen_toimittajat.yhtio
								and toimi.tunnus        = tuotteen_toimittajat.liitostunnus
								and toimi.tyyppi        = 'S'
								and toimi.tyyppi_tieto  = '$yhtio'
								and toimi.edi_palvelin != ''
								and toimi.edi_kayttaja != ''
								and toimi.edi_salasana != ''
								and toimi.edi_polku    != ''
								and toimi.oletus_vienti in ('C','F','I')";
					$superjtres  = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($superjtres) > 0) {
						$superrow = mysql_fetch_array($superjtres);

						$yhtiolisa	= $yhtio;
						$tuoteno 	= $superrow["toim_tuoteno"];
					}
					else {
						return array(0, 0, 0, FALSE);
					}
				}
				elseif($yhtio == $kukarow["yhtio"]) {
					$yhtiolisa = $kukarow["yhtio"];
				}
				else {
					return array(FALSE, FALSE, FALSE, FALSE);
				}
			}
			else {
				$yhtiolisa = $kukarow["yhtio"];
			}

			$valinta1 = ""; // varaston tyyppi
			$valinta2 = ""; // tietty varastopaikka special case
			$valinta3 = ""; // päätetään joinin stricteys

			// katotaan ollaanko annettu parametriksi joku tietty varastotyyppi
			if ($tyyppi == "E") {
				$valinta1 = " varastopaikat.tyyppi = 'E' and ";
				$valinta2 = "";
				$valinta3 = " HAVING varastopaikat.tyyppi is not null ";
			}
			elseif ($tyyppi == "V") {
				$valinta1 = " varastopaikat.tyyppi = 'V' and ";
				$valinta2 = "";
				$valinta3 = " HAVING varastopaikat.tyyppi is not null ";
			}
			elseif ($tyyppi == "KAIKKI") {
				$valinta1 = "";
				$valinta2 = "";
				$valinta3 = "";
			}
			elseif ($tyyppi == "ORVOT") {
				$valinta1 = "";
				$valinta2 = "";
				$valinta3 = " HAVING varastopaikat.tyyppi is null ";
			}
			else {
				$valinta1 = " varastopaikat.tyyppi = '' and ";
				$valinta2 = "";
				$valinta3 = " HAVING varastopaikat.tyyppi is not null ";
			}

			// Katotaan halutaanko saldo vaan jostain tietystä varastosta (varastopaikat.tunnus), silloin unohdetaan edellä annettu tyyppi kokonaan
			if ($varasto != 0) {
				$valinta1 = " varastopaikat.tunnus = '$varasto' and ";
				$valinta2 = "";
				$valinta3 = " HAVING varastopaikat.tyyppi is not null ";
			}

			// Katotaan halutaanko saldo vaan joltain tietyltä varasopaikalta, silloinkin unohdetaan edellä annettu tyyppi kokonaan
			if ($hyllyalue != "") {
				$valinta1 = "";
				$valinta2 = " 	and tuotepaikat.hyllyalue = '$hyllyalue'
								and tuotepaikat.hyllynro  = '$hyllynro'
								and tuotepaikat.hyllyvali = '$hyllyvali'
								and tuotepaikat.hyllytaso = '$hyllytaso' ";
				$valinta3 = " HAVING varastopaikat.tyyppi is not null ";
			}

			if ($maa != "") {
				$valinta1 .= " (varastopaikat.sallitut_maat like '%$maa%' or varastopaikat.sallitut_maat = '') and ";
			}

			if ($pvm != '') {
				$kerayslisa = " and tilausrivi.kerayspvm <= '$pvm' ";
			}

			// Saldo varastoista
			if (($tuote["sarjanumeroseuranta"] == "E" or $tuote["sarjanumeroseuranta"] == "F") and $era != '') {
				$query = "	SELECT sum(sarjanumeroseuranta.era_kpl) saldo, tuotepaikat.saldo_varattu, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, varastopaikat.tyyppi
							FROM tuotepaikat
							LEFT JOIN varastopaikat on varastopaikat.yhtio=tuotepaikat.yhtio
							and $valinta1
							concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
							concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
							JOIN sarjanumeroseuranta  use index (yhtio_tuoteno_sarjanumero)  ON sarjanumeroseuranta.yhtio = tuotepaikat.yhtio
							and sarjanumeroseuranta.tuoteno = tuotepaikat.tuoteno
							and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
							and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
							and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
							and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
							and sarjanumeroseuranta.sarjanumero = '$era'
							and sarjanumeroseuranta.myyntirivitunnus = 0
							and sarjanumeroseuranta.era_kpl != 0
							JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus and tilausrivi_osto.laskutettuaika != '0000-00-00'
							WHERE tuotepaikat.yhtio = '$yhtiolisa'
							and tuotepaikat.tuoteno = '$tuoteno'
							$valinta2
							GROUP BY tuotepaikat.saldo_varattu, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, varastopaikat.tyyppi
							$valinta3";
			}
			else {
				$query = "	SELECT tuotepaikat.saldo, tuotepaikat.saldo_varattu, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, varastopaikat.tyyppi
							FROM tuotepaikat
							LEFT JOIN varastopaikat on varastopaikat.yhtio=tuotepaikat.yhtio
							and $valinta1
							concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
							concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
							WHERE tuotepaikat.yhtio = '$yhtiolisa'
							and tuotepaikat.tuoteno = '$tuoteno'
							$valinta2
							$valinta3";
			}
			$result = mysql_query($query) or die($query);

			if (mysql_num_rows($result) == 0) {
				return array(FALSE, FALSE, FALSE, FALSE);
			}
			else {
				$saldo			= 0;
				$hyllyssa		= 0;
				$myytavissa		= 0;
				$ennakkopois 	= 0;

				while ($row = mysql_fetch_array($result)) {

					// Saldot
					$saldo += $row["saldo"];

					// Myyntirivien, varastosiirtojen ja valmistusten ennakkopoistot ja kerätyt rivit
					if (($tuote["sarjanumeroseuranta"] == "E" or $tuote["sarjanumeroseuranta"] == "F") and $era != '') {
						$query = "	SELECT
									ifnull(sum(if(tilausrivi.keratty!='', tilausrivi.varattu, 0)),0) keratty,
									ifnull(sum(tilausrivi.varattu),0) varattu
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
									JOIN sarjanumeroseuranta  use index (yhtio_tuoteno_sarjanumero) ON sarjanumeroseuranta.yhtio = tilausrivi.yhtio
									and sarjanumeroseuranta.tuoteno 			= tilausrivi.tuoteno
									and sarjanumeroseuranta.hyllyalue 			= tilausrivi.hyllyalue
									and sarjanumeroseuranta.hyllynro  			= tilausrivi.hyllynro
									and sarjanumeroseuranta.hyllyvali 			= tilausrivi.hyllyvali
									and sarjanumeroseuranta.hyllytaso 			= tilausrivi.hyllytaso
									and sarjanumeroseuranta.myyntirivitunnus 	= tilausrivi.tunnus
									and sarjanumeroseuranta.sarjanumero 		= '$era'
									WHERE tilausrivi.yhtio = '$yhtiolisa'
									and tilausrivi.tyyppi in ('L','G','V')
									and (tilausrivi.perheid2 = 0 or tilausrivi.perheid2=tilausrivi.tunnus)
									and tilausrivi.tuoteno = '$tuoteno'
									and tilausrivi.varattu > 0
									and tilausrivi.hyllyalue = '$row[hyllyalue]'
									and tilausrivi.hyllynro  = '$row[hyllynro]'
									and tilausrivi.hyllyvali = '$row[hyllyvali]'
									and tilausrivi.hyllytaso = '$row[hyllytaso]'
									$kerayslisa";
					}
					else {
						$query = "	SELECT
									ifnull(sum(if(tilausrivi.keratty!='', tilausrivi.varattu, 0)),0) keratty,
									ifnull(sum(tilausrivi.varattu),0) varattu
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
									WHERE tilausrivi.yhtio = '$yhtiolisa'
									and tilausrivi.tyyppi in ('L','G','V')
									and (tilausrivi.perheid2 = 0 or tilausrivi.perheid2=tilausrivi.tunnus)
									and tilausrivi.tuoteno = '$tuoteno'
									and tilausrivi.varattu > 0
									and tilausrivi.hyllyalue = '$row[hyllyalue]'
									and tilausrivi.hyllynro  = '$row[hyllynro]'
									and tilausrivi.hyllyvali = '$row[hyllyvali]'
									and tilausrivi.hyllytaso = '$row[hyllytaso]'
									$kerayslisa";
					}

					$ennresult = mysql_query($query) or die($query);
					$ennrow = mysql_fetch_array($ennresult);

					$myytavissa += $row["saldo"] - $ennrow["varattu"] - $row["saldo_varattu"];
					$hyllyssa   += $row["saldo"] - $ennrow["keratty"];

					if ($pvm != '') {
						// jos lasketaan tulevaisuuteen niin otetaan varastoonvalmistukset huomioon
						if (($tuote["sarjanumeroseuranta"] == "E" or $tuote["sarjanumeroseuranta"] == "F") and $era != '') {
							$query = "	SELECT
										ifnull(sum(tilausrivi.varattu),0) varattu
										FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
										JOIN lasku use index (primary) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and lasku.tilaustyyppi = 'W'
										JOIN sarjanumeroseuranta  use index (yhtio_tuoteno_sarjanumero) ON sarjanumeroseuranta.yhtio = tilausrivi.yhtio
										and sarjanumeroseuranta.tuoteno 			= tilausrivi.tuoteno
										and sarjanumeroseuranta.hyllyalue 			= tilausrivi.hyllyalue
										and sarjanumeroseuranta.hyllynro  			= tilausrivi.hyllynro
										and sarjanumeroseuranta.hyllyvali 			= tilausrivi.hyllyvali
										and sarjanumeroseuranta.hyllytaso 			= tilausrivi.hyllytaso
										and sarjanumeroseuranta.myyntirivitunnus 	= tilausrivi.tunnus
										and sarjanumeroseuranta.sarjanumero 		= '$era'
										WHERE tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
										and tilausrivi.yhtio = '$yhtiolisa'
										and tilausrivi.tyyppi = 'W'
										and (tilausrivi.perheid2 = 0 or tilausrivi.perheid2=tilausrivi.tunnus)
										and tilausrivi.tuoteno = '$tuoteno'
										and tilausrivi.varattu 	 > 0
										and tilausrivi.hyllyalue = '$row[hyllyalue]'
										and tilausrivi.hyllynro  = '$row[hyllynro]'
										and tilausrivi.hyllyvali = '$row[hyllyvali]'
										and tilausrivi.hyllytaso = '$row[hyllytaso]'
										and tilausrivi.kerayspvm <= '$pvm'";
						}
						else {
							$query = "	SELECT
										ifnull(sum(tilausrivi.varattu),0) varattu
										FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
										JOIN lasku use index (primary) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and lasku.tilaustyyppi = 'W'
										WHERE tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
										and tilausrivi.yhtio = '$yhtiolisa'
										and tilausrivi.tyyppi = 'W'
										and (tilausrivi.perheid2 = 0 or tilausrivi.perheid2=tilausrivi.tunnus)
										and tilausrivi.tuoteno = '$tuoteno'
										and tilausrivi.varattu > 0
										and tilausrivi.hyllyalue = '$row[hyllyalue]'
										and tilausrivi.hyllynro  = '$row[hyllynro]'
										and tilausrivi.hyllyvali = '$row[hyllyvali]'
										and tilausrivi.hyllytaso = '$row[hyllytaso]'
										and tilausrivi.kerayspvm <= '$pvm'";
						}
						$valmresult = mysql_query($query) or die($query);
						$valmrow = mysql_fetch_array($valmresult);

						$myytavissa += $valmrow["varattu"];
					}
				}


				// jos lasketaan tulevaisuuteen niin otetaan ostorivit huomioon
				if ($pvm != '') {
					if (($tuote["sarjanumeroseuranta"] == "E" or $tuote["sarjanumeroseuranta"] == "F") and $era != '') {
						$query = "	SELECT
									ifnull(sum(tilausrivi.varattu),0) varattu
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
									JOIN sarjanumeroseuranta  use index (yhtio_tuoteno_sarjanumero) ON sarjanumeroseuranta.yhtio = tilausrivi.yhtio
									and sarjanumeroseuranta.tuoteno 			= tilausrivi.tuoteno
									and sarjanumeroseuranta.hyllyalue 			= tilausrivi.hyllyalue
									and sarjanumeroseuranta.hyllynro  			= tilausrivi.hyllynro
									and sarjanumeroseuranta.hyllyvali 			= tilausrivi.hyllyvali
									and sarjanumeroseuranta.hyllytaso 			= tilausrivi.hyllytaso
									and sarjanumeroseuranta.myyntirivitunnus 	= tilausrivi.tunnus
									and sarjanumeroseuranta.sarjanumero 		= '$era'
									WHERE tilausrivi.yhtio 	= '$yhtiolisa'
									and tilausrivi.tyyppi  = 'O'
									and (tilausrivi.perheid2 = 0 or tilausrivi.perheid2=tilausrivi.tunnus)
									and tilausrivi.tuoteno 	= '$tuoteno'
									and tilausrivi.varattu > 0
									and tilausrivi.toimaika <= '$pvm'";
					}
					else {
						$query = "	SELECT
									ifnull(sum(tilausrivi.varattu),0) varattu
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
									WHERE tilausrivi.yhtio 	= '$yhtiolisa'
									and tilausrivi.tyyppi 	= 'O'
									and (tilausrivi.perheid2 = 0 or tilausrivi.perheid2=tilausrivi.tunnus)
									and tilausrivi.tuoteno 	= '$tuoteno'
									and tilausrivi.varattu 	> 0
									and tilausrivi.toimaika <= '$pvm'";
					}
					$ostresult = mysql_query($query) or die($query);
					$ostrow = mysql_fetch_array($ostresult);

					$myytavissa += $ostrow["varattu"];
				}


				// katsotaan löytyykö tuotetta varattuna joltain muulta paikalta, jota ei ole enää olemassa tuotepaikoissa
				// ekaks haetaan ihan kaikki nykyiset paikat suoraan mysql muotoon
				$query = "	SELECT group_concat(\"'\",rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0'),\"'\") paikat
							FROM tuotepaikat
							WHERE yhtio='$yhtiolisa' and tuoteno='$tuoteno'";
				$ennresult = mysql_query($query) or die($query);
				$ennrow = mysql_fetch_array($ennresult);

				if ($varasto == 0 and $hyllyalue == "" and $hyllynro == "") {

					$orposaldomyytavissa = 0;

					// jos paikkoja löytyi
					if ($ennrow["paikat"] != "") {

						if ($tyyppi != "KAIKKI" and $tyyppi != "ORVOT") {
							$query = "	SELECT sum(saldo) saldo, varastopaikat.tyyppi
										FROM tuotepaikat
										LEFT JOIN varastopaikat on varastopaikat.yhtio=tuotepaikat.yhtio and
										concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
										concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
										WHERE tuotepaikat.yhtio = '$yhtiolisa'
										and tuotepaikat.tuoteno = '$tuoteno'
										GROUP BY varastopaikat.tyyppi
										HAVING varastopaikat.tyyppi is null";
							$ennsaldoresult = mysql_query($query) or die($query);

							$ennsaldorow = mysql_fetch_array($ennsaldoresult);
							$orposaldomyytavissa = $ennsaldorow["saldo"];
						}

						// etsitään varatut kaikilta paikoilla jolla on joku muu varastopaikka (NOT IN)
						$query = "	SELECT sum(varattu) varattu
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
									WHERE tilausrivi.yhtio = '$yhtiolisa' and
									tilausrivi.tyyppi in ('L','G','V') and
									tilausrivi.tuoteno = '$tuoteno' and
									tilausrivi.varattu > 0 and
									concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) NOT IN ($ennrow[paikat])
									$kerayslisa ";
						$ennresult = mysql_query($query) or die($query);
						$ennrow = mysql_fetch_array($ennresult);

						$orposaldomyytavissa -= $ennrow["varattu"];
					}
					else {
						// tuotteella ei ole yhtään paikkaa.. katotaan silti varatut
						// etsitään varatut kaikilta paikoilta joilla on joku muu varastopaikka (NOT IN)
						$query = "	SELECT sum(varattu) varattu
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
									WHERE tilausrivi.yhtio = '$yhtiolisa' and
									tilausrivi.tyyppi in ('L','G','V') and
									tilausrivi.tuoteno = '$tuoteno' and
									tilausrivi.varattu > 0
									$kerayslisa ";
						$ennresult = mysql_query($query) or die($query);
						$ennrow = mysql_fetch_array($ennresult);

						$orposaldomyytavissa -= $ennrow["varattu"];
					}

					$myytavissa += $orposaldomyytavissa;

				}
			}
		}
		return array($saldo, $hyllyssa, $myytavissa, TRUE);
	}
}

if (!function_exists("lpr")) {
	function lpr($str,$prn,$prnkomento = "") {
		global $kukarow;

		if ($prnkomento != "") {
			$kirjoitin["komento"] = $prnkomento;
		}
		else {
			$query = "	SELECT *
						FROM kirjoittimet
						WHERE
						yhtio = '$kukarow[yhtio]' and
						tunnus = '$prn'
						ORDER by kirjoitin";
			$kirre = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($kirre) != 1) {
				echo "printer not found";
				return;
			}

			$kirjoitin = mysql_fetch_array($kirre);
		}

		$pipe = popen($kirjoitin["komento"], 'w');

		if (!$pipe) {
			echo "pipe failed";
			return;
		}

		// sallitut merkit listattu, kaikki muut menee spaceks...
		$str = ereg_replace("[^A-Za-z0-9ÖöÅåÄä .,-/!|+()%#\n\r]", " ", $str);

		// merkistökonversio
		$from = array('ä','å','ö','Ä','Å','Ö','|');

		if ($kirjoitin["merkisto"] == 1) {
			$to	= array('{','}','|','[',']','\\',chr(179));											// 7 bittiset
			$str = str_replace($from, $to, $str);
		}
		elseif ($kirjoitin["merkisto"] == 2) {
			$to	= array(chr(132),chr(134),chr(148),chr(142),chr(143),chr(153),chr(179));			// DOS charset
			$str = str_replace($from, $to, $str);
		}
		elseif ($kirjoitin["merkisto"] == 3) {
			$to	= array(chr(228),chr(229),chr(246),chr(196),chr(197),chr(214),chr(124));			// ANSI charset
			$str = str_replace($from, $to, $str);
		}
		elseif ($kirjoitin["merkisto"] == 4) {
			$str = utf8_encode($str);																// UTF8 charset käännös suoraan yhellä rivillä
		}
		elseif ($kirjoitin["merkisto"] == 5) {
			$to	= array('a','a','o','A','A','O',' ');												// Ääkköset kokonaan pois
			$str = str_replace($from, $to, $str);
		}

		fputs($pipe, $str);
		pclose($pipe);
	}
}

// tehdään vertailukelponen stringi varastopaikasta
if (!function_exists("varastopaikka")) {
	function varastopaikka($str) {
		$str = strtoupper(trim($str));

		if (is_numeric($str)) {
			$str = sprintf("%5.5s", $str); // numerot lpaddataan 5 merkkiä
		}
		else {
			$str = sprintf("%-5.5s", $str); // stringit rpaddataan 5 merkkiä
		}

		return $str;
	}
}

// Tehdään valuuttamuunnos laskun valuutasta yhtiön valuuttaan.
if (!function_exists("yhtioval")) {
	function yhtioval($summa, $kurssi) {
		if($kurssi <= 0 or !is_numeric($kurssi)) {
			$kurssi = 1;
		}

		$sum = $summa*$kurssi;
		return $sum;
	}
}

// Tehdään valuuttamuunnos yhtiön valuutasta laskun valuuttaan.
if (!function_exists("laskuval")) {
	function laskuval($summa, $kurssi) {
		if($kurssi <= 0 or !is_numeric($kurssi)) {
			$kurssi = 1;
		}

		$sum = $summa/$kurssi;
		return $sum;
	}
}

// Haetaan tuotteen avainsanoista/avainsanoista korvaavuuksia.
if (!function_exists("asana")) {
	function asana($laji, $sana, $sana2 = '') {

		global $kukarow, $yhtiorow;

		$laji = $laji.$kukarow["kieli"];

		if (substr($laji,0,8) == 'nimitys_') {
			$tarvitaan = 0;
			if ($kukarow["kieli"] != $yhtiorow["kieli"]) {
				$query = "select selite from tuotteen_avainsanat where yhtio = '$kukarow[yhtio]' and laji = '$laji' and tuoteno = '$sana' LIMIT 1";
				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) > 0) {
					$row = mysql_fetch_array($result);
					return $row["selite"];
				}
				elseif ($sana2 == '') {
					$tarvitaan++;
				}
			}
			elseif ($sana2 == '') {
				$tarvitaan++;
			}

			if ($tarvitaan > 0) {
				$query = "select nimitys from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$sana' LIMIT 1";
				$result = mysql_query($query) or pupe_error($query);
				$row = mysql_fetch_array($result);
				return $row["nimitys"];
			}
			elseif ($sana2 != '') {
				//return $row["selite"];
				return $sana2;
			}
		}
		elseif (substr($laji,0,13) == 'TOIMITUSTAPA_') {
			if ($kukarow["kieli"] != $yhtiorow["kieli"]) {
				$query = "select selitetark from avainsana where yhtio = '$kukarow[yhtio]' and laji = '$laji' and selite = '$sana' LIMIT 1";
				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) > 0) {
					$row = mysql_fetch_array($result);
					return $row["selitetark"];
				}
				else {
					return $sana;
				}
			}
			else {
				return $sana;
			}
		}
		else {
			if ($kukarow["kieli"] != $yhtiorow["kieli"]) {
				$query = "select selitetark from avainsana where yhtio = '$kukarow[yhtio]' and laji = '$laji' and selite = '$sana' LIMIT 1";
				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) > 0) {
					$row = mysql_fetch_array($result);
					return $row["selitetark"];
				}
				else {
					return $sana;
				}
			}
			else {
				return $sana;
			}
		}
	}

	/*esimerkki
	".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."
	*/
}

// helpotetaan queryn tekoa.
if (!function_exists("nimitys")) {
	function nimitys($laji, $joini = '', $kieli = '') {

		global $kukarow, $yhtiorow;

		$returni = "";

		if ($laji == 'select') {
			if ($joini == '') {
				$returni = " nimitys ";
			}
			else {
				$returni = "$joini.nimitys";
			}
		}

		if ($kieli == '') {
			$kieli = $kukarow["kieli"];
		}

		if ($kieli != $yhtiorow["kieli"]) {
			if ($laji == 'join') {
				if ($joini != '') {
					$returni = " LEFT JOIN tuotteen_avainsanat on $joini.yhtio = tuotteen_avainsanat.yhtio and $joini.tuoteno = tuotteen_avainsanat.tuoteno and tuotteen_avainsanat.laji = 'nimitys_$kieli' ";
				}
			}
			elseif ($laji == 'select') {
				if ($joini == '') {
					$mika = "nimitys";
				}
				else {
					$mika = "$joini.nimitys";
				}

				$returni = " if(isnull(tuotteen_avainsanat.selite) or tuotteen_avainsanat.selite='', $mika, tuotteen_avainsanat.selite) nimitys ";
			}
		}

		return $returni;
	}

	/*esimerkki
	".nimitys('select')."
	".nimitys('join','tilausrivi')."
	*/

}

// helpotetaan queryn tekoa.
if (!function_exists("avain")) {
	function avain($laji, $joini = '', $kieli = '', $as = '') {

		global $kukarow, $yhtiorow;

		$returni = "";

		if ($kieli == '') {
			$kieli = $kukarow["kieli"];
		}

		if ($joini == 'TOIMITUSTAPA_') {
			$returni = " selite ";
			$joinaa = "toimitustapa";
		}
		elseif ($joini == 'MEHTOTXT_') {
			$returni = " teksti ";
			$joinaa = "maksuehto";
		}
		elseif ($joini == 'MEHTOKATXT_') {
			$returni = " kassa_teksti ";
			$joinaa = "maksuehto";
		}
		else {
			$returni = " selitetark ";
			$joinaa = "avainsana";
		}

		if ($as == '') {
			$as = $returni;
			$returnas = "";
		}
		else {
			$as = " ".$as." ";
			$returnas = " as $as";
		}

		if ($kieli != $yhtiorow["kieli"]) {
			$returnas = "";
			
			if ($laji == 'join') {
				if ($joini != '') {
					$returni = " LEFT JOIN avainsana AS a on ".$joinaa.".yhtio = a.yhtio and ".$joinaa.".".trim($returni)." = a.selite and a.laji = '".$joini.$kieli."' ";
				}
			}
			elseif ($laji == 'join2') {
				if ($joini != '') {
					$returni = " LEFT JOIN avainsana AS b on ".$joinaa.".yhtio = b.yhtio and ".$joinaa.".".trim($returni)." = b.selite and b.laji = '".$joini.$kieli."' ";
				}
			}
			elseif ($laji == 'select') {
				$returni = " if(isnull(a.selitetark) or a.selitetark='', ".$joinaa.".".trim($returni).", a.selitetark) as ".trim($as)." ";
			}
			elseif ($laji == 'select2') {
				$returni = " if(isnull(b.selitetark) or b.selitetark='', ".$joinaa.".".trim($returni).", b.selitetark) as ".trim($as)." ";
			}
			elseif ($laji == 'selectcon') {
				$returni = " if(isnull(a.selitetark) or a.selitetark='', ".$joinaa.".".trim($returni).", a.selitetark) ";
			}
			elseif ($laji == 'selectcon2') {
				$returni = " if(isnull(b.selitetark) or b.selitetark='', ".$joinaa.".".trim($returni).", b.selitetark) ";
			}
		}
		elseif ($laji == 'join' or $laji == 'join2') {
			$returni = '';
		}

		return $returni.$returnas;
	}

	/*esimerkki
	".avain('select')."
	".avain('join','TRY_')."
	*/

}

// Tehdään lähetteen ja laskun sorttauskentät
if (!function_exists("generoi_sorttauskentta")) {

	function generoi_sorttauskentta($jarjestys = "", $toimittaja = 0) {

		global $kukarow, $yhtiorow;

		if ($jarjestys == "") {
			$jarjestys = $yhtiorow["lahetteen_jarjestys"];
		}

		$sorttauskentta = "";

		// varastopaikkajärjestys, tuoteperheet pidetään yhdessä, erikoistuotteet loppuun
		if ($jarjestys == "0") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno)),
									if(tilausrivi.perheid > 0, (select concat(rpad(upper(t2.hyllyalue), 5, '0'),lpad(upper(t2.hyllynro), 5, '0'),lpad(upper(t2.hyllyvali), 5, '0'),lpad(upper(t2.hyllytaso), 5, '0'), t2.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid),
								   		if(tilausrivi.perheid2 > 0, (select concat(rpad(upper(t3.hyllyalue), 5, '0'),lpad(upper(t3.hyllynro), 5, '0'),lpad(upper(t3.hyllyvali), 5, '0'),lpad(upper(t3.hyllytaso), 5, '0'), t3.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2),
											tilausrivi.tuoteno))) as sorttauskentta";
		}

		// varastopaikkajärjestys, tuoteperheet pidetään yhdessä
		elseif ($jarjestys == "1") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno),
									if(tilausrivi.perheid > 0, (select concat(rpad(upper(t2.hyllyalue), 5, '0'),lpad(upper(t2.hyllynro), 5, '0'),lpad(upper(t2.hyllyvali), 5, '0'),lpad(upper(t2.hyllytaso), 5, '0'), t2.tuoteno, rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid),
								   		if(tilausrivi.perheid2 > 0, (select concat(rpad(upper(t3.hyllyalue), 5, '0'),lpad(upper(t3.hyllynro), 5, '0'),lpad(upper(t3.hyllyvali), 5, '0'),lpad(upper(t3.hyllytaso), 5, '0'), t3.tuoteno, rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2),
											tilausrivi.tuoteno))) as sorttauskentta";
		}

		// varastopaikkajärjestys, erikoistuotteet loppuun
		elseif ($jarjestys == "2") {
			$sorttauskentta = "if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno),
									concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno)) as sorttauskentta";
		}

		// varastopaikkajärjestys
		elseif ($jarjestys == "3") {
			$sorttauskentta = "concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno) as sorttauskentta";
		}

		// tuotenumerojärjestys, tuoteperheet pidetään yhdessä, erikoistuotteet loppuun
		elseif ($jarjestys == "4") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno),
									if(tilausrivi.perheid > 0, (select concat(t2.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid),
										if(tilausrivi.perheid2 > 0, (select concat(t3.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2),
											tilausrivi.tuoteno))) as sorttauskentta";
		}

		// tuotenumerojärjestys, tuoteperheet pidetään yhdessä
		elseif ($jarjestys == "5") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, tilausrivi.tuoteno,
									if(tilausrivi.perheid > 0, (select concat(t2.tuoteno, tilausrivi.tuoteno) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid),
										if(tilausrivi.perheid2 > 0, (select concat(t3.tuoteno, tilausrivi.tuoteno) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2),
											tilausrivi.tuoteno))) as sorttauskentta";
		}

		// tuotenumerojärjestys, erikoistuotteet loppuun
		elseif ($jarjestys == "6") {
			$sorttauskentta = "if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno),
									tilausrivi.tuoteno) as sorttauskentta";
		}

		// tuotenumerojärjestys
		elseif ($jarjestys == "7") {
			$sorttauskentta = "tilausrivi.tuoteno as sorttauskentta";
		}

		// tilausjärjestys, tuoteperheet pidetään yhdessä
		elseif ($jarjestys == "8") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, tilausrivi.tunnus,
									if (tilausrivi.perheid > 0, tilausrivi.perheid,
										if(tilausrivi.perheid2 > 0, tilausrivi.perheid2,
											tilausrivi.tunnus))) as sorttauskentta";
		}

		// tilausjärjestys
		elseif ($jarjestys == "9") {
			$sorttauskentta = "tilausrivi.tunnus as sorttauskentta";
		}

		// toimittajan tuotenumerojärjestys, tuoteperheet pidetään yhdessä
		elseif ($jarjestys == "10") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, if(tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno),
									if(tilausrivi.perheid > 0, (select concat(if(tt2.toim_tuoteno!='', concat(tt2.toim_tuoteno,tilausrivi.perheid), concat(t2.tuoteno,tilausrivi.perheid)), if(tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno))
																from tilausrivi as t2
																JOIN tuotteen_toimittajat tt2 ON t2.yhtio = tt2.yhtio and t2.tuoteno = tt2.tuoteno and tt2.liitostunnus = '$toimittaja'
																where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid),
										if(tilausrivi.perheid2 > 0, (select concat(if(tt3.toim_tuoteno!='', concat(tt3.toim_tuoteno,tilausrivi.perheid2), concat(t3.tuoteno,tilausrivi.perheid2)), if(tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno))
																	from tilausrivi as t3
																	JOIN tuotteen_toimittajat tt3 ON t3.yhtio = tt3.yhtio and t3.tuoteno = tt3.tuoteno and tt3.liitostunnus = '$toimittaja'
																	where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2),
											if(tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)))) as sorttauskentta";
		}
		elseif ($jarjestys == "M") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, (SELECT tl.jarjestys
				 																		FROM tilausrivin_lisatiedot tl
																						WHERE tl.yhtio=tilausrivi.yhtio and tl.tilausrivitunnus=tilausrivi.tunnus
																						LIMIT 1),
									if (tilausrivi.perheid > 0, (	SELECT tl.jarjestys
																	FROM tilausrivin_lisatiedot tl
																	WHERE tl.yhtio=tilausrivi.yhtio and tl.tilausrivitunnus=tilausrivi.perheid
																	LIMIT 1),
										if (tilausrivi.perheid > 0, (	SELECT tl.jarjestys
																		FROM tilausrivin_lisatiedot tl
																		WHERE tl.yhtio=tilausrivi.yhtio and tl.tilausrivitunnus=tilausrivi.perheid2
																		LIMIT 1), tilausrivi.tunnus
										)
									)
								) as sorttauskentta";
		}

		// joku default
		else {
			$sorttauskentta = "tilausrivi.tunnus as sorttauskentta";
		}

		return $sorttauskentta;
	}
}

// Konserniyhtiöiden tietueiden synkronointi
if (!function_exists("synkronoi")) {
	function synkronoi($yhtio, $table, $tunnus, $orig = "", $force = "") {
		global $yhtiorow, $kukarow;

		if (!function_exists("synclog")) {
			function synclog($yhtio, $table, $viesti, $tunnus) {
				global $yhtiorow, $kukarow;
				
				if($kohde == $yhtio) {
					$tapa = "MASTER";
				}
				else {
					$tapa = "TARGET";
				}

				$query = "	INSERT INTO synclog SET
							yhtio		= '$yhtio',
							taulu		= '$table',
							tauluntunnus= '$tunnus',
							tapa		= '$tapa',
							viesti		= '".addslashes($viesti)."',
							laatija		= '$kukarow[kuka]',
							luontiaika	= now()";
				$insres = mysql_query($query) or pupe_error($query);
			}
		}

		//	Onko mahdollista synkronoida?
		if(strpos($yhtiorow["synkronoi"], $table) === false) {
			return false;
		}

		$muutokset = array();

		//	Näillä tarttetaan vähän lisätietoo
		if($table == "avainsana") {
			$abulisa = ereg(",(avainsana\|*([\|a-zA-Z]*)),*", $yhtiorow["synkronoi"], $regs);

			$lajit = explode("|",strtolower($regs[2]));
			
			//	saadaanko juuri tätä lajia synkronoida?
			if(is_array($orig) and count($orig) > 0) {				
				if(!in_array(strtolower($orig["laji"]), $lajit)) {
					return false;
				}
			}
		}

		$synclog = "\nAktivoidaan synkronointi konserniyrityksiin\n";

		if(!is_numeric($tunnus)) {
			$synclog .= "VIRHE: Syötetty tunnus ei kelpaa!\n";
			
			synclog($yhtio, $table, $synclog, $tunnus);
			return false;
		}

		//	Haetaan master
		$query = "	SELECT * 
					FROM $table 
					WHERE yhtio	= '$yhtio' 
					and tunnus	= $tunnus";
		$masterres = mysql_query($query) or pupe_error($query);
				
		if(mysql_num_rows($masterres) == 1) {
			$masterrow = mysql_fetch_array($masterres) or pupe_error($query);
			
			$synclog .= "Masteri on tunnus ($tunnus)\n";

			//	Tarkastetaan että originaali on varmasti samasta taulusta!
			if(is_array($orig) and count($orig) > 0) {
				
				$diff = array_diff_key($orig, $masterrow);
				
				if(count($diff) == 0) {

					//	Tarkastetaan oliko muutoksia?
					$diff = array_diff_assoc($orig, $masterrow);

					if(count($diff) > 0) {
										
						foreach($diff as $key => $value) {						
							if(is_string($key) and !in_array($key, array("tunnus","muuttaja","muutospvm","laatija","luontiaika"))) {
								$muutokset[$key] .= $value;
							}
						}
					}
					
					//	Jos mitään ei muuteta mitään ei kanssa tehdä!
					if(count($muutokset) > 0 or $force == "F") {
						$muutos = "Tapahtuneet muutokset mastertaulussa:\n";
						
						foreach($muutokset as $key => $value) {
							$muutos .= "$key: ".$value." => ".$masterrow[$key]."\n";
						}
						$synclog .= $muutos."\n";
					}
					else {
						$synclog .= "Mitään ei muutettu, synkronointia ei suoritettu!\n";
						
						synclog($yhtio, $table, $synclog, $tunnus);
						return true;
					}
				}
				else {
					$synclog = "\nVIRHE: Originaali ei täsmää masteriin!!!\n";
					
					synclog($yhtio, $table, $synclog, $tunnus);
					return false;
				}
			}
		}
		elseif(!is_array($orig)) {
			$synclog .= "Master tietuetta tai originaalia ei löytynyt yhtiöstä tunnuksella ($tunnus)\n";
			
			synclog($yhtio, $table, $synclog, $tunnus);
			return false;
		}
		else {
			$synclog .= "Master tietue ($tunnus) on poistettu, koitetaan poistaa tietue myös konserniyrityksiltä..\n\n";
			unset($masterrow);
		}

		require_once("inc/pakolliset_sarakkeet.inc");

		list($pakolliset, $kielletyt, $wherelliset) = pakolliset_sarakkeet($table);
		
		if(count($wherelliset) == 0 and count($pakolliset) == 0) {
			$synclog.= "VIRHE: Pyydettyä taulua $table ei voida synkronoida, sitä ei ole määritelty!\n";

			synclog($yhtio, $table, $synclog, $tunnus);
			return false;
		}
		else {
			//	Tehdään kysely
			$where = "";
			
			$indeksi = array_merge($wherelliset, $pakolliset);
			$indeksi = array_unique($indeksi);
			
			
			foreach($indeksi as $pakollinen) {
				//	Jos meillä on vielä se originaali tallessa vertailu sitä vastaan
				if(is_array($orig) and count($orig) > 0) {
					$where.=" and ".strtolower($pakollinen)."='".$orig[strtolower($pakollinen)]."'";
				}
				else {
					$where.=" and ".strtolower($pakollinen)."='".$masterrow[strtolower($pakollinen)]."'";
				}
			}
		}

		/*
			Aloitetaan itse synkronointii
		*/

		//	haetaan konserniyhtiöt joita voidaan synkronisoida
		$query = "	SELECT yhtio.yhtio 
					from yhtio 
					JOIN yhtion_parametrit ON yhtion_parametrit.yhtio=yhtio.yhtio
					where yhtio.konserni	 		= '$yhtiorow[konserni]' 
					and yhtion_parametrit.synkronoi like '%$toim%'
					and yhtio.yhtio					!= '$yhtio'";
		$kohderes = mysql_query($query) or pupe_error($query);
		
		if(mysql_num_rows($kohderes) > 0) {
			while($kohderow = mysql_fetch_array($kohderes)) {
				
				$vanhatunnus = $utunnus="";
				$override	 = array();

				//	Poistetaan tietue jos masteria ei löydetty mutta originaali on tallessa.. olisiko tässä selvempi tapa?
				if(!isset($masterrow) and is_array($orig) and count($orig) > 0) {

					//	Tarkastetaan ettei tällä kriteerillä löydy useita poistettavia..
					$query = "	SELECT tunnus
								FROM $table
								WHERE yhtio = '$kohderow[yhtio]' 
								$where";
					$abures = mysql_query($query);

					if(mysql_num_rows($abures) == 1) {
						$aburow = mysql_fetch_array($abures);
						$vanhatunnus = $aburow["tunnus"];
						
						$query = "	DELETE 
									FROM $table 
									WHERE yhtio = '$kohderow[yhtio]' 
									and tunnus = $vanhatunnus";
						$delres = mysql_query($query);

						$synclog .= "Yhtiöltä '$kohderow[yhtio]' poistettiin tietue ($vanhatunnus)\n";
						synclog($kohderow["yhtio"], $table, "Poistettiin $table ($vanhatunnus)", $vanhatunnus);
					}
					elseif(mysql_num_rows($abures)>0) {
						$synclog .= "Yhtiöllä '$kohderow[yhtio]' oli liian monta tietuetta haussa, tietuetta ei voitu poistaa.\n";
						synclog($kohderow["yhtio"], $table, "Koitettiin poistaa $table, mutta haulla löytyi enemmän kuin yski tietue, mitään ei poistettu!", $utunnus);
					}
					else {
						$synclog .= "Yhtiöllä '$kohderow[yhtio]' ei ollut poistettaavaa tietuetta.\n";
					}
				}
				else {
					//	Osa tauluista vaatii vähän käpistelyä!
					if($table == "tuotteen_toimittajat") {

						$query = "select * from tuote where yhtio='$yhtio' and tuoteno='$masterrow[tuoteno]'";
						$abures = mysql_query($query) or pupe_error($query);
						
						if(mysql_num_rows($abures) == 1) {

							$query = "select * from toimi where yhtio='$yhtio' and tunnus='$masterrow[liitostunnus]'";
							$abures = mysql_query($query) or pupe_error($query);
							$aburow = mysql_fetch_array($abures);

							//	Etsitään oikea toimittaja jotta saadaan oikea liitostunnus
							$tarkquery = "select tunnus from toimi where yhtio='$kohderow[yhtio]' and ytunnus='$aburow[ytunnus]' and ovttunnus='$aburow[ovttunnus]'";
							$tarkres = mysql_query($tarkquery) or pupe_error($tarkquery);
							
							if(mysql_num_rows($tarkres)==1) {
								//	Ylikirjoitetaan liitostunnus
								$tarkrow = mysql_fetch_array($tarkres);
								$override["liitostunnus"] == $tarkrow["tunnus"];
							}
							else {
								$synclog .= "Yhtiölle '$kohderow[yhtio]' ei löydy toimittajaa $masterrow[toimittaja] joten tuotteen toimittajaa ei voida sycronisoida\n";
								$ok=0;
							}
						}
						else {
							$synclog .= "Yhtiölle '$kohderow[yhtio]' ei löydy tuotetta $masterrow[tuoteno] joten tuotteen toimittajaa ei voida sycronisoida\n";
							$ok=0;
						}
					}

					if($table == "toimi") {

						//	Hyväksyjiä, kustannuspaikkoja ja tiliä ei synkata
						foreach(array("oletus_hyvak1","oletus_hyvak2","oletus_hyvak3","oletus_hyvak4","oletus_hyvak5","tilino","kustp","kohde","projekti") as $value) {
							$override[$value] = "";
						}
						
						/*
						//	onko hyväksyjät myös kohdeyrityksessä
						foreach(array("oletus_hyvak1","oletus_hyvak2","oletus_hyvak3","oletus_hyvak4","oletus_hyvak5") as $value) {
							$query = "select tunnus from kuka where yhtio='$kohderow[yhtio]' and kuka='".$masterrow[$value]."'";
							$tarkres=mysql_query($query) or pupe_error($query);
							
							//	Käyttäjää ei ole, joten tätä tietuetta ei voida synkronoida..
							if(mysql_num_rows($tarkres) != 1) {
								$override[$value] = "";
							}
						}

						//	Onko tilino myös kohdeyrityksessä?
						$query = "select tunnus from tili where yhtio='$kohderow[yhtio]' and tilino='$masterrow[tilino]'";
						$tarkres = mysql_query($query) or pupe_error($query);
						
						//	tiliä ei ole, joten tätä tietuetta ei voida synkronoida..
						if(mysql_num_rows($tarkres)!=1) {
							$override["tilino"] = "";
						}

						// Onko kustannuspaikat olemassa?
						foreach(array("kustp","kohde","projekti") as $value) {
							$query = "select nimi, tyyppi from kustannuspaikka where yhtio='$yhtio' and tunnus='".$masterrow[$value]."'";
							$abures=mysql_query($query) or pupe_error($query);
							
							if(mysql_num_rows($abures)==1) {
								$aburow = mysql_fetch_array($abures);
								$query = "select tunnus from kustannuspaikka where yhtio='$kohderow[yhtio]' and nimi='$aburow[nimi]' and tyyppi='$aburow[tyyppi]'";
								$tarkres = mysql_query($query) or pupe_error($query);
								
								if(mysql_num_rows($tarkres)!=1) {
									$override[$value]="";
								}
							}
						}
						*/
					}

					if($table == "tuote") {
						//	Oletetaan, että meillä on avainsanat sycronoituna (osasto/try)
						foreach(array("myyjanro","ostajanro","tilino","tilino_eu","tilino_ei_eu","kustp","kohde","projekti","kehahin","vihahin","vihapvm","epakurantti25pvm","epakurantti50pvm","epakurantti75pvm","epakurantti100pvm") as $value) {
							$override[$value] = "";
						}
						
						/*
						//	onko ostajat/myyjät myös kohdeyrityksessä
						foreach(array("myyjanro","ostajanro") as $value) {
							$query = "select tunnus from kuka where yhtio='$kohderow[yhtio]' and kuka='".$masterrow[$value]."'";
							$tarkres = mysql_query($query) or pupe_error($query);
							
							//	Käyttäjää ei ole, joten tätä tietuetta ei voida synkronoida..
							if(mysql_num_rows($tarkres)!=1) {
								$override[$value]="";
							}
						}

						//	Onko tilinot myös kohdeyrityksessä?
						foreach(array("tilino","tilino_eu","tilino_ei_eu") as $value) {
							$query = "select tunnus from tili where yhtio='$kohderow[yhtio]' and tilino='".$masterrow[$value]."'";
							$tarkres=mysql_query($query) or pupe_error($query);
							if(mysql_num_rows($tarkres)!=1) {
								$override[$value]="";
							}
						}

						// Onko kustannuspaikat olemassa?
						foreach(array("kustp","kohde","projekti") as $value) {
							$query = "select nimi, tyyppi from kustannuspaikka where yhtio='$yhtio' and tunnus='".$masterrow[$value]."'";
							$abures=mysql_query($query) or pupe_error($query);
							
							if(mysql_num_rows($abures)==1) {
								$aburow = mysql_fetch_array($abures);
								$query = "select tunnus from kustannuspaikka where yhtio='$kohderow[yhtio]' and nimi='$aburow[nimi]' and tyyppi='$aburow[tyyppi]'";
								$tarkres = mysql_query($query) or pupe_error($query);
								
								if(mysql_num_rows($tarkres)!=1) {
									$override[$value]="";
								}
							}
						}
						*/

						// Tarkastetaan että hinnat eivät mene aivan päin vittuja..
						$query = "select tunnus from yhtion_parametrit where yhtio='$kohderow[yhtio]' and alv_kasittely!='$yhtion_parametrit[alv_kasittely]'";
						$abures = mysql_query($query) or pupe_error($query);
						
						if(mysql_num_rows($abures)!=0 and $masterrow["alv"] > 0) {
							
							// yhtiolla on verolliset hinnat kohteella verottomat
							if($yhtiorow["alv_kasittely"] == "") {
								$override["myyntihinta"] = round(($masterrow["myyntihinta"]*(1+$masterrow["alv"]/100)),2);
								$override["nettohinta"]  = round(($masterrow["nettohinta"]*(1+$masterrow["alv"]/100)),2);
							}
							else {
								$override["myyntihinta"] = round(($masterrow["myyntihinta"]/(1+$masterrow["alv"]/100)),2);
								$override["nettohinta"]  = round(($masterrow["nettohinta"]/(1+$masterrow["alv"]/100)),2);
							}
						}
					}

					if($table == "asiakas") {
						foreach(array("myyjanro","tilino","kustp","kohde","projekti") as $value) {
							$override[$value] = "";
						}

						//	koitetaan hakea oikean maksuehdon tunnus..
						$query = "select * from maksuehto where yhtio='$yhtio' and tunnus='$masterrow[maksuehto]'";
						$abures = mysql_query($query) or pupe_error($query);
						$aburow = mysql_fetch_array($abures);

						//	Melkein kaikki tiedot pitää stemmata!
						$query = "	select tunnus from maksuehto where yhtio='$kohderow[yhtio]'
									and abs_pvm				= '$aburow[abs_pvm]'
									and erapvmkasin			= '$aburow[erapvmkasin]'
									and factoring			= '$aburow[factoring]'
									and jaksotettu			= '$aburow[jaksotettu]'
									and jv					= '$aburow[jv]'
									and kassa_abspvm		= '$aburow[kassa_abspvm]'
									and kassa_alepros		= '$aburow[kassa_alepros]'
									and kassa_relpvm		= '$aburow[kassa_relpvm]'
									and kassa_teksti		= '$aburow[kassa_teksti]'
									and kateinen			= '$aburow[kateinen]'
									and osamaksuehto1		= '$aburow[osamaksuehto1]'
									and osamaksuehto2		= '$aburow[osamaksuehto2]'
									and rel_pvm				= '$aburow[rel_pvm]'
									and sallitut_maat		= '$aburow[sallitut_maat]'
									and summanjakoprososa2	= '$aburow[summanjakoprososa2]'
									and suoraveloitus		= '$aburow[suoraveloitus]'
									LIMIT 1";
						$tarkres = mysql_query($query) or pupe_error($query);
						
						if(mysql_num_rows($tarkres) == 1) {
							$tarkrow = mysql_fetch_array($tarkres);
							$override["maksuehto"] = $tarkrow["tunnus"];
						}
						else {
							$override["maksuehto"] = "";
						}

						//	koitetaan hakea oikean toimitustavan tunnus..
						$query = "select * from toimitustapa where yhtio='$yhtio' and tunnus='$masterrow[toimitustapa]'";
						$abures = mysql_query($query) or pupe_error($query);
						$aburow = mysql_fetch_array($abures);

						//	Melkein kaikki tiedot pitää stemmata!
						$query = "select tunnus from toimitustapa where yhtio='$kohderow[yhtio]'
									and selite			= '$aburow[selite]'
									and jvkulu			= '$aburow[jvkulu]'
									and lauantai		= '$aburow[lauantai]'
									and maa_maara		= '$aburow[maa_maara]'
									and merahti			= '$aburow[merahti]'
									and multi_jv		= '$aburow[multi_jv]'
									and nouto			= '$aburow[nouto]'
									and sallitut_maat	= '$aburow[sallitut_maat]'
									";
						$tarkres=mysql_query($query) or pupe_error($query);
						if(mysql_num_rows($tarkres) == 1) {
							$tarkrow=mysql_fetch_array($tarkres);
							$override["toimitustapa"] = $tarkrow["tunnus"];
						}
						else {
							$override["toimitustapa"] = "";
						}
					}

					//	Päivitetään vai tehdään uutta?
					$query = "	SELECT tunnus
								FROM $table
								WHERE yhtio ='$kohderow[yhtio]' $where";
					$abures=mysql_query($query);

					$ok = 1;
					
					if(mysql_num_rows($abures)==0) {
						$query 	= "INSERT into $table SET yhtio='$kohderow[yhtio]', laatija='$yhtio', luontiaika=now() ";
						$query2	= "";
					}
					elseif(mysql_num_rows($abures)==1) {
						$aburow = mysql_fetch_array($abures);
						$vanhatunnus = $aburow["tunnus"];
						
						$query = "UPDATE $table SET yhtio='$kohderow[yhtio]'";
						$query2	=" WHERE yhtio='$kohderow[yhtio]' and tunnus=$vanhatunnus";
					}
					else {
						$ok=0;
					}

					if($ok == 1) {
						//	Duunataan itse päivitys/insert kysely!!!
						for ($i=1; $i < mysql_num_fields($masterres); $i++) {
							//	Päivitetään vain muuttuneet kentät!
							if (isset($masterrow[$i]) and !in_array(mysql_field_name($masterres, $i), array("yhtio","tunnus","muuttaja","muutospvm","laatija","luontiaika"))) {
								//	Jos arvo pitää ylikirjoittaa niin ylikirjoitetaan (mm. liitostunnus)								
								if(isset($override[mysql_field_name($masterres, $i)])) {
									//Nämä ohitetaan kokonaan
									//$query .= ", ". mysql_field_name($masterres,$i)."='".$override[mysql_field_name($masterres, $i)]."' ";
								}
								else {
									$query .= ", ". mysql_field_name($masterres,$i)."='".$masterrow[$i]."' ";
								}
							}
						}

						$query = $query." ".$query2;
						$updres = mysql_query($query) or pupe_error($query);

						if(mysql_affected_rows() > 0) {
							
							$erot = "";
							
							if(count($override) > 0) {
								$erot = "\n\nPoikkeukset synkronoinnissa:\n";
								
								foreach($override as $key => $value) {
									$erot .= "$key: ".$masterrow[$key]." => ".$value."\n";
								}
							}

							if(mysql_num_rows($abures) == 0) {
								$utunnus = mysql_insert_id();
								$synclog .= "Yhtiölle '$kohderow[yhtio]' lisättiin tietue ($utunnus)\n";
								
								synclog($kohderow["yhtio"], $table, "Uusi $table lisätty.$erot", $utunnus);
							}
							else {
								//	Annetaan aikaleima tässä, koska muuten affected row ei koskaan toimi oikein!
								$query = "UPDATE $table SET yhtio='$kohderow[yhtio]', muuttaja='$yhtio', muutospvm=now() WHERE yhtio='$kohderow[yhtio]' and tunnus=$vanhatunnus";
								$updres = mysql_query($query) or pupe_error($query);

								synclog($kohderow["yhtio"], $table, "Tietue ($vanhatunnus) päivitetty.".$muutos.$erot, $utunnus);
								$synclog .= "Yhtiölle '$kohderow[yhtio]' päivitettiin tietue ($vanhatunnus)\n";
							}
						}
						else {
							$synclog .= "Yhtiölle '$kohderow[yhtio]' ei ollut mitään päivitettävää\n";
						}
					}
					else {
						$synclog .= "VIRHE: Yhtiön '$kohderow[yhtio]' tietoa ei voitu päivittää!!!\n";
					}
				}
			}
		}
		else {
			$synclog .= "Yhtään synkronoitavaa yhtiötä ei löytynyt!!!\n";
			synclog($yhtio, $table, $synclog, $tunnus);
			return false;
		}

		$synclog .= "\nsynkronointi valmis!!";
		synclog($yhtio, $table, $synclog, $tunnus);
		return true;
	}
}

if (!function_exists("jalkilaskentafunktio")) {
	function jalkilaskentafunktio ($tuoteno, $pvm, $uusihinta, $rivitunnus) {
		global $yhtiorow, $kukarow;

		/*
		$tuoteno 	= korjattava tuote
		$pvm 		= mihin päivään asti korjataan
		$uusihinta 	= mikä on tuon pvm:n oikea ostohinta
		$rivitunnus = mikä on tapahtuman tehneen rivin tunnus (ostorivitunnus)
		*/

		require("tilauskasittely/jalkilaskenta.inc");

		return $uusikehahin;
	}
}

if (!function_exists("tuotteen_myyntihinta")) {
	function tuotteen_myyntihinta ($laskurow, $trow, $kpl) {
		global $yhtiorow, $kukarow;

		// palautetaan tuotteen SVH laskun valuutassa
		// tämä funktion on kopsattu alehinta.incistä sopivilta osilta
		$hinta 			= 0;
		$netto			= "";
		$valuutta		= "";

		// 5. hinnasto.hinta tuotteen nettohinta hinnastosta laskun valuutassa
		if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

			$query = "	SELECT *
						FROM hinnasto
						WHERE yhtio  = '$kukarow[yhtio]'
						and tuoteno  = '$trow[tuoteno]'
						and tuoteno != ''
						and laji in ('N', 'E')
						and valkoodi = '$laskurow[valkoodi]'
						and maa in ('$laskurow[maa]','')
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), maa desc
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow       	= mysql_fetch_array ($hresult);
				$hinta 			= $hrow["hinta"];
				$netto			= $hrow["laji"];
				$valuutta		= $hrow["valkoodi"];
			}
		}

		// 6. hinnasto.hinta tuotteen nettohinta hinnastosta yhtiön valuutassa
		if ($hinta == 0) {

			$query = "	SELECT *
						FROM hinnasto
						WHERE yhtio  = '$kukarow[yhtio]'
						and tuoteno  = '$trow[tuoteno]'
						and tuoteno != ''
						and laji in ('N', 'E')
						and valkoodi in ('$yhtiorow[valkoodi]','')
						and maa in ('$laskurow[maa]','')
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow       	= mysql_fetch_array ($hresult);
				$hinta 			= $hrow["hinta"];
				$netto			= $hrow["laji"];
				$valuutta		= $hrow["valkoodi"];
			}
		}

		// 15. tuote.nettohinta (tuotteen nettohinta)
		if ($hinta == 0 and $trow['nettohinta'] > 0) {

			$hinta 			= $trow['nettohinta'];
			$netto 			= 'N';
			$valuutta		= $yhtiorow["valkoodi"];
		}

		// 16. hinnasto.hinta tuotteen bruttohinta hinnastosta laskun valuutassa
		if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

			$query = "	SELECT *
						FROM hinnasto
						WHERE yhtio  = '$kukarow[yhtio]'
						and tuoteno  = '$trow[tuoteno]'
						and tuoteno != ''
						and laji     = ''
						and valkoodi = '$laskurow[valkoodi]'
						and maa in ('$laskurow[maa]','')
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), maa desc
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow			= mysql_fetch_array ($hresult);
				$hinta 			= $hrow["hinta"];
				$netto			= $hrow["laji"];
				$valuutta		= $hrow["valkoodi"];
			}
		}

		// 17. hinnasto.hinta tuotteen bruttohinta hinnastosta yhtion valuutassa
		if ($hinta == 0) {

			$query = "	SELECT *
						FROM hinnasto
						WHERE yhtio  = '$kukarow[yhtio]'
						and tuoteno  = '$trow[tuoteno]'
						and tuoteno != ''
						and laji     = ''
						and valkoodi in ('$yhtiorow[valkoodi]','')
						and maa in ('$laskurow[maa]','')
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow			= mysql_fetch_array ($hresult);
				$hinta 			= $hrow["hinta"];
				$netto			= $hrow["laji"];
				$valuutta		= $hrow["valkoodi"];
			}
		}

		// 18. tuote.myyntihinta (tuotteen bruttohinta)
		if ($hinta == 0) {

			$hinta		= $trow['myyntihinta'];
			$netto 		= '';
			$valuutta	= $yhtiorow["valkoodi"];
		}

		if ($valuutta == "") $valuutta = $yhtiorow["valkoodi"];

		if ($laskurow["valkoodi"] != $valuutta) {
			$hinta = laskuval($hinta, $laskurow["vienti_kurssi"]);
		}

		return $hinta;

	}

}

if (!function_exists("kalenteritapahtuma")) {
	function kalenteritapahtuma ($tyyppi, $tapa, $viesti, $liitostunnus, $kuittaus="", $henkilo="", $otunnus="") {
		global $yhtiorow, $kukarow;

		$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$asrow = mysql_fetch_array($result);

		//Tehdään asiakasmemotapahtuma jos se on tarpeellinen
		$kysely = "	INSERT INTO kalenteri
					SET tapa 		= '$tapa',
					asiakas  	 	= '$asrow[ytunnus]',
					liitostunnus	= '$liitostunnus',
					henkilo  		= '$henkilo',
					kuka     		= '$kukarow[kuka]',
					yhtio    		= '$kukarow[yhtio]',
					tyyppi   		= '$tyyppi',
					pvmalku  		= now(),
					otunnus  		= '$otunnus',
					kuittaus  		= '$kuittaus',
					kentta01 		= '$viesti'";
 		$result = mysql_query($kysely) or pupe_error($kysely);
	}
}

if (! function_exists('ebid')) {
	function ebid($lasku_tunnus, $url_only = false) {
		global $kukarow, $yhtiorow, $palvelin2;

		$query = "SELECT * from lasku where tunnus=" . (int) $lasku_tunnus . " and yhtio='{$kukarow['yhtio']}'";
		$res = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($res);

		if ($laskurow['ebid'] != "") {

			if (substr($laskurow['ebid'], 0, 1) == '/') {

				// otetaan palvelin muuttujasta mahdolliset turhat alut pois
				$alut    = array("http://","https://");
				$apunimi = str_replace($alut, "", $yhtiorow['lasku_polku_http']);

				if ($_SERVER["HTTPS"] == "on") {
					$url = "https://$apunimi".$laskurow["ebid"];
				}
				else {
					$url = "http://$apunimi".$laskurow["ebid"];
				}

				if ($url_only) {
					return $url;
				}

				return "<a href='$url'>". t('Näytä lasku')."</a>";
			}

			$ebid = $laskurow['ebid'];

			$verkkolaskutunnus = $yhtiorow['verkkotunnus_vas'];
			$salasana		   = $yhtiorow['verkkosala_vas'];

			$timestamppi=gmdate("YmdHis")."Z";

			$urlhead = "http://www.verkkolasku.net";
			$urlmain = "/view/ebs-2.0/$verkkolaskutunnus/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$ebid&TIMESTAMP=$timestamppi&VERSION=ebs-2.0";

			$digest	 = md5($urlmain . "&" . $salasana);
			$url	 = $urlhead.$urlmain."&DIGEST=$digest";

			if ($url_only) {
				return $url;
			}

			return "<a href='$url'>". t('Näytä lasku')."</a>";
		}
		else {

			$query = "SELECT tunnus from liitetiedostot where liitostunnus='{$laskurow['tunnus']}' and liitos='lasku' and yhtio='{$kukarow['yhtio']}'";
			$res = mysql_query($query) or pupe_error($query);

			$out = '';
			while ($row = mysql_fetch_array($res)) {
				$out .= "<a $target href='".$palvelin2."view.php?id={$row['tunnus']}'>". t('Näytä lasku') ."</a> ";
			}

			if ($out != '') {
				return $out;
			}

			return t('Paperilasku');
		}
	}
}

if (! function_exists('ta')) {
	function ta($kieli, $laji, $selite, $return = "selite", $selitetark="", $selitetark_2 = "", $selitetark_3 = "") {
		global $kukarow, $yhtiorow;

		//	Jos kielet matchaa niin palautetaan suoraan vastaus
		if($kieli == $yhtiorow["kieli"] and $return == "selite") {
			return $selite;
		}

		$lisa = "";
		if($selitetark != "") {
			$lisa .= " and avainsana.selitetark = '$selitetark'";
		}

		if($selitetark_2 != "") {
			$lisa .= " and avainsana.selitetark_2 = '$selitetark_2'";
		}

		if($selitetark_3 != "") {
			$lisa .= " and avainsana.selitetark_3 = '$selitetark_3'";
		}

		//	Tutkitaan onko käännös
		$query = "	SELECT a.*
					FROM avainsana
					RIGHT JOIN avainsana a ON avainsana.yhtio = a.yhtio and avainsana.laji = a.laji and avainsana.perhe=a.perhe and a.kieli = '$kieli'
					WHERE avainsana.yhtio = '{$kukarow["yhtio"]}' and avainsana.laji = '$laji' and avainsana.kieli = '{$yhtiorow["kieli"]}' and avainsana.selite = '$selite' $lisa and avainsana.perhe>0";
		$result = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result) == 1) {
			$row = mysql_fetch_array($result);
			return $row[$return];
		}
		else {
			return $selite;
		}
	}
}

if(!function_exists("size_readable")) {
	function size_readable($size) {

		$units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');

	    $i = 0;
	    while ($size >= 1024) {
	        $size /= 1024;
	        $i++;
	    }

	    return round($size, 2).$units[$i];
	}
}

if(!function_exists("sarjanumeron_ostohinta")) {
	function sarjanumeron_ostohinta($kentta, $arvo) {
		global $kukarow, $yhtiorow;

		// Funktio laskee yhden kappaleen ostohinnan

		$ostorivit = "";
		$ostohinta = 0;

		// Tuotteen ostohinta
		if ($kentta != "ostorivitunnus") {
			$query = "	SELECT group_concat(distinct tilausrivi.tunnus) tunnukset
						FROM sarjanumeroseuranta
						JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
						WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
						and sarjanumeroseuranta.$kentta = '$arvo'";
			$otsores = mysql_query($query) or pupe_error($query);
			$ostorow = mysql_fetch_array($otsores);

			$ostorivit = $ostorow["tunnukset"];
		}
		else {
			$ostorivit = $arvo;
		}

		if($ostorivit != '') {
			$query = "	SELECT
						round(if(tilausrivi.kpl!=0, tilausrivi.rivihinta/tilausrivi.kpl, 0), 2) ostosumma,
						round(tilausrivi.hinta * (1-(tilausrivi.ale/100)), 2) ostosumma_eiloppulaskettu
						FROM tilausrivi
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tunnus  IN ($ostorivit)";
			$sarjares = mysql_query($query) or pupe_error($query);
			$sarjarow = mysql_fetch_array($sarjares);

			$ostohinta = (float) $sarjarow["ostosumma"];

			if ($ostohinta == 0 and $sarjarow["ostosumma_eiloppulaskettu"] != 0) {
				$ostohinta = (float) $sarjarow["ostosumma_eiloppulaskettu"];
			}

			// Katsotaan onko sarjanumerolle liitetty kulukeikka
			$query  = "	SELECT lasku.laskunro
						FROM sarjanumeroseuranta
						JOIN lasku ON lasku.yhtio=sarjanumeroseuranta.yhtio and lasku.liitostunnus=sarjanumeroseuranta.tunnus and lasku.ytunnus=sarjanumeroseuranta.tunnus and lasku.tila = 'K' and lasku.alatila = 'S'
						WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
						and sarjanumeroseuranta.$kentta = '$arvo'";
			$keikkares = mysql_query($query) or pupe_error($query);

			while($kulukeikkarow = mysql_fetch_array($keikkares)) {
				// Haetaan kaikki keikkaan liitettyjen laskujen summa
				$query = "	SELECT round(sum(arvo*if(maksu_kurssi!=0, maksu_kurssi, vienti_kurssi)),2) kulusumma
							FROM lasku
							WHERE yhtio		= '$kukarow[yhtio]'
							and tila 		= 'K'
							and laskunro 	= '$kulukeikkarow[laskunro]'
							and vanhatunnus <> 0
							and vienti in ('B','E','H')";
				$result = mysql_query($query) or pupe_error($query);

				$kulukulurow = mysql_fetch_array($result);

				$ostohinta	+= $kulukulurow["kulusumma"];
			}
		}

	    return $ostohinta;
	}
}

if(!function_exists("remove_duplicates")) {
	function remove_duplicates($table, $yhtio) {

		$query  = "describe $table";
		$fieldresult = mysql_query($query) or pupe_error($query);

		$group = "";

		while ($fields = mysql_fetch_array($fieldresult)) {
			if ($fields[0] != "tunnus" and
				$fields[0] != "laatija"	and
				$fields[0] != "luontiaika" and
				$fields[0] != "muutospvm" and
				$fields[0] != "muuttaja") {

				$group .= $fields[0].",";
			}
		}

		$group = substr($group, 0, -1);

		$query = "	SELECT $group, count(*) countkpltahti, group_concat(tunnus) tunnukset
					FROM $table
					WHERE yhtio = '$yhtio'
					GROUP BY $group
					HAVING countkpltahti > 1";
		$result = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($result)) {
			$query = "DELETE FROM $table WHERE yhtio = '$yhtio' and tunnus in ($row[tunnukset]) LIMIT ".($row["countkpltahti"]-1);
			$delresult = mysql_query($query) or pupe_error($query);
		}
	}
}

if (!function_exists("ostolaskun_vienti")) {
	function ostolaskun_vienti($vienti) {
		switch ($vienti) {
			case 'A':
		  		$cVal = t("Kotimaa");
				break;
			case 'B':
				$cVal = t("Kotimaa huolinta/rahti");
				break;
			case 'C':
				$cVal = t("Kotimaa vaihto-omaisuus");
				break;
			case 'J':
				$cVal = t("Kotimaa raaka-aine");
				break;
			case 'D':
				$cVal = t("EU");
				break;
			case 'E':
				$cVal = t("EU huolinta/rahti");
				break;
			case 'F':
				$cVal = t("EU vaihto-omaisuus");
				break;
			case 'K':
				$cVal = t("EU raaka-aine");
				break;
			case 'G':
				$cVal = t("ei-EU");
				break;
			case 'H':
				$cVal = t("ei-EU huolinta/rahti");
				break;
			case 'I':
				$cVal = t("ei-EU vaihto-omaisuus");
				break;
			case 'L':
				$cVal = t("ei-EU raaka-aine");
				break;	
				
			default:
				$cVal = "";
		}
		
		return $cVal;
	}
}

/*
	Functio jolla voidaan syncronoida toimitusten sisältöjä
*/

if(!function_exists("paivita_toimitukset")) {
	function paivita_toimitukset($otunnus, $originaali) {
		global $kukarow, $yhtiorow;
				
		foreach(array("lasku","laskun_lisatiedot") as $taulu) {
			
			if(!is_array($originaali[$taulu]) or (int) $otunnus == 0) {
				echo "<font class='error'>".t("Toimitusten päivittäminen EPÄONNISTUI")."!</font><br>";
				return false;
			}
			
			if($taulu == "lasku") {
				$where = " and tunnus = '$otunnus'";
			}
			else {
				$where = " and otunnus = '$otunnus'";
			}
			
			$query = "	SELECT *
						FROM $taulu
						WHERE yhtio = '$kukarow[yhtio]' $where";
			$result = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($result) == 1) {
				$uusirow = mysql_fetch_array($result);

				$diffi = array_diff_assoc($uusirow, $originaali[$taulu]);

				if(count($diffi) > 0 and is_array($diffi)) {
					
					//	Poistetaan numeeriset avaimet
					$diff = array();
					foreach($diffi as $key => $value) {
						if(!is_numeric($key) and !in_array("tila", "alatila")) {
							$diff[$key] = $value;
						}
					}
					
					//	Haetaan tunnukset jotka voidaan päivittää
					if($taulu == "lasku") {
						$query = "	SELECT *
									FROM lasku
									WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$otunnus' and tila IN ('L','N') and alatila != 'X' and tunnusnippu > 0";
					}
					else {
						$query = "	SELECT laskun_lisatiedot.*
									FROM lasku
									JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
									WHERE lasku.yhtio = '$kukarow[yhtio]' and tunnusnippu = '$otunnus' and tila IN ('L','N') and alatila != 'X' and tunnusnippu > 0";
					}
					
					$result = mysql_query($query) or pupe_error($query);
					
					if(mysql_num_rows($result) > 0) {

						//	Rullataan kaikki toimitukset läpi
						while($row = mysql_fetch_array($result)) {

							//	Rullataan kaikki muutokset läpi..
							$updquery = "";
							foreach($diff as $col => $value) {

								//	Luodaan päivitettävistä sarakkeista kysely..
								if($row[$col] == $originaali[$taulu][$col]) {
									if($updquery != "") {
										$updquery .=", ";
									}
									$updquery .= " $col = '$value'"; 

									//	Poikkeus joka vahvistaa säännön..
									if(in_array($col, array("kerayspvm", "toimaika")) and $taulu == "lasku") {
										$query = "	UPDATE tilausrivi
													SET $col = '$value'
													WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$row[tunnus]' and $col = '".$originaali[$taulu][$col]."'";
										$res = mysql_query($query) or pupe_error($query);
									}
								}
							}

							if($updquery != "") {
								$query = "UPDATE $taulu SET $updquery WHERE yhtio = '$kukarow[yhtio]' and tunnus = '{$row["tunnus"]}'";
								$updres = mysql_query($query) or pupe_error($query);
							}
						}
					}
				}
			}
		}
		
		return true;
	}	
}

if(!function_exists("enable_ajax")) {
	function enable_ajax() {
		global $palvelin2, $kukarow;

		if($kukarow["extranet"] != "") {
			$imgUrl = $palvelin2."loading_blue_small.gif";
		}
		else {
			$imgUrl = $palvelin2."pics/loading_blue_small.gif";
		}

		/*
		tätä voidaan käyttää koodista sitte näin:
		echo "<a href='javascript:sndReq(\"123\", \"tuote.php?tee=Z&tuoteno=a2\")'>tuotekysely a2</a><br>";
		echo "<br><br><div id='123'></div>";
		*/
?>

<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">
<!--
	var toggeled = Array();
	function AJAXInteraction(urli, id, data, href) {
		
		var http = createRequestObject();
		http.onreadystatechange = handleResponse;
		
		function createRequestObject() {
			var ro;
			var browser = navigator.appName;

			if (browser == "Microsoft Internet Explorer") {
				ro = new ActiveXObject("Microsoft.XMLHTTP");
			}
			else {
				ro = new XMLHttpRequest();
			}
			return ro;
		}

		function handleResponse() {
			if (http.readyState == 4) {
				var response = http.responseText;
				document.getElementById(id).innerHTML = response;
			}
		}
		
		this.doPost = function() {
			http.open('post', urli, true);
			http.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			http.send(data);
		}
		
		this.doGet = function() {			
			http.open('get', urli + '&' + data);
			http.send(null);			
		}
		
		this.chgHref = function() {
			document.getElementById(href).href = 'javascript:toggleGroup("' + id + '");';
			toggeled.push(id);
		}

		this.unToggle = function() {
			unToggle(id);
			document.getElementById(href).href = document.getElementById(href).href+' unToggle("' + id + '");';
		}
				
		this.showLoader = function() {
			document.getElementById(id).innerHTML = "<div style='filter:alpha(opacity=0); -moz-opacity:0.7; opacity: 0.7; position: absolute; width: 100%; height: 100%; top: 0; left: 0; background-color: #0c0c0c;' z-index: 100><img style='position: absolute; top: 50%; left: 50%;' src='<?php echo $imgUrl; ?>'></div>";
		}
		
		this.visible = function() {
			document.getElementById(id).style.display = 'block';
		}
	}

	function sndReq(div, urli, href, load, unToggle, method, data) {
				
		//	Luodaan uudi instanssi
		var ai = new AJAXInteraction(urli, div, data, href);

		//	Näytetään latausanimaatio
		if(load) {
			ai.showLoader();
		}

		//	Jos päivitetään togglegrouppaus
		if(href) {
			ai.chgHref();
		}

		if(unToggle) {
			ai.unToggle();
		}

		//	Otetaan yhteydet
		if(method == 'post') {
			ai.doPost();
		}
		else {
			ai.doGet();
		}
		
		//	Varmistetaan näkyvyys
		ai.visible();
		
	}

	function unToggle(id) {
		for (x in toggeled) {
			if(toggeled[x] != id) {
				document.getElementById(toggeled[x]).style.display = 'none';
			}
		}
	}

	function toggleGroup(id) {
		
		if (document.getElementById(id).style.display != 'none') {
			document.getElementById(id).style.display = 'none';
		}
		else {
			document.getElementById(id).style.display = 'block';
		}
	}

	function ajaxPost(formID, urli , minne, href, load, unToggle, method) {

		obj = document.getElementById(formID);
		getstr = "";

 		for (i=0; i<obj.length; i++) {
			if (obj.elements[i].tagName == "INPUT" && obj.elements[i].value != "") {
				if (obj.elements[i].type == "text" || obj.elements[i].type == "hidden") {
					getstr += obj.elements[i].name + "=" + encodeURI(obj.elements[i].value) + "&";
				}
				else if (obj.elements[i].type == "checkbox") {
					if (obj.elements[i].checked) {
						getstr += obj.elements[i].name + "=" + encodeURI(obj.elements[i].value) + "&";
					}
					else {
						getstr += obj.elements[i].name + "=&";
					}
				}
				else if (obj.elements[i].type == "radio") {
					if (obj.elements[i].checked) {
						getstr += obj.elements[i].name + "=" + encodeURI(obj.elements[i].value) + "&";
					}
				}
			}
			if (obj.elements[i].tagName == "SELECT") {
				var sel = obj.elements[i];
				getstr += sel.name + "=" + sel.options[sel.selectedIndex].value + "&";
			}
		}

		sndReq(minne, urli, href, load, unToggle, method, getstr);
	}

//-->
</script>

<?php
	}
}

if(!function_exists("js_yllapito")) {
	function js_yllapito() {
?>
<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">
<!--
function yllapito(url, tag, submitOnSelect) {
	select=document.getElementById(tag);

	toim=url.substr(0,url.indexOf("&"));
	valinta=select.options[select.selectedIndex].value;
	
	//	Jos meillä on valintana uusi, siirrytään tekemään uusi tietue!
	if(isNaN(valinta)) {

		var width=0;
		var height=0;

		//	Koitetaan tehdä aina kaunis ja sopiva poppari
		switch (toim) {
			case 'yhteyshenkilo':
				width=400;
				height=500;
				break;

			case 'asiakkaan_positio':
				width=380;
				height=450;
				break;
			case 'asiakkaan_kohde':
				width=400;
				height=400;
				break;
		}

		if(height==0 || width==0) {
			alert('Ylläpitotoimintoa '+toim+' ei tunneta!');
		}
		else {
			//	Tehdään uutta
			if(valinta=='uusi') {
				newwindow=window.open('../yllapito.php?popparista=JOO&uusi=1&suljeYllapito='+tag+'&toim='+url, 'yllapito', 'width='+height+',height='+height+',top=100,left=100,scrollbars=no,resizable=yes');
			}
			//	Muokataan valittua
			else if (valinta.indexOf('\muokkaa#[0-9]+\b')) {
				tunnus=valinta.slice(valinta.indexOf("#")+1);
				newwindow=window.open('../yllapito.php?popparista=JOO&tunnus='+tunnus+'&suljeYllapito='+tag+'&toim='+url, 'yllapito', 'width='+height+',height='+height+',top=100,left=100,scrollbars=no,resizable=yes');
			}
			else {
				//alert('DONT FUCK THE SYSTEM \"'+valinta+'\"');
			}
		}
	}
	else {

		// Onko meillä muokkaus?
		sain='en';
		for(i=0;i<=select.options.length-1;i++) {
			if(select.options[i].value.substring(0,7)=='muokkaa') {
				if(valinta=='') {
					select.options[i]=null;
					sain='JOO';
				}
				else {
					select.options[i].value='muokkaa#'+valinta;
					sain='JOO';
				}
			}
		}

		//	Joudutaan duusaamaan kokonaan uusi..
		if(sain=='en') {
			
			var newOpt=document.createElement('OPTION');
			
			newOpt.text='Muokkaa';
			newOpt.value='muokkaa#'+valinta;
			
			select.appendChild(newOpt);

		}
		
		 if (submitOnSelect) {	
			document.forms[submitOnSelect].submit();
		}
	}
}
function suljeYllapito(sID,value,text) {

	//	Wanhaa on muokattu, submittoidaan formi tarvittaessa..
	if(sID.substring(0,2)=='P_') {
		sID=sID.substr(2);

		sel=window.opener.document.getElementById(sID);
		//	merkataan oikea valituksi
		for (i=0;i<sel.length;i++) {
			if(sel.options[i].value==value) {
				sel.selectedIndex=i;
				sel.options[i].text=text;
			}
		}

		if(sID=='asiakkaan_positio') {
			window.opener.document.forms[sID].submit();
		}
		window.close();
	}
	else {

		//	Paivitetaan ja valitaan select option
		var newOpt=document.createElement('option');
		newOpt.text=text;
		newOpt.value=value;

		//	Jos päivitettiin yhteyshenkilöitä meidän pitää listätä ne kaikkiin valikkoihin..
		sela='EI'
		if(sID=='yhteyshenkilo_kaupallinen') {
			sela=window.opener.document.getElementById('yhteyshenkilo_tekninen');
		}
		else if (sID=='yhteyshenkilo_tekninen') {
			sela=window.opener.document.getElementById('yhteyshenkilo_kaupallinen');
		}

		if(sela!='EI') {
			var newOpt2=document.createElement('option');
			newOpt2.text=text;
			newOpt2.value=value;

			try {
				sela.add(newOpt2, sela.options[1]);
			}
			catch(ex) {
				sela.add(newOpt2, 1);
			}
		}

		sel=window.opener.document.getElementById(sID);

		try {
			sel.add(newOpt, sel.options[1]);
		}
		catch(ex) {
			sel.add(newOpt, 1);
		}

		//	Valitaan uusi arvo
		sel.selectedIndex=1;



		window.close();

	}
}
//-->
</script>
<?php
	}
}

if(!function_exists("js_toimehtoTarkenne")) {
	function js_toimehtoTarkenne() {
?>
<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">
<!--
function toimehtoTarkenne(toimehto) {
	tehto=document.getElementById(toimehto);
	tehtoLisa=document.getElementById(toimehto+'Lisa');

	teksti=tehto.options[tehto.selectedIndex].text;
	arvo=tehto.options[tehto.selectedIndex].value;

	i=teksti.indexOf("-");
	if(i>0) {
		tarkenne=teksti.substr((i+1));
		tehtoLisa.value=tarkenne;
	}
	else {
		tehtoLisa.value='';
	}
}
//-->
</script>
<?php
	}
}

if (!function_exists("js_popup")) {
	// scripti balloonien tekemiseen
	function js_popup () {

?>
<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">
<!--

function getMouseX(e) {
	var x = null;
	if (e.pageX) 	{
		x = e.pageX;
	}
	else if (e.clientX) 	{
		x = e.clientX + document.body.scrollLeft
			+ document.documentElement.scrollLeft;
	}
	return x;
}
function getMouseY(e) {
	var y = null;
	if (e.pageY) 	{
		y = e.pageY;
	}
	else if (e.clientY) 	{
		y = e.clientY + document.body.scrollTop
			+ document.documentElement.scrollTop;
	}

	return y;
}
function popUp(e, oi) {
	var de = document.getElementById(oi);
	var ds = de.style;

	if (e.type == "mouseout") {
		ds.display = "none";
		ds.visibility = "hidden";
	}
	else {

		ds.display = "block";

		//	Detectoidaan ikkunan koko
		var ww = window.innerWidth != null? window.innerWidth: document.body.clientWidth != null? document.body.clientWidth:null;
		var wh = window.innerHeight != null? window.innerHeight: document.body.clientHeight != null? document.body.clientHeight:null;

		//	Detectoidaan div:n koko
		if(de.clientHeight) eh = de.clientHeight;
		else if (de.clip.height) eh = de.clip.height;
		else eh = null;

		if(de.clientWidth) ew = de.clientWidth;
		else if (de.clip.width) ew = de.clip.width;
		else ew = null;

		// relatiivinen sijainti
		var y = getMouseY(e)+10;
		var x = getMouseX(e)+10;

		var scrollY = document.body.scrollTop + document.documentElement.scrollTop;
		var scrollX = document.body.scrollLeft + document.documentElement.scrollLeft;

		//	Jos saimme riittävästi tietoa voimme kalkuloida oikean position
		if(eh != null && ew != null && x != null && y != null) {

			c=0;
			//	Riittääkö leveys
			if(x+ew-2>ww) {
				c=x+ew-ww+2;
				x -= c;
			}

			if (wh - y - eh < -5 && c > 10) {
				y -= eh + 22;
			}
			else if(y - scrollY + eh + 2 > wh) {
				y -= y - scrollY + eh - wh + 20;
			}

			//	Oikea laita on kuitenkin aina tärkein!
			if(x<2) {
				x=2;
			}
			if(y<2) {
				y=2;
			}
		}

		ds.left=x;
		ds.top=y;

		ds.visibility = "visible";
	}
}
//-->
</script>
<?php

	}
}

?>
