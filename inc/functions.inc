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
		$puperror  = "User:   $kukarow[nimi] ($kukarow[kuka]) @ $yhtiorow[nimi] ($yhtiorow[yhtio])\n\n";

		$nro = 1;
		foreach ($debuggi as $debuggii) {
			$puperror .= "File $nro:   $debuggii[file]\n";
			$puperror .= "Line $nro:   $debuggii[line]\n";
			$nro++;
		}

		$puperror .= "\n";
		$puperror .= "Script: $_SERVER[PHP_SELF]\n";
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
	function tv1dateconv($date, $pitka = "", $lyhyt = "") {

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
			if ($lyhyt == "LYHYT") return substr($date,2,2)."-". (int) substr($date,5,2)."-". (int) substr($date,8,2).$jatko;
			else return substr($date,0,4)."-".substr($date,5,2)."-".substr($date,8,2).$jatko;
		}
		else {
			if ($lyhyt == "LYHYT") return (int) substr($date,8,2).".". (int) substr($date,5,2).".".substr($date,2,2).$jatko;
			else return substr($date,8,2).".".substr($date,5,2).".".substr($date,0,4).$jatko;
		}
	}
}

if (!function_exists("lopetus")) {
	function lopetus($lopetus, $meta="") {
		global $kukarow, $palvelin2;

		$lopetukset = explode("/SPLIT/", $lopetus);

		$lask = 0;
		$edlopetus = "";

		foreach ($lopetukset as $lopetus) {
			if ($lopetus != "") {

				// Jotta urlin parametrissa voisi päässätä toisen urlin parametreineen
				$lopetus_clean = $lopetus;

				$lopetus = str_replace('////','?',               $lopetus);
				$lopetus = preg_replace('/([^:])\/\/\//','\\1#', $lopetus);
				$lopetus = preg_replace('/([^:])\/\//','\\1&',   $lopetus);

				preg_match('/\/NIMILISA=(.*?)\//',$lopetus, $nimilisa);
				$lopetus = preg_replace('/\/NIMILISA=(.*?)\//','',   $lopetus);

				if ($meta != "") {
					echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$lopetus'>";
					exit;
				}
				else {

					preg_match("/^([^\?&]*)\??/i", $lopetus, $nimi);
					preg_match("/toim=([^&]*)&?/i", $lopetus, $alanimi);

					$nimet = explode("/", str_replace($palvelin2, "", $nimi[1]));

					for($i=0; $i<count($nimet); $i++) {

						$nimi = "";

						for ($j = $i; $j<count($nimet); $j++) {
							$nimi .= $nimet[$j]."/";
						}

						$nimi = substr($nimi,0,-1);

						$query = "	SELECT nimitys
									FROM oikeu
									WHERE yhtio 	= '$kukarow[yhtio]'
									and kuka 		= ''
									and nimi		= '$nimi'
									and alanimi		= '$alanimi[1]'
									LIMIT 1";
						$res = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($res) > 0) {
							$row = mysql_fetch_assoc($res);
							break;
						}
					}

					if ($lask > 0) {
						if (strrpos($lopetus, "#") !== FALSE) {
							$alku = substr($lopetus, 0, strrpos($lopetus, "#"));
							$loppu = substr($lopetus, strrpos($lopetus, "#"));

							$lopetus = $alku."&lopetus=".$lopetukset[0].$loppu;
						}
					}

					if ($edlopetus != "") {
						if (strpos($lopetus, "?") === FALSE) {
							$lopetus .= "?";
						}
						else {
							$lopetus .= "&";
						}

						$lopetus .= "lopetus=$edlopetus";
					}

					if ($nimilisa[1] != "") {
						$nlisa = " / ".$nimilisa[1];
					}
					else {
						$nlisa = "";
					}

					$row["nimitys"] = trim(str_ireplace("UUSI", "", $row["nimitys"]));

					if ($row["nimitys"] != "") echo "<a href='$lopetus'>&laquo; ".t("Palaa ohjelmaan").": $row[nimitys]$nlisa</a><br>";
					else echo "<a href='$lopetus'>&laquo; ".t("Palaa edelliseen näkymään")."</a><br>";

					$edlopetus = $lopetus_clean;
				}

				$lask++;
			}
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

if (!function_exists("date2mysql")) {
	function date2mysql ($date) {
		//kääntää pp.kk.vvvv muodon mysqk muotoon vvvv-kk-mm
		return substr($date,6,4)."-".substr($date,3,2) . "-".substr($date,0,2);
	}
}

if (!function_exists("avoin_kori")) {
	function avoin_kori() {
		global $kukarow, $yhtiorow;

		// näyttää ostoskori-linkin verkkokaupassa ja tuoteselaushaussa
		if ($kukarow["kuka"] != "www" and $kukarow["kesken"] > 0) {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and tila = 'N' and tunnus = '$kukarow[kesken]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$laskurow = mysql_fetch_assoc($result);

				$query = "	SELECT round(sum(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * if (tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100))),$yhtiorow[hintapyoristys]) summa
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]' and tyyppi != 'D'";
				$result = mysql_query($query) or pupe_error($query);
				$row = mysql_fetch_assoc($result);

				$linkki = "<a href='#' onclick=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=tilatut&osasto=$osasto&try=$try')\"><strong>".t("Ostoskori: Tilaus %s%s, yhteensä %s %s", $kieli, $laskurow["tunnus"], " ".$laskurow["viesti"], number_format($row["summa"], 2, ',', ' '), $laskurow["valkoodi"])."</strong></a><br><br>";


				return "<center>$linkki</center>";
			}
		}
	}
}

if (!function_exists("maa")) {
	function maa($code, $kieli="") {
		// tarvitaan yhtiörowta, kukarowta ja tieto slaven:n käytöstä
		global $yhtiorow, $kukarow, $useslave, $link, $verkkokauppa;

		$query = sprintf("SELECT nimi FROM maat where koodi = '%s' LIMIT 1", mysql_real_escape_string(substr($code, 0 ,2)));
		$res = mysql_query($query) or pupe_error($query);
		$maa = mysql_fetch_assoc($res);

		// otetaan pois maa koodi
		if ($kieli=="") {
			$kieli = $GLOBALS["kieli"];
		}
		if (function_exists("mb_strtolower")) {
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
			$aliasrow = mysql_fetch_assoc($aliasresult);

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
			$prow = mysql_fetch_assoc($pres);

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
			$varcheckrow = mysql_fetch_assoc($varcheckres);
			return $varcheckrow['tunnus'];
		}
	}
}

if (!function_exists("onkotulostusalueita")) {
	function onkotulostusalueita($hyllyalue, $hyllynro, $varasto, $yhtio = '') {
		global $kukarow, $yhtiorow;
		/*
		$varastolisa = "";

		// voidaan zekata onko varastoalue jossain tietyssä varastossa...
		if ($varasto != "") {
			$varastolisa = " and tunnus='$varasto'";
		}*/

		//Jos yhtiö tulee parametrinä niin katsotaa, ettei se ole ihan mitä sattuu
		if ($yhtio != "") {
			$query	= "	SELECT GROUP_CONCAT(distinct yhtio) yhtiot
						from yhtio
						where yhtio='$kukarow[yhtio]' or (konserni = '$yhtiorow[konserni]' and konserni != '')";
			$pres = mysql_query($query) or pupe_error($query);
			$prow = mysql_fetch_assoc($pres);

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

		$query = "	SELECT nimi
					FROM varaston_tulostimet
					WHERE concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper('$hyllyalue'), 5, '0'),lpad(upper('$hyllynro'), 5, '0'))
					and	concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper('$hyllyalue'), 5, '0'),lpad(upper('$hyllynro'), 5, '0'))
					and	yhtio = '$yhtiolisa'
					and varasto ='$varasto'";
		$varcheckres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($varcheckres) == 0) {
			return "";
		}
		else {
			$varcheckrow = mysql_fetch_assoc($varcheckres);
			return trim($varcheckrow['nimi']);
		}
	}
}

if (!function_exists("pdf_substr")) {
	function pdf_substr($str, $len, $pdf, $param) {

		for($s = strlen($str); $s > 0; $s--) {
			if ($pdf->strlen($str, $param) > $len) {
				$str = substr($str, 0, $s);
			}
			else {
				break;
			}
		}

		return $str;
	}
}

