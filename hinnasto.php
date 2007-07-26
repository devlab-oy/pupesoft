<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;
if (isset($_POST['filenimi']) and $_POST['filenimi'] != '') {
	header("Content-type: application/force-download");
	header("Content-Disposition: attachment; filename=hinnasto.zip");
	header("Content-Description: File Transfer");
	
	$filenimi = '/tmp/' . basename($_POST['filenimi']);
	readfile($filenimi);

	system("rm -f " . '/tmp/' . basename($filenimi));
	exit;
}

require('inc/parametrit.inc');

echo "<font class='head'>".t("Hinnastoajo").":</font><hr>";

if ($tee != '') {
	$where1 = '';
	$where2 = '';

	if ($osasto != '') {
		$where1 = " osasto = '$osasto' ";
	}
	elseif ($osasto2 != '') {
		$osastot = split(" ",$osasto2);

		for($i = 0; $i < sizeof($osastot); $i++) {
			$osastot[$i] = trim($osastot[$i]);

			if ($osastot[$i] != '') {
				if (strpos($osastot[$i],"-")) {

					$osastot2 = split("-",$osastot[$i]);

					for($ia = $osastot2[0]; $ia<= $osastot2[1]; $ia++) {
						$where1 .= "'".$ia."',";
					}
				}
				else {
					$where1 .= "'".$osastot[$i]."',";
				}
			}
		}
		$where1 = substr($where1,0,-1);
		$where1 = " osasto in (".$where1.") ";
    }


	if ($try != '') {
		$where2 = " try ='$try' ";
	}		
	elseif ($try2 != '') {
		$tryt = split(" ",$try2);

		for($i = 0; $i < sizeof($tryt); $i++) {
			$tryt[$i] = trim($tryt[$i]);

			if ($tryt[$i] != '') {
				if (strpos($tryt[$i],"-")) {
					$tryt2 = split("-",$tryt[$i]);
					for($ia = $tryt2[0]; $ia<= $tryt2[1]; $ia++) {
						$where2 .= "'".$ia."',";
					}
				}
				else {
					$where2 .= "'".$tryt[$i]."',";
				}
			}
		}
		$where2 = substr($where2,0,-1);
		$where2 = " try in (".$where2.") ";
	}

	if (strlen($where1) > 0) {
		$where = $where1." and ";
	}
	if (strlen($where2) > 0) {
		$where = $where2." and ";
	}
	if (strlen($where2) > 0 && strlen($where1) > 0) {
		$where = "(". $where1." or ".$where2.")  and ";
	}
	
	if (isset($_POST['pp']) && isset($_POST['kk']) && isset($_POST['vv'])) {
		if (strlen(trim($_POST['vv'])) > 0 and strlen(trim($_POST['kk'])) > 0 and strlen(trim($_POST['pp'])) > 0) {
			$pvm = mysql_real_escape_string("{$_POST['vv']}-{$_POST['kk']}-{$_POST['pp']}");
			$where .= "muutospvm >= '" . $pvm . "' and ";
		}
	}
	
	$query = "	SELECT tuote.tuoteno
				FROM tuote
				WHERE $where tuote.yhtio='$kukarow[yhtio]' and tuote.status in ('','a') and hinnastoon != 'E'
				ORDER BY tuote.osasto+0, tuote.try+0";
	$result = mysql_query($query) or pupe_error($query);
	flush();

	// kirjoitetaan tmp file
	$filenimi = "$kukarow[yhtio]-".t("hindisk")."-".md5(uniqid(rand(),true)).".txt";

	if (!$fh = fopen("/tmp/" . $filenimi, "w+")) {
		die("filen luonti epäonnistui!");
	}

	while ($tuoterow = mysql_fetch_array($result)) {
		
		$query = "	SELECT tuote.*, korvaavat.id
					FROM tuote
					LEFT JOIN korvaavat use index (yhtio_tuoteno) ON tuote.tuoteno=korvaavat.tuoteno and tuote.yhtio=korvaavat.yhtio
					WHERE tuote.yhtio='{$kukarow['yhtio']}' and tuote.tuoteno='{$tuoterow['tuoteno']}'";

		$trresult = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($trresult);
		
		// katsotaan mikä hinnastoformaatti
		$rivifile = 'inc/hinnastorivi' . basename($_POST['hinnasto']) . '.inc';
		if (file_exists($rivifile)) {
			require $rivifile;
		}
		
		// tehdään yksi rivi
		$ulos = hinnastorivi($row);
		
		fwrite($fh, $ulos);
	}
	
	fclose($fh);
	
	//pakataan faili
	$cmd = "cd /tmp/;/usr/bin/zip {$kukarow['yhtio']}.{$kukarow['kuka']}.zip $filenimi";
	$palautus = exec($cmd);
    
    // poistetaan tmp file
	system("rm -f " . '/tmp/' . $filenimi);

	$filenimi = "/tmp/{$kukarow['yhtio']}.{$kukarow['kuka']}.zip";
	echo "<br><table><tr><th>".t("Tallenna hinnasto tiedostoon")."</th>";
	echo "<form method='post' action=''>";
	echo "<input type='hidden' name='filenimi' value='$filenimi'>";
	echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr>";
	echo "</form></table>";

	//lopetetaan tähän
	require ("inc/footer.inc");
	exit;
}


