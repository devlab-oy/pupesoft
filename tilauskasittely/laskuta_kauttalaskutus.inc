<?php

	// jos ollaan saavuttamassa tuotteita varastoon, jota on ollu JT-SUPER jälkkärissä tehdään niistä automaattisesti tilaus joka laskutetaan
	// eli täppä "suoraan laskutukseen" päälle tilaukselle...
	// tarvitaan keikan tunnus muuttujassa $otunnus
	// jos $mista ei ole tyhjä tullaan käyttöliittymästä, jolloin aina kutsutaan tätä failia eikä haluta pilata käyttäjän päivää echoamalla että epäonnistui

	$kaikkimeniok = "OK";

	require_once("tilauskasittely/luo_myyntitilausotsikko.inc");

	$asiytunnus1 = $GLOBALS["toim_asiakkaantiedot"]["ytunnus"];
	$asiytunnus2 = preg_replace("/[^0-9]/", "", $GLOBALS["toim_asiakkaantiedot"]["ytunnus"]);
	$asiytunnus3 = (int) $asiytunnus2;
	$asiytunnus4 = str_replace("-", "", $GLOBALS["toim_asiakkaantiedot"]["ytunnus"]);

	// KAUTTALASKUTUSKIKKARE
	$asquery = "SELECT tunnus
				FROM asiakas
				WHERE yhtio = '$kukarow[yhtio]'
				AND laji != 'P'
				AND ytunnus in ('$asiytunnus1','$asiytunnus2', '$asiytunnus3','$asiytunnus4','$osrow[maa]$asiytunnus1','$osrow[maa]$asiytunnus2', '$osrow[maa]$asiytunnus3','$osrow[maa]$asiytunnus4')
				AND toim_ovttunnus  = '{$GLOBALS["toim_asiakkaantiedot"]["toim_ovttunnus"]}'";
	$result = mysql_query($asquery) or pupe_error($asquery);
	$asirow = mysql_fetch_assoc($result);

	$tilausnumero = 0;
	$kukarow["kesken"] = 0;
	$kukarow["kuka"] = "verkkolas";

	if ((int) $asirow["tunnus"] > 0) {
		ob_start();
		$tilausnumero = luo_myyntitilausotsikko("RIVISYOTTO", $asirow["tunnus"]);
		ob_end_clean();
	}

	// Jos luo_myyntitilausotsikko feilaa niin tehdään käsin uusi myyntiotsikko
	if ((int) $tilausnumero == 0) {

		$kaikkimeniok = "";

		$query = "	INSERT into lasku set
					yhtio_nimi			= '$yhtiorow[nimi]',
					yhtio_osoite		= '$yhtiorow[osoite]',
					yhtio_postino		= '$yhtiorow[postino]',
					yhtio_postitp		= '$yhtiorow[postitp]',
					yhtio_maa			= '$yhtiorow[maa]',
					yhtio_ovttunnus		= '$yhtiorow[ovttunnus]',
					yhtio_kotipaikka	= '$yhtiorow[kotipaikka]',
					yhtio_toimipaikka	= '',
					alv_tili			= '$yhtiorow[alv]',
					yhtio      			= '$kukarow[yhtio]',
					tila       			= 'N',
					ytunnus	   			= '*',
					alv	   				= '".alv_oletus()."',
					laatija    			= 'verkkolas',
					luontiaika 			= now()";
		$result = mysql_query($query) or pupe_error($query);
		$tilausnumero = mysql_insert_id();
	}

	// Viritetään kukarow kesken
	$kukarow["kesken"] = $tilausnumero;

	// Nämä laskut menevät suoraan laskutukseen eikä niitä ketjuteta
	$query = "	UPDATE lasku SET
				eilahetetta	= 'o',
				ketjutus	= 'o',
				viesti		= '$osrow[sisviesti1]',
				rahtivapaa  = 'o',
				kohdistettu = 'K'
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus = '$tilausnumero'";
	$result = mysql_query($query) or pupe_error($query);

	//Laitetaan alkuperäinen ostolasku liitteeksi OLETETAAN ETTÄ SE ON FINVOISSIA...
	$query = "	SELECT ebid, nimi, summa, valkoodi
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus = '$tunnus'";
	$kuvalaskures = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($kuvalaskures) > 0) {

		$kuvalaskurow = mysql_fetch_assoc($kuvalaskures);

		$ebid 	= $kuvalaskurow['ebid'];
		$selite = mysql_real_escape_string("$kuvalaskurow[nimi] $kuvalaskurow[summa] $kuvalaskurow[valkoodi]");

		if ($ebid == "FINVOICEXML") {

			$filesize = strlen($GLOBALS["xmlstr"]);
			$filename = array_pop(explode("/", $GLOBALS["file"]));

			$query = "	INSERT INTO liitetiedostot SET
						yhtio    			= '$kukarow[yhtio]',
						liitos   			= 'lasku',
						liitostunnus 		= '$tilausnumero',
						data     			= '$GLOBALS[xmlstr]',
						selite   			= '$selite',
						kieli				= '$yhtiorow[kieli]',
						filename 			= '$filename',
						filesize 			= '$filesize',
						filetype 			= 'text/xml',
						image_width			= '',
						image_height		= '',
						image_bits			= '',
						image_channels		= '',
						kayttotarkoitus		= '',
						jarjestys			= '1',
						laatija				= 'verkkolas',
						luontiaika			= now()";
			$Xresult = mysql_query($query) or pupe_error($query);
		}
	}

	$query = "	SELECT *
				from lasku
				where yhtio = '$kukarow[yhtio]'
				and tunnus  = '$tilausnumero'";
	$result = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_assoc($result);

	for ($i=0; $i<count($rtuoteno); $i++) {

		$query = "	SELECT *
					FROM tuote
					where yhtio = '$kukarow[yhtio]'
					and tuoteno = '".$rtuoteno[$i]["tuoteno"]."'";
		$result = mysql_query($query) or pupe_error($query);
		$rivirow = mysql_fetch_assoc($result);

		if ($rtuoteno[$i]["saldoton"] == "") {
			// Aina oletuspaikalta
			$query = "	SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso
						FROM tuotepaikat
						WHERE yhtio	= '$kukarow[yhtio]'
	   		 			and tuoteno	= '".$rtuoteno[$i]["tuoteno"]."'
	   		 			and oletus != ''
	   		 			LIMIT 1";
			$olpares = mysql_query($query) or pupe_error($query);
			$olparow = mysql_fetch_assoc($olpares);
		}
		else {
			$olparow = array();
		}

		$lis_hinta 		= 0;
		$lis_hinta 		= 0;
		$lis_netto 		= 0;
		$lis_ale 		= array();
		$alehinta_alv 	= 0;
		$alehinta_val 	= 0;
		$lis_alv 		= 0;

		if ($rivirow["tuoteno"] != "") {
			list($lis_hinta, $lis_netto, $lis_ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $rivirow, $rtuoteno[$i]["kpl"], "", "", "");
			list($lis_hinta, $lis_alv) = alv($laskurow, $rivirow, $lis_hinta, "", $alehinta_alv);
		}

		if ((float) $lis_hinta == 0) {
			// Otetaan hintatiedot suoraan finvoicesta
			$lis_hinta		= $rtuoteno[$i]["hinta"];
			$lis_netto 		= "";

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				$lis_ale["ale{$alepostfix}"] = $rtuoteno[$i]["ale{$alepostfix}"];
			}

			$alehinta_alv 	= $rtuoteno[$i]["alv"];

			if ($yhtiorow["alv_kasittely"] == "") {
				// Myyntihinnat verollisia, lisätään vero
				$lis_hinta = round($lis_hinta * (1+$alehinta_alv/100), 2);
			}
		}

		$tilausrivin_kommentti = $rtuoteno[$i]["kommentti"];

		if (strpos(strtolower($tilausrivin_kommentti), "yhteens") !== FALSE) {
			$tilausrivin_kommentti = substr($tilausrivin_kommentti, 0, strpos(strtolower($tilausrivin_kommentti), "yhteens"));
		}

		$ale_query_insert_lisa = '';

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			$ale_query_insert_lisa .= " ale{$alepostfix} = '".$lis_ale["ale{$alepostfix}"]."', ";
		}

		$query = "	INSERT into tilausrivi set
					hyllyalue 		= '$olparow[hyllyalue]',
					hyllynro 		= '$olparow[hyllynro]',
					hyllyvali 		= '$olparow[hyllyvali]',
					hyllytaso 		= '$olparow[hyllytaso]',
					tilaajanrivinro = '".$rtuoteno[$i]["tilaajanrivinro"]."',
					laatija 		= 'verkkolas',
					laadittu 		= now(),
					yhtio 			= '$kukarow[yhtio]',
					tuoteno 		= '".$rtuoteno[$i]["tuoteno"]."',
					varattu 		= '".$rtuoteno[$i]["kpl"]."',
					yksikko 		= '".$rtuoteno[$i]["yksikko"]."',
					kpl 			= 0,
					kpl2			= 0,
					tilkpl 			= '".$rtuoteno[$i]["kpl"]."',
					jt				= 0,
					{$ale_query_insert_lisa}
					alv 			= '$lis_alv',
					netto			= '$lis_netto',
					hinta 			= '$lis_hinta',
					rivihinta		= '',
					kerayspvm 		= '',
					otunnus 		= '$tilausnumero',
					uusiotunnus 	= '',
					tyyppi 			= 'L',
					toimaika 		= now(),
					kommentti 		= '".$tilausrivin_kommentti."',
					var 			= '',
					try				= '$rivirow[try]',
					osasto			= '$rivirow[osasto]',
					perheid			= '',
					perheid2		= '',
					nimitys 		= '".$rtuoteno[$i]["nimitys"]."',
					jaksotettu		= ''";
		$result = mysql_query($query) or pupe_error($query);
	}

	// Laitetaan tilaus eteenpäin jos kaikki meni ok
	if ($kaikkimeniok == "OK") {
		require("tilauskasittely/tilaus-valmis.inc");
	}
	else {
		// Laitetaan maili, että virheitä oli, tilaus jäi kesken jne.
		mail($yhtiorow['talhal_email'], mb_encode_mimeheader("Verkkolaskun kauttalaskutus epäonnistui yhtiössä $yhtiorow[nimi]", "ISO-8859-1", "Q"), "Myyntitilaukselle: $tilausnumero ei löytynyt sopivaa asiakasta!", "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
	}
?>