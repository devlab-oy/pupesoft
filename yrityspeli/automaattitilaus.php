<?php
/// automaattitilaus.php
/// TAMK Yrityspeli, sutinan automaattitilausajo
/// author: Jarmo Kortetj‰rvi
/// created: 2010-05-03
/// updated: 2010-12-08

error_reporting(E_ALL ^ E_NOTICE);
chdir('/var/www/html/pupesoft/yrityspeli/');
date_default_timezone_set('Europe/Helsinki');
require_once 'laskeKerroin.php';

//* tietokantayhteys
	require_once '/var/www/html/pupesoft/inc/salasanat.php';
	$link = mysql_connect($dbhost, $dbuser, $dbpass) or die ('Error connecting database. ' . mysql_error());
	// Select database
	$result = mysql_select_db($dbkanta, $link) or die('Error selecting database. ' . mysql_error());

	if (!$result) {
		print 'Ongelma avatessa tietokantaa!';
	} 
	
// tarkistetaan onko tekem‰ttˆmi‰ maksuja
	$now = date('Y-m-d H:i:s');
	
	$query = "	SELECT 	*
				FROM	TAMK_automaattitilaus
				WHERE	suoritettu IS NOT TRUE
				AND 	tilausaika < '$now'
			";
	$tilaukset = mysql_query($query);

	if(mysql_num_rows($tilaukset)>0){
		// haetaan automaattitilausten kertoimet
			$query = " 	SELECT *
						FROM TAMK_automaattitilauskerroin
					";
		
			$kertoimet = mysql_query($query);
			$kerroinrivi = array();

			while($rivi = mysql_fetch_assoc($kertoimet)) {
				$kerroinrivi[$rivi['tilaustyyppi']] = $rivi['painoarvo'];
			}
	}
	
	// Myyr‰n y-tunnus
	$myyranytunnus = 32419754;
	
