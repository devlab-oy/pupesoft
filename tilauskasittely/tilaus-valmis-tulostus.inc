<?php
	//sis‰‰n halutaan $laskurow jossa on tulostettavan tilauksen tiedot
	//jos tilauksia on useita $laskurow muuttujassa on jonkun tilauksen tiedot

	// katotaan ihan aluksi, ett‰ meill‰ on edellytykset tulostukselle...
	$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]'";
	$prires= mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($prires) != 0) {
		$prirow= mysql_fetch_array($prires);
		$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$prirow[printteri1]'";
		$kirres= mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($kirres)==0)
			die ("<font class='error'>VIRHE: Varaston $prirow[nimitys] tulostinta ei lˆydy. L‰hetteit‰ ei voida tulostaa...</font>");
	}
	else {
		die ("<font class='error'>Yht‰‰n varastoa ei ole m‰‰ritelty. L‰hetteit‰ ei voida tulostaa...</font>");
	}

	// tarkistetan tulostuksessa syntyvi‰ virheit‰
	$virheellinen = '';

	// emuloidaan transactioita mysql LOCK komennolla
	$query = "LOCK TABLES liitetiedostot READ, tilausrivi WRITE, tilausrivi AS t2 READ, tilausrivi AS tilausrivi_osto READ, tilausrivi AS tilausrivi_myynti READ, sarjanumeroseuranta READ, tilausrivi AS t3 READ, lasku WRITE, sanakirja WRITE, avainsana READ, varaston_tulostimet READ, tuotepaikat READ, tuote READ, tuoteperhe READ, maksuehto READ, varastopaikat READ, kirjoittimet READ, asiakas READ, kuka WRITE, asiakaskommentti READ, tuotteen_toimittajat READ, tuotteen_avainsanat READ, pankkiyhteystiedot READ, toimitustapa READ, yhtion_toimipaikat READ, tuotteen_alv READ, maat READ, rahtisopimukset READ";
	$res   = mysql_query($query) or pupe_error($query);

	$queryc = "	SELECT sum(if(tila='N' and alatila='A', 1, 0)) ok, count(*) kaikki
				FROM lasku
				WHERE tunnus in ($tilausnumeroita)
				and yhtio	= '$kukarow[yhtio]'";
	$chk_result = mysql_query($queryc) or pupe_error($queryc);
	$chk_row = mysql_fetch_array($chk_result);

	// Katsotaan, ett‰ t‰m‰ ker‰yslista ei ole jo tulostettu
	if($chk_row["ok"] == $chk_row["kaikki"]) {

		//ker‰yslistan tulostusta varten
		require_once ("tulosta_lahete_kerayslista.inc");
		
		//hatetaan asiakkaan tiedot
		$query = "  SELECT lahetetyyppi, luokka, puhelin, if(asiakasnro!='', asiakasnro, ytunnus) asiakasnro
					FROM asiakas
					WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
		$riresult = mysql_query($query) or pupe_error($query);
		$asrow = mysql_fetch_array($riresult);

		// ker‰yslistalle ei oletuksena tulosteta saldottomia tuotteita
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

		// generoidaan ker‰yslistalle rivinumerot
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

		// Aloitellaan l‰hetteen teko
		$page[$sivu] = alku_kerayslista();

		while ($row = mysql_fetch_array($riresult)) {
			rivi_kerayslista($page[$sivu]);
		}

		loppu_kerayslista($page[$sivu], 1);


		//tulostetaan faili ja valitaan sopivat printterit
		if ($laskurow["varasto"] == 0) {
			$query = "	select *
						from varastopaikat
						where yhtio='$kukarow[yhtio]'
						order by alkuhyllyalue,alkuhyllynro
						limit 1";
		}
		else {
			$query = "	select *
						from varastopaikat
						where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[varasto]'
						order by alkuhyllyalue,alkuhyllynro";
		}
		$prires= mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($prires) > 0) {
			$prirow = mysql_fetch_array($prires);

			// k‰teinen muuttuja viritet‰‰n tilaus-valmis.inc:iss‰ jos maksuehto on k‰teinen
			// ja silloin pit‰‰ kaikki l‰hetteet tulostaa aina printteri5:lle (lasku printteri)
			if ($kateinen == 'X') {
				$apuprintteri = $prirow['printteri5']; // laskuprintteri
			}
			else {
				$apuprintteri = $prirow['printteri1']; // l‰heteprintteri

				//haetaan optimaalinen tulostin t‰lle l‰hetteelle
				$tilaus  = $tilausnumeroita;
				$varasto = $laskurow["varasto"];

				require("varaston_tulostusalue.inc");

				if ($kirjoitin != '') {
					$apuprintteri = $kirjoitin;
				}
			}

			//k‰sinvalittu printteri
			if ($valittu_tulostin != '') {
				$apuprintteri = $valittu_tulostin;
			}

			//haetaan l‰hetteen tulostuskomento
			$query   = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
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
			echo "<font class='error'>L‰hetteen tulostus ep‰onnistui! Tilaus $laskurow[tunnus] siirrettiin tulostusjonoon.
					K‰y tulostamassa l‰hete <a href='lahetteen_tulostusjono.php'>tulostusjonosta</a>.</font><br>";

			$virheellinen = "X"; //Merkataan ep‰onnistuneeksi
		}

		// jos meill‰ oli l‰hetteen tulostusongelmia
		if ($virheellinen != 'X') {
			
			//jos tilauksia on useita niin laitetaan niille yhteinen kerayslista-tunnus
			if ($laskuja > 1) {
				$tunnukset = explode(',', $tilausnumeroita);
				$kerayslistatunnus = trim($tunnukset[0]);
			}
			else {
				$kerayslistatunnus = 0;
			}

			$query = "	UPDATE lasku
						SET tila='L', lahetepvm=now(), kerayslista='$kerayslistatunnus'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus in ($tilausnumeroita)";
			$result = mysql_query($query) or pupe_error($query);
		}
	}
	else {
		echo "<font class='error'>".t("VIRHE: L‰hete/Ker‰yslista on jo tulostettu!")."</font><br>";
	}
	
	// poistetaan lukot
	$query ="UNLOCK TABLES";
	$res   = mysql_query($query) or pupe_error($query);
?>
