<?php
    ///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
	$useslave = 1;

	$lis = "";
	if ($kukarow["extranet"] != '') {
		$lis = " and liitostunnus = '$kukarow[oletus_asiakas]' ";
	}

	$query = "	SELECT *,
				if(tila = 'L' and laskunro>0, (select mapvm from lasku l where l.yhtio = lasku.yhtio and l.laskunro = lasku.laskunro and l.tila = 'U'), mapvm) mapvm
				FROM lasku
				WHERE tunnus = '$tunnus'
				$lis
				and yhtio='$kukarow[yhtio]'";
	$aresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($aresult) == 0) {
		echo "<b>".t("VIRHE: Tilausta ei lˆydy")."!</b>$query<br>";
		exit;
	}
	$laskurow = mysql_fetch_array($aresult);

	if ($laskurow['tila']=='L' or $laskurow['tila']=='G') {
		// haetaan eka rahtikirjat tietue, niin saadaan rahtikirjanumerot tietoon
		$query = "select * from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$laskurow[tunnus]' limit 1";
		$rakre = mysql_query($query) or pupe_error($query);
		$rakirrow = mysql_fetch_array($rakre);
		// spacet rivinvaihdoiksi niin n‰ytt‰‰ kivemmalta
		$rakirrow['rahtikirjanro'] = str_replace(" ", "<br>", trim($rakirrow['rahtikirjanro']));
	}

	//	Onko koko projekti maksettu
	if ($laskurow['tila']=='R') {

		$query = "	SELECT count(distinct laskunro) laskuja, sum(if(laskunro=0, 1, 0)) laskuttamatta,
					sum(if((select mapvm from lasku l where l.yhtio = lasku.yhtio and l.laskunro = lasku.laskunro and l.tila = 'U') = '0000-00-00', 1, 0)) maksamatta,
					max((select mapvm from lasku l where l.yhtio = lasku.yhtio and l.laskunro = lasku.laskunro and l.tila = 'U')) maksettu
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnus]' and tila = 'L'";
		$mares = mysql_query($query) or pupe_error($query);
		$marow = mysql_fetch_array($mares);
		if($marow["laskuttamatta"] == 0 and $marow["maksamatta"] == 0) {
			$laskurow["mapvm"] = $marow["maksettu"];
		}
	}

	echo "<table>";

	echo "<tr>
			<th>".t("Ytunnus")."</th>
			<th colspan='2'>".t("Laskutusosoite")."</th>
			<th colspan='3'>".t("Toimitusosoite")."</th>
			<th>".t("Toimitustapa")."</th>";

	if ($laskurow["tila"] == "O") {
		echo "<th>".t("Eturahti")."</th>";
	}

	echo "</tr>";

	if ($laskurow['kohdistettu']=='K') {
		$rahdinmaksaja = "<br>".t("L‰hett‰j‰ maksaa")."";
	}
	else {
		$rahdinmaksaja = "<br>".t("Vastaanottaja maksaa")."";
	}

	//	Haetaan kohdetiedot
	$query = "	SELECT concat_ws(' - ', asiakkaan_kohde.tunnus, asiakkaan_kohde.kohde) kohde, seuranta,
					yhteyshenkilo.nimi yhteyshenkilo,
					kuka.nimi projektipaallikko
				FROM laskun_lisatiedot
				LEFT JOIN asiakkaan_kohde ON laskun_lisatiedot.yhtio=asiakkaan_kohde.yhtio and laskun_lisatiedot.asiakkaan_kohde=asiakkaan_kohde.tunnus
				LEFT JOIN yhteyshenkilo ON laskun_lisatiedot.yhtio=yhteyshenkilo.yhtio and laskun_lisatiedot.yhteyshenkilo_tekninen=yhteyshenkilo.tunnus
				LEFT JOIN kuka ON laskun_lisatiedot.yhtio=kuka.yhtio and laskun_lisatiedot.projektipaallikko=kuka.kuka
				WHERE laskun_lisatiedot.yhtio='{$kukarow["yhtio"]}' and laskun_lisatiedot.otunnus='$laskurow[tunnus]' and seuranta!=''";
	$lisares=mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($lisares)>0) {
		$lisarow=mysql_fetch_array($lisares);
		$lisarivi="	<tr>
						<th>".t("Seuranta")."</th>
						<th colspan='2'>".t("Kohde")."</th>
						<th colspan='2'>".t("Yhteyshenkilo")."</th>
						<th colspan='2'>".t("Projektipaallikko")."</th>
					</tr>
					<tr>
						<td>{$lisarow["seuranta"]}</td>
						<td colspan='2'>{$lisarow["kohde"]}</td>
						<td colspan='2'>{$lisarow["yhteyshenkilo"]}</td>
						<td colspan='2'>{$lisarow["projektipaallikko"]}</td>
					</tr>";
	}
	else {
		$lisarivi="";
	}

	echo "<tr>
			<td>$laskurow[ytunnus]</td>
			<td colspan='2'>$laskurow[nimi] $laskurow[nimitark]<br> $laskurow[osoite]<br> $laskurow[postino] $laskurow[postitp]<br>".maa($laskurow["maa"])."</td>
			<td colspan='3'>$laskurow[toim_nimi] $laskurow[toim_nimitark]<br> $laskurow[toim_osoite]<br> $laskurow[toim_postino] $laskurow[toim_postitp]<br>".maa($laskurow["toim_maa"])."</td>
			<td>$laskurow[toimitustapa]<br>$laskurow[toimitusehto]</td>";

	if ($laskurow["tila"] == "O") {
		echo "<td>$laskurow[rahti_etu]</td>";
	}

	echo "</tr>$lisarivi";

	echo "<tr>
			<th>".t("Tilausnumero")."</th>
			<th colspan='2'>".t("Tila")."</th>
			<th>".t("Vienti")."</th>
			<th>".t("Erikoisalennus")."</th>
			<th>".t("Toimitusaika")."</th>
			<th>".t("Rahtikirjanro")."</th>";

	if ($laskurow["tila"] == "O") {
		echo "<th>".t("Rahti")."</th>";
	}

	echo "</tr>";

	echo "<tr>
			<td>$laskurow[tunnus]</td>";

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
		$vteksti=t("Vienti ei-EU");
	}
	elseif($laskurow["vienti"] == "E") {
		$vteksti=t("Vienti EU");
	}
	else {
		$vteksti=t("Kotimaa");
	}

	echo "	<td colspan='2'>".t("$laskutyyppi")." ".t("$alatila")."</td>
			<td>$vteksti</td>
			<td>".str_replace(".",",",$laskurow['erikoisale'])."</td>
			<td>".tv1dateconv($laskurow["toimaika"])."</td>";

	$dpdtun = ${$kukarow['yhtio'].'_dpdtun'};
	$dpdss 	= ${$kukarow['yhtio'].'_dpdss'};

	$possi = strpos(strtoupper($laskurow['toimitustapa']),"DPD");

	// postin rahtikirjat alkaa JJFI, ja tehd‰‰n niille linkki suoraan postin seurantaan
	if (strpos($rakirrow['rahtikirjanro'],"JFI")) {
		$linkurl = "";
		$jjfi = split("<br>", trim($rakirrow['rahtikirjanro']));

		foreach ($jjfi as $nro) {
			$linkurl .= "<a target=newikkuna href='http://www.verkkoposti.com/e3/TrackinternetServlet?lang=fi&LOTUS_hae=Hae&LOTUS_side=1&LOTUS_trackId=$nro&LOTUS_hae=Hae'>$nro</a><br>";
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
			$sscc .= sprintf("%08.08s",$yhtiorow["ytunnus"]);
			$sscc .= sprintf('%06.06d', substr($rakirrow['rahtikirjanro'], -6));
			$sscc .= sprintf('%02.02d', $dpdi);
			$loppu = tarkiste($sscc);
			$sscc = $sscc.$loppu;

			$linkurl .= "<a target=newikkuna href='http://extranet.dpd.de/cgi-bin/delistrack.acl?typ=4&model=web_login.gws&username=$dpdtun&password=$dpdss&pknr=$sscc'>$sscc</a><br>";

		}

		$linkurl = substr($linkurl,0,-4); // vika br pois

	}
	else {
		$linkurl = $rakirrow['rahtikirjanro'];
	}

	echo "	<td>$linkurl</td>";

	if ($laskurow["tila"] == "O") {
		echo "<td>$laskurow[rahti]</td>";
	}

	echo "</tr>";

	echo "<tr>
			<th>".t("Laskunro")."</th>
			<th colspan='2'>".t("Laskun pvm")."</th>
			<th>".t("Er‰p‰iv‰")."</th>
			<th>".t("Maksup‰iv‰")."</th>
			<th>".t("Luontiaika")."</th>
			<th>".t("ALV")."</th>";

	if ($laskurow["tila"] == "O") {
		echo "<th>".t("Huolinta")."</th>";
	}

	echo "<tr>
			<td>$laskurow[laskunro]</td>
			<td colspan='2'>".tv1dateconv($laskurow["tapvm"])."</td>
			<td>".tv1dateconv($laskurow["erpcm"])."</td>
			<td>".tv1dateconv($laskurow["mapvm"])."</td>
			<td>".tv1dateconv($laskurow["luontiaika"])."</td>
			<td>".str_replace(".",",",$laskurow['alv'])." %</td>";

	if ($laskurow["tila"] == "O") {
		echo "<td>$laskurow[rahti_huolinta]</td>";
	}

	$komm = "";
	
	if ($laskurow['myyja'] > 0) {
		//etsit‰‰n myyj‰n nimi
		$query  = "	SELECT nimi, kuka
					from kuka
					where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
		$myyresult = mysql_query($query) or pupe_error($query);
		$myyrow = mysql_fetch_array($myyresult);
		
		$komm .= "\n".t("Myyj‰").":###".$myyrow['nimi'];
	}
	
	if ($myyrow['kuka'] != $laskurow['laatija']) {
		//etsit‰‰n laatijan nimi
		$query  = "	SELECT nimi
					from kuka
					where kuka='$laskurow[laatija]' and yhtio='$kukarow[yhtio]'";
		$laaresult = mysql_query($query) or pupe_error($query);
		$laarow = mysql_fetch_array($laaresult);

		$komm .= "\n".t("Laatija").":###".$laarow['nimi'];
	}
	
	if (trim($laskurow['tilausyhteyshenkilo']) != '') {
		$komm .= "\n".t("Tilaaja").":###".$laskurow['tilausyhteyshenkilo'];
	}

	if (trim($laskurow['asiakkaan_tilausnumero']) != '') {
		$komm .= "\n".t("Tilauksenne").":###".$laskurow['asiakkaan_tilausnumero'];
	}

	if (trim($laskurow['kohde']) != '') {
		$komm .= "\n".t("Kohde").":###".$laskurow['kohde'];
	}

	if (trim($laskurow['viesti']) != '') {
		$komm .= "\n".t("Lis‰tietoja").":###".$laskurow['viesti'];
	}

	if (trim($laskurow['comments']) != '') {
		$komm .= "\n".t("Kommentti")." 1:###".wordwrap(str_replace("\n","\n###", $laskurow['comments']), 90, "\n###");
	}

	if ($laskurow["sisviesti2"] != "") {
		$komm .= "\n".t("Kommentti")." 2:###".wordwrap(str_replace("\n","\n###", $laskurow['sisviesti2']), 90, "\n###");
	}

	if ($laskurow["sisviesti1"] != "") {
		$komm .= "\n".t("Kommentti")." 3:###".wordwrap(str_replace("\n","\n###", $laskurow['sisviesti1']), 90, "\n###");
	}

	if ($laskurow["noutaja"] != '') {
		$komm .= "\n".t("Noutaja").":###$laskurow[noutaja]";
	}

	if ($komm != "") {
		$kommentit = explode("\n", trim($komm));

		foreach($kommentit as $kommentti) {

			list($o, $v) = explode("###", $kommentti);

			echo "<tr><th>$o</th><td colspan='8'>$v</td></tr>";
		}
	}

	// Katsotaan onko liitetiedostoja
	$liitequery = "	SELECT tunnus
					FROM liitetiedostot USE INDEX (yhtio_liitos_liitostunnus)
					WHERE yhtio='$kukarow[yhtio]'
					AND liitos='myyntilasku'
					AND liitostunnus='$laskurow[tunnus]'";
	$liiteres = mysql_query($liitequery) or pupe_error($liitequery);
	$liiterow = mysql_fetch_array($liiteres);

	if (mysql_num_rows($liiteres) > 0) {
		$kuvapalvelin = substr($palvelin2, 0, -1);
		echo "<tr><th>".t("Liitetiedosto")."</th><td colspan='8'><a href='$kuvapalvelin/view.php?id=$liiterow[tunnus]' target='_blank'>".t("N‰yt‰")."</a></td></tr>";
	}

	if ($laskurow["tila"] == 'K') {
		$query = "	SELECT yhtio
					FROM oikeu
					WHERE yhtio	= '$kukarow[yhtio]'
					and kuka	= '$kukarow[kuka]'
					and nimi	= 'raportit.php'
					and alanimi = 'laskuhaku'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			$query = "	SELECT l2.ebid, l2.nimi, l2.summa, l2.tunnus
						FROM lasku l1
						JOIN lasku l2 ON l1.yhtio=l2.yhtio and l1.vanhatunnus=l2.tunnus
						WHERE l1.yhtio		= '$kukarow[yhtio]'
						and l1.tila			= 'K'
						and l1.laskunro		= '$laskurow[laskunro]'
						and l1.vanhatunnus  > 0";
			$muutkeikres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($muutkeikres) > 0) {
				echo "<tr><th colspan='9'>".t("Keikkaan liitetyt ostolaskut:")."</th></tr>";
				echo "<tr><td colspan='9'>";

				while ($muutkeikrow = mysql_fetch_array($muutkeikres)) {
					$ebid = $muutkeikrow['ebid'];

					$laskakak = 1;

					echo $laskakak.": <a href='".$palvelin2."raportit.php?toim=laskuhaku&tee=T&summa1=$muutkeikrow[tunnus]'>$muutkeikrow[summa]</a> ".ebid($muutkeikrow['tunnus'])."<br>";
				}

				echo "</td></tr>";
			}
		}
	}

	echo "</table>";

	echo "<br>";
	echo "<b>".t("Tilausrivit").":</b><hr>";

	if ($laskurow["tila"] == 'U' or $laskurow["tila"] == 'K') {
		$where = " tilausrivi.uusiotunnus = '$tunnus' ";
	}
	elseif ($laskurow["tila"] == 'R') {
		$query = "	SELECT group_concat(tunnus) tunnukset
		 			FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = '$tunnus' and tila IN ('L','G','E','V','W','N','R')
					GROUP BY tunnusnippu";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);


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
		// Keikat ja ostotilausket
		$query = "	SELECT otunnus tilaus, ".nimitys('select','tilausrivi').", concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
					tilausrivi.tuoteno, toim_tuoteno,
					concat_ws('/', round(tilkpl,4), round(tilkpl*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tilattu sis/ulk',
					concat_ws('/', round(varattu,4),round(varattu*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tulossa sis/ulk',
					kpl,
					round(hinta*if($laskurow[vienti_kurssi]=0, 1, $laskurow[vienti_kurssi]), '$yhtiorow[hintapyoristys]') hinta, ale,
					if(rivihinta != 0, round(rivihinta, '$yhtiorow[hintapyoristys]'), round((varattu+kpl)*hinta*if($laskurow[vienti_kurssi]=0, 1, $laskurow[vienti_kurssi])*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*(1-(ale/100)),'$yhtiorow[hintapyoristys]')) rivihinta,
					'$laskurow[valkoodi]' valuutta,
					tilausrivi.kommentti, tilausrivi.tunnus,
					tilausrivi.uusiotunnus,
					$sorttauskentta
					FROM tilausrivi
					JOIN tuote USING (yhtio, tuoteno)
					".nimitys('join','tilausrivi')."
					LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='$laskurow[liitostunnus]'
					WHERE $where
					and tilausrivi.yhtio='$kukarow[yhtio]'
					and tyyppi='O'
					ORDER BY $jarjestys";
		$presult = mysql_query($query) or pupe_error($query);

		echo "<table><tr>";

		for ($i = 0; $i < mysql_num_fields($presult)-4; $i++) {
			$ojarj=$i+1; // sortti numeron mukaan niin ei tuu onglemia
			if($nippu>0) {
				echo "<th align='left'><a href = '$PHP_SELF?tee=NAYTATILAUS&toim=$toim&asiakasid=$asiakasid&toimittajaid=$toimittajaid&tunnus=$tunnus&nippu=$nippu&otunnus=$nippu&ojarj=$ojarj'>".t(mysql_field_name($presult,$i))."</a></th>";
			}
			else {
				echo "<th align='left'><a href = '$PHP_SELF?tee=NAYTATILAUS&toim=$toim&asiakasid=$asiakasid&toimittajaid=$toimittajaid&tunnus=$tunnus&ytunnus=$ytunnus&nimi=$nimi&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&ojarj=$ojarj'>".t(mysql_field_name($presult,$i))."</a></th>";
			}
		}

		echo "</tr>";

		$summa = 0;

		while ($prow = mysql_fetch_array ($presult)) {

			echo "<tr class='aktiivi'>";
			for ($i=0; $i < mysql_num_fields($presult)-4; $i++) {
				if (mysql_field_name($presult,$i) == 'kerattyaika' or mysql_field_name($presult,$i) == 'toimaika') {
					echo "<td>".tv1dateconv($prow[$i],"pitka")."</td>";
				}
				elseif (mysql_field_name($presult,$i) == 'kpl' or mysql_field_name($presult,$i) == 'tilkpl' or mysql_field_name($presult,$i) == 'jt' or mysql_field_name($presult,$i) == 'ale') {
					echo "<td align='right'>".($prow[$i]*1)."</td>";
				}
				elseif (mysql_field_name($presult,$i) == 'tuoteno') {
					echo "<td><a href='".$palvelin2."tuote.php?tee=Z&tuoteno=$prow[$i]'>$prow[$i]</a></td>";
				}
				elseif (mysql_field_name($presult,$i) == 'alv') {
					echo "<td align='right'>".($prow[$i]*1)."</td>";
				}
				elseif (is_numeric($prow[$i])) {
					echo "<td align='right' nowrap>$prow[$i]</td>";
				}
				else {
					echo "<td>$prow[$i]</td>";
				}
			}
			echo "</tr>";

			//Haetaan sarjanumeron tiedot
			if ($prow["kpl"] + $prow["varattu"] > 0){
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
			$sarjares = mysql_query($query) or pupe_error($query);

			$prow["kommentti"] = str_replace("\n", "<br>", $prow["kommentti"]);

			if ($prow["kommentti"] != '' and mysql_num_rows($sarjares) > 0){
				$prow["kommentti"] .= "<br>";
			}
			while($sarjarow = mysql_fetch_array($sarjares)) {
				$prow["kommentti"] .= "# <a href='".$palvelin2."tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$prow[tuoteno]&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."'>$sarjarow[sarjanumero]</a><br>";
			}

			if ($prow["kommentti"] != '') {
				echo "<tr>";
				echo "<td valign='top'>".t("Kommentti").":</td>";
				echo "<td colspan='".(mysql_num_fields($presult)-6)."'>$prow[kommentti]</td>";
				echo "</tr>";
			}

			//	Jos ollaan laskulla joka ei ole ennakkolasku
			if($prow["tuoteno"] == $yhtiorow["ennakkomaksu_tuotenumero"]) {
				$ennakkosumma += $prow["rivihinta"];
			}
			else {
				$summa += $prow["rivihinta"];
			}
		}

		echo "<tr><td class='back' colspan='10' align='right'>".t("Yhteens‰").":</td><td class='back' align='right'>".sprintf('%.2f',$summa)."</td><td class='back'>$laskurow[valkoodi]</td></tr>";
	}
	else {
		// Myynnit ja muut myyntipuolen jutut

		// katotaan miten halutaan sortattavan
		$sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

		// Tilausrivit
		$query  = "	SELECT tilausrivin_lisatiedot.*, tilausrivi.*,
					if (tuotetyyppi='K','Tyˆ','Varaosa') tuotetyyppi,
					tuote.myyntihinta,
					tuote.kehahin,
					tuote.sarjanumeroseuranta,
					if(tilausrivi.kpl!=0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100))) rivihinta,
					if(tilausrivi.kpl!=0, tilausrivi.rivihinta, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100))) arvo,
					if(tilausrivi.kpl!=0, tilausrivi.rivihinta * if(tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1), tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100))) summa,
					$sorttauskentta
					FROM tilausrivi
					LEFT JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno)
					LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
					WHERE $where
					and tilausrivi.yhtio='$kukarow[yhtio]'
					and tilausrivi.tyyppi in ('E','L','G','V','W','T')
					ORDER BY sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus";
		$result = mysql_query($query) or pupe_error($query);

		$rivilaskuri = mysql_num_rows($result);

		if($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
			$rivino = 0;
		}
		else {
			$rivino = $rivilaskuri+1;
		}

		echo "<br><table>";
		echo "<tr><td class='back' colspan='5'><font class='message'>";
		echo t("Tilausrivit").":</font>";
		echo "</td></tr>";
		echo "<tr><th>".t("#")."</th>";

		if ($laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "T" or $yhtiorow['tilauksen_kohteet'] == 'K') {

			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'TRIVITYYPPI'
						ORDER BY jarjestys, selite";
			$trivityyppi_result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($trivityyppi_result) > 0) {
				echo "<th>".t("Tyyppi")."</th>";
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
				<th>".t("Var")."</th>";


		echo "<th>".t("Netto")."</th>";

		echo "<th style='text-align:right;'>".t("Svh")."</th>";

		if ($kukarow['hinnat'] == 0) {
			echo "<th style='text-align:right;'>".t("Ale%")."</th>";
			echo "<th style='text-align:right;'>".t("Hinta")."</th>";
		}

		echo "<th style='text-align:right;'>".t("Rivihinta")."</th>";

		if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
			echo "<th style='text-align:right;'>".t("Kate")."</th>";
		}

		echo "	<th style='text-align:right;'>".t("Alv%")."</th>
				<td class='back'>&nbsp;</td>
				<td class='back'>&nbsp;</td>
				</tr>";

		$tuotetyyppi	= "";
		$varaosatyyppi	= "";
		$vanhaid 		= "KALA";
		$borderlask		= 0;
		$pknum			= 0;

		$summa_yht 		= 0;
		$arvo_yht  		= 0;
		$kate_yht  		= 0;
		$ennas_yht		= 0;
		$ennaa_yht		= 0;
		$rihin_yht		= 0;
		
		while ($row = mysql_fetch_array($result)) {

			if ($row["tuoteno"] == $yhtiorow["ennakkomaksu_tuotenumero"]) {
				$ennas_yht += $row["summa"];
				$ennaa_yht += $row["arvo"];
			}
			else {				
				if ($row["osto_vai_hyvitys"] != "O" and ($laskurow["tapvm"] == '0000-00-00' or ($row["var"] != "P" and $row["var"] != "J" and $row["var"] != "S"))) {
					$rihin_yht += $row["rivihinta"];
				}
				
				$arvo_yht  += $row["arvo"];
				$summa_yht += $row["summa"];
			}

			$tquery	= "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
			$tres	= mysql_query($tquery) or pupe_error($tquery);
			$trow 	= mysql_fetch_array($tres); // haetaan tuotteen tiedot..

			//Haetaan sarjanumeron tiedot
			if ($laskurow["tila"] == 'O' or $laskurow["tila"] == 'K') {
				if ($row["kpl"] + $row["varattu"] > 0){
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
				elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["varattu"] + $row["kpl"] < 0) {
					$sarjanutunnus = "myyntirivitunnus";
				}
				elseif ($row["varattu"] + $row["kpl"] < 0) {
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
			$sarjares = mysql_query($query) or pupe_error($query);

			$row["kommentti"] = str_replace("\n", "<br>", $row["kommentti"]);

			if ($row["sarjanumeroseuranta"] != ''){
				if ($row["kommentti"] != '') {
					$row["kommentti"] .= "<br>";
				}
				else {
					$row["kommentti"] .= "&nbsp;";
				}	
			}
			
			while($sarjarow = mysql_fetch_array($sarjares)) {
				if($kukarow['extranet'] == '') {
					$row["kommentti"] .= "# <a href='".$palvelin2."tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=$row[tuoteno]&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."'>$sarjarow[sarjanumero]</a><br>";
				}
				else {
					$row["kommentti"] .= "# $sarjarow[sarjanumero]<br>";
				}
			}

			if($kukarow['extranet'] == '' and $row["toimitettuaika"] != '0000-00-00 00:00:00') {
				$row['kommentti'] .= "<br><font class='info'>".t("Toimitettu").": $row[toimitettu] ".tv1dateconv($row["toimitettuaika"], "P")."</font>";
			}
			if($kukarow['extranet'] == '' and $row["kerattyaika"] != '0000-00-00 00:00:00') {
				$row['kommentti'] .= "<br><font class='info'>".t("Ker‰tty").": $row[keratty] ".tv1dateconv($row["kerattyaika"], "P")."</font>";
			}

			if ($laskurow["tilaustyyppi"] == "A") {
				if ($tuotetyyppi == "" and $row["tuotetyyppi"] == 'Tyˆ') {
					$tuotetyyppi = 1;

					echo "<tr><td class='back' colspan='10'><br></td></tr>";
					echo "<tr><td class='back' colspan='10'><b>".t("Tyˆt")."</b>:</td></tr>";
				}
			}

			if ($row["ale"] == 0.00) 	$row["ale"] 	= '';

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

			if($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
				$rivino++;
			}
			else {
				$rivino--;
			}

			// Tuoteperheiden lapsille ei n‰ytet‰ rivinumeroa
			if ($row["perheid"] == $row["tunnus"] or ($row["perheid2"] == $row["tunnus"] and $row["perheid"] == 0) or ($row["perheid2"] == -1 or ($row["perheid"] == 0 and $row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")))) {

				if (($row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")) or $row["perheid2"] == -1) {
					$pklisa = " and (tilausrivi.perheid = '$row[tunnus]' or tilausrivi.perheid2 = '$row[tunnus]')";
					$pkusei = " use index (yhtio_otunnus) ";
				}
				elseif ($row["perheid"] == 0) {
					$pklisa = " and tilausrivi.perheid2 = '$row[perheid2]'";
					$pkusei = " use index (yhtio_perheid2)";
				}
				else {
					$pklisa = " and (tilausrivi.perheid = '$row[perheid]' or tilausrivi.perheid2 = '$row[perheid]')";
					$pkusei = " use index (yhtio_otunnus) ";
				}

				$pkquery = "SELECT sum(if(tilausrivi.kommentti != '' or tuote.sarjanumeroseuranta!='' or ('$kukarow[extranet]' = '' and (tilausrivi.toimitettuaika!='0000-00-00 00:00:00' or tilausrivi.kerattyaika!='0000-00-00 00:00:00')), 1, 0)), count(*)
							FROM tilausrivi $pkusei
							JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and $where
							$pklisa
							and tilausrivi.tyyppi = '$row[tyyppi]'";
				$pkres = mysql_query($pkquery) or pupe_error($pkquery);
				$pkrow = mysql_fetch_array($pkres);

				$pknum = $pkrow[0] + $pkrow[1];
				$borderlask = $pkrow[1];

				echo "<tr>";

				$echorivino = $rivino;

				if ($yhtiorow['rivinumero_syotto'] != '') {
					if ($row['tilaajanrivinro'] != '' and $row['tilaajanrivinro'] != 0 and $echorivino != $row['tilaajanrivinro']) {
						$echorivino .= " ($row[tilaajanrivinro])";
					}
				}

				echo "<td valign='top' rowspan='$pknum' $class style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;' >$echorivino</td>";
			}
			elseif($row["perheid"] == 0 and $row["perheid2"] == 0) {

				$echorivino = $rivino;

				if ($yhtiorow['rivinumero_syotto'] != '') {
					if ($row['tilaajanrivinro'] != '' and $row['tilaajanrivinro'] != 0 and $echorivino != $row['tilaajanrivinro']) {
						$echorivino .= " ($row[tilaajanrivinro])";
					}
				}

				echo "<tr>";

				if($row["kommentti"] != "") {
					echo "<td rowspan = '2' valign='top'>$echorivino</td>";
				}
				else {
					echo "<td valign='top'>$echorivino</td>";
				}

				$borderlask		= 0;
				$pknum			= 0;
			}

			$classlisa = "";

			if($borderlask == 1 and $pkrow[1] == 1 and $pknum == 1) {
				$classlisa = $class." style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
				$class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

				$borderlask--;
			}
			elseif($borderlask == $pkrow[1] and $pkrow[1] > 0) {
				$classlisa = $class." style='border-top: 1px solid; border-right: 1px solid;' ";
				$class    .= " style='border-top: 1px solid;' ";

				$borderlask--;
			}
			elseif($borderlask == 1) {
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
			elseif($borderlask > 0 and $borderlask < $pknum) {
				$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
				$class    .= " style='font-style:italic;' ";
				$borderlask--;
			}

			$vanhaid = $row["perheid"];

			if ($laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "T" or $yhtiorow['tilauksen_kohteet'] == 'K') {

				echo "<td $class valign='top'>";

				if (mysql_num_rows($trivityyppi_result) > 0) {
					//annetaan valita tilausrivin tyyppi
					mysql_data_seek($trivityyppi_result, 0);

					while($trrow = mysql_fetch_array($trivityyppi_result)) {
						$sel = "";
						if ($trrow["selite"]==$row["positio"]) echo "$trrow[selitetark]";
					}
				}

				if($yhtiorow['tilauksen_kohteet'] == 'K' and $lasklisatied_row["asiakkaan_kohde"] > 0) {
					$posq = "SELECT * FROM asiakkaan_positio WHERE yhtio = '$kukarow[yhtio]' and asiakkaan_kohde = '$lasklisatied_row[asiakkaan_kohde]' and tunnus = '$row[asiakkaan_positio]'";
					$posres = mysql_query($posq) or pupe_error($posq);
					$posrow = mysql_fetch_array($posres);

					echo "$posrow[tunnus] - $posrow[positio]";
				}
				elseif ($yhtiorow['tilauksen_kohteet'] == 'K') {
					echo "<font class='info'>".t("Kohdetta ei valittu")."</font>";
				}

				echo "</td>";
			}

			// Tuotteen nimitys n‰ytet‰‰n vain jos k‰ytt‰j‰n resoluution on iso
			if ($kukarow["resoluutio"] == 'I' or $kukarow['extranet'] != '') {
				if (strtolower($kukarow["kieli"]) != strtolower($yhtiorow["kieli"])) {
					$query = "	SELECT selite
								from tuotteen_avainsanat
								where yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and laji	= 'nimitys_".$kukarow["kieli"]."'
								and selite != ''
								LIMIT 1";
					$pkres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($pkres) > 0) {
						$pkrow = mysql_fetch_array($pkres);
						$nimitys = $pkrow["selite"];
					}
					else {
						$nimitys = $row["nimitys"];
					}
				}
				else {
					$nimitys = $row["nimitys"];
				}

				echo "<td $class align='left' valign='top'>$nimitys</td>";
			}

			if (($laskurow["tila"] != "T" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $kukarow['extranet'] == '') {
				if ($varow['maa'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {
					echo "<td $class align='left' valign='top'><font class='error'><img src='../pics/flag_icons/gif/".strtolower($varow['maa']).".gif'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font>";
				}
				elseif ($varow['maa'] != '' and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
					echo "<td $class align='left' valign='top'><font class='error'>".strtoupper($varow['maa'])." $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font>";
				}
				else {
					echo "<td $class align='left' valign='top'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]";
				}

				if (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F") and !in_array($row["var"], array('P','J','S','T','U'))) {
			   		$query	= "	SELECT sarjanumeroseuranta.sarjanumero era, sarjanumeroseuranta.parasta_ennen
			   					FROM sarjanumeroseuranta
			   					WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
			   					and myyntirivitunnus = '$row[tunnus]'
			   					LIMIT 1";
			   		$sarjares = mysql_query($query) or pupe_error($query);
			   		$sarjarow = mysql_fetch_array($sarjares);

					echo ", $sarjarow[era]";

					if ($trow["sarjanumeroseuranta"] == "F") {
						echo " ".tv1dateconv($sarjarow["parasta_ennen"]);
					}
				}

				echo "</td>";
			}
			elseif (($laskurow["tila"] != "T" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $kukarow['extranet'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {

				if ($varow['maa'] != '' ) {
					echo "<td $class align='left' valign='top'><img src='".$palvelin2."flag_icons/gif/".strtolower($varow['maa']).".gif'></td>";
				}
				else {
					echo "<td $class align='left' valign='top'></td>";
				}
			}

			if($kukarow['extranet'] == '') {
				echo "<td $class valign='top'><a href='../tuote.php?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a></td>";
			}
			else {
				echo "<td $class valign='top'>$row[tuoteno]</td>";
			}

			echo "<td $class align='right' valign='top' nowrap>".($row["tilkpl"]*1)."</td>";

			if ($row["kpl"] != 0) {
				$kpl_ruudulle = $row['kpl'] * 1;
			}
			elseif ($row["var"] == 'J') {
				$kpl_ruudulle = $row['jt'] * 1;
			}
			elseif ($row["var"] == 'S' or $row["var"] == 'T' or $row["var"] == 'U') {
				$kpl_ruudulle = $row['jt'] * 1;
			}
			elseif($row["var"] == 'P') {
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

			$kpl   			= $row["varattu"]+$row["jt"]+$row['kpl'];
			$myyntihinta	= round(tuotteen_myyntihinta($laskurow, $trow, 1), $yhtiorow['hintapyoristys']);
			$bruttorivi		= $row["hinta"] * $kpl;

			if ($kukarow['hinnat'] == 1) {
				echo "<td $class align='right' valign='top' nowrap>$myyntihinta</td>";
			}
			else {
				if ($myyntihinta != $row["hinta"]) $myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $myyntihinta)." (".sprintf("%.".$yhtiorow['hintapyoristys']."f",$row["hinta"]).")";
				else $myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $myyntihinta);

				echo "<td $class align='right' valign='top' nowrap>$myyntihinta</td>";
				echo "<td $class align='right' valign='top' nowrap>".($row["ale"] * 1)."</td>";
				echo "<td $class align='right' valign='top' nowrap>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["hinta"])."</td>";
			}

			if ($kukarow['hinnat'] == 1) {
				echo "<td $class align='right' valign='top' nowrap>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $bruttorivi)."</td>";
			}
			else {
				if ($yhtiorow["alv_kasittely"] == "") {
					//verolliset hinnat
					echo "<td $class align='right' valign='top' nowrap>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["summa"])."</td>";
				}
				else {
					echo "<td $class align='right' valign='top' nowrap>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["arvo"])."</td>";
				}
			}

			if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
				// T‰n rivin kate
				$kate = 0;

				if ($laskurow["tapvm"] != '0000-00-00') {
					
					if ($row["kpl"] == 0) {
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

					if ($row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += $row["kate"];
				}
				elseif ($kukarow['extranet'] == '' and ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "U")) {
					if ($kpl > 0) {
						//Jos tuotteella yll‰pidet‰‰n in-out varastonarvo ja kyseess‰ on myynti‰
						$ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $row["tunnus"]);

						// Kate = Hinta - Ostohinta
						if ($row["rivihinta"] != 0) {
							$kate = sprintf('%.2f',100*($row["rivihinta"] - ($ostohinta * $kpl))/$row["rivihinta"])."%";
						}

						if ($row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += ($row["rivihinta"] - ($ostohinta * $kpl));
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
						$sarjares = mysql_query($query) or pupe_error($query);

						$ostohinta = 0;

						while($sarjarow = mysql_fetch_array($sarjares)) {

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
							$sarjares1 = mysql_query($query) or pupe_error($query);
							$sarjarow1 = mysql_fetch_array($sarjares1);

							$ostohinta += $sarjarow1["ostohinta"];
						}

						// Kate = Hinta - Alkuper‰inen ostohinta
						if ($row["rivihinta"] != 0) {
							$kate = sprintf('%.2f',100 * ($row["rivihinta"]*-1 - $ostohinta)/$row["rivihinta"])."%";
						}
						else {
							$kate = "100.00%";
						}

						if ($row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += ($row["rivihinta"]*-1 - $ostohinta);
					}
					else {
						$kate = "N/A";
					}
				}
				elseif ($kukarow['extranet'] == '') {
					if ($row["rivihinta"] != 0) {
						$kate = sprintf('%.2f',100*($row["rivihinta"] - (kehahin($row["tuoteno"])*($row["varattu"]+$row["jt"]+$row['kpl'])))/$row["rivihinta"])."%";
					}
					elseif (kehahin($row["tuoteno"]) != 0) {
						$kate = "-100.00%";
					}			
					
					if ($row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += ($row["rivihinta"] - (kehahin($row["tuoteno"])*($row["varattu"]+$row["jt"]+$row['kpl'])));
				}

				echo "<td $class align='right' valign='top' nowrap>$kate</td>";
			}

			if ($classlisa != "") {
				$classx = $classlisa;
			}
			else {
				$classx = $class;
			}

			if ($row["alv"] >= 500) {
				echo "<td $classx align='right' valign='top' nowrap>".t("MV")."</td>";
			}
			else {
				echo "<td $classx align='right' valign='top' nowrap>".($row["alv"] * 1)."</td>";
			}

			echo "</tr>";

			if ($row['kommentti'] != '') {
				$cspan = 11;

				if ($kukarow['hinnat'] == 1) {
					$cspan--;
				}
				if($kukarow["resoluutio"] == "I") {
					$cspan++;
				}
				if($laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "T" or $yhtiorow['tilauksen_kohteet'] == 'K') {
					$cspan++;
				}

				echo "<tr>";

				if ($borderlask == 0 and $pknum > 1) {
					$kommclass1 = " style='border-bottom: 1px solid; border-right: 1px solid;'";
					$kommclass2 = " style='border-bottom: 1px solid;'";
				}
				elseif($pknum > 0) {
					$kommclass1 = " style='border-right: 1px solid;'";
					$kommclass2 = " ";
				}
				else {
					$kommclass1 = "";
					$kommclass2 = " ";
				}

				if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
					echo "<td $kommclass2>&nbsp;</td>";
				}

				echo "<td $kommclass1 colspan='$cspan' valign='top'>".t("Kommentti").":<br><font class='message'>".str_replace("\n", "<br>", $row["kommentti"])."</font></td>";

				echo "</tr>";
			}
		}

		$colspan = 10;

		if ($kukarow['hinnat'] == 1) {
			$colspan = $colspan - 2;
		}
		if($kukarow["resoluutio"] == "I") {
			$colspan++;
		}
		if($laskurow["tila"] == 'T' or $laskurow["tilaustyyppi"] == "T" or $yhtiorow['tilauksen_kohteet'] == 'K') {
			$colspan++;
		}
		if($kukarow["extranet"] != "" and $yhtiorow['varastopaikan_lippu'] != '') {
			$colspan++;
		}
		if (!(($laskurow["tila"] != 'T' or $yhtiorow['tarjouksen_tuotepaikat'] == "") and ($kukarow['extranet'] == '' or $yhtiorow['varastopaikan_lippu'] != ''))) {
			$colspan--;
		}

		echo "<tr><td class='back' colspan='$colspan' align='right' nowrap>".t("Veroton yhteens‰").":</td><td class='back' align='right'>".sprintf('%.2f',$arvo_yht)."</td><td class='back'>$laskurow[valkoodi]</td></tr>";


		// Haetaan kaikki alvikannat riveilt‰
		$alvquery = "	SELECT distinct alv
						FROM tilausrivi
						WHERE $where
						and yhtio	= '$kukarow[yhtio]'
						and tyyppi	= 'L'
						and tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]'
						ORDER BY alv";
		$alvresult = mysql_query($alvquery) or pupe_error($alvquery);

		while ($alvrow = mysql_fetch_array($alvresult)) {

			// Valuuttajutut kuntoon
			if ($alvrow["alv"] >= 500) {
				$aquery = "	SELECT
							round(sum(0),2) alvrivihinta,
							round(sum((tilausrivi.hinta/if(lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*(tilausrivi.varattu+tilausrivi.kpl)*(1-tilausrivi.ale/100)),2) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE $where and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L' and tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]'";
			}
			else {
				$aquery = "	SELECT
							round(sum((tilausrivi.hinta/if(lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) * (tilausrivi.alv/100)),2) alvrivihinta,
							round(sum((tilausrivi.hinta/if(lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100))),2) rivihinta
							FROM tilausrivi
							JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
							WHERE $where and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L' and tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]'";
			}

			$aresult = mysql_query($aquery) or pupe_error($aquery);
			$arow = mysql_fetch_array($aresult);

			if ($alvrow["alv"] >= 500) {
				$alvi = t("M.V.");
			}
			else {
				$alvi = ($alvrow["alv"]*1)."%";
			}

			echo "<tr><td class='back' colspan='$colspan' align='right'>".t("Arvonlis‰vero", $kieli)." (".sprintf('%.2f',$arow["rivihinta"])."):</td><td align='right' class='back nowrap'>".sprintf('%.2f', $arow["alvrivihinta"])."</td><td class='back'>$alvi</td></tr>";
		}

		echo "<tr><td class='back' colspan='$colspan' align='right'>".t("Verollinen yhteens‰").":</td><td class='back' align='right' nowrap>".sprintf('%.2f',$summa_yht)."</td><td class='back'>$laskurow[valkoodi]</td></tr>";

		if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {

			if ($kate_yht < 0) {
				$ykate = round(-1 * abs($kate_yht / $rihin_yht * 100), 2);
			}
			else {
				$ykate = round(abs($kate_yht / $rihin_yht * 100), 2);
			}
					
			echo "<tr><td class='back' colspan='$colspan' align='right'>".t("Kate yhteens‰").":</td><td class='back' align='right' nowrap>".sprintf('%.2f',$ykate)."</td><td class='back'>%</td></tr>";
		}

		if($ennaa_yht != 0) {
			echo "<tr><td class='back'><br></td></tr>";
			echo "<tr><td class='back' colspan='$colspan' align='right'>".t("Ennakkolaskutus").":</td><td class='back' align='right' nowrap>".sprintf('%.2f',$ennaa_yht)."</td><td class='back'>$laskurow[valkoodi]</td></tr>";

			//Haetaan kaikki alvikannat riveilt‰
			$alvquery = "	SELECT distinct alv
							FROM tilausrivi
							WHERE $where
							and yhtio	= '$kukarow[yhtio]'
							and tyyppi	= 'L'
							and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'
							ORDER BY alv";
			$alvresult = mysql_query($alvquery) or pupe_error($alvquery);

			while ($alvrow = mysql_fetch_array($alvresult)) {

				// Valuuttajutut kuntoon
				if ($alvrow["alv"] >= 500) {
					$aquery = "	SELECT
								round(sum(0),2) alvrivihinta,
								round(sum((tilausrivi.hinta/if(lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*(tilausrivi.varattu+tilausrivi.kpl)*(1-tilausrivi.ale/100)),2) rivihinta
								FROM tilausrivi
								JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
								WHERE $where and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L' and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
				}
				else {
					$aquery = "	SELECT
								round(sum((tilausrivi.hinta/if(lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)) * (tilausrivi.alv/100)),2) alvrivihinta,
								round(sum((tilausrivi.hinta/if(lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100))),2) rivihinta
								FROM tilausrivi
								JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
								WHERE $where and tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.alv='$alvrow[alv]' and tilausrivi.tyyppi='L' and tuoteno = '$yhtiorow[ennakkomaksu_tuotenumero]'";
				}

				$aresult = mysql_query($aquery) or pupe_error($aquery);
				$arow = mysql_fetch_array($aresult);

				if ($alvrow["alv"] >= 500) {
					$alvi = t("M.V.");
				}
				else {
					$alvi = ($alvrow["alv"]*1)."%";
				}

				echo "<tr><td class='back' colspan='$colspan' align='right'>".t("Arvonlis‰vero", $kieli)." (".sprintf('%.2f',$arow["rivihinta"])."):</td><td align='right' class='back' nowrap>".sprintf('%.2f', $arow["alvrivihinta"])."</td><td class='back'>$alvi</td></tr>";
			}

			echo "<tr><td class='back' colspan='$colspan' align='right'>".t("Yhteens‰").":</td><td align='right' class='back' nowrap>".sprintf('%.2f',$ennas_yht)."</td><td class='back'>$laskurow[valkoodi]</td></tr>";
		}

		echo "</table>";
	}

	//	N‰ytet‰‰n vaan t‰‰ tilaus jostain linkist‰..
	if($tee != "NAYTA") {
		if($laskurow["jaksotettu"]<>0) {
			echo "<br><b>".t("Maksusopimus").":</b><hr>";

			if($laskurow["jaksotettu"]<0) {
				$sopimus = (-1) * $laskurow["jaksotettu"];
			}
			else {
				$sopimus = $laskurow["jaksotettu"];
			}

			echo "<table><tr><th>#</th><th>".t("Osuus-%")."</th><th>".t("Maksuehto/Kuvaus")."</th><th>".t("Lis‰tiedot/Ohje")."</th><th>".t("Summa")."</th><th>".t("Laskunro")."</th><th>".t("Laskun pvm")."</th><th>".t("Er‰p‰iv‰")."</th><th>".t("Maksup‰iv‰")."</th></tr>";

			$query = "	SELECT maksupositio.*, round(osuus,0) osuus,
						concat_ws('<br>', maksuehto.teksti, kuvaus) maksuehto,
						concat(maksupositio.lisatiedot, '<br>', maksupositio.ohje) lisatiedot,
						if(lasku.laskunro>0,lasku.laskunro, '') laskunro,
						if(myre.tapvm!='0000-00-00', date_format(myre.tapvm, '%d.%m.%Y'), '') tapvm,
						if(myre.erpcm!='0000-00-00', date_format(myre.erpcm, '%d.%m.%Y'), '') erpcm,
						if(myre.mapvm!='0000-00-00', date_format(myre.mapvm, '%d.%m.%Y'), '') mapvm
						FROM maksupositio
						LEFT JOIN lasku ON lasku.yhtio=maksupositio.yhtio and lasku.tunnus=maksupositio.uusiotunnus
						LEFT JOIN lasku myre ON myre.yhtio=lasku.yhtio and myre.laskunro=lasku.laskunro and myre.tila='U'
						LEFT JOIN maksuehto on maksuehto.yhtio=maksupositio.yhtio and maksuehto.tunnus=maksupositio.maksuehto
						WHERE maksupositio.yhtio = '$kukarow[yhtio]' and otunnus = '$sopimus'
						ORDER BY tunnus";
			$maksusopres = mysql_query($query) or pupe_error($query);
			$i=0;

			while($maksusoprow = mysql_fetch_array($maksusopres)) {
				$i++;
				echo "<tr class='aktiivi'>
						<td>$i</td>
						<td align='center'>{$maksusoprow[osuus]}</td>
						<td>{$maksusoprow[maksuehto]}</td>
						<td>{$maksusoprow[lisatiedot]}</td>
						<td align='right'>{$maksusoprow[summa]}</td>
						<td align='center'>{$maksusoprow[laskunro]}</td>
						<td align='center'>{$maksusoprow[tapvm]}</td>
						<td align='center'>{$maksusoprow[erpcm]}</td>
						<td align='center'>{$maksusoprow[mapvm]}</td>
					</tr>";
			}
			echo "</table><br>";
		}

		if($laskurow["tunnusnippu"] > 0) {
			echo "<br><b>".t("Liitetyt tilaukset").":</b><hr>";

			$query = "	SELECT lasku.tunnus tilaus, laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, toimaika, laatija, summa, tila, alatila
						FROM lasku use index (yhtio_tunnusnippu)
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnusnippu = '$laskurow[tunnusnippu]'
						and tila IN ('L','G','E','V','W','N','R','A')";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result)!=0) {

				echo "<table>";
				echo "<tr>";
				for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
					echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
				}
				echo "<th align='left'>".t("Tyyppi")."</th>";
				echo "</tr>";

				while ($row = mysql_fetch_array($result)) {

					$ero="td";
					if ($tunnus==$row['tilaus']) $ero="th";

					echo "<tr class='aktiivi'>";

					for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
						if (mysql_field_name($result,$i) == 'toimaika') {
							echo "<$ero>".tv1dateconv($row[$i])."</$ero>";
						}
						else {
							echo "<$ero>$row[$i]</$ero>";
						}
					}

					$laskutyyppi	= $row["tila"];
					$alatila		= $row["alatila"];

					//tehd‰‰n selv‰kielinen tila/alatila
					require "inc/laskutyyppi.inc";

					echo "<$ero>".t($laskutyyppi)." ".t($alatila)."</$ero>";

					echo "<form method='post' action='$PHP_SELF'><td class='back'>
							<input type='hidden' name='tee' value='NAYTATILAUS'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='asiakasid' value='$asiakasid'>
							<input type='hidden' name='toimittajaid' value='$toimittajaid'>
							<input type='hidden' name='tunnus' value='$row[tilaus]'>
							<input type='hidden' name='otunnus' value='{$laskurow["tunnusnippu"]}'>
							<input type='hidden' name='nippu' value='{$laskurow["tunnusnippu"]}'>
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


?>
