<?php
///* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *///
$useslave = 1;
require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Tulossa olevat ostotilaukset")."</font><hr>";
if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>Tilausnro: $tunnus</font><hr>";
		require ("naytatilaus.inc");
		echo "<br><br><br>";
		$tee = "";
}
if ($ytunnus!='') {
	require ("../inc/kevyt_toimittajahaku.inc");


}
if ($ytunnus!='') {

	echo "<table><tr>";
	echo "<th>".t("tilno")."</th>";
	echo "<th>".t("ytunnus")."</th>";
	echo "<th>".t("nimi")."</th>";
	echo "<th>".t("saapumispvm")."</th>";
	echo "<th>".t("rivim‰‰r‰")."</th>";
	echo "<th>".t("kplm‰‰r‰")."</th>";
	echo "<th>".t("arvo")."</th>";
	echo "<th>".t("valuutta")."</th>";
	echo "</tr>";

	$query = 	"select a.tunnus, a.nimi, tuoteno, b.toimaika, count(*) maara, sum(b.varattu) tilattu, sum(b.varattu * b.hinta) arvo, a.valkoodi
				from lasku a, tilausrivi b
				where a.yhtio = b.yhtio and a.tunnus = b.otunnus
				and a.yhtio='$kukarow[yhtio]' and a.ytunnus = $toimittajarow[ytunnus] and b.varattu > '0' and b.tyyppi = 'O' and a.tila = 'O'
				group by 1
				order by 4";

	$result = mysql_query($query) or pupe_error($query);
	//echo "$query";
	while ($tulrow = mysql_fetch_array($result)) {
		echo "<tr>";
		echo "<td><a href='$PHP_SELF?tee=NAYTATILAUS&tunnus=$tulrow[tunnus]&ytunnus=$ytunnus'>$tulrow[tunnus]</a></td>";
		echo "<td>$toimittajarow[ytunnus]</td>";
		echo "<td>$toimittajarow[nimi]</td>";
		echo "<td>$tulrow[toimaika]</td>";
		echo "<td>$tulrow[maara]</td>";
		echo "<td>$tulrow[tilattu]</td>";
		echo "<td>$tulrow[arvo]</td>";
		echo "<td>$tulrow[valkoodi]</td>";
		echo "</tr>";
	}

	echo "</table>";

	$ytunnus = '';
}


echo "<br><br><form name=asiakas action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<table><tr>";
echo "<th>".t("Anna ytunnus tai osa nimest‰")."</th>";
echo "<td><input type='text' name='ytunnus' value='$ytunnus'></td>";
echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
echo "</tr></table>";
echo "</form>";



// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

require ("../inc/footer.inc");

?>