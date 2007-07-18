<?php

echo "<font class='message'>Suorituksia kohdistetaan asiakkaaseen</font><br>";

$query  = "	SELECT *
			FROM suoritus
			WHERE asiakas_tunnus = '' and 
			yhtio = '$kukarow[yhtio]' and 
			summa != 0";
$result = mysql_query($query) or pupe_error($query);

echo "Yritän kohdistaa " .mysql_num_rows($result) . " suoritusta.<br>";

while ($suoritus = mysql_fetch_array ($result)) {
	$ok = 0;

	// Kokeillaan ensin suoraan viitteellä
	if ($suoritus['viite'] != '') {
		$query  = "	SELECT liitostunnus
					FROM lasku USE INDEX (yhtio_tila_mapvm)
					WHERE yhtio = '$kukarow[yhtio]' AND
					tila = 'U' AND
					alatila = 'X' AND
					mapvm = '0000-00-00' AND
					viite = '$suoritus[viite]'";
		$laresult = mysql_query($query) or pupe_error($query);

		// Viitteellä löytyi lasku!
		if (mysql_num_rows($laresult) == 1) {
			$lasku = mysql_fetch_array($laresult);

			//Etsitään vastaava asiakas
			$query = "SELECT * FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$lasku[liitostunnus]'";
			$asres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($asres) == 1) {
				$asiakas = mysql_fetch_array($asres);
				$ok = 1;

				echo "Kohdistettiin: $suoritus[nimi_maksaja] --> $asiakas[nimi] viitteen perusteella<br>";

				if ($asiakas['konserniyhtio'] != '') {
					$query   = "	UPDATE tiliointi SET tilino = '$yhtiorow[konsernimyyntisaamiset]'
									WHERE
									tiliointi.yhtio    = '$kukarow[yhtio]' AND
									tiliointi.tunnus   = '$suoritus[ltunnus]' AND
									tiliointi.korjattu = ''";
					$result2 = mysql_query($query) or pupe_error($query);
					echo "Asiakas on konserniyhtiö<br>";
				}
				$query   = "UPDATE suoritus set asiakas_tunnus = '$asiakas[tunnus]' where tunnus = '$suoritus[tunnus]' AND yhtio = '$kukarow[yhtio]'";
				$result2 = mysql_query($query) or pupe_error($query);
			}
		}
	}

	// Kokeillaan kohdistaa nimellä
	if ($ok == 0) {

		$old   = array("[","{","\\","|","]","}");
		$new   = array("Ä","ä", "Ö","ö","Å","å");
		$unimi = trim(preg_replace('/\b(oy|ab)\b/i', '', strtolower($suoritus['nimi_maksaja'])));
		$unimi = str_replace($old, $new, $unimi);

		$query = "SELECT * FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and MATCH (nimi) AGAINST ('$unimi')";
		$asres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($asres) == 1) {
			$asiakas = mysql_fetch_array($asres);

			echo "Kohdistettiin: $suoritus[nimi_maksaja] --> $asiakas[nimi] nimen perusteella<br>";

			if ($asiakas['konserniyhtio'] != '') {
				$query   = "	UPDATE tiliointi SET tilino = '$yhtiorow[konsernimyyntisaamiset]'
								WHERE
								tiliointi.yhtio    = '$kukarow[yhtio]' AND
								tiliointi.tunnus   = '$suoritus[ltunnus]' AND
								tiliointi.korjattu = ''";
				$result2 = mysql_query($query) or pupe_error($query);
				echo "Asiakas on konserniyhtiö<br>";
			}
			$query   = "UPDATE suoritus set asiakas_tunnus = '$asiakas[tunnus]' where tunnus = '$suoritus[tunnus]' AND yhtio = '$kukarow[yhtio]'";
			$result2 = mysql_query($query) or pupe_error($query);
		}
	}
}

echo "<font class='message'>Suoritukset kohdistettu</font><br><br>";

?>
