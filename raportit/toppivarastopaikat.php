<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require('../inc/parametrit.inc');

echo "<font class='head'>".t("Varastopaikkojen keräysseuranta")."</font><hr>";

if ($tee != '') {
	$apaikka = strtoupper(sprintf("%-05s", $ahyllyalue)).strtoupper(sprintf("%05s", $ahyllynro)).strtoupper(sprintf("%05s", $ahyllyvali)).strtoupper(sprintf("%05s", $ahyllytaso));
	$lpaikka = strtoupper(sprintf("%-05s", $lhyllyalue)).strtoupper(sprintf("%05s", $lhyllynro)).strtoupper(sprintf("%05s", $lhyllyvali)).strtoupper(sprintf("%05s", $lhyllytaso));

	$lisa = "";

	if ($toppi != '') {
		$lisa = " LIMIT $toppi ";
	}

	if (!empty($summaa_varastopaikalle)) {
		$result = hae_varastopaikkakohtaisesti($kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka);
		$saldolliset = echo_varastopaikkakohtainen_table($result, $ppa, $kka, $vva, $ppl, $kkl, $vvl);
	}
	else {
		$result = hae_tuotekohtaisesti($kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka);
		$saldolliset = echo_tuotekohtainen_table($result, $ppa, $kka, $vva, $ppl, $kkl, $vvl);
	}

	echo_tulosta_inventointilista($saldolliset);
}

echo_kayttoliittyma($ppa, $kka, $vva, $ppl, $kkl, $vvl, $ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso, $lhyllyalue, $lhyllynro, $lhyllyvali, $lhyllytaso, $toppi, $summaa_varastopaikalle);

