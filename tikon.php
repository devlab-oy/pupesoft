<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require('inc/parametrit.inc');

	if ($lataa_tiedosto == 1) {
		readfile("dataout/".$filenimi);
		exit;
	}

	// poimitaan kuluva p�iv�, raportin timestampille
	$today = date("Y-m-d");

	echo "<font class='head'>".t("Siirto ulkoiseen kirjanpitoon")."</font><hr>";

	if ($kausi == "") {

		//N�ytet��n k�ytt�liittym�
		echo "<form name = 'valinta' method='post'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Anna kausi")."</th>";
		echo "<td><input type = 'text' name = 'kausi' size=8> ".t("Esim").". ".date("Y")."-01</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<th>".t("Summaa tapahtumat")."</th>";
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

	function teetietue ($yhtio, $tosite, $summa, $ltunnus, $tapvm, $tilino, $kustp, $projekti, $ytunnus, $nimi, $selite, $jakso) {

		$ulos = 'TKB';																//tietuetyyppi
		$ulos .= sprintf ('%-8s',  $tapvm);											//p�iv�m��r�
		$ulos .= sprintf ('%-08d', $tosite); 										//tositelaji ja tositenumero
		$ulos .= sprintf ('%03d', '0'); 											//???? tositenumeron tarkenne 1
		$ulos .= sprintf ('%03d', '0'); 											//???? tositenumeron tarkenne 2
		$ulos .= sprintf ('%06d', $tilino);											//tili
		$ulos .= sprintf ('%6.6s', $kustp);
		$ulos .= sprintf ('%6.6s', $projekti);
		$ulos .= sprintf ('%-10s', ' '); 											//???? projektilaji
		$ulos .= sprintf ('%04d', $jakso);									//jakso

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

		$ulos .= $etu;																//raham��r�n etumerkki
		$ulos .= $summa;															//raham��r�
		$ulos .= $etu;																//m��r�n etumerkki
		$ulos .= $maara;															//m��r�
		$ulos .= sprintf ('%-72.72s', $nimi . "/" . $selite);						//liikekumppanin nimi + tili�innin selite
		$ulos .= sprintf ('%-8.8s', ''); 											//asiakasnumero
		$ulos .= sprintf ('%-2.2s', ' ');											//???? laskulaji
		$ulos .= sprintf ('%-6.6s', ' '); 											//laskun numero
		$ulos .= sprintf ('%-6.6s', ' ');											//???? kustannuslaji
		$ulos .= sprintf ('%-8.8s', ' ');											//???? ryhm�3
		$ulos .= sprintf ('%-6.6s', ' ');											//???? ryhm�3 laji
		$ulos .= sprintf ('%-8.8s', ' ');											//???? ryhm�4
		$ulos .= sprintf ('%-6.6s', ' ');											//???? ryhm�4 laji
		$ulos .= '+';																//???? m��r� kahden etumerkki
		$ulos .= sprintf ('%015d', '0');											//???? m��r� 2
		$ulos .= '+';																//???? m��r� kolmen etumerkki
		$ulos .= sprintf ('%015d', '0');											//???? m��r� 3
		$ulos .= sprintf ('%-4s', ' ');												//???? yritysnumero
		$ulos .= sprintf ('%-20s', ' ');											//???? maksatuser�tunnus
		$ulos .= sprintf ('%-3s', 'EUR');											//rahayksik�n valuutta

		$palautus = $ulos."\r\n";

		return $palautus;
	}

	function rivit ($result, $laji, $yhtio, $summataan) {

		$rivitruudulle 	= array();
		$palautus 		= "";
		$stosite 		= "";
		$summa 			= "";
		$sltunnus 		= "";
		$stapvm 		= "";
		$stapvmcle 		= "";
		$stilino 		= "";
		$skustp 		= "";
		$sprojekti 		= "";
		$sytunnus 		= "";
		$snimi 			= "";
		$sselite 		= "";
		$sjakso 		= "";
		$slaskunro 		= "";
		$smapvm 		= "";

		while ($row = mysql_fetch_assoc($result)) {

			//Kustannuspaikan koodien haku
			$query = "	SELECT nimi
						FROM kustannuspaikka
						WHERE yhtio = '$yhtio'
						and tunnus = '$row[kustp]'";
			$vresult = mysql_query($query) or pupe_error("Kysely ei onnistu $query");
			$kustprow = mysql_fetch_assoc($vresult);

			//Projekti koodien haku
			$query = "	SELECT nimi
						FROM kustannuspaikka
						WHERE yhtio = '$yhtio'
						and tunnus = '$row[projekti]'";
			$vresult = mysql_query($query) or pupe_error("Kysely ei onnistu $query");
			$projprow = mysql_fetch_assoc($vresult);

			if ((int) $kustprow['nimi'] == 0) {
				 $row['kustp'] = "";	//tsekataan ettei seurantakohteille  tule turhia etunollia
			}
			else {
				$row['kustp'] = $kustprow['nimi'];
			}
			if ((int) $projprow['nimi'] == 0) {
				$row['projekti'] = "";	//tsekataan ettei seurantakohteille  tule turhia etunollia
			}
			else {
				$row['projekti'] = $projprow['nimi'];
			}

			if ($row["ltunnus"] != $vltunnus) {
				$alaraja = $laji*1000000;
				$ylaraja = ($laji+1)*1000000;

				$query  = "	SELECT max(tosite) tosite
							FROM tiliointi
							WHERE yhtio='$yhtio' and tosite > $alaraja and tosite < $ylaraja";
				$tresult = pupe_query($query);
				$trow = mysql_fetch_assoc($tresult);

				if ($laji == 30) {
					$tosite = $laji.sprintf ('%06d', $row['laskunro']);
				}
				else {
					if ($trow["tosite"] == 0) {
						$trow["tosite"] = $laji*1000000;
					}
					$tosite = $trow["tosite"]+1;
				}

				$vltunnus = $row["ltunnus"];
			}

			if ($summataan != '') {

				if ($summataan != '' and ($sltunnus != $vltunnus or $stapvm != $row['tapvm'] or $stilino != $row['tilino'] or $skustp != $row['kustp'] or $sprojekti != $row['projekti'])) {

					if ($summa != 0) {
						$palautus .= teetietue($yhtio, $stosite, $summa, $sltunnus, $stapvm, $stilino, $skustp, $sprojekti, $sytunnus, $snimi, $sselite, $sjakso);
						$rivitruudulle[] = array("tapvm" => $stapvmcle, "nimi" => $snimi, "summa" => $summa, "tilino" => $stilino, "kustp" => $skustp, "projekti" => $sprojekti, "selite" => $sselite, "laskunro" => $slaskunro, "tosite" => $stosite, "mapvm" => $smapvm);
					}

					$stosite 	= $tosite;
					$summa 		= 0;
					$sltunnus 	= $row['ltunnus'];
					$stapvm 	= $row['tapvm'];
					$stapvmcle 	= $row['tapvmclean'];
					$stilino 	= $row['tilino'];
					$skustp 	= $row['kustp'];
					$sprojekti 	= $row['projekti'];
					$sytunnus 	= $row['ytunnus'];
					$snimi 		= $row['nimi'];
					$sselite 	= $row['selite'];
					$sjakso 	= $row['jakso'];
					$slaskunro 	= $row['laskunro'];
					$smapvm 	= $row['mapvm'];
				}
			}
			else {
				$palautus .= teetietue($yhtio, $stosite, $summa, $sltunnus, $stapvm, $stilino, $skustp, $sprojekti, $sytunnus, $snimi, $sselite, $sjakso);
				$rivitruudulle[] = array("tapvm" => $stapvmcle, "nimi" => $snimi, "summa" => $summa, "tilino" => $stilino, "kustp" => $skustp, "projekti" => $sprojekti, "selite" => $sselite, "laskunro" => $slaskunro, "tosite" => $stosite, "mapvm" => $smapvm);

				$stosite 	= $tosite;
				$summa 		= 0;
				$sltunnus 	= $row['ltunnus'];
				$stapvm 	= $row['tapvm'];
				$stapvmcle 	= $row['tapvmclean'];
				$stilino 	= $row['tilino'];
				$skustp 	= $row['kustp'];
				$sprojekti 	= $row['projekti'];
				$sytunnus 	= $row['ytunnus'];
				$snimi 		= $row['nimi'];
				$sselite 	= $row['selite'];
				$sjakso 	= $row['jakso'];
				$slaskunro 	= $row['laskunro'];
				$smapvm 	= $row['mapvm'];
			}

			$summa += $row['summa'];
			$yhdistetty++;

			$query  = "UPDATE tiliointi set tosite = $tosite WHERE tunnus = $row[tunnus]";
			$tresult = pupe_query($query);
		}

		if ($summa != 0) {
			$palautus .= teetietue($yhtio, $stosite, $summa, $sltunnus, $stapvm, $stilino, $skustp, $sprojekti, $sytunnus, $snimi, $sselite, $sjakso);
			$rivitruudulle[] = array("tapvm" => $stapvmcle, "nimi" => $snimi, "summa" => $summa, "tilino" => $stilino, "kustp" => $skustp, "projekti" => $sprojekti, "selite" => $sselite, "laskunro" => $slaskunro, "tosite" => $stosite, "mapvm" => $smapvm);
		}

		return array($palautus, $rivitruudulle);
	}

	//Onko aikaisempia ei vietyj� rivej�?
	$query  = "	SELECT left(tapvm, 7) kausi, count(distinct(ltunnus)) kpl
				FROM tiliointi
				WHERE yhtio = '$kukarow[yhtio]'
				and tosite = 0
				and left(tapvm,7) < '$kausi'
				and korjattu = ''
				and tapvm >= '$yhtiorow[tilikausi_alku]'
				group by 1 ";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='error'>".t("N�m� tili�innit ovat siirt�m�tt� edellisilt� kausilta").":</font><br>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("kausi")."</th>";
		echo "<th>".t("kpl")."</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_assoc($result)) {
			echo "<tr>";
			echo "<td>$trow[kausi]</td>";
			echo "<td align='right'>$trow[kpl]</td>";
			echo "</tr>";
		}

		echo "</table>";
		echo "<br><br>";
	}

	//Tarkistetaan aineisto tikon-mieless�
	$tikonerr = 0;

	$query  = "	SELECT tapvm, nimi, summa, tunnus
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				and tila IN ('H','M','P','Q','Y','U')
				and mapvm = tapvm
				and mapvm != '0000-00-00'
				and ((left(lasku.tapvm, 7) = '$kausi') or (left(lasku.tapvm, 7) = '$kausi'))";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='error'>N�ill� laskuilla laskunpvm ja maksupvm ovat samat. T�m� aiheuttaa ongelmia siirrossa.</font>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("tapvm")."</th>";
		echo "<th>".t("nimi")."</th>";
		echo "<th>".t("summa")."</th>";
		echo "</tr>";

		while ($trow = mysql_fetch_assoc($result)) {
			echo "<tr>";
			echo "<td>$trow[tapvm]</td>";
			echo "<td>$trow[nimi]</td>";
			echo "<td>$trow[summa]</td>";
			echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[tunnus]'>".t("Korjaa")."</a></td>";
			echo "</tr>";
		}

		echo "</table>";

		echo "<font class='error'>N�m� on korjattava ennenkuin siirto voidaan tehd�!</font><br><br>";
		$tikonerr = 1;
	}

	//tapvm:n tilioinnit puuttuvat?
	$query  = "	SELECT lasku.tapvm, nimi, lasku.summa, lasku.tunnus, count(*) kpl
				FROM lasku
				LEFT JOIN tiliointi ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tiliointi.tapvm = lasku.tapvm and korjattu = '')
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and ((left(lasku.tapvm, 7) = '$kausi') or (left(lasku.tapvm, 7) = '$kausi'))
				and lasku.tila IN ('H','M','P','Q','Y','U')
				GROUP BY 1,2,3,4
				HAVING kpl < 2";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='error'>".t("VIRHE: N�ilt� laskuilta puuttuvat kaikki laskupvm:n tili�innit")."!</font>";

		echo "<table>";
		echo "<tr>";
		for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
			echo "<th>".mysql_field_name($result, $i)."</th>";
		}
		echo "</tr>";

		while ($trow = mysql_fetch_assoc($result)) {
			echo "<tr>";
			for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
				echo "<td>".$trow[mysql_field_name($result, $i)]."</td>";
			}
			echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[tunnus]'>Korjaa</a></td>";
			echo "</tr>";
		}

		echo "</table>";
		echo "<font class='error'>".t("N�m� on korjattava ennenkuin siirto voidaan tehd�")."!</font><br><br>";
		$tikonerr = 1;
	}

	//mapvm:n tilioinnit puuttuvat?
	$query  = "	SELECT lasku.tapvm, nimi, lasku.summa, lasku.tunnus, count(*) kpl
				FROM lasku
				LEFT JOIN tiliointi ON (lasku.yhtio = tiliointi.yhtio
					AND lasku.tunnus = tiliointi.ltunnus
					AND tiliointi.tapvm = lasku.mapvm
					AND korjattu = '')
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				AND ((left(lasku.tapvm, 7) = '$kausi') or (left(lasku.tapvm, 7) = '$kausi'))
				AND lasku.mapvm != '0000-00-00'
				AND lasku.tila IN ('H','M','P','Q','Y','U')
				GROUP BY 1,2,3,4
				HAVING kpl < 2";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='error'>".t("VIRHE: N�ilt� laskuita puuttuvat kaikki maksupvm:n tili�innit")."!</font>";
		echo "<table>";
		echo "<tr>";

		for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
			echo "<th>".mysql_field_name($result,$i)."</th>";
		}

		echo "</tr>";

		while ($trow = mysql_fetch_assoc($result)) {
			echo "<tr>";

			for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
				echo "<td>".$trow[mysql_field_name($result, $i)]."</td>";
			}

			echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[tunnus]'>Korjaa</a></td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<font class='error'>".t("N�ma on korjattava ennenkuin siirto voidaan tehd�")."!</font>";
		$tikonerr=1;
	}

	if ($tikonerr != 0) {
		require ("inc/footer.inc");
		exit;
	}

	// Tiedoston polku ja nimi
	$nimi = "TIKON-$kukarow[yhtio]-".date("ymd.His-s").".dat";

	//avataan tiedosto
	$toot = fopen("dataout/".$nimi,"w+");

	echo "$yhtiorow[nimi] ".t("kirjanpidolliset tapahtumat kaudella")." $kausi. ";

	if ($summataan=='') echo "Tapahtumia ei summata. ";
	else echo "Tapahtumat summataan. ";

	echo " Raportti otettu $today.<br><br>";

	//haetaan myyntisaamiset
	$query  = "	SELECT date_format(tiliointi.tapvm, '%d%m%Y') tapvm, date_format(tiliointi.tapvm, '%y%m') jakso,
				tiliointi.tilino, tiliointi.kustp, tiliointi.projekti, tiliointi.summa,
				tiliointi.selite, lasku.ytunnus, tiliointi.ltunnus, lasku.mapvm, tiliointi.tunnus, lasku.laskunro, lasku.nimi, tiliointi.tapvm tapvmclean
				FROM tiliointi
				JOIN lasku ON tiliointi.yhtio = lasku.yhtio
					AND lasku.tunnus = tiliointi.ltunnus
					AND lasku.tila = 'U'
					AND lasku.tapvm = tiliointi.tapvm
					AND left(lasku.tapvm, 7) = '$kausi'
				WHERE tiliointi.yhtio = '$kukarow[yhtio]'
				AND tiliointi.korjattu = ''
				AND tiliointi.tosite = ''
				ORDER BY tiliointi.ltunnus, tiliointi.tapvm, tiliointi.tilino, tiliointi.kustp, tiliointi.projekti";
	$result_ms = pupe_query($query);

	if (mysql_num_rows($result_ms) > 0) {
		list($palautus, $rivitruudulle1) = rivit($result_ms, 91, $kukarow["yhtio"], $summataan);
		fwrite($toot, $palautus);
	}

	//haetaan ostovelat
	$query  = "	SELECT date_format(tiliointi.tapvm, '%d%m%Y') tapvm, date_format(tiliointi.tapvm, '%y%m') jakso,
				tiliointi.tilino, tiliointi.kustp, tiliointi.projekti, tiliointi.summa,
				tiliointi.selite, lasku.ytunnus, tiliointi.ltunnus, lasku.mapvm, tiliointi.tunnus, lasku.laskunro, lasku.nimi, tiliointi.tapvm tapvmclean
				FROM tiliointi
				JOIN lasku ON tiliointi.yhtio = lasku.yhtio
					AND lasku.tunnus = tiliointi.ltunnus
					AND lasku.tila in ('H','M','P','Q','Y')
					AND lasku.tapvm = tiliointi.tapvm
					AND left(lasku.tapvm, 7) = '$kausi'
				WHERE tiliointi.yhtio = '$kukarow[yhtio]'
				AND tiliointi.tosite = ''
				AND tiliointi.korjattu = ''
				ORDER BY tiliointi.ltunnus, tiliointi.tapvm, tiliointi.tilino, tiliointi.kustp, tiliointi.projekti";
	$result_ov = pupe_query($query);

	if (mysql_num_rows($result_ov) > 0) {
		list($palautus, $rivitruudulle2) = rivit($result_ov, 93, $kukarow["yhtio"], $summataan);
		fwrite($toot, $palautus);
	}

	//tehd��n uusi kysely jossa yhdistet��n suoritukset ja rahatapahtumat = TILIOTE
	$query  = "	SELECT date_format(tiliointi.tapvm, '%d%m%Y') tapvm, date_format(tiliointi.tapvm, '%y%m') jakso,
				tiliointi.tilino, tiliointi.kustp, tiliointi.projekti, tiliointi.summa,
				tiliointi.selite, lasku.ytunnus, tiliointi.ltunnus, lasku.mapvm, tiliointi.tunnus, lasku.laskunro, lasku.nimi, tiliointi.tapvm tapvmclean
				FROM tiliointi
				JOIN lasku ON (tiliointi.yhtio = lasku.yhtio
					AND lasku.tunnus = tiliointi.ltunnus
					AND ((lasku.tila in ('H','M','P','Q','Y','U')
					AND lasku.tapvm != tiliointi.tapvm
					AND left(tiliointi.tapvm, 7) = '$kausi') or (lasku.tila = 'X' and left(tiliointi.tapvm, 7) = '$kausi')))
				WHERE tiliointi.yhtio = '$kukarow[yhtio]'
				AND tiliointi.tosite = ''
				AND tiliointi.korjattu = ''
				ORDER BY tiliointi.ltunnus, tiliointi.tapvm, tiliointi.tilino, tiliointi.kustp, tiliointi.projekti";
	$result_mrt = pupe_query($query);

	if (mysql_num_rows($result_mrt) > 0) {
		list($palautus, $rivitruudulle3) = rivit($result_mrt, 50, $kukarow["yhtio"], $summataan);
		fwrite($toot, $palautus);
	}

	fclose($toot);

	$txtfile = "TIKON-$kukarow[yhtio]-".date("ymd.His-s").".dat";

	if (filesize("dataout/".$nimi) > 0) {
		echo "<br><form class='multisubmit'>";
		echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
		echo "<input type='hidden' name='filenimi' value='$nimi'>";
		echo "<input type='hidden' name='lataa_tiedosto' value='1'>";
		echo "<input type='hidden' name='kaunisnimi' value='$txtfile'>";
		echo "<input type='submit' value='".t("Tallenna tiedosto")."'>";
		echo "</form><br><br>";
	}

	echo "<br><font class=head>$yhtiorow[nimi] myyntisaamiset: $kausi</font><br>";
	echo t("Myyntisaamisia").": ".mysql_num_rows($result_ms)." ".t("kappaletta")."<br><br>";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Tapvm")."</th>";
	echo "<th>".t("Nimi")."</th>";
	echo "<th>".t("Summa")."</th>";
	echo "<th>".t("Tilino")."</th>";
	echo "<th>".t("Kustp")."</th>";
	echo "<th>".t("Projekti")."</th>";
	echo "<th>".t("Selite")."</th>";
	echo "<th>".t("Laskunro")."</th>";
	echo "<th>".t("Tosite")."</th>";
	echo "<th>".t("Mapvm")."</th>";
	echo "</tr>";

	//K�yd��n l�pi kaikki laskurivit
	foreach ($rivitruudulle1 as $rivirow) {
		echo "<tr>";
		echo "<td>".tv1dateconv($rivirow["tapvm"])."</td>";
		echo "<td>$rivirow[nimi]</td>";
		echo "<td align='right'>$rivirow[summa]</td>";
		echo "<td>$rivirow[tilino]</td>";
		echo "<td>$rivirow[kustp]</td>";
		echo "<td>$rivirow[projekti]</td>";
		echo "<td>$rivirow[selite]</td>";
		echo "<td>$rivirow[laskunro]</td>";
		echo "<td>$rivirow[tosite]</td>";
		echo "<td>".tv1dateconv($rivirow["mapvm"])."</td>";
		echo "</tr>";
	}
	echo "</table><br><br>";

	echo "<br><font class=head>$yhtiorow[nimi] ostovelkojen tili�innit: $kausi</font><br>";
	echo t("Ostovelkoja").": ".mysql_num_rows($result_ov)." ".t("kappaletta")."<br><br>";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Tapvm")."</th>";
	echo "<th>".t("Nimi")."</th>";
	echo "<th>".t("Summa")."</th>";
	echo "<th>".t("Tilino")."</th>";
	echo "<th>".t("Kustp")."</th>";
	echo "<th>".t("Projekti")."</th>";
	echo "<th>".t("Selite")."</th>";
	echo "<th>".t("Tosite")."</th>";
	echo "</tr>";

	//K�yd��n l�pi kaikki laskurivit
	foreach ($rivitruudulle2 as $rivirow) {
		echo "<tr>";
		echo "<td>".tv1dateconv($rivirow["tapvm"])."</td>";
		echo "<td>$rivirow[nimi]</td>";
		echo "<td align='right'>$rivirow[summa]</td>";
		echo "<td>$rivirow[tilino]</td>";
		echo "<td>$rivirow[kustp]</td>";
		echo "<td>$rivirow[projekti]</td>";
		echo "<td>$rivirow[selite]</td>";
		echo "<td>$rivirow[tosite]</td>";
		echo "</tr>";
	}
	echo "</table><br><br>";

	echo "<br><font class=head>$yhtiorow[nimi] tiliotteen tili�innit: $kausi</font><br>";
	echo t("Tiliotteiden tili�innit").": ".mysql_num_rows($result_mrt)." ".t("tapahtumaa")."<br><br>";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Tapvm")."</th>";
	echo "<th>".t("Nimi")."</th>";
	echo "<th>".t("Summa")."</th>";
	echo "<th>".t("Tilino")."</th>";
	echo "<th>".t("Kustp")."</th>";
	echo "<th>".t("Projekti")."</th>";
	echo "<th>".t("Selite")."</th>";
	echo "<th>".t("Tosite")."</th>";
	echo "</tr>";

	//K�yd��n l�pi kaikki laskurivit
	foreach ($rivitruudulle3 as $rivirow) {
		echo "<tr>";
		echo "<td>".tv1dateconv($rivirow["tapvm"])."</td>";
		echo "<td>$rivirow[nimi]</td>";
		echo "<td align='right'>$rivirow[summa]</td>";
		echo "<td>$rivirow[tilino]</td>";
		echo "<td>$rivirow[kustp]</td>";
		echo "<td>$rivirow[projekti]</td>";
		echo "<td>$rivirow[selite]</td>";
		echo "<td>$rivirow[tosite]</td>";
		echo "</tr>";
	}
	echo "</table><br><br>";

	require "inc/footer.inc";

?>