<?php

	if ($tee == '') {
			echo "<font class='head'>".t("Arvonlis‰verolaskelma")."</font><hr>";

			echo "<form action = 'raportit.php' method='post'>
					<input type = 'hidden' name = 'toim' value = 'alv'>
					<input type = 'hidden' name = 'tee' value = '1'>
					<table><tr>
					<td>".t("Arvonlis‰laskelma")."</td>
					<td><select name='alvv'>
					";

			for ($i = date("Y"); $i >= date("Y")-4; $i--) {
				if ($i == date("Y")) $sel = "selected";
				else $sel = "";
				echo "<option value='$i' $sel>$i</option>";
			}

			echo "
					</select>
					<select name='alvk'>
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
					<option value = '12'>12</select>
					</td>
					<td>".t("Ruotsimoodi:")."</td>
					<td><input type = 'checkbox' name = 'ruotsi'></td>
					<td><input type = 'submit' value = '".t("N‰yt‰")."'></td>
					</tr></table></form>";
			echo "<form action = 'raportit.php' method='post'>
					<input type = 'hidden' name = 'toim' value = 'alv'>
					<input type = 'hidden' name = 'tee' value = '2'>
					<table><tr>
					<td>".t("Alv-tarkistus")."</td>
					<td><select name='alvv'>
					";

			for ($i = date("Y"); $i >= date("Y")-4; $i--) {
				if ($i == date("Y")) $sel = "selected";
				else $sel = "";
				echo "<option value='$i' $sel>$i</option>";
			}

			echo "
					</select>
					<select name='alvk'>
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
					<option value = '12'>12</select>
					</td>
					<td><input type = 'submit' value = '".t("N‰yt‰")."'></td>
					</tr></table></form>";

			require ("inc/footer.inc");
			exit;
	}

	if ($tee == 1) {

		$lokk = $alvk + 1;
		$lovv = $alvv;

		if ($lokk > 12) {
			$lokk -= 12;
			$lovv++;
		}
		$alloppu = $lovv . "-" . $lokk . "-01";

		echo "<font class='head'>".t("Arvonlis‰verolaskelma kaudelta")." $alvv-$alvk</font><hr>";

		if ($ruotsi != 'on') {

			$query = "	SELECT if(lasku.tila='U','".t("myyntireskontra")."', if(lasku.tila='X','".t("muu")."','".t("ostoreskontra")."')) tyyppi, vero,
						round(sum(tiliointi.summa * (1+vero/100)),2) Bruttosumma,
						round(sum(tiliointi.summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus)
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
						tiliointi.korjattu = '' and
						tiliointi.tapvm >= '$alvv-$alvk-01' and
						tiliointi.tapvm < '$alloppu' and
						tiliointi.vero > 0
						group by tyyppi, vero";
			$result = mysql_query($query) or pupe_error($query);

			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
			}
			echo "</tr>";

			while ($trow=mysql_fetch_array ($result)) {
				echo "<tr>
					<td>$trow[tyyppi]</td>
					<td align = 'right'>$trow[vero]</td>
					<td align = 'right'>$trow[Bruttosumma]</td>
					<td align = 'right'>$trow[Verot]</td>
					<td align = 'right'>$trow[Kpl]</td></tr>";

			}
			echo "</table><br><br>";
		}

		echo t("K‰siviennit")."<br>";

		$query = "	SELECT ltunnus, tiliointi.tapvm, tiliointi.summa, concat_ws(' ',selite, lasku.nimi) selite
					FROM tiliointi
					JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
					tiliointi.korjattu = '' and
					tiliointi.aputunnus = '' and
					tiliointi.tapvm >= '$alvv-$alvk-01' and
					tiliointi.tapvm < '$alloppu' and
					tiliointi.tilino = '$yhtiorow[alv]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		}

		while ($trow = mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				if ($i == 0) {
					if ($kukarow['taso'] < 2) {
						echo "<td>$trow[tapvm]</td>";
					}
					else {
						echo "<td>";
						echo "<a href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]'>";
						echo "$trow[$i]</td>";
					}
				} else {
					echo "<td>$trow[$i]</td>";
				}
			}
			echo "</tr>";
		}
		echo "</table><br><br>";

		if ($ruotsi != 'on') {
			$query = "	SELECT vero, tiliointi.tilino, nimi, round(sum(summa * (1+vero/100)),2) Bruttosumma,
						round(sum(summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
						tiliointi.korjattu = '' and
						tiliointi.tapvm >= '$alvv-$alvk-01' and
						tiliointi.tapvm < '$alloppu' and
						vero > 0
						GROUP BY vero, tiliointi.tilino
						ORDER BY vero, tiliointi.tilino";
		}
		else {
			$query = "	SELECT concat_ws(' ',vero, if(lasku.tila='U','".t("ulos")."','".t("sis‰‰n")."')) vero, tiliointi.tilino, tili.nimi,
						round(sum(tiliointi.summa * (1+vero/100)),2) Bruttosumma,
						round(sum(tiliointi.summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus)
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						tiliointi.korjattu = '' and
						tiliointi.tapvm >= '$alvv-$alvk-01' and
						tiliointi.tapvm < '$alloppu' and
						tiliointi.vero > 0 and
						GROUP BY vero, tiliointi.tilino
						ORDER BY vero, tiliointi.tilino";
		}
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		}
		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i = 0; $i<mysql_num_fields($result); $i++) {
				if ($i == 0) { // Rivin alussa teht‰v‰t jutut
					if ($edvero ==  $trow[$i]) { // Vaihtuiko verokanta?
						$trow[$i] = "";
					}
					else {
						if (isset($edvero)) { // Ei summaa listan alkuun!
							echo "<td></td><td></td><td></td><td></td>
								  <td align = 'right'>$verosum</td>
								  <td align = 'right'>$kplsum</td></tr><tr>";
							$verotot += $verosum;
							$kpltot += $kplsum;
							$verosum = 0;
							$kplsum = 0;
						}
						$edvero = $trow[$i];
					}
				}
				if ($i > 2) {
					echo "<td align = 'right'>";
					if ($i > 4) {
						echo $trow[$i];
					}
					else {
						printf("%.2f", $trow[$i]);
					}
					echo "</td>";
				}
				else {
					echo "<td>$trow[$i]</td>";
				}
			}
			$verosum += $trow['Verot'];
			$kplsum += $trow['Kpl'];
			echo "</tr>";
		}
		$verotot += $verosum;
		$kpltot += $kplsum;
		echo "<td></td><td></td><td></td><td></td>
				<td align = 'right'>$verosum</td>
				<td align = 'right'>$kplsum</td></tr><tr>";
		echo "<td></td><td></td><td></td><td></td>
				<td align = 'right'>$verotot</td>
				<td align = 'right'>$kpltot</td></tr>";
		echo "</table><br>";
	}

	if ($tee == '2') {
		echo "<font class='head'>".t("Alv-tarkistus kaudelta")." $alvv-$alvk</font><hr>";
		echo "".t("Tarkistetaan automaattiviennit")."<br>";

		$query = "	SELECT tunnus, ltunnus, tapvm, summa, selite, round(summa * vero / 100 ,2) Verot
					FROM tiliointi
					WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
					tiliointi.korjattu = '' and
					left(tapvm,7) = '" . $alvv  . "-" . $alvk . "' and
					vero > 0 and
					selite <> 'Myyntikirja'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		}
		// Tilaa errorille
		echo "<th>".t("Virhe")."</th></tr>";

		while ($trow=mysql_fetch_array ($result)) {
			// Etsit‰‰n vastaava automaattitiliˆinti
			$query = "	SELECT summa, ltunnus
						FROM tiliointi
						WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
						tiliointi.korjattu = '' and
						aputunnus = '$trow[0]'";
			$xresult = mysql_query($query) or pupe_error($query);

			$error='';

			if (mysql_num_rows($xresult) != 1) {
				$error = "".t("Vastaavaa automaattivienti‰ ei ole tai niit‰ on liikaa")."";
			}
			else {
				$xrow=mysql_fetch_array ($xresult);

				$heitto = $xrow["summa"] - $trow["Verot"];
				$heitto = abs($heitto);

				if ($heitto > 0.02) {
					$error = " ".t("Vienti heitt‰‰")." $heitto";
				}
			}
			if ($error != '') {
				echo "<tr>";
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					if ($i == 0) {
						if ($kukarow['taso'] < 2) {
							echo "<td>$trow[tapvm]</td>";
						}
						else {
							echo "<td>";
							echo "<a href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]'>";
							echo "$trow[$i]</td>";
						}
					} else {
						echo "<td>$trow[$i]</td>";
					}
				}
				echo "<td>$error</td></tr>";
			}
		}
		echo "</table><br>Done!<br><br>";

		echo "".t("Tarkistetaan ep‰ilytt‰v‰t k‰siviennit")."<br>";
		$query = "	SELECT ltunnus, tapvm, summa, selite
					FROM tiliointi
					WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
					tiliointi.korjattu = '' and
					aputunnus = '' and
					left(tapvm,7) = '" . $alvv  . "-" . $alvk . "' and
					selite != 'Myyntikirja' and
					tilino = '$yhtiorow[alv]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		}

		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				if ($i == 0) {
					if ($kukarow['taso'] < 2) {
						echo "<td>$trow[tapvm]</td>";
					}
					else {
						echo "<td>";
						echo "<a href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]'>";
						echo "$trow[$i]</td>";
					}
				} else {
					echo "<td>$trow[$i]</td>";
				}
			}
			echo "</tr>";
		}
		echo "</table><br>Done!<br><br>";
	}
?>
