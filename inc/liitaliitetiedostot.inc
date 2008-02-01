<?php

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
			if($retval !== true) {
				echo $retval;
			}
			else {
				tallenna_liite("userfile", $toim, $tunnus, $kuvaotsikko, $teetyyppi, 0, $liite_jarjestys);
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
						order by tunnus";
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
