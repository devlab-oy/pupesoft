<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Sarjanumeroidun tuotteen varastonarvomuutos")."</font><hr>";

	if ($oikeurow["paivitys"] != '1') { // Saako päivittää
		if ($uusi == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta lisätä tätä tietoa")."</b><br>";
			$uusi = '';
		}
		if ($del == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta poistaa tätä tietoa")."</b><br>";
			$del = '';
			$tunnus = 0;
		}
		if ($upd == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta muuttaa tätä tietoa")."</b><br>";
			$upd = '';
			$uusi = 0;
			$tunnus = 0;
			exit;
		}
	}


	// lukitaan tableja
	$query = "	LOCK TABLES tuotepaikat WRITE,
				tapahtuma WRITE,
				lasku WRITE,
				tiliointi WRITE,
				sanakirja WRITE,
				tilausrivi as tilausrivi_upd WRITE,
				tuote READ,
				tilausrivi READ,
				tuotteen_avainsanat READ,
				sarjanumeroseuranta WRITE,
				sarjanumeroseuranta_arvomuutos READ,
				tilausrivi as tilausrivi_myynti READ,
				tilausrivi as tilausrivi_osto READ,
				tuotepaikat as tt READ,
				avainsana as avainsana_kieli READ,
				tili READ";
	$result = pupe_query($query);

	//tuotteen varastostatus
	if ($tee == 'VALMIS') {

		$virhe = 0;

		if (count($tuote) > 0) {
			foreach($tuote as $i => $tuoteno) {
				if (count($sarjanumero_edarvo[$i]) > 0) {
					foreach ($sarjanumero_edarvo[$i] as $snro_tun => $edarvo) {

						$sarjanumero_uusiarvo[$i][$snro_tun] = str_replace(",", ".", $sarjanumero_uusiarvo[$i][$snro_tun]);

						if ($sarjanumero_uusiarvo[$i][$snro_tun] != '' and (float) $sarjanumero_uusiarvo[$i][$snro_tun] >= 0) {
						 	$edarvo = (float) $edarvo;
							$uuarvo = (float) $sarjanumero_uusiarvo[$i][$snro_tun];

							$ero = round($uuarvo - $edarvo, 2);

							if (abs($ero) != 0) {

								$query = "	SELECT tilausrivi_osto.tunnus, tilausrivi_osto.alv, tilausrivi_osto.tyyppi, round(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl, 2) ostohinta, tilausrivi_osto.kpl,
											tilausrivi_myynti.hyllyalue, tilausrivi_myynti.hyllynro, tilausrivi_myynti.hyllyvali, tilausrivi_myynti.hyllytaso
											FROM sarjanumeroseuranta
											LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
											LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
											WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
											and sarjanumeroseuranta.tuoteno	= '$tuoteno'
											and sarjanumeroseuranta.myyntirivitunnus != -1
											and ((tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') and tilausrivi_osto.laskutettuaika != '0000-00-00' and abs(tilausrivi_osto.kpl)=1)
											and sarjanumeroseuranta.tunnus = '$snro_tun'
											ORDER BY sarjanumero";
								$sarjares = pupe_query($query);

								if (mysql_num_rows($sarjares) == 1) {
									$sarjarow = mysql_fetch_assoc($sarjares);

									if ($sarjarow["alv"] >= 600) {
										$sarjarow["alv"] -= 600;
									}
									elseif ($sarjarow["alv"] >= 500) {
										$sarjarow["alv"] -= 500;
									}

									if ($yhtiorow["alv_kasittely"] == "" and $sarjarow["tyyppi"] == 'L' and $sarjarow["alv"] != 0) {
										$uuhinta = round($uuarvo * (1+$sarjarow["alv"]/100));
									}
									else {
										$uuhinta = $uuarvo;
									}

									$query = "	UPDATE tilausrivi as tilausrivi_upd
												SET hinta = '$uuhinta',
												rivihinta = if(tyyppi='O', $uuarvo, $uuarvo*-1)
												where yhtio	= '$kukarow[yhtio]'
												and tunnus	= '$sarjarow[tunnus]'";
									$result = pupe_query($query);

									if ($ero < 0) {
										$tkpl = -1;
										$tero = abs($ero);
									}

									else {
										$tkpl = 1;
										$tero = abs($ero);
									}

									$query = "	INSERT into tapahtuma set
												yhtio   	= '$kukarow[yhtio]',
												tuoteno 	= '$tuoteno',
												laji    	= 'Epäkurantti',
												kpl     	= '$tkpl',
												hinta   	= '$tero',
												kplhinta	= '$tero',
												hyllyalue	= '$sarjarow[hyllyalue]',
												hyllynro 	= '$sarjarow[hyllynro]',
												hyllyvali 	= '$sarjarow[hyllyvali]',
												hyllytaso 	= '$sarjarow[hyllytaso]',
												selite  	= '".t("Varastonarvon muutos").": $edarvo -> $uuarvo. $lisaselite',
												laatija    	= '$kukarow[kuka]',
												laadittu 	= now()";
									$result = pupe_query($query);
									$tapahtumaid = mysql_insert_id();

									$query = "	INSERT into lasku set
												yhtio      = '$kukarow[yhtio]',
												tapvm      = now(),
												tila       = 'X',
												laatija    = '$kukarow[kuka]',
												viite      = '$tapahtumaid',
												luontiaika = now()";
									$result = pupe_query($query);
									$laskuid = mysql_insert_id($link);

									// Tiliöidään ensisijaisesti varastonmuutos tilin oletuskustannuspaikalle
									list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varastonmuutos"]);

									// Toissijaisesti kokeillaan vielä varasto-tilin oletuskustannuspaikkaa
									list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varasto"], $kustp_ins, $kohde_ins, $projekti_ins);

									$query = "	INSERT into tiliointi set
												yhtio    = '$kukarow[yhtio]',
												ltunnus  = '$laskuid',
												tilino   = '$yhtiorow[varasto]',
												kustp    = '{$kustp_ins}',
												kohde	 = '{$kohde_ins}',
												projekti = '{$projekti_ins}',
												tapvm    = now(),
												summa    = '$ero',
												vero     = 0,
												lukko    = '',
												selite   = '".t("Varastonarvon muutos").": $edarvo -> $uuarvo',
												laatija  = '$kukarow[kuka]',
												laadittu = now()";
									$result = pupe_query($query);

									$query = "	INSERT into tiliointi set
												yhtio    = '$kukarow[yhtio]',
												ltunnus  = '$laskuid',
												tilino   = '$yhtiorow[varastonmuutos]',
												kustp    = '{$kustp_ins}',
												kohde	 = '{$kohde_ins}',
												projekti = '{$projekti_ins}',
												tapvm    = now(),
												summa    = $ero * -1,
												vero     = 0,
												lukko    = '',
												selite   = '".t("Varastonarvon muutos").": $edarvo -> $uuarvo',
												laatija  = '$kukarow[kuka]',
												laadittu = now()";
									$result = pupe_query($query);

									echo "<font class='message'>$tuoteno: ".t("Varastonarvon muutos").": $edarvo -> $uuarvo</font><br>";
								}
							}
						}
					}
				}
			}
		}
		echo "<br><br>";
		$tee = "INVENTOI";
	}


	if ($tee == 'INVENTOI') {

		//hakulause, tämä on sama kaikilla vaihtoehdoilla
		$select = " tuote.sarjanumeroseuranta, tuotepaikat.oletus, tuotepaikat.tunnus tptunnus, tuote.tuoteno, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuote.nimitys, tuote.yksikko, concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) varastopaikka, inventointiaika, tuotepaikat.saldo, tuotepaikat.inventointilista, tuotepaikat.inventointilista_aika, concat(lpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta";

		if ($tuoteno != "") {
			///* Inventoidaan tuotenumeron perusteella *///
			$kutsu = " ".t("Tuote")." $tuoteno ";

			$query = "	SELECT $select
						FROM tuote use index (tuoteno_index)
						JOIN tuotepaikat use index (tuote_index) USING (yhtio, tuoteno)
						WHERE tuote.yhtio 				= '$kukarow[yhtio]'
						and tuote.tuoteno				= '$tuoteno'
						and tuote.ei_saldoa				= ''
						and tuote.sarjanumeroseuranta 	= 'S'
						ORDER BY sorttauskentta, tuoteno";
			$saldoresult = pupe_query($query);

			if (mysql_num_rows($saldoresult) == 0) {
				echo "<font class='error'>".t("Tuote")." '$tuoteno' ".t("ei löydy!")." ".t("Onko tuote saldoton tuote")."? ".t("Onko tuotteella varastopaikka")."?</font><br><br>";
				$tee = '';
			}
		}
		else {
			echo "<font class='error'>".t("VIRHE: Tarkista syötetyt tiedot")."!</font><br><br>";
			$tee = '';
		}

		if ($tee == 'INVENTOI') {

			echo "<form name='inve' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='VALMIS'>";
			echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("Paikka")."/".t("Snro")."</th><th>".t("Varastonarvo")."</th><th>".t("Uusi varastonarvo")."</th>";
			echo "</tr>";

			while($tuoterow = mysql_fetch_assoc($saldoresult)) {

				$query = "	SELECT if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, '$tuoterow[nimitys]') nimitys, sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus, round(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl, 2) ostohinta, era_kpl
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							WHERE sarjanumeroseuranta.yhtio 	= '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno		= '$tuoterow[tuoteno]'
							and sarjanumeroseuranta.myyntirivitunnus	!= -1
							and (	(sarjanumeroseuranta.hyllyalue		= '$tuoterow[hyllyalue]'
									 and sarjanumeroseuranta.hyllynro 	= '$tuoterow[hyllynro]'
									 and sarjanumeroseuranta.hyllyvali 	= '$tuoterow[hyllyvali]'
									 and sarjanumeroseuranta.hyllytaso 	= '$tuoterow[hyllytaso]')
								 or ('$tuoterow[oletus]' != '' and
									(	SELECT tunnus
										FROM tuotepaikat tt
										WHERE sarjanumeroseuranta.yhtio = tt.yhtio and sarjanumeroseuranta.tuoteno = tt.tuoteno and sarjanumeroseuranta.hyllyalue = tt.hyllyalue
										and sarjanumeroseuranta.hyllynro = tt.hyllynro and sarjanumeroseuranta.hyllyvali = tt.hyllyvali and sarjanumeroseuranta.hyllytaso = tt.hyllytaso) is null))
							and ((tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') and tilausrivi_osto.laskutettuaika != '0000-00-00' and abs(tilausrivi_osto.kpl)=1)
							ORDER BY sarjanumero";
				$sarjares = pupe_query($query);

				echo "<tr>";
				echo "<td valign='top' class='spec'>$tuoterow[tuoteno]</td><td valign='top' class='spec' nowrap>".t_tuotteen_avainsanat($tuoterow, 'nimitys')."</td><td class='spec' valign='top'>$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]</td><td></td><td></td></tr>";

				if (mysql_num_rows($sarjares) > 0) {

					$sarjalaskk = 1;

					while($sarjarow = mysql_fetch_assoc($sarjares)) {
						echo "<tr>
								<td>$sarjalaskk.</td><td>$sarjarow[nimitys]</td><td>$sarjarow[sarjanumero]</td><td align='right'>$sarjarow[ostohinta]</td>
								<td>
								<input type='hidden' name='sarjanumero_edarvo[$tuoterow[tptunnus]][$sarjarow[tunnus]]' value='$sarjarow[ostohinta]'>
								<input type='text' size='15' name='sarjanumero_uusiarvo[$tuoterow[tptunnus]][$sarjarow[tunnus]]'>
								</td></tr>";

						$sarjalaskk++;
					}
				}

				echo "</td>";
				echo "<input type='hidden' name='tuote[$tuoterow[tptunnus]]' value='$tuoterow[tuoteno]'>";
				echo "</tr>";
			}

			echo "</table>";
			echo "<br><font class='message'>".t("Syötä selite:")."</font><br>";
			echo "<input type='text' size='50' name='lisaselite' value='$lisaselite'><br><br>";
			echo "<input type='submit' name='valmis' value='".t("Muuta varastonarvoa")."'>";
			echo "</form>";
		}
	}


	if ($tee == '') {

		$formi  = "inve";
		$kentta = "tuoteno";

		echo "<form name='inve' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='INVENTOI'>";
		echo "<br><table>";
		echo "<tr><th>".t("Tuotenumero:")."</th><td><input type='text' size='25' name='tuoteno'></td><td class='back'><input type='submit' name='valmis' value='".t("Jatka")."'></td></tr>";
		echo "</table>";
		echo "</form>";
		echo "<br><br>";

	}

	// lukitaan tableja
	$query = "unlock tables";
	$result = pupe_query($query);

	require ("inc/footer.inc");

?>