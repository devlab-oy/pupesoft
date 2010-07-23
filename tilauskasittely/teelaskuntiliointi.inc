<?php

// tarvitaan arrayt $kukarow, $yhtiorow, $mehtorow ja $lasku

$tulos_ulos_tiliointi = "";

//Tehtaan tiliointiaineisto suoraan sql:lla
$query = "	SELECT tilausrivi.alv alv,
			if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.tilino, '') != '', 		tuotteen_alv.tilino, 		if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(yhtion_toimipaikat.tilino, '') != '', 		yhtion_toimipaikat.tilino, 			if(ifnull(tuote.tilino, '') != '', 			tuote.tilino,	 	if(ifnull(asiakas.tilino, '') != '', 			asiakas.tilino, 			if(ifnull(yhtion_toimipaikat.tilino, '') != '', 		yhtion_toimipaikat.tilino, 			yhtio.myynti))))) tilino,
			if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.tilino_eu, '') != '', 	tuotteen_alv.tilino_eu, 	if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(yhtion_toimipaikat.tilino_eu, '') != '', 		yhtion_toimipaikat.tilino_eu, 		if(ifnull(tuote.tilino_eu, '') != '', 		tuote.tilino_eu, 	if(ifnull(asiakas.tilino_eu, '') != '', 		asiakas.tilino_eu, 			if(ifnull(yhtion_toimipaikat.tilino_eu, '') != '', 		yhtion_toimipaikat.tilino_eu, 		yhtio.myynti_eu))))) tilino_eu,
			if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.tilino_ei_eu, '') != '', tuotteen_alv.tilino_ei_eu, 	if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(yhtion_toimipaikat.tilino_ei_eu, '') != '', 	yhtion_toimipaikat.tilino_ei_eu, 	if(ifnull(tuote.tilino_ei_eu, '') != '',	tuote.tilino_ei_eu, if(ifnull(asiakas.tilino_ei_eu, '') != '', 		asiakas.tilino_ei_eu, 		if(ifnull(yhtion_toimipaikat.tilino_ei_eu, '') != '', 	yhtion_toimipaikat.tilino_ei_eu, 	yhtio.myynti_ei_eu))))) tilino_ei_eu,
			if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.kustp, 0) > 0, 			tuotteen_alv.kustp, 		if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(yhtion_toimipaikat.kustp, 0) > 0, 			yhtion_toimipaikat.kustp, 			if(ifnull(tuote.kustp, 0) > 0, 				tuote.kustp, 		if(ifnull(asiakas.kustannuspaikka, 0) > 0, 		asiakas.kustannuspaikka, 	if(ifnull(yhtion_toimipaikat.kustp, 0) > 0, 			yhtion_toimipaikat.kustp, 			yhtio.myynti_kustp))))) kustp,
			if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.kohde, 0) > 0, 			tuotteen_alv.kohde, 		if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(yhtion_toimipaikat.kohde, 0) > 0, 			yhtion_toimipaikat.kohde, 			if(ifnull(tuote.kohde, 0) > 0, 				tuote.kohde, 		if(ifnull(asiakas.kohde, 0) > 0, 				asiakas.kohde, 				if(ifnull(yhtion_toimipaikat.kohde, 0) > 0, 			yhtion_toimipaikat.kohde, 			yhtio.myynti_kohde))))) kohde,
			if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.projekti, 0) > 0, 		tuotteen_alv.projekti, 		if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(yhtion_toimipaikat.projekti, 0) > 0, 			yhtion_toimipaikat.projekti, 		if(ifnull(tuote.projekti, 0) > 0, 			tuote.projekti, 	if(ifnull(asiakas.projekti, 0) > 0, 			asiakas.projekti, 			if(ifnull(yhtion_toimipaikat.projekti, 0) > 0, 			yhtion_toimipaikat.projekti, 		yhtio.myynti_projekti))))) projekti,
			if(ifnull(yhtion_toimipaikat.toim_alv, '') != '', yhtion_toimipaikat.toim_alv, yhtio.alv) alvtilino,
			if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.tilino_marginaali, '') != '', 	tuotteen_alv.tilino_marginaali, if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(yhtion_toimipaikat.tilino_marginaali, '') != '', 	yhtion_toimipaikat.tilino_marginaali, 	if(ifnull(tuote.tilino_marginaali, '') != '', 	tuote.tilino_marginaali, 	if(ifnull(asiakas.tilino_marginaali, '') != '', asiakas.tilino_marginaali, 	if(ifnull(yhtion_toimipaikat.tilino_marginaali, '') != '', 	yhtion_toimipaikat.tilino_marginaali, 	yhtio.myynti_marginaali))))) myynti_marginaali,
			if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.tilino_osto_marginaali, '') != '', 		tuotteen_alv.tilino_osto_marginaali, 	if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(yhtion_toimipaikat.tilino_osto_marginaali, '') != '', 	yhtion_toimipaikat.tilino_osto_marginaali, 	if(ifnull(tuote.tilino_osto_marginaali, '') != '', 	tuote.tilino_osto_marginaali,	 	if(ifnull(asiakas.tilino_osto_marginaali, '') != '', 	asiakas.tilino_osto_marginaali, 	if(ifnull(yhtion_toimipaikat.tilino_osto_marginaali, '') != '', 	yhtion_toimipaikat.tilino_osto_marginaali, 	yhtio.osto_marginaali))))) osto_marginaali,
			if(tilausrivi.alv >= 500 and tilausrivi.kpl < 0 and tilausrivin_lisatiedot.osto_vai_hyvitys = 'O', 'KYLLA', 'EI') marginaaliostot,
			if(tilausrivi.alv >= 500 and tilausrivi.kpl > 0 and tilausrivin_lisatiedot.osto_vai_hyvitys = 'H', 'KYLLA', 'EI') marginaalihyvitys,
			sum(tilausrivi.rivihinta) summa,
			sum(tilausrivi.rivihinta_valuutassa) summa_valuutassa,
			sum(tilausrivi.kate) kate,
			sum(if(tuote.ei_saldoa = '', tilausrivi.rivihinta-tilausrivi.kate, 0)) varmuutos,
			GROUP_CONCAT(tilausrivi.tunnus SEPARATOR ',') tunnukset
			FROM tilausrivi
			JOIN lasku on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)
			LEFT JOIN yhtio ON (yhtio.yhtio = lasku.yhtio)
			LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
			LEFT JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
			LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio and tuotteen_alv.tuoteno = tilausrivi.tuoteno and tuotteen_alv.maa = '$lasku[maa]' and tuotteen_alv.maa != '')
			LEFT JOIN yhtion_toimipaikat ON (yhtion_toimipaikat.yhtio = tilausrivi.yhtio and yhtion_toimipaikat.tunnus = '$lasku[yhtio_toimipaikka]')
			LEFT JOIN asiakas ON (tilausrivi.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus)
			WHERE tilausrivi.uusiotunnus = '$lasku[tunnus]'
			and tilausrivi.tyyppi		 = 'L'
			and tilausrivi.yhtio		 = '$kukarow[yhtio]'
			GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12";
