<?PHP
// puun_alkiorivi.inc

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (!isset($apu)) {
	$apu = array();
}

//yhtiötä ei näytetä
if (mysql_field_name($result, $i) == "yhtio") {
	$tyyppi = 0;
}

if (mysql_field_name($result, $i) == "laji") {

	$apu['laji'] = $laji;

	$ulos .= "<td>".ucwords($laji)."<input type='hidden' name='$nimi' value='$laji'></td>";
	$jatko = 0;
}

if (mysql_field_name($result, $i) == "liitos") {

	if ($lukitse_avaimeen != "") {
		$apu['liitos'] = $lukitse_avaimeen;
		$ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
	}
	elseif (isset($alasveto[$i])) {
		$apu['liitos'] = $alasveto[$i];
		$ulos = "<td>".$alasveto[$i]."</td>";
	}
	else {
		$apu['liitos'] = $trow[$i];
		$ulos = "<td>".$trow[$i]."</td>";
	}
	$jatko = 0;
}

if (mysql_field_name($result, $i) == "puun_tunnus") {

	if ($apu['laji'] != '') {

		$alkio_nimi = '';

		if (trim($trow[$i]) != '') {
			$query = "	SELECT nimi
						FROM dynaaminen_puu
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND laji 	= '{$apu['laji']}'
						AND tunnus 	= '{$trow[$i]}'";
			$vres = mysql_query($query) or pupe_error($query);
			$nimirow = mysql_fetch_assoc($vres);
			$alkio_nimi = $nimirow['nimi'];
		}

		$query = "	SELECT group_concat(distinct puun_tunnus) nodet
					FROM puun_alkio
					WHERE yhtio = '{$kukarow["yhtio"]}'
					and liitos	= '{$apu["liitos"]}'
					and kieli	= '{$apu["kieli"]}'
					and laji	= '{$apu["laji"]}'";
		$vres = mysql_query($query) or pupe_error($query);
		$vrow = mysql_fetch_array($vres);

		$ulos = "<td><a href='{$palvelin2}dynaaminen_puu.php?toim={$apu['laji']}&tee=valitsesegmentti&mista={$apu['laji']}&liitos=".urlencode($apu['liitos'])."&puun_tunnus={$vrow["nodet"]}&kieli={$apu['kieli']}' target='main'>".t('Valitse segmentit')."</a><br />\n";
		$ulos .= "<br /><input type='hidden' name='$nimi' value='{$trow[$i]}' /> {$trow[$i]} {$alkio_nimi}</td>\n";
	}

	$jatko = 0;
}

if (mysql_field_name($result, $i) == "kieli") {

	$ulos = "<td><select name='$nimi'>";
	$sel  = "";

	$query  = "SHOW columns from sanakirja";
	$fields =  mysql_query($query);

	while ($apurow = mysql_fetch_array($fields)) {
		if (strlen($apurow[0]) == 2) {
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