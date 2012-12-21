<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# Index.php ei sisällytä parametrit incistä headereita
echo "<meta name='viewport' content='width=250,height=246, user-scalable=no, maximum-scale=1'>\n";
echo "<link rel='stylesheet' type='text/css' href='kasipaate.css' />\n";
echo "<body>";
echo "<div class='header'><h1>",t("PÄÄVALIKKO", $browkieli),"</h1></div>";

echo "<div class='main valikko'>";
if (tarkista_oikeus("mobiili/siirto.php")) {
	echo "<button value=''>",t("Siirto", $browkieli),"</button>";
}

if (tarkista_oikeus("mobiili/tulouta.php")) {
	echo "<p><a href='tulouta.php' class='button'>",t("Tulouta", $browkieli),"</a></p>";
}

if (tarkista_oikeus("mobiili/inventointi.php")) {
	echo "<p><a href='inventointi.php' class='button'>",t("Inventointi", $browkieli),"</a></p>";
}

if (tarkista_oikeus("mobiili/tuki.php")) {
	echo "<button class='button' value=''>",t("Tuki", $browkieli),"</button>";
}

echo "<p><a href='{$palvelin2}logout.php?location={$palvelin2}mobiili' class='button'>",t("Lopeta", $browkieli),"</a></p>";

echo "</div>";
echo "</body>";

require('inc/footer.inc');