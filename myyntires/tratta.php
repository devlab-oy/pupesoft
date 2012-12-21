<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Tratta")."</font><hr>";

//vain n‰in monta p‰iv‰‰ sitten karhutut
//laskut huomioidaan trattauksessa
$kpvm_aikaa = 0;

//vain n‰in monta p‰iv‰‰ sitten er‰‰ntyneet
//laskut huomioidaan n‰kym‰sss‰
$lpvm_aikaa = 10;

//lasku pit‰‰ olla karhuttu v‰h n‰in
//monta kertaa jotta sit‰ haluutaan tratata
$karhu_kerta = 2;

if ($kukarow["kirjoitin"] == 0) {
	echo "<font class='error'>".t("Sinulla pit‰‰ olla henkilˆkohtainen tulostin valittuna, ett‰ voit tulostaa trattoja").".</font><br>";
	$tee = "";
}

if ($tee == 'LAHETA') {
	if (! empty($_POST['lasku_tunnus'])) {
		require ('paperitratta.php');
		$jatka = true;
	}
	else {
		echo "<font class='error'>".t("Et valinnut yht‰‰n laskua").".</font>";
		$jatka = false;
	}

	if ($jatka) {
		array_shift($tratattavat);
	}

	$tee = "TRATTAA";
}

// ohitetaanko asiakas?
if ($tee == 'OHITA') {
	array_shift($tratattavat);

	$tee = "TRATTAA";
}

if ($tee == "ALOITATRATTAAMINEN") {

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

			$query = "	SELECT GROUP_CONCAT(tunnus) karhuttavat
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
		$query = "	SELECT GROUP_CONCAT(tunnus) karhuttavat
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]' and factoring = '' $maa_lisa";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$maksuehdotrow = mysql_fetch_array($result);
			$maksuehtolista = " and lasku.maksuehto in ($maksuehdotrow[karhuttavat])";
		}
	}

	$query = "	SELECT GROUP_CONCAT(distinct concat('\'',ovttunnus,'\'')) konsrernyhtiot
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

	$query = "	SELECT asiakas.ytunnus, asiakas.nimi, asiakas.nimitark, asiakas.osoite, asiakas.postino, asiakas.postitp,
				group_concat(distinct lasku.tunnus) tratattavat
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
						$maksuehtolista
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

		while ($karhuttavarow = mysql_fetch_array($result)) {
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
				count(distinct karhu_lasku.ktunnus) as karhuttu,
				sum(if(karhukierros.tyyppi='T', 1, 0)) tratattu
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

	$query = "	SELECT *,
				IF(laskutus_nimi != '' and (maksukehotuksen_osoitetiedot = 'B' or ('{$yhtiorow['maksukehotuksen_osoitetiedot']}' = 'K' and maksukehotuksen_osoitetiedot = '')), laskutus_nimi, nimi) nimi,
				IF(laskutus_nimi != '' and (maksukehotuksen_osoitetiedot = 'B' or ('{$yhtiorow['maksukehotuksen_osoitetiedot']}' = 'K' and maksukehotuksen_osoitetiedot = '')), laskutus_nimitark, nimitark) nimitark,
				IF(laskutus_nimi != '' and (maksukehotuksen_osoitetiedot = 'B' or ('{$yhtiorow['maksukehotuksen_osoitetiedot']}' = 'K' and maksukehotuksen_osoitetiedot = '')), laskutus_osoite, osoite) osoite,
				IF(laskutus_nimi != '' and (maksukehotuksen_osoitetiedot = 'B' or ('{$yhtiorow['maksukehotuksen_osoitetiedot']}' = 'K' and maksukehotuksen_osoitetiedot = '')), laskutus_postino, postino) postino,
				IF(laskutus_nimi != '' and (maksukehotuksen_osoitetiedot = 'B' or ('{$yhtiorow['maksukehotuksen_osoitetiedot']}' = 'K' and maksukehotuksen_osoitetiedot = '')), laskutus_postitp, postitp) postitp
				FROM asiakas
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus  = '$asiakastiedot[liitostunnus]'";
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
	<tr><th>".t("Postinumero")."</th><td>$asiakastiedot[postino] $asiakastiedot[postitp]</td></tr>
	<tr><th>".t("Fakta")."</th><td>$asiakastiedot[fakta]</td></tr>";

	//Reskontraviestit
	$query  = "	SELECT kalenteri.kentta01, if(kuka.nimi!='',kuka.nimi, kalenteri.kuka) laatija, left(kalenteri.pvmalku,10) paivamaara
		        FROM asiakas
				JOIN kalenteri ON (kalenteri.yhtio=asiakas.yhtio and kalenteri.liitostunnus=asiakas.tunnus AND kalenteri.tyyppi = 'Myyntireskontraviesti')
				LEFT JOIN kuka ON (kalenteri.yhtio=kuka.yhtio and kalenteri.kuka=kuka.kuka)
		        WHERE asiakas.yhtio = '$kukarow[yhtio]'
	        	AND asiakas.ytunnus = '$asiakastiedot[ytunnus]'
				ORDER BY kalenteri.tunnus desc";
	$amres = pupe_query($query);

	while ($amrow = mysql_fetch_assoc($amres)) {
		echo "<tr><th>".t("Reskontraviesti")."</th><td>$amrow[kentta01] ($amrow[laatija] / $amrow[paivamaara])</td></tr>";
	}

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

	$query = "	SELECT sum(summa) summa
				FROM suoritus
				WHERE yhtio  = '$kukarow[yhtio]'
				and ltunnus  > 0
				and kohdpvm  = '0000-00-00'
				and asiakas_tunnus in ($lirow[liitokset])";
	$summaresult = mysql_query($query) or pupe_error($query);
	$kaato = mysql_fetch_array($summaresult);

	$kaatosumma=$kaato["summa"];
	if (!$kaatosumma) $kaatosumma='0.00';

	echo "<tr><th>".t("Kaatotilill‰")."</th><td>$kaatosumma</td></tr>";

	echo "</table>";
	echo "</td></tr></table><br>";


	echo "<table>";
	echo "<tr>";
	echo "<td class='back'><input type='button' onclick='javascript:document.lahetaformi.submit();' value='".t("L‰het‰")."'></td>";
	echo "<td class='back'><input type='button' onclick='javascript:document.ohitaformi.submit();' value='".t("Ohita")."'></td>";
	echo "</tr>";
	echo "</table><br>";

	echo "<form name='lahetaformi' method='post'>";
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

		echo "<tr class='aktiivi'><td>";

		if ($kukarow['taso'] < 2) {
			echo $lasku["tapvm"];
		}
		else {
			echo "<a href = '../muutosite.php?tee=E&tunnus=$lasku[tunnus]'>$lasku[tapvm]</a>";
		}

		echo "</td><td>";
		echo "<a href = '../tilauskasittely/tulostakopio.php?toim=LASKU&tee=ETSILASKU&laskunro=$lasku[laskunro]'>$lasku[laskunro]</a>";
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
		if ($lasku["tratattu"] > 0) {
			echo t("Lasku tratattu");
		}
		else {
			echo "<input type='checkbox' name = 'lasku_tunnus[]' value = '$lasku[tunnus]' checked>";
		}
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

	echo "<td class='back'><input name='$kentta' type='submit' value='".t("L‰het‰")."'></td></form>";


	echo "<form name='ohitaformi' method='post'>";
	echo "<input type='hidden' name='tee' value='OHITA'>";
	echo "<input name='yhteyshenkilo' type='hidden' value='$yhteyshenkilo'>";

	foreach($tratattavat as $tunnukset) {
		echo "\n<input type='hidden' name='tratattavat[]' value='$tunnukset'>";
	}

	echo "<td class='back'><input type='submit' value='".t("Ohita")."'></td>";
	echo "</form></tr>";
	echo "</table>";
}

