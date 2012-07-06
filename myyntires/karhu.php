<?php

if (strpos($_SERVER['SCRIPT_NAME'], "karhu.php")  !== FALSE) {
	require ("../inc/parametrit.inc");
}

echo "<font class='head'>".t("Karhu")."</font><hr>";

// vain n‰in monta p‰iv‰‰ sitten karhutut
// laskut huomioidaan n‰kym‰sss‰
if (!isset($kpvm_aikaa)) {
	$kpvm_aikaa = 10;
}

// vain n‰in monta p‰iv‰‰ sitten er‰‰ntyneet
// laskut huomioidaan n‰kym‰sss‰
if (!isset($lpvm_aikaa)) {
	$lpvm_aikaa = 7;
}

if ($kukarow["kirjoitin"] == 0) {
	echo "<font class='error'>".t("Sinulla pit‰‰ olla henkilˆkohtainen tulostin valittuna, ett‰ voit tulostaa karhuja").".</font><br>";
	$tee = "";
}

$query = "SELECT tunnus from avainsana where laji = 'KARHUVIESTI' and yhtio ='$yhtiorow[yhtio]'";
$res = pupe_query($query);

if (mysql_num_rows($res) == 0) {
    echo "<font class='error'>".t("Yhtiˆll‰ ei ole yht‰‰n karhuviesti‰ tallennettuna. Ei voida karhuta").".</font><br>";
    $tee = '';
}

