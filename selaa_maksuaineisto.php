<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>",t("Selaa maksuaineistoja"),"</font><hr>";

	if (!isset($kuka_poimi)) $kuka_poimi = '';
	if (!isset($tunnukset)) $tunnukset = '';
	if (!isset($tee)) $tee = '';

	echo "<table>";
	echo "<form method='post' action=''>";

	echo "<th valign='top'>",t("Alkukausi"),"</th>";
	echo "<td><select name='alkuvv'>";

	$sel = array();
	if (!isset($alkuvv) or $alkuvv == "") $alkuvv = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	$sel[$alkuvv] = "SELECTED";

	for ($i = date("Y"); $i >= date("Y")-4; $i--) {
		echo "<option value='{$i}' {$sel[$i]}>{$i}</option>";
	}

	echo "</select>";

	$sel = array();
	if (!isset($alkukk) or $alkukk == "") $alkukk = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	$sel[$alkukk] = "SELECTED";

	echo "<select name='alkukk'>";

	for ($i = 1; $i < 13; $i++) {
		$val = $i < 10 ? '0'.$i : $i;
		echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
	}

	echo "</select>";

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
		<th valign='top'>",t("Loppukausi"),"</th>
		<td><select name='loppuvv'>";

	$sel = array();
	if (!isset($loppuvv) or $loppuvv == "") $loppuvv = date("Y");
	$sel[$loppuvv] = "SELECTED";

	for ($i = date("Y")+1; $i >= date("Y")-4; $i--) {
		echo "<option value='{$i}' {$sel[$i]}>{$i}</option>";
	}

	echo "</select>";

	$sel = array();
	if (!isset($loppukk) or $loppukk == "") $loppukk = date("m");
	$sel[$loppukk] = "SELECTED";

	echo "<select name='loppukk'>";

	for ($i = 1; $i < 13; $i++) {
		$val = $i < 10 ? '0'.$i : $i;
		echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
	}

	echo "</select>";

	$sel = array();
	if (!isset($loppupp) or $loppupp == "") $loppupp = date("d", mktime(0, 0, 0, (date("m")+1), 0, date("Y")));
	$sel[$loppupp] = "SELECTED";

	echo "<select name='loppupp'>";

	for ($i = 1; $i < 32; $i++) {
		$val = $i < 10 ? '0'.$i : $i;
		echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
	}

	echo "</select></td><td class='back'>&nbsp;</td></tr>";

	echo "<tr><th>",t("Poimija"),"</th><td><input type='text' name='kuka_poimi' value='{$kuka_poimi}'></td><td><input type='submit' name='submitbutton' value='",t("Hae"),"'></td></tr>";
	echo "<input type='hidden' name='tee' value=''>";
	echo "<input type='hidden' name='tunnukset' value=''>";

	echo "</form>";
	echo "</table>";

	echo "<br />";

	if ($tee == '') {

		$lisa = '';

		if (trim($kuka_poimi) != '') {
			$lisa .= " and (lasku.maksaja like '%".mysql_real_escape_string((string) $kuka_poimi)."%' or kuka.nimi like '%".mysql_real_escape_string((string) $kuka_poimi)."%') ";
		}

		$query = "	SELECT if(lasku.maa = 'fi', 1, 2) tyyppi, lasku.popvm aika, kuka.nimi kukanimi, CONCAT(yriti.nimi, ' ', yriti.tilino) maksu_tili, COUNT(*) kpl, GROUP_CONCAT(lasku.tunnus) tunnukset, round(sum(lasku.summa*if(lasku.maksu_kurssi=0,lasku.vienti_kurssi,lasku.maksu_kurssi)), 2) summa
					FROM lasku
					LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = lasku.maksaja)
					LEFT JOIN yriti ON (yriti.yhtio = lasku.yhtio AND yriti.tunnus = lasku.maksu_tili)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tila in ('P', 'Q', 'Y') 
					AND lasku.maksuaika >= '$alkuvv-$alkukk-$alkupp 00:00:00' and lasku.maksuaika <= '$loppuvv-$loppukk-$loppupp 23:59:59'
					$lisa
					GROUP BY 1,2,3,4
					ORDER BY 1,2,3,4 ASC";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table>";
			$tyyppi = '';

			while ($row = mysql_fetch_assoc($result)) {
				if ($tyyppi == '' or $tyyppi != $row['tyyppi']) {

					echo "<tr><th colspan='5'>";

					if ($row['tyyppi'] == 1) {
						echo t("Kotimaan maksuaineistot");
					}
					else {
						echo t("Ulkomaan maksuaineistot");
					}

					echo "</th></tr>";

					echo "<tr><th>",t("Pvm"),"</th><th>",t("Poimijan nimi"),"</th><th>",t("Maksutili"),"</th><th>",t("Laskuja"),"</th><th>",t("Summa")," {$yhtiorow['valkoodi']}</th></tr>";
				}
				echo "<tr><td><a href='{$palvelin2}selaa_maksuaineisto.php?tee=selaa&alkuvv={$alkuvv}&alkukk={$alkukk}&alkupp={$alkupp}&loppuvv={$loppuvv}&loppukk={$loppukk}&loppupp={$loppupp}&tunnukset={$row['tunnukset']}'>{$row['aika']}</a></td><td>{$row['kukanimi']}</td><td>{$row['maksu_tili']}</td><td align='right'>{$row['kpl']}</td><td align='right'>{$row['summa']}</td></tr>";
				$tyyppi = $row['tyyppi'];
			}

			echo "</table>";
		}
	}

	if ($tee == 'selaa') {

		$lisa = '';

		if ($tunnukset) $lisa .= " and lasku.tunnus in ($tunnukset) ";

		$query = "	SELECT lasku.nimi, lasku.nimitark, lasku.maksaja, lasku.maksuaika, if(lasku.laskunro = 0, '', lasku.laskunro) laskunro, lasku.summa, lasku.valkoodi, kuka.nimi kukanimi, lasku.popvm, lasku.tapvm, lasku.mapvm
					FROM lasku
					LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = lasku.maksaja)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tila in ('P', 'Q', 'Y') 
					AND lasku.maksuaika > '0000-00-00 00:00:00'
					$lisa
					ORDER BY lasku.tila, lasku.maksuaika ASC";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<a hreF='{$palvelin2}selaa_maksuaineisto.php?&alkuvv={$alkuvv}&alkukk={$alkukk}&alkupp={$alkupp}&loppuvv={$loppuvv}&loppukk={$loppukk}&loppupp={$loppupp}'>&laquo; ",t("Takaisin"),"</a><br /><br /><table>";

			echo "<tr><th>",t("Nimi"),"</th><th>",t("Laskunro"),"</th><th>",t("Tapvm"),"</th><th>",t("Summa"),"</th><th>",t("Poimija"),"</th><th>",t("Poimittu"),"</th><th>",t("Maksuaineisto luotu"),"</th><th>",t("Mapvm"),"</th></tr>";

			while ($row = mysql_fetch_assoc($result)) {
				echo "<tr class='aktiivi'><td>{$row['nimi']} {$row['nimitark']}</td><td align='right'>{$row['laskunro']}</td><td valign='top'>",tv1dateconv($row['tapvm']),"</td><td align='right'>{$row['summa']} {$row['valkoodi']}</td><td>{$row['kukanimi']}</td><td>",tv1dateconv($row['maksuaika'], 'PITKA', ''),"</td><td>",tv1dateconv($row['popvm'], 'PITKA', ''),"</td><td valign='top'>",tv1dateconv($row['mapvm']),"</td></tr>";
			}

			echo "</table>";

		}
		else {
			echo "<br /><font class='error'>",t("Yhtään laskua ei löytynyt"),".</font><br />";
		}
	}

	require ("inc/footer.inc");

?>