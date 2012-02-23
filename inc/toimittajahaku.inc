<?php

	echo "<font class='head'>".t("Toimittajan laskut")."</font><hr>";

	if ($tee == 'A') {
		// N‰ytet‰‰n toimittajan laskuja
		//Jos k‰ytet‰‰n $tunnusta niin k‰ytet‰‰n liitostunnusta
		//Jos taas p‰‰ss‰t‰‰n $ytunnusta niin haetaan sen perusteella

		if (!isset($kausi)) $kausi = '';
		if (strlen($kausi) == 10) $laji = 'o';
		if (!isset($aikalisa)) $aikalisa ='';

		if ($laji == 'O') {
			$query = "	SELECT toimi.ytunnus, concat_ws(' ', if(toimi.tyyppi='K' and kuka.nimi is not null, kuka.nimi, toimi.nimi), toimi.nimitark) nimi, toimi.postitp
						FROM toimi
						LEFT JOIN kuka ON (kuka.yhtio = toimi.yhtio and kuka.kuka = toimi.nimi and toimi.tyyppi='K')
						WHERE toimi.tunnus = '$tunnus' and toimi.yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
			 	echo "<b>".t("Haulla ei lˆytynyt yht‰‰n toimittajaa")."</b>";
			}
		 	$trow = mysql_fetch_array ($result);

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Ytunnus")."</th>";
			echo "<th>".t("Nimi")."</th>";
			echo "<th>".t("Postitp")."</th>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>".tarkistahetu($trow["ytunnus"])."</td>";
			echo "<td>$trow[nimi]</td>";
			echo "<td>$trow[postitp]</td>";
			echo "</tr>";
			echo "</table>";

			echo "<br>";
			echo "<table>";
			echo "<form action = 'raportit.php?toim=toimittajahaku' method='post'>
					<tr>
					<input type='hidden' name='tunnus' value='$tunnus'>
					<input type='hidden' name='tee' value='A'>";

			$sel = array();
			$sel[$laji]	= " selected";

			echo "<th>".t("Laskuntyyppi")."</th>";
			echo "<td><select name = 'laji'>
					<option $sel[K] value = 'K'>".t("Kaikki")."
					<option $sel[H] value = 'H'>".t("Hyv‰ksytt‰v‰n‰")."
					<option $sel[M] value = 'M'>".t("Maksamatta")."
					<option $sel[Q] value = 'Q'>".t("Odottaa suoritusta")."
					<option $sel[Y] value = 'Y'>".t("Maksettu")."
					<option $sel[O] value = 'O'>".t("Ostoraportti")."
					</select></td>
					<td class='back'><input type='Submit' value='".t("N‰yt‰")."'></td>
					</tr></form>";
			echo "</table>";

			echo "<br>";


			$lisa='';

			if (!isset($kausi)) $kausi='';

			if ($kausi=='') {
				$alku = substr($yhtiorow["tilikausi_alku"],04) - 2;
				$lisa = "and tapvm >= '$alku' and tapvm <= '$yhtiorow[tilikausi_loppu]'";
				$kentta = "left(tapvm,4)";
			}

			if (strlen($kausi) == 4) {
				$lisa = "and tapvm >= '$kausi-01-01' and tapvm <= '$kausi-12-31'";
				$kentta = "left(tapvm,7)";
			}

			if (strlen($kausi) == 7) {
				$kk= (int) substr($kausi,-2);
				$vv= (int) substr($kausi,0,4);
				$kk++;

				if ($kk > 12) {
					$vv++;
					$kk = $kk-12;
				}

				$seuraava = $vv . "-" . $kk;

				$lisa = "and tapvm >= '$kausi-01' and tapvm < '$seuraava-01'";
				$kentta = "tapvm";
			}

			$query = "	SELECT $kentta ajalta, sum(summa) ostot, round(sum(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi)),2) $yhtiorow[valkoodi]
					  	FROM lasku
					  	WHERE yhtio='$kukarow[yhtio]' and liitostunnus = '$tunnus' $lisa
					  	and tila in ('H', 'M', 'P', 'Q', 'Y')
					  	GROUP BY 1";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
			 	echo "<font class='error'>".t("Haulla ei lˆytynyt yht‰‰n laskua")."</font>";
			}

			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "</tr>";

			while ($trow=mysql_fetch_array ($result)) {
				echo "<tr class='aktiivi'>";
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					if ($i==0) {
						echo "<td><a href='$PHP_SELF?toim=toimittajahaku&tee=A&laji=O&tunnus=$tunnus&kausi=$trow[$i]'>$trow[$i]</a></td>";
					}
					else {
						echo "<td>$trow[$i]</td>";
					}
				}
				echo "</tr>";
			}
			echo "</table><br><br>";

			require ("footer.inc");
			exit;
		}
		elseif ($laji == 'K' or $laji == 'H' or $laji == 'M' or $laji == 'Y' or $laji == 'Q' or $laji == 'o') {

			if ($ytunnus != "") {
				//Jos taas p‰‰ss‰t‰‰n $ytunnusta niin haetaan sen perusteella
				$query = "	SELECT toimi.ytunnus, concat_ws(' ', if(toimi.tyyppi='K' and kuka.nimi is not null, kuka.nimi, toimi.nimi), toimi.nimitark) nimi, toimi.postitp
							FROM toimi
							LEFT JOIN kuka ON (kuka.yhtio = toimi.yhtio and kuka.kuka = toimi.nimi and toimi.tyyppi='K')
							WHERE toimi.ytunnus 	= '$ytunnus'
							and toimi.yhtio 		= '$kukarow[yhtio]'
							ORDER by toimi.nimi";

				$toimiliilisa = " and ytunnus = '$ytunnus' ";
			}
			else {
				//Jos k‰ytet‰‰n $tunnusta niin k‰ytet‰‰n liitostunnusta
				$query = "	SELECT toimi.ytunnus, concat_ws(' ', if(toimi.tyyppi='K' and kuka.nimi is not null, kuka.nimi, toimi.nimi), toimi.nimitark) nimi, toimi.postitp
							FROM toimi
							LEFT JOIN kuka ON (kuka.yhtio = toimi.yhtio and kuka.kuka = toimi.nimi and toimi.tyyppi='K')
							WHERE toimi.tunnus 	= '$tunnus'
							and toimi.yhtio 	= '$kukarow[yhtio]'";

				$toimiliilisa = " and liitostunnus = '$tunnus' ";
			}

			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
			 	echo "<b>".t("Haulla ei lˆytynyt yht‰‰n toimittajaa")."</b>";
			}
			else {
				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Ytunnus")."</th>";
				echo "<th>".t("Nimi")."</th>";
				echo "<th>".t("Postitp")."</th>";
				echo "</tr>";

		 		while ($trow = mysql_fetch_array($result)) {
					echo "<tr>";
					echo "<td>".tarkistahetu($trow["ytunnus"])."</td>";
					echo "<td>$trow[nimi]</td><td>$trow[postitp]</td>";
					echo "</tr>";
				}

				echo "</table>";
				echo "<br>";
				echo "<table>";
				echo "<form action = 'raportit.php?toim=toimittajahaku' method='post'>
						<tr>
						<input type='hidden' name='tunnus' value='$tunnus'>
						<input type='hidden' name='tee' value='A'>
						<input type='hidden' name='lopetus' value='$lopetus'>";

				$sel = array();
				$sel[$laji]	= " selected";

				echo "<th>".t("Laskuntyyppi")."</th>";
				echo "<td><select name = 'laji'>
						<option $sel[K] value = 'K'>".t("Kaikki")."
						<option $sel[H] value = 'H'>".t("Hyv‰ksytt‰v‰n‰")."
						<option $sel[M] value = 'M'>".t("Maksamatta")."
						<option $sel[Q] value = 'Q'>".t("Odottaa suoritusta")."
						<option $sel[Y] value = 'Y'>".t("Maksettu")."
						<option $sel[O] value = 'O'>".t("Ostoraportti")."
						</select></td>
						</tr>";

				echo "<tr>";
				echo "<th>".t("Alkup‰iv‰m‰‰r‰")."</th>";
				echo "<td>";
				echo "<input type='text' name='pp1' value='$pp1' size='4'>";
				echo "<input type='text' name='kk1' value='$kk1' size='4'>";
				echo "<input type='text' name='vv1' value='$vv1' size='4'>";
				echo "</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<th>".t("Loppup‰iv‰m‰‰r‰")."</th>";
				echo "<td>";
				echo "<input type='text' name='pp2' value='$pp2' size='4'>";
				echo "<input type='text' name='kk2' value='$kk2' size='4'>";
				echo "<input type='text' name='vv2' value='$vv2' size='4'>";
				echo "</td>";
				echo "<td class='back'><input type='Submit' value='".t("N‰yt‰")."'></td>";
				echo "</tr>";
				echo "</form>";
				echo "</table>";

				echo "<br>";

				echo "<a href='$PHP_SELF?toim=toimittajahaku&tee=A&laji=$laji&tunnus=$tunnus&limit=NO&lopetus=$lopetus'>".t("N‰yt‰ kaikki laskut")."</a><br><br>";

				$lisa = '';

				if ($laji=='M'){
					$lisa = " and tila < 'S'";
				}
				elseif ($laji != 'K') {
					$lisa = " and tila = '$laji'";
				}

				if ($laji == 'o') $lisa = " and tapvm = '$kausi'";

				$alku += 0;

				if (@checkdate($kk1, $pp1, $vv1)) {
					$aikalisa = " AND lasku.tapvm >='$vv1-$kk1-$pp1' ";
					$limit = "NO";
				}
				elseif ($kk1 != '' or $pp1 != '' or $vv1 != '') {
					echo "<p><font class='error'>".t("Alkup‰iv‰m‰‰r‰ virheellinen!")."</font></p>";
				}

                if (@checkdate($kk2, $pp2, $vv2)) {
					$aikalisa .= " AND lasku.tapvm <='$vv2-$kk2-$pp2' ";
					$limit = "NO";
				}
				elseif ($kk2 != '' or $pp2 != '' or $vv2 != '') {
					echo "<p><font class='error'>".t("Loppup‰iv‰m‰‰r‰ virheellinen!")."</font></p>";
				}

				if ($limit != "NO") {
					$limlis = " LIMIT $alku, 50 ";
				}

				$query = "	SELECT tapvm, erpcm, summa, valkoodi, vienti,
							concat_ws(' ', viite, viesti) 'viite/viesti', ebid, tila, tunnus,
							if(laskunro = 0, '', laskunro) laskunro,
							round(lasku.summa * if(lasku.maksu_kurssi = 0, lasku.vienti_kurssi, lasku.maksu_kurssi), 2) kotisumma
						  	FROM lasku
						  	WHERE yhtio = '$kukarow[yhtio]'
						  	$toimiliilisa
							$lisa
							$aikalisa
						  	and tila in ('H', 'M', 'P', 'Q', 'Y')
						  	ORDER BY tapvm desc
							$limlis";
				$result = pupe_query($query);
				$seraavako = mysql_num_rows($result);

				if (mysql_num_rows($result) == 0) {
				 	echo "<font class='error'>".t("Haulla ei lˆytynyt yht‰‰n laskua")."</font>";
				}

				pupe_DataTables(array(array($pupe_DataTables, 8, 8)));

				echo "<table class='display dataTable' id='$pupe_DataTables'>";

				echo "<thead>";
				echo "<tr>
						<th>".t("Tapvm/Erpvm")."</th>
						<th>".t("Summa")."</th>
						<th>".t("Valuutta")."</th>
						<th>".t("Laskunro")."</th>
						<th>".t("Viite/Viesti")."</th>
						<th>".t("Keikka")."</th>
						<th>".t("EBID")."</th>
						<th>".t("Tila/Vienti")."</th>
						</tr>
						<tr>
						<td><input type='text' class='search_field' name='search_tapvm'></td>
						<td><input type='text' class='search_field' name='search_summa'></td>
						<td><input type='text' class='search_field' name='search_valuutta'></td>
						<td><input type='text' class='search_field' name='search_laskunro'></td>
						<td><input type='text' class='search_field' name='search_viite'></td>
						<td><input type='text' class='search_field' name='search_keikka'></td>
						<td><input type='text' class='search_field' name='search_ebid'></td>
						<td><input type='text' class='search_field' name='search_tila'></td>
					</tr>";

				echo "</thead>";
				echo "<tbody>";

				$yhteensa = array();

				while ($trow = mysql_fetch_array ($result)) {
					echo "<tr class='aktiivi'>";

					if ($kukarow['taso'] != 1 and $kukarow['taso'] != 2 and $kukarow['taso'] != 3) {
						echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."<br>".tv1dateconv($trow["erpcm"])."</td>";
					}
					else {
						echo "<td valign='top'><a href = 'muutosite.php?tee=E&tunnus=$trow[tunnus]&lopetus=$PHP_SELF////toim=$toim//tee=$tee//laji=$laji//tunnus=$tunnus'>".tv1dateconv($trow["tapvm"])."</a><br>".tv1dateconv($trow["erpcm"])."</td>";
					}

					echo "<td valign='top' align='right'>$trow[summa]</td>";
					echo "<td valign='top'>$trow[valkoodi]</td>";
					echo "<td valign='top'>$trow[laskunro]</td>";
					echo "<td valign='top'>".$trow["viite/viesti"]."</td>";

					//tehd‰‰n keikka linkki
					echo "<td valign='top'>";

					if ($trow["vienti"] != "A" and $trow["vienti"] != "D" and $trow["vienti"] != "G") {
						$query = "	SELECT keikka.laskunro, keikka.tunnus
									FROM lasku
									JOIN lasku AS keikka on lasku.yhtio = keikka.yhtio
									AND lasku.laskunro = keikka.laskunro
									AND keikka.tila = 'K'
									AND keikka.vanhatunnus = 0
									WHERE lasku.yhtio = '$kukarow[yhtio]'
									AND lasku.vanhatunnus = '$trow[tunnus]'";
						$keikka_res = pupe_query($query);

						while ($keikka_row = mysql_fetch_array($keikka_res)) {
							echo "<a href='tilauskasittely/selaa_tilauksia.php?toim=KEIKKA&tee=tilaus&tunnus=$keikka_row[tunnus]'>".$keikka_row['laskunro']."</a><br>";
						}
					}
					echo "</td>";

					// tehd‰‰n lasku linkki
					echo "<td valign='top'>".ebid($trow['tunnus']) ."</td>";

					$laskutyyppi = $trow["tila"];
					require "inc/laskutyyppi.inc";

					echo "<td valign='top'>".t($laskutyyppi)."<br>".ostolaskun_vienti($trow["vienti"])."</td>";

					$yhteensa["ZZ_".$yhtiorow["valkoodi"]] += $trow["kotisumma"];

					if (strtoupper($trow["valkoodi"]) != $yhtiorow["valkoodi"]) {
						$yhteensa[$trow["valkoodi"]] += $trow["summa"];
					}

					echo "</tr>";
				}

				ksort($yhteensa);
				echo "</tbody>";
				echo "<tfoot>";

				foreach ($yhteensa as $val => $sum) {

					if ($val == "ZZ_".$yhtiorow["valkoodi"]) $val = $yhtiorow["valkoodi"];

					echo "<tr>
							<td class='tumma'>".t("Yhteens‰:")."</td>
							<td class='tumma' align='right' name='yhteensa' id='yhteensa_$val'>".sprintf("%.2f", $sum)."</td>
							<td class='tumma'>$val</td>
							<td class='tumma' colspan='5'></td></tr>";
				}
				echo "</tfoot>";
				echo "</table><br>";

				if ($limit != "NO") {

					if ($alku > 0) {
						$siirry = $alku - 50;
						echo "<a href = '$PHP_SELF?toim=toimittajahaku&tee=A&tunnus=$tunnus&ytunnus=$ytunnus&alku=$siirry&laji=$laji'>&laquo; ".t("Edelliset")."</a> ";
					}

					echo "&nbsp;&nbsp;";

					if ($seraavako >= 50) {
						$siirry = $alku + 50;
						echo "<a href = '$PHP_SELF?toim=toimittajahaku&tee=A&tunnus=$tunnus&ytunnus=$ytunnus&alku=$siirry&laji=$laji'>".t("Seuraavat")." &raquo;</a> ";
						echo "<br><br>";
					}

				}

				require ("footer.inc");
				exit;
			}
		}
		else {
			echo "Virheellinen laji!";
			require ("footer.inc");
			exit;
		}
	}

	$lisat = '';

	if ($tee == 'S') { // S = selaussanahaku
		$lisat = "and toimi.selaus like '%" . $nimi . "%'";
	}

	if ($tee == 'N') { // N = nimihaku
		$lisat = "and (toimi.nimi like '%$nimi%' or kuka.nimi like '%$nimi%')";
	}

	if ($tee == 'Y') { // Y = yritystunnushaku
		$lisat = "and toimi.ytunnus = '$nimi'";
	}

	if ($tee == 'T') { // T = toimittajanro
		$lisat = "and toimi.toimittajanro = '$nimi'";
	}

	if ($lisat != '') {
		$query = "	SELECT toimi.tunnus, toimi.ytunnus, concat_ws(' ', if(toimi.tyyppi='K' and kuka.nimi is not null, kuka.nimi, toimi.nimi), toimi.nimitark) nimi, toimi.postitp, round(sum(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi)),2) avoimet
					FROM toimi
					LEFT JOIN kuka ON (kuka.yhtio = toimi.yhtio and kuka.kuka = toimi.nimi and toimi.tyyppi='K')
					LEFT JOIN lasku ON toimi.yhtio = lasku.yhtio and toimi.tunnus = lasku.liitostunnus and tila in ('H','M')
					WHERE toimi.yhtio = '$kukarow[yhtio]'
					$lisat
					GROUP BY toimi.tunnus
					ORDER BY toimi.selaus, toimi.nimi";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Haulla ei lˆytynyt yht‰‰n toimittajaa")."</font><br>";
			$tee = '';
		}
		elseif (mysql_num_rows($result) > 40) {
			echo "<font class='error'>".t("Haulla lˆytyi liikaa toimittajia. Tarkenna hakua")."</font><br>";
			$tee = '';
		}
		else {
			echo "<table><tr>";
			echo "<th>" . t("Ytunnus")."</th>";
			echo "<th>" . t("Nimi")."</th>";
			echo "<th>" . t("Postitp")."</th>";
			echo "<th>" . t("Avoimet")."</th>";
			echo "<th>" . t("Valitse")."</th><th></th></tr>";

			while ($trow = mysql_fetch_array($result)) {
				echo "<form action = 'raportit.php?toim=toimittajahaku' method='post'>
						<tr>
						<input type='hidden' name='tunnus' value='$trow[tunnus]'>
						<input type='hidden' name='lopetus' value='$PHP_SELF////toim=$toim//tee=$tee//nimi=$nimi'>
						<input type='hidden' name='tee' value='A'>";

				echo "<td>".tarkistahetu($trow["ytunnus"])."</td>";
				echo "<td>$trow[nimi]</td>";
				echo "<td>$trow[postitp]</td>";
				echo "<td>$trow[avoimet]</td>";

				echo "<td><select name = 'laji'>
						<option value = 'K'>".t("Kaikki")."
						<option value = 'H'>".t("Hyv‰ksytt‰v‰n‰")."
						<option value = 'M'>".t("Maksamatta")."
						<option value = 'Q'>".t("Odottaa suoritusta")."
						<option value = 'Y'>".t("Maksettu")."
						<option value = 'O'>".t("Ostoraportti")."
						</select></td>
						<td><input type='Submit' value='".t("N‰yt‰")."'></td>
						</tr></form>";
			}
			echo "</table>";
		}
	}

	if ($tee == '') {
		echo "<form name = 'valinta' action = 'raportit.php' method='post'>
				<input type='hidden' name='toim' value='toimittajahaku'>
				<br><table>
				<tr>
				<th>".t("Valitse toimittaja")."</th>
				<td><input type = 'text' name = 'nimi'></td>
				<td><select name='tee'>
				<option value = 'N'>".t("Toimittajan nimi")."
				<option value = 'S'>".t("Toimittajan selaussana")."
				<option value = 'Y'>".t("Y-tunnus")."
				<option value = 'T'>".t("Toimittajanro")."
				</select>
				</td>
				<td class='back'><input type = 'submit' value = '".t("Valitse")."'></td>
				</tr></table></form>";

		$formi = 'valinta';
		$kentta = 'nimi';

		require "inc/footer.inc";
		exit;
	}
?>