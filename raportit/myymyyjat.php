<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Myyjien myynnit").":</font><hr>";

	if ($tee != '') {

		//myynnit vuoden alusta
		$query = "	SELECT sum(summa) summa, sum(kate) kate, lasku.laatija, month(tapvm) kk, kuka.nimi
					FROM lasku use index (yhtio_tila_tapvm)
					LEFT JOIN kuka on kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.laatija
					WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tila='U' and lasku.alatila='X' and tapvm >= '$vv-01-01' and tapvm <= '$vv-12-31'
					GROUP BY 3,4,5
					HAVING summa <> 0 or kate <> 0
					ORDER BY laatija";
		$result3 = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr><th>".t("Nimi")."</th>";
		echo "<th>".t("Tammi")."</th>";
		echo "<th>".t("Helmi")."</th>";
		echo "<th>".t("Maalis")."</th>";
		echo "<th>".t("Huhti")."</th>";
		echo "<th>".t("Touko")."</th>";
		echo "<th>".t("Kesa")."</th>";
		echo "<th>".t("Heina")."</th>";
		echo "<th>".t("Elo")."</th>";
		echo "<th>".t("Syys")."</th>";
		echo "<th>".t("Loka")."</th>";
		echo "<th>".t("Marras")."</th>";
		echo "<th>".t("Joulu")."</th>";
		echo "<th>".t("Yhteensä")."</th></tr>";




		$summa= array();
		$kate = array();
		$lask = 0;

		while ($row=mysql_fetch_array ($result3)) {
			if ($lask == 0 or $edlaatija != $row["laatija"]) {
				$lask++;
				$nimi[$lask] = $row["nimi"]."(".$row["laatija"].")";
			}

			$kk = $row["kk"];
			$kate[$lask][$kk]  = $row["kate"];
			$summa[$lask][$kk] = $row["summa"];

			$edlaatija = $row["laatija"];
		}
		$lask--;

		for ($i=1; $i<=$lask+1; $i++) {
			$sumyht=$katyht='';

			echo "<tr><td>$nimi[$i]</td>";
			for ($j=1; $j<13; $j++) {
				echo "<td align='right'>";
				if ($summa[$i][$j] != 0.00 or $kate[$i][$j] != 0.00) {
					echo str_replace(".",",",$summa[$i][$j]);
					echo "<br>";
					echo str_replace(".",",",$kate[$i][$j]);

					$sumyht += $summa[$i][$j];
					$katyht += $kate[$i][$j];
				}
				echo "</td>";
			}
			echo "<td>".str_replace(".",",",$sumyht)."<br>".str_replace(".",",",$katyht)."</td>";
			echo "</tr>";
		}
	}
	echo "</table><br>";

	//Käyttöliittymä
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";


	if (!isset($vv))
		$vv = date("Y");

	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<tr><th>".t("Syötä vuosi (vvvv)")."</th><td><input type='text' name='vv' value='$vv' size='5'></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";

	require ("../inc/footer.inc");
?>