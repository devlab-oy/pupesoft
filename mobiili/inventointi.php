<?php
# Tuotepaikka ja varmistuskoodi talteen
if ($_GET['tee'] == 'laske' and isset($_POST['tuotepaikka']) and isset($_POST['varmistuskoodi'])) {
	setcookie("_tuotepaikka", $_POST['tuotepaikka']);
	setcookie("_varmistuskoodi", $_POST['varmistuskoodi']);
}
# Alkuvalikossa nollataan tuotepaikka ja varmistuskoodi
if ((!isset($_GET['tee']) and !isset($_POST['tee'])) and isset($_COOKIE['_tuotepaikka']) and isset($_COOKIE['_varmistuskoodi'])) {
	setcookie("_tuotepaikka", "", time()-3600);
	setcookie("_varmistuskoodi", "", time()-3600);
}

$_GET['ohje'] = 'off';
$_GET['no_css'] = 'yes';
$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

$errors = array();

# Haetaan tuotteita
function hae($viivakoodi='', $tuoteno='', $tuotepaikka='') {
	global $kukarow;

	# Viivakoodilla
	if (!empty($viivakoodi)) {}
	# Tuotenumerolla
	if (!empty($tuoteno)) {
		$query = "	SELECT
					tuote.nimitys,
					tuote.tuoteno,
					tuotepaikat.inventointilista,
					tuotepaikat.inventointilista_aika,
					concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
								tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) tuotepaikka
					FROM tuotepaikat
					JOIN tuote on (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
					WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
					AND tuote.tuoteno='$tuoteno'
					order by tuotepaikat.tuoteno
					limit 20";
	}
	# Tuotepaikalla
	if (!empty($tuotepaikka)) {
		$query = "	SELECT
					tuote.nimitys,
					tuote.tuoteno,
					concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
								tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) tuotepaikka
					FROM tuotepaikat
					JOIN tuote on (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
					WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
					AND concat_ws('-', tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
								tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) like '{$tuotepaikka}'
					order by tuotepaikat.tuoteno
					limit 50";
	}
	if (isset($query)) $result = pupe_query($query);

	while($row = mysql_fetch_assoc($result)) {
		$osumat[] = $row;
	}

	return $osumat;
}

# Tarkistetaan koodi
function tarkista_varmistuskoodi($tuotepaikka, $varmistuskoodi) {
	$hylly = explode('-', $tuotepaikka);

	# DEBUG!
	if($varmistuskoodi == 9999) return true;

	# Jos ei annettu varmistuskoodia ja keksissä on koodi, käytetään keksin koodia
	if ($varmistuskoodi == '' and isset($_COOKIE['_varmistuskoodi']) and $tuotepaikka == $_COOKIE['_tuotepaikka']) {
		$varmistuskoodi = $_COOKIE['_varmistuskoodi'];
	}

	if ($varmistuskoodi == '') {
		return false;
	}else {
		return tarkista_varaston_hyllypaikka($hylly[0], $hylly[1], $hylly[2], $hylly[3], $varmistuskoodi);
	}
}

# Valikko
if (!isset($tee)) {
	$title = t("Inventointi");
	include('views/inventointi/index.php');
}

# Haku
if ($tee == 'haku') {

	$title = t("Vapaa Inventointi");
	# Haettu jollain
	if (isset($viivakoodi) or isset($tuoteno) or isset($tuotepaikka)) {
		$tuotteet = hae($viivakoodi,$tuoteno,$tuotepaikka);
		if(count($tuotteet) == 0) $errors[] = "Ei löytynyt";
	}
	# Löydetyt osumat
	if (isset($tuotteet) and count($tuotteet) > 0) {
		include('views/inventointi/hakutulokset.php');
	}
	# Haku formi
	else {
		include('views/inventointi/haku.php');
	}
}

# Inventointilistat
if ($tee == 'listat') {

	# Haetaan inventointilistat
	$query = "	SELECT DISTINCT(inventointilista) as lista,
				count(tuoteno) as tuotteita,
				concat_ws('-', min(hyllyalue), max(hyllyalue)) as hyllyvali
				FROM tuotepaikat
				WHERE yhtio	= '{$kukarow['yhtio']}'
				and inventointilista > 0
				and inventointilista_aika > '0000-00-00 00:00:00'
				GROUP BY inventointilista_aika
				ORDER BY inventointilista";
	$result = pupe_query($query);

	while($row = mysql_fetch_assoc($result)) {
		$listat[] = $row;
	}

	if (count($listat) > 0) {
		$title = t('Useita listoja');
		include('views/inventointi/listat.php');
	}
	else {
		$errors[] =  t("Inventoitavia eriä ei ole");
		$title = t("Inventointi");
		include('views/inventointi/index.php');	}
}

