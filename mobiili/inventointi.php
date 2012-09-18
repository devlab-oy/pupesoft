<?php
var_dump($_POST);

$_GET['ohje'] = 'off';
$_GET['no_css'] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

echo "<table>";
echo "<tr><td><a href='inventointi.php'>Vapaa inventointi</a></td></tr>";
echo "<tr><td><a href='?tee=useita_listoja'>Keräyspaikat listalta</a></td></tr>";
echo "<tr><td><a href='?tee=useita_osumia'>Reservipaikat listalta</a></td></tr>";
echo "</tr></table>";

if (!isset($tee)) {
	$title = "Haku";

	# Tarkistetaan että edes yhdellä kentällä on haettu
	if (isset($viivakoodi) 	and empty($viivakoodi) 	and
		isset($tuoteno) 	and empty($tuoteno) 	and
		isset($tuotepaikka) and empty($tuotepaikka)) {

		$errors[] = "Vähintään yksi kenttä on syötettävä";
	}
	elseif ($tee == '' and (!empty($viivakoodi) or !empty($tuoteno) or !empty($tuotepaikka))) {
		# Haetaan tuotepaikan mukaan
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
					limit 20;";
		$result = pupe_query($query);

		while($row = mysql_fetch_assoc($result)) {
			$osumat[] = $row;
		}

		if (count($osumat) == 0) {
			$errors[] = "Ei löytynyt";
		}
		else {
			echo "osumia löyty";
			include('views/inventointi/osumia.php');
			break;
		}
	}
	include('views/inventointi/vapaa.php');
}

if ($tee == 'varmistuskoodi') {
	$title = t("Varmistuskoodi");

	echo $tuotepaikka."<br>";
	$hylly = explode('-', $tuotepaikka);

	if (isset($varmistuskoodi)) {
		if (empty($varmistuskoodi)) {
			echo "Syötä koodi";
		}
		elseif (!empty($varmistuskoodi)) {
			echo "Jee koodi, tarkastetaan!";
			echo "tarkista_varaston_hyllypaikka($hylly[0], $hylly[1], $hylly[2], $hylly[3], $varmistuskoodi)";

			if (tarkista_varaston_hyllypaikka($hylly[0], $hylly[1], $hylly[2], $hylly[3], $varmistuskoodi)) {
				# hyllypaikka ja koodi OK!
				echo "Koodi OK!";
				echo "<META HTTP-EQUIV='Refresh'CONTENT='3;URL=inventointi.php?tee=laske_maara'>";
			}
			else {
				echo "Koodi VÄÄRIN!";
			}
		}
	}

	include('views/inventointi/varmistuskoodi.php');
}
elseif ($tee == 'laske_maara') {
	include('views/inventointi/laske_maara.php');
}
elseif ($tee == 'apulaskuri') {
	include('views/inventointi/apulaskuri.php');
}


# Virheet
if (isset($errors)) {
	echo "<span class='error'>";
	foreach($errors as $virhe) {
		echo "{$virhe}<br>";
	}
	echo "</span>";
}

exit();
if ($tee == 'vapaa') {
	$title = t("Vapaa inventointi");

	if (isset($submit)) {
		$url = http_build_query($data);
		echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=inventointi.php?tee=osumia&{$url}'>"; exit();
	}
	if (isset($submit)) {
		if (empty($data['viivakoodi']) and empty($data['tuoteno']) and empty($data['tuotepaikka'])) {
			$errors[] = t("Vähintään yksi kenttä on syötettävä");
		}
		else {
			if($data['tuotepaikka'] != '') {

				# Haetaan tuotepaikan mukaan
				$query = "	SELECT
							tuote.nimitys,
							tuote.tuoteno,
							concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) tuotepaikka
							FROM tuotepaikat
							JOIN tuote on (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
							WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
							AND concat_ws('-', tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) like '{$data['tuotepaikka']}'
							order by tuotepaikat.tuoteno
							limit 20;";
				$result = pupe_query($query);

				while($row = mysql_fetch_assoc($result)) {
					$osumat[] = $row;
				}

				if (count($osumat) > 0) {
					include('views/inventointi/osumia.php');
				}
				else $errors[] = "Haulla ei löytyny mitään";
			}
		}
	}

	include('views/inventointi/vapaa.php');
}
if ($tee == 'useita_listoja') {
	$title = t("Useita listoja");
	include('views/inventointi/listoja.php');
}
if ($tee == 'osumia') {
	# Jos ei osumia mennään takasin edelliseen
	if (empty($viivakoodi) and empty($tuoteno) and empty($tuotepaikka)) {
		echo t("Vähintään yksi kenttä on syötettävä");
	}
	$title = t("Useita listoja");
	include('views/inventointi/osumia.php');
}

