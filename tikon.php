<?php

	require('inc/parametrit.inc');

	if ($lataa_tiedosto == 1) {
		$filetxt = file_get_contents($file);
		echo $filetxt;
		exit;
	}

	// poimitaan kuluva päivä, raportin timestampille
	$today = date("Y-m-d");

	echo "<font class='head'>Siirto ulkoiseen kirjanpitoon</font><hr>";

	if ($kausi == "") {

		//Näytetään käyttöliittymä
		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>Anna kausi</th>";
		echo "<td><input type = 'text' name = 'kausi' size=8> Esim 2007-01</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<th>Summaa tapahtumat</th>";
		echo "<td><input type='checkbox' name='summataan' checked></td>";
		echo "</tr>";
		echo "</table>";

		echo "<br>";
		echo "<input type = 'submit' value = 'Valitse'>";
		echo "</form>";

		$formi = 'valinta';
		$kentta = 'kausi';

		require "inc/footer.inc";
		exit;
	}

	function teetietue ($yhtio, $tosite, $summa, $ltunnus, $tapvm, $tilino, $kustp, $projekti, $ytunnus, $nimi, $selite) {

		//Kustannuspaikan koodien haku
		$query = "SELECT nimi FROM kustannuspaikka WHERE yhtio = '$yhtio' and tunnus = '$kustp'";
		$vresult = mysql_query($query) or pupe_error("Kysely ei onnistu $query");
		$kustprow = mysql_fetch_array($vresult);

		//Projekti koodien haku
		$query = "SELECT nimi FROM kustannuspaikka WHERE yhtio = '$yhtio' and tunnus = '$projekti'";
		$vresult = mysql_query($query) or pupe_error("Kysely ei onnistu $query");
		$projprow = mysql_fetch_array($vresult);

		if ((int) $kustprow['nimi'] == 0) {
			 $kustprow['nimi'] = ""; 												//tsekataan ettei seurantakohteille  tule turhia etunollia
		}
		if ((int) $projprow['nimi'] == 0) {
			$projprow['nimi'] = ""; 												//tsekataan ettei seurantakohteille  tule turhia etunollia
		}

		$ulos = 'TKB';																//tietuetyyppi
		$ulos .= sprintf ('%-8s',  $tapvm);											//päivämäärä
		$ulos .= sprintf ('%-08d', $tosite); 										//tositelaji ja tositenumero
		$ulos .= sprintf ('%03d', '0'); 											//???? tositenumeron tarkenne 1
		$ulos .= sprintf ('%03d', '0'); 											//???? tositenumeron tarkenne 2
		$ulos .= sprintf ('%06d', $tilino);											//tili
		$ulos .= sprintf ('%6.6s', $kustprow['nimi']);
		$ulos .= sprintf ('%6.6s', $projprow['nimi']);
		$ulos .= sprintf ('%-10s', ' '); 											//???? projektilaji
		$ulos .= sprintf ('%04d', $row['jakso']);									//jakso

		if ($summa > 0) {
			$etu   = '+';
			$summa = sprintf ('%016d',  round(100 * $summa,2));
			$maara = sprintf ('%015d',  round(100 * $summa,2));
		}
		else {
			$etu = '-';
			$summa = sprintf ('%016d',  round(100 * $summa * -1,2));
			$maara = sprintf ('%015d',  round(100 * $summa * -1,2));
		}

		$ulos .= $etu;																//rahamäärän etumerkki
		$ulos .= $summa;															//rahamäärä
		$ulos .= $etu;																//määrän etumerkki
		$ulos .= $maara;															//määrä
		$ulos .= sprintf ('%-72.72s', $nimi . "/" . $selite);						//liikekumppanin nimi + tiliöinnin selite
	//	$ulos .= sprintf ('%08d', '0'); 											//asiakasnumero
		$ulos .= sprintf ('%-8.8s', ''); 											//asiakasnumero
		$ulos .= sprintf ('%-2.2s', ' ');											//???? laskulaji
	//	$ulos .= sprintf ('%06.6d', '0'); 											//laskun numero
		$ulos .= sprintf ('%-6.6s', ' '); 											//laskun numero
		$ulos .= sprintf ('%-6.6s', ' ');											//???? kustannuslaji
		$ulos .= sprintf ('%-8.8s', ' ');											//???? ryhmä3
		$ulos .= sprintf ('%-6.6s', ' ');											//???? ryhmä3 laji
		$ulos .= sprintf ('%-8.8s', ' ');											//???? ryhmä4
		$ulos .= sprintf ('%-6.6s', ' ');											//???? ryhmä4 laji
		$ulos .= '+';																//???? määrä kahden etumerkki
		$ulos .= sprintf ('%015d', '0');											//???? määrä 2
		$ulos .= '+';																//???? määrä kolmen etumerkki
		$ulos .= sprintf ('%015d', '0');											//???? määrä 3
		$ulos .= sprintf ('%-4s', ' ');												//???? yritysnumero
		$ulos .= sprintf ('%-20s', ' ');											//???? maksatuserätunnus
		$ulos .= sprintf ('%-3s', 'EUR');											//rahayksikön valuutta
		$palautus .= $ulos."\r\n";

		return $palautus;
	}

	function rivit($result, $laji, $yhtio, $summataan) {

		while ($row = mysql_fetch_array($result)) {

			if ($row["ltunnus"] != $vltunnus) {
				//echo "Uusi tosite<br>";
				$alaraja = $laji*1000000;
				$ylaraja = ($laji+1)*1000000;

				$query  = "	SELECT max(tosite)
							FROM tiliointi
							WHERE yhtio='$yhtio' and tosite > $alaraja and tosite < $ylaraja";
				$tresult = mysql_query($query) or pupe_error($query);
				$trow = mysql_fetch_array($tresult);

				if ($laji == 30) {
					$tosite = $laji.sprintf ('%06d', $row['laskunro']);
				}
				else {
					if ($trow[0] == 0) {
						$trow[0] = $laji*1000000;
					}
					$tosite = $trow[0]+1;
				}

				$vltunnus = $row["ltunnus"];
			}

			if ($summataan != '') {

				if ($summataan != '' and ($sltunnus != $vltunnus or $stapvm != $row['tapvm'] or $stilino != $row['tilino'] or $skustp != $row['kustp'] or $sprojekti != $row['projekti'])) {
					//echo "Summaus loppu!<br>";
					if ($summa != 0) {
						$palautus .= teetietue($yhtio, $stosite, $summa, $sltunnus, $stapvm, $stilino, $skustp, $sprojekti, $sytunnus, $snimi, $sselite);
					}
					$stosite = $tosite;
					$summa = 0;
					$sltunnus = $row['ltunnus'];
					$stapvm = $row['tapvm'];
					$stilino = $row['tilino'];
					$skustp = $row['kustp'];
					$sprojekti = $row['projekti'];
					$sytunnus = $row['ytunnus'];
					$snimi = $row['nimi'];
					$sselite = $row['selite'];
				}
			}
			else {
				$palautus .= teetietue($yhtio, $stosite, $summa, $sltunnus, $stapvm, $stilino, $skustp, $sprojekti, $sytunnus, $snimi, $sselite);
				$stosite = $tosite;
				$summa = 0;
				$sltunnus = $row['ltunnus'];
				$stapvm = $row['tapvm'];
				$stilino = $row['tilino'];
				$skustp = $row['kustp'];
				$sprojekti = $row['projekti'];
				$sytunnus = $row['ytunnus'];
				$snimi = $row['nimi'];
				$sselite = $row['selite'];
			}

			$summa += $row['summa'];
			$yhdistetty++;

			$query  = "UPDATE tiliointi set tosite = $tosite WHERE tunnus = $row[tunnus]";
			$tresult = mysql_query($query) or pupe_error($query);
		}

		if ($summa != 0) {
			$palautus .= teetietue($yhtio, $stosite, $summa, $sltunnus, $stapvm, $stilino, $skustp, $sprojekti, $sytunnus, $snimi, $sselite);
		}

		return $palautus;
	}

	//Onko aikaisempia ei vietyjä rivejä?
	$query  = "	SELECT left(tapvm, 7) kausi, count(distinct(ltunnus)) kpl
				FROM tiliointi
				WHERE yhtio = '$kukarow[yhtio]'
				and tosite = 0
				and left(tapvm,7) < '$kausi'
				and korjattu = ''
				and tapvm >= '$yhtiorow[tilikausi_alku]'
				group by 1 ";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='error'>Nämä tiliöinnit ovat siirtämättä edellisiltä kausilta</font><br>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("kausi")."</th>";
		echo "<th>".t("kpl")."</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_array($result)) {
			echo "<tr>";
			echo "<td>$trow[kausi]</td>";
			echo "<td>$trow[kpl]</td>";
			echo "</tr>";
		}

		echo "</table>";
		echo "<br><br>";
	}

	//Tarkistetaan aineisto tikon-mielessä
	$tikonerr = 0;

	$query  = "	SELECT tapvm, nimi, summa, tunnus
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				and tila IN ('H','M','P','Q''Y','U')
				and mapvm = tapvm
				and mapvm != '0000-00-00'
				and ((left(lasku.tapvm, 7) = '$kausi') or (left(lasku.tapvm, 7) = '$kausi'))";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result)>0) {
		echo "<font class='error'>Näilla laskuilla laskunpvm ja maksupvm ovat samat. Tämä aiheuttaa ongelmia siirrossa.</font>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("tapvm")."</th>";
		echo "<th>".t("nimi")."</th>";
		echo "<th>".t("summa")."</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_array($result)) {
			echo "<tr>";
			echo "<td>$trow[tapvm]</td>";
			echo "<td>$trow[nimi]</td>";
			echo "<td>$trow[summa]</td>";
			echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[tunnus]'>".t("Korjaa")."</a></td>";
			echo "</tr>";
		}

		echo "</table>";

		echo "<font class='error'>Nämä on korjattava ennenkuin siirto voidaan tehdä!</font><br><br>";
		$tikonerr = 1;
	}

	//tapvm:n tilioinnit puuttuvat?
	$query  = "	SELECT lasku.tapvm, nimi, lasku.summa, lasku.tunnus, count(*) kpl
				FROM lasku
				LEFT JOIN tiliointi ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tiliointi.tapvm = lasku.tapvm and korjattu = '')
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and ((left(lasku.tapvm, 7) = '$kausi') or (left(lasku.tapvm, 7) = '$kausi'))
				and lasku.tila IN ('H','M','P','Q''Y','U')
				GROUP BY 1,2,3,4
				HAVING kpl < 2";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='error'>Näiltä laskuita puuttuvat kaikki laskupvm:n tiliöinnit. Se on virhe.</font>";

		echo "<table>";
		echo "<tr>";
		for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
			echo "<th>" . mysql_field_name($result,$i)."</th>";
		}
		echo "</tr>";

		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
				echo "<td>$trow[$i]</td>";
			}
			echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[tunnus]'>Korjaa</a></td>";
			echo "</tr>";
		}

		echo "</table>";
		echo "<font class='error'>Näma on korjattava ennenkuin siirto voidaan tehdä!</font><br><br>";
		$tikonerr = 1;
	}

	//mapvm:n tilioinnit puuttuvat?
	$query  = "	SELECT lasku.tapvm, nimi, lasku.summa, lasku.tunnus, count(*) kpl
				FROM lasku
				LEFT JOIN tiliointi ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tiliointi.tapvm = lasku.mapvm and korjattu = '')
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and ((left(lasku.tapvm, 7) = '$kausi') or (left(lasku.tapvm, 7) = '$kausi'))
				and lasku.mapvm != '0000-00-00'
				and lasku.tila IN ('H','M','P','Q''Y','U')
				GROUP BY 1,2,3,4
				HAVING kpl < 2";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='error'>Näiltä laskuita puuttuvat kaikki maksupvm:n tiliöinnit. Se on virhe.</font>";
		echo "<table>";
		echo "<tr>";
		for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
			echo "<th>" . mysql_field_name($result,$i)."</th>";
		}
		echo "</tr>";

		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
				echo "<td>$trow[$i]</td>";
			}
			echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[tunnus]'>Korjaa</a></td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<font class='error'>Näma on korjattava ennenkuin siirto voidaan tehdä!</font>";
		$tikonerr=1;
	}

	if ($tikonerr != 0) {
		require ("inc/footer.inc");
		exit;
	}

	//tiedoston polku ja nimi
	$nimi = "dataout/$kukarow[yhtio]/TIKON-$kukarow[yhtio]-".date("ymd.His-s").".dat";

	$hakemisto = dirname($nimi);
	if (!is_dir($hakemisto)) {
		mkdir($hakemisto);
		if (is_dir($hakemisto))
			echo "<font class='message'>".t("Loin hakemiston"). "$hakemisto</font><br>";
		else {
			echo "<font class='error'>".t("Yritin luoda hakemistoa, mutta se ei onnistu. Ota yhteyttä järjestelmän ylläpitoon")." $hakemisto</font><br>";
			exit;
		}
	}

	//avataan tiedosto
	$toot = fopen($nimi,"w+");

	echo "Yrityksen $yhtiorow[nimi] kirjanpidolliset tapahtumat kaudella $kausi. ";
	if ($summataan=='') echo "Tapahtumia ei summata."; else echo "Tapahtumat summataan.";
	echo "<br><br>Raportti otettu $today.<br><br>";

	//haetaan myyntisaamiset
	$query  = "	SELECT date_format(tiliointi.tapvm, '%d%m%Y') tapvm,
				date_format(tiliointi.tapvm, '%y%m') jakso, tilino, kustp, projekti, tiliointi.summa
				summa, selite, ytunnus, ltunnus, mapvm, tiliointi.tunnus tunnus, lasku.laskunro laskunro, nimi
				FROM tiliointi
				JOIN lasku ON tiliointi.yhtio = lasku.yhtio and lasku.tunnus = tiliointi.ltunnus and tosite = '' and lasku.tila = 'U' and lasku.tapvm = tiliointi.tapvm and left(lasku.tapvm, 7) = '$kausi' and korjattu = '' and lasku.tila != 'D'
				WHERE tiliointi.yhtio = '$kukarow[yhtio]'
				ORDER BY ltunnus, tiliointi.tapvm, tilino, kustp, projekti";
	$result_ms = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result_ms) > 0) {
		fputs($toot, rivit($result_ms, 91, $kukarow["yhtio"], $summataan));
	}
	echo "Myyntisaamisia ".mysql_num_rows($result_ms)." kappaletta<br>";

	//haetaan ostovelat
	$query  = "	SELECT date_format(tiliointi.tapvm, '%d%m%Y') tapvm,
				date_format(tiliointi.tapvm, '%y%m') jakso, tilino, kustp, projekti, tiliointi.summa
				summa, selite, ytunnus, ltunnus, mapvm, tiliointi.tunnus tunnus, nimi
				FROM tiliointi
				JOIN lasku ON tiliointi.yhtio = lasku.yhtio and lasku.tunnus = tiliointi.ltunnus and tosite = '' and lasku.tila != 'X' and lasku.tila != 'U' and lasku.tapvm = tiliointi.tapvm and left(lasku.tapvm, 7) = '$kausi' and korjattu = '' and lasku.tila != 'D'
				WHERE tiliointi.yhtio = '$kukarow[yhtio]'
				ORDER BY ltunnus, tiliointi.tapvm, tilino, kustp, projekti";
	$result_ov = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result_ov) > 0) {
		fputs($toot, rivit($result_ov, 93, $kukarow["yhtio"], $summataan));
	}
	echo "Ostovelkoja, ".mysql_num_rows($result_ov)." kappaletta<br>";

	//tehdään uusi kysely jossa yhdistetään suoritukset ja rahatapahtumat = TILIOTE
	$query  = "	SELECT date_format(tiliointi.tapvm, '%d%m%Y') tapvm,
				date_format(tiliointi.tapvm, '%y%m') jakso, tilino, kustp, projekti,tiliointi.summa
				summa, selite, ytunnus, ltunnus, mapvm, tiliointi.tunnus tunnus
				FROM tiliointi
				JOIN lasku ON tiliointi.yhtio = lasku.yhtio and lasku.tunnus = tiliointi.ltunnus and tosite = '' and ((lasku.tila != 'X' and lasku.tapvm != tiliointi.tapvm and left(tiliointi.tapvm, 7) = '$kausi') or (lasku.tila = 'X' and left(tiliointi.tapvm, 7) = '$kausi')) and korjattu = '' and lasku.tila != 'D'
				WHERE tiliointi.yhtio = '$kukarow[yhtio]'
				ORDER BY tiliointi.tapvm, ltunnus, tilino, kustp, projekti";
	$result_mrt = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result_mrt) > 0) {
		fputs($toot, rivit($result_mrt, 50, $kukarow["yhtio"], $summataan));
	}
	echo "Tiliotteiden tiliöinnit,  ".mysql_num_rows($result_mrt)." tapahtumaa<br>";

	fclose($toot);
	echo "Done!<br><br>";

	$filename = realpath($nimi);
	$txtfile = "TIKON-$kukarow[yhtio]-".date("ymd.His-s").".dat";

	if (filesize($nimi) > 0 ) {
		echo "<br><form>";
		echo "<input type='hidden' name='file' value='$filename'>";
		echo "<input type='hidden' name='lataa_tiedosto' value='1'>";
		echo "<input type='hidden' name='kaunisnimi' value='$txtfile'>";
		echo "<input type='submit' value='Tallenna tiedosto'>";
		echo "</form><br><br>";
	}

	// be add-on: TULOSTETAAN KAIKKI KYSEISET TULOKSET NÄYTÖLLE
	echo "<br><font class=head>$yhtiorow[nimi] myyntisaamiset, $kausi:</font><br><br>Raportti otettu $today.<br><br>";

	//Tehdään uusi kysely, jossa muutetaan lajittelujärjestys
	if ($summataan == '') {
		$query  = "	SELECT date_format(tiliointi.tapvm, '%e.%c.%Y') tapvm, lasku.nimi,
					tiliointi.summa summa, tilino, k.nimi kustp, p.nimi proj,
					selite, lasku.laskunro laskunro, tosite, mapvm, tiliointi.tunnus tunnus, date_format(tiliointi.tapvm, '%y%m') jakso, ytunnus, ltunnus
					FROM tiliointi
					JOIN lasku ON tiliointi.yhtio=lasku.yhtio and lasku.tunnus=tiliointi.ltunnus and lasku.tila='U' and lasku.tapvm=tiliointi.tapvm and left(lasku.tapvm,7)='$kausi' and korjattu='' and lasku.tila != 'D'
					LEFT JOIN kustannuspaikka k on tiliointi.yhtio=k.yhtio and k.tyyppi = 'k' and kustp = k.tunnus
					LEFT JOIN kustannuspaikka p on tiliointi.yhtio=p.yhtio and p.tyyppi = 'p' and projekti = p.tunnus
					WHERE tiliointi.yhtio='$kukarow[yhtio]'
					ORDER BY tosite, tiliointi.tapvm, ltunnus, tilino, k.nimi, p.nimi";
	}
	else {
		$query  = "	SELECT date_format(tiliointi.tapvm, '%e.%c.%Y') tapvm, lasku.nimi,
					sum(tiliointi.summa) summa, tilino, k.nimi kustp, p.nimi proj,
					selite, lasku.laskunro laskunro, tosite, mapvm, tiliointi.tunnus tunnus, date_format(tiliointi.tapvm, '%y%m') jakso, ytunnus, ltunnus
					FROM tiliointi
					JOIN lasku ON tiliointi.yhtio=lasku.yhtio and lasku.tunnus=tiliointi.ltunnus and lasku.tila='U' and lasku.tapvm=tiliointi.tapvm and left(lasku.tapvm,7)='$kausi' and korjattu='' and lasku.tila != 'D'
					LEFT JOIN kustannuspaikka k on tiliointi.yhtio=k.yhtio and k.tyyppi = 'k' and kustp = k.tunnus
					LEFT JOIN kustannuspaikka p on tiliointi.yhtio=p.yhtio and p.tyyppi = 'p' and projekti = p.tunnus
					WHERE tiliointi.yhtio='$kukarow[yhtio]'
					GROUP BY tosite, tiliointi.tapvm, ltunnus, tilino, kustp, projekti
					ORDER BY tosite, tiliointi.tapvm, ltunnus, tilino, kustp, projekti";
	}
	$result_ms = mysql_query($query) or pupe_error($query);

	//Tässä tehdään sarakeotsikot
	echo "<table>";
	echo "<tr>";
	for ($i = 0; $i < mysql_num_fields($result_ms)-4; $i++) {
		echo "<th>" . mysql_field_name($result_ms,$i)."</th>";
	}
	echo "</tr>";

	//Käydään läpi kaikki laskurivit
	while ($laskurow = mysql_fetch_array($result_ms)) {
		echo "<tr>";
		for ($i = 0; $i < mysql_num_fields($result_ms)-4; $i++) {
			echo "<td>$laskurow[$i]</td>";
			}
		echo "</tr>";
		}
	echo "</table><br><br>";

	//---- tulostetaan ostovelat näytölle -----------
	echo "<br><font class=head>$yhtiorow[nimi] ostovelkojen tiliöinnit, $kausi:</font><br><br>Raportti otettu $today.<br><br>";

	if ($summataan == '') {
		$query  = "	SELECT date_format(tiliointi.tapvm, '%e.%c.%Y') tapvm, lasku.nimi,
					tiliointi.summa summa, tilino, k.nimi kustp, p.nimi proj,
					selite,  tosite, mapvm, tiliointi.tunnus tunnus, ytunnus, date_format(tiliointi.tapvm, '%y%m') jakso, ltunnus
					FROM tiliointi
					JOIN lasku ON tiliointi.yhtio=lasku.yhtio and lasku.tunnus=tiliointi.ltunnus and lasku.tila!='X' and lasku.tila!='U' and lasku.tapvm=tiliointi.tapvm and left(lasku.tapvm,7)='$kausi' and korjattu='' and lasku.tila != 'D'
					LEFT JOIN kustannuspaikka k on tiliointi.yhtio=k.yhtio and k.tyyppi = 'k' and kustp = k.tunnus
					LEFT JOIN kustannuspaikka p on tiliointi.yhtio=p.yhtio and p.tyyppi = 'p' and projekti = p.tunnus
					WHERE tiliointi.yhtio='$kukarow[yhtio]'
					ORDER BY tosite, tiliointi.tapvm, tilino, k.nimi, p.nimi";
	}
	else {
		$query  = "	SELECT date_format(tiliointi.tapvm, '%e.%c.%Y') tapvm, lasku.nimi,
					sum(tiliointi.summa) summa, tilino, k.nimi kustp, p.nimi proj,
					selite,  tosite, mapvm, tiliointi.tunnus tunnus, ytunnus, date_format(tiliointi.tapvm, '%y%m') jakso, ltunnus
					FROM tiliointi
					JOIN lasku ON tiliointi.yhtio=lasku.yhtio and lasku.tunnus=tiliointi.ltunnus and lasku.tila!='X' and lasku.tila!='U' and lasku.tapvm=tiliointi.tapvm and left(lasku.tapvm,7)='$kausi' and korjattu='' and lasku.tila != 'D'
					LEFT JOIN kustannuspaikka k on tiliointi.yhtio=k.yhtio and k.tyyppi = 'k' and kustp = k.tunnus
					LEFT JOIN kustannuspaikka p on tiliointi.yhtio=p.yhtio and p.tyyppi = 'p' and projekti = p.tunnus
					WHERE tiliointi.yhtio='$kukarow[yhtio]'
					GROUP BY tosite, tiliointi.tapvm, tilino, k.nimi, p.nimi
					ORDER BY tosite, tiliointi.tapvm, tilino, k.nimi, p.nimi";
	}
	$result_ov = mysql_query($query) or pupe_error($query);

	//Tässä tehdään sarakeotsikot
	echo "<table>";
	echo "<tr>";
	for ($i = 0; $i < mysql_num_fields($result_ov)-5; $i++) {
		echo "<th>" . mysql_field_name($result_ov,$i)."</th>";
	}
	echo "</tr>";

	//Käydään läpi kaikki laskurivit
	while ($laskurow = mysql_fetch_array ($result_ov)) {
		echo "<tr>";
		for ($i = 0; $i < mysql_num_fields($result_ov)-5; $i++) {
			echo "<td>$laskurow[$i]</td>";
		}
		echo "</tr>";
	}
	echo "</table><br><br>";

	//---- tulostetaan rahatapahtumat näytölle ------
	echo "<br><font class=head>$yhtiorow[nimi] tiliotteen tiliöinnit, $kausi:</font><br><br> Raportti otettu $today.<br><br>";

	if ($summataan == '') {
		$query  = "	SELECT date_format(tiliointi.tapvm, '%e.%c.%Y') tapvm, lasku.nimi,
					tiliointi.summa summa, tilino,
					selite, tosite, date_format(tiliointi.tapvm, '%y%m') jakso, k.nimi kustp, p.nimi proj, ltunnus, ytunnus, mapvm, tiliointi.tunnus tunnus
					FROM tiliointi
					JOIN lasku ON tiliointi.yhtio=lasku.yhtio and lasku.tunnus=tiliointi.ltunnus and ((lasku.tila!='X' and lasku.tapvm!=tiliointi.tapvm and left(tiliointi.tapvm,7)='$kausi') or (lasku.tila ='X' and left(tiliointi.tapvm,7)='$kausi')) and korjattu='' and tila != 'D'
					LEFT JOIN kustannuspaikka k on tiliointi.yhtio=k.yhtio and k.tyyppi = 'k' and kustp = k.tunnus
					LEFT JOIN kustannuspaikka p on tiliointi.yhtio=p.yhtio and p.tyyppi = 'p' and projekti = p.tunnus
					WHERE tiliointi.yhtio='$kukarow[yhtio]'
					ORDER BY tiliointi.tapvm, tosite, tilino, k.nimi, p.nimi";
	}
	else {
		$query  = "	SELECT date_format(tiliointi.tapvm, '%e.%c.%Y') tapvm, lasku.nimi,
					sum(tiliointi.summa) summa, tilino,
					selite, tosite, date_format(tiliointi.tapvm, '%y%m') jakso, k.nimi kustp, p.nimi proj, ltunnus, ytunnus, mapvm, tiliointi.tunnus tunnus
					FROM tiliointi
					JOIN lasku ON tiliointi.yhtio=lasku.yhtio and lasku.tunnus=tiliointi.ltunnus and ((lasku.tila!='X' and lasku.tapvm!=tiliointi.tapvm and left(tiliointi.tapvm,7)='$kausi') or (lasku.tila ='X' and left(tiliointi.tapvm,7)='$kausi')) and korjattu='' and tila != 'D'
					LEFT JOIN kustannuspaikka k on tiliointi.yhtio=k.yhtio and k.tyyppi = 'k' and kustp = k.tunnus
					LEFT JOIN kustannuspaikka p on tiliointi.yhtio=p.yhtio and p.tyyppi = 'p' and projekti = p.tunnus
					WHERE tiliointi.yhtio='$kukarow[yhtio]'
					GROUP BY tiliointi.tapvm, tosite, tilino, k.nimi, p.nimi
					ORDER BY tiliointi.tapvm, tosite, tilino, k.nimi, p.nimi";
	}
	$result_rt = mysql_query($query) or pupe_error($query);

	//Tässä tehdään sarakeotsikot
	echo "<table>";
	echo "<tr>";
	for ($i = 0; $i < mysql_num_fields($result_rt)-7; $i++) {
		echo "<th>" . mysql_field_name($result_rt,$i)."</th>";
	}
	echo "</tr>";

	//Käydään läpi kaikki laskurivit
	while ($laskurow = mysql_fetch_array ($result_rt)) {
		echo "<tr>";
		for ($i = 0; $i < mysql_num_fields($result_rt)-7; $i++) {
			echo "<td>$laskurow[$i]</td>";
		}
		echo "</tr>";
	}
	echo "</table><br><br>";

?>