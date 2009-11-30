<?php

function delete_dir_content($conn_id,$dir,$nodel = "",$nodelpict = "",$rmdir = "") {
	$poistosyy = "";
	ftp_pasv($conn_id, true);

	if (substr($dir, -1) == "/") {
		 $dir = substr($dir, 0, strlen($dir)-1);
	}
	
	echo "dir: $dir\n";
	
	$content = ftp_nlist($conn_id, $dir);
	$dir_test = ftp_rawlist($conn_id, $dir);
	
	if ($content != FALSE) {
		for($i = 0; $i < count($content); $i++) {

			if ($content[$i] == '.' or $content[$i] == '..') {
				continue;
			}

			if ($dir_test[$i][0] != "d") {

				if (strpos($content[$i],$nodelpict) === FALSE) {
					$content_dir = "$dir/$content[$i]";
					if (ftp_is_dir($conn_id, $content_dir) === FALSE) {
						if(ftp_delete($conn_id, $content_dir) === FALSE) {
							$poistosyy .= "Tiedoston poisto ep‰onnistui: ".$content[$i]."\n";
						}
					}
				}
				
			}
			else {

				$subcontentdir = "$dir/$content[$i]";
				$subcontent = ftp_nlist($conn_id, $subcontentdir);
				
				if ($content[$i] != $nodel) {
					for ($k=0; $k < count($subcontent); $k++) {

						if ($subcontent[$k] == '.' or $subcontent[$k] == '..') {
							continue;
						}

						$subdir = "$dir/$content[$i]/$subcontent[$k]";
						echo "subdir: $subdir\n";
						if (ftp_is_dir($conn_id, $subdir) === FALSE) {
							if (ftp_delete($conn_id, $subdir) === FALSE) {
								$poistosyy .= "Tiedoston poisto ep‰onnistui: ".$subcontent[$k]."\n";
							}
						}
						else {
							echo "tiedosto $subdir olikin kansio!\n";
						}
					}
					
					if ($rmdir == "") {
						$content_dir = "$dir/$content[$i]";
						if (ftp_is_dir($conn_id, $content_dir) === TRUE)  {
							if (ftp_rmdir($conn_id, $content_dir) === FALSE) {
								$poistosyy .= "Kansion poisto ep‰onnistui: ".$content[$i]."\n";
							}
						}
					}
					
				}			
			}

		}
	}
	else {
		$poistosyy .= "Tiedostoja ei poistettu\n";
	}
	
	return $poistosyy;
}

function ftp_is_dir($conn_id, $dir_x) {
	$origin = ftp_pwd($conn_id);

	if (@ftp_chdir($conn_id, $dir_x) === TRUE) {
		echo "vaihetiin kansioon $dir_x\n";
		ftp_chdir($conn_id, $origin);
		echo "vaihdettiin takaisin kansioon $origin\n";
		return true;
	} 
	else {
		return false;
	}
}

//tarvitaan yhtiˆ
if (empty($argv)) {
	require ("inc/parametrit.inc");
	$kyhtio = $kukarow['yhtio'];
}
else {
	if ($argv[1] != "") $kyhtio = trim($argv[1]);
	else die;

	require ("/var/www/html/pupesoft/inc/connect.inc");
	require ("/var/www/html/pupesoft/inc/functions.inc");	
	$tee = "aja";
}