if (!function_exists("pdf_fontfit")) {
	function pdf_fontfit($str, $len, $pdf, $param) {

		$ffok = 0;

		if ($pdf->strlen($str, $param) > $len) {

			for($fkoko = $param["height"]; $fkoko >= 7; $fkoko--) {

				$param["height"] = $fkoko;

				if ($pdf->strlen($str, $param) <= $len) {
					$ffok = 1;
					break;
				}
			}
		}

		if (!$ffok) {
			$str = pdf_substr($str, $len, $pdf, $param);
		}

		return array($str, $param);
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

if (!function_exists("vapauta_sarjanumerot")) {
	function vapauta_sarjanumerot($toim, $tilausnumero, $lisa="") {
		global $kukarow, $yhtiorow;

		$query = "	SELECT tilausrivi.tunnus, (tilausrivi.varattu+tilausrivi.jt) varattu, tilausrivi.tuoteno, tuote.sarjanumeroseuranta, tilausrivin_lisatiedot.tilausrivilinkki
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.otunnus = '$kukarow[kesken]'
					$lisa ";
		$sres = mysql_query($query) or pupe_error($query);

		while ($srow = mysql_fetch_assoc($sres)) {

			if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
				// merktaan siirtolistatunnus nollaks
				$query = "UPDATE sarjanumeroseuranta set siirtorivitunnus = 0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and siirtorivitunnus='$srow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
			elseif ($srow["varattu"] < 0) {
				// dellataan koko rivi jos sitä ei ole vielä myyty
				$query = "DELETE from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]' and myyntirivitunnus=0";
				$sarjares = mysql_query($query) or pupe_error($query);

				if (mysql_affected_rows() == 0) {
					// merkataan osorivitunnus nollaksi
					$query = "UPDATE sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
			}
			else {
				// merkataan myyntirivitunnus nollaks
				if ($srow["sarjanumeroseuranta"] == "E" or $srow["sarjanumeroseuranta"] == "F" or $srow["sarjanumeroseuranta"] == "G") {
					$query = "	DELETE FROM sarjanumeroseuranta
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$srow[tuoteno]'
								and myyntirivitunnus = '$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
				else {
					$query = "	UPDATE sarjanumeroseuranta
								SET myyntirivitunnus = 0
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$srow[tuoteno]'
								and myyntirivitunnus = '$srow[tunnus]'";
					$sarjares = mysql_query($query) or pupe_error($query);
				}
			}

			// Onko tätä ostotilauksella?
			if ($srow["tilausrivilinkki"] > 0) {
				$query = "	UPDATE tilausrivi
							SET tyyppi = 'D'
							WHERE yhtio 	= '$kukarow[yhtio]'
							and tunnus  	= '$srow[tilausrivilinkki]'
							and tyyppi 		= 'O'
							and uusiotunnus = 0";
				$siirtores = mysql_query($query) or pupe_error($query);
			}
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

		if (is_array($varasto)) {
			$varasto_apu = '';
			foreach ($varasto as $varasto_tunnus) {
				$varasto_apu .= (int) $varasto_tunnus.",";
			}
			$varasto = substr($varasto_apu, 0, -1);
		}
		else {
			$varasto = (int) $varasto;
		}

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
			$valinta1 = " and varastopaikat.tunnus in ($varasto) ";
			$valinta3 = " HAVING varastopaikat.tyyppi is not null ";
		}

		$query = " 	SELECT *
					from tuote
					where tuote.yhtio = '$yhtio'
					and tuote.tuoteno = '$tuoteno'";
		$asires = mysql_query($query) or pupe_error($query);
		$asirow = mysql_fetch_assoc($asires);

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

					while ($isirow = mysql_fetch_assoc($isiresult)) {

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
			$tuotteet_str 		= "'',";
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
					and tuote.tuoteno IN ($tuotteet_str)
					and tuote.tuoteno != ''
					$valinta3
					ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys";
		$varresult = mysql_query($query) or pupe_error($query);

		$varasto_myytavissa = array();

		if ($summaus == "KAIKKI") {
			$perhe_myytavissa = array();
		}

		while ($saldorow = mysql_fetch_assoc($varresult)) {

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

		if (is_array($varasto)) {
			$varasto_apu = '';
			foreach ($varasto as $varasto_tunnus) {
				$varasto_apu .= (int) $varasto_tunnus.",";
			}
			$varasto = substr($varasto_apu, 0, -1);
		}
		else {
			$varasto = (int) $varasto;
		}

		$query  = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
		$result = mysql_query($query) or die($query.mysql_error());
		$tuote  = mysql_fetch_assoc($result);

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
				$prow = mysql_fetch_assoc($pres);

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
						$superrow = mysql_fetch_assoc($superjtres);

						$yhtiolisa	= $yhtio;
						$tuoteno 	= $superrow["toim_tuoteno"];
					}
					else {
						$yhtiolisa	= $yhtio;
					}
				}
				elseif ($yhtio == $kukarow["yhtio"]) {
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
				$valinta1 = " varastopaikat.tunnus in ($varasto) and ";
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
			if (($tuote["sarjanumeroseuranta"] == "E" or $tuote["sarjanumeroseuranta"] == "F" or $tuote["sarjanumeroseuranta"] == "G") and $era != '') {
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
				$ennrow = mysql_fetch_assoc($ennresult);

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

				while ($row = mysql_fetch_assoc($result)) {

					// Saldot
					$saldo += $row["saldo"];

					// Myyntirivien, varastosiirtojen ja valmistusten ennakkopoistot ja kerätyt rivit
					if (($tuote["sarjanumeroseuranta"] == "E" or $tuote["sarjanumeroseuranta"] == "F" or $tuote["sarjanumeroseuranta"] == "G") and $era != '') {
						$query = "	SELECT
									ifnull(sum(if (tilausrivi.keratty!='', tilausrivi.varattu, 0)),0) keratty,
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
									ifnull(sum(if (tilausrivi.keratty!='', tilausrivi.varattu, 0)),0) keratty,
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
					$ennrow = mysql_fetch_assoc($ennresult);

					$myytavissa += $row["saldo"] - $ennrow["varattu"] - $row["saldo_varattu"];
					$hyllyssa   += $row["saldo"] - $ennrow["keratty"];

					if ($pvm != '') {
						// jos lasketaan tulevaisuuteen niin otetaan tietenkin huomioon jo kerätyt rivit
						if (($tuote["sarjanumeroseuranta"] == "E" or $tuote["sarjanumeroseuranta"] == "F" or $tuote["sarjanumeroseuranta"] == "G") and $era != '') {
							$query = "	SELECT
										ifnull(sum(if (tilausrivi.keratty!='', tilausrivi.varattu, 0)),0) keratty
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
										ifnull(sum(if (tilausrivi.keratty!='', tilausrivi.varattu, 0)),0) keratty
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
						$kerarow = mysql_fetch_assoc($keraresult);

						$myytavissa -= $kerarow["keratty"];
						$hyllyssa -= $kerarow["keratty"];

						// jos lasketaan tulevaisuuteen niin otetaan varastoonvalmistukset huomioon
						if (($tuote["sarjanumeroseuranta"] == "E" or $tuote["sarjanumeroseuranta"] == "F" or $tuote["sarjanumeroseuranta"] == "G") and $era != '') {
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
						$valmrow = mysql_fetch_assoc($valmresult);

						$myytavissa += $valmrow["varattu"];
					}
				}


				// jos lasketaan tulevaisuuteen niin otetaan ostorivit huomioon
				if ($pvm != '') {
					if (($tuote["sarjanumeroseuranta"] == "E" or $tuote["sarjanumeroseuranta"] == "F" or $tuote["sarjanumeroseuranta"] == "G") and $era != '') {
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
						$osttarkrow = mysql_fetch_assoc($osttarkres);

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
							$osttarkrow = mysql_fetch_assoc($osttarkres);

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
								$osttarkrow = mysql_fetch_assoc($osttarkres);

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
									$osttarkrow = mysql_fetch_assoc($osttarkres);
								}
							}
						}
					}

					$ostresult = mysql_query($query) or die($query);
					$ostrow = mysql_fetch_assoc($ostresult);

					$myytavissa += $ostrow["varattu"];
				}

				// katsotaan löytyykö tuotetta varattuna joltain muulta paikalta, jota ei ole enää olemassa tuotepaikoissa
				// ekaks haetaan ihan kaikki nykyiset paikat suoraan mysql muotoon
				$query = "	SELECT group_concat(\"'\",rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0'),\"'\") paikat
							FROM tuotepaikat
							WHERE yhtio='$yhtiolisa' and tuoteno='$tuoteno'";
				$ennresult = mysql_query($query) or die($query);
				$ennrow = mysql_fetch_assoc($ennresult);

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

							$ennsaldorow = mysql_fetch_assoc($ennsaldoresult);
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
						$ennrow = mysql_fetch_assoc($ennresult);

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
						$ennrow = mysql_fetch_assoc($ennresult);

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

			$kirjoitin = mysql_fetch_assoc($kirre);
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
		if ($kurssi <= 0 or !is_numeric($kurssi)) {
			$kurssi = 1;
		}

		$sum = $summa*$kurssi;
		return $sum;
	}
}

// Tehdään valuuttamuunnos yhtiön valuutasta laskun valuuttaan.
if (!function_exists("laskuval")) {
	function laskuval($summa, $kurssi) {
		if ($kurssi <= 0 or !is_numeric($kurssi)) {
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

		$query = "SELECT round(if (epakurantti100pvm='0000-00-00', if (epakurantti75pvm='0000-00-00', if (epakurantti50pvm='0000-00-00', if (epakurantti25pvm='0000-00-00', kehahin, kehahin*0.75), kehahin*0.5), kehahin*0.25), 0),6) kehahin
				  FROM tuote
				  WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_assoc($result);
			return $row["kehahin"];
		}
		else {
			return 0;
		}
	}
}

if (!function_exists("desim")) {
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

if (!function_exists("desis")) {
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
									if (tilausrivi.perheid > 0, ifnull((select concat(rpad(upper(t2.hyllyalue), 5, '0'),lpad(upper(t2.hyllynro), 5, '0'),lpad(upper(t2.hyllyvali), 5, '0'),lpad(upper(t2.hyllytaso), 5, '0'), t2.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus), if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))),
								   		if (tilausrivi.perheid2 > 0, ifnull((select concat(rpad(upper(t3.hyllyalue), 5, '0'),lpad(upper(t3.hyllynro), 5, '0'),lpad(upper(t3.hyllyvali), 5, '0'),lpad(upper(t3.hyllytaso), 5, '0'), t3.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus), if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))),
											tilausrivi.tuoteno))) as sorttauskentta";
		}

		// varastopaikkajärjestys, tuoteperheet pidetään yhdessä
		elseif ($jarjestys == "1") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno),
									if (tilausrivi.perheid > 0, ifnull((select concat(rpad(upper(t2.hyllyalue), 5, '0'),lpad(upper(t2.hyllynro), 5, '0'),lpad(upper(t2.hyllyvali), 5, '0'),lpad(upper(t2.hyllytaso), 5, '0'), t2.tuoteno, rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno)),
								   		if (tilausrivi.perheid2 > 0, ifnull((select concat(rpad(upper(t3.hyllyalue), 5, '0'),lpad(upper(t3.hyllynro), 5, '0'),lpad(upper(t3.hyllyvali), 5, '0'),lpad(upper(t3.hyllytaso), 5, '0'), t3.tuoteno, rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno)),
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
									if (tilausrivi.perheid > 0, ifnull((select concat(t2.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus), if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)),
										if (tilausrivi.perheid2 > 0, ifnull((select concat(t3.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus), if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)),
											tilausrivi.tuoteno))) as sorttauskentta";
		}

		// tuotenumerojärjestys, tuoteperheet pidetään yhdessä
		elseif ($jarjestys == "5") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, tilausrivi.tuoteno,
									if (tilausrivi.perheid > 0, ifnull((select concat(t2.tuoteno, tilausrivi.tuoteno) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus), tilausrivi.tuoteno),
										if (tilausrivi.perheid2 > 0, ifnull((select concat(t3.tuoteno, tilausrivi.tuoteno) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus), tilausrivi.tuoteno),
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
										if (tilausrivi.perheid2 > 0, tilausrivi.perheid2,
											tilausrivi.tunnus))) as sorttauskentta";
		}

		// tilausjärjestys
		elseif ($jarjestys == "9") {
			$sorttauskentta = "tilausrivi.tunnus as sorttauskentta";
		}

		// toimittajan tuotenumerojärjestys, tuoteperheet pidetään yhdessä
		elseif ($jarjestys == "10") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, if (tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno),
									if (tilausrivi.perheid > 0, ifnull((select concat(if (tt2.toim_tuoteno!='', concat(tt2.toim_tuoteno,tilausrivi.perheid), concat(t2.tuoteno,tilausrivi.perheid)), if (tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)) from tilausrivi as t2 JOIN tuotteen_toimittajat tt2 ON t2.yhtio = tt2.yhtio and t2.tuoteno = tt2.tuoteno and tt2.liitostunnus = '$toimittaja' where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus), if (tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)),
										if (tilausrivi.perheid2 > 0, ifnull((select concat(if (tt3.toim_tuoteno!='', concat(tt3.toim_tuoteno,tilausrivi.perheid2), concat(t3.tuoteno,tilausrivi.perheid2)), if (tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)) from tilausrivi as t3 JOIN tuotteen_toimittajat tt3 ON t3.yhtio = tt3.yhtio and t3.tuoteno = tt3.tuoteno and tt3.liitostunnus = '$toimittaja' where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus), if (tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)),
											if (tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)))) as sorttauskentta";
		}
		elseif ($jarjestys == "M") {
			$sorttauskentta = " if ((tilausrivi.perheid=0 or tilausrivi.perheid=tilausrivi.tunnus),
									ifnull((SELECT tlt2.jarjestys FROM tilausrivin_lisatiedot tlt2 WHERE tlt2.yhtio=tilausrivi.yhtio and tlt2.tilausrivitunnus=tilausrivi.tunnus LIMIT 1), tilausrivi.tunnus),
									if (tilausrivi.perheid >0 ,
										ifnull((SELECT tlt2.jarjestys FROM tilausrivin_lisatiedot tlt2 WHERE tlt2.yhtio=tilausrivi.yhtio and tlt2.tilausrivitunnus=tilausrivi.perheid LIMIT 1), tilausrivi.perheid),
										tilausrivi.tunnus
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

				if ($kohde == $yhtio) {
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
		if (strpos($yhtiorow["synkronoi"], $table) === false) {
			return false;
		}

		$muutokset = array();

		//	Näillä tarttetaan vähän lisätietoo
		if ($table == "avainsana") {
			$abulisa = ereg(",(avainsana\|*([\|a-zA-Z_\-]*)),*", $yhtiorow["synkronoi"], $regs);

			$lajit = explode("|",strtolower($regs[2]));

			//	saadaanko juuri tätä lajia synkronoida?
			if (is_array($orig) and count($orig) > 0) {
				if (!in_array(strtolower($orig["laji"]), $lajit)) {
					return false;
				}
			}
		}

		$synclog = "\nAktivoidaan synkronointi konserniyrityksiin\n";

		if (!is_numeric($tunnus)) {
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

		if (mysql_num_rows($masterres) == 1) {
			$masterrow = mysql_fetch_assoc($masterres) or pupe_error($query);

			$synclog .= "Masteri on tunnus ($tunnus)\n";

			//	Tarkastetaan että originaali on varmasti samasta taulusta!
			if (is_array($orig) and count($orig) > 0) {

				$diff = array_diff_key($orig, $masterrow);

				if (count($diff) == 0) {

					//	Tarkastetaan oliko muutoksia?
					$diff = array_diff_assoc($orig, $masterrow);

					if (count($diff) > 0) {

						foreach($diff as $key => $value) {
							if (is_string($key) and !in_array($key, array("tunnus","muuttaja","muutospvm","laatija","luontiaika"))) {
								$muutokset[$key] .= $value;
							}
						}
					}

					//	Jos mitään ei muuteta mitään ei kanssa tehdä!
					if (count($muutokset) > 0 or $force == "F") {
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
					$synclog .= "\nVIRHE: Originaali ei täsmää masteriin!!!\n";

					synclog($yhtio, $table, $synclog, $tunnus);
					return false;
				}
			}
		}
		elseif (!is_array($orig)) {
			$synclog .= "Master tietuetta tai originaalia ei löytynyt yhtiöstä tunnuksella ($tunnus)\n";

			synclog($yhtio, $table, $synclog, $tunnus);
			return false;
		}
		else {
			$synclog .= "Master tietue ($tunnus) on poistettu, koitetaan poistaa tietue myös konserniyrityksiltä..\n\n";
			unset($masterrow);
		}

		require_once("inc/pakolliset_sarakkeet.inc");

		list($pakolliset, $kielletyt, $wherelliset, , ) = pakolliset_sarakkeet($table);

		if (count($wherelliset) == 0 and count($pakolliset) == 0) {
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
				if (is_array($orig) and count($orig) > 0) {
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
					and yhtion_parametrit.synkronoi like '%$table%'
					and yhtio.yhtio					!= '$yhtio'";
		$kohderes = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($kohderes) > 0) {
			while($kohderow = mysql_fetch_assoc($kohderes)) {

				$vanhatunnus = $utunnus="";
				$override	 = array();

				//	Jos master on poistettu haetaan tiedot siitä vanhasta!
				if (!isset($masterrow)) {
					$abuhaku = $orig;
				}
				else {
					$abuhaku = $masterrow;
				}

				//	Osa tauluista vaatii vähän käpistelyä!
				if ($table == "yhteyshenkilo") {
					if ($abuhaku["tyyppi"] == "A") {
						$query = "select * from asiakas where yhtio='$yhtio' and tunnus='$abuhaku[liitostunnus]'";
						$abures = mysql_query($query) or pupe_error($query);
						$aburow = mysql_fetch_assoc($abures);

						//	Etsitään oikea asiakas, jotta saadaan oikea liitostunnus
						$tarkquery = "select tunnus from asiakas where yhtio='$kohderow[yhtio]' and ytunnus='$aburow[ytunnus]' and ovttunnus='$aburow[ovttunnus]' and toim_ovttunnus='$aburow[toim_ovttunnus]'";
					}
					else {
						$query = "select * from toimi where yhtio='$yhtio' and tunnus='$abuhaku[liitostunnus]'";
						$abures = mysql_query($query) or pupe_error($query);
						$aburow = mysql_fetch_assoc($abures);

						//	Etsitään oikea toimittaja, jotta saadaan oikea liitostunnus
						$tarkquery = "select tunnus from toimi where yhtio='$kohderow[yhtio]' and ytunnus='$aburow[ytunnus]' and ovttunnus='$aburow[ovttunnus]'";
					}
					$tarkres = mysql_query($tarkquery) or pupe_error($tarkquery);

					if (mysql_num_rows($tarkres)==1) {
						//	Ylikirjoitetaan liitostunnus
						$tarkrow = mysql_fetch_assoc($tarkres);
						$override["liitostunnus"] = $tarkrow["tunnus"];

						//	Meidän pitääkorvata se vanha liitostunnus myös where haarasta..
						$where = str_replace(" and liitostunnus='$abuhaku[liitostunnus]'", " and liitostunnus='$override[liitostunnus]'", $where);
					}
					else {
						$synclog .= "Yhtiölle '$kohderow[yhtio]' ei löydy asiakasta/toimittajaa $masterrow[ytunnus] joten yhteyshenkilöä ei voida synkronoida!\n";
						$ok=0;
					}
				}
				elseif ($table == "tuotteen_toimittajat") {

					$query = "select * from tuote where yhtio='$yhtio' and tuoteno='$abuhaku[tuoteno]'";
					$abures = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($abures) == 1) {

						$query = "select * from toimi where yhtio='$yhtio' and tunnus='$abuhaku[liitostunnus]'";
						$abures = mysql_query($query) or pupe_error($query);
						$aburow = mysql_fetch_assoc($abures);

						//	Etsitään oikea toimittaja jotta saadaan oikea liitostunnus
						$tarkquery = "select tunnus from toimi where yhtio='$kohderow[yhtio]' and ytunnus='$aburow[ytunnus]' and ovttunnus='$aburow[ovttunnus]'";
						$tarkres = mysql_query($tarkquery) or pupe_error($tarkquery);

						if (mysql_num_rows($tarkres)==1) {
							//	Ylikirjoitetaan liitostunnus
							$tarkrow = mysql_fetch_assoc($tarkres);
							$override["liitostunnus"] = $tarkrow["tunnus"];

							//	Meidän pitääkorvata se vanha liitostunnus myös where haarasta..
							$where = str_replace(" and liitostunnus='$abuhaku[liitostunnus]'", " and liitostunnus='$override[liitostunnus]'", $where);

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


				//	Poistetaan tietue jos masteria ei löydetty mutta originaali on tallessa.. olisiko tässä selvempi tapa?
				if (!isset($masterrow) and is_array($orig) and count($orig) > 0) {

					//	Tarkastetaan ettei tällä kriteerillä löydy useita poistettavia..
					$query = "	SELECT tunnus
								FROM $table
								WHERE yhtio = '$kohderow[yhtio]'
								$where";
					$abures = mysql_query($query);

					if (mysql_num_rows($abures) == 1) {
						$aburow = mysql_fetch_assoc($abures);
						$vanhatunnus = $aburow["tunnus"];

						$query = "	DELETE
									FROM $table
									WHERE yhtio = '$kohderow[yhtio]'
									and tunnus = $vanhatunnus";
						$delres = mysql_query($query);

						$synclog .= "Yhtiöltä '$kohderow[yhtio]' poistettiin tietue ($vanhatunnus)\n";
						synclog($kohderow["yhtio"], $table, "Poistettiin $table ($vanhatunnus)", $vanhatunnus);
					}
					elseif (mysql_num_rows($abures)>0) {
						$synclog .= "Yhtiöllä '$kohderow[yhtio]' oli liian monta tietuetta haussa, tietuetta ei voitu poistaa.\n";
						synclog($kohderow["yhtio"], $table, "Koitettiin poistaa $table, mutta haulla löytyi enemmän kuin yski tietue, mitään ei poistettu!", $utunnus);
					}
					else {
						$synclog .= "Yhtiöllä '$kohderow[yhtio]' ei ollut poistettaavaa tietuetta.\n";
					}
				}
				else {

					if ($table == "toimi") {

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
							if (mysql_num_rows($tarkres) != 1) {
								$override[$value] = "";
							}
						}

						//	Onko tilino myös kohdeyrityksessä?
						$query = "select tunnus from tili where yhtio='$kohderow[yhtio]' and tilino='$masterrow[tilino]'";
						$tarkres = mysql_query($query) or pupe_error($query);

						//	tiliä ei ole, joten tätä tietuetta ei voida synkronoida..
						if (mysql_num_rows($tarkres)!=1) {
							$override["tilino"] = "";
						}

						// Onko kustannuspaikat olemassa?
						foreach(array("kustp","kohde","projekti") as $value) {
							$query = "select nimi, tyyppi from kustannuspaikka where yhtio='$yhtio' and tunnus='".$masterrow[$value]."'";
							$abures=mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($abures)==1) {
								$aburow = mysql_fetch_assoc($abures);
								$query = "select tunnus from kustannuspaikka where yhtio='$kohderow[yhtio]' and nimi='$aburow[nimi]' and tyyppi='$aburow[tyyppi]'";
								$tarkres = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($tarkres)!=1) {
									$override[$value]="";
								}
							}
						}
						*/
					}

					if ($table == "tuote") {
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
							if (mysql_num_rows($tarkres)!=1) {
								$override[$value]="";
							}
						}

						//	Onko tilinot myös kohdeyrityksessä?
						foreach(array("tilino","tilino_eu","tilino_ei_eu") as $value) {
							$query = "select tunnus from tili where yhtio='$kohderow[yhtio]' and tilino='".$masterrow[$value]."'";
							$tarkres=mysql_query($query) or pupe_error($query);
							if (mysql_num_rows($tarkres)!=1) {
								$override[$value]="";
							}
						}

						// Onko kustannuspaikat olemassa?
						foreach(array("kustp","kohde","projekti") as $value) {
							$query = "select nimi, tyyppi from kustannuspaikka where yhtio='$yhtio' and tunnus='".$masterrow[$value]."'";
							$abures=mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($abures)==1) {
								$aburow = mysql_fetch_assoc($abures);
								$query = "select tunnus from kustannuspaikka where yhtio='$kohderow[yhtio]' and nimi='$aburow[nimi]' and tyyppi='$aburow[tyyppi]'";
								$tarkres = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($tarkres)!=1) {
									$override[$value]="";
								}
							}
						}
						*/

						// Tarkastetaan että hinnat eivät mene aivan päin vittuja..
						$query = "select tunnus from yhtion_parametrit where yhtio='$kohderow[yhtio]' and alv_kasittely!='$yhtion_parametrit[alv_kasittely]'";
						$abures = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($abures)!=0 and $masterrow["alv"] > 0) {

							// yhtiolla on verolliset hinnat kohteella verottomat
							if ($yhtiorow["alv_kasittely"] == "") {
								$override["myyntihinta"] = round(($masterrow["myyntihinta"]*(1+$masterrow["alv"]/100)),2);
								$override["nettohinta"]  = round(($masterrow["nettohinta"]*(1+$masterrow["alv"]/100)),2);
							}
							else {
								$override["myyntihinta"] = round(($masterrow["myyntihinta"]/(1+$masterrow["alv"]/100)),2);
								$override["nettohinta"]  = round(($masterrow["nettohinta"]/(1+$masterrow["alv"]/100)),2);
							}
						}
					}

					if ($table == "asiakas") {
						foreach(array("myyjanro","tilino","kustp","kohde","projekti") as $value) {
							$override[$value] = "";
						}

						//	koitetaan hakea oikean maksuehdon tunnus..
						$query = "SELECT * from maksuehto where yhtio='$yhtio' and tunnus='$masterrow[maksuehto]'";
						$abures = mysql_query($query) or pupe_error($query);
						$aburow = mysql_fetch_assoc($abures);

						//	Melkein kaikki tiedot pitää stemmata!
						$query = "	SELECT tunnus
									from maksuehto
									where yhtio='$kohderow[yhtio]'
									and abs_pvm				= '$aburow[abs_pvm]'
									and erapvmkasin			= '$aburow[erapvmkasin]'
									and factoring			= '$aburow[factoring]'
									and jaksotettu			= '$aburow[jaksotettu]'
									and jv					= '$aburow[jv]'
									and kassa_abspvm		= '$aburow[kassa_abspvm]'
									and kassa_alepros		= '$aburow[kassa_alepros]'
									and kassa_relpvm		= '$aburow[kassa_relpvm]'
									and kateinen			= '$aburow[kateinen]'
									and osamaksuehto1		= '$aburow[osamaksuehto1]'
									and osamaksuehto2		= '$aburow[osamaksuehto2]'
									and rel_pvm				= '$aburow[rel_pvm]'
									and sallitut_maat		= '$aburow[sallitut_maat]'
									and summanjakoprososa2	= '$aburow[summanjakoprososa2]'
									and suoraveloitus		= '$aburow[suoraveloitus]'
									LIMIT 1";
						$tarkres = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($tarkres) == 1) {
							$tarkrow = mysql_fetch_assoc($tarkres);
							$override["maksuehto"] = $tarkrow["tunnus"];
						}
						else {
							$override["maksuehto"] = "";
						}

						//	koitetaan hakea oikean toimitustavan tunnus..
						$query = "SELECT * from toimitustapa where yhtio='$yhtio' and tunnus='$masterrow[toimitustapa]'";
						$abures = mysql_query($query) or pupe_error($query);
						$aburow = mysql_fetch_assoc($abures);

						//	Melkein kaikki tiedot pitää stemmata!
						$query = "	SELECT tunnus from toimitustapa where yhtio='$kohderow[yhtio]'
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
						if (mysql_num_rows($tarkres) == 1) {
							$tarkrow=mysql_fetch_assoc($tarkres);
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

					if (mysql_num_rows($abures)==0) {
						$query 	= "INSERT into $table SET yhtio='$kohderow[yhtio]', laatija='$yhtio', luontiaika=now() ";
						$query2	= "";
					}
					elseif (mysql_num_rows($abures)==1) {
						$aburow = mysql_fetch_assoc($abures);
						$vanhatunnus = $aburow["tunnus"];

						$query = "UPDATE $table SET yhtio='$kohderow[yhtio]'";
						$query2	=" WHERE yhtio='$kohderow[yhtio]' and tunnus=$vanhatunnus";
					}
					else {
						$ok=0;
					}

					if ($ok == 1) {
						//	Duunataan itse päivitys/insert kysely!!!
						for ($i=1; $i < mysql_num_fields($masterres); $i++) {
							if (isset($masterrow[mysql_field_name($masterres, $i)]) and !in_array(mysql_field_name($masterres, $i), array("yhtio","tunnus","muuttaja","muutospvm","laatija","luontiaika"))) {
								if (isset($override[mysql_field_name($masterres, $i)])) {
									if ($override[mysql_field_name($masterres, $i)] != "") {
										$query .= ", ". mysql_field_name($masterres,$i)."='".$override[mysql_field_name($masterres, $i)]."' ";
									}
								}
								else {
									$query .= ", ". mysql_field_name($masterres,$i)."='".$masterrow[mysql_field_name($masterres, $i)]."' ";
								}
							}
						}

						$query = $query." ".$query2;
						$updres = mysql_query($query) or pupe_error($query);

						if (mysql_affected_rows() > 0) {

							$erot = "";

							if (count($override) > 0) {
								$erot = "\n\nPoikkeukset synkronoinnissa:\n";

								foreach($override as $key => $value) {
									$erot .= "$key: ".$masterrow[$key]." => ".$value."\n";
								}
							}

							if (mysql_num_rows($abures) == 0) {
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
			$laskurow = mysql_fetch_assoc($result);

			if ($laskurow["tila"] == "U") {
				// Tavara on ostettu sisään myyntilaskulla
				$query = "	SELECT round(tilausrivi.rivihinta/tilausrivi.kpl, 2) ohinta
							FROM tilausrivi
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]'
							and tilausrivi.tunnus = $rivitunnus";
				$presult = mysql_query($query) or pupe_error($query);
				$tilrivirow = mysql_fetch_assoc($presult);

				return $tilrivirow["ohinta"];
			}
			elseif ($laskurow["tila"] == "K" and $laskurow["alatila"] == "I") {
				// Tavara on ostettu sisään myyntilaskulla
				$query = "	SELECT round(tilausrivi.rivihinta/tilausrivi.kpl, 2) ohinta
							FROM tilausrivi
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]'
							and tilausrivi.tunnus = $rivitunnus";
				$presult = mysql_query($query) or pupe_error($query);
				$tilrivirow = mysql_fetch_assoc($presult);

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

				while ($tilrivirow = mysql_fetch_assoc($presult)) {

					$query  = "	SELECT *
								from tuote
								LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]' and tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno)
								where tuote.yhtio	= '$kukarow[yhtio]'
								and tuote.tuoteno	= '$tilrivirow[tuoteno]'";
					$tuores = mysql_query($query) or pupe_error($query);
					$tuorow = mysql_fetch_assoc($tuores);

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
	function jalkilaskentafunktio ($tuoteno, $pvm, $uusihinta, $rivitunnus, $tapahtumatunnus=0, $kaantopisteen_saldomuutos=0) {
		global $yhtiorow, $kukarow, $jalkilaskenta_debug_text, $korjattavat_valmistukset, $korjattavat_valmistukset_ind;

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

if (!function_exists("jalkilaske_valmistus")) {
	function jalkilaske_valmistus ($valmistettava_tilaus) {
		global $kukarow, $yhtiorow, $korjattavat_valmistukset, $korjattavat_valmistukset_ind;

		$toim 			 = "KORJAA";
		$from_kaikkikorj = "KORJAAKAIKKI";
		$tee 			 = "VALMISTA";

		$query = "	SELECT lasku.tunnus,
					sum(if(tilausrivi.tyyppi in ('V','W'), 1, 0)) valmistusriveja,
					GROUP_CONCAT(DISTINCT tilausrivi.tunnus SEPARATOR ',') valmistettavat
					from tilausrivi, lasku
					where tilausrivi.yhtio = '$kukarow[yhtio]'
					and lasku.yhtio = tilausrivi.yhtio
					and lasku.tunnus = tilausrivi.otunnus
					and lasku.tila 	in ('V', 'L')
					and lasku.alatila  in ('V', 'K', 'X')
					and (tilausrivi.toimitettu != '' or tilausrivi.tyyppi='D') and lasku.tilaustyyppi in ('V','W','N')
					and lasku.tunnus = '$valmistettava_tilaus'
					GROUP BY lasku.tunnus
					HAVING valmistusriveja > 0";
		$tilre = mysql_query($query) or pupe_error($query);
		$tilrow = mysql_fetch_assoc($tilre);

		$valmistettavat = $tilrow["valmistettavat"];

		if ($tee == "VALMISTA" and $valmistettavat != "") {
			//Haetaan otsikoiden tiedot
			$query = "	SELECT
						GROUP_CONCAT(DISTINCT lasku.tunnus SEPARATOR ', ') 'Tilaus',
						GROUP_CONCAT(DISTINCT lasku.nimi SEPARATOR ', ') 'Asiakas/Nimi',
						GROUP_CONCAT(DISTINCT lasku.ytunnus SEPARATOR ', ') 'Ytunnus',
						GROUP_CONCAT(DISTINCT lasku.tilaustyyppi SEPARATOR ', ') 'Tilaustyyppi'
						FROM tilausrivi, lasku
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and	tilausrivi.tunnus in ($valmistettavat)
						and lasku.tunnus=tilausrivi.otunnus
						and lasku.yhtio=tilausrivi.yhtio";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Yhtään tilausta ei löytynyt")."</font><br>";
				$tee = "";
			}
		}
		else {
			echo "<tr>";
			echo "<th>$valmistettava_tilaus</th>";
			echo "<td colspan='5'><font class='error'>".t("Yhtään valmistusriviä ei löytynyt")."!</font></td>";
			echo "</tr>";
		}

		if ($tee == "VALMISTA" and $valmistettavat != "") {
			$row = mysql_fetch_assoc($result);

			//Päivitetään lasku niin, että se on tilassa korjataan
			$query = "	UPDATE lasku
						SET alatila	= 'K'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  in ($row[Tilaus])
						and tila	in ('V','L')
						and alatila in ('V','X')";
			$chkresult4 = mysql_query($query) or pupe_error($query);

			$korjataan = " and (tilausrivi.toimitettu != '' or tilausrivi.tyyppi='D') ";

			//Haetaan valmistettavat valmisteet ja käytettävät raaka-aineet
			$query = "	SELECT tilausrivi.nimitys,
						tilausrivi.tuoteno,
						tilkpl tilattu,
						if (tyyppi!='L', varattu, 0) valmistetaan,
						if (tyyppi='L' or tyyppi='D', varattu, 0) valmistettu,
						if (toimitettu!='', if (varattu!=0, varattu, kpl), 0) korjataan,
						if (toimitettu!='', kpl, 0) valmistettu_valmiiksi,
						if (tyyppi!='L', kpl, 0) kaytetty,
						toimaika,
						kerayspvm,
						tilausrivi.tunnus tunnus,
						tilausrivi.perheid,
						tilausrivi.tyyppi,
						tilausrivi.toimitettuaika,
						tilausrivi.otunnus otunnus,
						tilausrivi.uusiotunnus laskutettu,
						tilausrivi.kommentti,
						tuote.ei_saldoa,
						tilausrivi.kommentti,
						tuote.sarjanumeroseuranta,
						tuote.yksikko,
						tilausrivi.varattu,
						tilausrivi.var,
						tilausrivi.hyllyalue,
						tilausrivi.hyllyvali,
						tilausrivi.hyllytaso,
						tilausrivi.hyllynro
						FROM tilausrivi, tuote
						WHERE
						tilausrivi.otunnus in ($row[Tilaus])
						and tilausrivi.tunnus in ($valmistettavat)
						and tilausrivi.yhtio = '$kukarow[yhtio]'
						and tuote.yhtio = tilausrivi.yhtio
						and tuote.tuoteno = tilausrivi.tuoteno
						and tyyppi in ('V','W','M','L','D')
						$korjataan
						ORDER BY perheid desc, tyyppi in ('W','M','L','D','V'), tunnus";
			$presult = mysql_query($query) or pupe_error($query);
			$riveja = mysql_num_rows($presult);

			$rivkpl = mysql_num_rows($presult);
			$voikokorjata = 0;

			$tee 			= 'TEEVALMISTUS';
			$valmisteet_chk = array();
			$tuotenumerot 	= array();
			$edtilkpllat 	= array();
			$tilkpllat 		= array();
			$valmkpllat 	= array();
			$rekru 			= array();
			$virhe 			= array();
			$perutamakorj 	= array();
			$era_new_paikka = "";
			unset($osatoimitus);

			while ($prow = mysql_fetch_assoc ($presult)) {

				if ($prow["tyyppi"] == 'W' or $prow["tyyppi"] == 'M') {
					// Nämä ovat valmisteita
					$valmisteet_chk[$prow["tunnus"]] = $prow["tuoteno"];
				}
				elseif ($prow["tyyppi"] == 'D') {
					// Nämä ovat jo valmistettu
				}
				else {
					// Tässä tulevat kaikki raaka-aineet

					// tehdään salditsekki vain saldollisille raaka-aineille
					if ($prow["ei_saldoa"] == "") {
						$tuotenumerot[$prow["tunnus"]] = $prow["tuoteno"];
					}
				}

				$tuotenumerot[$prow["tunnus"]] = $prow["tuoteno"];

				if ($toim == "KORJAA" and  $prow["tyyppi"] == 'V') {
					$edtilkpllat[$prow["tunnus"]] = $prow["korjataan"];
					$tilkpllat[$prow["tunnus"]] = $prow["korjataan"];
				}
				elseif ($toim == "KORJAA" and  $prow["tyyppi"] == 'W') {
					$edtilkpllat[$prow["tunnus"]] = $prow["valmistetaan"];
					$tilkpllat[$prow["tunnus"]] = $prow["valmistetaan"];
				}
				elseif ($prow["toimitettuaika"] == "0000-00-00 00:00:00") {
					$edtilkpllat[$prow["tunnus"]] = $prow["valmistetaan"];
					$tilkpllat[$prow["tunnus"]] = $prow["valmistetaan"];
				}

				if ($prow["tunnus"] == $prow["perheid"] and ($prow["tyyppi"] == "W" or $prow["tyyppi"] == "M") and $prow["toimitettuaika"] != "0000-00-00 00:00:00" and $toim == "KORJAA") {
					//tutkitaan kuinka paljon tätä nyt oli valmistettu
					$query = "	SELECT sum(kpl) valmistetut
								FROM tilausrivi
								WHERE yhtio	= '$kukarow[yhtio]'
								and otunnus = '$prow[otunnus]'
								and perheid = '$prow[perheid]'
								and tyyppi	= 'D'
								and toimitettuaika = '0000-00-00 00:00:00'";
					$sumres = mysql_query($query) or pupe_error($query);
					$sumrow = mysql_fetch_assoc($sumres);

					$query = "	SELECT count(*) laskuja
								FROM lasku
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus 	= '$prow[laskutettu]'
								and tila 	= 'U'
								and alatila	= 'X'";
					$slres = mysql_query($query) or pupe_error($query);
					$slrow = mysql_fetch_assoc($slres);

					if ($sumrow["valmistetut"] != 0 and $slrow["laskuja"] == 0) {
						if ((float) $prow["valmistetaan"] > 0) {
							$valmkpllat[$prow["tunnus"]] = $prow["valmistetaan"];
						}
						else {
							$valmkpllat[$prow["tunnus"]] = $sumrow["valmistetut"];
						}

						$voikokorjata++;
					}
					elseif ($sumrow["valmistetut"] != 0 and $slrow["laskuja"] > 0) {
						if ((float) $prow["valmistetaan"] > 0) {
							$valmkpllat[$prow["tunnus"]] = $prow["valmistetaan"];
						}
						else {
							$valmkpllat[$prow["tunnus"]] = $sumrow["valmistetut"];
						}

						$voikokorjata++;
					}
				}

				#echo "<tr>";
				#echo "<th>$valmistettava_tilaus</th>";
				#echo "<td>".$valmisteet_chk[$prow["tunnus"]]."</td>";
				#echo "<td>".$tuotenumerot[$prow["tunnus"]]."</td>";
				#echo "<td>".$edtilkpllat[$prow["tunnus"]]."</td>";
				#echo "<td>".$tilkpllat[$prow["tunnus"]]."</td>";
				#echo "<td>".$valmkpllat[$prow["tunnus"]]."</td>";
				#echo "</tr>";
			}

			ob_start();
			require("tilauskasittely/valmista_tilaus.php");
			$retval = ob_get_contents();
			ob_end_clean();

			if (count($virhe) > 0) {
				echo "<tr>";
				echo "<th>$valmistettava_tilaus</th>";
				echo "<td colspan='5'><font class='error'>",var_dump($virhe),"</font></td>";
				echo "</tr>";
			}
			else {
				echo "<tr>";
				echo "<th>$valmistettava_tilaus</th>";
				echo "<td colspan='5'><font class='ok'>$retval</font></td>";
				echo "</tr>";
			}
		}
	}
}

if (!function_exists("tuotteen_myyntihinta")) {
	function tuotteen_myyntihinta ($laskurow, $trow, $kpl, $naytetaanko_netto = "") {
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
						and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), maa desc
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow       	= mysql_fetch_assoc ($hresult);
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
						and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow       	= mysql_fetch_assoc ($hresult);
				$hinta 			= $hrow["hinta"];
				$netto			= $hrow["laji"];
				$valuutta		= $hrow["valkoodi"];
			}
		}

		// 15. tuote.nettohinta (tuotteen nettohinta)
		if ($hinta == 0 and $trow['nettohinta'] > 0 and $naytetaanko_netto == "") {
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
						and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), maa desc
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow			= mysql_fetch_assoc ($hresult);
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
						and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow			= mysql_fetch_assoc ($hresult);
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
		$wtrow  = mysql_fetch_assoc($wtres);

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
	function alehinta ($laskurow, $trow, $kpl, $netto, $hinta, $ale, $palautus="", $yhtio="") {
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
		// $netto onko hinta nettohinta vai ei
		// $ale (aleprosentti)
		// $aperuste (selkokielinen teksti mihin päädyttiin)
		// $alehinta_alv jos on joku erikoialv tälle hinnaston tuotteelle
		// $alehinta_val hinnan valuutta
		// $hintaperuste hinnan peruste koodina
		// $aleperuste  alennuksen peruste koodina

		$aperuste 		= "";
		$alehinta_alv 	= 0;
		$hintaperuste	= false;
		$aleperuste		= false;
		$vanha_yhtio	= "";
		$vanha_liitostunnus = 0;

		if ($yhtio != '') {
			$vanha_yhtio = $kukarow['yhtio'];
			$yhtiorow = hae_yhtion_parametrit($yhtio);
			$kukarow['yhtio'] = $yhtio;

			$query = "	SELECT tunnus
						FROM asiakas
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND ytunnus = '{$laskurow['ytunnus']}'
						AND toim_ovttunnus = '{$laskurow['toim_ovttunnus']}'";
			$asiakas_tunnus_res = mysql_query($query) or pupe_error($query);
			$asiakas_tunnus_row = mysql_fetch_assoc($asiakas_tunnus_res);

			$vanha_liitostunnus = $laskurow['liitostunnus'];
			$laskurow['liitostunnus'] = $asiakas_tunnus_row['tunnus'];
		}

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
					$alehi_alrow = mysql_fetch_assoc($alhire);
					$alehinta_alv = $alehi_alrow["alv"];
				}
			}
		}

		// haetaan asiakkaan tiedot
		$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
		$alhire = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($alhire) == 1) {
			$alehi_asrow = mysql_fetch_assoc($alhire);
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

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakashinta USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and tuoteno  = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							UNION

							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakashinta USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							ORDER BY prio, minkpl desc, aika, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc ($hresult);


					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						$ale 	= 0;
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						$ale	= '';
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

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
							FROM asiakashinta USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							UNION

							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
							FROM asiakashinta USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							ORDER BY prio, minkpl desc, aika, valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						$ale 	= 0;
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						$ale	= '';
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

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakashinta USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							UNION

							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakashinta USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							ORDER BY prio, minkpl desc, aika, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						$ale 	= 0;
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						$ale	= '';
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

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
							FROM asiakashinta USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							UNION

							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
							FROM asiakashinta USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))

							ORDER BY prio, minkpl desc, aika, valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						$ale 	= 0;
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						$ale	= '';
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
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						$ale 	= 0;
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						$ale	= '';
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
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						$ale 	= 0;
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						$ale	= '';
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
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						$ale 	= 0;
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						$ale	= '';
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
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc ($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						$ale 	= 0;
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						$ale	= '';
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

				$query = "	(SELECT '1' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakasalennus USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and asiakas_ryhma = ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and (alennus < 0 or left(alennus,1) = '-'))

							UNION

							(SELECT '2' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakasalennus USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and asiakas_ryhma = ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and (alennus < 0 or left(alennus,1) = '-'))

							ORDER BY prio, minkpl desc, aika, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {

					$hrow = mysql_fetch_assoc($hresult);

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
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and (alennus < 0 or left(alennus,1) = '-')
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {

					$hrow = mysql_fetch_assoc($hresult);

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
				$query = "	(SELECT '1' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakasalennus USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus = 999.99)

							UNION

							(SELECT '2' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakasalennus USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus = 999.99)

							ORDER BY prio, minkpl desc, aika, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {

					$hrow = mysql_fetch_assoc($hresult);

					//Jos aleprossa  = 999.99 haetaan tuotteen myymälähinta myyntihinnan tilalle
					$query = "SELECT myymalahinta, alv FROM tuote WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$trow[tuoteno]'";
					$myymresult = mysql_query($query) or pupe_error($query);
					$myymrow = mysql_fetch_assoc ($myymresult);

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
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), maa desc, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow       	= mysql_fetch_assoc ($hresult);
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
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow       	= mysql_fetch_assoc ($hresult);
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
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), maa desc, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow			= mysql_fetch_assoc ($hresult);
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
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow			= mysql_fetch_assoc ($hresult);
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
		elseif (substr($ale,-1) == "-") {
			$aleperuste = 2;

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

			unset($ale);

			// 5. asiakas.tunnus/asiakas.ytunnus tuote.tuotenumero aleprosentti (asiakkaan tuotteen alennus)
			$query = "	(SELECT '1' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
						FROM asiakasalennus USE INDEX (yhtio_asiakas_tuoteno)
						WHERE yhtio = '$kukarow[yhtio]'
						and asiakas = '$laskurow[liitostunnus]'
						and asiakas > 0
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and (minkpl <= '$kpl' or minkpl = 0)
						and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and alennus >= 0
						and alennus <= 100)

						UNION

						(SELECT '2' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
						FROM asiakasalennus USE INDEX (yhtio_ytunnus_tuoteno)
						WHERE yhtio = '$kukarow[yhtio]'
						and ytunnus = '$laskurow[ytunnus]'
						and ytunnus != ''
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and (minkpl <= '$kpl' or minkpl = 0)
						and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and alennus >= 0
						and alennus <= 100)

						ORDER BY prio, minkpl desc, aika, tunnus desc
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$aleperuste = 5;

				$hrow = mysql_fetch_assoc ($hresult);
				$ale  = $hrow["alennus"];
				$aperuste .= " Asiakkaan tuotteen alennus";
			}

			// 6. asiakas.tunnus/asiakas.ytunnus tuote.aleryhmä aleprosentti (asiakkaan tuotealeryhmän alennus)
			if (!isset($ale)) {

				$query = "	(SELECT '1' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakasalennus USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100)

							UNION

							(SELECT '2' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakasalennus USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100)

							ORDER BY prio, minkpl desc, aika, tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$aleperuste = 6;

					$hrow = mysql_fetch_assoc ($hresult);
					$ale  = $hrow["alennus"];
					$aperuste .= " Asiakkaan tuotealeryhmän alennus";
				}
			}

			// 7. asiakas.ryhmä tuote.tuoteno aleprosentti (asiakasaleryhmän tuotteen alennus)
			if (!isset($ale)) {

				$query = "	SELECT alennus
							FROM asiakasalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_ryhma = '$alehi_asrow[ryhma]'
							and asiakas_ryhma != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and ytunnus = ''
							and asiakas = 0
							and (minkpl <= '$kpl' or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$aleperuste = 7;

					$hrow = mysql_fetch_assoc ($hresult);
					$ale  = $hrow["alennus"];
					$aperuste .= " Asiakasaleryhmän tuotteen alennus";
				}
			}

			// 8. asiakas.ryhmä tuote.aleryhmä aleprosentti (asiakasaleryhmän tuotealeryhmän alennus)
			if (!isset($ale)) {

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
								and (minkpl <= '$kpl' or minkpl = 0)
								and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
								and alennus >= 0
								and alennus <= 100
								ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc
								LIMIT 1";
					$hresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($hresult) > 0) {
						$aleperuste = 8;

						$hrow = mysql_fetch_assoc ($hresult);
						$ale  = $hrow["alennus"];
						$aperuste .= " Asiakasaleryhmän tuotealeryhmän alennus";
						break;
					}
				}
			}

			// 9. tuote.aleryhmä aleprosentti (tuotealeryhmän perusalennus)
			if (!isset($ale)) {

				$query = "	SELECT alennus
							FROM perusalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and alennus >= 0
							and alennus <= 100";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$aleperuste = 9;

					$hrow = mysql_fetch_assoc ($hresult);
					$ale  = $hrow["alennus"];
					$aperuste .= " Tuotealeryhmän perusalennus";
				}
			}
		}

		if (!isset($ale)) {
			$ale = 0;
		}
		if ($ale > 100) {
			$ale = 100;
		}
		if ($ale < 0) {
			$ale = 0;
		}

		//$debug = 1;
		if ($debug == 1) echo t("Tulin tulokseen").": $aperuste.  ALE: $ale ".t("% HINTA")." $hinta $yhtiorow[valkoodi] KPL:$kpl<br><br>";

		if ($yhtio != '') {
			$yhtiorow = hae_yhtion_parametrit($vanha_yhtio);
			$kukarow['yhtio'] = $vanha_yhtio;
			$laskurow['liitostunnus'] = $vanha_liitostunnus;
		}

		if ($palautus != "") {
			$ret=array();

			$a = explode(",", $palautus);
			if (is_array($a)) {
				foreach($a as $palauta) {
					if (isset(${$palauta})) {
						$ret[$palauta] = ${$palauta};
					}
					else {
						echo "<font class='error'>Muuttujaa '$palauta' ei voida palauttaa!</font><br>";
						$ret[$palauta] = false;
					}
				}
			}
			else {
				if (isset(${$palautus})) {
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
		$asrow = mysql_fetch_assoc($result);

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

		$query = "SELECT * from lasku where tunnus=" . (int) $lasku_tunnus . " and yhtio='$kukarow[yhtio]'";
		$res = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_assoc($res);

		if ($laskurow['ebid'] != "" and $laskurow['ebid'] != "FINVOICEXML") {
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
					return array($url);
				}

				return "<a href='$url' target='Attachment'>". t('Näytä lasku')."</a>";
			}

			$ebid = $laskurow['ebid'];

			$verkkolaskutunnus = $yhtiorow['verkkotunnus_vas'];
			$salasana		   = $yhtiorow['verkkosala_vas'];

			$timestamppi = gmdate("YmdHis")."Z";

			$urlhead = "http://www.verkkolasku.net";
			$urlmain = "/view/ebs-2.0/$verkkolaskutunnus/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$ebid&TIMESTAMP=$timestamppi&VERSION=ebs-2.0";

			$digest	 = md5($urlmain . "&" . $salasana);
			$url	 = $urlhead.$urlmain."&DIGEST=$digest";

			if ($url_only) {
				return array($url);
			}

			$out = "<a href='$url' target='Attachment'>". t('Näytä lasku')."</a> ";

			$query = "SELECT tunnus from liitetiedostot where liitostunnus='$laskurow[tunnus]' and liitos='lasku' and yhtio='$kukarow[yhtio]'";
			$res = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_assoc($res)) {
				$out .= " <a $target href='".$palvelin2."view.php?id=$row[tunnus]' target='Attachment'>". t('Näytä liite') ."</a> ";
			}

			return $out;
		}
		elseif ($laskurow['tila'] == 'U') {

			$out = "";

			if ($laskurow['chn'] == '100') $out = t("Paperilasku");
			elseif ($laskurow['chn'] == '010') $out = t("eInvoice")."-".t("lasku");
			elseif ($laskurow['chn'] == '020') $out = t("Vienti eInvoice")."-".t("lasku");
			elseif ($laskurow['chn'] == '111') $out = t("Elma EDI-inhouse")."-".t("lasku");
			elseif ($laskurow['chn'] == '666') $out = t("Sähköposti")."-".t("lasku");
			elseif ($laskurow['chn'] == '667') $out = t("Sisäinen")."-".t("lasku");

			$query = "SELECT tunnus from liitetiedostot where liitostunnus='$laskurow[tunnus]' and liitos='lasku' and yhtio='$kukarow[yhtio]'";
			$res = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_assoc($res)) {
				$out .= " <a $target href='".$palvelin2."view.php?id=$row[tunnus]' target='Attachment'>". t('Näytä liite') ."</a> ";
			}

			return $out;
	    }
		else {

			$query = "SELECT tunnus from liitetiedostot where liitostunnus='$laskurow[tunnus]' and liitos='lasku' and yhtio='$kukarow[yhtio]'";
			$res = mysql_query($query) or pupe_error($query);

			if ($url_only) {
				$out = array();
			}
			else {
				$out = '';
			}

			while ($row = mysql_fetch_assoc($res)) {
				if ($url_only) {
					$out[] = $palvelin2."view.php?id=$row[tunnus]";
				}
				else {
					$out .= "<a $target href='".$palvelin2."view.php?id=$row[tunnus]' target='Attachment'>". t('Näytä lasku') ."</a> ";
				}

			}

			if ($out != '') {
				return $out;
			}

			return t('Paperilasku');
		}
	}
}

