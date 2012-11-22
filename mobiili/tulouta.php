<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# Tarkistetaan onko käyttäjällä kesken olevia tilauksia
$kesken_query = "	SELECT kuka.kesken FROM lasku
					JOIN kuka ON (lasku.tunnus=kuka.kesken and lasku.yhtio=kuka.yhtio)
					WHERE kuka='{$kukarow['kuka']}'
					and kuka.yhtio='{$kukarow['yhtio']}'
					and lasku.tila='K';";
$kesken = mysql_fetch_assoc(pupe_query($kesken_query));
$kesken = ($kesken['kesken'] == 0) ? "" : "(Kesken)";

echo "<div class='header'>
<button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
<h1>TULOUTA</h1></div>";

echo "<div class='main'>
	<b>",t("TULOTYYPPI"),"</b>

	<ul>
	<li><a href='alusta.php' class='button'>",t("ASN / Suuntalava"),"</a></li>
	<li><a href='ostotilaus.php?uusi' class='button'>",t("Ostotilaus"),"</a>";

if ($kesken != '') echo "<a href='ostotilaus.php' class='button'><font style='color: red'>$kesken</font></a>";

echo "</li>

	<br>
	<li><a href='suuntalavat.php' class='button'>",t("Suuntalavat"),"</a></li>
	<ul>
</div>";

echo "<div class='controls'>
</div>";

require('inc/footer.inc');