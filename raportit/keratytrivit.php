<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Kerätyt rivit").":</font><hr>";

	if ($tee != '') {

		if ($eipoistettuja != "") {
			$lefti = "";
		}
		else {
			$lefti = "LEFT";
		}

		if (!empty($varastot)) {
			$lisa = " and varastopaikat.tunnus IN (" . implode(', ', $varastot) . ")";
        }
		else {
			$lisa = "";
		}

		if ($tapa == 'keraaja') {

			$query = "	SELECT tilausrivi.keratty,
						tilausrivi.otunnus,
						tilausrivi.kerattyaika,
						lasku.lahetepvm,
						kuka.nimi,
						kuka.keraajanro,
						sec_to_time(unix_timestamp(kerattyaika)-unix_timestamp(lahetepvm)) aika,
						sum(if(tilausrivi.var  = 'P', 1, 0)) puutteet,
						sum(if(tilausrivi.var != 'P' and tilausrivi.tyyppi='L', 1, 0)) kappaleet,
						sum(if(tilausrivi.var != 'P' and tilausrivi.tyyppi='G', 1, 0)) siirrot,
						round(sum(if(tilausrivi.var != 'P', tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl, 0)), 2) kerkappaleet,
						round(sum(if(tilausrivi.var != 'P', (tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl)*tuote.tuotemassa, 0)), 2) kerkilot,
						count(*) yht
						FROM tilausrivi USE INDEX (yhtio_tyyppi_kerattyaika)
						JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
						$lefti JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = tilausrivi.yhtio and kuka.kuka = tilausrivi.keratty)
						LEFT JOIN varastopaikat ON (varastopaikat.yhtio=tilausrivi.yhtio and
		                concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0')) and
		                concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0')))
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.kerattyaika >= '$vva-$kka-$ppa 00:00:00'
						and tilausrivi.kerattyaika <= '$vvl-$kkl-$ppl 23:59:59'
						and tilausrivi.var in ('','H','P')
						and tilausrivi.tyyppi in ('L','G')
						$lisa
						GROUP BY tilausrivi.keratty, tilausrivi.otunnus
						ORDER BY tilausrivi.keratty, tilausrivi.kerattyaika";
			$result = mysql_query($query) or pupe_error($query);

			echo "	<table><tr>
					<th nowrap>".t("Nimi")."</th>
					<th nowrap>".t("Kerääjänro")."</th>
					<th nowrap>".t("Tilaus")."</th>
					<th nowrap>".t("Lähete tulostettu")."</th>
					<th nowrap>".t("Tilaus kerätty")."</th>
					<th nowrap>".t("Käytetty aika")."</th>
					<th norwap>".t("Puuterivit")."</th>
					<th norwap>".t("Siirrot")."</th>
					<th nowrap>".t("Kerätyt")."</th>
					<th nowrap>".t("Yhteensä")."</th>
					<th nowrap>".t("Kappaleet")."<br>".t("Yhteensä")."</th>
					<th nowrap>".t("Kilot")."<br>".t("Yhteensä")."</th>
					</tr>";

			$lask		= 0;
			$edkeraaja	= 'EKADUUD';
			$psummayht	= 0;
			$ksummayht	= 0;
			$ssummayht	= 0;
			$summayht	= 0;

			while ($row = mysql_fetch_array($result)) {

				if ($edkeraaja != $row["keratty"] and $summa > 0 and $edkeraaja != "EKADUUD") {
					echo "<tr>
							<th colspan='6'>".t("Yhteensä").":</th>
							<td class='tumma' align='right'>$psumma</td>
							<td class='tumma' align='right'>$ssumma</td>
							<td class='tumma' align='right'>$ksumma</td>
							<td class='tumma' align='right'>$summa</td>
							<td class='tumma' align='right'>$kapsu</td>
							<td class='tumma' align='right'>$kilsu</td>
							</tr>";
					echo "<tr><td class='back'><br></td></tr>";

					echo "	<tr>
							<th nowrap>".t("Nimi")."</th>
							<th nowrap>".t("Kerääjänro")."</th>
							<th nowrap>".t("Tilaus")."</th>
							<th nowrap>".t("Lähete tulostettu")."</th>
							<th nowrap>".t("Tilaus kerätty")."</th>
							<th nowrap>".t("Käytetty aika")."</th>
							<th norwap>".t("Puuterivit")."</th>
							<th norwap>".t("Siirrot")."</th>
							<th nowrap>".t("Kerätyt")."</th>
							<th nowrap>".t("Yhteensä")."</th>
							<th nowrap>".t("Yhteensä")."<br>".t("kappaleet")."</th>
							<th nowrap>".t("Yhteensä")."<br>".t("kilot")."</th>
							</tr>";

					$psumma	= 0;
					$ksumma	= 0;
					$ssumma	= 0;
					$summa	= 0;
					$kapsu	= 0;
					$kilsu	= 0;
				}

				echo "<tr>
						<td>$row[nimi] ($row[keratty])</td>
						<td>$row[keraajanro]</td>
						<td>$row[otunnus]</td>
						<td>".tv1dateconv($row["lahetepvm"],"P")."</td>
						<td>".tv1dateconv($row["kerattyaika"],"P")."</td>
						<td>$row[aika]</td>
						<td align='right'>$row[puutteet]</td>
						<td align='right'>$row[siirrot]</td>
						<td align='right'>$row[kappaleet]</td>
						<td align='right'>$row[yht]</td>
						<td align='right'>$row[kerkappaleet]</td>
						<td align='right'>$row[kerkilot]</td>
					</tr>";

				$psumma	+= $row["puutteet"];
				$ksumma	+= $row["kappaleet"];
				$ssumma	+= $row["siirrot"];
				$summa	+= $row["yht"];
				$kapsu	+= $row["kerkappaleet"];
				$kilsu	+= $row["kerkilot"];

				// yhteensä
				$psummayht	+= $row["puutteet"];
				$ksummayht	+= $row["kappaleet"];
				$ssummayht	+= $row["siirrot"];
				$summayht	+= $row["yht"];
				$kapsuyht	+= $row["kerkappaleet"];
				$kilsuyht	+= $row["kerkilot"];

				$lask++;
				$edkeraaja = $row["keratty"];
			}

			if ($summa > 0) {
				echo "<tr>
						<th colspan='6'>".t("Yhteensä").":</th>
						<td class='tumma' align='right'>$psumma</td>
						<td class='tumma' align='right'>$ssumma</td>
						<td class='tumma' align='right'>$ksumma</td>
						<td class='tumma' align='right'>$summa</td>
						<td class='tumma' align='right'>$kapsu</td>
						<td class='tumma' align='right'>$kilsu</td>
					</tr>";
				echo "<tr><td class='back'><br></td></tr>";
			}

			// Kaikki yhteensä
			echo "<tr>
					<th colspan='6'>".t("Kaikki yhteensä").":</th>
					<td class='tumma' align='right'>$psummayht</td>
					<td class='tumma' align='right'>$ssummayht</td>
					<td class='tumma' align='right'>$ksummayht</td>
					<td class='tumma' align='right'>$summayht</td>
					<td class='tumma' align='right'>$kapsuyht</td>
					<td class='tumma' align='right'>$kilsuyht</td>
					</tr>";

			echo "</table><br>";
		}

		if (($tapa == 'kerpvm') or ($tapa == 'kerkk')) {

			if (!isset($vva)) {
				$vvaa = date("Y");
			}
			else {
				$vvaa = $vva;
			}

			$grp = 'pvm';
                        if ($tapa == 'kerkk') $grp = 'left(kerattyaika, 7)';

			$query = "	SELECT date_format(left(tilausrivi.kerattyaika,10), '%j') pvm,
						left(tilausrivi.kerattyaika,10) kerattyaika,
						sum(if(tilausrivi.var  = 'P', 1, 0)) puutteet,
						sum(if(tilausrivi.var != 'P' and tilausrivi.tyyppi='L', 1, 0)) kappaleet,
						sum(if(tilausrivi.var != 'P' and tilausrivi.tyyppi='G', 1, 0)) siirrot,
						count(*) yht
						FROM tilausrivi USE INDEX (yhtio_tyyppi_kerattyaika)
						$lefti JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = tilausrivi.yhtio and kuka.kuka = tilausrivi.keratty)
						LEFT JOIN varastopaikat ON (varastopaikat.yhtio=tilausrivi.yhtio and
		                concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0')) and
		                concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0')))
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.kerattyaika >= '$vvaa-01-01 00:00:00'
						and tilausrivi.kerattyaika <= '$vvaa-12-31 23:59:59'
						and tilausrivi.var in ('','H','P')
						and tilausrivi.tyyppi in ('L','G')
						$lisa
						GROUP BY $grp
						ORDER BY 1";
			$result = mysql_query($query) or pupe_error($query);

			echo "<table>";

			echo "<tr>
					<th>".t("Pvm")."</th>
					<th>".t("Puutteet")."</th>
					<th>".t("Siirrot")."</th>
					<th>".t("Kerätyt")."</th>
					<th>".t("Yhteensä")."</th>
				</tr>";

			$psummayht	= 0;
			$ksummayht	= 0;
			$ssummayht	= 0;
			$summayht	= 0;

			if ($tapa == 'kerkk') {
				while ($ressu = mysql_fetch_array($result)) {
					echo "<tr>";
					echo "<td align='right'>".substr($ressu[kerattyaika],0,7)."</td>";
					echo "<td align='right'>$ressu[puutteet]</td>";
					echo "<td align='right'>$ressu[siirrot]</td>";
					echo "<td align='right'>$ressu[kappaleet]</td>";
					echo "<td align='right'>$ressu[yht]</td>";
					echo "</tr>";
					// yhteensä
					$psummayht	+= $ressu["puutteet"];
					$ksummayht	+= $ressu["kappaleet"];
					$ssummayht	+= $ressu["siirrot"];
					$summayht	+= $ressu["yht"];
				}
			}
			else {
				$puutteet	= array();
				$kerattyaik= array();
				$kappalee	= array();
				$siirrot	= array();
				$yht	= array();

				while ($ressu = mysql_fetch_array($result)) {

					$apu		= (int) $ressu['pvm'];
					$puutteet[$apu]	= $ressu['puutteet'];
					$kerattyaika[$apu]	= $ressu['kerattyaika'];
					$kappaleet[$apu]	= $ressu['kappaleet'];
					$siirrot[$apu]	= $ressu['siirrot'];
					$yht[$apu]		= $ressu['yht'];

					// yhteensä
					$psummayht	+= $ressu["puutteet"];
					$ksummayht	+= $ressu["kappaleet"];
					$ssummayht	+= $ressu["siirrot"];
					$summayht	+= $ressu["yht"];

				}

				for ($i=1; $i<367; $i++) {

					echo "<tr>";

					if (strlen($kerattyaika[$i]) == 0) {
						echo "<td>$i</td>";
					}
					else {
						echo "<td>".tv1dateconv($kerattyaika[$i],"P")."</td>";
					}

					echo "<td align='right'>$puutteet[$i]</td>";
					echo "<td align='right'>$siirrot[$i]</td>";
					echo "<td align='right'>$kappaleet[$i]</td>";
					echo "<td align='right'>$yht[$i]</td>";
					echo "</tr>";

				}
			}
		}
		echo "<tr>";
		echo "<th>".t("Yhteensä")."</th>";
		echo "<td class='tumma' align='right'>$psummayht</td>";
		echo "<td class='tumma' align='right'>$ssummayht</td>";
		echo "<td class='tumma' align='right'>$ksummayht</td>";
		echo "<td class='tumma' align='right'>$summayht</td>";
		echo "</tr>";
		echo "</table><br>";
	}

	//Käyttöliittymä
	echo "<form method='post'>";
	echo "<table>";

	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<tr>
			<th>".t("Valitse tapa")."</th>
			<td colspan='3'>
				<select name='tapa'>
					<option value='keraaja'>".t("Kerääjittäin")."</option>
					<option value='kerpvm'>".t("Päivittäin")."</option>
					<option value='kerkk'>".t("Kuukausittain")."</option>
				</select>
			</td>
		</tr>";


	$query  = "SELECT tunnus, nimitys FROM varastopaikat WHERE yhtio='$kukarow[yhtio]' AND varasto_status != 'P'";
	$vares = mysql_query($query) or pupe_error($query);

	echo "<tr><th valign=top>" . t('Varastot') . "<br /><br /><span style='font-size: 0.8em;'>"
		. t('Saat kaikki varastot jos et valitse yhtään')
		. "</span></th>
	    <td colspan='3'>";

	$varastot = (isset($_POST['varastot']) && is_array($_POST['varastot'])) ? $_POST['varastot'] : array();

    while ($varow = mysql_fetch_array($vares)) {
		$sel = '';
		if (in_array($varow['tunnus'], $varastot)) {
			$sel = 'checked';
		}

		echo "<input type='checkbox' name='varastot[]' value='{$varow['tunnus']}' $sel/>{$varow['nimitys']}<br />\n";
	}

	echo "</td></tr>";


	echo "<tr>
			<th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td><td class='back'><-- ".t("Jos valitset *Päivittäin tai Kuukausittain*, niin ajetaan tämän vuoden tiedot")."</td>
		</tr>
		<tr>
			<th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>
		</tr>";

	echo "<tr>
			<th>".t("Älä näytä poistettujen käyttäjien rivejä")."</th>
			<td colspan='3'><input type='checkbox' name='eipoistettuja'></td>
		</tr>";

	echo "</table>";

	echo "<br>";
	echo "<input type='submit' value='".t("Aja raportti")."'>";
	echo "</form>";

	require ("../inc/footer.inc");

?>
