<?php
/// tyoajanseuranta.php
/// TAMK, ty�ajan seuranta opyille
///
/// Annika Granlund, Jarmo Kortetj�rvi
/// created: 2010-07-01
/// modified: 2010-09-08

require_once "../inc/parametrit.inc";

$errorText = '';
// kuka, nimi ja yhtio tulee pupen sis�lt�
$kuka = $kukarow[ 'kuka' ];
$nimi = $kukarow[ 'nimi' ];
$yhtio = $kukarow[ 'yhtio' ];

$tiimiArray = array( 1 => 'talous', 'materiaalihallinto', 'myynti', 'toimitusjohtaja');

print "<font class='head'>Ty�ajan kirjaaminen</font><font class='message'> - $nimi ($yhtio)</font><hr>";

// jos painettu kirjaa (p�iv�m��r� on valittu)
if ($_POST[ 'kirjaa' ]) {
	$selectedDay = $_POST[ 'selectedDay' ];
	$nyt = date('d.m.Y');
	
	// jos pvm ei ole annettu
	if ( $selectedDay == false ) {
		$errorText = "Tarkista p�iv�m��r�.";
	}
	
	$nyt = date('d.m.Y');
	// jos pvm on tulevaisuudessa
	if( strtotime($selectedDay) > strtotime($nyt) ) {
		$errorText = "Tarkista p�iv�m��r�. (Et voi kirjata tunteja tulevaisuuteen.)";
		$selectedDay = false;
	}
	
	// pilkotaan p�iv�m��r� osiin (p�iv�, kk, vuosi) checkdatea varten
	$dateArray = explode('.', $selectedDay);
	// tarkistetaan, ett� p�iv�m��r� on oikeasti olemassa
	if ( checkdate( $dateArray[1], $dateArray[0], $dateArray[2] ) == false ) {
		$errorText = "Tarkista p�iv�m��r�.";
		$selectedDay = false;
	}
	
	// jos errorTextiss� on tietoa, tulostetaan se
	if ($errorText) {
		print "<font class='error'>$errorText</font><br/>";
		getSearchForm( $selectedDay );
	}
	
	// jos pvm on annettu (ja se on validi) ja errorText on tyhj�, annetaan kirjata tunteja
	if( $selectedDay != false and empty($errorText) ) {
		$selectedDayMySql = date('Y-m-d', strtotime($selectedDay));
		$kirjattu = 0;
		
		// tarkistetaan, onko kyseiselle p�iv�lle jo tunteja merkittyn�
		$query = "	SELECT		*
					FROM		TAMK_tyoaika
					WHERE		kuka = '$kuka'
					AND			yhtio = '$yhtio'
					AND			pvm  = '$selectedDayMySql'
					";	
		
		$result = mysql_query( $query ) or pupe_error( $query );
		$row = mysql_fetch_assoc($result);
		$tunnit = $row[ 'tunnit' ];
		$selite = $row[ 'selite' ];
		$tiimi = $row[ 'tiimi' ];
		
		if (!empty($tunnit)) {
			print "<font>Olet jo kirjannut tunteja p�iv�m��r�lle $selectedDay, mutta voit muokata niit�:</font>";
			$kirjattu = 1;
		} else {
			print "<font>Tunnit p�iv�m��r�lle $selectedDay</font>";
			$kirjattu = 0;
		}
		
		getHourFrom( $tiimiArray, $selectedDay, $tunnit, $selite, $tiimi, $kirjattu);
		
		print "	<form action='' method='post'>
					<p><input type='submit' name='back' value='Takaisin p�iv�m��r��n valintaan'/></p>
				</form>";
	}
} 
// tunnit on annettu
else if ( $_POST[ 'tallenna' ] ) {
	$kirjattu = $_POST[ 'kirjattu' ];
	$tunnit = $_POST[ 'tunnit' ];
	$tiimi = $_POST[ 'tiimi' ];
	// jos data ei ole numeerista
	if ( !isDataNumeric( $tunnit )) {
		$errorText = 'Tarkista tuntim��r�!';
	} else {
		// korvataan pilkut pisteill� (sallitaan 6,5 mutta muutetaan se kantaa varten 6.5)
		$tunnit = str_replace(",", ".", $tunnit);
	}
	$selite = mysql_real_escape_string($_POST[ 'selite' ]);
	if ($selite == false) {
		$errorText = 'Tarkista selite.';
	}
	$pvm = $_POST[ 'pvm' ];
	$pvmMySql = date('Y-m-d', strtotime($pvm));
	$kuka = $kukarow[ 'kuka' ];
	$yhtio = $kukarow[ 'yhtio' ];
	
	// jos errorTextiss� on jotain
	if ( !empty($errorText) ) {
		print "<font class='error'>$errorText</font><br/>
				<font>Tunnit p�iv�m��r�lle $pvm</font>";
		getHourFrom( $tiimiArray, $pvm, $tunnit, $selite, $tiimi, $kirjattu);
		$errorText = '';
	} else {
		// jos kyseess� ensimm�inen tallennus
		if ( $kirjattu == '0' ) {
			$query = "	INSERT INTO TAMK_tyoaika
						SET			yhtio = '$yhtio'
									, kuka = '$kuka'
									, pvm = '$pvmMySql'
									, tunnit = '$tunnit'
									, tiimi = '$tiimi'
									, selite = '$selite'
									, luontiaika = now()
						";
						
			$result = mysql_query( $query ) or pupe_error( $query );
			
			if ( $result ) {
				print "<font>Kiitos, tunnit on tallennettu!</font><br/><br/>";
				getSearchForm();
			}
		}
		// jos kyseess� tietojen p�ivitys (ty�tunteja on kirjattu jo aiemmin)
		else {
			$query = "	UPDATE		TAMK_tyoaika
						SET			tunnit = '$tunnit'
									, tiimi = '$tiimi'
									, selite = '$selite'
									, luontiaika = now()
						WHERE		yhtio = '$yhtio'
						AND			kuka = '$kuka'
						AND			pvm = '$pvmMySql'
						";
			$result = mysql_query( $query ) or pupe_error( $query );
			
			if ( $result ) {
				print "<font>Kiitos, tunnit on tallennettu!</font><br/><br/>";
				getSearchForm();
			}
		}
	}
	
} else {
	getSearchForm();
}

