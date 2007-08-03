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
	$query = "LOCK TABLES tilausrivi WRITE, tilausrivi AS t2 READ, sarjanumeroseuranta READ, tilausrivi AS t3 READ, lasku WRITE, sanakirja WRITE, avainsana READ, varaston_tulostimet READ, tuotepaikat READ, tuote READ, tuoteperhe READ, maksuehto READ, varastopaikat READ, kirjoittimet READ, asiakas READ, kuka WRITE, asiakaskommentti READ, tuotteen_toimittajat READ, tuotteen_avainsanat READ, pankkiyhteystiedot READ, toimitustapa READ, yhtion_toimipaikat READ, tuotteen_alv READ, maat READ";
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
		if ($yhtiorow["kerailylistatyyppi"] != "" and file_exists($yhtiorow["kerailylistatyyppi"])) {
			require_once ($yhtiorow["kerailylistatyyppi"]);
		}
		else {
			require_once ("tulosta_lahete_kerayslista.inc");
		}
        
		// ker‰yslistat sortataan aina n‰in
		$sorttauskentta = "if(perheid = 0,
							(select concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0'), tuoteno, tunnus)  from tilausrivi as t2 where t2.yhtio = tilausrivi.yhtio and t2.tunnus = tilausrivi.tunnus),
							(select concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0'), tuoteno, perheid) from tilausrivi as t3 where t3.yhtio = tilausrivi.yhtio and t3.tunnus = tilausrivi.perheid)
							) as sorttauskentta";
		
		// korjataan t‰‰ p‰iv‰m‰‰r‰ kuntoon jota ei viel‰ ole
		$laskurow["lahetepvm"] = date("Y-m-d");

		// tehd‰‰n uusi PDF failin olio
		$pdf = new pdffile;
		$pdf->set_default('margin', 0);

		// ker‰yslistalle ei oletuksena tulosteta saldottomia tuotteita
		if ($yhtiorow["kerataanko_saldottomat"] == '') {
			$lisa1 = " and tuote.ei_saldoa = '' ";
		}
		else {
			$lisa1 = " ";
		}

		// generoidaan ker‰yslistalle rivinumerot
		$query = "	SELECT tilausrivi.*,
					tuote.sarjanumeroseuranta,
					$sorttauskentta
					FROM tilausrivi, tuote
					WHERE tilausrivi.otunnus in ($tilausnumeroita)
					and tilausrivi.yhtio   = '$kukarow[yhtio]'
					and tilausrivi.yhtio   = tuote.yhtio
					and tilausrivi.tuoteno = tuote.tuoteno
					and tilausrivi.tyyppi  != 'D'
					$lisa1
					ORDER BY sorttauskentta";
		$riresult = mysql_query($query) or pupe_error($query);

		// generoidaan rivinumerot
		$rivinumerot = array();

		while ($row = mysql_fetch_array($riresult)) {
			$rivinumerot[$row["tunnus"]] = $row["tunnus"];
		}

		sort($rivinumerot);

		$kal = 1;

		foreach($rivinumerot as $rivino) {
			$rivinumerot[$rivino] = $kal;
			$kal++;
		}

		mysql_data_seek($riresult,0);

		$paino = 0;
		$sivu  = 0;

		//pdf:n header..
		$firstpage = alku();
		

		while ($row = mysql_fetch_array($riresult)) {
			//piirr‰ rivi
			$firstpage = rivi($firstpage);
		}

		//Vikan rivin loppuviiva
		$x[0] = 20;
		$x[1] = 580;
		$y[0] = $y[1] = $kala + $rivinkorkeus - 4;
		$pdf->draw_line($x, $y, $firstpage, $rectparam);

		loppu($firstpage, 1);


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
			print_pdf($komento);
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