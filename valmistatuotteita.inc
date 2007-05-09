<?php

//vähän joutuu laitaa talteen näitä alkuperäisiä
$isavarattu = $atil;
$isatuoteno = $tuoteno;

if ($tee =='UV') {
	if ($atil <= 0) {
		$tee = '';
		echo "<font class='error'>".t("Anna valmistettava määrä")."</font><br>";
	}
}

if ($tee =='UV') {

	unset($tuoteresult);

	if ($varastopaikka != '') {
		//käytetään rivillä olevaa paikkaa
		$query = "	SELECT  saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus
					FROM tuote, tuotepaikat
					WHERE tuote.yhtio=tuotepaikat.yhtio
					and tuote.tuoteno=tuotepaikat.tuoteno
					and concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) = '$varastopaikka'
					and tuote.tuoteno = '$isatuoteno'
					and tuote.yhtio = '$kukarow[yhtio]'";
		$tuoteresult = mysql_query($query) or pupe_error($query);

		if(mysql_num_rows($tuoteresult) != 1) {
			//jos ei löytynyt yksiselitteistä riviä yritetään oletuspaikkaa
			$query = "	SELECT  saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus
						FROM tuote, tuotepaikat
						WHERE tuote.yhtio=tuotepaikat.yhtio
						and tuote.tuoteno=tuotepaikat.tuoteno
						and oletus='X'
						and tuote.tuoteno = '$isatuoteno'
						and tuote.yhtio = '$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);
		}
	}
	else {
		//käytetään oletuspaikkaa
		$query = "	SELECT  saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus
					FROM tuote, tuotepaikat
					WHERE tuote.yhtio=tuotepaikat.yhtio
					and tuote.tuoteno=tuotepaikat.tuoteno
					and oletus='X'
					and tuote.tuoteno = '$isatuoteno'
					and tuote.yhtio = '$kukarow[yhtio]'";
		$tuoteresult = mysql_query($query) or pupe_error($query);
	}

	if(mysql_num_rows($tuoteresult) != 1) {
		$tee='';
		echo "<font class='error'>$isatuoteno ".t("Varastopaikkaa ei löydy")."</font><br>";
	}
	else {
		$isarow = mysql_fetch_array($tuoteresult);
	}


	if ($valmistetaan == "TILAUKSELTA") {
		//käytetään tilausriveillä olevia tuotteita
		$query = "	SELECT tilausrivi.*, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka, tuote.ei_saldoa
					FROM tilausrivi
					JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.otunnus = '$tilrivirow[otunnus]'
					and tilausrivi.perheid = '$tilrivirow[perheid]'
					and tilausrivi.tyyppi = 'V'
					ORDER by tilausrivi.tuoteno";
		$perheresult = mysql_query($query) or pupe_error($query);
	}
	else {
		//käytetään vakioreseptiä
		$query = "	SELECT tuoteperhe.*, tuote.ei_saldoa
					FROM tuoteperhe
					JOIN tuote ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.tuoteno
					WHERE tuoteperhe.isatuoteno = '$isatuoteno'
					and tuoteperhe.tyyppi = 'R'
					and tuoteperhe.yhtio = '$kukarow[yhtio]'
					ORDER by tuoteperhe.tuoteno";
		$perheresult = mysql_query($query) or pupe_error($query);
	}

	//jos tuoteperhe on olemassa
	if (mysql_num_rows($perheresult) > 0) {
		while ($perherow = mysql_fetch_array($perheresult)) {
			if ($perherow["ei_saldoa"] == "") {

				unset($tuoteresult);
				
				$varataankpl = 0;
				
				//Näin paljon haluamme käyttää raaka-ainetta
				if ($valmistetaan == "TILAUKSELTA") {
					if ($kulukpllat[$perherow["tunnus"]] != 0) {
						$varataankpl = $kulukpllat[$perherow["tunnus"]];
					}
					else {
						$varataankpl = $perherow['varattu'] * $akerroin;
					}					
				}
				else {
					$varataankpl = $perherow['kerroin'] * $isavarattu;
				}
								
				if ($perherow["paikka"] != '') {
					//käytetään tilausrivillä olevaa paikkaa
					$query = "	SELECT  saldo, ei_saldoa, tuote.tuoteno
								FROM tuote ,tuotepaikat
								WHERE tuote.yhtio = tuotepaikat.yhtio
								and tuote.tuoteno = tuotepaikat.tuoteno
								and concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) = '$perherow[paikka]'
								and tuote.tuoteno = '$perherow[tuoteno]'
								and tuote.yhtio = '$kukarow[yhtio]'";
					$tuoteresult = mysql_query($query) or pupe_error($query);

					if(mysql_num_rows($tuoteresult) != 1) {
						//jos ei löytynyt yksiselitteistä riviä yritetään oletuspaikkaa
						$query = "	SELECT  saldo, ei_saldoa, tuote.tuoteno
									FROM tuote, tuotepaikat
									WHERE tuote.yhtio = tuotepaikat.yhtio
									and tuote.tuoteno = tuotepaikat.tuoteno
									and oletus = 'X'
									and tuote.tuoteno = '$perherow[tuoteno]'
									and tuote.yhtio = '$kukarow[yhtio]'";
						$tuoteresult = mysql_query($query) or pupe_error($query);
					}
				}
				else {
					//käytetään oletuspaikkaa
					$query = "	SELECT  saldo, ei_saldoa, tuote.tuoteno
								FROM tuote, tuotepaikat
								WHERE tuote.yhtio = tuotepaikat.yhtio
								and tuote.tuoteno = tuotepaikat.tuoteno
								and oletus = 'X'
								and tuote.tuoteno = '$perherow[tuoteno]'
								and tuote.yhtio = '$kukarow[yhtio]'";
					$tuoteresult = mysql_query($query) or pupe_error($query);
				}

				//katotaan kanssa, että perheenjäsenet löytyy kannasta ja niitä on riittävästi
				if(mysql_num_rows($tuoteresult) == 1) {
					$trow = mysql_fetch_array($tuoteresult);

					if ($valmistetaan == "TILAUKSELTA") {
						if ($trow['saldo'] < $varataankpl and $vakisinhyvaksy == "") {
							$tee=''; //Raaka-aineet eivät riitä!
							echo "<font class='error'>$perherow[tuoteno] ".t("saldo")." $trow[saldo] ".t("ei riitä valmistukseen")." ($varataankpl)</font><br>";
						}

					}
					else {
						if ($trow['saldo'] < $varataankpl) {
							$tee=''; //Raaka-aineet eivät riitä!
							echo "<font class='error'>$perherow[tuoteno] ".t("saldo")." $trow[saldo] ".t("ei riitä valmistukseen")." ($varataankpl)</font><br>";
						}
					}
				}
				else {
					$tee='';
					echo "<font class='error'>$perherow[tuoteno] ".t("Varastopaikkaa ei löydy")."</font><br>";
				}
			}
		}
	}
	else {
		$tee='';
		echo "<font class='error'>$tuoteno ".t("ei ole rakenne/resepti")."</font>";
	}
	echo "<br><br>";
}