while($tilaus = mysql_fetch_assoc($tilaukset)){
	// HAETAAN ASIAKAS JA TOIMITTAJA
	
		// haetaan asiakkaat myyr‰n sis‰lt‰
			$query = "	SELECT	* 
						FROM	yhtion_toimipaikat
						WHERE	yhtio = 'myyra'
					";
			$asiakasresult = mysql_query($query);
		
		// k‰yd‰‰n l‰pi kaikki asiakkaat	
			$asiakasrows =  mysql_num_rows($asiakasresult);
			
			//$laatija = mysql_result($asiakasresult, $asiakas, 'nimi');
			$laatija = "sutina";
			
		// haetaan toimittajat
			$query = " 	SELECT yhtio.ytunnus 		AS 'ytunnus'
								, yhtio.yhtio		AS 'yhtio'
								, toimi.tunnus 		AS 'tunnus'
								, toimi.fakta		AS 'viikkotilaus'
								, yhtio.nimi 		AS 'nimi'
								, yhtio.osoite		AS 'osoite'
								, yhtio.postitp		AS 'postitp'
								, yhtio.postino		AS 'postino'
								, yhtio.maa			AS 'maa'
								, yhtio.ovttunnus	AS 'ovttunnus'
								, toimi.email		AS 'email'
						FROM yhtio 
						JOIN toimi 
						ON yhtio.ytunnus = toimi.ytunnus 
						WHERE 	yhtio.yhtiotyyppi = 'OPY' 
						AND		toimi.tyyppi != 'P'
						AND		toimi.yhtio = 'myyra'
						AND		toimi.fakta != ''
					";
			$toimittajaresult = mysql_query($query);
			$toimittajarows =  mysql_num_rows($toimittajaresult);
			
	for($j=0; $j<$toimittajarows;$j++){
		$toimittaja = $j;
		
		// satunnainen asiakas 
		 $asiakas = rand(0, $asiakasrows-1);
		
		// satunnainen toimittaja
		// $toimittaja = rand(0, $numrows-1);
	
		// TEHDƒƒN LASKU
		
		// OVT-tunnus
		$ovttunnus = "0037" . $myyranytunnus . mysql_result($asiakasresult, $asiakas, 'ovtlisa');

		// syˆtet‰‰n tiedot laskuun
		$laskuquery = "
					INSERT INTO lasku
					(
						yhtio				
						, nimi				
						, osoite			
						, postino			
						, postitp			
						, maa				
						, toim_nimi	
						, toim_osoite	
						, toim_postino
						, toim_postitp
						, toim_maa
						, valkoodi
						, toimaika
						, laatija
						, luontiaika
						, lahetepvm
						, tila
						, alatila
						, maksuteksti
						, toimitusehto
						, liitostunnus
						, ytunnus
						, ovttunnus
						, vienti_kurssi
						, viikorkopros
						, myyja
						, tilausyhteyshenkilo
						
					)
					VALUES
					(
						'".mysql_result($asiakasresult, $asiakas, 'yhtio')."'
						, '".mysql_result($toimittajaresult, $toimittaja,'nimi')."'
						, '".mysql_result($toimittajaresult, $toimittaja,'osoite')."'
						, ".mysql_result($toimittajaresult, $toimittaja,'postino')."
						, '".mysql_result($toimittajaresult, $toimittaja,'postitp')."'
						, '".mysql_result($toimittajaresult, $toimittaja,'maa')."'
						, '".mysql_result($asiakasresult, $asiakas, 'nimi')."'
						, '".mysql_result($asiakasresult, $asiakas, 'osoite')."'
						, '".mysql_result($asiakasresult, $asiakas, 'postino')."'
						, '".mysql_result($asiakasresult, $asiakas, 'postitp')."'
						, '".mysql_result($asiakasresult, $asiakas, 'maa')."'
						, 'EUR'
						, ADDDATE(NOW(), INTERVAL 7 DAY) 
						, '$laatija'
						, now()
						, now()
						, 'O'
						, 'A'
						, '14 pv netto'
						, 'TOP'
						, '".mysql_result($toimittajaresult, $toimittaja, 'tunnus')."'
						, '".mysql_result($toimittajaresult, $toimittaja, 'ytunnus')."'
						, '$ovttunnus'
						, 1
						, 10
						, ''
						, 'Ostop‰‰llikkˆ'
					)
				";

		mysql_query("START TRANSACTION");
		
		/* Muokattu 16.1.2014, laitettu queryn tulos muuttujaan $onnistuiko_lasku */
		$onnistuiko_lasku = mysql_query($laskuquery); // tehd‰‰n lasku
		$tunnus = mysql_insert_id();

	// LASKETAAN TILAUKSEN SUURUUS
		// toimittajan viikottaisten tilausten summa					
			$viikkotilaus = mysql_result($toimittajaresult, $toimittaja, 'viikkotilaus');
		
		// haetaan toimittajan sutinakerroin
			// yhtion nimi
				$yhtio = mysql_result($toimittajaresult, $toimittaja, 'yhtio');
				
		// haetaan painoarvo tilaustyypin perusteella
			$tilaustyyppi = $tilaus['tilaustyyppi'];
			$painoarvo = $kerroinrivi[$tilaustyyppi];
		
					
			// tilien saldot
				// haetaan vain viimeisen kuukauden ajalta
				$now = date("Y-m-d");
				$monthAgo = date("Y-m-d", strtotime("-1 month"));
			
				$saldo = getSaldo($yhtio, $monthAgo, $now);

					// Mainosten klikkaukset
					$klikkaukset = getClicks($yhtio);
				$markkinointi = laskeMarkkinointi($saldo[800]+$saldo[805]+$klikkaukset);
				
				$sijainti = laskeSijainti($saldo[720]);
				$asiakassuhteet = laskeAsiakassuhteet($saldo[795]);
				$henkilostopanos = laskeHenkilostopanos($saldo[700]);
				
				$tuntiquery = "	SELECT SUM(tunnit) AS tunnit
								FROM TAMK_tyoaika
								WHERE yhtio = '$yhtio';
								";
				// TODO: WHERE tuntien tekij‰ on oikea ihminen
				$tuntiresult = mysql_query($tuntiquery);
				$tunnit = mysql_result($tuntiresult,0,'tunnit');
				$tyotunnit = laskeTyotunnit($tunnit);
			
			// TODO:
				$crm = laskeCRM($saldo[700]);
				$opykauppa = laskeOpykauppa($saldo[7207]);
			
			// kokonaiskerroin
				$kokonaiskerroin = ($opykauppa+$markkinointi+$sijainti+$asiakassuhteet+$henkilostopanos+$tyotunnit+$crm)/7;
			
			// edit: JukkaT 11.11.2011
			// Tilauksen suuruus ei ottanut aikaisemmin huomioon sen prosenttiosuutta $tilaus['summa'] 
			// vaan k‰ytti yhteen tilaukseen aina viikkotilauksen m‰‰r‰n
			$viikkotilaus = ($viikkotilaus * ($painoarvo/100)) * $kokonaiskerroin;
 
			$tilaus_viikko_osuus = $tilaus['summa'];
		        $tilaussumma = round((($tilaus_viikko_osuus/100) * $viikkotilaus), 2);  	
			
			// viikkotilauksen summa
			//	$tilaussumma = round ( ($kokonaiskerroin * $viikkotilaus * $painoarvo) , 2 );
			
	// TEHDƒƒN TILAUSRIVIT	
		// haetaan toimittajan tuotteet	
		
			$prequery = "	SELECT
							tuotteen_toimittajat.toimittaja
							, tuote.yhtio 		AS 'yhtio'
							, tuote.nimitys		AS 'nimitys'					
							, tuote.tuoteno		AS 'tuoteno'
							, tuote.try			AS 'try'
							, tuote.osasto		AS 'osasto'
							, tuote.nimitys		AS 'nimitys'
							, tuote.yksikko		AS 'yksikko'
							, tuote.tunnus		AS 'tuotetunnus'
							, tuotteen_toimittajat.ostohinta	AS 'ostohinta'
						FROM tuotteen_toimittajat 
						JOIN tuote ON tuotteen_toimittajat.tuoteno = tuote.tuoteno 
						WHERE tuotteen_toimittajat.toimittaja = '".mysql_result($toimittajaresult, $toimittaja,'yhtio.ytunnus')."' 
						AND tuotteen_toimittajat.yhtio = 'myyra'
						AND tuote.status != 'P'
					";
			$postquery = " AND tuote.osasto = $tilaustyyppi";
			$riviquery = $prequery.$postquery;
			
			$tuoteresult = mysql_query($riviquery);
			
			// tarkistetaan ett‰ sutinatuotteita lˆytyy
			if($tilaustyyppi != 2){
				if(mysql_num_rows($tuoteresult)==0){
					$postquery = " AND tuote.osasto = 2";
					$replacequery = $prequery.$postquery;
					//echo $replacequery;
					$tuoteresult = mysql_query($replacequery)or die("Query failed.");
				}
			}				
			
			$tuoterows = mysql_num_rows($tuoteresult);
		
		/* Muokattu 16.1.2014, lis‰tty ehtoon FALSE tarkistus */
		if($tuoterows>0 and $tuoterows != FALSE){
	
			$tilausquery = "
						INSERT INTO tilausrivi
						(
							yhtio				
							, tyyppi				
							, toimaika
							, kerayspvm
							, otunnus
							, tuoteno
							, try
							, osasto
							, nimitys
							, tilkpl
							, varattu
							, yksikko
							, hinta
							, laatija
							, laadittu
						)
						VALUES
					";
					
			// tilausrivien m‰‰r‰, muokattu 16.1.2014, lis‰tty tyyppimuunnos (int)
				$tilausrivit = (int)$tilaus['tilausrivit'];
				
			// lasketaan tuotteiden m‰‰r‰ tilausriviin
				// absoluuttinen osuus
					$osuus = round(100/$tilausrivit);
				
				// kokonaisosuus
					$laskuri = 0;
			
			/* Muokattu 16.1.2014, lis‰tty ehto jonka avulla estet‰‰n tyhjien tilauksen syntyminen */
			if ($tilausrivit > 0)
			{
				// luodaan tilausrivit
				for($i=0; $i < $tilausrivit; $i++){
				
					// lasketaan tuotteiden m‰‰r‰ tilausriviin
						// osuuteen satunnaisuutta
							$osuus = $osuus + rand(-($osuus/$tilausrivit)*2,($osuus/$tilausrivit)*2);
							
						// viimeiselle kierrokselle loput prosentit
							if($i == $tilausrivit-1){
								$osuus = 100 - $laskuri;
								if ($osuus < 1) $osuus = 1;
							}
						
							$laskuri += $osuus;
					
						// tilausriviin k‰ytett‰v‰ summa
							$tilausosuus = $tilaussumma * ($osuus/100);
				
						// arvotaan tuote
							$tuote = rand(0, $tuoterows-1);
					
						// tilausrivin tuotem‰‰r‰
							$hinta = mysql_result($tuoteresult, $tuote, 'ostohinta');
							$tuotemaara = ceil($tilausosuus / $hinta);
							if($tuotemaara<1)$tuotemaara=1;
					
					// tehd‰‰n tilausrivi
						$tilausquery .= "	(
										'".mysql_result($tuoteresult, $tuote, 'yhtio')."'
										, 'O'
										, now()
										, now()
										, '$tunnus'
										, '".mysql_result($tuoteresult, $tuote, 'tuoteno')."'
										, '".mysql_result($tuoteresult, $tuote, 'try')."'
										, '".mysql_result($tuoteresult, $tuote, 'osasto')."'
										, '".mysql_result($tuoteresult, $tuote, 'nimitys')."'
										, $tuotemaara
										, $tuotemaara
										, '".mysql_result($tuoteresult, $tuote, 'yksikko')."'
										, '$hinta'
										, '$laatija'
										, now()
									),";
					
					// merkit‰‰n random-tuote sutinatuotteeksi
					$tuoteno = mysql_result($tuoteresult, $tuote, 'tuoteno');
					if($tilaustyyppi == 2){
						$tuotetunnus = mysql_result($tuoteresult, $tuote, 'tuotetunnus');
					
						if($i==0){
							$osastoquery = "UPDATE tuote SET osasto = 2 WHERE tunnus = $tuotetunnus";
						}
						else{
							$osastoquery .= "OR tunnus = $tuotetunnus";
						}			
					}
				}
			}
			// napataan viimeinen pilkku pois
				$tilausquery = substr($tilausquery, 0, -1);
				
				/* Muokattu 16.1.2014, laitettu queryn tulos muuttujaan $onnistuiko_tilaus */
				$onnistuiko_tilaus = mysql_query($tilausquery); // tehd‰‰n tilausrivi
				
				// tuoteryhmien p‰ivitys
				mysql_query($osastoquery);
			
			// tehd‰‰n kyselyt (Muokattu 16.1.2014, vaihdettu ehdon muuttujat lis‰ttyihin muuttujiin [ennen: $tilausquery && $laskuquery])
			if($onnistuiko_tilaus && $onnistuiko_lasku){
				mysql_query("COMMIT");
				// l‰hetet‰‰n tilaus s‰hkˆpostissa toimittajalle
				$email = mysql_result($toimittajaresult, $toimittaja,'email');
				$done = exec("php /var/www/html/pupesoft/tilauskasittely/tulostaOstotilaus.php $tunnus $email");
				if(!empty($done)) echo "L‰hetetty tilaus $tunnus s‰hkˆpostiin: $email.\n";
				else echo "Virhe tilauksessa $tunnus.\n";
			}
			else{
				mysql_query("ROLLBACK");
			}
		}
		else
		{
			/* Lis‰tty 27.1.2014, lis‰tty else haara jossa kumotaan kaikki tehdyt muutokset ROLLBACK kyselyn avulla */
			mysql_query("ROLLBACK");
		}
	}
	// merkit‰‰n tilaus tehdyksi
	$tilausID = $tilaus['id'];
	$updatequery = "	UPDATE TAMK_automaattitilaus
						SET suoritettu = 1,
						suoritusaika = now()
						WHERE id = $tilausID
					";
	mysql_query($updatequery);
	echo "Done.\n";
}

?>
