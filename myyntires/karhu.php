<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Karhu")."</font><hr>";

//vain n‰in monta p‰iv‰‰ sitten karhutut
//laskut huomioidaan n‰kym‰sss‰
$kpvm_aikaa = 10;

//vain n‰in monta p‰iv‰‰ sitten er‰‰ntyneet
//laskut huomioidaan n‰kym‰sss‰
$lpvm_aikaa = 7;

if ($kukarow["kirjoitin"] == 0) {
	echo "<font class='error'>".t("Sinulla pit‰‰ olla henkilˆkohtainen tulostin valittuna, ett‰ voit tulostaa karhuja").".</font><br>";
	$tee = "";
}

if (strlen($yhtiorow['karhuviesti1']) < 6) {
    echo "<font class='error'>".t("Yhtiˆll‰ ei ole yht‰‰n karhuviesti‰ tallennettuna. Ei voida karhuta").".</font><br>";
    $tee = '';
}

if ($tee == 'LAHETA') {
	// kirjeen l‰hetyksen status
	$ekarhu_success = true;

	try {
		
		// koitetaan l‰hett‰‰ eKirje sek‰ tulostaa
		require ('paperikarhu.php');

	} catch (Exception $e) {
		$ekarhu_success = false;
		echo "<font class='error'>Ei voitu l‰hett‰‰ karhua eKirjeen‰, karhuaminen peruttiin. Virhe: " . $e->getMessage() . "</font>";
	}
	
	// poistetaan karhuttu vain jos karhun l‰hetys onnistui,
	// muuten voidaan kokeilla samaa uudestaan!!!!!
	if ($ekarhu_success) {
		array_shift($karhuttavat);
	}
	
	// jatketaan karhuamista
	$tee = "KARHUA";
	
}

// ohitetaanko asiakas?
if ($tee == 'OHITA') {
	array_shift($karhuttavat);
	
	$tee = "KARHUA";
}

if ($tee == "ALOITAKARHUAMINEN") {

	$maksuehtolista = "";
	$ktunnus = (int) $ktunnus;

	$maa_lisa = "";
	if ($mehto_maa != "") {
		$maa_lisa = "and (sallitut_maat like '%$mehto_maa%' or sallitut_maat = '') ";
	}

	if ($ktunnus != 0) {
		$query = "	SELECT *
					FROM factoring
					WHERE yhtio = '$kukarow[yhtio]' and tunnus=$ktunnus";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$factoringrow = mysql_fetch_array($result);
			$query = "SELECT GROUP_CONCAT(tunnus) karhuttavat
						FROM maksuehto
						WHERE yhtio = '$kukarow[yhtio]' and factoring = '$factoringrow[factoringyhtio]' $maa_lisa";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$maksuehdotrow = mysql_fetch_array($result);
				$maksuehtolista = " and lasku.maksuehto in ($maksuehdotrow[karhuttavat]) and lasku.valkoodi = '$factoringrow[valkoodi]'";
			}
		}
		else {
			echo "Valittu factoringsopimus ei lˆydy";
			exit;
		}
	}
	else {
		$query = "SELECT GROUP_CONCAT(tunnus) karhuttavat
						FROM maksuehto
						WHERE yhtio = '$kukarow[yhtio]' and factoring = '' $maa_lisa";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$maksuehdotrow = mysql_fetch_array($result);
			$maksuehtolista = " and lasku.maksuehto in ($maksuehdotrow[karhuttavat])";
		}
	}

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

	$query = "	SELECT asiakas.ytunnus, GROUP_CONCAT(distinct lasku.tunnus) karhuttavat, sum(summa) karhuttava_summa
				FROM lasku
				JOIN (	SELECT lasku.tunnus,
						maksuehto.jv,
						max(karhukierros.pvm) kpvm,
						count(distinct karhu_lasku.ktunnus) karhuttu
						FROM lasku use index (yhtio_tila_mapvm)
						LEFT JOIN karhu_lasku on (lasku.tunnus=karhu_lasku.ltunnus)
						LEFT JOIN karhukierros on (karhukierros.tunnus=karhu_lasku.ktunnus)
						LEFT JOIN maksuehto on (maksuehto.yhtio=lasku.yhtio and maksuehto.tunnus=lasku.maksuehto)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'U'
						and lasku.mapvm	= '0000-00-00'
						and (lasku.erpcm < date_sub(now(), interval $lpvm_aikaa day) or lasku.summa < 0)
						and lasku.summa	!= 0
						$maksuehtolista
						group by lasku.tunnus
						HAVING (kpvm is null or kpvm < date_sub(now(), interval $kpvm_aikaa day))) as laskut
				JOIN asiakas ON lasku.yhtio=asiakas.yhtio and lasku.liitostunnus=asiakas.tunnus
				WHERE lasku.tunnus = laskut.tunnus
				$konslisa
				$asiakaslisa
				GROUP BY asiakas.ytunnus, asiakas.nimi, asiakas.nimitark, asiakas.osoite, asiakas.postino, asiakas.postitp
				HAVING karhuttava_summa > 0
				ORDER BY asiakas.ytunnus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
		$karhuttavat = array();

		while($karhuttavarow = mysql_fetch_array($result)) {
			$karhuttavat[] = $karhuttavarow["karhuttavat"];
		}
		$tee = "KARHUA";
	}
	else {
		echo "<font class='message'>".t("Ei karhuttavia asiakkaita")."!</font><br><br>";
		$tee = "";
	}
}

