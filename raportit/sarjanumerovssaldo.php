<?php
	
	require("../inc/parametrit.inc");

	echo "<font class='head'>".t("Sarjanumero vs. Saldo")."</font><hr>";

	// Tuotteet
	$query = "	SELECT *
	 			FROM tuote
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				and tuote.sarjanumeroseuranta = 'S'
				ORDER BY tuoteno";
	$result = mysql_query($query) or pupe_error($query);
	
	echo "<table>";
	echo "<tr>
			<th>".t("Tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("Varasto")."</th>
			<th>".t("Varastopaikka")."</th><th>".t("Saldo")."</th><th>".t("Sarjakpl")."</th><th>".t("Sarjanumerot")."</th></tr>";
		
	while ($row = mysql_fetch_array ($result)) {	

		//saldot per varastopaikka			
		$query = "	SELECT tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa, 
					tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
					concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
					varastopaikat.nimitys
		 			FROM tuote
					JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
					JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
					and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno = '$row[tuoteno]'
					ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
		$sresult = mysql_query($query) or pupe_error($query);
    
		if (mysql_num_rows($sresult) > 0) {
			while ($saldorow = mysql_fetch_array ($sresult)) {
    
				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($saldorow["tuoteno"], '', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], '', '', '');
    
				
				$query = "	SELECT count(sarjanumeroseuranta.tunnus) kpl, group_concat(sarjanumeroseuranta.sarjanumero SEPARATOR '<br>') sarjanumerot
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							WHERE sarjanumeroseuranta.yhtio 	= '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno		= '$row[tuoteno]'
							and sarjanumeroseuranta.myyntirivitunnus	!= -1
							and (	(sarjanumeroseuranta.hyllyalue		= '$saldorow[hyllyalue]' 
									 and sarjanumeroseuranta.hyllynro 	= '$saldorow[hyllynro]' 
									 and sarjanumeroseuranta.hyllyvali 	= '$saldorow[hyllyvali]' 
									 and sarjanumeroseuranta.hyllytaso 	= '$saldorow[hyllytaso]') 
								 or ('$saldorow[oletus]' != '' and 
									(	SELECT tunnus 
										FROM tuotepaikat tt 
										WHERE sarjanumeroseuranta.yhtio = tt.yhtio and sarjanumeroseuranta.tuoteno = tt.tuoteno and sarjanumeroseuranta.hyllyalue = tt.hyllyalue
										and sarjanumeroseuranta.hyllynro = tt.hyllynro and sarjanumeroseuranta.hyllyvali = tt.hyllyvali and sarjanumeroseuranta.hyllytaso = tt.hyllytaso) is null))
							and ((tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') and tilausrivi_osto.laskutettuaika != '0000-00-00')
							ORDER BY sarjanumero+0";
				$sarjares = mysql_query($query) or pupe_error($query);
				$sarjarow = mysql_fetch_array($sarjares);
				
				if ((float) $saldo != (float) $sarjarow["kpl"]) {
					echo "<tr>
							<td valign='top'>$row[tuoteno]</td><td valign='top'>$row[nimitys]</td>
							<td valign='top'>$saldorow[nimitys] $saldorow[tyyppi]</td>
							<td valign='top'>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</td>
							<td valign='top' align='right'>".sprintf("%.2f", $saldo)."</td>
							<td valign='top' align='right'>".sprintf("%.2f", $sarjarow["kpl"])."</td><td valign='top'>$sarjarow[sarjanumerot]</td>
							</tr>";
				}
			}
		}
    
		list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, "ORVOT", '', '', '', '', '', '', '', $aikalisa);
    
		if ($saldo != 0) {
			echo "<tr><td valign='top'>".t("Tuntematon")."</td><td valign='top'>?</td>";
			echo "<td valign='top' align='right'>".sprintf("%.2f", $saldo)."</td>
					<td valign='top' align='right'>".sprintf("%.2f", $hyllyssa)."</td>
					<td valign='top' align='right'>".sprintf("%.2f", $myytavissa)."</td>
					</tr>";
		}
	}
	
    echo "</table>";
	require ("../inc/footer.inc");
	
?>