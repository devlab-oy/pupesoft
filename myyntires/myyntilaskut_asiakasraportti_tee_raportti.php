<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

// estetään sivun lataus suoraan
if (!empty($HTTP_GET_VARS["oikeus"]) ||
    !empty($HTTP_POST_VARS["oikeus"]) ||
    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
    !isset($oikeus)) {

  echo "<p>".t("Kielletty toiminto")."!</p>";
  exit;
}

//yleiset tiedot asiakkaasta
$tila = 'tee_raportti';

if (isset($tunnus)) {
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
	
	$query = "	SELECT sum(round(summa*if(kurssi=0, 1, kurssi),2)) summa, sum(summa) summa_valuutassa
				FROM suoritus
				WHERE yhtio='$kukarow[yhtio]' 
				and ltunnus<>0 
				and asiakas_tunnus in ($tunnukset)
				$salisa";
	$result = mysql_query($query) or pupe_error($query);
	$kaato = mysql_fetch_array($result);

	$query = "	SELECT count(tunnus) as maara, 
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
				and mapvm = '0000-00-00'";
	$result = mysql_query($query) or pupe_error($query);
	$kok = mysql_fetch_array($result);

	echo "
	<table>
	<tr>
	<th><a href='../crm/asiakasmemo.php?ytunnus=$ytunnus'>$asiakasrow[nimi]</a></td>
	<td>".t("Kaatotilillä")."</td>
	<td></td>";
	
	if ($savalkoodi != "" and strtoupper($yhtiorow['valkoodi']) != strtoupper($savalkoodi) and $valuutassako == 'V') {
		echo "<td align='right'>$kaato[summa_valuutassa]</td>";
	}
	else {
		echo "<td align='right'>$kaato[summa]</td>";	
	}
	
	echo "
	</tr>
	<tr>
	<th>$ytunnus</td>
	<td>".t("Myöhässä olevia laskuja yhteensä")."</td>
	<td align='right'>$kok[eraantynytmaara] kpl</td>";
	
	if ($savalkoodi != "" and strtoupper($yhtiorow['valkoodi']) != strtoupper($savalkoodi) and $valuutassako == 'V') {
		echo "<td align='right'>$kok[eraantynytsumma_valuutassa]</td>";
	}
	else {
		echo "<td align='right'>$kok[eraantynytsumma]</td>";
	}
	
	echo "
	</tr>
	<tr>
	<th>$asiakasrow[osoite]</td>
	<td>".t("Avoimia laskuja yhteensä")."</td>
	<td align='right'>$kok[avoinmaara] kpl</td>";
	
	if ($savalkoodi != "" and strtoupper($yhtiorow['valkoodi']) != strtoupper($savalkoodi) and $valuutassako == 'V') {
		echo "<td align='right'>$kok[avoinsumma_valuutassa]</td>";
	}
	else {
		echo "<td align='right'>$kok[avoinsumma]</td>";
	}
	
	echo "
	</tr>
	<tr>
	<th>$asiakasrow[postino] $asiakasrow[postitp]</td>
	<td>".t("Laskuja yhteensä")."</td>
	<td align='right'>$kok[maara] kpl</td>";
	
	if ($savalkoodi != "" and strtoupper($yhtiorow['valkoodi']) != strtoupper($savalkoodi) and $valuutassako == 'V') {
		echo "<td align='right'>$kok[summa_valuutassa]</td>";
	}
	else {
		echo "<td align='right'>$kok[summa]</td>";
	}
	
	echo "
	</tr>
	<tr>
	<th></th><td><a href='../raportit/asiakasinfo.php?ytunnus=$ytunnus'>".t("Asiakkaan myyntitiedot")."</a></td>
	<td></td>
	<td></td>
	</tr>";

	echo "<table><tr><td class='back'>";

	// ikäanalyysi
	echo "<table><tr><th>&lt;-2</th><th>-2--1</th><th>0-15</th><th>16-30</th><th>31-60</th><th>&gt;60</th></tr>";

	$palkki_html = '';
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
	echo "<td align='right'>$kok[maara1]</td>";
	echo "<td align='right'>$kok[maara2]</td>";
	echo "<td align='right'>$kok[maara3]</td>";
	echo "<td align='right'>$kok[maara4]</td>";
	echo "<td align='right'>$kok[maara5]</td>";
	echo "<td align='right'>$kok[maara6]</td>";

	if ($yhtmaara != 0) {
		$palkki_html .= "<img src='$kuvaurl[0]' height='$palkki_korkeus' width='" . ($kok['maara1']/$yhtmaara) * $palkki_leveys ."'>";
		$palkki_html .= "<img src='$kuvaurl[1]' height='$palkki_korkeus' width='" . ($kok['maara2']/$yhtmaara) * $palkki_leveys ."'>";
		$palkki_html .= "<img src='$kuvaurl[2]' height='$palkki_korkeus' width='" . ($kok['maara3']/$yhtmaara) * $palkki_leveys ."'>";
		$palkki_html .= "<img src='$kuvaurl[3]' height='$palkki_korkeus' width='" . ($kok['maara4']/$yhtmaara) * $palkki_leveys ."'>";
		$palkki_html .= "<img src='$kuvaurl[4]' height='$palkki_korkeus' width='" . ($kok['maara5']/$yhtmaara) * $palkki_leveys ."'>";
		$palkki_html .= "<img src='$kuvaurl[5]' height='$palkki_korkeus' width='" . ($kok['maara6']/$yhtmaara) * $palkki_leveys ."'>";
	}

	echo "</tr>";

	echo "</table>";
	echo "</td><td class='back' align='center'>";

	//visuaalinen esitys maksunopeudesta (hymynaama)
	list ($naama, $nopeushtml) = laskeMaksunopeus($ytunnus, $kukarow["yhtio"]);

	echo "$nopeushtml<br>";
	echo "$palkki_html";
	echo "</td><td class='back'>$naama</td></tr></table><br>";

	//avoimet laskut
	$kentat = 'laskunro, tapvm, erpcm, summa, kapvm, kasumma, mapvm, ika, viikorkoeur, olmapvm, tunnus';
	$kentankoko = array(8,8,8,10,8,10,8,4,6,8);

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
	
	if ($savalkoodi != "" and strtoupper($yhtiorow['valkoodi']) != strtoupper($savalkoodi) and $valuutassako == 'V') {
		$selectit = "	laskunro, tapvm, erpcm, summa_valuutassa-saldo_maksettu_valuutassa summa, kapvm, kasumma_valuutassa kasumma, mapvm, 
						TO_DAYS(mapvm) - TO_DAYS(erpcm) ika, viikorkoeur korko, olmapvm korkolaspvm, lasku.tunnus, saldo_maksettu_valuutassa saldo_maksettu ";
	}
	else {
		$selectit = "	laskunro, tapvm, erpcm, summa-saldo_maksettu summa, kapvm, kasumma, mapvm, 
						TO_DAYS(mapvm) - TO_DAYS(erpcm) ika, viikorkoeur korko, olmapvm korkolaspvm, lasku.tunnus, saldo_maksettu" ;
	}

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
				and mapvm = '0000-00-00'";
	$aasres = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($aasres) > 0) {
			
		echo "	<th>".t("Valuutta").":</th>
				<td><select name='savalkoodi' onchange='submit();'>";
		echo "<option value = ''>".t("Kaikki")."</option>";
	
		while($aasrow = mysql_fetch_array($aasres)) {
			$sel="";
			if ($aasrow["valuutat"] == strtoupper($savalkoodi)) {
				$sel = "selected";
			}
		
			echo "<option value = '$aasrow[valuutat]' $sel>$aasrow[valuutat]</option>";
		}
		
		if ($savalkoodi != "" and strtoupper($yhtiorow['valkoodi']) != strtoupper($savalkoodi)) {	
			echo "	<th>".t("Summat").":</th>
					<td><select name='valuutassako' onchange='submit();'>
					<option value = ''>".t("Yrityksen valuutassa")."</option>
					<option value = 'V' $chk4>".t("Laskun valuutassa")."</option>
					</select></td>";
		}
	}		
	
	echo "</tr>";
	echo "</form>";
	echo "</table><br>";

	echo "<table>";
	echo "<tr>";
	echo "<form action = '$PHP_SELF?tila=$tila&tunnus=$tunnus&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi' method = 'post'>";
	echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=0".$ulisa."'>".t("Laskunro")."</a></th>";
	echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=1".$ulisa."'>".t("Pvm")."</a></th>";
	echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=2".$ulisa."'>".t("Eräpäivä")."</a></th>";
	echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=3".$ulisa."'>".t("Summa")."</a></th>";
	echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=4".$ulisa."'>".t("Kassa-ale pvm")."</a></th>";
	echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=5".$ulisa."'>".t("Kassa-ale summa")."</a></th>";
	echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=6".$ulisa."'>".t("Maksupvm")."</a></th>";
	echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=7".$ulisa."'>".t("Ikä")."</a></th>";
	echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=8".$ulisa."'>".t("Korko")."</a></th>";
	echo "<th valign='top'><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&valuutassako=$valuutassako&savalkoodi=$savalkoodi&ojarj=9".$ulisa."'>".t("Korkolaskun pvm")."</a></th>";

	echo "<td class='back'></td></tr>";
	echo "<tr>";

	for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
	  echo "<td><input type='text' size='$kentankoko[$i]' name = 'haku[$i]' value = '$haku[$i]'></td>";
	}

	echo "<td class='back'><input type='submit' value='".t("Etsi")."'></td></tr>";
	echo "</form>";

	$summa = 0;

	while ($maksurow=mysql_fetch_array ($result)) {

		echo "<tr>";
		echo "<td><a href='../muutosite.php?tee=E&tunnus=$maksurow[tunnus]'>$maksurow[laskunro]</a></td>";
	
		echo "<td>".tv1dateconv($maksurow["tapvm"])."</td>";
		echo "<td>".tv1dateconv($maksurow["erpcm"])."</td>";
	
		if ($maksurow["saldo_maksettu"] != 0 and $maksurow["mapvm"] == "0000-00-00") {
			$maksurow["summa"] .= "*";
		}
		echo "<td align='right'>$maksurow[summa]</td>";
	
		if ($maksurow["kapvm"] != '0000-00-00') echo "<td>".tv1dateconv($maksurow["kapvm"])."</td>";
		else echo "<td></td>";
	
		if ($maksurow["kasumma"] != 0) echo "<td align='right'>$maksurow[kasumma]</td>";
		else echo "<td></td>";
	
		if ($maksurow["mapvm"] != '0000-00-00') echo "<td>".tv1dateconv($maksurow["mapvm"])."</td>";
		else echo "<td></td>";
	
		echo "<td align='right'>$maksurow[ika]</td>";
	
		if ($maksurow["korko"] != 0) echo "<td align='right'>$maksurow[korko]</td>";
		else echo "<td></td>";
	
		if ($maksurow["korkolaspvm"] != '0000-00-00') echo "<td>".tv1dateconv($maksurow["korkolaspvm"])."</td>";
		else echo "<td></td>";

		$summa+=$maksurow["summa"];
		echo "<td class='back'></td></tr>";
	}

	echo "<tr><td class='back' colspan='2'></td><th>".t("Yhteensä").":</th><td class='tumma' align='right'>$summa</th></tr>";

	echo "</table>";

	echo "<br>";
	if ($kaato["summa"]>0) {
		echo "<form action = 'maksa_kaatosumma.php?tunnus=$tunnus' method = 'post'>";
		echo "<input type='submit' value='".t("Maksa kaatotilin summa asiakkaalle")."'></submit>";
		echo "</form>";
	}

	echo "<script LANGUAGE='JavaScript'>document.forms[0][0].focus()</script>";
}

?>