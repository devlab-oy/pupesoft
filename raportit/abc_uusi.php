<?php
///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

require ("../inc/parametrit.inc");

if ($tee == '') {
	echo "<font class='head'>".t("ABC-Analysointia tuotteille")."<hr></font>";
	
	echo "<br><br><b>".t("Valitse toiminto").":</b><br><br>";
	echo "<a href='$PHP_SELF?tee=YHTEENVETO'>1. ".t("ABC-luokkayhteenveto")."</a><br>";
	echo "<a href='$PHP_SELF?tee=OSASTOTRYYHTEENVETO'>2. ".t("Tuoteryhmäyhteenveto")."</a><br>";
	echo "<a href='$PHP_SELF?tee=OSASTOTRY'>3. ".t("Osasto/Tuoteryhmä")."</a><br>";
	echo "<a href='$PHP_SELF?tee=PITKALISTA'>4. ".t("Kaikki luokat tekstinä")."</a><br>";
	
}

// jos kaikki tarvittavat tiedot löytyy mennään queryyn
if ($tee == 'YHTEENVETO') {
	require ("abc_tuote_yhteenveto.php");
}

if ($tee == 'LUOKKA') {
	require ("abc_tuote_luokka.php");
}

if ($tee == 'OSASTOTRY') {
	require ("abc_tuote_osastotry.php");
}

if ($tee == 'OSASTOTRYYHTEENVETO') {
	require ("abc_tuote_osastotry_yhteenveto.php");
}

if ($tee == 'TUOTE') {	
	require ("abc_tuote_tuotehistoria.php");
}

if ($tee == 'PITKALISTA') {	
	require ("abc_kaikki_taullask.php");
}


require ("../inc/footer.inc");

?>