function hae_tuotekohtaisesti($kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka) {
	//haetaan rivin tiedot päivämäärien sekä hyllypaikkojen mukaan
	//kpl_X ja tuokpl_X hakee tästä päivästä lukien 6 ja 12 kk:n ajanjaksoille kerättyjen tuotteiden lukumäärän
	$query = "
 SELECT tuotepaikat.hyllyalue,
       tuotepaikat.hyllynro,
       tuotepaikat.hyllyvali,
       tuotepaikat.hyllytaso,
       tuotepaikat.saldo,
       tuotepaikat.tunnus                        paikkatun,
       tuote.tuoteno                             AS temp_tuoteno,
       tuote.nimitys,
       COUNT(tapahtuma.tunnus)                   kpl,
       SUM(tapahtuma.kpl *- 1)                   tuokpl,
	   
       (SELECT COUNT(tapahtuma.tunnus)
        FROM   tuotepaikat
               JOIN tuote
                 ON ( tuotepaikat.yhtio = tuote.yhtio
                      AND tuotepaikat.tuoteno = tuote.tuoteno )
               LEFT JOIN tapahtuma
                      ON ( tuotepaikat.yhtio = tapahtuma.yhtio
                           AND tuotepaikat.tuoteno = tapahtuma.tuoteno
                           AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
                           AND tuotepaikat.hyllynro = tapahtuma.hyllynro
                           AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
                           AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
                           AND tapahtuma.laji = 'laskutus'
                           AND tapahtuma.laadittu >= DATE_SUB(NOW(),
                                                     INTERVAL 6 month)
                           AND tapahtuma.laadittu <= NOW() )
        WHERE  tuotepaikat.yhtio = '$kukarow[yhtio]'
           AND tapahtuma.tuoteno = temp_tuoteno) kpl_6,
		   
       (SELECT SUM(tapahtuma.kpl * -1)
        FROM   tuotepaikat
               JOIN tuote
                 ON ( tuotepaikat.yhtio = tuote.yhtio
                      AND tuotepaikat.tuoteno = tuote.tuoteno )
               LEFT JOIN tapahtuma
                      ON ( tuotepaikat.yhtio = tapahtuma.yhtio
                           AND tuotepaikat.tuoteno = tapahtuma.tuoteno
                           AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
                           AND tuotepaikat.hyllynro = tapahtuma.hyllynro
                           AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
                           AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
                           AND tapahtuma.laji = 'laskutus'
                           AND tapahtuma.laadittu >= DATE_SUB(NOW(),
                                                     INTERVAL 6 month)
                           AND tapahtuma.laadittu <= NOW() )
        WHERE  tuotepaikat.yhtio = '$kukarow[yhtio]'
           AND tapahtuma.tuoteno = temp_tuoteno) tuokpl_6,
		   
       (SELECT COUNT(tapahtuma.tunnus)
        FROM   tuotepaikat
               JOIN tuote
                 ON ( tuotepaikat.yhtio = tuote.yhtio
                      AND tuotepaikat.tuoteno = tuote.tuoteno )
               LEFT JOIN tapahtuma
                      ON ( tuotepaikat.yhtio = tapahtuma.yhtio
                           AND tuotepaikat.tuoteno = tapahtuma.tuoteno
                           AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
                           AND tuotepaikat.hyllynro = tapahtuma.hyllynro
                           AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
                           AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
                           AND tapahtuma.laji = 'laskutus'
                           AND tapahtuma.laadittu >= DATE_SUB(NOW(),
                                                     INTERVAL 12 month)
                           AND tapahtuma.laadittu <= NOW() )
        WHERE  tuotepaikat.yhtio = '$kukarow[yhtio]'
           AND tapahtuma.tuoteno = temp_tuoteno) kpl_12,
		   
       (SELECT SUM(tapahtuma.kpl * -1)
        FROM   tuotepaikat
               JOIN tuote
                 ON ( tuotepaikat.yhtio = tuote.yhtio
                      AND tuotepaikat.tuoteno = tuote.tuoteno )
               LEFT JOIN tapahtuma
                      ON ( tuotepaikat.yhtio = tapahtuma.yhtio
                           AND tuotepaikat.tuoteno = tapahtuma.tuoteno
                           AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
                           AND tuotepaikat.hyllynro = tapahtuma.hyllynro
                           AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
                           AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
                           AND tapahtuma.laji = 'laskutus'
                           AND tapahtuma.laadittu >= DATE_SUB(NOW(),
                                                     INTERVAL 12 month)
                           AND tapahtuma.laadittu <= NOW() )
        WHERE  tuotepaikat.yhtio = '$kukarow[yhtio]'
           AND tapahtuma.tuoteno = temp_tuoteno) tuokpl_12
FROM   tuotepaikat
       JOIN tuote
         ON ( tuotepaikat.yhtio = tuote.yhtio
              AND tuotepaikat.tuoteno = tuote.tuoteno )
       LEFT JOIN tapahtuma
              ON ( tuotepaikat.yhtio = tapahtuma.yhtio
                   AND tuotepaikat.tuoteno = tapahtuma.tuoteno
                   AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
                   AND tuotepaikat.hyllynro = tapahtuma.hyllynro
                   AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
                   AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
                   AND tapahtuma.laji = 'laskutus'
                   AND tapahtuma.laadittu >='$vva-$kka-$ppa'
                   AND tapahtuma.laadittu <='$vvl-$kkl-$ppl')
WHERE  tuotepaikat.yhtio = '$kukarow[yhtio]'
   AND CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),
           LPAD(UPPER(
           tuotepaikat.hyllynro), 5, '0'),
           LPAD(UPPER(tuotepaikat.hyllyvali), 5, '0'), LPAD(
           UPPER(tuotepaikat.hyllytaso), 5, '0')) >= '$apaikka'
   AND CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),
           LPAD(UPPER(
           tuotepaikat.hyllynro), 5, '0'),
           LPAD(UPPER(tuotepaikat.hyllyvali), 5, '0'), LPAD(
           UPPER(tuotepaikat.hyllytaso), 5, '0')) <= '$lpaikka'
GROUP  BY 1,
          2,
          3,
          4,
          5,
          6,
		  7,
		  8
ORDER  BY kpl DESC,
          tuokpl DESC ";
	
	$result = mysql_query($query) or pupe_error($query);

	return $result;
}

