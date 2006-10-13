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
		mail($yhtiorow['admin_email'], $yhtiorow['nimi']." - SQL Error", $puperror, "From: mailer@pupesoft.com\r\n");

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

if (!function_exists("t")) {

	function t($stringi, $kieli = "") {

		// tarvitaan yhtiörowta, kukarowta ja tieto slaven:n käytöstä
		global $yhtiorow, $kukarow, $useslave, $link;

		require("salasanat.php");

		if ($slavedb[$useslave]=='') $useslave=0;

		if ($useslave > 0) { //Voi olla, että käytämme slavea ja INSERT ei siis onnistu
			$link = mysql_connect ($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
			mysql_select_db ($dbkanta) or die ("Tietokantaa $dbkanta löydy palvelimelta!");
		}

		$sanakirjaquery  = "SELECT * FROM sanakirja WHERE fi = BINARY '$stringi'";
		$sanakirjaresult = mysql_query($sanakirjaquery, $link) or pupe_error($sanakirjaquery);

		if (mysql_num_rows($sanakirjaresult) > 0) {
			$sanakirjarow = mysql_fetch_array($sanakirjaresult);

			if ($kieli != '') {
				$indeksi = strtolower($kieli);
			}
			else {
				$indeksi = strtolower($kukarow["kieli"]);
			}

			if (trim($sanakirjarow[$indeksi]) != '') {
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
			$sanakirjaquery  = "INSERT INTO sanakirja SET fi = '$stringi', aikaleima=now(), kysytty=1";
			$sanakirjaresult = mysql_query($sanakirjaquery, $link) or pupe_error($sanakirjaquery);
		}

		if ($useslave > 0) { //Palautetaan slave käyttöön
			$link = mysql_connect ($slavedb[$useslave], $slaveuser[$useslave], $slavepass[$useslave]) or die("Ongelma tietokantapalvelimessa: '$slavedb[$useslave]'");
			mysql_select_db ($dbkanta) or die ("Tietokantaa $dbkanta löydy palvelimelta!");
		}

		return $stringi;
	}
}

if (!function_exists("kuuluukovarastoon")) {

	function kuuluukovarastoon($hyllyalue, $hyllynro, $varasto = '') {

		global $kukarow;

		$varastolisa = "";

		// voidaan zekata onko varastoalue jossain tietyssä varastossa...
		if ($varasto != "") {
			$varastolisa = " and tunnus='$varasto'";
		}

		$query = "	SELECT tunnus
					FROM varastopaikat
					WHERE
					concat(rpad(upper(alkuhyllyalue) ,3,'0'),lpad(alkuhyllynro ,2,'0')) <= concat(rpad(upper('$hyllyalue') ,3,'0'),lpad('$hyllynro' ,2,'0'))
					and concat(rpad(upper(loppuhyllyalue) ,3,'0'),lpad(loppuhyllynro ,2,'0')) >= concat(rpad(upper('$hyllyalue') ,3,'0'),lpad('$hyllynro' ,2,'0'))
					and yhtio = '$kukarow[yhtio]' $varastolisa";
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

	function saldo_myytavissa($tuoteno, $tyyppi = '', $varasto = 0) {

		// tämä funktio palauttaa myytävissä olevan saldon sallituista varastoista (tyyppi='')
		// laskuttamattomia hyvityksiä ei katsota myytäviksi vaan ne pitää laskuttaa ennenkuin näkyvät täällä (varattu > 0)

		global $kukarow;

		$query  = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
		$result = mysql_query($query) or die($query);
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
						concat(rpad(upper(alkuhyllyalue) ,3,'0'),lpad(alkuhyllynro ,2,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0'))
						and concat(rpad(upper(loppuhyllyalue) ,3,'0'),lpad(loppuhyllynro ,2,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0'))
						WHERE tuotepaikat.yhtio='$kukarow[yhtio]' and tuotepaikat.tuoteno='$tuoteno'";
			$result = mysql_query($query) or die($query);

			$saldo       = 0;
			$ennakkopois = 0;

			while ($row = mysql_fetch_array($result)) {

				$query = "	SELECT sum(varattu) varattu
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
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
			$query = "	SELECT group_concat(\"'\",rpad(upper(hyllyalue) ,3,'0'),lpad(hyllynro ,2,'0'),lpad(hyllyvali ,2,'0'),lpad(hyllytaso ,2,'0'),\"'\") paikat
						FROM tuotepaikat
						WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
			$ennresult = mysql_query($query) or die($query);
			$ennrow = mysql_fetch_array($ennresult);

			// jos paikkoja löytyi
			if ($ennrow["paikat"] != "") {
				// etsitään varatut kaikilta paikoilla jolla on joku muu varastopaikka (NOT IN)
				$query = "	SELECT sum(varattu) varattu
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
							tilausrivi.tyyppi in ('L','G','V') and
							tilausrivi.tuoteno = '$tuoteno' and
							tilausrivi.varattu > 0 and
							concat(rpad(upper(hyllyalue) ,3,'0'),lpad(hyllynro ,2,'0'),lpad(hyllyvali ,2,'0'),lpad(hyllytaso ,2,'0')) NOT IN ($ennrow[paikat])";
				$ennresult = mysql_query($query) or die($query);
				$ennrow = mysql_fetch_array($ennresult);
				$ennakkopois += $ennrow["varattu"];
			}
			else {
				// tuotteella ei ole yhtään paikkaa.. katotaan silti varatus
				// etsitään varatut kaikilta paikoilla jolla on joku muu varastopaikka (NOT IN)
				$query = "	SELECT sum(varattu) varattu
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and
							tilausrivi.tyyppi in ('L','G','V') and
							tilausrivi.tuoteno = '$tuoteno' and
							tilausrivi.varattu > 0";
				$ennresult = mysql_query($query) or die($query);
				$ennrow = mysql_fetch_array($ennresult);
				$ennakkopois += $ennrow["varattu"];
			}

			$myytavissa = $saldo - $ennakkopois;
		}

		return $myytavissa;
	}
}

?>