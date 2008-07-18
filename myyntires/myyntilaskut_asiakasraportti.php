<?php

	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	
	if (isset($tiliote) and $tiliote==1) $nayta_pdf = 1;
	
	require ("../inc/parametrit.inc");

	if (isset($tiliote) and $tiliote==1) {
		require('paperitiliote.php');
		exit;
	}

	/* visuaalinen esitys maksunopeudesta (hymynaama) */
	/* palauttaa listan arvoja, joissa ensimmäisessä on
	 * pelkkä img-tagi oikeaan naamaan ja toisessa
	 * koko maksunopeus-HTML
	 */

	function laskeMaksunopeus($ytunnus, $yhtio) {

		// myohassa maksetut
		$query="	SELECT sum(if(erpcm < mapvm, summa, 0)) myohassa, sum(summa) yhteensa
					from lasku,
					(select tunnus from asiakas where yhtio = '$yhtio' and ytunnus = '$ytunnus') valittu
					where yhtio = '$yhtio'
					and liitostunnus = valittu.tunnus
					and tila = 'U'
					and alatila = 'X'
					and summa > 0
					and mapvm != '0000-00-00'";
		$result = mysql_query($query) or pupe_error($query);
		$laskut = mysql_fetch_array($result);

		if ($laskut['yhteensa'] != 0) {
			$maksunopeus = $laskut['myohassa']/$laskut['yhteensa']*100;
		}
		else {
			$maksunopeus = "N/A";
		}

		$maksunopeusvari="lightgreen";
		$kuva="asiakas_jee.gif";

		if ($maksunopeus > 10) {
			$maksunopeusvari="orange";
			$kuva="asiakas_hui.gif";
		}
		if ($maksunopeus > 50) {
			$maksunopeusvari="red";
			$kuva="asiakas_argh.gif";
		}

		//echo "<br><br>";
		$html .= '<font color="'.$maksunopeusvari.'">';
		$html .= "".t("Myöhässä maksettuja laskuja").": ";
		$html .= sprintf('%.0f', $maksunopeus);
		$html .= " % </font>";
		$kuvaurl = "<img valign='bottom' src=\"../pics/$kuva\">";

		//$html .= $kuvaurl;

		return array ($kuvaurl, $html);
	}

	echo "<font class='head'>".t("Asiakasraportit myyntilaskuista")."</font><hr>";

	if ($ytunnus != '') {
		$tila = "tee_raportti";
		require ("inc/asiakashaku.inc");
		$tunnus = $asiakasid;
	}

	if ($tila == 'tee_raportti') {

		if ((int) $tunnus != 0) {
			$haku_sql = "tunnus='$tunnus'";
		}
		else {
			$haku_sql = "ytunnus='$ytunnus'";
		}

		$query = "	SELECT tunnus, ytunnus, nimi, osoite, postino, postitp
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					and $haku_sql";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			$asiakasrow = mysql_fetch_array($result);

			$query = "	SELECT group_concat(tunnus) tunnukset
						FROM asiakas
						WHERE yhtio = '$kukarow[yhtio]'
						and ytunnus = '$asiakasrow[ytunnus]'";
			$result = mysql_query($query) or pupe_error($query);
			$asiakasrow2 = mysql_fetch_array($result);

		  	$tunnus 	= $asiakasrow['tunnus'];
		  	$ytunnus 	= $asiakasrow['ytunnus'];
			$tunnukset 	= $asiakasrow2['tunnukset'];

			//kaatotilin saldo
			if ($savalkoodi != "") {
				$salisa = " and valkoodi='$savalkoodi' ";
			}
			else {
				$salisa = "";
			}

			$query = "	SELECT valkoodi, sum(round(summa*if(kurssi=0, 1, kurssi),2)) summa, sum(summa) summa_valuutassa 
						FROM suoritus
						WHERE yhtio='$kukarow[yhtio]'
						and ltunnus<>0
						and asiakas_tunnus in ($tunnukset)
						and summa != 0
						$salisa
						group by 1";
			$kaatoresult = mysql_query($query) or pupe_error($query);

			$query = "	SELECT valkoodi, count(tunnus) as maara,
						sum(if(mapvm = '0000-00-00',1,0)) avoinmaara,
						sum(if(erpcm < now() and mapvm = '0000-00-00',1,0)) eraantynytmaara,
						sum(summa-saldo_maksettu) as summa,
						sum(if(mapvm='0000-00-00',summa-saldo_maksettu,0)) avoinsumma,
						sum(if(erpcm < now() and mapvm = '0000-00-00',summa-saldo_maksettu,0)) eraantynytsumma,
						sum(summa_valuutassa-saldo_maksettu_valuutassa) as summa_valuutassa,
						sum(if(mapvm='0000-00-00',summa_valuutassa-saldo_maksettu_valuutassa,0)) avoinsumma_valuutassa,
						sum(if(erpcm < now() and mapvm = '0000-00-00',summa_valuutassa-saldo_maksettu_valuutassa,0)) eraantynytsumma_valuutassa,
						sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) <= -3,1,0)) maara1,
						sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) > -3 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= -1,1,0)) maara2,
						sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) > -1 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 15,1,0)) maara3,
						sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) > 15 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 30,1,0)) maara4,
						sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) > 30 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 60,1,0)) maara5,
						sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) > 60,1,0)) maara6
						FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
						WHERE yhtio = '$kukarow[yhtio]'
						and tila 	= 'U'
						and liitostunnus in ($tunnukset)
						$salisa
						and tapvm > '0000-00-00'
						and mapvm = '0000-00-00'
						group by 1";
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($kaatoresult) > 1) { 
				$riveja = mysql_num_rows($kaatoresult) + 1;
			}
			else {
				$riveja = 1;
				if (mysql_num_rows($kaatoresult) != 0) {
					$kaato = mysql_fetch_array($kaatoresult);
					mysql_data_seek($kaatoresult,0);
				
					if (strtoupper($yhtiorow['valkoodi']) != strtoupper($kaato['valkoodi'])) {
						$riveja = 2;
					}
				}
			}
				
			echo "<table>
				<tr>
				<th rowspan='$riveja'><a href='../crm/asiakasmemo.php?ytunnus=$ytunnus'>$asiakasrow[nimi]</a></td>
				<td rowspan='$riveja'>".t("Kaatotilillä")."</td>";
				
			if (mysql_num_rows($kaatoresult) > 1) { // Valuuttasummia
				$kotisumma = 0;
				while ($kaato = mysql_fetch_array($kaatoresult)) {
					echo "<td align='right'>$kaato[summa_valuutassa]</td><td>$kaato[valkoodi]</td></tr><tr>";
					$kotisumma += $kaato['summa'];
				}
				echo "<td align='right'>$kotisumma</td><td>$yhtiorow[valkoodi]</td></tr>";
			}
			else {
				$kaato = mysql_fetch_array($kaatoresult);
				if ($riveja == 2) {
					echo "<td align='right'>$kaato[summa_valuutassa]</td><td>$kaato[valkoodi]</td></tr>";
					echo "<tr><td align='right'>$kaato[summa]</td><td>$yhtiorow[valkoodi]</td></tr>";
				}
				else {
					echo "<td align='right'>$kaato[summa]</td>";
				}
			}



			if (mysql_num_rows($result) > 1) { 
				$riveja = mysql_num_rows($result) + 1;
			}
			else {
				$riveja = 1;
				if (mysql_num_rows($result) != 0) {
					$kok = mysql_fetch_array($result);
					mysql_data_seek($result,0);
					if (strtoupper($yhtiorow['valkoodi']) != strtoupper($kok['valkoodi'])) {
						$riveja = 2;
					}
				}
			}
				
			echo "
				<tr>
				<th rowspan='$riveja'>$ytunnus</td>
				<td rowspan='$riveja'>".t("Myöhässä olevia laskuja yhteensä")."</td>";

			if (mysql_num_rows($result) > 1) { // Valuuttasummia
				$kotisumma = 0;
				while ($kok = mysql_fetch_array($result)) {
					echo "<td align='right'>$kok[eraantynytsumma_valuutassa]</td><td>$kok[valkoodi]</td></tr>";
					$kotisumma += $kok['eraantynytsumma'];
				}
				echo "<td align='right'>$kotisumma</td><td>$yhtiorow[valkoodi]</td>";
			}
			else {
				$kok = mysql_fetch_array($result);
				if ($riveja == 2) {
					echo "<td align='right'>$kok[eraantynytsumma_valuutassa]</td><td>$kok[valkoodi]</td></tr>";
					echo "<tr><td align='right'>$kok[eraantynytsumma]</td><td>$yhtiorow[valkoodi]</td></tr>";
				}
				else {
					echo "<td align='right'>$kok[eraantynytsumma]</td></tr>";
				}
			}

			if (mysql_num_rows($result) > 0) mysql_data_seek($result,0);
			echo "
				<tr>
				<th rowspan='$riveja'>$asiakasrow[osoite]</td>
				<td rowspan='$riveja'>".t("Avoimia laskuja yhteensä")."</td>";

			if (mysql_num_rows($result) > 1) { // Valuuttasummia
				$kotisumma = 0;
				while ($kok = mysql_fetch_array($result)) {
					echo "<td align='right'>$kok[avoinsumma_valuutassa]</td><td>$kok[valkoodi]</td></tr>";
					$kotisumma += $kok['avoinsumma'];
				}
				echo "<td align='right'>$kotisumma</td><td>$yhtiorow[valkoodi]</td>";
			}
			else {
				$kok = mysql_fetch_array($result);
				if ($riveja == 2) {
					echo "<td align='right'>$kok[avoinsumma_valuutassa]</td><td>$kok[valkoodi]</td></tr>";
					echo "<tr><td align='right'>$kok[avoinsumma]</td><td>$yhtiorow[valkoodi]</td></tr>";
				}
				else { 
					echo "<td align='right'>$kok[avoinsumma]</td></tr>";
				}
			}
			
