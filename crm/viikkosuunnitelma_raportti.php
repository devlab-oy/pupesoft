<?php

require("../inc/parametrit.inc");

echo "<font class='head'>".t("Viikkosuunnitelma")."</font><hr>";

$sel1 = '';
$sel2 = '';

$sel1 = '';
$sel2 = '';
$lisa = "";

if ($vstk == '') {
	$sel11 = "CHECKED";
	$vstk = "Viikkosuunnitelma";
}
if ($vstk == 'Viikkosuunnitelma') {
	$sel11 = "CHECKED";
}
if ($vstk == 'Asiakask�ynti') {
	$sel22 = "CHECKED";
}

//echo "<li><a href='$PHP_SELF?tee=VALITSE_TIEDOSTO'>".t("Sis��nlue suunnitelma-/asiakask�yntitiedosto")."</a>";
//echo "<br><li><a href='$PHP_SELF?tee=laheta&kausi=$kausi&vstk=$vstk'>".t("Vie asiakastietopaketti s�hk�postiisi")."</a><br><br>";

print " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
		<!--

		function toggleAll(toggleBox) {

			var currForm = toggleBox.form;
			var isChecked = toggleBox.checked;

			for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
				if (currForm.elements[elementIdx].type == 'checkbox') {
					currForm.elements[elementIdx].checked = isChecked;
				}
			}
		}

		//-->
		</script>";



if ($tee == 'laheta') {		
	
	echo "<br><br><font class='message'>".t("Asiakastietopakettit l�hetetty s�hk�postiisi")."!</font><br><br><br>";
	
	require("laheta_asiakastietopaketti.inc");
	
	$tee = "";

}		



