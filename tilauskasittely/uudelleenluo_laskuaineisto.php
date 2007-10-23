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

						$ulos .= $tieto;

					}

					$pos = strpos($joukko, " ");
		            if ($pos === false) {
						//	Jos tehd‰‰n finvoicea rivilopu on \r\n
						if($yhtiorow["verkkolasku_lah"] != "" and $lasrow["chn"] != "111") {
							$ulos .= "</$joukko>\r\n";
						}
						else {
							$ulos .= "</$joukko>\n";
						}

		            }
		            else {
						if($yhtiorow["verkkolasku_lah"] != "" and $lasrow["chn"] != "111") {
							$ulos .= "</".substr($joukko,0,$pos).">\r\n";
						}
						else {
							$ulos .= "</".substr($joukko,0,$pos).">\n";
						}
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
				function pp ($muuttuja) {
					return $muuttuja = str_replace(".",",",$muuttuja);
				}
			}

			if (!function_exists("ymuuta")) {
				function ymuuta ($ytunnus) {
					// stripataan kaikki - merkit
					$ytunnus = str_replace("-","", $ytunnus);

					$ytunnus = sprintf("%08.8s",$ytunnus);
					return substr($ytunnus,0,7)."-".substr($ytunnus,-1);
				}
			}

			$today = date("w") + 1; // mik‰ viikonp‰iv‰ t‰n‰‰n on 1-7.. 1=sunnuntai, 2=maanantai, jne...

			//Tiedostojen polut ja nimet
			//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
			$nimixml = "../dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".xml";
			$nimi_filexml = "laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".xml";

			$nimifinvoice = "../dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";
			$nimi_filefinvoice = "laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true))."_finvoice.xml";

			$nimiedi = "../dataout/laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".edi";
			$nimi_fileedi = "laskutus-$kukarow[yhtio]-".date("Ymd")."-".md5(uniqid(rand(),true)).".edi";

			//Pupevoice xml-dataa
			if (!$tootxml = fopen($nimixml, "w")) die("Filen $nimixml luonti ep‰onnistui!");

			//Finvoice xml-dataa
			if (!$tootfinvoice = fopen($nimifinvoice, "w")) die("Filen $nimifinvoice luonti ep‰onnistui!");

			//Elma-EDI-inhouse dataa (EIH-1.4.0)
			if (!$tootedi = fopen($nimiedi, "w")) die("Filen $nimiedi luonti ep‰onnistui!");

			// lock tables
			$query = "LOCK TABLES lasku WRITE, tilausrivi WRITE, tilausrivi as t2 WRITE, tilausrivi as t3 READ, tilausrivin_lisatiedot READ, sanakirja WRITE, tapahtuma WRITE, tuotepaikat WRITE, tiliointi WRITE, toimitustapa READ, maksuehto READ, sarjanumeroseuranta WRITE, tullinimike READ, kuka READ, varastopaikat READ, tuote READ, rahtikirjat READ, kirjoittimet READ, tuotteen_avainsanat READ, tuotteen_toimittajat READ, asiakas READ, rahtimaksut READ, avainsana READ, factoring READ, pankkiyhteystiedot READ, yhtion_toimipaikat READ, tuotteen_alv READ, maat READ";
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
			$query = "	select *
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
				$query  = "	select * from maksuehto
							left join pankkiyhteystiedot on (pankkiyhteystiedot.yhtio=maksuehto.yhtio and pankkiyhteystiedot.tunnus=maksuehto.pankkiyhteystiedot)
							where maksuehto.yhtio='$kukarow[yhtio]' and maksuehto.tunnus='$lasrow[maksuehto]'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 0) {
					$masrow = array();

					if ($lasrow["erpcm"] == "0000-00-00") {
						$tulos_ulos .= "<font class='message'><br>\n".t("Maksuehtoa")." $lasrow[maksuehto] ".t("ei lˆydy!")." Tunnus $lasrow[tunnus] ".t("Laskunumero")." $lasrow[laskunro] ".t("ep‰onnistui pahasti")."!</font><br>\n<br>\n";
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


				// t‰ss‰ pohditaan laitetaanko verkkolaskuputkeen
				if (($lasrow["vienti"] == "" or ($lasrow["vienti"] == "E" and $lasrow["chn"] == "020")) and $masrow["itsetulostus"] == "" and $lasrow["sisainen"] == "" and $masrow["kateinen"] == "" and abs($lasrow["summa"]) != 0) {
					// Nyt meill‰ on:
					// $lasrow array on U-laskun tiedot
					// $yhtiorow array on yhtion tiedot
					// $masrow array maksuehdon tiedot

					// Etsit‰‰n myyj‰n nimi
					$mquery  = "select nimi
								from kuka
								where tunnus='$lasrow[myyja]' and yhtio='$kukarow[yhtio]'";
					$myyresult = mysql_query($mquery) or pupe_error($mquery);
					$myyrow = mysql_fetch_array($myyresult);

					if ($lasrow['chn'] == '') {
						//Paperi by default
						$lasrow['chn'] = "100";
					}
					if ($lasrow['chn'] == "020") {
						$lasrow['chn'] = "010";
					}

					if ($lasrow['arvo']>=0) {
						//Veloituslasku
						$tyyppi='380';
					}
					else {
						//Hyvityslasku
						$tyyppi='381';
					}

					$asiakas_apu_query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$lasrow[liitostunnus]'";
					$asiakas_apu_res = mysql_query($asiakas_apu_query) or pupe_error($asiakas_apu_query);

					if (mysql_num_rows($asiakas_apu_res) == 1) {
						$asiakas_apu_row = mysql_fetch_array($asiakas_apu_res);
					}
					else {
						$asiakas_apu_row = array();
					}

					if (strtoupper($asiakas_apu_row["kieli"]) == "SE") {
						$laskun_kieli = "SE";
					}
					elseif ($kieli != "") {
						$laskun_kieli = $kieli;
					}
					else {
						$laskun_kieli = "";
					}

					// Laskukohtaiset kommentit kuntoon
					// T‰m‰ merkki | eli pystyviiva on rivinvaihdon merkki elmalla
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
						// Vanhojen virheiden takia tehd‰‰n ereg_replace uudestaan.
						$komm = ereg_replace("[^A-Za-z0-9÷ˆƒ‰≈Â .,-/!|+():%\r\n]", "", $komm);
						
						$lasrow['sisviesti1'] = str_replace(array("\r\n","\r","\n"),"|", trim($komm));
					}
				
					///* Jos t‰m‰ on valuuttalasku *///
					if ($lasrow["valkoodi"] != '' and trim(strtoupper($lasrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
						$lasrow["kasumma"] 	= $lasrow["kasumma_valuutassa"];
						$lasrow["summa"] 	= $lasrow["summa_valuutassa"];
						$lasrow["arvo"]		= $lasrow["arvo_valuutassa"];
					}

					// Ulkomaisen ytunnuksen korjaus
					if (substr(trim(strtoupper($lasrow["ytunnus"])),0,2) != strtoupper($lasrow["maa"]) and trim(strtoupper($lasrow["maa"])) != trim(strtoupper($yhtiorow["maa"]))) {
						$lasrow["ytunnus"] = strtoupper($lasrow["maa"])."-".$lasrow["ytunnus"];
					}

					$query = "	SELECT min(date_format(toimitettuaika, '%Y-%m-%d')) mint, max(date_format(toimitettuaika, '%Y-%m-%d')) maxt
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and uusiotunnus = '$lasrow[tunnus]'
								and toimitettuaika != '0000-00-00'";
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
					elseif($yhtiorow["verkkolasku_lah"] != "") {
						finvoice_otsik($tootfinvoice, $lasrow, $pankkitiedot, $masrow, $silent, $tulos_ulos, $toimaikarow, $myyrow);
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
						// Valuuttajutut kuntoon
						if ($lasrow["valkoodi"] != '' and trim(strtoupper($lasrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $lasrow["vienti_kurssi"] != 0) {
							$rivihinta_q 	= "(rivihinta/$lasrow[vienti_kurssi])";
						}
						else {
							$rivihinta_q 	= "rivihinta";
						}

						if ($alvrow1["alv"] >= 500) {
							$aquery = "	SELECT alv, round(sum($rivihinta_q),2) rivihinta, round(sum(0),2) alvrivihinta
										FROM tilausrivi
										WHERE uusiotunnus = '$lasrow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow1[alv]'
										GROUP by alv";
						}
						else {
							$aquery = "	SELECT alv, round(sum($rivihinta_q),2) rivihinta, round(sum($rivihinta_q*(alv/100)),2) alvrivihinta
										FROM tilausrivi
										WHERE uusiotunnus = '$lasrow[tunnus]' and yhtio='$kukarow[yhtio]' and alv='$alvrow1[alv]'
										GROUP by alv";
						}
						$aresult = mysql_query($aquery) or pupe_error($aquery);
						$alvrow = mysql_fetch_array($aresult);

						// Kirjotetaan failiin arvierittelyt
						if ($lasrow["chn"] == "111") {
							elmaedi_alvierittely($tootedi, $alvrow);
						}
						elseif($yhtiorow["verkkolasku_lah"] != "") {
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
					elseif($yhtiorow["verkkolasku_lah"] != "") {
						finvoice_otsikko_loput($tootfinvoice, $lasrow, $masrow);
					}

					// katotaan miten halutaan sortattavan
					$sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);

					// Kirjoitetaan rivitietoja tilausriveilt‰
					$query = "	SELECT *, if(date_format(toimitettuaika, '%Y-%m-%d') = '0000-00-00', date_format(now(), '%Y-%m-%d'), date_format(toimitettuaika, '%Y-%m-%d')) toimitettuaika, $sorttauskentta
								FROM tilausrivi
								WHERE yhtio = '$kukarow[yhtio]'
								and uusiotunnus = '$lasrow[tunnus]'
								and kpl<>0
								and tilausrivi.tyyppi = 'L'
								ORDER BY otunnus, sorttauskentta $yhtiorow[laskun_jarjestys_suunta], tilausrivi.tunnus";
					$tilres = mysql_query($query) or pupe_error($query);

					$rivinumero = 1;

					while ($tilrow = mysql_fetch_array($tilres)) {

						if (strtolower($laskun_kieli) != strtolower($yhtiorow['kieli'])) {
							$query = "	SELECT selite
										FROM tuotteen_avainsanat
										WHERE yhtio = '$kukarow[yhtio]'
										and laji 	= 'nimitys_".strtolower($laskun_kieli)."'
										and tuoteno = '$tilrow[tuoteno]'
										and selite != ''
										LIMIT 1";
							$nimresult = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($nimresult) > 0) {
								$nimrow = mysql_fetch_array($nimresult);
								$tilrow['nimitys'] = $nimrow['selite'];
							}
						}

						//K‰ytetyn tavaran myynti
						if ($tilrow["alv"] >= 500) {
							$tilrow["alv"] = 0;
							$tilrow["kommentti"] .= "|Ei sis‰ll‰ v‰hennett‰v‰‰ veroa.";
						}

						//Hetaan sarjanumeron tiedot
						if ($tilrow["kpl"] > 0){
							$sarjanutunnus = "myyntirivitunnus";
						}
						else {
							$sarjanutunnus = "ostorivitunnus";
						}

						$query = "	select *
									from sarjanumeroseuranta
									where yhtio = '$kukarow[yhtio]'
									and tuoteno = '$tilrow[tuoteno]'
									and $sarjanutunnus='$tilrow[tunnus]'
									and sarjanumero != ''";
						$sarjares = mysql_query($query) or pupe_error($query);

						if ($tilrow["kommentti"] != '' and mysql_num_rows($sarjares) > 0){
							$tilrow["kommentti"] .= "|";
						}
						while($sarjarow = mysql_fetch_array($sarjares)) {
							$tilrow["kommentti"] .= "# $sarjarow[sarjanumero]|";
						}

						if ($lasrow["valkoodi"] != '' and trim(strtoupper($lasrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
							// Rivihinta
							if ($yhtiorow["alv_kasittely"] == '') {
								$tilrow["rivihinta"] = round(laskuval($tilrow["hinta"], $lasrow["vienti_kurssi"])*$tilrow["kpl"]*(1-$tilrow["ale"]/100) / (1+$tilrow["alv"]/100), 2);
							}
							else {
								$tilrow["rivihinta"] = round(laskuval($tilrow["hinta"], $lasrow["vienti_kurssi"])*$tilrow["kpl"]*(1-$tilrow["ale"]/100), 2);
							}
							// Yksikkˆhinta
							$tilrow["hinta"] = round(laskuval($tilrow["hinta"], $lasrow["vienti_kurssi"]), 2);
						}

						$vatamount = round($tilrow['rivihinta']*$tilrow['alv']/100, 2);
						$totalvat  = round($tilrow['rivihinta']+$vatamount, 2);

						$tilrow['kommentti'] 	= ereg_replace("[^A-Za-z0-9÷ˆƒ‰≈Â .,-/!|+()%#]", " ", $tilrow['kommentti']);
						$tilrow['nimitys'] 		= ereg_replace("[^A-Za-z0-9÷ˆƒ‰≈Â .,-/!|+()%#]", " ", $tilrow['nimitys']);

						if ($lasrow["chn"] == "111") {
							elmaedi_rivi($tootedi, $tilrow, $rivinumero);
						}
						elseif($yhtiorow["verkkolasku_lah"] != "") {
							finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $totalvat);
						}
						else {
							$tilrow["kommentti"]= str_replace("|"," ",$tilrow["kommentti"]); //Poistetaan pipet. Itella ei niist‰ t‰‰ll‰ selvi‰
							pupevoice_rivi($tootxml, $tilrow, $vatamount, $totalvat);
						}
						$rivinumero++;
					}

					//Lopetetaan lasku
					if ($lasrow["chn"] == "111") {
						elmaedi_lasku_loppu($tootedi, $lasrow);

						$edilask++;
					}
					elseif($yhtiorow["verkkolasku_lah"] != "") {
						finvoice_lasku_loppu($tootfinvoice, $lasrow, $pankkitiedot, $masrow);
					}
					else {
						pupevoice_lasku_loppu($tootxml);
					}

					// Otetaan talteen jokainen laskunumero joka l‰hetet‰‰n jotta voidaan tulostaa paperilaskut
					$tulostettavat[] = $lasrow["laskunro"];
					$lask++;
				}
				elseif ($lasrow["sisainen"] != '') {
					if ($silent == "") $tulos_ulos .= "<br>\n".t("Tehtiin sis‰inen lasku")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";

					// Sis‰isi‰ laskuja ei normaaalisti tuloseta paitski jos meill‰ on valittu_tulostin
					if ($valittu_tulostin != '') {
						$tulostettavat[] = $lasrow["laskunro"];
						$lask++;
					}
				}
				elseif ($masrow["kateinen"] != '') {
					if ($silent == "") {
						$tulos_ulos .= "<br>\n".t("K‰teislaskua ei l‰hetetty")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
					}

					// K‰teislaskuja ei l‰hetet‰ ulos mutta ne halutaan kuitenkin tulostaa itse
					$tulostettavat[] = $lasrow["laskunro"];
					$lask++;
				}
				elseif ($lasrow["vienti"] != '' or $masrow["itsetulostus"] != '') {
					if ($silent == "" or $silent == "VIENTI") {
						$tulos_ulos .= "<br>\n".t("T‰m‰ lasku tulostetaan omalle tulostimelle")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
					}

					// Halutaan tulostaa itse
					$tulostettavat[] = $lasrow["laskunro"];
					$lask++;
				}
				elseif($silent == "") {
					$tulos_ulos .= "\n".t("Nollasummaista laskua ei l‰hetetty")."! $lasrow[laskunro] $lasrow[nimi]<br>\n";
				}

				//Aineistojen lopput‰git
				elmaedi_aineisto_loppu($tootedi, $timestamppi);
				pupevoice_aineisto_loppu($tootxml);
			}

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
