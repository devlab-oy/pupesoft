<?php
	echo "<font class='head'>".t("Maksuvalmius")."</font><hr>";
	
	$sel1 = $sel2 = $sel3 = $sel4 = "";
	
	if ($aika == 'pv') {
		$sel1 = 'SELECTED';
	}
	if ($aika == 'vi') {
		$sel2 = "SELECTED";
	}
	if ($aika == 'kk') {
		$sel3 = "SELECTED";
	}
	
	if ($konserni != '') {
		$sel4 = "CHECKED";
	}
	
	echo "<form action = 'raportit.php' method='post'>
			<table><tr>
			<td>
			<input type = 'hidden' name = 'tee' value = '1'>
			<input type = 'hidden' name = 'toim' value = 'maksuvalmius'>
			".t("Maksuvalmius")."</td><td>
			<select name='aika'>
                     <option value = 'pv' $sel1>".t("Päivä")."
                     <option value = 'vi' $sel2>".t("Viikko")."
                     <option value = 'kk' $sel3>".t("Kuukausi")."
			</select></td>
			<td>".t("Konserni")."</td>
			<td><input type = 'checkbox' name = 'konserni' $sel4></td>
			<td><input type = 'submit' value = '".t("Näytä")."'></td>
			</tr></table></form><br>";

	if ($aika != '') {

		// Tehdään alkusiivous!
		$query = "	SELECT konserni
					FROM yhtio
					WHERE yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	
		if (mysql_num_rows($result) == 1) {
			$trow = mysql_fetch_array($result);
			//echo "Konserni on '$trow[konserni]'<br>";
		}
		else {
			echo t("Yhtiöitä löytyi monta tai ei lainkaan! Virhe!")."";
			exit;
		}

		// Poistetaan vanhat tapahtumat
		$query = "	DELETE from maksu
					WHERE (konserni='$trow[konserni]' or yhtio='$kukarow[yhtio]') and tyyppi != 'MU'";
		$result = mysql_query($query) or pupe_error($query);

		// Lisätään uudet
		// Onko meillä konserninäkökulma??
		if ($konserni == 'on') {
			$query = "	SELECT yhtio, konserni, nimi
						FROM yhtio
						WHERE konserni = '$trow[konserni]' and konserni != ''";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) < 2) {
				echo t("Pyysit konserninäkökulmaa, mutta yritys ei ole konsernin osa").".<br>";
				exit;
			}
			else {
				echo "<table><tr><th>".t("Konserniyritykset").":</th></tr>";
				
				while ($yrow = mysql_fetch_array ($result)) {
					echo "<tr><td>$yrow[nimi]</td></tr>";
				}
				
				echo "</table><br>";
				
				mysql_data_seek($result, 2);
			}

			while ($yrow = mysql_fetch_array ($result)) {
				//Myyntireskontra
				$query = "SELECT if(kapvm > now(), kapvm,erpcm) olmapvm, sum(summa) summa, count(*)
							FROM lasku
							WHERE yhtio = '$yrow[yhtio]' and lasku.tila = 'U' and mapvm='0000-00-00'
							GROUP BY 1";
				$yresult = mysql_query($query) or pupe_error($query);

				while ($trow=mysql_fetch_array ($yresult)) {
					$query = "INSERT into maksu values (
								'$yrow[yhtio]',
								'$yrow[konserni]',
								'$kukarow[kuka]',
								'$trow[olmapvm]',
								'MY',
								'$trow[summa]',
								'Ostolaskuja',
								'',
								'')";
					$xresult = mysql_query($query) or pupe_error($query);
				}

				//Ostoreskontra
				$query = "SELECT olmapvm, -1 * sum(summa * valuu.kurssi) summa, count(*)
							FROM lasku, valuu, yhtio
							WHERE lasku.yhtio = '$yrow[yhtio]' and valuu.yhtio = '$yrow[yhtio]' and
									lasku.valkoodi = valuu.nimi and lasku.tila in ('H', 'M', 'P','Q')
							GROUP BY olmapvm";
				$yresult = mysql_query($query) or pupe_error($query);

				while ($trow=mysql_fetch_array ($yresult)) {
					$query = "INSERT into maksu values (
								'$yrow[yhtio]',
								'$yrow[konserni]',
								'$kukarow[kuka]',
								'$trow[olmapvm]',
								'OS',
								'$trow[summa]',
								'Ostolaskuja',
								'',
								'')";
					$xresult = mysql_query($query) or pupe_error($query);
				}
			}
		}
		else {
			$inyhtio = $kukarow['yhtio'];
			$inkonserni = "";
		
			//Myyntireskontra
			$query = "SELECT if(kapvm > now(), kapvm, erpcm) olmapvm, sum(summa) summa, count(*)
						FROM lasku
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'U'
							and mapvm='0000-00-00'
						GROUP BY 1";

			$result = mysql_query($query) or pupe_error($query);

			//echo "Laskuja '". mysql_num_rows($result) . "'<br>";

			while ($trow=mysql_fetch_array ($result)) {
				$query = "INSERT into maksu values (
							'$kukarow[yhtio]',
							 '',
							'$kukarow[kuka]',
							'$trow[olmapvm]',
							'MY',
							'$trow[summa]',
							'Ostolaskuja',
							'',
							'')";
				$xresult = mysql_query($query) or pupe_error($query);
			}

			//Ostoreskontra
			$query = "SELECT olmapvm, -1 * sum(summa * valuu.kurssi) summa, count(*)
						FROM lasku, valuu
						WHERE lasku.yhtio = '$kukarow[yhtio]' and valuu.yhtio = '$kukarow[yhtio]' and
					      lasku.valkoodi = valuu.nimi and lasku.tila in ('H', 'M', 'P', 'Q')
						GROUP BY olmapvm";

			$result = mysql_query($query) or pupe_error($query);

			//echo "Laskuja '". mysql_num_rows($result) . "'<br>";
			while ($trow=mysql_fetch_array ($result)) {
				$query = "INSERT into maksu values (
							'$kukarow[yhtio]',
							 '',
							'$kukarow[kuka]',
							'$trow[olmapvm]',
							'OS',
							'$trow[summa]',
							'Ostolaskuja',
							'',
							'')";
				$xresult = mysql_query($query) or pupe_error($query);
			}
		}

		if ($aika == 'pv') {
			$tapa = 'tapvm';
		}
		if ($aika == 'vi') {
			$tapa = "YEARWEEK(tapvm,1)";
		}
		if ($aika == 'kk') {
			$tapa = "left(tapvm,7)";;
		}
		$query = "	SELECT $tapa Aika, 
					sum(if(tyyppi='MY',summa,0)) Myyntires,
					sum(if(tyyppi='OS',summa,0)) Ostores,
					sum(if(tyyppi='MU',-1 * summa,0)) Muu,
					sum(if(tyyppi='MU',-1 * summa,summa)) Yhteensa
					FROM maksu
					WHERE yhtio = '$kukarow[yhtio]' and maksettu <> 1
					GROUP BY Aika
			        ORDER BY Aika";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		echo "<th>".t("Jakso")."</th>";
		echo "<th>".t("Aika")."</th>";
		echo "<th>".t("Myyntireskontra")."</th>";
		echo "<th>".t("Ostoreskontra")."</th>";
		echo "<th>".t("Muu")."</th>";
		echo "<th>".t("Yhteensä")."</th>";
		echo "<th>".t("Kumulatiivinen")."</th>";
		echo "</tr>";

		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
			
			if ($aika == 'pv') {
				echo "<td>".t("Päivä").": ";
				echo "<td style='text-align: right;'>".tv1dateconv($trow["Aika"])."</td>";
			}
			if ($aika == 'vi') {
				echo "<td>".t("Viikko").": ";
				echo "<td>".substr($trow["Aika"], 4, 2)." - ".substr($trow["Aika"], 0, 4)."</td>";
			}
			if ($aika == 'kk') {
				echo "<td>".t("Kuukausi").": ";
				echo "<td>".substr($trow["Aika"], 5, 2)." - ".substr($trow["Aika"], 0, 4)."</td>";
			}
			
			echo "<td style='text-align: right;'>$trow[Myyntires]</td>";
			
			if ($aika == 'pv') {
				echo "<td style='text-align: right;'><a href = raportit.php?toim=laskuhaku&tee=M&pvm=$trow[Aika]>$trow[Ostores]</td>";
			}
			if ($aika == 'vi') {
				echo "<td style='text-align: right;'>$trow[Ostores]</td>";
			}
			if ($aika == 'kk') {
				echo "<td style='text-align: right;'>$trow[Ostores]</td>";
			}

			echo "<td style='text-align: right;'>$trow[Muu]</td>";
			echo "<td style='text-align: right;'>$trow[Yhteensa]</td>";
			echo "<td style='text-align: right;'>".sprintf("%.2f", $kumulat)."</td></tr>";
		
			$kumulat += $trow['Yhteensa'];
		}
		echo "</table>";
	}
?>
