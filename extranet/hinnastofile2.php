<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	
	if ($filenimi != '') {
		header("Content-type: application/force-download");
		header("Content-Disposition: attachment; filename=hindisk.zip");
		header("Content-Description: File Transfer");

		$toot = fopen($filenimi,"r");

		while (!feof ($toot)) {
			echo fgets($toot);
		}
		fclose ($toot);

		system("rm -f ".$filenimi);
		exit;
	}

	require('parametrit.inc');
	
	echo "<font class='head'>".t("Hinnastoajo").":</font><hr>";

	if ($tee != '') {
		$where1 = '';
		$where2 = '';

		if ($osasto != '') {
			$where1 = " osasto = '$osasto' ";
		}
		elseif ($osasto2 != '') {
			$osastot = split(" ",$osasto2);

			for($i = 0; $i < sizeof($osastot); $i++) {
				$osastot[$i] = trim($osastot[$i]);

				if ($osastot[$i] != '') {
					if (strpos($osastot[$i],"-")) {

						$osastot2 = split("-",$osastot[$i]);

						for($ia = $osastot2[0]; $ia<= $osastot2[1]; $ia++) {
							$where1 .= "'".$ia."',";
						}
					}
					else {
						$where1 .= "'".$osastot[$i]."',";
					}
				}
			}
			$where1 = substr($where1,0,-1);
			$where1 = " osasto in (".$where1.") ";
	    }


		if ($try != '') {
			$where2 = " try ='$try' ";
		}		
		elseif ($try2 != '') {
			$tryt = split(" ",$try2);

			for($i = 0; $i < sizeof($tryt); $i++) {
				$tryt[$i] = trim($tryt[$i]);

				if ($tryt[$i] != '') {
					if (strpos($tryt[$i],"-")) {
						$tryt2 = split("-",$tryt[$i]);
						for($ia = $tryt2[0]; $ia<= $tryt2[1]; $ia++) {
							$where2 .= "'".$ia."',";
						}
					}
					else {
						$where2 .= "'".$tryt[$i]."',";
					}
				}
			}
			$where2 = substr($where2,0,-1);
			$where2 = " try in (".$where2.") ";
		}

		if (strlen($where1) > 0) {
			$where = $where1." and ";
		}
		if (strlen($where2) > 0) {
			$where = $where2." and ";
		}
		if (strlen($where2) > 0 && strlen($where1) > 0) {
			$where = "(". $where1." or ".$where2.")  and ";
		}

		$query = "	SELECT tuote.tuoteno
					FROM tuote
					WHERE $where tuote.yhtio='$kukarow[yhtio]' and tuote.status in ('','a') and hinnastoon != 'E'
					ORDER BY tuote.osasto+0, tuote.try+0";
		$result = mysql_query($query) or pupe_error($query);
		
		flush();

		//kirjoitetaan pdf faili levylle..

		$filenimi = "$kukarow[yhtio]-".t("hindisk")."-".md5(uniqid(rand(),true)).".txt";

		if (!$fh = fopen("/tmp/".$filenimi, "w+"))
				die("filen luonti epäonnistui!");

		while ($tuoterow = mysql_fetch_array($result)) {
			
			$query = "	SELECT tuote.tuoteno, tuote.nimitys, tuote.myyntihinta, tuote.yksikko, tuote.aleryhma, korvaavat.id
						FROM tuote
						LEFT JOIN korvaavat use index (yhtio_tuoteno) ON tuote.tuoteno=korvaavat.tuoteno and tuote.yhtio=korvaavat.yhtio
						WHERE tuote.yhtio='$kukarow[yhtio]' and tuote.tuoteno='$tuoterow[tuoteno]'";

			$trresult = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($trresult);
			
			
			//haetaan aleryhmän tunnus
			$query = "	SELECT b.tunnus, b.ryhma
						FROM tuote a
						LEFT JOIN perusalennus b use index (yhtio_ryhma) ON a.yhtio = b.yhtio and a.aleryhma = b.ryhma
						WHERE a.tuoteno = '$row[tuoteno]' and a.yhtio = '$kukarow[yhtio]'";
			$aleresult = mysql_query($query) or pupe_error($query);
			$alerow = mysql_fetch_array($aleresult);
			//echo "$query <br>";

			$aletun = $alerow['tunnus'];
			//echo "$aletun <br>";
			$tunpit = strlen($aletun);

			if ($aletun == '') {
				$aletun = '000';
			}
			else if ($tunpit == '2'){
				$aletun = '0'.$aletun;
			}
			else if ($tunpit == '1'){
				$aletun = '00'.$aletun;
			}
			//echo "$aletun <br>";

			//korvaavat tuotteet
			$koti 		= '';
			$edellinen 	= '';
			$seuraava 	= '';
			$korvaavat	= '';

			if ($row["id"] > 0) {
				$query = "	select * 
							from korvaavat use index (yhtio_id) 
							where id='$row[id]' 
							and yhtio='$kukarow[yhtio]' 
							order by jarjestys, tuoteno";
				$korvaresult = mysql_query($query) or pupe_error($query);

				$lask = 0;
				while ($korvarow = mysql_fetch_array($korvaresult)){
					$korvaavat[$lask] = $korvarow["tuoteno"];
					if ($korvarow["tuoteno"] == $row["tuoteno"]) {
						$koti = $lask;
					}
					$lask++;
				}
				//tässä listan viimeinen indeksi
				$lask--;

				//edellinen ja seuraava korvaava
				if ($koti == 0) {
					$edellinen	= '';
					$seuraava	= $korvaavat[$koti+1];
				}
				elseif($koti == $lask) {
					$edellinen	= $korvaavat[$koti-1];
					$seuraava	= '';
				}
				else{
					$edellinen	= $korvaavat[$koti-1];
					$seuraava	= $korvaavat[$koti+1];
				}
			}

			$rivi  = sprintf('%-20.20s'	,$row["tuoteno"]);
			$rivi .= sprintf('%-60.60s'	,$row["nimitys"]);
			$rivi .= sprintf('%-10.10s'	,$row["yksikko"]);
			$rivi .= sprintf('%03d'		,$aletun);
			$rivi .= sprintf('%08d'		,str_replace('.','',$row["myyntihinta"]));
			$rivi .= sprintf('%08d'		,str_replace('.','',$row["myyntihinta"]));
			$rivi .= sprintf('%-20.20s'	,$seuraava);
			$rivi .= sprintf('%-20.20s'	,$edellinen);
			$rivi .= sprintf('%-3.3s'	,'');
			$rivi .= "\n";

			fwrite($fh, $rivi);
		}
		fclose($fh);//pakataan faili
		$cmd = "cd /tmp/;/usr/bin/zip $kukarow[yhtio].$kukarow[kuka].zip $filenimi";
		$palautus = exec($cmd);

		system("rm -f ".$filenimi);

		$filenimi = "/tmp/$kukarow[yhtio].$kukarow[kuka].zip";
		echo "<br><table><tr><th>".t("Tallenna hinnasto tiedostoon")."</th>";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='filenimi' value='$filenimi'>";
		echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr>";
		echo "</form></table>";

		//lopetetaan tähän
		require ("footer.inc");
		exit;
	}

	//Käyttöliittymä
	echo "<br>".t("Voit valita osaston ja tuoteryhmän joko alasvetovalikosta tai syöttämällä osaston- ja tuoteryhmien numerot käsin").".<br> ".t("Käsin voit syöttää tiedot joko välilyönnillä tai väliviivalla eroteltuna").".<br><br>";
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<tr><th>".t("Valitse osasto alasevetovalikosta").":</th>";
	
	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'
				ORDER BY selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='osasto'>";
	echo "<option value='' $sel>".t("Näytä kaikki")."</option>";

	while($srow = mysql_fetch_array ($sresult)){
		if($osasto == $srow[0]) {
			$sel = "SELECTED";
		}
		else {
			$sel = '';
		}
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}
	echo "</select></td><th>".t("tai syötä käsin")."</th><td><input type='text' name='osasto2' value='$osasto' size='15'></td></tr>";

	echo "<tr><th>".t("Valitse tuoteryhmä alasevetovalikosta").":</th>";
	
	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='TRY'
				ORDER BY selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='try'>";
	echo "<option value='' $sel>".t("Näytä kaikki")."</option>";

	while($srow = mysql_fetch_array ($sresult)) {
		if($try == $srow[0]) {
			$sel = "SELECTED";
		}
		else {
			$sel = '';
		}
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}
	echo "</select></td><th>tai syötä käsin</th><td><input type='text' name='try2' value='$try' size='15'></td></tr></table>";
	
	echo "<br><input type='submit' value='".t("Lähetä")."'></form>";
	
	require ("footer.inc");
?>