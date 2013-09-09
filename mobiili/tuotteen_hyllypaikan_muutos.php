<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (!isset($tuotepaikan_tunnus)) $tuotepaikan_tunnus = 0;
if (!isset($mista_koodi)) $mista_koodi = '';
if (!isset($minne_koodi)) $minne_koodi = '';
if (!isset($minne_hyllypaikka)) $minne_hyllypaikka = '';

$errors = array();

if ($tuotepaikan_tunnus == 0) {
	echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllysiirrot.php'>";
	exit();
}

$query = "	SELECT tuotepaikat.*, tuote.yksikko
			FROM tuotepaikat
			JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno)
			WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
			AND tuotepaikat.tunnus = '{$tuotepaikan_tunnus}'";
$res = pupe_query($query);
$row = mysql_fetch_assoc($res);

if (isset($submit)) {
	switch($submit) {
		case 'ok':

			# Tarkistetaan koodi
			$options = array('varmistuskoodi' => $mista_koodi);
			if (!is_numeric($mista_koodi) or !tarkista_varaston_hyllypaikka($row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso'], $options)) {
				$errors[] = t("Virheellinen varmistuskoodi")." ({$mista_koodi})";
			}

			if (empty($minne_hyllypaikka)) {
				$errors[] = t("Virheellinen hyllypaikka");
			}

			if (count($errors) == 0) {

				// Parsitaan uusi tuotepaikka
				// Jos tuotepaikka on luettu viivakoodina, muotoa (C21 045) tai (21C 03V)
				if (preg_match('/^([a-zÂ‰ˆ#0-9]{2,4} [a-zÂ‰ˆ#0-9]{2,4})/i', $minne_hyllypaikka)) {

					// Pilkotaan viivakoodilla luettu tuotepaikka v‰lilyˆnnist‰
					list($alku, $loppu) = explode(' ', $minne_hyllypaikka);

					// M‰ts‰t‰‰n numerot ja kirjaimet erilleen
					preg_match_all('/([0-9]+)|([a-z]+)/i', $alku, $alku);
					preg_match_all('/([0-9]+)|([a-z]+)/i', $loppu, $loppu);

					// Hyllyn tiedot oikeisiin muuttujiin
					$hyllyalue = $alku[0][0];
					$hyllynro  = $alku[0][1];
					$hyllyvali = $loppu[0][0];
					$hyllytaso = $loppu[0][1];

					// Kaikkia tuotepaikkoja ei pystyt‰ parsimaan
					if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
						$errors[] = t("Tuotepaikan haussa virhe, yrit‰ syˆtt‰‰ tuotepaikka k‰sin") . " ({$minne_hyllypaikka})";
					}
				}
				// Tuotepaikka syˆtetty manuaalisesti (C-21-04-5) tai (C 21 04 5) tai (E 14 21 5) tai (2 P 58 D)
				elseif (strstr($minne_hyllypaikka, '-') or strstr($minne_hyllypaikka, ' ')) {
					// Parsitaan tuotepaikka omiin muuttujiin (erotelto v‰lilyˆnnill‰)
					if (preg_match('/\w+\s\w+\s\w+\s\w+/i', $minne_hyllypaikka)) {
						list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode(' ', $minne_hyllypaikka);
					}
					// (erotelto v‰liviivalla)
					elseif (preg_match('/\w+-\w+-\w+-\w+/i', $minne_hyllypaikka)) {
						list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode('-', $minne_hyllypaikka);
					}

					// Ei saa olla tyhji‰ kentti‰
					if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
						$errors[] = t("Virheellinen tuotepaikka") . ". ({$minne_hyllypaikka})";
					}
				}
				else {
					$errors[] = t("Virheellinen tuotepaikka, yrit‰ syˆtt‰‰ tuotepaikka k‰sin") . " ({$minne_hyllypaikka})";
				}

				// Tarkistetaan ett‰ tuotepaikka on olemassa
				if (count($errors) == 0 and !tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso)) {
					$errors[] = t("Varaston tuotepaikkaa (%s-%s-%s-%s) ei ole perustettu", "", $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso).'.';
				}

				if (count($errors) == 0) {
					$options = array('varmistuskoodi' => $minne_koodi);
					if (!is_numeric($minne_koodi) or !tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $options)) {
						$errors[] = t("Virheellinen varmistuskoodi")." ({$minne_koodi})";
					}
				}
			}

			if (count($errors) == 0) {

			}

			break;
		default:
			$errors[] = t("Yll‰tt‰v‰ virhe");
			break;
	}
}

$url_lisa = "?tuotenumero=".urlencode($row['tuoteno']);

### UI ###
echo "<div class='header'>
	<button onclick='window.location.href=\"tuotteella_useita_tuotepaikkoja.php{$url_lisa}\"' class='button left'><img src='back2.png'></button>
	<h1>",t("HYLLYPAIKAN MUUTOS"), "</h1></div>";

# Virheet
if (count($errors) > 0) {
	echo "<span class='error'>";
	foreach($errors as $virhe) {
		echo "{$virhe}<br>";
	}
	echo "</span>";
}

echo "<form name='form1' method='post' action=''>";
echo "<input type='hidden' name='tuotepaikan_tunnus' value='{$row['tunnus']}' />";
echo "<table>";

echo "<tr>";
echo "<th>",t("Tuoteno"),"</th>";
echo "<td>{$row['tuoteno']}</td>";
echo "</tr>";

echo "<tr>";
echo "<th>",t("M‰‰r‰"),"</th>";
echo "<td>{$row['saldo']} {$row['yksikko']}</td>";
echo "</tr>";

echo "<tr>";
echo "<th>",t("Mist‰ hyllypaikka"),"</th>";
echo "<td>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>";
echo "</tr>";
echo "<tr>";
echo "<th>",t("Koodi"),"</th>";
echo "<td><input type='text' name='mista_koodi' id='mista_koodi' value='{$mista_koodi}' size='7' /></td>";
echo "</tr>";

echo "<tr>";
echo "<th>",t("Minne hyllypaikka"),"</th>";
echo "<td><input type='text' name='minne_hyllypaikka' value='{$minne_hyllypaikka}' /></td>";
echo "</tr>";
echo "<tr>";
echo "<th>",t("Koodi"),"</th>";
echo "<td><input type='text' name='minne_koodi' value='{$minne_koodi}' size='7' /></td>";
echo "</tr>";

echo "</table>";
echo "<div class='controls'>";
echo "<button name='submit' class='button left' id='haku_nappi' value='ok' onclick='submit();' class='button'>",t("OK"),"</button>";
echo "<button name='submit' class='button right' id='submit' value='kerayspaikka' onclick='submit();'>",t("UUSI HYLLYPAIKKA"),"</button>";
echo "</div>";
echo "</form>";

echo "<input type='button' id='myHiddenButton' visible='false' onclick='javascript:doFocus();' width='1px' style='display:none'>";
echo "<script type='text/javascript'>

		$(document).ready(function() {
			$('#minne_koodi').on('keyup', function() {
				// Autosubmit vain jos on syˆtetty tarpeeksi pitk‰ viivakoodi
				if ($('#minne_koodi').val().length > 1) {
					document.getElementById('haku_nappi').click();
				}
			});
		});

		function doFocus() {
		    var focusElementId = 'mista_koodi';
		    var textBox = document.getElementById(focusElementId);
		    textBox.focus();
	    }

		function clickButton() {
		   document.getElementById('myHiddenButton').click();
		}

		setTimeout('clickButton()', 1000);

	</script>";
