<?php

if (!function_exists("kopioi_tilaus")) {

	// otetaan parametriksi tunnus
	function kopioi_tilaus($tunnus, $muokkaa_lasku="", $muokkaa_rivi) {

		// tarvitaan yhtiörowta ja kukarowta
		global $yhtiorow, $kukarow;

		$query = "SELECT * FROM lasku WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
		$monistares = mysql_query($query) or pupe_error($query);

		$query = "SELECT * from tilausrivi where otunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
		$rivires = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($monistares) == 0) {
			echo "lasku not found";
			return FALSE;
		}

		if (mysql_num_rows($rivires) == 0) {
			echo "tilausrivi not found";
			return FALSE;
		}

		$monistarow = mysql_fetch_array($monistares);

		$fields = mysql_field_name($monistares,0);
		$values = "'".$monistarow[0]."'";

		// monistetaan kaikki paitsi tunnus
		for ($i = 1; $i < mysql_num_fields($monistares)-1; $i++) {
			$field = mysql_field_name($monistares, $i);
			$fields .= ", $field";
			
			//	Muokkaukset
			if(is_array($muokkaa_lasku[$field])) {
				if($muokkaa_lasku[$field]["from"] != "" and $muokkaa_lasku[$field]["to"] != "") {
					$monistarow[$i] = preg_replace($muokkaa_lasku[$field]["from"], $muokkaa_lasku[$field]["to"], $monistarow[$i]);
				}
			}
			
			$values .= ", '$monistarow[$i]'";
		}

		$kysely  = "INSERT into lasku ($fields) VALUES ($values)";
		$insres  = mysql_query($kysely) or pupe_error($kysely);
		$utunnus = mysql_insert_id();

		while ($rivirow = mysql_fetch_array($rivires)) {

			$rfields = mysql_field_name($rivires,0);
			$rvalues = "'$monistarow[0]'";

			for ($i = 1; $i < mysql_num_fields($rivires)-1; $i++) {
				
				$field = mysql_field_name($rivires, $i);
				
				if (mysql_field_name($rivires, $i) == "otunnus") {
					$rfields .= ", $field";
					$rvalues .= ", '$utunnus'";
				}
				else {
					
					//	Muokkaukset
					if(is_array($muokkaa_rivi[$field])) {
						if($muokkaa_rivi[$field]["from"] != "" and $muokkaa_rivi[$field]["to"] != "") {
							$rivirow[$i] = preg_replace($muokkaa_rivi[$field]["from"], $muokkaa_rivi[$field]["to"], $rivirow[$i]);
						}
					}
					
					$rfields .= ", $field";
					$rvalues .= ", '$rivirow[$i]'";
				}

			}

			$kysely = "INSERT into tilausrivi ($rfields) VALUES ($rvalues)";
			$insres = mysql_query($kysely) or pupe_error($kysely);
			$insid  = mysql_insert_id();

		}

		//Korjataan perheid:t uusilla riveillä
		$query = "	SELECT perheid, min(tunnus) uusiperheid
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$utunnus'
					and perheid != 0
					GROUP by perheid";
		$copresult = mysql_query($query) or pupe_error($query);

		while ($coprivirow = mysql_fetch_array($copresult)) {
			$query = "	UPDATE tilausrivi
						SET perheid = '$coprivirow[uusiperheid]'
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$utunnus'
						and perheid = '$coprivirow[perheid]'";
			$cores = mysql_query($query) or pupe_error($query);
		}

		//Korjataan perheid2:t uusilla riveillä
		$query = "	SELECT perheid2, min(tunnus) uusiperheid2
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$utunnus'
					and perheid2 != 0
					GROUP by perheid2";
		$copresult = mysql_query($query) or pupe_error($query);

		while ($coprivirow = mysql_fetch_array($copresult)) {
			$query = "	UPDATE tilausrivi
						SET perheid2 = '$coprivirow[uusiperheid2]'
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$utunnus'
						and perheid2 = '$coprivirow[perheid2]'";
			$cores = mysql_query($query) or pupe_error($query);
		}

		return $utunnus; // palautetaan uusi lasku.tunnus
	}

}

?>
