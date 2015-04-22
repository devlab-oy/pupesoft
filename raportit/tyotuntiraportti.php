<?php
/// tyotuntiraportti.php
/// TAMK Opettajat-valikko, raportti tehdyistä työtunneista
/// 
/// Annika Granlund, Jarmo Kortetjärvi
/// 2010-07-07

require_once "../inc/parametrit.inc";

print "<font class='head'>Työtuntiraportti</font><hr/>
		<font>Raportti opiskelijoiden kirjaamista työtunneista.<br/>
		Nimeä klikkaamalla pääset tarkastelemaan tietyn oppilaan kirjaamia tunteja. <br/>
		Keskimääräiset tuntimäärät on laskettu vain niiden henkilöiden perusteella, jotka ovat tehneet tunteja (ts. nollatunnit eivät vaikuta keskiarvoon).<br/>
		Mikäli haluat vaihtaa yritystä, valitse valikosta Opettajat -> Valitse yritys</font><br/><br/>";

// painettu hae nappia (päivämäärät valittu)
if ( $_POST[ 'hae' ] ) {
	$aloitusPvm = $_POST[ 'startdate' ];
	// aloituspäivämäärä sql-kyselyä varten (date)
	$aloitusPvmMySql = date( 'Y-m-d', strtotime($aloitusPvm) );
	$lopetusPvm = $_POST[ 'enddate' ];
	// lopetuspäivämäärä sql-kyselyä varten (date muodossa)
	$lopetusPvmMySql = date( 'Y-m-d', strtotime($lopetusPvm) );
	$valittu = $yhtiorow[ 'yhtio' ];

	// jos alkupvm ei ole annettu
	if( $aloitusPvm == false ){
		$errorText = "Tarkista alkupäivämäärä.";
	}
	// jos loppupvm ei ole annettu
	if( $lopetusPvm == false ){
		$errorText = "Tarkista loppupäivämäärä.";
	}
	
	$nyt = date('d.m.Y');
	// jos alkupvm on tulevaisuudessa
	if( strtotime($aloitusPvm) > strtotime($nyt) ) {
		$errorText = "Tarkista alkupäivämäärä. (Et voi hakea tapahtumia tulevaisuudesta.)";
		$aloitusPvm = false;
	}
	// jos loppupvm on tulevaisuudessa
	if ( strtotime($lopetusPvm) > strtotime($nyt) ) {
		$errorText = "Tarkista loppupäivämäärä. (Et voi hakea tapahtumia tulevaisuudesta.)";
		$lopetusPvm = false;
	}
	
	// jos loppupvm on aiemmin kuin aloituspvm
	if ( strtotime($lopetusPvm) < strtotime($aloitusPvm)) {
		$errorText = "Tarkista päivämäärät.";
		$lopetusPvm = false;
		$aloitusPvm = false;
	}
	
	// pilkotaan päivämäärät osiin (päivä, kk, vuosi) checkdatea varten
	$aloitusPvmArray = explode('.', $aloitusPvm);
	$lopetusPvmArray = explode('.', $lopetusPvm);
	// tarkistetaan, että aloituspäivämäärä on oikeasti olemassa
	if ( checkdate( $aloitusPvmArray[1], $aloitusPvmArray[0], $aloitusPvmArray[2] ) == false ) {
		$errorText = "Tarkista alkupäivämäärä.";
		$aloitusPvmArray = false;
	}
	// tarkistetaan, että lopetuspäivämäärä on oikeasti olemassa
	if ( checkdate( $lopetusPvmArray[1], $lopetusPvmArray[0], $lopetusPvmArray[2] ) == false ) {
		$errorText = "Tarkista alkupäivämäärä.";
		$lopetusPvmArray = false;
	}
	
	// jos errorTextissä on tietoa, tulostetaan se
	if ($errorText) {
		print "<font class='error'>$errorText</font><br/>";
		getSearchForm( $aloitusPvm, $lopetusPvm );
	}
	
	// jos alku- ja loppupvm:t on annettu ja errorText on tyhjä, tehdään raportti
	if( $aloitusPvm != false and $lopetusPvm != false and empty($errorText) ) {	
		// haetaan työntakijöiden tekemät työtunnit halutulla aikavälillä
		$query = "	SELECT		TAMK_tyoaika.kuka
								, SUM(TAMK_tyoaika.tunnit) AS tunnit
								, TAMK_tyoaika.selite
								, TAMK_tyoaika.pvm
					FROM		TAMK_tyoaika
					JOIN		kuka
					ON			TAMK_tyoaika.kuka = kuka.kuka
					WHERE		TAMK_tyoaika.yhtio = '$valittu'
					AND			(TAMK_tyoaika.pvm >= '$aloitusPvmMySql' AND TAMK_tyoaika.pvm <= '$lopetusPvmMySql')
					AND			kuka.profiilit = 'opiskelija'
					GROUP BY	TAMK_tyoaika.kuka, TAMK_tyoaika.pvm
					ORDER BY	TAMK_tyoaika.kuka ASC, TAMK_tyoaika.pvm ASC
					";
		
		$result = mysql_query( $query ) or pupe_error( $query );
		
		if ( $result ) {
			// luodaan taulu tuloksille
			$tunnit = array();
			// tunnit/päivä yhteensä
			$paivanTunnit = array();
			// tuntien yhteismäärien yhteismäärä
			$kaikkienTunnitYht = 0;
			
			while ( $row = mysql_fetch_assoc( $result ) ) {
				// tulokset muuttujiin
				$kuka = $row[ 'kuka' ];
				$tehdytTunnit = $row[ 'tunnit' ];
				$selite = $row[ 'selite' ];
				$pvm = $row[ 'pvm' ];
				
				// jos avain $kuka löytyy jo $tunnit-taulusta, lisätään arvot (key = pvm, value = tunnit)
				if ( array_key_exists($kuka, $tunnit) ) {
					$tunnit[$kuka][$pvm] = $tehdytTunnit;
					
				} else {
					$tunnit[$kuka] = array();
					$tunnit[$kuka] = array($pvm => $tehdytTunnit);
				}
				
				// otetaan päivät ja niiden tuntimäärät talteen
				// jos pvm on jo taulun avaimena, lisätään vain arvo
				if ( array_key_exists( $pvm, $paivanTunnit ) ) {
					$paivanTunnit[$pvm][] = $tehdytTunnit;
				} else {
					$paivanTunnit[$pvm] = array();
					$paivanTunnit[$pvm] = array(1 => $tehdytTunnit);
				}
			}
			
			// sorting array
			ksort($paivanTunnit);
		}

		// javascript tapahtumien sorttausta varten
		print "	<script type='text/javascript' src='/lib/standardista_table_sorting/common.js'></script>
				<script type='text/javascript' src='/lib/standardista_table_sorting/css.js'></script>
				<script type='text/javascript' src='/lib/standardista_table_sorting/standardista-table-sorting.js'></script>";
		// printataan tiedot taulukkoon (tableen)
		// tablen thead, tfoot, tbody on sorttausta varten!
		// tfoot (se missä keskiarvot eikä haluta, että se sorttautuu) pitää olla theadin ja tbodyn välissä, muuten ei toimi!!!
		print "	<table class='sortable'>
					<thead>
					<tr>
						<th>Nimi</th>";
		// tulostetaan päivämäärät taulukon headeriin
		foreach ($paivanTunnit as $key => $value) {
			$formatValue = date('d.m.Y', strtotime($key));
			print 		"<th>$formatValue</th>";
		}
		print 			"<th>Tunnit yht</th>
					</tr>
					</thead>
					<tfoot>";
		// tulostetaan viimeiselle riville keskimääräinen työaina/päivä
		// viimeinen rivi tulee olla tässä sorttauksen vuoksi!!!!! ÄLÄ SIIRRÄ!
		print "	<tr style='color: #433152;'>
					<td>Tunnit kesk/pvä</td>";
					foreach( $paivanTunnit as $paiva => $tuntimaara) {
						print "<td>" . number_format(array_sum($tuntimaara) / sizeof($tuntimaara), 2) . "</td>";
						// lasketaan $kaikkienTunnitYht samalla
						$kaikkienTunnitYht = $kaikkienTunnitYht + array_sum($tuntimaara);
					}
		// lasketaan ja printataan $kaikkienTunnitYht jaettuna niiden henkilöiden summalla, joilla on tunteja kirjattuna ($tunnit-taulun koko)
		print		"<td>" . number_format($kaikkienTunnitYht / sizeof($tunnit), 2) . "</td>
					</tr>
					</tfoot>
					<tbody>";
			
		// tapetaan muuttujat varmuuden vuoksi 
		unset($key);
		unset($value);
		
		// haetaan valitun yhtion opiskelija-profiililla olevat työntekijät (nimi, lyhenne)
		$query1 = "	SELECT		kuka
								, nimi
					FROM		kuka
					WHERE		yhtio = '$valittu'
					AND			profiilit = 'opiskelija'
					ORDER BY	nimi ASC
					";
					
		$result1 = mysql_query( $query1 ) or pupe_error( $query1 );
		
		// printataan nimet taulukon ensimmäiseen sarakkeeseen
		while ( $row1 = mysql_fetch_assoc( $result1 ) ) {
			// tiedot muuttujiin
			$kuka = $row1[ 'kuka' ];
			$nimi = $row1[ 'nimi' ];
			$tunnitYht = 0;
			
			print "	<tr>
						<td>
							<form action='' method='post'>
							<input type='submit' name='haeTarkatTiedot' value='$nimi' style='border:none; border-bottom: 1px solid #111; background: none;' />
							<input type='hidden' name='kuka' value='$kuka' />
							<input type='hidden' name='nimi' value='$nimi' />
							<input type='hidden' name='aloitusPvm' value='$aloitusPvm' />
							<input type='hidden' name='lopetusPvm' value='$lopetusPvm' />
							</form>
						</td>";
		
			// printataan tunnit henkilöille
			foreach ($tunnit as $tunnitKuka => $tunnitTaulu) {
				if ( $tunnitKuka == $kuka ) {
					foreach ($paivanTunnit as $paivatKey => $paivatValue) {
						if ( array_key_exists($paivatKey, $tunnitTaulu) ) {
							print "<td>" . $tunnitTaulu[$paivatKey] . "</td>";
							// lasketaan samalla tuntien yhteismäärä
							$tunnitYht = array_sum($tunnitTaulu);
						} else {
							print "<td>0</td>";
						}
					}
					// viimeinen sarake tunnit yht
					print "<td>" . number_format($tunnitYht, 2) . "</td>";
				} 
			}
			// jos henkilöllä ei ole lainkaan tunteja ($kuka ei löydy $tunnit-taulusta)
			if ( !array_key_exists($kuka, $tunnit) ) {
				// tulostetaan nollia niin  monta, kuin on sarakkeiden määrä
				// selviää laskemalla $paivanTunnit-taulun koko + 1 ( +1, jotta myös tunnit yht -sarakkeeseen tulee 0)
				for ( $i = 1; $i <= sizeof($paivanTunnit)+1; $i++) {
					print "<td>0</td>";
				}
			}
			print "</tr>";
		}
		
		print "</tbody>
				</table>";
	}
	print "	<form action='' method='post'>
				<p><input type='submit' name='back' value='Takaisin hakuun' /></p>
			</form>";
} 
// painettu henkilön nimi-nappia
else if ( $_POST[ 'haeTarkatTiedot' ] ) {
	$kuka = $_POST[ 'kuka' ];
	$nimi = $_POST[ 'nimi' ];
	$aloitusPvm = $_POST[ 'aloitusPvm' ];
	$lopetusPvm = $_POST[ 'lopetusPvm' ];
	// aloituspäivämäärä sql-kyselyä varten (date)
	$aloitusPvmMySql = date( 'Y-m-d', strtotime($aloitusPvm) );
	// lopetuspäivämäärä sql-kyselyä varten (date muodossa)
	$lopetusPvmMySql = date( 'Y-m-d', strtotime($lopetusPvm) );
	$valittu = $yhtiorow[ 'yhtio' ];
	
	$query = "	SELECT 		pvm
							, tunnit
							, selite
				FROM		TAMK_tyoaika 
				WHERE		yhtio = '$valittu'
				AND			kuka = '$kuka'
				AND			(pvm >= '$aloitusPvmMySql' AND pvm <= '$lopetusPvmMySql') 
				ORDER BY	pvm ASC
				";
	
	$result = mysql_query( $query ) or pupe_error( $query );
	$numRows = mysql_num_rows( $result );
	
	print "<font class='message'>$nimi - kirjatut tunnit aikavälillä $aloitusPvm - $lopetusPvm</font>";
	
	// jos kyselystä tulee 1 tai enemmän rivejä (tunteja on jo kirjattu aikaisemmin)
	if ( $numRows >= 1 ) {
		$tunnitYht = 0;
		print "	<table>
				<tr>
					<th>Päivämäärä</th>
					<th>Kirjatut työtunnit</th>
					<th>Selite</th>
				</tr>";
		while( $row = mysql_fetch_assoc( $result ) ) {
			$pvm = date('d.m.Y', strtotime($row[ 'pvm' ]));
			$tunnit = $row[ 'tunnit' ];
			$selite = $row[ 'selite' ];
			$tunnitYht += $tunnit;
			// seuraavat muuttujat ovat sitä varten, että saadaan tunnit ja minuutit erilleen
			$tunnitTaulu = explode('.', $tunnit);
			$tunnitTaulusta = $tunnitTaulu[0];
			$minuutitTaulusta = $tunnitTaulu[1];
			// lasketaan minuuttien todellinen määrä, pyöristetään lähimpään kokonaislukuun
			$minuutitMuotoiltu = round($minuutitTaulusta * 60 / 100);
			
			print "	<tr>
						<td>$pvm</td>
						<td>$tunnitTaulusta h $minuutitMuotoiltu min</td>
						<td>$selite</td>
					</tr>";
		}
		// muotoillaan niin, että on aina kaksi desimaalia
		$tunnitYhtMuotoiltu = number_format($tunnitYht, 2);
		// otetaan minuutit talteen
		$minuutitYhtMuotoiltu = substr($tunnitYhtMuotoiltu, -2);
		// räjäytetään pisteen kohdalta, jotta saadaan tunnit talteen
		$tunnitYhtTaulu = explode('.', $tunnitYht);
		$tunnitYhtTaulusta = $tunnitYhtTaulu[0];
		// lasketaan minuuttien todellinen määrä
		$minuutitYht = ( $minuutitYhtMuotoiltu * 60 / 100);
		// pyöristetään lähimpään kokonaislukuun
		$minuutitYht = round($minuutitYht);
		print "		<tr>
						<td>Tunnit Yhteensä</td>
						<td>$tunnitYhtTaulusta h $minuutitYht min</td>
						<td></td>
					</tr>
				</table>";
		
	} 
	// jos kyselystä ei tule yhtään riviä
	else {
		print "<br/><font>Ei kirjattuja työtunteja.</font>";
	}
	
	// takaisin hakutuloksiin
	print "	<form action='' method='post'>
				<p><input type='submit' name='hae' value='Takaisin hakutuloksiin' />
				<input type='hidden' name='startdate' value='$aloitusPvm' />
				<input type='hidden' name='enddate' value='$lopetusPvm' /></p>
			</form>";
	// takaisin hakukalenteriin
	print "	<form action='' method='post'>
				<p><input type='submit' name='back' value='Takaisin hakuun' /></p>
			</form>";
	
} else {
	getSearchForm();
}
require ("../inc/footer.inc");

