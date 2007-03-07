<?php

///* Sis‰‰n *///
// $alv                			--> K‰ytt‰j‰n syˆtt‰m‰ ALV
// $hinta						--> alehinta.inc:in laskema hinta
// $trow[alv]           		--> Tuotteen ALV
// $laskurow[vienti]   		 	--> Laskun tyyppi (kotimaa '', vientieu 'E' , vienti eieu 'K')
// $laskurow[ytunnus]   		--> Laskunsaajan y-tunnus
// $laskurow[tila]      		--> Laskun tila O=osto, muut tilat on myynti‰
// $laskurow[alv]       		--> Laskun otsikolla oleva alv

///* Ulos *///
// $alv                			--> Uusi lakettu ALV
// $hinta           			--> Uusi lakettu kappalehinta
		
//yhtiˆn oletusalvi!		
$wquery = "select selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
$wtres  = mysql_query($wquery) or pupe_error($wquery);
$wtrow  = mysql_fetch_array($wtres);
		
		
if ($laskurow["tila"] == "O") {
	//Jos k‰ytt‰j‰ on valinnut drop-downista jonkun nollasta poikkeavan alvin, niin lasketaan sen verran alvia pois hinnasta
	// Oletuksena ostohinnat ovat t‰ll‰ hetkell‰ ilman alvia
	if ($alv != 0) {
		$hinta = round($hinta / (1+$alv/100),2);
	}

	$alv = 0; //ostotilaus --> ei alvia riveille
}
elseif ($alv >= 500) {
	//T‰ss‰ keississ‰ on marginaalimyynti‰
	if ($laskurow["vienti"] == ""  and $laskurow["alv"] == 0) $alv = 500;											// Otsikolla on valittu veroton myynti, esim Ahvenanmaan mynti‰	
	if ($laskurow["vienti"] == ""  and $laskurow["alv"] == $wtrow["selite"]) $alv = $trow["alv"]+500;				// Otsikolla on valittu verollinen kotimaan myynti
	if ($laskurow["vienti"] == "E" and $laskurow["ytunnus"] != "") $alv = 500; 										// EU-vienti ytunnuksella --> alviton
	if ($laskurow["vienti"] == "K") $alv = 500; 																	// Vienti eu:n ulkopuolelle on aina verotonta
	
	$alv = $alv * 1;
}
elseif ($yhtiorow["alv_kasittely"] != "") {
	//T‰ss‰ keississ‰ kaikki hinnat ovat aina verottomia ja vero lis‰t‰‰n vasta laskutuksessa
	if ($laskurow["vienti"] == ""  and $laskurow["alv"] == 0) $alv = 0;												// Otsikolla on valittu veroton myynti, esim Ahvenanmaan mynti‰	
	if ($laskurow["vienti"] == ""  and $laskurow["alv"] == $wtrow["selite"] and $alv == "") $alv = $trow["alv"];	// Otsikolla on valittu verollinen kotimaan myynti
	if ($laskurow["vienti"] == "E" and $laskurow["ytunnus"] != "") $alv = 0; 										// EU-vienti ytunnuksella --> alviton
	if ($laskurow["vienti"] == "K") $alv = 0; 																		// Vienti eu:n ulkopuolelle on aina verotonta
	
	$alv = $alv * 1;
}
else {
	//T‰ss‰ keississ‰ kaikki hinnat sis‰lt‰v‰t arvonlis‰veron
	if ($laskurow["vienti"] == ""  and $laskurow["alv"] == 0) $alv = 0;												// Otsikolla on valittu veroton myynti, esim Ahvenanmaan mynti‰
	if ($laskurow["vienti"] == ""  and $laskurow["alv"] == $wtrow["selite"] and $alv == "") $alv = $trow["alv"];	// Otsikolla on valittu verollinen kotimaan myynti
	if ($laskurow["vienti"] == "E" and $laskurow["ytunnus"] != "") $alv = 0; 										// EU-vienti ytunnuksella --> alviton
	if ($laskurow["vienti"] == "K") $alv = 0; 																		// Vienti eu:n ulkopuolelle on aina verotonta
	
	$alv = $alv * 1;
	$trow['alv'] = $trow['alv'] * 1;
		
	// Jos alvit t‰sm‰‰v‰t, ei tarvitse tehd‰ mit‰‰n. Muuten lasketaan uuden alvin sis‰lt‰v‰ myyntihinta.
	if ($alv != $trow['alv']) {		
		// Huomioidaan hinnaston alviprossa
		if (trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$vquery = "	select selitetark_3 
						from avainsana 
						where yhtio			= '$kukarow[yhtio]' 
						and laji			= 'alvulk' 
						and selite 			= '$trow[alv]' 
						and selitetark		= '$laskurow[maa]' 
						and selitetark_2	= '$laskurow[valkoodi]'";
			$vtres  = mysql_query($vquery) or pupe_error($vquery);

			if (mysql_num_rows($vtres) == 1) {
				$vtrow  = mysql_fetch_array($vtres);
				
				$tuotealvi = $vtrow['selitetark_3'];
			}
			else {
				$tuotealvi = $trow['alv'];
			}
		}
		else {
			$tuotealvi = $trow['alv'];
		}
		
		$hinta = round($hinta / (1+$tuotealvi/100) * (1+$alv/100),2);
	}
}

//echo "$trow[tuoteno], vienti '$laskurow[vienti]', otsikon alv '$laskurow[alv]', annettava alv '$alv', tuotteen alv '$trow[alv]', oikea kappalehinta '$hinta'<br>";

?>