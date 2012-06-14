<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

require("inc/parametrit.inc");

echo "<font class='head'>".t("Saapumisien kulut")."</font><hr>";

# Oletuksena viimeiset 30 p�iv��
if (!isset($alkupp, $alkukk, $alkuvv, $loppupp, $loppukk, $loppuvv)) {
	$kuukausi_sitten = mktime(0, 0, 0, date("m")-1, date("d"), date("y"));
	$alkupp = date('d', $kuukausi_sitten);
	$alkukk = date('m', $kuukausi_sitten);
	$alkuvv = date('Y', $kuukausi_sitten);
	$loppupp = date('d');
	$loppukk = date('m');
	$loppuvv = date('Y');
}

echo "<form name='toimittaja' method='post'>";
echo "<input type='hidden' name='tee' value='raportoi'>";
echo "<table>
	<tr>
		<th>".t("Toimittaja")."</th>
		<td><input type='text' name='ytunnus' value='$ytunnus'></td>
	</tr>
	<tr>
		<th>".t("Alkupvm")."</th>
		<td>
		<input type='text' name='alkupp' value='$alkupp' size='4'>
		<input type='text' name='alkukk' value='$alkukk' size='4'>
		<input type='text' name='alkuvv' value='$alkuvv' size='8'> pp kk vvvv
		</td>
	</tr>
	<tr>
		<th>".t("Loppupvm")."</th>
		<td>
		<input type='text' name='loppupp' value='$loppupp' size='4'>
		<input type='text' name='loppukk' value='$loppukk' size='4'>
		<input type='text' name='loppuvv' value='$loppuvv' size='8'> pp kk vvvv
		</td>
	</tr>
</table>";
echo "<br/><input type='submit' value='".t("Hae")."'>";
echo "</form><br/>";

# Toimittajan haku
if ($ytunnus != '' and $toimittajaid == 0) {
	require ("inc/kevyt_toimittajahaku.inc");

	if ($toimittajaid == 0) {
		require("inc/footer.inc");
		exit;
	}
}

# Toimittajan tiedot
if ($toimittajaid > 0) {
	  $toimittaja_query = "	SELECT *
							FROM toimi
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus  = '$toimittajaid'";
	  $toimittaja_result = pupe_query($toimittaja_query);
	  $toimittaja = mysql_fetch_assoc($toimittaja_result);
}

# P�iv�m��rien tarkistus
if (checkdate($alkukk, $alkupp, $alkuvv) and checkdate($loppukk, $loppupp, $loppuvv)) {
	$alkupvm  = "$alkuvv-$alkukk-$alkupp";
	$loppupvm = "$loppuvv-$loppukk-$loppupp";
}
else {
	echo "<font class='error'>".t("Virheellinen p�iv�m��r�").".</font>";
	$tee = "";
}

