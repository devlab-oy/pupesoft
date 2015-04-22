<?php
/// yritysraportti.php
/// TAMK Yrityspeli-valikko, yrityksille laskettujen kertoimien näkymä
///
/// Annika Granlund, Jarmo Kortetjärvi
/// 2010-06-03

require_once "../inc/parametrit.inc";
require_once "laskeKerroin.php";

/*
 *  MYSQL QUERIES
 */

// yhtiorivi
	$query = "	SELECT		*
				FROM		yhtio
				WHERE 		yhtiotyyppi = 'OPY'
				ORDER BY nimi ASC
				";
				
	$yhtiot = mysql_query($query) or pupe_error($query);

// sutinakertoimet
	$kerroinrivi = sutinakertoimet();

/*
 *  END OF MYSQL QUERIES
 */

echo "<font class='head'>Yritysraportti</font><hr/><br/>";
 
// taulun ja otsikoiden tulostus
echo "<table>
		<tr>
			<th>Yhtio</th>";
				
		foreach($kerroinrivi as $key=>$value) {
			echo "<th>$key</th>";
		}
			
echo "		<th>Kokonaiskerroin</th>
		</tr>
	";

	
// Yhtioraportin rivien tulostus
while($yhtiorivi = mysql_fetch_array($yhtiot)) {
	
	// yhtion tilien saldot
	$yhtio = $yhtiorivi['yhtio'];
		// haetaan vain viimeisen kuukauden ajalta
		$now = date("Y-m-d");
		$monthAgo = date("Y-m-d", strtotime("-1 month"));

		$saldo = getSaldo($yhtio, $monthAgo, $now);

	// Kauppa OPY-yrityksille
	$opykauppa = laskeOpykauppa($saldo[720]);	

	// Markkinointipanos
	$markkinointi = laskeMarkkinointi($saldo[800]+$saldo[805]);
	
	// Toimipisteen sijainti, toimitilakulut
	$sijainti = laskeSijainti($saldo[720]);

	// Asiakassuhteet
	$asiakassuhteet = laskeAsiakassuhteet($saldo[795]);
	
	// Henkilöstöpanos
	$henkilostopanos = laskeHenkilostopanos($saldo[700]);
	
	// Työtunnit
	$tuntiquery = "	SELECT SUM(tunnit) AS tunnit
					FROM TAMK_tyoaika
					WHERE yhtio = '$yhtio';
					";
	// TODO: WHERE tuntien tekijä on oikea ihminen
	$tuntiresult = mysql_query($tuntiquery);
	$tunnit = mysql_result($tuntiresult,0,'tunnit');
	$tyotunnit = laskeTyotunnit($tunnit);
	
	// CRM
	$crm = laskeCRM($saldo[700]);
	
	// Kokonaiskerroin
	$kokonaiskerroin = ($opykauppa+$markkinointi+$sijainti+$asiakassuhteet+$henkilostopanos+$tyotunnit+$crm)/7;
	$kokonaiskerroin = number_format($kokonaiskerroin, 3, ',', '');

echo "<tr>";
			echo "<td>".$yhtiorivi['nimi']."</td>";														// Yhtion nimi
			echo "<td>".number_format($opykauppa, 3, ',', ' ')."</td>";									// Opykauppa
			echo "<td>".number_format($markkinointi, 3, ',', ' ')."</td>";								// Markkinointipanos
			echo "<td>".number_format($sijainti, 3, ',', ' ')."</td>";									// Toimipisteen sijainti
			echo "<td>".number_format($asiakassuhteet, 3, ',', ' ')."</td>";							// Asiakassuhteet
			echo "<td>".number_format($henkilostopanos, 3, ',', ' ')."</td>";							// Henkilöstöpanos
			echo "<td>".number_format($tyotunnit, 3, ',', ' ')."</td>";									// Työtunnit
			echo "<td>".number_format($crm, 3, ',', ' ')."</td>";										// CRM
			echo "<td>$kokonaiskerroin</td>";															// Kokonaiskerroin
			echo "</tr>";
}

echo "</table>";


require ("../inc/footer.inc");
?>