if ($tee == "") {

	echo "<form method='post'>";
	echo "<input type='hidden' name='tee' value='ALOITATRATTAAMINEN'>";
	echo t("Syˆt‰ ytunnus jos haluat tratata tietty‰ asiakasta").".<br>".t("J‰t‰ kentt‰ tyhj‰ksi jos haluat aloittaa trattaamisen ensimm‰isest‰ asiakkaasta").".<br><br>";
	echo "<table>";

	$apuqu = "	select concat(nimitys,' ', valkoodi, ' (',sopimusnumero,')') nimi, tunnus
				from factoring
				where yhtio = '$kukarow[yhtio]'";
	$meapu = mysql_query($apuqu) or pupe_error($apuqu);

	if (mysql_num_rows($meapu) > 0) {

		echo "<tr><th>".t("Trattojen tyyppi").":</th>";
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

	$apuqu = "	SELECT distinct sallitut_maat
				from maksuehto
				where yhtio = '$kukarow[yhtio]' and sallitut_maat != ''";
	$meapu = mysql_query($apuqu) or pupe_error($apuqu);

	if (mysql_num_rows($meapu) > 0) {

		$maa_lisa = " and koodi in (";

		while ($row = mysql_fetch_array($meapu)) {
			$maat = explode('[, ]', $row["sallitut_maat"]);
			foreach ($maat as $maa) {
				$maa_lisa .= "'$maa',";
			}
		}

		$maa_lisa = substr($maa_lisa, 0, -1);
		$maa_lisa .= ")";

		echo "<tr><th>".t("Trattaa vain maksuehtoja maasta").": </th>";
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
	echo "<td class='back'><input type='submit' value='".t("Aloita")."'></td>";
	echo "</form></tr>";
	echo "</table>";
}

require ("../inc/footer.inc");

?>
