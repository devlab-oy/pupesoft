<?php

if (file_exists("tulosta_tilaustuotetarrat.inc")) {
	require_once("tulosta_tilaustuotetarrat.inc");
}
else {
	require_once("tilauskasittely/tulosta_tilaustuotetarrat.inc");
}

//v‰h‰n joutuu laitaa talteen n‰it‰ alkuper‰isi‰
$isavarattu   = $atil;
$isatuoteno   = $tuoteno;
$useavalmiste = "";

if ($tee == 'UV') {
	if ($atil <= 0) {
		$tee = '';
		echo "<font class='error'>".t("Anna valmistettava m‰‰r‰")."</font><br>";
	}
}

// Katsotaan onko useita valmisteita?
if ($tee == 'UV') {
	$query = "	SELECT tilausrivi.*,
				trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka,
				tuote.ei_saldoa, tuote.kehahin
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

	if ($varastopaikka != '') {
		//k‰ytet‰‰n rivill‰ olevaa paikkaa
		$query = "	SELECT  saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
					tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
					FROM tuote, tuotepaikat
					WHERE tuote.yhtio=tuotepaikat.yhtio
					and tuote.tuoteno=tuotepaikat.tuoteno
					and concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) = '$varastopaikka'
					and tuote.tuoteno = '$isatuoteno'
					and tuote.yhtio = '$kukarow[yhtio]'";
		$tuoteresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tuoteresult) != 1) {
			//jos ei lˆytynyt yksiselitteist‰ rivi‰ yritet‰‰n oletuspaikkaa
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
		//k‰ytet‰‰n oletuspaikkaa
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

	if (mysql_num_rows($tuoteresult) != 1) {
		$tee='';
		echo "<font class='error'>$isatuoteno ".t("Varastopaikkaa ei lˆydy")."</font><br>";
	}
	else {
		$isarow = mysql_fetch_array($tuoteresult);
	}

	//k‰ytet‰‰n tilausriveill‰ olevia tuotteita
	$query = "	SELECT tilausrivi.*, trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka, tuote.ei_saldoa, tuote.sarjanumeroseuranta
				FROM tilausrivi
				JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.otunnus = '$tilrivirow[otunnus]'
				and tilausrivi.perheid = '$tilrivirow[perheid]'
				and tilausrivi.tyyppi = 'V'
				ORDER by tilausrivi.tuoteno";
	$perheresult = mysql_query($query) or pupe_error($query);

	//jos tuoteperhe on olemassa
	if (mysql_num_rows($perheresult) > 0) {
		while ($perherow = mysql_fetch_array($perheresult)) {
			if ($perherow["ei_saldoa"] == "") {

				unset($tuoteresult);

				$varataankpl = 0;

				//N‰in paljon haluamme k‰ytt‰‰ raaka-ainetta
				if ($kulukpllat[$perherow["tunnus"]] != 0) {
					$varataankpl = $kulukpllat[$perherow["tunnus"]];
				}
				else {
					$varataankpl = $perherow['varattu'] * $akerroin;
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

					if (mysql_num_rows($tuoteresult) != 1) {
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
				if (mysql_num_rows($tuoteresult) == 1) {
					$trow = mysql_fetch_array($tuoteresult);

					if ($trow['saldo'] < $varataankpl and $vakisinhyvaksy == "") {
						$tee=''; //Raaka-aineet eiv‰t riit‰!
						echo "<font class='error'>$perherow[tuoteno] ".t("saldo")." $trow[saldo] ".t("ei riit‰ valmistukseen")." ($varataankpl)</font><br>";
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

			if (mysql_num_rows($tuoteresult) != 1) {
				//jos ei lˆytynyt yksiselitteist‰ rivi‰ yritet‰‰n oletuspaikkaa
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
			//K‰ytet‰‰n oletuspaikkaa
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

		if (mysql_num_rows($tuoteresult) == 0) {
			//sittenh‰n tuote on saldoton
			$query = "	SELECT tuote.kehahin, tuote.ei_saldoa, tuote.yksikko,
						tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm
						FROM tuote
						WHERE tuote.tuoteno = '$perherow[tuoteno]'
						and tuote.yhtio = '$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);
		}

		$tuoterow = mysql_fetch_array($tuoteresult);

		//Poistetaan raaka-aineet varastosta
		if ($kulukpllat[$perherow["tunnus"]] != 0) {
			$kpl = str_replace(',','.',$kulukpllat[$perherow["tunnus"]]);
		}
		else {
			$kpl = $perherow['varattu'] * str_replace(',','.',$akerroin);
		}

		$kpl = str_replace(',','.',$kpl);

		if    ($tuoterow['epakurantti100pvm'] != '0000-00-00') $tuoterow['kehahin'] = 0;
		elseif ($tuoterow['epakurantti75pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.25, 6);
		elseif ($tuoterow['epakurantti50pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.5,  6);
		elseif ($tuoterow['epakurantti25pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.75, 6);

		if (in_array($perherow["sarjanumeroseuranta"], array("S","U","G"))) {
			$arvo = sarjanumeron_ostohinta("myyntirivitunnus", $perherow["tunnus"])*$kpl;
		}
		else {
			$arvo = $tuoterow['kehahin'] * $kpl;
		}

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
					hyllyalue 	= '$tuoterow[hyllyalue]',
					hyllynro 	= '$tuoterow[hyllynro]',
					hyllytaso 	= '$tuoterow[hyllytaso]',
					hyllyvali 	= '$tuoterow[hyllyvali]',
					selite   	= '".t("K‰ytettiin tuotteen")." $isatuoteno ".t("valmistamiseen")." ".t("tyˆm‰‰r‰yksell‰")." $tilrivirow[otunnus]',
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

			//	Merkataan er‰ kulutetuksi
			if (in_array($perherow["sarjanumeroseuranta"], array("E","F","G"))) {

				// Haetaan eka er‰tiedot
				$query = "	SELECT ostorivitunnus, myyntirivitunnus, sarjanumero
							FROM sarjanumeroseuranta
							WHERE yhtio			 = '$kukarow[yhtio]'
							and tuoteno			 = '$perherow[tuoteno]'
							and myyntirivitunnus = '$perherow[tunnus]'
							LIMIT 1";
				$lisa_res = mysql_query($query) or pupe_error($query);
				$lisa_row = mysql_fetch_array($lisa_res);

				if ($lisa_row["ostorivitunnus"] != $lisa_row["myyntirivitunnus"]) {
					// Normikeissi jossa er‰ on jo tuloutettu

					// luodaan muuttuja jota pienennet‰‰n luupissa
					$eraayht = $perherow["varattu"];

					// luupataan niin kauan kuin tarvitaan jotta kaikki varatut on hanskattu
					while ($eraayht > 0) {

						// koitetaan lˆyt‰‰ vapaita ostettuja eri‰ mit‰ myyd‰
						$query = "	SELECT era_kpl, tunnus, ostorivitunnus
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

						// jos lˆytyy ostettuja eri‰ myyt‰v‰ks niin menn‰‰n t‰nne
						if (mysql_num_rows($erajaljella_res) == 1) {
							$erajaljella_row = mysql_fetch_array($erajaljella_res);

							// luodaan muuttuja jossa annetaan kyselyille m‰‰r‰muutoksen
							$eravahennetaan = 0;

							// jos lˆydetyn er‰n m‰‰r‰ riitt‰‰ koko tarvittavaan m‰‰r‰‰n niin pannaan kaikki haisemaan
							if ($erajaljella_row["era_kpl"] >= $eraayht) {
								$eravahennetaan = $eraayht;
								$lisataanrivi = "";
							}
							// muuten vaan lˆydetyn er‰n verran
							else {
								$eravahennetaan = $erajaljella_row["era_kpl"];
								$lisataanrivi = "KYLLAKIITOS";
							}

							// v‰hennet‰‰n ostorivilt‰ tarvittava m‰‰r‰
							$query = "	UPDATE sarjanumeroseuranta
										SET era_kpl 			= era_kpl-$eravahennetaan,
										muutospvm				= now(),
										muuttaja				= '$kukarow[kuka]'
										WHERE yhtio 			= '$kukarow[yhtio]'
										and tuoteno				= '$perherow[tuoteno]'
										and ostorivitunnus 		= '$erajaljella_row[ostorivitunnus]'
										and myyntirivitunnus 	= 0
										and sarjanumero 		= '$lisa_row[sarjanumero]'
										and tunnus				= '$erajaljella_row[tunnus]'
										LIMIT 1";
							$lisa_res = mysql_query($query) or pupe_error($query);

							// haetaan p‰ivitett‰v‰ myyntirivi jotta voidaan kloonata se myˆhemmin tarvittaessa
							$query = "	SELECT *
										FROM sarjanumeroseuranta
										WHERE yhtio			 = '$kukarow[yhtio]'
										and tuoteno			 = '$perherow[tuoteno]'
										and myyntirivitunnus = '$perherow[tunnus]'
										and era_kpl			 = 0
										LIMIT 1";
							$erahakures = mysql_query($query) or pupe_error($query);
							$erahakurow = mysql_fetch_array($erahakures);

							// lis‰t‰‰n myyntiriville sama m‰‰r‰, ja samalla p‰ivitet‰‰n ostorivitunnus oikeaksi
							$query = "	UPDATE sarjanumeroseuranta
										SET era_kpl			 = era_kpl+$eravahennetaan,
										ostorivitunnus		 = '$erajaljella_row[ostorivitunnus]',
										muutospvm			 = now(),
										muuttaja			 = '$kukarow[kuka]'
										WHERE yhtio			 = '$kukarow[yhtio]'
										and tunnus 			 = '$erahakurow[tunnus]'
										LIMIT 1";
							$lisa_res = mysql_query($query) or pupe_error($query);

							// lis‰t‰‰n uusi kloonattu rivi
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

							// v‰hennet‰‰n luupattavaa m‰‰r‰‰
							$eraayht -= $eravahennetaan;
						}
						// Ei lˆytynyt (en‰‰n) ostettuja eri‰, eli "myyty pakkaselle" niin luodaan uusi er‰rivi jossa ostorivitunnus on nolla jonne laitetaan loppum‰‰r‰.
						else {

							$query = "	INSERT into sarjanumeroseuranta
										SET yhtio 		= '$kukarow[yhtio]',
										tuoteno			= '$perherow[tuoteno]',
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
										ostorivitunnus		 = '$perherow[tunnus]',
										muutospvm			 = now(),
										muuttaja			 = '$kukarow[kuka]'
										WHERE yhtio			 = '$kukarow[yhtio]'
										and tuoteno			 = '$perherow[tuoteno]'
										and myyntirivitunnus = '$perherow[tunnus]'
										and era_kpl			 = 0
										LIMIT 1";
							$lisa_res = mysql_query($query) or pupe_error($query);

							// v‰hennet‰‰n luupattavaa m‰‰r‰‰
							$eraayht = 0;
						}
					}
				}
				else {
					// Luodaan er‰ vaikka, ei olla viel‰ ostettu
					$query = "	UPDATE sarjanumeroseuranta
								SET era_kpl = $perherow[varattu]*-1,
								ostorivitunnus = 0
								WHERE yhtio 			= '$kukarow[yhtio]'
								and tuoteno				= '$perherow[tuoteno]'
								and ostorivitunnus 		= '$perherow[tunnus]'
								and myyntirivitunnus 	= 0
								and sarjanumero			= '$lisa_row[sarjanumero]'
								LIMIT 1";
					$lisa_res = mysql_query($query) or pupe_error($query);

					$query = "	UPDATE sarjanumeroseuranta
								SET era_kpl = $perherow[varattu]
								WHERE yhtio			 = '$kukarow[yhtio]'
								and tuoteno			 = '$perherow[tuoteno]'
								and myyntirivitunnus = '$perherow[tunnus]'
								LIMIT 1";
					$lisa_res = mysql_query($query) or pupe_error($query);
				}
			}

			echo "<font class='message'>".t("Varastosta k‰ytettiin tuotetta")." $perherow[tuoteno] $kpl ".t_avainsana("Y", "", "and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."</font><br>";

		}
		else {
			echo "<font class='message'>".t("K‰ytettiin tuotetta")." $perherow[tuoteno] $kpl ".t_avainsana("Y", "", "and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."</font><br>";
		}

		//p‰ivitet‰‰n tilausrivi k‰ytetyksi
		$query = "	UPDATE tilausrivi
					SET varattu = round(varattu-$kpl,2),
					kpl			= round(kpl+$kpl,2)
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilrivirow[otunnus]'
					and tunnus  = '$perherow[tunnus]'
					and tyyppi  = 'V'";
		$upparesult = mysql_query($query) or pupe_error($query);
	}

	if (!function_exists("valmista_isa")) {
		function valmista_isa ($isarow, $tilrivirow, $isavarattu, $isatuoteno, $uusiarvo, $kaikenkaikkiaan, $kaikenkaikkiaan_arvot) {
			global $yhtiorow, $kukarow, $laskurow, $valmistettavat, $jaljella_tot;

			// Jos on useampi valmiste
			if ((float) $kaikenkaikkiaan > 1) {

				if ((float) $kaikenkaikkiaan_arvot > 0) {
					//jyvitet‰‰n $uusiarvo valmisteille kappaleiden ja kehahintojen mukaan
					$uusiarvo = round($uusiarvo * (($isavarattu * $isarow["kehahin"])/$kaikenkaikkiaan_arvot), 6);
				}
				else {
					//jyvitet‰‰n $uusiarvo valmisteille valmisteiden m‰‰r‰n mukaan
					$uusiarvo = round($uusiarvo/$kaikenkaikkiaan, 6);
				}
			}

			if ($isarow["sarjanumeroseuranta"] == "S" or $isarow["sarjanumeroseuranta"] == "U" or $isarow["sarjanumeroseuranta"] == "G") {
				$kehahin = 0;
				$selite = t("Valmistettiin tuotetta tyˆm‰‰r‰yksell‰")." $tilrivirow[otunnus] ($uusiarvo)";
			}
			else {
				if ($isarow['saldo'] + $isavarattu > 0 and $isarow['saldo'] > 0) {
					// kehahin matikka (tuotteella pit‰‰ olla saldoa ennen ja j‰lkeen, ett‰ kehahin lasketaan)
					$kehahin = sprintf('%.4f', ($isarow['saldo'] * $isarow['kehahin'] + $uusiarvo) / ($isarow['saldo'] + $isavarattu));
				}
				else {
					// jos j‰‰d‰‰n saldossa t‰m‰nkin tulon j‰lkeen miinukselle tai nollille, laitetaan kehahin suoraan t‰m‰n valmistuksen hinnasta
					$kehahin = sprintf('%.4f', $uusiarvo/$isavarattu);
				}

				$selite = t("Valmistettiin tuotetta tyˆm‰‰r‰yksell‰")." $tilrivirow[otunnus] ($isarow[kehahin] --> $kehahin)";
			}

			///* Tehd‰‰n tapahtuma *///
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
			$tapahtumaid = mysql_insert_id();

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

			// p‰ivitet‰‰n tilausrivi k‰ytetyksi ja splitataan se...
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
					$insresult = mysql_query($query) or pupe_error($query);
					$valmistettavat .= ",".mysql_insert_id();
				}

				//	Kaikille sarjanumeroille tehd‰‰n varastoon saavutettu O-rivi, muuten mm ker‰ys ei toimi ja ehk‰ se on myˆs loogisempaa?
				//	P‰ivitet‰‰n valmistetulle riville laskutettuaika, jotta se n‰kyy kaikkialla oikein
				if ($isarow["sarjanumeroseuranta"] != "") {
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
				$insresult = mysql_query($query) or pupe_error($query);
				$uusrivitunnus = mysql_insert_id();
				$valmistettavat .= ",".$uusrivitunnus;

				$jaljella = round($tilrivirow["varattu"] - $isavarattu, 2);
				$jaljella_tot += $jaljella;

				//p‰ivitet‰‰n alkuper‰inen rivi
				$query = "	UPDATE tilausrivi
							SET varattu = '$jaljella',
							kpl 		= round(kpl+$isavarattu,2)
							WHERE yhtio = '$kukarow[yhtio]'
							and otunnus = '$tilrivirow[otunnus]'
							and tunnus  = '$tilrivirow[tunnus]'
							and tyyppi in ('W','M')";
				$upparesult = mysql_query($query) or pupe_error($query);

				//	Siirret‰‰n sarjanumerot valmistetulle tilausriville
				if (in_array($isarow["sarjanumeroseuranta"], array("S","T","U","V"))) {

					//	Siirret‰‰n ostorivin tunnus valmistetulle riville
					$query = "	SELECT tunnus
								FROM sarjanumeroseuranta
								WHERE yhtio = '$kukarow[yhtio]'
								and ostorivitunnus = '$tilrivirow[tunnus]'
								LIMIT ".(int) $isavarattu."";
					$sarjares = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($sarjares) > 0) {
						while ($sarjarow = mysql_fetch_array($sarjares)) {
							$query = "	UPDATE sarjanumeroseuranta SET
										ostorivitunnus	= $uusrivitunnus
										WHERE yhtio = '$kukarow[yhtio]'
										and tunnus = '$sarjarow[tunnus]'";
							$result = mysql_query($query) or pupe_error($query);
						}
					}

					//	Generoidaan loput jos jotain puuttuu!
					$puuttuva_sarjanumerot = $isavarattu - mysql_num_rows($sarjares);

					if ($puuttuva_sarjanumerot > 0) {
						for ($i=1; $i<=$puuttuva_sarjanumerot; $i++) {
							//	Arvotaan uniikki sarjanumero
							$query = "	SELECT count(distinct(sarjanumero)) kpl
										FROM sarjanumeroseuranta
										WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tilrivirow[tuoteno]'";
							$sarjares = mysql_query($query) or pupe_error($query);
							$sarjarow = mysql_fetch_array($sarjares);
							$sarja = $sarjarow["kpl"]+1;

							// varmistetaan, ett‰ t‰m‰ on uniikki!
							$onUniikki = false;

							while ($onUniikki === false) {
								$sarjanro = "$tilrivirow[tuoteno]-$sarja";

								$query = "	SELECT tunnus
											FROM sarjanumeroseuranta
											WHERE yhtio = '$kukarow[yhtio]'
											and tuoteno = '$tilrivirow[tuoteno]'
											and sarjanumero = '$sarjanro'";
								$sarjares = mysql_query($query) or pupe_error($query);

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
							$sarjares1 = mysql_query($query) or pupe_error($query);
							$uusisarjatunnus = mysql_insert_id();

							//	P‰ivitet‰‰n sarjanumerolle lis‰tiedot jos meill‰ on ne k‰ytˆss‰
							if (table_exists("sarjanumeron_lisatiedot") and file_exists("generoi_sarjanumeron_lisatiedot.inc")) {
								require("generoi_sarjanumeron_lisatiedot.inc");
							}
						}
					}

					tulosta_tilaustuotetarrat(0, $uusrivitunnus, "OPTIMOI");
				}
				elseif (in_array($isarow["sarjanumeroseuranta"], array("E","F","G"))) {

					//	Siirret‰‰n ostorivin tunnus valmistetulle riville
					$query = "	SELECT tunnus, era_kpl
								FROM sarjanumeroseuranta
								WHERE yhtio = '$kukarow[yhtio]'
								and ostorivitunnus = '$tilrivirow[tunnus]'
								LIMIT ".(int) $isavarattu."";
					$sarjares = mysql_query($query) or pupe_error($query);

					//	Generoidaan loput jos jotain puuttuu!
					$puuttuva_sarjanumerot = $isavarattu;

					if (mysql_num_rows($sarjares) > 0) {
						while ($sarjarow = mysql_fetch_array($sarjares)) {
							$query = "	UPDATE sarjanumeroseuranta SET
										ostorivitunnus	= $uusrivitunnus
										WHERE yhtio = '$kukarow[yhtio]'
										and tunnus = '$sarjarow[tunnus]'";
							$result = mysql_query($query) or pupe_error($query);

							$puuttuva_sarjanumerot -= $sarjarow["era_kpl"];
						}
					}

					if ($puuttuva_sarjanumerot > 0) {
						//	Arvotaan uniikki sarjanumero
						$query = "	SELECT count(distinct(sarjanumero)) kpl
									FROM sarjanumeroseuranta
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$tilrivirow[tuoteno]'";
						$sarjares = mysql_query($query) or pupe_error($query);
						$sarjarow = mysql_fetch_array($sarjares);
						$sarja = $sarjarow["kpl"]+1;

						// varmistetaan, ett‰ t‰m‰ on uniikki!
						$onUniikki = false;

						while ($onUniikki === false) {
							$sarjanro = "$tilrivirow[tuoteno]-$sarja";

							$query = "	SELECT tunnus
										FROM sarjanumeroseuranta
										WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tilrivirow[tuoteno]' and sarjanumero = '$sarjanro'";
							$sarjares = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($sarjares) == 0) {
								$onUniikki = true;
							}
							else {
								$sarja++;
							}
						}

						//	Generoidaan er‰numerot!
						$query = "	INSERT INTO sarjanumeroseuranta
									(yhtio, tuoteno, sarjanumero, ostorivitunnus, myyntirivitunnus, era_kpl, laatija, luontiaika, hyllyalue, hyllynro, hyllyvali, hyllytaso)
									VALUES ('$kukarow[yhtio]', '$tilrivirow[tuoteno]', '$sarjanro', '$uusrivitunnus', 0, '$puuttuva_sarjanumerot', '$kukarow[kuka]', now(), '$tilrivirow[hyllyalue]', '$tilrivirow[hyllynro]', '$tilrivirow[hyllyvali]', '$tilrivirow[hyllytaso]')";
						$sarjares1 = mysql_query($query) or pupe_error($query);
						$uusisarjatunnus = mysql_insert_id();

						//	P‰ivitet‰‰n sarjanumerolle lis‰tiedot jos meill‰ on ne k‰ytˆss‰
						if (table_exists("sarjanumeron_lisatiedot") and file_exists("generoi_sarjanumeron_lisatiedot.inc")) {
							require("generoi_sarjanumeron_lisatiedot.inc");
						}
					}

					tulosta_tilaustuotetarrat(0, $uusrivitunnus, "OPTIMOI");
				}
			}

			//printataan pikku raporttia mit‰ tulikaan tehty‰
			echo "<font class='message'>".t("Valmistettiin tuotetta")." $isatuoteno $isavarattu ".t_avainsana("Y", $kieli, "and avainsana.selite='$isarow[yksikko]'", "", "", "selite")."</font><br>";

			$query = "	SELECT paivitys
						FROM oikeu
						WHERE yhtio	= '$kukarow[yhtio]'
						and kuka	= '$kukarow[kuka]'
						and nimi	= 'tilauskasittely/jtselaus.php'
						and alanimi = ''";
			$jtoikeudetres = mysql_query($query) or pupe_error($query);

			if ((mysql_num_rows($jtoikeudetres) <> 0 and $yhtiorow["automaattinen_jt_toimitus_valmistus"] != "") or $yhtiorow["automaattinen_jt_toimitus_valmistus"] == "J") {
				$jtoikeudetrow  = mysql_fetch_array($jtoikeudetres);
				$jtrivit 		= array();
				$jtrivit_paikat = array();

				echo "<br>";

				//	Kohdistetaan vain oikeille asiakkaille n‰iss‰ caseissa
				if ($yhtiorow['automaattinen_jt_toimitus_valmistus'] == 'S' or $yhtiorow["automaattinen_jt_toimitus_valmistus"] == "V") {
					$suoralisa = "";

					if ($yhtiorow['automaattinen_jt_toimitus_valmistus'] == 'S') {
						$suoralisa = "AND tilausrivin_lisatiedot.suoraan_laskutukseen = 'o'";
					}

					//Tutkitaan lˆytyykˆ JT-rivi joka m‰pp‰ytyy t‰h‰n ostoriviin
				   	$query = "	SELECT ifnull(if((tilausrivi.perheid != 0 and tilausrivi.tunnus != tilausrivi.perheid), tilausrivi.perheid, tilausrivi.tunnus), 0) jtrivi
				   				FROM tilausrivin_lisatiedot
				   				JOIN tilausrivi ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
				   				WHERE tilausrivin_lisatiedot.yhtio 			 = '$kukarow[yhtio]'
				   				AND tilausrivin_lisatiedot.tilausrivilinkki  = '$tilrivirow[tunnus]'
								$suoralisa";
					$varastoon_result = pupe_query($query);

					while ($varastoon_row = mysql_fetch_assoc($varastoon_result)) {
						// Mitk‰ suoratoimitukset valmistettiin t‰ll‰ keikalla
						$jtrivit[$varastoon_row["jtrivi"]] = $varastoon_row["jtrivi"];
						// Katotaan mille paikalle n‰‰ meni, jotta suoratoimitus voidaan laukasta t‰lt‰ paikalta
						$jtrivit_paikat[$varastoon_row["jtrivi"]] = $tapahtumaid;
					}
				}

				$varastosta = array(kuuluukovarastoon($tilrivirow["hyllyalue"], $tilrivirow["hyllynro"]));

				// Toimitetaan vain valmistettua is‰tuotetta, ei KAIKKIA firman j‰lkitoimitusrivej‰
				jt_toimita("", "", $varastosta, $jtrivit, $jtrivit_paikat, "tosi_automaaginen", "JATKA", "", $isatuoteno, '', "TULLAAN_VALMISTUKSESTA");

				//	Laitetaan tavarat liikkeelle jos sallitaan!
				if (($jtoikeudetrow["paivitys"] == 1 and ($yhtiorow["automaattinen_jt_toimitus_valmistus"] == "T" or $yhtiorow["automaattinen_jt_toimitus_valmistus"] == "S" or $yhtiorow["automaattinen_jt_toimitus_valmistus"] == "V")) or $yhtiorow["automaattinen_jt_toimitus_valmistus"] == "J") {
					jt_toimita("", "", "", "", "", "dummy", "TOIMITA");
				}
			}
		}
	}

	// Tehd‰‰n uusi tuote $uusiarvo on k‰ytettyjen raaka-aineiden arvo.
	if ($useavalmiste == "ON") {

		// Katsotaan eka kuinka monta eri valmistetta syntyy
		$kaikenkaikkiaan = 1;

		// Katsotaan eka kanssa valmisteiden nykyinen varastonarvo
		if ($isarow["kehahin"]  > 0) {
			$kaikenkaikkiaan_arvot = $isarow["kehahin"];
		}
		else {
			unset($kaikenkaikkiaan_arvot);
		}

		while ($uvrow = mysql_fetch_array($uvresult)) {
			$kaikenkaikkiaan++;

			if (isset($kaikenkaikkiaan_arvot)) {
				if ($uvrow["kehahin"] == 0) {
					// Jos yksikin valmiste on nollakehalla, niin jyvitet‰‰n $uusiarvo valmisteille vain kappalem‰‰rien mukaan
					unset($kaikenkaikkiaan_arvot);
				}
				else {
					$kaikenkaikkiaan_arvot += ($uvrow["varattu"]*$uvrow["kehahin"]);
				}
			}
		}

		mysql_data_seek($uvresult, 0);

		// Valmistetaan ensimm‰inen is‰
		valmista_isa($isarow, $tilrivirow, $isavarattu, $isatuoteno, $uusiarvo, $kaikenkaikkiaan, $kaikenkaikkiaan_arvot);

		while ($uvrow = mysql_fetch_array($uvresult)) {
			if ($uvrow["paikka"] != '') {
				//k‰ytet‰‰n rivill‰ olevaa paikkaa
				$query = "	SELECT saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
							tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
							FROM tuote, tuotepaikat
							WHERE tuote.yhtio = tuotepaikat.yhtio
							and tuote.tuoteno = tuotepaikat.tuoteno
							and concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) = '$uvrow[paikka]'
							and tuote.tuoteno = '$uvrow[tuoteno]'
							and tuote.yhtio = '$kukarow[yhtio]'";
				$tuoteresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($tuoteresult) != 1) {
					//jos ei lˆytynyt yksiselitteist‰ rivi‰ yritet‰‰n oletuspaikkaa
					$query = "	SELECT saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
								tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
								FROM tuote, tuotepaikat
								WHERE tuote.yhtio = tuotepaikat.yhtio
								and tuote.tuoteno = tuotepaikat.tuoteno
								and oletus='X'
								and tuote.tuoteno = '$uvrow[tuoteno]'
								and tuote.yhtio = '$kukarow[yhtio]'";
					$tuoteresult = mysql_query($query) or pupe_error($query);
				}
			}
			else {
				//k‰ytet‰‰n oletuspaikkaa
				$query = "	SELECT saldo, ei_saldoa, tuote.yksikko, kehahin, tuotepaikat.tunnus tunnus, tuote.sarjanumeroseuranta,
							tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso
							FROM tuote, tuotepaikat
							WHERE tuote.yhtio = tuotepaikat.yhtio
							and tuote.tuoteno = tuotepaikat.tuoteno
							and oletus='X'
							and tuote.tuoteno = '$uvrow[tuoteno]'
							and tuote.yhtio = '$kukarow[yhtio]'";
				$tuoteresult = mysql_query($query) or pupe_error($query);
			}

			if (mysql_num_rows($tuoteresult) != 1) {
				echo "<font class='error'>$uvrow[tuoteno] ".t("Varastopaikkaa ei lˆydy")."</font><br>";
			}

			$uvisarow = mysql_fetch_array($tuoteresult);

			valmista_isa($uvisarow, $uvrow, $uvrow["varattu"], $uvrow["tuoteno"], $uusiarvo, $kaikenkaikkiaan, $kaikenkaikkiaan_arvot);
		}
	}
	else {
		valmista_isa($isarow, $tilrivirow, $isavarattu, $isatuoteno, $uusiarvo, 1, '');
	}

	echo "<br>";
}
