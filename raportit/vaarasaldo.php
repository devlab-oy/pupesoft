<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Väärät saldot").":</font><hr>";

	if ($tee != '') {
		$query = "	SELECT tuoteno, nimitys, tilkpl, kpl, keratty, concat_ws(' ',hyllyalue, hyllynro, hyllyvali, hyllytaso) tuotepaikka, nimitys, yksikko, hyllyalue, hyllynro, hyllytaso, hyllyvali,
					concat(lpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta
					FROM tilausrivi
					WHERE yhtio='$kukarow[yhtio]' and laadittu>='$vva-$kka-$ppa 00:00:00'
					and laadittu<='$vvl-$kkl-$ppl 23:59:59' and tyyppi='L' and laskutettu!='' and var!='J' and var!='P'
					and tilkpl<>kpl
					ORDER BY sorttauskentta, tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0 ) {
			echo "<table><tr><th>".t("Varastopaikka")."</th><th>".t("Tuoteno")."</th><th>".t("Nimitys")."</th>
					<th>".t("Toimittajan tuoteno")."</th><th>".t("Tilattu")."</th><th>".t("Toimitettu")."</th>
					<th nowrap>".t("Tilauksessa")."</th>
					<th>".t("Ensimmäinen toimitus")."</th><th>".t("Hyllyssä")."</th><th>".t("Saldo")."</th><th>".t("Kerääjä")."</th></tr>";

			while ($row = mysql_fetch_array($result)) {
				$query = "	SELECT tuotepaikat.saldo, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
							group_concat(tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno
							FROM tuotepaikat
							JOIN tuote USING (yhtio, tuoteno)
							LEFT JOIN tuotteen_toimittajat USING (yhtio, tuoteno)
							WHERE hyllyalue='$row[hyllyalue]'
							and hyllynro='$row[hyllynro]'
							and hyllytaso='$row[hyllytaso]'
							and hyllyvali='$row[hyllyvali]'
							and tuotepaikat.yhtio='$kukarow[yhtio]'
							and tuotepaikat.tuoteno='$row[tuoteno]'
							GROUP BY 1,2,3,4,5";
				$result1 = mysql_query($query) or pupe_error($query);
				$srow = mysql_fetch_array($result1);

				//jo kerätyt mutta ei laskutettu
				$query = "	SELECT ifnull(sum(tilausrivi.varattu),0) kermaara
							FROM tuotepaikat, tilausrivi
							WHERE tuotepaikat.yhtio = tilausrivi.yhtio
							and tuotepaikat.tuoteno = tilausrivi.tuoteno
							and keratty != ''
							and laskutettu = ''
							and varattu <> 0
							and tyyppi in ('L','G','V')
							and tuotepaikat.hyllyalue = tilausrivi.hyllyalue
							and tuotepaikat.hyllynro  = tilausrivi.hyllynro
							and tuotepaikat.hyllyvali = tilausrivi.hyllyvali
							and tuotepaikat.hyllytaso = tilausrivi.hyllytaso
							and '$row[hyllyalue]' = tilausrivi.hyllyalue
							and '$row[hyllynro]'  = tilausrivi.hyllynro
							and '$row[hyllyvali]' = tilausrivi.hyllyvali
							and '$row[hyllytaso]' = tilausrivi.hyllytaso
							and tuotepaikat.yhtio = '$kukarow[yhtio]'
							and tuotepaikat.tuoteno='$row[tuoteno]'";
				$kerresult = mysql_query($query) or pupe_error($query);
				$kerrow = mysql_fetch_array ($kerresult);
				$hyllyssa = $srow['saldo'] - $kerrow['kermaara'];

				//saldolaskentaa tulevaisuuteen
				$query = "	SELECT sum(varattu) varattu, min(toimaika) toimaika
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$row[tuoteno]' and tyyppi='O' and varattu > 0";
				$result2 = mysql_query($query) or pupe_error($query);
				$prow = mysql_fetch_array($result2);

				echo "	<tr><td>$row[tuotepaikka]</td><td>$row[tuoteno]</td><td>$row[nimitys]</td><td>$srow[toim_tuoteno]</td><td>".str_replace(".",",",$row['tilkpl'])."</td><td>".str_replace(".",",",$row['kpl'])."</td>
						<td>".str_replace(".",",",$prow['varattu'])."</td><td>$prow[toimaika]</td><td>".str_replace(".",",",sprintf("%.2f",$hyllyssa))."</td><td>".str_replace(".",",",$srow['saldo'])."</td><td>$row[keratty]</td></tr>";

			}

			echo "</table>";

		}
		else {
			echo "<br>".t("Ei tuotteita väärillä saldoilla")."!<br>";
		}
	}

	//Käyttöliittymä
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<tr><th>Syötä alkupäivämäärä (pp-kk-vvvv)</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'>
			<input type='text' name='kka' value='$kka' size='3'>
			<input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>Syötä loppupäivämäärä (pp-kk-vvvv)</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'>
			<input type='text' name='kkl' value='$kkl' size='3'>
			<input type='text' name='vvl' value='$vvl' size='5'>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";



	require ("../inc/footer.inc");
?>