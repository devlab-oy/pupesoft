<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "muuvarastopaikka.php")  !== FALSE) {
		require("inc/parametrit.inc");
	}

	if ($kutsuja == '') {
		echo "<font class='head'>".t("Tuotteen varastopaikat")."</font><hr>";
	}
	
	if ($tee == 'S' or $tee == 'E') {
		if ($tee == 'S') {
			$oper='>';
			$suun='';
		}
		else {
			$oper='<';
			$suun='desc';
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
			$tuoteno = $trow['tuoteno'];
			$tee='M';
		}
		else {
			$varaosavirhe = t("Yht‰‰n tuotetta ei lˆytynyt")."!";
			$tuoteno = '';
			$tee='Y';
		}
	}
	
	//Siirret‰‰n saldo, jos se on viel‰ olemassa
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

	// Itse varastopaikkoja muutellaan (Tarkistus)
	if ($tee == 'C') {
		$atil=0.01;
		$naytakaikkipaikat = "ON";

		require ("tilauskasittely/tarkistasaldo.inc");

		for ($i=0; $i < count($poista); $i++) {
			if ($poista[$i] == $oletus) {
				//katotaan ekax onko oletuspaikalla saldoa
				$poisoletus = -10;
				
				$tarkquery = "SELECT saldo FROM tuotepaikat where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and tunnus = '$poista[$i]' and saldo <> 0";
				$tarkresult = mysql_query($tarkquery) or pupe_error($tarkquery);
				if (mysql_num_rows($tarkresult) == 0) {
					//sitten katotaan onko tuotteella muita paikkoja
					$poisoletus = -20;
					
					$tarkquery1 = "SELECT tunnus FROM tuotepaikat where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and tunnus != '$poista[$i]'";
					$tarkresult1 = mysql_query($tarkquery1) or pupe_error($tarkquery1);
					if (mysql_num_rows($tarkresult1) == 0) {
						$poisoletus = 0;					
						//ennakot
						$tarkquery2 =	"SELECT tilausrivi.tunnus
										FROM tilausrivi
										WHERE yhtio = '$kukarow[yhtio]'
										AND tuoteno = '$tuoteno'
										AND tyyppi = 'E'";
						$tarkresult2 = mysql_query($tarkquery2) or pupe_error($tarkquery2);
						$poisoletus += mysql_num_rows($tarkresult2);
						
						//myyntirivit
						$tarkquery2 =	"SELECT tilausrivi.tunnus
										FROM tilausrivi
										WHERE yhtio = '$kukarow[yhtio]'
										AND tuoteno = '$tuoteno'
										AND tyyppi = 'L' 
										AND kpl=0 
										AND var !='P'";
						$tarkresult2 = mysql_query($tarkquery2) or pupe_error($tarkquery2);
						$poisoletus += mysql_num_rows($tarkresult2);
					
						//siirtorivit
						$tarkquery2 =	"SELECT tilausrivi.tunnus
										FROM tilausrivi
										WHERE yhtio = '$kukarow[yhtio]'
										AND tuoteno = '$tuoteno'
										AND tyyppi = 'G' 
										AND toimitettuaika != '0000-00-00 00:00:00'
										AND var !='P'";
						$tarkresult2 = mysql_query($tarkquery2) or pupe_error($tarkquery2);
						$poisoletus += mysql_num_rows($tarkresult2);
						
						//valmistukset ja valmisteet
						$tarkquery2 =	"SELECT tilausrivi.tunnus
										FROM tilausrivi
										WHERE yhtio = '$kukarow[yhtio]'
										AND tuoteno = '$tuoteno'
										AND tyyppi in ('V','W') 
										AND toimitettuaika != '0000-00-00 00:00:00'";
						$tarkresult2 = mysql_query($tarkquery2) or pupe_error($tarkquery2);
						$poisoletus += mysql_num_rows($tarkresult2);
						
						//ostorivit
						$tarkquery2 =	"SELECT tilausrivi.tunnus, kohdistettu
										FROM tilausrivi
										LEFT JOIN lasku ON tilausrivi.yhtio = lasku.yhtio AND tilausrivi.uusiotunnus = lasku.tunnus
										WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
										AND tuoteno = '$tuoteno'
										AND tyyppi = 'O' 
										HAVING kohdistettu != 'X'";
						$tarkresult2 = mysql_query($tarkquery2) or pupe_error($tarkquery2);
						$poisoletus += mysql_num_rows($tarkresult2);
					}
				}
				
				if ($poisoletus <> 0) {
					if ($poisoletus == -10) {
						echo "<font class='error'>".t("Et voi poistaa oletuspaikkaa").". ".t("Paikalla on saldoa")."</font><br><br>";
					}
					elseif ($poisoletus == -20) {
						echo "<font class='error'>".t("Et voi poistaa oletuspaikkaa").". ".t("Tuoteella on muitakin paikkoja")."</font><br><br>";
					}
					else {
						echo "<font class='error'>".t("Et voi poistaa oletuspaikkaa").". ".t("Tuoteella on")." $poisoletus ".t("avointa rivi‰")."</font><br><br>";
					}
					$tee='M';
				}
				
			}
			for ($j=0; $j < count($hyllyalue); $j++) {
				if (($poista[$i] == $hyllytunnus[$j]) and ($varastossa[$j] <> 0)) {
					echo "<font class='error'>".t("Et voi poistaa paikkaa jolla on saldoa")."</font><br><br>";
					$tee='M';
				}
			}
		}
	}

	$lock = "";

	if ($tee == 'U' or $tee == 'N' or $tee == 'C') {
		$lock   = "X";
		$query  = "LOCK TABLE tuotepaikat WRITE, tapahtuma WRITE, sanakirja WRITE, tuote READ, varastopaikat READ, tilausrivi READ, sarjanumeroseuranta WRITE";
		$result = mysql_query($query) or pupe_error($query);
	}

 	// Itse varastopaikkoja muutellaan
	if ($tee == 'C') {
		$varasto = 0;
		$vanhaoletus = $hyllytunnus[$oletuspaikka];

		if ($oletus != $vahnaoletus) { // Oletuspaikka vaihdettiin

			$query = "	SELECT *
						FROM tuotepaikat
						WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$vanhaoletus'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Vanha oletuspaikka katosi tuotteelta")."</font><br><br>";
				$tee = 'M';
			}

			$query = "	SELECT *
						FROM tuotepaikat
						WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$oletus'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Uusi oletuspaikka on kadonnut")."</font><br><br>";
				$tee = 'M';
			}
		}
	}

	// Muutetaan h‰lytysrajoja (tarkistus)
	if ($tee == 'C') {	
		for ($i=0; $i < count($halyraja1); $i++) {
			$query = "	SELECT *
						FROM tuotepaikat
						WHERE tuoteno = '$tuoteno'
						and yhtio = '$kukarow[yhtio]'
						and tunnus = '$halyraja1[$i]'";
			$result = mysql_query($query) or pupe_error($query);
			$paikkarow = mysql_fetch_array($result);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Muutettava tuotepaikka katosi")."!</font><br><br>";
				$tee = 'M';
			}
		}
	}

	// Muutetaan tilausm‰‰r‰ (tarkistus)
	if ($tee == 'C') {	
		for ($i=0; $i < count($tilausmaara1); $i++) {
			$query = "	SELECT *
						FROM tuotepaikat
						WHERE tuoteno = '$tuoteno'
						and yhtio = '$kukarow[yhtio]'
						and tunnus = '$tilausmaara1[$i]'";
			$result = mysql_query($query) or pupe_error($query);
			$paikkarow = mysql_fetch_array($result);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Muutettava tuotepaikka katosi")."!</font><br><br>";
				$tee = 'M';
			}
		}
	}

	// Poistetaan varastopaikkoja (tarkistus)
	if ($tee == 'C') {	
		for ($i=0; $i < count($poista); $i++) {
			$query = "	SELECT *
						FROM tuotepaikat
						WHERE tuoteno = '$tuoteno'
						and yhtio = '$kukarow[yhtio]'
						and tunnus = '$poista[$i]'";
			$result = mysql_query($query) or pupe_error($query);
			$paikkarow = mysql_fetch_array($result);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Poistattava paikka katosi tuotteelta")."</font><br><br>";
				$tee = 'M';
			}
		}
	}

	if ($tee == 'C') {
		$vanhaoletus = $hyllytunnus[$oletuspaikka];

		if ($oletus != $vanhaoletus) { // Oletuspaikka vaihdettiin
			// Tehd‰‰n p‰ivitykset
			echo "<font class='message'>".t("Siirret‰‰n oletuspaikka")."</font><br><br>";

			$query = "	UPDATE tuotepaikat SET oletus = ''
						WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$vanhaoletus'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE tuotepaikat SET oletus = 'X'
						WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$oletus'";
			$result = mysql_query($query) or pupe_error($query);
		}

		for ($i=0; $i < count($poista); $i++) {
			echo "<font class='message'>".t("Poistetaan varastopaikka")." " . str_replace ( "#", " ", $poista[$i]) . "</font><br><br>";

			$query = "	INSERT into tapahtuma set
						yhtio 		= '$kukarow[yhtio]',
						tuoteno 	= '$tuoteno',
						kpl 		= '0',
						kplhinta	= '0',
						hinta 		= '0',
						laji 		= 'poistettupaikka',
						selite 		= '".t("Poistettiin tuotepaikka")." $paikkarow[hyllyalue] $paikkarow[hyllynro] $paikkarow[hyllyvali] $paikkarow[hyllytaso]',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now()";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	DELETE FROM tuotepaikat
						WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus = '$poista[$i]'";
			$result = mysql_query($query) or pupe_error($query);
		}

		for ($i=0; $i < count($halyraja1); $i++) {
			//echo "<font class='message'>H‰lytysraja p‰ivitettiin!</font><br><br>";
			$query = "	UPDATE tuotepaikat SET halytysraja = '$halyraja2[$i]'
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$halyraja1[$i]'";
			$result = mysql_query($query) or pupe_error($query);
		}

		for ($i=0; $i < count($tilausmaara1); $i++) {

			//echo "<font class='message'>Tilausm‰‰r‰ p‰ivitettiin!</font><br><br>";

			$query = "	UPDATE tuotepaikat SET tilausmaara = '$tilausmaara2[$i]'
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tilausmaara1[$i]'";
			$result = mysql_query($query) or pupe_error($query);
		}



		$ahyllyalue='';
		$ahyllynro='';
		$ahyllyvali='';
		$ahyllytaso='';
		$tee='M';
	}

	if ($tee == 'N') {

		//tarvitsemme
		//$asaldo = siirrett‰v‰ m‰‰r‰
		//$mista = tuotepaikan tunnus josta otetaan
		//$minne = tuotepaikan tunnus jonne siirret‰‰n
		//$tuoteno = tuotenumero jota siirret‰‰n

		if ($kutsuja == "vastaanota.php") {
			$uusitee = "X";
		}
		else {
			$uusitee = "M";
		}

		$atil = $asaldo;
		$naytakaikkipaikat = "ON";

		require ("tilauskasittely/tarkistasaldo.inc");

		$varasto = 0;

		for ($i=1; $i <= count($hyllyalue); $i++) {

			//echo "T‰t‰ tarjotaan: $atil, $saldo[$i], $varastossa[$i], $hyllyalue[$i]<br>";

			if ($saldo[$i] >= 0 and $mista == $hyllytunnus[$i]) {
				$varasto = $i;
				//echo "T‰‰ otetaan: $atil $saldo[$i] $varasto<br>";
			}
		}

		//t‰h‰n erroriin tullaan vain jos kyseess‰ ei ole siirtolista
		//koska jos meill‰ on ker‰tty siirtolista miin ne m‰‰r‰t myˆs halutaan siirt‰‰ vaikka saldo menisikin nollille tai miinukselle
		if ($kutsuja != "vastaanota.php") {
			if ($varasto == 0) { //Taravat myytiin alta!
				echo "<font class='error'>".t("Siirett‰v‰ m‰‰r‰ on liian iso")."</font><br><br>";
				$tee = $uusitee;
			}
		}

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
			echo "<font class='error'>".t("T‰m‰ varastopaikka katosi tuotteelta")." $mista</font><br><br>";
			$tee = $uusitee;
		}
		
		
		//Tarkistetaan sarjanumeroseuranta
		$query = "	SELECT tunnus
					FROM tuote
					WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and sarjanumeroseuranta!=''";
		$sarjaresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sarjaresult) > 0 and (!is_array($sarjano_array) or count($sarjano_array) != $asaldo)) {
			echo "<font class='error'>".t("Tarkista sarjanumerovalintasi")."!</font><br><br>";
			$tee = $uusitee;
		}
	}
	if ($tee == 'N') {

		if ($kutsuja == "vastaanota.php") {
			$uusitee = "OK";
		}
		else {
			$uusitee = "M";
		}

		// Mist‰ varastotsta otetaan?
		$query = "	UPDATE tuotepaikat set saldo = saldo - $asaldo, saldoaika=now()
                  	WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$mista'";
		$result = mysql_query($query) or pupe_error($query);

		// Minne varastoon vied‰‰n?
		$query = "	UPDATE tuotepaikat set saldo = saldo + $asaldo, saldoaika=now()
					WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$minne'";
		$result = mysql_query($query) or pupe_error($query);

		$minne = $minnerow['hyllyalue']." ".$minnerow['hyllynro']." ".$minnerow['hyllyvali']." ".$minnerow['hyllytaso'];
		$mista = $mistarow['hyllyalue']." ".$mistarow['hyllynro']." ".$mistarow['hyllyvali']." ".$mistarow['hyllytaso'];
		
		if (($kutsuja == 'vastaanota.php' and $toim == 'MYYNTITILI') or ($kutsuja != 'vastaanota.php')) {
			$query = "	INSERT into tapahtuma set
						yhtio 		= '$kukarow[yhtio]',
						tuoteno 	= '$tuoteno',
						kpl 		= $asaldo * -1,
						hinta 		= '0',
						laji 		= 'siirto',
						selite 		= '".t("Paikasta")." $mista ".t("v‰hennettiin")." $asaldo',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now()";
			$result = mysql_query($query) or pupe_error($query);
		}
		
		$query = "	INSERT into tapahtuma set
					yhtio 		= '$kukarow[yhtio]',
					tuoteno 	= '$tuoteno',
					kpl 		= '$asaldo',
					hinta 		= '0',
					laji 		= 'siirto',
					selite 		= '".t("Paikalle")." $minne ".t("lis‰ttiin")." $asaldo',
					laatija 	= '$kukarow[kuka]',
					laadittu 	= now()";
		$result = mysql_query($query) or pupe_error($query);

		
		//P‰ivitet‰‰n sarjanumerot
		if (mysql_num_rows($sarjaresult) > 0) {
			foreach($sarjano_array as $sarjano) {
				$query = "	UPDATE sarjanumeroseuranta 
							set hyllyalue	= '$minnerow[hyllyalue]',
							hyllynro 		= '$minnerow[hyllynro]',
							hyllyvali 		= '$minnerow[hyllyvali]',
							hyllytaso		= '$minnerow[hyllytaso]'
							WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$sarjano'";
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

	if ($tee == 'U') { // Uusi varstopaikka tai vanhojen p‰ivitys
		// Tarkistetaan tuote
		$query = "SELECT *
					FROM tuote
					WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Tuotetta ei lˆydy")."!</font><br><br>";
			$tee = '';
		}
		$trow=mysql_fetch_array($result);

		if ($trow['ei_saldoa'] != '') {
			echo "<font class='error'>".t("Tuotteella ei ole saldoa, joten sill‰ ei voi olla varastopaikkaa")."!</font><br><br>";
			$tee='';
		}
		if ($ahyllyalue == '') $tee = 'M';// P‰ivitet‰‰nkin vanhoja
	}

	if ($tee == 'U') { // Uusi varstopaikka
		// Tarkistetaan lis‰‰
		$ahyllyalue = strtoupper($ahyllyalue);
		$ahyllynro  = strtoupper($ahyllynro);
		$ahyllyvali = strtoupper($ahyllyvali);
		$ahyllytaso = strtoupper($ahyllytaso);
		
		$query = "	SELECT *
					FROM tuotepaikat
					WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) { // T‰m‰ on uusi paikka joten siit‰ tehd‰‰n oletus
			$oletus = 'X';
		}

		$query = "	SELECT *
					FROM tuotepaikat
					WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and
					hyllyalue = '$ahyllyalue' and hyllynro = '$ahyllynro' and hyllyvali = '$ahyllyvali' and hyllytaso = '$ahyllytaso'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 0) { // T‰m‰ on jo!
			echo "<font class='error'>".t("T‰m‰ varastopaikka on jo tuotteella")." $tuoteno</font><br><br>";
			$tee = '';
		}
	}
	if ($tee == 'U') {
		// Lis‰t‰‰n s‰‰ntˆ
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

		echo "<font class='message'>".t("Uusi varastopaikka luotiin")."</font><br><br>";
		$tee = 'M';
		$ahyllyalue='';
		$ahyllynro='';
		$ahyllyvali='';
		$ahyllytaso='';
	}

	if ($lock == "X") {
		$query  = "UNLOCK TABLES";
		$result = mysql_query($query) or pupe_error($query);
	}

	if ($tee == 'M' or $tee == 'Q') { // Muutetaan varastopaikkoja tai saldoja

		require "inc/tuotehaku.inc";
		
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
		if ($ulos == '' and $tee != 'Y') {
			$atil = 0.01;
			$naytakaikkipaikat = "ON";

			require ("tilauskasittely/tarkistasaldo.inc");

			$tee  = "M";
		}
	}

	if ($tee=='Y') {
		echo "<font class='error'>$varaosavirhe</font>";
		$tee = '';
	}

	if ($tee == 'M') {

		for ($i=1; $i <= count($hyllyalue); $i++) {
			$ulos .= "<option value='$hyllytunnus[$i]'>$hyllyalue[$i] $hyllynro[$i] $hyllyvali[$i] $hyllytaso[$i] ($varastossa[$i])";
			if ($varastossa[$i] > 0) {
				$ulos1 .= "<option value='$hyllytunnus[$i]'>$hyllyalue[$i] $hyllynro[$i] $hyllyvali[$i] $hyllytaso[$i] ($varastossa[$i])";
			}
		}
		$ulos.= "</select>";

		echo "<table><tr>";
		
		//Jos ei haettu, annetaan 'edellinen' & 'seuraava'-nappi
		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tee' value='E'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		
		echo "<th>$tuoteno - $trow[nimitys]</th>";
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
				
		echo "	</tr>";
				
		echo "	<tr>
				<td valign='top'><select name='mista'>$ulos1</td>
				<td valign='top'><select name='minne'>$ulos</td>";	
		echo "	<td valign='top'><input type = 'text' name = 'asaldo' size = '3' value ='$asaldo'></td>";
				
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
				echo "<div style='height:265;width:500;overflow:auto;'>";
				echo "<table width='100%'>";
				
				while($sarjarow = mysql_fetch_array($sarjares)) {
					echo "<tr><td nowrap>$sarjarow[nimitys]</td><td nowrap>$sarjarow[sarjanumero]</td><td nowrap>$sarjarow[tuotepaikka]</td><td><input type='checkbox' name='sarjano_array[]' value='$sarjarow[tunnus]'></td></tr>";
				}
				echo "</table></div></td>";
			}
		}
		echo "</tr>";
		echo "	<tr><td colspan='6'><input type = 'submit' value = '".t("Siirr‰")."'></td>
				</tr></table></form><br>";

		// Tehd‰‰n k‰yttˆliittym‰ paikkojen muutoksille (otetus tai pois)
		echo "	<form name = 'valinta' action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'tee' value ='C'>
				<input type = 'hidden' name = 'tuoteno' value = '$tuoteno'>";

		echo "<table>";
		echo "<tr><th>".t("Varastopaikka")."</th><th>".t("Saldo")."</th><th>".t("Oletuspaikka")."</th><th>".t("H‰lyraja")."</th><th>".t("Tilausm‰‰r‰")."</th><th>".t("Poista")."</th></tr>";

		for ($i=1; $i <= count($hyllyalue); $i++) {
			$checked='';
			if ($i == $oletuspaikka) $checked='checked';

			echo "<tr>
				<td>$hyllyalue[$i] $hyllynro[$i] $hyllyvali[$i] $hyllytaso[$i]</td><td>$varastosaldo[$i]</td>
				<td><input type = 'radio' name='oletus' value='$hyllytunnus[$i]' $checked></td>";


			$query = "	SELECT halytysraja, tilausmaara
						FROM tuotepaikat
						WHERE tunnus = '$hyllytunnus[$i]' and yhtio = '$kukarow[yhtio]'";
			$halyresult = mysql_query($query) or pupe_error($query);
			$halyrow = mysql_fetch_array($halyresult);

			echo "<input type='hidden' name='halyraja1[]' value='$hyllytunnus[$i]'>";
			echo "<td><input type='text' size='6' name='halyraja2[]' value='$halyrow[halytysraja]'></td>";
			echo "<input type='hidden' name='tilausmaara1[]' value='$hyllytunnus[$i]'>";
			echo "<td><input type='text' size='6' name='tilausmaara2[]' value='$halyrow[tilausmaara]'></td>";
			
			if ($varastosaldo[$i] != 0) { // Ei n‰ytet‰ boxia, jos sit‰ ei saa k‰ytt‰‰
				echo "<td></td>";
			}
			else {
				echo "<td><input type = 'checkbox' name='poista[]' value='$hyllytunnus[$i]'></td>";
			}

			echo "</tr>";
		}
		echo "<tr><td colspan='6'><input type = 'submit' value = '".t("P‰ivit‰")."'></td></table></form><br>";

		$ahyllyalue	= '';
		$ahyllynro	= '';
		$ahyllyvali	= '';
		$ahyllytaso	= '';

		echo "<table><form name = 'valinta' action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='U'>
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
