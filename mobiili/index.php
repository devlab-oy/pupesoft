<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# Index.php ei sis�llyt� parametrit incist� headereita
echo "<meta name='viewport' content='width=250,height=246, user-scalable=no, maximum-scale=1'>\n";
echo "<link rel='stylesheet' type='text/css' href='kasipaate.css' />\n";
echo "<body>";
echo "<div class='header'><h1>",t("P��VALIKKO", $browkieli),"</h1></div>";

if (tarkista_oikeus("mobiili/siirto.php")) {
	echo "<button value=''>",t("Siirto", $browkieli),"</button>";
}

if (tarkista_oikeus("mobiili/tulouta.php")) {
	echo "<form name='tulouta' target='_top' action='tulouta.php' method='post'>";
	echo "<input class='button' type='submit' value='",t("Tulouta", $browkieli),"' />";
	echo "</form>";
}

if (tarkista_oikeus("mobiili/inventointi.php")) {
	echo "<button class='button' value=''>",t("Inventointi", $browkieli),"</button>";
}

if (tarkista_oikeus("mobiili/tuki.php")) {
	echo "<button class='button' value=''>",t("Tuki", $browkieli),"</button>";
}

echo "<form method='post' action='{$palvelin2}logout.php?location={$palvelin2}mobiili'>";
echo "<input type='submit' class='button' value='",t("Lopeta", $browkieli),"' />";
echo "</form>";
echo "</body>";

require('inc/footer.inc');