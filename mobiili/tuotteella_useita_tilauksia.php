<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if(!isset($errors)) $errors = array();

# Joku parametri tarvii olla setattu.
if ($ostotilaus != '' or $tuotenumero != '' or $viivakoodi != '') {

	if ($viivakoodi != '') 	$params[] = "tuote.eankoodi = '{$viivakoodi}'";
	if ($tuotenumero != '') $params[] = "tilausrivi.tuoteno = '{$tuotenumero}'";
	if ($ostotilaus != '') 	$params[] = "tilausrivi.otunnus = '{$ostotilaus}'";

	$query_lisa = implode($params, " AND ");

}
else {
	# T‰nne ei pit‰is p‰‰ty‰, tarkistetaan jo ostotilaus.php:ss‰
	echo "Parametrivirhe";
	echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=ostotilaus.php'>";
	exit();
}

# Tarkistetaan onko k‰ytt‰j‰ll‰ kesken saapumista
$keskeneraiset_query = "SELECT kuka.kesken FROM lasku
						JOIN kuka ON lasku.tunnus=kuka.kesken
						WHERE kuka='{$kukarow['kuka']}'
						and kuka.yhtio='{$kukarow['yhtio']}'
						and lasku.tila='K'";
$keskeneraiset = mysql_fetch_assoc(pupe_query($keskeneraiset_query));

$liitostunnus_lisa = '';

# Jos kuka.kesken on saapuminen, k‰ytet‰‰n sit‰
if ($keskeneraiset['kesken'] != 0) {
	$saapuminen = $keskeneraiset['kesken'];
	$query = "SELECT * FROM lasku where tunnus='{$saapuminen}'";
	$laskurow = mysql_fetch_assoc(pupe_query($query));
	$liitostunnus_lisa = "and lasku.liitostunnus='{$laskurow['liitostunnus']}'";
}

# Haetaan ostotilaukset
$query = "	SELECT
			lasku.tunnus as ostotilaus,
			lasku.liitostunnus,
			tilausrivi.tunnus,
			tilausrivi.otunnus,
			tilausrivi.tuoteno,
			tilausrivi.varattu,
			tilausrivi.kpl,
			tilausrivi.tilkpl,
			tilausrivi.uusiotunnus,
			concat_ws('-',tilausrivi.hyllyalue,tilausrivi.hyllynro,tilausrivi.hyllyvali,tilausrivi.hyllytaso) as hylly,
			tuotteen_toimittajat.tuotekerroin,
			tuotteen_toimittajat.liitostunnus
			FROM lasku
			JOIN tilausrivi ON tilausrivi.yhtio=lasku.yhtio AND tilausrivi.otunnus=lasku.tunnus AND tilausrivi.tyyppi='O' AND tilausrivi.varattu != 0 AND (tilausrivi.uusiotunnus = 0 OR tilausrivi.suuntalava = 0)
			JOIN tuote on tuote.tuoteno=tilausrivi.tuoteno AND tuote.yhtio=tilausrivi.yhtio
			JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio
				AND tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno
				AND tuotteen_toimittajat.liitostunnus=lasku.liitostunnus
			WHERE
			$query_lisa
			$liitostunnus_lisa
			AND lasku.yhtio='{$kukarow['yhtio']}'
		";
$result = pupe_query($query);
$tilausten_lukumaara = mysql_num_rows($result);
$tilaukset = mysql_fetch_assoc($result);

# Submit
if (isset($submit)) {
	switch($submit) {
		case 'ok':

			if(empty($tilausrivi)) {
				$errors[] = "Valitse rivi";
				break;
			}
			# Jos kuka.kesken ei ole saapuminen, tehd‰‰n uusi saapuminen haettujen ostotilausten
			# mukaan oikealle toimittajalle
			if ($keskeneraiset['kesken'] == 0) {

				# Haetaan toimittajan tiedot
				$toimittaja_query = "SELECT * FROM toimi WHERE tunnus='{$tilaukset['liitostunnus']}'";
				$toimittaja = mysql_fetch_assoc(pupe_query($toimittaja_query));

				# Kaivetaan sopivaa saapumista
				# kyseisen laatijan saapumiset, joissa ei-siirtovalmiita suuntalavoja
				// $saapuminen_query = "	SELECT * FROM lasku
				// 						WHERE tila='K'
				// 						AND liitostunnus='{$tilaukset['liitostunnus']}'
				// 						AND laatija='{$kukarow['kuka']}'
				// 						ORDER BY tunnus DESC
				// 						LIMIT 1";

				$saapuminen = uusi_saapuminen($toimittaja);

				# Asetetaan uusi saapuminen k‰ytt‰j‰lle kesken olevaksi
				$update_kuka = "UPDATE kuka SET kesken={$saapuminen} WHERE yhtio='{$kukarow['yhtio']}' AND kuka='{$kukarow['kuka']}'";
				$updated = pupe_query($update_kuka);
			}

			$url_array['ostotilaus'] = $ostotilaus;
			$url_array['tilausrivi'] = $tilausrivi;
			$url_array['saapuminen'] = $saapuminen;

			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys.php?".http_build_query($url_array)."'>"; exit();

			break;
		case 'cancel':
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php?ostotilaus=$ostotilaus'>"; exit();
		   	break;
		default:
			echo "Virhe";
			break;
	}
}

