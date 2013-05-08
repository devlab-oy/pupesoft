<?php

if (file_exists("tulosta_tilaustuotetarrat.inc")) {
	require_once("tulosta_tilaustuotetarrat.inc");
}
else {
	require_once("tilauskasittely/tulosta_tilaustuotetarrat.inc");
}

//vähän joutuu laitaa talteen näitä alkuperäisiä
$isavarattu   = $atil;
$isatuoteno   = $tuoteno;
$useavalmiste = "";

if ($tee == 'UV') {
	if ($atil <= 0) {
		$tee = '';
		echo "<font class='error'>".t("Anna valmistettava määrä")."</font><br>";
	}
}

// Katsotaan onko useita valmisteita?
if ($tee == 'UV') {
	$query = "	SELECT tilausrivi.*, tuote.ei_saldoa, tuote.kehahin
				FROM tilausrivi
				JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.otunnus = '$tilrivirow[otunnus]'
				and tilausrivi.perheid = '$tilrivirow[perheid]'
				and tilausrivi.tunnus != '$tilrivirow[perheid]'
				and tilausrivi.tyyppi in ('W','M')
				and tilausrivi.toimitettuaika = '0000-00-00 00:00:00'
				ORDER by tilausrivi.tuoteno";
	$uvresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($uvresult) > 0) {
		$useavalmiste = "ON";
	}
}

