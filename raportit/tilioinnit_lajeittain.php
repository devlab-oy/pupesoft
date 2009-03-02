<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Tiliöinnit lajeittain")."</font><hr>";

	// käyttöliittymä
	if (!isset($pp)) $pp = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($kk)) $kk = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($vv)) $vv = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	$pp = sprintf("%02d", trim($pp));
	$kk = sprintf("%02d", trim($kk));
	$vv = sprintf("%04d", trim($vv));

	if (!isset($lpp)) $lpp = date("d");
	if (!isset($lkk)) $lkk = date("m");
	if (!isset($lvv)) $lvv = date("Y");
	$lpp = sprintf("%02d", trim($lpp));
	$lkk = sprintf("%02d", trim($lkk));
	$lvv = sprintf("%04d", trim($lvv));

	if (!checkdate($kk, $pp, $vv) or !checkdate($lkk, $lpp, $lvv)) {
		echo "<font class='error'>".t("Virheellinen päivämäärä")."!</font><br><br>";
		$tee = "";
	}

	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='raportti'>";

	echo "<table>";

	$check = array();
	$check[$laji] = "SELECTED";

	echo "<tr>";
	echo "<th>".t("Valitse tapahtumatyyppi")."</th>";
	echo "<td>
			<select name='laji'>
			<option value='myynti' $check[myynti]>".t("Myyntilaskut")."</option>
			<option value='osto' $check[osto]>".t("Ostolaskut")."</option>
			<option value='tosite' $check[tosite]>".t("Tositteet")."</option>
			<option value='kaikki' $check[kaikki]>".t("Kaikki")."</option>
			</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syötä alkupäivä")."</th>";
	echo "<td><input type='text' name='pp' size='5' value='$pp'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='vv' size='7' value='$vv'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syötä loppupäivä")."</th>";
	echo "<td><input type='text' name='lpp' size='5' value='$lpp'><input type='text' name='lkk' size='5' value='$lkk'><input type='text' name='lvv' size='7' value='$lvv'></td>";
	echo "</tr>";

	echo "</table>";

	echo "<br><input type='submit' value='".t("Hae tiliöinnit")."'>";

	echo "</form>";
	echo "<br><br>";

	// itse raportti
	if ($tee == "raportti") {

		$laskurajaus = "";

		if ($laji == "myynti") {
			$laskurajaus = "AND lasku.tila = 'U'";
			echo "<font class='head'>".t("Myyntilaskut")."</font><hr>";
		}

		if ($laji == "osto") {
			$laskurajaus = "AND lasku.tila in ('H','Y','M','P','Q')";
			echo "<font class='head'>".t("Ostolaskut")."</font><hr>";
		}

		if ($laji == "tosite") {
			$laskurajaus = "AND lasku.tila = 'X'";
			echo "<font class='head'>".t("Tositteet")."</font><hr>";			
		}

		if ($laji == "kaikki") {
			$laskurajaus = "AND lasku.tila in ('H','Y','M','P','Q','U','X')";
			echo "<font class='head'>".t("Myyntilaskut")." + ".t("Ostolaskut")." + ".t("Tositteet")."</font><hr>";
			
		}
		
		$query = "	SELECT tiliointi.tilino, tili.nimi, sum(tiliointi.summa) summa				
					FROM tiliointi USE INDEX (yhtio_tapvm_tilino)
					JOIN lasku ON (lasku.yhtio = tiliointi.yhtio
						AND lasku.tunnus = tiliointi.ltunnus
						$laskurajaus)
					LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio
						AND tili.tilino = tiliointi.tilino)
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					AND tiliointi.tapvm >= '$vv-$kk-$pp'
					AND tiliointi.tapvm <= '$lvv-$lkk-$lpp'
					AND tiliointi.korjattu = ''
					GROUP BY tilino, nimi
					ORDER BY tilino, nimi";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Tilinumero")."</th>";
			echo "<th>".t("Nimitys")."</th>";
			echo "<th>".t("Summa")."</th>";
			echo "</tr>";

			$summa = 0;

			while ($row = mysql_fetch_array($result)) {
				echo "<tr>";
				echo "<td>$row[tilino]</td>";
				echo "<td>$row[nimi]</td>";
				echo "<td align='right'>$row[summa]</td>";
				echo "</tr>";
				$summa += $row["summa"];
			}

			echo "<tr>";
			echo "<th colspan='2'>".t("Yhteensä")."</th>";
			echo "<th style='text-align:right;'>". sprintf("%.02f", $summa)."</td>";
			echo "</tr>";

			echo "</table>";
		}

/*
		$query = "	SELECT lasku.*
					FROM lasku
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tapvm >= '$vv-$kk-$pp'
					AND lasku.tapvm <= '$lvv-$lkk-$lpp'
					$laskurajaus
					ORDER BY tapvm, tunnus";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			echo "<br><table>";
			echo "<tr>";
			echo "<th>".t("Nro")."</th>";
			echo "<th>".t("Nimi")."</th>";
			echo "<th>".t("Tapvm")."</th>";
			echo "<th>".t("Summa")."</th>";
			echo "</tr>";

			$summa = 0;

			while ($row = mysql_fetch_array($result)) {
				echo "<tr>";
				echo "<td>$row[laskunro]</td>";
				echo "<td>$row[nimi]</td>";
				echo "<td>$row[tapvm]</td>";
				echo "<td align='right'>$row[summa]</td>";
				echo "</tr>";
				$summa += $row["summa"];
			}

			echo "<tr>";
			echo "<th colspan='3'>".t("Yhteensä")."</th>";
			echo "<th style='text-align:right;'>". sprintf("%.02f", $summa)."</td>";
			echo "</tr>";

			echo "</table>";
		}
*/
					
	}

	require ("../inc/footer.inc");

?>