/*			mysql_data_seek($result,0);
			echo "<tr>
				<th>$asiakasrow[postino] $asiakasrow[postitp]</td>
				<td>".t("Laskuja yhteensä")."</td>";

			if (mysql_num_rows($result) > 1) { // Valuuttasummia
				$kotisumma = 0;
				while ($kok = mysql_fetch_array($result)) {
					echo "<td align='right'>$kok[summa_valuutassa]</td><td>$kok[valkoodi]</td>";
					$kotisumma += $kok['summa'];
				}
				echo "<td align='right'>$kotisumma</td><td>$yhtiorow[valkoodi]</td>";
			}
			else {
				$kok = mysql_fetch_array($result);
				echo "<td align='right'>$kok[summa]</td></tr>";
			}
*/

			echo "<tr>
				<th>$asiakasrow[postino] $asiakasrow[postitp]</td>
				<td colspan='2'></td></tr>";
			echo "<tr>
				<th></th><td colspan='2'><a href='../raportit/asiakasinfo.php?ytunnus=$ytunnus'>".t("Asiakkaan myyntitiedot")."</a></td>
				</tr>";

			echo "<table><tr><td class='back'>";

			// ikäanalyysi
			echo "<br><table><tr><th>&lt; -2</th><th>-2 - -1</th><th>0 - 15</th><th>16 - 30</th><th>31 - 60</th><th>&gt; 60</th></tr>";

			$palkki_korkeus = 20;
			$palkki_leveys = 300;

			$kuvaurl[0] = "../pics/vaaleanvihrea.png";
			$kuvaurl[1] = "../pics/vihrea.png";
			$kuvaurl[2] = "../pics/keltainen.png";
			$kuvaurl[3] = "../pics/oranssi.png";
			$kuvaurl[4] = "../pics/oranssihko.png";
			$kuvaurl[5] = "../pics/punainen.png";

			$yhtmaara = $kok['avoinmaara'];

			echo "<tr>";
			echo "<td align='right'>".(int) $kok["maara1"]."</td>";
			echo "<td align='right'>".(int) $kok["maara2"]."</td>";
			echo "<td align='right'>".(int) $kok["maara3"]."</td>";
			echo "<td align='right'>".(int) $kok["maara4"]."</td>";
			echo "<td align='right'>".(int) $kok["maara5"]."</td>";
			echo "<td align='right'>".(int) $kok["maara6"]."</td>";
			echo "</tr>";

			if ($yhtmaara != 0) {
				echo "<tr><td colspan='6' class='back'>";
				echo "<img src='$kuvaurl[0]' height='$palkki_korkeus' width='" . ($kok['maara1']/$yhtmaara) * $palkki_leveys ."'>";
				echo "<img src='$kuvaurl[1]' height='$palkki_korkeus' width='" . ($kok['maara2']/$yhtmaara) * $palkki_leveys ."'>";
				echo "<img src='$kuvaurl[2]' height='$palkki_korkeus' width='" . ($kok['maara3']/$yhtmaara) * $palkki_leveys ."'>";
				echo "<img src='$kuvaurl[3]' height='$palkki_korkeus' width='" . ($kok['maara4']/$yhtmaara) * $palkki_leveys ."'>";
				echo "<img src='$kuvaurl[4]' height='$palkki_korkeus' width='" . ($kok['maara5']/$yhtmaara) * $palkki_leveys ."'>";
				echo "<img src='$kuvaurl[5]' height='$palkki_korkeus' width='" . ($kok['maara6']/$yhtmaara) * $palkki_leveys ."'>";
				echo "</td></tr>";
			}

			echo "</table>";

			echo "</td><td class='back' align='center' width='300'>";

			//visuaalinen esitys maksunopeudesta (hymynaama)
			list ($naama, $nopeushtml) = laskeMaksunopeus($ytunnus, $kukarow["yhtio"]);

			echo "<br>$naama<br>$nopeushtml</td>";
			echo "<form action=''><td class='back'>
			<input type='hidden' name = 'tiliote' value='1'>
			<input type='hidden' name = 'ytunnus' value='$ytunnus'>
			<input type='submit' value='".t("Tulosta tiliote")."'></td></form>";
			echo "</tr></table><br>";

			//avoimet laskut
			$kentat = 'laskunro, tapvm, erpcm, summa, kapvm, kasumma, mapvm, ika, viikorkoeur, olmapvm, tunnus';
			$kentankoko = array(8,8,8,10,8,8,8,4,4,8);

			$array = split(",", $kentat);
			$count = count($array);

			for ($i=0; $i<=$count; $i++) {
				// tarkastetaan onko hakukentässä jotakin
				if (strlen($haku[$i]) > 0) {
					$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
					$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
				}
			}
			if (strlen($ojarj) > 0) {
				$jarjestys = $array[$ojarj];
			}
			else{
				$jarjestys = 'erpcm';
			}

			//näytetäänkö maksetut vai avoimet
			$chk1 = $chk2 = $chk3 = $chk4 = '';

			if ($valintra == 'maksetut') {
				$chk2 = 'SELECTED';
				$mapvmlisa = " and mapvm > '0000-00-00' ";
			}
			elseif($valintra == 'kaikki') {
				$chk3 = 'SELECTED';
				$mapvmlisa = " ";
			}
			else {
				$chk1 = 'SELECTED';
				$mapvmlisa = " and mapvm = '0000-00-00' ";
			}

			if ($valuutassako == 'V') {
				$chk4 = "SELECTED";
			}

			if ($savalkoodi != "") {
				$salisa = " and lasku.valkoodi='$savalkoodi' ";
			}

			$selectit = "	laskunro, tapvm, erpcm, summa-pyoristys-saldo_maksettu summa, summa_valuutassa-pyoristys_valuutassa-saldo_maksettu_valuutassa summa_valuutassa, kapvm, kasumma, kasumma_valuutassa, mapvm,
								TO_DAYS(mapvm) - TO_DAYS(erpcm) ika, viikorkoeur korko, olmapvm korkolaspvm, lasku.tunnus, saldo_maksettu, saldo_maksettu_valuutassa, valkoodi" ;

			$query = "	SELECT
						$selectit
						FROM lasku
						WHERE yhtio ='$kukarow[yhtio]'
						and tila = 'U'
						and liitostunnus in ($tunnukset)
						$mapvmlisa
						$lisa
						$salisa
						ORDER BY $jarjestys";
			$result = mysql_query($query) or pupe_error($query);

			echo "<table>";
			echo "<form action = '$PHP_SELF?tila=$tila&tunnus=$tunnus&ojarj=".$ojarj.$ulisa."' method = 'post'>";
			echo "<tr>
					<th>".t("Näytä").":</th>
					<td>
					<select name='valintra' onchange='submit();'>
					<option value='' $chk1>".t("Avoimet laskut")."</option>
					<option value='maksetut' $chk2>".t("Maksetut laskut")."</option>
					<option value='kaikki' $chk3>".t("Kaikki laskut")."</option>
					</select>
					</td>";

			$query = "	SELECT
						distinct upper(if(valkoodi='', '$yhtiorow[valkoodi]' , valkoodi)) valuutat
						FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
						WHERE yhtio = '$kukarow[yhtio]'
						and tila 	= 'U'
						and liitostunnus in ($tunnukset)
						and tapvm > '0000-00-00'
						$mapvmlisa";
			$aasres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($aasres) > 0) {

				echo "	<th>".t("Valuutta").":</th>
						<td><select name='savalkoodi' onchange='submit();'>";
				echo "<option value = ''>".t("Kaikki")."</option>";

				while ($aasrow = mysql_fetch_array($aasres)) {
					$sel="";
					if ($aasrow["valuutat"] == strtoupper($savalkoodi)) {
						$sel = "selected";
					}
					echo "<option value = '$aasrow[valuutat]' $sel>$aasrow[valuutat]</option>";
				}

			}

			echo "</tr>";
			echo "</form>";
			echo "</table><br>";

			echo "<table cellpadding='2'>";
			echo "<tr>";
			echo "<form action = '$PHP_SELF?tila=$tila&tunnus=$tunnus&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi' method = 'post'>";
			echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=0".$ulisa."'>".t("Laskunro")."</a></th>";
			echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=1".$ulisa."'>".t("Pvm")."</a></th>";
			echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=2".$ulisa."'>".t("Eräpäivä")."</a></th>";
			echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=3".$ulisa."'>".t("Summa")."</a></th>";
			echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=4".$ulisa."'>".t("Kassa-ale")."<br>".t("pvm")."</a></th>";
			echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=5".$ulisa."'>".t("Kassa-ale")."<br>".t("summa")."</a></th>";
			echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=6".$ulisa."'>".t("Maksu")."<br>".t("pvm")."</a></th>";
			echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=7".$ulisa."'>".t("Ikä")."</a></th>";
			echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=8".$ulisa."'>".t("Korko")."</a></th>";
			echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=9".$ulisa."'>".t("Korkolasku")."<br>".t("pvm")."</a></th>";
			echo "<th valign='top'>".t("Osasuoritukset")."</th>";

			echo "<td class='back'></td></tr>";
			echo "<tr>";

			for ($i = 0; $i < mysql_num_fields($result)-6; $i++) {
				echo "<td><input type='text' size='$kentankoko[$i]' name = 'haku[$i]' value = '$haku[$i]'></td>";
			}

			echo "<td></td><td class='back'><input type='submit' value='".t("Etsi")."'></td></tr>";
			echo "</form>";

			$summa = 0;

			// haetaan kaikki yrityksen rahatilit mysql muodossa
			$query  = "	SELECT concat(group_concat(distinct concat('\'',oletus_rahatili) SEPARATOR '\', '),'\'') rahatilit
						FROM yriti
						WHERE yhtio = '$kukarow[yhtio]' and
						oletus_rahatili != ''";
			$ratire = mysql_query($query) or pupe_error($query);
			$ratiro = mysql_fetch_array($ratire);

			while ($maksurow = mysql_fetch_array ($result)) {

				echo "<tr class='aktiivi'>";
				echo "<td><a href='../muutosite.php?tee=E&tunnus=$maksurow[tunnus]'>$maksurow[laskunro]</a></td>";

				echo "<td align='right'>".tv1dateconv($maksurow["tapvm"])."</td>";
				echo "<td align='right'>".tv1dateconv($maksurow["erpcm"])."</td>";

				if ($maksurow["saldo_maksettu"] != 0 and $maksurow["mapvm"] == "0000-00-00") {
					$maksurow["summa"] .= "*";
				}
				echo "<td align='right'>$maksurow[summa] {$yhtiorow['valkoodi']}";
				if ($maksurow['summa_valuutassa'] != 0 and $maksurow['summa'] != $maksurow['summa_valuutassa']) {
					echo "<br />{$maksurow['summa_valuutassa']} {$maksurow['valkoodi']}";
				}

				echo "</td>";

				if ($maksurow["kapvm"] != '0000-00-00') echo "<td align='right'>".tv1dateconv($maksurow["kapvm"])."</td>";
				else echo "<td></td>";

				if ($maksurow["kasumma"] != 0) {
					echo "<td align='right'>$maksurow[kasumma]";
					if ($maksurow['kasumma_valuutassa'] != 0 and $maksurow['kasumma'] != $maksurow['kasumma_valuutassa']) {
						echo "<br />" . $maksurow['kasumma_valuutassa'] . ' ' . $maksurow['valkoodi'];
					}
					echo "</td>";
				}
				else {
					 echo "<td></td>";
				}

				if ($maksurow["mapvm"] != '0000-00-00') echo "<td align='right'>".tv1dateconv($maksurow["mapvm"])."</td>";
				else echo "<td></td>";

				echo "<td align='right'>$maksurow[ika]</td>";

				if ($maksurow["korko"] != 0) echo "<td align='right'>$maksurow[korko]</td>";
				else echo "<td></td>";

				if ($maksurow["korkolaspvm"] != '0000-00-00') echo "<td align='right'>".tv1dateconv($maksurow["korkolaspvm"])."</td>";
				else echo "<td></td>";

				echo "<td align='right'>";
				// jos rahatilejä löytyy etsitään suoritukst
				if ($ratiro["rahatilit"] != "") {
					$query = "	SELECT *
								FROM tiliointi USE INDEX (tositerivit_index)
								WHERE yhtio = '$kukarow[yhtio]' and
								ltunnus = '$maksurow[tunnus]' and
								tilino in ($ratiro[rahatilit]) and
								korjattu = ''";
					$lasktilitre = mysql_query($query) or pupe_error($query);

					// listataan osasuoritukset jos maksupäivä on nollaa tai jos niitä on oli yks
					if ($maksurow["mapvm"] == "0000-00-00" or mysql_num_rows($lasktilitre) > 1) {
						while ($lasktilitro = mysql_fetch_array($lasktilitre)) {
							if ($lasktilitro["valkoodi"] != $yhtiorow["valkoodi"] and $lasktilitro["valkoodi"] != "") {
								echo "$lasktilitro[summa_valuutassa] $lasktilitro[valkoodi] ($lasktilitro[summa] $yhtiorow[valkoodi]) ", tv1dateconv($lasktilitro["tapvm"]), "<br>";
								
							}
							else {
								echo "$lasktilitro[summa] $yhtiorow[valkoodi] ", tv1dateconv($lasktilitro["tapvm"]), "<br>";
							}							
						}
					}
				}
				echo "</td>";

				echo "<td class='back'></td></tr>";
				if ($maksurow['valkoodi'] == $yhtiorow['valkoodi'])
					$totaali[$maksurow['valkoodi']] += $maksurow['summa'];
				else
					$totaali[$maksurow['valkoodi']] += $maksurow['summa_valuutassa'];
				$summa += $maksurow['summa'];
			}

			echo "<tr><td class='back' colspan='2'></td><th>".t("Yhteensä").":</th><td class='tumma' align='right'>";
			if (sizeof($totaali)==1 and isset($totaali[$yhtiorow['valkoodi']])) {
				echo $totaali[$yhtiorow['valkoodi']];
			}
			else {
				foreach ($totaali as $valuutta => $valsumma) {
					printf("%.2f",$valsumma);
					echo " $valuutta<br>";
				}
				echo "$summa $yhtiorow[valkoodi]";
			}
			echo "</th></tr>";

			echo "</table>";

			echo "<br>";
			if ($kaato["summa"]>0) {
				echo "<form action = 'maksa_kaatosumma.php?tunnus=$tunnus' method = 'post'>";
				echo "<input type='submit' value='".t("Maksa kaatotilin summa asiakkaalle")."'></submit>";
				echo "</form>";
			}

			echo "<script LANGUAGE='JavaScript'>document.forms[0][0].focus()</script>";
		}

	}

	if ($ytunnus == '') {
		$formi = 'haku';
		$kentta = 'ytunnus';

		/* hakuformi */
		echo "<br><form name='$formi' action='$PHP_SELF' method='GET'>";
		echo t("Etsi asiakasta nimellä tai y-tunnuksella").": ";
		echo "<input type='hidden' name='alatila' value='etsi'>";
		echo "<input type='text' name='ytunnus' value='$asiakas->ytunnus'>";
		echo "<input type='submit' value='".t("Etsi")."'>";
		echo "</form>";
	}

	require ("inc/footer.inc");

?>
