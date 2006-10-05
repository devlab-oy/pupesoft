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
		
		$query = "	SELECT tuote.tuoteno, tuote.nimitys, sum(saldo) saldo, status
					FROM tuote
					LEFT JOIN tuotepaikat ON tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.yhtio=tuote.yhtio
					WHERE tuote.yhtio = '$kukarow[yhtio]' 
					$lisa1
					GROUP BY tuote.tuoteno
					HAVING status != 'P' or saldo > 0
					ORDER BY tuote.tuoteno";
		$thresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($thresult) >  200) {
			$kentta='tuoteno';
			$varaosavirhe = t("VIRHE: Hakuehdolla löytyi liikaa tuotteita!")."";
			$tuoteno='';
			$tee = 'Y';
		}
		elseif (mysql_num_rows($thresult) == 0) {
			$kentta='tuoteno';
			$varaosavirhe = t("VIRHE: Hakuehdolla ei löytynyt yhtään tuotetta")."!";
			$tuoteno='';
			$tee = 'Y';
		}
		elseif (mysql_num_rows($thresult) == 1) {
			$trow = mysql_fetch_array($thresult);
			$tuoteno = $trow['tuoteno'];
		}
		elseif (mysql_num_rows($thresult) != 1) {

			//Tehdaan pop-up valmiiksi myohempaa kayttoa varten
			//katotaan mistä varastoista saldot saa myydä
			$query = "	select * 
						from varastopaikat 
						where yhtio = '$kukarow[yhtio]'";
			$varastoresult = mysql_query($query) or pupe_error($query);
			
			if ($multi == "") {
				$ulos = "<select name='tuoteno'>";
			}
			else {
				$ulos = "<select name='tuoteno_array[]' multiple='TRUE' size='6'>";
			}
			
			while ($trow = mysql_fetch_array($thresult)) {
				
				// jos kyseessä ei ole extranetkäyttäjä
				if ($kukarow['extranet'] == '') {
					
					mysql_data_seek($varastoresult,0);
					
					$vapaana2 = 0;
					
					while ($varastorow = mysql_fetch_array ($varastoresult)) {
						
						if ($varastorow["tyyppi"] != "E" or $laskurow["varasto"] == $varastorow["tunnus"]) {
							//saldo
							$query = "	select sum(saldo) saldo 
										from tuotepaikat 
										where tuoteno = '$trow[tuoteno]' 
										and yhtio = '$kukarow[yhtio]'
										and hyllyalue >= '$varastorow[alkuhyllyalue]' 
										and hyllynro >= '$varastorow[alkuhyllynro]' 
										and hyllyalue <= '$varastorow[loppuhyllyalue]' 
										and hyllynro <= '$varastorow[loppuhyllynro]'";
							$alkuresult = mysql_query($query) or pupe_error($query);
							$alkurow = mysql_fetch_array($alkuresult);

							//ennakkopoistot
							$query = "	SELECT sum(varattu) varattu
										FROM tilausrivi
										WHERE tyyppi in ('L','G','V') 
										and yhtio = '$kukarow[yhtio]' 
										and tuoteno = '$trow[tuoteno]' 
										and varattu > 0
										and hyllyalue >= '$varastorow[alkuhyllyalue]' 
										and hyllynro >= '$varastorow[alkuhyllynro]' 
										and hyllyalue <= '$varastorow[loppuhyllyalue]' 
										and hyllynro <= '$varastorow[loppuhyllynro]'";
							$varatutresult = mysql_query($query) or pupe_error($query);
							$varatutrow = mysql_fetch_array($varatutresult);

							$vapaana = $alkurow["saldo"] - $varatutrow["varattu"];
							$vapaana2 += $vapaana;
						}
					}
					
					$ulos .= "<option value='$trow[0]'>$trow[0] $trow[1] ($vapaana2)</option>";

				}
				else {
					$ulos .= "<option value='$trow[0]'>$trow[0] $trow[1]</option>";
				}

				//ja sama uudestaan korvaaville
				$query = "select id from korvaavat where yhtio = '$kukarow[yhtio]' and tuoteno = '$trow[tuoteno]'";
				$korvidresult = mysql_query($query) or pupe_error($query);
				$korvidrow = mysql_fetch_array ($korvidresult);
				
				$query = "select tuoteno from korvaavat where yhtio = '$kukarow[yhtio]' and tuoteno != '$trow[tuoteno]' and id = '$korvidrow[id]'";
				$korvresult = mysql_query($query) or pupe_error($query);
					
				while ($korvrow = mysql_fetch_array ($korvresult)) {
					
					$query = "select nimitys from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$korvrow[tuoteno]'";
					$korvnimresult = mysql_query($query) or pupe_error($query);
					$korvnimrow = mysql_fetch_array ($korvnimresult);
					
					// jos kyseessä ei ole extranetkäyttäjä
					if ($kukarow['extranet'] == '') {
						
						$vapaana2 = 0;
						mysql_data_seek($varastoresult,0);
						
						while ($varastorow = mysql_fetch_array ($varastoresult)) {

							//saldo
							$query = "select sum(saldo) saldo from tuotepaikat where tuoteno='$korvrow[tuoteno]' and yhtio='$kukarow[yhtio]'
							and hyllyalue >= '$varastorow[alkuhyllyalue]' and hyllynro >= '$varastorow[alkuhyllynro]' and hyllyalue <= '$varastorow[loppuhyllyalue]' and hyllynro <= '$varastorow[loppuhyllynro]'";
							$alkuresult = mysql_query($query) or pupe_error($query);
							$alkurow = mysql_fetch_array($alkuresult);

							//ennakkopoistot
							$query = "	SELECT sum(varattu) varattu
										FROM tilausrivi
										WHERE tyyppi in ('L','G','V') 
										and yhtio = '$kukarow[yhtio]' 
										and tuoteno = '$korvrow[tuoteno]' 
										and varattu > 0
										and hyllyalue >= '$varastorow[alkuhyllyalue]' 
										and hyllynro >= '$varastorow[alkuhyllynro]' 
										and hyllyalue <= '$varastorow[loppuhyllyalue]' 
										and hyllynro <= '$varastorow[loppuhyllynro]'";
							$varatutresult = mysql_query($query) or pupe_error($query);
							$varatutrow = mysql_fetch_array($varatutresult);

							$vapaana = $alkurow["saldo"] - $varatutrow["varattu"];
							$vapaana2 += $vapaana;
						}
						$ulos .= "<option value='$korvrow[0]'>--&gt; $korvrow[0] $korvnimrow[nimitys] ($vapaana2)</option>";
					}
					else {
						$ulos .= "<option value='$korvrow[0]'>--&gt; $korvrow[0] $korvnimrow[nimitys]</option>";
					}
				}
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

		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			$kentta = 'tuoteno';
			$varaosavirhe .= t("VIRHE: Tuotenumeroa")." $tuoteno ".t("ei löydy järjestelmästä")."!<br>";
			$tee = "Y";
		}
		else {
			$trow = mysql_fetch_array($result);
			$tuoteno = $trow['tuoteno'];
		}
	}
	
?>
