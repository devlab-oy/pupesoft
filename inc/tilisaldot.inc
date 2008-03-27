<?php
	
	$MONTH_ARRAY = array(1=>t('Tammikuu'),t('Helmikuu'),t('Maaliskuu'),t('Huhtikuu'),t('Toukokuu'),t('Kesäkuu'),t('Heinäkuu'),t('Elokuu'),t('Syyskuu'),t('Lokakuu'),t('Marraskuu'),t('Joulukuu'));
	
	if ($tee == '1' and !isset($ajarappari)) {
		$tee = "";
	}
	
	
	if ($tee == '1') {
		if ($tilino != '') {
			$query = "	SELECT nimi
						FROM tili
						WHERE yhtio='$kukarow[yhtio]' and tilino = '$tilino'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) != 1) {
				echo "<font class='error'>".t("Tili puuttuu tai sitä ei löydy")."!</font><br>";

				$tee='';
			}
		}
	}

	if ($tee == '1') {
		
		$tkausi = (int) $tkausi;

		$query = "	SELECT tilikausi_alku, tilikausi_loppu
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = $tkausi";
		$vresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($vresult) == 1) {
			$tilikausirow = mysql_fetch_array($vresult);

			echo "<font class='head'>".t("Tilien saldot tilikaudelta")." ".tv1dateconv($tilikausirow["tilikausi_alku"])." - ".tv1dateconv($tilikausirow["tilikausi_loppu"])."</font><hr>";
		}
		else {
			echo "<font class='error'>".t("Tuntematon tilikausi")."</font>";
			exit;
		}
		
		$lisa = "";
		$wher = "";

		if ($alvk != "") {						
			$alvy = (int) substr($alvk, 0, 4);
			$alvm = (int) substr($alvk, 4, 2);
			$alvd = date("d", mktime(0, 0, 0, $alvm+1, 0, $alvy));
			
			echo "<font class='message'>".t("Vain kuukausi").": $alvy - ".$MONTH_ARRAY[$alvm]."</font><br>";
			
			$lisa = " sum(if(tapvm>='$alvy-$alvm-01' and tapvm<='$alvy-$alvm-$alvd', 1, 0)) Vientejä, sum(if(tapvm>='$alvy-$alvm-01' and tapvm<='$alvy-$alvm-$alvd', summa, 0)) Saldo";
			$wher = " and tapvm <= '$alvy-$alvm-$alvd'";						
		}
		else {
			$lisa	= " count(*) Vientejä, sum(summa) Saldo";
		}
		
		if ($tilino != '') {
			$tilinolisa1 = "tapvm,";
			$tilinolisa2 = " and tiliointi.tilino = '$tilino' ";
		}
	    else {
			$tilinolisa1 = "";
			$tilinolisa2 = "";
		}

		$query = "	SELECT $tilinolisa1 tiliointi.tilino, nimi, $lisa, sum(summa) Kumulatiivinen
					FROM tiliointi
					LEFT JOIN tili ON tili.yhtio = '$kukarow[yhtio]' and tiliointi.tilino = tili.tilino
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					and tiliointi.korjattu=''
					and tapvm >= '$tilikausirow[tilikausi_alku]'
					and tapvm <= '$tilikausirow[tilikausi_loppu]'
					$wher
					$tilinolisa2
					GROUP BY $tilinolisa1 tiliointi.tilino
					ORDER BY $tilinolisa1 tiliointi.tilino";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";

		if ($tilino != '') {
			echo "<th>".t("Pvm")."</font></th>";	
		}
		
		echo "<th>".t("Tili")."</font></th>";
		echo "<th>".t("Nimi")."</font></th>";
		echo "<th>".t("Vientejä")."</font></th>";
		echo "<th>".t("Saldo")." ".$MONTH_ARRAY[($alvk*1)]."</font></th>";
		echo "<th>".t("Kumulatiivinen")."</font></th>";
		
		if(isset($workbook)) {
			$iiiiii = 0;
			
			if ($tilino != '') {
				$worksheet->write($excelrivi, $iiiiii, t("Pvm"), $format_bold);
				$iiiiii++;
			}
			
			$worksheet->write($excelrivi, $iiiiii, t("Tili"), $format_bold);
			$iiiiii++;
			$worksheet->write($excelrivi, $iiiiii, t("Nimi"), $format_bold);
			$iiiiii++;
			$worksheet->write($excelrivi, $iiiiii, t("Vientejä"), $format_bold);
			$iiiiii++;
			$worksheet->write($excelrivi, $iiiiii, t("Saldo")." ".$MONTH_ARRAY[($alvk*1)], $format_bold);
			$iiiiii++;
			$worksheet->write($excelrivi, $iiiiii, t("Kumulatiivinen"), $format_bold);
			$iiiiii++;
			$excelrivi++;
		}

		echo "</tr>";

		$summa	= 0;
		$summa2 = 0;
		$summa3 = 0;

		while ($trow=mysql_fetch_array ($result)) {
			
			$summa  += $trow['Kumulatiivinen'];
			$summa2 += $trow['Saldo'];
			
			if ($tilino == '' or $trow['Vientejä'] > 0) {
				echo "<tr>";
			
				if ($tilino != '') {
					echo "<td><a href='raportit.php?toim=paakirja&tee=K&tili=$trow[tilino]&alvv=".substr($trow["tapvm"], 0,4)."&alvk=".substr($trow["tapvm"],5,2)."&alvp=".substr($trow["tapvm"],-2)."'>".tv1dateconv($trow["tapvm"])."</a></td>";
				}
			
				echo "<td><a href='raportit.php?toim=paakirja&tee=K&tili=$trow[tilino]&tkausi=$tkausi'>$trow[tilino]</a></td>";				
				echo "<td>$trow[nimi]</td>";	
				echo "<td>$trow[Vientejä]</td>";
				echo "<td align='right'>$trow[Saldo]</td>";
			
				if ($tilino != '') {
					echo "<td align='right'>$summa</td>";
					$summa3 = $summa;
				}
				else {
					echo "<td align='right'>$trow[Kumulatiivinen]</td>";
					$summa3 = $summa;
				}
			
				if(isset($workbook)) {
					$iiiiii = 0;
				
					if ($tilino != '') {
						$worksheet->write($excelrivi, $iiiiii, $trow["tapvm"]);
						$iiiiii++;
					}

					$worksheet->write($excelrivi, $iiiiii, $trow["tilino"]);
					$iiiiii++;
					$worksheet->write($excelrivi, $iiiiii, $trow["nimi"]);
					$iiiiii++;
					$worksheet->write($excelrivi, $iiiiii, $trow["Vientejä"]);
					$iiiiii++;
					$worksheet->write($excelrivi, $iiiiii, $trow["Saldo"]);
					$iiiiii++;
					$worksheet->write($excelrivi, $iiiiii, $trow["Kumulatiivinen"]);
					$iiiiii++;
					$excelrivi++;
				}
				echo "</tr>";	
			}			
		}
		
		echo "<tr>";
		
		if ($tilino != '') {
			$cspan = 4;
		}
		else {
			$cspan = 3;
		}
		
		echo "<td class='tumma' colspan='$cspan'><strong>".t("Summa").":</strong></td><td align='right' class='tumma'>".round($summa2,2)."</td><td align='right' class='tumma'>".round($summa3,2)."</td></tr>";

				
		echo "</table><br>";
	}
	
	if ($tee == '') { // Näytetään valikko

		echo "<font class='head'>". t("Tilien saldot")."</font><hr>";

		echo "<form action = 'raportit.php' method='post'>
				<input type = 'hidden' name = 'toim' value = 'tilisaldot'>
				<input type = 'hidden' name = 'tee' value = '1'>
				<table>";

		echo "<tr><th>".t("Valitse tilikausi")."</th>";

	 	$query = "	SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY tilikausi_alku desc";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<td><select name='tkausi' onchange='submit();'>";
				
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			
			if (!isset($savrow)) {
				$savrow	= $vrow;
			}
			
			if ($tkausi == $vrow["tunnus"]) {
				$sel 	= "selected";				
				$savrow	= $vrow;
			}
			
			echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"]);
		}
		echo "</select></td></tr>";

		$alku = str_replace("-", "", substr($savrow["tilikausi_alku"], 0, 7));
		$lopp = str_replace("-", "", substr($savrow["tilikausi_loppu"], 0, 7));
				
		echo "<tr><th>".t("Valitse kuukausi")."</th>
				<td><select name='alvk'>
				<option value=''>".t("Koko tilikausi")."</option>";

		for ($a=$alku; $a<=$lopp; $a++) {
			if (substr($a, -2) == "13") {
				$a+=88;				
			}
			
			echo "<option value = '$a'>".substr($a, 0, 4)." - ".$MONTH_ARRAY[(int) substr($a, -2)]."</option>";
		}

	
		echo "	</select>
				</td></tr>
				<tr><th>".t("per päivä tilistä")."</th>
				<td><input type = 'text' name = 'tilino'></td></tr>
				<tr><th>".t("Tee Excel")."</th>
				<td><input type = 'checkbox' name = 'excel'  value = 'YES'></td></tr>
				<tr><th><input type = 'submit' name='ajarappari' value = '".t("Näytä")."'></th><td></td></tr></table></form>";
	}

?>