if ($tee == 'LAHETA') {
	// kirjeen l‰hetyksen status
	$ekarhu_success = true;

	if (!empty($_POST['lasku_tunnus'])) {
		try {
			// koitetaan l‰hett‰‰ eKirje sek‰ tulostaa
			require ('paperikarhu.php');
		}
		catch (Exception $e) {
			$ekarhu_success = false;
			echo "<font class='error'>".t("Ei voitu l‰hett‰‰ karhua eKirjeen‰, karhuaminen peruttiin").". ".t("VIRHE").": " . $e->getMessage() . "</font>";
		}
	}
	else {
		echo "<font class='error'>".t("Et valinnut yht‰‰n laskua").".</font>";
		$ekarhu_success = false;
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

	if ($ktunnus != 0) {
		$query = "	SELECT *
					FROM factoring
					WHERE yhtio = '$kukarow[yhtio]' and tunnus=$ktunnus";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$factoringrow = mysql_fetch_assoc($result);
			$query = "	SELECT GROUP_CONCAT(tunnus) karhuttavat
						FROM maksuehto
						WHERE yhtio = '$kukarow[yhtio]' and factoring = '$factoringrow[factoringyhtio]'";
			$result = pupe_query($query);

			$maksuehdotrow = mysql_fetch_assoc($result);

			if ($maksuehdotrow["karhuttavat"] != '') {
				$maksuehtolista = " and lasku.maksuehto in ($maksuehdotrow[karhuttavat]) and lasku.valkoodi = '$factoringrow[valkoodi]'";
			}
			else {
				echo t("Ei karhuttavaa");
				exit;
			}
		}
		else {
			echo t("Valittu factoringsopimus ei lˆydy");
			exit;
		}
	}
	else {
		$query = "	SELECT GROUP_CONCAT(tunnus) karhuttavat
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]' and factoring = ''";
		$result = pupe_query($query);

		$maksuehdotrow = mysql_fetch_assoc($result);

		if ($maksuehdotrow["karhuttavat"] != '') {
			$maksuehtolista = " and lasku.maksuehto in ($maksuehdotrow[karhuttavat])";
		} else {
			echo "Ei karhuttavaa";
			exit;
		}
	}

	$query = "	SELECT GROUP_CONCAT(distinct concat('\'',ovttunnus,'\'')) konsrernyhtiot
				FROM yhtio
				WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
	$result = pupe_query($query);

	$konslisa = "";

	if (mysql_num_rows($result) > 0) {
		$konsrow = mysql_fetch_assoc($result);
		$konslisa = " and lasku.ovttunnus not in ($konsrow[konsrernyhtiot])";
	}

	$asiakaslisa = "";

	if ($syot_ytunnus != '') {
		$asiakaslisa = " and asiakas.ytunnus >= '$syot_ytunnus' ";
	}
	elseif (isset($_POST['ytunnus_spec']) and ! empty($_POST['ytunnus_spec'])) {
		$asiakaslisa = sprintf(" and asiakas.ytunnus = '%s' ", mysql_real_escape_string($_POST['ytunnus_spec']));
	}

	$maa_lisa = "";
	if ($lasku_maa != "") {
		$maa_lisa = "and lasku.maa = '$lasku_maa'";
	}



	$query = "	SELECT asiakas.ytunnus,
				IF(asiakas.laskutus_nimi != '' and (asiakas.maksukehotuksen_osoitetiedot = 'B' or ('{$yhtiorow['maksukehotuksen_osoitetiedot']}' = 'K' and asiakas.maksukehotuksen_osoitetiedot = '')),
						concat(asiakas.laskutus_nimi, asiakas.laskutus_nimitark, asiakas.laskutus_osoite, asiakas.laskutus_postino, asiakas.laskutus_postitp),
						concat(asiakas.nimi, asiakas.nimitark, asiakas.osoite, asiakas.postino, asiakas.postitp)) asiakastiedot,
				group_concat(distinct lasku.tunnus) karhuttavat,
				sum(lasku.summa-lasku.saldo_maksettu) karhuttava_summa
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
						$maa_lisa
						GROUP BY lasku.tunnus
						HAVING (kpvm is null or kpvm < date_sub(now(), interval $kpvm_aikaa day))) as laskut
				JOIN asiakas ON lasku.yhtio=asiakas.yhtio and lasku.liitostunnus=asiakas.tunnus
				WHERE lasku.tunnus = laskut.tunnus
				$konslisa
				$asiakaslisa
				GROUP BY asiakas.ytunnus, asiakastiedot
				HAVING karhuttava_summa > 0
				ORDER BY asiakas.ytunnus";
	$result = pupe_query($query);

	if (mysql_num_rows($result) > 0) {
		$karhuttavat = array();
		$karhuttavat_asiakkaat = array();
		unset($pdf);

		while ($karhuttavarow = mysql_fetch_assoc($result)) {
			$karhuttavat[] = $karhuttavarow["karhuttavat"];
			$karhuttavat_asiakkaat[] = "'".$karhuttavarow["ytunnus"]."'";
		}

		if ($karhuakaikki != "") {
			$tee = "KARHUAKAIKKI";
		}
		else {
			$tee = "KARHUA";
		}
	}
	else {
		echo "<font class='message'>".t("Ei karhuttavia asiakkaita")."!</font><br><br>";
		$tee = "";
	}
}

