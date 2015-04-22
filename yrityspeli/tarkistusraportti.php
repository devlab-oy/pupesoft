<?php
/// tarkistusraportti.php
/// TAMK Yrityspeli-valikko, erilaisia tarkistusraportteja (pakollisten tietojen tarkistuksia)
///
/// Annika Granlund, Jarmo Kortetjärvi
/// created: 2010-06-15
/// modified: 2010-09-08

require_once "../inc/parametrit.inc";

print "<font class='head'>Tarkistusraportti</font><hr>
		<font>Erilaisten pakollisten kenttien tarkistusraportteja.</font><br/><br/>";

		// Yritysten asiakkaat, joilta puuttuu ovt-tunnus
		print "<font class='message'>Kaikkien yritysten asiakkaat, joilta puuttuu ovt-tunnus:</font>";

		// haetaan kannasta ne yritykset, joilta puuttuu ovt-tunnus
		$query = "	SELECT		yhtio.nimi AS 'yritys'
								, asiakas.nimi AS 'asiakas'
								, asiakas.asiakasnro AS 'asiakasnro'
					FROM		asiakas
					JOIN 		yhtio
					ON			asiakas.yhtio = yhtio.yhtio
					WHERE		asiakas.ovttunnus = ''
					AND		yhtio.noreport != 1
					";
			
		haeTarkistusRaportti($query, 'yritys', 'Onneksi olkoon, kaikilla asiakkailla on ovt-tunnus.', 'asiakas', 'asiakasnro' );
		
		// Yritysten toimittajat, joilta puuttuu ovt-tunnus
		print "<font class='message'>Kaikkien yhtiöiden toimittajat, joilta puuttuu ovt-tunnus:</font>";
		
		$query = "	SELECT 		yhtio.nimi AS yritys
								, toimi.nimi AS toimittaja
					FROM 		toimi
					JOIN		yhtio
					ON			toimi.yhtio = yhtio.yhtio
					WHERE		toimi.ovttunnus = ''
					AND		yhtio.noreport != 1
					";
		
		haeTarkistusRaportti($query, 'yritys', 'Onneksi olkoon, kaikilla toimittajilla on ovt-tunnus.', 'toimittaja', 'asiakasnro' );
		
		// Yritykset, joilta puuttuu y-tunnus
		print "<font class='message'>Yritykset, joilta puuttuu y-tunnus:</font>";
		
		$query = "	SELECT		yhtio.nimi AS yritys
					FROM		yhtio
					JOIN		asiakas
					ON			yhtio.yhtio = asiakas.yhtio
					WHERE		yhtio.ytunnus = ''
					AND			asiakas.laji != 'P'
					AND		yhtio.noreport != 1
					";
					
		haeTarkistusRaportti($query, 'yritys', 'Onneksi olkoon, kaikilla yrityksillä on y-tunnus.');
		
		// Yritysten asiakkaat, joilta joko puuttu verkkolaskutunnus tai se on virheellinen
		// HUOM! Tässä trimmataan välilyönnit alusta ja lopusta pois! Jos ne halutaan myös tarkistaa, TRIM otettava pois
		print "<font class='message'>Kaikkien yritysten asiakkaat, joilta puuttuu verkkolaskutunnus tai se on virheellinen:</font>";
		
		$query = "	SELECT	yhtio.nimi AS yritys
							, asiakas.nimi AS asiakas
							, asiakas.verkkotunnus AS verkkolaskutunnus
					FROM 	asiakas 
					JOIN 	yhtio 
					ON 		asiakas.yhtio = yhtio.yhtio 
					WHERE	NOT (TRIM(asiakas.verkkotunnus) REGEXP '^([0-9]{17})@NDEAFIHH$')
					AND		asiakas.laji != 'P'
					AND		yhtio.noreport != 1
					";
		
		haeTarkistusRaportti ($query, 'yritys', 'Onneksi olkoon, kaikilla yrityksillä on oikeanlainen verkkolaskutunnus.', 'asiakas', 'verkkolaskutunnus' );
		
		// Yritysten asiakkaat, joiden laskun kanavointitieto ei ole verkkolasku
		print "<font class='message'>Kaikkien yritysten asiakkaat, joiden laskun kanavointitieto ei ole verkkolasku (010):</font><br/>
				<font>Vaihda kanavointitieto verkkolaskuksi.</font>";
		
		$query = "	SELECT 		yhtio.nimi AS yritys
								, asiakas.nimi AS asiakas
								, asiakas.chn AS kanavointitieto
					FROM 		asiakas 
					JOIN 		yhtio 
					ON 			asiakas.yhtio = yhtio.yhtio 
					WHERE 		asiakas.chn <> 010
					AND			asiakas.laji != 'P'
					AND		yhtio.noreport != 1
					";
		
		haeTarkistusRaportti ($query, 'yritys', 'Onneksi olkoon, kaikilla yrityksillä laskun kanavointitieto on verkkolasku.', 'asiakas', 'kanavointitieto' );
		
		// Tuotteet, joiden paino puuttuu (mutta eivät ole merkattu palvelutuotteiksi)
		print "<font class='message'>Tuotteet, joilta puuttuu paino, mutta joita ei ole merkattu palvelutuotteiksi:</font><br/>
				<font>Käy lisäämässä tuotteille paino. <br/>
				Mikäli kyseessä on palvelutuote, käy lisäämässä kenttään ei_saldoa arvo 'o', tällöin painoa ei tarvita.</font>";
	
		$query = "	SELECT 		yhtio.nimi AS yritys
								, tuote.tuoteno AS tuotenumero
								, tuote.nimitys AS tuotteennimi
								, tuote.tuotemassa AS paino
					FROM 		tuote 
					JOIN		yhtio
					ON			tuote.yhtio = yhtio.yhtio
					WHERE 		tuote.ei_saldoa = '' 
					AND			tuote.status != 'P'
					AND 		( tuote.tuotemassa <= 0 OR tuote.tuotemassa = '' OR tuote.tuotemassa IS NULL)
					AND			yhtio.yhtio != 'myyra'
					AND		yhtio.noreport != 1
					";
		
		haeTarkistusRaportti ($query, 'yritys', 'Onneksi olkoon, kaikilla tuotteilla, joita ei luokitella palveluiksi, on paino.', 'tuotenumero'
								, 'tuotteennimi', 'paino');
		
		// Tuotteet, joilla on toimittaja ja joiden yhtiona on Myyra, hinta on tyhjä tai 0.00
		print "<font class='message'>Tuotteet, joilla on toimittaja ja joiden yhtiönä on Myyrä, mutta ostohinta puuttuu:</font><br/>
				<font>Käy lisäämässä tuotteille ostohinta. </font>";
		
		$query = "  SELECT 		tuotteen_toimittajat.yhtio AS yhtio
								, yhtio.nimi AS toimittaja
								, tuote.nimitys AS tuotteennimi
								, tuotteen_toimittajat.tuoteno AS tuotenumero
					FROM 		tuotteen_toimittajat 
					JOIN 		yhtio 
					ON 			tuotteen_toimittajat.toimittaja = yhtio.ytunnus 
					JOIN		tuote
					ON			tuotteen_toimittajat.tunnus = tuote.tunnus
					WHERE 		tuotteen_toimittajat.yhtio = 'myyra'  
					AND 		(tuotteen_toimittajat.ostohinta = '' OR tuotteen_toimittajat.ostohinta = '0.00')
					AND		yhtio.noreport != 1
					";
		
		haeTarkistusRaportti ($query, 'yhtio', 'Onneksi olkoon, kaikilla tuotteilla, joilla on toimittaja ja joiden yhtiönä on Myyrä, on hinta.'
								, 'toimittaja' , 'tuotteennimi', 'tuotenumero');
		