if (!function_exists("size_readable")) {
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

if (!function_exists("sarjanumeron_ostohinta")) {
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
		$ostorow = mysql_fetch_assoc($otsores);

		if ($ostorow["tunnukset"] != '') {
			$query = "	SELECT
						sum(tilausrivi.rivihinta/tilausrivi.kpl) ostosumma,
						sum(tilausrivi.hinta * (1-(tilausrivi.ale/100))) ostosumma_eiloppulaskettu
						FROM tilausrivi
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tunnus  IN ($ostorow[tunnukset])";
			$sarjares = mysql_query($query) or pupe_error($query);
			$sarjarow = mysql_fetch_assoc($sarjares);

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

				while($kulukeikkarow = mysql_fetch_assoc($keikkares)) {
					// Haetaan kaikki keikkaan liitettyjen laskujen summa
					$query = "	SELECT sum(arvo*if (maksu_kurssi!=0, maksu_kurssi, vienti_kurssi)) kulusumma
								FROM lasku
								WHERE yhtio		= '$kukarow[yhtio]'
								and tila 		IN ('K','X')
								and laskunro 	= '$kulukeikkarow[laskunro]'
								and vanhatunnus <> 0
								and vienti in ('B','E','H')";
					$result = mysql_query($query) or pupe_error($query);
					$kulukulurow = mysql_fetch_assoc($result);

					$ostohinta	+= $kulukulurow["kulusumma"];
				}
			}
		}

	    return round($ostohinta, 6);
	}
}

