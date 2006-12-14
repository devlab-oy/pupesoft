<?php

	if (isset($_POST["tee"]) and $_POST["tee"] == 'lataa_tiedosto') {
		$lataa_tiedosto = 1;
		$file = file_get_contents($_POST["file"]);
		unset($_POST["file"]);
			
		if(isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') { 
			$_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
		}
	}
		
	require("inc/parametrit.inc");
	
	if (isset($tee) and $tee == "lataa_tiedosto") {
		echo "$file";
	}
	else {
		
		
		echo "<font class='head'>".t("Luo laskutusaineisto")."</font><hr>\n";
		
		if (isset($tee) and $tee == "GENEROI" and $laskunumerot!='') {
			if (!function_exists("xml_add")) {
				function xml_add ($joukko, $tieto, $handle) {
					$ulos = "<$joukko>";

					if (strlen($tieto) > 0) {
						//K‰sitell‰‰n xml-entiteetit
						$serc = array("&", ">", "<", "'", "\"", "¥", "`");
						$repl = array("&amp;", "&gt;", "&lt;", "&apos;", "&quot;", " ", " ");
						$tieto = str_replace($serc, $repl, $tieto);

						$ulos .= $tieto;

					}

					$ulos .= "</$joukko>\n";

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
					$ytunnus = sprintf("%08.8s",$ytunnus);
					return substr($ytunnus,0,7)."-".substr($ytunnus,-1);
				}
			}

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
						where yhtio	= '$kukarow[yhtio]'
						and tila	= 'U'
						and alatila	= 'X'
						and laskunro in ($laskunumerot)";
			$res   = mysql_query($query) or pupe_error($query);

			$lkm = count(explode(',', $laskunumerot));
			
			echo "<br><font class='message'>".t("Syˆtit")." $lkm ".t("laskua").".</font><br>";
			echo "<font class='message'>".t("Aineistoon lis‰t‰‰n")." ".mysql_num_rows($res)." ".t("laskua").".</font><br><br>";
			
			while ($lasrow = mysql_fetch_array($res)) {
		
				// haetaan maksuehdon tiedot
				$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$lasrow[maksuehto]'";
				$result = mysql_query($query) or pupe_error($query);
				$masrow = mysql_fetch_array($result);

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
					$frow = "";
				}

				// t‰ss‰ pohditaan laitetaanko verkkolaskuputkeen
				if (($lasrow["vienti"] == "" or ($lasrow["vienti"] == "E" and $lasrow["chn"] == "020")) and $masrow["itsetulostus"] == "" and $lasrow["sisainen"] == "" and $masrow["kateinen"] == "" and abs($lasrow["summa"]) != 0) {

					// Etsit‰‰n myyj‰n nimi
					$mquery  = "select nimi
								from kuka
								where tunnus='$lasrow[myyja]' and yhtio='$kukarow[yhtio]'";
					$myyresult = mysql_query($mquery) or pupe_error($mquery);
					$myyrow = mysql_fetch_array($myyresult);

					if ($lasrow['chn'] == '') {
						//Paperi by default
						$lasrow['chn'] = 100;
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

					// Laskukohtaiset kommentit kuntoon
					// T‰m‰ merkki | eli pystyviiva on rivinvaihdon merkki elmalla
					// Laskun kommentti on stripattu erikoismerkeist‰ jo aikaisemmin joten se on nyt puhdas t‰ss‰
					if (trim($lasrow['sisviesti1']) != '') {
						$lasrow['sisviesti1'] = str_replace(array("\r\n","\r","\n"),"|", $lasrow['sisviesti1']);
					}
		
					//Kirjoitetaan failiin laskun otsikkotiedot
					if ($lasrow["chn"] == "111") {
						elmaedi_otsik($tootedi, $lasrow, $masrow, $tyyppi, $timestamppi);
					}					
					elseif($yhtiorow["verkkolasku_lah"] != "") {
						finvoice_otsik($tootfinvoice, $lasrow);
					}
					else {
						pupevoice_otsik($tootxml, $lasrow, $kieli, $frow, $masrow, $myyrow, $tyyppi);
					}


					// Tarvitaan rivien eri verokannat
					$query = "	select alv, round(sum(rivihinta),2), round(sum(alv/100*rivihinta),2)
								from tilausrivi
								where yhtio='$kukarow[yhtio]' and uusiotunnus='$lasrow[tunnus]' 
								group by alv";
					$alvres = mysql_query($query) or pupe_error($query);

					while ($alvrow = mysql_fetch_array($alvres)) {
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

					// Kirjoitetaan rivitietoja tilausriveilt‰
					$query = "	select *, concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
								from tilausrivi
								where yhtio='$kukarow[yhtio]' and uusiotunnus='$lasrow[tunnus]'
								and kpl<>0
								order by otunnus, sorttauskentta, tuoteno, tunnus";
					$tilres = mysql_query($query) or pupe_error($query);

					$rivinumero = 1;

					while ($tilrow = mysql_fetch_array($tilres)) {
						$vatamount = round($tilrow['rivihinta']*$tilrow['alv']/100, 2);
						$totalvat  = round($tilrow['rivihinta']+$vatamount, 2);
			
						$tilrow['kommentti']	= ereg_replace("[^A-Za-z0-9÷ˆƒ‰≈Â .,-/!|+():%]", " ", $tilrow['kommentti']);
						$tilrow['nimitys']		= ereg_replace("[^A-Za-z0-9÷ˆƒ‰≈Â .,-/!|+():%]", " ", $tilrow['nimitys']);
			
						if ($lasrow["chn"] == "111") {
							elmaedi_rivi($tootedi, $tilrow, $rivinumero);
						}
						elseif($yhtiorow["verkkolasku_lah"] != "") {
							finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $totalvat);
						}
						else {
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
						finvoice_lasku_loppu($tootfinvoice, $lasrow);
					}
					else {
						pupevoice_lasku_loppu($tootxml);
					}
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
	
		require("../inc/footer.inc");	
	}
?>