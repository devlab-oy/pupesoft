<?php

	if ($argc == 0) {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	if (isset($argv[1]) and trim($argv[1]) != '') {

		// otetaan tietokanta connect
		require ("inc/connect.inc");
		require ("inc/functions.inc");

		$kukarow['yhtio'] = $argv[1];

		$query = "	SELECT *
					FROM yhtio
					WHERE yhtio = '{$kukarow['yhtio']}'";
		$result = mysql_query($query, $link) or die ("Kysely ei onnistu yhtio\n$query\n");

		if (mysql_num_rows($result) == 0) {
			echo "\nKäyttäjän yritys ei löydy! ({$kukarow['yhtio']})\n";
			exit;
		}

		function get_yhtsop_malli($link, $yhtio, $merkki = "", $cc = "", $malli = "", $vm = "") {

			$query = "	SELECT tunnus
						FROM yhteensopivuus_mp
						WHERE yhtio = '$yhtio'
		       			AND merkki = '$merkki'
						AND cc = '$cc'
						AND malli = '$malli'
						AND vm = '$vm'";
			$yht_result = mysql_query($query, $link) or pupe_error($query);

			$rivi = mysql_fetch_assoc($yht_result);
			return $rivi['tunnus'];
		}

		// otetaan yhteys tietokantaan mursu
		$mursu = mysql_connect("d69.arwidson.fi", "mursu", '!"mursunperse') or die ("Ongelma tietokantapalvelimessa d69\n");
		mysql_select_db("mursu", $mursu) or die ("Tietokantaa mursu löydy palvelimelta!\n");

		$query = "	SELECT *
					FROM tbl_users
					WHERE etunimi != ''
					AND sukunimi != ''
					AND sahkopostiosoite != ''
					ORDER BY tbl_users.id ASC";
		$users_res = mysql_query($query, $mursu) or die("Query failed:\n$query\n".mysql_error()."\n");

		while ($users_row = mysql_fetch_assoc($users_res)) {

			$email = strtolower(trim($users_row['sahkopostiosoite']));

			if (!preg_match_all('/[a-z0-9!#$%&\'*+\/\=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/\=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/', $email, $match)) {
				echo t("Viallinen sähköpostiosoite ($email)")."\n";
				continue;
			}

			list($nimi, $domain) = split("@",$email);
			if (!(checkdnsrr($domain,"MX") or checkdnsrr($domain, "A"))) {
			   // domain not found in DNS
				echo "\n";
				echo t("Sähköpostin domain ei löydy ($email)")."\n";
				echo "*********************************************************************************************\n";
				echo "\n";
				continue;
			}

			$ytunnus = 'P'.str_pad((int)$users_row['id'], 6, "0", STR_PAD_LEFT);

			$etunimi = trim($users_row['etunimi']);
			if (strpos($etunimi, "-") !== FALSE) {
				list($etu1, $etu2) = explode("-", $etunimi);
				$etunimi = ucfirst(strtolower($etu1))."-".ucfirst(strtolower($etu2));
			}
			else {
				$etunimi = ucfirst(strtolower($etunimi));
			}

			$etunimi = mysql_real_escape_string($etunimi);

			$sukunimi = trim($users_row['sukunimi']);
			if (strpos($sukunimi, "-") !== FALSE) {
				list($suku1, $suku2) = explode("-", $sukunimi);
				$sukunimi = ucfirst(strtolower($suku1))."-".ucfirst(strtolower($suku2));
			}
			else {
				$sukunimi = ucfirst(strtolower($sukunimi));
			}

			$sukunimi = mysql_real_escape_string($sukunimi);

			$osoite = mysql_real_escape_string(ucfirst(strtolower(trim($users_row['katuosoite']))));

			$postino = (int) $users_row['postinumero'];
			if (strlen($postino) < 5) {
				$postino = str_pad($postino, 5, "0", STR_PAD_LEFT);
			}

			$postitp = mysql_real_escape_string(ucfirst(strtolower(trim($users_row['postitoimipaikka']))));

			$kieli = '';
			if (strtolower(trim($users_row['kieli'])) == 'suomi' or strtolower(trim($users_row['kieli'])) == 'fi' or strtolower(trim($users_row['kieli'])) == 'fin') {
				$kieli = 'fi';
			}
			elseif (strtolower(trim($users_row['kieli'])) == 'ruotsi' or strtolower(trim($users_row['kieli'])) == 'se' or strtolower(trim($users_row['kieli'])) == 'swe') {
				$kieli = 'se';
			}

			$mainonta = 'Kyllä';
			if (strtolower($users_row['mainonta']) != 'kylla') {
				$mainonta = 'Ei';
			}

			$query = "	SELECT *
						FROM asiakas
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND email = '$email'";
			$asiakas_check_res = mysql_query($query, $link) or pupe_error($query);

			$user_id = 0;

			if (mysql_num_rows($asiakas_check_res) == 0) {
				$query = "	INSERT INTO asiakas SET
							ytunnus = '$ytunnus',
							nimi = '$etunimi $sukunimi',
							osoite = '$osoite',
							postino = '$postino',
							postitp = '$postitp',
							email = '$email',
							kieli = '$kieli',
							yhtio = '{$kukarow['yhtio']}',
							laji = 'R'";
				$asiakas_res = mysql_query($query, $link) or pupe_error($query);
				$user_id = mysql_insert_id();
			}
			else {
				$asiakas_check_row = mysql_fetch_assoc($asiakas_check_res);
				$user_id = $asiakas_check_row['tunnus'];

				$query = "	UPDATE asiakas SET
							osoite = '$osoite',
							postino = '$postino',
							postitp = '$postitp'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = $user_id";
				$user_address_update_res = mysql_query($query, $link) or pupe_error($query);
			}

			echo "$ytunnus ($user_id --> $email)";
			echo "\n";

			if ($users_row['sukupuoli'] != NULL) {
				$query = "	SELECT *
							FROM asiakkaan_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND liitostunnus = $user_id
							AND laji = 'sukupuoli'
							AND avainsana = '{$users_row['sukupuoli']}'";
				$sukupuoli_check_res = mysql_query($query, $link) or pupe_error($query);

				if (mysql_num_rows($sukupuoli_check_res) == 0) {
					$query = "	INSERT INTO asiakkaan_avainsanat SET
								yhtio = '{$kukarow['yhtio']}',
								liitostunnus = $user_id,
								laji = 'sukupuoli',
								avainsana = '{$users_row['sukupuoli']}',
								muuttaja = '',
								laatija = '',
								luontiaika = now()";
					$sukupuoli_res = mysql_query($query, $link) or pupe_error($query);
				}
			}

			if ($users_row['syntymaaika'] != NULL) {
				$query = "	SELECT *
							FROM asiakkaan_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND liitostunnus = $user_id
							AND laji = 'syntymavuosi'
							AND avainsana = '{$users_row['syntymaaika']}'";
				$syntymaaika_check_res = mysql_query($query, $link) or pupe_error($query);

				if (mysql_num_rows($syntymaaika_check_res) == 0) {
					$query = "	INSERT INTO asiakkaan_avainsanat SET
								yhtio = '{$kukarow['yhtio']}',
								liitostunnus = $user_id,
								laji = 'syntymavuosi',
								avainsana = '{$users_row['syntymaaika']}',
								muuttaja = '',
								laatija = '',
								luontiaika = now()";
					$syntymaaika_res = mysql_query($query, $link) or pupe_error($query);
				}
			}

			$query = "	SELECT *
						FROM asiakkaan_avainsanat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND liitostunnus = $user_id
						AND laji = 'lahde'
						AND avainsana = '{$users_row['tyyppi']}'";
			$tyyppi_check_res = mysql_query($query, $link) or pupe_error($query);

			if (mysql_num_rows($tyyppi_check_res) == 0) {
				$query = "	INSERT INTO asiakkaan_avainsanat SET
							yhtio = '{$kukarow['yhtio']}',
							liitostunnus = $user_id,
							laji = 'lahde',
							avainsana = '{$users_row['tyyppi']}',
							muuttaja = '',
							laatija = '',
							luontiaika = now()";
				$tyyppi_res = mysql_query($query, $link) or pupe_error($query);
			}

			if ($users_row['tarkenne'] != '') {
				$query = "	SELECT *
							FROM asiakkaan_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND liitostunnus = $user_id
							AND laji = 'tarkenne'
							AND avainsana = '{$users_row['tarkenne']}'";
				$tarkenne_check_res = mysql_query($query, $link) or pupe_error($query);

				if (mysql_num_rows($tarkenne_check_res) == 0) {
					$query = "	INSERT INTO asiakkaan_avainsanat SET
								yhtio = '{$kukarow['yhtio']}',
								liitostunnus = $user_id,
								laji = 'tarkenne',
								avainsana = '{$users_row['tarkenne']}',
								muuttaja = '',
								laatija = '',
								luontiaika = now()";
					$tarkenne_res = mysql_query($query, $link) or pupe_error($query);
				}
				else {
					$query = "	UPDATE asiakkaan_avainsanat SET
								avainsana = '{$users_row['tarkenne']}'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND laji = 'tarkenne'
								AND liitostunnus = $user_id";
					$tarkenne_res = mysql_query($query, $link) or pupe_error($query);
				}
			}

			$query = "	SELECT *
						FROM asiakkaan_avainsanat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND liitostunnus = $user_id
						AND laji = 'mainonta'
						AND avainsana = '$mainonta'";
			$mainonta_check_res = mysql_query($query, $link) or pupe_error($query);

			if (mysql_num_rows($mainonta_check_res) == 0) {
				$query = "	INSERT INTO asiakkaan_avainsanat SET
							yhtio = '{$kukarow['yhtio']}',
							liitostunnus = $user_id,
							laji = 'mainonta',
							avainsana = '$mainonta',
							muuttaja = '',
							laatija = '',
							luontiaika = now()";
				$mainonta_res = mysql_query($query, $link) or pupe_error($query);
			}

			$query = "	SELECT *
						FROM tbl_users_bike
						WHERE person_id = '$users_row[id]'";
			$bike_res = mysql_query($query, $mursu) or die("Query failed:\n$query\n".mysql_error()."\n");

			while ($bike_row = mysql_fetch_assoc($bike_res)) {
				$yhtsop = '';

				if (trim($bike_row['merkki']) != '' and trim($bike_row['kuutiotilavuus']) != '' and trim($bike_row['malli']) != '' and trim($bike_row['vuosimalli']) != '') {
					$yhtsop = get_yhtsop_malli($link, $kukarow['yhtio'], mysql_real_escape_string($bike_row['merkki']), mysql_real_escape_string($bike_row['kuutiotilavuus']), mysql_real_escape_string($bike_row['malli']), mysql_real_escape_string($bike_row['vuosimalli']));
				}

				$query = "	SELECT *
							FROM asiakkaan_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND liitostunnus = $user_id
							AND laji = 'yhteensopivuus'";
				$yhteensopivuus_check_res = mysql_query($query, $link) or pupe_error($query);

				if (mysql_num_rows($yhteensopivuus_check_res) == 0) {
					$query = "	INSERT INTO asiakkaan_avainsanat SET
								yhtio = '{$kukarow['yhtio']}',
								liitostunnus = $user_id,
								laji = 'yhteensopivuus',
								avainsana = '$yhtsop',
								muuttaja = '',
								laatija = '',
								luontiaika = now()";
					$yhteensopivuus_res = mysql_query($query, $link) or pupe_error($query);
				}
				else {
					$query = "	UPDATE asiakkaan_avainsanat SET
								avainsana = '$yhtsop'
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND liitostunnus = $user_id
								AND laji = 'yhteensopivuus'";
					$yhteensopivuus_res = mysql_query($query, $link) or pupe_error($query);
				}
			}

			$query = "	SELECT *
						FROM tbl_users_hobby
						WHERE person_id = '$users_row[id]'";
			$hobby_res = mysql_query($query, $mursu) or die("Query failed:\n$query\n".mysql_error()."\n");

			while ($hobby_row = mysql_fetch_assoc($hobby_res)) {

				$query = "	SELECT *
							FROM asiakkaan_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND liitostunnus = $user_id
							AND laji = 'harrastus'
							AND avainsana = '{$hobby_row['laji']}'";
				$hobby_check_res = mysql_query($query, $link) or pupe_error($query);

				if (mysql_num_rows($hobby_check_res) == 0) {
					$query = "	INSERT INTO asiakkaan_avainsanat SET
								yhtio = '{$kukarow['yhtio']}',
								liitostunnus = $user_id,
								laji = 'harrastus',
								avainsana = '{$hobby_row['laji']}',
								muuttaja = '',
								laatija = '',
								luontiaika = now()";
					$harrastus_res = mysql_query($query, $link) or pupe_error($query);
				}

				$kilpa = 'Ei';

				if (strtolower($hobby_row['kilpa']) == 'kylla' or strtolower($hobby_row['kilpa']) == 'kyllä') {
					$kilpa = 'Kyllä';
				}

				$query = "	SELECT *
							FROM asiakkaan_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND liitostunnus = $user_id
							AND laji = 'kilpa'
							AND avainsana = '$kilpa'";
				$hobby_check_res = mysql_query($query, $link) or pupe_error($query);

				if (mysql_num_rows($hobby_check_res) == 0) {
					$query = "	INSERT INTO asiakkaan_avainsanat SET
								yhtio = '{$kukarow['yhtio']}',
								liitostunnus = $user_id,
								laji = 'kilpa',
								avainsana = '$kilpa',
								muuttaja = '',
								laatija = '',
								luontiaika = now()";
					$kilpa_res = mysql_query($query, $link) or pupe_error($query);
				}
			}

			$query = "	SELECT *
						FROM tbl_catalog
						WHERE person_id = '$users_row[id]'";
			$catalog_res = mysql_query($query, $mursu) or die("Query failed:\n$query\n".mysql_error()."\n");

			while ($catalog_row = mysql_fetch_assoc($catalog_res)) {

				$query = "	SELECT *
							FROM asiakkaan_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND liitostunnus = $user_id
							AND laji = 'kuvasto'
							AND avainsana = '{$catalog_row['kuvasto']}'";
				$catalog_check_res = mysql_query($query, $link) or pupe_error($query);

				if (mysql_num_rows($catalog_check_res) == 0) {
					$query = "	INSERT INTO asiakkaan_avainsanat SET
								yhtio = '{$kukarow['yhtio']}',
								liitostunnus = $user_id,
								laji = 'kuvasto',
								avainsana = '{$catalog_row['kuvasto']}',
								muuttaja = '',
								laatija = '',
								luontiaika = now()";
					$kuvasto_res = mysql_query($query, $link) or pupe_error($query);
				}
			}
		}
	}

?>
