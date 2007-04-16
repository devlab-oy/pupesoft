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
						if ($trow['saldo'] < $perherow["varattu"] and $vakisinhyvaksy == "") { //nyt varasto ei riitä
							$tee=''; //Raaka-aineet eivät riitä!
							echo "<font class='error'>$perherow[tuoteno] ".t("saldo")." $trow[saldo] ".t("ei riitä valmistukseen")." ($perherow[varattu])</font><br>";
						}

					}
					else {
						if ($trow['saldo'] < ($perherow['kerroin'] * $isavarattu)) { //nyt varasto ei riitä
							$tee=''; //Raaka-aineet eivät riitä!
							echo "<font class='error'>$perherow[tuoteno] ".t("saldo")." $trow[saldo] ".t("ei riitä valmistukseen")." (" . $perherow['kerroin'] * $isavarattu . ")</font><br>";
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
	$kplr 		= 0;
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
			$kpl = $perherow['varattu'] * $akerroin * -1;
		}
		else {
			$kpl = $perherow['kerroin'] * $isavarattu * -1;
		}

		$kplr = $kpl * -1;

		if ($valmistetaan == "TILAUKSELTA") {
			$arvo = $tuoterow['kehahin'] * round($perherow['varattu'] * $akerroin / $isavarattu,8);
		}
		else {
			$arvo = $tuoterow['kehahin'] * $perherow['kerroin'];
		}

		$uusiarvo += $arvo;

		///* Tehdään tapahtuma *///
		$query = "	INSERT into tapahtuma set
					yhtio    	= '$kukarow[yhtio]',
					tuoteno  	= '$perherow[tuoteno]',
					laji     	= 'kulutus',
					kpl      	= '$kpl',
					kplhinta 	= '$tuoterow[kehahin]',
					hinta    	= '$tuoterow[kehahin]',
					rivitunnus	= '$perherow[tunnus]',
					selite   	= '".t("Käytettiin tuotteen")." $isatuoteno ".t("valmistamiseen").".',
					laatija  	= '$kukarow[kuka]',
					laadittu 	= now()";
		$result = mysql_query($query) or pupe_error($query);

		if ($tuoterow['ei_saldoa'] == "") {
			$query = "	UPDATE tuotepaikat
						SET saldo	= saldo+$kpl
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$perherow[tuoteno]'
						and tunnus  = '$tuoterow[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);

			echo "<font class='message'>".t("Varastosta käytettiin tuotetta")." $perherow[tuoteno] $kplr $tuoterow[yksikko]</font><br>";

		}
		else {
			echo "<font class='message'>".t("Käytettiin tuotetta")." $perherow[tuoteno] $kplr $tuoterow[yksikko]</font><br>";
		}

		//päivitetään tilausrivi käytetyksi
		if ($valmistetaan == "TILAUKSELTA") {
			$query = "	UPDATE tilausrivi
						SET varattu = varattu+$kpl,
						kpl=kpl-$kpl
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
		$kehahin = round(($isarow['saldo'] * $isarow['kehahin'] + $uusiarvo * $isavarattu) / ($isarow['saldo'] + $isavarattu),4);
	}
	else {
		$kehahin = round($uusiarvo,4); // jos jäädään saldossa tämänkin tulon jälkeen miinukselle tai nollille, laitetaan kehahin suoraan tämän valmistuksen hinnasta
	}


	///* Tehdään tapahtuma *///
	$query = "	INSERT into tapahtuma set
				yhtio   	= '$kukarow[yhtio]',
				tuoteno 	= '$isatuoteno',
				rivitunnus 	= '$tilrivirow[tunnus]',
				laji    	= 'valmistus',
				kpl     	= '$isavarattu',
				kplhinta	= '$uusiarvo',
				hinta   	= '$kehahin',
				selite  	= '".t("Valmistettiin tuotetta työmääräyksellä")." $tilrivirow[otunnus] ($isarow[kehahin] --> $kehahin)',
				laatija 	= '$kukarow[kuka]',
				laadittu 	= now()";
	$result = mysql_query($query) or pupe_error($query);

	$query = "	UPDATE tuote
				SET kehahin=$kehahin, vihahin=$uusiarvo, vihapvm=now()
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

			if ($laskurow["tilaustyyppi"] == "V") {
				$tyx 		= "L";
				$kpllisa 	= " kpl = '', ";
				$perheidx 	= 0;
			}
			else {
				$tyx 		= "D";
				$kpllisa 	= " kpl = '$isavarattu', ";
				$perheidx 	= $tilrivirow["perheid"];
			}

			//luodaan toimitettu valmisterivi
			$query = "	INSERT into tilausrivi set
						laatija			= '$kukarow[kuka]',
						laadittu 		= now(),
						yhtio 			= '$kukarow[yhtio]',
						tuoteno 		= '$tilrivirow[tuoteno]',
						varattu 		= '$isavarattu',
						tilkpl 			= '$isavarattu',
						$kpllisa
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
						tyyppi 			= '$tyx',
						keratty	 		= '$kukarow[kuka]',
						kerattyaika		= now(),
						toimaika 		= '$tilrivirow[toimaika]',
						var 			= '$tilrivirow[var]',
						perheid 		= '$perheidx',
						kommentti 		= '$tilrivirow[kommentti]',
						hyllyalue 		= '$tilrivirow[hyllyalue]',
						hyllynro 		= '$tilrivirow[hyllynro]',
						hyllytaso 		= '$tilrivirow[hyllytaso]',
						hyllyvali 		= '$tilrivirow[hyllyvali]'";
			$insresult = mysql_query($query) or pupe_error($query);
			$insid = mysql_insert_id();

			$valmistettavat .= ",$insid";

			$jaljella = round($tilrivirow["varattu"] - $isavarattu,2);

			//päivitetään alkuperäinen rivi
			$query = "	UPDATE tilausrivi
						SET varattu = '$jaljella'
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tilrivirow[otunnus]'
						and tunnus  = '$tilrivirow[tunnus]'
						and tyyppi  = 'W'";
			$upparesult = mysql_query($query) or pupe_error($query);

		}
	}

	//printataan pikku raporttia mitä tulikaan tehtyä
	echo "<font class='message'>".t("Valmistettiin tuotetta")." $isatuoteno $isavarattu $isarow[yksikko]</font><br><br>";
}

?>