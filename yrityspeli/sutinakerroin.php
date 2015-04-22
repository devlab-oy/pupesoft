<?php
/// sutinakerroin.php
/// TAMK Yrityspeli-valikko, sutinakertoimien muuttaminen
///
/// Annika Granlund, Jarmo Kortetjärvi
/// created: 2010-06-30
/// updated: 2010-10-25

require_once "../inc/parametrit.inc";
require_once 'laskeKerroin.php';

print "<font class='head'>Automaattitilausten painokertoimet</font><hr>
		<font>Voit muuttaa automaattitilausten kertoimia</font>";

// jos painettu Päivitä-nappia
if ($_POST[ 'paivita' ]) {
	$error = false;

	$rivienMaara = $_POST[ 'rivienMaara' ];
	
	for ($i = 1; $i <= $rivienMaara; $i++) {
		$id = $_POST[ "id$i" ];
		$kerroin = is_numeric( $_POST[ "kerroin$i" ] );
		$kuvaus = mysql_real_escape_string( $_POST[ "kuvaus$i" ] );
		
		if ($kerroin != false and $kuvaus != false) {
			$kerroin = $_POST[ "kerroin$i"];
			$kuvaus = $_POST[ "kuvaus$i" ];
		
			$query = "	UPDATE		TAMK_sutinakerroin
						SET			kerroin = '$kerroin'
									, kuvaus = '$kuvaus'
						WHERE		ID = '$id'
						";
			
			$result = mysql_query($query) or pupe_error($query);
		} else {
			$error .= true;
		}
	}
	
	if ( $error != false ) {
		print "<p class='error'>Tarkista syöttämäsi tiedot!</p>";
	}
} 
// haetaan arvot kannasta (automaattitilaus)
$query = "	SELECT		*
			FROM		TAMK_sutinakerroin
			";
			
$result = mysql_query($query) or pupe_error($query);

// lomake
print "	<form action='' method='post'>
		<table>
			<tr>
				<th>Nimi</th>
				<th>Kerroin</th>
				<th>Kuvaus</th>
				<th>Esimerkki</th>
			</tr>";

$rivinNumero = 0;
$kokonaiskerroin = 0;
$esimerkkikerroin = 0;
$kerroinrivi = sutinakertoimet();

