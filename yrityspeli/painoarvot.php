<?php
/// painoarvot.php
/// TAMK Yrityspeli-valikko, automaattiostojen painoarvojen muuttaminen
///
/// Annika Granlund, Jarmo Kortetj‰rvi
/// 2010-06-30

require_once "../inc/parametrit.inc";
require_once 'laskeKerroin.php';

print "<font class='head'>Tilausten painoarvot</font><hr>
		<font>T‰ll‰ sivulla voit muuttaa automaattitilausten painoarvoja.</font><br/><br/>";

// jos painettu P‰ivit‰-nappia
if ($_POST[ 'paivita' ]) {

	$error = false;

	$rivienMaara = $_POST[ 'rivienMaara' ];

	for ($i = 1; $i <= $rivienMaara; $i++) {
		$onkoTilaustenMaaraInteger = isVariableInteger( $_POST[ "maara$i" ] );
		$onkoNimikkeidenMaaraInteger = isVariableInteger( $_POST[ "nimikkeidenMaara$i" ] );
		$onkoPainoarvoInteger = isVariableInteger( $_POST[ "painoarvo$i" ] );
		
		if ( $onkoTilaustenMaaraInteger != false and $onkoNimikkeidenMaaraInteger != false and $onkoPainoarvoInteger != false ) {
			$tilaustyypinNumero = $_POST[ "tilaustyyppi$i" ];
			$tilaustenMaara = $_POST[ "maara$i" ];
			$nimikkeidenMaara = $_POST[ "nimikkeidenMaara$i" ];
			$painoarvo = $_POST[ "painoarvo$i" ];
			
			$query = "	UPDATE		TAMK_automaattitilauskerroin
						SET			kertaaViikossa = $tilaustenMaara
									, tilausrivit = $nimikkeidenMaara
									, painoarvo = $painoarvo
						WHERE		tilaustyyppi = $tilaustyypinNumero
						";
			
			$result = mysql_query($query) or pupe_error($query);
		} else {
			$error = true;
		}
	}
	
	if ( $error != false ) {
		print "<font class='error'>Tarkista syˆtt‰m‰si tiedot!</font>";
	}
} 

// haetaan arvot kannasta (TAMK_automaattitilauskerroin)
$query = "	SELECT		*
			FROM		TAMK_automaattitilauskerroin
			";
			
$result = mysql_query($query) or pupe_error($query);

// lomake
print "	<form action='painoarvot.php' method='post'>
		<table>
			<tr>
				<th>Tilaustyyppi</th>
				<th>Tilausten m‰‰r‰/vko</th>
				<th>Tilausnimikkeiden m‰‰r‰/tilaus</th>
				<th>Painoarvo (%)</th>
			</tr>";

$rivinNumero = 0;
			
while($row = mysql_fetch_array($result)) {
	$rivinNumero++;

	//$tilaustyypinNimi = $row[ 'tilaustyypinNimi' ];
	if($row[ 'tilaustyyppi' ] == 1) $tilaustyypinNimi = "sutina";
	else if($row[ 'tilaustyyppi' ] == 2) $tilaustyypinNimi = "random";
	else $tilaustyypinNimi = $row[ 'tilaustyyppi' ];
	$tilaustyyppi = $row[ 'tilaustyyppi' ];

	$maara = $row[ 'kertaaViikossa' ];
	$nimikkeet = $row[ 'tilausrivit' ];
	$painoarvo = $row[ 'painoarvo' ];

	
	print "	<tr>
				<td>$tilaustyypinNimi</td>
				<td><input type='text' name='maara$rivinNumero' size='4' maxlenght='4' value='$maara' /></td>
				<td><input type='text' name='nimikkeidenMaara$rivinNumero' size='4' maxlenght='4' value='$nimikkeet' /></td>
				<td><input type='text' name='painoarvo$rivinNumero' size='4' maxlenght='4' value='$painoarvo' />
				<input type='hidden' name='tilaustyyppi$rivinNumero' value='$tilaustyyppi' /></td>
			</tr>
		";
}

print "	</table>
				<p>
					<input type='hidden' name='rivienMaara' value='$rivinNumero' />
					<input type='submit' name='paivita' value='P‰ivit‰' />
				</p>
		</form>";
		
print "	<font>
			- Tilausten m‰‰r‰/vko - montako automaattitilausta viikossa l‰hetet‰‰n. Tilaukset l‰hetet‰‰n satunnaisina arkip‰ivin‰<br/>
			- Tilausnimikkeiden m‰‰r‰/tilaus - keskim‰‰r‰inen nimikkeiden (rivien) m‰‰r‰ yhdess‰ tilauksessa, vaihteluv‰li on +- 1 kpl. <br/>
			- Painoarvo - vaikuttaa tilaussumman suuruuteen. Esim. normaalitilauksen summa on 1000 euroa (100%),<br/>
			painoarvo 80 antaa tilaussummaksi 800 euroa (80%)<br/>
			ja painoarvo 150 antaa tilaussummaksi 1500 euroa (150%).
		</font>";

require ("../inc/footer.inc");

/**
 * Tarkistaa, onko parametrina annettu arvo integer-tyyppinen. 
 * Funktio k‰y myˆs lomakkeeseen syˆtetyn arvon tarkistamiseen (lomakkeen arvo on aina string-tyyppinen)
 *
 * @access  public
 * @param	$variable   muuttuja, joka halutaan tarkistaa
 */
function isVariableInteger( $variable ) {
	// Tarkistetaan, ett‰ arvo numeerinen (joko numero tai string)
	if(is_numeric($variable) === TRUE){
	   
		// Tarkistetaan, ett‰ arvo on nimenomaan integer
		if((int)$variable == $variable){
			return TRUE;
		// Jos on numeerinen, mutta ei integer
		} else {
			return FALSE;
		}
	// Ei ole numeerinen
	} else {
		return FALSE;
	}
}
?>
