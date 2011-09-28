<?php

	///* T�m� skripti k�ytt�� slave-tietokantapalvelinta *///
	$useslave = 1;

	if (isset($_POST['tiliote']) and $_POST['tiliote'] == '1') {
		$nayta_pdf = 1;
	}

	require ("../inc/parametrit.inc");

	if (isset($_POST['tiliote']) and $_POST['tiliote'] == '1') {
		require('paperitiliote.php');
		exit;
	}

	if ($tee == "") {

		/* visuaalinen esitys maksunopeudesta (hymynaama) */
		/* palauttaa listan arvoja, joissa ensimm�isess� on
		 * pelkk� img-tagi oikeaan naamaan ja toisessa
		 * koko maksunopeus-HTML
		 */

		function laskeMaksunopeus($tunnukset, $yhtio) {

			global $palvelin2;

			// myohassa maksetut
			$query = "	SELECT sum(if(erpcm < mapvm, summa, 0)) myohassa, sum(summa) yhteensa
						FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
						WHERE yhtio = '$yhtio'
						AND tila = 'U'
						AND liitostunnus in ($tunnukset)
						AND tapvm  > '0000-00-00'
						AND mapvm  > '0000-00-00'
						AND alatila = 'X'
						AND summa > 0";
			$result = pupe_query($query);
			$laskut = mysql_fetch_array($result);

			if ($laskut['yhteensa'] != 0) {
				$maksunopeus = $laskut['myohassa'] / $laskut['yhteensa'] * 100;
			}
			else {
				$maksunopeus = "N/A";
			}

			if ($maksunopeus > 50) {
				$kuva = "asiakas_argh.gif";
			}
			elseif ($maksunopeus > 10) {
				$kuva = "asiakas_hui.gif";
			}
			else {
				$kuva = "asiakas_jee.gif";
			}

			$html = t("My�h�ss� maksettuja laskuja").": ".sprintf('%.0f', $maksunopeus)."%";
			$kuvaurl = "<img valign='bottom' src='${palvelin2}pics/$kuva'>";

			return array($kuvaurl, $html);
		}

		echo "<font class='head'>".t("Asiakasraportit myyntilaskuista")."</font><hr>";

		if ($ytunnus != '' and (int) $asiakasid == 0) {
			$tila = "tee_raportti";

			require ("inc/asiakashaku.inc");

			if ($ytunnus == "") {
				$tila = "";
			}
		}

		if ($tila == 'tee_raportti') {

			if ($alatila == "T" and (int) $asiakasid > 0) {
				$haku_sql = "tunnus = '$asiakasid'";
			}
			else {
				$ytunnus  = mysql_real_escape_string($ytunnus);
				$haku_sql = "ytunnus = '$ytunnus'";
			}

			$query = "	SELECT tunnus, ytunnus, nimi, osoite, postino, postitp, maa
						FROM asiakas
						WHERE yhtio = '$kukarow[yhtio]'
						and $haku_sql";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {

				$asiakasrow = mysql_fetch_array($result);

				// ekotetaan javascripti� jotta saadaan pdf:�t uuteen ikkunaan
				js_openFormInNewWindow();

				$asiakasid 	= $asiakasrow['tunnus'];
			  	$ytunnus 	= $asiakasrow['ytunnus'];

				if ($alatila == "T") {
					$tunnukset 	= $asiakasid;
					$nimet		= 1;
				}
				else {
					$query = "	SELECT group_concat(tunnus) tunnukset, count(*) kpl
								FROM asiakas
								WHERE yhtio = '$kukarow[yhtio]'
								and ytunnus = '$asiakasrow[ytunnus]'";
					$result = pupe_query($query);
					$asiakasrow2 = mysql_fetch_array($result);

					$tunnukset 	= $asiakasrow2['tunnukset'];
					$nimet		= $asiakasrow2['kpl'];
				}

				// Kaatotilin saldo
				if ($savalkoodi != "") {
					$savalkoodi = mysql_real_escape_string($savalkoodi);
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
							and kohdpvm = '0000-00-00'
							$salisa
							group by 1";
				$kaatoresult = pupe_query($query);

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
				$result = pupe_query($query);

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
					<th rowspan='$riveja'><a href='".$palvelin2."crm/asiakasmemo.php?ytunnus=$ytunnus&asiakasid=$asiakasid&lopetus=$lopetus/SPLIT/".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php////ytunnus=$ytunnus//asiakasid=$asiakasid//alatila=$alatila//tila=tee_raportti//lopetus=$lopetus'>$asiakasrow[nimi]</a></th>
					<td rowspan='$riveja'>".t("Kaatotilill�")."</td>";

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

				echo "<tr>
					<th rowspan='$riveja'>$ytunnus ($nimet)</th>
					<td rowspan='$riveja'>".t("My�h�ss� olevia laskuja yhteens�")."</td>";

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
					<th rowspan='$riveja'>$asiakasrow[osoite]</th>
					<td rowspan='$riveja'>".t("Avoimia laskuja yhteens�")."</td>";

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

				echo "<tr>
					<th>$asiakasrow[postino] $asiakasrow[postitp]</th>
					<td colspan='2'></td></tr>";

				echo "<tr>
					<th>$asiakasrow[maa]</th><td colspan='2'><a href='{$palvelin2}raportit/asiakasinfo.php?ytunnus=$ytunnus&asiakasid=$asiakasid&lopetus=$lopetus/SPLIT/".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php////ytunnus=$ytunnus//asiakasid=$asiakasid//alatila=$alatila//tila=tee_raportti//lopetus=$lopetus'>".t("Asiakkaan myyntitiedot")."</a></td>
					</tr>";

				echo "<tr><td colspan='3' class='back'><br></td></tr>";

				if (!isset($vv)) $vv = date("Y");
				if (!isset($kk)) $kk = date("n");
				if (!isset($pp)) $pp = date("j");

				echo "<tr><th>".t("Tiliote p�iv�lle").":</th>
						<td colspan='2'>
						<form id='tulosta_tiliote' name='tulosta_tiliote' method='post'>
						<input type='hidden' name = 'tee' value = 'NAYTATILAUS'>
						<input type='hidden' name = 'tiliote' value = '1'>
						<input type='hidden' name = 'ytunnus' value = '$ytunnus'>
						<input type='hidden' name = 'asiakasid' value = '$asiakasid'>
						<input type='hidden' name = 'alatila' value = '$alatila'>
						<input type = 'text' name = 'pp' value='$pp' size=2>
						<input type = 'text' name = 'kk' value='$kk' size=2>
						<input type = 'text' name = 'vv' value='$vv' size=4>
						<input type='submit' value='",t("Tulosta tiliote"),"' onClick=\"js_openFormInNewWindow('tulosta_tiliote', ''); return false;\"></form>
						</td></tr>";

				echo "</table>";

				echo "<table><tr><td class='back'>";

				// ik�analyysi
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
				list ($naama, $nopeushtml) = laskeMaksunopeus($tunnukset, $kukarow["yhtio"]);

				echo "<br>$naama<br>$nopeushtml</td>";
				echo "</tr></table><br>";

				//avoimet laskut
				$kentat = 'laskunro, tapvm, erpcm, summa, kapvm, kasumma, mapvm, ika, viikorkoeur, olmapvm, tunnus';
				$kentankoko = array(8,8,8,10,8,8,8,4,4,8);

				$lisa = "";
				$havlisa = "";

				$array = explode(",", $kentat);
				$count = count($array);

				for ($i=0; $i<=$count; $i++) {
					// tarkastetaan onko hakukent�ss� jotakin
					if (strlen($haku[$i]) > 0) {
						if (trim($array[$i]) == "ika") {
							$havlisa = " HAVING abs(ika)='$haku[$i]'";
						}
						else {
							$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
						}

						$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
					}
				}
				if (strlen($ojarj) > 0) {
					$jarjestys = $array[$ojarj];
				}
				else{
					$jarjestys = 'erpcm';
				}

				//n�ytet��nk� maksetut vai avoimet
				$chk1 = $chk2 = $chk3 = $chk4 = '';

				if ($valintra == 'maksetut') {
					$chk2 = 'SELECTED';
					$mapvmlisa = " and mapvm > '0000-00-00' ";
				}
				elseif ($valintra == 'kaikki') {
					$chk3 = 'SELECTED';
					$mapvmlisa = " ";
				}
				elseif ($valintra == "eraantyneet") {
					$chk4 = 'SELECTED';
					$mapvmlisa = " and erpcm < now() and mapvm = '0000-00-00' ";
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

                if (!isset($jarjestys_suunta) or $jarjestys_suunta != "desc") {
                    $jarjestys_suunta = "asc";
                }

				$query = "	SELECT laskunro, tapvm, erpcm,
							summa-pyoristys-saldo_maksettu summa,
							summa_valuutassa-pyoristys_valuutassa-saldo_maksettu_valuutassa summa_valuutassa,
							kapvm, kasumma, kasumma_valuutassa, mapvm,
							TO_DAYS(if(mapvm!='0000-00-00', mapvm, now())) - TO_DAYS(erpcm) ika,
							round((viikorkopros * (TO_DAYS(if(mapvm!='0000-00-00', mapvm, now())) - TO_DAYS(erpcm)) * summa / 36500),2) as korko,
							olmapvm korkolaspvm,
							tunnus,
							saldo_maksettu,
							saldo_maksettu_valuutassa,
							valkoodi
							FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
							WHERE yhtio ='$kukarow[yhtio]'
							and tila = 'U'
							and alatila = 'X'
							and liitostunnus in ($tunnukset)
							and tapvm > '0000-00-00'
							$mapvmlisa
							$lisa
							$salisa
							$havlisa
							ORDER BY $jarjestys $jarjestys_suunta";
				$result = pupe_query($query);

				echo "<form action = '$PHP_SELF?ojarj=".$ojarj.$ulisa."' method = 'post'>
						<input type='hidden' name = 'tila' value='$tila'>
						<input type='hidden' name = 'ytunnus' value = '$ytunnus'>
						<input type='hidden' name = 'asiakasid' value = '$asiakasid'>
						<input type='hidden' name = 'alatila' value = '$alatila'>
						<input type='hidden' name='lopetus' value = '$lopetus'>";

				echo "<table>";
				echo "<tr>
						<th>".t("N�yt�").":</th>
						<td>
						<select name='valintra' onchange='submit();'>
						<option value='' $chk1>".t("Avoimet laskut")."</option>
						<option value='eraantyneet' $chk4>".t("Er��ntyneet laskut")."</option>
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
				$aasres = pupe_query($query);

				if (mysql_num_rows($aasres) > 0) {

					echo "<th>".t("Valuutta").":</th><td><select name='savalkoodi' onchange='submit();'>";
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
				echo "</table></form><br>";

				echo "<form action = '$PHP_SELF' method = 'post'>
						<input type='hidden' name = 'tila' value='$tila'>
						<input type='hidden' name = 'ojarj' value='$ojarj'>
						<input type='hidden' name = 'jarjestys_suunta' value='$jarjestys_suunta'>
						<input type='hidden' name = 'ytunnus' value = '$ytunnus'>
						<input type='hidden' name = 'asiakasid' value = '$asiakasid'>
						<input type='hidden' name = 'valintra' value = '$valintra'>
						<input type='hidden' name = 'valuutassako' value = '$valuutassako'>
						<input type='hidden' name = 'savalkoodi' value = '$savalkoodi'>
						<input type='hidden' name = 'alatila' value = '$alatila'>
						<input type='hidden' name = 'lopetus' value = '$lopetus'>";

                $jarjestys_suunta = ($jarjestys_suunta == "asc") ? "desc" : "asc";

				echo "<table>";
				echo "<tr>";
				echo "<th valign='top'><a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&tila=$tila&alatila=$alatila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=0&jarjestys_suunta=$jarjestys_suunta".$ulisa."&lopetus=$lopetus'>".t("Laskunro")."</a></th>";
				echo "<th valign='top'><a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&tila=$tila&alatila=$alatila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=1&jarjestys_suunta=$jarjestys_suunta".$ulisa."&lopetus=$lopetus'>".t("Pvm")."</a></th>";
				echo "<th valign='top'><a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&tila=$tila&alatila=$alatila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=2&jarjestys_suunta=$jarjestys_suunta".$ulisa."&lopetus=$lopetus'>".t("Er�p�iv�")."</a></th>";
				echo "<th valign='top'><a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&tila=$tila&alatila=$alatila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=3&jarjestys_suunta=$jarjestys_suunta".$ulisa."&lopetus=$lopetus'>".t("Summa")."</a></th>";
				echo "<th valign='top'><a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&tila=$tila&alatila=$alatila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=4&jarjestys_suunta=$jarjestys_suunta".$ulisa."&lopetus=$lopetus'>".t("Kassa-ale")."<br>".t("pvm")."</a></th>";
				echo "<th valign='top'><a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&tila=$tila&alatila=$alatila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=5&jarjestys_suunta=$jarjestys_suunta".$ulisa."&lopetus=$lopetus'>".t("Kassa-ale")."<br>".t("summa")."</a></th>";
				echo "<th valign='top'><a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&tila=$tila&alatila=$alatila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=6&jarjestys_suunta=$jarjestys_suunta".$ulisa."&lopetus=$lopetus'>".t("Maksu")."<br>".t("pvm")."</a></th>";
				echo "<th valign='top'><a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&tila=$tila&alatila=$alatila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=7&jarjestys_suunta=$jarjestys_suunta".$ulisa."&lopetus=$lopetus'>".t("Ik�")."</a></th>";
				echo "<th valign='top'><a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&tila=$tila&alatila=$alatila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=8&jarjestys_suunta=$jarjestys_suunta".$ulisa."&lopetus=$lopetus'>".t("Korko")."</a></th>";
				echo "<th valign='top'><a href='$PHP_SELF?ytunnus=$ytunnus&asiakasid=$asiakasid&tila=$tila&alatila=$alatila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=9&jarjestys_suunta=$jarjestys_suunta".$ulisa."&lopetus=$lopetus'>".t("Korkolasku")."<br>".t("pvm")."</a></th>";
				echo "<th valign='top'>".t("Osasuoritukset")."</th>";

				echo "<td class='back'></td></tr>";
				echo "<tr>";

				for ($i = 0; $i < mysql_num_fields($result)-6; $i++) {
					echo "<td><input type='text' size='$kentankoko[$i]' name = 'haku[$i]' value = '$haku[$i]'></td>";
				}

				echo "<td></td><td class='back'><input type='submit' value='".t("Etsi")."'></td></tr>";

				$summa = 0;

				// haetaan kaikki yrityksen rahatilit mysql muodossa
				$query  = "	SELECT concat(group_concat(distinct concat('\'',oletus_rahatili) SEPARATOR '\', '),'\'') rahatilit
							FROM yriti
							WHERE yhtio = '$kukarow[yhtio]'
							and kaytossa = ''
							and oletus_rahatili != ''";
				$ratire = pupe_query($query);
				$ratiro = mysql_fetch_array($ratire);

				while ($maksurow = mysql_fetch_array ($result)) {

					echo "<tr class='aktiivi'>";
					echo "<td><a href='".$palvelin2."muutosite.php?tee=E&tunnus=$maksurow[tunnus]&lopetus=$lopetus/SPLIT/".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php////ytunnus=$ytunnus//asiakasid=$asiakasid//alatila=$alatila//tila=tee_raportti//lopetus=$lopetus'>$maksurow[laskunro]</a></td>";

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

					if ($maksurow["korko"] > 0) echo "<td align='right'>$maksurow[korko]</td>";
					else echo "<td></td>";

					if ($maksurow["korkolaspvm"] != '0000-00-00') echo "<td align='right'>".tv1dateconv($maksurow["korkolaspvm"])."</td>";
					else echo "<td></td>";

					echo "<td align='right'>";

					// jos rahatilej� l�ytyy etsit��n suoritukst
					if ($ratiro["rahatilit"] != "") {
						$query = "	SELECT *
									FROM tiliointi USE INDEX (tositerivit_index)
									WHERE yhtio = '$kukarow[yhtio]' and
									ltunnus = '$maksurow[tunnus]' and
									tilino in ($ratiro[rahatilit]) and
									korjattu = ''";
						$lasktilitre = pupe_query($query);

						// listataan osasuoritukset jos maksup�iv� on nollaa tai jos niit� on oli yks
						if ($maksurow["mapvm"] == "0000-00-00" or mysql_num_rows($lasktilitre) > 1) {
							while ($lasktilitro = mysql_fetch_array($lasktilitre)) {
								if ($lasktilitro["summa_valuutassa"] != 0 and $lasktilitro["valkoodi"] != $yhtiorow["valkoodi"] and $lasktilitro["valkoodi"] != "") {
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

				echo "<tr><th colspan='3'>".t("Yhteens�")."</th><th style='text-align:right'>";

				if (count($totaali) == 1 and isset($totaali[$yhtiorow['valkoodi']])) {
					echo $totaali[$yhtiorow['valkoodi']];
					echo " ".$yhtiorow["valkoodi"];
				}
				else {
					if (count($totaali) > 0) {
						foreach ($totaali as $valuutta => $valsumma) {
							printf("%.2f",$valsumma);
							echo " $valuutta<br>";
						}
					}
					echo "$summa $yhtiorow[valkoodi]";
				}
				echo "</th><th colspan='7'></th></tr>";
				echo "</table>";
				echo "</form>";
				echo "<script LANGUAGE='JavaScript'>document.forms[0][0].focus()</script>";
			}

		}

		if ($ytunnus == '') {
			$formi = 'haku';
			$kentta = 'ytunnus';

			js_popup(-100);

			/* hakuformi */
			echo "<br><form name='$formi' action='$PHP_SELF' method='GET'>";
			echo "<input type='hidden' name='alatila' value='etsi'>";
			echo "<table>";
			echo "<tr><th>".t("Asiakas").":</th>";
			echo "<td><input type='text' name='ytunnus'> ",asiakashakuohje(),"</td>";
			echo "<td class='back'></td></tr>";

			echo "<tr><th>".t("Asiakasraportin rajaus").":</th>";
			echo "<td><select name='alatila'>
				<option value='Y'>".t("Ytunnuksella")."</option>
				<option value='T' $sel[T]>".t("Asiakkaalla")."</option>
				</select></td>";
			echo "<td class='back'><input type='submit' value='".t("Etsi")."'></td></tr>";

			echo "</table>";
			echo "</form>";
		}
	}

	require ("inc/footer.inc");

?>