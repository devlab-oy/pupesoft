<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;
if (isset($_POST['filenimi']) and $_POST['filenimi'] != '') {
	header("Content-type: application/force-download");
	header("Content-Disposition: attachment; filename=hinnasto.zip");
	header("Content-Description: File Transfer");

	$filenimi = '/tmp/' . basename($_POST['filenimi']);
	readfile($filenimi);
    
    // tuhotaan edellinen
	unlink('/tmp/' . basename($filenimi));
	exit;
}

if (! @include('inc/parametrit.inc')) {
	require 'parametrit.inc';
}

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
		if ($where1 != '') {
			$where1 = " osasto in (".$where1.") ";
		}
		
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
		if ($where2 != '') {
			$where2 = " try in (".$where2.") ";
		}
		
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
			$where .= "tuote.muutospvm >= '" . $pvm . "' and ";
		}
	}
    
    if (isset($_POST['tuotemerkki']) and strlen($_POST['tuotemerkki']) > 0) {
        $where .= "tuote.tuotemerkki = '" . mysql_real_escape_string($_POST['tuotemerkki']) . "' and ";
    }
    
	// jos ei olla extranetissa niin otetaan valuuttatiedot yhtiolta
	// maa, valkoodi, ytunnus
	if (empty($kukarow['extranet'])) {
		$laskurowfake = array(
			'valkoodi' => $yhtiorow['valkoodi'],
			'maa'      => $yhtiorow['maa'],
			'ytunnus'  => $yhtiorow['ytunnus'],
		);
	} else {
		// otetaan valuuttatiedot oletus asiakkaalta
		$query = "SELECT maa, valkoodi, ytunnus from asiakas where tunnus='{$kukarow['oletus_asiakas']}' and yhtio ='{$kukarow['yhtio']}'";
		$res = mysql_query($query) or pupe_error($query);

		// käytetään tätä laskurowna
		$laskurowfake = mysql_fetch_assoc($res);
	}

	$query = "SELECT kurssi from valuu where nimi='{$laskurowfake['valkoodi']}' and yhtio = '{$kukarow['yhtio']}'";
	$res = mysql_query($query) or pupe_error($query);
	$kurssi = mysql_fetch_array($res);

	// asetetaan vienti kurssi
	$laskurowfake['vienti_kurssi'] = $kurssi['kurssi'];
	
	if ($kl_hinnastoon != "" and $kukarow['extranet'] == '') {
		$kl_lisa = " ";
	}
	else {
		$kl_lisa = " and tuote.hinnastoon!='E' ";	
	}

	$query = "	SELECT tuote.*, korvaavat.id, sum(tuotepaikat.saldo) saldo
				FROM tuote
				LEFT JOIN korvaavat use index (yhtio_tuoteno) ON (tuote.tuoteno=korvaavat.tuoteno and tuote.yhtio=korvaavat.yhtio)
				LEFT JOIN tuotepaikat ON tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno
				WHERE $where tuote.yhtio='$kukarow[yhtio]' 
				$kl_lisa
				and ((tuote.vienti = '' or tuote.vienti like '%-{$laskurowfake['maa']}%' or tuote.vienti like '%+%')
				and tuote.vienti not like '%+{$laskurowfake['maa']}%')
				GROUP BY tuote.tuoteno, korvaavat.id
				HAVING tuote.status in ('','a') or saldo > 0
				ORDER BY tuote.osasto+0, tuote.try+0";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo t('Yhtään tuotetta ei löytynyt hinnastoon.') . '<br />';
		die();
	}
	
	flush();

	// kirjoitetaan tmp file
	$filenimi = t("hinnasto")."-".date("ymdHis").".txt";

	if (!$fh = fopen("/tmp/" . $filenimi, "w+")) {
		die("filen luonti epäonnistui!");
	}

	// katsotaan mikä hinnastoformaatti

	if (empty($kukarow['extranet'])) {
		$rivifile = 'inc/hinnastorivi' . basename($_POST['hinnasto']) . '.inc';
	}
	else {
		$rivifile = 'hinnastorivi' . basename($_POST['hinnasto']) . '.inc';
	}

	if (file_exists($rivifile)) {
		require $rivifile;
	}
	else {
		die($rivifile . ' ei löydy');
	}

	while ($tuoterow = mysql_fetch_array($result)) {
		
		$ohitus = 0;
		if (!empty($kukarow['extranet'])) {
			$query = "  SELECT selite 
						FROM tuotteen_avainsanat 
						WHERE yhtio = '{$kukarow['yhtio']}' and laji like '%{$laskurowfake['maa']}%' and tuoteno = '{$tuoterow['tuoteno']}'";

			$avresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($avresult) > 0) {
				$avainrow = mysql_fetch_array($avresult);

				$tuoterow['nimitys'] = $avainrow['selite'];			
			}
			
			$query = "SELECT * FROM asiakas where yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[oletus_asiakas]'";
			$asiakastempres = mysql_query($query);
			$asiakastemprow = mysql_fetch_array($asiakastempres);
			
			$temp_laskurowwi = array();							
			$temp_laskurowwi['liitostunnus']	= $asiakastemprow['tunnus'];
			$temp_laskurowwi['ytunnus']			= $asiakastemprow['ytunnus'];
			$temp_laskurowwi['valkoodi']		= $asiakastemprow['valkoodi'];
			$temp_laskurowwi['maa']				= $asiakastemprow['maa'];
			
		
			$hinnat = alehinta($temp_laskurowwi, $tuoterow, 1, '', '', '', "hintaperuste,aleperuste");
			
			if 	($tuoterow["hinnastoon"] == "V" and ($hinnat["hintaperuste"] < 2 or $hinnat["hintaperuste"] > 12) and ($hinnat["aleperuste"] < 5 or $hinnat["aleperuste"] > 8)) {
				$ohitus = 1;
			}
		}
						 		
		// tehdään yksi rivi
		if ($ohitus == 0) {
			$ulos = hinnastorivi($tuoterow, $laskurowfake);
			fwrite($fh, $ulos);
		}		
	}

	fclose($fh);

	//pakataan faili
	$cmd = "/usr/bin/zip -j /tmp/{$kukarow['yhtio']}.{$kukarow['kuka']}.zip /tmp/$filenimi";
	$palautus = exec($cmd);
	
    // poistetaan tmp file
	unlink('/tmp/' . $filenimi);

	$filenimi = "/tmp/{$kukarow['yhtio']}.{$kukarow['kuka']}.zip";
	echo "<br><table><tr><th>".t("Tallenna hinnasto tiedostoon")."</th>";
	echo "<form method='post' action=''>";
	echo "<input type='hidden' name='filenimi' value='$filenimi'>";
	echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr>";
	echo "</form></table>";

	//lopetetaan tähän
	if (! @include('inc/footer.inc')) {
		require 'footer.inc';
	}
	exit;
}


