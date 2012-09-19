<?php

# Varmistuskoodi ja hyllypaikka talteen
if ($_GET['tee'] == 'varmistuskoodi' and isset($_POST['varmistuskoodi'])) {
	setcookie("_varmistuskoodi", $_POST['varmistuskoodi']);
	setcookie("_tuotepaikka", $_POST['tuotepaikka']);
}
# Nollataan keksit
if ($_GET['tee'] == '' and (isset($_COOKIE['_tuotepaikka']) or isset($_COOKIE['_varmistuskoodi']))) {
	setcookie("_varmistuskoodi");
	setcookie("_tuotepaikka");
}

$_GET['ohje'] = 'off';
$_GET['no_css'] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# Haetaan tuotteita
function hae($viivakoodi='', $tuoteno='', $tuotepaikka='') {
	# Kaikki ei saa olla tyhji‰
	#assert(!empty($viivakoodi) and !empty($tuoteno) and !empty($tuotepaikka));
	global $kukarow;

	if (!empty($tuoteno)) {
		$query = "SELECT
				tuote.nimitys,
				tuote.tuoteno,
				concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
							tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) tuotepaikka
				FROM tuotepaikat
				JOIN tuote on (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
				WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
				AND tuote.tuoteno='$tuoteno'
				order by tuotepaikat.tuoteno
				limit 20";
	}
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
					limit 50;";
	}
	$result = pupe_query($query);

	while($row = mysql_fetch_assoc($result)) {
		$osumat[] = $row;
	}

	return $osumat;
}

# Index
if (!isset($tee)) {
	$title = t("Inventointi");
	include('views/inventointi/index.php');
	exit();
}

# Haku
if ($tee == 'vapaa_inventointi') {

	$title = t("Vapaa Inventointi");
	# Haettu jollain
	if (isset($viivakoodi) or isset($tuoteno) or (isset($tuotepaikka))) {
		$osumat = hae($viivakoodi,$tuoteno,$tuotepaikka);
		if(count($osumat) == 0) $errors[] = "Ei lˆytynyt";
	}
	# Lˆyty
	if (isset($osumat) and count($osumat) > 0) {
		include('views/inventointi/osumia.php');
	}
	# Ei lˆytyny
	else {
		include('views/inventointi/vapaa.php');
	}
}

# Varmistuskoodin tarkistus
if ($tee == 'varmistuskoodi') {

	$hylly = explode('-', $tuotepaikka);

	# TODO:
	# Hyllypaikka ja varmistuskoodi talteen. Jos tullaan uudelleen varmistuskoodiin ja hylly on sama
	# kuin edellisell‰ kerralla, muistetaan varmistuskoodi!

	# Jos on talletettu tuotepaikka ja varmistuskoodi
	if (isset($_COOKIE['_tuotepaikka']) and isset($_COOKIE['_varmistuskoodi'])) {
		echo "Edellinen: ".$_COOKIE['_tuotepaikka']." : ".$_COOKIE['_varmistuskoodi'];

		# Jos nykyinen tuotepaikka on sama kuin edellisell‰ kerralla
		# tarkistetaan koodi ja menn‰‰n eteenp‰in.
		if ($tuotepaikka == $_COOKIE['_tuotepaikka']) {
			echo "<br>Jee sama paikka kun ennenkin!<br>";
			echo "tarkista_varaston_hyllypaikka()";
			if (tarkista_varaston_hyllypaikka($hylly[0], $hylly[1], $hylly[2], $hylly[3], $_varmistuskoodi)) {
				echo "tarkista_varasto on OK!";
			} else {
				echo "tarkista_varasto feilas, v‰‰r‰t koodit!";
			}
		}
	}

	# Varmistuskoodi annettu
	if (is_numeric($varmistuskoodi)) {
		echo "tarkista_varaston_hyllypaikka($hylly[0], $hylly[1], $hylly[2], $hylly[3], $varmistuskoodi)";

		if (tarkista_varaston_hyllypaikka($hylly[0], $hylly[1], $hylly[2], $hylly[3], $varmistuskoodi)) {
			# hyllypaikka ja koodi OK!
			echo "Koodi OK!";
			echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=inventointi.php?".http_build_query(array('tee' => 'laske_maara', 'tuoteno' => $tuoteno))."'>";
		}
		else $errors[] = t("Varmistuskoodi on v‰‰rin!")."!";
	}
	else if(isset($varmistuskoodi)) $errors[] = t("Varmistuskoodin pit‰‰ olla numero");

	$title = t("Varmistuskoodi");
	include('views/inventointi/varmistuskoodi.php');
}

# Lasketaan tuotteet
if ($tee == 'laske_maara') {
	# Mit‰ parametrej‰ tarvitaan (tuoteno)
	assert(!empty($tuoteno));

	$query = "SELECT
				tuote.nimitys,
				tuote.tuoteno,
				tuote.yksikko,
				concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
							tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) tuotepaikka
				FROM tuotepaikat
				JOIN tuote on (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
				WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
				and tuote.tuoteno='{$tuoteno}'
				order by tuotepaikat.tuoteno";
	$result = pupe_query($query);
	$tuote = mysql_fetch_assoc($result);

	echo "<pre>";
	var_dump($tuote);
	echo "</pre>";

	# Disabloidaan apulaskuri jos pakkaus2-3 ei lˆydy?
	$query = "SELECT tunnus
				FROM tuotteen_avainsanat
				WHERE tuoteno='{$tuoteno}'
				AND yhtio='{$kukarow['yhtio']}'
				AND (laji='pakkauskoko2' OR laji='pakkauskoko3');";
	$result = pupe_query($query);
	$count = mysql_num_rows($result);
	$disabled = ($count == 0) ? false : true;

	$title = t("Laske m‰‰r‰");
	include('views/inventointi/laske_maara.php');
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

	# Pakkaus2
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

if ($tee == 'inventoi') {
	echo "Inventoidaan<br>";
	echo "Inventointilaji: Kiert‰v‰<br>";
	echo "Selite: P‰ivitt‰isinventointi k‰sip‰‰tteell‰<br>";
}

if ($tee == 'useita_listoja') {
	echo "Useita listoja";
}

# Virheet
if (isset($errors)) {
	echo "<span class='error'>";
	foreach($errors as $virhe) {
		echo "{$virhe}<br>";
	}
	echo "</span>";
}
