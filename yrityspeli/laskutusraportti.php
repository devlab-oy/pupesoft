<?php
/// laskutusraportti.php
/// TAMK Yrityspeli-valikko, rapotteja, jonka avulla erityspelin yll‰pit‰j‰ voi laskuttaa yrityksilt‰ 
/// Laskutusraportit: 
/// rahtitilasto, jonka avulla laskutetaan kuljetukset (yritykselt‰ Kyyti)
/// puhelutilasto, jonka avulla l‰hetet‰‰n mahdollisia lis‰laskuja puheluista
/// pankin palvelumaksutilasto, jonka avulla l‰hetet‰‰n pankkipalveluista laskuja
///
/// Annika Granlund, Jarmo Kortetj‰rvi
/// 2010-06-15

require_once "../inc/parametrit.inc";

print "	<font class='head'>Laskutusraportti</font><hr><br/>";

if ($_POST[ 'hae' ]) {
	$aloituspvm = $_POST[ 'startdate' ];
	$lopetuspvm = $_POST[ 'enddate' ];
	$raporttityyppi = $_POST[ 'raportti' ];
	$errorText = '';
	
	// tarkistetaan, ett‰ p‰iv‰m‰‰r‰t on annettu
	if ( !$aloituspvm ) {
		$errorText = 'Tarkista alkupvm.';
		$aloituspvm = false;
	}
	if ( !$lopetuspvm ) {
		$errorText = 'Tarkista loppupvm.';
		$lopetuspvm = false;
	}
	
	// jos errorTextiss‰ on jotain, se tulostetaan
	if ( $errorText ) {
		print "<font class='error'>$errorText</font><br/>";
	}
	
	// jos kaikki ok (aloitus ja lopetuspvm:t annettu), tehd‰‰n kyselyt valintojen mukaan
	if ( $aloituspvm != false and $lopetuspvm != false and empty($errorText) ) {
		// aloitusp‰iv‰m‰‰r‰ sql-kysely‰ varten (datetime muodossa)
		$aloitusPvmMySql = date( 'Y-m-d', strtotime($aloituspvm) ) . ' 00:00:00';
		// lopetusp‰iv‰m‰‰r‰ sql-kysely‰ varten (datetime muodossa)
		$lopetusPvmMySql = date( 'Y-m-d', strtotime($lopetuspvm) ) . ' 23:59:59';
		
		// jos kyseess‰ rahtitilasto
		if ( $raporttityyppi == 'Rahtitilasto' ) {
			print "<font class='message'>Rahtitilasto $aloituspvm - $lopetuspvm</font><br/>
					<font>Oppiyritysten myymien tuotteiden yhteispaino (aikav‰lilll‰ $aloituspvm - $lopetuspvm)</font><br/><br/>";
			
			// haetaan yhtiˆn nimi, tuotteiden yhteenlaskettu paino sek‰ tuotteiden yhteism‰‰r‰
			// hakuehdot: laskun tilan tulee olla 'l' (myyntilasku), yhtion yhtiotyypin tulee olla 'OPY' (oppiyritys), 
			// lasku tulee olla laskutettu k‰ytt‰j‰n antamalla aikav‰lill‰, tuotteen ei_saldoa tulee olla tyhj‰ (kyseess‰ ei ole palvelu)
			// groupataan tiedot yhtion nimen perusteella
			/*$query = " 	SELECT 		yhtio.nimi AS yhtio
									, SUM(tuote.tuotemassa) AS tuotepaino
									, count(tuote.nimitys) AS maara
						FROM 		lasku 
						JOIN 		yhtio 
						ON 			lasku.yhtio = yhtio.yhtio 
						JOIN 		tilausrivi 
						ON 			lasku.tunnus = tilausrivi.otunnus 
						JOIN 		tuote 
						ON 			tilausrivi.tuoteno = tuote.tuoteno 
						WHERE 		lasku.tila = 'l' 
						AND 		yhtio.yhtiotyyppi = 'OPY'
						AND			(lasku.laskutettu >= '$aloitusPvmMySql' AND lasku.laskutettu <= '$lopetusPvmMySql')
						AND 		tuote.ei_saldoa = '' 
						GROUP BY 	lasku.yhtio
						";
						
						// tilausten lkm
						// SELECT count(*), yhtio FROM tilausrivi JOIN lasku ON lasku.tunnus = tilausrivi.otunnus WHERE lasku.yhtio = 'Ispee' AND lasku.tila = 'L';

						// eri tilaukset eri riville, jos tilauksessa on eri tuoteryhmien tavaroita, ne eri riveille ja jokaiselle riville oma paino
						//SELECT tilausrivi.otunnus, lasku.laskunro, tilausrivi.kpl * tuote.tuotemassa AS paino, tuote.nimitys, lasku.yhtio, tuote.try 
						// FROM tilausrivi JOIN lasku ON lasku.tunnus = tilausrivi.otunnus JOIN tuote ON tilausrivi.tuoteno = tuote.tuoteno 
						// WHERE lasku.yhtio = 'Ispee' AND lasku.tila = 'L' AND tuote.yhtio = 'Ispee';
						
						//  SELECT lasku.laskunro, tilausrivi.kpl * tuote.tuotemassa AS paino, tuote.nimitys, lasku.yhtio, tuote.try FROM tilausrivi JOIN lasku ON lasku.tunnus = tilausrivi.otunnus JOIN tuote ON tilausrivi.tuoteno = tuote.tuoteno JOIN yhtio ON lasku.tunnus = tilausrivi.otunnus WHERE lasku.tila = 'L' AND yhtio.yhtiotyyppi = 'OPY' GROUP BY lasku.yhtio, lasku.laskunro, tuote.try;';

						print $query;
			$result = mysql_query( $query ) or pupe_error( $query );
			
			print "	<table>
						<tr>
							<th>Yritys</th>
							<th>Tuotteiden m‰‰r‰ (kpl)</th>
							<th>Tuotteiden paino yhteens‰ (kg)</th>
						</tr>";
			
			// tulostellaan kyselyn tulokset
			while ( $row = mysql_fetch_assoc($result) ) {
				$yhtionNimi = $row[ 'yhtio' ];
				$tuotteidenMaara = $row[ 'maara' ];
				$tuotteidenPaino = $row[ 'tuotepaino' ];
				
				print "	<tr>
							<td>$yhtionNimi</td>
							<td>$tuotteidenMaara</td>
							<td>". number_format($tuotteidenPaino, 3) ."</td>
						</tr>";
			}
			
			print "</table>";*/
			
			// haetaan opyt kannasta
			$query = "	SELECT 	yhtio 
						FROM 	yhtio 
						WHERE 	yhtio.yhtiotyyppi  = 'OPY' ";
						
			$result = mysql_query( $query ) or pupe_error( $query );
			
			if ( $result and (mysql_num_rows($result) > 0) ) {
				print "	<table>
						<tr>
							<th>Yritys</th>
							<th>Tuote</th>
							<th>Tuotteiden paino yhteens‰ (kg)</th>
							<th>Tuoteryhm‰</th>
						</tr>";
				while ($row = mysql_fetch_assoc($result)) {
					$nykyinenYhtio = $row[ 'yhtio' ];
					
					$query2 = "	SELECT 		lasku.yhtio AS yhtio
											, lasku.tunnus
											, tilausrivi.tuoteno
											, tilausrivi.kpl
											, tuote.nimitys AS nimi
											, tuote.tuotemassa
											, tilausrivi.kpl * tuote.tuotemassa AS yhtPaino
											, tuote.try AS try
								FROM 		lasku 
								JOIN 		tilausrivi 
								ON 			lasku.tunnus = tilausrivi.otunnus 
								JOIN 		tuote 
								ON 			tilausrivi.tuoteno = tuote.tuoteno 
								WHERE 		lasku.tila = 'L' 
								AND 		lasku.yhtio = '$nykyinenYhtio' 
								AND 		tuote.yhtio = '$nykyinenYhtio' 
								AND			lasku.laskutettu >= '$aloitusPvmMySql' AND lasku.laskutettu <= '$lopetusPvmMySql'
								AND 		tuote.ei_saldoa = '' ";
								
					//print $query2 . "<br/>";
					$result2 = mysql_query( $query2 ) or pupe_error( $query2 );
					
					if ( $result2 and (mysql_num_rows($result2) > 0) ) {
					
						while ($row2 = mysql_fetch_assoc($result2)) {
							$yhtio = $row2[ 'yhtio' ];
							$tuotteenNimi = $row2[ 'nimi' ];
							$yhtPaino = $row2[ 'yhtPaino' ];
							$tuoteryhma = $row2[ 'try' ];
							
							print "	<tr>
									<td>$yhtio</td>
									<td>$tuotteenNimi</td>
									<td>". number_format($yhtPaino, 3) ."</td>
									<td>$tuoteryhma</td>
								</tr>";
						}
					}
				}
				print "</table>";
			}
		}
		// jos kyseess‰ puhelutilasto
		else if ( $raporttityyppi == 'Puhelutilasto' ) {
			print "<font class='message'>Puhelutilasto $aloituspvm - $lopetuspvm</font><br/>
					<font>Tilausrivien m‰‰r‰n perusteella yrityksille voi l‰hetell‰ lis‰laskua puheluista.</font><br/><br/>";
			
			// haetaan yhtion nimi, tilausrivien yhteenlaskettu m‰‰r‰
			// hakuehdot: laskun tilan tulee olla 'l' (myyntitilaus), yhtion yhtiotyypin tulee olla 'OPY' (oppiyritys)
			// lasku tulee olla laskutettu k‰ytt‰j‰n antamalla aikav‰lill‰
			// groupataan tiedot yhtion nimen perusteella
			$query = "	SELECT 		yhtio.nimi AS yhtio
									, count(tilausrivi.otunnus) kpl 
						FROM 		lasku 
						JOIN 		yhtio 
						ON 			lasku.yhtio = yhtio.yhtio 
						JOIN 		tilausrivi 
						ON 			lasku.tunnus = tilausrivi.otunnus 
						WHERE 		lasku.tila = 'l' 
						AND			yhtio.yhtiotyyppi = 'OPY'
						AND			(lasku.laskutettu >= '$aloitusPvmMySql' AND lasku.laskutettu <= '$lopetusPvmMySql')
						GROUP BY 	lasku.yhtio
						";

			$result = mysql_query( $query ) or pupe_error( $query );
			
			print "	<table>
						<tr>
							<th>Yritys</th>
							<th>Tilausrivien m‰‰r‰</th>
						</tr>";
			
			// tulostellaan kyselyn tulokset
			while ( $row = mysql_fetch_assoc($result) ) {
				$yhtio = $row[ 'yhtio' ];
				$kpl = $row[ 'kpl' ];
				print "	<tr>
							<td>$yhtio</td>
							<td>$kpl</td>
						</tr>";
			}
			
			print "</table>";
		}
		// jos kyseess‰ pankin palvelumaksutilasto
		else if ( $raporttityyppi == 'Pankin palvelumaksutilasto' ) {
			print "<font class='message'>Pankin palvelumaksutilasto $aloituspvm - $lopetuspvm</font><br/>
					<font>Raportti n‰ytt‰‰ pankkitapahtumat aikav‰lill‰ $aloituspvm - $lopetuspvm ja laskee
					laskutettavan summan kentt‰‰n 'Laskutetaan (&euro;)'.<br/>
					Tapahtuma maksaa 0,15 &euro;/kpl.</font><br/><br/>";
			
			// haetaan yhtion nimi, pankkitapahtumien yhteenlaskettu rahallinen summa, pankkitapahtumien lukum‰‰r‰
			// hakuehdot: yhtiˆn yhtiotyypin tulee olla 'OPY' (oppiyritys), 
			// eiVaikutaSaldoon-kent‰ss‰ ei saa olla v, e, p eik‰ s (suljetaan vero yms. ilmoitukset haun ulkopuolelle)
			// tapahtuman er‰p‰iv‰n (tapvm) tulee olla k‰ytt‰j‰n antamalla aikav‰lill‰
			// groupataan tiedot yhtion nimen perusteella
			$query = "SELECT 		yhtio.nimi AS nimi
									, sum(TAMK_pankkitapahtuma.summa) AS summa
									, count(TAMK_pankkitapahtuma.summa) AS kpl 
						FROM 		yhtio 
						JOIN 		TAMK_pankkitili 
						ON 			yhtio.ytunnus = TAMK_pankkitili.ytunnus 
						JOIN 		TAMK_pankkitapahtuma 
						ON 			TAMK_pankkitili.tilinro = TAMK_pankkitapahtuma.maksaja 
						WHERE 		yhtio.yhtiotyyppi = 'OPY' 
						AND 		( eiVaikutaSaldoon != 'v' 
										AND eiVaikutaSaldoon != 'e'
										AND eiVaikutaSaldoon != 'p'
										AND eiVaikutaSaldoon != 's'
									) 
						AND 		( TAMK_pankkitapahtuma.tapvm > '$aloitusPvmMySql' AND TAMK_pankkitapahtuma.tapvm < '$lopetusPvmMySql')
						GROUP BY 	yhtio.nimi
						";
			
			$result = mysql_query( $query ) or pupe_error( $query );
			
			print "	<table>
						<tr>
							<th>Yritys</th>
							<th>Pankkitapahtumien summa (&euro;)</th>
							<th>Pankkitapahtumia yhteens‰ (kpl)</th>
							<th>Laskutetaan (&euro;)</th>
						</tr>";
			
			// tulostellaan kysekyn tulokset
			while ($row = mysql_fetch_assoc($result)) {
				$yhtionNimi = $row[ 'nimi' ];
				$tapSumma = $row[ 'summa' ];
				$tapKplMaara = $row[ 'kpl' ];	
				$laskutus = $tapKplMaara * 0.15;
				
				print "	<tr>
							<td>$yhtionNimi</td>
							<td>$tapSumma</td>
							<td>$tapKplMaara</td>
							<td>" . number_format($laskutus, 2) ."</td>
						</tr>";
			}
			
			print "</table>";
		}
		
		print "	<form action='' method='post'>
					<p><input type='submit' name='back' value='Takaisin hakuun'/></p>
				</form>";
	} else {
		getSearchForm( $aloituspvm, $lopetuspvm, $raporttityyppi );
	}
} else {
	// lomake, jolla k‰ytt‰j‰ valitsee aikav‰lin sek‰ raporttityypin
	getSearchForm();
}
require ("../inc/footer.inc");