while($row = mysql_fetch_array($result)) {
	$rivinNumero++;

	$id = $row[ 'ID' ];
	$nimi = $row[ 'nimi' ];
	$kerroin = $row[ 'kerroin' ];
	$kuvaus = $row[ 'kuvaus' ];
	$kokonaiskerroin += $kerroin;
	
	print "	<tr>
				<td>$nimi</td>
				<td><input type='text' name='kerroin$rivinNumero' size='4' maxlenght='6' value='$kerroin' /></td>
				<td>
					<textarea name='kuvaus$rivinNumero'>$kuvaus</textarea>
					<input type='hidden' name='id$rivinNumero' value='$id' />
				</td>";
	
	// ESIMERKIT
		// esimerkkiarvo
			$arvo = 1000;
		switch($row[nimi]){
			default: 
				$esim = "Ei esimerkkiä.";
				break;
			case "opykauppa":
				$kerroin = $kerroinrivi['opykauppa'];
				$tulos = round(laskeOpykauppa($arvo),2);
				$esim = "<table>
							<tr>
								<td>kerroin</td>
								<td>saldo</td>
								<td>opykauppa</td>
							</tr>
							<tr>
								<td>$kerroin</td>
								<td>$arvo</td>
								<td>$tulos</td>
							</tr>
						</table>
						";
				break;
			case "markkinointipanos":
				$kerroin = $kerroinrivi['markkinointipanos'];
				$tulos = round(laskeMarkkinointi($arvo),2);
				$esim = "<table>
							<tr>
								<td>kerroin</td>
								<td>saldo</td>
								<td>markkinointipanos</td>
							</tr>
							<tr>
								<td>$kerroin</td>
								<td>$arvo</td>
								<td>$tulos</td>
							</tr>
						</table>
						";
				break;
			case "toimipisteenSijainti":
				$kerroin = $kerroinrivi['toimipisteenSijainti'];
				$tulos = round(laskeSijainti($arvo),2);
				$esim = "<table>
							<tr>
								<td>kerroin</td>
								<td>saldo</td>
								<td>sijainti</td>
							</tr>
							<tr>
								<td>$kerroin</td>
								<td>$arvo</td>
								<td>$tulos</td>
							</tr>
						</table>
						";
				break;
			case "asiakassuhteet":
				$kerroin = $kerroinrivi['asiakassuhteet'];
				$tulos = round(laskeAsiakassuhteet($arvo),2);
				$esim = "<table>
							<tr>
								<td>kerroin</td>
								<td>saldo</td>
								<td>asiakassuhteet</td>
							</tr>
							<tr>
								<td>$kerroin</td>
								<td>$arvo</td>
								<td>$tulos</td>
							</tr>
						</table>
						";
				break;
			case "henkilostopanos":
				$kerroin = $kerroinrivi['henkilostopanos'];
				$tulos = round(laskeHenkilostopanos($arvo),2);
				$esim = "<table>
							<tr>
								<td>kerroin</td>
								<td>saldo</td>
								<td>henkilöstöpanos</td>
							</tr>
							<tr>
								<td>$kerroin</td>
								<td>$arvo</td>
								<td>$tulos</td>
							</tr>
						</table>
						";
				break;
			case "tyotunnit":
				$arvo = 100;
				$kerroin = $kerroinrivi['tyotunnit'];
				$tulos = round(laskeTyotunnit($arvo),2);
				$esim = "<table>
							<tr>
								<td>kerroin</td>
								<td>tunnit</td>
								<td>työtunnit</td>
							</tr>
							<tr>
								<td>$kerroin</td>
								<td>$arvo</td>
								<td>$tulos</td>
							</tr>
						</table>
						";
				break;
			case "CRM":
				$kerroin = $kerroinrivi['CRM'];
				$tulos = round(laskeCRM($arvo),2);
				$esim = "<table>
							<tr>
								<td>kerroin</td>
								<td>saldo</td>
								<td>CRM</td>
							</tr>
							<tr>
								<td>$kerroin</td>
								<td>$arvo</td>
								<td>$tulos</td>
							</tr>
						</table>
						";
				break;
				
		}
		$esimerkkikerroin += $tulos;
		echo "<td>$esim</td>";
	print "</tr>";
}
	$kokonaiskerroin = number_format(($kokonaiskerroin / $rivinNumero), 2, '.', '');
	$esimerkkikerroin = round(($esimerkkikerroin / $rivinNumero), 2);
	print 	"	<tr>
					<td>keskim. kerroin</td>
					<td>$kokonaiskerroin</td>
					<td></td>
					<td>kokonaiskerroin: $esimerkkikerroin</td>
				</tr>
			";


print "	</table>
				<p>
					<input type='hidden' name='rivienMaara' value='$rivinNumero' />
					<input type='submit' name='paivita' value='Päivitä' />
				</p>
		</form>";

print 	"<p>Kertoimia muuttamalla voit painottaa yksittäisen osa-alueen vaikutusta automaattitilausten tilauksen rahalliseen arvoon. <br/>
			- Kertoimella 0.00 osa-aluetta ei huomioida lainkaan kokonaiskerrointa laskettaessa<br/>
			- Kertoimella 1.00 osa-alueen painoarvo on 100% (ei painostusta)<br/>
			- Kertoimella 2.00 osa-alueen painoarvo on 200%<br/>
			- Kertoimella 2.50 osa-alueen painoarvo on 250% jne.
		</p>
		
		<p>Yksittäisen osa-alueen vaikutus tilauksiin ei voi olla yli 150% (1.5).</p>
		
		";
		
require ("../inc/footer.inc");

?>