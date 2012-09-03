<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# N‰m‰ on pakollisia
if (!isset($alusta_tunnus, $liitostunnus, $tilausrivi)) exit;

$alusta_tunnus = (int) $alusta_tunnus;
$liitostunnus = (int) $liitostunnus;
$tilausrivi = (int) $tilausrivi;

# Urlin rakennus
$data = array(
	'alusta_tunnus' => $alusta_tunnus,
	'liitostunnus' => $liitostunnus,
	'tilausrivi' => $tilausrivi
);
$url = http_build_query($data);

# Haetaan suuntalavan tuotteet
if (!empty($alusta_tunnus)) {
	$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, "", "", "", $tilausrivi);
	$row = mysql_fetch_assoc($res);
}

# Jos suuntalavan_tuotteet() ei lˆyt‰ny mit‰‰n
if(!$row) {
	$query = "	SELECT
				tilausrivi.*,
				tuotteen_toimittajat.toim_tuoteno
				FROM tilausrivi
				LEFT JOIN tuotteen_toimittajat on (tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.yhtio=tilausrivi.yhtio)
				WHERE tilausrivi.tunnus='{$tilausrivi}'
				AND tilausrivi.yhtio='{$kukarow['yhtio']}'";
	$row = mysql_fetch_assoc(pupe_query($query));
}

# Jos parametrina hylly, eli ollaan muutettu tuotteen ker‰yspaikkaa
if(isset($hylly)) {
	$hylly = explode(",", $hylly);
	$row['hyllyalue'] = $hylly[0];
	$row['hyllynro'] = $hylly[1];
	$row['hyllyvali'] = $hylly[2];
	$row['hyllytaso'] = $hylly[3];
}

# Alkuper‰inen saapuminen talteen
$alkuperainen_saapuminen = $saapuminen;

