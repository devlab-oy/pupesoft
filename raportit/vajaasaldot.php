<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class=head>".t("Tuotteet joiden tulossa oleva saldo ei riitä")."</font><hr>";

	// käyttis
	echo "<br><form action='$PHP_SELF' method='POST'>";
	echo "<input type='hidden' name='tee' value='raportoi'>";
	echo "<table>";
	echo "<tr><th>".t("Rajaukset")."</th><td>";

	$monivalintalaatikot = array('OSASTO','TRY','TUOTEMERKKI');
	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr>";
	echo "</table>";

	echo "<br><input type='submit' value='".t("Aja raportti")."' name='painoinnappia'>";
	echo "</form>";
	echo "<br><br>";

	if ($tee != "" and isset($painoinnappia)) {

		if ($yhtiorow["varaako_jt_saldoa"] != "") {
			$lisavarattu = " + tilausrivi.varattu";
		}
		else {
			$lisavarattu = "";
		}

		echo "<table>";
		echo "<th>".t("Osasto")."</th>";
		echo "<th>".t("Tuoteryhmä")."</th>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Nimitys")."</th>";
		echo "<th>".t("Varastosaldo")."</th>";
		echo "<th>".t("Vapaa saldo")."</th>";
		echo "<th>".t("Tulossa")."</th>";
		echo "<th>".t("Toimaika")."</th>";

		$query = "	SELECT tuoteno, nimitys, osasto, try
					FROM tuote
					WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
					{$lisa}
					and (tuote.status != 'P' or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
					ORDER BY osasto, try, tuoteno";
		$eresult = pupe_query($query);

		while ($row = mysql_fetch_assoc($eresult)) {

			// ostopuoli
			$query = "	SELECT min(toimaika) toimaika,
						sum(varattu) tulossa
						FROM tilausrivi
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND tuoteno = '{$row["tuoteno"]}'
						AND tyyppi 	= 'O'
						AND varattu > 0";
			$ostoresult = pupe_query($query);
			$ostorivi = mysql_fetch_assoc($ostoresult);

			// Ajetaan saldomyytävissä niin, että JT-rivejä ei huomioida suuntaaan eikä toiseen
			list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], 'JTSPEC');

			$query = "	SELECT sum(jt $lisavarattu) jt
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						WHERE yhtio	= '{$kukarow["yhtio"]}'
						and tyyppi 	in ('L','G')
						and tuoteno	= '{$row["tuoteno"]}'
						and laskutettuaika = '0000-00-00'
						and jt $lisavarattu > 0
						and kpl		= 0
						and var		= 'J'";
			$juresult = pupe_query($query);
			$jurow    = mysql_fetch_assoc($juresult);

			if ($myytavissa - $jurow["jt"] + $ostorivi["tulossa"] < 0) {

				$osastores = t_avainsana("OSASTO", "", "and avainsana.selite ='$row[osasto]'");
				$osastorow = mysql_fetch_assoc($osastores);

				if ($osastorow['selitetark'] != "") $row['osasto'] = $row['osasto']." - ".$osastorow['selitetark'];

				$tryres = t_avainsana("TRY", "", "and avainsana.selite ='$row[try]'");
				$tryrow = mysql_fetch_assoc($tryres);

				if ($tryrow['selitetark'] != "") $row['try'] = $row['try']." - ".$tryrow['selitetark'];

				echo "<tr>";
				echo "<td>$row[osasto]</td>";
				echo "<td>$row[try]</td>";
				echo "<td><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
				echo "<td>$row[nimitys]</td>";
				echo "<td align='right'>$saldo</td>";
				echo "<td align='right'>".($myytavissa - $jurow["jt"])."</td>";
				echo "<td align='right'>$ostorivi[tulossa]</td>";
				echo "<td>$ostorivi[toimaika]</td>";
				echo "</tr>";
			}
		}

		echo "</table>";
	}

	require ("inc/footer.inc");
?>