# Luo raportti
if ($tee == "raportoi" and $alkupvm != "" and $loppupvm != "") {

	$query_lisa = "";

	if (isset($toimittaja['tunnus']) and !empty($toimittaja['tunnus'])) {
		$query_lisa .= "AND liitostunnus = {$toimittaja['tunnus']}";
	}

	$query_lisa .= " AND mapvm BETWEEN '$alkupvm' AND '$loppupvm'";

	# Haetaan kaikki saapumiset
	$saapumiset_query = "	SELECT *
							FROM lasku
							WHERE tila		= 'K'
							AND vanhatunnus	= 0
							AND alatila		= 'X'
							$query_lisa
							AND yhtio 		= '{$kukarow['yhtio']}'
							ORDER BY nimi ASC";
	$saapumiset_result = pupe_query($saapumiset_query);

	echo "<table>";

	# Loopataan saapumiset
	while ($tama_rivi = mysql_fetch_assoc($saapumiset_result)) {
		# Jos toimittaja vaihtuu..
		if ($tama_rivi['nimi'] != $edellinen_rivi['nimi']) {
			if (isset($edellinen_rivi)) {
				echo "<tr class='spec'>
					<td>".t("Yhteens�")."</td>
					<td style='text-align: right;'>{$yhteensa['vols']}</td>
					<td style='text-align: right;'>{$yhteensa['sks']}</td>
					<td style='text-align: right;'>".round($yhteensa['kulut'], 2)."</td>
					<td style='text-align: right;'>".round(((($yhteensa['sks'] / $yhteensa['vols'])-1) * 100), 2)."</tr>";

					$yhteensa_kaikki['vols'] += $yhteensa['vols'];
					$yhteensa_kaikki['sks'] += $yhteensa['sks'];
					$yhteensa_kaikki['kulut'] += $yhteensa['kulut'];
					$yhteensa = NULL; # Nollataan yhteens� arvot
			}

			# Toimittaja
			echo "<tr><td class='back' colspan='5'><br/><font class='head'>{$tama_rivi['nimi']}</font></td></tr>";
			# Otsikkorivi
			echo "
				<th>".t("Saapuminen")."</th>
				<th style='text-align: right;'>".t("Vaihto-omaisuuslaskut")."</th>
				<th style='text-align: right;'>".t("Saapuminen")."</th>
				<th style='text-align: right;'>".t("Kuluja")."</th>
				<th style='text-align: right;'>%</th>
			</tr>";
		}

		$query = "	SELECT group_concat(vanhatunnus) as vanhatunnus
					FROM lasku
					WHERE tila		 = 'K'
					AND vanhatunnus != 0
					AND laskunro	 = {$tama_rivi['laskunro']}
					AND yhtio		 = '{$kukarow['yhtio']}'";
		$vanhatunnus = mysql_fetch_assoc(pupe_query($query));

		if ($vanhatunnus['vanhatunnus'] != "") {
			#vols, Vaihto-omaisuuslaskujen summa
			$vols_query = "	SELECT round(sum(summa * vienti_kurssi),2) as summa
							FROM lasku
							WHERE tunnus IN ({$vanhatunnus['vanhatunnus']})
							AND vienti IN ('C','F','I','J','K','L')
							AND yhtio = '{$kukarow['yhtio']}'";
			$vols = mysql_fetch_assoc(pupe_query($vols_query));
		}

		if ($tama_rivi['tunnus'] != "") {
			#sks, Saapumisen kokonaissumma
			$sks_query = "	SELECT round(sum(tilausrivi.rivihinta),2) as saapumisen_summa
							FROM tilausrivi
							WHERE uusiotunnus	= {$tama_rivi['tunnus']}
							AND yhtio			= '{$kukarow['yhtio']}'";
			$sks = mysql_fetch_assoc(pupe_query($sks_query));

			echo "<tr class='aktiivi'>";
			echo "<td>".$tama_rivi["laskunro"]."</td>";
			echo "<td style='text-align: right;'>".$vols["summa"]."</td>";
			echo "<td style='text-align: right;'>".$sks["saapumisen_summa"]."</td>";
			echo "<td style='text-align: right;'>".round($sks["saapumisen_summa"] - $vols["summa"], 2)."</td>";
			echo "<td style='text-align: right;'>".round(((($sks["saapumisen_summa"] / $vols["summa"])-1)*100), 2)."</td>";
			echo "</tr>";

			$yhteensa['vols'] += $vols["summa"];
			$yhteensa['sks'] += $sks["saapumisen_summa"];
			$yhteensa['kulut'] += ($sks["saapumisen_summa"] - $vols["summa"]);
		}
		$edellinen_rivi = $tama_rivi;
	}

	# VIEL� viimeisen rivin yhteens� tulos
	if (mysql_num_rows($saapumiset_result) > 0) {
		echo "<tr class='spec'>
			<td>".t("Yhteens�")."</td>
			<td style='text-align: right;'>{$yhteensa['vols']}</td>
			<td style='text-align: right;'>{$yhteensa['sks']}</td>
			<td style='text-align: right;'>".round($yhteensa['kulut'], 2)."</td>
			<td style='text-align: right;'>".round((($yhteensa['sks'] / $yhteensa['vols']-1) * 100), 2)."</tr>";

			$yhteensa_kaikki['vols'] += $yhteensa['vols'];
			$yhteensa_kaikki['sks'] += $yhteensa['sks'];
			$yhteensa_kaikki['kulut'] += $yhteensa['kulut'];

		# Kaikkien rivien yhteens� tulos
		echo "<tr><td class='back'><br/></td></tr>
			<tr class='spec'>
			<th>".t("YHTEENS�")."</th>
			<th style='text-align: right;'>{$yhteensa_kaikki['vols']}</th>
			<th style='text-align: right;'>{$yhteensa_kaikki['sks']}</th>
			<th style='text-align: right;'>".round($yhteensa_kaikki['kulut'], 2)."</th>
			<th style='text-align: right;'>".round((($yhteensa_kaikki['sks'] / $yhteensa_kaikki['vols']-1) * 100), 2)."</th>
			</tr>";

		echo "</table>";
	}
	# mysql_num_rows == 0, ei l�ytynyt yht��n saapumista
	else {
		echo "<font class='error'>".t("Yht��n saapumista ei l�ytynyt")."</font>";
	}
}

require("inc/footer.inc");
