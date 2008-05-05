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

		if ($date == "0000-00-00" or $date == "0000-00-00 00:00:00" or $date == "") {
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
		global $yhtiorow, $kukarow, $useslave, $link, $verkkokauppa;

		require("salasanat.php");

		$useslave = (int) $useslave;

		if (isset($slavedb)) {
			if ($slavedb[$useslave] == '') $useslave = 0;
		}
		else {
			$useslave = 0;
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

		if ($useslave > 0 or $indeksi != 'fi') { // Voi olla, että käytämme slavea ja INSERT ei siis onnistu (insert vain jos ei fi)
			$link = mysql_connect ($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
			mysql_select_db ($dbkanta) or die ("Tietokantaa $dbkanta löydy palvelimelta (functions.inc 1)!");
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
					if ($indeksi == 'ru') {
						$stringi = t("$stringi","EN");
					}
					else {
						$stringi = $sanakirjarow["fi"];
					}
				}

				//Päivitetään aikaleima
				if ($verkkokauppa == "") {
					$sanakirjaqueryupd  = "UPDATE sanakirja SET aikaleima=now(), kysytty=kysytty+1 WHERE tunnus='$sanakirjarow[tunnus]'";
					$sanakirjaresultupd = mysql_query($sanakirjaqueryupd, $link) or pupe_error($sanakirjaqueryupd);
				}
			}
			elseif ($verkkokauppa == "") {
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
		// tarvitaan yhtiörowta, kukarowta ja tieto slaven:n käytöstä
		global $yhtiorow, $kukarow, $useslave, $link, $verkkokauppa;

		$query = sprintf("SELECT nimi FROM maat where koodi = '%s' LIMIT 1", mysql_real_escape_string(substr($code, 0 ,2)));
		$res = mysql_query($query) or pupe_error($query);
		$maa = mysql_fetch_array($res);

		// otetaan pois maa koodi
		if($kieli=="") {
			$kieli = $GLOBALS["kieli"];
		}
		if(function_exists("mb_strtolower")) {
			return ucfirst(mb_strtolower(substr(t($maa['nimi'], $kieli), 5)));
		}
		else {
			return ucfirst(strtolower(substr(t($maa['nimi'], $kieli), 5)));
		}
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

if (!function_exists("tuoteperhe_myytavissa")) {
	function tuoteperhe_myytavissa($tuoteno, $summaus, $tyyppi = '', $varasto = 0, $yhtio = '', $hyllyalue = '', $hyllynro = '', $hyllyvali = '', $hyllytaso = '', $maa = '', $pvm = '', $era = '') {
		global $kukarow, $yhtiorow;

		if ($yhtio == "") {
			$yhtio = $kukarow["yhtio"];
		}

		$varasto = (int) $varasto;

		$valinta1 = ""; // varaston tyyppi
		$valinta3 = "";

		// katotaan ollaanko annettu parametriksi joku tietty varastotyyppi
		if ($tyyppi == "E") {
			$valinta1 = " and varastopaikat.tyyppi = 'E' ";
			$valinta3 = " HAVING varastopaikat.tyyppi is not null ";
		}
		elseif ($tyyppi == "V") {
			$valinta1 = " and varastopaikat.tyyppi = 'V' ";
			$valinta3 = " HAVING varastopaikat.tyyppi is not null ";
		}
		elseif ($tyyppi == "KAIKKI") {
			$valinta1 = "";
			$valinta3 = "";
		}
		elseif ($tyyppi == "ORVOT") {
			$valinta1 = "";
			$valinta3 = " HAVING varastopaikat.tyyppi is null ";
		}
		else {
			$valinta1 = " and varastopaikat.tyyppi = '' ";
			$valinta3 = " HAVING varastopaikat.tyyppi is not null ";
		}

		// Katotaan halutaanko saldo vaan jostain tietystä varastosta (varastopaikat.tunnus), silloin unohdetaan edellä annettu tyyppi kokonaan
		if ($varasto != 0) {
			$valinta1 = " and varastopaikat.tunnus = '$varasto' ";
			$valinta3 = " HAVING varastopaikat.tyyppi is not null ";
		}

		$query = " 	SELECT *
					from tuote
					where tuote.yhtio = '$yhtio'
					and tuote.tuoteno = '$tuoteno'";
		$asires = mysql_query($query) or pupe_error($query);
		$asirow = mysql_fetch_array($asires);

		if (!function_exists("tuoteperhe_myytavissa_reku")) {
			function tuoteperhe_myytavissa_reku($yhtio, $tuoteno, $tuotteet, $tuotteet_str, $isat_array, $kaikki_array, $kerroin_array) {
				if (in_array($tuoteno, $isat_array)) {
					//echo "FUULI! TEET IKUISEN LUUPIN!!!!!!!!<br>";
				}
				else {
					$isat_array[] = $tuoteno;

					$query = " 	SELECT distinct tuote.tuoteno, tuoteperhe.kerroin, tuote.ei_saldoa
								from tuoteperhe
								join tuote on tuoteperhe.yhtio = tuote.yhtio and tuoteperhe.tuoteno = tuote.tuoteno
								where tuoteperhe.yhtio = '$yhtio'
								and isatuoteno = '$tuoteno'
								and tyyppi in ('','P')";
					$isiresult = mysql_query($query) or pupe_error($query);

					while ($isirow = mysql_fetch_array($isiresult)) {

						$kaikki_array[]  = $isirow["tuoteno"];
						$kerroin_array[] = $tuoteno."#!¡!#".$isirow["kerroin"];

						$mikaisa = $isirow["tuoteno"];
						$tmp_kaikki_array = $kaikki_array;
						$tmp_kerroin_array = $kerroin_array;

						krsort($tmp_kaikki_array);
						krsort($tmp_kerroin_array);

						$isirow["kerroin"] = 1;

						// Lasketaan kerroin rekursiivisesti taaksepäin
						foreach($tmp_kerroin_array as $ke_ind => $ke_kerr) {
							list($ker_isa, $ke_ker) = explode("#!¡!#", $ke_kerr);

							if ($ke_ker <= 0) {
								$ke_ker = 1;
							}

							if ($mikaisa == $tmp_kaikki_array[$ke_ind]) {
								$mikaisa = $ker_isa;
								$isirow["kerroin"] *= $ke_ker;
							}
						}

						if ($isirow["ei_saldoa"] == "") {
							$tuotteet[$isirow["tuoteno"]] = $isirow["kerroin"];
							$tuotteet_str 				 .= "'".$isirow["tuoteno"]."',";
						}
					}
				}

				return array($isat_array, $kaikki_array, $tuotteet, $tuotteet_str, $kerroin_array);
			}
		}

		$tuotteet = array();

		if ($asirow["ei_saldoa"] == "") {
			$tuotteet[$tuoteno] = 1;
			$tuotteet_str 		= "'".$tuoteno."',";
		}
		else {
			$tuotteet_str 		= "";
		}

		$riikoko 		= 1;
		$isat_array 	= array();
		$kaikki_array 	= array($tuoteno);
		$kerroin_array 	= array($tuoteno."#!¡!#1");

		for($isa=0; $isa < $riikoko; $isa++) {
			list($isat_array, $kaikki_array, $tuotteet, $tuotteet_str, $kerroin_array) = tuoteperhe_myytavissa_reku($yhtio, $kaikki_array[$isa], $tuotteet, $tuotteet_str, $isat_array, $kaikki_array, $kerroin_array);

			if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y") {
				$riikoko = count($kaikki_array);
			}
		}

		$tuotteet_str = substr($tuotteet_str, 0, -1);

		$query = "	SELECT distinct varastopaikat.tunnus, varastopaikat.nimitys, varastopaikat.tyyppi
		 			FROM tuote
					JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
					JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
					and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
					$valinta1
					WHERE tuote.yhtio = '$yhtio'
					and tuote.tuoteno in ($tuotteet_str)
					$valinta3
					ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys";
		$varresult = mysql_query($query) or pupe_error($query);

		$varasto_myytavissa = array();

		if ($summaus == "KAIKKI") {
			$perhe_myytavissa = array();
		}

		while ($saldorow = mysql_fetch_array($varresult)) {

			if ($summaus != "KAIKKI") {
				$perhe_myytavissa = array();
			}

			foreach($tuotteet as $tuoteno => $kerroin) {

				list(,,$myytavissa) = saldo_myytavissa($tuoteno, $tyyppi, $saldorow["tunnus"], $yhtio, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $maa, $pvm, $era);
				$perhe_myytavissa[$tuoteno] += round($myytavissa/$kerroin, 2);
			}

			if ($summaus != "KAIKKI") {
				arsort($perhe_myytavissa);
				$varasto_myytavissa[$saldorow["nimitys"]] = array_pop($perhe_myytavissa);
			}
		}

		if ($summaus == "KAIKKI") {
			arsort($perhe_myytavissa);
			$varasto_myytavissa["KAIKKI"] = array_pop($perhe_myytavissa);
		}

		return($varasto_myytavissa);
	}
}

if (!function_exists("saldo_myytavissa")) {
	function saldo_myytavissa($tuoteno, $tyyppi = '', $varasto = 0, $yhtio = '', $hyllyalue = '', $hyllynro = '', $hyllyvali = '', $hyllytaso = '', $maa = '', $pvm = '', $era = '') {

		// Tämä funktio palauttaa myytävissä olevan saldon sallituista varastoista (tyyppi='')
		// Laskuttamattomia hyvityksiä ei katsota myytäviksi vaan ne pitää laskuttaa ennenkuin näkyvät täällä (varattu > 0)
		global $kukarow, $yhtiorow;

		//Speciaalitapaus jossa JT-rivit varaavat saldoa
		$varaako_jt_saldoa = "";

		if ($tyyppi == "JTSPEC" and $yhtiorow["varaako_jt_saldoa"] != "") {
			$varaako_jt_saldoa = " and tilausrivi.var != 'J' ";
			$tyyppi = "";
		}

		$varasto = (int) $varasto;

		$query  = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
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
					$query = "	SELECT tyyppi_tieto, liitostunnus, toim_tuoteno
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
						$yhtiolisa	= $yhtio;
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

			$kerayslisa = "";
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
				/*
				// tuotteella ei ole yhtään paikkaa.. katotaan silti varatut
				// etsitään varatut kaikilta paikoilta
				$query = "	SELECT sum(varattu) varattu
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
							WHERE tilausrivi.yhtio = '$yhtiolisa' and
							tilausrivi.tyyppi in ('L','G','V') and
							tilausrivi.tuoteno = '$tuoteno' and
							tilausrivi.varattu > 0
							$varaako_jt_saldoa
							$kerayslisa ";
				$ennresult = mysql_query($query) or die($query);
				$ennrow = mysql_fetch_array($ennresult);

				$orposaldomyytavissa -= $ennrow["varattu"];

				return array(FALSE, FALSE, $orposaldomyytavissa, FALSE);
				*/

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
									$varaako_jt_saldoa
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
									$varaako_jt_saldoa
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
						// jos lasketaan tulevaisuuteen niin otetaan tietenkin huomioon jo kerätyt rivit
						if (($tuote["sarjanumeroseuranta"] == "E" or $tuote["sarjanumeroseuranta"] == "F") and $era != '') {
							$query = "	SELECT
										ifnull(sum(if(tilausrivi.keratty!='', tilausrivi.varattu, 0)),0) keratty
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
										and tilausrivi.kerayspvm > '$pvm'";
						}
						else {
							$query = "	SELECT
										ifnull(sum(if(tilausrivi.keratty!='', tilausrivi.varattu, 0)),0) keratty
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
										and tilausrivi.kerayspvm > '$pvm'";
						}

						$keraresult = mysql_query($query) or die($query);
						$kerarow = mysql_fetch_array($keraresult);

						$myytavissa -= $kerarow["keratty"];
						$hyllyssa -= $kerarow["keratty"];

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
						// löytyykö ostorivejä?
						$query = "	SELECT
									ifnull(sum(tilausrivi.varattu),0) varattu
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
									WHERE tilausrivi.yhtio 	= '$yhtiolisa'
									and tilausrivi.tyyppi 	= 'O'
									and (tilausrivi.perheid2 = 0 or tilausrivi.perheid2=tilausrivi.tunnus)
									and tilausrivi.tuoteno 	= '$tuoteno'
									and tilausrivi.varattu 	> 0
									and tilausrivi.toimaika <= '$pvm'";
						$osttarkres = mysql_query($query) or die($query);
						$osttarkrow = mysql_fetch_array($osttarkres);

						if ($osttarkrow['varattu'] != 0 and $hyllyalue != '') {
							// jos löytyy, niin onko ne tälle paikalle tulossa
							$query = "	SELECT
										ifnull(sum(tilausrivi.varattu),0) varattu
										FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
										WHERE tilausrivi.yhtio 	= '$yhtiolisa'
										and tilausrivi.tyyppi 	= 'O'
										and (tilausrivi.perheid2 = 0 or tilausrivi.perheid2=tilausrivi.tunnus)
										and tilausrivi.tuoteno 	= '$tuoteno'
										and tilausrivi.varattu 	> 0
										and tilausrivi.hyllyalue = '$hyllyalue'
										and tilausrivi.hyllynro  = '$hyllynro'
										and tilausrivi.hyllyvali = '$hyllyvali'
										and tilausrivi.hyllytaso = '$hyllytaso'
										and tilausrivi.toimaika <= '$pvm'";
							$osttarkres = mysql_query($query) or die($query);
							$osttarkrow = mysql_fetch_array($osttarkres);

							if ($osttarkrow['varattu'] == 0) {
								// jos ei ole, niin onko paikka olemassa jonne ne on tulossa
								$tarkquery = "	SELECT
											ifnull(sum(tilausrivi.varattu),0) varattu
											FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
											JOIN tuotepaikat ON tilausrivi.yhtio = tuotepaikat.yhtio and tilausrivi.tuoteno = tuotepaikat.tuoteno and tilausrivi.hyllyalue = tuotepaikat.hyllyalue and tilausrivi.hyllynro = tuotepaikat.hyllynro and tilausrivi.hyllyvali = tuotepaikat.hyllyvali and tilausrivi.hyllytaso = tuotepaikat.hyllytaso
											WHERE tilausrivi.yhtio 	= '$yhtiolisa'
											and tilausrivi.tyyppi 	= 'O'
											and (tilausrivi.perheid2 = 0 or tilausrivi.perheid2=tilausrivi.tunnus)
											and tilausrivi.tuoteno 	= '$tuoteno'
											and tilausrivi.varattu 	> 0
											and tilausrivi.toimaika <= '$pvm'";
								$osttarkres = mysql_query($tarkquery) or die($tarkquery);
								$osttarkrow = mysql_fetch_array($osttarkres);

								if ($osttarkrow['varattu'] == 0) {
									// eli on joutumassa ns. orvolle paikalle, niin leikitää että ne tulee oletuspaikalle
									$query = "	SELECT
												ifnull(sum(tilausrivi.varattu),0) varattu
												FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
												JOIN tuotepaikat ON tilausrivi.yhtio = tuotepaikat.yhtio and tilausrivi.tuoteno = tuotepaikat.tuoteno
												and tuotepaikat.hyllyalue = '$hyllyalue'
												and tuotepaikat.hyllynro = '$hyllynro'
												and tuotepaikat.hyllyvali = '$hyllyvali'
												and tuotepaikat.hyllytaso = '$hyllytaso'
												and tuotepaikat.oletus != ''
												WHERE tilausrivi.yhtio 	= '$yhtiolisa'
												and tilausrivi.tyyppi 	= 'O'
												and (tilausrivi.perheid2 = 0 or tilausrivi.perheid2=tilausrivi.tunnus)
												and tilausrivi.tuoteno 	= '$tuoteno'
												and tilausrivi.varattu 	> 0
												and tilausrivi.toimaika <= '$pvm'";
									$osttarkres = mysql_query($query) or die($query);
									$osttarkrow = mysql_fetch_array($osttarkres);
								}
							}
						}
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
									$varaako_jt_saldoa
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

// Tehdään valuuttamuunnos yhtiön valuutasta laskun valuuttaan.
if (!function_exists("kehahin")) {
	function kehahin($tuoteno) {

		global $kukarow, $yhtiorow;

		$query = "SELECT round(if(epakurantti100pvm='0000-00-00', if(epakurantti75pvm='0000-00-00', if(epakurantti50pvm='0000-00-00', if(epakurantti25pvm='0000-00-00', kehahin, kehahin*0.75), kehahin*0.5), kehahin*0.25), 0),6) kehahin
				  FROM tuote
				  WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result);
			return $row["kehahin"];
		}
		else {
			return 0;
		}
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

if(!function_exists("desim")) {
	function desim($summa) {
		global $kukarow, $yhtiorow;

		$monta = '';

		//echo "1 JOTAIN... $summa | $monta<br>";

		$summa =  str_replace(',','.',$summa);

		//echo "2 JOTAIN... $summa | $monta<br>";

		if ($yhtiorow['hintapyoristys'] > '2') {

			if (strpos($summa,'.')) {

				list($koko, $desi) = explode(".",trim($summa));

				//echo "3 JOTAIN... $summa | $monta |< $koko @ $desi ><br>";

				if (strlen($desi) > 2 and substr($desi,-1) == '0') {

					while (strlen($desi) > 2 and substr($desi,-1) == '0') {
						$desi = substr($desi,0,-1);
					}

					$monta = strlen($desi);

					//echo "4 JOTAIN... $summa | $monta |< $koko @ $desi ><br>";
				}
				elseif (strlen($desi) > 2) {
					$monta = strlen($desi);
				}
			}
		}

		if ($monta == '') {
			$monta = '2';
		}
		elseif ($monta > '4') {
			$monta = '4';
		}

		//echo "5 JOTAIN... $summa | $monta |< $koko @ $desi ><br>";

	    return $monta;
	}
}

if(!function_exists("desis")) {
	function desis($summa) {
		global $kukarow, $yhtiorow;

		$monta = '';

		//echo "1 JOTAIN... $summa | $monta<br>";

		$summa =  str_replace(',','.',$summa);

		//echo "2 JOTAIN... $summa | $monta<br>";

		if ($yhtiorow['hintapyoristys'] > '2') {

			if (strpos($summa,'.')) {

				list($koko, $desi) = explode(".",trim($summa));

				//echo "3 JOTAIN... $summa | $monta |< $koko @ $desi ><br>";

				if (strlen($desi) > 2 and substr($desi,-1) == '0') {

					while (strlen($desi) > 2 and substr($desi,-1) == '0') {
						$desi = substr($desi,0,-1);
					}

					$monta = strlen($desi);

					//echo "4 JOTAIN... $summa | $monta |< $koko @ $desi ><br>";
				}
				elseif (strlen($desi) > 2) {
					$monta = strlen($desi);
				}
			}
		}

		if ($monta == '') {
			$monta = '2';
		}
		elseif ($monta > '4') {
			$monta = '4';
		}

		$summa = sprintf("%.".$monta."f", $summa);

		//echo "5 JOTAIN... $summa | $monta |< $koko @ $desi ><br>";

	    return $summa;
	}
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
									if(tilausrivi.perheid > 0, ifnull((select concat(rpad(upper(t2.hyllyalue), 5, '0'),lpad(upper(t2.hyllynro), 5, '0'),lpad(upper(t2.hyllyvali), 5, '0'),lpad(upper(t2.hyllytaso), 5, '0'), t2.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus), if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))),
								   		if(tilausrivi.perheid2 > 0, ifnull((select concat(rpad(upper(t3.hyllyalue), 5, '0'),lpad(upper(t3.hyllynro), 5, '0'),lpad(upper(t3.hyllyvali), 5, '0'),lpad(upper(t3.hyllytaso), 5, '0'), t3.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus), if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))),
											tilausrivi.tuoteno))) as sorttauskentta";
		}

		// varastopaikkajärjestys, tuoteperheet pidetään yhdessä
		elseif ($jarjestys == "1") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno),
									if(tilausrivi.perheid > 0, ifnull((select concat(rpad(upper(t2.hyllyalue), 5, '0'),lpad(upper(t2.hyllynro), 5, '0'),lpad(upper(t2.hyllyvali), 5, '0'),lpad(upper(t2.hyllytaso), 5, '0'), t2.tuoteno, rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno)),
								   		if(tilausrivi.perheid2 > 0, ifnull((select concat(rpad(upper(t3.hyllyalue), 5, '0'),lpad(upper(t3.hyllynro), 5, '0'),lpad(upper(t3.hyllyvali), 5, '0'),lpad(upper(t3.hyllytaso), 5, '0'), t3.tuoteno, rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno)),
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
									if(tilausrivi.perheid > 0, ifnull((select concat(t2.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus), if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)),
										if(tilausrivi.perheid2 > 0, ifnull((select concat(t3.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus), if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)),
											tilausrivi.tuoteno))) as sorttauskentta";
		}

		// tuotenumerojärjestys, tuoteperheet pidetään yhdessä
		elseif ($jarjestys == "5") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, tilausrivi.tuoteno,
									if(tilausrivi.perheid > 0, ifnull((select concat(t2.tuoteno, tilausrivi.tuoteno) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus), tilausrivi.tuoteno),
										if(tilausrivi.perheid2 > 0, ifnull((select concat(t3.tuoteno, tilausrivi.tuoteno) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus), tilausrivi.tuoteno),
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
									if(tilausrivi.perheid > 0, ifnull((select concat(if(tt2.toim_tuoteno!='', concat(tt2.toim_tuoteno,tilausrivi.perheid), concat(t2.tuoteno,tilausrivi.perheid)), if(tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)) from tilausrivi as t2 JOIN tuotteen_toimittajat tt2 ON t2.yhtio = tt2.yhtio and t2.tuoteno = tt2.tuoteno and tt2.liitostunnus = '$toimittaja' where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus), if(tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)),
										if(tilausrivi.perheid2 > 0, ifnull((select concat(if(tt3.toim_tuoteno!='', concat(tt3.toim_tuoteno,tilausrivi.perheid2), concat(t3.tuoteno,tilausrivi.perheid2)), if(tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)) from tilausrivi as t3 JOIN tuotteen_toimittajat tt3 ON t3.yhtio = tt3.yhtio and t3.tuoteno = tt3.tuoteno and tt3.liitostunnus = '$toimittaja' where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus), if(tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)),
											if(tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)))) as sorttauskentta";
		}
		elseif ($jarjestys == "M") {
			$sorttauskentta = " ifnull((SELECT tlt2.jarjestys FROM tilausrivin_lisatiedot tlt2 WHERE tlt2.yhtio=tilausrivi.yhtio and tlt2.tilausrivitunnus=tilausrivi.tunnus LIMIT 1), tilausrivi.tunnus) as sorttauskentta";
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
			$abulisa = ereg(",(avainsana\|*([\|a-zA-Z_]*)),*", $yhtiorow["synkronoi"], $regs);

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
							if (isset($masterrow[$i]) and !in_array(mysql_field_name($masterres, $i), array("yhtio","tunnus","muuttaja","muutospvm","laatija","luontiaika"))) {
								if(isset($override[mysql_field_name($masterres, $i)])) {
									if ($override[mysql_field_name($masterres, $i)] != "") {
										$query .= ", ". mysql_field_name($masterres,$i)."='".$override[mysql_field_name($masterres, $i)]."' ";
									}
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

if (!function_exists("jalkilaskentafunktiolle_ostohinta")) {
	function jalkilaskentafunktiolle_ostohinta ($otunnus, $rivitunnus) {
		global $yhtiorow, $kukarow;

		// haetaan keikan otsikko laskurowhun
		$query  = "	SELECT *
					from lasku
					where tunnus = '$otunnus'
					and yhtio	 = '$kukarow[yhtio]'
					and ((tila='K' and alatila = 'X') or (tila='U' and alatila = 'X') or (tila='K' and alatila = 'I'))";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$laskurow = mysql_fetch_array($result);

			if ($laskurow["tila"] == "U") {
				// Tavara on ostettu sisään myyntilaskulla
				$query = "	SELECT round(tilausrivi.rivihinta/tilausrivi.kpl, 2) ohinta
							FROM tilausrivi
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]'
							and tilausrivi.tunnus = $rivitunnus";
				$presult = mysql_query($query) or pupe_error($query);
				$tilrivirow = mysql_fetch_array($presult);

				return $tilrivirow["ohinta"];
			}
			elseif ($laskurow["tila"] == "K" and $laskurow["alatila"] == "I") {
				// Tavara on ostettu sisään myyntilaskulla
				$query = "	SELECT round(tilausrivi.rivihinta/tilausrivi.kpl, 2) ohinta
							FROM tilausrivi
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]'
							and tilausrivi.tunnus = $rivitunnus";
				$presult = mysql_query($query) or pupe_error($query);
				$tilrivirow = mysql_fetch_array($presult);

				return $tilrivirow["ohinta"];
			}
			else {
				// Tavara on ostettu sisään keikalla


				// Virallinen laskenta, haetaan ihan kaikki rivit uudestaan (varattu=0) kaikki pitää olla viety varastoon jo ennen tätä..
				$query = "	SELECT tilausrivi.*, tilausrivi.kpl varattu
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus)
							LEFT JOIN tuotteen_toimittajat ON (tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = lasku.liitostunnus)
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]'
							and tilausrivi.varattu = 0
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and	tilausrivi.tyyppi = 'O'
							and tilausrivi.tunnus = $rivitunnus";
				$presult = mysql_query($query) or pupe_error($query);

				// Laskun valuttakurssi
				if ($laskurow["maksu_kurssi"] == 0) {	//tämä olisi huonompi juttu, mut ei mikään stopperi
					$laskurow["maksu_kurssi"] = $laskurow["vienti_kurssi"];
				}

				// Tsekataan vielä
				if ($laskurow["maksu_kurssi"] == 0) {	//tämä olisi huonompi juttu, mut ei mikään stopperi
					$laskurow["maksu_kurssi"] = 1;
				}

				// jos erikoisale niin otetaan sitä kanssa huomioon
				if ($laskurow['erikoisale'] <> 0) {
					$erikoisale = 1 - ($laskurow['erikoisale']/100);
				}
				else {
					$erikoisale = 1;
				}

				// Jos lasketaan virallista varastonarvoa, otetaan keikan summa huomioon (kululaskujen yhteenlaskettu summa OLETUSVALUUTASSA on laskurow.saldo_maksettu)
				$rahtikulut = $laskurow['saldo_maksettu'] + round($laskurow['rahti_etu'] * $laskurow['maksu_kurssi'], 2);

				while ($tilrivirow = mysql_fetch_array($presult)) {

					$query  = "	SELECT *
								from tuote
								LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]' and tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno)
								where tuote.yhtio	= '$kukarow[yhtio]'
								and tuote.tuoteno	= '$tilrivirow[tuoteno]'";
					$tuores = mysql_query($query) or pupe_error($query);
					$tuorow = mysql_fetch_array($tuores);

					if ($tuorow['tuotekerroin'] <= 0) $tuorow['tuotekerroin'] = 1;

					// jos kysessä on kotimainen vaihto-omaisuuslasku, pitää lisätä tuotteen hintaan alvi
					if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {
						$alvit = 1 + $tuorow["alv"] / 100;
					}
					else {
						$alvit = 1;
					}

					if ($laskurow['summa'] != 0) {
						$osuus   = round($erikoisale*$tilrivirow['hinta']*$alvit*abs($tilrivirow['varattu'])*(1-($tilrivirow['ale']/100))*$tuorow['tuotekerroin'],2)/$laskurow['summa'];	// kuinka paljon tämä rivi on koko tilauksesta
						$rahtios = $osuus*$rahtikulut;	// lasketaan sama osuus rahtikuluista tälle riville
					}
					else {
						$rahtios = 0;
					}

					if ($tilrivirow['varattu'] < 0) {
						$rahtios = $rahtios * -1;
					}

					$ohinta  = round($erikoisale*$tilrivirow['hinta']*(1-($tilrivirow['ale']/100))*$tuorow['tuotekerroin']*$laskurow['maksu_kurssi']*$tilrivirow['varattu']+$rahtios,6); // tuotteen rivihinta rahteineen OLETUSVALUUTASSA

					$tulliprossa = "";

					if ($tilrivirow["var"] == "" and $laskurow["maa"] != $yhtiorow["maa"]) {
						// lisätään tulli
						require ("tilauskasittely/taric_veroperusteet.inc");

						$ohinta = $ohinta * (1+($tulliprossa/100));
					}

					// lisätään riville extra kulu, jos sellanen oli annettu
					if ($tilrivirow["kate"] != 0) {
						$ohinta = $ohinta + $tilrivirow['kate'];
					}

					$rivihin = round($ohinta,$yhtiorow['hintapyoristys']);	// tilausrivin rivihinta talteen
					$ohinta  = round($ohinta / $tilrivirow['varattu'],2); 	// yhden tuotteen hinta kaikkine kuluineen

					return $ohinta;
				}
			}
		}
		else {
			return FALSE;
		}
	}
}


