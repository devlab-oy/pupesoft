<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Myynnit päivittäin asiakasosastoittain")."</font><hr>";

	if (!isset($vuosi)) $vuosi = date("Y");
	
	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<table>";	
	echo "<tr>";
	echo "<th>".t("Anna vuosi")."</th>";
	echo "<td><input type='text' name='vuosi' value='$vuosi' size='5'></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";

	if ($tee != '') {

			$query = "SELECT DISTINCT osasto FROM asiakas WHERE yhtio = '$kukarow[yhtio]' order by osasto";
			$result = mysql_query($query) or pupe_error($query);

			// haetaan kaikki osasto arrayseen
			$osastot = array();
			$tapvm = array();
			$kate = array();
			$myynt = array();
			$katepro = array();

			while ($ressu = mysql_fetch_array($result)) {
				$osastot[] = $ressu['osasto'];
			}

			$query = "	SELECT osasto, 
						date_format(tapvm, '%j') pvm, 
						tapvm, 
						sum(kate) kate, 
						sum(arvo) myynti,
						sum(kate) / sum(arvo) * 100 katepro
						FROM lasku use index (yhtio_tila_tapvm)
						JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						AND lasku.tapvm >= '$vuosi-01-01'
						AND lasku.tapvm <= '$vuosi-12-31'
						AND lasku.tila = 'u'
						AND lasku.alatila = 'x'
						GROUP BY 1,2,3
						ORDER BY 1,2,3";
			$result = mysql_query($query) or pupe_error($query);

			while ($ressu = mysql_fetch_array($result)) {
				$apu = (int) $ressu['pvm'];
				$osastoapu = $ressu['osasto'];
				$tapvm[$apu] = $ressu['tapvm'];
				$kate[$osastoapu][$apu]	= $ressu['kate'];
				$myynt[$osastoapu][$apu] = $ressu['myynti'];
				$katepro[$osastoapu][$apu] = $ressu['katepro'];				
			}

			echo "<table>";

			echo "<tr>";
			echo "<th>".t("pvm")."</th>";
			foreach ($osastot as $osasto) {
					echo "<th>$osasto ".t("Myynti")."</th>";
					echo "<th>$osasto ".t("Kate")."</th>";
					echo "<th>$osasto ".t("Kate%")."</th>";
			}
			echo "</tr>";

			for ($i=1; $i<367; $i++) {
				echo "<tr class='aktiivi'>";
				echo "<td>";
				if (strlen($tapvm[$i]) == 0) echo tv1dateconv(date("Y-m-d", mktime(0,0,0,1,$i,$vuosi)));
				else echo tv1dateconv($tapvm[$i]);
				echo "</td>";

				foreach ($osastot as $osasto) {
					$apu_myynt = $apu_kate = $apu_katepro = "";
					
					if ($myynt[$osasto][$i] != 0) $apu_myynt = sprintf("%.02f", $myynt[$osasto][$i]);
					if ($kate[$osasto][$i] != 0) $apu_kate = sprintf("%.02f", $kate[$osasto][$i]);
					if ($katepro[$osasto][$i] != 0) $apu_katepro = sprintf("%.02f", $katepro[$osasto][$i]);
					
					echo "<td nowrap style='text-align:right'>$apu_myynt</td>";
					echo "<td nowrap style='text-align:right'>$apu_kate</td>";
					echo "<td nowrap style='text-align:right'>$apu_katepro</td>";
				}
				echo "</tr>";
			}

			echo "</table>";
	}

	require ("inc/footer.inc");

?>