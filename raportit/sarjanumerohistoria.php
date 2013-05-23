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
require '../validation/Validation.php';

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

if ($request['tee'] == 'nayta_tilaus') {
	require 'naytatilaus.inc';
}
else {
	echo_kayttoliittyma($request);

	if ($request['tee'] == 'hae_tilaukset') {
		$validations = array(
			'sarjanro'	 => 'mitavaan',
			'asiakas'	 => 'mitavaan',
			'toimittaja' => 'mitavaan',
			'tuote'		 => 'mitavaan',
			'alku_pvm'	 => 'paiva',
			'loppu_pvm'	 => 'paiva'
		);
		$required = array('alku_pvm', 'loppu_pvm');

		$validator = new FormValidator($validations, $required);

		if ($validator->validate($request)) {
			$tilaukset = hae_tilaukset($request);
			//esitellään tilaus tyypit tässä jotta validaatio luokka ei yritä valitoida niitä.
			$request['tyypit'] = array(
				'L'	 => t("Myyntitilaus"),
				'O'	 => t("Ostotilaus"),
				'A'	 => t("Työmääräys"),
			);
			echo_tilaukset_raportti($tilaukset, $request);
		}
		else {
			echo $validator->getScript();
		}
	}
}

function hae_tilaukset($request) {
	global $kukarow, $yhtiorow;

	$sarjanumero_where = "";
	if (!empty($request['sarjanumero'])) {
		$sarjanumero_where = " AND sarjanumeroseuranta.sarjanumero LIKE '%{$request['sarjanumero']}%'";
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
		$tuoteno_where = " AND tilausrivi.nimitys LIKE '%{$request['tuote']}%'";
	}

	$lasku_where = "";
	if ($request['alku_pvm'] and $request['loppu_pvm']) {
		//loppu_pvm + 1 day, koska queryssä between ja luontiaika on datetime
		$lasku_where = " AND lasku.luontiaika BETWEEN '".date('Y-m-d', strtotime($request['alku_pvm']))."' AND '".date('Y-m-d', strtotime($request['loppu_pvm'].' + 1 day'))."'";
	}

	$queryt = array();
	//jos haetaan asiakkaita haetaan vain myyntitilauksia ja työmääräyksiä koska ostotilauksilla ei ole asiakasta
	if (!empty($request['asiakas'])) {
		$queryt[] = "	(
						SELECT lasku.tunnus,
						toimi.nimi as nimi,
						lasku.luontiaika,
						lasku.summa,
						'A' as tyyppi,
						sarjanumeroseuranta.sarjanumero as sarjanumerot,
						tilausrivi.nimitys as tuote
						FROM sarjanumeroseuranta
						JOIN tilausrivi
						ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
							AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
							AND tilausrivi.tyyppi = 'L'
							{$tuoteno_where} )
						JOIN lasku
						ON ( lasku.yhtio = tilausrivi.yhtio
							AND lasku.tunnus = tilausrivi.otunnus
							{$lasku_where} )
						JOIN toimi
						ON ( toimi.yhtio = lasku.yhtio
							AND toimi.tunnus = lasku.liitostunnus
							{$toimittaja_where} )
						WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
						{$sarjanumero_where}
						)";
		$queryt[] = "	(
						SELECT lasku.tunnus,
						asiakas.nimi as nimi,
						lasku.luontiaika,
						lasku.summa,
						'L' as tyyppi,
						sarjanumeroseuranta.sarjanumero as sarjanumerot,
						tilausrivi.nimitys as tuote
						FROM sarjanumeroseuranta
						JOIN tilausrivi
						ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
							AND tilausrivi.tunnus = sarjanumeroseuranta.myyntirivitunnus
							AND tilausrivi.tyyppi = 'L'
							{$tuoteno_where} )
						JOIN lasku
						ON ( lasku.yhtio = tilausrivi.yhtio
							AND lasku.tunnus = tilausrivi.otunnus
							{$lasku_where} )
						JOIN asiakas
						ON ( asiakas.yhtio = lasku.yhtio
							AND asiakas.tunnus = lasku.liitostunnus
							{$asiakas_where} )
						WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
						{$sarjanumero_where}
						)";
	}

	//jos haetaan toimittajia haetaan vain ostotilauksia koska myyntitilauksia ja työmääräyksiä ei ole toimittajia
	if (!empty($request['toimittaja'])) {
		$queryt[] = "	(
						SELECT lasku.tunnus,
						toimi.nimi as nimi,
						lasku.luontiaika,
						lasku.summa,
						'O' as tyyppi,
						sarjanumeroseuranta.sarjanumero as sarjanumerot,
						tilausrivi.nimitys as tuote
						FROM sarjanumeroseuranta
						JOIN tilausrivi
						ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
							AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
							AND tilausrivi.tyyppi = 'O'
							{$tuoteno_where} )
						JOIN lasku
						ON ( lasku.yhtio = tilausrivi.yhtio
							AND lasku.tunnus = tilausrivi.otunnus
							{$lasku_where} )
						JOIN toimi
						ON ( toimi.yhtio = lasku.yhtio
							AND toimi.tunnus = lasku.liitostunnus
							{$toimittaja_where} )
						WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
						{$sarjanumero_where}
						)";
	}

	if (!empty($queryt)) {
		foreach ($queryt as $q) {
			$query = $q.' UNION';
		}
		//poistetaan vika " UNION"
		$query = substr($query, 0, -6);
	}
	else {
		//jos queryt on empty asiakkaaseen tai toimittajaan ei ole syötetty mitään. tällöin haetaan kaikista tilaustyypeistä
		$query = "	(
						SELECT lasku.tunnus,
						asiakas.nimi as nimi,
						lasku.luontiaika,
						lasku.summa,
						'L' as tyyppi,
						sarjanumeroseuranta.sarjanumero as sarjanumerot,
						tilausrivi.nimitys as tuote
						FROM sarjanumeroseuranta
						JOIN tilausrivi
						ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
							AND tilausrivi.tunnus = sarjanumeroseuranta.myyntirivitunnus
							AND tilausrivi.tyyppi = 'L'
							{$tuoteno_where} )
						JOIN lasku
						ON ( lasku.yhtio = tilausrivi.yhtio
							AND lasku.tunnus = tilausrivi.otunnus
							{$lasku_where} )
						JOIN asiakas
						ON ( asiakas.yhtio = lasku.yhtio
							AND asiakas.tunnus = lasku.liitostunnus
							{$asiakas_where} )
						WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
						{$sarjanumero_where}
						)
						UNION
						(
						SELECT lasku.tunnus,
						toimi.nimi as nimi,
						lasku.luontiaika,
						lasku.summa,
						'A' as tyyppi,
						sarjanumeroseuranta.sarjanumero as sarjanumerot,
						tilausrivi.nimitys as tuote
						FROM sarjanumeroseuranta
						JOIN tilausrivi
						ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
							AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
							AND tilausrivi.tyyppi = 'L'
							{$tuoteno_where} )
						JOIN lasku
						ON ( lasku.yhtio = tilausrivi.yhtio
							AND lasku.tunnus = tilausrivi.otunnus
							{$lasku_where} )
						JOIN toimi
						ON ( toimi.yhtio = lasku.yhtio
							AND toimi.tunnus = lasku.liitostunnus
							{$toimittaja_where} )
						WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
						{$sarjanumero_where}
						)
						UNION
						(
						SELECT lasku.tunnus,
						toimi.nimi as nimi,
						lasku.luontiaika,
						lasku.summa,
						'O' as tyyppi,
						sarjanumeroseuranta.sarjanumero as sarjanumerot,
						tilausrivi.nimitys as tuote
						FROM sarjanumeroseuranta
						JOIN tilausrivi
						ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
							AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
							AND tilausrivi.tyyppi = 'O'
							{$tuoteno_where} )
						JOIN lasku
						ON ( lasku.yhtio = tilausrivi.yhtio
							AND lasku.tunnus = tilausrivi.otunnus
							{$lasku_where} )
						JOIN toimi
						ON ( toimi.yhtio = lasku.yhtio
							AND toimi.tunnus = lasku.liitostunnus
							{$toimittaja_where} )
						WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
						{$sarjanumero_where}
						)";
	}
	$query = $query."ORDER BY tyyppi, luontiaika";
	$result = pupe_query($query);

	$tilaukset = array();
	while ($tilaus = mysql_fetch_assoc($result)) {
		$tilaukset[] = $tilaus;
	}

	return $tilaukset;
}