if (!function_exists("jalkilaskentafunktio")) {
	function jalkilaskentafunktio ($tuoteno, $pvm, $uusihinta, $rivitunnus) {
		global $yhtiorow, $kukarow, $jalkilaskenta_debug_text;

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
		// tämä funktion on kopsattu alehinta-funktiosta sopivilta osilta
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

if (!function_exists("alv")) {
	function alv ($laskurow, $trow, $hinta, $alv, $alehinta_alv) {
		global $yhtiorow, $kukarow;

		///* Sisään *///
		// $alv                			--> Käyttäjän syöttämä ALV
		// $hinta						--> alehinta-funktion laskema hinta
		// $trow[alv]           		--> Tuotteen ALV
		// $laskurow[vienti]   		 	--> Laskun tyyppi (kotimaa '', vientieu 'E' , vienti eieu 'K')
		// $laskurow[ytunnus]   		--> Laskunsaajan y-tunnus
		// $laskurow[tila]      		--> Laskun tila O=osto, muut tilat on myyntiä
		// $laskurow[alv]       		--> Laskun otsikolla oleva alv

		///* Ulos *///
		// $alv                			--> Uusi lakettu ALV
		// $hinta           			--> Uusi lakettu kappalehinta

		//yhtiön oletusalvi!
		$wquery = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
		$wtres  = mysql_query($wquery) or pupe_error($wquery);
		$wtrow  = mysql_fetch_array($wtres);

		// jos meillä on tuotteelta tuleva poikkeava tuotteen alv, käytetään sitä
		if ($alehinta_alv != 0) {
			$trow["alv"]     = $alehinta_alv;
			$wtrow["selite"] = $laskurow["alv"]; // otetaan "yhtiön oletus" laskulta, koska tässä keisissä siellä pitäs olla yhtiön oletus aina
		}

		if ($laskurow["tila"] == "O") {
			//Jos käyttäjä on valinnut drop-downista jonkun nollasta poikkeavan alvin, niin lasketaan sen verran alvia pois hinnasta
			// Oletuksena ostohinnat ovat tällä hetkellä ilman alvia
			if ($alv != 0) {
				$hinta = round($hinta / (1+$alv/100),$yhtiorow['hintapyoristys']);
			}

			$alv = 0; //ostotilaus --> ei alvia riveille
		}
		elseif ($alv >= 500) {
			//Tässä keississä on marginaalimyyntiä
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] == 0) $alv = 500;											// Otsikolla on valittu veroton myynti, esim Ahvenanmaan myntiä
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] == $wtrow["selite"]) $alv = $trow["alv"]+500;				// Otsikolla on valittu verollinen kotimaan myynti
			if ($laskurow["vienti"] == "E" and $laskurow["ytunnus"] != "") $alv = 500; 										// EU-vienti ytunnuksella --> alviton
			if ($laskurow["vienti"] == "K") $alv = 500; 																	// Vienti eu:n ulkopuolelle on aina verotonta

			$alv = (float) $alv;
		}
		elseif ($yhtiorow["alv_kasittely"] != "") {
			//Tässä keississä kaikki hinnat ovat aina verottomia ja vero lisätään vasta laskutuksessa
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] == 0) $alv = 0;												// Otsikolla on valittu veroton myynti, esim Ahvenanmaan myntiä
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] == $wtrow["selite"] and $alv == "") $alv = $trow["alv"];	// Otsikolla on valittu verollinen kotimaan myynti
			if ($laskurow["vienti"] == "E" and $laskurow["ytunnus"] != "") $alv = 0; 										// EU-vienti ytunnuksella --> alviton
			if ($laskurow["vienti"] == "K") $alv = 0; 																		// Vienti eu:n ulkopuolelle on aina verotonta

			$alv = (float) $alv;
		}
		else {
			//Tässä keississä kaikki hinnat sisältävät arvonlisäveron
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] == 0) $alv = 0;												// Otsikolla on valittu veroton myynti, esim Ahvenanmaan myntiä
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] == $wtrow["selite"] and $alv == "") $alv = $trow["alv"];	// Otsikolla on valittu verollinen kotimaan myynti
			if ($laskurow["vienti"] == "E" and $laskurow["ytunnus"] != "") $alv = 0; 										// EU-vienti ytunnuksella --> alviton
			if ($laskurow["vienti"] == "K") $alv = 0; 																		// Vienti eu:n ulkopuolelle on aina verotonta

			$alv = (float) $alv;
			$trow['alv'] = (float) $trow['alv'];

			// Jos alvit täsmäävät, ei tarvitse tehdä mitään. Muuten lasketaan uuden alvin sisältävä myyntihinta.
			if ($alv != $trow['alv']) {
				if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
					$hinta = round($hinta / (1+$trow['alv']/100) * (1+$alv/100), 6);
				}
				else {
					$hinta = round($hinta / (1+$trow['alv']/100) * (1+$alv/100), $yhtiorow['hintapyoristys']);
				}
			}
		}

		//echo "$trow[tuoteno], vienti '$laskurow[vienti]', otsikon alv '$laskurow[alv]', annettava alv '$alv', tuotteen alv '$trow[alv]', oikea kappalehinta '$hinta'<br>";

		return array($hinta, $alv);
	}
}

