<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

echo "<div class='header'>
<button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
<h1>TULOUTA</h1></div>";

echo "<div class='main'>
	<b>",t("TULOTYYPPI"),"</b>

	<ul>
	<li><a href='alusta.php' class='button'>",t("ASN / Suuntalava"),"</a></li>
	<li><a href='ostotilaus.php?uusi' class='button'>",t("Ostotilaus"),"</a>";

echo "<a href='ostotilaus.php' class='button'><font style='color: red'>(".t("Jatka edellistä").")</font></a>";

echo "</li>

	<br>
	<li><a href='suuntalavat.php' class='button'>",t("Suuntalavat"),"</a></li>
	<ul>
</div>";

echo "<div class='controls'>
</div>";

require('inc/footer.inc');