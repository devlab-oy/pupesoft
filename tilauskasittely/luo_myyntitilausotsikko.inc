<?php

if(!function_exists("luo_myyntitilausotsikko")) {
	function luo_myyntitilausotsikko($toim, $asiakasid, $tilausnumero = '', $myyjanro = '', $viesti = '', $kantaasiakastunnus = '', $ohjelmamoduli = '') {
		global $kukarow, $yhtiorow, $session;

		$query	= "	SELECT *
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$asiakasid'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$asiakasrow  = mysql_fetch_assoc($result);

			if(empty($asiakasrow['maksuehto'])) {
					$query = "	SELECT *
								FROM maksuehto
								WHERE yhtio = '{$kukarow['yhtio']}'
								ORDER BY jarjestys ASC
								LIMIT 1";
					$result = pupe_query($query);

					$maksuehto_row = mysql_fetch_assoc($result);
					$asiakasrow['maksuehto'] = $maksuehto_row['tunnus'];
			}

			$ytunnus 		   = $asiakasrow["ytunnus"];
			$nimi 			   = $asiakasrow["nimi"];
			$nimitark 		   = $asiakasrow["nimitark"];
			$osoite 		   = $asiakasrow["osoite"];
			$postino 		   = $asiakasrow["postino"];
			$postitp 		   = $asiakasrow["postitp"];
			$maa 			   = $asiakasrow["maa"];
			$tnimi 			   = $asiakasrow["toim_nimi"];
			$tnimitark 		   = $asiakasrow["toim_nimitark"];
			$tosoite 		   = $asiakasrow["toim_osoite"];
			$tpostino 		   = $asiakasrow["toim_postino"];
			$tpostitp 		   = $asiakasrow["toim_postitp"];
			$toim_maa 		   = $asiakasrow["toim_maa"];
			$laskutus_nimi	   = $asiakasrow["laskutus_nimi"];
			$laskutus_nimitark = $asiakasrow["laskutus_nimitark"];
			$laskutus_osoite   = $asiakasrow["laskutus_osoite"];
			$laskutus_postino  = $asiakasrow["laskutus_postino"];
			$laskutus_postitp  = $asiakasrow["laskutus_postitp"];
			$laskutus_maa	   = $asiakasrow["laskutus_maa"];
			$kolm_nimi		   = $asiakasrow["kolm_nimi"];
			$kolm_nimitark	   = $asiakasrow["kolm_nimitark"];
			$kolm_osoite	   = $asiakasrow["kolm_osoite"];
			$kolm_postino	   = $asiakasrow["kolm_postino"];
			$kolm_postitp	   = $asiakasrow["kolm_postitp"];
			$kolm_maa		   = $asiakasrow["kolm_maa"];
			$kolm_ovttunnus	   = $asiakasrow["kolm_maa"];
			$verkkotunnus 	   = $asiakasrow["verkkotunnus"];
			$piiri 			   = $asiakasrow["piiri"];

			$kohde  = "";
			$eilahe = "";

			if ($kukarow['extranet'] != '' and $kukarow['hyvaksyja'] != "") {
				$eilahe = "o";
			}

			if (isset($asiakasrow["spec_ytunnus"]) and $asiakasrow["spec_ytunnus"] != '') {
				$ytunnus 				= $asiakasrow["spec_ytunnus"];
				$asiakasrow["ytunnus"] 	= $asiakasrow["spec_ytunnus"];
			}

			if (isset($asiakasrow["spec_tunnus"]) and $asiakasrow["spec_tunnus"] != '') {
				$asiakasid 				= $asiakasrow["spec_tunnus"];
				$asiakasrow["tunnus"] 	= $asiakasrow["spec_tunnus"];
			}

			$toimvv = date("Y");
			$toimkk = date("m");
			$toimpp = date("d");

			$kervv = date("Y");
			$kerkk = date("m");
			$kerpp = date("d");


			if ($toim == "TARJOUS" or $asiakasrow["vienti"] =='K') {
				$kolm_nimi 		= $asiakasrow["kolm_nimi"];
				$kolm_nimitark 	= $asiakasrow["kolm_nimitark"];
				$kolm_osoite 	= $asiakasrow["kolm_osoite"];
				$kolm_postino 	= $asiakasrow["kolm_postino"];
				$kolm_postitp 	= $asiakasrow["kolm_postitp"];
			}

			$maksuehto = $asiakasrow["maksuehto"];

			if ($myyjanro != "") {
				$apuqu = "	SELECT *
							from kuka use index (yhtio_myyja)
							where yhtio = '$kukarow[yhtio]'
							and myyja = '$myyjanro'
							AND myyja > 0";
				$meapu = pupe_query($apuqu);

				if (mysql_num_rows($meapu)==1) {
					$apuro = mysql_fetch_assoc($meapu);
					$myyja = $apuro['tunnus'];
				}
				elseif (mysql_num_rows($meapu)>1) {
					echo "<font class='error'>".t("Syöttämäsi myyjänumero")." $myyjanro ".t("löytyi usealla käyttäjällä")."!</font><br><br>";
					$myyja = 0;
				}
				else {
					echo "<font class='error'>".t("Syöttämäsi myyjänumero")." $myyjanro ".t("ei löytynyt")."!</font><br><br>";
					$myyja = 0;
				}
			}

			if (!isset($myyja) or $myyja == 0) {
				$myyja = $kukarow["tunnus"];
			}

			$alv 				= $asiakasrow["alv"];
			$ovttunnus 			= $asiakasrow["ovttunnus"];
			$toim_ovttunnus 	= $asiakasrow["toim_ovttunnus"];
			$chn 				= $asiakasrow["chn"];
			$maksuteksti 		= "";
			$tilausvahvistus	= $asiakasrow["tilausvahvistus"];
			$laskutusvkopv 		= $asiakasrow["laskutusvkopv"];
			$vienti 			= $asiakasrow["vienti"];
			$ketjutus 			= $asiakasrow["ketjutus"];
			$valkoodi 			= $asiakasrow["valkoodi"];
			$sisviesti1			= $asiakasrow["sisviesti1"];
			$rahtivapaa 		= $asiakasrow["rahtivapaa"];
			$comments			= "";

			if ($asiakasrow["myynti_kommentti1"] != "") {
				$comments = $asiakasrow["myynti_kommentti1"];
			}

			//annetaan extranet-tilaukselle aina paras prioriteetti, tämä on hyvä porkkana.
			if ($kukarow["extranet"] != '') {
				$query  = "	SELECT distinct selite
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji    = 'asiakasluokka'
							and selite != ''
							ORDER BY 1
							LIMIT 1";
				$prioresult = pupe_query($query);
				$priorow = mysql_fetch_assoc($prioresult);

				$luokka = $priorow["selite"];
			}
			else {
				$luokka	= $asiakasrow["luokka"];
			}

			$erikoisale	= $asiakasrow["erikoisale"];

			// Jos on varasto setattuna, pässätään eteenpäin
			$varasto = 0;

			if (strpos($kukarow["varasto"], ',') === FALSE and (int) $kukarow["varasto"] > 0) {
				// Jos kukarow varasto on vain YKSI varasto, niin silloin valitaan $kukarow['varasto']
				$varasto = $kukarow['varasto'];
			}
			// valitaan käyttäjän oletus varasto
			elseif ($yhtiorow['oletusvarasto_tilaukselle'] == 'O' and (int) $kukarow["oletus_varasto"] > 0) {
				// jos kukarow['oletus_varasto'] valittu ja myyntitilauksella ei ole varastoa valittuna, valitaan käyttäjän oletus varasto
				$varasto = $kukarow['oletus_varasto'];
			}

			if ($yhtiorow["splittauskielto"] != '') {
				$splittauskielto = "E";
			}
			else {
				$splittauskielto = "";
			}

			$jtkielto 		= $asiakasrow['jtkielto'];
			$toimitustapa 	= $asiakasrow["toimitustapa"];

			// Toimitusehto
			if (trim($asiakasrow["toimitusehto"]) != "") {
				$yhtiorow['oletus_toimitusehto'] = $asiakasrow["toimitusehto"];
			}

			$toimitusehto  = substr($yhtiorow['oletus_toimitusehto'], 0, 3);
			$kasitoimehto  = substr($yhtiorow['oletus_toimitusehto'], 4);

			$toimitusehto2 = substr($yhtiorow['oletus_toimitusehto2'], 0, 3);
			$kasitoimehto2 = substr($yhtiorow['oletus_toimitusehto2'], 4);

			// Käytetäänkö lähettäjän vai vastaanottajan rahtisopparia
			$apuqu = "	SELECT *
						from toimitustapa
						where yhtio	= '$kukarow[yhtio]'
						and selite	= '$toimitustapa'";
			$meapu = pupe_query($apuqu);
			$apuro = mysql_fetch_assoc($meapu);

			$maksaja = $apuro['merahti'];

			// Onko toimitusehdon takana määritelty rahdinmaksaja? Tämä yliajaa toimitustavan takana olevan.
			$toimehto_tresult = t_avainsana("TOIMEHTO", "", " and trim(concat_ws(' ', selite, selitetark)) = trim('$yhtiorow[oletus_toimitusehto]') ");

			if (mysql_num_rows($toimehto_tresult) > 0) {
				$toimehto_row = mysql_fetch_assoc($toimehto_tresult);

				if ($toimehto_row["selitetark_3"] == "LAHETTAJAN_SOPIMUS") {
					$maksaja = "K";
				}
				elseif ($toimehto_row["selitetark_3"] == "VASTAANOTTAJAN_SOPIMUS") {
					$maksaja = "";
				}
			}

			$yhteysquery = "SELECT nimi
							FROM yhteyshenkilo
							where yhtio = '$kukarow[yhtio]'
							and liitostunnus = '$asiakasrow[tunnus]'
							and tyyppi = 'A'
							and tilausyhteyshenkilo != ''
							and oletusyhteyshenkilo != ''
							ORDER BY nimi
							LIMIT 1";
			$yhteysresult = pupe_query($yhteysquery);

			if ($yhteysrow = mysql_fetch_assoc($yhteysresult)) {
				$tilausyhteyshenkilo = $yhteysrow['nimi'];
			}

			$yhteyshenkilo_kaupallinen	= "";
			$yhteyshenkilo_tekninen		= "";
		}
		else {
			//yhtiön oletusalvi!
			$xwquery = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
			$xwtres  = pupe_query($xwquery);
			$xwtrow  = mysql_fetch_assoc($xwtres);

			$alv = (float) $xwtrow["selite"];

			$ytunnus = "*";

			// Jos on varasto setattuna, pässätään eteenpäin
			$varasto = 0;

			if (strpos($kukarow["varasto"], ',') === FALSE and (int) $kukarow["varasto"] > 0) {
				// Jos kukarow varasto on vain YKSI varasto, niin silloin valitaan $kukarow['varasto']
				$varasto = $kukarow['varasto'];
			}
			// valitaan käyttäjän oletus varasto
			elseif ($yhtiorow['oletusvarasto_tilaukselle'] == 'O' and (int) $kukarow["oletus_varasto"] > 0) {
				// jos kukarow['oletus_varasto'] valittu ja myyntitilauksella ei ole varastoa valittuna, valitaan käyttäjän oletus varasto
				$varasto = $kukarow['oletus_varasto'];
			}
		}

		if ($valkoodi == '') {
			$valkoodi = $yhtiorow["valkoodi"]."##";
		}
		else {
			$query = "	SELECT nimi, kurssi
						FROM valuu
						WHERE yhtio = '$kukarow[yhtio]'
						and nimi= '$valkoodi'";
			$vresult = pupe_query($query);

			if (mysql_num_rows($vresult) == 1) {
				$vrow = mysql_fetch_assoc($vresult);

				$valkoodi = $vrow["nimi"]."##".$vrow["kurssi"];
			}
			else {
				$valkoodi = $yhtiorow["valkoodi"]."##";
			}
		}

		$alv_velvollisuus = "";

		// jos meillä on lasku menossa ulkomaille
		if ($maa != "" and $maa != $yhtiorow["maa"]) {
			// tutkitaan ollaanko siellä alv-rekisteröity
			$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$maa' and vat_numero != ''";
			$alhire = pupe_query($alhqur);

			// ollaan alv-rekisteröity, aina kotimaa myynti ja alvillista
			if (mysql_num_rows($alhire) == 1) {

				$alhiro  = mysql_fetch_assoc($alhire);

				// haetaan maan oletusalvi
				$query = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='ALVULK' and selitetark!='' and selitetark_2='$maa' limit 1";
				$alhire = pupe_query($query);

				// jos ei löydy niin mennään erroriin
				if (mysql_num_rows($alhire) == 0) {
					echo "<font class='error'>Oletus ALV-kantaa ei löydy asiakkaan maahan $maa! Ei voida jatkaa!</font><br>";
					exit;
				}
				else {
					$apuro  = mysql_fetch_assoc($alhire);
					// nämä tässä keisissä aina näin
					$alv = $apuro["selite"];
					$vienti = "";
					$alv_velvollisuus = $alhiro["vat_numero"];
				}
			}
		}

		if ($yhtiorow['myyntitilaus_osatoimitus'] == 'E' and in_array($toim, array("RIVISYOTTO", "PIKATILAUS"))) {
			$osatoimitus = "o";
		}

		$tilaustyyppi = "N";

		if ($toim == "TARJOUS") {
			$tilaustyyppi = 'T';
		}
		elseif ($toim == "EXTRANET_REKLAMAATIO") {
			$tilaustyyppi = 'R';
		}
		elseif ($toim == "MYYNTITILI") {
			$tilaustyyppi = "M";
		}
		elseif ($toim == "TYOMAARAYS") {
			$tilaustyyppi = "A";
		}

		$jatka	= "JATKA";
		$tee	= "OTSIK";
		$override_ytunnus_check = "YES";
		$ei_kayttoliittymaa = "kylla";

		require ("otsik.inc");

		if ($session == "") {
			return $id;
		}
		else {
			return $tilausnumero;
		}
	}
}