if (!function_exists("alehinta")) {
	function alehinta ($laskurow, $trow, $kpl, $netto, $hinta, $ale, $palautus="") {
		global $yhtiorow, $kukarow;

		// Tämä rutiini määrittelee riville hinnan ja alennuksen
		// siihen tarvitaan:
		// $laskurow[] (laskun tiedot)
		// $trow[] (select * from tuote)
		// $kpl tilatava määrä
		// $netto = N jos halutaan nettohinta
		// $hinta käyttäjän syöttämä hinta
		// $ale käyttäjän syöttämä ale
		// $debug (jos 1 niin näytetään tulos)
		// if ($yhtiorow["asiakashinta_netto"] == "") jos kenttä on tyhjä niin asiakashinnat ovat nettohintoja, muuten ovat ei-nettohintoja

		// Tulokset on:
		// $hinta (hinta)
		// $ale (aleprosentti)
		// $netto onko hinta nettohinta vai ei
		// $aperuste (selkokielinen teksti mihin päädyttiin)
		// $alehinta_alv jos on joku erikoialv tälle hinnaston tuotteelle
		// $alehinta_val hinnan valuutta
		// $hintaperuste hinnan peruste koodina
		// $aleperuste  alennuksen peruste koodina

		$aperuste 		= "";
		$alehinta_alv 	= 0;
		$hintaperuste	= false;
		$aleperuste		= false;

		// oletetaan yhtiön valuutta jos sitä ei tiedetä
		if ($laskurow["valkoodi"] == "") $laskurow["valkoodi"] = $yhtiorow["valkoodi"];

		// oletetaan tuotteen alvi ja valuutta
		$alehinta_val = $yhtiorow["valkoodi"];

		// jos meillä on lasku menossa ulkomaille
		if ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maa"]) {

			// tutkitaan ollaanko siellä alv-rekisteröity
			$query = "select * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and vat_numero != ''";
			$alhire = mysql_query($query) or pupe_error($query);

			// ollaan alv-rekisteröity, haetaan tuotteelle oikea ALV
			if (mysql_num_rows($alhire) == 1) {
				$query = "select * from tuotteen_alv where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and tuoteno='$trow[tuoteno]' limit 1";
				$alhire = mysql_query($query) or pupe_error($query);

				// ei löytynyt alvia, se on pakko löytyä
				if (mysql_num_rows($alhire) == 0) {
					$alehinta_alv = -999.99; // tää on näin että tiedetään että kävi huonosti ja ei anneta lisätä tuotetta
					$alv          = -999.99;
					$netto        = "";
					$hinta        = "0";
				}
				else {
					$alehi_alrow = mysql_fetch_array($alhire);
					$alehinta_alv = $alehi_alrow["alv"];
				}
			}
		}

		// haetaan asiakkaan tiedot
		$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
		$alhire = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($alhire) == 1) {
			$alehi_asrow = mysql_fetch_array($alhire);
		}
		else {
			$alehi_asrow = array();
			$aperuste .= t("Asiakasta ei löytynyt").". ";
		}

		// 1. käyttäjän syöttämä hinta/nettohinta
		if ($hinta != '') {
			$hintaperuste = 1;

			// nettohinta jos netto-kentässä tulee N tai E
			if ($netto == 'N' or $netto == 'E') {
				$aperuste .= "Käyttäjän antama nettohinta ";

				$ale = 0;
			}
			else {
				$aperuste .= "Käyttäjän antama hinta ";
			}

			$alehinta_val = $laskurow["valkoodi"];

			if (trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$hinta = round(yhtioval($hinta, $laskurow["vienti_kurssi"]), 6);
				$alehinta_val = $yhtiorow["valkoodi"];
			}
		}
		elseif ($hinta == '') {

			$hinta = 0;

			// 2. asiakas.tunnus/asiakas.ytunnus tuote.tuotenumero nettohinta (asiakkaan tuotteen hinta) laskun valuutassa
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika
							FROM asiakashinta USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and tuoteno  = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi = '$laskurow[valkoodi]'
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							UNION

							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika
							FROM asiakashinta USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi = '$laskurow[valkoodi]'
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							ORDER BY prio, aika
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_array ($hresult);


					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto = $hrow["laji"];
						$ale = 0;
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							$ale	= 0;
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							$ale	= '';
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							$ale	= 0;
						}
					}

					$hinta         = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste     .= "Asiakkaan tuotteen nettohinta laskun valuutassa. ";
					$alehinta_val  = $laskurow["valkoodi"];
					$hintaperuste = 2;
				}
			}

			// 3. asiakas.tunnus/asiakas.ytunnus tuote.tuotenumero nettohinta (asiakkaan tuotteen hinta) yhtiön valuutassa
			if ($hinta == 0) {

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, valkoodi
							FROM asiakashinta USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							UNION

							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, valkoodi
							FROM asiakashinta USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							ORDER BY prio, aika, valkoodi DESC
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_array ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto = $hrow["laji"];
						$ale = 0;
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							$ale	= 0;
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							$ale	= '';
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							$ale	= 0;
						}
					}

					$hinta		 = $hrow["hinta"];
					$aperuste	.= "Asiakkaan tuotteen nettohinta.".$hrow['laji'];
					$hintaperuste = 3;
				}
			}

			// 4. asiakas.tunnus/asiakas.ytunnus tuote.aleryhmä nettohinta (asiakkaan tuotealeryhmän hinta) laskun valuutassa
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika
							FROM asiakashinta USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi = '$laskurow[valkoodi]'
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							UNION

							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika
							FROM asiakashinta USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi = '$laskurow[valkoodi]'
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							ORDER BY prio, aika
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_array ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto = $hrow["laji"];
						$ale = 0;
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							$ale	= 0;
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							$ale	= '';
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							$ale	= 0;
						}
					}

					$hinta        = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste    .= "Asiakkaan tuotealeryhmän nettohinta laskun valuutassa. ";
					$alehinta_val = $laskurow["valkoodi"];
					$hintaperuste = 4;
				}
			}

			// 5. asiakas.tunnus/asiakas.ytunnus tuote.aleryhmä nettohinta (asiakkaan tuotealeryhmän hinta) yhtiön valuutassa
			if ($hinta == 0) {

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, valkoodi
							FROM asiakashinta USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							UNION

							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, valkoodi
							FROM asiakashinta USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							ORDER BY prio, aika, valkoodi DESC
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_array ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto = $hrow["laji"];
						$ale = 0;
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							$ale	= 0;
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							$ale	= '';
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							$ale	= 0;
						}
					}

					$hinta		= $hrow["hinta"];
					$aperuste	.= "Asiakkaan tuotealeryhmän nettohinta. ";
					$hintaperuste = 5;
				}
			}

			// 6. asiakas.ryhmä tuote.tuoteno nettohinta (asiakasaleryhmän tuotteen hinta) laskun valuutassa
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	SELECT hinta, laji
							FROM asiakashinta
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_ryhma = '$alehi_asrow[ryhma]'
							and asiakas_ryhma != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and ytunnus = ''
							and asiakas = 0
							and valkoodi = '$laskurow[valkoodi]'
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_array ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto = $hrow["laji"];
						$ale = 0;
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							$ale	= 0;
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							$ale	= '';
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							$ale	= 0;
						}
					}

					$hinta        = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste    .= "Asiakasaleryhmän tuotteen nettohinta laskun valuutassa. ";
					$alehinta_val = $laskurow["valkoodi"];
					$hintaperuste = 6;
				}
			}

			// 7. asiakas.ryhmä tuote.tuoteno nettohinta (asiakasaleryhmän tuotteen hinta)
			if ($hinta == 0) {

				$query = "	SELECT hinta, laji
							FROM asiakashinta
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_ryhma = '$alehi_asrow[ryhma]'
							and asiakas_ryhma != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and ytunnus = ''
							and asiakas = 0
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_array ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto = $hrow["laji"];
						$ale = 0;
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							$ale	= 0;
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							$ale	= '';
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							$ale	= 0;
						}
					}

					$hinta		= $hrow["hinta"];
					$aperuste	.= "Asiakasaleryhmän tuotteen nettohinta. ";
					$hintaperuste = 7;
				}
			}

			// 8. asiakas.ryhmä tuote.aleryhmä nettohinta (asiakasaleryhmän tuotealeryhmän hinta)
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	SELECT hinta, laji
							FROM asiakashinta
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_ryhma = '$alehi_asrow[ryhma]'
							and asiakas_ryhma != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ytunnus = ''
							and asiakas = 0
							and valkoodi = '$laskurow[valkoodi]'
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_array ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto = $hrow["laji"];
						$ale = 0;
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							$ale	= 0;
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							$ale	= '';
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							$ale	= 0;
						}
					}

					$hinta        = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste    .= "Asiakasaleryhmän tuotealeryhmän nettohinta laskun valuutassa. ";
					$alehinta_val = $laskurow["valkoodi"];
					$hintaperuste = 8;
				}
			}

			// 9. asiakas.ryhmä tuote.aleryhmä nettohinta (asiakasaleryhmän tuotealeryhmän hinta)
			if ($hinta == 0) {

				$query = "	SELECT hinta, laji
							FROM asiakashinta
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_ryhma = '$alehi_asrow[ryhma]'
							and asiakas_ryhma != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ytunnus = ''
							and asiakas = 0
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_array ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto = $hrow["laji"];
						$ale = 0;
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							$ale	= 0;
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							$ale	= '';
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							$ale	= 0;
						}
					}

					$hinta		= $hrow["hinta"];
					$aperuste	.= "Asiakasaleryhmän tuotealeryhmän nettohinta. ";
					$hintaperuste = 9;
				}
			}

			// 10. asiakas.tunnus/asiakas.ytunnus tuote.aleryhmä negatiivinen-aleprosentti (asiakkaan katemyyntihinta netto)
			if ($hinta == 0) {

				$query = "	(SELECT '1' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika
							FROM asiakasalennus USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and asiakas_ryhma = ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and (alennus < 0 or left(alennus,1) = '-'))

							UNION

							(SELECT '2' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika
							FROM asiakasalennus USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and asiakas_ryhma = ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and (alennus < 0 or left(alennus,1) = '-'))

							ORDER BY prio, aika
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {

					$hrow = mysql_fetch_array($hresult);

					//Tässä katotaan onko epäkuranttiutta
					$kokoepakurantti = "";

					if ($trow["epakurantti100pvm"] != "0000-00-00") {
						$trow["kehahin"] = 0;
						$kokoepakurantti = "ON";
					}
					elseif ($trow["epakurantti75pvm"] != "0000-00-00") {
						$trow["kehahin"] = round($trow["kehahin"] * 0.25, 6);
					}
					elseif ($trow["epakurantti50pvm"] != "0000-00-00") {
						$trow["kehahin"] = round($trow["kehahin"] * 0.5, 6);
					}
					elseif ($trow["epakurantti25pvm"] != "0000-00-00") {
						$trow["kehahin"] = round($trow["kehahin"] * 0.75, 6);
					}

					// Kehahinta saa olla nolla vain jos tuote on kokoepäkurantti, muuten katemyynti ei onnaa
					if ($trow['kehahin'] == 0 and $kokoepakurantti == "ON") {
						$hinta 		= 0.01;
						$ale		= 0;
						$netto		= "";
						$aperuste 	.= "Katemyyntihinta (kokoepäkurantti). ";
						$hintaperuste = 10;
					}
					elseif ($trow['kehahin'] > 0) {
						if ($yhtiorow['alv_kasittely']=='')
							$hinta	= round(($trow['kehahin'] / (1-(abs($hrow["alennus"])/100))) * (1+($trow['alv']/100)), $yhtiorow['hintapyoristys']);
						else
							$hinta	= round(($trow['kehahin'] / (1-(abs($hrow["alennus"])/100))) ,$yhtiorow['hintapyoristys']);

						$ale		= 0;
						$netto		= "";
						$aperuste 	.= "Katemyyntihinta. ";
						$hintaperuste = 10;
					}
				}
			}

			// 11. asiakas.ryhmä tuote.aleryhmä negatiivinen-aleprosentti (asiakkaan katemyyntihinta netto)
			if ($hinta == 0) {

				$query = "	SELECT alennus
							FROM asiakasalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_ryhma = '$alehi_asrow[ryhma]'
							and asiakas_ryhma != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ytunnus = ''
							and asiakas = 0
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and (alennus < 0 or left(alennus,1) = '-')
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {

					$hrow = mysql_fetch_array($hresult);

					//Tässä katotaan onko epäkuranttiutta
					$kokoepakurantti = "";

					if ($trow["epakurantti100pvm"] != "0000-00-00") {
						$trow["kehahin"] = 0;
						$kokoepakurantti = "ON";
					}
					elseif ($trow["epakurantti75pvm"] != "0000-00-00") {
						$trow["kehahin"] = round($trow["kehahin"] * 0.25, 6);
					}
					elseif ($trow["epakurantti50pvm"] != "0000-00-00") {
						$trow["kehahin"] = round($trow["kehahin"] * 0.5, 6);
					}
					elseif ($trow["epakurantti25pvm"] != "0000-00-00") {
						$trow["kehahin"] = round($trow["kehahin"] * 0.75, 6);
					}

					// Kehahinta saa olla nolla vain jos tuote on kokoepäkurantti, muuten katemyynti ei onnaa
					if ($trow['kehahin'] == 0 and $kokoepakurantti == "ON") {
						$hinta 		= 0.01;
						$ale		= 0;
						$netto		= "";
						$aperuste 	.= "Asiakasryhmä Katemyyntihinta (kokoepäkurantti). ";
						$hintaperuste = 11;
					}
					elseif ($trow['kehahin'] > 0) {
						if ($yhtiorow['alv_kasittely']=='')
							$hinta	= round(($trow['kehahin'] / (1-(abs($hrow["alennus"])/100))) * (1+($trow['alv']/100)), $yhtiorow['hintapyoristys']);
						else
							$hinta	= round(($trow['kehahin'] / (1-(abs($hrow["alennus"])/100))) ,$yhtiorow['hintapyoristys']);


						$ale		= 0;
						$netto		= "";
						$aperuste 	.= "Asiakasryhmä Katemyyntihinta. ";
						$hintaperuste = 11;
					}
				}

			}

			// 12. asiakas.tunnus/asiakas.ytunnus tuote.aleryhmä aleprosentti == 999.99 (asiakkaan myymälähinta)
			if ($hinta == 0) {
				$query = "	(SELECT '1' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika
							FROM asiakasalennus USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus = 999.99)

							UNION

							(SELECT '2' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika
							FROM asiakasalennus USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus = 999.99)

							ORDER BY prio, aika
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {

					$hrow = mysql_fetch_array($hresult);

					//Jos aleprossa  = 999.99 haetaan tuotteen myymälähinta myyntihinnan tilalle
					$query = "SELECT myymalahinta, alv FROM tuote WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$trow[tuoteno]'";
					$myymresult = mysql_query($query) or pupe_error($query);
					$myymrow = mysql_fetch_array ($myymresult);

					if ($alehinta_alv != "") $myymrow["alv"] = $alehinta_alv;

					if ($myymrow["myymalahinta"] > 0) {
						if ($yhtiorow["alv_kasittely"] != '') {
							$hinta = $myymrow["myymalahinta"]/($myymrow["alv"]/100+1);
						}
						else {
							$hinta = $myymrow["myymalahinta"];
						}
					}
					$ale		= 0;
					$netto		= "";
					$aperuste 	.= "Myymälähinta. ";
					$hintaperuste = 12;
				}
			}

			// 13. hinnasto.hinta tuotteen nettohinta hinnastosta laskun valuutassa
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	SELECT hinta, laji
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
					$hinta 			= round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$netto			= $hrow["laji"];
					$aperuste		.= "Tuotteen hinnastohinta laskun valuutassa";
					$alehinta_val	= $laskurow["valkoodi"];
					$hintaperuste = 13;
				}
			}

			// 14. hinnasto.hinta tuotteen nettohinta hinnastosta yhtiön valuutassa
			if ($hinta == 0) {

				$query = "	SELECT hinta, laji
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
					$aperuste		.= "Tuotteen hinnastohinta yhtiön valuutassa";
					$hintaperuste = 14;
				}
			}


			// 15. tuote.nettohinta (tuotteen nettohinta)
			if ($hinta == 0 and $netto != 'E' and $trow['nettohinta'] > 0) {

				$hinta 			= $trow['nettohinta'];
				$aperuste 		.= "Tuotteen nettohinta. ";
				$netto 			= 'N';
				$ale			= 0;
				$hintaperuste = 15;
			}

			// 16. hinnasto.hinta tuotteen bruttohinta hinnastosta laskun valuutassa
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	SELECT hinta, laji
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
					$hinta			= round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$netto			= $hrow["laji"];
					$aperuste		.= "Tuotteen hinnastohinta laskun valuutassa";
					$alehinta_val   = $laskurow["valkoodi"];
					$hintaperuste = 16;
				}
			}

			// 17. hinnasto.hinta tuotteen bruttohinta hinnastosta yhtion valuutassa
			if ($hinta == 0) {

				$query = "	SELECT hinta, laji
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
					$aperuste		.= "Tuotteen hinnastohinta yhtiön valuutassa";
					$hintaperuste = 17;
				}
			}

			// 18. tuote.myyntihinta (tuotteen bruttohinta)
			if ($hinta == 0) {
				$hinta		 = $trow['myyntihinta'];
				$aperuste	.= "Tuotteen myyntihinta. ";
				$hintaperuste = 18;
			}
		}

		if (substr($ale,-1) == "+") {
			$aleperuste = 1;

			// 1. käyttäjän syöttämä EURO-määräinen alennus
			$hinta_xxx = $hinta + substr($ale,0,-1);
			$ale = (1 - ($hinta/$hinta_xxx))*100;
			$hinta = $hinta_xxx;
		}
		elseif(substr($ale,-1) == "-") {
			$aleperuste = 3;

			// 2. käyttäjän syöttämä EURO-määräinen alennus
			$hinta_xxx = $hinta - substr($ale,0,-1);
			$ale = (1 - ($hinta_xxx/$hinta))*100;
		}
		elseif ($ale > 0) {
			$aleperuste = 3;

			// 3. käyttäjän syöttämä alennus
			$aperuste .= " Käyttäjän syöttämä ale";
		}
		elseif (is_numeric($ale) and $ale == 0) {
			$aleperuste = 4;

			// 4. käyttäjän syöttämä alennus
			$aperuste .= " Ei alennusta";
		}
		elseif ($ale == "" and $netto != 'N' and $netto != 'E') {

			$ale = 0;

			// 5. asiakas.tunnus/asiakas.ytunnus tuote.tuotenumero aleprosentti (asiakkaan tuotteen alennus)
			$query = "	(SELECT '1' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika
						FROM asiakasalennus USE INDEX (yhtio_asiakas_tuoteno)
						WHERE yhtio = '$kukarow[yhtio]'
						and asiakas = '$laskurow[liitostunnus]'
						and asiakas > 0
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and alennus > 0
						and alennus <= 100)

						UNION

						(SELECT '2' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika
						FROM asiakasalennus USE INDEX (yhtio_ytunnus_tuoteno)
						WHERE yhtio = '$kukarow[yhtio]'
						and ytunnus = '$laskurow[ytunnus]'
						and ytunnus != ''
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and alennus > 0
						and alennus <= 100)

						ORDER BY prio, aika
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$aleperuste = 5;

				$hrow = mysql_fetch_array ($hresult);
				$ale  = $hrow["alennus"];
				$aperuste .= " Asiakkaan tuotteen alennus";
			}

			// 6. asiakas.tunnus/asiakas.ytunnus tuote.aleryhmä aleprosentti (asiakkaan tuotealeryhmän alennus)
			if ($ale == 0) {

				$query = "	(SELECT '1' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika
							FROM asiakasalennus USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus > 0
							and alennus <= 100)

							UNION

							(SELECT '2' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika
							FROM asiakasalennus USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus > 0
							and alennus <= 100)

							ORDER BY prio, aika
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$aleperuste = 6;

					$hrow = mysql_fetch_array ($hresult);
					$ale  = $hrow["alennus"];
					$aperuste .= " Asiakkaan tuotealeryhmän alennus";
				}
			}

			// 7. asiakas.ryhmä tuote.tuoteno aleprosentti (asiakasaleryhmän tuotteen alennus)
			if ($ale == 0) {

				$query = "	SELECT alennus
							FROM asiakasalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_ryhma = '$alehi_asrow[ryhma]'
							and asiakas_ryhma != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and ytunnus = ''
							and asiakas = 0
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus > 0
							and alennus <= 100
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$aleperuste = 7;

					$hrow = mysql_fetch_array ($hresult);
					$ale  = $hrow["alennus"];
					$aperuste .= " Asiakasaleryhmän tuotteen alennus";
				}
			}

			// 8. asiakas.ryhmä tuote.aleryhmä aleprosentti (asiakasaleryhmän tuotealeryhmän alennus)
			if ($ale == 0) {

				for($aasi = strlen($trow["aleryhma"]); $aasi>0; $aasi--) {

					if ($aasi == strlen($trow["aleryhma"])) {
						$hakuavain_hae = "and ryhma  = '$trow[aleryhma]'";
					}
					else {
						$hakuavain_hae = "	and right(ryhma,1) = '*'
											and substring(ryhma, 1, char_length(ryhma)-1) = '".substr($trow["aleryhma"], 0, $aasi)."' ";
					}

					$query = "	SELECT alennus
								FROM asiakasalennus
								WHERE yhtio = '$kukarow[yhtio]'
								and asiakas_ryhma = '$alehi_asrow[ryhma]'
								and asiakas_ryhma != ''
								$hakuavain_hae
								and ryhma != ''
								and ytunnus = ''
								and asiakas = 0
								and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
								and alennus > 0
								and alennus <= 100
								ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
								LIMIT 1";
					$hresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($hresult) > 0) {
						$aleperuste = 8;

						$hrow = mysql_fetch_array ($hresult);
						$ale  = $hrow["alennus"];
						$aperuste .= " Asiakasaleryhmän tuotealeryhmän alennus";
						break;
					}
				}
			}

			// 9. tuote.aleryhmä aleprosentti (tuotealeryhmän perusalennus)
			if ($ale == 0) {

				$query = "	SELECT alennus
							FROM perusalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and alennus > 0
							and alennus <= 100";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$aleperuste = 9;

					$hrow = mysql_fetch_array ($hresult);
					$ale  = $hrow["alennus"];
					$aperuste .= " Tuotealeryhmän perusalennus";
				}
			}
		}

		if ($ale > 100) {
			$ale = 100;
		}
		if ($ale < 0) {
			$ale = 0;
		}

		//$debug = 1;
		if ($debug == 1) echo t("Tulin tulokseen").": $aperuste.  ALE: $ale ".t("% HINTA")." $hinta $yhtiorow[valkoodi]<br><br>";

		if($palautus != "") {
			$ret=array();

			$a = explode(",", $palautus);
			if(is_array($a)) {
				foreach($a as $palauta) {
					if(isset(${$palauta})) {
						$ret[$palauta] = ${$palauta};
					}
					else {
						echo "<font class='error'>Muuttujaa '$palauta' ei voida palauttaa!</font><br>";
						$ret[$palauta] = false;
					}
				}
			}
			else {
				if(isset(${$palautus})) {
					$ret[$palautus] = ${$palautus};
				}
				else {
					echo "<font class='error'>Muuttujaa '$palauta' ei voida palauttaa!</font><br>";
					$ret[$palautus] = false;
				}
			}

			return $ret;
		}
		else {
			return array($hinta, $netto, $ale, $alehinta_alv, $alehinta_val);
		}
	}
}

