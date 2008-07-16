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


// palautetaan $syy
function tee_file($result, $dirri, $tiedostonnimi, $ftpkuvahost, $ftpkuvauser, $ftpkuvapass) {
	
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
				
				$order   = array("\r\n", "\n", "\r");
				while ($row = mysql_fetch_array($result)) {
					for ($i=0; $i < $fields; $i++) { 
						$ulos .= str_replace($order,"<br>",$row[$i]);
					
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
	}
	
	if ($conn_id) {
		ftp_close($conn_id);
	}
	
	return $syy;
	
}	



$syy = "";


if ($tee == "aja") {
	// tarvitaan $ftpkuvahost $ftpkuvauser $ftpkuvapass
	if ($ftpkuvahost=='' or $ftpkuvauser=='' or $ftpkuvapass=='') {
		$syy .= "L‰hetykseen tarvittavia tietoja puuttuu! (host, user, pass)";
	}
	else {
	
		$dirri = "/tmp";
					
		if (!is_writable($dirri)) {
			die("$kokonimi ei ole m‰‰ritelty kirjoitusoikeutta. Ei voida jatkaa!<br>");
		}

		/*tuotetieto haku*/
		$query = "  SELECT tuote.tuoteno, tuote.try, tuote.osasto, tuote.nimitys, ta_nimitys.selite as nimitys_se, tuote.kuvaus, ta_kuvaus.selite as kuvaus_se, group_concat(liitetiedostot.filename SEPARATOR ',') as tiedostot, group_concat(liitetiedostot.selite SEPARATOR ',') as selitteet
					FROM tuote
					LEFT JOIN liitetiedostot on tuote.yhtio = liitetiedostot.yhtio and tuote.tunnus = liitetiedostot.liitostunnus and liitetiedostot.liitos = 'tuote' and liitetiedostot.kayttotarkoitus != 'TH'
					LEFT JOIN tuotteen_avainsanat as ta_nimitys on tuote.yhtio = ta_nimitys.yhtio and tuote.tuoteno = ta_nimitys.tuoteno and ta_nimitys.laji = 'nimitys_se'
					LEFT JOIN tuotteen_avainsanat as ta_kuvaus on tuote.yhtio = ta_kuvaus.yhtio and tuote.tuoteno = ta_kuvaus.tuoteno and ta_kuvaus.laji = 'kuvaus_se'
					LEFT JOIN tuotteen_avainsanat as ta_lyhyt on tuote.yhtio = ta_lyhyt.yhtio and tuote.tuoteno = ta_lyhyt.tuoteno and ta_lyhyt.laji = 'lyhyt_se'
					join avainsana as avtry on tuote.yhtio = avtry.yhtio and tuote.try = avtry.selite and avtry.laji = 'TRY' and avtry.nakyvyys != 'E'
					join avainsana as avosasto on tuote.yhtio = avosasto.yhtio and tuote.osasto = avosasto.selite and avosasto.laji = 'OSASTO' and avosasto.nakyvyys != 'E'
					WHERE tuote.yhtio = '$kyhtio'
					AND tuote.hinnastoon = 'W'
					AND tuote.status NOT IN('P','X')					
					GROUP BY tuote.tuoteno, tuote.try, tuote.osasto, tuote.nimitys, ta_nimitys.selite, tuote.kuvaus, ta_kuvaus.selite
					ORDER BY tuote.tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		$syy .= tee_file($result, $dirri, "tuotetiedot.csv",  $ftpkuvahost, $ftpkuvauser, $ftpkuvapass);
	
		/*osastot*/
		$query = "	SELECT avainsana.selite as osasto, avainsana.selitetark as nimitys, if(isnull(a.selitetark) or a.selitetark='', '', a.selitetark) as nimitys_se, avainsana.selitetark_3 as kuvaus, if(isnull(a.selitetark_3) or a.selitetark_3='', '', a.selitetark_3) as kuvaus_se
					FROM avainsana
					LEFT JOIN avainsana AS a on avainsana.yhtio = a.yhtio and avainsana.selitetark = a.selite and a.laji = 'OSASTO_SE'
					WHERE avainsana.yhtio = '$kyhtio'
					AND avainsana.laji = 'OSASTO'
					AND avainsana.nakyvyys != 'E'
					ORDER BY avainsana.jarjestys";
		$result = mysql_query($query) or pupe_error($query);

		$syy .= tee_file($result, $dirri, "osastot.csv",  $ftpkuvahost, $ftpkuvauser, $ftpkuvapass);
	
		/*tuoteryhm‰t*/
		$query = "	SELECT avainsana.selite as try, avainsana.selitetark as nimitys, if(isnull(a.selitetark) or a.selitetark='', '', a.selitetark) as nimitys_se, avainsana.selitetark_3 as kuvaus, if(isnull(a.selitetark_3) or a.selitetark_3='', '', a.selitetark_3) as kuvaus_se
					FROM avainsana
					LEFT JOIN avainsana AS a on avainsana.yhtio = a.yhtio and avainsana.selitetark = a.selite and a.laji = 'TRY_SE'
					WHERE avainsana.yhtio = '$kyhtio'
					AND avainsana.laji = 'TRY'
					AND avainsana.nakyvyys != 'E'
					ORDER BY avainsana.jarjestys";
		$result = mysql_query($query) or pupe_error($query);

		$syy .= tee_file($result, $dirri, "tuoteryhmat.csv",  $ftpkuvahost, $ftpkuvauser, $ftpkuvapass);		
			
			
		if ($syy != "") {
			echo $syy;		
		}
		else {
			echo "Tiedostojen siirto onnistui\n";
		}	
	
	}
}

if ($tee == "") {
	echo "<font class='head'>".t("Tuotetietojen siirto verkkokauppaan")."</font><hr>";
	
	echo "<table><form name='uliuli' method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='aja'>";
	echo "<tr><td class='back' colspan='2'><br><input type='submit' value='".t("Siirr‰ tiedot")."'></td></tr>";
	echo "</table>";
	echo "</form>";
	
}

?>