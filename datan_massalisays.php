<?php

if (1 == 2) {
	/*require ("inc/connect.inc");
	require ("inc/functions.inc");


	$polut = array();

	$polut[] = "allr/tuote/kasittele";
	$polut[] = "allr/tuote/thumb";
	$polut[] = "allr/tuote/normaali";
	$polut[] = "allr/tuote/alkuperainen";

	$polut[] = "allr/asiakas/kasittele";
	$polut[] = "allr/asiakas/thumb";
	$polut[] = "allr/asiakas/normaali";
	$polut[] = "allr/asiakas/alkuperainen";

	foreach ($polut as $polku) {
		for ($i=0; $i < 10; $i++) { 
				$tsydeemi = "cp ".$dirri."/jlo.png ".$dirri."/".$polku."/jlo".$i.".png";
				system($tsydeemi);
		}

	}*/
}

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Kuvien sis‰‰nluku")."</font><hr>";

$thumbkork = "65";
$thumbleve = "96";
$normaalikork = "480";
$normaalileve = "640";

if ($yhtiorow['kuvapankki_polku'] == '') {
	die("Kuvapankkia ei ole m‰‰ritelty. Ei voida jatkaa!<br>");
}

Function listdir($start_dir='.') {

  $files = array();
  if (is_dir($start_dir)) {
    $fh = opendir($start_dir);
    while (($file = readdir($fh)) !== false) {
      # loop through the files, skipping . and .., and recursing if necessary
      if (strcmp($file, '.')==0 || strcmp($file, '..')==0 || substr($file,0,1)==".") continue;
      $filepath = $start_dir . '/' . $file;
      if ( is_dir($filepath) )
        $files = array_merge($files, listdir($filepath));
      else
        array_push($files, $filepath);
    }
    closedir($fh);
  } else {
    # false if the function was called with an invalid non-directory argument
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
	
	$image = getimagesize($upfile1);	// l‰hetetty kuva
	
	$leve = $image[0];       

	$kork = $image[1];
	
	// teh‰‰n pienent‰m‰ll‰
	$upfilesgh = strtolower("/tmp/$nimi"."1.".$ext);
	
	
    // skaalataan kuva oikenakokoiseksi
    exec("nice -n 20 convert -resize x$ykoko -quality 80 $upfile1 $upfilesgh");
	
	$uusnimi = $dirri."/".$taulu."/".$type."/".$kuva;
	
	copy($upfilesgh,$uusnimi);
	
	if (!copy($upfilesgh,$uusnimi)) {
	    echo "Kopiointi ep‰onnistui $upfile1<br>";
		$upfileall = "";
	}
	else {
		$upfileall .= "$uusnimi";
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
	die("Kuvapankkiin ($dirri) ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida jatkaa!<br>");
}

$files = listdir($dirri);

if ($tee == 'GO') {
	
	if (isset($convertit) and $convertit == '1') {
		echo "<br>K‰sitell‰‰n konvertoitavia kuvia:<br>";
		
		foreach ($files as $file) {
			$polku = substr($file,$alkupituus);
	
			list($taulu, $toiminto, $kuva) = split("/", $polku, 3);
			
			$path_parts = pathinfo($kuva);
			$ext = $path_parts['extension'];
	
			list($mtype, $crap) = explode("/", $size["mime"]);					
	
			if ($toiminto == 'kasittele' and $mtype == "image") {
				if (file_exists($file)) {
					$thumbi = konvertoi($thumbkork,$thumbleve,'thumb',$taulu,$kuva,$dirri,$file);
					if ($thumbi != '') {
						echo "Luotiin thumbnailkuva $kuva<br>";
					}
					
					$normi = konvertoi($normaalikork,$normaalileve,'normaali',$taulu,$kuva,$dirri,$file);
					
					if ($normi != '') {
						echo "Luotiin normaali kuva $kuva<br>";
					}
					
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
	
		list($taulu, $toiminto, $kuva) = split("/", $polku, 3);
		
		$path_parts = pathinfo($kuva);
		$ext = $path_parts['extension'];
		
		$koko = getimagesize($file);
		$filetype = $koko["mime"];
		$leve = $koko[0];       
		$kork = $koko[1];
		
		if (strtoupper($ext) != "PDF") {
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
		
		if (strtoupper($ext) != "PDF") {
			if ($toiminto == 'kasittele') {
				// n‰ist‰ ollaan jo tehty uudet versiot
				continue;
			}
			elseif ($toiminto == 'alkuperainen') {
				// toistaiseksi ei tallenneta alkuper‰isi‰
				continue;
			}
		}
		
		
		unset($apuresult);
	
		$path_parts = pathinfo($kuva);
		$ext = $path_parts['extension'];
		//echo "$ext<br>";
	
		// pit‰‰ kattoo onko nimess‰ wildkardia ta h‰ssi‰
		if (strpos($kuva,"#") !== FALSE) {
			// (nro)
			$mihin = strpos($kuva,"#");
		
			$kuva = substr($kuva,0,"$mihin").".".$ext;
		
			//echo "kuva: $kuva<br>";
		
		
		}
	
		if (strpos($kuva,"%") !== FALSE) {
			// (wildkaard)
			$mihin = strpos($kuva,"%");
			$kuvanalku = substr($kuva,0,$mihin);
			//echo "Alku: $kuvanalku<br>";
		
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
				echo "Kuva $kuva on liian suuri. Hyl‰t‰‰n<br>";
				continue;
			}
			
			if ($filesize == 0) {
				echo "Kuva $kuva oli tyhj‰. Hyl‰t‰‰n<br>";
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
				
				// lis‰t‰‰n file
				while ($apurow = mysql_fetch_array($apuresult)) {
					// poistetaan vanhat samat ja korvataan uusilla
					$query =	"DELETE FROM liitetiedostot 
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
					
					$kuvaselite = "Tuotekuva";
					
					if ($toiminto == 'TH') {
						$kuvaselite .= " pieni";
					}
					elseif ($toiminto == 'TK') {
						$kuvaselite .= " normaali";
					}
					
					$query = "	INSERT INTO liitetiedostot SET
								yhtio    			= '$kukarow[yhtio]',
								liitos   			= '$taulu',
								liitostunnus 		= '$apurow[tunnus]',
								data     			= '$data',
								selite   			= '$kuvaselite',
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
					
					echo "Lis‰ttiin $toiminto $kuva tuotteelle $apurow[tuoteno]<br>";
					
					//echo "$query<br>";
				}
				system("rm -f \"$file\"");
			}
			else {
				echo "Tuotetta ei lˆytynyt kuvalle $kuva !!!<br>";
				
			}
		
			
		}

		//echo "$polku || $file ... $taulu | $toiminto @ $kuva<br><br><br>";
	
	}
}

if ($tee == 'DUMPPAA' and $mitkadumpataan != '') {
	
	if (!is_writable($dirri."/".$mitkadumpataan)) {
		die("Kuvapankkiin/$mitkadumpataan ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida jatkaa!<br>");
	}
	
	$query =	"SELECT *
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
			die("$kokonimi ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida jatkaa!<br>");
		}
		
		$kokonimi .= "/".$row["filename"];
		
		if (!file_exists($kokonimi)) {
			$handle = fopen("$kokonimi", "x");

			if ($handle === FALSE) {
				echo "Tiedoston $row[filename] luonti ep‰onnistui!<br>";
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
		echo "Poistettiin $dellattuja kuvaa j‰rjestelm‰st‰<br>";
	}
	
}


$files = listdir($dirri);

$lukuthumbit = 0;
$lukunormit = 0;
$lukutconvertit = 0;

foreach ($files as $file) {
	$polku = substr($file,$alkupituus);

	list($taulu, $toiminto, $kuva) = split("/", $polku, 3);
	
	if ($toiminto == 'thumb' and $kuva != '') {
		$lukuthumbit ++;
	}
	if ($toiminto == 'normaali' and $kuva != '') {
		$lukunormit ++;
	}
	if ($toiminto == 'kasittele' and $kuva != '') {
		$lukutconvertit ++;
	}
	
}

// k‰yttˆliittym‰
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
echo "<tr><td>K‰sittele/Tuo K‰sitelt‰v‰t: </td><td> ($lukutconvertit) <input type='checkbox' name='convertit' value='1' $chekkis3></td></tr>";
echo "<td class='back' colspan='2'><br><input type='submit' value='".t("Tuo")."'></td>";
echo "</table>";
echo "</form>";

echo "<br><br><br>";


if ($lukuthumbit+$lukunormit == 0) {
	echo "<table><form name='dumppi' method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='DUMPPAA'>";
	echo "<tr><th colspan='2'>Vie kuvat takaisin kuvapankkiin</th></tr>";
	echo "<tr><td>Valitse kuvalaji: </td>
		<td><select name = 'mitkadumpataan'>
		<option value=''>".t("Valitse")."</option>
		<option value='tuote'>".t("Tuotekuvat")."</option>
		</select></td></tr>";
	echo "<tr><td>Poistetaanko j‰rjestelm‰st‰: </td><td><input type='checkbox' name='dumppaajapoista' value='1'></td></tr>";
	echo "<td class='back' colspan='2'><br><input type='submit' value='".t("Vie")."'></td>";
	echo "</table>";
	echo "</form>";
}

require ("inc/footer.inc");




?>