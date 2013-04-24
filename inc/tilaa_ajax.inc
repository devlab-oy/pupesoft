<?php

	$maara = (float) str_replace(",", ".", $maara);

	// Lopetetaan jos tilattava määrä on nolla
	if ($maara > 0) {

		//päivitetään kukarow[kesken] kun käyttäjä tekee uutta tilausta
		$query = "	UPDATE kuka
					SET kesken = 0
					WHERE session = '$session'";
		$result = pupe_query($query);

		$kukarow['kesken'] 	= 0;
		$tilausnumero 		= 0;

		$query	= "	SELECT *, tunnus liitostunnus
					from toimi
					where yhtio = '$kukarow[yhtio]'
					and tunnus  = '$toimittaja'";
		$result = pupe_query($query);
		$srow 	= mysql_fetch_array($result);

		// oletuksia
		$varasto 		= 0;
		$toimipiste 	= 0;

		if (isset($valittuvarasto) and $valittuvarasto > 0) {
			$varasto = $valittuvarasto;
		}

		// tarvittavat muuttujat otsikolle
		$ytunnus 		= $srow['ytunnus'];
		$ovttunnus 		= $srow["ovttunnus"];
		$nimi			= $srow["nimi"];
		$nimitark		= $srow["nimitark"];
		$osoite			= $srow["osoite"];
		$postino		= $srow["postino"];
		$postitp		= $srow["postitp"];
		$maa			= $srow["maa"];
		$liitostunnus  	= $toimittaja;
		$maksuteksti	= $srow["maksuteksti"];
		$kuljetus		= $srow["kuljetus"];
		$tnimi			= "";

		$query	= "	SELECT nimi
					from yhteyshenkilo
					where yhtio				 = '$kukarow[yhtio]'
					and tyyppi 				 = 'T'
					and tilausyhteyshenkilo != ''
					and liitostunnus		 = '$toimittaja'";
		$result = pupe_query($query);
		$yhrow 	= mysql_fetch_array($result);

		$tilausyhteyshenkilo = $yhrow['nimi'];

		$verkkotunnus	= $srow["ovttunnus"];

		$valkoodi 		= $srow["oletus_valkoodi"];

		//keräyspvm pitäisi olla -
		if ($kukarow['kesken'] == 0 and $yhtiorow['ostotilaukseen_toimittajan_toimaika'] != '2') {
			$toimpp = date("j");
			$toimkk = date("n");
			$toimvv = date("Y");
		}
		elseif ($kukarow['kesken'] == 0 and $yhtiorow['ostotilaukseen_toimittajan_toimaika'] == '2') {
			$toimittajan_toimaika = date('Y-m-d',time() + $srow["oletus_toimaika"] * 24 * 60 * 60);
			list($toimvv, $toimkk, $toimpp) = explode('-', $toimittajan_toimaika);
		}
		else {
			list($toimvv, $toimkk, $toimpp) = explode('-', $srow["toimaika"]);
			$toimpp = substr($toimpp,0,2);
		}

		//voidaan tarvita
		if ($toimvv == '') {
			$toimpp = date("j");
			$toimkk = date("n");
			$toimvv = date("Y");
		}

		$maksaja 	= $srow["toimitusehto"];
		$myyja		= $kukarow["tunnus"];
		$comments	= "";

		$jatka = "jatka";

		$query = "	SELECT max(tunnus) tunnus
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tila = 'O'
					AND alatila = ''
					AND liitostunnus = '$toimittaja'
					AND myyja = '$kukarow[tunnus]'";
		$lasres = pupe_query($query);
		$lasrow = mysql_fetch_array($lasres);

		if ($lasrow['tunnus'] == 0) {
			require("../tilauskasittely/otsik_ostotilaus.inc");
			$rivi = 1;
		}
		else {
			$tilausnumero = $lasrow['tunnus'];

			$query = "	SELECT max(tilaajanrivinro) tilaajanrivinro
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						AND tyyppi = 'O'
						AND otunnus = '$tilausnumero'";
			$trivires = pupe_query($query);
			$trivirow = mysql_fetch_array($trivires);

			$rivi = $trivirow['tilaajanrivinro'] + 1;
		}

		unset($jtsre);

		// Jos varasto on syötetty niin ostetaan siihen varastoon
		if ($varasto > 0) {
			$query = "	SELECT tuotepaikat.*
			 			FROM tuotepaikat
						JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
						and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and varastopaikat.tunnus = $varasto
						WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
						and tuotepaikat.tuoteno = '$tuoteno'";
			$jtsre = pupe_query($query);
			$jtstu = mysql_fetch_array($jtsre);
		}

		if (!isset($jtsre) or mysql_num_rows($jtsre) == 0) {
			// haetaan oletuspaikan tiedot niin laitetaan se riville
			$query = "	SELECT *
						FROM tuotepaikat
						WHERE yhtio = '$kukarow[yhtio]'
						AND tuoteno = '$tuoteno'
						AND oletus!=''";
			$jtsre = pupe_query($query);
			$jtstu = mysql_fetch_array($jtsre);
		}

		//haetaan tuotteen ostohinta
		$query = "	SELECT *
					FROM tuotteen_toimittajat
					WHERE yhtio = '$kukarow[yhtio]'
					AND tuoteno = '$tuoteno'
					AND liitostunnus = '$toimittaja'";
		$ossre = pupe_query($query);
		$osstu = mysql_fetch_array($ossre);

		//haetaan tuotteen ostohinta
		$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
		$tuotere = pupe_query($query);
		$tuoterow = mysql_fetch_array($tuotere);

		$hinta = $osstu["ostohinta"];

		//otetaan tuoteen kertoimet huomioon - eli jaetaan myyntihinta kertoimella, jotta saadaan yksikköhinta
		if ($tuoterow["myyntihinta_maara"] != 0 and $osstu["ostohinta"] != 0) {
			$hinta = hintapyoristys($osstu["ostohinta"] / $tuoterow["myyntihinta_maara"]);
		}

		// lisätään ostotilausrivi
		$query = "	INSERT into tilausrivi
					(hinta, ale1, nimitys, tuoteno, try, osasto, tilkpl, varattu, yksikko, otunnus, yhtio, tyyppi, kommentti, toimaika, kerayspvm,hyllyalue, hyllynro, hyllyvali, hyllytaso, tilaajanrivinro, laatija, laadittu) values
					('$hinta', '$osstu[alennus]','$tuoterow[nimitys]', '$tuoteno', '$tuoterow[try]', '$tuoterow[osasto]', '$maara', '$maara', '$tuoterow[yksikko]', '$tilausnumero', '$kukarow[yhtio]', 'O','', now(), now(), '$jtstu[hyllyalue]','$jtstu[hyllynro]','$jtstu[hyllyvali]','$jtstu[hyllytaso]', '$rivi','$kukarow[kuka]', now())";
		$updre = pupe_query($query);
	}

	echo json_encode('ok');

	// Footteri hiljaiseksi
	$no_head = "yes";

	require ("inc/footer.inc");
	exit;
?>