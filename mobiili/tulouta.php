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
	// if ($tulotyyppi == 'suuntalava') {
	// 	echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=alusta.php'>";
	// 	exit;
	// }
}

if (isset($submit) and trim($submit) == 'submit') {
	if ($tulotyyppi == '') $errors['tulotyyppi'] = t("Valitse tulotyyppi");
}

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
					<option value='ostotilaus'>",t("Ostotilaus", $browkieli),"</option>
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