if ($tee == 'KARHUA' and $karhuttavat[0] == "") {
	echo "<font class='message'>".t("Kaikki asiakkaat karhuttu")."!</font><br><br>";
	$tee = "";
}

if ($tee == 'KARHUA')  {

	$query = "	SELECT lasku.liitostunnus,
				lasku.summa-lasku.saldo_maksettu as summa,
				lasku.erpcm, lasku.laskunro, lasku.tapvm, lasku.tunnus,
				TO_DAYS(now())-TO_DAYS(lasku.erpcm) as ika,
				max(karhukierros.pvm) as kpvm,
				count(distinct karhu_lasku.ktunnus) as karhuttu,
				if(maksuehto.jv!='', '".t("J‰lkivaatimus")."' ,'') jv
				FROM lasku
				LEFT JOIN karhu_lasku on (lasku.tunnus=karhu_lasku.ltunnus)
				LEFT JOIN karhukierros on (karhukierros.tunnus=karhu_lasku.ktunnus)
				LEFT JOIN maksuehto on (maksuehto.yhtio=lasku.yhtio and maksuehto.tunnus=lasku.maksuehto)
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus in ($karhuttavat[0])
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
	
	echo "<tr><th>". t('Karhuviesti') ."</th><td>";
	
	$max = 0;
	while ($lasku = mysql_fetch_array($result)) {
		if ($lasku['karhuttu'] > $max) {
			$max = $lasku['karhuttu'];
		}
	}
	
	$sel1 = $sel2 = $sel3 = '';
	if ($max >= 3) {
		$sel3 = 'selected';
	} elseif ($max == 2) {
		$sel2 = 'selected';
	} else {
		$sel1 = 'selected';
	}
	
	if (strlen(trim($yhtiorow['karhuviesti2'])) == 0) {
		$disabled2 = 'disabled';
	}
	
	if (strlen(trim($yhtiorow['karhuviesti3'])) == 0) {
		$disabled3 = 'disabled';
	}
	
	mysql_data_seek($result,0);
	
	?>
	<form name='lahetaformi' action='' method='post'>
	<select name='karhuviesti'>
	    <option <?php echo $sel1 ?> value=1><?php echo t('Karhuviesti 1') ?></option>
	    <option <?php echo $sel2 . ' ' . $disabled2 ?> value=2><?php echo t('Karhuviesti 2') ?></option>
	    <option <?php echo $sel3 . ' ' . $disabled3 ?> value=3><?php echo t('Karhuviesti 3') ?></option>
	</select>
	</td>
	</tr>
	</table>
	        
	<?php

	echo "</td><td valign='top' class='back'>";

	echo "<table>";
	echo "<tr><th>".t("Edellinen karhu v‰h").".</th><td>$kpvm_aikaa ".t("p‰iv‰‰ sitten").".</td></tr>";
	echo "<tr><th>".t("Er‰p‰iv‰st‰ v‰h").".</th><td>$lpvm_aikaa ".t("p‰iv‰‰").".</td></tr>";
	echo "<tr><td class='back'></td><td class='back'><br></td></tr>";
	echo "<tr><td class='back'></td><td class='back'><br></td></tr>";

	$query = "	SELECT GROUP_CONCAT(distinct liitostunnus) liitokset
				FROM lasku
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus in ($karhuttavat[0])";
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
	
	if (isset($ekirje_config) && is_array($ekirje_config)) {
		$submit_text = 'L‰het‰ eKirje';
	} else {
		$submit_text = 'Tulosta paperille';
	}
	
	echo "<table>";
	echo "<tr>";
	echo "<td class='back'><input type='button' onclick='javascript:document.lahetaformi.submit();' value='".t('Tulosta paperille')."'></td>";

	if (isset($ekirje_config) and is_array($ekirje_config)) {
		echo "<td class='back'><input type='button' onclick='document.lahetaformi.ekirje_laheta.click();' value='".t('L‰het‰ eKirje')."'></td>";
	}
	
	echo "<td class='back'><input type='button' onclick='javascript:document.ohitaformi.submit();' value='".t("Ohita")."'></td>";
	echo "</tr>";
	echo "</table><br>";

	echo "<table><tr>";
	echo "<th>".t("Laskunpvm")."</th>";
	echo "<th>".t("Laskunro")."</th>";
	echo "<th>".t("Summa")."</th>";
	echo "<th>".t("Er‰p‰iv‰")."</th>";
	echo "<th>".t("Ik‰ p‰iv‰‰")."</th>";
	echo "<th>".t("Karhuttu")."</th>";
	echo "<th>".t("Viimeisin karhu")."</th>";
	echo "<th>".t("Lasku karhutaan")."</th></tr>";
	$summmmma = 0;

	while ($lasku = mysql_fetch_array($result)) {
		echo "<tr class='aktiivi'><td>";
		if ($kukarow['taso'] < 2) {
			echo tv1dateconv($lasku["tapvm"]);
		}
		else {
			echo "<a href = '../muutosite.php?tee=E&tunnus=$lasku[tunnus]'>".tv1dateconv($lasku["tapvm"])."</a>";
		}
		echo "</td><td>";
		echo "<a href = '../tilauskasittely/tulostakopio.php?toim=LASKU&laskunro=$lasku[laskunro]'>$lasku[laskunro]</a>";
		echo "</td><td>";
		echo $lasku["summa"];
		echo "</td><td>";
		echo tv1dateconv($lasku["erpcm"]);
		echo "</td><td>";
		echo $lasku["ika"];
		echo "</td><td>";
		echo $lasku["karhuttu"];
		echo "</td><td>";
		
		if ($lasku["kpvm"] != '')
			echo tv1dateconv($lasku["kpvm"]);
		
		echo "</td>";

		if ($lasku["jv"] == "") {
			$chk = "checked";
		}
		else {
			$chk = "";
		}
		echo "<td><input type='checkbox' name = 'lasku_tunnus[]' value = '$lasku[tunnus]' $chk> $lasku[jv]</td></tr>\n";
		
		$summmmma += $lasku["summa"];
	}

	$summmmma -= $kaatosumma;

	echo "<th colspan='2'>".t("Karhuttavaa yhteens‰")."</th>";
	echo "<th>$summmmma</th>";
	echo "<td class='back'></td></tr>";

	echo "</table><br>";

	echo "<table>";
	echo "<tr>";

	echo "<input name='tee' type='hidden' value='LAHETA'>";
	echo "<input name='yhteyshenkilo' type='hidden' value='$yhteyshenkilo'>";
	echo "<input name='ktunnus' type='hidden' value='$ktunnus'>";

	foreach($karhuttavat as $tunnukset) {
		echo "\n<input type='hidden' name='karhuttavat[]' value='$tunnukset'>";
	}

	echo "<td class='back'><input name='$kentta' type='submit' value='".t('Tulosta paperille')."'>";
	
	// voiko l‰hett‰‰ eKirjeen?
	if (isset($ekirje_config) and is_array($ekirje_config)) {
		echo "<input type='submit' name='ekirje_laheta' value='" . t('L‰het‰ eKirje') . "'>";
	}
	
	echo "</td></form>";

	echo "<form name='ohitaformi' action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='tee' value='KARHUA'>";
	echo "<input name='yhteyshenkilo' type='hidden' value='$yhteyshenkilo'>";
	echo "<input name='ktunnus' type='hidden' value='$ktunnus'>";

	foreach($karhuttavat as $tunnukset) {
		echo "\n<input type='hidden' name='karhuttavat[]' value='$tunnukset'>";
	}

	echo "<td class='back'><input type='hidden' name='tee' value='OHITA'>
		<input type='submit' value='".t("Ohita")."'></td>";
	echo "</form></tr>";
	echo "</table>";

}