require ("../inc/footer.inc");

/**
 * Hakee ja tulostaa tarkistusraportin.
 *
 * @access  public
 * @param   string		$query			mySql kysely
 * @param   string		$sarake			Ensimmäisen sarakkeen nimi. Käytetään myös sql-kyselyssä ensimmäisen rivin AS-nimenä (yleensä 'yritys').
 * $param   string		$oikeaTieto		Teksti, joka tulee näkyviin, jos mySql-kysely ei palauta yhtään riviä (kaikki kyselyn tiedot ovat oikein).
 * @param   string		$valinnainen1	Oletuksena false. Toisen sarakkeen nimi. Käytetään myös sql-kyselyssä toisen rivin AS-nimenä (yleensä 'asiakas')
 * @param   string		$valinnainen2	Oletuksena false. Kolmannen sarakkeen nimi. Käytetään myös sql-kyselyssä kolmannen rivin AS-nimenä.
 * @param   string		$valinnainen3	Oletuksena false. Neljännen sarakkaane nimi. Käytetään myös sql-kyselyssä neljännen rivin AS-nimenä.
 */
function haeTarkistusRaportti ($query, $sarake, $oikeaTieto, $valinnainen1 = false, $valinnainen2 = false, $valinnainen3 = false ) {
	
	$result = mysql_query( $query ) or pupe_error( $query );
	$num_rows = mysql_num_rows( $result );
	
	if ( $num_rows > 0 ) {
		print "	<table>
					<tr>
						<th>$sarake</th>
				";
		if ( $valinnainen1 ) {
			print "<th>$valinnainen1</th>";
		}
		if ( $valinnainen2 ) {
			print "<th>$valinnainen2</th>";
		}
		if ( $valinnainen3 ) {
			print "<th>$valinnainen3</th>";
		}
		print "</tr>";
		
		while ($row = mysql_fetch_assoc($result)) {
			print "	<tr>
						<td>" . $row[ $sarake ] . "</td>";
			if ( $valinnainen1 ) {
				print "<td>" . $row[ $valinnainen1 ] . "</td>";
			}
			if ( $valinnainen2 ) {
				print "<td>" . $row[ $valinnainen2 ] . "</td>";
			}
			if ( $valinnainen3 ) {
				print "<td>" . $row[ $valinnainen3 ] . "</td>";
			}
						
			print "</tr>";
		}
	
		print "</table>";
	} else {
		print "<br/><font>$oikeaTieto</font>";
	}
	
	print "<br/><br/>";
}

?>
