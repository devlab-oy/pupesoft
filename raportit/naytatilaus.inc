<?php
    ///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
	$useslave = 1;

	$lis = "";

	if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
		$lis = " and lasku.liitostunnus = '$kukarow[oletus_asiakas]' ";
	}

	if (!isset($ojarj)) $ojarj = "";
	if (!isset($nippu)) $nippu = 0;
	if (!isset($toim)) $toim = "";
	if (!isset($asiakasid)) $asiakasid = "";
	if (!isset($toimittajaid)) $toimittajaid = "";
	if (!isset($nimi)) $nimi = "";
	if (!isset($ppa)) $ppa = "";
	if (!isset($kka)) $kka = "";
	if (!isset($vva)) $vva = "";
	if (!isset($ppl)) $ppl = "";
	if (!isset($kkl)) $kkl = "";
	if (!isset($vvl)) $vvl = "";
	if (!isset($lopetus)) $lopetus = "";
	if (!isset($linkurl)) $linkurl = "";
	if (!isset($kieli)) $kieli = "";
	if (!isset($tee)) $tee = "";
	$toimittajan_ostotilaus = array();

	$query = "	SELECT lasku.*,
				laskun_lisatiedot.laskutus_nimi,
				laskun_lisatiedot.laskutus_nimitark,
				laskun_lisatiedot.laskutus_osoite,
				laskun_lisatiedot.laskutus_postino,
				laskun_lisatiedot.laskutus_postitp,
				laskun_lisatiedot.laskutus_maa,
				if (lasku.tila = 'L' and lasku.laskunro > 0, (SELECT lasku.mapvm FROM lasku l WHERE l.yhtio = lasku.yhtio AND l.laskunro = lasku.laskunro AND l.tila = 'U' limit 1), lasku.mapvm) mapvm,
				if (lasku.tila = 'U', (	SELECT group_concat(distinct alv SEPARATOR ' ')
										FROM tilausrivi use index (uusiotunnus_index)
										WHERE tilausrivi.yhtio = lasku.yhtio
										AND tilausrivi.uusiotunnus = lasku.tunnus
										AND tilausrivi.tyyppi != 'D'),
									  ( SELECT group_concat(distinct alv SEPARATOR ' ')
										FROM tilausrivi use index (yhtio_otunnus)
										WHERE tilausrivi.yhtio = lasku.yhtio
										AND tilausrivi.otunnus = lasku.tunnus
										AND tilausrivi.tyyppi != 'D')) alv

				FROM lasku
				LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
				WHERE lasku.tunnus = '$tunnus'
				$lis
				and lasku.yhtio='$kukarow[yhtio]'";
	$aresult = pupe_query($query);

	if (mysql_num_rows($aresult) == 0) {
		echo "<strong>".t("VIRHE: Tilausta ei lˆydy")."!</strong>$query<br>";
		exit;
	}
	$laskurow = mysql_fetch_assoc($aresult);

	$vientikurssi = 1;

	if (!in_array($laskurow["vienti_kurssi"], array(1,0))) {
		$vientikurssi = $laskurow["vienti_kurssi"];
	}

	if ($laskurow['tila']=='L' or $laskurow['tila']=='G') {
		// haetaan eka rahtikirjat tietue, niin saadaan rahtikirjanumerot tietoon
		$query = "	SELECT *
					from rahtikirjat
					where yhtio = '$kukarow[yhtio]'
					and otsikkonro = '$laskurow[tunnus]'
					limit 1";
		$rakre = pupe_query($query);
		$rakirrow = mysql_fetch_assoc($rakre);
	}

	//	Onko koko projekti maksettu
	if ($laskurow['tila'] == 'R') {
		$query = "	SELECT count(distinct laskunro) laskuja, sum(if (laskunro=0, 1, 0)) laskuttamatta,
					sum(if ((select mapvm from lasku l where l.yhtio = lasku.yhtio and l.laskunro = lasku.laskunro and l.tila = 'U') = '0000-00-00', 1, 0)) maksamatta,
					max((select mapvm from lasku l where l.yhtio = lasku.yhtio and l.laskunro = lasku.laskunro and l.tila = 'U')) maksettu
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnus]' and tila = 'L'";
		$mares = pupe_query($query);
		$marow = mysql_fetch_assoc($mares);

		if ($marow["laskuttamatta"] == 0 and $marow["maksamatta"] == 0) {
			$laskurow["mapvm"] = $marow["maksettu"];
		}
	}

	echo "<table>";

	echo "<tr>
			<th>".t("Ytunnus")."</th>
			<th colspan='2'>".t("Ostaja")."</th>
			<th colspan='2'>".t("Toimitusosoite")."</th>
			<th>".t("Laskutusosoite")."</th>";

	if ($laskurow["tila"] == "O") {
		echo "<th>".t("Eturahti")."</th>";
		echo "<th>".t("Pyoristyserot")."</th>";
	}

	echo "</tr>";

	echo "<tr>
			<td>$laskurow[ytunnus]</td>
			<td colspan='2'>$laskurow[nimi] $laskurow[nimitark]<br> $laskurow[osoite]<br> $laskurow[postino] $laskurow[postitp]<br>".maa($laskurow["maa"])."</td>";

	if ($verkkokauppa != "" and $muokkaa != "") {
		echo "<td colspan='2'>
				<input type = 'text' name='toim_nimi' value = '$laskurow[toim_nimi]' size = '30'><br>
				<input type = 'text' name='toim_nimitark' value = '$laskurow[toim_nimitark]' size = '30'><br>
				<input type = 'text' name='toim_osoite' value = '$laskurow[toim_osoite]' size = '30'><br>
				<input type = 'text' name='toim_postino' value = '$laskurow[toim_postino]' size = '6'> <input type = 'text' name='toim_postitp' value = '$laskurow[toim_postitp]' size = '20'><br>
				".maa($laskurow["toim_maa"])."</td>";
	}
	else {
		echo "<td colspan='2'>
				$laskurow[toim_nimi] $laskurow[toim_nimitark]<br> $laskurow[toim_osoite]<br> $laskurow[toim_postino] $laskurow[toim_postitp]<br> ".maa($laskurow["toim_maa"])."</td>";
	}

	if (trim($laskurow['laskutus_nimi']) == '') {
		$laskurow['laskutus_nimi'] 		= $laskurow['nimi'];
		$laskurow['laskutus_nimitark'] 	= $laskurow['nimitark'];
		$laskurow['laskutus_osoite'] 	= $laskurow['osoite'];
		$laskurow['laskutus_postino'] 	= $laskurow['postino'];
		$laskurow['laskutus_postitp'] 	= $laskurow['postitp'];
		$laskurow['laskutus_maa'] 		= $laskurow['maa'];
	}

	echo "<td>$laskurow[laskutus_nimi] $laskurow[laskutus_nimitark]<br>$laskurow[laskutus_osoite]<br>$laskurow[laskutus_postino] $laskurow[laskutus_postitp]<br>",maa($laskurow['laskutus_maa']),"</td>";

	if ($laskurow["tila"] == "O") {
		echo "<td>$laskurow[rahti_etu]</td>";
		echo "<td>$laskurow[pyoristys_erot]</td>";
	}

	echo "</tr>";

	echo "<tr>
			<th>".t("Tilausnumero")."</th>
			<th colspan='2'>".t("Tila")."</th>
			<th>".t("Vienti")."</th>
			<th>".t("Alv")."</th>
			<th>".t("Erikoisalennus")."</th>";

	if ($laskurow["tila"] == "O") {
		echo "<th>".t("Rahti")."</th>";
	}
	echo "</tr>";

	echo "<tr>";

	if ($laskurow["tila"] != "K" and $laskurow["tila"] != "U") echo "<td>$laskurow[tunnus]</td>";
	else echo "<td></td>";

	$laskutyyppi = $laskurow["tila"];
	$alatila	 = $laskurow["alatila"];

	//tehd‰‰n selv‰kielinen tila/alatila
	if ($kukarow["extranet"] != '') {
		require ("laskutyyppi.inc");
	}
	else {
		require ("inc/laskutyyppi.inc");
	}

	if ($laskurow["vienti"] == "K") {
		$vteksti = t("Vienti ei-EU");
	}
	elseif ($laskurow["vienti"] == "E") {
		$vteksti = t("Vienti EU");
	}
	else {
		$vteksti = t("Kotimaa");
	}

	if ($laskutyyppi == "Mit‰tˆity") {
		$fn1 = "<font class='error'>";
		$fn2 = "</font>";

		$deletedlisa = ",'D'";
	}
	else {
		$fn1 = "";
		$fn2 = "";

		$deletedlisa = "";
	}

	echo "	<td colspan='2'>$fn1".t("$laskutyyppi")." ".t("$alatila")."$fn2</td>
			<td>$vteksti</td>
			<td>".(float) $laskurow['alv']."%</td>
			<td>".(float) $laskurow['erikoisale']."%</td>";

	if ($laskurow["tila"] == "O") {
		echo "<td>$laskurow[rahti]</td>";
	}

	echo "</tr>";
	echo "<tr>";

	if ($laskurow["tila"] == "O" or $laskurow["tila"] == "K") echo "<th>".t("Saapumisnumero")."</th>";
	else echo "<th>".t("Laskunro")."</th>";

	if ($laskurow["tila"] == "O" or $laskurow["tila"] == "K") {
		echo "<th colspan='2'>".t("Valuutta")."</th>";
	}
	else {
		echo "<th colspan='2'>".t("Laskun pvm")."</th>";
	}

	echo "	<th>".t("Er‰p‰iv‰")."</th>
			<th>".t("Maksup‰iv‰")."</th>
			<th>".t("Toimitusaika")."</th>";

	if ($laskurow["tila"] == "O") {
		echo "<th>".t("Huolinta")."</th>";
	}
	echo "</tr>";

	echo "<tr><td>$laskurow[laskunro]</td>";

	if ($laskurow["tila"] == "O" or $laskurow["tila"] == "K") {
		echo "<td colspan='2'>$laskurow[valkoodi]</td>";
	}
	else {
		echo "<td colspan='2'>".tv1dateconv($laskurow["tapvm"])."</td>";
	}
	echo "	<td>".tv1dateconv($laskurow["erpcm"])."</td>
			<td>".tv1dateconv($laskurow["mapvm"])."</td>
			<td>".tv1dateconv($laskurow["toimaika"])."</td>";

	if ($laskurow["tila"] == "O") {
		echo "<td>$laskurow[rahti_huolinta]</td>";
	}

	echo "</tr>";

	if ($laskurow["tila"] != "O" and $laskurow["tila"] != "K") {
		if (isset($dpdtun[$kukarow['yhtio']])) $dpdtun = $dpdtun[$kukarow['yhtio']];
		else $dpdtun = "";

		if (isset($dpdss[$kukarow['yhtio']]))  $dpdss  = $dpdss[$kukarow['yhtio']];
		else $dpdss = "";

		$possi = strpos(strtoupper($laskurow['toimitustapa']),"DPD");

		if (isset($rakirrow)) {
			// postin rahtikirjat alkaa JJFI, ja tehd‰‰n niille linkki suoraan postin seurantaan
			if (stripos($rakirrow['rahtikirjanro'],"JJFI") !== FALSE) {
				$linkurl = "";

				preg_match_all("/JJFI ?[0-9]{6} ?[0-9]{11}/", $rakirrow['rahtikirjanro'], $match);

				foreach ($match[0] as $nro) {
					$nro = str_replace(" ", "", $nro);
					$linkurl .= "<a target=newikkuna href='http://www.itella.fi/itemtracking/itella/search_by_shipment_id?ShipmentId={$nro}'>{$nro}</a><br>";
				}

				$linkurl = substr($linkurl,0,-4); // vika br pois
			}
			//luodaan ups seurantalinkki
			elseif (stripos($rakirrow['rahtikirjanro'], 'UPS') !== FALSE) {
				$linkurl = "";

				preg_match_all("/UPS:([0-9A-Z]+)/", $rakirrow['rahtikirjanro'], $match);

				foreach ($match[1] as $nro) {
					$linkurl .= "<a target=newikkuna href='http://wwwapps.ups.com/etracking/tracking.cgi?InquiryNumber1={$nro}&TypeOfInquiryNumber=T&AcceptUPSLicenseAgreement=yes&submit=Trac&loc=".strtolower($kukarow["kieli"])."_".strtoupper($kukarow["kieli"])."'>{$nro}</a><br>";
				}

				$linkurl = substr($linkurl,0,-4); // vika br pois
			}
			//luodaan dpd seurantalinkk
			elseif ($possi !== FALSE and $rakirrow['rahtikirjanro'] != '' and $dpdtun != '' and $dpdss != '') {
				$linkurl = "";

				for ($dpdi=1; $dpdi<$rakirrow['kollit']+1; $dpdi++) {
					$sscc = "";

					if (!function_exists('tarkiste')) {

						function tarkiste($sscc) {

							$kerroin = 3; // kerroin aluks 3
							$summa   = 0; // summa nolla tietty

							// loopataan luvut oikeelta vasemmalle
							for ($i = 16; $i >= 0; $i--) {
								$summa += $kerroin * (ord($sscc{$i})-48); // lis‰t‰‰n summaan ko. luku * kerroin (t‰‰ hanskaa kirjaimet )
								$kerroin = 4 - $kerroin; // kerroin on vuorotellen 3 tai 1
							}

							$sscc = ceil($summa / 10) * 10 - $summa; // tarkiste on luku mik‰ pit‰‰ lis‰t‰, ett‰ p‰‰st‰‰n seuraavaan tasakymmeneen

							return $sscc;
						}
					}

					$sscc = 1;
					$sscc .= sprintf("%08.8s", $yhtiorow["ytunnus"]);
					$sscc .= sprintf('%06.6s', substr($rakirrow['rahtikirjanro'], -6));
					$sscc .= sprintf('%02.2s', $dpdi);
					$loppu = tarkiste($sscc);
					$sscc = $sscc.$loppu;

					#$linkurl .= "<a target=newikkuna href='http://extranet.dpd.de/cgi-bin/delistrack.acl?typ=4&model=web_login.gws&username=$dpdtun&password=$dpdss&pknr=$sscc'>$sscc</a><br>";
					$linkurl .= "<a target=newikkuna href='http://$dpdtun:$dpdss@tracking.dpd.de/cgi-bin/delistrack.acl?typ=4&pknr=$sscc'>$sscc</a><br>";

				}

				$linkurl = substr($linkurl,0,-4); // vika br pois

			}
			else {
				$linkurl = $rakirrow['rahtikirjanro'];
			}
		}

		echo "<tr><th colspan='4'>".t("Toimitustapa")." / ".t("Rahtikirjanro")."</th><th colspan='2'>".t("Toimitusehto")."</th></tr>";
		echo "<tr><td colspan='4'>".t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV")." / $linkurl</td><td colspan='2'>$laskurow[toimitusehto]</td></tr>";
	}

	if (($laskurow["tila"] == "L" or $laskurow["tila"] == "N") and $kukarow["extranet"] == "") {

		$query = "	SELECT
					max(if(tilausrivi.keratty = 'saldoton', '9999-99-99 23:25:59', tilausrivi.kerattyaika)) kerattyaika,
					max(tilausrivi.toimaika) toimaika,
					max(tilausrivi.toimitettuaika) toimitettuaika
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					AND tilausrivi.otunnus = '$laskurow[tunnus]'
					AND tilausrivi.tyyppi != 'D'";
		$aikares = pupe_query($query);
		$aikarow = mysql_fetch_assoc($aikares);

		// Rivin toimitusaika
		if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K' or $yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
			$aikarow["toimitettuaika"] = $aikarow["toimaika"];
		}

		echo "<tr>
				<th>".t("Luontiaika")."</th>
				<th>".t("Ker‰ykseen")."</th>
				<th>".t("Tulostettu")."</th>
				<th>".t("Ker‰tty")."</th>
				<th>".t("Toimitettu")."</th>
				<th>".t("Laskutettu")."</th>
			</tr>";
		echo "<tr>
				<td>".tv1dateconv($laskurow["luontiaika"], "P", "")."</td>
				<td>".tv1dateconv($laskurow["h1time"], "P", "")."</td>
				<td>".tv1dateconv($laskurow["lahetepvm"], "P", "")."</td>
				<td>".tv1dateconv($aikarow["kerattyaika"], "P", "")."</td>
				<td>".tv1dateconv($aikarow["toimitettuaika"], "P", "")."</td>
				<td>".tv1dateconv($laskurow["laskutettu"], "P", "")."</td>
			</tr>";
	}

	$komm = "";

	if ($laskurow['myyja'] > 0) {
		//etsit‰‰n myyj‰n nimi
		$query  = "	SELECT nimi, kuka
					from kuka
					where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
		$myyresult = pupe_query($query);
		$myyrow = mysql_fetch_assoc($myyresult);

		if ($laskurow["tila"] == "O" or $laskurow["tila"] == "K") {
			$komm .= "\n".t("Ostaja").":###".$myyrow['nimi'];
		}
		else {
			$komm .= "\n".t("Myyj‰").":###".$myyrow['nimi'];
		}
	}

	if (!isset($myyrow) or $myyrow['kuka'] != $laskurow['laatija']) {
		//etsit‰‰n laatijan nimi
		$query  = "	SELECT nimi
					from kuka
					where kuka='$laskurow[laatija]' and yhtio='$kukarow[yhtio]'";
		$laaresult = pupe_query($query);
		$laarow = mysql_fetch_assoc($laaresult);

		if ($laarow["nimi"] == "") {
			$laarow["nimi"] = $laskurow["laatija"];
		}

		$komm .= "\n".t("Laatija").":###".$laarow['nimi'];
	}


	if ($verkkokauppa != "" and $muokkaa != "") {
		list($toimvv, $toimkk, $toimpp) = explode("-", $laskurow['toimaika']);

		$komm .= "\n".t("Toimitusaika").":###<input type = 'text' name = 'toimpp' value='$toimpp' size = '3'> - <input type = 'text' name = 'toimkk' value='$toimkk' size = '3'> - <input type = 'text' name = 'toimvv' value='$toimvv' size = '5'>";
	}
	elseif ($verkkokauppa != "") {
		$komm .= "\n".t("Toimitusaika").":###".tv1dateconv($laskurow["toimaika"]);
	}

	if ($verkkokauppa != "" and $muokkaa != "") {
		$komm .= "\n".t("Tilaaja").":###<input type = 'text' name='tilausyhteyshenkilo' value='$laskurow[tilausyhteyshenkilo]' size = '62'>";
	}
	elseif (trim($laskurow['tilausyhteyshenkilo']) != '') {
		$komm .= "\n".t("Tilaaja").":###".$laskurow['tilausyhteyshenkilo'];
	}

	if ($verkkokauppa != "" and $muokkaa != "") {
		$komm .= "\n".t("Tilauksenne").":###<input type = 'text' name='asiakkaan_tilausnumero' value='$laskurow[asiakkaan_tilausnumero]' size = '62'>";
	}
	elseif (trim($laskurow['asiakkaan_tilausnumero']) != '') {
		$komm .= "\n".t("Tilauksenne").":###".$laskurow['asiakkaan_tilausnumero'];
	}

	if ($verkkokauppa != "" and $muokkaa != "") {
		$komm .= "\n".t("Kohde").":###<input type = 'text' name='kohde' value='$laskurow[kohde]' size = '62'>";
	}
	elseif (trim($laskurow['kohde']) != '') {
		$komm .= "\n".t("Kohde").":###".$laskurow['kohde'];
	}

	if ($verkkokauppa != "" and $muokkaa != "") {
		$komm .= "\n".t("Lis‰tietoja").":###<input type = 'text' name='viesti' value='$laskurow[viesti]' size = '62'>";
	}
	elseif (trim($laskurow['viesti']) != '') {
		$komm .= "\n".t("Lis‰tietoja").":###".$laskurow['viesti'];
	}

	if ($laskurow['tilaustyyppi'] == 'R' or $laskurow['tilaustyyppi'] == 'T' or $laskurow['tilaustyyppi'] == 'A') {
		$query  = "	SELECT komm1, komm2
					from tyomaarays
					where yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[tunnus]'";
		$reklaresult = pupe_query($query);
		$reklarow = mysql_fetch_assoc($reklaresult);

		if (trim($reklarow['komm1']) != '') {
			$komm .= "\n".t("Tyˆn kuvaus").":###".wordwrap(str_replace("\n","\n###", $reklarow['komm1']), 90, "\n###");
		}

		if (trim($reklarow['komm2']) != '') {
			$komm .= "\n".t("Sis‰iset kommentit").":###".wordwrap(str_replace("\n","\n###", $reklarow['komm2']), 90, "\n###");
		}
	}

	if ($verkkokauppa != "" and $muokkaa != "") {
		$komm .= "\n".t("Kommentti")." 1:###<textarea name='comments' cols = '60' rows = '3'>".str_replace(array("\r\n", "\r", "\n"), " ", $laskurow["comments"])."</textarea>";
	}
	elseif (trim($laskurow['comments']) != '') {
		$komm .= "\n".t("Kommentti")." 1:###".wordwrap(str_replace("\n","\n###", $laskurow['comments']), 90, "\n###");
	}

	if ($laskurow["sisviesti2"] != "" and $kukarow["extranet"] == "") {

		$pregit = FALSE;

		if (strpos($_SERVER['SCRIPT_NAME'], "asiakkaantilaukset.php") !== FALSE and (preg_match_all("/Tilaus jakaantunut [0-9]* tilaukseksi: ([0-9, ]*) Tilaus:/", $laskurow['sisviesti2'], $tilausnumerotlista))) {
			foreach ($tilausnumerotlista[1] as $tilausnumerolis) {
				foreach (explode(",", $tilausnumerolis) as $tilnro) {

					$tilnro = (int) trim($tilnro);

					$laskurow["sisviesti2"] = preg_replace("/".$tilnro."([ ,])/", "<a href='asiakkaantilaukset.php?tee=NAYTATILAUS&toim=$toim&asiakasid=$asiakasid&toimittajaid=$toimittajaid&laskunro=$laskunro&lasku_yhtio=$lasku_yhtio&tunnus=$tilnro&ytunnus=$ytunnus&nimi=$nimi&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl'>$tilnro</a>\\1", $laskurow["sisviesti2"]);
					$pregit = TRUE;
				}
			}
		}

		if (!$pregit) $komm .= "\n".t("Kommentti")." 2:###".wordwrap(str_replace("\n","\n###", $laskurow['sisviesti2']), 90, "\n###");
		else $komm .= "\n".t("Kommentti")." 2:###".$laskurow["sisviesti2"];
	}

	if ($laskurow["sisviesti1"] != "") {
		$komm .= "\n".t("Kommentti")." 3:###".wordwrap(str_replace("\n","\n###", $laskurow['sisviesti1']), 90, "\n###");
	}

	if ($laskurow["sisviesti3"] != "" and $kukarow["extranet"] == "") {
		$komm .= "\n".t("Sis‰inen viesti").":###".wordwrap(str_replace("\n","\n###", $laskurow['sisviesti3']), 90, "\n###");
	}

	if ($laskurow["noutaja"] != '') {
		$komm .= "\n".t("Noutaja").":###$laskurow[noutaja]";
	}

	if ($komm != "") {
		$kommentit = explode("\n", trim($komm));

		foreach($kommentit as $kommentti) {

			list($o, $v) = explode("###", $kommentti);

			echo "<tr><th>$o</th><td colspan='5'>$v</td></tr>";
		}
	}

	// Katsotaan onko liitetiedostoja
	$liitequery = "	SELECT tunnus, selite
					FROM liitetiedostot USE INDEX (yhtio_liitos_liitostunnus)
					WHERE yhtio = '$kukarow[yhtio]'
					AND liitos = 'lasku'
					AND liitostunnus = '$laskurow[tunnus]'";
	$liiteres = pupe_query($liitequery);

	if (mysql_num_rows($liiteres) > 0) {
		$liitemaara = 1;
		echo "<tr><th>".t("Liitetiedosto")."</th><td colspan='5'>";

		while ($liiterow = mysql_fetch_assoc($liiteres)) {
			echo "<a href='{$palvelin2}view.php?id=$liiterow[tunnus]' target='Attachment'>".t("Liite")." $liitemaara $liiterow[selite]</a> ";
			$liitemaara++;
		}
		echo "</td></tr>";
	}

	if ($laskurow["tila"] == 'K') {
		$query = "	SELECT yhtio
					FROM oikeu
					WHERE yhtio	= '$kukarow[yhtio]'
					and kuka	= '$kukarow[kuka]'
					and nimi	= 'raportit.php'
					and alanimi = 'laskuhaku'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {

			$query = "	SELECT l2.ebid, l2.nimi, l2.summa, l2.tunnus
						FROM lasku l1
						JOIN lasku l2 ON l1.yhtio=l2.yhtio and l1.vanhatunnus=l2.tunnus
						WHERE l1.yhtio		= '$kukarow[yhtio]'
						and l1.tila			= 'K'
						and l1.laskunro		= '$laskurow[laskunro]'
						and l1.vanhatunnus  > 0";
			$muutkeikres = pupe_query($query);

			if (mysql_num_rows($muutkeikres) > 0) {
				echo "<tr><th colspan='6'>".t("Saapumiseen liitetyt ostolaskut:")."</th></tr>";
				echo "<tr><td colspan='6'>";

				$laskakak = 1;

				while ($muutkeikrow = mysql_fetch_assoc($muutkeikres)) {
					echo $laskakak.": <a href='{$palvelin2}raportit.php?toim=laskuhaku&tee=T&summa1=$muutkeikrow[tunnus]&lopetus=$PHP_SELF////toim=$toim//tee=$tee//asiakasid=$asiakasid//toimittajaid=$toimittajaid//laskunro=$laskunro//lasku_yhtio=$lasku_yhtio//tunnus=$tunnus//ytunnus=$ytunnus//nimi=$nimi//ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl/SPLIT/$lopetus'>$muutkeikrow[summa]</a> ".ebid($muutkeikrow['tunnus'])."<br>";
					$laskakak++;
				}

				echo "</td></tr>";
			}
		}
	}

	echo "</table>";

	if ($verkkokauppa != "" and $muokkaa != "") {
		echo "<br><input type = 'submit' name='tallenna' value='".t("Tallenna muutokset")."'></form><br>";
	}

	echo "<br>";
	echo "<strong>".t("Tilausrivit").":</strong><hr>";

	if ($laskurow["tila"] == 'U' or $laskurow["tila"] == 'K') {
		$where = " tilausrivi.uusiotunnus = '$tunnus' ";
		$pkusei = " use index (uusiotunnus_index) ";
	}
	elseif ($laskurow["tila"] == 'R') {
		$query = "	SELECT group_concat(tunnus) tunnukset
		 			FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = '$tunnus' and tila IN ('L','G','E','V','W','N','R')
					GROUP BY tunnusnippu";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);


		$where = " tilausrivi.otunnus IN ($row[tunnukset]) ";
	}
	else {
		$where = " tilausrivi.otunnus = '$tunnus' ";
	}

	$jarjestys = " toimaika, tilausrivi.tunnus ";

	if (strlen($ojarj) > 0) {
		$jarjestys = $ojarj;
	}
	else {
		$jarjestys = "otunnus, sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus";
	}

	// katotaan miten halutaan sortattavan
	$sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

	if ($yhtiorow["varaako_jt_saldoa"] != "") {
		$lisavarattu = " + tilausrivi.varattu";
	}
	else {
		$lisavarattu = "";
	}

	if ($laskurow["tila"] == 'O' or $laskurow["tila"] == 'K') {

		$query_ale_lisa = generoi_alekentta('O');

		$ale_query_select_lisa = generoi_alekentta_select('yhteen', 'O');

		// Keikat ja ostotilausket
		$query = "	SELECT tilausrivi.otunnus tilaus,
					tilausrivi.nimitys,
					concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
					tilausrivi.tuoteno,
					tuotteen_toimittajat.toim_tuoteno,
					round(tilausrivi.tilkpl, 4) tilattu_sis,
					round(tilausrivi.tilkpl*if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4) tilattu_ulk,
					round(tilausrivi.varattu,4) tulossa_sis,
					round(tilausrivi.varattu*if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4) tulossa_ulk,
					tilausrivi.kpl maara,
					concat_ws('<br>', if ('$laskurow[valkoodi]' != '$yhtiorow[valkoodi]', concat(tilausrivi.hinta, ' $laskurow[valkoodi]'), NULL), concat(round(tilausrivi.hinta*if ($laskurow[vienti_kurssi]=0, 1, $laskurow[vienti_kurssi]), '$yhtiorow[hintapyoristys]'), ' $yhtiorow[valkoodi]')) ostohinta,
					{$ale_query_select_lisa} ale,
					concat_ws('<br>', if ('$laskurow[valkoodi]' != '$yhtiorow[valkoodi]', concat(round((tilausrivi.varattu+tilausrivi.kpl)*tilausrivi.hinta*if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa},'$yhtiorow[hintapyoristys]'), ' $laskurow[valkoodi]'), NULL), concat(round((tilausrivi.varattu+tilausrivi.kpl)*tilausrivi.hinta*if ($laskurow[vienti_kurssi]=0, 1, $laskurow[vienti_kurssi])*if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa},'$yhtiorow[hintapyoristys]'), ' $yhtiorow[valkoodi]')) rivihinta,
					concat(round(tilausrivi.rivihinta, '$yhtiorow[hintapyoristys]'), ' $yhtiorow[valkoodi]') Varastonarvo,
					tilausrivi.kpl maara, tilausrivi.varattu,
					tilausrivi.kommentti,
					tilausrivi.tunnus,
					tilausrivi.uusiotunnus,
					tilausrivin_lisatiedot.hankintakulut,
					$sorttauskentta
					FROM tilausrivi
					JOIN tuote USING (yhtio, tuoteno)
					LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='$laskurow[liitostunnus]'
					LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
					WHERE $where
					and tilausrivi.yhtio='$kukarow[yhtio]'
					and tilausrivi.tyyppi in ('O' $deletedlisa)
					ORDER BY $jarjestys";
		$presult = pupe_query($query);

		echo "<table><tr>";

		if ($nippu > 0) {
			$linkkilisa = "$PHP_SELF?tee=NAYTATILAUS&toim=$toim&asiakasid=$asiakasid&toimittajaid=$toimittajaid&tunnus=$tunnus&nippu=$nippu&otunnus=$nippu&lopetus=$lopetus";
		}
		else {
			$linkkilisa = "$PHP_SELF?tee=NAYTATILAUS&toim=$toim&asiakasid=$asiakasid&toimittajaid=$toimittajaid&tunnus=$tunnus&ytunnus=$ytunnus&nimi=$nimi&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&lopetus=$lopetus";
		}

		echo "<th align='left'><a href = '{$linkkilisa}&ojarj=1'>" .t("Tilaus")."</a></th>";
		echo "<th align='left'><a href = '{$linkkilisa}&ojarj=2'>" .t("Nimitys")."</a></th>";
		echo "<th align='left'><a href = '{$linkkilisa}&ojarj=3'>" .t("Paikka")."</a></th>";
		echo "<th align='left'><a href = '{$linkkilisa}&ojarj=4'>" .t("Tuoteno")."</a></th>";
		echo "<th align='left'><a href = '{$linkkilisa}&ojarj=5'>" .t("Toim_tuoteno")."</a></th>";
		echo "<th align='left'><a href = '{$linkkilisa}&ojarj=6'>" .t("Tilattu")."<br>".t("sis/ulk")."</a></th>";
		echo "<th align='left'><a href = '{$linkkilisa}&ojarj=7'>" .t("Tulossa")."<br>".t("sis/ulk")."</a></th>";
		echo "<th align='left'><a href = '{$linkkilisa}&ojarj=8'>" .t("Varastossa")."</a></th>";
		echo "<th align='left'><a href = '{$linkkilisa}&ojarj=9'>" .t("Hinta")."</a></th>";
		echo "<th align='left'><a href = '{$linkkilisa}&ojarj=10'>".t("Ale")."</a></th>";
		echo "<th align='left'><a href = '{$linkkilisa}&ojarj=11'>".t("Rivihinta")."</a></th>";
		echo "<th align='left'><a href = '{$linkkilisa}&ojarj=12'>".t("Varastonarvo")."</a></th>";
		echo "</tr>";

		$summa = array();
		$osuus_kululaskuista_yhteensa 	= "";
		$osuus_eturahdista_yhteensa 	= "";
		$aputullimaara_yhteensa 		= "";
		$rivinlisakulu_yhteensa 		= "";

		while ($prow = mysql_fetch_assoc($presult)) {

			echo "<tr class='aktiivi'>";
			echo "<td valign='top'>$prow[tilaus]</td>";
			echo "<td valign='top'>$prow[nimitys]</td>";
			echo "<td valign='top'>$prow[paikka]</td>";
			echo "<td valign='top'><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($prow["tuoteno"])."&lopetus=$lopetus'>$prow[tuoteno]</a></td>";
			echo "<td valign='top'>$prow[toim_tuoteno]</td>";
			echo "<td align='right' valign='top'>".($prow["tilattu_sis"]*1)."<br>".($prow["tilattu_ulk"]*1)."</td>";
			echo "<td align='right' valign='top'>".($prow["tulossa_sis"]*1)."<br>".($prow["tulossa_ulk"]*1)."</td>";
			echo "<td align='right' valign='top'>".($prow["maara"]*1)."</td>";
			echo "<td align='right' nowrap valign='top'>$prow[ostohinta]</td>";
			echo "<td align='right' valign='top'>".($prow["ale"]*1)."</td>";
			echo "<td align='right' nowrap valign='top'>$prow[rivihinta]</td>";
			echo "<td align='right' nowrap valign='top'>$prow[Varastonarvo]</td>";

			$osuus_kululaskuista = $osuus_eturahdista = $tulliprossa = $aputullimaara = $rivinlisakulu = "";

			if (strpos($prow["hankintakulut"], "#") !== false) {
				list($osuus_kululaskuista, $osuus_eturahdista, $tulliprossa, $aputullimaara, $rivinlisakulu) = explode("#", $prow["hankintakulut"]);

				$osuus_kululaskuista_yhteensa += $osuus_kululaskuista;
				$osuus_eturahdista_yhteensa += $osuus_eturahdista;
				$aputullimaara_yhteensa += $aputullimaara;
				$rivinlisakulu_yhteensa += $rivinlisakulu;

				if ((float) $osuus_kululaskuista != 0) $prow["kommentti"] .= "\n".t("Kulut").": ".$osuus_kululaskuista;
				if ((float) $osuus_eturahdista != 0) $prow["kommentti"] .= "\n".t("Eturahti").": ".$osuus_eturahdista;
				if ((float) $tulliprossa != 0) $prow["kommentti"] .= "\n".t("Tulli%").": ".$tulliprossa;
				if ((float) $aputullimaara != 0) $prow["kommentti"] .= "\n".t("Tulli").": ".$aputullimaara;
				if ((float) $rivinlisakulu != 0) $prow["kommentti"] .= "\n".t("Lis‰kulu").": ".$rivinlisakulu;
			}

			echo "</tr>";

			//Haetaan sarjanumeron tiedot
			if ($prow["maara"] + $prow["varattu"] > 0){
				$sarjanutunnus = "ostorivitunnus";
			}
			else {
				$sarjanutunnus = "myyntirivitunnus";
			}

			$query = "	SELECT distinct sarjanumero
						from sarjanumeroseuranta
						where yhtio = '$kukarow[yhtio]'
						and tuoteno = '$prow[tuoteno]'
						and $sarjanutunnus='$prow[tunnus]'
						and sarjanumero != ''";
			$sarjares = pupe_query($query);

			$prow["kommentti"] = str_replace("\n", "<br>", trim($prow["kommentti"]));

			if ($prow["kommentti"] != '' and mysql_num_rows($sarjares) > 0){
				$prow["kommentti"] .= "<br>";
			}

			while ($sarjarow = mysql_fetch_assoc($sarjares)) {
				$prow["kommentti"] .= "# <a href='{$palvelin2}tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($prow["tuoteno"])."&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."&lopetus=$lopetus'>$sarjarow[sarjanumero]</a><br>";
			}

			if ($prow["kommentti"] != '') {
				echo "<tr>";
				echo "<td valign='top'>".t("Kommentti").":</td>";
				echo "<td colspan='".(mysql_num_fields($presult)-6)."'>".trim($prow["kommentti"])."</td>";
				echo "</tr>";
			}

			//	Jos ollaan laskulla joka ei ole ennakkolasku
			if ($prow["tuoteno"] == $yhtiorow["ennakkomaksu_tuotenumero"]) {
				$ennakkosumma += $prow["ostohinta"];
			}
			else {
				list($s1, $s2) = explode("<br>", $prow["rivihinta"]);

				list($ss1, $sv1) = explode(" ", trim($s1));
				list($ss2, $sv2) = explode(" ", trim($s2));

				$summa[$sv1] += $ss1;
				if ($sv2 != "") $summa[$sv2] += $ss2;
			}
		}

		foreach($summa as $val => $sum) {
			echo "<tr><td class='back' colspan='8'></td><td class='spec' colspan='2' align='right'>".t("Yhteens‰").":</td><td class='spec' align='right'>".hintapyoristys($sum)." $val</td></tr>";
		}

		if ($osuus_kululaskuista_yhteensa != "" or $osuus_eturahdista_yhteensa != "" or $aputullimaara_yhteensa != "" or $rivinlisakulu_yhteensa != "") {
			if ((float) $osuus_kululaskuista_yhteensa != 0) echo "<tr><td class='back' colspan='8'></td><td class='spec' colspan='2' align='right'>".t("Kulut").":</td><td class='spec' align='right'>".$osuus_kululaskuista_yhteensa."</td></tr>";
			if ((float) $osuus_eturahdista_yhteensa != 0) echo "<tr><td class='back' colspan='8'></td><td class='spec' colspan='2' align='right'>".t("Eturahti").":</td><td class='spec' align='right'>".$osuus_eturahdista_yhteensa."</td></tr>";
			if ((float) $aputullimaara_yhteensa != 0) echo "<tr><td class='back' colspan='8'></td><td class='spec' colspan='2' align='right'>".t("Tulli").":</td><td class='spec' align='right'>".$aputullimaara_yhteensa."</td></tr>";
			if ((float) $rivinlisakulu_yhteensa != 0) echo "<tr><td class='back' colspan='8'></td><td class='spec' colspan='2' align='right'>".t("Lis‰kulu").":</td><td class='spec' align='right'>".$rivinlisakulu_yhteensa."</td></tr>";
		}
		echo "</table>";
	}
	else {
		// Myynnit ja muut myyntipuolen jutut

		// katotaan miten halutaan sortattavan
		$tilauksen_jarjestys = $yhtiorow['tilauksen_jarjestys'];

		if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
			$sorttauslisa = "tuotetyyppi, ";
		}
		elseif ($toim == 'EXTRANET') {
			$sorttauslisa = "tilausrivin_lisatiedot.positio, ";
		}
		else {
			if ($tilauksen_jarjestys == '0' or $tilauksen_jarjestys == '1' or $tilauksen_jarjestys == '4' or $tilauksen_jarjestys == '5') {
				$sorttauslisa = "tilausrivi.perheid $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.perheid2 $yhtiorow[tilauksen_jarjestys_suunta],";
			}
			else {
				$sorttauslisa = "";
			}
		}

		$sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

		$query_ale_lisa = generoi_alekentta('M');

		if ($laskurow["tila"] == "L" or $laskurow["tila"] == "N" or $laskurow["tila"] == "U") {
			$tyyppilisa = " and tilausrivi.tyyppi in ('L' $deletedlisa) ";
		}
		else {
			// Onko k‰ytt‰j‰ll‰ oikeus n‰hd‰ valmistuksia tai reseptej‰
			$oikeu_t1 = tarkista_oikeus("tilauskasittely/tilaus_myynti.php", "VALMISTAVARASTOON");

			if ($yhtiorow["raaka_aineet_valmistusmyynti"] == "N") {
				$oikeu_t2 = FALSE;
			}
			else {
				$oikeu_t2 = tarkista_oikeus("tilauskasittely/tilaus_myynti.php", "VALMISTAASIAKKAALLE");
			}

			$oikeu_t3 = tarkista_oikeus("tilauskasittely/valmista_tilaus.php", "");
			$oikeu_t4 = tarkista_oikeus("tuoteperhe.php", "RESEPTI");

			if ($oikeu_t1 or $oikeu_t2 or $oikeu_t3 or $oikeu_t4) {
				$tyyppilisa = " and tilausrivi.tyyppi in ('E','L','G','V','W','T' $deletedlisa) ";
			}
			else {
				$tyyppilisa = " and tilausrivi.tyyppi in ('E','L','G','W','T' $deletedlisa) ";
			}
		}

		// Tilausrivit
		$query  = "	SELECT tilausrivin_lisatiedot.*, tilausrivi.*,
					tilausrivi.kpl m‰‰r‰,
					if (tuotetyyppi='K','Tyˆ','Varaosa') tuotetyyppi,
					tuote.myyntihinta,
					tuote.kehahin,
					tuote.sarjanumeroseuranta,
					tuote.myyntihinta_maara,
					tuote.status,
					tuote.ei_saldoa,
					tuote.valmistuslinja,
					if (tilausrivi.tyyppi='V', 0, tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) rivihinta,
					if (tilausrivi.tyyppi='V', 0, tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) arvo,
					if (tilausrivi.tyyppi='V', 0, if (tilausrivi.kpl!=0, tilausrivi.rivihinta * if (tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1), tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa})) summa,
					$sorttauskentta
					FROM tilausrivi
					LEFT JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno)
					LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
					WHERE $where
					and tilausrivi.yhtio='$kukarow[yhtio]'
					$tyyppilisa
					ORDER BY tilausrivi.otunnus, $sorttauslisa sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus";
		$result = pupe_query($query);

		$rivilaskuri = mysql_num_rows($result);

		if ($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
			$rivino = 0;
		}
		else {
			$rivino = $rivilaskuri+1;
		}

		echo "<table>";
		echo "<tr><th>#</th>";

		if ($laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "T") {

			$trivityyppi_result = t_avainsana("TRIVITYYPPI");

			if (mysql_num_rows($trivityyppi_result) > 0) {
				echo "<th>".t("Tyyppi")."</th>";

				$tyyppisarake = "OK";
			}
			else {
				$tyyppisarake = "";
			}
		}

		if ($kukarow["resoluutio"] == 'I' or $kukarow['extranet'] != '') {
			echo "<th>".t("Nimitys")."</th>";
		}

		if (($laskurow["tila"] != "T" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and ($kukarow['extranet'] == '' or $yhtiorow['varastopaikan_lippu'] != '')) {
			echo "	<th>".t("Paikka")."</th>";
		}

		echo "	<th>".t("Tuotenumero")."</th>
				<th>".t("Til. M‰‰r‰")."</th>
				<th>".t("M‰‰r‰")."</th>
				<th>".t("Var")."</th>
				<th>".t("Netto")."</th>";

		$kurssilisa = "";
		if ($vientikurssi != 1) {
			$kurssilisa = " (Hinta val.)";
		}

		$kurssilisa = "";
		if ($vientikurssi != 1) {
			$kurssilisa = " (Rivihinta val.)";
		}

		if ($kukarow['hinnat'] >= 0) echo "<th style='text-align:right;'>".t("Svh $kurssilisa")."</th>";

		if ($kukarow['hinnat'] == 0) {
			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				echo "<th style='text-align:right;'>".t("Ale")."{$alepostfix}%</th>";
			}
		}

		if ($kukarow['hinnat'] == 0) echo "<th style='text-align:right;'>".t("Hinta")."</th>";
		if ($kukarow['hinnat'] >= 0) echo "<th style='text-align:right;'>".t("Rivihinta $kurssilisa")."</th>";

		if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
			echo "<th style='text-align:right;'>".t("Kate")."</th>";
		}

		echo "<th style='text-align:right;'>".t("Alv%")."</th>";

		$naytetaan_valmistuslinja = false;

		if ($toim == "VALMISTUS") {
			$avainsanat = t_avainsana("VALMISTUSLINJA");

			echo "<th>".t("Reaalisaldo")."</th>";

			if (mysql_num_rows($avainsanat) > 0) {
				echo "<th style='text-align:right;'>".t("Valmistuslinja")."</th>";
				$naytetaan_valmistuslinja = true;
			}
		}

		echo "<td class='back'>&nbsp;</td>
		<td class='back'>&nbsp;</td>";


		echo "</tr>";

		$tuotetyyppi		= "";
		$positio_varattu	= "";
		$varaosatyyppi		= "";
		$vanhaid 			= "KALA";
		$borderlask			= 0;
		$pknum				= 0;
		$erikoistuote_tuoteperhe = array();
		$tuoteperhe_kayty 	= '';
		$edotunnus 			= 0;
		$tuotekyslinkki		= "";

		$summa_yht 		= 0;
		$arvo_yht  		= 0;
		$kate_yht  		= 0;
		$ennas_yht		= 0;
		$ennaa_yht		= 0;
		$rihin_yht		= 0;

		while ($row = mysql_fetch_assoc($result)) {
			$linjannimi = "";

			if (strpos($row['sorttauskentta'], '÷÷÷÷÷÷÷÷÷÷÷÷÷÷÷÷÷÷÷÷÷÷') !== FALSE) {
				$erikoistuote_tuoteperhe[$row['perheid']] = $row['sorttauskentta'];
			}

			if ($row["tuoteno"] == $yhtiorow["ennakkomaksu_tuotenumero"]) {
				$ennas_yht += $row["summa"];
				$ennaa_yht += $row["arvo"];
			}
			else {

				if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
					if ($row["osto_vai_hyvitys"] != "O" and ($laskurow["tapvm"] == '0000-00-00' or ($row["var"] != "P" and $row["var"] != "J" and $row["var"] != "S"))) {
						$rihin_yht += laskuval($row["rivihinta"], $laskurow["vienti_kurssi"]);
					}

					$arvo_yht  += round(laskuval($row["arvo"], $laskurow["vienti_kurssi"]), 2);
					$summa_yht += round(laskuval($row["summa"], $laskurow["vienti_kurssi"]), 2);
				}
				else {
					if ($row["osto_vai_hyvitys"] != "O" and ($laskurow["tapvm"] == '0000-00-00' or ($row["var"] != "P" and $row["var"] != "J" and $row["var"] != "S"))) {
						$rihin_yht += $row["rivihinta"];
					}

					$arvo_yht  += round($row["arvo"], 2);
					$summa_yht += round($row["summa"], 2);
				}
			}

			$tquery	= "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
			$tres	= pupe_query($tquery);
			$trow 	= mysql_fetch_assoc($tres); // haetaan tuotteen tiedot..

			//Haetaan sarjanumeron tiedot
			if ($laskurow["tila"] == 'O' or $laskurow["tila"] == 'K') {
				if ($row["m‰‰r‰"] + $row["varattu"] > 0){
					$sarjanutunnus = "ostorivitunnus";
				}
				else {
					$sarjanutunnus = "myyntirivitunnus";
				}
			}
			else {
				if ($laskurow["tila"] == "G") {
					$sarjanutunnus = "siirtorivitunnus";
				}
				elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["varattu"] + $row["m‰‰r‰"] < 0 and $row["var2"] == "EIOST") {
					$sarjanutunnus = "ostorivitunnus";
				}
				elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["varattu"] + $row["m‰‰r‰"] < 0) {
					$sarjanutunnus = "myyntirivitunnus";
				}
				elseif ($row["varattu"] + $row["m‰‰r‰"] < 0) {
					$sarjanutunnus = "ostorivitunnus";
				}
				else {
					$sarjanutunnus = "myyntirivitunnus";
				}
			}

			$query = "	SELECT distinct sarjanumero
						from sarjanumeroseuranta
						where yhtio = '$kukarow[yhtio]'
						and tuoteno = '$row[tuoteno]'
						and $sarjanutunnus='$row[tunnus]'
						and sarjanumero != ''";
			$sarjares = pupe_query($query);

			$row["kommentti"] = str_replace("\n", "<br>", trim($row["kommentti"]));

			if ($row["sarjanumeroseuranta"] != ''){
				if ($row["kommentti"] != '') {
					$row["kommentti"] .= "<br>";
				}
				else {
					$row["kommentti"] .= "&nbsp;";
				}
			}

			while ($sarjarow = mysql_fetch_assoc($sarjares)) {
				if ($kukarow['extranet'] == '') {
					$row["kommentti"] .= "# <a href='{$palvelin2}tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($row["tuoteno"])."&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."&lopetus=$lopetus'>$sarjarow[sarjanumero]</a><br>";
				}
				else {
					$row["kommentti"] .= "# $sarjarow[sarjanumero]<br>";
				}
			}

			if ($kukarow['extranet'] == '' and $row["toimitettuaika"] != '0000-00-00 00:00:00') {
				$row['kommentti'] .= "<br><font class='info'>".t("Toimitettu").": $row[toimitettu] ".tv1dateconv($row["toimitettuaika"], "P")."</font>";
			}

			if ($kukarow['extranet'] == '' and $row["kerattyaika"] != '0000-00-00 00:00:00') {
				$row['kommentti'] .= "<br><font class='info'>".t("Ker‰tty").": $row[keratty] ".tv1dateconv($row["kerattyaika"], "P")."</font>";
			}

			if ($laskurow["tilaustyyppi"] == "A") {
				if ($tuotetyyppi == "" and $row["tuotetyyppi"] == 'Tyˆ') {
					$tuotetyyppi = 1;

					echo "<tr><td class='back' colspan='10'><br></td></tr>";
					echo "<tr><td class='back' colspan='10'><strong>".t("Tyˆt")."</strong>:</td></tr>";
				}
			}

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
				if ($row["ale{$alepostfix}"] == 0.00) $row["ale{$alepostfix}"] = '';
			}

			if ($row["hyllyalue"] == "") {
				$row["hyllyalue"] = "";
				$row["hyllynro"]  = "";
				$row["hyllyvali"] = "";
				$row["hyllytaso"] = "";
			}

			if ($row["var"] == "P") {
				$class = " class='spec' ";
			}
			elseif ($row["var"] == "J") {
				$class = " class='green' ";
			}
			else {
				$class = '';
			}

			if ($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
				$rivino++;
			}
			else {
				$rivino--;
			}

			// Tuoteperheiden lapsille ei n‰ytet‰ rivinumeroa
			if ($tilauksen_jarjestys != 'M' and $tuoteperhe_kayty != $row['perheid'] and (($row['perheid'] != 0 and ($tilauksen_jarjestys == '1' or $tilauksen_jarjestys == '5' or ($tilauksen_jarjestys == '4' or $tilauksen_jarjestys == '0' and $erikoistuote_tuoteperhe[$row['perheid']] == $row['sorttauskentta']))) or $row["perheid"] == $row["tunnus"] or ($row["perheid2"] == $row["tunnus"] and $row["perheid"] == 0) or ($row["perheid2"] == -1 or ($row["perheid"] == 0 and $row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U"))))) {

				if ($row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")) {
					$pklisa = " and (tilausrivi.perheid = '$row[tunnus]' or tilausrivi.perheid2 = '$row[tunnus]' or tilausrivi.tunnus = '$row[tunnus]')";
				}
				elseif ($row["perheid"] == 0) {
					$pklisa = " and tilausrivi.perheid2 = '$row[perheid2]'";
				}
				else {
					$pklisa = " and (tilausrivi.perheid = '$row[perheid]' or tilausrivi.perheid2 = '$row[perheid]')";
				}

				$pkquery = "SELECT sum(if(tilausrivi.kommentti != ''
										or tuote.sarjanumeroseuranta!=''
										or (tilausrivi.kerattyaika != '0000-00-00 00:00:00' and '$kukarow[extranet]' = '')
										or (tilausrivi.toimitettuaika != '0000-00-00 00:00:00' and '$kukarow[extranet]' = ''), 1, 0)), count(*)
							FROM tilausrivi
							JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and $where
							$pklisa
							$tyyppilisa";
				$pkres = pupe_query($pkquery);
				$pkrow = mysql_fetch_row($pkres);

				$pknum = $pkrow[0] + $pkrow[1];
				$borderlask = $pkrow[1];

				echo "<tr>";

				$echorivino = $rivino;

				if ($yhtiorow['rivinumero_syotto'] != '') {
					if ($row['tilaajanrivinro'] != '' and $row['tilaajanrivinro'] != 0 and $echorivino != $row['tilaajanrivinro']) {
						$echorivino .= " &raquo; ($row[tilaajanrivinro])";
					}
				}

				// jos tuoteperheit‰ ei pidet‰ yhdess‰, ei tehd‰ rowspannia eik‰ bordereita
				if ($tilauksen_jarjestys != '0' and $tilauksen_jarjestys != '1' and $tilauksen_jarjestys != '4' and $tilauksen_jarjestys != '5' and $tilauksen_jarjestys != '8') {
					$pknum 		= 0;
					$borderlask = 0;

					if ($row["kommentti"] != "") {
						echo "<td valign='top' rowspan = '2' $class>$echorivino</td>";
					}
					else {
						echo "<td valign='top' $class>$echorivino</td>";
					}
				}
				else {
					if ($row['perheid'] != 0 and ($tilauksen_jarjestys == '1' or $tilauksen_jarjestys == '0' or $tilauksen_jarjestys == '4' or $tilauksen_jarjestys == '5')) {
						$tuoteperhe_kayty = $row['perheid'];
					}

					echo "<td valign='top' rowspan='$pknum' $class style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;'>$echorivino</td>";
				}
			}
			// normirivit tai jos tuoteperheit‰ ei pidet‰ yhdess‰, n‰ytet‰‰n lapsille rivinumerot
			elseif ($row["perheid"] == 0 and $row["perheid2"] == 0 or ($tilauksen_jarjestys != '0' and $tilauksen_jarjestys != '1' and $tilauksen_jarjestys != '4' and $tilauksen_jarjestys != '5' and $tilauksen_jarjestys != '8') or (($tilauksen_jarjestys == '0' or $tilauksen_jarjestys == '4') and $erikoistuote_tuoteperhe[$row['perheid']] == $row['sorttauskentta'] and $tuoteperhe_kayty != $row['perheid'])) {

				$echorivino = $rivino;

				if ($yhtiorow['rivinumero_syotto'] != '') {
					if ($row['tilaajanrivinro'] != '' and $row['tilaajanrivinro'] != 0 and $echorivino != $row['tilaajanrivinro']) {
						$echorivino .= " &raquo; ($row[tilaajanrivinro])";
					}
				}

				echo "<tr>";

				if ($row["kommentti"] != "") {
					echo "<td $class rowspan = '2' valign='top'>$echorivino";
				}
				elseif ($tilauksen_jarjestys == '1' and $row['perheid'] != 0) {
					echo "<td $class>&nbsp;</td>";
				}
				else {
					echo "<td  $class valign='top'>$echorivino";
				}

				echo "</td>";
				$borderlask		= 0;
				$pknum			= 0;
			}

			$vanhaid		= $row["perheid"];
			$trivityyulos	= "";
			$classlisa		= "";

			if ($borderlask == 1 and $pkrow[1] == 1 and $pknum == 1) {
				$classlisa = $class." style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
				$class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

				$borderlask--;
			}
			elseif (isset($pkrow) and $borderlask == $pkrow[1] and $pkrow[1] > 0) {
				$classlisa = $class." style='border-top: 1px solid; border-right: 1px solid;' ";
				$class    .= " style='border-top: 1px solid;' ";

				$borderlask--;
			}
			elseif ($borderlask == 1) {
				if ($row["kommentti"] != '') {
					$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
					$class    .= " style='font-style:italic; ' ";
				}
				else {
					$classlisa = $class." style='font-style:italic; border-bottom: 1px solid; border-right: 1px solid;' ";
					$class    .= " style='font-style:italic; border-bottom: 1px solid;' ";
				}

				$borderlask--;
			}
			elseif ($borderlask > 0 and $borderlask <= $pknum) {
				$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
				$class    .= " style='font-style:italic;' ";
				$borderlask--;
			}

			if (isset($tyyppisarake) and $tyyppisarake == "OK" and ($laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "T")) {

				echo "<td $class valign='top'>";

				if (mysql_num_rows($trivityyppi_result) > 0) {
					//annetaan valita tilausrivin tyyppi
					mysql_data_seek($trivityyppi_result, 0);

					while($trrow = mysql_fetch_assoc($trivityyppi_result)) {
						$sel = "";
						if ($trrow["selite"]==$row["positio"]) echo "$trrow[selitetark]";
					}
				}

				echo "</td>";
			}

			// Tuotteen nimitys n‰ytet‰‰n vain jos k‰ytt‰j‰n resoluution on iso
			if ($kukarow["resoluutio"] == 'I' or $kukarow['extranet'] != '') {
				echo "<td $class align='left' valign='top'>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";
			}

			if (($laskurow["tila"] != "T" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $kukarow['extranet'] == '') {

				$query = "	SELECT *
							FROM varastopaikat
							WHERE
							concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0')) and
							concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0'))
							and yhtio = '$kukarow[yhtio]'";
				$varesult = pupe_query($query);
				$varow = mysql_fetch_assoc($varesult);

				if ($varow['maa'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {
					echo "<td $class align='left' valign='top'><font class='error'><img src='{$palvelin2}pics/flag_icons/gif/".strtolower($varow['maa']).".gif'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font>";
				}
				elseif ($varow['maa'] != '' and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
					echo "<td $class align='left' valign='top'><font class='error'>".strtoupper($varow['maa'])." $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font>";
				}
				else {
					echo "<td $class align='left' valign='top'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]";
				}

				if (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and !in_array($row["var"], array('P','J','S','T','U'))) {
			   		$query	= "	SELECT sarjanumeroseuranta.sarjanumero era, sarjanumeroseuranta.parasta_ennen
			   					FROM sarjanumeroseuranta
			   					WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
			   					and myyntirivitunnus = '$row[tunnus]'
			   					LIMIT 1";
			   		$sarjares = pupe_query($query);
			   		$sarjarow = mysql_fetch_assoc($sarjares);

					echo ", $sarjarow[era]";

					if ($trow["sarjanumeroseuranta"] == "F") {
						echo " ".tv1dateconv($sarjarow["parasta_ennen"]);
					}
				}

				echo "</td>";
			}
			elseif (($laskurow["tila"] != "T" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $kukarow['extranet'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {
				$query = "	SELECT *
							FROM varastopaikat
							WHERE
							concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0')) and
							concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0'))
							and yhtio = '$kukarow[yhtio]'";
				$varesult = pupe_query($query);
				$varow = mysql_fetch_assoc($varesult);

				if ($varow['maa'] != '' ) {
					echo "<td $class align='left' valign='top'><img src='{$palvelin2}pics/flag_icons/gif/".strtolower($varow['maa']).".gif'></td>";
				}
				else {
					echo "<td $class align='left' valign='top'></td>";
				}
			}

			if ($kukarow['extranet'] == '') {
				echo "<td $class valign='top'><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."&lopetus=$lopetus'>$row[tuoteno]</a></td>";
			}
			else {
				echo "<td $class valign='top'>$row[tuoteno]</td>";
			}

			echo "<td $class align='right' valign='top' nowrap>".($row["tilkpl"]*1)."</td>";

			if ($row["m‰‰r‰"] != 0) {
				$kpl_ruudulle = $row['m‰‰r‰'] * 1;
			}
			elseif ($row["var"] == 'J') {
				$kpl_ruudulle = $row['jt'] * 1;
			}
			elseif ($row["var"] == 'S' or $row["var"] == 'T' or $row["var"] == 'U') {
				$kpl_ruudulle = $row['jt'] * 1;
			}
			elseif ($row["var"] == 'P') {
				$kpl_ruudulle = $row['tilkpl'] * 1;
			}
			else {
				$kpl_ruudulle = $row['varattu'] * 1;
			}

			echo "<td $class align='right' valign='top' nowrap>$kpl_ruudulle</td>";

			if ($toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA") {
				$classvar = $class;
			}
			else {
				if ($classlisa != "") {
					$classvar = $classlisa;
				}
				else {
					$classvar = $class;
				}
			}

			echo "<td $classvar align='center' valign='top'>$row[var]&nbsp;</td>";
			echo "<td $class align='center' valign='top'>$row[netto]&nbsp;</td>";

			$kpl   			= $row["varattu"]+$row["jt"]+$row['m‰‰r‰'];
			$myyntihinta	= hintapyoristys(tuotteen_myyntihinta($laskurow, $trow, 1));
			$bruttorivi		= $row["hinta"] * $kpl;

			if ($kukarow['hinnat'] == 1) {
				echo "<td $class align='right' valign='top' nowrap>$myyntihinta</td>";
			}
			elseif ($kukarow['hinnat'] == 0) {

				$kurssilisa = "";
				if ($vientikurssi != 1) {
					$kurssilisa = " (".hintapyoristys($row["hinta"]/$vientikurssi).")";
				}

				if ($myyntihinta != $row["hinta"]) $myyntihinta = hintapyoristys($myyntihinta)." $kurssilisa";
				else $myyntihinta = hintapyoristys($myyntihinta);

				echo "<td $class align='right' valign='top' nowrap>$myyntihinta</td>";

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					echo "<td $class align='right' valign='top' nowrap>".($row["ale{$alepostfix}"] * 1)."</td>";
				}

				echo "<td $class align='right' valign='top' nowrap>".hintapyoristys($row["hinta"]);

				if ($row["myyntihinta_maara"] > 1) {
					echo "<br>".hintapyoristys($row["hinta"]*$row["myyntihinta_maara"])." / $row[myyntihinta_maara]";
				}

				echo "</td>";
			}

			if ($kukarow['hinnat'] == 1) {
				echo "<td $class align='right' valign='top' nowrap>".hintapyoristys($bruttorivi)."</td>";
			}
			elseif ($kukarow['hinnat'] == 0) {

				if ($yhtiorow["alv_kasittely"] == "") {
					//verolliset hinnat
					$kurssilisa = "";
					if ($vientikurssi != 1) {
						$kurssilisa = " (".hintapyoristys($row["summa"]/$vientikurssi).")";
					}
					echo "<td $class align='right' valign='top' nowrap>".hintapyoristys($row["summa"])."$kurssilisa</td>";
				}
				else {
					$kurssilisa = "";
					if ($vientikurssi != 1) {
						$kurssilisa = " (".hintapyoristys($row["arvo"]/$vientikurssi).")";
					}
					echo "<td $class align='right' valign='top' nowrap>".hintapyoristys($row["arvo"])."$kurssilisa</td>";
				}
			}

			if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
				// T‰n rivin kate
				$kate = 0;

				if ($laskurow["tapvm"] != '0000-00-00') {

					if ($row["m‰‰r‰"] == 0) {
						$kate = "";
					}
					elseif ($row["rivihinta"] != 0) {
						if ($row["kate"] < 0) {
							$kate = sprintf('%.2f', -1 * abs(100 * $row["kate"] / $row["rivihinta"]))."%";
						}
						else {
							$kate = sprintf('%.2f', abs(100 * $row["kate"] / $row["rivihinta"]))."%";
						}
					}
					elseif ($row["kate"] != 0) {
						$kate = "-100.00%";
					}

					if ($row["tyyppi"] != "V" and $row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += $row["kate"];
				}
				elseif ($kukarow['extranet'] == '' and ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "U")) {
					if ($kpl > 0) {
						//Jos tuotteella yll‰pidet‰‰n in-out varastonarvo ja kyseess‰ on myynti‰
						$ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $row["tunnus"]);

						// Kate = Hinta - Ostohinta
						if ($row["rivihinta"] != 0) {
							$kate = sprintf('%.2f',100*($row["rivihinta"] - ($ostohinta * $kpl))/$row["rivihinta"])."%";
						}

						if ($row["tyyppi"] != "V" and $row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += ($row["rivihinta"] - ($ostohinta * $kpl));
					}
					elseif ($kpl < 0 and $row["osto_vai_hyvitys"] == "O") {
						//Jos tuotteella yll‰pidet‰‰n in-out varastonarvo ja kyseess‰ on OSTOA

						// Kate = 0
						$kate = "0%";
					}
					elseif ($kpl < 0 and $row["osto_vai_hyvitys"] == "") {
						//Jos tuotteella yll‰pidet‰‰n in-out varastonarvo ja kyseess‰ on HYVITYSTƒ

						//T‰h‰n hyvitysriviin liitetyt sarjanumerot
						$query = "	SELECT sarjanumero, kaytetty
									FROM sarjanumeroseuranta
									WHERE yhtio 		= '$kukarow[yhtio]'
									and ostorivitunnus 	= '$row[tunnus]'";
						$sarjares = pupe_query($query);

						$ostohinta = 0;

						while($sarjarow = mysql_fetch_assoc($sarjares)) {

							// Haetaan hyvitett‰vien myyntirivien kautta alkuper‰iset ostorivit
							$query  = "	SELECT tilausrivi.rivihinta/tilausrivi.kpl ostohinta
										FROM sarjanumeroseuranta
										JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
										WHERE sarjanumeroseuranta.yhtio 	= '$kukarow[yhtio]'
										and sarjanumeroseuranta.tuoteno 	= '$row[tuoteno]'
										and sarjanumeroseuranta.sarjanumero = '$sarjarow[sarjanumero]'
										and sarjanumeroseuranta.kaytetty 	= '$sarjarow[kaytetty]'
										and sarjanumeroseuranta.myyntirivitunnus > 0
										and sarjanumeroseuranta.ostorivitunnus   > 0
										ORDER BY sarjanumeroseuranta.tunnus
										LIMIT 1";
							$sarjares1 = pupe_query($query);
							$sarjarow1 = mysql_fetch_assoc($sarjares1);

							$ostohinta += $sarjarow1["ostohinta"];
						}

						// Kate = Hinta - Alkuper‰inen ostohinta
						if ($row["rivihinta"] != 0) {
							$kate = sprintf('%.2f',100 * ($row["rivihinta"]*-1 - $ostohinta)/$row["rivihinta"])."%";
						}
						else {
							$kate = "100.00%";
						}

						if ($row["tyyppi"] != "V" and $row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += ($row["rivihinta"]*-1 - $ostohinta);
					}
					else {
						$kate = "N/A";
					}
				}
				elseif ($kukarow['extranet'] == '') {

					if ($row["tyyppi"] == "V") {
						$kate = "";
					}
					elseif ($row["rivihinta"] != 0) {
						$kate = sprintf('%.2f',100*($row["rivihinta"] - (kehahin($row["tuoteno"])*($row["varattu"]+$row["jt"]+$row['m‰‰r‰'])))/$row["rivihinta"])."%";
					}
					elseif (kehahin($row["tuoteno"]) != 0) {
						$kate = "-100.00%";
					}

					if ($row["tyyppi"] != "V" and $row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += ($row["rivihinta"] - (kehahin($row["tuoteno"])*($row["varattu"]+$row["jt"]+$row['m‰‰r‰'])));
				}

				echo "<td $class align='right' valign='top' nowrap>$kate</td>";
			}

			if ($classlisa != "") {
				$classx = $classlisa;
			}
			else {
				$classx = $class;
			}

			if ($row["alv"] >= 600) {
				echo "<td $classx align='right' valign='top' nowrap>".t("K.V.")."</td>";
			}
			elseif ($row["alv"] >= 500) {
				echo "<td $classx align='right' valign='top' nowrap>".t("M.V.")."</td>";
			}
			else {
				echo "<td $classx align='right' valign='top' nowrap>".($row["alv"] * 1)."</td>";
			}

			if ($toim == "VALMISTUS") {

				if ($row["ei_saldoa"] != "") {

					echo "<td style='text-align:right;'>".t("Saldoton")."</td>";

					if ($naytetaan_valmistuslinja) {
						$linjannimi = t_avainsana("VALMISTUSLINJA",''," and avainsana.selite='{$row["valmistuslinja"]}'",'','',"selitetark");
						if ($linjannimi == "") $linjannimi = t("Ei Linjaa");
						echo "<td>{$linjannimi}</td>";
					}
				}
				else {

					list ($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"]);

					$tilauksessa = "SELECT sum(tilausrivi.varattu) tulossa
									FROM tilausrivi
									WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
									AND tilausrivi.tuoteno = '{$row["tuoteno"]}'
									AND tilausrivi.tyyppi = 'O'
									AND tilausrivi.varattu > 0";
					$tilres = pupe_query($tilauksessa);
					$tilrivi = mysql_fetch_assoc($tilres);

					$reaalisaldo = $myytavissa + $tilrivi["tulossa"];

					if ($row["status"] == "T") {
						echo "<td style='text-align:right;'>".t("Tehdastilaustuote")."</td>";
					}
					else {
						echo "<td style='text-align:right;'>$reaalisaldo</td>";
					}

					if ($naytetaan_valmistuslinja) {
						$linjannimi = t_avainsana("VALMISTUSLINJA",''," and avainsana.selite='{$row["valmistuslinja"]}'",'','',"selitetark");
						if ($linjannimi == "") $linjannimi = t("Ei Linjaa");
						echo "<td>{$linjannimi}</td>";
					}

					if ($yhtiorow["teeostotilaus_valmistuksen_tulosjonosta"] == "K" and $row["status"] == "T") {

						// haetaan tuotteen toimittajan tiedot ja datat
						$query2 = "	SELECT tt.liitostunnus, if (tt.jarjestys = 0, 9999, tt.jarjestys) sorttaus, tt.osto_era
									FROM tuotteen_toimittajat as tt
									WHERE tt.yhtio = '{$kukarow["yhtio"]}'
									AND tt.tuoteno = '{$row["tuoteno"]}'
									ORDER BY sorttaus
									LIMIT 1";
						$result2   = pupe_query($query2);
						$toimirow2 = mysql_fetch_assoc($result2);

						if (!isset($toimittajan_ostotilaus[$toimirow2["liitostunnus"]][$row["tuoteno"]]["tarve"])) $toimittajan_ostotilaus[$toimirow2["liitostunnus"]][$row["tuoteno"]]["tarve"] = 0;

						$toimittajan_ostotilaus[$toimirow2["liitostunnus"]][$row["tuoteno"]]["tarve"] += $row["tilkpl"];
						$toimittajan_ostotilaus[$toimirow2["liitostunnus"]][$row["tuoteno"]]["osto_era"] = $toimirow2['osto_era'];
						$toimittajan_ostotilaus[$toimirow2["liitostunnus"]][$row["tuoteno"]]["reaalisaldo"] = $reaalisaldo;
						$toimittajan_ostotilaus[$toimirow2["liitostunnus"]][$row["tuoteno"]]["nimitys"] = $row["nimitys"];
					}
				}
			}

			if ($verkkokauppa != "" and $muokkaa != "") {
				echo "<td class='back'><input type = 'button' onclick = \"if (confirm('".t("Oletko varma, ett‰ haluat mit‰tˆid‰ tilausrivin?")."')) { sndReq('selain', 'verkkokauppa.php?tee=poistarivi&rivitunnus=$row[tunnus]&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki'); }\" value='".t("Mit‰tˆi rivi")."'></td>";
			}

			echo "</tr>";

			if ($row['kommentti'] != '') {
				$cspan = 10;

				if ($kukarow['hinnat'] == 1) {
					$cspan-=2;
				}
				if ($kukarow['hinnat'] == -1) {
					$cspan-=4;
				}
				if ($kukarow["resoluutio"] == "I") {
					$cspan++;
				}
				if (($laskurow["tila"] != "T" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and ($kukarow['extranet'] == '' or $yhtiorow['varastopaikan_lippu'] != '')) {
					$cspan++;
				}
				if (isset($tyyppisarake) and $tyyppisarake == "OK" and ($laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "T")) {
					$cspan++;
				}
				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					$cspan++;
				}

				$cspan += $yhtiorow['myynnin_alekentat'] - 1;

				echo "<tr>";

				if ($borderlask == 0 and $pknum > 1) {
					$kommclass1 = " style='border-bottom: 1px solid; border-right: 1px solid;'";
				}
				elseif ($pknum > 0) {
					$kommclass1 = " style='border-right: 1px solid;'";
				}
				else {
					$kommclass1 = "";
				}

				echo "<td $kommclass1 colspan='$cspan' valign='top'>".t("Kommentti").":<br><font class='message'>".str_replace("\n", "<br>", $row["kommentti"])."</font></td>";
				echo "</tr>";
			}
		}

		if ($kukarow['hinnat'] >= 0) {
			$colspan = 6;

			if ($kukarow['hinnat'] == 1) {
				$colspan = $colspan - 2;
			}
			if ($kukarow["resoluutio"] == "I") {
				$colspan++;
			}
			if (($laskurow["tila"] != "T" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and ($kukarow['extranet'] == '' or $yhtiorow['varastopaikan_lippu'] != '')) {
				$colspan++;
			}
			if (isset($tyyppisarake) and $tyyppisarake == "OK" and ($laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "T")) {
				$colspan++;
			}
			if ($kukarow["extranet"] != "") {
				$colspan++;
			}
			if ($kukarow["extranet"] != "" and $yhtiorow['varastopaikan_lippu'] != '') {
				$colspan++;
			}

			$colspan += $yhtiorow['myynnin_alekentat'] - 1;

			echo "<tr><td class='back' colspan='$colspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Veroton yhteens‰").":</td><td class='spec' align='right'>".sprintf('%.2f',$arvo_yht)."</td><td class='spec'>$laskurow[valkoodi]</td></tr>";


			// Haetaan kaikki alvikannat riveilt‰
			$alvquery = "	SELECT distinct alv
							FROM tilausrivi
							WHERE $where
							and yhtio	= '$kukarow[yhtio]'
							$tyyppilisa
							and tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]'
							ORDER BY alv";
			$alvresult = pupe_query($alvquery);

			$query_ale_lisa = generoi_alekentta('M');

			while ($alvrow = mysql_fetch_assoc($alvresult)) {

				// Valuuttajutut kuntoon
				if ($alvrow["alv"] >= 500) {
					$aquery = "	SELECT
								round(sum(0),2) alvrivihinta,
								round(sum((tilausrivi.hinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*(tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl)*{$query_ale_lisa}),2) rivihinta
								FROM tilausrivi
								JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
								WHERE $where
								and tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.alv = '$alvrow[alv]'
								$tyyppilisa
								and tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]'";
				}
				else {
					$aquery = "	SELECT
								round(sum((tilausrivi.hinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa} * (tilausrivi.alv/100)),2) alvrivihinta,
								round(sum((tilausrivi.hinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}),2) rivihinta
								FROM tilausrivi
								JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
								WHERE $where
								and tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.alv = '$alvrow[alv]'
								$tyyppilisa
								and tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]'";
				}

				$aresult = pupe_query($aquery);
				$arow = mysql_fetch_assoc($aresult);

				if ($alvrow["alv"] >= 600) {
					$alvi = t("K.V.");
				}
				elseif ($alvrow["alv"] >= 500) {
					$alvi = t("M.V.");
				}
				else {
					$alvi = ($alvrow["alv"]*1)."%";
				}

				echo "<tr><td class='back' colspan='$colspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Arvonlis‰vero", $kieli)." (".sprintf('%.2f',$arow["rivihinta"])."):</td><td align='right' class='spec' nowrap>".sprintf('%.2f', $arow["alvrivihinta"])."</td><td class='spec'>$alvi</td></tr>";
			}

			echo "<tr><td class='back' colspan='$colspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Verollinen yhteens‰").":</td><td class='spec' align='right' nowrap>".sprintf('%.2f',$summa_yht)."</td><td class='spec'>$laskurow[valkoodi]</td></tr>";

			if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {

				if ($kate_yht < 0) {
					$ykate = @round(-1 * abs($kate_yht / $rihin_yht * 100), 2);
				}
				else {
					$ykate = @round(abs($kate_yht / $rihin_yht * 100), 2);
				}

				echo "<tr><td class='back' colspan='$colspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Kate yhteens‰").":</td><td class='spec' align='right' nowrap>".sprintf('%.2f',$ykate)."</td><td class='spec'>%</td></tr>";
			}

			if ($ennaa_yht != 0) {
				echo "<tr><td class='back'><br></td></tr>";
				echo "<tr><td class='back' colspan='$colspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Ennakkolaskutus").":</td><td class='spec' align='right' nowrap>".sprintf('%.2f',$ennaa_yht)."</td><td class='spec'>$laskurow[valkoodi]</td></tr>";

				//Haetaan kaikki alvikannat riveilt‰
				$alvquery = "	SELECT distinct alv
								FROM tilausrivi
								WHERE $where
								and yhtio	= '$kukarow[yhtio]'
								$tyyppilisa
								and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'
								ORDER BY alv";
				$alvresult = pupe_query($alvquery);

				while ($alvrow = mysql_fetch_assoc($alvresult)) {

					// Valuuttajutut kuntoon
					if ($alvrow["alv"] >= 500) {
						$aquery = "	SELECT
									round(sum(0),2) alvrivihinta,
									round(sum((tilausrivi.hinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*(tilausrivi.varattu+tilausrivi.kpl)*{$query_ale_lisa}),2) rivihinta
									FROM tilausrivi
									JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
									WHERE $where
									and tilausrivi.yhtio = '$kukarow[yhtio]'
									and tilausrivi.alv = '$alvrow[alv]'
									$tyyppilisa
									and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
					}
					else {
						$aquery = "	SELECT
									round(sum((tilausrivi.hinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa} * (tilausrivi.alv/100)),2) alvrivihinta,
									round(sum((tilausrivi.hinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) rivihinta
									FROM tilausrivi
									JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
									WHERE $where
									and tilausrivi.yhtio = '$kukarow[yhtio]'
									and tilausrivi.alv = '$alvrow[alv]'
									$tyyppilisa
									and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
					}

					$aresult = pupe_query($aquery);
					$arow = mysql_fetch_assoc($aresult);

					if ($alvrow["alv"] >= 600) {
						$alvi = t("K.V.");
					}
					elseif ($alvrow["alv"] >= 500) {
						$alvi = t("M.V.");
					}
					else {
						$alvi = ($alvrow["alv"]*1)."%";
					}

					echo "<tr><td class='back' colspan='$colspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Arvonlis‰vero", $kieli)." (".sprintf('%.2f',$arow["rivihinta"])."):</td><td align='right' class='spec' nowrap>".sprintf('%.2f', $arow["alvrivihinta"])."</td><td class='spec'>$alvi</td></tr>";
				}

				echo "<tr><td class='back' colspan='$colspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Yhteens‰").":</td><td align='right' class='spec' nowrap>".sprintf('%.2f',$ennas_yht)."</td><td class='spec'>$laskurow[valkoodi]</td></tr>";
			}
		}

		echo "</table>";
	}

	// Tehd‰‰n yhteistilaus.
	if ($yhtiorow["teeostotilaus_valmistuksen_tulosjonosta"] == "K") {

		if (!function_exists("tuot_toim")) {
			function tuot_toim($liitostunnus) {
				global $kukarow, $yhtiorow;
				// Haetaan tuotteen toimittajan liitostunnuksella toimitaulusta kaikki tiedot.
				// halutaan n‰ytt‰‰ rivill‰ t‰ydellinen toimittajanimi pelk‰n tunnuksen sijasta.

				$toimsql = "SELECT * from toimi where yhtio = '{$kukarow["yhtio"]}' and tunnus = '{$liitostunnus}'";
				$toimres = pupe_query($toimsql);

				if (mysql_num_rows($toimres) >1) {
					return FALSE;
				}
				else {
					$toimrivit = mysql_fetch_assoc($toimres);
					return $toimrivit;
				}
			}
		}

		echo "<font class='head'>".t("Tilaa valmistuksen tehdastilaustuotteet")."</font><hr>";

		$toimittajan_ostotilaus_indeksi = 0;

		if (count($toimittajan_ostotilaus) == 0) {
			echo "<font class='message'>".t("Ei tehdastilaustuotteita")."</font>";
		}

		echo "<table>";

		foreach ($toimittajan_ostotilaus as $toimittajan_tunnus => $toimittajan_ostotilaus_array) {

			$toimittaja = tuot_toim($toimittajan_tunnus);

			echo "<tr>";
			echo "<td class='back' colspan='5'><font class='message'><br>{$toimittaja["nimi"]}</font></th>";
			echo "<tr>";

			echo "<tr>";
			echo "<th>".t("Tuoteno")."</th>";
			echo "<th>".t("Nimitys")."</th>";
			echo "<th>".t("Tarve")."</th>";
			echo "<th>".t("Reaalisaldo")."</th>";
			echo "<th></th>";
			echo "<tr>";

			foreach ($toimittajan_ostotilaus_array as $tuotenumero => $tuote_array) {

				// Muuttujat
				$tarve = $tuote_array["tarve"];
				$ostoera = $tuote_array["osto_era"];
				$reaalisaldo = $tuote_array["reaalisaldo"];
				$nimitys = $tuote_array["nimitys"];

				echo "<tr>";
				echo "<td>$tuotenumero</td>";
				echo "<td>$nimitys</td>";
				echo "<td align='right'>$tarve</td>";
				echo "<td align='right'>$reaalisaldo</td>";

				// Jos ei ole ostoer‰‰, niin yksi
				if ((int) $ostoera == 0) {
					$ostoera = 1;
				}

				// Pyˆristet‰‰n tilattava m‰‰r‰ ylˆsp‰in seuraavaan ostoer‰‰n
				$tilattava_maara = ceil($tarve / $ostoera) * $ostoera;

				// mik‰li muutat n‰iden nimityksi‰, niin nimitykset pit‰‰ korjata myˆs lahetteen_tulostujono.php kohdan "NAYTATILAUS" javascriptiin.
				echo "<td>";
				echo "<input type='text' size='6' id='maara_$toimittajan_ostotilaus_indeksi' name='maara' value='$tilattava_maara'>";
				echo "<input type='hidden' id='tuoteno_$toimittajan_ostotilaus_indeksi' name='tuoteno' value='$tuotenumero'>";
				echo "<input type='hidden' id='toimittaja_$toimittajan_ostotilaus_indeksi' name='toimittaja' value='$toimittajan_tunnus'>";
				echo "</td>";
				echo "<td class='back'><input type='button' value='".t("Tee Ostotilaus")."' class='tilaa' id='submit_$toimittajan_ostotilaus_indeksi'></td>";
				echo "</tr>";

				$toimittajan_ostotilaus_indeksi++;
			}
		}

		echo "</table>";
	}

	//	N‰ytet‰‰n vaan t‰‰ tilaus jostain linkist‰..
	if ($tee != "NAYTA") {
		if ($laskurow["jaksotettu"] <> 0) {
			echo "<br><strong>".t("Maksusopimus").":</strong><hr>";

			if ($laskurow["jaksotettu"]<0) {
				$sopimus = (-1) * $laskurow["jaksotettu"];
			}
			else {
				$sopimus = $laskurow["jaksotettu"];
			}

			echo "<table><tr><th>#</th><th>".t("Osuus-%")."</th><th>".t("Maksuehto/Kuvaus")."</th><th>".t("Lis‰tiedot/Ohje")."</th><th>".t("Summa")."</th><th>".t("Laskunro")."</th><th>".t("Laskun pvm")."</th><th>".t("Er‰p‰iv‰")."</th><th>".t("Maksup‰iv‰")."</th></tr>";

			$query = "	SELECT maksupositio.*, round(osuus,0) osuus,
						concat_ws('<br>', maksuehto.teksti, kuvaus) maksuehto,
						concat(maksupositio.lisatiedot, '<br>', maksupositio.ohje) lisatiedot,
						if (lasku.laskunro>0,lasku.laskunro, '') laskunro,
						if (myre.tapvm!='0000-00-00', date_format(myre.tapvm, '%d.%m.%Y'), '') tapvm,
						if (myre.erpcm!='0000-00-00', date_format(myre.erpcm, '%d.%m.%Y'), '') erpcm,
						if (myre.mapvm!='0000-00-00', date_format(myre.mapvm, '%d.%m.%Y'), '') mapvm,
						lasku.summa, lasku.summa_valuutassa, lasku.valkoodi
						FROM maksupositio
						LEFT JOIN lasku ON lasku.yhtio=maksupositio.yhtio and lasku.tunnus=maksupositio.uusiotunnus
						LEFT JOIN lasku myre ON myre.yhtio=lasku.yhtio and myre.laskunro=lasku.laskunro and myre.tila='U'
						LEFT JOIN maksuehto on maksuehto.yhtio=maksupositio.yhtio and maksuehto.tunnus=maksupositio.maksuehto
						WHERE maksupositio.yhtio = '$kukarow[yhtio]' and otunnus = '$sopimus'
						ORDER BY tunnus";
			$maksusopres = pupe_query($query);
			$i=0;

			while ($maksusoprow = mysql_fetch_assoc($maksusopres)) {
				$i++;
				echo "<tr class='aktiivi'>
						<td>$i</td>
						<td align='center'>$maksusoprow[osuus]</td>
						<td>$maksusoprow[maksuehto]</td>
						<td>$maksusoprow[lisatiedot]</td>
						<td align='right'>$maksusoprow[summa]</td>
						<td align='center'>$maksusoprow[laskunro]</td>
						<td align='center'>$maksusoprow[tapvm] ";
						if ($maksusoprow["tapvm"] != '') {
							echo "($maksusoprow[summa] $yhtiorow[valkoodi] / $maksusoprow[summa_valuutassa] $maksusoprow[valkoodi])";
						}
						echo "</td>
						<td align='center'>$maksusoprow[erpcm]</td>
						<td align='center'>$maksusoprow[mapvm]</td>
					</tr>";
			}
			echo "</table><br>";
		}

		if ($laskurow["tunnusnippu"] > 0) {
			echo "<br><strong>".t("Liitetyt tilaukset").":</strong><hr>";

			if ($kukarow['hinnat'] == 0) $summaselli = " summa, ";
			else $summaselli = "";

			$query = "	SELECT tunnus tilaus, laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, toimaika, laatija, $summaselli tila, alatila
						FROM lasku use index (yhtio_tunnusnippu)
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnusnippu = '$laskurow[tunnusnippu]'
						and tila IN ('L','G','E','V','W','N','R','A')";
			$result = pupe_query($query);

			if (mysql_num_rows($result)!=0) {

				echo "<table>";
				echo "<tr>";
				for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
					echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
				}
				echo "<th align='left'>".t("Tyyppi")."</th>";
				echo "</tr>";

				while ($row = mysql_fetch_assoc($result)) {

					$ero="td";
					if ($tunnus==$row['tilaus']) $ero="th";

					echo "<tr class='aktiivi'>";

					for ($i=0; $i<mysql_num_fields($result)-2; $i++) {

						$fieldname = mysql_field_name($result, $i);

						if ($fildname == 'toimaika') {
							echo "<$ero>".tv1dateconv($row[$fieldname])."</$ero>";
						}
						else {
							echo "<$ero>".$row[$fieldname]."</$ero>";
						}
					}

					$laskutyyppi	= $row["tila"];
					$alatila		= $row["alatila"];

					//tehd‰‰n selv‰kielinen tila/alatila
					require "inc/laskutyyppi.inc";

					echo "<$ero>".t($laskutyyppi)." ".t($alatila)."</$ero>";

					echo "<form method='post'><td class='back'>
							<input type='hidden' name='tee' value='NAYTATILAUS'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='asiakasid' value='$asiakasid'>
							<input type='hidden' name='toimittajaid' value='$toimittajaid'>
							<input type='hidden' name='tunnus' value='$row[tilaus]'>
							<input type='hidden' name='tunnukset' value='$laskurow[tunnusnippu]'>
							<input type='hidden' name='otunnus' value='$laskurow[tunnusnippu]'>
							<input type='hidden' name='nippu' value='$laskurow[tunnusnippu]'>
							<input type='hidden' name='nimi' value='$nimi'>
							<input type='hidden' name='ppa' value='$ppa'>
							<input type='hidden' name='kka' value='$kka'>
							<input type='hidden' name='vva' value='$vva'>
							<input type='hidden' name='ppl' value='$ppl'>
							<input type='hidden' name='kkl' value='$kkl'>
							<input type='hidden' name='vvl' value='$vvl'>
							<input type='submit' value='".t("N‰yt‰ tilaus")."'></td></form>";

					echo "</tr>";
				}
				echo "</table><br>";
			}
			else {
				echo t("Ei tilauksia")."...<br><br>";
			}
		}
	}
