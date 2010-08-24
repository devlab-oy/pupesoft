<?php

	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Rivilaskuri")."</font><hr>";

	if ($ppa != '' and $kka != '' and $vva != '') {

		if ($raporttityyppi == "kerays") {
			echo "<font class='head'>".t("Kerätyt rivit")." $ppa.$kka.$vva - $ppl.$kkl.$vvl</font>";			

			$ajotapa = 'tilausrivi.kerattyaika';
			$ajoindex = 'yhtio_tyyppi_kerattyaika';
			$saldotonjoin = "JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '')";
		}
		else {
			echo "<font class='head'>".t("Myyntitilaukset")." $ppa.$kka.$vva - $ppl.$kkl.$vvl</font>";			

			$ajotapa = 'tilausrivi.laadittu';
			$ajoindex = 'yhtio_laadittu';
			$saldotonjoin = '';
		}

		$query = "	SELECT left(date_format($ajotapa, '%H:%i'), 4),
					count(*),
					round(sum(kpl + varattu + jt)),
					sum(IF(lasku.vienti = 'E', 1, 0)),
					round(sum(IF(lasku.vienti = 'E', kpl + varattu + jt, 0))),
					sum(IF(lasku.vienti = 'K', 1, 0)),
					round(sum(IF(lasku.vienti = 'K', kpl + varattu + jt, 0))),
					sum(IF(lasku.laatija = 'EDI' or lasku.laatija = 'FuturSoft' or lasku.laatija = 'Magento', 1, 0)),
					round(sum(IF(lasku.laatija = 'EDI' or lasku.laatija = 'FuturSoft' or lasku.laatija = 'Magento', kpl + varattu + jt, 0)))
					FROM tilausrivi USE INDEX ($ajoindex)
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'L')
					$saldotonjoin
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					AND $ajotapa >= '$vva-$kka-$ppa 00:00:00'
					AND $ajotapa <= '$vvl-$kkl-$ppl 23:59:59'
					AND tilausrivi.tyyppi = 'L'
					GROUP BY 1
					ORDER BY 1";
		$res = mysql_query($query) or pupe_error($query);

		echo "<br>";
		echo "<br>";
		echo "<table>";

		echo "<tr>";
		echo "<th></th>";
		echo "<th colspan='2' align='center'>".t("Yhteensä")."</th>";
		echo "<th colspan='2' align='center'>".t("Vienti EU")."</th>";
		echo "<th colspan='2' align='center'>".t("ei-EU")."</th>";
		echo "<th colspan='2' align='center'>".t("Sähköinen")."</th>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Kello")."</th>";
		echo "<th>".t("Rivejä")."</th>";
		echo "<th>".t("Nimikkeitä")."</th>";
		echo "<th>".t("Rivejä")."</th>";
		echo "<th>".t("Nimikkeitä")."</th>";
		echo "<th>".t("Rivejä")."</th>";
		echo "<th>".t("Nimikkeitä")."</th>"; 
		echo "<th>".t("Rivejä")."</th>";
		echo "<th>".t("Nimikkeitä")."</th>";
		echo "</tr>";

		while ($row = mysql_fetch_array($res)) {
			echo "<tr class='aktiivi'>";
			echo "<td>$row[0]0 - $row[0]9</td>";
			echo "<td align='right'>$row[1]</td>";
			echo "<td align='right'>$row[2]</td>";
			echo "<td align='right'>$row[3]</td>";
			echo "<td align='right'>$row[4]</td>";
			echo "<td align='right'>$row[5]</td>";
			echo "<td align='right'>$row[6]</td>";
			echo "<td align='right'>$row[7]</td>";
			echo "<td align='right'>$row[8]</td>";
			echo "</tr>";
		}

		///* Yhteensärivi, annetaan tietokannan tehä työ, en jakssa summata while loopissa t. juppe*///
		$query = "	SELECT 
					count(*),
					round(sum(kpl + varattu + jt)),
					sum(IF(lasku.vienti = 'E', 1, 0)),
					round(sum(IF(lasku.vienti = 'E', kpl + varattu + jt, 0))),
					sum(IF(lasku.vienti = 'K', 1, 0)),
					round(sum(IF(lasku.vienti = 'K', kpl + varattu + jt, 0))),
					sum(IF(lasku.laatija = 'EDI' or lasku.laatija = 'FuturSoft' or lasku.laatija = 'Magento', 1, 0)),
					round(sum(IF(lasku.laatija = 'EDI' or lasku.laatija = 'FuturSoft' or lasku.laatija = 'Magento', kpl + varattu + jt, 0)))
					FROM tilausrivi USE INDEX ($ajoindex)
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'L')
					$saldotonjoin
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					AND $ajotapa >= '$vva-$kka-$ppa 00:00:00'
					AND $ajotapa <= '$vvl-$kkl-$ppl 23:59:59'
					AND tilausrivi.tyyppi = 'L'";
		$res = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($res);

		echo "<tr>";
		echo "<th>".t("Yhteensä").":</th>";
		echo "<th style='text-align:right;'>$row[0]</th>";
		echo "<th style='text-align:right;'>$row[1]</th>";
		echo "<th style='text-align:right;'>$row[2]</th>";
		echo "<th style='text-align:right;'>$row[3]</th>";
		echo "<th style='text-align:right;'>$row[4]</th>";
		echo "<th style='text-align:right;'>$row[5]</th>";
		echo "<th style='text-align:right;'>$row[6]</th>";
		echo "<th style='text-align:right;'>$row[7]</th>";
		echo "</tr>";

		echo "</table>";
	}

	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	$tyyppisel = array();
	$tyyppisel[$raporttityyppi] = "SELECTED";

	echo "<br>";
	echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Ajotyyppi")."</th>";
	echo "<td colspan='3'><select name='raporttityyppi'>";
	echo "<option value='myynti' $tyyppisel[myynti]>".t("Myyntitilauksen luontiajan mukaan")."</option>";
	echo "<option value='kerays' $tyyppisel[kerays]>".t("Myyntitilauksen keräysajan mukaan")."</option></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syötä alkupäivämäärä")." (".t("pp-kk-vvvv").")</th>";
	echo "<td><input type='text' name='ppa' value='$ppa' size='3'></td>";
	echo "<td><input type='text' name='kka' value='$kka' size='3'></td>";
	echo "<td><input type='text' name='vva' value='$vva' size='5'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syötä loppupäivämäärä")." (".t("pp-kk-vvvv").")</th>";
	echo "<td><input type='text' name='ppl' value='$ppl' size='3'></td>";
	echo "<td><input type='text' name='kkl' value='$kkl' size='3'></td>";
	echo "<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
	echo "</tr>";

	echo "</table>";

	echo "<br>";
	echo "<input type='submit' value='".t("Aja raportti")."'>";

	echo "</form>";

	require ("inc/footer.inc");

?>
