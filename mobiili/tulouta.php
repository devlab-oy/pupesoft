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
					JOIN kuka ON lasku.tunnus=kuka.kesken
					WHERE kuka='{$kukarow['kuka']}'
					and kuka.yhtio='{$kukarow['yhtio']}'
					and lasku.tila='K';";
$kesken = mysql_fetch_assoc(pupe_query($kesken_query));

#$data = array('kesken' => $kesken['kesken']);
#$url = http_build_query($data);

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

include("kasipaate.css");

echo "<div class='header'><h1>TULOUTA</h1></div>";

echo "<div class='main'>

	<form method='post' action=''>
	<table>
		<tr>
			<th>",t("TULOTYYPPI", $browkieli),"</th>
		</tr>
		<tr>
			<td>
				<select name='tulotyyppi' size='4'>
					<option value='suuntalava'>",t("ASN / Suuntalava", $browkieli),"</option>
					<option value='ostotilaus'>",t("Ostotilaus", $browkieli)," ".$kesken."</option>
				</select>
			</td>
		</tr>
	</table>

</div>";

echo "<div class='controls'>
	<button name='submit' value='submit' onclick='submit();'>",t("Valitse", $browkieli),"</button>
	<button class='right' name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
	</form>
</div>";

echo "<div class='error'>";
foreach($errors as $virhe => $selite) {
	echo strtoupper($virhe).": ".$selite."<br>";
}
echo "</div>";

require('inc/footer.inc');