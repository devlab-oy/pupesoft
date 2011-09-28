<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Vaihda tilauksen tila").":<hr></font>";

	// sallitaan vain numerot 0-9
	$tunnus = ereg_replace("[^0-9]", "", $tunnus);

	if ($tunnus != "" and $tee == "vaihda") {

		$tila_query  = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							AND tila in ('L','N','A','V')
							AND tunnus = '$tunnus'";
		$tila_result = mysql_query($tila_query) or pupe_error($tila_query);

		if (mysql_num_rows($tila_result) == 1) {
			$tila_row = mysql_fetch_assoc($tila_result);

			// lock tables
			$query = "LOCK TABLES lasku WRITE, tilausrivi WRITE, rahtikirjat WRITE, tuote WRITE, sarjanumeroseuranta WRITE, avainsana as avainsana_kieli READ";
			$locre = mysql_query($query) or pupe_error($query);

			// tilaus kesken
			if ($tila == "1") {
				$query = "	UPDATE tilausrivi set
							keratty        = '',
							kerattyaika    = '',
							toimitettu     = '',
							toimitettuaika = ''
							where yhtio = '$kukarow[yhtio]'
							and otunnus = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				if ($tila_row["tila"] == "V") {
					$uustila = "V";
				}
				elseif ($tila_row["tilaustyyppi"] == "A") {
					$uustila = "A";
				}
				else {
					$uustila = "N";
				}

				$query = "	UPDATE lasku set
							tila    = '$uustila',
							alatila = '',
							viite 	= ''
							where yhtio = '$kukarow[yhtio]'
							and tunnus = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "DELETE from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);
			}

			// tilaus tulostusjonossa
			if ($tila == "2") {
				$query = "	UPDATE tilausrivi set
							keratty        = '',
							kerattyaika    = '',
							toimitettu     = '',
							toimitettuaika = ''
							where yhtio = '$kukarow[yhtio]'
							and otunnus = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				if ($tila_row["tila"] == "V") {
					$uustila = "V";
					$uusalatila = "J";
				}
				else {
					$uustila = "N";
					$uusalatila = "A";
				}

				$query = "	UPDATE lasku set
							tila    = '$uustila',
							alatila = '$uusalatila'
							where yhtio = '$kukarow[yhtio]'
							and tunnus = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "DELETE from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);
			}

			// l�hete tulostettu
			if ($tila == "3") {
				$query = "	UPDATE tilausrivi set
							keratty        = '',
							kerattyaika    = '',
							toimitettu     = '',
							toimitettuaika = ''
							where yhtio = '$kukarow[yhtio]'
							and otunnus = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				if ($tila_row["tila"] == "V") {
					$uustila = "V";
				}
				else {
					$uustila = "L";
				}

				$query = "	UPDATE lasku set
							tila    = '$uustila',
							alatila = 'A'
							where yhtio = '$kukarow[yhtio]'
							and tunnus = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "DELETE from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);
			}

			// tilaus ker�tty
			if ($tila == "4") {
				$query = "	UPDATE tilausrivi set
							toimitettu     = '',
							toimitettuaika = ''
							where yhtio = '$kukarow[yhtio]'
							and otunnus = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				if ($tila_row["tila"] == "V") {
					$uustila = "V";
				}
				else {
					$uustila = "L";
				}

				$query = "	UPDATE lasku set
							tila    = '$uustila',
							alatila = 'C'
							where yhtio = '$kukarow[yhtio]'
							and tunnus = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "DELETE from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);
			}

			// rahtikirjatiedot sy�tetty
			if ($tila == "5") {
				$query = "	UPDATE tilausrivi set
							toimitettu     = '',
							toimitettuaika = ''
							where yhtio = '$kukarow[yhtio]'
							and otunnus = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "	UPDATE lasku set
							tila    = 'L',
							alatila = 'B'
							where yhtio = '$kukarow[yhtio]'
							and tunnus = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "	UPDATE rahtikirjat
							set tulostettu = ''
							where yhtio = '$kukarow[yhtio]'
							and otsikkonro = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);
			}

			// mit�t�i
			if ($tila == "999") {
				$query = "	UPDATE tilausrivi set
							tyyppi = 'D'
							where yhtio = '$kukarow[yhtio]'
							and otunnus = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "	UPDATE lasku set
							tila     = 'D',
							alatila  = tila,
							comments = '$kukarow[nimi] ($kukarow[kuka]) ".t("mit�t�i tilauksen")." ohjelmassa vaihda_tila.php ".date("d.m.y @ G:i:s")."'
				 			where yhtio = '$kukarow[yhtio]'
							and tunnus = '$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				$query = "DELETE from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$tunnus'";
				$tila_result = mysql_query($query) or pupe_error($query);

				//Nollataan sarjanumerolinkit
			   $query = "	SELECT tilausrivi.tunnus, (tilausrivi.varattu+tilausrivi.jt) varattu
							FROM tilausrivi
							JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
							WHERE tilausrivi.yhtio='$kukarow[yhtio]'
							and tilausrivi.otunnus='$tunnus'";
			   $sres = mysql_query($query) or pupe_error($query);

			   while ($srow = mysql_fetch_array($sres)) {
			       if ($srow["varattu"] > 0) {
			           $tunken = "myyntirivitunnus";
			       }
			       else {
			           $tunken = "ostorivitunnus";
			       }

			       $query = "UPDATE sarjanumeroseuranta set $tunken=0 WHERE yhtio='$kukarow[yhtio]' and $tunken='$srow[tunnus]'";
			       $sarjares = mysql_query($query) or pupe_error($query);
				}
			}

			// poistetaan lukot
			$query = "UNLOCK TABLES";
			$locre = mysql_query($query) or pupe_error($query);
		}

		$tee = "valitse";
	}

	if ($tunnus != "" and $tee == "valitse") {

		$tila_query  = "	SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							AND tila in ('L','N','A','V')
							AND tunnus = '$tunnus'";
		$tila_result = mysql_query($tila_query) or pupe_error($tila_query);

		if (mysql_num_rows($tila_result) == 1) {

			$tila_row = mysql_fetch_array($tila_result);

			// vain laskuttamattomille myyntitilaukille voi tehd� jotain
			if (	($tila_row["tila"] == "L" and $tila_row["alatila"] != "X") or
					($tila_row["tila"] == "N" and in_array($tila_row["alatila"], array('A',''))) or
					($tila_row["tila"] == "V" and in_array($tila_row["alatila"], array('','A','J','C')))) {

				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='parametrit' value='$parametrit'>";
				echo "<input type='hidden' name='tee' value='vaihda'>";
				echo "<input type='hidden' name='tunnus' value='$tila_row[tunnus]'>";

				echo "<table><tr>";
				echo "<th>".t("Vaihda tilauksen tila").": </th>";
				echo "<td><select name='tila'>";
				echo "<option value = ''>".t("Valitse uusi tila")."</option>";
				echo "<option value = '999'>".t("Mit�t�ity")."</option>";

				if ($tila_row["alatila"] != "") {
					echo "<option value = '1'>".t("Tilaus kesken")."</option>";
				}
				if (($tila_row["tila"] == "L" or $tila_row["tila"] == "V") and in_array($tila_row["alatila"], array('A','B','C','D'))) {
					echo "<option value = '2'>".t("Tilaus tulostusjonossa")."</option>";
				}
				if (in_array($tila_row["alatila"], array('B','C','D'))) {
					echo "<option value = '3'>".t("Ker�yslista tulostettu")."</option>";
				}
				if (in_array($tila_row["alatila"], array('B','D'))) {
					echo "<option value = '4'>".t("Tilaus ker�tty")."</option>";
				}
				if (in_array($tila_row["alatila"], array('D'))) {
					echo "<option value = '5'>".t("Rahtikirjatiedot sy�tetty")."</option>";
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
			echo "<font class='error'>".t("Tilausta ei l�ydy")."!</font>";
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