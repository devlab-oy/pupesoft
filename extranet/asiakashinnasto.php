<?php

	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	
	if (file_exists('parametrit.inc')) {
		require('parametrit.inc');
	}
	else {
		require('../inc/parametrit.inc');
	}
	
	echo "<font class='head'>".t("Hinnasto asiakashinnoin")."</font><hr>";

	if ($kukarow["eposti"] == "") {
		echo "<font class='error'>".t("Sinulle ei ole määritelty sähköpostiosoitetta. Et voi ajaa tätä raporttia.")."</font><br>";
	}

	if ($tee != '' and $kukarow["eposti"] != "") {
		$where1 = '';
		$where2 = '';

		if ($osasto != '' and $checkall == "") {
			$osastot = split(" ",$osasto);

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

		if ($try != '' and $checkall == "") {
			$tryt = split(" ",$try);

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
		if (strlen($where2) > 0 and strlen($where1) > 0) {
			$where = "(". $where1." and ".$where2.")  and ";
		}

		$ytunnus = "";

		if ($kukarow["extranet"] == '' and $kukarow["oletus_asiakas"] == '' and $syytunnus != "") {
			$ytunnus = $syytunnus;
		}
		elseif ($kukarow["extranet"] != '' and $kukarow["oletus_asiakas"] != '') {
			//Haetaan asiakkaan tunnuksella
			$query  = "	SELECT *
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$asiakas = mysql_fetch_array($result);
				$ytunnus = $asiakas["ytunnus"];
			}
			else {
				echo t("VIRHE: Käyttäjätiedoissasi on virhe! Ota yhteys järjestelmän ylläpitäjään.")."<br><br>";
				exit;
			}
		}


		if ((strlen($where) > 0 or $checkall != "") and $ytunnus != '') {
			$query = "	SELECT *
						FROM tuote
						WHERE $where tuote.yhtio='$kukarow[yhtio]' and tuote.status NOT IN ('P','X')
						ORDER BY tuote.osasto, tuote.try, tuote.tuoteno";
			$rresult = mysql_query($query) or pupe_error($query);

			//kirjoitetaan pdf faili levylle..
			$filenimi = "$kukarow[yhtio]-asiakashinnasto-".md5(uniqid(rand(),true)).".txt";

			if (!$fh = fopen("/tmp/".$filenimi, "w+"))
					die("filen luonti epäonnistui!");

			echo "<font class='message'>";
			echo mysql_num_rows($rresult)." ".t("tuotetta löytyi").". ";
			echo t("Asiakashinnastoa ajetaan...");
			echo "</font>";
			echo "<br>";
			flush();

			$rivi  = t("Tuotenumero")."\t";
			$rivi .= t("Osasto")."\t";
			$rivi .= t("Tuoteryhmä")."\t";
			$rivi .= t("Nimitys")."\t";
			$rivi .= t("Yksikkö")."\t";
			$rivi .= t("Aleryhmä")."\t";
			$rivi .= t("Myyntihinta")."\t";
			$rivi .= t("Alennus")."\t";
			$rivi .= t("Sinun hinta")."\t";
			$rivi .= "\r\n";
			fwrite($fh, $rivi);

			$kala = 0;

			while ($rrow = mysql_fetch_array($rresult)) {

				$kala++;

				//haetaan asiakkaan oma hinta
				$laskurow["ytunnus"] = $ytunnus;
				
				list($hinta, $netto, $ale, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $rrow, 1, '', '', '');
				
				if ($netto != '') {
					$ale = t("Netto");
				}

				if ($hinta == 0) {
					$hinta = $rrow["myyntihinta"];
				}
				
				if ($netto == "") {
					$asiakashinta = round($hinta * (1-($ale/100)),$yhtiorow['hintapyoristys']);
				}
				else {
					$asiakashinta = $hinta;
				}

				$rivi  = $rrow["tuoteno"]."\t";
				$rivi .= $rrow["osasto"]."\t";
				$rivi .= $rrow["try"]."\t";
				$rivi .= $rrow["nimitys"]."\t";
				$rivi .= $rrow["yksikko"]."\t";
				$rivi .= $rrow["aleryhma"]."\t";
				$rivi .= str_replace(".",",",$rrow["myyntihinta"])."\t";

				if ($netto == "") {
					$rivi .= str_replace(".",",",sprintf('%.2f',$ale))."\t";
				}
				else {
					$rivi .= $ale."\t";
				}

				$rivi .= str_replace(".",",",sprintf("%.".$yhtiorow['hintapyoristys']."f",$asiakashinta))."\t";
				$rivi .= "\r\n";
				
				fwrite($fh, $rivi);
			}
			fclose($fh);

			//pakataan faili
			$cmd = "cd /tmp/;/usr/bin/zip $ytunnus-price.zip $filenimi";
			$palautus = exec($cmd);

			$liite = "/tmp/$ytunnus-price.zip";

			$bound = uniqid(time()."_") ;

			$header  = "From: <$yhtiorow[postittaja_email]>\n";
			$header .= "MIME-Version: 1.0\n" ;
			$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

			$content .= "--$bound\n";

			$content .= "Content-Type: application/zip; name=\"$ytunnus-price.zip\"\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: inline; filename=\"$ytunnus-price.zip\"\n\n";

			$handle  = fopen($liite, "r");
			$sisalto = fread($handle, filesize($liite));
			fclose($handle);

			$content .= chunk_split(base64_encode($sisalto));
			$content .= "\n" ;

			$content .= "--$bound\n";
			$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - Pricelist", $content, $header, "-f $yhtiorow[postittaja_email]");

			exec("rm -f /tmp/".$filenimi);
			exec("rm -f $liite");

			echo "<font class='message'>".t("Hinnasto lähetetty sähköpostiosoitteeseen").": $kukarow[eposti]</font><br>";
		}
	}

	//Käyttöliittymä
	echo "<br>";

	echo "<font class='message'>".("Osastot ja tuoteryhmät voit syöttää joko listana, pilkulla eroteltuna, tai osasto/tuoteryhmävälin väliviivalla.")."</font><br><br>";

	echo "<table><form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='kaikki'>";

	if ($kukarow["extranet"] == '' and $kukarow["oletus_asiakas"] == '') {
		echo "<tr><th>".t("Syötä asiakkaan ytunnus").":</th><td><input type='text' name='syytunnus' size='15' value='$syytunnus'></td></tr>";
	}

	echo "<tr><th>".t("Osasto").":</th><td><input type='text' name='osasto' value='$osasto' size='15'></td></tr>";
	echo "<tr><th>".t("Tuoteryhmä").":</th><td><input type='text' name='try' value='$try' size='15'></td></tr>";
	echo "<tr><th>".t("Kaikki osastot ja tuoteryhmät").":</th><td><input type='checkbox' name='checkall'></td></tr>";

	echo "</table><br>";
	echo "<input type='submit' value='Aja hinnasto'>";
	echo "</form>";
	
	if (file_exists('parametrit.inc')) {
		require ("footer.inc");
	}
	else {
		require ("../inc/footer.inc");
	}
?>