<?php

require('inc/parametrit.inc');

if ($tee == "lataa_tiedosto") {
	readfile("dataout/".$filenimi);
	exit;
}

echo "<font class='head'>".t("Viranomaisilmoitukset")."</font><hr><br><br>";

if($tee == "VSRALVYV") {
	
	echo "<font class='message'></font>".t("Arvonlisäveron yhteenvetoilmoitus");
	
	if($kohdekausi != "") {
		echo " - ".t("kaudelta")." $kohdekausi.";
	}
	
	echo "</font><br><hr>";
	$yhtiorow["maakoodi"] = "FI";
	$yhtiorow["ytunnus"] = 6270618;
	if (strtoupper($yhtiorow["maakoodi"])== 'FI') {
		//muutetaan ytunnus takas oikean näköseks
		$ytunpit = 8-strlen($yhtiorow["ytunnus"]);

		if ($ytunpit > 0) {
			$uytunnus = $yhtiorow["ytunnus"];
			while ($ytunpit > 0) {
			    $uytunnus = "0".$uytunnus; $ytunpit--;
			}
		}
		else {
			$uytunnus = $yhtiorow["ytunnus"];
		}

		$uytunnus = substr($uytunnus,0,7)."-".substr($uytunnus,7,1);
	}
	else {
		$uytunnus = $yhtiorow["ytunnus"];
	}
	
	if($kohdekausi != "") {
		list($kvarttaali,$vuosi) = explode("/", $kohdekausi);
		switch ($kvarttaali) {
			case 1:
				$alkupvm = "01-01";
				$loppupvm = "03-31";			
				break;
			case 2:
				$alkupvm = "04-01";
				$loppupvm = "06-30";			
				break;
			case 3:
				$alkupvm = "05-01";
				$loppupvm = "09-30";			
				break;
			case 4:
				$alkupvm = "10-01";
				$loppupvm = "12-31";			
				break;
			default:
				die("Kohdekausi on väärä!!!");
		}
		
		if($ytunnus!="") {
			//	Onko syötetty maa oikea
			$query = "select distinct(koodi) from maat where koodi = '$maa'";
			$result = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($result) == 1) {
				$query = "	UPDATE asiakas SET maa = '$maa'
							WHERE yhtio = '$kukarow[yhtio]'and ytunnus='$ytunnus'";
				$result = mysql_query($query) or pupe_error($query);
				echo "<font class='message'>".t("Korjattiin asiakkaan")." '$ytunnus' ".t("maakoodiksi")." '$maa'</font><br>";
			}
			else {
				echo "<font class='error'>".t("Syötetty maakoodi on väärin")."</font><br>";
			}
		}
		
		$query = "select group_concat(distinct(koodi) SEPARATOR '\',\'') from maat where eu != '' and koodi != 'FI'";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);
		$eumaat = $row[0]; 

		$query = "	SELECT lasku.ytunnus, if(lasku.maa='', asiakas.maa, lasku.maa) as maa, 
					asiakas.nimi, sum(rivihinta) summa, sum(round(rivihinta*100,0)) arvo, count(distinct(lasku.tunnus)) laskuja, lasku.ytunnus, lasku.liitostunnus, if(lasku.maa='','X','') asiakkaan_maa
					FROM lasku USE INDEX (yhtio_tila_tapvm)
					JOIN tilausrivi USE INDEX (uusiotunnus_index) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus
					JOIN tuote USE INDEX (tuoteno_index) ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and ei_saldoa='' and lasku.vienti='E'
					LEFT JOIN asiakas ON asiakas.yhtio = lasku.yhtio and lasku.liitostunnus = asiakas.tunnus 
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = 'U'
					and lasku.tapvm >= '$vuosi-$alkupvm' and lasku.tapvm <= '$vuosi-$loppupvm'
					GROUP BY lasku.ytunnus
					HAVING maa IN ('','$eumaat')";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);
		$ok=0;
		if(mysql_num_rows($result)>0) {
			
			$summa = 0;
			$osatiedot = "";
			$i=0;
			
			echo "<table cellpadding='1'><tr><th>".t("Maatunnus")."</th><th>".t("Ytunnus")."</th><th>".t("Asiakas")."</th><th>".t("Arvo")."</th><th>".t("Laskuja")."</th></tr>";
			while($row=mysql_fetch_array($result)) {
				if($row["maa"] == "") {

					$query = "	SELECT distinct koodi, nimi
								FROM maat
								WHERE nimi != ''
								ORDER BY koodi";
					$vresult = mysql_query($query) or pupe_error($query);
					$ulos = "<select name='maa'>";

					$ulos .= "<option value=''>".t("Valitse maa")."</option>";

					while ($vrow=mysql_fetch_array($vresult)) {

						$ulos .= "<option value = '".strtoupper($vrow[0])."'>".t($vrow[1])."</option>";
					}

					$ulos .= "</select>";
					
					echo "<tr><form enctype='multipart/form-data' action='$PHP_SELF' method='post'>
								<input type='hidden' name='tee' value='$tee'>
								<input type='hidden' name='ytunnus' value='$row[ytunnus]'>
								<input type='hidden' name='kohdekausi' value='$kohdekausi'>
								<td>$ulos</td><td>$row[ytunnus]</td><td>$row[nimi]</td><td>$row[summa]</td><td>$row[laskuja]</td>
								<td class='back'>
								<font class='error'>".t("VIRHE!!! asiakkaan maa puuttuu!!")."</font><br>
								<input type='submit' name='tallenna' value='".t("Korjaa asiakkaan maakoodi")."'>
								</td></form></tr>";
					
					$ok = 1;
					
				}
				elseif($row["maa"] != "" and $row["asiakkaan_maa"] == "X") {
					echo "<tr><td>$row[maa]</td><td>$row[ytunnus]</td><td>$row[nimi]</td><td>$row[summa]</td><td>$row[laskuja]</td><td class='back'><font class='info'>".t("HUOM! Maakoodi haettu asiakkaan tiedoista")."</font></td></tr>";
				} 
				else {
					echo "<tr><td>$row[maa]</td><td>$row[ytunnus]</td><td>$row[nimi]</td><td>$row[summa]</td><td>$row[laskuja]</td></tr>";					
				}
				
				if($row["maa"] != "") {
					$i++;					
					$summa+=$row["arvo"];
					$osatiedot .= "102:$row[maa]\n";
					$osatiedot .= "103:".sprintf("%012.12s",str_replace(array($row["maa"],"-","_"), "", $row["ytunnus"]))."\n";
					$osatiedot .= "210:$row[arvo]\n";
					$osatiedot .= "104:\n";
					$osatiedot .= "009:$i\n";
				}
				
			}
						
			if($ok==0) {
			
				$file = "000:$tee\n";
				$file .= "100:".date("dmY")."\n";
				$file .= "105:E03\n";
				$file .= "010:$uytunnus\n";			
				$file .= "053:$kohdekausi\n";
				$file .= "098:1\n";				
				$file .= "101:$summa\n";
				$file .= "001:$i\n";
				$file .= $osatiedot;
				$file .= "999:1\n";
				
				$filenimi = "VSRALVYV-$kvarttaali$vuosi	".date("dmy-His").".txt";
				$fh = fopen("dataout/".$filenimi, "w");
				if (fwrite($fh, $file) === FALSE) die("Kirjoitus epäonnistui $filenimi");
				fclose($fh);
				
				echo "<tr><td colspan='4' class='back'>
						<form enctype='multipart/form-data' action='$PHP_SELF' method='post'>
						<input type='hidden' name='tee' value='lataa_tiedosto'>
						<input type='hidden' name='kausi' value='$kausi'>
						<input type='hidden' name='lataa_tiedosto' value='1'>
						<input type='hidden' name='kaunisnimi' value='".t("Arvonlisaveron_yhteenvetoilmoitus-$kvarttaali$vuosi")."'>
						<input type='hidden' name='filenimi' value='$filenimi'>					
						<input type='submit' name='tallenna' value='".t("Tallenna tiedosto")."'></form>
						</td></tr>";
			}
			else {
				echo "<tr><td colspan='4' class='back'><font class='error'>".t("Korjaa virheet maakoodit ennen ilmoituksen lähettämistä")."</font></td></tr>";
			}
			echo "</table>";												
			
		}
		else {
			echo "<font class='message'>".t("Ei aineistoa valitulla kaudella")."</font>";
		}
		
		//
	}
	else {
		//	Haetaan alkupiste
		$query = "select ((year(now())-year(min(tilikausi_alku)))*4), quarter(now()) from tilikaudet where tilikausi_alku != '0000-00-00' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);
		$kausia = $row[0]+$row[1]+1;
		$kvarttaali = $row[1];
		$vuosi = date("Y");
		
		//	Ei näytetä ihan kaikeka
		if($kausia > 10) $kausia = 10;
		
		echo "<form enctype='multipart/form-data' action='$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='$tee'>
				<select name='kohdekausi' onchange='submit();'>
					<option value = ''>".t('Valitse kohdekausi')."</option>";

		for($i=1;$i<$kausia;$i++) {

			echo "<option value='$kvarttaali/$vuosi'>$kvarttaali/$vuosi</option>";

			if($kvarttaali == 1) {
				$kvarttaali = 4;
				$vuosi--;			
			}
			else {
				$kvarttaali--;	
			}
		}
		echo "</select>";		
	}
}

if($tee == "") {
	echo "<form enctype='multipart/form-data' action='$PHP_SELF' method='post'>
			<select name='tee' onchange='submit();'>
				<option value = ''>".t('Valitse viranomaisilmoitus')."</option>
				<option value = 'VSRALVYV'>".t("Arvonlisäveron yhteenvetoilmoitus")."</option>
			</select></form>";	
}


require ("inc/footer.inc");
?>