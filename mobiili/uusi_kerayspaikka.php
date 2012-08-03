<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

$alusta_tunnus = (int) $alusta_tunnus;
$liitostunnus = (int) $liitostunnus;
$selected_row = (int) $selected_row;

$error = array(
	'kerayspaikka' => ''
);

if (isset($submit) and trim($submit) != '') {

	$data = array(
		'alusta_tunnus' => $alusta_tunnus,
		'liitostunnus' => $liitostunnus,
		'selected_row' => $selected_row
	);

	$url = http_build_query($data);

	if ($submit == 'cancel') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?{$url}'>";
		exit;
	}
}

$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, "", "", "", $selected_row);
$row = mysql_fetch_assoc($res);

$oletuspaikka_chk = "checked";

$onko_suoratoimitus_res = onko_suoratoimitus($selected_row);

if ($row_suoratoimitus = mysql_fetch_assoc($onko_suoratoimitus_res)) {
	if ($row_suoratoimitus["suoraan_laskutukseen"] == "") $oletuspaikka_chk = '';
}

if (isset($submit) and trim($submit) != '' and $submit == 'submit') {

	# Ei saa olla tyhji� kentti�
	if ($hyllyalue == '' and $hyllynro == '' and $hyllyvali == '' and $hyllytaso == '') {
		$error['kerayspaikka'] = t("Hyllypaikka ei saa olla tyhj�", $browkieli).'.';
	}
	elseif ($hylly_ok = tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso)) {
		$oletus = $oletuspaikka != '' ? 'X' : '';

		$hylly = array(
				"hyllyalue" => $hyllyalue,
				"hyllynro" 	=> $hyllynro,
				"hyllyvali" => $hyllyvali,
				"hyllytaso" => $hyllytaso
			);

		# Tarkistetaan onko sy�tetty hyllypaikka jo t�lle tuotteelle
		$tuotteen_oma_hyllypaikka = "	SELECT * FROM tuotepaikat
										WHERE tuoteno='$row[tuoteno]'
										AND hyllyalue='$hyllyalue'
										AND hyllynro='$hyllynro'
										AND hyllyvali='$hyllyvali'
										AND hyllytaso='$hyllytaso'";
		$oma_paikka = mysql_query($tuotteen_oma_hyllypaikka);

		# Jos sy�tetty� paikkaa ei ole t�m�n tuotteen, lis�t��n uusi tuotepaikka
		if (mysql_num_rows($oma_paikka) == 0) {
			#lisaa_tuotepaikka($row['tuoteno'], $row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso'], 'Saapumisessa', $oletus);
			lisaa_tuotepaikka($row['tuoteno'], $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, 'Saapumisessa', $oletus);
		}

		# P�ivitet��n oletuspaikat jos tehd��n t�st� oletuspaikka
		if($oletus != '') {
			# Asetetaan oletuspaikka uusiksi
			$paivitetty_paikka = paivita_oletuspaikka($row['tuoteno'], $hylly);
		}

		# Asetetaan tuotepaikka tilausriville
		paivita_tilausrivin_hylly($selected_row, $hylly);

		# Palataan edelliselle sivulle
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?{$url}'>";
		exit;
	}
	else {
		$error['kerayspaikka'] = t("Varaston tuotepaikkaa ei ole perustettu", $browkieli).'.';
	}
}

include("kasipaate.css");

echo "<div class='header'><h1>",t("UUSI KER�YSPAIKKA", $browkieli),"</h1></div>";
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
			<th>",t("Ker�yspaikka", $browkieli),"</th>
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
			<th>",t("V�li", $browkieli),"</th>
			<td><input type='text' name='hyllyvali' value='' /></td>
		</tr>
		<tr>
			<th>",t("Taso", $browkieli),"</td>
			<td><input type='text' name='hyllytaso' value='' /></th>
		</tr>
	</table>

	<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
	<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
	<input type='hidden' name='selected_row' value='{$selected_row}' />
</div>";

echo "<div class='controls'>
	<button name='submit' value='submit' onclick='submit();'>",t("Perusta", $browkieli),"</button>
	<button id='takaisin' name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
	</form>
</div>";

require('inc/footer.inc');