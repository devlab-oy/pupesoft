<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	//parametrit
	include('../inc/parametrit.inc');

	js_popup();

	//Näytetään aina konsernikohtaisesti
	if ($yhtiorow["konserni"] != "") {
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
		$result = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($result)) {
			$konsernit .= " '".$row["yhtio"]."' ,";
		}
	}
	else {
		$konsernit = "'".$kukarow["yhtio"]."' ,";
	}

	$konsernit1 = " and yhtio in (".substr($konsernit, 0, -1).") ";
	$konsernit2 = " and kalenteri.yhtio in (".substr($konsernit, 0, -1).") ";
	$konsernit3 = " and kuka.yhtio in (".substr($konsernit, 0, -1).") ";

	//kuukaudet ja päivät ja ajat
	$MONTH_ARRAY = array(1=>t('Tammi'),t('Helmi'),t('Maalis'),t('Huhti'),t('Touko'),t('Kesä'),t('Heinä'),t('Elo'),t('Syys'),t('Loka'),t('Marras'),t('Joulu'));
	$DAY_ARRAY = array(t("Ma"), t("Ti"), t("Ke"), t("To"), t("Pe"), t("La"), t("Su"));

	//kalenteritoiminnot
	function days_in_month($month, $year){
		// calculate number of days in a month
		return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
	}

	function weekday_name($day, $month, $year) {
		// calculate weekday name
		$days = array(t("Maanantai"), t("Tiistai"), t("Keskiviikko"), t("Torstai"), t("Perjantai"), t("Lauantai"),t("Sunnuntai"));
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

	// otetaan oletukseksi tämä monthkausi ja tämä vuosi
	if ($month == '') $month=date("n");
	if ($year == '')  $year=date("Y");
	if ($day == '') $day=date("j");

	//lasketaan edellinen ja seuraava paiva kun siirytaan yksi paiva
	$backmday = date("n",mktime(0, 0, 0, $month, $day-1,  $year));
	$backyday = date("Y",mktime(0, 0, 0, $month, $day-1,  $year));
	$backdday = date("j",mktime(0, 0, 0, $month, $day-1,  $year));

	$nextmday = date("n",mktime(0, 0, 0, $month, $day+1,  $year));
	$nextyday = date("Y",mktime(0, 0, 0, $month, $day+1,  $year));
	$nextdday = date("j",mktime(0, 0, 0, $month, $day+1,  $year));

	//lasketaan edellinen ja seuraava paiva kun siirytaan yksi kuukausi
	$backmmonth = date("n",mktime(0, 0, 0, $month-1, $day,  $year));
	$backymonth = date("Y",mktime(0, 0, 0, $month-1, $day,  $year));
	$backdmonth = date("j",mktime(0, 0, 0, $month-1, $day,  $year));

	$nextmmonth = date("n",mktime(0, 0, 0, $month+1, $day,  $year));
	$nextymonth = date("Y",mktime(0, 0, 0, $month+1, $day,  $year));
	$nextdmonth = date("j",mktime(0, 0, 0, $month+1, $day,  $year));

	//viela muuttujat mysql kyselyja varten, (etunollat pitaa olla...)
	$mymonth = $month;
	$myday = $day;

	$lopetus = "../crm/kuukausinakyma.php////osasto=$osasto//year=$year//month=$month//day=$pva//toim=$toim";

	echo "<font class='head'>".t("Kuukausinäkymä").": ". $MONTH_ARRAY[$month] ." $year</font><hr>";

	echo "<table>";
	echo "<th>".t("Valitse kuukausi").":</th><td>
			<form method='post'>
			<input type='hidden' name='year'  value='$year'>
			<input type='hidden' name='toim' value='$toim'>
			<a href='$PHP_SELF?day=1&month=$backmmonth&year=$backymonth&osasto=$osasto&toim=$toim'> << </a>
			<select name='month' Onchange='submit();'>";

	$i=1;
	foreach($MONTH_ARRAY as $val) {
		if($i == $month) {
			$sel = "selected";
		}
		else {
			$sel = "";
		}
		echo "<option value='$i' $sel>$val</option>";
		$i++;
	}

	echo "</select><a href='$PHP_SELF?day=1&month=$nextmmonth&year=$nextymonth&osasto=$osasto&toim=$toim'> >> </a></td>";
	echo "<td class='back' width='20'></td>";
	echo "<th>".t("Valitse osasto").":</th><td><select name='osasto' Onchange='submit();'>";

	//Haetaan kaikki käyttäjät
	$query = "	SELECT distinct kuka.osasto
				FROM kuka, oikeu
				WHERE oikeu.yhtio	= kuka.yhtio
				and oikeu.kuka		= kuka.kuka
				and oikeu.nimi like '%kalenteri.php'
				and kuka.osasto	   != ''
				$konsernit3
				ORDER BY kuka.osasto";
	$result = mysql_query($query) or pupe_error($query);

	// jos ei olla valittu osastoa ja käyttäjällä on oma osasto valitaan se
	if ($osasto == "" and $kukarow["osasto"] != "") {
		$osasto = $kukarow["osasto"];
	}

	if ($osasto == "kaikki") {
		$osasto = "";
	}

	echo "<option value='kaikki'>".t("Näytetään kaikki")."</option>";

	while($row = mysql_fetch_array($result)) {
		if($row["osasto"] == $osasto) {
			$sel = "selected";
		}
		else {
			$sel = "";
		}
		echo "<option value='$row[osasto]' $sel>$row[osasto]</option>";

	}

	if($osasto == "varauskalenterit") {
		$sel = "selected";
	}
	else {
		$sel = "";
	}

	echo "<option value='varauskalenterit' $sel>".t("Varauskalenterit")."</option>";
	echo "</select></td></form>";
	echo "</table>";

	echo "<table>";

	if ($osasto != '') {
		$osastolisa = " and kuka.osasto='".$osasto."'";
	}
	else {
		$osastolisa = "";
	}

	// Haetaan kaikki käyttäjät
	$query = "	SELECT distinct if(kuka.nimi!='', kuka.nimi, kuka.kuka) nimi, kuka.kuka, kuka.osasto
				FROM kuka, oikeu
				WHERE oikeu.yhtio	= kuka.yhtio
				and oikeu.kuka		= kuka.kuka
				and oikeu.nimi like '%kalenteri.php'
				and kuka.osasto	   != ''
				$konsernit3
				$osastolisa
				ORDER BY kuka.osasto, kuka.nimi";
	$result = mysql_query($query) or pupe_error($query);

	while($row = mysql_fetch_array($result)) {

		echo "\n";

		$nimi = explode(' ', $row["nimi"]);

		if ($edosasto != $row["osasto"]) {
			echo "<tr><td class='back'></td></tr>";

			echo "<tr>";
			echo "<th>$row[osasto]</th><th></th>";

			$y = weekday_number("1", $month, $year);
			$x = days_in_month($month, $year);
			$u = 1;

			for($r = $y; $r < count($DAY_ARRAY); $r++) {
				echo "<th>$DAY_ARRAY[$r]<br>$u</th>";

				if ($r == 6) {
					$r = -1;

					echo "<td class='back'></td>";
				}

				if ($u >= $x) {
					break;
				}
				$u++;
			}
			echo "</tr>";
		}

		if ($row["kuka"] == $valkuka) {
			$varitys = "th";
		}
		else {
			$varitys = "td";
		}

		echo "<tr><$varitys nowrap><a href='$PHP_SELF?day=1&month=$month&year=$year&valkuka=$row[kuka]&osasto=$osasto&toim=$toim' title='$row[nimi]'>$row[nimi]</a></$varitys><td></td>";

		for ($i = 1; $i <= days_in_month($month, $year); $i++) {
			$pva = $i;

			$query = "	SELECT avainsana.selite tapa, avainsana.selitetark, kalenteri.tunnus, pvmalku, pvmloppu, kalenteri.yhtio,
						date_format(pvmalku,'%H:%i') kello, date_format(pvmalku,'%d') paiva, date_format(pvmalku,'%m') kuu, date_format(pvmalku,'%Y') vuosi,
						kalenteri.kuka, date_format(pvmloppu,'%H:%i') lkello,
						kalenteri.kuittaus,
						kalenteri.laatija,
						replace(kentta01,'\r\n',' ') kentta01
						FROM kalenteri
						LEFT JOIN avainsana ON kalenteri.yhtio = avainsana.yhtio and avainsana.laji = 'KALETAPA' and avainsana.selitetark = kalenteri.tapa
						WHERE kalenteri.kuka = '$row[kuka]'
						and kalenteri.tyyppi in ('asennuskalenteri','kalenteri')
						and ((pvmalku >= '$year-$mymonth-$pva 00:00:00' and pvmalku <= '$year-$mymonth-$pva 23:59:00') or
						(pvmalku < '$year-$mymonth-$pva 00:00:00' and pvmloppu > '$year-$mymonth-$pva 00:00:00'))
						$konsernit2
						order by pvmalku";
			$kresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($kresult) > 0) {
				$krow = mysql_fetch_array($kresult);

				$etufontti  = "";
				$takafontti = "";

				if ($krow["kuittaus"] == "" and ($krow["selitetark"] == "Palkaton vapaa" or $krow["selitetark"] == "Sairasloma" or $krow["selitetark"] == "Kesäloma" or $krow["selitetark"] == "Talviloma" or $krow["selitetark"] == "Ylityövapaa")) {
					$etufontti = "<font style='color:#FF0000;'>";
					$takafontti = "</font>";
				}
				elseif ($krow["kuittaus"] != "" and ($krow["selitetark"] == "Palkaton vapaa" or $krow["selitetark"] == "Sairasloma" or $krow["selitetark"] == "Kesäloma" or $krow["selitetark"] == "Talviloma" or $krow["selitetark"] == "Ylityövapaa")) {
					$etufontti = "<font style='color:#00FF00;'>";
					$takafontti = "</font>";
				}

				//Vanhoja kalenteritapahtumia ei saa enää muuttaa
				list($rvv,$rkk,$rpp) = explode("-",substr($krow["pvmloppu"],0,10));
				$kaleloppu  = (int) date('Ymd',mktime(0,0,0,$rkk,$rpp,$rvv));
				$aikanyt 	= (int) date('Ymd',mktime(0,0,0,date('m'),date('d'),date('Y')));

				// Vanhoja kalenteritapahtumia ei saa enää muuttaa ja Hyväksyttyjä lomia ei saa ikinä muokata
				if($krow['tunnus'] != '' and $krow["kuittaus"] == "" and $kaleloppu >= $aikanyt and ($krow['kuka'] == $kukarow["kuka"] or $krow['laatija'] == $kukarow["kuka"])) {
					echo "<div id='div_$krow[tunnus]' class='popup' style='width:200px;'>";

					if (mysql_num_rows($kresult) > 0) {
						mysql_data_seek($kresult,0);

						while ($krow2 = mysql_fetch_array($kresult)) {
							echo "$krow2[kello]-$krow2[lkello] $row2[nimi]<br>$krow2[selitetark].<br>$krow2[kentta01]<br><br>";
						}
					}
					echo "</div>";

					echo "<$varitys align='center' width='35' nowrap class='tooltip' id='$krow[tunnus]'><a class='td' href='kalenteri.php?valitut=$row[kuka]&kenelle=$row[kuka]&tee=SYOTA&kello=$krow[kello]&year=$krow[vuosi]&kuu=$krow[kuu]&paiva=$krow[paiva]&tunnus=$krow[tunnus]&konserni=XXX&toim=$toim'>$etufontti $krow[tapa] $takafontti</a></$varitys>";
				}
				else {
					echo "	<div id='div_$krow[tunnus]' class='popup' style='width:200px;'>
							$krow[kello]-$krow[lkello] $row[nimi]<br>$krow[selitetark].<br>$krow[kentta01]
							</div>";

					echo "<$varitys align='center' width='35' nowrap class='tooltip' id='$krow[tunnus]'>$etufontti $krow[tapa] $takafontti</$varitys>";
				}
			}
			else {
				echo "<$varitys align='center' width='35' nowrap><a class='td' href='kalenteri.php?valitut=$row[kuka]&kenelle=$row[kuka]&tee=SYOTA&kello=&year=$year&kuu=$month&paiva=$pva&konserni=XXX&toim=$toim'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a></$varitys>";
			}

			if (weekday_number($i, $month, $year)==6) {
				echo "<td class='back'></td>";
			}

			echo "\n";
		}

		$edosasto = $row["osasto"];

		echo "</tr>";
	}

	echo "<tr><td class='back'><br></td></tr>";

	if ($osasto == "" or $osasto == "varauskalenterit") {
		if ($edosasto != $row["osasto"] or ($edosasto=="")) {
			echo "<tr><td class='back'></td></tr>";

			echo "<tr>";
			echo "<th>".t("Varauskalenterit")."</th><th></th>";
			$y = weekday_number("1", $month, $year);
			$x = days_in_month($month, $year);
			$u = 1;

			for($r = $y; $r < count($DAY_ARRAY); $r++) {
				echo "<th>$DAY_ARRAY[$r]<br>$u</th>";

				if ($r == 6) {
					$r = -1;

					echo "<td class='back'></td>";
				}

				if ($u >= $x) {
					break;
				}
				$u++;
			}
			echo "</tr>";
		}

		$query = "	SELECT distinct alanimi
					FROM oikeu
					WHERE
					oikeu.nimi	= 'varauskalenteri/varauskalenteri.php'
					and oikeu.kuka  = ''
					and yhtio = '$kukarow[yhtio]'
					ORDER BY alanimi";
		$result = mysql_query($query) or pupe_error($query);

		while($row = mysql_fetch_array($result)) {

			echo "<tr><td width='35'>$row[alanimi]</td><td></td>";

			for($i = 1; $i <= days_in_month($month, $year); $i++) {
				$pva = $i;

				$query = "	SELECT kalenteri.tunnus tunnus, pvmalku, pvmloppu, kalenteri.yhtio, if(kuka.nimi!='', kuka.nimi, kuka.kuka) nimi, replace(kentta01,'\r\n',' ') kentta01,
							date_format(pvmalku,'%H:%i') kello, date_format(pvmloppu,'%H:%i') lkello
							FROM kalenteri, kuka
							WHERE kalenteri.tyyppi = 'varauskalenteri'
							and pvmalku  <= '$year-$mymonth-$pva 23:59:00'
							and pvmloppu >= '$year-$mymonth-$pva 00:00:00'
							and kalenteri.tapa  = '$row[alanimi]'
							and kalenteri.yhtio	= kuka.yhtio
							and kalenteri.kuka	= kuka.kuka
							$konsernit2
							order by pvmalku";
				$kresult = mysql_query($query) or pupe_error($query);
				$krow = mysql_fetch_array($kresult);

				if($krow["nimi"] == '') {
					echo "<td align='center' width='35'><a class='td' href='../varauskalenteri/varauskalenteri.php?lopetus=$lopetus&tee=SYOTA&year=$year&month=$month&day=$pva&toim=$row[alanimi]'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a></td>";
				}
				else {
					echo "<div id='div_$krow[tunnus]' class='popup' style='width:200px;'>";

					if (mysql_num_rows($kresult) > 0) {
						mysql_data_seek($kresult,0);

						while ($krow2 = mysql_fetch_array($kresult)) {
							echo "$krow2[kello]-$krow2[lkello] $krow2[nimi]<br>$row[alanimi].<br>$krow2[kentta01]<br><br>";
						}
					}
					echo "</div>";
					echo "<td align='center' width='35' class='tooltip' id='$krow[tunnus]'><a class='td' href='../varauskalenteri/varauskalenteri.php?lopetus=$lopetus&tee=NAYTA&year=$year&month=$month&day=$pva&tunnus=$krow[tunnus]&toim=$row[alanimi]'>$krow[nimi]</a></td>";
				}

				if (weekday_number($i, $month, $year)==6) {
					echo "<td class='back'></td>";
				}

				echo "\n";

			}

			echo "</tr>";
		}
	}

	echo "</table>";

	require ("inc/footer.inc");

?>