<?php

//tarvitaan yhtiˆ
if ($argv[1] != "") $kyhtio = trim($argv[1]);
else die;


require ("/var/www/html/pupesoft/inc/connect.inc");
require ("/var/www/html/pupesoft/inc/functions.inc");


// palautetaan $syy
function tee_file($result, $dirri, $tiedostonnimi, $conn_id) {
	

	$kokonimi = $dirri."/".$tiedostonnimi;


	if (!file_exists($kokonimi)) {
		$handle = fopen("$kokonimi", "x");
		
		if ($handle === FALSE) {
			$syy .= "Tiedoston $kokonimi luonti ep‰onnistui!\n";								
		}
		else {
			
			$fields = mysql_num_fields($result);
			
			$ulos = "";
			
			
			for ($i=0; $i < $fields; $i++) { 
				$ulos .= mysql_field_name($result, $i);
				
				if ($i == $fields-1) {
					$ulos .= "\n";
				}
				else {
					$ulos .= "\t";
				}
			}
			
			while ($row = mysql_fetch_array($result)) {
				for ($i=0; $i < $fields; $i++) { 
					$ulos .= $row[$i];
					
					if ($i == $fields-1) {
						$ulos .= "\n";
					}
					else {
						$ulos .= "\t";
					}						
				}										
			}
			
							
			fputs ($handle,$ulos);
			fclose ($handle);
			
			$upload = ftp_put($conn_id, $tiedostonnimi, realpath($kokonimi), FTP_BINARY);
							
			//check upload
			if ($upload === FALSE) {
				$syy .= "Transfer failed ($conn_id, $tiedostonnimi, ".realpath($kokonimi).")\n";
			
			}
		}
					
		//poistetaan filu
		system("rm -f '$kokonimi'");
	}
	
	return $syy;
	
}	



$syy = "";

// tarvitaan $ftpkuvahost $ftpkuvauser $ftpkuvapass

if ($ftpkuvahost=='' or $ftpkuvauser=='' or $ftpkuvapass=='') {
	$tulos_ulos_ftp .= "<font class='error'>".t("L‰hetykseen tarvittavia tietoja puuttuu")."! (host, user, pass)</font><br>";
}
else {
	
		
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

		
	// jos login ok kokeillaan uploadata
	if ($login_result) {		

		$dirri = "/tmp";
				
		if (!is_writable($dirri)) {
			die("$kokonimi ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida jatkaa!<br>");
		}
	
		/*tuotetieto haku*/
		$query = "  SELECT tuote.tuoteno, tuote.try, tuote.osasto, tuote.nimitys, ta_nimitys.selite as nimitys_se, tuote.kuvaus, ta_kuvaus.selite as kuvaus_se, group_concat(liitetiedostot.filename SEPARATOR ',') as tiedostot
					FROM tuote
					LEFT JOIN liitetiedostot on tuote.yhtio = liitetiedostot.yhtio and tuote.tunnus = liitetiedostot.liitostunnus and liitetiedostot.liitos = 'tuote' and liitetiedostot.kayttotarkoitus != 'TH'
					LEFT JOIN tuotteen_avainsanat as ta_nimitys on tuote.yhtio = ta_nimitys.yhtio and tuote.tuoteno = ta_nimitys.tuoteno and ta_nimitys.laji = 'nimitys_se'
					LEFT JOIN tuotteen_avainsanat as ta_kuvaus on tuote.yhtio = ta_kuvaus.yhtio and tuote.tuoteno = ta_kuvaus.tuoteno and ta_kuvaus.laji = 'kuvaus_se'
					join avainsana as avtry on tuote.yhtio = avtry.yhtio and tuote.try = avtry.selite and avtry.laji = 'TRY' and avtry.nakyvyys != 'E'
					join avainsana as avosasto on tuote.yhtio = avosasto.yhtio and tuote.osasto = avosasto.selite and avosasto.laji = 'OSASTO' and avosasto.nakyvyys != 'E'
					WHERE tuote.yhtio = '$kyhtio'
					AND tuote.hinnastoon = 'W'
					AND tuote.status NOT IN('P','X')					
					GROUP BY tuote.tuoteno, tuote.try, tuote.osasto, tuote.nimitys, ta_nimitys.selite, tuote.kuvaus, ta_kuvaus.selite
					ORDER BY tuote.tuoteno";
		$result = mysql_query($query) or pupe_error($query);
	
		$syy .= tee_file($result, $dirri, "tuotetiedot.csv", $conn_id);
		
		/*osastot*/
		$query = "	SELECT avainsana.selite as osasto, avainsana.selitetark as nimitys, if(isnull(a.selitetark) or a.selitetark='', '', a.selitetark) as nimitys_se, avainsana.selitetark_2 as kuvaus, if(isnull(a.selitetark_2) or a.selitetark_2='', '', a.selitetark_2) as kuvaus_se
					FROM avainsana
					LEFT JOIN avainsana AS a on avainsana.yhtio = a.yhtio and avainsana.selitetark = a.selite and a.laji = 'OSASTO_SE'
					WHERE avainsana.yhtio = '$kyhtio'
					AND avainsana.laji = 'OSASTO'
					AND avainsana.nakyvyys != 'E'
					ORDER BY avainsana.jarjestys";
		$result = mysql_query($query) or pupe_error($query);

		$syy .= tee_file($result, $dirri, "osastot.csv", $conn_id);
		
		/*tuoteryhm‰t*/
		$query = "	SELECT avainsana.selite as try, avainsana.selitetark as nimitys, if(isnull(a.selitetark) or a.selitetark='', '', a.selitetark) as nimitys_se, avainsana.selitetark_2 as kuvaus, if(isnull(a.selitetark_2) or a.selitetark_2='', '', a.selitetark_2) as kuvaus_se
					FROM avainsana
					LEFT JOIN avainsana AS a on avainsana.yhtio = a.yhtio and avainsana.selitetark = a.selite and a.laji = 'TRY_SE'
					WHERE avainsana.yhtio = '$kyhtio'
					AND avainsana.laji = 'TRY'
					AND avainsana.nakyvyys != 'E'
					ORDER BY avainsana.jarjestys";
		$result = mysql_query($query) or pupe_error($query);

		$syy .= tee_file($result, $dirri, "tuoteryhmat.csv", $conn_id);		
		
	
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


?>