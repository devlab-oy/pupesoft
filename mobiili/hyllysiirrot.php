<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (!isset($errors)) $errors = array();
if (!isset($hyllypaikka)) $hyllypaikka = "";

if (!isset($tuotenumero)) $tuotenumero = "";

if (isset($submit)) {
	switch($submit) {
		case 'ok':

			$tuotenumero = $data['tuotenumero'];
			$hyllypaikka = $data['hyllypaikka'];

			# Haettu v�hint��n yhdell� kent�ll�
			if (empty($data['viivakoodi']) and empty($data['tuotenumero']) and empty($data['hyllypaikka'])) {
				$errors[] = t("V�hint��n yksi kentt� on sy�tett�v�");
				break;
			}

			if (!empty($data['hyllypaikka'])) {

				// Parsitaan uusi tuotepaikka
				// Jos tuotepaikka on luettu viivakoodina, muotoa (C21 045) tai (21C 03V)
				if (preg_match('/^([a-z���#0-9]{2,4} [a-z���#0-9]{2,4})/i', $data['hyllypaikka'])) {

					// Pilkotaan viivakoodilla luettu tuotepaikka v�lily�nnist�
					list($alku, $loppu) = explode(' ', $data['hyllypaikka']);

					// M�ts�t��n numerot ja kirjaimet erilleen
					preg_match_all('/([0-9]+)|([a-z]+)/i', $alku, $alku);
					preg_match_all('/([0-9]+)|([a-z]+)/i', $loppu, $loppu);

					// Hyllyn tiedot oikeisiin muuttujiin
					$hyllyalue = $alku[0][0];
					$hyllynro  = $alku[0][1];
					$hyllyvali = $loppu[0][0];
					$hyllytaso = $loppu[0][1];

					// Kaikkia tuotepaikkoja ei pystyt� parsimaan
					if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
						$errors[] = t("Tuotepaikan haussa virhe, yrit� sy�tt�� tuotepaikka k�sin") . " ({$data['hyllypaikka']})";
					}
				}
				// Tuotepaikka sy�tetty manuaalisesti (C-21-04-5) tai (C 21 04 5) tai (E 14 21 5)
				elseif (strstr($data['hyllypaikka'], '-') or strstr($data['hyllypaikka'], ' ')) {
					// Parsitaan tuotepaikka omiin muuttujiin (erotelto v�lily�nnill�)
					if (preg_match('/\w+\s\w+\s\w+\s\w+/i', $data['hyllypaikka'])) {
						list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode(' ', $data['hyllypaikka']);
					}
					// (erotelto v�liviivalla)
					elseif (preg_match('/\w+-\w+-\w+-\w+/i', $data['hyllypaikka'])) {
						list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode('-', $data['hyllypaikka']);
					}

					// Ei saa olla tyhji� kentti�
					if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
						$errors[] = t("Virheellinen tuotepaikka") . ". ({$data['hyllypaikka']})";
					}
				}
				else {
					$errors[] = t("Virheellinen tuotepaikka, yrit� sy�tt�� tuotepaikka k�sin") . " ({$data['hyllypaikka']})";
				}

				// Tarkistetaan ett� tuotepaikka on olemassa
				if (count($errors) == 0 and !tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso)) {
					$errors[] = t("Varaston tuotepaikkaa (%s-%s-%s-%s) ei ole perustettu", "", $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso).'.';
				}

				if (count($errors) == 0) {
					$data['hyllyalue'] 	= $hyllyalue;
					$data['hyllynro'] 	= $hyllynro;
					$data['hyllyvali'] 	= $hyllyvali;
					$data['hyllytaso'] 	= $hyllytaso;
				}
			}

			if (count($errors) == 0) {

				# Rakennetaan parametrit kentist�
				$url = http_build_query($data);

				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tuotteella_useita_tuotepaikkoja.php?{$url}'>"; exit();
			}

			break;
		default:
			$errors[] = t("Yll�tt�v� virhe");
			break;
	}
}

### UI ###
echo "
<div class='header'>
	<button onclick='window.location.href=\"index.php\"' class='button left'><img src='back2.png'></button>
	<h1>",t("HYLLYSIIRROT"),"</h1>
</div>";

// Virheet
if (count($errors) > 0) {
	echo "<span class='error'>";
	foreach($errors as $virhe) {
		echo "{$virhe}<br>";
	}
	echo "</span>";
}

echo "<div class='main'>
<form method='post' action=''>
<table>
	<tr>
		<th><label for='viivakoodi'>",t("Viivakoodi"),"</label></th>
		<td><input type='text' id='viivakoodi' name='data[viivakoodi]' /><td>
	</tr>
	<tr>
		<th><label for='tuotenumero'>",t("Tuotenumero"),"</label></th>
		<td><input type='text' id='tuotenumero' name='data[tuotenumero]' value='{$tuotenumero}' /><td>
	</tr>
	<tr>
		<th><label for='hyllypaikka'>",t("Hyllypaikka"),"</label></th>
		<td><input type='text' id='hyllypaikka' name='data[hyllypaikka]' value='{$hyllypaikka}' /><td>
	</tr>
</table>
</div>";

echo "<div class='controls'>
	<button name='submit' id='haku_nappi' value='ok' onclick='submit();' class='button'>",t("OK"),"</button>
</form>
</div>";

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
		    var focusElementId = 'hyllypaikka';
		    var textBox = document.getElementById(focusElementId);
		    textBox.focus();
	    }

		function clickButton() {
		   document.getElementById('myHiddenButton').click();
		}

		setTimeout('clickButton()', 1000);

	</script>";

require('inc/footer.inc');
