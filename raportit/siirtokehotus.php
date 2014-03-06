<?php

if (isset($_POST["tee"])) {
	if($_POST["tee"] == 'lataa_tiedosto'){ $lataa_tiedosto=1; }
	if(isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != ''){ $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]); }
}

if (strpos($_SERVER['SCRIPT_NAME'], "siirtokehotus.php") !== FALSE) {
	require ("../inc/parametrit.inc");
}

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}

echo "<font class='head'>" . t("Siirtokehotusraportti") . "</font><hr>";

if (isset($tee) and $tee == "hae_raportti" and count($varasto) < 1) {
	$tee = '';
	$ei_varastoa = true;
}

if (isset($tee) and $tee == "hae_raportti") {

		$varastot = implode(",",$varasto);

		if( isset($keraysvyohyke) and count($keraysvyohyke) > 0 ){
			$keraysvyohykkeet = implode(",",$keraysvyohyke);

			$kv_join = "JOIN varaston_hyllypaikat AS vh ON
								(
									vh.yhtio = tuotepaikat.yhtio AND vh.hyllyalue = tuotepaikat.hyllyalue
									AND vh.hyllynro = tuotepaikat.hyllynro
									AND vh.hyllyvali = tuotepaikat.hyllyvali
									AND vh.hyllytaso = tuotepaikat.hyllytaso
								)
							JOIN keraysvyohyke ON
								(
									keraysvyohyke.yhtio = varastopaikat.yhtio
									AND keraysvyohyke.varasto = varastopaikat.tunnus
									AND keraysvyohyke.tunnus = vh.keraysvyohyke
								)";

			$kv_and = "AND keraysvyohyke.tunnus IN ({$keraysvyohykkeet})";
		}
		else{
			$kv_join = "";
			$kv_and = "";
		}

		$query = "SELECT 	tuotepaikat.tuoteno AS tuoteno,
							varastopaikat.tunnus AS varasto,
							tuotepaikat.halytysraja AS haly,
							tuotepaikat.oletus AS oletus,
							CONCAT(tuotepaikat.hyllyalue, '-', tuotepaikat.hyllynro, '-', tuotepaikat.hyllyvali, '-', tuotepaikat.hyllytaso ) AS tuotepaikka,
							tuotepaikat.hyllyalue AS alue,
							tuotepaikat.hyllynro AS nro,
							tuotepaikat.hyllyvali AS vali,
							tuotepaikat.hyllytaso AS taso
							FROM tuotepaikat
							JOIN varastopaikat ON
								(
									varastopaikat.yhtio = tuotepaikat.yhtio
									AND concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
									AND concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								)
							{$kv_join}
							WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
							{$kv_and}
							AND varastopaikat.tunnus IN ({$varastot})
							AND tuotepaikat.halytysraja > 0
							AND tuotepaikat.oletus = 'X'";

		$result = pupe_query($query);

		$oletuspaikat = [];

		while ($row = mysql_fetch_assoc($result)) {

			$varapaikka_query = "SELECT COUNT(tp.tunnus) as count
								FROM tuotepaikat AS tp
								JOIN varastopaikat AS vp ON
									(
										vp.yhtio = tp.yhtio
										AND concat(rpad(upper(vp.alkuhyllyalue),  5, '0'),lpad(upper(vp.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tp.hyllyalue), 5, '0'),lpad(upper(tp.hyllynro), 5, '0'))
										AND concat(rpad(upper(vp.loppuhyllyalue), 5, '0'),lpad(upper(vp.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tp.hyllyalue), 5, '0'),lpad(upper(tp.hyllynro), 5, '0'))
									)
								WHERE oletus != 'X'
								AND tp.tuoteno = '{$row['tuoteno']}'
								AND vp.tunnus = {$row['varasto']}
								AND tp.yhtio = '{$kukarow['yhtio']}'";

			$varapaikka_result = pupe_query($varapaikka_query);
			$varapaikka_count = mysql_result($varapaikka_result, 0);

			if( $varapaikka_count > 0 ){
				$oletuspaikat[] = $row;
			}
		}

		echo '<table>';
		echo '<tr>';
		echo '<th>';
		echo 'tyyppi';
		echo '</th>';
		echo '<th>';
		echo 'tuoteno';
		echo '</th>';
		echo '<th>';
		echo 'tuotepaikka';
		echo '</th>';
		echo '<th>';
		echo 'hyllyssa';
		echo '</th>';
		echo '<th>';
		echo 'haly';
		echo '</th>';
		echo '<tr>';

		foreach ($oletuspaikat as $row) {

			$saldo_info = saldo_myytavissa($row['tuoteno'], '', $row['varasto'], $kukarow['yhtio'], $row['alue'], $row['nro'], $row['vali'], $row['taso'] );
			$row['hyllyssa'] = $saldo_info[1];

			if( $row['hyllyssa'] >= $row['haly'] ){
				continue;
			}

			$query2 = "SELECT CONCAT(hyllyalue, '-', hyllynro, '-', hyllyvali, '-', hyllytaso ) AS tuotepaikka,
							hyllyalue AS alue,
							hyllynro AS nro,
							hyllyvali AS vali,
							hyllytaso AS taso,
							varastopaikat.tunnus as vt
							FROM tuotepaikat
							JOIN varastopaikat ON
								(
									varastopaikat.yhtio = tuotepaikat.yhtio
									AND concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
									AND concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								)
							WHERE tuoteno = '{$row['tuoteno']}'
							AND tuotepaikat.yhtio = '{$kukarow['yhtio']}'
							AND oletus != 'X'
							AND varastopaikat.tunnus = {$row['varasto']}";

			$varapaikka_echo = '';
			$result2 = pupe_query($query2);
			while ($row2 = mysql_fetch_assoc($result2)) {
				$saldo_info = saldo_myytavissa($row['tuoteno'], '', $row['varasto'], $kukarow['yhtio'], $row2['alue'], $row2['nro'], $row2['vali'], $row2['taso'] );
				$row2['hyllyssa'] = $saldo_info[1];

				if( $row2['hyllyssa'] < 1 ){
					continue;
				}

				$varapaikka_echo .= '<tr>';
				$varapaikka_echo .= '<th>';
				$varapaikka_echo .= 'Varapaikka';
				$varapaikka_echo .= '</th>';
				$varapaikka_echo .= '<td style="color:silver;">';
				$varapaikka_echo .= $row['tuoteno'];
				$varapaikka_echo .= '</td>';
				$varapaikka_echo .= '<td>';
				$varapaikka_echo .= $row2['tuotepaikka'];
				$varapaikka_echo .= '</td>';
				$varapaikka_echo .= '<td>';
				$varapaikka_echo .= $row2['hyllyssa'];
				$varapaikka_echo .= '</td>';
				$varapaikka_echo .= '<td>';
				$varapaikka_echo .= '';
				$varapaikka_echo .= '</td>';
				$varapaikka_echo .= '</tr>';

			}

			if( $varapaikka_echo == '' ){
				continue;
			}

			//tyhjä rivi ennen jokaista oletuspaikkaa
			echo '<tr>';
			echo '<td colspan="12" style="background:#cbd9e1; padding:4px;"></td>';
			echo '</tr>';

			echo '<tr>';
			echo '<th>';
			echo 'Oletuspaikka';
			echo '</th>';
			echo '<td>';
			echo $row['tuoteno'];
			echo '</td>';
			echo '<td>';
			echo $row['tuotepaikka'];
			echo '</td>';
			echo '<td>';
			echo $row['hyllyssa'];
			echo '</td>';
			echo '<td>';
			echo $row['haly'];
			echo '</td>';
			echo '</tr>';

			echo $varapaikka_echo;

		}

		echo '</table>';
}
else {

	if( $ei_varastoa === true ){
		echo "<font class='error'>Vähintään yksi varasto on valittava</font>";
	}

	echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
	echo "<table>";
	echo "<tr><th align='left'>" . t("Varasto") . ": <br /><br /><span style='text-transform: none;'>Valitse vähintään<br />yksi varasto.</span></td>";
	echo "<td>";

	$query  = "	SELECT *
				FROM varastopaikat
				WHERE yhtio = '{$kukarow['yhtio']}' AND tyyppi != 'P'
				ORDER BY tyyppi, nimitys";

	$vares = pupe_query($query);

	$varastot_array = explode(",", $krow["varasto"]);

	while ($varow = mysql_fetch_assoc($vares)) {
		$sel = $eri = '';
		if (in_array($varow['tunnus'], $varastot_array)) $sel = 'CHECKED';
		if ($varow["tyyppi"] == "E") $eri = "(E)";
		echo "<input type='checkbox' name='varasto[]' value='{$varow['tunnus']}' {$sel}> {$varow['nimitys']} {$eri}<br>";
	}

	$query = "SELECT tunnus, nimitys FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
	$keraysvyohyke_result = pupe_query($query);

	if (mysql_num_rows($keraysvyohyke_result) > 0) {
		echo "<tr><th align='left'>",t("Keräysvyöhyke"),":</th><td>";
		//echo "<input type='hidden' name='keraysvyohyke[]' value='default' />";

		while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result)) {
			$chk = strpos($krow['keraysvyohyke'], $keraysvyohyke_row['tunnus']) !== false ? ' checked' : '';
			echo "<input type='checkbox' name='keraysvyohyke[]' value='{$keraysvyohyke_row['tunnus']}'{$chk} />&nbsp;{$keraysvyohyke_row['nimitys']}<br />";
		}
		echo "</td></tr>";
	}

	echo "</td></tr>";
	echo "</table>";
	echo "<input type='hidden' name='tee' value='hae_raportti'>";
	echo "<br><input type='submit' name='hae_raportti' value='",t("Hae raportti"),"'></form>";
}

if (strpos($_SERVER['SCRIPT_NAME'], "siirtokehotus.php") !== FALSE) {
	require ("../inc/footer.inc");
}
