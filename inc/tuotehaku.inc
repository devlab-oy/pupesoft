<?php


	if (substr($tuoteno,0,1) != '*' and substr($tuoteno,-1) != '*' and substr($tuoteno,0,1) != '?' and substr($tuoteno,0,1) != '#' and $yhtiorow["automaattinen_tuotehaku"] != '') {
		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) == 0) {
			$query = "	SELECT *
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]' and tuoteno LIKE '$tuoteno%'";
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result) != 1) {
				$tuoteno = $tuoteno . "*";
			} 
			else {
				$tuotenorow = mysql_fetch_array($result);
				$tuoteno = $tuotenorow["tuoteno"];
			}
		}
	}

 	// Nyt me selataan
	if ((substr($tuoteno,0,1) == '*' or substr($tuoteno,-1) == '*' or substr($tuoteno,0,1) == '?') and trim(strlen($tuoteno)) > 2) {

		$lisa1 		= "";
		$ulos  		= "";
		$poislisa	= "";
		$kieltolisa	= "";

		if (substr($tuoteno,0,1) == '?') {
			// Toimittajan tuotenumero
			
			$query = "SELECT * from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and toim_tuoteno='".substr($tuoteno,1)."'";
			$tresult = mysql_query($query) or pupe_error($query);
			
			while ($toimirivi = mysql_fetch_array($tresult)) {
				$lisa1 .= "'$toimirivi[tuoteno]',";
			}

			if ($lisa1 != "") {
				$lisa1 = "and tuoteno in (".substr($lisa1,0,-1).")"; // vika pilkku pois
			}
		}
		elseif (substr($tuoteno, -2) == '**') {
			// Tuoteryhmä
			$lisa1 = " and tuote.try = '$tuoteno' ";
		}
		elseif (substr($tuoteno,0,1) == '*') {
			// Jos eka merkki on tähti etsitään teksteistä
			$tuoteno = substr($tuoteno, 1);
			$sanat = split(" ", $tuoteno);

			foreach ($sanat as $sana) {
				$lisa1 .= " and concat_ws(' ', tuote.nimitys, tuote.lyhytkuvaus, tuote.tuotemerkki) like '%" . trim($sana) . "%' ";
			}
		}
		elseif (substr($tuoteno,-1) == '*') {
			// Jos vika merkki on tähti niin etsitään osittainen tuotenumero
			$tuoteno = substr($tuoteno,0,-1);
			
			$lisa1 = " and tuote.tuoteno like '" . $tuoteno . "%' ";
		}
		
		$yhtiot 	= "";
		$konsyhtiot = array();

		//Voidaan hakea myös koko konsernin tuotteista
		if($konsernihaku != "KYLLA") {
			$yhtiot 	  = "yhtio = '$kukarow[yhtio]' ";
			$konsyhtiot[] = $kukarow["yhtio"];
		}
		else {
			//Yhtiövalinnat
			$query	= "	SELECT distinct yhtio, nimi
						from yhtio
						where konserni = '$yhtiorow[konserni]' and konserni != ''";
			$presult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($presult) > 0) {
				while ($prow = mysql_fetch_array($presult)) {
					$yhtiot .= "'".$prow["yhtio"]."',";
					$konsyhtiot[] = $prow["yhtio"];
				}
				$yhtiot = substr($yhtiot,0,-1);
				$yhtiot = "yhtio in ($yhtiot) ";

			}
			else {
				$yhtiot = "yhtio = '$kukarow[yhtio]' ";
				$konsyhtiot[] = $kukarow["yhtio"];
			}
		}
		
		if ($kutsuja == "tuoteperhe.php") {
			$perhelisa = ", IF((SELECT tuoteperhe.isatuoteno FROM tuoteperhe WHERE tuoteperhe.yhtio=tuote_wrapper.yhtio and tuoteperhe.isatuoteno=tuote_wrapper.tuoteno and tuoteperhe.tyyppi = '$hakutyyppi' LIMIT 1) IS NULL, 'pois', 'mukaan') rajaus";
			$perhelisa2 = "HAVING rajaus='mukaan'";
		}
		
		if($kaikkituhaku != "KYLLA") {
			
			$poislisa = " HAVING (tuote.status not in ('P','X') or saldo > 0) ";
		
			if ($kukarow["kesken"] > 0) {
				$query  = "	SELECT if(toim_maa != '', toim_maa, maa) maa
							FROM lasku
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus  = '$kukarow[kesken]'";
				$vieres = mysql_query($query) or pupe_error($query);
				$vierow = mysql_fetch_array($vieres);
			}
			elseif($verkkokauppa != "") {
				$vierow = array();

				if ($maa != "") {
					$vierow["maa"] = $maa;
				}
				else {
					$vierow["maa"] = $yhtiorow["maa"];
				}
			}
			elseif($kukarow["extranet"] != "") {
				$query  = "	SELECT if(toim_maa != '', toim_maa, maa) maa
							FROM asiakas
							WHERE yhtio	= '$kukarow[yhtio]'
							and tunnus  = '$kukarow[oletus_asiakas]'";
				$vieres = mysql_query($query) or pupe_error($query);
				$vierow = mysql_fetch_array($vieres);
			}

			if (isset($vierow) and $vierow["maa"] != "") {
				$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
			}
		}

		// Poistettuja tuotteita ei näytetä paitsi jos niillä on saldoa
		// Joinataan korvaavat mukaan		
		$query = "	SELECT
					tuote.yhtio,
					ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi='P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
					ifnull((SELECT id FROM korvaavat use index (yhtio_tuoteno) where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1), tuote.tuoteno) korvaavat,
					tuote.tuoteno,
					tuote.nimitys,
					tuote.osasto,
					tuote.try,
					tuote.myyntihinta,
					tuote.nettohinta,
					tuote.aleryhma,
					tuote.status,
					tuote.ei_saldoa,
					tuote.yksikko,
					tuote.tunnus,
					(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
					tuote.sarjanumeroseuranta,
					tuote.status,
					(SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) saldo
					FROM tuote use index (tuoteno, nimitys)
					WHERE tuote.$yhtiot
					$kieltolisa
					$lisa1
					$poislisa
					ORDER BY tuote.tuoteno
					LIMIT 201";
		$thresult = mysql_query($query) or pupe_error($query);
		
		$rows = array();
		
		if (mysql_num_rows($thresult) > 0) {

			// Rakennetaan array ja laitetaan korvaavat mukaan
			while ($mrow = mysql_fetch_array($thresult)) {
				if ($mrow["korvaavat"] != $mrow["tuoteno"]) {
					
					$query = "	SELECT
								tuote.yhtio,
								ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi='P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
								'$mrow[tuoteno]' korvaavat,
								tuote.tuoteno,
								tuote.nimitys,
								tuote.osasto,
								tuote.try,
								tuote.myyntihinta,
								tuote.nettohinta,
								tuote.aleryhma,
								tuote.status,
								tuote.ei_saldoa,
								tuote.yksikko,
								tuote.tunnus,
								(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
								tuote.sarjanumeroseuranta,
								tuote.status,
								(SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) saldo
								FROM korvaavat
								JOIN tuote ON tuote.yhtio=korvaavat.yhtio and tuote.tuoteno=korvaavat.tuoteno
								WHERE korvaavat.yhtio = '$mrow[yhtio]'
								and korvaavat.id = '$mrow[korvaavat]'
								$kieltolisa
								$poislisa
								ORDER BY korvaavat.jarjestys, korvaavat.tuoteno";
					$kores = mysql_query($query) or pupe_error($query);

					$ekakorva = "";

					while ($krow = mysql_fetch_array($kores)) {
						if ($ekakorva == "") $ekakorva = $krow["yhtio"].$krow["tuoteno"];
						if(!isset($rows[$ekakorva.$krow["tuoteno"]])) $rows[$ekakorva.$krow["tuoteno"]] = $krow;
					}
				}
				else {
					$rows[$mrow["yhtio"].$mrow["tuoteno"]] = $mrow;					
				}
			}
		}
		
		if (count($rows) >  200) {
			$kentta			= 'tuoteno';
			$varaosavirhe 	= t("VIRHE: Hakuehdolla löytyi liikaa tuotteita!");
			$varaosavirhe 	.= t("Näytetään 200 ensimmäistä").".";
			$tee 			= 'Y';
		}

		if (count($rows) == 0) {
			$kentta			= 'tuoteno';
			$varaosavirhe 	= t("VIRHE: Hakuehdolla ei löytynyt yhtään tuotetta")."!";
			$tuoteno		= '';
			$tee 			= 'Y';
		}
		elseif (count($rows) == 1) {
			$trow = array_pop($rows);
			
			if($konsernihaku == "KYLLA") {
				$tuoteno = $trow['tuoteno']."####$trow[yhtio]";
			}
			else {
				$tuoteno = $trow['tuoteno'];
			}			
		}
		elseif (count($rows) != 1) {

			if ($multi == "" and $kutsuja == "tuoteperhe.php") {
				$ulos = "<select name='hakutuoteno2'>";
			}
			elseif ($multi == "" and $kutsuja != "yllapito.php") {
				$ulos = "<select name='tuoteno'>";
			}
			elseif ($kutsuja != "yllapito.php") {
				$ulos = "<select name='tuoteno_array[]' multiple='TRUE' size='6'>";
			}
			
			if ($yhtiorow["saldo_kasittely"] == "T") {
				$saldoaikalisa = date("Y-m-d");
			}
			else {
				$saldoaikalisa = "";
			}
			
			$ulos .= "<option value=''>".t("Valitse tuote")."</option>";

			// Loopataan tuotteet läpi
			foreach($rows as $trow) {

				$lisakala1 = "";

				if ($trow["yhtio"].$trow["korvaavat"] == $edtuoteno) {
					$lisakala1 = "* ";
				}

				$kokonaismyytavissa = 0;
				
				if ($trow["tuoteperhe"] == $trow["tuoteno"]) {
					// Tuoteperheen isä
					$saldot = tuoteperhe_myytavissa($trow["tuoteno"], "", "", $laskurow["varasto"], "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

					foreach ($saldot as $varasto => $myytavissa) {
						$kokonaismyytavissa += $myytavissa;
					}					
				}
				elseif ($trow['ei_saldoa'] != '') {
					$kokonaismyytavissa = t("On");
				}
				else {
					foreach($konsyhtiot as $yhtio) {
						list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($trow["tuoteno"], '', $laskurow["varasto"], $yhtio, '', '', '', '', '', $saldoaikalisa);
						$kokonaismyytavissa += $myytavissa;
					}	
				}

				if ($kukarow['extranet'] != '' and $kokonaismyytavissa > 0) {
					$kokonaismyytavissa = t("On");
				}
				elseif ($kukarow['extranet'] != '') {
					$kokonaismyytavissa = t("Ei");
				}
				
				$lisakala2 = "";
				$lisakala3 = "";

				if($konsernihaku == "KYLLA") {
					$lisakala2 = " $trow[yhtio] - ";
					$lisakala3 = "####$trow[yhtio]";
				}

				$ulos .= "<option value='$trow[tuoteno]$lisakala3'>$lisakala2 $lisakala1 $trow[tuoteno] ".asana('nimitys_',$trow['tuoteno'],$trow['nimitys'])." ($kokonaismyytavissa)</option>";

				$edtuoteno = $trow["yhtio"].$trow["korvaavat"];
			}

			if ($kutsuja != "yllapito.php") {
				$ulos .= "</select>";
				$tee = 'Y';
			}
		}
	}
	elseif ((substr($tuoteno,0,1) == '*' or substr($tuoteno,-1) == '*' or substr($tuoteno,0,1) == '?') and trim(strlen($tuoteno)) < 3) {
		$kentta			= 'tuoteno';
		$varaosavirhe 	= t("VIRHE: Hakusana pitää sisältää tähden(*) lisäksi vähintään kaksi(2) merkkiä")."!<br>";
		$tuoteno		= '';
		$tee 			= 'Y';
	}

	if ($ulos == '' and $tee != 'Y') {

		///* Viivakoodinlukija-special *///
		///* Konffaa lukija näin: prefiksiksi #-merkki ja suffiksiksi RETURN*///
		if (substr($tuoteno,0,1) == '#') {

			$lisa1 = "and eankoodi='".substr($tuoteno,1)."'";
			
			$query = "	SELECT tuoteno
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]' $lisa1";
			$tresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tresult) > 1) {
				$kentta='tuoteno';
				$varaosavirhe = t("Useita tuotteita samalla koodilla")."!<br>";
				$tuoteno='';
				$tee = 'Y';
			}
			elseif (mysql_num_rows($tresult) == 0) {
				$kentta='tuoteno';
				$varaosavirhe = t("VIRHE: Hakuehdolla ei löytynyt yhtään tuotetta")."!<br>";
				$tuoteno='';
				$tee = 'Y';
			}
			elseif (mysql_num_rows($tresult) == 1) {
				//Tehdään tuotenumero ja kpl
				$trivi = mysql_fetch_array($tresult);
				$tuoteno = $trivi['tuoteno'];
			}
		}

		if(strpos($tuoteno, '####') !== FALSE and $konsernihaku == "KYLLA") {
			$hakyhtio	= substr($tuoteno, strpos($tuoteno, '####')+4);
			$tuoteno 	= substr($tuoteno, 0, strpos($tuoteno, '####'));
		}
		else {
			$hakyhtio 	= $kukarow["yhtio"];
		}

		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '$hakyhtio' and tuoteno = '$tuoteno'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			$kentta = 'tuoteno';
			
			if (strpos($_SERVER['SCRIPT_NAME'], "tilaus_myynti.php") === FALSE or $kukarow['extranet'] != '') {
				$varaosavirhe .= t("VIRHE: Tuotenumeroa")." $tuoteno ".t("ei löydy järjestelmästä")."!<br>";
			}
		
			$tee = "Y";
		}
		else {
			$trow = mysql_fetch_array($result);

			if($konsernihaku == "KYLLA") {
				$tuoteno = $trow['tuoteno']."####".$trow["yhtio"];
			}
			else {
				$tuoteno = $trow['tuoteno'];
			}
		}
	}

?>
