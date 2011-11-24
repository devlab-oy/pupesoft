<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Laadunvalvonta").":</font><hr>";

if (!isset($tuoteryhmittain)) $tuoteryhmittain = '';
if (!isset($submit)) $submit = '';
if (!isset($vva)) $vva = date('Y');

if ($submit) {
	if (isset($tuoteryhmittain) and trim($tuoteryhmittain) != '') {
		$tuoteryhma = "tuote.try, ";
		$group_by = "1, 2";
		$order_by = "1, 2";
	}
	else {
		$tuoteryhma = "";
		$group_by = "1";
		$order_by = "1";
	}

	$tuotelisa = '';

	if (isset($mul_try) and count($mul_try) > 0) {

		if (!in_array("PUPEKAIKKIMUUT", $mul_try)) {

			foreach ($mul_try as $mul_try_selite) {
				$tuotelisa .= "'$mul_try_selite',";
			}

			if (count($mul_try) == 1 and $mul_try[0] == '') {
				$tuotelisa = '';
			}
			else {
				$tuotelisa = " and tuote.try in (".substr($tuotelisa, 0, -1).") ";
			}
		}
	}

	$vva = (int) $vva;

	$query = "	SELECT $tuoteryhma LEFT(toimitettuaika, 7) as toimitettuaika,
				SUM(IF(toimaika = LEFT(toimitettuaika, 10), 1, 0)) as ajallaan,
				SUM(IF(toimaika > LEFT(toimitettuaika, 10), 1, 0)) as etuajassa,
				SUM(IF(toimaika < LEFT(toimitettuaika, 10), 1, 0)) as myohassa
				FROM tilausrivi USE INDEX (yhtio_laadittu)
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '' $tuotelisa)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				AND tilausrivi.laadittu >= '$vva-01-01 00:00:00'
				AND tilausrivi.laadittu <= '$vva-12-31 23:59:59'
				AND tilausrivi.tyyppi = 'L'
				AND tilausrivi.toimitettuaika != '0000-00-00 00:00:00'
				AND tilausrivi.var != 'P'
				GROUP BY $group_by
				ORDER BY $order_by";
	$toimaikares = mysql_query($query) or pupe_error($query);

	if (isset($tuoteryhmittain) and trim($tuoteryhmittain) != '') {
		while ($toimaikarow = mysql_fetch_array($toimaikares)) {
			$tuoteryh[] = $toimaikarow;
		}
	}
	else {
		while ($toimaikarow = mysql_fetch_array($toimaikares)) {
			$myohassa[] = $toimaikarow['myohassa'];
		}

		mysql_data_seek($toimaikares, 0);

		while ($toimaikarow = mysql_fetch_array($toimaikares)) {
			$ajallaan[] = $toimaikarow['ajallaan'];
		}

		mysql_data_seek($toimaikares, 0);

		while ($toimaikarow = mysql_fetch_array($toimaikares)) {
			$etuajassa[] = $toimaikarow['etuajassa'];
		}
	}
}

$ajallaan_summa = 0;
$etuajassa_summa = 0;
$myohassa_summa = 0;

echo "<table>";
echo "<form name='laatu' action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<tr><th>".t("Syötä vuosi (vvvv)")."</th>
		<td><input type='text' name='vva' value='$vva' size='5'></td></tr>
		<tr><th>",t("Valitse tuoteryhmä"),"</th>";

echo "<td>";
$monivalintalaatikot = array("TRY");
$monivalintalaatikot_normaali = array();

require ("../tilauskasittely/monivalintalaatikot.inc");
echo "</td>";

$tuoteryhmittain_chk = (isset($tuoteryhmittain) and trim($tuoteryhmittain) != '') ? ' CHECKED' : '';

echo "	</select></td></tr>
		<tr><th>".t("Tuoteryhmittäin")."</th><td align='center'><input type='checkbox' name='tuoteryhmittain'$tuoteryhmittain_chk></td><td class='back'><input type='submit' name='submit' value='Hae'></td></tr>";