if (!function_exists("liitettyjen_kululaskujen_summa")) {
	function liitettyjen_kululaskujen_summa($otunnus) {
		global $kukarow, $yhtiorow;

		// Katsotaan onko tilaukselle liitetty kulukeikka ja palatetaan laskujen summat yhtiön valuutassa
		$query  = "	SELECT laskunro
					FROM lasku
					WHERE yhtio 		= '$kukarow[yhtio]'
					and liitostunnus	= '$otunnus'
					and ytunnus			= '$otunnus'
					and tila 			= 'K'
					and alatila 		= 'T'";
		$keikkares = mysql_query($query) or pupe_error($query);

		while($kulukeikkarow = mysql_fetch_assoc($keikkares)) {
			// Haetaan kaikki keikkaan liitettyjen laskujen summa
			$query = "	SELECT sum(arvo*if (maksu_kurssi!=0, maksu_kurssi, vienti_kurssi)) kulusumma
						FROM lasku
						WHERE yhtio		= '$kukarow[yhtio]'
						and tila 		IN ('K','X')
						and laskunro 	= '$kulukeikkarow[laskunro]'
						and vanhatunnus <> 0";
			$result = mysql_query($query) or pupe_error($query);
			$kulukulurow = mysql_fetch_assoc($result);

			$ostohinta	+= $kulukulurow["kulusumma"];
		}

	    return round($ostohinta, 6);
	}
}