if ($tee == "aja") {
	
	// tarvitaan $ftpkuvahost $ftpkuvauser $ftpkuvapass $ftpkuvapath $ftpmuupath
	// palautetaan $palautus ja $syy

	$dummy			= array();
	$syy			= "";
	$poistosyy		= "";
	$palautus		= "";
	$tulos_ulos_ftp	= "";

	if ($ftpkuvahost=='' or $ftpkuvauser=='' or $ftpkuvapass=='' or $ftpkuvapath=='' or $ftpmuupath == '') {
		$tulos_ulos_ftp .= "<font class='error'>".t("L‰hetykseen tarvittavia tietoja puuttuu")."! (host, user, pass, path)</font><br>";
	}
	else {
	
		//tehd‰‰n kysely t‰ss‰, ettei tule timeouttia
		$query = "	SELECT liitetiedostot.*
					FROM liitetiedostot
					JOIN tuote ON tuote.yhtio = liitetiedostot.yhtio and tuote.hinnastoon = 'W' and tuote.tunnus = liitetiedostot.liitostunnus
					WHERE liitetiedostot.yhtio = '$kyhtio' and liitetiedostot.liitos = 'tuote' and liitetiedostot.kayttotarkoitus in ('TK','MU')
					ORDER BY liitetiedostot.kayttotarkoitus ASC";
		$result = mysql_query($query) or pupe_error($query);
	
		//l‰hetet‰‰n tiedosto
		$conn_id = ftp_connect($ftpkuvahost);

		// jos connectio ok, kokeillaan loginata
		if ($conn_id) {
			$login_result = ftp_login($conn_id, $ftpkuvauser, $ftpkuvapass);
		
			if ($login_result === FALSE) {
				$syy .= "Could not login to remote host ($conn_id, $ftpkuvauser, $ftpkuvapass)\n";
			}
		}
		else {
			$syy .= "Could not connect to remote host. ($ftpkuvahost)\n";
		}

		// jos viimeinen merkki pathiss‰ ei ole kauttaviiva lis‰t‰‰n kauttaviiva...
		if (substr($ftpkuvapath, -1) != "/") {
			 $ftpkuvapath .= "/";
		}
	
		if (substr($ftpmuupath, -1) != "/") {
			 $ftpmuupath .= "/";
		}
	
		// jos login ok kokeillaan uploadata
		if ($login_result) {		

			$dirri = "/tmp";
		
			if (!is_writable($dirri)) {
				die("$kokonimi ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida jatkaa!<br>");
			}
			
			$poistosyy = delete_dir_content($conn_id,$ftpmuupath,"","","nope");
			$poistosyy .= delete_dir_content($conn_id,$ftpkuvapath,"672x","kategoria");
			
			$counter = 0;
				
			while ($row = mysql_fetch_array($result) and $counter < 10) {
			
				if ($row["liitos"] == '' or $row["kayttotarkoitus"] == '' or $row["filename"] == '') {
					continue;
				}
			
				//Laitetaan oikean maan kansioon
				if ($row["kieli"] == "" or $row["kieli"] == "fi") {
					$ftpmuupathlisa = "fi/";
				}
				else {
					$ftpmuupathlisa = $row["kieli"]."/";
				}
			
				$kokonimi = $dirri;			
			
				$kokonimi .= "/".$row["filename"];		
			
				if (!file_exists($kokonimi)) {
					$handle = fopen("$kokonimi", "x");
				
					if ($handle === FALSE) {
						$syy .= "Tiedoston $row[filename] luonti ep‰onnistui!\n";
						$counter++;					
					}
					else {
						file_put_contents($kokonimi,$row["data"]);
						fclose($handle);
					
						list($mtype, $crap) = explode("/", $row["filetype"]);
						
						if ($mtype == "image") {
							$upload = ftp_put($conn_id, $ftpkuvapath.$row["filename"], realpath($kokonimi), FTP_BINARY);
						}
						else {
							$upload = ftp_put($conn_id, $ftpmuupath.$ftpmuupathlisa.$row["filename"], realpath($kokonimi), FTP_BINARY);
						}
					
						//check upload
						if ($upload === FALSE) {
							if ($mtype == "image") {
								$syy .= "Transfer failed ($conn_id, $ftpkuvapath, ".realpath($kokonimi).")\n";
							}
							else {
								$syy .= "Transfer failed ($conn_id, $ftpmuupath, ".realpath($kokonimi).")\n";
							}
						
							$counter++;
						}
					}					
				
					//poistetaan filu
					system("rm -f '$kokonimi'");
				}				
			
			}	
	
		}
		
		if ($conn_id) {
			ftp_close($conn_id);
		}
	
		if ($poistosyy != "") {
			echo $poistosyy;
		}
		else {
			echo "Vanhat tiedostot poistettiin onnistuneesti\n";
		}
	
		if ($syy != "") {
			echo $syy;		
		}
		else {
			echo "Tiedostojen siirto onnistui\n";
		}	
	
	}
}

if ($tee == "") {
	echo "<font class='head'>".t("Tuotekuvien siirto verkkokauppaan")."</font><hr>";
	
	echo "<table><form name='uliuli' method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='aja'>";
	echo "<tr><td class='back' colspan='2'><br><input type='submit' value='".t("Siirr‰ tuotekuvat")."'></td></tr>";
	echo "</table>";
	echo "</form>";
	
}
?>
