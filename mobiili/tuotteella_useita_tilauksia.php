<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if(!isset($errors)) $errors = array();

# Rajataan sallitut get parametrit
$sallitut_parametrit = array('viivakoodi', 'tuotenumero', 'ostotilaus');

# Rakennetaan parametreist‰ url_taulukko
$url_array = array();
foreach($sallitut_parametrit as $parametri) {
	if(!empty($$parametri)) {
		$url_array[$parametri] = $$parametri;
	}
}

if (isset($submit)) {
	switch($submit) {
		case 'ok':
			var_dump($_POST);
			#echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php'>"; exit();
			#exit;
			#break;
		case 'cancel':
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php'>"; exit();
		   	break;
		default:
			echo "Virhe";
			break;
	}
}

# Joku parametri tarvii olla setattu.
if ($ostotilaus != '' or $tuotenumero != '' or $viivakoodi != '') {

	if ($viivakoodi != '') 	$params[] = "tilausrivi.eankoodi = '{$viivakoodi}'";
	if ($tuotenumero != '') $params[] = "tilausrivi.tuoteno = '{$tuotenumero}'";
	if ($ostotilaus != '') 	$params[] = "tilausrivi.otunnus = '{$ostotilaus}'";

	$query_lisa = implode($params, " AND ");

}
else {
	echo "Parametrivirhe";
	echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=ostotilaus.php'>";
	exit();
}
# Haetaan tilaukset
# TODO: Toimittajarajaus
$query = "	SELECT
			tilausrivi.tunnus,
			tilausrivi.otunnus,
			tilausrivi.tuoteno,
			tilausrivi.varattu,
			tilausrivi.kpl,
			tilausrivi.tilkpl,
			tuotteen_toimittajat.tuotekerroin,
			tuotteen_toimittajat.liitostunnus,
			concat_ws('-',tilausrivi.hyllyalue,tilausrivi.hyllynro,
						tilausrivi.hyllyvali,tilausrivi.hyllytaso) as hylly
			FROM tilausrivi
			JOIN lasku ON lasku.yhtio=tilausrivi.yhtio
				AND lasku.tunnus=tilausrivi.otunnus
			JOIN tuote ON tuote.yhtio=tilausrivi.yhtio
				AND tuote.tuoteno=tilausrivi.tuoteno
			JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio
				AND tuotteen_toimittajat.tuoteno=tuote.tuoteno
				AND tuotteen_toimittajat.liitostunnus=lasku.liitostunnus
			WHERE
			$query_lisa
			AND tilausrivi.yhtio='{$kukarow['yhtio']}'
			AND tilausrivi.tyyppi='O'
			AND tilausrivi.uusiotunnus=0
			order by tilausrivi.otunnus
			LIMIT 100";
$result = pupe_query($query);
$tilausten_lukumaara = mysql_num_rows($result);

# Ei osumia, palataan ostotilaus sivulle
if ($tilausten_lukumaara == 0) {
	echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php?virhe'>";
	exit();
}

# Tarkistetaan onko k‰ytt‰j‰ll‰ kesken olevia ostotilauksia
$kesken_query = "	SELECT kuka.kesken FROM lasku
					JOIN kuka ON lasku.tunnus=kuka.kesken
					WHERE kuka='{$kukarow['kuka']}'
					and kuka.yhtio='{$kukarow['yhtio']}'
					and lasku.tila='K';";
$kesken_result = mysql_fetch_assoc(pupe_query($kesken_query));

# Jos saapuminen on kesken niin k‰ytet‰‰n sit‰, tai tehd‰‰n uusi saapuminen
if ($kesken_result['kesken'] != 0) {
	echo "K‰ytt‰j‰ll‰ on saapuminen kesken: {$kesken_result['kesken']}";

	$saapuminen = $kesken_result['kesken'];
}
else {
	# Haetaan toimittajan tiedot
	$row = mysql_fetch_assoc($result);
	$toim_query = "SELECT * FROM toimi WHERE tunnus='{$row['liitostunnus']}'";
	$toimittajarow = mysql_fetch_assoc(pupe_query($toim_query));

	# Tehd‰‰n uusi saapuminen
	$saapuminen = uusi_saapuminen($toimittajarow);

	# Asetetaan uusi saapuminen k‰ytt‰j‰lle kesken olevaksi
	$update_kuka = "UPDATE kuka SET kesken={$saapuminen} WHERE yhtio='{$kukarow['yhtio']}' AND kuka='{$kukarow['kuka']}'";
	$updated = pupe_query($update_kuka);
}

$url_array['saapuminen'] = $saapuminen;

# Jos vain yksi osuma, menn‰‰n suoraan hyllytykseen
if ($tilausten_lukumaara == 1) {

	#$row = mysql_fetch_assoc($result);
	#$url_array['tilausrivi'] = $row['tunnus'];
	#echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=hyllytys.php?".http_build_query($url_array)."'>";
	#exit();
}

# Result alkuun
mysql_data_seek($result, 0);

### UI ###
include("kasipaate.css");

echo "<div class='header'><h1>",t("TUOTTEELLA USEITA TILAUKSIA", $browkieli), "</h1></div>";

echo "<div class='main'>
<form name='form1' method='post' action=''>
<table>
<tr>";

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
	#echo "<td>{$row['tunnus']}</td>";

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

	# TODO: listaus p‰ivitett‰v‰ nykyisill‰ oletuspaikoilla
	// $oletuspaikka_query = "	SELECT concat_ws('-',hyllyalue,hyllynro, hyllyvali,hyllytaso) as hylly
	// 						FROM tuotepaikat
	// 						WHERE tuoteno='{$row['tuoteno']}'
	// 						and oletus='X'
	// 						and yhtio='{$kukarow['yhtio']}'";
	// $oletuspaikka = pupe_query($oletuspaikka_query);
	// $oletuspaikka = mysql_fetch_assoc($oletuspaikka);
	// echo "<td>(".$oletuspaikka['hylly'].")</td>";

	# Linkki eteenp‰in vahvista_ker‰yspaikka n‰kym‰‰n.
	# Ainakin t‰lle tulleet parametrit, viivakoodi, tuotenumero ja ostotilaus.

	# Pakolliset tiedot vahvista_ker‰yspaikka n‰kym‰lle
	# $tilausrivi
	# T‰n parametrit riippuu mill‰ t‰h‰n sivulle on tultu?
	# (tuotenumero|eankoodi) (ostotilaus) (ostotilaus ja (tuotenumero ja/tai eankoodi))
	$url_array['tilausrivi'] = $row['tunnus'];
	echo "<td><a href='hyllytys.php?".http_build_query($url_array)."'>Valkkaa</a></td>";
	echo "<tr>";
}

echo "</table></div>";
echo "Rivej‰: ".mysql_num_rows($result);

echo "<div class='controls'>

<button name='submit' id='submit' value='ok' onclick='submit();'>",t("OK", $browkieli),"</button>
<button class='right' name='submit' id='takaisin' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
</form>
</div>";

echo "<div class='error'>";
	foreach($errors as $virhe => $selite) {
		echo strtoupper($virhe).": ".$selite;
	}
echo "</div>";

## DEBUG ##
#echo "<pre>$query</pre>";
#var_dump($_POST);
