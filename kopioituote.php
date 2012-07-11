<?php

	require ("inc/parametrit.inc");

	if ($tee != 'PERUSTA') {

		if ($livesearch_tee == "TUOTEHAKU") {
			livesearch_tuotehaku();
			exit;
		}

		// Enaboidaan ajax kikkare
		enable_ajax();

		echo "<font class='head'>".t("Kopioi tuote")."</font><hr>";
	}

	if ($tee == 'PERUSTA') {
		//	Trimmataan tyhjät merkit
		$uustuoteno = trim($uustuoteno);

		if ($uustuoteno == '') {
			$tee = 'AVALITTU';
			$varaosavirhe = t("VIRHE: Uusi tuotenumero ei saa olla tyhjä")."!";
		}
	}

	if ($tee == 'PERUSTA') {

		if (strpos($tuoteno, '####') !== FALSE) {
			$hakyhtio	= substr($tuoteno, strpos($tuoteno, '####')+4);
			$tuoteno 	= substr($tuoteno, 0, strpos($tuoteno, '####'));
		}
		else {
			$hakyhtio = $kukarow["yhtio"];
		}

		$query = "	SELECT tunnus
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$uustuoteno'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 0 ) {
			$tee = 'HAKU';
			$varaosavirhe = t("VIRHE: Uudella tuotenumerolla")." $uustuoteno ".t("löytyy jo tuote, ei voida perustaa")."!";
		}
		else {
			$query = "	SELECT *
						FROM tuote
						WHERE yhtio = '$hakyhtio'
						and tuoteno = '$tuoteno'";
			$stresult = pupe_query($query);

			if (mysql_num_rows($stresult) == 0) {
				$tee = 'HAKU';
				$varaosavirhe = t("VIRHE: Vanha tuote")." $tuoteno ".t("on kadonnut, ei uskalleta tehdä mitään")."!";
			}
			else {
				$otsikkorivi = mysql_fetch_array($stresult);

				$tuotepaikat_query = "	SELECT *
										FROM tuotepaikat
										WHERE tuoteno = '$tuoteno'
										and yhtio = '$kukarow[yhtio]'";
				$tuotepaikat_result = pupe_query($tuotepaikat_query);

				if ($yhtiorow["tuotteen_oletuspaikka"] != "" and mysql_num_rows($tuotepaikat_result) == 0 and $otsikkorivi["ei_saldoa"] == "") {
					list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode("-", $yhtiorow["tuotteen_oletuspaikka"]);

					if ($hyllyalue == "") {
						$hyllyalue = 0;
					}
					if ($hyllynro == "") {
						$hyllynro = 0;
					}
					if ($hyllyvali == "") {
						$hyllyvali = 0;
					}
					if ($hyllytaso == "") {
						$hyllytaso = 0;
					}

					$tuotepaikka_query = "	INSERT INTO tuotepaikat set
					 						yhtio			= '$kukarow[yhtio]',
								 			tuoteno     	= '$tuoteno',
								 			oletus      	= 'X',
						   		 			saldoaika   	= now(),
											hyllyalue   	= '$hyllyalue',
											hyllynro    	= '$hyllynro',
											hyllyvali   	= '$hyllyvali',
											hyllytaso   	= '$hyllytaso',
											luontiaika		= now(),
											laatija			= '$kukarow[kuka]',
											muutospvm		= now(),
											muuttaja		= '$kukarow[kuka]'";
					$tuotepaikka_result = pupe_query($tuotepaikka_query);
				}

				// tehdään vanhasta tuotteesta 1:1 kopio...
				$query = "INSERT into tuote set ";

				for ($i = 0; $i < mysql_num_fields($stresult); $i++) {

					if (mysql_field_name($stresult, $i) == 'yhtio') {
						$query .= "yhtio='$kukarow[yhtio]',";
					}
					// tuotenumeroksi tietenkin uustuoteno
					elseif (mysql_field_name($stresult, $i) == 'tuoteno') {
						$query .= "tuoteno='$uustuoteno',";
					}
					// laatijaksi klikkaaja
					elseif (mysql_field_name($stresult,$i) == 'laatija') {
						$query .= "laatija='$kukarow[kuka]',";
					}
					// muuttajaksi klikkaaja
					elseif (mysql_field_name($stresult,$i) == 'muuttaja') {
						$query .= "muuttaja='$kukarow[kuka]',";
					}
					// luontiaika
					elseif (mysql_field_name($stresult,$i) == 'luontiaika' or mysql_field_name($stresult,$i) == 'muutospvm') {
						$query .= mysql_field_name($stresult,$i)."=now(),";
					}
					// nämä kentät tyhjennetään
					elseif (mysql_field_name($stresult,$i) == 'kehahin' or
							mysql_field_name($stresult,$i) == 'vihahin' or
							mysql_field_name($stresult,$i) == 'vihapvm' or
							mysql_field_name($stresult,$i) == 'epakurantti25pvm' or
							mysql_field_name($stresult,$i) == 'epakurantti50pvm' or
							mysql_field_name($stresult,$i) == 'epakurantti75pvm' or
							mysql_field_name($stresult,$i) == 'epakurantti100pvm' or
							mysql_field_name($stresult,$i) == 'eankoodi') {
						$query .= mysql_field_name($stresult,$i)."='',";
					}
					// ja kaikki muut paitsi tunnus sellaisenaan
					elseif (mysql_field_name($stresult,$i) != 'tunnus') {
						$query .= mysql_field_name($stresult,$i)."='".$otsikkorivi[$i]."',";
					}
				}
				$query = substr($query,0,-1);
				$stresult = pupe_query($query);

				$tuote_id = mysql_insert_id();

				//	Tämä funktio tekee myös oikeustarkistukset!
				synkronoi($kukarow["yhtio"], "tuote", $tuote_id, "", "");

				$query = "	SELECT *
							FROM tuotteen_toimittajat
							WHERE yhtio = '$hakyhtio'
							and tuoteno = '$tuoteno'";
				$stresult = pupe_query($query);

				if (mysql_num_rows($stresult) != 0 ) {
					while ($otsikkorivi = mysql_fetch_array($stresult)) {

						$query_fields = "";

						for ($i=0; $i<mysql_num_fields($stresult); $i++) {

							if (mysql_field_name($stresult,$i) == 'yhtio') {
								$query_fields .= "yhtio='$kukarow[yhtio]',";
							}
							// tuotenumeroksi tietenkin uustuoteno
							elseif (mysql_field_name($stresult,$i) == 'tuoteno') {
								$query_fields .= "tuoteno='$uustuoteno',";
							}
							// laatijaksi klikkaaja
							elseif (mysql_field_name($stresult,$i) == 'laatija') {
								$query_fields .= "laatija='$kukarow[kuka]',";
							}
							// muuttajaksi klikkaaja
							elseif (mysql_field_name($stresult,$i) == 'muuttaja') {
								$query_fields .= "muuttaja='$kukarow[kuka]',";
							}
							// luontiaika
							elseif (mysql_field_name($stresult,$i) == 'luontiaika' or mysql_field_name($stresult,$i) == 'muutospvm') {
								$query_fields .= mysql_field_name($stresult,$i)."=now(),";
							}
							// ja kaikki muut paitsi tunnus sellaisenaan
							elseif (mysql_field_name($stresult,$i) != 'tunnus') {
								$query_fields .= mysql_field_name($stresult,$i)."='".$otsikkorivi[$i]."',";
							}
						}

						// Tehdään vanhoista tuotteen_toimittajista 1:1 kopio...
						$query  = "INSERT into tuotteen_toimittajat set ";
						$query .= substr($query_fields, 0, -1);
						$query .= " ON DUPLICATE KEY UPDATE ";
						$query .= substr($query_fields, 0, -1);

						$astresult = pupe_query($query);
						$id2 = mysql_insert_id();

						synkronoi($kukarow["yhtio"], "tuotteen_toimittajat", $id2, "", "");
					}
				}

				// kopioidaan dynaamisen puun tiedot uudelle tuotteelle
				$query = "	SELECT *
							FROM puun_alkio
							WHERE yhtio = '$hakyhtio'
							and laji    = 'tuote'
							and liitos  = '$tuoteno'";
				$stresult = pupe_query($query);

				if (mysql_num_rows($stresult) != 0) {

					while ($otsikkorivi = mysql_fetch_array($stresult)) {

						$query_fields = "";

						for ($i = 0; $i < mysql_num_fields($stresult); $i++) {

							if (mysql_field_name($stresult,$i) == 'yhtio') {
								$query_fields .= "yhtio='$kukarow[yhtio]',";
							}
							// liitokseksi tietenkin uustuoteno
							elseif (mysql_field_name($stresult,$i) == 'liitos') {
								$query_fields .= "liitos='$uustuoteno',";
							}
							// laatijaksi klikkaaja
							elseif (mysql_field_name($stresult,$i) == 'laatija') {
								$query_fields .= "laatija='$kukarow[kuka]',";
							}
							// muuttajaksi klikkaaja
							elseif (mysql_field_name($stresult,$i) == 'muuttaja') {
								$query_fields .= "muuttaja='$kukarow[kuka]',";
							}
							// luontiaika
							elseif (mysql_field_name($stresult,$i) == 'luontiaika' or mysql_field_name($stresult,$i) == 'muutospvm') {
								$query_fields .= mysql_field_name($stresult,$i)."=now(),";
							}
							// ja kaikki muut paitsi tunnus sellaisenaan
							elseif (mysql_field_name($stresult,$i) != 'tunnus') {
								$query_fields .= mysql_field_name($stresult,$i)."='".$otsikkorivi[$i]."',";
							}
						}

						// Tehdään vanhasta alkiosta kopio...
						$query = "INSERT into puun_alkio set ";
						$query .= substr($query_fields, 0, -1);
						$query .= " ON DUPLICATE KEY UPDATE ";
						$query .= substr($query_fields, 0, -1);

						$puunalkio_result = pupe_query($query);

						$id2 = mysql_insert_id();

						synkronoi($kukarow["yhtio"], "puun_alkio", $id2, "", "");
					}
				}

				//	Lähetetään mailia tästä eteenpäin jos meillä on vastaanottajia
				if ($yhtiorow["tuotekopio_email"] != "") {
					$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
					$header .= "MIME-Version: 1.0\n" ;

					$query = "	SELECT *
								FROM yhtio
								WHERE yhtio = '$hakyhtio'";
					$yres = pupe_query($query);
					$yrow = mysql_fetch_array($yres);

					$content = $kukarow["nimi"]." ".t("kopioi yhtiön")." $yrow[nimi] ".t("tuotteen")." '$tuoteno' ".t("yhtiön")." $yhtiorow[nimi] ".t("tuotteeksi")." '$uustuoteno'\n\n";

					mail($yhtiorow["tuotekopio_email"], mb_encode_mimeheader(t("Tuotteita kopioitu"), "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
				}

				$toim 	= 'tuote';
				$tunnus = $tuote_id;
				$tee 	= '';

				require ("yllapito.php");

				exit;
			}
		}
	}

	if ($tee == 'HAKU') {

		$query = "	SELECT tunnus
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tuoteno'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$tee = 'AVALITTU';
		}
		else {
			$konsernihaku = "KYLLA";
			$kaikkituhaku = "KYLLA";

			if (strpos($tuoteno, '*') === FALSE) {
				$tuoteno = $tuoteno."*";
			}

			require("inc/tuotehaku.inc");

			//on vaan löytynyt 1 muuten tulis virhettä ja ulosta
			if ($tee == 'HAKU' and $ulos == '' and $varaosavirhe == '' and $tuoteno != '') {
				$tee = 'AVALITTU';
			}
		}
	}

	if ($tee == 'AVALITTU' and $tuoteno != '') {
		$formi  = 'performi';
		$kentta = 'uustuoteno';

		echo "<table>";
		echo "<tr><th>".t("Kopioitava tuote")."</th></tr>";

		if(strpos($tuoteno, '####') !== FALSE) {
			$tu = substr($tuoteno, strpos($tuoteno, '####')+4)." - ".substr($tuoteno, 0, strpos($tuoteno, '####'));
		}
		else {
			$tu = $tuoteno;
		}

		echo "<tr><td>$tu</td>";

		echo "<tr><th>".t("Anna uusi tuotenumero")."<br>".t("joka perustetaan")."</th></tr>";
		echo "<tr><form method='post' name='$formi' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='PERUSTA'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<td><input type='text' name='uustuoteno' size='22' maxlength='30' value=''></td>";
		echo "<td class='back'><input type='Submit' value='".t("Kopioi")."'></td>";
		echo "<td class='back'><font class='error'>$varaosavirhe</font></td>";
		echo "</form></tr></table>";
	}

	if (($tee == 'HAKU' or $tee == "Y") and $ulos != '') {
			echo "<form method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='AVALITTU'>";
			echo "<table><tr>";
			echo "<th>".t("Valitse listasta").":</th></tr>";
			echo "<tr><td>$ulos</td>";
			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
			echo "<td class='back'><font class='error'>$varaosavirhe</font></td>";
			echo "</tr></table>";
			echo "</form>";

			$varaosavirhe = "";
	}

	if ($tee == '' or $tee == "Y") {
		$formi  = 'formi';
		$kentta = 'tuoteno';

		echo "<table><tr>";
		echo "<th>".t("Anna tuotenumero josta")."<br>".t("haluat kopioda tiedot")."</th>";

		echo "<tr><form method='post' name='$formi' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='HAKU'>";
		echo "<td>".livesearch_kentta("formi", "TUOTEHAKU", "tuoteno", 210)."</td>";
		echo "<td class='back'><input type='Submit' value='".t("Jatka")."'></td>";
		echo "<td class='back'><font class='error'>$varaosavirhe</font></td>";
		echo "</form></tr></table>";
	}

	require("inc/footer.inc");
?>
