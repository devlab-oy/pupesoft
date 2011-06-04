<?php

	//sis‰‰n tarvitaan $laskurow jossa on laskun tiedot.


	$tulos_ulos_ehtosplit = "";

	// otetaan maksuehto t‰ss‰ heti alussa selville
	$query = "	SELECT *
				from maksuehto
				where yhtio	= '$kukarow[yhtio]'
				and tunnus	= '$laskurow[maksuehto]'";
	$yresult = mysql_query($query) or pupe_error($query);
	$mrow = mysql_fetch_array($yresult);

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

		$presult = mysql_query($query) or pupe_error($query);

		$nyht = 0;
		$myht = 0;
		$kyht = 0;

		while ($prow = mysql_fetch_array ($presult)) {

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
			$addtil = mysql_query($query) or pupe_error($query);

			//p‰ivitet‰‰n alkuper‰isen otsikon maksuehto
			$query = "	UPDATE lasku set
						maksuehto 	= '$mrow[osamaksuehto1]'
						where yhtio	= '$kukarow[yhtio]'
						and tunnus 	= '$laskurow[tunnus]'";
			$varresult = mysql_query($query) or pupe_error($query);

			// vaikka meill‰ onkin laskurow, haetaan silti laskun tiedot fetch_arraylla, jotta saadaan kiva looppi
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus = '$laskurow[tunnus]'";
			$laskufetch_res = mysql_query($query) or pupe_error($query);
			$laskufetch_row = mysql_fetch_array($laskufetch_res);

			$query = "INSERT INTO lasku SET ";

			for ($i = 0; $i < mysql_num_fields($laskufetch_res); $i++) {
				if (mysql_field_name($laskufetch_res, $i) == 'maksuehto') {
					$query .= mysql_field_name($laskufetch_res, $i)."='$mrow[osamaksuehto2]',";
				}
				elseif (mysql_field_name($laskufetch_res, $i) == 'alatila') {
					$query .= mysql_field_name($laskufetch_res, $i)."='D',";
				}
				elseif (mysql_field_name($laskufetch_res, $i) == 'tila') {
					$query .= mysql_field_name($laskufetch_res, $i)."='L',";
				}
				elseif (mysql_field_name($laskufetch_res, $i) != 'tunnus') {
					$query .= mysql_field_name($laskufetch_res, $i)."='".$laskufetch_row[$i]."',";
				}
			}

			$query = substr($query, 0, -1);
			$laskuinsert_result = mysql_query($query) or pupe_error($query);
			$uusitunnus = (string) mysql_insert_id();

			$query = "	SELECT *
						FROM laskun_lisatiedot
						WHERE yhtio = '$kukarow[yhtio]'
						AND otunnus = '$laskurow[tunnus]'";
			$lisatiedot_res = mysql_query($query) or pupe_error($query);
			$lisatiedot_row = mysql_fetch_array($lisatiedot_res);

			$query = "INSERT INTO laskun_lisatiedot SET ";

			for ($i = 0; $i < mysql_num_fields($lisatiedot_res); $i++) {
				if (mysql_field_name($lisatiedot_res, $i) == 'laatija') {
					$query .= mysql_field_name($lisatiedot_res, $i)."='$kukarow[kuka]',";
				}
				elseif (mysql_field_name($lisatiedot_res, $i) == 'luontiaika') {
					$query .= mysql_field_name($lisatiedot_res, $i)."=now(),";
				}
				elseif (mysql_field_name($lisatiedot_res, $i) == 'otunnus') {
					$query .= mysql_field_name($lisatiedot_res, $i)."='$uusitunnus',";
				}
				elseif (mysql_field_name($lisatiedot_res, $i) != 'tunnus') {
					$query .= mysql_field_name($lisatiedot_res, $i)."='".$lisatiedot_row[$i]."',";
				}
			}

			$query = substr($query, 0, -1);
			$lisatiedot_result = mysql_query($query) or pupe_error($query);


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
			$addtil = mysql_query($query) or pupe_error($query);
		}
	}

	//echotaan rudulle jos kyseess‰ ei ole batch-ajo
	if ($tulos_ulos == "" and  $silent == "") {
		echo "<br><font class='message'>".$tulos_ulos_ehtosplit."</font><br><br>";
	}

?>