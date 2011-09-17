<?php

	// DataTables päälle
	$pupe_DataTables = "tyojono";

	require('../inc/parametrit.inc');

	if ($toim == "OMAJONO") {
		echo "<font class='head'>".t("Omat työmääräykset").":</font><hr><br>";
	}
 	else {
		echo "<font class='head'>".t("Työjono").":</font><hr><br>";
	}

	if (!isset($AIKA_ARRAY)) $AIKA_ARRAY = array("08:00","09:00","10:00","11:00","12:00","13:00","14:00","15:00","16:00");

	//kuukaudet ja päivät ja ajat
	if (!isset($MONTH_ARRAY)) $MONTH_ARRAY = array(1=>'Tammikuu','Helmikuu','Maaliskuu','Huhtikuu','Toukokuu','Kesäkuu','Heinäkuu','Elokuu','Syyskuu','Lokakuu','Marraskuu','Joulukuu');
	if (!isset($DAY_ARRAY)) $DAY_ARRAY = array("Maanantai", "Tiistai", "Keskiviikko", "Torstai", "Perjantai");

	if (!function_exists("tunnit_minuutit")) {
		function tunnit_minuutit ($sekunnit) {
			$tunn = sprintf("%02d", $sekunnit / 3600);
			$mins = sprintf("%02d", $sekunnit / 60 % 60);

			return $tunn.":".$mins;
		}
	}

	if ($tyostatus_muutos != '' and $tyomaarayksen_tunnus != '') {
		$tyostatus_muutos = mysql_real_escape_string($tyostatus_muutos);
		$tyomaarayksen_tunnus = (int) $tyomaarayksen_tunnus;

		$query = "	UPDATE tyomaarays SET
					tyostatus = '$tyostatus_muutos'
					WHERE yhtio = '$kukarow[yhtio]'
					AND otunnus = '$tyomaarayksen_tunnus'";
		$update_tyom_res = pupe_query($query);
	}

	$chk = "";
	if (trim($konserni) != '') {
		$chk = "CHECKED";
	}

	if ($yhtiorow["konserni"] != "") {
		echo "<form method='post'>";
		echo t("Näytä konsernin työmääräykset").":<input type='checkbox' name='konserni' $chk onclick='submit();'><br><br>";
		echo "</form>";
	}

	pupe_DataTables($pupe_DataTables, 7, 8, '', true, true);

	echo "<table class='display' id='$pupe_DataTables'>";
	echo "<thead>";
	echo "<tr>";

	if (trim($konserni) != '') {
		echo "<th>".t("Yhtiö")."</th>";
	}

	echo "	<th width='1'>".t("Työmääräys")."<br>".t("Tilausviite")."</th>
			<th width='1'>".t("Ytunnus")."<br>".t("Asiakas")."</th>
			<th width='1'>".t("Työaika")."<br>".t("Työn suorittaja")."</th>
			<th width='1'>".t("Toimitetaan")."</th>
			<th width='1'>".t("Myyjä")."<br>".t("Tyyppi")."</th>
			<th width='1'>".t("Työjono")."/<br>".t("Työstatus")."</th>
			<th width='1'>".t("Vaihda")."<br>".t("Työstatusta")."</th>
			<th width='1'>".t("Muokkaa")."</th>
			</tr>";

	echo "<tr>";

	if (trim($konserni) != '') {
		echo "<td></td>";
	}

	echo "<td valign='top'><input type='text' size='10' name='search_myyntitilaus_haku'></td>";
	echo "<td valign='top'><input type='text' size='10' name='search_asiakasnimi_haku'></td>";
	echo "<td valign='top'><input type='text' size='10' name='search_suorittaja_haku'></td>";
	echo "<td valign='top'><input type='hidden' size='10' name='search_toimitetaan_haku'></td>"; 	// toistaiseksi, pitää hiddenöidä että saa haun toimimaan.
	echo "<td valign='top'><input type='hidden' size='10' name='search_myyja_haku'></td>";			// toistaiseksi, pitää hiddenöidä että saa haun toimimaan.
	echo "<td valign='top'><input type='text' size='10' name='search_tyojono_haku'></td>";
	echo "<td valign='top'><input type='hidden' size='10' name='search_tyostatus_haku'></td>";
	echo "<td valign='top'></td>";
	echo "</tr>";
	echo "</thead>";

	$lisa   = "";
	$lisa2  = "";
	$omattyot = "";

	if ($myyntitilaus_haku != "") {
		$lisa .= " and lasku.tunnus='$myyntitilaus_haku' ";
	}

	if ($viesti_haku != "") {
		$lisa .= " and lasku.viesti like '%$viesti_haku%' ";
	}

	if ($asiakasnimi_haku != "") {
		$lisa .= " and lasku.nimi like '%$asiakasnimi_haku%' ";
	}

	if ($asiakasnumero_haku != "") {
		$lisa .= " and lasku.ytunnus like '$asiakasnumero_haku%' ";
	}

	if ($tyojono_haku != "") {
		$lisa .= " and a1.selitetark like '$tyojono_haku%' ";
	}

	if ($tyostatus_haku != "") {
		$lisa .= " and a2.selitetark like '$tyostatus_haku%' ";
	}

	if ($suorittaja_haku != "") {
		$lisa2 .= " HAVING suorittajanimi like '%$suorittaja_haku%' or asekalsuorittajanimi like '%$suorittaja_haku%' ";
	}

	if (trim($konserni) != '') {
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
		$result = pupe_query($query);
		$konsernit = "";

		while ($row = mysql_fetch_array($result)) {
			$konsernit .= " '".$row["yhtio"]."' ,";
		}
		$konsernit = " lasku.yhtio in (".substr($konsernit, 0, -1).") ";
	}
	else {
		$konsernit = " lasku.yhtio = '$kukarow[yhtio]' ";
	}

	if ($toim == "OMAJONO") {
		$omattyot = " HAVING suorittajanimi = '$kukarow[nimi]' or asekalsuorittajanimi = '$kukarow[nimi]' ";
	}

	if ($linkkihaku != "") {
		$linkkihaku = mysql_real_escape_string(trim($_GET['linkkihaku']));
		$linkkihaku = urldecode($linkkihaku);
		$omattyot = " HAVING suorittajanimi = '$linkkihaku' or asekalsuorittajanimi = '$linkkihaku' ";
	}
	// scripti balloonien tekemiseen
	js_popup();

	$query = "	SELECT
				lasku.tunnus,
				lasku.viesti,
				lasku.nimi,
				lasku.tila,
				lasku.alatila,
				lasku.tilaustyyppi,
				lasku.ytunnus,
				lasku.toimaika,
				tyomaarays.komm1,
				tyomaarays.komm2,
				tyomaarays.tyojono,
				tyomaarays.tyostatus,
				kuka.nimi myyja,
				a1.selite tyojonokoodi,
				a1.selitetark tyojono,
				a2.selitetark tyostatus,
				a2.selitetark_2 tyostatusvari,
				yhtio.nimi yhtio,
				yhtio.yhtio yhtioyhtio,
				a3.nimi suorittajanimi,
				a2.jarjestys prioriteetti,
				lasku.luontiaika,
				group_concat(a4.selitetark_2) asekalsuorittajanimi,
				group_concat(concat(left(kalenteri.pvmalku,16), '##', left(kalenteri.pvmloppu,16), '##', a4.selitetark_2, '##', kalenteri.tunnus, '##', a4.selitetark, '##', timestampdiff(SECOND, kalenteri.pvmalku, kalenteri.pvmloppu))) asennuskalenteri
				FROM lasku
				JOIN yhtio ON lasku.yhtio=yhtio.yhtio
				JOIN tyomaarays ON (tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus )
				LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
				LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja
				LEFT JOIN avainsana a1 ON a1.yhtio=tyomaarays.yhtio and a1.laji='TYOM_TYOJONO'   and a1.selite=tyomaarays.tyojono
				LEFT JOIN avainsana a2 ON a2.yhtio=tyomaarays.yhtio and a2.laji='TYOM_TYOSTATUS' and a2.selite=tyomaarays.tyostatus
				LEFT JOIN kuka a3 ON a3.yhtio=tyomaarays.yhtio and a3.kuka=tyomaarays.suorittaja
				LEFT JOIN kalenteri ON kalenteri.yhtio = lasku.yhtio and kalenteri.tyyppi = 'asennuskalenteri' and kalenteri.liitostunnus = lasku.tunnus
				LEFT JOIN avainsana a4 ON a4.yhtio=kalenteri.yhtio and a4.laji='TYOM_TYOLINJA'  and a4.selitetark=kalenteri.kuka
				WHERE $konsernit
				and lasku.tila in ('A','L','N','S','C')
				and lasku.alatila != 'X'
				$lisa
				GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22
				$omattyot
				$lisa2
				ORDER BY prioriteetti, luontiaika ASC";
	$vresult = pupe_query($query);

	$tyomaarays_tunti_yhteensa = array();
	$tyomaarays_kuitti_yhteensa = array();

	echo "<tbody>";

	while ($vrow = mysql_fetch_array($vresult)) {

		$laskutyyppi = $vrow["tila"];
		$alatila	 = $vrow["alatila"];

		//tehdään selväkielinen tila/alatila
		require "inc/laskutyyppi.inc";

		if ($vrow['tilaustyyppi'] != '' and $vrow['tila'] != 'C') {
			$laskutyyppi = $vrow['tilaustyyppi'];

			require "inc/laskutyyppi.inc";
		}

		echo "<tr>";

		if (trim($konserni) != '') {
			echo "<td>$vrow[yhtio]</td>";
		}

		if ($vrow["tila"] == "L" or $vrow["tila"] == "N") {
			$toimi = "RIVISYOTTO";
		}
		elseif ($vrow["tila"] == "T") {
			$toimi = "TARJOUS";
		}
		elseif ($vrow["tila"] == "S") {
			$toimi = "SIIRTOTYOMAARAYS";
		}
		elseif ($vrow["tila"] == "A") {
			$toimi = "TYOMAARAYS";
		}

		if (trim($vrow["komm1"]) != "") {
			echo "<div id='div_$vrow[tunnus]' class='popup' style='width:500px;'>";
			echo t("Työmääräys"),": $vrow[tunnus]<br><br>".str_replace("\n", "<br>", $vrow["komm1"]."<br>".$vrow["komm2"]);
			echo "</div>";
			echo "<td valign='top' class='tooltip' id='$vrow[tunnus]'>$vrow[tunnus]</td>";
		}
		else {
			echo "<td valign='top'>$vrow[tunnus]<br>$vrow[viesti]</td>";
		}

		echo "<td valign='top'>$vrow[ytunnus]<br>$vrow[nimi]</td>";


		echo "<td valign='top'>";

		$olenko_asentaja_tassa_hommassa = FALSE;

		if ($vrow["asennuskalenteri"] != "") {

			$lopetusx = "";

			if ($lopetus != "") $lopetusx = $lopetus;

			$lopetusx .= "/SPLIT/$PHP_SELF////konserni=$konserni//toim=$toim//myyntitilaus_haku=$myyntitilaus_haku//viesti_haku=$viesti_haku//asiakasnumero_haku=$asiakasnumero_haku//asiakasnimi_haku=$asiakasnimi_haku//suorittaja_haku=$suorittaja_haku//tyojono_haku=$tyojono_haku//tyostatus_haku=$tyostatus_haku";

			// vrow.asennuskalenteri = kaikki työnjohdon tekemät merkinnät tälle työmääräykselle
			foreach (explode(",", $vrow["asennuskalenteri"]) as $asekale) {

				list($pvmalku, $pvmloppu, $selitetark_2, $tunnus, $selitetark, $asennussekunnit) = explode("##", $asekale);
				list($alku_pvm, $alku_klo) = explode(" ", $pvmalku);
				list($loppu_pvm, $loppu_klo) = explode(" ", $pvmloppu);

				// jos ollaan asentaja
				if ($toim == 'TYOMAARAYS_ASENTAJA') {
					if ($kukarow['kuka'] == $selitetark) {
						$olenko_asentaja_tassa_hommassa = TRUE;
						list($url_vuosi, $url_kk, $url_pp) = explode("-", $alku_pvm);
						echo "<a href='".$palvelin2."crm/kalenteri.php?tyomaarays=$vrow[tunnus]&year=$url_vuosi&kuu=$url_kk&paiva=$url_pp&toim=$toim&lopetus=$lopetusx'>".tv1dateconv($alku_pvm, "", "LYHYT")." $selitetark_2</a><br>";
					}
					else {
						echo tv1dateconv($pvmloppu, "", "LYHYT")." $selitetark_2<br>";
					}
				}

				// jos ollaan työnjohto
				if ($toim != 'TYOMAARAYS_ASENTAJA') {
					$tyomaarays_tunti_yhteensa[$vrow['tunnus']][$selitetark_2] += $asennussekunnit;

					echo "<a href='asennuskalenteri.php?liitostunnus=$vrow[tunnus]&tyojono=$vrow[tyojonokoodi]&tyotunnus=$tunnus&tee=MUOKKAA&lopetus=$lopetusx'>";

					if ($alku_pvm == $loppu_pvm) {
						echo tv1dateconv($alku_pvm, '', 'LYHYT')." $alku_klo - $loppu_klo";
					}
					else {
						echo tv1dateconv($alku_pvm, '', 'LYHYT')." $alku_klo - ".tv1dateconv($loppu_pvm, '', 'LYHYT')." $loppu_klo";
					}

					echo " $selitetark_2</a><br>";
				}
			}

			// lasketaan katotaan mitä asentajat on syöttänyt tunteja
			$query = "	SELECT kalenteri.kuka, a4.selitetark_2 nimi, left(kalenteri.pvmalku, 10) pvmalku, left(kalenteri.pvmloppu, 10) pvmloppu,
						sum(timestampdiff(SECOND, kalenteri.pvmalku, kalenteri.pvmloppu)) sekunnit,
						SEC_TO_TIME(sum(timestampdiff(SECOND, kalenteri.pvmalku, kalenteri.pvmloppu))) aika
						FROM kalenteri
						LEFT JOIN avainsana a4 ON a4.yhtio=kalenteri.yhtio and a4.laji='TYOM_TYOLINJA' and a4.selitetark=kalenteri.kuka
						WHERE kalenteri.yhtio = '$kukarow[yhtio]'
						AND kalenteri.tyyppi = 'kalenteri'
						AND kalenteri.kentta02 = '$vrow[tunnus]'
						GROUP BY 1,2,3,4
						ORDER BY 1,2,3";
			$tunti_chk_res = pupe_query($query);

			while ($tunti_chk_row = mysql_fetch_assoc($tunti_chk_res)) {
				$tyomaarays_kuitti_yhteensa[$vrow['tunnus']][$tunti_chk_row["nimi"]] += $tunti_chk_row['sekunnit'];

				// jos me itse ollaan ko. asentaja
				if ($toim == 'TYOMAARAYS_ASENTAJA' and $kukarow['kuka'] == $tunti_chk_row["kuka"]) {
					list($url_vuosi, $url_kk, $url_pp) = explode("-", $tunti_chk_row["pvmalku"]);
					list($url_tunti, $url_min, $url_sek) = explode(":", $tunti_chk_row["aika"]);
					echo "<a href='".$palvelin2."crm/kalenteri.php?tyomaarays=$vrow[tunnus]&year=$url_vuosi&kuu=$url_kk&paiva=$url_pp&toim=$toim&lopetus=$lopetusx'>".tv1dateconv($tunti_chk_row["pvmalku"], "", "LYHYT")." $tunti_chk_row[nimi]: ".(int) $url_tunti."h ".$url_min."m</a><br>";
				}

			}

		}

		echo $vrow["suorittajanimi"];

		echo "</td>";

		if ($vrow["tyojono"] != "" and $toim != 'TYOMAARAYS_ASENTAJA') {
			list($ankkuri_pp, $ankkuri_kk, $ankkuri_vv) = explode(".", tv1dateconv($vrow["toimaika"]));
			$ankkuri_pp = (strlen($ankkuri_pp) == 2 and $ankkuri_pp{0} == 0) ? $ankkuri_pp{1} : $ankkuri_pp;
			$ankkuri_kk = (strlen($ankkuri_kk) == 2 and $ankkuri_kk{0} == 0) ? $ankkuri_kk{1} : $ankkuri_kk;

			$ankkuri = "{$ankkuri_pp}_{$ankkuri_kk}_{$ankkuri_vv}";

			if ($toim == "OMAJONO") {
				$toim = "";
				$omatoimi = "OMAJONO";
			}

			echo "<td valign='top'><a href='asennuskalenteri.php?liitostunnus=$vrow[tunnus]&tyojono=$vrow[tyojonokoodi]&toim=$toim&lopetus=$lopetus/SPLIT/$PHP_SELF////konserni=$konserni//toim=$toim#$ankkuri'>{$vrow["toimaika"]}</a></td>";
		}
		else {
			echo "<td valign='top'>{$vrow["toimaika"]}</td>";
		}

		echo "<td valign='top'>$vrow[myyja]<br>".t("$laskutyyppi")." ".t("$alatila")."</td>";

		if ($vrow["tyostatusvari"] != "") {
			$varilisa = "style='background-color: $vrow[tyostatusvari];'";
		}
		else {
			$varilisa = "";
		}

		echo "<td valign='top' $varilisa>$vrow[tyojono] / <br>$vrow[tyostatus]</td>";

		if ($toim == 'TYOMAARAYS_ASENTAJA' and $toimi != 'TYOMAARAYS_ASENTAJA') $toimi = 'TYOMAARAYS_ASENTAJA';

		if ($vrow['tila'] == 'C') {
			$toimi = 'REKLAMAATIO';
		}
		elseif ($vrow['tila'] == 'L' and $vrow['tilaustyyppi'] == 'A') {
			$toimi = $toimi != 'TYOMAARAYS_ASENTAJA' ? 'TYOMAARAYS' : 'TYOMAARAYS_ASENTAJA';
		}

		if ($toim != 'TYOMAARAYS_ASENTAJA' or $olenko_asentaja_tassa_hommassa) {
			if ($vrow["yhtioyhtio"] != $kukarow["yhtio"]) {
				$muoklinkki = "<a href='../tilauskasittely/tilaus_myynti.php?user=$kukarow[kuka]&pass=$kukarow[salasana]&yhtio=$vrow[yhtioyhtio]&toim=$toimi&tee=AKTIVOI&from=LASKUTATILAUS&tilausnumero=$vrow[tunnus]&lopetus={$palvelin2}tyomaarays/tyojono.php////toim=$omatoimi'>".t("Muokkaa")."</a>";
			}
			else {
				$muoklinkki = "<a href='../tilauskasittely/tilaus_myynti.php?toim=$toimi&tee=AKTIVOI&from=LASKUTATILAUS&tilausnumero=$vrow[tunnus]&tyojono=$tyojono&lopetus={$palvelin2}tyomaarays/tyojono.php////toim=$omatoimi'>".t("Muokkaa")."</a>";
			}
		}
		else {
			$muoklinkki = "";
		}
//		echo "<td valign='top' valign='top' $varilisa>$vrow[tyostatus]</td>";
		echo "<td>";
		if ($toim != 'TYOMAARAYS_ASENTAJA') {
			$tyostatus_result = t_avainsana("TYOM_TYOSTATUS");

			if (mysql_num_rows($tyostatus_result) > 0) {
				echo "<form method='post' id='tmform' name='tmform'>";
				echo "<input type='hidden' name='tyomaarayksen_tunnus' value='$vrow[tunnus]'>";
				echo "<input type='hidden' name='konserni' value='$konserni'>";
				echo "<input type='hidden' name='myyntitilaus_haku' value='$myyntitilaus_haku'>";
				echo "<input type='hidden' name='viesti_haku' value='$viesti_haku'>";
				echo "<input type='hidden' name='asiakasnimi_haku' value='$asiakasnimi_haku'>";
				echo "<input type='hidden' name='asiakasnumero_haku' value='$asiakasnumero_haku'>";
				echo "<input type='hidden' name='tyojono_haku' value='$tyojono_haku'>";
				echo "<input type='hidden' name='tyostatus_haku' value='$tyostatus_haku'>";
				echo "<input type='hidden' name='suorittaja_haku' value='$suorittaja_haku'>";
				echo "<input type='hidden' name='tyojono' value='$tyojono'>";

				echo "<select name='tyostatus_muutos' onchange='submit();'>";

				while ($tyostatus_row = mysql_fetch_assoc($tyostatus_result)) {
					$sel = $vrow['tyostatus'] == $tyostatus_row['selitetark'] ? ' SELECTED' : '';
					echo "<option value='$tyostatus_row[selite]'$sel>$tyostatus_row[selitetark]</option>";
				}

				echo "</select></form>";
			}
		}
		else {
			echo "$vrow[tyostatus]";
		}
		// Laitetaan hiddeninä työstatus sorttauksen takia.
		echo "<p style='visibility:hidden'>$vrow[tyostatus]</p>";

		echo "</td>";

		echo "<td valign='top'>$muoklinkki</td>";
		echo "</tr>";
	}

	echo "</tbody></table>";
	echo "<br><br>";
	echo "<table><tr>";

	if (count($tyomaarays_tunti_yhteensa) > 0) {
		if ($toim != 'TYOMAARAYS_ASENTAJA') {
			echo "<td class='back'><table><tr><th colspan='2'>",t("Työmääräyksien tuntiyhteenveto"),"</th><th>",t("Työmääräyksien asentajien tuntiyhteenveto"),"</th></tr>";

			$total_yht = 0;

			foreach ($tyomaarays_tunti_yhteensa as $tyom_id => $tyom_array) {
				$yht = 0;
				$yhtk = 0;

				echo "<tr><td>$tyom_id</td><td><table width='100%'>";

				ksort($tyom_array);

				foreach ($tyom_array as $tyom_asentaja => $tyom_sekunnit) {
					echo "<tr><td>$tyom_asentaja</td><td class='ok' align='right'><strong>".tunnit_minuutit($tyom_sekunnit)."</strong></td></tr>";
					$yht += $tyom_sekunnit;
				}

				echo "</table></td>";
				echo "<td><table width='100%'>";

				ksort($tyomaarays_kuitti_yhteensa[$tyom_id]);

				foreach ($tyomaarays_kuitti_yhteensa[$tyom_id] as $tyom_asentaja => $tyom_sekunnit) {
					echo "<tr><td>$tyom_asentaja</td><td class='ok' align='right'><strong>".tunnit_minuutit($tyom_sekunnit)."</strong></td></tr>";
					$yhtk += $tyom_sekunnit;
				}

				echo "</table></td>";
				echo "</tr>";
				echo "<tr><td class='tumma'>",t("Yhteensä")," ",t("Työmääräys")," $tyom_id</td><td class='tumma' align='right'>".tunnit_minuutit($yht)."</td><td class='tumma' align='right'>".tunnit_minuutit($yhtk)."</td></tr>";

				$total_yht += $yht;
				$total_yhtk += $yhtk;
			}

			echo "<tr><th>",t("Yhteensä"),"</th><td class='tumma' align='right'>".tunnit_minuutit($total_yht)."</td><td class='tumma' align='right'>".tunnit_minuutit($total_yhtk)."</td></tr>";
			echo "</table></td>";
		}
	}
	elseif (count($tyomaarays_kuitti_yhteensa) > 0) {
		echo "<td class='back'><table>";
		echo "<tr><th colspan='2'>",t("Työmääräyksien asentajien tuntiyhteenveto"),"</th></tr>";

		$total_yht = 0;

		foreach ($tyomaarays_kuitti_yhteensa as $tyom_id => $tyom_array) {
			$yht = 0;

			echo "<tr><td>$tyom_id</td><td><table width='100%'>";

			foreach ($tyom_array as $tyom_asentaja => $tyom_sekunnit) {
				echo "<tr><td>$tyom_asentaja</td><td class='ok' align='right'><strong>".tunnit_minuutit($tyom_sekunnit)."</strong></td></tr>";
				$yht += $tyom_sekunnit;
			}

			echo "</td></table></td></tr>";
			echo "<tr><td class='tumma'>",t("Yhteensä")," ",t("Työmääräys")," $tyom_id</td><td class='tumma' align='right'>".tunnit_minuutit($yht)."&nbsp;</td></tr>";

			$total_yht += $yht;
		}

		echo "<tr><th>",t("Yhteensä"),"</th><td class='tumma' align='right'>".tunnit_minuutit($total_yht)."&nbsp;</th></tr>";
		echo "</table></td>";
	}
	echo "</tr></table>";

	echo "<br><br>";

	require ("inc/footer.inc");

?>