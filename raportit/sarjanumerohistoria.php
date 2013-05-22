<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
	if ($_POST["tee"] == 'lataa_tiedosto') {
		$lataa_tiedosto = 1;
	}
	if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
		$_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
	}
}


require '../inc/parametrit.inc';

if ($tee == 'lataa_tiedosto') {
	$filepath = "/tmp/".$tmpfilenimi;
	if (file_exists($filepath)) {
		readfile($filepath);
		unlink($filepath);
	}
	else {
		echo "<font class='error'>".t("Tiedostoa ei ole olemassa")."</font>";
	}
	exit;
}

if ($ajax_request) {

}

echo "<font class='head'>".t("Sarjanumerohistoria")."</font><hr>";
?>
<style>

</style>
<script>

</script>
<?php

$request = array(
	'tee'			 => $tee,
	'sarjanumero'	 => $sarjanumero,
	'asiakas'		 => $asiakas,
	'toimittaja'	 => $toimittaja,
	'tuote'			 => $tuote,
	'alku_pvm'		 => $alku_pvm,
	'loppu_pvm'		 => $loppu_pvm
);

echo_kayttoliittyma($request);

if ($request['tee'] == 'hae_tilaukset') {
	$tilaukset = hae_tilaukset($request);

	echo_tilaukset_raportti($tilaukset);
}

function hae_tilaukset($request) {
	global $kukarow, $yhtiorow;

	$sarjanumero_where = "";
	if (!empty($request['sarjanumero'])) {
		$sarjanumero_where = " AND sarjanumero.sarjanumero LIKE '%{$request['sarjanumero']}%'";
	}

	$asiakas_where = "";
	if (!empty($request['asiakas'])) {
		$asiakas_where = " AND asiakas.nimi LIKE '%{$request['asiakas']}%'";
	}

	$toimittaja_where = "";
	if (!empty($request['toimittaja'])) {
		$toimittaja_where = " AND toimi.nimi LIKE '%{$request['toimittaja']}%'";
	}

	$tuoteno_where = "";
	if (!empty($request['tuote'])) {
		$tuoteno_where = " AND tilausrivi.tuoteno LIKE '%{$request['tuote']}%'";
	}

	$lasku_where = "";
	if ($request['alku_pvm'] and $request['loppu_pvm']) {
		$lasku_where = " AND lasku.luontiaika BETWEEN '".date('Y-m-d', strtotime($request['alku_pvm']))."' AND '".date('Y-m-d', strtotime($request['loppu_pvm'].' + 1 day'))."'";
	}

	$query = "	(SELECT lasku.tunnus,
				asiakas.nimi as nimi,
				lasku.luontiaika,
				lasku.summa,
				lasku.tila as tyyppi,
				group_concat(sarjanumeroseuranta.sarjanumero) as sarjanumerot
				FROM sarjanumeroseuranta
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
					AND tilausrivi.tunnus = sarjanumeroseuranta.myyntirivitunnus
					{$tuoteno_where} )
				JOIN lasku
				ON ( lasku.yhtio = tilausrivi.yhtio
					AND lasku.tunnus = tilausrivi.otunnus
					AND lasku.tila = 'L'
					{$lasku_where} )
				JOIN asiakas
				ON ( asiakas.yhtio = lasku.yhtio
					AND asiakas.tunnus = lasku.liitostunnus
					{$asiakas_where} )
				WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
				{$sarjanumero_where}
				GROUP BY lasku.tunnus
				)
				UNION
				(SELECT lasku.tunnus,
				toimi.nimi as nimi,
				lasku.luontiaika,
				lasku.summa,
				lasku.tila as tyyppi,
				group_concat(sarjanumeroseuranta.sarjanumero) as sarjanumerot
				FROM sarjanumeroseuranta
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
					AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
					{$tuoteno_where} )
				JOIN lasku
				ON ( lasku.yhtio = tilausrivi.yhtio
					AND lasku.tunnus = tilausrivi.otunnus
					AND lasku.tila = 'O'
					{$lasku_where} )
				JOIN toimi
				ON ( toimi.yhtio = lasku.yhtio
					AND toimi.tunnus = lasku.liitostunnus
					{$toimittaja_where} )
				WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
				{$sarjanumero_where}
				GROUP BY lasku.tunnus
				)";


