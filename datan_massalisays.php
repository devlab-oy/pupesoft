<?php

require ("inc/parametrit.inc");

if (!isset($thumb_size_x, $thumb_size_y, $normaali_size_x, $normaali_size_y)) {
	echo "<font class='error'>Kuvakokoja ei ole m‰‰ritelty. Ei voida jatkaa!</font>";
	exit;
}

echo "<font class='head'>".t("Kuvien sis‰‰nluku")."</font><hr>";

if ($yhtiorow['kuvapankki_polku'] == '') {
	echo "<font class='error'>Kuvapankkia ei ole m‰‰ritelty. Ei voida jatkaa!</font>";
	exit;
}

function listdir($start_dir = '.') {

	$files = array();

	if (is_dir($start_dir)) {

		$fh = opendir($start_dir);

		while (($file = readdir($fh)) !== false) {
			if (strcmp($file, '.') == 0 or strcmp($file, '..') == 0 or substr($file, 0, 1) == ".") {
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

function konvertoi ($ykoko, $xkoko, $type, $taulu, $kuva, $dirri, $upfile1) {

	global $kukarow;

	// uniikki nimi
	list($usec, $sec) = explode(" ",microtime());
	$nimi = $usec+$sec;

	// extensio
	$path_parts = pathinfo($upfile1);
	$ext = strtolower($path_parts['extension']);

	// filekoko
	$image = getimagesize($upfile1);
	$leve = $image[0];
	$kork = $image[1];

	// tmpfile
	$upfilesgh = strtolower("/tmp/$nimi"."1.".$ext);
	$uusnimi = $dirri."/".$taulu."/".$type."/".$kuva;

	if ($ykoko > 0 and $ykoko < $kork and ($kork >= $leve or $xkoko == 0)) {
		// Haetaan kuvan v‰riprofiili
		exec("nice -n 20 identify -format %[colorspace] \"$upfile1\"", $identify);

		$colorspace = "sRGB";
		if ($identify[0] != "") $colorspace = $identify[0];

		// skaalataan kuva oikenakokoiseksi y:n mukaan
    	exec("nice -n 20 convert -resize x$ykoko -quality 90 -colorspace $colorspace -strip \"$upfile1\" \"$upfilesgh\"", $output, $error);
    }
	elseif ($xkoko > 0 and $xkoko < $leve and ($leve > $kork or $ykoko == 0)) {
		// Haetaan kuvan v‰riprofiili
		exec("nice -n 20 identify -format %[colorspace] \"$upfile1\"", $identify);

		$colorspace = "sRGB";
		if ($identify[0] != "") $colorspace = $identify[0];

		// skaalataan kuva oikenakokoiseksi x:n mukaan
  		exec("nice -n 20 convert -resize $xkoko -quality 90 -colorspace $colorspace -strip \"$upfile1\" \"$upfilesgh\"", $output, $error);
    }
	else {
		exec("cp -f \"$upfile1\" \"$upfilesgh\"");
		$error = 0;
    }

	if ($error != 0) {
		echo " &raquo; <font class='error'>Virhe $type kuvan skaalauksessa</font>";
	}
	else {

		unlink($uusnimi);
		$copy_boob = copy($upfilesgh, $uusnimi);

		if ($copy_boob === FALSE) {
		    echo "Kopiointi ep‰onnistui $upfilesgh $uusnimi $upfile1 <br>";
			$upfileall = "";
		}
		else {
			$upfileall = "$uusnimi";
		}
	}

	// poistetaan file
	unlink($upfilesgh);

	return $upfileall;

}

// testausta varten staattinen
//$dirri = "kuvapankki";
$dirri = $yhtiorow['kuvapankki_polku']."/".$kukarow['yhtio'];

$alkupituus = strlen($dirri) + 1;

if (!is_writable($dirri)) {
	echo "<font class='error'>Kuvapankkiin ($dirri) ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida jatkaa!<br></font>";
	exit;
}

if ($tee == 'GO') {

	if ($kasittele_kuvat != "1" and $thumb_kuvat != "1" and $normaali_kuvat != "1" and $paino_kuvat != "1" and $muut_kuvat != "1") {
		echo "<font class='message'>Et valinnut mit‰‰n k‰sitelt‰v‰‰!</font>";
		exit;
	}

	// k‰yd‰‰n l‰pi ensin k‰sitelt‰v‰t kuvat
	$files = listdir($dirri);

	if (isset($kasittele_kuvat) and $kasittele_kuvat == '1') {

		echo "<br>";
		echo "<font class='message'>K‰sitell‰‰n konvertoitavia kuvia:</font>";
		echo "<br><br>";

		foreach ($files as $file) {

			$polku = substr($file, $alkupituus);
			list($taulu, $toiminto, $kuva) = explode("/", $polku, 3);

			if (strtolower($taulu) != 'tuote') {
				echo "<font class='message'>Toistaiseksi voidaan vaan lukea tuotekuvia!</font>";
				exit;
			}

			$path_parts = pathinfo($kuva);
			$ext = $path_parts['extension'];

			$size = getimagesize($file);
			list($mtype, $crap) = explode("/", $size["mime"]);

			echo "<font class='message'>$kuva</font>";

			if ($size !== FALSE and $toiminto == 'kasittele' and $mtype == "image") {

				if (file_exists($file)) {

					// konvertoidaan thumb kuva ja siirret‰‰n thumb hakemistoon
					$thumbi = konvertoi($thumb_size_y, $thumb_size_x, 'thumb', $taulu, $kuva, $dirri, $file);

					if ($thumbi != '') {
						echo " &raquo; luotiin thumb-kuva.";
					}

					// konvertoidaan normaali kuva ja siirret‰‰n normaali hakemistoon
					$normi = konvertoi($normaali_size_y, $normaali_size_x, 'normaali', $taulu, $kuva, $dirri, $file);

					if ($normi != '') {
						echo " &raquo; luotiin normaali-kuva.";
					}

					if ($normi != '' and $thumbi != '') {
						// poistetaan orkkisfile
						unlink($file);
					}

					echo "<br>";

				}
			}
			else {
				echo " &raquo;  <font class='error'>Virhe! Voidaan k‰sitell‰ vain kuvia!<br>";
			}

		}

		echo "<br>";
	}

	echo "<font class='message'>P‰ivitet‰‰n tuotekuvat j‰rjestelm‰‰n:</font>";
	echo "<br><br>";

	// k‰yd‰‰n l‰pi dirikka nyt uudestaan
	$files = listdir($dirri);

	foreach ($files as $file) {

		$polku = substr($file, $alkupituus);
		list($taulu, $toiminto, $kuva) = explode("/", $polku, 3);

		if (strtolower($taulu) != 'tuote') {
			echo "<font class='message'>Toistaiseksi voidaan vaan lukea tuotekuvia!</font>";
			exit;
		}

		// jos ei olla ruksattu thumbeja niin ohitetaan ne
		if ($toiminto == 'thumb' and $thumb_kuvat != "1") {
			continue;
		}
		// jos ei olla ruksattu normaaleja niin ohitetaan ne
		elseif ($toiminto == 'normaali' and $normaali_kuvat != "1") {
			continue;
		}
		// jos ei olla ruksattu painokuvia niin ohitetaan ne
		elseif ($toiminto == 'paino' and $paino_kuvat != "1") {
			continue;
		}
		// jos ei olla ruksattu painokuvia niin ohitetaan ne
		elseif ($toiminto == 'muut' and $muut_kuvat != "1") {
			continue;
		}
		// ohitetaan aina k‰sitelt‰v‰t kuvat, koska ne on hoidettu jo ylh‰‰ll‰
		elseif ($toiminto == "kasittele") {
			continue;
		}

		// tuntematon toiminto
		if ($toiminto == 'thumb' and $toiminto == 'normaali' and $toiminto == 'paino' and $toiminto == 'muut' and $toiminto == 'kasittele') {
			echo "<font class='error'>Tuntematon toiminto $toiminto $thumb_kuvat!</font><br>";
			continue;
		}

		$path_parts = pathinfo($kuva);
		$ext = $path_parts['extension'];

		$koko = getimagesize($file);
		$filetype = $koko["mime"];

		// Jos ei olla saatu filetyyppi‰ niin arvotaan se vaikka filen nimest‰
		if ($filetype == "") {
			if ($ext == "jpg" or $ext == "jpeg") {
				$filetype = "image/jpeg";
			}
			elseif ($ext == "pdf" ) {
				$filetype = "application/pdf";
			}
			elseif (substr($ext, 0, 3) == "xls" ) {
				$filetype = "application/vnd.ms-excel";
			}
			elseif (substr($ext, 0, 3) == "doc" ) {
				$filetype = "application/msword";
			}
			else {
				$filetype = "application/octet-stream";
			}
		}

		$leve = $koko[0];
		$kork = $koko[1];
		$apukuva = $kuva;

		echo "<font class='message'>$kuva</font> ";

		// jos saimme jonkun imagesizen, katsellaan, ett‰ se on ok
		if ($koko !== FALSE) {
			if ($toiminto == 'thumb' and (($kork > $thumb_size_y and $thumb_size_y > 0) or ($leve > $thumb_size_x and $thumb_size_x > 0))) {
				// konvertoidaan thumb kuva ja siirret‰‰n thumb hakemistoon
				$thumbi = konvertoi($thumb_size_y, $thumb_size_x, 'thumb', $taulu, $kuva, $dirri, $file);
				if ($thumbi != "") {
					echo " &raquo; Skaalattiin thumb-kuva";
				}
				else {
					echo " &raquo; <font class='error'>Ohitetaan thumb-kuva, koska resoluutio $leve x $kork on liian suuri ja skaalaus ep‰onnistui!</font><br>";
					continue;
				}
			}

			if ($toiminto == 'normaali' and (($kork > $normaali_size_y and $normaali_size_y > 0) or ($leve > $normaali_size_x and $normaali_size_x > 0))) {
				// konvertoidaan normaali kuva ja siirret‰‰n normaali hakemistoon
				$normi = konvertoi($normaali_size_y, $normaali_size_x, 'normaali', $taulu, $kuva, $dirri, $file);
				if ($normi != "") {
					echo " &raquo; Skaalattiin normaalikuva-kuva";
				}
				else {
					echo " &raquo; <font class='error'>Ohitetaan normaali-kuva, koska resoluutio $leve x $kork on liian suuri ja skaalaus ep‰onnistui!</font><br>";
					continue;
				}
			}
		}

		unset($apuresult);

		$path_parts = pathinfo($kuva);
		$ext = $path_parts['extension'];
		$jarjestys = 0;

		// pit‰‰ kattoo onko nimess‰ h‰shsi‰
		if (strpos($kuva, "#") !== FALSE) {
			list($kuva, $jarjestys) = explode("#", $kuva);
			$kuva = "$kuva.$ext";
		}

		$apuselite = "";
		$mikakieli = "fi";

		// wildcard
		if (strpos($kuva, "%") !== FALSE) {

			$mihin = strpos($kuva, "%");
			$kuvanalku = substr($kuva, 0, $mihin);

			//kyseess‰ on k‰yttˆturvatiedot ja tuotekortti
			if (strpos($kuva,"%ktt") !== FALSE) {
				$mistakieli = strpos($kuva, "%ktt") + 4;
				$mikakieli = substr($kuva, $mistakieli, 2);

				if (strpos($mikakieli, "fi") !== FALSE or
					strpos($mikakieli, "se") !== FALSE or
					strpos($mikakieli, "en") !== FALSE or
					strpos($mikakieli, "ru") !== FALSE or
					strpos($mikakieli, "ee") !== FALSE or
					strpos($mikakieli, "no") !== FALSE or
					strpos($mikakieli, "de") !== FALSE) {

					$apuselite = t("K‰yttˆturvatiedote", $mikakieli);
				}
				else {
					$apuselite = t("K‰yttˆturvatiedote");
					$mikakieli = "fi";
				}
			}
			elseif (strpos($kuva,"%tko") !== FALSE) {
				$mistakieli = strpos($kuva, "%tko" ) + 4;
				$mikakieli = substr($kuva, $mistakieli, 2);

				if (strpos($mikakieli, "fi") !== FALSE or
					strpos($mikakieli, "se") !== FALSE or
					strpos($mikakieli, "en") !== FALSE or
					strpos($mikakieli, "ru") !== FALSE or
					strpos($mikakieli, "no") !== FALSE or
					strpos($mikakieli, "ee") !== FALSE or
					strpos($mikakieli, "de") !== FALSE) {

					$apuselite = t("Info", $mikakieli);
				}
				else {
					$apuselite = t("Info");
					$mikakieli = "fi";
				}
			}

			$query = "SELECT tuoteno, tunnus FROM tuote WHERE yhtio = '$kukarow[yhtio]' AND tuoteno LIKE '$kuvanalku%'";
			$apuresult = pupe_query($query);
		}

		if (file_exists($file)) {

			$filesize = filesize($file);

			$query = "SHOW variables like 'max_allowed_packet'";
			$result = pupe_query($query);
			$paketti = mysql_fetch_array($result);

			//echo "Kuvan koko:$filesize ($paketti[0]) ($paketti[1])<br>";

			if ($filesize > $paketti[1]) {
				echo " &raquo; <font class='error'>Ohitetaan kuva, koska tiedostokoko on liian suuri!</font><br>";
				continue;
			}

			if ($filesize == 0) {
				echo " &raquo; <font class='error'>Ohitetaan kuva, koska tiedosto on tyhj‰!</font><br>";
				continue;
			}

			$size = getimagesize($file);
			list($mtype, $crap) = explode("/", $size["mime"]);

			if ($mtype == "image") {
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

			if ($data === FALSE) {
				echo " &raquo; <font class='error'>Ohitetaan kuva, koska tiedoston luku ep‰onnistui!</font><br>";
				continue;
			}

			if (!isset($apuresult)) {
				$mihin = strpos($kuva, ".$ext");
				$tuoteno = substr($kuva, 0, "$mihin");

				$query = "SELECT tuoteno, tunnus FROM tuote WHERE yhtio = '$kukarow[yhtio]' AND tuoteno = '$tuoteno' LIMIT 1";
				$apuresult = pupe_query($query);
			}

			if (mysql_num_rows($apuresult) > 0) {

				echo " &raquo; Lis‰ttiin liitetiedosto tuotteelle ";

				// lis‰t‰‰n file
				while ($apurow = mysql_fetch_array($apuresult)) {

					$kuvaselite = "Tuotekuva";
					$kayttotarkoitus = "MU";

					if ($toiminto == 'thumb' and $apuselite == "") {
						$kayttotarkoitus = 'TH';
						$kuvaselite = "Tuotekuva pieni";
					}
					elseif ($toiminto == 'normaali' and $apuselite == "") {
						$kayttotarkoitus = 'TK';
						$kuvaselite = "Tuotekuva normaali";
					}
					elseif ($toiminto == 'paino' and $apuselite == "") {
						$kayttotarkoitus = 'HR';
						$kuvaselite = "Tuotekuva painokuva";
					}
					elseif ($apuselite != "") {
						$kuvaselite = $apuselite;
					}

					// poistetaan vanhat kuvat ja ...
					$query = "	DELETE FROM liitetiedostot
								WHERE yhtio 			= '$kukarow[yhtio]'
								and liitos 				= '$taulu'
								and liitostunnus 		= '$apurow[tunnus]'
								and kayttotarkoitus 	= '$kayttotarkoitus'
								and filename			= '$apukuva'";
					$delresult = pupe_query($query);

					// lis‰t‰‰n uusi
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
								kayttotarkoitus		= '$kayttotarkoitus',
								jarjestys			= '$jarjestys',
								laatija				= '$kukarow[kuka]',
								luontiaika			= now()";
					$insre = pupe_query($query);

					$query = "	UPDATE $taulu
								SET muutospvm = now(),
								muuttaja = '$kukarow[kuka]'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$apurow[tunnus]'";
					$insre = pupe_query($query);

					echo "$apurow[tuoteno] ";

				}

				echo "<br>";
				unlink($file);
			}
			else {
				echo " &raquo; <font class='error'>Ohitetaan kuva, koska kuvalle ei lˆytynyt tuotetta!</font><br>";
			}
		}
	}
	echo "<br>";
}

if ($tee == 'DUMPPAA' and $mitkadumpataan != '') {

	if (!is_writable($dirri."/".$mitkadumpataan)) {
		die("Kuvapankkiin/$mitkadumpataan ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida jatkaa!<br>");
	}

	if (strtolower($mitkadumpataan) != 'tuote') {
		echo "<font class='message'>Toistaiseksi voidaan vaan dumpata tuotekuvia!</font>";
		exit;
	}

	$query = "	SELECT *
				FROM liitetiedostot
				WHERE yhtio = '$kukarow[yhtio]'
				and liitos = '$mitkadumpataan'";
	$result = pupe_query($query);

	$dumpattuja = 0;
	$dellattuja = 0;

	while ($row = mysql_fetch_array($result)) {

		if ($row["liitos"] == '' or $row["kayttotarkoitus"] == '' or $row["filename"] == '') {
			echo "Ohitetaan kuva ($row[tunnus]), koska tarvittavia tietoja ei oltu tallennettu! $row[liitos] / $row[filename] / $row[kayttotarkoitus]<br>";
			continue;
		}

		if ($row["kayttotarkoitus"] == "TH") {
			$toiminto = 'thumb';
		}
		elseif ($row["kayttotarkoitus"] == "TK") {
			$toiminto = 'normaali';
		}
		elseif ($row["kayttotarkoitus"] == "HR") {
			$toiminto = 'paino';
		}
		elseif ($row["kayttotarkoitus"] == "MU") {
			$toiminto = "muut";
		}
		else {
			echo "<font class='message'>Tuntematon k‰yttˆtarkoitus $row[kayttotarkoitus]!</font>";
			continue;
		}

		$kokonimi = $dirri."/".$row["liitos"]."/".$toiminto;

		if (!is_writable($kokonimi)) {
			echo "<font class='error'>Hakemistolle $kokonimi ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida tallentaa kuvaa!</font><br>";
			continue;
		}

		$kokonimi .= "/".$row["filename"];

		if (!file_exists($kokonimi)) {

			$handle = fopen("$kokonimi", "x");

			if ($handle === FALSE) {
				echo "<font class='error'>Tiedoston $kokonimi kirjoitus ep‰onnistui!</font><br>";
			}
			else {
				file_put_contents($kokonimi, $row["data"]);
				fclose($handle);
			}

			$dumpattuja++;
		}

		if (isset($dumppaajapoista) and $dumppaajapoista == '1') {
			$query = "DELETE FROM liitetiedostot WHERE yhtio = '$kukarow[yhtio]' and liitos = '$mitkadumpataan' and tunnus = '$row[tunnus]'";
			$delresult = pupe_query($query);
			$dellattuja++;
		}
	}

	echo "<br><font class='message'>Vietiin $dumpattuja kuvaa kuvapankkiin</font><br>";

	if ($dellattuja > 0) {
		echo "<br><font class='message'>Poistettiin $dellattuja kuvaa j‰rjestelm‰st‰</font><br>";
	}

	echo "<br>";
}

$files = listdir($dirri);

$lukuthumbit 	= 0;
$lukunormit 	= 0;
$lukutconvertit = 0;
$lukupainot 	= 0;
$lukumuut 		= 0;

foreach ($files as $file) {

	$polku = substr($file, $alkupituus);
	list($taulu, $toiminto, $kuva) = explode("/", $polku, 3);

	if ($toiminto == 'thumb' and $kuva != '') {
		$lukuthumbit++;
	}
	if ($toiminto == 'paino' and $kuva != '') {
		$lukupainot++;
	}
	if ($toiminto == 'normaali' and $kuva != '') {
		$lukunormit++;
	}
	if ($toiminto == 'muut' and $kuva != '') {
		$lukumuut++;
	}
	if ($toiminto == 'kasittele' and $kuva != '') {
		$lukutconvertit++;
	}
}

// k‰yttˆliittym‰
echo "<form name='uliuli' method='post'>";
echo "<input type='hidden' name='tee' value='GO'>";

echo "<table>";
echo "<tr><th colspan='3'>Tuo kuvakuvapankista</th></tr>";
echo "<tr><td>Thumb</td><td>$lukuthumbit ".t("kpl")."</td><td><input type='checkbox' name='thumb_kuvat' value='1'></td><td class='back'></td></tr>";
echo "<tr><td>Normaali</td><td>$lukunormit ".t("kpl")."</td><td><input type='checkbox' name='normaali_kuvat' value='1'></td><td class='back'></td></tr>";
echo "<tr><td>Paino</td><td>$lukupainot ".t("kpl")."</td><td><input type='checkbox' name='paino_kuvat' value='1'></td></tr>";
echo "<tr><td>Muut</td><td>$lukumuut ".t("kpl")."</td><td><input type='checkbox' name='muut_kuvat' value='1'></td></tr>";
echo "<tr><td>Kasittele</td><td>$lukutconvertit ".t("kpl")."</td><td><input type='checkbox' name='kasittele_kuvat' value='1'></td></tr>";
echo "</table>";

echo "<br>";
echo "<input type='submit' value='".t("Tuo")."'>";
echo "</form>";

if ($lukuthumbit + $lukunormit + $lukupainot + $lukumuut + $lukutconvertit == 0) {

	echo "<br><br>";
	echo "<font class='head'>".t("Kuvien uloskirjoitus")."</font><hr>";

	echo "<form name='dumppi' method='post'>";
	echo "<input type='hidden' name='tee' value='DUMPPAA'>";
	echo "<input type='hidden' name='mitkadumpataan' value='tuote'>";

	echo "<table>";
	echo "<tr><th colspan='2'>Vie kuvat takaisin kuvapankkiin</th></tr>";
	echo "<tr><td>Poistetaanko j‰rjestelm‰st‰</td><td><input type='checkbox' name='dumppaajapoista' value='1'></td></tr>";
	echo "</table>";

	echo "<br>";
	echo "<input type='submit' value='".t("Vie")."'>";
	echo "</form>";
}

require ("inc/footer.inc");

?>