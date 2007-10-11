<?php

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Työjono").":</font><hr><br>";
	
	$AIKA_ARRAY = array("08:00","09:00","10:00","11:00","12:00","13:00","14:00","15:00","16:00");
	
	$query = "	SELECT selite, selitetark
				FROM avainsana
				WHERE yhtio = '$kukarow[yhtio]' and laji = 'TYOM_TYOLINJA'
				ORDER BY jarjestys, selite";
	$kires = mysql_query($query) or pupe_error($query);

	$ASENTAJA_ARRAY = array();
	$ASENTAJA_ARRAY_TARK = array();

	while ($kirow = mysql_fetch_array($kires)) {
		$ASENTAJA_ARRAY[] = $kirow["selite"];
		$ASENTAJA_ARRAY_TARK[] = $kirow["selitetark"];
	}
	
	//kuukaudet ja päivät ja ajat
	$MONTH_ARRAY = array(1=>'Tammikuu','Helmikuu','Maaliskuu','Huhtikuu','Toukokuu','Kesäkuu','Heinäkuu','Elokuu','Syyskuu','Lokakuu','Marraskuu','Joulukuu');
	$DAY_ARRAY = array("Maanantai", "Tiistai", "Keskiviikko", "Torstai", "Perjantai");
	
	
	echo "<form name='haku' action='$PHP_SELF' method='post'>";
	
	$chk = "";
	if (trim($konserni) != '') {
		$chk = "CHECKED";
	}
	
	echo t("Näytä konsernin työmäräykset").":<input type='checkbox' name='konserni' $chk onclick='submit();'><br><br>";
			
	echo "<table>";	
	echo "<tr>";
			
	if (trim($konserni) != '') {
		echo "<th>Yhtiö</th>";
	}
			
	echo "	<th>Työmääräys<br>Tilausviite</th>
			<th>Ytunnus / Asiakas</th>
			<th>Työaika</th>
			<th>Toimitetaan</th>
			<th>Myyjä<br>Tyyppi</th>
			<th>Työjono<br>Työstatus</th>
			<th>Muokkaa</th>
			</tr>";
			
	echo "<tr>";
	
	if (trim($konserni) != '') {
		echo "<td></td>";
	}
	
	echo "<td valign='top'><input type='text' size='10'  name='myyntitilaus_haku'		value='$myyntitilaus_haku'><br>
							<input type='text' size='10' name='viesti_haku'				value='$viesti_haku'></td>";
	echo "<td valign='top'>	<input type='text' size='10' name='asiakasnumero_haku' 		value='$asiakasnumero_haku'><br>
							<input type='text' size='10' name='asiakasnimi_haku' 		value='$asiakasnimi_haku'></td>";
	echo "<td valign='top'></td>";
	echo "<td valign='top'></td>";
	echo "<td valign='top'></td>";
	echo "<td valign='top'><input type='text' size='10' name='tyojono_haku' 			value='$tyojono_haku'><br>
								<input type='text' size='10' name='tyostatus_haku' 		value='$tyostatus_haku'></td>";
	echo "<td valign='top'></td>";
	echo "<td valign='top' class='back'><input type='submit' value='Hae'></td>";
	echo "</tr>";
	echo "</form>";
	
	$lisa  = "";

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
	
	if (trim($konserni) != '') {
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
		$result = mysql_query($query) or pupe_error($query);
		$konsernit = "";
		
		while ($row = mysql_fetch_array($result)) {	
			$konsernit .= " '".$row["yhtio"]."' ,";
		}		
		$konsernit = " lasku.yhtio in (".substr($konsernit, 0, -1).") ";			
	}
	else {
		$konsernit = " lasku.yhtio = '$kukarow[yhtio]' ";
	}
	
	// scripti balloonien tekemiseen
	js_popup();	
	
	//Myyydyt sarjanumerot joita ei olla ollenkaan ostettu
	$query = "	SELECT tyomaarays.*, laskun_lisatiedot.*, lasku.*, kuka.nimi myyja, tyomaarays.otunnus tyomaarays, a1.selite tyojonokoodi, a1.selitetark tyojono, a2.selitetark tyostatus, yhtio.nimi yhtio, yhtio.yhtio yhtioyhtio
				FROM lasku
				LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
				JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
				LEFT JOIN kuka on kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja
				LEFT JOIN avainsana a1 ON a1.yhtio=tyomaarays.yhtio and a1.laji='TYOM_TYOJONO' and a1.selite=tyomaarays.tyojono
				LEFT JOIN avainsana a2 ON a2.yhtio=tyomaarays.yhtio and a2.laji='TYOM_TYOSTATUS' and a2.selite=tyomaarays.tyostatus
				JOIN yhtio on lasku.yhtio=yhtio.yhtio
				WHERE $konsernit
				and lasku.tila in ('A','L','N','S')
				and lasku.alatila != 'X'
				$lisa
				ORDER BY toimaika";
	$vresult = mysql_query($query) or pupe_error($query);

	while ($vrow = mysql_fetch_array($vresult)) {	
		
		$laskutyyppi = $vrow["tila"];
		$alatila	 = $vrow["alatila"];
		
		//tehdään selväkielinen tila/alatila
		require "inc/laskutyyppi.inc";
		
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
			echo "<div id='$vrow[tyomaarays]' class='popup' style='width:500px;'>";
			echo "Työmääräys: $vrow[tyomaarays]<br><br>".str_replace("\n", "<br>", $vrow["komm1"])."<br><a href='#' onclick=\"popUp(event,'$vrow[tyomaarays]')\">Sulje</a>";
			echo "</div>";		
			echo "<td valign='top' class='spec' onmouseout=\"popUp(event,'$vrow[tyomaarays]')\" onmouseover=\"popUp(event,'$vrow[tyomaarays]')\">$vrow[tyomaarays]</td>";
		}
		else {
			echo "<td valign='top'>$vrow[tyomaarays]<br>$vrow[viesti]</td>";
		}
		
		echo "<td valign='top'>$vrow[ytunnus]<br>$vrow[nimi]</td>";
		
		
		$query = "	SELECT kalenteri.kuka, kalenteri.liitostunnus, kalenteri.pvmalku, kalenteri.pvmloppu, kalenteri.tunnus
					FROM kalenteri
					LEFT JOIN avainsana ON kalenteri.yhtio = avainsana.yhtio and avainsana.laji = 'KALETAPA' and avainsana.selitetark = kalenteri.tapa
					WHERE kalenteri.yhtio = '$kukarow[yhtio]'
					and kalenteri.tyyppi = 'asennuskalenteri'
					and kalenteri.liitostunnus = '$vrow[tyomaarays]'
					order by pvmalku";
		$kaleres = mysql_query($query) or pupe_error($query);

		echo "<td valign='top'>";
		
		while($kalerow = mysql_fetch_array($kaleres)) {
			echo "<a href='asennuskalenteri.php?liitostunnus=$vrow[tyomaarays]&tyojono=$vrow[tyojonokoodi]&tyotunnus=$kalerow[tunnus]&tee=MUOKKAA'>".substr(tv1dateconv($kalerow["pvmalku"], "X"), 0, 16)." - ".substr(tv1dateconv($kalerow["pvmloppu"], "X"), 0, 16)." (".$ASENTAJA_ARRAY_TARK[$kalerow["kuka"]].")<br>";
		}
		
		echo "</td>";
				
		if ($vrow["tyojono"] != "") {
			echo "<td valign='top'><a href='asennuskalenteri.php?liitostunnus=$vrow[tyomaarays]&tyojono=$vrow[tyojonokoodi]'>".tv1dateconv($vrow["toimaika"])."</a></td>";
		}
		else {
			echo "<td valign='top'>".tv1dateconv($vrow["toimaika"])."</td>";	
		}
		
		echo "<td valign='top'>$vrow[myyja]<br>".t("$laskutyyppi")." ".t("$alatila")."</td>
				<td valign='top'>$vrow[tyojono]<br>$vrow[tyostatus]</td>";
		
		if ($vrow["yhtioyhtio"] != $kukarow["yhtio"]) {		
			echo "<td valign='top'><a href='../tilauskasittely/tilaus_myynti.php?user=$kukarow[kuka]&pass=$kukarow[salasana]&yhtio=$vrow[yhtioyhtio]&toim=$toimi&tee=AKTIVOI&from=LASKUTATILAUS&tilausnumero=$vrow[tyomaarays]&lopetus=../tyomaarays/tyojono.php////user=$kukarow[kuka]//pass=$kukarow[salasana]//yhtio=$kukarow[yhtio]//konserni=$konserni'>".t("Muokkaa")."</a></td>";
		}
		else {
			echo "<td valign='top'><a href='../tilauskasittely/tilaus_myynti.php?toim=$toimi&tee=AKTIVOI&from=LASKUTATILAUS&tilausnumero=$vrow[tyomaarays]&lopetus=../tyomaarays/tyojono.php////konserni=$konserni'>".t("Muokkaa")."</a></td>";
		}
				
		echo "</tr>";
	}
	
	echo "</table><br><br>";
	
	require ("../inc/footer.inc");

?>
