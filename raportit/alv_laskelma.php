<?php

	require("../inc/parametrit.inc");

   	echo "<font class='head'>".t("ALV-laskelma")."</font><hr>";

	// tehdään käyttöliittymä, näytetään aina
	echo "<form action = 'alv_laskelma.php' method='post'>";
	echo "<input type = 'hidden' name = 'tee' value = 'aja'>";
	echo "<table>";

	if (!isset($vv)) $vv = date("Y");
	if (!isset($kk)) $kk = date("n");

	echo "<tr>";
	echo "<th>".t("Valitse kausi")."</th>";
	echo "<td>";

	$sel = array();
	$sel[$vv] = "SELECTED";

	echo "<select name='vv'>";
	for ($i = date("Y"); $i >= date("Y")-4; $i--) {
		echo "<option value='$i' $sel[$i]>$i</option>";
	}
	echo "</select>";

	$sel = array();
	$sel[$kk] = "SELECTED";

	echo "<select name='kk'>
			<option $sel[1] value = '1'>01</option>
			<option $sel[2] value = '2'>02</option>
			<option $sel[3] value = '3'>03</option>
			<option $sel[4] value = '4'>04</option>
			<option $sel[5] value = '5'>05</option>
			<option $sel[6] value = '6'>06</option>
			<option $sel[7] value = '7'>07</option>
			<option $sel[8] value = '8'>08</option>
			<option $sel[9] value = '9'>09</option>
			<option $sel[10] value = '10'>10</option>
			<option $sel[11] value = '11'>11</option>
			<option $sel[12] value = '12'>12</option>
			</select>";
	echo "</td>";

	echo "<td class='back'><input type = 'submit' value = '".t("Näytä")."'></td>";

	echo "</tr>";
	echo "</table>";

	echo "</form><br>";

	if ($tee == "aja") {

		// edellinen taso
		$taso     = array();
		$tasonimi = array();
		$summa    = array();
		$verot    = array();
		$kappa    = array();

		$startmonth	= date("Y-m-d", mktime(0, 0, 0, $kk,   1, $vv));
		$endmonth 	= date("Y-m-d", mktime(0, 0, 0, $kk+1, 0, $vv));

		$query = "	SELECT *
					FROM taso
					WHERE yhtio = '$kukarow[yhtio]'
					AND	tyyppi = 'A'
					AND taso != ''
					ORDER BY taso";
		$tasores = mysql_query($query) or pupe_error($query);

		while ($tasorow = mysql_fetch_array($tasores)) {

			// millä tasolla ollaan (1,2,3,4,5,6)
			$tasoluku = strlen($tasorow["taso"]);

			// tasonimi talteen (rightpäddätään Ö:llä, niin saadaan oikeaan järjestykseen)
			$apusort = str_pad($tasorow["taso"], 20, "Ö");
			$tasonimi[$apusort] = $tasorow["nimi"];

			// pilkotaan taso osiin
			$taso = array();
			for ($i=0; $i < $tasoluku; $i++) {
				$taso[$i] = substr($tasorow["taso"], 0, $i+1);
			}

			$query = "	SELECT round(sum(tiliointi.summa * vero / 100) * -1, 2) summa,
						round(sum(tiliointi.summa * (1 + vero / 100)) * -1, 2) verollinensumma,
						count(*) kpl
					 	FROM tili
						JOIN tiliointi USE INDEX (yhtio_tilino_tapvm) ON (tiliointi.yhtio = tili.yhtio AND tiliointi.tilino = tili.tilino AND tiliointi.korjattu = '' AND tiliointi.vero != 0 AND tiliointi.tapvm >= '$startmonth' AND tiliointi.tapvm <= '$endmonth')
						WHERE tili.yhtio = '$kukarow[yhtio]'
						AND tili.alv_taso = '$tasorow[taso]'
						AND tili.alv_taso != ''
						GROUP BY tili.alv_taso";
			$tilires = mysql_query($query) or pupe_error($query);

			while ($tilirow = mysql_fetch_array ($tilires)) {
				// summataan tän plus pienempien tasojen saldot
				for ($i = $tasoluku - 1; $i >= 0; $i--) {
					$summa[$taso[$i]] += $tilirow["verollinensumma"];
					$verot[$taso[$i]] += $tilirow["summa"];
					$kappa[$taso[$i]] += $tilirow["kpl"];
				}
			}

		}

		echo "<table>";
		echo "<tr>";
		echo "<th>Tilino</th>";
		echo "<th>Alv%</th>";
		echo "<th>Verollinen summa</th>";
		echo "<th>Veron määrä</th>";
		echo "<th>Tiliöintejä</th>";
		echo "</tr>\n";

		// sortataan array indexin (tason) mukaan
		ksort($tasonimi);

		// loopataan tasot läpi
		foreach ($tasonimi as $key => $value) {

			$key = str_replace("Ö", "", $key); // Ö-kirjaimet pois

			$tilirivi = "";

			// haetaan kaikki summat per tili
			$query = "SELECT * FROM tili WHERE yhtio = '$kukarow[yhtio]' and alv_taso = '$key'";
			$tilires = mysql_query($query) or pupe_error($query);

			while ($tilirow = mysql_fetch_array($tilires)) {
				$query = "	SELECT tilino, vero,
							round(sum(tiliointi.summa * (1 + vero / 100)) * -1, 2) verollinensumma,
							round(sum(tiliointi.summa * vero / 100) * -1, 2) verot,
							count(*) kpl
							FROM tiliointi USE INDEX (yhtio_tilino_tapvm)
							WHERE yhtio = '$kukarow[yhtio]'
							AND tilino = '$tilirow[tilino]'
							AND korjattu = ''
							AND vero != 0
							AND tapvm >= '$startmonth'
							AND tapvm <= '$endmonth'
							GROUP BY tilino, vero";
				$summares = mysql_query($query) or pupe_error($query);

				while ($summarow = mysql_fetch_array($summares)) {
					$tilirivi .= "<tr>";
					$tilirivi .= "<td nowrap>$tilirow[tilino] - $tilirow[nimi]</td>";
					$tilirivi .= "<td nowrap align='right'>$summarow[vero]</td>";
					$tilirivi .= "<td nowrap align='right'>$summarow[verollinensumma]</td>";
					$tilirivi .= "<td nowrap align='right'>$summarow[verot]</td>";
					$tilirivi .= "<td nowrap align='right'>$summarow[kpl]</td>";
					$tilirivi .= "</tr>\n";
				}
			}

			$query = "	SELECT summattava_taso
						FROM taso
						WHERE yhtio 		 = '$kukarow[yhtio]'
						and taso 			 = '$key'
						and summattava_taso != ''
						and tyyppi 			 = 'A'";
			$summares = mysql_query($query) or pupe_error($query);

			if ($summarow = mysql_fetch_array ($summares)) {
				foreach(explode(",", $summarow["summattava_taso"]) as $staso) {
					$staso = trim($staso);
					$summa[$key] = $summa[$key] + $summa[$staso];
					$verot[$key] = $verot[$key] + $verot[$staso];
					$kappa[$key] = $kappa[$key] + $kappa[$staso];
				}
			}

			$rivi  = "<tr class='aktiivi'>";
			$rivi .= "<th nowrap>$value</th>";
			$rivi .= "<th></th>";
			$rivi .= "<th style='text-align:right;'>$summa[$key]</th>";
			$rivi .= "<th style='text-align:right;'>$verot[$key]</th>";
			$rivi .= "<th style='text-align:right;'>$kappa[$key]</th>";
			$rivi .= "</tr>\n";

			// jos oli summa != 0 niin tulostetaan rivi
			if ($summa[$key] != 0 or $verot[$key] != 0 or $kappa[$key] != 0) {
				echo $tilirivi, $rivi;
			}

		}

		echo "</table>";
	}

	require("inc/footer.inc");

?>