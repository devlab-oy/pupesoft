<?php

if (!function_exists("pupe_query")) {
	function pupe_query($query, $link = NULL) {

		if (isset($GLOBALS["pupe_query_debug"]) and $GLOBALS["pupe_query_debug"] > 0) {
			$timeparts = explode(" ", microtime());
			$starttime = $timeparts[1].substr($timeparts[0], 1);
		}

		if (is_resource($link)) {
			$res = mysql_query($query, $link) or pupe_error($query);
		}
		else {
			$res = mysql_query($query) or pupe_error($query);
		}

		if (isset($GLOBALS["pupe_query_debug"]) and $GLOBALS["pupe_query_debug"] > 0) {
			$timeparts = explode(" ",microtime());
			$endtime = $timeparts[1].substr($timeparts[0], 1);
			$aika = round($endtime-$starttime, 4);
			$GLOBALS["aika_debug_array"][] = $aika;
			$GLOBALS["quer_debug_array"][] = $query;
		}

		return $res;
	}
}

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
		$query = preg_replace("/  +/", " ", $query);

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
		mail($yhtiorow['admin_email'], mb_encode_mimeheader($yhtiorow['nimi']." - SQL Error", "ISO-8859-1", "Q"), $puperror, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");

		// kuollaan pois
		exit(nl2br($puperror));
	}
}

// mysql queryn echotus kauniisti
if (!function_exists("query_dump")) {
	// otetaan parametriksi query
	function query_dump($query) {

		// trimmataan, tabit, rivinvaihdot ja tuplaspacet pois querystä..
		$query = trim($query);
		$query = str_replace("\t", " ",$query);
		$query = str_replace("\n", "", $query);
		$query = str_replace("\r", "", $query);
		$query = preg_replace("/ {2,}/", " ", $query);

		echo "\n";
		echo htmlentities($query);
		echo "\n<br><br>";
	}
}

// Tehdään REST-kutsuja. Data aina JSON:ia.
if (!function_exists("pupesoft_rest")) {

	function pupesoft_rest($parameters) {

		if (!isset($parameters["method"]) or !in_array($parameters["method"], array("GET", "POST", "PUT", "DELETE"))) {
			return array(FALSE, "Invalid method");
		}

		if (!isset($parameters["data"]) or !is_array($parameters["data"])) {
			return array(FALSE, "Invalid data");
		}

		if (!isset($parameters["url"]) or parse_url($parameters["url"]) === FALSE) {
			return array(FALSE, "Invalid URL");
		}

		if (!isset($parameters["headers"]) or !is_array($parameters["headers"])) {
			$parameters["headers"] = array(	'Accept: application/json',
											'Content-Type: application/json',
											);
		}

		$handle = curl_init();
		curl_setopt($handle, CURLOPT_URL, $parameters["url"]);
		curl_setopt($handle, CURLOPT_HTTPHEADER, $parameters["headers"]);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

		if (isset($parameters["auth_user"]) and isset($parameters["auth_pass"])) {
			curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($handle, CURLOPT_USERPWD, "{$parameters["auth_user"]}:{$parameters["auth_pass"]}");
		}

		if (isset($parameters["cookie"])) {
			curl_setopt($handle, CURLOPT_COOKIE, $parameters["cookie"]);
		}

		if (isset($parameters["posttype"]) and $parameters["posttype"] == "array") {
			$post_data = $parameters["data"];
		}
		else {
			$post_data = json_encode($parameters["data"]);
		}

		switch ($parameters["method"]) {
			case "GET":
				curl_setopt($handle, CURLOPT_URL, $parameters["url"]."?".http_build_query($parameters["data"]));
				break;
			case "POST":
				curl_setopt($handle, CURLOPT_POST, true);
				curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
				break;
			case "PUT":
				curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
				break;
			case "DELETE":
				curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
		}

		$response_json = curl_exec($handle);
		$response = json_decode($response_json, true);

		$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

		return array($code, $response);
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
		elseif (isset($laskurow["maa"]) and $laskurow["maa"] == "SE") {
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
	function lopetus($lopetus, $meta = "", $palautavainurli = FALSE) {
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

				preg_match("/^([^\?&]*)\??/i", $lopetus, $nimi);
				preg_match("/toim=([^&]*)&?/i", $lopetus, $alanimi);

				$nimet = explode("/", str_replace($palvelin2, "", $nimi[1]));

				if (!isset($alanimi[1])) $alanimi[1] = "";

				for ($i=0; $i<count($nimet); $i++) {

					$nimi = "";

					for ($j = $i; $j < count($nimet); $j++) {
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
					$res = pupe_query($query);

					if (mysql_num_rows($res) > 0) {
						$row = mysql_fetch_assoc($res);
						break;
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

				if ($lask > 0) {
					if (strrpos($lopetus, "#") !== FALSE) {

						preg_match("/(#.*?)[&\?\/$]/", $lopetus, $ankkurimatchi);

						$lopetus = str_replace($ankkurimatchi[1], "", $lopetus).$ankkurimatchi[1];
					}
				}

				if (isset($nimilisa[1]) and $nimilisa[1] != "") {
					$nlisa = " / ".$nimilisa[1];
				}
				else {
					$nlisa = "";
				}

				$row["nimitys"] = trim(str_ireplace("UUSI", "", $row["nimitys"]));

				if ($meta == "") {
					if ($palautavainurli) return $lopetus;
					else if ($row["nimitys"] != "") echo "<a href='$lopetus'>&laquo; ".t("Palaa ohjelmaan").": ".t("$row[nimitys]")."$nlisa</a><br>";
					else echo "<a href='$lopetus'>&laquo; ".t("Palaa edelliseen näkymään")."</a><br>";
				}

				if ($edlopetus != "") {
					$edlopetus .= "/SPLIT/";
				}

				$edlopetus .= $lopetus_clean;

				$lask++;
			}
		}

		if ($meta != "") {
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$lopetus'>";

			require("inc/footer.inc");
			exit;
		}
	}
}

if (!function_exists("tv2dateconv")) {
	function tv2dateconv($date) {
		//kääntää mysqln vvvv-kk-pp muodon muotoon vvvvkkpp
		return substr($date,0,4).substr($date,5,2).substr($date,8,2);
	}
}

if (!function_exists("tv3dateconv")) {
	function tv3dateconv($date) {
		//kääntää vvvvkkpp muodon muotoon vvvv-kk-pp
		return substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6,2)." 00:00:00";
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
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {
				$laskurow = mysql_fetch_assoc($result);

				$query_ale_lisa = generoi_alekentta('M');

				$query = "	SELECT round(sum(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),$yhtiorow[hintapyoristys]) summa
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and otunnus = '$kukarow[kesken]'
							and tyyppi != 'D'";
				$result = pupe_query($query);
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
		$res = pupe_query($query);
		$maa = mysql_fetch_assoc($res);

		// otetaan pois maa koodi
		if ($kieli == "" and isset($GLOBALS["kieli"])) {
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
		$aliasresult = pupe_query($aliasquery);

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
			$pres = pupe_query($query);
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
		$varcheckres = pupe_query($query);

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
			$pres = pupe_query($query);
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
		$varcheckres = pupe_query($query);

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

if (!function_exists("maventa_invoice_put_file")) {
	function maventa_invoice_put_file($client, $api_keys, $invoice_number, $xmlstring, $kieli) {
		$files_out['files'] 	= array();
		$files_out['filenames'] = array();

		//Finvoice
		$files_out['files'][0] 	   = base64_encode($xmlstring);
		$files_out['filenames'][0] = "$invoice_number.xml";

		//PDFä
		$files_out['files'][1] 	   = base64_encode(tulosta_lasku("LASKU:".$invoice_number, $kieli, "VERKKOLASKU_MAVENTA", "", "", "", ""));
		$files_out['filenames'][1] = "$invoice_number.pdf";

		$return_value = $client->invoice_put_finvoice($api_keys, $files_out);

		return $return_value->status;
	}
}

if (!function_exists("apix_invoice_put_file")) {
	function apix_invoice_put_file($lahetettavat_apix, $kieli, $valittu_tulostin, $valittu_kopio_tulostin) {
		global $kukarow, $yhtiorow, $pupe_root_polku;

		// siirretään laskutiedosto operaattorille
		#$url         	= "https://test-api.apix.fi/invoices";
		$url            = "https://api.apix.fi/invoices";
		$transferkey    = $yhtiorow['apix_avain'];
		$transferid     = $yhtiorow['apix_tunnus'];
		$software       = "Pupesoft";
		$version        = "1.0";
		$timestamp      = gmdate("YmdHis");
		$apixzipfile    = "Apix_".$yhtiorow['yhtio']."_invoices_".$timestamp."_".md5(uniqid(rand(),true)).".zip";
		$apix_ulos 		= "";

		// Luodaan temppidirikka jonne työnnetään tän kiekan kaikki apixfilet
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$apix_tmpdirnimi = "/tmp/apix-".md5(uniqid(mt_rand(), true));

		if (mkdir($apix_tmpdirnimi)) {

			// Kirjoitetaan finvoiceaineisto dirikkaan
			if (!file_put_contents($apix_tmpdirnimi."/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml", implode("\n", $lahetettavat_apix))) {
				$apix_ulos .= "APIX finvoicewrite feilasi!";
			}

			// Luodaan laskupdf:ät
			$apix_lasnot = array_keys($lahetettavat_apix);

			foreach ($apix_lasnot as $apixlaskunro) {
				$apixtmpfile = tulosta_lasku("LASKU:".$apixlaskunro, $kieli, "VERKKOLASKU_APIX", "", "", "", "");

				// Siirretään faili apixtemppiin
				if (!rename($apixtmpfile, $apix_tmpdirnimi."/Apix_invoice_$apixlaskunro.pdf")) {
					$apix_ulos .= "APIX tmpmove Apix_invoice_$apixlaskunro.pdf feilas!";
				}
			}

			// Tehdään apixzippi
			exec("cd $apix_tmpdirnimi; zip $apixzipfile *;");

			// Aineisto dataouttiin
			exec("cp $apix_tmpdirnimi/$apixzipfile $pupe_root_polku/dataout/");

			// Poistetaan apix-tmpdir
			exec("rm -rf $apix_tmpdirnimi");

			// Siirretään aineisto APIXiin
			$digest_src = $software."+".$version."+".$transferid."+".$timestamp."+".$transferkey;

			$dt = substr(hash('sha256', $digest_src), 0, 64);

			$real_url = "$url?soft=$software&ver=$version&TraID=$transferid&t=$timestamp&d=SHA-256:$dt";

			$apixfilesize = filesize("$pupe_root_polku/dataout/$apixzipfile");
			$apix_fh = fopen("$pupe_root_polku/dataout/$apixzipfile", 'r');

			$ch = curl_init($real_url);
			curl_setopt($ch, CURLOPT_PUT, true);
			curl_setopt($ch, CURLOPT_INFILE, $apix_fh);
			curl_setopt($ch, CURLOPT_INFILESIZE, $apixfilesize);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			$apix_ulos .= "Lähetetään aineisto APIX:lle...<br>";
			$response = curl_exec($ch);

			curl_close($ch);
			fclose($apix_fh);

			$xml = simplexml_load_string($response);

			if ($xml->Status == "OK") {
				$apix_ulos .= "Lähetys onnistui!";
			}
			else {
				$apix_ulos .= "Lähetys epäonnistui:<br>";

				$apix_ulos .= "Tila: ".$xml->Status."<br>";
				$apix_ulos .= "Tilakoodi: ".$xml->StatusCode."<br>";

				foreach ($xml->FreeText as $teksti) {
					$apix_ulos .= "Tilaviesti: ".$teksti."<br>";
				}

				$apix_ulos .= "Laskut: ".implode(", ", $apix_lasnot)."<br>";
			}
		}
		else {
			$apix_ulos .= "APIX tmpdirrin teko feilas!<br>";
		}

		return $apix_ulos;
	}
}

if (!function_exists("vapauta_sarjanumerot")) {
	function vapauta_sarjanumerot($toim, $tilausnumero, $lisa="", $dellaa_ostorivi = TRUE) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT tilausrivi.tunnus, (tilausrivi.varattu+tilausrivi.jt) varattu, tilausrivi.tuoteno, tuote.sarjanumeroseuranta, tilausrivin_lisatiedot.tilausrivilinkki
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.otunnus = '$kukarow[kesken]'
					$lisa ";
		$sres = pupe_query($query);

		while ($srow = mysql_fetch_assoc($sres)) {

			if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
				// merktaan siirtolistatunnus nollaks
				$query = "UPDATE sarjanumeroseuranta set siirtorivitunnus = 0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and siirtorivitunnus='$srow[tunnus]'";
				$sarjares = pupe_query($query);
			}
			elseif ($srow["varattu"] < 0) {
				// dellataan koko rivi jos sitä ei ole vielä myyty
				$query = "DELETE from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]' and myyntirivitunnus=0";
				$sarjares = pupe_query($query);

				if (mysql_affected_rows() == 0) {
					// merkataan osorivitunnus nollaksi
					$query = "UPDATE sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]'";
					$sarjares = pupe_query($query);
				}
			}
			else {
				// merkataan myyntirivitunnus nollaks
				if ($srow["sarjanumeroseuranta"] == "E" or $srow["sarjanumeroseuranta"] == "F" or $srow["sarjanumeroseuranta"] == "G") {
					$query = "	DELETE FROM sarjanumeroseuranta
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$srow[tuoteno]'
								and myyntirivitunnus = '$srow[tunnus]'";
					$sarjares = pupe_query($query);
				}
				else {
					$query = "	UPDATE sarjanumeroseuranta
								SET myyntirivitunnus = 0
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$srow[tuoteno]'
								and myyntirivitunnus = '$srow[tunnus]'";
					$sarjares = pupe_query($query);
				}
			}

			// Onko tätä ostotilauksella?
			if ($dellaa_ostorivi and $srow["tilausrivilinkki"] > 0) {
				$query = "	UPDATE tilausrivi
							SET tyyppi = 'D'
							WHERE yhtio 	= '$kukarow[yhtio]'
							and tunnus  	= '$srow[tilausrivilinkki]'
							and tyyppi 		= 'O'
							and uusiotunnus = 0";
				$siirtores = pupe_query($query);
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

		if (is_array($varasto) and count($varasto) > 0) {
			$varasto = implode(",", $varasto);
		}
		else {
			$varasto = (int) $varasto;

			if ($kukarow["extranet"] != "" and $varasto == 0 and (int) $kukarow["varasto"] > 0) {
				$varasto = $kukarow['varasto'];
			}
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
		$asires = pupe_query($query);
		$asirow = mysql_fetch_assoc($asires);

		if (!function_exists("tuoteperhe_myytavissa_reku")) {
			function tuoteperhe_myytavissa_reku($yhtio, $tuoteno, $tuotteet, $tuotteet_str, $isat_array, $kaikki_array, $kerroin_array) {
				if (!in_array($tuoteno, $isat_array)) {
					$isat_array[] = $tuoteno;

					$query = " 	SELECT distinct tuote.tuoteno, tuoteperhe.kerroin, tuote.ei_saldoa
								from tuoteperhe
								join tuote on tuoteperhe.yhtio = tuote.yhtio and tuoteperhe.tuoteno = tuote.tuoteno
								where tuoteperhe.yhtio = '$yhtio'
								and isatuoteno = '$tuoteno'
								and tyyppi in ('','P')";
					$isiresult = pupe_query($query);

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

		for ($isa=0; $isa < $riikoko; $isa++) {
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
		$varresult = pupe_query($query);

		$varasto_myytavissa = array();

		if ($summaus == "KAIKKI") {
			$perhe_myytavissa = array();
		}

		while ($saldorow = mysql_fetch_assoc($varresult)) {

			if ($summaus != "KAIKKI") {
				$perhe_myytavissa = array();
			}

			foreach ($tuotteet as $tuoteno => $kerroin) {

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

		if (is_array($varasto) and count($varasto) > 0) {
			$varasto = implode(",", $varasto);
		}
		else {
			$varasto = (int) $varasto;

			if ($kukarow["extranet"] != "" and $varasto == 0 and (int) $kukarow["varasto"] > 0) {
				$varasto = $kukarow['varasto'];
			}
		}

		$query  = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
		$result = pupe_query($query);
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

			if ($yhtio != "") {
				// Jos yhtiö tulee parametrinä niin katsotaan, ettei se ole ihan mitä sattuu ja lasketaan sen yhtiön saldo
				// (TÄMÄ OMINAISUUS DEPRECATED!)
				$yhtiolisa = $kukarow["yhtio"];
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
				$valinta1 = " (varastopaikat.tyyppi = '' or (varastopaikat.tyyppi = 'E' and varastopaikat.erikoistoimitus_alarajasumma > 0 and '$tuote[myyntihinta]' >= varastopaikat.erikoistoimitus_alarajasumma)) and ";
				$valinta2 = "";
				$valinta3 = " HAVING varastopaikat.tyyppi is not null ";
			}

			// Katotaan halutaanko saldo vaan jostain tietystä varastosta (varastopaikat.tunnus), silloin unohdetaan edellä annettu tyyppi kokonaan
			if ((is_numeric($varasto) and $varasto > 0) or (!is_numeric($varasto) and $varasto != "")) {
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
			$result = pupe_query($query);

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

					$ennresult = pupe_query($query);
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

						$keraresult = pupe_query($query);
						$kerarow = mysql_fetch_assoc($keraresult);

						$myytavissa -= $kerarow["keratty"];
						$hyllyssa -= $kerarow["keratty"];

						// jos lasketaan tulevaisuuteen niin otetaan varastoonvalmistukset huomioon (asiakkaallevalmistuksten valmisteet eivät lisää myytävissäolevaa määrää ku tuoteet on samantien varattu asiakkaalle jolle ne valmistetaan)
						if (($tuote["sarjanumeroseuranta"] == "E" or $tuote["sarjanumeroseuranta"] == "F" or $tuote["sarjanumeroseuranta"] == "G") and $era != '') {
							$query = "	SELECT
										ifnull(sum(if(lasku.tilaustyyppi='V' and tilausrivi.tyyppi = 'W', 0, tilausrivi.varattu)),0) varattu
										FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
										JOIN lasku use index (primary) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and lasku.tilaustyyppi in ('W','V')
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
										and tilausrivi.tyyppi in ('W','M')
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
										ifnull(sum(if(lasku.tilaustyyppi='V' and tilausrivi.tyyppi = 'W', 0, tilausrivi.varattu)),0) varattu
										FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
										JOIN lasku use index (primary) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and lasku.tilaustyyppi in ('W','V')
										WHERE tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
										and tilausrivi.yhtio = '$yhtiolisa'
										and tilausrivi.tyyppi in ('W','M')
										and (tilausrivi.perheid2 = 0 or tilausrivi.perheid2=tilausrivi.tunnus)
										and tilausrivi.tuoteno = '$tuoteno'
										and tilausrivi.varattu > 0
										and tilausrivi.hyllyalue = '$row[hyllyalue]'
										and tilausrivi.hyllynro  = '$row[hyllynro]'
										and tilausrivi.hyllyvali = '$row[hyllyvali]'
										and tilausrivi.hyllytaso = '$row[hyllytaso]'
										and tilausrivi.kerayspvm <= '$pvm'";
						}
						$valmresult = pupe_query($query);
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
						$osttarkres = pupe_query($query);
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
							$osttarkres = pupe_query($query);
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
								$osttarkres = pupe_query($tarkquery);
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
									$osttarkres = pupe_query($query);
									$osttarkrow = mysql_fetch_assoc($osttarkres);
								}
							}
						}
					}

					$ostresult = pupe_query($query);
					$ostrow = mysql_fetch_assoc($ostresult);

					$myytavissa += $ostrow["varattu"];
				}

				// katsotaan löytyykö tuotetta varattuna joltain muulta paikalta, jota ei ole enää olemassa tuotepaikoissa
				// ekaks haetaan ihan kaikki nykyiset paikat suoraan mysql muotoon
				$query = "	SELECT group_concat(\"'\",rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0'),\"'\") paikat
							FROM tuotepaikat
							WHERE yhtio='$yhtiolisa' and tuoteno='$tuoteno'";
				$ennresult = pupe_query($query);
				$ennrow = mysql_fetch_assoc($ennresult);

				if (is_numeric($varasto) and $varasto == 0 and $hyllyalue == "" and $hyllynro == "") {

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
							$ennsaldoresult = pupe_query($query);

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
						$ennresult = pupe_query($query);
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
						$ennresult = pupe_query($query);
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
	function lpr($str, $prn, $prnkomento = "") {

		global $kukarow;

		if ($prnkomento != "") {
			$query = "	SELECT *
						FROM kirjoittimet
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND komento = '".mysql_real_escape_string($prnkomento)."'";
		}
		else {
			$query = "	SELECT *
						FROM kirjoittimet
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND tunnus = '{$prn}'";
		}

		$kirre = pupe_query($query);

		if (mysql_num_rows($kirre) != 1) {
			echo "printer not found";
			return;
		}

		$kirjoitin = mysql_fetch_assoc($kirre);

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

if (!function_exists("tilinumero_print")) {
	function tilinumero_print($tilino_data) {

		global $yhtiorow;

		if (strtoupper($yhtiorow["maa"]) != "FI") {
			return $tilino_data;
		}

		if (trim($tilino_data) == '') {
			return "";
		}
		else {
			if (substr($tilino_data, 0, 1) >= 0 and substr($tilino_data, 0, 1) <= '9') {
				$alkuosa = substr($tilino_data, 0, 6);

				# WHAT ???
				#if ($tilino_print[0] == "4" or $tilino_print[0] == "5") {
				#	$loppuosa = substr($tilino_data, 6, 1);
				#	$loppuindex = 7;
				#}
				#else {
					$loppuosa = "";
					$loppuindex = 6;
		  		#}

				while ($tilino_data[$loppuindex] == "0") {
					$loppuindex++;
				}

		  		$loppuosa .= substr($tilino_data, $loppuindex);

		  		return "$alkuosa-$loppuosa";
		  	}
		  	else {
				return $tilino_data;
		  	}
		}

	}
}

if (!function_exists("pankkipvm")) {
	function pankkipvm($pvm) {
		return substr($pvm, 4, 2).".".substr($pvm, 2, 2).".20".substr($pvm, 0, 2);
	}
}

// Tehdään valuuttamuunnos yhtiön valuutasta laskun valuuttaan.
if (!function_exists("kehahin")) {
	function kehahin($tuoteno) {

		global $kukarow, $yhtiorow;

		$query = "SELECT round(if (epakurantti100pvm='0000-00-00', if (epakurantti75pvm='0000-00-00', if (epakurantti50pvm='0000-00-00', if (epakurantti25pvm='0000-00-00', kehahin, kehahin*0.75), kehahin*0.5), kehahin*0.25), 0),6) kehahin
				  FROM tuote
				  WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
		$result = pupe_query($query);
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

				// echo "3 JOTAIN... $summa | $monta |< $koko @ $desi ><br>";

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
									if (tilausrivi.perheid > 0, ifnull((select concat(rpad(upper(t2.hyllyalue), 5, '0'),lpad(upper(t2.hyllynro), 5, '0'),lpad(upper(t2.hyllyvali), 5, '0'),lpad(upper(t2.hyllytaso), 5, '0'), t2.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus AND t2.tyyppi != 'D'), if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))),
								   		if (tilausrivi.perheid2 > 0, ifnull((select concat(rpad(upper(t3.hyllyalue), 5, '0'),lpad(upper(t3.hyllynro), 5, '0'),lpad(upper(t3.hyllyvali), 5, '0'),lpad(upper(t3.hyllytaso), 5, '0'), t3.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus AND t3.tyyppi != 'D'), if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno))),
											tilausrivi.tuoteno))) as sorttauskentta";
		}

		// varastopaikkajärjestys, tuoteperheet pidetään yhdessä
		elseif ($jarjestys == "1") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno),
									if (tilausrivi.perheid > 0, ifnull((select concat(rpad(upper(t2.hyllyalue), 5, '0'),lpad(upper(t2.hyllynro), 5, '0'),lpad(upper(t2.hyllyvali), 5, '0'),lpad(upper(t2.hyllytaso), 5, '0'), t2.tuoteno, rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus AND t2.tyyppi != 'D'), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno)),
								   		if (tilausrivi.perheid2 > 0, ifnull((select concat(rpad(upper(t3.hyllyalue), 5, '0'),lpad(upper(t3.hyllynro), 5, '0'),lpad(upper(t3.hyllyvali), 5, '0'),lpad(upper(t3.hyllytaso), 5, '0'), t3.tuoteno, rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus AND t3.tyyppi != 'D'), concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno)),
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
									if (tilausrivi.perheid > 0, ifnull((select concat(t2.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus AND t2.tyyppi != 'D'), if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)),
										if (tilausrivi.perheid2 > 0, ifnull((select concat(t3.tuoteno, if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus AND t3.tyyppi != 'D'), if (tilausrivi.hyllyalue = '' and tilausrivi.hyllynro = '' and tilausrivi.hyllyvali = '' and tilausrivi.hyllytaso = '', concat('ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ', tilausrivi.tuoteno), tilausrivi.tuoteno)),
											tilausrivi.tuoteno))) as sorttauskentta";
		}

		// tuotenumerojärjestys, tuoteperheet pidetään yhdessä
		elseif ($jarjestys == "5") {
			$sorttauskentta = "if (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0, tilausrivi.tuoteno,
									if (tilausrivi.perheid > 0, ifnull((select concat(t2.tuoteno, tilausrivi.tuoteno) from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus AND t2.tyyppi != 'D'), tilausrivi.tuoteno),
										if (tilausrivi.perheid2 > 0, ifnull((select concat(t3.tuoteno, tilausrivi.tuoteno) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus AND t3.tyyppi != 'D'), tilausrivi.tuoteno),
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
									if (tilausrivi.perheid > 0, ifnull((select concat(if (tt2.toim_tuoteno!='', concat(tt2.toim_tuoteno,tilausrivi.perheid), concat(t2.tuoteno,tilausrivi.perheid)), if (tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)) from tilausrivi as t2 JOIN tuotteen_toimittajat tt2 ON t2.yhtio = tt2.yhtio and t2.tuoteno = tt2.tuoteno and tt2.liitostunnus = '$toimittaja' where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.perheid and t2.otunnus=tilausrivi.otunnus AND t2.tyyppi != 'D'), if (tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)),
										if (tilausrivi.perheid2 > 0, ifnull((select concat(if (tt3.toim_tuoteno!='', concat(tt3.toim_tuoteno,tilausrivi.perheid2), concat(t3.tuoteno,tilausrivi.perheid2)), if (tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)) from tilausrivi as t3 JOIN tuotteen_toimittajat tt3 ON t3.yhtio = tt3.yhtio and t3.tuoteno = tt3.tuoteno and tt3.liitostunnus = '$toimittaja' where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid2 and t3.otunnus=tilausrivi.otunnus AND t3.tyyppi != 'D'), if (tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)),
											if (tuotteen_toimittajat.toim_tuoteno!='', tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno)))) as sorttauskentta";
		}
		elseif ($jarjestys == "M") {
			$sorttauskentta = " if ((tilausrivi.perheid=0 or tilausrivi.perheid=tilausrivi.tunnus),
									ifnull((SELECT tlt2.jarjestys FROM tilausrivin_lisatiedot tlt2 WHERE tlt2.yhtio=tilausrivi.yhtio and tlt2.tilausrivitunnus=tilausrivi.tunnus LIMIT 1), tilausrivi.tunnus),
									if (tilausrivi.perheid >0 ,
										ifnull((SELECT tlt3.jarjestys FROM tilausrivin_lisatiedot tlt3 WHERE tlt3.yhtio=tilausrivi.yhtio and tlt3.tilausrivitunnus=tilausrivi.perheid LIMIT 1), tilausrivi.perheid),
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

if (!function_exists("synclog")) {
	function synclog($yhtio, $table, $viesti, $tunnus) {
		global $yhtiorow, $kukarow;

		if ($kukarow["yhtio"] == $yhtio) {
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
		$insres = pupe_query($query);
	}
}

// Konserniyhtiöiden tietueiden synkronointi
if (!function_exists("synkronoi")) {
	function synkronoi($yhtio, $table, $tunnus, $orig = "", $force = "") {
		global $yhtiorow, $kukarow, $synkronoi_kiellot;

		//	Onko mahdollista synkronoida?
		if (stripos($yhtiorow["synkronoi"], $table) === false) {
			return false;
		}

		$muutokset = array();
		$avainsanalajilisa = "";
		$lajit = "";

		if ($table == "avainsana") {
			$abulisa = preg_match("/(^|,)(avainsana\|*([\|a-zA-Z_\-]*))($|,)/i", $yhtiorow["synkronoi"], $regs);

			$avainsanalajilisa = "|".$regs[3];
			$lajit = explode("|",strtolower($regs[3]));
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
		$masterres = pupe_query($query);

		if (mysql_num_rows($masterres) == 1) {
			$masterrow = mysql_fetch_array($masterres) or pupe_error($query);

			//	Näillä tarttetaan vähän lisätietoo, saadaanko juuri tätä lajia synkronoida?
			if ($table == "avainsana" and !in_array(strtolower($masterrow["laji"]), $lajit)) {
				return false;
			}

			//	Näillä tarttetaan vähän lisätietoo
			if ($table == "asiakasalennus" and $masterrow["alennus"] < 0) {
				synclog($yhtio, $table, "Negatiivisia alennuksia ei synkata!", $tunnus);
				return false;
			}

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
							    if (isset($synkronoi_kiellot[$table]) and in_array($key, $synkronoi_kiellot[$table])) {
							        // Ohitetaan tämä koska se on synkronoi kielloissa
							    }
							    else {
								    $muutokset[$key] .= $value;
								}
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

		list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattavat, $saakopoistaa, $oletukset) = pakolliset_sarakkeet($table);

		if (count($wherelliset) == 0 and count($pakolliset) == 0) {
			$synclog.= "VIRHE: Pyydettyä taulua $table ei voida synkronoida, sitä ei ole määritelty!\n";

			synclog($yhtio, $table, $synclog, $tunnus);
			return false;
		}
		else {
			//	Tehdään kysely
			$where = "";

			if (is_array($wherelliset)) {
				$indeksi = array_merge($wherelliset, $pakolliset);
				$indeksi = array_unique($indeksi);
			}
			else {
				$indeksi = &$pakolliset;
			}

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

		//	haetaan konserniyhtiöt joita voidaan synkronoida
		$query = "	SELECT yhtio.yhtio
					from yhtio
					JOIN yhtion_parametrit ON yhtion_parametrit.yhtio=yhtio.yhtio
					where yhtio.konserni	 		 = '$yhtiorow[konserni]'
					and (yhtion_parametrit.synkronoi = '$table$avainsanalajilisa' or yhtion_parametrit.synkronoi like '$table$avainsanalajilisa,%' or yhtion_parametrit.synkronoi like '%,$table$avainsanalajilisa,%' or yhtion_parametrit.synkronoi like '%,$table$avainsanalajilisa')
					and yhtio.yhtio					!= '$yhtio'";
		$kohderes = pupe_query($query);

		if (mysql_num_rows($kohderes) > 0) {
			while ($kohderow = mysql_fetch_assoc($kohderes)) {

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
						$query = "SELECT * from asiakas where yhtio='$yhtio' and tunnus='$abuhaku[liitostunnus]'";
						$abures = pupe_query($query);
						$aburow = mysql_fetch_assoc($abures);

						//	Etsitään oikea asiakas, jotta saadaan oikea liitostunnus
						$tarkquery = "SELECT tunnus from asiakas where yhtio='$kohderow[yhtio]' and ytunnus='$aburow[ytunnus]' and ovttunnus='$aburow[ovttunnus]' and toim_ovttunnus='$aburow[toim_ovttunnus]'";
					}
					else {
						$query = "SELECT * from toimi where yhtio='$yhtio' and tunnus='$abuhaku[liitostunnus]'";
						$abures = pupe_query($query);
						$aburow = mysql_fetch_assoc($abures);

						//	Etsitään oikea toimittaja, jotta saadaan oikea liitostunnus
						$tarkquery = "SELECT tunnus from toimi where yhtio='$kohderow[yhtio]' and ytunnus='$aburow[ytunnus]' and ovttunnus='$aburow[ovttunnus]'";
					}
					$tarkres = pupe_query($tarkquery);

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

					$query = "SELECT * from tuote where yhtio='$yhtio' and tuoteno='$abuhaku[tuoteno]'";
					$abures = pupe_query($query);

					if (mysql_num_rows($abures) == 1) {

						$query = "SELECT * from toimi where yhtio='$yhtio' and tunnus='$abuhaku[liitostunnus]'";
						$abures = pupe_query($query);
						$aburow = mysql_fetch_assoc($abures);

						//	Etsitään oikea toimittaja jotta saadaan oikea liitostunnus
						$tarkquery = "SELECT tunnus from toimi where yhtio='$kohderow[yhtio]' and ytunnus='$aburow[ytunnus]' and ovttunnus='$aburow[ovttunnus]'";
						$tarkres = pupe_query($tarkquery);

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
					}

					if ($table == "tuote") {
						//	Oletetaan, että meillä on avainsanat sycronoituna (osasto/try)
						foreach(array("myyjanro","ostajanro","tilino","tilino_eu","tilino_ei_eu","kustp","kohde","projekti","kehahin","vihahin","vihapvm","epakurantti25pvm","epakurantti50pvm","epakurantti75pvm","epakurantti100pvm") as $value) {
							$override[$value] = "";
						}

						// Tarkastetaan että hinnat eivät mene aivan päin metsää..
						$query = "SELECT tunnus FROM yhtion_parametrit WHERE yhtio='$kohderow[yhtio]' AND alv_kasittely!='$yhtiorow[alv_kasittely]'";
						$abures = pupe_query($query);

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
						$abures = pupe_query($query);
						$aburow = mysql_fetch_assoc($abures);

						//	Melkein kaikki tiedot pitää stemmata!
						$query = "	SELECT tunnus
									from maksuehto
									where yhtio				= '$kohderow[yhtio]'
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
						$tarkres = pupe_query($query);

						if (mysql_num_rows($tarkres) == 1) {
							$tarkrow = mysql_fetch_assoc($tarkres);
							$override["maksuehto"] = $tarkrow["tunnus"];
						}
						else {
							$override["maksuehto"] = "";
						}

						//	koitetaan hakea oikean toimitustavan tunnus..
						$query = "SELECT * from toimitustapa where yhtio='$yhtio' and tunnus='$masterrow[toimitustapa]'";
						$abures = pupe_query($query);
						$aburow = mysql_fetch_assoc($abures);

						//	Melkein kaikki tiedot pitää stemmata!
						$query = "	SELECT tunnus from toimitustapa where yhtio='$kohderow[yhtio]'
									and selite			= '$aburow[selite]'
									and jvkulu			= '$aburow[jvkulu]'
									and lauantai		= '$aburow[lauantai]'
									and maa_maara		= '$aburow[maa_maara]'
									and merahti			= '$aburow[merahti]'
									and nouto			= '$aburow[nouto]'
									and sallitut_maat	= '$aburow[sallitut_maat]'";
						$tarkres=pupe_query($query);

						if (mysql_num_rows($tarkres) == 1) {
							$tarkrow = mysql_fetch_assoc($tarkres);
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
							    if (isset($synkronoi_kiellot[$table]) and in_array(mysql_field_name($masterres, $i), $synkronoi_kiellot[$table])) {
							        // Ohitetaan tämä koska se on synkronoi kielloissa
							    }
                                elseif (isset($override[mysql_field_name($masterres, $i)]) and $override[mysql_field_name($masterres, $i)] != "") {
									$query .= ", ". mysql_field_name($masterres,$i)."='".$override[mysql_field_name($masterres, $i)]."' ";
								}
								else {
									$query .= ", ". mysql_field_name($masterres,$i)."='".$masterrow[mysql_field_name($masterres, $i)]."' ";
								}
							}
						}

						$query = $query." ".$query2;
						$updres = pupe_query($query);

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
								$updres = pupe_query($query);

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
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$laskurow = mysql_fetch_assoc($result);

			if ($laskurow["tila"] == "U") {
				// Tavara on ostettu sisään myyntilaskulla
				$query = "	SELECT round(tilausrivi.rivihinta/tilausrivi.kpl, 2) ohinta
							FROM tilausrivi
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]'
							and tilausrivi.tunnus = $rivitunnus";
				$presult = pupe_query($query);
				$tilrivirow = mysql_fetch_assoc($presult);

				return $tilrivirow["ohinta"];
			}
			elseif ($laskurow["tila"] == "K" and $laskurow["alatila"] == "I") {
				// Tavara on ostettu sisään myyntilaskulla
				$query = "	SELECT round(tilausrivi.rivihinta/tilausrivi.kpl, 2) ohinta
							FROM tilausrivi
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]'
							and tilausrivi.tunnus = $rivitunnus";
				$presult = pupe_query($query);
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
				$presult = pupe_query($query);

				// Tsekataan vielä laskun valuttakurssi
				if ($laskurow["vienti_kurssi"] == 0) {	//tämä olisi huonompi juttu, mut ei mikään stopperi
					$laskurow["vienti_kurssi"] = 1;
				}

				// Tarkastetaan onko taricit käytössä
				$tv_kaytossa = FALSE;

				$query = "SELECT count(*) kpl from taric_veroperusteet";
				$tv_res = mysql_query($query);
				$tv_row = mysql_fetch_array($tv_res);

				if ($tv_row["kpl"] > 0) {
					$tv_kaytossa = TRUE;
				}

				// Jos lasketaan virallista varastonarvoa, otetaan keikan summa huomioon (kululaskujen yhteenlaskettu summa OLETUSVALUUTASSA on laskurow.saldo_maksettu)
				$rahtikulut = $laskurow['saldo_maksettu'] + round($laskurow['rahti_etu'] * $laskurow['vienti_kurssi'], 6);

				while ($tilrivirow = mysql_fetch_assoc($presult)) {

					$query  = "	SELECT *
								from tuote
								LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]' and tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno)
								where tuote.yhtio	= '$kukarow[yhtio]'
								and tuote.tuoteno	= '$tilrivirow[tuoteno]'";
					$tuores = pupe_query($query);
					$tuorow = mysql_fetch_assoc($tuores);

					if ($tuorow['tuotekerroin'] <= 0) $tuorow['tuotekerroin'] = 1;

					// jos kysessä on kotimainen vaihto-omaisuuslasku, pitää lisätä tuotteen hintaan alvi
					if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {
						$alvit = 1 + $tuorow["alv"] / 100;
					}
					else {
						$alvit = 1;
					}

					$alennukset = generoi_alekentta_php($tilrivirow, 'O', 'kerto');

					if ($laskurow['summa'] != 0) {
						$osuus   = round($tilrivirow['hinta']*$alvit*abs($tilrivirow['varattu'])*$alennukset*$tuorow['tuotekerroin'],2)/$laskurow['summa'];	// kuinka paljon tämä rivi on koko tilauksesta

						$rahtios = $osuus*$rahtikulut;	// lasketaan sama osuus rahtikuluista tälle riville
					}
					else {
						$rahtios = 0;
					}

					if ($tilrivirow['varattu'] < 0) {
						$rahtios = $rahtios * -1;
					}

					$ohinta  = round($tilrivirow['hinta']*$alennukset*$tuorow['tuotekerroin']*$laskurow['vienti_kurssi']*$tilrivirow['varattu']+$rahtios,6); // tuotteen rivihinta rahteineen OLETUSVALUUTASSA

					$tulliprossa = 0;

					if ($tv_kaytossa and $tilrivirow["var"] == "" and $laskurow["maa"] != $yhtiorow["maa"]) {
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
					sum(if (tilausrivi.tyyppi in ('V','W'), 1, 0)) valmistusriveja,
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
		$tilre = pupe_query($query);
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
			$result = pupe_query($query);

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
			$chkresult4 = pupe_query($query);

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
			$presult = pupe_query($query);
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

			while ($prow = mysql_fetch_assoc($presult)) {

				if ($prow["tyyppi"] == 'W' or $prow["tyyppi"] == 'M') {
					// Nämä ovat valmisteita
					$valmisteet_chk[$prow["tunnus"]] = $prow["tuoteno"];
					$tuotenumerot[$prow["tunnus"]] = $prow["tuoteno"];
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

				if (($prow["tyyppi"] == "W" or $prow["tyyppi"] == "M") and $prow["toimitettuaika"] != "0000-00-00 00:00:00" and $toim == "KORJAA") {

					//tutkitaan kuinka paljon tätä nyt oli valmistettu
					$query = "	SELECT sum(kpl) valmistetut
								FROM tilausrivi
								WHERE yhtio	= '$kukarow[yhtio]'
								and otunnus = '$prow[otunnus]'
								and perheid = '$prow[perheid]'
								and tuoteno = '$prow[tuoteno]'
								and tyyppi	= 'D'
								and toimitettuaika = '0000-00-00 00:00:00'";
					$sumres = pupe_query($query);
					$sumrow = mysql_fetch_array($sumres);

					$query = "	SELECT count(*) laskuja
								FROM lasku
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus 	= '$prow[laskutettu]'
								and tila 	= 'U'
								and alatila	= 'X'";
					$slres = pupe_query($query);
					$slrow = mysql_fetch_array($slres);

					if ($prow["tunnus"] == $prow["perheid"]) {
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
					else {
						if (($sumrow["valmistetut"] != 0 and $slrow["laskuja"] == 0) or ($sumrow["valmistetut"] != 0 and $slrow["laskuja"] > 0)) {

							if ((float) $prow["valmistetaan"] > 0) {
								$valmkpllat[$prow["tunnus"]] = $prow["valmistetaan"];
							}
							else {
								$valmkpllat[$prow["tunnus"]] = $sumrow["valmistetut"];
							}
						}
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
			$hresult = pupe_query($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow       	= mysql_fetch_assoc($hresult);
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
			$hresult = pupe_query($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow       	= mysql_fetch_assoc($hresult);
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
			$hresult = pupe_query($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow			= mysql_fetch_assoc($hresult);
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
			$hresult = pupe_query($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow			= mysql_fetch_assoc($hresult);
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

		if ($trow["myyntihinta_maara"] != 0) {
			$hinta = round($hinta / $trow["myyntihinta_maara"], $yhtiorow["hintapyoristys"]);
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
		// $laskurow[valkoodi]			--> Laskun valuutta

		///* Ulos *///
		// $alv                			--> Uusi laskettu ALV
		// $hinta           			--> Uusi laskettu kappalehinta

		//yhtiön oletusalvi!
		$wquery = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
		$wtres  = pupe_query($wquery);
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
		elseif ($alv >= 600) {
			//Tässä keississä on käänteisen verotuksen
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] == 0) $alv = 600;											// Otsikolla on valittu veroton myynti, esim Ahvenanmaan myntiä
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] != 0) $alv = $trow["alv"]+600;								// Otsikolla on valittu verollinen kotimaan myynti
			if ($laskurow["vienti"] == "E" and $laskurow["ytunnus"] != "") $alv = 600; 										// EU-vienti ytunnuksella --> alviton
			if ($laskurow["vienti"] == "K") $alv = 600; 																	// Vienti eu:n ulkopuolelle on aina verotonta

			$alv = (float) $alv;
		}
		elseif ($alv >= 500) {
			//Tässä keississä on marginaalimyyntiä
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] == 0) $alv = 500;											// Otsikolla on valittu veroton myynti, esim Ahvenanmaan myntiä
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] != 0) $alv = $trow["alv"]+500;								// Otsikolla on valittu verollinen kotimaan myynti
			if ($laskurow["vienti"] == "E" and $laskurow["ytunnus"] != "") $alv = 500; 										// EU-vienti ytunnuksella --> alviton
			if ($laskurow["vienti"] == "K") $alv = 500; 																	// Vienti eu:n ulkopuolelle on aina verotonta

			$alv = (float) $alv;
		}
		elseif ($yhtiorow["alv_kasittely"] != "") {
			//Tässä keississä kaikki hinnat ovat aina verottomia ja vero lisätään vasta laskutuksessa
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] == 0) $alv = 0;												// Otsikolla on valittu veroton myynti, esim Ahvenanmaan myntiä
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] != 0 and $alv == "") $alv = $trow["alv"];					// Otsikolla on valittu verollinen kotimaan myynti
			if ($laskurow["vienti"] == "E" and $laskurow["ytunnus"] != "") $alv = 0; 										// EU-vienti ytunnuksella --> alviton
			if ($laskurow["vienti"] == "K") $alv = 0; 																		// Vienti eu:n ulkopuolelle on aina verotonta

			$alv = (float) $alv;
		}
		else {
			//Tässä keississä kaikki hinnat sisältävät arvonlisäveron
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] == 0) $alv = 0;												// Otsikolla on valittu veroton myynti, esim Ahvenanmaan myntiä
			if ($laskurow["vienti"] == ""  and $laskurow["alv"] != 0 and $alv == "") $alv = $trow["alv"];					// Otsikolla on valittu verollinen kotimaan myynti
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

// Funkkari jolla tutkitaan onko joku ale vielä settaamatta
if (!function_exists("jatka_ale")) {
	function jatka_ale($ale) {
		global $yhtiorow;

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			if (!isset($ale["ale".$alepostfix])) {
				return TRUE;
			}
		}

		return FALSE;
	}
}

// Funkkari jolla setataan löydetyt alennukset
if (!function_exists("settaa_ale")) {
	function settaa_ale($ale, $aleperuste, $aperuste, $hresult, $aleperuste_nro, $aleperuste_teksti) {
		global $yhtiorow;

		if (mysql_num_rows($hresult) > 0) {
			while ($hrow = mysql_fetch_assoc($hresult)) {
				if (!isset($ale["ale".$hrow["alennuslaji"]])) {
					$aleperuste["ale".$hrow["alennuslaji"]] = $aleperuste_nro;
					$ale["ale".$hrow["alennuslaji"]] = $hrow["alennus"];
					$aperuste .= " $aleperuste_teksti (Aletaso: $hrow[alennuslaji]).";
				}
			}
		}

		return array($ale, $aleperuste, $aperuste);
	}
}

if (!function_exists("etayhtio_hinta")) {
	function etayhtio_hinta ($rivi_tuoteno, $kpl_maara, $laskurow) {
		// Tarvitaa: rivin tuoteno, laskurow, kpl-määrä, eta_yhtio

		global $yhtiorow, $kukarow;

		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '{$GLOBALS['eta_yhtio']}'
					AND tuoteno = '{$rivi_tuoteno}'";
		$tres_eta = pupe_query($query);
		$trow_eta = mysql_fetch_assoc($tres_eta);

		list($lis_hinta_eta, $lis_netto_eta, $lis_eta_ale_kaikki, $alehinta_alv_eta, $alehinta_val_eta) = alehinta($laskurow, $trow_eta, $kpl_maara, '', '', '', '', $GLOBALS['eta_yhtio']);

		$hintapyoristys_echo = $lis_hinta_eta;

		foreach ($lis_eta_ale_kaikki as $val) {
			$hintapyoristys_echo *= (1 - ($val / 100));
		}

		$etayhtion_rivinarvo = 	($hintapyoristys_echo * $kpl_maara);
		return 	$etayhtion_rivinarvo;
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
		// $ale käyttäjän syöttämä ale - array
		// $debug (jos 1 niin näytetään tulos)
		// if ($yhtiorow["asiakashinta_netto"] == "") jos kenttä on tyhjä niin asiakashinnat ovat nettohintoja, muuten ovat ei-nettohintoja

		// Tulokset on:
		// $hinta (hinta)
		// $netto onko hinta nettohinta vai ei
		// $ale - array jossa alennuprosentteja (aleprosentti)
		// $aperuste (selkokielinen teksti mihin päädyttiin)
		// $alehinta_alv jos on joku erikoialv tälle hinnaston tuotteelle
		// $alehinta_val hinnan valuutta
		// $hintaperuste hinnan peruste koodina
		// $aleperuste  alennuksen peruste koodina

		/*
		HUOM: tarkista asiakasinfo.php kun muutat alennus ja hintalalaskentaa!
		*/

		/*
		HUOM: tarkista kaikki nämä koodit "if ($temptrow["hinnastoon"] == "V" and ($hinnat["hintaperuste"] > 13 or $hinnat["hintaperuste"] === FALSE) and ($hinnat["aleperuste"] > 12 or $hinnat["aleperuste"] === FALSE)) {" kun muutat ale ja hintaperusteita!
		REGEXP: if \(\$[^\[]*\["hinnastoon"\] == "V" and \(\$[^\[]*\["hintaperuste"\] > 13 or \$[^\[]*\["hintaperuste"\] === FALSE\) and \(\$[^\[]*\["aleperuste"\] > 12 or \$[^\[]*\["aleperuste"\] === FALSE\)\)
		*/

		// Varmistetaan, että määrä on ok
		$kpl = (float) $kpl;
		if ($kpl == 0) $kpl = 1;

		$aperuste 		= "";
		$alehinta_alv 	= 0;
		$hintaperuste	= FALSE;
		$aleperuste		= FALSE;
		$vanha_yhtio	= "";
		$vanha_liitostunnus = 0;

		// Tässä katotaan onko epäkuranttiutta
		if ($trow["epakurantti100pvm"] != "0000-00-00") {
			$trow["kehahin"] = 0;
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

		// KAUTTALASKUTUSKIKKARE
		if ($yhtio != '') {
			$vanha_yhtio 		= $kukarow['yhtio'];
			$vanha_liitostunnus = $laskurow['liitostunnus'];
			$vanha_ytunnus 		= $laskurow['ytunnus'];

			$yhtiorow = hae_yhtion_parametrit($yhtio);
			$kukarow['yhtio'] = $yhtio;

			$query = "	SELECT ytunnus, tunnus
						FROM asiakas
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND laji != 'P'
						AND ytunnus = '{$laskurow['ytunnus']}'
						AND toim_ovttunnus = '{$laskurow['toim_ovttunnus']}'";
			$asiakas_tunnus_res = pupe_query($query);
			$asiakas_tunnus_row = mysql_fetch_assoc($asiakas_tunnus_res);

			$laskurow["ytunnus"] 		= $asiakas_tunnus_row["ytunnus"];
			$laskurow["liitostunnus"] 	= $asiakas_tunnus_row["tunnus"];
		}

		// oletetaan yhtiön valuutta jos sitä ei tiedetä
		if ($laskurow["valkoodi"] == "") $laskurow["valkoodi"] = $yhtiorow["valkoodi"];

		// oletetaan tuotteen alvi ja valuutta
		$alehinta_val = $yhtiorow["valkoodi"];

		// jos meillä on lasku menossa ulkomaille
		if ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maa"]) {

			// tutkitaan ollaanko siellä alv-rekisteröity
			$query = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and vat_numero != ''";
			$alhire = pupe_query($query);

			// ollaan alv-rekisteröity, haetaan tuotteelle oikea ALV
			if (mysql_num_rows($alhire) == 1) {
				$query = "SELECT * from tuotteen_alv where yhtio='$kukarow[yhtio]' and maa='$laskurow[maa]' and tuoteno='$trow[tuoteno]' limit 1";
				$alhire = pupe_query($query);

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
		$query = "	SELECT *
					from asiakas
					where yhtio = '$kukarow[yhtio]'
					and tunnus = '$laskurow[liitostunnus]'";
		$alhire = pupe_query($query);

		if (mysql_num_rows($alhire) == 1) {
			$alehi_asrow = mysql_fetch_assoc($alhire);
		}
		else {
			$alehi_asrow = array();
			$aperuste .= t("Asiakasta ei löytynyt").". ";
		}

		// haetaan asiakkaan segmentit
		$query = "	SELECT group_concat(parent.tunnus) tunnukset
	   				FROM puun_alkio
					JOIN dynaaminen_puu AS node ON (puun_alkio.yhtio = node.yhtio and puun_alkio.laji = node.laji and puun_alkio.puun_tunnus = node.tunnus)
	   				JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft AND parent.lft > 0)
	   				WHERE puun_alkio.yhtio = '$kukarow[yhtio]'
					AND puun_alkio.laji    = 'asiakas'
					AND puun_alkio.liitos  = '$laskurow[liitostunnus]'";
		$almight = pupe_query($query);
		$alehi_assegmenttirow = mysql_fetch_assoc($almight);

		if ($alehi_assegmenttirow["tunnukset"] == "") {
			$alehi_assegmenttirow = array();
		}

		// 1. käyttäjän syöttämä hinta/nettohinta
		if ($hinta != '') {
			$hintaperuste = 1;

			// nettohinta jos netto-kentässä tulee N tai E
			if ($netto == 'N' or $netto == 'E') {
				$aperuste .= "Käyttäjän antama nettohinta ";
				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					$ale["ale".$alepostfix] = 0;
				}
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

			// 2A. asiakas.tunnus/asiakas.ytunnus tuote.tuotenumero nettohinta (asiakkaan tuotteen hinta) laskun valuutassa
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakashinta ashin1 USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and tuoteno  = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							UNION
							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakashinta ashin2 USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							ORDER BY prio, minkpl desc, aika, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta         = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste     .= "Asiakkaan tuotteen nettohinta laskun valuutassa. ";
					$alehinta_val  = $laskurow["valkoodi"];
					$hintaperuste = 2;
				}
			}

			// 2B. asiakas.tunnus/asiakas.ytunnus tuote.tuotenumero nettohinta (asiakkaan tuotteen hinta) yhtiön valuutassa
			if ($hinta == 0) {

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
							FROM asiakashinta ashin1 USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							UNION
							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
							FROM asiakashinta ashin2 USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							ORDER BY prio, minkpl desc, aika, valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta		 = $hrow["hinta"];
					$aperuste	.= "Asiakkaan tuotteen nettohinta.".$hrow['laji'];
					$hintaperuste = 2;
				}
			}

			// 3A. asiakas.tunnus/asiakas.ytunnus tuote.aleryhmä nettohinta (asiakkaan tuotealeryhmän hinta) laskun valuutassa
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakashinta ashin1 USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							UNION
							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakashinta ashin2 USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							ORDER BY prio, minkpl desc, aika, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta        = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste    .= "Asiakkaan tuotealeryhmän nettohinta laskun valuutassa. ";
					$alehinta_val = $laskurow["valkoodi"];
					$hintaperuste = 3;
				}
			}

			// 3B. asiakas.tunnus/asiakas.ytunnus tuote.aleryhmä nettohinta (asiakkaan tuotealeryhmän hinta) yhtiön valuutassa
			if ($hinta == 0) {

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
							FROM asiakashinta ashin1 USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							UNION
							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
							FROM asiakashinta ashin2 USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							ORDER BY prio, minkpl desc, aika, valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta		= $hrow["hinta"];
					$aperuste	.= "Asiakkaan tuotealeryhmän nettohinta. ";
					$hintaperuste = 3;
				}
			}

			// 4A. asiakas.segmentti tuote.tuoteno nettohinta (asiakassegmentin tuotteen hinta) laskun valuutassa
			if ($hinta == 0 and isset($alehi_assegmenttirow["tunnukset"]) and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	SELECT hinta, laji
							FROM asiakashinta
							WHERE yhtio = '$kukarow[yhtio]'
						    and asiakas_segmentti in ($alehi_assegmenttirow[tunnukset])
							and asiakas_segmentti != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and ytunnus = ''
							and asiakas = 0
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), hinta asc, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta        = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste    .= "Asiakassegmentin tuotteen nettohinta laskun valuutassa. ";
					$alehinta_val = $laskurow["valkoodi"];
					$hintaperuste = 4;
				}
			}

			// 4B. asiakas.segmentti tuote.tuoteno nettohinta (asiakassegmentin tuotteen hinta) yhtiön valuutassa
			if ($hinta == 0 and isset($alehi_assegmenttirow["tunnukset"])) {

				$query = "	SELECT hinta, laji
							FROM asiakashinta
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_segmentti in ($alehi_assegmenttirow[tunnukset])
							and asiakas_segmentti !=''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and ytunnus = ''
							and asiakas = 0
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), hinta asc,tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta        = $hrow["hinta"];
					$aperuste    .= "Asiakassegmentin tuotteen nettohinta ";
					$hintaperuste = 4;
				}
			}

			// 5A. asiakas.ryhmä tuote.tuoteno nettohinta (asiakasaleryhmän tuotteen hinta) laskun valuutassa
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
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta        = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste    .= "Asiakasaleryhmän tuotteen nettohinta laskun valuutassa. ";
					$alehinta_val = $laskurow["valkoodi"];
					$hintaperuste = 5;
				}
			}

			// 5B. asiakas.ryhmä tuote.tuoteno nettohinta (asiakasaleryhmän tuotteen hinta) yhtiön valuutassa
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
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta		= $hrow["hinta"];
					$aperuste	.= "Asiakasaleryhmän tuotteen nettohinta. ";
					$hintaperuste = 5;
				}
			}

		   	// 6A. asiakas.piiri tuote.tuoteno nettohinta (asiakaspiirin tuotteen hinta) laskun valuutassa
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	SELECT hinta, laji
							FROM asiakashinta
							WHERE yhtio = '$kukarow[yhtio]'
							and piiri = '$alehi_asrow[piiri]'
							and piiri != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and ytunnus = ''
							and asiakas = 0
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), hinta asc,tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta        = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste    .= "Asiakaspiirin tuotteen nettohinta laskun valuutassa. ";
					$alehinta_val = $laskurow["valkoodi"];
					$hintaperuste = 6;
				}
			}

		   	// 6B. asiakas.piiri tuote.tuoteno nettohinta (asiakaspiirin tuotteen hinta) yhtiön valuutassa
			if ($hinta == 0) {

				$query = "	SELECT hinta, laji
							FROM asiakashinta
							WHERE yhtio = '$kukarow[yhtio]'
							and piiri = '$alehi_asrow[piiri]'
							and piiri != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and ytunnus = ''
							and asiakas = 0
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999),hinta asc, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta        = $hrow["hinta"];
					$aperuste    .= "Asiakaspiirin tuotteen nettohinta. ";
					$hintaperuste = 6;
				}
			}

		 	// 7A. asiakas.segmentti tuote.aleryhma nettohinta (asiakassegmentin tuotealeryhmän hinta) laskun valuutassa
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and isset($alehi_assegmenttirow["tunnukset"])) {

				$query = "	SELECT hinta, laji
							FROM asiakashinta
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_segmentti in ($alehi_assegmenttirow[tunnukset])
							and asiakas_segmentti != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ytunnus = ''
							and asiakas = 0
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), hinta asc,tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta        = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste    .= "Asiakassegmentin tuotealeryhmän nettohinta laskun valuutassa.";
					$alehinta_val = $laskurow["valkoodi"];
					$hintaperuste = 7;
				}
			}

			// 7B. asiakas.segmentti tuote.aleryhma nettohinta (asiakassegmentin tuotealeryhmän hinta) yhtiön valuutassa
			if ($hinta == 0 and isset($alehi_assegmenttirow["tunnukset"])) {

				$query = "	SELECT hinta, laji
							FROM asiakashinta
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_segmentti in ($alehi_assegmenttirow[tunnukset])
							and asiakas_segmentti != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ytunnus = ''
							and asiakas = 0
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), hinta asc,tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta        = $hrow["hinta"];
					$aperuste    .= "Asiakassegmentin tuotealeryhmän nettohinta.";
					$hintaperuste = 7;
				}
			}

			// 8A. asiakas.ryhmä tuote.aleryhmä nettohinta (asiakasaleryhmän tuotealeryhmän hinta) laskun valuutassa
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
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta        = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste    .= "Asiakasaleryhmän tuotealeryhmän nettohinta laskun valuutassa. ";
					$alehinta_val = $laskurow["valkoodi"];
					$hintaperuste = 8;
				}
			}

			// 8B. asiakas.ryhmä tuote.aleryhmä nettohinta (asiakasaleryhmän tuotealeryhmän hinta) yhtiön valuutassa
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
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta		= $hrow["hinta"];
					$aperuste	.= "Asiakasaleryhmän tuotealeryhmän nettohinta. ";
					$hintaperuste = 9;
				}
			}

			// 9A. asiakas.piiri tuote.aleryhma nettohinta (asiakaspiirin tuotealeryhmän hinta) laskun valuutassa
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	SELECT hinta, laji
							FROM asiakashinta
							WHERE yhtio = '$kukarow[yhtio]'
							and piiri = '$alehi_asrow[piiri]'
							and piiri != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ytunnus = ''
							and asiakas = 0
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), hinta asc,tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta        = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste    .= "Asiakaspiirin tuotealeryhmän nettohinta laskun valuutassa. ";
					$alehinta_val = $laskurow["valkoodi"];
					$hintaperuste = 9;
				}
			}

			// 9B. asiakas.piiri tuote.aleryhma nettohinta (asiakaspiirin tuotealeryhmän hinta) yhtiön valuutassa
			if ($hinta == 0) {

				$query = "	SELECT hinta, laji
							FROM asiakashinta
							WHERE yhtio = '$kukarow[yhtio]'
							and piiri = '$alehi_asrow[piiri]'
							and piiri != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ytunnus = ''
							and asiakas = 0
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), hinta asc,tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					elseif ($hrow["laji"] == "B") {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}
					else {
						//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
						if ($yhtiorow["asiakashinta_netto"] == "E") {
							$netto	= 'E';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
						//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
						elseif ($yhtiorow["asiakashinta_netto"] == "B") {
							$netto	= '';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
							}
						}
						//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
						else {
							$netto	= 'N';
							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								$ale["ale".$alepostfix] = 0;
							}
						}
					}

					$hinta        = $hrow["hinta"];
					$aperuste    .= "Asiakaspiirin tuotealeryhmän nettohinta. ";
					$hintaperuste = 9;
				}
			}

			// 10. asiakas.tunnus/asiakas.ytunnus tuote.aleryhmä negatiivinen-aleprosentti (asiakkaan katemyyntihinta netto)
			if ($hinta == 0) {

				$query = "	(SELECT '1' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakasalennus asale1 USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and asiakas_ryhma = ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and (alennus < 0 or left(alennus,1) = '-'))
							UNION
							(SELECT '2' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakasalennus asale2 USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and asiakas_ryhma = ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and (alennus < 0 or left(alennus,1) = '-'))

							ORDER BY prio, minkpl desc, aika, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {

					$hrow = mysql_fetch_assoc($hresult);

					// Kehahinta saa olla nolla vain jos tuote on kokoepäkurantti, muuten katemyynti ei onnaa
					if ($trow['kehahin'] <= 0) {
						$hinta 		= 0.01;
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
						$netto		= "";
						$aperuste 	.= "Katemyyntihinta (kokoepäkurantti). ";
						$hintaperuste = 10;
					}
					elseif ($trow['kehahin'] > 0) {
						// Lasketaan keskihankintahinnan päälle haluttu kate
						$hinta = $trow['kehahin'] / (1 - (abs($hrow["alennus"]) / 100));

						// Jos on poikkeava myyntihinta-määrä laitetaan se hintaan mukaan
						if ($trow["myyntihinta_maara"] != 0) {
							$hinta = $hinta * $trow["myyntihinta_maara"];
						}

						// Jos tuotteiden hinnat sisältävät arvonlisäveron lisätään vero päälle
						if ($yhtiorow['alv_kasittely'] == '') {
							$hinta = $hinta * (1 + ($trow['alv'] / 100));
						}

						// Pyöristetään hinta haluttuun tarkkuuteen
						$hinta = round($hinta, $yhtiorow['hintapyoristys']);

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
						$netto		= "";
						$aperuste 	.= "Katemyyntihinta. ";
						$hintaperuste = 10;
					}
				}
			}

			// 11. asiakas.segmentti tuote.aleryhmä negatiivinen-aleprosentti (asiakassegmentin katemyyntihinta netto)
			if ($hinta == 0 and isset($alehi_assegmenttirow["tunnukset"])) {

				$query = "	SELECT alennus
							FROM asiakasalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_segmentti in ($alehi_assegmenttirow[tunnukset])
							and asiakas_segmentti != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ytunnus = ''
							and asiakas = 0
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and (alennus < 0 or left(alennus,1) = '-')
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999),alennus desc, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {

					$hrow = mysql_fetch_assoc($hresult);

					// Kehahinta saa olla nolla vain jos tuote on kokoepäkurantti, muuten katemyynti ei onnaa
					if ($trow['kehahin'] <= 0) {
						$hinta 		= 0.01;
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
						$netto		= "";
						$aperuste 	.= "Asiakassegmentin Katemyyntihinta (kokoepäkurantti). ";
						$hintaperuste = 11;
					}
					elseif ($trow['kehahin'] > 0) {
						// Lasketaan keskihankintahinnan päälle haluttu kate
						$hinta = $trow['kehahin'] / (1 - (abs($hrow["alennus"]) / 100));

						// Jos on poikkeava myyntihinta-määrä laitetaan se hintaan mukaan
						if ($trow["myyntihinta_maara"] != 0) {
							$hinta = $hinta * $trow["myyntihinta_maara"];
						}

						// Jos tuotteiden hinnat sisältävät arvonlisäveron lisätään vero päälle
						if ($yhtiorow['alv_kasittely'] == '') {
							$hinta = $hinta * (1 + ($trow['alv'] / 100));
						}

						// Pyöristetään hinta haluttuun tarkkuuteen
						$hinta = round($hinta, $yhtiorow['hintapyoristys']);

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}

						$netto		= "";
						$aperuste 	.= "Asiakassegmentin Katemyyntihinta. ";
						$hintaperuste = 11;
					}
				}
			}

			// 12. asiakas.ryhmä tuote.aleryhmä negatiivinen-aleprosentti (asiakkaan katemyyntihinta netto)
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
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and (alennus < 0 or left(alennus,1) = '-')
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {

					$hrow = mysql_fetch_assoc($hresult);

					// Kehahinta saa olla nolla vain jos tuote on kokoepäkurantti, muuten katemyynti ei onnaa
					if ($trow['kehahin'] <= 0) {
						$hinta 		= 0.01;
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
						$netto		= "";
						$aperuste 	.= "Asiakasryhmä Katemyyntihinta (kokoepäkurantti). ";
						$hintaperuste = 11;
					}
					elseif ($trow['kehahin'] > 0) {
						// Lasketaan keskihankintahinnan päälle haluttu kate
						$hinta = $trow['kehahin'] / (1 - (abs($hrow["alennus"]) / 100));

						// Jos on poikkeava myyntihinta-määrä laitetaan se hintaan mukaan
						if ($trow["myyntihinta_maara"] != 0) {
							$hinta = $hinta * $trow["myyntihinta_maara"];
						}

						// Jos tuotteiden hinnat sisältävät arvonlisäveron lisätään vero päälle
						if ($yhtiorow['alv_kasittely'] == '') {
							$hinta = $hinta * (1 + ($trow['alv'] / 100));
						}

						// Pyöristetään hinta haluttuun tarkkuuteen
						$hinta = round($hinta, $yhtiorow['hintapyoristys']);

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}

						$netto		= "";
						$aperuste 	.= "Asiakasryhmä Katemyyntihinta. ";
						$hintaperuste = 12;
					}
				}
			}

			// 13. asiakas.piiri tuote.aleryhmä negatiivinen-aleprosentti (asiakaspiirin katemyyntihinta netto)
			if ($hinta == 0) {

				$query = "	SELECT alennus
							FROM asiakasalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and piiri = '$alehi_asrow[piiri]'
							and piiri != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ytunnus = ''
							and asiakas = 0
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and (alennus < 0 or left(alennus,1) = '-')
							ORDER BY minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999),alennus desc, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {

					$hrow = mysql_fetch_assoc($hresult);

					// Kehahinta saa olla nolla vain jos tuote on kokoepäkurantti, muuten katemyynti ei onnaa
					if ($trow['kehahin'] <= 0) {
						$hinta 		= 0.01;
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
						$netto		= "";
						$aperuste 	.= "Asiakaspiirin Katemyyntihinta (kokoepäkurantti). ";
						$hintaperuste = 11;
					}
					elseif ($trow['kehahin'] > 0) {
						// Lasketaan keskihankintahinnan päälle haluttu kate
						$hinta = $trow['kehahin'] / (1 - (abs($hrow["alennus"]) / 100));

						// Jos on poikkeava myyntihinta-määrä laitetaan se hintaan mukaan
						if ($trow["myyntihinta_maara"] != 0) {
							$hinta = $hinta * $trow["myyntihinta_maara"];
						}

						// Jos tuotteiden hinnat sisältävät arvonlisäveron lisätään vero päälle
						if ($yhtiorow['alv_kasittely'] == '') {
							$hinta = $hinta * (1 + ($trow['alv'] / 100));
						}

						// Pyöristetään hinta haluttuun tarkkuuteen
						$hinta = round($hinta, $yhtiorow['hintapyoristys']);

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}

						$netto		= "";
						$aperuste 	.= "Asiakaspiirin Katemyyntihinta. ";
						$hintaperuste = 13;
					}
				}
			}

			// 14. asiakas.tunnus/asiakas.ytunnus tuote.aleryhmä aleprosentti == 999.99 (asiakkaan myymälähinta)
			if ($hinta == 0) {
				$query = "	(SELECT '1' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakasalennus asale1 USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus = 999.99)
							UNION
							(SELECT '2' prio, alennus, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM asiakasalennus asale2 USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus = 999.99)

							ORDER BY prio, minkpl desc, aika, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {

					$hrow = mysql_fetch_assoc($hresult);

					//Jos aleprossa  = 999.99 haetaan tuotteen myymälähinta myyntihinnan tilalle
					$query = "SELECT myymalahinta, alv FROM tuote WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$trow[tuoteno]'";
					$myymresult = pupe_query($query);
					$myymrow = mysql_fetch_assoc($myymresult);

					if ($alehinta_alv != "") $myymrow["alv"] = $alehinta_alv;

					if ($myymrow["myymalahinta"] > 0) {
						if ($yhtiorow["alv_kasittely"] != '') {
							$hinta = $myymrow["myymalahinta"]/($myymrow["alv"]/100+1);
						}
						else {
							$hinta = $myymrow["myymalahinta"];
						}
					}

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
						$ale["ale".$alepostfix] = 0;
					}

					$netto		= "";
					$aperuste 	.= "Myymälähinta. ";
					$hintaperuste = 14;
				}
			}

			// 15A. hinnasto.hinta tuotteen nettohinta hinnastosta laskun valuutassa
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
							and ((minkpl <= $kpl and maxkpl >= $kpl) or (minkpl = 0 and maxkpl = 0))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), maa desc, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow       	= mysql_fetch_assoc($hresult);
					$hinta 			= round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$netto			= $hrow["laji"];
					$aperuste		.= "Tuotteen hinnastohinta laskun valuutassa";
					$alehinta_val	= $laskurow["valkoodi"];
					$hintaperuste 	= 15;
				}
			}

			// 15B. hinnasto.hinta tuotteen nettohinta hinnastosta yhtiön valuutassa
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
							and ((minkpl <= $kpl and maxkpl >= $kpl) or (minkpl = 0 and maxkpl = 0))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow       	= mysql_fetch_assoc($hresult);
					$hinta 			= $hrow["hinta"];
					$netto			= $hrow["laji"];
					$aperuste		.= "Tuotteen hinnastohinta yhtiön valuutassa";
					$hintaperuste = 15;
				}
			}

			// 16. tuote.nettohinta (tuotteen nettohinta)
			if ($hinta == 0 and $netto != 'E' and $trow['nettohinta'] > 0) {

				$hinta 			= $trow['nettohinta'];
				$aperuste 		.= "Tuotteen nettohinta. ";
				$netto 			= 'N';
				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					$ale["ale".$alepostfix] = 0;
				}
				$hintaperuste = 16;
			}

			// 17A. hinnasto.hinta tuotteen bruttohinta hinnastosta laskun valuutassa
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
							and ((minkpl <= $kpl and maxkpl >= $kpl) or (minkpl = 0 and maxkpl = 0))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), maa desc, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow			= mysql_fetch_assoc($hresult);
					$hinta			= round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$netto			= $hrow["laji"];
					$aperuste		.= "Tuotteen hinnastohinta laskun valuutassa";
					$alehinta_val   = $laskurow["valkoodi"];
					$hintaperuste = 17;
				}
			}

			// 17B. hinnasto.hinta tuotteen bruttohinta hinnastosta yhtiön valuutassa
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
							and ((minkpl <= $kpl and maxkpl >= $kpl) or (minkpl = 0 and maxkpl = 0))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow			= mysql_fetch_assoc($hresult);
					$hinta 			= $hrow["hinta"];
					$netto			= $hrow["laji"];
					$aperuste		.= "Tuotteen hinnastohinta yhtiön valuutassa";
					$hintaperuste = 17;
				}
			}

			// 18. tuote.myyntihinta (tuotteen bruttohinta) yhtiön valuutassa
			if ($hinta == 0) {
				$hinta		 = $trow['myyntihinta'];
				$aperuste	.= "Tuotteen myyntihinta. ";
				$hintaperuste = 18;
			}
		}

		// Käydään läpi käsin syötetyt alennukset jos niitä on
		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			if (isset($ale["ale".$alepostfix]) and substr($ale["ale".$alepostfix], -1) == "+") {
				// 1. käyttäjän syöttämä EURO-määräinen alennus
				$aleperuste["ale".$alepostfix] = 1;

				$hinta_xxx = $hinta + substr($ale["ale".$alepostfix],0,-1);
				$ale["ale".$alepostfix] = (1 - ($hinta/$hinta_xxx))*100;
				$hinta = $hinta_xxx;

				$aperuste .= " Käyttäjän syöttämä EURO-määräinen ale (Aletaso: $alepostfix)";
			}
			elseif (isset($ale["ale".$alepostfix]) and substr($ale["ale".$alepostfix], -1) == "-") {
				// 2. käyttäjän syöttämä EURO-määräinen alennus
				$aleperuste["ale".$alepostfix] = 2;

				$hinta_xxx = $hinta - substr($ale["ale".$alepostfix],0,-1);
				$ale["ale".$alepostfix] = (1 - ($hinta_xxx/$hinta))*100;

				$aperuste .= " Käyttäjän syöttämä EURO-määräinen ale (Aletaso: $alepostfix)";
			}
			elseif (isset($ale["ale".$alepostfix]) and is_numeric($ale["ale".$alepostfix]) and $ale["ale".$alepostfix] > 0) {
				// 3. käyttäjän syöttämä alennus
				$aleperuste["ale".$alepostfix] = 3;

				$aperuste .= " Käyttäjän syöttämä ale (Aletaso: $alepostfix)";
			}
			elseif (isset($ale["ale".$alepostfix]) and is_numeric($ale["ale".$alepostfix]) and $ale["ale".$alepostfix] == 0) {
				// 4. käyttäjän syöttämä alennus
				$aleperuste["ale".$alepostfix] = 4;

				$aperuste .= " Ei alennusta (Aletaso: $alepostfix)";
			}
			elseif (isset($ale["ale".$alepostfix]) and is_numeric($ale["ale".$alepostfix]) and $ale["ale".$alepostfix] < 0) {
				// 5. käyttäjän syöttämä katejuttu
				// Nolla keskihankintahinta tai kokoepäkurantti, katemyynti ei onnaa laitetaan sentti-hinnalla
				if ($trow['kehahin'] <= 0) {
					$hinta = 0.01;
				}
				elseif ($trow['kehahin'] > 0) {
					// Lasketaan keskihankintahinnan päälle haluttu kate
					$hinta = $trow['kehahin'] / (1 - (abs($ale["ale".$alepostfix]) / 100));

					// Jos on poikkeava myyntihinta-määrä laitetaan se hintaan mukaan
					if ($trow["myyntihinta_maara"] != 0) {
						$hinta = $hinta * $trow["myyntihinta_maara"];
					}

					// Jos tuotteiden hinnat sisältävät arvonlisäveron lisätään vero päälle
					if ($yhtiorow['alv_kasittely'] == '') {
						$hinta = $hinta * (1 + ($trow['alv'] / 100));
					}

					// Pyöristetään hinta haluttuun tarkkuuteen
					$hinta = round($hinta, $yhtiorow['hintapyoristys']);
				}

				$aleperuste["ale".$alepostfix] = 5;
				$ale["ale".$alepostfix] = 0;
				$netto = "";
				$hintaperuste = 10;
				$aperuste .= "Katemyyntihinta. ";
			}
		}

		// Jos joku aletasoista on tyhjä tai kokonaan settaamatta, niin lähdetään etsimään alennuksia
		$jatka = FALSE;

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			if (isset($ale["ale".$alepostfix]) and !is_numeric($ale["ale".$alepostfix])) {
				// Jos ale on tyhjä niin unsetataan se
				unset($ale["ale".$alepostfix]);
				$jatka = TRUE;
			}

			if (!isset($ale["ale".$alepostfix])) {
				$jatka = TRUE;
			}
		}

		if ($jatka and $netto != 'N' and $netto != 'E') {

			// 5. asiakas.tunnus/asiakas.ytunnus tuote.tuotenumero aleprosentti (asiakkaan tuotteen alennus)
			$query = "	(SELECT '1' prio, alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, tunnus
						FROM asiakasalennus asale1 USE INDEX (yhtio_asiakas_tuoteno)
						WHERE yhtio = '$kukarow[yhtio]'
						and asiakas = '$laskurow[liitostunnus]'
						and asiakas > 0
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
						and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and alennus >= 0
						and alennus <= 100)
						UNION
						(SELECT '2' prio, alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, tunnus
						FROM asiakasalennus asale2 USE INDEX (yhtio_ytunnus_tuoteno)
						WHERE yhtio = '$kukarow[yhtio]'
						and ytunnus = '$laskurow[ytunnus]'
						and ytunnus != ''
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
						and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and alennus >= 0
						and alennus <= 100)
						ORDER BY alennuslaji, prio, minkpl desc, aika, alennus desc, tunnus desc";
			$hresult = pupe_query($query);

			list($ale, $aleperuste, $aperuste) = settaa_ale($ale, $aleperuste, $aperuste, $hresult, 5, "Asiakkaan tuotteen alennus");

			// 6. asiakas.tunnus/asiakas.ytunnus tuote.aleryhmä aleprosentti (asiakkaan tuotealeryhmän alennus)
			if (jatka_ale($ale)) {
				$query = "	(SELECT '1' prio, alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, tunnus
							FROM asiakasalennus asale1 USE INDEX (yhtio_asiakas_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas = '$laskurow[liitostunnus]'
							and asiakas > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100)
							UNION
							(SELECT '2' prio, alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, tunnus
							FROM asiakasalennus asale2 USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100)
							ORDER BY alennuslaji, prio, minkpl desc, aika, alennus desc, tunnus desc";
				$hresult = pupe_query($query);

				list($ale, $aleperuste, $aperuste) = settaa_ale($ale, $aleperuste, $aperuste, $hresult, 6, "Asiakkaan tuotealeryhmän alennus");
			}

			// 7. asiakas.segmentti tuote.tuoteno aleprosentti (asiakassegmentin tuotteen alennus)
			if (jatka_ale($ale) and isset($alehi_assegmenttirow["tunnukset"])) {
				$query = "	SELECT alennus, alennuslaji
							FROM asiakasalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_segmentti in ($alehi_assegmenttirow[tunnukset])
							and asiakas_segmentti != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and ytunnus = ''
							and asiakas = 0
							and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100
							ORDER BY alennuslaji, minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc";
				$hresult = pupe_query($query);

				list($ale, $aleperuste, $aperuste) = settaa_ale($ale, $aleperuste, $aperuste, $hresult, 7, "Asiakassegmentin tuotteen alennus");
			}

			// 8. asiakas.ryhmä tuote.tuoteno aleprosentti (asiakasryhmän tuotteen alennus)
			if (jatka_ale($ale)) {
				$query = "	SELECT alennus, alennuslaji
							FROM asiakasalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_ryhma = '$alehi_asrow[ryhma]'
							and asiakas_ryhma != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and ytunnus = ''
							and asiakas = 0
							and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100
							ORDER BY alennuslaji, minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc";
				$hresult = pupe_query($query);

				list($ale, $aleperuste, $aperuste) = settaa_ale($ale, $aleperuste, $aperuste, $hresult, 8, "Asiakasryhmän tuotteen alennus");
			}

			// 9. asiakas.piiri tuote.tuoteno aleprosentti (asiakaspiirin tuotteen alennus)
			if (jatka_ale($ale)) {
				$query = "	SELECT alennus, alennuslaji
							FROM asiakasalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and piiri = '$alehi_asrow[piiri]'
							and piiri != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and ytunnus = ''
							and asiakas = 0
							and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100
							ORDER BY alennuslaji, minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc";
				$hresult = pupe_query($query);

				list($ale, $aleperuste, $aperuste) = settaa_ale($ale, $aleperuste, $aperuste, $hresult, 9, "Asiakaspiirin tuotteen alennus");
			}

			// 10. asiakas.segmentti tuote.aleryhmä aleprosentti (asiakassegmentin tuotealeryhmän alennus)
			if (jatka_ale($ale) and isset($alehi_assegmenttirow["tunnukset"])) {
				$query = "	SELECT alennus, alennuslaji
							FROM asiakasalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_segmentti in ($alehi_assegmenttirow[tunnukset])
							and asiakas_segmentti != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ytunnus = ''
							and asiakas = 0
							and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100
							ORDER BY alennuslaji, minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc";
				$hresult = pupe_query($query);

				list($ale, $aleperuste, $aperuste) = settaa_ale($ale, $aleperuste, $aperuste, $hresult, 10, "Asiakassegmentin tuotealeryhmän alennus");
			}

			// 11. asiakas.ryhmä tuote.aleryhmä aleprosentti (asiakasryhmän tuotealeryhmän alennus)
			if (jatka_ale($ale)) {
				$query = "	SELECT alennus, alennuslaji
							FROM asiakasalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_ryhma = '$alehi_asrow[ryhma]'
							and asiakas_ryhma != ''
							and ryhma  = '$trow[aleryhma]'
							and ryhma != ''
							and ytunnus = ''
							and asiakas = 0
							and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100
							ORDER BY alennuslaji, minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc";
				$hresult = pupe_query($query);

				list($ale, $aleperuste, $aperuste) = settaa_ale($ale, $aleperuste, $aperuste, $hresult, 11, "Asiakasryhmän tuotealeryhmän alennus");
			}

			// 12. asiakas.piiri tuote.aleryhmä aleprosentti (asiakaspiirin tuotealeryhmän alennus)
			if (jatka_ale($ale)) {
				$query = "	SELECT alennus, alennuslaji
							FROM asiakasalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and piiri = '$alehi_asrow[piiri]'
							and piiri != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and ytunnus = ''
							and asiakas = 0
							and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100
							ORDER BY alennuslaji, minkpl desc, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc";
				$hresult = pupe_query($query);

				list($ale, $aleperuste, $aperuste) = settaa_ale($ale, $aleperuste, $aperuste, $hresult, 12, "Asiakaspiirin tuotealeryhmän alennus");
			}

			// 13. tuote.aleryhmä aleprosentti (tuotealeryhmän perusalennus) (Vain ykköstason alennus voidaan tallentaa tähän)
			if (!isset($ale["ale1"])) {

				$query = "	SELECT alennus
							FROM perusalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and alennus >= 0
							and alennus <= 100";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$aleperuste["ale1"] = 13;

					$hrow = mysql_fetch_assoc($hresult);
					$ale["ale1"]  = $hrow["alennus"];
					$aperuste .= " Tuotealeryhmän perusalennus (Aletaso: 1).";
				}
			}
		}

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			if (!isset($ale["ale".$alepostfix])) {
				$ale["ale".$alepostfix] = 0;
			}

			if ($ale["ale".$alepostfix] > 100) {
				$ale["ale".$alepostfix] = 100;
			}

			if ($ale["ale".$alepostfix] < 0) {
				$ale["ale".$alepostfix] = 0;
			}
		}

		$debug = 0;
		if ($debug == 1) echo t("Tulin tulokseen").": $aperuste.  ALE: ",var_dump($ale),"% ".t("HINTA")." $hinta $yhtiorow[valkoodi] KPL:$kpl<br><br>";

		if ($yhtio != '') {
			$yhtiorow = hae_yhtion_parametrit($vanha_yhtio);
			$kukarow['yhtio'] 			= $vanha_yhtio;
			$laskurow['liitostunnus'] 	= $vanha_liitostunnus;
			$laskurow['ytunnus'] 		= $vanha_ytunnus;
		}

		if ($trow["myyntihinta_maara"] != 0) {
			$hinta = round($hinta / $trow["myyntihinta_maara"], $yhtiorow["hintapyoristys"]);
		}

		if ($palautus != "") {

			$ret = array();
			$a = explode(",", $palautus);

			if (!is_array($a)) {
				$a = array($palautus);
			}

			foreach ($a as $palauta) {

				$palauta = trim($palauta);

				if (substr($palauta, 0, 3) == 'ale' and is_numeric($palauta{strlen($palauta) - 1})) {
					extract($ale);
				}

				if (isset(${$palauta})) {
					$ret[$palauta] = ${$palauta};
				}
				else {
					echo "<font class='error'>Muuttujaa '$palauta' ei voida palauttaa!</font><br>";
					$ret[$palauta] = FALSE;
				}
			}

			return $ret;
		}
		else {
			return array($hinta, $netto, $ale, $alehinta_alv, $alehinta_val);
		}
	}
}

if (!function_exists("generoi_alekentta_php")) {
	function generoi_alekentta_php ($row, $tyyppi, $tapa = '', $ei_erikoisale = '') {
		global $yhtiorow;

		if ($row["netto"] == "N") {
			if ($tapa == 'erikseen') {
				$ale = array();
				$ale["ale1"] = 0;
			}
			elseif ($tapa == 'plain') {
				return 0;
			}
			else {
				return 1;
			}
		}

		// Ei erikoisaletta
		if ($ei_erikoisale != "") {
			$row["erikoisale"] = 0;
		}

		if ($tyyppi == 'M') {
			$maara = $yhtiorow['myynnin_alekentat'];
		}
		elseif ($tyyppi == 'O')  {
			$maara = $yhtiorow['oston_alekentat'];
		}
		else {
			$maara = 1;
		}

		if ($tapa == 'erikseen') {
			$ale = array();

			for ($alepostfix = 1; $alepostfix <= $maara; $alepostfix++) {
				if ($alepostfix == 1) {
					if ($row["ale{$alepostfix}"] != 0) {
						$ale["ale{$alepostfix}"] = round((1 - (1 - $row["ale{$alepostfix}"] / 100) * (1 - $row["erikoisale"] / 100)) * 100, 2);
					}
					else {
						$ale["ale{$alepostfix}"] = round($row["erikoisale"], 2);
					}
				}
				else {
					$ale["ale{$alepostfix}"] = round($row["ale{$alepostfix}"], 2);
				}
			}
		}
		elseif ($tapa == 'plain') {
			$ale = 1;

			for ($alepostfix = 1; $alepostfix <= $maara; $alepostfix++) {
				$ale *= (1 - $row["ale{$alepostfix}"] / 100);
			}

			$ale *= (1 - $row["erikoisale"] / 100);

			$ale = round((1 - $ale) * 100, 2);
		}
		elseif ($tapa == 'kerto') {
			$ale = 1;

			for ($alepostfix = 1; $alepostfix <= $maara; $alepostfix++) {
				$ale *= (1 - $row["ale{$alepostfix}"] / 100);
			}

			$ale *= (1 - $row["erikoisale"] / 100);

			$ale = round($ale, 4);
		}

		return $ale;
	}
}

if (!function_exists("generoi_alekentta_select")) {
	function generoi_alekentta_select($tapa, $tyyppi) {
		global $yhtiorow;

		// lasketaanko kentät yhteen vai palautetaanko ne erikseen
		// $tapa = 'yhteen' tai $tapa = 'erikseen'
		// otetaan huomioon aina erikoisale

		if ($tyyppi == 'M') {
			$maara = $yhtiorow['myynnin_alekentat'];
		}
		elseif ($tyyppi == 'O')  {
			$maara = $yhtiorow['oston_alekentat'];
		}
		else {
			$maara = 1;
		}

		// jos halutaan käyttää jotain omaa tilausrivin taulun nimeä
		$prefix = 'tilausrivi';

		if ($tapa == 'erikseen') {

			$return = '';

			for ($i = 1; $i <= $maara; $i++) {
				$return .= " {$prefix}.ale{$i},";
			}
		}
		elseif ($tapa == 'yhteen') {
			$query_1 = $query_2 = '';

			for ($i = 1; $i <= $maara; $i++) {

				if ($i == 1) {
					$query_1 = "{$prefix}.ale{$i}";
					$query_2 = "if({$prefix}.ale{$i}=0, 1, {$prefix}.ale{$i})";
				}
				else {
					$query_1 .= " + {$prefix}.ale{$i}";
					$query_2 .= " * if({$prefix}.ale{$i}=0, 1, {$prefix}.ale{$i})";
				}
			}

			$return = "round((1 - (1 - ({$query_1} + {$prefix}.erikoisale - ({$query_2} * if({$prefix}.erikoisale=0, 1, {$prefix}.erikoisale) / 100)) / 100)) * 100, 2)";
		}

		return $return;
	}
}

if (!function_exists("generoi_alekentta")) {
	function generoi_alekentta ($tyyppi, $prefix = '', $ei_erikoisale = '') {
		global $yhtiorow;

		if ($tyyppi == 'M') {
			$maara = $yhtiorow['myynnin_alekentat'];
		}
		elseif ($tyyppi == 'O')  {
			$maara = $yhtiorow['oston_alekentat'];
		}
		else {
			$maara = 1;
		}

		$query_1 = $query_2 = '';
		$ale = '';

		// jos halutaan käyttää jotain omaa tilausrivin taulun nimeä
		$prefix = trim($prefix) != '' ? trim($prefix) : 'tilausrivi';

		for ($alepostfix = 1; $alepostfix <= $maara; $alepostfix++) {
			if ($alepostfix > 1) {
				$ale .= "*";
			}

			$ale .= "(1 - {$prefix}.ale{$alepostfix} / 100)";
		}

		if ($ei_erikoisale == "") {
			$ale .= "* (1 - {$prefix}.erikoisale / 100)";
		}

		$return = "if ({$prefix}.netto='N', 1, {$ale})";

		return $return;
	}
}

if (!function_exists("alehinta_osto")) {
	function alehinta_osto ($laskurow, $trow, $kpl, $netto, $hinta, $ale) {
		global $yhtiorow, $kukarow;

		// Tämä rutiini määrittelee riville hinnan ja alennuksen
		// siihen tarvitaan:
		// $laskurow[] (laskun tiedot)
		// $trow[] (select * from tuote)
		// $kpl tilatava määrä
		// $netto = N jos halutaan nettohinta
		// $hinta käyttäjän syöttämä hinta
		// $ale käyttäjän syöttämä ale - array
		// $debug (jos 1 niin näytetään tulos)

		// Tulokset on:
		// $hinta
		// $netto
		// $ale
		// $valuutta

		// Otetaan $trow ja tehdään siitä vähän isompi. liitetään tuotteen_toimittaja taulu siihen. Saadaan isompi otanta dataa
		$query = "	SELECT tuote.*, tt.toim_tuoteno, tt.osto_era, tt.pakkauskoko, tt.ostohinta, tt.valuutta, tt.osto_alv
					FROM tuote
					JOIN tuotteen_toimittajat as tt on (tt.yhtio = tuote.yhtio and tuote.tuoteno = tt.tuoteno and tt.liitostunnus = '$laskurow[liitostunnus]')
					WHERE tuote.yhtio='{$kukarow["yhtio"]}'
					AND tuote.tuoteno = '{$trow["tuoteno"]}'";
		$result = pupe_query($query);

		// poistetaan vanha ja uusi tilalle
		unset($trow);
		$trow = mysql_fetch_assoc($result);

		// Varmistetaan, että määrä on ok
		$kpl = (float) $kpl;
		if ($kpl == 0) $kpl = 1;

		$aperuste 		= "";
		$hintaperuste	= FALSE;
		$aleperuste		= FALSE;

		// oletetaan yhtiön valuutta jos sitä ei tiedetä
		if ($laskurow["valkoodi"] == "") $laskurow["valkoodi"] = $yhtiorow["valkoodi"];

		// oletetaan tuotteen alvi ja valuutta
		$valuutta = $yhtiorow["valkoodi"];

		// haetaan toimittajan tiedot
		$query = "	SELECT *
					FROM toimi
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus  = '$laskurow[liitostunnus]'";
		$alhire = pupe_query($query);

		if (mysql_num_rows($alhire) == 1) {
			$alehi_torow = mysql_fetch_assoc($alhire);
		}
		else {
			$alehi_torow = array();
			$aperuste .= t("Toimittajaa ei löytynyt").". ";
		}

		// 1. käyttäjän syöttämä hinta/nettohinta
		if ($hinta != '') {
			$hintaperuste = 1;

			// nettohinta jos netto-kentässä tulee N tai E
			if ($netto == 'N' or $netto == 'E') {
				$aperuste .= "Käyttäjän antama nettohinta ";
				for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
					$ale["ale".$alepostfix] = 0;
				}
			}
			else {
				$aperuste .= "Käyttäjän antama hinta ";
			}

			$valuutta = $laskurow["valkoodi"];

			if (trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
				$hinta = round(yhtioval($hinta, $laskurow["vienti_kurssi"]), 6);
				$valuutta = $yhtiorow["valkoodi"];
			}
		}
		elseif ($hinta == '') {

			$hinta = 0;

			// 2A. toimi.toimittaja/toimi.ytunnus tuote.tuotenumero nettohinta (toimittajan tuotteen hinta) laskun valuutassa
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM toimittajahinta tohin1 USE INDEX (yhtio_toimittaja_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and toimittaja = '$laskurow[liitostunnus]'
							and toimittaja > 0
							and tuoteno  = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							UNION
							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM toimittajahinta tohin2 USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							ORDER BY prio, minkpl desc, aika, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					else {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}

					$hinta         	= round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste     	.= "Toimittajan tuotteen nettohinta laskun valuutassa. ";
					$valuutta		= $laskurow["valkoodi"];
					$hintaperuste	= 2;
				}
			}

			// 2B. toimi.toimittaja/asiakas.ytunnus tuote.tuotenumero nettohinta (toimittajan tuotteen hinta) yhtiön valuutassa
			if ($hinta == 0) {

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
							FROM toimittajahinta tohin1 USE INDEX (yhtio_toimittaja_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and toimittaja = '$laskurow[liitostunnus]'
							and toimittaja > 0
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							UNION
							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
							FROM toimittajahinta tohin2 USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and tuoteno = '$trow[tuoteno]'
							and tuoteno != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							ORDER BY prio, minkpl desc, aika, valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					else  {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}

					$hinta		 = $hrow["hinta"];
					$aperuste	.= "Toimittajan tuotteen nettohinta.".$hrow['laji'];
					$hintaperuste = 2;
					$valuutta	= $yhtiorow["valkoodi"];
				}
			}

			// 3A. toimi.tunnus/toimi.ytunnus tuote.aleryhmä nettohinta (toimittajan tuotealeryhmän hinta) laskun valuutassa
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM toimittajahinta tohin1 USE INDEX (yhtio_toimittaja_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and toimittaja = '$laskurow[liitostunnus]'
							and toimittaja > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							UNION
							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, tunnus
							FROM toimittajahinta tohin2 USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi = '$laskurow[valkoodi]'
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							ORDER BY prio, minkpl desc, aika, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					else {
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}

					$hinta        = round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]), 6);
					$aperuste    .= "Toimittajan tuotealeryhmän nettohinta laskun valuutassa. ";
					$valuutta	= $laskurow["valkoodi"];
					$hintaperuste = 3;
				}
			}

			// 3B. toimi.tunnus/toimi.ytunnus tuote.aleryhmä nettohinta (toimittajan tuotealeryhmän hinta) yhtiön valuutassa
			if ($hinta == 0) {

				$query = "	(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
							FROM toimittajahinta tohin1 USE INDEX (yhtio_toimittaja_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and toimittaja = '$laskurow[liitostunnus]'
							and toimittaja > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							UNION
							(SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
							FROM toimittajahinta tohin2 USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and valkoodi in ('$yhtiorow[valkoodi]','')
							and (minkpl <= $kpl or minkpl = 0)
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
							ORDER BY prio, minkpl desc, aika, valkoodi DESC, tunnus desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);

					if ($hrow["laji"] == "N" or $hrow["laji"] == "E") {
						$netto 	= $hrow["laji"];
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
							$ale["ale".$alepostfix] = 0;
						}
					}
					else {
						//  ($hrow["laji"] == "B")
						$netto	= '';
						for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
							if (isset($ale["ale".$alepostfix])) unset($ale["ale".$alepostfix]);
						}
					}

					$hinta		= $hrow["hinta"];
					$aperuste	.= "Toimittajan tuotealeryhmän nettohinta. ";
					$hintaperuste = 3;
					$valuutta	= $yhtiorow["valkoodi"];
				}
			}

			// 4. laskun valuutalla, omalla tuotenumerolla
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	SELECT *
							FROM hinnasto
							WHERE hinnasto.yhtio  = '$kukarow[yhtio]'
							and hinnasto.tuoteno  = '$trow[tuoteno]'
							and hinnasto.tuoteno != ''
							and hinnasto.laji = 'O'
							and hinnasto.valkoodi = '$laskurow[valkoodi]'
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), maa desc
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) != 0) {
					$hrow			= mysql_fetch_assoc($hresult);
					$hinta 			= $hrow["hinta"];
					$netto			= $hrow["laji"];
					$aperuste 	   .= "Laskun valuutalla, omalla tuotenumerolla ";
					$valuutta		= $hrow["valkoodi"];
					$hintaperuste 	= 4;
				}
			}

			// 5. yhtiön valuutalla, omalla tuotenumerolla
			if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {

				$query = "	SELECT *
							FROM hinnasto
							WHERE hinnasto.yhtio  = '$kukarow[yhtio]'
							and hinnasto.tuoteno  = '$trow[tuoteno]'
							and hinnasto.tuoteno != ''
							and hinnasto.laji = 'O'
							and hinnasto.valkoodi in ('$yhtiorow[valkoodi]','')
							and ((hinnasto.alkupvm <= current_date and if (hinnasto.loppupvm = '0000-00-00','9999-12-31',hinnasto.loppupvm) >= current_date) or (hinnasto.alkupvm='0000-00-00' and hinnasto.loppupvm='0000-00-00'))
							and ((hinnasto.minkpl <= '$kpl' and hinnasto.maxkpl >= '$kpl') or (hinnasto.minkpl = 0 and hinnasto.maxkpl = 0))
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(hinnasto.alkupvm),9999999999999), hinnasto.valkoodi DESC
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) != 0) {
					$hrow			= mysql_fetch_assoc($hresult);
					$hinta 			= $hrow["hinta"];
					$netto			= $hrow["laji"];
					$aperuste 	   .= "Yhtiön valuutalla, omalla tuotenumerolla ";
					$valuutta		= $hrow["valkoodi"];
					$hintaperuste 	= 5;
				}
			}

			// 6. tuotteen toimittajan ostohinta
			if ($hinta == 0) {
					$hinta 			= $trow["ostohinta"];
					$netto			= "";
					$valuutta		= $trow["valuutta"];
					$aperuste 	   .= "Tuotteen toimittajan ostohinta ";
					$hintaperuste 	= 6;
			}
		}

		// Käydään läpi käsin syötetyt alennukset jos niitä on
		for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
			if (isset($ale["ale".$alepostfix]) and substr($ale["ale".$alepostfix], -1) == "+") {
				// 1. käyttäjän syöttämä EURO-määräinen alennus
				$aleperuste["ale".$alepostfix] = 1;

				$hinta_xxx = $hinta + substr($ale["ale".$alepostfix],0,-1);
				$ale["ale".$alepostfix] = (1 - ($hinta/$hinta_xxx))*100;
				$hinta = $hinta_xxx;

				$aperuste .= " Käyttäjän syöttämä EURO-määräinen ale (Aletaso: $alepostfix)";
			}
			elseif (isset($ale["ale".$alepostfix]) and substr($ale["ale".$alepostfix], -1) == "-") {
				// 2. käyttäjän syöttämä EURO-määräinen alennus
				$aleperuste["ale".$alepostfix] = 2;

				$hinta_xxx = $hinta - substr($ale["ale".$alepostfix],0,-1);
				$ale["ale".$alepostfix] = (1 - ($hinta_xxx/$hinta))*100;

				$aperuste .= " Käyttäjän syöttämä EURO-määräinen ale (Aletaso: $alepostfix)";
			}
			elseif (isset($ale["ale".$alepostfix]) and is_numeric($ale["ale".$alepostfix]) and $ale["ale".$alepostfix] > 0) {
				// 3. käyttäjän syöttämä alennus
				$aleperuste["ale".$alepostfix] = 3;

				$aperuste .= " Käyttäjän syöttämä ale (Aletaso: $alepostfix)";
			}
			elseif (isset($ale["ale".$alepostfix]) and is_numeric($ale["ale".$alepostfix]) and $ale["ale".$alepostfix] == 0) {
				// 4. käyttäjän syöttämä alennus
				$aleperuste["ale".$alepostfix] = 4;

				$aperuste .= " Ei alennusta (Aletaso: $alepostfix)";
			}
		}

		// Jos joku aletasoista on tyhjä tai kokonaan settaamatta, niin lähdetään etsimään alennuksia
		$jatka = FALSE;

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
			if (isset($ale["ale".$alepostfix]) and !is_numeric($ale["ale".$alepostfix])) {
				// Jos ale on tyhjä niin unsetataan se
				unset($ale["ale".$alepostfix]);
				$jatka = TRUE;
			}

			if (!isset($ale["ale".$alepostfix])) {
				$jatka = TRUE;
			}
		}

		if ($jatka and $netto != 'N' and $netto != 'E') {

			// 5. asiakas.tunnus/asiakas.ytunnus tuote.tuotenumero aleprosentti (toimittajan tuotteen alennus)
			$query = "	(SELECT '1' prio, alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, tunnus
						FROM toimittajaalennus asale1 USE INDEX (yhtio_toimittaja_tuoteno)
						WHERE yhtio = '$kukarow[yhtio]'
						and toimittaja = '$laskurow[liitostunnus]'
						and toimittaja > 0
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
						and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and alennus >= 0
						and alennus <= 100)
						UNION
						(SELECT '2' prio, alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, tunnus
						FROM asiakasalennus asale2 USE INDEX (yhtio_ytunnus_tuoteno)
						WHERE yhtio = '$kukarow[yhtio]'
						and ytunnus = '$laskurow[ytunnus]'
						and ytunnus != ''
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
						and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and alennus >= 0
						and alennus <= 100)
						ORDER BY alennuslaji, prio, minkpl desc, aika, alennus desc, tunnus desc";
			$hresult = pupe_query($query);

			list($ale, $aleperuste, $aperuste) = settaa_ale($ale, $aleperuste, $aperuste, $hresult, 9, "Toimittajan tuotteen alennus");

			// 6. asiakas.tunnus/asiakas.ytunnus tuote.aleryhmä aleprosentti (toimittajan tuotealeryhmän alennus)
			if (jatka_ale($ale)) {
				$query = "	(SELECT '1' prio, alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, tunnus
							FROM toimittajaalennus asale1 USE INDEX (yhtio_toimittaja_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and toimittaja = '$laskurow[liitostunnus]'
							and toimittaja > 0
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100)
							UNION
							(SELECT '2' prio, alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, tunnus
							FROM toimittajaalennus asale2 USE INDEX (yhtio_ytunnus_tuoteno)
							WHERE yhtio = '$kukarow[yhtio]'
							and ytunnus = '$laskurow[ytunnus]'
							and ytunnus != ''
							and ryhma = '$trow[aleryhma]'
							and ryhma != ''
							and (minkpl = 0 or (minkpl <= $kpl and monikerta = '') or (mod($kpl, minkpl) = 0 and monikerta != ''))
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus >= 0
							and alennus <= 100)
							ORDER BY alennuslaji, prio, minkpl desc, aika, alennus desc, tunnus desc";
				$hresult = pupe_query($query);

				list($ale, $aleperuste, $aperuste) = settaa_ale($ale, $aleperuste, $aperuste, $hresult, 10, "Toimittajan tuotealeryhmän alennus");
			}

			// 7. Tuotteen toimittajan oletusalennus (Vain ykköstason alennus voidaan tallentaa tähän)
			if (!isset($ale["ale1"])) {

				$query = "	SELECT alennus
							FROM tuotteen_toimittajat
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$trow[tuoteno]'
							and liitostunnus = '$laskurow[liitostunnus]'
							and alennus > 0
							LIMIT 1";
				$hresult = pupe_query($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_assoc($hresult);
					$ale["ale1"]  = $hrow["alennus"];
					$aperuste .= " Tuotteen toimittajan oletusalennus (Aletaso: 1).";
				}
			}
		}

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
			if (!isset($ale["ale".$alepostfix])) {
				$ale["ale".$alepostfix] = 0;
			}

			if ($ale["ale".$alepostfix] > 100) {
				$ale["ale".$alepostfix] = 100;
			}

			if ($ale["ale".$alepostfix] < 0) {
				$ale["ale".$alepostfix] = 0;
			}
		}

		$debug = 0;
		if ($debug == 1) echo t("Tulin tulokseen").": $aperuste.  ALE: ",var_dump($ale),"% ".t("HINTA")." $hinta $yhtiorow[valkoodi] KPL:$kpl<br><br>";

		if ($laskurow["valkoodi"] != $valuutta) {
			$hinta = laskuval($hinta, $laskurow["vienti_kurssi"]);
		}

		return array($hinta, $netto, $ale, $valuutta);
	}
}

if (!function_exists("kalenteritapahtuma")) {
	function kalenteritapahtuma ($tyyppi, $tapa, $viesti, $liitostunnus, $kuittaus="", $henkilo="", $otunnus="", $pvmalku='now()', $pvmloppu='now()', $asentaja='') {
		global $yhtiorow, $kukarow;

		if (trim($asentaja) == '') {
			$query = "SELECT * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus'";
			$result = pupe_query($query);
			$asrow = mysql_fetch_assoc($result);
		}
		else {
			$asrow['ytunnus'] = '';
		}

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

		if (trim($asentaja) != '') {
			$kysely .= ", kentta02 = '$liitostunnus', pvmloppu = $pvmloppu ";
		}

 		$result = pupe_query($kysely);
	}
}

if (!function_exists('ebid')) {
	function ebid($lasku_tunnus, $url_only = false) {
		global $kukarow, $yhtiorow, $palvelin2;

		$query = "SELECT * from lasku where tunnus=" . (int) $lasku_tunnus . " and yhtio='$kukarow[yhtio]'";
		$res = pupe_query($query);
		$laskurow = mysql_fetch_assoc($res);

		if ($laskurow['ebid'] != "" and $laskurow['ebid'] != "FINVOICEXML" and $laskurow['ebid'] != "TECCOM-INVOICE" and $laskurow['ebid'] != "UNIKKO-INVOICE") {
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

			// Ei näytetä INVOIC.D.96A xml-linkkiä
			$query = "	SELECT tunnus
						from liitetiedostot
						where liitostunnus = '$laskurow[tunnus]'
						and liitos = 'lasku'
						and kayttotarkoitus != 'INVOIC.D.96A'
						and yhtio='$kukarow[yhtio]'";
			$res = pupe_query($query);

			while ($row = mysql_fetch_assoc($res)) {
				$out .= " <a href='".$palvelin2."view.php?id=$row[tunnus]' target='Attachment'>". t('Näytä liite') ."</a> ";
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

			$query = "	SELECT tunnus
						from liitetiedostot
						where liitostunnus = '$laskurow[tunnus]'
						and liitos = 'lasku'
						and yhtio = '$kukarow[yhtio]'";
			$res = pupe_query($query);

			while ($row = mysql_fetch_assoc($res)) {
				$out .= " <a href='".$palvelin2."view.php?id=$row[tunnus]' target='Attachment'>". t('Näytä liite') ."</a> ";
			}

			return $out;
	    }
		else {

			$query = "	SELECT tunnus
						from liitetiedostot
						where liitostunnus = '$laskurow[tunnus]'
						and liitos = 'lasku'
						and yhtio = '$kukarow[yhtio]'";
			$res = pupe_query($query);

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
					$out .= "<a href='".$palvelin2."view.php?id=$row[tunnus]' target='Attachment'>". t('Näytä lasku') ."</a> ";
				}
			}

			if ($laskurow['ebid'] == "UNIKKO-INVOICE") {
				return t('Unikkolasku');
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
	function sarjanumeron_ostohinta($kentta, $arvo, $eikululaskuja="", $pvm="") {
		global $kukarow, $yhtiorow;

		// Funktio laskee yhden kappaleen ostohinnan
		$ostohinta = 0;

		// Tuotteen ostohinta
		$query = "	SELECT
					group_concat(distinct tilausrivi.tunnus) tunnukset,
					group_concat(distinct sarjanumeroseuranta.tunnus) sarjatunnukset,
					count(distinct tilausrivi.tunnus) tunnukset_kpl
					FROM sarjanumeroseuranta
					JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
					WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					and sarjanumeroseuranta.$kentta = '$arvo'";
		$otsores = pupe_query($query);
		$ostorow = mysql_fetch_assoc($otsores);

		if ($ostorow["tunnukset"] != '') {

			$query_ale_lisa_m = generoi_alekentta('M');
			$query_ale_lisa_o = generoi_alekentta('O');

			$query = "	SELECT
						sum(tilausrivi.rivihinta/tilausrivi.kpl) ostosumma,
						sum(tilausrivi.hinta * if(tilausrivi.tyyppi = 'O', {$query_ale_lisa_o}, {$query_ale_lisa_m})) ostosumma_eiloppulaskettu
						FROM tilausrivi
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tunnus  IN ($ostorow[tunnukset])";
			$sarjares = pupe_query($query);
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
				$keikkares = pupe_query($query);

				while ($kulukeikkarow = mysql_fetch_assoc($keikkares)) {
					// Haetaan kaikki keikkaan liitettyjen laskujen summa
					$query = "	SELECT sum(arvo*vienti_kurssi) kulusumma
								FROM lasku
								WHERE yhtio		= '$kukarow[yhtio]'
								and tila 		IN ('K','X')
								and laskunro 	= '$kulukeikkarow[laskunro]'
								and vanhatunnus <> 0
								and vienti in ('B','E','H')";
					$result = pupe_query($query);
					$kulukulurow = mysql_fetch_assoc($result);

					$ostohinta	+= $kulukulurow["kulusumma"];
				}
			}

			$pvmlisa = " now() ";

			if ($pvm != "") {
				$pvmlisa = " '$pvm' ";
			}

			// Onko tuotteen varastonarvoa muutettu käsin (epäkurantti.php)
			$query = "	SELECT sum(arvomuutos) arvomuutos
						FROM sarjanumeroseuranta_arvomuutos
						WHERE yhtio		= '$kukarow[yhtio]'
						and sarjanumerotunnus IN ({$ostorow['sarjatunnukset']})
						and luontiaika <= $pvmlisa";
			$result = pupe_query($query);
			$epakurrow = mysql_fetch_assoc($result);

			if ($epakurrow['arvomuutos'] != 0) {
				$ostohinta += $epakurrow['arvomuutos'];
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
		$keikkares = pupe_query($query);

		while($kulukeikkarow = mysql_fetch_assoc($keikkares)) {
			// Haetaan kaikki keikkaan liitettyjen laskujen summa
			$query = "	SELECT sum(arvo*vienti_kurssi) kulusumma
						FROM lasku
						WHERE yhtio		= '$kukarow[yhtio]'
						and tila 		IN ('K','X')
						and laskunro 	= '$kulukeikkarow[laskunro]'
						and vanhatunnus <> 0";
			$result = pupe_query($query);
			$kulukulurow = mysql_fetch_assoc($result);

			$ostohinta	+= $kulukulurow["kulusumma"];
		}

	    return round($ostohinta, 6);
	}
}

if (!function_exists("remove_duplicates")) {
	function remove_duplicates($table, $yhtio) {

		$query  = "describe $table";
		$fieldresult = pupe_query($query);

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
		$result = pupe_query($query);

		while ($row = mysql_fetch_assoc($result)) {
			$query = "DELETE FROM $table WHERE yhtio = '$yhtio' and tunnus in ($row[tunnukset]) LIMIT ".($row["countkpltahti"]-1);
			$delresult = pupe_query($query);
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
	function verkkolasku_luo_keikkafile ($tunnus, $trow, $rtuoteno) {
		global $yhtiorow, $kukarow;

		// Tarvitaan $tunnus jossa on ostoreskontralaskun tunnus
		// Tarvitaan $trow jossa on toimittajan tiedot
		// Tarvitaan $rtuoteno jossa on array kaikista ostoreskontralaskun tuotteista

		require("inc/verkkolasku-in-luo-keikkafile.inc");
	}
}

if (!function_exists("verkkolasku_luo_liitosotsikko")) {
	function verkkolasku_luo_liitosotsikko ($laskutunnus, $otunnus) {
		global $yhtiorow, $kukarow;

		// Tarvitaan $laskutunnus jossa on ostoreskontralaskun tunnus
		// Tarvitaan $otunnus jossa on keikan tunnus

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
			$result = pupe_query($query);
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
					$result = pupe_query($query);

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
										$res = pupe_query($query);
									}
								}
							}

							if ($updquery != "") {
								$query = "UPDATE $taulu SET $updquery WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$row[tunnus]'";
								$updres = pupe_query($query);
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
	function tulosta_ytunnus($ytunnus, $maa = "", $vienti = "") {
		global $yhtiorow, $kukarow;

		if ($maa == "") {
			$maa = $yhtiorow["maa"];
		}

		//jos on suomalainen yritys tehdään ytunnus nätiks
		if (strtoupper($maa) == 'FI') {

			$ytunnus = preg_replace("/[^0-9]/", "", $ytunnus); // poistetaan kaikki paitsi numerot

			//muutetaan ytunnus takas oikean näköseks
			$ytunpit = 8-strlen($ytunnus);

			if ($ytunpit > 0) {
				$uytunnus = $ytunnus;

				while ($ytunpit > 0) {
				    $uytunnus = "0".$uytunnus;
					$ytunpit--;
				}
			}
			else {
				$uytunnus = $ytunnus;
			}

			if ($vienti == "VATNUMERO") {
				$uytunnus = strtoupper($maa).$uytunnus;
			}
			elseif ($vienti != "") {
				$uytunnus = strtoupper($maa).$uytunnus;
			}
			else {
				$uytunnus = substr($uytunnus,0,7)."-".substr($uytunnus,7,1);
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

				document.getElementById(id).innerHTML = response;

				<?php if (stripos($_SERVER['HTTP_USER_AGENT'], "MSIE") === FALSE and stripos($_SERVER['HTTP_USER_AGENT'], "EXPLORER") === FALSE) { ?>
					var container = document.getElementById(id);
					var allNewScripts = container.getElementsByTagName('script');
					c = allNewScripts.length;

					if (c>0) {
						for (x=0;x<=c;x++) {
							if (allNewScripts[x]) {
								eval(allNewScripts[x].innerHTML);
							}
						}
					}
				<?php }	?>

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
			http.open('post', urli + '&ohje=off&no_css=yes&no_head=yes', true);
			http.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			http.send(data);
		}

		this.doGet = function() {
			http.open('get', urli + '&' + data + '&ohje=off&no_css=yes&no_head=yes');
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
			document.getElementById(id).style.display = '';
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

	function livesearch_blurhandler(id) {
		if ( document.activeElement ) hf = document.activeElement;
		else hf = document.focusNode;

		if (hf.id != id) {

			var selectOptions = document.getElementsByName('selectOptions'+id);

			for (var iEl = 0; iEl < selectOptions.length; iEl++) {
				if (document.getElementById(selectOptions[iEl].id).className != '') {
					return false;
				}
			}

			document.getElementById('livesearch_'+id).style.visibility = 'hidden';
		}
	}

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

				if (selectOptions[keyStrokeIndex].id) document.getElementById(selectOptions[keyStrokeIndex].id).scrollIntoView();
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
				if (list.length > 0) {

					for(i=0;i<=list.length;i++){
						if (list[i].type == "checkbox") {
							if (list[i].name.substr(0, name.length) == name) {
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

if (!function_exists("js_openFormInNewWindow")) {
	function js_openFormInNewWindow() {
		?>
		<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">
			function js_openFormInNewWindow(formID, name) {

				if (!name) {
					name='Pupesoft'+Math.floor(Math.random()*1001);
				}

				formi = document.getElementById(formID);

				if (name == "samewindow") {
					formi.target='';

					formi.submit();
				}
				else {
					thistee = formi.elements["tee"].value;
					formi.elements["tee"].value = "NAYTATILAUS";

					window.open('', name, 'top=100,left=100,resizable=yes');

					formi.target=name;

					formi.submit();

					formi.elements["tee"].value = thistee;
				}
			}
		</SCRIPT>
		<?php
	}
}

if (!function_exists("js_open_yllapito")) {
	function js_open_yllapito() {
		global $palvelin2;

		?>
		<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">
			function js_open_yllapito(tdid, parametrit){

				var posX, posY;
				var offsetX=30, offsetY=30;

				if (typeof window.screenLeft != "undefined"){ //IE
					posX = window.screenLeft + offsetX;
					posY = window.screenTop + offsetY;
				}
				else if (typeof window.screenX != "undefined"){ //NS/Moz
					posX = window.screenX + offsetX;
					posY = window.screenY + offsetY;
				}
				else { //default - center of screen
					posX = screen.availWidth/2 - w/2;
					posY = screen.availHeight/2 - h/2;
				}

				newwindow=window.open('<?php echo $palvelin2; ?>yllapito.php?'+parametrit+'&js_open_yp='+tdid+'&uusi=1&ohje=off', 'Pupesoft_popup', 'width=800, height=600, top='+posY+', left='+posX+', scrollbars=no, resizable=yes');
			}
		</SCRIPT>
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
		return "id = \"$id\" style=\"max-width: {$leveys}px; width: expression(if (parseInt(document.getElementById('$id').clientWidth) > $leveys) { {$leveys}px });\" onmousedown=\"var browser = navigator.appName.toUpperCase(); var loytyyko = browser.indexOf('EXPLORER'); if (loytyyko >= 0) {document.getElementById('$id').style.width = 'auto';}\" onblur=\"var browser = navigator.appName.toUpperCase(); var loytyyko = browser.indexOf('EXPLORER'); if (loytyyko >= 0 && $leveys > 0) {cwidth = document.getElementById('$id').clientWidth; if (cwidth > $leveys) { document.getElementById('$id').style.width = '$leveys'+'px';} else {document.getElementById('$id').style.width = 'auto';}}\"";
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
		tarkenne=teksti.substr((i+2));
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
				x = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
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
	        toolTip.css({'top':y, 'left':x}).delay(500).show('fast');
	    }

	    function toolTipHide() {
			toolTip.clearQueue();
			toolTip.hide();
	    }

	    //events for each element
	    this.each(function() {

	        $(this).mousemove(function(e){
				var x = getMouseX(e) + 20;
				var y = getMouseY(e) + 20;

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
	            if (allowHtml) {
	                toolTip.html(content);
				}
	            else {
	                toolTip.text(content);
				}

	            //remove all classes for the tipBox before add a new one and to avoid the 'append class'
	            toolTip.removeClass();

	            //set class if specified
	            if (className) {
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

	<?php

	echo "</script>";

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
	function tallenna_muisti($nimitys, $exclude = "", $kuka = "") {
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
		$data = mysql_real_escape_string(serialize($data));

		$kuka = trim($kuka) != '' ? mysql_real_escape_string($kuka) : $kukarow['kuka'];

		$query = "	REPLACE INTO tallennetut_parametrit SET
					yhtio		= '$kukarow[yhtio]',
					kuka		= '$kuka',
					nimitys		= '$nimitys',
					sovellus	= '$_SERVER[SCRIPT_NAME]',
					data		= '$data',
					laatija		= '$kukarow[kuka]',
					luontiaika  = now()";
		$result = pupe_query($query);

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
		$result = pupe_query($query);

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
		$result = pupe_query($query);

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
				$result = pupe_query($query);
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
		$result = pupe_query($query);

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
				$result = pupe_query($query);

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
						$result = pupe_query($query);

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
							$result = pupe_query($query);
						}
					}
				}
			}

			$aja_kysely = $uusi_kysely;

			if ($aja_kysely != "tmpquery") {
				echo "<br><font class='message'>".t("Tallennettiin kysely")." $aja_kysely</font><br>";
			}
			return true;
		}
		//	Poistetaan kysely
		elseif ($poista_kysely != "" and $aja_kysely != "") {
			$query = "	DELETE
						FROM muisti
						WHERE yhtio='$kukarow[yhtio]' and haku = '$hakukysely' and kuka = '$kukarow[kuka]' and nimi = '$aja_kysely'";
			$result = pupe_query($query);

			echo "<br><font class='message'>".t("Poistettiin kysely")." $aja_kysely</font><br>";

			return false;
		}
		//	Ajetaan kysely muistista
		elseif ($aja_kysely != "") {
			//	Onko tämä arvo jo tallennettu?
			$query = "	SELECT *
						FROM muisti
						WHERE yhtio='$kukarow[yhtio]' and haku = '$hakukysely' and kuka = '$kukarow[kuka]' and nimi = '$aja_kysely'";
			$result = pupe_query($query);
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
				echo "<br><font class='message'>".t("Suoritetaan kysely")." $aja_kysely</font><br>";
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
		$result = pupe_query($query);
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

		list($usec, $sec) = explode(" ", microtime());

		$nimi      = $usec+$sec; // uniikki nimi
		$upfileall = ""; 		 // palautus
		$identify  = array();

		$alkuperainen_kuva = $_FILES[$upfile1];

		$path_parts = pathinfo($alkuperainen_kuva["name"]);
		$ext = strtolower($path_parts['extension']);

		$image = getimagesize($alkuperainen_kuva["tmp_name"]);	// lähetetty kuva
		$leve  = $image[0];
		$kork  = $image[1];

		if ($ykoko > 0 or $xkoko > 0) {
			/* skaaltaanko x:n vai y:n mukaan, jos xkoko tai ykoko == 0, skaalataan != 0 mukaan */
			if ($ykoko > 0 and $kork > $ykoko) {
				$uusi_kuva = strtolower("/tmp/$nimi"."1.".$ext);

				// Haetaan kuvan väriprofiili
				exec("nice -n 20 identify -format %[colorspace] \"$alkuperainen_kuva[tmp_name]\"", $identify);

				$colorspace = "sRGB";
				if ($identify[0] != "") $colorspace = $identify[0];

				// skaalataan kuva oikenakokoiseksi y:n mukaan
		    	exec("nice -n 20 convert -resize x$ykoko -quality 90 -colorspace $colorspace -strip \"$alkuperainen_kuva[tmp_name]\" \"$uusi_kuva\"");
		    }
			elseif ($xkoko > 0 and $leve > $xkoko) {
				$uusi_kuva = strtolower("/tmp/$nimi"."1.".$ext);

				// Haetaan kuvan väriprofiili
				exec("nice -n 20 identify -format %[colorspace] \"$alkuperainen_kuva[tmp_name]\"", $identify);

				$colorspace = "sRGB";
				if ($identify[0] != "") $colorspace = $identify[0];

				// skaalataan kuva oikenakokoiseksi x:n mukaan
		  		exec("nice -n 20 convert -resize $xkoko -quality 90 -colorspace $colorspace -strip \"$alkuperainen_kuva[tmp_name]\" \"$uusi_kuva\"");
		    }
			else {
				return $upfile1;
		    }

			$uusnimi = $dirri.$alkuperainen_kuva["name"];

			if (!rename($uusi_kuva, $uusnimi)) {
		    	echo "Siirto epäonnistui $uusi_kuva -> $uusnimi<br>";
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

		if (isset($_FILES[$userfile]) and is_array($_FILES[$userfile])) {
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

		$data 		= mysql_real_escape_string(file_get_contents($file["tmp_name"]));
		$filename	= mysql_real_escape_string($file["name"]);
		$filetype	= mysql_real_escape_string($file["type"]);
		$filesize	= mysql_real_escape_string($file["size"]);
		$selite		= mysql_real_escape_string($selite);

		//	Tarkastetaan, että kuvatunnus on oikea..
		if ($tunnus > 0) {
			$query = "	SELECT tunnus FROM liitetiedostot WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				$tunnus = 0;
			}
		}

		//	Onko meillä jokin kuvatyyppi?
		if (substr($filetype, 0, 5) == "image") {
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
			$result = pupe_query($query);
			$id = $tunnus;
		}
		else {
			if ((int) $jarjestys == 0) {
				$query = "	SELECT max(jarjestys) jarjestys
							FROM liitetiedostot
							WHERE yhtio 		= '$kukarow[yhtio]'
							and liitos   		= '$liitos'
							and liitostunnus 	= '$liitostunnus'";
				$result = pupe_query($query);
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
			$result = pupe_query($query);
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
			return "<font class='error'>".t("VIRHE: Tiedosto on tyhjä")."</font><br><br>";
		}
		elseif ($file["error"] == 0) {

			//	Paketti riittävän pieni mysql:lle
			$query = "SHOW variables like 'max_allowed_packet'";
			$result = pupe_query($query);
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
						return "<font class='error'>".t("VIRHE: Tiedostomuoto $ext ei kelpaa, sallitut tiedostomuodot on %s ja %s", $kieli, implode(", ", array_slice($sallitut_tiedostot, 0, -1)), end($sallitut_tiedostot)).".</font><br><br>";

					}
					else {
						return "<font class='error'>".t("VIRHE: Tiedostomuoto $ext ei kelpaa, sallittu tiedostomuoto on %s", $kieli, $sallitut_tiedostot[0]).".</font><br><br>";
					}
				}
			}
			else {
				return "<font class='error'>".t("VIRHE: Ladattu tiedosto oli liian suuri! Suurin sallittu tiedostokoko on %s", $kieli, size_readable($file["size"]))."!</font><br><br>";
			}
		}
		elseif ($file["error"] == 1 or $file["error"] == 2) {
			return "<font class='error'>".t("VIRHE: Tiedosto on liian suuri!")."!</font><br><br>";
		}
		elseif ($file["error"] == 3) {
			return "<font class='error'>".t("VIRHE: Tiedoston lataus epäonnistui!")."!</font><br><br>";
		}
		elseif ($file["error"] == 4) {
			return true;
		}
		elseif ($file["error"] == 7) {
			return "<font class='error'>".t("VIRHE: Palvelinasetuksissa on virhe!")."!</font><br><br>";
		}
		else {
			return "<font class='error'>".t("VIRHE: Tapahtui virhe tallennettaessa tiedostoa!")."!</font><br><br>";
		}
	}
}

if (!function_exists('hae_liite')) {
	function hae_liite($tunnus, $liitos, $palautus="") {
		global $kukarow, $yhtiorow;

		$query = "select * from liitetiedostot where tunnus='$tunnus' and liitos = '$liitos'";
		$liiteres = pupe_query($query);

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
		global $yhtiorow;

		// otetaan sisään lähetysmaan ja määrämaan maakoodi
		// palautetaan:
		// tyhjää = ei tarvita intrastatata
		// -1 = kuuluu vienti-ilmoitukseen
		// -2 = kuuluu tuonti-ilmoitukseen

		$maa_lahetys 	= strtoupper(trim($maa_lahetys));
		$maa_maara 		= strtoupper(trim($maa_maara));
		$yhtiorow_maa 	= strtoupper($yhtiorow["maa"]);
		$ultilno 		= "";

		// kokeillaan arpoa intrastat käsittelyä, molemmat maat pitää olla EU maita
		if ($maa_lahetys != "" and $maa_maara != "") {
			$query = "SELECT DISTINCT koodi FROM maat WHERE koodi in ('$maa_lahetys','$maa_maara') AND eu = 'ON'";
			$result = pupe_query($query);

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

if (!function_exists("palauta_intrastat_tiedot")) {
	function palauta_intrastat_tiedot($laskurow, $varasto, $teeupdate = FALSE) {
		global $yhtiorow, $kukarow;

		if ($varasto > 0) {
			$query = "SELECT * from varastopaikat where yhtio = '$kukarow[yhtio]' and tunnus = '$varasto'";
			$varaston_res = pupe_query($query);
			$varaston_row = mysql_fetch_assoc($varaston_res);
		}

		if (!isset($varaston_row["maa"]) or $varaston_row["maa"] == "") {
			$varaston_row = array();
			$varaston_row["maa"] = $yhtiorow["maa"];
		}

		$laskurow["ultilno"] = tarvitaanko_intrastat($varaston_row["maa"], $laskurow["toim_maa"]);

		$laskurow["maa_maara"] = $laskurow["toim_maa"];
		$laskurow["maa_lahetys"] = $varaston_row["maa"];

		if ($laskurow["ultilno"] == '-1') {
			// vienti-ilmoitus
			// katotaan onko jotain oletuksia asiakkaan takaa
			$query  = "	SELECT *
						from asiakas
						where yhtio = '$kukarow[yhtio]'
						and tunnus  = '$laskurow[liitostunnus]'";
			$toiresult = pupe_query($query);
			$aputoimirow = mysql_fetch_assoc($toiresult);

			$laskurow["aktiivinen_kuljetus"] 				= $aputoimirow["aktiivinen_kuljetus"];
			$laskurow["aktiivinen_kuljetus_kansallisuus"]	= $aputoimirow["aktiivinen_kuljetus_kansallisuus"];
			$laskurow["bruttopaino"] 						= $aputoimirow["bruttopaino"];
			$laskurow["kauppatapahtuman_luonne"] 			= $aputoimirow["kauppatapahtuman_luonne"];
			$laskurow["kontti"]								= $aputoimirow["kontti"];
			$laskurow["kuljetusmuoto"]						= $aputoimirow["kuljetusmuoto"];
			$laskurow["lisattava_era"] 						= $aputoimirow["lisattava_era"];
			$laskurow["poistumistoimipaikka"] 				= $aputoimirow["poistumistoimipaikka"];
			$laskurow["poistumistoimipaikka_koodi"] 		= $aputoimirow["poistumistoimipaikka_koodi"];
			$laskurow["sisamaan_kuljetus"]					= $aputoimirow["sisamaan_kuljetus"];
			$laskurow["sisamaan_kuljetusmuoto"]  			= $aputoimirow["sisamaan_kuljetusmuoto"];
			$laskurow["sisamaan_kuljetus_kansallisuus"]		= $aputoimirow["sisamaan_kuljetus_kansallisuus"];
			$laskurow["vahennettava_era"] 					= $aputoimirow["vahennettava_era"];
		}
		elseif ($laskurow["ultilno"] == '-2') {
			// tuonti-ilmoitus
			// katotaan onko jotain oletuksia toimitustavan takana
			$query  = "	SELECT *
						from toimitustapa
						where yhtio = '$kukarow[yhtio]'
						and selite = '$laskurow[toimitustapa]'";
			$toiresult = pupe_query($query);
			$aputoimirow = mysql_fetch_assoc($toiresult);

			$laskurow["aktiivinen_kuljetus"] 				= $aputoimirow["aktiivinen_kuljetus"];
			$laskurow["aktiivinen_kuljetus_kansallisuus"]	= $aputoimirow["aktiivinen_kuljetus_kansallisuus"];
			$laskurow["bruttopaino"] 						= $aputoimirow["bruttopaino"];
			$laskurow["kauppatapahtuman_luonne"] 			= $aputoimirow["kauppatapahtuman_luonne"];
			$laskurow["kontti"]								= $aputoimirow["kontti"];
			$laskurow["kuljetusmuoto"]						= $aputoimirow["kuljetusmuoto"];
			$laskurow["lisattava_era"] 						= $aputoimirow["lisattava_era"];
			$laskurow["poistumistoimipaikka"] 				= $aputoimirow["poistumistoimipaikka"];
			$laskurow["poistumistoimipaikka_koodi"] 		= $aputoimirow["poistumistoimipaikka_koodi"];
			$laskurow["sisamaan_kuljetus"]					= $aputoimirow["sisamaan_kuljetus"];
			$laskurow["sisamaan_kuljetusmuoto"]  			= $aputoimirow["sisamaan_kuljetusmuoto"];
			$laskurow["sisamaan_kuljetus_kansallisuus"]		= $aputoimirow["sisamaan_kuljetus_kansallisuus"];
			$laskurow["vahennettava_era"] 					= $aputoimirow["vahennettava_era"];
		}
		elseif ($varaston_row["maa"] == $laskurow["toim_maa"] and $laskurow["toim_maa"] != "") {
			// intrastattia ei lähetetä tulliin kun varasto ja toimitusosoite on samassa maassa
			$laskurow["kauppatapahtuman_luonne"] = '999';
		}

		if ($teeupdate) {
			$query = "	UPDATE lasku set
						ultilno 							= '$laskurow[ultilno]',
						aktiivinen_kuljetus 				= '$laskurow[aktiivinen_kuljetus]',
						aktiivinen_kuljetus_kansallisuus	= '$laskurow[aktiivinen_kuljetus_kansallisuus]',
						bruttopaino 						= '$laskurow[bruttopaino]',
						kauppatapahtuman_luonne 			= '$laskurow[kauppatapahtuman_luonne]',
						kontti								= '$laskurow[kontti]',
						kuljetusmuoto						= '$laskurow[kuljetusmuoto]',
						lisattava_era 						= '$laskurow[lisattava_era]',
						maa_lahetys							= '$laskurow[maa_lahetys]',
						maa_maara							= '$laskurow[maa_maara]',
						poistumistoimipaikka 				= '$laskurow[poistumistoimipaikka]',
						poistumistoimipaikka_koodi 			= '$laskurow[poistumistoimipaikka_koodi]',
						sisamaan_kuljetus					= '$laskurow[sisamaan_kuljetus]',
						sisamaan_kuljetusmuoto  			= '$laskurow[sisamaan_kuljetusmuoto]',
						sisamaan_kuljetus_kansallisuus		= '$laskurow[sisamaan_kuljetus_kansallisuus]',
						vahennettava_era 					= '$laskurow[vahennettava_era]'
						where yhtio = '$kukarow[yhtio]'
						and tunnus  = '$laskurow[tunnus]'";
			$varresult = pupe_query($query);
		}

		return $laskurow;
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
			$result = pupe_query($query);

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

					if ($limit > 49) {
						break;
					}
				}

				echo "<div style='padding-left:2px;padding-bottom:5px;padding-top:5px;'><font class='info'>".t("Haulla löytyi")." ".mysql_num_rows($result). " ",t("tiliä")," ".t("näytetään")." 50 ".t("tarkinta osumaa").".</font></div>";
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
		global $yhtiorow, $kukarow, $palvelin2, $verkkokauppa;

		$tee = $_REQUEST["livesearch_tee"];
		$nimi = $_REQUEST["livesearch_nimi"];
		$formi = $_REQUEST["livesearch_form"];
		$hakuid = $_REQUEST["livesearch_hakuid"];
		$submit = $_REQUEST["livesearch_submit"];
		$haku = trim($_REQUEST["livesearch_haku"]);

		if (mb_detect_encoding($haku, mb_detect_order(), TRUE) == "UTF-8") {
			$haku = iconv("UTF-8", "latin1//TRANSLIT", $haku);
		}

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

			// vientikieltokäsittely:
			// +maa tarkoittaa että myynti on kielletty tähän maahan ja sallittu kaikkiin muihin
			// -maa tarkoittaa että ainoastaan tähän maahan saa myydä
			// eli näytetään vaan tuotteet jossa vienti kentässä on tyhjää tai -maa.. ja se ei saa olla +maa
			$kieltolisa = "";
			unset($vierow);

			if ($kukarow["kesken"] > 0) {
				$query  = "	SELECT if (toim_maa != '', toim_maa, maa) maa
							FROM lasku
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus  = '$kukarow[kesken]'";
				$vieres = pupe_query($query);
				$vierow = mysql_fetch_array($vieres);
			}
			elseif ($verkkokauppa != "") {
				$vierow = array();

				if ($maa != "") {
					$vierow["maa"] = $maa;
				}
				else {
					$vierow["maa"] = $yhtiorow["maa"];
				}
			}

			if (isset($vierow) and $vierow["maa"] != "") {
				$kieltolisa .= " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
			}

			if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
				if ($verkkokauppa != "") {
					if ($kukarow["kuka"] == "www") {
						$kieltolisa .= " and tuote.hinnastoon = 'W' ";
					}
					else {
						$kieltolisa .= " and tuote.hinnastoon in ('W','V') ";
					}
				}
				else {
					$kieltolisa .= " and tuote.hinnastoon != 'E' ";
				}
			}

			// Näytetään poistuvat tuotteet mutta ei poistettuja
			if ($yhtiorow['livetuotehaku_poistetut'] == 'X' or $yhtiorow['livetuotehaku_poistetut'] == 'Y') {
				$naytetaanko_poistetut = "and (tuote.status != 'P' or (tuote.status = 'P' and (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0))";
			}
			else {
				$naytetaanko_poistetut = "and tuote.status != 'P'";
			}
			$query = "	SELECT tuote.tunnus, tuote.tuoteno, tuote.nimitys, tuote.kuvaus, tuote.osasto, tuote.try, tuote.epakurantti25pvm, tuote.epakurantti50pvm,tuote.epakurantti75pvm,tuote.epakurantti100pvm
						FROM tuote
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						$naytetaanko_poistetut
						$kieltolisa
						$haku_query";
			$result = pupe_query($query);

			$limit = 0;
			if (mysql_num_rows($result) > 0) {

				while ($row = mysql_fetch_assoc($result)) {

					if ($yhtiorow['livetuotehaku_poistetut'] == 'Y' and ($row["epakurantti25pvm"] != 0000-00-00 or $row["epakurantti50pvm"] != 0000-00-00 or $row["epakurantti75pvm"] != 0000-00-00 or $row["epakurantti100pvm"] != 0000-00-00) ) {
						$tyyli = "font-weight:bold;color:red;";
						$tyyli2 = "font-weight:normal;color:red;";
					}
					else {
						$tyyli = "font-weight:bold'";
						$tyyli2 = "font-weight:normal'";
					}

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
								<font style='$tyyli'>$row[tuoteno]</font> &raquo; <font style='$tyyli2'>$row[nimitys]</font>
							</div><div name='selectValues$hakuid' id='$row[tuoteno]'></div>";
					$limit++;

					if ($limit > 49) {
						break;
					}
				}

				echo "<div style='padding-left:2px;padding-bottom:5px;padding-top:5px;'><font class='info'>".t("Haulla löytyi")." ".mysql_num_rows($result). " ".t("tuotetta").", ".t("näytetään")." 50 ".t("tarkinta osumaa").".</font></div>";
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
			$result = pupe_query($query);

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

					if ($limit > 49) {
						break;
					}
				}

				echo "<div style='padding-left:2px;padding-bottom:5px;padding-top:5px;'><font class='info'>",t("Haulla löytyi")," ".mysql_num_rows($result). " ",t("käyttäjää"),", ".t("näytetään")." 50 ".t("tarkinta osumaa").".</font></div>";
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

if (!function_exists("livesearch_asiakashaku")) {
	function livesearch_asiakashaku() {
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

			foreach ($haku_array as $haku) {
				$haku = mysql_real_escape_string($haku);
				$haku_query .= " and (asiakas.ytunnus like ('%$haku%') or asiakas.asiakasnro like ('%$haku%') ";
				$haku_query .= " or asiakas.ovttunnus like ('%$haku%%') or asiakas.nimi like ('%$haku%') or asiakas.nimitark like ('%$haku%') or asiakas.postitp like ('%$haku%') ";
				$haku_query .= " or asiakas.toim_ovttunnus like ('%$haku%%') or asiakas.toim_nimi like ('%$haku%') or asiakas.toim_nimitark like ('%$haku%') or asiakas.toim_postitp like ('%$haku%') ";
				$haku_query .= " or asiakas.kolm_ovttunnus like ('%$haku%%') or asiakas.kolm_nimi like ('%$haku%') or asiakas.kolm_nimitark like ('%$haku%') or asiakas.kolm_postitp like ('%$haku%') ";
				$haku_query .= " or asiakas.laskutus_nimi like ('%$haku%') or asiakas.laskutus_nimitark like ('%$haku%') or asiakas.laskutus_postitp like ('%$haku%')) ";
			}

			$query = "	SELECT *, if (toim_nimi != nimi and toim_nimi != '', concat(nimi, ' ', nimitark, ' / ', toim_nimi, ' ', toim_nimitark), concat(nimi, ' ', nimitark)) asiakkaan_tieto
						FROM asiakas
						WHERE asiakas.yhtio = '$kukarow[yhtio]' and asiakas.laji!='P'
						$haku_query";
			$result = pupe_query($query);

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
								<font style='font-weight:bold'>$row[ytunnus] &raquo; $row[asiakkaan_tieto]</font>
							</div><div name='selectValues$hakuid' id='$row[tunnus]'></div>";
					$limit++;

					if ($limit > 49) {
						break;
					}
				}

				echo "<div style='padding-left:2px;padding-bottom:5px;padding-top:5px;'><font class='info'>",t("Haulla löytyi")," ".mysql_num_rows($result). " ",t("asiakasta"),", ".t("näytetään")." 50 ".t("tarkinta osumaa").".</font></div>";
			}
			else {
				echo "<div style='padding:2px'><font class='message'>".t("Ei asiakkaita.")."</font></div>";
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

if (!function_exists("livesearch_varastohaku")) {
	function livesearch_varastohaku() {
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

			foreach ($haku_array as $haku) {
				$haku = mysql_real_escape_string($haku);
				$haku_query .= "and concat(hyllyalue, hyllynro, hyllyvali, hyllytaso) like '%$haku%'";
			}

			$query = "	SELECT DISTINCT concat(hyllyalue,'-',hyllynro,'-',hyllyvali,'-',hyllytaso) hyllyt, min(tunnus) tunnus
						FROM tuotepaikat
						WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
						$haku_query
						GROUP BY hyllyt";
			$result = pupe_query($query);

			$limit = 0;
			if (mysql_num_rows($result) > 0) {
				while ($row = mysql_fetch_assoc($result)) {
					echo "	<div id='varasto_valinta_$row[tunnus]'
								name='selectOptions$hakuid'
								onmouseover=\"this.className='liveSearchSelectedItem';
									keyStrokeIndex = -1;
									var aEls = document.getElementsByName('selectOptions$hakuid');
									for (var iEl = 0; iEl < aEls.length; iEl++) {
										if (aEls[iEl].id.substring(0,16) == 'varasto_valinta_' && aEls[iEl].id != 'varasto_valinta_$row[tunnus]') {
											document.getElementById(aEls[iEl].id).className='';
										}
									} \"
								onmouseout=\"this.className='';\"
								onClick=\"document.getElementById('$hakuid').value='$row[hyllyt]';";
								if ($submit == "") {
									echo "document.$formi.submit();";
								}
								else {
									echo "document.getElementById('livesearch_$hakuid').style.visibility = 'hidden';";
								}
								echo "\"
								style='padding:2px;'>
								<font style='font-weight:bold'>$row[hyllyt]</font>
							</div><div name='selectValues$hakuid' id='$row[hyllyt]'></div>";
					$limit++;

					if ($limit > 49) {
						break;
					}
				}

				echo "<div style='padding-left:2px;padding-bottom:5px;padding-top:5px;'><font class='info'>",t("Haulla löytyi")," ".mysql_num_rows($result). " ",t("varastoa"),", ".t("näytetään")." 50 ".t("tarkinta osumaa").".</font></div>";
			}
			else {
				echo "<div style='padding:2px'><font class='message'>".t("Ei varastoa.")."</font></div>";
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

		if (stripos($_SERVER['HTTP_USER_AGENT'], "MSIE") !== FALSE or stripos($_SERVER['HTTP_USER_AGENT'], "EXPLORER") !== FALSE) {
			return "<input type='text' id='$hakuID' name='$nimi' autocomplete='off' style='width:".$width."px;' value='$value'>";
		}

		$hakuID = "haku".uniqid();

		return "<input type='text' id='$hakuID' name='$nimi' autocomplete='off' style='width:".$width."px;' value='$value'
					onkeydown=\"return livesearch_keyhandler(event, '$hakuID', '$formi', '$submit');\"
					onkeyup=\"if ((event.keyCode < 37 || event.keyCode > 40) && event.keyCode != 13) {
							sndReq('livesearch_$hakuID', '$_SERVER[SCRIPT_NAME]?livesearch_tee=$tee&livesearch_form=$formi&livesearch_hakuid=$hakuID&livesearch_nimi=$nimi&livesearch_submit=$submit&ohje=off&toim=$toim&livesearch_haku='+this.value);
							keyStrokeIndex = -1;}\"
					onblur=\"setTimeout('livesearch_blurhandler(\'$hakuID\')', 100);\">
				<div style='break:all'></div>
				<div id='livesearch_$hakuID' class='liveSearch' name='livesearch_$hakuID' style='width:".($width * 2)."px; overflow: auto; max-height: 200px;' onmousedown=\"setTimeout('document.getElementById(\'$hakuID\').focus()',10);\"></div>";
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
	function hae_rahtisopimusnumero ($toimitustapa, $ytunnus = "", $asiakastunnus = "") {
		global $kukarow;

		// Pelkällä toimitustavalla haettuna palautetaan firman oma sopimusnumero
		if ($ytunnus == "" and $asiakastunnus == "") {
			$query = "	SELECT sopimusnro rahtisopimus
						FROM toimitustapa
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND selite = '{$toimitustapa}'";
		}
		else {
			$query = "	(SELECT *, '1' prio
						FROM rahtisopimukset
						WHERE toimitustapa = '$toimitustapa'
						AND asiakas = '$asiakastunnus'
						AND asiakas != ''
						AND yhtio = '{$kukarow["yhtio"]}')
						UNION
						(SELECT *, '2' prio
						FROM rahtisopimukset as rahtisopimukset2
						WHERE toimitustapa = '$toimitustapa'
						AND ytunnus = '$ytunnus'
						AND ytunnus != ''
						AND yhtio = '{$kukarow["yhtio"]}')
						ORDER BY prio
						LIMIT 1";
		}
		$rares = pupe_query($query);
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
			return array(0, 0, 0, 0);
		}

		// haetaan ensimmäisen otsikon tiedot
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus IN ($otsikot)
					ORDER BY tunnus
					LIMIT 1";
		$otsre = pupe_query($query);
		$laskurow = mysql_fetch_assoc($otsre);

		$kyht = 0;

		// jos JT-tilaukset annetaan aina rahtivapaasti
		if ($laskurow["clearing"] == "JT-TILAUS" and $yhtiorow["jt_rahti"] == "B") {
			return array(0, 0, 0, 0);
		}

		// jos laskulla on rahtivapaa täppä niin annetaan ilmaseks (paitsi jos kyseessä on JT ja niille halutaan väkisin rahti optiot C tai D)
		if ($laskurow["rahtivapaa"] != "" AND ($laskurow["clearing"] != "JT-TILAUS" OR $yhtiorow["jt_rahti"] != "C") AND ($laskurow["clearing"] != "JT-TILAUS" OR $yhtiorow["jt_rahti"] != "D")) {
			return array(0, 0, 0, 0);
		}

		// Jos rahtituoteno tulee toimitustavan takaa, niin tehdään alennuslaskenta
		$rahtituoteno_toimitustavalta = FALSE;

		// yhtiön rahtikulutuotenumero, ilman tätä ei onnistu
		$query = "  SELECT tuote.*
					FROM tuote
					WHERE yhtio  = '$kukarow[yhtio]'
					AND tuoteno  = '$yhtiorow[rahti_tuotenumero]'
					AND tuoteno != ''";
		$rahti_tuoteno_result = pupe_query($query);

		// Ei löydetty sopivaa tuotetta
		if (mysql_num_rows($rahti_tuoteno_result) == 0) {
			return array(0, 0, 0, 0);
		}
		else {
			$rahti_tuoteno_row = mysql_fetch_assoc($rahti_tuoteno_result);
		}

		// katsotaan onko toimitustavan taakse syötetty rahtimaksutuoteno
		$query = "	SELECT tuote.*
					FROM toimitustapa
					JOIN tuote ON (tuote.yhtio = toimitustapa.yhtio AND tuote.tuoteno = toimitustapa.rahti_tuotenumero)
					WHERE toimitustapa.yhtio = '$kukarow[yhtio]'
					AND toimitustapa.selite  = '$laskurow[toimitustapa]'
					AND toimitustapa.rahti_tuotenumero not in ('','$yhtiorow[rahti_tuotenumero]')";
		$rahti_tuoteno_result = pupe_query($query);

		if (mysql_num_rows($rahti_tuoteno_result) == 1) {
			$rahtituoteno_toimitustavalta = TRUE;
			$rahti_tuoteno_row = mysql_fetch_assoc($rahti_tuoteno_result);
		}

		// jos meillä on automaattinen tuoteella tallennettuihin painoihin perustuva hinnoittelu
		// rahtimaksujen hinnoitteluun otetaan vain tuotteet joita ei myydä erikoisvarastoista (etävarastot joista ei myydä automaattisesti eli tyyppi = E). Tällöin on täpätty erikoistoimitus_myynti on 0 eli false.
		// erikoisvarastoista myydyt tuotteet ovat aina rahtivapaita ja lähettäjä maksaa rahdin.
		if ($yhtiorow["rahti_hinnoittelu"] == 'P') {
			// Jos hyvitysrivi ja tuotteen paino on nolla niin laitetaan tuotteen painoksi yksi gramma, muuten hyvärillekin saattaa tulla rahtia jos kaikkien tuotteiden painot on nolla.
			$query  = "	SELECT sum(if((varattu + kpl + jt) < 0 and tuotemassa = 0, 0.001, tuotemassa) * (varattu + kpl + jt)) massa
						FROM tilausrivi
						JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.erikoistoimitus_myynti = 0)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						AND tilausrivi.tyyppi in ('L', 'E')
						AND tilausrivi.otunnus in ($otsikot)";
			$painoresult = pupe_query($query);
			$painorow = mysql_fetch_assoc($painoresult);
			$kyht = $painorow['massa'];
		}
		// meillä on tilauksen hintaan perustuva hinnoittelu
		elseif ($yhtiorow["rahti_hinnoittelu"] == "o") {

			$query_ale_lisa = generoi_alekentta('M');

			// alvittomat hinnat
			if ($yhtiorow["alv_kasittely"] == '') {
				$query = "	SELECT round(((tilausrivi.varattu + tilausrivi.kpl + tilausrivi.jt) * tilausrivi.hinta * {$query_ale_lisa}) / (1 + tilausrivi.alv / 100), '$yhtiorow[hintapyoristys]') rivihinta, tilausrivi.netto
							FROM tilausrivi
							JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.erikoistoimitus_myynti = 0)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							AND tilausrivi.tyyppi in ('L', 'E')
							AND tilausrivi.otunnus in ($otsikot)";
			}
			else {
				$query = "	SELECT round(((tilausrivi.varattu + tilausrivi.kpl + tilausrivi.jt) * tilausrivi.hinta * {$query_ale_lisa}), '$yhtiorow[hintapyoristys]') rivihinta, tilausrivi.netto
							FROM tilausrivi
							JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.erikoistoimitus_myynti = 0)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							AND tilausrivi.tyyppi in ('L', 'E')
							AND tilausrivi.otunnus in ($otsikot)";
			}
			$presult = pupe_query($query);

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
			$pakre = pupe_query($query);
			$pakka = mysql_fetch_assoc($pakre);

			// Jos rahtikirjaa ei olla syötetty niin resturnataan nollaa
			if (is_null($pakka["kilot"])) {
				return array(0, 0, 0, 0);
			}
			else {
				$kyht = $pakka["kilot"];
			}
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
		$varastoresult = pupe_query($query);

		// jos saadaan joku kilometrimäärä niin laitetaan se muuttujaan talteen
		if (mysql_num_rows($varastoresult) == 1) {
			$varastorow = mysql_fetch_assoc($varastoresult);
			$km = $varastorow['km'];
		}
		else {
			$km = 0;
		}

		// haetaan rahtimaksu hinnan/painon ja/tai etäisyyden mukaan
		$query = "	SELECT rahtihinta
					FROM rahtimaksut
					WHERE toimitustapa = '$laskurow[toimitustapa]'
					AND toimitustapa != ''
					AND kilotalku <= '$kyht'
					AND kilotloppu >= '$kyht'
					AND yhtio = '$kukarow[yhtio]'
					AND ((kmalku <= '$km' and kmloppu >= '$km') or (kmalku = 0 and kmloppu = 0))
					ORDER BY kmloppu DESC, kmalku DESC
					LIMIT 1";
		$rares = pupe_query($query);

		if (mysql_num_rows($rares) == 1) {
			$hirow = mysql_fetch_assoc($rares);
			$ale_kaikki_array = array();

			if ($rahtituoteno_toimitustavalta) {
				// Etsitään spessuhintaa/alennuksia jos toimitustavan takana oli oma rahti_tuotenumero
				list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $rahti_tuoteno_row, 1, '', '', '');

				// Ei asiakashintaa, otetaan hinta rahtimatriisista
				if ((float) $lis_hinta == 0) {
					list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $rahti_tuoteno_row, 1, $lis_netto, $hirow['rahtihinta'], $lis_ale_kaikki);
				}
			}
			else {
				// Yhtiön oletus rahti_tuotenumero
				list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $rahti_tuoteno_row, 1, 'N', $hirow['rahtihinta'], '');
			}

			// Alvit kuntoon
			list($lis_hinta, $alehinta_alv) = alv($laskurow, $rahti_tuoteno_row, $lis_hinta, '', $alehinta_alv);

			return array($lis_hinta, $lis_ale_kaikki, $alehinta_alv, $lis_netto);
		}
		else {
			return array(0, 0, 0, 0);
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
			$ei_pakkaamoa_res = pupe_query($query);
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
		$varastotark_res = pupe_query($query);
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
		$vanhat_res = pupe_query($query);

		if (mysql_num_rows($vanhat_res) > 0) {
			$split_pakkaamo_row = mysql_fetch_assoc($vanhat_res);

			if (isset($update) and $update != '') {
				$query = "	UPDATE lasku SET
							pakkaamo = '$split_pakkaamo_row[pakkaamo]'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus in ($tilausnumero)";
				$pakkaamo_insert_res = pupe_query($query);
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
		$pakkaamotark_res = pupe_query($query);

		if (mysql_num_rows($pakkaamotark_res) == 1) {
			$split_pakkaamo_row = mysql_fetch_assoc($pakkaamotark_res);

			if (isset($update) and $update != '') {
				$query = "	UPDATE lasku SET
							pakkaamo = '$split_pakkaamo_row[tunnus]'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus in ($tilausnumero)";
				$pakkaamo_insert_res = pupe_query($query);
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
			$etsitaan_pakkaamo_res = pupe_query($query);

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
					$paljon_laskuja_lokerossa_res = pupe_query($query);
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
				$pakkaamo_insert_res = pupe_query($query);
				$pakkaamo_insert_row = mysql_fetch_assoc($pakkaamo_insert_res);

				if (isset($update) and $update != '') {
					// päivitetään pakkaamotunnus tilauksille
					$query = "	UPDATE lasku SET
								pakkaamo = '$pakkaamo_insert_row[tunnus]'
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus in ($tilausnumero)";
					$pakkaamo_insert_res = pupe_query($query);
				}

				return $pakkaamo_insert_row['tunnus'];
			}

			return 0;
		}
	}
}

if (!function_exists("laheta_tilausvahvistus")) {
	function laheta_tilausvahvistus($params) {
		global $kukarow, $yhtiorow;

		// Luodaan muuttujat
		foreach ($params as $key => $val) {
			${$key} = $val;
		}

		//
		// LÄHETETÄÄN TILAUSVAHVISTUS
		//
		//

		$tuoteperheetyhdessa = FALSE;

		if ($kukarow['extranet'] != "" and $kukarow['hyvaksyja'] != '') {
			$naytatvale = 2; // jos mellä on tilausvahvistuksessa kakkonen, ei haluta nähä aleja
		}
		elseif (strpos($laskurow['tilausvahvistus'], '8') !== FALSE) {
			$naytatvale = 8; // jos meillä on tilausvahvistuksessa kasi, niin tehdään EAN tilausvahvistus asiakashinnalla ja ei näytetä alennuksia
		}
		elseif (strpos($laskurow['tilausvahvistus'], '7') !== FALSE) {
			$naytatvale = 7; // jos meillä on tilausvahvistuksessa seiska, niin tehdään EAN tilausvahvistus
			if (strpos($laskurow['tilausvahvistus'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($laskurow['tilausvahvistus'], '6') !== FALSE) {
			$naytatvale = 6; // jos meillä on tilausvahvistuksessa kutonen, niin tehdään customoitu tilausvahvistus
			if (strpos($laskurow['tilausvahvistus'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($laskurow['tilausvahvistus'], '5') !== FALSE) {
			$naytatvale = 5; // jos mellä on tilausvahvistuksessa vitonen, niin haetaan hinnat toisesta pupesta
			if (strpos($laskurow['tilausvahvistus'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($laskurow['tilausvahvistus'], '4') !== FALSE) {
			$naytatvale = 4; // jos mellä on tilausvahvistuksessa nelonen, ei haluta nähdä alennuksia, näytetään tilausrivin hinta ja rivihinta
			if (strpos($laskurow['tilausvahvistus'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($laskurow['tilausvahvistus'], '3') !== FALSE) {
			$naytatvale = 3; // jos mellä on tilausvahvistuksessa kolmonen, ei haluta nähdä hintoja, pelkästään kpl-määrät
			if (strpos($laskurow['tilausvahvistus'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($laskurow['tilausvahvistus'], '2') !== FALSE) {
			$naytatvale = 2; // jos mellä on tilausvahvistuksessa kakkonen, ei haluta nähä aleja
			if (strpos($laskurow['tilausvahvistus'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($laskurow['tilausvahvistus'], '1') !== FALSE) {
			$naytatvale = 1; // jos mellä on tilausvahvistuksessa ykkönen, halutaan nähä alet
			if (strpos($laskurow['tilausvahvistus'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '7') !== FALSE) {
			$naytatvale = 7; // jos meillä on tilausvahvistuksessa seiska, niin tehdään EAN tilausvahvistus
			if (strpos($yhtiorow['tilausvahvistustyyppi'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '6') !== FALSE) {
			$naytatvale = 6; // jos meillä on tilausvahvistuksessa kutonen, niin tehdään customoitu tilausvahvistus
			if (strpos($yhtiorow['tilausvahvistustyyppi'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '5') !== FALSE) {
			$naytatvale = 5; // jos mellä on tilausvahvistuksessa vitonen, niin haetaan hinnat toisesta pupesta
			if (strpos($yhtiorow['tilausvahvistustyyppi'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '4') !== FALSE) {
			$naytatvale = 4; // jos mellä on tilausvahvistuksessa nelonen, ei haluta nähdä alennuksia, näytetään tilausrivin hinta ja rivihinta
			if (strpos($yhtiorow['tilausvahvistustyyppi'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '3') !== FALSE) {
			$naytatvale = 3; // jos mellä on tilausvahvistuksessa kolmonen, ei haluta nähdä hintoja, pelkästään kpl-määrät
			if (strpos($yhtiorow['tilausvahvistustyyppi'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '2') !== FALSE) {
			$naytatvale = 2; // jos mellä on tilausvahvistuksessa kakkonen, ei haluta nähä aleja
			if (strpos($yhtiorow['tilausvahvistustyyppi'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		elseif (strpos($yhtiorow['tilausvahvistustyyppi'], '1') !== FALSE) {
			$naytatvale = 1; // jos mellä on tilausvahvistuksessa ykkönen, halutaan nähä alet
			if (strpos($yhtiorow['tilausvahvistustyyppi'], 'Y') !== FALSE) {
				$tuoteperheetyhdessa = TRUE;
			}
		}
		else {
			$naytatvale = 1; // halutaan nähä alet
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "tulostakopio.php") !== FALSE) {
			if (@include("tilauskasittely/tulosta_tilausvahvistus_pdf.inc"));
			else require("tulosta_tilausvahvistus_pdf.inc");
		}
		else {
			if (strpos($laskurow['tilausvahvistus'], 'E') !== FALSE) {
				if (@include("tilauskasittely/tilausvahvistus-edi.inc"));
				else require("tilausvahvistus-edi.inc");
			}

			if (strpos($laskurow['tilausvahvistus'], 'S') !== FALSE or strpos($laskurow['tilausvahvistus'], 'O') !== FALSE) {
				if (@include("tilauskasittely/tilausvahvistus-email.inc"));
				else require("tilausvahvistus-email.inc");
			}

			if (strpos($laskurow['tilausvahvistus'], 'F') !== FALSE) {
				if (@include("tilauskasittely/tilausvahvistus-fax.inc"));
				else require("tilausvahvistus-fax.inc");
			}

			if (strpos($laskurow['tilausvahvistus'], 'U') !== FALSE) {
				if (@include("tilauskasittely/tilausvahvistus-futursoft.inc"));
				else require("tilausvahvistus-futursoft.inc");
			}

			if (strpos($laskurow['tilausvahvistus'], 'K') !== FALSE) {
				$query = "	SELECT komento
							FROM kirjoittimet
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$kukarow[kirjoitin]'";
				$tilvares = pupe_query($query);

				if ($tilvakir = mysql_fetch_assoc($tilvares)) {
					$komento["Tilausvahvistus"] = $tilvakir["komento"];

					$toim_save = $toim;
					if ($toim != "YLLAPITOSOPIMUS") $toim = "TILAUSVAHVISTUS";

					if (@include("tilauskasittely/tulosta_tilausvahvistus_pdf.inc"));
					else require("tulosta_tilausvahvistus_pdf.inc");
					$toim = $toim_save;
				}
			}
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "tulostakopio.php") === FALSE and $yhtiorow['tilausvahvistus_tallenna'] == 'K' and isset($tilausvahvistus_tallenna) and $tilausvahvistus_tallenna != '') {

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
			unlink("/tmp/".basename($tilausvahvistus_tallenna));

			$tilausvahvistus_tallenna = "";
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

		if ($useslave > 0) {
			if (isset($slavedb)) {
				if (isset($slavedb[$useslave]) and $slavedb[$useslave] == '') $useslave = 0;
			}
			else {
				$useslave = 0;
			}
		}

		if (trim($kieli) != '') {
			$indeksi = trim(strtolower($kieli));
		}
		elseif (isset($kukarow["kieli"]) and trim($kukarow["kieli"]) != '') {
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
			$sanakirjaresult = pupe_query($sanakirjaquery, $link);

			if (isset($kaannetyt_sanat) and !in_array($stringi, $kaannetyt_sanat)) $kaannetyt_sanat[] = $stringi;

			if (mysql_num_rows($sanakirjaresult) > 0) {
				$sanakirjarow = mysql_fetch_assoc($sanakirjaresult);

				if (isset($sanakirjarow[$indeksi]) and trim($sanakirjarow[$indeksi]) != '') {
					$stringi = $sanakirjarow[$indeksi];
				}
				else {
					if ($indeksi == 'ru') {
						$stringi = t($stringi,"EN");
					}
					else {
						$stringi = $sanakirjarow["fi"];
					}
				}

				//Päivitetään aikaleima
				if ($verkkokauppa == "") {
					$sanakirjaqueryupd  = "UPDATE sanakirja SET aikaleima=now(), kysytty=kysytty+1 WHERE tunnus='$sanakirjarow[tunnus]'";
					$sanakirjaresultupd = pupe_query($sanakirjaqueryupd, $link);
				}
			}
			elseif ($verkkokauppa == "") {
				$sanakirjaquery  = "INSERT INTO sanakirja SET fi = '$stringi', aikaleima=now(), kysytty=1, laatija='$kukarow[kuka]', luontiaika=now()";
				$sanakirjaresult = pupe_query($sanakirjaquery, $link);
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
		if (func_num_args() > 2) {
		    $arg = func_get_args();

			if (!isset($arg[2])) $arg[2] = "";
			if (!isset($arg[3])) $arg[3] = "";
			if (!isset($arg[4])) $arg[4] = "";
			if (!isset($arg[5])) $arg[5] = "";
			if (!isset($arg[6])) $arg[6] = "";

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
		global $kukarow, $yhtiorow, $link;

		$laji = mysql_real_escape_string($laji);
		$orderlisa = "";

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
		}
		else {
			$kielilisa = "selitetark";
		}

		if ($where == "" and stripos($wherelisa, "ORDER BY") === FALSE) {
			$query = "	SELECT distinct selite
						FROM avainsana
						WHERE $yhtiolisa
						and laji  = '$laji'
						and kieli = '$yhtiorow[kieli]'";
			$result = pupe_query($query, $link);

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

		if ($orderlisa == "" and stripos($wherelisa, "ORDER BY") === FALSE) {
			$orderlisa = " ORDER BY avainsana.jarjestys, avainsana.selite ";
		}

		$kielisubquery = "";

		// Haetaan vieraskieliset vain jos pyydetään vieraskielistä
		if (strtoupper($kieli) != strtoupper($yhtiorow["kieli"])) {
			$kielisubquery = ", IFNULL((SELECT if (avainsana_kieli.$kielilisa = '', NULL, avainsana_kieli.$kielilisa)
								FROM avainsana as avainsana_kieli
								WHERE avainsana_kieli.yhtio = avainsana.yhtio
								and avainsana_kieli.laji = avainsana.laji
								and avainsana_kieli.perhe = avainsana.perhe
								and avainsana_kieli.perhe > 0
								and avainsana_kieli.kieli = '$kieli' LIMIT 1), avainsana.$kielilisa) $kielilisa";
		}

		$query = "	SELECT DISTINCT avainsana.* $kielisubquery
					FROM avainsana
					WHERE $yhtiolisa
					and avainsana.laji = '$laji'
					and avainsana.kieli in ('$yhtiorow[kieli]', '')
					$wherelisa
					$orderlisa";
		$result = pupe_query($query, $link);

		if ($return != "") {
			if (mysql_num_rows($result) > 0) {
				$row = mysql_fetch_assoc($result);
				return $row[$return];
			}
			else {
				return "";
			}
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

		if ($kieli == "") $kieli = $kukarow["kieli"];
		if ($kieli == "") $kieli = $yhtiorow["kieli"];

		//	Jos kielet matchaa niin palautetaan suoraan vastaus
		if (strtoupper($kieli) == strtoupper($yhtiorow["kieli"])) {
			if (is_array($row)) {
				return $row[$laji];
			}
			else {
				return $row;
			}
		}

		// Jos tyhjää niin unohdetaan koko juttu
		if ($laji == "" or (is_array($row) and !isset($row["tunnus"]))) {
			return;
		}

		// Jos tunnusta ei tule niin koklataan oisko tämä suoraan se käännettävä stringi
		if (!is_array($row)) {
			if ($avainsanalaji == "TOIMTAPAKV") {
				$query = "	SELECT *
							FROM toimitustapa
							WHERE yhtio = '$kukarow[yhtio]'
							and selite = '$row'";
				$sresult = pupe_query($query);

				if (mysql_num_rows($sresult) == 1) {
					$row = mysql_fetch_assoc($sresult);
				}
				else {
					return $row;
				}
			}
		}

		// Tutkitaan onko meillä käännös
		$query = "	SELECT *
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]'
					and kieli = '$kieli'
					and laji = '$avainsanalaji'
					and selite = '$row[tunnus]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$row = mysql_fetch_assoc($result);
			// jos löydettiin käännös palautetaan se
			if ($laji == "pakkauskuvaus") {
				return $row["selitetark_2"];
			}
			else {
				return $row["selitetark"];
			}
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

		if ($kieli == "" and isset($kukarow["kieli"])) $kieli = $kukarow["kieli"];
		if ($kieli == "") $kieli = $yhtiorow["kieli"];

		//	Jos kielet matchaa niin palautetaan suoraan vastaus
		if (strtoupper($kieli) == strtoupper($yhtiorow["kieli"]) and isset($tuoterow[$laji])) {
			return $tuoterow[$laji];
		}

		// Tutkitaan onko meillä käännös
		$query = "	SELECT *
					FROM tuotteen_avainsanat USE INDEX (yhtio_kieli_laji_tuoteno)
					WHERE yhtio = '$kukarow[yhtio]'
					and kieli = '$kieli'
					and laji = '$laji'
					and tuoteno = '$tuoterow[tuoteno]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$row = mysql_fetch_assoc($result);
			// jos löydettiin käännös palautetaan se
			if ($row["selitetark"] != "") {
				$palautus = array();
				$palautaus["selite"] = $row["selite"];
				$palautaus["selitetark"] = $row["selitetark"];
			}
			else {
				$palautaus = $row["selite"];
			}
			return $palautaus;
		}

		// muuten palautetaan se mitä kysyttiin
		if (isset($tuoterow[$laji])) {
			return $tuoterow[$laji];
		}
		else {
			// palautetaan laji kun mitäs muutakaan tässä tekisi
			return $laji;
		}
	}
}

if (!function_exists("jt_toimita")) {
	function jt_toimita($toimittaja, $toimittajaid, $varastosta, $jtrivit, $jtrivit_paikat, $automaaginen, $tee, $automaattinen_poiminta = '', $tuotenumero = '', $huomioi_pvm = '') {
		global $yhtiorow, $kukarow, $oikeurow;

		if (!is_array($jtrivit)) $jtrivit = array();
		if (!is_array($jtrivit_paikat)) $jtrivit_paikat = array();

		if ($automaattinen_poiminta != '') {
			$asiakasno 			  = $toimittaja;
			$toimittaja			  = "";
			$asiakasid			  = $toimittajaid;
			$toimittajaid		  = "";
			$asiakasmaa 		  = "";
			$jarj	 			  = "luontiaika";
			$tilaus				  = "";
			$toimi				  = "";
			$suoratoimit		  = "";
			$tilaus_on_jo		  = "KYLLA";
			$suoratoimitus_rivit  = array();
			$suoratoimitus_paikat = array();
			$from_varastoon_inc   = "";
			$jt_huomioi_pvm		  = $huomioi_pvm;
		}
		else {
			$asiakasno 			  = "";
			$asiakasid			  = "";
			$asiakasmaa 		  = "";
			$jarj	 			  = "luontiaika";
			$tilaus				  = "";
			$toimi				  = "";
			$suoratoimit		  = "";
			$tilaus_on_jo		  = "";
			$suoratoimitus_rivit  = $jtrivit;
			$suoratoimitus_paikat = $jtrivit_paikat;
			$from_varastoon_inc   = "varastoon.inc";
			$jt_huomioi_pvm		  = $huomioi_pvm;
		}

		require('jtselaus.php');
	}
}

if (!function_exists("hae_yhtion_parametrit")) {
	function hae_yhtion_parametrit($yhtio) {
		global $kukarow, $konserni_yhtiot, $konsernivarasto_yhtiot;

		$query = "	SELECT *
					FROM yhtio
					WHERE yhtio = '$yhtio'";
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
			$result = pupe_query($query);

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

			if ($yhtion_parametritrow['hintapyoristys'] < 2 or $yhtion_parametritrow['hintapyoristys'] > 6) {
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
		global $kukarow, $yhtiorow, $PHP_SELF, $palvelin2, $pupe_root_polku, $toim;

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
								window.open('$PHP_SELF?ohje=off&toim=$toim&liite_popup_toiminto=AK&tuotetunnus='+tuotetunnus, '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=25,top=25, width='+myWidth+', height='+myHeight);
							}
							else {
								window.open('$PHP_SELF?ohje=off&toim=$toim&liite_popup_toiminto=AK&tuotetunnus='+tuotetunnus+'&width='+maxwidth, '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=25,top=25, width='+maxwidth+', height='+totalheight);
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
						if (kayttotarkoitus='TK', 0, if (kayttotarkoitus='MU', 1, 2)) sorttaus,
						jarjestys
						FROM liitetiedostot
						WHERE yhtio 		= '$kukarow[yhtio]'
						AND liitos 			= 'tuote'
						AND liitostunnus	= '$tuotetunnus'
						AND kayttotarkoitus != 'TH'
						ORDER BY sorttaus, jarjestys, filename";
			$kuvares = pupe_query($query);

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

			require("inc/footer.inc");
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
								if (kayttotarkoitus='TK', 0, if (kayttotarkoitus='MU', 1, 2)) sorttaus,
								jarjestys
								FROM liitetiedostot
								WHERE yhtio		 = '$kukarow[yhtio]'
								and liitos		 = 'tuote'
								and liitostunnus = '$tuotetunnus'
								AND kayttotarkoitus != 'TH'
								ORDER BY sorttaus, jarjestys, filename";
			$filetype_result = pupe_query($filetype_query);

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
		elseif ($toiminto == "TH") {

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
								if (kayttotarkoitus='TK', 0, if (kayttotarkoitus='MU', 1, 2)) sorttaus,
								jarjestys,
								liitostunnus
								FROM liitetiedostot
								WHERE yhtio		 = '$kukarow[yhtio]'
								and liitos		 = 'tuote'
								and liitostunnus = '$tuotetunnus'
								AND kayttotarkoitus != 'TH'
								ORDER BY sorttaus, jarjestys, filename
								LIMIT 1";
			$filetype_result = pupe_query($filetype_query);

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

				$filetype_peukalo_query = "	SELECT tunnus peukalokuva, image_width, image_height
											FROM liitetiedostot
											WHERE yhtio = '$kukarow[yhtio]'
											AND liitos = 'tuote'
											AND liitostunnus = '$filetype_row[liitostunnus]'
											AND kayttotarkoitus = 'TH'
											AND filename = '$filetype_row[filename]'
											ORDER BY tunnus DESC
											LIMIT 1";
				$filetype_result = pupe_query($filetype_peukalo_query);
				$filetype_peukalo_row = mysql_fetch_assoc($filetype_result);

				if ($filetype_peukalo_row["peukalokuva"] > 0) {
					$liitteet  = "<img src='".$palvelin2."view.php?id=$filetype_peukalo_row[peukalokuva]' onClick=\"javascript:liite_popup_js($tuotetunnus, $maxwidth, $totalheight)\"";

					if (!isset($thumb_size_x) or !isset($thumb_size_y)) {

						$uusi_h = 100;
						$uusi_w = 100;

						if ($filetype_peukalo_row['image_width'] > $filetype_peukalo_row['image_height']) {
							$uusi_h = ($filetype_peukalo_row['image_height'] / $filetype_peukalo_row['image_width']) * 100;
						}
						elseif ($filetype_peukalo_row['image_height'] > $filetype_peukalo_row['image_width']) {
							$uusi_w = ($filetype_peukalo_row['image_width'] / $filetype_peukalo_row['image_height']) * 100;
						}

						$liitteet .= " width='$uusi_w' ";
						$liitteet .= " height='$uusi_h' ";
					}

					$liitteet .= ">";
				}
				else {
					$liitteet = "<input type='button' value='";

					if ($pdf_exist) {
						$liitteet .= t("Pdf");
					}
					else {
						$liitteet .= t("Kuva");
					}
					$liitteet .= "' onClick=\"javascript:liite_popup_js($tuotetunnus, $maxwidth, $totalheight)\">";
				}

				return $liitteet;
			}
		}
	}
}

if (!function_exists("lue_kurssit")) {
	function lue_kurssit($file, $handle, $tyyppi = '') {
		global $yhtiorow, $kukarow;

		ini_set("auto_detect_line_endings", 1);

		// luetaan koko file arrayhyn
		$rivit = file($file);

		if ($tyyppi == 2) {
			// eka rivi pois
			array_shift($rivit);
		}
		else {
			// 2 ekaa riviä pois
			array_shift($rivit);
			array_shift($rivit);
		}

		$valuutat = array();

		foreach ($rivit as $rivi) {

			if ($tyyppi == 2) {
				$valuutta      = substr($rivi, 25, 3);																// valuutan nimi
				$vastavaluutta = substr($rivi, 28, 3);																// vastavaluutta
				$kurssipvm_vv  = substr($rivi, 7, 4);
				$kurssipvm_kk  = substr($rivi, 11, 2);
				$kurssipvm_pp  = substr($rivi, 13, 2);
				$kurssipvm     = "$kurssipvm_vv-$kurssipvm_kk-$kurssipvm_pp";
				$kurssi        = (float) substr($rivi, 31, 13) / 10000000;											// kurssi
			}
			else {
				$valuutta      = substr($rivi, 0, 3);																// valuutan nimi
				$vastavaluutta = "EUR";																				// vastavaluutta
				$kurssipvm_vv  = substr($rivi, 86, 4);
				$kurssipvm_kk  = substr($rivi, 83, 2);
				$kurssipvm_pp  = substr($rivi, 80, 2);
				$kurssipvm     = "$kurssipvm_vv-$kurssipvm_kk-$kurssipvm_pp";
				$kurssi        = (float) str_replace(array(',', ' '), array('.',''), trim(substr($rivi, 5, 20)));	// kurssi
			}

			// ei päivitetä jos ollaan jo päivitetty tai väärä vastavaluutta
			if (in_array($valuutta, $valuutat) or $vastavaluutta != $yhtiorow["valkoodi"]) {
				continue;
			}

			$query = "	UPDATE valuu SET
						kurssi = round(1 / $kurssi, 9),
						muutospvm = now(),
						muuttaja = '{$kukarow['kuka']}'
						WHERE yhtio = '{$kukarow['yhtio']}' AND
						nimi = '$valuutta'";
			$result = pupe_query($query);

			// tämä valuutta on nyt päivitetty!
			$valuutat[] = $valuutta;

			if (mysql_affected_rows() != 0) {
				echo "<font class='message'>Päivitettiin kurssi valuutalle $valuutta: ".round(1/$kurssi, 9)."</font><br>";
			}

			$query = "	INSERT INTO valuu_historia (kotivaluutta, valuutta, kurssi, kurssipvm)
						VALUES ('$vastavaluutta', '$valuutta', round(1 / $kurssi, 9), '$kurssipvm')
			  			ON DUPLICATE KEY UPDATE kurssi = round(1 / $kurssi, 9)";
			$result = pupe_query($query);

		}
	}
}

// textmode progress palkki
if (!function_exists("progress_bar")) {
	function progress_bar($current_item, $total_items, $bar_length = 60) {
		static $microtime_start = NULL;

		if ($microtime_start === NULL) {
			$timeparts = explode(" ", microtime());
			$microtime_start = $timeparts[1].substr($timeparts[0], 1);
	    }

		$timeparts = explode(" ", microtime());
		$microtime_stop = $timeparts[1].substr($timeparts[0], 1);
		$current_item = ($current_item == 0) ? 1 : $current_item;

		$microtime_current = round($microtime_stop - $microtime_start, 0);
		$microtime_total = round($microtime_current / $current_item * $total_items, 0);

		printf("|%-60s| [$current_item/$total_items] ($microtime_current sec, total $microtime_total sec)          \r", sprintf("%'#".round($current_item / $total_items * $bar_length)."s", ''));
	}
}

if (!function_exists("pupesoft_cleanstring")) {
	function pupesoft_cleanstring($string, $exclude = array()) {

		$illegal = array("\"","'","<",">","\\", ";");

		if (count($exclude) > 0) {
			$illegal = array_diff($illegal, $exclude);
		}

		return mysql_real_escape_string(str_replace($illegal, "", trim($string)));
	}
}

if (!function_exists("pupesoft_cleannumber")) {
	function pupesoft_cleannumber($string) {
		$string = str_replace(",", ".", trim($string));
		$string = preg_replace("/[^0-9\.\-]/", "", $string);

		return (float) $string;
	}
}

if (!function_exists("viivakoodi")) {
	function viivakoodi($viivakoodi, $encoding = "ean", $leveys = 180, $korkeus = 40, $teksti = "") {

		if (trim($viivakoodi) == '') return FALSE;

		$leveys = (int) $leveys;
		$korkeus = (int) $korkeus;

		if ($leveys <= 0 or $korkeus <= 0) {
			return FALSE;
		}

		$encoding = trim(strtolower($encoding));

		switch ($encoding) {
			case "ean":
			case "ean13":
			case "ean-13":
			case "ean8":
			case "ean-8":
			case "upc":
			case "upc-a":
			case "upc-e":
			case "isbn":
			case "39":
			case "code39":
			case "128c":
			case "code128c":
			case "128b":
			case "code128b":
			case "128":
			case "code128":
			case "128raw":
			case "i25":
			case "interleaved 2 of 5":
			case "cbr":
			case "codabar":
			case "msi":
			case "pls":
			case "plessey":
			case "code93":
			case "93":
				break;
			default:
				return FALSE;
		}

		$lisa = $teksti != "" ? "" : "-n";

		// Tehdään tempfilet viivakoodille ja kuvalle
		$postscript = tempnam("/tmp", "barcode-");
		$image = tempnam("/tmp", "image-");

		// Tehdään viivakoodi PostScript
		shell_exec("barcode -b \"$viivakoodi\" -e \"$encoding\" -u mm -g \"{$leveys}x{$korkeus}\" -E $lisa -o \"$postscript\" -c");

		// Ghostscript konversioon yksikkö kuntoon
		$leveys = round($leveys * 3.03);
		$korkeus = round($korkeus * 4.8);

		// Käännetään PostScript JPG:ksi
		shell_exec("gs -dSAFER -dBATCH -dNOPAUSE -r72x72 -g{$leveys}x{$korkeus} -sDEVICE=jpeg -sOutputFile=\"$image\" \"$postscript\"");
		// Otetaan data muuttujaan
		$data = file_get_contents($image);

		// Dellataan temp-filet
		unlink($postscript);
		unlink($image);

		return $data;
	}
}

if (!function_exists("tarkista_ean13")) {
	function tarkista_ean13($eankoodi){
		// Ensiksi eankoodi muutetaan Stringiksi
		$eankoodi =(string)$eankoodi;

		$tarkistusmerkki = $eankoodi[12];

		// 1. Add the values of the digits in the even-numbered positions: 2, 4, 6, etc.
		$even_sum = $eankoodi{1} + $eankoodi{3} + $eankoodi{5} + $eankoodi{7} + $eankoodi{9} + $eankoodi{11};
		// 2. Multiply this result by 3.
		$even_sum_three = $even_sum * 3;
		// 3. Add the values of the digits in the odd-numbered positions: 1, 3, 5, etc.
		$odd_sum = $eankoodi{0} + $eankoodi{2} + $eankoodi{4} + $eankoodi{6} + $eankoodi{8} + $eankoodi{10};
		// 4. Sum the results of steps 2 and 3.
		$total_sum = $even_sum_three + $odd_sum;
		// 5. The check character is the smallest number which, when added to the result in step 4,  produces a multiple of 10.
		$next_ten = (ceil($total_sum/10))*10;
		$check_digit = $next_ten - $total_sum;

		if ($check_digit == $tarkistusmerkki) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}
}

// Tämä funktio tekee alv-popupin, jossa on yhtiön maan mukaiset alv%tit
if (!function_exists("alv_popup")) {
	function alv_popup($nimi, $oletus) {

		// tarvitaan yhtiörowta ja kukarowta
		global $yhtiorow, $kukarow;

		$ulos = "";

		$laji = "'alv'";
		$lisa = "";

		// tutkitaan ollaanko jossain alv-rekisteröity
		$query = "	SELECT group_concat(concat(\"'\",maa,\"'\") SEPARATOR ',') maat
					FROM yhtion_toimipaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and maa != ''
					and vat_numero != ''";
		$alhire = pupe_query($query);

		// ollaan alv-rekisteröity, haetaan oikea ALV-kanta
		if (mysql_num_rows($alhire) == 1) {
			$vrow = mysql_fetch_array($alhire);

			if ($vrow["maat"] != NULL) {
				$laji = "'alvulk', 'alv'";
				$lisa = "and ((selitetark_2 in ($vrow[maat]) and laji = 'alvulk') or laji = 'alv') ";
			}
		}

		$query = "	SELECT distinct selite
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji in ($laji) $lisa
					ORDER BY selite+0, laji, jarjestys, selite";
		$vresult = pupe_query($query);

		if (mysql_num_rows($vresult) == 0) {
			echo "<font class = 'error'>".t("Yrityksen ALV%tit puuttuu!")."'$kukarow[yhtio]'</font>";
		}
		else {
			$ulos = "<select name='$nimi'>";
			while ($vrow = mysql_fetch_array($vresult)) {
				$sel = "";

				if ($oletus == $vrow['selite']) {
					$sel = "selected";
				}

				if ($vrow["selite"] >= 600) {
					$selite = "K.V.";
				}
				else {
					$selite = "$vrow[selite] %";
				}

				$ulos .= "<option value = '$vrow[selite]' $sel>$selite</option>";
			}
			$ulos .= "</select>";
		}
		return $ulos;
	}
}

if (!function_exists("alv_popup_oletus")) {
	function alv_popup_oletus($nimi, $oletus, $maa = '', $tapa = '') {

		// tarvitaan yhtiörowta ja kukarowta
		global $yhtiorow, $kukarow;

		$ulos = "";

		$laji = "alv";
		$lisa = "";

		// syötettiin maa
		if ($maa != "" and $maa != $yhtiorow["maa"]) {

			//	Otetaan pelkkä ulkomaan-alv lista, käytetään mm. matkalaskuilla
			if ($tapa == "lista") {
				$laji = "alvulk";
				$lisa = "and selitetark_2 = '$maa'";
			}
			else {
				// tutkitaan ollaanko siellä alv-rekisteröity
				$query = "	SELECT *
							FROM yhtion_toimipaikat
							WHERE yhtio = '$kukarow[yhtio]'
							AND maa = '$maa'
							AND vat_numero != ''";
				$alhire = pupe_query($query);

				// ollaan alv-rekisteröity, haetaan oikea ALV-kanta
				if (mysql_num_rows($alhire) == 1) {
					$laji = "alvulk";
					$lisa = "and selitetark_2 = '$maa'";
				}
			}
		}

		$query = "	SELECT selite
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = '$laji' $lisa
					ORDER BY selite+0, jarjestys, selite";
		$vresult = pupe_query($query);

		if (mysql_num_rows($vresult) == 0) {
			if ($tapa == "lista") {
				echo "<font class = 'error'>".t("Kohdemaan ALV ei määritelty!")." '$maa'</font>";
			}
			else {
				echo "<font class = 'error'>".t("Yrityksen ALV%tit puuttuu!")." '$kukarow[yhtio]'</font>";
			}

		}
		else {

			$ulos = "<select name='$nimi'>";
			$sel = '';
			if ($oletus == '') {
				$sel = "selected";
			}
			$ulos .= "<option value = '' $sel>".t("Oletus")."</option>";

			while ($vrow = mysql_fetch_array($vresult)) {
				$sel = "";
				if ($oletus == $vrow['selite']) {
					$sel = "selected";
				}

				if ($vrow["selite"] >= 600) {
					$selite = "K.V.";
				}
				else {
					$selite = "$vrow[selite] %";
				}

				$ulos .= "<option value = '$vrow[selite]' $sel>$selite</option>";
			}
			$ulos .= "</select>";
		}
		return $ulos;
	}
}

if (!function_exists("alv_oletus")) {
	function alv_oletus() {
		global $kukarow;
		$ulos = 0;
		$query = "	SELECT selite
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'alv' and selitetark != ''";
		$vresult = pupe_query($query);

		if (mysql_num_rows($vresult) != 1) {
			echo "<font class = 'error'>".t("Yrityksen oletusALV%tit puuttuu tai niitä on monta!")."</font>";
		}
		else {
			$vrow = mysql_fetch_array($vresult);
			$ulos = $vrow['selite'];
		}
		return $ulos;
	}
}

if (!function_exists("tulosta_logo_pdf")) {
	function tulosta_logo_pdf($pdf, $thispage, $laskurow, $lasku_logo_positio_y=0, $lasku_logo_positio_x=0, $lasku_logo_koko_y=0, $lasku_logo_koko_x=0) {
		global $kukarow, $yhtiorow;

		$apu_yhtiorow = array();
		$apu_yhtiorow["nimi"] = $yhtiorow["nimi"];

		// varmistetaan, että kopiossakin tulee oikean toimipaikan logo
		if (isset($laskurow["eta_yhtio"]) and $laskurow["eta_yhtio"] != "") {
			$query = "	SELECT *
						FROM yhtion_parametrit
						WHERE yhtio = '$laskurow[eta_yhtio]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_array($result);
				$apu_yhtiorow["lasku_logo"] = $yhtion_parametritrow["lasku_logo"];
			}
		}
		elseif ((int) $laskurow["yhtio_toimipaikka"] > 0) {
			$query = "	SELECT *
						FROM yhtion_toimipaikat
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$laskurow[yhtio_toimipaikka]'
						and lasku_logo != ''";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_toimipaikkarow = mysql_fetch_array($result);
				$apu_yhtiorow["lasku_logo"] = $yhtion_toimipaikkarow["lasku_logo"];
				$apu_yhtiorow["nimi"] = $yhtion_toimipaikkarow["nimi"];
		 	}
		}
		elseif ($laskurow["yhtio"] != "") {
			$query = "	SELECT *
						FROM yhtion_parametrit
						WHERE yhtio = '$laskurow[yhtio]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_array($result);

				// Ostotilauksille aina logo käyttäjän toimipaikan takaa
				if (isset($kukarow["toimipaikka"]) and $kukarow["toimipaikka"] != 0 and ($laskurow["tila"] == "O" or $laskurow["tila"] == "K")) {
					$query = "	SELECT lasku_logo, nimi
								FROM yhtion_toimipaikat
								WHERE yhtio='$laskurow[yhtio]' and tunnus='$kukarow[toimipaikka]'";
					$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

					if (mysql_num_rows($result) == 1) {
						$yhtion_toimipaikkarow = mysql_fetch_assoc($result);

						$apu_yhtiorow["lasku_logo"] = $yhtion_toimipaikkarow["lasku_logo"];
						$apu_yhtiorow["nimi"] = $yhtion_toimipaikkarow["nimi"];
				 	}
				}
				else {
					$apu_yhtiorow["lasku_logo"] = $yhtion_parametritrow["lasku_logo"];
				}
			}
		}
		else {
			$apu_yhtiorow["lasku_logo"] = $yhtiorow["lasku_logo"];
		}

		unset($data);

		if (isset($apu_yhtiorow["lasku_logo"]) and (int) $apu_yhtiorow["lasku_logo"] > 0) {
			$liite = hae_liite($apu_yhtiorow["lasku_logo"], "Yllapito", "array");
			$data = $liite["data"];
			$isizelogo[0] = $liite["image_width"];
			$isizelogo[1] = $liite["image_height"];
			unset($liite);
		}
		elseif (isset($apu_yhtiorow["lasku_logo"]) and $apu_yhtiorow["lasku_logo"] != "" and file_exists($apu_yhtiorow["lasku_logo"])) {
			$filename = $apu_yhtiorow["lasku_logo"];

			$fh = fopen($filename, "r");
			$data = fread($fh, filesize($filename));
			fclose($fh);

			$isizelogo = getimagesize($apu_yhtiorow["lasku_logo"]);
		}

		if (isset($data) and $data) {
			$image = $pdf->jfif_embed($data);

			if (!$image) {
				echo t("Logokuvavirhe");
			}
			else {

				$logoparam = array();

				if ($lasku_logo_koko_y == 0 and $lasku_logo_koko_x == 0) {
					$lasku_logo_koko_y = 50;
					$lasku_logo_koko_x = 240;

					if ((int) $yhtiorow["lasku_logo_koko"] > 0) {
						$lasku_logo_koko_y = (int) $yhtiorow["lasku_logo_koko"];
					}
				}

				if ($lasku_logo_positio_y == 0 and $lasku_logo_positio_x == 0) {
					$lasku_logo_positio_y = 830;
					$lasku_logo_positio_x = 20;

					if ($yhtiorow["lasku_logo_positio"] != "") {
						if (strpos($yhtiorow["lasku_logo_positio"], "x") !== FALSE) {
							list($llp_y, $llp_x) = explode("x", trim($yhtiorow["lasku_logo_positio"]));
						}
						else {
							$llp_y = trim($yhtiorow["lasku_logo_positio"]);
							$llp_x = 0;
						}

						if ((int) $llp_y > 0) $lasku_logo_positio_y = (int) $llp_y;
						if ((int) $llp_x > 0) $lasku_logo_positio_x = (int) $llp_x;
					}
				}

				if ($isizelogo[0] > $isizelogo[1] and $isizelogo[1] * ($lasku_logo_koko_x / $isizelogo[0]) <= $lasku_logo_koko_y) {
					$logoparam['scale'] = $lasku_logo_koko_x / $isizelogo[0];
				}
				else {
					$logoparam['scale'] = $lasku_logo_koko_y  / $isizelogo[1];
				}

				$placement = $pdf->image_place($image, $lasku_logo_positio_y-($logoparam['scale']*$isizelogo[1]), $lasku_logo_positio_x, $thispage, $logoparam);
			}
		}
		else {

			$iso["height"] = 14;
			$iso["font"]   = "Helvetica-Bold";

			if ($laskurow["yhtio_nimi"] != "") $yhtionimi = $laskurow["yhtio_nimi"];
			elseif ($apu_yhtiorow["nimi"] != "") $yhtionimi = $apu_yhtiorow["nimi"];
			else $yhtionimi = $yhtiorow["nimi"];

			list($ff_string, $ff_font) = pdf_fontfit($yhtionimi, 270, $pdf, $iso);

			if ($lasku_logo_positio_y == 0 and $lasku_logo_positio_x == 0) {
				$lasku_logo_positio_y = 815;
				$lasku_logo_positio_x = 30;
			}

			$pdf->draw_text($lasku_logo_positio_x, $lasku_logo_positio_y, $ff_string, $thispage, $ff_font);
		}
	}
}

if (!function_exists("listdir")) {
	function listdir($start_dir = '.') {

		$files = array();

		if (is_dir($start_dir)) {

			$fh = opendir($start_dir);

			while (($file = readdir($fh)) !== false) {
				if (strcmp($file, '.') == 0 or strcmp($file, '..') == 0 or substr($file, 0, 1) == ".") {
					continue;
				}
				$filepath = $start_dir . '/' . $file;

				if (is_dir($filepath)) {
					$files = array_merge($files, listdir($filepath));
				}
				else {
					array_push($files, $filepath);
				}
			}
			closedir($fh);
			sort($files);
		}
		else {
			$files = false;
		}

		return $files;
	}
}

if (!function_exists("laskun_ja_tilauksen_vertailu")) {
	function laskun_ja_tilauksen_vertailu($kukarow, $tunnus) {

		$tunnus = (int) $tunnus;

		// haetaan liitetiedostot
		$query = "	SELECT liitetiedostot.filename, liitetiedostot.data, liitetiedostot.kayttotarkoitus, liitetiedostot.liitostunnus
					FROM liitetiedostot
					WHERE liitetiedostot.yhtio = '$kukarow[yhtio]'
					AND liitetiedostot.liitos = 'lasku'
					AND liitetiedostot.kayttotarkoitus IN ('EDI', 'FINVOICE')
					AND liitetiedostot.liitostunnus = '$tunnus'";
		$liite_res = pupe_query($query);

		$purchaseorder_data = $invoice_data = '';
		$noIdentifierlask = 0;

		if (mysql_num_rows($liite_res) == 0) {
			return FALSE;
		}
		elseif (mysql_num_rows($liite_res) == 1) {
			return 'ei_loydy_edia';
		}

		while ($liite_row = mysql_fetch_assoc($liite_res)) {

			// jos käyttötarkoituksessa lukee FINVOICE tai EDI, otetaan data talteen
			if ($liite_row['kayttotarkoitus'] == 'FINVOICE') {
				$invoice_data = $liite_row['data'];
			}
			elseif ($liite_row['kayttotarkoitus'] == 'EDI') {
				$purchaseorder_data = $liite_row['data'];
			}
		}

		if ($invoice_data != '' and $purchaseorder_data != '') {

			$invoice = $purchaseorder = array();

			if (!$invoice_obj = @simplexml_load_string($invoice_data)) {
				return FALSE;
			}

			$purchaseorder_tuoteno = '';
			$purchaseorder_tilausnumero = '';

			// loopataan EDI
			foreach (explode("\n", $purchaseorder_data) as $tietue) {

				// tää on futurikeisi
				if (substr($tietue,0,1) != '*') {
					if (strpos($tietue, ":") !== FALSE) list($tunnus, $tieto)  = explode(':', $tietue);
				}
				else {
					$tunnus = $tietue;
					$tieto	= "";
				}

				$tunnus = trim($tunnus);
				$tieto  = trim($tieto);

				if (substr($tunnus,0,15) == '*RE  OSTOTILRIV') {
					$tunnus='*RE  OSTOTILRIV'; // Poistetaan häiritsevä nro
				}

				switch ($tunnus) {
					case 'OSTOTILRIV.OTR_TUOTEKOODI':
						$purchaseorder_tuoteno = $tieto;
						break;
					case 'OSTOTILRIV.OTR_NIMI':
						$purchaseorder[$purchaseorder_tuoteno]['nimitys'] = $tieto;
						break;
					case 'OSTOTILRIV.OTR_TILATTUMAARA':
						if (!isset($purchaseorder[$purchaseorder_tuoteno]['tilattumaara'])) {
							$purchaseorder[$purchaseorder_tuoteno]['tilattumaara'] = (float) str_replace(",", ".", $tieto);
						}
						else {
							$purchaseorder[$purchaseorder_tuoteno]['tilattumaara'] += (float) str_replace(",", ".", $tieto);
						}
						break;
					case 'OSTOTILRIV.OTR_RIVISUMMA':
						if (!isset($purchaseorder[$purchaseorder_tuoteno]['nettohinta'])) {
							$purchaseorder[$purchaseorder_tuoteno]['nettohinta'] = (float) str_replace(",", ".", $tieto);
						}
						else {
							$purchaseorder[$purchaseorder_tuoteno]['nettohinta'] += (float) str_replace(",", ".", $tieto);
						}
						break;
					case 'OSTOTIL.OT_NRO':
						$purchaseorder_tilausnumero = $tieto;
						break;
				}
			}

			foreach ($invoice_obj->InvoiceRow as $row) {

				if (trim($row->ArticleIdentifier) != "") {
					$ai_tuoteno = (string) trim($row->ArticleIdentifier);
				}
				else {
					$ai_tuoteno = "Ei_tuotekoodia_".$noIdentifierlask;
					$noIdentifierlask++;
				}

				$invoice[$ai_tuoteno]['nimitys'] = $row->ArticleName;

				if (!isset($invoice[$ai_tuoteno]['tilattumaara'])) {
					$invoice[$ai_tuoteno]['tilattumaara'] = (float) str_replace(",", ".", $row->DeliveredQuantity);
				}
				else {
					$invoice[$ai_tuoteno]['tilattumaara'] += (float) str_replace(",", ".", $row->DeliveredQuantity);
				}

				if (!isset($invoice[$ai_tuoteno]['bruttohinta'])) {
					$invoice[$ai_tuoteno]['bruttohinta'] = (float) str_replace(",", ".", $row->UnitPriceAmount);
				}
				else {
					$invoice[$ai_tuoteno]['bruttohinta'] += (float) str_replace(",", ".", $row->UnitPriceAmount);
				}

				// voi olla monta alennusta
				foreach ($row as $child => $value) {
					if ($child == 'RowDiscountPercent') {
						$invoice[$ai_tuoteno]['ale'][] = (float) str_replace(",", ".", $value);
					}
				}

				if (!isset($invoice[$ai_tuoteno]['ale'])) $invoice[$ai_tuoteno]['ale'] = '';

				if ($row->RowAmount) {
					if (!isset($invoice[$ai_tuoteno]['nettohinta'])) {
						$invoice[$ai_tuoteno]['nettohinta'] = (float) str_replace(",", ".", $row->RowAmount);
					}
					else {
						$invoice[$ai_tuoteno]['nettohinta'] += (float) str_replace(",", ".", $row->RowAmount);
					}
				}
				else {
					$invoice[$ai_tuoteno]['nettohinta'] = 0;
				}
			}

			// mitkä rivit ei löydy laskulta, mutta löytyvät tilaukselta
			$invoice_ei_loydy 		= array_diff_key($invoice, $purchaseorder);

			// mitkä rivit ei löydy tilaukselta, mutta löytyvät laskulta
			$purchaseorder_ei_loydy = array_diff_key($purchaseorder, $invoice);

			// mitkä rivit löytyvät kummastakin
			$loytyy_kummastakin 	= array_intersect_key($invoice, $purchaseorder);

			// jos yksi rivi ei mätsää (esim. kuljetusvakuutus)
			if (count($invoice_ei_loydy) == 1) {
				foreach ($invoice_ei_loydy as $ei_loydytuoteno => $ei_loydy) {

					// Jos tämä tuote on saldoton, niin oletetaan, että se on kuljetusvakuutus tai joku muu "ei alunperin tilattu" rivi.
					$query = "	SELECT ei_saldoa
								FROM tuote
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '$ei_loydytuoteno'";
					$esaldoa_result = mysql_query($query) or pupe_error($query);
					$esaldoa_row = mysql_fetch_assoc($esaldoa_result);

					if ($ei_loydy['tilattumaara'] == 0 or $esaldoa_row["ei_saldoa"] != "") {
						// oletetaan että tämä on kuljetusvakuutus tms., joten laitetaan se "löytyviin" että vertailu-check hyväksyy nämä OK:na
						$loytyy_kummastakin[key($invoice_ei_loydy)] = $ei_loydy;
						$purchaseorder[key($invoice_ei_loydy)] = $ei_loydy;

						// poistetaan se ei löydetyistä
						array_pop($invoice_ei_loydy);
					}
				}

				$erot = '';

				foreach ($loytyy_kummastakin as $tuoteno => $null) {

					if ($invoice[$tuoteno]['tilattumaara'] > 0 and ($invoice[$tuoteno]['tilattumaara'] != $purchaseorder[$tuoteno]['tilattumaara'] or $invoice[$tuoteno]['nettohinta'] != $purchaseorder[$tuoteno]['nettohinta'])) {
						// löytyi ero
						$erot = 'joo';
					}
				}
			}

			return array($invoice, $purchaseorder, $invoice_ei_loydy, $purchaseorder_ei_loydy, $loytyy_kummastakin, $purchaseorder_tilausnumero);
		}
		else {
			return FALSE;
		}
	}
}

if (!function_exists('liita_tilaus_laskun_liitetiedostoksi')) {
	function liita_tilaus_laskun_liitetiedostoksi($kukarow, $yhtiorow, $liitetaanko_tilaus_laskulle_hakemisto, $tunnus, $laskun_asiakkaan_tilausnumero) {

		if (trim($liitetaanko_tilaus_laskulle_hakemisto) != '') {

			// fopen dirikka $liitetaanko_tilaus_laskulle_hakemisto eli purchaseorderit
			$files_editilaus_laskulle = listdir($liitetaanko_tilaus_laskulle_hakemisto);

			// loopataan se läpi
			foreach ($files_editilaus_laskulle as $files_editilaus_laskulle_x) {

				$path_parts = pathinfo($files_editilaus_laskulle_x);
				$editilaus_laskulle_polku = substr($liitetaanko_tilaus_laskulle_hakemisto, -1) !== '/' ? $liitetaanko_tilaus_laskulle_hakemisto.'/' : $liitetaanko_tilaus_laskulle_hakemisto;

				if ($editilaus_laskulle_data = file_get_contents($editilaus_laskulle_polku.$path_parts['basename'])) {

					if (strpos($editilaus_laskulle_data, "OSTOTIL.OT_NRO:".$laskun_asiakkaan_tilausnumero) !== FALSE) {

						$editilaus_laskulle_filesize = filesize($editilaus_laskulle_polku.$path_parts['basename']);
						$data  = mysql_real_escape_string($editilaus_laskulle_data);

						$query = "	INSERT INTO liitetiedostot SET
									yhtio    			= '$kukarow[yhtio]',
									liitos   			= 'lasku',
									liitostunnus 		= '$tunnus',
									data     			= '$data',
									selite   			= 'Futursoft-tilaus $laskun_asiakkaan_tilausnumero',
									kieli				= '$yhtiorow[kieli]',
									filename 			= '$path_parts[basename]',
									filesize 			= '$editilaus_laskulle_filesize',
									filetype 			= 'text',
									image_width			= '',
									image_height		= '',
									image_bits			= '',
									image_channels		= '',
									kayttotarkoitus		= 'EDI',
									jarjestys			= '1',
									laatija				= '$kukarow[kuka]',
									luontiaika			= now()";
						$editilaus_laskulle_result = mysql_query($query) or die ("$query\n\n".mysql_error());

						return TRUE;
					}
				}
			}

			return FALSE;

		}
		else {
			echo "<br/><font class='error'>",t("Asetuksissa on virheellinen hakemistopolku"),"!</font><br/>";
			return FALSE;
		}
	}
}

if (!function_exists("luoiban")) {
	function luoiban ($tilino) {
		include ("inc/pankkitiedot.inc");

		$iban = $tilino . "151800";
		for ($ind = 0; $ind < 20; $ind += 7) {
			$vali .= substr($iban, $ind, 7);
			$vali = $vali % 97;
			//echo "iban: $iban, (".substr($iban, $ind, 7).") $vali\n";
		}
		$tarkiste = 98 - $vali;
		$vastaus['iban'] = "FI" . sprintf('%02d', $tarkiste) . $tilino;

		$pankki = pankkitiedot($tilino,'');
		$vastaus['swift'] = $pankki['swift'];

		return $vastaus;
	}
}

if (!function_exists("tarkista_sepa")) {
	function tarkista_sepa($maa) {

		// SEPA (Single Euro Payments Area) maat ja niiden IBAN tilinumeron pituus
		$sepa_maat	= array("AL" => "28",
							"AD" => "24",
							"AT" => "20",
							"BE" => "16",
							"BA" => "20",
							"BG" => "22",
							"HR" => "21",
							"CY" => "28",
							"CZ" => "24",
							"DK" => "18",
							"EE" => "20",
							"FO" => "18",
							"FI" => "18",
							"FR" => "27",
							"GE" => "22",
							"DE" => "22",
							"GI" => "23",
							"GR" => "27",
							"GL" => "18",
							"HU" => "28",
							"IS" => "26",
							"IE" => "22",
							"IL" => "23",
							"IT" => "27",
							"KZ" => "20",
							"LV" => "21",
							"LB" => "28",
							"LI" => "21",
							"LT" => "20",
							"LU" => "20",
							"MK" => "19",
							"MT" => "31",
							"MU" => "30",
							"MC" => "27",
							"ME" => "22",
							"NL" => "18",
							"NO" => "15",
							"PL" => "28",
							"PT" => "25",
							"RO" => "24",
							"SM" => "27",
							"SA" => "24",
							"RS" => "22",
							"SK" => "24",
							"SI" => "19",
							"ES" => "24",
							"SE" => "24",
							"CH" => "21",
							"TN" => "24",
							"TR" => "26",
							"GB" => "22");

		// Maa ei ole sepa maa, palautetaan FALSE
		if (!isset($sepa_maat[$maa])) {
			return FALSE;
		}

		// Palautetaan IBAN:in pituus, jos on SEPA maa
		return $sepa_maat[$maa];

	}
}

if (!function_exists("tarkista_iban")) {
	function tarkista_iban($tilino) {
		global $yhtiorow;

		if ($yhtiorow["maa"] != "FI") {
			return $tilino;
		}

		// Calculating and validating IBAN checksums:

		// 1. Check that the total IBAN length is correct as per the country. If not, the IBAN is invalid.
		// 2. Move the four initial characters to the end of the string.
		// 3. Replace each letter in the string with two digits, thereby expanding the string, where A=10, B=11, ..., Z=35.
		// 4. Interpret the string as a decimal integer and compute the remainder of that number on division by 97.

		// The IBAN number can only be valid if the remainder is 1.

		$tilino			= strtoupper(str_replace(" ", "", $tilino));	// Kirjaimet isoksi ja spacet pois
		$maa			= substr($tilino, 0, 2);
		$pituus			= strlen($tilino);
		$oikea_pituus	= tarkista_sepa($maa);

		// Maa ei ole sallittu SEPA maa
		if ($oikea_pituus === FALSE) {
			return "";
		}

		// Tilinumeron pituus on väärä
		if ($pituus != $oikea_pituus) {
			return "";
		}

		// Siirretään neljä ensimmäistä merkkiä tilinumeron loppuun
		$chk_tilinumero = substr($tilino, 4).substr($tilino, 0, 4);
		$chk_tilinumeronumeric = "";

		// Muutetaan kirjaimet numeroiksi (A = 10, B = 11, C = 12, jne...)
		for ($i = 0; $i < $pituus; $i++) {
			if (is_numeric($chk_tilinumero{$i})) {
				$chk_tilinumeronumeric .= $chk_tilinumero{$i};
			}
			else {
				$chk_tilinumeronumeric .= ord($chk_tilinumero{$i}) - 55;
			}
		}

		// Poistetaan stringistä etunollat
		$chk_tilinumeronumeric = ltrim($chk_tilinumeronumeric, "0");

		// Lasketaan MOD-97
		$mod = 0;
		$pituus = strlen($chk_tilinumeronumeric);

		for ($i = 0; $i < $pituus; $i++) {
		    $numero = (int) $chk_tilinumeronumeric{$i};
		    $mod = (($mod * 10) + $numero) % 97;
		}

		// Jos tulos ei ole yksi, niin tilinumero on väärin
		if ($mod != 1) {
			return "";
		}

		// Tilinumero on oikein!
		return $tilino;
	}
}

if (!function_exists("tarkista_oikeus")) {
	function tarkista_oikeus($ohjelma, $alanimi = '', $paivitys = '', $return = '') {
		global $kukarow;

		$ohjelma = mysql_real_escape_string(trim($ohjelma));
		$alanimi = mysql_real_escape_string(trim($alanimi));
		$querylisa = "";

		if (strpos($alanimi, "%") !== FALSE) {
			$querylisa .= " AND alanimi like '$alanimi'";
		}
		elseif ($alanimi != "") {
			// Luetaan alanimeä vain ekaan &-merkkiin saakka koska joskus kantaan on tallennettu myös jotain lisätietoja toisessa muuttujassa
			$querylisa = " AND substring(alanimi, 1, if(LOCATE('&', alanimi) > 0, LOCATE('&', alanimi) - 1, CHAR_LENGTH(alanimi))) = '$alanimi'";
		}
		else {
			$querylisa .= " AND alanimi = '' ";
		}

		if ($paivitys != "") {
			$querylisa .= " AND paivitys = '1' ";
		}

		$query = "	SELECT *
					FROM oikeu
					WHERE yhtio = '$kukarow[yhtio]'
					AND kuka = '$kukarow[kuka]'
					AND nimi LIKE '%$ohjelma'
					$querylisa
					ORDER BY length(alanimi)";
		$res = pupe_query($query);

		if (mysql_num_rows($res) > 0) {

			if ($return != "") {
				return mysql_fetch_array($res);
			}

			return TRUE;
		}
		else {
			return FALSE;
		}
	}
}

if (!function_exists("mod11_tarkiste")) {
	function mod11_tarkiste ($numero) {

		$tulos = "";
		$painoarvo = array( 2, 3, 4, 5, 6, 7, 2, 3, 4, 5, 6, 7, 2, 3, 4, 5, 6, 7, 2, 3, 4, 5, 6, 7 );

		# Käännetään stringi takaperin
		$numero_rev = strrev($numero);

		for ($i = 0, $summa = 0; $i < strlen($numero_rev); $i++) {
			# Lasketaan tulo ja kumuloidaan
			$summa += substr($numero_rev, $i, 1 ) * $painoarvo[$i];
		}

		# lasketaan tarkiste ja konkatenoidaan peruslukuun
		$jaannos = $summa % 11;

		switch ($jaannos) {
			case 0:
				$tulos = $numero . 0;
				break;
			case 1:
				$tulos = $numero . 0;
				break;
			default:
				$tulos = $numero . (11 - $jaannos);
				break;
		}

		return $tulos;
	}
}

if (!function_exists("tarkista_viite")) {
	function tarkista_viite($viite) {

		// otetaan sisään $viite muuttuja
		// palautetaan TRUE jos viite on ok, muutoin FALSE
		// sallitaan viitteessä vaan numerot 0-9

		if (!is_numeric($viite)) {
			return FALSE;
		}

		$kerroin = 7;
		$tulo 	 = 0;

		for ($i = 2; $i <= strlen($viite); $i++) {

			$merkki = substr($viite, -1 * $i, 1);
			$tulo += $kerroin * $merkki;

			switch ($kerroin) {
				case 7:
					$kerroin = 3;
					break;
				case 3:
					$kerroin = 1;
					break;
				case 1:
					$kerroin = 7;
					break;
			}
		}

		$tmerkki = substr($viite, -1);
		$tulo = substr($tulo, -1) + 0;

		if ($tulo != 0) {
			$tulo = 10 - $tulo;
		}

		if ($tulo == $tmerkki) {
			return TRUE;
		}

		return FALSE;
	}
}

if (!function_exists("generoi_rfviite")) {
	function generoi_rfviite($laskunumero) {

		// Tehdään laskunumerosta ensin vanha suomalainen viite
		$viite   = ltrim(preg_replace("/[^0-9]/", "", $laskunumero), "0");
		$kerroin = 7;
		$tulo    = 0;

		for ($i = 1; $i <= strlen($viite); $i++) {

			$merkki = substr($viite, -1 * $i, 1);
			$tulo += $kerroin * $merkki;

			switch ($kerroin) {
				case 7:
					$kerroin = 3;
					break;
				case 3:
					$kerroin = 1;
					break;
				case 1:
					$kerroin = 7;
					break;
			}
		}

		$tulo = substr($tulo, -1) + 0;

		if ($tulo != 0) {
			$tulo = 10 - $tulo;
		}

		$viite .= $tulo;

		// Generoidaan viitteestä RF-viite
		$rf_viite = $viite."271500";

		// Lasketaan MOD-97
		$mod = 0;
		$pituus = strlen($rf_viite);

		for ($i = 0; $i < $pituus; $i++) {
		    $numero = (int) $rf_viite{$i};
		    $mod = (($mod * 10) + $numero) % 97;
		}

		$tarkiste = 98 - $mod;
		$tarkiste = sprintf('%02d', $tarkiste); // Esitetään kahdella merkillä

		return "RF".$tarkiste.$viite;
	}

}

if (!function_exists("tarkista_rfviite")) {
	function tarkista_rfviite($viite) {

		// Poistetaan välilyönnit
		$viite  = str_replace(" ", "", $viite);
		$pituus = strlen($viite);

		// Siirretään neljä ensimmäistä merkkiä tilinumeron loppuun
		$chk_viite = substr($viite, 4).substr($viite, 0, 4);
		$chk_viitenumeric = "";

		// Muutetaan kirjaimet numeroiksi (A = 10, B = 11, C = 12, jne...)
		for ($i = 0; $i < $pituus; $i++) {
			if (is_numeric($chk_viite{$i})) {
				$chk_viitenumeric .= $chk_viite{$i};
			}
			else {
				$chk_viitenumeric .= ord($chk_viite{$i}) - 55;
			}
		}

		// Poistetaan stringistä etunollat
		$chk_viitenumeric = ltrim($chk_viitenumeric, "0");

		// Lasketaan MOD-97
		$mod = 0;
		$pituus = strlen($chk_viitenumeric);

		for ($i = 0; $i < $pituus; $i++) {
		    $numero = (int) $chk_viitenumeric{$i};
		    $mod = (($mod * 10) + $numero) % 97;
		}

		// Jos tulos ei ole yksi, niin viite on väärin
		if ($mod != 1) {
			return FALSE;
		}

		// Viite on oikein!
		return TRUE;
	}
}

if (!function_exists("tarkista_bic")) {
	function tarkista_bic($bic) {
		global $yhtiorow;

		if ($yhtiorow["maa"] != "FI") {
			return TRUE;
		}

		preg_match("/^[A-Z]{6,6}[A-Z2-9][A-NP-Z0-9]([A-Z0-9]{3,3}){0,1}$/", $bic, $matches);

		if ($matches[0] == "") {
			// BIC on väärin!
			return FALSE;
		}

		// BIC on oikein!
		return TRUE;
	}
}

if (!function_exists("tarkista_bban")) {
	function tarkista_bban($bban) {
		global $yhtiorow;

		if ($yhtiorow["maa"] != "FI") {
			return TRUE;
		}

		preg_match("/^[a-zA-Z0-9]{1,30}$/", $bban, $matches);

		if ($matches[0] == "") {
			// BBAN on väärin!
			return FALSE;
		}

		// BBAN on oikein!
		return TRUE;
	}
}

if (!function_exists("asiakashakuohje")) {
	function asiakashakuohje() {
		global $palvelin2;

		echo "<div id='div_asiakashakuohje' class='popup' style='width: 300px'>
			<table>
			<tr><th>".t("Asiakasta voi etsiä").":</th></tr>
			<tr><td>".t("Nimellä")."</td></tr>
			<tr><td>".t("Nimitarkenteella")."</td></tr>
			<tr><td>".t("Toimitusosoitteen nimellä")."</td></tr>
			<tr><td>".t("Toimitusosoitteen nimitarkenteella")."</td></tr>
			<tr><td>".t("Ytunnuksella")."</td></tr>
			<tr><td>".t("Asiakasnumerolla")."</td></tr>
			<tr><td>".t("OVT-tunnuksella")."</td></tr>
			<tr><td>".t("Toimitusosoitteen OVT-tunnuksella")."</td></tr>
			<tr><td>".t("Kantaasiakastunnuksella")."</td></tr>
			</table>
			</div>";

		// jos eka merkki on # etsitään toimitusnimen perusteella
		// jos eka merkki on * etsitään laskuilta asiakastietoja nimellä
		// jos eka merkki £ etsitään tilausnumerolla

		echo "<img class='tooltip' id='asiakashakuohje' src='{$palvelin2}pics/lullacons/info.png'>";
	}
}

// Funktio lisää tuotteenalkion dynaamiseen puuhun.
if (!function_exists("TuotteenAlkiot")) {
	function TuotteenAlkiot($laji, $liitos, $puun_tunnus, $kieli) {
		global $kukarow;

		$qu = "	INSERT INTO puun_alkio
				SET yhtio	= '$kukarow[yhtio]',
				liitos 		= '$liitos',
				laji 		= '$laji',
				puun_tunnus = '$puun_tunnus',
				kieli 		= '$kieli',
				laatija 	= '$kukarow[kuka]',
				luontiaika 	= now()
				ON DUPLICATE KEY UPDATE
				muuttaja = '$kukarow[kuka]',
				muutospvm = now()";
		$r = pupe_query($qu);
	}
}

if (!function_exists("LisaaTaso")) {
	function LisaaTaso($laji, $left, $koodi ,$nimi) {
		global $kukarow;

		// Funktio mahdollistaa lisäämään tason mihin tahansa väliin.
		$qu = "LOCK TABLE dynaaminen_puu WRITE";
		$re = pupe_query($qu);

		// Haetaan valitun tason tiedot
		$q2 = "	SELECT @myRight := rgt, @myLeft := lft
				FROM dynaaminen_puu
				WHERE yhtio = '{$kukarow["yhtio"]}'
				AND laji 	= '$laji'
				AND lft 	= '$left'";
		$re = pupe_query($q2);

		// Pävitetään kaikki nodet, jotka on uuden noden oikealla puolella kaksi pykälää oikealle (vasen puoli)
		$qu = "	UPDATE dynaaminen_puu
				set lft = lft + 2, muutospvm = now(), muuttaja = '$kukarow[kuka]'
				WHERE yhtio	= '{$kukarow["yhtio"]}'
				AND laji	= '$laji'
				AND lft 	> @myRight
				ORDER BY lft desc";
		$re = pupe_query($qu);

		// Pävitetään kaikki nodet joiden oikea laita on uuden noden oikealla puolella kaksi pykälää oikealle (oikea puoli)
		$qu = "	UPDATE dynaaminen_puu
				set rgt = rgt + 2, muutospvm = now(), muuttaja = '$kukarow[kuka]'
				WHERE yhtio	= '{$kukarow["yhtio"]}'
				AND laji	= '$laji'
				AND rgt    >= @myRight
				ORDER BY rgt desc";
		$re = pupe_query($qu);

		// Päivitetään valitun tason leftit pykälää oikealle, jotta uusi taso mahtuu mukaan
		$qu = "	UPDATE dynaaminen_puu
				set lft = lft + 1, muutospvm = now(), muuttaja = '$kukarow[kuka]'
				WHERE yhtio	= '{$kukarow["yhtio"]}'
				AND laji	= '$laji'
				AND lft 	> @myLeft
				AND lft 	< @myRight
				ORDER BY lft desc";
		$re = pupe_query($qu);

		// Päivitetään valitun tason rightit pykälää oikealle, jotta uusi taso mahtuu mukaan
		$qu = "	UPDATE dynaaminen_puu
				set rgt = rgt + 1, muutospvm = now(), muuttaja = '$kukarow[kuka]'
				WHERE yhtio	= '{$kukarow["yhtio"]}'
				AND laji	= '$laji'
				AND lft 	> @myLeft
				AND lft 	< @myRight
				ORDER BY rgt desc";
		$re = pupe_query($qu);

		$qu = "INSERT INTO dynaaminen_puu(yhtio,koodi,nimi, lft, rgt, laji,laatija,luontiaika) values ('$kukarow[yhtio]', '{$koodi}', '{$nimi}', @myLeft + 1 , @myRight + 1, '$laji', '$kukarow[kuka]', now())";
		$re = pupe_query($qu);

		$qu = "UNLOCK TABLES";
		$re = pupe_query($qu);

	}
}

if (!function_exists("LisaaPaaKat")) {
	function LisaaPaaKat($laji, $nimi) {
		global $kukarow;

		$qu = "INSERT INTO dynaaminen_puu(yhtio,nimi,lft,rgt, laji, laatija, luontiaika) VALUES('$kukarow[yhtio]', '$nimi', 1, 2, '$laji', '$kukarow[kuka]', now())";
		$re = pupe_query($qu);
	}
}

if (!function_exists("LisaaLapsi")) {
	function LisaaLapsi($laji, $left, $syvyys, $koodi, $nimi) {
		global $kukarow;

		if ($left == 1) {
			// mikäli isä on root, niin lisätään sille alakat
			$apu 	= " and rgt >= @myRight";
			$apu2 	= " @myRight , @myRight + 1";
			$apu3 	= " @myRight := rgt ";
			$apu4 	= " and lft > @myRight";
		}
		elseif ($syvyys >= 1) {
			// lisätään alakategorialle lapsi
			$apu 	= " and rgt > @myLeft";
			$apu2 	= " @myLeft +1 , @myLeft + 2";
			$apu3 	= " @myLeft := lft ";
			$apu4 	= " and lft > @myLeft";
		}
		else {
			//alakat lapsen lisäys
			$apu 	= " and rgt > @myRight";
			$apu2 	= " @myRight + 1, @myRight + 2";
			$apu3 	= " @myRight := rgt ";
			$apu4 	= " and lft > @myRight";
		}

		$qu = "LOCK TABLE dynaaminen_puu WRITE";
		$re = pupe_query($qu);

		$qu = "	SELECT $apu3
				FROM dynaaminen_puu
				WHERE yhtio = '{$kukarow["yhtio"]}'
				AND laji 	= '$laji'
				AND lft 	= '$left'";
		$re = pupe_query($qu);

		$qu = "	UPDATE dynaaminen_puu
				SET rgt = rgt + 2, muutospvm = now(), muuttaja = '$kukarow[kuka]'
				WHERE yhtio	= '{$kukarow["yhtio"]}'
				AND laji 	= '$laji'
				$apu
				ORDER BY rgt desc";
		$re = pupe_query($qu);

		$qu = "	UPDATE dynaaminen_puu
				SET lft = lft + 2,muutospvm = now(), muuttaja = '$kukarow[kuka]'
				WHERE yhtio = '{$kukarow["yhtio"]}'
				AND laji 	= '$laji'
				$apu4
				ORDER BY lft desc";
		$re = pupe_query($qu);

		$qu = "INSERT INTO dynaaminen_puu (yhtio, nimi, koodi, lft, rgt, syvyys, laji, laatija, luontiaika) VALUES ('$kukarow[yhtio]', '$nimi', '$koodi', $apu2, $syvyys + 1, '$laji', '$kukarow[kuka]', now())";
		$re = pupe_query($qu);

		$qu = "UNLOCK TABLES";
		$re = pupe_query($qu);

		// palautetaan juuri luodun rivin tiedot
		$qu = "SELECT tunnus, nimi, koodi, lft, rgt FROM dynaaminen_puu WHERE yhtio = '$kukarow[yhtio]' AND laji = '$laji' AND koodi = '$koodi' AND nimi = '$nimi'";
		$re = pupe_query($qu);
		$row = mysql_fetch_assoc($re);
		return $row;
	}
}

if (!function_exists("PoistaLapset")) {
	function PoistaLapset($laji, $left) {
		global $kukarow;

		if ($left == 1) {
			echo "<p><font class='error'>",t('Et Voi poistaa pääkategoriaa')," !!!</font></p>";
		}
		else {
	 		$qu = "LOCK TABLE dynaaminen_puu WRITE";
	 		$re = pupe_query($qu);

			// Haetaan poistettavan noden tiedot
			$qu = "	SELECT @myLeft := lft, @myRight := rgt, @myWidth := rgt - lft + 1
					FROM dynaaminen_puu
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND laji	= '$laji'
					AND lft 	= '$left'";
	 		$re = pupe_query($qu);

			// Poistetaan se
			$qu = "	DELETE FROM dynaaminen_puu
					WHERE yhtio	= '{$kukarow["yhtio"]}'
					AND laji	= '$laji'
					and lft 	= @myLeft";
	 		$re = pupe_query($qu);

			// Päivitetään poistettavan noden lapset pykälää vasemmalle
			$qu = "	UPDATE dynaaminen_puu
					SET rgt = rgt - 1
					WHERE yhtio	= '{$kukarow["yhtio"]}'
					AND laji = '$laji'
					AND lft between @myLeft and @myRight
					ORDER BY rgt";
	 		$re = pupe_query($qu);

			// Päivitetään poistettavan noden lapset pykälää vasemmalle
			$qu = "	UPDATE dynaaminen_puu
					SET lft = lft - 1, muutospvm = now(), muuttaja = '$kukarow[kuka]'
					WHERE yhtio	= '{$kukarow["yhtio"]}'
					AND laji = '$laji'
					AND lft between @myLeft and @myRight
					ORDER BY lft";
	 		$re = pupe_query($qu);

			// Päivitetään poistetun noden oikealta puolelta löytyvät nodet vasemalle
			$qu = "	UPDATE dynaaminen_puu
					SET rgt = rgt - 2, muutospvm = now(), muuttaja = '$kukarow[kuka]'
					WHERE yhtio	= '{$kukarow["yhtio"]}'
					AND laji = '$laji'
					AND rgt > @myRight
					ORDER BY rgt";
	 		$re = pupe_query($qu);

			// Päivitetään poistetun noden oikealta puolelta löytyvät nodet vasemalle
			$qu = "	UPDATE dynaaminen_puu
					SET lft = lft - 2, muutospvm = now(), muuttaja = '$kukarow[kuka]'
					WHERE yhtio	= '{$kukarow["yhtio"]}'
					AND laji = '$laji'
					AND lft > @myRight
					ORDER BY lft";
	 		$re = pupe_query($qu);

			$qu = "UNLOCK TABLES";
			$re = pupe_query($qu);
		}
	}
}

// Funktio joka palauttaa valitun kategorian alakategorioiden lukumäärän, käytetään syvyyden määrittelyssä
if (!function_exists("lapset")) {
	function lapset($laji, $left) {
		global $kukarow;

		$qu = "	SELECT node.tunnus, (COUNT(parent.tunnus) - (sub_tree.depth + 1)) AS depth
				FROM dynaaminen_puu AS node
				JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft)
				JOIN (	SELECT node.tunnus AS tunnus, (COUNT(parent.tunnus) - 1) AS depth
						FROM dynaaminen_puu AS node
						JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft)
						WHERE node.yhtio = '{$kukarow["yhtio"]}'
						AND node.laji 	 = '$laji'
						AND node.lft 	 = '$left'
						GROUP BY node.tunnus
						ORDER BY node.lft) AS sub_tree
				JOIN dynaaminen_puu AS sub_parent ON (sub_parent.yhtio = node.yhtio AND sub_parent.laji = node.laji AND sub_parent.tunnus = sub_tree.tunnus AND sub_parent.lft <= node.lft AND sub_parent.rgt >= node.lft)
				WHERE node.yhtio = '{$kukarow["yhtio"]}'
				AND node.laji 	= '$laji'
				GROUP BY node.tunnus
				ORDER BY node.lft";
		$re = pupe_query($qu);

		return mysql_num_rows($re);
	}
}

// Funktio joka päivittää valitun kategorian valitulle nimelle
if (!function_exists("paivitakat")) {
	function paivitakat($laji, $koodi, $nimi, $kategoriaid) {
		global $kukarow;

		$qu = "LOCK TABLE dynaaminen_puu WRITE";
		$re = pupe_query($qu);

		$qu = "	UPDATE dynaaminen_puu
				set nimi='{$nimi}', koodi='{$koodi}', muutospvm = now(), muuttaja = '$kukarow[kuka]'
				where yhtio	= '{$kukarow["yhtio"]}'
				AND laji	= '{$laji}'
				AND tunnus	= '{$kategoriaid}'";
		$re = pupe_query($qu);

		$qu = "UNLOCK TABLES";
		$re = pupe_query($qu);
	}
}

// muuttaa left/right arvoja muutos -muuttujan verran $kohdevas -muuttujasta ylospain
if (!function_exists("muutaOksienRL")) {
	function muutaOksienRL($laji, $kohdevas, $muutos) {
		global $kukarow;

		$qu = "	UPDATE dynaaminen_puu
				SET lft = lft+{$muutos}
				WHERE yhtio = '{$kukarow["yhtio"]}'
				AND laji = '{$laji}' and lft >= '{$kohdevas}'";
		$re = pupe_query($qu);

		$qu = "	UPDATE dynaaminen_puu
				SET rgt = rgt+{$muutos}
				WHERE yhtio = '{$kukarow["yhtio"]}'
				AND laji = '{$laji}'
				and rgt >= '{$kohdevas}'";
		$re = pupe_query($qu);
	}
}

// muuttaa left/right arvoja muutos -muuttujan verran $src[lft]:n ja $src[rgt]:n valilla
if (!function_exists("muutaOksavalinRL")) {
	function muutaOksavalinRL($laji, $src, $muutos) {
		global $kukarow;

		$qu = "	UPDATE dynaaminen_puu
				SET lft = lft+{$muutos}
				WHERE yhtio = '{$kukarow["yhtio"]}'
				AND laji = '{$laji}'
				AND lft >= {$src['lft']}
				AND lft <= {$src['rgt']}";
		$re = pupe_query($qu);

		$qu = "	UPDATE dynaaminen_puu
				SET rgt = rgt+{$muutos}
				WHERE yhtio = '{$kukarow["yhtio"]}'
				AND laji = '{$laji}'
				AND rgt >= {$src['lft']}
				AND rgt <= {$src['rgt']}";
		$re = pupe_query($qu);
	}
}

// siirretaan puun oksaa, $src:ssa tulee lahteen lft/rgt ja $kohde on kohde-noden lft -arvo
if (!function_exists("siirraOksa")) {
	function siirraOksa($laji, $src, $kohde) {
		global $kukarow;

		// lasketaan siirrettavan haaran koko
		$haarakoko = $src['rgt']-$src['lft']+1;

		// tehdaan kohteeseen tilaa ja lisataan kohteen l-arvon oik. puolelle lahdehaaran koon verran
		muutaOksienRL($laji, $kohde, $haarakoko);

		// jos lahdetta on siirretty niin tehdaan sama muutos muuttujiin
		if ($src['lft'] >= $kohde) {
			$src['lft'] += $haarakoko;
			$src['rgt'] += $haarakoko;
		}

		// siirretaan lahdehaara kohteen tilalle
		muutaOksavalinRL($laji, $src, $kohde-$src['lft']);

		// korjataan siirrosta aiheutunut loppupuun venyminen
		muutaOksienRL($laji, $src['rgt']+1, -$haarakoko);
	}
}

// Siirrä puun haaraa järjestyksessä ylös tai alaspäin
if (!function_exists("SiirraTaso")) {
	function SiirraTaso($laji, $src, $suunta) {
		global $kukarow;

		if ($suunta == 'ylos') {

			$qu = "	SELECT lft, rgt
					FROM dynaaminen_puu
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND laji = '{$laji}'
					AND rgt = {$src['lft']} - 1";
			$re = pupe_query($qu);
			$kohde = mysql_fetch_array($re);

			if ($kohde['lft'] == '' or $kohde['rgt'] == '') return "fail";
			siirraOksa($laji, $src, $kohde['lft']);
		}
		if ($suunta == 'alas') {

			$qu = "	SELECT lft, rgt
					FROM dynaaminen_puu
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND laji = '{$laji}'
					AND lft = {$src['rgt']} + 1";
			$re = pupe_query($qu);
			$kohde = mysql_fetch_array($re);

			if($kohde['lft'] == '' or $kohde['rgt'] == '') return "fail";
			siirraOksa($laji, $src, $kohde['rgt']+1);
		}

		return $kohde;

	}
}

// Tarkistaa onko dynaamisen puun syvyys oikein ja paivittaa tarvittaessa
if (!function_exists("paivitapuunsyvyys")) {
	function paivitapuunsyvyys($laji) {
		global $kukarow;

		$qu = "	SELECT
				node.lft AS lft,
				node.rgt AS rgt,
				node.tunnus AS node_tunnus,
				node.syvyys as node_syvyys,
				(COUNT(node.tunnus) - 1) AS syvyys
				FROM dynaaminen_puu AS node
				JOIN dynaaminen_puu AS parent ON node.yhtio=parent.yhtio and node.laji=parent.laji AND node.lft BETWEEN parent.lft AND parent.rgt
				WHERE node.yhtio = '{$kukarow["yhtio"]}'
				AND node.laji = '{$laji}'
				GROUP BY node.lft
				ORDER BY node.lft";
		$re = pupe_query($qu);

		while ($row = mysql_fetch_assoc($re)) {

			if ($row["node_syvyys"] != $row["syvyys"]) {
				$updqu = "	UPDATE dynaaminen_puu
							SET syvyys = {$row["syvyys"]}
							WHERE yhtio	= '{$kukarow["yhtio"]}'
							AND laji	= '{$laji}'
							AND tunnus 	= {$row["node_tunnus"]}";
				$updateres = pupe_query($updqu);
			}
		}
		return;
	}
}

if (!function_exists("laskutuspaiva")) {
	function laskutuspaiva ($mika, $arki = FALSE, $seuraava_kk = 0) {

		$seuraavalaskutus = date('Y-m-d');

		if ($mika == 'eka') {
			// Eka
			$ekapaiva = date('N', mktime(0,0,0,(date('m')+$seuraava_kk),1,date('Y')));

			if ($arki and $ekapaiva > 5) {
				$seuraavalaskutus = date('Y-m-d', mktime(0,0,0,(date('m')+$seuraava_kk),(1+(8-$ekapaiva)),date('Y')));
			}
			else {
				$seuraavalaskutus = date('Y-m-d', mktime(0,0,0,(date('m')+$seuraava_kk),1,date('Y')));
			}
		}
		elseif ($mika == 'keski') {
			// Keskimmäinen
			$keskipaiva = date('N', mktime(0,0,0,date('m'), round(date('t')/2), date('Y')));

			if ($arki and $keskipaiva > 5) {
				$seuraavalaskutus = date('Y-m-d', mktime(0,0,0,date('m'), round(date('t')/2)+(8-$keskipaiva), date('Y')));
			}
			else {
				$seuraavalaskutus = date('Y-m-d', mktime(0,0,0,date('m'), round(date('t')/2), date('Y')));
			}
		}
		elseif ($mika == 'vika') {
			// Vika
			$vikapaiva = date('N', mktime(0,0,0,(date('m')+1),0,date('Y')));

			if ($arki and $vikapaiva == 7) {
				$seuraavalaskutus = date('Y-m-d', mktime(0,0,0,(date('m')+1), -2, date('Y')));
			}
			elseif ($arki and $vikapaiva == 6) {
				$seuraavalaskutus = date('Y-m-d', mktime(0,0,0,(date('m')+1), -1, date('Y')));
			}
			else {
				$seuraavalaskutus = date('Y-m-d', mktime(0,0,0,(date('m')+1),0,date('Y')));
			}
		}

		return $seuraavalaskutus;
	}
}

if (!function_exists("pupe_DataTables")) {
	function pupe_DataTables($parametrit) {
		global $yhtiorow;

		preg_match("/.*?\/\*(.*?(OK_COLOR))\*\//", $yhtiorow['css'], $varitmatch);
		preg_match("/(#[a-f0-9]{3,6});/i", $varitmatch[0], $varirgb);

		$json_pass = array();

		// Luetaan taulujen parametrit ja viedään ne javascriptille...
		foreach ($parametrit as $param) {

			$taulu			= $param[0];
			$sarakkeetVis	= $param[1];
			$sarakkeetTot	= $param[2];
			$paginate 		= isset($param[3]) ? $param[3] : "false";
			$info 			= isset($param[4]) ? $param[4] : "false";
			$sorting 		= isset($param[5]) ? $param[5] : "true";
			$aoColumns 		= "";
			$paginationtype = $paginate !== false ? "full_numbers" : "two_button";

			$json_pass[$taulu] = array($sarakkeetVis, $sarakkeetTot, $paginate, $paginationtype, $info, $sorting);
		}

		preg_match("/.*?\/\*(.*?(OK_COLOR))\*\//", $yhtiorow['css'], $varitmatch);
		preg_match("/(#[a-f0-9]{3,6});/i", $varitmatch[0], $varirgb);

		echo "<script type=\"text/javascript\" charset=\"utf-8\">

			$(document).ready(function() {

				var datatablesit = [];
				var parametrit 	 = ".json_encode($json_pass).";

				// Loopataan läpi kaikki datatablesit
				$('.dataTable').each(function(t) {

					var tableid = $(this).attr(\"id\");

					var aoColumns = [];

					for (var i = 1; i <= parametrit[tableid][0]; i++) {
						// Keikalla eka sarake on kohdistusnappula....
						if (i == 1 && tableid == \"keikka\") {
							aoColumns[i-1] = \"{\\\"bSortable\\\": false, \\\"bSearchable\\\": false, \\\"sWidth\\\": 20px}\";
						}
						else {
							aoColumns[i-1] = \"{\\\"bSortable\\\": true, \\\"bSearchable\\\": true}\";
						}
					}

					for (var i = parametrit[tableid][0]+1; i <= parametrit[tableid][1]; i++) {
						aoColumns[i-1] = \"{\\\"bSortable\\\": false, \\\"bSearchable\\\": false}\";
					}

					datatablesit[t] = $(this).dataTable( {
						\"bSortCellsTop\": true,
						\"bDestroy\" : true,
						\"bPaginate\" : parametrit[tableid][2],
						\"sPaginationType\": parametrit[tableid][3],
						\"aLengthMenu\": [[10, 25, 50, 100, 250, 500, -1], [10, 25, 50, 100, 250, 500, \"".t("Kaikki")."\"]],
						\"bInfo\" : parametrit[tableid][4],
						\"bStateSave\" : true,
						\"iDisplayLength\" : 25,
						\"oLanguage\": {
							\"sSearch\" : \"".t("Etsi taulukosta").":\",
							\"sZeroRecords\": \"".t("Ei hakuosumia")."\",
							\"sLengthMenu\": \"",t("Näytä")," _MENU_ ",t("riviä"),"\",
							\"sInfo\": \"",t("Näytetään")," _START_ - _END_ / _TOTAL_ ",t("rivistä"),"\",
							\"sInfoFiltered\": \"(",t("suodatettu kaikista")," _MAX_ ",t("rivistä"),")\",
							\"oPaginate\": {
								\"sFirst\": \"&laquo;&laquo;\",
								\"sPrevious\": \"&laquo;\",
								\"sNext\": \"&raquo;\",
								\"sLast\": \"&raquo;&raquo;\"
							}
						},
						\"bSort\" : parametrit[tableid][5],
						\"aaSorting\": [],
						\"aoColumns\": aoColumns,
						\"fnFooterCallback\": function( nRow, aaData, iStart, iEnd, aiDisplay ) {

							if (tableid == \"tyojono1\") {
								var tunnit1 = 0;
								var minsat1 = 0;
								var tunnit2 = 0;
								var minsat2 = 0;


								for (var i=iStart; i<iEnd; i++) {
									puperegexp = new RegExp(\"<span class=\\\"yhteensa\\\">(.*)<\\/span>\");

									var pupematch1 = puperegexp.exec(aaData[aiDisplay[i]][1]);
									var pupematch2 = puperegexp.exec(aaData[aiDisplay[i]][2]);

									if (pupematch1 !== null) {
										osat = pupematch1[1].split(\":\");

										tunnit1 += osat[0]*1;
										minsat1 += osat[1]*1;
									}

									if (pupematch2 !== null) {
										osat = pupematch2[1].split(\":\");

										tunnit2 += osat[0]*1;
										minsat2 += osat[1]*1;
									}
								}

								// Muutetaan sekunneiks
								tunnit1 = tunnit1*3600+minsat1*60;
								tunnit2 = tunnit2*3600+minsat2*60;

								// Ja takas tunneiks ja minuuteiks
								var tun1 = Math.round(tunnit1 / 3600);
								var min1 = Math.round((tunnit1 / 60) % 60);

								var tun2 = Math.round(tunnit2 / 3600);
								var min2 = Math.round((tunnit2 / 60) % 60);

								$('#tyom_yhteensa1').html(tun1+\":\"+min1+\"&nbsp;\");
								$('#tyom_yhteensa2').html(tun2+\":\"+min2+\"&nbsp;\");
							}

							if (tableid == \"avoimet0\") {
								var avoimety = new Array();
								var valuutat = new Array();
								var valuutta = '';

								// Summataan vain ruudulla näkyvät luvut
								for (var i=iStart; i<iEnd; i++) {
									valuutta = aaData[aiDisplay[i]][10];
									valuutat[valuutta] = valuutta;

									if (typeof avoimety[valuutta] == 'undefined') avoimety[valuutta] = 0;
									avoimety[valuutta] += aaData[aiDisplay[i]][9]*1;
								}

								// Tyhjennetään kaikki summat
								$('td[name=avoimet_yhteensa]').each(
									function() {
										$( this ).html('');
									}
								);

								// Ekotetaan vain ne valuutat jotka löytyy
								for (valuutta in valuutat) {
									if (typeof avoimety[valuutta] == 'undefined') avoimety[valuutta] = 0;
									$('#avoimet_yhteensa_'+valuutta).html(avoimety[valuutta].toFixed(2));
								}
							}

							if (tableid == \"avoimet1\") {
								var avoimety = new Array();
								var avoimetsuoriy = new Array();
								var valuutat = new Array();
								var yseroyhteensa = 0;
								var valuutta = '';

								// Summataan vain ruudulla näkyvät luvut
								for (var i=iStart; i<iEnd; i++) {
									valuutta = aaData[aiDisplay[i]][9];
									valuutat[valuutta] = valuutta;

									if (aaData[aiDisplay[i]][7] == '') {
										if (typeof avoimetsuoriy[valuutta] == 'undefined') avoimetsuoriy[valuutta] = 0;
										avoimetsuoriy[valuutta] += aaData[aiDisplay[i]][8]*1;
									}
									else {
										if (typeof avoimety[valuutta] == 'undefined') avoimety[valuutta] = 0;
										avoimety[valuutta] += aaData[aiDisplay[i]][8]*1;
									}
								}

								// Tyhjennetään kaikki summat
								$('td[name=avoimet_yhteensa]').each(
									function() {
										$( this ).html('');
									}
								);

								// Ekotetaan vain ne valuutat jotka löytyy
								for (valuutta in valuutat) {
									if (typeof avoimetsuoriy[valuutta] == 'undefined') avoimetsuoriy[valuutta] = 0;
									if (typeof avoimety[valuutta] == 'undefined') avoimety[valuutta] = 0;

									$('#avoimet_yhteensa_'+valuutta).html(avoimety[valuutta].toFixed(2));
									$('#avoimet_suoriyhteensa_'+valuutta).html(avoimetsuoriy[valuutta].toFixed(2));

									yseroyhteensa = avoimety[valuutta] + avoimetsuoriy[valuutta];
									$('#avoimet_yseroyhteensa_'+valuutta).html(yseroyhteensa.toFixed(2));
								}
							}

							if (tableid == \"laskuhaku\") {
								var summat = new Array();
								var valuutat = new Array();
								var valuutta = '';

								for (var i=iStart; i<iEnd; i++) {
									valuutta = aaData[aiDisplay[i]][3];
									valuutat[valuutta] = valuutta;

									if (typeof summat[valuutta] == 'undefined') summat[valuutta] = 0;
									summat[valuutta] += aaData[aiDisplay[i]][2]*1;
								}

								// Tyhjennetään kaikki summat
								$('td[name=yhteensa]').each(
									function() {
										$( this ).html('');
									}
								);

								// Ekotetaan vain ne valuutat jotka löytyy
								for (valuutta in valuutat) {
									if (typeof summat[valuutta] == 'undefined') summat[valuutta] = 0;
									$('#yhteensa_'+valuutta).html(summat[valuutta].toFixed(2));
								}
							}

							if (tableid == \"myyrespaakirja\") {
								var summa = 0;

								for (var i=iStart; i<iEnd; i++) {
									summa += aaData[aiDisplay[i]][6]*1;
								}

								$('#yhteensa').html(summa.toFixed(2));
							}

							if (tableid == \"toimittajahaku\") {
								var summat = new Array();
								var valuutat = new Array();
								var valuutta = '';

								for (var i=iStart; i<iEnd; i++) {
									valuutta = aaData[aiDisplay[i]][2];
									valuutat[valuutta] = valuutta;

									if (typeof summat[valuutta] == 'undefined') summat[valuutta] = 0;
									summat[valuutta] += aaData[aiDisplay[i]][1]*1;
								}

								// Tyhjennetään kaikki summat
								$('td[name=yhteensa]').each(
									function() {
										$( this ).html('');
									}
								);

								// Ekotetaan vain ne valuutat jotka löytyy
								for (valuutta in valuutat) {
									if (typeof summat[valuutta] == 'undefined') summat[valuutta] = 0;
									$('#yhteensa_'+valuutta).html(summat[valuutta].toFixed(2));
								}
							}

							if (tableid == \"saatanat_taulu\") {
								var saatavaty = new Array();
								var sarakemaara = parametrit[tableid][0]-1;

								// Summataan vain ruudulla näkyvät luvut
								for (var i=iStart; i<iEnd; i++) {

									for (var j=1; j<=sarakemaara; j++) {
										if (typeof saatavaty[j] == 'undefined') saatavaty[j] = 0;
										saatavaty[j] += aaData[aiDisplay[i]][j]*1;
									}
								}

								// Tyhjennetään kaikki summat
								$('td[name=saatavat_yhteensa]').each(
									function() {
										$( this ).html('');
									}
								);

								for (var j=1; j<=sarakemaara; j++) {
									$('#saatavat_yhteensa_'+j).html(saatavaty[j].toFixed(2));
								}
							}

							if (tableid == \"raportti_valmistuksista\") {
								var saatavaty = new Array();
								var sarakemaara = parametrit[tableid][0]-4;

								// Summataan vain ruudulla näkyvät luvut
								for (var i=iStart; i<iEnd; i++) {

									for (var j=4; j<=sarakemaara; j++) {
										if (typeof saatavaty[j] == 'undefined') saatavaty[j] = 0;
										saatavaty[j] += aaData[aiDisplay[i]][j]*1;
									}
								}

								// Tyhjennetään kaikki summat
								$('td[name=yhteensa]').each(
									function() {
										$( this ).html('');
									}
								);

								for (var j=4; j<=sarakemaara; j++) {
									$('#yhteensa_'+j).html(saatavaty[j].toFixed(2));
								}
							}
						}
					} );
				} );

				// KEIKKA.PHPN OMAT JQUERY-TOIMINNALLISUUDET
				if (datatablesit[0].attr(\"id\") == 'keikka') {

					$(window).keypress(function(e) {
					    if(e.keyCode == 13) {
					        $('#submit_tallenna').trigger('click');
					    }
					});

					// JOS RUKSATAAN/RUKSATAAN POIS JOKIN RIVI
					$('input[name^=\"rivi_valitut_tunnukset\"]').live('click', function(){
						rivi_ja_otsikko = this.value.split(\"###\", 2);
						var otunnus 	= $('input[name=\"otunnus\"]').val();

						if ($(this).is(':checked')) {

							var suuntalavan_tunnus = $('#suuntalava_'+rivi_ja_otsikko[0]+' option:selected').val();
							var suoratoimitusrivi = $('#suoratoimitus_'+rivi_ja_otsikko[0]) ? 'X' : '';

							$.post('{$_SERVER['SCRIPT_NAME']}',
								{ 	keikan_otunnus: otunnus,
									otunnus: otunnus,
									ajax_toiminto: 'kohdista_rivi',
									valittu: this.value,
									suuntalavan_tunnus: suuntalavan_tunnus,
									suoratoimitusrivi: suoratoimitusrivi,
									no_head: 'yes',
									ohje: 'off' },
								function(return_value) {
									$('.'+rivi_ja_otsikko[0]+' td').addClass('tumma');

									var data = jQuery.parseJSON(return_value);

									if (data.suuntalava != null && data.suuntalava > 0) {

										if ($('#option_'+rivi_ja_otsikko[0]+'_'+data.suuntalava).length > 0) {
											$('#option_'+rivi_ja_otsikko[0]+'_'+data.suuntalava).attr('selected', 'selected');
										}
										else {
											$('#suuntalava_'+rivi_ja_otsikko[0]+' option:last').after('<option id=\"option_'+rivi_ja_otsikko[0]+'_'+data.suuntalava+'\" value=\"'+data.suuntalava+'\" selected>'+data.suuntalava_sscc+'</option>');
										}

									}

									var summa_kohdistettu = parseFloat($('#kohdistettu_summa').html());
									var summa_jaljella = parseFloat($('#laskunsumma_jaljella').html());

									for (var key in data.uudet_hinnat) {
										summa_kohdistettu = summa_kohdistettu + parseFloat(data.uudet_hinnat[key]['alerivihinta']);
										summa_jaljella = summa_jaljella - parseFloat(data.uudet_hinnat[key]['alerivihinta']);

										$('#hinnat_'+key).html(data.uudet_hinnat[key]['rivihinta']+'<br>'+data.uudet_hinnat[key]['alerivihinta']);
										$('#alerivihintaalkuperainen_'+key).val(data.uudet_hinnat[key]['alerivihinta']);
									}

									if (summa_jaljella != 0) {
										$('#kohdistettu_summa_td font').css({'color': '#000', 'font-weight': 'normal'});
										$('#laskunsumma_jaljella_td font').css({'color': '#000', 'font-weight': 'normal'});
									}
									else {
										$('#kohdistettu_summa_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
										$('#laskunsumma_jaljella_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
									}

									$('#kohdistettu_summa').html(summa_kohdistettu.toFixed(2));
									$('#kohdistettu_rivit').html(parseInt($('#kohdistettu_rivit').html()) + 1);
									$('#kohdistettu_kpl').html(parseFloat($('#kohdistettu_kpl').html()) + parseFloat($('#rivi_kpl_alkuperainen_'+rivi_ja_otsikko[0]).val()));
									$('#laskunsumma_jaljella').html(summa_jaljella.toFixed(2));
								}
							);
						}
						else {
							$.post('{$_SERVER['SCRIPT_NAME']}',
								{ 	keikan_otunnus: otunnus,
									otunnus: 0,
									ajax_toiminto: 'kohdista_rivi',
									valittu: this.value,
									no_head: 'yes',
									ohje: 'off' },
								function(return_value) {
									$('.'+rivi_ja_otsikko[0]+' td').removeClass('tumma');

									var data = jQuery.parseJSON(return_value);

									$('#suuntalava_'+rivi_ja_otsikko[0]+' option:first').attr('selected', 'selected');
									//$('#suuntalavan_sscc_'+rivi_ja_otsikko[0]).html('');

									var summa_kohdistettu = parseFloat($('#kohdistettu_summa').html());
									var summa_jaljella = parseFloat($('#laskunsumma_jaljella').html());

									for (var key in data.uudet_hinnat) {
										summa_kohdistettu = summa_kohdistettu - parseFloat(data.vanhat_hinnat[key]['alerivihinta']);
										summa_jaljella = summa_jaljella + parseFloat(data.vanhat_hinnat[key]['alerivihinta']);

										$('#hinnat_'+key).html(data.uudet_hinnat[key]['rivihinta']+'<br>'+data.uudet_hinnat[key]['alerivihinta']);
										$('#alerivihintaalkuperainen_'+key).val(data.uudet_hinnat[key]['alerivihinta']);
									}

									if (summa_jaljella != 0) {
										$('#kohdistettu_summa_td font').css({'color': '#000', 'font-weight': 'normal'});
										$('#laskunsumma_jaljella_td font').css({'color': '#000', 'font-weight': 'normal'});
									}
									else {
										$('#kohdistettu_summa_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
										$('#laskunsumma_jaljella_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
									}

									$('#kohdistettu_summa').html(summa_kohdistettu.toFixed(2));
									$('#kohdistettu_rivit').html(parseInt($('#kohdistettu_rivit').html()) - 1);
									$('#kohdistettu_kpl').html(parseFloat($('#kohdistettu_kpl').html()) - parseFloat($('#rivi_kpl_alkuperainen_'+rivi_ja_otsikko[0]).val()));
									$('#laskunsumma_jaljella').html(summa_jaljella.toFixed(2));
								}
							);
						}
					});

					// VAIHDETAAN SUUNTALAVAA DROPDOWNISTA
					$('select[id^=\"suuntalava_\"]').live('change', function(){
						var tun		= $(this).attr('id').substring(11);
						var sscc 	= $('#suuntalava_'+tun+' option:selected').text();
						var otunnus	= $('input[name=\"otunnus\"]').val();

						$.post('{$_SERVER['SCRIPT_NAME']}',
								{	keikan_otunnus: otunnus,
									tunnus: tun,
									ajax_toiminto: 'suuntalava',
									suuntalava: this.value,
									no_head: 'yes',
									ohje: 'off' },
								function(return_value) {
									var message = jQuery.parseJSON(return_value);

									if (message == 'ok') {
										$('tr[class*=\"'+tun+'\"]').animate({'opacity':0.5},500).animate({'opacity':1},500);
										//$('#suuntalavan_sscc_'+tun).html(sscc);
									}
								}
						);
					});

					$('img[name^=\"use_as_search_\"]').live('click', function(){
						var tun = $(this).attr('name').substring(14);
						var searchbox = $(this).attr('class').substring(14);
						var kohde = 0;

						if (searchbox == 'tuoteno') {
							kohde = 1;
						}

						datatablesit[0].fnFilter(tun, kohde);
						$('input[name=\"search_'+searchbox+'\"]').val(tun);
					});

					// JOS PAINETAAN 'TALLENNA SIVUN MUUTOKSET'-NAPPIA
					$('#submit_tallenna').live('click', function() {

						$('input[name^=\"rivi_kpl\"]').each(function(){

							// OTETAAN TARVITTAVAT TUNNUKSET JA MUUTTUJAT TALTEEN
							var tun 					= $(this).attr('id').substring(9);
							var otunnus 				= $('input[name=\"otunnus\"]').val();
							var rivi_hinta_value 		= parseFloat($('#rivi_hinta_'+tun).val());
							var rivi_kpl_alkuperainen 	= parseFloat($('#rivi_kpl_alkuperainen_'+tun).val());
							var rivi_hinta_alkup		= parseFloat($('#rivihintaalkuperainen_'+tun).val());
							var alerivi_hinta_alkup		= parseFloat($('#alerivihintaalkuperainen_'+tun).val());
							var kpl 					= parseFloat(this.value);

							// JOS HINTA * KPL ON ENEMMÄN KUIN 10,2 DESIMAALIA, NIIN HERJATAAN
							if ((rivi_hinta_value * kpl) > 9999999999.99) {
								var virhe = '".t('VIRHE: liian iso loppusumma')."!';
								alert(virhe);
							}
							else {
								if (rivi_kpl_alkuperainen != kpl) {
									$.post('{$_SERVER['SCRIPT_NAME']}',
										{ 	keikan_otunnus: otunnus,
											otunnus: otunnus,
											tunnus: tun,
											ajax_toiminto: 'rivi_kpl',
											kpl: kpl,
											no_head: 'yes',
											ohje: 'off'},
										function(return_value) {
											var data = jQuery.parseJSON(return_value);

											$('#hinnat_'+tun).html(data.rivihinta+'<br>'+data.alerivihinta);
											$('#ulkkpl_'+tun).html(data.ulkkpl);
											$('#alerivihintaalkuperainen_'+tun).val(data.alerivihinta);
										}
									);

									if (rivi_kpl_alkuperainen > 1 && rivi_kpl_alkuperainen > kpl) {
										var uuden_rivin_kpl = rivi_kpl_alkuperainen - kpl;

										$.post('{$_SERVER['SCRIPT_NAME']}',
											{ 	keikan_otunnus: otunnus,
												otunnus: otunnus,
												tunnus: tun,
												ajax_toiminto: 'rivi_kpl_splittaus',
												no_head: 'yes',
												ohje: 'off',
												uuden_rivin_kpl: uuden_rivin_kpl},
											function(return_value) {

												var data = jQuery.parseJSON(return_value);

												var uusi_rivi = $('tr[class*=\"'+tun+'\"]').clone();

												// MUUTETAAN KLOONATUN RIVIN (<tr>) CLASSI UUDELLE TILAUSRIVIN TUNNUKSELLE
												$(uusi_rivi).attr('class', $(uusi_rivi).attr('class').replace(tun, data.tunnus));

												$(uusi_rivi).insertAfter('tr[class*=\"'+tun+'\"]').show(500).animate({'opacity':0.5},200).animate({'opacity':1},200).animate({'opacity':0.5},200).animate({'opacity':1},200,'linear');

												$('tr[class*=\"'+data.tunnus+'\"]').find('td, div, input, a, span').each(function() {

													if ($(this).attr('id') != false && $(this).attr('id') != undefined) {
														$(this).attr('id', $(this).attr('id').replace(tun, data.tunnus));
													}

													if ($(this).attr('class') != false && $(this).attr('class') != undefined) {
														$(this).attr('class', $(this).attr('class').replace(tun, data.tunnus));
													}

													if ($(this).attr('name') != false && $(this).attr('name') != undefined) {
														$(this).attr('name', $(this).attr('name').replace(tun, data.tunnus));
													}

													if ($(this).is('input') && $(this).val() != false && $(this).val() != undefined) {
														$(this).val($(this).val().replace(tun, data.tunnus));
													}

													if ($(this).is('a') && $(this).attr('href') != false && $(this).attr('href') != undefined) {
														$(this).attr('href', $(this).attr('href').replace(new RegExp(tun, 'g'), data.tunnus));
													}
												});

												$('tr[class*=\"'+data.tunnus+'\"]').find('#rivi_kpl_'+data.tunnus).val(uuden_rivin_kpl);
												$('tr[class*=\"'+data.tunnus+'\"]').find('#ulkkpl_'+data.tunnus).html(uuden_rivin_kpl);
												$('#hinnat_'+data.tunnus).html(data.rivihinta+'<br>'+data.alerivihinta);
												$('#rivi_kpl_alkuperainen_'+tun).val(kpl);
												$('#rivi_kpl_alkuperainen_'+data.tunnus).val(uuden_rivin_kpl);

												$('#refresh_screen_ajaxille').val('true');
											}
										);
									}
									else {
										$('#rivi_kpl_alkuperainen_'+tun).val(kpl);

										// Päivitetään 'Kohdistettu Summa'
										if ($('#rivi_valitut_tunnukset_'+tun).is(':checked')) {
											// Vähennetään summista rivin alkuperäinen arvo
											var summa_kohdistettu = parseFloat($('#kohdistettu_summa').html()) - alerivi_hinta_alkup;
											var summa_jaljella = parseFloat($('#laskunsumma_jaljella').html()) + alerivi_hinta_alkup;

											// Lisätään uusilla arvoilla
											summa_kohdistettu = summa_kohdistettu + ((alerivi_hinta_alkup/rivi_kpl_alkuperainen) * kpl);
											summa_jaljella = summa_jaljella - ((alerivi_hinta_alkup/rivi_kpl_alkuperainen) * kpl);

											if (summa_jaljella != 0) {
												$('#kohdistettu_summa_td font').css({'color': '#000', 'font-weight': 'normal'});
												$('#laskunsumma_jaljella_td font').css({'color': '#000', 'font-weight': 'normal'});
											}
											else {
												$('#kohdistettu_summa_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
												$('#laskunsumma_jaljella_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
											}

											$('#kohdistettu_summa').html(summa_kohdistettu.toFixed(2));
											$('#laskunsumma_jaljella').html(summa_jaljella.toFixed(2));
										}
									}
								}
							}
						});

						$('input[name^=\"rivi_ale\"]').each(function(){

							var monesko				= $(this).attr('name').substring(8,9);
							var tun 				= $(this).attr('id').substring(10);
							var otunnus 			= $('input[name=\"otunnus\"]').val();
							var rivi_ale_alkup		= parseFloat($('#rivialealkuperainen'+monesko+'_'+tun).val());
							var alerivi_hinta_alkup	= parseFloat($('#alerivihintaalkuperainen_'+tun).val());
							var ale 				= parseFloat(this.value);

							if (rivi_ale_alkup != ale) {
								$.post('{$_SERVER['SCRIPT_NAME']}',
									{ 	keikan_otunnus: otunnus,
										otunnus: otunnus,
										tunnus: tun,
										ajax_toiminto: 'rivi_ale',
										ale: ale,
										no_head: 'yes',
										ohje: 'off',
										monesko_kentta: monesko},
									function(return_value) {
										var data = jQuery.parseJSON(return_value);
										$('#hinnat_'+tun).html(data.rivihinta+'<br>'+data.alerivihinta);
										$('#rivialealkuperainen'+monesko+'_'+tun).val(ale);
										$('#alerivihintaalkuperainen_'+tun).val(data.alerivihinta);

										// Päivitetään 'Kohdistettu Summa'
										if ($('#rivi_valitut_tunnukset_'+tun).is(':checked')) {
											// Vähennetään summista rivin alkuperäinen arvo
											var summa_kohdistettu = parseFloat($('#kohdistettu_summa').html()) - alerivi_hinta_alkup;
											var summa_jaljella = parseFloat($('#laskunsumma_jaljella').html()) + alerivi_hinta_alkup;

											// Lisätään uusilla arvoilla
											summa_kohdistettu = summa_kohdistettu + data.alerivihinta;
											summa_jaljella = summa_jaljella - data.alerivihinta;

											if (summa_jaljella != 0) {
												$('#kohdistettu_summa_td font').css({'color': '#000', 'font-weight': 'normal'});
												$('#laskunsumma_jaljella_td font').css({'color': '#000', 'font-weight': 'normal'});
											}
											else {
												$('#kohdistettu_summa_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
												$('#laskunsumma_jaljella_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
											}

											$('#kohdistettu_summa').html(summa_kohdistettu.toFixed(2));
											$('#laskunsumma_jaljella').html(summa_jaljella.toFixed(2));
										}
									}
								);
							}
						});

						$('input[name^=\"rivi_hinta\"]').each(function(){
							var tun 				= $(this).attr('id').substring(11);
							var otunnus 			= $('input[name=\"otunnus\"]').val();
							var rivi_kpl_value 		= parseFloat($('#rivi_kpl_'+tun).val());
							var rivi_hinta_alkup	= parseFloat($('#rivihintaalkuperainen_'+tun).val());
							var alerivi_hinta_alkup	= parseFloat($('#alerivihintaalkuperainen_'+tun).val());
							var hinta 				= parseFloat(this.value.replace(',','.'));

							if ((rivi_kpl_value * hinta) > 9999999999.99) {
								var virhe = '".t('VIRHE: liian iso loppusumma')."!';
								alert(virhe);
							}
							else if (rivi_hinta_alkup != hinta) {

								$.post('{$_SERVER['SCRIPT_NAME']}',
									{ 	keikan_otunnus: otunnus,
										otunnus: otunnus,
										tunnus: tun,
										ajax_toiminto: 'rivi_hinta',
										hinta: hinta,
										no_head: 'yes',
										ohje: 'off'},
									function(return_value) {
										var data = jQuery.parseJSON(return_value);
										$('#rivin_hinta_'+tun).html(data.hinta);
										$('#hinnat_'+tun).html(data.rivihinta+'<br>'+data.alerivihinta);
										$('#rivihintaalkuperainen_'+tun).val(hinta);
										$('#alerivihintaalkuperainen_'+tun).val(data.alerivihinta);

										// Päivitetään 'Kohdistettu Summa'
										if ($('#rivi_valitut_tunnukset_'+tun).is(':checked')) {
											// Vähennetään summista rivin alkuperäinen arvo
											var summa_kohdistettu = parseFloat($('#kohdistettu_summa').html()) - alerivi_hinta_alkup;
											var summa_jaljella = parseFloat($('#laskunsumma_jaljella').html()) + alerivi_hinta_alkup;

											// Lisätään uusilla arvoilla
											summa_kohdistettu = summa_kohdistettu + data.alerivihinta;
											summa_jaljella = summa_jaljella - data.alerivihinta;

											if (summa_jaljella != 0) {
												$('#kohdistettu_summa_td font').css({'color': '#000', 'font-weight': 'normal'});
												$('#laskunsumma_jaljella_td font').css({'color': '#000', 'font-weight': 'normal'});
											}
											else {
												$('#kohdistettu_summa_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
												$('#laskunsumma_jaljella_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
											}

											$('#kohdistettu_summa').html(summa_kohdistettu.toFixed(2));
											$('#laskunsumma_jaljella').html(summa_jaljella.toFixed(2));
										}
									}
								);
							}

						});

						$('input[name^=\"rivi_toimitettuaika_vv\"]').each(function(){

							var tun 				= $(this).attr('id').substring(23);
							var otunnus 			= $('input[name=\"otunnus\"]').val();

							var rivi_taika_vv		= parseFloat(this.value);
							var rivi_taika_kk		= parseFloat($('input[name=\"rivi_toimitettuaika_kk_'+tun+'\"]').val());
							var rivi_taika_pp		= parseFloat($('input[name=\"rivi_toimitettuaika_pp_'+tun+'\"]').val());

							if (rivi_taika_vv > 0 && rivi_taika_kk > 0 && rivi_taika_pp > 0) {
								$.post('{$_SERVER['SCRIPT_NAME']}',
									{ 	keikan_otunnus: otunnus,
										otunnus: otunnus,
										tunnus: tun,
										ajax_toiminto: 'rivi_toimitettuaika',
										rivi_taika_vv: rivi_taika_vv,
										rivi_taika_kk: rivi_taika_kk,
										rivi_taika_pp: rivi_taika_pp,
										no_head: 'yes',
										ohje: 'off'},
									function(return_value) {
										var data = jQuery.parseJSON(return_value);
									}
								);
							}
						});

					}).ajaxComplete(function(){
						if ($('#refresh_screen_ajaxille').val() == 'true') {
							$('#refresh_screen_ajaxille').val('false');
							var t = setTimeout('document.formi1.submit()', 1000);
						}
					});

					// KOHDISTETAAN SIVUN KAIKKI RIVIT
					$('#submit_kohd_kaikki').live('click', function(){
						var otunnus = $('input[name=\"otunnus\"]').val();

						$('input[name^=\"rivi_valitut_tunnukset\"]').each(function(){
							rivi_ja_otsikko = this.value.split(\"###\", 2);

							if (!$('#rivi_valitut_tunnukset_'+rivi_ja_otsikko[0]).is(':checked')) {
								var suuntalavan_tunnus = $('#suuntalava_'+rivi_ja_otsikko[0]+' option:selected').val();
								var suoratoimitusrivi = $('#suoratoimitus_'+rivi_ja_otsikko[0]) ? 'X' : '';

								$.post('{$_SERVER['SCRIPT_NAME']}',
									{ 	keikan_otunnus: otunnus,
										otunnus: otunnus,
										ajax_toiminto: 'kohdista_rivi',
										valittu: this.value,
										suuntalavan_tunnus: suuntalavan_tunnus,
										suoratoimitusrivi: suoratoimitusrivi,
										no_head: 'yes',
										ohje: 'off' },
									function(return_value) {
										var data = jQuery.parseJSON(return_value);

										if (data.suuntalava != null && data.suuntalava > 0) {

											if ($('#option_'+rivi_ja_otsikko[0]+'_'+data.suuntalava).length > 0) {
												$('#option_'+rivi_ja_otsikko[0]+'_'+data.suuntalava).attr('selected', 'selected');
											}
											else {
												$('#suuntalava_'+rivi_ja_otsikko[0]+' option:last').after('<option id=\"option_'+rivi_ja_otsikko[0]+'_'+data.suuntalava+'\" value=\"'+data.suuntalava+'\" selected>'+data.suuntalava_sscc+'</option>');
											}
										}

										var summa_kohdistettu = parseFloat($('#kohdistettu_summa').html());
										var summa_jaljella = parseFloat($('#laskunsumma_jaljella').html());

										for (var key in data.uudet_hinnat) {
											summa_kohdistettu = summa_kohdistettu + parseFloat(data.uudet_hinnat[key]['alerivihinta']);
											summa_jaljella = summa_jaljella - parseFloat(data.uudet_hinnat[key]['alerivihinta']);

											$('#hinnat_'+key).html(data.uudet_hinnat[key]['rivihinta']+'<br>'+data.uudet_hinnat[key]['alerivihinta']);
											$('#alerivihintaalkuperainen_'+key).val(data.uudet_hinnat[key]['alerivihinta']);
											$('.'+key+' td').addClass('tumma');
										}

										if (summa_jaljella != 0) {
											$('#kohdistettu_summa_td font').css({'color': '#000', 'font-weight': 'normal'});
											$('#laskunsumma_jaljella_td font').css({'color': '#000', 'font-weight': 'normal'});
										}
										else {
											$('#kohdistettu_summa_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
											$('#laskunsumma_jaljella_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
										}

										$('#kohdistettu_summa').html(summa_kohdistettu.toFixed(2));
										$('#kohdistettu_rivit').html(parseInt($('#kohdistettu_rivit').html()) + 1);
										$('#kohdistettu_kpl').html(parseFloat($('#kohdistettu_kpl').html()) + parseFloat($('#rivi_kpl_alkuperainen_'+rivi_ja_otsikko[0]).val()));
										$('#laskunsumma_jaljella').html(summa_jaljella.toFixed(2));
									}
								);

								$(this).attr('checked', 'checked');
							}
						});
					});

					// POISTETAAN KAIKKI KOHDISTUKSET
					$('#submit_kohd_eimit').live('click', function(){
						var otunnus = $('input[name=\"otunnus\"]').val();

						$('input[name^=\"rivi_valitut_tunnukset\"]').each(function(){
							rivi_ja_otsikko = this.value.split(\"###\", 2);

							if ($('#rivi_valitut_tunnukset_'+rivi_ja_otsikko[0]).is(':checked')) {

								$.post('{$_SERVER['SCRIPT_NAME']}',
									{ 	keikan_otunnus: otunnus,
										otunnus: 0,
										ajax_toiminto: 'kohdista_rivi',
										valittu: this.value,
										no_head: 'yes',
										ohje: 'off' },
									function(return_value) {
										var data = jQuery.parseJSON(return_value);

										$('#suuntalava_'+rivi_ja_otsikko[0]+' option:first').attr('selected', 'selected');
										//$('#suuntalavan_sscc_'+rivi_ja_otsikko[0]).html('');

										var summa_kohdistettu = parseFloat($('#kohdistettu_summa').html());
										var summa_jaljella = parseFloat($('#laskunsumma_jaljella').html());

										for (var key in data.uudet_hinnat) {
											summa_kohdistettu = summa_kohdistettu - parseFloat(data.vanhat_hinnat[key]['alerivihinta']);
											summa_jaljella = summa_jaljella + parseFloat(data.vanhat_hinnat[key]['alerivihinta']);

											$('#hinnat_'+key).html(data.uudet_hinnat[key]['rivihinta']+'<br>'+data.uudet_hinnat[key]['alerivihinta']);
											$('#alerivihintaalkuperainen_'+key).val(data.uudet_hinnat[key]['alerivihinta']);
											$('.'+key+' td').removeClass('tumma');
										}

										if (summa_jaljella != 0) {
											$('#kohdistettu_summa_td font').css({'color': '#000', 'font-weight': 'normal'});
											$('#laskunsumma_jaljella_td font').css({'color': '#000', 'font-weight': 'normal'});
										}
										else {
											$('#kohdistettu_summa_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
											$('#laskunsumma_jaljella_td font').css({'color': '{$varirgb[1]}', 'font-weight': 'bold'});
										}

										$('#kohdistettu_summa').html(summa_kohdistettu.toFixed(2));
										$('#kohdistettu_rivit').html(parseInt($('#kohdistettu_rivit').html()) - 1);
										$('#kohdistettu_kpl').html(parseFloat($('#kohdistettu_kpl').html()) - parseFloat($('#rivi_kpl_alkuperainen_'+rivi_ja_otsikko[0]).val()));
										$('#laskunsumma_jaljella').html(summa_jaljella.toFixed(2));
									}
								);

								$(this).attr('checked', '');
							}
						});
					});
				}

				// Sarakefiltteröinti, toistaiseksi vain ekalle taululle!
				$(\"thead input\").keyup( function () {
					var arvo = this.value;
					var sarake = $(\"thead input\").index(this);

					// fnFilter toistaiseksi vain ekalle tablelle

					// Onko regular expressioni?
					if (arvo.indexOf(\"|\") > -1) {
						datatablesit[0].fnFilter( arvo, sarake, true, false );
					}
					else {
						datatablesit[0].fnFilter( arvo, sarake, false, true );
					}

					// filtteröidään työjono.php:ssa myös alempaa taulua, jos ja vain jos alempi taulu on olemassa
					if (datatablesit[0].attr(\"id\") == \"tyojono0\" && datatablesit[1] != undefined) {
						var tyojono = [];

						$(\"span[class=tyom_id]\").each(function(i) {
							tyojono[i] = this.innerHTML;
						});

						var jonosearch = tyojono.join(\"|\");

						if (arvo != \"\" && jonosearch == \"\") {
							// ei näytetä mitään
							jonosearch = \"xyzzypupe\";
						}

						datatablesit[1].fnFilter( jonosearch, 0, true, false, true );
					}
				} );

				// Presorttaus, eli luetaan cookiesta tarvittavat tiedot, toistaiseksi vain ekalle taululle!
				var oSettings = datatablesit[0].fnSettings();

				$(\"thead input\").each(function(u){
					if (oSettings.aoPreSearchCols[u]['sSearch']!=''){
						$(this).val(oSettings.aoPreSearchCols[u]['sSearch']);
					}
				} );

				if (oSettings.oPreviousSearch['sSearch']!=''){
					$('.search_field').val(oSettings.oPreviousSearch['sSearch']);
				}

				// Prioriteetin rajausta dropdownilla
				$('select.prioriteetti_sort').change(function() {
					var prioriteetti = $('select.prioriteetti_sort option:selected');
					var haku_arvo = prioriteetti.val();

					console.log(prioriteetti.val());
					datatablesit[0].fnFilter(haku_arvo, 1);
				} );

				// Työjonon ja työstatuksen rajausta dropdownilla
				$('select.tyojono_sort, select.tyostatus_sort').change(function() {
					var tyojono = $('select.tyojono_sort option:selected');
					var tyostatus = $('select.tyostatus_sort option:selected');
					var hakuarvo;

					// Jos tyojono tai tyostatus on tyhja, haetaan vain yhdellä ehdolla
					haku_arvo = tyojono.val() +' '+ tyostatus.val();

					datatablesit[0].fnFilter(haku_arvo, 8, true, false);
				} );
			} );
		</script>";
	}
}

if (!function_exists('hae_suuntalava')) {
	function hae_suuntalava($otunnus, $tunnus) {
		global $kukarow;

		$query = "	SELECT suuntalavat.tunnus,
					if (suuntalavat.alkuhyllyalue != '', concat(rpad(upper(suuntalavat.alkuhyllyalue),  5, '0'),lpad(upper(suuntalavat.alkuhyllynro),  5, '0'),lpad(upper(suuntalavat.alkuhyllyvali),  5, '0'),lpad(upper(suuntalavat.alkuhyllytaso),  5, '0')), '') alkuhyllypaikka,
					if (suuntalavat.loppuhyllyalue != '', concat(rpad(upper(suuntalavat.loppuhyllyalue),  5, '0'),lpad(upper(suuntalavat.loppuhyllynro),  5, '0'),lpad(upper(suuntalavat.loppuhyllyvali),  5, '0'),lpad(upper(suuntalavat.loppuhyllytaso),  5, '0')), '') loppuhyllypaikka
					FROM tilausrivi
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
					JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio
										AND suuntalavat.keraysvyohyke = tuote.keraysvyohyke
										AND suuntalavat.keikkatunnus IN (0, {$otunnus})
										AND suuntalavat.tila = ''
										AND (concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0'),lpad(upper(alkuhyllyvali),  5, '0'),lpad(upper(alkuhyllytaso),  5, '0')) <= concat(rpad(upper(hyllyalue),  5, '0'),lpad(upper(hyllynro),  5, '0'),lpad(upper(hyllyvali),  5, '0'),lpad(upper(hyllytaso),  5, '0')) or alkuhyllyalue = '')
										AND (concat(rpad(upper(loppuhyllyalue),  5, '0'),lpad(upper(loppuhyllynro),  5, '0'),lpad(upper(loppuhyllyvali),  5, '0'),lpad(upper(loppuhyllytaso),  5, '0')) >= concat(rpad(upper(hyllyalue),  5, '0'),lpad(upper(hyllynro),  5, '0'),lpad(upper(hyllyvali),  5, '0'),lpad(upper(hyllytaso),  5, '0')) or loppuhyllyalue = ''))
					WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
					AND tilausrivi.tunnus = '{$tunnus}'
					ORDER BY alkuhyllypaikka
					LIMIT 1";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);

		$suuntalava = (trim($row['tunnus']) != '' and trim($row['tunnus']) > 0) ? $row['tunnus'] : 0;

		return $suuntalava;
	}
}

if (!function_exists("seuraava_lahtoaika")) {
	function seuraava_lahtoaika($ttunnus) {

		global $yhtiorow, $kukarow;

		// ttunnus = toimitustapa tunnus
		// $viikonpvmnro = viikonpäivän numero-arvo
		// 0 = sunnuntai, 1 = maanantai ... 6 = lauantai
		// Käytetään PHP:n syntaksia date("w"), ei mysql !!!
		// Päivämäärä array, nimellinen ilmentymä toiselle puolelle on kiva.

		$viikonpaiva = date("w", time());

		$pvmnimet = array();
		$pvmnimet[1] = t("Maanantai");
		$pvmnimet[2] = t("Tiistai");
		$pvmnimet[3] = t("Keskiviikko");
		$pvmnimet[4] = t("Torstai");
		$pvmnimet[5] = t("Perjantai");
		$pvmnimet[6] = t("Lauantai");
		$pvmnimet[0] = t("Sunnuntai");

		$query = 	"	(SELECT '1' prio, concat(curdate(), ' ', lahdon_kellonaika) as lahto, lahdon_viikonpvm, lahdon_kellonaika
						FROM toimitustavan_lahdot
						WHERE yhtio = '$kukarow[yhtio]'
						AND liitostunnus = '$ttunnus'
						and viimeinen_tilausaika > curtime()
						and lahdon_viikonpvm = '$viikonpaiva'
						AND aktiivi != 'E')
						UNION
						(SELECT '2' prio, concat(adddate(curdate(), (lahdon_viikonpvm-$viikonpaiva)), ' ', lahdon_kellonaika) as lahto, lahdon_viikonpvm, lahdon_kellonaika
						FROM toimitustavan_lahdot
						WHERE yhtio = '$kukarow[yhtio]'
						AND liitostunnus = '$ttunnus'
						and lahdon_viikonpvm > '$viikonpaiva'
						AND aktiivi != 'E')
						UNION
						(SELECT '3' prio, concat(adddate(curdate(),  (7-$viikonpaiva+lahdon_viikonpvm)), ' ', lahdon_kellonaika) as lahto, lahdon_viikonpvm, lahdon_kellonaika
						FROM toimitustavan_lahdot
						WHERE yhtio = '$kukarow[yhtio]'
						AND liitostunnus = '$ttunnus'
						AND lahdon_viikonpvm <= '$viikonpaiva'
						AND aktiivi != 'E')
						ORDER BY prio, lahdon_viikonpvm, lahdon_kellonaika
						LIMIT 1";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			$rivi = mysql_fetch_assoc($result);
			return $rivi["lahto"];
		}
		else {
			return "0000-00-00 00:00:00";
		}
	}
}

// Käsitellään XML-entiteetit
if (!function_exists("xmlentities")) {
	function xmlentities($string) {
   		return preg_replace("/[^\x20-\xFF]/", "", str_replace(array('&', '"', "'", '<', '>', '´', '`'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;', '&apos;', '&apos;'), trim($string)));
	}
}

if (!function_exists("xml_add")) {
	function xml_add ($joukko, $tieto, $handle, $maxpituus = '', $minpituus = '') {
		global $yhtiorow, $lasrow;

		$ulos = "<$joukko>";

		if (strlen($tieto) > 0 or $minpituus != '') {

			if ($maxpituus != "" and is_numeric($maxpituus) and strlen($tieto) > $maxpituus) {
				$tieto = substr($tieto, 0, $maxpituus);
			}

			$tieto = xmlentities($tieto);

			if ($minpituus != "" and is_numeric($minpituus) and strlen($tieto) < $minpituus) {
				if ($yhtiorow["verkkolasku_lah"] == "maventa" and ($joukko == "InvoiceRecipientTownName" or $joukko == "InvoiceRecipientPostCodeIdentifier")) {
					$tieto = str_pad($tieto, $minpituus,'.');
				}
				else {
					$tieto = str_pad($tieto, $minpituus);
				}
			}

			$ulos .= $tieto;
		}

		$pos = strpos($joukko, " ");

		if ($pos === FALSE) {
			$ulos .= "</$joukko>";
        }
        else {
			$ulos .= "</".substr($joukko,0,$pos).">";
        }

		if ($lasrow["chn"] == "112" or $yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice" or $yhtiorow["verkkolasku_lah"] == "apix") {
			//	Jos tehdään finvoicea rivinvaihto on \r\n
			$ulos .= "\r\n";
		}
		else {
			$ulos .= "\n";
		}

		fputs($handle, $ulos);
	}
}

if (!function_exists("alvilmo_kassa_ale_erittely")) {
	function alvilmo_kassa_ale_erittely ($alkupvm, $loppupvm, $maalisa, $tiliointilisa, $ryhma, $oletus_verokanta, $yhteenvetolilmo = FALSE) {
		global $yhtiorow, $kukarow;

		$kakerroinlisa  = 1;
		$kerroin 		= '';

		if ($ryhma == 'fi313') {
			// Tavaraostot muista EU-maista
			$ryhma = 'fi305';
		}
		elseif ($ryhma == 'fi314') {
			// Palveluostot muista EU-maista
			$ryhma = 'fi306';
		}

		if ($ryhma == 'fi311' or $ryhma == 'fi312') {
			$kerroin = " * -1 ";
		}

		if ($ryhma == 'fi305' or $ryhma == 'fi306' or $ryhma == 'fi320') {
			// Hetaan yhteisöostot/käänteisen alvin joilla on kassa-aletta
			$query = "	SELECT
						group_concat(DISTINCT tiliointi.tunnus) tilioinnit
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio AND lasku.tunnus = tiliointi.ltunnus)
						JOIN tiliointi tiliointi2 ON (lasku.yhtio = tiliointi2.yhtio AND lasku.tunnus = tiliointi2.ltunnus)
						JOIN tili ON (tili.yhtio = tiliointi2.yhtio and tili.tilino = tiliointi2.tilino AND tili.alv_taso like '%$ryhma%')
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND tiliointi.tapvm >= '$alkupvm'
						AND tiliointi.tapvm <= '$loppupvm'
						AND tiliointi.tilino = '$yhtiorow[kassaale]'";
			$ktres = pupe_query($query);
		}
		else {
			// Hetaan yhteisömyynti, palvelut ja tuotteet erikseen jotta osataan splitata kassa-alennus tavaramyyntiin ja palvelumyyntIin
			$query = "	SELECT
						sum(if(tuote.tuotetyyppi='K',  tilausrivi.rivihinta, 0)) palvelut,
						sum(if(tuote.tuotetyyppi!='K', tilausrivi.rivihinta, 0)) tavarat,
						sum(tilausrivi.rivihinta) yhteensa,
						group_concat(DISTINCT tiliointi.tunnus) tilioinnit
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio AND lasku.tunnus = tiliointi.ltunnus and lasku.vienti = 'E')
						JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
						JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]')
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio AND tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND tiliointi.tapvm >= '$alkupvm'
						AND tiliointi.tapvm <= '$loppupvm'
						$maalisa
						$tiliointilisa
						AND tiliointi.tilino = '$yhtiorow[myynninkassaale]'";
			$ktres = pupe_query($query);
		}

		if (mysql_num_rows($ktres) == 1) {
			$ktrow = mysql_fetch_assoc($ktres);

			if ($ktrow["tilioinnit"] != "" and ($ryhma == 'fi305' or $ryhma == 'fi306' or $ryhma == 'fi320' or $ktrow["yhteensa"] != 0)) {

				// Palvelut ja tuotteet erikseen
				if ($ryhma == 'fi311') {
					$kakerroinlisa = $ktrow["tavarat"]/$ktrow["yhteensa"];
				}
				elseif ($ryhma == 'fi312') {
					$kakerroinlisa = $ktrow["palvelut"]/$ktrow["yhteensa"];
				}

				$selec = "	if(lasku.toim_maa!='', lasku.toim_maa, if(lasku.maa != '', lasku.maa, '$yhtiorow[maa]')) maa,
							if(lasku.valkoodi = '', '$yhtiorow[valkoodi]', lasku.valkoodi) valuutta,
							tiliointi.vero vero,
							tiliointi.tilino tilino,
							tili.nimi nimi,";
				$group = "	maa, valuutta, vero, tilino, nimi";

				if ($yhteenvetolilmo) {
					$selec = "	lasku.ytunnus ytunnus,
								if(lasku.toim_maa = '', '$yhtiorow[maa]', lasku.toim_maa) maa,
								max(lasku.nimi) laskunimi, ";
					$group = "	ytunnus, maa";
				}

				$query = "	SELECT
							$selec
							sum(round(tiliointi.summa * (1 + ($oletus_verokanta / 100)), 2)) $kerroin bruttosumma,
							sum(round(tiliointi.summa * ($oletus_verokanta / 100), 2)) $kerroin verot,
							sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * (1 + ($oletus_verokanta / 100)), 2)) $kerroin bruttosumma_valuutassa,
							sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * ($oletus_verokanta / 100), 2)) $kerroin verot_valuutassa,
							count(*) kpl
							FROM tiliointi
							JOIN lasku ON (lasku.yhtio = tiliointi.yhtio AND lasku.tunnus = tiliointi.ltunnus)
							LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio AND tiliointi.tilino = tili.tilino)
							WHERE tiliointi.yhtio = '$kukarow[yhtio]'
							AND tiliointi.tunnus in ($ktrow[tilioinnit])
							GROUP BY $group
							ORDER BY $group";
				$ttres = pupe_query($query);

				return array($kakerroinlisa, $ttres);
			}
			return array(NULL, NULL);
		}
		return array(NULL, NULL);
	}
}

if (!function_exists("hintapyoristys")) {
	function hintapyoristys($luku, $desimaalit=0, $loppunollat_pois=FALSE) {
		global $yhtiorow;

		$luku = (float) $luku;

		if ((int) $desimaalit > 0) {
			$desit = (int) $desimaalit;
		}
		else {
			$desit = (int) $yhtiorow['hintapyoristys'];
		}

		// käännetään luku desimaaliluvuksi parametrin mukaisesti
		$luku = sprintf("%.{$desit}f", round($luku, $desit));

		if ($yhtiorow["hintapyoristys_loppunollat"] == "P" or (int) $desimaalit > 0 or $loppunollat_pois) {
			// poistetaan nollat lopusta
			$luku = rtrim($luku, "0");

			// katotaan montako desimaalia luvussa on
			$desimaaleja = strlen($luku) - strpos($luku, ".") - 1;

			// jos alle kaksi desimaalia, niin näytetään silti kaksi
			if ($desimaaleja < 2) {
				$luku = sprintf("%.02f", $luku);
			}
		}

		return $luku;
	}
}

if (!function_exists("palauta_vak_tiedot")) {
	function palauta_vak_tiedot ($otunnus, $kieli='', $tyyppi='') {
		global $kukarow, $yhtiorow;

		if ($tyyppi == "DGD") $kieli = "en";

		// Käytetäänkö VAK-tietokantaa
		if ($yhtiorow["vak_kasittely"] != "") {

			if (isset($kieli) and strtolower($kieli) != "fi") {
				$nimityskuvaus = "name_and_description";
			}
			else {
				$nimityskuvaus = "nimi_ja_kuvaus";
			}

			$vakselect = "	concat(vak.yk_nro, vak.name_and_description, vak.lipukkeet, vak.pakkausryhma) vakkoodi,
							concat('UN', vak.yk_nro) yk_nro,
							vak.name_and_description nimitys,
							vak.lipukkeet,
							vak.pakkausryhma,
							vak.rajoitetut_maarat_ja_poikkeusmaarat_1 limited_qty";
			$vakjoin   = "JOIN vak ON tuote.yhtio = vak.yhtio and tuote.vakkoodi = vak.tunnus";
		}
		else {
			$vakselect = "	tuote.vakkoodi vakkoodi,
							'' yk_nro,
							'' nimitys,
							'' lipukkeet,
							'' pakkausryhma,
							'' limited_qty";
			$vakjoin   = "";
		}

		$query = "  SELECT $vakselect,
					if(tuote.myynti_era > 0,tuote.myynti_era , 1) era,
					tuote.vakmaara tilavuus,
					tuote.pakkausmateriaali materiaali,
					sum(tilausrivi.varattu+tilausrivi.kpl) kpl,
					sum((tilausrivi.varattu+tilausrivi.kpl)*tuote.tuotemassa) tuotepaino
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					$vakjoin
					JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi  = 'L'
					and tilausrivi.otunnus IN ($otunnus)
					and tuote.vakkoodi not in ('','0')
					and (tilausrivi.varattu+tilausrivi.kpl) > 0
					GROUP BY 1,2,3,4,5,6,7,8,9
					ORDER BY 1,2,3,4,5,6,7,8,9";
		$riresult1 = pupe_query($query);

		$query = "  SELECT distinct r1.tunnus, r1.pakkaus, r1.pakkauskuvaus, r1.kollit, round(r1.kilot,1) kilot
					FROM rahtikirjat r1
					LEFT JOIN rahtikirjat r2 ON (r1.yhtio = r2.yhtio and r1.rahtikirjanro=r2.rahtikirjanro and r2.rahtikirjanro != 0)
					WHERE r1.otsikkonro in ($otunnus)
					and r1.yhtio = '$kukarow[yhtio]'";
		$riresult2 = pupe_query($query);

		//* Limited Quantity tiedot *//
		//Inner packagings placed in shrink-wrapped or stretch-wrapped trays a Maximum net quantity
		/*
		$limited_qty = array(
		"LQ0"	=> "0",
		"LQ1"	=> "120",
		"LQ2"	=> "1000",
		"LQ3"	=> "0",
		"LQ4"	=> "1000",
		"LQ5"	=> "1000",
		"LQ6"	=> "1000",
		"LQ7"	=> "5000",
		"LQ8"	=> "500",
		"LQ9"	=> "3000",
		"LQ10"	=> "500",
		"LQ11"	=> "500",
		"LQ12"	=> "1000",
		"LQ13"	=> "1000",
		"LQ14"	=> "25",
		"LQ15"	=> "100",
		"LQ16"	=> "125",
		"LQ17"	=> "100",
		"LQ18"	=> "500",
		"LQ19"	=> "5000",
		"LQ20"	=> "0",
		"LQ21"	=> "0",
		"LQ22"	=> "500",
		"LQ23"	=> "1000",
		"LQ24"	=> "2000",
		"LQ25"	=> "1000",
		"LQ26"	=> "500",
		"LQ27"	=> "6000",
		"LQ28"	=> "3000");
		*/

		//Combination packagings a Maximum net quantity
		$limited_qty = array(
		"LQ0"	=> "0",
		"LQ1"	=> "120",
		"LQ2"	=> "1000",
		"LQ3"	=> "500",
		"LQ4"	=> "3000",
		"LQ5"	=> "5000",
		"LQ6"	=> "5000",
		"LQ7"	=> "5000",
		"LQ8"	=> "3000",
		"LQ9"	=> "6000",
		"LQ10"	=> "500",
		"LQ11"	=> "500",
		"LQ12"	=> "1000",
		"LQ13"	=> "1000",
		"LQ14"	=> "25",
		"LQ15"	=> "100",
		"LQ16"	=> "125",
		"LQ17"	=> "500",
		"LQ18"	=> "1000",
		"LQ19"	=> "5000",
		"LQ20"	=> "0",
		"LQ21"	=> "0",
		"LQ22"	=> "1000",
		"LQ23"	=> "3000",
		"LQ24"	=> "6000",
		"LQ25"	=> "1000",
		"LQ26"	=> "500",
		"LQ27"	=> "6000",
		"LQ28"	=> "3000");

		$vakrakirtiedot = array();
		$vaktuotetiedot = array();
		$rakirkilot = 0;

		if (mysql_num_rows($riresult1) > 1) {

			$vakkoodicount = 1;

			while ($row = mysql_fetch_assoc($riresult1)) {
				if (isset($edvaki) and $row["vakkoodi"] != $edvaki) {
					$vakkoodicount++;
				}

				$edvaki = $row["vakkoodi"];
			}

			if ($vakkoodicount > 1) {
				$tuotepainot = 0;
				$tuotepainot_yht = 0;
			}

			mysql_data_seek($riresult1, 0);
		}

		if (mysql_num_rows($riresult1) > 1 and mysql_num_rows($riresult2) > 0) {
			while ($row = mysql_fetch_assoc($riresult2)) {

				$query = "	SELECT *
							FROM pakkaus
							WHERE yhtio = '$kukarow[yhtio]'
							and pakkaus = '$row[pakkaus]'
						 	and pakkauskuvaus = '$row[pakkauskuvaus]'";
				$sresult = pupe_query($query);
				$srow = mysql_fetch_assoc($sresult);

				$pakkaus_en = t_tunnus_avainsanat($srow, "pakkaus", "PAKKAUSKV", $kieli);

				if ($row["kollit"] != 0 or $row["kilot"] != 0) {
					if ($tyyppi == "DGD") {

						$vakkirakirri = "$row[kollit]###$row[kollit] x $pakkaus_en containing:###";

						if (!isset($tuotepainot)) {
							$vakkirakirri .= round($row["kilot"]);
						}

						$vakrakirtiedot[] = $vakkirakirri;
					}
					else {
						$vakrakirtiedot[] = "$row[kollit] x $pakkaus_en (".t("Bruttopaino", $kieli)." ".round($row["kilot"])." kg) ".t("sisältäen", $kieli).":";
					}

					$rakirkilot += round($row["kilot"]);
				}
			}
		}

		if (mysql_num_rows($riresult1) > 0) {

			$edrow   	 = "";
			$max_ltq_til = "";

			while ($row = mysql_fetch_assoc($riresult1)) {

				if (isset($edrow["vakkoodi"]) and $row["vakkoodi"] != $edrow["vakkoodi"]) {

					if ($tyyppi == "DGD") $nimitykset = explode("###", wordwrap($edrow["nimitys"], 50, "###"));
					else $nimitykset = explode("###", wordwrap($edrow["nimitys"], 90, "###"));

					$vaktuotetiedot[] = "$edrow[yk_nro], $nimitykset[0]";

					for ($i = 1; $i<count($nimitykset); $i++) {
						$vaktuotetiedot[] = "$nimitykset[$i]";
					}

					$vakecho = "";
					if (trim($edrow["lipukkeet"]) != "") $vakecho .= t("Luokka", $kieli)." $edrow[lipukkeet]";
					if (trim($edrow["pakkausryhma"]) != "") $vakecho .= ", ".t("Pakkausryhmä", $kieli)." $edrow[pakkausryhma]";

					// Limited Quantity jos kaikki pakkaukset ovat riittävän pieniä
					if ($edrow["limited_qty"] != "" and isset($limited_qty[$edrow["limited_qty"]]) and $max_ltq_til > 0 and $max_ltq_til <= $limited_qty[$edrow["limited_qty"]]) {
						$vakecho .= ", LTD QTY";
					}

					if ($tyyppi == "DGD" and isset($tuotepainot)) {
						$vakecho .= "###".round($tuotepainot);
					}

					$max_ltq_til = 0;

					if ($vakecho != "") {
						$vaktuotetiedot[] = "$vakecho";
					}

					$vaktuotetiedot[] = "";
					$tuotepainot = 0;
				}

				$eramaara = ceil($row["kpl"]/$row["era"]);

				$vaktuotetiedot[] = "$eramaara ".t("pakkausta", $kieli).", ".t("kukin", $kieli)." ".(float) $row['era']." x $row[tilavuus] (".t($row["materiaali"], $kieli).")";

				// Mikä on toistaiseksi suurin vakmäärä
				$ltq_yks = preg_replace("/[^a-z]/", "", strtolower($row["tilavuus"]));

				$ltq_til = (float) preg_replace("/[^0-9,\.]/", "", str_replace(",", ".", $row["tilavuus"]));

				// Käytetään vain millilitroja ja grammoja
				if ($ltq_yks == 'l' or $ltq_yks == 'kg') {
					$ltq_til = $ltq_til*1000;
				}

				if ($ltq_til > $max_ltq_til) $max_ltq_til = $ltq_til;

				if (isset($tuotepainot)) {
					$tuotepainot += round($row["tuotepaino"]);
					$tuotepainot_yht += round($row["tuotepaino"]);
				}

				$edrow = $row;
			}

			if ($tyyppi == "DGD") $nimitykset = explode("###", wordwrap($edrow["nimitys"], 50, "###"));
			else $nimitykset = explode("###", wordwrap($edrow["nimitys"], 90, "###"));

			$vaktuotetiedot[] = "$edrow[yk_nro], $nimitykset[0]";

			for ($i = 1; $i<count($nimitykset); $i++) {
				$vaktuotetiedot[] = "$nimitykset[$i]";
			}

			$vakecho = "";
			if (trim($edrow["lipukkeet"]) != "") $vakecho .= t("Luokka", $kieli)." $edrow[lipukkeet]";
			if (trim($edrow["pakkausryhma"]) != "") $vakecho .= ", ".t("Pakkaus", $kieli)." $edrow[pakkausryhma]";

			// Limited Quantity jos kaikki pakkaukset ovat riittävän pieniä
			if ($edrow["limited_qty"] != "" and isset($limited_qty[$edrow["limited_qty"]]) and $max_ltq_til > 0 and $max_ltq_til <= $limited_qty[$edrow["limited_qty"]]) {
				$vakecho .= ", LTD QTY";
			}

			if ($tyyppi == "DGD" and isset($tuotepainot)) {
				$vakecho .= "###$tuotepainot";
			}

			if ($vakecho != "") {
				$vaktuotetiedot[] = "$vakecho";
			}

			if (isset($tuotepainot)) {
				$vaktuotetiedot[] = "";
				$vaktuotetiedot[] = "TOTAL GROSS MASS: $rakirkilot kg";
			}
		}


		return array($vakrakirtiedot, $vaktuotetiedot);
	}
}

if (!function_exists('tulosta_sscc')) {
	function tulosta_sscc($komento) {
		global $kukarow, $yhtiorow;

		$query = "SELECT selite FROM avainsana WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);

		$sscc = is_numeric($row['selite']) ? (int) $row['selite'] + 1 : 1;

		$nimi = str_replace(array('Ä','ä','Ö','ö'), array('A','a','O','o'), $yhtiorow['nimi']);

		$viivakoodi  = ">6";
		$viivakoodi .= substr($sscc,0,1);
		$viivakoodi .= ">5";
		$viivakoodi .= substr($sscc,1);

		$sivu = "*** ETIKETIN KOKO ***
                 {D1530,1040,1470|}
                 {AX;+000,+000,+00|}
                 {AY;+05,0|}
                 {C|}

				 *** YHTIÖN NIMI ***
				 {PC001;0100,0100,1,1,J,00,B|}
	             {RC001;$nimi|}

				 {LC;0050,0130,1000,0130,0,2|}

				 {PC002;0100,0180,05,05,J,00,B|}
	             {RC002;SSCC|}

				 {PC003;0100,0260,1,1,J,00,B|}
	             {RC003;$sscc|}

				 {LC;0050,0310,1000,0310,0,2|}

                 {XB00;0100,0350,A,3,04,0,0350,+0000000000,000,0,00|}
                 {RB00;$viivakoodi|}

				 *** TULOSTUS  ***
             	 {XS;I,0001,0002C3210|}
             	 ".chr(12);

		$sivu = escapeshellarg($sivu);

		if ($komento == "email") {
			echo t("SSCC-tarraa ei voida tulostaa sähköpostiin"),"<br>";
		}
		else {
			$line = exec(" echo \"$sivu\" | $komento");
		}

		if (trim($row['selite']) == '') {

			// haetaan aluksi max perhe
			$query = "	SELECT max(perhe)+1 perhe
						FROM avainsana
						WHERE yhtio = '{$kukarow['yhtio']}'";
			$max_perhe_res = pupe_query($query);
			$max_perhe_row = mysql_fetch_assoc($max_perhe_res);

			$query = "	INSERT INTO avainsana SET
						yhtio = '{$kukarow['yhtio']}',
						perhe = '{$max_perhe_row['perhe']}',
						kieli = '{$kukarow['kieli']}',
						laji = 'SSCC',
						nakyvyys = '',
						selite = '{$sscc}',
						selitetark = '',
						selitetark_2 = '',
						selitetark_3 = '',
						jarjestys = 0,
						laatija = '{$kukarow['kuka']}',
						luontiaika = now(),
						muutospvm = now(),
						muuttaja = '{$kukarow['kuka']}'";
			$insert_res = pupe_query($query);
		}
		else {
			$query = "UPDATE avainsana SET selite = '{$sscc}' WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
			$update_res = pupe_query($query);
		}

		return $sscc;
	}
}

if (!function_exists("fetchFreeSlot")) {
	function fetchFreeSlot($params) {

		// Luodaan muuttujat
		foreach ($params as $key => $val) {
			${$key} = $val;
		}

		$kokeillaan_x = false;
		$kokeillaan_y = true;
		$kokeillaan_z = false;

		$tmp_haystack = $haystack;

		// otetaan pakkauksen maksimimitat talteen
		$pakkauksen_max_x = $pakkauksen_max_y = $pakkauksen_max_z = 0;

		foreach ($tmp_haystack as $key => $val) {
			if (end($tmp_haystack)) $pakkauksen_max_x = $key;
		}

		foreach ($tmp_haystack as $zarr) {
			foreach ($zarr as $key => $val) {
				if (end($zarr)) {
					$pakkauksen_max_y = key($zarr);
					break 2;
				}
			}
		}

		foreach ($tmp_haystack as $farr) {
			foreach ($farr as $garr) {
				foreach ($garr as $key => $val) {
					if (end($garr)) {
						$pakkauksen_max_z = key($garr);
						break 3;
					}
				}
			}
		}

		## LOGIIIKKA:
		###############################
		## 1. katsotaan löytyykö sopiva paikka y-akselilta [0][y][0]
		## 2. katsotaan löytyykö sopiva paikka z-akselilta [0][0][z]
		## 3. katsotaan löytyykö sopiva paikka y- ja z-akseleilta [0][y][z]
		## 3.1 ---> Katsotaan ensiksi löytyykö z-akselilta paikkaa
		## 3.2 ---> Jos löytyy, käytetään sitä z-akselilla ja etsitään sille sopiva paikka y-akselilta
		## 4. Katsotaan löytyisikö muualta x-akselilta paikkaa [x][0][0] tai [x][0][z]
		## 5. Etsitään paikkaa muualta kuin 0-pisteistä ja kaikilta akseleilta [x][y][z]

		list($xx, $yy, $zz) = array_values($index);

		// katsotaan löytyykö sopiva paikka y-akselilta [0][y][0]
		if ($kokeillaan_y) {
			for ($y = 0; $y <= $pakkauksen_max_y; $y=$y+$resoluutio) {
				if (isset($haystack[0][$y][0]) and $haystack[0][$y][0] === $needle and isset($haystack[$xx][$y+$yy][$zz]) and $haystack[$xx][$y+$yy][$zz] === $needle) {

					$return_params = array(
						'vapaa_x' 		=> 0,
						'vapaa_y' 		=> $y,
						'vapaa_z' 		=> 0,
						'vapaa_chk_x'	=> $xx,
						'vapaa_chk_y'	=> $y+$yy,
						'vapaa_chk_z'	=> $zz
					);

					// echo "Löytyi sopiva paikka y-akselilta. (needle: $needle)<br>";
					// echo "<pre>",var_dump($return_params),"</pre>";

					return $return_params;
				}
			}

			$kokeillaan_z = true;
		}

		// katsotaan löytyykö sopiva paikka z-akselilta [0][0][z]
		if ($kokeillaan_z) {
			for ($z = 0; $z <= $pakkauksen_max_z; $z=$z+$resoluutio) {
				if (isset($haystack[0][0][$z]) and $haystack[0][0][$z] === $needle and isset($haystack[$xx][$yy][$z+$zz-$resoluutio]) and $haystack[$xx][$yy][$z+$zz-$resoluutio] === $needle) {

					$return_params = array(
						'vapaa_x' 		=> 0,
						'vapaa_y' 		=> 0,
						'vapaa_z' 		=> $z,
						'vapaa_chk_x'	=> $xx,
						'vapaa_chk_y'	=> $yy,
						'vapaa_chk_z'	=> $z+$zz-$resoluutio
					);

					// echo "Löytyi sopiva paikka z-akselilta (needle: $needle).<br>";
					// echo "<pre>",var_dump($return_params),"</pre>";

					return $return_params;
				}
			}
		}

		// katsotaan löytyykö sopiva paikka y- ja z-akseleilta [0][y][z]
		if ($kokeillaan_z and $kokeillaan_y) {

			$break = false;

			// Katsotaan ensiksi löytyykö z-akselilta paikkaa
			for ($z = $zz + $resoluutio; $z <= $pakkauksen_max_z; $z = $z + $resoluutio) {

				if (isset($haystack[0][$yy+$resoluutio][$z]) and $haystack[0][$yy+$resoluutio][$z] === $needle and isset($haystack[0][$yy+$resoluutio][$z+$zz-$resoluutio]) and $haystack[0][$yy+$resoluutio][$z+$zz-$resoluutio] === $needle) {
					// echo "Löytyi Z-akselilta paikka! [0][".($yy+$resoluutio)."][$z] - [0][".($yy+$resoluutio)."][".($z+$zz-$resoluutio)."]<br>";
					$break = true;
					break;
				}
			}

			// echo "y = $yy + $resoluutio; y <= $pakkauksen_max_y; y = y + $resoluutio<br>";

			for ($y = $yy + $resoluutio, $kpl = 1; $y <= $pakkauksen_max_y; $y = $y + $resoluutio, $kpl++) {

				if (!$break) {

					// echo "[0][".($y+$yy+$resoluutio)."][".($z-$zz)."] - [0][".(($yy*$kpl)+$y+$resoluutio)."][".($z-$resoluutio)."]   OAAAA: ".((($yy*$kpl)+$y)-($y+$yy))." == $yy<br>";

					if (isset($haystack[0][$y+$yy+$resoluutio][$z-$zz]) and $haystack[0][$y+$yy+$resoluutio][$z-$zz] === $needle and isset($haystack[0][($yy*$kpl)+$y+$resoluutio][$z-$resoluutio]) and $haystack[0][($yy*$kpl)+$y+$resoluutio][$z-$resoluutio] === $needle and (($yy*$kpl)+$y)-($y+$yy) == $yy) {

						$return_params = array(
							'vapaa_x' 		=> 0,
							'vapaa_y' 		=> $yy+$y+$resoluutio,
							'vapaa_z' 		=> $z-$zz,
							'vapaa_chk_x'	=> $xx,
							'vapaa_chk_y'	=> ($yy*$kpl)+$y+$resoluutio,
							'vapaa_chk_z'	=> $z-$resoluutio
						);

						// echo "Löytyi sopiva paikka y-akselilta ja z-akselilta (needle: $needle)<br>";
						// echo "<pre>",var_dump($return_params),"</pre>";

						return $return_params;
					}
				}
				else {
					// Jos löytyy, käytetään sitä z-akselilla ja etsitään sille sopiva paikka y-akselilta
					if (isset($haystack[0][$yy+$resoluutio][$z]) and $haystack[0][$yy+$resoluutio][$z] === $needle and isset($haystack[0][$y+$yy][$z+$zz-$resoluutio]) and $haystack[0][$y+$yy][$z+$zz-$resoluutio] === $needle) {

						$return_params = array(
							'vapaa_x' 		=> 0,
							'vapaa_y' 		=> $yy+$resoluutio,
							'vapaa_z' 		=> $z,
							'vapaa_chk_x'	=> $xx,
							'vapaa_chk_y'	=> $yy+$y,
							'vapaa_chk_z'	=> $z+$zz-$resoluutio
						);

						// echo "Löytyi sopiva paikka y-akselilta ja z-akselilta. Löytyi sopiva paikka ensiksi z-akselilta ja käytettiin sitä tässä. (needle: $needle)<br>";
						// echo "<pre>",var_dump($return_params),"</pre>";

						return $return_params;
					}
				}
			}

			$kokeillaan_x = true;
		}

		// Katsotaan löytyisikö muualta x-akselilta paikkaa [x][0][0] tai [x][0][z]
		if ($kokeillaan_x) {
			for ($x = $xx + $resoluutio; $x <= $pakkauksen_max_x; $x = $x + $resoluutio) {

				for ($z = $zz + $resoluutio; $z < $pakkauksen_max_z; $z = $z + $resoluutio) {
					if (isset($haystack[$x][0][0]) and $haystack[$x][0][0] === $needle and isset($haystack[$x+$xx-$resoluutio][$yy][$zz]) and $haystack[$x+$xx-$resoluutio][$yy][$zz] === $needle) {

						$return_params = array(
							'vapaa_x' 		=> $x,
							'vapaa_y' 		=> 0,
							'vapaa_z' 		=> 0,
							'vapaa_chk_x'	=> $x+$xx-$resoluutio,
							'vapaa_chk_y'	=> $yy,
							'vapaa_chk_z'	=> $zz
						);

						// echo "Löytyi sopiva paikka x-akselilta. (needle: $needle)<br>";
						// echo "<pre>",var_dump($return_params),"</pre>";

						return $return_params;
					}

					// echo "[$x][0][$z] - [".($x+$xx-$resoluutio)."][".($yy)."][".($z+$zz-$resoluutio)."]<br>";

					// !!!!! and lisätty
					if (isset($haystack[$x][0][$z]) and $haystack[$x][0][$z] === $needle and isset($haystack[$x+$xx-$resoluutio][$yy][$z+$zz-$resoluutio]) and $haystack[$x+$xx-$resoluutio][$yy][$z+$zz-$resoluutio] === $needle
						and isset($haystack[$x][0][$z+$zz-$resoluutio]) and $haystack[$x][0][$z+$zz-$resoluutio] === $needle) {

						$return_params = array(
							'vapaa_x' 		=> $x,
							'vapaa_y' 		=> 0,
							'vapaa_z' 		=> $z,
							'vapaa_chk_x'	=> $x+$xx-$resoluutio,
							'vapaa_chk_y'	=> $yy,
							'vapaa_chk_z'	=> $z+$zz-$resoluutio
						);

						// echo "Löytyi sopiva paikka x-akselilta ja z-akselilta (needle: $needle)<br>";
						// echo "<pre>",var_dump($return_params),"</pre>";

						return $return_params;
					}
				}
			}
		}

		// Etsitään paikkaa muualta kuin 0-pisteistä ja kaikilta akseleilta [x][y][z]
		if ($kokeillaan_x and $kokeillaan_y and $kokeillaan_z) {
			$break = false;

			for ($x = $xx + $resoluutio, $y = $yy + $resoluutio; $x < $pakkauksen_max_x, $y < $pakkauksen_max_y; $x = $x + $resoluutio, $y = $y + $resoluutio) {
				if (isset($haystack[$x][$y][0]) and $haystack[$x][$y][0] === $needle and isset($haystack[$x+$xx-$resoluutio][$y+$yy][$zz]) and $haystack[$x+$xx-$resoluutio][$y+$yy][$zz] === $needle) {
					$break = true;
					break;
				}
			}

			if ($break) {

				$return_params = array(
					'vapaa_x' 		=> $x,
					'vapaa_y' 		=> $y,
					'vapaa_z' 		=> 0,
					'vapaa_chk_x'	=> $x+$xx-$resoluutio,
					'vapaa_chk_y'	=> $y+$yy,
					'vapaa_chk_z'	=> $zz
				);

				// echo "Löytyi sopiva paikka x-akselilta, y-akselilta ja z-akselilta (needle: $needle)<br>";
				// echo "<pre>",var_dump($return_params),"</pre>";

				return $return_params;
			}
			else {

				for ($x = $xx + $resoluutio, $kpl = 1; $x <= $pakkauksen_max_x; $x = $x + $resoluutio, $kpl++) {
					for ($y = $yy + $resoluutio; $y <= $pakkauksen_max_y; $y = $y + $resoluutio) {
						for ($z = 0; $z <= $pakkauksen_max_z; $z = $z + $resoluutio) {

							// echo "[$x][$y][$z] - [".($x+$xx-$resoluutio)."][".($y+$yy)."][".($z+$zz)."]<br>";

							// !!!!! and lisätty
							if (isset($haystack[$x][$y][$z]) and $haystack[$x][$y][$z] !== false and $haystack[$x][$y][$z] === $needle and isset($haystack[$x+$xx-$resoluutio][$y+$yy][$z+$zz]) and $haystack[$x+$xx-$resoluutio][$y+$yy][$z+$zz] === $needle
							and isset($haystack[$x][$y+$yy][$z+$zz]) and $haystack[$x][$y+$yy][$z+$zz] === $needle) {

								$return_params = array(
									'vapaa_x' 		=> $x,
									'vapaa_y' 		=> $y,
									'vapaa_z' 		=> $z,
									'vapaa_chk_x'	=> $x+$xx-$resoluutio,
									'vapaa_chk_y'	=> $y+$yy,
									'vapaa_chk_z'	=> $z+$zz
								);

								// echo "Löytyi sopiva paikka y-akselilta ja z-akselilta (needle: $needle)<br>";
								// echo "<pre>",var_dump($return_params),"</pre>";

								return $return_params;
							}
						}
					}
				}
			}
		}

		$return_params = array(
			'vapaa_x' 		=> false,
			'vapaa_y' 		=> false,
			'vapaa_z' 		=> false,
			'vapaa_chk_x'	=> false,
			'vapaa_chk_y'	=> false,
			'vapaa_chk_z'	=> false
		);

	    return $return_params;
	}
}

if (!function_exists("ceiling")) {
	function ceiling($number, $significance = 1) {
		return ( is_numeric($number) && is_numeric($significance) ) ? (ceil($number / $significance) * $significance) : false;
	}
}

if (!function_exists("checkPackage")) {
	function checkPackage($pakkaukset, $max_keraysera_pintaala, $eran_koko, $debug) {
		foreach ($pakkaukset as $pakkauksen_tunnus => $aarr) {
			if ($max_keraysera_pintaala >= ($eran_koko + ($aarr['leveys'] * $aarr['syvyys']))) {
				if ($debug) {
					echo "Mahtuu vielä pakkaus [$pakkauksen_tunnus]<br>";
				}
			}
			else {
				if ($debug) {
					echo "Pakkaus [$pakkauksen_tunnus] ei mahdu enää erään! ($eran_koko + ".($aarr['leveys'] * $aarr['syvyys'])." / $max_keraysera_pintaala)<br>";
					echo "Poistetaan pakkaus käytettävistä pakkauksista.<br>";
				}

				unset($pakkaukset[$pakkauksen_tunnus]);
			}
		}

		return $pakkaukset;
	}
}

if (!function_exists("fitting")) {
	function fitting($params) {

		foreach ($params as $k => $v) {
			${$k} = $v;
		}

		$all_in_one = true;

		foreach ($taulukot as $key => $taulukko) {
			$vapaa_chk_x = (float) ceiling($tuoterow['tuoteleveys'] * $tuoterow['y_maara'], $resoluutio);
			$vapaa_chk_y = (float) ceiling($tuoterow['tuotekorkeus'] * $tuoterow['y_maara'], $resoluutio);
			$vapaa_chk_z = (float) ceiling($tuoterow['tuotesyvyys'] * $tuoterow['y_maara'], $resoluutio);

			$tuote_leveys 	= (float) ceiling($tuoterow['tuoteleveys'] * 1, $resoluutio);
			$tuote_korkeus 	= (float) ceiling($tuoterow['tuotekorkeus'] * 1, $resoluutio);
			$tuote_syvyys 	= (float) ceiling($tuoterow['tuotesyvyys'] * 1, $resoluutio);

			if (!isset($taulukko["$vapaa_chk_x"]["$vapaa_chk_y"]["$vapaa_chk_z"]) and !isset($taulukko["$tuote_leveys"]["$tuote_korkeus"]["$vapaa_chk_z"])) {
				$all_in_one = false;

				if ($debug) {
					echo "kaikki tuotteet eivät mahtuneet yhteen pakkaukseen [$key] $vapaa_chk_x ($tuote_leveys) x $vapaa_chk_y ($tuote_korkeus) x $vapaa_chk_z<br>";
				}
			}
			else {

				$ed_juokseva = $juokseva_pakkausnro;
				$juokseva_pakkausnro++;

				if ($debug) {
					echo "kaikki tuotteet mahtuvat yhteen pakkaukseen [$key] ($juokseva_pakkausnro) $vapaa_chk_x x $vapaa_chk_y x $vapaa_chk_z<br>";
					echo "käytetään sitä!<br>";
				}

				// if (($eran_koko + ($pakkaukset[$key]['leveys'] * $pakkaukset[$key]['syvyys'])) > $max_keraysera_pintaala) {
				// 	if ($debug) {
				// 		echo "Tosin pakkaus ylittää keräyserän maksimipinta-alan. Ei käytetä sitä. Keräyserä on nyt valmis!<br>";
				// 	}
				//
				// 	$era_valmis = true;
				//
				// 	return array(3, $all_in_one, $era_valmis, $asiakkaan_pakkaukset, $kaytettavat_pakkaukset, $eran_koko, $erat, $juokseva_pakkausnro, $ed_juokseva, $sopiva_pakkaus, $pakkaukset);
				// }

				$sopiva_pakkaus = array();

				$sopiva_pakkaus[$key] = $taulukko;

				for ($x = 0; $x < $vapaa_chk_x; $x += $resoluutio) {
					for ($y = 0; $y < $vapaa_chk_y; $y += $resoluutio) {
						for ($z = 0; $z < $vapaa_chk_z; $z += $resoluutio) {
							$sopiva_pakkaus[$key]["$x"]["$y"]["$z"] = false;
						}
					}
				}

				$asiakkaan_pakkaukset[$key][$juokseva_pakkausnro] = $sopiva_pakkaus[$key];
				$eran_koko += $pakkaukset[$key]['leveys'] * $pakkaukset[$key]['syvyys'];

				if ($debug) {
					echo "Pakkaus käytetty ({$pakkaukset[$key]['leveys']} x {$pakkaukset[$key]['syvyys']}): ".($pakkaukset[$key]['leveys'] * $pakkaukset[$key]['syvyys'])." (resoluutio: $resoluutio) / $max_keraysera_pintaala<br>";
					echo "Erän koko nyt: $eran_koko<br>";
				}

				// $pakkaukset = checkPackage($pakkaukset, $max_keraysera_pintaala, $eran_koko, $debug);

				$erat['tilaukset'][$tuoterow['otunnus']] = $tuoterow['otunnus'];

				$all_in_one = true;
				$kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnro][$tuoterow['tunnus']] = $tuoterow['y_maara'];

				$params_return = array(
					'breikki' 					=> 0,
					'all_in_one' 				=> $all_in_one,
					'era_valmis' 				=> $era_valmis,
					'asiakkaan_pakkaukset' 		=> $asiakkaan_pakkaukset,
					'kaytettavat_pakkaukset' 	=> $kaytettavat_pakkaukset,
					'eran_koko'					=> $eran_koko,
					'erat'						=> $erat,
					'juokseva_pakkausnro'		=> $juokseva_pakkausnro,
					'ed_juokseva'				=> $ed_juokseva,
					'sopiva_pakkaus'			=> $sopiva_pakkaus,
					'pakkaukset'				=> $pakkaukset
				);

				return $params_return;
			}
		}

		$params_return = array(
			'breikki' 					=> 0,
			'all_in_one' 				=> $all_in_one,
			'era_valmis' 				=> $era_valmis,
			'asiakkaan_pakkaukset' 		=> $asiakkaan_pakkaukset,
			'kaytettavat_pakkaukset' 	=> $kaytettavat_pakkaukset,
			'eran_koko'					=> $eran_koko,
			'erat'						=> $erat,
			'juokseva_pakkausnro'		=> $juokseva_pakkausnro,
			'ed_juokseva'				=> $ed_juokseva,
			'sopiva_pakkaus'			=> $sopiva_pakkaus,
			'pakkaukset'				=> $pakkaukset
		);

		return $params_return;
	}
}

if (!function_exists('tulosta_reittietiketti')) {
	function tulosta_reittietiketti($params) {
		global $kukarow, $yhtiorow;

		extract($params);

		$y_nimi 	= str_replace(array('Ä','ä','Ö','ö'), array('A','a','O','o'), $yhtiorow['nimi']);
		$y_osoite 	= str_replace(array('Ä','ä','Ö','ö'), array('A','a','O','o'), $yhtiorow['osoite']);
		$y_postino 	= str_replace(array('Ä','ä','Ö','ö'), array('A','a','O','o'), $yhtiorow['postino']);
		$y_postitp 	= str_replace(array('Ä','ä','Ö','ö'), array('A','a','O','o'), $yhtiorow['postitp']);

		$viivakoodi1  = ">6";
		$viivakoodi1 .= substr($tilriv,0,2);
		$viivakoodi1 .= ">5";
		$viivakoodi1 .= substr($tilriv,2);

		$viivakoodi2  = ">6";
		$viivakoodi2 .= substr($sscc,0,1);
		$viivakoodi2 .= ">5";
		$viivakoodi2 .= substr($sscc,1);

		$sivu = "	*** ETIKETIN KOKO ***
					{D2280,1040,2250|}
					{AX;+000,+000,+00|}
					{AY;+10,0|}
					{C|}

				 	*** YHTIÖN NIMI ***
				 	{PC000;0850,0260,3,3,M,00,B|}
					{RC000;{$pakkaus_kirjain}|}

				 	{PC001;0100,0100,1,1,J,00,B|}
			 		{RC001;{$y_nimi}|}

				 	{PC002;0100,0150,05,05,J,00,B|}
			 		{RC002;{$y_osoite}|}

				 	{PC003;0100,0190,05,05,J,00,B|}
			 		{RC003;{$y_postino} {$y_postitp}|}

				 	{PC004;0600,0050,07,07,H,00,B|}
			 		{RC004;".date("d.m.Y")."|}

				 	{PC005;0100,0340,1,1,H,00,B|}
			 		{RC005;{$toimitustapa}|}

				 	{PC006;0100,0390,05,05,J,00,B|}
			 		{RC006;Keraaja: {$kukarow['nimi']}|}

				 	{PC007;0100,0430,05,05,J,00,B|}
			 		{RC007;Til.nro: {$tilriv}|}

				 	{PC008;0100,0470,05,05,J,00,B|}
			 		{RC008;Rivit: {$rivit}|}

				 	{PC009;0300,0470,05,05,J,00,B|}
			 		{RC009;Paino: {$paino} kg|}

				 	{PC010;0650,0470,05,05,J,00,B|}
			 		{RC010;Tilavuus: {$tilavuus} m3|}

				 	*** VAAKAVIIVAT ***
				 	{LC;0100,0570,1000,0570,0,2|}
				 	{LC;0100,0900,1000,0900,0,2|}

				 	*** PYSTYVIIVAT ***
				 	{LC;0100,0570,0100,0900,0,2|}
				 	{LC;1000,0570,1000,0900,0,2|}

				 	{PC011;0120,0660,1,1,H,00,B|}
			 		{RC011;{$lask_nimi}|}

				 	{PC012;0120,0720,1,1,H,00,B|}
			 		{RC012;{$lask_nimitark}|}

				 	{PC013;0120,0780,1,1,H,00,B|}
			 		{RC013;{$lask_osoite}|}

				 	{PC014;0120,0840,1,1,H,00,B|}
			 		{RC014;{$lask_postino} {$lask_postitp}|}

				 	{PC015;0100,0980,1,1,H,00,B|}
			 		{RC015;Viite: {$lask_viite}|}

				 	{PC016;0100,1040,1,1,H,00,B|}
			 		{RC016;Merkki: {$lask_merkki}|}

			     	{XB00;0100,1100,A,3,04,0,0150,+0000000000,000,1,00|}
			     	{RB00;{$viivakoodi1}|}

			     	{XB01;0100,1320,A,3,04,0,0150,+0000000000,000,1,00|}
			     	{RB01;{$viivakoodi2}|}

				 	*** TULOSTUS  ***
					{XS;I,0001,0002C3210|}
					".chr(12);

		if (trim($komento_reittietiketti) != '') {
			$line = exec(" echo \"$sivu\" | $komento_reittietiketti");
		}
	}
}

if (!function_exists("tarkista_email")) {
	function tarkista_email($email) {

		// meillä jos on useampi meiliosoite
		if (strpos($email, ",") !== FALSE) {
			$sahkopostit = explode(",", $email);
		}
		else {
			$sahkopostit = array($email);
		}

		foreach ($sahkopostit as $email) {
			// katotaan löytyykö email osoitteen domain...
			list($nimi, $domain) = explode("@", trim($email));

			if (!(checkdnsrr($domain,"MX") or checkdnsrr($domain, "A"))) {
				return $domain;
			}
		}

		return "";
	}
}

if (!function_exists("hae_tuotemuutokset")) {
	function hae_tuotemuutokset($tuoteno, $pvm = '') {

		global $kukarow;

		$lask = 0;
		$lista = array();
		$muutos = "";
		$validaattori = TRUE;

		while ($validaattori === TRUE) {

			$lisa = "";

			if ($muutos != "") {
				$lisa = " AND tuote_muutokset.muutospvm < '$muutos' ORDER BY tuote_muutokset.muutospvm DESC";
			}

			if ($pvm != "") {
				$lisa .= " AND tuote_muutokset.muutospvm > '$pvm'";
			}

			$query = "	SELECT tuote_muutokset.alkup_tuoteno, tuote_muutokset.muutospvm, ifnull(kuka.nimi, tuote_muutokset.kuka) kuka
						FROM tuote_muutokset
						LEFT JOIN kuka on (kuka.yhtio = tuote_muutokset.yhtio AND kuka.kuka = tuote_muutokset.kuka)
						WHERE tuote_muutokset.yhtio = '$kukarow[yhtio]'
						AND tuote_muutokset.tuoteno = '$tuoteno'
						$lisa
						LIMIT 1";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {
				$row = mysql_fetch_assoc($result);
				$muutos = $row['muutospvm'];
				$tuoteno = $row['alkup_tuoteno'];
				$lista[$lask]['tuoteno'] = $tuoteno;
				$lista[$lask]['muutospvm'] = $muutos;
				$lista[$lask]['kuka'] = $row["kuka"];
			}
			else {
				$validaattori = FALSE;
			}

			$lask++;

			// laitetaan maksimiarvo jonka jälkeen se hyppää pois ettei jää ikuiseen looppiin
			if ($lask == 60) break;
		}

		return $lista;
	}
}

if (!function_exists("kopioi_tilausrivi")) {
	function kopioi_tilausrivi($tunnus, $poikkeukset = array()) {
		global $kukarow;

		$insid = 0;

		$query = "	SELECT *
					from tilausrivi
					where yhtio = '{$kukarow["yhtio"]}'
					and tunnus = '$tunnus'";
		$rivires = pupe_query($query);

		if (mysql_num_rows($rivires) > 0) {

			$rivirow = mysql_fetch_assoc($rivires);

			$rfields = "";
			$rvalues = "";

			for ($i=0; $i < mysql_num_fields($rivires)-1; $i++) {

				$rfields .= mysql_field_name($rivires,$i).",";

				switch (mysql_field_name($rivires, $i)) {
					case 'muuttaja':
					case 'muutospvm':
						$rvalues .= "'',";
						break;
					case 'laatija':
						$rvalues .= "'$kukarow[kuka]',";
						break;
					case 'laadittu':
						$rvalues .= "now(),";
						break;
					default:
						$rvalues .= "'";

						if (isset($poikkeukset["tilausrivi.".mysql_field_name($rivires,$i)])) {
							$rvalues .= $poikkeukset["tilausrivi.".mysql_field_name($rivires,$i)];
						}
						else {
							$rvalues .= $rivirow[mysql_field_name($rivires,$i)];
						}

						$rvalues .= "',";
				}
			}

			$rfields = substr($rfields, 0, -1);
			$rvalues = substr($rvalues, 0, -1);

			$kysely = "INSERT INTO tilausrivi ($rfields) VALUES ($rvalues)";
			$insres = pupe_query($kysely);
			$insid  = mysql_insert_id();

			// Kopioidaan tilausrivin lisatiedot
			$query = "	SELECT *
						FROM tilausrivin_lisatiedot
						WHERE yhtio = '{$kukarow["yhtio"]}'
						and tilausrivitunnus = '$tunnus'";
			$lisrivires = pupe_query($query);

			if (mysql_num_rows($lisrivires) > 0) {
				$rivirow = mysql_fetch_assoc($lisrivires);

				$rfields = "";
				$rvalues = "";

				for ($i=0; $i < mysql_num_fields($lisrivires)-1; $i++) {

					$rfields .= mysql_field_name($lisrivires,$i).",";

					switch (mysql_field_name($lisrivires, $i)) {
						case 'muuttaja':
						case 'muutospvm':
							$rvalues .= "'',";
							break;
						case 'laatija':
							$rvalues .= "'$kukarow[kuka]',";
							break;
						case 'luontiaika':
							$rvalues .= "now(),";
							break;
						case 'tilausrivitunnus':
							$rvalues .= "'$insid',";
							break;
						default:
							$rvalues .= "'";

							if (isset($poikkeukset["tilausrivin_lisatiedot.".mysql_field_name($lisrivires,$i)])) {
								$rvalues .= $poikkeukset["tilausrivin_lisatiedot.".mysql_field_name($lisrivires,$i)];
							}
							else {
								$rvalues .= $rivirow[mysql_field_name($lisrivires,$i)];
							}

							$rvalues .= "',";
					}
				}

				$rfields = substr($rfields, 0, -1);
				$rvalues = substr($rvalues, 0, -1);

				$kysely = "INSERT INTO tilausrivin_lisatiedot ($rfields) VALUES ($rvalues)";
				$insres = pupe_query($kysely);
			}
		}

		return $insid;
	}
}


if (!function_exists("hae_hyvityshinta")) {
	function hae_hyvityshinta($liitostunnus, $tuoteno, $kpl) {
		global $kukarow;

		$kpl = abs($kpl);
		$laskuri = 0;
		$palautus = array();
		$tilausrivit = array();

		// Haetaan tuotteen tiedot
		$status =  "	SELECT *
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]'
						AND tuoteno = '$tuoteno'";
		$tuoteres = pupe_query($status);
		$tuoterow = mysql_fetch_assoc($tuoteres);

		$query_ale_lisa = generoi_alekentta('M');

		// Haetaan asiakkaan laskutetut/toimitetut veloitusrivit + toisella queryllä hyvitysrivit
		$query = "	(SELECT datediff(now(), if(tilausrivi.laskutettuaika='0000-00-00', now(), tilausrivi.laskutettuaika)) AS ika,
					tilausrivi.hinta * {$query_ale_lisa} hinta,
					sum(tilausrivi.kpl + tilausrivi.varattu) kpl
					FROM lasku
					JOIN tilausrivi USE index (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio
									AND tilausrivi.otunnus = lasku.tunnus
									AND tilausrivi.tyyppi != 'D'
									AND tilausrivi.tuoteno = '$tuoteno'
									AND tilausrivi.varattu+tilausrivi.kpl > 0)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.liitostunnus = '$liitostunnus'
					AND lasku.tila = 'L'
					AND lasku.alatila IN ('D','X')
					GROUP BY ika, hinta
					HAVING kpl != 0)
					UNION
					(SELECT datediff(now(), if(tilausrivi.laskutettuaika='0000-00-00', now(), tilausrivi.laskutettuaika)) AS ika,
					tilausrivi.hinta * {$query_ale_lisa} hinta,
					sum(tilausrivi.kpl  +tilausrivi.varattu) kpl
					FROM tilausrivi USE index (yhtio_tyyppi_tuoteno_varattu)
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
						AND lasku.tunnus = tilausrivi.otunnus
						AND lasku.liitostunnus = '$liitostunnus')
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					AND tilausrivi.tyyppi in ('0','L','M','V','W')
					AND tilausrivi.tuoteno = '$tuoteno'
					AND tilausrivi.varattu+tilausrivi.kpl < 0
					GROUP BY ika, hinta
					HAVING kpl != 0)

					ORDER BY ika ASC, kpl ASC";
		$viimhintares = pupe_query($query);

		// Asiakas ei ole ostanut tätä tuotette -> palautuskielto
		if (mysql_num_rows($viimhintares) == 0) {
			$palautus[] = array("tuoteno" => $tuoteno, "kpl" => $kpl, "hinta" => 0, "ale" => 0, "kommentti" => "Palautuskielto", "palautuskielto" => "X");
			return $palautus;
		}

		// echo "Syotetty: $kpl<br>";

		while ($viimerow = mysql_fetch_assoc($viimhintares)) {

			// Haluamme hyvittää $kpl kappaletta
			// Tilausrivillä (historiassa) on myyty $viimerow[kpl] kappaletta
			// Asiakkaan kumulatiiviset myydyt kappaleet on $laskuri kappaletta

			$laskuri += $viimerow['kpl'];

			// echo "Kpl $viimerow[kpl] Kumulatiivinen_kpl $laskuri Jäljellä_kpl $kpl<br>";

			if ($laskuri > 0 and $viimerow["kpl"] > 0) {

				// Rivillä on vähemmän kuin halutaan hyvittää, joten hyvitetään vain rivin verra
				if ($viimerow["kpl"] <= $kpl and $laskuri >= $kpl) {
					$hyvitys_kpl = $viimerow["kpl"];
				}
				elseif ($laskuri <= $kpl) {
					// Kumulatiivisesti on vähemmän kuin halutaan, joten hyvitetään vain kumulatiivinen arvo
					$hyvitys_kpl = $laskuri;
				}
				else {
					// Muussa tapauksessa hyvitetään niin paljon kun tarvitaan;
					$hyvitys_kpl = $kpl;
				}

				// Lisätään rivi
				$tilausrivit[] = array("ika" => $viimerow['ika'], "hinta" => $viimerow['hinta'], "kpl" => $hyvitys_kpl);

				// Vähennetään kumulatiivisesta saldosta sekä jäljellä olevasta määrätstä tämä rivi
				$laskuri -= $hyvitys_kpl;
				$kpl -= $hyvitys_kpl;
				// echo "** Hyvitetään: $hyvitys_kpl Jäljellä_kpl $kpl<br>";
			}

		}

		// Jos löytyi vähemmän myyntejä kun asiakas on palauttamassa -> palautuskielto
		if ($laskuri < $kpl) {
			$palautus[] = array("tuoteno" => $tuoteno, "kpl" => $kpl, "hinta" => 0, "ale" => 0, "kommentti" => "Palautuskielto", "palautuskielto" => "X");
			// echo "** Palautuskielto: $kpl<br>";
		}

		foreach ($tilausrivit as $tilausrivi_index => $tilausrivi_arvo) {

			unset($ale);
			unset($kommentti);

			$sql = "SELECT tuote_kentta, tuote_arvo, aika_ostosta, rokotusprosentti, palautuskielto, prioriteetti, rokotusselite
					FROM hyvityssaannot
					WHERE yhtio = '$kukarow[yhtio]'
					AND (aika_ostosta > '$tilausrivi_arvo[ika]' or aika_ostosta = 0)
					ORDER BY prioriteetti, tunnus";
			$result = pupe_query($sql);

			while ($rivi = mysql_fetch_array($result)) {

				if ($rivi["tuote_kentta"] == "") {
					$ale = $rivi["rokotusprosentti"];
					$kommentti = $rivi["rokotusselite"];
					break;
				}
				elseif ($rivi["tuote_kentta"] != "" and $tuoterow[$rivi["tuote_kentta"]] == $rivi["tuote_arvo"]) {
					$ale = $rivi["rokotusprosentti"];
					$kommentti = $rivi["rokotusselite"];
					break;
				}
			}

			// Jos ei löytynyt yhtään hyvityssaantöjä -> palautuskielto
			if (!isset($ale)) {
				$palautus[] = array("tuoteno" => $tuoteno, "kpl" => $tilausrivi_arvo['kpl'], "hinta" => 0, "ale" => 0, "kommentti" => "Palautuskielto", "palautuskielto" => "X");
			}
			else {
				$palautus[] = array("tuoteno" => $tuoteno, "kpl" => $tilausrivi_arvo['kpl'], "hinta" => $tilausrivi_arvo['hinta'], "ale" => $ale, "kommentti" => $kommentti, "palautuskielto" => "");
			}
		}

		return $palautus;
	}
}


if (!function_exists("tuotteen_lisatiedot")) {
	function tuotteen_lisatiedot($tuoteno) {
		global $kukarow;

		$i = 0;
		$parametrit = array();

		// Haetaan asiakkaan laskutetut/toimitetut veloitusrivit + toisella queryllä hyvitysrivit
		$query = "	SELECT tuotteen_avainsanat.laji, tuotteen_avainsanat.selite, avainsana.selitetark
					FROM tuotteen_avainsanat
					JOIN avainsana ON (avainsana.yhtio = tuotteen_avainsanat.yhtio
						AND avainsana.laji = 'LISATIETO'
						AND avainsana.selite = SUBSTR(tuotteen_avainsanat.laji, 11))
					WHERE tuotteen_avainsanat.yhtio = '$kukarow[yhtio]'
					AND tuotteen_avainsanat.kieli = '$kukarow[kieli]'
					AND tuotteen_avainsanat.tuoteno = '$tuoteno'
					AND tuotteen_avainsanat.laji LIKE 'lisatieto_%'
					AND tuotteen_avainsanat.selite != ''
					ORDER BY tuotteen_avainsanat.selite";
		$parares = pupe_query($query);

		// Jos tuotteelle ei löydy parametrejä
		if (mysql_num_rows($parares) == 0) {
			return $parametrit;
		}

		while ($pararow = mysql_fetch_assoc($parares)) {
			if ($pararow["selitetark"] == "") {
				$parametrit[$i]["kentta"] = substr($pararow["laji"], 10);
			}
			else {
				$parametrit[$i]["kentta"] = $pararow["selitetark"];
			}
			$parametrit[$i]["selite"] = $pararow["selite"];
			$i++;
		}

		return $parametrit;
	}
}

if (!function_exists("tee_keraysera")) {
	function tee_keraysera($keraysvyohyke) {
		global $kukarow, $yhtiorow;

		// toimitustavan lähtöjen viikonpäivä
		$viikonpaiva = date('w');

		$pakkaukset = array();
		$max_keraysera_pintaala = 0;
		$max_keraysera_rivit = 0;
		$lahtojen_valinen_enimmaisaika = 0;
		$yhdistelysaanto = '';
		$ulkoinen_jarjestelma = '';

		// esim. muutetaan metrit senteiksi
		$mittakerroin = 100;
		// millä tarkkuudella tuotteita pakataan (esim. 5 cm)
		$resoluutio = 5;

		$query = "	SELECT sallitut_alustat,
					if(max_keraysera_pintaala * pow({$mittakerroin}, 2) < 1, 1, max_keraysera_pintaala * pow({$mittakerroin}, 2)) max_keraysera_pintaala,
					max_keraysera_rivit,
					yhdistelysaanto,
					lahtojen_valinen_enimmaisaika,
					ulkoinen_jarjestelma
					FROM keraysvyohyke
					JOIN pakkaus ON (pakkaus.yhtio = keraysvyohyke.yhtio AND pakkaus.tunnus IN (keraysvyohyke.sallitut_alustat))
					WHERE keraysvyohyke.yhtio = '{$kukarow['yhtio']}'
					AND keraysvyohyke.tunnus IN ({$keraysvyohyke})";
		$ker_result = pupe_query($query);

		while ($ker_row = mysql_fetch_assoc($ker_result)) {
			$max_keraysera_pintaala = $ker_row['max_keraysera_pintaala'];
			$max_keraysera_rivit = $ker_row['max_keraysera_rivit'];
			$yhdistelysaanto = $ker_row['yhdistelysaanto'];
			$lahtojen_valinen_enimmaisaika = $ker_row['lahtojen_valinen_enimmaisaika'];
			$ulkoinen_jarjestelma = $ker_row['ulkoinen_jarjestelma'];

			$query = "	SELECT pakkaus.tunnus,
						pakkaus.pakkaus,
						pakkaus.paino,
						if(pakkaus.leveys * {$mittakerroin} < 1, 1, pakkaus.leveys * {$mittakerroin}) leveys,
						if(pakkaus.korkeus * {$mittakerroin} < 1, 1, pakkaus.korkeus * {$mittakerroin}) korkeus,
						if(pakkaus.syvyys * {$mittakerroin} < 1, 1, pakkaus.syvyys * {$mittakerroin}) syvyys
						FROM pakkaus
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus IN ({$ker_row['sallitut_alustat']})
						ORDER BY leveys ASC, korkeus ASC, syvyys ASC";
			$pakkaus_result = pupe_query($query);

			while ($pakkaus_row = mysql_fetch_assoc($pakkaus_result)) {
				$pakkaukset[$pakkaus_row['tunnus']]['leveys'] = $pakkaus_row['leveys'];
				$pakkaukset[$pakkaus_row['tunnus']]['korkeus'] = $pakkaus_row['korkeus'];
				$pakkaukset[$pakkaus_row['tunnus']]['syvyys'] = $pakkaus_row['syvyys'];
				$pakkaukset[$pakkaus_row['tunnus']]['paino'] = $pakkaus_row['paino'];
			}
		}

		$query = "	SELECT lasku.tunnus,
					keraysvyohyke.nimitys as keraysvyohyke_nimitys,
					lasku.liitostunnus,
					lasku.nimi as asiakas_nimi,
					SUBSTRING(lasku.h1time, 12, 8) aika,
					asiakas.tunnus as asiakas_tunnus,
					toimitustavan_lahdot.tunnus as lahto,
					(TIME_TO_SEC(TIMEDIFF(lahdon_kellonaika, CURTIME())) / 60) as erotus,
					lasku.hyvaksynnanmuutos as prioriteetti,
					lasku.h1time,
					lasku.sisviesti2,
					keraysvyohyke.tunnus as keraysvyohyke_tunnus,
					MIN(vh.indeksi) as minimi_indeksi,
					SUM(ROUND((if(tuote.tuoteleveys * {$mittakerroin} < 1, 1, tuote.tuoteleveys * {$mittakerroin}) * if(tuote.tuotekorkeus * {$mittakerroin} < 1, 1, tuote.tuotekorkeus * {$mittakerroin}) * if(tuote.tuotesyvyys * {$mittakerroin} < 1, 1, tuote.tuotesyvyys * {$mittakerroin})) * (tilausrivi.varattu+tilausrivi.kpl), 4)) tilauksen_koko
					FROM lasku
					JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
					JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.keraysvyohyke IN ({$keraysvyohyke}))
					JOIN keraysvyohyke ON (keraysvyohyke.yhtio = lasku.yhtio AND keraysvyohyke.tunnus IN ({$keraysvyohyke}))
					JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa)
				 	JOIN toimitustavan_lahdot ON (toimitustavan_lahdot.yhtio = toimitustapa.yhtio
													AND toimitustavan_lahdot.liitostunnus = toimitustapa.tunnus
													AND toimitustavan_lahdot.lahdon_kellonaika >= CURTIME()
													AND toimitustavan_lahdot.lahdon_viikonpvm = {$viikonpaiva}
													AND toimitustavan_lahdot.aktiivi = ''
													AND toimitustavan_lahdot.kerailyn_aloitusaika <= CURTIME()
 												)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tila = 'N'
					AND lasku.alatila = 'A'
					GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12
					ORDER BY lahto, erotus, lasku.hyvaksynnanmuutos, tilauksen_koko DESC, minimi_indeksi, lasku.h1time ASC, lasku.sisviesti2, keraysvyohyke.nimitys";
		$res = pupe_query($query);

		$debug = false;

		if ($debug) {
			echo "<table>";
			echo "<tr>";
			echo "<th>Tilaus</th>";
			echo "<th>Vyöhyke</th>";
			echo "<th>Max<br>m2</th>";
			echo "<th>Max<br>rivit</th>";
			echo "<th>Aika</th>";
			echo "<th>Tilauksen<br>Koko</th>";
			echo "<th>Asiakas</th>";
			echo "<th>Tilausrivi</th>";
			echo "<th>Tuoteno</th>";
			echo "<th>Tilavuus</th>";
			echo "<th>Yks.<br>koko</th>";
			echo "<th>Yks.<br>maara</th>";
			echo "<th>Yks.<br>paino</th>";
			echo "<th>Yksin</th>";
			echo "</tr>";
		}

		$ulkoisen_jarjestelman_tiedosto = '';

		$kaytettavat_pakkaukset = array();
		$asiakkaan_pakkaukset = array();
		$juokseva_pakkausnro = 1;
		$ed_asiakas = '';

		$ed_juokseva = 0;
		$juokseva_pakkausnro = 0;

		$pakkauksen_paino = 0;

		$rivit = 0;

		$ed_lahto = '';
		$ed_prioriteetti = '';
		$ed_sisviesti2 = 'ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ';

		$eran_koko = 0;
		$era_valmis = false;

		$erat = array('tilaukset' => array(), 'pakkaukset' => array());

		while ($row = mysql_fetch_assoc($res)) {

			if ($row['erotus'] > $lahtojen_valinen_enimmaisaika) {
				if ($debug) {
					echo "Lähdön enimmäisaika ylittyi! Let's skip it. ($row[erotus] > $lahtojen_valinen_enimmaisaika)<br>";
					echo "Laskutunnus: $row[tunnus]<br>Asiaskas: $row[asiakas_nimi]<br>Erotus: $row[erotus]<br>Lähtö: $row[lahto]<br>Minimi_indeksi: $row[minimi_indeksi]<br>Tilauksen koko: $row[tilauksen_koko]<br>H1time: $row[h1time]<br>Prioriteetti: $row[prioriteetti]<br><br>";
				}
				continue;
			}

			if ($eran_koko > $max_keraysera_pintaala) {
				if ($debug) {
					echo "Keräyserän maksimiraja ylitetty! $eran_koko / $max_keraysera_pintaala<br>";
				}

				$keraysvyohyke = $row['keraysvyohyke_tunnus'];
				$era_valmis = true;
				break;
			}

			if (trim($ed_prioriteetti) != '' and $ed_prioriteetti != $row['prioriteetti'] and strpos($yhdistelysaanto, 'P') === false and count($kaytettavat_pakkaukset) > 0) {
				if ($debug) {
					echo "EI SAA OLLA USEITA PRIORITEETTEJÄ ALUSTOISSA! KERÄYSERÄ VALMIS<br>";
				}

				$era_valmis = true;
				break;
			}

			$ed_prioriteetti = $row['prioriteetti'];

			if (trim($ed_lahto) != '' and $ed_lahto != $row['lahto'] and strpos($yhdistelysaanto, 'K') === false and count($kaytettavat_pakkaukset) > 0) {
				if ($debug) {
					echo "EI SAA OLLA ALUSTOJA USEISTA LÄHDÖISTÄ! KERÄYSERÄ VALMIS<br>";
				}

				$era_valmis = true;
				break;
			}

			$ed_lahto = $row['lahto'];

			if (trim($ed_asiakas) != '' and $ed_asiakas != $row['liitostunnus']) {
				$asiakkaan_pakkaukset = array();

				if (strpos($yhdistelysaanto, 'S') === false and count($kaytettavat_pakkaukset) > 0) {
					if ($debug) {
						echo "EI SAA OLLA USEITA ASIAKKAITA! KERÄYSERÄ VALMIS<br>";
					}

					$era_valmis = true;
					break;
				}
			}

			$query = "	SELECT tilausrivi.tunnus, tilausrivi.otunnus,
						round(if(tuote.tuotekorkeus * {$mittakerroin} < 1, 1, tuote.tuotekorkeus * {$mittakerroin}) * if(tuote.tuoteleveys * {$mittakerroin} < 1, 1, tuote.tuoteleveys * {$mittakerroin}) * if(tuote.tuotesyvyys * {$mittakerroin} < 1, 1, tuote.tuotesyvyys * {$mittakerroin}) * (tilausrivi.varattu+tilausrivi.kpl), 4) as tuotteen_koko,
						tilausrivi.tuoteno,
						round(if(tuote.tuotekorkeus * {$mittakerroin} < 1, 1, tuote.tuotekorkeus * {$mittakerroin}) * if(tuote.tuoteleveys * {$mittakerroin} < 1, 1, tuote.tuoteleveys * {$mittakerroin}) * if(tuote.tuotesyvyys * {$mittakerroin} < 1, 1, tuote.tuotesyvyys * {$mittakerroin}), 4) y_koko,
						round(if(tuote.tuotekorkeus * {$mittakerroin} < 1, 1, tuote.tuotekorkeus * {$mittakerroin}), 2) tuotekorkeus,
						round(if(tuote.tuoteleveys * {$mittakerroin} < 1, 1, tuote.tuoteleveys * {$mittakerroin}), 2) tuoteleveys,
						round(if(tuote.tuotesyvyys * {$mittakerroin} < 1, 1, tuote.tuotesyvyys * {$mittakerroin}), 2) tuotesyvyys,
						(tilausrivi.varattu+tilausrivi.kpl) y_maara,
						tuote.yksin_kerailyalustalle,
						tuote.tuotemassa y_paino
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.keraysvyohyke IN ({$keraysvyohyke}))
						JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.otunnus = '{$row['tunnus']}'
						AND tilausrivi.tyyppi != 'D'
						ORDER BY tuote.yksin_kerailyalustalle ASC, y_koko DESC";
			$tuoteres = pupe_query($query);

			while ($tuoterow = mysql_fetch_assoc($tuoteres)) {

				if ($rivit >= $max_keraysera_rivit) {
					if ($debug) {
						echo "RIVEJÄ ON TARPEEKSI KERÄYSERÄÄ VARTEN (rivit: $rivit max_rivit: $max_keraysera_rivit)<br>";
					}

					$era_valmis = true;
					break 2;
				}

				// katsotaan onko tilaus kokonaan jo keräyserässä
				$query = "	SELECT tunnus
							FROM kerayserat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND otunnus = '{$row['tunnus']}'
							AND tilausrivi = '{$tuoterow['tunnus']}'
							AND kpl = '{$tuoterow['y_maara']}'";
				$kerayserat_chk_res = pupe_query($query);

				if (mysql_num_rows($kerayserat_chk_res) != 0) {

					if ($debug) {
						echo "Tilausrivi on jo keräyserässä!!!! Let's skip it.<br />";
					}

					continue;
				}

				// katsotaan onko tilaus osittain jo keräyserässä
				$query = "	SELECT sum(kpl) as montako
							FROM kerayserat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND otunnus = '{$row['tunnus']}'
							AND tilausrivi = '{$tuoterow['tunnus']}'
							AND kpl < '{$tuoterow['y_maara']}'";
				$kerayserat_chk_res = pupe_query($query);
				$kerayserat_chk_row = mysql_fetch_assoc($kerayserat_chk_res);

				if ($kerayserat_chk_row['montako'] > 0) {

					if ($tuoterow['y_maara'] == $kerayserat_chk_row['montako']) {

						if ($debug) {
							echo "Kaikki tilausrivin kappaleet ovat jo keräyserissä. Let's skipataan.<br>";
						}

						continue;
					}

					$tuoterow['y_maara'] -= $kerayserat_chk_row['montako'];
				}

				if ($debug) {
					echo "<tr>";
					echo "<td>$row[tunnus]</td>";
					echo "<td>$row[keraysvyohyke_nimitys]</td>";
					echo "<td>$max_keraysera_pintaala</td>";
					echo "<td>$max_keraysera_rivit</td>";
					echo "<td>$row[aika]</td>";
					echo "<td>$row[tilauksen_koko]</td>";
					echo "<td>$row[asiakas_nimi]</td>";

					echo "<td>$tuoterow[tunnus]</td>";
					echo "<td>$tuoterow[tuoteno]</td>";
					echo "<td>$tuoterow[tuotteen_koko]</td>";
					echo "<td>$tuoterow[y_koko] ($tuoterow[tuoteleveys] x $tuoterow[tuotekorkeus] x $tuoterow[tuotesyvyys])</td>";
					echo "<td>$tuoterow[y_maara]</td>";
					echo "<td>$tuoterow[y_paino]</td>";
					echo "<td>$tuoterow[yksin_kerailyalustalle]</td>";
					echo "</tr>";
				}

				if (trim($ed_asiakas) == '' or $ed_asiakas != $row['liitostunnus'] or ($ed_asiakas == $row['liitostunnus'] and trim($ed_sisviesti2) != 'ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ' and $ed_sisviesti2 != $row['sisviesti2'])) {
					$taulukot = array();

					foreach ($pakkaukset as $pakkauksen_tunnus => $xarr) {

						// skipataan pakkaus, jos yksittäinen tuote ei mahdu kyseiseen laatikkoon
						if ($tuoterow['tuoteleveys'] > $xarr['leveys'] or $tuoterow['tuotekorkeus'] > $xarr['korkeus'] or $tuoterow['tuotesyvyys'] > $xarr['syvyys']) {

							if ($debug) {
								echo "POISTETAAN PAKETTI [$pakkauksen_tunnus]<br>";
								echo "($tuoterow[tuoteleveys] > $xarr[leveys] or $tuoterow[tuotekorkeus] > $xarr[korkeus] or $tuoterow[tuotesyvyys] > $xarr[syvyys])<br><br>";
							}
						}
						else {
							$taulukko = array();

							// [x][y][z]
							for ($x = 0; $x <= $xarr['leveys']; $x += $resoluutio) {
								for ($y = 0; $y <= $xarr['korkeus']; $y += $resoluutio) {
									for ($z = 0; $z <= $xarr['syvyys']; $z += $resoluutio) {
										$taulukko["$x"]["$y"]["$z"] = true;
									}
								}
							}

							$taulukot[$pakkauksen_tunnus] = $taulukko;
						}
					}

					if (isset($taulukko) and count($taulukko) > 0) {
						$taulukot = array_reverse($taulukot, true);
					}
				}

				// ei ole yhtään pakkausta käytettävissä
				if (count($taulukot) == 0) {
					continue;
				}

				if ($debug) {
					echo "ASIAKAS: $row[asiakas_nimi] ($row[asiakas_tunnus])<br>";
					echo "TUOTE: $tuoterow[tuoteno] ($tuoterow[y_maara] kpl)<br>";
					echo "=================================<br>";

					if (trim($tuoterow['yksin_kerailyalustalle']) != '') {
						echo "TUOTE YKSIN KERÄYSALUSTALLE!!!<br>";
					}
				}

				$all_in_one = false;

				if ($debug) {
					echo "Juokseva pakkausnro: $juokseva_pakkausnro<br>";
					echo "<br>";
				}

				// jos kaikki kappaleet ei mahdu yhteen pakettiin, katsotaan mihin paketteihin ne mahtuu
				if (!$all_in_one) {

					if (trim($ed_asiakas) == '' or $ed_asiakas != $row['liitostunnus'] or ($ed_asiakas == $row['liitostunnus'] and trim($ed_sisviesti2) != 'ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ' and $ed_sisviesti2 != $row['sisviesti2'])) {

						$sopiva_pakkaus = array();

						foreach ($taulukot as $key => $taulukko) {
							$sopiva_pakkaus[$key] = $taulukko;
							break;
						}

						if ($ed_asiakas != $row['liitostunnus'] and trim($tuoterow['yksin_kerailyalustalle']) == '') {
							$ed_juokseva = $juokseva_pakkausnro;
							$juokseva_pakkausnro++;
						}
					}

					$kaytetty_kpl = 0;

					$tuotteenleveys 	= (float) ceiling($tuoterow['tuoteleveys'], $resoluutio);
					$tuotteenkorkeus 	= (float) ceiling($tuoterow['tuotekorkeus'], $resoluutio);
					$tuotteensyvyys 	= (float) ceiling($tuoterow['tuotesyvyys'], $resoluutio);
					$tuotteenpaino		= $tuoterow['y_paino'];

					for ($i = 1; $i <= $tuoterow['y_maara']; $i++) {

						$breikkasi = '';

						if (count($asiakkaan_pakkaukset) > 0) {

							foreach ($asiakkaan_pakkaukset as $key => &$arr) {

								foreach ($arr as $juokseva_pakkausnumero => &$taulukko_chk) {

									if ($debug) {
										echo "Katsotaan mahtuuko aiempaan pakkaukseen [$key]<br>";
									}

									$params_fetch = array(
										'haystack' 		=> &$taulukko_chk,
										'needle'		=> true,
										'index'			=> array($tuotteenleveys, $tuotteenkorkeus, $tuotteensyvyys),
										'resoluutio'	=> $resoluutio,
										'i'				=> $i,
										'row'			=> $row,
										'tuoterow'		=> $tuoterow
									);

									$return_params = fetchFreeSlot($params_fetch);

									extract($return_params);

									if ($vapaa_x !== false and $vapaa_y !== false and $vapaa_z !== false and $vapaa_chk_x !== false and $vapaa_chk_y !== false and $vapaa_chk_z !== false) {

										if ($debug) {
											echo "MAHTUU AIEMPAAN PAKKAUKSEEN!!!! [$key] juokseva: $juokseva_pakkausnumero<br>";
											echo "vapaa_chk_x: $vapaa_chk_x ($vapaa_x) vapaa_chk_y: $vapaa_chk_y ($vapaa_y) vapaa_chk_z: $vapaa_chk_z ($vapaa_z)<br><br>";
										}

										if ($pakkaukset[$key]['paino'] > ($pakkauksen_paino + $tuotteenpaino)) {
											$pakkauksen_paino += $tuotteenpaino;

											if ($debug) {
												echo "Pakkauksen paino nyt: $pakkauksen_paino<br>";
											}
										}
										else {
											if ($debug) {
												echo "PAINO YLITTÄÄ PAKKAUKSEN MAKSIMIPAINON! ($pakkauksen_paino + $tuotteenpaino > {$pakkaukset[$key]['paino']})<br>";
											}

											continue;
										}

										for ($x = $vapaa_x; $x <= $vapaa_chk_x; $x += $resoluutio) {
											for ($y = $vapaa_y; $y <= $vapaa_chk_y; $y += $resoluutio) {
												for ($z = $vapaa_z; $z <= $vapaa_chk_z; $z += $resoluutio) {
													$taulukko_chk["$x"]["$y"]["$z"] = false;
												}
											}
										}

										if (!isset($kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnumero][$tuoterow['tunnus']])) $kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnumero][$tuoterow['tunnus']] = 0;
										$kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnumero][$tuoterow['tunnus']] += $i - $kaytetty_kpl;
										$kaytetty_kpl = $i;
										$erat['tilaukset'][$tuoterow['otunnus']] = $tuoterow['otunnus'];
										$rivit++;

										if ($i == $tuoterow['y_maara']) {
											if ($debug) {
												echo "Erän koko nyt: $eran_koko<br><br>";
											}
										}

										$breikkasi = 'joo';
										break 2;
									}
								}
							}

							if ($breikkasi == 'joo') {
								$breikkasi = '';
								continue;
							}
							else if ($ed_asiakas == $row['liitostunnus'] and trim($tuoterow['yksin_kerailyalustalle']) == '') {
								$ed_juokseva = $juokseva_pakkausnro;
								$juokseva_pakkausnro++;
							}
						}

						foreach ($sopiva_pakkaus as $key => &$taulukko_chk) {

							if (trim($tuoterow['yksin_kerailyalustalle']) != '') {

								if ($debug) {
									echo "TUOTE LISÄTÄÄN NYT OMALLE KERÄYSALUSTALLE!!!!<br>";
								}

								// katsotaan aluksi mahtuuko kaikki tuotteet yhteen pakettiin
								// pienemmät ensiksi
								$taulukot = array_reverse($taulukot, true);

								$params_fitting = array(
									'taulukot' 					=> $taulukot,
									'asiakkaan_pakkaukset' 		=> $asiakkaan_pakkaukset,
									'kaytettavat_pakkaukset' 	=> $kaytettavat_pakkaukset,
									'pakkaukset' 				=> $pakkaukset,
									'tuoterow'					=> $tuoterow,
									'resoluutio'				=> $resoluutio,
									'eran_koko' 				=> $eran_koko,
									'max_keraysera_pintaala' 	=> $max_keraysera_pintaala,
									'row'						=> $row,
									'juokseva_pakkausnro'		=> $juokseva_pakkausnro,
									'ed_juokseva'				=> $ed_juokseva,
									'erat' 						=> $erat,
									'debug'						=> $debug
								);

								$return_params = fitting($params_fitting);

								extract($return_params);

								if ($breikki == 3) {
									break 4;
								}

								// reverse takaisin. isot ensimmäiseksi.
								$taulukot = array_reverse($taulukot, true);

								$pakkauksen_paino = 0;
								$rivit++;

								if ($debug) {
									echo "Lisätään uusi laatikko! juokseva: $juokseva_pakkausnro<br>";
									echo "=================================<br><br>";
								}

								foreach ($taulukot as $key_xx => $taulukko) {
									$sopiva_pakkaus[$key_xx] = $taulukko;
									break;
								}

								continue;
							}

							$params_fetch = array(
								'haystack' 		=> &$taulukko_chk,
								'needle'		=> true,
								'index'			=> array($tuotteenleveys, $tuotteenkorkeus, $tuotteensyvyys),
								'resoluutio'	=> $resoluutio,
								'i'				=> $i,
								'row'			=> $row,
								'tuoterow'		=> $tuoterow
							);

							$return_params = fetchFreeSlot($params_fetch);

							extract($return_params);

							if ($debug) {
								echo "vapaa_x: $vapaa_x, vapaa_y: $vapaa_y, vapaa_z: $vapaa_z<br>";
								echo "vapaa_chk_x: $vapaa_chk_x, vapaa_chk_y: $vapaa_chk_y, vapaa_chk_z: $vapaa_chk_z<br>";
								echo "<br>KPL: $i<br>";
							}

							if ($vapaa_x === false or $vapaa_y === false or $vapaa_z === false) {

								foreach ($pakkaukset as $pakkauksen_tunnus => $qarr) {

									// skipataan pakkaus, jos yksittäinen tuote ei mahdu kyseiseen laatikkoon
									if ($tuoterow['tuoteleveys'] > $qarr['leveys'] or $tuoterow['tuotekorkeus'] > $qarr['korkeus'] or $tuoterow['tuotesyvyys'] > $qarr['syvyys']) {
										if ($debug) {
											echo "TUOTE EI VOI MAHTUA PAKETTIIN [$pakkauksen_tunnus]<br>";
											echo "$tuoterow[tuoteleveys] > $qarr[leveys] or $tuoterow[tuotekorkeus] > $qarr[korkeus] or $tuoterow[tuotesyvyys] > $qarr[syvyys]<br><br>";
										}

										break 2;
									}
								}

								$asiakkaan_pakkaukset[$key][$juokseva_pakkausnro] = $taulukko_chk;

								if ($debug) {
									if ($ed_asiakas == $row['liitostunnus']) {
										echo "SAMA ASIAKAS $row[asiakas_nimi]!!!<br>";
									}

									echo "Joku ei riittänyt! [$key]<br>";
								}

								$i--;

								$eran_koko += ($pakkaukset[$key]['leveys'] * $pakkaukset[$key]['syvyys']);

								if ($debug) {
									echo "Pakkaus käytetty ({$pakkaukset[$key]['leveys']} x {$pakkaukset[$key]['syvyys']}): ".($pakkaukset[$key]['leveys'] * $pakkaukset[$key]['syvyys'])." (resoluutio: $resoluutio) / $max_keraysera_pintaala<br>";
									echo "Erän koko nyt: $eran_koko<br>";
								}

								if (count($pakkaukset) == 0) {
									if ($debug) {
										echo "Käytettäviä pakkauksia ei ole enää jäljellä! Keräyserä valmis.<br><br>";
									}

									$era_valmis = true;
									break 4;
								}

								$erat['tilaukset'][$tuoterow['otunnus']] = $tuoterow['otunnus'];
								$kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnro][$tuoterow['tunnus']] += $i - $kaytetty_kpl;
								$pakkauksen_paino = 0;

								$ed_juokseva = $juokseva_pakkausnro;
								$juokseva_pakkausnro++;

								if ($debug) {
									echo "Lisätään uusi laatikko!<br>";
									echo "=================================<br><br>";
								}

								unset($sopiva_pakkaus[$key]);

								foreach ($taulukot as $key_xx => $taulukko) {
									$sopiva_pakkaus[$key_xx] = $taulukko;
									break;
								}

								continue;
							}
							else {

								if ($debug) {
									echo "vapaa_chk_x: $vapaa_chk_x ($vapaa_x) vapaa_chk_y: $vapaa_chk_y ($vapaa_y) vapaa_chk_z: $vapaa_chk_z ($vapaa_z)<br><br>";
								}

								if ($pakkaukset[$key]['paino'] > ($pakkauksen_paino + $tuotteenpaino)) {
									$pakkauksen_paino += $tuotteenpaino;

									if ($debug) {
										echo "Pakkauksen [$key] paino nyt: $pakkauksen_paino<br>";
									}
								}
								else {
									if ($debug) {
										echo "PAINO YLITTÄÄ PAKKAUKSEN MAKSIMIPAINON! ($pakkauksen_paino + $tuotteenpaino > {$pakkaukset[$key]['paino']})<br>";
									}

									continue;
								}

								for ($x = $vapaa_x; $x <= $vapaa_chk_x; $x += $resoluutio) {
									for ($y = $vapaa_y; $y <= $vapaa_chk_y; $y += $resoluutio) {
										for ($z = $vapaa_z; $z <= $vapaa_chk_z; $z += $resoluutio) {
											$taulukko_chk["$x"]["$y"]["$z"] = false;
										}
									}
								}
							}

							if ($juokseva_pakkausnro == 0) $juokseva_pakkausnro = 1;

							if (!isset($kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnro][$tuoterow['tunnus']])) $kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnro][$tuoterow['tunnus']] = 0;
							$kaytettavat_pakkaukset[$key][$row['liitostunnus']][$juokseva_pakkausnro][$tuoterow['tunnus']] += $i - $kaytetty_kpl;
							$kaytetty_kpl = $i;
							$erat['tilaukset'][$tuoterow['otunnus']] = $tuoterow['otunnus'];
							$rivit++;

							if ($i == $tuoterow['y_maara']) {
								if ($ed_asiakas != '' and $ed_asiakas != $row['liitostunnus']) {
									$eran_koko += $pakkaukset[$key]['leveys'] * $pakkaukset[$key]['syvyys'];
								}
							}

							break;
						} #end foreach
					} #end for

					$ed_asiakas = $row['liitostunnus'];
				} #end if
			}

			if (mysql_num_rows($tuoteres) > 0) {
				$ed_asiakas = $row['liitostunnus'];
				$ed_sisviesti2 = $row['sisviesti2'];
			}

			if ($debug) {
				echo "==========================================================<br>";
				echo "==========================================================<br><br><br><br>";
			}
		}

		if ($debug) echo "</table>";

		if ($debug) {
			if ($era_valmis) echo "ERÄ VALMIS!!!<br>";
			elseif (count($erat['tilaukset']) == 0) echo "KERÄYSERÄ ON TYHJÄ!!!<br>";
		}

		if ($ulkoinen_jarjestelma == 'K') {
			//keksitään uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$filenimi = "/Users/sami/temp/kardex/orders/ulkoisen_jarjestelman_tiedosto-".md5(uniqid(mt_rand(), true)).".txt";
			$fh = fopen($filenimi, "w+");
		}

		foreach ($kaytettavat_pakkaukset as $pakkauksen_nro => $larr) {
			foreach ($larr as $as_tun => $jarr) {
				foreach ($jarr as $juokseva_nro => $marr) {
					foreach ($marr as $tilriv => $kpl) {

						$erat['pakkaukset'][$pakkauksen_nro][$juokseva_nro][$tilriv] = $kpl;

						if ($ulkoinen_jarjestelma == 'K') {
							$query = "	SELECT otunnus, tuoteno, nimitys, CONCAT(hyllyalue, '-', hyllynro, '-', hyllyvali, '-', hyllytaso) AS hyllypaikka
										FROM tilausrivi
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$tilriv}'";
							$tilriv_tiedot_res = pupe_query($query);
							$tilriv_tiedot_row = mysql_fetch_assoc($tilriv_tiedot_res);

							$sisalto = "4;{$juokseva_nro};{$tilriv_tiedot_row['tuoteno']};{$tilriv_tiedot_row['nimitys']};-{$kpl};{$tilriv_tiedot_row['hyllypaikka']};{$tilriv};";

							fputs($fh, $sisalto."\r\n");
						}
					}
				}
			}
		}

		if ($ulkoinen_jarjestelma == 'K') {

			fclose($fh);

			$ftphost = $ftphost_kardex;
			$ftpuser = $ftpuser_kardex;
			$ftppass = $ftppass_kardex;
			$ftppath = $ftppath_kardex;

			$ftpfile = $unlink_filenimi = $filenimi;

			require('inc/ftp-send.inc');

			unlink($unlink_filenimi);
		}

		if ($debug) {
			echo "<pre>",var_dump($kaytettavat_pakkaukset, $erat),"</pre>";
		}

		return $erat;
	}
}


if (!function_exists("pupesoft_lahetetyyppi")) {
	function pupesoft_lahetetyyppi($laskutunnus, $asiakastunnus = 0) {
		global $kukarow;

		if ((int) $laskutunnus != 0) {
			# Haetaan laskun lähetetyyppi
			$query = "	SELECT lahetetyyppi, liitostunnus
						FROM lasku
						WHERE tunnus = '$laskutunnus'
						AND yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {

				$lahetetyyppi = mysql_fetch_assoc($result);

				if ($lahetetyyppi['lahetetyyppi'] != '') {
					return $lahetetyyppi['lahetetyyppi'];
				}

				$asiakastunnus = $lahetetyyppi["liitostunnus"];
			}
		}

		if ((int) $asiakastunnus != 0) {
			# Jos tilauksella ei ollut lähetetyyppiä, haetaan asiakkaan oletuslähettyyppi
			$query = "	SELECT lahetetyyppi
						FROM asiakas
						WHERE tunnus = '$asiakastunnus'
						AND yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {

				$lahetetyyppi = mysql_fetch_assoc($result);

				if ($lahetetyyppi['lahetetyyppi'] != '') {
					return $lahetetyyppi['lahetetyyppi'];
				}
			}
		}

		# Jos ei asiakkaalla ei ole lähetetyyppiä, haetaan yhtiön oletuslähetetyyppi
		$query = "	SELECT selite
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]'
					AND laji = 'LAHETETYYPPI'
					ORDER BY jarjestys, selite
					LIMIT 1";
		$result = pupe_query($query);
		$lahetetyyppi = mysql_fetch_assoc($result);

		if ($lahetetyyppi['selite'] != '') {
			return $lahetetyyppi['selite'];
		}

		return false;
	}
}

if (!function_exists("pupesoft_tulosta_lahete")) {
	function pupesoft_tulosta_lahete($params) {
		global $kukarow, $yhtiorow;

		extract($params);

		$otunnus = $laskurow["tunnus"];

		//hatetaan asiakkaan lähetetyyppi
		$query = "  SELECT luokka, puhelin, if (asiakasnro!='', asiakasnro, ytunnus) asiakasnro, asiakasnro as asiakasnro_aito
					FROM asiakas
					WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
		$result = pupe_query($query);
		$asrow = mysql_fetch_assoc($result);

		$lahetetyyppi = "";

		if (isset($sellahetetyyppi) and $sellahetetyyppi != '') {
			$lahetetyyppi = $sellahetetyyppi;
		}
		else {
			$lahetetyyppi = pupesoft_lahetetyyppi($otunnus, $laskurow['liitostunnus']);
		}

		/*
		Testausta varten.....
		$lahetetyyppi = "tulosta_lahete.inc";
		$lahetetyyppi = "tulosta_lahete_ean.inc";
		$lahetetyyppi = "tulosta_lahete_brutto.inc";
		$lahetetyyppi = "tulosta_lahete_eialeja.inc";
		$lahetetyyppi = "tulosta_lahete_viivakoodi.inc";
		$lahetetyyppi = "tulosta_lahete_viivakoodi_osh.inc";
		$lahetetyyppi = "tulosta_lahete_asiakviivakoodi.inc";
		$lahetetyyppi = "tulosta_lahete_viivakoodi_ean13.inc";
		$lahetetyyppi = "tulosta_lahete_eialehintoja.inc";
		$lahetetyyppi = "tulosta_lahete_hae_hinnat.inc";
		$lahetetyyppi = "tulosta_lahete_custom.inc";
		$lahetetyyppi = "tulosta_lahete_eiale_eihinta.inc";
		*/

		if (@include("tilauskasittely/tulosta_lahete.inc"));
		else require("tulosta_lahete.inc");

		// katotaan miten halutaan sortattavan
		// haetaan asiakkaan tietojen takaa sorttaustiedot
		$order_sorttaus = '';

		$asiakas_apu_query = "	SELECT lahetteen_jarjestys, lahetteen_jarjestys_suunta, email
								FROM asiakas
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$laskurow[liitostunnus]'";
		$asiakas_apu_res = pupe_query($asiakas_apu_query);

		if (mysql_num_rows($asiakas_apu_res) == 1) {
			$asiakas_apu_row = mysql_fetch_assoc($asiakas_apu_res);
			$sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["lahetteen_jarjestys"] != "" ? $asiakas_apu_row["lahetteen_jarjestys"] : $yhtiorow["lahetteen_jarjestys"]);
			$order_sorttaus = $asiakas_apu_row["lahetteen_jarjestys_suunta"] != "" ? $asiakas_apu_row["lahetteen_jarjestys_suunta"] : $yhtiorow["lahetteen_jarjestys_suunta"];
		}
		else {
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);
			$order_sorttaus = $yhtiorow["lahetteen_jarjestys_suunta"];
		}

		if ($yhtiorow["lahetteen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
		else $pjat_sortlisa = "";

		if ($laskurow["tila"] == "L" or $laskurow["tila"] == "N") {
			$tyyppilisa = " and tilausrivi.tyyppi in ('L') ";
		}
		else {
			$tyyppilisa = " and tilausrivi.tyyppi in ('L','G','W') ";
		}

		$query_ale_lisa = generoi_alekentta('M');

		//generoidaan lähetteelle ja keräyslistalle rivinumerot
		$query = "  SELECT tilausrivi.*,
					round(if (tuote.myymalahinta != 0, tuote.myymalahinta/if(tuote.myyntihinta_maara>0, tuote.myyntihinta_maara, 1), tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]') ovhhinta,
					round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
					$sorttauskentta,
					if (tilausrivi.tuoteno='$yhtiorow[rahti_tuotenumero]', 2, if(tilausrivi.var='J', 1, 0)) jtsort,
					if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
					if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
					tuote.sarjanumeroseuranta
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.otunnus = '$otunnus'
					and tilausrivi.yhtio = '$kukarow[yhtio]'
					$tyyppilisa
					and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
					ORDER BY jtsort, $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
		$riresult = pupe_query($query);

		$params_lahete = array(
		'arvo'						=> 0,
		'asrow'						=> $asrow,
		'boldi'						=> $boldi,
		'ei_otsikoita'				=> '',
		'extranet_tilausvahvistus'	=> $extranet_tilausvahvistus,
		'iso'						=> $iso,
		'jtid'						=> '',
		'kala'						=> 0,
		'kassa_ale'					=> '',
		'kieli'						=> $kieli,
		'lahetetyyppi'				=> $lahetetyyppi,
		'laskurow'					=> $laskurow,
		'naytetaanko_rivihinta'		=> $naytetaanko_rivihinta,
		'norm'						=> $norm,
		'page'						=> NULL,
		'pdf'						=> NULL,
		'perheid'					=> 0,
		'pieni'						=> $pieni,
		'pieni_boldi'				=> $pieni_boldi,
		'pitkattuotteet'			=> FALSE,
		'rectparam'					=> $rectparam,
		'riviresult'				=> $riresult,
		'rivinkorkeus'				=> $rivinkorkeus,
		'rivinumerot'				=> "",
		'row'						=> NULL,
		'sivu'						=> 1,
		'summa'						=> 0,
		'tee'						=> $tee,
		'thispage'					=> NULL,
		'toim'						=> $toim,
		'tots'						=> 0,
		'tuotenopituus'				=> '',
		'nimityskohta'				=> '',
		'nimitysleveys'				=> '',
		'tyyppi'					=> '',
		'useita'					=> '',
		'komento'					=> $komento,
		'yhteensamaara'				=> 0,
		'lahetekpl'					=> $lahetekpl,
		);

		if ($laskurow["tila"] == "G") {
			$params_lahete["tyyppi"] = "SIIRTOLISTA";
		}

		// Aloitellaan lähetteen teko
		$params_lahete = alku_lahete($params_lahete);

		// Piirretään rivit
		mysql_data_seek($riresult,0);

		while ($row = mysql_fetch_assoc($riresult)) {
			$params_lahete["row"] = $row;
			$params_lahete = rivi_lahete($params_lahete);
		}

		// Haetaan erikseen toimitettavat tuotteet
		if ($laskurow["vanhatunnus"] != 0) {
			$query = " 	SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
						FROM lasku use index (yhtio_vanhatunnus)
						WHERE yhtio		= '$kukarow[yhtio]'
						and vanhatunnus = '$laskurow[vanhatunnus]'
						and tunnus != '$laskurow[tunnus]'";
			$perheresult = pupe_query($query);
			$tunrow = mysql_fetch_assoc($perheresult);

			//generoidaan lähetteelle ja keräyslistalle rivinumerot
			if ($tunrow["tunnukset"] != "") {

				$toimitettulisa = "";

				if ($laskurow["clearing"] == "ENNAKKOTILAUS" or $laskurow["clearing"] == "JT-TILAUS") {
					$toimitettulisa = " and tilausrivi.toimitettu = '' ";
				}

				$query = "  SELECT tilausrivi.*,
							round(if (tuote.myymalahinta != 0, tuote.myymalahinta/if(tuote.myyntihinta_maara>0, tuote.myyntihinta_maara, 1), tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]') ovhhinta,
							round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
							$sorttauskentta,
							if (tilausrivi.tuoteno='$yhtiorow[rahti_tuotenumero]', 2, if(tilausrivi.var='J', 1, 0)) jtsort,
							if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
							if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
							tuote.sarjanumeroseuranta
							FROM tilausrivi
							JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
							JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
							WHERE tilausrivi.otunnus in ('$tunrow[tunnukset]')
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							$tyyppilisa
							$toimitettulisa
							and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
							ORDER BY jtsort, $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
				$riresult = pupe_query($query);

				while ($row = mysql_fetch_assoc($riresult)) {

					if ($row['toimitettu'] == '') {
						$row['kommentti'] .= "\n******* ".t("Toimitetaan erikseen",$kieli).". *******";
					}
					else {
						$row['kommentti'] .= "\n******* ".t("Toimitettu erikseen tilauksella",$kieli)." ".$row['otunnus'].". *******";
					}

					$row['rivihinta'] 	= "";
					$row['varattu'] 	= "";
					$row['kpl']			= "";
					$row['jt'] 			= "";
					$row['d_erikseen'] 	= "JOO";

					$params_lahete["row"] = $row;
					$params_lahete = rivi_lahete($params_lahete);
				}
			}
		}

		// Loppulaatikot
		$params_lahete["tots"] = 1;
		$params_lahete = loppu_lahete($params_lahete);

		//katotaan onko laskutus nouto
		$query = "  SELECT toimitustapa.nouto, maksuehto.kateinen
					FROM lasku
					JOIN toimitustapa ON lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite
					JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
					WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = '$laskurow[tunnus]'
					and toimitustapa.nouto != '' and maksuehto.kateinen = ''";
		$kures = pupe_query($query);

		if (mysql_num_rows($kures) > 0 and $yhtiorow["lahete_nouto_allekirjoitus"] != "") {
			$params_lahete = kuittaus_lahete($params_lahete);
		}

		if (is_array($komento) and isset($komento["Lähete"])) $params_lahete["komento"] = $komento["Lähete"];

		//tulostetaan sivu
		print_pdf_lahete($params_lahete);
	}
}

if (!function_exists("pupeFileReader")) {
	function pupeFileReader($file, $extension = "", $saveintocsvfile = "") {

        /** Laitetaan rivit arrayseen **/
    	$excelrivit = array();

        // Jos halutaan tehdä esim XLSX to CSV konversio
        if ($saveintocsvfile != "") {
            // Aina temppiin
            $saveintocsvfile = "/tmp/".basename($saveintocsvfile);

            // Avataan file
            $savefile = fopen($saveintocsvfile, "w");
        }

        if ($extension == "DATAIMPORT") {
        	/** Ladataan CSV file **/
        	$file = fopen($file,"r") or die (t("Tiedoston avaus epäonnistui")."!");

        	while ($rivi = fgets($file)) {
        		/** Luetaan CSV rivi tiedostosta **/

        		// Katsotaan onko UTF-8 muodossa
        		if (mb_detect_encoding($rivi, 'UTF-8', true) !== FALSE) {
        			$rivi = str_getcsv(utf8_decode($rivi));
        		}
        		else {
        			$rivi = str_getcsv($rivi);
        		}

        		$excelrivit[] = $rivi;
        	}

        	fclose($file);
        }
        elseif ($extension == "TXT" or $extension == "CSV") {
        	/** Ladataan Tab eroteltu teksti file **/
        	$file = fopen($file,"r") or die (t("Tiedoston avaus epäonnistui")."!");

        	$rowIndex = 0;

        	while ($rivi = fgets($file)) {
        		// luetaan rivi tiedostosta..
        		$rivi = explode("\t", str_replace(array("'", "\\"), "", $rivi));

        		for ($colIndex = 0; $colIndex < count($rivi); $colIndex++) {
        			$excelrivit[$rowIndex][$colIndex] = trim($rivi[$colIndex]);
        		}

        		$rowIndex++;
        	}

        	fclose($file);
        }
        elseif ($extension == "XLSX") {
        	/** XLSX XML-tiedosto **/
        	$foldername = md5(uniqid(rand(),true));

        	function charColumn($string) {

        		$string = strrev($string);
        		$luku 	= 0;

        		for ($r = 0; $r < strlen($string); $r++) {
        			$luku += (ord($string{$r})-64) * pow(26, $r);
        		}

        		return $luku-1;
        	}

        	// Avataan XLSX Zippi
        	exec("cp $file /tmp/$foldername.zip; unzip /tmp/$foldername.zip -d /tmp/$foldername;");

        	$workSheetFile		= "/tmp/$foldername/xl/worksheets/sheet1.xml";
        	$sharedStringsFile	= "/tmp/$foldername/xl/sharedStrings.xml";

        	$sheetData		= simplexml_load_file($workSheetFile);
        	$sharedStrings	= simplexml_load_file($sharedStringsFile);

        	$sharedStringsArray = array();

        	foreach ($sharedStrings->si as $string) {
        		$sharedStringsArray[] = utf8_decode($string->t);
        	}

        	foreach ($sheetData->sheetData->row as $row) {

        		$rowIndex = ($row->attributes()->r)-1;

        		foreach ($row->c as $cell) {

        			if (isset($cell->attributes()->t) and $cell->attributes()->t == "s") {
        				$value = $sharedStringsArray[(int) $cell->v];
        			}
        			else {
        				$value = $cell->v;
        			}

        			$colIndex = charColumn(str_replace(($rowIndex+1), "", (string) $cell->attributes()->r));

        			$excelrivit[$rowIndex][$colIndex] = trim($value);
        			$colIndex++;
        		}
        	}

        	// XLSX failisssa ei ole ollenkaan tyhjiä soluja, injisoidaan ne tässä...
        	foreach ($excelrivit as $rowIndex => $row) {
        		$edindex = 0;

        		foreach ($row as $colIndex => $column) {

        			if ($colIndex > 0 and $colIndex > $edindex+1) {
        				for ($inj = $edindex+1; $inj < $colIndex; $inj++) {
        					$excelrivit[$rowIndex][$inj] = "";
        				}
        			}

        			$edindex = $colIndex;
        		}

                // Laitetaan solut vielä indeksien mukaan järjestykseen jotta implode toimisi
        		ksort($excelrivit[$rowIndex]);

        		if (isset($savefile)) {
        		    fwrite($savefile, "\"".implode("\",\"", $excelrivit[$rowIndex])."\"\n");
        		}
        	}

        	// Poistetaan zipppi ja purettu zippi
        	exec("rm -f /tmp/$foldername.zip; rm -rf /tmp/$foldername;");
        }
        else {
        	/** PHPExcel kirjasto **/
        	require_once "PHPExcel/PHPExcel/IOFactory.php";

        	/** Tunnistetaan tiedostomuoto **/
        	$inputFileType = PHPExcel_IOFactory::identify($file);

        	/** Luodaan readeri **/
        	$objReader = PHPExcel_IOFactory::createReader($inputFileType);

        	/** Ladataan vain solujen datat (ei formatointeja jne) **/
        	$objReader->setReadDataOnly(true);

        	/** Ladataan vain eka sheet **/
        	$objReader->setLoadSheetsOnly(0);

        	/** Ladataan file halutuilla parametreilla **/
        	$objPHPExcel = $objReader->load($file);

        	/** Aktivoidaan eka sheetti**/
        	$objPHPExcel->setActiveSheetIndex(0);

        	/** Loopataan tiedoston rivit **/
        	foreach ($objPHPExcel->getActiveSheet()->getRowIterator() as $row) {
        	    $cellIterator = $row->getCellIterator();
        	    $cellIterator->setIterateOnlyExistingCells(false);

        		$rowIndex = ($row->getRowIndex())-1;

        	    foreach ($cellIterator as $cell) {
        	        $colIndex = (PHPExcel_Cell::columnIndexFromString($cell->getColumn()))-1;

        			$excelrivit[$rowIndex][$colIndex] = trim(utf8_decode($cell->getCalculatedValue()));
        	    }
        	}

        	/** Tuhotaan excel oliot **/
        	unset($objReader);
        	unset($objPHPExcel);
        }

        if (isset($savefile)) {
            fclose($savefile);

            return $saveintocsvfile;
        }
        else {
            return $excelrivit;
        }
    }
}

if (!function_exists("kustannuspaikka_kohde_projekti")) {
	function kustannuspaikka_kohde_projekti($tilino, $kustp=0, $kohde=0, $projekti=0) {
		global $kukarow;

		$kustp		= (int) $kustp;
		$kohde		= (int) $kohde;
		$projekti	= (int) $projekti;

		$query = "	SELECT kustp, kohde, projekti
					FROM tili
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tilino = '{$tilino}'";
		$kustp_kohde_proj_res = pupe_query($query);
		$kustp_kohde_proj_row = mysql_fetch_assoc($kustp_kohde_proj_res);

		$returnarray = array();

		if ($kustp == 0) {
			$returnarray[] = $kustp_kohde_proj_row["kustp"];
		}
		else {
			$returnarray[] = $kustp;
		}

		if ($kohde == 0) {
			$returnarray[] = $kustp_kohde_proj_row["kohde"];
		}
		else {
			$returnarray[] = $kohde;
		}

		if ($projekti == 0) {
			$returnarray[] = $kustp_kohde_proj_row["projekti"];
		}
		else {
			$returnarray[] = $projekti;
		}

		return $returnarray;
	}
}

if (!function_exists("tarkistahetu")) {
	function tarkistahetu($hetu) {

		$hetu_orig = $hetu;

		// tarkistetaan onko ytunnus henkilötunnus
		require ("inc/tarkistahetu.inc");

		if ($hetuok == 0) {
			$hetu_clean = substr($hetu_orig, 0, 7)."XXXX";
		}
		else {
			$hetu_clean = $hetu_orig;
		}

		return $hetu_clean;
	}
}

if (!function_exists("tarkistaytunnus")) {
	function tarkistaytunnus($ytunnus) {

		$tulo = 0;

		// Ytunnus pitää olla numeerinen ja 8 merkkiä pitkä
		if (!is_numeric($ytunnus) or strlen($ytunnus) != 8) {
			return FALSE;
		}

		// loopataan ytunnus
		for ($ytunnusi = 0; $ytunnusi < 7; $ytunnusi++) {

			$merkki = substr($ytunnus, $ytunnusi, 1);

			switch ($ytunnusi) {
				case 0:
					$kerroin = 7;
					break;
				case 1:
					$kerroin = 9;
					break;
				case 2:
					$kerroin = 10;
					break;
				case 3:
					$kerroin = 5;
					break;
				case 4:
					$kerroin = 8;
					break;
				case 5:
					$kerroin = 4;
					break;
				case 6:
					$kerroin = 2;
					break;
			}
			$tulo += $kerroin * $merkki;
		}

		// otetaan tarkastusmerkki
		$tmerkki = substr($ytunnus, -1);

		// summasta mod 11
		$tulo = $tulo % 11;

		if ($tulo <> 0) {
			$tulo = 11 - $tulo;
		}

		// oliko tarkastusmerkki ok
		if ($tulo == $tmerkki) {
			return TRUE;
		}
		else {
			return FALSE;
		}

	}
}

if (!function_exists('gauge')) {
	function gauge() {

		/**
		 * Mittari piirretään diviin id:llä chart_div
		 * Tarvitaan args- ja options-oliot
		 */

		/** ESIMERKKI
			var gauge = new Gauge();
			var args = {
				foo: ['BAR', 0]
			}

			var options = {	width: 800,
							height: 220,
							min: 0,
							max: 400000,
							redFrom: 200000,
							redTo: 300000,
							greenFrom: 350000,
							greenTo: 400000,
							yellowFrom: 300000,
							yellowTo: 350000,
							minorTicks: 5,
							majorTicks: ['0', '50', '100', '150', '200', '250', '300', '350', '400']};

			gauge.init(args, options);
			gauge.draw($('#value_from').val(), options.max);
		*/

		echo "<script type='text/javascript' src='https://www.google.com/jsapi'></script>";
		echo "	<script type='text/javascript' charset='utf-8'>

					google.load('visualization', '1', {packages:['gauge']});
					// google.setOnLoadCallback(drawChart);

					var Gauge = (function() {
						var data;
						var options;
						var chart;

						return {
							init: function(args, opt) {
								data = new google.visualization.DataTable();
								options = opt;

								data.addColumn('string', 'Label');
								data.addColumn('number', 'Value');

								data.addRows(1);

								var div_id;
								var _i = 0;

								for (var i in args) {
									var _x = 0;
									div_id = i;

									for (var x in args[i]) {
										data.setValue(_i, _x, args[i][x]);
										_x++;
									}
									_i++;
								}

								$('#chart_div').append('<div id=\''+div_id+'\'></div>');
								$('#'+div_id).css('float', 'left');
								var div = document.getElementById(div_id);
								chart = new google.visualization.Gauge(div);

								chart.draw(data, options);

								var body_bgcolor = $('body').css('background-color');
								$('#chart_div *').css({'background-color': body_bgcolor});
							},
							draw: function(value, max) {

								value = parseInt(value);

								if (parseInt((value / (max / 40))) >= 4) {

									var force = parseInt(value / (max / 40));
									force = force < 2 ? 2 : force;
									force = 1 + '.' + force;

									var _dot120 = parseInt(value * force);
									data.setValue(0, 1, _dot120);
									chart.draw(data, options);

									var _dot90 = parseInt(value * 0.9);
									setTimeout(function(){data.setValue(0, 1, _dot90); chart.draw(data, options);}, 400);

									setTimeout(function(){data.setValue(0, 1, value); chart.draw(data, options);}, 800);
								}
								else {
									data.setValue(0, 1, value);
									chart.draw(data, options);
								}
							}
						}
					});

				</script>";
	}
}

// Lasketaan moneenko valmisteeseen raaka-aineet riittää
if (!function_exists('raaka_aineiden_riitto')) {

	function raaka_aineiden_riitto($tuoteno, $varasto = 0) {

		global $kukarow;

		// Käytettävät muuttujat
		$join_lisa = "";
		$varasto = (int) $varasto;
		$tuoteno = mysql_real_escape_string($tuoteno);

		// Katotaan halutaanko saldo vaan jostain tietystä varastosta (varastopaikat.tunnus), silloin unohdetaan edellä annettu tyyppi kokonaan
		if ($varasto != 0) {
			$join_lisa = "	JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
							JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
							and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
							and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
							and varastopaikat.tunnus in ($varasto)";
		}

		// Loopataan lapsituotteet läpi
		$query = "	SELECT tuoteperhe.tuoteno,
					tuoteperhe.kerroin
					FROM tuoteperhe
					JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio
						AND tuote.tuoteno = tuoteperhe.tuoteno
						AND tuote.ei_saldoa = '')
					$join_lisa
					WHERE tuoteperhe.yhtio = '{$kukarow["yhtio"]}'
					AND tuoteperhe.isatuoteno = '$tuoteno'
					AND tuoteperhe.tyyppi = 'R'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			return 0;
		}

		$voidaan_valmistaa = 9999999999999;

		// Katotaan moneeko valmisteeseen lapsien saldo riittää
		while ($row = mysql_fetch_assoc($result)) {
			list ($saldo, $hyllyssa, $myytavissa, $bool) = saldo_myytavissa($row["tuoteno"], '', $varasto);

			$riitto = $myytavissa / $row["kerroin"];

			// Otetaan telteen pienin riitto
			if ($riitto < $voidaan_valmistaa) {
			 	$voidaan_valmistaa = $riitto;
			}
		}

		// Näytetään alle nollat nollana ja pyöristetään desimaalit alas
		$voidaan_valmistaa = $voidaan_valmistaa < 0 ? 0 : floor($voidaan_valmistaa);

		return $voidaan_valmistaa;
	}
}

if (!function_exists("mod11_tarkiste")) {
	function mod11_tarkiste ($numero) {

		$tulos = "";
		$painoarvo = array( 2, 3, 4, 5, 6, 7, 2, 3, 4, 5, 6, 7, 2, 3, 4, 5, 6, 7, 2, 3, 4, 5, 6, 7 );

		# Käännetään stringi takaperin
		$numero_rev = strrev($numero);

		for ($i = 0, $summa = 0; $i < strlen($numero_rev); $i++) {
			# Lasketaan tulo ja kumuloidaan
			$summa += substr($numero_rev, $i, 1 ) * $painoarvo[$i];
		}

		# lasketaan tarkiste ja konkatenoidaan peruslukuun
		$jaannos = $summa % 11;

		switch ($jaannos) {
			case 0:
				$tulos = $numero . 0;
				break;
			case 1:
				$tulos = $numero . 0;
				break;
			default:
				$tulos = $numero . (11 - $jaannos);
				break;
		}

		return $tulos;
	}
}

if (!function_exists("mod10_tarkiste")) {
	function mod10_tarkiste ($numero) {

		$kerroin = 7;
		$tulo    = 0;

		for ($i=1; $i<=strlen($numero); $i++) {

			$merkki = substr($numero, -1 * $i, 1);
			$tulo += $kerroin * $merkki;

			switch ($kerroin) {
				case 7:
					$kerroin = 3;
					break;
				case 3:
					$kerroin = 1;
					break;
				case 1:
					$kerroin = 7;
					break;
			}
		}

		$tulo = substr($tulo, -1) + 0;

		if ($tulo != 0) {
			$tulo = 10 - $tulo;
		}

		$numero .= $tulo;

		return $numero;
	}
}

// Lasketaan moneenko valmisteeseen raaka-aineet riittää
if (!function_exists('zendesk_curl')) {
	function zendesk_curl ($url) {
		global $zendesk_auth;

		$ch  = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_GET, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, "Content-Type: application/xml");
		curl_setopt($ch, CURLOPT_USERPWD, $zendesk_auth);

		$result = curl_exec($ch);

		if ($result === FALSE) {
			echo "<font class='error'>VIRHE:</font><br>";
		   	echo curl_errno($ch)." - ".curl_error($ch)."</font><br>";
			exit;
		}

		$info 	= curl_getinfo($ch);
		$header = substr($result,0,$info['header_size']);
		$body 	= substr($result, $info['header_size']);

		curl_close($ch);

		$statukset = array();
		$statukset[0] = "New";
		$statukset[1] = "Open";
		$statukset[2] = "Pending";
		$statukset[3] = "Solved";

		$priot = array();
		$priot[0] = "-";
		$priot[1] = "Low";
		$priot[2] = "Normal";
		$priot[3] = "High";
		$priot[4] = "Urgent";

		return array($body, $statukset, $priot);
	}
}

// Haetaan ryhmänimet tietokannasta ja palautetaan 2 arrayta
if (!function_exists('hae_ryhmanimet')) {
	function hae_ryhmanimet ($abc_tyyppi) {
		global $yhtiorow, $kukarow;

		if ($abc_tyyppi == "") {
			return FALSE;
		}

		// Haetaan abc-parametrit
		$query = "	SELECT *
					FROM abc_parametrit
					WHERE yhtio = '{$kukarow["yhtio"]}'
					and tyyppi 	= '{$abc_tyyppi}'
					ORDER by luokka";
		$res = pupe_query($query);

		if (mysql_num_rows($res) == 0) {
			echo t("ABC-parametrit puuttuu")."</br>";
			exit;
		}

		$ryhmanimet   					= array();
		$ryhmaprossat					= array();
		$kiertonopeus_tavoite 			= array();
		$palvelutaso_tavoite 			= array();
		$varmuusvarasto_pv   			= array();
		$toimittajan_toimitusaika_pv 	= array();

		while ($row = mysql_fetch_array($res)) {
			$ryhmanimet[] 					= $row["luokka"];
			$ryhmaprossat[] 				= $row["osuusprosentti"];
			$kiertonopeus_tavoite[] 		= $row["kiertonopeus_tavoite"];
			$palvelutaso_tavoite[] 			= $row["palvelutaso_tavoite"];
			$varmuusvarasto_pv[]   			= $row["varmuusvarasto_pv"];
			$toimittajan_toimitusaika_pv[] 	= $row["toimittajan_toimitusaika_pv"];
		}

		return array($ryhmanimet, $ryhmaprossat, $kiertonopeus_tavoite, $palvelutaso_tavoite, $varmuusvarasto_pv, $toimittajan_toimitusaika_pv);
	}
}


// Tehdään asiakkaan tunnuksesta myyntitili-varastopaikka
if (!function_exists('myyntitili_varastopaikka')) {
	function myyntitili_varastopaikka($tunnus) {

		// Left paddataan nollilla 15 merkkiä pitkäksi
		$asiakkaan_tunnus = str_pad($tunnus, 15, 0, STR_PAD_LEFT);

		// Splitataan kolmeen, 5 merkkiä pitkään, osaan
		$hyllypaikka = str_split($asiakkaan_tunnus, 5);

		// Palautetaan hyllyalue, hyllynro, hyllyvali, hyllytaso
		return array("!!M", $hyllypaikka[0], $hyllypaikka[1], $hyllypaikka[2]);
	}
}

// Tulostetaan VAK/ADR-erittely
if (!function_exists('tulosta_vakadr_erittely')) {
	function tulosta_vakadr_erittely($laskun_tunnus, $vakadr_komento, $tee) {
		global $yhtiorow, $kukarow, $kieli;

		if ($yhtiorow["kerayserat"] != "K") {
			echo t("Keräyserät pitää olla käytössä, ei tulosteta VAK-erittelyä");
			return;
		}

		if ($yhtiorow["vak_erittely"] != "K") {
			echo t("Vak-erittely pitää olla käytössä, ei tulosteta VAK-erittelyä");
			return;
		}

		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND tunnus = '$laskun_tunnus'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo t("Laskua ei löydy");
			return;
		}

		$laskurow = mysql_fetch_assoc($result);

		require("tilauskasittely/tulosta_vakerittely.inc");

		if ($kieli == '') {
			$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]'";
			$kielresult = mysql_query($querykiel) or pupe_error($querykiel);
			$kielrow = mysql_fetch_assoc($kielresult);
			$kieli = strtolower($kielrow['kieli']);
		}

		// Haetaan kaikki tilauksen SSCC koodit
		$query = "	SELECT distinct sscc, sscc_ulkoinen
					FROM tilausrivi
					JOIN kerayserat on (kerayserat.yhtio = tilausrivi.yhtio and kerayserat.tilausrivi = tilausrivi.tunnus)
					JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.vakkoodi != '')
					WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
					AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
					AND tilausrivi.tyyppi != 'D'
					AND tilausrivi.kpl+tilausrivi.varattu > 0";
		$result_sscc = pupe_query($query);

		if (mysql_num_rows($result_sscc) > 0) {

			$params_vak = array(
						'boldi'			=> $boldi,
						'iso'			=> $iso,
						'kala'			=> 0,
						'kieli'			=> $kieli,
						'komento' 		=> $vakadr_komento,
						'laskurow'		=> $laskurow,
						'massa'			=> 0,
						'norm'			=> $norm,
						'page'			=> NULL,
						'pdf'			=> NULL,
						'pieni'			=> $pieni,
						'sivu'			=> 1,
						'rectparam'		=> $rectparam,
						'tee'			=> $tee,
						'sscc' 			=> NULL,
						'sscc_ulkoinen' => NULL,
						'thispage'		=> NULL,
						'vakkoodi' 		=> NULL,
						);

			// VAK lapun otsikko
			$params_vak = vakadr_otsikko($params_vak);

			while ($row = mysql_fetch_assoc($result_sscc)) {

				$params_vak['sscc'] 			= $row["sscc"];
				$params_vak['sscc_ulkoinen'] 	= $row["sscc_ulkoinen"];
				$params_vak['vakkoodi'] 		= NULL;
				$params_vak['massa']	 		= NULL;

				// piirretään riviotsikko (koska meillä on sscc setattu)
				$params_vak = vakadr_rivi($params_vak);

				// Käytetäänkö VAK-tietokantaa
				if ($yhtiorow["vak_kasittely"] != "") {

					if (isset($kieli) and strtolower($kieli) != "fi") {
						$nimityskuvaus = "name_and_description";
					}
					else {
						$nimityskuvaus = "nimi_ja_kuvaus";
					}

					$vakselect = " concat('UN ',vak.yk_nro,' ', vak.{$nimityskuvaus},', ', vak.lipukkeet,', ', vak.pakkausryhma) as vakkoodi,";
					$vakjoin   = " JOIN vak ON (tuote.yhtio = vak.yhtio and tuote.vakkoodi = vak.tunnus)";
				}
				else {
					$vakselect = " tuote.vakkoodi vakkoodi,";
					$vakjoin   = "";
				}

				// Haetaan paketille (SSCC) kaikki vakkoodit ja niiden massa (kerroin 0.95, koska koko tuotteen massa ei ole yleensä vaarallista ainetta)
				$aliquery = "	SELECT {$vakselect}
								sum(kerayserat.kpl * tuote.tuotemassa * 0.95) as massa
								FROM tilausrivi
								JOIN kerayserat on (kerayserat.yhtio = tilausrivi.yhtio and kerayserat.tilausrivi = tilausrivi.tunnus and kerayserat.sscc = {$row['sscc']})
								JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.vakkoodi != '')
								{$vakjoin}
								WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
								AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
								AND tilausrivi.tyyppi != 'D'
								AND tilausrivi.kpl+tilausrivi.varattu > 0
								group by vakkoodi";
				$apuresult = pupe_query($aliquery);

				while ($sscc_rivi = mysql_fetch_assoc($apuresult)) {
					$params_vak['sscc'] 			= NULL;
					$params_vak['sscc_ulkoinen'] 	= NULL;
					$params_vak['vakkoodi'] 		= $sscc_rivi["vakkoodi"];
					$params_vak['massa']	 		= $sscc_rivi["massa"];

					// Piirretään vakkoodit (koska meillä on vakkoodi ja massa setattu)
					$params_vak = vakadr_rivi($params_vak);
				}
			}

			// Piirretään footer
			$params_vak = vakadr_loppu($params_vak);

			// Tulostetaan PDF
			print_pdf_vakadr($params_vak);
		}
	}
}

if (!function_exists("pupesoft_sahkoposti")) {

	function pupesoft_sahkoposti($parametrit) {

		/* Tällä funktiolla lähetetään sähköposti, johon voidaan liittää yksi tai useampi liitetiedosto.

		Parametrinä seuraavanlainen array:

		$parametri = array( "to" 		=> "devlab@devlab.fi",
							"cc" 		=> "",
							"subject"	=> "Mail from Pupesoft!",
							"ctype"		=> "html",
							"body"		=> "<h1>Hello from Pupesoft!</h1>",

							"attachements" => array(0 	=> array(
														"filename"		=> "/foo/baz/file1.txt",
														"newfilename"	=> "report.txt",
														"ctype"			=> "text"),

													1 	=> array(
														"filename"		=> "/bar/foo/file2",
														"newfilename"	=> "pretty_report.xls",
														"ctype"			=> "excel"),
							)
		);

		*/

		global $yhtiorow, $kukarow;

		// Tarvitaan seuraavat parametrit
		$email_to		= isset($parametrit["to"]) ? $parametrit["to"] : "";
		$email_cc		= isset($parametrit["cc"]) ? $parametrit["cc"] : "";
		$email_subject	= isset($parametrit["subject"]) ? $parametrit["subject"] : "";
		$email_ctype 	= isset($parametrit["ctype"]) ? strtoupper($parametrit["ctype"]) : "";
		$email_body 	= isset($parametrit["body"]) ? $parametrit["body"] : "";

		// Liitetiedostoja tulee parametreissä arrayna
		$liitteet		= (isset($parametrit["attachements"]) and is_array($parametrit["attachements"])) ? $parametrit["attachements"] : "";

		// Kaikki pitää tulla kunnossa (paitsi body ja cc)
		if ($email_to == "" or $email_subject == "" or $email_ctype == "" or $liitteet == "") {
			return false;
		}

		$content = "";
		$bound = uniqid(time()."_") ;
		$header = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <{$yhtiorow["postittaja_email"]}>\n";

		if ($email_cc != "") {
			$header .= "Cc: $email_cc\n";
		}

		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

		if ($email_body != "") {
			$content .= "--$bound\n";
			if ($email_ctype == "HTML") {
				$content .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
			}
			else {
				$content .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
			}
			$content .= "Content-Transfer-Encoding: quoted-printable\n\n";
			$content .= "$email_body\n\n";
		}

		$content .= "--$bound";

		foreach ($liitteet as $liite) {

			// Tarkistetaan $liite arrayn oikeellisuus
			$attachement_filename		= isset($liite["filename"]) ? $liite["filename"] : "";
			$attachement_newfilename	= isset($liite["newfilename"]) ? $liite["newfilename"] : "";
			$attachement_ctype			= isset($liite["ctype"]) ? strtoupper($liite["ctype"]) : "";

			// Jos tiedostoa ei löydy tai ctype on tyjää, hypätään filen yli.
			if (!file_exists($attachement_filename) or !is_readable($attachement_filename) or $attachement_ctype == "") {
				continue;
			}

			// Otetaan tiedoston sisältö muuttujaan
			$attachement_content = chunk_split(base64_encode(file_get_contents($attachement_filename)));

			// Tiedostosta vain nimi
			$attachement_name = basename($attachement_filename);

			// Jos on poikkeava nimi, otetaan se
			if ($attachement_newfilename != "") {
				$attachement_name = basename($attachement_newfilename);
			}

			// Boundin jälkeen aina rivinvaihto
			$content .= "\n";

			if ($attachement_ctype == "TEXT") {
				$content .= "Content-Type: text/plain; name=\"$attachement_name\"\n";
			}
			elseif ($attachement_ctype == "PS") {
				$content .= "Content-Type: application/ps; name=\"$attachement_name\"\n";
			}
			elseif ($attachement_ctype == "JPG" or $attachement_ctype == "JPEG") {
				$content .= "Content-Type: image/jpeg; name=\"$attachement_name\"\n";
			}
			elseif ($attachement_ctype == "PNG") {
				$content .= "Content-Type: image/png; name=\"$attachement_name\"\n";
			}
			elseif ($attachement_ctype == "GIF") {
				$content .= "Content-Type: image/gif; name=\"$attachement_name\"\n";
			}
			elseif ($attachement_ctype == "TIF" or $attachement_ctype == "TIFF") {
				$content .= "Content-Type: image/tiff; name=\"$attachement_name\"\n";
			}
			elseif ($attachement_ctype == "BMP") {
				$content .= "Content-Type: image/bmp; name=\"$attachement_name\"\n";
			}
			elseif ($attachement_ctype == "EXCEL") {
				$content .= "Content-Type: application/excel; name=\"$attachement_name\"\n";
			}
			elseif ($attachement_ctype == "PDF") {
				$content .= "Content-Type: application/pdf; name=\"$attachement_name\"\n";
			}
			else {
				// Defaulttina myös pdf ?
				$content .= "Content-Type: application/pdf; name=\"$attachement_name\"\n";
			}

			$content .= "Content-Transfer-Encoding: base64\n";
			$content .= "Content-Disposition: attachment; filename=\"$attachement_name\"\n\n";
			$content .= $attachement_content;
			$content .= "\n";
			$content .= "--$bound";
		}

		// Vikassa boundissa pitää olla -- lopussa.
		$content .= "--\n";

		$boob = mail($email_to, mb_encode_mimeheader($email_subject, "ISO-8859-1", "Q"), $content, $header, "-f {$yhtiorow["postittaja_email"]}");

		if ($boob === FALSE) echo t("Sähköpostin lähetys epäonnistui").": $email_to<br>";

		return $boob;
	}
}

if (!function_exists("ostolaskun_kulujen_tiliointi")) {
	function ostolaskun_kulujen_tiliointi($laskun_summa, $tiliointirivin_summa, $osto_rahti = 0, $osto_kulu = 0, $osto_rivi_kulu = 0) {

		global $kukarow, $yhtiorow;

		## TARVITAAN SEURAAVAT MUUTTUJAT:
		# $laskun_summa
		# $tiliointirivin_summa
		# $osto_rahti
		# $osto_kulu
		# $osto_rivi_kulu
		## PALAUTETAAN TILIÖINTIRIVIN MUUTETTU ARVO

		$osto_rahti = (float) $osto_rahti;
		$osto_kulu = (float) $osto_kulu;
		$osto_rivi_kulu = (float) $osto_rivi_kulu;

		$til_sum_ratio = $tiliointirivin_summa / $laskun_summa;

		$return_array = array(
			'osto_rahti' => 0,
			'osto_kulu' => 0,
			'osto_rivi_kulu' => 0
		);

		for ($ix = 1; $ix <= $yhtiorow['ostolaskun_kulutilit']; $ix++) {

			if ($ix == 1 and trim($osto_rahti) != 0) {
				$summa 			= str_replace(",", ".", $osto_rahti) * $til_sum_ratio;
				$tiliointirivin_summa = $tiliointirivin_summa - $summa;
				$return_array['osto_rahti'] += $summa;
			}
			elseif ($ix == 2 and trim($osto_kulu) != 0) {
				$summa 			= str_replace(",", ".", $osto_kulu) * $til_sum_ratio;
				$tiliointirivin_summa = $tiliointirivin_summa - $summa;
				$return_array['osto_kulu'] += $summa;
			}
			elseif ($ix == 3 and trim($osto_rivi_kulu) != 0) {
				$summa 			= str_replace(",", ".", $osto_rivi_kulu) * $til_sum_ratio;
				$tiliointirivin_summa = $tiliointirivin_summa - $summa;
				$return_array['osto_rivi_kulu'] += $summa;
			}
		}

		return array($tiliointirivin_summa, $return_array);
	}
}

if (!function_exists("etsi_kuuluvat_tilioinnit")) {
	function etsi_kuuluvat_tilioinnit($tunnus, $ylikirjataanko = true) {
		global $kukarow;

		$summa = 0;

		// Etsitään kaikki tiliöintirivit, jotka kuuluvat tähän tiliöintiin ja lasketaan niiden summa
		$query = "	SELECT sum(summa) summa
					FROM tiliointi
					WHERE aputunnus = '{$tunnus}'
					AND yhtio 		= '{$kukarow['yhtio']}'
					AND korjattu 	= ''
					GROUP BY aputunnus";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 0) {
			$summarow = mysql_fetch_assoc($result);
			$summa += $summarow["summa"];

			if ($ylikirjataanko) {
				$query = "	UPDATE tiliointi SET
							korjattu = '{$kukarow['kuka']}',
							korjausaika = now()
							WHERE aputunnus = '{$tunnus}'
							and yhtio 		= '{$kukarow['yhtio']}'
							and korjattu 	= ''";
				$result = pupe_query($query);
			}
		}

		return $summa;
	}
}

if (!function_exists('triangulation')) {
	function triangulation($tunnukset) {
		global $kukarow, $yhtiorow;

		# Katsotaan onko kyseessä kolmikantakauppa
		$query = "	SELECT lasku.tunnus, lasku.toim_maa
					FROM tilausrivi
					JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
						AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
					JOIN tilausrivi as t3 ON (t3.yhtio = tilausrivi.yhtio
						AND t3.tunnus = tilausrivin_lisatiedot.tilausrivilinkki
						AND t3.tyyppi = 'O')
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
						AND lasku.tunnus = t3.otunnus
						AND lasku.toim_maa != '{$yhtiorow['maa']}')
					WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
					AND tilausrivi.tyyppi = 'L'
					AND tilausrivi.otunnus IN ({$tunnukset})";
		$result = pupe_query($query);

		return mysql_num_rows($result) > 0 ? "VAT 0% Triangulation.\n" : "";
	}
}
