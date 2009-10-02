<?php
	//sisään halutaan $laskurow jossa on tulostettavan tilauksen tiedot
	//jos tilauksia on useita $laskurow muuttujassa on jonkun tilauksen tiedot

	// katotaan ihan aluksi, että meillä on edellytykset tulostukselle...
	$query = "SELECT * from varastopaikat where yhtio='$kukarow[yhtio]'";
	$prires= mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($prires) != 0) {
		$prirow= mysql_fetch_array($prires);
		$query = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$prirow[printteri1]'";
		$kirres= mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($kirres)==0)
			die ("<font class='error'>VIRHE: Varaston $prirow[nimitys] tulostinta ei löydy. Lähetteitä ei voida tulostaa...</font>");
	}
	else {
		die ("<font class='error'>Yhtään varastoa ei ole määritelty. Lähetteitä ei voida tulostaa...</font>");
	}

	// tarkistetan tulostuksessa syntyviä virheitä
	$virheellinen = '';

	// emuloidaan transactioita mysql LOCK komennolla
	$query = "LOCK TABLES liitetiedostot READ, tilausrivi WRITE, tilausrivi AS t2 READ, tilausrivin_lisatiedot AS tlt2 READ, tilausrivi AS tilausrivi_osto READ, tilausrivi AS tilausrivi_myynti READ, sarjanumeroseuranta READ, tilausrivi AS t3 READ, lasku WRITE, sanakirja WRITE, avainsana READ, avainsana as a READ, avainsana as b READ, varaston_tulostimet READ, tuotepaikat READ, tuote READ, tuoteperhe READ, maksuehto READ, varastopaikat READ, kirjoittimet READ, asiakas READ, kuka WRITE, asiakaskommentti READ, tuotteen_toimittajat READ, tuotteen_avainsanat READ, pankkiyhteystiedot READ, toimitustapa READ, yhtion_toimipaikat READ, yhtion_parametrit READ, tuotteen_alv READ, maat READ, rahtisopimukset READ, rahtisopimukset AS rahtisopimukset2 READ, pakkaamo WRITE, avainsana as avainsana_kieli READ";
	$res   = mysql_query($query) or pupe_error($query);

	$queryc = "	SELECT sum(if(tila='N' and alatila='A', 1, 0)) ok, count(*) kaikki
				FROM lasku
				WHERE tunnus in ($tilausnumeroita)
				and yhtio	= '$kukarow[yhtio]'";
	$chk_result = mysql_query($queryc) or pupe_error($queryc);
	$chk_row = mysql_fetch_array($chk_result);

	// Katsotaan, että tämä keräyslista ei ole jo tulostettu
	if ($chk_row["ok"] == $chk_row["kaikki"] and $chk_row["ok"] != 0) {

		$laskurow['pakkaamo'] = pakkaamo($tilausnumeroita, "yes", $ei_pakkaamoa);

		//keräyslistan tulostusta varten
		require_once ("tulosta_lahete_kerayslista.inc");

		//hatetaan asiakkaan tiedot
		$query = "  SELECT lahetetyyppi, luokka, puhelin, if(asiakasnro!='', asiakasnro, ytunnus) asiakasnro
					FROM asiakas
					WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
		$riresult = mysql_query($query) or pupe_error($query);
		$asrow = mysql_fetch_array($riresult);

		// keräyslistalle ei oletuksena tulosteta saldottomia tuotteita
		if ($yhtiorow["kerataanko_saldottomat"] == '') {
			$lisa1 = " and tuote.ei_saldoa = '' ";
		}
		else {
			$lisa1 = " ";
		}

		if($laskurow["tila"] == "V") {
			$sorttaus = "valmistus_kerayslistan_jarjestys";
			$sorttaussuunta = "valmistus_kerayslistan_jarjestys_suunta";
		}
		else {
			$sorttaus = "kerayslistan_jarjestys";
			$sorttaussuunta = "kerayslistan_jarjestys_suunta";
		}

		$sorttauskentta = generoi_sorttauskentta($yhtiorow[$sorttaus]);

		$select_lisa = $where_lisa = "";

		//	 Summataan rivit yhteen (HUOM! unohdetaan kaikki perheet!)
		if($yhtiorow[$sorttaus] == "S") {
			$select_lisa = "sum(kpl) kpl, sum(tilkpl) tilkpl, sum(varattu) varattu, sum(jt) jt, '' perheid, '' perheid2, ";
			$where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
		}

		// generoidaan keräyslistalle rivinumerot
		$query = "	SELECT tilausrivi.*,
					tuote.sarjanumeroseuranta,
					$select_lisa
					$sorttauskentta
					FROM tilausrivi, tuote
					WHERE tilausrivi.otunnus in ($tilausnumeroita)
					and tilausrivi.yhtio   = '$kukarow[yhtio]'
					and tilausrivi.yhtio   = tuote.yhtio
					and tilausrivi.tuoteno = tuote.tuoteno
					and tilausrivi.tyyppi  != 'D'
					$lisa1
					$where_lisa
					ORDER BY sorttauskentta $yhtiorow[kerayslistan_jarjestys_suunta], tilausrivi.tunnus";
		$riresult = mysql_query($query) or pupe_error($query);

		//generoidaan rivinumerot
		$rivinumerot = array();

		$kal = 1;

		while ($row = mysql_fetch_array($riresult)) {
			$rivinumerot[$row["tunnus"]] = $kal;
			$kal++;
		}

		mysql_data_seek($riresult,0);

		unset($pdf);
		unset($page);

		$sivu  = 1;
		$paino = 0;

		// Aloitellaan lähetteen teko
		$page[$sivu] = alku_kerayslista();

		while ($row = mysql_fetch_array($riresult)) {
			rivi_kerayslista($page[$sivu]);
		}

		loppu_kerayslista($page[$sivu], 1);


		//tulostetaan faili ja valitaan sopivat printterit
		if ($laskurow["varasto"] == 0) {
			$query = "	SELECT *
						from varastopaikat
						where yhtio='$kukarow[yhtio]'
						order by alkuhyllyalue,alkuhyllynro
						limit 1";
		}
		else {
			
			$query = "	SELECT *
						from varastopaikat
						where yhtio='$kukarow[yhtio]' 
						and tunnus='$laskurow[varasto]'";
		}
		$prires= mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($prires) > 0) {
			$prirow = mysql_fetch_array($prires);

			$apuprintteri = $prirow['printteri1']; // läheteprintteri

			//haetaan optimaalinen tulostin tälle lähetteelle
			$tilaus  = $tilausnumeroita;
			$varasto = $laskurow["varasto"];

			require("varaston_tulostusalue.inc");

			if ($kirjoitin != '') {
				$apuprintteri = $kirjoitin;
			}

			//käsinvalittu printteri
			if ($valittu_tulostin != '') {
				$apuprintteri = $valittu_tulostin;
			}

			//haetaan lähetteen tulostuskomento
			$query   = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
			$kirres  = mysql_query($query) or pupe_error($query);
			$kirrow  = mysql_fetch_array($kirres);

			$komento = $kirrow['komento'];
		}

		if ($komento != "") {
			//tulostetaan sivu
			print_pdf_kerayslista($komento);
		}
		else {
			$returnvalue = 1;
		}

		if ($returnvalue != 0) {
			echo "<br><font class='error'>Lähetteen tulostus epäonnistui! Tilaus $laskurow[tunnus] siirrettiin tulostusjonoon. Käy tulostamassa lähete <a href='lahetteen_tulostusjono.php'>tulostusjonosta</a>.</font><br><br>";
			$virheellinen = "X"; //Merkataan epäonnistuneeksi
		}

		// jos meillä oli lähetteen tulostusongelmia
		if ($virheellinen != 'X') {

			//jos tilauksia on useita niin laitetaan niille yhteinen kerayslista-tunnus
			if ($laskuja > 1) {
				$tunnukset = explode(',', $tilausnumeroita);
				$kerayslistatunnus = trim($tunnukset[0]);
			}
			else {
				$kerayslistatunnus = 0;
			}

			$ei_pakkaamoa_lisaviesti = '';

			if ($ei_pakkaamoa_selected == 'checked' and $ei_pakkaamoa == '') {
				$ei_pakkaamoa_lisaviesti = ", sisviesti2 = concat(sisviesti2, ' $kukarow[kuka] ".t("halusi tilauksen pakkaamolokeroihin")." ".tv1dateconv(date("Y-m-d H:i:s"))."')";
			}

			$query = "	UPDATE lasku SET
						tila = 'L',
						lahetepvm = now(),
						kerayslista = '$kerayslistatunnus'
						$ei_pakkaamoa_lisaviesti
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus in ($tilausnumeroita)";
			$result = mysql_query($query) or pupe_error($query);
		}
	}
	else {
		echo "<font class='error'>".t("VIRHE: Lähete/Keräyslista on jo tulostettu!")."</font><br>";
	}

	// poistetaan lukot
	$query ="UNLOCK TABLES";
	$res   = mysql_query($query) or pupe_error($query);
?>
