<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("inc/parametrit.inc");

	echo "<font class='head'>",t("Selaa maksuaineistoja"),"</font><hr>";

	if (!isset($kuka_poimi)) $kuka_poimi = '';
	if (!isset($tunnukset)) $tunnukset = '';
	if (!isset($tee)) $tee = '';

	echo "<table>";
	echo "<form method='post'>";

	echo "<th valign='top'>",t("Alkupvm"),"</th>";
	echo "<td><select name='alkuvv'>";

	$sel = array();
	if (!isset($alkuvv) or $alkuvv == "") $alkuvv = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	$sel[$alkuvv] = "SELECTED";

	for ($i = date("Y"); $i >= date("Y")-4; $i--) {
		echo "<option value='{$i}' {$sel[$i]}>{$i}</option>";
	}

	echo "</select> ";

	$sel = array();
	if (!isset($alkukk) or $alkukk == "") $alkukk = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	$sel[$alkukk] = "SELECTED";

	echo "<select name='alkukk'>";

	for ($i = 1; $i < 13; $i++) {
		$val = $i < 10 ? '0'.$i : $i;
		echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
	}

	echo "</select> ";

	$sel = array();
	if (!isset($alkupp) or $alkupp == "") $alkupp = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	$sel[$alkupp] = "SELECTED";

	echo "<select name='alkupp'>";

	for ($i = 1; $i < 32; $i++) {
		$val = $i < 10 ? '0'.$i : $i;
		echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
	}

	echo "</select></td><td class='back'>&nbsp;</td>";


	echo "<tr>
		<th valign='top'>",t("Loppupvm"),"</th>
		<td><select name='loppuvv'>";

	$sel = array();
	if (!isset($loppuvv) or $loppuvv == "") $loppuvv = date("Y");
	$sel[$loppuvv] = "SELECTED";

	for ($i = date("Y")+1; $i >= date("Y")-4; $i--) {
		echo "<option value='{$i}' {$sel[$i]}>{$i}</option>";
	}

	echo "</select> ";

	$sel = array();
	if (!isset($loppukk) or $loppukk == "") $loppukk = date("m");
	$sel[$loppukk] = "SELECTED";

	echo "<select name='loppukk'>";

	for ($i = 1; $i < 13; $i++) {
		$val = $i < 10 ? '0'.$i : $i;
		echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
	}

	echo "</select> ";

	$sel = array();
	if (!isset($loppupp) or $loppupp == "") $loppupp = date("d", mktime(0, 0, 0, (date("m")+1), 0, date("Y")));
	$sel[$loppupp] = "SELECTED";

	echo "<select name='loppupp'>";

	for ($i = 1; $i < 32; $i++) {
		$val = $i < 10 ? '0'.$i : $i;
		echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
	}

	echo "</select></td><td class='back'>&nbsp;</td></tr>";

	echo "<tr><th>",t("Poimija"),"</th><td><input type='text' name='kuka_poimi' value='{$kuka_poimi}'></td><td class='back'><input type='submit' name='submitbutton' value='",t("Hae"),"'></td></tr>";
	echo "<input type='hidden' name='tee' value=''>";
	echo "<input type='hidden' name='tunnukset' value=''>";

	echo "</form>";
	echo "</table>";

	if ($tee == '') {

		$lisa = '';

		if (trim($kuka_poimi) != '') {
			$lisa .= " and (lasku.maksaja like '%".mysql_real_escape_string((string) $kuka_poimi)."%' or kuka.nimi like '%".mysql_real_escape_string((string) $kuka_poimi)."%') ";
		}

		if ($yhtiorow["pankkitiedostot"] == "E") {
			$query = "	SELECT if(lasku.maa = 'fi', 1, 2) tyyppi,
						lasku.popvm aika,
						ifnull(kuka.nimi, lasku.maksaja) kukanimi,
						lasku.olmapvm,
						CONCAT(yriti.nimi, ' ', yriti.tilino) maksu_tili,
						COUNT(*) kpl,
						GROUP_CONCAT(lasku.tunnus) tunnukset,
						round(sum(if(lasku.alatila = 'K', lasku.summa - lasku.kasumma, lasku.summa) * if(lasku.maksu_kurssi = 0, lasku.vienti_kurssi, lasku.maksu_kurssi)), 2) summa
						FROM lasku
						LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = lasku.maksaja)
						LEFT JOIN yriti ON (yriti.yhtio = lasku.yhtio AND yriti.tunnus = lasku.maksu_tili)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						AND lasku.tila in ('P', 'Q', 'Y')
						AND lasku.popvm >= '$alkuvv-$alkukk-$alkupp 00:00:00'
						and lasku.popvm <= '$loppuvv-$loppukk-$loppupp 23:59:59'
						$lisa
						GROUP BY tyyppi, aika, kukanimi, olmapvm, maksu_tili
						ORDER BY tyyppi ASC, aika DESC";
		}
		else {
			$query = "	SELECT if(lasku.maa = 'fi', 1, 2) tyyppi,
						lasku.popvm aika,
						ifnull(kuka.nimi, lasku.maksaja) kukanimi,
						group_CONCAT(distinct yriti.nimi, ' ', yriti.tilino separator '<br>') maksu_tili,
						COUNT(*) kpl,
						GROUP_CONCAT(lasku.tunnus) tunnukset,
						round(sum(if(lasku.alatila = 'K', lasku.summa - lasku.kasumma, lasku.summa) * if(lasku.maksu_kurssi = 0, lasku.vienti_kurssi, lasku.maksu_kurssi)), 2) summa
						FROM lasku
						LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = lasku.maksaja)
						LEFT JOIN yriti ON (yriti.yhtio = lasku.yhtio AND yriti.tunnus = lasku.maksu_tili)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						AND lasku.tila in ('P', 'Q', 'Y')
						AND lasku.popvm >= '$alkuvv-$alkukk-$alkupp 00:00:00'
						and lasku.popvm <= '$loppuvv-$loppukk-$loppupp 23:59:59'
						$lisa
						GROUP BY tyyppi, aika, kukanimi
						ORDER BY tyyppi ASC, aika DESC";

		}
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table>";
			$tyyppi = '';

			while ($row = mysql_fetch_assoc($result)) {
				if ($tyyppi == '' or $tyyppi != $row['tyyppi']) {

					echo "<tr><td class='back'>&nbsp;</td></tr>";
					echo "<tr><th colspan='6'>";

					if ($row['tyyppi'] == 1) {
						echo t("Kotimaan maksuaineistot");
					}
					else {
						echo t("Ulkomaan maksuaineistot");
					}

					echo "</th></tr>";

					echo "<tr>";
					echo "<th>",t("Maksuaineisto"),"</th>";
					echo "<th>",t("Poimijan nimi"),"</th>";
					echo "<th>",t("Maksutili"),"</th>";
					echo "<th>",t("Laskuja"),"</th>";
					echo "<th>",t("Summa")," {$yhtiorow['valkoodi']}</th>";
					echo "<th></th>";
					echo "</tr>";
				}

				echo "<tr class='aktiivi'>";
				echo "<td>{$row['aika']}</td>";
				echo "<td>{$row['kukanimi']}</td>";
				echo "<td>{$row['maksu_tili']}</td>";
				echo "<td align='right'>{$row['kpl']}</td>";
				echo "<td align='right'>{$row['summa']}</td>";

				echo "<td align='right'>
						<form method='POST'>
						<input type='hidden' name='tee' value='selaa'>
						<input type='hidden' name='alkuvv' value='$alkuvv'>
						<input type='hidden' name='alkukk' value='$alkukk'>
						<input type='hidden' name='alkupp' value='$alkupp'>
						<input type='hidden' name='loppuvv' value='$loppuvv'>
						<input type='hidden' name='loppukk' value='$loppukk'>
						<input type='hidden' name='loppupp' value='$loppupp'>
						<input type='hidden' name='tunnukset' value='{$row['tunnukset']}'>
						<input type='submit' value='".t("Näytä")."'>
						</form>
						</td>";

				echo "</tr>";

				$tyyppi = $row['tyyppi'];
			}

			echo "</table>";
		}
	}

	if ($tee == 'selaa') {

		$lisa = '';

		if ($tunnukset) $lisa .= " and lasku.tunnus in ($tunnukset) ";

		$query = "	SELECT lasku.tunnus,
					lasku.nimi,
					lasku.nimitark,
					lasku.maksaja,
					lasku.maksuaika,
					if(lasku.laskunro = 0, '', lasku.laskunro) laskunro,
					lasku.summa,
					if(lasku.alatila = 'K', lasku.summa - lasku.kasumma, lasku.summa) poimittusumma,
					lasku.valkoodi,
					ifnull(kuka.nimi, lasku.maksaja) kukanimi,
					lasku.popvm,
					lasku.tapvm,
					lasku.olmapvm,
					lasku.mapvm,
					lasku.erpcm,
					yriti.nimi maksu_tili,
					yriti.iban yriti_iban,
					date_format(lasku.popvm, '%d.%m.%y.%H.%i.%s') popvm_dmy,
					round(if(lasku.alatila = 'K', lasku.summa - lasku.kasumma, lasku.summa) * if(lasku.maksu_kurssi = 0, lasku.vienti_kurssi, lasku.maksu_kurssi), 2) poimittusumma_eur,
					round(lasku.summa * if(lasku.maksu_kurssi = 0, lasku.vienti_kurssi, lasku.maksu_kurssi), 2) summa_eur
					FROM lasku
					LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = lasku.maksaja)
					LEFT JOIN yriti ON (yriti.yhtio = lasku.yhtio AND yriti.tunnus = lasku.maksu_tili)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tila in ('P', 'Q', 'Y')
					AND lasku.maksuaika > '0000-00-00 00:00:00'
					$lisa
					ORDER BY lasku.liitostunnus, lasku.tapvm, lasku.summa ASC";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<br />";
			echo "<a href='{$palvelin2}selaa_maksuaineisto.php?&alkuvv={$alkuvv}&alkukk={$alkukk}&alkupp={$alkupp}&loppuvv={$loppuvv}&loppukk={$loppukk}&loppupp={$loppupp}'>&laquo; ",t("Takaisin"),"</a>";
			echo "<br />";
			echo "<br />";

			$row = mysql_fetch_assoc($result);

			echo "<table>";
			echo "<tr class='aktiivi'>";
			echo "<th>",t("Poimija"),"</th>";
			echo "<td>{$row['kukanimi']}</td>";
			echo "</tr>";
			echo "<tr class='aktiivi'>";
			echo "<th>",t("Maksuaineisto"),"</th>";
			echo "<td>";

			if (strtoupper($yhtiorow['maa']) == 'EE' and substr($row['yriti_iban'], 0, 2) == "EE") {
				echo "EESEPA-$kukarow[yhtio]-".$row['popvm_dmy'].".xml";
			}
			else {
				echo "SEPA-$kukarow[yhtio]-".$row['popvm_dmy'].".xml";
			}

			echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<br />";

			mysql_data_seek($result, 0);

			echo "<table>";

			echo "<tr>";
			echo "<th>",t("Nimi"),"</th>";
			echo "<th>",t("Laskunro"),"</th>";
			echo "<th>",t("Tapvm"),"</th>";
			echo "<th>",t("Eräpvm"),"</th>";
			echo "<th>",t("Olmapvm"),"</th>";
			echo "<th>",t("Mapvm"),"</th>";
			echo "<th>",t("Summa"),"</th>";
			echo "<th>",t("Maksettu"),"</th>";
			echo "<th>",t("Pankkitili"),"</th>";
			echo "<th>",t("Poimittu aineistoon"),"</th>";
			echo "</tr>";

			$poimittu_summa = 0;
			$summa = 0;
			$laskuja = 0;

			if (tarkista_oikeus("muutosite.php")) {
				$toslink = TRUE;
			}
			else {
				$toslink = FALSE;
			}

			$poimitut_laskut = array();

			while ($row = mysql_fetch_assoc($result)) {
				echo "<tr class='aktiivi'>";
				echo "<td>{$row['nimi']} {$row['nimitark']}</td>";
				echo "<td align='right'>{$row['laskunro']}</td>";

				echo "<td>";

				if ($toslink) echo "<a href='muutosite.php?tee=E&tunnus=$row[tunnus]&lopetus=$PHP_SELF////tee=$tee//alkuvv=$alkuvv//alkukk=$alkukk//alkupp=$alkupp//loppuvv=$loppuvv//loppukk=$loppukk//loppupp=$loppupp//tunnukset=$tunnukset'>".tv1dateconv($row["tapvm"])."</a>";
				else echo tv1dateconv($row["tapvm"]);

				echo "</td>";

				echo "<td valign='top'>",tv1dateconv($row['erpcm']),"</td>";
				echo "<td valign='top'>",tv1dateconv($row['olmapvm']),"</td>";
				echo "<td valign='top'>",tv1dateconv($row['mapvm']),"</td>";
				echo "<td align='right'>{$row['summa']} {$row['valkoodi']}</td>";
				echo "<td align='right'>{$row['poimittusumma']} {$row['valkoodi']}</td>";
				echo "<td>{$row['maksu_tili']}</td>";
				echo "<td>",tv1dateconv($row['maksuaika'], 'PITKA', ''),"</td>";
				echo "</tr>";

				$laskuja++;

				$poimitut_laskut[] = $row['tunnus'];
				$poimittu_summa += $row['poimittusumma_eur'];
				$summa += $row['summa_eur'];
			}

			echo "<tr>";
			echo "<th colspan='6'>".t("Yhteensä")." $laskuja ".t("kpl")."</th>";
			echo "<th>$summa $yhtiorow[valkoodi]</th>";
			echo "<th>$poimittu_summa $yhtiorow[valkoodi]</th>";
			echo "<th colspan='2'></th>";
			echo "</tr>";

			echo "</table>";

			if (tarkista_oikeus("sepa.php")) {
				echo "<br><br><form name = 'valinta' method='post' action = 'sepa.php'>";
				echo "<input type = 'hidden' name = 'tee' value = 'KIRJOITAKOPIO'>";
				echo "<input type = 'hidden' name = 'poimitut_laskut' value = '".implode(",", $poimitut_laskut)."'>";
				echo "<input type = 'submit' value = '".t("Tee maksuaineistokopio")."'>";
				echo "</form>";
			}
		}
		else {
			echo "<br /><font class='error'>",t("Yhtään laskua ei löytynyt"),".</font><br />";
		}
	}

	require ("inc/footer.inc");

?>