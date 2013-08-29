<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if(!isset($errors)) $errors = array();

// Jos uusi parametri on setattu nollataan kuka.kesken
if (isset($uusi)) {
	$nollaus_query = "UPDATE kuka SET kesken=0 WHERE yhtio='{$kukarow['yhtio']}' AND kuka='{$kukarow['kuka']}'";
	$result = pupe_query($nollaus_query);
}
// Katsotaan onko k�ytt�j�lle keskener�ist� saapumista
else {
	$query = "	SELECT kesken
				FROM kuka
				JOIN lasku ON (kuka.yhtio=lasku.yhtio AND kuka.kesken=lasku.tunnus AND lasku.tila='K' AND lasku.alatila NOT IN ('X','I'))
				WHERE kuka.kuka = '{$kukarow['kuka']}'
				AND kuka.yhtio  = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);
	$kesken_row = mysql_fetch_assoc($result);

	// Jos k�ytt�j�ll� ei ole keskener�ist� saapumista, haetaan k�ytt�j�n viimeisimm�ksi luotu saapumisotsikko ja jatketaan sit�
	if ($kesken_row['kesken'] == 0) {

		// Haetaan k�ytt�j�n uusin saapumisen tunnus ja setataan se kesken kolumniin
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

# Jos haulla ei l�ytyny mit��n, ollaan palattu t�lle sivulle virheparametrilla.
if (isset($virhe)) {
	$errors[] = t("Ei l�ytynyt. Hae uudestaan.");
}

if (isset($submit)) {
	switch($submit) {
		case 'ok':
			# Haettu v�hint��n yhdell� kent�ll�
			if (empty($data['viivakoodi']) and empty($data['tuotenumero']) and empty($data['ostotilaus'])) {
				$errors[] = t("V�hint��n yksi kentt� on sy�tett�v�");
				break;
			}

			$data['manuaalisesti_syotetty_ostotilausnro'] = $data['ostotilaus'] != '' ? 1 : 0;

			# Rakennetaan parametrit kentist�
			$url = http_build_query($data);

			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tuotteella_useita_tilauksia.php?{$url}'>"; exit();
			break;
		case 'takaisin':
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tulouta.php'>"; exit();
		   	break;
		default:
			$errors[] = t("Yll�tt�v� virhe");
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

	$(document).ready(function() {
		$('#viivakoodi').on('keyup', function() {
			// Autosubmit vain jos on sy�tetty tarpeeksi pitk� viivakoodi
			if ($('#viivakoodi').val().length > 8) {
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
