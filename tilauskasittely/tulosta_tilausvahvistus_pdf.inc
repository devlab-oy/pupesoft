<?php

	//hatetaan asiakkaan lähetetyyppi
	$query = "  SELECT lahetetyyppi, luokka, puhelin, if(asiakasnro!='', asiakasnro, ytunnus) asiakasnro
				FROM asiakas
				WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$asrow = mysql_fetch_array($result);

	if ($naytatvale == 2) {
		$lahetetyyppi = "tulosta_lahete_eialeja.inc";
	}
	else {
		$lahetetyyppi = "tulosta_lahete.inc";
	}

	require_once ("tulosta_lahete.inc");

	// katotaan miten halutaan sortattavan
	$sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);

	if($laskurow["tila"] == "L" or $laskurow["tila"] == "N") {
		$tyyppilisa = " and tilausrivi.tyyppi in ('L') ";
	}
	else {
		$tyyppilisa = " and tilausrivi.tyyppi in ('L','G','W') ";
	}

	//generoidaan lähetteelle ja keräyslistalle rivinumerot
	$query = "  SELECT tilausrivi.*,
				round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),2) ovhhinta,
				round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),2) rivihinta,
				$sorttauskentta,
				if(tilausrivi.var='J', 1, 0) jtsort
				FROM tilausrivi
				JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
				JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
				WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
				and tilausrivi.yhtio = '$kukarow[yhtio]'
				$tyyppilisa
				ORDER BY jtsort, sorttauskentta $yhtiorow[lahetteen_jarjestys_suunta], tilausrivi.tunnus";
	$riresult = mysql_query($query) or pupe_error($query);
			
	//generoidaan rivinumerot
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

	unset($pdf);
	unset($page);

	$sivu  = 1;
	$total = 0;

	// Aloitellaan lähetteen teko
	$page[$sivu] = alku("TILAUSVAHVISTUS");


	while ($row = mysql_fetch_array($riresult)) {
		rivi($page[$sivu], "TILAUSVAHVISTUS");
		$total+= $row["rivihinta"];			
	}

	loppu($page[$sivu], 1);

	//tulostetaan sivu		
	print_pdf($komento["Tilausvahvistus"], "TILAUSVAHVISTUS");
	
?>