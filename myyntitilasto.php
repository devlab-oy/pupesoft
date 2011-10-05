<?php
	require ("inc/parametrit.inc");
	
	echo "<font class=head>".t("Myyntitilasto")."</font><hr>";
	
	// käyttis
	echo "<form action='$PHP_SELF' action='POST'>";
	echo "<table>";

	$edvv = date("Y")-1;
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
		
	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<input type='hidden' name='vv' value='$vv'>";
	echo "<input type='hidden' name='edvv' value='$edvv'>";
	echo "<tr><th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='pp1' value='$pp1' size='3'></td>
			<td><input type='text' name='kk1' value='$kk1' size='3'></td>
			<td><input type='text' name='vv1' value='$vv1' size='5'></td></tr>";
			
	echo "<tr><th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='pp2' value='$pp2' size='3'></td>
			<td><input type='text' name='kk2' value='$kk2' size='3'></td>
			<td><input type='text' name='vv2' value='$vv2' size='5'></td>";	
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr>";
	echo "</table></form>";
	echo "<br>";
	
	if (!function_exists("tuoteryhman_varastonarvo")) {

		function tuoteryhman_varastonarvo($parametrit) {
			global $kukarow, $yhtiorow;
			
			$osasto = $parametrit['osasto'];
			$try = $parametrit['try'];
			$pvm1 = isset($parametrit['pvm1']) ? $parametrit['pvm1'] : "";
			$pvm2 = isset($parametrit['pvm2']) ? $parametrit['pvm2'] : $pvm1;

			if ($pvm1 == "" or $osasto == "" or $try == "") {
				return false;
			}

			// saldo nyt
			$query = "	SELECT sum(kpl) saldo_nyt
						FROM tapahtuma
						JOIN tuote on (tuote.yhtio = tapahtuma.yhtio AND tuote.tuoteno = tapahtuma.tuoteno)
						WHERE tapahtuma.yhtio = '{$kukarow["yhtio"]}' 
						AND tuote.osasto = '{$osasto}'
						AND tuote.try = '{$try}'";
			$result = pupe_query($query);
			$arvo = mysql_fetch_assoc($result);
			$saldo_nyt = $arvo['saldo_nyt'];
			
			// varastonmuutos
 			$query = " 	SELECT sum(if(tapahtuma.laadittu >= '{$pvm2}', kpl, 0))*-1 arvo1, 
						sum(if(tapahtuma.laadittu >= date_sub('{$pvm2}', interval 12 month), kpl, 0))*-1 arvo2 
						FROM tapahtuma 
						JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio AND tuote.tuoteno = tapahtuma.tuoteno) 
						WHERE tapahtuma.yhtio = '{$kukarow["yhtio"]}' 
						AND tuote.osasto = '{$osasto}' 
						AND tuote.try = '{$try}'";
			$result = pupe_query($query);
			$row = mysql_fetch_assoc($result);
			
			$arvo1 = $saldo_nyt + $row['arvo1'];
			$arvo2 = $saldo_nyt + $row['arvo2'];

			return array("arvo1" => $arvo1, "arvo2" => $arvo2);

		}
	}
	
	if ($tee != '') {
		// aletaan tekee taulukkoa.
		$pvm1 = $vv1.'-'.$kk1.'-'.$pp1;
		$pvm2 = $vv2.'-'.$kk2.'-'.$pp2;
		
		echo "<table>";
		echo "<th>".t("Osasto")."</th>";
		echo "<th>".t("Tuoteryhmä")."</th>";
		echo "<th>".t("Tilanne nyt")."</th>";
		echo "<th>".t("Kate")."</th>";
		echo "<th>".t("Kate %")."</th>";
		echo "<th>".t("tilanne")." $edvv</th>";
		echo "<th>".t("Kate")."</th>";
		echo "<th>".t("Kate %")."</th>";
		echo "<th>$edvv ".t("Myynti")."</th>";
		echo "<th>$edvv ".t("Kate")."</th>";
		echo "<th>$edvv ".t("Kate %")."</th>";
		echo "<th>".t("12kk Myynti")."</th>";
		echo "<th>".t("12kk Kate")."</th>";
		echo "<th>".t("12kk Kate %")."</th>";
		echo "<th>".t("Varasto")." <br> $pp2.$kk2.$vv2</th>";
		echo "<th>".t("Varasto")." <br> $pp2.$kk2.$edvv</th>";
		
			
		$query = "	SELECT
					osasto,
					try,
					round(sum(if(tilausrivi.laskutettuaika >= '{$vv1}-{$kk1}-{$pp1}' and tilausrivi.laskutettuaika <= '{$vv2}-{$kk2}-{$pp2}', rivihinta, 0)), 2) aikavalilla,
					round(sum(if(tilausrivi.laskutettuaika >= '{$vv1}-{$kk1}-{$pp1}' and tilausrivi.laskutettuaika <= '{$vv2}-{$kk2}-{$pp2}', kate, 0)), 2) kate_aikavalilla, 
					round(sum(if(tilausrivi.laskutettuaika >= date_sub('{$vv}-{$kk2}-{$pp2}', interval 12 month), rivihinta, 0)), 2) myynti12,
					round(sum(if(tilausrivi.laskutettuaika >= date_sub('{$vv}-{$kk2}-{$pp2}', interval 12 month), kate, 0)), 2) kate12,
					round(sum(if(tilausrivi.laskutettuaika >= '{$vv}-01-01', rivihinta, 0)), 2) summaVA,
					round(sum(if(tilausrivi.laskutettuaika >= '{$vv}-01-01', kate, 0)), 2) kateVA,
					round(sum(if(tilausrivi.laskutettuaika >= '{$vv}-01-01', kpl, 0)), 2) kplVA, 
					round(sum(if(tilausrivi.laskutettuaika >= date_sub('{$vv1}-{$kk1}-{$pp1}', interval 12 month) and tilausrivi.laskutettuaika <= date_sub('{$vv2}-{$kk2}-{$pp2}', interval 12 month), rivihinta, 0)), 2) aikavalillaedellinen, 
					round(sum(if(tilausrivi.laskutettuaika >= date_sub('{$vv1}-{$kk1}-{$pp1}', interval 12 month) and tilausrivi.laskutettuaika <= date_sub('{$vv2}-{$kk2}-{$pp2}', interval 12 month), kate,0 )), 2) kate_aikavalillaedellinen, 
					round(sum(if(tilausrivi.laskutettuaika >= '{$edvv}-01-01' and tilausrivi.laskutettuaika <= '{$edvv}-12-31', rivihinta, 0)), 2) EDvuodenmyynti, 
					round(sum(if(tilausrivi.laskutettuaika >= '{$edvv}-01-01' and tilausrivi.laskutettuaika <= '{$edvv}-12-31', kate, 0)), 2) EDvuodenkate 
					FROM tilausrivi use index (yhtio_tyyppi_osasto_try_laskutettuaika)
					WHERE yhtio = '{$kukarow["yhtio"]}' and tyyppi = 'L' 
					and	laskutettuaika >= date_sub('{$vv1}-{$kk1}-{$pp1}', interval 12 month) 
					and	laskutettuaika <= '{$vv2}-{$kk2}-{$pp2}'
					group by 1,2";

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

			$parametrit['osasto'] = $row['osasto'];
			$parametrit['try'] = $row['try'];
			$parametrit['pvm1'] = $pvm1;
			$parametrit['pvm2'] = $pvm2;
			
			$arvotaulu = tuoteryhman_varastonarvo($parametrit);
			$arvo_hetkella_1 = $arvotaulu['arvo1'];
			$arvo_hetkella_2 = $arvotaulu['arvo2'];
			
			echo "<td align='right'>$row[aikavalilla]</td>";
			echo "<td align='right'>$row[kate_aikavalilla]</td>";
			echo "<td align='right'>".round($row['kate_aikavalilla'] / abs($row['aikavalilla']) * 100, 2)."</td>";
			echo "<td align='right'>$row[aikavalillaedellinen]</td>";
			echo "<td align='right'>$row[kate_aikavalillaedellinen]</td>";
			echo "<td align='right'>".round($row['kate_aikavalillaedellinen'] / abs($row['aikavalillaedellinen']) * 100, 2)."</td>";
			echo "<td align='right'>$row[EDvuodenmyynti]</td>";
			echo "<td align='right'>$row[EDvuodenkate]</td>";
			echo "<td align='right'>".round($row['EDvuodenkate'] / abs($row['EDvuodenmyynti']) * 100, 2)."</td>";
			echo "<td align='right'>$row[myynti12]</td>";
			echo "<td align='right'>$row[kate12]</td>";
			echo "<td align='right'>".round($row['kate12'] / abs($row['myynti12']) * 100, 2)."</td>";
			echo "<td align='right'>$arvo_hetkella_1</td>";
			echo "<td align='right'>$arvo_hetkella_2</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	


	require ("inc/footer.inc");
?>