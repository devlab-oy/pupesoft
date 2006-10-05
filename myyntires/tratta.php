<?php

require "../inc/parametrit.inc";

//HUOMHUOM!!
$query = "SET SESSION group_concat_max_len = 100000";
$result = mysql_query($query) or pupe_error($query);

echo "<font class='head'>".t("Tratta")."</font><hr>";

//vain n‰in monta p‰iv‰‰ sitten karhutut
//laskut huomioidaan trattauksessa
$kpvm_aikaa  = 0;


//vain n‰in monta p‰iv‰‰ sitten er‰‰ntyneet
//laskut huomioidaan n‰kym‰sss‰
$lpvm_aikaa  = 10;


//lasku pit‰‰ olla karhuttu v‰h n‰in
//monta kertaa jotta sit‰ haluutaan tratata
$karhu_kerta = 2;

if ($kukarow["kirjoitin"] == 0) {
	echo "<font class='error'>".t("Sinulla pit‰‰ olla henkilˆkohtainen tulostin valittuna, ett‰ voit tulostaa trattoja").".</font><br>";
	$tee = "";
}

if ($tee == 'LAHETA') {
	require('paperitratta.php');
	
	$tee = "TRATTAA";
}

if ($tee == "ALOITATRATTAAMINEN") {
	$query = "	SELECT GROUP_CONCAT(distinct ovttunnus) konsrernyhtiot 
				FROM yhtio
				WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
	$result = mysql_query($query) or pupe_error($query);			
	
	$konslisa = "";
	if (mysql_num_rows($result) > 0) {
		$konsrow = mysql_fetch_array($result);
		
		$konslisa = " and lasku.ovttunnus not in ($konsrow[konsrernyhtiot])";				
	}
	
	$asiakaslisa = "";
	if ($syot_ytunnus != '') {
		$asiakaslisa = " and asiakas.ytunnus >= '$syot_ytunnus' ";
	}
	
	$query = "	SELECT GROUP_CONCAT(distinct lasku.tunnus) tratattavat
				FROM lasku
				JOIN (	SELECT lasku.tunnus,
						maksuehto.jv,
						max(karhukierros.pvm) kpvm,
						count(distinct karhu_lasku.ktunnus) karhuttu					
						FROM lasku use index (yhtio_tila_mapvm)					
						JOIN karhu_lasku on (lasku.tunnus=karhu_lasku.ltunnus)
						JOIN karhukierros on (karhukierros.tunnus=karhu_lasku.ktunnus)
						LEFT JOIN maksuehto on (maksuehto.yhtio=lasku.yhtio and maksuehto.tunnus=lasku.maksuehto)					
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'U'
						and lasku.mapvm	= '0000-00-00'
						and (lasku.erpcm < date_sub(now(), interval $lpvm_aikaa day) or lasku.summa < 0)
						and lasku.summa	!= 0
						group by lasku.tunnus
						HAVING kpvm < date_sub(now(), interval $kpvm_aikaa day)
						and karhuttu >= '$karhu_kerta' 
						and (maksuehto.jv is null or maksuehto.jv = '')) as laskut																			
				JOIN asiakas ON lasku.yhtio=asiakas.yhtio and lasku.liitostunnus=asiakas.tunnus					
				WHERE lasku.tunnus = laskut.tunnus
				$konslisa
				$asiakaslisa
				GROUP BY asiakas.ytunnus, asiakas.nimi, asiakas.nimitark, asiakas.osoite, asiakas.postino, asiakas.postitp
				ORDER BY lasku.ytunnus";					
	$result = mysql_query($query) or pupe_error($query);
				
	if (mysql_num_rows($result) > 0) {
		$tratattavat = array();
		
		while($karhuttavarow = mysql_fetch_array($result)) {		 
			$tratattavat[] = $karhuttavarow["tratattavat"];
		}
		$tee = "TRATTAA";
	}
	else {
		echo "<font class='message'>".t("Ei tratattavia asiakkaita")."!</font><br><br>";
		$tee = "";
	}
}

if ($tee == 'TRATTAA' and $tratattavat[0] == "") {
	echo "<font class='message'>".t("Kaikki asiakkaat tratattu")."!</font><br><br>";
	$tee = "";
}

