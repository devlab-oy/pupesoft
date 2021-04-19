<?php

	// k�ytet��n slavea jos sellanen on
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

	// h�rski oikeellisuustzekki
	if ($pp == "00" or $kk == "00" or $vv == "0000") $tee = $pp = $kk = $vv = "";
	if ($pp1 == "00" or $kk1 == "00" or $vv1 == "0000") $tee = $pp1 = $kk1 = $vv1 = "";

	// piirrell��n formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Sy�t� alku pp-kk-vvvv").":</th>";
	echo "<td><input type='text' name='pp' size='5' value='$pp'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='vv' size='7' value='$vv'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>".t("Sy�t� loppu pp-kk-vvvv").":</th>";
	echo "<td><input type='text' name='pp1' size='5' value='$pp1'><input type='text' name='kk1' size='5' value='$kk1'><input type='text' name='vv1' size='7' value='$vv1'></td>";
	echo "</tr>";
	echo "</table>";

	echo "<br>";
	echo "<input type='hidden' name='tee' value='tee'>";
	echo "<input type='submit' value='".t("Tarkastele")."'>";
	echo "</form>";
	echo "<br><br>";

	if ($tee == "tee") {

		echo "<font class='message'>Logistiikan tapahtumat ja niiden varastonmuutos</font><br><br>";

		// haetaan halutut varastotaphtumat
		$query  = "	SELECT laji, count(*) kpl, round(sum(if(laji='tulo', kplhinta, hinta) * kpl), 2) logistiikka
					FROM tapahtuma
					JOIN tuote ON (tapahtuma.yhtio = tuote.yhtio
						and tapahtuma.tuoteno = tuote.tuoteno
						and tuote.ei_saldoa = '')
					WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
					and laadittu >= '$vv-$kk-$pp 00:00:00'
					and laadittu <= '$vv1-$kk1-$pp1 23:59:59'
					GROUP BY laji";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("laji")."</th>";
		echo "<th>".t("kpl")."</th>";
		echo "<th>".t("tapahtuma")."</th>";
		echo "<th>".t("kirjanpito")."</th>";
		echo "<th>".t("ero")."</th>";
		echo "</tr>";

		$automaatit = 0;

		while ($trow = mysql_fetch_array ($result)) {

			echo "<tr class='aktiivi'>";
			echo "<td>$trow[laji]</td>";
			echo "<td align='right'>$trow[kpl]</td>";
			echo "<td align='right'>$trow[logistiikka]</td>";

			//Etsit��n vastaavat kirjapidon viennit
			$lvalinta = '';

			if ($trow['laji'] == 'laskutus') 	$lvalinta = "tila = 'U' and alatila = 'X' and selite not like 'Varastoontulo%'";
			if ($trow['laji'] == 'Inventointi') $lvalinta = "tila = 'X' and selite like 'Inventointi%'";
			if ($trow['laji'] == 'Ep�kurantti') $lvalinta = "tila = 'X' and selite like '%ep�kura%'";
			if ($trow['laji'] == 'tulo') 		$lvalinta = " ((tila in ('H', 'M', 'P', 'Q', 'Y') and vienti in ('B', 'C', 'E', 'F', 'H', 'I')) or (tila = 'U' and alatila = 'X' and selite like 'Varastoontulo%'))";

			if ($lvalinta != '') {
				$query  = "	SELECT sum(tiliointi.summa) summa
							FROM tiliointi use index (yhtio_tilino_tapvm), lasku
							WHERE tiliointi.yhtio = '$kukarow[yhtio]'
							and tiliointi.tapvm >= '$vv-$kk-$pp'
							and tiliointi.tapvm <= '$vv1-$kk1-$pp1'
							and tiliointi.yhtio = lasku. yhtio
							and tiliointi.ltunnus = lasku.tunnus
							and tiliointi.tilino in ('$yhtiorow[varasto]', '$yhtiorow[matkalla_olevat]')
							and tiliointi.korjattu = ''
							and $lvalinta";
				$lresult = mysql_query($query) or pupe_error($query);
				$lrow = mysql_fetch_array ($lresult);

				echo "<td align='right'>$lrow[summa]</td>";
				echo "<td align='right'>".round($trow["logistiikka"] - $lrow["summa"], 2)."</td>";

				$automaatit += $lrow["summa"];
			}
			else {
				echo "<td align='right'>0.00</td align='right'><td align='right'>0.00</td>";
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
		$lresult = mysql_query($query) or pupe_error($query);
		$lrow = mysql_fetch_array ($lresult);

		echo "<br>";
		echo t("Samalta ajanjaksolta varastonarvoon vaikuttavat k�siviennit tileilt�"). " $yhtiorow[varasto] & $yhtiorow[matkalla_olevat]: ";
		echo "<font class='message'>";
		echo round($lrow["summa"] - $automaatit, 2);
		echo " $yhtiorow[valkoodi]";
		echo "</font>";
		echo "<br><br><hr>";

		echo "<font class='message'>Myyntilaskut ja niiden varastonmuutos</font><br><br>";

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
		$result = mysql_query($query) or pupe_error($query);

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

		while ($trow = mysql_fetch_array ($result)) {

			$query  = "	SELECT sum(tapahtuma.hinta * tapahtuma.kpl) logistiikkasumma
						FROM tilausrivi, tapahtuma
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.uusiotunnus = '$trow[tunnus]'
						and tapahtuma.yhtio = tilausrivi.yhtio
						and tapahtuma.rivitunnus = tilausrivi.tunnus
						and tapahtuma.laji = 'laskutus'";
			$lresult = mysql_query($query) or pupe_error($query);
			$lrow = mysql_fetch_array ($lresult);

			$lomuutos += $lrow["logistiikkasumma"];
			$kpmuutos += $trow["varastonmuutos"];
		}

		$ero = $lomuutos - $kpmuutos;

		echo "<tr class='aktiivi'>";
		echo "<td>$maara</td>";
		echo "<td>".round($lomuutos,2)."</td>";
		echo "<td>".round($kpmuutos,2)."</td>";
		echo "<td>".round($ero,2)."</td>";
		echo "</tr>";
		echo "</table>";

		echo "<br><hr>";
		echo "<font class='message'>Keikat ja niiden varastonmuutos (listataan vain jos eroja)</font><br><br>";

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
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("keikka")."</th>";
		echo "<th>".t("toimittaja")."</th>";
		echo "<th>".t("j�lkilaskettu")."</th>";
		echo "<th>".t("laskut")."</th>";
		echo "<th>".t("tilausrivit")."</th>";
		echo "<th>".t("tapahtumat")."</th>";
		echo "<th>".t("kirjanpito")."</th>";
		echo "<th>".t("ero")."</th>";
		echo "</tr>";

		$lomuutos = 0.0;
		$timuutos = 0.0;
		$kpmuutos = 0.0;

		while ($trow = mysql_fetch_array ($result)) {

			// haetaan kaikki keikkaan liitetyt vaihto-omaisuus ja rahtilaskut
			$query = "	SELECT ifnull(group_concat(vanhatunnus), 0) ostolaskut, group_concat(concat(lasku.ytunnus, ' ', lasku.nimi, ' ', lasku.tapvm, ' ', lasku.summa, ' ', lasku.valkoodi, ' = ', round(lasku.summa*lasku.vienti_kurssi,2), ' EUR<br>') SEPARATOR '') laskut
						from lasku
						where yhtio = '$kukarow[yhtio]'
						and lasku.laskunro = '$trow[laskunro]'
						and lasku.tila = 'K'
						and lasku.vanhatunnus != 0";
			$keikres = mysql_query($query) or pupe_error($query);
			$keekrow = mysql_fetch_array($keikres);

			// haetaan liitettyjen laskujen laskujen varastonmuutos kirjanpidosta
			$query = "	SELECT sum(tiliointi.summa) varastonmuutos
						FROM tiliointi
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						and tiliointi.ltunnus in ($keekrow[ostolaskut])
						and tiliointi.tilino in ('$yhtiorow[varasto]', '$yhtiorow[matkalla_olevat]')
						and tiliointi.korjattu = ''";
			$kpres = mysql_query($query) or pupe_error($query);
			$kprow = mysql_fetch_array($kpres);
			$kpmuutos += $kprow["varastonmuutos"];

			// haetaan keikan arvo tapahtumilta
			$query  = "	SELECT sum(tapahtuma.kplhinta * tapahtuma.kpl) logistiikkasumma, sum(tilausrivi.rivihinta) tilausrivisumma
						FROM tilausrivi, tapahtuma
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.uusiotunnus = '$trow[tunnus]'
						and tapahtuma.yhtio = tilausrivi.yhtio
						and tapahtuma.rivitunnus = tilausrivi.tunnus
						and tapahtuma.laji = 'tulo'";
			$lores = mysql_query($query) or pupe_error($query);
			$lorow = mysql_fetch_array ($lores);
			$lomuutos += $lorow["logistiikkasumma"];
			$timuutos += $lorow["tilausrivisumma"];

			$ero = $lorow["logistiikkasumma"] - $kprow["varastonmuutos"];

			if (round($ero, 0) != 0) {
				echo "<tr class='aktiivi'>";
				echo "<td>$trow[laskunro]</td>";
				echo "<td>$trow[nimi]</td>";
				echo "<td>$trow[mapvm]</td>";
				echo "<td>$keekrow[laskut]</td>";
				echo "<td align='right'>".round($lorow["tilausrivisumma"],2)."</td>";
				echo "<td align='right'>".round($lorow["logistiikkasumma"],2)."</td>";
				echo "<td align='right'>".round($kprow["varastonmuutos"],2)."</td>";
				echo "<td align='right'>".round($ero, 2)."</td>";
				echo "</tr>";
			}
		}

		$ero = $lomuutos - $kpmuutos;

		echo "<tr>";
		echo "<th colspan='4'>".t('Keikat yhteens�')."</th>";
		echo "<th align='right' NOWRAP>".round($timuutos,2)."</th>";
		echo "<th align='right' NOWRAP>".round($lomuutos,2)."</th>";
		echo "<th align='right' NOWRAP>".round($kpmuutos,2)."</th>";
		echo "<th align='right' NOWRAP>".round($ero,2)."</th>";
		echo "</tr>";

		echo "</table>";

		echo "<br><hr>";
		echo "<font class='message'>V��rin laskutetut myyntitilaukset (suuntaa-antava arvio)</font><br><br>";

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
					JOIN tuote ON (tapahtuma.yhtio = tuote.yhtio
						AND tapahtuma.tuoteno = tuote.tuoteno
						AND tuote.ei_saldoa = '')
					JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio
						AND tuotepaikat.tuoteno = tuote.tuoteno)
					WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
					AND laadittu >= '$vv-$kk-$pp 00:00:00'
					AND laadittu <= '$vv1-$kk1-$pp1 23:59:59'
					GROUP BY tuote.tuoteno
					ORDER BY tapahtuma.tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		while ($tuoterow = mysql_fetch_array($result)) {

			$query = "	SELECT *, if(tapahtuma.laji in ('tulo', 'valmistus'), tapahtuma.kplhinta, tapahtuma.hinta) * tapahtuma.kpl arvo
						FROM tapahtuma use index (yhtio_tuote_laadittu)
						WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
						AND tapahtuma.tuoteno = '$tuoterow[tuoteno]'
						AND tapahtuma.laji != 'Ep�kurantti'
						AND tapahtuma.kpl != 0
						AND laadittu >= '$vv-$kk-$pp 00:00:00'
						AND laadittu <= '$vv1-$kk1-$pp1 23:59:59'
						ORDER BY tapahtuma.laadittu DESC";
			$result2 = mysql_query($query) or pupe_error($query);

			$saldo_nyt = $tuoterow["saldo"];
			$edellisen_tulon_kehahin = 0;

			while ($tapahtumarow = mysql_fetch_array($result2)) {

				// pidet��n tallessa aina viimeisin tulon kehahinta
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
		echo "<th colspan='5'>Yhteens�</th>";
		echo "<th>".sprintf("%.02f", $heitto_yhteensa)."</th>";
		echo "</tr>";

		echo "</table>";
	}

	require ("inc/footer.inc");

?>
