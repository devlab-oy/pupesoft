<?php

	$tee = 'UV'; // Toivotaan, etta kaikki onnistuu

	$atil 		= str_replace ( ",", ".", $atil);
	$akplhinta 	= str_replace ( ",", ".", $akplhinta);
	$aale 		= str_replace ( ",", ".", $aale);

	if (substr($tuoteno,-1) == "'") $tuoteno = str_replace ( "'", "*", $tuoteno);

	if(substr($tuoteno,0,1) == '*') { // Nyt me selataan

		$tuoteno 	= substr($tuoteno,1);
		$sanat 		= split(" ", $tuoteno);
		$lisa1		= '';

		foreach ($sanat as $sana) $lisa1 .= " and concat_ws(' ', nimitys, lyhytkuvaus, tuotemerkki) like '%" . trim($sana) . "%' ";

		$lisa1 = substr($lisa1, 4);

		$query = "	SELECT tuoteno, nimitys
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]' 
					and status != 'P'
					and $lisa1
					ORDER BY tuoteno";
		$tresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tresult) >  100) {
			$kentta='tuoteno';
			$varaosavirhe = "".t("VIRHE: Hakuehdolla löytyi liikaa tuotteita!")."";
			$tuoteno='';
			$tee = 'Y';
		}
		else {
			if (mysql_num_rows($tresult) == 0) {
				$kentta='tuoteno';
				$varaosavirhe = "".t("VIRHE: Hakuehdolla ei löytynyt yhtään tuotetta")."!";
				$tuoteno='';
				$tee = 'Y';
			}
			else {
				//Tehdaan pop-up valmiiksi myohempaa kayttoa varten
				$kentta = 'atil';
				$ulos = "<select name='tuoteno'>";
				while ($trow = mysql_fetch_array ($tresult)) {
					$ulos .= "<option value='$trow[0]'>$trow[0] $trow[1]</option>";
				}
				$ulos .= "</select>";
				$tee = 'Y';
			}
		}
	}
	elseif (substr($tuoteno,-1) == '*') {
		$query = "	SELECT tuoteno, nimitys, ostohinta
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]' 
					and status != 'P'
					and tuoteno like '" . substr($tuoteno,0,-1) . "%'
					ORDER BY tuoteno";
		$tresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tresult) >  100) {
			$kentta='tuoteno';
			$varaosavirhe = "".t("VIRHE: Hakuehdolla löytyi liikaa tuotteita")."!";
			$tuoteno='';
			$tee = 'Y';
		}
		else {
			if (mysql_num_rows($tresult) == 0) {
				$kentta='tuoteno';
				$varaosavirhe = "".t("VIRHE: Hakuehdolla ei löytynyt yhtään tuotetta")."!";
				$tuoteno='';
				$tee = 'Y';
			}
			else {
				//Tehdaan pop-up valmiiksi myohempaa kayttoa varten
				$kentta = 'atil';
				$ulos = "<select name='tuoteno'>";
				while ($trow = mysql_fetch_array ($tresult)) {
					$ulos .= "<option value='$trow[0]'>$trow[0] $trow[1]</option>";
				}
				$ulos .= "</select>";
				$tee = 'Y';
			}
		}
	}
	else  {
			///* Toimittajan tuotenumerospecial*///
			if (substr($tuoteno,0,1) == '?') {

				$query = "	SELECT tuoteno
							FROM tuotteen_toimittajat
							WHERE yhtio = '$kukarow[yhtio]' 
							and toim_tuoteno='".substr($tuoteno,1)."'";
				$tresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($tresult) > 1) {
					$kentta='tuoteno';
					$varaosavirhe = "".t("Useita tuotteita samalla Toimittajan tuotenumerolla")."!<br>";
					$tuoteno='';
					$tee = 'Y';
				}
				else {
					if (mysql_num_rows($tresult) == 0) {
						$kentta='tuoteno';
						$varaosavirhe = "".t("VIRHE: Hakuehdolla ei löytynyt yhtään tuotetta")."!";
						$tuoteno='';
						$tee = 'Y';
					}
					else {
						//Tehdään tuotenumero ja kpl
						$trivi = mysql_fetch_array($tresult);
						$tuoteno = $trivi['tuoteno'];
					}
				}
			}

			$query = "	SELECT *
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				$kentta = 'tuoteno';
				$varaosavirhe .= "".t("VIRHE: Tuotenumeroa")." $tuoteno ".t("ei löydy järjestelmästä")."!<br>";
				$tuoteno = '';
				$tee = "Y";
			}
			else{
				$trow = mysql_fetch_array($result);
				$tuoteno = $trow['tuoteno'];
			}

			if ($atil == 0) {
				if ($kentta == '') $kentta = 'atil'; // Kursori

				$varaosavirhe .= "".t("VIRHE: Syötä tilattava määrä")."!<br>";
				$tee = "Y";
			}
	}

	if ($tee == 'UV') {
		//etsitään tuotteelle varastopaikka
		$query = "	SELECT *, varastopaikat.tunnus varasto, tuotepaikat.tunnus tuotepaikka
					FROM tuotepaikat
					LEFT JOIN varastopaikat
					ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(lpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(lpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					and concat(lpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(lpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					WHERE tuotepaikat.tuoteno = '$tuoteno'
					and tuotepaikat.yhtio = '$kukarow[yhtio]'";
		$qvresult = mysql_query($query) or pupe_error($query);
		
		while ($zrow = mysql_fetch_array($qvresult)) { // Käydään kaikki varastopaikat läpi

			///* Huomioidaan vain normaalivarastojen paikkoja, paitsi jos erikoisvarastokäsittely on triggeröity *///
			if ($zrow["tyyppi"] == '' or ($laskurow['varasto'] == $zrow["varasto"]) or ($naytakaikkipaikat == "ON")) {
				$hyllyalue[$i] 		= $zrow['hyllyalue'];
				$hyllynro[$i]  		= $zrow['hyllynro'];
				$hyllyvali[$i] 		= $zrow['hyllyvali'];
				$hyllytaso[$i] 		= $zrow['hyllytaso'];
				$hyllytunnus[$i]	= $zrow['tuotepaikka'];
				$varastotunnus[$i]	= $zrow['varasto'];

				if ($zrow['oletus'] != '') {
					$oletuspaikka = $i;
				}
				
				if ($laskurow['varasto'] == $zrow["varasto"]) {
					$varasto = $i;
				}
				$i++;
			}
		}
		
		//Ei löytynyt optimoitua paikkaa, otetaan silloin oletuspaikka
		if ($varasto == 0) {
			$varasto = $oletuspaikka;
		}

		//poistotuotekäsittely
		if ($trow["status"] == 'P') {
			$varaosavirhe .= t("VIRHE: Tämä on poistotuote, sitä ei voi valmistaa")."!<br>";
			$tee = "Y";
		}
	}

	if ($tee == 'UV') {

		if ($akerayspvm != '') {
			$kerayspvm = $akerayspvm;
		}
		else {
			$kerayspvm = $laskurow["kerayspvm"];
		}
		if ($atoimaika != '') {
			$toimaika = $atoimaika;
		}
		else {
			$toimaika = $laskurow["toimaika"];
		}

		// Valmistuksessa
		$avarattu	= $atil;
		$ajt		= 0;
	}
?>