if (!function_exists("kalenteritapahtuma")) {
	function kalenteritapahtuma ($tyyppi, $tapa, $viesti, $liitostunnus, $kuittaus="", $henkilo="", $otunnus="", $pvmalku='now()') {
		global $yhtiorow, $kukarow;

		$query = "SELECT * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus'";
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
					pvmalku  		= $pvmalku,
					otunnus  		= '$otunnus',
					kuittaus  		= '$kuittaus',
					kentta01 		= '$viesti'";
 		$result = mysql_query($kysely) or pupe_error($kysely);
	}
}

if (!function_exists('ebid')) {
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
		elseif ($laskurow['tila'] == 'U') {

			$out = "";

			if ($laskurow['chn'] == '111') {
				$out = t("Elma EDI-inhouse");
			}
			elseif ($laskurow['chn'] == '010') {
				$out = t("eInvoice");
			}
			elseif ($laskurow['chn'] == '020') {
				$out = t("Vienti eInvoice");
			}
			elseif ($laskurow['chn'] == '100') {
				$out = t("Paperilasku");
			}

			return $out;

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

if (!function_exists('ta')) {
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
	function sarjanumeron_ostohinta($kentta, $arvo, $eikululaskuja="") {
		global $kukarow, $yhtiorow;

		// Funktio laskee yhden kappaleen ostohinnan
		$ostohinta = 0;

		// Tuotteen ostohinta
		$query = "	SELECT group_concat(distinct tilausrivi.tunnus) tunnukset, count(distinct tilausrivi.tunnus) tunnukset_kpl
					FROM sarjanumeroseuranta
					JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
					WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					and sarjanumeroseuranta.$kentta = '$arvo'";
		$otsores = mysql_query($query) or pupe_error($query);
		$ostorow = mysql_fetch_array($otsores);

		if($ostorow["tunnukset"] != '') {
			$query = "	SELECT
						sum(tilausrivi.rivihinta/tilausrivi.kpl) ostosumma,
						sum(tilausrivi.hinta * (1-(tilausrivi.ale/100))) ostosumma_eiloppulaskettu
						FROM tilausrivi
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tunnus  IN ($ostorow[tunnukset])";
			$sarjares = mysql_query($query) or pupe_error($query);
			$sarjarow = mysql_fetch_array($sarjares);

			$ostohinta = (float) $sarjarow["ostosumma"] / $ostorow["tunnukset_kpl"];

			if ($ostohinta == 0 and $sarjarow["ostosumma_eiloppulaskettu"] != 0) {
				$ostohinta = (float) $sarjarow["ostosumma_eiloppulaskettu"] / $ostorow["tunnukset_kpl"];
			}

			if ($eikululaskuja != "EIKULULASKUJA") {

				// Katsotaan onko sarjanumerolle liitetty kulukeikka
				$query  = "	SELECT lasku.laskunro
							FROM sarjanumeroseuranta
							JOIN lasku ON lasku.yhtio=sarjanumeroseuranta.yhtio and lasku.liitostunnus=sarjanumeroseuranta.tunnus and lasku.ytunnus=sarjanumeroseuranta.tunnus and lasku.tila = 'K' and lasku.alatila = 'S'
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
							and sarjanumeroseuranta.$kentta = '$arvo'";
				$keikkares = mysql_query($query) or pupe_error($query);

				while($kulukeikkarow = mysql_fetch_array($keikkares)) {
					// Haetaan kaikki keikkaan liitettyjen laskujen summa
					$query = "	SELECT sum(arvo*if(maksu_kurssi!=0, maksu_kurssi, vienti_kurssi)) kulusumma
								FROM lasku
								WHERE yhtio		= '$kukarow[yhtio]'
								and tila 		IN ('K','X')
								and laskunro 	= '$kulukeikkarow[laskunro]'
								and vanhatunnus <> 0
								and vienti in ('B','E','H')";
					$result = mysql_query($query) or pupe_error($query);
					$kulukulurow = mysql_fetch_array($result);

					$ostohinta	+= $kulukulurow["kulusumma"];
				}
			}
		}

	    return round($ostohinta, 6);
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

if(!function_exists("paivita_toimitukset")) {
	function paivita_toimitukset($otunnus, $originaali) {
		global $kukarow, $yhtiorow;

		/*
			Functio jolla voidaan syncronoida toimitusten sisältöjä
		*/

		foreach(array("lasku","laskun_lisatiedot") as $taulu) {

			if(!is_array($originaali[$taulu]) or (int) $otunnus == 0) {
				//echo "<font class='error'>".t("Toimitusten päivittäminen EPÄONNISTUI")."!</font><br>";
				return false;
			}

			if($taulu == "lasku") {
				$where = " and tunnus = '$otunnus' and tunnusnippu > 0";
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
						if(!is_numeric($key) and !in_array($key, array("tila", "alatila", "muutospvm"))) {
							$diff[$key] = $value;
						}
					}

					//	Haetaan tunnukset jotka voidaan päivittää
					if($taulu == "lasku") {
						$query = "	SELECT *
									FROM lasku
									WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$uusirow[tunnusnippu]' and tila IN ('L','N') and alatila != 'X' and tunnus != '$otunnus' and tunnusnippu > 0";
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

if(!function_exists("tulosta_ytunnus")) {
	function tulosta_ytunnus($ytunnus, $maa="", $vienti="") {
		global $yhtiorow, $kukarow;

		if ($maa == "") {
			$maa = $yhtiorow["maa"];
		}

		//jos on suomalainen yritys tehdään ytunnus nätiks
		if (strtoupper($maa) == 'FI') {
			//muutetaan ytunnus takas oikean näköseks
			$ytunpit = 8-strlen($ytunnus);

			if ($ytunpit > 0) {
				$uytunnus = $ytunnus;

				while ($ytunpit > 0) {
				    $uytunnus = "0".$uytunnus; $ytunpit--;
				}
			}
			else {
				$uytunnus = $ytunnus;
			}

			$uytunnus = substr($uytunnus,0,7)."-".substr($uytunnus,7,1);

			if ($vienti != "") {
				$uytunnus = strtoupper($maa).$uytunnus;
			}
		}
		else {
			if (substr(trim(strtoupper($ytunnus)),0,2) != strtoupper($maa) and trim(strtoupper($maa)) != trim(strtoupper($yhtiorow["maa"])) and trim(strtoupper($maa)) != "") {
				$ytunnus = strtoupper($maa)."-".$ytunnus;
			}

			$uytunnus = $ytunnus;
		}

		return $uytunnus;
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
	var toggled = Array();
	function AJAXInteraction(urli, id, data, href) {

		var http = createRequestObject();
		http.onreadystatechange = handleResponse;

		function createRequestObject() {

			var xmlHttp;

			try {
				// Firefox, Opera 8.0+, Safari
				xmlHttp=new XMLHttpRequest();
			}
			catch (e) {
				// Internet Explorer
				try {
					xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
				}
				catch (e) {
					try {
						xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
					}
					catch (e) {
						alert("Your browser does not support AJAX!");
						return false;
					}
				}
			}

			return xmlHttp;
		}

		function handleResponse() {
			if (http.readyState == 4) {
				var response = http.responseText;
				if(response.substr(0, 10) == "TABLE-ROW:") {

					//	Meidän rivi ja sen solut
					response=response.substr(9);
					var patt=/(<td[^>]*>.*?<\/td>)|(<tr[^>]*>)/gm;
					newRowElements=response.match(patt);

					var row='';
					for (var x = 0; x < newRowElements.length; x++) {

						//	Vaihdellaan riviä
						if(newRowElements[x].match(/<tr[^>]*>/)) {
							y=0;
							if(row=='') {
								row=document.getElementById(id);
								rowElements=row.cells;
							}
							else {
								row=row.parentNode.rows[(row.rowIndex+1)];
								rowElements=row.cells;
							}
						}
						else if (newRowElements[x].match(/<td[^>]*>/)){
							element=newRowElements[x].substr(newRowElements[x].indexOf(">")+1);
							element=element.substr(0, (element.length-5));

							rowElements[y].innerHTML=element;

							y++;
						}
					}
				}
				else {

					document.getElementById(id).innerHTML = response;
				}

				var container = document.getElementById(id);
				var allNewScripts = container.getElementsByTagName('script');
				c=allNewScripts.length-1;
				if(c>1) {
					for (x=0;x<=c;x++) {
						eval(allNewScripts[x].innerHTML);
					}
				}

				//	Jos päivitetään togglegrouppaus
				if(href) {
					document.getElementById(href).href = 'javascript:toggleGroup("' + id + '");';
					toggled.push(id);
				}

				//	Varmistetaan näkyvyys
				document.getElementById(id).style.display = '';

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

		this.unToggle = function() {
			unToggle(id);
			document.getElementById(href).href = document.getElementById(href).href+' unToggle("' + id + '");';
		}

		this.showLoader = function() {
			document.getElementById(id).innerHTML = "<div style='filter:alpha(opacity=30); -moz-opacity:0.3; opacity: 0.3; position: absolute; width: 100%; height: 100%; top: 0; left: 0; background-color: #0c0c0c;' z-index: 100><img style='position: absolute; top: 50%; left: 50%;' src='<?php echo $imgUrl; ?>'></div>";
		}

	}

	function sndReq(div, urli, href, load, unToggle, method, data) {

		//	Luodaan uudi instanssi
		var ai = new AJAXInteraction(urli, div, data, href);

		//	Näytetään latausanimaatio
		if(load) {
			ai.showLoader();
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

	}

	function unToggle(id) {
		for (x in toggled) {
			if(toggled[x] != id) {
				if(document.getElementById(toggled[x])) {
					document.getElementById(toggled[x]).style.display = 'none';
				}
				delete toggled[x];
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
		getstr = "sourceCharset=UTF-8&";

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
			else if (obj.elements[i].tagName == "SELECT") {
				var sel = obj.elements[i];
				if(sel.multiple) {
					for (var x = 0; x < sel.options.length; x++) {
						if (sel.options[x].selected) {
							getstr += sel.name + "=" + sel.options[x].value + "&";
						}
					}
				}
				else {
					getstr += sel.name + "=" + sel.options[sel.selectedIndex].value + "&";
				}
			}
			else if (obj.elements[i].tagName == "TEXTAREA") {
				getstr += obj.elements[i].name + "=" + encodeURI(obj.elements[i].value) + "&";
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

if (!function_exists("js_showhide")) {
	// scripti balloonien tekemiseen
	function js_showhide () {

?>
<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">
<!--

function showhide(layer_ref, type) {


	if(type=='byName') {
		var e = document.getElementsByName(layer_ref);

		for(i=0;i<=e.length-1;i++) {
			if (e[i].style.display != 'none') {
				e[i].style.display = 'none';
			}
			else {
				e[i].style.display = '';
			}
		}
	}
	else {

		var state = document.getElementById(layer_ref).style.display;
		if (state != 'none') {
			state = 'none';
		}
		else {
			state = '';
		}

		if (document.all) { //IS IE 4 or 5 (or 6 beta)
			eval( "document.all." + layer_ref + ".style.display = state");
		}
		if (document.layers) { //IS NETSCAPE 4 or below
			document.layers[layer_ref].display = state;
		}
		if (document.getElementById &&!document.all) {
			hza = document.getElementById(layer_ref);
			hza.style.display = state;
		}
	}
}

function show(layer_ref, type) {


	if(type=='byName') {
		var e = document.getElementsByName(layer_ref);

		for(i=0;i<=e.length-1;i++) {
			e[i].style.display = '';
		}
	}
	else {

		var state = document.getElementById(layer_ref).style.display;
		state = '';

		if (document.all) { //IS IE 4 or 5 (or 6 beta)
			eval( "document.all." + layer_ref + ".style.display = state");
		}
		if (document.layers) { //IS NETSCAPE 4 or below
			document.layers[layer_ref].display = state;
		}
		if (document.getElementById &&!document.all) {
			hza = document.getElementById(layer_ref);
			hza.style.display = state;
		}
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
function popUp(e, oi, offsetX, offsetY, html) {

	if(!document.getElementById(oi)) {
		var myDiv = document.createElement('div');

		myDiv.setAttribute('class', 'popup');
		myDiv.setAttribute('id', oi);
		myDiv.setAttribute('name', oi);
		myDiv.style.display = 'block';
		document.body.appendChild(myDiv);
	}

	if(e.type == "mouseout") {
		var de = document.getElementById(oi);
		var ds = de.style;

		ds.display = "none";
		ds.visibility = "hidden";
	}
	else {

		if(html) {
			sndReq(oi, html+'&ohje=off&sulje='+oi);
		}

		// relatiivinen sijainti
		if(e.type=="keyup") {
			if(!offsetY) offsetY=-10;
			if(!offsetX) offsetX=-10;

			kohde=e.target;
			var y = 0;
			while( kohde != null ) {
				var l = kohde.offsetTop;
				if(l > 0) {
					y += l;
				}
				kohde = kohde.offsetParent;
			}

			kohde=e.target;
			var x = 0;
			while( kohde != null ) {
				var l = kohde.offsetLeft;
				if(l > 0) {
					x += l;
				}
				kohde = kohde.offsetParent;
			}
		}
		else {
			if(!offsetY) offsetY=-10;
			if(!offsetX) offsetX=-10;

			var y = getMouseY(e);
			var x = getMouseX(e);
		}

		var y = y-(offsetY);
		var x = x-(offsetX);

		var de = document.getElementById(oi);
		var ds = de.style;

		ds.display = "block";
		ds.visibility = "hidden";

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
		de.display = 'block';

	}
}
//-->
</script>
<?php

	}
}

if(!function_exists("encodeURI")) {
	function encodeURI($string) {
		/*
		PHP URL encoding/decoding functions for Javascript interaction V3.0
		(C) 2006 www.captain.at - all rights reserved
		License: GPL
		*/

		//	Apufunktio
		if(!function_exists("encodeURIbycharacter")) {
			function encodeURIbycharacter($char) {
			   if ($char == "+") { return "%20"; }
			   if ($char == "%21") { return "!"; }
			   if ($char == "%23") { return "#"; }
			   if ($char == "%24") { return "$"; }
			   if ($char == "%26") { return "&"; }
			   if ($char == "%27") { return "\""; }
			   if ($char == "%28") { return "("; }
			   if ($char == "%29") { return ")"; }
			   if ($char == "%2A") { return "*"; }
			   if ($char == "%2B") { return "+"; }
			   if ($char == "%2C") { return ","; }
			   if ($char == "%2F") { return "/"; }
			   if ($char == "%3A") { return ":"; }
			   if ($char == "%3B") { return ";"; }
			   if ($char == "%3D") { return "="; }
			   if ($char == "%3F") { return "?"; }
			   if ($char == "%40") { return "@"; }
			   if ($char == "%7E") { return "~"; }
			   if ($char == "%80") { return "%E2%82%AC"; }
			   if ($char == "%81") { return "%C2%81"; }
			   if ($char == "%82") { return "%E2%80%9A"; }
			   if ($char == "%83") { return "%C6%92"; }
			   if ($char == "%84") { return "%E2%80%9E"; }
			   if ($char == "%85") { return "%E2%80%A6"; }
			   if ($char == "%86") { return "%E2%80%A0"; }
			   if ($char == "%87") { return "%E2%80%A1"; }
			   if ($char == "%88") { return "%CB%86"; }
			   if ($char == "%89") { return "%E2%80%B0"; }
			   if ($char == "%8A") { return "%C5%A0"; }
			   if ($char == "%8B") { return "%E2%80%B9"; }
			   if ($char == "%8C") { return "%C5%92"; }
			   if ($char == "%8D") { return "%C2%8D"; }
			   if ($char == "%8E") { return "%C5%BD"; }
			   if ($char == "%8F") { return "%C2%8F"; }
			   if ($char == "%90") { return "%C2%90"; }
			   if ($char == "%91") { return "%E2%80%98"; }
			   if ($char == "%92") { return "%E2%80%99"; }
			   if ($char == "%93") { return "%E2%80%9C"; }
			   if ($char == "%94") { return "%E2%80%9D"; }
			   if ($char == "%95") { return "%E2%80%A2"; }
			   if ($char == "%96") { return "%E2%80%93"; }
			   if ($char == "%97") { return "%E2%80%94"; }
			   if ($char == "%98") { return "%CB%9C"; }
			   if ($char == "%99") { return "%E2%84%A2"; }
			   if ($char == "%9A") { return "%C5%A1"; }
			   if ($char == "%9B") { return "%E2%80%BA"; }
			   if ($char == "%9C") { return "%C5%93"; }
			   if ($char == "%9D") { return "%C2%9D"; }
			   if ($char == "%9E") { return "%C5%BE"; }
			   if ($char == "%9F") { return "%C5%B8"; }
			   if ($char == "%A0") { return "%C2%A0"; }
			   if ($char == "%A1") { return "%C2%A1"; }
			   if ($char == "%A2") { return "%C2%A2"; }
			   if ($char == "%A3") { return "%C2%A3"; }
			   if ($char == "%A4") { return "%C2%A4"; }
			   if ($char == "%A5") { return "%C2%A5"; }
			   if ($char == "%A6") { return "%C2%A6"; }
			   if ($char == "%A7") { return "%C2%A7"; }
			   if ($char == "%A8") { return "%C2%A8"; }
			   if ($char == "%A9") { return "%C2%A9"; }
			   if ($char == "%AA") { return "%C2%AA"; }
			   if ($char == "%AB") { return "%C2%AB"; }
			   if ($char == "%AC") { return "%C2%AC"; }
			   if ($char == "%AD") { return "%C2%AD"; }
			   if ($char == "%AE") { return "%C2%AE"; }
			   if ($char == "%AF") { return "%C2%AF"; }
			   if ($char == "%B0") { return "%C2%B0"; }
			   if ($char == "%B1") { return "%C2%B1"; }
			   if ($char == "%B2") { return "%C2%B2"; }
			   if ($char == "%B3") { return "%C2%B3"; }
			   if ($char == "%B4") { return "%C2%B4"; }
			   if ($char == "%B5") { return "%C2%B5"; }
			   if ($char == "%B6") { return "%C2%B6"; }
			   if ($char == "%B7") { return "%C2%B7"; }
			   if ($char == "%B8") { return "%C2%B8"; }
			   if ($char == "%B9") { return "%C2%B9"; }
			   if ($char == "%BA") { return "%C2%BA"; }
			   if ($char == "%BB") { return "%C2%BB"; }
			   if ($char == "%BC") { return "%C2%BC"; }
			   if ($char == "%BD") { return "%C2%BD"; }
			   if ($char == "%BE") { return "%C2%BE"; }
			   if ($char == "%BF") { return "%C2%BF"; }
			   if ($char == "%C0") { return "%C3%80"; }
			   if ($char == "%C1") { return "%C3%81"; }
			   if ($char == "%C2") { return "%C3%82"; }
			   if ($char == "%C3") { return "%C3%83"; }
			   if ($char == "%C4") { return "%C3%84"; }
			   if ($char == "%C5") { return "%C3%85"; }
			   if ($char == "%C6") { return "%C3%86"; }
			   if ($char == "%C7") { return "%C3%87"; }
			   if ($char == "%C8") { return "%C3%88"; }
			   if ($char == "%C9") { return "%C3%89"; }
			   if ($char == "%CA") { return "%C3%8A"; }
			   if ($char == "%CB") { return "%C3%8B"; }
			   if ($char == "%CC") { return "%C3%8C"; }
			   if ($char == "%CD") { return "%C3%8D"; }
			   if ($char == "%CE") { return "%C3%8E"; }
			   if ($char == "%CF") { return "%C3%8F"; }
			   if ($char == "%D0") { return "%C3%90"; }
			   if ($char == "%D1") { return "%C3%91"; }
			   if ($char == "%D2") { return "%C3%92"; }
			   if ($char == "%D3") { return "%C3%93"; }
			   if ($char == "%D4") { return "%C3%94"; }
			   if ($char == "%D5") { return "%C3%95"; }
			   if ($char == "%D6") { return "%C3%96"; }
			   if ($char == "%D7") { return "%C3%97"; }
			   if ($char == "%D8") { return "%C3%98"; }
			   if ($char == "%D9") { return "%C3%99"; }
			   if ($char == "%DA") { return "%C3%9A"; }
			   if ($char == "%DB") { return "%C3%9B"; }
			   if ($char == "%DC") { return "%C3%9C"; }
			   if ($char == "%DD") { return "%C3%9D"; }
			   if ($char == "%DE") { return "%C3%9E"; }
			   if ($char == "%DF") { return "%C3%9F"; }
			   if ($char == "%E0") { return "%C3%A0"; }
			   if ($char == "%E1") { return "%C3%A1"; }
			   if ($char == "%E2") { return "%C3%A2"; }
			   if ($char == "%E3") { return "%C3%A3"; }
			   if ($char == "%E4") { return "%C3%A4"; }
			   if ($char == "%E5") { return "%C3%A5"; }
			   if ($char == "%E6") { return "%C3%A6"; }
			   if ($char == "%E7") { return "%C3%A7"; }
			   if ($char == "%E8") { return "%C3%A8"; }
			   if ($char == "%E9") { return "%C3%A9"; }
			   if ($char == "%EA") { return "%C3%AA"; }
			   if ($char == "%EB") { return "%C3%AB"; }
			   if ($char == "%EC") { return "%C3%AC"; }
			   if ($char == "%ED") { return "%C3%AD"; }
			   if ($char == "%EE") { return "%C3%AE"; }
			   if ($char == "%EF") { return "%C3%AF"; }
			   if ($char == "%F0") { return "%C3%B0"; }
			   if ($char == "%F1") { return "%C3%B1"; }
			   if ($char == "%F2") { return "%C3%B2"; }
			   if ($char == "%F3") { return "%C3%B3"; }
			   if ($char == "%F4") { return "%C3%B4"; }
			   if ($char == "%F5") { return "%C3%B5"; }
			   if ($char == "%F6") { return "%C3%B6"; }
			   if ($char == "%F7") { return "%C3%B7"; }
			   if ($char == "%F8") { return "%C3%B8"; }
			   if ($char == "%F9") { return "%C3%B9"; }
			   if ($char == "%FA") { return "%C3%BA"; }
			   if ($char == "%FB") { return "%C3%BB"; }
			   if ($char == "%FC") { return "%C3%BC"; }
			   if ($char == "%FD") { return "%C3%BD"; }
			   if ($char == "%FE") { return "%C3%BE"; }
			   if ($char == "%FF") { return "%C3%BF"; }
			   return $char;
			}
		}

		$result = "";
		for ($i = 0; $i < strlen($string); $i++) {
			$result .= encodeURIbycharacter(urlencode($string[$i]));
		}
		return $result;
	}
}

if(!function_exists("decodeURI")) {
	function decodeURI($string) {
		/*
		PHP URL encoding/decoding functions for Javascript interaction V3.0
		(C) 2006 www.captain.at - all rights reserved
		License: GPL
		*/

		//	Apufunktio
		if(!function_exists("decodeURIbycharacter")) {
			function decodeURIbycharacter($str) {

			   $char = $str;

			   if ($char == "%E2%82%AC") { return array("%80", 8); }
			   if ($char == "%E2%80%9A") { return array("%82", 8); }
			   if ($char == "%E2%80%9E") { return array("%84", 8); }
			   if ($char == "%E2%80%A6") { return array("%85", 8); }
			   if ($char == "%E2%80%A0") { return array("%86", 8); }
			   if ($char == "%E2%80%A1") { return array("%87", 8); }
			   if ($char == "%E2%80%B0") { return array("%89", 8); }
			   if ($char == "%E2%80%B9") { return array("%8B", 8); }
			   if ($char == "%E2%80%98") { return array("%91", 8); }
			   if ($char == "%E2%80%99") { return array("%92", 8); }
			   if ($char == "%E2%80%9C") { return array("%93", 8); }
			   if ($char == "%E2%80%9D") { return array("%94", 8); }
			   if ($char == "%E2%80%A2") { return array("%95", 8); }
			   if ($char == "%E2%80%93") { return array("%96", 8); }
			   if ($char == "%E2%80%94") { return array("%97", 8); }
			   if ($char == "%E2%84%A2") { return array("%99", 8); }
			   if ($char == "%E2%80%BA") { return array("%9B", 8); }

			   $char = substr($str, 0, 6);

			   if ($char == "%C2%81") { return array("%81", 5); }
			   if ($char == "%C6%92") { return array("%83", 5); }
			   if ($char == "%CB%86") { return array("%88", 5); }
			   if ($char == "%C5%A0") { return array("%8A", 5); }
			   if ($char == "%C5%92") { return array("%8C", 5); }
			   if ($char == "%C2%8D") { return array("%8D", 5); }
			   if ($char == "%C5%BD") { return array("%8E", 5); }
			   if ($char == "%C2%8F") { return array("%8F", 5); }
			   if ($char == "%C2%90") { return array("%90", 5); }
			   if ($char == "%CB%9C") { return array("%98", 5); }
			   if ($char == "%C5%A1") { return array("%9A", 5); }
			   if ($char == "%C5%93") { return array("%9C", 5); }
			   if ($char == "%C2%9D") { return array("%9D", 5); }
			   if ($char == "%C5%BE") { return array("%9E", 5); }
			   if ($char == "%C5%B8") { return array("%9F", 5); }
			   if ($char == "%C2%A0") { return array("%A0", 5); }
			   if ($char == "%C2%A1") { return array("%A1", 5); }
			   if ($char == "%C2%A2") { return array("%A2", 5); }
			   if ($char == "%C2%A3") { return array("%A3", 5); }
			   if ($char == "%C2%A4") { return array("%A4", 5); }
			   if ($char == "%C2%A5") { return array("%A5", 5); }
			   if ($char == "%C2%A6") { return array("%A6", 5); }
			   if ($char == "%C2%A7") { return array("%A7", 5); }
			   if ($char == "%C2%A8") { return array("%A8", 5); }
			   if ($char == "%C2%A9") { return array("%A9", 5); }
			   if ($char == "%C2%AA") { return array("%AA", 5); }
			   if ($char == "%C2%AB") { return array("%AB", 5); }
			   if ($char == "%C2%AC") { return array("%AC", 5); }
			   if ($char == "%C2%AD") { return array("%AD", 5); }
			   if ($char == "%C2%AE") { return array("%AE", 5); }
			   if ($char == "%C2%AF") { return array("%AF", 5); }
			   if ($char == "%C2%B0") { return array("%B0", 5); }
			   if ($char == "%C2%B1") { return array("%B1", 5); }
			   if ($char == "%C2%B2") { return array("%B2", 5); }
			   if ($char == "%C2%B3") { return array("%B3", 5); }
			   if ($char == "%C2%B4") { return array("%B4", 5); }
			   if ($char == "%C2%B5") { return array("%B5", 5); }
			   if ($char == "%C2%B6") { return array("%B6", 5); }
			   if ($char == "%C2%B7") { return array("%B7", 5); }
			   if ($char == "%C2%B8") { return array("%B8", 5); }
			   if ($char == "%C2%B9") { return array("%B9", 5); }
			   if ($char == "%C2%BA") { return array("%BA", 5); }
			   if ($char == "%C2%BB") { return array("%BB", 5); }
			   if ($char == "%C2%BC") { return array("%BC", 5); }
			   if ($char == "%C2%BD") { return array("%BD", 5); }
			   if ($char == "%C2%BE") { return array("%BE", 5); }
			   if ($char == "%C2%BF") { return array("%BF", 5); }
			   if ($char == "%C3%80") { return array("%C0", 5); }
			   if ($char == "%C3%81") { return array("%C1", 5); }
			   if ($char == "%C3%82") { return array("%C2", 5); }
			   if ($char == "%C3%83") { return array("%C3", 5); }
			   if ($char == "%C3%84") { return array("%C4", 5); }
			   if ($char == "%C3%85") { return array("%C5", 5); }
			   if ($char == "%C3%86") { return array("%C6", 5); }
			   if ($char == "%C3%87") { return array("%C7", 5); }
			   if ($char == "%C3%88") { return array("%C8", 5); }
			   if ($char == "%C3%89") { return array("%C9", 5); }
			   if ($char == "%C3%8A") { return array("%CA", 5); }
			   if ($char == "%C3%8B") { return array("%CB", 5); }
			   if ($char == "%C3%8C") { return array("%CC", 5); }
			   if ($char == "%C3%8D") { return array("%CD", 5); }
			   if ($char == "%C3%8E") { return array("%CE", 5); }
			   if ($char == "%C3%8F") { return array("%CF", 5); }
			   if ($char == "%C3%90") { return array("%D0", 5); }
			   if ($char == "%C3%91") { return array("%D1", 5); }
			   if ($char == "%C3%92") { return array("%D2", 5); }
			   if ($char == "%C3%93") { return array("%D3", 5); }
			   if ($char == "%C3%94") { return array("%D4", 5); }
			   if ($char == "%C3%95") { return array("%D5", 5); }
			   if ($char == "%C3%96") { return array("%D6", 5); }
			   if ($char == "%C3%97") { return array("%D7", 5); }
			   if ($char == "%C3%98") { return array("%D8", 5); }
			   if ($char == "%C3%99") { return array("%D9", 5); }
			   if ($char == "%C3%9A") { return array("%DA", 5); }
			   if ($char == "%C3%9B") { return array("%DB", 5); }
			   if ($char == "%C3%9C") { return array("%DC", 5); }
			   if ($char == "%C3%9D") { return array("%DD", 5); }
			   if ($char == "%C3%9E") { return array("%DE", 5); }
			   if ($char == "%C3%9F") { return array("%DF", 5); }
			   if ($char == "%C3%A0") { return array("%E0", 5); }
			   if ($char == "%C3%A1") { return array("%E1", 5); }
			   if ($char == "%C3%A2") { return array("%E2", 5); }
			   if ($char == "%C3%A3") { return array("%E3", 5); }
			   if ($char == "%C3%A4") { return array("%E4", 5); }
			   if ($char == "%C3%A5") { return array("%E5", 5); }
			   if ($char == "%C3%A6") { return array("%E6", 5); }
			   if ($char == "%C3%A7") { return array("%E7", 5); }
			   if ($char == "%C3%A8") { return array("%E8", 5); }
			   if ($char == "%C3%A9") { return array("%E9", 5); }
			   if ($char == "%C3%AA") { return array("%EA", 5); }
			   if ($char == "%C3%AB") { return array("%EB", 5); }
			   if ($char == "%C3%AC") { return array("%EC", 5); }
			   if ($char == "%C3%AD") { return array("%ED", 5); }
			   if ($char == "%C3%AE") { return array("%EE", 5); }
			   if ($char == "%C3%AF") { return array("%EF", 5); }
			   if ($char == "%C3%B0") { return array("%F0", 5); }
			   if ($char == "%C3%B1") { return array("%F1", 5); }
			   if ($char == "%C3%B2") { return array("%F2", 5); }
			   if ($char == "%C3%B3") { return array("%F3", 5); }
			   if ($char == "%C3%B4") { return array("%F4", 5); }
			   if ($char == "%C3%B5") { return array("%F5", 5); }
			   if ($char == "%C3%B6") { return array("%F6", 5); }
			   if ($char == "%C3%B7") { return array("%F7", 5); }
			   if ($char == "%C3%B8") { return array("%F8", 5); }
			   if ($char == "%C3%B9") { return array("%F9", 5); }
			   if ($char == "%C3%BA") { return array("%FA", 5); }
			   if ($char == "%C3%BB") { return array("%FB", 5); }
			   if ($char == "%C3%BC") { return array("%FC", 5); }
			   if ($char == "%C3%BD") { return array("%FD", 5); }
			   if ($char == "%C3%BE") { return array("%FE", 5); }
			   if ($char == "%C3%BF") { return array("%FF", 5); }

			   $char = substr($str, 0, 3);
			   if ($char == "%20") { return array("+", 2); }

			   $char = substr($str, 0, 1);

			   if ($char == "!") { return array("%21", 0); }
			   if ($char == "#") { return array("%23", 0); }
			   if ($char == "$") { return array("%24", 0); }
			   if ($char == "&") { return array("%26", 0); }
			   if ($char == "\"") { return array("%27", 0); }
			   if ($char == "(") { return array("%28", 0); }
			   if ($char == ")") { return array("%29", 0); }
			   if ($char == "*") { return array("%2A", 0); }
			   if ($char == "+") { return array("%2B", 0); }
			   if ($char == ",") { return array("%2C", 0); }
			   if ($char == "/") { return array("%2F", 0); }
			   if ($char == ":") { return array("%3A", 0); }
			   if ($char == ";") { return array("%3B", 0); }
			   if ($char == "=") { return array("%3D", 0); }
			   if ($char == "?") { return array("%3F", 0); }
			   if ($char == "@") { return array("%40", 0); }
			   if ($char == "~") { return array("%7E", 0); }

			   if ($char == "%") {
			      return array(substr($str, 0, 3), 2);
			   } else {
			      return array($char, 0);
			   }
			}
		}

	   $result = "";
	   for ($i = 0; $i < strlen($string); $i++) {
	       $decstr = "";
	       for ($p = 0; $p <= 8; $p++) {
	          $decstr .= $string[$i+$p];
	       }
	       list($decodedstr, $num) = decodeURIbycharacter($decstr);
	       $result .= urldecode($decodedstr);
	       $i += $num ;
	   }
	   return $result;
	}
}

if(!function_exists("muistiin")) {
	function muistiin ($muisti, $nimi, $muistettava, $exclude="") {
		global $kukarow;

		if($muisti == "" or $nimi == "" or !is_array($muistettava)) {
			return false;
		}

		if(!is_array($exclude)) {
			$exclude = array();
		}

		//	Poistetaan vanha kysely sekä tmpquery
		$query = "	DELETE
					FROM muisti
					WHERE yhtio='$kukarow[yhtio]' and haku = '$muisti' and kuka = '$kukarow[kuka]' and nimi = '$nimi'";
		$result = mysql_query($query) or pupe_error($query);

		foreach($muistettava as $key => $value) {

			if(!in_array($key, $exclude)) {

				//	Koitetaan arpoa pari speciaalia datatyyppiä
				$array = "";
				if(is_array($value)) {
					$value = serialize($value);
					$array = "X";
				}

				$query = "	INSERT INTO muisti SET
								yhtio		= '$kukarow[yhtio]',
								kuka		= '$kukarow[kuka]',
								haku		= '$muisti',
								nimi		= '$nimi',
								var			= '$key',
								value		= '".mysql_real_escape_string($value)."',
								array 		= '$array',
								luontiaika	= now(),
								laatija		= '$kukarow[kuka]',
								muokattu	= now(),
								muokannut	= '$kukarow[kuka]'";
				$result = mysql_query($query) or pupe_error($query);
			}
		}

		return true;
	}
}

if(!function_exists("muistista")) {
	function muistista ($muisti, $nimi) {
		global $kukarow;

		$query = "	SELECT *
					FROM muisti
					WHERE yhtio='$kukarow[yhtio]' and haku = '$muisti' and kuka = '$kukarow[kuka]' and nimi = '$nimi'";
		$result = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result)>0) {
			$retval = array();
			while($row = mysql_fetch_array($result)) {

				if($row["array"] != "") {
					$retval[$row["var"]] = unserialize($row["value"]);
				}
				else {
					$retval[$row["var"]] = stripslashes($row["value"]);
				}
			}
			return $retval;
		}

		return false;
	}
}

if(!function_exists("aja_kysely")) {
	function aja_kysely() {
		global $kukarow, $hakukysely, $aja_kysely, $uusi_kysely, $tallenna_muutokset, $poista_kysely, $_POST;

		if(!table_exists("muisti") or $hakukysely == "") {
			return true;
		}

		//	Tallennetaan viimeisin kysely aina muistiin jotta voimme kutsua sitä helposti uudestaan
		if($aja_kysely == "" and $uusi_kysely == "") {
			$tallenna_muutokset = "X";
			$aja_kysely = "tmpquery";
		}

		if($tallenna_muutokset != "") {
			if($aja_kysely != "") {
				$uusi_kysely = $aja_kysely;
			}
			else {
				echo "<font class='error'>".t("Valitse kysely jonka muutokset tallennetaan")."</font>";
				return false;
			}
		}

		if($poista_kysely != "" and $aja_kysely == "") {
			echo "<font class='error'>".t("Valitse kysely jonka haluat poistaa")."</font>";
			return false;
		}

		//	Tallennetaan uusi kysely
		if($uusi_kysely != "") {
			if(is_array($_POST) and count($_POST)>0) {

				if($exclude != "") {
					$exclude = implode(",", $exclude);
				}
				else {
					$exclude = array();
				}

				//	Poistetaan vanha kysely sekä tmpquery
				$query = "	DELETE
							FROM muisti
							WHERE yhtio='$kukarow[yhtio]' and haku = '$hakukysely' and kuka = '$kukarow[kuka]' and (nimi = '$uusi_kysely' or nimi = 'tmpquery')";
				$result = mysql_query($query) or pupe_error($query);

				//	Tälläiset arvot skipataan aina
				$exclude[] = "uusi_kysely";
				$exclude[] = "aja_kysely";
				$exclude[] = "hakukysely";
				$exclude[] = "tallenna_muutokset";

				$exclude[] = "ppa";
				$exclude[] = "kka";
				$exclude[] = "vva";
				$exclude[] = "ppl";
				$exclude[] = "kkl";
				$exclude[] = "vvl";

				$exclude[] = "tee";
				$exclude[] = "toim";

				foreach($_POST as $key => $value) {

					if(!in_array($key, $exclude)) {

						//	Koitetaan arpoa pari speciaalia datatyyppiä
						$array = "";
						if(is_array($value)) {
							$value = serialize($value);
							$array = "X";
						}

						$query = "	INSERT INTO muisti SET
										yhtio		= '$kukarow[yhtio]',
										kuka		= '$kukarow[kuka]',
										haku		= '$hakukysely',
										nimi		= '$uusi_kysely',
										var			= '$key',
										value		= '$value',
										array 		= '$array',
										luontiaika	= now(),
										laatija		= '$kukarow[kuka]',
										muokattu	= now(),
										muokannut	= '$kukarow[kuka]'";
						$result = mysql_query($query) or pupe_error($query);

						//	Tallennetaan viimeisin haku
						if($aja_kysely != "tmpquery") {
							$query = "	INSERT INTO muisti SET
											yhtio		= '$kukarow[yhtio]',
											kuka		= '$kukarow[kuka]',
											haku		= '$hakukysely',
											nimi		= 'tmpquery',
											var			= '$key',
											value		= '$value',
											array 		= '$array',
											luontiaika	= now(),
											laatija		= '$kukarow[kuka]',
											muokattu	= now(),
											muokannut	= '$kukarow[kuka]'";
							$result = mysql_query($query) or pupe_error($query);
						}
					}
				}
			}

			$aja_kysely = $uusi_kysely;

			if($aja_kysely != "tmpquery") {
				echo "<font class='message'>".t("Tallennettiin kysely")." $aja_kysely</font>";
			}
			return true;
		}
		//	Poistetaan kysely
		elseif($poista_kysely != "" and $aja_kysely != "") {
			$query = "	DELETE
						FROM muisti
						WHERE yhtio='$kukarow[yhtio]' and haku = '$hakukysely' and kuka = '$kukarow[kuka]' and nimi = '$aja_kysely'";
			$result = mysql_query($query) or pupe_error($query);

			echo "<font class='message'>".t("Poistettiin kysely")." $aja_kysely</font>";

			return false;
		}
		//	Ajetaan kysely muistista
		elseif($aja_kysely != "") {
			//	Onko tämä arvo jo tallennettu?
			$query = "	SELECT *
						FROM muisti
						WHERE yhtio='$kukarow[yhtio]' and haku = '$hakukysely' and kuka = '$kukarow[kuka]' and nimi = '$aja_kysely'";
			$result = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($result)>0) {
				while($row = mysql_fetch_array($result)) {
					global ${$row["var"]};
					if($row["array"] != "") {
						$row["value"] = unserialize($row["value"]);
					}

					${$row["var"]} = $row["value"];
				}
			}

			if($aja_kysely != "tmpquery") {
				echo "<font class='message'>".t("Suoritetaan kysely")." $aja_kysely</font>";
			}
			return true;
		}

		return false;
	}
}

if(!function_exists("nayta_kyselyt")) {
	function nayta_kyselyt($haku) {
		global $kukarow, $aja_kysely;

		if(!table_exists("muisti")) {
			return false;
		}

		$ulos = "<table>";
		$ulos .= "<tr>
				<th>".t("Tallenna kysely nimellä")."</th>
				<td><input type='text' name='uusi_kysely' value='' size='30'><input type='hidden' name='hakukysely' value='$haku'></td>
			</tr>
			<tr>
				<th>".t("Aja tallennettu kysely")."</th>
				<td>
					<select name='aja_kysely'>
					<option value=''>".t("Valitse kysely")."</option>";

		$lisa = "";
		$query = "	SELECT distinct nimi
					FROM muisti
					WHERE yhtio='$kukarow[yhtio]' and haku = '$haku' and kuka = '$kukarow[kuka]' and nimi != 'tmpquery'";
		$result = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result)>0) {
			while($row = mysql_fetch_array($result)) {
				$ulos .= "<option value='$row[nimi]'>$row[nimi]</option>\n";
			}
		}

		$ulos .= "	</select>
				</td>
			</tr>\n";

		$ulos .= "<tr>
				<th>".t("Tallenna kyselyn muutokset")."</th>
			 	<td><input type='checkbox' name='tallenna_muutokset' value='X'></td>
			</tr>";

		$ulos .= "<tr>
				<th>".t("Poista kysely")."</th>
			 	<td><input type='checkbox' name='poista_kysely' value='X'></td>
			</tr>";

		$ulos .= "</table>";

		return $ulos;
	}
}

if (! function_exists('tallenna_liite')) {
	function tallenna_liite($userfile, $liitos, $liitostunnus, $selite, $kayttotarkoitus="", $tunnus=0, $jarjestys = 0) {
		global $kukarow, $yhtiorow, $_FILES, $kieli;

		$file = array();

		if(is_array($_FILES[$userfile])) {
			$file = $_FILES[$userfile];
		}
		elseif(file_exists($userfile)) {
			$a = getimagesize($userfile);

			$file["name"] 		= basename($userfile);
			$file["type"] 		= $a["mime"];
			$file["tmp_name"] 	= $userfile;
			$file["error"] 		= 0;
			$file["size"] 		= filesize($userfile);

			$_FILES[$userfile] = $file;
		}

		$tark = tarkasta_liite($userfile);

		if($tark !== true or $file["error"] == 4) {
			return false;
		}

		$data = mysql_real_escape_string(file_get_contents($file["tmp_name"]));

		$filename = $file["name"];
		$filetype = $file["type"];
		$filesize = $file["size"];

		//	Tarkastetaan, että kuvatunnus on oikea..
		if($tunnus > 0) {
			$query = "	SELECT tunnus FROM liitetiedostot WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
			$result = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($result) == 0) {
				$tunnus = 0;
			}
		}

		//	Onko meillä jokin kuvatyyppi?
		list($type, $crap) = explode("/", $filetype);

		if($type == "image") {
			$size = getimagesize($file["tmp_name"], $imageinfo);
			$image_width 	= $size[0];
			$image_height 	= $size[1];
			$image_bits 	= $size["bits"];
			$image_channels	= $size["channels"];
			//echo "<pre>".print_r($size, true)."</pre>";

			unset($size);
		}
		else {
			$image_width 	= "";
			$image_height 	= "";
			$image_bits 	= "";
			$image_channels	= "";
		}

		// lisätään kuva
		if($tunnus > 0) {
			$query = "	UPDATE liitetiedostot SET
							data     		= '$data',
							selite   		= '".trim($selite)."',
							filename 		= '$filename',
							filesize 		= '$filesize',
							filetype 		= '$filetype',
							image_width		= '$image_width',
							image_height	= '$image_height',
							image_bits		= '$image_bits',
							image_channels	= '$image_channels',
							kayttotarkoitus	= '$teetyyppi',
							jarjestys		= '$jarjestys',
							laatija			= '$kukarow[kuka]',
							luontiaika		= now()
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$id = $tunnus;
		}
		else {
			if ((int) $jarjestys == 0) {
				$query = "	SELECT max(jarjestys) jarjestys
							FROM liitetiedostot
							WHERE yhtio 		= '$kukarow[yhtio]'
							and liitos   		= '$liitos'
							and liitostunnus 	= '$liitostunnus'";
				$result = mysql_query($query) or pupe_error($query);
				$row = mysql_fetch_array($result);

				$jarjestys = $row["jarjestys"]+1;
			}

			$query = "	INSERT INTO liitetiedostot SET
					  	yhtio    		= '$kukarow[yhtio]',
					  	liitos   		= '$liitos',
					  	liitostunnus 	= '$liitostunnus',
					  	data     		= '$data',
					  	selite   		= '".trim($selite)."',
					  	filename 		= '$filename',
					  	filesize 		= '$filesize',
					  	filetype 		= '$filetype',
					  	image_width		= '$image_width',
					  	image_height	= '$image_height',
					  	image_bits		= '$image_bits',
					  	image_channels	= '$image_channels',
					  	kayttotarkoitus	= '$kayttotarkoitus',
					  	jarjestys		= '$jarjestys',
					  	laatija			= '$kukarow[kuka]',
					  	luontiaika		= now()";
			$result = mysql_query($query) or pupe_error($query);
			$id = mysql_insert_id();
		}
		return $id;
	}
}

if (! function_exists('tarkasta_liite')) {
	function tarkasta_liite($userfile, $sallitut_tiedostot="") {
		global $kukarow, $yhtiorow, $_FILES, $kieli;

		if(!is_array($_FILES[$userfile])) {
			return false;
		}

		$file = $_FILES[$userfile];

		// otetaan file extensio
		$path_parts = pathinfo($file['name']);
		$ext = strtoupper($path_parts['extension']);
		if ($ext == "JPEG") $ext = "jpg";

		//	Sallitut tiedostot on aina upper
		if(is_array($sallitut_tiedostot)){
			foreach($sallitut_tiedostot as &$s) {
				$s = strtoupper($s);
			}
		}

		//	Ei saatu erroreita. jatketaan..
		if($file["error"] == 0) {

			//	Paketti riittävän pieni mysql:lle
			$query = "SHOW variables like 'max_allowed_packet'";
			$result = mysql_query($query) or pupe_error($query);
			$varirow = mysql_fetch_array($result);

			if($file["size"] < $varirow[1]) {
				//	Tämä on ainoa haara jossa voimme jatkaa!
				if(!is_array($sallitut_tiedostot) and $sallitut_tiedostot == "") {
					return true;
				}
				elseif(in_array(strtoupper($ext), $sallitut_tiedostot)) {
					return true;
				}
				else {
					if(count($sallitut_tiedostot)>1) {
						//	Kaunistellaan..
						return "<font class='error'>".t("VIRHE! Tiedostomuoto '$ext' ei kelpaa, sallitut tiedostomuodot on %s ja %s", $kieli, implode(", ", array_slice($sallitut_tiedostot, 0, -1)), end($sallitut_tiedostot)).".</font><br><br>";

					}
					else {
						return "<font class='error'>".t("VIRHE! Tiedostomuoto '$ext' ei kelpaa, sallittu tiedostomuoto on %s", $kieli, $sallitut_tiedostot[0]).".</font><br><br>";
					}
				}
			}
			else {
				return "<font class='error'>".t("VIRHE! Ladattu tiedosto oli liian suuri! Suurin sallittu tiedostokoko on %s", $kieli, size_readable($file["size"]))."!</font><br><br>";
			}
		}
		elseif($file["error"] == 1 or $file["error"] == 2) {
			return "<font class='error'>".t("VIRHE! Tiedosto on liian suuri!")."!</font><br><br>";
		}
		elseif($file["error"] == 3) {
			return "<font class='error'>".t("VIRHE! Tiedoston lataus epäonnistui!")."!</font><br><br>";
		}
		elseif($file["error"] == 4) {
			return true;
		}
		elseif($file["error"] == 7) {
			return "<font class='error'>".t("VIRHE! Palvelinasetuksissa on virhe!")."!</font><br><br>";
		}
		else {
			return "<font class='error'>".t("VIRHE! Tapahtui virhe tallennettaessa tiedostoa!")."!</font><br><br>";
		}
	}
}

if (! function_exists('hae_liite')) {
	function hae_liite($tunnus, $liitos, $palautus="") {
		global $kukarow, $yhtiorow;

		$query = "select * from liitetiedostot where tunnus='$tunnus' and liitos = '$liitos'";
		$liiteres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($liiteres) > 0) {
			$liiterow = mysql_fetch_array($liiteres);

			if($palautus != "") {
				return $liiterow;
			}
			return $liiterow["data"];
		}

		return false;
	}
}

if(!function_exists("nayta_asiakashaku")) {
	function nayta_asiakashaku($liitostunnus, $formi, $vars="") {
		global $kukarow;

		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus'";
		$result = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result) == 1) {
			$asiakasrow = mysql_fetch_array($result);

			$disabled 	= "DISABLED";
			$nimi 		= $asiakasrow["nimi"]." ".$asiakasrow["nimitark"];

		}
		else {
			$disabled 	= "";
			$nimi 		= "";
			$id			= "";
		}

		return "<input type='hidden' id='liitos' name='liitostunnus'><input type='text' id='haku' value='$nimi' name='haku' size='50' onkeyup=\"popUp(event, 'asiakashaku', '0', '-20', '{$_SERVER["SCRIPT_NAME"]}?tee=ASIAKASHAKU&formi=$formi&url={$_SERVER["SCRIPT_NAME"]}&haku='+this.value);\" $disabled>&nbsp;&nbsp;<a href='#' onclick=\"document.getElementById('haku').disabled=false; document.getElementById('haku').value=''; return false;\">".t("Vaihda asiakasta")."</a>";
	}
}

/*
if(!function_exists("ake_log")) {
	function ake_log($rekkari) {
		global $kukarow, $useslave;

		echo "#1 JOTAIN...$useslave<br>";


		$tulislave = $useslave;
		if ($useslave != 0) {
			// EI käytetä slavea
			$useslave = 0;
			// otetaan tietokanta connect uudestaan
			if (file_exists("connect.inc")) {
				require ("connect.inc");
			}
			else {
				require ("inc/connect.inc");
			}
		}

		echo "#2 JOTAIN...$useslave<br>";

		$query = "	INSERT into ake_log SET
					yhtio	= '$kukarow[yhtio]',
				  	kuka	= '$kukarow[kuka]',
				  	rekno	= '$rekkari',
				  	aika	= now()";
		$result = mysql_query($query) or pupe_error($query);

		if ($tulislave != 0) {
			$useslave = $tulislave;
			// otetaan tietokanta connect uudestaan
			if (file_exists("connect.inc")) {
				require ("connect.inc");
			}
			else {
				require ("inc/connect.inc");
			}
		}

		echo "#3 JOTAIN...$useslave<br>";

	}
}
*/

if(!function_exists("tarvitaanko_intrastat")) {
	function tarvitaanko_intrastat($maa_lahetys, $maa_maara) {

		// otetaan sisään lähetysmaan ja määrämaan maakoodi

		// palautetaan:
		// tyhjää = ei tarvita intrastatata
		// -1 = kuuluu vienti-ilmoitukseen
		// -2 = kuuluu tuonti-ilmoitukseen

		global $yhtiorow;

		$maa_lahetys = strtoupper(trim($maa_lahetys));
		$maa_maara = strtoupper(trim($maa_maara));
		$yhtiorow_maa = strtoupper($yhtiorow["maa"]);
		$ultilno = "";

		// kokeillaan arpoa intrastat käsittelyä, molemmat maat pitää olla EU maita
		if ($maa_lahetys != "" and $maa_maara != "") {
			$query = "SELECT DISTINCT koodi FROM maat WHERE koodi in ('$maa_lahetys','$maa_maara') AND eu = 'ON'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 2) {
				if ($maa_lahetys == $yhtiorow_maa and $maa_maara != $yhtiorow_maa) {
					$ultilno = "-1"; // miinus yks tarkoittaa, että lisätiedot pitää syöttää ja VIENTI-intrastat pitää lähettää
				}
				elseif ($maa_maara == $yhtiorow_maa and $maa_lahetys != $yhtiorow_maa) {
					$ultilno = "-2"; // miinus kaks tarkoittaa, että lisätiedot pitää syöttää ja TUONTI-intrastat pitää lähettää
				}
			}
		}

		return $ultilno;
	}
}

if(!function_exists("tee_asiakashaku")) {
	function tee_asiakashaku($ytunnus, $formi, $lopetus="", $div="") {
		global $yhtiorow, $kukarow, $palvelin2;

		if (trim($konserni) != '') {
			$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
			$result = mysql_query($query) or pupe_error($query);
			$konsyhtiot = "";

			while ($row = mysql_fetch_array($result)) {
				$konsyhtiot .= " '".$row["yhtio"]."' ,";
			}
			$konsyhtiot = " yhtio in (".substr($konsyhtiot, 0, -1).") ";
		}
		else {
			$konsyhtiot = " yhtio = '$kukarow[yhtio]' ";
		}

		$ytunnus = addslashes(trim($ytunnus));
		$limit   = 300; // montako riviä on maksimi
		$monta   = 0;

		//	Jos käyttäjällä on valittu piirejä niin sallitaan vain ko. piirin/piirien hakeminen
		if($kukarow["piirit"] != "") {
			$asiakasrajaus = "and piiri IN ($kukarow[piirit])";
		}
		else {
			$asiakasrajaus="";
		}
		if($div == "") $div = "ajax_menu";

		if (is_numeric($asiakasid) and $asiakasid > 0) {
			$query	= "	SELECT *
						FROM asiakas
						WHERE $konsyhtiot and tunnus='$asiakasid' $asiakasrajaus";
			$result = mysql_query($query) or pupe_error($query);
			$monta  = mysql_num_rows($result);
		}
		elseif (strlen($ytunnus) >= 3) {
			if (substr($ytunnus,0,1) == "£") {
				// jos eka merkki £ etsitään laskun tunnuksella
				// eka merkki pois alusta
				$ytunnus = substr($ytunnus, 1);

				$query  = "	select *, concat('£', tunnus) tunnus, concat('£', tunnus) ytunnus, ytunnus spec_ytunnus, liitostunnus spec_tunnus
							FROM lasku
							WHERE $konsyhtiot and tunnus='$ytunnus'";
				$result = mysql_query($query) or pupe_error($query);
				$monta  = mysql_num_rows($result);
			}

			if (substr($ytunnus,0,1) == "*") {
				// jos eka merkki on * etsitään laskuilta asiakastietoja

				// eka merkki pois alusta
				$ytunnus = substr($ytunnus, 1);

				// kokeillaan ihan asiakkaan nimellä laskulta
				$query	= "	SELECT nimi, nimitark, osoite, postino, postitp, toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp, concat('£',max(tunnus)) ytunnus, concat('£',max(tunnus)) tunnus, yhtio
							FROM lasku use index (asiakasnimi)
							WHERE $konsyhtiot and tila != 'D' and match (nimi) against ('$ytunnus*' IN BOOLEAN MODE)
							group by 1,2,3,4,5 order by 1";
				$result = mysql_query($query) or pupe_error($query);
				$monta  = mysql_num_rows($result);

				if ($monta != 0) $monta  = 2; // aina selailuun niin menee liitostunnukset ja ytunnukset oikein
			}

			if ($monta == 0  and substr($ytunnus,0,1) == "#") {
				// jos eka merkki on # etsitään toimitusnimen perusteella

				// eka merkki pois alusta
				$ytunnus = substr($ytunnus, 1);

				// kokeillaan ihan asiakkaan nimellä laskulta
				$query	= "	SELECT toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp, nimi, nimitark, osoite, postino, postitp, concat('£',max(tunnus)) ytunnus, concat('£',max(tunnus)) tunnus, yhtio
							FROM lasku use index (asiakastoim_nimi)
							WHERE $konsyhtiot and tila != 'D' and match (toim_nimi) against ('$ytunnus*' IN BOOLEAN MODE)
							group by 1,2,3,4,5 order by 1";
				$result = mysql_query($query) or pupe_error($query);
				$monta  = mysql_num_rows($result);

				if ($monta != 0) $monta  = 2; // aina selailuun niin menee liitostunnukset ja ytunnukset oikein
			}


			if ($toim == "TYOMAARAYS") {
				$query = "	SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, concat('£',max(lasku.tunnus)) ytunnus, concat('£',max(lasku.tunnus)) tunnus, lasku.yhtio
							FROM lasku
							JOIN tyomaarays ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
							WHERE lasku.yhtio='$kukarow[yhtio]'
							and lasku.tila in ('A','L','N')
							and tyomaarays.rekno = '$ytunnus'
							group by 1,2,3,4,5 order by 1
							LIMIT 50";
				$result = mysql_query($query) or pupe_error($query);
				$monta  = mysql_num_rows($result);

				if ($monta == 0) {
					$query = "	SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, concat('£',max(lasku.tunnus)) ytunnus, concat('£',max(lasku.tunnus)) tunnus, lasku.yhtio
								FROM lasku
								JOIN tyomaarays ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
								WHERE lasku.yhtio='$kukarow[yhtio]'
								and lasku.tila in ('A','L','N')
								and tyomaarays.valmnro = '$ytunnus'
								group by 1,2,3,4,5 order by 1
								LIMIT 50";
					$result = mysql_query($query) or pupe_error($query);
					$monta  = mysql_num_rows($result);
				}
			}

			if ($monta == 0) {
				// exactihaku
				$query	= "	(SELECT * FROM asiakas use index (ytunnus_index) WHERE $konsyhtiot and ytunnus = '$ytunnus' and ytunnus!='' and laji != 'P' $asiakasrajaus)
							UNION
							(SELECT * FROM asiakas use index (asno_index) WHERE $konsyhtiot and asiakasnro = '$ytunnus' and asiakasnro!=0 and laji != 'P' $asiakasrajaus)
							UNION
							(SELECT * FROM asiakas use index (ovttunnus_index) WHERE $konsyhtiot and ovttunnus = '$ytunnus' and ovttunnus!='' and laji != 'P' $asiakasrajaus)
							UNION
							(SELECT * FROM asiakas use index (toim_ovttunnus_index) WHERE $konsyhtiot and toim_ovttunnus = '$ytunnus' and toim_ovttunnus!='' and laji != 'P' $asiakasrajaus)
							ORDER BY nimi";
				$result = mysql_query($query) or pupe_error($query);
				$monta  = mysql_num_rows($result);
			}

			if ($monta == 0) {
				// kokeillaan ytunnuksen alulla
				$query	= "	SELECT *
							FROM asiakas use index (ytunnus_index)
							WHERE $konsyhtiot
							and ytunnus like '$ytunnus%'
							and ytunnus!=''
							and laji != 'P'
							$asiakasrajaus
							ORDER BY asiakas.nimi";
				$result = mysql_query($query) or pupe_error($query);
				$monta  = mysql_num_rows($result);
			}

			if ($monta == 0 and is_string($ytunnus)) {
				// arvailuhaku
				$query	= "	(SELECT * FROM asiakas use index (asiakasnimi) WHERE $konsyhtiot and nimi like '%$ytunnus%' and nimi!='' and laji != 'P' $asiakasrajaus)
							UNION
							(SELECT * FROM asiakas use index (asiakasnimitark) WHERE $konsyhtiot and nimitark like '%$ytunnus%' and nimitark!='' and laji != 'P' $asiakasrajaus)
							UNION
							(SELECT * FROM asiakas use index (asiakastoim_nimi) WHERE $konsyhtiot and toim_nimi like '%$ytunnus%' and toim_nimi!='' and laji != 'P' $asiakasrajaus)
							UNION
							(SELECT * FROM asiakas use index (asiakastoim_nimitark) WHERE $konsyhtiot and toim_nimitark like '%$ytunnus%' and toim_nimitark!='' and laji != 'P' $asiakasrajaus)
							UNION
							(SELECT * FROM asiakas use index (ovttunnus_index) WHERE $konsyhtiot and ovttunnus like '%$ytunnus%' and ovttunnus!='' and laji != 'P' $asiakasrajaus)
							UNION
							(SELECT * FROM asiakas WHERE $konsyhtiot and postitp like '%$ytunnus%' and postitp!='' and laji != 'P' $asiakasrajaus)
							ORDER BY nimi";
				$result = mysql_query($query) or pupe_error($query);
				$monta  = mysql_num_rows($result);
			}
		}

		echo "<table>
				<caption>Valitse asiakas</caption>";

		if($monta > 0 and $monta < 200) {
			echo "	<tr>
						<th style='border: 1px solid;'>".t("Asiakas")."</th><th style='border: 1px solid;'>".t("Toimitusosoite")."</th><td></td>
					</tr>";
			while($row = mysql_fetch_array($result)) {
				//ajaxPost('$formi', '$urli?' , '$div', false, true);
				echo "
					<tr class='aktiivi'>
						<td style='border: 1px solid;'>$row[nimi] $row[nimitark]</td>
						<td style='border: 1px solid;'>$row[toim_nimi] $row[toim_nimitark]</td>
						<td><a href='' onclick=\"document.getElementById('liitos').value='$row[tunnus]'; document.getElementById('haku').value='$row[nimi] $row[nimitark]'; document.getElementById('haku').disabled='true'; asval=document.getElementById('asiakashaku'); asval.innerHTML=''; asval.style.display='none'; ajaxPost('$formi', '$urli?from=asiakasvalinta' , '$div', false, true); return false;\">".t("Valitse")."</a></td>
					</tr>";
			}
		}

		//	Tsekataan josko saataisiin perustaa uusi asiakas?
		if($lopetus != "") {
			echo "	<tr>
						<td class='back'><font class='message'>".t("Eikö löydy")."?</font></td>
					</tr>";

			$query = "	SELECT yhtio
						FROM oikeu
						WHERE yhtio	= '$kukarow[yhtio]'
						and kuka	= '$kukarow[kuka]'
						and nimi	= 'yllapito.php'
						and alanimi = 'asiakas'
						and paivitys != ''
						LIMIT 1";
			$oikeures = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($oikeures) > 0) {
				echo "	<tr>
							<td class='back'><a href='{$palvelin2}yllapito.php?laji=Y&uusi=1&toim=asiakas&lopetus=$lopetus'>".t(" Perusta uusi yritysasiakas")."</a></td>
						</tr>";
			}

			$query = "	SELECT yhtio
						FROM oikeu
						WHERE yhtio	= '$kukarow[yhtio]'
						and kuka	= '$kukarow[kuka]'
						and nimi	= 'yllapito.php'
						and alanimi = 'asiakas!!!VAHITTAISMYYNTI!!!true&laji=H'
						and paivitys != ''
						LIMIT 1";
			$oikeures = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($oikeures) > 0) {
				echo "	<tr>
							<td class='back'><a href='{$palvelin2}yllapito.php?laji=H&uusi=1&toim=asiakas&lopetus=$lopetus'>".t(" Perusta uusi henkilöasiakas")."</a></td>
						</tr>";
			}
		}

		echo "	</table>";
	}
}


if(!function_exists("sendSMS")) {
	function sendSMS($smsnumero, $smsviesti) {
		global $sms_palvelin, $sms_user, $sms_pass;

		if($sms_palvelin != "" and $sms_user != "" and $sms_pass != "" and $smsnumero != "" and $smsviesti != "") {

			$smsviesti = urlencode($smsviesti);

			$retval = file_get_contents("$sms_palvelin?user=$sms_user&pass=$sms_pass&numero=$smsnumero&viesti=$smsviesti");
			$smsviesti = urldecode($smsviesti);

			if(trim($retval) == "0") {
				echo "<font class='info'>SMS-viesti lähetetty numeroon $smsnumero</font><br>";
			}
		}
	}
}


if(!function_exists("on_puhelinnumero")) {
	function on_puhelinnumero($numero) {

		//	Stripataan vähän turhia merkkejä
		$checkno = preg_replace("/[\r\n\s\t\(\)\{\}\-]/", "", $numero);

		//	Jos meille jäi vain numeroita se on varmaan aika oikein
		if(preg_replace("/[+0-9]/", "", $checkno) == "" and strlen($checkno)>0) {
			return $checkno;
		}
		else {
			return false;
		}
	}
}


?>
