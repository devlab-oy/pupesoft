<?PHP
// puun_alkiorivi.inc

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

	$sresult = t_avainsana("DYNAAMINEN_PUU");

	if (isset ($toim2) and strtolower(trim($toim2)) == 'tuote') {

		while ($srow = mysql_fetch_array($sresult)) {
				
			if (strtolower(trim($toim2)) == strtolower(trim($srow['selite']))) {
				$ulos = "<td>".$srow['selite']."<input type='hidden' value='$srow[selite]' name='$nimi' />";
				$apu['laji'] = $srow['selite'];
			}
			
		}

		mysql_data_seek($sresult,0);
	}
	else {
		$sel = array($trow[$i] => "SELECTED");

		$ulos = "<td><select name='$nimi' onchange='submit();'>";

		$ulos .= "<option value=''>".t('Valitse p‰‰kategoria')."</option>";

			while ($srow = mysql_fetch_array($sresult)) {
				$ulos .= "<option value='$srow[selite]' ".$sel[$srow["selite"]].">$srow[selite] $srow[selitetark]</option>";
			}

		$ulos .= "</select>";
		$apu['laji'] = $trow[$i];
	}
		
	$jatko = 0;
		
}

if (mysql_field_name($result, $i) == "liitos") {

	if ($lukitse_avaimeen != "") {
		$apu['ttuoteno'] = $lukitse_avaimeen;
		$ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
		$jatko = 0;
	}
	elseif (isset($alasveto[$i])) {
		$apu['ttuoteno'] = $alasveto[$i];
		$ulos = "<td>".$alasveto[$i]."</td>";
		$jatko = 0;
	}
	else {
		$apu['ttuoteno'] = $trow[$i];
		$ulos = "<td>".$trow[$i]."</td>";
		$jatko = 0;
	}
}

if (mysql_field_name($result, $i) == "puun_tunnus") {

	if ($apu['laji'] != '') {
		$ulos = "<td><a href='{$palvelin2}dynaaminen_puu.php?laji={$apu['laji']}&tee=tuotteet&tuoteno={$apu['ttuoteno']}&puun_tunnus={$trow[$i]}&yhtio={$kukarow[yhtio]}&ttunnus={$tunnus}&toim=tuote&mista_tulin={$toim}&toim2={$toim2}' ";
		$ulos .= "target='main'> ".t('Valitse t‰h‰n puuhun sopivat segmentit')." </a><br />\n";
		$ulos .= "<br /><input type='hidden' name='$nimi' value='{$trow[$i]}' /> {$trow[$i]}</td>\n";
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