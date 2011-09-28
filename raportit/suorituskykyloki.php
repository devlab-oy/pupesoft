<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Suorituskykylokiraportointi")."<hr></font>";
	echo "<br><br>";

	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	if (!isset($rekuesti)) $rekuesti = "";

	echo "<form action = '$PHP_SELF' method = 'post'>
			<input type='hidden' name='tee' value='listaa'>";

	echo "<table>";
	echo "<tr><th>".t("Alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr>
			<tr><th>".t("loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>
			</tr>";

	echo "<tr><th>".t("K‰ytt‰j‰")."</th>
			<td colspan='3'><select name='kuka'><option value=''>".t("Valitse k‰ytt‰j‰")."</option>";

	$query  = "	SELECT distinct kuka, nimi, eposti
				FROM kuka
				WHERE yhtio = '$kukarow[yhtio]'
				and extranet = ''
				ORDER BY nimi";
	$res = mysql_query($query) or pupe_error($query);

	while ($row = mysql_fetch_array($res)) {

		$sel = "";

		if (isset($kuka) and $kuka == $row["kuka"]) {
			$sel = "SELECTED";
		}

		echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
	}

	echo "</select></td></tr>";

	echo "<tr><th>".t("Ohjelma")."</th>
			<td colspan='3'><select name='skripti'><option value=''>".t("Valitse ohjelma")."</option>";

	$query = "	SELECT sovellus, nimi, alanimi, min(nimitys) nimitys, min(jarjestys) jarjestys, min(jarjestys2) jarjestys2, max(hidden) hidden
				FROM oikeu
				WHERE yhtio = '$kukarow[yhtio]'
				and kuka = ''
				and profiili = ''
				GROUP BY sovellus, nimi, alanimi
				ORDER BY sovellus, jarjestys, jarjestys2";
	$res = mysql_query($query) or pupe_error($query);

	while ($row = mysql_fetch_array($res)) {

		$sel = "";

		if (isset($skripti) and $skripti == $row["sovellus"]."###".$row["nimi"]."###".$row["alanimi"]) {
			$sel = "SELECTED";
		}

		echo "<option value='$row[sovellus]###$row[nimi]###$row[alanimi]' $sel>$row[sovellus] --> $row[nimitys]</option>";
	}

	echo "</select></td></tr>";

	echo "<tr><th>".t("Request-haku")."</th>
			<td colspan='3'><input type='text' name='rekuesti' value='$rekuesti' size='30'></td></tr>";

	echo "</table><br>";
	echo "<input type='submit' value='".t("Listaa")."'>";
	echo "</form><br><br>";

	if (isset($tee) and $tee == 'listaa') {

		function avaa_array ($arrayi) {
			foreach ($arrayi as $muuttuja => $arvo) {
				echo "<table>";
				echo "<tr><td class='spec'>$muuttuja</td><td class='spec'>$arvo</td></tr>";
				echo "</table>";
			}
		}

		// Serverin polku, t‰st‰ saadaan prefixi suorituskykylokiin
		$polku = str_ireplace("raportit/suorituskykyloki.php", "", $_SERVER['SCRIPT_NAME']);
		$polku = "/";

		$skriptilisa = "";

		if ($skripti != "") {

			list($sovellus,$nimi,$alanimi) = explode("###", $skripti);

			$skriptilisa .= " and suorituskykyloki.skripti like '%$polku$nimi' ";

			if ($alanimi != "") {
				$skriptilisa .= " and suorituskykyloki.request like '%s:4:\"toim\";s:".strlen($alanimi).":\"$alanimi\"%' ";
			}
		}

		if (isset($rekuesti) and $rekuesti != "") {
			$skriptilisa .= " and request like '%".mysql_real_escape_string($rekuesti)."%' ";
		}

		if (isset($kuka) and $kuka != "") {
			$skriptilisa .= " and suorituskykyloki.laatija = '$kuka' ";
		}

		$query = "	SELECT suorituskykyloki.*, kuka.nimi kukanimi
					FROM suorituskykyloki
					LEFT JOIN kuka on suorituskykyloki.yhtio=kuka.yhtio and suorituskykyloki.laatija=kuka.kuka
					WHERE suorituskykyloki.yhtio = '$kukarow[yhtio]'
					$skriptilisa
					and suorituskykyloki.luontiaika >= '$vva-$kka-$ppa 00:00:00'
					and suorituskykyloki.luontiaika <= '$vvl-$kkl-$ppl 23:59:59'
					ORDER BY suorituskykyloki.luontiaika
					LIMIT 500";
		$res = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr><th>".t("Pvm")."</th><th>".t("K‰ytt‰j‰")."</th><th>".t("Ohjelma")."</th><th>".t("Muuttujat")."</th></tr>";

		while ($row = mysql_fetch_array($res)) {

			$requrest = unserialize($row["request"]);

			echo "<tr><td>".tv1dateconv($row["luontiaika"], "P")."</td><td>$row[kukanimi]</td><td>$row[skripti]</td><td>";

			echo "<table>";

			foreach ($requrest as $muuttuja => $arvo) {
				echo "<tr><td class='spec'>$muuttuja</td><td class='spec'>";

				if (is_array($arvo)) avaa_array($arvo);
				else echo $arvo;

				echo "</td></tr>";
			}

			echo "</table>";
			echo "</td></tr>";
		}

		echo "</table>";
	}

	require ("inc/footer.inc");
?>