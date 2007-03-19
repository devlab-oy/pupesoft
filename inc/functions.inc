<?php

// näin kuollaan mysql errorista...
if (!function_exists("pupe_error")) {
	// otetaan parametriksi query
	function pupe_error($query) {
		// tarvitaan yhtiörowta ja kukarowta
		global $yhtiorow, $kukarow;

		// trimmataan, tabit, rivinvaihdot ja tuplaspacet pois querystä..
		$query = trim($query);
		$query = str_replace("\t", " ",$query);
		$query = str_replace("\n", "", $query);
		$query = str_replace("\r", "", $query);
		$query = ereg_replace("  +", " ", $query);

		// tehdään errorimessage
		$puperror = "File: $_SERVER[PHP_SELF]\nUser: $kukarow[kuka]\n\n$query\n\n".mysql_error();

		// lähetetään se meilitse adminille
		mail($yhtiorow['admin_email'], $yhtiorow['nimi']." - SQL Error", $puperror, "From: $yhtiorow[postittaja_email]\r\n", "-f $yhtiorow[postittaja_email]");

		// kuollaan pois
		exit("Kysely ei onnistunut tiedostossa $_SERVER[PHP_SELF].<br><br>$query<br><br>".mysql_error());
	}
}

if (!function_exists("js_popup")) {
	// scripti balloonien tekemiseen
	function js_popup ($vasen_offset) {

		if ($vasen_offset == '' or !is_numeric($vasen_offset)) {
			$vasen_offset = 0;
		}

		return "<script>
				var DH = 0;
				var an = 0;
				var al = 0;
				var ai = 0;

				if (document.getElementById) {
					ai = 1; DH = 1;
				}
				else {
					if (document.all) {
						al = 1; DH = 1;
					}
					else {
						browserVersion = parseInt(navigator.appVersion);

						if ((navigator.appName.indexOf('Netscape') != -1) && (browserVersion == 4)) {
							an = 1; DH = 1;
						}
					}
				}

				function fd(oi, wS) {
					if (ai) return wS ? document.getElementById(oi).style:document.getElementById(oi);
					if (al) return wS ? document.all[oi].style: document.all[oi]; if (an) return document.layers[oi];
				}

				function pw() {
					return window.innerWidth != null? window.innerWidth: document.body.clientWidth != null? document.body.clientWidth:null;
				}

				function mouseX(evt) {
					if (evt.pageX) return evt.pageX;
					else if (evt.clientX)return evt.clientX + (document.documentElement.scrollLeft ?  document.documentElement.scrollLeft : document.body.scrollLeft);
					else return null;
				}

				function mouseY(evt) {
					if (evt.pageY) return evt.pageY;
					else if (evt.clientY)return evt.clientY + (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop);
					else return null;
				}

				function popUp(evt,oi) {
					if (DH) {
						var wp = pw();
						ds = fd(oi,1);
						dm = fd(oi,0);
						st = ds.visibility;

						if (dm.offsetWidth) ew = dm.offsetWidth;
						else if (dm.clip.width) ew = dm.clip.width;

						if (st == \"visible\" || st == \"show\") {
							ds.visibility = \"hidden\";
						}
						else {
							tv = mouseY(evt) + 20;
							lv = mouseX(evt) - ew - $vasen_offset;

							if (lv < 2) lv = 2;
							else if (lv + ew > wp) lv -= ew/2;

							if (!an) {
								lv += 'px';
								tv += 'px';
							}

							ds.left = lv;
							ds.top = tv;
							ds.visibility = \"visible\";
						}
					}
				}
				</script>";
	}
}