if ($tee == 'TRATTAA')  {
										
	$query = "	SELECT lasku.liitostunnus,
				lasku.summa-lasku.saldo_maksettu as summa, 
				lasku.erpcm, lasku.laskunro, lasku.tapvm, lasku.tunnus,
				TO_DAYS(now())-TO_DAYS(lasku.erpcm) as ika, 
				max(karhukierros.pvm) as kpvm, 
				count(distinct karhu_lasku.ktunnus) as karhuttu				
				FROM lasku
				JOIN karhu_lasku on (lasku.tunnus=karhu_lasku.ltunnus)
				JOIN karhukierros on (karhukierros.tunnus=karhu_lasku.ktunnus)			
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus in ($tratattavat[0])
				GROUP BY lasku.tunnus
				ORDER BY lasku.erpcm";
	$result = mysql_query($query) or pupe_error($query);
																																						
	//otetaan asiakastiedot ekalta laskulta
	$asiakastiedot = mysql_fetch_array($result);
	
	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' and tunnus = '$asiakastiedot[liitostunnus]'";
	$asiakasresult = mysql_query($query) or pupe_error($query);
	$asiakastiedot = mysql_fetch_array($asiakasresult);
	
	//ja kelataan akuun
	mysql_data_seek($result,0);
	
	echo "<table><td valign='top' class='back'>";

	echo "<table>
	<tr><th>".t("Ytunnus")."</th><td>$asiakastiedot[ytunnus]</td></tr>
	<tr><th>".t("Nimi")."</th><td>$asiakastiedot[nimi]</td></tr>
	<tr><th>".t("Nimitark")."</th><td>$asiakastiedot[nimitark]</td></tr>
	<tr><th>".t("Osoite")."</th><td>$asiakastiedot[osoite]</td></tr>
	<tr><th>".t("Postinumero")."</th><td>$asiakastiedot[postino] $asiakastiedot[postitp]</td></tr>";
	echo "</table>";

	echo "</td><td valign='top' class='back'>";

	echo "<table>";
	echo "<tr><th>".t("Edellinen karhu v‰h").".</th><td>$kpvm_aikaa ".t("p‰iv‰‰ sitten").".</td></tr>";
	echo "<tr><th>".t("Er‰p‰iv‰st‰ v‰h").".</th><td>$lpvm_aikaa ".t("p‰iv‰‰").".</td></tr>";
	echo "<tr><th>".t("Karhukertoja v‰h").".</th><td>$karhu_kerta ".t("kertaa").".</td></tr>";
	echo "<tr><td class='back'></td><td class='back'><br></td></tr>";

	$query = "	SELECT GROUP_CONCAT(distinct liitostunnus) liitokset
				FROM lasku
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus in ($tratattavat[0])";
	$lires = mysql_query($query) or pupe_error($query);
	$lirow = mysql_fetch_array($lires);
	
	$query = "	SELECT SUM(summa) summa
				FROM suoritus
				WHERE yhtio  = '$kukarow[yhtio]' 
				and ltunnus <> 0 
				and asiakas_tunnus in ($lirow[liitokset])";
	$summaresult = mysql_query($query) or pupe_error($query);
	$kaato = mysql_fetch_array($summaresult);
	
	$kaatosumma=$kaato["summa"];
	if (!$kaatosumma) $kaatosumma='0.00';

	echo "<tr><th>".t("Kaatotilill‰")."</th><td>$kaatosumma</td></tr>";

	echo "</table>";
	echo "</td></tr></table><br>";

	//Poistetaan arraysta k‰ytetyt tunnukset
	unset($tratattavat[0]);	

	echo "<table>";
	echo "<tr>";
	echo "<td><input type='button' onclick='javascript:document.lahetaformi.submit();' value='".t("L‰het‰")."'></td>";
	echo "<td><input type='button' onclick='javascript:document.ohitaformi.submit();' value='".t("Ohita")."'></td>";
	echo "</tr>";
	echo "</table><br>";
	
	echo "<form name='lahetaformi' action='$PHP_SELF' method='post'>";
	echo "<table><tr>";
	echo "<th>".t("Laskunpvm")."</th>";
	echo "<th>".t("Laskunro")."</th>";
	echo "<th>".t("Summa")."</th>";
	echo "<th>".t("Er‰p‰iv‰")."</th>";
	echo "<th>".t("Ik‰ p‰iv‰‰")."</th>";
	echo "<th>".t("Karhuttu")."</th>";
	echo "<th>".t("Edellinen karhu")."</th>";
	echo "<th>".t("Tratataan")."</th>";

	$summmmma = 0;

	while ($lasku=mysql_fetch_array($result)) {

		echo "<tr><td>";

		if ($kukarow['taso'] < 2) {
			echo $lasku["tapvm"];
		}
		else {
			echo "<a href = '../muutosite.php?tee=E&tunnus=$lasku[tunnus]'>$lasku[tapvm]</a>";
		}

		echo "</td><td>";
		echo "<a href = '../tilauskasittely/tulostakopio.php?toim=LASKU&laskunro=$lasku[laskunro]'>$lasku[laskunro]</a>";
		echo "</td><td align='right'>";
		echo $lasku["summa"];
		echo "</td><td>";
		echo $lasku["erpcm"];
		echo "</td><td align='right'>";
		echo $lasku["ika"];
		echo "</td><td align='right'>";
		echo $lasku["karhuttu"];
		echo "</td><td>";
		echo $lasku["kpvm"];
		echo "</td><td align='center'>";
		echo "<input type='checkbox' name = 'lasku_tunnus[]' value = '$lasku[tunnus]' checked>";
		echo "</td></tr>\n";

		$summmmma += $lasku["summa"];
	}

	$summmmma += $kaatosumma;

	echo "<th colspan='2'>".t("Karhuttavaa yhteens‰")."</th>";
	echo "<th>$summmmma</th>";
	echo "<td class='back'></td></tr>";

	echo "</table>";

	echo "<br><table>";
	echo "<tr>";
			
	echo "<input name='tee' type='hidden' value='LAHETA'>";				
	echo "<input name='yhteyshenkilo' type='hidden' value='$yhteyshenkilo'>";
	
	foreach($tratattavat as $tunnukset) {
		echo "\n<input type='hidden' name='tratattavat[]' value='$tunnukset'>";		
	}
	
	echo "<td><input name='$kentta' type='submit' value='".t("L‰het‰")."'></td></form>";
	
	
	echo "<form name='ohitaformi' action='$PHP_SELF' method='post'>";			
	echo "<input type='hidden' name='tee' value='TRATTAA'>";
	echo "<input name='yhteyshenkilo' type='hidden' value='$yhteyshenkilo'>";
	
	foreach($tratattavat as $tunnukset) {
		echo "\n<input type='hidden' name='tratattavat[]' value='$tunnukset'>";		
	}
	
	echo "<td><input type='submit' value='".t("Ohita")."'></td>";
	echo "</form></tr>";
	echo "</table>";
}
	
