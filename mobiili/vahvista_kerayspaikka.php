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
	if (!$row = mysql_fetch_assoc($res)) exit("Virhe: suuntalavan_tuotteet()");
}
# Ilman suuntalavaa
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

# Jos parametrina hylly, eli ollaan muutettu tuotteen ker‰yspaikkaa
if(isset($hylly)) {
	$hylly = explode(",", $hylly);
	$row['hyllyalue'] = $hylly[0];
	$row['hyllynro'] = $hylly[1];
	$row['hyllyvali'] = $hylly[2];
	$row['hyllytaso'] = $hylly[3];
}

# Tullaan nappulasta
if (isset($submit) and trim($submit) != '') {

	# Virheet
	$errors = array();

	switch ($submit) {
		case 'cancel':
			# TODO: Riippuen mist‰ ollaan tultu, mihin menn‰‰n
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
				$errors[] = "Virheellinen m‰‰r‰";
			}
			# Tarkistetaan koodi
			if (!is_numeric($koodi) or !tarkista_varaston_hyllypaikka($row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso'], $koodi)) {
				$errors[] = "Virheellinen varmistuskoodi";
			}
			# Jos ei virheit‰
			if(count($errors) == 0) {
				$tilausrivit = array();

				# Tarkastetaan m‰‰r‰t, eli tarviiko tilausrivia splittailla tai kopioida
				if ($maara < $row['varattu']) {
					echo "M‰‰r‰‰ on pienennetty <br>";
					echo "splittaa_tilausrivi()<br>";

					# P‰ivitet‰‰n alkuper‰isen rivin kpl
					$ok = paivita_tilausrivin_kpl($tilausrivi, ($row['varattu'] - $maara));
					# Splitataan rivi, $pois_suuntalavalta = false
					$tilausrivit[] = splittaa_tilausrivi($tilausrivi, $maara, false, false);
				}
				elseif ($maara == $row['varattu']) {
					echo "M‰‰r‰‰ on sama <br>";
					$tilausrivit[] = $tilausrivi;
				}
				else {
					echo "M‰‰r‰‰ on nostettu <br>";
					echo "splittaa_tilausrivi()<br>";

					# Tehd‰‰n insertti erotukselle
					$kopioitu_tilausrivi = kopioi_tilausrivi($tilausrivi);
					# P‰ivit‰ kopioidun kpl (maara - varattu)
					paivita_tilausrivin_kpl($kopioitu_tilausrivi, ($maara - $row['varattu']));

					$tilausrivit = array($tilausrivi, $kopioitu_tilausrivi);
				}

				# Onko suuntalavaa?
				if ($alusta_tunnus == 0 && !empty($saapuminen)) {

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
					foreach($tilausrivit as $tilausrivi) {
						$kohdista_status = kohdista_rivi($laskurow, $tilausrivi, $row['otunnus'], $saapuminen, $alusta_tunnus);
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

				foreach($tilausrivit as $rivi) {
					vie_varastoon($saapumiset[0], $alusta_tunnus, $hylly, $rivi);
				}

				# Redirectit ostotilaukseen tai suuntalavan_tuotteet?
				echo "<META HTTP-EQUIV='Refresh'CONTENT='3;URL=ostotilaus.php?{$url}'>";
			}
			break;
		default:
			$errors[] = "Odottamaton virhe";
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

include("kasipaate.css");

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

echo "<div class='header'><h1>",t("VAHVISTA KERƒYSPAIKKA", $browkieli),"</h1></div>";

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
		<th>",t("Tuote", $browkieli),"</th>
		<td colspan='2'>{$row['tuoteno']}</td>
	</tr>
	<tr>
		<th>",t("Toim. Tuotekoodi", $browkieli),"</th>
		<td colspan='2'>{$row['toim_tuoteno']}</td>
	</tr>
	<tr>
		<th>",t("M‰‰r‰", $browkieli),"</th>
		<td><input type='text' id='maara' name='maara' value='{$maara}' size='7' $disabled/></td>
		<td><span id='row_varattu' $hidden>{$row['varattu']}</span><span id='yksikko'>{$row['yksikko']}</span></td>
	</tr>
	<tr>
		<th>",t("Ker‰yspaikka", $browkieli),"</th>
		<td colspan='2'>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>
	</tr>
	<tr>
		<th>",t("Koodi", $browkieli),"</th>
		<td colspan='2'><input type='text' name='koodi' value='' size='7' />
	</tr>
</table>
</div>";

echo "<div class='controls'>
	<button name='submit' value='submit' onclick='return vahvista();'>",t("Vahvista", $browkieli),"</button>
	<button class='right' $piilotettu name='submit' value='new'>",t("Uusi ker‰yspaikka", $browkieli),"</button>
	<button class='right' name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
	<a class='takaisin' href='hyllytys.php?ostotilaus={$ostotilaus}&tilausrivi={$tilausrivi}&saapuminen={$saapuminen}'>Takaisin</a>
	<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
	<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
	<input type='hidden' name='tilausrivi' value='{$tilausrivi}' />
	<input type='hidden' name='saapuminen' value='{$saapuminen}' />
</form>
";

require('inc/footer.inc');