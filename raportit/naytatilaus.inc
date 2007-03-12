<?php
    ///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
	$useslave = 1;

	$lis = "";
	if ($kukarow["extranet"] != '') {
		$lis = " and liitostunnus = '$kukarow[oletus_asiakas]' ";
	}

	$query = "	SELECT *
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

	if ($laskurow['tila']=='L') {
		// haetaan eka rahtikirjat tietue, niin saadaan rahtikirjanumerot tietoon
		$query = "select * from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$laskurow[tunnus]' limit 1";
		$rakre = mysql_query($query) or pupe_error($query);
		$rakirrow = mysql_fetch_array($rakre);
		// spacet rivinvaihdoiksi niin n‰ytt‰‰ kivemmalta
		$rakirrow['rahtikirjanro'] = str_replace(" ", "<br>", trim($rakirrow['rahtikirjanro']));
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

	echo "<tr>
			<td>$laskurow[ytunnus]</td>
			<td colspan='2'>$laskurow[nimi] $laskurow[nimitark]<br> $laskurow[osoite]<br> $laskurow[postino] $laskurow[postitp]</td>
			<td colspan='3'>$laskurow[toim_nimi] $laskurow[toim_nimitark]<br> $laskurow[toim_osoite]<br> $laskurow[toim_postino] $laskurow[toim_postitp]</td>
			<td>$laskurow[toimitustapa]</td>";
	
	if ($laskurow["tila"] == "O") {
		echo "<td>$laskurow[rahti_etu]</td>";
	}

	echo "</tr>";

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

	// postin rahtikirjat alkaa JJFI, ja tehd‰‰n niille linkki suoraan postin seurantaan
	if (strpos($rakirrow['rahtikirjanro'],"JFI")) {
		$linkurl = "";
		$jjfi = split("<br>", trim($rakirrow['rahtikirjanro']));

		foreach ($jjfi as $nro) {
			$linkurl .= "<a target=newikkuna href='http://www.verkkoposti.com/e3/TrackinternetServlet?lang=fi&LOTUS_hae=Hae&LOTUS_side=1&LOTUS_trackId=$nro&LOTUS_hae=Hae'>$nro</a><br>";
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
			<th colspan='2'>".t("Maksup‰iv‰")."</th>
			<th>".t("ALV")."</th>";

	if ($laskurow["tila"] == "O") {
		echo "<th>".t("Huolinta")."</th>";
	}

	echo "<tr>
			<td>$laskurow[laskunro]</td>
			<td colspan='2'>".tv1dateconv($laskurow["tapvm"])."</td>
			<td>".tv1dateconv($laskurow["erpcm"])."</td>
			<td colspan='2'>".tv1dateconv($laskurow["mapvm"])."</td>
			<td>".str_replace(".",",",$laskurow['alv'])." %</td>";

	if ($laskurow["tila"] == "O") {
		echo "<td>$laskurow[rahti_huolinta]</td>";
	}
	
	$comments = "";

	if ($laskurow["comments"] != "")
		$comments .= $laskurow["comments"]."\n";
	
	if ($laskurow["sisviesti2"] != "")
		$comments .= $laskurow["sisviesti2"]."\n";
	
	if ($laskurow["sisviesti1"] != "")
		$comments .= $laskurow["sisviesti1"]."\n";
	
	if ($laskurow["noutaja"] != '') {
		$comments .= "\n".t("Noutaja").": $laskurow[noutaja]";
	}
	
	if ($comments != "") {
		echo "<tr><th colspan='9'>".t("Kommentit:")."</th></tr>";
		echo "<tr><td colspan='9'>";
		echo str_replace("\n", "<br>", $comments);
		echo "</td></tr>";
	}

	echo "</table>";

	echo "<br>";
	echo "<b>".t("Tilausrivit").":</b><hr>";

	if ($laskurow["tila"] == 'U') {
		$where = " uusiotunnus = '$tunnus' ";
	}
	else {
		$where = " otunnus = '$tunnus' ";
	}

	$jarjestys = " toimaika, tilausrivi.tunnus ";

	if (strlen($ojarj) > 0) {
		$jarjestys = $ojarj;
	}
	elseif ($laskurow["tila"] != 'O') {
		// katotaan miten halutaan sortattavan
		$jarjestys = " otunnus, sorttauskentta, tuoteno, tunnus ";
	}
	
	$sorttauskentta = ", ".generoi_sorttauskentta();

	//Listataan tilauksessa olevat tuotteet
	if ($laskurow["tila"] != 'O') {
		$query = "	SELECT otunnus tilausnumero, nimitys, concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka, tuoteno, tilkpl, varattu+kpl kpl, jt,
					hinta, ale, var V, netto N, round((varattu+kpl)*hinta*(1-(ale/100)),2) rivihinta, alv, keratty keraaja, tilausrivi.kommentti $sorttauskentta
					FROM tilausrivi
					WHERE $where and yhtio='$kukarow[yhtio]'
					and tilausrivi.tyyppi in ('E','L','G','V','W','T')
					ORDER BY $jarjestys";
	}
	else {
		$query = "	SELECT otunnus tilausnumero, tilausrivi.nimitys, concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
					tilausrivi.tuoteno, toim_tuoteno, concat_ws('/',tilkpl,round(tilkpl*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tilattu sis/ulk',
					concat_ws('/',varattu,round(varattu*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tulossa sis/ulk', jt,
					hinta, ale, var V, netto N, round((varattu+kpl)*hinta*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*(1-(ale/100)),2) rivihinta, tilausrivi.alv, tilausrivi.kommentti
					FROM tilausrivi
					JOIN tuote USING (yhtio, tuoteno)
					LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='$laskurow[liitostunnus]'
					WHERE $where
					and tilausrivi.yhtio='$kukarow[yhtio]'
					and tyyppi='O'
					ORDER BY $jarjestys";
	}
	$presult = mysql_query($query) or pupe_error($query);

	echo "<table><tr>";


	for ($i = 0; $i < mysql_num_fields($presult)-2; $i++) {
		$ojarj=$i+1; // sortti numeron mukaan niin ei tuu onglemia
		echo "<th align='left'><a href = '$PHP_SELF?tee=NAYTATILAUS&toim=$toim&asiakasid=$asiakasid&toimittajaid=$toimittajaid&tunnus=$tunnus&ytunnus=$ytunnus&nimi=$nimi&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&ojarj=$ojarj'>".t(mysql_field_name($presult,$i))."</a></th>";
	}

	echo "</tr>";


	$summa = 0;
	while ($prow = mysql_fetch_array ($presult)) {

		echo "<tr>";
		for ($i=0; $i < mysql_num_fields($presult)-2; $i++) {
			echo "<td>".str_replace(".",",",$prow[$i])."</td>";
		}
		echo "</tr>";

		
		//Haetaan sarjanumeron tiedot
		if ($prow["kpl"]+$prow["varattu"] > 0){
			$sarjanutunnus = "myyntirivitunnus";
		}
		else {
			$sarjanutunnus = "ostorivitunnus";
		}

		$query = "	select *
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
			$prow["kommentti"] .= "# <a href='sarjanumeroseuranta.php?tuoteno_haku=$prow[tuoteno]&sarjanumero_haku=$sarjarow[sarjanumero]'>$sarjarow[sarjanumero]</a><br>";
		}
		
		
		if ($prow["kommentti"] != '') {
			echo "<tr>";
			echo "<td valign='top'>".t("Kommentti").":</td>";
			echo "<td colspan='13'>$prow[kommentti]</td>";
			echo "</tr>";
		}


		$summa += $prow["rivihinta"];
	}

	echo "<tr><td class='back' colspan='11' align='right'>".t("Arvo").":</td><td class='back' colspan='2'>".str_replace(".",",",sprintf('%.2f',$summa))."</td></tr>";

	$aquery = "	SELECT alv, round(sum(rivihinta*(alv/100)),2) alvrivihinta, sum(rivihinta) rivihinta
				FROM tilausrivi
				WHERE $where and yhtio='$kukarow[yhtio]'
				GROUP BY alv
				ORDER BY alv";
	$aresult = mysql_query($aquery) or pupe_error($aquery);
	$summa = 0;
	while ($arow = mysql_fetch_array($aresult)) {

		if ($arow['alvrivihinta']!=0) {
			echo "<tr><td class='back' colspan='11' align='right'>".t("Alv").": ".str_replace(".",",",$arow['alv'])."%</td><td class='back' colspan='2'>".str_replace(".",",",$arow['alvrivihinta'])."</td></tr>";
		}
		$summa += $arow["alvrivihinta"] + $arow["rivihinta"];
	}

	if ($summa!=0) {
		echo "<tr><td class='back' colspan='11' align='right'>".t("Yhteens‰").":</td><td class='back' colspan='2'>".str_replace(".",",",sprintf('%.2f',$summa))."</td></tr>";
	}

	echo "</table>";
?>