if ($tee == "") {
				
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='tee' value='ALOITATRATTAAMINEN'>";		
	echo t("Syˆt‰ ytunnus jos haluat tratata tietty‰ asiakasta").".<br>".t("J‰t‰ kentt‰ tyhj‰ksi jos haluat aloittaa trattaamisen ensimm‰isest‰ asiakkaasta").".<br><br>";
	echo "<table>";
	
	$apuqu = "	select kuka, nimi, puhno, eposti, tunnus
				from kuka 
				where yhtio='$kukarow[yhtio]' and nimi!='' and puhno!='' and eposti!='' and extranet=''";
	$meapu = mysql_query($apuqu) or pupe_error($apuqu);
	
	echo "<tr><th>".t("Yhteyshenkilˆ").":</th>";		
	echo "<td><select name='yhteyshenkilo'>";
	
	while($row = mysql_fetch_array($meapu)) {
		$sel = "";
		
		if ($row['kuka'] == $kukarow['kuka']) {
			$sel = 'selected';
		}
				
		echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
	}
	
	echo "</select></td></tr>";
	
	
	echo "<tr><th>".t("Ytunnus").":</th>";	
	echo "<td>";	
	echo "<input name='syot_ytunnus' type='text' value='$syot_ytunnus'></td>";
	echo "<td><input type='submit' value='".t("Aloita")."'></td>";
	echo "</form></tr>";
	echo "</table>";
}

require "../inc/footer.inc";

?>
