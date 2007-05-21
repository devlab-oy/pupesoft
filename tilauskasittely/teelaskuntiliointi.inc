<?php

// tarvitaan arrayt $kukarow, $yhtiorow, $mehtorow ja $lasku

$tulos_ulos_tiliointi = "";

//Tehtaan tiliointiaineisto suoraan sql:lla
$query = "	SELECT tilausrivi.alv,
			tuote.tilino, tuote.tilino_eu, tuote.tilino_ei_eu,
			tuote.kustp, tuote.kohde, tuote.projekti,
			tuotteen_alv.tilino alvtilino, tuotteen_alv.kustp alvkusp, tuotteen_alv.kohde alvkohde, tuotteen_alv.projekti alvprojekti,
			yhtion_toimipaikat.tilino yhtiotilino, yhtion_toimipaikat.kustp yhtiokusp, yhtion_toimipaikat.kohde yhtiokohde, yhtion_toimipaikat.projekti yhtioprojekti, yhtion_toimipaikat.vat_numero,
			sum(tilausrivi.rivihinta) summa,
			sum(tilausrivi.kate) kate,
			sum(if(tuote.ei_saldoa='', tilausrivi.rivihinta-tilausrivi.kate,0)) varmuutos,
			GROUP_CONCAT(tilausrivi.tunnus SEPARATOR ',') tunnukset,
			if(tilausrivi.alv >= 500 and tilausrivi.kpl < 0, 'KYLLA', 'EI') marginaaliostot
			FROM tilausrivi
			LEFT JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
			LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio and tuotteen_alv.tuoteno = tilausrivi.tuoteno and tuotteen_alv.maa = '$lasku[maa]' and tuotteen_alv.maa != '')
			LEFT JOIN yhtion_toimipaikat ON (yhtion_toimipaikat.yhtio = tilausrivi.yhtio and yhtion_toimipaikat.vat_numero = '$lasku[ovttunnus]' and yhtion_toimipaikat.vat_numero != '')
			WHERE tilausrivi.uusiotunnus = '$lasku[tunnus]'
			and tilausrivi.tyyppi		 = 'L'
			and tilausrivi.yhtio		 = '$kukarow[yhtio]'
			GROUP BY tilausrivi.alv, marginaaliostot, tuote.tilino, tuote.tilino_eu, tuote.tilino_ei_eu, tuote.kustp, tuote.kohde, tuote.projekti";
$xresult = mysql_query($query) or pupe_error($query);

$onkoalvia = ""; // apumuuttuja, ett‰ tiedet‰‰n onko alvillista

