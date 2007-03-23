<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "muuvarastopaikka.php")  !== FALSE) {
		require("inc/parametrit.inc");
	}

	if ($tee != '') {
		$query  = "LOCK TABLE tuotepaikat WRITE, tapahtuma WRITE, sanakirja WRITE, tuote READ, varastopaikat READ, tilausrivi READ, tilausrivi as tilausrivi_osto READ, sarjanumeroseuranta WRITE";
		$result = mysql_query($query) or pupe_error($query);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "muuvarastopaikka.php")  !== FALSE) {
		echo "<font class='head'>".t("Tuotteen varastopaikat")."</font><hr>";
	}
	
	// Seuraava ja edellinen napit
	if ($tee == 'S' or $tee == 'E') {
		if ($tee == 'S') {
			$oper = '>';
			$suun = '';
		}
		else {
			$oper = '<';
			$suun = 'desc';
		}

		$query = "	SELECT tuote.tuoteno, sum(saldo) saldo, status
					FROM tuote
					LEFT JOIN tuotepaikat ON tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.yhtio=tuote.yhtio
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno " . $oper . " '$tuoteno'
					GROUP BY tuote.tuoteno
					HAVING status != 'P' or saldo > 0
					ORDER BY tuote.tuoteno " . $suun . "
					LIMIT 1";
		$result = mysql_query($query) or pupe_error($query);
	
		if (mysql_num_rows($result) > 0) {
			$trow = mysql_fetch_array ($result);
			$tuoteno 	= $trow['tuoteno'];
			$tee		='M';
		}
		else {
			$varaosavirhe = t("Yht‰‰n tuotetta ei lˆytynyt")."!";
			$tuoteno 	= '';
			$tee		='Y';
		}
	}
	
	
	// Itse varastopaikkoja muutellaan
	if ($tee == 'MUUTA') {
		$query = "	SELECT tunnus
					FROM tuotepaikat
					WHERE tuoteno = '$tuoteno'
					and yhtio 	= '$kukarow[yhtio]'
					and oletus != ''";
		$result = mysql_query($query) or pupe_error($query);
		$oletusrow = mysql_fetch_array($result);
		
		// Saldot per varastopaikka
		$query = "	SELECT tuotepaikat.*, 
					varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi,
					concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
		 			FROM tuotepaikat
					LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
					and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
					WHERE tuotepaikat.yhtio = '$kukarow[yhtio]' 
					and tuotepaikat.tuoteno = '$tuoteno'
					ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
		$paikatresult1 = mysql_query($query) or pupe_error($query);
				
		$saldot = array();
		
		if (mysql_num_rows($paikatresult1) > 0) {
			while ($saldorow = mysql_fetch_array ($paikatresult1)) {
				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, '', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"]);
				
				$saldot[$saldorow["tunnus"]] 	= $saldo;
				$hyllyalue[$saldorow["tunnus"]] = $saldorow["hyllyalue"];
				$hyllynro[$saldorow["tunnus"]] 	= $saldorow["hyllynro"];
				$hyllyvali[$saldorow["tunnus"]] = $saldorow["hyllyvali"];
				$hyllytaso[$saldorow["tunnus"]] = $saldorow["hyllytaso"];
			}
		}
		
		// K‰yd‰‰n l‰pi poistettavat paikat
		if (count($poista) > 0) {
			foreach ($poista as $poistetaan) {
				if ($poistetaan == $oletusrow["tunnus"] and ($saldot[$poistetaan] != 0 or count($saldot) > 1)) {
					echo "<font class='error'>".t("Et voi poistaa oletuspaikkaa, koska sill‰ on saldoa tai tuotteella on muitakin paikkoja")."</font><br><br>";
				}	
				elseif($saldot[$poistetaan] != 0) {
					echo "<font class='error'>".t("Et voi poistaa paikkaa jolla on saldoa")."</font><br><br>";	
				}
				else {
					echo "<font class='message'>".t("Poistetaan varastopaikka")." $hyllyalue[$poistetaan] $hyllynro[$poistetaan] $hyllyvali[$poistetaan] $hyllytaso[$poistetaan]</font><br><br>";

					$query = "	INSERT into tapahtuma set
								yhtio 		= '$kukarow[yhtio]',
								tuoteno 	= '$tuoteno',
								kpl 		= '0',
								kplhinta	= '0',
								hinta 		= '0',
								laji 		= 'poistettupaikka',
								selite 		= '".t("Poistettiin tuotepaikka")." $hyllyalue[$poistetaan] $hyllynro[$poistetaan] $hyllyvali[$poistetaan] $hyllytaso[$poistetaan]',
								laatija 	= '$kukarow[kuka]',
								laadittu 	= now()";
					$result = mysql_query($query) or pupe_error($query);

					$query = "	DELETE FROM tuotepaikat
								WHERE tuoteno 	= '$tuoteno' 
								and yhtio 		= '$kukarow[yhtio]' 
								and tunnus 		= '$poistetaan'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}
		}
		
		// Oletuspaikka vaihdettiin
		if ($oletus != $oletusrow["tunnus"]) { 
			$query = "	SELECT *
						FROM tuotepaikat
						WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$oletus'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				// Tehd‰‰n p‰ivitykset
				echo "<font class='message'>".t("Siirret‰‰n oletuspaikka")."</font><br><br>";

				$query = "	UPDATE tuotepaikat SET oletus = ''
							WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);

				$query = "	UPDATE tuotepaikat SET oletus = 'X'
							WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$oletus'";
				$result = mysql_query($query) or pupe_error($query);
			}
			else {
				echo "<font class='error'>".t("Uusi oletuspaikka on kadonnut")."</font><br><br>";
			}
		}

		if (count($halyraja2) > 0) {
			foreach ($halyraja2 as $tunnus => $halyraja) {
				$query = "	UPDATE tuotepaikat SET halytysraja = '$halyraja'
							WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
				$result = mysql_query($query) or pupe_error($query);
			}
		}

		if (count($tilausmaara2) > 0) {
			foreach ($tilausmaara2 as $tunnus => $tilausmaara) {
				$query = "	UPDATE tuotepaikat SET tilausmaara = '$tilausmaara'
							WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
		
		$ahyllyalue	= '';
		$ahyllynro	= '';
		$ahyllyvali	= '';
		$ahyllytaso	= '';
		$tee = 'M';
	}


	// Siirret‰‰n saldo, jos se on viel‰ olemassa
	if ($tee == 'N') { 
		if ($mista == $minne) {
			echo "<font class='error'>".t("Kummatkin paikat ovat samat")."!</font><br><br>";

			if ($kutsuja == 'vastaanota.php') {
				$tee = 'X';
			}
			else {
				$tee = 'M';
			}
		}
		$asaldo = (float) str_replace( ",", ".", $asaldo);
		
		if ($asaldo == 0) {
			echo "<font class='error'>".t("Anna siirrett‰v‰ m‰‰r‰")."!</font><br><br>";

			if ($kutsuja == 'vastaanota.php') {
				$tee = 'X';
			}
			else {
				$tee = 'M';
			}
		}
	}

	if ($tee == 'N') {

		// Tarvitsemme
		// $asaldo  = siirrett‰v‰ m‰‰r‰
		// $mista   = tuotepaikan tunnus josta otetaan
		// $minne   = tuotepaikan tunnus jonne siirret‰‰n
		// $tuoteno = tuotenumero jota siirret‰‰n

		if ($kutsuja == "vastaanota.php") {
			$uusitee = "X";
		}
		else {
			$uusitee = "M";
		}
		
		$tuotteet 	= array(0 => $tuoteno);
		$kappaleet 	= array(0 => $asaldo); 
		$lisavaruste= array(0 => "");
		
		// Mist‰ varastosta otetaan?
		$query = "	SELECT *
					FROM tuotepaikat
					WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$mista'";
		$result = mysql_query($query) or pupe_error($query);
		$mistarow = mysql_fetch_array($result);

		if (mysql_num_rows($result) == 0) {
			$mista = str_replace ( "#", " ", $mista);
			echo "<font class='error'>".t("T‰m‰ varastopaikka katosi tuotteelta")." $mista</font><br><br>";
			$tee = $uusitee;
		}

		// Minne varastoon vied‰‰n?
		$query = "	SELECT *
					FROM tuotepaikat
					WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$minne'";
		$result = mysql_query($query) or pupe_error($query);
		$minnerow = mysql_fetch_array($result);

		if (mysql_num_rows($result) == 0) {
			$minne = str_replace ( "#", " ", $minne);
			echo "<font class='error'>".t("T‰m‰ varastopaikka katosi tuotteelta")." $minne</font><br><br>";
			$tee = $uusitee;
		}
		
		// Tarkistetaan sarjanumeroseuranta
		$query = "	SELECT tunnus
					FROM tuote
					WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and sarjanumeroseuranta!=''";
		$sarjaresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sarjaresult) > 0 and (!is_array($sarjano_array) or count($sarjano_array) != $asaldo)) {
			echo "<font class='error'>".t("Tarkista sarjanumerovalintasi")."!</font><br><br>";
			$tee = $uusitee;
		}
		elseif (mysql_num_rows($sarjaresult) > 0 and $kutsuja != "vastaanota.php") {
			foreach($sarjano_array as $sarjatun) {
				//Tutkitaan lis‰varusteita
				$query = "	SELECT tilausrivi_osto.perheid2
							FROM sarjanumeroseuranta 
							JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus and tilausrivi_osto.tunnus=tilausrivi_osto.perheid2
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]' 
							and sarjanumeroseuranta.tunnus  = '$sarjatun'";
				$sarjares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sarjares) == 1) {
					$sarjarow = mysql_fetch_array($sarjares);

					// Ostorivi
					$query = "	SELECT if(kpl!=0, kpl, tilkpl) kpl
								FROM tilausrivi 
								WHERE yhtio  = '$kukarow[yhtio]' 
								and tunnus   = '$sarjarow[perheid2]'
								and perheid2!= 0";
					$sarjares = mysql_query($query) or pupe_error($query);
					$ostorow = mysql_fetch_array($sarjares);

					// Haetaan muut lis‰varusteet
					$query = "	SELECT tuoteno, round(kpl/$ostorow[kpl]) kpl, round(tilkpl/$ostorow[kpl]) tilkpl, tilkpl sistyomaarayskpl, var, tyyppi, concat_ws('#', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka
								FROM tilausrivi 
								WHERE yhtio  = '$kukarow[yhtio]' 
								and perheid2 = '$sarjarow[perheid2]'
								and tyyppi  != 'D'
								and tunnus  != '$sarjarow[perheid2]'
								and perheid2!= 0";
					$sarjares = mysql_query($query) or pupe_error($query);
					
					while ($lisavarrow = mysql_fetch_array($sarjares)) {
						if ($lisavarrow["tyyppi"] == 'G' and $lisavarrow["sistyomaarayskpl"] > 0) {
							$tuotteet[]	 	= $lisavarrow["tuoteno"];
							$kappaleet[] 	= $lisavarrow["sistyomaarayskpl"];
							$lisavaruste[]	= "LISAVARUSTE";
						}
						elseif ($lisavarrow["var"] != 'P' and $lisavarrow["kpl"] > 0) {
							$tuotteet[]	 	= $lisavarrow["tuoteno"];
							$kappaleet[] 	= $lisavarrow["kpl"];
							$lisavaruste[]	= "LISAVARUSTE";
						}
					}
				}
			}
		}
		
		//t‰h‰n erroriin tullaan vain jos kyseess‰ ei ole siirtolista
		//koska jos meill‰ on ker‰tty siirtolista niin ne m‰‰r‰t myˆs halutaan siirt‰‰ vaikka saldo menisikin nollille tai miinukselle
		if ($kutsuja != "vastaanota.php") {
		
			$saldook = 0;
		
			for($iii=0; $iii< count($tuotteet); $iii++) {
				
				// Tutkitaan lis‰vausteiden tuotepaikkoja
				$query = "	SELECT *
							FROM tuotepaikat
							WHERE tuoteno 	= '$tuotteet[$iii]' 
							and yhtio 		= '$kukarow[yhtio]' 
							and hyllyalue	= '$mistarow[hyllyalue]'
							and hyllynro 	= '$mistarow[hyllynro]'
							and hyllyvali 	= '$mistarow[hyllyvali]'
							and hyllytaso	= '$mistarow[hyllytaso]'";
				$result = mysql_query($query) or die($query);
				
				if (mysql_num_rows($result) == 1) {
					list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuotteet[$iii], '', '', '', $mistarow["hyllyalue"], $mistarow["hyllynro"], $mistarow["hyllyvali"], $mistarow["hyllytaso"]);
				
					if ($kappaleet[$iii] > $myytavissa) {
						echo "Tuotetta ei voida siirt‰‰. Saldo ei riitt‰nyt. $tuotteet[$iii] $kappaleet[$iii] ($mistarow[hyllyalue] $mistarow[hyllynro] $mistarow[hyllyvali] $mistarow[hyllytaso])<br>";
						$saldook++;
					}
				}
				else {
					echo t("Tuotetta ei voida siirt‰‰. Tuotetta ei lˆytynyt paikalta").": $tuotteet[$iii] ($mistarow[hyllyalue] $mistarow[hyllynro] $mistarow[hyllyvali] $mistarow[hyllytaso])<br>";
					$saldook++;
				}
			}
			
			
			if ($saldook == 0) { 
				for($iii=0; $iii< count($tuotteet); $iii++) {						
					$query = "	SELECT *
								FROM tuotepaikat
								WHERE tuoteno 	= '$tuotteet[$iii]' 
								and yhtio 		= '$kukarow[yhtio]' 
								and hyllyalue	= '$minnerow[hyllyalue]'
								and hyllynro 	= '$minnerow[hyllynro]'
								and hyllyvali 	= '$minnerow[hyllyvali]'
								and hyllytaso	= '$minnerow[hyllytaso]'";
					$result = mysql_query($query) or die($query);
			
					// Vastaanottavaa paikkaa ei lˆydy, perustetaan se
					if (mysql_num_rows($result) == 0) {
						$query = "INSERT into tuotepaikat (yhtio, hyllyalue, hyllynro, hyllyvali, hyllytaso, tuoteno)
								  VALUES (
									'$kukarow[yhtio]',
									'$minnerow[hyllyalue]',
									'$minnerow[hyllynro]',
									'$minnerow[hyllyvali]',
									'$minnerow[hyllytaso]',
									'$tuotteet[$iii]')";
						$result = mysql_query($query) or die($query);

						$query = "	INSERT into tapahtuma set
									yhtio 		= '$kukarow[yhtio]',
									tuoteno 	= '$tuotteet[$iii]',
									kpl 		= '0',
									kplhinta	= '0',
									hinta 		= '0',
									laji 		= 'uusipaikka',
									selite 		= '".t("Lis‰ttiin tuotepaikka")." $minnerow[hyllyalue] $minnerow[hyllynro] $minnerow[hyllyvali] $minnerow[hyllytaso]',
									laatija 	= '$kukarow[kuka]',
									laadittu 	= now()";
						$result = mysql_query($query) or die($query);

						echo t("Uusi varastopaikka luotiin tuotteelle").": $tuotteet[$iii] ($minnerow[hyllyalue] $minnerow[hyllynro] $minnerow[hyllyvali] $minnerow[hyllytaso])<br>";
					}
				}
			}
			
			if ($saldook > 0) { //Taravat myytiin alta!
				echo "<font class='error'>".t("Siirett‰v‰ m‰‰r‰ on liian iso")."</font><br><br>";
				$tee = $uusitee;
			}
		}	
	}
	
	if ($tee == 'N') {

		if ($kutsuja == "vastaanota.php") {
			$uusitee = "OK";
		}
		else {
			$uusitee = "M";
		}
		
		for($iii=0; $iii< count($tuotteet); $iii++) {

			if($lisavaruste[$iii] == "LISAVARUSTE") {
				$lisavarlisa1 = " saldo_varattu = saldo_varattu - $kappaleet[$iii], ";
				$lisavarlisa2 = " saldo_varattu = saldo_varattu + $kappaleet[$iii], ";
			}
			else {
				$lisavarlisa1 = "";
				$lisavarlisa2 = "";
			}
			
			// Mist‰ varastosta otetaan?
			$query = "	UPDATE tuotepaikat 
						set saldo = saldo - $kappaleet[$iii], 
						$lisavarlisa1
						saldoaika=now()
	                  	WHERE tuoteno 	= '$tuotteet[$iii]' 
						and yhtio 		= '$kukarow[yhtio]' 
						and tunnus		= '$mista'";
			$result = mysql_query($query) or pupe_error($query);
			
			// Minne varastoon vied‰‰n?
			$query = "	UPDATE tuotepaikat 
						set saldo = saldo + $kappaleet[$iii], 
						$lisavarlisa2
						saldoaika=now()
						WHERE tuoteno 	= '$tuotteet[$iii]' 
						and yhtio 		= '$kukarow[yhtio]' 
						and tunnus		= '$minne'";
			$result = mysql_query($query) or pupe_error($query);
			
			$minne = $minnerow['hyllyalue']." ".$minnerow['hyllynro']." ".$minnerow['hyllyvali']." ".$minnerow['hyllytaso'];
			$mista = $mistarow['hyllyalue']." ".$mistarow['hyllynro']." ".$mistarow['hyllyvali']." ".$mistarow['hyllytaso'];
		
			if (($kutsuja == 'vastaanota.php' and $toim == 'MYYNTITILI') or ($kutsuja != 'vastaanota.php')) {
				$query = "	INSERT into tapahtuma set
							yhtio 		= '$kukarow[yhtio]',
							tuoteno 	= '$tuotteet[$iii]',
							kpl 		= $kappaleet[$iii] * -1,
							hinta 		= '0',
							laji 		= 'siirto',
							selite 		= '".t("Paikasta")." $mista ".t("v‰hennettiin")." $kappaleet[$iii]',
							laatija 	= '$kukarow[kuka]',
							laadittu 	= now()";
				$result = mysql_query($query) or pupe_error($query);
			}
		
			$query = "	INSERT into tapahtuma set
						yhtio 		= '$kukarow[yhtio]',
						tuoteno 	= '$tuotteet[$iii]',
						kpl 		= '$kappaleet[$iii]',
						hinta 		= '0',
						laji 		= 'siirto',
						selite 		= '".t("Paikalle")." $minne ".t("lis‰ttiin")." $kappaleet[$iii]',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now()";
			$result = mysql_query($query) or pupe_error($query);
			
			// P‰ivitet‰‰n sarjanumerot
			if (mysql_num_rows($sarjaresult) > 0) {
				foreach($sarjano_array as $sarjano) {
					$query = "	UPDATE sarjanumeroseuranta 
								set hyllyalue	= '$minnerow[hyllyalue]',
								hyllynro 		= '$minnerow[hyllynro]',
								hyllyvali 		= '$minnerow[hyllyvali]',
								hyllytaso		= '$minnerow[hyllytaso]'
								WHERE tuoteno 	= '$tuotteet[$iii]' 
								and yhtio 		= '$kukarow[yhtio]' 
								and tunnus		= '$sarjano'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}
		}
		
		//P‰ivitet‰‰n sarjanumerot
		if (mysql_num_rows($sarjaresult) > 0) {
			foreach($sarjano_array as $sarjano) {
				$query = "	UPDATE sarjanumeroseuranta 
							set hyllyalue	= '$minnerow[hyllyalue]',
							hyllynro 		= '$minnerow[hyllynro]',
							hyllyvali 		= '$minnerow[hyllyvali]',
							hyllytaso		= '$minnerow[hyllytaso]'
							WHERE tuoteno = '$tuotteet[$iii]' 
							and yhtio = '$kukarow[yhtio]' 
							and tunnus='$sarjano'";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
		
		$ahyllyalue = '';
		$ahyllynro  = '';
		$ahyllyvali = '';
		$ahyllytaso = '';
		$asaldo     = '';
		$tee = $uusitee;
	}

	// Uusi varstopaikka
	if ($tee == 'UUSIPAIKKA') { 
		
		$ahyllyalue = trim($ahyllyalue);
		$ahyllynro  = trim($ahyllynro);
		$ahyllyvali = trim($ahyllyvali);
		$ahyllytaso = trim($ahyllytaso);
		
		//Tarkistetaan onko paikka validi
		list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, '', '', '', $ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso);
		
		if ($saldo === FALSE) {
			if (kuuluukovarastoon($ahyllyalue, $ahyllynro) and $ahyllyalue != '' and $ahyllynro != '' and $ahyllyvali != '' and $ahyllytaso != '') {
				echo "<font class='message'>".("Uusi varastopaikka luotiin tuotteelle").": $tuoteno ($ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso)</font><br>";
			
				$query = "	SELECT oletus
							FROM tuotepaikat 
							WHERE yhtio = '$kukarow[yhtio]' 
							and tuoteno = '$tuoteno'
							and oletus != ''";
				$result = mysql_query($query) or pupe_error($query);
				
				if (mysql_num_rows($result) > 0) {
					$oletus = "";
				}
				else {
					$oletus = "X";
				}
				
				
				$query = "INSERT into tuotepaikat (yhtio, hyllyalue, hyllynro, hyllyvali, hyllytaso, oletus, tuoteno)
						  VALUES (
							'$kukarow[yhtio]',
							'$ahyllyalue',
							'$ahyllynro',
							'$ahyllyvali',
							'$ahyllytaso',
							'$oletus',
							'$tuoteno')";
				$result = mysql_query($query) or pupe_error($query);

				$query = "	INSERT into tapahtuma set
							yhtio 		= '$kukarow[yhtio]',
							tuoteno 	= '$tuoteno',
							kpl 		= '0',
							kplhinta	= '0',
							hinta 		= '0',
							laji 		= 'uusipaikka',
							selite 		= '".t("Lis‰ttiin tuotepaikka")." $ahyllyalue $ahyllynro $ahyllyvali $ahyllytaso',
							laatija 	= '$kukarow[kuka]',
							laadittu 	= now()";
				$result = mysql_query($query) or pupe_error($query);
			}
			else {
				echo "<font class='error'>".("Uusi varastopaikka ei kuulu mihink‰‰n varastoon").": $tuoteno ($ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso)</font><br>";
			}
		}
		else {
			echo "<font class='error'>".("Uusi varastopaikka lˆytyy j otuotteelta").": $tuoteno ($ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso)</font><br>";
		}
		$tee = 'M';
	}

	if ($tee != "") {
		$query  = "UNLOCK TABLES";
		$result = mysql_query($query) or pupe_error($query);
	}
	
	if ($tee == 'M' or $tee == 'Q') { 

		require ("inc/tuotehaku.inc");
		
		if ($ulos != "") {
			$formi  = 'hakua';
   			echo "<form action = '$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
   			echo "<input type='hidden' name='tee' value='Q'>";
   			echo "<input type='hidden' name='tulostakappale' value='$tulostakappale'>";
   			echo "<input type='hidden' name='kirjoitin' value='$kirjoitin'>";
   			echo "<input type='hidden' name='toim' value='$toim'>";
   			echo "<input type='hidden' name='malli' value='$malli'>";
   			echo "<table><tr>";
   			echo "<td>".t("Valitse listasta").":</td>";
   			echo "<td>$ulos</td>";
   			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
   			echo "</tr></table>";
   			echo "</form>";
			
			$tee = 'Q';
		}
		elseif ($ulos == '' and $tee != 'Y') {
			$tee  = "M";
		}
	}

	if ($tee == 'Y') {
		echo "<font class='error'>$varaosavirhe</font>";
		$tee = '';
	}

	if ($tee == 'M') {

		echo "<table><tr>";
		
		//Jos ei haettu, annetaan 'edellinen' & 'seuraava'-nappi
		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tee' value='E'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		
		echo "<th>$tuoteno - ".asana('nimitys_',$tuoteno,$trow['nimitys'])."</th>";
		echo "<td>";
		echo "<input type='Submit' value='".t("Edellinen tuote")."'>";
		echo "</td>";
		echo "</form>";

		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='tee' value='S'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<td>";
		echo "<input type='Submit' value='".t("Seuraava tuote")."'>";
		echo "</td>";
		echo "</form>";
		echo "</tr>";
		echo "</table><br>";
		
		echo "<table>";
		echo "<tr>";
				
		echo "	<form name = 'valinta' action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'tuoteno' value = '$tuoteno'>
				<input type = 'hidden' name = 'tee' value ='N'>
				<tr>
				<th>".t("L‰hett‰v‰")."<br>".t("varastopaikka").":</th>
				<th>".t("Vastaanottava")."<br>".t("varastopaikka").":</th>
				<th>".t("Siirret‰‰n")."<br>".t("kpl").":</th>";
				
		if($trow["sarjanumeroseuranta"] != '') {
			echo "<th>".t("Valitse")."<br>".t("sarjanumerot").":</th>";	
		}
				
		echo "</tr>";

		// Saldot per varastopaikka LEFT JOIN
		$query = "	SELECT tuotepaikat.*, 
					varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi,
					concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
		 			FROM tuotepaikat
					LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
					and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
					WHERE tuotepaikat.yhtio = '$kukarow[yhtio]' 
					and tuotepaikat.tuoteno = '$tuoteno'
					ORDER BY sorttauskentta";
		$paikatresult1 = mysql_query($query) or pupe_error($query);
				
		echo "<tr>";
		echo "<td valign='top'><select name='mista'>";
		
		if (mysql_num_rows($paikatresult1) > 0) {
			while ($saldorow = mysql_fetch_array ($paikatresult1)) {
				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, '', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"]);
				
				if ($myytavissa > 0) {
					echo "<option value='$saldorow[tunnus]'>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso] ($myytavissa)</option>";
				}
			}
		}
		echo "</select></td>";
		echo "<td valign='top'><select name='minne'>";
		
		// Saldot per varastopaikka JOIN
		$query = "	SELECT tuotepaikat.*, 
					varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi,
					concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
		 			FROM tuotepaikat
					JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
					and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
					WHERE tuotepaikat.yhtio = '$kukarow[yhtio]' 
					and tuotepaikat.tuoteno = '$tuoteno'
					ORDER BY sorttauskentta";
		$paikatresult2 = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($paikatresult2) > 0) {
			while ($saldorow = mysql_fetch_array ($paikatresult2)) {
				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, '', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"]);
				
				echo "<option value='$saldorow[tunnus]'>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso] ($myytavissa)</option>";
			}
		}
		echo "</select></td>";
		echo "<td valign='top'><input type = 'text' name = 'asaldo' size = '3' value ='$asaldo'></td>";
				
		if($trow["sarjanumeroseuranta"] != '') {
			$query	= "	SELECT tilausrivi_osto.nimitys nimitys, sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus, 
						concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka
						FROM sarjanumeroseuranta
						LEFT JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
						LEFT JOIN lasku lasku_osto use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.uusiotunnus
						WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
						and sarjanumeroseuranta.tuoteno = '$trow[tuoteno]'
						and sarjanumeroseuranta.myyntirivitunnus = 0
						and (lasku_osto.tila='U' or (lasku_osto.tila='K' and lasku_osto.alatila='X'))";
			$sarjares = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sarjares) > 0) {			
				echo "<td valign='top' class='back'>";
				
				if (mysql_num_rows($sarjares) > 10) {
					echo "<div style='height:265;width:500;overflow:auto;'>";
				}
				
				echo "<table width='100%'>";
				
				while($sarjarow = mysql_fetch_array($sarjares)) {
					echo "<tr><td nowrap>".asana('nimitys_',$trow['tuoteno'],$sarjarow['nimitys'])."</td><td nowrap>$sarjarow[sarjanumero]</td><td nowrap>$sarjarow[tuotepaikka]</td><td><input type='checkbox' name='sarjano_array[]' value='$sarjarow[tunnus]'></td></tr>";
				}
				echo "</table>";
				
				if (mysql_num_rows($sarjares) > 10) {
					echo "</div>";
				}
				
				echo "</td>";
			}
		}
		echo "</tr>";
		echo "	<tr><td colspan='6'><input type = 'submit' value = '".t("Siirr‰")."'></td>
				</tr></table></form><br>";

		// Tehd‰‰n k‰yttˆliittym‰ paikkojen muutoksille (otetus tai pois)
		echo "	<form name = 'valinta' action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'tee' value ='MUUTA'>
				<input type = 'hidden' name = 'tuoteno' value = '$tuoteno'>";

		echo "<table>";
		echo "<tr><th>".t("Varastopaikka")."</th><th>".t("Saldo")."</th><th>".t("Oletuspaikka")."</th><th>".t("H‰lyraja")."</th><th>".t("Tilausm‰‰r‰")."</th><th>".t("Poista")."</th></tr>";

		
		if (mysql_num_rows($paikatresult1) > 0) {
			$query = "	SELECT tunnus
						FROM tuotepaikat
						WHERE tuoteno = '$tuoteno'
						and yhtio 	= '$kukarow[yhtio]'
						and oletus != ''";
			$result = mysql_query($query) or pupe_error($query);
			$oletusrow = mysql_fetch_array($result);
			
			mysql_data_seek($paikatresult1, 0);
			
			while ($saldorow = mysql_fetch_array ($paikatresult1)) {				
				if ($saldorow["tunnus"] == $oletusrow["tunnus"]) {
					$checked = "CHECKED";
				}
				else {
					$checked = "";
				}
				
				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, '', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"]);
				
				echo "<tr><td>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</td><td align='right'>$saldo</td>";
					
				if (kuuluukovarastoon($saldorow["hyllyalue"], $saldorow["hyllynro"])) {	
					echo "<td><input type = 'radio' name='oletus' value='$saldorow[tunnus]' $checked></td>
						<td><input type='text' size='6' name='halyraja2[$saldorow[tunnus]]'    value='$saldorow[halytysraja]'></td>
						<td><input type='text' size='6' name='tilausmaara2[$saldorow[tunnus]]' value='$saldorow[tilausmaara]'></td>";
				}
				else {
					echo "<td></td><td></td><td></td>";
				}
				
				// Ei n‰ytet‰ boxia, jos sit‰ ei saa k‰ytt‰‰
				if ($saldo != 0) { 
					echo "<td></td>";
				}
				else {
					echo "<td><input type = 'checkbox' name='poista[$saldorow[tunnus]]' value='$saldorow[tunnus]'></td>";
				}
				echo "</tr>";
			}
		}
		echo "<tr><td colspan='6'><input type = 'submit' value = '".t("P‰ivit‰")."'></td></table></form><br>";

		$ahyllyalue	= '';
		$ahyllynro	= '';
		$ahyllyvali	= '';
		$ahyllytaso	= '';

		echo "<table><form name = 'valinta' action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='UUSIPAIKKA'>
				<input type='hidden' name='tuoteno' value='$tuoteno'>
				<tr><th>".t("Lis‰‰ uusi varastopaikka")."</th></tr>
				<tr><td>
				".t("Alue")." <input type = 'text' name = 'ahyllyalue' size = '5' maxlength='5' value = '$ahyllyalue'>
				".t("Nro")."  <input type = 'text' name = 'ahyllynro'  size = '5' maxlength='5' value = '$ahyllynro'>
				".t("V‰li")." <input type = 'text' name = 'ahyllyvali' size = '5' maxlength='5' value = '$ahyllyvali'>
				".t("Taso")." <input type = 'text' name = 'ahyllytaso' size = '5' maxlength='5' value = '$ahyllytaso'>
				</td></tr>
				<tr><td><input type = 'submit' value = '".t("Lis‰‰")."'></td></tr>
				</table></form>";

		echo "<br><hr><form name = 'valinta' action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value=''>
				<input type = 'submit' value = '".t("Palaa tuotteen valintaan")."'>";
	}

	if ($tee == '') {
		// T‰ll‰ ollaan, jos olemme vasta valitsemassa tuotetta
		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='M'>
				<table>
				<tr><th>".t("Anna tuotenumero")."</th>
				<td><input type = 'text' name = 'tuoteno' value = '$tuoteno'></td></tr>
				</table><br>
				<input type = 'submit' value = '".t("Hae")."'>
				</form>";

		$kentta = 'tuoteno';
		$formi = 'valinta';
	}

	if ($kutsuja == '') {
		require "inc/footer.inc";
	}
?>