if (!function_exists("tv1dateconv")) {
	function tv1dateconv($date) {
		//kääntää mysqln vvvv-kk-pp muodon muotoon pp.kk.vvvv
		return substr($date,8,2).".".substr($date,5,2).".".substr($date,0,4);
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
			$sanakirjaquery  = "SELECT fi, $indeksi FROM sanakirja WHERE fi = BINARY '$stringi'";
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
				//$sanakirjaqueryupd  = "UPDATE sanakirja SET aikaleima=now(), kysytty=kysytty+1 WHERE tunnus='$sanakirjarow[tunnus]'";
				//$sanakirjaresultupd = mysql_query($sanakirjaqueryupd, $link) or pupe_error($sanakirjaqueryupd);
			}
			else {
				$sanakirjaquery  = "INSERT INTO sanakirja SET fi = '$stringi', aikaleima=now(), kysytty=1";
				$sanakirjaresult = mysql_query($sanakirjaquery, $link) or pupe_error($sanakirjaquery);
			}
		}
		
		if ($useslave > 0) { //Palautetaan slave käyttöön
			$link = mysql_connect ($slavedb[$useslave], $slaveuser[$useslave], $slavepass[$useslave]) or die("Ongelma tietokantapalvelimessa: '$slavedb[$useslave]'");
			mysql_select_db ($dbkanta) or die ("Tietokantaa $dbkanta löydy palvelimelta (functions.inc 2)!");
		}

		return $stringi;
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

if (!function_exists("mm_pt")) {
	function mm_pt($millimetreja) {
		$pointseja = round($millimetreja / 0.3527777778,2);
		return $pointseja;
	}
}