/**
 * Piirtää tilanneraportin aikavälihakukentät (aloitus ja lopetuspvm)
 *
 * @access   public
 * @param    mixed   $startDate   oletuksena false, haun aloituspäivämäärä
 * @param    mixed   $endDate     oletuksena false, haun lopetuspäivämäärä
 */
function getSearchForm( $startDate = false, $endDate = false ) {
	print	"<font class='message'>Valitse aikavälit ja paina hae.</font><br/>
		
		<style>";
			// kalenterin tyylitiedosto
			require_once '../../lib/calendar/calendar.css';
			
print 	"</style>
		<form action='' method='post'>
			<p>Syötä tiedot muodossa pp.kk.vvvv</p>
			<table>
			<tr>
			<td>alkupvm</td>
			<td>
			<input type='text' name='startdate' id='startdate' value='$startDate' />
			<script type='text/javascript' src='../../lib/calendar/calendar.js' ></script>
			<script type='text/javascript' >
				calendar.set('startdate');
			</script>
			</td>
			</tr>
			<tr>
			<td>loppupvm</td>
			<td>
			<input type='text' name='enddate' id='enddate' value='$endDate' />
			<!--<script type='text/javascript' src='../../lib/calendar/calendar.js' ></script>-->
			<script type='text/javascript' >
				calendar.set('enddate');
			</script>
			</td>
			</tr>
			</table>
			<p>
			<input type='submit' name='hae' value='Hae' />
			</p>
		
		</form>";
}
?>