// piirret��n aiemmin kirjatut tunnit taulukkoon, muotoillaan tunnit -> tunnit ja minuutit
print "<font class='head'>Kirjaamasi ty�tunnit</font><hr/>";

$query = "	SELECT 		pvm
						, tunnit
						, tiimi
						, selite
			FROM		TAMK_tyoaika 
			WHERE		yhtio = '$yhtio'
			AND			kuka = '$kuka'
			ORDER BY	pvm DESC
			";

$result = mysql_query( $query ) or pupe_error( $query );
$numRows = mysql_num_rows( $result );

// jos kyselyst� tulee 1 tai enemm�n rivej� (tunteja on jo kirjattu aikaisemmin)
if ( $numRows >= 1 ) {
	$tunnitYht = 0;
	print "	<font>Mik�li haluat muokata kirjattuja tunteja, valitse kyseinen p�iv�m��r� (yll�) ja paina Kirjaa tunnit.</font>
			<table>
			<tr>
				<th>P�iv�m��r�</th>
				<th>Kirjatut ty�tunnit</th>
				<th>Tiimi</th>
				<th>Selite</th>
			</tr>";
	while( $row = mysql_fetch_assoc( $result ) ) {
		$pvm = date('d.m.Y', strtotime($row[ 'pvm' ]));
		$tunnit = $row[ 'tunnit' ];
		$tiimi = $row[ 'tiimi' ];
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
					<td>$tiimi</td>
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
					<td></td>
				</tr>
			</table>";
	
} 
// jos kyselyst� ei tule yht��n rivi�
else {
	print "<font>Et ole viel� kirjannut ty�tunteja.</font>";
}

require ("../inc/footer.inc");

/**
 * Piirt�� lomakkeen tuntien ja selitteen kirjaamista varten.
 *
 * @access		public
 * @param		string		$selectedDay		valittu pvm
 * @param		numeric		$tunnit				tunnit joko integerina tai decimalina
 * @param		string		$selite				lyhyt selite tehdyst� ty�st�
 * @param		string		$tiimi				valittu tiimi
 * @param		boolean		$kirjattu			tieto, onko kyseiselle pvm:lle kirjattu tunteja jo aikaisemmin
 *												0 - ei ole kirjattu aiemmin, 1 - on kirjattu aiemmin
 */
function getHourFrom( $tiimiArray, $selectedDay, $tunnit, $selite, $tiimi, $kirjattu ) {
	print "<form action='' method='post'>
				<p>Ty�tunnit: <input type='text' name='tunnit' value='$tunnit' size='2' />
				Tiimi: <select name='tiimi'>";
				foreach ( $tiimiArray as $key => $value ) {
					if ( $value == $tiimi ) {
						print "<option selected='selected'>$value</option>";
					} else {
						print "<option>$value</option>";
					}
				}
	print "		</select><br/>
				Selite: <input type='text' name='selite' value='$selite' size='30' maxlength='255' />
				<input type='hidden' name='kirjattu' value='$kirjattu' />
				<input type='hidden' name='pvm' value='$selectedDay' />
				</p>
				<p><input type='submit' name='tallenna' value='Tallenna' /></p>
			</form>";
}

/**
 * Piirt�� ty�ajanseurantaan kalenterin (lomake), josta valitaan yksi p�iv� (tuntien lis��misp�iv�)
 *
 * @access   public
 * @param    mixed   $selectedDay   oletuksena false, ty�n kirjaamisp�iv�
 */
function getSearchForm( $startDate = false ) {
	print	"<font class='message'>Valitse p�iv�m��r� ja paina kirjaa tunnit.</font><br/>
		
		<style>";
			// kalenterin tyylitiedosto
			require_once '../../lib/calendar/calendar.css';
			
print 	"</style>
		<form action='' method='post'>
			<font>Sy�t� tiedot muodossa pp.kk.vvvv</font>
			<table>
				<tr>
					<td>pvm</td>
					<td>
					<input type='text' name='selectedDay' id='selectedDay' value='$selectedDay' />
					<script type='text/javascript' src='../../lib/calendar/calendar.js' ></script>
					<script type='text/javascript' >
						calendar.set('selectedDay');
					</script>
					</td>
				</tr>
			</table>
			<p>
			<input type='submit' name='kirjaa' value='Kirjaa tunnit' />
			</p>
		
		</form>";
}

/**
 * Tarkistaa, onko data numeerinen (hyv�ksyy my�s desimaalipilkun).
 *
 * @access	public
 * @param	mixed	$data
 * @return	jos data on numeerinen, palauttaa datan
 * 			jos ei, palauttaa falsen
 */
function isDataNumeric( $data ) {
	$value = false;
	$data = str_replace(",", ".", $data);
	
	if (is_numeric( $data ) and $data >= 0) {
		$value = $data;
	}

	return $value;
}

?>