if (!function_exists("saldo_myytavissa")) {
	function saldo_myytavissa($tuoteno, $tyyppi = '', $varasto = 0, $yhtio = '') {

		// tämä funktio palauttaa myytävissä olevan saldon sallituista varastoista (tyyppi='')
		// laskuttamattomia hyvityksiä ei katsota myytäviksi vaan ne pitää laskuttaa ennenkuin näkyvät täällä (varattu > 0)

		global $kukarow, $yhtiorow;

		$query  = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
		$result = mysql_query($query) or die($query.mysql_error());
		$tuote  = mysql_fetch_array($result);

		if (mysql_num_rows($result) == 0) {
			$myytavissa = FALSE;
		}
		elseif ($tuote["ei_saldoa"] != "") {
			$myytavissa = 0;
		}
		else {
			// tuote ok katellaan parametrejä
			$varasto = (int) $varasto;


			//Jos yhtiö tulee parametrinä niin katsotaa, ettei se ole ihan mitä sattuu ja lasketaan sen yhtiön saldo
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
						return 0;
					}
				}
				elseif($yhtio == $kukarow["yhtio"]) {
					$yhtiolisa = $kukarow["yhtio"];
				}
				else {
					return 0;
				}
			}
			else {
				$yhtiolisa = $kukarow["yhtio"];
			}

			// katotaan ollaanko annettu parametriksi joku tietty varastotyyppi
			if ($tyyppi == "E") {
				$valinta = " varastopaikat.tyyppi = 'E' and ";
			}
			elseif ($tyyppi == "V") {
				$valinta = " varastopaikat.tyyppi = 'V' and ";
			}
			elseif ($tyyppi == "KAIKKI") {
				$valinta = " ";
			}
			elseif ($tyyppi == "ORVOT") {
				$valinta = " varastopaikat.tyyppi = 'jokutyyppijotaeioleolemassa' and ";
			}
			else {
				$valinta = " varastopaikat.tyyppi = '' and ";
			}

			// katotaan halutaanko saldo vaan jostain tietystä varastosta (varastopaikat.tunnus), silloin unohdetaan edellä annettu tyyppi kokonaan
			if ($varasto != 0) {
				$valinta = " varastopaikat.tunnus = '$varasto' and ";
			}

			//saldo sallituista varastoista
			$query = "	SELECT tuotepaikat.*
						FROM tuotepaikat
						JOIN varastopaikat on varastopaikat.yhtio=tuotepaikat.yhtio and $valinta
						concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
						concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						WHERE tuotepaikat.yhtio = '$yhtiolisa'
						and tuotepaikat.tuoteno = '$tuoteno'";
			$result = mysql_query($query) or die($query);

			$saldo       = 0;
			$ennakkopois = 0;

			while ($row = mysql_fetch_array($result)) {

				$query = "	SELECT sum(varattu) varattu
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
							WHERE tilausrivi.yhtio = '$yhtiolisa' and
							tilausrivi.tyyppi in ('L','G','V') and
							tilausrivi.tuoteno = '$tuoteno' and
							tilausrivi.varattu > 0 and
							tilausrivi.hyllyalue = '$row[hyllyalue]' and
							tilausrivi.hyllynro =  '$row[hyllynro]' and
							tilausrivi.hyllyvali = '$row[hyllyvali]' and
							tilausrivi.hyllytaso = '$row[hyllytaso]'";
				$ennresult = mysql_query($query) or die($query);
				$ennrow = mysql_fetch_array($ennresult);

				$saldo += $row["saldo"];
				$ennakkopois += $ennrow["varattu"];
			}

			// katsotaan löytyykö tuotetta varattuna joltain muulta paikalta, jota ei ole enää olemassa tuotepaikoissa
			// ekaks haetaan ihan kaikki nykyiset paikat suoraan mysql muotoon
			$query = "	SELECT group_concat(\"'\",rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0'),\"'\") paikat
						FROM tuotepaikat
						WHERE yhtio='$yhtiolisa' and tuoteno='$tuoteno'";
			$ennresult = mysql_query($query) or die($query);
			$ennrow = mysql_fetch_array($ennresult);
			if ($varasto == 0) {
				// jos paikkoja löytyi
				if ($ennrow["paikat"] != "") {
					// etsitään varatut kaikilta paikoilla jolla on joku muu varastopaikka (NOT IN)
					$query = "	SELECT sum(varattu) varattu
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
								WHERE tilausrivi.yhtio = '$yhtiolisa' and
								tilausrivi.tyyppi in ('L','G','V') and
								tilausrivi.tuoteno = '$tuoteno' and
								tilausrivi.varattu > 0 and
								concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) NOT IN ($ennrow[paikat])";
					$ennresult = mysql_query($query) or die($query);
					$ennrow = mysql_fetch_array($ennresult);
					$ennakkopois += $ennrow["varattu"];
				}
				else {
					// tuotteella ei ole yhtään paikkaa.. katotaan silti varatus
					// etsitään varatut kaikilta paikoilla jolla on joku muu varastopaikka (NOT IN)
					$query = "	SELECT sum(varattu) varattu
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
								WHERE tilausrivi.yhtio = '$yhtiolisa' and
								tilausrivi.tyyppi in ('L','G','V') and
								tilausrivi.tuoteno = '$tuoteno' and
								tilausrivi.varattu > 0";
					$ennresult = mysql_query($query) or die($query);
					$ennrow = mysql_fetch_array($ennresult);
					$ennakkopois += $ennrow["varattu"];
				}
			}
			$myytavissa = $saldo - $ennakkopois;
		}
		return $myytavissa;
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

		$pipe = popen("$kirjoitin[komento]", 'w');

		if (!$pipe) {
			echo "pipe failed";
			return;
		}

		$from = array ('ä','å','ö','Ä','Å','Ö','|');

		if ($kirjoitin["merkisto"] == 1) {
			$to	= array ('{','}','|','[',']','\\',chr(179));								// 7 bittiset
			$str = str_replace($from, $to, $str);											// Tehdään käännös
		}
		elseif ($kirjoitin["merkisto"] == 2) {
			$to	= array (chr(132),chr(134),chr(148),chr(142),chr(143),chr(153),chr(179));	// DOS charset
			$str = str_replace($from, $to, $str);											// Tehdään käännös
		}
		elseif ($kirjoitin["merkisto"] == 3) {
			$to	= array (chr(228),chr(229),chr(246),chr(196),chr(197),chr(214),chr(124));	// ANSI charset
			$str = str_replace($from, $to, $str);											// Tehdään käännös
		}
		elseif ($kirjoitin["merkisto"] == 4) {
			$str = utf8_encode($str);														// UTF8 charset käännös suoraan yhellä rivillä
		}

		fputs($pipe,$str);
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
	function avain($laji, $joini = '', $kieli = '') {
		
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

		if ($kieli != $yhtiorow["kieli"]) {
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
				$returni = " if(isnull(a.selitetark) or a.selitetark='', ".$joinaa.".".trim($returni).", a.selitetark) ".trim($returni)." ";
			}
			elseif ($laji == 'select2') {
				$returni = " if(isnull(b.selitetark) or b.selitetark='', ".$joinaa.".".trim($returni).", b.selitetark) ".trim($returni)." ";
			}
			elseif ($laji == 'selectcon') {
				$returni = " if(isnull(a.selitetark) or a.selitetark='', ".$joinaa.".".trim($returni).", a.selitetark) ";
			}
			elseif ($laji == 'selectcon2') {
				$returni = " if(isnull(b.selitetark) or b.selitetark='', ".$joinaa.".".trim($returni).", b.selitetark) ";
			}
		}
						
		return $returni;
	}
	
	/*esimerkki
	".avain('select')."
	".avain('join','TRY_')."
	*/
	
}