# Ei osumia, palataan ostotilaus sivulle
if ($tilausten_lukumaara == 0) {
	echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php?virhe'>";
	exit();
}

# Jos vain yksi osuma, menn‰‰n suoraan hyllytykseen;
if ($tilausten_lukumaara == 1) {

	if(empty($saapuminen)) {
		# Haetaan toimittajan tiedot
		$toimittaja_query = "SELECT * FROM toimi WHERE tunnus='{$tilaukset['liitostunnus']}'";
		$toimittaja = mysql_fetch_assoc(pupe_query($toimittaja_query));

		$saapuminen = uusi_saapuminen($toimittaja);
	}

	$url_array['tilausrivi'] = $tilaukset['tunnus'];
	$url_array['ostotilaus'] = (empty($ostotilaus)) ? $tilaukset['otunnus'] : $ostotilaus;
	$url_array['saapuminen'] = $saapuminen;

	echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys.php?".http_build_query($url_array)."'>";
	exit();
}

# Result alkuun
mysql_data_seek($result, 0);

### UI ###
echo "<div class='header'><h1>",t("TUOTTEELLA USEITA TILAUKSIA", $browkieli), "</h1></div>";

echo "<div class='main'>
<form name='form1' method='post' action=''>
<table>
<tr><th></th>";

if (($tuotenumero != '' or $viivakoodi != '') and $ostotilaus == '') {
	echo "<th>",t("Ostotilaus", $browkieli),"</th>";
}
if ($tuotenumero == '' and $viivakoodi == '' and $ostotilaus != '') {
	echo "<th>",t("Tuoteno", $browkieli), "</th>";
}

echo "
	<th>",t("Kpl (ulk.)", $browkieli),"</th>
	<th>",t("Tuotepaikka", $browkieli),"</th>
</tr>";

# Loopataan ostotilaukset
while($row = mysql_fetch_assoc($result)) {

	echo "<tr>";
	echo "<td><input class='radio' type='radio' name='tilausrivi' value='{$row['tunnus']}' /></td>";
	if (($tuotenumero != '' or $viivakoodi != '') and $ostotilaus == '') {
		echo "<td>{$row['otunnus']}</td>";
	}
	if ($tuotenumero == '' and $viivakoodi == '' and $ostotilaus != '') {
		echo "<td>{$row['tuoteno']}</td>";
	}
	echo "
		<td>".($row['varattu']+$row['kpl']).
			"(".($row['varattu']+$row['kpl'])*$row['tuotekerroin'].")
		</td>
		<td>{$row['hylly']}</td>
	";

	$url_array['ostotilaus'] = $row['ostotilaus'];
	$url_array['tilausrivi'] = $row['tunnus'];

	echo "<input type='hidden' name='ostotilaus' value={$row['ostotilaus']}>";
	echo "<input type='hidden' name='saapuminen' id='saapuminen' value={$saapuminen}>";
	echo "<tr>";
}

echo "</table></div>";
echo "Rivej‰: ".mysql_num_rows($result);

echo "<div class='controls'>
<button type='submit' name='submit' value='ok' onsubmit='false' onclick='return tarkista_saapuminen();'>",t("OK", $browkieli),"</button>
<button class='right' name='submit' id='takaisin' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
</form>
</div>";

echo "<div class='error'>";
	foreach($errors as $virhe) {
		echo $virhe;
	}
echo "</div>";

echo "<script type='text/javascript'>
	function tarkista_saapuminen() {
		var saapuminen = document.getElementById('saapuminen').value;
		if (saapuminen == '') {
			result = confirm('Huom, Keskener‰ist‰ saapumista ei lˆydy. Tuloutusta varten luodaan uusi saapuminen.');
			if(result == true) {
				console.log('tehd‰‰n uusi saapuminen');
				return true;
			}
			else {
				window.location = 'ostotilaus.php';
				return false;
			}
		}
	}
</script>";
