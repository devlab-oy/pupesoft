<?php

	// M‰‰ritell‰‰n funktio, jolla kopsataan valitut rivit uudelle myyntitilaukselle.
	require("tilauksesta_myyntitilaus.inc");

	if ($tilatapa == "VALITSE") {
		$sela = "";
		$selb = "";

		if ($row["var"] == "A") {
			$sela = "SELECTED";
		}
		if ($row["var"] == "B") {
			$selb = "SELECTED";
		}

		$paikat = "<option value=''>".t("J‰t‰ myyntitiliin");
		$paikat .= "<option value='A' $sela>".t("Laskuta asiakasta");
		$paikat .= "<option value='B' $selb>".t("Palauta omaan varastoon");

		$paikat = "<select Style='{font-size: 8pt; padding:0;}' name='myyntitilirivit' onchange='submit();'>".$paikat."</select>";

		echo "	<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tilausnumero' value = '$tilausnumero'>
				<input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
				<input type='hidden' name='tila' value = 'MYYNTITILIRIVI'>";
		echo "<td $class align='left'>$paikat</td>";

		//formi suljetaan tilaus_myynti.php failissa

		$tilatapa	= "";
	}

	if ($tilatapa == "PAIVITA") {
		//P‰ivitet‰‰n is‰n perheid nollaksi jotta voidaan lis‰t‰ lis‰ lis‰varusteita
		$query	= "	SELECT *
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]'
					AND otunnus = '$kukarow[kesken]'
					AND tunnus  = '$rivitunnus'";
		$qres = pupe_query($query);
		$qrow = mysql_fetch_array($qres);

		if ($qrow["kpl"] != $kpl and (float) $kpl > 0) {
			$isakpl = $qrow["kpl"];

			if($qrow["perheid"] != 0) {
				$query	= "	SELECT *
							FROM tilausrivi
							WHERE yhtio = '$kukarow[yhtio]'
							AND otunnus = '$kukarow[kesken]'
							AND perheid = '$qrow[perheid]'
							ORDER BY tunnus";
				$qres = pupe_query($query);
			}

			mysql_data_seek($qres,0);

			$uuden_perheen_perheid = 0;
			$kopsatut_rivit = array();

			while($qrow = mysql_fetch_array($qres)) {

				$uusikpl = round(($qrow["kpl"]/$isakpl)*$kpl,2);
				$erotus  = $qrow["kpl"] - $uusikpl;

				if ($erotus > 0) {
					$query = "	UPDATE tilausrivi SET
								kpl = '$uusikpl'
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$kukarow[kesken]'
								AND tunnus = '$qrow[tunnus]'";
					$updres = pupe_query($query);

					$fields = mysql_field_name($qres,0);
					$values = "'".$qrow[0]."'";

					for($i=1; $i < mysql_num_fields($qres)-1; $i++) { // Ei monisteta tunnusta

						$fields .= ", ".mysql_field_name($qres,$i);

						switch (mysql_field_name($qres,$i)) {
							case 'laadittu':
								$values .= ", now()";
								break;
							case 'tunnus':
								$values .= ", ''";
								break;
							case 'laatija':
								$values .= ", '$kukarow[kuka]'";
								break;
							case 'kpl':
							case 'tilkpl':
							case 'varattu':
								$values .= ", '$erotus'";
								break;
							default:
								$values .= ", '".$qrow[$i]."'";
						}
					}

					$kysely  = "INSERT into tilausrivi ($fields) VALUES ($values)";
					$insres  = pupe_query($kysely);
					$utunnus = mysql_insert_id($link);

					if ($uuden_perheen_perheid == 0) {
						$uuden_perheen_perheid = $utunnus;
					}

					$kopsatut_rivit[] = $utunnus;
				}
			}
			if (count($kopsatut_rivit) > 1) {
				foreach($kopsatut_rivit as $uusitunnu) {
					$query = "	UPDATE tilausrivi SET
								perheid = '$uuden_perheen_perheid'
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$kukarow[kesken]'
								AND tunnus = '$uusitunnu'";
					$updres = pupe_query($query);
				}
			}
		}

		$query = "	UPDATE tilausrivi SET
					var = '$myyntitilirivit'
					WHERE yhtio = '$kukarow[yhtio]'
					AND otunnus = '$kukarow[kesken]'
					AND (tunnus = '$rivitunnus' or perheid='$rivitunnus')";
		$updres = pupe_query($query);

		$tila 		= "";
		$tapa 		= "";
		$rivitunnus = "";
		$tilatapa	= "";
	}

	if ($tilatapa == "LASKUTA") {
		$query = "	UPDATE tilausrivi
					SET varattu = kpl
					WHERE yhtio = '$kukarow[yhtio]'
					AND otunnus = '$kukarow[kesken]'
					AND var = 'A'
					AND kpl != 0";
		$result = pupe_query($query);

		$query	= "	SELECT tunnus
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					AND alkuhyllyalue = '!!M'
					AND loppuhyllyalue = '!!M'";
		$result = pupe_query($query);
		$row = mysql_fetch_array($result);

		$syot_varasto 	= $row["tunnus"];
		$syot_var 		= "H";
		$tilrivilisa	= " and tilausrivi.var = 'A' and tilausrivi.kpl != 0 ";

		$tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($kukarow["kesken"], $tilrivilisa, $syot_varasto, $syot_var, "", "KOPIOIDAANPAIKAT");

		echo "$tilauksesta_myyntitilaus<br><br>";

		//Laitetaan myyntitilin kappalem‰‰ri‰ kuntoon jotta niit‰ ei voisi laskuttaa vahingossa monta kertaa
		$query = "	UPDATE tilausrivi
					SET varattu = 0,
					kpl = 0
					WHERE yhtio = '$kukarow[yhtio]'
					AND otunnus = '$kukarow[kesken]'
					AND var = 'A'
					AND kpl != 0";
		$result = pupe_query($query);

		$tila 		= "";
		$tapa 		= "";
		$rivitunnus = "";
		$tilatapa	= "";

		echo "<font class='message'>".t("Valitut myyntitilirivit siirretty laskutukseen")."!</font><br><br>";

		$tilatapa = "LEPAA";
	}

	if ($tilatapa == "PALAUTA") {

		// Palautetaan myyntitilin rivit tuotteen oletuspaikalle
		$query = "	SELECT *
					FROM tilausrivi
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND otunnus = '{$kukarow["kesken"]}'
					AND var = 'B'
					AND kpl != 0";
		$palauta_result = pupe_query($query);

		while ($palauta_row = mysql_fetch_assoc($palauta_result)) {

			// $tuoteno = tuotenumero jota siirret‰‰n
			// $asaldo  = siirrett‰v‰ m‰‰r‰
			// $mista   = tuotepaikan tunnus josta otetaan
			// $minne   = tuotepaikan tunnus jonne siirret‰‰n

			$tuoteno = $palauta_row["tuoteno"];
			$asaldo = $palauta_row["kpl"];

			// Haetaan l‰hde-varastopaikka
			$query = "	SELECT tunnus, saldo
						FROM tuotepaikat
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND tuoteno = '{$palauta_row["tuoteno"]}'
						AND hyllyalue = '{$palauta_row["hyllyalue"]}'
						AND hyllynro = '{$palauta_row["hyllynro"]}'
						AND hyllyvali = '{$palauta_row["hyllyvali"]}'
						AND hyllytaso = '{$palauta_row["hyllytaso"]}'";
			$tuotepaikat_result = pupe_query($query);

			if (mysql_num_rows($tuotepaikat_result) != 1) {
				echo "<font class='error'>$tuoteno: Virheellinen l‰hett‰v‰ varastopaikka</font><br>";
				continue;
			}

			$tuotepaikat_row = mysql_fetch_assoc($tuotepaikat_result);
			$tuotepaikan_saldo = $tuotepaikat_row["saldo"] - $asaldo;
			$mista = $tuotepaikat_row["tunnus"];

			// Haetaan kohde-varastopaikka (oletus)
			$query = "	SELECT tunnus
						FROM tuotepaikat
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND tuoteno = '{$palauta_row["tuoteno"]}'
						AND hyllyalue != '!!M'
						AND oletus != ''";
			$tuotepaikat_result = pupe_query($query);

			if (mysql_num_rows($tuotepaikat_result) != 1) {
				echo "<font class='error'>$tuoteno: Virheellinen vastaanottava varastopaikka</font><br>";
				continue;
			}

			$tuotepaikat_row = mysql_fetch_assoc($tuotepaikat_result);
			$minne = $tuotepaikat_row["tunnus"];

			$tee = "N";
			$kutsuja = "vastaanota.php";

			require("muuvarastopaikka.php");

			// Jos siirto onnistui
			if ($tee == "OK") {
				// P‰ivitet‰‰n siirretty rivi k‰sitellyksi
				$query = "	UPDATE tilausrivi SET
							varattu = 0,
							kpl = 0
							WHERE yhtio = '{$kukarow["yhtio"]}'
							AND tunnus = {$palauta_row["tunnus"]}";
				$tilausrivi_result = pupe_query($query);

				// Poistetaan myyntitilin varastopaikka jos se meni tyhj‰ksi
				if ($tuotepaikan_saldo == 0) {
					$tee = "MUUTA";
					$poista = array($mista);
					$halyraja2 = array();
					$tilausmaara2 = array();					
					$kutsuja = "vastaanota.php";
					require("muuvarastopaikka.php");
				}
			}
		}

		$tila 		= "";
		$tapa 		= "";
		$rivitunnus = "";
		$tilatapa = "LEPAA";

		echo "<font class='message'>".t("Valitut rivit siirret‰‰n omaan varastoon")."! $uusi_siirtolista</font><br><br>";
	}

	if ($tilatapa == "LEPAA") {
		// poistetaan aktiiviset tilaukset jota t‰ll‰ k‰ytt‰j‰ll‰ oli
		$query = "	UPDATE kuka SET
					kesken = ''
					WHERE yhtio = '$kukarow[yhtio]'
					AND kuka = '$kukarow[kuka]'";
		$result = pupe_query($query);

		$tee				= '';
		$tilausnumero		= '';
		$laskurow			= '';
		$kukarow['kesken']	= '';
	}

?>