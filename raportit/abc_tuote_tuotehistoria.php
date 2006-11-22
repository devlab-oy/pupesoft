<?php

	echo "<font class='head'>".t("ABC-Analyysiä: Tuotehistoria")." $tuoteno<hr></font>";

	echo "<a href='$PHP_SELF?tee=$teekutsu&luokka=$luokka&try=$try&osasto=$osasto&valinta=$valinta&toim=$toim'>".t("Palaa luokka-analyysiin")."</a><br><br>";

	$query = "	SELECT tuote.*, date_format(muutospvm, '%Y-%m-%d') muutos, date_format(luontiaika, '%Y-%m-%d') luonti,
				group_concat(distinct tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '<br>') toimittaja,
				group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '<br>') osto_era,
	 			group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno,
				group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '<br>') tuotekerroin,
				group_concat(distinct concat_ws(' ',tuotteen_toimittajat.ostohinta,tuotteen_toimittajat.valuutta) order by tuotteen_toimittajat.tunnus separator '<br>') ostohinta
				FROM tuote
				LEFT JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				and tuote.tuoteno = '$tuoteno'
				GROUP BY tuote.tuoteno";
	$result = mysql_query($query) or pupe_error($query);
	$tuoterow = mysql_fetch_array($result);

	//eka laitetaan tuotteen yleiset (aika staattiset) tiedot
	echo "<table width = '650'>";

	//1
	echo "<tr><th>".t("Tuotenumero")."</th><th>".t("Yksikkö")."</th><th colspan='4'>".t("Nimitys")."</th>";
	echo "<tr><td>$tuoterow[tuoteno]</td><td>$tuoterow[yksikko]</td><td colspan='4'>".substr($tuoterow["nimitys"],0,100)."</td></tr>";

	//2
	echo "<tr><th>".t("Osasto/try")."</th><th>".t("Toimittaja")."</th><th>".t("Aleryhmä")."</th><th>".t("Tähti")."</th><th colspan='2'>".t("VAK")."</th></tr>";
	echo "<td>$tuoterow[osasto]/$tuoterow[try]</td><td>$tuoterow[toimittaja]</td>
			<td>$tuoterow[aleryhma]</td><td>$tuoterow[tahtituote]</td><td colspan='2'>$tuoterow[vakkoodi]</td></tr>";

	//3
	echo "<tr><th>".t("Toimtuoteno")."</th><th>".t("Myyntihinta")."</th><th>".t("Nettohinta")."</th><th>".t("Ostohinta")."</th><th>".t("Kehahinta")."</th><th>".t("Vihahinta")."</th>";
	echo "<tr><td>$tuoterow[toim_tuoteno]</td>
			<td>$tuoterow[myyntihinta]</td><td>$tuoterow[nettohinta]</td><td>$tuoterow[ostohinta] $tuoterow[valuutta]</td>
			<td>$tuoterow[kehahin]</td><td>$tuoterow[vihahin] $tuoterow[vihapvm]</td></tr>";

	//4
	echo "<tr><th>".t("Hälyraja")."</th><th>".t("Tilerä")."</th><th>".t("Toierä")."</th><th>".t("Kerroin")."</th><th>".t("Tarrakerroin")."</th><th>".t("Tarrakpl")."</th>";
	echo "<tr><td>$tuoterow[halytysraja]</td>
			<td>$tuoterow[osto_era]</td><td>$tuoterow[myynti_era]</td><td>$tuoterow[tuotekerroin]</td>
			<td>$tuoterow[tarrakerroin]</td><td>$tuoterow[tarrakpl]</td></tr>";

	//6
	echo "<tr><th>".t("Info")."</th><th colspan='5'>".t("Avainsanat")."</th></tr>";
	echo "<tr><td>$tuoterow[fakta]&nbsp;</td><td colspan='5'>$tuoterow[lyhytkuvaus]</td></tr>";

	//7
	echo "<tr><th colspan='6'>".t("Tuotteen kuvaus")."</th></tr>";
	echo "<tr><td colspan='6'>$tuoterow[kuvaus]&nbsp;</td></tr>";
	echo "</table><br>";


	//tapahtumat
	$query = "	SELECT concat_ws('@', laatija, laadittu) kuka, laji, kpl, hinta, kpl*hinta arvo, selite
				FROM tapahtuma use index (yhtio_tuote_laadittu)
				WHERE yhtio = '$kukarow[yhtio]'
				and tuoteno = '$tuoteno'
				and laadittu >= '2004-01-01'
				and laadittu <= '2004-12-31'
				ORDER BY laadittu desc";
	$qresult = mysql_query($query) or pupe_error($query);

	// Varastotapahtumat
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("kuka@milloin")."</th>";
	echo "<th>".t("laji")."</th>";
	echo "<th>".t("kpl")."</th>";
	echo "<th>".t("hinta")."</th>";
	echo "<th>".t("arvo")."</th>";
	echo "<th>".t("selite")."</th>";
	echo "</tr>";

	while ($prow = mysql_fetch_array ($qresult)) {
		echo "<tr>";
		echo "<td>$prow[kuka]</td>";
		echo "<td>$prow[laji]</td>";
		echo "<td nowrap>$prow[kpl]</td>";
		echo "<td nowrap>$prow[hinta]</td>";
		echo "<td nowrap>$prow[arvo]</td>";
		echo "<td>$prow[selite]</td>";
		echo "</tr>";
	}
	echo "</table>";
?>