//	$query = "	SELECT lasku.tunnus,
//				lasku.liitostunnus,
//				lasku.luontiaika,
//				lasku.summa,
//				lasku.tila as tyyppi,
//				tilausrivi.tuoteno,
//				asiakas.nimi AS asiakas_nimi,
//				toimi.nimi AS toimi_nimi
//				FROM lasku
//				JOIN tilausrivi
//					ON ( tilausrivi.yhtio = lasku.yhtio
//					AND tilausrivi.otunnus = lasku.tunnus
//					{$tilausrivi_join} )
//				LEFT JOIN asiakas
//				ON ( asiakas.yhtio = lasku.yhtio
//					AND asiakas.tunnus = lasku.liitostunnus )
//				LEFT JOIN toimi
//				ON ( toimi.yhtio = lasku.yhtio
//					AND toimi.tunnus = lasku.liitostunnus )
//				LEFT JOIN tyomaarays
//				ON ( tyomaarays.yhtio = lasku.yhtio
//					AND tyomaarays.otunnus = lasku.tunnus )
//				{$tilausrivi_join}
//				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
//				AND lasku.tila != 'D'
//				AND lasku.tila IN ('O', 'L', 'N', 'A')
//				{$lasku_where}
//				GROUP BY lasku.tunnus";
	$result = pupe_query($query);

	$tilaukset = array();
	while ($tilaus = mysql_fetch_assoc($result)) {
		$tilaukset[] = $tilaus;
	}

	return $tilaukset;
}

function echo_tilaukset_raportti($tilaukset) {
	global $kukarow, $yhtiorow;

	//tilausnro, asiakkaan/toimittajan nimi, luontiaika, summa sekä tyyppi (osto/myynti/työmääräys).
	echo "<table>";
	echo "<thead>";
	echo "<tr>";
	echo "<th>".t('Tilausnumero')."</th>";
	echo "<th>".t('Asiakkaan')." / ".t('toimittajan nimi')."</th>";
	echo "<th>".t('Sarjanumerot')."</th>";
	echo "<th>".t('Luontiaika')."</th>";
	echo "<th>".t('Summa')."</th>";
	echo "<th>".t('Tyyppi')."</th>";
	echo "</tr>";
	echo "</thead>";
	foreach ($tilaukset as $tilaus) {
		echo "<tr>";
		echo "<td><a href='#'>{$tilaus['tunnus']}</a></td>";
		echo "<td>".(!empty($tilaus['asiakas']) ? $tilaus['asiakas_nimi'] : $tilaus['toimi_nimi'])."</td>";
		echo "<td>{$tilaus['sarjanumerot']}</td>";
		echo "<td>{$tilaus['luontiaika']}</td>";
		echo "<td>{$tilaus['summa']}</td>";
		echo "<td>{$tilaus['tyyppi']}</td>";
		echo "</tr>";
	}
	echo "</table>";
}

function echo_kayttoliittyma($request) {
	global $kukarow, $yhtiorow;

	echo "<form action='' method='POST'>";
	echo "<input type='hidden' name='tee' value='hae_tilaukset' />";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Sarjanumero")."</th>";
	echo "<td>";
	echo "<input type='text' name='sarjanumero' value='{$request['sarjanumero']}' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Asiakas")."</th>";
	echo "<td>";
	echo "<input type='text' name='asiakas' value='{$request['asiakas']}' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Toimittaja")."</th>";
	echo "<td>";
	echo "<input type='text' name='toimittaja' value='{$request['toimittaja']}' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Tuote")."</th>";
	echo "<td>";
	echo "<input type='text' name='tuote' value='{$request['tuote']}' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Päivämääräväli")."</th>";
	echo "<td>";
	echo "<input type='text' name='alku_pvm' value='".(!empty($request['alku_pvm']) ? $request['alku_pvm'] : date('d.m.Y', strtotime('now - 30 day')))."' />";
	echo " - ";
	echo "<input type='text' name='loppu_pvm' value='".(!empty($request['loppu_pvm']) ? $request['loppu_pvm'] : date('d.m.Y', strtotime('now')))."' />";
	echo "</td>";
	echo "</tr>";

	echo "</table>";
	echo "<br/>";
	echo "<input type='submit' value='".t('Hae')."' />";
	echo "</form>";
}

require '../inc/footer.inc';
