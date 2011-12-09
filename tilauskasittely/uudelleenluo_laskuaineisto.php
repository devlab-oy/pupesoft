<?php

	if (isset($_REQUEST["tee"])) {
		if($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
		if($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/","",$_REQUEST["kaunisnimi"]);
	}

	if (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == "NAYTATILAUS") $no_head = "yes";

	require("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("$pupe_root_polku/dataout/".basename($filenimi));
		exit;
	}

	if (!isset($tee) or $tee != "NAYTATILAUS") echo "<font class='head'>".t("Luo laskutusaineisto")."</font><hr>\n";

	if (isset($tee) and $tee == "pupevoice_siirto") {

		$ftphost = (isset($verkkohost_lah) and trim($verkkohost_lah) != '') ? $verkkohost_lah : "ftp.verkkolasku.net";
		$ftpuser = $yhtiorow['verkkotunnus_lah'];
		$ftppass = $yhtiorow['verkkosala_lah'];
		$ftppath = (isset($verkkopath_lah) and trim($verkkopath_lah) != '') ? $verkkopath_lah : "out/einvoice/data/";
		$ftpfile = $pupe_root_polku."/dataout/".basename($filenimi);

		$tulos_ulos = "";

		require("inc/ftp-send.inc");
	}

	if (isset($tee) and $tee == "apix_siirto") {

		#$url			= "https://test-api.apix.fi/invoices";
		$url			= "https://api.apix.fi/invoices";
		$transferkey	= $yhtiorow['verkkosala_lah'];
		$transferid		= $yhtiorow['verkkotunnus_lah'];
		$software		= "Pupesoft";
		$version		= "1.0";
		$timestamp		= gmdate("YmdHis");

		// Siirret‰‰n aineisto APIXiin
		$digest_src = $software."+".$version."+".$transferid."+".$timestamp."+".$transferkey;

		$dt = substr(hash('sha256', $digest_src), 0, 64);

		$real_url = "$url?soft=$software&ver=$version&TraID=$transferid&t=$timestamp&d=SHA-256:$dt";

		$apixfilesize = filesize("$pupe_root_polku/dataout/".basename($filenimi));
		$apix_fh = fopen("$pupe_root_polku/dataout/".basename($filenimi), 'r');

		$ch = curl_init($real_url);
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_INFILE, $apix_fh);
		curl_setopt($ch, CURLOPT_INFILESIZE, $apixfilesize);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		echo "L‰hetet‰‰n aineisto APIX:lle...<br>";
		$response = curl_exec($ch);

		curl_close($ch);
		fclose($apix_fh);

		$xml = simplexml_load_string($response);

		if ($xml->Status == "OK") {
			echo "L‰hetys onnistui!";
		}
		else {
			echo "L‰hetys ep‰onnistui:<br>";

			echo "Tila: ".$xml->Status."<br>";
			echo "Tilakoodi: ".$xml->StatusCode."<br>";

			foreach ($xml->FreeText as $teksti) {
				echo "Tilaviesti: ".$teksti."<br>";
			}
		}
	}

	if (isset($tee) and ($tee == "GENEROI" or $tee == "NAYTATILAUS") and $laskunumerot != '') {

		$tulostettavat_apix  = array();
		$lask = 0;

		if ($tee == "NAYTATILAUS") {
			$nosoap 	= "NOSOAP";
			$nosoapapix = "NOSOAP";
		}
		else {
			$nosoap 	= "";
			$nosoapapix = "NOSOAPAPIX";
		}

		if (!function_exists("vlas_dateconv")) {
			function vlas_dateconv ($date) {
				//k‰‰nt‰‰ mysqln vvvv-kk-mm muodon muotoon vvvvkkmm
				return substr($date,0,4).substr($date,5,2).substr($date,8,2);
			}
		}

		//tehd‰‰n viitteest‰ SPY standardia eli 20 merkki‰ etunollilla
		if (!function_exists("spyconv")) {
			function spyconv ($spy) {
				return $spy = sprintf("%020.020s",$spy);
			}
		}

		//pilkut pisteiksi
		if (!function_exists("pp")) {
			function pp ($muuttuja, $round="", $rmax="", $rmin="") {
				if(strlen($round)>0) {
					if(strlen($rmax)>0 and $rmax<$round) {
						$round = $rmax;
					}
					if(strlen($rmin)>0 and $rmin>$round) {
						$round = $rmin;
					}

					return $muuttuja = number_format($muuttuja, $round, ",", "");
				}
				else {
					return $muuttuja = str_replace(".",",",$muuttuja);
				}
			}
		}

		//Tiedostojen polut ja nimet
		//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		$nimixml = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".xml";

		//	Itellan iPost vaatii siirtoon v‰h‰n oman nimen..
		if ($yhtiorow["verkkolasku_lah"] == "iPost") {
			$nimiipost = "-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
			$nimifinvoice = "$pupe_root_polku/dataout/TRANSFER_IPOST".$nimiipost;
			$nimifinvoice_delivered = "DELIVERED_IPOST".$nimiipost;
		}
		elseif ($yhtiorow["verkkolasku_lah"] == "apix") {
			$nimifinvoice = "/tmp/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
		}
		else {
			$nimifinvoice = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
		}

		$nimisisainenfinvoice = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_sisainenfinvoice.xml";

		$nimiedi = "$pupe_root_polku/dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".edi";

		//Pupevoice xml-dataa
		if (!$tootxml = fopen($nimixml, "w")) die("Filen $nimixml luonti ep‰onnistui!");

		//Finvoice xml-dataa
		if (!$tootfinvoice = fopen($nimifinvoice, "w")) die("Filen $nimifinvoice luonti ep‰onnistui!");

		//Elma-EDI-inhouse dataa (EIH-1.4.0)
		if (!$tootedi = fopen($nimiedi, "w")) die("Filen $nimiedi luonti ep‰onnistui!");

		//Sis‰inenfinvoice xml-dataa
		if (!$tootsisainenfinvoice = fopen($nimisisainenfinvoice, "w")) die("Filen $nimisisainenfinvoice luonti ep‰onnistui!");

		// lock tables
		$query = "LOCK TABLES lasku WRITE, tilausrivi WRITE, tilausrivi as t2 WRITE, yhtio READ, tilausrivi as t3 READ, tilausrivin_lisatiedot READ, tilausrivin_lisatiedot as tl2 WRITE, tilausrivin_lisatiedot as tlt2 WRITE, tilausrivin_lisatiedot as tlt3 WRITE, sanakirja WRITE, tapahtuma WRITE, tuotepaikat WRITE, tiliointi WRITE, toimitustapa READ, maksuehto READ, sarjanumeroseuranta WRITE, tullinimike READ, kuka WRITE, varastopaikat READ, tuote READ, rahtikirjat READ, kirjoittimet READ, tuotteen_avainsanat READ, tuotteen_toimittajat READ, asiakas READ, rahtimaksut READ, avainsana READ, avainsana as a READ, avainsana as b READ, avainsana as avainsana_kieli READ, factoring READ, pankkiyhteystiedot READ, yhtion_toimipaikat READ, yhtion_parametrit READ, tuotteen_alv READ, maat READ, laskun_lisatiedot WRITE, kassalipas READ, kalenteri WRITE, etaisyydet READ, tilausrivi as t READ, asiakkaan_positio READ, yhteyshenkilo as kk READ, yhteyshenkilo as kt READ, asiakasalennus READ, tyomaarays READ, dynaaminen_puu AS node READ, dynaaminen_puu AS parent READ, puun_alkio READ, asiakaskommentti READ";
		$locre = mysql_query($query) or pupe_error($query);

		//Haetaan tarvittavat funktiot aineistojen tekoa varten
		require("verkkolasku_elmaedi.inc");
		require("verkkolasku_finvoice.inc");
		require("verkkolasku_pupevoice.inc");

		if (!isset($kieli)) {
			$kieli = "";
		}

		//Timestamppi EDI-failiin alkuu ja loppuun
		$timestamppi = gmdate("YmdHis");

		//Hetaan laskut jotka laitetaan aineistoon
		$query = "	SELECT *
		            from lasku
		            where yhtio = '$kukarow[yhtio]'
		            and tila    = 'U'
		            and alatila = 'X'
		            and laskunro in ($laskunumerot)";
		$res   = mysql_query($query) or pupe_error($query);

		$lkm = count(explode(',', $laskunumerot));

		if (!isset($tee) or $tee != "NAYTATILAUS") {
			echo "<br><font class='message'>".t("Syˆtit")." $lkm ".t("laskua").".</font><br>";
			echo "<font class='message'>".t("Aineistoon lis‰t‰‰n")." ".mysql_num_rows($res)." ".t("laskua").".</font><br><br>";
		}

		while ($lasrow = mysql_fetch_array($res)) {
			// Haetaan maksuehdon tiedot
			$query  = "	SELECT pankkiyhteystiedot.*, maksuehto.*
						FROM maksuehto
						LEFT JOIN pankkiyhteystiedot on (pankkiyhteystiedot.yhtio=maksuehto.yhtio and pankkiyhteystiedot.tunnus=maksuehto.pankkiyhteystiedot)
						WHERE maksuehto.yhtio='$kukarow[yhtio]' and maksuehto.tunnus='$lasrow[maksuehto]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				$masrow = array();

				if ($lasrow["erpcm"] == "0000-00-00") {
					echo "<font class='message'><br>\n".t("Maksuehtoa")." $lasrow[maksuehto] ".t("ei lˆydy!")." Tunnus $lasrow[tunnus] ".t("Laskunumero")." $lasrow[laskunro] ".t("ep‰onnistui pahasti")."!</font><br>\n<br>\n";
				}
			}
			else {
				$masrow = mysql_fetch_array($result);
			}

			//Haetaan factoringsopimuksen tiedot
			if ($masrow["factoring"] != '') {
				$query = "	SELECT *
							FROM factoring
							WHERE yhtio 		= '$kukarow[yhtio]'
							and factoringyhtio 	= '$masrow[factoring]'
							and valkoodi 		= '$lasrow[valkoodi]'";
				$fres = mysql_query($query) or pupe_error($query);
				$frow = mysql_fetch_array($fres);
			}
			else {
				unset($frow);
			}

			$pankkitiedot = array();

			//Laitetaan pankkiyhteystiedot kuntoon
			if ($masrow["factoring"] != "") {
				$pankkitiedot["pankkinimi1"]  =	$frow["pankkinimi1"];
				$pankkitiedot["pankkitili1"]  =	$frow["pankkitili1"];
				$pankkitiedot["pankkiiban1"]  =	$frow["pankkiiban1"];
				$pankkitiedot["pankkiswift1"] =	$frow["pankkiswift1"];
				$pankkitiedot["pankkinimi2"]  =	$frow["pankkinimi2"];
				$pankkitiedot["pankkitili2"]  =	$frow["pankkitili2"];
				$pankkitiedot["pankkiiban2"]  =	$frow["pankkiiban2"];
				$pankkitiedot["pankkiswift2"] = $frow["pankkiswift2"];
				$pankkitiedot["pankkinimi3"]  =	"";
				$pankkitiedot["pankkitili3"]  =	"";
				$pankkitiedot["pankkiiban3"]  =	"";
				$pankkitiedot["pankkiswift3"] =	"";

			}
			elseif ($masrow["pankkinimi1"] != "") {
				$pankkitiedot["pankkinimi1"]  =	$masrow["pankkinimi1"];
				$pankkitiedot["pankkitili1"]  =	$masrow["pankkitili1"];
				$pankkitiedot["pankkiiban1"]  =	$masrow["pankkiiban1"];
				$pankkitiedot["pankkiswift1"] =	$masrow["pankkiswift1"];
				$pankkitiedot["pankkinimi2"]  =	$masrow["pankkinimi2"];
				$pankkitiedot["pankkitili2"]  =	$masrow["pankkitili2"];
				$pankkitiedot["pankkiiban2"]  =	$masrow["pankkiiban2"];
				$pankkitiedot["pankkiswift2"] = $masrow["pankkiswift2"];
				$pankkitiedot["pankkinimi3"]  =	$masrow["pankkinimi3"];
				$pankkitiedot["pankkitili3"]  =	$masrow["pankkitili3"];
				$pankkitiedot["pankkiiban3"]  =	$masrow["pankkiiban3"];
				$pankkitiedot["pankkiswift3"] =	$masrow["pankkiswift3"];
			}
			else {
				$pankkitiedot["pankkinimi1"]  =	$yhtiorow["pankkinimi1"];
				$pankkitiedot["pankkitili1"]  =	$yhtiorow["pankkitili1"];
				$pankkitiedot["pankkiiban1"]  =	$yhtiorow["pankkiiban1"];
				$pankkitiedot["pankkiswift1"] =	$yhtiorow["pankkiswift1"];
				$pankkitiedot["pankkinimi2"]  =	$yhtiorow["pankkinimi2"];
				$pankkitiedot["pankkitili2"]  =	$yhtiorow["pankkitili2"];
				$pankkitiedot["pankkiiban2"]  =	$yhtiorow["pankkiiban2"];
				$pankkitiedot["pankkiswift2"] = $yhtiorow["pankkiswift2"];
				$pankkitiedot["pankkinimi3"]  =	$yhtiorow["pankkinimi3"];
				$pankkitiedot["pankkitili3"]  =	$yhtiorow["pankkitili3"];
				$pankkitiedot["pankkiiban3"]  =	$yhtiorow["pankkiiban3"];
				$pankkitiedot["pankkiswift3"] =	$yhtiorow["pankkiswift3"];
			}

			$asiakas_apu_query = "	SELECT *
									FROM asiakas
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus = '$lasrow[liitostunnus]'";
			$asiakas_apu_res = mysql_query($asiakas_apu_query) or pupe_error($asiakas_apu_query);

			if (mysql_num_rows($asiakas_apu_res) == 1) {
				$asiakas_apu_row = mysql_fetch_array($asiakas_apu_res);
			}
			else {
				$asiakas_apu_row = array();
			}

			if (strtoupper(trim($asiakas_apu_row["kieli"])) == "SE") {
				$laskun_kieli = "SE";
			}
			elseif (strtoupper(trim($asiakas_apu_row["kieli"])) == "EE") {
				$laskun_kieli = "EE";
			}
			elseif (strtoupper(trim($asiakas_apu_row["kieli"])) == "FI") {
				$laskun_kieli = "FI";
			}
			else {
				$laskun_kieli = trim(strtoupper($yhtiorow["kieli"]));
			}

			if ($kieli != "") {
				$laskun_kieli = trim(strtoupper($kieli));
			}

			// t‰ss‰ pohditaan laitetaanko verkkolaskuputkeen
			if (($lasrow["vienti"] == "" or ($lasrow["vienti"] == "E" and $lasrow["chn"] == "020")) and $masrow["itsetulostus"] == "" and $lasrow["sisainen"] == "" and $masrow["kateinen"] == ""  and $lasrow["chn"] != '666' and $lasrow["chn"] != '667' and abs($lasrow["summa"]) != 0) {

				// Nyt meill‰ on:
				// $lasrow array on U-laskun tiedot
				// $yhtiorow array on yhtion tiedot
				// $masrow array maksuehdon tiedot

				// Etsit‰‰n myyj‰n nimi
				$mquery  = "SELECT nimi, puhno, eposti
							FROM kuka
							WHERE tunnus='$lasrow[myyja]' and yhtio='$kukarow[yhtio]'";
				$myyresult = mysql_query($mquery) or pupe_error($mquery);
				$myyrow = mysql_fetch_array($myyresult);

				//HUOM: T‰ss‰ kaikki sallitut verkkopuolen chn:‰t
				if (!in_array($lasrow['chn'], array("100", "010", "001", "020", "111", "112"))) {
					//Paperi by default
					$lasrow['chn'] = "100";
				}

				if ($lasrow['chn'] == "020") {
					$lasrow['chn'] = "010";
				}

				 if ($lasrow['arvo'] >= 0) {
					//Veloituslasku
					$tyyppi='380';
				}
				else {
					//Hyvityslasku
					$tyyppi='381';
				}

				// Laskukohtaiset kommentit kuntoon
				// T‰m‰ merkki | eli pystyviiva on rivinvaihdon merkki laskun kommentissa elmalla
				$komm = "";

				// Onko k‰‰nteist‰ verotusta
				$alvquery = "   SELECT tunnus
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and uusiotunnus in ($lasrow[tunnus])
								and tyyppi  = 'L'
								and alv >= 600";
				$alvresult = mysql_query($alvquery) or pupe_error($alvquery);

				if (mysql_num_rows($alvresult) > 0) {
					$komm .= t_avainsana("KAANTALVVIESTI", $laskun_kieli, "", "", "", "selitetark");
				}

				if (trim($lasrow['tilausyhteyshenkilo']) != '') {
					$komm .= "\n".t("Tilaaja", $laskun_kieli).": ".$lasrow['tilausyhteyshenkilo'];
				}

				if (trim($lasrow['asiakkaan_tilausnumero']) != '') {
					$komm .= "\n".t("Tilauksenne", $laskun_kieli).": ".$lasrow['asiakkaan_tilausnumero'];
				}

				if (trim($lasrow['kohde']) != '') {
					$komm .= "\n".t("Kohde", $laskun_kieli).": ".$lasrow['kohde'];
				}

				if (trim($lasrow['sisviesti1']) != '') {
					$komm .= "\n".t("Kommentti", $laskun_kieli).": ".$lasrow['sisviesti1'];
				}

				if (trim($komm) != '') {
					$lasrow['sisviesti1'] = str_replace(array("\r\n","\r","\n"),"|", trim($komm));
				}

				// Hoidetaan pyˆristys sek‰ valuuttak‰sittely
				if ($lasrow["valkoodi"] != '' and trim(strtoupper($lasrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
					$lasrow["kasumma"] 	 = $lasrow["kasumma_valuutassa"];
					$lasrow["summa"] 	 = sprintf("%.2f", $lasrow["summa_valuutassa"] - $lasrow["pyoristys_valuutassa"]);
					$lasrow["arvo"]		 = $lasrow["arvo_valuutassa"];
					$lasrow["pyoristys"] = $lasrow["pyoristys_valuutassa"];
				}
				else {
					$lasrow["summa"] 	= sprintf("%.2f", $lasrow["summa"] - $lasrow["pyoristys"]);
				}

				// Ulkomaisen ytunnuksen korjaus
				if (substr(trim(strtoupper($lasrow["ytunnus"])),0,2) != strtoupper($lasrow["maa"]) and trim(strtoupper($lasrow["maa"])) != trim(strtoupper($yhtiorow["maa"]))) {
					$lasrow["ytunnus"] = strtoupper($lasrow["maa"])."-".$lasrow["ytunnus"];
				}

				if (strtoupper($laskun_kieli) != strtoupper($yhtiorow['kieli'])) {
					//K‰‰nnet‰‰n maksuehto
					$masrow["teksti"] = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $laskun_kieli);
				}

				$query = "	SELECT
							ifnull(min(date_format(if('$yhtiorow[tilausrivien_toimitettuaika]' = 'X', toimaika, if('$yhtiorow[tilausrivien_toimitettuaika]' = 'K' and keratty = 'saldoton', toimaika, toimitettuaika)), '%Y-%m-%d')), '0000-00-00') mint,
							ifnull(max(date_format(if('$yhtiorow[tilausrivien_toimitettuaika]' = 'X', toimaika, if('$yhtiorow[tilausrivien_toimitettuaika]' = 'K' and keratty = 'saldoton', toimaika, toimitettuaika)), '%Y-%m-%d')), '0000-00-00') maxt
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							and uusiotunnus = '$lasrow[tunnus]'
							and toimitettuaika != '0000-00-00 00:00:00'";
				$toimaikares = mysql_query($query) or pupe_error($query);
				$toimaikarow = mysql_fetch_array($toimaikares);

				if ($toimaikarow["mint"] == "0000-00-00") {
					$toimaikarow["mint"] = date("Y-m-d");
				}
				if ($toimaikarow["maxt"] == "0000-00-00") {
					$toimaikarow["maxt"] = date("Y-m-d");
				}

				//Kirjoitetaan failiin laskun otsikkotiedot
				if ($lasrow["chn"] == "111") {
					elmaedi_otsik($tootedi, $lasrow, $masrow, $tyyppi, $timestamppi, $toimaikarow);
				}
				elseif ($lasrow["chn"] == "112") {
					finvoice_otsik($tootsisainenfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, "", "", $nosoap);
				}
				elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice") {
					finvoice_otsik($tootfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, "", "", $nosoap);
				}
				elseif ($yhtiorow["verkkolasku_lah"] == "apix") {
					finvoice_otsik($tootfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, "", "", $nosoapapix);
				}
				else {
					pupevoice_otsik($tootxml, $lasrow, $laskun_kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow);
				}

				// Tarvitaan rivien eri verokannat
				$alvquery = "	SELECT distinct alv
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and uusiotunnus = '$lasrow[tunnus]'
								and tyyppi	= 'L'
								ORDER BY alv";
				$alvresult = mysql_query($alvquery) or pupe_error($alvquery);

				while ($alvrow1 = mysql_fetch_array($alvresult)) {

					if ($alvrow1["alv"] >= 500) {
						$aquery = "	SELECT '0' alv,
									round(sum(tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)),2) rivihinta,
									round(sum(0),2) alvrivihinta
									FROM tilausrivi
									JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
									WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]' and tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.alv = '$alvrow1[alv]' and tilausrivi.tyyppi = 'L'
									GROUP BY alv";
					}
					else {
						$aquery = "	SELECT tilausrivi.alv,
									round(sum(tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)),2) rivihinta,
									round(sum((tilausrivi.rivihinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*(tilausrivi.alv/100)),2) alvrivihinta
									FROM tilausrivi
									JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
									WHERE tilausrivi.uusiotunnus = '$lasrow[tunnus]' and tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.alv = '$alvrow1[alv]' and tilausrivi.tyyppi = 'L'
									GROUP BY alv";
					}
					$aresult = mysql_query($aquery) or pupe_error($aquery);
					$alvrow = mysql_fetch_array($aresult);

					// Kirjotetaan failiin arvierittelyt
					if ($lasrow["chn"] == "111") {
						elmaedi_alvierittely($tootedi, $alvrow);
					}
					elseif ($lasrow["chn"] == "112") {
						finvoice_alvierittely($tootsisainenfinvoice, $lasrow, $alvrow);
					}
					elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice" or $yhtiorow["verkkolasku_lah"] == "apix") {
						finvoice_alvierittely($tootfinvoice, $lasrow, $alvrow);
					}
					else {
						pupevoice_alvierittely($tootxml, $alvrow);
					}
				}

				//Kirjoitetaan otsikkojen lopputiedot
				if ($lasrow["chn"] == "111") {
					elmaedi_otsikko_loput($tootedi, $lasrow);
				}
				elseif ($lasrow["chn"] == "112") {
					finvoice_otsikko_loput($tootsisainenfinvoice, $lasrow, $masrow);
				}
				elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice" or $yhtiorow["verkkolasku_lah"] == "apix") {
					finvoice_otsikko_loput($tootfinvoice, $lasrow, $masrow);
				}

				// katotaan miten halutaan sortattavan
				// haetaan asiakkaan tietojen takaa sorttaustiedot
				$order_sorttaus = '';

				$asiakas_apu_query = "	SELECT laskun_jarjestys, laskun_jarjestys_suunta, laskutyyppi
										FROM asiakas
										WHERE yhtio = '$kukarow[yhtio]'
										and tunnus = '$lasrow[liitostunnus]'";
				$asiakas_apu_res = mysql_query($asiakas_apu_query) or pupe_error($asiakas_apu_query);

				if (mysql_num_rows($asiakas_apu_res) == 1) {
					$asiakas_apu_row = mysql_fetch_array($asiakas_apu_res);
					$sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["laskun_jarjestys"] != "" ? $asiakas_apu_row["laskun_jarjestys"] : $yhtiorow["laskun_jarjestys"]);
					$order_sorttaus = $asiakas_apu_row["laskun_jarjestys_suunta"] != "" ? $asiakas_apu_row["laskun_jarjestys_suunta"] : $yhtiorow["laskun_jarjestys_suunta"];
				}
				else {
					$sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);
					$order_sorttaus = $yhtiorow["laskun_jarjestys_suunta"];
				}

				// Asiakkaan / yhtiˆn laskutyyppi
				if (isset($asiakas_apu_row['laskutyyppi']) and $asiakas_apu_row['laskutyyppi'] != -9) {
					$laskutyyppi = $asiakas_apu_row['laskutyyppi'];
				}
				else {
					$laskutyyppi = $yhtiorow['laskutyyppi'];
				}

				if ($yhtiorow["laskun_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
				else $pjat_sortlisa = "";

				$query_ale_lisa = generoi_alekentta('M');

				// Haetaan laskun kaikki rivit
				$query = "  SELECT
							if(tilausrivi.perheid > 0, ifnull((SELECT vanha_otunnus from tilausrivin_lisatiedot t_lisa where t_lisa.yhtio=tilausrivi.yhtio and t_lisa.tilausrivitunnus=tilausrivi.perheid and t_lisa.omalle_tilaukselle != ''), tilausrivi.tunnus), tilausrivi.tunnus) rivigroup,
							tilausrivi.ale1,
							tilausrivi.ale2,
							tilausrivi.ale3,
							tilausrivi.alv,
							tuote.eankoodi,
							tuote.ei_saldoa,
							tilausrivi.erikoisale,
							tilausrivi.nimitys,
							tilausrivin_lisatiedot.osto_vai_hyvitys,
							tuote.sarjanumeroseuranta,
							tilausrivi.tuoteno,
							tilausrivi.uusiotunnus,
							tilausrivi.yksikko,
							tilausrivi.hinta,
							tilausrivi.netto,
							lasku.vienti_kurssi,
							lasku.viesti laskuviesti,
							lasku.asiakkaan_tilausnumero,
							if (tuote.tuotetyyppi = 'K','2 Tyˆt','1 Muut') tuotetyyppi,
							if (tilausrivi.var2 = 'EIOST', 'EIOST', '') var2,
							if (tuote.myyntihinta_maara = 0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
							min(tilausrivi.hyllyalue) hyllyalue,
							min(tilausrivi.hyllynro) hyllynro,
							min(tilausrivi.keratty) keratty,
							min(if (tilausrivi.toimaika = '0000-00-00', date_format(now(), '%Y-%m-%d'), tilausrivi.toimaika)) toimaika,
							min(if (date_format(tilausrivi.toimitettuaika, '%Y-%m-%d') = '0000-00-00', date_format(now(), '%Y-%m-%d'), date_format(tilausrivi.toimitettuaika, '%Y-%m-%d'))) toimitettuaika,
							min(tilausrivi.otunnus) otunnus,
							min(tilausrivi.perheid) perheid,
							min(tilausrivi.tunnus) tunnus,
							min(tilausrivi.kommentti) kommentti,
							min(tilausrivi.tilaajanrivinro) tilaajanrivinro,
							min(tilausrivi.laadittu) laadittu,
							sum(tilausrivi.tilkpl) tilkpl,
							sum((tilausrivi.hinta / {$lasrow["vienti_kurssi"]}) / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}) rivihinta_valuutassa,
							group_concat(tilausrivi.tunnus) rivitunnukset,
							count(*) rivigroup_maara,
							sum(tilausrivi.rivihinta) rivihinta,
							sum(tilausrivi.kpl) kpl,
							$sorttauskentta
							FROM tilausrivi
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
							JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
							WHERE tilausrivi.yhtio 		= '$kukarow[yhtio]'
							and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
							and tilausrivi.kpl != 0
							and tilausrivi.uusiotunnus 	= '$lasrow[tunnus]'
							GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22
							ORDER BY tilausrivi.otunnus, $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
				$tilres = mysql_query($query) or pupe_error($query);

				$rivinumerot = array(0 => 0);
				$rivilaskuri = 1;
				$rivimaara   = mysql_num_rows($tilres);
				$rivigrouppaus 	= FALSE;

				while ($tilrow = mysql_fetch_array($tilres)) {

					if (strtolower($laskun_kieli) != strtolower($yhtiorow['kieli'])) {
						//K‰‰nnet‰‰n nimitys
						$tilrow['nimitys'] = t_tuotteen_avainsanat($tilrow, 'nimitys', $laskun_kieli);
					}

					// Rivin toimitusaika
					if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K' and $tilrow["keratty"] == "saldoton") {
						$tilrow["toimitettuaika"] = $tilrow["toimaika"];
					}
					elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
						$tilrow["toimitettuaika"] = $tilrow["toimaika"];
					}
					else {
						$tilrow["toimitettuaika"] = $tilrow["toimitettuaika"];
					}

					if ($row["rivigroup_maara"] > 1 and !$rivigrouppaus) {
						$rivigrouppaus = TRUE;
					}

					// Otetaan yhteens‰kommentti pois jos summataan rivej‰
					if ($rivigrouppaus) {
						$tilrow["kommentti"] = preg_replace("/ ".t("yhteens‰", $kieli).": [0-9\.]* [A-Z]{3}\./", "", $tilrow["kommentti"]);
						$tilrow["kommentti"] = preg_replace("/ ".t("yhteens‰").": [0-9\.]* [A-Z]{3}\./", "", $tilrow["kommentti"]);
						$tilrow["kommentti"] = preg_replace("/ "."yhteens‰".": [0-9\.]* [A-Z]{3}\./", "", $tilrow["kommentti"]);
					}

					// Laitetaan alennukset kommenttiin, koska laksulla on vain yksi alekentt‰
					if ($yhtiorow['myynnin_alekentat'] > 1) {

						$alekomm = "";

						for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
							if (trim($tilrow["ale{$alepostfix}"]) > 0) {
								$alekomm .= t("Ale")."{$alepostfix} ".($tilrow["ale{$alepostfix}"]*1)." %|";
							}
						}

						if ($tilrow['erikoisale'] > 0) {
							$alekomm .= t("Erikoisale")." ".($tilrow["erikoisale"]*1)." %|";
						}

						$tilrow["kommentti"] = $alekomm.$tilrow["kommentti"];
					}

					//K‰‰nteinen arvonlis‰verovelvollisuus ja K‰ytetyn tavaran myynti
					if ($tilrow["alv"] >= 600) {
						$tilrow["alv"] = 0;
						$tilrow["kommentti"] .= " Ei lis‰tty‰ arvonlis‰veroa, ostajan k‰‰nnetty verovelvollisuus.";
					}
					elseif ($tilrow["alv"] >= 500) {
						$tilrow["alv"] = 0;
						$tilrow["kommentti"] .= " Ei sis‰ll‰ v‰hennett‰v‰‰ veroa.";
					}

					//Hetaan sarjanumeron tiedot
					if ($tilrow["kpl"] > 0) {
						$sarjanutunnus = "myyntirivitunnus";
					}
					else {
						$sarjanutunnus = "ostorivitunnus";
					}

					$query = "	SELECT *
								FROM sarjanumeroseuranta
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$tilrow[tuoteno]'
								and $sarjanutunnus in ($tilrow[rivitunnukset])
								and sarjanumero != ''";
					$sarjares = mysql_query($query) or pupe_error($query);

					if ($tilrow["kommentti"] != '' and mysql_num_rows($sarjares) > 0) {
						$tilrow["kommentti"] .= " ";
					}
					while ($sarjarow = mysql_fetch_array($sarjares)) {
						$tilrow["kommentti"] .= "S:nro: $sarjarow[sarjanumero] ";
					}

					if ($laskutyyppi == "7") {

						if ($tilrow["eankoodi"] != "") {
							$tilrow["kommentti"] = "EAN: $tilrow[eankoodi]|$tilrow[kommentti]";
						}

						$query = "	SELECT kommentti
									FROM asiakaskommentti
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tuoteno = '{$tilrow['tuoteno']}'
									AND ytunnus = '{$lasrow['ytunnus']}'
									ORDER BY tunnus";
						$asiakaskommentti_res = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($asiakaskommentti_res) > 0) {
							while ($asiakaskommentti_row = mysql_fetch_assoc($asiakaskommentti_res)) {
								$tilrow["kommentti"] .= "|".$asiakaskommentti_row['kommentti'];
							}
						}
					}

					if ($lasrow["valkoodi"] != '' and trim(strtoupper($lasrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
						// Veroton rivihinta valuutassa
						$tilrow["rivihinta"] = $tilrow["rivihinta_valuutassa"];

						// Yksikkˆhinta valuutassa
						$tilrow["hinta"] = laskuval($tilrow["hinta"], $tilrow["vienti_kurssi"]);
					}

					// Verollinen Rivihinta. Lasketaan saman kaavan mukaan kuin laskutus.inc:ss‰, eli pyˆristet‰‰n kaikki kerralla lopuksi!
					$totalvat = $tilrow["hinta"] * generoi_alekentta_php($tilrow, 'M', 'kerto') * $tilrow["kpl"];

					if ($yhtiorow["alv_kasittely"] != '') {
						$totalvat = $totalvat * (1 + ($tilrow["alv"] / 100));
					}

					// Yksikkˆhinta on laskulla aina veroton
					if ($yhtiorow["alv_kasittely"] == '') {
						$tilrow["hinta"] = $tilrow["hinta"] / (1 + $tilrow["alv"] / 100);
					}

					// Veron m‰‰r‰
					$vatamount = $tilrow['rivihinta'] * $tilrow['alv'] / 100;

					// Pyˆristet‰‰n ja formatoidaan lopuksi
					$tilrow["hinta"] 	 = hintapyoristys($tilrow["hinta"]);
					$tilrow["rivihinta"] = hintapyoristys($tilrow["rivihinta"]);
					$totalvat			 = hintapyoristys($totalvat);
					$vatamount 			 = hintapyoristys($vatamount);

					$tilrow['kommentti'] = preg_replace("/[^A-Za-z0-9÷ˆƒ‰≈Â ".preg_quote(".,-/!+()%#|:", "/")."]/", " ", $tilrow['kommentti']);
					$tilrow['nimitys'] 	 = preg_replace("/[^A-Za-z0-9÷ˆƒ‰≈Â ".preg_quote(".,-/!+()%#|:", "/")."]/", " ", $tilrow['nimitys']);

					// Otetaan seuraavan rivin otunnus
					if ($rivilaskuri < $rivimaara) {
						$tilrow_seuraava = mysql_fetch_assoc($tilres);
						mysql_data_seek($tilres, $rivilaskuri);
						$tilrow['seuraava_otunnus'] = $tilrow_seuraava["otunnus"];
					}
					else {
						$tilrow['seuraava_otunnus'] = 0;
					}

					if ($lasrow["chn"] == "111") {

						if ((int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6) > 0 and !in_array((int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6), $rivinumerot)) {
							$rivinumero	= (int) substr(sprintf("%06s", $tilrow["tilaajanrivinro"]), -6);
						}
						else {
							$rivinumero = (int) substr(sprintf("%06s", $tilrow["tunnus"]), -6);
						}

						elmaedi_rivi($tootedi, $tilrow, $rivinumero);
					}
					elseif ($lasrow["chn"] == "112") {
						finvoice_rivi($tootsisainenfinvoice, $tilrow, $lasrow, $vatamount, $totalvat);
					}
					elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice" or $yhtiorow["verkkolasku_lah"] == "apix") {
						finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $totalvat);
					}
					else {
						pupevoice_rivi($tootxml, $tilrow, $vatamount, $totalvat);
					}

					$rivilaskuri++;
				}

				// Lopetetaan lasku
				if ($lasrow["chn"] == "111") {
					elmaedi_lasku_loppu($tootedi, $lasrow);

					$edilask++;
				}
				elseif ($lasrow["chn"] == "112") {
					finvoice_lasku_loppu($tootsisainenfinvoice, $lasrow, $pankkitiedot, $masrow);
				}
				elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice" or $yhtiorow["verkkolasku_lah"] == "apix") {
					finvoice_lasku_loppu($tootfinvoice, $lasrow, $pankkitiedot, $masrow);

					if ($yhtiorow["verkkolasku_lah"] == "apix") {
						$tulostettavat_apix[] = $lasrow["tunnus"];
					}
				}
				else {
					pupevoice_lasku_loppu($tootxml);
				}

				$lask++;
			}
			else {
				echo "\n".t("T‰m‰ lasku ei mene verkkolaskuoperaattorille")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
			}
		}

		//Aineistojen lopput‰git
		elmaedi_aineisto_loppu($tootedi, $timestamppi);
		pupevoice_aineisto_loppu($tootxml);

		// suljetaan faili
		fclose($tootxml);
		fclose($tootedi);
		fclose($tootfinvoice);
		fclose($tootsisainenfinvoice);

		//dellataan failit jos ne on tyhji‰
		if (filesize($nimixml) == 0) {
			unlink($nimixml);
		}
		if (filesize($nimifinvoice) == 0) {
			unlink($nimifinvoice);
		}
		if (filesize($nimiedi) == 0) {
			unlink($nimiedi);
		}
		if (filesize($nimisisainenfinvoice) == 0) {
			unlink($nimisisainenfinvoice);
		}

		// poistetaan lukot
		$query = "UNLOCK TABLES";
		$locre = mysql_query($query) or pupe_error($query);

		if (count($tulostettavat_apix) > 0) {
			require_once("tilauskasittely/tulosta_lasku.inc");
		}

		if ($tee == "NAYTATILAUS") {
			header("Content-type: text/xml");
			header("Content-length: ".(filesize($nimifinvoice)));
			header("Content-Disposition: inline; filename=$nimifinvoice");
			header("Content-Description: Lasku");

			readfile($nimifinvoice);
		}
		else {
			if (file_exists(realpath($nimixml))) {
				//siirretaan laskutiedosto operaattorille
				$ftphost = (isset($verkkohost_lah) and trim($verkkohost_lah) != '') ? $verkkohost_lah : "ftp.verkkolasku.net";
				$ftpuser = $yhtiorow['verkkotunnus_lah'];
				$ftppass = $yhtiorow['verkkosala_lah'];
				$ftppath = (isset($verkkopath_lah) and trim($verkkopath_lah) != '') ? $verkkopath_lah : "out/einvoice/data/";
				$ftpfile = realpath($nimixml);

				// t‰t‰ ei ajata eik‰ k‰ytet‰, mutta jos tulee ftp errori niin echotaan t‰‰ meiliin, niin ei tartte k‰sin kirjotella resendi‰
				echo "<pre>ncftpput -u $ftpuser -p $ftppass $ftphost $ftppath $ftpfile</pre>";

				echo "<table>";
				echo "<tr><th>".t("Tallenna pupevoice-aineisto").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='".basename($nimixml)."'>";
				echo "<input type='hidden' name='filenimi' value='".basename($nimixml)."'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table><br><br>";

				echo "<table>";
				echo "<tr><th>".t("L‰het‰ pupevoice-aineisto uudestaan Itellaan").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='pupevoice_siirto'>";
				echo "<input type='hidden' name='filenimi' value='".basename($nimixml)."'>";
				echo "<td class='back'><input type='submit' value='".t("L‰het‰")."'></td></tr></form>";
				echo "</table>";
			}
			elseif ($yhtiorow["verkkolasku_lah"] == "finvoice" and file_exists(realpath($nimifinvoice))) {
				//siirretaan laskutiedosto operaattorille
				$ftphost = "ftp.itella.net";
				$ftpuser = $yhtiorow['verkkotunnus_lah'];
				$ftppass = $yhtiorow['verkkosala_lah'];
				$ftppath = "out/finvoice/data/";
				$ftpfile = realpath($nimifinvoice);
				$renameftpfile = $nimifinvoice_delivered;

				// t‰t‰ ei ajata eik‰ k‰ytet‰, mutta jos tulee ftp errori niin echotaan t‰‰ meiliin, niin ei tartte k‰sin kirjotella resendi‰
				echo "<pre>mv $ftpfile ".str_replace("TRANSFER_", "DELIVERED_", $ftpfile)."\nncftpput -u $ftpuser -p $ftppass -T T $ftphost $ftppath ".str_replace("TRANSFER_", "DELIVERED_", $ftpfile)."</pre>";

				echo "<table>";
				echo "<tr><th>".t("Tallenna finvoice-aineisto").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
				echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table>";
			}
			elseif ($yhtiorow["verkkolasku_lah"] == "apix" and file_exists(realpath($nimifinvoice))) {

				$timestamp		= gmdate("YmdHis");
				$apixfinvoice 	= basename($nimifinvoice);
				$apixzipfile	= "Apix_".$yhtiorow['yhtio']."_invoices_$timestamp.zip";

				// Luodaan temppidirikka jonne tyˆnnet‰‰n t‰n kiekan kaikki apixfilet
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$apix_tmpdirnimi = "/tmp/apix-".md5(uniqid(mt_rand(), true));

				if (mkdir($apix_tmpdirnimi)) {

					// Siirret‰‰n finvoiceaineisto dirikkaan
					if (!rename("/tmp/".$apixfinvoice, $apix_tmpdirnimi."/".$apixfinvoice)) {
						echo "APIX finvoicemove $apixfinvoice feilas!";
					}

					// Luodaan laskupdf:‰t
					foreach ($tulostettavat_apix as $apixlasku) {
						list($apixnumero, $apixtmpfile) = tulosta_lasku($apixlasku, $kieli, "VERKKOLASKU_APIX", "", "", "");

						// Siirret‰‰n faili apixtemppiin
						if (!rename($apixtmpfile, $apix_tmpdirnimi."/Apix_invoice_$apixnumero.pdf")) {
							echo "APIX tmpmove Apix_invoice_$apixnumero.pdf feilas!";
						}
					}

					// Tehd‰‰n apixzippi
					exec("cd $apix_tmpdirnimi; zip $apixzipfile *;");

					// Aineisto dataouttiin
					exec("cp $apix_tmpdirnimi/$apixzipfile $pupe_root_polku/dataout/");

					// Poistetaan apix-tmpdir
					exec("rm -rf $apix_tmpdirnimi");

					echo "<table>";
					echo "<tr><th>".t("Tallenna apix-aineisto").":</th>";
					echo "<form method='post' action='$PHP_SELF'>";
					echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
					echo "<input type='hidden' name='kaunisnimi' value='".basename($apixzipfile)."'>";
					echo "<input type='hidden' name='filenimi' value='".basename($apixzipfile)."'>";
					echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
					echo "</table><br><br>";

					echo "<table>";
					echo "<tr><th>".t("L‰het‰ aineisto uudestaan APIX:lle").":</th>";
					echo "<form method='post' action='$PHP_SELF'>";
					echo "<input type='hidden' name='tee' value='apix_siirto'>";
					echo "<input type='hidden' name='filenimi' value='".basename($apixzipfile)."'>";
					echo "<td class='back'><input type='submit' value='".t("L‰het‰")."'></td></tr></form>";
					echo "</table>";
				}
				else {
					echo "APIX tmpdirrin teko feilas!<br>";
				}
			}
			elseif ($yhtiorow["verkkolasku_lah"] == "iPost" and file_exists(realpath($nimifinvoice))) {
				//siirretaan laskutiedosto operaattorille
				$ftphost = "ftp.itella.net";
				$ftpuser = $yhtiorow['verkkotunnus_lah'];
				$ftppass = $yhtiorow['verkkosala_lah'];
				$ftppath = "out/finvoice/data/";
				$ftpfile = realpath($nimifinvoice);
				$renameftpfile = $nimifinvoice_delivered;

				// t‰t‰ ei ajata eik‰ k‰ytet‰, mutta jos tulee ftp errori niin echotaan t‰‰ meiliin, niin ei tartte k‰sin kirjotella resendi‰
				echo "<pre>mv $ftpfile ".str_replace("TRANSFER_", "DELIVERED_", $ftpfile)."\nncftpput -u $ftpuser -p $ftppass -T T $ftphost $ftppath ".str_replace("TRANSFER_", "DELIVERED_", $ftpfile)."</pre>";

				echo "<table>";
				echo "<tr><th>".t("Tallenna finvoice-aineisto").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='".basename($nimifinvoice)."'>";
				echo "<input type='hidden' name='filenimi' value='".basename($nimifinvoice)."'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table>";
			}

			if (file_exists(realpath($nimiedi))) {
				echo "<table>";
				echo "<tr><th>".t("Tallenna Elmaedi-aineisto").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='".basename($nimiedi)."'>";
				echo "<input type='hidden' name='filenimi' value='".basename($nimiedi)."'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table>";
			}

			if (file_exists(realpath($nimisisainenfinvoice))) {
				echo "<table>";
				echo "<tr><th>".t("Tallenna Pupesoft-Finvoice-aineisto").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='".basename($nimisisainenfinvoice)."'>";
				echo "<input type='hidden' name='filenimi' value='".basename($nimisisainenfinvoice)."'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table>";
			}
		}

		// poistetaan lukot
		$query = "UNLOCK TABLES";
		$locre = mysql_query($query) or pupe_error($query);
	}

	if (!isset($tee) or $tee == "") {
		echo "<font class='message'>".t("Anna laskunumerot, pilkulla eroteltuina, joista aineisto muodostetaan:")."</font><br>";
		echo "<form method='post'>";
		echo "<input type='hidden' name='tee' value='GENEROI'>";
		echo "<textarea name='laskunumerot' rows='10' cols='60'></textarea>";
		echo "<input type='submit' value='Luo aineisto'>";
		echo "</form>";
	}

	if (!isset($tee) or $tee != "NAYTATILAUS") require("inc/footer.inc");

?>