<?php

if(file_exists("tulosta_tilaustuotetarrat.inc")) {
	require_once("tulosta_tilaustuotetarrat.inc");
}
else {
	require_once("tilauskasittely/tulosta_tilaustuotetarrat.inc");
}


//vähän joutuu laitaa talteen näitä alkuperäisiä
$isavarattu = $atil;
$isatuoteno = $tuoteno;

if ($tee == 'UV') {
	if ($atil <= 0) {
		$tee = '';
		echo "<font class='error'>".t("Anna valmistettava määrä")."</font><br>";
	}
}

// Katsotaan onko useita valmisteita?
if ($tee == 'UV') {
	$query = "	SELECT tilausrivi.*, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka, tuote.ei_saldoa
				FROM tilausrivi
				JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.otunnus = '$tilrivirow[otunnus]'
				and tilausrivi.perheid = '$tilrivirow[perheid]'
				and tilausrivi.tunnus != '$tilrivirow[perheid]'
				and tilausrivi.tyyppi in ('W','M')
				and tilausrivi.toimitettuaika = '0000-00-00 00:00:00'
				ORDER by tilausrivi.tuoteno";
	$uvresult = mysql_query($query) or pupe_error($query);

	if(mysql_num_rows($uvresult) > 0) {
		$useavalmiste = "ON";
	}
}

