<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Vaihda tilauksen tila").":<hr></font>";

	// sallitaan vain numerot 0-9
	$tunnus = ereg_replace("[^0-9]", "", $tunnus);

	if ($tunnus != "" and $tee == "vaihda") {

		$tila_query  = "select * from lasku where yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
		$tila_result = mysql_query($tila_query) or pupe_error($tila_query);

		if (mysql_num_rows($tila_result) == 1) {

			// tilaus kesken
			if ($tila == "1") {
				$query = "	update tilausrivi set
							keratty        = '',
							kerattyaika    = '',
							toimitettu     = '',
							toimitettuaika = '',
							varattu        = tilkpl,
							jt             = 0,
							var            = ''
							where yhtio='$kukarow[yhtio]' and otunnus='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "	update lasku set
							tila    = 'N',
							alatila = ''
							where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "delete from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);
			}

			// tilaus tulostusjonossa
			if ($tila == "2") {
				$query = "	update tilausrivi set
							keratty        = '',
							kerattyaika    = '',
							toimitettu     = '',
							toimitettuaika = ''
							where yhtio='$kukarow[yhtio]' and otunnus='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "	update lasku set
							tila    = 'N',
							alatila = 'A'
							where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "delete from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);
			}

			// lähete tulostettu
			if ($tila == "3") {
				$query = "	update tilausrivi set
							keratty        = '',
							kerattyaika    = '',
							toimitettu     = '',
							toimitettuaika = ''
							where yhtio='$kukarow[yhtio]' and otunnus='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "	update lasku set
							tila    = 'L',
							alatila = 'A'
							where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "delete from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);
			}

			// tilaus kerätty
			if ($tila == "4") {
				$query = "	update tilausrivi set
							toimitettu     = '',
							toimitettuaika = ''
							where yhtio='$kukarow[yhtio]' and otunnus='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "	update lasku set
							tila    = 'L',
							alatila = 'C'
							where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "delete from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);
			}

			// rahtikirjatiedot syötetty
			if ($tila == "5") {
				$query = "	update tilausrivi set
							toimitettu     = '',
							toimitettuaika = ''
							where yhtio='$kukarow[yhtio]' and otunnus='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "	update lasku set
							tila    = 'L',
							alatila = 'B'
							where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "	update rahtikirjat
							set tulostettu = ''
							where yhtio='$kukarow[yhtio]' and otsikkonro='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);
			}

			// mitätöi
			if ($tila == "999") {
				$query = "	update tilausrivi set
							tyyppi = 'D'
							where yhtio='$kukarow[yhtio]' and otunnus='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "	update lasku set
							tila     = 'D',
							alatila  = tila,
							comments = '$kukarow[nimi] ($kukarow[kuka]) ".t("mitätöi tilauksen")." ".date("d.m.y @ G:i:s")."'
				 			where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "delete from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);
			}

		}

		$tee = "valitse";
	}

	if ($tunnus != "" and $tee == "valitse") {

		$tila_query  = "select * from lasku where yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
		$tila_result = mysql_query($tila_query) or pupe_error($tila_query);

		if (mysql_num_rows($tila_result) == 1) {

			$tila_row = mysql_fetch_array($tila_result);

			// vain laskuttamattomille myyntitilaukille voi tehdä jotain
			if ($tila_row["tila"] == "L" and $tila_row["alatila"] != "X" or ($tila_row["tila"] == "N" and in_array($tila_row["alatila"], array('A','')))) {
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='parametrit' value='$parametrit'>";
				echo "<input type='hidden' name='tee' value='vaihda'>";
				echo "<input type='hidden' name='tunnus' value='$tila_row[tunnus]'>";

				echo "<table><tr>";
				echo "<th>Vaihda tilauksen tila: </th>";
				echo "<td><select name='tila'>";
				echo "<option value = ''>Valitse uusi tila</option>";
				echo "<option value = '999'>Mitätöity</option>";
				if ($tila_row["alatila"] != "") {
					echo "<option value = '1'>Myyntitilaus kesken</option>";
				}
				if ($tila_row["tila"] == "L" and in_array($tila_row["alatila"], array('A','B','C','D'))) {
					echo "<option value = '2'>Myyntitilaus tulostusjonossa</option>";
				}
				if (in_array($tila_row["alatila"], array('B','C','D'))) {
					echo "<option value = '3'>Lähete tulostettu</option>";
				}
				if (in_array($tila_row["alatila"], array('B','D'))) {
					echo "<option value = '4'>Tilaus kerätty</option>";
				}
				if (in_array($tila_row["alatila"], array('D'))) {
					echo "<option value = '5'>Rahtikirjatiedot syötetty</option>";
				}
				echo "</select></td>";
				echo "<td class='back'><input type='submit' value='".t("Vaihda tila")."'></td>";
				echo "</form>";

				echo "</tr>";
				echo "</table><br>";
			}

			require ("raportit/naytatilaus.inc");

			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='parametrit' value='$parametrit'>";
			echo "<td class='back'><input type='submit' value='".t("Peruuta")."'></td>";
			echo "</form>";

		}
		else {
			echo "<font class='error'>".t("Tilausta ei löydy")."!</font>";
			$tee = "";
		}

	}

	if ($tee == "") {
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='valitse'>";
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Anna tilausnumero").":</th>";
		echo "<td><input type='text' name='tunnus'></td>";
		echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
	}

	require ("../inc/footer.inc");

?>