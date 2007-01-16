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

		//keksit‰‰n uudelle failille joku varmasti uniikki nimi:
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$filenimi = "/tmp/CRM-Osoitetarrat-".md5(uniqid(mt_rand(), true)).".txt";
		$fh = fopen($filenimi, "w+");

		$sisalto1 = "";
		$sisalto2 = "";
		$sisalto3 = "";
		$sisalto4 = "";
		$sisalto5 = "";
		$sisalto  = sprintf ('%-28.28s',"");
		$laskuri  = 0;

		if ($toimas == "") {
			$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa
								FROM asiakas
								WHERE yhtio = '$kukarow[yhtio]' and tunnus in ($otunnus)";
		}
		else {
			$query = "	SELECT toim_nimi nimi, toim_nimitark nimitark, toim_osoite osoite, toim_postino postino, toim_postitp postitp, toim_maa maa
								FROM asiakas
								WHERE yhtio = '$kukarow[yhtio]' and tunnus in ($otunnus)";
		}
		$res = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($res)) {

			$sisalto1 .= sprintf ('%-26.26s'," ".$row["nimi"]);
			$sisalto2 .= sprintf ('%-26.26s'," ".$row["nimitark"]);
			$sisalto3 .= sprintf ('%-26.26s'," ".$row["osoite"]);
			$sisalto4 .= sprintf ('%-26.26s'," ".$row["postino"]." ".$row["postitp"]);
			$sisalto5 .= sprintf ('%-26.26s'," ".$row["maa"]);

			$sisalto  = sprintf ('%-28.28s',$sisalto1);
			$sisalto .= sprintf ('%-28.28s',$sisalto2);
			$sisalto .= sprintf ('%-28.28s',$sisalto3);
			$sisalto .= sprintf ('%-28.28s',$sisalto4);
			$sisalto .= sprintf ('%-28.28s',$sisalto5);

			if ($laskuri == 7) {
				$sisalto .= sprintf ('%-28.28s',"");
				$sisalto .= sprintf ('%-28.28s',"");
				$laskuri = -1;
			}
			else {
				$sisalto .= sprintf ('%-28.28s',"");
				$sisalto .= sprintf ('%-28.28s',"");
			}

			fputs($fh, $sisalto);
			$sisalto1 = "";
			$sisalto2 = "";
			$sisalto3 = "";
			$sisalto4 = "";
			$sisalto5 = "";
			$sisalto  = sprintf ('%-28.28s',"");

			$laskuri++;
		}
		fclose($fh);

		
		$line = exec("a2ps -o ".$filenimi.".ps --no-header --columns=3 -R --medium=a4 --chars-per-line=28 --margin=0 --major=columns --borders=0 $filenimi");
		
		// itse print komento...
		if ($komento["Tarrat"] == 'email') {
			
			$line = exec("ps2pdf ".$filenimi.".ps");
			
			$liite = $filenimi.".pdf";
			$kutsu = "Tarrat";

			require("../inc/sahkoposti.inc");
		}
		else {
			$line = exec($komento["Tarrat"]." ".$filenimi.".ps");
		}
		
		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $filenimi");
		system("rm -f ".$filenimi.".ps");
		system("rm -f ".$filenimi.".pdf");

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
		else{
				$jarjestys = 'asiakas.nimi';
		}

		//haetaan omat asiakkaat
		$query = "	SELECT nimi, osoite, postino, postitp, maa, ryhma, piiri, tunnus
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]' and nimi!='' $lisa
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
		echo "<tr><th>".t("Tulosta toim_asiakkaan tiedot").":</th><th><input type='checkbox' name='toimas'></th></tr>";
		echo "<tr><td class='back'><input type='Submit' value = '".t("Tulosta")."'></td><td class='back'></td></tr></table></form>";
	}

	require ("../inc/footer.inc");
?>