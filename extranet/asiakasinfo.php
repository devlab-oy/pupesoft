<?php
///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

require ("parametrit.inc");


echo "<font class='head'>".t("Alennustaulukot")."</font><hr>";

// hardcoodataan värejä
$cmyynti = "#ccccff";
$ckate   = "#ff9955";
$ckatepr = "#00dd00";
$maxcol  = 12; // montako columnia näyttö on

//Haetaan asiakkaan tunnuksella
$query  = "	SELECT *
			FROM asiakas
			WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result) == 1) {
	$asiakas = mysql_fetch_array($result);
	$ytunnus = $asiakas["ytunnus"];
}
else {
	echo t("VIRHE: Käyttäjätiedoissasi on virhe! Ota yhteys järjestelmän ylläpitäjään.")."<br><br>";
	exit;
}

if ($asale!='') {
	// tehdään asiakkaan alennustaulukot
	$query = "select * from perusalennus where yhtio='$kukarow[yhtio]' order by ryhma+0";
	$result = mysql_query($query) or pupe_error($query);

	$asale  = "<table>";
	$asale .= "<tr><th>".t("Alennusryhmä")."</th><th>".t("Alennusprosentti")."</th></tr>";

	while ($alerow = mysql_fetch_array($result)) {

		$ryhma = $alerow['ryhma'];
		$ale   = $alerow['alennus'];

		$query = "	select * 
					from asiakasalennus 
					where yhtio='$kukarow[yhtio]' 
					and ytunnus='$ytunnus' 
					and ryhma='$ryhma'";
		$asres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($asres)>0) {
			$asrow = mysql_fetch_array($asres);
			$ryhma = $asrow['ryhma'];
			$ale   = $asrow['alennus'];
		}

		// näytetään rivi vaan jos ale on olemassa ...
		if ($ale != 0.00) {
			$asale .= "<tr>
				<td><font class='info'>$ryhma	<font></td>
				<td><font class='info'>$ale		<font></td>
				</tr>";
		}

	}
	$asale .= "</table>";
}
else {
	$asale = "<a href='$PHP_SELF?&asale=kylla'>".t("Näytä alennustaulukko")."</a>";
}

if ($ashin!='') {
	// haetaan asiakas hintoaja
	$ashin  = "<table>";
	$ashin .= "<tr><th>".t("Tuotenumero")."</th><th>".t("Nimitys")."</th><th>".t("Hinta")."</th></tr>";

	$query = "	select tuote.tuoteno, tuote.nimitys, asiakashinta.hinta 
				from asiakashinta, tuote 
				where asiakashinta.yhtio='$kukarow[yhtio]' 
				and ytunnus='$ytunnus' 
				and asiakashinta.yhtio=tuote.yhtio 
				and asiakashinta.tuoteno=tuote.tuoteno
				and ((asiakashinta.alkupvm <= now() and asiakashinta.loppupvm >= now()) or (asiakashinta.alkupvm='0000-00-00' and asiakashinta.loppupvm='0000-00-00'))";
	$result = mysql_query($query) or pupe_error($query);

	while ($row = mysql_fetch_array($result)) {
		$ashin .= "<tr>
			<td><font class='info'>$row[tuoteno]<font></td>
			<td><font class='info'>".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."<font></td>
			<td><font class='info'>$row[hinta]	<font></td>
			</tr>";
	}

	if (mysql_num_rows($result)==0) {
		$ashin .= "<tr><td colspan='3'><font class='info'>".t("Asiakashintoja ei löytynyt").".</font></td></tr>";
	}

	$ashin .= "</table>";
}
else {
	$ashin = "<a href='$PHP_SELF?ashin=kylla'>".t("Näytä asiakashinnat")."</a>";
}

// piirretään ryhmistä ja hinnoista taulukko..
echo "<table><tr>
		<td valign='top' class='back'>$asale</td>
		<td class='back'></td>
		<td valign='top' class='back'>$aletaulu</td>
		<td class='back'></td>
		<td valign='top' class='back'>$ashin</td>
		</tr></table>";


require ("footer.inc");

?>