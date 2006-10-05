<?php
	if ($tee == 'A') { // Näytetään toimittajan laskuja

		$tunnus = (int) $tunnus;

		if (($laji != 'K') and ($laji != 'H') and ($laji != 'M') and ($laji != 'Y')) {
			echo "Fail!";
			exit;
		}

		$query = "SELECT ytunnus, nimi, postitp
			FROM toimi
			WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
		 	echo "<b>".t("Haulla ei löytynyt yhtään toimittajaa")."</b>";
		}

	 	$trow=mysql_fetch_array ($result);
		echo "<b>$trow[ytunnus]</b> $trow[nimi] $trow[postitp]<br><br>";
		
		if ($laji != 'K') {
			$lisa = " and tila = '" . $laji ."'";
			if ($laji=='M'){
				$lisa = " and tila < 'S'";
			}
		}

		$alku += 0;

		$query = "SELECT tapvm, erpcm, summa, valkoodi, ebid, tila, tunnus
				  FROM lasku
				  WHERE yhtio='$kukarow[yhtio]' and liitostunnus = '$tunnus' $lisa
				  		and tila in ('H', 'M', 'P', 'Q', 'Y')  
				  ORDER BY tapvm desc LIMIT $alku, 20";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
		 	echo "<font class='error'>".t("Haulla ei löytynyt yhtään laskua")."</font>";
		}

		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";

		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
				if ($i == 4) {
					if (strlen($trow['ebid']) > 0) {
						$ebid = $trow['ebid'];
						require "inc/ebid.inc";
						echo "<td><a href='$url'>".t("Näytä lasku")."</a></td>";
					}
					else {
					        echo "<td>".t("Paperilasku")."</td>";
					}
				}
				else {
					if ($i == 0) { // Linkki tositteelle
						if ($kukarow['taso'] < 2) {
							echo "<td>$trow[tapvm]</td>";
						}
						else {
							echo "<td>";
							echo "<a href = 'muutosite.php?tee=E&tunnus=$trow[tunnus]'>";
							echo "$trow[$i]</td>";
						}
					}
					else {
						if ($i == 5) { // Laskun tila
							$laskutyyppi = $trow[$i];
							require "inc/laskutyyppi.inc";
							echo "<td>$laskutyyppi</td>";
						}
						else {
							echo "<td>$trow[$i]</td>";
						}
					}
				}
			}
			echo "</tr>";
		}
		echo "</table><br>";
		if ($alku > 0) {
			$siirry = $alku - 20;
			echo "<a href = '$PHP_SELF?toim=toimittajahaku&tee=A&tunnus=$tunnus&alku=$siirry&laji=$laji'>".t("Edelliset")."</a> ";
		}
		else {
			echo "".t("Edelliset")." ";
		}
		$siirry = $alku + 20;
		echo "<a href = '$PHP_SELF?toim=toimittajahaku&tee=A&tunnus=$tunnus&alku=$siirry&laji=$laji'>".t("Seuraavat")."</a> ";
		echo "<br><br>";
		exit;
	}
	
	$lisat = '';
	if ($tee == 'S') { // S = selaussanahaku
		$lisat = "and selaus like '%" . $nimi . "%'";
	}

	if ($tee == 'N') { // N = nimihaku
		$lisat = "and nimi like '%" . $nimi . "%'";
	}

	if ($tee == 'Y') { // Y = yritystunnushaku
		$lisat = "and ytunnus = '$nimi'";
	}
	if ($lisat != '') {
		$query = "SELECT tunnus, ytunnus, nimi, postitp
					FROM toimi
					WHERE yhtio='$kukarow[yhtio]' $lisat
					ORDER BY selaus";

		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 0) {
			echo "<b>".t("Haulla ei löytynyt yhtään toimittajaa")."</b>";
		}
		if (mysql_num_rows($result) > 40) {
			echo "<b>".t("Haulla löytyi liikaa toimittajia. Tarkenna hakua")."</b><br>";
			$tee = '';
		}
		else {
			echo "<table><tr>";
			for ($i = 1; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "<th></th><th></th></tr>";

			while ($trow=mysql_fetch_array ($result)) {
				echo "<form action = 'raportit.php?toim=toimittajahaku' method='post'>
						<tr>
						<input type='hidden' name='tunnus' value='$trow[tunnus]'>
						<input type='hidden' name='tee' value='A'>";
				for ($i=1; $i<mysql_num_fields($result); $i++) {
					echo "<td>$trow[$i]</td>";
				}
				echo "<td><select name = 'laji'>
						<option value = 'K'>".t("Kaikki")."
						<option value = 'H'>".t("Hyväksymättä")."
						<option value = 'M'>".t("Maksamatta")."
						<option value = 'Y'>".t("Maksettu")."
						</select></td>
						<td><input type='Submit' value='".t("Näytä")."'></td>
						</tr></form>";
			}
			echo "</table>";
			exit;
		}
	}
	if ($tee == '') {
		echo "<form name = 'valinta' action = 'raportit.php' method='post'>
				<input type='hidden' name='toim' value='toimittajahaku'>
				<td>".t("Valitse toimittaja")."</td>
				<td><input type = 'text' name = 'nimi'></td>
				<td><select name='tee'><option value = 'N'>".t("Toimittajan nimi")."
				<option value = 'S'>".t("Toimittajan selaussana")."
				<option value = 'Y'>".t("Y-tunnus")."
				</select>
				</td>
				<td><input type = 'submit' value = '".t("Valitse")."'></td>
				</tr></table></form>";
		$formi = 'valinta';
		$kentta = 'nimi';
		require "inc/footer.inc";
		exit;
	}
