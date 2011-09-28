<?php
	///* T�m� skripti k�ytt�� slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Varastopaikkojen ker�ysseuranta")."</font><hr>";

	if ($tee != '') {
		echo "<table>";

		$apaikka = strtoupper(sprintf("%-05s",$ahyllyalue)).strtoupper(sprintf("%05s",$ahyllynro)).strtoupper(sprintf("%05s",$ahyllyvali)).strtoupper(sprintf("%05s",$ahyllytaso));
		$lpaikka = strtoupper(sprintf("%-05s",$lhyllyalue)).strtoupper(sprintf("%05s",$lhyllynro)).strtoupper(sprintf("%05s",$lhyllyvali)).strtoupper(sprintf("%05s",$lhyllytaso));

		$lisa = "";

		if ($toppi != '') {
			$lisa = " LIMIT $toppi ";
		}

		$query = "	SELECT tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tuotepaikat.saldo, tuotepaikat.tunnus paikkatun, tilausrivi.nimitys, count(*) kpl, sum(tilausrivi.kpl) tuokpl
					FROM tilausrivi, tuotepaikat
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					and tilausrivi.tyyppi='L'
					and tilausrivi.laskutettuaika >='$vva-$kka-$ppa'
					and tilausrivi.laskutettuaika <='$vvl-$kkl-$ppl'
					and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'),lpad(upper(tuotepaikat.hyllyvali) ,5,'0'),lpad(upper(tuotepaikat.hyllytaso) ,5,'0')) >= '$apaikka'
					and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'),lpad(upper(tuotepaikat.hyllyvali) ,5,'0'),lpad(upper(tuotepaikat.hyllytaso) ,5,'0')) <= '$lpaikka'
					and tuotepaikat.yhtio=tilausrivi.yhtio
					and tuotepaikat.tuoteno=tilausrivi.tuoteno
					and tuotepaikat.hyllyalue=tilausrivi.hyllyalue
					and tuotepaikat.hyllynro=tilausrivi.hyllynro
					and tuotepaikat.hyllyvali=tilausrivi.hyllyvali
					and tuotepaikat.hyllytaso=tilausrivi.hyllytaso
					GROUP BY 1,2,3,4,5,6,7,8
					ORDER BY kpl desc, tuokpl desc
					$lisa";
		$result = mysql_query($query) or pupe_error($query);

		echo "<tr>
				<th>".t("Tuoteno")."</th>
				<th>".t("Nimitys")."</th>
				<th>".t("Varastopaikka")."</th>
				<th>".t("Saldo")."</th>
				<th>".t("Ker�yst�")."</th>
				<th>".t("Ker�yst�/P�iv�")."</th>
				<th>".t("Kpl/Ker�ys")."</th>";
		echo "</tr>";

		//p�ivi� aikajaksossa
		$epa1 = (int) date('U',mktime(0,0,0,$kka,$ppa,$vva));
		$epa2 = (int) date('U',mktime(0,0,0,$kkl,$ppl,$vvl));

		//Diff in workdays (5 day week)
		$pva = abs($epa2-$epa1)/60/60/24/7*5;

		$saldolliset = array();

		while ($row = mysql_fetch_array($result)) {
			echo "<tr>";
			echo "<td>$row[tuoteno]</td>";
			echo "<td>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";
			echo "<td>$row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</td>";
			echo "<td>$row[saldo]</td>";
			echo "<td>$row[kpl]</td>";


			$kplperpva = round($row["kpl"]/$pva,0);
			echo "<td>$kplperpva</td>";

			echo "<td>".round($row["tuokpl"]/$row["kpl"],0)."</td>";

			echo "</tr>";

			$saldolliset[] = $row["paikkatun"];
		}
		echo "</table><br><br>";

		echo "<form method='POST' action='../inventointi_listat.php'>";
		echo "<input type='hidden' name='tee' value='TULOSTA'>";

		$saldot = "";
		foreach($saldolliset as $saldo) {
			$saldot .= "$saldo,";
		}
		$saldot = substr($saldot,0,-1);

		echo "<input type='hidden' name='saldot' value='$saldot'>";
		echo "<input type='hidden' name='tila' value='SIIVOUS'>";
		echo "<input type='hidden' name='ei_inventointi' value='EI'>";
		echo "<input type='submit' value='".t("Tulosta lista")."'></form><br><br>";
	}


	//K�ytt�liittym�
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	// ehdotetaan 7 p�iv�� taaksep�in
	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<tr><th>".t("Sy�t� alkup�iv�m��r� (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'>
			<input type='text' name='kka' value='$kka' size='3'>
			<input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Sy�t� loppup�iv�m��r� (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'>
			<input type='text' name='kkl' value='$kkl' size='3'>
			<input type='text' name='vvl' value='$vvl' size='5'></td>";

	echo "<tr><th>".t("Anna alkuvarastopaikka:")."</th>
			<td><input type='text' size='6' name='ahyllyalue' value='$ahyllyalue'>
			<input type='text' size='6' name='ahyllynro' value='$ahyllynro'>
			<input type='text' size='6' name='ahyllyvali' value='$ahyllyvali'>
			<input type='text' size='6' name='ahyllytaso' value='$ahyllytaso'>
			</td></tr>";

	echo "<tr><th>".t("ja loppuvarastopaikka:")."</th>
			<td><input type='text' size='6' name='lhyllyalue' value='$lhyllyalue'>
			<input type='text' size='6' name='lhyllynro' value='$lhyllynro'>
			<input type='text' size='6' name='lhyllyvali' value='$lhyllyvali'>
			<input type='text' size='6' name='lhyllytaso' value='$lhyllytaso'>
			</td></tr>";

	echo "<tr><th>".t("Listaa vain n�in monta ker�tyint� tuotetta:")."</th>
			<td><input type='text' size='6' name='toppi' value='$toppi'></td>";

	echo "</table><br>";

	echo "<input type='submit' value='".t("Aja raportti")."'></form>";

	require ("../inc/footer.inc");

?>