function echo_tuotekohtainen_table($result, $ppa, $kka, $vva, $ppl, $kkl, $vvl) {
	echo "<table>";

	echo "<tr>
				<th>".t("Tuoteno")."</th>
				<th>".t("Nimitys")."</th>
				<th>".t("Varastopaikka")."</th>
				<th>".t("Saldo")."</th>
				<th>".t("Keräystä")."</th>
				<th>".t("Keräystä/Päivä")."</th>
				<th>".t("Kpl/Keräys")."</th>
				<th>".t('Keräystä 6kk')."</th>
				<th>".t('Keräystä 12kk')."</th>";
	echo "</tr>";

	//päiviä aikajaksossa
	$epa1 = (int)date('U', mktime(0, 0, 0, $kka, $ppa, $vva));
	$epa2 = (int)date('U', mktime(0, 0, 0, $kkl, $ppl, $vvl));

	//Diff in workdays (5 day week)
	$pva = abs($epa2 - $epa1) / 60 / 60 / 24 / 7 * 5;

	$saldolliset = array();

	while ($row = mysql_fetch_array($result)) {
		echo "<tr>";
		echo "<td>$row[temp_tuoteno]</td>";
		//echo "<td>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";
		echo "<td>".$row[nimitys]."</td>";
		echo "<td>$row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</td>";
		echo "<td align='right'>$row[saldo]</td>";
		echo "<td align='right'>$row[kpl]</td>";
		echo "<td align='right'>".round($row["kpl"] / $pva)."</td>";
		echo "<td align='right'>".round($row["tuokpl"] / $row["kpl"])."</td>";
		echo "<td align='right'>$row[kpl_6]</td>";
		echo "<td align='right'>$row[kpl_12]</td>";

		echo "</tr>";

		$saldolliset[] = $row["paikkatun"];
	}
	echo "</table><br/><br/>";

	return $saldolliset;
}

