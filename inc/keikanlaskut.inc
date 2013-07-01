<?php

	enable_ajax();

	if ($toim_tee == "kululasku") {

		$tunnus = mysql_real_escape_string($tunnus);

		// Haetaan lasku
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tunnus = '$tunnus'
					and lasku.tila in ('H','Y','M','P','Q')
					and lasku.vienti in ('B','C','J','E','F','K','H','I','L')
					and lasku.tapvm >= '$yhtiorow[tilikausi_alku]'";
		$result = pupe_query($query);

		if ($laskurow = mysql_fetch_array($result)) {

			$vienti = "";

			// kotimaa
			if (in_array($laskurow["vienti"], array('B', 'C', 'J'))) {
				$vienti = "A";
			}
			// eu
			if (in_array($laskurow["vienti"], array('E', 'F', 'K'))) {
				$vienti = "D";
			}
			// ei-eu
			if (in_array($laskurow["vienti"], array('H', 'I', 'L'))) {
				$vienti = "G";
			}

			if ($vienti != "") {
				// Poistetaan kirjanpidosta varastoviennit
				$query = "	UPDATE tiliointi
							SET korjattu = '$kukarow[kuka]',
							korjausaika = now()
							WHERE yhtio ='$kukarow[yhtio]'
							and ltunnus = '$tunnus'
							and korjattu = ''
							and tilino in ('$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[matkalla_olevat]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]')";
				$updres = pupe_query($query);

				// Päivitetään laskun tila
				$query = "	UPDATE lasku
							SET vienti = '$vienti'
							where yhtio = '$kukarow[yhtio]'
							and tunnus = '$tunnus'";
				$updres = pupe_query($query);
			}
			else {
				echo "<font class='error'>".t("Lasku on jo kululasku")."!</font><br><br>";
			}
		}
		else {
			echo "<font class='error'>".t("Laskua ei voida muuttaa")."!</font><br><br>";
		}

	}

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

	echo "<br><font class='head'>".t("Laskut joita ei ole liitetty saapumisiin")."</font><hr>";

	$query = "	SELECT lasku.ytunnus,
				lasku.nimi,
				lasku.summa,
				lasku.valkoodi,
				lasku.tapvm,
				lasku.vienti,
				liitos.tunnus,
				lasku.tunnus latunnus,
				lasku.alv,
				if (lasku.vienti in ('B','E','H'), 2, 1) sorttaus,
				round(lasku.summa*lasku.vienti_kurssi,2) 'summaeur',
				if(lasku.laskunro = 0, '', lasku.laskunro) laskunro,
				round(lasku.osto_rahti + lasku.osto_kulu + lasku.osto_rivi_kulu, 2) 'kulut'
				FROM lasku
				LEFT JOIN lasku AS liitos on (liitos.yhtio = lasku.yhtio and liitos.vanhatunnus = lasku.tunnus and liitos.tila = 'K')
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila in ('H','Y','M','P','Q') and
				lasku.vienti in ('B','C','J','E','F','K','H','I','L') and
				lasku.tapvm >= '$avv-$akk-$app' and
				lasku.tapvm <= '$lvv-$lkk-$lpp'
				GROUP BY 1,2,3,4,5,6,7,8,9,10
				HAVING liitos.tunnus IS NULL
				ORDER BY sorttaus, lasku.vienti, lasku.tapvm, lasku.valkoodi, lasku.summa";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0 ) {
		echo mysql_num_rows($result)." laskua. <a href=\"javascript:toggleGroup('eee')\">Näytä / Piilota</a><br><br>";

		if ($toim_tee != "") {
			echo "<div id='eee' style='display:block'>";
		}
		else {
			echo "<div id='eee' style='display:none'>";
		}

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Summa valuutassa")."</th>";
		echo "<th>".t("Summa")."</th>";
		echo "<th>".t("Kulut")."</th>";
		echo "<th>".t("Veroton summa")."</th>";
		echo "<th>".t("Laskunro")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Tyyppi")."</th>";
		echo "</tr>";

		$yhteesa_ve = 0;
		$yhteesa_eive = 0;
		$edellinen = 0;

		$vaihto_arvo_yhteensa = 0;
		$rahti_arvo_yhteensa  = 0;
		$vaihto_sum_yhteensa  = 0;
		$rahti_sum_yhteensa   = 0;

		while ($trow = mysql_fetch_array($result)) {

			// jos meillä on hyvityslasku niin haetaan vaan negatiivisiä alvikirjauksia (en tiedä ollenkaan onko tämä futureprooof) :(
			if ($trow["summaeur"] < 0) {
				$alvilisa = "and summa < 0";
			}
			else {
				$alvilisa = "and summa > 0";
			}

			// Haetaan kululaskun kaikki verotiliöinnit jotta voidaan tallentaa myös veroton summa
			$query = "	SELECT sum(summa) summa
						from tiliointi
						where yhtio	= '$kukarow[yhtio]'
						and ltunnus	= '$trow[latunnus]'
						and tilino  = '$yhtiorow[alv]'
						$alvilisa
						and korjattu = ''";
			$alvires = pupe_query($query);
			$alvirow = mysql_fetch_array($alvires);

			// Ostoreskontralaskun veroton arvo
			$alvisumma = $alvirow["summa"];

			if (strtoupper($trow["valkoodi"]) != strtoupper($yhtiorow["valkoodi"])) {
				$alvisumma = $alvirow["summa"] / $oslasrow["vienti_kurssi"];
			}

			$trow["arvo"] = round((float) $trow["summaeur"] - (float) $alvisumma - (float) $trow['kulut'], 2);

			$vienti = $trow["vienti"];
			if ($trow["vienti"] == 'A') $vienti = t('Kotimaa');
			if ($trow["vienti"] == 'B') $vienti = t('Kotimaa huolinta/rahti');
			if ($trow["vienti"] == 'C') $vienti = t('Kotimaa vaihto-omaisuus');
			if ($trow["vienti"] == 'J') $vienti = t('Kotimaa raaka-aine');
			if ($trow["vienti"] == 'D') $vienti = t('EU');
			if ($trow["vienti"] == 'E') $vienti = t('EU huolinta/rahti');
			if ($trow["vienti"] == 'F') $vienti = t('EU vaihto-omaisuus');
			if ($trow["vienti"] == 'K') $vienti = t('EU raaka-aine');
			if ($trow["vienti"] == 'G') $vienti = t('ei-EU');
			if ($trow["vienti"] == 'H') $vienti = t('ei-EU huolinta/rahti');
			if ($trow["vienti"] == 'I') $vienti = t('ei-EU vaihto-omaisuus');
			if ($trow["vienti"] == 'L') $vienti = t('ei-EU raaka-aine');

			echo "<tr class='aktiivi'>";
			echo "<td><a name='$trow[latunnus]' href='$palvelin2","muutosite.php?tee=E&tunnus=$trow[latunnus]'>$trow[ytunnus]</a></td>";
			echo "<td>$trow[nimi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["summa"])." $trow[valkoodi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["summaeur"])." $yhtiorow[valkoodi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["kulut"])." $yhtiorow[valkoodi]</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["arvo"])." $yhtiorow[valkoodi]</td>";
			echo "<td style='text-align: right;' nowrap>$trow[laskunro]</td>";
			echo "<td>".tv1dateconv($trow["tapvm"])."</td>";
			echo "<td>$vienti</td>";

			// jos lasku on kuluvalla tilikaudella, annetaan mahdollisuus muuttaa tila
			if ($trow["tapvm"] >= $yhtiorow["tilikausi_alku"] and ($kukarow["taso"] == "2" or $kukarow["taso"] == "3")) {
				echo "<td class='back'>";
				echo "<form method='post' action='#$edellinen' onSubmit='return confirm(\"$trow[nimi]\\n$vienti\\n".tv1dateconv($trow["tapvm"])."\\n$trow[summa] $trow[valkoodi]\\n\\n".t("Oletko varma, että haluat muuttaa laskun kululaskuksi")."?\")'>";
			 	echo "<input type='hidden' name='toim' value='$toim'>";
			 	echo "<input type='hidden' name='toim_tee' value='kululasku'>";
			 	echo "<input type='hidden' name='tunnus' value='$trow[latunnus]'>";
				echo "<input type='hidden' name='app' value='$app'>";
				echo "<input type='hidden' name='akk' value='$akk'>";
				echo "<input type='hidden' name='lpp' value='$lpp'>";
				echo "<input type='hidden' name='lkk' value='$lkk'>";
				echo "<input type='hidden' name='lvv' value='$lvv'>";
				echo "<input type='submit' value='".t("Muuta kululaskuksi")."'>";
				echo "</form>";
				echo "</td>";
			}

			echo "</tr>";

			if ($trow["sorttaus"] == 1) {
				$vaihto_sum_yhteensa += $trow["summaeur"];
				$vaihto_arvo_yhteensa += $trow["arvo"];
			}
			else {
				$rahti_sum_yhteensa += $trow["summaeur"];
				$rahti_arvo_yhteensa += $trow["arvo"];
			}

			$edellinen = $trow["latunnus"];
			$yhteesa_ve += $trow["summaeur"];
			$yhteesa_eive += $trow["arvo"];
		}

		echo "<tr>";
		echo "<th colspan='3'>".t("Vaihto-omaisuuslaskuja yhteensä")."</th>";
		echo "<th style='text-align: right;'>".round($vaihto_sum_yhteensa,2)." $yhtiorow[valkoodi]</th>";
		echo "<th></th>";
		echo "<th style='text-align: right;'>".round($vaihto_arvo_yhteensa,2)." $yhtiorow[valkoodi]</th>";
		echo "<th colspan='3'></th>";
		echo "</tr>";

		echo "<tr>";
		echo "<th colspan='3'>".t("Rahti/huolintalaskuja yhteensä")."</th>";
		echo "<th style='text-align: right;'>".round($rahti_sum_yhteensa,2)." $yhtiorow[valkoodi]</th>";
		echo "<th></th>";
		echo "<th style='text-align: right;'>".round($rahti_arvo_yhteensa,2)." $yhtiorow[valkoodi]</th>";
		echo "<th colspan='3'></th>";
		echo "</tr>";


		echo "<tr>";
		echo "<th colspan='3'>".t("Yhteensä")."</th>";
		echo "<th style='text-align: right;'>".round($yhteesa_ve,2)." $yhtiorow[valkoodi]</th>";
		echo "<th></th>";
		echo "<th style='text-align: right;'>".round($yhteesa_eive,2)." $yhtiorow[valkoodi]</th>";
		echo "<th colspan='3'></th>";
		echo "</tr>";
		echo "</table><br>";
		echo "</div>";
	}
	else {
		echo "<font class='message'>".t("Ei laskuja")."!</font><br><br>";
	}

	echo "<font class='head'>".t("Rahtilaskut joita ei ole liitetty kokonaan saapumisiin")."</font><hr>";

	$query = "	SELECT lasku.ytunnus, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.tapvm, lasku.vienti, liitos.tunnus, lasku.tunnus latunnus,
				(	SELECT sum(summa) summa
					FROM tiliointi
					WHERE tiliointi.yhtio = lasku.yhtio
					AND	tiliointi.ltunnus = lasku.tunnus
					AND	tiliointi.tilino in ('$yhtiorow[varasto]','$yhtiorow[raaka_ainevarasto]')
					AND	tiliointi.korjattu = '') varastossa,
				round(lasku.summa*lasku.vienti_kurssi,2) 'summaeur',
				round(sum(liitos.arvo*liitos.vienti_kurssi),2) 'kohdistettu'
				FROM lasku
				LEFT JOIN lasku AS liitos on (liitos.yhtio = lasku.yhtio and liitos.vanhatunnus = lasku.tunnus and liitos.tila = 'K')
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				AND lasku.tila in ('H','Y','M','P','Q')
				AND lasku.vienti in ('B','E','H')
				AND lasku.tapvm >= '$avv-$akk-$app'
				AND lasku.tapvm <= '$lvv-$lkk-$lpp'
				GROUP BY lasku.tunnus
				HAVING varastossa != kohdistettu
				ORDER BY lasku.tapvm, lasku.valkoodi, lasku.summa";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0 ) {
		echo mysql_num_rows($result)." laskua. <a href=\"javascript:toggleGroup('kaa')\">Näytä / Piilota</a><br><br>";

		echo "<div id='kaa' style='display:none'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Lasku valuutassa")."</th>";
		echo "<th>".t("Lasku")."</th>";
		echo "<th>".t("Liitetty saapumiseen")."</th>";
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
		echo "</div>";
	}
	else {
		echo "<font class='message'>".t("Ei laskuja")."!</font><br><br>";
	}

	echo "<font class='head'>".t("Käsin viedyt tiliöinnit")."</font><hr>";

	$query = "	SELECT tiliointi.*, tili.nimi tilinimi, kustannuspaikka1.nimi kustpnimi, kustannuspaikka2.nimi kohdenimi, kustannuspaikka3.nimi projektinimi
				FROM lasku
				JOIN tiliointi ON (	tiliointi.yhtio = lasku.yhtio
									and tiliointi.ltunnus = lasku.tunnus
									and tiliointi.tilino in ('$yhtiorow[varasto]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]')
									and tiliointi.korjattu = ''
									and tiliointi.selite not like ('Inventointi%')
									and tiliointi.selite not like ('Epäkuranttimuutos%'))
				LEFT JOIN tili ON (tili.yhtio = lasku.yhtio and tili.tilino = tiliointi.tilino)
				LEFT JOIN kustannuspaikka AS kustannuspaikka1 ON (kustannuspaikka1.yhtio = lasku.yhtio and kustannuspaikka1.tunnus = tiliointi.kustp)
				LEFT JOIN kustannuspaikka AS kustannuspaikka2 ON (kustannuspaikka2.yhtio = lasku.yhtio and kustannuspaikka2.tunnus = tiliointi.kohde)
				LEFT JOIN kustannuspaikka AS kustannuspaikka3 ON (kustannuspaikka3.yhtio = lasku.yhtio and kustannuspaikka3.tunnus = tiliointi.projekti)
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				AND lasku.tila = 'X'
				AND lasku.tapvm >= '$avv-$akk-$app'
				AND lasku.tapvm <= '$lvv-$lkk-$lpp'
				ORDER BY tiliointi.tapvm, tiliointi.summa";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0 ) {
		echo mysql_num_rows($result)." tiliöintiä. <a href=\"javascript:toggleGroup('koo')\">Näytä / Piilota</a><br><br>";

		echo "<div id='koo' style='display:none'>";

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
		echo "</div>";
	}
	else {
		echo "<font class='message'>".t("Ei tiliöintejä")."!</font><br><br>";
	}

	echo "<font class='head'>".t("Varastonarvoon vaikuttavat laskut joilla ei ole varastokirjauksia")."</font><hr>";

	$query = "	SELECT lasku.ytunnus, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.summa*lasku.vienti_kurssi 'summaeur', lasku.tapvm, lasku.vienti, lasku.tunnus latunnus, tiliointi1.ltunnus, tiliointi2.ltunnus
				FROM lasku
				LEFT JOIN tiliointi AS tiliointi1 ON (tiliointi1.yhtio = lasku.yhtio and
									tiliointi1.ltunnus = lasku.tunnus and
									tiliointi1.tilino in ('$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]') and
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
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0 ) {
		echo mysql_num_rows($result)." laskua. <a href=\"javascript:toggleGroup('nee')\">Näytä / Piilota</a><br><br>";

		echo "<div id='nee' style='display:none'>";

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
		echo "</div>";
	}
	else {
		echo "<font class='message'>".t("Ei laskuja")."!</font><br><br>";
	}

	echo "<font class='head'>".t("Normaalit kululaskut joilla on varastokirjauksia")."</font><hr>";

	$query = "	SELECT lasku.ytunnus, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.summa*lasku.vienti_kurssi 'summaeur', lasku.tapvm, lasku.vienti, lasku.tunnus latunnus, tiliointi1.ltunnus
				FROM lasku
				JOIN tiliointi AS tiliointi1 ON (tiliointi1.yhtio = lasku.yhtio and
									tiliointi1.ltunnus = lasku.tunnus and
									tiliointi1.tilino in ('$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]', '$yhtiorow[varasto]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[matkalla_olevat]') and
									tiliointi1.korjattu = '')
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila in ('H','Y','M','P','Q') and
				lasku.vienti in ('A','D','G') and
				lasku.tapvm >= '$avv-$akk-$app' and
				lasku.tapvm <= '$lvv-$lkk-$lpp'
				GROUP BY lasku.tunnus
				ORDER BY lasku.tapvm, lasku.valkoodi, lasku.summa";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0 ) {
		echo mysql_num_rows($result)." laskua. <a href=\"javascript:toggleGroup('vii')\">Näytä / Piilota</a><br><br>";

		echo "<div id='vii' style='display:none'>";

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
			if ($trow["vienti"] == 'A') $vienti = t('Kotimaa');
			if ($trow["vienti"] == 'D') $vienti = t('EU');
			if ($trow["vienti"] == 'G') $vienti = t('ei-EU');

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
		echo "</div>";
	}
	else {
		echo "<font class='message'>".t("Ei laskuja")."!</font><br><br>";
	}

	echo "<font class='head'>".t("Laskut joiden varastokirjaukset eivät täsmää")."</font><hr>";

	if ($yhtiorow['ostolaskun_kulutilit_kayttaytyminen'] == 'E') {
		$havinglisa = "abs(summa1 + summa2) > 0.01 or abs(summaeur-summa3+summa1) > 0.01 or abs(summaeur-summa3-summa2) > 0.01";
	}
	else {
		$havinglisa = "abs(summa1 + summa2) > 0.01 or abs(summaeur-summa3-osto_rahti-osto_kulu-osto_rivi_kulu+summa1) > 0.01 or abs(summaeur-summa3-osto_rahti-osto_kulu-osto_rivi_kulu-summa2) > 0.01";
	}

	$query = "	SELECT lasku.ytunnus, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.summa*lasku.vienti_kurssi 'summaeur',
				lasku.tapvm, lasku.vienti, lasku.tunnus latunnus,
				lasku.osto_rahti/(1+lasku.osto_rahti_alv/100) osto_rahti,
				lasku.osto_kulu/(1+lasku.osto_kulu_alv/100) osto_kulu,
				lasku.osto_rivi_kulu/(1+lasku.osto_rivi_kulu_alv/100) osto_rivi_kulu,
				(SELECT sum(summa)
					FROM tiliointi AS tiliointi1
					WHERE tiliointi1.yhtio = lasku.yhtio and tiliointi1.ltunnus = lasku.tunnus and tiliointi1.tilino in ('$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]') and tiliointi1.korjattu = ''
				) summa1,
				(SELECT sum(summa)
					FROM tiliointi AS tiliointi2
					WHERE tiliointi2.yhtio = lasku.yhtio and tiliointi2.ltunnus = lasku.tunnus and tiliointi2.tilino in ('$yhtiorow[varasto]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[matkalla_olevat]') and tiliointi2.korjattu = ''
				) summa2,
				(SELECT sum(summa)
					FROM tiliointi AS tiliointi2
					WHERE tiliointi2.yhtio = lasku.yhtio and tiliointi2.ltunnus = lasku.tunnus and tiliointi2.tilino in ('$yhtiorow[alv]') and tiliointi2.korjattu = ''
				) summa3
				FROM lasku
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tila in ('H','Y','M','P','Q')
				and lasku.tapvm >= '$avv-$akk-$app'
				and lasku.tapvm <= '$lvv-$lkk-$lpp'
				HAVING {$havinglisa}
				ORDER BY lasku.tapvm, lasku.valkoodi, lasku.summa";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0 ) {
		echo mysql_num_rows($result)." laskua. <a href=\"javascript:toggleGroup('kuu')\">Näytä / Piilota</a><br><br>";

		echo "<div id='kuu' style='display:none'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Summa valuutassa")."</th>";
		echo "<th>".t("Summa")."</th>";
		echo "<th>".t("Arvo")."</th>";
		echo "<th>".t("Varastonmuutos")."</th>";
		echo "<th>".t("Varasto")."</th>";
		echo "<th>".t("Ero")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Tyyppi")."</th>";
		echo "</tr>";

		$yhteesa = 0;

		while ($trow = mysql_fetch_array($result)) {

			$vienti = $trow["vienti"];
			if ($trow["vienti"] == 'A') $vienti = t('Kotimaa');
			if ($trow["vienti"] == 'D') $vienti = t('EU');
			if ($trow["vienti"] == 'G') $vienti = t('ei-EU');
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
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["summaeur"])."</td>";

			echo "<td style='text-align: right;' nowrap>";

			if ($yhtiorow['ostolaskun_kulutilit_kayttaytyminen'] == 'E') {
				echo sprintf("%.2f", $trow["summaeur"]-$trow["summa3"]);
			}
			else {
				echo sprintf("%.2f", $trow["summaeur"]-$trow["summa3"]-$trow["osto_rahti"]-$trow["osto_kulu"]-$trow["osto_rivi_kulu"]);
			}

			echo "</td>";

			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["summa1"]*-1)."</td>";
			echo "<td style='text-align: right;' nowrap>".sprintf("%.2f", $trow["summa2"])."</td>";

			echo "<td style='text-align: right;' nowrap>";

			if ($yhtiorow['ostolaskun_kulutilit_kayttaytyminen'] == 'E') {
				echo sprintf("%.2f", ($trow["summaeur"]-$trow["summa3"]+$trow["summa1"]))." {$yhtiorow['valkoodi']}";
			}
			else {
				echo sprintf("%.2f", ($trow["summaeur"]-$trow["summa3"]-$trow["osto_rahti"]-$trow["osto_kulu"]-$trow["osto_rivi_kulu"]+$trow["summa1"]))." {$yhtiorow['valkoodi']}";
			}

			echo "</td>";
			echo "<td>".tv1dateconv($trow["tapvm"])."</td>";
			echo "<td>$vienti</td>";
			echo "</tr>";

			$yhteesa += ($trow["summaeur"]-$trow["summa3"]-$trow["osto_rahti"]-$trow["osto_kulu"]-$trow["osto_rivi_kulu"]+$trow["summa1"]);
		}

		echo "<tr>";
		echo "<th colspan='7'>".t("Yhteensä")."</th>";
		echo "<th style='text-align: right;'>".round($yhteesa,2)." $yhtiorow[valkoodi]</th>";
		echo "<th colspan='2'></th>";
		echo "</tr>";
		echo "</table><br>";
		echo "</div>";
	}
	else {
		echo "<font class='message'>".t("Ei laskuja")."!</font><br><br>";
	}

	echo "<font class='head'>".t("Saapumisten eturahdit")."</font><hr>";

	$query = "	SELECT *, lasku.rahti_etu*lasku.vienti_kurssi rahti
				FROM lasku
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila = 'K' and
				lasku.vanhatunnus = 0 and
				lasku.tapvm >= '$avv-$akk-$app' and
				lasku.tapvm <= '$lvv-$lkk-$lpp'
				HAVING abs(rahti) > 0.05
				ORDER BY lasku.tapvm, lasku.rahti_etu";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0 ) {
		echo mysql_num_rows($result)." ".t("saapumista").". <a href=\"javascript:toggleGroup('see')\">".t("Näytä")." / ".t("Piilota")."</a><br><br>";

		echo "<div id='see' style='display:none'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Saapuminen")."</th>";
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
			echo "<td style='text-align: right;' nowrap>".round($trow["rahti"], 6)." $yhtiorow[valkoodi]</td>";
			echo "</tr>";

			$yhteesa += $trow["rahti"];
		}

		echo "<tr>";
		echo "<th colspan='4'>".t("Yhteensä")."</th>";
		echo "<th style='text-align: right;'>".round($yhteesa, 6)." $yhtiorow[valkoodi]</th>";
		echo "</tr>";
		echo "</table><br>";
		echo "</div>";
	}
	else {
		echo "<font class='message'>".t("Ei saapumisia")."!</font><br><br>";
	}

	echo "<font class='head'>".t("Saapumisten pyöristyserot")."</font><hr>";

	$query = "	SELECT *, lasku.pyoristys_erot * lasku.vienti_kurssi pyoristys
				FROM lasku
				WHERE lasku.yhtio = '$kukarow[yhtio]' and
				lasku.tila = 'K' and
				lasku.vanhatunnus = 0 and
				lasku.tapvm >= '$avv-$akk-$app' and
				lasku.tapvm <= '$lvv-$lkk-$lpp'
				HAVING abs(pyoristys) > 0.05
				ORDER BY lasku.tapvm, lasku.pyoristys_erot";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0 ) {
		echo mysql_num_rows($result)." ".t("saapumista").". <a href=\"javascript:toggleGroup('pyoristys_erot')\">".t("Näytä")." / ".t("Piilota")."</a><br><br>";

		echo "<div id='pyoristys_erot' style='display:none'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Saapuminen")."</th>";
		echo "<th>".t("Ytunnus")."</th>";
		echo "<th>".t("Nimi")."</th>";
		echo "<th>".t("Tapvm")."</th>";
		echo "<th>".t("Pyöristyserot")."</th>";
		echo "</tr>";

		$yhteesa = 0;

		while ($trow = mysql_fetch_array($result)) {

			echo "<tr class='aktiivi'>";
			echo "<td>$trow[laskunro]</td>";
			echo "<td>$trow[ytunnus]</td>";
			echo "<td>$trow[nimi]</td>";
			echo "<td>".tv1dateconv($trow["tapvm"])."</td>";
			echo "<td style='text-align: right;' nowrap>".round($trow["pyoristys"], 6)." $yhtiorow[valkoodi]</td>";
			echo "</tr>";

			$yhteesa += $trow["pyoristys"];
		}

		echo "<tr>";
		echo "<th colspan='4'>".t("Yhteensä")."</th>";
		echo "<th style='text-align: right;'>".round($yhteesa, 6)." $yhtiorow[valkoodi]</th>";
		echo "</tr>";
		echo "</table><br>";
		echo "</div>";
	}
	else {
		echo "<font class='message'>".t("Ei saapumisia")."!</font><br><br>";
	}

?>