if (!function_exists("pupesoft_lisaa_rivi")) {
	function pupesoft_lisaa_rivi($parametrit) {

		global $kukarow, $yhtiorow;

		if (empty($kukarow['kesken'])) {
			echo "Kukarow kesken on pakollinen";
			exit;
		}

		if (empty($yhtiorow)) {
			echo "Yhtiorow on pakollinen";
			exit;
		}

		//TODO tee muut pakolliset ylläolevan mukaisesti trow, laskurow, tuoteno, kpl,

		// Tarvitsemme:
		$trow               = (!empty($parametrit['trow']))               ? $parametrit['trow'] : '';                // tuote.*
		$rivinumero         = (!empty($parametrit['rivinumero']))         ? $parametrit['rivinumero'] : 0;           // joko tilaajan rivinumero tai konserninsisäisissä kaupoissa sisäinen toimittajanumero
		$laskurow           = (!empty($parametrit['laskurow']))           ? $parametrit['laskurow'] : '';            // lasku.*
		$kpl                = (!empty($parametrit['kpl']))                ? $parametrit['kpl'] : 0;                  // --> tilattu kappalemäärä
		$tuoteno            = (!empty($parametrit['tuoteno']))            ? $parametrit['tuoteno'] : '';             // --> tilattava tuotenumero
		$toimaika           = (!empty($parametrit['toimaika']))           ? $parametrit['toimaika'] : '';            // --> arvioitu toimitusaika
		$kerayspvm          = (!empty($parametrit['kerayspvm']))          ? $parametrit['kerayspvm'] : '';           // --> toivottu keräysaika
		$hinta              = (!empty($parametrit['hinta']))              ? $parametrit['hinta'] : 0;                // --> käyttäjän syöttämä hinta
		$netto              = (!empty($parametrit['netto']))              ? $parametrit['netto'] : '';               // --> käyttäjän syöttämä netto
		$var                = (!empty($parametrit['var']))                ? $parametrit['var'] : '';                 // H,J,P varrit
		$varasto            = (!empty($parametrit['varasto']))            ? $parametrit['varasto'] : '';             // myydään vain tästä/näistä varastosta
		$paikka             = (!empty($parametrit['paikka']))             ? $parametrit['paikka'] : '';              // myydään vain tältä paikalta
		$rivitunnus         = (!empty($parametrit['rivitunnus']))         ? $parametrit['rivitunnus'] : '';          // tietokannan tunnus jolle rivi lisätään
		$rivilaadittu       = (!empty($parametrit['rivilaadittu']))       ? $parametrit['rivilaadittu'] : '';        // vanhan rivin laadittuaika, säilytetään se
		$korvaavakielto     = (!empty($parametrit['korvaavakielto']))     ? $parametrit['korvaavakielto'] : '';      // Jos erisuuri kuin tyhjä niin ei myydä korvaavia
		$jtkielto           = (!empty($parametrit['jtkielto']))           ? $parametrit['jtkielto'] : '';            // Jos erisuuri kuin tyhjä niin ei laiteta JT:Seen
		$perhekielto        = (!empty($parametrit['perhekielto']))        ? $parametrit['perhekielto'] : '';         // Jos erisuuri kuin tyhjä niin ei etsitä ollenkaan perheitä
		$varataan_saldoa    = (!empty($parametrit['varataan_saldoa']))    ? $parametrit['varataan_saldoa'] : '';     // Jos == (!empty(EI niin ei varata saldoa (tietyissä keisseissä), tai siis ei ainakan tehdä saldotsekkiä
		$kutsuja            = (!empty($parametrit['kutsuja']))            ? $parametrit['kutsuja'] : '';             // Kuka tätä skriptiä kutsuu
		$myy_sarjatunnus    = (!empty($parametrit['myy_sarjatunnus']))    ? $parametrit['myy_sarjatunnus'] : '';     // Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
		$osto_sarjatunnus   = (!empty($parametrit['osto_sarjatunnus']))   ? $parametrit['osto_sarjatunnus'] : '';    // Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
		$jaksotettu         = (!empty($parametrit['jaksotettu']))         ? $parametrit['jaksotettu'] : '';          // Kuuluuko tilausrivi mukaan jaksotukseen
		$perheid            = (!empty($parametrit['perheid']))            ? $parametrit['perheid'] : '';             // Tuoteperheen perheid
		$perheid2           = (!empty($parametrit['perheid2']))           ? $parametrit['perheid2'] : '';            // Lisävarusteryhmän perheid2
		$orvoteikiinnosta   = (!empty($parametrit['orvoteikiinnosta']))   ? $parametrit['orvoteikiinnosta'] : '';    // Meitä ei kiinnosta orvot jos tämä ei ole tyhjä.
		$osatoimkielto      = (!empty($parametrit['osatoimkielto']))      ? $parametrit['osatoimkielto'] : '';       // Jos saldo ei riitä koko riville niin ei lisätä riviä ollenkaan
		$olpaikalta         = (!empty($parametrit['olpaikalta']))         ? $parametrit['olpaikalta'] : '';          // pakotetaan myymään oletuspaikalta
		$tuotenimitys       = (!empty($parametrit['tuotenimitys']))       ? $parametrit['tuotenimitys'] : '';        // tuotteen nimitys jos nimityksen syötö on yhtiöllä sallittu
		$tuotenimitys_force = (!empty($parametrit['tuotenimitys_force'])) ? $parametrit['tuotenimitys_force'] : '';  // tuotteen nimitys muutetaan systemitasolla

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			$ale_temp = str_replace(',', '.', $parametrit{'ale'.$alepostfix});
			${'ale'.$alepostfix} = (!empty($ale_temp)) ? $ale_temp : 0;
			${'alkupera_ale'.$alepostfix} = (!empty($parametrit{'ale'.$alepostfix})) ? $parametrit{'ale'.$alepostfix} : 0;
		}

		require('tilauskasittely/lisaarivi.inc');

		return array(
			'lisatyt_rivit1' => $lisatyt_rivit1,
			'lisatyt_rivit2' => $lisatyt_rivit2,
		);

		// Palauttaa arrayn $lisatyt_rivit1 jossa on kaikkien rivien tunnukset jotka tää systeemi lisäsi myyntihaarassa
		// Palauttaa arrayn $lisatyt_rivit2 jossa on kaikkien rivien tunnukset jotka tää systeemi lisäsi puute-jt-osto-haarassa
	}
}
