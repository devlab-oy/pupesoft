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
if (!isset($nakyma)) $nakyma = '';
if (!isset($siirrettava_yht)) $siirrettava_yht = 0;
if (!isset($siirretty)) $siirretty = 0;

$errors = array();

if ($tuotepaikan_tunnus == 0 or $siirretty) {
	echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllysiirrot.php'>";
	exit();
}

if (!function_exists('laske_siirrettava_maara')) {
	function laske_siirrettava_maara($row) {
		global $kukarow, $yhtiorow;

		$siirrettavat_rivit = array();

		// Lets lock up
		$query = "	LOCK TABLES
					lasku READ,
					lasku AS l1 READ,
					tilausrivi READ,
					tilausrivi AS t1 READ,
					tilausrivi AS t2 READ,
					tuote READ,
					tuotepaikat READ,
					varastopaikat READ";
		$res = pupe_query($query);

		list($saldo, $hyllyssa, $siirrettava_yht, $devnull) = saldo_myytavissa($row['tuoteno'], '', 0, '', $row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso']);

		$query = "	(SELECT t1.tunnus, t1.otunnus, t1.varattu
					FROM lasku AS l1
					JOIN tilausrivi AS t1 ON (
						t1.yhtio = l1.yhtio AND
						t1.otunnus = l1.tunnus AND
						t1.tuoteno = '{$row['tuoteno']}' AND
						t1.hyllyalue = '{$row['hyllyalue']}' AND
						t1.hyllynro = '{$row['hyllynro']}' AND
						t1.hyllyvali = '{$row['hyllyvali']}' AND
						t1.hyllytaso = '{$row['hyllytaso']}' AND
						t1.tyyppi IN ('L','G') AND
						t1.var IN ('','H') AND
						t1.keratty = '' AND
						t1.uusiotunnus = 0 AND
						t1.kpl = 0 AND
						t1.varattu > 0
					)
					WHERE l1.yhtio = '{$kukarow['yhtio']}'
					AND l1.tila = 'N'
					AND l1.alatila IN ('','A'))
					UNION
					(SELECT t2.tunnus, t2.otunnus, t2.jt + t2.varattu AS varattu
					FROM tilausrivi AS t2
					WHERE t2.yhtio = '{$kukarow['yhtio']}'
					AND	t2.tuoteno = '{$row['tuoteno']}'
					AND	t2.hyllyalue = '{$row['hyllyalue']}'
					AND	t2.hyllynro = '{$row['hyllynro']}'
					AND	t2.hyllyvali = '{$row['hyllyvali']}'
					AND	t2.hyllytaso = '{$row['hyllytaso']}'
					AND	t2.tyyppi IN ('L','G')
					AND	t2.var = 'J'
					AND	t2.keratty = ''
					AND	t2.uusiotunnus = 0
					AND	t2.kpl = 0
					AND	t2.jt + t2.varattu > 0)";
		$result = pupe_query($query);

		while ($saldorow = mysql_fetch_assoc($result)) {

			$siirrettava_yht += $saldorow['varattu'];
			$siirrettavat_rivit[] = $saldorow['tunnus'];
		}

		// poistetaan lukko
		$query = "UNLOCK TABLES";
		$res   = pupe_query($query);

		return array($siirrettava_yht, $siirrettavat_rivit);
	}
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

				if (count($errors) == 0) {

					$query = "	SELECT tunnus
								FROM tuotepaikat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '{$row['tuoteno']}'
								AND hyllyalue = '{$hyllyalue}'
								AND hyllynro = '{$hyllynro}'
								AND hyllyvali = '{$hyllyvali}'
								AND hyllytaso = '{$hyllytaso}'";
					$chk_res = pupe_query($query);

					if (mysql_num_rows($chk_res) == 0) $errors[] = t("Tuotepaikkaa (%s-%s-%s-%s) ei ole perustettu tuotteelle", "", $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso).'.';
				}

				// Tarkistetaan ett‰ tuotepaikka on olemassa
				if (count($errors) == 0 and !tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso)) {
					$errors[] = t("Tuotepaikkaa (%s-%s-%s-%s) ei ole perustettu varaston hyllypaikkoihin", "", $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso).'.';
				}

				if (count($errors) == 0) {
					$options = array('varmistuskoodi' => $minne_koodi);
					if (!is_numeric($minne_koodi) or !tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $options)) {
						$errors[] = t("Virheellinen varmistuskoodi")." ({$minne_koodi})";
					}
				}
			}

			if (count($errors) == 0) {

				list($siirrettava_yht, $siirrettavat_rivit) = laske_siirrettava_maara($row);

				$query = "	SELECT tuotepaikat.*, tuote.yksikko
							FROM tuotepaikat
							JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno)
							WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
							AND tuotepaikat.tuoteno = '{$row['tuoteno']}'
							AND tuotepaikat.hyllyalue = '{$hyllyalue}'
							AND tuotepaikat.hyllynro = '{$hyllynro}'
							AND tuotepaikat.hyllyvali = '{$hyllyvali}'
							AND tuotepaikat.hyllytaso = '{$hyllytaso}'";
				$res = pupe_query($query);
				$minnerow = mysql_fetch_assoc($res);

				$params = array(
					'kappaleet' => $siirrettava_yht,
					'lisavaruste' => '',
					'tuoteno' => $row['tuoteno'],
					'tuotepaikat_tunnus_otetaan' => $row['tunnus'],
					'tuotepaikat_tunnus_siirretaan' => $minnerow['tunnus'],
					'mistarow' => $row,
					'minnerow' => $minnerow,
					'sarjano_array' => array(),
					'selite' => '',
				);

				hyllysiirto($params);

				if (count($siirrettavat_rivit) > 0) {

					foreach ($siirrettavat_rivit as $siirrettavat_rivi) {

						$query = "	UPDATE tilausrivi SET
									hyllyalue = '{$hyllyalue}',
									hyllynro = '{$hyllynro}',
									hyllyvali = '{$hyllyvali}',
									hyllytaso = '{$hyllytaso}'
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = {$siirrettavat_rivi}";
						pupe_query($query);
					}
				}

				$nakyma = 'siirr‰';
			}

			break;
		default:
			$errors[] = t("Yll‰tt‰v‰ virhe");
			break;
	}
}

