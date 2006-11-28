<?php

// tämä skripti käyttää slave-tietokantapalvelinta
$useslave = 1;

require ("../inc/parametrit.inc");

if ($toim == "") {
	$toim = "myynti";
}

if ($toim == "kate") {
	$abcwhat = "kate";
	$abcchar = "AK";
}
elseif ($toim == "kpl") {
	$abcwhat = "kpl";
	$abcchar = "AP";
}
else {
	$abcwhat = "summa";
	$abcchar = "AM";
}

if ($tee == '') {
	echo "<font class='head'>".t("ABC-Analysointia asiakkaille")."<hr></font>";
	echo "<font class='message'>";

	if ($toim == "myynti") {
		echo "ABC-luokat myynnin mukaan.";
	}
	elseif ($toim == "kpl") {
		echo "ABC-luokat kappaleiden mukaan.";
	}
	else {
		echo "ABC-luokat katteen mukaan.";
	}

	echo "<br><br>".t("Valitse toiminto").":<br><br>";
	echo "</font>";
	echo "<li><a class='menu' href='$PHP_SELF?tee=YHTEENVETO&toim=$toim'         >".t("ABC-luokkayhteenveto")."</a><br>";
	echo "<li><a class='menu' href='$PHP_SELF?tee=OSASTOTRY&toim=$toim'          >".t("Asiakasosaston tai asiakasryhmän luokat")."</a><br>";
	echo "<li><a class='menu' href='$PHP_SELF?tee=LUOKKA&toim=$toim'             >".t("Luokan asiakkaat")."</a><br>";
	echo "<li><a class='menu' href='$PHP_SELF?tee=PITKALISTA&toim=$toim'         >".t("Kaikki luokat tekstinä")."</a><br>";
}

// jos kaikki tarvittavat tiedot löytyy mennään queryyn
if ($tee == 'YHTEENVETO') {
	require ("abc_asiakas_yhteenveto.php");
}

if ($tee == 'OSASTOTRY') {
	require ("abc_asiakas_osastotry.php");
}

if ($tee == 'LUOKKA') {
	require ("abc_asiakas_luokka.php");
}

if ($tee == 'PITKALISTA') {
	require ("abc_asiakas_kaikki_taullask.php");
}

require ("../inc/footer.inc");

?>