$xresult = mysql_query($query) or pupe_error($query);

$onkoalvia = ""; // apumuuttuja, ett‰ tiedet‰‰n onko alvillista

if (mysql_num_rows($xresult) == 0) {
	$tulos_ulos_tiliointi .= "<font class='message'>".t("VIRHE: Laskulla ei ollut yht‰‰n rivi‰").": $lasku[tunnus]</font><br><br>\r\n";
}
else {

	$varmuutos  = 0;
	$vastasumma = 0;
	$maksimisumma = 0;
	$maksimisumma_kustp = 0;
	$maksimisumma_kohde = 0;
	$maksimisumma_projekti = 0;

	// Tehdaan myynnin kirjaukset
	while ($xrow = mysql_fetch_array($xresult)) {

		$lukko 					= "";
		$tunnus 				= $lasku['tunnus'];
		$tapvm					= $lasku['tapvm'];
		$loppusumma				= $lasku['summa'];
		$loppusumma_valuutassa	= $lasku['summa_valuutassa'];
		$tilino					= 0;
		$tilino_eu				= 0;
		$tilino_ei_eu			= 0;

		// kustannuspaikkaa, kohde ja projekti
		$kustp 		= $xrow["kustp"];
		$kohde 		= $xrow["kohde"];
		$projekti 	= $xrow["projekti"];

		// otetaan suurimman myynnin tiedot
		if ($yhtiorow["kirjanpidon_tarkenteet"] == "K" and $xrow["summa"] > $maksimisumma) {
			$maksimisumma = $xrow["summa"];
			$maksimisumma_kustp = $kustp;
			$maksimisumma_kohde = $kohde;
			$maksimisumma_projekti = $projekti;		
		}

		// Valitaan myyntitili, jos rivill‰ on alv yli 500 niin kyseess‰ on marginaalimyynti‰
		if ($xrow["alv"] >= 500 and $xrow["marginaaliostot"] == "EI" and $xrow["marginaalihyvitys"] == "EI") {
			// Valitaan myyntitili, t‰ss‰ on marginaalimyynti‰
			$selite = "Marginaaliverolasku/Myynti ". $lasku['nimi']." ".$lasku['nimitark'];

			// t‰ss‰ on kaikki marginaali-myyntitilausrivit
			$query = "	SELECT *
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus IN ($xrow[tunnukset])";
			$marginaali_myyntirivitres = mysql_query($query) or pupe_error($query);

			// lasketaan kaikkien marginaalituotteiden ostohinnnat yhteen
			$marginaali_ostohinta = 0;
			while ($marginaali_myyntirivi = mysql_fetch_array($marginaali_myyntirivitres)) {
				$marginaali_ostohinta += round(sarjanumeron_ostohinta("myyntirivitunnus", $marginaali_myyntirivi["tunnus"], "EIKULULASKUJA") * $marginaali_myyntirivi["kpl"], 6);
			}

			// lasketaan alv-laskennallisesti marginaalituotteille oikea "kate"
			$marginaali_kate = $xrow['summa'] - $marginaali_ostohinta;

			//Tilioidaan veroton osuus marginaalimyynnist‰
			$tiliointisumma = $xrow['summa'] - $marginaali_kate;

			//T‰h‰n ei osata parempaa valuuttasummaa keksi‰, pit‰‰ k‰‰nt‰‰ kurssilla
			$tiliointisumma_valuutassa = round($tiliointisumma / $lasku['vienti_kurssi'], 2);

			//Tiliˆid‰‰n margiinaalimyyntitapahtuma
			$query = "	INSERT into tiliointi set
						yhtio 				= '$kukarow[yhtio]',
						ltunnus 			= '$tunnus',
						tilino 				= '$xrow[myynti_marginaali]',
						kustp 				= '$xrow[kustp]',
						kohde 				= '$xrow[kohde]',
						projekti			= '$xrow[projekti]',
						tapvm 				= '$tapvm',
						summa				= $tiliointisumma * -1,
						summa_valuutassa 	= $tiliointisumma_valuutassa * -1,
						valkoodi			= '$lasku[valkoodi]',
						vero 				= 0,
						selite 				= '$selite',
						lukko 				= '1',
						laatija 			= '$kukarow[kuka]',
						laadittu 			= now()";
			$laskutusres = mysql_query($query) or pupe_error($query);

			$vastasumma += $tiliointisumma;
			$vastasumma_valuutassa += $tiliointisumma_valuutassa;

			//Sitten pit‰‰ viel‰ tilioid‰ itse marginaali joka on normaalia verollista myynti‰
			$selite 		= "Marginaaliverolasku/Marginaali ". $lasku['nimi']." ".$lasku['nimitark'];

			$tilino 		= (int) $xrow['tilino'];
			$tilino_eu 		= (int) $xrow['tilino_eu'];
			$tilino_ei_eu 	= (int) $xrow['tilino_ei_eu'];

			$tiliointisumma = $marginaali_kate;
			$tiliointisumma_valuutassa = round($marginaali_kate / $lasku['vienti_kurssi'], 2); //Myynnin marginaali valuutassa (paras arvaus on k‰‰nt‰‰ kurssilla)

			$lukko			= "1";
			$xrow['alv'] 	= $xrow['alv'] - 500; //Poistetaan 500 yksikkˆ‰ alvista

			$alv 			= round($tiliointisumma - ($tiliointisumma / (1 + $xrow['alv'] / 100)), 2);
			$alv_valuutassa = round($tiliointisumma_valuutassa - ($tiliointisumma_valuutassa / (1 + $xrow['alv'] / 100)), 2);

			$tiliointisumma -= $alv;
			$tiliointisumma_valuutassa -= $alv_valuutassa;
		}
		elseif ($xrow["alv"] >= 500 and ($xrow["marginaaliostot"] == "KYLLA" or $xrow["marginaalihyvitys"] == "KYLLA")) {
			// Valitaan ostotili, t‰ss‰ on marginaaliostoa
			$selite 		= "Marginaaliverolasku/Osto ". $lasku['nimi']." ".$lasku['nimitark'];

			$tilino 		= (int) $xrow['osto_marginaali'];
			$tilino_eu 		= (int) $xrow['osto_marginaali'];
			$tilino_ei_eu	= (int) $xrow['osto_marginaali'];

			$tiliointisumma 			= $xrow['summa'];
			$tiliointisumma_valuutassa	= $xrow['summa_valuutassa'];
			$xrow["varmuutos"] 			= $xrow["summa"];
			$xrow["alv"] 				= 0;
			$alv 		 				= 0;
			$alv_valuutassa				= 0;
		}
		else {
			// Valitaan myyntitili, t‰ss‰ on normaalimyynti‰
			$selite 		= "Lasku ". $lasku['nimi']." ".$lasku['nimitark'];

			$tilino 		= (int) $xrow['tilino'];
			$tilino_eu 		= (int) $xrow['tilino_eu'];
			$tilino_ei_eu 	= (int) $xrow['tilino_ei_eu'];

			$tiliointisumma = $xrow['summa'];
			$tiliointisumma_valuutassa = $xrow['summa_valuutassa'];

			$alv 			= round($xrow['alv'] / 100 * $tiliointisumma, 2);
			$alv_valuutassa	= round($xrow['alv'] / 100 * $tiliointisumma_valuutassa, 2);
		}

		// Valitaan myyntitili vienin mukaan
		if ($lasku['vienti'] == 'E') $tilino = $tilino_eu;
		if ($lasku['vienti'] == 'K') $tilino = $tilino_ei_eu;

		//Tiliˆid‰‰n myynti
		if ($tiliointisumma != 0) {

			$tiliointisumma = round($tiliointisumma, 2);
			$tiliointisumma_valuutassa = round($tiliointisumma_valuutassa, 2);

			$query = "	INSERT into tiliointi set
						yhtio 				= '$kukarow[yhtio]',
						ltunnus				= '$tunnus',
						tilino				= '$tilino',
						kustp 				= '$kustp',
						kohde 				= '$kohde',
						projekti			= '$projekti',
						tapvm 				= '$tapvm',
						summa 				= $tiliointisumma * -1,
						summa_valuutassa 	= $tiliointisumma_valuutassa * -1,
						valkoodi			= '$lasku[valkoodi]',
						vero 				= '$xrow[alv]',
						selite 				= '$selite',
						lukko 				= '$lukko',
						laatija 			= '$kukarow[kuka]',
						laadittu 			= now()";
			$laskutusres = mysql_query($query) or pupe_error($query);
			$isa = mysql_insert_id ($link);

			$lukko = "";

			//Tiliˆidaan ALV, jos sit‰ on
			if ($xrow['alv'] > 0) {

				$onkoalvia = "kylla"; // apumuuttuja, ett‰ tiedet‰‰n alempana, ett‰ tehtiin alvikirjauksia

				$query = "	INSERT into tiliointi set
							yhtio 				= '$kukarow[yhtio]',
							ltunnus 			= '$tunnus',
							tilino 				= '$xrow[alvtilino]',
							kustp 				= 0,
							kohde 				= 0,
							projekti 			= 0,
							tapvm 				= '$tapvm',
							summa 				= $alv * -1,
							summa_valuutassa 	= $alv_valuutassa * -1,
							valkoodi			= '$lasku[valkoodi]',
							vero 				= 0,
							selite 				= '$selite',
							lukko 				= '1',
							laatija 			= '$kukarow[kuka]',
							laadittu 			= now(),
							aputunnus			= '$isa'";
				$laskutusres = mysql_query($query) or pupe_error($query);
			}
		}

		//Lasketaan vastasummaa jotta voidaan tutkia pyˆristyseroa
		$vastasumma += $tiliointisumma + $alv;
		$vastasumma_valuutassa += $tiliointisumma_valuutassa + $alv_valuutassa;

		//Lasketaan varastonmuutos
		$varmuutos = $xrow["varmuutos"];
		$varmuutos_valuutassa = round($xrow["varmuutos"] / $lasku['vienti_kurssi'], 2); // ei osata paremmin, pit‰‰ k‰‰nt‰‰ kurssilla

		// Kirjataan varastonmuutos, jos sita on
		if ($varmuutos != 0) {

			if($xrow["marginaaliostot"] == "KYLLA" or $xrow["marginaalihyvitys"] == "KYLLA") {
				$selite		= "Varastoontulo ". $lasku['nimi'];
			}
			else {
				$selite		= "Varastostamyynti ". $lasku['nimi'];
			}

			$vero			= 0;
			$tiliper		= 'varasto';
			$tilian			= 'varastonmuutos';
			$tiliointisumma	= $varmuutos * -1;
			$tiliointisumma_valuutassa = $varmuutos_valuutassa * -1;

			//Kirjataan 'varasto'-tilille
			$query = "	INSERT into tiliointi set
						yhtio 				= '$kukarow[yhtio]',
						ltunnus 			= '$tunnus',
						tilino 				= '$yhtiorow[$tiliper]',
						kustp 				= '$xrow[kustp]',
						kohde 				= '$xrow[kohde]',
						projekti			= '$xrow[projekti]',
						tapvm 				= '$tapvm',
						summa				= '$tiliointisumma',
						summa_valuutassa 	= '$tiliointisumma_valuutassa',
						valkoodi			= '$lasku[valkoodi]',
						vero 				= '$vero',
						selite 				= '$selite',
						lukko 				= '',
						laatija 			= '$kukarow[kuka]',
						laadittu 			= now()";
			$laskutusres = mysql_query($query) or pupe_error($query);

			$tiliointisumma = $tiliointisumma * -1;
			$tiliointisumma_valuutassa = $tiliointisumma_valuutassa * -1;

			//Kirjataan 'varastonmuutos'-tilille
			$query = "INSERT into tiliointi set
						yhtio 				= '$kukarow[yhtio]',
						ltunnus 			= '$tunnus',
						tilino 				= '$yhtiorow[$tilian]',
						kustp 				= '$xrow[kustp]',
						kohde 				= '$xrow[kohde]',
						projekti 			= '$xrow[projekti]',
						tapvm 				= '$tapvm',
						summa 				= '$tiliointisumma',
						summa_valuutassa 	= '$tiliointisumma_valuutassa',
						valkoodi			= '$lasku[valkoodi]',
						vero 				= '$vero',
						selite 				= '$selite',
						lukko 				= '',
						laatija 			= '$kukarow[kuka]',
						laadittu 			= now()";
			$laskutusres = mysql_query($query) or pupe_error($query);
		}
	}

	// Kirjataan myyntisaamiset tai kassa
	if ($mehtorow['kateinen'] != '') {

		$query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]' and tunnus = '$lasku[kassalipas]'";

		$kateisresult = mysql_query($query) or pupe_error($query);
		$kateisrow = mysql_fetch_array($kateisresult);

		$myysaatili = "";

		$myysaa_kateinen = array();

		if (mysql_num_rows($kateisresult) == 1) {
			$myysaa_kateinen['kateinen'] 		= $kateisrow['kassa'];
			$myysaa_kateinen['pankkikortti'] 	= $kateisrow['pankkikortti'];
			$myysaa_kateinen['luottokortti'] 	= $kateisrow['luottokortti'];
		}
		else {
			$myysaa_kateinen['kateinen'] 		= $yhtiorow['kassa'];
			$myysaa_kateinen['pankkikortti'] 	= $yhtiorow['pankkikortti'];
			$myysaa_kateinen['luottokortti'] 	= $yhtiorow['luottokortti'];
		}

		if ($mehtorow['kateinen'] == "n") {
			if ($kateisrow["pankkikortti"] != "") {
				$myysaatili = $kateisrow["pankkikortti"];
			}
			else {
				$myysaatili = $yhtiorow['pankkikortti'];
			}
		}

		if ($mehtorow['kateinen'] == "o") {
			if ($kateisrow["luottokortti"] != "") {
				$myysaatili = $kateisrow["luottokortti"];
			}
			else {
				$myysaatili = $yhtiorow['luottokortti'];
			}
		}

		if ($myysaatili == "") {
			if ($kateisrow["kassa"] != "") {
				$myysaatili = $kateisrow["kassa"];
			}
			else {
				$myysaatili = $yhtiorow['kassa'];
			}
		}

		$apusumma = $loppusumma;
		$apusumma_valuutassa = $loppusumma_valuutassa;

		// jos maksetaan k‰teisell‰ ja meill‰ on kassa-alennusta, tehd‰‰n kirjaus... vain jos alvikirjauksia EI OLE tehty!
		if ($lasku['kasumma'] != '' and $onkoalvia == "") {
			$selite = "Lasku ". $lasku['nimi']." ".$lasku['nimitark'];
			$query  = "	INSERT into tiliointi set
		 		   		yhtio 				= '$kukarow[yhtio]',
		 		   		ltunnus 			= '$tunnus',
		 		   		tilino 				= '$yhtiorow[myynninkassaale]',
		 		   		kustp 				= 0,
		 		   		kohde 				= 0,
		 		   		projekti 			= 0,
		 		   		tapvm 				= '$tapvm',
		 		   		summa 				= '$lasku[kasumma]',
						summa_valuutassa 	= '$lasku[kasumma_valuutassa]',
						valkoodi			= '$lasku[valkoodi]',
		 		   		vero 				= 0,
		 		   		selite 				= '$selite',
		 		   		lukko 				= '1',
		 		   		laatija 			= '$kukarow[kuka]',
		 		   		laadittu 			= now()";
			$laskutusres = mysql_query($query) or pupe_error($query);

			// v‰hennet‰‰n loppusummasta kassa-ale
			$apusumma 				= $loppusumma - $lasku['kasumma'];
			$apusumma_valuutassa	= $loppusumma_valuutassa - $lasku['kasumma_valuutassa'];
		}
	}
	elseif ($mehtorow["factoring"] != "") {
		$myysaatili				= $yhtiorow['factoringsaamiset'];
		$apusumma				= $loppusumma;
		$apusumma_valuutassa	= $loppusumma_valuutassa;
	}
	elseif ($konsrow["konserniyhtio"] != "") {
		$myysaatili				= $yhtiorow['konsernimyyntisaamiset'];
		$apusumma				= $loppusumma;
		$apusumma_valuutassa	= $loppusumma_valuutassa;
	}
	else {
		$myysaatili				= $yhtiorow['myyntisaamiset'];
		$apusumma				= $loppusumma;
		$apusumma_valuutassa	= $loppusumma_valuutassa;
	}

	$selite = "Lasku ". $lasku['nimi']." ".$lasku['nimitark'];

	if (isset($kateismaksu)) {

		$loppusumma = 0;
		$loppusumma_valuutassa = 0;

		foreach ($kateismaksu as $tili => $arvo) {

			$arvo = str_replace(",",".",$arvo);

			if ((float) $arvo != 0) {

				// jos meill‰ on valuuttalasku n‰m‰ arvot ovat valuutassa
				if ($lasku["valkoodi"] != $yhtiorow["valkoodi"]) {
					$arvo_valuutassa = $arvo;
					$arvo = round($arvo * $lasku['vienti_kurssi'], 2);
				}
				else {
					$arvo_valuutassa = round($arvo / $lasku["vienti_kurssi"], 2);
				}

				$query = "	INSERT into tiliointi set
							yhtio 				= '$kukarow[yhtio]',
							ltunnus 			= '$tunnus',
							tilino 				= '$myysaa_kateinen[$tili]',
							kustp 				= 0,
							kohde 				= 0,
							projekti 			= 0,
							tapvm 				= '$tapvm',
							summa 				= '$arvo',
							summa_valuutassa 	= '$arvo_valuutassa',
							valkoodi			= '$lasku[valkoodi]',
							vero 				= 0,
							selite 				= '$selite',
							lukko 				= '1',
							laatija 			= '$kukarow[kuka]',
							laadittu 			= now()";
				$laskutusres = mysql_query($query) or pupe_error($query);

				$loppusumma += $arvo;
				$loppusumma_valuutassa += $arvo_valuutassa;
			}
		}

	}
	else {
		$query = "	INSERT into tiliointi set
					yhtio 				= '$kukarow[yhtio]',
					ltunnus 			= '$tunnus',
					tilino 				= '$myysaatili',
					kustp 				= $maksimisumma_kustp,
					kohde 				= $maksimisumma_kohde,
					projekti 			= $maksimisumma_projekti,
					tapvm 				= '$tapvm',
					summa 				= '$apusumma',
					summa_valuutassa 	= '$apusumma_valuutassa',
					valkoodi			= '$lasku[valkoodi]',
					vero 				= 0,
					selite 				= '$selite',
					lukko 				= '1',
					laatija 			= '$kukarow[kuka]',
					laadittu 			= now()";
		$laskutusres = mysql_query($query) or pupe_error($query);
	}

	// Kirjataan pyoristykset, jos niita on
	$vastasumma = round($vastasumma,2);
	$vastasumma_valuutassa = round($vastasumma_valuutassa,2);

	if ($vastasumma != $loppusumma or ($lasku['valkoodi'] != $yhtiorow['valkoodi'] and $vastasumma_valuutassa != $loppusumma_valuutassa)) {
		$query = "	INSERT into tiliointi set
					yhtio 				= '$kukarow[yhtio]',
					ltunnus 			= '$tunnus',
					tilino 				= '$yhtiorow[pyoristys]',
					kustp 				= 0,
					kohde 				= 0,
					projekti 			= 0,
					tapvm 				= '$tapvm',
					summa 				= $vastasumma - $loppusumma,
					summa_valuutassa 	= $vastasumma_valuutassa - $loppusumma_valuutassa,
					valkoodi			= '$lasku[valkoodi]',
					vero 				= 0,
					selite 				= '$selite',
					lukko 				= '',
					laatija 			= '$kukarow[kuka]',
					laadittu 			= now()";
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
