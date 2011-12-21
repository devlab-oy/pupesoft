<?php

	// tarvitaan
	// $kukarow
	// $tuoteno
	// $tee jossa tulee olla:
	// - paalle (t‰ysep‰kurantti)
	// - 75paalle
	// - puolipaalle
	// - 25paalle
	// - pois (kurantiksi ja keskihankintahinta nostetaan takaisin)
	// - peru (kurantiksi ja keskihankintahinta j‰‰ alas nykyiselle tasolle)

	$tuoteno = isset($tuoteno) ? mysql_real_escape_string(trim($tuoteno)) : "";
	$tee = isset($tee) ? trim($tee) : "";

	// Katsotaan lˆytyykˆ tuote
	if ($tuoteno != "") {

		$query = "	SELECT tuote.tunnus,
					tuote.tuoteno,
					tuote.kehahin,
					tuote.epakurantti25pvm,
					tuote.epakurantti50pvm,
					tuote.epakurantti75pvm,
					tuote.epakurantti100pvm,
					tuote.kustp,
					tuote.kohde,
					tuote.projekti,
					round(if (tuote.epakurantti100pvm = '0000-00-00',
							if (tuote.epakurantti75pvm = '0000-00-00',
								if (tuote.epakurantti50pvm = '0000-00-00',
									if (tuote.epakurantti25pvm = '0000-00-00',
										tuote.kehahin,
									tuote.kehahin * 0.75),
								tuote.kehahin * 0.5),
							tuote.kehahin * 0.25),
						0),
					6) kehahin_nyt,
					sum(tuotepaikat.saldo) saldo
					FROM tuote
					LEFT JOIN tuotepaikat ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno)
					WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.sarjanumeroseuranta NOT IN ('S','U','G')
					AND tuote.tuoteno = '$tuoteno'
					GROUP by 1,2,3,4,5,6,7,8,9,10,11";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>";
			printf (t("Tuote %s ei lˆydy!"), $tuoteno);
			echo "</font><br>";
			$tee = "";
		}
		else {
			$tuoterow = mysql_fetch_assoc($result);

			$nykyinen_keskihankintahinta = $tuoterow["kehahin_nyt"];
			$tuotteen_saldo = $tuoterow["saldo"];
			$brutto_varastonarvo = $tuotteen_saldo * $tuoterow["kehahin"];
			$brutto_keskihankintahinta = $tuoterow["kehahin"];

			// Tehd‰‰n selkokielinen selite
			$epakurantti_selite = "Ep‰kuranttimuutos: ".t("Tuote")." {$tuoterow["tuoteno"]} ".t("p‰ivitet‰‰n")." ";

			// Lasketaan uusi keskihankintahinta
			if ($tee == "paalle") {
				$uusi_keskihankintahinta = 0;
				$epakurantti_selite .= "100% ".t("ep‰kurantiksi");
			}
			elseif ($tee == "25paalle") {
				$uusi_keskihankintahinta = $tuoterow["kehahin"] * 0.75;
				$epakurantti_selite .= "25% ".t("ep‰kurantiksi");
			}
			elseif ($tee == "puolipaalle") {
				$uusi_keskihankintahinta = $tuoterow["kehahin"] * 0.5;
				$epakurantti_selite .= "50% ".t("ep‰kurantiksi");
			}
			elseif ($tee == "75paalle") {
				$uusi_keskihankintahinta = $tuoterow["kehahin"] * 0.25;
				$epakurantti_selite .= "75% ".t("ep‰kurantiksi");
			}
			elseif ($tee == "pois") {
				$uusi_keskihankintahinta = $tuoterow["kehahin"];
				$epakurantti_selite .= t("kurantiksi");
			}
			elseif ($tee == "peru") {
				$uusi_keskihankintahinta = $tuoterow["kehahin_nyt"];
				$epakurantti_selite .= t("kurantiksi");
			}
			else {
				$uusi_keskihankintahinta = $tuoterow["kehahin_nyt"];
				$epakurantti_selite .= $tee;
			}

			// Lasketaan keskihankintahinnan muutos
			$keskihankintahinta_muutos = abs($nykyinen_keskihankintahinta - $uusi_keskihankintahinta);

			// Lasketaan varastonmuutos
			$uusi_varastonarvo = $tuotteen_saldo * $uusi_keskihankintahinta;
			$nykyinen_varastonarvo = $tuotteen_saldo * $nykyinen_keskihankintahinta;
			$varaston_muutos = round($nykyinen_varastonarvo - $uusi_varastonarvo, 2) * -1;

			// Selitteen loppu
			$epakurantti_selite .= ". ".t("Alkup. kehahin").": $brutto_keskihankintahinta ";
			$epakurantti_selite .= t("Kehahin ennen muutosta").": $nykyinen_keskihankintahinta ";
			$epakurantti_selite .= t("Uusi kehahin").": $uusi_keskihankintahinta ";
			$epakurantti_selite .= t("Saldo").": $tuotteen_saldo ";
			$epakurantti_selite .= t("Varastonmuutos").": $varaston_muutos";
		}
	}

	// Tehd‰‰n oikeellisuustarkistukset
	if ($tee == "paalle" or $tee == "25paalle" or $tee == "puolipaalle" or $tee == "75paalle" or $tee == "pois" or $tee == "peru") {

		// katotaan onko tilauksessa
		$query = "	SELECT sum(varattu) varattu
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
					WHERE yhtio = '{$kukarow["yhtio"]}'
					and tuoteno = '{$tuoterow["tuoteno"]}'
					and varattu > 0
					and tyyppi = 'O'";
		$tilre = pupe_query($query);
		$tilro = mysql_fetch_assoc($tilre);

		if ($tilro["varattu"] != 0 and $tee != "pois" and $tee != "peru") {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuotetta on tilauksessa!")." ".t("Ei voida laittaa ep‰kurantiksi!")."</font><br>";
			$tee = "";
		}

		// katotaan onko liitetty keikkaan jonka virallista varastonarvoa ei ole laskettu
		$query = "	SELECT laskunro
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
						AND lasku.tunnus = tilausrivi.uusiotunnus
						AND lasku.tila = 'K'
						AND lasku.alatila != 'X')
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tuoterow[tuoteno]'
					and varattu = 0
					and tyyppi = 'O'";
		$tilre = pupe_query($query);

		if (mysql_num_rows($tilre) != 0 and $tee != "pois" and $tee != "peru") {
			$tilro = mysql_fetch_assoc($tilre);
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuotetta on viem‰tt‰ varastoon keikalla")." $tilro[laskunro]. ".t("Ei voida laittaa ep‰kurantiksi!")."</font><br>";
			$tee = "";
		}

		if ($tuoterow["saldo"] < 0) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuotten saldo on negatiivinen!")." ".t("Ei voida vaihtaa tilaa!")."</font><br>";
			$tee = "";
		}

		// ei voida aktivoida tuotetta kurantiksi jos se on jo kurantti
		if (($tee == 'pois' or $tee == "peru") and ($tuoterow['epakurantti25pvm'] == '0000-00-00') and ($tuoterow['epakurantti50pvm'] == '0000-00-00') and ($tuoterow['epakurantti75pvm'] == '0000-00-00') and ($tuoterow['epakurantti100pvm'] == '0000-00-00')) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote v‰‰r‰ss‰ tilassa!")." ".t("Ei voida laittaa kurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa puoliep‰kurantiksi jos tuote on puoliep‰kurantti tai t‰ysep‰kurantti
		if (($tee == '25paalle') and (($tuoterow['epakurantti25pvm'] != '0000-00-00') or ($tuoterow['epakurantti50pvm'] != '0000-00-00') or ($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00'))) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote v‰‰r‰ss‰ tilassa!")." ".t("Ei voida laittaa 25% ep‰kurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa puoliep‰kurantiksi jos tuote on puoliep‰kurantti tai t‰ysep‰kurantti
		if (($tee == 'puolipaalle') and (($tuoterow['epakurantti50pvm'] != '0000-00-00') or ($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00'))) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote v‰‰r‰ss‰ tilassa!")." ".t("Ei voida laittaa puoliep‰kurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa puoliep‰kurantiksi jos tuote on puoliep‰kurantti tai t‰ysep‰kurantti
		if (($tee == '75paalle') and (($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00'))) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote v‰‰r‰ss‰ tilassa!")." ".t("Ei voida laittaa 75% ep‰kurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa t‰ysep‰kurantiksi jos tuote on jo t‰ysep‰kurantti
		if (($tee == 'paalle')      and ($tuoterow['epakurantti100pvm'] != '0000-00-00')) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote v‰‰r‰ss‰ tilassa!")." ".t("Ei voida laittaa t‰ysiep‰kurantiksi!")."</font><br>";
			$tee = "";
		}

	}

	// P‰ivitet‰‰n tuote
	if ($tee == "paalle" or $tee == "25paalle" or $tee == "puolipaalle" or $tee == "75paalle" or $tee == "pois" or $tee == "peru") {

		// Tehd‰‰n lis‰muuttujat, jos hyp‰t‰‰n jonkun ep‰kuranttitason yli
		$lisa75 = "";
		$lisa50 = "";
		$lisa25 = "";

		if ($tuoterow['epakurantti75pvm'] == '0000-00-00') { // jos tuote ei ole puoliep‰kurantti, p‰ivitet‰‰n sama p‰iv‰ t‰nnekkin
			$lisa75 = " epakurantti75pvm = now(), ";
		}

		if ($tuoterow['epakurantti50pvm'] == '0000-00-00') { // jos tuote ei ole puoliep‰kurantti, p‰ivitet‰‰n sama p‰iv‰ t‰nnekkin
			$lisa50 = " epakurantti50pvm = now(), ";
		}

		if ($tuoterow['epakurantti25pvm'] == '0000-00-00') { // jos tuote ei ole puoliep‰kurantti, p‰ivitet‰‰n sama p‰iv‰ t‰nnekkin
			$lisa25 = " epakurantti25pvm = now(), ";
		}

		///* P‰ivitet‰‰n tuote kurantiksi *///
		if ($tee == 'pois' or $tee == "peru") {
			$query = "	UPDATE tuote SET
						epakurantti25pvm = '0000-00-00',
						epakurantti50pvm = '0000-00-00',
						epakurantti75pvm = '0000-00-00',
						epakurantti100pvm = '0000-00-00'
						WHERE yhtio = '$kukarow[yhtio]'
						AND	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* P‰ivitet‰‰n tuote t‰ysep‰kurantiksi *///
		if ($tee == 'paalle') {
			$query = "	UPDATE tuote set
						$lisa75
						$lisa50
						$lisa25
						epakurantti100pvm = now()
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* P‰ivitet‰‰n tuote 75ep‰kurantiksi *///
		if ($tee == '75paalle') {
			$query = "	UPDATE tuote set
						$lisa50
						$lisa25
						epakurantti75pvm = now(),
						epakurantti100pvm = ''
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* P‰ivitet‰‰n tuote puoliep‰kurantiksi *///
		if ($tee == 'puolipaalle') {
			$query = "	UPDATE tuote set
						$lisa25
						epakurantti50pvm = now(),
						epakurantti75pvm = '',
						epakurantti100pvm = ''
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* P‰ivitet‰‰n tuote 25ep‰kurantiksi *///
		if ($tee == '25paalle') {
			$query = "	UPDATE tuote set
						epakurantti25pvm = now(),
						epakurantti50pvm = '',
						epakurantti75pvm = '',
						epakurantti100pvm = ''
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* P‰ivitet‰‰n tuotteen keskihankintahinta nykyiselle tasolle *///
		if ($tee == 'peru') {
			$query = "	UPDATE tuote SET
						kehahin = '$uusi_keskihankintahinta',
						muutospvm = now(),
						muuttaja = '$kukarow[kuka]'
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		// jos ollaan laittamassa tuotetta ep‰kurantiksi niin k‰‰nnet‰‰n saldon etumerkki
		if ($tee == 'paalle' or $tee == '75paalle' or $tee == 'puolipaalle' or $tee == '25paalle') {
			$tuotteen_saldo = $tuotteen_saldo * -1;
		}

		///* Tehd‰‰n tapahtuma *///
		$query = "	INSERT into tapahtuma set
					yhtio   	= '$kukarow[yhtio]',
					tuoteno 	= '$tuoterow[tuoteno]',
					laji    	= 'Ep‰kurantti',
					kpl     	= '$tuotteen_saldo',
					hinta   	= round($keskihankintahinta_muutos, 6),
					kplhinta	= round($keskihankintahinta_muutos, 6),
					selite  	= '$epakurantti_selite',
					laatija    	= '$kukarow[kuka]',
					laadittu 	= now()";
		$result = pupe_query($query);

		// otetaan tapahtuman tunnus, laitetaan se tiliˆinnin otsikolle
		$tapahtumaid = mysql_insert_id();

		///* Kirjanpito *///
		if ($varaston_muutos <> 0) {

			if ($tapahtumat_samalle_tositteelle != "kylla" or $laskuid == 0) {
				$query = "	INSERT into lasku set
							yhtio      = '$kukarow[yhtio]',
							tapvm      = now(),
							tila       = 'X',
							alatila    = 'E',
							viite      = '$tapahtumaid',
							laatija    = '$kukarow[kuka]',
							luontiaika = now()";
				$result = pupe_query($query);
				$laskuid = mysql_insert_id($link);
			}

			if ($yhtiorow["varastonmuutos_epakurantti"] != "") {
				$varastonmuutos_tili = $yhtiorow["varastonmuutos_epakurantti"];
			}
			else {
				$varastonmuutos_tili = $yhtiorow["varastonmuutos"];
			}

			// Otetaan ensisijaisesti kustannuspaikka tuotteen takaa
			$kustp_ins 		= $tuoterow["kustp"];
			$kohde_ins 		= $tuoterow["kohde"];
			$projekti_ins 	= $tuoterow["projekti"];

			// Tiliˆid‰‰n ensisijaisesti varastonmuutos tilin oletuskustannuspaikalle
			list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastonmuutos_tili, $kustp_ins, $kohde_ins, $projekti_ins);

			// Toissijaisesti kokeillaan viel‰ varasto-tilin oletuskustannuspaikkaa
			list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varasto"], $kustp_ins, $kohde_ins, $projekti_ins);

			$query = "	INSERT into tiliointi set
						yhtio    = '$kukarow[yhtio]',
						ltunnus  = '$laskuid',
						tilino   = '$yhtiorow[varasto]',
						kustp    = '{$kustp_ins}',
						kohde	 = '{$kohde_ins}',
						projekti = '{$projekti_ins}',
						tapvm    = now(),
						summa    = $varaston_muutos,
						vero     = 0,
						lukko    = '',
						selite   = '$epakurantti_selite',
						laatija  = '$kukarow[kuka]',
						laadittu = now()";
			$result = pupe_query($query);

			$query = "	INSERT into tiliointi set
						yhtio    = '$kukarow[yhtio]',
						ltunnus  = '$laskuid',
						tilino   = '$varastonmuutos_tili',
						kustp    = '{$kustp_ins}',
						kohde	 = '{$kohde_ins}',
						projekti = '{$projekti_ins}',
						tapvm    = now(),
						summa    = $varaston_muutos * -1,
						vero     = 0,
						lukko    = '',
						selite   = '$epakurantti_selite',
						laatija  = '$kukarow[kuka]',
						laadittu = now()";
			$result = pupe_query($query);
		}

		if (!isset($php_cli) or !$php_cli) {
			echo "<font class='message'>";
			echo t("Tuote")." $tuoteno ok!<br>";
			echo t("Varastonarvoa muutettiin")." $varaston_muutos $yhtiorow[valkoodi].<br>";
			echo "</font><br>";
		}
	}
?>