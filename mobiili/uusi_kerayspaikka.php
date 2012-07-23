<?php
echo "<meta name='viewport' content='width=device-width,height=device-height, user-scalable=no'/>";

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

$oletuspaikka_chk = "";

$onko_suoratoimitus_res = onko_suoratoimitus($selected_row);

if ($row_suoratoimitus = mysql_fetch_assoc($onko_suoratoimitus_res)) {
	if ($row_suoratoimitus["suoraan_laskutukseen"] == "") $oletuspaikka_chk = '';
}

if (isset($submit) and trim($submit) != '' and $submit == 'submit') {

	# Tarkista että hyllypaikka on olemmassa
	$kaikki_ok = tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso);
	$kaikki_ok = true;

	if ($hyllyalue == '' and $hyllynro == '' and $hyllyvali == '' and $hyllytaso == '') {
		$error['kerayspaikka'] = t("Hyllypaikka ei saa olla tyhjä", $browkieli).'.';
	}
	elseif ($kaikki_ok) {

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
			echo "lisaa_tuotepaikka!<br>";
			lisaa_tuotepaikka($row['tuoteno'], $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, 'Saapumisessa', $oletus);
		}

		# Päivitetään oletuspaikat jos tehdään tästä oletuspaikka
		if($oletus != '') {
			# Asetetaan oletuspaikka uusiksi
			echo "Oletuspaikka uusiksi!";
			$paivitetty_paikka = paivita_oletuspaikka($row['tuoteno'], $hylly);
			echo "Paivitetty paikka: ".$paivitetty_paikka;
		}

		# Asetetaan tuotepaikka tilausriville
		paivita_tilausrivin_hylly($selected_row, $hylly);
		echo "päivitetään tilausrivin hyllypaikka";

		# Palataan edelliselle sivulle
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?{$url}'>";
		exit;
	}
	else {
		$error['kerayspaikka'] = t("Varaston tuotepaikkaa ei ole perustettu", $browkieli).'.';
	}
}

include("kasipaate.css");
echo "
	<style type='text/css'>
	<!--
		A, A:visited	{color: #c0c0c0; text-decoration:none;}
		.error		{color: #ff6666;}
	-->
	</style>

	<table border='0'>
		<tr>
			<td><h1>",t("UUSI KERÄYSPAIKKA", $browkieli),"</h1>
				<form name='uusipaikkaformi' method='post' action=''>
				<table>
					<tr>
						<td>",t("Tuote", $browkieli),"</td>
						<td colspan='3'>{$row['tuoteno']}</td>
					</tr>
					<tr>
						<td>",t("Toim. Tuotekoodi", $browkieli),"</td>
						<td colspan='3'>{$row['toim_tuoteno']}</td>
					</tr>
					<tr>
						<td>",t("Keräyspaikka", $browkieli),"</td>
						<td colspan='3'>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>
					</tr>
					<tr>
						<td>",t("Alue", $browkieli),"</td>
						<td><input type='text' name='hyllyalue' value='' /></td>
					</tr>
					<tr>
						<td>",t("Nro", $browkieli),"</td>
						<td><input type='text' name='hyllynro' value='' /></td>
					</tr>
						<td>",t("Väli", $browkieli),"</td>
						<td><input type='text' name='hyllyvali' value='' /></td>
					<tr>
					</tr>
					<tr>
						<td>",t("Taso", $browkieli),"</td>
						<td><input type='text' name='hyllytaso' value='' /></td>
					</tr>
					</table>
					<table>
					<tr>
						<td colspan='2'>",t("Tee tästä oletuspaikka", $browkieli)," <input type='checkbox' name='oletuspaikka' checked /></td>
					</tr>
					<tr>
						<td nowrap>
							<button name='submit' value='submit' onclick='submit();'>",t("Perusta", $browkieli),"</button>
						</td>
						<td nowrap>
							<button name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
						</td>
					</tr>
					<tr><td>&nbsp;</td></tr>
				</table>
				<span class='error'>{$error['kerayspaikka']}</span>
				<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
				<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
				<input type='hidden' name='selected_row' value='{$selected_row}' />
				</form>
			</td>
		</tr>
	</table>";

#require('inc/footer.inc');