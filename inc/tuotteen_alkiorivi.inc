<?PHP
// tuotteen_alkiorivi.inc
// yllapito.php?ojarj=&haku[1]=%40000117-109&ohje=off&from=yllapito&lukitse_avaimeen=000117-109&lukitse_laji=&toim=tuotteen_alkio&tunnus=1&limit=&nayta_poistetut=&nayta_eraantyneet=&laji=

$ulos = "";
$jatko = 1; // oletetaan normaali k‰sittely
$tyyppi = 1; // oletetaan rivin n‰kyvyys

if (!isset($apu)) {
	$apu = array();
}


//yhtiˆt‰ ei n‰ytet‰
if (mysql_field_name($result, $i) == "yhtio") {
	$tyyppi = 0;
}

if (mysql_field_name($result, $i) == "laji") {

	$sel = array($trow[$i] => "SELECTED");

	$ulos = "<td><select name='$nimi' onchange='submit();'>";

	$sresult = t_avainsana("DYNAAMINEN_PUU");
	
	$ulos .= "<option value=''>".t('Valitse p‰‰kategoria')."</option>";
	
	while ($srow = mysql_fetch_array($sresult)) {
		$ulos .= "<option value='$srow[selite]' ".$sel[$srow["selite"]].">$srow[selite] $srow[selitetark]</option>";
	}
	
	$ulos .= "</select>";
	
	$apu['laji'] = $trow[$i];
	$jatko = 0;
}

if (mysql_field_name($result, $i) == "tuoteno") {
	if($lukitse_avaimeen != "") {
		$ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
		$jatko = 0;
	}
	elseif(isset($alasveto[$i])) {
		$ulos = "<td>".$alasveto[$i]."</td>";
		$jatko = 0;
	}
}



if (mysql_field_name($result, $i) == "selite") {

	if ($apu['laji'] == '') {
		
	}
	else {
		
		$ulos = "<td><a href='{$palvelin2}dynaaminen_puu.php?laji={$apu['laji']}&tee=tuotteet&tuoteno={$lukitse_avaimeen}&selite={$trow[$i]}&yhtio={$kukarow[yhtio]}&ttunnus={$tunnus}' ";
		$ulos .= "target='main'> ".t('Valitse t‰h‰n puuhun sopivat segmentit')." </a><br />";
		$ulos .= "<br /><input type='hidden' name='$nimi' value='{$trow[$i]}' /> {$trow[$i]}</td>";
	}
	$jatko = 0;
}

if (mysql_field_name($result, $i) == "kieli") {
	
	$ulos = "<td><select name='$nimi'>";
	$sel  = "";

	//	n‰ytet‰‰‰n vain kielet joita on
	if ($perhe > 0) {
		$query = "SELECT group_concat(distinct kieli) kielet FROM avainsana WHERE yhtio = '$kukarow[yhtio]' and kieli != ''";
		$res = mysql_query($query) or pupe_error($query);
		$kilerow = mysql_fetch_array($res);
		$kielet = $kilerow["kielet"];
	}

	$query  = "SHOW columns from sanakirja";
	$fields =  mysql_query($query);

	while ($apurow = mysql_fetch_array($fields)) {
		if (strlen($apurow[0]) == 2 and (strpos($kielet, $apurow[0]) === false or $apurow[0] == $trow[$i])) {
			$sel = "";
			if ($trow[$i] == $apurow[0]) {
				$sel = "SELECTED";
			}
			elseif ($trow[$i] == "" and $apurow[0] == $yhtiorow["kieli"]) {
				$sel = "SELECTED";
			}
			$ulos .= "<option value='$apurow[0]' $sel>$apurow[0] - ".maa($apurow[0])."</option>";
		}
	}

	$ulos .= "</select></td>";

	$jatko = 0;
}

?>