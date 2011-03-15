<?php

	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Matkallaolevat laskuittain")."</font><hr>";

	$llisa = "";
	$alisa = "";

	if ($vv != "") {
		if (!checkdate($kk, $pp, $vv)) {
			echo "<font class='error'>".t("Virheellinen päivämäärä")."!</font><br><br>";
		}
		else {
	 		$alisa = " AND lasku.tapvm >= '$vv-$kk-$pp' ";
		}
	}

	if ($lvv != "") {
		if (!checkdate($lkk, $lpp, $lvv)) {
			echo "<font class='error'>".t("Virheellinen päivämäärä")."!</font><br><br>";
		}
		else {
			$llisa = " AND lasku.tapvm < '$lvv-$lkk-$lpp' ";
		}
	}

	echo "<form method='post' action='$PHP_SELF'>";
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Syötä alkupäivä")."</th>";
	echo "<td><input type='text' name='pp' size='5' value='$pp'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='vv' size='7' value='$vv'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syötä loppupäivä")."</th>";
	echo "<td><input type='text' name='lpp' size='5' value='$lpp'><input type='text' name='lkk' size='5' value='$lkk'><input type='text' name='lvv' size='7' value='$lvv'></td>";
	echo "</tr>";
	echo "</table>";
	echo "<br><input type='submit' value='".t("Näytä")."'>";

	echo "</form>";
	echo "<br><br>";

	if ($alisa != "" and $llisa != "") {

		$query = "	SELECT lasku.tunnus, if(lasku.tila = 'X', '".t("Tosite")."', lasku.nimi) nimi, lasku.summa, lasku.valkoodi, lasku.tapvm, sum(tiliointi.summa) matkalla
					FROM lasku
					JOIN tiliointi on (tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus and tiliointi.tilino = '$yhtiorow[matkalla_olevat]' and tiliointi.korjattu = '')
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tila in ('H', 'Y', 'M', 'P', 'Q', 'X')
					$alisa
					$llisa
					GROUP BY lasku.tunnus, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.tapvm
					HAVING matkalla != 0
					ORDER BY lasku.nimi, lasku.tapvm, lasku.summa";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Nimi")."</th>";
			echo "<th>".t("Tapvm")."</th>";
			echo "<th>".t("Summa")."</th>";
			echo "<th>".t("Valuutta")."</th>";
			echo "<th>".t("Matkalla")."</th>";
			echo "<th>".t("Valuutta")."</th>";
			echo "</tr>";

			$summa = 0;
			$alvsumma = array();

			while ($row = mysql_fetch_array($result)) {
				echo "<tr class='aktiivi'>";
				echo "<td>$row[nimi]</td>";
				echo "<td>".tv1dateconv($row["tapvm"])."</td>";
				echo "<td align='right'>$row[summa]</td>";
				echo "<td align='right'>$row[valkoodi]</td>";
				echo "<td align='right'><a href='$palvelin2","muutosite.php?tee=E&tunnus=$row[tunnus]&lopetus=$palvelin2","raportit/matkallaolevat_laskuittain.php'>$row[matkalla]</a></td>";
				echo "<td align='right'>$yhtiorow[valkoodi]</td>";
				echo "</tr>";
				$summa += $row["matkalla"];
			}

			echo "<tr>";
			echo "<th colspan='4'>".t("Yhteensä")."</th>";
			echo "<th style='text-align:right;'>". sprintf("%.02f", $summa)."</td>";
			echo "<th></th>";
			echo "</tr>";

			echo "</table>";
		}

	}

	require ("../inc/footer.inc");

?>