if ($tee == 'UV') { //Tehdään tuotteet!

	mysql_data_seek($perheresult,0);

	$uusiarvo 	= 0;
	$kpl 		= 0;

	while ($perherow = mysql_fetch_array($perheresult)) {

		unset($tuoteresult);

		if ($perherow["paikka"] != '') {
			$query = "	SELECT saldo, kehahin, ei_saldoa, yksikko, tuotepaikat.tunnus tunnus
						FROM tuote, tuotepaikat
						WHERE tuote.yhtio = tuotepaikat.yhtio
						and tuote.tuoteno = tuotepaikat.tuoteno
						and concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) = '$perherow[paikka]'
						and tuote.tuoteno = '$perherow[tuoteno]'
						and tuote.yhtio = '$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($tuoteresult) != 1) {
				//jos ei löytynyt yksiselitteistä riviä yritetään oletuspaikkaa
				$query = "	SELECT saldo, kehahin, ei_saldoa, yksikko, tuotepaikat.tunnus tunnus
							FROM tuote, tuotepaikat
							WHERE tuote.yhtio = tuotepaikat.yhtio
							and tuote.tuoteno = tuotepaikat.tuoteno
							and oletus = 'X'
							and tuote.tuoteno = '$perherow[tuoteno]'
							and tuote.yhtio = '$kukarow[yhtio]'";
				$tuoteresult = mysql_query($query) or pupe_error($query);
			}
		}
		else {
			//Käytetään oletuspaikkaa
			$query = "	SELECT saldo, kehahin, ei_saldoa, yksikko, tuotepaikat.tunnus tunnus
						FROM tuote, tuotepaikat
						WHERE tuote.yhtio = tuotepaikat.yhtio
						and tuote.tuoteno = tuotepaikat.tuoteno
						and oletus = 'X'
						and tuote.tuoteno = '$perherow[tuoteno]'
						and tuote.yhtio = '$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);
		}

		if(mysql_num_rows($tuoteresult) == 0) {
			//sittenhän tuote on saldoton
			$query = "	SELECT kehahin, ei_saldoa, yksikko
						FROM tuote
						WHERE tuote.tuoteno = '$perherow[tuoteno]'
						and tuote.yhtio = '$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);
		}

		$tuoterow = mysql_fetch_array($tuoteresult);

		//Poistetaan raaka-aineet varastosta
		if ($valmistetaan == "TILAUKSELTA") {
			if ($kulukpllat[$perherow["tunnus"]] != 0) {
				$kpl = $kulukpllat[$perherow["tunnus"]];
			}
			else {
				$kpl = $perherow['varattu'] * $akerroin;
			}					
		}
		else {
			$kpl = $perherow['kerroin'] * $isavarattu;
		}
		
		$arvo = $tuoterow['kehahin'] * $kpl;
		$uusiarvo += $arvo;

		///* Tehdään tapahtuma *///
		$query = "	INSERT into tapahtuma set
					yhtio    	= '$kukarow[yhtio]',
					tuoteno  	= '$perherow[tuoteno]',
					laji     	= 'kulutus',
					kpl      	=  round($kpl*-1 ,2),
					kplhinta 	= '$tuoterow[kehahin]',
					hinta    	= '$tuoterow[kehahin]',
					rivitunnus	= '$perherow[tunnus]',
					selite   	= '".t("Käytettiin tuotteen")." $isatuoteno ".t("valmistamiseen")." ".t("työmääräyksellä")." $tilrivirow[otunnus]',
					laatija  	= '$kukarow[kuka]',
					laadittu 	= now()";
		$result = mysql_query($query) or pupe_error($query);

		if ($tuoterow['ei_saldoa'] == "") {
			$query = "	UPDATE tuotepaikat
						SET saldo	= round(saldo-$kpl,2)
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$perherow[tuoteno]'
						and tunnus  = '$tuoterow[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);

			echo "<font class='message'>".t("Varastosta käytettiin tuotetta")." $perherow[tuoteno] $kpl $tuoterow[yksikko]</font><br>";

		}
		else {
			echo "<font class='message'>".t("Käytettiin tuotetta")." $perherow[tuoteno] $kpl $tuoterow[yksikko]</font><br>";
		}

		//päivitetään tilausrivi käytetyksi
		if ($valmistetaan == "TILAUKSELTA") {
			$query = "	UPDATE tilausrivi
						SET varattu = round(varattu-$kpl,2),
						kpl			= round(kpl+$kpl,2)
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tilrivirow[otunnus]'
						and tunnus  = '$perherow[tunnus]'
						and tyyppi  = 'V'";
			$upparesult = mysql_query($query) or pupe_error($query);
		}
	}


	// Tehdään uusi tuote
	// kehahin matikka (tuotteella pitää olla saldoa ennen ja jälkeen, että kehahin lasketaan)
	if ($isarow['saldo'] + $isavarattu > 0 and $isarow['saldo'] > 0) {
		$kehahin = sprintf('%.4f', ($isarow['saldo'] * $isarow['kehahin'] + $uusiarvo) / ($isarow['saldo'] + $isavarattu));
	}
	else {
		$kehahin = sprintf('%.4f', $uusiarvo/$isavarattu); // jos jäädään saldossa tämänkin tulon jälkeen miinukselle tai nollille, laitetaan kehahin suoraan tämän valmistuksen hinnasta
	}


	///* Tehdään tapahtuma *///
	$query = "	INSERT into tapahtuma set
				yhtio   	= '$kukarow[yhtio]',
				tuoteno 	= '$isatuoteno',
				rivitunnus 	= '$tilrivirow[tunnus]',
				laji    	= 'valmistus',
				kpl     	= '$isavarattu',
				kplhinta	=  round($uusiarvo/$isavarattu, 4),
				hinta   	= '$kehahin',
				selite  	= '".t("Valmistettiin tuotetta työmääräyksellä")." $tilrivirow[otunnus] ($isarow[kehahin] --> $kehahin)',
				laatija 	= '$kukarow[kuka]',
				laadittu 	= now()";
	$result = mysql_query($query) or pupe_error($query);

	$query = "	UPDATE tuote
				SET kehahin=$kehahin, vihahin=round($uusiarvo/$isavarattu, 2), vihapvm=now()
				WHERE yhtio='$kukarow[yhtio]'
				and tuoteno='$isatuoteno'";
	$result = mysql_query($query) or pupe_error($query);

	$query = "	UPDATE tuotepaikat
				SET saldo=saldo+$isavarattu
				WHERE yhtio = '$kukarow[yhtio]'
				and tuoteno = '$isatuoteno'
				and tunnus  = '$isarow[tunnus]'";
	$result = mysql_query($query) or pupe_error($query);

	//päivitetään tilausrivi käytetyksi ja splitataan se
	if ($valmistetaan == "TILAUKSELTA") {
		//osavalmistus tai kokovalmistus
		if ($isavarattu <= $tilrivirow["varattu"]) {
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
							ale 			= '$tilrivirow[ale]',
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
				$insresult = mysql_query($query) or pupe_error($query);
				$valmistettavat .= ",".mysql_insert_id();
			}
			
			//luodaan toimitettu valmisterivi
			$query = "	INSERT into tilausrivi set
						laatija			= '$kukarow[kuka]',
						laadittu 		= now(),
						yhtio 			= '$kukarow[yhtio]',
						tuoteno 		= '$tilrivirow[tuoteno]',
						varattu 		= '$isavarattu',
						tilkpl 			= '$isavarattu',
						kpl 			= '$isavarattu',
						ale 			= '$tilrivirow[ale]',
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
						tyyppi 			= 'D',
						keratty	 		= '$kukarow[kuka]',
						kerattyaika		= now(),
						toimaika 		= '$tilrivirow[toimaika]',
						var 			= '$tilrivirow[var]',
						perheid 		= '$tilrivirow[perheid]',
						kommentti 		= '$tilrivirow[kommentti]',
						hyllyalue 		= '$tilrivirow[hyllyalue]',
						hyllynro 		= '$tilrivirow[hyllynro]',
						hyllytaso 		= '$tilrivirow[hyllytaso]',
						hyllyvali 		= '$tilrivirow[hyllyvali]'";
			$insresult = mysql_query($query) or pupe_error($query);			
			$valmistettavat .= ",".mysql_insert_id();

			$jaljella = round($tilrivirow["varattu"] - $isavarattu,2);

			//päivitetään alkuperäinen rivi
			$query = "	UPDATE tilausrivi
						SET varattu = '$jaljella'
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tilrivirow[otunnus]'
						and tunnus  = '$tilrivirow[tunnus]'
						and tyyppi in ('W','M')";
			$upparesult = mysql_query($query) or pupe_error($query);
		}
	}

	//printataan pikku raporttia mitä tulikaan tehtyä
	echo "<font class='message'>".t("Valmistettiin tuotetta")." $isatuoteno $isavarattu $isarow[yksikko]</font><br><br>";
}

?>