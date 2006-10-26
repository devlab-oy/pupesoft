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
					HAVING status != 'P' or saldo > 0
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
						JOIN tuote ON tuote.yhtio=tuotteen_toimittajat.yhtio and tuote.tuoteno=tuotteen_toimittajat.tuoteno and tuote.status != 'P'
						LEFT JOIN tuotepaikat ON tuotepaikat.yhtio=tuotteen_toimittajat.yhtio and tuotepaikat.tuoteno=tuotteen_toimittajat.tuoteno
						WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
						and tuotteen_toimittajat.toim_tuoteno = '$tuoteno'
						GROUP BY tuotteen_toimittajat.tuoteno
						HAVING saldo > 0 or status != 'P'
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

		$query = "	SELECT tuote.*, date_format(muutospvm, '%Y-%m-%d') muutos, date_format(luontiaika, '%Y-%m-%d') luonti,
					group_concat(distinct tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '<br>') toimittaja,
					group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '<br>') osto_era,
					group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno,
					group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '<br>') tuotekerroin,
					group_concat(distinct concat_ws(' ',tuotteen_toimittajat.ostohinta,tuotteen_toimittajat.valuutta) order by tuotteen_toimittajat.tunnus separator '<br>') ostohinta
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

		if ($tuoterow["tuoteno"] != "" and ($tuoterow["status"] != "P" or $salro["saldo"] != 0)) {

			// laitetaan kehahin oikein...
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

			//eka laitetaan tuotteen yleiset (aika staattiset) tiedot
			echo "<table>";

			//1
			echo "<tr><th>".t("Tuotenumero")."</th><th>".t("Yksikkö")."</th><th colspan='4'>".t("Nimitys")."</th>";
			echo "<tr><td>$tuoterow[tuoteno]</td><td>$tuoterow[yksikko]</td><td colspan='4'>".substr($tuoterow["nimitys"],0,100)."</td></tr>";

			//2
			echo "<tr><th>".t("Osasto/try")."</th><th>".t("Toimittaja")."</th><th>".t("Aleryhmä")."</th><th>".t("Tähti")."</th><th>".t("Perusalennus")."</th><th>".t("VAK")."</th></tr>";
			echo "<td>$tuoterow[osasto]/$tuoterow[try]</td><td>$tuoterow[toimittaja]</td>
					<td>$tuoterow[aleryhma]</td><td>$tuoterow[tahtituote]</td><td>$peralrow[alennus]%</td><td>$tuoterow[vakkoodi]</td></tr>";

			//3
			echo "<tr><th>".t("Toimtuoteno")."</th><th>".t("Myyntihinta")."</th><th>".t("Netto/Ovh")."</th><th>".t("Ostohinta")."</th><th>".t("Kehahinta")."</th><th>".t("Vihahinta")."</th>";
			echo "<tr><td>$tuoterow[toim_tuoteno]</td>
					<td>$tuoterow[myyntihinta]</td><td>$tuoterow[nettohinta]/$tuoterow[myymalahinta]</td><td>$tuoterow[ostohinta]</td>
					<td>$tuoterow[kehahin]</td><td>$tuoterow[vihahin] $tuoterow[vihapvm]</td></tr>";

			//4
			echo "<tr><th>".t("Hälyraja")."</th><th>".t("Tilerä")."</th><th>".t("Toierä")."</th><th>".t("Kerroin")."</th><th>".t("Tarrakerroin")."</th><th>".t("Tarrakpl")."</th>";
			echo "<tr><td>$tuoterow[halytysraja]</td>
					<td>$tuoterow[osto_era]</td><td>$tuoterow[myynti_era]</td><td>$tuoterow[tuotekerroin]</td>
					<td>$tuoterow[tarrakerroin]</td><td>$tuoterow[tarrakpl]</td></tr>";

			//5
			echo "<tr><th>".t("Tullinimike")."</th><th colspan='4'>".t("Tullinimikkeen kuvaus")."</th><th>".t("Toinen paljous")."</th></tr>";
			echo "<tr><td>$tullirow1[cn]</td><td colspan='4'>".substr($tullirow3['dm'],0,20)." - ".substr($tullirow2['dm'],0,20)." - ".substr($tullirow1['dm'],0,20)."</td><td>$tullirow1[su]</td></tr>";


			//6
			echo "<tr><th>".t("Luontipvm")."</th><th>".t("Muutospvm")."</th><th>".t("Epäkurantti1pvm")."</th><th>".t("Epäkurantti2pvm")."</th><th colspan='2'>".t("Tuotteen kuvaus")."</th></tr>";
			echo "<tr><td>$tuoterow[luonti]</td><td>$tuoterow[muutos]</td><td>$tuoterow[epakurantti1pvm]</td><td>$tuoterow[epakurantti2pvm]</td><td colspan='2'>$tuoterow[kuvaus]&nbsp;</td></tr>";


			//7
			echo "<tr><th>".t("Info")."</th><th colspan='5'>".t("Avainsanat")."</th></tr>";
			echo "<tr><td>$tuoterow[muuta]&nbsp;</td><td colspan='5'>$tuoterow[lyhytkuvaus]</td></tr>";


			echo "</table><br>";

			echo "<td class='back' valign='top' align='left'>";
			echo "<table>";
			echo "<th>".t("Korvaavat")."</th><th>".t("Kpl")."</th>";

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
					//hateaan vielä korvaaville niiden saldot.
					//saldot per varastopaikka
					$query = "select sum(saldo) alkusaldo from tuotepaikat where tuoteno='$row[tuoteno]' and yhtio='$kukarow[yhtio]'";
					$alkuresult = mysql_query($query) or pupe_error($query);
					$alkurow = mysql_fetch_array($alkuresult);

					//ennakkopoistot
					$query = "	SELECT sum(varattu) varattu
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
								WHERE tyyppi in ('L','G','V')
								and yhtio    = '$kukarow[yhtio]'
								and tuoteno  = '$row[tuoteno]'
								and varattu	<> 0";
					$varatutresult = mysql_query($query) or pupe_error($query);
					$varatutrow = mysql_fetch_array($varatutresult);

					$vapaana = $alkurow["alkusaldo"] - $varatutrow["varattu"];

					echo "<tr><td><a href='$PHP_SELF?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a></td><td>$vapaana</td></tr>";
				}
			}

			echo "</table>";
			echo "</td><br>";

			// Varastosaldot ja paikat
			echo "<table>";
			echo "<tr>";
			echo "<td class='back' valign='top' align='left'>";

			if ($tuoterow["ei_saldoa"] == '') {
				//saldot
				echo "<table>";
				echo "<tr><th>".t("Varasto")."</th><th>".t("Varastopaikka")."</th><th>".t("Saldo")."</th><th>".t("Hyllyssä")."</th><th>".t("Myytävissä")."</th></tr>";

				$kokonaissaldo = 0;
				$kokonaishyllyssa = 0;
				$kokonaismyytavissa = 0;

				//saldot per varastopaikka
				$query = "select * from tuotepaikat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]' order by hyllyalue, hyllynro, hyllyvali, hyllytaso";
				$sresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sresult) > 0) {
					while ($saldorow = mysql_fetch_array ($sresult)) {

						//jo kerätyt mutta ei laskutettu
						$query = "	SELECT
									ifnull(sum(if(tilausrivi.keratty!='', tilausrivi.varattu, 0)),0) keratty,
									ifnull(sum(if(tilausrivi.keratty ='', tilausrivi.varattu, 0)),0) ennpois
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
									WHERE yhtio 	= '$kukarow[yhtio]'
									and tyyppi 		in ('L','G','V')
									and tuoteno		= '$tuoteno'
									and varattu    <> 0
									and hyllyalue   = '$saldorow[hyllyalue]'
                                   	and hyllynro    = '$saldorow[hyllynro]'
									and hyllyvali   = '$saldorow[hyllyvali]'
									and hyllytaso   = '$saldorow[hyllytaso]'";
						$kerresult = mysql_query($query) or pupe_error($query);
						$kerrow = mysql_fetch_array ($kerresult);

						$hyllyssa = $saldorow['saldo'] - $kerrow['keratty'];
						$myytavissa = $saldorow['saldo'] - $kerrow["ennpois"] - $kerrow['keratty'];

						//summataan kokonaissaldoa
						$kokonaissaldo += $saldorow["saldo"];
						$kokonaishyllyssa += $hyllyssa;
						$kokonaismyytavissa += $myytavissa;

						// haetaan varaston nimi
						$query = "	SELECT *
									FROM varastopaikat
									WHERE
									concat(rpad(upper(alkuhyllyalue) ,3,'0'),lpad(alkuhyllynro ,2,'0')) <= concat(rpad(upper('$saldorow[hyllyalue]') ,3,'0'),lpad('$saldorow[hyllynro]' ,2,'0'))
									and concat(rpad(upper(loppuhyllyalue) ,3,'0'),lpad(loppuhyllynro ,2,'0')) >= concat(rpad(upper('$saldorow[hyllyalue]') ,3,'0'),lpad('$saldorow[hyllynro]' ,2,'0'))
									and yhtio = '$kukarow[yhtio]'";
						$varcheckres = mysql_query($query) or pupe_error($query);
						$varcheckrow = mysql_fetch_array($varcheckres);

						if ($varcheckrow["tyyppi"] != "") {
							$vartyyppi = "($varcheckrow[tyyppi])";
						}
						else {
							$vartyyppi = "";
						}

						echo "<tr><td>$varcheckrow[nimitys] $vartyyppi</td><td>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</td>";
						echo "<td align='right'>$saldorow[saldo]</td><td align='right'>".sprintf("%.2f",$hyllyssa)."</td><td align='right'>".sprintf("%.2f",$myytavissa)."</td></tr>";
					}
				}

				$orvot = saldo_myytavissa($tuoteno, "ORVOT");

				if ($orvot != 0) {
					echo "<tr><td>".t("Tuntematon")."</td><td>?</td>";
					echo "<td align='right'>0.00</td><td align='right'>0.00</td><td align='right'>".sprintf("%.2f",$orvot)."</td></tr>";
				}

				echo "<tr><th colspan='2'>".t("Yhteensä")."</th><td align='right'>".sprintf("%.2f",$kokonaissaldo)."</td><td align='right'>".sprintf("%.2f",$kokonaishyllyssa)."</td><td align='right'>".sprintf("%.2f",saldo_myytavissa($tuoteno, "KAIKKI"))."</td></tr></td>";

				echo "</table>";

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
									and concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0')) >= concat(rpad(upper(alkuhyllyalue) ,3,'0'),lpad(alkuhyllynro ,2,'0'))
									and concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0')) <= concat(rpad(upper(loppuhyllyalue) ,3,'0'),lpad(loppuhyllynro ,2,'0'))
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
											and concat(rpad(upper(hyllyalue), 3, '0'),lpad(hyllynro, 2, '0')) >= concat(rpad(upper('$krow[alkuhyllyalue]'),  3, '0'),lpad(upper('$krow[alkuhyllynro]'),  2, '0'))
											and concat(rpad(upper(hyllyalue), 3, '0'),lpad(hyllynro, 2, '0')) <= concat(rpad(upper('$krow[loppuhyllyalue]'), 3, '0'),lpad(upper('$krow[loppuhyllynro]'), 2, '0'))";
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

					echo "</table>";
				}
			}

			echo "</td>";



			echo "</tr><tr><td class='back' valign='top' align='left' colspan='2'>";


			$query = "	SELECT tilausrivi.*, lasku.ytunnus, tilausrivi.varattu+tilausrivi.jt kpl, lasku.nimi, tilausrivi.toimaika, round((tilausrivi.varattu+tilausrivi.jt)*tilausrivi.hinta*(1-(tilausrivi.ale/100)),2) rivihinta
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						JOIN lasku use index (PRIMARY) ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi in ('L','E','O','G','V')
						and tilausrivi.tuoteno = '$tuoteno'
						and tilausrivi.laadittu > '0000-00-00 00:00:00'
						and tilausrivi.laskutettuaika = '0000-00-00'
						and (tilausrivi.varattu != 0 or jt != 0)
						and tilausrivi.var not in ('P')
						ORDER BY tyyppi, var";
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
						</tr>";

				// jtrivejä löytyi
				while ($jtrow = mysql_fetch_array($jtresult)) {

					$tyyppi = "";
					$merkki = "";
					$keikka = "";

					if ($jtrow["tyyppi"] == "O") {
						$tyyppi = t("Ostotilaus");
						$merkki = "+";

						$query = "	SELECT laskunro
									FROM lasku
									WHERE yhtio = '$kukarow[yhtio]'
									and tunnus='$jtrow[uusiotunnus]'";
						$keikkares = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($keikkares) > 0) {
							$keikkarow = mysql_fetch_array($keikkares);
							$keikka = " / ".$keikkarow["laskunro"];
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
						$tyyppi = t("Valmistus");
						$merkki = "-";
					}
					elseif($jtrow["tyyppi"] == "L" and $jtrow["var"] == "J") {
						$tyyppi = t("Jälkitoimitus");
						$merkki = "-";
					}
					elseif(($jtrow["tyyppi"] == "L" or $jtrow["tyyppi"] == "N") and $jtrow["varattu"] > 0) {
						$tyyppi = t("Myynti");
						$merkki = "-";
					}
					elseif(($jtrow["tyyppi"] == "L" or $jtrow["tyyppi"] == "N") and $jtrow["varattu"] < 0) {
						$tyyppi = t("Hyvitys");
						$merkki = "+";
					}
					echo "<tr>
							<td>$jtrow[nimi]</td>
							<td><a href='$PHP_SELF?tuoteno=$tuoteno&tee=NAYTATILAUS&tunnus=$jtrow[otunnus]'>$jtrow[otunnus]</a>$keikka</td>";
					echo "	<td>$tyyppi</td>
							<td>".substr($jtrow["toimaika"],0,10)."</td>
							<td>$merkki".abs($jtrow["kpl"])."</td>
							</tr>";
				}

				echo "</table>";
			}

			echo "</td>";
			echo "</tr>";
			echo "</table><br>";

			//myynnit
			$edvuosi = date('Y')-1;
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

			// Varastotapahtumat
			echo "<table>";
			echo "<form action='$PHP_SELF' method='post'>";

			if ($historia == "") $historia=1;
			$chk[$historia] = "CHECKED";

			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";



			echo "<tr><th>".t("Tuotehistoria").":</th>";

			echo "<th colspan='6'>
					<input type='radio' name='historia' value='1' onclick='submit()' $chk[1]> ".t("20 viimeisintä")."
					<input type='radio' name='historia' value='2' onclick='submit()' $chk[2]> ".t("Tilivuoden alusta")."
					<input type='radio' name='historia' value='3' onclick='submit()' $chk[3]> ".t("Lähes kaikki")."</th></tr>";

			echo "<tr>";
			echo "<th>".t("Käyttäjä@Pvm")."</th>";
			echo "<th>".t("Tyyppi")."</th>";
			echo "<th>".t("Kpl")."</th>";
			echo "<th>".t("Kplhinta")."</th>";
			echo "<th>".t("Kehahinta")."</th>";
			echo "<th>".t("Arvo")."</th>";
			echo "<th>".t("Selite")."";

			echo "</th></form>";
			echo "</tr>";


			//tapahtumat
			if ($historia == '1' or $historia == '') {
				$maara = "LIMIT 20";
				$ehto = ' and laadittu >= date_sub(now(), interval 6 month)';
			}
			if ($historia == '2') {
				$maara = "";
				$ehto = " and laadittu > '$yhtiorow[tilikausi_alku]'";
			}
			if ($historia == '3') {
				$maara = "LIMIT 2500";
				$ehto = "";
			}

			$query = "	SELECT concat_ws('@', laatija, laadittu) kuka, laji, kpl, kplhinta, hinta, if(laji in ('tulo','valmistus'), kplhinta, hinta)*kpl arvo, selite
						FROM tapahtuma use index (yhtio_tuote_laadittu)
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$tuoteno'
						and laadittu > '0000-00-00 00:00:00'
						$ehto
						ORDER BY laadittu desc $maara";
			$qresult = mysql_query($query) or pupe_error($query);

			while ($prow = mysql_fetch_array ($qresult)) {
				echo "<tr>";
				echo "<td nowrap>$prow[kuka]</td>";
				echo "<td nowrap>".t("$prow[laji]")."</td>";
				echo "<td nowrap>$prow[kpl]</td>";
				echo "<td nowrap>$prow[kplhinta]</td>";
				echo "<td nowrap>$prow[hinta]</td>";
				echo "<td nowrap>$prow[arvo]</td>";
				echo "<td>$prow[selite]</td>";
				echo "</tr>";
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
