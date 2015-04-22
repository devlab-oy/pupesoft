<?php
/// tyotuntiraportti.php
/// TAMK Opettajat-valikko, raportti tehdyist� ty�tunneista
/// 
/// Annika Granlund, Jarmo Kortetj�rvi
/// 2010-07-07

require_once "../inc/parametrit.inc";

print "<font class='head'>Ty�tuntiraportti</font><hr/>
		<font>Raportti opiskelijoiden kirjaamista ty�tunneista.<br/>
		Nime� klikkaamalla p��set tarkastelemaan tietyn oppilaan kirjaamia tunteja. <br/>
		Keskim��r�iset tuntim��r�t on laskettu vain niiden henkil�iden perusteella, jotka ovat tehneet tunteja (ts. nollatunnit eiv�t vaikuta keskiarvoon).<br/>
		Mik�li haluat vaihtaa yrityst�, valitse valikosta Opettajat -> Valitse yritys</font><br/><br/>";

// painettu hae nappia (p�iv�m��r�t valittu)
if ( $_POST[ 'hae' ] ) {
	$aloitusPvm = $_POST[ 'startdate' ];
	// aloitusp�iv�m��r� sql-kysely� varten (date)
	$aloitusPvmMySql = date( 'Y-m-d', strtotime($aloitusPvm) );
	$lopetusPvm = $_POST[ 'enddate' ];
	// lopetusp�iv�m��r� sql-kysely� varten (date muodossa)
	$lopetusPvmMySql = date( 'Y-m-d', strtotime($lopetusPvm) );
	$valittu = $yhtiorow[ 'yhtio' ];

	// jos alkupvm ei ole annettu
	if( $aloitusPvm == false ){
		$errorText = "Tarkista alkup�iv�m��r�.";
	}
	// jos loppupvm ei ole annettu
	if( $lopetusPvm == false ){
		$errorText = "Tarkista loppup�iv�m��r�.";
	}
	
	$nyt = date('d.m.Y');
	// jos alkupvm on tulevaisuudessa
	if( strtotime($aloitusPvm) > strtotime($nyt) ) {
		$errorText = "Tarkista alkup�iv�m��r�. (Et voi hakea tapahtumia tulevaisuudesta.)";
		$aloitusPvm = false;
	}
	// jos loppupvm on tulevaisuudessa
	if ( strtotime($lopetusPvm) > strtotime($nyt) ) {
		$errorText = "Tarkista loppup�iv�m��r�. (Et voi hakea tapahtumia tulevaisuudesta.)";
		$lopetusPvm = false;
	}
	
	// jos loppupvm on aiemmin kuin aloituspvm
	if ( strtotime($lopetusPvm) < strtotime($aloitusPvm)) {
		$errorText = "Tarkista p�iv�m��r�t.";
		$lopetusPvm = false;
		$aloitusPvm = false;
	}
	
	// pilkotaan p�iv�m��r�t osiin (p�iv�, kk, vuosi) checkdatea varten
	$aloitusPvmArray = explode('.', $aloitusPvm);
	$lopetusPvmArray = explode('.', $lopetusPvm);
	// tarkistetaan, ett� aloitusp�iv�m��r� on oikeasti olemassa
	if ( checkdate( $aloitusPvmArray[1], $aloitusPvmArray[0], $aloitusPvmArray[2] ) == false ) {
		$errorText = "Tarkista alkup�iv�m��r�.";
		$aloitusPvmArray = false;
	}
	// tarkistetaan, ett� lopetusp�iv�m��r� on oikeasti olemassa
	if ( checkdate( $lopetusPvmArray[1], $lopetusPvmArray[0], $lopetusPvmArray[2] ) == false ) {
		$errorText = "Tarkista alkup�iv�m��r�.";
		$lopetusPvmArray = false;
	}
	
	// jos errorTextiss� on tietoa, tulostetaan se
	if ($errorText) {
		print "<font class='error'>$errorText</font><br/>";
		getSearchForm( $aloitusPvm, $lopetusPvm );
	}
	
	// jos alku- ja loppupvm:t on annettu ja errorText on tyhj�, tehd��n raportti
	if( $aloitusPvm != false and $lopetusPvm != false and empty($errorText) ) {	
		// haetaan ty�ntakij�iden tekem�t ty�tunnit halutulla aikav�lill�
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
			// tunnit/p�iv� yhteens�
			$paivanTunnit = array();
			// tuntien yhteism��rien yhteism��r�
			$kaikkienTunnitYht = 0;
			
			while ( $row = mysql_fetch_assoc( $result ) ) {
				// tulokset muuttujiin
				$kuka = $row[ 'kuka' ];
				$tehdytTunnit = $row[ 'tunnit' ];
				$selite = $row[ 'selite' ];
				$pvm = $row[ 'pvm' ];
				
				// jos avain $kuka l�ytyy jo $tunnit-taulusta, lis�t��n arvot (key = pvm, value = tunnit)
				if ( array_key_exists($kuka, $tunnit) ) {
					$tunnit[$kuka][$pvm] = $tehdytTunnit;
					
				} else {
					$tunnit[$kuka] = array();
					$tunnit[$kuka] = array($pvm => $tehdytTunnit);
				}
				
				// otetaan p�iv�t ja niiden tuntim��r�t talteen
				// jos pvm on jo taulun avaimena, lis�t��n vain arvo
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
		// tfoot (se miss� keskiarvot eik� haluta, ett� se sorttautuu) pit�� olla theadin ja tbodyn v�liss�, muuten ei toimi!!!
		print "	<table class='sortable'>
					<thead>
					<tr>
						<th>Nimi</th>";
		// tulostetaan p�iv�m��r�t taulukon headeriin
		foreach ($paivanTunnit as $key => $value) {
			$formatValue = date('d.m.Y', strtotime($key));
			print 		"<th>$formatValue</th>";
		}
		print 			"<th>Tunnit yht</th>
					</tr>
					</thead>
					<tfoot>";
		// tulostetaan viimeiselle riville keskim��r�inen ty�aina/p�iv�
		// viimeinen rivi tulee olla t�ss� sorttauksen vuoksi!!!!! �L� SIIRR�!
		print "	<tr style='color: #433152;'>
					<td>Tunnit kesk/pv�</td>";
					foreach( $paivanTunnit as $paiva => $tuntimaara) {
						print "<td>" . number_format(array_sum($tuntimaara) / sizeof($tuntimaara), 2) . "</td>";
						// lasketaan $kaikkienTunnitYht samalla
						$kaikkienTunnitYht = $kaikkienTunnitYht + array_sum($tuntimaara);
					}
		// lasketaan ja printataan $kaikkienTunnitYht jaettuna niiden henkil�iden summalla, joilla on tunteja kirjattuna ($tunnit-taulun koko)
		print		"<td>" . number_format($kaikkienTunnitYht / sizeof($tunnit), 2) . "</td>
					</tr>
					</tfoot>
					<tbody>";
			
		// tapetaan muuttujat varmuuden vuoksi 
		unset($key);
		unset($value);
		
		// haetaan valitun yhtion opiskelija-profiililla olevat ty�ntekij�t (nimi, lyhenne)
		$query1 = "	SELECT		kuka
								, nimi
					FROM		kuka
					WHERE		yhtio = '$valittu'
					AND			profiilit = 'opiskelija'
					ORDER BY	nimi ASC
					";
					
		$result1 = mysql_query( $query1 ) or pupe_error( $query1 );
		
		// printataan nimet taulukon ensimm�iseen sarakkeeseen
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
		
			// printataan tunnit henkil�ille
			foreach ($tunnit as $tunnitKuka => $tunnitTaulu) {
				if ( $tunnitKuka == $kuka ) {
					foreach ($paivanTunnit as $paivatKey => $paivatValue) {
						if ( array_key_exists($paivatKey, $tunnitTaulu) ) {
							print "<td>" . $tunnitTaulu[$paivatKey] . "</td>";
							// lasketaan samalla tuntien yhteism��r�
							$tunnitYht = array_sum($tunnitTaulu);
						} else {
							print "<td>0</td>";
						}
					}
					// viimeinen sarake tunnit yht
					print "<td>" . number_format($tunnitYht, 2) . "</td>";
				} 
			}
			// jos henkil�ll� ei ole lainkaan tunteja ($kuka ei l�ydy $tunnit-taulusta)
			if ( !array_key_exists($kuka, $tunnit) ) {
				// tulostetaan nollia niin  monta, kuin on sarakkeiden m��r�
				// selvi�� laskemalla $paivanTunnit-taulun koko + 1 ( +1, jotta my�s tunnit yht -sarakkeeseen tulee 0)
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
// painettu henkil�n nimi-nappia
else if ( $_POST[ 'haeTarkatTiedot' ] ) {
	$kuka = $_POST[ 'kuka' ];
	$nimi = $_POST[ 'nimi' ];
	$aloitusPvm = $_POST[ 'aloitusPvm' ];
	$lopetusPvm = $_POST[ 'lopetusPvm' ];
	// aloitusp�iv�m��r� sql-kysely� varten (date)
	$aloitusPvmMySql = date( 'Y-m-d', strtotime($aloitusPvm) );
	// lopetusp�iv�m��r� sql-kysely� varten (date muodossa)
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
	
	print "<font class='message'>$nimi - kirjatut tunnit aikav�lill� $aloitusPvm - $lopetusPvm</font>";
	
	// jos kyselyst� tulee 1 tai enemm�n rivej� (tunteja on jo kirjattu aikaisemmin)
	if ( $numRows >= 1 ) {
		$tunnitYht = 0;
		print "	<table>
				<tr>
					<th>P�iv�m��r�</th>
					<th>Kirjatut ty�tunnit</th>
					<th>Selite</th>
				</tr>";
		while( $row = mysql_fetch_assoc( $result ) ) {
			$pvm = date('d.m.Y', strtotime($row[ 'pvm' ]));
			$tunnit = $row[ 'tunnit' ];
			$selite = $row[ 'selite' ];
			$tunnitYht += $tunnit;
			// seuraavat muuttujat ovat sit� varten, ett� saadaan tunnit ja minuutit erilleen
			$tunnitTaulu = explode('.', $tunnit);
			$tunnitTaulusta = $tunnitTaulu[0];
			$minuutitTaulusta = $tunnitTaulu[1];
			// lasketaan minuuttien todellinen m��r�, py�ristet��n l�himp��n kokonaislukuun
			$minuutitMuotoiltu = round($minuutitTaulusta * 60 / 100);
			
			print "	<tr>
						<td>$pvm</td>
						<td>$tunnitTaulusta h $minuutitMuotoiltu min</td>
						<td>$selite</td>
					</tr>";
		}
		// muotoillaan niin, ett� on aina kaksi desimaalia
		$tunnitYhtMuotoiltu = number_format($tunnitYht, 2);
		// otetaan minuutit talteen
		$minuutitYhtMuotoiltu = substr($tunnitYhtMuotoiltu, -2);
		// r�j�ytet��n pisteen kohdalta, jotta saadaan tunnit talteen
		$tunnitYhtTaulu = explode('.', $tunnitYht);
		$tunnitYhtTaulusta = $tunnitYhtTaulu[0];
		// lasketaan minuuttien todellinen m��r�
		$minuutitYht = ( $minuutitYhtMuotoiltu * 60 / 100);
		// py�ristet��n l�himp��n kokonaislukuun
		$minuutitYht = round($minuutitYht);
		print "		<tr>
						<td>Tunnit Yhteens�</td>
						<td>$tunnitYhtTaulusta h $minuutitYht min</td>
						<td></td>
					</tr>
				</table>";
		
	} 
	// jos kyselyst� ei tule yht��n rivi�
	else {
		print "<br/><font>Ei kirjattuja ty�tunteja.</font>";
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
 * Piirt�� tilanneraportin aikav�lihakukent�t (aloitus ja lopetuspvm)
 *
 * @access   public
 * @param    mixed   $startDate   oletuksena false, haun aloitusp�iv�m��r�
 * @param    mixed   $endDate     oletuksena false, haun lopetusp�iv�m��r�
 */
function getSearchForm( $startDate = false, $endDate = false ) {
	print	"<font class='message'>Valitse aikav�lit ja paina hae.</font><br/>
		
		<style>";
			// kalenterin tyylitiedosto
			require_once '../../lib/calendar/calendar.css';
			
print 	"</style>
		<form action='' method='post'>
			<p>Sy�t� tiedot muodossa pp.kk.vvvv</p>
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