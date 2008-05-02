<?php

	require('../inc/parametrit.inc');

	// scripti balloonien tekemiseen
	js_popup();
	
	// jos ei olla postattu mit‰‰n, niin halutaan varmaan tehd‰ kokonaan uusi tilaus..
	if (count($_POST) == 0 and $from == "") {
		$tila				= '';
		$tilausnumero		= '';
		$laskurow			= '';
		$kukarow["kesken"]	= '';

		//varmistellaan ettei vanhat kummittele...
		$query	= "update kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);
	}
	
	if($from == "LASKUTATILAUS") {
		$tee = "AKTIVOI";
	}
	
	if ($tee == 'AKTIVOI') {
		// katsotaan onko muilla aktiivisena
		$query = "select * from kuka where yhtio='$kukarow[yhtio]' and kesken='$tilausnumero' and kesken!=0";
		$result = mysql_query($query) or pupe_error($query);

		unset($row);
		
		if (mysql_num_rows($result) != 0) {
			$row=mysql_fetch_array($result);
		}

		if (isset($row) and $row['kuka'] != $kukarow['kuka']) {
			echo "<font class='error'>".t("Tilaus on aktiivisena k‰ytt‰j‰ll‰")." $row[nimi]. ".t("Tilausta ei voi t‰ll‰ hetkell‰ muokata").".</font><br>";

			// poistetaan aktiiviset tilaukset jota t‰ll‰ k‰ytt‰j‰ll‰ oli
			$query = "update kuka set kesken='' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);

			exit;
		}
		else {
			$query = "	UPDATE kuka 
						SET kesken = '$tilausnumero' 
						WHERE yhtio = '$kukarow[yhtio]' AND 
						kuka = '$kukarow[kuka]' AND 
						session = '$session'";
			$result = mysql_query($query) or pupe_error($query);

			$kukarow['kesken'] 	 = $tilausnumero;
			$tee = "Y";
		}
	}
	
	if ($tee != "") {
		//katsotaan ett‰ kukarow kesken ja $kukarow[kesken] stemmaavat kesken‰‰n
		if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero != '' or (int) $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
			echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("K‰y aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
			exit;
		}
		if ($kukarow['kesken'] != '0') {
			$tilausnumero=$kukarow['kesken'];
		}
	}
	
	if ((int) $kukarow['kesken'] == 0 or $tee == "MUUOTAOSTIKKOA") {
		require("otsik_ostotilaus.inc");
	}
		
	if ($tee != "" and $tee != "MUUOTAOSTIKKOA") {
		
		//korjataan hintaa ja aleprossaa
		$hinta	= str_replace(',','.',$hinta);
		$ale 	= str_replace(',','.',$ale);
		$kpl 	= str_replace(',','.',$kpl);
		
		// Hateaan tilauksen tiedot
		$query = "	SELECT *
					FROM lasku
					WHERE tunnus = '$kukarow[kesken]' and yhtio = '$kukarow[yhtio]' and tila = 'O'";
		$aresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($aresult) == 0) {
			echo "<font class='message'>".t("VIRHE: Tilausta ei lˆydy")."!<br><br></font>";
			exit;
		}
		$laskurow = mysql_fetch_array($aresult);
		
		
		if($tee == "vahvista") {
			$query = "UPDATE tilausrivi SET  jaksotettu=1 where yhtio='$kukarow[yhtio]' and otunnus = '$kukarow[kesken]' and tyyppi='O' and uusiotunnus=0";
			$result = mysql_query($query) or pupe_error($query);
			if(mysql_affected_rows() > 0) {
				echo "<font class='message'>".t("Toimitus vahvistettu")."</font><br><br>";
			}
			else {
				echo "<font class='error'>".t("Toimituksella ei ollut vahvistettavia rivej‰")."</font><br><br>";
			}
			
			$tee = "Y";
		}
		
		if ($tee == 'poista') {
			// poistetaan tilausrivit, mutta j‰tet‰‰n PUUTE rivit analyysej‰ varten...
			$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
			$result = mysql_query($query) or pupe_error($query);

			//Nollataan sarjanumerolinkit
			$query    = "	SELECT tilausrivi.tunnus
							FROM tilausrivi
							JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.otunnus = '$kukarow[kesken]'";
			$sres = mysql_query($query) or pupe_error($query);

			while($srow = mysql_fetch_array($sres)) {
				$query = "update sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and ostorivitunnus='$srow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
			
			$query = "UPDATE lasku SET tila='D', alatila='$laskurow[tila]', comments='$kukarow[nimi] ($kukarow[kuka]) ".t("mit‰tˆi tilauksen")." ".date("d.m.y @ G:i:s")."' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "UPDATE kuka SET kesken=0 WHERE session='$session'";
			$result = mysql_query($query) or pupe_error($query);

			$tee = "";
			$kukarow["kesken"] = 0; // Ei en‰‰ kesken

		}

		if ($tee == 'poista_kohdistamattomat') {
			// poistetaan kohdistamattomat ostotilausrivit
			$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and uusiotunnus=0";
			$result = mysql_query($query) or pupe_error($query);

			echo "<font class='message'>".t("Kohdistamattomat tilausrivit poistettu")."!<br><br></font>";

			$tee = "Y";
		}

		if ($tee =='valmis') {

			//tulostetaan tilaus kun se on valmis
			$otunnus = $kukarow["kesken"];

			if (count($komento) == 0) {
				echo "<font class='head'>".t("Ostotilaus").":</font><hr><br>";

				$tulostimet[0] = "Ostotilaus";
				require("../inc/valitse_tulostin.inc");
			}

			// luodaan varastopaikat jos tilaus on optimoitu varastoon...
			$query = "select * from lasku WHERE tunnus='$kukarow[kesken]'";
			$result = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($result);

			if ($laskurow['tila'] != 'O') {
				echo t("Kesken oleva tilaus ei ole ostotilaus");
				exit;
			}

			// katotaan ollaanko haluttu optimoida johonki varastoon
			if ($laskurow["varasto"] != 0) {

				$query = "SELECT * from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$laskurow[tunnus]' and tyyppi='O'";
				$result = mysql_query($query) or pupe_error($query);

				// k‰yd‰‰n l‰pi kaikki tilausrivit
				while ($ostotilausrivit = mysql_fetch_array($result)) {

					// k‰yd‰‰n l‰pi kaikki tuotteen varastopaikat
					$query = "	SELECT *, concat(lpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
								hyllyalue, hyllynro, hyllytaso, hyllyvali
					 			from tuotepaikat
								where yhtio='$kukarow[yhtio]' and tuoteno='$ostotilausrivit[tuoteno]'
								order by sorttauskentta";
					$tuopaires = mysql_query($query) or pupe_error($query);

					// apulaskuri
					$kuuluu = 0;

					while ($tuopairow = mysql_fetch_array($tuopaires)) {
						// katotaan kuuluuko tuotepaikka haluttuun varastoon
						if (kuuluukovarastoon($tuopairow["hyllyalue"], $tuopairow["hyllynro"], $laskurow["varasto"]) != 0) {

							// jos kuului niin p‰ivitet‰‰n info tilausriville
							$query = "	UPDATE tilausrivi set
										hyllyalue = '$tuopairow[hyllyalue]',
										hyllynro  = '$tuopairow[hyllynro]',
										hyllytaso = '$tuopairow[hyllytaso]',
										hyllyvali = '$tuopairow[hyllyvali]'
										where yhtio = '$kukarow[yhtio]' and
										tunnus = '$ostotilausrivit[tunnus]'";
							$tuopaiupd = mysql_query($query) or pupe_error($query);

							$kuuluu++;
							break; // breakataan niin ei looppailla en‰‰ turhaa
						}
					} // end while tuopairow

					// tuotteella ei ollut varastopaikkaa halutussa varastossa, tehd‰‰n sellainen
					if ($kuuluu == 0) {

						// haetaan halutun varaston tiedot
						$query = "SELECT alkuhyllyalue, alkuhyllynro from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[varasto]'";
						$hyllyres = mysql_query($query) or pupe_error($query);
						$hyllyrow =  mysql_fetch_array($hyllyres);

						// katotaan lˆytykˆ yht‰‰n tuotepaikkaa, jos ei niin teh‰‰n oletus
						if (mysql_num_rows($tuopaires) == 0) {
							$oletus = 'X';
						}
						else {
							$oletus = '';
						}

			   			echo "<font class='error'>".t("Tehtiin uusi varastopaikka")." $ostotilausrivit[tuoteno]: $ostotilausrivit[hyllyalue] $ostotilausrivit[hyllynro] $ostotilausrivit[hyllytaso] $ostotilausrivit[hyllyvali]</font><br>";

						// lis‰t‰‰n paikka
						$query = "	INSERT INTO tuotepaikat set
			 						yhtio		= '$kukarow[yhtio]',
						 			tuoteno     = '$ostotilausrivit[tuoteno]',
						 			oletus      = '$oletus',
				   		 			saldo       = '0',
				   		 			saldoaika   = now(),
									hyllyalue 	= '$ostotilausrivit[hyllyalue]', 
									hyllynro 	= '$ostotilausrivit[hyllynro]', 
									hyllytaso 	= '$ostotilausrivit[hyllytaso]', 
									hyllyvali 	= '$ostotilausrivit[hyllyvali]'";
						$updres = mysql_query($query) or pupe_error($query);

						// tehd‰‰n tapahtuma
						$query = "	INSERT into tapahtuma set
									yhtio 		= '$kukarow[yhtio]',
									tuoteno 	= '$ostotilausrivit[tuoteno]',
									kpl 		= '0',
									kplhinta	= '0',
									hinta 		= '0',
									laji 		= 'uusipaikka',
									hyllyalue 	= '$ostotilausrivit[hyllyalue]', 
									hyllynro 	= '$ostotilausrivit[hyllynro]', 
									hyllytaso 	= '$ostotilausrivit[hyllytaso]', 
									hyllyvali 	= '$ostotilausrivit[hyllyvali]',
									selite 		= '".t("Lis‰ttiin tuotepaikka")." $ostotilausrivit[hyllyalue] $ostotilausrivit[hyllynro] $ostotilausrivit[hyllytaso] $ostotilausrivit[hyllyvali]',
									laatija 	= '$kukarow[kuka]',
									laadittu 	= now()";
						$updres = mysql_query($query) or pupe_error($query);

						// p‰ivitet‰‰n tilausrivi
						$query = "	UPDATE tilausrivi set
									hyllyalue 	= '$ostotilausrivit[hyllyalue]', 
									hyllynro 	= '$ostotilausrivit[hyllynro]', 
									hyllytaso 	= '$ostotilausrivit[hyllytaso]', 
									hyllyvali 	= '$ostotilausrivit[hyllyvali]'
									where yhtio = '$kukarow[yhtio]' and
									tunnus = '$ostotilausrivit[tunnus]'";
						$updres = mysql_query($query) or pupe_error($query);

					}
				} // end while ostotilausrivit
			} // end if varasto != 0

			require('tulosta_ostotilaus.inc');

			$query = "UPDATE lasku SET alatila='A' WHERE tunnus='$kukarow[kesken]'";
			$result = mysql_query($query) or pupe_error($query);
						
			if ($toim == "HAAMU") {
				
				$query = "UPDATE lasku SET tila='D', tilaustyyppi = 'O' WHERE tunnus='$kukarow[kesken]'";
				$result = mysql_query($query) or pupe_error($query);
				
				$query = "UPDATE tilausrivi SET tyyppi = 'D' WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
				$result = mysql_query($query) or pupe_error($query);
			}	

			$query = "UPDATE kuka SET kesken=0 WHERE session='$session'";
			$result = mysql_query($query) or pupe_error($query);

			$kukarow["kesken"] 	= '';
			$tilausnumero 		= 0;
			$tee 				= '';
		}
		
		//Kuitataan OK-var riville
		if ($tee == "OOKOOAA") {
			$query = "	UPDATE tilausrivi
						SET var2 = 'OK'
						WHERE tunnus = '$rivitunnus'";
			$result = mysql_query($query) or pupe_error($query);

			$tee 		= "Y";
			$rivitunnus = "";
		}


		// Olemassaolevaa rivi‰ muutetaan, joten poistetaan se ja annetaan perustettavaksi
		if ($tee == 'PV') {
			$query = "	SELECT tilausrivi.*, tuote.sarjanumeroseuranta
						FROM tilausrivi use index (PRIMARY)
						LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
						WHERE tilausrivi.tunnus = '$rivitunnus' 
						and tilausrivi.yhtio	= '$kukarow[yhtio]' 
						and tilausrivi.otunnus	= '$kukarow[kesken]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo t("Tilausrivi ei en‰‰ lˆydy")."! $query";
				exit;
			}
			$tilausrivirow = mysql_fetch_array($result);

			$query = "	DELETE
						FROM tilausrivi
						WHERE tunnus = '$rivitunnus'";
			$result = mysql_query($query) or pupe_error($query);

			
			// Tehd‰‰n pari juttua jos tuote on sarjanumeroseurannassa
			if($tilausrivirow["sarjanumeroseuranta"] != '') {
				//Nollataan sarjanumero
				$query = "SELECT tunnus FROM sarjanumeroseuranta WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tilausrivirow[tuoteno]' and ostorivitunnus='$tilausrivirow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
				$sarjarow = mysql_fetch_array($sarjares);

				//Pidet‰‰n sarjatunnus muistissa
				$osto_sarjatunnus = $sarjarow["tunnus"];

				$query = "update sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tilausrivirow[tuoteno]' and ostorivitunnus='$tilausrivirow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
			
			
			$hinta 			= $tilausrivirow["hinta"];
			$tuoteno 		= $tilausrivirow["tuoteno"];
			$tuotenimitys	= $tilausrivirow["nimitys"];	
			$kpl 			= $tilausrivirow["tilkpl"];
			$ale 			= $tilausrivirow["ale"];
			$toimaika 		= $tilausrivirow["toimaika"];
			$kerayspvm 		= $tilausrivirow["kerayspvm"];
			$alv 			= $tilausrivirow["alv"];
			$kommentti 		= $tilausrivirow["kommentti"];
			$perheid2 		= $tilausrivirow["perheid2"];
			$rivitunnus 	= $tilausrivirow["tunnus"];
			$automatiikka 	= "ON";
			$tee 			= "Y";
		}

		// Tyhjennet‰‰n tilausrivikent‰t n‰ytˆll‰
		if ($tee == 'TY') {
			$tee 				= "Y";
			$tuoteno			= '';
			$perheid 			= '';
			$perheid2 			= '';
			$kpl				= '';
			$var				= '';
			$hinta				= '';
			$netto				= '';
			$ale				= '';
			$rivitunnus			= '';
			$kerayspvm			= '';
			$toimaika			= '';
			$alv				= '';
			$paikka 			= '';
			$paikat				= '';
			$osto_sarjatunnus	= '';
			$kommentti			= '';

		}

		if ($tee == "LISLISAV") {
			//P‰ivitet‰‰n is‰n perheid jotta voidaan lis‰t‰ lis‰‰ lis‰varusteita
			$query = "	UPDATE tilausrivi use index (primary)
						set perheid2 = -1
						where yhtio = '$kukarow[yhtio]'
						and tunnus 	= '$rivitunnus'";
			$updres = mysql_query($query) or pupe_error($query);
			$tee = "Y";
		}

		// Rivi on lisataan tietokantaan
		if ($tee == 'TI' and $tuoteno != "") {
			if ($toim != "HAAMU") {
				$multi = "TRUE";
				require("../inc/tuotehaku.inc");
			}
			
		}
				
		if ($tee == 'TI' and (trim($tuoteno) != '' or is_array($tuoteno_array)) and ($kpl != '' or is_array($kpl_array))) {
			if (!is_array($tuoteno_array) and trim($tuoteno) != "") {
				$tuoteno_array[] = $tuoteno;
			}

			//K‰ytt‰j‰n syˆtt‰m‰ hinta ja ale ja netto, pit‰‰ s‰ilˆ‰ jotta tuotehaussakin voidaan syˆtt‰‰ n‰m‰
			$kayttajan_hinta	= $hinta;
			$kayttajan_ale		= $ale;
			$kayttajan_netto 	= $netto;
			$kayttajan_var		= $var;
			$kayttajan_kpl		= $kpl;
			$kayttajan_alv		= $alv;

			foreach($tuoteno_array as $tuoteno) {

				$query	= "	select *
							from tuote
							where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) > 0) {
					//Tuote lˆytyi
					$trow = mysql_fetch_array($result);
				}
				else {
					//Tuotetta ei lˆydy, arvataan muutamia muuttujia
					$trow["alv"] = $laskurow["alv"];
				}

				if (checkdate($toimkka,$toimppa,$toimvva)) {
					$toimaika = $toimvva."-".$toimkka."-".$toimppa;
				}
				if (checkdate($kerayskka,$keraysppa,$keraysvva)) {
					$kerayspvm = $keraysvva."-".$kerayskka."-".$keraysppa;
				}
				if ($toimaika == "" or $toimaika == "0000-00-00") {
					$toimaika = $laskurow["toimaika"];
				}
				if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
					$kerayspvm = $laskurow["kerayspvm"];
				}

				if ($laskurow["varasto"] != 0) {
					$varasto = (int) $laskurow["varasto"];
				}

				//Tehd‰‰n muuttujaswitchit
				if (is_array($hinta_array)) {
					$hinta = $hinta_array[$tuoteno];
				}
				else {
					$hinta = $kayttajan_hinta;
				}

				if (is_array($ale_array)) {
					$ale = $ale_array[$tuoteno];
				}
				else {
					$ale = $kayttajan_ale;
				}

				if (is_array($netto_array)) {
					$netto = $netto_array[$tuoteno];
				}
				else {
					$netto = $kayttajan_netto;
				}

				if (is_array($var_array)) {
					$var = $var_array[$tuoteno];
				}
				else {
					$var = $kayttajan_var;
				}

				if (is_array($kpl_array)) {
					$kpl = $kpl_array[$tuoteno];
				}
				else {
					$kpl = $kayttajan_kpl;
				}

				if (is_array($alv_array)) {
					$alv = $alv_array[$tuoteno];
				}
				else {
					$alv = $kayttajan_alv;
				}

				if ($kpl != 0) {
					require ('lisaarivi.inc');
				}

				$hinta 	= '';
				$ale 	= '';
				$netto 	= '';
				$var 	= '';
				$kpl 	= '';
				$alv 	= '';
				$paikka	= '';
			}

			if ($lisavarusteita == "ON" and $perheid2 > 0) {
				//P‰ivitet‰‰n is‰lle perheid jotta tiedet‰‰n, ett‰ lis‰varusteet on nyt lis‰tty
				$query = "	update tilausrivi set
							perheid2	= '$perheid2'
							where yhtio = '$kukarow[yhtio]'
							and tunnus 	= '$perheid2'";
				$updres = mysql_query($query) or pupe_error($query);
			}

			$tee 				= "Y";
			$tuoteno			= '';
			$kpl				= '';
			$var				= '';
			$hinta				= '';
			$netto				= '';
			$ale				= '';
			$rivitunnus			= '';
			$kerayspvm			= '';
			$toimaika			= '';
			$alv				= '';
			$paikka 			= '';
			$paikat				= '';
			$kayttajan_hinta	= '';
			$kayttajan_ale		= '';
			$kayttajan_netto 	= '';
			$kayttajan_var		= '';
			$kayttajan_kpl		= '';
			$kayttajan_alv		= '';
			$perheid			= '';
			$perheid2			= '';
		}
		elseif ($tee == 'TI') {
			$tee = "Y";
		}
		
		//lis‰t‰‰n rivej‰ tiedostosta
		if ($tee == 'mikrotila' or $tee == 'file') {
			require('mikrotilaus_ostotilaus.inc');
		}

		// Jee meill‰ on otsikko!
		if ($tee == 'Y') {
			echo "<font class='head'>".t("Ostotilaus").":</font><hr><br>";

			$query = "	SELECT a.fakta, l.ytunnus
						FROM toimi a, lasku l
						WHERE l.tunnus='$kukarow[kesken]' and l.yhtio='$kukarow[yhtio]' and a.yhtio = l.yhtio and a.tunnus = l.liitostunnus";
			$faktaresult = mysql_query($query) or pupe_error($query);
			$faktarow = mysql_fetch_array($faktaresult);

			echo "<table width='720' cellpadding='2' cellspacing='1' border='0'>";

			echo "<tr><th>".t("Ytunnus")."</th><th colspan='3'>".t("Toimittaja")."</th></tr>";
			echo "<tr><td>$laskurow[ytunnus]</td><td colspan='3'>$laskurow[nimi] $laskurow[nimitark]<br>$laskurow[osoite] $laskurow[postino] $laskurow[postitp]</td>";
			
			echo "<tr><th colspan='4'>".t("Toimitusosoite")."</th></tr>";
			echo "<tr><td colspan='4'>$laskurow[toim_nimi] $laskurow[toim_nimitark]<br>$laskurow[toim_osoite] $laskurow[toim_postino] $laskurow[toim_postitp]</td>";
			
			echo "<form action = 'tilaus_osto.php' method='post'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
				<input type='hidden' name='tee' value='MUUOTAOSTIKKOA'>
				<input type='hidden' name='tila' value='Muuta'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='ei_aikatarkistusta' value='$ei_aikatarkistusta'>
				<td class='back'><input type='Submit' value='".t("Muuta otsikkoa")."'></td></form></tr>";

			echo "<tr><th>".t("Tila")."</th><th>".t("Toimaika")."</th><th>".t("Tilausnumero")."</th<th>".t("Valuutta")."</th><td class='back'></td></tr>";

			echo "<tr><td>$laskurow[tila]</td>
				<td>".tv1dateconv($laskurow["toimaika"])."</td><td>$laskurow[tunnus]</td>
				<td>{$laskurow["valkoodi"]}</td>
				<td class='back'>
				<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='mikrotila'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
				<input type='hidden' name='ei_aikatarkistusta' value='$ei_aikatarkistusta'>
				<input type='Submit' value='".t("Lue tilausrivit tiedostosta")."'></form>
				</td></tr>
				<tr><th>".t("Fakta")."</th><td colspan='3'>$faktarow[fakta]&nbsp;</td></tr>";
			echo "</table><br>";

			//anntetaan mahdollisuus syottaa uusi/muokata tilausrivi/korjata virhelliset rivit
			if(file_exists("ostomenu.inc") and $toim != "HAAMU"){

				/*
					sama kuin myyntimenut @ tilaus_myynti.php
					paitti, ett‰ t‰‰ll‰ tarttetaan toi form
					
				*/	


				//	Haetaan ostomenu Array ja kysely Array
				//	Jos menutilaa ei ole laitetaan oletus
				if(!isset($menutila)) $menutila = "oletus";
				
				require("ostomenu.inc");

				//suoritetaan kysely ja tehd‰‰n menut jos aihetta
				if(is_array($ostomenu)){

					//	Tehd‰‰n menuset				
					$menuset = "<select name='menutila' onChange='submit()'>";
					foreach($ostomenu as $key => $value){
						$sel = "";
						if($key == $menutila) {
							$sel = "SELECTED";
						}

						$menuset .= "<option value='$key' $sel>$value[menuset]</option>";
					}				

					//	Jos ei olla ostomenussa n‰ytet‰‰n aina haku
					$sel = "";
					if(!isset($ostomenu[$menutila])) {
						$sel = "SELECTED";
					}

					$menuset .= "<option value='haku' $sel>Tuotehaku</option>";
					$menuset .= "</select>";

					//	Tehd‰‰n paikka menusetille
					echo  "
								<form name='myyntimenu' action = '$PHP_SELF' method='post' autocomplete='off'>
									<input type='hidden' name='toiminto' value='$toiminto'>
									<input type='hidden' name='toim' value='$toim'>									
									<input type='hidden' name='tee' value = 'Y'>
									<input type='hidden' name='tilausnumero' value='$tilausnumero'>
									<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
								<table>								
									<tr>
										<td class='back' align = 'left'><b>".t("Lis‰‰ rivi").":</b> </td><td class='back' align = 'left'>$menuset</td>
									</tr>
								</table><hr>
								</form>";


					//	Tarkastetaan viel‰, ett‰ menutila on m‰‰ritelty ja luodaan lista
					if($ostomenu[$menutila]["query"] != "") {
						unset($ulos);

						// varsinainen kysely ja menu
						$query = " SELECT distinct(tuote.tuoteno), nimitys
									FROM tuote
									LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
									WHERE tuote.yhtio ='$kukarow[yhtio]' and ".$ostomenu[$menutila]["query"];
									$tuoteresult = mysql_query($query) or pupe_error($query);	

						$ulos = "<select Style=\"width: 230px; font-size: 8pt; padding: 0\" name='tuoteno' multiple ='TRUE'><option value=''>Valitse tuote</option>";

						if(mysql_num_rows($tuoteresult) > 0) {	
							while($row=mysql_fetch_array($tuoteresult)) {
								$sel='';
								if($tuoteno==$row['tuoteno']) $sel='SELECTED';
								$ulos .= "<option value='$row[tuoteno]' $sel>$row[tuoteno] - ".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."</option>";
							}
							$ulos .= "</select>";
						}
						else {
							echo "Valinnan antama haku oli tyhj‰<br>";
						}
					}
					//	Jos haetaan niin ei ilmoitella turhia
					elseif($menutila != "haku" and $menutila != "") {
						echo "HUOM! Koitettiin hakea ostomenua '$menutila' jota ei ollut m‰‰ritelty!<br>";
					}

				}
				else {
					echo "HUOM! Koitettiin hakea ostomenuja, mutta tiedot olivat puutteelliset.<br>";
				}				
			}
			else {
				echo "<b>".t("Lis‰‰ rivi").": </b><hr>";
			}
			
			
			require('syotarivi_ostotilaus.inc');

			if ($huomio != '') {
				echo "<font class='message'>$huomio</font><br>";
				$huomio = '';
			}

			echo "<b>".t("Tilausrivit").":</b><hr>";

			if(!isset($toim_nimitykset)) {
				$toim_nimitykset = "ME";
			}
			
			$sel = array();
			$sel[$toim_nimitykset] = "CHECKED";

			echo "<form action='$PHP_SELF' method='post'><font class='info'>";
			echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
			echo "<input type='hidden' name='tee' value='Y'>";
			echo t("Nimitykset").": <input onclick='submit();' type='radio' name='toim_nimitykset' value='ME' {$sel["ME"]}> ".t("Omat")." <input onclick='submit();' type='radio' name='toim_nimitykset' value='HE' {$sel["HE"]}> ".t("Toimittajan")."";
			echo "</font></form>";
			
			// katotaan onko joku rivi jo liitetty johonkin keikkaan ja jos on niin annetaan mahdollisuus piilottaa lukitut rivit
			$query = "select * from tilausrivi where yhtio = '$kukarow[yhtio]' and otunnus = '$laskurow[tunnus]' and uusiotunnus != 0";
			$kaunisres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($kaunisres) != 0) {

				if ($naytetaankolukitut == "EI") {
					$sel_ky = "";
					$sel_ei = "CHECKED";
				}
				else {
					$sel_ky = "CHECKED";
					$sel_ei = "";
				}
				echo "<br><form action='$PHP_SELF' method='post'><font class='info'>";
				echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
				echo "<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>";
				echo "<input type='hidden' name='tee' value='Y'>";
				echo t("N‰ytet‰‰nkˆ lukitut rivit").": <input onclick='submit();' type='radio' name='naytetaankolukitut' value='kylla' $sel_ky> ".t("Kyll‰")." <input onclick='submit();' type='radio' name='naytetaankolukitut' value='EI' $sel_ei> ".t("Ei");
				echo "</font></form>";

			}
			
			// katotaan miten halutaan sortattavan
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

			//Listataan tilauksessa olevat tuotteet
			$query = "	SELECT tilausrivi.nimitys, concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
						tilausrivi.tuoteno, toim_tuoteno, toim_nimitys, concat_ws('/',tilkpl,round(tilkpl*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tilattu',
						round((varattu+jt)*tilausrivi.hinta*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*(1-(tilausrivi.ale/100)),'$yhtiorow[hintapyoristys]') rivihinta,
						tilausrivi.alv, toimaika, kerayspvm, uusiotunnus, tilausrivi.tunnus, tilausrivi.perheid2, tilausrivi.hinta, tilausrivi.ale, tilausrivi.varattu varattukpl, tilausrivi.kommentti,
						$sorttauskentta,
						tilausrivi.var,
						tilausrivi.var2,
						tilausrivi.jaksotettu,
						tuote.kehahin keskihinta,
						tuotteen_toimittajat.ostohinta,
						tuotteen_toimittajat.valuutta
						FROM tilausrivi
						LEFT JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
						LEFT JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
						WHERE otunnus = '$kukarow[kesken]'
						and tilausrivi.yhtio='$kukarow[yhtio]'
						and tilausrivi.tyyppi='O'
						ORDER BY sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus";
			$presult = mysql_query($query) or pupe_error($query);

			$rivienmaara = mysql_num_rows($presult);

			echo "<table border='0' cellspacing='1' cellpadding='2'><tr>";
			echo "<th>#</th>";

			echo "<th align='left'>".t("Nimitys")."</th>";
			echo "<th align='left'>".t("Paikka")."</th>";
			echo "<th align='left'>".t("Tuote")."</th>";
			echo "<th align='left'>".t("Toim Tuote")."</th>";
			echo "<th align='left'>".t("M‰‰r‰")."</th>";
			echo "<th align='left'>".t("Ostohinta")."</th>";
			echo "<th align='left'>".t("Ale")."</th>";
			echo "<th align='left'>".t("Alv")."</th>";
			echo "<th align='left'>".t("Rivihinta")."</th>";
			echo "</tr>";

			$yhteensa 		= 0;
			$nettoyhteensa 	= 0;
			$eimitatoi 		= '';
			$lask 			= mysql_num_rows($presult);
			$tilausok 		= 0;

			while ($prow = mysql_fetch_array ($presult)) {

				$yhteensa += $prow["rivihinta"];
				$class = "";

				if ($prow["uusiotunnus"] == 0 or $naytetaankolukitut != "EI") {

					echo "<tr>";

					// Tuoteperheiden lapsille ei n‰ytet‰ rivinumeroa
					if ($prow["perheid"] == $prow["tunnus"] or ($prow["perheid2"] == $prow["tunnus"] and $prow["perheid"] == 0) or ($prow["perheid2"] == -1)) {

						if ($prow["perheid2"] == 0 or $prow["perheid2"] == -1) {
							$pklisa = " and (perheid = '$prow[tunnus]' or perheid2 = '$prow[tunnus]')";
						}
						elseif ($prow["perheid"] == 0) {
							$pklisa = " and perheid2 = '$prow[perheid2]'";
						}
						else {
							$pklisa = " and (perheid = '$prow[perheid]' or perheid2 = '$prow[perheid]')";
						}

						$query = "	SELECT count(*), count(*)
									FROM tilausrivi use index (yhtio_otunnus)
									WHERE yhtio = '$kukarow[yhtio]'
									and otunnus = '$kukarow[kesken]'
									$pklisa
									and tyyppi != 'D'";
						$pkres = mysql_query($query) or pupe_error($query);
						$pkrow = mysql_fetch_array($pkres);

						if ($prow["perheid2"] == 0 or $prow["perheid2"] == -1) {
							$query  = "	SELECT tuoteperhe.tunnus
										FROM tuoteperhe
										WHERE tuoteperhe.yhtio 		= '$kukarow[yhtio]'
										and tuoteperhe.isatuoteno 	= '$prow[tuoteno]'
										and tuoteperhe.tyyppi 		= 'L'";
							$lisaresult = mysql_query($query) or pupe_error($query);
							
							$lisays = mysql_num_rows($lisaresult)+1;
						}
						else {
							$lisays = 0;
						}
						
						$pkrow[1] += $lisays;
						
						if ($prow["perheid2"] == -1) {
							$pkrow[1]++;	
						}

						$pknum = $pkrow[0] + $pkrow[1];
						$borderlask = $pkrow[1];


						echo "<td valign='top' rowspan='$pknum' $class style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;' >$lask</td>";
					}
					elseif($prow["perheid"] == 0 and $prow["perheid2"] == 0) {
						echo "<td rowspan = '2' valign='top'>$lask</td>";
						
						$borderlask		= 0;
						$pknum			= 0;
					}
					
					$lask--;
					$classlisa = "";

					if($borderlask == 1 and $pkrow[1] == 1 and $pknum == 1) {
						$classlisa = $class." style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
						$class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

						$borderlask--;
					}
					elseif($borderlask == $pkrow[1] and $pkrow[1] > 0) {
						$classlisa = $class." style='border-top: 1px solid; border-right: 1px solid;' ";
						$class    .= " style='border-top: 1px solid;' ";

						$borderlask--;
					}
					elseif($borderlask == 1) {
						$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
						$class    .= " style='font-style:italic; ' ";
						
						$borderlask--;
					}
					elseif($borderlask > 0 and $borderlask < $pknum) {
						$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
						$class    .= " style='font-style:italic;' ";
						$borderlask--;
					}
										
					if ($toim != "HAAMU") {
						if($toim_nimitykset == "HE") {
							echo "<td valign='top' $class>{$prow["toim_tuoteno"]} {$prow["toim_nimitys"]}</td>";
						}
						else {
							echo "<td valign='top' $class>".asana('nimitys_',$prow['tuoteno'],$prow['nimitys'])."</td>";	
						}
					}
					else {
						echo "<td valign='top' $class>{$prow["kommentti"]}</td>";
					}
					
					
					echo "<td valign='top' $class>$prow[paikka]</td>";

					$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$prow[tuoteno]'";
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);

					// tehd‰‰n pop-up divi jos keikalla on kommentti...
					if ($prow["tunnus"] != "") {
						list ($saldo, $hyllyssa, $myytavissa, $bool) = saldo_myytavissa($prow["tuoteno"]);
						echo "<div id='$prow[tunnus]' class='popup' style='width: 400px;'>";
						echo "<ul>";
						echo "<li>".t("Saldo").": $saldo ".t("kpl")."</li><li>".t("Hyllyss‰").": $hyllyssa ".t("kpl")."</li><li>".t("Myyt‰viss‰").": $myytavissa ".t("kpl")."</li>";
						echo "<li>".t("Tilattu").": $prow[tilattu] ".t("kpl")."</li><li>".t("Varattu").": $prow[varattukpl] ".t("kpl")."</li>";
						echo "<li>".t("Keskihinta").": $prow[keskihinta] $prow[valuutta]</li><li>".t("Ostohinta").": $prow[ostohinta] $prow[valuutta]</li>";
						echo "</ul>";
						echo "</div>";
						echo "<td valign='top' $class><a href='../tuote.php?tee=Z&tuoteno=$prow[tuoteno]' onmouseout=\"popUp(event,'$prow[tunnus]')\" onmouseover=\"popUp(event,'$prow[tunnus]')\">$prow[tuoteno]</a>";
					}
					else {
						echo "<td valign='top' $class><a href='../tuote.php?tee=Z&tuoteno=$prow[tuoteno]'>$prow[tuoteno]</a>";
					}

					if ($sarjarow["sarjanumeroseuranta"] != "") {
						$query = "	SELECT count(*) kpl 
									from sarjanumeroseuranta 
									where yhtio='$kukarow[yhtio]' and tuoteno='$prow[tuoteno]' and ostorivitunnus='$prow[tunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
						$sarjarow = mysql_fetch_array($sarjares);

						if ($sarjarow["kpl"] == abs($prow["varattukpl"])) {
							echo " (<a href='sarjanumeroseuranta.php?tuoteno=$prow[tuoteno]&ostorivitunnus=$prow[tunnus]&from=riviosto' style='color:00FF00'>".t("S:nro ok")."</font></a>)";
						}
						else {
							echo " (<a href='sarjanumeroseuranta.php?tuoteno=$prow[tuoteno]&ostorivitunnus=$prow[tunnus]&from=riviosto'>".t("S:nro")."</a>)";
						}						
					}

					echo "</td>";


					echo "<td valign='top' $class>$prow[toim_tuoteno]</td>";
					echo "<td valign='top' $class align='right'>$prow[tilattu]</td>";
					echo "<td valign='top' $class align='right'>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $prow["hinta"])."</td>";
					echo "<td valign='top' $class align='right'>".((float) $prow["ale"])."</td>";
					echo "<td valign='top' $class align='right'>".((float) $prow["alv"])."</td>";
					echo "<td valign='top' $classlisa align='right'>$prow[rivihinta]</td>";


					if ($prow["uusiotunnus"] == 0) {

						// Tarkistetaan tilausrivi
						if ($toim != "HAAMU") {
							require("tarkistarivi_ostotilaus.inc");
						}
						

						echo "	<td valign='top' class='back' nowrap>
								<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='tilausnumero' 	value='$tilausnumero'>
								<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>								
								<input type='hidden' name='rivitunnus' 		value = '$prow[tunnus]'>
								<input type='hidden' name='menutila' 		value = '$menutila'>
								<input type='hidden' name='toim' 			value = '$toim'>
								<input type='hidden' name='tee' 			value = 'PV'>
								<input type='hidden' name='ei_aikatarkistusta' value='$ei_aikatarkistusta'>
								<input type='Submit' value='".t("Muuta")."'>
								</td></form>";
																			
						if ($saako_hyvaksya > 0) {
							echo "<td valign='top' class='back'>
									<form action='$PHP_SELF' method='post'>
									<input type='hidden' name='tilausnumero' 	value='$tilausnumero'>
									<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>								
									<input type='hidden' name='rivitunnus' 		value = '$prow[tunnus]'>
									<input type='hidden' name='menutila' 		value = '$menutila'>
									<input type='hidden' name='tee' 			value = 'OOKOOAA'>
									<input type='Submit' value='".t("Hyv‰ksy")."'>
									</form></td> ";
						}

						if ($varaosavirhe != '') {
							echo "<td valign='top' class='back'>$varaosavirhe</td>";
						}
							
						if ($varaosavirhe == "") {
							//Tutkitaan tuotteiden lis‰varusteita
							$query  = "	SELECT *
										FROM tuoteperhe
										JOIN tuote ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.tuoteno
										WHERE tuoteperhe.yhtio 		= '$kukarow[yhtio]'
										and tuoteperhe.isatuoteno 	= '$prow[tuoteno]'
										and tuoteperhe.tyyppi 		= 'L'
										order by tuoteperhe.tuoteno";
							$lisaresult = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($lisaresult) > 0 and $prow["perheid2"] == -1) {

								echo "</tr>";

								echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
										<input type='hidden' name='tilausnumero' 	value='$tilausnumero'>
										<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
										<input type='hidden' name='toim' 			value='$toim'>
										<input type='hidden' name='tee' 			value='TI'>
										<input type='hidden' name='lisavarusteita' 	value='ON'>
										<input type='hidden' name='perheid2'	 	value='$prow[tunnus]'>";

								if ($alv=='') $alv=$laskurow['alv'];
								$lask = 0;
								$borderlask--;
								
								while ($xprow = mysql_fetch_array($lisaresult)) {
									echo "<tr><td class='spec'>".asana('nimitys_',$xprow['tuoteno'],$xprow['nimitys'])."</td><td></td>";
									echo "<td><input type='hidden' name='tuoteno_array[$xprow[tuoteno]]' value='$xprow[tuoteno]'>$xprow[tuoteno]</td>";
									echo "<td></td>";
									echo "<td><input type='text' name='kpl_array[$xprow[tuoteno]]' size='5' maxlength='5'></td>
											<td><input type='text' name='hinta_array[$xprow[tuoteno]]' size='5' maxlength='12'></td>
											<td><input type='text' name='ale_array[$xprow[tuoteno]]' size='5' maxlength='6'></td>
											<td>".alv_popup_oletus('alv',$alv)."</td>
											<td style='border-right: 1px solid;'></td>";
									$lask++;
									$borderlask--;

									if ($lask == mysql_num_rows($lisaresult)) {
										echo "<td class='back'><input type='submit' value='".t("Lis‰‰")."'></td>";
										echo "</form>";
									}
									echo "</tr>";
								}
							}
							elseif(mysql_num_rows($lisaresult) > 0 and $prow["perheid2"] != -1) {
								echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
										<input type='hidden' name='tilausnumero' 	value='$tilausnumero'>
										<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
										<input type='hidden' name='toim' 			value='$toim'>
										<input type='hidden' name='tee' 			value='LISLISAV'>
										<input type='hidden' name='rivitunnus' 		value='$prow[tunnus]'>
										<input type='hidden' name='menutila' value = '$menutila'>
										<td class='back'><input type='submit' value='".t("Lis‰‰ lis‰varusteita tuotteelle")."'></td>
										</form>";
								echo "</tr>";
							}
						}
						else {
							echo "</tr>";
						}
					}
					else {
						echo "<td class='back'>".t("Lukittu")."</td>";
						$eimitatoi = "EISAA";
						echo "</tr>";
					}
					
					echo "<tr>";
					
					if ($borderlask == 0 and $pknum > 1) {
						$kommclass1 = " style='border-bottom: 1px solid; border-right: 1px solid;'";
						$kommclass2 = " style='border-bottom: 1px solid;'";
					}
					elseif($pknum > 0) {
						$kommclass1 = " style='border-right: 1px solid;'";
						$kommclass2 = "";
					}
					else {
						$kommclass1 = "";
						$kommclass2 = "";
					}
										
					if($prow["jaksotettu"] == 1) {
						echo "<td $kommclass2><font style = 'color: #00FF00;'>".t("Vahvistettu toimitusaika").": ".tv1dateconv($prow["toimaika"])."</font></td>";
					}
					else {
						
						if ($paivitetty_ok == "YES") {
							echo "<td $kommclass2>".t("Toimitusaika").": ".tv1dateconv($ehdotus_pvm)."</td>";
						}
						else {
							echo "<td $kommclass2>".t("Toimitusaika").": ".tv1dateconv($prow["toimaika"])."</td>";
						}
					}
					
					echo "<td colspan='8' $kommclass1>".t("Kommentti").": $prow[kommentti]</td></tr>";
				}
			}
			
			if($toim == "HAAMU") {
				$kopiotoim = "HAAMU";
			}
			else {
				$kopiotoim = "OSTO";
			}
			echo "	<tr>
					<th colspan='2' nowrap>".t("N‰yt‰ ostotilaus").":</th>
					<td colspan='2' nowrap>
					<form name='valmis' action='tulostakopio.php' method='post' name='tulostakopio'>
						<input type='hidden' name='otunnus' value='$tilausnumero'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>						
						<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
						<input type='hidden' name='toim' value='$kopiotoim'>
						<input type='hidden' name='nimitykset' value='JOO'>
						<input type='hidden' name='lopetus' value='$PHP_SELF////toim=$toim//tilausnumero=$tilausnumero//from=LASKUTATILAUS//lopetus=$lopetus//ei_aikatarkistusta=$ei_aikatarkistusta//tee='>
						<input type='submit' name='NAYTATILAUS' value='".t("N‰yt‰")."'>
						<input type='submit' name='TULOSTA' value='".t("Tulosta")."'>
					</form>
				</td>
				<td class='back' colspan='2'></td>

				<td colspan='3' class='spec'>Tilauksen arvo:</td>
				<td align='right' class='spec'>".sprintf("%.2f",$yhteensa)."</td>
				</tr>";
			
			echo "</table>";


			echo "<br><br><table width='100%'><tr>";

			if ($rivienmaara > 0 and $laskurow["liitostunnus"] != '' and $tilausok == 0) {
				echo "	<td class='back' align='left'>
						<form action = '$PHP_SELF' method='post'>
							<input type='hidden' name='tilausnumero' value='$tilausnumero'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tee' value='valmis'><input type='Submit' value='".t("Tilaus valmis")."'>
						</form></td>";
					
				if ($toim != "HAAMU") {
					echo "	<td class='back' align='left'>
							<form action = '$PHP_SELF' method='post'>
								<input type='hidden' name='tilausnumero' value='$tilausnumero'>
								<input type='hidden' name='ei_aikatarkistusta' value='$ei_aikatarkistusta'>
								<input type='hidden' name='tee' value='vahvista'><input type='Submit' value='".t("Vahvista toimitus")."'>
							</form></td>";
				}
				
			}
			if ($eimitatoi != "EISAA") {
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>
							function verify(){
									msg = '".t("Haluatko todella poistaa t‰m‰n tietueen?")."';
									return confirm(msg);
							}
					</SCRIPT>";
				echo "	<form action = '$PHP_SELF' method='post' onSubmit = 'return verify()'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='toim' value='$toim'>
						<td class='back' align='right'><input type='hidden' name='tee' value='poista'><input type='Submit' value='*".t("Mit‰tˆi koko tilaus")."*'></form></td>";

			}
			elseif ($laskurow["tila"] == 'O') {
				echo "	<form action = '$PHP_SELF' method='post'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
						<td class='back' align='right'><input type='hidden' name='tee' value='poista_kohdistamattomat'><input type='Submit' value='*".t("Mit‰tˆi kohdistamattomat rivit")."*'></form></td>";

			}
			echo "</tr></table>";
		}

		if ($tee == "") {
			require("otsik_ostotilaus.inc");
		}
	}

	require("../inc/footer.inc");
?>
