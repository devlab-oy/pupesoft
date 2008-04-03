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

		if(trim($asrow["valkoodi"]) != '') {
			$query = "	SELECT nimi, kurssi, tunnus
						FROM valuu
						WHERE yhtio = '$kukarow[yhtio]'
						and nimi = '$yhtiorow[valkoodi]'
						and kurssi > 0
						LIMIT 1";
			$vres = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($vres) == 1) {
				$vrow = mysql_fetch_array($vres);
				
				$valkoodi	= $vrow["nimi"];
				$kurssi 	= $vrow["kurssi"];
			}
			else {
				$valkoodi	= $yhtiorow["valkoodi"];
				$kurssi		= 1;
			}
		}
		else {
			$valkoodi	= $yhtiorow["valkoodi"];
			$kurssi		= 1;
		}


		$query = "	INSERT into lasku
					SET
					yhtio_nimi		= '$yhtiorow[nimi]',
					yhtio_osoite	= '$yhtiorow[osoite]',
					yhtio_postino	= '$yhtiorow[postino]',
					yhtio_postitp	= '$yhtiorow[postitp]',
					yhtio_maa		= '$yhtiorow[maa]',
					yhtio_ovttunnus	= '$yhtiorow[ovttunnus]',
					yhtio_kotipaikka= '$yhtiorow[kotipaikka]',
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
					ketjutus 		= 'o',
					sisainen 		= 'o',
					eilahetetta 	= 'o',
					valkoodi 		= '$valkoodi',
					vienti_kurssi	= '$kurssi',
					viikorkopros 	= '0',
					laatija 		= '$kukarow[kuka]',
					liitostunnus    = '$asrow[tunnus]',
					piiri			= '$asrow[piiri]',
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

		$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);
		
		if ($yhtiorow["kasittelykulu_tuotenumero"] != '') {

			$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[kasittelykulu_tuotenumero]'";
			$result = mysql_query($query) or pupe_error($query);
			$trow = mysql_fetch_array($result);

			$kasittelykulu = laskuval($kasittelykulu, $laskurow["vienti_kurssi"]);

			$ytunnus         = $laskurow["ytunnus"];
			$kpl             = 1;
			$tuoteno         = $yhtiorow["kasittelykulu_tuotenumero"];
			$toimaika 	     = $laskurow["toimaika"];
			$kerayspvm	     = $laskurow["kerayspvm"];
			$hinta 		     = $kasittelykulu;
			$netto 		     = "N";
			$ale 		     = "";
			$var		     = "H";
			$alv		     = "";
			$paikka		     = "";
			$varasto 	     = "";
			$rivitunnus		 = "";
			$korvaavakielto	 = "";
			$varataan_saldoa = "";

			require ("tilauskasittely/lisaarivi.inc");
		}
				
		$valittu_tulostin = $kukarow["kirjoitin"];
		
		require ("../tilauskasittely/tilaus-valmis.inc");
	}
	else {
		echo "<br><br><font class='error'>Korko-tuotetta ei löydy! Ole hyvä ja perusta tuote tuotenumerolla 'korko'.</font>";
		exit;
	}
?>
