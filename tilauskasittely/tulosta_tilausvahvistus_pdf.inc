<?php

	// Haetaan asiakkaan tiedot
	$query = "  SELECT lahetteen_jarjestys, lahetteen_jarjestys_suunta, luokka, puhelin, if(asiakasnro!='', asiakasnro, ytunnus) asiakasnro
				FROM asiakas
				WHERE tunnus = '$laskurow[liitostunnus]'
				and yhtio = '$kukarow[yhtio]'";
	$result = pupe_query($query);
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
	elseif ($naytatvale == 7) {
		$lahetetyyppi = "tulosta_lahete_ean.inc";
	}
	else {
		$lahetetyyppi = "tulosta_lahete.inc";
	}

	require("tulosta_lahete.inc");

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
		$abures = pupe_query($query);
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
		$abures = pupe_query($query);
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
	$sorttauskentta = generoi_sorttauskentta($asrow["lahetteen_jarjestys"] != "" ? $asrow["lahetteen_jarjestys"] : $yhtiorow["lahetteen_jarjestys"]);
	$order_sorttaus = $asrow["lahetteen_jarjestys_suunta"] != "" ? $asrow["lahetteen_jarjestys_suunta"] : $yhtiorow["lahetteen_jarjestys_suunta"];

	if ($yhtiorow["lahetteen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
	else $pjat_sortlisa = "";

	$query_ale_lisa = generoi_alekentta('M');

	$ale_query_select_lisa = generoi_alekentta_select('erikseen', 'M');

	// generoidaan lähetteelle ja keräyslistalle rivinumerot
	// Rivit groupataan tilausvahvistukselle
	if (strpos($laskurow['tilausvahvistus'], 'Y') !== FALSE) {
		$query = "  SELECT tilausrivi.perheid, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.kommentti, tilausrivi.tilaajanrivinro, tilausrivi.otunnus, tilausrivi.netto, {$ale_query_select_lisa}
					tilausrivi.toimaika, tilausrivi.hinta, tilausrivi.keratty, tilausrivi.var, tilausrivi.yksikko,
					$sorttauskentta,
					if (tilausrivi.tuoteno='$yhtiorow[rahti_tuotenumero]', 2, if(tilausrivi.var='J', 1, 0)) jtsort,
					if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
					if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
					tuote.sarjanumeroseuranta,
					sum(tilausrivi.varattu) varattu,
					sum(tilausrivi.kpl) kpl,
					sum(tilausrivi.jt) jt,
					sum(tilausrivi.hinta),
					min(tilausrivi.tunnus) tunnus,
					sum(tilausrivi.tilkpl) tilkpl,
					sum(round(if (tuote.myymalahinta != 0, tuote.myymalahinta/if(tuote.myyntihinta_maara>0, tuote.myyntihinta_maara, 1), tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]')) ovhhinta,
					sum(round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]')) rivihinta
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and $wherelisa
					$tyyppilisa
					and tilausrivi.tuoteno != '{$yhtiorow['ennakkomaksu_tuotenumero']}'
					and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
					GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18
					ORDER BY $pjat_sortlisa jtsort, sorttauskentta $order_sorttaus, tilausrivi.tunnus";
	}
	else {
		$query = "  SELECT tilausrivi.*,
					round(if (tuote.myymalahinta != 0, tuote.myymalahinta/if(tuote.myyntihinta_maara>0, tuote.myyntihinta_maara, 1), tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),'$yhtiorow[hintapyoristys]') ovhhinta,
					round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
					$sorttauskentta,
					if (tilausrivi.tuoteno='$yhtiorow[rahti_tuotenumero]', 2, if(tilausrivi.var='J', 1, 0)) jtsort,
					if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
					if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
					tuote.sarjanumeroseuranta
					FROM tilausrivi
					JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and $wherelisa
					$tyyppilisa
					and tilausrivi.tuoteno != '{$yhtiorow['ennakkomaksu_tuotenumero']}'
					and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
					ORDER BY $pjat_sortlisa jtsort, sorttauskentta $yhtiorow[lahetteen_jarjestys_suunta], tilausrivi.tunnus";
	}

	$riresult = pupe_query($query);

	// tämä siksi koska halutaan alkuperäisen tilauksen tilausnumero pdf-filuun.
	if (($laskurow["tunnusnippu"] > 0 or $yhtiorow["tilausvahvistus_lahetys"] == 1) and $laskurow['vanhatunnus'] > 0) {
		$laskurow["tunnus"] = $laskurow['vanhatunnus'];
	}

	$params_lahete = array(
	'arvo'						=> 0,
	'asrow'						=> $asrow,
	'boldi'						=> $boldi,
	'ei_otsikoita'				=> '',
	'extranet_tilausvahvistus'	=> $extranet_tilausvahvistus,
	'iso'						=> $iso,
	'jtid'						=> '',
	'kala'						=> 0,
	'kassa_ale'					=> '',
	'kieli'						=> $kieli,
	'lahetetyyppi'				=> $lahetetyyppi,
	'laskurow'					=> $laskurow,
	'naytetaanko_rivihinta'		=> $naytetaanko_rivihinta,
	'norm'						=> $norm,
	'page'						=> NULL,
	'pdf'						=> NULL,
	'perheid'					=> 0,
	'pieni'						=> $pieni,
	'pieni_boldi'				=> $pieni_boldi,
	'pitkattuotteet'			=> FALSE,
	'rectparam'					=> $rectparam,
	'riviresult'				=> $riresult,
	'rivinkorkeus'				=> $rivinkorkeus,
	'rivinumerot'				=> "",
	'row'						=> NULL,
	'sivu'						=> 1,
	'summa'						=> 0,
	'tee'						=> $tee,
	'thispage'					=> NULL,
	'toim'						=> $toim,
	'tots'						=> 0,
	'tuotenopituus'				=> '',
	'nimityskohta'				=> '',
	'nimitysleveys'				=> '',
	'tyyppi'					=> 'TILAUSVAHVISTUS',
	'useita'					=> '',
	'yhteensamaara'				=> 0,
	);

	// Aloitellaan lähetteen teko
	$params_lahete = alku_lahete($params_lahete);

	// Piirretään rivit
	mysql_data_seek($riresult,0);

	while ($row = mysql_fetch_assoc($riresult)) {
		$params_lahete["row"] = $row;
		$params_lahete = rivi_lahete($params_lahete);
	}

	// Loppulaatikot
	$params_lahete["tots"] = 1;
	$params_lahete = loppu_lahete($params_lahete);

	if (strpos($_SERVER['SCRIPT_NAME'], "tulostakopio.php") === FALSE and $yhtiorow['tilausvahvistus_tallenna'] == 'K') {
		$tilausvahvistus_tallenna = "/tmp/Tilausvahvistus-".md5(uniqid(mt_rand(), true)).".pdf";

		//kirjoitetaan pdf faili levylle..
		$fh_liite = fopen($tilausvahvistus_tallenna, "w");
		if (fwrite($fh_liite, $params_lahete["pdf"]->generate()) === FALSE) die("PDF create error $tilausvahvistus_tallenna");
		fclose($fh_liite);
	}
	else {
		$tilausvahvistus_tallenna = "";
	}

	$params_lahete["komento"] = $komento["Tilausvahvistus"];

	//tulostetaan sivu
	print_pdf_lahete($params_lahete);
?>