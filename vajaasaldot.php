<?php
	require ("inc/parametrit.inc");

	echo "<font class=head>".t("Tuotteet joiden tulossa oleva saldo ei riit‰")."</font><hr>";

	// k‰yttis
	echo "<br><form action='$PHP_SELF' action='POST'>";
	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<table>";
	echo "<tr><td><font class='ok'>".t("T‰m‰n kyselyn ajaminen voi kest‰‰ useita minuutteja")."</font></td></tr>";
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
		echo "<th>".t("Vapaa saldo")."</th>";
		echo "<th>".t("Tulossa")."</th>";

		$query = "	SELECT tuote.tuoteno, tuote.try, tuote.osasto, tuote.nimitys
					FROM tuote
					WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
					AND tuote.status not in ('P','X')
					{$osastosql}
					{$trysql}
					{$tuotemerkkisql}
					order by osasto, try";

		$eresult = pupe_query($query);

		while ($row = mysql_fetch_assoc($eresult)) {

			// ostopuoli
			$query = "	SELECT sum(varattu) tulossa
						FROM tilausrivi
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND tyyppi = 'O'
						AND tuoteno = '{$row["tuoteno"]}'
						AND varattu != 0
						AND kpl = 0";
			$ostoresult = pupe_query($query);

			if (mysql_num_rows($ostoresult) > 0) {
				$ostorivi = mysql_fetch_assoc($ostoresult);
			}
			else {
				$ostorivi["tulossa"] = 0;
			}

			list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"]);
			$varattu = $saldo - $myytavissa;			
			
			// tulossa 21 < abs -22 , myytavissa -22 < 0 ja tulossa 21 > 0
			
			if ($ostorivi['tulossa'] < abs($myytavissa) and $myytavissa < 0 and $ostorivi["tulossa"] > 0) {

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

				// riviotsikoita
				echo "<tr>";
				echo "<td>$osastorow[selitetark]</td>";
				echo "<td>$tryrow[selitetark] </td>";
				echo "<td >$row[tuoteno]</td>";
				echo "<td >$row[nimitys]</td>";
				echo "<td align='right'>$saldo </td>";
				echo "<td align='right'>$myytavissa</td>";
				echo "<td align='right'>$ostorivi[tulossa]</td>";
				echo "</tr>";
			}

		}
		echo "</table>";
		echo "<br>";
	}

	require ("inc/footer.inc");
?>