# Tullaan nappulasta
if (isset($submit) and trim($submit) != '') {

	# Virheet
	$errors = array();

	switch ($submit) {
		case 'cancel':
			# Ostotilaus -> hyllytykseen
			if (isset($hyllytys)) {
				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys.php?ostotilaus={$row['otunnus']}&tilausrivi={$tilausrivi}&saapuminen={$saapuminen}'>";
			}
			# Asn-tuloutus -> suuntalava
			else {
				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php?{$url}'>";
			}
			exit;
			break;
		case 'new':
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=uusi_kerayspaikka.php?{$url}'>";
			exit;
			break;
		case 'submit':
			# Tarkistetaan m‰‰r‰
			if (!is_numeric($maara) or $maara < 1) {
				$errors[] = t("Virheellinen m‰‰r‰");
			}
			# Tarkistetaan koodi
			if (!is_numeric($koodi) or !tarkista_varaston_hyllypaikka($row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso'], $koodi)) {
				$errors[] = t("Virheellinen varmistuskoodi");
			}
			# Jos ei virheit‰
			if(count($errors) == 0) {
				$tilausrivit = array();

				# Jos rivi on jo kohdistettu eri saapumiselle
				if (!empty($row['uusiotunnus'])) {
					$saapuminen = $row['uusiotunnus'];
				}

				# Tarkastetaan m‰‰r‰t, eli tarviiko tilausrivia splittailla tai kopioida
				if ($maara < $row['varattu']) {
					# Splitataan rivi
					# Jos viimeinen rivi ja m‰‰r‰‰ pienennetty, pudotetaan toinen rivi pois lavalta.
					# Koska viimeist‰ rivii viedess‰ vied‰‰n kaikkilavan rivit varastoon
					if (isset($viimeinen)) {
						splittaa_tilausrivi($tilausrivi, ($row['varattu'] - $maara), false, true);
					}
					else {
						splittaa_tilausrivi($tilausrivi, ($row['varattu'] - $maara), false, false);
					}

					# Alkuper‰inen vied‰‰n varastoon, splitattu j‰‰ j‰ljelle
					$ok = paivita_tilausrivin_kpl($tilausrivi, $maara);
					$tilausrivit[] = $tilausrivi;
				}
				elseif ($maara == $row['varattu']) {
					$tilausrivit[] = $tilausrivi;
				}
				else {
					# Tehd‰‰n insertti erotukselle
					$kopioitu_tilausrivi = kopioi_tilausrivi($tilausrivi);

					# P‰ivit‰ kopioidun kpl (maara - varattu)
					paivita_tilausrivin_kpl($kopioitu_tilausrivi, ($maara - $row['varattu']));

					$tilausrivit = array($tilausrivi, $kopioitu_tilausrivi);
				}

				$temppi_lava = false;
				# Vied‰‰n varastoon temppi lavalla
				if (($alusta_tunnus == 0 && $saapuminen != 0) || ($alusta_tunnus != 0 && $row['uusiotunnus'] == 0)) {
					$temppi_lava = true;
					# Tarkottaa ett‰ on tultu ostotilauksen tuloutuksesta ilman ett‰ kyseisell‰
					# tilauksella on suuntalavaa. Ratkaisuna tehd‰‰n v‰liaikanen lava.
					$tee = "eihalutamitankayttoliittymaapliis";
					$suuntalavat_ei_kayttoliittymaa = "KYLLA";
					$otunnus = $saapuminen;
					require ("../tilauskasittely/suuntalavat.inc");

					# Suuntalavalle nimi, temp_timestamp+kuka hash
					$hash = "temp_".substr(sha1(time().$kukarow['kuka']), 0,8);

					$params = array(
							'sscc' => $hash,
							'tyyppi' => 0,
							'keraysvyohyke' => $hash,
							'usea_keraysvyohyke' => 'K',
							'kaytettavyys' => 'Y',
							'terminaalialue' => $hash,
							'korkeus' => 0,
							'paino' => 0,
							'alkuhyllyalue' => "",
							'alkuhyllynro' => "",
							'alkuhyllyvali' => "",
							'alkuhyllytaso' => "",
							'loppuhyllyalue' => "",
							'loppuhyllynro' => "",
							'loppuhyllyvali' => "",
							'loppuhyllytaso' => "",
							'suuntalavat_ei_kayttoliittymaa' => "KYLLA",
							'valittutunnus' => $tilausrivi
						);

					$alusta_tunnus = lisaa_suuntalava($saapuminen, $params);

					# Saapumisen tiedot
					$query    = "SELECT * FROM lasku WHERE tunnus = '{$saapuminen}' AND yhtio = '{$kukarow['yhtio']}'";
					$result   = pupe_query($query);
					$laskurow = mysql_fetch_array($result);

					# Ei voi kohdistaa ennen kuin tilausrivi on splitattu
					require("../inc/keikan_toiminnot.inc");
					foreach($tilausrivit as $rivi) {
						$kohdista_status = kohdista_rivi($laskurow, $rivi, $row['otunnus'], $saapuminen, $alusta_tunnus);
					}

					# Suuntalava siirtovalmiiksi
					$otunnus = $saapuminen;
					$suuntalavan_tunnus = $alusta_tunnus;
					$tee = 'siirtovalmis';
					$suuntalavat_ei_kayttoliittymaa = "KYLLA";
					require ("../tilauskasittely/suuntalavat.inc");
				}

				# Kun splittaukset ja alustat on selvitelty, voidaan kamat vied‰ varastoon.
				# Hylly array
				$hylly = array(
					"hyllyalue" => $row['hyllyalue'],
					"hyllynro" 	=> $row['hyllynro'],
					"hyllyvali" => $row['hyllyvali'],
					"hyllytaso" => $row['hyllytaso']);

				# Saapumiset
				$saapumiset = hae_saapumiset($alusta_tunnus);

				# Viimeisell‰ rivill‰ vied‰‰n koko suuntalava, jolloin lava merkataan puretuksi
				if(isset($viimeinen)) {
					vie_varastoon($saapumiset[0], $alusta_tunnus, $hylly);
				}
				else {
					foreach($tilausrivit as $rivi) {
						vie_varastoon($saapumiset[0], $alusta_tunnus, $hylly, $rivi);
					}
				}
				# Jos temppi lava niin merkataan suoraan puretuksi
				if ($temppi_lava) {
					$query = "	UPDATE suuntalavat SET
								tila = 'P'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$alusta_tunnus}'";
					$tila_res = pupe_query($query);
				}

				echo t("Odota hetki...");

				# Redirectit ostotilaukseen tai suuntalavan_tuotteet?
				if (isset($hyllytys)) {
					echo "<META HTTP-EQUIV='Refresh'CONTENT='3;URL=ostotilaus.php?ostotilaus={$row['otunnus']}'>";
				}
				else {
					echo "<META HTTP-EQUIV='Refresh'CONTENT='3;URL=suuntalavan_tuotteet?{$url}'>";
				}
			}
			break;
		default:
			$errors[] = t("Odottamaton virhe");
			break;
	}

}

