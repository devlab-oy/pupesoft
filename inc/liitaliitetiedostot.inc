<?php


function konvertoi ($ykoko,$xkoko,$type,$taulu,$upfile1) {
	global $kukarow, $yhtiorow,$_FILES;
	
	// testausta varten staattinen
	//$dirri = "kuvapankki";
	$dirri = $yhtiorow['kuvapankki_polku'];
	$dirri .= "/".$kukarow['yhtio'];
	
	
	list($usec, $sec) = explode(" ",microtime());

	$nimi      = $usec+$sec; // uniikki nimi
	$resize    = ""; // resize komento
	$crop      = ""; // crop komento
	$upfileall = ""; // palautus
	
	$filetsu = $_FILES[$upfile1];
	
	$path_parts = pathinfo($filetsu["name"]);
	$ext = strtolower($path_parts['extension']);	
		
	$image = getimagesize($filetsu["tmp_name"],$imageinfo);	// lähetetty kuva
		
	$leve = $image[0];       

	$kork = $image[1];
		
	if ($kork > $ykoko) {
		// tehään pienentämällä
		$upfilesgh = strtolower("/tmp/$nimi"."1.".$ext);
	
    	// skaalataan kuva oikenakokoiseksi
	    exec("nice -n 20 convert -resize x$ykoko -quality 80 $filetsu[tmp_name] $upfilesgh");
		$uusnimi = $dirri."/".$taulu."/".$type."/".$filetsu["name"];
		
		if (!copy($upfilesgh,$uusnimi)) {
	    	echo "Kopiointi epäonnistui $upfile1<br>";
			$upfileall = "";
		}
		else {
			$upfileall .= "$uusnimi";
		}
		
		$a = getimagesize($uusnimi);

		$file["name"] 		= basename($uusnimi);
		$file["type"] 		= $a["mime"];
		$file["tmp_name"] 	= $uusnimi;
		$file["error"] 		= 0;
		$file["size"] 		= filesize($uusnimi);

		$_FILES[$upfile1] = $file;
	
		// poistetaan file
		system("rm -f $upfilesgh");
	
		
	}
	else {
		$upfileall = "";
	}
	
	return $upfileall;
}


	echo "<font class='head'>".t("Liitetiedostot")."</font><hr>";

	if ($oikeurow['paivitys'] != '1') { // Saako päivittää
		echo "<br><font class='error'>".t("Sun natsat ei riitä tähä hommaan poika")."!</font><br>";
		exit;
	}

	if ($tunnus == "" and $uusi == 1) {
		echo "	<form action='#' method='POST'>
				<input type='button' onclick='javascript:document.mainform.submit();' name='kikkeli' value='".t("Lisää uusi tiedosto")."'>
				</form>";
	}
	elseif ($tunnus == "") {
		echo "<font class='error'>".t("Sarjanumeron lisätiedot katosi")."!</font>";
	}
	else {

		// poistetaan kuva
		if ($teekuva == "poista") {
			$query = "	DELETE from liitetiedostot 
						where yhtio			= '$kukarow[yhtio]' 
						and liitos 			= '$toim' 
						and liitostunnus	= '$tunnus' 
						and tunnus			= '$kuvatunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$teekuva = "";
		}
		
		if ($teekuva == "paivita") {
			$query = "	UPDATE liitetiedostot SET
						selite   		= '$kuvaotsikko',
						kayttotarkoitus	= '$teetyyppi',
						jarjestys		= '$liite_jarjestys',
						muuttaja		= '$kukarow[kuka]',
						muutospvm		= now()
						where yhtio			= '$kukarow[yhtio]' 
						and liitos 			= '$toim' 
						and liitostunnus	= '$tunnus' 
						and tunnus			= '$kuvatunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$teekuva = "";
		}

		// lisätään kuva
		if ($teekuva == "insert") {
						
			$retval = tarkasta_liite("userfile");
			
			//	Onko meillä jokin kuvatyyppi?
			list($type, $crap) = explode("/", $_FILES["userfile"]["type"]);
			
			//tarkastetaan löytyykö kuva jo kannasta
			if($teetyyppi == "TK" and $type == "image" and strtoupper($toim) == 'TUOTE') {
				$kayttotarkoitus_lisa = " and kayttotarkoitus in('TK','TH') ";
			}
			elseif ($teetyyppi == "TH" and $type == "image" and strtoupper($toim) == 'TUOTE') {
				$kayttotarkoitus_lisa = " and kayttotarkoitus in('TH') ";
			}
			else {
				$kayttotarkoitus_lisa = " and kayttotarkoitus in('MU') ";
			}
			
			$file_name = $_FILES['userfile']['name'];
			
			$query = "  SELECT filename, kayttotarkoitus
						FROM liitetiedostot
						where yhtio = '$kukarow[yhtio]'
						and liitos = '$toim'
						and liitostunnus	= '$tunnus'
						$kayttotarkoitus_lisa
						and filename = '$file_name'";
			$result = mysql_query($query) or pupe_error($query);
			
			
			if($retval !== true) {
				echo $retval;
				
			}
			elseif (mysql_num_rows($result) > 0) {				
				$checkrow=mysql_fetch_array($result);
				echo "<font class='error'>".t("VIRHE! Tiedosto %s %s löytyy jo tuotteelle", $kieli, $file_name, $checkrow['kayttotarkoitus']).".</font><br><br>";						
			}
			else {
			
							
				$uusi_filu = "";
				
				//konvertoidaan kuva
				if ($teetyyppi == "TK" and $type == "image" and strtoupper($toim) == 'TUOTE') {	
					$temp_file = $_FILES["userfile"];
					
					$uusi_filu = konvertoi(480,640,"normaali",$toim,"userfile");
					tallenna_liite("userfile", $toim, $tunnus, $kuvaotsikko, $teetyyppi, 0, $liite_jarjestys);
					if ($uusi_filu != "") {
						system("rm -f \"$uusi_filu\"");
					}
					
					$_FILES["userfile"] = $temp_file;
					$uusi_filu = konvertoi(65,96,"thumb",$toim,"userfile");
					
					$teetyyppi = "TH";
					tallenna_liite("userfile", $toim, $tunnus, $kuvaotsikko, $teetyyppi, 0, $liite_jarjestys);
					if ($uusi_filu != "") {
						system("rm -f \"$uusi_filu\"");
					}
				}
				elseif ($teetyyppi == "TH" and $type == "image" and strtoupper($toim) == 'TUOTE') {
					$uusi_filu = konvertoi(65,96,"thumb",$toim,"userfile");
					tallenna_liite("userfile", $toim, $tunnus, $kuvaotsikko, $teetyyppi, 0, $liite_jarjestys);
					if ($uusi_filu != "") {
						system("rm -f \"$uusi_filu\"");
					}
				}
				else {
					$teetyyppi = "MU";
					tallenna_liite("userfile", $toim, $tunnus, $kuvaotsikko, $teetyyppi, 0, $liite_jarjestys);
				}				
								
			}
			
			$teekuva = "";
		}

		// uuden syöttö
		if ($teekuva == "uusikuva") {

			echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post' enctype='multipart/form-data'>";

			echo "<table>";
			
			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'LITETY'
						ORDER BY jarjestys, selite";
			$kires = mysql_query($query) or pupe_error($query);

			echo "<tr><th>".t("Tyyppi")."</th><td><select name='teetyyppi'>";

			while ($kirow=mysql_fetch_array($kires)) {
				if ($kirow["selite"] == $teetyyppi) $select='SELECTED';
				else $select = '';

				echo "<option value='$kirow[selite]' $select>$kirow[selitetark]</option>";
			}

			echo "</select></td></tr>";
			
			echo "<tr><th>Liitä tiedosto</th><td><input name='userfile' type='file'></td></tr>";
			echo "<tr><th>".t("Selite")."</th><td><input type='text' name='kuvaotsikko' value='$kuvaotsikko'></tr>";
			echo "<tr><th>".t("Järjestys")."</th><td><input type='text' name='liite_jarjestys' value='$liite_jarjestys'></tr>";
			echo "</table><hr>";
			
			echo "<input type = 'hidden' name = 'teekuva' value='insert'>";
			echo "<input type = 'hidden' name = 'toim' value='$aputoim'>";
			echo "<input type = 'hidden' name = 'tunnus' value='$tunnus'>";
			echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
			echo "<input type='submit' class='btn' value='".t("Lisää tiedosto")."'></form>";
		}

		// selataan vanhoja
		if ($teekuva == "") {
			
			$query  = "	SELECT * 
						from liitetiedostot 
						where yhtio = '$kukarow[yhtio]' 
						and liitos = '$toim' 
						and liitostunnus = '$tunnus'
						order by jarjestys, tunnus";
			$result = mysql_query($query) or pupe_error($query);

			echo "<table>";
			
			while ($trow = mysql_fetch_array($result)) {
				echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";
				echo "<td class='back' valign='bottom'>";
				echo "<input type='hidden' name='teekuva' value='paivita'>";
				echo "<input type='hidden' name='toim' value='$aputoim'>";
				echo "<input type='hidden' name='tunnus' value='$tunnus'>";
				echo "<input type='hidden' name='kuvatunnus' value='$trow[tunnus]'>";
				echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
				
				$query = "	SELECT selite, selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]' and laji = 'LITETY'
							ORDER BY jarjestys, selite";
				$kires = mysql_query($query) or pupe_error($query);

				echo "<tr><th>".t("Tyyppi")."</th><td colspan='2'><select name='teetyyppi'>";

				while ($kirow=mysql_fetch_array($kires)) {
					if ($kirow["selite"] == $trow["kayttotarkoitus"]) $select='SELECTED';
					else $select = '';

					echo "<option value='$kirow[selite]' $select>$kirow[selitetark]</option>";
				}

				echo "</select></td></tr>";
				
				
				echo "<tr><th>".t("Selite")."</th><td colspan='2'><input type='text' name='kuvaotsikko' value='$trow[selite]'></td></tr>";
				echo "<tr><th>".t("Järjestys")."</th><td colspan='2'><input type='text' name='liite_jarjestys' value='$trow[jarjestys]'></td>";
				echo "<td class='back'><input type='submit' class='btn' value='".t("Päivitä tiedot")."'></td></tr>";
				echo "</form>";
				
				echo "<tr><th>".t("Liite")."</th>";
				
				$path_parts = pathinfo($trow['filename']);
				$ext = $path_parts['extension'];
				 
				if (strtoupper($ext) == "JPEG" or strtoupper($ext) == "JPG" or strtoupper($ext) == "GIF" or strtoupper($ext) == "PNG") {
					$im = imagecreatefromstring($trow["data"]);

					$width = imagesx($im);
					$height = imagesy($im);
					
					$leveysimg = "150";
					
					if ($width < $leveysimg) {
						$leveysimg = $width;
					}
					
					echo "<td><img width='".$leveysimg."px' src='view.php?id=$trow[tunnus]'></td><td valign='bottom'>$width x $height px. ".size_readable($trow["filesize"])."</td>";
				}
				else {
					echo "<td><a href='view.php?id=$trow[tunnus]'>$trow[filename]</a></td><td valign='bottom'>".size_readable($trow["filesize"])."</td>";
				}

				echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";
				echo "<td class='back' valign='bottom'>";
				echo "<input type='hidden' name='teekuva' value='poista'>";
				echo "<input type='hidden' name='toim' value='$aputoim'>";
				echo "<input type='hidden' name='tunnus' value='$tunnus'>";
				echo "<input type='hidden' name='kuvatunnus' value='$trow[tunnus]'>";
				echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
				echo "<input type='submit' class='btn' value='".t("Poista tiedosto")."'></td></form>";
				echo "</tr>";
				
			}

			echo "</table>";

			echo "<br><form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>
					<input type='hidden' name='teekuva' value='uusikuva'>
					<input type='hidden' name='toim' value='$aputoim'>
					<input type='hidden' name='tunnus' value='$tunnus'>";
			echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
			echo "<input type='submit' class='btn' value='".t("Lisää tiedosto")."'></form>";
		}
	}

?>