function echo_tilaukset_raportti($tilaukset, $request = array()) {
	global $kukarow, $yhtiorow;

	$lopetus = "{$_SERVER['PHP_SELF']}////tee=hae_tilaukset//sarjanumero={$request['sarjanumero']}//asiakas={$request['asiakas']}//toimittaja={$request['toimittaja']}//tuote={$request['tuote']}//alku_pvm={$request['alku_pvm']}//loppu_pvm={$request['loppu_pvm']}";

	echo "<table>";
	echo "<thead>";
	echo "<tr>";
	echo "<th>".t('Tilausnumero')."</th>";
	echo "<th>".t('Asiakkaan')." / ".t('toimittajan nimi')."</th>";
	echo "<th>".t('Tuote')."</th>";
	echo "<th>".t('Sarjanumero')."</th>";
	echo "<th>".t('Luontiaika')."</th>";
	echo "<th>".t('Summa')."</th>";
	echo "<th>".t('Tyyppi')."</th>";
	echo "</tr>";
	echo "</thead>";
	foreach ($tilaukset as $tilaus) {
		echo "<tr class='aktiivi'>";
		echo "<td><a href='sarjanumerohistoria.php?tee=nayta_tilaus&tunnus={$tilaus['tunnus']}&lopetus={$lopetus}'>{$tilaus['tunnus']}</a></td>";
		echo "<td>{$tilaus['nimi']}</td>";
		echo "<td>{$tilaus['tuote']}</td>";
		echo "<td>{$tilaus['sarjanumerot']}</td>";
		echo "<td>{$tilaus['luontiaika']}</td>";
		echo "<td>{$tilaus['summa']}</td>";
		echo "<td>{$request['tyypit'][$tilaus['tyyppi']]}</td>";
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
