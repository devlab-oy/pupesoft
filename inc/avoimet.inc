<?php

	if ($muutparametrit != '') list($tee,$vv,$kk,$pp,$laji,$naytetaankokommentit,$savalkoodi,$valuutassako,$maa,$maksuehto,$excel) = explode("/",$muutparametrit);

	echo "<font class=head>".t("Avoimet laskut")."</font><hr>";

	// scripti balloonien tekemiseen
	js_popup();

	// Tarkistetaan pvm
	if ($tee != '') {
		// muutetaan numeroiksi
		$kk += 0;
		$pp += 0;
		$vv += 0;
		$pvm = sprintf("%04d", $vv) . "-" . sprintf("%02d", $kk) . "-" . sprintf("%02d", $pp);

		if (!checkdate($kk, $pp, $vv)) {
			echo "<font class='error'>".t("Virheellinen pvm")."</font><br>";
			$tee = '';
		}

		// katotaan onko annettu tämä päivä (että voidaanko näyttää kirjanpitovertailuja
		$onkotanaan = "";
		if ($pvm != date("Y-m-d")) {
			$onkotanaan = "NO";
		}

		// Löytyykö oikea tilikausi?
		$query = "	SELECT * FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]'
					and '$pvm' >= tilikausi_alku
					and '$pvm' <= tilikausi_loppu";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Päivämäärälle ei löydy tilikautta")."</font><br>";
			$tee = '';
		}
		else {
			$tilikausirow = mysql_fetch_array($result);
		}
	}

	// Valitaan asiakas, jos sitä ei tiedetä
	if ($tee != '' and $ytunnus != '') {

		$ytunnus = mysql_real_escape_string($ytunnus);
		$muutparametrit = $tee."/".$vv."/".$kk."/".$pp."/".$laji."/".$naytetaankokommentit."/".$savalkoodi."/".$valuutassako."/".$maa."/".$maksuehto."/".$excel;

		if (substr($laji,0,1) == 'M') {
			include "asiakashaku.inc";
			if ($asiakasid == '') $tee = '';
		}

		if (substr($laji,0,1) == 'O') {
			include "kevyt_toimittajahaku.inc";
			if ($toimittajaid == '') $tee = '';
		}
	}

	// mikä kuu/vuosi nyt on
	$year = date("Y");
	$kuu  = date("n");

	if (!isset($vv)) $vv = date("Y");
	if (!isset($kk)) $kk = date("n");
	if (!isset($pp)) $pp = date("j");

	$sel[$laji] = " selected ";

	echo "	<form name = 'valinta' action = 'raportit.php' method='post'>
			<input type = 'hidden' name = 'toim' value = 'avoimet'>
			<input type = 'hidden' name = 'tee' value = 'X'>
			<table>
			<tr>
			<th>".t("Anna päivämäärä, muodossa pp-kk-vvvv:")."</th>
			<td><input type = 'text' name = 'pp' value='$pp' size=2></td>
			<td><input type = 'text' name = 'kk' value='$kk' size=2></td>
			<td><input type = 'text' name = 'vv' value='$vv' size=4></td>
			</tr>

			<tr>
			<th>".t("Mitkä laskut listataan:")."</th>
			<td colspan = '3'><select name='laji'>
			<option value='M'   $sel[M]>".t("myyntisaamiset")."</option>
			<option value='MF'  $sel[MF]>".t("factoringmyyntisaamiset")."</option>
			<option value='MK'  $sel[MK]>".t("konsernimyyntisaamiset")."</option>
			<option value='MMK' $sel[MMK]>".t("myyntisaamiset + konsernimyyntisaamiset")."</option>
			<option value='MA'  $sel[MA]>".t("myyntisaamiset + factoringmyyntisaamiset + konsernimyyntisaamiset")."</option>
			<option value='O'   $sel[O]>".t("ostovelat")."</option>
			<option value='OK'  $sel[OK]>".t("konserniostovelat")."</option>
			<option value='OOK' $sel[OOK]>".t("ostovelat + konserniostovelat")."</option>
			</select></td>
			</tr>";

	echo "	<tr>
			<th>".t("Vain toimittaja/asiakas").":</th>
			<td colspan='3'><input type = 'text' name = 'ytunnus' value='$ytunnus'></td>
			</tr>";

	if ($naytetaankokommentit != "") $komsel = "CHECKED";
	else $komsel = "";

	echo "	<tr>
			<th>".t("Älä näytä laskujen kommentteja").":</th>
			<td colspan='3'><input type = 'checkbox' name = 'naytetaankokommentit' $komsel></td>
			</tr>";

	$query = "	SELECT nimi, tunnus
				FROM valuu
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY jarjestys";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<tr><th>".t('Näytä vain valuutta') .":</th><td colspan=3><select name='savalkoodi'>";
	echo "<option value = ''>".t("Kaikki")."</option>";

	while ($vrow = mysql_fetch_array($vresult)) {
		$sel="";
	 	if (strtoupper($vrow['nimi']) == strtoupper($savalkoodi)) {
	 		$sel = "selected";
	 	}
		echo "<option value = '$vrow[nimi]' $sel>$vrow[nimi]</option>";
	}

	echo "</select></td>";
	echo "</tr>";

	$sel1 = '';

	if ($valuutassako == 'V') {
		$sel1 = "SELECTED";
	}

	echo "<tr><th>".t("Summat valuutassa").":</th>";
	echo "<td colspan=3><select name='valuutassako'>";
	echo "<option value = ''>".t("Yrityksen valuutassa")."</option>";
	echo "<option value = 'V' $sel1>".t("Laskun valuutassa")."</option>";
	echo "</select></td></tr>";

	echo "<tr><th>" . t('Maa'). " ".t("(vain myyntilaskut)").":</th><td colspan=3><select name='maa'>";

	$query = "select distinct maa from asiakas where yhtio='$kukarow[yhtio]' and maa!=''";
	$res = mysql_query($query) or pupe_error($query);

	echo "<option value=''>" . t('Kaikki'). "</option>";

	while ($rowmaa = mysql_fetch_array($res)) {
		if ($maa == $rowmaa['maa']) {
			$sel = "SELECTED";
		}
		else {
			$sel = "";
		}

		echo "<option value='$rowmaa[maa]' $sel>".maa($rowmaa['maa'])."</option>";
	}

	echo "</td></tr>";

	echo "<tr><th>".t("Maksuehto")." ".t("(vain myyntilaskut)").":</th>";
	echo "<td colspan='3'><select name='maksuehto'>";
	echo "<option value=''>".t("Ei valintaa")."";

	$query = "	SELECT *
				FROM maksuehto
				WHERE yhtio = '$kukarow[yhtio]'
				and (sallitut_maat = '' or sallitut_maat like '%$srow[maa]%')
				order by jarjestys, teksti, tunnus";
	$mresult = mysql_query($query) or pupe_error($query);

	while($row = mysql_fetch_array($mresult)) {
		if ($row["tunnus"] == $maksuehto) {
			$sel = 'selected';
		}
		else {
			$sel = "";
		}

		echo "<option value='$row[tunnus]' $sel>".t_tunnus_avainsanat($row, "teksti", "MAKSUEHTOKV")."</option>";
	}
	echo "</select></td></tr>";

	if ($excel != "") $exsel = "CHECKED";
	else $exsel = "";

	echo "<tr><th>".t("Tee Excel")."</th><td colspan='3'><input type='checkbox' name='excel' value = 'YES' $exsel></td></tr>";

	$sel1 = '';

	if ($asitoimittain == 'AT') {
		$sel1 = "SELECTED";
	}

	echo "<tr><th>".t("Summaustaso").":</th>";
	echo "<td colspan=3><select name='asitoimittain'>";
	echo "<option value = ''>".t("Lasku")."</option>";
	echo "<option value = 'AT' $sel1>".t("Asiakas/Toimittaja")."</option>";
	echo "</select></td></tr>";

	echo "</table><br>";
	echo "<input type = 'submit' value = '".t("Aja raportti")."'>";
	echo "</form><br><br>";

	$formi = 'valinta';
	$kentta = 'pp';

	if ($tee != '') {

		switch ($laji) {
			case 'M' :
				$lista = t("myyntisaamiset");
				break;
			case 'MF' :
				$lista = t("factoringmyyntisaamiset");
				break;
			case 'MK' :
				$lista = t("konsernimyyntisaamiset");
				break;
			case 'MMK' :
				$lista = t("myyntisaamiset + konsernimyyntisaamiset");
				break;
			case 'MA' :
				$lista = t("myyntisaamiset + factoringmyyntisaamiset + konsernimyyntisaamiset");
				break;
			case 'O' :
				$lista = t("ostovelat");
				break;
			case 'OK' :
				$lista = t("konserniostovelat");
				break;
			case 'OOK' :
				$lista = t("ostovelat + konserniostovelat");
				break;
		}

		$valkoodi  = "";
		$valkoodi2 = "";

		if (isset($savalkoodi) and $savalkoodi != '') {
			$valkoodi1 = $valkoodi = " and lasku.valkoodi = '$savalkoodi'";
			$valkoodi2 = " and suoritus.valkoodi = '$savalkoodi'";
		}

		if ($ojarj != "") {
			$jarj = $ojarj;
		}
		else {
			$jarj = "lasku.ytunnus";
		}

		if (substr($laji,0,1) == 'O') {

			$lisa = '';

			if ($toimittajaid != '')  {
				$lisa = " and liitostunnus='$toimittajaid'";
				$indeksi = " use index (yhtio_tila_liitostunnus_tapvm)";
			}
			else {
				$indeksi = " use index (yhtio_tila_tapvm)";
			}

			if ($laji == 'OOK') 	$tili = "'".$yhtiorow['ostovelat']."','".$yhtiorow['konserniostovelat']."'";
			elseif ($laji == 'OK') 	$tili = "'".$yhtiorow['konserniostovelat']."'";
			else 					$tili = "'".$yhtiorow['ostovelat']."'";

			if ($asitoimittain == "AT") {
				$query = "	SELECT
							lasku.liitostunnus,
							lasku.ytunnus,
							lasku.valkoodi,
							group_concat(lasku.comments) comments,
							concat_ws(' - ', min(lasku.erpcm), max(lasku.erpcm)) erpcm,
							concat_ws(' - ', min(lasku.mapvm), max(lasku.mapvm)) mapvm,
							max(lasku.nimi) nimi,
							concat_ws(' - ', min(lasku.olmapvm), max(lasku.olmapvm)) olmapvm,
							concat_ws(' - ', min(lasku.laskunro), max(lasku.laskunro)) laskunro,
							concat_ws(' - ', min(lasku.tapvm), max(lasku.tapvm)) tapvm,
							max(lasku.tunnus) tunnus,
							concat_ws(' - ', min(concat_ws(' ', viite, viesti)), max(concat_ws(' ', viite, viesti))) maksutieto,
							sum(tiliointi.summa * -1) avoinsaldo,
							sum(round(lasku.summa * lasku.vienti_kurssi, 2)) laskun_summa,
							sum(lasku.summa) summa";

				$groupby = "GROUP BY 1,2,3";
			}
			else {
				$query = "	SELECT
							lasku.ytunnus,
							lasku.valkoodi,
							lasku.comments,
							lasku.erpcm,
							lasku.mapvm,
							lasku.nimi,
							lasku.olmapvm,
							if(lasku.laskunro = 0, '', laskunro) laskunro,
							lasku.tapvm,
							lasku.tunnus,
							concat_ws(' ', viite, viesti) maksutieto,
							tiliointi.summa * -1 avoinsaldo,
							round(lasku.summa * lasku.vienti_kurssi, 2) laskun_summa,
							lasku.summa";

				$groupby = "";
			}


			$query .= "	FROM lasku use index (yhtio_tila_tapvm)
						JOIN tiliointi use index (tositerivit_index) ON (lasku.yhtio=tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and lasku.tapvm = tiliointi.tapvm and tiliointi.tilino IN ($tili) and tiliointi.korjattu = '')
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and (mapvm > '$pvm' or mapvm = '0000-00-00')
						and lasku.tapvm <= '$pvm'
						and lasku.tapvm > '0000-00-00'
						and tila in ('H','Y','M','P','Q')
						$lisa
						$valkoodi1
						$groupby
						ORDER BY $jarj";
			$result = mysql_query($query) or pupe_error($query);

			// poimitaan kuluva päivä, raportin timestampille
			$today = date("Y-m-d");

			echo "<font class='message'>".t("Avoimet")." $lista</font><br><font class='message'>".t("Raportti otettu")." ".tv1dateconv($today)."</font><br>";

			if ($toimittajaid == '') {
				echo "<font class='message'>".t("Yrityksellä")." $yhtiorow[nimi] ".t("oli")." ".tv1dateconv($pvm)." ".t("seuraavat avoimet")." $lista:</font><br>";
			}

			$footerCallBack = ",
				\"fnFooterCallback\": function ( nRow, aaData, iStart, iEnd, aiDisplay ) {
					var avoimety = new Array();
					var valuutat = new Array();
					var valuutta = '';

					// Summataan vain ruudulla näkyvät luvut
					for (var i=iStart; i<iEnd; i++) {
						valuutta = aaData[aiDisplay[i]][9];
						valuutat[valuutta] = valuutta;

						if (typeof avoimety[valuutta] == 'undefined') avoimety[valuutta] = 0;
						avoimety[valuutta] += aaData[aiDisplay[i]][8]*1;
					}

					// Tyhjennetään kaikki summat
					$('td[name=avoimet_yhteensa]').each(
						function() {
							$( this ).html('');
						}
					);

					// Ekotetaan vain ne valuutat jotka löytyy
					for (valuutta in valuutat) {
						if (typeof avoimety[valuutta] == 'undefined') avoimety[valuutta] = 0;
						$('#avoimet_yhteensa_'+valuutta).html(avoimety[valuutta].toFixed(2));
					}
				}";

			pupe_DataTables($pupe_DataTables, 10, 11, $footerCallBack);

			echo "<table class='display' id='$pupe_DataTables'>";

			echo "<thead>";
			echo "<tr>
					<th>".t("Ytunnus")."</th>
					<th>".t("Nimi")."</th>
					<th>".t("Maksutieto")."</th>
					<th>".t("Laskunro")."</th>
					<th>".t("Pvm")."</th>
					<th>".t("Maksupäivä")."</th>
					<th>".t("Oletusmaksupäivä")."</th>
					<th>".t("Eräpäivä")."</th>
					<th>".t("Avoin saldo")."</th>
					<th>".t("Val")."</th>
					<th class='back'></th>
					</tr>
					<tr>
					<td><input type='text' name='search_ytunnus'></td>
					<td><input type='text' name='search_nimi'></td>
					<td><input type='text' name='search_maksutieto'></td>
					<td><input type='text' name='search_laskunro'></td>
					<td><input type='text' name='search_pvm'></td>
					<td><input type='text' name='search_maksupaiva'></td>
					<td><input type='text' name='search_olmaksupaiva'></td>
					<td><input type='text' name='search_erapaiva'></td>
					<td><input type='text' name='search_avoinsaldo'></td>
					<td><input type='text' name='search_val'></td>
					<td class='back'></td>
				</tr>";

			echo "</thead>";
			echo "<tbody>";

			if (isset($workbook)) {
				$worksheet->write($excelrivi, 0, t("Ytunnus"), $format_bold);
				$worksheet->write($excelrivi, 1, t("Nimi"), $format_bold);
				$worksheet->write($excelrivi, 2, t("Maksutieto"), $format_bold);
				$worksheet->write($excelrivi, 3, t("Laskunro"), $format_bold);
				$worksheet->write($excelrivi, 4, t("Pvm"), $format_bold);
				$worksheet->write($excelrivi, 5, t("Maksupäivä"), $format_bold);
				$worksheet->write($excelrivi, 6, t("Oletusmaksupäivä"), $format_bold);
				$worksheet->write($excelrivi, 7, t("Eräpäivä"), $format_bold);
				$worksheet->write($excelrivi, 8, t("Avoin saldo"), $format_bold);
				$worksheet->write($excelrivi, 9, t("Val"), $format_bold);
				$excelrivi++;
			}

			$summa  = array();
			$summa2 = 0;

			while ($trow = mysql_fetch_array ($result)) {
				if ($trow['avoinsaldo'] != 0) {

					// Laskun valuutassa
					$summa[$trow["valkoodi"]] += $trow['summa'];

					// Yrityksen valuutassa
					$summa2+= $trow['avoinsaldo'];

					echo "<tr class='aktiivi'>";
					echo "<td><a name='$trow[tunnus]' href='".$palvelin2."raportit.php?toim=toimittajahaku&tee=A&ytunnus=$trow[ytunnus]&alku=0&laji=M&lopetus=".$palvelin2."raportit.php////toim=$toim//tee=$tee//pp=$pp//kk=$kk//vv=$vv//laji=$laji//ytunnus=$ytunnus//savalkoodi=$savalkoodi//valuutassako=$valuutassako//maa=$maa//maksuehto=$maksuehto//toimittajaid=$toimittajaid//asiakasid=$asiakasid///$trow[tunnus]'>$trow[ytunnus]</a></td>";
					echo "<td>$trow[nimi]</td>";
					echo "<td>$trow[maksutieto]";

					if ($trow["comments"] != "" and $naytetaankokommentit == "") {
						echo "<a class='tooltip' id='$trow[tunnus]'><img src='{$palvelin2}pics/lullacons/info.png'></a>";
						echo "<div id='div_$trow[tunnus]' class='popup' style='width:500px;'>";
						echo $trow["comments"];
						echo "</div>";
					}

					echo "</td>";
					echo "<td>$trow[laskunro]</td>";

					if ($asitoimittain == "AT") {
						list($ap,$lp) = explode(" - ", $trow["tapvm"]);
						echo "<td>".tv1dateconv($ap)." - ".tv1dateconv($lp)."</td>";

						list($ap,$lp) = explode(" - ", $trow["mapvm"]);
						echo "<td>".tv1dateconv($ap)." - ".tv1dateconv($lp)."</td>";

						list($ap,$lp) = explode(" - ", $trow["olmapvm"]);
						echo "<td>".tv1dateconv($ap)." - ".tv1dateconv($lp)."</td>";

						list($ap,$lp) = explode(" - ", $trow["erpcm"]);
						echo "<td>".tv1dateconv($ap)." - ".tv1dateconv($lp)."</td>";
					}
					else {
						echo "<td>".tv1dateconv($trow["tapvm"])."</td>";
						echo "<td>".tv1dateconv($trow["mapvm"])."</td>";
						echo "<td>".tv1dateconv($trow["olmapvm"])."</td>";
						echo "<td>".tv1dateconv($trow["erpcm"])."</td>";
					}

					if ($valuutassako == "V") {
						// Laskun valuutassa
						echo "<td align='right'>$trow[summa]</td>";
						echo "<td align='right'>$trow[valkoodi]</td>";
					}
					else {
						// Yrityksen valuutassa
						echo "<td align='right'>$trow[avoinsaldo]</td>";
						echo "<td align='right'>$yhtiorow[valkoodi]</td>";
					}

					// jos ollaan ajettu tälle päivälle laskuittain voidaan echota errori avoimesta saldosta
					if ($asitoimittain == "" and $onkotanaan == "" and abs($trow["avoinsaldo"] - $trow["laskun_summa"]) >= 0.02) {
						echo "<td class='back' nowrap><font class='error'>VIRHE: Lasku / Kirjanpito heittää!</font> $trow[avoinsaldo] / $trow[laskun_summa]</td>";
					}
					else {
						echo "<td class='back'></td>";
					}

					echo "</tr>";

					if (isset($workbook)) {
						$worksheet->writeString($excelrivi, 0, $trow["ytunnus"]);
						$worksheet->writeString($excelrivi, 1, $trow["nimi"]);
						$worksheet->write($excelrivi, 2, $trow["maksutieto"]);
						$worksheet->write($excelrivi, 3, $trow["laskunro"]);
						$worksheet->write($excelrivi, 4, $trow["tapvm"]);

						if ($trow["mapvm"] != '0000-00-00') $worksheet->write($excelrivi, 5, $trow["mapvm"]);
						else $worksheet->write($excelrivi, 5, "");

						$worksheet->write($excelrivi, 6, $trow["olmapvm"]);
						$worksheet->write($excelrivi, 7, $trow["erpcm"]);

						if ($valuutassako == "V") {
							$worksheet->write($excelrivi, 8, $trow["summa"]);
							$worksheet->write($excelrivi, 9, $trow["valkoodi"]);
						}
						else {
							$worksheet->write($excelrivi, 8, $trow["avoinsaldo"]);
							$worksheet->write($excelrivi, 9, $yhtiorow["valkoodi"]);
						}

						$excelrivi++;
					}
				}
			}

			echo "</tbody>";
			echo "<tfoot>";

			foreach ($summa as $val => $sum) {
				echo "<tr>";
				echo "<th colspan='8'>".t("Avoimet yhteensä").":</th>";
				echo "<td class='tumma' align='right' nowrap name='avoimet_yhteensa' id='avoimet_yhteensa_$val'>".sprintf("%.2f", $sum)."</td>";
				echo "<td class='tumma' align='right' nowrap>$val</td>";
				echo "</tr>";

				if(!in_array($val, $kaikkival)) $kaikkival[] = $val;
			}

			$query = "	SELECT sum(summa) saldo
						FROM tiliointi
						WHERE yhtio	 = '$kukarow[yhtio]'
						and korjattu = ''
						and tapvm	>= '$tilikausirow[tilikausi_alku]'
						and tapvm	<= '$pvm'
						and tilino	IN ($tili)";
			$result = mysql_query($query) or pupe_error($query);
			$trow = mysql_fetch_array ($result);

			$erotus = $trow['saldo']+$summa2;

			echo "<tr><td class='back' colspan='10'><br></td></tr>";
			echo "<tr><th colspan='8'>".t("Avoimet yhteensä").":</th><td class='tumma' align='right' nowrap>".sprintf("%01.2f", $summa2)."</td><th>$yhtiorow[valkoodi]</th></tr>";
			echo "<tr><th colspan='8'>".t("Tili %s yhteensä", "", $tili).":</th><td class='tumma' align='right' nowrap>".sprintf("%01.2f", $trow['saldo'])."</td><th>$yhtiorow[valkoodi]</th></tr>";
			echo "<tr><th colspan='8'>".t("Ero").":</th><td class='tumma' align='right' nowrap>".sprintf("%01.2f", $erotus)."</td><th>$yhtiorow[valkoodi]</th></tr>";

			echo "</tfoot>";
		}
		elseif (substr($laji,0,1) == 'M') {

			$lisa1 = ""; // lasku rajauksia
			$lisa2 = ""; // suoritus rajauksia
			$lisa3 = ""; // asiakas rajauksia

			if ($asiakasid != '')  {
				$lisa1 .= " and liitostunnus='$asiakasid' ";
				$lisa2 .= " and asiakas_tunnus='$asiakasid' ";
				$indeksi = " use index (yhtio_tila_liitostunnus_tapvm)";
			}
			else {
				$indeksi = " use index (yhtio_tila_mapvm) ";
			}

			if ($maksuehto != '') {
				$lisa1 .= " and lasku.maksuehto = '$maksuehto' ";
			}

			$maalisa = '';
			if (isset($maa) and $maa != '') {
				$maalisa = " and lasku.maa = '$maa'";
			}

			$tili = 0;

			if ($laji == 'MK') 		$tili = "'$yhtiorow[konsernimyyntisaamiset]'";
			elseif ($laji == 'MF') 	$tili = "'$yhtiorow[factoringsaamiset]'";
			elseif ($laji == 'MA') 	$tili = "'$yhtiorow[myyntisaamiset]', '$yhtiorow[factoringsaamiset]', '$yhtiorow[konsernimyyntisaamiset]'";
			elseif ($laji == 'MMK') $tili = "'$yhtiorow[myyntisaamiset]', '$yhtiorow[konsernimyyntisaamiset]'";
			else 					$tili = "'$yhtiorow[myyntisaamiset]'";

			if ($asitoimittain == "AT") {
				$query = "	SELECT
							lasku.liitostunnus,
							lasku.ytunnus,
							lasku.valkoodi,
							max(lasku.tunnus) tunnus,
							concat_ws(' - ', min(lasku.erpcm), max(lasku.erpcm)) erpcm,
							max(lasku.liitostunnus) liitostunnus,
							concat_ws(' - ', min(lasku.mapvm), max(lasku.mapvm)) mapvm,
							max(concat_ws('<br>', concat(lasku.nimi,' ',lasku.nimitark), concat(lasku.toim_nimi,' ',lasku.toim_nimitark))) nimi,
							concat_ws(' - ', min(lasku.tapvm), max(lasku.tapvm)) tapvm,
							concat_ws(' - ', min(lasku.laskunro), max(lasku.laskunro)) maksutieto,
							sum(lasku.summa-lasku.saldo_maksettu) laskuavoinsaldo,
							sum(lasku.summa_valuutassa-lasku.saldo_maksettu_valuutassa) laskuavoinsaldo_valuutassa,
							sum(lasku.summa) laskusumma,
							sum(lasku.summa_valuutassa) laskusumma_valuutassa,";

				$groupby = "GROUP BY 1,2,3";
			}
			else {
				$query = "	SELECT
							lasku.ytunnus,
							lasku.valkoodi,
							lasku.tunnus,
							lasku.erpcm,
							lasku.liitostunnus,
							lasku.mapvm,
							lasku.nimi,
							lasku.tapvm,
							lasku.laskunro maksutieto,
							lasku.summa-lasku.saldo_maksettu laskuavoinsaldo,
							lasku.summa_valuutassa-lasku.saldo_maksettu_valuutassa laskuavoinsaldo_valuutassa,
							lasku.summa laskusumma,
							lasku.summa_valuutassa laskusumma_valuutassa,";

				$groupby = "GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13";
			}

			$query .= "	sum(tiliointi.summa) tiliointiavoinsaldo,
						sum(tiliointi.summa_valuutassa) tiliointiavoinsaldo_valuutassa,
						sum(if(tiliointi.tapvm = lasku.tapvm, tiliointi.summa, 0))-lasku.pyoristys tiliointisumma,
						sum(if(tiliointi.tapvm = lasku.tapvm, tiliointi.summa_valuutassa, 0))-lasku.pyoristys_valuutassa tiliointisumma_valuutassa
						FROM lasku $indeksi
						JOIN tiliointi use index (tositerivit_index) ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and tiliointi.tilino in ($tili) and tiliointi.korjattu = '' and tiliointi.tapvm <= '$pvm')
				  		WHERE lasku.yhtio		= '$kukarow[yhtio]'
						and (lasku.mapvm		> '$pvm' or lasku.mapvm = '0000-00-00')
						and lasku.tapvm			<= '$pvm'
						and lasku.tapvm 		> '0000-00-00'
						and lasku.tila			= 'U'
						and lasku.alatila		= 'X'
						$lisa1
						$valkoodi
						$maalisa
						$groupby
						ORDER BY $jarj, lasku.laskunro";
			$result = mysql_query($query) or pupe_error($query);

			// poimitaan kuluva päivä, raportin timestampille
			$today = date("Y-m-d");

			echo "<font class='message'>".t("Avoimet")." $lista</font><br><font class='message'>".t("Raportti otettu")." ".tv1dateconv($today)."</font><br>";

			if ($toimittajaid == '') {
				echo "<font class='message'>".t("Yrityksellä")." $yhtiorow[nimi] ".t("oli")." ".tv1dateconv($pvm)." ".t("seuraavat avoimet")." $lista:</font><br>";
			}

			$footerCallBack = ",
				\"fnFooterCallback\": function ( nRow, aaData, iStart, iEnd, aiDisplay ) {
					var avoimety = new Array();
					var avoimetsuoriy = new Array();
					var valuutat = new Array();
					var yseroyhteensa = 0;
					var valuutta = '';

					// Summataan vain ruudulla näkyvät luvut
					for (var i=iStart; i<iEnd; i++) {
						valuutta = aaData[aiDisplay[i]][8];
						valuutat[valuutta] = valuutta;

						if (aaData[aiDisplay[i]][6] == '') {
							if (typeof avoimetsuoriy[valuutta] == 'undefined') avoimetsuoriy[valuutta] = 0;
							avoimetsuoriy[valuutta] += aaData[aiDisplay[i]][7]*1;
						}
						else {
							if (typeof avoimety[valuutta] == 'undefined') avoimety[valuutta] = 0;
							avoimety[valuutta] += aaData[aiDisplay[i]][7]*1;
						}
					}

					// Tyhjennetään kaikki summat
					$('td[name=avoimet_yhteensa]').each(
						function() {
							$( this ).html('');
						}
					);

					// Ekotetaan vain ne valuutat jotka löytyy
					for (valuutta in valuutat) {
						if (typeof avoimetsuoriy[valuutta] == 'undefined') avoimetsuoriy[valuutta] = 0;
						if (typeof avoimety[valuutta] == 'undefined') avoimety[valuutta] = 0;

						$('#avoimet_yhteensa_'+valuutta).html(avoimety[valuutta].toFixed(2));
						$('#avoimet_suoriyhteensa_'+valuutta).html(avoimetsuoriy[valuutta].toFixed(2));

						yseroyhteensa = avoimety[valuutta] + avoimetsuoriy[valuutta];
						$('#avoimet_yseroyhteensa_'+valuutta).html(yseroyhteensa.toFixed(2));
					}
				}";

			pupe_DataTables($pupe_DataTables, 9, 10, $footerCallBack);

			echo "<table class='display' id='$pupe_DataTables'>";

			echo "<thead>";
			echo "<tr>
					<th>".t("Ytunnus")."</th>
					<th>".t("Nimi")."</th>
					<th>".t("Laskunro")."</th>
					<th>".t("Pvm")."</th>
					<th>".t("Maksupäivä")."</th>
					<th>".t("Eräpäivä")."</th>
					<th>".t("Laskun summa")."</th>
					<th>".t("Avoin saldo")."</th>
					<th>".t("Val")."</th>
					<th class='back'></th>
					</tr>
					<tr>
					<td><input type='text' name='search_ytunnus'></td>
					<td><input type='text' name='search_nimi'></td>
					<td><input type='text' name='search_laskunro'></td>
					<td><input type='text' name='search_pvm'></td>
					<td><input type='text' name='search_maksupaiva'></td>
					<td><input type='text' name='search_erapvm'></td>
					<td><input type='text' name='search_summa'></td>
					<td><input type='text' name='search_avoinsaldo'></td>
					<td><input type='text' name='search_val'></td>
					<td class='back'></td>
				</tr>";

			echo "</thead>";
			echo "<tbody>";

			if (isset($workbook)) {
				$worksheet->write($excelrivi, 0, t("Ytunnus"), $format_bold);
				$worksheet->write($excelrivi, 1, t("Nimi"), $format_bold);
				$worksheet->write($excelrivi, 2, t("Laskunro"), $format_bold);
				$worksheet->write($excelrivi, 3, t("Pvm"), $format_bold);
				$worksheet->write($excelrivi, 4, t("Maksupäivä"), $format_bold);
				$worksheet->write($excelrivi, 5, t("Eräpäivä"), $format_bold);
				$worksheet->write($excelrivi, 6, t("Laskun summa"), $format_bold);
				$worksheet->write($excelrivi, 7, t("Avoin saldo"), $format_bold);
				$worksheet->write($excelrivi, 8, t("Val"), $format_bold);
				$excelrivi++;
			}

			$summa = array(); // tässä kirjanpidosta osasuoritukset poistettuna
			$summatotal = 0;  // ässä koko höskä kotivaluutassa

			while ($trow = mysql_fetch_array ($result)) {

				if ($trow['tiliointiavoinsaldo'] != 0) {

					echo "<tr class='aktiivi'>";
					echo "<td><a name='$trow[tunnus]' href='".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php?ytunnus=$trow[ytunnus]&asiakasid=$trow[liitostunnus]&alatila=Y&tila=tee_raportti&lopetus=$PHP_SELF////toim=$toim//tee=$tee//pp=$pp//kk=$kk//vv=$vv//laji=$laji//ytunnus=$ytunnus//savalkoodi=$savalkoodi//valuutassako=$valuutassako//maa=$maa//maksuehto=$maksuehto//toimittajaid=$toimittajaid//asiakasid=$asiakasid///$trow[tunnus]'>$trow[ytunnus]</a></td>";
					echo "<td>$trow[nimi]</td>";
					echo "<td>$trow[maksutieto]</td>";

					if ($asitoimittain == "AT") {
						list($ap,$lp) = explode(" - ", $trow["tapvm"]);
						echo "<td>".tv1dateconv($ap)." - ".tv1dateconv($lp)."</td>";

						list($ap,$lp) = explode(" - ", $trow["mapvm"]);
						echo "<td>".tv1dateconv($ap)." - ".tv1dateconv($lp)."</td>";

						list($ap,$lp) = explode(" - ", $trow["erpcm"]);
						echo "<td>".tv1dateconv($ap)." - ".tv1dateconv($lp)."</td>";
					}
					else {
						echo "<td>".tv1dateconv($trow["tapvm"])."</td>";
						echo "<td>".tv1dateconv($trow["mapvm"])."</td>";
						echo "<td>".tv1dateconv($trow["erpcm"])."</td>";
					}

					$summatotal += $trow['tiliointiavoinsaldo'];

					if ($valuutassako == 'V') {
						echo "<td align='right'>$trow[tiliointisumma_valuutassa]</td>";
						echo "<td align='right' class='avoin'>$trow[tiliointiavoinsaldo_valuutassa]</td>";
						echo "<td align='right'>$trow[valkoodi]</td>";
						$summa[$trow["valkoodi"]] += $trow["tiliointiavoinsaldo_valuutassa"];	// paljo kirjanpidossa avoimena valuutassa
					}
					else {
						echo "<td align='right'>$trow[tiliointisumma]</td>";
						echo "<td align='right' class='avoin'>$trow[tiliointiavoinsaldo]</td>";
						echo "<td align='right'>$yhtiorow[valkoodi]</td>";
						$summa[$yhtiorow["valkoodi"]] += $trow['tiliointiavoinsaldo'];	// paljo kirjanpidossa avoimena
					}

					// jos ollaan ajettu tälle päivälle laskuittain voidaan echota errori avoimesta saldosta
					if ($asitoimittain == "" and $onkotanaan == "" and abs($trow["tiliointiavoinsaldo"] - $trow["laskuavoinsaldo"]) >= 0.05) {
						echo "<td class='back' nowrap><font class='error'>VIRHE: Lasku / Kirjanpito heittää!</font> $trow[laskuavoinsaldo] / <a href='muutosite.php?tee=E&tunnus=$trow[tunnus]'>$trow[tiliointiavoinsaldo]</a></td>";
					}
					else {
						echo "<td class='back'></td>";
					}

					echo "</tr>";

					if (isset($workbook)) {
						$worksheet->writeString($excelrivi, 0, $trow["ytunnus"]);
						$worksheet->writeString($excelrivi, 1, $trow["nimi"]);
						$worksheet->write($excelrivi, 2, $trow["maksutieto"]);
						$worksheet->write($excelrivi, 3, $trow["tapvm"]);

						if ($trow["mapvm"] != '0000-00-00') $worksheet->write($excelrivi, 4, $trow["mapvm"]);
						else $worksheet->write($excelrivi, 4, "");

						$worksheet->write($excelrivi, 5, $trow["erpcm"]);

						if ($valuutassako == "V") {
							$worksheet->write($excelrivi, 6, $trow["tiliointisumma_valuutassa"]);
							$worksheet->write($excelrivi, 7, $trow["tiliointiavoinsaldo_valuutassa"]);
							$worksheet->write($excelrivi, 8, $trow["valkoodi"]);
						}
						else {
							$worksheet->write($excelrivi, 6, $trow["tiliointisumma"]);
							$worksheet->write($excelrivi, 7, $trow["tiliointiavoinsaldo"]);
							$worksheet->write($excelrivi, 8, $yhtiorow["valkoodi"]);
						}

						$excelrivi++;
					}
				}
			}

			//Listataan vielä kohdistamattomat
			$query = "	SELECT asiakas.tunnus, asiakas.ytunnus, nimi_maksaja, viite, kirjpvm,
						tiliointi.summa tiliointisumma,
						tiliointi.summa_valuutassa tiliointisumma_valuutassa,
						round(suoritus.summa * if(suoritus.kurssi = 0, 1, kurssi), 2) * -1 suoritussumma,
						tiliointi.ltunnus,
						suoritus.summa * -1 summa,
						suoritus.valkoodi
				  		FROM suoritus
						JOIN tiliointi ON (tiliointi.tunnus = suoritus.ltunnus and tiliointi.korjattu = '' and tiliointi.tilino	in ($tili))
				  		LEFT JOIN asiakas ON (suoritus.yhtio = asiakas.yhtio and suoritus.asiakas_tunnus = asiakas.tunnus $lisa3)
				  		WHERE suoritus.yhtio = '$kukarow[yhtio]'
						and kirjpvm <= '$pvm'
						and kohdpvm  = '0000-00-00'
						$lisa2
						$valkoodi2
				  		ORDER BY ytunnus";
			$result = mysql_query($query) or pupe_error($query);

			$suoritussumma = array();
			$suoritussummatotal = 0; // tässä koko höskä kotivaluutassa

			while ($trow = mysql_fetch_array ($result)) {
					echo "<tr class='aktiivi'>";

					$suoritussummatotal += $trow["suoritussumma"];

					if ($trow["tunnus"] != NULL) echo "<td><a name='a_$trow[tunnus]' href='".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php?ytunnus=$trow[ytunnus]&asiakasid=$trow[tunnus]&alatila=Y&tila=tee_raportti&lopetus=$PHP_SELF////toim=$toim//tee=$tee//pp=$pp//kk=$kk//vv=$vv//laji=$laji//ytunnus=$ytunnus//savalkoodi=$savalkoodi//valuutassako=$valuutassako//maa=$maa//maksuehto=$maksuehto//toimittajaid=$toimittajaid//asiakasid=$asiakasid///a_$trow[tunnus]'>$trow[ytunnus]</a></td>";
					else echo "<td>*".t("ei asiakasta")."*</td>";

					echo "<td>$trow[nimi_maksaja]</td>";
					echo "<td>$trow[viite]</td>";
					echo "<td>".tv1dateconv($trow["kirjpvm"])."</td>";
					echo "<td></td>";
					echo "<td></td>";

					$trow["summa"] = sprintf("%01.2f", $trow["summa"]);
					$trow["suoritussumma"] = sprintf("%01.2f", $trow["suoritussumma"]);

					if ($valuutassako == 'V') {
						echo "<td></td>";
						echo "<td align='right'>$trow[summa]</td>";
						echo "<td align='right'>$trow[valkoodi]</td>";

						$suoritussumma[$trow["valkoodi"]] += $trow["summa"];
					}
					else {
						echo "<td></td>";
						echo "<td align='right'>$trow[suoritussumma]</td>";
						echo "<td align='right'>$yhtiorow[valkoodi]</td>";

						$suoritussumma[$yhtiorow["valkoodi"]] += $trow["suoritussumma"];
					}

					echo "<td class='back' nowrap>";

					if ($trow["suoritussumma"] != $trow["tiliointisumma"] and $trow["summa"] != $trow["tiliointisumma_valuutassa"] and $trow["tiliointisumma"] != NULL and $trow["tiliointisumma_valuutassa"] != NULL) {
						echo "<font class='error'>VIRHE: Kirjanpito / Suoritus summat heittävät!</font> (<a href='muutosite.php?tee=E&tunnus=$trow[ltunnus]'>$trow[tiliointisumma] / $trow[suoritussumma]</a>)";
					}

					if ($trow["tiliointisumma"] == NULL) {
						echo "<br><font class='error'>VIRHE: Suoritukselle ei löydy tiliöintejä!</font>";
					}

					echo "</td>";
					echo "</tr>";

					if (isset($workbook)) {
						if ($trow["tunnus"] != NULL) $worksheet->write($excelrivi, 0, $trow["ytunnus"]);
						else $worksheet->write($excelrivi, 0, "*".t("ei asiakasta")."*");
						$worksheet->write($excelrivi, 1, $trow["nimi_maksaja"]);
						$worksheet->write($excelrivi, 2, $trow["viite"]);
						$worksheet->write($excelrivi, 3, $trow["kirjpvm"]);
						$worksheet->write($excelrivi, 4, "");
						$worksheet->write($excelrivi, 5, "");

						if ($valuutassako == "V") {
							$worksheet->write($excelrivi, 6, "");
							$worksheet->write($excelrivi, 7, $trow["summa"]);
							$worksheet->write($excelrivi, 8, $trow["valkoodi"]);
						}
						else {
							$worksheet->write($excelrivi, 6, "");
							$worksheet->write($excelrivi, 7, $trow["suoritussumma"]);
							$worksheet->write($excelrivi, 8, $yhtiorow["valkoodi"]);
						}

						$excelrivi++;
					}
			}

			$kaikkival = array();

			echo "</tbody>";
			echo "<tfoot>";

	        foreach ($summa as $val => $sum) {
				echo "<tr>";
				echo "<th colspan='7'>".t("Avoimet yhteensä").":</th>";
				echo "<td class='tumma' align='right' nowrap name='avoimet_yhteensa' id='avoimet_yhteensa_$val'>".sprintf("%.2f", $sum)."</td>";				// tässä kirjanpidosta osasuoritukset poistettuna
				echo "<td class='tumma' align='right' nowrap>$val</td>";
				echo "</tr>";

				if(!in_array($val, $kaikkival)) $kaikkival[] = $val;
			}

			foreach ($suoritussumma as $val => $sum) {
	        	echo "<tr>";
				echo "<th colspan='7'>".t("Kohdistamattomat suoritukset yhteensä").":</th>";
				echo "<td class='tumma' align='right' nowrap name='avoimet_yhteensa' id='avoimet_suoriyhteensa_$val'>".sprintf("%.2f", $sum)."</td>";
				echo "<td class='tumma' align='right' nowrap>$val</td>";
				echo "</tr>";

				if(!in_array($val, $kaikkival)) $kaikkival[] = $val;
			}

			foreach ($kaikkival as $val) {
	        	echo "<tr>";
				echo "<th colspan='7'>".t("Erotus avoimet - kohdistamattomat").":</th>";
				echo "<td class='tumma' align='right' nowrap name='avoimet_yhteensa' id='avoimet_yseroyhteensa_$val'>".sprintf("%.2f", ($summa[$val] + $suoritussumma[$val]))."</td>"; // tässä kirjanpidosta osasuoritukset poistettuna
				echo "<td class='tumma' align='right' nowrap>$val</td>";
				echo "</tr>";
			}

			// ei rajauksia, voidaan näyttää erotus kirjanpitoon
			if ($lisa1 == "" and $lisa3 == "" and $valkoodi == "" and $maa == "") {

		        $query = "	SELECT sum(summa) saldo
							FROM tiliointi
							WHERE yhtio = '$kukarow[yhtio]'
							and korjattu = ''
							and tapvm >= '$tilikausirow[tilikausi_alku]'
							and tapvm <= '$pvm'
							and tilino in ($tili)";
				$result = mysql_query($query) or pupe_error($query);
				$trow = mysql_fetch_array ($result);

				$trow["saldo"] 	= sprintf("%.2f", $trow["saldo"]);
				$erotus 		= $summatotal + $suoritussummatotal - $trow["saldo"];
				$erotus 		= sprintf("%.2f", $erotus);

				echo "<tr><td class='back' colspan='9'><br></td></tr>";

		        echo "<tr>";
				echo "<th colspan='7'>".t("Erotus avoimet - kohdistamattomat", "", $tili).":</th>";
				echo "<td class='tumma' align='right' nowrap>".sprintf("%.2f",$summatotal + $suoritussummatotal)."</td>";
				echo "<td class='tumma' align='right' nowrap>$yhtiorow[valkoodi]</td>";
				echo "</tr>";

		        echo "<tr>";
				echo "<th colspan='7'>".t("Kirjanpidon tili %s yhteensä", "", $tili).":</th>";
				echo "<td class='tumma' align='right' nowrap>".sprintf("%.2f",$trow["saldo"])."</td>";
				echo "<td class='tumma' align='right' nowrap>$yhtiorow[valkoodi]</td>";
				echo "</tr>";

		        echo "<tr>";
				echo "<th colspan='7'>".t("Erotus avoimet - kohdistamattomat - kirjanpito").":</th>";
				echo "<td class='tumma' align='right' nowrap>".sprintf("%.2f",$erotus)."</td>";
				echo "<td class='tumma' align='right' nowrap>$yhtiorow[valkoodi]</td>";
				echo "</tr>";
			}

			echo "</tfoot>";
		}

		echo "</table>";
	}
?>