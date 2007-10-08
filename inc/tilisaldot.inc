<?php
	if ($tee == '1') {
		if ($tilino != '') {
			if ($alvk == 0) {
				echo "<font class='error'>".t("Osaan laskea päiväsaldot vain tietyltä kaudelta")."</font><br>";
				$tee='';
			}
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
		$lisa='';
		$tkausi = (int) $tkausi;
		if ($tkausi != 0) {
			 $query = "	SELECT tilikausi_alku, tilikausi_loppu
						FROM tilikaudet
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = $tkausi";
			$vresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($vresult) == 1) {
				$tilikausirow=mysql_fetch_array($vresult);
				$lisa = "tapvm >= '$tilikausirow[tilikausi_alku]' and tapvm <= '$tilikausirow[tilikausi_loppu]'";
				echo "<font class='head'>".t("Tilien saldot tilikaudelta")."  $tilikausirow[tilikausi_alku] - $tilikausirow[tilikausi_loppu]</font><hr>";
			}
			else {
				echo "<font class='error'>Tuntematon tilikausi</font>";
				exit;
			} 
		}
		else {
			if ($kumulat == 'on') {
			
				$alkupvm = $yhtiorow['tilikausi_alku'];
				
				if ($alvk != 12) $loppupvm = substr($yhtiorow['tilikausi_alku'],0,5) . sprintf("%02d",$alvk+1) . "-01";
				else $loppupvm = substr($yhtiorow['tilikausi_alku'],0,4) + 1 . "-01-01";
				
				if ($loppupvm <= $alkupvm)  //Kausi on tilikauden "etupuoella"
					$loppupvm = substr($loppupvm,0,4) + 1 . substr($loppupvm,4,6);
				
				echo "<font class='head'>".t("Tilien saldot tilikauden alusta")." $alkupvm - $loppupvm</font><hr>";
				
				$lisa = "tapvm >= '$alkupvm' and tapvm < '$loppupvm'";
			}
			else {
				$alkupvm = substr($yhtiorow['tilikausi_alku'],0,5) . sprintf("%02d",$alvk) . "-01";
				
				if ($alvk != 12) $loppupvm = substr($yhtiorow['tilikausi_alku'],0,5) . sprintf("%02d",$alvk+1) . "-01";
				else $loppupvm = substr($yhtiorow['tilikausi_alku'],0,5) + 1 . "01-01";
				
				if ($alkupvm < $yhtiorow['tilikausi_alku']) { //Kausi on tilikauden "etupuoella"
					$alkupvm = substr($alkupvm,0,4) + 1 . substr($alkupvm,4,6);
					$loppupvm = substr($loppupvm,0,4) + 1 . substr($loppupvm,4,6);
				}
				echo "<font class='head'>". t("Tilien saldot kaudelta") . " ". substr($alkupvm,0,7) ."</font><hr>";
				
				$lisa = "tapvm >= '$alkupvm' and tapvm < '$loppupvm'";
			}
		}
		if ($tilino!='') {
			$query = "	SELECT tapvm, tiliointi.tilino, nimi, count(*) Vientejä, sum(summa) Saldo
						FROM tiliointi
						LEFT JOIN tili ON tili.yhtio = '$kukarow[yhtio]' and tiliointi.tilino = tili.tilino
						WHERE tiliointi.yhtio = '$kukarow[yhtio]' 
						and tiliointi.korjattu='' 
						and tiliointi.tilino='$tilino' 
						and $lisa
						GROUP BY tapvm, tiliointi.tilino
						ORDER BY tapvm";
		}
	    else {
			$query = "	SELECT tiliointi.tilino, nimi, count(*) Vientejä, sum(summa) Saldo
						FROM tiliointi
						LEFT JOIN tili ON tili.yhtio = '$kukarow[yhtio]' and tiliointi.tilino = tili.tilino
						WHERE tiliointi.yhtio = '$kukarow[yhtio]' 
						and tiliointi.korjattu='' 
						and $lisa
						GROUP BY tiliointi.tilino
						ORDER BY tiliointi.tilino";
		}
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		
		$iiiiii = 0;
		
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) . "</font></th>";
			
			if(isset($workbook)) {
				$worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
			}
			
			$iiiiii++;
		}
		
		if (($tilino != '') and ($kumulat == 'on')) {
			echo "<th>".t("kumulatiivinen")."</th>";
			
			if(isset($workbook)) {
				$worksheet->write($excelrivi, $iiiiii, ucfirst(t("kumulatiivinen"))), $format_bold);
			}
		}
		
		if(isset($workbook)) {
			$excelrivi++;
		}
		
		echo "</tr>";
		
		$summa	= 0;
		$summa2 = 0;
		
		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
			
			$iiiiii = 0;
			
			for ($i=0; $i<mysql_num_fields($result); $i++) {
				if ($i > 2) {
					echo "<td align = 'right'>";
					printf("%.2f", $trow[$i]);
					echo "</td>";
				}
				elseif($i==0) {
					if ($tilino != 0) 
						echo "<td><a href='raportit.php?toim=paakirja&tee=K&tili=$trow[tilino]&alvv=".substr($trow[$i], 0,4)."&alvk=".substr($trow[$i],5,2)."&alvp=".substr($trow[$i],-2)."'>$trow[$i]</a></td>";
					else
						if ($tkausi == 0)
							if ($kumulat != 'on')
								echo "<td><a href='raportit.php?toim=paakirja&tee=K&tili=$trow[tilino]&alvv=".substr($alkupvm, 0,4)."&alvk=".substr($alkupvm,5,2)."&alvp='>$trow[$i]</a></td>";
							else
								echo "<td>$trow[$i]</td>";
						else
							echo "<td><a href='raportit.php?toim=paakirja&tee=K&tili=$trow[$i]&tkausi=$tkausi'>$trow[$i]</a></td>";
				}
				else {
					echo "<td>$trow[$i]</td>";
				}
				
				if(isset($workbook)) {
					$worksheet->write($excelrivi, $i, $trow[$i]);
				}
				
				$iiiiii++;
			}
			$summa  += $trow['Saldo'];
			$summa2 += $trow['Saldo'];
			
			if (($tilino != '') and ($kumulat == 'on')) {
				echo "<td align = 'right'>$summa</td>";
				
				if(isset($workbook)) {
					$worksheet->write($excelrivi, $iiiiii, $summa);
				}
			}
			
			echo "</tr>";
			
			if(isset($workbook)) {
				$excelrivi++;
			}
		}
		if ($tilino == 0) echo "<tr><td></td><td></td><td></td><td align='right'>".round($summa2,2)."</td>";
		
		echo "</table><br>";
	}
	if ($tee == '') { // Näytetään valikko
		
		echo "<font class='head'>". t("Tilien saldot")."</font><hr>";
		
		echo "<form action = 'raportit.php' method='post'>
				<input type = 'hidden' name = 'toim' value = 'tilisaldot'>
				<input type = 'hidden' name = 'tee' value = '1'>
				<table>";
				
		echo "<tr><th>".t("Koko tilikausi")."</th>";
	 	$query = "SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY tilikausi_alku";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<td><select name='tkausi'><option value='0'>".t("Ei valintaa")."";
		
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow["tunnus"]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[tunnus]' $sel>$vrow[tilikausi_alku] - $vrow[tilikausi_loppu]";
		}
		echo "</select></td></tr>";
		
		echo "<tr><th>".t("Yksittäisen kauden saldo")."</th> 
				<td><select name='alvk'>
				<option value = '01'>01
				<option value = '02'>02
				<option value = '03'>03
				<option value = '04'>04
				<option value = '05'>05
				<option value = '06'>06
				<option value = '07'>07
				<option value = '08'>08
				<option value = '09'>09
				<option value = '10'>10
				<option value = '11'>11
				<option value = '12'>12
				</select>
				</td></tr>
				<tr><th>".t("per päivä tilistä")."</th>
				<td><input type = 'text' name = 'tilino'></td></tr>								
				<tr><th>".t("kumulatiivinen")."</th>
				<td><input type = 'checkbox' name = 'kumulat'></td></tr>
				<tr><th>".t("Tee Excel")."</th>
				<td><input type = 'checkbox' name = 'excel'  value = 'YES'></td></tr>
				<tr><th><input type = 'submit' value = '".t("Näytä")."'></th><td></td></tr></table></form>";
	}

?>