if ($tee == "KARHUAKAIKKI") {

	foreach ($karhuttavat as $lasku_tunnus) {
		try {
			// koitetaan l‰hett‰‰ eKirje sek‰ tulostaa
			require ('paperikarhu.php');
		}
		catch (Exception $e) {
			$ekarhu_success = false;
			echo "<font class='error'>Ei voitu l‰hett‰‰ karhua eKirjeen‰, karhuaminen peruttiin. Virhe: " . $e->getMessage() . "</font>";
		}

		unset($karhuviesti);
	}

	unset($karhuttavat);
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
				sum(if(karhukierros.tyyppi='T', 1, 0)) tratattu,
				if(maksuehto.jv!='', '".t("J‰lkivaatimus")."' ,'') jv, lasku.yhtio_toimipaikka, lasku.valkoodi,
				concat_ws(' ', lasku.viesti, lasku.comments) comments
				FROM lasku
				LEFT JOIN karhu_lasku on (lasku.tunnus=karhu_lasku.ltunnus)
				LEFT JOIN karhukierros on (karhukierros.tunnus=karhu_lasku.ktunnus)
				LEFT JOIN maksuehto on (maksuehto.yhtio=lasku.yhtio and maksuehto.tunnus=lasku.maksuehto)
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus in ($karhuttavat[0])
				GROUP BY lasku.tunnus
				ORDER BY lasku.erpcm";
	$result = pupe_query($query);

	//otetaan asiakastiedot ekalta laskulta
	$asiakastiedot = mysql_fetch_assoc($result);

	$query = "	SELECT *,
				IF(laskutus_nimi != '' and (maksukehotuksen_osoitetiedot = 'B' or ('{$yhtiorow['maksukehotuksen_osoitetiedot']}' = 'K' and maksukehotuksen_osoitetiedot = '')), laskutus_nimi, nimi) nimi,
				IF(laskutus_nimi != '' and (maksukehotuksen_osoitetiedot = 'B' or ('{$yhtiorow['maksukehotuksen_osoitetiedot']}' = 'K' and maksukehotuksen_osoitetiedot = '')), laskutus_nimitark, nimitark) nimitark,
				IF(laskutus_nimi != '' and (maksukehotuksen_osoitetiedot = 'B' or ('{$yhtiorow['maksukehotuksen_osoitetiedot']}' = 'K' and maksukehotuksen_osoitetiedot = '')), laskutus_osoite, osoite) osoite,
				IF(laskutus_nimi != '' and (maksukehotuksen_osoitetiedot = 'B' or ('{$yhtiorow['maksukehotuksen_osoitetiedot']}' = 'K' and maksukehotuksen_osoitetiedot = '')), laskutus_postino, postino) postino,
				IF(laskutus_nimi != '' and (maksukehotuksen_osoitetiedot = 'B' or ('{$yhtiorow['maksukehotuksen_osoitetiedot']}' = 'K' and maksukehotuksen_osoitetiedot = '')), laskutus_postitp, postitp) postitp
				FROM asiakas
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus  = '$asiakastiedot[liitostunnus]'";
	$asiakasresult = pupe_query($query);
	$asiakastiedot = mysql_fetch_assoc($asiakasresult);

	//ja kelataan akuun
	mysql_data_seek($result,0);

	echo "<table><td valign='top' class='back'>";

	echo "<table>
	<tr><th>".t("Ytunnus")."</th><td>$asiakastiedot[ytunnus]</td></tr>
	<tr><th>".t("Nimi")."</th><td>$asiakastiedot[nimi]</td></tr>
	<tr><th>".t("Nimitark")."</th><td>$asiakastiedot[nimitark]</td></tr>
	<tr><th>".t("Osoite")."</th><td>$asiakastiedot[osoite]</td></tr>
	<tr><th>".t("Postinumero")."</th><td>$asiakastiedot[postino] $asiakastiedot[postitp]</td></tr>
	<tr><th>".t("Fakta")."</th><td>$asiakastiedot[fakta]</td></tr>";

	if ($karhuttavat_asiakkaat[0] != "") {
		$query  = "	SELECT kalenteri.kentta01, if(kuka.nimi!='',kuka.nimi, kalenteri.kuka) laatija, left(pvmalku,10) paivamaara
			        FROM asiakas
					JOIN kalenteri ON (kalenteri.yhtio=asiakas.yhtio and kalenteri.liitostunnus=asiakas.tunnus AND kalenteri.tyyppi = 'Myyntireskontraviesti')
					LEFT JOIN kuka ON (kalenteri.yhtio=kuka.yhtio and kalenteri.kuka=kuka.kuka)
			        WHERE asiakas.yhtio = '$kukarow[yhtio]'
		        	AND asiakas.ytunnus IN  ({$karhuttavat_asiakkaat[0]})
					ORDER BY kalenteri.tunnus desc";
		$amres = pupe_query($query);

		while ($amrow = mysql_fetch_assoc($amres)) {
			echo "<tr><th>".t("Reskontraviesti")."</th><td>$amrow[kentta01] ($amrow[laatija] / $amrow[paivamaara])</td></tr>";
		}
	}

	echo "<tr><th>". t('Karhuviesti') ."</th><td>";

	$max = 0;

	while ($lasku = mysql_fetch_assoc($result)) {
		if ($lasku['karhuttu'] > $max) {
			$max = $lasku['karhuttu'];
		}
	}
	mysql_data_seek($result,0);

	if ($asiakastiedot["kieli"] != "" and strtoupper($asiakastiedot["kieli"]) != strtoupper($yhtiorow["maa"])) {
		$sorttaus = $asiakastiedot["kieli"];
	}
	else {
		$sorttaus = $yhtiorow["maa"];
	}

	$query = "	SELECT *, if(kieli='$sorttaus', concat(1, kieli), kieli) sorttaus
				from avainsana
				where laji 	= 'KARHUVIESTI'
				and yhtio 	= '$yhtiorow[yhtio]'
				order by sorttaus, jarjestys";
	$res = mysql_query($query) or pupe_error();

	echo "<form name='lahetaformi' method='post'>";
	echo "<select name='karhuviesti'>";

	$sel1 = $sel2 = $sel3 = '';
	if ($max >= 2 and mysql_num_rows($res) > 2) {
		$sel3 = 'selected';
	}
	elseif ($max >= 1 and mysql_num_rows($res) > 1) {
		$sel2 = 'selected';
	}
	else {
		$sel1 = 'selected';
	}

	while ($viesti = mysql_fetch_assoc($res)) {
		if ($viesti["kieli"] != $edkieli) {
			$lask = 1;
		}

		echo "<option value='$viesti[tunnus]' ${'sel'.$lask}>".maa($viesti["kieli"])." ".t("viesti")." $lask</option>";

		if (${'sel'.$lask} != '') {
			${'sel'.$lask} = "";
		}

		$edkieli = $viesti["kieli"];
		$lask++;
	}

	echo "
	</select>
	</td>
	</tr>
	</table>";

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
	$lires = pupe_query($query);
	$lirow = mysql_fetch_assoc($lires);

	$query = "	SELECT sum(summa) summa
				FROM suoritus
				WHERE yhtio = '$kukarow[yhtio]'
				and kohdpvm	= '0000-00-00'
				and ltunnus > 0
				and asiakas_tunnus in ($lirow[liitokset])";
	$summaresult = pupe_query($query);
	$kaato = mysql_fetch_assoc($summaresult);

	$kaatosumma=$kaato["summa"];
	if (!$kaatosumma) $kaatosumma='0.00';

	echo "<tr><th>".t("Kaatotilill‰")."</th><td>$kaatosumma</td></tr>";

	echo "</table>";
	echo "</td></tr></table><br>";

	if (isset($ekirje_config) and is_array($ekirje_config)) {
		$submit_text = 'L‰het‰ eKirje';
	}
	else {
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
	echo "<th>".t("Lasku karhutaan")."</th>";
	echo "<th>".t("Viesti")."</th></tr>";
	$summmmma = 0;

	$valuutat = array();
	while ($lasku = mysql_fetch_assoc($result)) {
		echo "<tr class='aktiivi'><td>";

		if ($kukarow['taso'] < 2) {
			echo tv1dateconv($lasku["tapvm"]);
		}
		else {
			echo "<a href = '../muutosite.php?tee=E&tunnus=$lasku[tunnus]'>".tv1dateconv($lasku["tapvm"])."</a>";
		}

		echo "</td>";

		echo "<td><a href = '../tilauskasittely/tulostakopio.php?toim=LASKU&tee=ETSILASKU&laskunro=$lasku[laskunro]'>$lasku[laskunro]</a></td>
				<td align='right'>$lasku[summa]</td>
				<td>".tv1dateconv($lasku["erpcm"])."</td>
				<td>$lasku[ika]</td>
				<td>$lasku[karhuttu]</td>";

		echo "<td>";

		if ($lasku["kpvm"] != '')
			echo tv1dateconv($lasku["kpvm"]);

		echo "</td>";

		if ($lasku["jv"] == "") {
			$chk = "checked";
		}
		else {
			$chk = "";
		}

		echo "<td>";
		echo "<input type='checkbox' name = 'lasku_tunnus[]' value = '$lasku[tunnus]' $chk> $lasku[jv] ";

		if ($lasku["tratattu"] > 0) {
			echo t("Lasku tratattu");
		}

		echo "</td>";

		echo "<td>$lasku[comments]</td>";
		echo "</tr>\n";

		$summmmma += $lasku["summa"];

		// ker‰t‰‰n eri valuutat taulukkoon
		if (!in_array($lasku["valkoodi"], $valuutat)) {
			$valuutat[] = $lasku["valkoodi"];
		}
	}

	$summmmma -= $kaatosumma;

	echo "<th colspan='2'>".t("Karhuttavaa yhteens‰")."</th>";
	echo "<th>".sprintf('%.2f', $summmmma)."</th>";
	echo "<td class='back'></td></tr>";

	echo "</table><br>";

	echo "<table>";
	echo "<tr>";

	echo "<input name='karhut_samalle_laskulle' type='hidden' value='".count($valuutat)."'>";
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

	echo "<form name='ohitaformi' method='post'>";
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

	echo "<form method='post'>";
	echo "<input type='hidden' name='tee' value='ALOITAKARHUAMINEN'>";
	echo t("Syˆt‰ ytunnus jos haluat karhuta tietty‰ asiakasta").".<br>".t("J‰t‰ kentt‰ tyhj‰ksi jos haluat aloittaa karhuamisen ensimm‰isest‰ asiakkaasta").".<br><br>";

	echo "<table>";

	$apuqu = "	SELECT concat(nimitys,' ', valkoodi, ' (',sopimusnumero,')') nimi, tunnus
				from factoring
				where yhtio = '$kukarow[yhtio]'";
	$meapu = pupe_query($apuqu);

	if (mysql_num_rows($meapu) > 0) {

		echo "<tr><th>".t("Karhujen tyyppi").":</th>";
		echo "<td><select name='ktunnus'>";
		echo "<option value='0'>".t("Ei factoroidut")."</option>";

		while ($row = mysql_fetch_assoc($meapu)) {
			echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
		}

		echo "</select></td></tr>";
	}
	else {
		echo "<input type='hidden' name='ktunnus' value='0'>";
	}

	echo "<tr><th>".t("Karhua vain laskuja maahan").": </th>";
	echo "<td><select name='lasku_maa'>";
	echo "<option value=''>".t("Ei maavalintaa")."</option>";

	$query = "	SELECT distinct koodi, nimi
				FROM maat
				where nimi != ''
				ORDER BY koodi";
	$meapu = pupe_query($query);

	while ($row = mysql_fetch_assoc($meapu)) {
		$sel = '';
		if ($row["koodi"] == $lasku_maa) {
			$sel = 'selected';
		}
		echo "<option value='$row[koodi]' $sel>$row[nimi]</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	$apuqu = "	SELECT kuka, nimi, puhno, eposti, tunnus
				from kuka
				where yhtio = '$kukarow[yhtio]'
				and nimi    != ''
				and puhno   != ''
				and eposti  != ''
				and extranet = ''";
	$meapu = pupe_query($apuqu);

	echo "<tr><th>".t("Yhteyshenkilˆ").":</th>";
	echo "<td><select name='yhteyshenkilo'>";

	while ($row = mysql_fetch_assoc($meapu)) {
		$sel = "";

		if ($row['kuka'] == $kukarow['kuka']) {
			$sel = 'selected';
		}

		echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
	}

	echo "</select></td></tr>";


	echo "<tr><th>".t("Ytunnus").":</th>";
	echo "<td>";
	echo "<input name='ytunnus_spec' type='text' value=''></td></tr>";
	echo "<tr><th>" . t('Aloita ytunnuksesta') . "</th><td><input type='text' name='syot_ytunnus' value='$syot_ytunnus'></td></tr>";
	echo "<td class='back'><input type='submit' value='".t("Aloita")."'></td>";
	echo "</form></tr>";
	echo "</table>";
}

require ("inc/footer.inc");

?>