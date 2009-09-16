<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Myyjien myynnit").":</font><hr>";

	//Käyttöliittymä
	if (!isset($vv)) $vv = date("Y");

	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Syötä vuosi (vvvv)")."</th>";
	echo "<td><input type='text' name='vv' value='$vv' size='5'></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";

	if ($tee != '') {

		$vvl = $vv + 1;

		//myynnit vuoden alusta
		$query = "	SELECT lasku.myyja, kuka.nimi, month(tapvm) kk, round(sum(arvo),0) summa, round(sum(kate),0) kate
					FROM lasku use index (yhtio_tila_tapvm)
					LEFT JOIN kuka on kuka.yhtio = lasku.yhtio and kuka.tunnus = lasku.myyja
					WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'L' and lasku.alatila = 'X' and tapvm >= '$vv-01-01' and tapvm < '$vvl-01-01'
					GROUP BY myyja, nimi, kk
					HAVING summa <> 0 or kate <> 0
					ORDER BY myyja";
		$result3 = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Nimi")."</th>";
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

		$summa = array();
		$kate = array();
		$lask = 0;

		while ($row = mysql_fetch_array ($result3)) {

			if ($lask == 0 or $edlaatija != $row["myyja"]) {
				$lask++;
				$nimi[$lask] = $row["nimi"]."(".$row["myyja"].")";
			}

			$kk = $row["kk"];
			$kate[$lask][$kk]  = $row["kate"];
			$summa[$lask][$kk] = $row["summa"];

			$edlaatija = $row["myyja"];
		}

		$lask--;

		$koksumyht = $kokkatyht = '';

		for ($i=1; $i<=$lask+1; $i++) {

			$sumyht = $katyht = '';

			echo "<tr class='aktiivi'><td>$nimi[$i]</td>";
			for ($j=1; $j<13; $j++) {

				echo "<td align='right'>";

				if ($summa[$i][$j] != 0.00 or $kate[$i][$j] != 0.00) {
					echo str_replace(".",",",$summa[$i][$j]);
					echo "<br>";
					echo str_replace(".",",",$kate[$i][$j]);

					$sumyht += $summa[$i][$j];
					$katyht += $kate[$i][$j];
					$koksumyht += $summa[$i][$j];
					$kokkatyht += $kate[$i][$j];					
				}
				echo "</td>";
			}
			echo "<td align='right'>".str_replace(".",",",$sumyht)."<br>".str_replace(".",",",$katyht)."</td>";
			echo "</tr>";
		}

		echo "<tr><th>".t("Yhteensä")."</th>";
		
		// yhteensärivit
		for ($j=1; $j<13; $j++) {

			$sumyht = $katyht = $kateproyht = '';
			for ($i=1; $i<=$lask+1; $i++) {
				if ($summa[$i][$j] != 0.00 or $kate[$i][$j] != 0.00) {
					$sumyht += $summa[$i][$j];
					$katyht += $kate[$i][$j];
				}
			}

			if ($sumyht != 0) {
				$kateproyht = round($katyht / $sumyht * 100). "%";
			}

			echo "<th style='text-align:right'>".str_replace(".",",",$sumyht)."<br>".str_replace(".",",",$katyht)."<br>".str_replace(".",",",$kateproyht)."</th>";
		}

		echo "<th style='text-align:right'>$koksumyht<br>$kokkatyht<br>".str_replace(".",",", round($kokkatyht / $koksumyht * 100))."%</th>";
		echo "</tr>";
	}

	echo "</table>";

	require ("inc/footer.inc");
?>