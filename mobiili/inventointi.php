<?php

$_GET['ohje'] = 'off';
$_GET['no_css'] = 'yes';
$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# Varmistuskoodi ja hyllypaikka talteen
if ($_GET['tee'] == 'varmistuskoodi' and (isset($_POST['varmistuskoodi']) or isset($_GET['lista']))) {
	setcookie("_varmistuskoodi", $_POST['varmistuskoodi']);
	setcookie("_tuotepaikka", $_POST['tuotepaikka']);
	setcookie("_lista", $_GET['lista']);
}
# Nollataan keksit
if ($_GET['tee'] == '' and (isset($_COOKIE['_tuotepaikka']) or isset($_COOKIE['_varmistuskoodi']))) {
	#setcookie("_varmistuskoodi");
	#setcookie("_tuotepaikka");
	#setcookie("_lista");
}

$_GET['ohje'] = 'off';
$_GET['no_css'] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# Haetaan tuotteita
function hae($viivakoodi='', $tuoteno='', $tuotepaikka='') {
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
	if (isset($query)) $result = pupe_query($query);

	while($row = mysql_fetch_assoc($result)) {
		$osumat[] = $row;
	}

	return $osumat;
}

# Index
if (!isset($tee)) {
	$title = t("Inventointi");
	include('views/inventointi/index.php');
	exit();}

# Haku
if ($tee == 'vapaa_inventointi') {

	$title = t("Vapaa Inventointi");
	# Haettu jollain
	if (isset($viivakoodi) or isset($tuoteno) or isset($tuotepaikka)) {
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

	if(empty($tuotepaikka) and empty($lista)) {
		exit("Virheelliset parametrit");
	}

	# Setataan takaisin nappi, riippuen mist‰ on tultu
	if (!empty($tuotepaikka)) {
		$back = http_build_query(array('tee' => 'vapaa_inventointi'));
	}
	else {
		$back = http_build_query(array('tee' => 'useita_listoja'));
	}

	if (!empty($lista)) {
		# Haetaan listan ensimm‰inen tuote
		# Skipataan kyseisen listalta jo inventoidut tuotteet
		$query = "SELECT
				tuoteno,
			 	concat_ws('-', hyllyalue, hyllynro, hyllyvali, hyllytaso) as tuotepaikka,
			 	concat(	lpad(upper(tuotepaikat.hyllyalue), 5, '0'),
			 			lpad(upper(tuotepaikat.hyllynro), 5, '0'),
			 			lpad(upper(tuotepaikat.hyllyvali), 5, '0'),
			 			lpad(upper(tuotepaikat.hyllytaso), 5, '0')) as sorttauskentta
			 	from tuotepaikat
			 	where inventointilista='{$lista}'
			 	and inventointilista_aika > '0000-00-00 00:00:00' # Inventoidut tuotteet on nollattu
			 	and yhtio='{$kukarow['yhtio']}'
			 	order by sorttauskentta, tuoteno
			 	limit 1";
			$result = pupe_query($query);
			$result = mysql_fetch_assoc($result);

			echo "<br><br>";
			var_dump($result);

			$tuotepaikka = $result['tuotepaikka'];
			$tuoteno = $result['tuoteno'];
	}

	$hylly = explode('-', $tuotepaikka);

	# Jos on talletettu tuotepaikka ja varmistuskoodi
	if (isset($_COOKIE['_tuotepaikka']) and isset($_COOKIE['_varmistuskoodi'])) {
		echo "<br>Edellinen: ".$_tuotepaikka." : ".$_varmistuskoodi;

		# Jos nykyinen tuotepaikka on sama kuin edellisell‰ kerralla
		# tarkistetaan koodi ja menn‰‰n eteenp‰in.
		if ($tuotepaikka == $_COOKIE['_tuotepaikka'] and isset($_COOKIE['_varmistuskoodi'])) {

			if (tarkista_varaston_hyllypaikka($hylly[0], $hylly[1], $hylly[2], $hylly[3], $_COOKIE['varmistuskoodi']) or $_varmistuskoodi = '9999') {
				$url = http_build_query(array('tee' => 'laske_maara', 'tuoteno' => $tuoteno, 'tuotepaikka' => $tuotepaikka));
				echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=inventointi.php?".$url."'>";
			} else {
				echo "tarkista_varasto feilas, v‰‰r‰t koodit!";
			}
		}
	}

	# Varmistuskoodi annettu
	if (is_numeric($varmistuskoodi)) {
		echo "tarkista_varaston_hyllypaikka($hylly[0], $hylly[1], $hylly[2], $hylly[3], $varmistuskoodi)";

		if (tarkista_varaston_hyllypaikka($hylly[0], $hylly[1], $hylly[2], $hylly[3], $varmistuskoodi) or $varmistuskoodi == '9999') {
			# hyllypaikka ja koodi OK!
			echo "Koodi OK!";
			$url = http_build_query(array('tee' => 'laske_maara', 'tuoteno' => $tuoteno, 'tuotepaikka' => $tuotepaikka));
			echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=inventointi.php?".$url."'>";
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

	$query = "	SELECT
				tuote.nimitys,
				tuote.tuoteno,
				tuote.yksikko,
				tuotepaikat.inventointilista,
				concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro,
							tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) tuotepaikka
				FROM tuotepaikat
				JOIN tuote on (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
				WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
				and tuote.tuoteno='{$tuoteno}'
				order by tuotepaikka";
	$result = pupe_query($query);
	$tuote = mysql_fetch_assoc($result);

	echo "<pre>";
	var_dump($tuote);
	echo "</pre>";

	# Disabloidaan apulaskuri jos pakkaus2-3 ei lˆydy?
	$query = "	SELECT tunnus
				FROM tuotteen_avainsanat
				WHERE tuoteno='{$tuoteno}'
				AND yhtio='{$kukarow['yhtio']}'
				AND (laji='pakkauskoko2' OR laji='pakkauskoko3');";
	$result = pupe_query($query);
	$count = mysql_num_rows($result);

	$disabled = ($count == 0) ? false : true;
	$maara = 0;

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

	# Haetaan tuotteen tiedot
	$query = "	SELECT tunnus,
				concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) tuotepaikka,
				hyllyalue,
				hyllynro,
				hyllyvali,
				hyllytaso
				FROM tuotepaikat
				WHERE tuoteno='{$tuote}'
				AND yhtio='{$kukarow['yhtio']}'
				ORDER BY tuotepaikka ";
	$result = pupe_query($query);
	$result = mysql_fetch_assoc($result);

	$hylly = array($tuote, $result['hyllyalue'], $result['hyllynro'], $result['hyllyvali'], $result['hyllytaso']);
	$hash = implode('###', $hylly);

	$tuote = array($result['tunnus'] => $hash);
	$maara = array($result['tunnus'] => $maara);

	include('inventointi_valmis.php');
}

if ($tee == 'useita_listoja') {

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

	$linkki = http_build_query(array(
			'tee' => 'varmistuskoodi',
			'lista' => ''));

	$title = t('Useita listoja');
	include('views/inventointi/listoja.php');
}

# Virheet
if (isset($errors)) {
	echo "<span class='error'>";
	foreach($errors as $virhe) {
		echo "{$virhe}<br>";
	}
	echo "</span>";
}
