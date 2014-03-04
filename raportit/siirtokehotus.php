<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if(isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "siirtokehotus.php") !== FALSE) {
		require ("../inc/parametrit.inc");
	}

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

if (isset($tee) and $tee == "hae_raportti") {

$query = "SELECT t4.tuoteno AS tuoteno,
			t2.tunnus AS varasto,
			t4.halytysraja AS  haly,
			t4.oletus AS oletus,
			CONCAT(t4.hyllyalue, '-', t4.hyllynro, '-', t4.hyllyvali, '-', t4.hyllytaso ) AS tuotepaikka,
			t4.hyllyalue AS alue,
			t4.hyllynro AS nro,
			t4.hyllyvali AS vali,
			t4.hyllytaso AS taso
			FROM keraysvyohyke AS t1
			JOIN varastopaikat AS t2
				ON ( t1.yhtio = t2.yhtio AND t1.varasto = t2.tunnus )
			JOIN tuote AS t3
				ON ( t2.yhtio = t3.yhtio AND t1.tunnus = t3.keraysvyohyke)
			JOIN tuotepaikat AS t4
				ON ( t3.yhtio = t4.yhtio AND t3.tuoteno = t4.tuoteno)
			WHERE t1.yhtio = '{$kukarow['yhtio']}'
			AND t2.tunnus IN (139,121)
			AND t4.halytysraja > 0
			AND t4.oletus = 'X'";

		$result = pupe_query($query);

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

		while ($row = mysql_fetch_assoc($result)) {

			$saldo_info = saldo_myytavissa($row['tuoteno'], '', $row['varasto'], $kukarow['yhtio'], $row['alue'], $row['nro'], $row['vali'], $row['taso'] );
			$row['hyllyssa'] = $saldo_info[1];

			if( $row['hyllyssa'] >= $row['haly'] ){
				continue;
			}


			$query2 = "SELECT CONCAT(hyllyalue, '-', hyllynro, '-', hyllyvali, '-', hyllytaso ) AS tuotepaikka,
						hyllyalue AS alue,
						hyllynro AS nro,
						hyllyvali AS vali,
						hyllytaso AS taso
						FROM tuotepaikat
						WHERE tuoteno = '{$row['tuoteno']}'
						AND yhtio = '{$kukarow['yhtio']}'
						AND oletus != 'X'";

			$varapaikka_echo = '';
			$result2 = pupe_query($query2);
			while ($row2 = mysql_fetch_assoc($result2)) {
				$saldo_info = saldo_myytavissa($row['tuoteno'], '', $row['varasto'], $kukarow['yhtio'], $row2['alue'], $row2['nro'], $row2['vali'], $row2['taso'] );
				$row2['hyllyssa'] = $saldo_info[1];

				if( $row2['hyllyssa'] < 1 ){
					continue;
				}

				$varapaikka_echo .= '<tr>';
				$varapaikka_echo .= '<td>';
				$varapaikka_echo .= 'Varapaikka';
				$varapaikka_echo .= '</td>';
				$varapaikka_echo .= '<td>';
				$varapaikka_echo .= '';
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



			echo '<tr>';
			echo '<td colspan="12" style="background:#cbd9e1">';
			echo '</td>';
			echo '<tr>';


			echo '<tr>';
			echo '<td>';
			echo 'Oletuspaikka';
			echo '</td>';
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
			echo '<tr>';

			echo $varapaikka_echo;

		}

echo '</table>';

}
	else {
		echo "<font class='head'>",t("Siirtokehotusraportti"),"</font><hr>";

		if (!aja_kysely()) {
			unset($_REQUEST);
		}


		echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<table>";



		echo "<tr><th align='left'>",t("Varasto"),":</td>";
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
			echo "<input type='hidden' name='keraysvyohyke[]' value='default' />";

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

		if (isset($tee) and $tee == "hae_raportti") {


			echo '<br />';
			echo '<hr />';
			echo "raporttien haku";

		}

		if (strpos($_SERVER['SCRIPT_NAME'], "siirtokehotus.php") !== FALSE) {
			require ("../inc/footer.inc");
		}
	}
