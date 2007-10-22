<?php

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Työjono").":</font><hr><br>";
	
	$AIKA_ARRAY = array("08:00","09:00","10:00","11:00","12:00","13:00","14:00","15:00","16:00");
	
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
		echo "<th>".t("Yhtiö")."</th>";
	}
			
	echo "	<th>".t("Työmääräys")."<br>".t("Tilausviite")."</th>
			<th>".t("Ytunnus")."<br>".t("Asiakas")."</th>
			<th>".t("Työaika")."<br>".t("Työn suorittaja")."</th>
			<th>".t("Toimitetaan")."</th>
			<th>".t("Myyjä")."<br>".t("Tyyppi")."</th>
			<th>".t("Työjono")."<br>".t("Työstatus")."</th>
			<th>".t("Muokkaa")."</th>
			</tr>";
			
	echo "<tr>";
	
	if (trim($konserni) != '') {
		echo "<td></td>";
	}
	
	echo "<td valign='top'><input type='text' size='10'  name='myyntitilaus_haku'		value='$myyntitilaus_haku'><br>
							<input type='text' size='10' name='viesti_haku'				value='$viesti_haku'></td>";
	echo "<td valign='top'>	<input type='text' size='10' name='asiakasnumero_haku' 		value='$asiakasnumero_haku'><br>
							<input type='text' size='10' name='asiakasnimi_haku' 		value='$asiakasnimi_haku'></td>";
	echo "<td valign='top'><br>
							<input type='text' size='10' name='suorittaja_haku' 		value='$suorittaja_haku'><br></td>";
	echo "<td valign='top'></td>";
	echo "<td valign='top'></td>";
	echo "<td valign='top'><input type='text' size='10' name='tyojono_haku' 			value='$tyojono_haku'><br>
								<input type='text' size='10' name='tyostatus_haku' 		value='$tyostatus_haku'></td>";
	echo "<td valign='top'></td>";
	echo "<td valign='top' class='back'><input type='submit' value='Hae'></td>";
	echo "</tr>";
	echo "</form>";
	
	$lisa   = "";
	$lisa2  = "";

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
	
	// Myyydyt sarjanumerot joita ei olla ollenkaan ostettu
	$query = "	SELECT 
				lasku.tunnus,
				lasku.viesti,
				lasku.nimi,
				lasku.tila, 
				lasku.alatila,
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
				yhtio.nimi yhtio, 
				yhtio.yhtio yhtioyhtio,
				a3.nimi suorittajanimi,
				group_concat(a4.selitetark) asekalsuorittajanimi,
				group_concat(concat(left(kalenteri.pvmalku,16), '##', left(kalenteri.pvmloppu,16), '##', a4.selitetark_2, '##', kalenteri.tunnus)) asennuskalenteri
				FROM lasku
				JOIN yhtio ON lasku.yhtio=yhtio.yhtio
				JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
				LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
				LEFT JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja
				LEFT JOIN avainsana a1 ON a1.yhtio=tyomaarays.yhtio and a1.laji='TYOM_TYOJONO'   and a1.selite=tyomaarays.tyojono
				LEFT JOIN avainsana a2 ON a2.yhtio=tyomaarays.yhtio and a2.laji='TYOM_TYOSTATUS' and a2.selite=tyomaarays.tyostatus
				LEFT JOIN kuka a3 ON a3.yhtio=tyomaarays.yhtio and a3.kuka=tyomaarays.suorittaja
				LEFT JOIN kalenteri ON kalenteri.yhtio = lasku.yhtio and kalenteri.tyyppi = 'asennuskalenteri' and kalenteri.liitostunnus = lasku.tunnus
				LEFT JOIN avainsana a4 ON a4.yhtio=kalenteri.yhtio and a4.laji='TYOM_TYOLINJA'  and a4.selitetark=kalenteri.kuka
				WHERE $konsernit
				and lasku.tila in ('A','L','N','S')
				and lasku.alatila != 'X'
				$lisa
				GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18
				$lisa2
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
			echo "<div id='$vrow[tunnus]' class='popup' style='width:500px;'>";
			echo "Työmääräys: $vrow[tunnus]<br><br>".str_replace("\n", "<br>", $vrow["komm1"]."<br>".$vrow["komm2"])."<br><a href='#' onclick=\"popUp(event,'$vrow[tunnus]')\">Sulje</a>";
			echo "</div>";		
			echo "<td valign='top' class='spec' onmouseout=\"popUp(event,'$vrow[tunnus]')\" onmouseover=\"popUp(event,'$vrow[tunnus]')\">$vrow[tunnus]</td>";
		}
		else {
			echo "<td valign='top'>$vrow[tunnus]<br>$vrow[viesti]</td>";
		}
		
		echo "<td valign='top'>$vrow[ytunnus]<br>$vrow[nimi]</td>";
		
		
		echo "<td valign='top'>";
		
		if ($vrow["asennuskalenteri"] != "") {
			foreach(explode(",", $vrow["asennuskalenteri"]) as $asekale) {
				
				list($a, $l, $s, $t) = explode("##", $asekale);
				
				echo "<a href='asennuskalenteri.php?liitostunnus=$vrow[tunnus]&tyojono=$vrow[tyojonokoodi]&tyotunnus=$t&tee=MUOKKAA'>".tv1dateconv($a, "P")." - ".tv1dateconv($l, "P")." $s</a><br>";
			}
		}
		
		echo $vrow["suorittajanimi"];
		
		echo "</td>";
				
		if ($vrow["tyojono"] != "") {
			echo "<td valign='top'><a href='asennuskalenteri.php?liitostunnus=$vrow[tunnus]&tyojono=$vrow[tyojonokoodi]'>".tv1dateconv($vrow["toimaika"])."</a></td>";
		}
		else {
			echo "<td valign='top'>".tv1dateconv($vrow["toimaika"])."</td>";	
		}
		
		echo "<td valign='top'>$vrow[myyja]<br>".t("$laskutyyppi")." ".t("$alatila")."</td>
				<td valign='top'>$vrow[tyojono]<br>$vrow[tyostatus]</td>";
		
		if ($vrow["yhtioyhtio"] != $kukarow["yhtio"]) {		
			echo "<td valign='top'><a href='../tilauskasittely/tilaus_myynti.php?user=$kukarow[kuka]&pass=$kukarow[salasana]&yhtio=$vrow[yhtioyhtio]&toim=$toimi&tee=AKTIVOI&from=LASKUTATILAUS&tilausnumero=$vrow[tunnus]&lopetus=../tyomaarays/tyojono.php////user=$kukarow[kuka]//pass=$kukarow[salasana]//yhtio=$kukarow[yhtio]//konserni=$konserni'>".t("Muokkaa")."</a></td>";
		}
		else {
			echo "<td valign='top'><a href='../tilauskasittely/tilaus_myynti.php?toim=$toimi&tee=AKTIVOI&from=LASKUTATILAUS&tilausnumero=$vrow[tunnus]&lopetus=../tyomaarays/tyojono.php////konserni=$konserni'>".t("Muokkaa")."</a></td>";
		}
				
		echo "</tr>";
	}
	
	echo "</table><br><br>";
	
	require ("../inc/footer.inc");

?>