if ($tee == 'UV') {

	unset($tuoteresult);

	// käytetään rivillä olevaa paikkaa
	$query = "	SELECT tuotepaikat.saldo, tuote.ei_saldoa, tuote.yksikko, tuote.kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
				tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
				FROM tuote
				JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
				WHERE tuote.yhtio 		  = '$kukarow[yhtio]'
				and tuote.tuoteno 		  = '$isatuoteno'
				and tuotepaikat.hyllyalue = '$tilrivirow[hyllyalue]'
				and tuotepaikat.hyllynro  = '$tilrivirow[hyllynro]'
				and tuotepaikat.hyllytaso = '$tilrivirow[hyllytaso]'
				and tuotepaikat.hyllyvali = '$tilrivirow[hyllyvali]'";
	$tuoteresult = pupe_query($query);

	if (mysql_num_rows($tuoteresult) != 1) {
		//jos ei löytynyt yksiselitteistä riviä yritetään oletuspaikkaa
		$query = "	SELECT tuotepaikat.saldo, tuote.ei_saldoa, tuote.yksikko, tuote.kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
					tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
					FROM tuote
					JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
					WHERE tuote.yhtio 	    = '$kukarow[yhtio]'
					and tuote.tuoteno 	    = '$isatuoteno'
					and tuotepaikat.oletus != ''
					LIMIT 1";
		$tuoteresult = pupe_query($query);
	}

	if (mysql_num_rows($tuoteresult) != 1) {
		//jos ei löytynyt yksiselitteistä riviä yritetään mitä tahansa paikkaa
		$query = "	SELECT tuotepaikat.saldo, tuote.ei_saldoa, tuote.yksikko, tuote.kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
					tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
					FROM tuote
					JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno = '$isatuoteno'
					LIMIT 1";
		$tuoteresult = pupe_query($query);
	}

	if (mysql_num_rows($tuoteresult) != 1) {
		$tee = '';
		echo "<font class='error'>$isatuoteno ".t("Varastopaikkaa ei löydy")."</font><br>";
	}
	else {
		// Valmisteen kokonaissaldo
		$query = "	SELECT sum(saldo) kokonaissaldo
					FROM tuotepaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$isatuoteno'";
		$saldores = pupe_query($query);
		$saldorow = mysql_fetch_assoc($saldores);

		$valmisterow = mysql_fetch_assoc($tuoteresult);
		$valmisterow['kokonaissaldo'] = $saldorow['kokonaissaldo'];

		// Päivitetään sarjanumerolle ja riville löytynyt paikka
		$query = "	UPDATE tilausrivi SET
					hyllyalue = '$valmisterow[hyllyalue]',
					hyllynro  = '$valmisterow[hyllynro]',
					hyllytaso = '$valmisterow[hyllytaso]',
					hyllyvali = '$valmisterow[hyllyvali]'
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$tilrivirow[tunnus]'";
		$lisa_res = pupe_query($query);

		$tilrivirow['hyllyalue'] = $valmisterow['hyllyalue'];
		$tilrivirow['hyllynro']  = $valmisterow['hyllynro'];
		$tilrivirow['hyllytaso'] = $valmisterow['hyllytaso'];
		$tilrivirow['hyllyvali'] = $valmisterow['hyllyvali'];

		if ($valmisterow['sarjanumeroseuranta'] != "") {
			$query = "	UPDATE sarjanumeroseuranta SET
						hyllyalue = '$valmisterow[hyllyalue]',
						hyllynro  = '$valmisterow[hyllynro]',
						hyllytaso = '$valmisterow[hyllytaso]',
						hyllyvali = '$valmisterow[hyllyvali]'
						WHERE yhtio 	   = '$kukarow[yhtio]'
						and ostorivitunnus = '$tilrivirow[tunnus]'";
			$lisa_res = pupe_query($query);
		}
	}

	// raaka-aineet
	$query = "	SELECT tilausrivi.*,
				tuote.ei_saldoa, tuote.sarjanumeroseuranta
				FROM tilausrivi
				JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.otunnus = '$tilrivirow[otunnus]'
				and tilausrivi.perheid = '$tilrivirow[perheid]'
				and tilausrivi.tyyppi = 'V'
				ORDER BY tilausrivi.tuoteno";
	$raakaaineres = pupe_query($query);

	//jos tuoteperhe on olemassa
	if (mysql_num_rows($raakaaineres) > 0) {
		while ($raakaainerow = mysql_fetch_assoc($raakaaineres)) {
			if ($raakaainerow["ei_saldoa"] == "") {

				unset($tuoteresult);

				$varataankpl = 0;

				//Näin paljon haluamme käyttää raaka-ainetta
				if ($kulukpllat[$raakaainerow["tunnus"]] != 0) {
					$varataankpl = $kulukpllat[$raakaainerow["tunnus"]];
				}
				else {
					$varataankpl = $raakaainerow['varattu'] * $akerroin;
				}

				//käytetään rivillä olevaa paikkaa
				$query = "	SELECT tuotepaikat.saldo, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
							FROM tuote
							JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
							WHERE tuote.yhtio 		  = '$kukarow[yhtio]'
							and tuote.tuoteno 		  = '$raakaainerow[tuoteno]'
							and tuotepaikat.hyllyalue = '$raakaainerow[hyllyalue]'
							and tuotepaikat.hyllynro  = '$raakaainerow[hyllynro]'
							and tuotepaikat.hyllytaso = '$raakaainerow[hyllytaso]'
							and tuotepaikat.hyllyvali = '$raakaainerow[hyllyvali]'";
				$tuoteresult = pupe_query($query);

				if (mysql_num_rows($tuoteresult) != 1) {
					//jos ei löytynyt yksiselitteistä riviä yritetään oletuspaikkaa
					$query = "	SELECT tuotepaikat.saldo, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
								FROM tuote
								JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
								WHERE tuote.yhtio 	    = '$kukarow[yhtio]'
								and tuote.tuoteno 	    = '$raakaainerow[tuoteno]'
								and tuotepaikat.oletus != ''
								LIMIT 1";
					$tuoteresult = pupe_query($query);
				}

				if (mysql_num_rows($tuoteresult) != 1) {
					//jos ei löytynyt yksiselitteistä riviä yritetään mitä tahansa paikkaa
					$query = "	SELECT tuotepaikat.saldo, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
								FROM tuote
								JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
								WHERE tuote.yhtio = '$kukarow[yhtio]'
								and tuote.tuoteno = '$raakaainerow[tuoteno]'
								LIMIT 1";
					$tuoteresult = pupe_query($query);
				}

				//katotaan kanssa, että perheenjäsenet löytyy kannasta ja niitä on riittävästi
				if (mysql_num_rows($tuoteresult) == 1) {
					$trow = mysql_fetch_assoc($tuoteresult);

					// Päivitetään sarjanumerolle ja riville löytynyt paikka
					$query = "	UPDATE tilausrivi SET
								hyllyalue = '$trow[hyllyalue]',
								hyllynro  = '$trow[hyllynro]',
								hyllytaso = '$trow[hyllytaso]',
								hyllyvali = '$trow[hyllyvali]'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = '$raakaainerow[tunnus]'";
					$lisa_res = pupe_query($query);

					if ($raakaainerow['sarjanumeroseuranta'] != "") {
						$query = "	UPDATE sarjanumeroseuranta SET
									hyllyalue = '$trow[hyllyalue]',
									hyllynro  = '$trow[hyllynro]',
									hyllytaso = '$trow[hyllytaso]',
									hyllyvali = '$trow[hyllyvali]'
									WHERE yhtio 	     = '$kukarow[yhtio]'
									and myyntirivitunnus = '$raakaainerow[tunnus]'";
						$lisa_res = pupe_query($query);
					}

					if ($trow['saldo'] < $varataankpl and $vakisinhyvaksy == "") {
						$tee = ''; //Raaka-aineet eivät riitä!
						echo "<font class='error'>$raakaainerow[tuoteno] ".t("saldo")." $trow[saldo] ".t("ei riitä valmistukseen")." ($varataankpl)</font><br>";
					}
				}
				else {
					$tee = '';
					echo "<font class='error'>$raakaainerow[tuoteno] ".t("Varastopaikkaa ei löydy")."</font><br>";
				}
			}
		}
	}
	else {
		$tee = '';
		echo "<font class='error'>$tuoteno ".t("ei ole rakenne/resepti")."</font>";
	}

	echo "<br><br>";
}

