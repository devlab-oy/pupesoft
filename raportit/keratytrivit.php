<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>",t("Kerätyt rivit"),"</font><hr>";

	if (!isset($tee)) $tee = '';

	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	if (!isset($tapa)) $tapa = "";
	if (!isset($eipoistettuja)) $eipoistettuja = "";
	if (!isset($varastot)) $varastot = array();
	if (!isset($keraysvyohykkeet)) $keraysvyohykkeet = array();

	//Käyttöliittymä
	echo "<form method='post'>";
	echo "<table>";

	echo "<input type='hidden' name='tee' value='kaikki'>";

	$sel = array($tapa => " selected") + array("keraaja" => "", "kerpvm" => "", "kerkk" => "");

	echo "<tr>";
	echo "<th>",t("Valitse tapa"),"</th>";
	echo "<td colspan='3'>";
	echo "<select name='tapa'>";
	echo "<option value='keraaja'{$sel['keraaja']}>",t("Kerääjittäin"),"</option>";
	echo "<option value='kerpvm'{$sel['kerpvm']}>",t("Päivittäin"),"</option>";
	echo "<option value='kerkk'{$sel['kerkk']}>",t("Kuukausittain"),"</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	$query  = "	SELECT tunnus, nimitys
				FROM keraysvyohyke
				WHERE yhtio = '{$kukarow['yhtio']}'
				ORDER BY nimitys";
	$vares = pupe_query($query);

	echo "<tr>";
	echo "<th valign=top>",t('Keräysvyöhykkeet'),"<br /><br /><span style='font-size: 0.8em;'>",t('Saat kaikki keräysvyöhykkeet jos et valitse yhtään'),"</span></th>";
	echo "<td colspan='3'>";

    while ($varow = mysql_fetch_assoc($vares)) {
		$sel = in_array($varow['tunnus'], $keraysvyohykkeet) ? 'checked' : '';

		echo "<input type='checkbox' name='keraysvyohykkeet[]' value='{$varow['tunnus']}' {$sel}/>{$varow['nimitys']}<br />\n";
	}

	echo "</td></tr>";

	$query  = "	SELECT tunnus, nimitys
				FROM varastopaikat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tyyppi != 'P'
				ORDER BY tyyppi, nimitys";
	$vares = pupe_query($query);

	echo "<tr>";
	echo "<th valign=top>",t('Varastot'),"<br /><br /><span style='font-size: 0.8em;'>",t('Saat kaikki varastot jos et valitse yhtään'),"</span></th>";
	echo "<td colspan='3'>";

    while ($varow = mysql_fetch_assoc($vares)) {
		$sel = in_array($varow['tunnus'], $varastot) ? 'checked' : '';

		echo "<input type='checkbox' name='varastot[]' value='{$varow['tunnus']}' {$sel}/>{$varow['nimitys']}<br />\n";
	}

	echo "</td></tr>";


	echo "<tr>";
	echo "<th>",t("Syötä päivämäärä (pp-kk-vvvv)"),"</th>";
	echo "<td><input type='text' name='ppa' value='{$ppa}' size='3'></td>";
	echo "<td><input type='text' name='kka' value='{$kka}' size='3'></td>";
	echo "<td><input type='text' name='vva' value='{$vva}' size='5'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>",t("Syötä loppupäivämäärä (pp-kk-vvvv)"),"</th>";
	echo "<td><input type='text' name='ppl' value='{$ppl}' size='3'></td>";
	echo "<td><input type='text' name='kkl' value='{$kkl}' size='3'></td>";
	echo "<td><input type='text' name='vvl' value='{$vvl}' size='5'></td>";
	echo "</tr>";

	$chk = $eipoistettuja != '' ? " checked" : "";

	echo "<tr>";
	echo "<th>",t("Älä näytä poistettujen käyttäjien rivejä"),"</th>";
	echo "<td colspan='3'><input type='checkbox' name='eipoistettuja'{$chk}></td>";
	echo "</tr>";

	echo "<tr><td colspan='4' class='back'></td></tr>";
	echo "<tr><td colspan='4' class='back'><input type='submit' value='",t("Aja raportti"),"'></td></tr>";
	echo "</table>";
	echo "</form>";

	echo "<br /><br />";

	if ($tee != '') {

		if ($eipoistettuja != "") {
			$lefti = "";
		}
		else {
			$lefti = "LEFT";
		}

		$lisa = "";

		if (count($varastot) > 0) {
			$lisa = " and varastopaikat.tunnus IN (".implode(', ', $varastot).")";
        }

        $keraysvyohykejoin = "";

        if (count($keraysvyohykkeet) > 0) {
        	$keraysvyohykejoin = "	JOIN varaston_hyllypaikat AS vh1 ON (vh1.yhtio = tilausrivi.yhtio AND vh1.hyllyalue = tilausrivi.hyllyalue AND vh1.hyllynro = tilausrivi.hyllynro AND vh1.hyllyvali = tilausrivi.hyllyvali AND vh1.hyllytaso = tilausrivi.hyllytaso AND vh1.keraysvyohyke IN (".implode(",", $keraysvyohykkeet)."))";
        }

		if ($tapa == 'keraaja') {

			$query = "	SELECT tilausrivi.keratty,
						tilausrivi.otunnus,
						tilausrivi.kerattyaika,
						lasku.lahetepvm,
						kuka.nimi,
						kuka.keraajanro,
						SEC_TO_TIME(UNIX_TIMESTAMP(kerattyaika) - UNIX_TIMESTAMP(lahetepvm)) aika,
						SUM(IF(tilausrivi.var  = 'P', 1, 0)) puutteet,
						SUM(IF(tilausrivi.var != 'P' AND tilausrivi.tyyppi = 'L', 1, 0)) kappaleet,
						SUM(IF(tilausrivi.var != 'P' AND tilausrivi.tyyppi = 'G', 1, 0)) siirrot,
						ROUND(SUM(IF(tilausrivi.var != 'P', tilausrivi.jt + tilausrivi.varattu + tilausrivi.kpl, 0)), 2) kerkappaleet,
						ROUND(SUM(IF(tilausrivi.var != 'P', (tilausrivi.jt + tilausrivi.varattu + tilausrivi.kpl) * tuote.tuotemassa, 0)), 2) kerkilot,
						COUNT(*) yht
						FROM tilausrivi USE INDEX (yhtio_tyyppi_kerattyaika)
						JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays = '')
						JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND lasku.eilahetetta = '' AND lasku.sisainen = '')
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
						{$keraysvyohykejoin}
						{$lefti} JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = tilausrivi.yhtio AND kuka.kuka = tilausrivi.keratty)
						LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio AND
		                CONCAT(RPAD(UPPER(alkuhyllyalue),  5, '0'),LPAD(UPPER(alkuhyllynro),  5, '0')) <= CONCAT(RPAD(UPPER(tilausrivi.hyllyalue), 5, '0'),LPAD(UPPER(tilausrivi.hyllynro), 5, '0')) AND
		                CONCAT(RPAD(UPPER(loppuhyllyalue), 5, '0'),LPAD(UPPER(loppuhyllynro), 5, '0')) >= CONCAT(RPAD(UPPER(tilausrivi.hyllyalue), 5, '0'),LPAD(UPPER(tilausrivi.hyllynro), 5, '0')))
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.kerattyaika >= '{$vva}-{$kka}-{$ppa} 00:00:00'
						AND tilausrivi.kerattyaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
						AND tilausrivi.var IN ('','H','P')
						AND tilausrivi.tyyppi IN ('L','G')
						AND tilausrivi.keratty != ''
						{$lisa}
						GROUP BY 1,2,3,4,5,6,7
						ORDER BY tilausrivi.keratty, tilausrivi.kerattyaika";
			$result = pupe_query($query);

			echo "<table>";
			echo "<tr>";
			echo "<th nowrap>",t("Nimi"),"</th>";
			echo "<th nowrap>",t("Kerääjänro"),"</th>";
			echo "<th nowrap>",t("Tilaus"),"</th>";
			echo "<th nowrap>",t("Lähete tulostettu"),"</th>";
			echo "<th nowrap>",t("Tilaus kerätty"),"</th>";
			echo "<th nowrap>",t("Käytetty aika"),"</th>";
			echo "<th norwap>",t("Puuterivit"),"</th>";
			echo "<th norwap>",t("Siirrot"),"</th>";
			echo "<th nowrap>",t("Kerätyt"),"</th>";
			echo "<th nowrap>",t("Yhteensä"),"</th>";
			echo "<th nowrap>",t("Kappaleet"),"<br />",t("Yhteensä"),"</th>";
			echo "<th nowrap>",t("Kilot"),"<br />",t("Yhteensä"),"</th>";
			echo "</tr>";

			$lask		= 0;
			$edkeraaja	= 'EKADUUD';
			$psummayht	= 0;
			$ksummayht	= 0;
			$ssummayht	= 0;
			$summayht	= 0;
			$psumma	= 0;
			$ksumma	= 0;
			$ssumma	= 0;
			$summa	= 0;
			$kapsu	= 0;
			$kilsu	= 0;
			$kapsuyht = 0;
			$kilsuyht = 0;

			while ($row = mysql_fetch_assoc($result)) {

				if ($edkeraaja != $row["keratty"] and $summa > 0 and $edkeraaja != "EKADUUD") {
					echo "<tr>";
					echo "<th colspan='6'>",t("Yhteensä"),":</th>";
					echo "<td class='tumma' align='right'>{$psumma}</td>";
					echo "<td class='tumma' align='right'>{$ssumma}</td>";
					echo "<td class='tumma' align='right'>{$ksumma}</td>";
					echo "<td class='tumma' align='right'>{$summa}</td>";
					echo "<td class='tumma' align='right'>{$kapsu}</td>";
					echo "<td class='tumma' align='right'>{$kilsu}</td>";
					echo "</tr>";
					echo "<tr><td class='back'><br /></td></tr>";

					echo "<tr>";
					echo "<th nowrap>",t("Nimi"),"</th>";
					echo "<th nowrap>",t("Kerääjänro"),"</th>";
					echo "<th nowrap>",t("Tilaus"),"</th>";
					echo "<th nowrap>",t("Lähete tulostettu"),"</th>";
					echo "<th nowrap>",t("Tilaus kerätty"),"</th>";
					echo "<th nowrap>",t("Käytetty aika"),"</th>";
					echo "<th norwap>",t("Puuterivit"),"</th>";
					echo "<th norwap>",t("Siirrot"),"</th>";
					echo "<th nowrap>",t("Kerätyt"),"</th>";
					echo "<th nowrap>",t("Yhteensä"),"</th>";
					echo "<th nowrap>",t("Yhteensä"),"<br />",t("kappaleet"),"</th>";
					echo "<th nowrap>",t("Yhteensä"),"<br />",t("kilot"),"</th>";
					echo "</tr>";

					$psumma	= 0;
					$ksumma	= 0;
					$ssumma	= 0;
					$summa	= 0;
					$kapsu	= 0;
					$kilsu	= 0;
				}

				$row['kerkilot'] = abs($row['kerkilot']);

				echo "<tr>";
				echo "<td>{$row['nimi']} ({$row['keratty']})</td>";
				echo "<td>{$row['keraajanro']}</td>";
				echo "<td>{$row['otunnus']}</td>";
				echo "<td>",tv1dateconv($row["lahetepvm"], "P"),"</td>";
				echo "<td>",tv1dateconv($row["kerattyaika"], "P"),"</td>";
				echo "<td>{$row['aika']}</td>";
				echo "<td align='right'>{$row['puutteet']}</td>";
				echo "<td align='right'>{$row['siirrot']}</td>";
				echo "<td align='right'>{$row['kappaleet']}</td>";
				echo "<td align='right'>{$row['yht']}</td>";
				echo "<td align='right'>{$row['kerkappaleet']}</td>";
				echo "<td align='right'>{$row['kerkilot']}</td>";
				echo "</tr>";

				$row['kerkappaleet'] = abs($row['kerkappaleet']);

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
				echo "<tr>";
				echo "<th colspan='6'>",t("Yhteensä"),":</th>";
				echo "<td class='tumma' align='right'>{$psumma}</td>";
				echo "<td class='tumma' align='right'>{$ssumma}</td>";
				echo "<td class='tumma' align='right'>{$ksumma}</td>";
				echo "<td class='tumma' align='right'>{$summa}</td>";
				echo "<td class='tumma' align='right'>{$kapsu}</td>";
				echo "<td class='tumma' align='right'>{$kilsu}</td>";
				echo "</tr>";
				echo "<tr><td class='back'><br /></td></tr>";
			}

			// Kaikki yhteensä
			echo "<tr>";
			echo "<th colspan='6'>",t("Kaikki yhteensä"),":</th>";
			echo "<td class='tumma' align='right'>{$psummayht}</td>";
			echo "<td class='tumma' align='right'>{$ssummayht}</td>";
			echo "<td class='tumma' align='right'>{$ksummayht}</td>";
			echo "<td class='tumma' align='right'>{$summayht}</td>";
			echo "<td class='tumma' align='right'>{$kapsuyht}</td>";
			echo "<td class='tumma' align='right'>{$kilsuyht}</td>";
			echo "</tr>";

			echo "</table><br />";
		}

		if (($tapa == 'kerpvm') or ($tapa == 'kerkk')) {

			$grp = $tapa == 'kerkk' ? 'left(kerattyaika, 7)' : 'pvm';

			if ($tapa == 'kerkk') {
				$selecti = "LEFT(tilausrivi.kerattyaika,7) pvm,";
			}
			else {
				$selecti = "LEFT(tilausrivi.kerattyaika,10) pvm,";
			}

			$query = "	SELECT {$selecti}
						SUM(IF(tilausrivi.var  = 'P', 1, 0)) puutteet,
						SUM(IF(tilausrivi.var != 'P' and tilausrivi.tyyppi = 'L' and (tilausrivi.jt + tilausrivi.varattu + tilausrivi.kpl) > 0, 1, 0)) kappaleet,
						SUM(IF(tilausrivi.var != 'P' and tilausrivi.tyyppi = 'L' and (tilausrivi.jt + tilausrivi.varattu + tilausrivi.kpl) < 0, 1, 0)) kappaleet_palautus,
						SUM(IF(tilausrivi.var != 'P' and tilausrivi.tyyppi='G', 1, 0)) siirrot,
						COUNT(*) yht
						FROM tilausrivi USE INDEX (yhtio_tyyppi_kerattyaika)
						JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays = '')
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
						{$lefti} JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = tilausrivi.yhtio AND kuka.kuka = tilausrivi.keratty)
						LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio AND
		                CONCAT(RPAD(UPPER(alkuhyllyalue),  5, '0'),LPAD(UPPER(alkuhyllynro),  5, '0')) <= CONCAT(RPAD(UPPER(tilausrivi.hyllyalue), 5, '0'),LPAD(UPPER(tilausrivi.hyllynro), 5, '0')) AND
		                CONCAT(RPAD(UPPER(loppuhyllyalue), 5, '0'),LPAD(UPPER(loppuhyllynro), 5, '0')) >= CONCAT(RPAD(UPPER(tilausrivi.hyllyalue), 5, '0'),LPAD(UPPER(tilausrivi.hyllynro), 5, '0')))
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.kerattyaika >= '{$vva}-{$kka}-{$ppa} 00:00:00'
						AND tilausrivi.kerattyaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
						AND tilausrivi.var IN ('','H','P')
						AND tilausrivi.tyyppi IN ('L','G')
						{$lisa}
						GROUP BY 1
						ORDER BY 1";
			$result = pupe_query($query);

			echo "<table>";

			echo "<tr>";
			echo "<th>",t("Pvm"),"</th>";
			echo "<th>",t("Puutteet"),"</th>";
			echo "<th>",t("Siirrot"),"</th>";
			echo "<th>",t("Kerätyt"),"</th>";
			echo "<th>",t("Palautukset"),"</th>";
			echo "<th>",t("Yhteensä"),"</th>";
			echo "</tr>";

			$psummayht	= 0;
			$ksummayht	= 0;
			$palsummayht = 0;
			$ssummayht	= 0;
			$summayht	= 0;

			if ($tapa == 'kerkk') {

				while ($ressu = mysql_fetch_assoc($result)) {
					echo "<tr>";
					echo "<td align='right'>{$ressu['pvm']}</td>";
					echo "<td align='right'>{$ressu['puutteet']}</td>";
					echo "<td align='right'>{$ressu['siirrot']}</td>";
					echo "<td align='right'>{$ressu['kappaleet']}</td>";
					echo "<td align='right'>{$ressu['kappaleet_palautus']}</td>";
					echo "<td align='right'>{$ressu['yht']}</td>";
					echo "</tr>";

					// yhteensä
					$psummayht	+= $ressu["puutteet"];
					$palsummayht += abs($ressu["kappaleet_palautus"]);
					$ksummayht	+= $ressu["kappaleet"];
					$ssummayht	+= $ressu["siirrot"];
					$summayht	+= $ressu["yht"];
				}
			}
			else {

				while ($ressu = mysql_fetch_assoc($result)) {

					// $kerattyaika[$apu] = $ressu['kerattyaika'];

					// yhteensä
					$psummayht	+= $ressu["puutteet"];
					$palsummayht += abs($ressu["kappaleet_palautus"]);
					$ksummayht	+= $ressu["kappaleet"];
					$ssummayht	+= $ressu["siirrot"];
					$summayht	+= $ressu["yht"];

					echo "<tr>";
					echo "<td>",tv1dateconv($ressu['pvm'],"P"),"</td>";
					echo "<td align='right'>{$ressu['puutteet']}</td>";
					echo "<td align='right'>{$ressu['siirrot']}</td>";
					echo "<td align='right'>{$ressu['kappaleet']}</td>";
					echo "<td align='right'>{$ressu['kappaleet_palautus']}</td>";
					echo "<td align='right'>{$ressu['yht']}</td>";
					echo "</tr>";

				}
			}

			echo "<tr>";
			echo "<th>",t("Yhteensä"),"</th>";
			echo "<td class='tumma' align='right'>{$psummayht}</td>";
			echo "<td class='tumma' align='right'>{$ssummayht}</td>";
			echo "<td class='tumma' align='right'>{$ksummayht}</td>";
			echo "<td class='tumma' align='right'>{$palsummayht}</td>";
			echo "<td class='tumma' align='right'>{$summayht}</td>";
			echo "</tr>";
			echo "</table><br>";
		}
	}

	require ("inc/footer.inc");