if ($tee == "") {

	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='tee' value='ALOITAKARHUAMINEN'>";
	echo t("Syˆt‰ ytunnus jos haluat karhuta tietty‰ asiakasta").".<br>".t("J‰t‰ kentt‰ tyhj‰ksi jos haluat aloittaa karhuamisen ensimm‰isest‰ asiakkaasta").".<br><br>";

	echo "<table>";

	$apuqu = "	select concat(nimitys,' ', valkoodi, ' (',sopimusnumero,')') nimi, tunnus
				from factoring
				where yhtio = '$kukarow[yhtio]'";
	$meapu = mysql_query($apuqu) or pupe_error($apuqu);

	if (mysql_num_rows($meapu) > 0) {

		echo "<tr><th>".t("Karhujen tyyppi").":</th>";
		echo "<td><select name='ktunnus'>";
		echo "<option value='0'>".t("Ei factoroidut")."</option>";

		while ($row = mysql_fetch_array($meapu)) {
			echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
		}

		echo "</select></td></tr>";
	}
	else {
		echo "<input type='hidden' name='ktunnus' value='0'>";
	}

	$apuqu = "	select distinct sallitut_maat
				from maksuehto
				where yhtio = '$kukarow[yhtio]' and sallitut_maat != ''";
	$meapu = mysql_query($apuqu) or pupe_error($apuqu);

	if (mysql_num_rows($meapu) > 0) {

		$maa_lisa = " and koodi in (";

		while ($row = mysql_fetch_array($meapu)) {
			$maat = split('[, ]', $row["sallitut_maat"]);
			foreach ($maat as $maa) {
				$maa_lisa .= "'$maa',";
			}
		}

		$maa_lisa = substr($maa_lisa, 0, -1);
		$maa_lisa .= ")";

		echo "<tr><th>".t("Karhua vain maksuehtoja maasta").": </th>";
		echo "<td><select name='mehto_maa'>";
		echo "<option value=''>".t("Ei maavalintaa")."</option>";

		$query = "	SELECT distinct koodi, nimi
					FROM maat
					where nimi != '' $maa_lisa
					ORDER BY koodi";
		$meapu = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($meapu)) {
			$sel = '';
			if ($row["koodi"] == $mehto_maa) {
				$sel = 'selected';
			}
			echo "<option value='$row[koodi]' $sel>$row[nimi]</option>";
		}
		echo "</select></td>";
		echo "</tr>";
	}
	else {
		echo "<input type='hidden' name='mehto_maa' value=''>";
	}

	$apuqu = "	select kuka, nimi, puhno, eposti, tunnus
				from kuka
				where yhtio='$kukarow[yhtio]' and nimi!='' and puhno!='' and eposti!='' and extranet=''";
	$meapu = mysql_query($apuqu) or pupe_error($apuqu);

	echo "<tr><th>".t("Yhteyshenkilˆ").":</th>";
	echo "<td><select name='yhteyshenkilo'>";

	while ($row = mysql_fetch_array($meapu)) {
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
	echo "<td class='back'><input type='submit' value='".t("Aloita")."'></td>";
	echo "</form></tr>";
	echo "</table>";
}

require ("../inc/footer.inc");

?>
