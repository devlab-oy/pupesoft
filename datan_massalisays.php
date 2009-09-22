<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Kuvien sisäänluku")."</font><hr>";

$thumbkork 		= "65";
$thumbleve 		= "96";
$normaalikork 	= "480";
$normaalileve 	= "640";

if ($yhtiorow['kuvapankki_polku'] == '') {
	die("Kuvapankkia ei ole määritelty. Ei voida jatkaa!<br>");
}

function listdir($start_dir='.') {

	$files = array();

	if (is_dir($start_dir)) {

		$fh = opendir($start_dir);

		while (($file = readdir($fh)) !== false) {
			if (strcmp($file, '.') == 0 or strcmp($file, '..') == 0 or substr($file,0,1) == ".") {
				continue;
			}
			$filepath = $start_dir . '/' . $file;

			if (is_dir($filepath)) {
				$files = array_merge($files, listdir($filepath));
			}
			else {
				array_push($files, $filepath);
			}
		}
		closedir($fh);
		sort($files);
	}
	else {
		$files = false;
	}

	return $files;
}

function konvertoi ($ykoko,$xkoko,$type,$taulu,$kuva,$dirri,$upfile1) {

	global $kukarow;

	list($usec, $sec) = explode(" ",microtime());

	$nimi      = $usec+$sec; // uniikki nimi
	$resize    = ""; // resize komento
	$crop      = ""; // crop komento
	$upfileall = ""; // palautus

	$path_parts = pathinfo($upfile1);
	$ext = strtolower($path_parts['extension']);

	$image = getimagesize($upfile1);	// lähetetty kuva

	$leve = $image[0];
	$kork = $image[1];

	// tehään pienentämällä
	$upfilesgh = strtolower("/tmp/$nimi"."1.".$ext);

    // skaalataan kuva oikenakokoiseksi
    exec("nice -n 20 convert -resize x$ykoko -quality 80 $upfile1 $upfilesgh", $output, $error);

	if ($error != 0) {
		echo "Virhe kuvan muokkauksessa<br>";
	}
	else {
		$uusnimi = $dirri."/".$taulu."/".$type."/".$kuva;

		copy($upfilesgh,$uusnimi);

		if (!copy($upfilesgh,$uusnimi)) {
		    echo "Kopiointi epäonnistui $upfile1<br>";
			$upfileall = "";
		}
		else {
			$upfileall .= "$uusnimi";
		}
	}

	// poistetaan file
	system("rm -f $upfilesgh");

	return $upfileall;

}

// testausta varten staattinen
//$dirri = "kuvapankki";
$dirri = $yhtiorow['kuvapankki_polku'];
$dirri .= "/".$kukarow['yhtio'];

$alkupituus = strlen($dirri)+1;

if (!is_writable($dirri)) {
	die("Kuvapankkiin ($dirri) ei ole määritelty kirjoitusoikeutta. Ei voida jatkaa!<br>");
}

$files = listdir($dirri);

