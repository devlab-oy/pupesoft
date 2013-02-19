<?php

	require ("inc/parametrit.inc");

	enable_ajax();

	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	if (!isset($sopparit))   $sopparit = "";
	if (!isset($riveittain)) $riveittain = "";
	if (!isset($tee)) 		 $tee = "";

	if ($tee == 'PAIVITAMYYJA' and (int) $tunnus > 0 and (int) $myyja > 0) {

		$query = "	UPDATE lasku
					SET myyja = '$myyja'
					WHERE yhtio = '{$kukarow['yhtio']}'
					and tunnus	= '$tunnus'
					and tila 	= 'L'
					and alatila = 'X'";
		pupe_query($query);

		die("Myyjä päivitetty!");
	}

	if ($tee == 'PAIVITARIVIMYYJA' and (int) $rivitunnus > 0 and $myyja != "") {

		$query = "	UPDATE tilausrivin_lisatiedot
					SET positio = '$myyja'
					WHERE yhtio = '{$kukarow['yhtio']}'
					and tilausrivitunnus = '$rivitunnus'";
		pupe_query($query);

		die("Myyjä päivitetty!");
	}

	if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";

		require ("raportit/naytatilaus.inc");
		echo "<hr>";
	}

	echo "<font class='head'>".t("Vaihda tilauksen myyjä").":</font><hr>";
	echo "<form method='post'>";
	echo "<table>";

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

	$rukchk = "";
	if ($sopparit  != '') $rukchk = "CHECKED";

	echo "<tr><th>".t("Piilota ylläpitosopimukset")."</th>
			<td colspan='3'><input type='checkbox' name='sopparit' value='YLLARI' $rukchk></td>";
	echo "</tr>";

	$rukchk = "";
	if ($riveittain  != '') $rukchk = "CHECKED";

	echo "<tr><th>".t("Riveittäin")."</th>
			<td colspan='3'><input type='checkbox' name='riveittain' value='RIVI' $rukchk></td>";
	echo "</tr>";

	$soplisa = "";

	if ($sopparit != "") {
		$soplisa = " AND lasku.clearing != 'sopimus' ";
	}

	$rivilisa = "";
	$rivijoin = "";

	if ($riveittain != "") {
		$rivilisa = " tilausrivi.kommentti rivikommetti, tilausrivi.tunnus rivitunnus, tilausrivin_lisatiedot.positio, ";
		$rivijoin = " JOIN tilausrivi ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi='L')
					  JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus) ";
	}

	$query = "	SELECT
				lasku.tunnus,
				lasku.laskunro,
				concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas,
				lasku.summa,
				lasku.viesti tilausviite,
				$rivilisa
				lasku.myyja,
				kuka.nimi
				FROM lasku
				$rivijoin
				LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				and lasku.tila 	  = 'L'
				and lasku.alatila = 'X'
				and lasku.tapvm  >= '$vva-$kka-$ppa'
				and lasku.tapvm  <= '$vvl-$kkl-$ppl'
				{$soplisa}
				ORDER BY 1";
	$result = pupe_query($query);

	echo "</table><br>";

	echo "<input type='submit' value='".t("Näytä tilaukset")."'>";
	echo "</form><br><br>";

	if (mysql_num_rows($result) > 0) {

		if ($riveittain != "") {
			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						AND laji    = 'TRIVITYYPPI'
 						ORDER BY selitetark";
			$myyjares = pupe_query($query);
		}
		else {
			$query = "	SELECT kuka.tunnus, kuka.kuka, kuka.nimi, kuka.myyja, kuka.asema
						FROM kuka
						WHERE kuka.yhtio = '$kukarow[yhtio]'
						ORDER BY kuka.nimi";
			$myyjares = pupe_query($query);
		}

		echo "<br>";
		echo "<table>";
		echo "<thead>";
		echo "<tr>";
		echo "<th>".t("Tilausnumero")."</th>";
		echo "<th>".t("Laskunro")."</th>";
		echo "<th>".t("Asiakas")."</th>";
		echo "<th>".t("Summa")."</th>";
		echo "<th>".t("Tilausviite")."</th>";
		echo "<th>".t("Myyjä")."</th>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";

		while ($row = mysql_fetch_array($result)) {

			echo "<tr>";

			$class = "";
			if (isset($tunnus) and $tunnus == $row['tunnus']) {
				$class = " class='tumma' ";
			}

			echo "<td valign='top' $class>{$row['tunnus']}</td>";
			echo "<td valign='top' $class>{$row['laskunro']}</td>";
			echo "<td valign='top' $class>{$row['asiakas']}</td>";
			echo "<td valign='top' align='right' $class>{$row['summa']}</td>";
			echo "<td valign='top' $class>{$row['tilausviite']}";

			if ($riveittain != "") {
				if ($row['tilausviite'] != "") echo "<br>";
				echo "{$row['rivikommetti']}";
			}
			else {
				$query = "	SELECT DISTINCT kommentti
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							AND otunnus = '$row[tunnus]'
							and tyyppi  = 'L'
							AND kommentti != ''";
				$kommres = pupe_query($query);

				while ($kommrow = mysql_fetch_assoc($kommres)) {
					echo "<br>{$kommrow['kommentti']}";
				}
			}

			echo "</td>";

			echo "<td valign='top' $class><a name='$row[tunnus]'>";

			if ($riveittain != "") {
				echo "<form name='myyjaformi_$row[rivitunnus]' id='myyjaformi_$row[rivitunnus]' action=\"javascript:ajaxPost('myyjaformi_$row[rivitunnus]', '{$palvelin2}vaihda_tilauksen_myyja.php?', 'div_$row[rivitunnus]', '', '', '', 'post');\" method='POST'>
						<input type='hidden' name='tee' 	value = 'PAIVITARIVIMYYJA'>
						<input type='hidden' name='rivitunnus' 	value = '$row[rivitunnus]'>";

				echo "<select name='myyja' onchange='submit();'>";
				echo "<option value=''>".t("Valitse")."</option>";

				mysql_data_seek($myyjares, 0);

				while ($myyjarow = mysql_fetch_assoc($myyjares)) {
					$sel = "";

					if ($myyjarow['selite'] == $row['positio']) {
						$sel = 'selected';
					}

					echo "<option value='$myyjarow[selite]' $sel>$myyjarow[selitetark]</option>";
				}

				echo "</select></form><div id='div_$row[rivitunnus]'></div></td>";
			}
			else {
				echo "<form name='myyjaformi_$row[tunnus]' id='myyjaformi_$row[tunnus]' action=\"javascript:ajaxPost('myyjaformi_$row[tunnus]', '{$palvelin2}vaihda_tilauksen_myyja.php?', 'div_$row[tunnus]', '', '', '', 'post');\" method='POST'>
						<input type='hidden' name='tee' 	value = 'PAIVITAMYYJA'>
						<input type='hidden' name='tunnus' 	value = '$row[tunnus]'>";

				echo "<select name='myyja' onchange='submit();'>";

				mysql_data_seek($myyjares, 0);

				while ($myyjarow = mysql_fetch_assoc($myyjares)) {
					$sel = "";

					if ($myyjarow['tunnus'] == $row['myyja']) {
						$sel = 'selected';
					}

					echo "<option value='$myyjarow[tunnus]' $sel>$myyjarow[nimi]</option>";
				}

				echo "</select></form><div id='div_$row[tunnus]'></div></td>";
			}

			echo "<td class='back' valign='top'>
					<form method='post'>
					<input type='hidden' name='tee' 	   value='NAYTATILAUS'>
					<input type='hidden' name='tunnus' 	   value='$row[tunnus]'>
					<input type='hidden' name='ppa' 	   value='$ppa'>
					<input type='hidden' name='kka' 	   value='$kka'>
					<input type='hidden' name='vva' 	   value='$vva'>
					<input type='hidden' name='ppl' 	   value='$ppl'>
					<input type='hidden' name='kkl' 	   value='$kkl'>
					<input type='hidden' name='vvl' 	   value='$vvl'>
					<input type='hidden' name='sopparit'   value='$sopparit'>
					<input type='hidden' name='riveittain' value='$riveittain'>
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