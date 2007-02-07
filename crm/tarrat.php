<?php
	require "../inc/parametrit.inc";

	echo "<font class='head'>".t("Tulosta osoitetarrat")."</font><hr>";
	if ($oikeurow[2] != '1') { // Saako p‰ivitt‰‰
		if ($uusi == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta lis‰t‰ t‰t‰ tietoa")."</b><br>";
			$uusi = '';
		}
		if ($del == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta poistaa t‰t‰ tietoa")."</b><br>";
			$del = '';
			$tunnus = 0;
		}
		if ($upd == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta muuttaa t‰t‰ tietoa")."</b><br>";
			$upd = '';
			$uusi = 0;
			$tunnus = 0;
		}
	}

	if ($tee == "TULOSTA") {
		$tulostimet[0] = 'Tarrat';

		if (count($komento) == 0) {
			require("../inc/valitse_tulostin.inc");
		}
		
		if ($toimas == "") {
			$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa
						FROM asiakas
						WHERE yhtio = '$kukarow[yhtio]' and tunnus in ($otunnus)";
		}
		else {
			$query = "	SELECT 
						if(toim_nimi!='', toim_nimi, nimi) nimi, 
						if(toim_nimitark!='', toim_nimitark, nimitark) nimitark, 
						if(toim_osoite!='', toim_osoite, osoite) osoite, 
						if(toim_postino!='', toim_postino, postino) postino, 
						if(toim_postitp!='', toim_postitp, postitp) postitp, 
						if(toim_maa!='', toim_maa, maa) maa
						FROM asiakas
						WHERE yhtio = '$kukarow[yhtio]' and tunnus in ($otunnus)";
		}
		$res = mysql_query($query) or pupe_error($query);

		$laskuri = 1;
		$sarake  = 1;	
		$sisalto = "";
				
		if ($raportti == 33) {
			$rivinpituus_ps	= 28;
			$rivinpituus	= 27;
			$sarakkeet 		= 3;
			$rivit 			= 11;
			$sisalto .= "\n";
			$sisalto .= "\n";
			$sisalto .= "\n";
		}
		elseif ($raportti == 24) {
			$rivinpituus_ps	= 28;
			$rivinpituus	= 27;
			$sarakkeet 		= 3;
			$rivit 			= 8;
		}
		
		
		while ($row = mysql_fetch_array($res)) {
			
			if ($sarake == 3) {
				$lisa = " ";
			}
			else {
				$lisa = "";
			}
			
			$sisalto .= sprintf ('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["nimi"]))."\n";
			$sisalto .= sprintf ('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["nimitark"]))."\n";
			$sisalto .= sprintf ('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["osoite"]))."\n";
			$sisalto .= sprintf ('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["postino"]." ".$row["postitp"]))."\n";
			$sisalto .= sprintf ('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["maa"]))."\n";
			
			$sisalto .= "\n\n";
			
			if ($raportti == 24 and $laskuri != $rivit) {
				$sisalto .= "\n\n\n";
			}
			
			if ($raportti == 33 and $laskuri == ($rivit-1)) {
				$sisalto .= "\n";
				$sisalto .= "\n";
				$sisalto .= "\n";
				$sisalto .= "\n";
				$laskuri++;	
			}
			
			
			if ($laskuri == $rivit) {
				if ($raportti == 33) {
					$sisalto .= "\n";
					$sisalto .= "\n";
					$sisalto .= "\n";
				}
				
				$laskuri = 0;
				$sarake++;
			}
			$laskuri++;
		}
				
		//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$filenimi = "/tmp/CRM-Osoitetarrat-".md5(uniqid(mt_rand(), true)).".txt";
		$fh = fopen($filenimi, "w+");
		fputs($fh, $sisalto);
		fclose($fh);

		$line = exec("a2ps -o ".$filenimi.".ps --no-header --columns=$sarakkeet -R --medium=a4 --chars-per-line=$rivinpituus_ps --margin=0 --major=columns --borders=0 $filenimi");
		
		// itse print komento...
		if ($komento["Tarrat"] == 'email') {
						
			$liite = $filenimi.".ps";
			$kutsu = "Tarrat.ps";
			$ctype = "ps";

			require("../inc/sahkoposti.inc");
		}
		else {
			$line = exec($komento["Tarrat"]." ".$filenimi.".ps");
		}
		
		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $filenimi");
		system("rm -f ".$filenimi.".ps");

		echo "<br>".t("Tarrat tulostuu")."!<br><br>";
		$tee='';
	}

	// Nyt selataan
	if ($tee == '') {
		$kentat = "nimi, osoite, postino, postitp, maa, ryhma, piiri";

		$array = split(",", $kentat);
        $count = count($array);
        for ($i=0; $i<=$count; $i++) {
			if (strlen($haku[$i]) > 0) {
				$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
				$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
			}
        }
        if (strlen($ojarj) > 0) {
        	$jarjestys = $ojarj;
        }
		else {
			$jarjestys = 'asiakas.nimi';
		}

		if ($tarra_aineisto != "") {
			$lisa = " and tunnus in ($tarra_aineisto) ";
		}
		
		
		//haetaan omat asiakkaat
		$query = "	SELECT nimi, osoite, postino, postitp, maa, ryhma, piiri, tunnus
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]' 
					and nimi != '' 
					$lisa
					ORDER BY $jarjestys
					LIMIT 1000";

		$result = mysql_query($query) or pupe_error($query);
		echo "<table><tr><form action = '$PHP_SELF' method = 'post'>";

		for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
			echo "<th><a href='$PHP_SELF?ojarj=".mysql_field_table($result,$i).".".mysql_field_name($result,$i).$ulisa."'>".t(mysql_field_name($result,$i))."</a>";

			echo "<br><input type='text' size='10' name = 'haku[$i]' value = '$haku[$i]'></th>";
		}

		echo "<th valign='bottom'><input type='Submit' value = '".t("Etsi")."'></th></form></tr>";

		$otunnus = '';

		while ($trow = mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
				if ($i == 1) {
					if(strlen($trow[$i]) <= 25){
						echo "<td nowrap>$trow[$i]</td>";
					}
					else {
						echo "<td nowrap>".substr($trow[$i],0,24)."...</td>";
					}
				}
				else {
					if(strlen($trow[$i]) <= 15){
						echo "<td nowrap>$trow[$i]</td>";
					}
					else {
						echo "<td nowrap>".substr($trow[$i],0,14)."...</td>";
					}
				}
			}
			echo "</tr>";

			$otunnus .= $trow["tunnus"].",";
		}
		echo "</table>";
		echo "<br><br>";

		$otunnus = substr($otunnus,0,-1);

		echo "<table><form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tee' value='TULOSTA'>";
		echo "<input type='hidden' name='otunnus' value='$otunnus'>";
		echo "<tr><th>".t("Tulosta toimitusosoitteen tiedot").":</th><td><input type='checkbox' name='toimas'></td></tr>";
		echo "<tr><th>".t("Valitse tarra-arkin tyyppi").":</th>
				<td><select name='raportti'>
				<option value='33'>33 ".t("Tarraa")."</option>
				<option value='24'>24 ".t("Tarraa")."</option>
				</select></td></tr>";
		
		echo "<tr><td class='back'><input type='Submit' value = '".t("Tulosta")."'></td><td class='back'></td></tr></table></form>";
	}

	require ("../inc/footer.inc");
?>