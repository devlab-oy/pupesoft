<?php

	echo "<font class='head'>".t("Varastonarvo selvittelyä")."</font><hr>";

	if (!isset($app)) $app = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($akk)) $akk = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($avv)) $avv = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($lpp)) $lpp = date("d");
	if (!isset($lkk)) $lkk = date("m");
	if (!isset($lvv)) $lvv = date("Y");

	echo "<form>";
 	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Alkupäivä")."</th>";
	echo "<td><input type='text' name='app' size='5' value='$app'></td>";
	echo "<td><input type='text' name='akk' size='5' value='$akk'></td>";
	echo "<td><input type='text' name='avv' size='5' value='$avv'></td>";
	echo "</tr><tr>";
	echo "<th>".t("Loppupäivä")."</th>";
	echo "<td><input type='text' name='lpp' size='5' value='$lpp'></td>";
	echo "<td><input type='text' name='lkk' size='5' value='$lkk'></td>";
	echo "<td><input type='text' name='lvv' size='5' value='$lvv'></td><td class='back'><input type='submit' value='".t("Hae laskut")."'></td>";
	echo "</tr>";
	echo "</table>";

	echo "</form>";

	echo "<br><font class='head'>".t("Laskut joita ei ole liitetty keikkoihin")."</font><hr>";

	$query = "	SELECT lasku.ytunnus, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.tapvm, lasku.vienti, lasku2.tunnus, lasku.tunnus latunnus,
				round(lasku.summa*(if(lasku.maksu_kurssi=0, lasku.vienti_kurssi, lasku.maksu_kurssi)),2) 'summaeur'
				FROM lasku
				LEFT JOIN lasku AS lasku2 on (lasku2.yhtio = lasku.yhtio and lasku2.vanhatunnus = lasku.tunnus and lasku2.tila = 'K')
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila in ('H','Y','M','P','Q') and
				lasku.vienti in ('B','C','J','E','F','K','H','I','L') and
				lasku.tapvm >= '$avv-$akk-$app' and
				lasku.tapvm <= '$lvv-$lkk-$lpp'
				GROUP by lasku.tunnus
				HAVING lasku2.tunnus IS NULL
				ORDER BY lasku.tapvm, lasku.valkoodi, lasku.summa";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0 ) {
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Summa valuutassa")."</th>";
		echo "<th>".t("Summa")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Tyyppi")."</th>";
		echo "</tr>";

		$yhteesa = 0;

		while ($trow = mysql_fetch_array($result)) {

			$vienti = $trow["vienti"];
			if ($trow["vienti"] == 'B') $vienti = t('Kotimaa huolinta/rahti');
			if ($trow["vienti"] == 'C') $vienti = t('Kotimaa vaihto-omaisuus');
			if ($trow["vienti"] == 'J') $vienti = t('Kotimaa raaka-aine');
			if ($trow["vienti"] == 'E') $vienti = t('EU huolinta/rahti');
			if ($trow["vienti"] == 'F') $vienti = t('EU vaihto-omaisuus');
			if ($trow["vienti"] == 'K') $vienti = t('EU raaka-aine');
			if ($trow["vienti"] == 'H') $vienti = t('ei-EU huolinta/rahti');
			if ($trow["vienti"] == 'I') $vienti = t('ei-EU vaihto-omaisuus');
			if ($trow["vienti"] == 'L') $vienti = t('ei-EU raaka-aine');

			echo "<tr class='aktiivi'>";
			echo "<td><a href='$palvelin2","muutosite.php?tee=E&tunnus=$trow[latunnus]'>$trow[ytunnus]</a></td>";
			echo "<td>$trow[nimi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["summa"])." $trow[valkoodi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["summaeur"])." $yhtiorow[valkoodi]</td>";
			echo "<td>".tv1dateconv($trow["tapvm"])."</td>";
			echo "<td>$vienti</td>";
			echo "</tr>";

			$yhteesa += $trow["summaeur"];
		}

		echo "<tr>";
		echo "<th colspan='3'>".t("Yhteensä")."</th>";
		echo "<th style='text-align: right;'>".round($yhteesa,2)." $yhtiorow[valkoodi]</th>";
		echo "<th colspan='2'></th>";
		echo "</tr>";
		echo "</table><br>";
	}
	else {
		echo "<font class='message'>".t("Ei laskuja")."!</font><br><br>";
	}

	echo "<br><font class='head'>".t("Rahtilaskut, joita ei ole liitetty kokonaan keikkoihin")."</font><hr>";

	$query = "	SELECT lasku.ytunnus, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.tapvm, lasku.vienti, lasku2.tunnus, lasku.tunnus latunnus,
				sum(tiliointi.summa) 'varastossa', 
				round(lasku.summa*(if(lasku.maksu_kurssi=0, lasku.vienti_kurssi, lasku.maksu_kurssi)),2) 'summaeur',
				round(sum(lasku2.summa*(if(lasku2.maksu_kurssi=0, lasku2.vienti_kurssi, lasku2.maksu_kurssi))),2) 'kohdistettu'
				FROM lasku
				LEFT JOIN lasku AS lasku2 on (lasku2.yhtio = lasku.yhtio and lasku2.vanhatunnus = lasku.tunnus and lasku2.tila = 'K')
				LEFT JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio and
										tiliointi.ltunnus = lasku.tunnus and
										tiliointi.tilino in ('$yhtiorow[varasto]','$yhtiorow[raaka_ainevarasto]') and
										tiliointi.korjattu = '')
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila in ('H','Y','M','P','Q') and
				lasku.vienti in ('B','J','E','K','H','L') and
				lasku.tapvm >= '$avv-$akk-$app' and
				lasku.tapvm <= '$lvv-$lkk-$lpp'
				GROUP by lasku.tunnus
				HAVING varastossa != kohdistettu
				ORDER BY lasku.tapvm, lasku.valkoodi, lasku.summa";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0 ) {
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Lasku valuutassa")."</th>";
		echo "<th>".t("Lasku")."</th>";
		echo "<th>".t("Liitetty keikkaan")."</th>";
		echo "<th>".t("Varastotilillä")."</th>";
		echo "<th>".t("Erotus")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Tyyppi")."</th>";
		echo "</tr>";

		$yhteesa = 0;

		while ($trow = mysql_fetch_array($result)) {

			$vienti = $trow["vienti"];
			if ($trow["vienti"] == 'B') $vienti = t('Kotimaa huolinta/rahti');
			if ($trow["vienti"] == 'C') $vienti = t('Kotimaa vaihto-omaisuus');
			if ($trow["vienti"] == 'J') $vienti = t('Kotimaa raaka-aine');
			if ($trow["vienti"] == 'E') $vienti = t('EU huolinta/rahti');
			if ($trow["vienti"] == 'F') $vienti = t('EU vaihto-omaisuus');
			if ($trow["vienti"] == 'K') $vienti = t('EU raaka-aine');
			if ($trow["vienti"] == 'H') $vienti = t('ei-EU huolinta/rahti');
			if ($trow["vienti"] == 'I') $vienti = t('ei-EU vaihto-omaisuus');
			if ($trow["vienti"] == 'L') $vienti = t('ei-EU raaka-aine');

			echo "<tr class='aktiivi'>";
			echo "<td><a href='$palvelin2","muutosite.php?tee=E&tunnus=$trow[latunnus]'>$trow[ytunnus]</a></td>";
			echo "<td>$trow[nimi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["summa"])." $trow[valkoodi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["summaeur"])." $yhtiorow[valkoodi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["kohdistettu"])." $yhtiorow[valkoodi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["varastossa"])." $yhtiorow[valkoodi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["kohdistettu"]-$trow["varastossa"])." $yhtiorow[valkoodi]</td>";
			echo "<td>".tv1dateconv($trow["tapvm"])."</td>";
			echo "<td>$vienti</td>";
			echo "</tr>";

			$yhteesa += ($trow["kohdistettu"] - $trow["varastossa"]);
		}

		echo "<tr>";
		echo "<th colspan='6'>".t("Yhteensä")."</th>";
		echo "<th style='text-align: right;'>".round($yhteesa,2)." $yhtiorow[valkoodi]</th>";
		echo "<th colspan='2'></th>";
		echo "</tr>";
		echo "</table><br>";
	}
	else {
		echo "<font class='message'>".t("Ei laskuja")."!</font><br><br>";
	}

	echo "<font class='head'>".t("Käsin viedyt tiliöinnit")."</font><hr>";

	$query = "	SELECT tiliointi.*, tili.nimi tilinimi, kustannuspaikka1.nimi kustpnimi, kustannuspaikka2.nimi kohdenimi, kustannuspaikka3.nimi projektinimi
				FROM lasku
				JOIN tiliointi on (	tiliointi.yhtio = lasku.yhtio and
									tiliointi.ltunnus = lasku.tunnus and
									tiliointi.tilino in ('$yhtiorow[varasto]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarastonmuutos]') and
									tiliointi.korjattu = '' and
									tiliointi.selite not like ('Inventointi%') and
									tiliointi.selite not like ('%epäkuranttimuutos') and
									tiliointi.selite not like ('%epäkurantttimuutos'))
				LEFT JOIN tili ON (tili.yhtio = lasku.yhtio and tili.tilino = tiliointi.tilino)
				LEFT JOIN kustannuspaikka AS kustannuspaikka1 ON (kustannuspaikka1.yhtio = lasku.yhtio and kustannuspaikka1.tunnus = tiliointi.kustp)
				LEFT JOIN kustannuspaikka AS kustannuspaikka2 ON (kustannuspaikka2.yhtio = lasku.yhtio and kustannuspaikka2.tunnus = tiliointi.kohde)
				LEFT JOIN kustannuspaikka AS kustannuspaikka3 ON (kustannuspaikka3.yhtio = lasku.yhtio and kustannuspaikka3.tunnus = tiliointi.projekti)
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila = 'X' and
				lasku.tapvm >= '$avv-$akk-$app' and
				lasku.tapvm <= '$lvv-$lkk-$lpp'
				ORDER BY tiliointi.tapvm, tiliointi.summa";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0 ) {
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Tili")."</th>";
		echo "<th>".t("Kustp")."</th>";
		echo "<th>".t("Kohde")."</th>";
		echo "<th>".t("Proj")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Summa")."</th>";
		echo "<th>".t("Alv")."</th>";
		echo "<th>".t("Selite")."</th>";
		echo "</tr>";

		$yhteesa = 0;

		while ($trow = mysql_fetch_array($result)) {

			echo "<tr class='aktiivi'>";
			echo "<td><a href='$palvelin2","muutosite.php?tee=E&tunnus=$trow[ltunnus]'>$trow[tilino] - $trow[tilinimi]</a></td>";
			echo "<td>$trow[kustpnimi]</td>";
			echo "<td>$trow[kohdenimi]</td>";
			echo "<td>$trow[projektinimi]</td>";
			echo "<td>".tv1dateconv($trow["tapvm"])."</td>";
			echo "<td style='text-align: right;' nowrap>$trow[summa] $yhtiorow[valkoodi]</td>";
			echo "<td>$trow[alv]</td>";
			echo "<td>$trow[selite]</td>";
			echo "</tr>";

			$yhteesa += $trow["summa"];

		}

		echo "<tr>";
		echo "<th colspan='5'>".t("Yhteensä")."</th>";
		echo "<th style='text-align: right;'>".round($yhteesa,2)." $yhtiorow[valkoodi]</th>";
		echo "<th colspan='2'></th>";
		echo "</tr>";
		echo "</table><br>";
	}
	else {
		echo "<font class='message'>".t("Ei tiliöintejä")."!</font><br><br>";
	}

	echo "<font class='head'>".t("Varastonarvoon vaikuttavat laskut joilla ei ole varastokirjauksia")."</font><hr>";

	$query = "	SELECT lasku.ytunnus, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.summa*(if(lasku.maksu_kurssi=0, lasku.vienti_kurssi, lasku.maksu_kurssi)) 'summaeur', lasku.tapvm, lasku.vienti, lasku.tunnus latunnus, tiliointi1.ltunnus, tiliointi2.ltunnus
				FROM lasku
				LEFT JOIN tiliointi AS tiliointi1 ON (tiliointi1.yhtio = lasku.yhtio and
									tiliointi1.ltunnus = lasku.tunnus and
									tiliointi1.tilino in ('$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarastonmuutos]') and
									tiliointi1.korjattu = '')
				LEFT JOIN tiliointi AS tiliointi2 ON (tiliointi2.yhtio = lasku.yhtio and
									tiliointi2.ltunnus = lasku.tunnus and
									tiliointi2.tilino in ('$yhtiorow[varasto]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[matkalla_olevat]') and
									tiliointi2.korjattu = '')
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila in ('H','Y','M','P','Q') and
				lasku.vienti in ('B','C','J','E','F','K','H','I','L') and
				lasku.tapvm >= '$avv-$akk-$app' and
				lasku.tapvm <= '$lvv-$lkk-$lpp'
				HAVING tiliointi1.ltunnus IS NULL or tiliointi2.ltunnus IS NULL
				ORDER BY lasku.tapvm, lasku.valkoodi, lasku.summa";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0 ) {
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Summa valuutassa")."</th>";
		echo "<th>".t("Summa")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Tyyppi")."</th>";
		echo "</tr>";

		$yhteesa = 0;

		while ($trow = mysql_fetch_array($result)) {

			$vienti = $trow["vienti"];
			if ($trow["vienti"] == 'B') $vienti = t('Kotimaa huolinta/rahti');
			if ($trow["vienti"] == 'C') $vienti = t('Kotimaa vaihto-omaisuus');
			if ($trow["vienti"] == 'J') $vienti = t('Kotimaa raaka-aine');
			if ($trow["vienti"] == 'E') $vienti = t('EU huolinta/rahti');
			if ($trow["vienti"] == 'F') $vienti = t('EU vaihto-omaisuus');
			if ($trow["vienti"] == 'K') $vienti = t('EU raaka-aine');
			if ($trow["vienti"] == 'H') $vienti = t('ei-EU huolinta/rahti');
			if ($trow["vienti"] == 'I') $vienti = t('ei-EU vaihto-omaisuus');
			if ($trow["vienti"] == 'L') $vienti = t('ei-EU raaka-aine');

			echo "<tr class='aktiivi'>";
			echo "<td><a href='$palvelin2","muutosite.php?tee=E&tunnus=$trow[latunnus]'>$trow[ytunnus]</a></td>";
			echo "<td>$trow[nimi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["summa"])." $trow[valkoodi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["summaeur"])." $yhtiorow[valkoodi]</td>";
			echo "<td>".tv1dateconv($trow["tapvm"])."</td>";
			echo "<td>$vienti</td>";
			echo "</tr>";

			$yhteesa += $trow["summaeur"];
		}

		echo "<tr>";
		echo "<th colspan='3'>".t("Yhteensä")."</th>";
		echo "<th style='text-align: right;'>".round($yhteesa,2)." $yhtiorow[valkoodi]</th>";
		echo "<th colspan='2'></th>";
		echo "</tr>";
		echo "</table><br>";
	}
	else {
		echo "<font class='message'>".t("Ei laskuja")."!</font><br><br>";
	}

	echo "<font class='head'>".t("Keikkojen eturahdit")."</font><hr>";

	$query = "	SELECT *, lasku.rahti_etu*(if(lasku.maksu_kurssi=0, lasku.vienti_kurssi, lasku.maksu_kurssi)) rahti
				FROM lasku
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila = 'K' and
				lasku.vanhatunnus = 0 and
				lasku.tapvm >= '$avv-$akk-$app' and
				lasku.tapvm <= '$lvv-$lkk-$lpp'
				HAVING abs(rahti) > 0.05
				ORDER BY lasku.tapvm, lasku.rahti_etu";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0 ) {
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Keikka")."</th>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Eturahti")."</th>";
		echo "</tr>";

		$yhteesa = 0;

		while ($trow = mysql_fetch_array($result)) {

			echo "<tr class='aktiivi'>";
			echo "<td>$trow[laskunro]</td>";
			echo "<td>$trow[ytunnus]</td>";
			echo "<td>$trow[nimi]</td>";
			echo "<td>".tv1dateconv($trow["tapvm"])."</td>";
			echo "<td style='text-align: right;' nowrap>".round($trow["rahti"],2)." $yhtiorow[valkoodi]</td>";
			echo "</tr>";

			$yhteesa += $trow["rahti"];
		}

		echo "<tr>";
		echo "<th colspan='4'>".t("Yhteensä")."</th>";
		echo "<th style='text-align: right;'>".round($yhteesa,2)." $yhtiorow[valkoodi]</th>";
		echo "</tr>";
		echo "</table><br>";
	}
	else {
		echo "<font class='message'>".t("Ei keikkoja")."!</font><br><br>";
	}

?>