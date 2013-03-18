<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

$alusta_tunnus = (int) $alusta_tunnus;
$liitostunnus = (int) $liitostunnus;
$tilausrivi = (int) $tilausrivi;

$data = array(
	'alusta_tunnus' => $alusta_tunnus,
	'liitostunnus' => $liitostunnus,
	'tilausrivi' => $tilausrivi,
	'ostotilaus' => $ostotilaus,
	'saapuminen' => $saapuminen
);
$url = http_build_query($data);

# Virheet
$errors = array();

# Suuntalavan kanssa
if (!empty($alusta_tunnus)) {
	$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, "", "", "", $tilausrivi);
	$row = mysql_fetch_assoc($res);
}
# Ilman suuntalavaa
else {
	$query = "	SELECT
				tilausrivi.*,
				tuotteen_toimittajat.toim_tuoteno
				FROM tilausrivi
				LEFT JOIN tuotteen_toimittajat on (tuotteen_toimittajat.tuoteno=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno)
				WHERE tilausrivi.tunnus='{$tilausrivi}'
				AND tilausrivi.yhtio='{$kukarow['yhtio']}'";
	$row = mysql_fetch_assoc(pupe_query($query));
}

if (isset($submit) and trim($submit) != '') {

	switch ($submit) {
		case 'submit':

			// Parsitaan uusi tuotepaikka
			// Jos tuotepaikka on luettu viivakoodina, muotoa (C21 045) tai (21C 03V)
			if (preg_match('/^([a-zåäö#0-9]{2,4} [a-zåäö#0-9]{2,4})/i', $tuotepaikka)) {

				// Pilkotaan viivakoodilla luettu tuotepaikka välilyönnistä
				list($alku, $loppu) = explode(' ', $tuotepaikka);

				// Mätsätään numerot ja kirjaimet erilleen
				preg_match_all('/([0-9]+)|([a-z]+)/', $alku, $alku);
				preg_match_all('/([0-9]+)|([a-z]+)/', $loppu, $loppu);

				// Hyllyn tiedot oikeisiin muuttujiin
				$hyllyalue = $alku[0][0];
				$hyllynro = $alku[0][1];
				$hyllyvali = $loppu[0][0];
				$hyllytaso = $loppu[0][1];

				// Kaikkia tuotepaikkoja ei pystytä parsimaan
				if (empty($hyllyalue) or empty($hyllynro) or empty($hyllyvali) or empty($hyllytaso)) {
					$errors[] = t("Tuotepaikan haussa virhe, yritä syöttää tuotepaikka käsin") . " ($tuotepaikka)";
				}
			}
			// Tuotepaikka syötetty manuaalisesti (C-21-04-5) tai (C 21 04 5)
			elseif (strstr($tuotepaikka, '-') or strstr($tuotepaikka, ' ')) {
				// Parsitaan tuotepaikka omiin muuttujiin (erotelto välilyönnillä)
				if (preg_match('/\w+\s\w+\s\w+\s\w+/i', $tuotepaikka)) {
					list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode(' ', $tuotepaikka);
				}
				// (erotelto väliviivalla)
				elseif (preg_match('/\w+-\w+-\w+-\w+/i', $tuotepaikka)) {
					list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode('-', $tuotepaikka);
				}

				// Ei saa olla tyhjiä kenttiä
				if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
					$errors[] = t("Virheellinen tuotepaikka") . ". ($tuotepaikka)";
				}
			}
			else {
				$errors[] = t("Virheellinen tuotepaikka, yritä syöttää tuotepaikka käsin") . " ($tuotepaikka)";
			}

			// Tarkistetaan että tuotepaikka on olemassa
			if (count($errors) == 0 and !tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso)) {
				$errors[] = t("Varaston tuotepaikkaa ei ole perustettu").'.';
			}

			if (count($errors) == 0) {
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
					lisaa_tuotepaikka($row['tuoteno'], $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, 'Saapumisessa', $oletus, $halytysraja, $tilausmaara);
				}

				# Päivitetään oletuspaikat jos tehdään tästä oletuspaikka
				if($oletus != '') {
					# Asetetaan oletuspaikka uusiksi
					$paivitetty_paikka = paivita_oletuspaikka($row['tuoteno'], $hylly);
				}

				# Asetetaan tuotepaikka tilausriville
				paivita_tilausrivin_hylly($tilausrivi, $hylly);

				# Palataan edelliselle sivulle
				if(isset($hyllytys)) {
					echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys.php?{$url}'>"; exit();
				} else {
					echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?{$url}'>"; exit;
				}
			}

			break;
	}

}

$oletuspaikka_chk = "checked";

$onko_suoratoimitus_res = onko_suoratoimitus($tilausrivi);

if ($row_suoratoimitus = mysql_fetch_assoc($onko_suoratoimitus_res)) {
	if ($row_suoratoimitus["suoraan_laskutukseen"] == "") $oletuspaikka_chk = '';
}

$paluu_url = "vahvista_kerayspaikka.php?{$url}";
if (isset($hyllytys)) {
	$paluu_url = "hyllytys.php?{$url}";
}

####
echo "<div class='header'>";
echo "<button onclick='window.location.href=\"$paluu_url\"' class='button left'><img src='back2.png'></button>";
echo "<h1>",t("UUSI KERÄYSPAIKKA"),"</h1></div>";

# Virheet
if (isset($errors)) {
	echo "<span class='error'>";
	foreach($errors as $virhe) {
		echo "{$virhe}<br>";
	}
	echo "</span>";
}
echo "<div class='main'>
<form name='uusipaikkaformi' method='post' action=''>
	<table>
		<tr>
			<th>",t("Tuote"),"</th>
			<td colspan='3'>{$row['tuoteno']}</td>
		</tr>
		<tr>
			<th>",t("Toim. Tuotekoodi"),"</th>
			<td colspan='3'>{$row['toim_tuoteno']}</td>
		</tr>
		<tr>
			<th>",t("Keräyspaikka"),"</th>
			<td colspan='3'>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>
		</tr>
		<tr>
			<th>",t("Uusi tuotepaikka"),"</td>
			<td><input type='text' name='tuotepaikka' /></td>
		</tr>
		<tr>
			<th>",t("Hälytysraja"),"</td>
			<td><input type='text' name='halytysraja' value='' /></th>
		</tr>
		<tr>
			<th>",t("Tilausmäärä"),"</td>
			<td><input type='text' name='tilausmaara' value='' /></th>
		</tr>
		<tr>
			<td colspan='2'>",t("Tee tästä oletuspaikka")," <input type='checkbox' name='oletuspaikka' $oletuspaikka_chk /></td>
		</tr>
	</table>

	<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
	<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
	<input type='hidden' name='tilausrivi' value='{$tilausrivi}' />
</div>";

echo "<div class='controls'>
	<button name='submit' class='button' value='submit' onclick='submit();'>",t("Perusta"),"</button>
	</form>
</div>";

require('inc/footer.inc');