$url_lisa = "?tuotenumero=".urlencode($row['tuoteno']);

### UI ###
echo "<div class='header'>";
if ($nakyma == '') echo "<button onclick='window.location.href=\"tuotteella_useita_tuotepaikkoja.php{$url_lisa}\"' class='button left'><img src='back2.png'></button>";
echo "<h1>",t("HYLLYPAIKAN MUUTOS"), "</h1></div>";

echo "<form name='form1' method='post' action=''>";
echo "<input type='hidden' name='tuotepaikan_tunnus' value='{$row['tunnus']}' />";

if ($nakyma == 'siirr‰') {

	echo "<input type='hidden' name='siirretty' value='1' />";

	echo "<span class='message'>",t("Siirr‰ %d tuotetta", "", $siirrettava_yht),"</span>";

	echo "<div class='controls'>";
	echo "<button name='submit' class='button left' id='haku_nappi' value='ok' onclick='submit();' class='button'>",t("OK"),"</button>";
	echo "</div>";

}
else {

	# Virheet
	if (count($errors) > 0) {
		echo "<span class='error'>";
		foreach($errors as $virhe) {
			echo "{$virhe}<br>";
		}
		echo "</span>";
	}

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
	echo "<td><input type='text' id='minne_koodi' name='minne_koodi' value='{$minne_koodi}' size='7' /></td>";
	echo "</tr>";

	echo "</table>";
	echo "<div class='controls'>";
	echo "<button name='submit' class='button left' id='haku_nappi' value='ok' onclick='submit();' class='button'>",t("OK"),"</button>";
	echo "<button name='submit' class='button right' id='submit' value='kerayspaikka' onclick='submit();'>",t("UUSI HYLLYPAIKKA"),"</button>";
	echo "</div>";
	echo "</form>";

	echo "<input type='button' id='myHiddenButton' visible='false' onclick='javascript:doFocus();' width='1px' style='display:none'>";
	echo "<script type='text/javascript'>

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
}

require('inc/footer.inc');