if ($tee == 'GO') {

	if (isset($convertit) and $convertit == '1') {
		echo "<br>Käsitellään konvertoitavia kuvia:<br>";

		foreach ($files as $file) {
			$polku = substr($file,$alkupituus);

			list($taulu, $toiminto, $kuva) = explode("/", $polku, 3);

			$path_parts = pathinfo($kuva);
			$ext = $path_parts['extension'];

			$size = getimagesize($file);
			list($mtype, $crap) = explode("/", $size["mime"]);

			if ($toiminto == 'kasittele' and $mtype == "image") {
				if (file_exists($file)) {

					echo "$kuva ";
					$thumbi = konvertoi($thumbkork,$thumbleve,'thumb',$taulu,$kuva,$dirri,$file);

					if ($thumbi != '') {
						echo "luotiin thumbnailkuva. ";
					}

					$normi = konvertoi($normaalikork,$normaalileve,'normaali',$taulu,$kuva,$dirri,$file);

					if ($normi != '') {
						echo "luotiin normaali kuva.";
					}

					echo "<br>";

					if ($normi != '' and $thumbi != '') {
						// poistetaan orkkisfile
						system("rm -f $file");
					}

					continue;
				}
			}

		}

		echo "<br>";
	}

	$files = listdir($dirri);

	foreach ($files as $file) {
		$polku = substr($file,$alkupituus);

		list($taulu, $toiminto, $kuva) = explode("/", $polku, 3);

		$path_parts = pathinfo($kuva);
		$ext = $path_parts['extension'];

		$koko = getimagesize($file);
		$filetype = $koko["mime"];
		$leve = $koko[0];
		$kork = $koko[1];

		if (strtoupper($ext) != "PDF" and $toiminto != "paino") {
			if ($toiminto == 'thumb') {
				if ($kork <> $thumbkork) {
					continue;
				}
			}
			elseif ($toiminto == 'normaali') {
				if ($kork <> $normaalikork) {
					continue;
				}
			}
			else {
				continue;
			}
		}

		$apukuva = $kuva;

		if (strtolower($taulu) != 'tuote') {
			die("Toistaiseksi voidaan vaan lukea tuotekuvia!");
		}

		if (strtoupper($ext) != "PDF" and $toiminto != "paino") {
			if ($toiminto == 'kasittele') {
				// näistä ollaan jo tehty uudet versiot
				continue;
			}
			elseif ($toiminto == 'alkuperainen') {
				// toistaiseksi ei tallenneta alkuperäisiä
				continue;
			}
		}


		unset($apuresult);

		$path_parts = pathinfo($kuva);
		$ext = $path_parts['extension'];
		//echo "$ext<br>";

		// pitää kattoo onko nimessä wildkardia ta hässiä
		if (strpos($kuva,"#") !== FALSE) {
			// (nro)
			$mihin = strpos($kuva,"#");

			$kuva = substr($kuva, 0, $mihin).".".$ext;
		}

		$apuselite = "";
		$mikakieli = "";

		if (strpos($kuva,"%") !== FALSE) {
			// (wildkaard)
			$mihin = strpos($kuva,"%");
			$kuvanalku = substr($kuva,0,$mihin);
			//echo "Alku: $kuvanalku<br>";

			//kyseessä on käyttöturvatiedot ja tuotekortti
			if (strpos($kuva,"%ktt") !== FALSE) {
				$mistakieli = strpos($kuva,"%ktt")+4;
				$mikakieli = substr($kuva,$mistakieli,2);

				if (strpos($mikakieli,"fi") !== FALSE or
					strpos($mikakieli,"se") !== FALSE or
					strpos($mikakieli,"en") !== FALSE or
					strpos($mikakieli,"ru") !== FALSE or
					strpos($mikakieli,"ee") !== FALSE or
					strpos($mikakieli,"de") !== FALSE) {
					$apuselite = t("Käyttöturvatiedote",$mikakieli);
				}
				else {
					$apuselite = t("Käyttöturvatiedote");
					$mikakieli = "fi";
				}

			}
			elseif (strpos($kuva,"%tko") !== FALSE) {
				$mistakieli = strpos($kuva,"%tko")+4;
				$mikakieli = substr($kuva,$mistakieli,2);

				if (strpos($mikakieli,"fi") !== FALSE or
					strpos($mikakieli,"se") !== FALSE or
					strpos($mikakieli,"en") !== FALSE or
					strpos($mikakieli,"ru") !== FALSE or
					strpos($mikakieli,"ee") !== FALSE or
					strpos($mikakieli,"de") !== FALSE) {
					$apuselite = t("Tuotekortti",$mikakieli);
				}
				else {
					$apuselite = t("Tuotekortti");
					$mikakieli = "fi";
				}

			}

			$query = "SELECT tuoteno, tunnus FROM tuote WHERE yhtio = '$kukarow[yhtio]' AND tuoteno LIKE '$kuvanalku%'";
			$apuresult = mysql_query($query) or pupe_error($query);
		}

		if (file_exists($file)) {
			$filesize = filesize($file);

			$query = "show variables like 'max_allowed_packet'";
			$result = mysql_query($query) or pupe_error($query);
			$paketti = mysql_fetch_array($result);

			//echo "Kuvan koko:$filesize ($paketti[0]) ($paketti[1])<br>";

			if ($filesize > $paketti[1]) {
				echo "Kuva $kuva on liian suuri. Hylätään<br>";
				continue;
			}

			if ($filesize == 0) {
				echo "Kuva $kuva oli tyhjä. Hylätään<br>";
				continue;
			}

			$size = getimagesize($file);

			list($mtype, $crap) = explode("/", $size["mime"]);

			if($mtype == "image") {
				$image_width 	= $size[0];
				$image_height 	= $size[1];
				$image_bits 	= $size["bits"];
				$image_channels	= $size["channels"];
			}
			else {
				$image_width 	= "";
				$image_height 	= "";
				$image_bits 	= "";
				$image_channels	= "";
			}

			$filee = fopen($file, 'r');
			$data = addslashes(fread($filee, $filesize));

			if (!isset($apuresult)) {
				$mihin = strpos($kuva,".$ext");
				$tuoteno = substr($kuva,0,"$mihin");

				$query = "SELECT tuoteno, tunnus FROM tuote WHERE yhtio = '$kukarow[yhtio]' AND tuoteno = '$tuoteno' LIMIT 1";
				$apuresult = mysql_query($query) or pupe_error($query);
			}

			if (mysql_num_rows($apuresult) > 0) {

				// lisätään file
				while ($apurow = mysql_fetch_array($apuresult)) {
					$kuvaselite = "Tuotekuva";

					if (($toiminto == 'thumb' or $toiminto == 'TH') and $apuselite == "") {
						$toiminto = 'TH';
						$kuvaselite .= " pieni";
					}
					elseif (($toiminto == 'normaali' or $toiminto == 'TK') and $apuselite == "") {
						$toiminto = 'TK';
						$kuvaselite .= " normaali";
					}
					elseif (($toiminto == 'paino' or $toiminto == 'HR') and $apuselite == "") {
						$toiminto = 'HR';
						$kuvaselite .= " painokuva";
					}
					else {
						if ($apuselite != "") {
							$kuvaselite = $apuselite;
						}

						$toiminto = "MU";
					}

					// poistetaan vanhat samat ja korvataan uusilla
					$query = "	DELETE FROM liitetiedostot
								WHERE yhtio 			= '$kukarow[yhtio]'
								and liitos 				= '$taulu'
								and liitostunnus 		= '$apurow[tunnus]'
								and kayttotarkoitus 	= '$toiminto'
								and filename			= '$apukuva'";
					$delresult = mysql_query($query) or pupe_error($query);
					$dellatut = mysql_affected_rows();

					if ($dellatut > 0) {
						echo "Poistettiin $dellatut $kuva kuvaa<br>";
					}

					$query = "	INSERT INTO liitetiedostot SET
								yhtio    			= '$kukarow[yhtio]',
								liitos   			= '$taulu',
								liitostunnus 		= '$apurow[tunnus]',
								data     			= '$data',
								selite   			= '$kuvaselite',
								kieli				= '$mikakieli',
								filename 			= '$apukuva',
								filesize 			= '$filesize',
								filetype 			= '$filetype',
								image_width			= '$image_width',
								image_height		= '$image_height',
								image_bits			= '$image_bits',
								image_channels		= '$image_channels',
								kayttotarkoitus		= '$toiminto',
								laatija				= '$kukarow[kuka]',
								luontiaika			= now()";
					$insre = mysql_query($query) or pupe_error($query);

					$query = "	UPDATE $taulu
								SET muutospvm = now(),
								muuttaja = '$kukarow[kuka]'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$apurow[tunnus]'";
					$insre = mysql_query($query) or pupe_error($query);

					echo "Lisättiin $toiminto $kuva tuotteelle $apurow[tuoteno]<br>";
				}
				system("rm -f \"$file\"");
			}
			else {
				echo "Tuotetta ei löytynyt kuvalle $kuva !!!<br>";
			}
		}
	}
}

