<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

include("kasipaate.css");

echo "<body>";
echo "<h1>",t("PÄÄVALIKKO", $browkieli),"</h1>";

if (tarkista_oikeus("mobiili/siirto.php")) {
	echo "<button value=''>",t("Siirto", $browkieli),"</button>";
}

if (tarkista_oikeus("mobiili/tulouta.php")) {
	echo "<form name='tulouta' target='_top' action='tulouta.php' method='post'>";
	echo "<input type='submit' value='",t("Tulouta", $browkieli),"' />";
	echo "</form>";
}

if (tarkista_oikeus("mobiili/inventointi.php")) {
	echo "<button value=''>",t("Inventointi", $browkieli),"</button>";
}

if (tarkista_oikeus("mobiili/tuki.php")) {
	echo "<button value=''>",t("Tuki", $browkieli),"</button>";
}

echo "<form method='post' action='{$palvelin2}logout.php?location={$palvelin2}mobiili'>";
echo "<input type='submit' value='",t("Lopeta", $browkieli),"' />";
echo "</form>";
echo "</body>";

#require('inc/footer.inc');