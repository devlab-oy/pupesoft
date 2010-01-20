<?php

	if (isset($_POST["tee"]) and $_POST["tee"] == 'lataa_tiedosto') {
		$lataa_tiedosto = 1;
		$file = file_get_contents($_POST["file"]);
		unset($_POST["file"]);

		if(isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
			$_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
		}
	}

	require("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		echo "$file";
	}
	else {

		echo "<font class='head'>".t("Luo laskutusaineisto")."</font><hr>\n";

		if (isset($tee) and $tee == "GENEROI" and $laskunumerot!='') {
			if (!function_exists("xml_add")) {
				function xml_add ($joukko, $tieto, $handle) {
					global $yhtiorow, $lasrow;

					$ulos = "<$joukko>";

					if (strlen($tieto) > 0) {
						//K‰sitell‰‰n xml-entiteetit
						$serc = array("&", ">", "<", "'", "\"", "¥", "`");
						$repl = array("&amp;", "&gt;", "&lt;", "&apos;", "&quot;", " ", " ");
						$tieto = str_replace($serc, $repl, $tieto);

						$ulos .= trim($tieto);
					}

					$pos = strpos($joukko, " ");

					if ($pos === FALSE) {
						$ulos .= "</$joukko>";
		            }
		            else {
						$ulos .= "</".substr($joukko,0,$pos).">";
		            }

					if ($lasrow["chn"] == "112" or $yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice") {
						//	Jos tehd‰‰n finvoicea rivinvaihto on \r\n
						$ulos .= "\r\n";
					}
					else {
						$ulos .= "\n";
					}

					fputs($handle, $ulos);
				}
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

			$today = date("w") + 1; // mik‰ viikonp‰iv‰ t‰n‰‰n on 1-7.. 1=sunnuntai, 2=maanantai, jne...

			//Tiedostojen polut ja nimet
			//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
			$nimixml = "../dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".xml";
			$nimi_filexml = "laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".xml";

			if($yhtiorow["verkkolasku_lah"] == "iPost") {
				$nimifinvoice = "../dataout/TRANSFER_IPOST-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
				$nimi_filefinvoice = "TRANSFER_IPOST-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
				$nimi_filefinvoice_siirto_valmis = "DELIVERED_IPOST-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
			}
			else {
				$nimifinvoice = "../dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
				$nimi_filefinvoice = "laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
			}

			$nimiedi = "../dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".edi";
			$nimi_fileedi = "laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".edi";

			//Pupevoice xml-dataa
			if (!$tootxml = fopen($nimixml, "w")) die("Filen $nimixml luonti ep‰onnistui!");

			//Finvoice xml-dataa
			if (!$tootfinvoice = fopen($nimifinvoice, "w")) die("Filen $nimifinvoice luonti ep‰onnistui!");

			//Elma-EDI-inhouse dataa (EIH-1.4.0)
			if (!$tootedi = fopen($nimiedi, "w")) die("Filen $nimiedi luonti ep‰onnistui!");

			// lock tables
			$query = "LOCK TABLES lasku WRITE, tilausrivi WRITE, tilausrivi as t2 WRITE, yhtio READ, tilausrivi as t3 READ, tilausrivin_lisatiedot READ, tilausrivin_lisatiedot as tl2 WRITE, sanakirja WRITE, tapahtuma WRITE, tuotepaikat WRITE, tiliointi WRITE, toimitustapa READ, maksuehto READ, sarjanumeroseuranta WRITE, tullinimike READ, kuka WRITE, varastopaikat READ, tuote READ, rahtikirjat READ, kirjoittimet READ, tuotteen_avainsanat READ, tuotteen_toimittajat READ, asiakas READ, rahtimaksut READ, avainsana READ, avainsana as a READ, avainsana as b READ, avainsana as avainsana_kieli READ, factoring READ, pankkiyhteystiedot READ, yhtion_toimipaikat READ, yhtion_parametrit READ, tuotteen_alv READ, maat READ, laskun_lisatiedot WRITE, kassalipas READ, kalenteri WRITE, etaisyydet READ, tilausrivi as t READ, asiakkaan_positio READ, yhteyshenkilo as kk READ, yhteyshenkilo as kt READ";
			$locre = mysql_query($query) or pupe_error($query);

			//Haetaan tarvittavat funktiot aineistojen tekoa varten
			require("verkkolasku_elmaedi.inc");
			require("verkkolasku_finvoice.inc");
			require("verkkolasku_pupevoice.inc");

			if (!isset($kieli)) {
				$kieli = "";
			}

			//Timestamppi EDI-failiin alkuu ja loppuun
			$timestamppi=gmdate("YmdHis");

			//Hetaan laskut jotka laitetaan aineistoon
			$query = "	SELECT *
			            from lasku
			            where yhtio = '$kukarow[yhtio]'
			            and tila    = 'U'
			            and alatila = 'X'
			            and laskunro in ($laskunumerot)";
			$res   = mysql_query($query) or pupe_error($query);

			$lkm = count(explode(',', $laskunumerot));

			echo "<br><font class='message'>".t("Syˆtit")." $lkm ".t("laskua").".</font><br>";
			echo "<font class='message'>".t("Aineistoon lis‰t‰‰n")." ".mysql_num_rows($res)." ".t("laskua").".</font><br><br>";

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

					if (trim($lasrow['tilausyhteyshenkilo']) != '') {
						$komm .= "\n".t("Tilaaja").": ".$lasrow['tilausyhteyshenkilo'];
					}

					if (trim($lasrow['asiakkaan_tilausnumero']) != '') {
						$komm .= "\n".t("Tilauksenne").": ".$lasrow['asiakkaan_tilausnumero'];
					}

					if (trim($lasrow['kohde']) != '') {
						$komm .= "\n".t("Kohde").": ".$lasrow['kohde'];
					}

					if (trim($lasrow['sisviesti1']) != '') {
						$komm .= "\n".t("Kommentti").": ".$lasrow['sisviesti1'];
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

					$query = "	SELECT min(date_format(toimitettuaika, '%Y-%m-%d')) mint, max(date_format(toimitettuaika, '%Y-%m-%d')) maxt
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
						finvoice_otsik($tootsisainenfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent);
					}
					elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice") {
						finvoice_otsik($tootfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent);
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
							$aquery = "	SELECT tilausrivi.alv,
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
						elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice") {
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
					elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice") {
						finvoice_otsikko_loput($tootfinvoice, $lasrow, $masrow);
					}

					// katotaan miten halutaan sortattavan
					// haetaan asiakkaan tietojen takaa sorttaustiedot
					$order_sorttaus = '';

					$asiakas_apu_query = "	SELECT laskun_jarjestys, laskun_jarjestys_suunta
											FROM asiakas
											WHERE yhtio = '$kukarow[yhtio]'
											and tunnus = '$lasrow[liitostunnus]'";
					$asiakas_apu_res = mysql_query($asiakas_apu_query) or pupe_error($asiakas_apu_query);

					if (mysql_num_rows($asiakas_apu_res) == 1) {
						$asiakas_apu_row = mysql_fetch_array($asiakas_apu_res);
						$sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["laskun_jarjestys"]);
						$order_sorttaus = $asiakas_apu_row["laskun_jarjestys_suunta"];
					}
					else {
						$sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);
						$order_sorttaus = $yhtiorow["laskun_jarjestys_suunta"];
					}

					// Kirjoitetaan rivitietoja tilausriveilt‰
					$query = "	SELECT tilausrivi.*, lasku.vienti_kurssi,
								if (date_format(tilausrivi.toimitettuaika, '%Y-%m-%d') = '0000-00-00', date_format(now(), '%Y-%m-%d'), date_format(tilausrivi.toimitettuaika, '%Y-%m-%d')) toimitettuaika,
								if (tilausrivi.toimaika = '0000-00-00', date_format(now(), '%Y-%m-%d'), tilausrivi.toimaika) toimaika,
								$sorttauskentta
								FROM tilausrivi
								JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.uusiotunnus = '$lasrow[tunnus]'
								and tilausrivi.kpl <> 0
								and tilausrivi.tyyppi = 'L'
								ORDER BY otunnus, sorttauskentta $order_sorttaus, tilausrivi.tunnus";
					$tilres = mysql_query($query) or pupe_error($query);

					$rivinumerot = array(0 => 0);

					while ($tilrow = mysql_fetch_array($tilres)) {

						if (strtolower($laskun_kieli) != strtolower($yhtiorow['kieli'])) {
							//K‰‰nnet‰‰n nimitys
							$tilrow['nimitys'] = t_tuotteen_avainsanat($tilrow, 'nimitys', $laskun_kieli);
						}

						// Palvelutuotteiden toimitettuaika syˆtet‰‰n k‰sin
						if ($tilrow["keratty"] == "saldoton" and $yhtiorow["saldottomien_toimitettuaika"] == "K") {
							$tilrow['toimitettuaika'] = $tilrow['toimaika'];
						}

						//K‰ytetyn tavaran myynti
						if ($tilrow["alv"] >= 500) {
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
									and $sarjanutunnus='$tilrow[tunnus]'
									and sarjanumero != ''";
						$sarjares = mysql_query($query) or pupe_error($query);

						if ($tilrow["kommentti"] != '' and mysql_num_rows($sarjares) > 0) {
							$tilrow["kommentti"] .= " ";
						}
						while ($sarjarow = mysql_fetch_array($sarjares)) {
							$tilrow["kommentti"] .= "S:nro: $sarjarow[sarjanumero] ";
						}

						if ($lasrow["valkoodi"] != '' and trim(strtoupper($lasrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
							// Rivihinta
							if ($yhtiorow["alv_kasittely"] == '') {
								$tilrow["rivihinta"] = round(laskuval($tilrow["hinta"], $tilrow["vienti_kurssi"])*$tilrow["kpl"]*(1-$tilrow["ale"]/100) / (1+$tilrow["alv"]/100), 2);
							}
							else {
								$tilrow["rivihinta"] = round(laskuval($tilrow["hinta"], $tilrow["vienti_kurssi"])*$tilrow["kpl"]*(1-$tilrow["ale"]/100), 2);
							}
							// Yksikkˆhinta
							$tilrow["hinta"] = round(laskuval($tilrow["hinta"], $tilrow["vienti_kurssi"]), 2);
						}

						$vatamount = round($tilrow['rivihinta']*$tilrow['alv']/100, 2);
						$totalvat  = round($tilrow['rivihinta']+$vatamount, 2);

						$tilrow['kommentti'] = preg_replace("/[^A-Za-z0-9÷ˆƒ‰≈Â ".preg_quote(".,-/!+()%#", "/")."]/", " ", $tilrow['kommentti']);
						$tilrow['nimitys'] 	 = preg_replace("/[^A-Za-z0-9÷ˆƒ‰≈Â ".preg_quote(".,-/!+()%#", "/")."]/", " ", $tilrow['nimitys']);

						// yksikkˆhinta pit‰‰ olla veroton
						if ($yhtiorow["alv_kasittely"] == '') {
							$tilrow["hinta"] = round($tilrow["hinta"] / (1 + $tilrow["alv"] / 100), 2);
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
						elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice") {
							finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $totalvat);
						}
						else {
							pupevoice_rivi($tootxml, $tilrow, $vatamount, $totalvat);
						}
					}

					//Lopetetaan lasku
					if ($lasrow["chn"] == "111") {
						elmaedi_lasku_loppu($tootedi, $lasrow);

						$edilask++;
					}
					elseif ($lasrow["chn"] == "112") {
						finvoice_lasku_loppu($tootsisainenfinvoice, $lasrow, $pankkitiedot, $masrow);
					}
					elseif ($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice") {
						finvoice_lasku_loppu($tootfinvoice, $lasrow, $pankkitiedot, $masrow);
					}
					else {
						pupevoice_lasku_loppu($tootxml);
					}

					// Otetaan talteen jokainen laskunumero joka l‰hetet‰‰n jotta voidaan tulostaa paperilaskut
					$tulostettavat[] = $lasrow["laskunro"];
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

			//dellataan failit jos ne on tyhji‰
			if(filesize($nimixml) == 0) {
				unlink($nimixml);
			}
			else {
				//siirretaan laskutiedosto operaattorille
				$ftphost = "ftp.verkkolasku.net";
				$ftpuser = $yhtiorow['verkkotunnus_lah'];
				$ftppass = $yhtiorow['verkkosala_lah'];
				$ftppath = "out/einvoice/data/";
				$ftpfile = realpath($nimixml);

				// t‰t‰ ei ajata eik‰ k‰ytet‰, mutta jos tulee ftp errori niin echotaan t‰‰ meiliin, niin ei tartte k‰sin kirjotella resendi‰
				echo "<pre>ncftpput -u $ftpuser -p $ftppass $ftphost $ftppath $ftpfile</pre>";

				echo "<table>";
				echo "<tr><th>".t("Tallenna pupevoice-aineisto").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='".str_replace('../dataout/', '', $nimixml)."'>";
				echo "<input type='hidden' name='file' value='$nimixml'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table>";
			}


			if(filesize($nimifinvoice) == 0) {
				unlink($nimifinvoice);
			}
			else {

				//siirretaan laskutiedosto operaattorille
				$ftphost = "ftp.itella.net";
				$ftpuser = $yhtiorow['verkkotunnus_lah'];
				$ftppass = $yhtiorow['verkkosala_lah'];
				$ftppath = "out/finvoice/data/";
				$ftpfile = realpath($nimifinvoice);
				$renameftpfile = $nimi_filefinvoice_siirto_valmis;

				// t‰t‰ ei ajata eik‰ k‰ytet‰, mutta jos tulee ftp errori niin echotaan t‰‰ meiliin, niin ei tartte k‰sin kirjotella resendi‰
				echo "<pre>mv $ftpfile ".str_replace("TRANSFER_", "DELIVERED_", $ftpfile)."\nncftpput -u $ftpuser -p $ftppass -T T $ftphost $ftppath ".str_replace("TRANSFER_", "DELIVERED_", $ftpfile)."</pre>";

				echo "<table>";
				echo "<tr><th>".t("Tallenna finnvoice-aineisto").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='".str_replace('../dataout/', '', $nimifinvoice)."'>";
				echo "<input type='hidden' name='file' value='$nimifinvoice'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table>";
			}

			if(filesize($nimiedi) == 0) {
				unlink($nimiedi);
			}
			else{
				echo "<table>";
				echo "<tr><th>".t("Tallenna Elmaedi-aineisto").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='".str_replace('../dataout/', '', $nimiedi)."'>";
				echo "<input type='hidden' name='file' value='$nimiedi'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table>";
			}
		}
		else {
			echo "<font class='message'>".t("Anna laskunumerot, pilkulla eroteltuina, joista aineisto muodostetaan:")."</font><br>";
			echo "<form method='post'>";
			echo "<input type='hidden' name='tee' value='GENEROI'>";
			echo "<textarea name='laskunumerot' rows='10' cols='60'></textarea>";
			echo "<input type='submit' value='Luo aineisto'>";
			echo "</form>";
		}

		// poistetaan lukot
		$query = "UNLOCK TABLES";
		$locre = mysql_query($query) or pupe_error($query);

		require("../inc/footer.inc");
	}
?>
