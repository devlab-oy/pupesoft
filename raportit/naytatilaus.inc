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
		$komm .= "\n".t("Kommentti")." 1:###".wordwrap(str_replace("\n","\n###", $laskurow['comments']), 100, "\n###");							
	}
		
	if ($laskurow["sisviesti2"] != "") {
		$komm .= "\n".t("Kommentti")." 2:###".wordwrap(str_replace("\n","\n###", $laskurow['sisviesti2']), 100, "\n###");
	}
	
	if ($laskurow["sisviesti1"] != "") {
		$komm .= "\n".t("Kommentti")." 3:###".wordwrap(str_replace("\n","\n###", $laskurow['sisviesti1']), 100, "\n###");
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
	
	if ($laskurow["tila"] == 'K') {
		$query = "	SELECT yhtio
					FROM oikeu
					WHERE yhtio	= '$kukarow[yhtio]'
					and kuka	= '$kukarow[kuka]'
					and nimi	= 'raportit.php'
					and alanimi = 'laskuhaku'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
		
			$query = "	select l2.ebid, l2.nimi, l2.summa, l2.tunnus
						FROM lasku l1
						JOIN lasku l2 ON l1.yhtio=l2.yhtio and l1.vanhatunnus=l2.tunnus and l2.ebid!=''
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
					WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = '$tunnus' and tila IN ('L','G','E','V','W','N')
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
	
	//Listataan tilauksessa olevat tuotteet
	if ($laskurow["tila"] == 'O' or $laskurow["tila"] == 'K') {
		$query = "	SELECT otunnus tilausnumero, ".nimitys('select','tilausrivi').", concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
					tilausrivi.tuoteno, toim_tuoteno, concat_ws('/',tilkpl,round(tilkpl*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tilattu sis/ulk',
					concat_ws('/',varattu,round(varattu*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tulossa sis/ulk', kpl,
					hinta, ale, var V, netto N, round((varattu+kpl)*hinta*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*(1-(ale/100)),'$yhtiorow[hintapyoristys]') rivihinta, 
					tilausrivi.alv, tilausrivi.kommentti, tilausrivi.tunnus,
					$sorttauskentta
					FROM tilausrivi
					JOIN tuote USING (yhtio, tuoteno)
					".nimitys('join','tilausrivi')."
					LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='$laskurow[liitostunnus]'
					WHERE $where
					and tilausrivi.yhtio='$kukarow[yhtio]'
					and tyyppi='O'
					ORDER BY $jarjestys";
	}
	else {
		$query = "	SELECT otunnus tilausnumero, ".nimitys('select','tilausrivi').", 
					concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka, tilausrivi.tuoteno, tilkpl, varattu+kpl kpl, jt,
					hinta, ale, var V, netto N, 					
					round(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)),'$yhtiorow[hintapyoristys]') rivihinta,
					alv, kerattyaika, keratty keraaja, toimaika, tilausrivi.kommentti, tilausrivi.tunnus,
					$sorttauskentta
					FROM tilausrivi
					".nimitys('join','tilausrivi')."
					WHERE $where 
					and tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi in ('E','L','G','V','W','T')
					ORDER BY $jarjestys";
	}
	$presult = mysql_query($query) or pupe_error($query);

	echo "<table><tr>";

	for ($i = 0; $i < mysql_num_fields($presult)-3; $i++) {
		$ojarj=$i+1; // sortti numeron mukaan niin ei tuu onglemia
		if($nippu>0) {
			echo "<th align='left'><a href = '$PHP_SELF?tee=NAYTATILAUS&toim=$toim&asiakasid=$asiakasid&toimittajaid=$toimittajaid&tunnus=$tunnus&nippu=$nippu&otunnus=$nippu&ojarj=$ojarj'>".t(mysql_field_name($presult,$i))."</a></th>";			
		}
		else {
			echo "<th align='left'><a href = '$PHP_SELF?tee=NAYTATILAUS&toim=$toim&asiakasid=$asiakasid&toimittajaid=$toimittajaid&tunnus=$tunnus&ytunnus=$ytunnus&nimi=$nimi&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&ojarj=$ojarj'>".t(mysql_field_name($presult,$i))."</a></th>";			
		}
	}

	echo "</tr>";
	
	$summa = $ennakkosumma = 0;
	
	while ($prow = mysql_fetch_array ($presult)) {

		echo "<tr class='aktiivi'>";
		for ($i=0; $i < mysql_num_fields($presult)-3; $i++) {
			if (mysql_field_name($presult,$i) == 'kerattyaika' or mysql_field_name($presult,$i) == 'toimaika') {
				echo "<td>".tv1dateconv($prow[$i],"pitka")."</td>";
			}
			else {
				echo "<td>".str_replace(".",",",$prow[$i])."</td>";
			}
		}
		echo "</tr>";

		
		//Haetaan sarjanumeron tiedot
		if ($laskurow["tila"] == 'O' or $laskurow["tila"] == 'K') {
			if ($prow["kpl"]+$prow["varattu"] > 0){
				$sarjanutunnus = "ostorivitunnus";
			}
			else {
				$sarjanutunnus = "myyntirivitunnus";
			}
		}
		else {
			if ($prow["varattu"] + $prow["kpl"] < 0){
				$sarjanutunnus = "ostorivitunnus";
			}
			else {
				$sarjanutunnus = "myyntirivitunnus";
			}

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
			echo "<td colspan='15'>$prow[kommentti]</td>";
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

	echo "<tr><td class='back' colspan='11' align='right'>".t("Veroton arvo yhteens‰").":</td><td class='back'>".sprintf('%.2f',$summa)."</td><td class='back'>$laskurow[valkoodi]</td></tr>";

	//Haetaan kaikki alvikannat riveilt‰
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
		
		echo "<tr><td class='back' colspan='11' align='right'>".t("Arvonlis‰vero", $kieli)." (".sprintf('%.2f',$arow["rivihinta"])."):</td><td align='right' class='back'>".sprintf('%.2f', $arow["alvrivihinta"])."</td><td class='back'>$alvi</td></tr>";		
	
		$summa += $arow["alvrivihinta"];
	}

	if ($summa != 0) {
		echo "<tr><td class='back' colspan='11' align='right'>".t("Yhteens‰").":</td><td class='back' align='right'>".sprintf('%.2f',$summa)."</td><td class='back'>$laskurow[valkoodi]</td></tr>";
	}

	if($ennakkosumma != 0) {
		echo "<tr><td class='back'><br></td></tr>";
		echo "<tr><td class='back' colspan='11' align='right'>".t("Ennakkolaskutus").":</td><td class='back' align='right'>".sprintf('%.2f',$ennakkosumma)."</td><td class='back'>$laskurow[valkoodi]</td></tr>";
		
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

			echo "<tr><td class='back' colspan='11' align='right'>".t("Arvonlis‰vero", $kieli)." (".sprintf('%.2f',$arow["rivihinta"])."):</td><td align='right' class='back'>".sprintf('%.2f', $arow["alvrivihinta"])."</td><td class='back'>$alvi</td></tr>";
		
			$ennakkosumma += $arow["alvrivihinta"];
		}
		
		if ($ennakkosumma != 0) {
			echo "<tr><td class='back' colspan='11' align='right'>".t("Yhteens‰").":</td><td align='right' class='back'>".sprintf('%.2f',$ennakkosumma)."</td><td class='back'>$laskurow[valkoodi]</td></tr>";
		}
		
	}

	echo "</table>";
	
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
						FROM lasku use index (yhtio_tila_luontiaika)
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
	
?>