// Käyttöliittymä
echo "<br>".t("Voit valita osaston ja tuoteryhmän joko alasvetovalikosta tai syöttämällä osaston- ja tuoteryhmien numerot käsin").".<br>
		  ".t("Käsin voit syöttää tiedot joko välilyönnillä tai väliviivalla eroteltuna").".<br>
		  ".t("Tarkemmat ohjeet")." <a target='_blank' href='$hreffi'>".t("tässä")."</a>.<br><br>";

echo "<br>";
echo "<table><form method='post' action=''>";

echo "<input type='hidden' name='tee' value='kaikki'>";
echo "<tr><th>".t("Valitse osasto alasevetovalikosta").":</th>";

// tehdään avainsana query
$sresult = avainsana("OSASTO", $kukarow['kieli']);

echo "<td><select name='osasto'>";
echo "<option value='' $sel>".t("Näytä kaikki")."</option>";

while($srow = mysql_fetch_array ($sresult)){
	if($osasto == $srow["selite"]) {
		$sel = "SELECTED";
	}
	else {
		$sel = '';
	}
	echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
}
echo "</select></td><th>".t("tai syötä käsin")."</th><td><input type='text' name='osasto2' value='$osasto' size='15'></td></tr>";

echo "<tr><th>".t("Valitse tuoteryhmä alasevetovalikosta").":</th>";

