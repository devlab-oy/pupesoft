<?php
	require ("inc/parametrit.inc");

	echo "<font class=head>".t("Varastoluettelo")."</font><hr>";

	// k‰yttis
	echo "<br><form action='$PHP_SELF' action='POST'>";
	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<table>";
	echo "<tr><td>";

	$monivalintalaatikot = array('OSASTO', 'TRY','TUOTEMERKKI');
	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";
	echo "<tr><td class='back'>";
	echo "<input type='submit' value='".t("Aja raportti")."' name='painoinnappia'>";
	echo "</td></tr>";
	echo "</table>";
	echo "</form>";
	echo "<br>";

	if ($tee != "" and isset($painoinnappia)) {

		$osastosql = "";
		$trysql = "";
		$tuotemerkkisql = "";

		if (isset($mul_try) and $mul_try != "") {
			$try = "";
			foreach ($mul_try as $key => $value ) {
				$try .= $value.',';
			}
			if (trim($try) != "") {
				$try = "(".substr($try, 0, -1).")";
				$trysql = " AND tuote.try in $try ";
			}
		}

		if (isset($mul_osasto) and $mul_osasto != "") {
			$osasto = "";
			foreach ($mul_osasto as $key => $value ) {
				$osasto .= $value.',';
			}
			if (trim($osasto) != "") {
				$osasto = "(".substr($osasto, 0, -1).")";
				$osastosql = " AND tuote.osasto in $osasto ";
			}
		}

		if (isset($mul_tme) and $mul_tme !="") {
			$tuotemerkki = "";
			foreach ($mul_tme as $key => $value ) {
				$tuotemerkki .= '\''.$value.'\',';
			}
			if (trim($tuotemerkki) != "") {
				$tuotemerkki = "(".substr($tuotemerkki, 0, -1).")";
				$tuotemerkkisql = " AND tuote.tuotemerkki in $tuotemerkki ";
			}
		}

		$edvv = date("Y")-1;
		$vv = date("Y");

		if (!isset($kk1))
			$kk1 = date("m");
		if (!isset($vv1))
			$vv1 = date("Y");
		if (!isset($pp1))
			$pp1 = '01';

		if (!isset($kk2))
			$kk2 = date("m");
		if (!isset($vv2))
			$vv2 = date("Y");
		if (!isset($pp2))
			$pp2 = date("d");

		echo "<table>";
		echo "<th>".t("Osasto")."</th>";
		echo "<th>".t("Tuoteryhm‰")."</th>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Nimitys")."</th>";
		echo "<th>".t("Varastosaldo")."</th>";
		echo "<th>".t("Myyntihinta")."</th>";
		echo "<th>".t("Varmuusvarasto")."</th>";
		echo "<th>".t("Tilattu %s m‰‰r‰",'',"<br>")."</th>";
		echo "<th>".t("Toimitus %s aika",'',"<br>")."</th>";
		echo "<th>".t("Varattu %s saldo",'',"<br>")."</th>";
		echo "<th>$vv ".t("Myynti")."</th>";
		echo "<th>".t("6kk %s Myynti",'',"<br>")."</th>";
		echo "<th>".t("3kk %s Myynti",'',"<br>")."</th>";


		$query = "	SELECT *
					FROM tuote
					WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
					{$osastosql}
					{$trysql}
					{$tuotemerkkisql}
					ORDER BY osasto, try";

		$eresult = pupe_query($query);

		while ($row = mysql_fetch_assoc($eresult)) {

			// ostopuoli
			$query = "	SELECT toimaika,
						sum(varattu) tulossa
						FROM tilausrivi
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND tuoteno = '{$row["tuoteno"]}'
						AND tyyppi = 'O'
						AND kpl = 0
						AND varattu != 0";
			$ostoresult = pupe_query($query);

			if (mysql_num_rows($ostoresult) > 0) {
				$ostorivi = mysql_fetch_assoc($ostoresult);
			}

			// myyntipuoli
			$query = "	SELECT
						round(sum(if(tilausrivi.laskutettuaika >= '{$vv}-01-01', rivihinta, 0)), 2) myyntiVA,
						round(sum(if(tilausrivi.laskutettuaika >= date_sub('{$vv}-{$kk2}-{$pp2}', interval 6 month), rivihinta, 0)), 2) myynti6kk,
						round(sum(if(tilausrivi.laskutettuaika >= date_sub('{$vv}-{$kk2}-{$pp2}', interval 3 month), rivihinta, 0)), 2) myynti3kk
						FROM tilausrivi
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND tuoteno = '{$row["tuoteno"]}'
						AND tyyppi = 'L'
						AND kpl != 0
						AND varattu = 0
						and laskutettuaika >= if(date_sub('{$vv}-{$kk2}-{$pp2}',interval 6 month) < '{$vv}-01-01', date_sub('{$vv}-{$kk2}-{$pp2}',interval 6 month), '{$vv}-01-01')";
			$myyntiresult = pupe_query($query);

			if (mysql_num_rows($myyntiresult) > 0) {
				$myyntirivi = mysql_fetch_assoc($myyntiresult);
			}

			echo "<tr>";
			$osastores = t_avainsana("OSASTO", "", "and avainsana.selite = '$row[osasto]'");
			$osastorow = mysql_fetch_assoc($osastores);
			if ($osastorow == "") {
				$osastorow['selitetark'] = $row['osasto'];
			}

			$tryres = t_avainsana("TRY", "", "and avainsana.selite = '$row[try]'");
			$tryrow = mysql_fetch_assoc($tryres);

			if ($tryrow == "") {
				$tryrow['selitetark'] = $row['try'];
			}

			list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"]);

			$varattu = $saldo - $myytavissa;
			// riviotsikoita
			echo "<td>$osastorow[selitetark]</td>";
			echo "<td>$tryrow[selitetark] </td>";
			echo "<td >$row[tuoteno]</td>";
			echo "<td >$row[nimitys]</td>";
			echo "<td align='right'>$saldo</td>";
			echo "<td align='right'>".round($row['myyntihinta'], $yhtiorow['hintapyoristys'])."</td>";
			echo "<td align='right'>".round($row['varmuus_varasto'], $yhtiorow['hintapyoristys'])."</td>";
			echo "<td align='right'>$ostorivi[tulossa]</td>";
			echo "<td align='right'>".tv1dateconv($ostorivi['toimaika'])."</td>";
			echo "<td align='right'>$varattu</td>";
			echo "<td align='right'>$myyntirivi[myyntiVA]</td>";
			echo "<td align='right'>$myyntirivi[myynti6kk]</td>";
			echo "<td align='right'>$myyntirivi[myynti3kk]</td>";
			echo "</tr>";

		}
		echo "</table>";
		echo "<br>";
	}

	require ("inc/footer.inc");
?>