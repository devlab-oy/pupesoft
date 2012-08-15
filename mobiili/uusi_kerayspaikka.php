<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

$alusta_tunnus = (int) $alusta_tunnus;
$liitostunnus = (int) $liitostunnus;
$tilausrivi = (int) $tilausrivi;

$error = array(
	'kerayspaikka' => ''
);

if (isset($submit) and trim($submit) != '') {

	$data = array(
		'alusta_tunnus' => $alusta_tunnus,
		'liitostunnus' => $liitostunnus,
		'tilausrivi' => $tilausrivi
	);

	$url = http_build_query($data);

	if ($submit == 'cancel') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?{$url}'>";
		exit;
	}
}

# Jos suuntalava tiedetään
if (!empty($alusta_tunnus)) {
	$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, "", "", "", $tilausrivi);
	$row = mysql_fetch_assoc($res);
}
else {
	$query = "	SELECT
				tilausrivi.*,
				tuotteen_toimittajat.toim_tuoteno
				FROM tilausrivi
				LEFT JOIN tuotteen_toimittajat on (tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno)
				WHERE tilausrivi.tunnus='{$tilausrivi}'
				AND tilausrivi.yhtio='{$kukarow['yhtio']}'";
	$row = mysql_fetch_assoc(pupe_query($query));
}

$oletuspaikka_chk = "checked";

$onko_suoratoimitus_res = onko_suoratoimitus($tilausrivi);

if ($row_suoratoimitus = mysql_fetch_assoc($onko_suoratoimitus_res)) {
	if ($row_suoratoimitus["suoraan_laskutukseen"] == "") $oletuspaikka_chk = '';
}

if (isset($submit) and trim($submit) != '' and $submit == 'submit') {

	# Ei saa olla tyhjiä kenttiä
	if ($hyllyalue == '' and $hyllynro == '' and $hyllyvali == '' and $hyllytaso == '') {
		$error['kerayspaikka'] = t("Hyllypaikka ei saa olla tyhjä", $browkieli).'.';
	}
	elseif ($hylly_ok = tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso)) {
		$oletus = $oletuspaikka != '' ? 'X' : '';

		$hylly = array(
				"hyllyalue" => $hyllyalue,
				"hyllynro" 	=> $hyllynro,
				"hyllyvali" => $hyllyvali,
				"hyllytaso" => $hyllytaso
			);

		# Tarkistetaan onko syötetty hyllypaikka jo tälle tuotteelle
		$tuotteen_oma_hyllypaikka = "	SELECT * FROM tuotepaikat
										WHERE tuoteno='$row[tuoteno]'
										AND hyllyalue='$hyllyalue'
										AND hyllynro='$hyllynro'
										AND hyllyvali='$hyllyvali'
										AND hyllytaso='$hyllytaso'";
		$oma_paikka = mysql_query($tuotteen_oma_hyllypaikka);

		# Jos syötettyä paikkaa ei ole tämän tuotteen, lisätään uusi tuotepaikka
		if (mysql_num_rows($oma_paikka) == 0) {
			#lisaa_tuotepaikka($row['tuoteno'], $row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso'], 'Saapumisessa', $oletus);
			lisaa_tuotepaikka($row['tuoteno'], $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, 'Saapumisessa', $oletus);
		}

		# Päivitetään oletuspaikat jos tehdään tästä oletuspaikka
		if($oletus != '') {
			# Asetetaan oletuspaikka uusiksi
			$paivitetty_paikka = paivita_oletuspaikka($row['tuoteno'], $hylly);
		}

		# Asetetaan tuotepaikka tilausriville
		paivita_tilausrivin_hylly($tilausrivi, $hylly);

		# Palataan edelliselle sivulle
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?{$url}'>";
		exit;
	}
	else {
		$error['kerayspaikka'] = t("Varaston tuotepaikkaa ei ole perustettu", $browkieli).'.';
	}
}

include("kasipaate.css");

echo "<div class='header'><h1>",t("UUSI KERÄYSPAIKKA", $browkieli),"</h1></div>";
echo "<span class='error'>{$error['kerayspaikka']}</span>";

echo "<div class='main'>

<form name='uusipaikkaformi' method='post' action=''>
	<table>
		<tr>
			<th>",t("Tuote", $browkieli),"</th>
			<td colspan='3'>{$row['tuoteno']}</td>
		</tr>
		<tr>
			<th>",t("Toim. Tuotekoodi", $browkieli),"</th>
			<td colspan='3'>{$row['toim_tuoteno']}</td>
		</tr>
		<tr>
			<th>",t("Keräyspaikka", $browkieli),"</th>
			<td colspan='3'>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>
		</tr>
		<tr>
			<th>",t("Alue", $browkieli),"</th>
			<td><input type='text' name='hyllyalue' value='' /></td>
		</tr>
		<tr>
			<th>",t("Nro", $browkieli),"</td>
			<td><input type='text' name='hyllynro' value='' /></th>
		</tr>
			<th>",t("Väli", $browkieli),"</th>
			<td><input type='text' name='hyllyvali' value='' /></td>
		</tr>
		<tr>
			<th>",t("Taso", $browkieli),"</td>
			<td><input type='text' name='hyllytaso' value='' /></th>
		</tr>
	</table>

	<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
	<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
	<input type='hidden' name='tilausrivi' value='{$tilausrivi}' />
</div>";

echo "<div class='controls'>
	<button name='submit' value='submit' onclick='submit();'>",t("Perusta", $browkieli),"</button>
	<button id='takaisin' name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
	</form>
</div>";

require('inc/footer.inc');