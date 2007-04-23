<?php

if (!function_exists("kopioi_tilaus")) {

	// otetaan parametriksi tunnus
	function kopioi_tilaus($tunnus) {

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
			$fields .= ", ".mysql_field_name($monistares, $i);
			$values .= ", '$monistarow[$i]'";
		}

		$kysely  = "INSERT into lasku ($fields) VALUES ($values)";
		$insres  = mysql_query($kysely) or pupe_error($kysely);
		$utunnus = mysql_insert_id();

		while ($rivirow = mysql_fetch_array($rivires)) {

			$rfields = mysql_field_name($rivires,0);
			$rvalues = "'$monistarow[0]'";

			for ($i = 1; $i < mysql_num_fields($rivires)-1; $i++) {

				if (mysql_field_name($rivires, $i) == "otunnus") {
					$rfields .= ", ".mysql_field_name($rivires, $i);
					$rvalues .= ", '$utunnus'";
				}
				else {
					$rfields .= ", ".mysql_field_name($rivires, $i);
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