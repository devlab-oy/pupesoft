<?php

	require("inc/parametrit.inc");

	if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
		require ("raportit/naytatilaus.inc");
		echo "<br><br><br>";
		$tee = "Z";
	}

	if (($tee == 'N') or ($tee == 'E')) {
		if ($tee == 'N') {
			$oper='>';
			$suun='';
		}
		else {
			$oper='<';
			$suun='desc';
		}

		$query = "	SELECT tuote.tuoteno, sum(saldo) saldo, status
					FROM tuote
					LEFT JOIN tuotepaikat ON tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.yhtio=tuote.yhtio
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno " . $oper . " '$tuoteno'
					GROUP BY tuote.tuoteno
					HAVING status NOT IN ('P','X') or saldo > 0
					ORDER BY tuote.tuoteno " . $suun . "
					LIMIT 1";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$trow = mysql_fetch_array ($result);
			$tuoteno = $trow['tuoteno'];
			$tee='Z';
		}
		else {
			$varaosavirhe = t("Yhtään tuotetta ei löytynyt")."!";
			$tuoteno = '';
			$tee='Y';
		}
	}

	echo "<font class='head'>".t("Tuotekysely")."</font><hr>";

	if (($tee == 'Z') and ($tyyppi == '')) {
		require "inc/tuotehaku.inc";
	}
	if (($tee == 'Z') and ($tyyppi != '')) {

		if ($tyyppi == 'TOIMTUOTENO') {

			$query = "	SELECT tuotteen_toimittajat.tuoteno, sum(saldo) saldo, status
						FROM tuotteen_toimittajat
						JOIN tuote ON tuote.yhtio=tuotteen_toimittajat.yhtio and tuote.tuoteno=tuotteen_toimittajat.tuoteno and tuote.status NOT IN ('P','X')
						LEFT JOIN tuotepaikat ON tuotepaikat.yhtio=tuotteen_toimittajat.yhtio and tuotepaikat.tuoteno=tuotteen_toimittajat.tuoteno
						WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
						and tuotteen_toimittajat.toim_tuoteno = '$tuoteno'
						GROUP BY tuotteen_toimittajat.tuoteno
						HAVING status NOT IN ('P','X') or saldo > 0
						ORDER BY tuote.tuoteno";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				$varaosavirhe = t("VIRHE: Tiedolla ei löytynyt tuotetta")."!";
				$tee = 'Y';
			}
			elseif (mysql_num_rows($result) > 1) {
				$varaosavirhe = t("VIRHE: Tiedolla löytyi useita tuotteita")."!";
				$tee = 'Y';
			}
			else {
				$tr = mysql_fetch_array($result);
				$tuoteno = $tr["tuoteno"];
			}
		}
		elseif ($tyyppi != '') {
			$query = "	SELECT tuoteno
						FROM tuotteen_avainsanat
						WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and laji='$tyyppi'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				$varaosavirhe = t("VIRHE: Tiedolla ei löytynyt tuotetta")."!";
				$tee = 'Y';
			}
			else {
				$tr = mysql_fetch_array($result);
				$tuoteno = $tr["tuoteno"];
			}
		}
	}

	if ($tee=='Y') echo "<font class='error'>$varaosavirhe</font>";

	 //syotetaan tuotenumero
	$formi  = 'formi';
	$kentta = 'tuoteno';

	echo "<table><tr>";;
	echo "<form action='$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='Z'>";

	echo "<td class='back'><select name='tyyppi'>";
	echo "<option value=''>".t("Tuotenumero").":</option>";
	echo "<option value='TOIMTUOTENO'>".t("Toimittajan tuotenumero").":</option>";

	$query = "	SELECT selite, selitetark
				FROM avainsana
				WHERE laji = 'TUOTEULK' and yhtio = '$kukarow[yhtio]'
				ORDER BY jarjestys";
	$vresult = mysql_query($query) or pupe_error($query);

	while ($vrow = mysql_fetch_array($vresult)) {
		echo "<option value='$vrow[selite]'>$vrow[selitetark]:</option>";
	}

	echo "</select></th>";
	echo "<td class='back'><input type='text' name='tuoteno' value=''></td>";
	echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
	echo "</form>";

	//Jos ei haettu, annetaan 'edellinen' & 'seuraava'-nappi
	if (($ulos=='') and ($tee=='Z')) {
		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tee' value='E'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<td class='back'>";
		echo "<input type='Submit' value='".t("Edellinen")."'>";
		echo "</td>";
		echo "</form>";

		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='tee' value='N'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<td class='back'>";
		echo "<input type='Submit' value='".t("Seuraava")."'>";
		echo "</td>";
		echo "</form>";
	}
	echo "</tr></table>";



	//tuotteen varastostatus
	if ($tee == 'Z') {

		$query = "	SELECT tuote.*, date_format(tuote.muutospvm, '%Y-%m-%d') muutos, date_format(tuote.luontiaika, '%Y-%m-%d') luonti,
					group_concat(distinct tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '<br>') toimittaja,
					group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '<br>') osto_era,
					group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno,
					group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '<br>') tuotekerroin,
					group_concat(distinct concat_ws(' ',tuotteen_toimittajat.ostohinta,upper(tuotteen_toimittajat.valuutta)) order by tuotteen_toimittajat.tunnus separator '<br>') ostohinta
					FROM tuote
					LEFT JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno = '$tuoteno'
					GROUP BY tuote.tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		$query = "select sum(saldo) saldo from tuotepaikat  where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
		$salre = mysql_query($query) or pupe_error($query);
		$salro = mysql_fetch_array($salre);

		if (mysql_num_rows($result) == 1) {
			$tuoterow = mysql_fetch_array($result);
		}
		else {
			$tuoterow = array();
		}

		if ($tuoterow["tuoteno"] != "" and (!in_array($tuoterow["status"], array('P', 'X')) or $salro["saldo"] != 0)) {

			// Laitetaan kehahin oikein...
			if ($tuoterow['sarjanumeroseuranta'] != "") {
				$query = "	SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno = '$tuoterow[tuoteno]'
							and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
							and tilausrivi_osto.laskutettuaika != '0000-00-00'";
				$sarjares = mysql_query($query) or pupe_error($query);
				$sarjarow = mysql_fetch_array($sarjares);

				$tuoterow["kehahin"] = sprintf('%.4f', $sarjarow["kehahin"]);
			}

			if ($tuoterow['epakurantti1pvm'] != '0000-00-00') $tuoterow['kehahin'] = $tuoterow['kehahin'] / 2;
			if ($tuoterow['epakurantti2pvm'] != '0000-00-00') $tuoterow['kehahin'] = 0;

			//tullinimike
			$cn1 = $tuoterow["tullinimike1"];
			$cn2 = substr($tuoterow["tullinimike1"],0,6);
			$cn3 = substr($tuoterow["tullinimike1"],0,4);

			$query = "select cn, dm, su from tullinimike where cn='$cn1' and kieli = '$yhtiorow[kieli]'";
			$tulliresult1 = mysql_query($query) or pupe_error($query);

			$query = "select cn, dm, su from tullinimike where cn='$cn2' and kieli = '$yhtiorow[kieli]'";
			$tulliresult2 = mysql_query($query) or pupe_error($query);

			$query = "select cn, dm, su from tullinimike where cn='$cn3' and kieli = '$yhtiorow[kieli]'";
			$tulliresult3 = mysql_query($query) or pupe_error($query);

			$tullirow1 = mysql_fetch_array($tulliresult1);
			$tullirow2 = mysql_fetch_array($tulliresult2);
			$tullirow3 = mysql_fetch_array($tulliresult3);

			//perusalennus
			$query  = "select alennus from perusalennus where ryhma='$tuoterow[aleryhma]' and yhtio='$kukarow[yhtio]'";
			$peralresult = mysql_query($query) or pupe_error($query);
			$peralrow = mysql_fetch_array($peralresult);

			$query = "	select distinct valkoodi, maa from hinnasto
						where yhtio = '$kukarow[yhtio]'
						and tuoteno = '$tuoterow[tuoteno]'
						and laji = ''
						order by maa, valkoodi";
			$hintavalresult = mysql_query($query) or pupe_error($query);

			$valuuttalisa = "";

			while ($hintavalrow = mysql_fetch_array($hintavalresult)) {

				// katotaan onko tuotteelle valuuttahintoja
				$query = "	select *
							from hinnasto
							where yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tuoterow[tuoteno]'
							and valkoodi = '$hintavalrow[valkoodi]'
							and maa = '$hintavalrow[maa]'
							and laji = ''
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
							limit 1";
				$hintaresult = mysql_query($query) or pupe_error($query);

				while ($hintarow = mysql_fetch_array($hintaresult)) {
					$valuuttalisa .= "<br>$hintarow[maa]: $hintarow[hinta] $hintarow[valkoodi]";
				}

			}

			//eka laitetaan tuotteen yleiset (aika staattiset) tiedot
			echo "<table>";

			//1
			echo "<tr><th>".t("Tuotenumero")."</th><th>".t("Yksikkö")."</th><th colspan='4'>".t("Nimitys")."</th>";
			echo "<tr><td>$tuoterow[tuoteno]</td><td>$tuoterow[yksikko]</td><td colspan='4'>".substr(asana('nimitys_',$tuoterow['tuoteno'],$tuoterow['nimitys']),0,100)."</td></tr>";

			//2
			echo "<tr><th>".t("Osasto/try")."</th><th>".t("Toimittaja")."</th><th>".t("Aleryhmä")."</th><th>".t("Tähti")."</th><th>".t("Perusalennus")."</th><th>".t("VAK")."</th></tr>";
			echo "<td>$tuoterow[osasto]/$tuoterow[try]</td><td>$tuoterow[toimittaja]</td>
					<td>$tuoterow[aleryhma]</td><td>$tuoterow[tahtituote]</td><td>$peralrow[alennus]%</td><td>$tuoterow[vakkoodi]</td></tr>";

			//3
			echo "<tr><th>".t("Toimtuoteno")."</th><th>".t("Myyntihinta")."</th><th>".t("Netto/Ovh")."</th><th>".t("Ostohinta")."</th><th>".t("Kehahinta")."</th><th>".t("Vihahinta")."</th>";
			echo "<tr><td valign='top' >$tuoterow[toim_tuoteno]</td>
						<td valign='top' align='right'>$tuoterow[myyntihinta] $yhtiorow[valkoodi]$valuuttalisa</td>
						<td valign='top' align='right'>$tuoterow[nettohinta]/$tuoterow[myymalahinta]</td>
						<td valign='top' align='right'>$tuoterow[ostohinta]</td>
						<td valign='top' align='right'>$tuoterow[kehahin]</td>
						<td valign='top' align='right'>$tuoterow[vihahin] $tuoterow[vihapvm]</td>
				</tr>";

			//4
			echo "<tr><th>".t("Hälyraja")."</th><th>".t("Tilerä")."</th><th>".t("Toierä")."</th><th>".t("Kerroin")."</th><th>".t("Tarrakerroin")."</th><th>".t("Tarrakpl")."</th>";
			echo "<tr><td valign='top' align='right'>$tuoterow[halytysraja]</td>
						<td valign='top' align='right'>$tuoterow[osto_era]</td>
						<td valign='top' align='right'>$tuoterow[myynti_era]</td>
						<td valign='top' align='right'>$tuoterow[tuotekerroin]</td>
						<td valign='top' align='right'>$tuoterow[tarrakerroin]</td>
						<td valign='top' align='right'>$tuoterow[tarrakpl]</td>
					</tr>";

			//5
			echo "<tr><th>".t("Tullinimike")."</th><th colspan='4'>".t("Tullinimikkeen kuvaus")."</th><th>".t("Toinen paljous")."</th></tr>";
			echo "<tr><td>$tullirow1[cn]</td><td colspan='4'>".substr($tullirow3['dm'],0,20)." - ".substr($tullirow2['dm'],0,20)." - ".substr($tullirow1['dm'],0,20)."</td><td>$tullirow1[su]</td></tr>";


			//6
			echo "<tr><th>".t("Luontipvm")."</th><th>".t("Muutospvm")."</th><th>".t("Epäkurantti1pvm")."</th><th>".t("Epäkurantti2pvm")."</th><th colspan='2'>".t("Tuotteen kuvaus")."</th></tr>";
			echo "<tr><td>$tuoterow[luonti]</td><td>$tuoterow[muutos]</td><td>$tuoterow[epakurantti1pvm]</td><td>$tuoterow[epakurantti2pvm]</td><td colspan='2'>$tuoterow[kuvaus]&nbsp;</td></tr>";


			//7
			echo "<tr><th>".t("Info")."</th><th colspan='5'>".t("Avainsanat")."</th></tr>";
			echo "<tr><td>$tuoterow[muuta]&nbsp;</td><td colspan='5'>$tuoterow[lyhytkuvaus]</td></tr>";


			echo "</table>";

			echo "<table><tr>";
			echo "<td class='back' valign='top' align='left'>";
			echo "<table>";
			echo "<tr><th>".t("Korvaavat")."</th><th>".t("Kpl")."</th></tr>";

			//korvaavat tuotteet
			$query  = "select * from korvaavat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$korvaresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($korvaresult)==0) {
				echo "<tr><td>".t("Ei korvaavia")."!</td><td></td></tr>";
			}
			else {
				// tuote löytyi, joten haetaan sen id...
				$row    = mysql_fetch_array($korvaresult);
				$id		= $row['id'];

				$query = "select * from korvaavat where id='$id' and tuoteno<>'$tuoteno' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
				$korva2result = mysql_query($query) or pupe_error($query);

				while ($row = mysql_fetch_array($korva2result)) {
					list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"]);

					echo "<tr><td><a href='$PHP_SELF?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a></td><td>$myytavissa</td></tr>";
				}
			}

			echo "</table>";
			echo "</td>";

			// aika karseeta, mutta katotaan voidaanko tällästä optiota näyttää yks tosi firma specific juttu
			$query = "describe yhteensopivuus_tuote";
			$res = mysql_query($query);

			if (mysql_error() == "" and file_exists("yhteensopivuus_tuote.php")) {

				$query = "select count(*) countti from yhteensopivuus_tuote where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
				$yhtresult = mysql_query($query) or pupe_error($query);
				$yhtrow = mysql_fetch_array($yhtresult);

				if ($yhtrow['countti'] > 0) {
					echo "<td class='back' valign='top' align='left'>";
					echo "<table>";
					echo "<tr><th>".t("Yhteensopivuudet")."</th></tr>";

					echo "<tr><td><a href='yhteensopivuus_tuote.php?tee=etsi&tuoteno=$tuoteno'>Siirry tuotteen yhteensopivuuksiin</a></td></tr>";

					echo "</table>";
					echo "</td>";
				}
			}

			echo "<tr></table>";

			// Varastosaldot ja paikat
			echo "<table>";
			echo "<tr>";
			echo "<td class='back' valign='top' align='left'>";

			if ($yhtiorow["saldo_kasittely"] == "T") {
				$aikalisa = date("Y-m-d");
			}
			else {
				$aikalisa = "";
			}

			if ($tuoterow["ei_saldoa"] == '') {
				// Saldot
				echo "<table>";
				echo "<tr><th>".t("Varasto")."</th><th>".t("Varastopaikka")."</th><th>".t("Saldo")."</th><th>".t("Hyllyssä")."</th><th>".t("Myytävissä")."</th></tr>";

				$kokonaissaldo = 0;
				$kokonaishyllyssa = 0;
				$kokonaismyytavissa = 0;

				//saldot per varastopaikka
				$query = "	SELECT tuotepaikat.*,
							varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi,
							concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
				 			FROM tuotepaikat
							JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
							and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
							and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
							WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
							and tuotepaikat.tuoteno = '$tuoteno'
							ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) > 0) {
					while ($saldorow = mysql_fetch_array ($sresult)) {

						list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($saldorow["tuoteno"], '', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], '', $aikalisa);

						//summataan kokonaissaldoa
						$kokonaissaldo += $saldo;
						$kokonaishyllyssa += $hyllyssa;
						$kokonaismyytavissa += $myytavissa;

						echo "<tr>
								<td>$saldorow[nimitys] $saldorow[tyyppi]</td>
								<td>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</td>
								<td align='right'>".sprintf("%.2f", $saldo)."</td>
								<td align='right'>".sprintf("%.2f", $hyllyssa)."</td>
								<td align='right'>".sprintf("%.2f", $myytavissa)."</td>
								</tr>";
					}
				}

				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, "ORVOT", '', '', '', '', '', '', '', $aikalisa);

				if ($saldo != 0) {
					echo "<tr><td>".t("Tuntematon")."</td><td>?</td>";
					echo "<td align='right'>".sprintf("%.2f", $saldo)."</td>
							<td align='right'>".sprintf("%.2f", $hyllyssa)."</td>
							<td align='right'>".sprintf("%.2f", $myytavissa)."</td>
							</tr>";

					//summataan kokonaissaldoa
					$kokonaissaldo += $saldo;
					$kokonaishyllyssa += $hyllyssa;
					$kokonaismyytavissa += $myytavissa;
				}

				echo "<tr>
						<th colspan='2'>".t("Yhteensä")."</th>
						<td align='right'>".sprintf("%.2f", $kokonaissaldo)."</td>
						<td align='right'>".sprintf("%.2f", $kokonaishyllyssa)."</td>
						<td align='right'>".sprintf("%.2f", $kokonaismyytavissa)."</td>
						</tr></td>";

				echo "</table>";

				$kokonaissaldo_tapahtumalle = $kokonaissaldo;

				// katsotaan onko tälle tuotteelle yhtään sisäistä toimittajaa ja että toimittajan tiedoissa on varmasti kaikki EDI mokkulat päällä ja oletusvienti on jotain vaihto-omaisuutta
				$query = "	select tyyppi_tieto, liitostunnus
							from tuotteen_toimittajat, toimi
							where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
							and tuotteen_toimittajat.tuoteno = '$tuoteno'
							and toimi.yhtio         = tuotteen_toimittajat.yhtio
							and toimi.tunnus        = tuotteen_toimittajat.liitostunnus
							and toimi.tyyppi        = 'S'
							and toimi.tyyppi_tieto != ''
							and toimi.edi_palvelin != ''
							and toimi.edi_kayttaja != ''
							and toimi.edi_salasana != ''
							and toimi.edi_polku    != ''
							and toimi.oletus_vienti in ('C','F','I')";
				$kres  = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($kres) > 0) {
					echo "<td class='back' valign='top' align='left'><table>";
					echo "<tr><th>".t("Suoratoimitus Yhtiö/Varasto")."</th><th>".t("Saldo")."</th></tr>";

					$kokonaissaldo = 0;
					$firmanimi = '';

					while ($superrow = mysql_fetch_array($kres)) {
						$query = "	select yhtio.nimi, yhtio.yhtio, yhtio.tunnus, varastopaikat.tunnus, varastopaikat.nimitys, hyllyalue, hyllynro, hyllyvali, hyllytaso, alkuhyllyalue, loppuhyllyalue, alkuhyllynro, loppuhyllynro, sum(saldo) saldo
									from tuotepaikat
									join yhtio on yhtio.yhtio=tuotepaikat.yhtio
									join varastopaikat on tuotepaikat.yhtio = varastopaikat.yhtio
									and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
									and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
									where tuotepaikat.yhtio = '$superrow[tyyppi_tieto]'
									and tuoteno = '$tuoteno'
									and varastopaikat.tyyppi = ''
									group by 1,2,3,4
									order by 5";
						$kres2  = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($kres2) > 0) {

							while ($krow  = mysql_fetch_array($kres2)) {
								// katotaan ennakkopoistot toimittavalta yritykseltä
								$query = "	select sum(varattu) varattu
											from tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
											where yhtio='$krow[yhtio]' and
											tyyppi='L' and
											varattu>0 and
											tuoteno='$tuoteno'
											and concat(rpad(upper('$krow[alkuhyllyalue]')  ,5,'0'),lpad(upper('$krow[alkuhyllynro]')  ,5,'0')) <= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))
											and concat(rpad(upper('$krow[loppuhyllyalue]') ,5,'0'),lpad(upper('$krow[loppuhyllynro]') ,5,'0')) >= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))";
								$krtre = mysql_query($query) or pupe_error($query);
								$krtur = mysql_fetch_array($krtre);

								// sitten katotaan ollaanko me jo varattu niitä JT rivejä toimittajalta
								$query =	"select sum(jt) varattu
											from tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
											where yhtio='$kukarow[yhtio]' and tyyppi='L' and laskutettuaika='0000-00-00' and var='S'
											and tuoteno='$tuoteno' and tilaajanrivinro='$superrow[liitostunnus]'
											and hyllyalue = '$krow[hyllyalue]' and hyllynro = '$krow[hyllynro]' and hyllyvali = '$krow[hyllyvali]' and hyllytaso = '$krow[hyllytaso]'";
								$krtre = mysql_query($query) or pupe_error($query);
								$krtu2 = mysql_fetch_array($krtre);

								// katotaan tuotteen saldo
								$saldo = sprintf("%.02f",$krow["saldo"] - $krtur["varattu"] - $krtu2["varattu"]);

								echo "<tr><td>$krow[nimi]/$krow[nimitys]</td>";
								echo "<td align='right'>$saldo</td></tr>";

								$firmanimi = $krow['nimi'];

								$kokonaissaldo += $saldo;
							}
						}
					}
					echo "<tr><th>".t("Suoratoimitettavissa Yhteensä")."</th><td align='right'>".sprintf("%.02f",$kokonaissaldo)."</td></tr>";

					echo "</table></td></tr>";
				}
			}

			echo "</td>";
			echo "</tr><tr><td class='back' valign='top' align='left' colspan='2'>";


			// Tilausrivit tälle tuotteelle
			$query = "	SELECT lasku.nimi, lasku.tunnus, (tilausrivi.varattu+tilausrivi.jt) kpl,
						if(tilausrivi.tyyppi!='O', tilausrivi.kerayspvm, tilausrivi.toimaika) kerayspvm,
						varastopaikat.nimitys varasto, tilausrivi.tyyppi, lasku.laskunro, lasku.tilaustyyppi, tilausrivi.var
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						JOIN lasku use index (PRIMARY) ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
						LEFT JOIN varastopaikat ON varastopaikat.yhtio = lasku.yhtio and varastopaikat.tunnus = lasku.varasto
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi in ('L','E','O','G','V','W')
						and tilausrivi.tuoteno = '$tuoteno'
						and tilausrivi.laadittu > '0000-00-00 00:00:00'
						and tilausrivi.laskutettuaika = '0000-00-00'
						and (tilausrivi.varattu != 0 or jt != 0)
						and tilausrivi.var not in ('P')
						ORDER BY kerayspvm";
			$jtresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($jtresult) != 0) {

				// Avoimet rivit
				echo "<br><table>";

				echo "<tr>
						<th>".t("Asiakas/Toimittaja")."</th>
						<th>".t("Tilaus/Keikka")."</th>
						<th>".t("Tyyppi")."</th>
						<th>".t("Toimaika")."</th>
						<th>".t("Kpl")."</th>
						<th>".t("Myytävissä")."</th>
						</tr>";

				while ($jtrow = mysql_fetch_array($jtresult)) {

					$tyyppi = "";
					$merkki = "";
					$keikka = "";

					if ($jtrow["tyyppi"] == "O") {
						$tyyppi = t("Ostotilaus");
						$merkki = "+";

						if ($jtrow["laskunro"] > 0) {
							$keikka = " / ".$jtrow["laskunro"];
						}
					}
					elseif($jtrow["tyyppi"] == "E") {
						$tyyppi = t("Ennakkotilaus");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "G") {
						$tyyppi = t("Varastosiirto");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "V") {
						$tyyppi = t("Kulutus");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "L" and $jtrow["var"] == "J") {
						$tyyppi = t("Jälkitoimitus");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "L" and $jtrow["kpl"] > 0) {
						$tyyppi = t("Myynti");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "L" and $jtrow["kpl"] < 0) {
						$tyyppi = t("Hyvitys");
						$merkki = "+";
					}
					elseif($jtrow["tyyppi"] == "W" and $jtrow["tilaustyyppi"] == "W") {
						$tyyppi = t("Valmistus");
						$merkki = "+";
					}
					elseif($jtrow["tyyppi"] == "W" and $jtrow["tilaustyyppi"] == "V") {
						$tyyppi = t("Asiakkaallevalmistus");
						$merkki = "+";
					}

					if($jtrow["varasto"] != "") {
						$tyyppi = $tyyppi." - ".$jtrow["varasto"];
					}

					list(, , $myyta) = saldo_myytavissa($tuoteno, "KAIKKI", '', '', '', '', '', '', '', $jtrow["kerayspvm"]);

					echo "<tr>
							<td>$jtrow[nimi]</td>
							<td><a href='$PHP_SELF?tuoteno=$tuoteno&tee=NAYTATILAUS&tunnus=$jtrow[tunnus]'>$jtrow[tunnus]</a>$keikka</td>
							<td>$tyyppi</td>
							<td>".tv1dateconv($jtrow["kerayspvm"])."</td>
							<td align='right'>$merkki".abs($jtrow["kpl"])."</td>
							<td align='right'>".sprintf('%.2f', $myyta)."</td>
							</tr>";
				}

				echo "</table>";
			}

			echo "</td>";
			echo "</tr>";
			echo "</table><br>";

			//myynnit
			$edvuosi  = date('Y')-1;
			$taavuosi = date('Y');

			$query = "	SELECT
						sum(if(laskutettuaika >= date_sub(now(),interval 30 day), rivihinta,0))	summa30,
						sum(if(laskutettuaika >= date_sub(now(),interval 30 day), kate,0))  	kate30,
						sum(if(laskutettuaika >= date_sub(now(),interval 30 day), kpl,0))  		kpl30,
						sum(if(laskutettuaika >= date_sub(now(),interval 90 day), rivihinta,0))	summa90,
						sum(if(laskutettuaika >= date_sub(now(),interval 90 day), kate,0))		kate90,
						sum(if(laskutettuaika >= date_sub(now(),interval 90 day), kpl,0))		kpl90,
						sum(if(YEAR(laskutettuaika) = '$taavuosi', rivihinta,0))	summaVA,
						sum(if(YEAR(laskutettuaika) = '$taavuosi', kate,0))		kateVA,
						sum(if(YEAR(laskutettuaika) = '$taavuosi', kpl,0))		kplVA,
						sum(if(YEAR(laskutettuaika) = '$edvuosi', rivihinta,0))	summaEDV,
						sum(if(YEAR(laskutettuaika) = '$edvuosi', kate,0))		kateEDV,
						sum(if(YEAR(laskutettuaika) = '$edvuosi', kpl,0))		kplEDV
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						WHERE yhtio='$kukarow[yhtio]'
						and tyyppi='L'
						and tuoteno='$tuoteno'
						and laskutettuaika >= '$edvuosi-01-01'";
			$result3 = mysql_query($query) or pupe_error($query);
			$lrow = mysql_fetch_array($result3);

			// tulostetaan myynnit
			echo "<table>";
			echo "<tr>
					<th>".t("Myynti").":</th>
					<th>".t("Edelliset 30pv")."</th>
					<th>".t("Edelliset 90pv")."</th>
					<th>".t("Kauden alusta")."</th>
					<th>".t("Vuosi")." $edvuosi</th>
					</tr>";

			echo "<tr><th align='left'>".t("Liikevaihto").":</th>
					<td align='right' nowrap>$lrow[summa30] $yhtiorow[valkoodi]</td>
					<td align='right' nowrap>$lrow[summa90] $yhtiorow[valkoodi]</td>
					<td align='right' nowrap>$lrow[summaVA] $yhtiorow[valkoodi]</td>
					<td align='right' nowrap>$lrow[summaEDV] $yhtiorow[valkoodi]</td></tr>";

			echo "<tr><th align='left'>".t("Myykpl").":</th>
					<td align='right' nowrap>$lrow[kpl30] ".t("KPL")."</td>
					<td align='right' nowrap>$lrow[kpl90] ".t("KPL")."</td>
					<td align='right' nowrap>$lrow[kplVA] ".t("KPL")."</td>
					<td align='right' nowrap>$lrow[kplEDV] ".t("KPL")."</td></tr>";

			echo "<tr><th align='left'>".t("Kate").":</th>
					<td align='right' nowrap>$lrow[kate30] $yhtiorow[valkoodi]</td>
					<td align='right' nowrap>$lrow[kate90] $yhtiorow[valkoodi]</td>
					<td align='right' nowrap>$lrow[kateVA] $yhtiorow[valkoodi]</td>
					<td align='right' nowrap>$lrow[kateEDV] $yhtiorow[valkoodi]</td></tr>";

			echo "<tr><th align='left'>".t("Katepros").":</th>";

			if ($lrow["summa30"] > 0)
				$kate30pros = round($lrow["kate30"]/$lrow["summa30"]*100,2);

			if ($lrow["summa90"] > 0)
				$kate90pros = round($lrow["kate90"]/$lrow["summa90"]*100,2);

			if ($lrow["summaVA"] > 0)
				$kateVApros = round($lrow["kateVA"]/$lrow["summaVA"]*100,2);

			if ($lrow["summaEDV"] > 0)
				$kateEDVpros = round($lrow["kateEDV"]/$lrow["summaEDV"]*100,2);

			echo "<td align='right' nowrap>$kate30pros %</td>";
			echo "<td align='right' nowrap>$kate90pros %</td>";
			echo "<td align='right' nowrap>$kateVApros %</td>";
			echo "<td align='right' nowrap>$kateEDVpros %</td></tr>";

			echo "</table><br>";

			if ($tuoterow["sarjanumeroseuranta"] != "") {
				$query	= "	SELECT sarjanumeroseuranta.*, tilausrivi_osto.tunnus, if(tilausrivi_osto.rivihinta=0 and tilausrivi_osto.tyyppi='L', tilausrivi_osto.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi_osto.alv<500, (1+tilausrivi_osto.alv/100), 1) * if(tilausrivi_osto.netto='N', (1-tilausrivi_osto.ale/100), (1-(tilausrivi_osto.ale+lasku_osto.erikoisale-(tilausrivi_osto.ale*lasku_osto.erikoisale/100))/100)), if(tilausrivi_osto.rivihinta!=0 and tilausrivi_osto.kpl!=0, tilausrivi_osto.rivihinta/tilausrivi_osto.kpl, 0)) ostosumma,
							tilausrivi_osto.nimitys nimitys
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.uusiotunnus
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno = '$tuoterow[tuoteno]'
							and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
							and tilausrivi_osto.laskutettuaika != '0000-00-00'";
				$sarjares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sarjares) > 0) {
					echo "<table>";
					echo "<tr><th colspan='3'>".t("Varasto").":</th></tr>";
					echo "<tr><th>".t("Nimitys")."</th>";
					echo "<th>".t("Sarjanumero")."</th>";
					echo "<th>".t("Ostohinta")."</th></tr>";

					while($sarjarow = mysql_fetch_array($sarjares)) {
						echo "<tr><td>$sarjarow[nimitys]</td><td><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$tuoterow[tuoteno]&sarjanumero_haku=$sarjarow[sarjanumero]'>$sarjarow[sarjanumero]</a></td><td align='right'>".sprintf('%.2f', $sarjarow["ostosumma"])."</td></tr>";
					}

					echo "</table><br>";
				}
			}

			// Varastotapahtumat
			echo "<table>";
			echo "<form action='$PHP_SELF#Tapahtumat' method='post'>";

			if ($historia == "") $historia=1;
			$chk[$historia] = "SELECTED";

			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";

			echo "<a href='#' name='Tapahtumat'>";

			echo "<tr><th>".t("Tuotehistoria").":</th>";
			echo "<th colspan='2'>".t("Näytä tapahtumat").":</th>";
			echo "<th colspan='2'>";
			echo "<select name='historia' onchange='submit();'>'";
			echo "<option value='1' $chk[1]> ".t("20 viimeisintä")."</option>";
			echo "<option value='2' $chk[2]> ".t("Tilivuoden alusta")."</option>";
			echo "<option value='3' $chk[3]> ".t("Edellinen tilivuosi")."</option>";
			echo "</select>";
			echo "</th>";


			if ($tapahtumalaji == "laskutus") 			$sel1="SELECTED";
			if ($tapahtumalaji == "tulo") 				$sel2="SELECTED";
			if ($tapahtumalaji == "valmistus") 			$sel3="SELECTED";
			if ($tapahtumalaji == "siirto") 			$sel4="SELECTED";
			if ($tapahtumalaji == "kulutus") 			$sel5="SELECTED";
			if ($tapahtumalaji == "Inventointi") 		$sel6="SELECTED";
			if ($tapahtumalaji == "Epäkurantti") 		$sel7="SELECTED";
			if ($tapahtumalaji == "poistettupaikka") 	$sel8="SELECTED";
			if ($tapahtumalaji == "uusipaikka") 		$sel9="SELECTED";

			echo "<th colspan='2'>".t("Tapahtumalaji").":</th>";
			echo "<th>";
			echo "<select name='tapahtumalaji' onchange='submit();'>'";
			echo "<option value=''>".t("Näytä kaikki")."</option>";
			echo "<option value='laskutus' $sel1>".t("Laskutukset")."</option>";
			echo "<option value='tulo' $sel2>".t("Tulot")."</option>";
			echo "<option value='valmistus' $sel3>".t("Valmistukset")."</option>";
			echo "<option value='siirto' $sel4>".t("Siirrot")."</option>";
			echo "<option value='kulutus' $sel5>".t("Kulutukset")."</option>";
			echo "<option value='Inventointi' $sel6>".t("Inventoinnit")."</option>";
			echo "<option value='Epäkurantti' $sel7>".t("Epäkuranttiusmerkinnät")."</option>";
			echo "<option value='poistettupaikka' $sel8>".t("Poistetut tuotepaikat")."</option>";
			echo "<option value='uusipaikka' $sel9>".t("Perustetut tuotepaikat")."</option>";
			echo "</select>";
			echo "</th>";

			echo "<tr>";
			echo "<th>".t("Käyttäjä@Pvm")."</th>";
			echo "<th>".t("Tyyppi")."</th>";
			echo "<th>".t("Kpl")."</th>";
			echo "<th>".t("Kplhinta")."</th>";
			echo "<th>".t("Kehahinta")."</th>";
			echo "<th>".t("Arvo")."</th>";
			echo "<th>".t("Var.Arvo")."</th>";
			echo "<th>".t("Selite")."";

			echo "</th></form>";
			echo "</tr>";


			//tapahtumat
			if ($historia == '2') {
				$maara = "";
				$ehto  = " and tapahtuma.laadittu > '$yhtiorow[tilikausi_alku]' ";
			}
			elseif ($historia == '3') {
				$maara = "";
				$ehto  = " and tapahtuma.laadittu >= date_sub('$yhtiorow[tilikausi_alku]', interval 12 month) and tapahtuma.laadittu <= '$yhtiorow[tilikausi_alku]' ";
			}
			else {
				$maara = "LIMIT 20";
				$ehto  = " and tapahtuma.laadittu >= date_sub(now(), interval 6 month) ";
			}

			$query = "	SELECT concat_ws('@', tapahtuma.laatija, tapahtuma.laadittu) kuka, tapahtuma.laji, tapahtuma.kpl, tapahtuma.kplhinta, tapahtuma.hinta,
						if(tapahtuma.laji in ('tulo','valmistus'), tapahtuma.kplhinta, tapahtuma.hinta)*tapahtuma.kpl arvo, tapahtuma.selite, lasku.tunnus laskutunnus
						FROM tapahtuma use index (yhtio_tuote_laadittu)
						LEFT JOIN tilausrivi use index (primary) ON tilausrivi.yhtio=tapahtuma.yhtio and tilausrivi.tunnus=tapahtuma.rivitunnus
						LEFT JOIN lasku use index (primary) ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
						and tapahtuma.tuoteno = '$tuoteno'
						$ehto
						ORDER BY tapahtuma.laadittu desc $maara";
			$qresult = mysql_query($query) or pupe_error($query);

			$vararvo_nyt = sprintf('%.2f',$kokonaissaldo_tapahtumalle*$tuoterow["kehahin"]);

			echo "<tr>
					<td colspan='4'>".t("Varastonarvo nyt").":</td>
					<td align='right'>$tuoterow[kehahin]</td>
					<td align='right'>$vararvo_nyt</td>
					<td align='right'>".sprintf('%.2f',$kokonaissaldo_tapahtumalle*$tuoterow["kehahin"])."</td>
					<td></td>
					</tr>";

			while ($prow = mysql_fetch_array ($qresult)) {

				$vararvo_nyt -= $prow["arvo"];

				if ($tapahtumalaji == "" or strtoupper($tapahtumalaji)==strtoupper($prow["laji"])) {
					echo "<tr>";
					echo "<td nowrap>$prow[kuka]</td>";
					echo "<td nowrap>";

					if ($prow["laji"] == "laskutus" and $prow["laskutunnus"] != "") {
						echo "<a href='raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$prow[laskutunnus]'>".t("$prow[laji]")."</a>";
					}
					elseif ($prow["laji"] == "tulo" and $prow["laskutunnus"] != "") {
						echo "<a href='raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus=$prow[laskutunnus]'>".t("$prow[laji]")."</a>";
					}
					else {
						echo t("$prow[laji]");
					}

					echo "</td>";

					echo "<td nowrap align='right'>$prow[kpl]</td>";
					echo "<td nowrap align='right'>$prow[kplhinta]</td>";
					echo "<td nowrap align='right'>$prow[hinta]</td>";
					echo "<td nowrap align='right'>".sprintf('%.2f', $prow["arvo"])."</td>";
					echo "<td nowrap align='right'>".sprintf('%.2f', $vararvo_nyt)."</td>";
					echo "<td>$prow[selite]</td>";
					echo "</tr>";
				}
			}
			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Yhtään tuotetta ei löytynyt")."!<br></font>";
		}
		$tee = '';
	}
	if ($ulos != "") {
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<table><tr>";
			echo "<td>Valitse listasta:</td>";
			echo "<td>$ulos</td>";
			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
			echo "</tr></table>";
			echo "</form>";
	}

	require ("inc/footer.inc");
?>
