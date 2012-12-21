<?php

	// Parametrit
	require('../inc/parametrit.inc');

	if ((int) $liitostunnus > 0) {
		$query 	= "	SELECT *
					FROM lasku
					WHERE tunnus = '$liitostunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);
	}

	// scripti balloonien tekemiseen
	js_popup();

	echo "<font class='head'>".t("Asennuskalenteri").":</font><hr><br>";

	// Voi tulla myös salasanat.php:stä
	if (!isset($MONTH_ARRAY)) $MONTH_ARRAY = array(1=> t('Tammikuu'),t('Helmikuu'),t('Maaliskuu'),t('Huhtikuu'),t('Toukokuu'),t('Kesäkuu'),t('Heinäkuu'),t('Elokuu'),t('Syyskuu'),t('Lokakuu'),t('Marraskuu'),t('Joulukuu'));
	if (!isset($DAY_ARRAY)) $DAY_ARRAY = array(t("Maanantai"), t("Tiistai"), t("Keskiviikko"), t("Torstai"), t("Perjantai"), t("Lauantai"), t("Sunnuntai"));
	if (!isset($AIKA_ARRAY)) $AIKA_ARRAY = array("08:00","09:00","10:00","11:00","12:00","13:00","14:00","15:00","16:00");

	$lis = "";

	if (!isset($tyojono) and $toim == 'TYOMAARAYS_ASENTAJA') {
		$lis .= " and avainsana.selitetark = '$kukarow[kuka]' ";
	}

	if (!isset($tyojono)) {
		// Onko käyttäjällä oletustyöjono
		$query = "	SELECT selite
					FROM avainsana
					WHERE yhtio 	= '$kukarow[yhtio]'
					and laji		= 'TYOM_TYOLINJA'
					and selitetark	= '$kukarow[kuka]'
					and selite     != ''";
		$yres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($yres) > 0) {
			$yrow = mysql_fetch_array($yres);

			$tyojono = $yrow["selite"];
		}
	}

	if (isset($tyojono) and $tyojono != '') {
		$lis .= " and (avainsana.selite = '' or avainsana.selite = '$tyojono') ";
	}

	$kires = t_avainsana("TYOM_TYOLINJA", "", $lis." ORDER BY jarjestys, selitetark_2");

	$ASENTAJA_ARRAY = array();
	$ASENTAJA_ARRAY_TARK = array();

	while ($kirow = mysql_fetch_array($kires)) {
		$ASENTAJA_ARRAY[] = $kirow["selitetark"];
		$ASENTAJA_ARRAY_TARK[] = $kirow["selitetark_2"];
	}

	// otetaan oletukseksi tämä kuukausi ja tämä vuosi
	if ($month == '') $month = date("n");
	else $month = sprintf("%02d", $month);

	if ($year == '') $year = date("Y");
	else $year = sprintf("%04d", $year);

	if ($day == '') $day = date("j");
	else $day = sprintf("%02d", $day);


	if ($lmonth != '') $lmonth = sprintf("%02d", $lmonth);
	if ($lyear != '') $lyear = sprintf("%04d", $lyear);
	if ($lday != '') $lday = sprintf("%02d", $lday);

	//lasketaan edellinen ja seuraava paiva kun siirytään yksi päivä
	$backmday = date("n",mktime(0, 0, 0, $month, $day-1,  $year));
	$backyday = date("Y",mktime(0, 0, 0, $month, $day-1,  $year));
	$backdday = date("j",mktime(0, 0, 0, $month, $day-1,  $year));

	$nextmday = date("n",mktime(0, 0, 0, $month, $day+1,  $year));
	$nextyday = date("Y",mktime(0, 0, 0, $month, $day+1,  $year));
	$nextdday = date("j",mktime(0, 0, 0, $month, $day+1,  $year));

	//lasketaan edellinen ja seuraava paiva kun siirytään yksi kuukausi
	$backmmonth = date("n",mktime(0, 0, 0, $month-1, $day,  $year));
	$backymonth = date("Y",mktime(0, 0, 0, $month-1, $day,  $year));
	$backdmonth = date("j",mktime(0, 0, 0, $month-1, $day,  $year));

	$nextmmonth = date("n",mktime(0, 0, 0, $month+1, $day,  $year));
	$nextymonth = date("Y",mktime(0, 0, 0, $month+1, $day,  $year));
	$nextdmonth = date("j",mktime(0, 0, 0, $month+1, $day,  $year));

	//kalenteritoiminnot
	function days_in_month($month, $year) {
		// calculate number of days in a month
		return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
	}

	function weekday_name($day, $month, $year) {
		// calculate weekday name
		$days = array(t("Maanantai"), t("Tiistai"), t("Keskiviikko"), t("Torstai"), t("Perjantai"), t("Lauantai"), t("Sunnuntai"));
		$nro = date("w", mktime(0, 0, 0, $month, $day, $year));
		if ($nro==0) $nro=6;
		else $nro--;

		return $days[$nro];
	}

	function weekday_number($day, $month, $year) {
		// calculate weekday number
		$nro = date("w", mktime(0, 0, 0, $month, $day, $year));
		if ($nro==0) $nro=6;
		else $nro--;

		return $nro;
	}

	function month_name($month) {
		// display long month name
		$kk = $MONTH_ARRAY;
		return $kk[$month-1];
	}

	if (trim($konserni) != '') {
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
		$result = mysql_query($query) or pupe_error($query);
		$konsernit = "";

		while ($row = mysql_fetch_array($result)) {
			$konsernit .= " '".$row["yhtio"]."' ,";
		}
		$konsernit = "yhtio in (".substr($konsernit, 0, -1).") ";
	}
	else {
		$konsernit = "yhtio = '$kukarow[yhtio]' ";
	}

	if ($tee == "LISAA") {

		$alku  = str_replace(array(":","-"," "), "", "$year-$month-$day $aika");
		$loppu = str_replace(array(":","-"," "), "", "$lyear-$lmonth-$lday $laika");

		//tarkistetaan, etta alku ja loppu ovat eri..
		if ($alku == $loppu) {
			echo "<font class='error'>".t("VIRHE: Alku- ja päättymisajankohta ovat samat")."!</font><br><br>";
			$tee = "VARAA";
		}
		if ($alku > $loppu) {
			echo "<font class='error'>".t("VIRHE: Päättymisjankohta on aikaisempi kuin alkamisajankohta")."!</font><br><br>";
			$tee = "VARAA";
		}

		$query = "	SELECT tunnus
					FROM kalenteri
					WHERE
					$konsernit
					and tyyppi 	= 'asennuskalenteri'
					and kuka 	= '$asentaja'
					and (	(pvmalku >= '$year-$month-$day $aika:00' and pvmalku  < '$lyear-$lmonth-$lday $laika:00') or
							(pvmalku  < '$year-$month-$day $aika:00' and pvmloppu > '$lyear-$lmonth-$lday $laika:00') or
							(pvmloppu > '$year-$month-$day $aika:00' and pvmloppu<= '$lyear-$lmonth-$lday $laika:00'))
					and tunnus != '$tyotunnus'
					order by pvmalku";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			echo "<font class='error'>".t("VIRHE: Päällekkäisiä tapahtumia")."!</font><br><br>";
			$tee = "VARAA";
		}

		if ($tee == "LISAA" and $tyotunnus > 0) {
			$query = "	UPDATE kalenteri
						SET
						muuttaja	= '$kukarow[kuka]',
						muutospvm	= now(),
						tapa		= '$tyojono',
						kuka 		= '$asentaja',
						pvmalku 	= '$year-$month-$day $aika',
						pvmloppu 	= '$lyear-$lmonth-$lday $laika',
						liitostunnus= '$liitostunnus',
						tyyppi 		= 'asennuskalenteri'
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi  = 'asennuskalenteri'
						and tunnus  = '$tyotunnus'";
			mysql_query($query) or pupe_error($query);


			if ($yhtiorow['tyomaarays_asennuskalenteri_muistutus'] == 'K' and isset($lisaa_muistutus) and trim($lisaa_muistutus) == 'kylla') {
				$query = "	SELECT *
							FROM kalenteri
							WHERE yhtio = '$kukarow[yhtio]'
							AND tyyppi = 'Muistutus'
							AND tapa = 'Asentajan kuittaus'
							AND liitostunnus = '$liitostunnus'
							AND kentta02 = '$liitostunnus'
							AND kuittaus != ''
							ORDER BY tunnus DESC";
				$muistutus_chk_res = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($muistutus_chk_res) != 0) {
					$muistutus_chk_row = mysql_fetch_assoc($muistutus_chk_res);

					$query = "	UPDATE kalenteri SET
								pvmloppu = '$lyear-$lmonth-$lday $laika'
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$muistutus_chk_row[tunnus]'";
					$muistutus_insert_res = mysql_query($query) or pupe_error($query);
				}
				else {
					$kysely = "	SELECT viesti, comments, sisviesti2
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$liitostunnus'";
					$viestit_chk_res = mysql_query($kysely) or pupe_error($kysely);
					$viestit_chk_row = mysql_fetch_assoc($viestit_chk_res);

					kalenteritapahtuma ("Muistutus", "Asentajan kuittaus", "Muista työmääräys $liitostunnus\n\n$viestit_chk_row[viesti]\n$viestit_chk_row[comments]\n$viestit_chk_row[sisviesti2]", $liitostunnus, "K", '', $liitostunnus, "'$year-$month-$day $aika'", "'$lyear-$lmonth-$lday $laika'", 'ASENTAJA');
				}
			}

			$tee = "";
		}
		elseif ($tee == "LISAA") {
			$query = "	INSERT INTO kalenteri
						SET
						yhtio 		= '$kukarow[yhtio]',
						laatija		= '$kukarow[kuka]',
						luontiaika	= now(),
						tapa		= '$tyojono',
						kuka 		= '$asentaja',
						pvmalku 	= '$year-$month-$day $aika',
						pvmloppu 	= '$lyear-$lmonth-$lday $laika',
						liitostunnus= '$liitostunnus',
						tyyppi 		= 'asennuskalenteri'";

			if ($yhtiorow['tyomaarays_asennuskalenteri_muistutus'] == 'K' and isset($lisaa_muistutus) and trim($lisaa_muistutus) == 'kylla') {
				$query .= ", kentta02 = '$liitostunnus' ";

				$kysely = "	SELECT *
							FROM kalenteri
							WHERE yhtio = '$kukarow[yhtio]'
							AND tyyppi = 'Muistutus'
							AND tapa = 'Asentajan kuittaus'
							AND liitostunnus = '$liitostunnus'
							AND kentta02 = '$liitostunnus'
							AND kuittaus != ''
							ORDER BY tunnus DESC";
				$muistutus_chk_res = mysql_query($kysely) or pupe_error($kysely);

				if (mysql_num_rows($muistutus_chk_res) != 0) {
					$muistutus_chk_row = mysql_fetch_assoc($muistutus_chk_res);

					$kysely = "	UPDATE kalenteri SET
								pvmloppu = '$lyear-$lmonth-$lday $laika'
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$muistutus_chk_row[tunnus]'";
					$muistutus_insert_res = mysql_query($kysely) or pupe_error($kysely);
				}
				else {

					$kysely = "	SELECT viesti, comments, sisviesti2
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$liitostunnus'";
					$viestit_chk_res = mysql_query($kysely) or pupe_error($kysely);
					$viestit_chk_row = mysql_fetch_assoc($viestit_chk_res);

					kalenteritapahtuma ("Muistutus", "Asentajan kuittaus", "Muista työmääräys $liitostunnus\n\n$viestit_chk_row[viesti]\n$viestit_chk_row[comments]\n$viestit_chk_row[sisviesti2]", $liitostunnus, "K", '', $liitostunnus, "'$year-$month-$day $aika'", "'$lyear-$lmonth-$lday $laika'", 'ASENTAJA');
				}
			}

			mysql_query($query) or pupe_error($query);

			$tee = "";
		}
	}

	if ($tee == "MUOKKAA") {
		$query = "	SELECT *
					FROM kalenteri
					WHERE
					$konsernit
					and tunnus = '$tyotunnus'
					and tyyppi = 'asennuskalenteri'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$kalerow = mysql_fetch_array($result);

			$year			= substr($kalerow["pvmalku"], 0, 4);
			$month			= substr($kalerow["pvmalku"], 5, 2);
			$day			= substr($kalerow["pvmalku"], 8, 2);
			$aika			= substr($kalerow["pvmalku"], 11, 5);
			$lyear			= substr($kalerow["pvmloppu"], 0, 4);
			$lmonth			= substr($kalerow["pvmloppu"], 5, 2);
			$lday			= substr($kalerow["pvmloppu"], 8, 2);
			$aikaloppu		= substr($kalerow["pvmloppu"], 11, 5);
			$liitostunnus	= $kalerow["liitostunnus"];
			$asentaja		= $kalerow["kuka"];
			$tyojono		= $kalerow["tapa"];

			$tee = "VARAA";
		}
		else {
			$tee = "";
		}
	}

	if ($tee == "POISTA") {

		if ($yhtiorow['tyomaarays_asennuskalenteri_muistutus'] == 'K') {

			$query = "	SELECT pvmloppu, tunnus
						FROM kalenteri
						WHERE tyyppi = 'asennuskalenteri'
						AND liitostunnus = '$liitostunnus'
						AND kentta02 = '$liitostunnus'
						#AND tapa = '$tyojono'
						AND tunnus != '$tyotunnus'
						ORDER BY pvmloppu DESC";
			$kappale_chk_res = mysql_query($query) or pupe_error($query);

			$query = "	SELECT *
						FROM kalenteri
						WHERE yhtio = '$kukarow[yhtio]'
						AND tyyppi = 'Muistutus'
						AND tapa = 'Asentajan kuittaus'
						AND liitostunnus = '$liitostunnus'
						AND kentta02 = '$liitostunnus'
						AND kuittaus != ''
						ORDER BY tunnus DESC";
			$muistutus_chk_res = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($muistutus_chk_res) != 0) {
				$muistutus_chk_row = mysql_fetch_assoc($muistutus_chk_res);

				if (mysql_num_rows($kappale_chk_res) == 0) {
					$query = "	DELETE FROM kalenteri
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$muistutus_chk_row[tunnus]'
								AND pvmalku	= '$year-$month-$day $aika'
								AND pvmloppu = '$lyear-$lmonth-$lday $laika'
								AND liitostunnus = '$liitostunnus'
								AND kentta02 = '$liitostunnus'
								AND tunnus = '$muistutus_chk_row[tunnus]'";
					$muistutus_delete_res = mysql_query($query) or pupe_error($query);
				}
				else {
					while ($kappale_chk_row = mysql_fetch_assoc($kappale_chk_res)) {
						if ($kappale_chk_row['pvmloppu'] != "$lyear-$lmonth-$lday $laika") {
							$query = "	UPDATE kalenteri SET
										pvmloppu = '$kappale_chk_row[pvmloppu]'
										WHERE yhtio = '$kukarow[yhtio]'
										AND tunnus = '$muistutus_chk_row[tunnus]'";
							$muistutus_update_res = mysql_query($query) or pupe_error($query);
							break;
						}
					}
				}
			}
		}

		$query = "	DELETE
					FROM kalenteri
					WHERE
					$konsernit
					and tunnus = '$tyotunnus'
					and tyyppi = 'asennuskalenteri'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='message'>".t("Kalenterimerkintä poistettu")."!</font><br><br>";
		$tee = "";
	}

	if ($tee == "VARAA") {
		echo "<table>";

		echo "<form method='POST' action='$PHP_SELF#$year$month$day'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='tee'  			value='LISAA'>
				<input type='hidden' name='toim'			value='$toim'>
				<input type='hidden' name='year'  			value='$year'>
				<input type='hidden' name='month'  			value='$month'>
				<input type='hidden' name='day'  			value='$day'>
				<input type='hidden' name='liitostunnus'  	value='$liitostunnus'>
				<input type='hidden' name='asentaja'  		value='$asentaja'>
				<input type='hidden' name='tyojono'  		value='$tyojono'>
				<input type='hidden' name='aika'  			value='$aika'>";

		if ($tyotunnus > 0) {
			echo "<input type='hidden' name='tyotunnus' 	value='$tyotunnus'>";
		}

		echo "<tr><th>",t("Asentaja"),":</th><td>$asentaja</td></tr>";
		echo "<tr><th>",t("Työjono"),":</th><td>$tyojono</td></tr>";

		if ($yhtiorow['tyomaarays_asennuskalenteri_muistutus'] == 'K') {
			echo "<tr><th>",t("Lisää muistutus"),":</th>";
			echo "<td><select name='lisaa_muistutus'>";
			echo "<option value=''>",t("Ei lisätä muistutusta kalenteriin"),"</option>";
			echo "<option value='kylla'>",t("Lisätään muistutus kalenteriin"),"</option>";
			echo "</select></td></tr>";
		}

		if (!isset($lday)) $lday     = $day;
		if (!isset($lmonth)) $lmonth = $month;
		if (!isset($lyear)) $lyear   = $year;

		echo  "<tr><th nowrap>".t("Työn alku").":</th><td>".tv1dateconv(sprintf('%04d',$year)."-".sprintf('%02d',$month)."-".sprintf('%02d',$day))." - $aika</td></tr>";

		$whileaika = $AIKA_ARRAY[0];
		if (!isset($aikaloppu)) $aikaloppu = date("H:i", mktime(substr($aika,0,2), substr($aika,3,2)+60, 0));

		list($whlopt, $whlopm) = explode(":", $AIKA_ARRAY[count($AIKA_ARRAY)-1]);
		$whileloppu = sprintf("%02d", $whlopt+2);

		if ($whileloppu >= 24) $whileloppu= sprintf("%02d", $whileloppu-24);

		$whileloppu = $whileloppu.":".$whlopm;

		echo  "<tr>
			<th nowrap>$whileloppu ".t("Työn loppu").":</th>
			<td>
			<input type='text' size='3' name='lday' value='$lday'>
			<input type='text' size='3' name='lmonth' value='$lmonth'>
			<input type='text' size='5' name='lyear' value='$lyear'> - <select name='laika'>";

		while ($whileaika != $whileloppu) {

			$sel = '';
			if ($whileaika == $aikaloppu) {
				$sel = "SELECTED";
			}
			echo  "<option value='$whileaika' $sel>$whileaika</option>";

			$whileaika = date("H:i", mktime(substr($whileaika,0,2), substr($whileaika,3,2)+60, 0));
		}

		echo "</select></td>";
		echo "</tr>";
		echo "</table><br>";

		if ($tyotunnus > 0) {
			echo "<input type='submit' value='".t("Muokkaa")."'>";
		}
		else {
			echo "<input type='submit' value='".t("Lisää")."'>";
		}

		echo "</form>";

		if ($tyotunnus > 0) {
			echo "<br><br><br><form method='POST' action='$PHP_SELF#$year$month$day'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='tee'  			value='POISTA'>
					<input type='hidden' name='toim'			value='$toim'>
					<input type='hidden' name='year'  			value='$year'>
					<input type='hidden' name='month'  			value='$month'>
					<input type='hidden' name='day'  			value='$day'>
					<input type='hidden' name='lyear'			value='$lyear'>
					<input type='hidden' name='lmonth'			value='$lmonth'>
					<input type='hidden' name='lday'			value='$lday'>
					<input type='hidden' name='liitostunnus'  	value='$liitostunnus'>
					<input type='hidden' name='asentaja'  		value='$asentaja'>
					<input type='hidden' name='tyojono'  		value='$tyojono'>
					<input type='hidden' name='aika'  			value='$aika'>
					<input type='hidden' name='laika'			value='$aikaloppu'>
					<input type='hidden' name='tyotunnus' 	value='$tyotunnus'>";
			echo "<input type='submit' value='".t("Poista")."'>";
		}
	}

	if ($tee == "") {

		echo "<center><table>";
		echo "<th>".t("Kuukausi").":</th>";
		echo "<td><a href='$PHP_SELF?toim=$toim&day=1&month=$backmmonth&year=$backymonth&tyojono=$tyojono&liitostunnus=$liitostunnus&tyotunnus=$tyotunnus&lopetus=$lopetus'> << </a></td>";
		echo "<td>
				<form method='POST'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='liitostunnus'  value='$liitostunnus'>
				<input type='hidden' name='tyotunnus'  value='$tyotunnus'>
				<input type='hidden' name='toim' value='$toim'>
				<select name='month' Onchange='submit();'>";

		$i=1;
		foreach ($MONTH_ARRAY as $val) {
			if($i == $month) {
				$sel = "selected";
			}
			else {
				$sel = "";
			}
			echo "<option value='$i' $sel>$val</option>";
			$i++;
		}

		echo "</select></td>";
		echo "<td><a href='$PHP_SELF?toim=$toim&day=1&month=$nextmmonth&year=$nextymonth&tyojono=$tyojono&liitostunnus=$liitostunnus&tyotunnus=$tyotunnus&lopetus=$lopetus'> >> </a></td>";

		echo "<th>".t("Työjono").":</th><td>";

		echo "<select name='tyojono' Onchange='submit();'>";

		$vresult = t_avainsana("TYOM_TYOJONO");

		echo "<option value = ''>".t("Kaikki työjonot")."</option>";

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel="";
			if ($tyojono == $vrow['selite']) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[selite]' $sel>$vrow[selitetark]</option>";
		}

		echo "</select>";
		echo "</td>";
		echo "<td class='back'><input type='submit' value='".t("Näytä")."'></form></td>";
		echo "</tr>";
		echo "</table></center><br>";
	}

	if ($tee == "") {
		echo "<div style='width:99%; height:82%; position: absolute; overflow:auto;'>";
		echo "<table style='width: 90%; height: 90%;'>";

		$korkeus = floor(100 / (count($AIKA_ARRAY)+2))."%";

		if (count($ASENTAJA_ARRAY_TARK) < 5) {
			echo "<tr>";
			echo "<td class='back'></td>";

			foreach ($DAY_ARRAY as $d) {
				echo "<th nowrap><b>$d</b></th>";
			}

			echo "</tr>";
			echo "<tr>";
			echo "<td class='back' style='height: 100%;'>
					<table style='width: 100%; height: 100%;'>
					<tr><td class='back' style='height: $korkeus;'>&nbsp;</td></tr>
					<tr><td class='back' style='height: $korkeus;'>&nbsp;</td></tr>";

			foreach ($AIKA_ARRAY as $a) {
				if ($toim == 'TYOMAARAYS_ASENTAJA') $a = '&nbsp;';
				echo "<tr><td class='back' style='height: $korkeus;'>$a</td></tr>";
			}

			echo "</table>";
			echo "</td>";

			if (count($ASENTAJA_ARRAY_TARK) < 5) {
				// Kirjotetaan alkuun tyhjiä soluja
				if (weekday_number("1", $month, $year) < count($DAY_ARRAY)) {
					for ($i = 0; $i < weekday_number("1", $month, $year); $i++) {
						echo "<td class='back' style='height: 100%;'>&nbsp;</td>";
					}
				}
			}
		}

		$div_arrayt = array();
		$solu = 0;

	    for ($i = 1; $i <= days_in_month($month, $year); $i++) {

			$pvanro = date('w', mktime(0, 0, 0, $month, $i, $year))-1;

			if ($pvanro == -1) $pvanro = 6;

			if ($pvanro < count($DAY_ARRAY)) {

				if (count($ASENTAJA_ARRAY_TARK) < 5) echo "<td class='back' align='center' style='height: 100%;'>";

				$selectlisa = '';
				$orderlisa = '';
				$tyyppilisa = "'asennuskalenteri','kalenteri'";

				// asentaja saa nähdä vaan asennuskalenterin merkinnät.
				if ($toim == 'TYOMAARAYS_ASENTAJA') {
					$selectlisa = "lasku.tunnus, ";
					$orderlisa = " lasku.tunnus, ";
					$tyyppilisa = "'asennuskalenteri'";
				}

				$query = "	SELECT $selectlisa kalenteri.kuka,
							kalenteri.pvmalku,
							kalenteri.pvmloppu,
							kalenteri.tapa,
							kalenteri.tyyppi,
							if(kalenteri.tyyppi='asennuskalenteri', kalenteri.liitostunnus, kalenteri.tunnus) liitostunnus,
							if(lasku.nimi='', kalenteri.kuka, lasku.nimi) nimi,
							if(tyomaarays.komm1='' or tyomaarays.komm1 is null, kalenteri.kentta01, tyomaarays.komm1) komm1,
							tyomaarays.komm2, lasku.viesti, tyomaarays.tyostatus, 
							lasku.nimi, lasku.toim_nimi,
							kalenteri.konserni, a2.selitetark_2 tyostatusvari
							FROM kalenteri
							LEFT JOIN avainsana ON kalenteri.yhtio = avainsana.yhtio and avainsana.laji = 'KALETAPA' and avainsana.selitetark = kalenteri.tapa
							LEFT JOIN lasku ON kalenteri.yhtio=lasku.yhtio and lasku.tunnus=kalenteri.liitostunnus and lasku.tunnus > 0
							LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus  and tyomaarays.otunnus > 0
							LEFT JOIN avainsana a2 ON a2.yhtio=tyomaarays.yhtio and a2.laji='TYOM_TYOSTATUS' and a2.selite=tyomaarays.tyostatus
							WHERE kalenteri.yhtio = '$kukarow[yhtio]'
							and kalenteri.tyyppi in ($tyyppilisa)
							and (	(pvmalku >= '$year-$month-$i 00:00:00' and pvmalku <= '$year-$month-$i 23:59:00') or
									(pvmalku <  '$year-$month-$i 00:00:00' and pvmloppu > '$year-$month-$i 00:00:00') or
									(pvmloppu >='$year-$month-$i 00:00:00' and pvmloppu<= '$year-$month-$i 23:59:00'))
							order by $orderlisa pvmalku";
				$vres = mysql_query($query) or pupe_error($query);

				$varaukset 	= array();

				if (mysql_num_rows($vres) > 0) {

					$ed_tunnukset = array();

					while ($vrow = mysql_fetch_array($vres)) {
						foreach ($ASENTAJA_ARRAY as $b) {

							$aika_temp = $AIKA_ARRAY;
							$ed_aika = array_shift($aika_temp);

							foreach ($AIKA_ARRAY as $a) {

								$slot = str_replace(array(":","-"," "), "", $year."-".sprintf('%02d', $month)."-".sprintf('%02d', $i)." ".$a);

								// Koska kale on tuntitarkuudella työ alkaa aina "00" minuuttina
								$alku = str_replace(array(":","-"," "), "", substr($vrow["pvmalku"],0,14)."00");

								$lopp = str_replace(array(":","-"," "), "", substr($vrow["pvmloppu"],0,16));

								if ($alku <= $slot and $lopp > $slot and $vrow["kuka"] == $b) {

									if ($toim == 'TYOMAARAYS_ASENTAJA') {
										if (in_array($vrow['tunnus'], $ed_tunnukset["$b"])) {
											continue;
										}
										else {
											$a = $ed_aika;
											$ed_aika = array_shift($aika_temp);
										}
									}

									if (!in_array($vrow["liitostunnus"], $div_arrayt)) {
										$div_arrayt[] = $vrow["liitostunnus"];

										echo "<div id='div_$vrow[liitostunnus]' class='popup'>";

										if ($vrow["tyyppi"] == "asennuskalenteri") {
											echo t("Työmääräys").": $vrow[liitostunnus]";
											echo "<br>".t("Asiakas").": $vrow[nimi] $vrow[toim_nimi]";
										}
										else {
											echo t("Kalenterimerkintä").": $vrow[tapa]";
										}

										if (trim($vrow["viesti"]) != "") echo "<br><br>",t("Tilausviite"),": $vrow[viesti]";
										if (trim($vrow["komm1"]) != "") echo "<br><br>",t("Työn kuvaus").": ".str_replace("\n", "<br>", $vrow["komm1"]);
										if (trim($vrow["komm2"]) != "") echo "<br>".t("Toimenpiteet").": ".str_replace("\n", "<br>", $vrow["komm2"]);
										
										echo "</div>";
									}

									$varaukset[$b][$a][] = $vrow["nimi"]."|||".$vrow["liitostunnus"]."|||".$vrow["tapa"]."|||".$vrow["tyyppi"]."|||".$vrow["tyostatus"]."|||".$vrow['kuka']."|||".$vrow['konserni']."|||".$vrow['pvmalku']."|||".$vrow['tyostatusvari']."|||".$vrow['tunnus'];
									$ed_tunnukset["$b"][] = $vrow['tunnus'];
								}
							}
							
							if (is_array($varaukset[$b])) {
								natsort($varaukset[$b]);
							}
						}

						$ed_tunnukset[] = $vrow['tunnus'];
					}
				}

				if (count($ASENTAJA_ARRAY_TARK) < 5) {

					echo "<table style='width: 100%; height: 100%;'>";

					$sarakeleveys = "40px";

					echo "<tr><th style='text-align: center; height: $korkeus;' colspan='".count($ASENTAJA_ARRAY)."'><a name='{$i}_{$month}_{$year}'><b>$i</b></a></th></tr><tr>";

					foreach ($ASENTAJA_ARRAY_TARK as $b) {
						echo "<td align='center' nowrap style='width: $sarakeleveys; height: $korkeus;'>$b</td>";
					}
					echo "</tr>";
				}
				else {

					$sarakeleveys = floor(100 / (count($ASENTAJA_ARRAY) + 1))."%";

					echo "<tr>";

					if ($toim != 'TYOMAARAYS_ASENTAJA') echo "<td class='back'></td>";

					echo "<th colspan='".count($ASENTAJA_ARRAY)."'><a name='{$i}_{$month}_{$year}'>".$DAY_ARRAY[$pvanro].": $i.$month.$year</a></th></tr>";
					echo "<tr>";


					if ($toim != 'TYOMAARAYS_ASENTAJA') echo "<td class='back'></td>";

					foreach ($ASENTAJA_ARRAY_TARK as $b) {
						echo "<td align='center' style='width: $sarakeleveys; height: $korkeus;'>$b</td>";
					}

				    echo "</tr>";
				}

				foreach ($AIKA_ARRAY as $a) {

					echo "<tr>";

					if (count($ASENTAJA_ARRAY_TARK) >= 5 and $toim != 'TYOMAARAYS_ASENTAJA') {
						echo "<td class='back' style='width: $sarakeleveys; height: $korkeus;'>$a</td>";
					}

					foreach ($ASENTAJA_ARRAY as $b) {
						if (isset($varaukset[$b][$a])) {

							$varilisa = "";
							$tdekotus = "";

							foreach ($varaukset[$b][$a] as $varaus) {
								list($nimi, $tilausnumero, $tapa, $tyyppi, $tyostatus, $kuka, $konserni, $pvmalku, $tyostatusvari, $tunnus) = explode("|||", $varaus);

								if ($tyyppi == "asennuskalenteri") {

									if ($lopetus == '') {
										$lopetus = $palvelin2."tyomaarays/asennuskalenteri.php////toim=$toim//";

										if (isset($tyojono)) $tyojono .= "tyojono=$tyojono//";

										$lopetus .= "year=$year//month=$month";
									}

									$zul = $tilausnumero;

									if ($tyostatusvari != "") {
										$varilisa = " background-color: $tyostatusvari; ";
									}
								}
								else {
									$zul = $tapa;
								}

								if ($tyyppi == 'asennuskalenteri') {
									$tdekotus .= "<a class='tooltip' id='$tilausnumero' href='tyojono.php?myyntitilaus_haku=$tilausnumero&toim=$toim&tyojono=$tyojono&lopetus=$lopetus'>$zul</a> ";
								}
								else {
									$tdekotus .= "<a class='tooltip' id='$tilausnumero' href='".$palvelin2."crm/kalenteri.php?valitut=$kuka&kenelle=$kuka&tee=SYOTA&kello=".substr($pvmalku, 11, 5)."&year=$year&kuu=$month&paiva=$i&tunnus=$tilausnumero&tyomaarays=$tunnus&konserni=$konserni&lopetus=$lopetus'>$zul</a> ";
								}
							}

							if ($tdekotus == "") $tdekotus = "&nbsp;";

							echo "<td align='center' style='width: $sarakeleveys; height: $korkeus; $varilisa'>$tdekotus</td>";
						}
						elseif ($liitostunnus > 0 and $tyojono != "" and (float) str_replace("-", "", $laskurow["toimaika"]) < (float) $year.sprintf("%02d", $month).sprintf("%02d", $i)) {
							echo "<td align='center' style='width: $sarakeleveys; height: $korkeus;' class='tumma'>&nbsp;</td>";
						}
						elseif ($liitostunnus > 0 and $tyojono != "") {
		                    echo "<td align='center' style='width: $sarakeleveys; height: $korkeus;'><a class='td' name='$year$month$i' href='$PHP_SELF?year=$year&month=$month&day=$i&liitostunnus=$liitostunnus&tyojono=$tyojono&toim=$toim&asentaja=$b&aika=$a&tee=VARAA&lopetus=$lopetus'>&nbsp;</a></td>";
		                }
						else {
							echo "<td align='center' style='width: $sarakeleveys; height: $korkeus;'>&nbsp;</td>";
						}
					}
				}

				echo "</tr>";

				if (count($ASENTAJA_ARRAY_TARK) < 5) {
					echo "</table>";
					echo "</td>";
				}

				$solu++;
			}

			if (count($ASENTAJA_ARRAY_TARK) < 5 and weekday_number($i, $month, $year) == 6 and $solu > 0) {
				// Rivinvaihto jos seuraava viikko on olemassa
				if (days_in_month($month, $year) != $i) {
					echo "</tr><tr>";

					echo "<td class='back' style='vertical-align: bottom;'>
							<table style='width: 100%; height: 100%;'>
							<tr><td class='back' style='height: $korkeus;'>&nbsp;</td></tr>
							<tr><td class='back' style='height: $korkeus;'>&nbsp;</td></tr>";

					foreach ($AIKA_ARRAY as $a) {
						if ($toim == 'TYOMAARAYS_ASENTAJA') $a = '&nbsp;';
						echo "<tr><td class='back' style='height: $korkeus;'>$a</td></tr>";
					}
					echo "</table>";
					echo "</td>";
				}
			}
		}

		// Kirjotetaan loppuun tyhjiä soluja
		if (count($ASENTAJA_ARRAY_TARK) < 5 and weekday_number($i, $month, $year) < count($DAY_ARRAY) and weekday_number($i, $month, $year) > 0) {
			for ($a = weekday_number($i, $month, $year); $a <= count($DAY_ARRAY)-1; $a++) {
				echo "<td class='back'>&nbsp;</td>";
			}
		}

		echo "</tr>";
		echo "</table>";
		echo "</div>";
	}

	require("inc/footer.inc");

?>