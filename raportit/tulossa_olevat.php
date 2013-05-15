<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>",t("Tulossa olevat ostotilaukset"),"</font><hr>";

if (!isset($tee)) $tee = '';
if (!isset($vahvistus)) $vahvistus = '';
if (!isset($myohassa)) $myohassa = '';
if (!isset($ytunnus)) $ytunnus = '';
if (!isset($komento)) $komento = '';
if (!isset($muutparametrit)) $muutparametrit = '';

if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>",t("Tilausnro"),": {$tunnus}</font><hr>";
		require ("naytatilaus.inc");
		echo "<br /><br /><br />";
		$tee = "";
}

if ($vahvistus != '' or $myohassa != '') {
	$muutparametrit = $vahvistus."#".$myohassa;
}

if ($ytunnus != '' and $ytunnus != 'TULKAIKKI') {

	require ("inc/kevyt_toimittajahaku.inc");
}

if (($ytunnus != '' or $ytunnus == 'TULKAIKKI') and $komento == '') {

	echo "<table><tr>";
	echo "<th>",t("tilno"),"</th>";
	echo "<th>",t("ytunnus"),"</th>";
	echo "<th>",t("nimi"),"</th>";
	echo "<th>",t("saapumispvm"),"</th>";
	echo "<th>",t("rivimäärä"),"</th>";
	echo "<th>",t("määrä"),"</th>";
	echo "<th>",t("arvo"),"</th>";
	echo "<th>",t("valuutta"),"</th>";
	echo "</tr>";

	if ($ytunnus != 'TULKAIKKI') {
		$lisa = " and lasku.ytunnus = '{$toimittajarow['ytunnus']}' ";
		$sorttaus = "lasku.tunnus,";
	}
	else {
		$lisa = " ";
		$sorttaus = "";
	}

	if (trim($muutparametrit) != '') {
		list($vahvistus, $myohassa) = explode("#",$muutparametrit);
	}

	if ($vahvistus != '') {
		$lisa .= "and tilausrivi.jaksotettu = '{$vahvistus}' ";
	}

	if ($myohassa == 0 and $myohassa != '') {
		$lisa .= "and tilausrivi.toimaika >= CURDATE() ";
	}
	elseif ($myohassa == 1) {
		$lisa .= "and tilausrivi.toimaika < CURDATE() ";
	}

	$query_ale_lisa = generoi_alekentta('O');

	$query = "	SELECT lasku.tunnus, lasku.ytunnus, lasku.nimi, tilausrivi.tuoteno, tilausrivi.toimaika, lasku.valkoodi,
				count(*) maara, sum(tilausrivi.varattu) tilattu, sum(tilausrivi.varattu * tilausrivi.hinta * {$query_ale_lisa}) arvo
				from tilausrivi use index (yhtio_tyyppi_laskutettuaika)
				JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
				where tilausrivi.yhtio	= '{$kukarow['yhtio']}'
				and tilausrivi.varattu 	> '0'
				and tilausrivi.tyyppi 	= 'O'
				and tilausrivi.laskutettuaika = '0000-00-00'
				{$lisa}
				group by 1,2,3,4,5
				order by {$sorttaus} lasku.nimi, tilausrivi.tuoteno";
	$result = pupe_query($query);

	$lastunnus = $edellinen = "";

	while ($tulrow = mysql_fetch_assoc($result)) {
		echo "<tr>";
		echo "<td><a href='?tee=NAYTATILAUS&tunnus={$tulrow['tunnus']}&ytunnus={$ytunnus}&vahvistus={$vahvistus}&myohassa={$myohassa}'>{$tulrow['tunnus']}</a></td>";
		echo "<td>{$tulrow['ytunnus']}</td>";
		echo "<td>{$tulrow['nimi']}</td>";
		echo "<td>",tv1dateconv($tulrow["toimaika"]),"</td>";
		echo "<td align='right'>{$tulrow['maara']}</td>";
		echo "<td align='right'>{$tulrow['tilattu']}</td>";
		echo "<td align='right'>",hintapyoristys($tulrow["arvo"]),"</td>";
		echo "<td>{$tulrow['valkoodi']}</td>";
		echo "</tr>";

		if ($edellinen == "" or $edellinen != $tulrow["tunnus"]) {
			$lastunnus .= $tulrow["tunnus"].",";
			$edellinen = $tulrow["tunnus"];
		}
	}

	$lastunnus = rtrim($lastunnus, ",");

	echo "</table>";

	if ($ytunnus != 'TULKAIKKI' and $vahvistus == 0 and $vahvistus != '') {

		echo "<br><form name=asiakas method='post' autocomplete='off'>";
		echo "<td><input type='hidden' name='otunnus' value='{$lastunnus}'></td>";
		echo "<td><input type='hidden' name='komento' value='email'></td>";
		echo "<td><input type='hidden' name='tee' value='TULOSTA'></td>";
		echo "<tr><td class='back'><input type='submit' value='",t("Lähetä"),"'></td></tr>";
		echo "</form>";
	}

	$ytunnus = '';
}

if ($tee == 'TULOSTA') {
	require('inc/tulosta_vahvistamattomat_ostot.inc');
	echo $tulosta_ostotilaus_ulos;
}


echo "<br><form name=asiakas method='post' autocomplete='off'>";
echo "<table><tr>";
echo "<th>",t("Anna ytunnus tai osa nimestä"),"</th>";
echo "<td><input type='text' name='ytunnus' value='{$ytunnus}'></td>";
echo "</tr><tr>";

echo "<th>",t("Vahvistetut rajaus"),"</th>";
echo "<td><select name='vahvistus'>";
echo "<option value=''>",t("Kaikki"),"</option>";
echo "<option value='0'>",t("Ei Vahvistetut"),"</option>";
echo "<option value='1'>",t("Vahvistetut"),"</option>";
echo "</select></td>";
echo "</tr><tr>";

echo "<th>",t("Myöhässä olevat"),"</th>";
echo "<td><select name='myohassa'>";
echo "<option value=''>",t("Kaikki"),"</option>";
echo "<option value='0'>",t("Ei Myöhässä"),"</option>";
echo "<option value='1'>",t("Myöhässä"),"</option>";
echo "</select></td>";
echo "<td class='back'><input type='submit' value='",t("Hae"),"'></td>";
echo "</tr>";
echo "</form>";

echo "<tr><td class='back'><br /><br /></td></tr>";
echo "<form name=asiakas method='post' autocomplete='off'>";
echo "<tr>";
echo "<th>",t("Listaa kaikki tulossa olevat"),"</th>";
echo "<td><input type='hidden' name='ytunnus' value='TULKAIKKI'></td>";
echo "</tr><tr>";

echo "<th>",t("Vahvistetut rajaus"),"</th>";
echo "<td><select name='vahvistus'>";
echo "<option value=''>",t("Kaikki"),"</option>";
echo "<option value='0'>",t("Ei Vahvistetut"),"</option>";
echo "<option value='1'>",t("Vahvistetut"),"</option>";
echo "</select></td>";
echo "</tr><tr>";

echo "<th>",t("Myöhässä olevat"),"</th>";
echo "<td><select name='myohassa'>";
echo "<option value=''>",t("Kaikki"),"</option>";
echo "<option value='0'>",t("Ei Myöhässä"),"</option>";
echo "<option value='1'>",t("Myöhässä"),"</option>";
echo "</select></td>";
echo "<td class='back'><input type='submit' value='",t("Listaa"),"'></td>";
echo "</tr>";
echo "<tr><td class='back'><br><br></td></tr>";

echo "</tr></table>";
echo "</form>";

// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

require ("inc/footer.inc");