<?php
///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
$useslave = 1;
require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Menossa olevat tilaukset")."</font><hr>";

if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>Tilausnro: $tunnus</font><hr>";
		require ("naytatilaus.inc");
		echo "<br><br><br>";
		$tee = "";
}

if ($ytunnus != '' and $ytunnus != 'TULKAIKKI') {
	require ("../inc/asiakashaku.inc");
}

if ($ytunnus != '' or $ytunnus == 'TULKAIKKI') {

	echo "<table><tr>";
	echo "<th>".t("tilno")."</th>";
	echo "<th>".t("ytunnus")."</th>";
	echo "<th>".t("nimi")."</th>";
	echo "<th>".t("toimituspvm")."</th>";
	echo "<th>".t("rivim‰‰r‰")."</th>";
	echo "<th>".t("kplm‰‰r‰")."</th>";
	echo "<th>".t("arvo")."</th>";
	echo "<th>".t("valuutta")."</th>";
	echo "</tr>";
	
	if ($ytunnus != 'TULKAIKKI') {
		$lisa = " and lasku.ytunnus = '$ytunnus' ";
	}
	else {
		$lisa = " ";
	}

	$query = "	SELECT lasku.tunnus, lasku.nimi, tilausrivi.tuoteno, tilausrivi.toimaika, 
				count(*) maara, sum(tilausrivi.varattu) tilattu, sum(tilausrivi.varattu * tilausrivi.hinta) arvo, lasku.valkoodi
				from tilausrivi use index (yhtio_tyyppi_laskutettuaika)
				JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
				where tilausrivi.yhtio	= '$kukarow[yhtio]' 
				and tilausrivi.varattu 	> '0' 
				and tilausrivi.tyyppi 	= 'L'
				and tilausrivi.laskutettuaika = '0000-00-00' 
				$lisa
				group by 1,2,3,4
				order by lasku.nimi, tilausrivi.tuoteno";
	$result = mysql_query($query) or pupe_error($query);
	
	while ($tulrow = mysql_fetch_array($result)) {
		echo "<tr>";
		echo "<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$tulrow[tunnus]&ytunnus=$ytunnus'>$tulrow[tunnus]</a></td>";
		echo "<td>$tulrow[ytunnus]</td>";
		echo "<td>$tulrow[nimi]</td>";
		echo "<td>".tv1dateconv($tulrow["toimaika"])."</td>";
		echo "<td align='right'>$tulrow[maara]</td>";
		echo "<td align='right'>$tulrow[tilattu]</td>";
		echo "<td align='right'>$tulrow[arvo]</td>";
		echo "<td>$tulrow[valkoodi]</td>";
		echo "</tr>";
	}

	echo "</table>";

	$ytunnus = '';
}


echo "<br><form name=asiakas action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<table><tr>";
echo "<th>".t("Anna ytunnus tai osa nimest‰")."</th>";
echo "<td><input type='text' name='ytunnus' value='$ytunnus'></td>";
echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
echo "</tr>";
echo "</form>";
echo "<tr><td class='back'><br><br></td></tr>";
echo "<form name=asiakas action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<tr>";
echo "<th>".t("Listaa kaikki menossa olevat")."</th>";
echo "<td><input type='hidden' name='ytunnus' value='TULKAIKKI'></td>";
echo "<td class='back'><input type='submit' value='".t("Listaa")."'></td>";
echo "</tr></table>";
echo "</form>";



// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

require ("../inc/footer.inc");

?>