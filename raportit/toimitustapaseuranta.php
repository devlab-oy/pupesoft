<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>",t("Toimitustapaseuranta"),"</font><hr>";

	echo "<br /><table><form method='post'>";

	// ehdotetaan 7 päivää taaksepäin
	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	if (!isset($tee)) $tee = "";

	$sel = array_fill_keys(array($tee), ' selected') + array_fill_keys(array('kaikki', 'paivittain'), '');

	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<tr><th>",t("Syötä alkupäivämäärä (pp-kk-vvvv)"),"</th>
			<td><input type='text' name='ppa' value='{$ppa}' size='3'></td>
			<td><input type='text' name='kka' value='{$kka}' size='3'></td>
			<td><input type='text' name='vva' value='{$vva}' size='5'></td>
			</tr><tr><th>",t("Syötä loppupäivämäärä (pp-kk-vvvv)"),"</th>
			<td><input type='text' name='ppl' value='{$ppl}' size='3'></td>
			<td><input type='text' name='kkl' value='{$kkl}' size='3'></td>
			<td><input type='text' name='vvl' value='{$vvl}' size='5'></td><td class='back'></td></tr>";
	echo "<tr><th>",t("Valitse seurantatapa"),"</th>";
	echo "<td colspan='3'><select name='tee'>";
	echo "<option value='kaikki'{$sel['kaikki']}>",t("Näytä summattuna"),"</option>";
	echo "<option value='paivittain'{$sel['paivittain']}>",t("Näytä päivittäin"),"</option>";
	echo "</td></select>";
	echo "<td class='back'><input type='submit' value='",t("Aja raportti"),"'></td></tr></table>";

	echo "<br />";

	if ($tee != '') {
		echo "<table>";

		$lisa = $orderbylisa = $groupbylisa = "";

		if ($tee == 'paivittain') {
			$lisa = ", LEFT(tilausrivi.toimitettuaika, 10) tapvm";
			$groupbylisa = ",2";
			$orderbylisa = "tapvm, ";
		}

		$query = "	SELECT lasku.toimitustapa
					{$lisa},
					SEC_TO_TIME(AVG(TIME_TO_SEC(DATE_FORMAT(toimitettuaika,'%H:%i:%s')))) aika,
					COUNT(DISTINCT lasku.tunnus) kpl,
					SUM(tilausrivi.rivihinta) summa,
					COUNT(DISTINCT IF(lasku.kerayslista = 0, lasku.tunnus, lasku.kerayslista)) kpl_kerayslista,
					COUNT(DISTINCT tilausrivi.tunnus) tilausriveja,
					ROUND(SUM(tilausrivi.kpl)) kpl_tilriv
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.toimitettuaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' AND tilausrivi.toimitettuaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59')
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tila = 'L'
					AND lasku.alatila = 'X'
					GROUP BY 1 {$groupbylisa}
					ORDER BY {$orderbylisa} aika, kpl desc, toimitustapa";
		$result = mysql_query($query) or pupe_error($query);

		$otsikot =  "	<tr>
						<th>".t("Toimitustapa")."</th>
						<th>".t("Toimitusaika")."</th>";

		if ($tee == 'kaikki') {
			$otsikot .= "<th>".t("Tilauksia")."</th>";
			$otsikot .= "<th>".t("Tilauksia/Päivä")."</th>";
		}
		else {
			$otsikot .= "<th>".t("Keräyslistoja")."</th>";
			$otsikot .= "<th>".t("Tilauksia")."</th>";
			$otsikot .= "<th>".t("Tilausrivejä")."</th>";
			$otsikot .= "<th>".t("Määrä")."</th>";
		}

		$otsikot .= "<th>".t("Myynti")."</th></tr>";

		if ($tee == 'kaikki') echo $otsikot;

		//päiviä aikajaksossa
		$epa1 = (int) date('U',mktime(0,0,0,$kka,$ppa,$vva));
		$epa2 = (int) date('U',mktime(0,0,0,$kkl,$ppl,$vvl));

		//Diff in workdays (5 day week)
		if ($tee == 'paivittain') $pva = abs($epa2-$epa1)/60/60/24;
		else $pva = abs($epa2-$epa1)/60/60/24/7*5;

		$paivamaara = "";

		$tilauksia_kaikki = 0;
		$tilauksia = 0;
		$kerayslistoja_kaikki = 0;
		$kerayslistoja = 0;
		$tilausriveja_kaikki = 0;
		$tilausriveja = 0;
		$maara_kaikki = 0;
		$maara = 0;
		$kplperpva_kaikki = 0;

		while ($row = mysql_fetch_array($result)) {

			if ($tee == 'kaikki') $row['tapvm'] = "";

			if ($tee == 'paivittain' and ($paivamaara == "" or $paivamaara != $row['tapvm'])) {

				if ($paivamaara != "") {
					echo "<tr><th colspan='2'>&nbsp;</th><th>{$kerayslistoja}</th><th>{$tilauksia}</th><th>{$tilausriveja}</th><th>{$maara}</th><th>&nbsp;</th></tr>";
					echo "<tr><td class='back' colspan='7'>&nbsp;</td></tr>";
				}

				$tilauksia = 0;
				$kerayslistoja = 0;
				$tilausriveja = 0;
				$maara = 0;

				echo "<tr><th colspan='7'>",tv1dateconv($row['tapvm']),"</th></tr>";
				echo $otsikot;
			}

			echo "<tr class='aktiivi'>";
			echo "<td>$row[toimitustapa]</td>";
			echo "<td>$row[aika]</td>";

			if ($tee == 'paivittain') {
				echo "<td>{$row['kpl_kerayslista']}</td>";
				$kerayslistoja += $row['kpl_kerayslista'];
				$kerayslistoja_kaikki += $row['kpl_kerayslista'];
				$tilausriveja += $row['tilausriveja'];
				$tilausriveja_kaikki += $row['tilausriveja'];
				$maara += $row['kpl_tilriv'];
				$maara_kaikki += $row['kpl_tilriv'];
			}

			echo "<td>$row[kpl]</td>";

			if ($tee == 'kaikki') {
				$kplperpva = round($row["kpl"]/$pva,0);
				echo "<td>$kplperpva</td>";
				$kplperpva_kaikki += $kplperpva;
			}
			else {
				echo "<td>{$row['tilausriveja']}</td>";
				echo "<td>{$row['kpl_tilriv']}</td>";
			}

			echo "<td>",hintapyoristys($row['summa']),"</td>";
			echo "</tr>";

			$paivamaara = $row['tapvm'];

			$tilauksia += $row['kpl'];
			$tilauksia_kaikki += $row['kpl'];
		}

		if ($tee == 'paivittain') {
			echo "<tr><th colspan='2'>&nbsp;</th><th>{$kerayslistoja}</th><th>{$tilauksia}</th><th>{$tilausriveja}</th><th>{$maara}</th><th>&nbsp;</th></tr>";

			echo "<tr><td class='back' colspan='7'>&nbsp;</td></tr>";
			echo "<tr><th colspan='2'>",t("Kaikki yhteensä"),"</th><th>{$kerayslistoja_kaikki}</th><th>{$tilauksia_kaikki}</th><th>{$tilausriveja_kaikki}</th><th>{$maara_kaikki}</th><th>&nbsp;</th>";
		}
		else {
			echo "<tr><th colspan='2'>",t("Kaikki yhteensä"),"</th><th>{$tilauksia_kaikki}</th><th>{$kplperpva_kaikki}</th><th>&nbsp;</th>";
		}


		echo "</table>";
	}

	require ("inc/footer.inc");
