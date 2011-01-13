<?php

	//hatetaan asiakkaan lähetetyyppi
	$query = "  SELECT lahetetyyppi, luokka, puhelin, if(asiakasnro!='', asiakasnro, ytunnus) asiakasnro
				FROM asiakas
				WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$asrow = mysql_fetch_assoc($result);

	if ($naytatvale == 2) {
		$lahetetyyppi = "tulosta_lahete_eialeja.inc";
	}
	elseif ($naytatvale == 3) {
		$lahetetyyppi = "tulosta_lahete_eiale_eihinta.inc";
	}
	elseif ($naytatvale == 4) {
		$lahetetyyppi = "tulosta_lahete_eialehintoja.inc";
	}
	elseif ($naytatvale == 5) {
		$lahetetyyppi = "tulosta_lahete_hae_hinnat.inc";
	}
	elseif ($naytatvale == 6) {
		$lahetetyyppi = "tulosta_lahete_custom.inc";
	}
	else {
		$lahetetyyppi = "tulosta_lahete.inc";
	}

	require_once ("tulosta_lahete.inc");

	if ($laskurow["tila"] == "L" or $laskurow["tila"] == "N") {
		$tyyppilisa = " and tilausrivi.tyyppi in ('L') ";
	}
	else {
		$tyyppilisa = " and tilausrivi.tyyppi in ('L','G','W','E','T') ";
	}

	if ($yhtiorow["tilausvahvistus_lahetys"] == 1 and $laskurow["vanhatunnus"] > 0) {
		$query = " 	SELECT group_concat(tunnus) tunnukset
					FROM lasku use index (yhtio_vanhatunnus)
					WHERE yhtio		= '$kukarow[yhtio]'
					and vanhatunnus = '$laskurow[vanhatunnus]'
					and tila IN ('N','L','A')";
		$abures = mysql_query($query) or pupe_error($query);
		$aburow = mysql_fetch_assoc($abures);

		if ($aburow["tunnukset"] != "") {
			$wherelisa = " tilausrivi.otunnus IN ($aburow[tunnukset])";
			$laskurow["nippu"] = $aburow["tunnukset"];
		}
		else {
			$wherelisa = "tilausrivi.otunnus = '$laskurow[tunnus]'";
			$laskurow["nippu"] = $laskurow["tunnus"];
		}
	}
	elseif ($laskurow["tunnusnippu"] > 0 and $laskurow["clearing"] != "JT-TILAUS") {
		$query = "  SELECT group_concat(tunnus) tunnukset
					FROM lasku
					WHERE yhtio 	= '$kukarow[yhtio]'
					and tunnusnippu = '$laskurow[tunnusnippu]'
					and tila IN ('N','L','A')";
		$abures = mysql_query($query) or pupe_error($query);
		$aburow = mysql_fetch_assoc($abures);

		if ($aburow["tunnukset"] != "") {
			$wherelisa = " tilausrivi.otunnus IN ($aburow[tunnukset])";
			$laskurow["nippu"] = $aburow["tunnukset"];
		}
		else {
			$wherelisa = "tilausrivi.otunnus = '$laskurow[tunnus]'";
			$laskurow["nippu"] = $laskurow["tunnus"];
		}
	}
	else {
		$wherelisa = "tilausrivi.otunnus = '$laskurow[tunnus]'";
		$laskurow["nippu"] = $laskurow["tunnus"];
	}

	// katotaan miten halutaan sortattavan
	$sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);

	if ($yhtiorow["lahetteen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
	else $pjat_sortlisa = "";

	// generoidaan lähetteelle ja keräyslistalle rivinumerot
	// Rivit groupataan tilausvahvistukselle
	if (strpos($laskurow['tilausvahvistus'], 'Y') !== FALSE) {
		$query = "  SELECT tilausrivi.perheid, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.kommentti, tilausrivi.tilaajanrivinro, tilausrivi.otunnus, tilausrivi.netto, tilausrivi.ale,
					tilausrivi.toimaika, tilausrivi.hinta, tilausrivi.keratty, tilausrivi.var, tilausrivi.yksikko,
					$sorttauskentta,
					if (tilausrivi.tuoteno='$yhtiorow[rahti_tuotenumero]', 2, if(tilausrivi.var='J', 1, 0)) jtsort,
					if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
					sum(tilausrivi.varattu) varattu,
					sum(tilausrivi.kpl) kpl,
					sum(tilausrivi.jt) jt,
					sum(tilausrivi.hinta),
					min(tilausrivi.tunnus) tunnus,
					sum(tilausrivi.tilkpl) tilkpl,
					sum(round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]')) ovhhinta,
					sum(round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),'$yhtiorow[hintapyoristys]')) rivihinta
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and $wherelisa
					$tyyppilisa
					and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
					GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16
					ORDER BY $pjat_sortlisa jtsort, sorttauskentta $yhtiorow[lahetteen_jarjestys_suunta], tilausrivi.tunnus";
	}
	else {
		$query = "  SELECT tilausrivi.*,
					round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]') ovhhinta,
					round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),'$yhtiorow[hintapyoristys]') rivihinta,
					$sorttauskentta,
					if (tilausrivi.tuoteno='$yhtiorow[rahti_tuotenumero]', 2, if(tilausrivi.var='J', 1, 0)) jtsort,
					if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and $wherelisa
					$tyyppilisa
					and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
					ORDER BY $pjat_sortlisa jtsort, sorttauskentta $yhtiorow[lahetteen_jarjestys_suunta], tilausrivi.tunnus";
	}

	$riresult = mysql_query($query) or pupe_error($query);

	unset($pdf);
	unset($page);

	$sivu  = 1;
	$total = 0;

	// Aloitellaan lähetteen teko
	$page[$sivu] = alku_lahete($toim);

	// generoidaan rivinumerot
	$rivinumerot    = array();
	$tuotenopituus  = 0;
	$nimityspituus  = 0;
	$pitkattuotteet = FALSE;

	while ($row = mysql_fetch_assoc($riresult)) {
		$rivinumerot[$row["tunnus"]] = $row["tunnus"];

		$tuotenopituus += $pdf->strlen($row["tuoteno"], $norm);
		$nimityspituus += $pdf->strlen($row["nimitys"], $norm);
	}

	// Jos tuotenumerot ovat hyvin pitkät
	if (($tuotenopituus / mysql_num_rows($riresult)) > 140 or ($nimityspituus / mysql_num_rows($riresult)) > 140) {
		#$pitkattuotteet = TRUE;
	}

	// Määritetään dynaamisesti tuoteno ja nimitys-sarakkeiden leveydet
	$tuotenopituus = round($tuotenopituus/($tuotenopituus+$nimityspituus), 2);

	sort($rivinumerot);

	$kal = 1;

	foreach ($rivinumerot as $rivino) {
		$rivinumerot[$rivino] = $kal;
		$kal++;
	}

	mysql_data_seek($riresult,0);

	// Ekan sivun otsikot
	rivi_otsikot_lahete($page[$sivu], $naytetaanko_rivihinta);

	while ($row = mysql_fetch_assoc($riresult)) {
		rivi_lahete($page[$sivu], $toim, $naytetaanko_rivihinta);
		$total+= $row["rivihinta"];
	}

	loppu_lahete($page[$sivu], 1, $toim, $naytetaanko_rivihinta);

	if ($yhtiorow['tilausvahvistus_tallenna'] == 'K') {
		$tilausvahvistus_tallenna = "/tmp/Tilausvahvistus-".md5(uniqid(mt_rand(), true)).".pdf";

		//kirjoitetaan pdf faili levylle..
		$apu_pdf = $pdf; // otetaan globaali käyttöön..
		$fh_liite = fopen($tilausvahvistus_tallenna, "w");
		if (fwrite($fh_liite, $apu_pdf->generate()) === FALSE) die("PDF create error $tilausvahvistus_tallenna");
		fclose($fh_liite);
	}
	else {
		$tilausvahvistus_tallenna = "";
	}

	//tulostetaan sivu
	print_pdf_lahete($komento["Tilausvahvistus"], $toim);
?>