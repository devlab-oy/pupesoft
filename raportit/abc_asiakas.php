<?php

// tämä skripti käyttää slave-tietokantapalvelinta
$useslave = 1;

require ("../inc/parametrit.inc");

if ($toim == "") {
	$toim = "myynti";
}

if ($tee == '') {
	echo "<font class='head'>".t("ABC-Analysointia asiakkaille")."<hr></font>";
	echo "<br><br><b>".t("Valitse toiminto").":</b><br><br>";
	echo "<a href='$PHP_SELF?tee=YHTEENVETO&toim=$toim'         >1. ".t("ABC-luokkayhteenveto")."</a><br>";
	echo "<a href='$PHP_SELF?tee=OSASTOTRYYHTEENVETO&toim=$toim'>2. ".t("Osasto/Ryhmä yhteenveto")."</a><br>";
	echo "<a href='$PHP_SELF?tee=OSASTOTRY&toim=$toim'          >3. ".t("Osasto/Ryhmä")."</a><br>";
	echo "<a href='$PHP_SELF?tee=PITKALISTA&toim=$toim'         >4. ".t("Kaikki luokat tekstinä")."</a><br>";
}

// jos kaikki tarvittavat tiedot löytyy mennään queryyn
if ($tee == 'YHTEENVETO') {
	require ("abc_asiakas_yhteenveto.php");
}

if ($tee == 'LUOKKA') {
	require ("abc_asiakas_luokka.php");
}

if ($tee == 'OSASTOTRY') {
	require ("abc_asiakas_osastotry.php");
}

if ($tee == 'OSASTOTRYYHTEENVETO') {
	require ("abc_asiakas_osastotry_yhteenveto.php");
}

if ($tee == 'TUOTE') {
	require ("abc_asiakashistoria.php");
}

if ($tee == 'PITKALISTA') {
	require ("abc_asiakas_kaikki_taullask.php");
}

require ("../inc/footer.inc");

?>