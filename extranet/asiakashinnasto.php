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

	$ytunnus = trim($ytunnus);

	if ($tee != '' and $ytunnus != '' and (int) $asiakas == 0 and $kukarow["extranet"] == '') {

		if (isset($muutparametrit)) {
			$muutparametrit = unserialize(urldecode($muutparametrit));
			$osasto 	= $muutparametrit[0];
			$try 		= $muutparametrit[1];
			$checkall	= $muutparametrit[2];
		}

		$muutparametrit = array($osasto, $try, $checkall);
		$muutparametrit = urlencode(serialize($muutparametrit));

		require("../inc/asiakashaku.inc");

		$asiakas = $asiakasrow["tunnus"];
		$ytunnus = $asiakasrow["ytunnus"];
	}
	elseif ($tee != '' and $kukarow["extranet"] != '') {
		//Haetaan asiakkaan tunnuksella
		$query  = "	SELECT *
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$asiakasrow = mysql_fetch_array($result);

			$ytunnus = $asiakasrow["ytunnus"];
			$asiakas = $asiakasrow["tunnus"];
		}
		else {
			echo t("VIRHE: Käyttäjätiedoissasi on virhe! Ota yhteys järjestelmän ylläpitäjään.")."<br><br>";
			exit;
		}
	}

	if ($tee != '' and $kukarow["eposti"] != "" and $asiakas > 0) {
		$where1 = '';
		$where2 = '';
		$osasto = mysql_real_escape_string(trim($osasto));
		$try    = mysql_real_escape_string(trim($try));

		if ($osasto != '' and $checkall == "") {
			$osastot = split(" ", $osasto);

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
			$where2 = substr($where2, 0, -1);
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

		$query  = "	SELECT if(toim_maa != '', toim_maa, maa) sallitut_maat, osasto
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$asiakas'";
		$maa_result = mysql_query($query) or pupe_error($query);
		$asiakas_maa_row = mysql_fetch_array($maa_result);

		$kieltolisa = '';

		if ($asiakas_maa_row["sallitut_maat"] != "") {
			$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$asiakas_maa_row[sallitut_maat]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$asiakas_maa_row[sallitut_maat]%' ";
		}

		if ((strlen($where) > 0 or $checkall != "") and $ytunnus != '' and $asiakas != '') {
			$query = "	SELECT *
						FROM tuote
						WHERE $where tuote.yhtio='$kukarow[yhtio]'
						and tuote.status NOT IN ('P','X') and hinnastoon != 'E'
						$kieltolisa
						ORDER BY tuote.osasto, tuote.try, tuote.tuoteno";
			$rresult = mysql_query($query) or pupe_error($query);

			//kirjoitetaan pdf faili levylle..
			$filenimi = "$kukarow[yhtio]-asiakashinnasto-".md5(uniqid(rand(),true)).".txt";

			if (!$fh = fopen("/tmp/".$filenimi, "w+"))
					die("filen luonti epäonnistui!");

			echo "<font class='message'>";
			echo t("Asiakashinnastoa luodaan...");
			echo "</font>";
			echo "<br>";
			flush();

			$rivi = t("Ytunnus").": $ytunnus";
			$rivi .= "\r\n";
			$rivi .= t("Asiakas").": $asiakasrow[nimi] $asiakasrow[nimitark]";
			$rivi .= "\r\n";
			$rivi .= t("Tuotenumero")."\t";
			$rivi .= t("EAN-koodi")."\t";
			$rivi .= t("Osasto")."\t";
			$rivi .= t("Tuoteryhmä")."\t";
			$rivi .= t("Nimitys")."\t";
			$rivi .= t("Yksikkö")."\t";
			$rivi .= t("Aleryhmä")."\t";
			$rivi .= t("Verollinen Myyntihinta")."\t";
			$rivi .= t("Alennus")."\t";
			$rivi .= t("Sinun veroton hinta")."\t";
			$rivi .= t("Sinun verollinen hinta")."\t";
			$rivi .= "\r\n";
			fwrite($fh, $rivi);

			$kala = 0;

			if ($GLOBALS['eta_yhtio'] != '' and ($GLOBALS['koti_yhtio'] != $kukarow['yhtio'] or $asiakas_maa_row['osasto'] != '6')) {
				unset($GLOBALS['eta_yhtio']);
			}

			while ($rrow = mysql_fetch_assoc($rresult)) {

				$kala++;

				if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['koti_yhtio'] == $kukarow['yhtio']) {
					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '{$GLOBALS['eta_yhtio']}'
								AND tuoteno = '{$row['tuoteno']}'";
					$tres_eta = mysql_query($query) or pupe_error($query);
					$alehinrrow = mysql_fetch_assoc($tres_eta);
				}
				else {
					$alehinrrow = $rrow;
				}

				//haetaan asiakkaan oma hinta
				$laskurow["ytunnus"] 		= $ytunnus;
				$laskurow["liitostunnus"] 	= $asiakas;
				$laskurow["vienti"] 		= '';
				$laskurow["alv"] 			= '';

				$hinnat = alehinta($laskurow, $alehinrrow, 1, '', '', '', "hinta,netto,ale,alehinta_alv,alehinta_val,hintaperuste,aleperuste", $GLOBALS['eta_yhtio']);

				$hinta = $hinnat["hinta"];
				$netto = $hinnat["netto"];
				$ale = $hinnat["ale"];
				$alehinta_alv = $hinnat["alehinta_alv"];
				$alehinta_val = $hinnat["alehinta_val"];

				list($hinta, $lis_alv) = alv($laskurow, $rrow, $hinta, '', $alehinta_alv);

				// katsotaan löytyyko asiakasalennus / asikakashinta
				if ($rrow["hinnastoon"] == "V" and ($hinnat["hintaperuste"] >= 13 or $hinnat["hintaperuste"] == false) and ($hinnat["aleperuste"] >= 9 or $hinnat["aleperuste"] == false)) {
					continue;
				}

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

				$asiakashinta_veroton = 0;
				$asiakashinta_verollinen = 0;
				$verollinen = 0;

				if ($yhtiorow["alv_kasittely"] == "") {
					$verollinen = $rrow["myyntihinta"];
					$asiakashinta_veroton = $asiakashinta;
					$asiakashinta_verollinen = round(($asiakashinta*(1+$rrow['alv']/100)),2);
				}
				else {
					$verollinen = round(($rrow["myyntihinta"]*(1+$rrow['alv']/100)),2);
					$asiakashinta_veroton = round(($asiakashinta/(1+$rrow['alv']/100)),2);
					$asiakashinta_verollinen = $asiakashinta;
				}

				$rivi  = $rrow["tuoteno"]."\t";
				$rivi .= $rrow["eankoodi"]."\t";
				$rivi .= $rrow["osasto"]."\t";
				$rivi .= $rrow["try"]."\t";
				$rivi .= $rrow["nimitys"]."\t";
				$rivi .= t_avainsana("Y", "", "and avainsana.selite='$rrow[yksikko]'", "", "", "selite")."\t";
				$rivi .= $rrow["aleryhma"]."\t";
				$rivi .= str_replace(".",",",$verollinen)."\t";

				if ($netto == "") {
					$rivi .= str_replace(".",",",sprintf('%.2f',$ale))."\t";
				}
				else {
					$rivi .= $ale."\t";
				}

				$rivi .= str_replace(".",",",sprintf("%.".$yhtiorow['hintapyoristys']."f",$asiakashinta_veroton))."\t";
				$rivi .= str_replace(".",",",sprintf("%.".$yhtiorow['hintapyoristys']."f",$asiakashinta_verollinen))."\t";
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

	if ($kukarow["extranet"] == '') {
		if ($asiakas > 0) {
			echo "<tr><th>".t("Asiakkaan ytunnus").":</th><td><input type='hidden' name='ytunnus' value='$ytunnus'>$ytunnus</td></tr>";
			echo "<input type='hidden' name='asiakas' value='$asiakas'></td></tr>";
		}
		else {
			echo "<tr><th>".t("Syötä asiakkaan ytunnus").":</th><td><input type='text' name='ytunnus' size='15' value='$ytunnus'></td></tr>";
		}
	}

	echo "<tr><th>".t("Osasto").":</th><td><input type='text' name='osasto' value='$osasto' size='15'></td></tr>";
	echo "<tr><th>".t("Tuoteryhmä").":</th><td><input type='text' name='try' value='$try' size='15'></td></tr>";

	if (isset($checkall) !== FALSE) {
		$chk='CHECKED';
	}

	echo "<tr><th>".t("Kaikki osastot ja tuoteryhmät").":</th><td><input type='checkbox' name='checkall' $chk></td></tr>";

	echo "</table><br>";
	echo "<input type='submit' value='Aja hinnasto'>";
	echo "</form>";

	if ($kukarow["extranet"] == '' and $asiakas > 0) {
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='submit' value='Valitse uusi asiakas'>";
		echo "</form>";
	}

	if (file_exists('parametrit.inc')) {
		require ("footer.inc");
	}
	else {
		require ("../inc/footer.inc");
	}
?>