# Asetetaan m‰‰r‰ varattu kent‰n arvoksi jos sit‰ ei ole setattu
$maara = (empty($maara)) ? $row['varattu'] : $maara;

# Jos ollaan tultu ostotilausten tuloutuksesta, on n‰kym‰ hieman erilainen kuin asn-tuloutuksessa
if (isset($ostotilaus)) {
	$disabled = "readonly";
	$hidden = "hidden";
}

if (isset($hyllytetty)) {
	$maara = $hyllytetty;
}

echo "
	<script type='text/javascript'>
		function vahvista() {
			var maara = document.getElementById('maara').value;
			var row_varattu = parseInt(document.getElementById('row_varattu').innerHTML);
			if(maara > row_varattu) {
				return confirm('Olet tulouttamassa enemm‰n kuin rivill‰ alunperin oli. Oletko varma?');
			}
			else return true;
		}
	</script>
";

echo "<div class='header'><h1>",t("VAHVISTA KERƒYSPAIKKA"),"</h1></div>";

# Virheet
if (isset($errors)) {
	echo "<span class='error'>";
	foreach($errors as $virhe) {
		echo "{$virhe}<br>";
	}
	echo "</span>";
}

echo "<div class='main'>
<form name='vahvistaformi' method='post' action=''>
<table>
	<tr>
		<th>",t("Tuote"),"</th>
		<td colspan='2'>{$row['tuoteno']}</td>
	</tr>
	<tr>
		<th>",t("Toim. Tuotekoodi"),"</th>
		<td colspan='2'>{$row['toim_tuoteno']}</td>
	</tr>
	<tr>
		<th>",t("M‰‰r‰"),"</th>
		<td><input type='text' id='maara' name='maara' value='{$maara}' size='7' $disabled/></td>
		<td><span id='row_varattu' $hidden>{$row['varattu']}</span><span id='yksikko'>{$row['yksikko']}</span></td>
	</tr>
	<tr>
		<th>",t("Ker‰yspaikka"),"</th>
		<td colspan='2'>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>
	</tr>
	<tr>
		<th>",t("Koodi"),"</th>
		<td colspan='2'><input type='text' name='koodi' value='' size='7' />
	</tr>
	<tr>
		<td><input type='hidden' name='saapuminen' value='{$saapuminen}' /></td>
	</tr>
</table>
</div>";

echo "<div class='controls'>
	<button name='submit' value='submit' onclick='return vahvista();'>",t("Vahvista"),"</button>";

# Jos hyllytyksest‰ niin t‰m‰ piiloon
if (!isset($hyllytys)) echo "<button class='right' name='submit' value='new'>",t("Uusi ker‰yspaikka"),"</button>";

echo "<button class='right' name='submit' value='cancel' onclick='submit();'>",t("Takaisin"),"</button>
	<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
	<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
	<input type='hidden' name='tilausrivi' value='{$tilausrivi}' />
</form>
";

require('inc/footer.inc');