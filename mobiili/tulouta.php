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

$error = array(
	'tulotyyppi' => '',
);

if (isset($submit) and trim($submit) == 'submit' and isset($tulotyyppi) and trim($tulotyyppi) != '') {

	if ($tulotyyppi == 'suuntalava') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=alusta.php'>";
		exit;
	}
}

if (isset($submit) and trim($submit) == 'submit') {
	if ($tulotyyppi == '') $error['tulotyyppi'] = "<font class='error'>".t("Valitse tulotyyppi")."!</font>";
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
				</select>
			</td>
		</tr>
		<tr>
			<td>{$error['tulotyyppi']}</td>
		</tr>
	</table>

</div>";

echo "<div class='controls'>
	<button name='submit' value='submit' onclick='submit();'>",t("Valitse", $browkieli),"</button>
	<button class='right' name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
	</form>
</div>";

require('inc/footer.inc');