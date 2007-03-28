<?php
	//sisään halutaan $laskurow jossa on tulostettavan tilauksen tiedot
	//jos tilauksia on useita $laskurow muuttujassa on jonkun tilauksen tiedot


	// katotaan ihan aluksi, että meillä on edellytykset tulostukselle...
	$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]'";
	$prires= mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($prires) != 0) {

		$prirow= mysql_fetch_array($prires);
		$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$prirow[printteri1]'";
		$kirres= mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($kirres)==0)
			die ("<font class='error'>VIRHE: Varaston $prirow[nimitys] tulostinta ei löydy. Lähetteitä ei voida tulostaa...</font>");
	}
	else {
		die ("<font class='error'>Yhtään varastoa ei ole määritelty. Lähetteitä ei voida tulostaa...</font>");
	}

	//tarkistetan tulostuksessa syntyviä virheitä
	$virheellinen = '';

	// emuloidaan transactioita mysql LOCK komennolla
	$query = "LOCK TABLES tilausrivi WRITE, tilausrivi AS t2 READ, tilausrivi AS t3 READ, lasku WRITE, sanakirja WRITE, avainsana READ, varaston_tulostimet READ, tuotepaikat READ, tuote READ, tuoteperhe READ, maksuehto READ, varastopaikat READ, kirjoittimet READ, asiakas READ, kuka WRITE, asiakaskommentti READ, tuotteen_toimittajat READ, tuotteen_avainsanat READ, pankkiyhteystiedot READ";
	$res   = mysql_query($query) or pupe_error($query);

	$queryc = "	SELECT sum(if(tila='N' and alatila='A', 1, 0)) ok, count(*) kaikki
				FROM lasku
				WHERE tunnus in ($tilausnumeroita)
				and yhtio	= '$kukarow[yhtio]'";
	$chk_result = mysql_query($queryc) or pupe_error($queryc);
	$chk_row = mysql_fetch_array($chk_result);

	// Katsotaan, että tämä keräyslista ei ole jo tulostettu
	if($chk_row["ok"] == $chk_row["kaikki"]) {

		if ($kerayslista == 'K') {
			//keräyslistan tulostusta varten
			if ($yhtiorow["kerailylistatyyppi"] != "" and file_exists($yhtiorow["kerailylistatyyppi"])) {
				require_once ($yhtiorow["kerailylistatyyppi"]);
			}
			else {
				require_once ("tulosta_lahete_kerayslista.inc");
			}
		}
		else {
			//hatetaan asiakkaan lähetetyyppi
			$query = "	SELECT lahetetyyppi, luokka, puhelin
						FROM asiakas
						WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			$asrow = mysql_fetch_array($result);

			if ($asrow["lahetetyyppi"] != '' and file_exists($asrow["lahetetyyppi"])) {
				require_once ($asrow["lahetetyyppi"]);
			}
			else {
				//Haetaan yhtiön oletuslähetetyyppi
				$query = "	SELECT selite
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]' and laji = 'LAHETETYYPPI'
							ORDER BY jarjestys, selite
							LIMIT 1";
				$vres = mysql_query($query) or pupe_error($query);
				$vrow = mysql_fetch_array($vres);

				if ($vrow["selite"] != '' and file_exists($vrow["selite"])) {
					require_once ($vrow["selite"]);
				}
				else {
					echo "<font class='error'>".t("Emme löytäneet yhtään lähetetyyppiä. Lähetettä ei voida tulostaa.")."</font><br>";
				}
			}
		}

		//korjataan tää päivämäärä kuntoon jota ei vielä ole
		$laskurow["lahetepvm"] = date("Y-m-d");

		//tehdään uusi PDF failin olio
		$pdf= new pdffile;
		$pdf->set_default('margin', 0);

		//keräyslistalle ei tulosteta saldottomia tuotteita
		if ($kerayslista == 'K') {
			$lisa1 = " and tuote.ei_saldoa = '' ";
		}
		else {
			$lisa1 = " ";
		}

		//ovhhintaa tarvitaan jos lähetetyyppi on sellainen, että sinne tulostetaan bruttohinnat
		if ($yhtiorow["alv_kasittely"] != "") {
			$lisa2 = " round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta*(1+(tilausrivi.alv/100))),2) ovhhinta ";
		}
		else {
			$lisa2 = " round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta),2) ovhhinta ";
		}

		// katotaan miten halutaan sortattavan
		$sorttauskentta = generoi_sorttauskentta();

		//generoidaan lähetteelle ja keräyslistalle rivinumerot
		$query = "	SELECT tilausrivi.*,
					round((tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * tilausrivi.hinta * (1-(tilausrivi.ale/100)),2) rivihinta,
					$sorttauskentta,
					$lisa2
					FROM tilausrivi, tuote
					WHERE tilausrivi.otunnus in ($tilausnumeroita)
					and tilausrivi.yhtio   = '$kukarow[yhtio]'
					and tilausrivi.yhtio   = tuote.yhtio
					and tilausrivi.tuoteno = tuote.tuoteno
					and tilausrivi.tyyppi  != 'D'
					$lisa1
					ORDER BY sorttauskentta";
		$result = mysql_query($query) or pupe_error($query);

		//generoidaan rivinumerot
		$rivinumerot = array();

		$kal = 1;

		while ($row = mysql_fetch_array($result)) {
			if ($kerayslista == 'K') {
				$rivinumerot[$row["tunnus"]] = $kal;
				$kal++;
			}
			else {
				$rivinumerot[$row["tunnus"]] = $row["tunnus"];
			}
		}

		if ($kerayslista != 'K') {
			sort($rivinumerot);

			$kal = 1;

			foreach($rivinumerot as $rivino) {
				$rivinumerot[$rivino] = $kal;
				$kal++;
			}
		}

		mysql_data_seek($result,0);

		//pdf:n header..
		$firstpage = alku();
		$paino = 0;

		while ($row = mysql_fetch_array($result)) {
			//piirrä rivi
			$firstpage = rivi($firstpage);
			$total += $row["rivihinta"];
		}

		//Vikan rivin loppuviiva
		$x[0] = 20;
		$x[1] = 580;
		$y[0] = $y[1] = $kala + $rivinkorkeus - 4;
		$pdf->draw_line($x, $y, $firstpage, $rectparam);

		loppu($firstpage, 1);


		//tulostetaan faili ja valitaan sopivat printterit
		if ($laskurow["varasto"] == '') {
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
			$prirow= mysql_fetch_array($prires);

			// käteinen muuttuja viritetään tilaus-valmis.inc:issä jos maksuehto on käteinen
			// ja silloin pitää kaikki lähetteet tulostaa aina printteri5:lle (lasku printteri)
			if ($kateinen=='X') {
				$apuprintteri=$prirow['printteri5']; // laskuprintteri
			}
			else {
				$apuprintteri=$prirow['printteri1']; // läheteprintteri

				//haetaan optimaalinen tulostin tälle lähetteelle
				$tilaus  = $tilausnumeroita;
				$varasto = $laskurow["varasto"];

				require("varaston_tulostusalue.inc");

				if ($kirjoitin != '') {
					$apuprintteri = $kirjoitin;
				}
			}

			//käsinvalittu printteri
			if ($valittu_tulostin != '') {
				$apuprintteri = $valittu_tulostin;
			}

			//haetaan lähetteen tulostuskomento
			$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
			$kirres= mysql_query($query) or pupe_error($query);
			$kirrow= mysql_fetch_array($kirres);
			$komento=$kirrow['komento'];

			//haetaan osoitelapun tulostuskomento
			$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$prirow[printteri3]'";
			$kirres= mysql_query($query) or pupe_error($query);
			$kirrow= mysql_fetch_array($kirres);
			$oslapp=$kirrow['komento'];

		}

		//tulostetaan sivu
		print_pdf($komento);

		if ($returnvalue != 0) {
			echo "<font class='error'>Lähetteen tulostus epäonnistui! Tilaus $laskurow[tunnus] siirrettiin tulostusjonoon.
					Käy tulostamassa lähete <a href='lahetteen_tulostusjono.php'>tulostusjonosta</a>.</font><br>";

			$virheellinen = "X"; //Merkataan epäonnistuneeksi
		}

		if ($kerayslista != 'K') {
			//jos osoitelappuprintteri löytyy tulostetaan osoitelappu..
			$tunnus = $laskurow["tunnus"];
			if ($oslapp!='') {
				require ("osoitelappu_pdf.inc");
			}
		}

		// jos meillä oli lähetteen tulostusongelmia
		if ($virheellinen != 'X') {
			//päivitetään rivit tulostetuksi EI JÄRJEN HÄIVÄÄ!
			$query = "UPDATE tilausrivi SET tyyppi ='L' where yhtio='$kukarow[yhtio]' and otunnus in ($tilausnumeroita)";
			$result = mysql_query($query) or pupe_error($query);

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
		echo "<font class='error'>".t("VIRHE: Lähete/Keräyslista on jo tulostettu!")."</font><br>";
	}
	// poistetaan lukot
	$query ="UNLOCK TABLES";
	$res   = mysql_query($query) or pupe_error($query);
?>