if ($tee == 'UV') { //Tehdään tuotteet!

	$uusiarvo 	= 0;
	$kpl 		= 0;

	$query = "	SELECT tilausrivi.*,
				tuote.ei_saldoa, tuote.sarjanumeroseuranta
				FROM tilausrivi
				JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.otunnus = '$tilrivirow[otunnus]'
				and tilausrivi.perheid = '$tilrivirow[perheid]'
				and tilausrivi.tyyppi = 'V'
				ORDER BY tilausrivi.tuoteno";
	$raakaaineres = pupe_query($query);

	while ($raakaainerow = mysql_fetch_assoc($raakaaineres)) {

		unset($tuoteresult);

		if ($raakaainerow["ei_saldoa"] == "") {
			$query = "	SELECT tuotepaikat.saldo, tuote.kehahin, tuote.ei_saldoa, tuote.yksikko, tuotepaikat.tunnus tunnus,
						tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm,
						tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
						FROM tuote
						JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
						WHERE tuote.yhtio 		  = '$kukarow[yhtio]'
						and tuote.tuoteno 		  = '$raakaainerow[tuoteno]'
						and tuotepaikat.hyllyalue = '$raakaainerow[hyllyalue]'
						and tuotepaikat.hyllynro  = '$raakaainerow[hyllynro]'
						and tuotepaikat.hyllytaso = '$raakaainerow[hyllytaso]'
						and tuotepaikat.hyllyvali = '$raakaainerow[hyllyvali]'";
		}
		else {
			//sittenhän tuote on saldoton
			$query = "	SELECT tuote.kehahin, tuote.ei_saldoa, tuote.yksikko,
						tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm
						FROM tuote
						WHERE tuote.tuoteno = '$raakaainerow[tuoteno]'
						and tuote.yhtio = '$kukarow[yhtio]'";
		}

		$tuoteresult = pupe_query($query);
		$tuoterow = mysql_fetch_assoc($tuoteresult);

		// Poistetaan raaka-aineet varastosta
		if ($kulukpllat[$raakaainerow["tunnus"]] != 0) {
			$kpl = str_replace(',','.',$kulukpllat[$raakaainerow["tunnus"]]);
		}
		else {
			$kpl = $raakaainerow['varattu'] * str_replace(',','.',$akerroin);
		}

		$kpl = str_replace(',','.',$kpl);

		if    ($tuoterow['epakurantti100pvm'] != '0000-00-00') $tuoterow['kehahin'] = 0;
		elseif ($tuoterow['epakurantti75pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.25, 6);
		elseif ($tuoterow['epakurantti50pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.5,  6);
		elseif ($tuoterow['epakurantti25pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.75, 6);

		if (in_array($raakaainerow["sarjanumeroseuranta"], array("S","U","G"))) {
			$arvo = sarjanumeron_ostohinta("myyntirivitunnus", $raakaainerow["tunnus"])*$kpl;
		}
		else {
			$arvo = $tuoterow['kehahin'] * $kpl;
		}

		$uusiarvo += $arvo;

		///* Tehdään tapahtuma *///
		$query = "	INSERT into tapahtuma set
					yhtio    	= '$kukarow[yhtio]',
					tuoteno  	= '$raakaainerow[tuoteno]',
					laji     	= 'kulutus',
					kpl      	=  round($kpl*-1 ,2),
					kplhinta 	= '$tuoterow[kehahin]',
					hinta    	= '$tuoterow[kehahin]',
					rivitunnus	= '$raakaainerow[tunnus]',
					hyllyalue 	= '$tuoterow[hyllyalue]',
					hyllynro 	= '$tuoterow[hyllynro]',
					hyllytaso 	= '$tuoterow[hyllytaso]',
					hyllyvali 	= '$tuoterow[hyllyvali]',
					selite   	= '".t("Käytettiin tuotteen")." $isatuoteno ".t("valmistamiseen")." ".t("työmääräyksellä")." $tilrivirow[otunnus]',
					laatija  	= '$kukarow[kuka]',
					laadittu 	= now()";
		$result = pupe_query($query);

		if ($tuoterow['ei_saldoa'] == "") {
			$query = "	UPDATE tuotepaikat
						SET saldo	= round(saldo-$kpl,2)
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$raakaainerow[tuoteno]'
						and tunnus  = '$tuoterow[tunnus]'";
			$result = pupe_query($query);

			//	Merkataan erä kulutetuksi
			if (in_array($raakaainerow["sarjanumeroseuranta"], array("E","F","G"))) {

				// Haetaan eka raaka-aineriviin liitetyn erän tiedot
				$query = "	SELECT ostorivitunnus, myyntirivitunnus, sarjanumero, tunnus
							FROM sarjanumeroseuranta
							WHERE yhtio			 = '$kukarow[yhtio]'
							and tuoteno			 = '$raakaainerow[tuoteno]'
							and myyntirivitunnus = '$raakaainerow[tunnus]'
							LIMIT 1";
				$lisa_res = pupe_query($query);
				$lisa_row = mysql_fetch_assoc($lisa_res);

				// luodaan muuttuja jota pienennetään luupissa
				$eraayht = $kpl;

				// koitetaan löytää vapaita ostettuja eriä mitä kuluttaa
				$query = "	SELECT era_kpl, tunnus, ostorivitunnus
							FROM sarjanumeroseuranta
							WHERE yhtio 		 = '$kukarow[yhtio]'
							and tuoteno			 = '$raakaainerow[tuoteno]'
							and ostorivitunnus 	 > 0
							and myyntirivitunnus = 0
							and sarjanumero 	 = '$lisa_row[sarjanumero]'
							and era_kpl 		 > 0
							and hyllyalue   	 = '$tuoterow[hyllyalue]'
							and hyllynro    	 = '$tuoterow[hyllynro]'
							and hyllyvali   	 = '$tuoterow[hyllyvali]'
							and hyllytaso   	 = '$tuoterow[hyllytaso]'
							ORDER BY era_kpl DESC, tunnus";
				$erajaljella_res = pupe_query($query);

				// jos löytyy ostettuja eriä myytäväks niin mennään tänne
				while ($erajaljella_row = mysql_fetch_assoc($erajaljella_res)) {

					// luodaan muuttuja jossa annetaan kyselyille määrämuutoksen
					$eravahennetaan = 0;

					// jos löydetyn erän määrä riittää koko tarvittavaan määrään niin otetaan sen verran ku tarvitaan
					if ($erajaljella_row["era_kpl"] >= $eraayht) {
						$eravahennetaan = $eraayht;
					}
					// muuten vaan löydetyn erän verran
					else {
						$eravahennetaan = $erajaljella_row["era_kpl"];
					}

					// vähennetään ostoriviltä tarvittava määrä
					$query = "	UPDATE sarjanumeroseuranta
								SET era_kpl = era_kpl-$eravahennetaan,
								muutospvm   = now(),
								muuttaja    = '$kukarow[kuka]'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = '$erajaljella_row[tunnus]'";
					$lisa_res = pupe_query($query);

					// lisätään raaka-aineriviin liitetylle erälle määrä, ja samalla päivitetään ostorivitunnus oikeaksi
					$query = "	UPDATE sarjanumeroseuranta
								SET era_kpl	    = era_kpl+$eravahennetaan,
								ostorivitunnus  = '$erajaljella_row[ostorivitunnus]',
								muutospvm	    = now(),
								muuttaja	    = '$kukarow[kuka]'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus  = '$lisa_row[tunnus]'";
					$lisa_res = pupe_query($query);

					// vähennetään luupattavaa määrää
					$eraayht -= $eravahennetaan;
				}

				// Ei löytynyt riittävästi ostettuja eriä, eli "myyty pakkaselle" niin luodaan uusi erärivi jossa ostorivitunnus on nolla jonne laitetaan loppumäärä.
				if ($eraayht != 0) {

					$query = "	INSERT into sarjanumeroseuranta
								SET yhtio 		 = '$kukarow[yhtio]',
								tuoteno			 = '$raakaainerow[tuoteno]',
								myyntirivitunnus = $raakaainerow[tunnus],
								ostorivitunnus 	 = 0,
								era_kpl			 = 0,
								laatija			 = '$kukarow[kuka]',
								luontiaika		 = now(),
								hyllyalue   	 = '$tuoterow[hyllyalue]',
								hyllynro    	 = '$tuoterow[hyllynro]',
								hyllyvali   	 = '$tuoterow[hyllyvali]',
								hyllytaso   	 = '$tuoterow[hyllytaso]',
								sarjanumero 	 = '$lisa_row[sarjanumero]'";
					#$lisa_res = pupe_query($query);

					$query = "	UPDATE sarjanumeroseuranta
								SET era_kpl	   = $eraayht,
								ostorivitunnus = '$raakaainerow[tunnus]',
								muutospvm	   = now(),
								muuttaja	   = '$kukarow[kuka]'
								WHERE yhtio			 = '$kukarow[yhtio]'
								and tuoteno			 = '$raakaainerow[tuoteno]'
								and myyntirivitunnus = '$raakaainerow[tunnus]'
								and era_kpl			 = 0
								LIMIT 1";
					#$lisa_res = pupe_query($query);
				}
			}

			echo "<font class='message'>".t("Varastosta käytettiin tuotetta")." $raakaainerow[tuoteno] $kpl ".t_avainsana("Y", "", "and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite").", $tuoterow[kehahin]</font><br>";

		}
		else {
			echo "<font class='message'>".t("Käytettiin tuotetta")." $raakaainerow[tuoteno] $kpl ".t_avainsana("Y", "", "and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite").", $tuoterow[kehahin]</font><br>";
		}

		//päivitetään tilausrivi käytetyksi
		$query = "	UPDATE tilausrivi
					SET varattu = round(varattu-$kpl,2),
					kpl			= round(kpl+$kpl,2)
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilrivirow[otunnus]'
					and tunnus  = '$raakaainerow[tunnus]'
					and tyyppi  = 'V'";
		$upparesult = pupe_query($query);
	}

	if (!function_exists("valmista_isa")) {
		function valmista_isa ($valmisterow, $tilrivirow, $isavarattu, $isatuoteno, $uusiarvo, $kaikenkaikkiaan, $kaikenkaikkiaan_arvot) {
			global $yhtiorow, $kukarow, $laskurow, $valmistettavat, $jaljella_tot;

			// Jos on useampi valmiste
			if ((float) $kaikenkaikkiaan > 1) {

				if ((float) $kaikenkaikkiaan_arvot > 0) {
					//jyvitetään $uusiarvo valmisteille kappaleiden ja kehahintojen mukaan
					$uusiarvo = round($uusiarvo * (($isavarattu * $valmisterow["kehahin"])/$kaikenkaikkiaan_arvot), 6);
				}
				else {
					//jyvitetään $uusiarvo valmisteille valmisteiden määrän mukaan
					$uusiarvo = round($uusiarvo/$kaikenkaikkiaan, 6);
				}
			}

			if ($valmisterow["sarjanumeroseuranta"] == "S" or $valmisterow["sarjanumeroseuranta"] == "U" or $valmisterow["sarjanumeroseuranta"] == "G") {
				$kehahin = 0;
				$selite = t("Valmistettiin tuotetta työmääräyksellä")." $tilrivirow[otunnus] ($uusiarvo)";
			}
			else {
				if ($valmisterow['kokonaissaldo'] + $isavarattu > 0 and $valmisterow['kokonaissaldo'] > 0) {
					// kehahin matikka (tuotteella pitää olla saldoa ennen ja jälkeen, että kehahin lasketaan)
					$kehahin = round(($valmisterow['kokonaissaldo'] * $valmisterow['kehahin'] + $uusiarvo) / ($valmisterow['kokonaissaldo'] + $isavarattu), 4);
				}
				else {
					// jos jäädään saldossa tämänkin tulon jälkeen miinukselle tai nollille, laitetaan kehahin suoraan tämän valmistuksen hinnasta
					$kehahin = round($uusiarvo/$isavarattu, 4);
				}

				$selite = t("Valmistettiin tuotetta työmääräyksellä")." $tilrivirow[otunnus] ($valmisterow[kehahin] --> $kehahin)";
			}

			///* Tehdään tapahtuma *///
			$query = "	INSERT into tapahtuma set
						yhtio   	= '$kukarow[yhtio]',
						tuoteno 	= '$isatuoteno',
						rivitunnus 	= '$tilrivirow[tunnus]',
						laji    	= 'valmistus',
						kpl     	= '$isavarattu',
						kplhinta	=  round($uusiarvo/$isavarattu, 6),
						hinta   	= '$kehahin',
						hyllyalue 	= '$valmisterow[hyllyalue]',
						hyllynro 	= '$valmisterow[hyllynro]',
						hyllytaso 	= '$valmisterow[hyllytaso]',
						hyllyvali 	= '$valmisterow[hyllyvali]',
						selite  	= '$selite',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now()";
			$result = pupe_query($query);
			$tapahtumaid = mysql_insert_id();

			$query = "	UPDATE tuote
						SET kehahin	= $kehahin,
						vihahin		= round($uusiarvo/$isavarattu, 2),
						vihapvm		= now()
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$isatuoteno'";
			$result = pupe_query($query);

			$query = "	UPDATE tuotepaikat
						SET saldo = saldo+$isavarattu
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$isatuoteno'
						and tunnus  = '$valmisterow[tunnus]'";
			$result = pupe_query($query);

			// päivitetään tilausrivi käytetyksi ja splitataan se...
			// osavalmistus tai kokovalmistus
			if ($isavarattu <= $tilrivirow["varattu"]) {

				$ale_query_insert_lisa = '';

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					$ale_query_insert_lisa .= " ale{$alepostfix} = '".$tilrivirow["ale{$alepostfix}"]."',";
				}

				// Asiakkaallevalmistus
				if ($laskurow["tilaustyyppi"] == "V" and $tilrivirow["tyyppi"] == "W") {

					//luodaan toimitettu valmisterivi
					$query = "	INSERT into tilausrivi set
								laatija			= '$kukarow[kuka]',
								laadittu 		= now(),
								yhtio 			= '$kukarow[yhtio]',
								tuoteno 		= '$tilrivirow[tuoteno]',
								varattu 		= '$isavarattu',
								tilkpl 			= '$isavarattu',
								kpl 			= '',
								{$ale_query_insert_lisa}
								erikoisale 		= '$tilrivirow[erikoisale]',
								netto 			= '$tilrivirow[netto]',
								jt 				= '',
								yksikko 		= '$tilrivirow[yksikko]',
								try 			= '$tilrivirow[try]',
								osasto 			= '$tilrivirow[osasto]',
								alv 			= '$tilrivirow[alv]',
								hinta 			= '$tilrivirow[hinta]',
								kerayspvm 		= '$tilrivirow[kerayspvm]',
								nimitys 		= '$tilrivirow[nimitys]',
								otunnus 		= '$tilrivirow[otunnus]',
								uusiotunnus		= '$tilrivirow[uusiotunnus]',
								tyyppi 			= 'L',
								keratty	 		= '$kukarow[kuka]',
								kerattyaika		= now(),
								toimaika 		= '$tilrivirow[toimaika]',
								var 			= '$tilrivirow[var]',
								perheid 		= 0,
								kommentti 		= '$tilrivirow[kommentti]',
								hyllyalue 		= '$tilrivirow[hyllyalue]',
								hyllynro 		= '$tilrivirow[hyllynro]',
								hyllytaso 		= '$tilrivirow[hyllytaso]',
								hyllyvali 		= '$tilrivirow[hyllyvali]'";
					$insresult = pupe_query($query);
					$valmistettavat .= ",".mysql_insert_id();
				}

				//	Kaikille sarjanumeroille tehdään varastoon saavutettu O-rivi, muuten mm keräys ei toimi ja ehkä se on myös loogisempaa?
				//	Päivitetään valmistetulle riville laskutettuaika, jotta se näkyy kaikkialla oikein
				if ($valmisterow["sarjanumeroseuranta"] != "") {
					$tilausriviTyyppi = "O";
					$laskutettuLisa = ", laskutettu = '$kukarow[kuka]', laskutettuaika = now()";
				}
				else {
					$tilausriviTyyppi = "D";
					$laskutettuLisa = "";
				}

				// luodaan toimitettu valmisterivi
				$query = "	INSERT into tilausrivi set
							laatija			= '$kukarow[kuka]',
							laadittu 		= now(),
							yhtio 			= '$kukarow[yhtio]',
							tuoteno 		= '$tilrivirow[tuoteno]',
							varattu 		= '$isavarattu',
							tilkpl 			= '$isavarattu',
							kpl 			= '$isavarattu',
							{$ale_query_insert_lisa}
							erikoisale 		= '$tilrivirow[erikoisale]',
							netto 			= '$tilrivirow[netto]',
							jt 				= '',
							yksikko 		= '$tilrivirow[yksikko]',
							try 			= '$tilrivirow[try]',
							osasto 			= '$tilrivirow[osasto]',
							alv 			= '$tilrivirow[alv]',
							hinta 			= '$tilrivirow[hinta]',
							rivihinta		= '$uusiarvo',
							kerayspvm 		= '$tilrivirow[kerayspvm]',
							nimitys 		= '$tilrivirow[nimitys]',
							otunnus 		= '$tilrivirow[otunnus]',
							uusiotunnus		= '$tilrivirow[uusiotunnus]',
							tyyppi 			= '$tilausriviTyyppi',
							keratty	 		= '$kukarow[kuka]',
							kerattyaika		= now(),
							toimaika 		= '$tilrivirow[toimaika]',
							var 			= '$tilrivirow[var]',
							perheid 		= '$tilrivirow[perheid]',
							kommentti 		= '$tilrivirow[kommentti]',
							hyllyalue 		= '$tilrivirow[hyllyalue]',
							hyllynro 		= '$tilrivirow[hyllynro]',
							hyllytaso 		= '$tilrivirow[hyllytaso]',
							hyllyvali 		= '$tilrivirow[hyllyvali]'
							$laskutettuLisa";
				$insresult = pupe_query($query);
				$uusrivitunnus = mysql_insert_id();
				$valmistettavat .= ",".$uusrivitunnus;

				$jaljella = round($tilrivirow["varattu"] - $isavarattu, 2);
				$jaljella_tot += $jaljella;

				//päivitetään alkuperäinen rivi
				$query = "	UPDATE tilausrivi
							SET varattu = '$jaljella',
							kpl 		= round(kpl+$isavarattu,2)
							WHERE yhtio = '$kukarow[yhtio]'
							and otunnus = '$tilrivirow[otunnus]'
							and tunnus  = '$tilrivirow[tunnus]'
							and tyyppi in ('W','M')";
				$upparesult = pupe_query($query);

				// Siirretään sarjanumerot valmistetulle tilausriville
				if (in_array($valmisterow["sarjanumeroseuranta"], array("S","T","U","V"))) {

					//	Siirretään ostorivin tunnus valmistetulle riville
					$query = "	SELECT tunnus
								FROM sarjanumeroseuranta
								WHERE yhtio = '$kukarow[yhtio]'
								and ostorivitunnus = '$tilrivirow[tunnus]'
								LIMIT ".(int) $isavarattu."";
					$sarjares = pupe_query($query);

					while ($sarjarow = mysql_fetch_assoc($sarjares)) {
						$query = "	UPDATE sarjanumeroseuranta SET
									ostorivitunnus	= $uusrivitunnus
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus = '$sarjarow[tunnus]'";
						$result = pupe_query($query);
					}

					// Generoidaan loput jos jotain puuttuu!
					$puuttuva_sarjanumerot = $isavarattu - mysql_num_rows($sarjares);

					if ($puuttuva_sarjanumerot > 0) {
						for ($i=1; $i<=$puuttuva_sarjanumerot; $i++) {
							//	Arvotaan uniikki sarjanumero
							$query = "	SELECT count(distinct(sarjanumero)) kpl
										FROM sarjanumeroseuranta
										WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tilrivirow[tuoteno]'";
							$sarjares = pupe_query($query);
							$sarjarow = mysql_fetch_assoc($sarjares);
							$sarja = $sarjarow["kpl"]+1;

							// varmistetaan, että tämä on uniikki!
							$onUniikki = false;

							while ($onUniikki === false) {
								$sarjanro = "$tilrivirow[tuoteno]-$sarja";

								$query = "	SELECT tunnus
											FROM sarjanumeroseuranta
											WHERE yhtio = '$kukarow[yhtio]'
											and tuoteno = '$tilrivirow[tuoteno]'
											and sarjanumero = '$sarjanro'";
								$sarjares = pupe_query($query);

								if (mysql_num_rows($sarjares) == 0) {
									$onUniikki = true;
								}
								else {
									$sarja++;
								}
							}

							$query = "	INSERT INTO sarjanumeroseuranta
										(yhtio, tuoteno, sarjanumero, ostorivitunnus, myyntirivitunnus, laatija, luontiaika, hyllyalue, hyllynro, hyllyvali, hyllytaso)
										VALUES ('$kukarow[yhtio]', '$tilrivirow[tuoteno]', '$sarjanro', '$uusrivitunnus', 0, '$kuka[kuka]', now(), '$tilrivirow[hyllyalue]', '$tilrivirow[hyllynro]', '$tilrivirow[hyllyvali]', '$tilrivirow[hyllytaso]')";
							$sarjares1 = pupe_query($query);
							$uusisarjatunnus = mysql_insert_id();
						}
					}

					tulosta_tilaustuotetarrat(0, $uusrivitunnus, "OPTIMOI");
				}
				elseif (in_array($valmisterow["sarjanumeroseuranta"], array("E","F","G"))) {

					// Siirretään ostorivin tunnus valmistetulle riville
					$query = "	SELECT tunnus, era_kpl, sarjanumero, lisatieto
								FROM sarjanumeroseuranta
								WHERE yhtio = '$kukarow[yhtio]'
								and ostorivitunnus = '$tilrivirow[tunnus]'";
					$sarjares = pupe_query($query);

					// Generoidaan loput jos jotain puuttuu!
					$eraa_tarvitaan_viela = $isavarattu;

					$v_eranro = "";
					$v_listie = "";

					while ($sarjarow = mysql_fetch_assoc($sarjares)) {

						$v_eranro = $sarjarow['sarjanumero'];
						$v_listie = $sarjarow['lisatieto'];

						// Valmistetaan vähemmän ku erässä ois kamaa, eli osavalmistus
						if ($sarjarow["era_kpl"] > $isavarattu) {
							$eraa_tarvitaan_viela = $sarjarow["era_kpl"]-$isavarattu;

							$query = "	UPDATE sarjanumeroseuranta SET
										ostorivitunnus	= $uusrivitunnus,
										era_kpl 		= $isavarattu
										WHERE yhtio = '$kukarow[yhtio]'
										and tunnus  = '$sarjarow[tunnus]'";
							$result = pupe_query($query);

							break;
						}
						else {
							$eraa_tarvitaan_viela -= $sarjarow["era_kpl"];

							$query = "	UPDATE sarjanumeroseuranta SET
										ostorivitunnus	= $uusrivitunnus
										WHERE yhtio = '$kukarow[yhtio]'
										and tunnus  = '$sarjarow[tunnus]'";
							$result = pupe_query($query);
						}
					}

					if ($eraa_tarvitaan_viela != 0) {
						$query = "	INSERT into sarjanumeroseuranta
									SET yhtio 		 = '$kukarow[yhtio]',
									tuoteno			 = '$tilrivirow[tuoteno]',
									myyntirivitunnus = 0,
									ostorivitunnus 	 = $tilrivirow[tunnus],
									era_kpl			 = $eraa_tarvitaan_viela,
									laatija			 = '$kukarow[kuka]',
									luontiaika		 = now(),
									hyllyalue   	 = '$valmisterow[hyllyalue]',
									hyllynro    	 = '$valmisterow[hyllynro]',
									hyllyvali   	 = '$valmisterow[hyllyvali]',
									hyllytaso   	 = '$valmisterow[hyllytaso]',
									sarjanumero 	 = '$v_eranro',
									lisatieto		 = '$v_listie'";
						$result = pupe_query($query);
					}

					tulosta_tilaustuotetarrat(0, $uusrivitunnus, "OPTIMOI");
				}
			}

			//printataan pikku raporttia mitä tulikaan tehtyä
			echo "<font class='message'>".t("Valmistettiin tuotetta")." $isatuoteno $isavarattu ".t_avainsana("Y", $kieli, "and avainsana.selite='$valmisterow[yksikko]'", "", "", "selite")."</font><br>";

			$query = "	SELECT paivitys
						FROM oikeu
						WHERE yhtio	= '$kukarow[yhtio]'
						and kuka	= '$kukarow[kuka]'
						and nimi	= 'tilauskasittely/jtselaus.php'
						and alanimi = ''";
			$jtoikeudetres = pupe_query($query);

			if ((mysql_num_rows($jtoikeudetres) <> 0 and $yhtiorow["automaattinen_jt_toimitus_valmistus"] != "") or $yhtiorow["automaattinen_jt_toimitus_valmistus"] == "J") {
				$jtoikeudetrow  = mysql_fetch_assoc($jtoikeudetres);
				$jtrivit 		= array();
				$jtrivit_paikat = array();

				echo "<br>";

				//	Kohdistetaan vain oikeille asiakkaille näissä caseissa
				if ($yhtiorow['automaattinen_jt_toimitus_valmistus'] == 'S' or $yhtiorow["automaattinen_jt_toimitus_valmistus"] == "V") {
					$suoralisa = "";

					if ($yhtiorow['automaattinen_jt_toimitus_valmistus'] == 'S') {
						$suoralisa = "AND tilausrivin_lisatiedot.suoraan_laskutukseen = 'o'";
					}

					//Tutkitaan löytyykö JT-rivi joka mäppäytyy tähän valmistusriviin
				   	$query = "	SELECT ifnull(if((tilausrivi.perheid != 0 and tilausrivi.tunnus != tilausrivi.perheid), tilausrivi.perheid, tilausrivi.tunnus), 0) jtrivi
				   				FROM tilausrivin_lisatiedot
				   				JOIN tilausrivi ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
				   				WHERE tilausrivin_lisatiedot.yhtio 			 = '$kukarow[yhtio]'
				   				AND tilausrivin_lisatiedot.tilausrivilinkki  = '$tilrivirow[tunnus]'
								$suoralisa";
					$varastoon_result = pupe_query($query);

					while ($varastoon_row = mysql_fetch_assoc($varastoon_result)) {
						// Mitkä suoratoimitukset valmistettiin tällä keikalla
						$jtrivit[$varastoon_row["jtrivi"]] = $varastoon_row["jtrivi"];
						// Katotaan mille paikalle nää meni, jotta suoratoimitus voidaan laukasta tältä paikalta
						$jtrivit_paikat[$varastoon_row["jtrivi"]] = $tapahtumaid;
					}
				}

				$varastosta = array(kuuluukovarastoon($tilrivirow["hyllyalue"], $tilrivirow["hyllynro"]));

				// Toimitetaan vain valmistettua isätuotetta, ei KAIKKIA firman jälkitoimitusrivejä
				jt_toimita("", "", $varastosta, $jtrivit, $jtrivit_paikat, "tosi_automaaginen", "JATKA", "", $isatuoteno, '', "TULLAAN_VALMISTUKSESTA");

				//	Laitetaan tavarat liikkeelle jos sallitaan!
				if (($jtoikeudetrow["paivitys"] == 1 and ($yhtiorow["automaattinen_jt_toimitus_valmistus"] == "T" or $yhtiorow["automaattinen_jt_toimitus_valmistus"] == "S" or $yhtiorow["automaattinen_jt_toimitus_valmistus"] == "V")) or $yhtiorow["automaattinen_jt_toimitus_valmistus"] == "J") {
					jt_toimita("", "", "", "", "", "dummy", "TOIMITA");
				}
			}
		}
	}

	// Tehdään uusi tuote $uusiarvo on käytettyjen raaka-aineiden arvo.
	if ($useavalmiste == "ON") {

		// Katsotaan eka kuinka monta eri valmistetta syntyy
		$kaikenkaikkiaan = 1;

		// Katsotaan eka kanssa valmisteiden nykyinen varastonarvo
		if ($valmisterow["kehahin"]  > 0) {
			$kaikenkaikkiaan_arvot = $isavarattu * $valmisterow["kehahin"];
		}
		else {
			unset($kaikenkaikkiaan_arvot);
		}

		while ($uvrow = mysql_fetch_assoc($uvresult)) {
			$kaikenkaikkiaan++;

			if (isset($kaikenkaikkiaan_arvot)) {
				if ($uvrow["kehahin"] == 0) {
					// Jos yksikin valmiste on nollakehalla, niin jyvitetään $uusiarvo valmisteille vain kappalemäärien mukaan
					unset($kaikenkaikkiaan_arvot);
				}
				else {
					$kaikenkaikkiaan_arvot += ($uvrow["varattu"]*$uvrow["kehahin"]);
				}
			}
		}

		mysql_data_seek($uvresult, 0);

		// Valmistetaan ensimmäinen isä
		valmista_isa($valmisterow, $tilrivirow, $isavarattu, $isatuoteno, $uusiarvo, $kaikenkaikkiaan, $kaikenkaikkiaan_arvot);

		while ($uvrow = mysql_fetch_assoc($uvresult)) {

			// käytetään rivillä olevaa paikkaa
			$query = "	SELECT tuotepaikat.saldo, tuote.ei_saldoa, tuote.yksikko, tuote.kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
						tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
						FROM tuote
						JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
						WHERE tuote.yhtio 		  = '$kukarow[yhtio]'
						and tuote.tuoteno 		  = '$uvrow[tuoteno]'
						and tuotepaikat.hyllyalue = '$uvrow[hyllyalue]'
						and tuotepaikat.hyllynro  = '$uvrow[hyllynro]'
						and tuotepaikat.hyllytaso = '$uvrow[hyllytaso]'
						and tuotepaikat.hyllyvali = '$uvrow[hyllyvali]'";
			$tuoteresult = pupe_query($query);

			if (mysql_num_rows($tuoteresult) != 1) {
				//jos ei löytynyt yksiselitteistä riviä yritetään oletuspaikkaa
				$query = "	SELECT tuotepaikat.saldo, tuote.ei_saldoa, tuote.yksikko, tuote.kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
							tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
							FROM tuote
							JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
							WHERE tuote.yhtio 	    = '$kukarow[yhtio]'
							and tuote.tuoteno 	    = '$uvrow[tuoteno]'
							and tuotepaikat.oletus != ''
							LIMIT 1";
				$tuoteresult = pupe_query($query);
			}

			if (mysql_num_rows($tuoteresult) != 1) {
				//jos ei löytynyt yksiselitteistä riviä yritetään mitä tahansa paikkaa
				$query = "	SELECT tuotepaikat.saldo, tuote.ei_saldoa, tuote.yksikko, tuote.kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
							tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
							FROM tuote
							JOIN tuotepaikat ON (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							and tuote.tuoteno = '$uvrow[tuoteno]'
							LIMIT 1";
				$tuoteresult = pupe_query($query);
			}

			if (mysql_num_rows($tuoteresult) != 1) {
				echo "<font class='error'>$uvrow[tuoteno] ".t("Varastopaikkaa ei löydy")."</font><br>";
			}
			else {

				// Valmisteen kokonaissaldo
				$query = "	SELECT sum(saldo) kokonaissaldo
							FROM tuotepaikat
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$uvrow[tuoteno]'";
				$saldores = pupe_query($query);
				$saldorow = mysql_fetch_assoc($saldores);

				$uvisarow = mysql_fetch_assoc($tuoteresult);
				$uvisarow['kokonaissaldo'] = $saldorow['kokonaissaldo'];

				// Päivitetään sarjanumerolle ja riville löytynyt paikka
				$query = "	UPDATE tilausrivi SET
							hyllyalue = '$uvisarow[hyllyalue]',
							hyllynro  = '$uvisarow[hyllynro]',
							hyllytaso = '$uvisarow[hyllytaso]',
							hyllyvali = '$uvisarow[hyllyvali]'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$uvrow[tunnus]'";
				$lisa_res = pupe_query($query);

				if ($uvisarow['sarjanumeroseuranta'] != "") {
					$query = "	UPDATE sarjanumeroseuranta SET
								hyllyalue = '$uvisarow[hyllyalue]',
								hyllynro  = '$uvisarow[hyllynro]',
								hyllytaso = '$uvisarow[hyllytaso]',
								hyllyvali = '$uvisarow[hyllyvali]'
								WHERE yhtio 	     = '$kukarow[yhtio]'
								and myyntirivitunnus = '$uvrow[tunnus]'";
					$lisa_res = pupe_query($query);
				}

				valmista_isa($uvisarow, $uvrow, $uvrow["varattu"], $uvrow["tuoteno"], $uusiarvo, $kaikenkaikkiaan, $kaikenkaikkiaan_arvot);
			}
		}
	}
	else {
		valmista_isa($valmisterow, $tilrivirow, $isavarattu, $isatuoteno, $uusiarvo, 1, '');
	}

	echo "<br>";
}