// Tehdään lähetteen ja laskun sorttauskentät
if (!function_exists("generoi_sorttauskentta")) {

	function generoi_sorttauskentta() {

		global $kukarow, $yhtiorow;

		$jarjestys = (int) $yhtiorow["lahetteen_jarjestys"];
		$sorttauskentta = "";

		// varastopaikkajärjestys, tuoteperheet pidetään yhdessä, erikoistuotteet loppuun
		if ($jarjestys == "0") {
			$sorttauskentta = "if (tilausrivi.perheid = 0,
								if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '',
									concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno),
									concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno)
								),
								(select concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0'), tilausrivi.tuoteno, perheid) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid)
								) as sorttauskentta";
		}

		// varastopaikkajärjestys, tuoteperheet pidetään yhdessä
		if ($jarjestys == "1") {
			$sorttauskentta = "if (perheid = 0,
								concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno),
								(select concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0'), tilausrivi.tuoteno, perheid) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid)
								) as sorttauskentta";
		}

		// varastopaikkajärjestys, erikoistuotteet loppuun
		if ($jarjestys == "2") {
			$sorttauskentta = "if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '',
								concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno),
								concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno)
								) as sorttauskentta";
		}

		// varastopaikkajärjestys
		if ($jarjestys == "3") {
			$sorttauskentta = "concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno) as sorttauskentta";
		}

		// tuotenumerojärjestys, tuoteperheet pidetään yhdessä, erikoistuotteet loppuun
		if ($jarjestys == "4") {
			$sorttauskentta = "if (tilausrivi.perheid = 0,
								if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '',
									concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno),
									tilausrivi.tuoteno
								),
								(select concat(tuoteno, perheid) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid)
								) as sorttauskentta";
		}

		// tuotenumerojärjestys, tuoteperheet pidetään yhdessä
		if ($jarjestys == "5") {
			$sorttauskentta = "if (tilausrivi.perheid = 0,
								tilausrivi.tuoteno,
								(select concat(tilausrivi.tuoteno, perheid) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid)
								) as sorttauskentta";
		}

		// tuotenumerojärjestys, erikoistuotteet loppuun
		if ($jarjestys == "6") {
			$sorttauskentta = "if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '',
									concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno),
									tilausrivi.tuoteno
								) as sorttauskentta";
		}

		// tuotenumerojärjestys
		if ($jarjestys == "7") {
			$sorttauskentta = "tilausrivi.tuoteno as sorttauskentta";
		}

		// tilausjärjestys, tuoteperheet pidetään yhdessä
		if ($jarjestys == "8") {
			$sorttauskentta = "if (tilausrivi.perheid = 0,
								tilausrivi.tunnus,
								(select concat(tilausrivi.tunnus, perheid) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid)
								) as sorttauskentta";
		}

		// tilausjärjestys
		if ($jarjestys == "9") {
			$sorttauskentta = "tilausrivi.tunnus as sorttauskentta";
		}

		return $sorttauskentta;
	}
}


?>