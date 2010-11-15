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
	
	if (isset ($toim2) and strtolower(trim($toim2)) == 'tuote' OR isset ($toim2) and strtolower(trim($toim2)) == 'asiakas' ) {
		
		while ($srow = mysql_fetch_array($sresult)) {
				
			if (strtolower(trim($toim2)) == strtolower(trim($srow['selite']))) {
				$ulos = "<td>".$srow['selite']."<input type='hidden' value='$srow[selite]' name='$nimi' />";
				$apu['laji'] = $srow['selite'];
			}
			
		}

		mysql_data_seek($sresult,0);
	}
	else {

		if (isset($laji) and trim($laji) != '') {
			$ulos .= "<td>".ucwords($laji)."<input type='hidden' value='$laji' name='$nimi' /></td>";
		}
		else {
			$sel = array($trow[$i] => "SELECTED");

			$ulos = "<td><select name='$nimi' onchange='submit();'>";

			$ulos .= "<option value=''>".t('Valitse p‰‰kategoria')."</option>";

			while ($srow = mysql_fetch_array($sresult)) {
				$ulos .= "<option value='$srow[selite]' ".$sel[$srow["selite"]].">$srow[selite] $srow[selitetark]</option>";
			}

			$ulos .= "</select>";
		}

		$apu['laji'] = (isset($laji) and trim($laji) != '') ? $laji : $trow[$i];
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

		$alkio_nimi = '';

		if (trim($trow[$i]) != '') {
			$query = "SELECT nimi FROM dynaaminen_puu WHERE yhtio = '{$kukarow['yhtio']}' AND laji = '{$apu['laji']}' AND tunnus = '{$trow[$i]}'";
			$nimi_fetch_res = mysql_query($query) or pupe_error($query);
			$nimi_fetch_row = mysql_fetch_assoc($nimi_fetch_res);
			$alkio_nimi = $nimi_fetch_row['nimi'];
		}

		if (strtolower($apu['laji']) == strtolower('asiakas')) {
			$ulos = "<td><a href='{$palvelin2}dynaaminen_puu.php?laji={$apu['laji']}&tee=tuotteet&tuoteno=".trim($apu['ttuoteno'])."&puun_tunnus={$trow[$i]}&yhtio={$kukarow[yhtio]}&ttunnus={$tunnus}&toim=asiakas&mista_tulin={$toim}&toim2={$toim2}&kieli={$apu['kieli']}' ";
			$ulos .= "target='main'> ".t('Valitse t‰h‰n puuhun sopivat asiakassegmentit')." </a><br />\n";
			$ulos .= "<br /><input type='hidden' name='$nimi' value='{$trow[$i]}' /> {$trow[$i]} {$alkio_nimi}</td>\n";
			
		}
		else {
			$ulos = "<td><a href='{$palvelin2}dynaaminen_puu.php?laji={$apu['laji']}&tee=tuotteet&tuoteno=".trim($apu['ttuoteno'])."&puun_tunnus={$trow[$i]}&yhtio={$kukarow[yhtio]}&ttunnus={$tunnus}&toim=tuote&mista_tulin={$toim}&toim2={$toim2}&kieli={$apu['kieli']}' ";
			$ulos .= "target='main'> ".t('Valitse t‰h‰n puuhun sopivat tuotesegmentit')." </a><br />\n";
			$ulos .= "<br /><input type='hidden' name='$nimi' value='{$trow[$i]}' /> {$trow[$i]} {$alkio_nimi}</td>\n";
		}
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
				$apu['kieli'] = $apurow[0];
			}
			elseif ($trow[$i] == "" and $apurow[0] == $yhtiorow["kieli"]) {
				$sel = "SELECTED";
				$apu['kieli'] = $apurow[0];
			}
			$ulos .= "<option value='$apurow[0]' $sel>$apurow[0] - ".maa($apurow[0])."</option>";
		}
	}

	$ulos .= "</select></td>";

	$jatko = 0;
}

?>