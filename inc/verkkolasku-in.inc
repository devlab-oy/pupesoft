<?php

	// otetaan sisään $file jossa on luettavan filen nimi polkuineen
	// palautetaan virheviesti jos tapahtuu jotain, muuten palautetaan tyhjää

	if (!function_exists("verkkolasku_in")) {
		function verkkolasku_in ($file, $luolasku = TRUE) {
			global $kukarow, $yhtiorow, $toim_asiakkaantiedot, $xmlstr, $liitetaanko_editilaus_laskulle_hakemisto, $verkkolaskut_orig;

			if (!is_file($file)) {
				echo ("Tiedostoa $file ei löydy!\n");
				return ("Tiedostoa $file ei löydy!");
			}

			// luetaan file muuttujaan
			$xmlstr = file_get_contents($file);

			if ($xmlstr === FALSE) {
				echo ("Tiedosto $file luku epäonnistui!\n");
				return ("Tiedosto $file luku epäonnistui!");
			}

			// luetaan sisään xml
			$xml = simplexml_load_string($xmlstr);

			if ($xml === FALSE) {
				echo ("Tiedosto $file ei ole validi XML!\n");
				return ("Tiedosto $file ei ole validi XML!");
			}

			require_once("inc/functions.inc");

			// Katsotaan mitä aineistoa käpistellään
			if (strpos($file, "finvoice") !== false or strpos($file, "maventa") !== false) {
				require("inc/verkkolasku-in-finvoice.inc");
			}
			elseif (strpos($file, "teccominvoice") !== false){
				require("inc/verkkolasku-in-teccom.inc");
			}
			elseif (strpos($file, "ORAO") !== false){
				require("inc/verkkolasku-in-unikko.inc");
			}
			else {
				require("inc/verkkolasku-in-pupevoice.inc");
			}

			// Nämä muuttujat kuuluisi olla setattuna:
			/*
			echo "\n\nLASKUNTIEDOT:\n";
			echo "01: ".$yhtio."\n";
			echo "02: ".$verkkotunnus_vas."\n";
			echo "03: ".$laskun_tyyppi."\n";
			echo "04: ".$laskun_numero."\n";
			echo "05: ".$laskun_ebid."\n";
			echo "06: ".$laskun_tapvm."\n";
			echo "07: ".$laskun_lapvm."\n";
			echo "08: ".$laskun_erapaiva."\n";
			echo "09: ".$laskun_kapvm."\n";
			echo "10: ".$laskun_kasumma."\n";
			echo "11: ".$laskuttajan_ovt."\n";
			echo "12: ".$laskuttajan_nimi."\n";
			echo "13: ".$laskuttajan_vat."\n";
			echo "14: ".$laskun_pankkiviite."\n";
			echo "15: ".$laskun_asiakastunnus."\n";
			echo "16: ".$laskun_summa_eur."\n";
			echo "17: ".$laskun_tilausviite."\n";
			echo "18: ".$kauttalaskutus."\n";
			echo "19: ".$laskun_asiakkaan_tilausnumero."\n";
			echo "20: ".$toim_asiakkaantiedot["toim_ovttunnus"]."\n";
			echo "21: ".$toim_asiakkaantiedot["ytunnus"]."\n";
			echo "22: ".$toim_asiakkaantiedot["nimi"]."\n";
			echo "23: ".$toim_asiakkaantiedot["osoite"]."\n";
			echo "24: ".$toim_asiakkaantiedot["postino"]."\n";
			echo "25: ".$toim_asiakkaantiedot["postitp"]."\n";
			echo "26: ".$ostaja_asiakkaantiedot["toim_ovttunnus"]."\n";
			echo "27: ".$ostaja_asiakkaantiedot["ytunnus"]."\n";
			echo "28: ".$ostaja_asiakkaantiedot["nimi"]."\n";
			echo "29: ".$ostaja_asiakkaantiedot["osoite"]."\n";
			echo "30: ".$ostaja_asiakkaantiedot["postino"]."\n";
			echo "31: ".$ostaja_asiakkaantiedot["postitp"]."\n";
			echo "32: TecCom/Unikko only ".$laskuttajan_toimittajanumero."\n";
			echo "33: ".$laskuttajan_valkoodi."\n";
			echo "34: ".$laskun_toimitunnus."\n";

			Nämä muuttujat ovat valinnaisia:

			RIVINTIEDOT:
			$rtuoteno[]["ale"]
			$rtuoteno[]["alv"]
			$rtuoteno[]["hinta"]
			$rtuoteno[]["kauttalaskutus"]
			$rtuoteno[]["kommentti"]
			$rtuoteno[]["kpl"]
			$rtuoteno[]["laskutettuaika"]
			$rtuoteno[]["nimitys"]
			$rtuoteno[]["rivihinta"]
			$rtuoteno[]["riviinfo"]
			$rtuoteno[]["riviviite"]
			$rtuoteno[]["tilaajanrivinro"]
			$rtuoteno[]["tuoteno"]
			$rtuoteno[]["yksikko"]
			$rtuoteno[]["tilinumero"]
			$rtuoteno[]["rivihinta_valuutassa"]
			*/

			// generic error messagen alku
			$return  = "Tiedosto: $file\n";
			$return .= "Lähettäjä: $laskuttajan_nimi\n";
			$return .= "Laskunro: $laskun_numero\n";
			$return .= "Päivä: $laskun_tapvm\n";
			$return .= "Summa: $laskun_summa_eur\n";
			$return .= "Viite: $laskun_pankkiviite\n";

			$komm 		= "";
			$laskuvirhe = "";
			$viesti		= "";

			// $yhtio:ssa on nyt laskun VASTAANOTTAJAN ovt-tunnus ja $verkkotunnus_vas:ssa on VASTAANOTTAJAN verkkolaskutunnus
			$verkkotunnus_vas 	= trim($verkkotunnus_vas);
			$yhtio	 			= trim($yhtio);

			// Itella lehettää joskus verkkotunnusta todella kummallisessa muodossa.. kokeillaan arvailla oikein
			$pos = strpos($verkkotunnus_vas,"@");

			if ($pos !== FALSE) {
				$verkkotunnus_vas = substr($verkkotunnus_vas, 0, $pos); // otetaan info ekaan ättämerkkiin asti
			}

			if ($verkkotunnus_vas != "") {
				// 1 kokeillaan löytää yritys verkkotunnuksella
				$query = "SELECT * FROM yhtion_parametrit WHERE verkkotunnus_vas = '$verkkotunnus_vas'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$paramrow = mysql_fetch_assoc($result);

					// löydettiin parametreistä oikea yhtiö haetaan yhtiorow
					$query = "SELECT * FROM yhtio WHERE yhtio = '$paramrow[yhtio]'";
					$result = pupe_query($query);

					if (mysql_num_rows($result) == 1) {
						$yhtiorow = mysql_fetch_assoc($result);
					}
				}
			}

			if (!isset($yhtiorow) and $verkkotunnus_vas != "") {
				// 2 etsitään vastaanottavaa yritystä ovt-tunnuksella..
				$query = "SELECT * FROM yhtio WHERE ovttunnus = '$verkkotunnus_vas'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($yhtiorow) and $verkkotunnus_vas != "") {
				// 3 etsitään vastaanottavaa yritystä ovt-tunnuksella ilman tarkenteita
				$oyhtio1 = substr($verkkotunnus_vas, 0, 12); // Poistetaan mahdolliset ovt-tunnuksen tarkenteet

				$query = "SELECT * FROM yhtio WHERE ovttunnus = '$oyhtio1'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($yhtiorow) and $verkkotunnus_vas != 0) {
				// 4 etsitään vastaanottavaa yritystä ytunnuksella..
				$yyhtio1 = substr(str_replace("0037", "", $verkkotunnus_vas), 0, 8); // mahdollisella etunollalla
				$yyhtio2 = (int) $yyhtio1; // ilman etunollaa

				$query = "SELECT * FROM yhtio WHERE ytunnus in ('$yyhtio1', '$yyhtio2')";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($yhtiorow) and $yhtio != "" and $yhtio != 0) {
				// 5 etsitään vastaanottavaa yritystä ovt-tunnuksella..
				$query = "SELECT * FROM yhtio WHERE ovttunnus = '$yhtio'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($yhtiorow) and $yhtio != "") {
				// 6 etsitään vastaanottavaa yritystä IBAN-tilinumerolla
				$query = "SELECT * FROM yriti WHERE kaytossa != 'E' and iban = '$yhtio'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$ibanrow = mysql_fetch_assoc($result);

					$query = "SELECT * FROM yhtio WHERE yhtio = '$ibanrow[yhtio]'";
					$result = pupe_query($query);

					if (mysql_num_rows($result) == 1) {
						$yhtiorow = mysql_fetch_assoc($result);
					}
				}
			}

			if (!isset($yhtiorow) and $yhtio != "" and $yhtio != 0) {
				// 7 etsitään vastaanottavaa yritystä ovt-tunnuksella ilman tarkenteita
				$oyhtio2 = substr($yhtio, 0, 12); // Poistetaan mahdolliset ovt-tunnuksen tarkenteet

				$query = "SELECT * FROM yhtio WHERE ovttunnus = '$oyhtio2'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($yhtiorow) and $yhtio != "" and $yhtio != 0) {
				// 8 etsitään vastaanottavaa yritystä ytunnuksella..
				$yyhtio3 = substr(str_replace("0037", "", $yhtio), 0, 8); // mahdollisella etunollalla
				$yyhtio4 = (int) $yyhtio3; // ilman etunollaa

				$query = "SELECT * FROM yhtio WHERE ytunnus in ('$yyhtio3', '$yyhtio4')";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($yhtiorow) and $yhtio != "" and $yhtio != 0) {
				// 9 viimeinen hätäkeino, katotaan onko joku lisätieto ongelma
				$query = "SELECT * FROM yhtio WHERE ovttunnus like '$yhtio%'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$yhtiorow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($yhtiorow) and $luolasku === TRUE) {
				// ei löydetty VASTAANOTTAVAA yhtiötä, lähetetään meili kaikille tän serverin talhal-meileihin!
				$query = "	SELECT distinct yhtio nimi, talhal_email, postittaja_email
							FROM yhtion_parametrit
							WHERE talhal_email != ''
							AND postittaja_email != ''";
				$result = pupe_query($query);

				$hostname = exec('hostname');

				$return .= "\nEpäonnistumisen syy: Laskun vastaanottajaa ei löytynyt järjestelmästä $hostname!\n\n";
				$return .= "Etsittiin tiedoilla:\n";
				$return .= "Verkkotunnus: $verkkotunnus_vas\n";
				$return .= "Ovt: $yhtio $oyhtio1 $oyhtio2\n";
				$return .= "Ytunnus: $yyhtio1 $yyhtio2 $yyhtio3 $yyhtio4\n\n";

				while ($yhtiorow = mysql_fetch_assoc($result)) {
					mail($yhtiorow['talhal_email'], mb_encode_mimeheader("Verkkolaskun vastaanotto epäonnistui", "ISO-8859-1", "Q"), $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
				}

				return ($return);
			}
			elseif (isset($yhtiorow)) {

				$return .= "Lasku yhtiölle: $yhtiorow[nimi] ($yhtiorow[yhtio])\n";

				// Haetaan yhtiön parametrit
				$query = "	SELECT *
							FROM yhtion_parametrit
							WHERE yhtio = '$yhtiorow[yhtio]'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$yhtion_parametritrow = mysql_fetch_assoc($result);
					// lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
					foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
						$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
					}
				}
			}

			// $yhtiossa on nyt yhtio.yhtio
			$yhtio = $yhtiorow['yhtio'];

			// Perusta lasku
			$summa = $laskun_summa_eur;

			if ($laskuttajan_valkoodi != "") {
				$valkoodi = $laskuttajan_valkoodi;
			}
			else {
				$valkoodi = 'EUR';
			}

			if ($laskun_tyyppi == "381" and $summa > 0) {
				$summa = $summa * -1;
			}

			$viite  = str_replace(" ", "", $laskun_pankkiviite); // Poistetaan viitteestä kaikki välilyönnit
			$viite  = str_replace("N/A", "", $viite); // Poistetaan viitteestä N/A tieto, mikäli joistakin skannauspalvelusta sellainen tulee

			// Hylätään viite jos se on nollaa
			if (is_numeric($viite) and (int) $viite == 0) {
				$viite = "";

				// Setataan viesti
				if (trim($laskun_numero) != "") $viesti = $laskun_numero;
				else $viesti = "Lasku: $summa $valkoodi";
			}

			$ebid   = $laskun_ebid;
			$selite = t("Laskunumero").": ".$laskun_numero;

			unset($trow);

			// $laskuttajan_ovt --> voi olla ovttunnus, ytunnus, tai vatnumero
			// $laskuttajan_vat --> voisi olla vatnumero
			// $laskuttajan_nimi --> voisi olla laskuttajan nimi
			// $laskuttajan_toimittajanumero --> toimittajanumero
			$laskuttajan_ovt  = trim($laskuttajan_ovt);
			$laskuttajan_vat  = trim($laskuttajan_vat);
			$laskuttajan_nimi = trim($laskuttajan_nimi);
			$laskuttajan_toimittajanumero = trim($laskuttajan_toimittajanumero);
			$laskun_toimitunnus = (int) $laskun_toimitunnus;

			if ($laskun_toimitunnus > 0) {
				// 0 etsitään toimittaja tunnuksella
				$query  = "	SELECT *
							FROM toimi
							WHERE tunnus = '{$laskun_toimitunnus}'
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($trow) and $laskuttajan_ovt != "") {
				// 1 etsitään toimittaja ovttunnuksella
				$query  = "	SELECT *
							FROM toimi
							WHERE ovttunnus = '$laskuttajan_ovt'
							and ovttunnus not in ('','0')
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($trow) and $laskuttajan_ovt != "") {
				// 2 etsitään toimittaja ovt-tunnuksella ilman tarkenteita
				$yovt = substr($laskuttajan_ovt, 0, 12); // Poistetaan mahdolliset ovt-tunnuksen tarkenteet

				$query  = "	SELECT *
							FROM toimi
							WHERE ovttunnus = '$yovt'
							and ovttunnus not in ('','0')
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($trow) and $laskuttajan_vat != "") {
				// 3 etsitään toimittaja vat-numerolla
				$query  = "	SELECT *
							FROM toimi
							WHERE ovttunnus = '$laskuttajan_vat'
							and ovttunnus not in ('','0')
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($trow) and $laskuttajan_vat != "") {
				// 4 etsitään toimittaja vat-numerolla
				$query  = "	SELECT *
							FROM toimi
							WHERE ytunnus = '$laskuttajan_vat'
							and ytunnus not in ('','0')
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($trow) and $laskuttajan_ovt != "") {
				// 5 etsitään toimittaja ytunnuksella
				$yovt1 = substr(str_replace("0037", "", $laskuttajan_ovt), 0, 8); // mahdollisella etunollalla
				$yovt2 = (int) $yovt1; // ilman etunollaa

				$query  = "	SELECT *
							FROM toimi
							WHERE ytunnus in ('$yovt1', '$yovt2')
							and ytunnus not in ('','0')
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($trow) and $laskuttajan_vat != "") {
				$intvat = preg_replace("/[^0-9]/", "", $laskuttajan_vat);
				$intvat2 = (int) $intvat; // ilman etunollaa

				// 6 etsitään toimittaja vat-numerolla ilman FI-etuliitettä
				$query  = "	SELECT *
							FROM toimi
							WHERE REPLACE(REPLACE(REPLACE(ytunnus,'FI',''),'fi',''),'-','') in ('$intvat', '$intvat2')
							and ytunnus not in ('','0')
							and yhtio = '$yhtio'
							and tyyppi != 'P'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_assoc($result);
				}
			}
			
			if (!isset($trow) and $laskuttajan_toimittajanumero != "") {
				// 9 etsitään toimittaja teccomi-specific
				$query = "	SELECT *
							FROM toimi
							WHERE yhtio = '$yhtio'
							and toimittajanro = '$laskuttajan_toimittajanumero'
							and tyyppi != 'P'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_assoc($result);
				}
			}
			
			if (!isset($trow) and $laskuttajan_nimi != "") {
				// 7 etsitään toimittaja nimellä
				$query = "	SELECT *
							FROM toimi
							WHERE yhtio = '$yhtio'
							and nimi = '$laskuttajan_nimi'
							and nimi not in ('','0')
							and tyyppi != 'P'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_assoc($result);
				}
			}

			if (!isset($trow) and $laskuttajan_nimi != "") {

				$laskuttajan_nimi = str_replace(" ", "", $laskuttajan_nimi);

				// 8 etsitään IBAN-numerolla. (Maventa special: Jos laskulta puuttuu ytunnus, niin IBAN-työnnetaan laskun nimi-kenttään...)
				$query = "	SELECT *
							FROM toimi
							WHERE yhtio = '$yhtio'
							and ultilno = '$laskuttajan_nimi'
							and ultilno not in ('','0')
							and tyyppi != 'P'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 1) {
					$trow = mysql_fetch_assoc($result);
				}
			}

			// Tätä voi ajaa myös verkkolaskuvirheetohjelmasta silloin $luolasku on FALSE ja lopetetaan tässä vaiheessa
			if ($luolasku === TRUE) {
				if (!isset($trow)) {
					$return .= "\nEpäonnistumisen syy: Emme löytäneet toimittajaa yhtiöltä!\n\n";
					$return .= "Etsittiin tiedoilla:\n";
					$return .= "Nimi: $laskuttajan_nimi\n";
					$return .= "Ovt: $laskuttajan_ovt $yovt $yovt1 $yovt2\n";
					$return .= "Vat: $laskuttajan_vat $intvat $intvat2\n";
					$return .= "Nro: $laskuttajan_toimittajanumero\n\n";

					$urlmain = "/view/ebs-2.0/$yhtiorow[verkkotunnus_vas]/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$ebid&TIMESTAMP=".gmdate("YmdHis")."Z&VERSION=ebs-2.0";
					$digest	 = md5($urlmain . "&" . $yhtiorow['verkkosala_vas']);
					$url	 = "http://www.verkkolasku.net".$urlmain."&DIGEST=$digest";

					$return .= "Laskun kuva: $url\n";

					mail($yhtiorow['talhal_email'], mb_encode_mimeheader("Verkkolaskun vastaanotto epäonnistui", "ISO-8859-1", "Q"), $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");

					return ($return);
				}
				else {
					$return .= "Toimittaja: $trow[ytunnus]/$trow[ovttunnus] - $trow[nimi]\nEpäonnistumisen syy: ";
				}

				$ytunnus = $trow["ytunnus"];
				$verkapunimi = $trow["nimi"];

				$hyvak[1] = $trow['oletus_hyvak1'];
				$hyvak[2] = $trow['oletus_hyvak2'];
				$hyvak[3] = $trow['oletus_hyvak3'];
				$hyvak[4] = $trow['oletus_hyvak4'];
				$hyvak[5] = $trow['oletus_hyvak5'];

				$oletustili     	= $trow['tilino'];
				$oletuskust     	= $trow['kustannuspaikka'];
				$oletuskohde    	= $trow['kohde'];
				$oletusprojekti 	= $trow['projekti'];
				$selite         	= $trow['nimi'] . " " . $trow['nimitark'] . " Lasno: " . $laskun_numero;
				$laskun_numero		= preg_replace("/[^0-9]/", "", $laskun_numero);
				$onko_dupli_lasku 	= FALSE;
				$onko_dupli_asn 	= FALSE;

				// Onko tämä kauttalaskutusta?
				$query = "	SELECT kustp, hyvak1, hyvak2, hyvak3, hyvak4, hyvak5, vienti
							FROM tiliointisaanto
							WHERE ttunnus 	= '$trow[tunnus]'
							and tyyppi 		= 'k'
							and yhtio 		= '$yhtio'
							and tilino 		= 0
							and kuvaus 		= '".mysql_real_escape_string($kauttalaskutus)."'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) != 0) {
					$tiliointirow = mysql_fetch_assoc($result);
					$oletuskust = $tiliointirow['kustp'];

					if ($tiliointirow['vienti'] != '') {
						$trow['oletus_vienti'] = $tiliointirow['vienti'];
					}

					if ($tiliointirow['hyvak1'] == 'MAKSUUN') {
						$hyvak[1] = "";
						$hyvak[2] = "";
						$hyvak[3] = "";
						$hyvak[4] = "";
						$hyvak[5] = "";
					}
					elseif (trim($tiliointirow['hyvak1']) != '') {
						$hyvak[1] = $tiliointirow['hyvak1'];
						$hyvak[2] = $tiliointirow['hyvak2'];
						$hyvak[3] = $tiliointirow['hyvak3'];
						$hyvak[4] = $tiliointirow['hyvak4'];
						$hyvak[5] = $tiliointirow['hyvak5'];
					}
				}
				else {
					// Onko tälle toimitusasiakkaalle erityinen kustannuspaikka?  //
					$query = "	SELECT kustp, hyvak1, hyvak2, hyvak3, hyvak4, hyvak5, vienti
								FROM tiliointisaanto
								WHERE ttunnus 	= '$trow[tunnus]'
								and yhtio 		= '$yhtio'
								and tyyppi		= 'o'";

					if ($laskuformaatti == "INVOIC.D.96A") {
						$query .= "	and SUBSTRING(kuvaus,   1, 30) = '".mysql_real_escape_string($toim_asiakkaantiedot['nimi'])."'
									and SUBSTRING(kuvaus2,  1, 30) = '".mysql_real_escape_string($toim_asiakkaantiedot['osoite'])."'
									and SUBSTRING(mintuote, 1, 30) = '".mysql_real_escape_string($toim_asiakkaantiedot['postino'])."'
									and SUBSTRING(maxtuote, 1, 30) = '".mysql_real_escape_string($toim_asiakkaantiedot['postitp'])."'";
					}
					else {
						$query .= " and kuvaus   = '".mysql_real_escape_string($toim_asiakkaantiedot['nimi'])."'
									and kuvaus2  = '".mysql_real_escape_string($toim_asiakkaantiedot['osoite'])."'
									and mintuote = '".mysql_real_escape_string($toim_asiakkaantiedot['postino'])."'
									and maxtuote = '".mysql_real_escape_string($toim_asiakkaantiedot['postitp'])."'";
					}

					$result = pupe_query($query);

					if (mysql_num_rows($result) != 0) {
						$tiliointirow = mysql_fetch_assoc($result);
						$oletuskust = $tiliointirow['kustp'];

						if ($tiliointirow['vienti'] != '') {
							$trow['oletus_vienti'] = $tiliointirow['vienti'];
						}

						if ($tiliointirow['hyvak1'] == 'MAKSUUN') {
							$hyvak[1] = "";
							$hyvak[2] = "";
							$hyvak[3] = "";
							$hyvak[4] = "";
							$hyvak[5] = "";
						}
						elseif (trim($tiliointirow['hyvak1']) != '') {
							$hyvak[1] = $tiliointirow['hyvak1'];
							$hyvak[2] = $tiliointirow['hyvak2'];
							$hyvak[3] = $tiliointirow['hyvak3'];
							$hyvak[4] = $tiliointirow['hyvak4'];
							$hyvak[5] = $tiliointirow['hyvak5'];
						}
					}

					// Onko tälle ostajatiedoille erityinen kustannuspaikka?
					$query = "	SELECT kustp, hyvak1, hyvak2, hyvak3, hyvak4, hyvak5, vienti
								FROM tiliointisaanto
								WHERE ttunnus 	= '$trow[tunnus]'
								and yhtio 		= '$yhtio'
								and tyyppi		= 'b'";

					if ($laskuformaatti == "INVOIC.D.96A") {
						$query .= "	and SUBSTRING(kuvaus,   1, 30) = '".mysql_real_escape_string($ostaja_asiakkaantiedot['nimi'])."'
									and SUBSTRING(kuvaus2,  1, 30) = '".mysql_real_escape_string($ostaja_asiakkaantiedot['osoite'])."'
									and SUBSTRING(mintuote, 1, 30) = '".mysql_real_escape_string($ostaja_asiakkaantiedot['postino'])."'
									and SUBSTRING(maxtuote, 1, 30) = '".mysql_real_escape_string($ostaja_asiakkaantiedot['postitp'])."'";
					}
					else {
						$query .= " and kuvaus   = '".mysql_real_escape_string($ostaja_asiakkaantiedot['nimi'])."'
									and kuvaus2  = '".mysql_real_escape_string($ostaja_asiakkaantiedot['osoite'])."'
									and mintuote = '".mysql_real_escape_string($ostaja_asiakkaantiedot['postino'])."'
									and maxtuote = '".mysql_real_escape_string($ostaja_asiakkaantiedot['postitp'])."'";
					}

					$result = pupe_query($query);

					if (mysql_num_rows($result) != 0) {
						$tiliointirow = mysql_fetch_assoc($result);
						$oletuskust = $tiliointirow['kustp'];

						if ($tiliointirow['vienti'] != '') {
							$trow['oletus_vienti'] = $tiliointirow['vienti'];
						}

						if ($tiliointirow['hyvak1'] == 'MAKSUUN') {
							$hyvak[1] = "";
							$hyvak[2] = "";
							$hyvak[3] = "";
							$hyvak[4] = "";
							$hyvak[5] = "";
						}
						elseif (trim($tiliointirow['hyvak1']) != '') {
							$hyvak[1] = $tiliointirow['hyvak1'];
							$hyvak[2] = $tiliointirow['hyvak2'];
							$hyvak[3] = $tiliointirow['hyvak3'];
							$hyvak[4] = $tiliointirow['hyvak4'];
							$hyvak[5] = $tiliointirow['hyvak5'];
						}
					}

					if (mysql_num_rows($result) == 0) {
						// Onko tälle asiakastunnukselle erityinen kustannuspaikka?
						$query = "	SELECT kustp, hyvak1, hyvak2, hyvak3, hyvak4, hyvak5, vienti
									FROM tiliointisaanto
									WHERE ttunnus 	= '$trow[tunnus]'
									and tyyppi 		= 'a'
									and yhtio 		= '$yhtio'
									and tilino 		= 0
									and kuvaus 		= '".mysql_real_escape_string($laskun_asiakastunnus)."'";
						$result = pupe_query($query);

						if (mysql_num_rows($result) != 0) {
							$tiliointirow = mysql_fetch_assoc($result);
							$oletuskust = $tiliointirow['kustp'];

							if ($tiliointirow['vienti'] != '') {
								$trow['oletus_vienti'] = $tiliointirow['vienti'];
							}

							if ($tiliointirow['hyvak1'] == 'MAKSUUN') {
								$hyvak[1] = "";
								$hyvak[2] = "";
								$hyvak[3] = "";
								$hyvak[4] = "";
								$hyvak[5] = "";
							}
							elseif (trim($tiliointirow['hyvak1']) != '') {
								$hyvak[1] = $tiliointirow['hyvak1'];
								$hyvak[2] = $tiliointirow['hyvak2'];
								$hyvak[3] = $tiliointirow['hyvak3'];
								$hyvak[4] = $tiliointirow['hyvak4'];
								$hyvak[5] = $tiliointirow['hyvak5'];
							}
						}
					}
				}

				// Laitetaan tänä päivä laskun kirjauspäiväksi jos yhtiön parametri on tässä asennossa. (UNIKKO-INVOICE:ssa tapvm tulee erikseen, joten käytetään sitä siinä tapauksessa suoraan...)
				if ($yhtiorow['ostolaskujen_paivays'] == "1" and $laskun_tyyppi != "UNIKKO-INVOICE") {
					$laskun_tapvm = date("Ymd");
				}

				$tpp = (int) substr($laskun_tapvm, 6, 2);
				$tpk = (int) substr($laskun_tapvm, 4, 2);
				$tpv = (int) substr($laskun_tapvm, 0, 4);

				// Laskun laskupäivä
				$vpp = (int) substr($laskun_lapvm, 6, 2);
				$vpk = (int) substr($laskun_lapvm, 4, 2);
				$vpv = (int) substr($laskun_lapvm, 0, 4);

				// Tsekataan laskun päiväys
				if (!checkdate($tpk, $tpp, $tpv)) {
					// Laitetaan sitten ajopäivä
					$tpp = date("d");
					$tpk = date("m");
					$tpv = date("Y");

					$komm = "(verkkolas@" . date('Y-m-d') .") Tiedoista puuttui päiväys. Tarkista asia laskulta!<br>\n" . $komm;
				}

				// Otetaan yhtiön tiedoista ostoreskontran sallittu tilikauden ajankohta
				$tilalk = explode("-", $yhtiorow["ostoreskontrakausi_alku"]);
				$tillop = explode("-", $yhtiorow["ostoreskontrakausi_loppu"]);

				// Verrataan vähän päivämääriä. onpa vittumaista PHP:ssä!
				$ostoresktilalk = (int) date('Ymd', mktime(0,0,0, $tilalk[1], $tilalk[2], $tilalk[0]));
				$ostoresktillop = (int) date('Ymd', mktime(0,0,0, $tillop[1], $tillop[2], $tillop[0]));
				$syotettypvm 	= (int) date('Ymd', mktime(0,0,0, $tpk, $tpp, $tpv));

				if ($syotettypvm > $ostoresktillop) {
					$tpp = $tillop[2];
					$tpk = $tillop[1];
					$tpv = $tillop[0];
					$komm = "(verkkolas@" . date('Y-m-d') .") Laskulla oli päiväys liian pitkälle tulevaisuuteen. Tarkista asia laskulta!<br>\n" . $komm;
				}

				if ($syotettypvm < $ostoresktilalk) {
					$tpp = $tilalk[2];
					$tpk = $tilalk[1];
					$tpv = $tilalk[0];
					$komm = "(verkkolas@" . date('Y-m-d') .") Laskulla oli liian vanha päiväys. Tarkista asia laskulta!<br>\n" . $komm;
				}

				// Oletus-alv nolla paitsi jos toimittajalla on oletuksena jotain kotimaahan liittyvää ni haetaan oletus avainsanoista...
				$oletusalvi = 0;

				if ($trow['oletus_vienti'] == "A" or $trow['oletus_vienti'] == "B" or $trow['oletus_vienti'] == "C" or $trow['oletus_vienti'] == "J") {

					$query = "	SELECT selite
								FROM avainsana
								WHERE yhtio = '$yhtio'
								and laji = 'alv'
								and selitetark != ''
								ORDER BY jarjestys,selite";
					$avainresult = pupe_query($query);

					if (mysql_num_rows($avainresult) != 1) {
						$return .= "Yrityksen $yhtio oletus ALV% puuttuu tai niitä on monta!\n";
						mail($yhtiorow['talhal_email'], mb_encode_mimeheader("Verkkolaskun vastaanotto epäonnistui", "ISO-8859-1", "Q"), $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
						return ($return);
					}
					else {
						$avainrow = mysql_fetch_assoc($avainresult);
						$oletusalvi = $avainrow['selite'];
					}
				}

				// Tsekataan laskun eräpäivä
				$erp = (int) substr($laskun_erapaiva, 6, 2);
				$erk = (int) substr($laskun_erapaiva, 4, 2);
				$erv = (int) substr($laskun_erapaiva, 0, 4);

				// jos eräpäivää ei tule laskulta, otetaan toimittajan oletus
				if ($erp == 0 and $erk == 0 and $erv == 0) {
					$err = $trow["oletus_erapvm"];

					// Jos oletustakaan ei ole, laitetaan lasku erääntymään huomenna...
					if ($err == 0) {
						list($erv,$erk,$erp) = explode("-",strftime("%Y-%m-%d", mktime(0,0,0,date("m"),date("d")+1,date("Y"))));
					}
					elseif ($err > 0) {
						$newer = strftime("%Y-%m-%d", mktime(0,0,0,$vpk, $vpp+$err, $vpv));
						$erp = (int) substr($newer, 8, 2);
						$erk = (int) substr($newer, 5, 2);
						$erv = (int) substr($newer, 0, 4);
					}

					$komm = "(verkkolas@" . date('Y-m-d') .") Tiedoista puuttui eräpvm. Tarkista asia laskulta!<br>\n" . $komm;
				}

				if (!checkdate($erk, $erp, $erv)) {
					$return .= "Virheellinen eräpvm $erv-$erk-$erp\n";
					mail($yhtiorow['talhal_email'], mb_encode_mimeheader("Verkkolaskun vastaanotto epäonnistui", "ISO-8859-1", "Q"), $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					return ($return);
				}

				$kassaale = 0;

				// Tsekataan laskun kassa-alepäivä
				$krp = (int) substr($laskun_kapvm, 6, 2);
				$krk = (int) substr($laskun_kapvm, 4, 2);
				$krv = (int) substr($laskun_kapvm, 0, 4);

				// Jos kassa-alennusta ei tule laskulta, otetaan toimittajan oletus
				if ($krp == 0 and $krk == 0 and $krv == 0) {
					$krr   = $trow["oletus_kapvm"];
					$kapro = $trow['oletus_kapro'];

					if ($krr > 0) {
						$newer = strftime("%Y-%m-%d", mktime(0,0,0,$vpk, $vpp+$krr, $vpv));
						$krp = (int) substr($newer, 8, 2);
						$krk = (int) substr($newer, 5, 2);
						$krv = (int) substr($newer, 0, 4);

						if ((float) $kapro != 0 and (float) $summa != 0) {
							$kassaale = $summa * $kapro / 100;
						}
					}
				}
				elseif ((float) $laskun_kasumma != 0) {
					$kassaale = (float) $laskun_kasumma;
				}

				if ($kassaale != 0) {
					$kassaale = abs(round($kassaale, 2));

					if ($summa < 0) {
						$kassaale = $kassaale *-1;
					}
				}

				if ($krk > 0 and $krp > 0 and $krv > 0 and $kassaale != 0) {
					if (!checkdate($krk, $krp, $krv)) {
						$return .= "Virheellinen kassaeräpvm '$krv-$krk-$krp' kassaale '$kassaale'\n";
						mail($yhtiorow['talhal_email'], mb_encode_mimeheader("Verkkolaskun vastaanotto epäonnistui", "ISO-8859-1", "Q"), $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
						return ($return);
					}
					elseif ($kassaale == 0) {
						$return .= "Kassapvm on, mutta kassa-ale puuttu\n";
						mail($yhtiorow['talhal_email'], mb_encode_mimeheader("Verkkolaskun vastaanotto epäonnistui", "ISO-8859-1", "Q"), $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
						return ($return);
					}
				}

				if (trim($laskun_numero) != '' and trim($trow['tunnus']) != '') {

					// katsotaan onko lasku jo ostoreskontrassa
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio = '{$yhtio}'
								AND tila IN ('H','M','P','Q','Y')
								AND laskunro = '{$laskun_numero}'
								AND tapvm >= date_sub(now(), INTERVAL 12 MONTH)
								AND liitostunnus = '{$trow['tunnus']}'";
					$duplikaattilasku_check_res = pupe_query($query);

					if (mysql_num_rows($duplikaattilasku_check_res) > 0) {
						$komm = "(verkkolas@" . date('Y-m-d') .") Mahdollisesti duplikaattilasku!<br>\n".$komm;
						$onko_dupli_lasku = TRUE;
					}

					// katsotaan onko Teccom-lasku jo asn_sanomat-taulussa
					if ($laskun_tyyppi == 'TECCOM-INVOICE') {
						$query = "	SELECT *
									FROM asn_sanomat
									WHERE yhtio 		 = '{$yhtio}'
									AND laji 			 = 'tec'
									AND asn_numero 		 = '{$laskun_numero}'
									AND toimittajanumero = '{$laskuttajan_toimittajanumero}'";
						$duplikaattilasku_check_res = pupe_query($query);

						if (mysql_num_rows($duplikaattilasku_check_res) > 0) {
							$onko_dupli_asn = TRUE;
						}
					}
				}

				$summa = round($summa, 2);

				if ($summa == 0) {
					$return .= "Laskulta puuttuu summa!\n";
					mail($yhtiorow['talhal_email'], mb_encode_mimeheader("Verkkolaskun vastaanotto epäonnistui", "ISO-8859-1", "Q"), $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					return ($return);
				}

				// Normaali viitetarkistus
				if (isset($viite) and strlen($viite) > 0 and substr($viite, 0, 2) != "RF" and tarkista_viite($viite) === FALSE) {
					$return .= "Viite '$viite' on väärin!\n";
					mail($yhtiorow['talhal_email'], mb_encode_mimeheader("Verkkolaskun vastaanotto epäonnistui", "ISO-8859-1", "Q"), $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					return ($return);
				}
				// RF-viitetarkistus
				if (isset($viite) and strlen($viite) > 0 and  substr($viite, 0, 2) == "RF" and tarkista_rfviite($viite) === FALSE) {
					$return .= "RF-Viite '$viite' on väärin!\n";
					mail($yhtiorow['talhal_email'], mb_encode_mimeheader("Verkkolaskun vastaanotto epäonnistui", "ISO-8859-1", "Q"), $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					return ($return);
				}

				if (isset($viite) and strlen($viite) > 0 and isset($viesti) and strlen($viesti) > 0) {
					$return .= "Viitettä ja viestiä ei voi antaa yhtäaikaa!\n";
					mail($yhtiorow['talhal_email'], mb_encode_mimeheader("Verkkolaskun vastaanotto epäonnistui", "ISO-8859-1", "Q"), $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					return ($return);
				}

				$query = "	SELECT kurssi
							FROM valuu
							WHERE nimi = '$valkoodi'
							AND yhtio  = '$yhtio'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) != 1) {
					$return .= "Valuuttaa $valkoodi ei löytynytkään!\n";
					mail($yhtiorow['talhal_email'], mb_encode_mimeheader("Verkkolaskun vastaanotto epäonnistui", "ISO-8859-1", "Q"), $return, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					return ($return);
				}

				$vrow = mysql_fetch_assoc($result);

				$hyvaksyja_nyt 	= '';
				$tila 			= "M";

				$hyvak[5] = trim($hyvak[5]);
				$hyvak[4] = trim($hyvak[4]);
				$hyvak[3] = trim($hyvak[3]);
				$hyvak[2] = trim($hyvak[2]);
				$hyvak[1] = trim($hyvak[1]);

				if (strlen($hyvak[5]) > 0) {
					$hyvaksyja_nyt=$hyvak[5];
					$tila = "H";
				}
				if (strlen($hyvak[4]) > 0) {
					$hyvaksyja_nyt=$hyvak[4];
					$tila = "H";
				}
				if (strlen($hyvak[3]) > 0) {
					$hyvaksyja_nyt=$hyvak[3];
					$tila = "H";
				}
				if (strlen($hyvak[2]) > 0) {
					$hyvaksyja_nyt=$hyvak[2];
					$tila = "H";
				}
				if (strlen($hyvak[1]) > 0) {
					$hyvaksyja_nyt=$hyvak[1];
					$tila = "H";
				}

				$olmapvm = $erv."-".$erk."-".$erp;

				if ($krp != 0) {
					$olmapvm = $krv."-".$krk."-".$krp;
				}

				#echo "osto_kulu ennen alustusta: $osto_kulu<br><br>";

				$osto_kulu	= (!isset($osto_kulu) or trim($osto_kulu) == '') ? 0 : $osto_kulu;
				$osto_rahti = (!isset($osto_rahti) or trim($osto_rahti) == '') ? 0 : $osto_rahti;
				$osto_rivi_kulu = (!isset($osto_rivi_kulu) or trim($osto_rivi_kulu) == '') ? 0 : $osto_rivi_kulu;

				#echo "osto_kulu jälkeen alustuksen: $osto_kulu<br><br>";

				$osto_kulu_alv	= (!isset($osto_kulu_alv) or trim($osto_kulu_alv) == '') ? 0 : $osto_kulu_alv;
				$osto_rahti_alv = (!isset($osto_rahti_alv) or trim($osto_rahti_alv) == '') ? 0 : $osto_rahti_alv;
				$osto_rivi_kulu_alv = (!isset($osto_rivi_kulu_alv) or trim($osto_rivi_kulu_alv) == '') ? 0 : $osto_rivi_kulu_alv;

				// Kirjoitetaan lasku
				$query = "	INSERT into lasku set
							yhtio 				= '$yhtio',
							summa 				= '$summa',
							kasumma 			= '$kassaale',
							erpcm 				= '$erv-$erk-$erp',
							kapvm 				= '$krv-$krk-$krp',
							olmapvm 			= '$olmapvm',
							valkoodi 			= '$valkoodi',
							hyvak1 				= '$hyvak[1]',
							hyvak2 				= '$hyvak[2]',
							hyvak3 				= '$hyvak[3]',
							hyvak4 				= '$hyvak[4]',
							hyvak5 				= '$hyvak[5]',
							hyvaksyja_nyt 		= '$hyvaksyja_nyt',
							ytunnus 			= '$ytunnus',
							tilinumero 			= '$trow[tilinumero]',
							nimi 				= '$trow[nimi]',
							nimitark 			= '$trow[nimitark]',
							osoite 				= '$trow[osoite]',
							osoitetark 			= '$trow[osoitetark]',
							postino 			= '$trow[postino]',
							postitp 			= '$trow[postitp]',
							maa 				= '$trow[maa]',
							toim_maa			= '$trow[verovelvollinen]',
							ultilno_maa			= '$trow[ultilno_maa]',
							viite 				= '$viite',
							viesti 				= '$viesti',
							sisviesti1 			= '$trow[ohjeitapankille]',
							sisviesti2 			= '$laskun_tilausviite',
							tapvm 				= '$tpv-$tpk-$tpp',
							lapvm 				= '$vpv-$vpk-$vpp',
							vienti 				= '$trow[oletus_vienti]',
							ebid 				= '$ebid',
							tila 				= '$tila',
							vienti_kurssi 		= '$vrow[kurssi]',
							laatija 			= 'verkkolas',
							liitostunnus 		= '$trow[tunnus]',
							luontiaika 			= now(),
							pankki1				= '$trow[pankki1]',
							pankki2				= '$trow[pankki2]',
							pankki3				= '$trow[pankki3]',
							pankki4				= '$trow[pankki4]',
							ultilno				= '$trow[ultilno]',
							swift				= '$trow[swift]',
							suoraveloitus		= '$trow[oletus_suoraveloitus]',
							hyvaksynnanmuutos	= '$trow[oletus_hyvaksynnanmuutos]',
							comments 			= '$komm',
							laskunro 			= '$laskun_numero',
							asiakkaan_tilausnumero = '$laskun_asiakkaan_tilausnumero',
							osto_rahti			= '{$osto_rahti}',
							osto_kulu			= '{$osto_kulu}',
							osto_rivi_kulu		= '{$osto_rivi_kulu}',
							osto_rahti_alv		= '{$osto_rahti_alv}',
							osto_kulu_alv		= '{$osto_kulu_alv}',
							osto_rivi_kulu_alv	= '{$osto_rivi_kulu_alv}'";
				$result = pupe_query($query);
				$tunnus = mysql_insert_id();

				$omasumma = round($summa * $vrow['kurssi'], 2);
				$omasumma_valuutassa = $summa;

				// Tehdään oletustiliöinnit, ostovelat
				$vassumma = -1 * $omasumma;
				$vassumma_valuutassa = -1 * $summa;

				//Tutkitaan ostovelkatiliä
				if ($trow["konserniyhtio"] != '') {
					$ostovelat = $yhtiorow["konserniostovelat"];
				}
				else {
					$ostovelat = $yhtiorow["ostovelat"];
				}

				// Ostovelka, (Hoidetaan kustannuspaikka alempana, kun tiedetään kulutiliöintien kustannuspaikat....)
				$query = "	INSERT INTO tiliointi SET
							yhtio 			 = '$yhtio',
							ltunnus 		 = '$tunnus',
							tilino 			 = '$ostovelat',
							kustp    		 = 0,
							kohde	 		 = 0,
							projekti 		 = 0,
							tapvm 			 = '$tpv-$tpk-$tpp',
							summa 			 = '$vassumma',
							summa_valuutassa = '$vassumma_valuutassa',
							valkoodi 		 = '$valkoodi',
							selite 			 = '$selite',
							vero 			 = 0,
							lukko 			 = '1',
							laatija 		 = 'verkkolas',
							laadittu 		 = now()";
				$result = pupe_query($query);
				$ostovelkatiliointitunnus = mysql_insert_id();

				// Onko rivikohtaisia tiliöintisääntöjä
				$query = "	SELECT *
							FROM tiliointisaanto
							WHERE ttunnus	= '$trow[tunnus]'
							and yhtio 		= '$yhtio'
							and tyyppi 		= 't'
							and tilino	   != 0";
				$result = pupe_query($query);

				// Onko laskuriveillä useita alv-verokantoja?
				$ealvi = array_unique($ealvi);

				// Katsotaan alv laskuerittelystä
				if (count($ealvi) == 1 and $ealvi[0] == "") {
					$ealvi = array($lisavat[0]);
				}

	 			// Tehdän pelkät oletustiliöinnit
	 			// Pitää hardkoodata UNIKKO ohittamaan tämän.
				if (mysql_num_rows($result) == 0 and count($ealvi) == 1 and $laskun_tyyppi != "UNIKKO-INVOICE" and $laskun_tyyppi != "TECCOM-INVOICE") {

					if ($oletustili > 0) {
						$tili = $oletustili;
					}
					else {
						$tili = $yhtiorow['muutkulut'];
					}

					if ($ealvi[0] != "") $vero = $ealvi[0];
					else $vero = $oletusalvi;

					$vero = (float) $vero;

					$kukarow['yhtio']   = $yhtio;
					$kukarow['kuka']    = 'verkkolas';

					$verkkolaskuveroton = round($omasumma / (1 + ($vero / 100)),2);
					$verkkolaskuveroton_valuutassa = round($summa / (1 + ($vero / 100)),2);
					$summa              = $omasumma;
					$kustp              = $oletuskust;
					$kohde              = $oletuskohde;
					$projekti           = $oletusprojekti;

					require ("inc/teetiliointi.inc");

					// Napataan luodut tarkenteet...
					if ($yhtiorow["kirjanpidon_tarkenteet"] == "K") {
						$ostovel_kustp		= $kustp_ins;
						$ostovel_kohde		= $kohde_ins;
						$ostovel_projekti	= $projekti_ins;
					}

					// jos kyseessä on vaihto-omaisuutta tai rahti/huolintakuluja, tiliöidään varastonarvoon
					if ($trow['oletus_vienti'] != 'A' and $trow['oletus_vienti'] != 'D' and $trow['oletus_vienti'] != 'G' and $trow['oletus_vienti'] != '') {

						$varastotili = $yhtiorow['varasto'];

						if ($trow['oletus_vienti'] == 'C' or $trow['oletus_vienti'] == 'F' or $trow['oletus_vienti'] == 'I' or $trow['oletus_vienti'] == 'J' or $trow['oletus_vienti'] == 'K' or $trow['oletus_vienti'] == 'L') {
							$varastotili = $yhtiorow['matkalla_olevat'];
						}

						list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastotili, $kustp, $kohde, $projekti);

						// Varasto
						$query = "	INSERT into tiliointi set
									yhtio 			 = '$kukarow[yhtio]',
									ltunnus 		 = '$tunnus',
									tilino 			 = '$varastotili',
									kustp    		 = '{$kustp_ins}',
									kohde	 		 = '{$kohde_ins}',
									projekti 		 = '{$projekti_ins}',
									tapvm 			 = '$tpv-$tpk-$tpp',
									summa			 = '$verkkolaskuveroton',
									summa_valuutassa = '$verkkolaskuveroton_valuutassa',
									valkoodi		 = '$valkoodi',
									selite 			 = '$selite',
									vero 			 = 0,
									lukko 			 = '',
									laatija 		 = '$kukarow[kuka]',
									laadittu 		 = now()";
						$result = pupe_query($query);

						$varastonmuutostili = $yhtiorow["varastonmuutos"];

						if ($yhtiorow['raaka_ainevarastonmuutos'] != "" and ($trow['oletus_vienti'] == 'J' or $trow['oletus_vienti'] == 'K' or $trow['oletus_vienti'] == 'L')) {
							$varastonmuutostili = $yhtiorow["raaka_ainevarastonmuutos"];
						}

						if ($yhtiorow['varastonmuutos_rahti'] != "" and ($trow['oletus_vienti'] == 'B' or $trow['oletus_vienti'] == 'E' or $trow['oletus_vienti'] == 'H')) {
							$varastonmuutostili = $yhtiorow["varastonmuutos_rahti"];
						}

						list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastonmuutostili, $kustp, $kohde, $projekti);

						// Varastonmuutos
						$query = "	INSERT into tiliointi set
									yhtio 			 = '$kukarow[yhtio]',
									ltunnus 		 = '$tunnus',
									tilino 			 = '$varastonmuutostili',
									kustp    		 = '{$kustp_ins}',
									kohde	 		 = '{$kohde_ins}',
									projekti 		 = '{$projekti_ins}',
									tapvm 			 = '$tpv-$tpk-$tpp',
									summa 			 = $verkkolaskuveroton*-1,
									summa_valuutassa = $verkkolaskuveroton_valuutassa*-1,
									valkoodi 		 = '$valkoodi',
									selite 			 = '$selite',
									vero 			 = 0,
									lukko 			 = '',
									laatija 		 = '$kukarow[kuka]',
									laadittu 		 = now()";
						$result = pupe_query($query);

						// Jos tämä lasku menee varastoon, tehdään valmiiksi keikka
						if ($trow['oletus_vienti'] == 'C' or $trow['oletus_vienti'] == 'F' or $trow['oletus_vienti'] == 'I' or $trow['oletus_vienti'] == 'J' or $trow['oletus_vienti'] == 'K' or $trow['oletus_vienti'] == 'L') {
							verkkolasku_luo_keikkafile($tunnus, $trow, $rtuoteno);
						}
					}
				}
				else {
					// Tehdään rivikohtaiset tiliöinnit
					$i								= 0;
					$totsumma 						= 0;
					$totsumma_valuutassa			= 0;
					$verkkolaskuveroton 			= 0;
					$verkkolaskuveroton_valuutassa	= 0;
					$vtontot 						= 0;
					$vtontot_valuutassa 			= 0;
					$maksimisumma 		 			= 0;
					$sanomantarkistin				= 0;

					$laskun_kulut = array();

					$kustp		= $oletuskust;
					$kohde		= $oletuskohde;
					$projekti	= $oletusprojekti;
					$saapumispvm_chk = trim($laskun_tapvm) != '' ? substr($laskun_tapvm, 0, 4).'-'.substr($laskun_tapvm, 4, 2).'-'.substr($laskun_tapvm, 6, 2).' 00:00:00' : '0000-00-00 00:00:00';

					$count_rtuoteno = count($rtuoteno);

					for ($i = 0; $i < $count_rtuoteno; $i++) {
						if ((float) $rtuoteno[$i]["rivihinta"] != 0 or ($laskun_tyyppi == "UNIKKO-INVOICE" and $rtuoteno[$i]["rivihinta_valuutassa"] != 0)) {
							$kustp = $oletuskust;

							$selite = isset($rtuoteno[$i]["riviinfo"]) ? mysql_real_escape_string(utf8_decode($rtuoteno[$i]["riviinfo"])) : "";
							$tuote  = isset($rtuoteno[$i]["tuoteno"])  ? mysql_real_escape_string(utf8_decode($rtuoteno[$i]["tuoteno"])) : "";

							$query = "	SELECT tilino, kustp
										FROM tiliointisaanto
										WHERE ttunnus 	= '$trow[tunnus]'
										and tyyppi 		= 't'
										and yhtio 		= '$yhtio'
										and mintuote   <= '$tuote'
										and maxtuote   >= '$tuote'
										and tilino 	   != 0";
							$result = pupe_query($query);

	 						// Sopiva sääntöä ei löytynyt
							if (mysql_num_rows($result) == 0) {

								$query = "	SELECT tilino, kustp
											FROM tiliointisaanto
											WHERE ttunnus 	= '$trow[tunnus]'
											and tyyppi 		= 't'
											and yhtio 		= '$yhtio'
											and kuvaus like '%$selite%'
											and tilino     != 0";
								$result = pupe_query($query);

	 							// Hmm, mikään sääntö ei kelpaa
								if (mysql_num_rows($result) == 0) {
	 								if (isset($rtuoteno[$i]["tilinumero"]) and $rtuoteno[$i]["tilinumero"] != "") {
										$tili = $rtuoteno[$i]["tilinumero"];
									}
									elseif ($oletustili > 0) {
										// Toimittajan oletustili
										$tili = $oletustili;
									}
									else {
										 // Yleinen kulutili
										$tili = $yhtiorow['muutkulut'];
									}
								}
								else {
									$tiliointirow = mysql_fetch_assoc($result);
									$tili = $tiliointirow['tilino'];
									if ($tiliointirow['kustp'] > 0) $kustp = $tiliointirow['kustp'];
								}
							}
							else {
								$tiliointirow = mysql_fetch_assoc($result);
								$tili = $tiliointirow['tilino'];
								if ($tiliointirow['kustp'] > 0) $kustp = $tiliointirow['kustp'];
							}

							if ($laskun_tyyppi == "UNIKKO-INVOICE" and $rtuoteno[$i]["rivihinta_valuutassa"] != 0) {
								// Unikko laskuissa tulee kirjauksetkin valuutassa
								$summa_valuutassa = (float) $rtuoteno[$i]["rivihinta_valuutassa"];
								$summa = round($summa_valuutassa * $vrow["kurssi"], 2);
							}
							elseif ($vrow["kurssi"] != 1) {
								// Laskulla olevat rivihinnat ovat valuutassa
								$summa_valuutassa = (float) $rtuoteno[$i]["rivihinta"];
								$summa = round($summa_valuutassa * $vrow["kurssi"], 2);
							}
							else {
								$summa = (float) $rtuoteno[$i]["rivihinta"];
								$summa_valuutassa = $summa;
							}

							// Hyvityslasku tai summa alle nolla
							// FINVOICE-laskuilla rivihinta on aina valmiiksi oikeinpäin
							// LISÄTTY VIELÄ:  and $summa > 0 koska nyt rivihinnat näyttäisi olevan myös INVOIC.D.96A laskulla miinusta kun se on hyväri????????
							if (($laskun_tyyppi == "381" or $omasumma < 0) and $laskuformaatti != "FINVOICE" and $summa > 0) {
								$summa = $summa * -1;
							}

							if ($rtuoteno[$i]["alv"] != "") $vero = $rtuoteno[$i]["alv"];
							else $vero = $oletusalvi;

							$vero = (float) $vero;

							if ($yhtiorow['ostolaskun_kulutilit'] != '' and ($yhtiorow['osto_rahti'] != '' or $yhtiorow['osto_kulu'] != '' or $yhtiorow['osto_rivi_kulu'] != '') and ($trow['oletus_vienti'] == 'C' or $trow['oletus_vienti'] == 'F' or $trow['oletus_vienti'] == 'I') and ($osto_rahti != 0 or $osto_kulu != 0 or $osto_rivi_kulu != 0)) {

								if (!isset($ei_oteta_kulua_pois) or !$ei_oteta_kulua_pois) {

									$summa_vero_chk = $summa * (1 + ($vero / 100));

									list($summa, $_arr) = ostolaskun_kulujen_tiliointi($omasumma, $summa_vero_chk, $osto_rahti, $osto_kulu, $osto_rivi_kulu);

									$summa_vero_chk = $summa * (1 + ($vero / 100));
									$summa_vero_chk = $summa_vero_chk - $summa;

									$summa = $summa - $summa_vero_chk;

									foreach($_arr as $_k => $_v) {
										if (!isset($laskun_kulut[${"{$_k}_alv"}][$_k])) $laskun_kulut[${"{$_k}_alv"}][$_k] = 0;

										$_v = round($_v, 2);

										$laskun_kulut[${"{$_k}_alv"}][$_k] += $_v;
										$totsumma += $_v;
									}
								}
							}

							$verkkolaskuveroton				+= $summa;
							$verkkolaskuveroton_valuutassa	+= $summa_valuutassa;
							$vtonsumma						 = $summa;
							$vtonsumma_valuutassa			 = $summa_valuutassa;
							$vtontot						+= $summa;
							$vtontot_valuutassa				+= $summa_valuutassa;
							$summa						 	 = round($summa * (1+($vero/100)),2);
							$totsumma						+= $summa;
							$totsumma_valuutassa			+= $summa_valuutassa;
							$selite							 = $trow['nimi']." ".$trow['nimitark']." Lasno: ".$laskun_numero." ".$selite;
							$kukarow['yhtio']				 = $yhtio;
							$kukarow['kuka']				 = 'verkkolas';

							require ("inc/teetiliointi.inc");

							// Napataan luodut tarkenteet...
							if ($yhtiorow["kirjanpidon_tarkenteet"] == "K" and (($summa > $maksimisumma and $omasumma >= 0) or ($summa < $maksimisumma and $omasumma < 0))) {
								$ostovel_kustp		= $kustp_ins;
								$ostovel_kohde		= $kohde_ins;
								$ostovel_projekti	= $projekti_ins;
							}

							// jos kyseessä on rahti/huolintakuluja, tiliöidään varastonmuutokseen
							if ($trow['oletus_vienti'] != 'A' and $trow['oletus_vienti'] != 'D' and $trow['oletus_vienti'] != 'G' and $trow['oletus_vienti'] != '') {

								$varastotili = $yhtiorow['varasto'];

								if ($trow['oletus_vienti'] == 'C' or $trow['oletus_vienti'] == 'F' or $trow['oletus_vienti'] == 'I' or $trow['oletus_vienti'] == 'J' or $trow['oletus_vienti'] == 'K' or $trow['oletus_vienti'] == 'L') {
									$varastotili = $yhtiorow['matkalla_olevat'];
								}

								list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastotili, $kustp, $kohde, $projekti);

								// Varasto
								$query = "	INSERT into tiliointi set
											yhtio 			 = '$kukarow[yhtio]',
											ltunnus 		 = '$tunnus',
											tilino 			 = '$varastotili',
											kustp    		 = '{$kustp_ins}',
											kohde	 		 = '{$kohde_ins}',
											projekti 		 = '{$projekti_ins}',
											tapvm 			 = '$tpv-$tpk-$tpp',
											summa 			 = '$vtonsumma',
											summa_valuutassa = '$vtonsumma_valuutassa',
											valkoodi 		 = '$valkoodi',
											selite 			 = '$selite',
											vero 			 = 0,
											lukko 			 = '',
											laatija			 = '$kukarow[kuka]',
											laadittu		 = now()";
								$result = pupe_query($query);

								$varastonmuutostili = $yhtiorow["varastonmuutos"];

								if ($yhtiorow['raaka_ainevarastonmuutos'] != "" and ($trow['oletus_vienti'] == 'J' or $trow['oletus_vienti'] == 'K' or $trow['oletus_vienti'] == 'L')) {
									$varastonmuutostili = $yhtiorow["raaka_ainevarastonmuutos"];
								}

								if ($yhtiorow['varastonmuutos_rahti'] != "" and ($trow['oletus_vienti'] == 'B' or $trow['oletus_vienti'] == 'E' or $trow['oletus_vienti'] == 'H')) {
									$varastonmuutostili = $yhtiorow["varastonmuutos_rahti"];
								}

								list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastonmuutostili, $kustp, $kohde, $projekti);

								// Varastonmuutos
								$query = "	INSERT into tiliointi set
											yhtio 			 = '$kukarow[yhtio]',
											ltunnus 		 = '$tunnus',
											tilino 			 = '$varastonmuutostili',
											kustp    		 = '{$kustp_ins}',
											kohde	 		 = '{$kohde_ins}',
											projekti 		 = '{$projekti_ins}',
											tapvm 			 = '$tpv-$tpk-$tpp',
											summa 			 = $vtonsumma *-1,
											summa_valuutassa = $vtonsumma_valuutassa*-1,
											valkoodi 		 = '$valkoodi',
											selite 			 = '$selite',
											vero 			 = 0,
											lukko 			 = '',
											laatija 		 = '$kukarow[kuka]',
											laadittu 		 = now()";
								$result = pupe_query($query);
							}
						}

						if ($rtuoteno[$i]["tuoteno"] != "" and $laskun_tyyppi == "TECCOM-INVOICE" and $sanomantarkistin != 999 and !$onko_dupli_asn) {

							if (!isset($rtuoteno[$i]['ale1'])) $rtuoteno[$i]['ale1'] = 0;
							if (!isset($rtuoteno[$i]['ale2'])) $rtuoteno[$i]['ale2'] = 0;
							if (!isset($rtuoteno[$i]['ale3'])) $rtuoteno[$i]['ale3'] = 0;
							if (!isset($rtuoteno[$i]['lisakulu'])) $rtuoteno[$i]['lisakulu'] = 0;
							if (!isset($rtuoteno[$i]['kulu'])) $rtuoteno[$i]['kulu'] = 0;

							if ((float) $rtuoteno[$i]["rivihinta"] == 0) $summa_valuutassa = 0;

							$asn_hinta = round($summa_valuutassa/$rtuoteno[$i]['kpl'], 2);

							#echo "Lasketaan nettohintaa...<br>";
							#echo "{$asn_hinta} = round({$summa_valuutassa}/{$rtuoteno[$i]['kpl']}, 2)<br><br>";

							// Laitetaan bruttohinta asn_sanomat-tauluun
							if (isset($rtuoteno[$i]['ale1']) and trim($rtuoteno[$i]['ale1']) != 0) {
								#echo "Ale1 löytyi!<br>";
								#echo "{$asn_hinta} / ((100 - {$rtuoteno[$i]['ale1']}) / 100)<br><br>";
								$asn_hinta = $asn_hinta / ((100 - $rtuoteno[$i]['ale1']) / 100);
							}

							if (isset($rtuoteno[$i]['ale2']) and trim($rtuoteno[$i]['ale2']) != 0) {
								#echo "Ale2 löytyi!<br>";
								#echo "{$asn_hinta} / ((100 - {$rtuoteno[$i]['ale2']}) / 100)<br><br>";
								$asn_hinta = $asn_hinta / ((100 - $rtuoteno[$i]['ale2']) / 100);
							}

							if (isset($rtuoteno[$i]['ale3']) and trim($rtuoteno[$i]['ale3']) != 0) {
								#echo "Ale3 löytyi!<br>";
								#echo "{$asn_hinta} / ((100 - {$rtuoteno[$i]['ale3']}) / 100)<br><br>";
								$asn_hinta = $asn_hinta / ((100 - $rtuoteno[$i]['ale3']) / 100);
							}

							// osto_kulu = $laskun_pakkauskulu
							// osto_rahti = invoiceSummary rahti
							// osto_rivi_kulu  = ($laskun_rahtikulu, $rtuoteno[$i]['kulu'])

							$insertquery = "	INSERT INTO asn_sanomat SET
												asn_numero 			= '{$laskun_numero}',
												hinta 				= '{$asn_hinta}',
												kappalemaara 		= '{$rtuoteno[$i]['kpl']}',
												keikkarivinhinta	= 0,
												kulu 				= '{$rtuoteno[$i]['kulu']}',
												laatija 			= 'crond',
												lahetyslistannro 	= '',
												laji 				= 'tec',
												lasku_ale1			= '{$rtuoteno[$i]['ale1']}',
												lasku_ale2			= '{$rtuoteno[$i]['ale2']}',
												lasku_ale3			= '{$rtuoteno[$i]['ale3']}',
												lisakulu 			= '{$rtuoteno[$i]['lisakulu']}',
												luontiaika 			= now(),
												paketinnumero 		= '',
												paketintunniste 	= '',
												pakkauskulu 		= '{$laskun_pakkauskulu}',
												rahtikulu 			= '{$laskun_rahtikulu}',
												saapumispvm 		= '{$saapumispvm_chk}',
												sscc_koodi			= '',
												status				= '',
												tilausnumero 		= '{$rtuoteno[$i]['ostotilausnro']}',
												tilausrivi 			= '',
												tilausrivinpositio 	= '{$rtuoteno[$i]['tilaajanrivinro']}',
												toimittajanumero 	= '{$laskuttajan_toimittajanumero}',
												toim_tuoteno 		= '{$rtuoteno[$i]['tuoteno']}',
												toim_tuoteno2 		= '{$rtuoteno[$i]['tuoteno2']}',
												tuoteno				= '',
												vastaanottaja 		= '{$ostaja_asiakkaantiedot['nimi']}',
												yhtio 				= '{$yhtio}'";
							#echo "<pre>",str_replace("\t", "", $insertquery),"</pre>";
							$asn_ins_res = pupe_query($insertquery);
							$rtuoteno[$i]["insert_id"] = mysql_insert_id();
							#echo "insert_id: {$rtuoteno[$i]["insert_id"]}<br><br>";
						}

						$selite = $trow['nimi']." ".$trow['nimitark']." Lasno: ".$laskun_numero;
					}

					if ($ei_oteta_kulua_pois and ($osto_kulu != 0 or $osto_rahti != 0 or $osto_rivi_kulu != 0)) {

						$totsumma += ($osto_rahti + $osto_kulu + $osto_rivi_kulu);

						$laskun_kulut = array(
							$vero => array(
							'osto_kulu' => $osto_kulu,
							'osto_rahti' => $osto_rahti,
							'osto_rivi_kulu' => $osto_rivi_kulu
							)
						);
					}

					if (count($laskun_kulut) > 0) {

						foreach ($laskun_kulut as $vero => $_array) {
							foreach ($_array as $key => $value) {

								if ($value == 0) continue;

								$tili = $yhtiorow[$key];
								$summa = $value;

								// Laitetaan kustannuspaikaksi/kohteeksi/projektiksi aina ensimmäisen tiliöintirivin tiedot.
								// Jos menevät nollana, funktio osaa ottaa oletuksen tilin takaa.
								list($kustp, $kohde, $projekti) = kustannuspaikka_kohde_projekti($tili, $kustp, $kohde, $projekti);

								require ("inc/teetiliointi.inc");
							}
						}

						#echo "Tuliko pyöristyseroja $omasumma - $totsumma<br><br>";
					}

	 				// Tuli pyöristyseroja
					if (round(abs($totsumma - $omasumma), 2) >= 0.01) {

						$vero = 0;

						$summa = $omasumma - $totsumma;
						$summa_valuutassa = $omasumma_valuutassa - $totsumma_valuutassa;

						$tili = $yhtiorow['pyoristys'];

						// Jos oletusalvi löytyy ja pyöristysero on yli 4 senttiä, tehdään pyöristyksestä myös alv-kirjaus
						if ($oletusalvi != 0 and round(abs($totsumma - $omasumma), 2) >= 0.05) {
							$vero = $oletusalvi;
						}

						require ("inc/teetiliointi.inc");
					}

					// jos kyseessä on tavaraa
					if ($trow['oletus_vienti'] == 'C' or $trow['oletus_vienti'] == 'F' or $trow['oletus_vienti'] == 'I' or $trow['oletus_vienti'] == 'J' or $trow['oletus_vienti'] == 'K' or $trow['oletus_vienti'] == 'L') {
						/**
						 * Jos Unikkoinvoice, muutetaan oletus_vienti
						 * C => A
						 * F => D
						 * I => G
						*/
						if ($laskun_tyyppi == "UNIKKO-INVOICE" and ($trow['oletus_vienti'] == 'C' or $trow['oletus_vienti'] == 'F' or $trow['oletus_vienti'] == 'I')) {

							if ($trow['oletus_vienti'] == 'C') 		$trow['oletus_vienti'] = 'A';
							elseif ($trow['oletus_vienti'] == 'F') 	$trow['oletus_vienti'] = 'D';
							elseif ($trow['oletus_vienti'] == 'I') 	$trow['oletus_vienti'] = 'G';

							$query = "	UPDATE lasku SET
										vienti = '{$trow['oletus_vienti']}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus  = '{$tunnus}'";
							$vienti_upd_res = pupe_query($query);
						}

						verkkolasku_luo_keikkafile($tunnus, $trow, $rtuoteno);
					}
				}

				// Lisätään vielä ostovelkatiliöinnin tarkenteet
				if ($yhtiorow["kirjanpidon_tarkenteet"] == "K") {
					$ostovel_kustp		= $kustp_ins;
					$ostovel_kohde		= $kohde_ins;
					$ostovel_projekti	= $projekti_ins;
				}
				else {
					$ostovel_kustp		= 0;
					$ostovel_kohde		= 0;
					$ostovel_projekti	= 0;
				}

				list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($ostovelat, $ostovel_kustp, $ostovel_kohde, $ostovel_projekti);

				$query = "	UPDATE tiliointi SET
							kustp    = '{$kustp_ins}',
							kohde	 = '{$kohde_ins}',
							projekti = '{$projekti_ins}'
							WHERE yhtio = '{$yhtio}'
							AND tunnus  = '{$ostovelkatiliointitunnus}'";
				$vienti_upd_res = pupe_query($query);


				// Jos meillä on suoraveloitus
				if ($trow['oletus_suoraveloitus'] != '') {

					if ($trow['oletus_suoravel_pankki'] > 0) {
						 //Toimittajalla on pankkitili, teemme eräpäivälle suorituksen valmiiksi

						list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($ostovelat);

						// Oletustiliöinnit
						// Ostovelat
						$query = "	INSERT into tiliointi set
									yhtio 			 = '$kukarow[yhtio]',
									ltunnus 		 = '$tunnus',
									tilino			 = '$ostovelat',
									kustp    		 = '{$kustp_ins}',
									kohde	 		 = '{$kohde_ins}',
									projekti 		 = '{$projekti_ins}',
									tapvm 			 = '$erv-$erk-$erp',
									summa 			 = '$omasumma',
									summa_valuutassa = '$omasumma_valuutassa',
									valkoodi 		 = '$valkoodi',
									vero 			 = 0,
									lukko 			 = '',
									laatija 		 = '$kukarow[kuka]',
									laadittu		 = now()";
						$xresult = pupe_query($query);

						list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["selvittelytili"]);

						// Rahatili
						$query = "	INSERT into tiliointi set
									yhtio 			 = '$kukarow[yhtio]',
									ltunnus 		 = '$tunnus',
									tilino 			 = '$yhtiorow[selvittelytili]',
									kustp    		 = '{$kustp_ins}',
									kohde	 		 = '{$kohde_ins}',
									projekti 		 = '{$projekti_ins}',
									tapvm 			 = '$erv-$erk-$erp',
									summa 			 = $vassumma,
									summa_valuutassa = '$vassumma_valuutassa',
									valkoodi 		 = '$valkoodi',
									vero 			 = 0,
									lukko 			 = '',
									laatija 		 = '$kukarow[kuka]',
									laadittu 		 = now()";
						$xresult = pupe_query($query);

						if ($tila == 'M') {
							$query = "	UPDATE lasku set
										tila 	= 'Y',
										mapvm 	= '$erv-$erk-$erp',
										maksu_kurssi = 1
										WHERE tunnus = '$tunnus'";
							$xresult = pupe_query($query);
						}
					}
					else {
					 	// Tämä koskee vain suoraveloitusta
						if ($tila == 'M') {
							$query = "	UPDATE lasku set
										tila = 'Q'
										WHERE tunnus='$tunnus'";
							$xresult = pupe_query($query);
						}
					}
				}

				$query = "SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
				$kuvalaskures = pupe_query($query);

				if (mysql_num_rows($kuvalaskures) > 0) {

					$kuvalaskurow = mysql_fetch_assoc($kuvalaskures);

					$ebid 	= $kuvalaskurow['ebid'];
					$selite = mysql_real_escape_string("$kuvalaskurow[nimi] $kuvalaskurow[summa] $kuvalaskurow[valkoodi]");

					// Tallennetaan laskun XML liitetiedostoksi
					$filesize = strlen($xmlstr);
					$filename = array_pop(explode("/", $file));
					$xmlstr   = mysql_real_escape_string($xmlstr);

					if ($ebid == "FINVOICEXML") {
						$kayttotarkoitus = 'FINVOICE';
					}
					elseif ($ebid == "TECCOM-INVOICE") {
						$kayttotarkoitus = 'TECCOM-INVOICE';
						/*
						Tiedoston alkuun pitää lisätä nämä tiedot, kun saadaan xsl ja dtd
						<?xml version="1.0" encoding="utf-8"?>
						<!DOCTYPE Finvoice SYSTEM "datain/teccom.dtd">
						<?xml-stylesheet type="text/xsl" href="datain/teccom.xsl"?>
						*/
					}
					elseif ($ebid == "UNIKKO-INVOICE") {
						$kayttotarkoitus = 'UNIKKO-INVOICE';
					}
					else {
						$kayttotarkoitus = 'INVOIC.D.96A';
					}

					$query = "	INSERT INTO liitetiedostot SET
								yhtio    			= '$kukarow[yhtio]',
								liitos   			= 'lasku',
								liitostunnus 		= '$tunnus',
								data     			= '$xmlstr',
								selite   			= '$selite',
								kieli				= '$yhtiorow[kieli]',
								filename 			= '$filename',
								filesize 			= '$filesize',
								filetype 			= 'text/xml',
								image_width			= '',
								image_height		= '',
								image_bits			= '',
								image_channels		= '',
								kayttotarkoitus		= '$kayttotarkoitus',
								jarjestys			= '1',
								laatija				= '$kukarow[kuka]',
								luontiaika			= now()";
					$Xresult = pupe_query($query);

					// Haetaan verkkolaskun visualisoitu versio
					if ($kayttotarkoitus == "INVOIC.D.96A") {
						$verkkolaskutunnus = $yhtiorow['verkkotunnus_vas'];
						$salasana		   = $yhtiorow['verkkosala_vas'];

						$timestamppi = gmdate("YmdHis")."Z";

						$urlhead = "http://www.verkkolasku.net";
						$urlmain = "/view/ebs-2.0/$verkkolaskutunnus/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$ebid&TIMESTAMP=$timestamppi&VERSION=ebs-2.0";

						$digest	 = md5($urlmain . "&" . $salasana);
						$url	 = $urlhead.$urlmain."&DIGEST=$digest";

						$sisalto = file_get_contents($url);

						if (substr($sisalto,0,4) == "%PDF") {
							// tallennetaan kuva
							$filename = $ebid.".pdf";

							//filesize functio ei toimi koska ei ole oikea file.
							$filesize = strlen($sisalto);
							$data  = mysql_real_escape_string($sisalto);

							// lisätään kuva
							$query = "	INSERT INTO liitetiedostot SET
										yhtio    			= '$kukarow[yhtio]',
										liitos   			= 'lasku',
										liitostunnus 		= '$tunnus',
										data     			= '$data',
										selite   			= '$selite',
										kieli				= '$yhtiorow[kieli]',
										filename 			= '$file',
										filesize 			= '$filesize',
										filetype 			= 'application/pdf',
										image_width			= '',
										image_height		= '',
										image_bits			= '',
										image_channels		= '',
										kayttotarkoitus		= 'VERKKOLASKU',
										jarjestys			= '1',
										laatija				= '$kukarow[kuka]',
										luontiaika			= now()";
							$Xresult = pupe_query($query);
						}
					}

					// Maventa- tai APIX-lasku, niin yritetään hakea laskun kuva mukaan
					if (preg_match("/(maventa|apix)_(.*?)_(maventa|apix)/", basename($file), $match)) {

						// Haetaan liitteet
						unset($liitefilet);
						$liitteet = exec("find $verkkolaskut_orig -name \"*{$match[1]}_{$match[2]}_{$match[1]}*\"", $liitefilet);

						if ($liitteet != "") {
							foreach ($liitefilet as $liitefile) {
								if (strtoupper(substr($liitefile, -4)) == ".PDF") {

									$data = file_get_contents($liitefile);
									$filesize = strlen($data);

									$data = mysql_real_escape_string($data);

									$filename = str_replace("maventa_{$match[2]}_maventa-", "", basename($liitefile));

									// Lisätään kuva
									$query = "	INSERT INTO liitetiedostot SET
												yhtio    			= '$kukarow[yhtio]',
												liitos   			= 'lasku',
												liitostunnus 		= '$tunnus',
												data     			= '$data',
												selite   			= '$selite',
												kieli				= '$yhtiorow[kieli]',
												filename 			= '$filename',
												filesize 			= '$filesize',
												filetype 			= 'application/pdf',
												image_width			= '',
												image_height		= '',
												image_bits			= '',
												image_channels		= '',
												kayttotarkoitus		= 'VERKKOLASKU',
												jarjestys			= '1',
												laatija				= '$kukarow[kuka]',
												luontiaika			= now()";
									$Xresult = pupe_query($query);
								}
							}
						}
					}

					if (trim($liitetaanko_editilaus_laskulle_hakemisto) != '' and trim($laskun_asiakkaan_tilausnumero) != '') {

						// fopen dirikka $liitetaanko_editilaus_laskulle_hakemisto eli purchaseorderit
						$files_editilaus_laskulle = listdir($liitetaanko_editilaus_laskulle_hakemisto);

						// loopataan se läpi
						foreach ($files_editilaus_laskulle as $files_editilaus_laskulle_x) {

							$path_parts = pathinfo($files_editilaus_laskulle_x);
							$editilaus_laskulle_polku = substr($liitetaanko_editilaus_laskulle_hakemisto, -1) !== '/' ? $liitetaanko_editilaus_laskulle_hakemisto.'/' : $liitetaanko_editilaus_laskulle_hakemisto;

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
									$editilaus_laskulle_result = pupe_query($query);

									// Katotaan onko eroja vai onko ok
									list($invoice, $purchaseorder, $invoice_ei_loydy, $purchaseorder_ei_loydy, $loytyy_kummastakin, $purchaseorder_tilausnumero) = laskun_ja_tilauksen_vertailu($kukarow, $tunnus);

									if ($invoice != FALSE and $invoice != 'ei_loydy_edia') {
										if (count($purchaseorder_ei_loydy) == 0 and count($invoice_ei_loydy) == 0 and count($loytyy_kummastakin) > 0) {
											$ok = 'ok';

											foreach ($loytyy_kummastakin as $tuoteno => $null) {
												if (substr($tuoteno, 0, 15) != "Ei_tuotekoodia_" and ($invoice[$tuoteno]['tilattumaara'] != $purchaseorder[$tuoteno]['tilattumaara'] or abs($invoice[$tuoteno]['nettohinta'] - $purchaseorder[$tuoteno]['nettohinta']) > 1)) {
													$ok = '';
													break;
												}
											}

											if ($ok == 'ok' and !$onko_dupli_lasku) {
												// Ei eroja, laitetaan lasku suoraan maksuvalmiiksi
												$query = "	UPDATE lasku set
															tila 		  = 'M',
															hyvak1 		  = 'verkkolas',
															h1time 		  = now(),
															hyvak2 		  = '',
															hyvak3 		  = '',
															hyvak4 		  = '',
															hyvak5 		  = '',
															hyvaksyja_nyt = '',
															comments 	  = trim(concat(comments, '\n Lasku siirrettiin suoraan maksuvalmiiksi, koska se täsmäsi ostotilaukseen.'))
															WHERE tunnus = '$tunnus'";
												$xresult = pupe_query($query);
											}
										}
									}

									// ulos loopista
									break;
								}
							}
						}
					}
				}

				// päästiin onnillisesti loppuun
		    	$meili = "Verkkolasku $verkapunimi yritykselle $yhtiorow[nimi]\nTiedosto: $file\nLähettäjä: $laskuttajan_nimi\nLaskunro: $laskun_numero\nPäivä: $laskun_tapvm\nSumma: $laskun_summa_eur\nViite: $laskun_pankkiviite\nEnsimmäinen hyväksyjä: $hyvaksyja_nyt\n$laskuvirhe\n";

				$tulos = mail($yhtiorow['talhal_email'], mb_encode_mimeheader("$yhtiorow[nimi] vastaanotti laskun $verkapunimi:lta", "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");

				if ($tulos === FALSE) echo t("Sähköpostin lähetys epäonnistui").": $yhtiorow[talhal_email]<br>\n";
				return 0;
			}
			else {
				return array($yhtiorow, $trow);
			}
		}
	}