if ($tee == '') {	

	echo "<br><table>
			<form action='$PHP_SELF' method='post'>";

	$asosresult = t_avainsana("ASIAKASOSASTO");
	
	echo "<tr><th>".t("Valitse asiakkaan osasto").":</th><td colspan='3'><select name='asos'>";
	echo "<option value=''>".t("Kaikki osastot")."</option>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($asos == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}
	echo "</select></td></tr>";
	
	$asosresult = t_avainsana("PIIRI");
	
	echo "<tr><th>".t("Valitse asiakkaan piiri").":</th><td colspan='3'><select name='aspiiri'>";
	echo "<option value=''>".t("Kaikki piirit")."</option>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($aspiiri == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}
	echo "</select></td></tr>";
	
	$asosresult = t_avainsana("ASIAKASRYHMA");
	
	echo "<tr><th>".t("Valitse asiakkaan ryhm�").":</th><td colspan='3'><select name='asryhma'>";
	echo "<option value=''>".t("Kaikki ryhm�t")."</option>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($asryhma == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}
	
	echo "	</select>
			</td>
			</tr>";
							
	$chk = "";
	if (trim($konserni) != '') {
		$ckh = "CHECKED";
	}
	
	echo "<tr><th>".t("N�yt� konsernin kaikki asiakkaat").":</th><td colspan='3'><input type='checkbox' name='konserni' $ckh></td>";
	echo "</tr>";						
	
	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	
	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");
	
	echo "<tr><th>Sy�t� alkup�iv�m��r� (pp-kk-vvvv)</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>Sy�t� loppup�iv�m��r� (pp-kk-vvvv)</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";
	echo "<tr>";
		
	echo "	<th>".t("N�yt� viikkosuunnitelmat")." </th><td colspan='3'><input type='radio' name='vstk' value='Viikkosuunnitelma' $sel11></td></tr>
			<tr><th>".t("N�yt� asiakask�ynnit")." </th><td colspan='3'><input type='radio' name='vstk' value='Asiakask�ynti' $sel22></td></tr>";
	
	if (isset($kalen)) {
		foreach($kalen as $tama) {
			$valitut .= "$tama,";
		}
		$valitut = substr($valitut,0,-1); // Viimeinen pilkku poistetaan
	}
	else {
		if (!isset($valitut)) {
			$valitut = "$kukarow[kuka]"; // Jos ketaan ei ole valittu valitaan kayttaja itse
			$vertaa  = "'$kukarow[kuka]'";
		}
		else {
			$vertaa = "'$valitut'";
		}
	}
	
	$ruksatut   = explode(",", $valitut);					//tata kaytetaan ihan lopussa
	$ruksattuja = count($ruksatut);   					//taman avulla pohditaan tarvitaanko tarkenteita
	$vertaa     = "'".implode("','", $ruksatut)."'";	// tehd��n mysql:n ymm�rt�m� muoto
	
	
	if (in_array("$kukarow[kuka]", $ruksatut)) { // Oletko valinnut itsesi
		$checked = 'checked';
	}
	
	echo "<tr>
			<th>".t("Listaa edustajat")."</th>";
			
	echo "	<td colspan='3'><div style='width:280px;height:265px;overflow:auto;'>
			
			<table width='100%'><tr>
			<td><input type='checkbox' name='kalen[]' value = '$kukarow[kuka]' $checked></td>
			<td>Oma</td></tr>";
	
	$query = "	SELECT kuka.tunnus, kuka.nimi, kuka.kuka
				FROM kuka, oikeu
				WHERE kuka.yhtio	= '$kukarow[yhtio]'
				and oikeu.yhtio		= kuka.yhtio
				and oikeu.kuka		= kuka.kuka
				and oikeu.nimi		= 'crm/viikkosuunnitelma.php' 
				and kuka.tunnus <> '$kukarow[tunnus]'
				ORDER BY kuka.nimi";
	$result = mysql_query($query) or pupe_error($query);
	
	while ($row = mysql_fetch_array($result)) {
		$checked = '';
		if (in_array("$row[kuka]", $ruksatut)) {
			$checked = 'checked';
		}
		echo "<tr><td nowrap><input type='checkbox' name='kalen[]' value='$row[kuka]' $checked></td><td>$row[1]</td></tr>";
	}
	
	echo "<tr><td><input type='checkbox' name='chbox' onclick='toggleAll(this)'></td><td>N�yt� kaikki</td></tr>";
	
	echo "</table>";
	echo "</div></td></tr></table>";
	echo "<br><input type='submit' value='Jatka'>";
	echo "</form>";
	
	
	if ($asos != '') {
		$lisa .= " and asiakas.osasto='$asos' ";
	}
	
	if ($aspiiri != '') {
		$lisa .= " and asiakas.piiri='$aspiiri' ";
	}
	
	if ($asryhma != '') {
		$lisa .= " and asiakas.ryhma='$asryhma' ";
	}
	
	if (trim($konserni) != '') {
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
		$result = mysql_query($query) or pupe_error($query);
		$yhtiot = array();
		
		while ($row = mysql_fetch_array($result)) {	
			$yhtiot[] = $row["yhtio"];
		}
	}
	else {
		$yhtiot = array();
		$yhtiot[] = $kukarow["yhtio"];
	}

	echo "<br><br><table>";
	
	foreach($yhtiot as $yhtio) {
				
		$query = "	SELECT kuka.nimi kukanimi, 
					if(asiakas.toim_postitp!='', asiakas.toim_postitp, asiakas.postitp) postitp, 
					if(asiakas.toim_postino!='' and asiakas.toim_postino!='00000', asiakas.toim_postino, asiakas.postino) postino, 
					kalenteri.asiakas ytunnus, asiakas.asiakasnro asiakasno, kalenteri.yhtio, 
					if (kalenteri.asiakas!='', asiakas.nimi, 'N/A') nimi, 
					left(kalenteri.pvmalku,10) pvmalku, 					
					kentta01, kentta02, kentta03, kentta04, if(right(pvmalku,8)='00:00:00','',right(pvmalku,8)) aikaalku, if(right(pvmloppu,8)='00:00:00','',right(pvmloppu,8)) aikaloppu
					FROM kalenteri
					LEFT JOIN asiakas use index (ytunnus_index) on asiakas.tunnus=kalenteri.liitostunnus and asiakas.yhtio='$yhtio' $lisa
					LEFT JOIN kuka on kuka.kuka = kalenteri.kuka and kuka.yhtio='$yhtio'
					WHERE kalenteri.yhtio='$yhtio'
					and kalenteri.kuka in ($vertaa)
					and kalenteri.pvmalku >= '$vva-$kka-$ppa 00:00:00'
					and kalenteri.pvmalku <= '$vvl-$kkl-$ppl 23:59:59'
					and kalenteri.tapa     = '$vstk'
					and kalenteri.tyyppi in ('kalenteri','memo')
					order by pvmalku, kalenteri.tunnus";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) > 0) {
			
			echo "<tr><th>".t("Edustaja")."</th><th>".t("Yhtio")."</th><th>".t("Paikka")."</th><th>".t("Postino")."</th><th>".t("Asiakas")."</th><th>".t("Asiakasno")."</th><th>".t("Nimi")."</th><th>".t("Pvm")."</th>";
		
			if ($vstk == "Asiakask�ynti") {
				echo "<th>".t("Kampanjat")."</th><th>".t("PvmK�yty")."</th><th>".t("Km")."</th><th>".t("L�ht�")."</th><th>".t("Paluu")."</th><th>".t("PvRaha")."</th><th>".t("Kommentit")."</th></tr>";
			}
				
			while ($row=mysql_fetch_array($result)) {
				echo "<tr>
						<td>$row[kukanimi]</td>
						<td>$row[yhtio]</td>
						<td>$row[postitp]</td>
						<td>$row[postino]</td>
						<td>$row[ytunnus]</td>
						<td>$row[asiakasno]</td>
						<td><a href='asiakasmemo.php?ytunnus=$row[ytunnus]'>$row[nimi]</a></td>
						<td>$row[pvmalku]</td>";
						
				if ($vstk == "Asiakask�ynti") {
					echo "	<td>$row[kentta02]</td>
							<td>$row[pvmalku]</td>
							<td>$row[kentta03]</td>
							<td>$row[aikaalku]</td>
							<td>$row[aikaloppu]</td>
							<td>$row[kentta04]</td>
							<td>$row[kentta01]</td>";
				}
						
				echo "</tr>";
			}
		}
	}
	
	echo "</table>";
}	
		

require("../inc/footer.inc");
		
		
?>