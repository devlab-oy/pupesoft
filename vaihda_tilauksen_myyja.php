<?php

	require ("inc/parametrit.inc");

	if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";

		require ("raportit/naytatilaus.inc");
		echo "<hr>";
	}

	if ($tee == 'PAIVITAMYYJA') {
		$query = "	UPDATE lasku
					SET myyja = '$myyja'
					WHERE yhtio = '{$kukarow['yhtio']}'
					and tunnus	= '$tunnus'
					and tila 	= 'L'
					and alatila = 'X'";
		$result = pupe_query($query);
	}

	echo "<font class='head'>".t("Vaihda tilauksen myyjä").":</font><hr>";
	echo "<form method='post'>";
	echo "<table>";

	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr>";
	echo "<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
	echo "</tr>";

	$query = "	SELECT
				lasku.tunnus,
				lasku.laskunro,
				concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas,
				lasku.summa,
				lasku.viesti tilausviite,
				lasku.myyja,
				kuka.nimi
				FROM lasku
				LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				and lasku.tila 	  = 'L'
				and lasku.alatila = 'X'
				and lasku.tapvm  >= '$vva-$kka-$ppa'
				and lasku.tapvm  <= '$vvl-$kkl-$ppl'
				ORDER BY 1";
	$result = pupe_query($query);

	echo "</table><br>";

	echo "<input type='submit' value='".t("Näytä tilaukset")."'>";
	echo "</form><br><br>";

	if (mysql_num_rows($result) > 0) {


		$query = "	SELECT kuka.tunnus, kuka.kuka, kuka.nimi, kuka.myyja, kuka.asema
					FROM kuka
					WHERE kuka.yhtio = '$kukarow[yhtio]'
					ORDER BY kuka.nimi";
		$myyjares = pupe_query($query);

		echo "<br>";
		echo "<table>";
		echo "<thead>";
		echo "<tr>";

		for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
			echo "<th>".t(mysql_field_name($result,$i))."</th>";
		}

		echo "<th>".t("Myyjä")."</th>";

		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";

		while ($row = mysql_fetch_array($result)) {

			echo "<tr>";

			$class = "";
			if ($tunnus == $row['tunnus']) {
				$class = " class='tumma' ";
			}

			for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
				echo "<td valign='top' $class>{$row[$i]}</td>";
			}

			echo "<td valign='top' $class><a name='$row[tunnus]'>";

			echo "<form method='post' action='#$row[tunnus]'>
					<input type='hidden' name='tee' 	value = 'PAIVITAMYYJA'>
					<input type='hidden' name='tunnus' 	value = '$row[tunnus]'>
					<input type='hidden' name='ppa' 	value='$ppa'>
					<input type='hidden' name='kka' 	value='$kka'>
					<input type='hidden' name='vva' 	value='$vva'>
					<input type='hidden' name='ppl' 	value='$ppl'>
					<input type='hidden' name='kkl' 	value='$kkl'>
					<input type='hidden' name='vvl' 	value='$vvl'>";

			echo "<select name='myyja' onchange='submit();'>";

			mysql_data_seek($myyjares, 0);

			while ($myyjarow = mysql_fetch_assoc($myyjares)) {
				$sel = "";

				if ($myyjarow['tunnus'] == $row['myyja']) {
					$sel = 'selected';
				}

				echo "<option value='$myyjarow[tunnus]' $sel>$myyjarow[nimi]</option>";
			}

			echo "</select></form></td>";

			echo "<td class='back' valign='top'>
					<form method='post'>
					<input type='hidden' name='tee' 	value = 'NAYTATILAUS'>
					<input type='hidden' name='tunnus' 	value = '$row[tunnus]'>
					<input type='hidden' name='ppa' 	value='$ppa'>
					<input type='hidden' name='kka' 	value='$kka'>
					<input type='hidden' name='vva' 	value='$vva'>
					<input type='hidden' name='ppl' 	value='$ppl'>
					<input type='hidden' name='kkl' 	value='$kkl'>
					<input type='hidden' name='vvl' 	value='$vvl'>
					<input type='submit' value='".t("Näytä tilaus")."'>
					</form></td>";

			echo "</tr>";
		}

		echo "</tbody>";
		echo "</table>";
	}
	else {
		echo t("Ei tilauksia")."...<br><br>";
	}

	require ("inc/footer.inc");
?>