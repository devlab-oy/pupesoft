<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Varastonarvon tarkastelua")."</font><hr>";

	// tutkaillaan saadut muuttujat
	$pp 	= sprintf("%02d", trim($pp));
	$kk 	= sprintf("%02d", trim($kk));
	$vv 	= sprintf("%04d", trim($vv));

	$pp1 	= sprintf("%02d", trim($pp1));
	$kk1 	= sprintf("%02d", trim($kk1));
	$vv1 	= sprintf("%04d", trim($vv1));

	if ($osasto == "") $osasto = trim($osasto2);
	if ($try    == "")    $try = trim($try2);

	// härski oikeellisuustzekki
	if ($pp == "00" or $kk == "00" or $vv == "0000") $tee = $pp = $kk = $vv = "";
	if ($pp1 == "00" or $kk1 == "00" or $vv1 == "0000") $tee = $pp1 = $kk1 = $vv1 = "";

	// piirrellään formi
	echo "<form method='post' autocomplete='OFF'>";
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Syötä alku pp-kk-vvvv").":</th>";
	echo "<td><input type='text' name='pp' size='5' value='$pp'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='vv' size='7' value='$vv'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>".t("Syötä loppu pp-kk-vvvv").":</th>";
	echo "<td><input type='text' name='pp1' size='5' value='$pp1'><input type='text' name='kk1' size='5' value='$kk1'><input type='text' name='vv1' size='7' value='$vv1'></td>";
	echo "</tr>";
	echo "</table>";

	echo "<br>";
	echo "<input type='hidden' name='tee' value='tee'>";
	echo "<input type='submit' value='".t("Tarkastele")."'>";
	echo "</form>";
	echo "<br><br>";

	if ($tee == "tee") {

		echo "<font class='message'>".t("Logistiikan tapahtumat ja niiden varastonmuutos")."</font><br><br>";

		// haetaan halutut varastotaphtumat
		$query  = "	SELECT laji, count(*) kpl, round(sum(if(laji='tulo', kplhinta, hinta) * kpl), 2) logistiikka
					FROM tapahtuma
					JOIN tuote ON (tapahtuma.yhtio = tuote.yhtio and tapahtuma.tuoteno = tuote.tuoteno and tuote.ei_saldoa = '')
					WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
					and laadittu >= '$vv-$kk-$pp 00:00:00'
					and laadittu <= '$vv1-$kk1-$pp1 23:59:59'
					GROUP BY laji";
		$result = pupe_query($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("laji")."</th>";
		echo "<th>".t("kpl")."</th>";
		echo "<th>".t("tapahtuma")."</th>";
		echo "<th>".t("kirjanpito")."</th>";
		echo "<th>".t("ero")."</th>";
		echo "</tr>";

		$automaatit = 0;
		$summa_array = array();

		while ($trow = mysql_fetch_assoc($result)) {

			echo "<tr class='aktiivi'>";
			echo "<td>$trow[laji]</td>";
			echo "<td align='right'>$trow[kpl]</td>";
			echo "<td align='right'>$trow[logistiikka]</td>";

			//Etsitään vastaavat kirjapidon viennit
			$lvalinta = '';

			if ($trow['laji'] == 'laskutus') 	$lvalinta = "tila = 'U' and alatila = 'X' and selite not like 'Varastoontulo%'";
			if ($trow['laji'] == 'Inventointi') $lvalinta = "tila = 'X' and (selite like 'Inventointi%' or selite like 'KORJATTU: Inventointi%')";
			if ($trow['laji'] == 'Epäkurantti') $lvalinta = "tila = 'X' and selite like '%epäkura%'";
			if ($trow['laji'] == 'tulo') 		$lvalinta = " ((tila in ('H', 'M', 'P', 'Q', 'Y') and vienti in ('B', 'C', 'E', 'F', 'H', 'I')) or (tila = 'U' and alatila = 'X' and selite like 'Varastoontulo%'))";

			if ($lvalinta != '') {
				$query  = "	SELECT sum(tiliointi.summa) summa
							FROM tiliointi use index (yhtio_tilino_tapvm)
							JOIN lasku ON tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus
							WHERE tiliointi.yhtio = '$kukarow[yhtio]'
							and tiliointi.tapvm >= '$vv-$kk-$pp'
							and tiliointi.tapvm <= '$vv1-$kk1-$pp1'
							and tiliointi.tilino in ('$yhtiorow[varasto]', '$yhtiorow[matkalla_olevat]')
							and tiliointi.korjattu = ''
							and $lvalinta";
				$lresult = pupe_query($query);
				$lrow = mysql_fetch_assoc($lresult);

				echo "<td align='right'>$lrow[summa]</td>";
				echo "<td align='right'>".round($trow["logistiikka"] - $lrow["summa"], 2)."</td>";

				$automaatit += $lrow["summa"];

				$summa_array[$trow['laji']]["kpl"] += $trow["kpl"];
				$summa_array[$trow['laji']]["logistiikka"] += $trow["logistiikka"];
				$summa_array[$trow['laji']]["kirjanpito"] += $lrow["summa"];

			}
			else {
				echo "<td align='right'>0.00</td align='right'><td align='right'>0.00</td>";

				$summa_array[$trow['laji']]["kpl"] += $trow["kpl"];
				$summa_array[$trow['laji']]["logistiikka"] += $trow["logistiikka"];
				$summa_array[$trow['laji']]["kirjanpito"] += 0;
			}

			echo "</tr>";
		}

		echo "</table>";

		$query  = "	SELECT sum(tiliointi.summa) summa
					FROM tiliointi
					WHERE tiliointi.yhtio = '$kukarow[yhtio]'
					and tiliointi.tapvm >= '$vv-$kk-$pp'
					and tiliointi.tapvm <= '$vv1-$kk1-$pp1'
					and tiliointi.korjattu = ''
					and tiliointi.tilino in ('$yhtiorow[varasto]', '$yhtiorow[matkalla_olevat]')";
		$lresult = pupe_query($query);
		$lrow = mysql_fetch_assoc ($lresult);

		echo "<br>";
		echo t("Samalta ajanjaksolta varastonarvoon vaikuttavat käsiviennit tileiltä"). " $yhtiorow[varasto] & $yhtiorow[matkalla_olevat]: ";
		echo "<font class='message'>";
		echo round($lrow["summa"] - $automaatit, 2);
		echo " $yhtiorow[valkoodi]";
		echo "</font>";
		echo "<br><br><hr>";


		echo "<font class='message'>".t("Inventoinnit ja niiden varastonmuutos")."</font><br><br>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("kpl")."</th>";
		echo "<th>".t("logistiikka")."</th>";
		echo "<th>".t("kirjanpito")."</th>";
		echo "<th>".t("ero")."</th>";
		echo "</tr>";

		echo "<tr class='aktiivi'>";
		echo "<td>".$summa_array["Inventointi"]["kpl"]."</td>";
		echo "<td align='right'>".round($summa_array["Inventointi"]["logistiikka"],2)."</td>";
		echo "<td align='right'>".round($summa_array["Inventointi"]["kirjanpito"],2)."</td>";
		echo "<td align='right'>".round($summa_array["Inventointi"]["logistiikka"]-$summa_array["Inventointi"]["kirjanpito"],2)."</td>";
		echo "</tr>";
		echo "</table><br><br>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("tuoteno")."</th>";
		echo "<th>".t("laadittu")."</th>";
		echo "<th>".t("tapahtuma")."</th>";
		echo "<th>".t("kpito")."</th>";
		echo "<th>".t("ero")."</th>";
		echo "</tr>";

		// haetaan halutut varastotaphtumat
		$query  = "	SELECT tapahtuma.*
					FROM tapahtuma
					JOIN tuote ON (tapahtuma.yhtio = tuote.yhtio and tapahtuma.tuoteno = tuote.tuoteno and tuote.ei_saldoa = '')
					WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
					and tapahtuma.laji = 'inventointi'
					and tapahtuma.laadittu >= '$vv-$kk-$pp 00:00:00'
					and tapahtuma.laadittu <= '$vv1-$kk1-$pp1 23:59:59'
					ORDER BY tapahtuma.laadittu";
		$result = pupe_query($query);

		$eroyht = 0;

		while ($tapahtuma = mysql_fetch_assoc($result)) {

			$query  = "	SELECT lasku.tunnus, sum(tiliointi.summa) summa
						FROM tiliointi
						JOIN lasku ON tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus and lasku.tila = 'X' and lasku.viite = '$tapahtuma[tunnus]'
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						and tiliointi.tapvm = left('$tapahtuma[laadittu]', 10)
						and (tiliointi.selite like 'Inventointi%' or tiliointi.selite like 'KORJATTU: Inventointi%')
						and tiliointi.tilino in ('$yhtiorow[varasto]', '$yhtiorow[matkalla_olevat]')
						and tiliointi.korjattu = ''
						GROUP BY lasku.tunnus";
			$lresult = pupe_query($query);
			$lrow = mysql_fetch_assoc ($lresult);

			$tavarmuu = sprintf("%.2f", round($tapahtuma["kpl"]*$tapahtuma["hinta"], 2));
			$kpvarmuu = sprintf("%.2f", round($lrow["summa"], 2));

			// Kirjanpito - Tapahtuma
			$ero = sprintf("%.2f", round($tavarmuu-$kpvarmuu, 2));

			if (abs($ero) > 0.01) {
				echo "<tr>";
				echo "<td><a href='{$palvelin2}muutosite.php?tee=E&tunnus=$lrow[tunnus]'>$tapahtuma[tuoteno]</a></td>";
				echo "<td>$tapahtuma[laadittu]</td>";
				echo "<td align='right'>$tavarmuu</td>";
				echo "<td align='right'>$kpvarmuu</td>";
				echo "<td align='right'>$ero</td>";
				echo "</tr>";

				$eroyht += $ero;
			}
		}

		echo "</table><br><br>Yhteensä: $eroyht";
		echo "<br><br><hr>";

		echo "<font class='message'>".t("Myyntilaskut ja niiden varastonmuutos")."</font><br><br>";

		// haetaan myyntilaskut ja niiden varastonmuutos
		$query  = "	SELECT lasku.tunnus, sum(tiliointi.summa) varastonmuutos
					FROM lasku, tiliointi
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tapvm >= '$vv-$kk-$pp'
					and lasku.tapvm <= '$vv1-$kk1-$pp1'
					and lasku.tila = 'U'
					and lasku.alatila = 'X'
					and tiliointi.ltunnus = lasku.tunnus
					and tiliointi.tilino in ('$yhtiorow[varasto]', '$yhtiorow[matkalla_olevat]')
					and tiliointi.korjattu = ''
					GROUP BY lasku.tunnus";
		$result = pupe_query($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("laskuja")."</th>";
		echo "<th>".t("myynti")."</th>";
		echo "<th>".t("kirjanpito")."</th>";
		echo "<th>".t("ero")."</th>";
		echo "</tr>";

		$lomuutos = 0.0;
		$kpmuutos = 0.0;
		$maara = mysql_num_rows($result);

		while ($trow = mysql_fetch_assoc ($result)) {

			$query  = "	SELECT sum(tapahtuma.hinta * tapahtuma.kpl) logistiikkasumma
						FROM tilausrivi, tapahtuma
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.uusiotunnus = '$trow[tunnus]'
						and tapahtuma.yhtio = tilausrivi.yhtio
						and tapahtuma.rivitunnus = tilausrivi.tunnus
						and tapahtuma.laji = 'laskutus'";
			$lresult = pupe_query($query);
			$lrow = mysql_fetch_assoc ($lresult);

			$lomuutos += $lrow["logistiikkasumma"];
			$kpmuutos += $trow["varastonmuutos"];
		}

		$ero = $lomuutos - $kpmuutos;

		echo "<tr class='aktiivi'>";
		echo "<td>$maara</td>";
		echo "<td align='right'>".round($lomuutos,2)."</td>";
		echo "<td align='right'>".round($kpmuutos,2)."</td>";
		echo "<td align='right'>".round($ero,2)."</td>";
		echo "</tr>";
		echo "</table><br><br>";

		echo "<font class='message'>".t("Myyntitapahtumat ja niiden varastonmuutos")."</font><br><br>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("kpl")."</th>";
		echo "<th>".t("tapahtuma")."</th>";
		echo "<th>".t("kirjanpito")."</th>";
		echo "<th>".t("ero")."</th>";
		echo "</tr>";

		echo "<tr class='aktiivi'>";
		echo "<td>".$summa_array["laskutus"]["kpl"]."</td>";
		echo "<td align='right'>".round($summa_array["laskutus"]["logistiikka"],2)."</td>";
		echo "<td align='right'>".round($summa_array["laskutus"]["kirjanpito"],2)."</td>";
		echo "<td align='right'>".round($summa_array["laskutus"]["logistiikka"]-$summa_array["laskutus"]["kirjanpito"],2)."</td>";
		echo "</tr>";
		echo "</table><br><br>";

		// haetaan halutut varastotaphtumat
		$query  = "	SELECT tapahtuma.*
					FROM tapahtuma
					JOIN tuote ON (tapahtuma.yhtio = tuote.yhtio and tapahtuma.tuoteno = tuote.tuoteno and tuote.ei_saldoa = '')
					WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
					and tapahtuma.laji = 'laskutus'
					and tapahtuma.laadittu >= '$vv-$kk-$pp 00:00:00'
					and tapahtuma.laadittu <= '$vv1-$kk1-$pp1 23:59:59'
					ORDER BY tapahtuma.laadittu";
		$result = pupe_query($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("tuoteno")."</th>";
		echo "<th>".t("laadittu")."</th>";
		echo "<th>".t("tapahtuma")."</th>";
		echo "<th>".t("rivi")."</th>";
		echo "<th>".t("kpito")."</th>";
		echo "<th>".t("ero")."</th>";
		echo "</tr>";

		$eroyht = 0;

		while ($tapahtuma = mysql_fetch_assoc($result)) {

			$query  = "	SELECT uusiotunnus, rivihinta, round(rivihinta-kate, 2) varmuutos
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = $tapahtuma[rivitunnus]";
			$tres = pupe_query($query);
			$trow = mysql_fetch_assoc($tres);

			$query  = "	SELECT round(sum(tilausrivi.rivihinta-tilausrivi.kate), 2) varmuutos
						FROM tilausrivi
						JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tuote.ei_saldoa = '')
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.uusiotunnus = '$trow[uusiotunnus]'
						and tilausrivi.tyyppi = 'L'";
			$sres = pupe_query($query);
			$srow = mysql_fetch_assoc($sres);

			$query  = "	SELECT sum(summa) varmuutos
						FROM tiliointi
						WHERE yhtio = '$kukarow[yhtio]'
						and ltunnus = $trow[uusiotunnus]
						and korjattu = ''
						and tilino = '$yhtiorow[varastonmuutos]'";
			$mres = pupe_query($query);
			$mrow = mysql_fetch_assoc($mres);

			// Laskulla luulatavimmin +- samaa tuotetta jolloin varmuutos ja kate yhteensä nolla
			if ($srow["varmuutos"] == 0 and $mrow["varmuutos"] == 0) {
				$trow["varmuutos"] 	= 0;
				$kpvarmuu 			= 0;
				$tavarmuu 			= 0;
			}
			else {
				$kpvarmuu =  round(($trow["varmuutos"] / $srow["varmuutos"]) * $mrow["varmuutos"], 2);
				$tavarmuu = sprintf("%.2f", round($tapahtuma["kpl"]*$tapahtuma["hinta"]*-1, 2));
			}

			// Kirjanpito - Tilausrivi
			$ero1 = sprintf("%.2f", round($kpvarmuu-$trow["varmuutos"], 2));

			// Tapahtuma - Kirjanpito
			$ero2 = sprintf("%.2f", round($kpvarmuu-$tavarmuu, 2));

			// Tapahtuma - Tilausrivi
			$ero3 = sprintf("%.2f", round($tavarmuu-$trow["varmuutos"], 2));

			if (abs($ero1) > 0.01 or abs($ero2) > 0.01 or abs($ero3) > 0.01) {
				echo "<tr>";
				echo "<td>$tapahtuma[tuoteno]</td>";
				echo "<td>$tapahtuma[laadittu]</td>";
				echo "<td align='right'>$tavarmuu</td>";
				echo "<td align='right'>".sprintf("%.2f", round($trow["varmuutos"], 2))."</td>";
				echo "<td align='right'>".sprintf("%.2f", round($kpvarmuu, 2))."</td>";
				echo "<td align='right'>$ero2</td>";
				echo "</tr>";

				$eroyht += $ero2;
			}
		}

		echo "</table><br><br>Yhteensä: $eroyht<br>";

		echo "<br><hr>";
		echo "<font class='message'>".t("Saapumiset ja niiden varastonmuutos (listataan vain jos eroja)")."</font><br><br>";

		// haetaan kaikki ajanjakson keikat
		$query  = "	SELECT *
					FROM lasku use index (yhtio_tila_luontiaika)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.mapvm >= '$vv-$kk-$pp'
					and lasku.mapvm <= '$vv1-$kk1-$pp1'
					and lasku.tila = 'K'
					and lasku.alatila = 'X'
					and lasku.vanhatunnus = 0
					GROUP BY lasku.laskunro";
		$result = pupe_query($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("saapuminen")."</th>";
		echo "<th>".t("toimittaja")."</th>";
		echo "<th>".t("jälkilaskettu")."</th>";
		echo "<th>".t("laskut")."</th>";
		echo "<th>".t("tilausrivit")."</th>";
		echo "<th>".t("tapahtumat")."</th>";
		echo "<th>".t("kirjanpito")."</th>";
		echo "<th>".t("ero")."</th>";
		echo "</tr>";

		$lomuutos = 0.0;
		$timuutos = 0.0;
		$kpmuutos = 0.0;

		while ($trow = mysql_fetch_assoc($result)) {

			// haetaan kaikki saapumiseen liitetyt vaihto-omaisuus ja rahtilaskut
			$query = "	SELECT
						sum(if(vienti in ('B','E','H'), summa, 0)) rahtilaskusumma,
						group_concat(if(vienti in ('B','E','H'), vanhatunnus, NULL)) rahtilaskut,
						group_concat(if(vienti in ('C','J','F','K','I','L'), vanhatunnus, NULL)) ostolaskut,
						group_concat(concat(ytunnus, ' ', nimi, ' ', tapvm, ' ', summa, ' ', valkoodi, ' = ', round(summa*vienti_kurssi,2), ' EUR<br>') SEPARATOR '') laskut
						from lasku
						where yhtio = '$kukarow[yhtio]'
						and laskunro = '$trow[laskunro]'
						and tila = 'K'
						and vanhatunnus != 0";
			$keikres = pupe_query($query);
			$keekrow = mysql_fetch_assoc($keikres);

			// Nollataan nämä
			$kprow = array();
			$k2prow = array();

			$kprow["varastonmuutos"] = 0;
			$k2prow["varastonmuutosrahti"] = 0;

			// haetaan liitettyjen tavara-laskujen laskujen varastonmuutos kirjanpidosta
			if ($keekrow["ostolaskut"] != "") {
				$query = "	SELECT sum(summa) varastonmuutos
							FROM tiliointi
							WHERE yhtio = '$kukarow[yhtio]'
							and ltunnus in ($keekrow[ostolaskut])
							and tilino in ('$yhtiorow[varasto]', '$yhtiorow[matkalla_olevat]')
							and korjattu = ''";
				$kpres = pupe_query($query);
				$kprow = mysql_fetch_assoc($kpres);

				$kpmuutos += $kprow["varastonmuutos"];
			}

			// haetaan liitettyjen rahti-laskujen varastonmuutos kirjanpidosta
			// suuntaa-antava, koska rahtilaskusta on voitu liittää vain osa tähän saapumiseen
			if ($keekrow["rahtilaskut"] != "") {
				$query = "	SELECT sum(summa) rahtilaskusummakokonaan
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus in ($keekrow[rahtilaskut])";
				$k2pres = pupe_query($query);
				$k2prow = mysql_fetch_assoc($k2pres);

				// Haetaan kululaskun kaikki verotiliöinnit jotta voidaan tallentaa myös veroton summa
				$query = "	SELECT sum(summa) summa
							from tiliointi
							where yhtio	= '$kukarow[yhtio]'
							and ltunnus in ($keekrow[rahtilaskut])
							and tilino  = '$yhtiorow[alv]'
							and korjattu = ''";
				$alvires = pupe_query($query);
				$alvirow = mysql_fetch_assoc($alvires);

				$rahtipros = (float) $keekrow["rahtilaskusumma"] / ($k2prow["rahtilaskusummakokonaan"]-$alvirow["summa"]);

				$query = "	SELECT round(sum(summa) * $rahtipros, 2) varastonmuutosrahti
							FROM tiliointi
							WHERE yhtio = '$kukarow[yhtio]'
							and ltunnus in ($keekrow[rahtilaskut])
							and tilino in ('$yhtiorow[varasto]', '$yhtiorow[matkalla_olevat]')
							and korjattu = ''";
				$k2pres = pupe_query($query);
				$k2prow = mysql_fetch_assoc($k2pres);

				$kpmuutos += $k2prow["varastonmuutosrahti"];
			}

			// Haetaan keikan arvo tapahtumilta
			$query  = "	SELECT sum(tapahtuma.kplhinta * tapahtuma.kpl) logistiikkasumma, sum(tilausrivi.rivihinta) tilausrivisumma
						FROM tilausrivi
						JOIN tapahtuma ON tapahtuma.yhtio = tilausrivi.yhtio and tapahtuma.rivitunnus = tilausrivi.tunnus and tapahtuma.laji = 'tulo'
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.uusiotunnus = '$trow[tunnus]'";
			$lores = pupe_query($query);
			$lorow = mysql_fetch_assoc($lores);

			$lomuutos += $lorow["logistiikkasumma"];
			$timuutos += $lorow["tilausrivisumma"];

			$ero = round($lorow["logistiikkasumma"] - ($kprow["varastonmuutos"]+$k2prow["varastonmuutosrahti"]), 2);

			if (round($ero, 0) != 0) {
				echo "<tr class='aktiivi'>";
				echo "<td>$trow[laskunro]</td>";
				echo "<td>$trow[nimi]</td>";
				echo "<td>$trow[mapvm]</td>";
				echo "<td>$keekrow[laskut]</td>";
				echo "<td align='right'>".round($lorow["tilausrivisumma"], 2)."</td>";
				echo "<td align='right'>".round($lorow["logistiikkasumma"], 2)."</td>";
				echo "<td align='right'>".round($kprow["varastonmuutos"]+$k2prow["varastonmuutosrahti"], 2)."</td>";
				echo "<td align='right'>".round($ero, 2)."</td>";
				echo "</tr>";
			}
		}

		$ero = $lomuutos - $kpmuutos;

		echo "<tr>";
		echo "<th colspan='4'>".t('Saapumiset yhteensä')."</th>";
		echo "<th align='right' NOWRAP>".round($timuutos,2)."</th>";
		echo "<th align='right' NOWRAP>".round($lomuutos,2)."</th>";
		echo "<th align='right' NOWRAP>".round($kpmuutos,2)."</th>";
		echo "<th align='right' NOWRAP>".round($ero,2)."</th>";
		echo "</tr>";

		echo "</table>";

		echo "<br><hr>";
		echo "<font class='message'>".t("Väärin laskutetut myyntitilaukset (suuntaa-antava arvio)")."</font><br><br>";

		echo "<table>";

		echo "<tr>";
		echo "<th>Tuote</th>";
		echo "<th>Laji</th>";
		echo "<th>Laadittu</th>";
		echo "<th>Varmuutos log</th>";
		echo "<th>Varmuutos kp</th>";
		echo "<th>Erotus</th>";
		echo "</tr>";

		$heitto_yhteensa = 0;

		// haetaan kaikki tuotteet (+ saldot)
		$query  = "	SELECT tuote.tuoteno, sum(tuotepaikat.saldo) saldo
					FROM tapahtuma
					JOIN tuote ON (tapahtuma.yhtio = tuote.yhtio AND tapahtuma.tuoteno = tuote.tuoteno AND tuote.ei_saldoa = '')
					JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio AND tuotepaikat.tuoteno = tuote.tuoteno)
					WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
					AND tapahtuma.laadittu >= '$vv-$kk-$pp 00:00:00'
					AND tapahtuma.laadittu <= '$vv1-$kk1-$pp1 23:59:59'
					GROUP BY tuote.tuoteno
					ORDER BY tapahtuma.tuoteno";
		$result = pupe_query($query);

		while ($tuoterow = mysql_fetch_assoc($result)) {

			$query = "	SELECT *, if(tapahtuma.laji in ('tulo', 'valmistus'), tapahtuma.kplhinta, tapahtuma.hinta) * tapahtuma.kpl arvo
						FROM tapahtuma use index (yhtio_tuote_laadittu)
						WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
						AND tapahtuma.tuoteno = '$tuoterow[tuoteno]'
						AND tapahtuma.laji != 'Epäkurantti'
						AND tapahtuma.kpl != 0
						AND laadittu >= '$vv-$kk-$pp 00:00:00'
						AND laadittu <= '$vv1-$kk1-$pp1 23:59:59'
						ORDER BY tapahtuma.laadittu DESC";
			$result2 = pupe_query($query);

			$saldo_nyt = $tuoterow["saldo"];
			$edellisen_tulon_kehahin = 0;

			while ($tapahtumarow = mysql_fetch_assoc($result2)) {

				// pidetään tallessa aina viimeisin tulon kehahinta
				if ($tapahtumarow["laji"] == "tulo") {
					$edellisen_tulon_kehahin = $tapahtumarow["kplhinta"];
				}

				if ($saldo_nyt < 0 and $edellisen_tulon_kehahin != 0 and abs($tapahtumarow["kpl"] * $edellisen_tulon_kehahin - $tapahtumarow["arvo"]) > 0.01) {

					$erotus = round($tapahtumarow["kpl"] * $edellisen_tulon_kehahin - $tapahtumarow["arvo"], 2) * -1;
					$heitto_yhteensa += $erotus;

					echo "<tr class='aktiivi'>";
					echo "<td><a href='".$palvelin2."tuote.php?tuoteno=$tapahtumarow[tuoteno]&tee=Z&lopetus=".$palvelin2."raportit/varastonarvo-tutki.php////tee=tee//pp=$pp//kk=$kk//vv=$vv//pp1=$pp1//kk1=$kk1//vv1=$vv1'>$tapahtumarow[tuoteno]</a></td>";
					echo "<td>$tapahtumarow[laji]</td>";
					echo "<td>".tv1dateconv($tapahtumarow["laadittu"])."</td>";
					echo "<td align='right'>".sprintf("%.02f", $tapahtumarow["arvo"] * -1)."</td>";
					echo "<td align='right'>".sprintf("%.02f", ($tapahtumarow["kpl"] * $edellisen_tulon_kehahin * -1))."</td>";
					echo "<td align='right'>".sprintf("%.02f", $erotus)."</td>";
					echo "</tr>";

				}

				$saldo_nyt -= $tapahtumarow["kpl"];

			}
		}

		echo "<tr>";
		echo "<th colspan='5'>Yhteensä</th>";
		echo "<th>".sprintf("%.02f", $heitto_yhteensa)."</th>";
		echo "</tr>";

		echo "</table>";
	}

	require ("inc/footer.inc");

?>