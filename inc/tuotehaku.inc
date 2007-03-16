<?php

 	// Nyt me selataan
	if ((substr($tuoteno,0,1) == '*' or substr($tuoteno,-1) == '*') and trim(strlen($tuoteno)) > 2) {

		$lisa1 = '';
		$ulos  = '';

		// jos eka merkki on tähti etsitään teksteistä
		if (substr($tuoteno,0,1) == '*') {

			$tuoteno = substr($tuoteno,1);
			$sanat = split(" ", $tuoteno);

			foreach ($sanat as $sana) {
				$lisa1 .= " and concat_ws(' ', tuote.nimitys, tuote.lyhytkuvaus, tuote.tuotemerkki) like '%" . trim($sana) . "%' ";
			}
		}

		// jos vika merkki on tähti niin etsitään osittainen tuotenumero
		if (substr($tuoteno,-1) == '*') {
			$tuoteno = substr($tuoteno,0,-1);
			$lisa1 = "and tuote.tuoteno like '" . $tuoteno . "%'";
		}


		//Yhtiövalinnat
		$query	= "	SELECT distinct yhtio, nimi
					from yhtio
					where konserni = '$yhtiorow[konserni]' and konserni != ''";
		$presult = mysql_query($query) or pupe_error($query);

		$yhtiot 	= "";
		$konsyhtiot = array();

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


		//Voidaan hakea myös koko konsernin tuotteista
		if($konsernihaku != "KYLLA") {
			$yhtiot = "yhtio = '$kukarow[yhtio]' ";
		}

		// Poistettuja tuotteita ei näytetä paitsi jos niillä on saldoa
		// Joinataan korvaavat mukaan
		$query = "	SELECT tuote_wrapper.*, valitut.sorttauskentta
					FROM tuote tuote_wrapper,
					(SELECT if(korvaavat.id>0,(select concat(korvaavat.tuoteno,korvaavat.yhtio) from korvaavat korva2 use index (yhtio_id) where korva2.yhtio=korvaavat.yhtio and korva2.id=korvaavat.id ORDER BY jarjestys LIMIT 1),concat(tuote.tuoteno,tuote.yhtio)) sorttauskentta,
					ifnull(korvaavat.tuoteno, tuote.tuoteno) tuoteno, tuote.yhtio, sum(tuotepaikat.saldo) saldo
					FROM tuote
					LEFT JOIN korvaavat use index (yhtio_id) ON korvaavat.yhtio=tuote.yhtio and korvaavat.id = (select id from korvaavat use index (yhtio_tuoteno) where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1)
					LEFT JOIN tuotepaikat use index (tuote_index) ON tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno
					WHERE tuote.$yhtiot
					$lisa1
					GROUP BY 1,2,3
					ORDER BY sorttauskentta, tuoteno) valitut
					WHERE tuote_wrapper.yhtio = valitut.yhtio
					and tuote_wrapper.tuoteno = valitut.tuoteno
					LIMIT 201";
		$thresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($thresult) >  200) {
			$kentta			= 'tuoteno';
			$varaosavirhe 	= t("VIRHE: Hakuehdolla löytyi liikaa tuotteita!");
			$varaosavirhe 	.= t("Näytetään 200 ensimmäistä").".";
			$tee 			= 'Y';
		}

		if (mysql_num_rows($thresult) == 0) {
			$kentta			= 'tuoteno';
			$varaosavirhe 	= t("VIRHE: Hakuehdolla ei löytynyt yhtään tuotetta")."!";
			$tuoteno		= '';
			$tee 			= 'Y';
		}
		elseif (mysql_num_rows($thresult) == 1) {
			$trow = mysql_fetch_array($thresult);
			
			if($konsernihaku == "KYLLA") {
				$tuoteno = $trow['tuoteno']."####$trow[yhtio]";
			}
			else {
				$tuoteno = $trow['tuoteno'];
			}
		}
		elseif (mysql_num_rows($thresult) != 1) {

			if ($multi == "") {
				$ulos = "<select name='tuoteno'>";
			}
			else {
				$ulos = "<select name='tuoteno_array[]' multiple='TRUE' size='6'>";
			}

			// Loopataan tuotteet läpi
			while ($trow = mysql_fetch_array($thresult)) {

				$lisakala1 = "";

				if ($trow["sorttauskentta"] == $edtuoteno) {
					$lisakala1 = "* ";
				}

				$saldo = 0;

				foreach($konsyhtiot as $yhtio) {
					$saldo += saldo_myytavissa($trow["tuoteno"], "", 0, $yhtio);
				}

				if ($kukarow['extranet'] != '' and $saldo > 0) {
					$saldo = t("On");
				}
				elseif ($kukarow['extranet'] != '') {
					$saldo = t("Ei");
				}

				$lisakala2 = "";
				$lisakala3 = "";

				if($konsernihaku == "KYLLA") {
					$lisakala2 = " $trow[yhtio] - ";
					$lisakala3 = "####$trow[yhtio]";
				}

				$ulos .= "<option value='$trow[tuoteno]$lisakala3'>$lisakala2 $lisakala1 $trow[tuoteno] ".asana('nimitys_',$trow['tuoteno'],$trow['nimitys'])." ($saldo)</option>";

				$edtuoteno = $trow["sorttauskentta"];
			}

			$ulos .= "</select>";
			$tee = 'Y';
		}

	}
	elseif ((substr($tuoteno,0,1) == '*' or substr($tuoteno,-1) == '*') and trim(strlen($tuoteno)) < 3) {
		$kentta='tuoteno';
		$varaosavirhe = t("VIRHE: Hakusana pitää sisältää tähden(*) lisäksi vähintään kaksi(2) merkkiä")."!<br>";
		$tuoteno='';
		$tee = 'Y';
	}

	if ($ulos == '' and $tee != 'Y') {

		///* Viivakoodinlukija-special *///
		///* Konffaa lukija näin: prefiksiksi #-merkki ja suffiksiksi RETURN*///
		///* Toimittajan tuotenumerospecial* myös täällä///

		if (substr($tuoteno,0,1) == '#' or substr($tuoteno,0,1) == '?') {

			$lisa1 = '';

 				///* Toimittajan tuotenumerospecial*///
			if (substr($tuoteno,0,1) == '?') {

				$query = "select * from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and toim_tuoteno='".substr($tuoteno,1)."'";
				$tresult = mysql_query($query) or pupe_error($query);

				while ($toimirivi = mysql_fetch_array($tresult)) {
					$lisa1 .= "'$toimirivi[tuoteno]',";
				}

				if ($lisa1 != "") {
					$lisa1 = "and tuoteno in (".substr($lisa1,0,-1).")"; // vika pilkku pois
				}
			}

			if (substr($tuoteno,0,1) == '#') {
				$lisa1 = "and eankoodi='".substr($tuoteno,1)."'";
			}

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
			$varaosavirhe .= t("VIRHE: Tuotenumeroa")." $tuoteno ".t("ei löydy järjestelmästä")."!<br>";
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