if ($tee == 'UV') {

	unset($tuoteresult);

	if ($varastopaikka != '') {
		//käytetään rivillä olevaa paikkaa
		$query = "	SELECT  saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
					tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
					FROM tuote, tuotepaikat
					WHERE tuote.yhtio=tuotepaikat.yhtio
					and tuote.tuoteno=tuotepaikat.tuoteno
					and concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) = '$varastopaikka'
					and tuote.tuoteno = '$isatuoteno'
					and tuote.yhtio = '$kukarow[yhtio]'";
		$tuoteresult = mysql_query($query) or pupe_error($query);

		if(mysql_num_rows($tuoteresult) != 1) {
			//jos ei löytynyt yksiselitteistä riviä yritetään oletuspaikkaa
			$query = "	SELECT  saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
						tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
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
		$query = "	SELECT  saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
					tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
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
		$query = "	SELECT tilausrivi.*, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka, tuote.ei_saldoa, tuote.sarjanumeroseuranta
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
						if ($trow['saldo'] < $varataankpl and $vakisinhyvaksy == "") {
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
			$query = "	SELECT saldo, kehahin, ei_saldoa, yksikko, tuotepaikat.tunnus tunnus,
						tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm,
						tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
						FROM tuote, tuotepaikat
						WHERE tuote.yhtio = tuotepaikat.yhtio
						and tuote.tuoteno = tuotepaikat.tuoteno
						and concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) = '$perherow[paikka]'
						and tuote.tuoteno = '$perherow[tuoteno]'
						and tuote.yhtio = '$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($tuoteresult) != 1) {
				//jos ei löytynyt yksiselitteistä riviä yritetään oletuspaikkaa
				$query = "	SELECT saldo, kehahin, ei_saldoa, yksikko, tuotepaikat.tunnus tunnus,
							tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm,
							tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
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
			$query = "	SELECT saldo, kehahin, ei_saldoa, yksikko, tuotepaikat.tunnus tunnus,
						tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm,
						tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
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
			$query = "	SELECT tuote.kehahin, tuote.ei_saldoa, tuote.yksikko,
						tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm
						FROM tuote
						WHERE tuote.tuoteno = '$perherow[tuoteno]'
						and tuote.yhtio = '$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);
		}

		$tuoterow = mysql_fetch_array($tuoteresult);

		//Poistetaan raaka-aineet varastosta
		if ($valmistetaan == "TILAUKSELTA") {
			if ($kulukpllat[$perherow["tunnus"]] != 0) {
				$kpl = str_replace(',','.',$kulukpllat[$perherow["tunnus"]]);
			}
			else {
				$kpl = $perherow['varattu'] * str_replace(',','.',$akerroin);
			}
		}
		else {
			$kpl = $perherow['kerroin'] * str_replace(',','.',$isavarattu);
		}

		$kpl = str_replace(',','.',$kpl);

		if 		($tuoterow['epakurantti100pvm'] != '0000-00-00') $tuoterow['kehahin'] = 0;
		elseif 	($tuoterow['epakurantti75pvm'] != '0000-00-00')  $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.25, 6);
		elseif 	($tuoterow['epakurantti50pvm'] != '0000-00-00')  $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.5,  6);
		elseif 	($tuoterow['epakurantti25pvm'] != '0000-00-00')  $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.75, 6);
		
		if($perherow["sarjanumeroseuranta"] == "G") {
			$arvo = sarjanumeron_ostohinta("myyntirivitunnus", $perherow["tunnus"])*$kpl;
		}
		else {
			$arvo = $tuoterow['kehahin'] * $kpl;
		}
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
					hyllyalue 	= '$tuoterow[hyllyalue]',
					hyllynro 	= '$tuoterow[hyllynro]',
					hyllytaso 	= '$tuoterow[hyllytaso]',
					hyllyvali 	= '$tuoterow[hyllyvali]',
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
			
			//	Merkataan sarjanumero kulutetuksi
			if($perherow["sarjanumeroseuranta"] == "E" or $perherow["sarjanumeroseuranta"] == "F" or $perherow["sarjanumeroseuranta"] == "G") {
				
				// Haetaan eka erätiedot
				$query = "	SELECT ostorivitunnus, myyntirivitunnus, sarjanumero
							FROM sarjanumeroseuranta
							WHERE yhtio			 = '$kukarow[yhtio]'
							and tuoteno			 = '$perherow[tuoteno]'
							and myyntirivitunnus = '$perherow[tunnus]'
							LIMIT 1";
				$lisa_res = mysql_query($query) or pupe_error($query);
				$lisa_row = mysql_fetch_array($lisa_res);

				if ($lisa_row["ostorivitunnus"] != $lisa_row["myyntirivitunnus"]) {
					// Normikeissi jossa erä on jo tuloutettu

					// luodaan muuttuja jota pienennetään luupissa
					$eraayht=$perherow["varattu"];

					// luupataan niin kauan kuin tarvitaan jotta kaikki varatut on hanskattu
					while ($eraayht > 0) {

						// koitetaan löytää vapaita ostettuja eriä mitä myydä
						$query =   "SELECT era_kpl, tunnus, ostorivitunnus
									FROM sarjanumeroseuranta
									WHERE yhtio 			= '$kukarow[yhtio]'
									and tuoteno				= '$perherow[tuoteno]'
									and ostorivitunnus 		> 0
									and myyntirivitunnus 	= 0
									and sarjanumero 		= '$lisa_row[sarjanumero]'
									and era_kpl 			> 0
									and hyllyalue   		= '$tuoterow[hyllyalue]'
									and hyllynro    		= '$tuoterow[hyllynro]'
									and hyllyvali   		= '$tuoterow[hyllyvali]'
									and hyllytaso   		= '$tuoterow[hyllytaso]'
									ORDER BY era_kpl DESC, tunnus 
									LIMIT 1";
						$erajaljella_res = mysql_query($query) or pupe_error($query);

						// jos löytyy ostettuja eriä myytäväks niin mennään tänne
						if (mysql_num_rows($erajaljella_res) == 1) {
							$erajaljella_row = mysql_fetch_array($erajaljella_res);

							// luodaan muuttuja jossa annetaan kyselyille määrämuutoksen
							$eravahennetaan = 0;

							// jos löydetyn erän määrä riittää koko tarvittavaan määrään niin pannaan kaikki haisemaan
							if ($erajaljella_row["era_kpl"] >= $eraayht) {
								$eravahennetaan = $eraayht;
								$lisataanrivi = "";
							}
							// muuten vaan löydetyn erän verran
							else {
								$eravahennetaan = $erajaljella_row["era_kpl"];
								$lisataanrivi = "KYLLAKIITOS";
							}

							// vähennetään ostoriviltä tarvittava määrä
							$query = "	UPDATE sarjanumeroseuranta
										SET era_kpl 			= era_kpl-$eravahennetaan,
										muutospvm				= now(),
										muuttaja				= '$kukarow[kuka]'
										WHERE yhtio 			= '$kukarow[yhtio]'
										and tuoteno				= '$trow[tuoteno]'
										and ostorivitunnus 		= '$erajaljella_row[ostorivitunnus]'
										and myyntirivitunnus 	= 0
										and sarjanumero 		= '$lisa_row[sarjanumero]'
										and tunnus				= '$erajaljella_row[tunnus]'
										LIMIT 1";
							$lisa_res = mysql_query($query) or pupe_error($query);

							// haetaan päivitettävä myyntirivi jotta voidaan kloonata se myöhemmin tarvittaessa
							$query = 	"SELECT * FROM sarjanumeroseuranta
										WHERE yhtio			 = '$kukarow[yhtio]'
										and tuoteno			 = '$tuoterow[tuoteno]'
										and myyntirivitunnus = '$tuoterow[tunnus]'
										and era_kpl			 = 0
										LIMIT 1";
							$erahakures = mysql_query($query) or pupe_error($query);
							$erahakurow = mysql_fetch_array($erahakures);						

							// lisätään myyntiriville sama määrä, ja samalla päivitetään ostorivitunnus oikeaksi
							$query = "	UPDATE sarjanumeroseuranta
										SET era_kpl			 = era_kpl+$eravahennetaan,
										ostorivitunnus		 = '$erajaljella_row[ostorivitunnus]',
										muutospvm			 = now(),
										muuttaja			 = '$kukarow[kuka]'
										WHERE yhtio			 = '$kukarow[yhtio]'
										and tunnus 			 = '$erahakurow[tunnus]'
										LIMIT 1";
							$lisa_res = mysql_query($query) or pupe_error($query);

							// lisätään uusi kloonattu rivi
							if ($lisataanrivi != '') {
								$query = "INSERT INTO sarjanumeroseuranta values (";
								for ($ikentta=0; $ikentta < mysql_num_fields($erahakures)-1; $ikentta++) {
									if (mysql_field_name($erahakures,$ikentta) == 'laatija') {
										$query .= "'".$kukarow["kuka"]."',";
									}
									elseif (mysql_field_name($erahakures,$ikentta) == 'luontiaika') {
										$query .= "now(),";
									}
									else {
										$query .= "'".$erahakurow[$ikentta]."',";
									}	
								}
								$query .= "0)";

								$lisa_res = mysql_query($query) or pupe_error($query);
							}

							// vähennetään luupattavaa määrää 
							$eraayht -= $eravahennetaan;
						}
						// Ei löytynyt (enään) ostettuja eriä, eli "myyty pakkaselle" niin luodaan uusi erärivi jossa ostorivitunnus on nolla jonne laitetaan loppumäärä.
						else {

							$query = "	INSERT into sarjanumeroseuranta
										SET yhtio 		= '$kukarow[yhtio]',
										tuoteno			= '$tuoterow[tuoteno]',
										myyntirivitunnus= 0,
										ostorivitunnus 	= '$trow[tunnus]',
										era_kpl			= '0',
										laatija			= '$kukarow[kuka]',
										luontiaika		= now(),
										hyllyalue   	= '$tuoterow[hyllyalue]',
										hyllynro    	= '$tuoterow[hyllynro]',
										hyllyvali   	= '$tuoterow[hyllyvali]',
										hyllytaso   	= '$tuoterow[hyllytaso]',
										sarjanumero 	= '$lisa_row[sarjanumero]'";
							$lisa_res = mysql_query($query) or pupe_error($query);

							$query = "	UPDATE sarjanumeroseuranta
										SET era_kpl			 = $eraayht,
										ostorivitunnus		 = '$tuoterow[tunnus]',
										muutospvm			 = now(),
										muuttaja			 = '$kukarow[kuka]'
										WHERE yhtio			 = '$kukarow[yhtio]'
										and tuoteno			 = '$tuoterow[tuoteno]'
										and myyntirivitunnus = '$tuoterow[tunnus]'
										and era_kpl			 = 0
										LIMIT 1";
							$lisa_res = mysql_query($query) or pupe_error($query);

							// vähennetään luupattavaa määrää 
							$eraayht = 0;

						}
					}
				}
				else {
					// Laskutetaan vaikka ei olla vielä ostettu
					$query = "	UPDATE sarjanumeroseuranta
								SET era_kpl = $perherow[varattu]*-1,
								ostorivitunnus = 0
								WHERE yhtio 			= '$kukarow[yhtio]'
								and tuoteno				= '$tuoterow[tuoteno]'
								and ostorivitunnus 		= '$tuoterow[tunnus]'
								and myyntirivitunnus 	= 0
								and sarjanumero			= '$lisa_row[sarjanumero]'
								LIMIT 1";
					$lisa_res = mysql_query($query) or pupe_error($query);

					$query = "	UPDATE sarjanumeroseuranta
								SET era_kpl = $perherow[varattu]
								WHERE yhtio			 = '$kukarow[yhtio]'
								and tuoteno			 = '$tuoterow[tuoteno]'
								and myyntirivitunnus = '$tuoterow[tunnus]'
								LIMIT 1";
					$lisa_res = mysql_query($query) or pupe_error($query);
				}
			}
			
			echo "<font class='message'>".t("Varastosta käytettiin tuotetta")." $perherow[tuoteno] $kpl ".ta($kieli, "Y", $tuoterow["yksikko"])."</font><br>";

		}
		else {
			echo "<font class='message'>".t("Käytettiin tuotetta")." $perherow[tuoteno] $kpl ".ta($kieli, "Y", $tuoterow["yksikko"])."</font><br>";
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

	if (!function_exists("valmista_isa")) {
		function valmista_isa($isarow, $tilrivirow, $isavarattu, $isatuoteno, $uusiarvo, $kaikenkaikkiaan) {
			global $yhtiorow, $kukarow, $laskurow, $valmistettavat, $valmistetaan, $jaljella_tot;

			if ((float) $kaikenkaikkiaan != (float) $isavarattu) {
				$uusiarvo = round($uusiarvo * ($isavarattu/$kaikenkaikkiaan), 6);
			}
			
			if($isarow["sarjanumeroseuranta"] == "S" or $isarow["sarjanumeroseuranta"] == "U" or $isarow["sarjanumeroseuranta"] == "G") {
				$kehahin = 0;
				$selite = "".t("Valmistettiin tuotetta työmääräyksellä")." $tilrivirow[otunnus] ($uusiarvo)";
			}
			else {
				if ($isarow['saldo'] + $isavarattu > 0 and $isarow['saldo'] > 0) {
					// kehahin matikka (tuotteella pitää olla saldoa ennen ja jälkeen, että kehahin lasketaan)
					$kehahin = sprintf('%.4f', ($isarow['saldo'] * $isarow['kehahin'] + $uusiarvo) / ($isarow['saldo'] + $isavarattu));
				}
				else {
					// jos jäädään saldossa tämänkin tulon jälkeen miinukselle tai nollille, laitetaan kehahin suoraan tämän valmistuksen hinnasta
					$kehahin = sprintf('%.4f', $uusiarvo/$isavarattu);
				}
				
				$selite = "".t("Valmistettiin tuotetta työmääräyksellä")." $tilrivirow[otunnus] ($isarow[kehahin] --> $kehahin)";
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
						hyllyalue 	= '$isarow[hyllyalue]',
						hyllynro 	= '$isarow[hyllynro]',
						hyllytaso 	= '$isarow[hyllytaso]',
						hyllyvali 	= '$isarow[hyllyvali]',
						selite  	= '$selite',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now()";
			$result = mysql_query($query) or pupe_error($query);

				$query = "	UPDATE tuote
							SET kehahin	= $kehahin,
							vihahin		= round($uusiarvo/$isavarattu, 2),
							vihapvm		= now()
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$isatuoteno'";
				$result = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE tuotepaikat
						SET saldo = saldo+$isavarattu
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
					
					//	Kaikille sarjanumeroille tehdään varastoon saavutettu O-rivi, muuten mm keräys ei toimi ja ehkä se on myös loogisempaa?
					if($isarow["sarjanumeroseuranta"] != "") {
						$tilausriviTyyppi = "O";
						$laskutettuLisa = ", laskutettu	 	= '$kukarow[kuka]', laskutettuaika	= now()";
					}
					else {
						$tilausriviTyyppi = "D";
						$laskutettuLisa = "";
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
								rivihinta		= '$uusiarvo',
								kerayspvm 		= '$tilrivirow[kerayspvm]',
								nimitys 		= '$tilrivirow[nimitys]',
								otunnus 		= '$tilrivirow[otunnus]',
								uusiotunnus		= '$tilrivirow[uusiotunnus]',
								tyyppi 			= 'O',
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
					$insresult = mysql_query($query) or pupe_error($query);
					$uusrivitunnus = mysql_insert_id();
					$valmistettavat .= ",".$uusrivitunnus;

					$jaljella = round($tilrivirow["varattu"] - $isavarattu, 2);
					$jaljella_tot += $jaljella;

					//päivitetään alkuperäinen rivi
					$query = "	UPDATE tilausrivi
								SET varattu = '$jaljella'
								WHERE yhtio = '$kukarow[yhtio]'
								and otunnus = '$tilrivirow[otunnus]'
								and tunnus  = '$tilrivirow[tunnus]'
								and tyyppi in ('W','M')";
					$upparesult = mysql_query($query) or pupe_error($query);
					
					//	Siirretään sarjanumerot valmistetulle tilausriville
					
					if(in_array($isarow["sarjanumeroseuranta"], array("S", "U"))) {
						//	Päivitetään valmistetulle riville laskutettuaika, jotta se näkyy kaikkialla oikein
						$query = "	UPDATE tilausrivi SET
										laskutettuaika = now(),
										laskutettu = '$kukarow[kuka]'
									WHERE yhtio = '$kukarow[yhtio]'
									and otunnus = '$tilrivirow[otunnus]'
									and tunnus  = '$uusrivitunnus'
									and tyyppi = 'D'";
						$upparesult = mysql_query($query) or pupe_error($query);
						
						//	Siirretään ostorivin tunnus valmistetulle riville
						$query = "	SELECT tunnus
									FROM sarjanumeroseuranta
									WHERE yhtio='$kukarow[yhtio]' and ostorivitunnus='$tilrivirow[tunnus]'
									LIMIT ".(int) $isavarattu."";
						$sarjares = mysql_query($query) or pupe_error($query);
						if(mysql_num_rows($sarjares) > 0) {
							while($sarjarow = mysql_fetch_array($sarjares)) {
								$query = "	UPDATE sarjanumeroseuranta SET
											ostorivitunnus	= $uusrivitunnus
											WHERE yhtio = '$kukarow[yhtio]'
											and tunnus = '$sarjarow[tunnus]'";
								$result = mysql_query($query) or pupe_error($query);
							}
						}
						
						
						//	Generoidaan loput jos jotain puuttuu!
						$puuttuva_sarjanumerot = $isavarattu - mysql_num_rows($sarjares);
						if($puuttuva_sarjanumerot>0) {
							
							for($i=1;$i<=$puuttuva_sarjanumerot;$i++) {
								//	Arvotaan uniikki sarjanumero
								$query = "	SELECT count(*) kpl
											FROM sarjanumeroseuranta
											WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '{$tilrivirow["tuoteno"]}'";
								$sarjares = mysql_query($query) or pupe_error($query);
								$sarjarow = mysql_fetch_array($sarjares);
								$sarja = $sarjarow["kpl"]+1;
								
								// varmistetaan, että tämä on uniikki!
								$onUniikki = false;
								$x=0;
								while($onUniikki === false) {
									$sarjanro = "{$tilrivirow["tuoteno"]}-$sarja";
									$query = "	SELECT tunnus
												FROM sarjanumeroseuranta
												WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '{$tilrivirow["tuoteno"]}' and sarjanumero = '$sarjanro'";
									$sarjares = mysql_query($query) or pupe_error($query);
									if(mysql_num_rows($sarjares) == 0) {
										$onUniikki = true;
									}
									
									if($x > 50) {
										echo "<font class='error'>".t("VIRHE: Uniikkia sarjanumeroa ei meinaa löytyä! Ota yhteys ylläpitoon.")."</font><br>";
										die();
									}

									$x++;
								}
								
								$query = "	INSERT INTO sarjanumeroseuranta
											(yhtio, tuoteno, sarjanumero, ostorivitunnus, myyntirivitunnus, laatija, luontiaika, hyllyalue, hyllynro, hyllyvali, hyllytaso)
											VALUES ('{$kukarow["yhtio"]}', '{$tilrivirow["tuoteno"]}', '$sarjanro', '$uusrivitunnus', 0, '{$kuka["kuka"]}', now(), '{$tilrivirow["hyllyalue"]}', '{$tilrivirow["hyllynro"]}', '{$tilrivirow["hyllyvali"]}', '{$tilrivirow["hyllytaso"]}')";
								$sarjares1 = mysql_query($query) or pupe_error($query);
								$uusisarjatunnus = mysql_insert_id();
								
								//	Päivitetään sarjanumerolle lisätiedot jos meillä on ne käytössä
								if(table_exists("sarjanumeron_lisatiedot") and file_exists("generoi_sarjanumeron_lisatiedot.inc")) {
									require("generoi_sarjanumeron_lisatiedot.inc");
								}
							}
						}

						tulosta_tilaustuotetarrat(0, $uusrivitunnus, "OPTIMOI");
					}
					elseif(in_array($isarow["sarjanumeroseuranta"], array("E", "F", "G"))) {
						//	Päivitetään valmistetulle riville laskutettuaika, jotta se näkyy kaikkialla oikein
						$query = "	UPDATE tilausrivi SET
										laskutettuaika = now(),
										laskutettu = '$kukarow[kuka]'
									WHERE yhtio = '$kukarow[yhtio]'
									and otunnus = '$tilrivirow[otunnus]'
									and tunnus  = '$uusrivitunnus'
									and tyyppi = 'D'";
						$upparesult = mysql_query($query) or pupe_error($query);
						
						//	Arvotaan uniikki sarjanumero
						$query = "	SELECT count(*) kpl
									FROM sarjanumeroseuranta
									WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '{$tilrivirow["tuoteno"]}'";
						$sarjares = mysql_query($query) or pupe_error($query);
						$sarjarow = mysql_fetch_array($sarjares);
						$sarja = $sarjarow["kpl"]+1;
						
						// varmistetaan, että tämä on uniikki!
						$onUniikki = false;
						$x=0;
						while($onUniikki === false) {
							$sarjanro = "{$tilrivirow["tuoteno"]}-$sarja";
							$query = "	SELECT tunnus
										FROM sarjanumeroseuranta
										WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '{$tilrivirow["tuoteno"]}' and sarjanumero = '$sarjanro'";
							$sarjares = mysql_query($query) or pupe_error($query);
							if(mysql_num_rows($sarjares) == 0) {
								$onUniikki = true;
							}
							
							if($x > 50) {
								echo "<font class='error'>".t("VIRHE: Uniikkia sarjanumeroa ei meinaa löytyä! Ota yhteys ylläpitoon.")."</font><br>";
								die();
							}

							$x++;
						}
						
						//	Generoidaan eränumerot! näitä ei siis edes pitäisi voida tehdä  mistään käyttöliittymästä!
						$query = "	INSERT INTO sarjanumeroseuranta
									(yhtio, tuoteno, sarjanumero, ostorivitunnus, myyntirivitunnus, era_kpl, laatija, luontiaika, hyllyalue, hyllynro, hyllyvali, hyllytaso)
									VALUES ('{$kukarow["yhtio"]}', '{$tilrivirow["tuoteno"]}', '$sarjanro', '$uusrivitunnus', 0, '$isavarattu', '{$kuka["kuka"]}', now(), '{$tilrivirow["hyllyalue"]}', '{$tilrivirow["hyllynro"]}', '{$tilrivirow["hyllyvali"]}', '{$tilrivirow["hyllytaso"]}')";
						$sarjares1 = mysql_query($query) or pupe_error($query);
						$uusisarjatunnus = mysql_insert_id();

						//	Päivitetään sarjanumerolle lisätiedot jos meillä on ne käytössä
						if(table_exists("sarjanumeron_lisatiedot") and file_exists("generoi_sarjanumeron_lisatiedot.inc")) {
							require("generoi_sarjanumeron_lisatiedot.inc");
						}
						tulosta_tilaustuotetarrat(0, $uusrivitunnus, "OPTIMOI");
					}
				}
			}

			//printataan pikku raporttia mitä tulikaan tehtyä
			echo "<font class='message'>".t("Valmistettiin tuotetta")." $isatuoteno $isavarattu ".ta($kieli, "Y", $isarow["yksikko"])."</font><br>";
		}
	}

	// Tehdään uusi tuote $uusiarvo on käytettyjen raaka-aineiden arvo.
	if ($useavalmiste == "ON") {

		// Katsotaan eka paljonko kaikenkaikkiaan syntyy valmisteita
		$kaikenkaikkiaan = $isavarattu;

		while ($uvrow = mysql_fetch_array($uvresult)) {
			$kaikenkaikkiaan += $uvrow["varattu"];
		}

		mysql_data_seek($uvresult, 0);

		// Valmistetaan ensimmäinen isä
		valmista_isa($isarow, $tilrivirow, $isavarattu, $isatuoteno, $uusiarvo, $kaikenkaikkiaan);

		while ($uvrow = mysql_fetch_array($uvresult)) {
			if ($uvrow["paikka"] != '') {
				//käytetään rivillä olevaa paikkaa
				$query = "	SELECT saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus,
							tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
							FROM tuote, tuotepaikat
							WHERE tuote.yhtio=tuotepaikat.yhtio
							and tuote.tuoteno=tuotepaikat.tuoteno
							and concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) = '$uvrow[paikka]'
							and tuote.tuoteno = '$uvrow[tuoteno]'
							and tuote.yhtio = '$kukarow[yhtio]'";
				$tuoteresult = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($tuoteresult) != 1) {
					//jos ei löytynyt yksiselitteistä riviä yritetään oletuspaikkaa
					$query = "	SELECT saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus,
								tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
								FROM tuote, tuotepaikat
								WHERE tuote.yhtio=tuotepaikat.yhtio
								and tuote.tuoteno=tuotepaikat.tuoteno
								and oletus='X'
								and tuote.tuoteno = '$uvrow[tuoteno]'
								and tuote.yhtio = '$kukarow[yhtio]'";
					$tuoteresult = mysql_query($query) or pupe_error($query);
				}
			}
			else {
				//käytetään oletuspaikkaa
				$query = "	SELECT saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus,
							tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
							FROM tuote, tuotepaikat
							WHERE tuote.yhtio=tuotepaikat.yhtio
							and tuote.tuoteno=tuotepaikat.tuoteno
							and oletus='X'
							and tuote.tuoteno = '$uvrow[tuoteno]'
							and tuote.yhtio = '$kukarow[yhtio]'";
				$tuoteresult = mysql_query($query) or pupe_error($query);
			}

			if(mysql_num_rows($tuoteresult) != 1) {
				echo "<font class='error'>$uvrow[tuoteno] ".t("Varastopaikkaa ei löydy")."</font><br>";
			}

			$uvisarow = mysql_fetch_array($tuoteresult);

			valmista_isa($uvisarow, $uvrow, $uvrow["varattu"], $uvrow["tuoteno"], $uusiarvo, $kaikenkaikkiaan);
		}
	}
	else {
		valmista_isa($isarow, $tilrivirow, $isavarattu, $isatuoteno, $uusiarvo, $isavarattu);
	}

	echo "<br>";
}

?>