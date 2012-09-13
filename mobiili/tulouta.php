<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (isset($submit) and trim($submit) == 'cancel') {
	echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL={$palvelin2}mobiili'>";
	exit;
}

$errors = array();

# Tarkistetaan onko käyttäjällä kesken olevia tilauksia
$kesken_query = "	SELECT kuka.kesken FROM lasku
					JOIN kuka ON (lasku.tunnus=kuka.kesken and lasku.yhtio=kuka.yhtio)
					WHERE kuka='{$kukarow['kuka']}'
					and kuka.yhtio='{$kukarow['yhtio']}'
					and lasku.tila='K';";
$kesken = mysql_fetch_assoc(pupe_query($kesken_query));

if (isset($submit) and trim($submit) == 'submit' and isset($tulotyyppi) and trim($tulotyyppi) != '') {

	switch($tulotyyppi) {
		case 'suuntalava':
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=alusta.php'>"; exit;
			break;
		case 'ostotilaus':
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php'>"; exit;
			break;
		default:
			echo "Virheet tänne";
			break;
	}
}

if (isset($submit) and trim($submit) == 'submit') {
	if ($tulotyyppi == '') $errors['tulotyyppi'] = t("Valitse tulotyyppi");
}

$kesken = ($kesken['kesken'] == 0) ? "" : "({$kesken['kesken']})";

echo "<div class='header'>
<button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
<h1>TULOUTA</h1></div>";

echo "<div class='main'>
	<form method='post' action=''>
	<b>",t("TULOTYYPPI"),"</b>
	<p>
	<a href='alusta.php' class='button'>",t("ASN / Suuntalava"),"</a><br>
	<a href='ostotilaus.php' class='button'>",t("Ostotilaus"),"</a><br>
	</p>
	<a href='suuntalavat.php' class='button'>",t("Suuntalavat"),"</a><br>
</div>";

echo "<div class='controls'>
	</form>
</div>";

echo "<div class='error'>";
foreach($errors as $virhe => $selite) {
	echo strtoupper($virhe).": ".$selite."<br>";
}
echo "</div>";

require('inc/footer.inc');