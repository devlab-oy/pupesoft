<?php

	echo "<font class='head'>".t("Arvonlis‰verolaskelma")."</font><hr>";

	echo "<form action = 'raportit.php' method='post'>
			<input type = 'hidden' name = 'toim' value = 'alv'>
			<input type = 'hidden' name = 'tee' value = '1'>";

	echo "<table>";
	echo "<tr>";
	echo "<td>".t("Arvonlis‰laskelma")."</td>";
	echo "<td><select name='alvv'>";

	for ($i = date("Y"); $i >= date("Y")-4; $i--) {
		if ($i == date("Y")) $sel = "selected";
		else $sel = "";
		echo "<option value='$i' $sel>$i</option>";
	}

	echo "</select></td>";

	echo "<td><select name='alvk'>
			<option value = '01'>01</option>
			<option value = '02'>02</option>
			<option value = '03'>03</option>
			<option value = '04'>04</option>
			<option value = '05'>05</option>
			<option value = '06'>06</option>
			<option value = '07'>07</option>
			<option value = '08'>08</option>
			<option value = '09'>09</option>
			<option value = '10'>10</option>
			<option value = '11'>11</option>
			<option value = '12'>12</option>
			</select></td>";

	echo "<td>".t("Ruotsimoodi:")."</td>";
	echo "<td><input type = 'checkbox' name = 'ruotsi'></td>";
	echo "<td><input type = 'submit' value = '".t("N‰yt‰")."'></td>";

	echo "</tr>";
	echo "</table>";
	echo "</form>";

	echo "<form action = 'raportit.php' method='post'>
			<input type = 'hidden' name = 'toim' value = 'alv'>
			<input type = 'hidden' name = 'tee' value = '2'>";

	echo "<table>";
	echo "<tr>";
	echo "<td>".t("Alv-tarkistus")."</td>";

	echo "<td><select name='alvv'>";

	for ($i = date("Y"); $i >= date("Y")-4; $i--) {
		if ($i == date("Y")) $sel = "selected";
		else $sel = "";
		echo "<option value='$i' $sel>$i</option>";
	}

	echo "</select></td>";

	echo "<td><select name='alvk'>
			<option value = '01'>01</option>
			<option value = '02'>02</option>
			<option value = '03'>03</option>
			<option value = '04'>04</option>
			<option value = '05'>05</option>
			<option value = '06'>06</option>
			<option value = '07'>07</option>
			<option value = '08'>08</option>
			<option value = '09'>09</option>
			<option value = '10'>10</option>
			<option value = '11'>11</option>
			<option value = '12'>12</option>
			</select></td>";

	echo "<td><input type = 'submit' value = '".t("N‰yt‰")."'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";

	echo "<br>";

	if ($tee == '1') {

		$alkupvm  = date("Y-m-d", mktime(0, 0, 0, $alvk,   1, $alvv));
		$loppupvm = date("Y-m-d", mktime(0, 0, 0, $alvk+1, 0, $alvv));

		echo "<font class='head'>".t("Arvonlis‰verolaskelma kaudelta")." $alvv-$alvk</font><hr>";

		if ($ruotsi != 'on') {

			// Myynti_eu tilit
			$eu_tilit = "'".$yhtiorow["myynti_eu"]."'";

			$query = "SELECT group_concat(distinct concat(\"'\",tilino_eu,\"'\")) tilino_eu from asiakas where yhtio='$kukarow[yhtio]' and tilino_eu != ''";
			$res = mysql_query($query) or pupe_error($query);
			$rivi1 = mysql_fetch_array($res);

			if ($rivi1["tilino_eu"] != "") {
				$eu_tilit .= ",".$rivi1["tilino_eu"];
			}

			$query = "SELECT group_concat(distinct concat(\"'\",tilino_eu,\"'\")) tilino_eu from tuote where yhtio='$kukarow[yhtio]' and tilino_eu != ''";
			$res = mysql_query($query) or pupe_error($query);
			$rivi2 = mysql_fetch_array($res);

			if ($rivi2["tilino_eu"] != "") {
				$eu_tilit .= ",".$rivi2["tilino_eu"];
			}

			$query = "SELECT group_concat(distinct concat(\"'\",tilino_eu,\"'\")) tilino_eu from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and tilino_eu != ''";
			$res = mysql_query($query) or pupe_error($query);
			$rivi2 = mysql_fetch_array($res);

			if ($rivi2["tilino_eu"] != "") {
				$eu_tilit .= ",".$rivi2["tilino_eu"];
			}

			$query = "SELECT group_concat(distinct concat(\"'\",tilino_eu,\"'\")) tilino_eu from tuotteen_alv where yhtio='$kukarow[yhtio]' and tilino_eu != ''";
			$res = mysql_query($query) or pupe_error($query);
			$rivi2 = mysql_fetch_array($res);

			if ($rivi2["tilino_eu"] != "") {
				$eu_tilit .= ",".$rivi2["tilino_eu"];
			}

			// Osto_eu tilit
			$eu_ostotilit = "'".$yhtiorow["osto_eu"]."'";

			$query = "SELECT group_concat(distinct concat(\"'\",tilino,\"'\")) tilino_eu from toimi where yhtio='$kukarow[yhtio]' and tilino != ''";
			$res = mysql_query($query) or pupe_error($query);
			$rivi2 = mysql_fetch_array($res);

			if ($rivi2["tilino_eu"] != "") {
				$eu_ostotilit .= ",".$rivi2["tilino_eu"];
			}

			$query = "	(SELECT if(lasku.maa='', '$yhtiorow[maa]', lasku.maa) Maa, '".t("myyntireskontra")."' Tyyppi, Vero,
 						round(sum(tiliointi.summa * (1+vero/100)),2) 'Verollinen summa',
						round(sum(tiliointi.summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tila = 'U')
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND tiliointi.tapvm >= '$alkupvm'
						AND tiliointi.tapvm <= '$loppupvm'
						AND tiliointi.vero > 0
						GROUP BY 1, 2, 3)

						UNION

						(SELECT if(lasku.maa='', '$yhtiorow[maa]', lasku.maa) Maa, '".t("muu")."' Tyyppi, Vero,
						round(sum(tiliointi.summa * (1+vero/100)),2) 'Verollinen summa',
						round(sum(tiliointi.summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and lasku.tila = 'X')
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND tiliointi.tapvm >= '$alkupvm'
						AND tiliointi.tapvm <= '$loppupvm'
						AND tiliointi.vero > 0
						GROUP BY 1, 2, 3)

						UNION

						(SELECT if(lasku.maa='', '$yhtiorow[maa]', lasku.maa) Maa, '".t("ostoreskontra")."' Tyyppi, Vero,
						round(sum(tiliointi.summa * (1+vero/100)),2) 'Verollinen summa',
						round(sum(tiliointi.summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and lasku.tila in ('H','Y','M','P','Q'))
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND tiliointi.tapvm >= '$alkupvm'
						AND tiliointi.tapvm <= '$loppupvm'
						AND tiliointi.vero > 0
						GROUP BY 1, 2, 3)

						UNION

						(SELECT '' Maa, concat('".t("yhteisˆmyynnit")."',' ','".t("tili")."',': ',tiliointi.tilino,' ',tili.nimi) Tyyppi, Vero,
						round(sum(tiliointi.summa * (1+vero/100)),2) 'Verollinen summa',
						round(sum(tiliointi.summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and lasku.tila='U' and lasku.vienti = 'E')
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND tiliointi.tilino in ($eu_tilit)
						AND tiliointi.tapvm >= '$alkupvm'
						AND tiliointi.tapvm <= '$loppupvm'
						AND tiliointi.vero = 0
						GROUP BY 1, 2, 3)

						UNION

						(SELECT '' Maa, concat('".t("yhteisˆostot")."',' ','".t("tili")."',': ',tiliointi.tilino,' ',tili.nimi) Tyyppi, Vero,
						round(sum(tiliointi.summa * (1+vero/100)),2) 'Verollinen summa',
						round(sum(tiliointi.summa * vero / 100),2) Verot, count(*) Kpl
						FROM tiliointi
						JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and lasku.tila in ('H','Y','M','P','Q') and vienti = 'F')
						LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						and tiliointi.korjattu = ''
						and tiliointi.tilino in ($eu_ostotilit)
						and tiliointi.tapvm >= '$alkupvm'
						and tiliointi.tapvm <= '$loppupvm'
						and tiliointi.vero = 0
						GROUP BY 1, 2, 3)

						ORDER BY 2, 3, 1";
			$result = mysql_query($query) or pupe_error($query);

			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
			}
			echo "</tr>";

			while ($trow = mysql_fetch_array ($result)) {
				echo "<tr>";
				echo "<td>$trow[Maa]</td>";
				echo "<td>$trow[Tyyppi]</td>";
				echo "<td align='right'>".(float) $trow["Vero"]."%</td>";
				echo "<td align='right'>".$trow["Verollinen summa"]."</td>";
				echo "<td align='right'>$trow[Verot]</td>";
				echo "<td align='right'>$trow[Kpl]</td>";
				echo "</tr>";
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
					tiliointi.tapvm >= '$alkupvm' and
					tiliointi.tapvm <= '$loppupvm' and
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
				}
				else {
					echo "<td>$trow[$i]</td>";
				}
			}
			echo "</tr>";
		}
		echo "</table><br><br>";


		if ($ruotsi != 'on') {
			$verolisa = " tiliointi.vero ";
		}
		else {
			$verolisa = " concat_ws(' ',vero, if(lasku.tila='U','".t("ulos")."','".t("sis‰‰n")."')) vero ";
		}

		$query = "	SELECT if(lasku.maa='', '$yhtiorow[maa]', lasku.maa) Maa,
					if(lasku.valkoodi='', '$yhtiorow[valkoodi]', lasku.valkoodi) Valuutta, $verolisa, tiliointi.tilino, tili.nimi,
					round(sum(tiliointi.summa * (1+tiliointi.vero/100)),2) Bruttosumma,
					round(sum(tiliointi.summa * tiliointi.vero/100),2) Verot,
					round(sum(tiliointi.summa/if(lasku.vienti_kurssi=0,1,lasku.vienti_kurssi) * (1+vero/100)),2) Bruttosumma_valuutassa,
					round(sum(tiliointi.summa/if(lasku.vienti_kurssi=0,1,lasku.vienti_kurssi) * vero/100),2) Verot_valuutassa,
					count(*) Kpl
					FROM tiliointi
					JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus)
					LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio and tiliointi.tilino = tili.tilino)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					and tiliointi.korjattu = ''
					and tiliointi.tapvm >= '$alkupvm'
					and tiliointi.tapvm <= '$loppupvm'
					and tiliointi.vero 	> 0
					GROUP BY 1,2,3,4,5
					ORDER BY 1,2,3,4,5";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";
		echo "<th valign='top'>" . t("Maa") . "</th>";
		echo "<th valign='top'>" . t("Val") . "</th>";
		echo "<th valign='top'>" . t("Vero") . "</th>";
		echo "<th valign='top'>" . t("Tili") . "</th>";
		echo "<th valign='top'>" . t("Nimi") . "</th>";
		echo "<th valign='top'>" . t("Verollinen summa") . "</th>";
		echo "<th valign='top'>" . t("Verot") . "</th>";
		echo "<th valign='top'>" . t("Verollinen summa valuutassa") . "</th>";
		echo "<th valign='top'>" . t("Verot valuutassa") . "</th>";
		echo "<th valign='top'>" . t("Kpl") . "</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_array ($result)) {

			if (isset($edvero) and ($edvero != $trow["vero"] or $edmaa != $trow["Maa"])) { // Vaihtuiko verokanta?
				echo "<tr>
						<td colspan='6' align='right'>".t("Yhteens‰").":</td>
						<td align = 'right'>".sprintf('%.2f', $verosum)."</td>
						<td colspan='2'></td>
						<td align = 'right'>$kplsum</td></tr>";

				$verosum 	= 0.0;
				$kplsum 	= 0.0;
			}

			echo "<tr>";
			echo "<td valign='top'>$trow[Maa]</td>";
			echo "<td valign='top'>$trow[Valuutta]</td>";
			echo "<td valign='top' align='right'>". (float) $trow["vero"]."%</td>";
			echo "<td valign='top'>$trow[tilino]</td>";
			echo "<td valign='top'>$trow[nimi]</td>";
			echo "<td valign='top' align='right' nowrap>$trow[Bruttosumma]</td>";
			echo "<td valign='top' align='right' nowrap>$trow[Verot]</td>";

			if (strtoupper($trow["Maa"]) != strtoupper($yhtiorow["maa"])) {
				echo "<td valign='top' align='right' nowrap>$trow[Bruttosumma_valuutassa]</td>";
				echo "<td valign='top' align='right' nowrap>$trow[Verot_valuutassa]</td>";
			}
			else {
				echo "<td valign='top' align='right'></td>";
				echo "<td valign='top' align='right'></td>";
			}

			echo "<td valign='top' align='right' nowrap>$trow[Kpl]</td>";
			echo "</tr>";

			$verosum += $trow['Verot'];
			$kplsum  += $trow['Kpl'];
			$verotot += $trow['Verot'];
			$kpltot  += $trow['Kpl'];

			$edvero = $trow["vero"];
			$edmaa 	= $trow["Maa"];
		}

		echo "<tr><td colspan='6' align='right'>".t("Yhteens‰").":</td><td align = 'right'>".sprintf('%.2f', $verosum)."</td><td colspan='2'></td><td align = 'right'>$kplsum</td></tr>";
		echo "<tr><td colspan='6' align='right'>".t("Kaikki yhteens‰").":</td><td align = 'right'>".sprintf('%.2f', $verotot)."</td><td colspan='2'></td><td align = 'right'>$kpltot</td></tr>";
		echo "</table><br>";
	}

	if ($tee == '2') {

		echo "<font class='head'>".t("Alv-tarkistus kaudelta")." $alvv-$alvk</font><hr>";

		$alkupvm  = date("Y-m-d", mktime(0, 0, 0, $alvk,   1, $alvv));
		$loppupvm = date("Y-m-d", mktime(0, 0, 0, $alvk+1, 0, $alvv));

		echo "<font class='message'>".t("Tarkistetaan automaattiviennit")."</font><br><br>";

		$query = "	SELECT tunnus, ltunnus, tapvm, summa, selite, round(summa * vero / 100 ,2) verot
					FROM tiliointi USE INDEX (yhtio_tapvm_tilino)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					AND tiliointi.tapvm >= '$alkupvm'
					AND tiliointi.tapvm <= '$loppupvm'
					AND tiliointi.korjattu = ''
					AND vero > 0
					AND selite <> 'Myyntikirja'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("tapvm")."</th>";
		echo "<th>".t("summa")."</th>";
		echo "<th>".t("selite")."</th>";
		echo "<th>".t("verot")."</th>";
		echo "<th>".t("virhe")."</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_array ($result)) {

			// Etsit‰‰n vastaava automaattitiliˆinti
			$query = "	SELECT summa, ltunnus
						FROM tiliointi
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						and tiliointi.korjattu = ''
						and aputunnus = '$trow[0]'";
			$xresult = mysql_query($query) or pupe_error($query);

			$error = '';

			if (mysql_num_rows($xresult) == 1) {
				$xrow = mysql_fetch_array($xresult);
				$heitto = $xrow["summa"] - $trow["verot"];
				$heitto = abs($heitto);
				if ($heitto > 0.02) {
					$error = t("Vienti heitt‰‰")." $heitto";
				}
			}
			else {
				$error = t("Vastaavaa automaattivienti‰ ei ole tai niit‰ on liikaa");
			}

			if ($error != '') {
				echo "<tr>";
				if ($kukarow['taso'] < 2) {
					echo "<td>$trow[tapvm]</td>";
				}
				else {
					echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[ltunnus]'>$trow[tapvm]</a></td>";
				}
				echo "<td>$trow[summa]</td>";
				echo "<td>$trow[selite]</td>";
				echo "<td>$trow[verot]</td>";
				echo "<td>$error</td>";
				echo "</tr>";
			}
		}
		echo "</table>";

		echo "<br><br><font class='message'>".t("Tarkistetaan ep‰ilytt‰v‰t k‰siviennit")."</font><br><br>";

		$query = "	SELECT ltunnus, tapvm, summa, selite
					FROM tiliointi USE INDEX (yhtio_tapvm_tilino)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					AND tiliointi.tapvm >= '$alkupvm'
					AND tiliointi.tapvm <= '$loppupvm'
					AND tiliointi.korjattu = ''
					AND aputunnus = ''
					AND selite != 'Myyntikirja'
					AND tilino = '$yhtiorow[alv]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("tapvm")."</th>";
		echo "<th>".t("summa")."</th>";
		echo "<th>".t("selite")."</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_array($result)) {
			echo "<tr>";
			if ($kukarow['taso'] < 2) {
				echo "<td>$trow[tapvm]</td>";
			}
			else {
				echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[ltunnus]'>$trow[tapvm]</a></td>";
			}
			echo "<td>$trow[summa]</td>";
			echo "<td>$trow[selite]</td>";
			echo "</tr>";
		}

		echo "</table>";

	}

?>