if ($tee == 'DUMPPAA' and $mitkadumpataan != '') {

	if (!is_writable($dirri."/".$mitkadumpataan)) {
		die("Kuvapankkiin/$mitkadumpataan ei ole määritelty kirjoitusoikeutta. Ei voida jatkaa!<br>");
	}

	$query = "	SELECT *
				FROM liitetiedostot
				WHERE yhtio = '$kukarow[yhtio]' and liitos = '$mitkadumpataan'";
	$result = mysql_query($query) or pupe_error($query);

	$dumpattuja = 0;
	$dellattuja = 0;

	while ($row = mysql_fetch_array($result)) {
		if ($row["liitos"] == '' or $row["kayttotarkoitus"] == '' or $row["filename"] == '') {
			continue;
		}

		$kokonimi = $dirri."/".$row["liitos"]."/".$row["kayttotarkoitus"];

		if (!is_writable($kokonimi)) {
			die("$kokonimi ei ole määritelty kirjoitusoikeutta. Ei voida jatkaa!<br>");
		}

		$kokonimi .= "/".$row["filename"];

		if (!file_exists($kokonimi)) {
			$handle = fopen("$kokonimi", "x");

			if ($handle === FALSE) {
				echo "Tiedoston $row[filename] luonti epäonnistui!<br>";
			}
			else {
				file_put_contents($kokonimi,$row["data"]);
				fclose($handle);
			}

			$dumpattuja++;
		}

		if (isset($dumppaajapoista) and $dumppaajapoista == '1') {
			$query = "DELETE FROM liitetiedostot WHERE yhtio = '$kukarow[yhtio]' and liitos = '$mitkadumpataan' and tunnus = '$row[tunnus]'";
			$delresult = mysql_query($query) or pupe_error($query);
			$dellattuja ++;
		}
	}

	echo "Vietiin $dumpattuja kuvaa kuvapankkiin<br>";

	if (isset($dumppaajapoista) and $dumppaajapoista == '1') {
		echo "Poistettiin $dellattuja kuvaa järjestelmästä<br>";
	}
}


