<?php
    echo "<font class='head'>".t("Tilien summat kuukausittain")."<hr></font>";

	if ($tee == '') {
		echo "<form action = 'raportit.php' method='post'>
				<input type = 'hidden' name = 'toim' value = 'tilitkk'>
				<input type = 'hidden' name = 'tee' value = '1'>
				<table><tr>
				<td>".t("Ajalta")."</td>
				<td><select name='alvv'>
				";

		for ($i = date("Y"); $i >= date("Y")-4; $i--) {
			if ($i == date("Y")) $sel = "selected";
			else $sel = "";
			echo "<option value='$i' $sel>$i</option>";
		}

		echo "
		</select>
		</td></tr>";

		echo "<tr><td>".t("Vain kustannuspaikka")."</td>";
		$query = "SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<td><select name='kustp'><option value=' '>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		echo "</select></td>";
		echo "</tr>";
		echo "<tr><td>".t("Vain kohde")."</td>";
		$query = "SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<td><select name='kohde'><option value=' '>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		echo "</select></td>";
		echo "</tr>";
		echo "<tr><td>".t("Vain projekti")."</td>";
		$query = "SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<td><select name='proj'><option value=' '>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow[0]) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[0]' $sel>$vrow[1]";
		}
		echo "</select></td>";
		echo "</tr>";
		echo "<tr><td></td>
		      <td><input type = 'submit' value = '".t("Näytä")."'></form></td><tr></table>";
		exit;
	}

	$timeparts = explode(" ",microtime());
	$phptime = $timeparts[1].substr($timeparts[0],1);

// Onko meillä lisärajoitteita??

	$lisa='';
	$teksti='';
	if (strlen(trim($kustp)) > 0) {
		$lisa .= " and tiliointi.kustp = '" . $kustp . "'";
		$teksti.=" - Kustannuspaikka $kustp";
	}
	if (strlen(trim($proj)) > 0) {
		$lisa .= " and tiliointi.projekti = '" . $proj . "'";
		$teksti.=" - Projekti $proj";
	}
	if (strlen(trim($kohde)) > 0) {
		$lisa .= " and tiliointi.kohde = '" . $kohde . "'";
		$teksti.=" - Kohde $kohde";
	}

// Mita tileja loytyy?

	$query = "SELECT sisainen_taso, tilino, nimi
			FROM tili
			WHERE yhtio='$kukarow[yhtio]'
			ORDER BY tilino";
	$result = mysql_query($query) or pupe_error($query);

	echo "<font class='head'>".t("Vuosi")." $alvv $teksti</font>";
	echo "<table>";

	echo "<tr><th>".t("taso")."</th><th>".t("tili")."</th><th>".t("nimi")."</th><th>".t("tammi")."</th><th>".t("helmi")."</th><th>".t("maalis")."</th><th>".t("huhti")."</th><th>".t("touko")."</th><th>".t("kesä")."</th><th>".t("heinä")."</th><th>".t("elo")."</th><th>".t("syys")."</th><th>".t("loka")."</th><th>".t("marras")."</th><th>".t("joulu")."</th></tr>";

	while ($tilirow=mysql_fetch_array($result)) {

		// haetaan kaikki, summataan phpssä..

		$alku  = $alvv."-01-01";
		$loppu = $alvv*1+1 ."-01-01";

		$query = "SELECT DATE_FORMAT(tapvm, '%Y-%m') kausi, tiliointi.tilino, sum(tiliointi.summa) summa
					 FROM tiliointi
					 WHERE tilino = '$tilirow[tilino]'
					   and yhtio  = '$kukarow[yhtio]'
					   and korjattu=''
					   and tapvm>='$alku' and tapvm<'$loppu'
					   $lisa
					 GROUP BY DATE_FORMAT(tapvm, '%Y-%m')";
		$aresult = mysql_query($query) or pupe_error($query);

		$kausi='';
		$summa='';

		while ($tiliointirow=mysql_fetch_array ($aresult)) {
			$kk = substr($tiliointirow['kausi'],5,2)*1;
			$kausi[$kk]=$tiliointirow['kausi'];
			$summa[$kk]=$tiliointirow['summa'];
		}

		echo "<tr><td>$tilirow[sisainen_taso]</td><td>$tilirow[tilino]</td><td>$tilirow[nimi]</td>";

		for ($i=1; $i<13; $i++) {
			echo "<td align='right'>";
			echo str_replace(".",",",sprintf("%.2f", $summa[$i]));
			echo "</td>";
		}

		echo "</tr>";

	} // Tili

	echo "<table>";
?>