if (!function_exists("remove_duplicates")) {
	function remove_duplicates($table, $yhtio) {

		$query  = "describe $table";
		$fieldresult = mysql_query($query) or pupe_error($query);

		$group = "";

		while ($fields = mysql_fetch_assoc($fieldresult)) {
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

		while ($row = mysql_fetch_assoc($result)) {
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

if (!function_exists("verkkolasku_luo_keikkafile")) {
	function verkkolasku_luo_keikkafile ($tunnus, $trow, $tuoteno) {
		global $yhtiorow, $kukarow;	
		
		// Tarvitaan $tunnus jossa on ostoreskontralaskun tunnus
		// Tarvitaan $trow jossa on toimittajan tiedot
		// Tarvitaan $tuoteno jossa on array kaikista ostoreskontralaskun tuotteista
		
		require("inc/verkkolasku-in-luo-keikkafile.inc");
	}
}

if (!function_exists("verkkolasku_luo_liitosotsikko")) {
	function verkkolasku_luo_liitosotsikko ($laskutunnus, $otunnus) {
		global $yhtiorow, $kukarow;	
		
		// Tarvitaan $laskutunnus jossa on ostoreskontralaskun tunnus
		// Tarvitaan $otunnus jossa on keikan tunnus
		// Tarvitaan $tuoteno jossa on array kaikista ostoreskontralaskun tuotteista
		
		$tee_kululaskut = "liita";
		$silent 		= "SILENT";
		$keikanalatila 	= "";
		
		require("tilauskasittely/kululaskut.inc");
	}
}



if (!function_exists("paivita_toimitukset")) {
	function paivita_toimitukset($otunnus, $originaali) {
		global $kukarow, $yhtiorow;

		/*
			Functio jolla voidaan syncronoida toimitusten sisältöjä
		*/

		foreach(array("lasku","laskun_lisatiedot") as $taulu) {

			if (!is_array($originaali[$taulu]) or (int) $otunnus == 0) {
				//echo "<font class='error'>".t("Toimitusten päivittäminen EPÄONNISTUI")."!</font><br>";
				return false;
			}

			if ($taulu == "lasku") {
				$where = " and tunnus = '$otunnus' and tunnusnippu > 0";
			}
			else {
				$where = " and otunnus = '$otunnus'";
			}

			$query = "	SELECT *
						FROM $taulu
						WHERE yhtio = '$kukarow[yhtio]' $where";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) == 1) {
				$uusirow = mysql_fetch_assoc($result);

				$diffi = array_diff_assoc($uusirow, $originaali[$taulu]);

				if (count($diffi) > 0 and is_array($diffi)) {

					//	Poistetaan numeeriset avaimet
					$diff = array();
					foreach($diffi as $key => $value) {
						if (!is_numeric($key) and !in_array($key, array("tila", "alatila", "muutospvm"))) {
							$diff[$key] = $value;
						}
					}

					//	Haetaan tunnukset jotka voidaan päivittää
					if ($taulu == "lasku") {
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

					if (mysql_num_rows($result) > 0) {

						//	Rullataan kaikki toimitukset läpi
						while($row = mysql_fetch_assoc($result)) {

							//	Rullataan kaikki muutokset läpi..
							$updquery = "";
							foreach($diff as $col => $value) {

								//	Luodaan päivitettävistä sarakkeista kysely..
								if ($row[$col] == $originaali[$taulu][$col]) {
									if ($updquery != "") {
										$updquery .=", ";
									}
									$updquery .= " $col = '$value'";

									//	Poikkeus joka vahvistaa säännön..
									if (in_array($col, array("kerayspvm", "toimaika")) and $taulu == "lasku") {
										$query = "	UPDATE tilausrivi
													SET $col = '$value'
													WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$row[tunnus]' and $col = '".$originaali[$taulu][$col]."'";
										$res = mysql_query($query) or pupe_error($query);
									}
								}
							}

							if ($updquery != "") {
								$query = "UPDATE $taulu SET $updquery WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$row[tunnus]'";
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

if (!function_exists("tulosta_ytunnus")) {
	function tulosta_ytunnus($ytunnus, $maa="", $vienti="") {
		global $yhtiorow, $kukarow;

		if ($maa == "") {
			$maa = $yhtiorow["maa"];
		}

		//jos on suomalainen yritys tehdään ytunnus nätiks
		if (strtoupper($maa) == 'FI') {

			$ytunnus = str_replace("-", "", $ytunnus); // poistetaan väliviivat jos niitä oli jo

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

if (!function_exists("enable_ajax")) {
	function enable_ajax() {
		global $palvelin2, $kukarow;

		if ($kukarow["extranet"] != "") {
			$imgUrl = $palvelin2."loading_blue_small.gif";
		}
		else {
			$imgUrl = $palvelin2."pics/loading_blue_small.gif";
		}

?>

<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">

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
				if (response.substr(0, 10) == "TABLE-ROW:") {
					//	Meidän rivi ja sen solut
					response=response.substr(9);
					var patt=/(<td[^>]*>.*?<\/td>)|(<tr[^>]*>)/gm;
					newRowElements=response.match(patt);

					var row='';
					for (var x = 0; x < newRowElements.length; x++) {

						//	Vaihdellaan riviä
						if (newRowElements[x].match(/<tr[^>]*>/)) {
							y=0;
							if (row=='') {
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
				c=allNewScripts.length;
				if (c>0) {
					for (x=0;x<=c;x++) {
						if (allNewScripts[x]) {
							eval(allNewScripts[x].innerHTML);
						}
					}
				}

				//	Jos päivitetään togglegrouppaus
				if (href) {
					document.getElementById(href).href = 'javascript:toggleGroup("' + id + '");';
					toggled.push(id);
				}

				//	Varmistetaan näkyvyys
				document.getElementById(id).style.display = '';

			}
		}

		this.doPost = function() {
			http.open('post', urli + '&ohje=off', true);
			http.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			http.send(data);
		}

		this.doGet = function() {
			http.open('get', urli + '&' + data + '&ohje=off');
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
		if (load) {
			ai.showLoader();
		}

		if (unToggle) {
			ai.unToggle();
		}

		//	Otetaan yhteydet
		if (method == 'post') {
			ai.doPost();
		}
		else {
			ai.doGet();
		}

	}

	function unToggle(id) {
		for (x in toggled) {
			if (toggled[x] != id) {
				if (document.getElementById(toggled[x])) {
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
					getstr += obj.elements[i].name + "=" + encodeURIComponent(obj.elements[i].value) + "&";
				}
				else if (obj.elements[i].type == "checkbox") {
					if (obj.elements[i].checked) {
						getstr += obj.elements[i].name + "=" + encodeURIComponent(obj.elements[i].value) + "&";
					}
					else {
						getstr += obj.elements[i].name + "=&";
					}
				}
				else if (obj.elements[i].type == "radio") {
					if (obj.elements[i].checked) {
						getstr += obj.elements[i].name + "=" + encodeURIComponent(obj.elements[i].value) + "&";
					}
				}
			}
			else if (obj.elements[i].tagName == "SELECT") {
				var sel = obj.elements[i];
				if (sel.multiple) {
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
				getstr += obj.elements[i].name + "=" + encodeURIComponent(obj.elements[i].value) + "&";
			}
		}

		if (method==undefined) {
			method="post";
		}

		sndReq(minne, urli, href, load, unToggle, method, getstr);
	}

	var keyStrokeIndex=-1;

	function livesearch_keyhandler(event, haku_id, haku_formi, dont_submit_after) {

		var selectOptions = document.getElementsByName('selectOptions'+haku_id);

		for (var iEl = 0; iEl < selectOptions.length; iEl++) {
			document.getElementById(selectOptions[iEl].id).className='';
		}

		if (event.keyCode == 40 || event.keyCode == 38) {
			if (event.keyCode  == 40) {
				previousKeyStrokeIndex=keyStrokeIndex;
				keyStrokeIndex++;
			}
			else if (event.keyCode == 38){
				previousKeyStrokeIndex=keyStrokeIndex;
				keyStrokeIndex--;
			}

			if (keyStrokeIndex > selectOptions.length-1) {
				keyStrokeIndex = selectOptions.length-1;
			}
			else if (keyStrokeIndex < 0) {
				keyStrokeIndex = -1;
				selectOptions[previousKeyStrokeIndex].className='';
			}
			else {
				if (previousKeyStrokeIndex < 0) {
					previousKeyStrokeIndex = 0;
				}

				if (previousKeyStrokeIndex != keyStrokeIndex) {
					selectOptions[previousKeyStrokeIndex].className='';
				}

				selectOptions[keyStrokeIndex].className='liveSearchSelectedItem';
			}
		}
		else if (event.keyCode == 13) {
			if (keyStrokeIndex >= 0 && keyStrokeIndex <= selectOptions.length-1) {
				var selectValues = document.getElementsByName('selectValues'+haku_id);
				document.getElementById(haku_id).value=selectValues[keyStrokeIndex].id;
				if (dont_submit_after) {
					document.getElementById('livesearch_'+haku_id).style.visibility = 'hidden';
				}
				else {
					document.haku_formi.submit();
				}
				keyStrokeIndex=-1;
			}
			else {
				document.haku_formi.submit();
			}

			return false;
		}
		else if (event.keyCode == 27) {
			selectOptions[keyStrokeIndex].className='';
			KeyStrokeIndex = -1;
		}
		else if (event.keyCode == 9) {
			document.getElementById('livesearch_'+haku_id).style.visibility = 'hidden';
			KeyStrokeIndex = -1;
		}

		if (keyStrokeIndex >= 0 && keyStrokeIndex <= selectOptions.length-1) {
			selectOptions[keyStrokeIndex].className='liveSearchSelectedItem';
		}
	}

</script>

<?php
	}
}

if (!function_exists("js_selectAllCheckboxesByName")) {
	function js_selectAllCheckboxesByName() {
		?>
		<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">
			function selectAllCheckboxesByName(suunta, name) {
				list=document.getElementsByTagName("input")
				if(list.length > 0) {

					for(i=0;i<=list.length;i++){
						if(list[i].type == "checkbox") {
							if(list[i].name.substr(0, name.length) == name) {
								list[i].checked=suunta;
							}
						}
					}
				}
			}
		</SCRIPT>
		<?php
	}
}

if (!function_exists("js_yllapito")) {
	function js_yllapito() {
?>
<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">

function yllapito(url, tag, submitOnSelect) {
	select=document.getElementById(tag);

	toim=url.substr(0,url.indexOf("&"));
	valinta=select.options[select.selectedIndex].value;

	//	Jos meillä on valintana uusi, siirrytään tekemään uusi tietue!
	if (isNaN(valinta)) {

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

		if (height==0 || width==0) {
			alert('Ylläpitotoimintoa '+toim+' ei tunneta!');
		}
		else {
			//	Tehdään uutta
			if (valinta=='uusi') {
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
			if (select.options[i].value.substring(0,7)=='muokkaa') {
				if (valinta=='') {
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
		if (sain=='en') {

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
function suljeYllapito(sID,value,text, fromDiv) {

	if (fromDiv) {
		ref=document;
	}
	else {
		ref=window.opener.document;
	}

	//	Wanhaa on muokattu, submittoidaan formi tarvittaessa..
	if (sID.substring(0,2)=='P_') {
		sID=sID.substr(2);

		sel=window.opener.document.getElementById(sID);
		//	merkataan oikea valituksi
		for (i=0;i<sel.length;i++) {
			if (sel.options[i].value==value) {
				sel.selectedIndex=i;
				sel.options[i].text=text;
			}
		}
	}
	else {

		//	Paivitetaan ja valitaan select option
		var newOpt=document.createElement('option');
		newOpt.text=text;
		newOpt.value=value;

		//	Jos päivitettiin yhteyshenkilöitä meidän pitää listätä ne kaikkiin valikkoihin..
		sela='EI'
		if (sID=='yhteyshenkilo_kaupallinen') {
			sela=ref.getElementById('yhteyshenkilo_tekninen');
		}
		else if (sID=='yhteyshenkilo_tekninen') {
			sela=ref.getElementById('yhteyshenkilo_kaupallinen');
		}

		if (sela!='EI') {
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

		sel=ref.getElementById(sID);

		try {
			sel.add(newOpt, sel.options[1]);
		}
		catch(ex) {
			sel.add(newOpt, 1);
		}

		//	Valitaan uusi arvo
		sel.selectedIndex=1;

		if (fromDiv) {
			pop=ref.getElementById('ajax_menu_yp');
			pop.style.display='none';
			pop.innerHTML='';
		}
		else {
			window.close();
		}

	}
}

</script>
<?php
	}
}

if (!function_exists("js_showhide")) {
	// scripti balloonien tekemiseen
	function js_showhide () {

?>
<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">

function showhide(layer_ref, type) {


	if (type=='byName') {
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


	if (type=='byName') {
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

</script>
<?php

	}
}

if (!function_exists("js_alasvetoMaxWidth")) {
	function js_alasvetoMaxWidth($id, $leveys) {
		return "id = \"$id\" style=\"max-width: $leveys; width: expression(if(parseInt(document.getElementById('$id').clientWidth) > $leveys) { $leveys });\" onmousedown=\"var browser = navigator.appName.toUpperCase(); var loytyyko = browser.indexOf('EXPLORER'); if (loytyyko >= 0) {document.getElementById('$id').style.width = 'auto';}\" onblur=\"var browser = navigator.appName.toUpperCase(); var loytyyko = browser.indexOf('EXPLORER'); if (loytyyko >= 0 && $leveys > 0) {cwidth = document.getElementById('$id').clientWidth; if (cwidth > $leveys) { document.getElementById('$id').style.width = '$leveys'+'px';} else {document.getElementById('$id').style.width = 'auto';}}\"";
	}
}

if (!function_exists("js_toimehtoTarkenne")) {
	function js_toimehtoTarkenne() {
?>
<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">

function toimehtoTarkenne(toimehto) {
	tehto=document.getElementById(toimehto);
	tehtoLisa=document.getElementById(toimehto+'Lisa');

	teksti=tehto.options[tehto.selectedIndex].text;
	arvo=tehto.options[tehto.selectedIndex].value;

	i=teksti.indexOf("-");
	if (i>0) {
		tarkenne=teksti.substr((i+1));
		tehtoLisa.value=tarkenne;
	}
	else {
		tehtoLisa.value='';
	}
}

</script>
<?php
	}
}

if (!function_exists("js_popup")) {
	// scripti balloonien tekemiseen
	function js_popup () {

	echo "<script type='text/javascript' language='javascript'>";

	if (file_exists("inc/jquery.min.js")) {
		require("inc/jquery.min.js");
	}
	else {
		require("jquery.min.js");
	}

?>

	jQuery.fn.tooltip = function(allowHtml, className){
		jQuery.fn.tooltip.created.id = 'toolTip';
		$('body').append(jQuery.fn.tooltip.created);

		var toolTip = $(jQuery.fn.tooltip.created);

	    toolTip.css({'position':'absolute','display':'none'});

	    //functions
		function getMouseX(e) {
			var x = null;

			if (e.pageX) 	{
				x = e.pageX;
			}
			else if (e.clientX) {
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
			else if (e.clientY) {
				y = e.clientY + document.body.scrollTop
					+ document.documentElement.scrollTop;
			}

			return y;
		}

	    function toolTipShow(e, x, y) {
	        toolTip.css({'top':y, 'left':x}).show();
	    }

	    function toolTipHide() {
			toolTip.hide();
	    }

	    //events for each element
	    this.each(function() {

	        $(this).mousemove(function(e){
				var x = getMouseX(e) + 40;
				var y = getMouseY(e);

				// get content id from element and fetch text from the div
				var content_id = $(this).attr('id');
				var content = $('#div_'+content_id).html();

				var div_height = $('#div_'+content_id).height();
				var div_width = $('#div_'+content_id).width();

				var window_width = $(window).width();
				var window_height = $(window).height();

				var scrollY = document.body.scrollTop + document.documentElement.scrollTop;
				var scrollX = document.body.scrollLeft + document.documentElement.scrollLeft;

				//	Jos saimme riittävästi tietoa voimme kalkuloida oikean position
				if (div_height != null && div_width != null && x != null && y != null) {
					//	Riittääkö leveys
					if (((x - scrollX) + div_width + 30) > window_width) {
						//	Siirretään tämä ihan oikeaan laitaan..
						x = window_width - div_width - 30 + scrollX;
					}

					if ((y - scrollY) + div_height > window_height) {
						y -= (div_height);
					}

					//	Oikea laita on kuitenkin aina tärkein!
					if (x < 10) {
						x = 10;
					}
					if (y < 10) {
						y = 10;
					}
				}

				toolTipShow(e, x, y);

	            //update the content
	            if(allowHtml) {
	                toolTip.html(content);
				}
	            else {
	                toolTip.text(content);
				}

	            //remove all classes for the tipBox before add a new one and to avoid the 'append class'
	            toolTip.removeClass();

	            //set class if specified
	            if(className) {
					toolTip.addClass(className);
				}
			});

	        $(this).mouseout(function(){
	            toolTipHide();
	        });
	    });
	};

	$(function(){
		//create the element (avoiding create multiple divisions for the tooltip)
		jQuery.fn.tooltip.created = document.createElement('div');

		$('.tooltip').tooltip('yes', 'popup');
	});

</script>
<?php

	}
}

if (!function_exists("encodeURI")) {
	function encodeURI($string) {
		/*
		PHP URL encoding/decoding functions for Javascript interaction V3.0
		(C) 2006 www.captain.at - all rights reserved
		License: GPL
		*/

		//	Apufunktio
		if (!function_exists("encodeURIbycharacter")) {
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

if (!function_exists("decodeURI")) {
	function decodeURI($string) {
		/*
		PHP URL encoding/decoding functions for Javascript interaction V3.0
		(C) 2006 www.captain.at - all rights reserved
		License: GPL
		*/

		//	Apufunktio
		if (!function_exists("decodeURIbycharacter")) {
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

if (!function_exists("tallenna_muisti")) {
	function tallenna_muisti($nimitys, $exclude = "") {
		global $kukarow;

		if ($nimitys == "") {
			return false;
		}

		$data = $_REQUEST;

		if (is_array($exclude)) {
			foreach ($exclude as $kentta) {
				unset($data[$kentta]);
			}
		}

		$nimitys = mysql_real_escape_string($nimitys);
		$sovellus = mysql_real_escape_string($sovellus);
		$data = mysql_real_escape_string(serialize($data));

		$query = "	REPLACE INTO tallennetut_parametrit SET
					yhtio		= '$kukarow[yhtio]',
					kuka		= '$kukarow[kuka]',
					nimitys		= '$nimitys',
					sovellus	= '$_SERVER[SCRIPT_NAME]',
					data		= '$data',
					laatija		= '$kukarow[kuka]',
					luontiaika  = now()";
		$result = mysql_query($query) or pupe_error($query);

		return true;
	}
}

if (!function_exists("hae_muisti")) {
	function hae_muisti($nimitys, $kuka = '') {
		global $kukarow;

		if ($kuka == "") {
			$kuka = $kukarow["kuka"];
		}

		$query = "	SELECT *
					FROM tallennetut_parametrit
					WHERE yhtio = '$kukarow[yhtio]'
					and nimitys = '$nimitys'
					and kuka = '$kuka'
					and sovellus = '$_SERVER[SCRIPT_NAME]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$row = mysql_fetch_assoc($result);
			$data = unserialize($row["data"]);

			foreach ($data as $nimi => $arvo) {
				global ${$nimi};
				${$nimi} = $arvo;
			}
		}
		else {
			return false;
		}
	}
}

if (!function_exists("muistiin")) {
	function muistiin ($muisti, $nimi, $muistettava, $exclude = "") {
		global $kukarow;

		if ($muisti == "" or $nimi == "" or !is_array($muistettava)) {
			return false;
		}

		if (!is_array($exclude)) {
			$exclude = array();
		}

		// Poistetaan vanha kysely sekä tmpquery
		$query = "	DELETE
					FROM muisti
					WHERE yhtio = '$kukarow[yhtio]'
					and haku = '$muisti'
					and kuka = '$kukarow[kuka]'
					and nimi = '$nimi'";
		$result = mysql_query($query) or pupe_error($query);

		foreach ($muistettava as $key => $value) {

			if (!in_array($key, $exclude)) {

				// Katsotaan onko kyseessä array
				$array = "";
				if (is_array($value)) {
					$value = serialize($value);
					$array = "X";
				}

				$value = mysql_real_escape_string($value);

				$query = "	INSERT INTO muisti SET
							yhtio		= '$kukarow[yhtio]',
							kuka		= '$kukarow[kuka]',
							haku		= '$muisti',
							nimi		= '$nimi',
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

		return true;
	}
}

if (!function_exists("muistista")) {
	function muistista ($muisti, $nimi) {
		global $kukarow;

		$query = "	SELECT *
					FROM muisti
					WHERE yhtio = '$kukarow[yhtio]'
					and haku = '$muisti'
					and kuka = '$kukarow[kuka]'
					and nimi = '$nimi'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			return false;
		}

		$retval = array();

		while ($row = mysql_fetch_assoc($result)) {
			if ($row["array"] != "") {
				$retval[$row["var"]] = unserialize($row["value"]);
			}
			else {
				$retval[$row["var"]] = stripslashes($row["value"]);
			}
		}

		return $retval;
	}
}

if (!function_exists("aja_kysely")) {
	function aja_kysely() {
		global $kukarow, $hakukysely, $aja_kysely, $uusi_kysely, $tallenna_muutokset, $poista_kysely, $_POST;

		if (!table_exists("muisti") or $hakukysely == "") {
			return true;
		}

		//	Tallennetaan viimeisin kysely aina muistiin jotta voimme kutsua sitä helposti uudestaan
		if ($aja_kysely == "" and $uusi_kysely == "") {
			$tallenna_muutokset = "X";
			$aja_kysely = "tmpquery";
		}

		if ($tallenna_muutokset != "") {
			if ($aja_kysely != "") {
				$uusi_kysely = $aja_kysely;
			}
			else {
				echo "<font class='error'>".t("Valitse kysely jonka muutokset tallennetaan")."</font>";
				return false;
			}
		}

		if ($poista_kysely != "" and $aja_kysely == "") {
			echo "<font class='error'>".t("Valitse kysely jonka haluat poistaa")."</font>";
			return false;
		}

		//	Tallennetaan uusi kysely
		if ($uusi_kysely != "") {
			if (is_array($_POST) and count($_POST)>0) {

				if ($exclude != "") {
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

					if (!in_array($key, $exclude)) {

						//	Koitetaan arpoa pari speciaalia datatyyppiä
						$array = "";
						if (is_array($value)) {
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
						if ($aja_kysely != "tmpquery") {
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

			if ($aja_kysely != "tmpquery") {
				echo "<font class='message'>".t("Tallennettiin kysely")." $aja_kysely</font>";
			}
			return true;
		}
		//	Poistetaan kysely
		elseif ($poista_kysely != "" and $aja_kysely != "") {
			$query = "	DELETE
						FROM muisti
						WHERE yhtio='$kukarow[yhtio]' and haku = '$hakukysely' and kuka = '$kukarow[kuka]' and nimi = '$aja_kysely'";
			$result = mysql_query($query) or pupe_error($query);

			echo "<font class='message'>".t("Poistettiin kysely")." $aja_kysely</font>";

			return false;
		}
		//	Ajetaan kysely muistista
		elseif ($aja_kysely != "") {
			//	Onko tämä arvo jo tallennettu?
			$query = "	SELECT *
						FROM muisti
						WHERE yhtio='$kukarow[yhtio]' and haku = '$hakukysely' and kuka = '$kukarow[kuka]' and nimi = '$aja_kysely'";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result)>0) {
				while($row = mysql_fetch_assoc($result)) {
					global ${$row["var"]};
					if ($row["array"] != "") {
						$row["value"] = unserialize($row["value"]);
					}

					${$row["var"]} = $row["value"];
				}
			}

			if ($aja_kysely != "tmpquery") {
				echo "<font class='message'>".t("Suoritetaan kysely")." $aja_kysely</font>";
			}
			return true;
		}

		return false;
	}
}

if (!function_exists("nayta_kyselyt")) {
	function nayta_kyselyt($haku) {
		global $kukarow, $aja_kysely;

		if (!table_exists("muisti") or $haku == "") {
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
		if (mysql_num_rows($result)>0) {
			while($row = mysql_fetch_assoc($result)) {
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

if (!function_exists('muuta_kuvan_koko')) {
	function muuta_kuvan_koko ($ykoko, $xkoko, $type, $taulu, $upfile1) {
		global $kukarow, $yhtiorow, $_FILES;

		if ($yhtiorow['kuvapankki_polku'] != "") $dirri = $yhtiorow['kuvapankki_polku']."/".$kukarow['yhtio']."/".$taulu."/".$type."/";
		else $dirri = "/tmp/";

		list($usec, $sec) = explode(" ",microtime());

		$nimi      = $usec+$sec; // uniikki nimi
		$resize    = ""; // resize komento
		$crop      = ""; // crop komento
		$upfileall = ""; // palautus

		$filetsu = $_FILES[$upfile1];

		$path_parts = pathinfo($filetsu["name"]);
		$ext = strtolower($path_parts['extension']);

		$image = getimagesize($filetsu["tmp_name"], $imageinfo);	// lähetetty kuva
		$leve = $image[0];
		$kork = $image[1];

		// eihän molemmat (y ja x) ole nollia?!
		if ($ykoko > 0 or $xkoko > 0) {
			/* skaaltaanko x:n vai y:n mukaan, jos xkoko tai ykoko == 0, skaalataan != 0 mukaan */
			if ($ykoko > 0 and $kork > $ykoko) {
				$upfilesgh = strtolower("/tmp/$nimi"."1.".$ext);

				// skaalataan kuva oikenakokoiseksi y:n mukaan
		    	exec("nice -n 20 convert -resize x$ykoko -quality 80 $filetsu[tmp_name] $upfilesgh", $output);
		    }
			elseif ($xkoko > 0 and $leve > $xkoko) {
				$upfilesgh = strtolower("/tmp/$nimi"."1.".$ext);

				// skaalataan kuva oikenakokoiseksi x:n mukaan
		  		exec("nice -n 20 convert -resize $xkoko -quality 80 $filetsu[tmp_name] $upfilesgh", $output);
		    }
			else {
				return $upfile1;
		    }

			$uusnimi = $dirri.$filetsu["name"];

			if (!copy($upfilesgh, $uusnimi)) {
		    	echo "Kopiointi epäonnistui $upfilesgh -> $uusnimi<br>";

				$upfileall = "";
			}
			else {
				$upfileall = $uusnimi;
			}

			$a = getimagesize($uusnimi);

			$file["name"] 		= basename($uusnimi);
			$file["type"] 		= $a["mime"];
			$file["tmp_name"] 	= $uusnimi;
			$file["error"] 		= 0;
			$file["size"] 		= filesize($uusnimi);

			$_FILES[$upfile1] = $file;

			// poistetaan file
			system("rm -f $upfilesgh");
		}
		else {
			$upfileall = "";
		}

		return $upfileall;
	}
}

if (!function_exists('tallenna_liite')) {
	function tallenna_liite($userfile, $liitos, $liitostunnus, $selite, $kayttotarkoitus="", $tunnus=0, $jarjestys=0, $kieli="") {
		global $kukarow, $yhtiorow, $_FILES;

		$file = array();

		if ($kieli == "") {
			$kieli = $yhtiorow["kieli"];
		}

		if (is_array($_FILES[$userfile])) {
			$file = $_FILES[$userfile];
		}
		elseif (file_exists($userfile)) {
			$a = getimagesize($userfile);

			$file["name"] 		= basename($userfile);
			$file["type"] 		= $a["mime"];
			$file["tmp_name"] 	= $userfile;
			$file["error"] 		= 0;
			$file["size"] 		= filesize($userfile);

			$_FILES[$userfile] = $file;
		}

		$tark = tarkasta_liite($userfile);

		if ($tark !== true or $file["error"] == 4) {
			return false;
		}

		$data = mysql_real_escape_string(file_get_contents($file["tmp_name"]));

		$filename = $file["name"];
		$filetype = $file["type"];
		$filesize = $file["size"];

		//	Tarkastetaan, että kuvatunnus on oikea..
		if ($tunnus > 0) {
			$query = "	SELECT tunnus FROM liitetiedostot WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				$tunnus = 0;
			}
		}

		//	Onko meillä jokin kuvatyyppi?
		list($type, $crap) = explode("/", $filetype);

		if ($type == "image") {
			$size = getimagesize($file["tmp_name"], $imageinfo);
			$image_width 	= $size[0];
			$image_height 	= $size[1];
			$image_bits 	= $size["bits"];
			$image_channels	= $size["channels"];

			unset($size);
		}
		else {
			$image_width 	= "";
			$image_height 	= "";
			$image_bits 	= "";
			$image_channels	= "";
		}

		// Jos ei olla saatu filetyyppiä niin arvotaan se vaikka filen nimestä
		if ($filetype == "") {
			if ($filename != "") {
				$path_parts = pathinfo($filename);
				$extensio = strtolower($path_parts['extension']);

				if ($extensio == "jpg" or $extensio == "jpeg") {
					$filetype = "image/jpeg";
				}
				elseif ($extensio == "pdf" ) {
					$filetype = "application/pdf";
				}
				elseif (substr($extensio, 0, 3) == "xls" ) {
					$filetype = "application/vnd.ms-excel";
				}
				elseif (substr($extensio, 0, 3) == "doc" ) {
					$filetype = "application/msword";
				}
				else {
					$filetype = "application/octet-stream";
				}
			}
			else {
				$filetype = "application/octet-stream";
			}
		}

		// lisätään kuva
		if ($tunnus > 0) {
			$query = "	UPDATE liitetiedostot SET
						data     		= '$data',
						selite   		= trim('$selite'),
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
				$row = mysql_fetch_assoc($result);

				$jarjestys = $row["jarjestys"]+1;
			}

			$query = "	INSERT INTO liitetiedostot SET
					  	yhtio    		= '$kukarow[yhtio]',
					  	liitos   		= '$liitos',
					  	liitostunnus 	= '$liitostunnus',
					  	data     		= '$data',
					  	selite   		= trim('$selite'),
						kieli			= '$kieli',
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

if (!function_exists('tarkasta_liite')) {
	function tarkasta_liite($userfile, $sallitut_tiedostot="") {
		global $kukarow, $yhtiorow, $_FILES, $kieli;

		if (!is_array($_FILES[$userfile])) {
			return false;
		}

		$file = $_FILES[$userfile];

		// otetaan file extensio
		$path_parts = pathinfo($file['name']);
		$ext = strtoupper($path_parts['extension']);
		if ($ext == "JPEG") $ext = "jpg";

		//	Sallitut tiedostot on aina upper
		if (is_array($sallitut_tiedostot)){
			foreach($sallitut_tiedostot as &$s) {
				$s = strtoupper($s);
			}
		}

		//	Ei saatu erroreita. jatketaan..
		if ($file["size"] == 0) {
			return "<font class='error'>".t("VIRHE! Tiedosto on tyhjä")."</font><br><br>";
		}
		elseif ($file["error"] == 0) {

			//	Paketti riittävän pieni mysql:lle
			$query = "SHOW variables like 'max_allowed_packet'";
			$result = mysql_query($query) or pupe_error($query);
			$varirow = mysql_fetch_row($result);

			if ($file["size"] < $varirow[1]) {
				//	Tämä on ainoa haara jossa voimme jatkaa!
				if (!is_array($sallitut_tiedostot) and $sallitut_tiedostot == "") {
					return true;
				}
				elseif (in_array(strtoupper($ext), $sallitut_tiedostot)) {
					return true;
				}
				else {
					if (count($sallitut_tiedostot)>1) {
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
		elseif ($file["error"] == 1 or $file["error"] == 2) {
			return "<font class='error'>".t("VIRHE! Tiedosto on liian suuri!")."!</font><br><br>";
		}
		elseif ($file["error"] == 3) {
			return "<font class='error'>".t("VIRHE! Tiedoston lataus epäonnistui!")."!</font><br><br>";
		}
		elseif ($file["error"] == 4) {
			return true;
		}
		elseif ($file["error"] == 7) {
			return "<font class='error'>".t("VIRHE! Palvelinasetuksissa on virhe!")."!</font><br><br>";
		}
		else {
			return "<font class='error'>".t("VIRHE! Tapahtui virhe tallennettaessa tiedostoa!")."!</font><br><br>";
		}
	}
}

if (!function_exists('hae_liite')) {
	function hae_liite($tunnus, $liitos, $palautus="") {
		global $kukarow, $yhtiorow;

		$query = "select * from liitetiedostot where tunnus='$tunnus' and liitos = '$liitos'";
		$liiteres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($liiteres) > 0) {
			$liiterow = mysql_fetch_assoc($liiteres);

			if ($palautus != "") {
				return $liiterow;
			}
			return $liiterow["data"];
		}

		return false;
	}
}

if (!function_exists("tarvitaanko_intrastat")) {
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

if (!function_exists("js_liveSearch")) {
	function js_liveSearch() {

		echo "	<SCRIPT TYPE='text/javascript' LANGUAGE='JavaScript'>
			var keyStrokeIndex=-1;

			function keyhandler(event) {

				var selectOptions = document.getElementsByName('selectOptions');

				if (event.keyCode == 40 || event.keyCode == 38) {
					if (event.keyCode  == 40) {
						previousKeyStrokeIndex=keyStrokeIndex;
						keyStrokeIndex++;
					}
					else if (event.keyCode == 38){
						previousKeyStrokeIndex=keyStrokeIndex;
						keyStrokeIndex--;
					}

					if (keyStrokeIndex > selectOptions.length-1) {
						keyStrokeIndex = selectOptions.length-1;
					}
					else if (keyStrokeIndex < 0) {
						keyStrokeIndex = -1;
						selectOptions[previousKeyStrokeIndex].className='liveSearchUnSelectedItem';
					}
					else {

						if (previousKeyStrokeIndex < 0) {
							previousKeyStrokeIndex = 0;
						}

						if (previousKeyStrokeIndex != keyStrokeIndex) {
							selectOptions[previousKeyStrokeIndex].className='liveSearchUnSelectedItem';
						}

						selectOptions[keyStrokeIndex].className='liveSearchSelectedItem';
					}
				}
				else if (event.keyCode == 13){

					if (keyStrokeIndex >= 0 && keyStrokeIndex <= selectOptions.length-1) {
						var selectValues = document.getElementsByName('selectValues');
						eval(selectValues[keyStrokeIndex].innerHTML);
						keyStrokeIndex=-1;
					}

					return false;
				}
				else if (event.keyCode == 27){
					selectOptions[keyStrokeIndex].className='liveSearchUnSelectedItem';
					KeyStrokeIndex = -1;
				}

				if (keyStrokeIndex >= 0 && keyStrokeIndex <= selectOptions.length-1) {
					selectOptions[keyStrokeIndex].className='liveSearchSelectedItem';
				}
			}

		</SCRIPT>";

	}
}

if (!function_exists("tee_asiakashaku")) {
	function tee_asiakashaku($ytunnus, $formi, $lopetus="", $div="") {
		global $yhtiorow, $kukarow, $palvelin2;

		if (trim($konserni) != '') {
			$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
			$result = mysql_query($query) or pupe_error($query);
			$konsyhtiot = "";

			while ($row = mysql_fetch_assoc($result)) {
				$konsyhtiot .= " '".$row["yhtio"]."' ,";
			}
			$konsyhtiot = " yhtio in (".substr($konsyhtiot, 0, -1).") ";
		}
		else {
			$konsyhtiot = " yhtio = '$kukarow[yhtio]' ";
		}

		$ytunnus = addslashes(trim($ytunnus));
		$limit   = 50; // montako riviä on maksimi
		$monta   = 0;

		//	Jos käyttäjällä on valittu piirejä niin sallitaan vain ko. piirin/piirien hakeminen
		if ($kukarow["piirit"] != "") {
			$asiakasrajaus = "and piiri IN ($kukarow[piirit])";
		}
		else {
			$asiakasrajaus="";
		}
		if ($div == "") $div = "ajax_menu";

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
							group by 1,2,3,4,5 order by 1
							LIMIT $limit";
				$result = mysql_query($query) or pupe_error($query);
				$monta  = mysql_num_rows($result);

				if ($monta != 0) $monta  = 2; // aina selailuun niin menee liitostunnukset ja ytunnukset oikein
			}

			if ($monta <= 5  and substr($ytunnus,0,1) == "#") {
				// jos eka merkki on # etsitään toimitusnimen perusteella

				// eka merkki pois alusta
				$ytunnus = substr($ytunnus, 1);

				// kokeillaan ihan asiakkaan nimellä laskulta
				$query	= "	SELECT toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp, nimi, nimitark, osoite, postino, postitp, concat('£',max(tunnus)) ytunnus, concat('£',max(tunnus)) tunnus, yhtio
							FROM lasku use index (asiakastoim_nimi)
							WHERE $konsyhtiot and tila != 'D' and match (toim_nimi) against ('$ytunnus*' IN BOOLEAN MODE)
							group by 1,2,3,4,5 order by 1
							LIMIT $limit";
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
							LIMIT $limit";
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
								LIMIT $limit";
					$result = mysql_query($query) or pupe_error($query);
					$monta  = mysql_num_rows($result);
				}
			}

			if ($monta <= 5) {
				// exactihaku
				$query	= "	(SELECT * FROM asiakas use index (ytunnus_index) WHERE $konsyhtiot and ytunnus = '$ytunnus' and ytunnus!='' and laji != 'P' $asiakasrajaus)
							UNION
							(SELECT * FROM asiakas use index (asno_index) WHERE $konsyhtiot and asiakasnro = '$ytunnus' and asiakasnro!=0 and laji != 'P' $asiakasrajaus)
							UNION
							(SELECT * FROM asiakas use index (ovttunnus_index) WHERE $konsyhtiot and ovttunnus = '$ytunnus' and ovttunnus!='' and laji != 'P' $asiakasrajaus)
							UNION
							(SELECT * FROM asiakas use index (toim_ovttunnus_index) WHERE $konsyhtiot and toim_ovttunnus = '$ytunnus' and toim_ovttunnus!='' and laji != 'P' $asiakasrajaus)
							ORDER BY nimi
							LIMIT $limit";
				$result = mysql_query($query) or pupe_error($query);
				$monta  = mysql_num_rows($result);
			}

			if ($monta <= 5) {
				// kokeillaan ytunnuksen alulla
				$query	= "	SELECT *
							FROM asiakas use index (ytunnus_index)
							WHERE $konsyhtiot
							and ytunnus like '$ytunnus%'
							and ytunnus!=''
							and laji != 'P'
							$asiakasrajaus
							ORDER BY asiakas.nimi
							LIMIT $limit";
				$result = mysql_query($query) or pupe_error($query);
				$monta  = mysql_num_rows($result);
			}

			if ($monta <= 5 and is_string($ytunnus)) {
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
							ORDER BY nimi
							LIMIT $limit";
				$result = mysql_query($query) or pupe_error($query);
				$monta  = mysql_num_rows($result);
			}
		}
		else {
			echo "<font class='info'>".t("Anna vähintään 3 merkkiä.")."</font>";
		}

		if (mysql_num_rows($result) == $limit) {
			echo "<font class='info'>".t("HUOM! osa hakutulosesta nätetään vain %s ensimmäistä", $kieli, $limit)."</font>";
		}

		if (mysql_num_rows($result)) {
			while($row = mysql_fetch_assoc($result)) {
				//ajaxPost('$formi', '$urli?' , '$div', false, true);
				echo "	<div id='asiakas_valinta_$row[tunnus]' name='selectOptions' onmouseover=\"this.className='liveSearchSelectedItem';\" onmouseout=\"this.className='liveSearchUnSelectedItem';\" onclick=\"eval(document.getElementById('asiakas_toiminto_$row[tunnus]').innerHTML);\" class='unSelectedItem' style='width: 500px;'>
						<table width = '100%' class='liveSearch'>
							<tr>
								<th width='50%' class='liveSearch'>
									$row[nimi] $row[nimitark]
								</th>
								<th width='50%' class='liveSearch'>
									$row[toim_nimi] $row[toim_nimitark]
								</th>
							</tr>
							<tr>
								<td width='50%' class='liveSearch'>
									$row[osoite]<br>$row[postino] $row[postitp]<br>".maa($row["maa"])."
								</td>
								<td width='50%' class='liveSearch'>
								$row[toim_osoite]<br>$row[toim_postino] $row[toim_postitp]<br>".maa($row["toim_maa"])."
								</td>
							<tr>
						</table>
						</div>
						<div id='asiakas_toiminto_$row[tunnus]' name='selectValues' style='display: none;'>document.getElementById('liitos').value='$row[tunnus]'; document.getElementById('haku').value='$row[nimi] $row[nimitark]'; document.getElementById('haku').disabled='true'; asval=document.getElementById('asiakashaku'); asval=document.getElementById('asiakashaku'); asval.innerHTML=''; asval.style.display='none'; ajaxPost('$formi', '$_SERVER[SCRIPT_NAME]?from=asiakasvalinta' , '$div', false, true);</div>";
			}
		}
	}
}

if (!function_exists("nayta_asiakashaku")) {
	function nayta_asiakashaku($liitostunnus, $formi, $vars="") {
		global $kukarow;

		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 1) {
			$asiakasrow = mysql_fetch_assoc($result);

			$disabled 	= "DISABLED";
			$nimi 		= $asiakasrow["nimi"]." ".$asiakasrow["nimitark"];

		}
		else {
			$disabled 	= "";
			$nimi 		= "";
			$id			= "";
		}

		return "<input type='hidden' id='liitos' name='liitostunnus'><input type='text' id='haku' value='$nimi' name='haku' size='50' onkeydown=\"return keyhandler(event);\" onkeyup=\"if ((event.keyCode < 37 || event.keyCode > 40) && event.keyCode != 13) { popUp(event, 'asiakashaku', '-6', '-30', '$_SERVER[SCRIPT_NAME]?tee=ASIAKASHAKU&formi=$formi&url=$_SERVER[SCRIPT_NAME]&haku='+this.value, 'liveSearch'); keyStrokeIndex = -1; }\" $disabled autocomplete='off'>&nbsp;&nbsp;<a href='#' onclick=\"document.getElementById('haku').disabled=false; document.getElementById('haku').value=''; return false;\" onblur=\"document.getElementById('liveSearch').style.display='none';\">".t("Vaihda asiakasta")."</a>
			<script LANGUAGE='JavaScript'>window.document.$formi.haku.focus();</script>";
	}
}

if (!function_exists("tee_tuotehaku")) {
	function tee_tuotehaku($haku, $formi, $lopetus="", $div="", $hakuID="", $liitosID="") {
		global $yhtiorow, $kukarow, $palvelin2;

		$haku = trim($haku);
		if(strlen($haku)>=3) {
			$query = "";
			$harray = explode(" ", $haku);
			$i = 0;
			for($i=0;$i<=count($harray);$i++) {

				if($query != "") {
					$query .="\nUNION\n";
				}
				$query .= "	(
								SELECT tuote.tunnus, tuote.tuoteno, tuote.nimitys, tuote.kuvaus, concat_ws(' ', try.selite, try.selitetark) try, concat_ws(' ', osasto.selite, osasto.selitetark) osasto
								FROM tuote
								LEFT JOIN avainsana try ON try.yhtio=tuote.yhtio and try.laji='TRY' and try.selite=tuote.try
								LEFT JOIN avainsana osasto ON osasto.yhtio=tuote.yhtio and osasto.laji='OSASTO' and osasto.selite=tuote.osasto
								WHERE tuote.yhtio='$kukarow[yhtio]' and concat(tuote.tuoteno,tuote.nimitys) like ('%".implode("%", $harray)."%')
							)";

				$jog = array_shift($harray);
				$harray[] = $jog;
			}

			$query.="LIMIT 8";
			//echo "<pre>$query</pre>";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result)) {
				while($row = mysql_fetch_assoc($result)) {
					//ajaxPost('$formi', '$urli?' , '$div', false, true);
					echo "	<div id='asiakas_valinta_$row[tunnus]' name='selectOptions' onmouseover=\"this.className='liveSearchSelectedItem';\" onmouseout=\"this.className='liveSearchUnSelectedItem';\" onclick=\"eval(document.getElementById('tuote_toiminto_$row[tunnus]').innerHTML);\" class='unSelectedItem' style='width: 500px;'>
							<table width = '100%' class='liveSearch'>
								<tr>
									<th width='100%' class='liveSearch' colspan='3'>
										$row[tuoteno] $row[nimitys]
									</th>
								</tr>
								<tr>
									<td width='15%' class='liveSearch'>
										".t("Osasto")."
									</td>
									<td width='25%' class='liveSearch'>
										$row[osasto]
									</td>
									<td width='60%' class='liveSearch' rowspan='3'>
										<font class='info'>Kuvaus:</font><br>
										<em>$row[kuvaus]</em>
									</td>
								</tr>
								<tr>
								<td width='15%' class='liveSearch'>
									".t("Yksikkö")."
								</td>
									<td width='25%' class='liveSearch'>
										$row[yksikko]
									</td>
								</tr>
								<tr>
									<td width='15%' class='liveSearch'>
										".t("Try")."
									</td>
									<td width='25%' class='liveSearch'>
										$row[try]
									</td>
								</tr>
							</table>
							</div>
							<div id='tuote_toiminto_$row[tunnus]' name='selectValues' style='display: none;'>document.getElementById('$liitosID').value='$row[tuoteno]'; document.getElementById('$hakuID').value='$row[tuoteno] $row[nimitys]'; document.getElementById('$hakuID').disabled='true'; tuoval=document.getElementById('tuotehaku'); tuoval.innerHTML=''; tuoval.style.display='none';</div>";
				}
			}
		}
		else {
			echo "<font class='info'>".t("Anna vähintään 3 merkkiä.")."</font>";
		}
	}
}

if (!function_exists("nayta_tuotehaku")) {
	function nayta_tuotehaku($tuoteno, $formi) {
		global $kukarow;

		$query = "	SELECT *
					FROM tuote
					WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 1) {
			$tuoterow = mysql_fetch_assoc($result);

			$disabled 	= "DISABLED";
			$nimi 		= $tuoterow["tuoteno"]." ".$tuoterow["nimitys"];

		}
		else {
			$tuoteno	= "";
			$disabled 	= "";
			$nimi 		= "";
			$id			= "";
		}

		$liitosID = "liitosID".uniqid();
		$hakuID = "haku".uniqid();

		return "<input type='hidden' id='$liitosID' name='tuoteno' value='$tuoteno'><input type='text' id='$hakuID' value='$nimi' name='haku' size='50' onkeydown=\"return keyhandler(event);\" onkeyup=\"if ((event.keyCode < 37 || event.keyCode > 40) && event.keyCode != 13) { popUp(event, 'tuotehaku', '-6', '-30', '$_SERVER[SCRIPT_NAME]?tee=TUOTEHAKU&formi=$formi&url=$_SERVER[SCRIPT_NAME]&liitosID=$liitosID&hakuID=$hakuID&haku='+this.value, 'liveSearch'); keyStrokeIndex = -1; }\" $disabled autocomplete='off'>&nbsp;&nbsp;<a href='#' onclick=\"document.getElementById('$hakuID').disabled=false; document.getElementById('$hakuID').value=''; return false;\" onblur=\"document.getElementById('liveSearch').style.display='none';\">".t("Vaihda tuotetta")."</a>";
	}
}

if (!function_exists("tee_kayttajahaku")) {
	function tee_kayttajahaku($nimi, $formi, $lopetus="", $div="", $hakuID="", $liitosID="") {
		global $yhtiorow, $kukarow, $palvelin2;

		$nimi = trim($nimi);
		if (strlen($nimi) >= 3) {

			$query = "	SELECT kuka.nimi, kuka.tunnus, kuka.kuka
						FROM kuka
						WHERE yhtio = '$kukarow[yhtio]'
						AND nimi like '%$nimi%'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result)) {
				while($row = mysql_fetch_assoc($result)) {
					echo "	<div id='kayttaja_valinta_$row[tunnus]' name='selectOptions' onmouseover=\"this.className='liveSearchSelectedItem';\" onmouseout=\"this.className='liveSearchUnSelectedItem';\" onclick=\"eval(document.getElementById('kayttaja_toiminto_$row[tunnus]').innerHTML);\" class='unSelectedItem' style='width: 500px;'>
							<table width = '100%' class='liveSearch'>
								<tr>
									<th width='100%' class='liveSearch' colspan='3'>
										$row[nimi] ($row[kuka])
									</th>
								</tr>
							</table>
							</div>
							<div id='kayttaja_toiminto_$row[tunnus]' name='selectValues' style='display: none;'>document.getElementById('$hakuID').value='$row[tunnus]'; document.getElementById('$hakuID').disabled='true'; tuoval=document.getElementById('kayttajahaku'); tuoval.innerHTML=''; tuoval.style.display='none';</div>";
				}
			}
		}
		else {
			echo "<font class='info'>".t("Anna vähintään 3 merkkiä.")."</font>";
		}
	}
}

if (!function_exists("nayta_kayttajahaku")) {
	function nayta_kayttajahaku($tunnus, $formi, $vars="") {
		global $kukarow;

		$query = "	SELECT *
					FROM kuka
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$kayttajarow = mysql_fetch_assoc($result);

			$disabled 	= "DISABLED";
			$nimi 		= "$kayttajarow[tunnus]";

		}
		else {
			$disabled 	= "";
			$nimi 		= "";
			$id			= "";
		}

		return "<input type='text' id='haku' value='$nimi' name='haku' size='50' onkeydown=\"return keyhandler(event);\" onkeyup=\"if ((event.keyCode < 37 || event.keyCode > 40) && event.keyCode != 13) { popUp(event, 'kayttajahaku', '-6', '-30', '$_SERVER[SCRIPT_NAME]?tee=KAYTTAJAHAKU&formi=$formi&url=$_SERVER[SCRIPT_NAME]&haku='+this.value, 'liveSearch'); keyStrokeIndex = -1; }\" $disabled autocomplete='off'>&nbsp;&nbsp;<a href='#' onclick=\"document.getElementById('haku').disabled=false; document.getElementById('haku').value=''; return false;\" onblur=\"document.getElementById('liveSearch').style.display='none';\">".t("Vaihda käyttäjää")."</a>
			<script LANGUAGE='JavaScript'>window.document.$formi.haku.focus();</script>";
	}
}

if (!function_exists("livesearch_tilihaku")) {
	function livesearch_tilihaku() {
		global $yhtiorow, $kukarow, $palvelin2;

		$tee = $_REQUEST["livesearch_tee"];
		$nimi = $_REQUEST["livesearch_nimi"];
		$formi = $_REQUEST["livesearch_form"];
		$hakuid = $_REQUEST["livesearch_hakuid"];
		$submit = $_REQUEST["livesearch_submit"];
		$haku = trim($_REQUEST["livesearch_haku"]);

		if (strlen($haku) > 2) {

			echo "
			<script TYPE='text/javascript' LANGUAGE='JavaScript'>
				document.getElementById('livesearch_$hakuid').style.visibility = 'visible';
			</script>";

			$haku_array = explode(" ", $haku);
			$haku_query = "";

			foreach ($haku_array as $haku) {
				$haku = mysql_real_escape_string($haku);
				$haku_query .= " and (tili.tilino like ('%$haku%') or tili.nimi like ('%$haku%')) ";
			}

			$query = "	SELECT tili.tilino, tili.nimi, tili.tunnus
						FROM tili
						WHERE tili.yhtio = '$kukarow[yhtio]'
						$haku_query";
			$result = mysql_query($query) or pupe_error($query);

			$limit = 0;
			if (mysql_num_rows($result) > 0) {
				while ($row = mysql_fetch_assoc($result)) {
					echo "	<div id='tuote_valinta_$hakuid$row[tunnus]'
								name='selectOptions$hakuid'
								onmouseover=\"this.className='liveSearchSelectedItem';
									keyStrokeIndex = -1;
									var aEls = document.getElementsByName('selectOptions$hakuid');
									for (var iEl = 0; iEl < aEls.length; iEl++) {
										if (aEls[iEl].id.substring(0,14) == 'tuote_valinta_$hakuid' && aEls[iEl].id != 'tuote_valinta_$hakuid$row[tunnus]') {
											document.getElementById(aEls[iEl].id).className='';
										}
									} \"
								onmouseout=\"this.className='';\"
								onclick=\"document.getElementById('$hakuid').value='$row[tilino]';";
								if ($submit == "") {
									echo "document.$formi.submit();";
								}
								else {
									echo "document.getElementById('livesearch_$hakuid').style.visibility = 'hidden';";
								}
								echo "\"
								style='padding:2px;'>
								<font style='font-weight:bold'>$row[tilino]</font> &raquo; $row[nimi]
							</div><div name='selectValues$hakuid' id='$row[tilino]'></div>";
					$limit++;
					if ($limit > 14) {
						break;
					}
				}

				echo "<div style='padding-left:2px;padding-bottom:5px;padding-top:5px;'><font class='info'>Haulla löytyi ".mysql_num_rows($result). " tiliä.</font></div>";
			}
			else {
				echo "<div style='padding:2px'><font class='message'>".t("Ei tilejä.")."</font></div>";
			}
		}
		else {
			echo "
			<script TYPE='text/javascript' LANGUAGE='JavaScript'>
				document.getElementById('livesearch_$hakuid').style.visibility = 'hidden';
				document.getElementById('livesearch_$hakuid').style.innerHTML = '';
			</script>";
		}
	}
}

if (!function_exists("livesearch_tuotehaku")) {
	function livesearch_tuotehaku() {
		global $yhtiorow, $kukarow, $palvelin2;

		$tee = $_REQUEST["livesearch_tee"];
		$nimi = $_REQUEST["livesearch_nimi"];
		$formi = $_REQUEST["livesearch_form"];
		$hakuid = $_REQUEST["livesearch_hakuid"];
		$submit = $_REQUEST["livesearch_submit"];
		$haku = trim($_REQUEST["livesearch_haku"]);

		if (strlen($haku) > 2 and $haku{0} != "*" and substr($haku, -1) != '*' and $haku{0} != "?" and $haku{0} != "#") {

			echo "
			<script TYPE='text/javascript' LANGUAGE='JavaScript'>
				document.getElementById('livesearch_$hakuid').style.visibility = 'visible';
			</script>";

			$haku_array = explode(" ", $haku);
			$haku_query = "";

			foreach ($haku_array as $haku) {
				$haku = mysql_real_escape_string($haku);
				$haku_query .= " and (tuote.tuoteno like ('%$haku%') or tuote.nimitys like ('%$haku%')) ";
			}

			$query = "	SELECT tuote.tunnus, tuote.tuoteno, tuote.nimitys, tuote.kuvaus, tuote.osasto, tuote.try
						FROM tuote
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.status != 'P'
						$haku_query";
			$result = mysql_query($query) or pupe_error($query);

			$limit = 0;
			if (mysql_num_rows($result) > 0) {
				while ($row = mysql_fetch_assoc($result)) {
					echo "	<div id='tuote_valinta_$row[tunnus]'
								name='selectOptions$hakuid'
								onmouseover=\"this.className='liveSearchSelectedItem';
									keyStrokeIndex = -1;
									var aEls = document.getElementsByName('selectOptions$hakuid');
									for (var iEl = 0; iEl < aEls.length; iEl++) {
										if (aEls[iEl].id.substring(0,14) == 'tuote_valinta_' && aEls[iEl].id != 'tuote_valinta_$row[tunnus]') {
											document.getElementById(aEls[iEl].id).className='';
										}
									} \"
								onmouseout=\"this.className='';\"
								onclick=\"document.getElementById('$hakuid').value='$row[tuoteno]';";
								if ($submit == "") {
									echo "document.$formi.submit();";
								}
								else {
									echo "document.getElementById('livesearch_$hakuid').style.visibility = 'hidden';";
								}
								echo "\"
								style='padding:2px;'>
								<font style='font-weight:bold'>$row[tuoteno]</font> &raquo; $row[nimitys]
							</div><div name='selectValues$hakuid' id='$row[tuoteno]'></div>";
					$limit++;
					if ($limit > 14) {
						break;
					}
				}

				echo "<div style='padding-left:2px;padding-bottom:5px;padding-top:5px;'><font class='info'>Haulla löytyi ".mysql_num_rows($result). " tuotetta.</font></div>";
			}
			else {
				echo "<div style='padding:2px'><font class='message'>".t("Ei tuotteita.")."</font></div>";
			}
		}
		else {
			echo "
			<script TYPE='text/javascript' LANGUAGE='JavaScript'>
				document.getElementById('livesearch_$hakuid').style.visibility = 'hidden';
			</script>";
		}
	}
}

if (!function_exists("livesearch_kayttajahaku")) {
	function livesearch_kayttajahaku($toim = '') {
		global $yhtiorow, $kukarow, $palvelin2;

		$tee = $_REQUEST["livesearch_tee"];
		$nimi = $_REQUEST["livesearch_nimi"];
		$formi = $_REQUEST["livesearch_form"];
		$hakuid = $_REQUEST["livesearch_hakuid"];
		$submit = $_REQUEST["livesearch_submit"];
		$haku = trim($_REQUEST["livesearch_haku"]);

		if (strlen($haku) > 2 and $haku{0} != "*" and substr($haku, -1) != '*' and $haku{0} != "?" and $haku{0} != "#") {

			echo "
			<script TYPE='text/javascript' LANGUAGE='JavaScript'>
				document.getElementById('livesearch_$hakuid').style.visibility = 'visible';
			</script>";

			$haku_array = explode(" ", $haku);
			$haku_query = "";
			$haku_join = '';

			if ($toim == 'extranet') {
				$haku_query .= " and kuka.extranet != '' ";
			}
			else {
				$haku_query .= " and kuka.extranet = '' ";
			}

			foreach ($haku_array as $haku) {
				$haku = mysql_real_escape_string($haku);
				if ($toim == 'extranet') {
					$haku_query .= " and (asiakas.asiakasnro like ('%$haku%') or asiakas.ytunnus like ('%$haku%') or kuka.nimi like ('%$haku%') or kuka.kuka like ('%$haku%')) ";
				}
				else {
					$haku_query .= " and (kuka.nimi like ('%$haku%') or kuka.kuka like ('%$haku%')) ";
				}
			}

			if ($toim == 'extranet') {
				$haku_join = " LEFT JOIN asiakas ON (asiakas.yhtio = kuka.yhtio AND asiakas.tunnus = kuka.oletus_asiakas) ";
			}

			$query = "	SELECT kuka.tunnus, kuka.nimi, kuka.kuka
						FROM kuka
						$haku_join
						WHERE kuka.yhtio = '$kukarow[yhtio]'
						$haku_query";
			$result = mysql_query($query) or pupe_error($query);

			$limit = 0;
			if (mysql_num_rows($result) > 0) {
				while ($row = mysql_fetch_assoc($result)) {
					echo "	<div id='kayttaja_valinta_$row[tunnus]'
								name='selectOptions$hakuid'
								onmouseover=\"this.className='liveSearchSelectedItem';
									keyStrokeIndex = -1;
									var aEls = document.getElementsByName('selectOptions$hakuid');
									for (var iEl = 0; iEl < aEls.length; iEl++) {
										if (aEls[iEl].id.substring(0,14) == 'kayttaja_valinta_' && aEls[iEl].id != 'kayttaja_valinta_$row[tunnus]') {
											document.getElementById(aEls[iEl].id).className='';
										}
									} \"
								onmouseout=\"this.className='';\"
								onclick=\"document.getElementById('$hakuid').value='$row[tunnus]';";
								if ($submit == "") {
									echo "document.$formi.submit();";
								}
								else {
									echo "document.getElementById('livesearch_$hakuid').style.visibility = 'hidden';";
								}
								echo "\"
								style='padding:2px;'>
								<font style='font-weight:bold'>$row[nimi]</font> &raquo; ($row[kuka])
							</div><div name='selectValues$hakuid' id='$row[tunnus]'></div>";
					$limit++;
					if ($limit > 14) {
						break;
					}
				}

				echo "<div style='padding-left:2px;padding-bottom:5px;padding-top:5px;'><font class='info'>",t("Haulla löytyi")," ".mysql_num_rows($result). " ",t("käyttäjää"),".</font></div>";
			}
			else {
				echo "<div style='padding:2px'><font class='message'>".t("Ei käyttäjiä.")."</font></div>";
			}
		}
		else {
			echo "
			<script TYPE='text/javascript' LANGUAGE='JavaScript'>
				document.getElementById('livesearch_$hakuid').style.visibility = 'hidden';
			</script>";
		}
	}
}

if (!function_exists("livesearch_kentta")) {
	function livesearch_kentta($formi, $tee = 'TUOTEHAKU', $nimi = 'liveseach_hakukentta', $width = '300', $value = '', $submit = '') {
		global $kukarow, $toim;

		if (strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), "MSIE") !== FALSE or strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), "EXPLORER") !== FALSE) {
			echo "<input type='text' id='$hakuID' name='$nimi' autocomplete='off' style='width:".$width."px;' value='$value'>";
			return;
		}

		$hakuID = "haku".uniqid();

		echo "<input type='text' id='$hakuID' name='$nimi' autocomplete='off' style='width:".$width."px;' value='$value'
					onkeydown=\"return livesearch_keyhandler(event, '$hakuID', '$formi', '$submit');\"
					onkeyup=\"if ((event.keyCode < 37 || event.keyCode > 40) && event.keyCode != 13) {
							sndReq('livesearch_$hakuID', '$_SERVER[SCRIPT_NAME]?livesearch_tee=$tee&livesearch_form=$formi&livesearch_hakuid=$hakuID&livesearch_nimi=$nimi&livesearch_submit=$submit&ohje=off&toim=$toim&livesearch_haku='+this.value);
							keyStrokeIndex = -1;
					}\"
					onblur=\"var selectOptions = document.getElementsByName('selectOptions$hakuID');
					for (var iEl = 0; iEl < selectOptions.length; iEl++) {
						if (document.getElementById(selectOptions[iEl].id).className != '') {
							return false;
						}
					}
					document.getElementById('livesearch_$hakuID').style.visibility = 'hidden';\">
				<div style='break:all'></div>
				<div id='livesearch_$hakuID' class='liveSearch' name='livesearch_$hakuID' style='width:".($width * 2)."px;'></div>";
	}
}

if (!function_exists("sendSMS")) {
	function sendSMS($smsnumero, $smsviesti, $animi = "") {
		global $sms_palvelin, $sms_user, $sms_pass;

		if ($sms_palvelin != "" and $sms_user != "" and $sms_pass != "" and $smsnumero != "" and $smsviesti != "") {
			$smsviesti = urlencode($smsviesti);

			$retval = file_get_contents("$sms_palvelin?user=$sms_user&pass=$sms_pass&numero=$smsnumero&viesti=$smsviesti");
			$smsviesti = urldecode($smsviesti);

			if (trim($retval) == "0") {
				if ($animi != "") {
					$animi = "($animi)";
				}
				echo "<font class='info'>SMS-viesti lähetetty numeroon $smsnumero $animi</font><br>";
			}
		}
	}
}

if (!function_exists("on_puhelinnumero")) {
	function on_puhelinnumero($numero) {

		//	Stripataan vähän turhia merkkejä
		$checkno = preg_replace("/[\r\n\s\t\(\)\{\}\-]/", "", $numero);

		//	Jos meille jäi vain numeroita se on varmaan aika oikein
		if (preg_replace("/[+0-9]/", "", $checkno) == "" and strlen($checkno)>0) {
			return $checkno;
		}
		else {
			return false;
		}
	}
}

if (!function_exists("hae_rahtisopimusnumero")) {
	function hae_rahtisopimusnumero ($toimitustapa, $ytunnus, $asiakastunnus = "") {
		global $kukarow;

		//etsitään löytyykö rahtisopimusta
		$query = "	(SELECT *, '1' prio
					FROM rahtisopimukset
					WHERE toimitustapa = '$toimitustapa'
					AND asiakas = '$asiakastunnus'
					AND asiakas != ''
					AND yhtio = '$kukarow[yhtio]')

					UNION

					(SELECT *, '2' prio
					FROM rahtisopimukset as rahtisopimukset2
					WHERE toimitustapa = '$toimitustapa'
					AND ytunnus = '$ytunnus'
					AND ytunnus != ''
					AND yhtio = '$kukarow[yhtio]')

					ORDER BY prio
					LIMIT 1";
		$rares = mysql_query($query) or pupe_error($query);
		$rarow = mysql_fetch_assoc($rares);

 		return $rarow;
	}
}

if (!function_exists("hae_rahtimaksu")) {
	// otetaan sisään halutut otsikot mysql muodossa (1,2,3,4)
	function hae_rahtimaksu ($otsikot) {

		global $kukarow, $yhtiorow;

		$otsikot = mysql_real_escape_string(trim($otsikot));

		if ($otsikot == "") {
			return 0;
		}

		// haetaan ensimmäisen otsikon tiedot
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus IN ($otsikot)
					ORDER BY tunnus
					LIMIT 1";
		$otsre = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_assoc($otsre);

		$kyht = 0;

		// jos JT-tilaukset annetaan aina rahtivapaasti
		if ($laskurow["clearing"] == "JT-TILAUS" and $yhtiorow["jt_rahti"] == "B") {
			return 0;
		}

		// jos laskulla on rahtivapaa täppä niin annetaan ilmaseks (paitsi jos kyseessä on JT ja niille halutaan väkisin rahti optiot C tai D)
		if ($laskurow["rahtivapaa"] != "" AND ($laskurow["clearing"] != "JT-TILAUS" OR $yhtiorow["jt_rahti"] != "C") AND ($laskurow["clearing"] != "JT-TILAUS" OR $yhtiorow["jt_rahti"] != "D")) {
			return 0;
		}

		// jos meillä on automaattinen tuoteella tallennettuihin painoihin perustuva hinnoittelu
		if ($yhtiorow["rahti_hinnoittelu"] == 'P') {
			$query  = "	SELECT sum(tuotemassa * (varattu + kpl + jt)) massa
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
							AND tuote.tuoteno = tilausrivi.tuoteno
							AND tuote.ei_saldoa = '')
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						AND tilausrivi.tyyppi in ('L', 'E')
						AND tilausrivi.otunnus in ($otsikot)";
			$painoresult = mysql_query($query) or pupe_error($query);
			$painorow = mysql_fetch_assoc($painoresult);
			$kyht = $painorow['massa'];
		}
		// meillä on tilauksen hintaan perustuva hinnoittelu
		elseif ($yhtiorow["rahti_hinnoittelu"] == "o") {
			// alvittomat hinnat
			if ($yhtiorow["alv_kasittely"] == '') {
				$query = "	SELECT round(((varattu + kpl + jt) * hinta * (1 - (ale / 100))) / (1 + alv / 100), '$yhtiorow[hintapyoristys]') rivihinta, netto
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							AND tyyppi in ('L', 'E')
							AND otunnus in ($otsikot)";
			}
			else {
				$query = "	SELECT round(((varattu + kpl + jt) * hinta * (1 - (ale / 100))), '$yhtiorow[hintapyoristys]') rivihinta, netto
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							AND tyyppi in ('L', 'E')
							AND otunnus in ($otsikot)";
			}
			$presult = mysql_query($query) or pupe_error($query);

			$nyht = 0;
			$myht = 0;
			$kyht = 0;

			while ($prow = mysql_fetch_assoc($presult)) {
				if ($prow["netto"] != 'N') {
					$myht += $prow["rivihinta"]; // lasketaan tilauksen loppusummaa MUUT RIVIT..
				}
				else {
					$nyht += $prow["rivihinta"]; // lasketaan tilauksen loppusummaa NETTORIVIT..
				}
			}

			//erikoisalennus lasketaan vain riveille joilla EI ole NETTOHINTAA
			if ($laskurow['erikoisale'] != 0) {
				$apu1 = round($laskurow['erikoisale']/100,2);	// erikoisale prosentti
				$apu2 = round($myht*$apu1,2); 					// erikoisalen määrä
				$apu3 = round((1-$apu1)*$myht,2);				// loppusumma

				//Kaikki yhteensä
				$kyht = $apu3 + $nyht;
			}
			else {
				//Kaikki yhteensä
				$kyht = $myht + $nyht;
			}

			// $kyht on KOTIVALUUTASSA, käännetään laskun valuuttaan
			if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$kyht = round(laskuval($kyht, $laskurow["vienti_kurssi"]), 2);
			}
		}
		// meillä on rahtikirjan syötössä syötettyihin painoihin perustuva hinnoittelu
		else {
			//summataan kaikki rahtikirjojen painot yhteen
			$query = "	SELECT sum(kilot) kilot
						FROM rahtikirjat
						WHERE yhtio = '$kukarow[yhtio]'
						AND otsikkonro in ($otsikot)";
			$pakre = mysql_query($query) or pupe_error($query);
			$pakka = mysql_fetch_assoc($pakre);
			$kyht = $pakka["kilot"];
		}

		// haetaan ensimmäinen (pienimmällä postinumerolla) etäisyys
		$query = "	SELECT
					etaisyydet.km, etaisyydet.postino
					FROM etaisyydet
					JOIN varastopaikat ON (varastopaikat.yhtio = etaisyydet.yhtio AND varastopaikat.tunnus = '$laskurow[varasto]' AND varastopaikat.postino = etaisyydet.varasto_postino)
					WHERE etaisyydet.yhtio='$kukarow[yhtio]'
					AND etaisyydet.postino <= '$laskurow[toim_postino]'
					ORDER BY postino DESC
					LIMIT 1";
		$varastoresult = mysql_query($query) or pupe_error($query);

		// jos saadaan joku kilometrimäärä niin laitetaan se muuttujaan talteen
		if (mysql_num_rows($varastoresult) == 1) {
			$varastorow = mysql_fetch_assoc($varastoresult);
			$km = $varastorow['km'];
		}
		else {
			$km = 0;
		}

		//haetaan tällä rahtimaksu hinnan/painon ja/tai etäisyyden mukaan
		$query = "	SELECT *
					FROM rahtimaksut
					WHERE toimitustapa = '$laskurow[toimitustapa]'
					AND toimitustapa != ''
					AND kilotalku <= '$kyht'
					AND kilotloppu >= '$kyht'
					AND yhtio = '$kukarow[yhtio]'
					AND ((kmalku <= '$km' and kmloppu >= '$km') or (kmalku = 0 and kmloppu = 0))
					ORDER BY kmloppu DESC, kmalku DESC
					LIMIT 1";
		$rares = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($rares) == 1) {
			$hirow = mysql_fetch_assoc($rares);
			return $hirow["rahtihinta"];
		}
		else {
			return 0;
		}

	}

}

if (!function_exists("pakkaamo")) {
	function pakkaamo ($tilausnumero, $update = '', $ei_pakkaamoa = '') {
		global $kukarow, $yhtiorow, $konsernivarasto_yhtiot;

		// Pupen varaston hierarkia:
		//
		// 1) varastopaikat aka "YRITYKSEN VARASTOT"
		// 2) varaston_tulostimet aka "VARASTON TULOSTUSALUEET" (varastopaikat.tunnus = varaston_tulostimet.varasto)
		// 3) pakkaamo aka "PAKKAAMOLOKEROT" (pakkaamo.nimi = varaston_tulostimet.pakkaamo)
		//
		// tiedot tallennetaan laskulle:
		// lasku.varasto = varasto.tunnus
		// lasku.tulostusalue = varaston_tulostimet.nimi
		// lasku.pakkaamo = pakkaamo.tunnus

		if ($yhtiorow['pakkaamolokerot'] == '') {
			return 0;
		}

		// Tässä nollataan tilausten pakkaamolokerot
		if ($ei_pakkaamoa != '') {
			$query = "	UPDATE lasku SET pakkaamo = 0
						WHERE yhtio = '$kukarow[yhtio]'
						AND ((tila in ('N','L') and alatila = 'A') or (tila = 'L' and alatila = 'C') or (tila = 'G' and alatila in ('J','A','C')))
						AND tunnus in ($tilausnumero)";
			$ei_pakkaamoa_res = mysql_query($query) or pupe_error($query);
			return 0;
		}

		if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
			$logistiikka_yhtio = $konsernivarasto_yhtiot;
			$logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";
		}
		else {
			$logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
		}

		// Tässä haetaan ekan tilauksen tiedot
		$query = "	SELECT *
					FROM lasku
					WHERE $logistiikka_yhtiolisa
					AND ((tila in ('N', 'L') and alatila = 'A') or (tila = 'L' and alatila = 'C') or (tila = 'G' and alatila in ('J','A','C')))
					AND lasku.tunnus in ($tilausnumero)
					ORDER BY tunnus
					LIMIT 1";
		$varastotark_res = mysql_query($query) or pupe_error($query);
		$varastotark_row = mysql_fetch_assoc($varastotark_res);

		//katsotaan onko jo annettu pakkaamolokero vastaavalle SIIRTOLISTALLE, niitä saa/pitää yhdistää
		$query = " 	SELECT lasku.pakkaamo
					FROM lasku
					JOIN pakkaamo ON (pakkaamo.yhtio = lasku.yhtio and pakkaamo.tunnus = lasku.pakkaamo)
					WHERE lasku.$logistiikka_yhtiolisa
					AND lasku.ytunnus = '$varastotark_row[ytunnus]'
					AND lasku.toim_ovttunnus = '$varastotark_row[toim_ovttunnus]'
					AND lasku.toim_nimi = '$varastotark_row[toim_nimi]'
					AND lasku.toim_nimitark = '$varastotark_row[toim_nimitark]'
					AND lasku.nimi = '$varastotark_row[nimi]'
					AND lasku.nimitark = '$varastotark_row[nimitark]'
					AND lasku.toim_osoite = '$varastotark_row[toim_osoite]'
					AND lasku.toim_postitp = '$varastotark_row[toim_postitp]'
					AND lasku.toim_maa = '$varastotark_row[toim_maa]'
					AND lasku.toimitustapa = '$varastotark_row[toimitustapa]'
					AND lasku.varasto = '$varastotark_row[varasto]'
					AND lasku.pakkaamo > 0
					AND lasku.tila in ('G')
					AND lasku.alatila in ('A','C')
					ORDER BY pakkaamo.pakkaamon_prio, pakkaamo.prio
					LIMIT 1";
		$vanhat_res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($vanhat_res) > 0) {
			$split_pakkaamo_row = mysql_fetch_assoc($vanhat_res);

			if (isset($update) and $update != '') {
				$query = "	UPDATE lasku SET
							pakkaamo = '$split_pakkaamo_row[pakkaamo]'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus in ($tilausnumero)";
				$pakkaamo_insert_res = mysql_query($query) or pupe_error($query);
			}

			return $split_pakkaamo_row['pakkaamo'];
		}

		$pakkaamotark_wherelisa = '';

		if ($yhtiorow['pakkaamolokerot'] == 'K') {
			$pakkaamotark_wherelisa = ' AND varaston_tulostimet.pakkaamo = vanha_varaston_tulostimet.pakkaamo ';
		}

		// Katsotaan onko näitä tilauksia laitettu jo joku osa jonnekin lokeroon samassa pakkaamossa ja valitaan paras lokero
		$query = "	SELECT pakkaamo.tunnus
					FROM lasku
					JOIN varaston_tulostimet ON (varaston_tulostimet.yhtio = lasku.yhtio and varaston_tulostimet.nimi = lasku.tulostusalue)
					JOIN lasku vanha_lasku ON (vanha_lasku.yhtio = lasku.yhtio and vanha_lasku.vanhatunnus = lasku.vanhatunnus and vanha_lasku.tunnus not in ($tilausnumero))
					JOIN varaston_tulostimet vanha_varaston_tulostimet ON (vanha_varaston_tulostimet.yhtio = vanha_lasku.yhtio and vanha_varaston_tulostimet.nimi = vanha_lasku.tulostusalue)
					JOIN pakkaamo ON (pakkaamo.yhtio = vanha_lasku.yhtio and pakkaamo.tunnus = vanha_lasku.pakkaamo)
					WHERE lasku.$logistiikka_yhtiolisa
					AND ((lasku.tila in ('N','L') and lasku.alatila = 'A') or (lasku.tila = 'L' and lasku.alatila = 'C') or (lasku.tila = 'G' and lasku.alatila in ('J','A','C')))
					AND lasku.vanhatunnus != 0
					AND lasku.tunnus in ($tilausnumero)
					$pakkaamotark_wherelisa
					ORDER BY pakkaamo.pakkaamon_prio, pakkaamo.prio
					LIMIT 1";
		$pakkaamotark_res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($pakkaamotark_res) == 1) {
			$split_pakkaamo_row = mysql_fetch_assoc($pakkaamotark_res);

			if (isset($update) and $update != '') {
				$query = "	UPDATE lasku SET
							pakkaamo = '$split_pakkaamo_row[tunnus]'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus in ($tilausnumero)";
				$pakkaamo_insert_res = mysql_query($query) or pupe_error($query);
			}

			return $split_pakkaamo_row['tunnus'];
		}
		else {

			// listataan kaikki pakkaamot tilauksen tulostusalueelta
			$query = "	SELECT pakkaamo.lokero, pakkaamo.nimi, group_concat(distinct pakkaamo.tunnus) tunnus, group_concat(distinct pakkaamo.varasto) varasto
						FROM pakkaamo
						JOIN varaston_tulostimet ON (varaston_tulostimet.yhtio = pakkaamo.yhtio and varaston_tulostimet.nimi = '$varastotark_row[tulostusalue]')
						WHERE pakkaamo.$logistiikka_yhtiolisa
						AND pakkaamo.nimi = varaston_tulostimet.pakkaamo
						GROUP BY lokero, nimi
						ORDER BY pakkaamon_prio ASC, prio ASC";
			$etsitaan_pakkaamo_res = mysql_query($query) or pupe_error($query);

			$pienin_maara = 99999;
			$pienin_maara_tunnus = '';

			while ($etsitaan_pakkaamo_row = mysql_fetch_assoc($etsitaan_pakkaamo_res)) {
				$loytyyko_varasto = '';

				foreach (explode(',', $etsitaan_pakkaamo_row['varasto']) as $varasto) {
					// katsotaan löytyykö tilauksella oleva varasto loopattavista varastoista
					if ($varastotark_row['varasto'] == $varasto) {
						$loytyyko_varasto = 'löytyi!';
						break;
					}
				}

				if ($loytyyko_varasto != '') {

					// lasketaan montako tilausta on lokerossa
					$query = "	SELECT count(*) kpl
								FROM lasku
								WHERE lasku.$logistiikka_yhtiolisa
								AND ((lasku.tila in ('N','L') and lasku.alatila = 'A') or (lasku.tila = 'L' and lasku.alatila = 'C') or (lasku.tila = 'G' and lasku.alatila in ('J','A','C')))
								AND lasku.pakkaamo in ($etsitaan_pakkaamo_row[tunnus])";
					$paljon_laskuja_lokerossa_res = mysql_query($query) or pupe_error($query);
					$paljon_laskuja_lokerossa_row = mysql_fetch_assoc($paljon_laskuja_lokerossa_res);

					// otetaan talteen pienin kpl määrällä oleva lokero
					if ($paljon_laskuja_lokerossa_row['kpl'] < $pienin_maara) {
						$pienin_maara = (int) $paljon_laskuja_lokerossa_row['kpl'];
						$pienin_maara_tunnus = $etsitaan_pakkaamo_row['tunnus'];
					}

					// lopetetaan looppi jos löydetään tyhjä lokero
					if ($pienin_maara == 0) {
						break;
					}
				}
			}

			if ($pienin_maara < 99999) {

				// katsotaan mikä lokerotunnuksista oli käyttäjän yrityksen oma
				$query = "	SELECT tunnus
							FROM pakkaamo
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus in ($pienin_maara_tunnus)";
				$pakkaamo_insert_res = mysql_query($query) or pupe_error($query);
				$pakkaamo_insert_row = mysql_fetch_assoc($pakkaamo_insert_res);

				if (isset($update) and $update != '') {
					// päivitetään pakkaamotunnus tilauksille
					$query = "	UPDATE lasku SET
								pakkaamo = '$pakkaamo_insert_row[tunnus]'
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus in ($tilausnumero)";
					$pakkaamo_insert_res = mysql_query($query) or pupe_error($query);
				}

				return $pakkaamo_insert_row['tunnus'];
			}

			return 0;
		}
	}
}

if (!function_exists("laheta_tilausvahvistus")) {
	function laheta_tilausvahvistus($laskurow) {

		// globaaliin pitää laittaa kaikki mitä tulosta_lahete.incissäkin.. :(
		global $asrow, $boldi, $iso, $jtid, $kala, $kala, $kateinen, $kateistyyppi, $kieli, $kukarow, $kukarow, $lahetetyyppi, $laskurow, $norm, $p, $page, $page, $pdf, $perheid, $pieni, $pieni_boldi, $rectparam, $returnvalue, $rivinkorkeus, $rivinkorkeus, $rivinumerot, $row, $sivu, $summa, $tee, $toim, $useita, $viite, $yhtiorow, $yhtiorow, $futurtilvahhost, $futurtilvahuser, $futurtilvahpass, $tilvahhost, $tilvahuser, $tilvahpass, $tilvahpath, $faxhost, $faxuser, $faxpass, $faxpath;

		// LÄHETETÄÄN TILAUSVAHVISTUS
		if (strpos($laskurow['tilausvahvistus'], '4') !== FALSE) {
			$naytatvale = 4; // jos mellä on tilausvahvistuksessa nelonen, ei haluta nähdä alennuksia, näytetään tilausrivin hinta ja rivihinta
		}
		elseif (strpos($laskurow['tilausvahvistus'], '3') !== FALSE) {
			$naytatvale = 3; // jos mellä on tilausvahvistuksessa kolmonen, ei haluta nähdä hintoja, pelkästään kpl-määrät
		}
		elseif (strpos($laskurow['tilausvahvistus'], '2') !== FALSE) {
			$naytatvale = 2; // jos mellä on tilausvahvistuksessa kakkonen, ei haluta nähä aleja
		}
		else {
			$naytatvale = 1; // halutaan nähä alet
		}

		if (strpos($laskurow['tilausvahvistus'], 'E') !== FALSE) {
			require("tilausvahvistus-edi.inc");
		}

		if (strpos($laskurow['tilausvahvistus'], 'S') !== FALSE) {
			require("tilausvahvistus-email.inc");
		}

		if (strpos($laskurow['tilausvahvistus'], 'F') !== FALSE) {
			require("tilausvahvistus-fax.inc");
		}

		if (strpos($laskurow['tilausvahvistus'], 'O') !== FALSE) {
			require("tilausvahvistus-email.inc");
		}

		if (strpos($laskurow['tilausvahvistus'], 'U') !== FALSE) {
			require("tilausvahvistus-futursoft.inc");
		}

		if (strpos($laskurow['tilausvahvistus'], 'K') !== FALSE) {
			$query = "	SELECT komento
						FROM kirjoittimet
						WHERE yhtio = '$kukarow[yhtio]' and
						tunnus = '$kukarow[kirjoitin]'";
			$tilvares = mysql_query($query) or pupe_error($query);

			if ($tilvakir = mysql_fetch_assoc($tilvares)) {
				$komento["Tilausvahvistus"] = $tilvakir["komento"];

				$toim_save = $toim;
				if ($toim != "YLLAPITOSOPIMUS") $toim = "TILAUSVAHVISTUS";

				require("tulosta_tilausvahvistus_pdf.inc");
				$toim = $toim_save;
			}
		}

		if ($yhtiorow['tilausvahvistus_tallenna'] == 'K' and $tilausvahvistus_tallenna != '') {
			if (stristr(basename($tilausvahvistus_tallenna), ".pdf")) {
				$liite_tyyppi = "application/pdf";
			}
			else {
				$liite_tyyppi = "text/plain";
			}

			$file["name"] 		= basename($tilausvahvistus_tallenna);
			$file["type"] 		= $liite_tyyppi;
			$file["tmp_name"] 	= $tilausvahvistus_tallenna;
			$file["error"] 		= 0;
			$file["size"] 		= filesize($tilausvahvistus_tallenna);

			$_FILES['tilvah_liite'] = $file;

			$liitetied_id = tallenna_liite('tilvah_liite', 'lasku', $laskurow['tunnus'], t('Myyntilaskun tilausvahvistus'));

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f ".basename($tilausvahvistus_tallenna));
		}
	}
}

// Tämä funktio kääntää sanoja sanakirjasta
if (!function_exists("t")) {
	function t($stringi, $kieli = "") {
		// tarvitaan yhtiörowta, kukarowta ja tieto slaven:n käytöstä
		global $yhtiorow, $kukarow, $useslave, $link, $verkkokauppa, $kaannetyt_sanat;

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

		// Voi olla, että käytämme slavea ja INSERT ei siis onnistu (insert vain jos ei fi)
		if ($useslave > 0 and $indeksi != 'fi') {
			unset($link);
			$link = mysql_connect ($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
			mysql_select_db ($dbkanta,$link) or die ("Tietokantaa $dbkanta löydy palvelimelta (functions.inc 1)!");
		}

		if ($indeksi != 'fi') {
			$sanakirjaquery  = "SELECT fi, $indeksi, tunnus FROM sanakirja WHERE fi = BINARY '$stringi'";
			$sanakirjaresult = mysql_query($sanakirjaquery, $link) or pupe_error($sanakirjaquery);

			if (isset($kaannetyt_sanat) and !in_array($stringi, $kaannetyt_sanat)) $kaannetyt_sanat[] = $stringi;

			if (mysql_num_rows($sanakirjaresult) > 0) {
				$sanakirjarow = mysql_fetch_assoc($sanakirjaresult);

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
		elseif (isset($kaannetyt_sanat) and !in_array($stringi, $kaannetyt_sanat)) {
			$kaannetyt_sanat[] = $stringi;
		}

		if ($useslave > 0) { //Palautetaan slave käyttöön
			unset($link);
			$link = mysql_connect ($slavedb[$useslave], $slaveuser[$useslave], $slavepass[$useslave]) or die("Ongelma tietokantapalvelimessa: '$slavedb[$useslave]'");
			mysql_select_db ($dbkanta) or die ("Tietokantaa $dbkanta löydy palvelimelta (functions.inc 2)!");
		}

		//	Palautetaan muotoiltu stringi!!!
		if (func_num_args()>2) {
		    $arg = func_get_args();
			return sprintf($stringi, $arg[2],$arg[3],$arg[4],$arg[5],$arg[6]);
		}
		else {
			return $stringi;
		}
	}
}

// Tämä funktio hakee ja kääntää avainsanoja
if (!function_exists("t_avainsana")) {
	function t_avainsana($laji, $kieli = '', $where = '', $yhtio = '', $eivielakaytossa = '', $return = "") {
		global $kukarow, $yhtiorow;

		$laji = mysql_real_escape_string($laji);
		$limit = mysql_real_escape_string($limit);

		if ($kieli == "") {
			$kieli = $kukarow["kieli"];
		}
		else {
			$kieli = mysql_real_escape_string($kieli);
		}

		if ($where != "") {
			$wherelisa = $where;
		}
		else {
			$wherelisa = "";
		}

		if ($yhtio != "") {
			$yhtiolisa = " avainsana.yhtio in ($yhtio) ";
		}
		else {
			$yhtiolisa = " avainsana.yhtio = '$kukarow[yhtio]' ";
		}

		if ($return != "") {
			$kielilisa = $return;

			if ($return != "selite") {
				$fallback = "";
			}
		}
		else {
			$kielilisa = "selitetark";
		}

		if (stripos($wherelisa, "ORDER BY") === FALSE) {
			$query = "	SELECT distinct selite
						from avainsana
						where $yhtiolisa
						and laji = '$laji'
						and kieli = '$yhtiorow[kieli]'";
			$result = mysql_query($query) or pupe_error($query);

			$onko_numero = $onko_alpha = 0;

			while ($row = mysql_fetch_assoc($result)) {
				if (is_numeric($row["selite"])) {
					$onko_numero++;
				}
				else {
					$onko_alpha++;
				}
			}

			if ($onko_numero > $onko_alpha) {
				$orderlisa = " ORDER BY avainsana.jarjestys, avainsana.selite+0 ";
			}
			else {
				$orderlisa = " ORDER BY avainsana.jarjestys, avainsana.selite ";
			}
		}
		else {
			$orderlisa = "";
		}

		$query = "	SELECT DISTINCT avainsana.*,
					IFNULL((SELECT if(avainsana_kieli.$kielilisa = '', NULL, avainsana_kieli.$kielilisa)
					FROM avainsana as avainsana_kieli
					WHERE avainsana_kieli.yhtio = avainsana.yhtio
					and avainsana_kieli.laji = avainsana.laji
					and avainsana_kieli.perhe = avainsana.perhe
					and avainsana_kieli.perhe > 0
					and avainsana_kieli.kieli = '$kieli' LIMIT 1), avainsana.$kielilisa) $kielilisa
					FROM avainsana
					WHERE $yhtiolisa
					and avainsana.laji = '$laji'
					and avainsana.kieli in ('$yhtiorow[kieli]', '')
					$wherelisa
					$orderlisa";
		$result = mysql_query($query) or pupe_error($query);

		if ($return != "") {
			$row = mysql_fetch_assoc($result);
			return $row[$return];
		}

		return $result;
	}
}

// Tämä funktio kääntää tuotteen_avainsanoja.
if (!function_exists('t_tunnus_avainsanat')) {
	function t_tunnus_avainsanat($row, $laji, $avainsanalaji, $kieli = '') {

		global $kukarow, $yhtiorow;

		$laji = mysql_real_escape_string(trim($laji));
		$kieli = mysql_real_escape_string(trim($kieli));

		// Jos tyhjää niin unohdetaan koko juttu
		if (!isset($row["tunnus"]) or $laji == "") {
			return;
		}

		if ($kieli == "") $kieli = $kukarow["kieli"];
		if ($kieli == "") $kieli = $yhtiorow["kieli"];

		//	Jos kielet matchaa niin palautetaan suoraan vastaus
		if (strtoupper($kieli) == strtoupper($yhtiorow["kieli"])) {
			return $row[$laji];
		}

		// Tutkitaan onko meillä käännös
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]'
					and kieli = '$kieli'
					and laji = '$avainsanalaji'
					and selite = '$row[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$row = mysql_fetch_assoc($result);
			// jos löydettiin käännös palautetaan se
			return $row["selitetark"];
		}

		// muuten palautetaan se mitä kysyttiin
		return $row[$laji];
	}
}


// Tämä funktio kääntää tuotteen_avainsanoja.
if (!function_exists('t_tuotteen_avainsanat')) {
	function t_tuotteen_avainsanat($tuoterow, $laji, $kieli = '') {

		global $kukarow, $yhtiorow;

		$laji = mysql_real_escape_string(trim($laji));
		$kieli = mysql_real_escape_string(trim($kieli));

		// Jos tyhjää niin unohdetaan koko juttu
		if (!isset($tuoterow["tuoteno"]) or $laji == "") {
			return;
		}

		if ($kieli == "") $kieli = $kukarow["kieli"];
		if ($kieli == "") $kieli = $yhtiorow["kieli"];

		//	Jos kielet matchaa niin palautetaan suoraan vastaus
		if (strtoupper($kieli) == strtoupper($yhtiorow["kieli"])) {
			return $tuoterow[$laji];
		}

		// Tutkitaan onko meillä käännös
		$query = "	SELECT *
					FROM tuotteen_avainsanat USE INDEX (yhtio_kieli_laji_tuoteno)
					WHERE yhtio = '$kukarow[yhtio]'
					and kieli = '$kieli'
					and laji = '$laji'
					and tuoteno = '$tuoterow[tuoteno]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$row = mysql_fetch_assoc($result);
			// jos löydettiin käännös palautetaan se
			return $row["selite"];
		}

		// muuten palautetaan se mitä kysyttiin
		return $tuoterow[$laji];
	}
}

if (!function_exists("jt_toimita")) {
	function jt_toimita($toimittaja, $toimittajaid, $varastosta, $jtrivit, $automaaginen, $tee) {
		global $yhtiorow, $kukarow, $oikeurow;

		$asiakasno 			= "";
		$asiakasid			= "";
		$asiakasmaa 		= "";
		$jarj	 			= "luontiaika";
		$tuotenumero		= "";
		$tilaus				= "";
		$toimi				= "";
		$superit			= "";
		$tilaus_on_jo		= "";
		$vain_rivit 		= $jtrivit;
		$from_varastoon_inc = "varastoon.inc";

		require('jtselaus.php');
	}
}

if (!function_exists("hae_yhtion_parametrit")) {
	function hae_yhtion_parametrit($yhtio) {
		global $kukarow, $konserni_yhtiot, $konsernivarasto_yhtiot;

		$query = "	SELECT *
					FROM yhtio
					WHERE yhtio='$yhtio'";
		$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

		if (mysql_num_rows($result) == 0) {
			echo "<b>Käyttäjän yritys ei löydy! ($yhtio)";
			exit;
		}
		$yhtiorow = mysql_fetch_assoc($result);

		$konserni_yhtiot = "";
		$konsernivarasto_yhtiot = "";

		if ($yhtiorow['konserni'] != '') {
			$query = "	SELECT distinct yhtio.yhtio, konsernivarasto
						FROM yhtio
						JOIN yhtion_parametrit ON (yhtion_parametrit.yhtio = yhtio.yhtio)
						WHERE (yhtio.konserni = '$yhtiorow[konserni]' and yhtio.konserni != '') or (yhtio.yhtio = '$yhtiorow[yhtio]')";
			$result = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_assoc($result)) {
				$konserni_yhtiot .= " '".$row["yhtio"]."' ,";
				if ($row["konsernivarasto"] != "") {
					$konsernivarasto_yhtiot .= " '".$row["yhtio"]."' ,";
				}
			}
			$konserni_yhtiot = substr($konserni_yhtiot, 0, -1);
			$konsernivarasto_yhtiot = substr($konsernivarasto_yhtiot, 0, -1);
		}
		else {
			$konserni_yhtiot = "'".$yhtio."'";
		}

		$query = "	SELECT *
					FROM yhtion_parametrit
					WHERE yhtio='$yhtio'";
		$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

		if (mysql_num_rows($result) == 1) {
			$yhtion_parametritrow = mysql_fetch_assoc($result);

			if ($yhtion_parametritrow['hintapyoristys'] != 2 and $yhtion_parametritrow['hintapyoristys'] != 4 and $yhtion_parametritrow['hintapyoristys'] != 6) {
				$yhtion_parametritrow['hintapyoristys'] = 2;
			}

			// lisätään kaikki yhtiorow arrayseen
			foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
				$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
			}
		}

		if (isset($kukarow["toimipaikka"]) and $kukarow["toimipaikka"] != 0) {
			$query = "	SELECT *
						FROM yhtion_toimipaikat
						WHERE yhtio='$yhtio' and tunnus='$kukarow[toimipaikka]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_toimipaikkarow = mysql_fetch_assoc($result);

				// lisätään kaikki yhtiorow arrayseen
				foreach ($yhtion_toimipaikkarow as $parametrit_nimi => $parametrit_arvo) {
					$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
				}
		 	}
		}

		return $yhtiorow;
	}
}

if (!function_exists("liite_popup")) {
	function liite_popup($toiminto, $tuotetunnus = 0, $width=0, $height=0) {
		global $kukarow, $yhtiorow, $PHP_SELF, $palvelin2, $pupe_root_polku;

		if ($toiminto == "JS") {
			echo "<SCRIPT type='text/javascript'>
					<!--
						function liite_popup_js(tuotetunnus, maxwidth, totalheight) {
							var myWidth = 0, myHeight = 0;

							if (typeof(window.innerWidth ) == 'number') {
								//Non-IE
								myWidth = window.innerWidth;
								myHeight = window.innerHeight;
							} else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
								//IE 6+ in 'standards compliant mode'
								myWidth = document.documentElement.clientWidth;
								myHeight = document.documentElement.clientHeight;
							} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
								//IE 4 compatible
								myWidth = document.body.clientWidth;
								myHeight = document.body.clientHeight;
							}

							if (maxwidth == '0' && totalheight == '0') {
								window.open('$PHP_SELF?ohje=off&liite_popup_toiminto=AK&tuotetunnus='+tuotetunnus, '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=25,top=25, width='+myWidth+', height='+myHeight);
							}
							else {
								window.open('$PHP_SELF?ohje=off&liite_popup_toiminto=AK&tuotetunnus='+tuotetunnus+'&width='+maxwidth, '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=25,top=25, width='+maxwidth+', height='+totalheight);
							}
						}
					//-->
					</SCRIPT>";
		}
		elseif ($toiminto == "AK") {

			if ($width > 0) {
				$maxi = " width='".($width-30)."' ";
			}

			$query = "	SELECT
						tunnus,
						selite,
						kieli,
						filename,
						filesize,
						filetype,
						image_width,
						image_height,
						kayttotarkoitus,
						if(kayttotarkoitus='TK', 0, if(kayttotarkoitus='MU', 1, 2)) sorttaus,
						jarjestys
						FROM liitetiedostot
						WHERE yhtio 		= '$kukarow[yhtio]'
						AND liitos 			= 'tuote'
						AND liitostunnus	= '$tuotetunnus'
						AND kayttotarkoitus != 'TH'
						ORDER BY sorttaus, jarjestys, filename";
			$kuvares = mysql_query($query) or pupe_error($query);

			echo "<table $maxi>";

			while ($kuvarow = mysql_fetch_assoc($kuvares)) {
				echo "<tr><th style='text-align: center;'>$kuvarow[selite]</th></tr>";
				echo "<tr><td align='center'>";

				if ($kuvarow["kayttotarkoitus"] == "TK" and in_array($kuvarow["filetype"], array("image/jpeg","image/jpg","image/gif","image/png","image/bmp"))) {

					echo "<img $maxi src='".$palvelin2."view.php?id=$kuvarow[tunnus]'>";

					if ($width > 0 and $width < $kuvarow["image_width"]) {
						echo "<br><a href='".$palvelin2."view.php?id=$kuvarow[tunnus]' target='$kuvarow[tunnus]'>".t("Näytä täysikokoinen kuva")."</a>";
					}
				}
				else {
					list($liitedata1, $liitedata2) = explode("/", $kuvarow["filetype"]);

					$path_parts = pathinfo($kuvarow["filename"]);
					$ext = $path_parts['extension'];

					if (file_exists($pupe_root_polku."/pics/tiedostotyyppiikonit/".strtoupper($liitedata2).".ico")) {
						echo "<a href='".$palvelin2."view.php?id=$kuvarow[tunnus]' target='$kuvarow[tunnus]'><img src='".$palvelin2."pics/tiedostotyyppiikonit/".strtoupper($liitedata2).".ico' height='80px'></a>";
					}
					elseif (file_exists("pics/tiedostotyyppiikonit/".strtoupper($ext).".ico")) {
						echo "<a href='".$palvelin2."view.php?id=$kuvarow[tunnus]' target='$kuvarow[tunnus]'><img src='".$palvelin2."pics/tiedostotyyppiikonit/".strtoupper($ext).".ico' height='80px'></a>";
					}
					echo "<br><a href='".$palvelin2."view.php?id=$kuvarow[tunnus]' target='$kuvarow[tunnus]'>".t("Avaa tiedosto")."</a>";
				}

				if ($kuvarow["kieli"] != "" and strtoupper($kuvarow["kieli"]) != strtoupper($yhtiorow["kieli"])) {
					echo "<br><font class='info'>".t("Kieli").": ".strtoupper($kuvarow["kieli"])."</font>";
				}

				echo "</td></tr>";
			}
			echo "</table>";

			echo "<br><center><input type='button' value='".t("Sulje ikkuna")."' onClick=\"javascript:window.close();\"></center>";

			exit;
		}
		elseif ($toiminto == "TN") {

			unset($images_exist);
			unset($pdf_exist);
			unset($filetype);

			$filetype_query = "	SELECT
								yhtio,
								selite,
								kieli,
								filename,
								filesize,
								filetype,
								image_width,
								image_height,
								kayttotarkoitus,
								if(kayttotarkoitus='TK', 0, if(kayttotarkoitus='MU', 1, 2)) sorttaus,
								jarjestys
								FROM liitetiedostot
								WHERE yhtio		 = '$kukarow[yhtio]'
								and liitos		 = 'tuote'
								and liitostunnus = '$tuotetunnus'
								AND kayttotarkoitus != 'TH'
								ORDER BY sorttaus, jarjestys, filename";
			$filetype_result = mysql_query($filetype_query) or pupe_error($filetype_query);

			if (mysql_num_rows($filetype_result) > 0) {

				$filetype_row = mysql_fetch_assoc($filetype_result);

				if (in_array($filetype_row["filetype"], array("image/jpeg","image/jpg","image/gif","image/png","image/bmp"))) {

					$maxwidth = $filetype_row["image_width"];
					$totalheight = $filetype_row["image_height"];

					if ($maxwidth > 640) {

						$kerroin = 640/$maxwidth;

						$maxwidth = $kerroin*$maxwidth;
						$totalheight = $kerroin*$totalheight;
					}

					$maxwidth += 30;
					$totalheight += 100;

					$images_exist = 1;
				}
				elseif ($filetype_row["filetype"] == "application/pdf") {
					$maxwidth 		= 0;
					$totalheight 	= 0;
					$pdf_exist 		= 1;
				}
			}

			if (isset($images_exist) or isset($pdf_exist)) {

				$liitteet = "<input type='button' value='";

				if ($pdf_exist) {
					$liitteet .= t("Pdf");
				}
				else {
					$liitteet .= t("Kuva");
				}
				$liitteet .= "' onClick=\"javascript:liite_popup_js($tuotetunnus, $maxwidth, $totalheight)\">";

				return $liitteet;
			}
		}
	}
}

?>