// Käyttöliittymä
echo "<br>".t("Voit valita osaston ja tuoteryhmän joko alasvetovalikosta tai syöttämällä osaston- ja tuoteryhmien numerot käsin").".<br> ".t("Käsin voit syöttää tiedot joko välilyönnillä tai väliviivalla eroteltuna").".<br><br>";
echo "<br>";
echo "<table><form method='post' action=''>";

echo "<input type='hidden' name='tee' value='kaikki'>";
echo "<tr><th>".t("Valitse osasto alasevetovalikosta").":</th>";

$query = "	SELECT distinct avainsana.selite, ".avain('select')."
			FROM avainsana
			".avain('join','OSASTO_')."
			WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO'
			ORDER BY avainsana.selite+0";
$sresult = mysql_query($query) or pupe_error($query);

echo "<td><select name='osasto'>";
echo "<option value='' $sel>".t("Näytä kaikki")."</option>";

while($srow = mysql_fetch_array ($sresult)){
	if($osasto == $srow[0]) {
		$sel = "SELECTED";
	}
	else {
		$sel = '';
	}
	echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
}
echo "</select></td><th>".t("tai syötä käsin")."</th><td><input type='text' name='osasto2' value='$osasto' size='15'></td></tr>";

echo "<tr><th>".t("Valitse tuoteryhmä alasevetovalikosta").":</th>";

$query = "	SELECT distinct avainsana.selite, ".avain('select')."
			FROM avainsana
			".avain('join','TRY_')."
			WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY'
			ORDER BY avainsana.selite+0";
$sresult = mysql_query($query) or pupe_error($query);

echo "<td><select name='try'>";
echo "<option value='' $sel>".t("Näytä kaikki")."</option>";

while($srow = mysql_fetch_array ($sresult)) {
	if($try == $srow[0]) {
		$sel = "SELECTED";
	}
	else {
		$sel = '';
	}
	echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
}
echo "</select></td><th>tai syötä käsin</th><td><input type='text' name='try2' value='$try' size='15'></td></tr>
	<tr>
	<th>" .t('Muutospäivämäärä') . "</th>
	<td>
		<input type='text' name='pp' value='$pp' size='3'>
		<input type='text' name='kk' value='$kk' size='3'>
		<input type='text' name='vv' value='$vv' size='5'>
	</td>
	</tr>
	<tr>
	<th>" . t('Hinnastoformaatti') . "</th>
	<td>
		<select name='hinnasto'>
			<option value='futur'>" . t('Futursoft') . "</option>
			<option value='hindisk'>" . t('Hindisk') . "</option>
		</select>
	</td>
	</tr>
</table>";

echo "<br><input type='submit' value='".t("Lähetä")."'></form>";

require ("inc/footer.inc");
?>