/**
 * Piirt‰‰ tilanneraportin lomakkeen, joka sis‰lt‰‰ aikav‰lihakukent‰t (aloitus ja lopetuspvm), raportinvalinnan ja hae-napin.
 *
 * @access   public
 * @param    mixed   $startDate   oletuksena false, haun aloitusp‰iv‰m‰‰r‰
 * @param    mixed   $endDate     oletuksena false, haun lopetusp‰iv‰m‰‰r‰
 * @param    mixed	 $raportti    oletuksena false, raporttityyppi
 */
function getSearchForm( $startDate = false, $endDate = false, $raportti = false ) {
	print	"<font class='message'>Valitse aikav‰lit sek‰ raporttityyppi ja paina hae.</font><br/>
		
		<style>";
			// kalenterin tyylitiedosto
			require_once '../../lib/calendar/calendar.css';
			
	// haetaan eri yritykset kannasta
	$query = "	SELECT	yhtio
						, nimi
				FROM	yhtio
				";
	
	$result = mysql_query( $query ) or pupe_error( $query );
	$raportit = array( 'Rahtitilasto', 'Puhelutilasto', 'Pankin palvelumaksutilasto' );
			
print 	"</style>
		<form action='' method='post'>
			<p>Syˆt‰ tiedot muodossa pp.kk.vvvv</p>
			<table>
			<tr>
				<td>Alkupvm</td>
				<td>
					<input type='text' name='startdate' id='startdate' value='$startDate' />
					<script type='text/javascript' src='../../lib/calendar/calendar.js' ></script>
					<script type='text/javascript' >
						calendar.set('startdate');
					</script>
				</td>
			</tr>
			<tr>
				<td>Loppupvm</td>
				<td>
					<input type='text' name='enddate' id='enddate' value='$endDate' />
					<script type='text/javascript' scr='../../lib/calendar/calendar.js' ></script>
					<script type='text/javascript' >
						calendar.set('enddate');
					</script>
				</td>
			</tr>
			<tr>
				<td>Raporttityyppi</td>
				<td>
					<select name='raportti'>";
					foreach ( $raportit as $nimi ) {
						if ( $raportti === $nimi ) {
							print "<option selected='selected'>$nimi</option>";
						} else {
							print "<option>$nimi</option>";
						}
					}
print 				"</select>
				</td>
			</tr>
			</table>
			<p>
			<input type='submit' name='hae' value='Hae' />
			</p>
		
		</form>";
}

?>