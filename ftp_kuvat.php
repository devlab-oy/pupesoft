<?php

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
	$palautus		= "";
	$tulos_ulos_ftp	= "";

	if ($ftpkuvahost=='' or $ftpkuvauser=='' or $ftpkuvapass=='' or $ftpkuvapath=='' or $ftpmuupath == '') {
		$tulos_ulos_ftp .= "<font class='error'>".t("L‰hetykseen tarvittavia tietoja puuttuu")."! (host, user, pass, path)</font><br>";
	}
	else {
	
		//tehd‰‰n kysely t‰ss‰, ettei tule timeouttia
		$query =	"SELECT liitetiedostot.*
					FROM liitetiedostot
					JOIN tuote ON tuote.yhtio = liitetiedostot.yhtio and tuote.hinnastoon = 'W' and tuote.tunnus = liitetiedostot.liitostunnus
					WHERE liitetiedostot.yhtio = '$kyhtio' and liitetiedostot.liitos = 'tuote' and liitetiedostot.kayttotarkoitus != 'TH'
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
		
			$counter = 0;
				
			while ($row = mysql_fetch_array($result) and $counter < 10) {
			
				if ($row["liitos"] == '' or $row["kayttotarkoitus"] == '' or $row["filename"] == '') {
					continue;
				}
			
				//T‰m‰ kohta t‰ytyy muuttaa, kun tulee erikielisi‰ liitetiedostoja
				$ftpmuupathlisa = "fi/";
			
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