if (mysql_num_rows($xresult) == 0) {
	$tulos_ulos_tiliointi .= "<font class='message'>".t("VIRHE: Laskulla ei ollut yht‰‰n rivi‰").": $lasku[tunnus]</font><br><br>\r\n";
}
else {

	$varmuutos  = 0;
	$vastasumma = 0;
	$marginaali = 0;
	$ostosumma  = 0;

	// Tehdaan myynnin kirjaukset
	while ($xrow = mysql_fetch_array($xresult)) {

		$lukko 			= "";
		$tunnus 		= $lasku['tunnus'];
		$tapvm			= $lasku['tapvm'];
		$loppusumma		= $lasku['summa'];
		$tilino			= 0;
		$tilino_eu		= 0;
		$tilino_ei_eu	= 0;
		$kustp			= 0;
		$kohde			= 0;
		$projekti		= 0;

		// Valitaan myyntitili, jos rivill‰ on alv yli 500 niin kyseess‰ on marginaalimyynti‰
		if ($xrow["alv"] >= 500 and $xrow["marginaaliostot"] == "EI") {
			// Tuotteiden ostohinnat
			$query = "	SELECT round(sum(if(tilausrivi.kpl!=0, tilausrivi.rivihinta/tilausrivi.kpl, 0)), 2) ostosumma
						FROM sarjanumeroseuranta
						JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
						WHERE sarjanumeroseuranta.yhtio 		 = '$kukarow[yhtio]'
						and sarjanumeroseuranta.myyntirivitunnus in ($xrow[tunnukset])
						HAVING ostosumma!=0";
			$xsarjares = mysql_query($query) or pupe_error($query);
			$sarjarow = mysql_fetch_array($xsarjares);

			$ostohinta = (float) $sarjarow["ostosumma"];

			// Katsotaan onko sarjanumerolle liitetty kulukeikka
			$query  = "	select lasku.laskunro
						FROM sarjanumeroseuranta
						JOIN lasku ON lasku.yhtio=sarjanumeroseuranta.yhtio and lasku.liitostunnus=sarjanumeroseuranta.tunnus and lasku.ytunnus=sarjanumeroseuranta.tunnus and lasku.tila = 'K' and lasku.alatila = 'S'
						WHERE sarjanumeroseuranta.yhtio 		 = '$kukarow[yhtio]'
						and sarjanumeroseuranta.myyntirivitunnus in ($xrow[tunnukset])";
			$keikkares = mysql_query($query) or pupe_error($query);

			while ($kulukeikkarow = mysql_fetch_array($keikkares)) {
				// Haetaan kaikki keikkaan liitettyjen laskujen summa
				$query = "	SELECT round(sum(summa*if(maksu_kurssi!=0, maksu_kurssi, vienti_kurssi)),2) kulusumma
							FROM lasku
							WHERE yhtio		= '$kukarow[yhtio]'
							and tila 		= 'K'
							and laskunro 	= '$kulukeikkarow[laskunro]'
							and vanhatunnus <> 0
							and vienti in ('B','E','H')";
				$kukeikkares = mysql_query($query) or pupe_error($query);
				$kulukulurow = mysql_fetch_array($kukeikkares);

				$ostohinta	+= $kulukulurow["kulusumma"];
			}

			// Valitaan myyntitili, t‰ss‰ on marginaalimyynti‰
			$selite 	= "Marginaaliverolasku/Myynti ". $lasku['nimi']." ".$lasku['nimitark'];

			$tilino 		= (int) $yhtiorow['myynti_marginaali'];
			$tilino_eu 		= (int) $yhtiorow['myynti_marginaali'];
			$tilino_ei_eu	= (int) $yhtiorow['myynti_marginaali'];

			//Myyntimarginaali
			$marginaali = $xrow['summa'] - $ostohinta;

			//Tilioidaan veroton osuus marginaalimyynnist‰
			$tiliointisumma = $ostosumma = $ostohinta;

			//Tiliˆid‰‰n margiinaalimyyntitapahtuma
			$query = "	INSERT into tiliointi set
						yhtio 		= '$kukarow[yhtio]',
						ltunnus 	= '$tunnus',
						tilino 		= '$tilino',
						kustp 		= '$xrow[kustp]',
						kohde 		= '$xrow[kohde]',
						projekti	= '$xrow[projekti]',
						tapvm 		= '$tapvm',
						summa		= $tiliointisumma*-1,
						vero 		= 0,
						selite 		= '$selite',
						lukko 		= '1',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now()";
			$laskutusres = mysql_query($query) or pupe_error($query);

			//Sitten pit‰‰ viel‰ tilioid‰ itse marginaali joka on normaalia verollista myynti‰
			$selite 		= "Marginaaliverolasku/Marginaali ". $lasku['nimi']." ".$lasku['nimitark'];
			$tiliointisumma = $marginaali;
			$lukko			= "1";

			//Poistetaan 500 yksikkˆ‰ alvista
			$xrow['alv'] = $xrow['alv'] - 500;

			$alv = round($tiliointisumma - ($tiliointisumma / (1+ $xrow['alv'] / 100)), 2);
			$tiliointisumma -= $alv;
		}
		elseif ($xrow["alv"] >= 500 and $xrow["marginaaliostot"] == "KYLLA") {
			// Valitaan ostotili, t‰ss‰ on marginaaliostoa
			$selite 		= "Marginaaliverolasku/Osto ". $lasku['nimi']." ".$lasku['nimitark'];

			$tilino 		= (int) $yhtiorow['osto_marginaali'];
			$tilino_eu 		= (int) $yhtiorow['osto_marginaali'];
			$tilino_ei_eu	= (int) $yhtiorow['osto_marginaali'];

			$tiliointisumma 	= $xrow['summa'];
			$xrow["varmuutos"] 	= $xrow["summa"];

			$xrow["alv"] 	= 0;
			$alv 		 	= 0;
		}
		else {

			// Valitaan myyntitili, t‰ss‰ on normaalimyynti‰
			$selite 	= "Lasku ". $lasku['nimi']." ".$lasku['nimitark'];

			// jos meill‰ on VAT-numero
			if ($xrow["vat_numero"] != "") {

				// ensiks kokeillaan tuotteen alv-liitoksen tiliˆintis‰‰ntˆj‰
				$tilino 		= (int) $xrow['alvtilino'];
				$kustp			= (int) $xrow["alvkustp"];
				$kohde 			= (int) $xrow["alvkohde"];
				$projekti		= (int) $xrow["alvprojekti"];

				// sitten toimipaikan tiliˆintis‰‰ntˆj‰
				if ($tilino == 0) {
					$tilino = (int) $xrow['yhtiotilino'];
				}
				if ($kustp == 0) {
					$kustp = (int) $xrow["yhtiokustp"];
				}
				if ($kohde == 0) {
					$kohde = (int) $xrow["yhtiokohde"];
				}
				if ($projekti == 0) {
					$projekti = (int) $xrow["yhtioprojekti"];
				}

			}

			// sitten tuotteen tiliˆintis‰‰ntˆj‰
			if ($tilino == 0) {
				$tilino = (int) $xrow['tilino'];
			}
			if ($tilino_eu == 0) {
				$tilino_eu = (int) $xrow['tilino_eu'];
			}
			if ($tilino_ei_eu == 0) {
				$tilino_ei_eu = (int) $xrow['tilino_ei_eu'];
			}

			$tiliointisumma = $xrow['summa'];
			$alv = round($xrow['alv'] / 100 * $tiliointisumma, 2);
		}

		// jos ei viel‰k‰‰n lˆydy otetaan tilit yhtiˆlt‰
		if ($tilino == 0) {
			$tilino = $yhtiorow['myynti'];
		}
		if ($tilino_eu == 0) {
			$tilino_eu = $yhtiorow['myynti_eu'];
		}
		if ($tilino_ei_eu == 0) {
			$tilino_ei_eu = $yhtiorow['myynti_ei_eu'];
		}

		// jos kustannuspaikkaa, kohdetta ja projektia ei ole annettu otetaan ne tuotteelta
		if ($kustp == 0) {
			$kustp = $xrow["kustp"];
		}
		if ($kohde == 0) {
			$kohde = $xrow["kohde"];
		}
		if ($projekti == 0) {
			$projekti = $xrow["projekti"];
		}

		// valitaan myyntitili vienin mukaan
		if ($lasku['vienti'] == 'E') $tilino = $tilino_eu;
		if ($lasku['vienti'] == 'K') $tilino = $tilino_ei_eu;

		//Tiliˆid‰‰n myynti
		$query = "	INSERT into tiliointi set
					yhtio 		= '$kukarow[yhtio]',
					ltunnus		= '$tunnus',
					tilino		= '$tilino',
					kustp 		= '$kustp',
					kohde 		= '$kohde',
					projekti	= '$projekti',
					tapvm 		= '$tapvm',
					summa 		= $tiliointisumma * -1,
					vero 		= '$xrow[alv]',
					selite 		= '$selite',
					lukko 		= '$lukko',
					laatija 	= '$kukarow[kuka]',
					laadittu 	= now()";
		$laskutusres = mysql_query($query) or pupe_error($query);

		$lukko = "";

		//Tiliˆidaan ALV, jos sit‰ on
		if ($xrow['alv'] > 0) {

			$onkoalvia = "kylla"; // apumuuttuja, ett‰ tiedet‰‰n alempana, ett‰ tehtiin alvikirjauksia

			$isa = mysql_insert_id ($link);
			$query = "	INSERT into tiliointi set
						yhtio 		= '$kukarow[yhtio]',
						ltunnus 	= '$tunnus',
						tilino 		= '$yhtiorow[alv]',
						kustp 		= '',
						kohde 		= '',
						projekti 	= '',
						tapvm 		= '$tapvm',
						summa 		= $alv * -1,
						vero 		= '0',
						selite 		= '$selite',
						lukko 		= '1',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now(),
						aputunnus	= $isa";
			$laskutusres = mysql_query($query) or pupe_error($query);
		}

		//Lasketaan vastasummaa jotta voidaan tutkia pyˆristyseroa
		$vastasumma += $tiliointisumma + $alv + $ostosumma;

		//Lasketaan varastonmuutos
		$varmuutos = $xrow["varmuutos"];

		// Kirjataan varastonmuutos, jos sita on
		if ($varmuutos != 0) {

			if($xrow["marginaaliostot"] == "KYLLA") {
				$selite		= "Varastoontulo ". $lasku['nimi'];
			}
			else {
				$selite		= "Varastostamyynti ". $lasku['nimi'];
			}

			$vero			= 0;
			$tiliper		= 'varasto';
			$tilian			= 'varastonmuutos';
			$tiliointisumma	= $varmuutos * -1;

			//Kirjataan 'varasto'-tilille
			$query = "	INSERT into tiliointi set
						yhtio 		= '$kukarow[yhtio]',
						ltunnus 	= '$tunnus',
						tilino 		= '$yhtiorow[$tiliper]',
						kustp 		= '$xrow[kustp]',
						kohde 		= '$xrow[kohde]',
						projekti	= '$xrow[projekti]',
						tapvm 		= '$tapvm',
						summa		= '$tiliointisumma',
						vero 		= '$vero',
						selite 		= '$selite',
						lukko 		= '',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now()";
			$laskutusres = mysql_query($query) or pupe_error($query);

			$tiliointisumma = $tiliointisumma * -1;

			//Kirjataan 'varastonmuutos'-tilille
			$query = "INSERT into tiliointi set
						yhtio 		= '$kukarow[yhtio]',
						ltunnus 	= '$tunnus',
						tilino 		= '$yhtiorow[$tilian]',
						kustp 		= '$xrow[kustp]',
						kohde 		= '$xrow[kohde]',
						projekti 	= '$xrow[projekti]',
						tapvm 		= '$tapvm',
						summa 		= '$tiliointisumma',
						vero 		= '$vero',
						selite 		= '$selite',
						lukko 		= '',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now()";
			$laskutusres = mysql_query($query) or pupe_error($query);
		}
	} // end while

	// Kirjataan myyntisaamiset tai kassa
	if ($mehtorow['kateinen'] != '') {
		$myysaatili = $yhtiorow['kassa'];
		$apusumma = $loppusumma;

		// jos maksetaan k‰teisell‰ ja meill‰ on kassa-alennusta, tehd‰‰n kirjaus... vain jos alvikirjauksia EI OLE tehty!
		if ($lasku['kasumma'] != '' and $onkoalvia == "") {
			$selite = "Lasku ". $lasku['nimi']." ".$lasku['nimitark'];
			$query  = "	INSERT into tiliointi set
		 		   		yhtio 		= '$kukarow[yhtio]',
		 		   		ltunnus 	= '$tunnus',
		 		   		tilino 		= '$yhtiorow[myynninkassaale]',
		 		   		kustp 		= '',
		 		   		kohde 		= '',
		 		   		projekti 	= '',
		 		   		tapvm 		= '$tapvm',
		 		   		summa 		= '$lasku[kasumma]',
		 		   		vero 		= '0',
		 		   		selite 		= '$selite',
		 		   		lukko 		= '1',
		 		   		laatija 	= '$kukarow[kuka]',
		 		   		laadittu 	= now()";
			$laskutusres = mysql_query($query) or pupe_error($query);

			// v‰hennet‰‰n loppusummasta kassa-ale
			$apusumma = $loppusumma - $lasku['kasumma'];
		}
	}
	elseif ($mehtorow["factoring"] != "") {
		$myysaatili = $yhtiorow['factoringsaamiset'];
		$apusumma = $loppusumma;
	}
	elseif ($konsrow["konserniyhtio"] != "") {
		$myysaatili = $yhtiorow['konsernimyyntisaamiset'];
		$apusumma = $loppusumma;
	}
	else {
		$myysaatili = $yhtiorow['myyntisaamiset'];
		$apusumma = $loppusumma;
	}

	$selite = "Lasku ". $lasku['nimi']." ".$lasku['nimitark'];

	$query = "	INSERT into tiliointi set
				yhtio 		= '$kukarow[yhtio]',
				ltunnus 	= '$tunnus',
				tilino 		= '$myysaatili',
				kustp 		= '',
				kohde 		= '',
				projekti 	= '',
				tapvm 		= '$tapvm',
				summa 		= '$apusumma',
				vero 		= '0',
				selite 		= '$selite',
				lukko 		= '1',
				laatija 	= '$kukarow[kuka]',
				laadittu 	= now()";
	$laskutusres = mysql_query($query) or pupe_error($query);

	// Kirjataan pyoristykset, jos niita on
	$vastasumma = round($vastasumma,2);

	if ($vastasumma != $loppusumma) {
		$query = "	INSERT into tiliointi set
					yhtio 		= '$kukarow[yhtio]',
					ltunnus 	= '$tunnus',
					tilino 		= '$yhtiorow[pyoristys]',
					kustp 		= '',
					kohde 		= '',
					projekti 	= '',
					tapvm 		= '$tapvm',
					summa 		= $vastasumma - $loppusumma,
					vero 		= '0',
					selite 		= '$selite',
					lukko 		= '',
					laatija 	= '$kukarow[kuka]',
					laadittu 	= now()";
		$laskutusres = mysql_query($query) or pupe_error($query);
	}

	$vastasumma = 0;
	$varmuutos = 0;

} // yht‰‰n tilausrivi‰ ei lˆytyny else-haara

//echotaan rudulle jos kyseess‰ ei ole batch-ajo
if ($tulos_ulos == "" and $silent == "") {
	echo $tulos_ulos_tiliointi;
}

?>