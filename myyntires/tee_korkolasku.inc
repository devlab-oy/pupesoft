<?php

	//luodaan korkolasku
	
	//sisään otetaan $liitostunnus. $liitostunnus tulee paperikorkolasku.php-failista
	//ja maksuehdon tunnus $vmehto
	//ja $loppusumma jossa on maksettavan koron määrä. $loppusumma tulee paperikorkolasku.php-failista

	//Haetaan asiakkaan tiedot
	$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$liitostunnus'";
	$result = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($result) != 1) {
		printf ("<font class='error'>".t("Oho, asiakas katosi (%d)")."</font><br>",$ytunnus);
		exit;
	}
	$asrow = mysql_fetch_array($result);

	//Etsitään tämä tässä tilanteessa käytettävä maksuehto
	$apuqu = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$vmehto'";
	$meapu = mysql_query($apuqu) or pupe_error($apuqu);
	
	if (mysql_num_rows($meapu) != 1) {
		printf ("<font class='error'>".t("Oho, maksuehto katosi (%d)")."</font><br>",$vmehto);
		exit;
	}
	$merow = mysql_fetch_array($meapu);

	$crlf = array("\r","\n"); // poistetaan rivinvaihdot kommentista
	$comments = str_replace($crlf, " ", $comments);

	//Haetaan tuotteen tiedot
	$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='Korko'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {

		$tuorow = mysql_fetch_array($result);


		$query = "	INSERT into lasku
					SET
					ytunnus			= '$asrow[ytunnus]',
					nimi 			= '$asrow[nimi]',
					nimitark 		= '$asrow[nimitark]',
					osoite 			= '$asrow[osoite]',
					postino 		= '$asrow[postino]',
					postitp 		= '$asrow[postitp]',
					maa 			= '$asrow[maa]',
					toim_nimi 		= '$asrow[toim_nimi]',
					toim_nimitark 	= '$asrow[toim_nimitark]',
					toim_osoite 	= '$asrow[toim_osoite]',
					toim_postino	= '$asrow[toim_postino]',
					toim_postitp 	= '$asrow[toim_postitp]',
					toim_maa 		= '$asrow[toim_maa]',
					verkkotunnus	= '$asrow[verkkotunnus]',
					maksuehto 		= '$merow[tunnus]',
					toimitustapa 	= '',
					myyja			= '$kukarow[tunnus]',
					alv 			= '0',
					ovttunnus 		= '$asrow[ovttunnus]',
					toim_ovttunnus 	= '$asrow[toim_ovttunnus]',
					viesti 			= 'Korkolasku',
					chn				= '$asrow[chn]',
					maksuteksti 	= '$merow[teksti]',
					laskutusvkopv 	= '$asrow[laskutusvkopv]',
					yhtio 			= '$kukarow[yhtio]',
					tila 			= 'N',
					alatila 		= '',
					vienti 			= '$asrow[vienti]',
					tilaustyyppi	= 'N',
					toimaika		= now(),
					ketjutus 		= '',
					sisainen 		= '',
					eilahetetta 	= '',
					valkoodi 		= '$yhtiorow[valkoodi]',
					viikorkopros 	= '0',
					laatija 		= '$kukarow[kuka]',
					liitostunnus    = '$asrow[tunnus]',
					olmapvm			= NOW(),
					luontiaika		= NOW()";
		$result = mysql_query($query) or pupe_error($query);
		$otunnus = mysql_insert_id();

		$query = "	INSERT into tilausrivi set
					laatija 		= '$kukarow[kuka]',
					laadittu 		= now(),
					toimaika		= now(),
					yhtio 			= '$kukarow[yhtio]',
					tuoteno 		= '$tuorow[tuoteno]',
					varattu 		= '1',
					tilkpl 			= '1',
					ale 			= '0',
					netto 			= 'N',
					jt 				= 0,
					yksikko 		= '',
					try 			= '$tuorow[try]',
					osasto 			= '$tuorow[osasto]',
					kpl 			= 0,
					alv 			= '0',
					hinta 			= '$loppusumma',
					nimitys 		= '$tuorow[nimitys]',
					otunnus 		= '$otunnus',
					tyyppi 			= 'L',
					var 			= ''";
		$result = mysql_query($query) or pupe_error($query);

		$kukarow["kesken"] = $otunnus;
		$korkolasku = "kylla";

		$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		require ("../tilauskasittely/tilaus-valmis.inc");
	}
	else {
		echo "<br><br><font class='error'>Korko-tuotetta ei löydy! Ole hyvä ja perusta tuote tuotenumerolla 'korko'.</font>";
		exit;
	}
?>
