<?php
	require "../inc/parametrit.inc";

	echo "<font class='head'>".t("Tulosta luettelotarrat")."</font><hr>";
	
	if ($tee == "TULOSTA") {
		$tulostimet[0] = 'Tarrat';

		if (count($komento) == 0) {
			require("../inc/valitse_tulostin.inc");
		}

		//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$filenimi = "/tmp/CRM-Osoitetarrat-".md5(uniqid(mt_rand(), true)).".txt";
		$fh = fopen($filenimi, "w+");

		$sisalto1 = array();
		$sisalto2 = array();
		$sisalto3 = array();
		$sisalto4 = array();
		$sisalto5 = array();
		$sisalto6 = array();
		$sisalto7 = array();
		$sisalto8 = array();

		$query = "	SELECT nimi, osoite, postino, postitp, maa, GROUP_CONCAT(DISTINCT luettelo ORDER BY luettelo SEPARATOR ' ') luettelo
					FROM luettelo_tilaukset
					WHERE yhtio = '$kukarow[yhtio]' and tunnus in ($tuoteno)
					GROUP BY nimi, trim(replace(osoite,' ','')), postino, postitp, maa
					ORDER BY nimi, osoite, postino, postitp, maa";
		$res = mysql_query($query) or pupe_error($query);
		
		$rivi   = 0;
		$sarake = 0;
		
		while ($row = mysql_fetch_array($res)) {
			
			if ($sarake==0) {
				$sisalto1[$rivi][$sarake] = sprintf ('%-24.24s'," "); 
				$sisalto2[$rivi][$sarake] = sprintf ('%-24.24s',$row["nimi"]);
				$sisalto3[$rivi][$sarake] = sprintf ('%-24.24s',$row["osoite"]);
				$sisalto4[$rivi][$sarake] = sprintf ('%-24.24s',$row["postino"]." ".$row["postitp"]);
				$sisalto5[$rivi][$sarake] = sprintf ('%-24.24s',$row["maa"]);
				$sisalto6[$rivi][$sarake] = sprintf ('%-24.24s'," ");
				$sisalto7[$rivi][$sarake] = sprintf ('%-24.24s',trim($row["luettelo"]));
				$sisalto8[$rivi][$sarake] = sprintf ('%-24.24s'," ");
			}			
			if ($sarake==1) {
				$sisalto1[$rivi][$sarake] = sprintf ('%-24.24s'," "); 
				$sisalto2[$rivi][$sarake] = sprintf ('%-24.24s'," ".$row["nimi"]);
				$sisalto3[$rivi][$sarake] = sprintf ('%-24.24s'," ".$row["osoite"]);
				$sisalto4[$rivi][$sarake] = sprintf ('%-24.24s'," ".$row["postino"]." ".$row["postitp"]);
				$sisalto5[$rivi][$sarake] = sprintf ('%-24.24s'," ".$row["maa"]);
				$sisalto6[$rivi][$sarake] = sprintf ('%-24.24s'," ");
				$sisalto7[$rivi][$sarake] = sprintf ('%-24.24s'," ".trim($row["luettelo"]));
				$sisalto8[$rivi][$sarake] = sprintf ('%-24.24s'," ");
			}
			if ($sarake==2) {
				$sisalto1[$rivi][$sarake] = sprintf ('%-24.24s',"   "); 
				$sisalto2[$rivi][$sarake] = sprintf ('%-24.24s',"   ".$row["nimi"]);
				$sisalto3[$rivi][$sarake] = sprintf ('%-24.24s',"   ".$row["osoite"]);
				$sisalto4[$rivi][$sarake] = sprintf ('%-24.24s',"   ".$row["postino"]." ".$row["postitp"]);
				$sisalto5[$rivi][$sarake] = sprintf ('%-24.24s',"   ".$row["maa"]);
				$sisalto6[$rivi][$sarake] = sprintf ('%-24.24s',"   ");
				$sisalto7[$rivi][$sarake] = sprintf ('%-24.24s',"   ".trim($row["luettelo"]));
				$sisalto8[$rivi][$sarake] = sprintf ('%-24.24s'," ");
			}											
			
			$sarake++;
			
			if ($sarake==3) {
				$rivi++;
				$sarake=0;
			}
		
		}	
			
		
		for($i=0; $i<count($sisalto1); $i++) {
					
			fputs($fh, $sisalto1[$i][0].$sisalto1[$i][1].$sisalto1[$i][2]."\n");
			fputs($fh, $sisalto2[$i][0].$sisalto2[$i][1].$sisalto2[$i][2]."\n");
			fputs($fh, $sisalto3[$i][0].$sisalto3[$i][1].$sisalto3[$i][2]."\n");
			fputs($fh, $sisalto4[$i][0].$sisalto4[$i][1].$sisalto4[$i][2]."\n");
			fputs($fh, $sisalto5[$i][0].$sisalto5[$i][1].$sisalto5[$i][2]."\n");
			fputs($fh, $sisalto6[$i][0].$sisalto6[$i][1].$sisalto6[$i][2]."\n");
			fputs($fh, $sisalto7[$i][0].$sisalto7[$i][1].$sisalto7[$i][2]."\n");
			fputs($fh, $sisalto8[$i][0].$sisalto8[$i][1].$sisalto8[$i][2]."\n");
		
		}
		
		fclose($fh);

		$line = exec("a2ps -o ".$filenimi.".ps --no-header -R --lines-per-page=64 --columns=1 --medium=a4 --margin=0 --borders=0 $filenimi");
		
		$line = exec("$komento[Tarrat] $filenimi.ps");

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $filenimi");
		system("rm -f ".$filenimi.".ps");
		
		$query = "	UPDATE luettelo_tilaukset
					SET tulostettu='T', tulostettuaika=now()
					WHERE yhtio = '$kukarow[yhtio]' and tunnus in ($tuoteno)";
		$res = mysql_query($query) or pupe_error($query);

		echo "<br>".t("Tarrat tulostuu")."!<br><br>";
		$tee='';
	}
	
	if ($tee == "POISTA") {
		$query = "	UPDATE luettelo_tilaukset
					SET tulostettu='P', tulostettuaika=now()
					WHERE yhtio = '$kukarow[yhtio]' and tunnus in ($poistettava)";
		$res = mysql_query($query) or pupe_error($query);
				
		echo "Merkint‰ poistettu!<br><br>";
		$tee = "";
	}
	
	if ($tee == "MUOKKAA") {
	
		$query = "	SELECT nimi, osoite, postino, postitp, maa, GROUP_CONCAT(DISTINCT luettelo ORDER BY luettelo SEPARATOR ' ') luettelo
					FROM luettelo_tilaukset
					WHERE yhtio = '$kukarow[yhtio]' and tunnus in ($muokattava)
					GROUP BY nimi, trim(replace(osoite,' ','')), postino, postitp, maa
					ORDER BY nimi, osoite, postino, postitp, maa";
		$res = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array ($res);

		echo "<table>";
		echo "<form method='POST' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='PAIVITA'>";
		echo "<input type='hidden' name='muokattava' value='$muokattava'>";
		
		$chk1 = "CHECKED";
		$chk2 = "CHECKED";
		$chk3 = "CHECKED";
		
		if (strpos($trow["luettelo"], "MP") === FALSE) {
				$chk1 = "";
		}
		if (strpos($trow["luettelo"], "MX") === FALSE) {
				$chk2 = "";
		}
		if (strpos($trow["luettelo"], "MK") === FALSE) {
				$chk3 = "";
		}
		
		echo "<tr><td>Katukuvasto:</td><td><input type='checkbox' name='MP' value='MP' $chk1></td></tr>";		
		echo "<tr><td>MX/Enduro/Trial:</td><td><input type='checkbox' name='MX' value='MX' $chk2></td></tr>";
		echo "<tr><td>Kelkka/M&ouml;nkk&auml;ri:</td><td><input type='checkbox' name='MK' value='MK' $chk3></td></tr>";
		echo "<tr><td>Nimi:</td><td><input type='text' name='nimi' size='20' value='$trow[nimi]'></td></tr>";
		echo "<tr><td>Osoite:</td><td><input type='text' name='osoite' size='20' value='$trow[osoite]'></td></tr>";
		echo "<tr><td>Postinumero:</td><td><input type='text' name='postino' size='20' value='$trow[postino]'></td></tr>";
		echo "<tr><td>Postitoimipaikka:</td><td><input type='text' name='postitp' size='20' value='$trow[postitp]'></td></tr>";
		echo "<tr><td>Maa:</td><td><input type='text' name='maa' size='20' value='$trow[maa]'></td></tr>";
		echo "<tr><td></td><td><br><input type='submit' value='L&auml;het&auml;'></td></tr>";
		echo "</form>";
		echo "</table>";
		
	}
	
	if ($tee == "PAIVITA") {
		$query = "	UPDATE luettelo_tilaukset
					SET nimi='$nimi',
					osoite='$osoite',
					postino='$postino',
					postitp='$postitp',
					maa='$maa',
					luettelo='$MP $MX $MK'
					WHERE yhtio = '$kukarow[yhtio]' and tunnus in ($muokattava)";       
		$res = mysql_query($query) or pupe_error($query);
        
        echo "Tiedot p‰ivitetty!<br><br>";
		$tee = "";
	}

	// Nyt selataan
	if ($tee == '') {
		
		$query = "	SELECT nimi, osoite, postino, postitp, maa, 
					GROUP_CONCAT(DISTINCT luettelo ORDER BY luettelo SEPARATOR ' ') luettelo, 
					GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnus
					FROM luettelo_tilaukset
					WHERE yhtio = '$kukarow[yhtio]' and tulostettu=''
					GROUP BY nimi, trim(replace(osoite,' ','')), postino, postitp, maa
					ORDER BY nimi, osoite, postino, postitp, maa";
		$res = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($res) > 0) {
		
			$tuoteno = "";
			
			echo "<table>";
			
			while ($trow = mysql_fetch_array ($res)) {
				
				echo "<tr>";
				
				echo "<td>$trow[luettelo]</td>";
				echo "<td>$trow[nimi]</td>";
				echo "<td>$trow[osoite]</td>";
				echo "<td>$trow[postino]</td>";
				echo "<td>$trow[postitp]</td>";
				echo "<td>$trow[maa]</td>";
				echo "<td><a href='$PHP_SELF?tee=POISTA&poistettava=$trow[tunnus]'>Poista</a></td>";
	
				echo "<td><a href='$PHP_SELF?tee=MUOKKAA&muokattava=$trow[tunnus]'>Muokkaa</a></td>";					
				echo "</tr>";
	
				$tuoteno .= $trow["tunnus"].",";
			}
			echo "</table>";
			echo "<br><br>";
	
			$tuoteno = substr($tuoteno,0,-1);
	
			echo "<table><form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='tee' value='TULOSTA'>";
			echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
			echo "<tr><td class='back'><input type='Submit' value = '".t("Tulosta")."'></td><td class='back'></td></tr></table></form>";
		}
		else {
			echo "Kaikki tarrat tulostettu!<br>";
		}
	}

	require ("../inc/footer.inc");
?>