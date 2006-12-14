<?php

	// Tämä skripti käyttää slave-tietokantapalvelinta
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

		if ($tapa == 'keraaja') {

			$query = "	SELECT tilausrivi.keratty,
						tilausrivi.otunnus,
						tilausrivi.kerattyaika,
						lasku.lahetepvm,
						kuka.nimi,
						kuka.keraajanro,
						sec_to_time(unix_timestamp(kerattyaika)-unix_timestamp(lahetepvm)) aika,
						sum(if(var  = 'P', 1, 0)) puutteet,
						sum(if(var != 'P' and tyyppi='L', 1, 0)) kappaleet,
						sum(if(var != 'P' and tyyppi='G', 1, 0)) siirrot,
						count(*) yht
						FROM tilausrivi USE INDEX (yhtio_tyyppi_kerattyaika)
						JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
						$lefti JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = tilausrivi.yhtio and kuka.kuka = tilausrivi.keratty)
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.kerattyaika >= '$vva-$kka-$ppa 00:00:00'
						and tilausrivi.kerattyaika <= '$vvl-$kkl-$ppl 23:59:59'
						and tilausrivi.var in ('','H','P')
						and tilausrivi.tyyppi in ('L','G')
						GROUP BY keratty, otunnus
						ORDER BY keratty, kerattyaika";
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
							<th>$psumma</th>
							<th>$ssumma</th>
							<th>$ksumma</th>
							<th>$summa</th>
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
							</tr>";

					$psumma	= 0;
					$ksumma	= 0;
					$ssumma	= 0;
					$summa	= 0;
				}

				echo "<tr>
						<td>$row[nimi] ($row[keratty])</td>
						<td>$row[keraajanro]</td>
						<td>$row[otunnus]</td>
						<td>$row[lahetepvm]</td>
						<td>$row[kerattyaika]</td>
						<td>$row[aika]</td>
						<td align='right'>$row[puutteet]</td>
						<td align='right'>$row[siirrot]</td>
						<td align='right'>$row[kappaleet]</td>
						<td align='right'>$row[yht]</td>
					</tr>";

				$psumma	+= $row["puutteet"];
				$ksumma	+= $row["kappaleet"];
				$ssumma	+= $row["siirrot"];
				$summa	+= $row["yht"];

				// yhteensä
				$psummayht	+= $row["puutteet"];
				$ksummayht	+= $row["kappaleet"];
				$ssummayht	+= $row["siirrot"];
				$summayht	+= $row["yht"];

				$lask++;
				$edkeraaja = $row["keratty"];
			}

			if ($summa > 0) {
				echo "<tr>
						<th colspan='6'>".t("Yhteensä").":</th>
						<th>$psumma</th>
						<th>$ssumma</th>
						<th>$ksumma</th>
						<th>$summa</th>
					</tr>";
				echo "<tr><td class='back'><br></td></tr>";
			}

			// Kaikki yhteensä
			echo "<tr>
					<th colspan='6'>".t("Kaikki yhteensä").":</th>
					<th>$psummayht</th>
					<th>$ssummayht</th>
					<th>$ksummayht</th>
					<th>$summayht</th>
					</tr>";

			echo "</table><br>";
		}

		if ($tapa == 'kerpvm') {

			if (!isset($vva)) {
				$vvaa = date("Y");
			}
			else {
				$vvaa = $vva;
			}

			$query = "	SELECT date_format(left(kerattyaika,10), '%j') pvm,
						left(kerattyaika,10) kerattyaika,
						sum(if(var  = 'P', 1, 0)) puutteet,
						sum(if(var != 'P' and tyyppi='L', 1, 0)) kappaleet,
						sum(if(var != 'P' and tyyppi='G', 1, 0)) siirrot,
						count(*) yht
						FROM tilausrivi USE INDEX (yhtio_tyyppi_kerattyaika)
						$lefti JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = tilausrivi.yhtio and kuka.kuka = tilausrivi.keratty)
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.kerattyaika >= '$vvaa-01-01 00:00:00'
						and tilausrivi.kerattyaika <= '$vvaa-12-31 23:59:59'
						and tilausrivi.var in ('','H','P')
						and tilausrivi.tyyppi in ('L','G')
						GROUP BY pvm
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

			$puutteet	= array();
			$kerattyaik	= array();
			$kappalee	= array();
			$siirrot	= array();
			$yht		= array();

			while ($ressu = mysql_fetch_array($result)) {

				$apu				= (int) $ressu['pvm'];
				$puutteet[$apu]		= $ressu['puutteet'];
				$kerattyaika[$apu]	= $ressu['kerattyaika'];
				$kappaleet[$apu]	= $ressu['kappaleet'];
				$siirrot[$apu]		= $ressu['siirrot'];
				$yht[$apu]			= $ressu['yht'];

				// yhteensä
				$psummayht	+= $ressu["puutteet"];
				$ksummayht	+= $ressu["kappaleet"];
				$ssummayht	+= $ressu["siirrot"];
				$summayht	+= $ressu["yht"];

			}

			for ($i=1; $i<367; $i++) {

				if (strlen($kerattyaika[$i]) == 0) $kerattyaika[$i] = $i;

				echo "<tr>";
				echo "<td>$kerattyaika[$i]</td>";
				echo "<td align='right'>$puutteet[$i]</td>";
				echo "<td align='right'>$siirrot[$i]</td>";
				echo "<td align='right'>$kappaleet[$i]</td>";
				echo "<td align='right'>$yht[$i]</td>";
				echo "</tr>";

			}

			echo "<tr>";
			echo "<th>".t("Yhteensä")."</th>";
			echo "<th>$psummayht</th>";
			echo "<th>$ssummayht</th>";
			echo "<th>$ksummayht</th>";
			echo "<th>$summayht</th>";
			echo "</tr>";

			echo "</table><br>";

		}

	}

	//Käyttöliittymä
	echo "<form method='post' action='$PHP_SELF'>";
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
				</select>
			</td>
		</tr>";

	echo "<tr>
			<th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td><td class='back'><-- ".t("Jos valitset *Päivittäin* niin ajetaan tämän vuoden tiedot")."</td>
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