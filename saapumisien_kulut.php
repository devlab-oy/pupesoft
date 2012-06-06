<?php

require("inc/parametrit.inc");
echo "<font class='head'>".t("Saapumisien kulut")."</font><hr>";

if (!isset($alkupp, $alkukk, $alkuvv)) {
	$kuukausi_sitten = mktime(0, 0, 0, date("m")-1, date("d"), date("y"));
	$alkupp = date('d', $kuukausi_sitten);
	$alkukk = date('m', $kuukausi_sitten);
	$alkuvv = date('Y', $kuukausi_sitten);
	$loppupp = date('d');
	$loppukk = date('m');
	$loppuvv = date('Y');
}

echo "<form name=toimittaja method=post>";
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
		<input type='text' name='alkuvv' value='$alkuvv' size='8'> pp kk vvvv</td></td>
	</tr>
	<tr>
		<th>".t("Loppupvm")."</th>
		<td>
		<input type='text' name='loppupp' value='$loppupp' size='4'>
		<input type='text' name='loppukk' value='$loppukk' size='4'>
		<input type='text' name='loppuvv' value='$loppuvv' size='8'> pp kk vvvv</td>
		<td class='back'><input type='submit' value='Hae'></td>
		<input type='hidden' name='tee' value='raportoi'>
	</tr>
</table>";
echo "</form><br/>";

# Toimittajan haku
if ($ytunnus != '' and $toimittajaid == 0) {
	require ("inc/kevyt_toimittajahaku.inc");
	if ($toimittajaid == 0) exit;
}

# Toimittajan tiedot
## Pirkanmaan työkalukeskus, tunnus=70979
if ($toimittajaid != "") {
	  $toimittaja_query = "	SELECT * 
	  						FROM toimi 
	  						WHERE yhtio='{$kukarow[yhtio]}' 
	  						AND tunnus='$toimittajaid'";
	  $toimittaja_result = pupe_query($toimittaja_query);
	  $toimittaja = mysql_fetch_assoc($toimittaja_result);
}

# Päivämäärien tarkistus
if (checkdate($alkukk, $alkupp, $alkuvv) and checkdate($loppukk, $loppupp, $loppuvv)) {
	$alkupvm = "$alkuvv-$alkukk-$alkupp";
	$loppupvm = "$loppuvv-$loppukk-$loppupp";
} else {
	echo "<font class='error'>VIRHE PÄIVÄMÄÄRÄSSÄ</font>";
}

if ($tee == "raportoi") {
	if ($alkupvm != "" and $loppupvm != "" or $toimittajaid != "") {
		if (!empty($toimittajaid)) {
			$query_lisa = "AND liitostunnus = $toimittaja[tunnus]";
		} 
		$query_lisa .= " AND mapvm BETWEEN '$alkupvm' AND '$loppupvm'";
		

		$saapumiset_query = "	SELECT * 
								FROM lasku 
								WHERE tila='k' 
								AND vanhatunnus='0' 
								AND alatila='x' 
								$query_lisa 
								AND yhtio='{$kukarow['yhtio']}' 
								ORDER BY nimi ASC"; 
		$saapumiset_result = pupe_query($saapumiset_query);
		echo "<table>";

		# Loopataan saapumiset
		while ($tama_rivi = mysql_fetch_assoc($saapumiset_result)) {
			# Jos toimittaja vaihtui
			if ($tama_rivi['nimi'] != $edellinen_rivi['nimi']) {
	 			if (isset($edellinen_rivi)) {
		        	echo "<tr class='spec'>
			            <td>".t("Yhteensä")."</td>
			            <td style='text-align: right;'>{$yhteensa['vols']}</td>
			            <td style='text-align: right;'>{$yhteensa['sks']}</td>
			            <td style='text-align: right;'>".round($yhteensa['kulut'], 2)."</td>
			            <td style='text-align: right;'>".round(((($yhteensa['sks'] / $yhteensa['vols'])-1) * 100), 2)."</tr>";
			            $yhteensa_kaikki['vols'] += $yhteensa['vols'];
			            $yhteensa_kaikki['sks'] += $yhteensa['sks'];
			            $yhteensa_kaikki['kulut'] += $yhteensa['kulut'];
			            $yhteensa = NULL;
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
			if ($tama_rivi['tunnus'] != "") {

				$query = "	SELECT group_concat(vanhatunnus) as vanhatunnus 
							FROM lasku 
							WHERE tila='K' 
							AND vanhatunnus!=0 
							AND laskunro={$tama_rivi['laskunro']} 
							AND yhtio='{$kukarow['yhtio']}'";
				$vanhatunnus = mysql_fetch_array(pupe_query($query));

				if ($vanhatunnus['vanhatunnus'] != "") {
					#vols, Vaihto-omaisuuslaskujen summa
					$vols_query = "	SELECT round(sum(summa * vienti_kurssi),2) as summa 
								FROM lasku 
								WHERE tunnus IN ({$vanhatunnus['vanhatunnus']}) 
								AND vienti IN ('C','F','I','J','K','L') 
								AND yhtio='{$kukarow['yhtio']}'";
					$vols = mysql_fetch_array(pupe_query($vols_query));
				}

				#sks, Saapumisen kokonaissumma
				$sks_query = "	SELECT round(sum(tilausrivi.rivihinta),2) as saapumisen_summa 
								FROM tilausrivi 
								WHERE uusiotunnus={$tama_rivi['tunnus']} 
								AND yhtio='{$kukarow['yhtio']}'";
				$sks = mysql_fetch_array(pupe_query($sks_query));

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

		# VIELÄ viimeinen rivi?!?
		if (mysql_num_rows($saapumiset_result) > 0) {
			echo "<tr class='spec'>
			    <td>".t("Yhteensä")."</td>
			    <td style='text-align: right;'>{$yhteensa['vols']}</td>
			    <td style='text-align: right;'>{$yhteensa['sks']}</td>
			    <td style='text-align: right;'>".round($yhteensa['kulut'], 2)."</td>
			    <td style='text-align: right;'>".round((($yhteensa['sks'] / $yhteensa['vols']-1) * 100), 2)."</tr>";
			    $yhteensa_kaikki['vols'] += $yhteensa['vols'];
			    $yhteensa_kaikki['sks'] += $yhteensa['sks'];
			    $yhteensa_kaikki['kulut'] += $yhteensa['kulut'];

			# Kaikkien rivien yhteensä tulos
			echo "<tr><td class='back'><br/></td></tr>
				<tr class='spec'>
				<th>".t("YHTEENSÄ")."</th>
				<th style='text-align: right;'>{$yhteensa_kaikki['vols']}</th>
				<th style='text-align: right;'>{$yhteensa_kaikki['sks']}</th>
				<th style='text-align: right;'>".round($yhteensa_kaikki['kulut'], 2)."</th>
				<th style='text-align: right;'>".round((($yhteensa_kaikki['sks'] / $yhteensa_kaikki['vols']-1) * 100), 2)."</th>
			 	</tr>";
			 
			echo "</table>";
		}
	}
}
include("inc/footer.inc");
