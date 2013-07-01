<?php

	//sis‰‰n tarvitaan $laskurow jossa on laskun tiedot.

	$tulos_ulos_ehtosplit = "";

	// otetaan maksuehto t‰ss‰ heti alussa selville
	$query = "	SELECT *
				from maksuehto
				where yhtio	= '$kukarow[yhtio]'
				and tunnus	= '$laskurow[maksuehto]'";
	$yresult = pupe_query($query);
	$mrow = mysql_fetch_assoc($yresult);

	//jos ehdot t‰yttyv‰t niin jaetaan tilaus kahteen osaan ja jaetaan loppusumma summanjakoprososa2:sen mukaan
	if ($mrow["summanjakoprososa2"] != 0 and $mrow["osamaksuehto1"] != 0 and $mrow["osamaksuehto2"] != 0) {

		$tulos_ulos_ehtosplit .= sprintf(t("Tilauksella %s on moniehto-maksuehto. Se jaettiin nyt kahdeksi tilaukseksi jakoprosentilla %s"), $laskurow["tunnus"], $mrow["summanjakoprososa2"])."<br>\n";

		$query_ale_lisa = generoi_alekentta('M');

		if ($yhtiorow["alv_kasittely"] == '') {
			$query = "	SELECT round((varattu*hinta*{$query_ale_lisa})/(1+alv/100),2) rivihinta, netto
						FROM tilausrivi
						WHERE otunnus 	= '$laskurow[tunnus]'
						and yhtio	  	= '$kukarow[yhtio]'
						and tyyppi		= 'L'";
		}
		else {
			$query = "	SELECT round((varattu*hinta*{$query_ale_lisa}),2) rivihinta, netto
						FROM tilausrivi
						WHERE otunnus 	= '$laskurow[tunnus]'
						and yhtio	  	= '$kukarow[yhtio]'
						and tyyppi		= 'L'";
		}

		$presult = pupe_query($query);

		$nyht = 0;
		$myht = 0;
		$kyht = 0;

		while ($prow = mysql_fetch_assoc ($presult)) {

			if ($prow["netto"] != 'N') {
				$myht += $prow["rivihinta"]; // lasketaan tilauksen loppusummaa MUUT RIVIT..
			}
			else {
				$nyht += $prow["rivihinta"]; // lasketaan tilauksen loppusummaa NETTORIVIT..
			}
		}
		//erikoisalennus lasketaan vain riveille joilla EI ole NETTOHINTAA
		if ($laskurow['erikoisale'] != 0) {
			$apu1 = round($laskurow['erikoisale']/100,2);	// erikoisale prosentti
			$apu2 = round($myht*$apu1,2); 					// erikoisalen m‰‰r‰
			$apu3 = round((1-$apu1)*$myht,2);				// loppusumma

			//Kakki yhteens‰
			$kyht = $apu3 + $nyht;
		}
		else {
			//Kakki yhteens‰
			$kyht = $myht + $nyht;
		}

		//laitetaan t‰lle alkuper‰iselle tilaukselle hyvitysrivi joka hyvitt‰‰ summanjakoprososa2:verran loopusummasta
		if ($yhtiorow["alv_kasittely"] == '') {
			$hyvitetaan = round($mrow["summanjakoprososa2"]/100*$kyht*(1+($laskurow["alv"]/100)),2);
		}
		else {
			$hyvitetaan = round($mrow["summanjakoprososa2"]/100*$kyht,2);
		}

		if ($hyvitetaan != 0) {
			$otunnus		= $laskurow['tunnus'];
			$hinta			= $hyvitetaan*-1;
			$trow["alv"]	= $laskurow["alv"];
			$nimitys		= t("Maksuehtohyvitys");

			if (trim($yhtiorow["maksuehto_tuotenumero"]) == "") {
				$tuoteno = t("Tuote");
			}
			else {
				$tuoteno = $yhtiorow["maksuehto_tuotenumero"];
			}

			list($hinta, $alv) = alv($laskurow, $trow, $hinta, '', '');

			$query  = "	INSERT into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti, keratty, kerattyaika, toimitettu, toimitettuaika, toimaika)
						values ('$hinta', 'N', '1', '1', '$otunnus', '$tuoteno', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '','$kukarow[kuka]',now(),'$kukarow[kuka]',now(),now())";
			$addtil = pupe_query($query);

			//p‰ivitet‰‰n alkuper‰isen otsikon maksuehto
			$query = "	UPDATE lasku set
						maksuehto 	= '$mrow[osamaksuehto1]'
						where yhtio	= '$kukarow[yhtio]'
						and tunnus 	= '$laskurow[tunnus]'";
			$varresult = pupe_query($query);

			// vaikka meill‰ onkin laskurow, haetaan silti laskun tiedot fetch_arraylla, jotta saadaan kiva looppi
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus = '$laskurow[tunnus]'";
			$laskufetch_res = pupe_query($query);
			$laskufetch_row = mysql_fetch_assoc($laskufetch_res);

			$query = "INSERT INTO lasku SET ";

			for ($i = 0; $i < mysql_num_fields($laskufetch_res); $i++) {

				$fieldname = mysql_field_name($laskufetch_res, $i);

				if ($fieldname == 'maksuehto') {
					$query .= $fieldname."='$mrow[osamaksuehto2]',";
				}
				elseif ($fieldname == 'alatila') {
					$query .= $fieldname."='D',";
				}
				elseif ($fieldname == 'tila') {
					$query .= $fieldname."='L',";
				}
				elseif ($fieldname == 'kate_korjattu') {
					$query .= $fieldname."=NULL,";
				}
				elseif ($fieldname != 'tunnus') {
					$query .= $fieldname."='".$laskufetch_row[$fieldname]."',";
				}
			}

			$query = substr($query, 0, -1);
			$laskuinsert_result = pupe_query($query);
			$uusitunnus = (string) mysql_insert_id();

			$query = "	SELECT *
						FROM laskun_lisatiedot
						WHERE yhtio = '$kukarow[yhtio]'
						AND otunnus = '$laskurow[tunnus]'";
			$lisatiedot_res = pupe_query($query);
			$lisatiedot_row = mysql_fetch_assoc($lisatiedot_res);

			$query = "INSERT INTO laskun_lisatiedot SET ";

			for ($i = 0; $i < mysql_num_fields($lisatiedot_res); $i++) {

				$fieldname = mysql_field_name($lisatiedot_res, $i);

				if ($fieldname == 'laatija') {
					$query .= $fieldname."='$kukarow[kuka]',";
				}
				elseif ($fieldname == 'luontiaika') {
					$query .= $fieldname."=now(),";
				}
				elseif ($fieldname == 'otunnus') {
					$query .= $fieldname."='$uusitunnus',";
				}
				elseif ($fieldname != 'tunnus') {
					$query .= $fieldname."='".$lisatiedot_row[$fieldname]."',";
				}
			}

			$query = substr($query, 0, -1);
			$lisatiedot_result = pupe_query($query);


			$otunnus		= $uusitunnus;
			$trow["alv"]	= $laskurow["alv"];
			$nimitys		= t("Maksuehtoveloitus");

			if (trim($yhtiorow["maksuehto_tuotenumero"]) == "") {
				$tuoteno = t("Tuote");
			}
			else {
				$tuoteno = $yhtiorow["maksuehto_tuotenumero"];
			}

			list($hinta, $alv) = alv($laskurow, $trow, $hyvitetaan, '', '');

			$query  = "	INSERT into tilausrivi (hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti, keratty, kerattyaika, toimitettu, toimitettuaika, toimaika)
						values ('$hinta', 'N', '1', '1', '$otunnus', '$tuoteno', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '','$kukarow[kuka]',now(),'$kukarow[kuka]',now(),now())";
			$addtil = pupe_query($query);
		}
	}

	//echotaan rudulle jos kyseess‰ ei ole batch-ajo
	if ($tulos_ulos == "" and  $silent == "") {
		echo "<br><font class='message'>".$tulos_ulos_ehtosplit."</font><br><br>";
	}

?>