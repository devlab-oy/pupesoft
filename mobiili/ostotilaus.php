<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# Jos haulla ei löytyny mitään, ollaan palattu tähän virhe parametrilla.
if (isset($virhe)) {
	$error['ostotilaus'] = "Ei löytynyt. Hae uudestaan.";
}

if (isset($submit)) {
	switch($submit) {
		case 'ok':
			# TODO: Tarkistus, yksi kenttä vähintään syötetty
			if (empty($data['viivakoodi']) and empty($data['tuotenumero']) and empty($data['ostotilaus'])) {
				$error['ostotilaus'] = "Vähintään yksi kenttä on syötettävä";
				break;
			}
			$url = http_build_query($data);

			#echo "tuotteella_useita_tilauksia.php?{$url}";
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tuotteella_useita_tilauksia.php?{$url}'>"; exit();
			break;
		case 'cancel':
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tulouta.php'>"; exit();
		   	break;
		default:
			echo "Virhe";
			break;
	}
}

### UI ###
include("kasipaate.css");

echo "
<div class='header'><h1>",t("OSTOTILAUS"),"</h1></div>";

echo "<div class='main'>
<form method='post' action=''>
<table>
	<tr>
		<th><label for='viivakoodi'>Viivakoodi</label></th>
		<td><input type='text' id='viivakoodi' name='data[viivakoodi]' /><td>
	</tr>
	<tr>
		<th><label for='tuotenumero'>Tuotenumero</label></th>
		<td><input type='text' id='tuotenumero' name='data[tuotenumero]'/><td>
	</tr>
	<tr>
		<th><label for='ostotilaus'>Ostotilaus</label></th>
		<td><input type='text' id='ostotilaus' name='data[ostotilaus]'/><td>
	</tr>
</table>
</div>";

echo "<div class='controls'>
	<button name='submit' value='ok' onclick='submit();'>",t("OK", $browkieli),"</button>
	<button class='right' name='submit' id='takaisin' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
</form>
</div>";

echo "<span class='error'>{$error['ostotilaus']}</span>";