<?php
# SAAPUMISET/KEIKKA ON: Lasku where tila=k and vanhatunnus=0 and alatila='x' and mapvm between pvma and pvml (and liitostunnus=toimi.tunnus)
# Saapumisen arvo (sks): sum(tilausrivi.rivihinta) from tilausrivi where uusiotunnus=lasku.tunnus 
# Laskut: and vienti in ('C','F','I','J','K','L')";
# laskunro = saapumisen numero
# pvml: tämä päivä, 
# pvma: -30päivää 

####
# select liitostunnus, laskunro, summa, vienti_kurssi, vienti, tila, alatila, vanhatunnus, tunnus from lasku where tila='k' and alatila='x' and vanhatunnus='0' and liitostunnus='7097' and vienti in ('C','F','I','J','K','L');

require("inc/parametrit.inc");

echo "<font class='head'>".t("Saapumisien kulut")."</font><hr>";

echo "<form name=toimittaja method=post>";
echo "<table>
	<tr>
		<th>Toimittaja:</th>
		<td><input type='text' name='ytunnus'></td>
	</tr>
		<tr>
		<th>Alkupvm:</th>
		<td><input type='text' name='alkupvm'></td>
	</tr>
		<tr>
		<th>Loppupvm:</th>
		<td><input type='text' name='loppupvm'></td>
		<td><input type='submit' value='Hae'></td>
	</tr>
</table>";
echo "</form>";

# Toimittajan haku
if ($ytunnus != '' and $toimittajaid == 0) {
	require ("inc/kevyt_toimittajahaku.inc");
	if ($toimittajaid == 0) exit;
}

# Toimittajan tiedot
## Pirkanmaan työkalukeskus, tunnus=70979
if ($toimittajaid != "") {
	  $toimittaja_query = "SELECT * FROM toimi where yhtio='{$kukarow[yhtio]}' and tunnus='$toimittajaid'";
	  $toimittaja_result = pupe_query($toimittaja_query);
	  $toimittaja = mysql_fetch_assoc($toimittaja_result);
	  echo "<br>".$toimittaja[nimi]."</b>("."$toimittaja[tunnus])";
}

if ($alkupvm != "" and $loppupvm != "" or $toimittajaid != "") {
	if (!empty($toimittajaid)) {
		$query_lisa = "AND liitostunnus = $toimittaja[tunnus]";
	} else {
		$query_lisa = "AND mapvm BETWEEN '$alkupvm' AND '$loppupvm'";
	}

	#$saapumiset_query = "SELECT * FROM lasku WHERE tila='k' AND vanhatunnus='0' AND alatila='x' AND liitostunnus={$toimittaja[tunnus]}";
	$saapumiset_query = "SELECT * FROM lasku WHERE tila='k' AND vanhatunnus='0' AND alatila='x' $query_lisa AND yhtio='{$kukarow['yhtio']}' order by nimi asc"; 
	$saapumiset_result = pupe_query($saapumiset_query);

	echo "<table>
		<tr>
		<th>Nimi</th>
		<th>Saapuminen</th>
		<th>lasku.tunnus</th>
		<th>vols</th>
		<th>sks</th>
		<th>kuluja</th>
		<th>%</th>
		<th>vienti_kurssi</td>
		</tr>";

	# Loopataan saapumiset
	while($saapuminen = mysql_fetch_assoc($saapumiset_result)) {
	
		$sks_query = "SELECT SUM(tilausrivi.rivihinta) FROM tilausrivi WHERE uusiotunnus={$saapuminen['tunnus']} AND yhtio='{$kukarow['yhtio']}'";
		$sks = mysql_fetch_row(pupe_query($sks_query));

		$query = "SELECT vanhatunnus FROM lasku WHERE tila='K' AND vanhatunnus!=0 AND laskunro={$saapuminen['laskunro']}";
		$vanhatunnus = mysql_fetch_row(pupe_query($query));

		$query_ = "SELECT summa, vienti_kurssi FROM lasku WHERE tunnus='{$vanhatunnus[0]}' AND vienti IN ('C','F','I','J','K','L')";
		$summat = mysql_fetch_row(pupe_query($query_));
		
		echo "<tr>";
		echo "<td>".$saapuminen['nimi'] ."(".$saapuminen['liitostunnus'].")";
		echo "<td>".$saapuminen["laskunro"]."</td>";
		echo "<td>".$saapuminen["tunnus"]."</td>";
		echo "<td>".$summat[0]*$saapuminen['vienti_kurssi']." (".$saapuminen["summa"].")</td>";
		echo "<td>".round($sks[0], 2)."</td>";
		echo "<td>".round(($sks[0] - $summat[0]), 2)."</td>";
		echo "<td>".round($sks[0]/$summat[0], 4)."</td>";
		echo "<td>".round($saapuminen['vienti_kurssi'], 2)."</td>";
		echo "</tr>";

		$edellinen_toimittaja = $saapuminen['liitostunnus'];
	}
	echo "</table>";
}

include("inc/footer.inc");