echo "</form>";
echo "</table>";

if (isset($tuoteryhmittain) and trim($tuoteryhmittain) != '') {
	echo "<table>";
	echo "<tr><th>".t("Tuoteryhmä")."</th><th>".t("Selite")."</th><th>".t("Aika")."</th><th>".t("Ajallaan")."</th><th>".t("Etuajassa")."</th><th>".t("Myöhässä")."</th></tr>";

	foreach ($tuoteryh as $key) {
		// tehdään avainsana query
		$seliteres = t_avainsana("TRY", $kukarow['kieli'], "and avainsana.selite ='$key[try]'");
		$seliterow = mysql_fetch_array($seliteres);

		echo "<tr><td align='right'>$key[try]</td><td align='left'>";

		if ($seliterow["selitetark"] != "") {
			echo "$seliterow[selitetark]";
		}

		echo "</td><td align='left'>$key[toimitettuaika]</td><td align='right'>$key[ajallaan]</td><td align='right'>$key[etuajassa]</td><td align='right'>$key[myohassa]</td></tr>";

		$ajallaan_summa += $key['ajallaan'];
		$etuajassa_summa += $key['etuajassa'];
		$myohassa_summa += $key['myohassa'];
	}

	echo "<tr><th colspan='3'>".t("Yhteensä")."</th><td class='tumma' align='right'><strong>$ajallaan_summa</strong></td><td class='tumma' align='right'><strong>$etuajassa_summa</strong></td><td class='tumma' align='right'><strong>$myohassa_summa</strong></td></tr>";
	echo "</table>";
}
elseif ($submit != '' and $tuoteryhmittain == '') {

	echo "<table width='700'>";
		echo "<tr><th>".t("Toimitukset")."</th><th>".t("Tammikuu")."</th><th>".t("Helmikuu")."</th><th>".t("Maaliskuu")."</th><th>".t("Huhtikuu")."</th><th>".t("Toukokuu")."</th><th>".t("Kesäkuu")."</th><th>".t("Heinäkuu")."</th><th>".t("Elokuu")."</th><th>".t("Syyskuu")."</th><th>".t("Lokakuu")."</th><th>".t("Marraskuu")."</th><th>".t("Joulukuu")."</th><th>".t("Yhteensä")."</th></tr>";
		echo "<tr><th>".t("Myöhästyneet").":</th>";
			for ($i = 0; $i < 12; $i++) {
				if (isset($myohassa[$i]) and $myohassa[$i] != null) {
					$myohassa_summa += $myohassa[$i];
						echo "<td align='right'>$myohassa[$i]</td>";
				} else {
					echo "<td align='right'>0</td>";
				}
			}
		echo "<td align='right'>$myohassa_summa</td>";
		echo "</tr>";
		echo "<tr><th>".t("Ajallaan").":</th>";
			for ($i = 0; $i < 12; $i++) {
				if (isset($ajallaan[$i]) and $ajallaan[$i] != null) {
					$ajallaan_summa += $ajallaan[$i];
						echo "<td align='right'>$ajallaan[$i]</td>";
				} else {
					echo "<td align='right'>0</td>";
				}
			}
		echo "<td align='right'>$ajallaan_summa</td>";
		echo "</tr>";
		echo "<tr><th>".t("Etuajassa").":</th>";
			for ($i = 0; $i < 12; $i++) {
				if (isset($etuajassa[$i]) and $etuajassa[$i] != null) {
					$etuajassa_summa += $etuajassa[$i];
					echo "<td align='right'>$etuajassa[$i]</td>";
				} else {
					echo "<td align='right'>0</td>";
				}
			}
		echo "<td align='right'>$etuajassa_summa</td>";
		echo "</tr>";
	echo "</table>";
}

require("../inc/footer.inc");

?>