$files = listdir($dirri);

$lukuthumbit = 0;
$lukunormit = 0;
$lukutconvertit = 0;
$lukupainot = 0;

foreach ($files as $file) {
	$polku = substr($file,$alkupituus);

	list($taulu, $toiminto, $kuva) = explode("/", $polku, 3);

	if ($toiminto == 'thumb' and $kuva != '') {
		$lukuthumbit ++;
	}
	if ($toiminto == 'paino' and $kuva != '') {
		$lukupainot ++;
	}
	if ($toiminto == 'normaali' and $kuva != '') {
		$lukunormit ++;
	}
	if ($toiminto == 'kasittele' and $kuva != '') {
		$lukutconvertit ++;
	}

}

// käyttöliittymä
echo "<table><form name='uliuli' method='post' action='$PHP_SELF'>";
echo "<input type='hidden' name='tee' value='GO'>";

echo "<tr><th colspan='2'>Tuo kuvat kuvapankista</th></tr>";

$chekkis1 = "";
$chekkis2 = "";
$chekkis3 = "";

if ($tee == '') {
	$chekkis1 = "CHECKED";
	$chekkis2 = "CHECKED";
}

if ($thumbikset == '1') {
	$chekkis1 = "CHECKED";
}
if ($normit == '1') {
	$chekkis2 = "CHECKED";
}
if ($convertit == '1') {
	$chekkis3 = "CHECKED";
}

/*echo "<tr><td>Tuo Thumbnailit: </td><td> ($lukuthumbit) <input type='checkbox' name='thumbikset' value='1' $chekkis1></td></tr>";
echo "<tr><td>Tuo Normaalit: </td><td> ($lukunormit) <input type='checkbox' name='normit' value='1' $chekkis2></td></tr>";
echo "<tr><td colspan='2' class='back'>&nbsp;</td></tr>";*/
echo "<tr><td>Käsittele/Tuo Käsiteltävät: </td><td> ($lukutconvertit) <input type='checkbox' name='convertit' value='1' $chekkis3></td></tr>";
echo "<td class='back' colspan='2'><br><input type='submit' value='".t("Tuo")."'></td>";
echo "</table>";
echo "</form>";

echo "<br><br><br>";

if ($lukuthumbit+$lukunormit+$lukupainot == 0) {
	echo "<table><form name='dumppi' method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='DUMPPAA'>";
	echo "<tr><th colspan='2'>Vie kuvat takaisin kuvapankkiin</th></tr>";
	echo "<tr><td>Valitse kuvalaji: </td>
		<td><select name = 'mitkadumpataan'>
		<option value=''>".t("Valitse")."</option>
		<option value='tuote'>".t("Tuotekuvat")."</option>
		</select></td></tr>";
	echo "<tr><td>Poistetaanko järjestelmästä: </td><td><input type='checkbox' name='dumppaajapoista' value='1'></td></tr>";
	echo "<td class='back' colspan='2'><br><input type='submit' value='".t("Vie")."'></td>";
	echo "</table>";
	echo "</form>";
}

require ("inc/footer.inc");

?>