function hae_varastopaikkakohtaisesti($kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka) {
	//haetaan rivin tiedot päivämäärien sekä hyllypaikkojen mukaan
	//kpl_X ja tuokpl_X hakee tästä päivästä lukien 6 ja 12 kk:n ajanjaksoille kerättyjen tuotteiden lukumäärän
	$query = "
 SELECT tuotepaikat.hyllyalue,
       tuotepaikat.hyllynro,
       tuotepaikat.hyllyvali,
       tuotepaikat.hyllytaso,
       tuotepaikat.saldo,
       tuotepaikat.tunnus                        paikkatun,
       tuote.tuoteno                             AS temp_tuoteno,
       tuote.nimitys,
       COUNT(tapahtuma.tunnus)                   kpl,
       SUM(tapahtuma.kpl *- 1)                   tuokpl,
	   
       (SELECT COUNT(tapahtuma.tunnus)
        FROM   tuotepaikat
               JOIN tuote
                 ON ( tuotepaikat.yhtio = tuote.yhtio
                      AND tuotepaikat.tuoteno = tuote.tuoteno )
               LEFT JOIN tapahtuma
                      ON ( tuotepaikat.yhtio = tapahtuma.yhtio
                           AND tuotepaikat.tuoteno = tapahtuma.tuoteno
                           AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
                           AND tuotepaikat.hyllynro = tapahtuma.hyllynro
                           AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
                           AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
                           AND tapahtuma.laji = 'laskutus'
                           AND tapahtuma.laadittu >= DATE_SUB(NOW(),
                                                     INTERVAL 6 month)
                           AND tapahtuma.laadittu <= NOW() )
        WHERE  tuotepaikat.yhtio = '$kukarow[yhtio]'
           AND tapahtuma.tuoteno = temp_tuoteno) kpl_6,
		   
       (SELECT SUM(tapahtuma.kpl * -1)
        FROM   tuotepaikat
               JOIN tuote
                 ON ( tuotepaikat.yhtio = tuote.yhtio
                      AND tuotepaikat.tuoteno = tuote.tuoteno )
               LEFT JOIN tapahtuma
                      ON ( tuotepaikat.yhtio = tapahtuma.yhtio
                           AND tuotepaikat.tuoteno = tapahtuma.tuoteno
                           AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
                           AND tuotepaikat.hyllynro = tapahtuma.hyllynro
                           AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
                           AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
                           AND tapahtuma.laji = 'laskutus'
                           AND tapahtuma.laadittu >= DATE_SUB(NOW(),
                                                     INTERVAL 6 month)
                           AND tapahtuma.laadittu <= NOW() )
        WHERE  tuotepaikat.yhtio = '$kukarow[yhtio]'
           AND tapahtuma.tuoteno = temp_tuoteno) tuokpl_6,
		   
       (SELECT COUNT(tapahtuma.tunnus)
        FROM   tuotepaikat
               JOIN tuote
                 ON ( tuotepaikat.yhtio = tuote.yhtio
                      AND tuotepaikat.tuoteno = tuote.tuoteno )
               LEFT JOIN tapahtuma
                      ON ( tuotepaikat.yhtio = tapahtuma.yhtio
                           AND tuotepaikat.tuoteno = tapahtuma.tuoteno
                           AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
                           AND tuotepaikat.hyllynro = tapahtuma.hyllynro
                           AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
                           AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
                           AND tapahtuma.laji = 'laskutus'
                           AND tapahtuma.laadittu >= DATE_SUB(NOW(),
                                                     INTERVAL 12 month)
                           AND tapahtuma.laadittu <= NOW() )
        WHERE  tuotepaikat.yhtio = '$kukarow[yhtio]'
           AND tapahtuma.tuoteno = temp_tuoteno) kpl_12,
		   
       (SELECT SUM(tapahtuma.kpl * -1)
        FROM   tuotepaikat
               JOIN tuote
                 ON ( tuotepaikat.yhtio = tuote.yhtio
                      AND tuotepaikat.tuoteno = tuote.tuoteno )
               LEFT JOIN tapahtuma
                      ON ( tuotepaikat.yhtio = tapahtuma.yhtio
                           AND tuotepaikat.tuoteno = tapahtuma.tuoteno
                           AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
                           AND tuotepaikat.hyllynro = tapahtuma.hyllynro
                           AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
                           AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
                           AND tapahtuma.laji = 'laskutus'
                           AND tapahtuma.laadittu >= DATE_SUB(NOW(),
                                                     INTERVAL 12 month)
                           AND tapahtuma.laadittu <= NOW() )
        WHERE  tuotepaikat.yhtio = '$kukarow[yhtio]'
           AND tapahtuma.tuoteno = temp_tuoteno) tuokpl_12
FROM   tuotepaikat
       JOIN tuote
         ON ( tuotepaikat.yhtio = tuote.yhtio
              AND tuotepaikat.tuoteno = tuote.tuoteno )
       LEFT JOIN tapahtuma
              ON ( tuotepaikat.yhtio = tapahtuma.yhtio
                   AND tuotepaikat.tuoteno = tapahtuma.tuoteno
                   AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
                   AND tuotepaikat.hyllynro = tapahtuma.hyllynro
                   AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
                   AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
                   AND tapahtuma.laji = 'laskutus'
                   AND tapahtuma.laadittu >='$vva-$kka-$ppa'
                   AND tapahtuma.laadittu <='$vvl-$kkl-$ppl')
WHERE  tuotepaikat.yhtio = '$kukarow[yhtio]'
   AND CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),
           LPAD(UPPER(
           tuotepaikat.hyllynro), 5, '0'),
           LPAD(UPPER(tuotepaikat.hyllyvali), 5, '0'), LPAD(
           UPPER(tuotepaikat.hyllytaso), 5, '0')) >= '$apaikka'
   AND CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),
           LPAD(UPPER(
           tuotepaikat.hyllynro), 5, '0'),
           LPAD(UPPER(tuotepaikat.hyllyvali), 5, '0'), LPAD(
           UPPER(tuotepaikat.hyllytaso), 5, '0')) <= '$lpaikka'
GROUP  BY 1,
          2,
          3,
          4,
          5,
          6,
		  7,
		  8
ORDER  BY kpl DESC,
          tuokpl DESC ";
	
	$result = mysql_query($query) or pupe_error($query);

	return $result;
}

