<?php

//v‰h‰n joutuu laitaa talteen n‰it‰ alkuper‰isi‰
$isavarattu = $atil;
$isatuoteno = $tuoteno;

if ($tee =='UV') {
	if ($atil <= 0) {
		$tee = '';
		echo "<font class='error'>".t("Anna valmistettava m‰‰r‰")."</font><br>";
	}
}

if ($tee =='UV') {

	unset($tuoteresult);

	if ($varastopaikka != '') {
		//k‰ytet‰‰n rivill‰ olevaa paikkaa
		$query = "	SELECT  saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus
					FROM tuote, tuotepaikat
					WHERE tuote.yhtio=tuotepaikat.yhtio
					and tuote.tuoteno=tuotepaikat.tuoteno
					and concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) = '$varastopaikka'
					and tuote.tuoteno = '$isatuoteno'
					and tuote.yhtio = '$kukarow[yhtio]'";
		$tuoteresult = mysql_query($query) or pupe_error($query);

		if(mysql_num_rows($tuoteresult) != 1) {
			//jos ei lˆytynyt yksiselitteist‰ rivi‰ yritet‰‰n oletuspaikkaa
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
		//k‰ytet‰‰n oletuspaikkaa
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
		echo "<font class='error'>$isatuoteno ".t("Varastopaikkaa ei lˆydy")."</font><br>";
	}
	else {
		$isarow = mysql_fetch_array($tuoteresult);
	}


	if ($valmistetaan == "TILAUKSELTA") {
		//k‰ytet‰‰n tilausriveill‰ olevia tuotteita
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
		//k‰ytet‰‰n vakioresepti‰
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
				
				//N‰in paljon haluamme k‰ytt‰‰ raaka-ainetta
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
					//k‰ytet‰‰n tilausrivill‰ olevaa paikkaa
					$query = "	SELECT  saldo, ei_saldoa, tuote.tuoteno
								FROM tuote ,tuotepaikat
								WHERE tuote.yhtio = tuotepaikat.yhtio
								and tuote.tuoteno = tuotepaikat.tuoteno
								and concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) = '$perherow[paikka]'
								and tuote.tuoteno = '$perherow[tuoteno]'
								and tuote.yhtio = '$kukarow[yhtio]'";
					$tuoteresult = mysql_query($query) or pupe_error($query);

					if(mysql_num_rows($tuoteresult) != 1) {
						//jos ei lˆytynyt yksiselitteist‰ rivi‰ yritet‰‰n oletuspaikkaa
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
					//k‰ytet‰‰n oletuspaikkaa
					$query = "	SELECT  saldo, ei_saldoa, tuote.tuoteno
								FROM tuote, tuotepaikat
								WHERE tuote.yhtio = tuotepaikat.yhtio
								and tuote.tuoteno = tuotepaikat.tuoteno
								and oletus = 'X'
								and tuote.tuoteno = '$perherow[tuoteno]'
								and tuote.yhtio = '$kukarow[yhtio]'";
					$tuoteresult = mysql_query($query) or pupe_error($query);
				}

				//katotaan kanssa, ett‰ perheenj‰senet lˆytyy kannasta ja niit‰ on riitt‰v‰sti
				if(mysql_num_rows($tuoteresult) == 1) {
					$trow = mysql_fetch_array($tuoteresult);

					if ($valmistetaan == "TILAUKSELTA") {
						if ($trow['saldo'] < $varataankpl and $vakisinhyvaksy == "") {
							$tee=''; //Raaka-aineet eiv‰t riit‰!
							echo "<font class='error'>$perherow[tuoteno] ".t("saldo")." $trow[saldo] ".t("ei riit‰ valmistukseen")." ($varataankpl)</font><br>";
						}

					}
					else {
						if ($trow['saldo'] < $varataankpl) {
							$tee=''; //Raaka-aineet eiv‰t riit‰!
							echo "<font class='error'>$perherow[tuoteno] ".t("saldo")." $trow[saldo] ".t("ei riit‰ valmistukseen")." ($varataankpl)</font><br>";
						}
					}
				}
				else {
					$tee='';
					echo "<font class='error'>$perherow[tuoteno] ".t("Varastopaikkaa ei lˆydy")."</font><br>";
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


if ($tee == 'UV') { //Tehd‰‰n tuotteet!

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
				//jos ei lˆytynyt yksiselitteist‰ rivi‰ yritet‰‰n oletuspaikkaa
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
			//K‰ytet‰‰n oletuspaikkaa
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
			//sittenh‰n tuote on saldoton
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

		///* Tehd‰‰n tapahtuma *///
		$query = "	INSERT into tapahtuma set
					yhtio    	= '$kukarow[yhtio]',
					tuoteno  	= '$perherow[tuoteno]',
					laji     	= 'kulutus',
					kpl      	=  round($kpl*-1 ,2),
					kplhinta 	= '$tuoterow[kehahin]',
					hinta    	= '$tuoterow[kehahin]',
					rivitunnus	= '$perherow[tunnus]',
					selite   	= '".t("K‰ytettiin tuotteen")." $isatuoteno ".t("valmistamiseen").".',
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

			echo "<font class='message'>".t("Varastosta k‰ytettiin tuotetta")." $perherow[tuoteno] $kpl $tuoterow[yksikko]</font><br>";

		}
		else {
			echo "<font class='message'>".t("K‰ytettiin tuotetta")." $perherow[tuoteno] $kpl $tuoterow[yksikko]</font><br>";
		}

		//p‰ivitet‰‰n tilausrivi k‰ytetyksi
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


	// Tehd‰‰n uusi tuote
	// kehahin matikka (tuotteella pit‰‰ olla saldoa ennen ja j‰lkeen, ett‰ kehahin lasketaan)
	if ($isarow['saldo'] + $isavarattu > 0 and $isarow['saldo'] > 0) {
		$kehahin = sprintf('%.4f', ($isarow['saldo'] * $isarow['kehahin'] + $uusiarvo) / ($isarow['saldo'] + $isavarattu));
	}
	else {
		$kehahin = sprintf('%.4f', $uusiarvo/$isavarattu); // jos j‰‰d‰‰n saldossa t‰m‰nkin tulon j‰lkeen miinukselle tai nollille, laitetaan kehahin suoraan t‰m‰n valmistuksen hinnasta
	}


	///* Tehd‰‰n tapahtuma *///
	$query = "	INSERT into tapahtuma set
				yhtio   	= '$kukarow[yhtio]',
				tuoteno 	= '$isatuoteno',
				rivitunnus 	= '$tilrivirow[tunnus]',
				laji    	= 'valmistus',
				kpl     	= '$isavarattu',
				kplhinta	=  round($uusiarvo/$isavarattu, 4),
				hinta   	= '$kehahin',
				selite  	= '".t("Valmistettiin tuotetta tyˆm‰‰r‰yksell‰")." $tilrivirow[otunnus] ($isarow[kehahin] --> $kehahin)',
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

	//p‰ivitet‰‰n tilausrivi k‰ytetyksi ja splitataan se
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

			//p‰ivitet‰‰n alkuper‰inen rivi
			$query = "	UPDATE tilausrivi
						SET varattu = '$jaljella'
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tilrivirow[otunnus]'
						and tunnus  = '$tilrivirow[tunnus]'
						and tyyppi in ('W','M')";
			$upparesult = mysql_query($query) or pupe_error($query);
		}
	}

	//printataan pikku raporttia mit‰ tulikaan tehty‰
	echo "<font class='message'>".t("Valmistettiin tuotetta")." $isatuoteno $isavarattu $isarow[yksikko]</font><br><br>";
}

?>