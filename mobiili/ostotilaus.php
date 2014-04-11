<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if(!isset($errors)) $errors = array();

// Jos uusi parametri on setattu nollataan kuka.kesken
if (isset($uusi) AND !isset($virhe)) {
	$nollaus_query = "UPDATE kuka SET kesken=0 WHERE yhtio='{$kukarow['yhtio']}' AND kuka='{$kukarow['kuka']}'";
	$result = pupe_query($nollaus_query);
}
// Katsotaan onko käyttäjälle keskeneräistä saapumista
elseif (!isset($virhe) AND (!isset($backsaapuminen) OR $backsaapuminen != "")) {
	$query = "	SELECT kesken
				FROM kuka
				JOIN lasku ON (kuka.yhtio=lasku.yhtio AND kuka.kesken=lasku.tunnus AND lasku.tila='K' AND lasku.alatila NOT IN ('X','I'))
				WHERE kuka.kuka = '{$kukarow['kuka']}'
				AND kuka.yhtio  = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);
	$kesken_row = mysql_fetch_assoc($result);

	// Jos käyttäjällä ei ole keskeneräistä saapumista, haetaan käyttäjän viimeisimmäksi luotu saapumisotsikko ja jatketaan sitä
	if ($kesken_row['kesken'] == 0) {

		// Haetaan käyttäjän uusin saapumisen tunnus ja setataan se kesken kolumniin
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND laatija = '{$kukarow['kuka']}'
					AND tila = 'K'
					AND alatila NOT IN ('X','I')
					ORDER BY luontiaika DESC
					LIMIT 1";
		$result = pupe_query($query);
		$saapuminen_row = mysql_fetch_assoc($result);

		$kesken_query = "	UPDATE kuka
							SET kesken = '{$saapuminen_row['tunnus']}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND kuka    = '{$kukarow['kuka']}'";
		pupe_query($kesken_query);
	}
}

# Jos haulla ei löytyny mitään, ollaan palattu tälle sivulle virheparametrilla.
if (isset($virhe)) {
	$errors[] = t("Ei löytynyt. Hae uudestaan.");
}

if (isset($submit)) {
	switch($submit) {
		case 'ok':
			# Haettu vähintään yhdellä kentällä
			if (empty($data['viivakoodi']) and empty($data['tuotenumero']) and empty($data['ostotilaus'])) {
				$errors[] = t("Vähintään yksi kenttä on syötettävä");
				break;
			}

			$data['manuaalisesti_syotetty_ostotilausnro'] = $data['ostotilaus'] != '' ? 1 : 0;

			# Rakennetaan parametrit kentistä
			$url = http_build_query($data);

			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tuotteella_useita_tilauksia.php?{$url}'>"; exit();
			break;
		case 'takaisin':
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tulouta.php'>"; exit();
		   	break;
		default:
			$errors[] = t("Yllättävä virhe");
			break;
	}
}

$ostotilaus = (!empty($ostotilaus)) ? $ostotilaus : '';

### UI ###
echo "
<div class='header'>
	<button onclick='window.location.href=\"tulouta.php\"' class='button left'><img src='back2.png'></button>
	<h1>",t("OSTOTILAUS"),"</h1>
</div>";

echo "<div class='main'>
<form method='post' action=''>
<table>
	<tr>
		<th><label for='viivakoodi'>",t("Viivakoodi"),"</label></th>
		<td><input type='text' id='viivakoodi' name='data[viivakoodi]' /><td>
	</tr>
	<tr>
		<th><label for='tuotenumero'>",t("Tuotenumero"),"</label></th>
		<td><input type='text' id='tuotenumero' name='data[tuotenumero]'/><td>
	</tr>
	<tr>
		<th><label for='ostotilaus'>",t("Ostotilaus"),"</label></th>
		<td><input type='text' id='ostotilaus' name='data[ostotilaus]' value='{$ostotilaus}'/><td>
	</tr>
</table>
</div>";

echo "<div class='controls'>
	<button name='submit' id='haku_nappi' value='ok' onclick='submit();' class='button'>",t("OK"),"</button>
</form>
</div>";

echo "<div class='error'>";
    foreach($errors as $error) {
        echo $error."<br>";
    }
echo "</div>";

echo "<input type='button' id='myHiddenButton' visible='false' onclick='javascript:doFocus();' width='1px' style='display:none'>";
echo "<script type='text/javascript'>

	// katotaan onko mobile
	var is_mobile = navigator.userAgent.match(/Opera Mob/i) != null;

	$(document).ready(function() {
		$('#viivakoodi').on('keyup', function() {
			// Autosubmit vain jos on syötetty tarpeeksi pitkä viivakoodi
			if (is_mobile && $('#viivakoodi').val().length > 8) {
				document.getElementById('haku_nappi').click();
			}
		});
	});

	function doFocus() {
	    var focusElementId = 'viivakoodi';
	    var textBox = document.getElementById(focusElementId);
	    textBox.focus();
    }

	function clickButton() {
	   document.getElementById('myHiddenButton').click();
	}

	setTimeout('clickButton()', 1000);

</script>
";
require('inc/footer.inc');
