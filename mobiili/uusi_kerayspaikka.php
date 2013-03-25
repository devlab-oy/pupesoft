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

// Virheet
$errors = array();

// Suuntalavan kanssa
if (!empty($alusta_tunnus)) {
	$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, "", "", "", $tilausrivi);
	$row = mysql_fetch_assoc($res);
}
// Ilman suuntalavaa
else {
	$query = "	SELECT
				tilausrivi.*,
				tuotteen_toimittajat.toim_tuoteno
				FROM tilausrivi
				LEFT JOIN tuotteen_toimittajat on (tuotteen_toimittajat.tuoteno=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno)
				WHERE tilausrivi.tunnus = '{$tilausrivi}'
				AND tilausrivi.yhtio    = '{$kukarow['yhtio']}'";
	$row = mysql_fetch_assoc(pupe_query($query));
}

// Tarkistetaan tuotteen saldo
list($saldo['saldo'], $saldo['hyllyssa'], $saldo['myytavissa']) = saldo_myytavissa($row['tuoteno'], '', '', '0', $row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso']);
$saldo['myytavissa'] = ($saldo['myytavissa'] > 0) ? $saldo['myytavissa'] : 0;

if (isset($submit) and trim($submit) != '') {

	switch ($submit) {
		case 'submit':

			// Parsitaan uusi tuotepaikka
			// Jos tuotepaikka on luettu viivakoodina, muotoa (C21 045) tai (21C 03V)
			if (preg_match('/^([a-zåäö#0-9]{2,4} [a-zåäö#0-9]{2,4})/i', $tuotepaikka)) {

				// Pilkotaan viivakoodilla luettu tuotepaikka välilyönnistä
				list($alku, $loppu) = explode(' ', $tuotepaikka);

				// Mätsätään numerot ja kirjaimet erilleen
				preg_match_all('/([0-9]+)|([a-z]+)/i', $alku, $alku);
				preg_match_all('/([0-9]+)|([a-z]+)/i', $loppu, $loppu);

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
				$errors[] = t("Varaston tuotepaikkaa ($hyllyalue-$hyllynro-$hyllyvali-$hyllytaso) ei ole perustettu").'.';
			}

			// Ei sarjanumerollisia tuotteita
			$query = "	SELECT sarjanumeroseuranta
						FROM tuote
						WHERE yhtio='{$kukarow['yhtio']}'
						AND tuoteno='{$row['tuoteno']}'";
			$result = pupe_query($query);
			$tuote = mysql_fetch_assoc($result);

			if ($tuote['sarjanumeroseuranta'] != '' and $siirra_saldot == 'on') {
				$errors[] = t("Saldojen siirto ei tue sarjanumerollisia tuotteita");
			}

			if (count($errors) == 0) {

				// Oletuspaikka checkboxi
				if ($oletuspaikka == 'on') {
					$oletus = 'X';
				}
				else {
					$oletus = '';
				}

				$hylly = array(
						"hyllyalue" => $hyllyalue,
						"hyllynro" 	=> $hyllynro,
						"hyllyvali" => $hyllyvali,
						"hyllytaso" => $hyllytaso
					);

				// Tarkistetaan onko syötetty hyllypaikka jo tälle tuotteelle
				$tuotteen_oma_hyllypaikka = "	SELECT * FROM tuotepaikat
												WHERE tuoteno ='$row[tuoteno]'
												AND yhtio     ='{$kukarow['yhtio']}'
												AND hyllyalue ='$hyllyalue'
												AND hyllynro  ='$hyllynro'
												AND hyllyvali ='$hyllyvali'
												AND hyllytaso ='$hyllytaso'";
				$oma_paikka = mysql_query($tuotteen_oma_hyllypaikka);

				// Jos syötettyä paikkaa ei ole tämän tuotteen, lisätään uusi tuotepaikka
				if (mysql_num_rows($oma_paikka) == 0) {
					lisaa_tuotepaikka($row['tuoteno'], $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, 'Saapumisessa', $oletus, $halytysraja, $tilausmaara);
				}
				else {
					// Nollataan poistettava kenttä varmuuden vuoksi
					$query = "	UPDATE tuotepaikat SET
								poistettava 	= ''
								WHERE tuoteno 	= '{$row['tuoteno']}'
								AND yhtio     	= '{$kukarow['yhtio']}'
								AND hyllyalue 	= '$hyllyalue'
								AND hyllynro  	= '$hyllynro'
								AND hyllyvali 	= '$hyllyvali'
								AND hyllytaso 	= '$hyllytaso'";
					pupe_query($query);
				}

				// Päivitetään oletuspaikat jos tehdään tästä oletuspaikka
				if ($oletus == 'X') {
					// Asetetaan oletuspaikka uusiksi
					$paivitetty_paikka = paivita_oletuspaikka($row['tuoteno'], $hylly);

					// Siirretään saldot jos on siirrettävää
					if ($siirra_saldot == 'on' and $saldo['myytavissa'] > 0 and $tuote['sarjanumeroseuranta'] == '') {

						// Siirretään saldot vanhasta oletuspaikasta uuteen oletuspaikkaan
						// Poistetaan VANHALTA tuotepaikalta siirrettävä määrä
						// Saldojen siirroissa vanha tuotepaikka merkataan aina poistettavaksi
						$query = "	UPDATE tuotepaikat SET
									saldo         = saldo - {$saldo['myytavissa']},
									saldoaika     = now(),
									muuttaja      = '{$kukarow['kuka']}',
									muutospvm     = now(),
									poistettava   = 'D'
									WHERE tuoteno = '{$row['tuoteno']}'
									AND yhtio     = '{$kukarow['yhtio']}'
									AND hyllyalue = '{$row['hyllyalue']}'
									AND hyllynro  = '{$row['hyllynro']}'
									AND hyllyvali = '{$row['hyllyvali']}'
									AND hyllytaso = '{$row['hyllytaso']}'";
						$result = pupe_query($query);

						// Lisätään UUTEEN tuotepaikkaan siirrettävä määrä
						$query = "	UPDATE tuotepaikat SET
									saldo         = saldo + {$saldo['myytavissa']},
									saldoaika     = now(),
									muuttaja      = '{$kukarow['kuka']}',
									muutospvm     = now(),
									poistettava   = ''
									WHERE tuoteno = '{$row['tuoteno']}'
									AND yhtio     = '{$kukarow['yhtio']}'
									AND hyllyalue = '{$hyllyalue}'
									AND hyllynro  = '{$hyllynro}'
									AND hyllyvali = '{$hyllyvali}'
									AND hyllytaso = '{$hyllytaso}'";
						$result = pupe_query($query);

						$mista = "{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}";
						$minne = "$hyllyalue $hyllynro $hyllyvali $hyllytaso";

						###
						$kehahin_query = "	SELECT tuote.sarjanumeroseuranta,
											round(if (tuote.epakurantti100pvm = '0000-00-00',
													if (tuote.epakurantti75pvm = '0000-00-00',
														if (tuote.epakurantti50pvm = '0000-00-00',
															if (tuote.epakurantti25pvm = '0000-00-00',
																tuote.kehahin,
															tuote.kehahin * 0.75),
														tuote.kehahin * 0.5),
													tuote.kehahin * 0.25),
												0),
											6) kehahin
											FROM tuote
											WHERE yhtio = '{$kukarow['yhtio']}'
											and tuoteno = '{$row['tuoteno']}'";
						$kehahin_result = mysql_query($kehahin_query) or pupe_error($kehahin_query);
						$kehahin_row = mysql_fetch_array($kehahin_result);
						$keskihankintahinta = $kehahin_row['kehahin'];
						###

						// Tapahtumat
						// insert into tapahtumat "vähennettiin"
						$tapahtuma_query = "INSERT INTO tapahtuma SET
											yhtio 		= '{$kukarow['yhtio']}',
											tuoteno 	= '{$row['tuoteno']}',
											kpl 		= {$saldo['myytavissa']} * -1,
											hinta 		= '$keskihankintahinta',
											laji 		= 'siirto',
											hyllyalue	= '{$row['hyllyalue']}',
											hyllynro 	= '{$row['hyllynro']}',
											hyllyvali	= '{$row['hyllyvali']}',
											hyllytaso	= '{$row['hyllytaso']}',
											rivitunnus	= '0',
											selite 		= '".t("Paikasta")." $mista ".t("vähennettiin")." {$saldo['myytavissa']}',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
						$result = pupe_query($tapahtuma_query);

						// insert into tapahtumat "lisättiin"
						$tapahtuma_query = "INSERT INTO tapahtuma SET
											yhtio 		= '{$kukarow['yhtio']}',
											tuoteno 	= '{$row['tuoteno']}',
											kpl 		= {$saldo['myytavissa']},
											hinta 		= '$keskihankintahinta',
											laji 		= 'siirto',
											hyllyalue	= '$hyllyalue',
											hyllynro 	= '$hyllynro',
											hyllyvali	= '$hyllyvali',
											hyllytaso	= '$hyllytaso',
											rivitunnus	= '0',
											selite 		= '".t("Paikalle")." $minne ".t("lisättiin")." {$saldo['myytavissa']}',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now()";
						$result = pupe_query($tapahtuma_query);

						// Päivitetään vanhan tuotepaikan avoimet tulouttamattomat ostot uudelle paikalle
						$ostot_query = "UPDATE tilausrivi SET
										hyllyalue     = '$hyllyalue',
										hyllynro      = '$hyllynro',
										hyllyvali     = '$hyllyvali',
										hyllytaso     = '$hyllytaso'
										WHERE yhtio   = '{$kukarow['yhtio']}'
										AND tyyppi    = 'O'
										AND varattu   > 0
										AND tuoteno   = '{$row['tuoteno']}'
										AND hyllyalue = '{$row['hyllyalue']}'
										AND hyllynro  = '{$row['hyllynro']}'
										AND hyllyvali = '{$row['hyllyvali']}'
										AND hyllytaso = '{$row['hyllytaso']}'";
						$result = pupe_query($ostot_query);
					}
				}

				// Asetetaan tuotepaikka tilausriville
				$affected_rows = paivita_tilausrivin_hylly($tilausrivi, $hylly);

				// Palataan edelliselle sivulle
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

if ($siirra_saldot == 'on') {
	$siirra_saldot_chk = "checked";
}

$paluu_url = "vahvista_kerayspaikka.php?{$url}";
if (isset($hyllytys)) {
	$paluu_url = "hyllytys.php?{$url}";
}

// View
echo "<div class='header'>";
echo "<button onclick='window.location.href=\"$paluu_url\"' class='button left'><img src='back2.png'></button>";
echo "<h1>",t("UUSI KERÄYSPAIKKA"),"</h1></div>";

// Virheet
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
			<td colspan='2'>",t("Tee tästä oletuspaikka")," <input type='checkbox' id='oletuspaikka' name='oletuspaikka' $oletuspaikka_chk /></td>
		</tr>
		<tr>
			<td colspan='2'>",t("Siirrä saldo")," ({$saldo['myytavissa']}) <input type='checkbox' id='siirra_saldot' name='siirra_saldot' $siirra_saldot_chk/> </td>
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

echo "
<script type='text/javascript'>

$(document).ready(function() {
	$('#oletuspaikka').on('change', function() {
		if ($('#oletuspaikka').is(':checked')) {
			// enabloidaan siirra saldot checkbox
			$('#siirra_saldot').removeAttr('disabled');
		}
		else {
			// Tyhjennetään ja disabloidaan siirra saldot checkbox
			$('#siirra_saldot').attr('disabled', 'disabled');
			$('#siirra_saldot').removeAttr('checked');
		}
	});
});

</script>";


require('inc/footer.inc');