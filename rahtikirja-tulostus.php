<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-tulostus.php") !== FALSE) {
		require("inc/parametrit.inc");
	}

	$logistiikka_yhtio = '';
	$logistiikka_yhtiolisa = '';
	if (!isset($unifaun_era_vainkollitarra)) $unifaun_era_vainkollitarra = FALSE;

	if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
		$logistiikka_yhtio = $konsernivarasto_yhtiot;
		$logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

		if ($lasku_yhtio != '') {
			$kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);
			$yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
		}
	}
	else {
		$logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
	}

	if (!isset($nayta_pdf)) echo "<font class='head'>",t("Rahtikirjojen tulostus"),"</font><hr>";

	$laskutettu = "";

	if (!isset($tee)) {
		$tee = '';
	}

	// Katostaan kuuluuko tulostaa rahtikirja vai kutsua Unifaunin _closeWithPrinter-metodia
	if ($tee == 'tulosta') {

		list($toimitustapa, $yhtio, $varasto, $crap) = explode("!!!!", $toimitustapa_varasto);

		if ($logistiikka_yhtio != '') {
			$kukarow['yhtio'] = $yhtio;
		}

		$toimitustapa 	= mysql_real_escape_string(trim($toimitustapa));
		$varasto 		= (int) $varasto;

		// haetaan toimitustavan tiedot
		$query = "	SELECT *
					FROM toimitustapa
					WHERE yhtio = '$kukarow[yhtio]'
					AND selite = '$toimitustapa'";
		$toitares = pupe_query($query);
		$toitarow = mysql_fetch_assoc($toitares);

		// Ollaan tässä skriptissä tulostamassa erärahtikirjoja
		// Unifaun keississä tämä tarkoittaa, että kutsutaan _closeWithPrinter() metodia
		if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and isset($tulosta_rahtikirjat_nappulatsukka) and ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' or $toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc')) {
			$tee = "close_with_printer";
		}
	}

	// Tulostetaan rahtikirja tai kutsutaan unifaunin _closeWithPrinter-metodia
	if ($tee == 'tulosta' or $tee == 'close_with_printer') {

		if ($toitarow['tulostustapa'] == 'L' and $toitarow['uudet_pakkaustiedot'] == 'K' and $tultiin != 'koonti_eratulostus_pakkaustiedot' and strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) {

			$linkkilisa = '';

			if (count($sel_ltun) > 0) {
				foreach ($sel_ltun as $ltun_x) {
					$linkkilisa .= "&sel_ltun[]=$ltun_x";
				}
			}

			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL={$palvelin2}rahtikirja.php?toim=lisaa&id=dummy&jv=$jv&komento=$komento&merahti=$toitarow[merahti]&mista=rahtikirja-tulostus.php&toimitustapa_varasto=$toimitustapa_varasto&valittu_rakiroslapp_tulostin={$valittu_rakiroslapp_tulostin}&rakirsyotto_dgd_tulostin={$rakirsyotto_dgd_tulostin}&dgdkpl={$dgdkpl}{$linkkilisa}'>";
			exit;
		}

		// haetaan rahtikirjan tyyppi
		$query = "	SELECT *
					from avainsana
					where yhtio = '$kukarow[yhtio]'
					and laji 	= 'RAHTIKIRJA'
					and selite 	= '$toitarow[rahtikirja]'";
		$avainres = pupe_query($query);
		$avainrow = mysql_fetch_assoc($avainres);

		// haetaan printterin tiedot
		if (isset($laskurow)) {
			if ($laskurow['pakkaamo'] > 0 and $laskurow['varasto'] != '' and $laskurow['tulostusalue'] != '') {
				$query = "	SELECT pakkaamo.printteri2, pakkaamo.printteri4, pakkaamo.printteri6
							from pakkaamo
							where pakkaamo.yhtio='$kukarow[yhtio]'
							and pakkaamo.tunnus='$laskurow[pakkaamo]'
							order by pakkaamo.tunnus";
			}
			elseif ($laskurow['tulostusalue'] != '' and $laskurow['varasto'] != '') {
				$query = "	SELECT varaston_tulostimet.printteri2, varaston_tulostimet.printteri4, varaston_tulostimet.printteri6
							FROM varaston_tulostimet
							WHERE varaston_tulostimet.yhtio = '$kukarow[yhtio]'
							AND varaston_tulostimet.nimi = '$laskurow[tulostusalue]'
							AND varaston_tulostimet.varasto = '$laskurow[varasto]'
							ORDER BY varaston_tulostimet.prioriteetti, varaston_tulostimet.alkuhyllyalue";
			}
			else {
				$query = "	SELECT *
							FROM varastopaikat
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus 	= '$varasto'";
			}
		}
		else {
			$query = "	SELECT *
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus = '$varasto'";
		}

		$pres  = pupe_query($query);
		$print = mysql_fetch_assoc($pres);

		if ($komento == "PDF_RUUDULLE") {
			$kirjoitin = "PDF_RUUDULLE";
		}
		elseif ($komento != "") {
			$kirjoitin_tunnus = (int) $komento; // jos ollaan valittu oma printteri
		}
		elseif ($avainrow["selitetark_2"] == "1") {
			$kirjoitin_tunnus = $print["printteri6"]; // Rahtikirja A4
		}
		elseif ($avainrow["selitetark_2"] == "2") {
			$kirjoitin_tunnus = $print["printteri4"]; // Rahtikirja A5
		}
		elseif ($avainrow["selitetark_2"] == "3") {
			$kirjoitin_tunnus = $print["printteri2"]; // Rahtikirja matriisi
		}
		elseif ($toitarow['tulostustapa'] == 'H') {
			$kirjoitin_tunnus = $print["printteri4"]; // Rahtikirja A5
		}
		elseif (strpos($toitarow['rahtikirja'],'pdf') === false) {
			$kirjoitin_tunnus = $print["printteri2"]; // Rahtikirja matriisi
		}
		else {
			$kirjoitin_tunnus = $print["printteri6"]; // Rahtikirja A4
		}

		if ($komento != "PDF_RUUDULLE") {
			// haetaan printterille tulostuskomento
			$query = "SELECT * from kirjoittimet where tunnus = '$kirjoitin_tunnus'";
			$pres  = pupe_query($query);
			$print = mysql_fetch_assoc($pres);

			$kirjoitin = $print['komento'];
			$merkisto  = $print['merkisto'];
		}

		$pvm = date("j.n.Y");

		if ($valittu_rakiroslapp_tulostin != '') {
			//haetaan osoitelapun tulostuskomento
			if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-tulostus.php") !== FALSE or strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") !== FALSE) {
				$query  = "SELECT * from kirjoittimet where tunnus = '$valittu_rakiroslapp_tulostin'";
				$kirres = pupe_query($query);
				$kirrow = mysql_fetch_assoc($kirres);
				$oslapp = $kirrow['komento'];
			}
		}

		if ($rakirsyotto_dgd_tulostin != "") {
			//haetaan DGD-lomakkeen tulostuskomento
			$query  = "SELECT * from kirjoittimet where yhtio = '{$kukarow['yhtio']}' and tunnus = '{$rakirsyotto_dgd_tulostin}'";
			$kirres = pupe_query($query);
			$kirrow = mysql_fetch_assoc($kirres);
			$dgdkomento = $kirrow['komento'];
		}
		else {
			$dgdkomento = "";
		}

		if ($kirjoitin == '') die (t("Valitsemallesi varastolle ole ole määritelty tarvittavaa rahtikirja-tulostinta")." ($mika)!");

		if (!isset($nayta_pdf)) echo "<font class='message'>".t("Tulostetaan rahtikirjat toimitustavalle").": $toimitustapa<br>".t("Kirjoitin").": $print[kirjoitin]</font><hr>";

		if (isset($vain_tulostus) and $vain_tulostus != '') $vain_tulostus = $print['kirjoitin'];

		$query = "	LOCK TABLES
					asiakas READ,
					avainsana as a READ,
					avainsana as avainsana_kieli READ,
					avainsana as b READ,
					avainsana READ,
					dynaaminen_puu AS node READ,
					dynaaminen_puu AS parent READ,
					etaisyydet READ,
					kirjoittimet READ,
					kuka READ,
					lasku WRITE,
					laskun_lisatiedot READ,
					liitetiedostot READ,
					maat READ,
					maksuehto READ,
					pakkauskoodit READ,
					pankkiyhteystiedot READ,
					puun_alkio READ,
					rahtikirjanumero WRITE,
					rahtikirjat AS r1 READ,
					rahtikirjat AS r2 READ,
					rahtikirjat WRITE,
					rahtimaksut READ,
					rahtisopimukset READ,
					sanakirja WRITE,
					tapahtuma WRITE,
					tilausrivi WRITE,
					tilausrivin_lisatiedot READ,
					tiliointi WRITE,
					toimitustapa READ,
					tuote WRITE,
					tuotepaikat WRITE,
					tuotteen_alv READ,
					vak READ,
					vak_imdg READ,
					varaston_tulostimet READ,
					varastopaikat READ,
					yhteyshenkilo READ,
					yhtion_parametrit READ,
					yhtion_toimipaikat READ,
				 	pakkaus READ,
 					rahdinkuljettajat READ";
		$res = pupe_query($query);

		if ($jv == 'vainjv') {
			if (!isset($nayta_pdf)) echo t("Vain jälkivaatimukset").".";
			$jvehto = " having jv!='' ";
		}
		elseif ($jv == 'eivj') {
			if (!isset($nayta_pdf)) echo t("Ei jälkivaatimuksia").".";
			$jvehto = " having jv='' ";
		}
		elseif ($jv == 'vainvak') {
			if (!isset($nayta_pdf)) echo t("Vain VAK").". ";
			$vainvakilliset = " JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
								JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.vakkoodi not in ('','0')) ";
		}
		else {
			$jvehto = " ";
		}

		$ltun_querylisa = '';

		if (count($sel_ltun) > 0) {
			$ltun_querylisa = " and lasku.tunnus in (".implode(",", $sel_ltun).")";
		}
	}

	// Kutsutaan unifaunin _closeWithPrinter-metodia
	if ($tee == 'close_with_printer') {

		require_once("inc/unifaun_send.inc");

		$query = "	SELECT lasku.tunnus, lasku.toimitustavan_lahto, lasku.toimitustapa, lasku.ytunnus, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, asiakas.toimitusvahvistus, group_concat(DISTINCT rahtikirjat.tunnus) ratunnarit, sum(rahtikirjat.kilot) kilot
					FROM rahtikirjat
					JOIN lasku on (rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio and lasku.tila in ('L','G'))
					$ltun_querylisa
					$vainvakilliset
					LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
					LEFT JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus)
					LEFT JOIN rahtisopimukset ON (lasku.ytunnus = rahtisopimukset.ytunnus and rahtikirjat.toimitustapa = rahtisopimukset.toimitustapa and rahtikirjat.rahtisopimus = rahtisopimukset.rahtisopimus)
					WHERE rahtikirjat.yhtio			= '$kukarow[yhtio]'
					AND rahtikirjat.tulostettu		= '0000-00-00 00:00:00'
					AND rahtikirjat.toimitustapa	= '$toimitustapa'
					AND rahtikirjat.tulostuspaikka	= '$varasto'
					$jvehto
					GROUP BY 1,2,3,4,5,6,7,8
					ORDER BY lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, rahtikirjat.merahti, rahtikirjat.rahtisopimus, lasku.tunnus";
		$rakir_res = pupe_query($query);

		$mergeid_arr = array();

		while ($row = mysql_fetch_assoc($rakir_res)) {
			$mergeid = md5(date("Ymd").$row["ytunnus"].$row["toim_osoite"].$row["toim_postino"].$row["toim_postitp"]);
			$mergeid_arr[$mergeid] = $mergeid;

			echo t("Suljetaan rahtikirja").": $row[tunnus] $row[toim_osoite] $row[toim_postino] $row[toim_postitp]<br>";

			// merkataan tilausrivit toimitetuiksi, paitsi kun tulostetaan Unifaun erätulostuksen kollitarra
			if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) {
				$query = "	UPDATE tilausrivi
							SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
							WHERE otunnus  = '$row[tunnus]'
							AND yhtio 	   = '$kukarow[yhtio]'
							AND var not in ('P','J')
							AND keratty    != ''
							AND toimitettu  = ''
							AND tyyppi 	 	= 'L'";
				$ures  = pupe_query($query);

				$query = "	UPDATE rahtikirjat
							SET tulostettu = now()
							WHERE tunnus  IN ($row[ratunnarit])
							AND yhtio 	   = '$kukarow[yhtio]'
							AND tulostettu = '0000-00-00 00:00:00'";
				$ures  = pupe_query($query);

				// kotimaan myynti menee alatilaan D
				$query = "UPDATE lasku set alatila = 'D' where tunnus = '{$row['tunnus']}' and vienti = '' and yhtio='$kukarow[yhtio]'";
				$ures  = pupe_query($query);

				// vientilaskut menee alatilaan B
				$query = "UPDATE lasku set alatila = 'B' where tunnus = '{$row['tunnus']}' and vienti != '' and yhtio='$kukarow[yhtio]'";
				$ures  = pupe_query($query);

				// jos laskulla on maksupositioita, menee ne alatilaan J
				$query = "UPDATE lasku set alatila = 'J' where tunnus = '{$row['tunnus']}' and jaksotettu != 0 and yhtio='$kukarow[yhtio]'";
				$ures  = pupe_query($query);

				// verkkolaskutettavat EU-viennit menee alatilaan D, jos niillä on tarpeeksi lisätietoja
				$query = "	UPDATE lasku set
							alatila = 'D',
							bruttopaino = '{$row['kilot']}'
							where yhtio = '$kukarow[yhtio]'
							and tunnus  = '{$row['tunnus']}'
							and vienti  = 'E'
							and chn     = '020'
							and maa_maara != ''
							and kauppatapahtuman_luonne > 0
							and kuljetusmuoto != ''";
				$ures  = pupe_query($query);

				// Etukäteen maksetut tilaukset pitää muuttaa takaisin "maksettu"-tilaan
				$query = "	UPDATE lasku SET
							alatila = 'X'
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus = '{$row['tunnus']}'
							AND mapvm != '0000-00-00'
							AND chn = '999'";
				$ures  = pupe_query($query);

			}
		}

		$query = "	SELECT unifaun_nimi
					FROM kirjoittimet
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus  = '{$kirjoitin_tunnus}'";
		$kires = pupe_query($query);
		$kirow = mysql_fetch_assoc($kires);

		foreach ($mergeid_arr as $mergeid) {

			if ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") {
				$unifaun = new Unifaun($unifaun_ps_host, $unifaun_ps_user, $unifaun_ps_pass, $unifaun_ps_path, $unifaun_ps_port, $unifaun_ps_fail, $unifaun_ps_succ);
			}
			elseif ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "") {
				$unifaun = new Unifaun($unifaun_uo_host, $unifaun_uo_user, $unifaun_uo_pass, $unifaun_uo_path, $unifaun_uo_port, $unifaun_uo_fail, $unifaun_uo_succ);
			}

			$unifaun->_closeWithPrinter($mergeid, $kirow['unifaun_nimi']);
			$unifaun->ftpSend();
		}

		// poistetaan lukko
		$query = "UNLOCK TABLES";
		$res   = pupe_query($query);

		echo "<br>";

		$tee = '';
	}

	// Tulostetaan rahtikirja
	if ($tee == 'tulosta') {

		// haetaan kaikki distinct rahtikirjat..
		$query = "	SELECT distinct lasku.ytunnus, lasku.toim_maa, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_ovttunnus, lasku.toim_postino, lasku.toim_postitp,
					lasku.maa, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.ovttunnus, lasku.postino, lasku.postitp,
					rahtikirjat.merahti, rahtikirjat.rahtisopimus, if(maksuehto.jv is null,'',maksuehto.jv) jv, lasku.alv, lasku.vienti, rahtisopimukset.muumaksaja,
					asiakas.toimitusvahvistus, if(asiakas.gsm != '', asiakas.gsm, if(asiakas.tyopuhelin != '', asiakas.tyopuhelin, if(asiakas.puhelin != '', asiakas.puhelin, ''))) puhelin
					FROM rahtikirjat
					JOIN lasku on (rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio and lasku.tila in ('L','G') ";

		if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) {
			$query .= " and lasku.alatila = 'B' ";
		}

		$query .= " $ltun_querylisa)
					$vainvakilliset
					LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
					LEFT JOIN maksuehto on lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
					LEFT JOIN rahtisopimukset on lasku.ytunnus = rahtisopimukset.ytunnus and rahtikirjat.toimitustapa = rahtisopimukset.toimitustapa and rahtikirjat.rahtisopimus = rahtisopimukset.rahtisopimus
					WHERE rahtikirjat.yhtio	= '$kukarow[yhtio]' ";

		// Jos keräyserät ja lähdöt on päällä, niin Unifaun 'hetitulostus' tilaukset merkataan tässä toimitetuiksi ja siksi näin
		if (isset($lahetetaanko_unifaun_heti) and $lahetetaanko_unifaun_heti !== FALSE) {
			$query .= " and rahtikirjat.tulostettu	!= '0000-00-00 00:00:00' ";
		}
		elseif (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) {
			$query .= " and rahtikirjat.tulostettu	= '0000-00-00 00:00:00' ";
		}

		$query .= "	and rahtikirjat.toimitustapa	= '$toimitustapa'
					and rahtikirjat.tulostuspaikka	= '$varasto'
					$jvehto
					ORDER BY lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, rahtikirjat.merahti, rahtikirjat.rahtisopimus, lasku.tunnus";
		$rakir_res = pupe_query($query);

		if (mysql_num_rows($rakir_res) == 0) {
			if (!isset($nayta_pdf)) echo "<font class='message'>".t("Yhtään tulostettavaa rahtikirjaa ei löytynyt").".</font><br><br>";
		}

		$kopiotulostuksen_otsikot = array();

		$kaikki_lotsikot_per_toimitus = '';
		while ($rakir_row = mysql_fetch_assoc($rakir_res)) {
			// muutama muuttuja tarvitaan
			$pakkaus       		= array();
			$pakkauskuvaus 		= array();
			$pakkauskuvaustark 	= array();
			$kuljetusohjeet 	= "";
			$kilot         		= array();
			$kollit        		= array();
			$kuutiot       		= array();
			$lavametri     		= array();
			$lotsikot      		= array();
			$astilnrot			= array();
			$vakit         		= array();
			$shipment_idt  		= array();
			$kilotyht      		= 0;
			$lavatyht      		= 0;
			$kollityht     		= 0;
			$kuutiotyht    		= 0;
			$tulostuskpl   		= 0;
			$otunnukset    		= "";
			$tunnukset     		= "";
			$rahtikirjanro 		= "";
			$kaikki_lotsikot 	= "";

			if ($rakir_row['merahti'] == 'K') {
				$rahdinmaksaja = "Lähettäjä";
			}
			else {
				$rahdinmaksaja = "Vastaanottaja"; //tämä on defaultti
			}

			// Katsotaan onko tämä koontikuljetus
			if ($toitarow["tulostustapa"] == "L" or $toitarow["tulostustapa"] == "K") {
				// Monen asiakkaan rahtikirjat tulostuu aina samalle paperille
				$asiakaslisa = " ";

				//Toimitusosoitteeksi halutaan tässä tapauksessa toimitustavan takaa löytyvät
				$rakir_row["toim_maa"]		= $toitarow["toim_maa"];
				$rakir_row["toim_nimi"]		= $toitarow["toim_nimi"];
				$rakir_row["toim_nimitark"]	= $toitarow["toim_nimitark"];
				$rakir_row["toim_osoite"]	= $toitarow["toim_osoite"];
				$rakir_row["toim_postino"]	= $toitarow["toim_postino"];
				$rakir_row["toim_postitp"]	= $toitarow["toim_postitp"];

			}
			else {
				// Normaalissa keississä ainoastaan saman toimitusasiakkaan kirjat menee samalle paperille
				$asiakaslisa = "and lasku.ytunnus			= '$rakir_row[ytunnus]'
								and lasku.toim_maa			= '$rakir_row[toim_maa]'
								and lasku.toim_nimi			= '$rakir_row[toim_nimi]'
								and lasku.toim_nimitark		= '$rakir_row[toim_nimitark]'
								and lasku.toim_osoite		= '$rakir_row[toim_osoite]'
								and lasku.toim_ovttunnus	= '$rakir_row[toim_ovttunnus]'
								and lasku.toim_postino		= '$rakir_row[toim_postino]'
								and lasku.toim_postitp		= '$rakir_row[toim_postitp]' ";
			}

			if ($rakir_row['jv'] != '') {
				$jvehto = " having jv!='' ";
			}
			else {
				$jvehto = " having jv='' ";
			}

			// haetaan tälle rahtikirjalle kuuluvat tunnukset
			$query = "	SELECT rahtikirjat.rahtikirjanro, rahtikirjat.tunnus rtunnus, lasku.tunnus otunnus, merahti, lasku.ytunnus, if(maksuehto.jv is null,'',maksuehto.jv) jv, lasku.asiakkaan_tilausnumero
						FROM rahtikirjat
						JOIN lasku ON (rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio and lasku.tila in ('L','G') ";

			if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) {
				$query .= " and lasku.alatila = 'B' ";
			}

			$query .= " $ltun_querylisa)
						LEFT JOIN maksuehto on lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
						WHERE rahtikirjat.yhtio			= '$kukarow[yhtio]' ";

			// Jos keräyserät ja lähdöt on päällä, niin Unifaun 'hetitulostus' tilaukset merkataan tässä toimitetuiksi ja siksi näin
			if (isset($lahetetaanko_unifaun_heti) and $lahetetaanko_unifaun_heti !== FALSE) {
				$query .= " and rahtikirjat.tulostettu	!= '0000-00-00 00:00:00' ";
			}
			elseif (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) {
				// Normaalisti tämä ehto estää sen, että koontirahtikirjasta ei tuu yhtä monta kopiota kuin tilausten määrä koontirahtikirjalla.
				$query .= " and rahtikirjat.tulostettu	= '0000-00-00 00:00:00' ";
			}
			else {
				// Kopiotulostuksessa joudumme tekee sen näin
				if (count($kopiotulostuksen_otsikot) > 0) {
					$query .= " and lasku.tunnus not in (".implode(",", $kopiotulostuksen_otsikot).") ";
				}
			}

			$query .= "	and rahtikirjat.toimitustapa	= '$toimitustapa'
						and rahtikirjat.tulostuspaikka	= '$varasto'
						$asiakaslisa
						and rahtikirjat.merahti			= '$rakir_row[merahti]'
						and rahtikirjat.rahtisopimus	= '$rakir_row[rahtisopimus]'
						$jvehto
						ORDER BY lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, rahtikirjat.merahti, rahtikirjat.rahtisopimus, lasku.tunnus";
			$res   = pupe_query($query);

			while ($rivi = mysql_fetch_assoc($res)) {

				// Kopiotulostuksen tulostetut otsikot
				$kopiotulostuksen_otsikot[$rivi["otunnus"]] = $rivi["otunnus"];

				// otetaan kaikki otsikkonumerot ja rahtikirjanumerot talteen... tarvitaan myöhemmin hauissa
				$otunnukset   .= "'$rivi[otunnus]',";
				$tunnukset    .= "'$rivi[rtunnus]',";

				// otsikkonumerot talteen, nämä printataan paperille
				if (!in_array($rivi['otunnus'], $lotsikot)) {
					$lotsikot[] 	= $rivi['otunnus'];
					$astilnrot[]	= $rivi['asiakkaan_tilausnumero'];
				}
				// otetaan jokuvaan rtunnus talteen uniikisi numeroksi
				// tarvitaan postin rahtikirjoissa
				$rtunnus = $rivi["rtunnus"];
			}

			if (mysql_num_rows($res) > 0) {
				mysql_data_seek($res,0);

				$rivi = mysql_fetch_assoc($res);

				//vikat pilkut pois
				$otunnukset = substr($otunnukset,0,-1);
				$tunnukset  = substr($tunnukset,0,-1);

				$vanhat_tunnukset = $tunnukset;

				if ($yhtiorow['rahtikirjan_kollit_ja_lajit'] != "") {
					$groupby_lisa = ", tunnus";
				}
				else {
					$groupby_lisa = "";
				}

				$pakkaustieto_tunnukset = '';

				if ($toitarow['tulostustapa'] == 'L' and $toitarow['uudet_pakkaustiedot'] == 'K' and $tultiin == 'koonti_eratulostus_pakkaustiedot' and trim($pakkaustieto_rahtikirjanro) != '') {
					$query = "	SELECT group_concat(tunnus) pakkaustieto_tunnukset
								FROM rahtikirjat
								WHERE yhtio = '$kukarow[yhtio]'
								AND rahtikirjanro = '$pakkaustieto_rahtikirjanro'
								AND pakkaustieto_tunnukset != ''";
					$koonti_era_pakkaustieto_tunnus_res = pupe_query($query);
					$koonti_era_pakkaustieto_tunnus_row = mysql_fetch_assoc($koonti_era_pakkaustieto_tunnus_res);

					if (trim($koonti_era_pakkaustieto_tunnus_row['pakkaustieto_tunnukset']) != '') {
						$tunnukset = $pakkaustieto_tunnukset = $koonti_era_pakkaustieto_tunnus_row['pakkaustieto_tunnukset'];
					}
				}

				// Summataan kaikki painot yhteen
				$query = "	SELECT pakkaus, pakkauskuvaus, pakkauskuvaustark,
							sum(kilot) kilot, sum(kollit) kollit, sum(kuutiot) kuutiot, sum(lavametri) lavametri,
							group_concat(tunnus SEPARATOR '_') shipment_id
							FROM rahtikirjat
							WHERE tunnus in ($tunnukset)
							AND yhtio = '$kukarow[yhtio]'
							GROUP BY pakkaus, pakkauskuvaus, pakkauskuvaustark $groupby_lisa
							ORDER BY pakkaus, pakkauskuvaus, pakkauskuvaustark";
				$pakka = pupe_query($query);

				while ($pak = mysql_fetch_assoc($pakka)) {

					if ($pak["kilot"] > 0 or $pak["kollit"] > 0) {
						$kilot[]     			= $pak["kilot"];
						$kollit[]    			= $pak["kollit"];
						$pakkaus[]   			= $pak["pakkaus"];
						$pakkauskuvaus[]   		= $pak["pakkauskuvaus"];
						$pakkauskuvaustark[]   	= $pak["pakkauskuvaustark"];
						$kuutiot[]   			= $pak["kuutiot"];
						$lavametri[] 			= $pak["lavametri"];
						$shipment_idt[] 		= $pak["shipment_id"];
					}

					$kilotyht   += $pak["kilot"];
					$kollityht  += $pak["kollit"];
					$kuutiotyht += $pak["kuutiot"];
					$lavatyht   += $pak["lavametri"];
				}

				// Kuljetusohjeet
				$query = "	SELECT trim(group_concat(DISTINCT viesti SEPARATOR ' ')) viesti
							FROM rahtikirjat
							WHERE tunnus in ($tunnukset)
							AND yhtio = '$kukarow[yhtio]'";
				$pakka = pupe_query($query);
				$pak = mysql_fetch_assoc($pakka);

				$kuljetusohjeet = $pak["viesti"];

				$tulostuskpl = $kollityht;

				if ($toitarow['tulostustapa'] == 'L' and $toitarow['uudet_pakkaustiedot'] == 'K' and $tultiin == 'koonti_eratulostus_pakkaustiedot' and trim($pakkaustieto_rahtikirjanro) != '') {
					// merkataan rahtikirjat tulostetuksi..
					if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) {
						$query = "	UPDATE rahtikirjat
									SET tulostettu = now()
									WHERE tunnus  IN ($tunnukset)
									AND yhtio 	   = '$kukarow[yhtio]'
									AND tulostettu = '0000-00-00 00:00:00'";
						$ures  = pupe_query($query);
					}

					// käytetään tästä alaspäin vanhoja tunnuksia
					$tunnukset = $vanhat_tunnukset;
				}

				// merkataan tilausrivit toimitetuiksi, paitsi kun tulostetaan Unifaun erätulostuksen kollitarra
				if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and !$unifaun_era_vainkollitarra) {
					$query = "	UPDATE tilausrivi
								SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
								WHERE otunnus in ($otunnukset)
								AND yhtio 	   = '$kukarow[yhtio]'
								AND var not in ('P','J')
								AND keratty    != ''
								AND toimitettu  = ''
								AND tyyppi 	 	= 'L'";
					$ures  = pupe_query($query);
				}

				// Käytetäänkö VAK-tietokantaa
				if ($yhtiorow["vak_kasittely"] != "") {
					$vakselect = "concat_ws(', ', concat('UN',yk_nro), nimi_ja_kuvaus, if(trim(lipukkeet)='', NULL, lipukkeet), if(trim(pakkausryhma)='', NULL, pakkausryhma), if(trim(luokituskoodi)='', NULL, luokituskoodi), if(trim(Rajoitetut_maarat_ja_poikkeusmaarat_1)='', NULL, Rajoitetut_maarat_ja_poikkeusmaarat_1))";
					$vakjoin   = "JOIN vak ON tuote.yhtio = vak.yhtio and tuote.vakkoodi = vak.tunnus";
				}
				else {
					$vakselect = "tuote.vakkoodi";
					$vakjoin   = "";
				}

				// Haetaan kaikki vakkoodit arrayseen
				$query = "	SELECT $vakselect vakkoodi,
							round(sum(tuote.tuotemassa*(tilausrivi.kpl+tilausrivi.varattu)), 1) tuote_kilot,
							sum(if(tuote.tuotemassa=0, 1, 0)) tuotemassattomat
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.vakkoodi not in ('','0'))
							$vakjoin
							where tilausrivi.otunnus in ($otunnukset)
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.var in ('','H')
							and tilausrivi.tyyppi in ('L','G')
							GROUP BY 1
							ORDER BY 1";
				$vres = pupe_query($query);

				if (mysql_num_rows($vres) > 0) {
					while ($vak = mysql_fetch_assoc($vres)) {

						if ($vak["tuotemassattomat"] == 0) {
							$vak["vakkoodi"] .= " ($vak[tuote_kilot] kg)";
						}

						$vakit[] = $vak["vakkoodi"];
					}
				}

				// nyt on kaikki tiedot rahtikirjaa varten haettu..
				//
				// arrayt:
				// toitarow, otsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
				// $rakir_row:sta löytyy asiakkaan tiedot
				//
				////ja $rivi:stä ytunnus
				//
				// muuttujat:
				// otunnukset, pvm, rahdinmaksaja, toimitustapa, kolliyht, kilotyht, kuutiotyht, kirjoitin
				// jv tapauksissa on myös aputeksti, rahtihinta, rahdinhinta, yhteensa, summa, jvhinta, jvtext, lasno ja viite muuttujat
				// rtunnus jossa on uniikki numero
				//
				// tulostetaan rahtikirja

				// merkataan rahtikirjat tulostetuksi..
				if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and !$unifaun_era_vainkollitarra) {
					$query = "	UPDATE rahtikirjat
								SET tulostettu = now()
								WHERE tunnus  IN ($tunnukset)
								AND yhtio 	   = '$kukarow[yhtio]'
								AND tulostettu = '0000-00-00 00:00:00'";
					$ures  = pupe_query($query);
				}

				// näitä tarvitaan vain JV-keiseissa, mutta pitää nollata tietty joka luupilla
				$lasno		= "";
				$viite		= "";
				$summa		= "";
				$jvtext		= "";
				$jvhinta	= "";
				$yhteensa	= "";
				$aputeksti	= "";
				$rahinta 	= "";

				// jos kyseessä on jälkivaatimus
				if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and $rakir_row['jv'] != '') {
					$tee 			= "TARKISTA";
					$laskutakaikki 	= "KYLLA";
					$silent		 	= "KYLLA";
					$laskutettavat 	= $otunnukset;

					if ($rakirsyotto_laskutulostin != '') {
						$valittu_tulostin = $rakirsyotto_laskutulostin;
						$chnlisa = ", chn = '667' ";
					}
					else {
						$valittu_tulostin = "";
						$chnlisa = "";
					}

					$query = "	UPDATE lasku
								set alatila='D'
								$chnlisa
								where tunnus in ($laskutettavat)
								and yhtio = '$kukarow[yhtio]'";
					$result = pupe_query($query);

					$rivitunnukset = $tunnukset;
					chdir('tilauskasittely');

					require("verkkolasku.php");

					chdir('../');
					$tunnukset = $rivitunnukset;

					// Nämä muuttujat tulevat toivottavasti ulos verkkolasku.php:stä
					// $jvhinta jossa on jälkivaatimuskulut
					// $rahinta jossa on rahtikulut
					// $laskurow jossa on laskutetun laskun tiedot
					// $viite jossa on viitenumero

					$yhteensa = $laskurow['summa'];
					$summa    = $laskurow['summa'] - $jvhinta - $rahinta;

					$jvtext  = "<li>".t("Jälkivaatimuskulu").": $jvhinta $yhtiorow[valkoodi]";
					$jvtext .= "<li>".t("Loppusumma yhteensä").": $yhteensa $yhtiorow[valkoodi]";

					$aputeksti = t("JÄLKIVAATIMUS");
				}
				elseif (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") !== FALSE and $rakir_row['jv'] != '') {

					// Nämä muuttujat tulevat toivottavasti ulos verkkolasku.php:stä
					// $jvhinta jossa on jälkivaatimuskulut
					// $rahinta jossa on rahtikulut
					// $laskurow jossa on laskutetun laskun tiedot

					$query = "	SELECT *
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$rivi[otunnus]'";
					$lasres = pupe_query($query);
					$laskurow = mysql_fetch_array($lasres);

					$viite = $laskurow["viite"];

					$yhteensa = $laskurow['summa'];
					$summa    = $laskurow['summa'] - $jvhinta - $rahinta;

					$jvtext  = "<li>".t("Jälkivaatimuskulu").": $jvhinta $yhtiorow[valkoodi]";
					$jvtext .= "<li>".t("Loppusumma yhteensä").": $yhteensa $yhtiorow[valkoodi]";

					$aputeksti = t("JÄLKIVAATIMUS");
				}

				if (!isset($nayta_pdf)) echo "<font class='message'>".t("Asiakas")." $rakir_row[toim_nimi]</font><li>".t("Yhdistetään tilaukset").": ";

				foreach ($lotsikot as $doit) {
					if (!isset($nayta_pdf)) echo "$doit ";
					$kaikki_lotsikot .= $doit.", ";
					$kaikki_lotsikot_per_toimitus .= $doit . ", ";
				}

				$kaikki_lotsikot = substr($kaikki_lotsikot ,0 ,-2);

				if (!isset($nayta_pdf)) echo "$rahinta $jvtext<br>";

				// Kopsutulostus toistaiseksi vain A4-paperille unifaun keississä
				if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") !== FALSE and ($toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' or $toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc')) {
					$toitarow["rahtikirja"] = "rahtikirja_pdf.inc";
				}

				// Jos keräyserät ja lähdöt on päällä, niin tässä ei tulosteta rahtikirjaa jos Unifaun on käytössä
				if (!isset($tee_varsinainen_tulostus) or (isset($tee_varsinainen_tulostus) and $tee_varsinainen_tulostus)) {

					// tulostetaan toimitustavan määrittelemä rahtikirja
					if (@include("tilauskasittely/$toitarow[rahtikirja]")) {

						// Otetaan talteen tässä $rahtikirjanro talteen
						$rahtikirjanro_alkuperainen = $rahtikirjanro;

						if ($tulosta_vak_yleisrahtikirja != '') {
							require("tilauskasittely/rahtikirja_pdf.inc");
						}

						if ($toitarow['erittely'] == 'k') {
							require("tilauskasittely/rahtikirja_erittely_pdf.inc");
						}

						// palautetaan alkuperäinen $rahtikirjanro takaisin
						$rahtikirjanro = $rahtikirjanro_alkuperainen;
					}
					else {
						if (!isset($nayta_pdf)) echo "<li><font class='error'>".t("VIRHE: Rahtikirja-tiedostoa")." 'tilauskasittely/$toitarow[rahtikirja]' ".t("ei löydy")."!</font>";
					}

					// Kopsuille ei päivitetä eikä kun muokataan rahtikirjan tietoja!
					if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and (!isset($muutos) or $muutos != 'yes')) {
						$query = "	UPDATE rahtikirjat
									set rahtikirjanro = '$rahtikirjanro'
									where tunnus in ($tunnukset)
									and yhtio = '$kukarow[yhtio]'";
						$ures  = pupe_query($query);

						if (trim($pakkaustieto_tunnukset) != '') {
							$query = "	UPDATE rahtikirjat
										set rahtikirjanro = '$rahtikirjanro'
										where tunnus in ($pakkaustieto_tunnukset)
										and yhtio = '$kukarow[yhtio]'";
							$ures  = pupe_query($query);
						}
					}
				}

				if ($rakir_row['toimitusvahvistus'] != '') {

					$tulostauna = "";

					if ($rakir_row["toimitusvahvistus"] == "toimitusvahvistus_desadv_una.inc") {
						$tulostauna = "KYLLA";
						$rakir_row["toimitusvahvistus"] = "toimitusvahvistus_desadv.inc";
					}

					if (file_exists("tilauskasittely/$rakir_row[toimitusvahvistus]")) {
						require("tilauskasittely/$rakir_row[toimitusvahvistus]");
					}
				}

				// Katsotaan onko magento-API konffattu, eli verkkokauppa käytössä, silloin merkataan tilaus toimitetuksi Magentossa kun rahtikirja tulostetaan
				if (isset($magento_api_url) and $magento_api_url != "" and $magento_api_usr != "" and  $magento_api_pas != "") {
					$query = "	SELECT asiakkaan_tilausnumero
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus  IN ($otunnukset)
								AND laatija = 'Magento'
								AND asiakkaan_tilausnumero != ''";
					$mageres = pupe_query($query);

					while ($magerow = mysql_fetch_assoc($mageres)) {
						$magento_api_met = $toitarow['virallinen_selite'] != '' ? $toitarow['virallinen_selite'] : $toitarow['selite'];
						$magento_api_rak = $rahtikirjanro;
						$magento_api_ord = $magerow["asiakkaan_tilausnumero"];

						require("magento_toimita_tilaus.php");
					}
				}

				// Tulostetaan DGD
				if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-tulostus.php") !== FALSE and $rakirsyotto_dgd_tulostin != "" and $dgdkomento != '' and $dgdkpl > 0) {

					$query = "	SELECT *
								FROM lasku
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$rivi['otunnus']}'";
					$laskures = pupe_query($query);
					$laskurow = mysql_fetch_assoc($laskures);

					if ($dgdkpl > 0 and $dgdkpl != '' and $dgdkomento != 'email') {
						$dgdkomento .= " -#{$dgdkpl} ";
					}

					require ("tilauskasittely/tulosta_dgd.inc");

					$params_dgd = array(
					'kieli'			=> 'en',
					'laskurow'		=> $laskurow,
					'page'			=> NULL,
					'pdf'			=> NULL,
					'row'			=> NULL,
					'sivu'			=> 0,
					'tee'			=> $tee,
					'toim'			=> $toim,
					'norm'			=> $norm,
					);

					// Aloitellaan lähetteen teko
					$params_dgd = alku_dgd($params_dgd);
					$params_dgd = rivi_dgd($params_dgd);
					$params_dgd = loppu_dgd($params_dgd);

					//tulostetaan sivu
					$params_dgd["komento"] = $dgdkomento;
					print_pdf_dgd($params_dgd);
				}

				// jos ei JV merkataan rahtikirjat tulostetuksi otsikollekkin E-tyyppiset Unifaunit merkataan toimitetuiksi close_with_printer-haarassa..
				if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE and $rakir_row['jv'] == '' and !$unifaun_era_vainkollitarra) {

					// kotimaan myynti menee alatilaan D
					$query = "UPDATE lasku set alatila = 'D' where tunnus in ($otunnukset) and vienti = '' and yhtio='$kukarow[yhtio]'";
					$ures  = pupe_query($query);

					// vientilaskut menee alatilaan B
					$query = "UPDATE lasku set alatila = 'B' where tunnus in ($otunnukset) and vienti != '' and yhtio='$kukarow[yhtio]'";
					$ures  = pupe_query($query);

					// jos laskulla on maksupositioita, menee ne alatilaan J
					$query = "UPDATE lasku set alatila = 'J' where tunnus in ($otunnukset) and jaksotettu != 0 and yhtio='$kukarow[yhtio]'";
					$ures  = pupe_query($query);

					// verkkolaskutettavat EU-viennit menee alatilaan D, jos niillä on tarpeeksi lisätietoja
					$query = "	UPDATE lasku set
								alatila = 'D',
								bruttopaino = '$kilotyht'
								where yhtio = '$kukarow[yhtio]'
								and tunnus in ($otunnukset)
								and vienti = 'E'
								and chn = '020'
								and maa_maara != ''
								and kauppatapahtuman_luonne > 0
								and kuljetusmuoto != ''";
					$ures  = pupe_query($query);

					// Etukäteen maksetut tilaukset pitää muuttaa takaisin "maksettu"-tilaan
					$query = "	UPDATE lasku SET
								alatila = 'X'
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus in ($otunnukset)
								AND mapvm != '0000-00-00'
								AND chn = '999'";
					$ures  = pupe_query($query);
				}

				// Tulostetaan osoitelappu
				if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-tulostus.php") !== FALSE or strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") !== FALSE) {
					if ($valittu_rakiroslapp_tulostin != "" and $oslapp != '' and $kollityht > 0) {

						$rahtikirja_tulostus = "yep";
						$tunnus = $lotsikot[0];

						$query = "	SELECT GROUP_CONCAT(distinct if(viesti!='',viesti,NULL) separator '. ') viesti
									from lasku
									where yhtio = '$kukarow[yhtio]'
									and tunnus	in ($otunnukset)";
						$viestirar = pupe_query($query);
						$viestirarrow = mysql_fetch_assoc($viestirar);

						$oslappkpl = $kollityht;

						if (($toitarow["tulostustapa"] == "L" or $toitarow["tulostustapa"] == "K") and $toitarow["toim_nimi"] != '') {
							$tiedot = "toimitusta";
							$toimitustaparow = $toitarow;
						}

						if ($toitarow['osoitelappu'] == 'intrade') {
							require('tilauskasittely/osoitelappu_intrade_pdf.inc');
						}
						else {
							require ("tilauskasittely/osoitelappu_pdf.inc");
						}
					}
				}
			}
			if (!isset($nayta_pdf) and strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) echo "<br>";
		} // end while haetaan kaikki distinct rahtikirjat..


		if ($toitarow['erittely'] == 't') {
			$kaikki_lotsikot_per_toimitus = substr($kaikki_lotsikot_per_toimitus ,0 ,-2);//poistetaan pilkku ja välilyönti viimosen perästä
			$otunnukset_temp = $otunnukset;
			$otunnukset = $kaikki_lotsikot_per_toimitus;
			require("tilauskasittely/rahtikirja_erittely_pdf.inc");
			$otunnukset = $otunnukset_temp;
		}
		// poistetaan lukko
		$query = "UNLOCK TABLES";
		$res   = pupe_query($query);

		if (isset($nayta_pdf)) {
			$tee = "SKIPPAA";
		}
		elseif (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) {
			if ($toitarow['tulostustapa'] == 'H' or $toitarow['tulostustapa'] == 'K' or $toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' or $toitarow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc') {
				$tee = 'XXX';
			}
			else {
				$tee = '';
			}
		}

		if (!isset($nayta_pdf)) echo "<br>";
	}

	if (!isset($tee)) {
		$tee = '';
	}

	if ($tee == '') {

		echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
				<!--
				function disableEnterKey(e)
				{
					var key;

					if (window.event) {
						key = window.event.keyCode;     //IE
					}
					else {
						key = e.which;     //firefox
					}

					if (key == 13) {
						document.getElementById('etsi_button').focus();
						return false;
					}
					else {
						return true;
					}
				}

				function untoggleAll(toggleBox, param) {

					var currForm = toggleBox.form;
					var isChecked = toggleBox.checked;
					var selectMenu = document.getElementById('toimitustapa_varasto');
					var chosenOption = selectMenu.options[selectMenu.selectedIndex];
					var chosenOptionValue = chosenOption.value;
					var tableObject = document.getElementById('toim_table');
					var edOpt = document.getElementById('edOpt');

					for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
						if (currForm.elements[elementIdx].type == 'checkbox') {
							currForm.elements[elementIdx].checked = false;
						}

						if (currForm.elements[elementIdx].name == 'toimitustapa_varasto') {
							var value = chosenOptionValue.substr(0,chosenOptionValue.indexOf('!!!!'));
							value = value.replace(/^\s*/, '').replace(/\s*$/, '');
							if (edOpt.value != value) {
								document.getElementById(edOpt.value).style.display='none';
							}
							document.getElementById('nayta_rahtikirjat').checked = false;
						}
					}
				}

				function naytaTunnukset(data) {
					var currForm = data.form;
					var selectMenu = document.getElementById('toimitustapa_varasto');
					var chosenOption = selectMenu.options[selectMenu.selectedIndex];
					var chosenOptionValue = chosenOption.value;

					for (var elementIdx = 0; elementIdx < currForm.elements.length; elementIdx++) {
						if (currForm.elements[elementIdx].name == 'toimitustapa_varasto') {
							var value = chosenOptionValue.substr(0,chosenOptionValue.indexOf('!!!!'));
							value = value.replace(/^\s*/, '').replace(/\s*$/, '');
							document.getElementById(value).style.display='inline';
							document.getElementById('edOpt').value = value;
						}
					}
				}

				function showNumber(data) {
					var currForm = data.form;
					var etsi_value = currForm.etsi_nro.value;
					var nro_etsi = 'nro_'+etsi_value;

					if (etsi_value != '') {
						for (var elementIdx = 0; elementIdx < currForm.elements.length; elementIdx++) {
							if (currForm.elements[elementIdx].name == 'div_nro') {
								if (currForm.elements[elementIdx].value == etsi_value) {
									document.getElementById(nro_etsi).style.display = 'inline';
								}
								else {
									document.getElementById('nro_'+currForm.elements[elementIdx].value).style.display = 'none';
								}
							}
						}
					}
				}

				function showNumbers(data) {
					var currForm = data.form;
					document.getElementById('etsi_nro').value = '';

					for (var elementIdx = 0; elementIdx < currForm.elements.length; elementIdx++) {
						if (currForm.elements[elementIdx].name == 'div_nro') {
							document.getElementById('nro_'+currForm.elements[elementIdx].value).style.display = 'inline';
						}
					}
				}

				//-->
				</script>";

		$wherelisa = '';

		if (!isset($resetti)) {
			$resetti = '';
		}

		if ($resetti != '') {
			$etsi_nro2 = '';
		}

		if (!isset($etsi_nro2)) {
			$etsi_nro2 = '';
		}

		if (trim($etsi_button2) != '' and trim($etsi_nro2) != '') {
			$etsi_nro2 = (int) $etsi_nro2;
			$wherelisa = " and lasku.tunnus = $etsi_nro2 ";
		}

		// haetaan kaikki distinct toimitustavat joille meillä on rahtikirjoja tulostettavana..
		$query = "	SELECT lasku.yhtio yhtio, lasku.toimitustapa, varastopaikat.tunnus, varastopaikat.nimitys, varastopaikat.printteri7, group_concat(distinct lasku.tunnus ORDER BY lasku.tunnus ASC) ltunnus
					from rahtikirjat
					join lasku on rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio and lasku.tila in ('L','G') and lasku.alatila = 'B'
					join toimitustapa on lasku.yhtio = toimitustapa.yhtio
					and lasku.toimitustapa = toimitustapa.selite
					and toimitustapa.tulostustapa in ('E','L')
					and toimitustapa.nouto = ''
					left join maksuehto on lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
					left join varastopaikat on varastopaikat.yhtio=rahtikirjat.yhtio and varastopaikat.tunnus=rahtikirjat.tulostuspaikka
					where rahtikirjat.tulostettu = '0000-00-00 00:00:00'
					and rahtikirjat.$logistiikka_yhtiolisa
					$wherelisa
					GROUP BY lasku.yhtio, lasku.toimitustapa, varastopaikat.tunnus, varastopaikat.nimitys, varastopaikat.printteri7
					ORDER BY varastopaikat.tunnus, lasku.toimitustapa";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			echo "<table><tr><td>";

			echo "<table><tr><th valign='top'>",t("Etsi numerolla"),":</th>";
			echo "<form method='post'>"; // document.getElementById('sel_rahtikirjat').style.display='inline';document.getElementById('sel_td').className='';
			echo "<td valign='top'><input type='text' value='$etsi_nro2' name='etsi_nro2' id='etsi_nro2'>&nbsp;<input type='submit' id='etsi_button2' name='etsi_button2' value='",t("Etsi"),"'>&nbsp;<input type='submit' id='resetti' name='resetti' value='",t("Tyhjennä"),"'></td>";
			echo "</form>";
			echo "</tr>";

			echo "<form method='post'>";
			echo "<input type='hidden' name='tee' value='tulosta'>";
			echo "<input type='hidden' name='edOpt' id='edOpt' value=''>";

			echo "<tr><th>",t("Valitse toimitustapa"),":</th>";
			echo "<td valign='top'><select name='toimitustapa_varasto' id='toimitustapa_varasto' onchange=\"untoggleAll(this);document.getElementById('sel_rahtikirjat').style.display='none';document.getElementById('sel_td').className='back';document.getElementById('kirjoitin').options.selectedIndex=document.getElementById('K'+this.value.substr(this.value.indexOf('!!!!!')+5)).index;\">";

			$toimitustapa_lask_tun = '';

			while ($rakir_row = mysql_fetch_assoc($result)) {
				if ($rakir_row['toimitustapa'] != '') {
					$sel = "";

					if ((isset($kukarow["varasto"]) and (int) $kukarow["varasto"] > 0 and in_array($rakir_row["tunnus"], explode(",", $kukarow['varasto']))) and $varasto == "") {
						$sel = "selected";
						$varasto = $rakir_row["tunnus"];
					}

					echo "<option value='$rakir_row[toimitustapa]!!!!$rakir_row[yhtio]!!!!$rakir_row[tunnus]!!!!!$rakir_row[printteri7]' $sel>$rakir_row[nimitys] - $rakir_row[toimitustapa]";

					if ($logistiikka_yhtio != '') {
						echo " ($rakir_row[yhtio])";
					}

					echo "</option>";
				}
			}

			echo "</select></td>";
			echo "</tr>";

			echo "<tr><th>".t("Tulosta kaikki rahtikirjat").":</th>";
			echo "<td><input type='radio' name='jv' value='' checked></td></tr>";

			echo "<tr><th>".t("Tulosta vain jälkivaatimukset").":</th>";
			echo "<td><input type='radio' name='jv' value='vainjv'></td></tr>";

			echo "<tr><th>".t("Älä tulosta jälkivaatimuksia").":</th>";
			echo "<td><input type='radio' name='jv' value='eijv'></td></tr>";

			echo "<tr><th>".t("Tulosta vain rahtikirjoja joilla on VAK-koodeja").":</th>";
			echo "<td><input type='radio' name='jv' id='jv' value='vainvak'></td></tr>";

			echo "<tr><th>".t("Valitse jälkivaatimuslaskujen tulostuspaikka").":</th>";
			echo "<td><select id='kirjoitin' name='laskukomento'>";
			echo "<option value=''>",t("Ei kirjoitinta"),"</option>";

			$query = "SELECT printteri7 FROM varastopaikat WHERE $logistiikka_yhtiolisa and tunnus='$varasto'";
			$jvres = pupe_query($query);
			$jvrow = mysql_fetch_assoc($jvres);

			$e = $jvrow["printteri7"];
			$sel = array();
			$sel[$e] = "SELECTED";

			$query = "	SELECT komento, min(kirjoitin) kirjoitin, min(tunnus) tunnus
						FROM kirjoittimet
						WHERE $logistiikka_yhtiolisa
						AND komento != 'EDI'
						GROUP BY komento
						ORDER BY kirjoitin";
			$kires = pupe_query($query);

			while ($kirow = mysql_fetch_assoc($kires)) {
				echo "<option id='K$kirow[tunnus]' value='$kirow[komento]' ".$sel[$kirow["tunnus"]].">$kirow[kirjoitin]</option>";
			}

			echo "</select></td></tr>";

			echo "<tr><th>",t("Valitse tulostin"),":</th>";
			echo "<td><select name='komento'>";
			echo "<option value='' SELECTED>",t("Oletustulostimelle"),"</option>";

			mysql_data_seek($kires, 0);

			while ($kirow = mysql_fetch_assoc($kires)) {
				echo "<option id='K$kirow[tunnus]' value='$kirow[tunnus]'>$kirow[kirjoitin]</option>";
			}

			echo "</select></td></tr>";

			echo "<tr><th>",t("Tulosta osoitelaput"),"</th>";

			mysql_data_seek($kires, 0);

			echo "<td>";
			echo "<select name='valittu_rakiroslapp_tulostin'>";
			echo "<option value=''>",t("Ei tulosteta"),"</option>";

			while ($kirrow = mysql_fetch_assoc($kires)) {
				echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
			}

			echo "</select></td></tr>";

			echo "<tr><th>",t("DGD-lomake"),"</th><td>";
			echo "<select name='rakirsyotto_dgd_tulostin'>";
			echo "<option value=''>",t("Ei tulosteta"),"</option>";

			mysql_data_seek($kires, 0);

			while ($kirrow = mysql_fetch_assoc($kires)) {
				$sel = "";
				if (isset($rakirsyotto_dgd_tulostin) and $kirrow['tunnus'] == $rakirsyotto_dgd_tulostin) {
					$sel = "SELECTED";
				}

				echo "<option value='{$kirrow['tunnus']}' {$sel}>{$kirrow['kirjoitin']}</option>";
			}

			if (!isset($dgdkpl)) $dgdkpl = 1;

			echo "</select>&nbsp;",t("Kpl"),": <input type='text' size='4' name='dgdkpl' value='{$dgdkpl}'></td></tr>";

			echo "</table>";
			echo "</td>";

			echo "<td valign='top'><table><tr>";

			if (!isset($checked_chk)) {
				$checked_chk = '';
			}

			if (!isset($nayta_div)) {
				$nayta_div = '';
			}

			if (!isset($class)) {
				$class = '';
			}

			if ($etsi_button2 != '' and $etsi_nro2 != '') {
				$checked_chk = 'checked';
				$nayta_div = '';
				$class = '';
			}
			else {
				$nayta_div = "style='display:none'";
				$checked_chk = '';
				$class = 'back';
			}

			echo "<td valign='top'><input type='checkbox' name='nayta_rahtikirjat' id='nayta_rahtikirjat' $checked_chk onclick=\"document.getElementById('etsi_button').focus();if(document.getElementById('nayta_rahtikirjat').checked==true){document.getElementById('sel_rahtikirjat').style.display='inline';document.getElementById('sel_td').className='';naytaTunnukset(this);}else{untoggleAll(this);document.getElementById('sel_rahtikirjat').style.display='none';document.getElementById('sel_td').className='back';}\"> Valitse rahtikirjat</td>";

			echo "</tr><tr>";
			echo "<td valign='top' class='$class' id='sel_td'><div id='sel_rahtikirjat' $nayta_div>";
			echo "<table id='toim_table' name='toim_table'><tr><td valign='top'>",t("Etsi numerolla"),": <input type='input' name='etsi_nro' id='etsi_nro' onkeypress=\"return disableEnterKey(event);\"> <input type='button' name='etsi_button' id='etsi_button' value='",t("Etsi"),"' onclick='untoggleAll(this);document.getElementById(\"nayta_rahtikirjat\").checked=true;showNumber(this);'> <input type='button' name='etsi_kaikki' id='etsi_kaikki' value='",t("Näytä kaikki"),"' onclick='untoggleAll(this);document.getElementById(\"nayta_rahtikirjat\").checked=true;showNumbers(this);'></td></tr>";

			mysql_data_seek($result, 0);

			while ($asdf_row = mysql_fetch_assoc($result)) {

				echo "<tr><td valign='top'>";
				echo "<div id='$asdf_row[toimitustapa]' $nayta_div>";
				echo $asdf_row['toimitustapa'];

				$ltun = array();

				$ltun_temp = array();
				$ltun_temp = explode(",",$asdf_row['ltunnus']);

				foreach ($ltun_temp as $tun) {
					$ltun[$tun] = $tun;
				}

				unset($ltun_temp);

				echo "<table id='table_$asdf_row[toimitustapa]' name='table_$asdf_row[toimitustapa]'><tr>";
				$i = 0;
				foreach ($ltun as $tun) {
					echo "<td valign='top'><input type='hidden' name='div_nro' value='$tun'><div id='nro_$tun' name='nro_$tun'><input type='checkbox' name='sel_ltun[]' id='sel_$asdf_row[toimitustapa]*$tun' value='$tun' $checked_chk> $tun</div></td>";
					if ($i >= 4) {
						echo "</tr>";
						$i = 0;
					}
					else {
						$i++;
					}
				}
				echo "</tr></table>";
				echo "</div></td></tr>";
			}
			echo "</table>";
			echo "</div></td>";

			echo "</tr></table>";
			echo "</td></tr>";
			echo "</table>";

			echo "<br>";
			echo "<input type='submit' name='tulosta_rahtikirjat_nappulatsukka' value='",t("Tulosta rahtikirjat"),"'>";
			echo "</form>";
		}
		else {
			echo "<br><br><br><font class='message'>",t("Yhtään tulostettavaa rahtikirjaa ei löytynyt"),".</font><br><br>";
		}

		require("inc/footer.inc");
	}
