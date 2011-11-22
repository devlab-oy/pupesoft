<?php

	echo "<font class='head'>".t("Arvonlisäveroraportti")."</font><hr>";

	echo "<form action = 'raportit.php' method='post'>
			<input type = 'hidden' name = 'toim' value = 'alv'>
			<input type = 'hidden' name = 'tee' value = '1'>";

	echo "<br><table>";
	echo "<tr>";
	echo "<th>".t("Valitse kausi").":</th>";

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

	if (!isset($alvk)) $alvk = date("m");
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

	echo "<td>".t("Erottele myynti/muut verokirjaukset").":</td>";

	if ($ruotsi != "") {
		$ched = "CHECKED";
	}
	else {
		$ched = "";
	}

	echo "<td><input type = 'checkbox' name = 'ruotsi' $ched></td>";
	echo "<td class='back'><input type = 'submit' value = '".t("Näytä")."'></td>";

	echo "</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br>";

	if ($tee == '1') {

		$alkupvm  = date("Y-m-d", mktime(0, 0, 0, $alvk,   1, $alvv));
		$loppupvm = date("Y-m-d", mktime(0, 0, 0, $alvk+1, 0, $alvv));

		$query = "	SELECT tiliointi.tunnus, tiliointi.ltunnus, tiliointi.tapvm, tiliointi.summa, tiliointi.selite, round(tiliointi.summa * tiliointi.vero / 100, 2) verot, lasku.tunnus laskutunnus, tiliointi.tilino, tiliointi.vero
					FROM tiliointi USE INDEX (yhtio_tapvm_tilino)
					LEFT JOIN lasku USE INDEX (primary) ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and lasku.tila != 'D')
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					AND tiliointi.tapvm >= '$alkupvm'
					AND tiliointi.tapvm <= '$loppupvm'
					AND tiliointi.korjattu = ''
					AND vero > 0";
		$result = pupe_query($query);

		$errorit = "";

		while ($trow = mysql_fetch_array ($result)) {

			// Etsitään vastaava automaattitiliöinti
			$query = "	SELECT summa, ltunnus
						FROM tiliointi USE INDEX (aputunnus_index)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						AND tiliointi.korjattu = ''
						AND aputunnus = '$trow[tunnus]'";
			$xresult = pupe_query($query);

			$error = '';

			if (mysql_num_rows($xresult) == 1) {
				$xrow = mysql_fetch_array($xresult);
				$heitto = abs(round($xrow["summa"] - $trow["verot"], 2));
				if ($heitto > 0.01) {
					$error = t("Vienti heittää")." $heitto";
				}
			}
			else {
				if (abs($trow['summa']) <= 0.02) continue;
				$error = t("Vastaavaa automaattivientiä ei ole tai niitä on liikaa")."!";
			}

			if ($trow["laskutunnus"] == NULL) {
				$error .= " ".t("Tiliöinnillä ei ole otsikkoa")."! $trow[tunnus]";
			}

			if ($error != '') {
				$errorkpl++;

				$errorit .= "<tr class='aktiivi'>";

				if ($kukarow['taso'] < 2) {
					$errorit .= "<td nowrap valign='top'>$trow[tapvm]</td>";
				}
				else {
					$errorit .= "<td nowrap valign='top'><a href='muutosite.php?tee=E&tunnus=$trow[ltunnus]'>$trow[tapvm]</td>";
				}

				$errorit .= "<td nowrap align='right' valign='top'>$trow[tilino]</td>";
				$errorit .= "<td nowrap align='right' valign='top'>$trow[summa]</td>";
				$errorit .= "<td valign='top'>$trow[selite]</td>";
				$errorit .= "<td valign='top'>$trow[vero]</td>";
				$errorit .= "<td valign='top'>$trow[verot]</td>";
				$errorit .= "<td valign='top'>$error</td>";
				$errorit .= "</tr>";
			}
		}

		// käydään läpi kaikki automaattitiliöinnit
		$query = "	SELECT tiliointi.aputunnus, tiliointi.tunnus, tiliointi.ltunnus, tiliointi.tapvm, tiliointi.summa, tiliointi.selite, round(tiliointi.summa * tiliointi.vero / 100, 2) verot, tiliointi.tilino, tiliointi.vero
					FROM tiliointi USE INDEX (yhtio_tapvm_tilino)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					AND tiliointi.tapvm >= '$alkupvm'
					AND tiliointi.tapvm <= '$loppupvm'
					AND tiliointi.korjattu = ''
					AND tiliointi.aputunnus != 0";
		$result = pupe_query($query);

		while ($trow = mysql_fetch_array ($result)) {

			// Etsitään isätiliointi
			$query = "	SELECT tunnus
						FROM tiliointi USE INDEX (primary)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						and tiliointi.korjattu = ''
						and tunnus = '$trow[aputunnus]'";
			$xresult = pupe_query($query);

			if (mysql_num_rows($xresult) != 1) {
				$errorkpl++;

				$errorit .= "<tr class='aktiivi'>";

				if ($kukarow['taso'] < 2) {
					$errorit .= "<td nowrap valign='top'>$trow[tapvm]</td>";
				}
				else {
					$errorit .= "<td nowrap valign='top'><a href='muutosite.php?tee=E&tunnus=$trow[ltunnus]'>$trow[tapvm]</td>";
				}

				$errorit .= "<td nowrap align='right' valign='top'>$trow[tilino]</td>";
				$errorit .= "<td nowrap align='right' valign='top'>$trow[summa]</td>";
				$errorit .= "<td valign='top'>$trow[selite]</td>";
				$errorit .= "<td valign='top'>$trow[vero]</td>";
				$errorit .= "<td valign='top'>$trow[verot]</td>";
				$errorit .= "<td valign='top'>".t("Automaattitiliöinnin isää ei löydy")."! $trow[tunnus]</td>";
				$errorit .= "</tr>";
			}
		}

		if ($errorit != "") {
			echo "<table>";
			echo "<tr>";
			echo "<th>".t("tapvm")."</th>";
			echo "<th>".t("tilino")."</th>";
			echo "<th>".t("summa")."</th>";
			echo "<th>".t("selite")."</th>";
			echo "<th>".t("alv")."</th>";
			echo "<th>".t("verot")."</th>";
			echo "<th>".t("virhe")."</th>";
			echo "</tr>";
			echo $errorit;
			echo "</table>";
		}

		$alkupvm  = date("Y-m-d", mktime(0, 0, 0, $alvk,   1, $alvv));
		$loppupvm = date("Y-m-d", mktime(0, 0, 0, $alvk+1, 0, $alvv));

		echo "<br><font class='head'>".t("Arvonlisäveroraportti kaudelta")." $alvv-$alvk</font><hr>";

		// Katotaan voisiko meillä olla tässä joku toinen ALV tili
		// tutkitaan ollaanko jossain toimipaikassa alv-rekisteröity ja oteteaan niiden alv tilit
		$query = "	SELECT ifnull(group_concat(concat(\"'\",toim_alv,\"'\") SEPARATOR ','), '') toim_alv,
					ifnull(group_concat(toim_alv SEPARATOR ' '), '') alv_output
					FROM yhtion_toimipaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and maa != ''
					and vat_numero != ''
					and toim_alv != ''";
		$result = pupe_query($query);
		$tilitrow = mysql_fetch_array($result);

		echo "<br><font class='head'>".t("Käsiviennit").": ".t("Tilit")." $yhtiorow[alv] $tilitrow[alv_output]</font><br><br>";

		$alv_tilit = "'$yhtiorow[alv]'";

		if ($tilitrow["toim_alv"] != "") {
			$alv_tilit = $tilitrow["toim_alv"].", '$yhtiorow[alv]'";
		}

		$query = "	SELECT ltunnus, tiliointi.tilino, tiliointi.tapvm, tiliointi.summa, concat_ws(' ',selite, lasku.nimi) selite
					FROM tiliointi USE INDEX (yhtio_tapvm_tilino)
					LEFT JOIN lasku USE INDEX (primary) ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
					tiliointi.korjattu = '' and
					tiliointi.aputunnus = 0 and
					tiliointi.tapvm >= '$alkupvm' and
					tiliointi.tapvm <= '$loppupvm' and
					tiliointi.tilino in ($alv_tilit)
					ORDER BY tilino, tapvm";
		$result = pupe_query($query);

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
			echo "<tr class='aktiivi'>";

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

		echo "<tr><td colspan='2' class='spec' align='right'>".t("Yhteensä").":</td><td align='right' class='spec'>$kasiviennit_summa</td><td class='spec'></td></tr>";

		echo "</table><br>";

		echo "<font class='head'>".t("Automaattiviennit")."</font><br><br>";

		if ($ruotsi != 'on') {
			$verolisa = " tiliointi.vero ";
		}
		else {
			$verolisa = " concat_ws(' ', if(lasku.tila='U','".t("Myynti")."','".t("Muut")."'), vero) vero ";
		}

		$query = "	SELECT if(lasku.maa = '', '$yhtiorow[maa]', lasku.maa) maa,
					if(lasku.valkoodi = '', '$yhtiorow[valkoodi]', lasku.valkoodi) valuutta,
					$verolisa,
					tiliointi.tilino,
					tili.nimi,
					sum(round(tiliointi.summa * (1 + tiliointi.vero / 100), 2)) bruttosumma,
					sum(round(tiliointi.summa * tiliointi.vero / 100, 2)) verot,
					sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * (1 + vero / 100), 2)) bruttosumma_valuutassa,
					sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * vero / 100, 2)) verot_valuutassa,
					count(*) kpl
					FROM tiliointi USE INDEX (yhtio_tapvm_tilino)
					LEFT JOIN lasku USE INDEX (primary) ON (lasku.yhtio = tiliointi.yhtio AND lasku.tunnus = tiliointi.ltunnus)
					LEFT JOIN tili USE INDEX (tili_index) ON (tili.yhtio = tiliointi.yhtio AND tiliointi.tilino = tili.tilino)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					AND tiliointi.korjattu = ''
					AND tiliointi.tapvm >= '$alkupvm'
					AND tiliointi.tapvm <= '$loppupvm'
					AND tiliointi.vero > 0
					GROUP BY maa, valuutta, vero, tilino, nimi
					ORDER BY maa, valuutta, vero, tilino, nimi";
		$result = pupe_query($query);

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

			if (isset($edvero) and ((float) $edvero != (float) $trow["vero"] or $edmaa != $trow["maa"])) { // Vaihtuiko verokanta?
				echo "<tr>
						<td colspan='6' class='spec' align='right'>".t("Yhteensä").":</td>
						<td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td>
						<td colspan='2' class='spec'></td>
						<td align = 'right' class='spec'>$kplsum</td></tr>";

				$verosum 	= 0.0;
				$kplsum 	= 0.0;
			}

			echo "<tr class='aktiivi'>";
			echo "<td valign='top'>$trow[maa]</td>";
			echo "<td valign='top'>$trow[valuutta]</td>";
			echo "<td valign='top' align='right'>".$trow["vero"]."%</td>";
			echo "<td valign='top'>$trow[tilino]</td>";
			echo "<td valign='top'>$trow[nimi]</td>";
			echo "<td valign='top' align='right' nowrap>$trow[bruttosumma]</td>";
			echo "<td valign='top' align='right' nowrap>$trow[verot]</td>";

			if (strtoupper($trow["maa"]) != strtoupper($yhtiorow["maa"])) {
				echo "<td valign='top' align='right' nowrap>$trow[bruttosumma_valuutassa]</td>";
				echo "<td valign='top' align='right' nowrap>$trow[verot_valuutassa]</td>";
			}
			else {
				echo "<td valign='top' align='right'></td>";
				echo "<td valign='top' align='right'></td>";
			}

			echo "<td valign='top' align='right' nowrap>$trow[kpl]</td>";
			echo "</tr>";

			$verosum += $trow['verot'];
			$kplsum  += $trow['kpl'];
			$verotot += $trow['verot'];
			$kpltot  += $trow['kpl'];

			$edvero = $trow["vero"];
			$edmaa 	= $trow["maa"];
		}

		echo "<tr><td colspan='6' align='right' class='spec'>".t("Yhteensä").":</td><td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td><td colspan='2' class='spec'></td><td align = 'right' class='spec'>$kplsum</td></tr>";
		echo "<tr><td colspan='6' align='right' class='spec'>".t("Verokannat yhteensä").":</td><td align = 'right' class='spec'>".sprintf('%.2f', $verotot)."</td><td colspan='2' class='spec'></td><td align = 'right' class='spec'>$kpltot</td></tr>";

		$verotot += $kasiviennit_summa;
		$kpltot  += $kasiviennit_kpl;

		echo "<tr><td colspan='6' align='right' class='spec'>".t("Käsiviennit").":</td><td align = 'right' class='spec'>".sprintf('%.2f', $kasiviennit_summa)."</td><td colspan='2' class='spec'></td><td align = 'right' class='spec'>$kasiviennit_kpl</td></tr>";
		echo "<tr><td colspan='6' align='right' class='spec'>".t("Kaikki yhteensä").":</td><td align = 'right' class='spec'>".sprintf('%.2f', $verotot)."</td><td colspan='2' class='spec'></td><td align = 'right' class='spec'>$kpltot</td></tr>";
		echo "</table><br>";
	}

	if ($tee == '2') {


	}
?>