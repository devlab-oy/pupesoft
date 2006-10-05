<?php

echo "<font class='message'>Suorituksia kohdistetaan asiakkaaseen</font><br>";

$query  = "	SELECT * 
			FROM suoritus 
			WHERE asiakas_tunnus='' and yhtio='$kukarow[yhtio]' and summa != 0";
$result = mysql_query($query) or pupe_error($query);

echo "Yritän kohdistaa " .mysql_num_rows($result) . " suoritusta.<br>";

while ($suoritus=mysql_fetch_array ($result)) {
	$ok=0;
	
	//Kokeillaan ensin suoraan viitteellä 
	if ($suoritus['viite'] != '') {
		
		$query  = "	SELECT ytunnus 
					FROM lasku use index (yhtio_tila_mapvm) 
					WHERE viite = '$suoritus[viite]' and yhtio='$kukarow[yhtio]' and tila='U' and alatila='X' and mapvm='0000-00-00'";
		$laresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($laresult) == 1) { //Viitteellä löytyi lasku!
			$lasku=mysql_fetch_array($laresult);
			
			//Etsitään vastaava asiakas
			$query = "SELECT * FROM asiakas WHERE yhtio='$kukarow[yhtio]' and ytunnus='$lasku[ytunnus]'";
			$asres = mysql_query($query) or pupe_error($query);		
			
			if (mysql_num_rows($asres) == 1) {
				$asiakas=mysql_fetch_array($asres);
				$ok=1;
				
				echo "Kohdistettiin: $suoritus[nimi_maksaja] --> $asiakas[nimi] viitteen perusteella<br>";
				if ($asiakas['konserniyhtio'] != '') {
					$query   = "UPDATE tiliointi set tilino='$yhtiorow[konsernimyyntisaamiset]' where
										tiliointi.yhtio='$kukarow[yhtio]' AND
										tiliointi.tunnus='$suoritus[ltunnus]' AND
										tiliointi.korjattu=''";
					$result2 = mysql_query($query) or pupe_error($query);
					echo "Asiakas on konserniyhtiö<br>";
				}			
				flush();
				
				$query   = "UPDATE suoritus set asiakas_tunnus='$asiakas[tunnus]' where tunnus='$suoritus[tunnus]' AND yhtio='$kukarow[yhtio]'";
				$result2 = mysql_query($query) or pupe_error($query);
				
			}		
		}
	}
	if ($ok==0) {
		$old   = array("[","{","\\","|","]","}");
		$new   = array("Ä","ä", "Ö","ö","Å","å");
		$unimi = trim(preg_replace('/\b(oy|ab)\b/i', '', strtolower($suoritus['nimi_maksaja'])));
		$unimi = str_replace($old, $new, $unimi);

		$query = "SELECT * FROM asiakas WHERE yhtio='$kukarow[yhtio]' and MATCH (nimi) AGAINST ('$unimi')";
		$asres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($asres) == 1) {
			$asiakas = mysql_fetch_array($asres);
			
			echo "Kohdistettiin: $suoritus[nimi_maksaja] --> $asiakas[nimi] nimen perusteella<br>";
			if ($asiakas['konserniyhtio'] != '') {
				$query   = "UPDATE tiliointi set tilino='$yhtiorow[konsernimyyntisaamiset]' where
									tiliointi.yhtio='$kukarow[yhtio]' AND
									tiliointi.tunnus='$suoritus[ltunnus]' AND
									tiliointi.korjattu=''";
				$result2 = mysql_query($query) or pupe_error($query);
				echo "Asiakas on konserniyhtiö<br>";
			}
			flush();
			
			$query   = "UPDATE suoritus set asiakas_tunnus='$asiakas[tunnus]' where tunnus='$suoritus[tunnus]' AND yhtio='$kukarow[yhtio]'";
			$result2 = mysql_query($query) or pupe_error($query);
		}
	}
}

echo "<font class='message'>Suoritukset kohdistettu</font><br><br>";

?>
