<?php

	echo "<font class='head'>".t("Arvonlisäverolaskelma")."</font><hr>";

	echo "<form action = 'raportit.php' method='post'>
			<input type = 'hidden' name = 'toim' value = 'alv'>
			<input type = 'hidden' name = 'tee' value = '1'>";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Arvonlisälaskelma")."</th>";
	echo "<td><select name='alvv'>";

	for ($i = date("Y"); $i >= date("Y")-4; $i--) {
		if (isset($alvv) and $alvv == $i) {
			$sel = "selected";
		}
		elseif ($i == date("Y")) {
			$sel = "selected";
		}
		else {
			$sel = "";
		}
		echo "<option value='$i' $sel>$i</option>";
	}

	echo "</select></td>";

	$alvk_sel = array();
	$alvk_sel[$alvk] = "selected";

	echo "<td><select name='alvk'>
			<option value = '01' $alvk_sel[01]>01</option>
			<option value = '02' $alvk_sel[02]>02</option>
			<option value = '03' $alvk_sel[03]>03</option>
			<option value = '04' $alvk_sel[04]>04</option>
			<option value = '05' $alvk_sel[05]>05</option>
			<option value = '06' $alvk_sel[06]>06</option>
			<option value = '07' $alvk_sel[07]>07</option>
			<option value = '08' $alvk_sel[08]>08</option>
			<option value = '09' $alvk_sel[09]>09</option>
			<option value = '10' $alvk_sel[10]>10</option>
			<option value = '11' $alvk_sel[11]>11</option>
			<option value = '12' $alvk_sel[12]>12</option>
			</select></td>";

	echo "<td>".t("Ruotsimoodi:")."</td>";
	echo "<td><input type = 'checkbox' name = 'ruotsi'></td>";
	echo "<td class='back'><input type = 'submit' value = '".t("Näytä")."'></td>";

	echo "</tr>";
	echo "</form>";

	echo "<form action = 'raportit.php' method='post'>
			<input type = 'hidden' name = 'toim' value = 'alv'>
			<input type = 'hidden' name = 'tee' value = '2'>";

	echo "<tr>";
	echo "<th>".t("Alv-tarkistus")."</th>";

	echo "<td><select name='alvv'>";

	for ($i = date("Y"); $i >= date("Y")-4; $i--) {
		if (isset($alvv) and $alvv == $i) {
			$sel = "selected";
		}
		elseif ($i == date("Y")) {
			$sel = "selected";
		}
		else {
			$sel = "";
		}
		echo "<option value='$i' $sel>$i</option>";
	}

	echo "</select></td>";

	$alvk_sel = array();
	$alvk_sel[$alvk] = "selected";

	echo "<td><select name='alvk'>
			<option value = '01' $alvk_sel[01]>01</option>
			<option value = '02' $alvk_sel[02]>02</option>
			<option value = '03' $alvk_sel[03]>03</option>
			<option value = '04' $alvk_sel[04]>04</option>
			<option value = '05' $alvk_sel[05]>05</option>
			<option value = '06' $alvk_sel[06]>06</option>
			<option value = '07' $alvk_sel[07]>07</option>
			<option value = '08' $alvk_sel[08]>08</option>
			<option value = '09' $alvk_sel[09]>09</option>
			<option value = '10' $alvk_sel[10]>10</option>
			<option value = '11' $alvk_sel[11]>11</option>
			<option value = '12' $alvk_sel[12]>12</option>
			</select></td>";

	echo "<td class='back'><input type = 'submit' value = '".t("Näytä")."'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";

	echo "<br>";

	if ($tee == '1') {

		$alkupvm  = date("Y-m-d", mktime(0, 0, 0, $alvk,   1, $alvv));
		$loppupvm = date("Y-m-d", mktime(0, 0, 0, $alvk+1, 0, $alvv));

		echo "<br><font class='head'>".t("Arvonlisäverolaskelma kaudelta")." $alvv-$alvk</font><hr>";
/*
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

						(SELECT '' Maa, concat('".t("yhteisömyynnit")."',' ','".t("tili")."',': ',tiliointi.tilino,' ',tili.nimi) Tyyppi, Vero,
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

						(SELECT '' Maa, concat('".t("yhteisöostot")."',' ','".t("tili")."',': ',tiliointi.tilino,' ',tili.nimi) Tyyppi, Vero,
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
*/
		// Katotaan voisiko meillä olla tässä joku toinen ALV tili
		// tutkitaan ollaanko jossain toimipaikassa alv-rekisteröity ja oteteaan niiden alv tilit
		$query = "	SELECT ifnull(group_concat(concat(\"'\",toim_alv,\"'\") SEPARATOR ','), '') toim_alv,
					ifnull(group_concat(toim_alv SEPARATOR ' '), '') alv_output
					FROM yhtion_toimipaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and maa != ''
					and vat_numero != ''
					and toim_alv != ''";
		$result = mysql_query($query) or pupe_error($query);
		$tilitrow = mysql_fetch_array($result);

		echo "<br><font class='head'>".t("Käsiviennit")." $yhtiorow[alv] $tilitrow[alv_output]</font><br><br>";

		$alv_tilit = "'$yhtiorow[alv]'";

		if ($tilitrow["toim_alv"] != "") {
			$alv_tilit = $tilitrow["toim_alv"]."'$yhtiorow[alv]'";
		}

		$query = "	SELECT ltunnus, tiliointi.tilino, tiliointi.tapvm, tiliointi.summa, concat_ws(' ',selite, lasku.nimi) selite
					FROM tiliointi
					JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
					tiliointi.korjattu = '' and
					tiliointi.aputunnus = 0 and
					tiliointi.tapvm >= '$alkupvm' and
					tiliointi.tapvm <= '$loppupvm' and
					tiliointi.tilino in ($alv_tilit)
					ORDER BY tilino, tapvm";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("tapvm")."</th>";
		echo "<th>".t("tilino")."</th>";
		echo "<th>".t("summa")."</th>";
		echo "<th>".t("selite")."</th>";
		echo "</tr>";

		$kasiviennit_summa = 0;
		$kasiviennit_kpl = 0;

		while ($trow = mysql_fetch_array ($result)) {
			echo "<tr>";

			if ($kukarow['taso'] < 2) {
				echo "<td nowrap valign='top'>$trow[tapvm]</td>";
			}
			else {
				echo "<td nowrap valign='top'><a href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]'>$trow[tapvm]</td>";
			}

			echo "<td nowrap valign='top'>$trow[tilino]</td>";
			echo "<td nowrap align='right' valign='top'>$trow[summa]</td>";
			echo "<td valign='top'>$trow[selite]</td>";
			echo "</tr>";

			$kasiviennit_summa += $trow["summa"];
			$kasiviennit_kpl++;
		}

		echo "<tr><td colspan='2' align='right'>".t("Yhteensä")."</td><td align='right'>$kasiviennit_summa</td><td></td></tr>";

		echo "</table><br>";

		echo "<font class='head'>".t("Automaattiviennit")."</font><br><br>";

		if ($ruotsi != 'on') {
			$verolisa = " tiliointi.vero ";
		}
		else {
			$verolisa = " concat_ws(' ',vero, if(lasku.tila='U','".t("ulos")."','".t("sisään")."')) vero ";
		}

		$query = "	SELECT if(lasku.maa='', '$yhtiorow[maa]', lasku.maa) Maa,
					if(lasku.valkoodi='', '$yhtiorow[valkoodi]', lasku.valkoodi) Valuutta,
					$verolisa,
					tiliointi.tilino,
					tili.nimi,
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

		$verosum = 0;
		$kplsum  = 0;
		$verotot = 0;
		$kpltot  = 0;

		while ($trow = mysql_fetch_array ($result)) {

			if (isset($edvero) and ($edvero != $trow["vero"] or $edmaa != $trow["Maa"])) { // Vaihtuiko verokanta?
				echo "<tr>
						<td colspan='6' align='right'>".t("Yhteensä").":</td>
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

		echo "<tr><td colspan='6' align='right'>".t("Yhteensä").":</td><td align = 'right'>".sprintf('%.2f', $verosum)."</td><td colspan='2'></td><td align = 'right'>$kplsum</td></tr>";
		echo "<tr><td colspan='6' align='right'>".t("Verokannat yhteensä").":</td><td align = 'right'>".sprintf('%.2f', $verotot)."</td><td colspan='2'></td><td align = 'right'>$kpltot</td></tr>";

		$verotot += $kasiviennit_summa;
		$kpltot  += $kasiviennit_kpl;

		echo "<tr><td colspan='6' align='right'>".t("Käsiviennit").":</td><td align = 'right'>".sprintf('%.2f', $kasiviennit_summa)."</td><td colspan='2'></td><td align = 'right'>$kasiviennit_kpl</td></tr>";
		echo "<tr><td colspan='6' align='right'>".t("Kaikki yhteensä").":</td><td align = 'right'>".sprintf('%.2f', $verotot)."</td><td colspan='2'></td><td align = 'right'>$kpltot</td></tr>";
		echo "</table><br>";
	}

	if ($tee == '2') {

		echo "<br><font class='head'>".t("Alv-tarkistus kaudelta")." $alvv-$alvk</font><hr>";

		$alkupvm  = date("Y-m-d", mktime(0, 0, 0, $alvk,   1, $alvv));
		$loppupvm = date("Y-m-d", mktime(0, 0, 0, $alvk+1, 0, $alvv));

		echo "<br><font class='head'>".t("Tarkistetaan automaattiviennit")."</font><br><br>";

		$query = "	SELECT tunnus, ltunnus, tapvm, summa, selite, round(summa * vero / 100 ,2) verot
					FROM tiliointi USE INDEX (yhtio_tapvm_tilino)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					AND tiliointi.tapvm >= '$alkupvm'
					AND tiliointi.tapvm <= '$loppupvm'
					AND tiliointi.korjattu = ''
					AND vero > 0";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("tapvm")."</th>";
		echo "<th>".t("summa")."</th>";
		echo "<th>".t("selite")."</th>";
		echo "<th>".t("verot")."</th>";
		echo "<th>".t("virhe")."</th>";
		echo "</tr>";

		$errorkpl = 0;
		
		while ($trow = mysql_fetch_array ($result)) {

			// Etsitään vastaava automaattitiliöinti
			$query = "	SELECT summa, ltunnus
						FROM tiliointi USE INDEX (aputunnus_index)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						and tiliointi.korjattu = ''
						and aputunnus = '$trow[tunnus]'";
			$xresult = mysql_query($query) or pupe_error($query);

			$error = '';

			if (mysql_num_rows($xresult) == 1) {
				$xrow = mysql_fetch_array($xresult);
				$heitto = $xrow["summa"] - $trow["verot"];
				$heitto = abs($heitto);
				if ($heitto > 0.02) {
					$error = t("Vienti heittää")." $heitto";
				}
			}
			else {
				$error = t("Vastaavaa automaattivientiä ei ole tai niitä on liikaa")."!";
			}

			if ($error != '') {
				$errorkpl++;
				echo "<tr>";
				if ($kukarow['taso'] < 2) {
					echo "<td nowrap valign='top'>$trow[tapvm]</td>";
				}
				else {
					echo "<td nowrap valign='top'><a href='muutosite.php?tee=E&tunnus=$trow[ltunnus]'>$trow[tapvm]</td>";
				}
				echo "<td nowrap align='right' valign='top'>$trow[summa]</td>";
				echo "<td valign='top'>$trow[selite]</td>";
				echo "<td valign='top'>$trow[verot]</td>";
				echo "<td valign='top'>$error</td>";
				echo "</tr>";
			}
		}

		if ($errorkpl == 0) {
			echo "<tr>";
			echo "<td colspan='5'>".t("Ei virheellisiä automaattivientejä")."!</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
?>