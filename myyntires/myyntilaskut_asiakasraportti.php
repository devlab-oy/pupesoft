<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	if (isset($_POST['tiliote']) and $_POST['tiliote'] == '1') {
		$nayta_pdf = 1;
	}

	// DataTables päälle
	$pupe_DataTables = "astilmyrerap";

	require ("../inc/parametrit.inc");

	if ((isset($tiliote) and $tiliote == '1') or (!empty($tee) and $tee == 'TULOSTA_EMAIL' and !empty($asiakasid))) {

		require('paperitiliote.php');

		if (!empty($tee) and $tee == 'TULOSTA_EMAIL') {

			$asiakasid = (int) $asiakasid;

			$query = "	SELECT nimi, IF(lasku_email != '', lasku_email, email) AS email
						FROM asiakas
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$asiakasid}'";
			$asiakasresult = pupe_query($query);
			$asiakasrow = mysql_fetch_assoc($asiakasresult);

			$params = array(
				'to' => $asiakasrow['email'],
				'cc' => '',
				'subject' => t("Asiakasraportit myyntilaskuista")." - {$asiakasrow['nimi']}",
				'ctype' => 'html',
				'body' => "",
				'attachements' => array(
					array(
						"filename" => $pdffilenimi,
						"newfilename" => t("Asiakasraportti myyntilaskuista")." - {$asiakasrow['nimi']}.pdf",
						"ctype" => "pdf",
					),
				),
			);

			pupesoft_sahkoposti($params);

			echo "<font class='info'>";
			echo t("Tiliote lähetettiin osoitteeseen").": {$asiakasrow['email']}<br><br>";
			echo "</font>";
		}

		$tee = "";
		$tila = "tee_raportti";
	}

	if (!empty($tee) and $tee == 'TULOSTA_EMAIL_LASKUT' and !empty($laskunrot)) {

		if (@include_once("tilauskasittely/tulosta_lasku.inc"));
		elseif (@include_once("tulosta_lasku.inc"));
		else exit;

		foreach(explode(",", $laskunrot) as $laskunro) {
			tulosta_lasku("LASKU:{$laskunro}", $kieli, $tee, 'LASKU', "asiakasemail{$asiakasemail}", "", "");
		}

		echo "<font class='info'>";

		if (strpos($laskunrot, ",") !== FALSE) echo t("Laskut lähetettiin osoitteeseen"),": {$asiakasemail}";
		else echo t("Lasku lähetettiin osoitteeseen"),": {$asiakasemail}";

		echo "</font><br /><br />";

		$tee = "";
		$tila = "tee_raportti";
	}

	if (!isset($tee)) $tee = "";
	if (!isset($ytunnus)) $ytunnus = "";
	if (!isset($tila)) $tila = "";
	if (!isset($asiakasid)) $asiakasid = 0;
	if (!isset($savalkoodi)) $savalkoodi = "";
	if (!isset($valintra)) $valintra = "";

	if ($tee == "") {

		/* visuaalinen esitys maksunopeudesta (hymynaama) */
		/* palauttaa listan arvoja, joissa ensimmäisessä on
		 * pelkkä img-tagi oikeaan naamaan ja toisessa
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

			$html = t("Myöhässä maksettuja laskuja").": ".sprintf('%.0f', $maksunopeus)."%";
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

			echo "	<script language='javascript' type='text/javascript'>

					$(function() {

						var val = $('#valintra').val();

						if (val == 'eraantyneet') {
							$('#infoteksti').html('",t("Erääntyneet laskut"),"');
						}
						else if (val == 'maksetut') {
							$('#infoteksti').html('",t("Maksetut laskut"),"');
						}
						else if (val == 'kaikki') {
							$('#infoteksti').html('",t("Kaikki laskut"),"');
						}
						else {
							$('#infoteksti').html('",t("Avoimet laskut"),"');
						}

						$('#valintra').on('change', function() {
							$('#riviformi').submit();
						});

						$('.date').on('keyup change blur', function() {

							var id = $(this).attr('id');

							$('#'+id+'_hidden').val($(this).val());
						});

						var laskunrot_loop = function() {

							var nrot = [];

							$('.laskunro:checked').each(function() {
								nrot.push($(this).val());
							});

							$('#laskunrot').val(nrot.join(','));

						}

						$('.laskunro').on('click', laskunrot_loop);

						$('.laskunro_checkall').on('click', function() {

							$('.laskunro').prop('checked', $(this).is(':checked'));

							laskunrot_loop();
						});

						$('#laskunrot_submit').on('click', function(event) {

							event.preventDefault();

							if ($('#laskunrot').val() == '') {
								alert('".t("Et valinnut yhtään laskua")."!');
								return false;
							}

							$('#tulosta_lasku_email').submit();
						});

					});

					</script>";

			if ($alatila == "T" and (int) $asiakasid > 0) {
				$haku_sql = "tunnus = '$asiakasid'";
			}
			else {
				$ytunnus  = mysql_real_escape_string($ytunnus);
				$haku_sql = "ytunnus = '$ytunnus'";
			}

			$query = "	SELECT tunnus, ytunnus, nimi, osoite, postino, postitp, maa, IF(lasku_email != '', lasku_email, email) AS email
						FROM asiakas
						WHERE yhtio = '$kukarow[yhtio]'
						and $haku_sql";
			$result = pupe_query($query);

			if (mysql_num_rows($result) > 0) {

				$asiakasrow = mysql_fetch_array($result);

				// ekotetaan javascriptiä jotta saadaan pdf:ät uuteen ikkunaan
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
							WHERE yhtio = '$kukarow[yhtio]'
							and kohdpvm = '0000-00-00'
							and ltunnus > 0
							and asiakas_tunnus in ($tunnukset)
							and summa  != 0
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

				echo "<tr>
					<th rowspan='$riveja'>$ytunnus ($nimet)</th>
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
					<th rowspan='$riveja'>$asiakasrow[osoite]</th>
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

				echo "<tr>
					<th>$asiakasrow[postino] $asiakasrow[postitp]</th>
					<td colspan='2'></td></tr>";

				echo "<tr>
					<th>$asiakasrow[maa]</th><td colspan='2'><a href='{$palvelin2}raportit/asiakasinfo.php?ytunnus=$ytunnus&asiakasid=$asiakasid&lopetus=$lopetus/SPLIT/".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php////ytunnus=$ytunnus//asiakasid=$asiakasid//alatila=$alatila//tila=tee_raportti//lopetus=$lopetus'>".t("Asiakkaan myyntitiedot")."</a></td>
					</tr>";

				if ($asiakasrow['email'] != '') {
					echo "<tr>";
					echo "<th>",t("Sähköpostiosoite"),"</th>";
					echo "<td colspan='2'>{$asiakasrow['email']}</td>";
					echo "</tr>";
				}

				$as_tunnus = explode(",", $tunnukset);

				foreach ($as_tunnus as $astun) {
					$query  = "	SELECT kentta01
						        FROM kalenteri
						        WHERE yhtio = '$kukarow[yhtio]'
						        AND tyyppi  = 'Myyntireskontraviesti'
						        AND liitostunnus = '$astun'
						        AND yhtio   = '$kukarow[yhtio]'
								ORDER BY tunnus desc
								LIMIT 1";
					$amres = pupe_query($query);

					if (mysql_num_rows($amres) > 0) {
						$amrow = mysql_fetch_assoc($amres);

						echo "<tr>
							<th>".t("Reskontraviesti")."</th><td colspan='2'>$amrow[kentta01]</td>
							</tr>";
					}
				}

				echo "<tr><td colspan='3' class='back'><br></td></tr>";

				echo "</table>";

				if (!isset($vv)) $vv = date("Y");
				if (!isset($kk)) $kk = date("n");
				if (!isset($pp)) $pp = date("j");

				echo "<font class='message'><span id='infoteksti'></span></font><br />";

				echo "<table>";

				echo "<tr><th>",t("Tiliote päivälle"),"</th>
						<td>
						<form id='tulosta_tiliote' name='tulosta_tiliote' method='post'>
						<input type='hidden' name = 'tee' value = 'NAYTATILAUS'>
						<input type='hidden' name = 'tiliote' value = '1'>
						<input type='hidden' name = 'ytunnus' value = '{$ytunnus}'>
						<input type='hidden' name = 'asiakasid' value = '{$asiakasid}'>
						<input type='hidden' name = 'alatila' value = '{$alatila}'>
						<input type = 'text' name = 'pp' id = 'pp' value='{$pp}' size=3 class='date'>
						<input type = 'text' name = 'kk' id = 'kk' value='{$kk}' size=3 class='date'>
						<input type = 'text' name = 'vv' id = 'vv' value='{$vv}' size=5 class='date'>
						<input type='hidden' name = 'valintra' value='{$valintra}' />
						</td>
						<td class='back'>
						<input type='submit' value='",t("Tulosta tiliote"),"' onClick=\"js_openFormInNewWindow('tulosta_tiliote', ''); return false;\">
						</td>
						</form>";

				if ($asiakasrow['email'] != '') {
					echo "</td><td class='back'><form id='tulosta_tiliote_email' name='tulosta_tiliote_email' method='post'>
						<input type='hidden' name = 'tee' value = 'TULOSTA_EMAIL'>
						<input type='hidden' name = 'ytunnus' value = '{$ytunnus}'>
						<input type='hidden' name = 'asiakasid' value = '{$asiakasid}'>
						<input type='hidden' name = 'alatila' value = '{$alatila}'>
						<input type='hidden' name = 'pp' id='pp_hidden' value='{$pp}' size=2>
						<input type='hidden' name = 'kk' id='kk_hidden' value='{$kk}' size=2>
						<input type='hidden' name = 'vv' id='vv_hidden' value='{$vv}' size=4>
						<input type='hidden' name = 'valintra' value='{$valintra}' />
						<input type='submit' value='",t("Lähetä tiliote asiakkaan sähköpostiin"),": {$asiakasrow['email']}' />
						</form>";
				}

				echo "</td></tr>";

				echo "</table>";

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
				list ($naama, $nopeushtml) = laskeMaksunopeus($tunnukset, $kukarow["yhtio"]);

				echo "<br>$naama<br>$nopeushtml</td>";
				echo "</tr></table><br>";

				//näytetäänkö maksetut vai avoimet
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

				if ($savalkoodi != "") {
					$salisa = " and lasku.valkoodi='$savalkoodi' ";
				}

				$query = "	SELECT laskunro, tapvm, erpcm,
							summa loppusumma,
							summa_valuutassa loppusumma_valuutassa,
							if(mapvm!='0000-00-00', 0, summa-saldo_maksettu) avoinsumma,
							if(mapvm!='0000-00-00', 0, summa_valuutassa-saldo_maksettu_valuutassa) avoinsumma_valuutassa,
							kapvm, kasumma, kasumma_valuutassa, mapvm,
							TO_DAYS(if(mapvm!='0000-00-00', mapvm, now())) - TO_DAYS(erpcm) ika,
							round((viikorkopros * (TO_DAYS(if(mapvm!='0000-00-00', mapvm, now())) - TO_DAYS(erpcm)) * summa / 36500),2) as korko,
							olmapvm korkolaspvm,
							tunnus,
							saldo_maksettu,
							saldo_maksettu_valuutassa,
							valkoodi
							FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
							WHERE yhtio ='$kukarow[yhtio]'
							and tila = 'U'
							and alatila = 'X'
							and liitostunnus in ($tunnukset)
							AND tapvm > '0000-00-00'
							$mapvmlisa
							$salisa
							ORDER BY erpcm";
				$result = pupe_query($query);

				echo "<br><form action = 'myyntilaskut_asiakasraportti.php' method = 'post' id='riviformi'>
						<input type='hidden' name = 'tila' value='$tila'>
						<input type='hidden' name = 'ytunnus' value = '$ytunnus'>
						<input type='hidden' name = 'asiakasid' value = '$asiakasid'>
						<input type='hidden' name = 'alatila' value = '$alatila'>
						<input type='hidden' name='lopetus' value = '$lopetus'>";

				echo "<table>";
				echo "<tr>
						<th>".t("Näytä").":</th>
						<td>
						<select name='valintra' id='valintra'>
						<option value='' $chk1>".t("Avoimet laskut")."</option>
						<option value='eraantyneet' $chk4>".t("Erääntyneet laskut")."</option>
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

				if (mysql_num_rows($result) > 0) {
					echo "<form method = 'post'>
							<input type='hidden' name = 'tila' value='$tila'>
							<input type='hidden' name = 'ytunnus' value = '$ytunnus'>
							<input type='hidden' name = 'asiakasid' value = '$asiakasid'>
							<input type='hidden' name = 'valintra' value = '$valintra'>
							<input type='hidden' name = 'savalkoodi' value = '$savalkoodi'>
							<input type='hidden' name = 'alatila' value = '$alatila'>
							<input type='hidden' name = 'lopetus' value = '$lopetus'>";

					pupe_DataTables(array(array($pupe_DataTables, 12, 12, false, false)));

					echo "<table class='display dataTable' id='$pupe_DataTables'><thead>";
					echo "<tr>";
					echo "<th valign='top'>".t("Laskunro")."</th>";
					echo "<th valign='top'>".t("Pvm")."</th>";
					echo "<th valign='top'>".t("Eräpäivä")."</th>";
					echo "<th valign='top'>".t("Summa")."</th>";
					echo "<th valign='top'>".t("Avoinsaldo")."</th>";
					echo "<th valign='top'>".t("Kassa-ale")."<br>".t("pvm")."</th>";
					echo "<th valign='top'>".t("Kassa-ale")."<br>".t("summa")."</th>";
					echo "<th valign='top'>".t("Maksu")."<br>".t("pvm")."</th>";
					echo "<th valign='top'>".t("Ikä")."</th>";
					echo "<th valign='top'>".t("Korko")."</th>";
					echo "<th valign='top'>".t("Korkolasku")."<br>".t("pvm")."</th>";
					echo "<th valign='top'>".t("Osasuoritukset")."</th>";
					echo "</tr>";

					echo "<tr>
					<td><input type='text' class='search_field' name='search_Laskunro'></td>
					<td><input type='text' class='search_field' name='search_Pvm'></td>
					<td><input type='text' class='search_field' name='search_Erapaiva'></td>
					<td><input type='text' class='search_field' name='search_Summa'></td>
					<td><input type='text' class='search_field' name='search_Avoinsumma'></td>
					<td><input type='text' class='search_field' name='search_Kale1'></td>
					<td><input type='text' class='search_field' name='search_Kale2'></td>
					<td><input type='text' class='search_field' name='search_Mapvm'></td>
					<td><input type='text' class='search_field' name='search_Ika'></td>
					<td><input type='text' class='search_field' name='search_Korko'></td>
					<td><input type='text' class='search_field' name='search_Korkolasku'></td>
					<td><input type='text' class='search_field' name='search_Osasuor'></td>
					</tr>";

					echo "</thead>";

					echo "<tbody>";

					$totaali = array();
	                $avoimet = array();
	 				$korkoja = 0;

					// haetaan kaikki yrityksen rahatilit mysql muodossa
					$query  = "	SELECT concat(group_concat(distinct concat('\'',oletus_rahatili) SEPARATOR '\', '),'\'') rahatilit
								FROM yriti
								WHERE yhtio = '$kukarow[yhtio]'
								and kaytossa = ''
								and oletus_rahatili != ''";
					$ratire = pupe_query($query);
					$ratiro = mysql_fetch_array($ratire);

					while ($maksurow = mysql_fetch_array($result)) {

						echo "<tr class='aktiivi'>";
						echo "<td>".pupe_DataTablesEchoSort($maksurow['laskunro']);

						if ($asiakasrow['email'] != '') {
							echo "<input class='laskunro' type='checkbox' value='{$maksurow['laskunro']}' /> ";
						}

						echo "<a href='".$palvelin2."muutosite.php?tee=E&tunnus=$maksurow[tunnus]&lopetus=$lopetus/SPLIT/".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php////ytunnus=$ytunnus//asiakasid=$asiakasid//alatila=$alatila//tila=tee_raportti//lopetus=$lopetus'>$maksurow[laskunro]</a>";
						echo "</td>";

						echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['tapvm']).tv1dateconv($maksurow["tapvm"])."</td>";
						echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['erpcm']).tv1dateconv($maksurow["erpcm"])."</td>";
						echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['loppusumma'])."$maksurow[loppusumma] {$yhtiorow['valkoodi']}";

						if (strtoupper($yhtiorow['valkoodi']) != strtoupper($maksurow['valkoodi'])) {
							echo "<br />{$maksurow['loppusumma_valuutassa']} {$maksurow['valkoodi']}";
						}

						echo "</td>";

						echo "<td align='right'>";

						if ($maksurow["avoinsumma"] != 0) {
							echo "$maksurow[avoinsumma] {$yhtiorow['valkoodi']}";

							if (strtoupper($yhtiorow['valkoodi']) != strtoupper($maksurow['valkoodi'])) {
								echo "<br />{$maksurow['avoinsumma_valuutassa']} {$maksurow['valkoodi']}";
							}
						}

						echo "</td>";

						if ($maksurow["kapvm"] != '0000-00-00') echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['kapvm']).tv1dateconv($maksurow["kapvm"])."</td>";
						else echo "<td></td>";

						if ($maksurow["kasumma"] != 0) {
							echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['kasumma'])."$maksurow[kasumma]";

							if (strtoupper($yhtiorow['valkoodi']) != strtoupper($maksurow['valkoodi'])) {
								echo "<br />" . $maksurow['kasumma_valuutassa'] . ' ' . $maksurow['valkoodi'];
							}
							echo "</td>";
						}
						else {
							 echo "<td></td>";
						}

						if ($maksurow["mapvm"] != '0000-00-00') echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['mapvm']).tv1dateconv($maksurow["mapvm"])."</td>";
						else echo "<td></td>";

						echo "<td align='right'>$maksurow[ika]</td>";

						if ($maksurow["korko"] > 0) echo "<td align='right'>$maksurow[korko]</td>";
						else echo "<td></td>";

						if ($maksurow["korkolaspvm"] != '0000-00-00') echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['korkolaspvm']).tv1dateconv($maksurow["korkolaspvm"])."</td>";
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
							$lasktilitre = pupe_query($query);

							// listataan osasuoritukset jos maksupäivä on nollaa tai jos niitä on oli yks
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
						echo "</tr>";

						if (!isset($totaali[$maksurow['valkoodi']])) $totaali[$maksurow['valkoodi']] = 0;
						if (!isset($avoimet[$maksurow['valkoodi']])) $avoimet[$maksurow['valkoodi']] = 0;

						if (strtoupper($yhtiorow['valkoodi']) != strtoupper($maksurow['valkoodi'])) {
							$totaali[$maksurow['valkoodi']] += $maksurow['loppusumma_valuutassa'];
							$avoimet[$maksurow['valkoodi']] += $maksurow['avoinsumma_valuutassa'];
						}
						else {
							$totaali[$maksurow['valkoodi']] += $maksurow['loppusumma'];
							$avoimet[$maksurow['valkoodi']] += $maksurow['avoinsumma'];
						}

						$korkoja += $maksurow['korko'];
					}

					echo "</tbody>";

					echo "<tfoot>";
					echo "<tr><th colspan='3'>".t("Yhteensä")."</th>";
					echo "<th style='text-align:right'>";

					if (count($totaali) > 0) {
						foreach ($totaali as $valuutta => $valsumma) {
							echo sprintf('%.2f', $valsumma)." $valuutta<br>";
						}
					}
					echo "</th>";

					echo "<th style='text-align:right'>";

					if (count($avoimet) > 0) {
						foreach ($avoimet as $valuutta => $valsumma) {
							echo sprintf('%.2f', $valsumma)." $valuutta<br>";
						}
					}
					echo "</th>";

					echo "<th colspan='4'></th>";

					echo "<th style='text-align:right'>";
					echo sprintf('%.2f', $korkoja)."<br>";
					echo "</th>";

					echo "<th colspan='2'></th>";

					echo "</tr>";
					echo "</tfoot>";

					echo "</table>";
					echo "</form>";

					if ($asiakasrow['email'] != '') {
						echo "<br>";
						echo "<input class='laskunro_checkall' type='checkbox' /> ".t("Valitse kaikki listatut laskut");
						echo "<br />";
						echo "<form id='tulosta_lasku_email' name='tulosta_lasku_email' method='post'>";
						echo "<table>";
						echo "<tr>";
						echo "<td style='vertical-align: middle;' class='back'>";
						echo "<input type='hidden' name = 'tee' value = 'TULOSTA_EMAIL_LASKUT'>
							<input type='hidden' name = 'laskunrot' id='laskunrot' value = ''>
							<input type='hidden' name = 'asiakasemail' value = '{$asiakasrow['email']}' />
							<input type='hidden' name = 'asiakasid' value='{$asiakasrow['tunnus']}' />
							<input type='hidden' name = 'ytunnus' value='{$ytunnus}' />
							<input type='hidden' name = 'valintra' value='{$valintra}' />
							<input type='submit' id='laskunrot_submit' value='",t("Lähetä laskukopiot valituista laskuista asiakkaan sähköpostiin"),": {$asiakasrow['email']}' />";
						echo "</td>";
						echo "</tr>";
						echo "</table>";
						echo "</form>";
					}


				}
				else {
					echo t("Ei laskuja")."!<br>";
				}
			}
		}

		if ($ytunnus == '') {
			$formi = 'haku';
			$kentta = 'ytunnus';

			js_popup(-100);

			/* hakuformi */
			echo "<br><form name='{$formi}' method='GET'>";
			echo "<input type='hidden' name='alatila' value='etsi'>";
			echo "<table>";
			echo "<tr><th>",t("Asiakas"),":</th>";
			echo "<td><input type='text' name='ytunnus'> ",asiakashakuohje(),"</td>";
			echo "<td class='back'></td></tr>";

			$sel = (!empty($alatila) and $alatila == 'T') ? "selected" : "";

			echo "<tr><th>",t("Asiakasraportin rajaus"),":</th>";
			echo "<td><select name='alatila'>
				<option value='Y'>",t("Ytunnuksella"),"</option>
				<option value='T' {$sel}>",t("Asiakkaalla"),"</option>
				</select></td>";
			echo "<td class='back'><input type='submit' value='",t("Etsi"),"'></td></tr>";

			echo "</table>";
			echo "</form>";
		}
	}

	require ("inc/footer.inc");