# Lasketaan tuotteet
if ($tee == 'laske' or $tee == 'inventoi') {
	# Mitä parametrejä tarvitaan (tuoteno tai lista)
	assert(!empty($tuoteno) or !empty($lista));

	if (!isset($maara)) $maara = 0;
	if (!is_numeric($maara)) $errors[] = t("Määrän on oltava numero");

	$disabled = false;

	# Inventoidaan listaa
	if (!empty($lista)) {
		# Haetaan listan 'ensimmäinen' tuote
		$query = "	SELECT
					tuote.nimitys,
					tuote.tuoteno,
					tuote.yksikko,
					tuotepaikat.inventointilista,
			 		concat_ws('-', tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
			 					tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) as tuotepaikka,
			 		concat(	lpad(upper(tuotepaikat.hyllyalue), 5, '0'),
			 				lpad(upper(tuotepaikat.hyllynro), 5, '0'),
			 				lpad(upper(tuotepaikat.hyllyvali), 5, '0'),
			 				lpad(upper(tuotepaikat.hyllytaso), 5, '0')) as sorttauskentta
			 		FROM tuotepaikat
			 		JOIN tuote on (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
			 		WHERE tuotepaikat.yhtio='{$kukarow['yhtio']}'
			 		AND inventointilista='{$lista}'
			 		AND inventointilista_aika > '0000-00-00 00:00:00' # Inventoidut tuotteet on nollattu
			 		ORDER BY sorttauskentta, tuoteno
			 		LIMIT 1";
		$result = pupe_query($query);
		$tuote = mysql_fetch_assoc($result);
	}
	# Inventoidaan haulla
	else {
		# Haetaan tuotteen ja tuotepaikan tiedot
		# TODO: viivakoodi, tuoteno tai tuotepaikka
		$query = "	SELECT
					tuote.nimitys,
					tuote.tuoteno,
					tuote.yksikko,
					#tuotepaikat.inventointilista,
					concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
								tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) as tuotepaikka
					FROM tuotepaikat
					JOIN tuote on (tuote.yhtio=tuotepaikat.yhtio and tuote.tuoteno=tuotepaikat.tuoteno)
					WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
					AND tuote.tuoteno='{$tuoteno}'
					AND concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
								tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) = '{$tuotepaikka}'";
		$result = pupe_query($query);
		$tuote = mysql_fetch_assoc($result);
	}

	# Tarkistetaan varmistuskoodi
	if (tarkista_varmistuskoodi($tuote[tuotepaikka], $varmistuskoodi)) {
		$title = t("Laske määrä");
		include('views/inventointi/laske.php');

	}
	else {
		if (isset($varmistuskoodi)) $errors[] = "Virheellinen varmistuskoodi";
		include('views/inventointi/varmistuskoodi.php');
	}

	if ($tee == 'inventoi' and count($errors) == 0) {
		$tee = 'inventoidaan';
	}

}

if ($tee == 'apulaskuri') {

	# Pakkaus1
	$query = "SELECT
				if(myynti_era > 0, myynti_era, 1) as myynti_era,
				yksikko
				FROM tuote
				WHERE tuoteno='{$tuoteno}' AND yhtio='{$kukarow['yhtio']}'";
	$result = pupe_query($query);
	$p1 = mysql_fetch_assoc($result);

	# Pakkaus2
	$query ="SELECT
				selite as myynti_era,
				selitetark as yksikko
				FROM tuotteen_avainsanat
				WHERE tuoteno='{$tuoteno}'
				AND yhtio='{$kukarow['yhtio']}'
				AND laji='pakkauskoko2'";
	$result = pupe_query($query);
	$p2 = mysql_fetch_assoc($result);

	# Pakkaus3
	$query ="SELECT
				selite as myynti_era,
				selitetark as yksikko
				FROM tuotteen_avainsanat
				WHERE tuoteno='{$tuoteno}'
				AND yhtio='{$kukarow['yhtio']}'
				AND laji='pakkauskoko3'";
	$result = pupe_query($query);
	$p3 = mysql_fetch_assoc($result);

	$title = t("Apulaskuri");
	include('views/inventointi/apulaskuri.php');
}

if ($tee == 'inventoidaan') {
	assert(!empty($tuoteno) or !empty($maara) or !empty($tuotepaikka));

	# Haetaan tuotteen tiedot
	$query = "	SELECT tunnus,
				concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) tuotepaikka,
				hyllyalue,
				hyllynro,
				hyllyvali,
				hyllytaso
				FROM tuotepaikat
				WHERE tuoteno='{$tuoteno}'
				AND yhtio='{$kukarow['yhtio']}'
				AND concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
							tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) = '{$tuotepaikka}'
				ORDER BY tuotepaikka ";
	$result = pupe_query($query);
	$result = mysql_fetch_assoc($result);

	# Jos tuotteen tiedot OK, voidaan inventoida
	if ($result) {
		$hylly = array($tuoteno, $result['hyllyalue'], $result['hyllynro'], $result['hyllyvali'], $result['hyllytaso']);
		$hash = implode('###', $hylly);

		$tuote = array($result['tunnus'] => $hash);
		$maara = array($result['tunnus'] => $maara);

		# inventointi
		$tee = 'VALMIS';
		require('../inventoi.php');

		# Jos inventoidaan listalta, palataan inventoimaan listan seuraava tuote.
		if($lista != 0) {
			$paluu_url = http_build_query(array('tee' => 'laske', 'lista' => $lista));
		}
		# Palataan alkuun
		else {
			$paluu_url ='';
		}
		echo "<META HTTP-EQUIV='Refresh'CONTENT='4;URL=inventointi.php?".$paluu_url."'>";
	}
	else {
		$errors[] = "Virhe inventoinnissa.";
	}
}

# Virheet
if (isset($errors)) {
	echo "<span class='error'>";
	foreach($errors as $virhe) {
		echo "{$virhe}<br>";
	}
	echo "</span>";
}