// tehdään avainsana query
$sresult = avainsana("TRY", $kukarow['kieli']);

echo "<td><select name='try'>";
echo "<option value='' $sel>".t("Näytä kaikki")."</option>";

while($srow = mysql_fetch_array ($sresult)) {
	if($try == $srow["selite"]) {
		$sel = "SELECTED";
	}
	else {
		$sel = '';
	}
	echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
}
echo "</select></td><th>".t("tai syötä käsin")."</th><td><input type='text' name='try2' value='$try' size='15'></td></tr>";

if (empty($kukarow['extranet'])) {
	$query = "	SELECT distinct tuotemerkki
				FROM tuote
				WHERE yhtio='{$kukarow['yhtio']}' and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);
}
else {
	// otetaan valuuttatiedot oletus asiakkaalta
	$query = "SELECT maa from asiakas where tunnus='{$kukarow['oletus_asiakas']}' and yhtio ='{$kukarow['yhtio']}'";
	$res = mysql_query($query) or pupe_error($query);

	// käytetään tätä laskurowna
	$asmaa = mysql_fetch_array($res);
	
	
	$query = "	SELECT distinct tuotemerkki
				FROM tuote
				WHERE yhtio='{$kukarow['yhtio']}' and tuotemerkki != ''
				and ((tuote.vienti = '' or tuote.vienti like '%-{$asmaa['maa']}%' or tuote.vienti like '%+%')
				and tuote.vienti not like '%+{$asmaa['maa']}%')
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);
}

echo "<tr><th>".t('Valitse tuotemerkki alasvetovalikosta')."</th><td><select name='tuotemerkki'>";
echo "<option value=''>".t("Tuotemerkki")."</option>";

while ($srow = mysql_fetch_array($sresult)) {
	if ($tuotemerkki == $srow[0]) $sel = "selected";
	else $sel = "";
	echo "<option value='$srow[0]' $sel>$srow[0]</option>";
}

echo "</select></td></tr>";


if ($kukarow['extranet'] == '') {
	echo "<tr><th>".t("Listaa kaikki tuotteet").":</th>
			<td><input type='checkbox' name='kl_hinnastoon'> (".t("muuten hinnastoon flägi <> E").")</td></tr>";


	echo "<tr><th>".t("Näytä aleryhmän tunnus").":</th>
			<td><input type='checkbox' name='kl_alenimi'> (".t("muuten näytetään aleryhmän nimi").")</td></tr>";
}

echo "<tr>
	<th>" .t('Muutospäivämäärä') . "</th>
	<td>
		<input type='text' name='pp' value='$pp' size='3'>
		<input type='text' name='kk' value='$kk' size='3'>
		<input type='text' name='vv' value='$vv' size='5'>
		" . t('ppkkvvvv') . "
	</td>
	</tr>
	<tr>
	<th>" . t('Hinnastoformaatti') . "</th>
	<td>
		<select name='hinnasto'>
			<option value='futur'>" . t('Futursoft') . "</option>
			<option value='automaster'>" . t('Automaster') . "</option>
			<option value='vienti'>" . t('Vientihinnasto') . "</option>
			<option value='tab'>" . t('Tab eroteltu') . "</option>
		</select>
	</td>
	</tr>
</table>";

echo "<br><input type='submit' value='".t("Lähetä")."'></form>";

if (! @include('inc/footer.inc')) {
	require 'footer.inc';
}
?>