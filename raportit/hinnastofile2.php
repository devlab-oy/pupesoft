<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	if ($filenimi != '' and file_exists($filenimi)) {
		header("Content-type: application/force-download");
		header("Content-Disposition: attachment; filename=hindisk.zip");
		header("Content-Description: File Transfer");

		$toot = fopen($filenimi,"r");

		while (!feof ($toot)) {
			echo fgets($toot);
		}
		fclose ($toot);

		exec("rm -f ".$filenimi);
		exit;
	}

	require('../inc/parametrit.inc');
	echo "<font class='head'>".t("Hinnastoajo").":</font><hr>";

	if ($tee != '') {
		$where1 = '';
		$where2 = '';

		if ($osasto != '') {
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

		if ($try != '') {
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
		if (strlen($where2) > 0 && strlen($where1) > 0) {
			$where = "(". $where1." or ".$where2.")  and ";
		}

		if ($hinnastoon == "") {
			$where .= " tuote.hinnastoon!='E' and ";
		}

		if (strlen($where) > 0) {
			$query = "	SELECT tuote.tuoteno, tuote.nimitys, tuote.myyntihinta, tuote.yksikko, tuote.aleryhma, korvaavat.id
						FROM tuote
						LEFT JOIN korvaavat ON (tuote.tuoteno=korvaavat.tuoteno and tuote.yhtio=korvaavat.yhtio)
						WHERE $where tuote.yhtio='$kukarow[yhtio]'
						ORDER BY tuote.osasto, tuote.try";

			$result = mysql_query($query) or pupe_error($query);
			//echo "$query";
			flush();

			//kirjoitetaan pdf faili levylle..

			$filenimi = "$kukarow[yhtio]-hindisk-".md5(uniqid(rand(),true)).".txt";

			if (!$fh = fopen("/tmp/".$filenimi, "w+"))
					die("filen luonti epäonnistui!");

			while ($row = mysql_fetch_array($result)) {
				//haetaan aleryhmän tunnus
				$query = "	SELECT b.tunnus, b.ryhma
							FROM tuote a
							LEFT JOIN perusalennus b ON a.yhtio = b.yhtio and a.aleryhma = b.ryhma
							WHERE a.tuoteno = '$row[tuoteno]' and a.yhtio = '$kukarow[yhtio]'";
				$aleresult = mysql_query($query) or pupe_error($query);
				$alerow = mysql_fetch_array($aleresult);
				//echo "$query <br>";
				if ($alenimi == "") {
					$aletun = $alerow['tunnus'];
				}
				else {
					$aletun = $alerow['ryhma'];
				}
				//echo "$aletun <br>";
				$tunpit = strlen($aletun);

				//korvaavat tuotteet
				$koti 		= '';
				$edellinen 	= '';
				$seuraava 	= '';
				$korvaavat	= '';

				if ($row["id"] > 0) {
					$query = "select * from korvaavat where id='$row[id]' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
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
				$rivi .= sprintf('%-15.15s'	,$aletun);
				$rivi .= sprintf('%08d'		,str_replace('.','',$row["myyntihinta"]));
				$rivi .= sprintf('%08d'		,str_replace('.','',$row["myyntihinta"]));
				$rivi .= sprintf('%-20.20s'	,$seuraava);
				$rivi .= sprintf('%-20.20s'	,$edellinen);
				$rivi .= sprintf('%-3.3s'	,'');
				$rivi .= "\r\n";

				fwrite($fh, $rivi);
			}
			fclose($fh);//pakataan faili
			$cmd = "cd /tmp/;/usr/bin/zip $kukarow[yhtio].$kukarow[kuka].zip $filenimi";
			$palautus = exec($cmd);

			exec("rm -f /tmp/".$filenimi);

			$filenimi = "/tmp/$kukarow[yhtio].$kukarow[kuka].zip";
			echo "<br><table><tr><th>".t("Tallenna hinnasto tiedostoon")."</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='filenimi' value='$filenimi'>";
			echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr>";
			echo "</form></table>";

			//lopetetaan tähän
			exit;
		}
	}

	//Käyttöliittymä
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<tr><th>".t("Syötä osastot ja tuoteryhmät").":</th>
			<td><input type='text' name='osasto' value='$osasto' size='15'></td>
			<td><input type='text' name='try' value='$try' size='15'></tr>";
	echo "<tr><th>".t("Listaa kaikki tuotteet").":</th>
			<td colspan='2'><input type='checkbox' name='hinnastoon'> (".t("muuten hinnastoon flägi <> E").")</td></tr>";
	echo "<tr><th>".t("Näytä aleryhmän nimi").":</th>
			<td colspan='2'><input type='checkbox' name='alenimi'> (".t("muuten näytetään aleryhmän tunnus").")</td></tr></table><br>";
	echo "<input type='submit' value='".t("Aja raportti")."'>";
	echo "</form>";

	require ("../inc/footer.inc");
?>