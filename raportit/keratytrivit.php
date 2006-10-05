<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Kerätyt rivit").":</font><hr>";

	if ($tee != '') {
		if ($tapa== 'keraaja') {
			$query = "	SELECT nimi, keraajanro, kuka
						FROM kuka
						WHERE yhtio='$kukarow[yhtio]' and keraajanro>0
						ORDER BY keraajanro";

			$result = mysql_query($query) or pupe_error($query);

			echo "	<table><tr><th>".t("Nimi")."</th><th>".t("Kerääjänro")."</th>
					<th>".t("Tilaus")."</th><th>".t("Puuterivit")."</th><th>".t("Siirrot")."</th>
					<th nowrap>".t("Rivit")."</th><th nowrap>".t("Yhteensä")."</th>
					<th nowrap>".t("Lähete tulostettu")."</th><th nowrap>".t("Tilaus kerätty")."</th><th nowrap>".t("Käytetty aika")."</th></tr>";
			$lask = 0;
			$edkeraaja = '';
	        $psummayht	= 0;
			$ksummayht	= 0;
			$summayht	= 0;

			while ($row = mysql_fetch_array($result)) {
				if ($edkeraaja != $row["kuka"] && $lask > 0 && $ksumma > 0) {
					echo "	<tr><td colspan='3' class='back'>".t("Yhteensä").":</td><td>$psumma</td>
							<td>$ssumma</td><td>$ksumma</td><td>$summa</td><td></td><td></td><td></td></tr>";
					echo "<tr><td class='back'><br></td></tr>";

					$psumma	= 0;
					$ksumma	= 0;
					$summa	= 0;
				}
				$query = "	SELECT otunnus,
							sum(if(var='P',1,0)) puutteet,
							sum(if(var!='P' and tyyppi='L',1,0)) kappaleet,
							sum(if(var!='P' and tyyppi='G',1,0)) siirrot,
							count(*) yht, kerattyaika, lahetepvm,
							sec_to_time(unix_timestamp(kerattyaika)-unix_timestamp(lahetepvm)) aika
							FROM tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus), lasku
							WHERE tilausrivi.yhtio='$kukarow[yhtio]' and lasku.yhtio='$kukarow[yhtio]' and tilausrivi.keratty='$row[kuka]'
							and tilausrivi.kerattyaika>='$vva-$kka-$ppa 00:00:00'
							and tilausrivi.kerattyaika<='$vvl-$kkl-$ppl 23:59:59'
							and tilausrivi.var in ('','H','P','-')
							and tilausrivi.tyyppi in ('L','G')
							and lasku.tunnus=tilausrivi.otunnus
							GROUP BY otunnus
							ORDER BY otunnus";

				$result1 = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result1) > 0) {
					while ($srow = mysql_fetch_array($result1)) {
						echo "	<tr><td>$row[nimi]</td><td>$row[keraajanro]</td>
									<td>$srow[otunnus]</td><td>$srow[puutteet]</td><td>$srow[siirrot]</td>
									<td>$srow[kappaleet]</td><td>$srow[yht]</td><td>$srow[lahetepvm]</td><td>$srow[kerattyaika]</td><td>$srow[aika]</td></tr>";


						$psumma	+= $srow["puutteet"];
						$ksumma	+= $srow["kappaleet"];
						$ssumma	+= $srow["siirrot"];
						$summa	+= $srow["yht"];

						// yhteensä
						$psummayht	+= $srow["puutteet"];
						$ksummayht	+= $srow["kappaleet"];
						$ssummayht	+= $srow["siirrot"];
						$summayht	+= $srow["yht"];
					}
				}

				$lask++;
				$edkeraaja = $row["kuka"];
			}
			if ($psumma > 0) {
				echo "	<tr><td colspan='3' class='back'>".t("Yhteensä").":</td><td>$psumma</td><td>$ssumma</td><td>$ksumma</td><td>$summa</td><td></td><td></td><td></td></tr>";
			}
			// Kaikki yhteensä
			echo "<tr><td class='back'><br></td></tr>";
			echo "<tr><td colspan='3' class='back'>".t("Kaikki yhteensä").":</td><td>$psummayht</td><td>$ssummayht</td><td>$ksummayht</td><td>$summayht</td><td></td><td></td><td></td></tr>";

			echo "</table>";
		}

		else {
			if (!isset($vva))
				$vvaa = date("Y");
			else {
				$vvaa = $vva;
			}
			$ppaa = '01';
			$kkaa = '01';


			$kkll = '12';
			$vvll = date("Y");
			$ppll = '31';

			$query = "SELECT date_format(left(kerattyaika,10), '%j') pvm, left(kerattyaika,10) kerattyaika,
					sum(if(var='P',1,0)) puutteet,
					sum(if(var!='P' and tyyppi='L',1,0)) kappaleet,
					sum(if(var!='P' and tyyppi='G',1,0)) siirrot,
					count(*) yht
					from tilausrivi, kuka
					where tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.kerattyaika >= '$vvaa-$kkaa-$ppaa 00:00:00'
					and tilausrivi.kerattyaika <= '$vvaa-$kkll-$ppll 23:59:59'
					and tilausrivi.var in ('','H','P','-')
					and tilausrivi.tyyppi in ('L','G')
					and kuka.yhtio = tilausrivi.yhtio
					and kuka.kuka = tilausrivi.keratty
					and kuka.keraajanro > 0
					group by pvm
					order by 1";
			$result = mysql_query($query) or pupe_error($query);

			//echo "$query";
			echo "<table>";

			echo "<tr>";
			echo "<th>".t("pvm")."</th>";

			echo "<th>".t("Kappaleet")."</th><th>".t("Siirrot")."</th><th>".t("Puutteet")."</th><th>".t("Yhteensä")."</th>";

			echo "</tr>";
			while ($ressu = mysql_fetch_array($result)) {
					$apu 		= (int) $ressu['pvm'];
					$puutteet[$apu]	= $ressu['puutteet'];
					$kerattyaika[$apu]= $ressu['kerattyaika'];
					$kappaleet[$apu]= $ressu['kappaleet'];
					$siirrot[$apu]= $ressu['siirrot'];
			}




			for ($i=1; $i<367; $i++) {

					echo "<tr>";
					echo "<td>";
					if (strlen($kerattyaika[$i])==0) echo "$i"; else  echo "$kerattyaika[$i]";
					echo "</td>";
					echo "<td>".$kappaleet[$i]."</td>";
					echo "<td>".$siirrot[$i]."</td>";
					echo "<td>".$puutteet[$i]."</td>";
					$yht = $kappaleet[$i]+$puutteet[$i]+$siirrot[$i];
					echo "<td>$yht</td>";
					echo "</tr>";
			}

			echo "</table>";
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
	echo "<tr><th>".t("Valitse tapa")."</th>
		<td colspan='3'><select name='tapa'>
		<option value='keraaja'>".t("Kerääjittäin")."</option>
		<option value='kerpvm'>".t("Päivittäin")."</option></select></td>";
	echo "<tr><th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppa' value='$ppa' size='3'></td>
		<td><input type='text' name='kka' value='$kka' size='3'></td>
		<td><input type='text' name='vva' value='$vva' size='5'></td><th><-- ".t("Jos valitset *Päivittäin* niin ajetaan tämän vuoden tiedot")."</th>
		</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppl' value='$ppl' size='3'></td>
		<td><input type='text' name='kkl' value='$kkl' size='3'></td>
		<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	echo "<tr><td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";



	require ("../inc/footer.inc");
?>
