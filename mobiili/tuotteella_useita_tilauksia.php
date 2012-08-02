<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# Rajataan sallitut get parametrit
$sallitut_parametrit = array('viivakoodi', 'tuotenumero', 'ostotilaus');

# Rakkentaan parametreist‰ url_taulukko
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

if ($ostotilaus == '' and $tuotenumero == '' and $viivakoodi == '') {
	echo "jotain tartteis syˆtt‰‰";
	exit;
}

$params = array();
if ($ostotilaus != '') 	$params[] = "tilausrivi.otunnus = '{$ostotilaus}'";
if ($tuotenumero != '') $params[] = "tilausrivi.tuoteno = '{$tuotenumero}'";
if ($viivakoodi != '') 	$params[] = "tuote.eankoodi = '{$viivakoodi}'";

$query_lisa = implode($params, " AND ");

# Haetaan tilaukset
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
				AND tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno
				AND tuotteen_toimittajat.liitostunnus=lasku.liitostunnus
			WHERE
			$query_lisa
			AND tilausrivi.yhtio='{$kukarow['yhtio']}'
			AND tilausrivi.tyyppi='O'
			AND tilausrivi.uusiotunnus=0
			order by tilausrivi.otunnus
			LIMIT 100";

$result = pupe_query($query);
$haku_osumat = mysql_num_rows($result);

# Ei osumia, palataan ostotilaus sivulle
if ($haku_osumat == 0) {
	echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php?virhe'>";
	exit();
}
# Yksi osuma
#-> suoraan hyllytys.php sivulle
elseif ($haku_osumat == 1) {
	echo "Yks osuma, menn‰‰n hyllytykseen...";
}

# Useita tilauksia

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
		<td>{$row['tilkpl']} (".
			($row['varattu']+$row['kpl'])*$row['tuotekerroin'].")</td>
		<td>{$row['hylly']}</td>
	";

	$oletuspaikka_query = "SELECT concat_ws('-',hyllyalue,hyllynro,
				hyllyvali,hyllytaso) as hylly from tuotepaikat where tuoteno='{$row['tuoteno']}' and oletus='X' and yhtio='{$kukarow['yhtio']}'";
	$oletuspaikka = pupe_query($oletuspaikka_query);
	$oletuspaikka = mysql_fetch_assoc($oletuspaikka);
	#echo "<td>(".$oletuspaikka['hylly'].")</td>";

	# Linkki eteenp‰in vahvista_ker‰yspaikka n‰kym‰‰n.
	# Ainakin t‰lle tulleet parametrit, viivakoodi, tuotenumero ja ostotilaus.
	#
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
