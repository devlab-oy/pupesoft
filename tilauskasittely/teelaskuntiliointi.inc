<?php

// tarvitaan arrayt $kukarow, $yhtiorow, $mehtorow ja $lasku

$tulos_ulos_tiliointi = "";

//Tehtaan tiliointiaineisto suoraan sql:lla
$query = "	SELECT tilausrivi.alv, 
			tuote.tilino, tuote.tilino_eu, tuote.tilino_ei_eu, 
			tuote.kustp, tuote.kohde, tuote.projekti,
			sum(tilausrivi.rivihinta) summa, 
			sum(tilausrivi.kate) kate, 
			sum(if(tuote.ei_saldoa='', tilausrivi.rivihinta-tilausrivi.kate,0)) varmuutos,
			GROUP_CONCAT(tilausrivi.tunnus SEPARATOR ',') tunnukset,
			if(tilausrivi.alv >= 500 and tilausrivi.kpl < 0, 'KYLLA', 'EI') marginaaliostot
			FROM tilausrivi
			LEFT JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
			WHERE tilausrivi.uusiotunnus='$lasku[tunnus]' 
			and tilausrivi.yhtio='$kukarow[yhtio]'
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
	while ($xrow = mysql_fetch_array ($xresult)) {
				
		$tunnus 	= $lasku['tunnus'];
		$tapvm		= $lasku['tapvm'];
		$loppusumma	= $lasku['summa'];
				
		// Valitaan myyntitili, jos rivill‰ on alv yli 500 niin kyseess‰ on marginaalimyynti‰
		if ($xrow["alv"] >= 500 and $xrow["marginaaliostot"] == "EI") {
			// Valitaan myyntitili, t‰ss‰ on marginaalimyynti‰
			
			$selite 	= "Marginaaliverolasku/Myynti ". $lasku['nimi']." ".$lasku['nimitark'];
			
			$tilino 		= (int) $yhtiorow['myynti_marginaali'];
			$tilino_eu 		= (int) $yhtiorow['myynti_marginaali'];
			$tilino_ei_eu	= (int) $yhtiorow['myynti_marginaali'];
			
			$query = "	SELECT GROUP_CONCAT(ostorivitunnus SEPARATOR ',') ostotunnukset, GROUP_CONCAT(sarjanumero SEPARATOR ', ') sarjanumerot 
						from sarjanumeroseuranta 
						where yhtio = '$kukarow[yhtio]' 
						and myyntirivitunnus in ($xrow[tunnukset])";
			$sarjares1 = mysql_query($query) or pupe_error($query);
			$sarjarow1 = mysql_fetch_array($sarjares1);
						
			$query = "	SELECT round(sum(hinta*(1-ale/100)),2) ostosumma
						FROM tilausrivi
						WHERE yhtio='$kukarow[yhtio]'
						and tunnus in ($sarjarow1[ostotunnukset])";
			$sarjares2 = mysql_query($query) or pupe_error($query);
			$sarjarow2 = mysql_fetch_array($sarjares2);
									
			//Myyntimarginaali
			$marginaali = $xrow['summa'] - $sarjarow2["ostosumma"];
			
			//Tilioidaan veroton osuus marginaalimyynnist‰
			$tiliointisumma = $ostosumma = $sarjarow2["ostosumma"];
									
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
						lukko 		= '',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now()";
			$laskutusres = mysql_query($query) or pupe_error($query);
						
			//Sitten pit‰‰ viel‰ tilioid‰ itse marginaali joka on normaalia verollista myynti‰
			$selite 	= "Marginaaliverolasku/Marginaali ". $lasku['nimi']." ".$lasku['nimitark'];
			$tiliointisumma = $marginaali;
			
			//Poistetaan 500 yksikkˆ‰ alvista
			$xrow['alv'] = $xrow['alv'] - 500;
			
			$alv = round($xrow['alv'] / 100 * $tiliointisumma,2);
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
			
			$tilino 		= (int) $xrow['tilino'];
			$tilino_eu 		= (int) $xrow['tilino_eu'];
			$tilino_ei_eu	= (int) $xrow['tilino_ei_eu'];
			
			$tiliointisumma = $xrow['summa'];			
			$alv = round($xrow['alv'] / 100 * $tiliointisumma,2);			
		}
		
		// Tehdaan tilioinnit!
		if ($tilino == 0) {
			$tilino = $yhtiorow['myynti'];
		}
		if ($tilino_eu == 0) {
			$tilino_eu = $yhtiorow['myynti_eu'];
		}
		if ($tilino_ei_eu == 0) {
			$tilino_ei_eu = $yhtiorow['myynti_ei_eu'];
		}

		if ($lasku['vienti'] == 'E') $tilino = $tilino_eu;
		if ($lasku['vienti'] == 'K') $tilino = $tilino_ei_eu;


		//Tiliˆid‰‰n myynti
		$query = "	INSERT into tiliointi set
					yhtio 		='$kukarow[yhtio]',
					ltunnus		= '$tunnus',
					tilino		= '$tilino',
					kustp 		= '$xrow[kustp]',
					kohde 		= '$xrow[kohde]',
					projekti	= '$xrow[projekti]',
					tapvm 		= '$tapvm',
					summa 		= $tiliointisumma * -1,
					vero 		= '$xrow[alv]',
					selite 		= '$selite',
					lukko 		= '',
					laatija 	= '$kukarow[kuka]',
					laadittu 	= now()";
		$laskutusres = mysql_query($query) or pupe_error($query);

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
	elseif($mehtorow["factoring"] != "") {
		$myysaatili = $yhtiorow['factoringsaamiset'];
		$apusumma = $loppusumma;
	}
	elseif($konsrow["konserniyhtio"] != "") {
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