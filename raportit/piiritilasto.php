<?php
	require ("inc/parametrit.inc");
	
	echo "<font class=head>".t("Piiritilasto")."</font><hr>";
	
	// käyttis
	echo "<form action='$PHP_SELF' action='POST'>";
	echo "<table>";

	$edellisvuosi = date("Y")-1;
	$toissavuosi  = date("Y")-2;
	$vv = date("Y");	
	
	if (!isset($kk1))
		$kk1 = date("m");
	if (!isset($vv1))
		$vv1 = date("Y");
	if (!isset($pp1))
		$pp1 = '01';
		
	if (!isset($kk2))
		$kk2 = date("m");
	if (!isset($vv2))
		$vv2 = date("Y");
	if (!isset($pp2))
		$pp2 = date("d");
	
	$asiakaspiirisql = "";
		
	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<input type='hidden' name='vv' value='$vv'>";
	echo "<input type='hidden' name='edellisvuosi' value='$edellisvuosi'>";
	echo "<input type='hidden' name='toissavuosi' value='$toissavuosi'>";	
	echo "<tr><th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='pp1' value='$pp1' size='3'></td>
			<td><input type='text' name='kk1' value='$kk1' size='3'></td>
			<td><input type='text' name='vv1' value='$vv1' size='5'></td></tr>";
			
	echo "<tr><th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='pp2' value='$pp2' size='3'></td>
			<td><input type='text' name='kk2' value='$kk2' size='3'></td>
			<td><input type='text' name='vv2' value='$vv2' size='5'></td></tr>";	
	
	echo "<tr><td colspan='5'>";
	
	$monivalintalaatikot = array('ASIAKASPIIRI');
	require ("tilauskasittely/monivalintalaatikot.inc");		
	
	echo "</td>";		
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."' name='painoinnappia'></td></tr>";
	echo "</table></form>";
	echo "<br>";


	if ($tee != '' and isset($painoinnappia)) {
		// aletaan tekee taulukkoa.
		$pvm1 = $vv1.'-'.$kk1.'-'.$pp1;
		$pvm2 = $vv2.'-'.$kk2.'-'.$pp2;
		
		if (isset($mul_asiakaspiiri) and $mul_asiakaspiiri != "") {
			$asiakaspiiri = "";
			foreach ($mul_asiakaspiiri as $key => $value ) {
				$asiakaspiiri .= $value.',';
			}
			if (trim($asiakaspiiri) != "") {
				$asiakaspiiri = "(".substr($asiakaspiiri, 0, -1).")";
				$asiakaspiirisql = " AND asiakas.piiri in $asiakaspiiri ";
			}
		}
		
		echo "<table>";
		echo "<th>".t("Osasto")."</th>";
		echo "<th>".t("Tuoteryhmä")."</th>";
		echo "<th>".t("Myynti")."<br> $toissavuosi</th>";
		echo "<th>".t("Kate")."<br> $toissavuosi</th>";
		echo "<th>".t("Myynti ")." $edellisvuosi</th>";
		echo "<th>".t("Kate")."<br> $edellisvuosi</th>";
		echo "<th>".t("Myynti aikavälillä")."</th>";
		echo "<th>".t("Kate")."</th>";
		echo "<th>".t("Myynti")." $vv1</th>";
		echo "<th>".t("Kate")."</th>";
		echo "<th>Budjetti</th>";
		echo "<th>Budjetti</th>";
		echo "<th>Budjetti</th>";
		echo "<th>Budjetti</th>";

		$query = "	SELECT
					tilausrivi.osasto,
					tilausrivi.try,
					asiakas.piiri,
					round(sum(if(tilausrivi.laskutettuaika >= '{$vv1}-{$kk1}-{$pp1}' and tilausrivi.laskutettuaika <= '{$vv2}-{$kk2}-{$pp2}', tilausrivi.rivihinta, 0)), 2) aikavalilla,
					round(sum(if(tilausrivi.laskutettuaika >= '{$vv1}-{$kk1}-{$pp1}' and tilausrivi.laskutettuaika <= '{$vv2}-{$kk2}-{$pp2}', tilausrivi.kate, 0)), 2) kate_aikavalilla, 
					round(sum(if(tilausrivi.laskutettuaika >= '{$vv}-01-01', tilausrivi.rivihinta, 0)), 2) summaVA,
					round(sum(if(tilausrivi.laskutettuaika >= '{$vv}-01-01', tilausrivi.kate, 0)), 2) kateVA,
					round(sum(if(tilausrivi.laskutettuaika >= '{$edellisvuosi}-01-01' and tilausrivi.laskutettuaika <= '{$edellisvuosi}-12-31', tilausrivi.rivihinta, 0)), 2) edvuodenmyynti, 
					round(sum(if(tilausrivi.laskutettuaika >= '{$edellisvuosi}-01-01' and tilausrivi.laskutettuaika <= '{$edellisvuosi}-12-31', tilausrivi.kate, 0)), 2) edvuodenkate,
					round(sum(if(tilausrivi.laskutettuaika >= '{$toissavuosi}-01-01' and tilausrivi.laskutettuaika <= '{$toissavuosi}-12-31', tilausrivi.rivihinta, 0)), 2) toissavuodenmyynti, 
					round(sum(if(tilausrivi.laskutettuaika >= '{$toissavuosi}-01-01' and tilausrivi.laskutettuaika <= '{$toissavuosi}-12-31', tilausrivi.kate, 0)), 2) toissavuodenkate
					FROM tilausrivi use index (yhtio_tyyppi_osasto_try_laskutettuaika)
					JOIN lasku on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
					JOIN asiakas on (asiakas.yhtio = tilausrivi.yhtio and asiakas.tunnus = lasku.liitostunnus $asiakaspiirisql)
					WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}' and tilausrivi.tyyppi = 'L' 
					and	tilausrivi.laskutettuaika >= '{$toissavuosi}-01-01' 
					and	tilausrivi.laskutettuaika <= '{$vv2}-{$kk2}-{$pp2}'
					group by 1,2,3";
		$eresult = pupe_query($query);
		
		while ($row = mysql_fetch_assoc($eresult)) {
			
			echo "<tr>";
			$osastores = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
			$osastorow = mysql_fetch_assoc($osastores);
			if ($osastorow == "") {
				$osastorow['selitetark'] = $row['osasto'];
			}
			
			$tryres = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
			$tryrow = mysql_fetch_assoc($tryres);
			if ($tryrow == "") {
				$tryrow['selitetark'] = $row['try'];
			}
			
			// riviotsikoita
			echo "<td>$osastorow[selitetark]</td>";
			echo "<td>$tryrow[selitetark] </td>";
	
			echo "<td align='right'>$row[toissavuodenmyynti]</td>";
			echo "<td align='right'>$row[toissavuodenkate]</td>";
			echo "<td align='right'>$row[edvuodenmyynti]</td>";
			echo "<td align='right'>$row[edvuodenkate]</td>";
			echo "<td align='right'>$row[aikavalilla]</td>";
			echo "<td align='right'>$row[kate_aikavalilla]</td>";
			echo "<td align='right'>$row[summaVA]</td>";
			echo "<td align='right'>$row[kateVA]</td>";
			echo "<td align='right'>tulossa</td>";
			echo "<td align='right'>tulossa</td>";
			echo "<td align='right'>tulossa</td>";
			echo "<td align='right'>tulossa</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	


	require ("inc/footer.inc");
?>