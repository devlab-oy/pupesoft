<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

echo "
	<style type='text/css'>
	<!--
		A, A:visited	{color: #c0c0c0; text-decoration:none;}
		.error		{color: #ff6666;}
	-->
	</style>";

echo "<body>";
echo "<h1>",t("P‰‰valikko", $browkieli),"</h1>";

if (tarkista_oikeus("mobiili/siirto.php")) {
	echo "<button value=''>Siirto</button>";
}

if (tarkista_oikeus("mobiili/tulouta.php")) {
	echo "<form name='tulouta' target='_top' action='tulouta.php' method='post'>";
	echo "<input type='submit' value='Tulouta' />";
	echo "</form>";
}

if (tarkista_oikeus("mobiili/inventointi.php")) {
	echo "<button value=''>Inventointi</button>";
}

if (tarkista_oikeus("mobiili/tuki.php")) {
	echo "<button value=''>Tuki</button>";
}

echo "<form method='post' action='{$palvelin2}logout.php?location={$palvelin2}mobiili'>";
echo "<input type='submit' value='Lopeta' />";
echo "</form>";
echo "</body>";

require('inc/footer.inc');