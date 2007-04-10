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
		
		$query = "select group_concat(distinct(koodi) SEPARATOR '\',\'') from maat where eu != '' and koodi != 'FI'";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);
		$eumaat = $row[0]; 

		$query = "	SELECT lasku.ytunnus, lasku.maa, asiakas.nimi, sum(rivihinta) summa, sum(round(rivihinta*100,0)) arvo, count(distinct(lasku.tunnus)) laskuja, if(lasku.maa = '', 'X', '') tark
					FROM lasku USE INDEX (yhtio_tila_tapvm)
					JOIN tilausrivi USE INDEX (uusiotunnus_index) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus
					JOIN tuote USE INDEX (tuoteno_index) ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and ei_saldoa=''
					LEFT JOIN asiakas ON asiakas.yhtio = lasku.yhtio and lasku.liitostunnus = asiakas.tunnus 
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = 'U' and lasku.maa IN ('','$eumaat')
					and lasku.tapvm >= '$vuosi-$alkupvm' and lasku.tapvm <= '$vuosi-$loppupvm'
					GROUP BY lasku.ytunnus";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);
		
		if(mysql_num_rows($result)>0) {
			
			$summa = 0;
			$osatiedot = "";
			$i=0;
			
			echo "<table cellpadding='1'><tr><th>".t("Maatunnus")."</th><th>".t("Ytunnus")."</th><th>".t("Asiakas")."</th><th>".t("Arvo")."</th><th>".t("Laskuja")."</th></tr>";
			while($row=mysql_fetch_array($result)) {
				$lisa = "";
				if($row["tark"] != "") {
					$lisa = "<td class = 'back'><font class='error'>".t("HUOM! maa puuttuu!!")."</font></td>";
				}
				
				echo "<tr><td>$row[maa]</td><td>$row[ytunnus]</td><td>$row[nimi]</td><td>$row[summa]</td><td>$row[laskuja]</td>$lisa</tr>";
				
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
			
			echo "<tr><td colspan='4' class='back'><form enctype='multipart/form-data' action='$PHP_SELF' method='post'>
					<input type='hidden' name='tee' value='lataa_tiedosto'>
					<input type='hidden' name='kausi' value='$kausi'>
					<input type='hidden' name='lataa_tiedosto' value='1'>
					<input type='hidden' name='kaunisnimi' value='".t("Arvonlisaveron_yhteenvetoilmoitus-$kvarttaali$vuosi")."'>
					<input type='hidden' name='filenimi' value='$filenimi'>					
					<input type='submit' name='tallenna' value='".t("Tallenna tiedosto")."'></td></tr>";
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
			</select>";	
}


require ("inc/footer.inc");
?>