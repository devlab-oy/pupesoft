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
	echo t("Parametrivirhe");
	echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=ostotilaus.php'>";
	exit();
}

# Tarkistetaan onko k‰ytt‰j‰ll‰ kesken saapumista
$keskeneraiset_query = "SELECT kuka.kesken FROM lasku
						JOIN kuka ON (lasku.tunnus=kuka.kesken and lasku.yhtio=kuka.yhtio)
						WHERE kuka='{$kukarow['kuka']}'
						and kuka.yhtio='{$kukarow['yhtio']}'
						and lasku.tila='K'";
$keskeneraiset = mysql_fetch_assoc(pupe_query($keskeneraiset_query));

# Jos kuka.kesken on saapuminen, k‰ytet‰‰n sit‰
if ($keskeneraiset['kesken'] != 0) {
	$saapuminen = $keskeneraiset['kesken'];
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
			JOIN tilausrivi ON tilausrivi.yhtio=lasku.yhtio AND tilausrivi.otunnus=lasku.tunnus AND tilausrivi.tyyppi='O'
				AND tilausrivi.varattu != 0 AND (tilausrivi.uusiotunnus = 0 OR tilausrivi.suuntalava = 0)
			JOIN tuote on tuote.tuoteno=tilausrivi.tuoteno AND tuote.yhtio=tilausrivi.yhtio
			JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio
				AND tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno
				AND tuotteen_toimittajat.liitostunnus=lasku.liitostunnus
			WHERE
			$query_lisa
			AND lasku.alatila = 'A'
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
				$errors[] = t("Valitse rivi");
				break;
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

	$url_array['tilausrivi'] = $tilaukset['tunnus'];
	$url_array['ostotilaus'] = (empty($ostotilaus)) ? $tilaukset['otunnus'] : $ostotilaus;
	$url_array['saapuminen'] = $saapuminen;

	echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys.php?".http_build_query($url_array)."'>";
	exit();
}

# Result alkuun
mysql_data_seek($result, 0);

### UI ###
echo "<div class='header'><h1>",t("TUOTTEELLA USEITA TILAUKSIA"), "</h1></div>";

echo "<div class='main'>
<form name='form1' method='post' action=''>
<table>
<tr>";

if (($tuotenumero != '' or $viivakoodi != '') and $ostotilaus == '') {
	echo "<th>",t("Ostotilaus"),"</th>";
}
if ($tuotenumero == '' and $viivakoodi == '' and $ostotilaus != '') {
	echo "<th>",t("Tuoteno"), "</th>";
}

echo "
	<th>",t("Kpl (ulk.)"),"</th>
	<th>",t("Tuotepaikka"),"</th>
</tr>";

# Loopataan ostotilaukset
while($row = mysql_fetch_assoc($result)) {

	# Jos rivi on jo kohdistettu eri saapumiselle
	if ($row['uusiotunnus'] != 0) $saapuminen = $row['uusiotunnus'];
	$url = http_build_query(array('saapuminen' => $saapuminen, 'ostotilaus' => $row['ostotilaus'], 'tilausrivi' => $row['tunnus']));

	echo "<tr>";

	if (($tuotenumero != '' or $viivakoodi != '') and $ostotilaus == '') {
		echo "<td><a href='hyllytys.php?$url'>{$row['otunnus']}</a></td>";
	}
	if ($tuotenumero == '' and $viivakoodi == '' and $ostotilaus != '') {
		echo "<td><a href='hyllytys.php?$url'>{$row['tuoteno']}</a></td>";
	}
	echo "
		<td>".($row['varattu']+$row['kpl']).
			"(".($row['varattu']+$row['kpl'])*$row['tuotekerroin'].")
		</td>
		<td>{$row['hylly']}</td>";
	echo "<tr>";
}

echo "</table></div>";
echo "Rivej‰: ".mysql_num_rows($result);

echo "<div class='controls'>
<button type='submit' name='submit' value='ok' onsubmit='false'>",t("OK"),"</button>
<button class='right' name='submit' id='takaisin' value='cancel' onclick='submit();'>",t("Takaisin"),"</button>
</form>
</div>";

echo "<div class='error'>";
	foreach($errors as $virhe) {
		echo $virhe;
	}
echo "</div>";