function echo_varastopaikkakohtainen_table($result, $ppa, $kka, $vva, $ppl, $kkl, $vvl) {
	echo "<table>";

	echo "<tr>
				<th>".t("Varastopaikka")."</th>
				<th>".t("Saldo")."</th>
				<th>".t("Keräystä")."</th>
				<th>".t("Keräystä/Päivä")."</th>
				<th>".t("Kpl/Keräys")."</th>
				<th>".t('Keräystä 6kk')."</th>
				<th>".t('Keräystä 12kk')."</th>";
	echo "</tr>";

	//päiviä aikajaksossa
	$epa1 = (int)date('U', mktime(0, 0, 0, $kka, $ppa, $vva));
	$epa2 = (int)date('U', mktime(0, 0, 0, $kkl, $ppl, $vvl));

	//Diff in workdays (5 day week)
	$pva = abs($epa2 - $epa1) / 60 / 60 / 24 / 7 * 5;

	$saldolliset = array();

	while ($row = mysql_fetch_array($result)) {
		echo "<tr>";
		echo "<td>$row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</td>";
		echo "<td align='right'>$row[saldo]</td>";
		echo "<td align='right'>$row[kpl]</td>";
		echo "<td align='right'>".round($row["kpl"] / $pva)."</td>";
		echo "<td align='right'>".round($row["tuokpl"] / $row["kpl"])."</td>";
		echo "<td align='right'>$row[kpl_6]</td>";
		echo "<td align='right'>$row[kpl_12]</td>";

		echo "</tr>";

		$saldolliset[] = $row["paikkatun"];
	}
	echo "</table><br><br>";

	return $saldolliset;
}

function echo_tulosta_inventointilista($saldolliset) {
	echo "<form method='POST' action='../inventointi_listat.php'>";
	echo "<input type='hidden' name='tee' value='TULOSTA'>";

	$saldot = "";
	foreach ($saldolliset as $saldo) {
		$saldot .= "$saldo,";
	}
	$saldot = substr($saldot, 0, -1);

	echo "<input type='hidden' name='saldot' value='$saldot'>";
	echo "<input type='hidden' name='tulosta' value='JOO'>";
	echo "<input type='hidden' name='tila' value='SIIVOUS'>";
	echo "<input type='hidden' name='ei_inventointi' value='EI'>";
	echo "<input type='submit' value='".t("Tulosta inventointilista")."'></form><br><br>";
}

function echo_kayttoliittyma($ppa, $kka, $vva, $ppl, $kkl, $vvl, $ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso, $lhyllyalue, $lhyllynro, $lhyllyvali, $lhyllytaso, $toppi, $summaa_varastopaikalle) {
	//Käyttöliittymä
	echo "<br>";
	echo "<form method='POST'>";
	echo "<table>";
	// ehdotetaan 7 päivää taaksepäin
	if (!isset($kka))
		$kka = date("m", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));
	if (!isset($vva))
		$vva = date("Y", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));
	if (!isset($ppa))
		$ppa = date("d", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	echo "<input type='hidden' name='tee' value='kaikki' />";

	if (!empty($summaa_varastopaikalle)) {
		$checked = 'checked = "checked"';
	}
	else {
		$checked = '';
	}
	echo "<tr><th>".t('Summaa varastopaikka tasolle')."</th>
			<td><input type='checkbox' name='summaa_varastopaikalle' $checked /></td></tr>";
	
	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'>
			<input type='text' name='kka' value='$kka' size='3' />
			<input type='text' name='vva' value='$vva' size='5' /></td>
			</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3' />
			<input type='text' name='kkl' value='$kkl' size='3' />
			<input type='text' name='vvl' value='$vvl' size='5' /></td>";

	echo "<tr><th>".t("Anna alkuvarastopaikka:")."</th>
			<td><input type='text' size='6' name='ahyllyalue' value='$ahyllyalue' />
			<input type='text' size='6' name='ahyllynro' value='$ahyllynro' />
			<input type='text' size='6' name='ahyllyvali' value='$ahyllyvali' />
			<input type='text' size='6' name='ahyllytaso' value='$ahyllytaso' />
			</td></tr>";

	echo "<tr><th>".t("ja loppuvarastopaikka:")."</th>
			<td><input type='text' size='6' name='lhyllyalue' value='$lhyllyalue' />
			<input type='text' size='6' name='lhyllynro' value='$lhyllynro' />
			<input type='text' size='6' name='lhyllyvali' value='$lhyllyvali' />
			<input type='text' size='6' name='lhyllytaso' value='$lhyllytaso' />
			</td></tr>";

	echo "<tr><th>".t("Listaa vain näin monta kerätyintä tuotetta:")."</th>
			<td><input type='text' size='6' name='toppi' value='$toppi' /></td>";

	echo "</table>";
	echo '<br/>';
	echo "<input type='submit' value='".t("Aja raportti")."' />";
	echo '</form>';

	
}

require ("../inc/footer.inc");
?>