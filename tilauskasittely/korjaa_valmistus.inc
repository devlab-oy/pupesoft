<?php

	//	Kaikille sarjanumeroille tehd‰‰n varastoon saavutettu O-rivi, muuten mm ker‰ys ei toimi ja ehk‰ se on myˆs loogisempaa? (Muille tuotteille D-rivi)
	if ($tilrivirow["sarjanumeroseuranta"] != "") {
		$tilausriviTyyppi = "O";
	}
	else {
		$tilausriviTyyppi = "D";
	}

	//k‰ytet‰‰n tilausriveill‰ olevia tuotteita
	$query = "	SELECT tilausrivi.*,
				if(tilausrivi.varattu!=0, tilausrivi.varattu, tilausrivi.kpl) varattu,
				tuote.ei_saldoa, tuote.kehahin, tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm,
				tuote.sarjanumeroseuranta
				FROM tilausrivi
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				AND tilausrivi.otunnus = '$tilrivirow[otunnus]'
				AND tilausrivi.perheid = '$tilrivirow[perheid]'
				AND tilausrivi.tyyppi  = 'V'
				ORDER BY tilausrivi.tuoteno";
	$raakaaineres = pupe_query($query);

	$uusiarvo = 0;

	while ($raakaainerow = mysql_fetch_assoc($raakaaineres)) {

		// Perutaan t‰m‰ raaka-ainerivi kokonaan
		if ($perutaan == "JOO") {
			$raakaainerow["varattu"] = 0;
		}

		// Katsotaan mik‰ tuotteen keskihankintahinta oli valmistusta tehdess‰
		$query = "	SELECT *
					FROM tapahtuma
					WHERE yhtio 	= '$kukarow[yhtio]'
					and laji 		= 'kulutus'
					and rivitunnus	= '$raakaainerow[tunnus]'
					LIMIT 1";
		$tuoteresult = pupe_query($query);

		if (mysql_num_rows($tuoteresult) == 0) {
			// Raaka-aine list‰tty j‰lkik‰teen tehd‰‰n tapahtuma
			if 	  ($raakaainerow['epakurantti100pvm'] != '0000-00-00') $raakaainerow['kehahin'] = 0;
			elseif ($raakaainerow['epakurantti75pvm'] != '0000-00-00') $raakaainerow['kehahin'] = round($raakaainerow['kehahin'] * 0.25, 6);
			elseif ($raakaainerow['epakurantti50pvm'] != '0000-00-00') $raakaainerow['kehahin'] = round($raakaainerow['kehahin'] * 0.5,  6);
			elseif ($raakaainerow['epakurantti25pvm'] != '0000-00-00') $raakaainerow['kehahin'] = round($raakaainerow['kehahin'] * 0.75, 6);

			// Kpl nolla koska homma korjataan $kaantopisteen_saldomuutos-homman kautta
			$query = "	INSERT into tapahtuma set
						yhtio    	= '$kukarow[yhtio]',
						tuoteno  	= '$raakaainerow[tuoteno]',
						laji     	= 'kulutus',
						kpl      	=  0,
						kplhinta 	= '$raakaainerow[kehahin]',
						hinta    	= '$raakaainerow[kehahin]',
						rivitunnus	= '$raakaainerow[tunnus]',
						hyllyalue	= '$raakaainerow[hyllyalue]',
						hyllynro	= '$raakaainerow[hyllynro]',
						hyllyvali	= '$raakaainerow[hyllyvali]',
						hyllytaso	= '$raakaainerow[hyllytaso]',
						selite   	= '".t("K‰ytettiin tuotteen")." $tilrivirow[tuoteno] ".t("valmistamiseen")." ".t("tyˆm‰‰r‰yksell‰")." $tilrivirow[otunnus]',
						laatija  	= '$kukarow[kuka]',
						laadittu 	= now()";
			$tapahtumaresult = pupe_query($query);
			$tapahtumaid = mysql_insert_id();

			$query = "	SELECT *
						FROM tapahtuma
						WHERE yhtio = '$kukarow[yhtio]'
						and laji 	= 'kulutus'
						and tunnus	= '$tapahtumaid'";
			$tuoteresult = pupe_query($query);
		}

		$tuoterow = mysql_fetch_assoc($tuoteresult);

		$arvo = $tuoterow['hinta'] * $raakaainerow['varattu'];
		$uusiarvo += $arvo;

		//Tutkitan onko raaka-aineiden k‰yttˆm‰‰ri‰ muutettu
		if ($raakaainerow["kpl"] != $raakaainerow["varattu"]) {
			echo "<br><font class='message'>Raaka aineen $raakaainerow[tuoteno] kulutusta muutettu! $raakaainerow[kpl] --> $raakaainerow[varattu]. Teemme j‰lkilaskennan raaka-aineelle.</font><br>";

			//Suoritetaan j‰lkilaskenta
			$query = "	SELECT *
						FROM tapahtuma
						WHERE yhtio 	= '$kukarow[yhtio]'
						and laji 		= 'kulutus'
						and rivitunnus 	= '$raakaainerow[tunnus]'
						and tuoteno 	= '$raakaainerow[tuoteno]'
						ORDER BY tunnus";
			$res = pupe_query($query);
			$osavalm_lukumaara = mysql_num_rows($res);

			//Lasketaan k‰‰ntˆpisteen saldomuutos joka johtuu raaka-aineen kulutuksen muutoksesta
			$kaantopisteen_saldomuutos = round(($raakaainerow["kpl"] - $raakaainerow["varattu"]) / $osavalm_lukumaara, 2);

			while ($taparow = mysql_fetch_assoc($res)) {

				//Etsit‰‰n k‰‰ntˆpiste
				$query = "	SELECT *
							FROM tapahtuma
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$taparow[tuoteno]'
							and laji in ('tulo','valmistus')
							and laadittu >= '$taparow[laadittu]'
							LIMIT 1";
				$kaantopisteres = pupe_query($query);

				if (mysql_num_rows($kaantopisteres) > 0) {
					$kaantopisterow = mysql_fetch_assoc($kaantopisteres);

					$uusihinta 		 = $kaantopisterow["kplhinta"];
					$tuoteno		 = $kaantopisterow["tuoteno"];
					$pvm			 = $kaantopisterow["laadittu"];
					$rivitunnus 	 = $kaantopisterow["rivitunnus"];
					$tapahtumatunnus = $kaantopisterow["tunnus"];

					echo "<font class='message'>Korjataan raaka-aineen $tuoteno tapahtumat $pvm l‰htien. Raaka-aineen kulutusmuutos: $kaantopisteen_saldomuutos.</font><br>";

					jalkilaskentafunktio($tuoteno, $pvm, $uusihinta, $rivitunnus, $tapahtumatunnus, $kaantopisteen_saldomuutos);
				}

				//P‰ivitet‰‰n myˆs tapahtuman kappalem‰‰r‰
				$query = "	UPDATE tapahtuma
							SET kpl = kpl+round($kaantopisteen_saldomuutos,2)
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus 	= '$taparow[tunnus]'";
				$updresult = pupe_query($query);
			}

			// P‰ivitet‰‰n saldo oikeaksi vasta j‰lkilaskennan j‰lkeen kun valmistusmuutos otetaan huomioon
			// Pistet‰‰n saldo aina oletuspaikalle
			$mtos = $raakaainerow["kpl"] - $raakaainerow["varattu"];

			if ($raakaainerow["ei_saldoa"] == "") {
				$query = "	UPDATE tuotepaikat
							SET saldo = saldo+$mtos,
							saldoaika = now()
							WHERE yhtio   = '$kukarow[yhtio]'
							and tuoteno   = '$raakaainerow[tuoteno]'
							and hyllyalue = '$raakaainerow[hyllyalue]'
							and hyllynro  = '$raakaainerow[hyllynro]'
							and hyllytaso = '$raakaainerow[hyllytaso]'
							and hyllyvali = '$raakaainerow[hyllyvali]'
							LIMIT 1";
				$result = pupe_query($query);

				if (mysql_affected_rows() == 0) {
					$query = "	UPDATE tuotepaikat
								SET saldo = saldo+$mtos,
								saldoaika = now()
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$raakaainerow[tuoteno]'
								and oletus != ''
								LIMIT 1";
					$result = pupe_query($query);

					if (mysql_affected_rows() == 0) {
						$query = "	UPDATE tuotepaikat
									SET saldo = saldo+$mtos,
									saldoaika = now()
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$raakaainerow[tuoteno]'
									LIMIT 1";
						$result = pupe_query($query);
					}
				}
			}

			if ($perutaan == "JOO") {
				if (in_array($raakaainerow["sarjanumeroseuranta"], array("E","F","G"))) {
					$query = "	UPDATE sarjanumeroseuranta
								SET myyntirivitunnus = 0,
								muutospvm			 = now(),
								muuttaja			 = '$kukarow[kuka]'
								WHERE yhtio 		 = '$kukarow[yhtio]'
								and tuoteno			 = '$raakaainerow[tuoteno]'
								and myyntirivitunnus = '$raakaainerow[tunnus]'";
					$result = pupe_query($query);
				}

				$query = "	UPDATE tilausrivi
							SET varattu = kpl, kpl = 0, toimitettuaika = '0000-00-00 00:00:00'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$raakaainerow[tunnus]'";
			}
			else {
				if (in_array($raakaainerow["sarjanumeroseuranta"], array("E","F","G"))) {
					// P‰vitet‰‰n raaka-aineen er‰numeron era_kpl-kentt‰‰n oikea luku
					$query = "	UPDATE sarjanumeroseuranta
								SET era_kpl = era_kpl+$mtos,
								muutospvm	= now(),
								muuttaja	= '$kukarow[kuka]'
								WHERE yhtio 		 = '$kukarow[yhtio]'
								and tuoteno			 = '$raakaainerow[tuoteno]'
								and myyntirivitunnus = '$raakaainerow[tunnus]'
								LIMIT 1";
					$result = pupe_query($query);

					$query = "	SELECT *
					 			FROM sarjanumeroseuranta
								WHERE yhtio 		 = '$kukarow[yhtio]'
								and tuoteno			 = '$raakaainerow[tuoteno]'
								and myyntirivitunnus = '$raakaainerow[tunnus]'
								LIMIT 1";
					$result = pupe_query($query);

					if ($erarow = mysql_fetch_assoc($result)) {
						// Ja palautetaan loput sinne mist‰ ne oli otettu
						$query = "	INSERT into sarjanumeroseuranta
									SET yhtio 		 = '$kukarow[yhtio]',
									tuoteno			 = '$erarow[tuoteno]',
									myyntirivitunnus = 0,
									ostorivitunnus 	 = $erarow[tunnus],
									era_kpl			 = $mtos*-1,
									laatija			 = '$kukarow[kuka]',
									luontiaika		 = now(),
									hyllyalue   	 = '$erarow[hyllyalue]',
									hyllynro    	 = '$erarow[hyllynro]',
									hyllyvali   	 = '$erarow[hyllyvali]',
									hyllytaso   	 = '$erarow[hyllytaso]',
									sarjanumero 	 = '$erarow[sarjanumero]',
									lisatieto		 = '$erarow[lisatieto]'";
						$result = pupe_query($query);
					}
				}

				$query = "	UPDATE tilausrivi
							SET kpl = $raakaainerow[varattu], varattu=0
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$raakaainerow[tunnus]'";
			}

			$updresult = pupe_query($query);
		}
	}

	if ($perutaan == "JOO") {

		// P‰ivitet‰‰n myˆs valmisteen tapahtuman kappalem‰‰r‰
		$query = "	UPDATE tapahtuma
					SET kpl	= 0,
					selite  = concat(selite,' Valmistus peruttu')
					WHERE yhtio 	= '$kukarow[yhtio]'
					and laji 		= 'valmistus'
					and rivitunnus 	= '$tilrivirow[tunnus]'";
		$updresult = pupe_query($query);

		// Haetaan valmisteen tapahtuma
		$query = "	SELECT laadittu
					FROM tapahtuma
					WHERE yhtio 	= '$kukarow[yhtio]'
					and laji 		= 'valmistus'
					and rivitunnus	= '$tilrivirow[tunnus]'
					LIMIT 1";
		$tapares = pupe_query($query);
		$taparow = mysql_fetch_assoc($tapares);

		// Haetaan viimeisin kehahin ennen peruttua valmistusta
		$query = "	SELECT hinta
					FROM tapahtuma
					WHERE yhtio     = '$kukarow[yhtio]'
					and tuoteno     = '$tilrivirow[tuoteno]'
					and hinta      != 0
					and rivitunnus != '$tilrivirow[tunnus]'
					and laadittu    < '$taparow[laadittu]'
					LIMIT 1";
		$edkehares = pupe_query($query);

		// Palautetaan valmisteen kehahin niinku se oli ennen t‰t‰ valmistusta
		if ($edkeharow = mysql_fetch_assoc($edkehares)) {
			$query = "	UPDATE tuote
						SET kehahin = $edkeharow[hinta],
						vihahin = $edkeharow[hinta],
						vihapvm = now()
						WHERE yhtio	= '$kukarow[yhtio]'
						and tuoteno	= '$tilrivirow[tuoteno]'";
			$updresult = pupe_query($query);
		}

		if ($tilrivirow["ei_saldoa"] == "") {
			$query = "	UPDATE tuotepaikat
						SET saldo = saldo-$valmkpl,
						saldoaika = now()
						WHERE yhtio   = '$kukarow[yhtio]'
						and tuoteno   = '$tilrivirow[tuoteno]'
						and hyllyalue = '$tilrivirow[hyllyalue]'
						and hyllynro  = '$tilrivirow[hyllynro]'
						and hyllytaso = '$tilrivirow[hyllytaso]'
						and hyllyvali = '$tilrivirow[hyllyvali]'
						LIMIT 1";
			$result = pupe_query($query);

			if (mysql_affected_rows() == 0) {
				$query = "	UPDATE tuotepaikat
							SET saldo = saldo-$valmkpl,
							saldoaika = now()
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tilrivirow[tuoteno]'
							and oletus != ''
							LIMIT 1";
				$result = pupe_query($query);

				if (mysql_affected_rows() == 0) {
					$query = "	UPDATE tuotepaikat
								SET saldo = saldo-$valmkpl,
								saldoaika = now()
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$tilrivirow[tuoteno]'
								LIMIT 1";
					$result = pupe_query($query);
				}
			}
		}

		if (in_array($tilrivirow["sarjanumeroseuranta"], array("E","F","G"))) {
			// Valmisteen er‰numerot on siirretty valmistetulle tilausriville, eli ne jotka on tyypill‰ "O"
			$query = "	SELECT tunnus
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tilrivirow[otunnus]'
						and perheid = '$tilrivirow[perheid]'
						and tuoteno = '$tilrivirow[tuoteno]'
						and tyyppi = '{$tilausriviTyyppi}'";
			$resvalm = pupe_query($query);

			while ($rowvalm = mysql_fetch_assoc($resvalm)) {
				$query = "	UPDATE sarjanumeroseuranta
							SET era_kpl	= 0,
							muutospvm	= now(),
							muuttaja	= '$kukarow[kuka]'
							WHERE yhtio 		 = '$kukarow[yhtio]'
							and tuoteno			 = '$tilrivirow[tuoteno]'
							and ostorivitunnus 	 = '$rowvalm[tunnus]'
							and myyntirivitunnus = 0";
				$result = pupe_query($query);
			}
		}

		echo "<font class='message'>".t("Valmisteen valmistusmuutos").": $valmkpl.</font><br>";

		$query = "	SELECT sum(kpl) valmistetut
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilrivirow[otunnus]'
					and perheid = '$tilrivirow[perheid]'
					and tuoteno = '$tilrivirow[tuoteno]'
					and tyyppi = '{$tilausriviTyyppi}'";
		$resisa = pupe_query($query);
		$rowisa = mysql_fetch_assoc($resisa);

		$query = "	UPDATE tilausrivi
					SET varattu = '$rowisa[valmistetut]', kpl = 0, toimitettuaika='0000-00-00 00:00:00'
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilrivirow[otunnus]'
					and tunnus  = '$tilrivirow[tunnus]'";
		$updresult = pupe_query($query);

		$query = "	UPDATE tilausrivi
					SET kpl=0, varattu=0
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilrivirow[otunnus]'
					and perheid = '$tilrivirow[perheid]'
					and tuoteno = '$tilrivirow[tuoteno]'
					and tyyppi  = '{$tilausriviTyyppi}'";
		$updresult = pupe_query($query);
	}
	else {

		// Katsotaan onko useita valmisteita ja tallennetaan arvot muuttujaan niin kuin ne oli!
		if (!isset($kaikenkaikkiaan_arvot_silloin[$tilrivirow["perheid"]])) {
			$query = "	SELECT tapahtuma.tuoteno, sum(tapahtuma.kplhinta*tapahtuma.kpl) summa
						FROM tilausrivi
						JOIN tapahtuma ON tapahtuma.yhtio = tilausrivi.yhtio and tapahtuma.laji='valmistus' and tapahtuma.tuoteno = tilausrivi.tuoteno and tapahtuma.rivitunnus = tilausrivi.tunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus = '$tilrivirow[otunnus]'
						and tilausrivi.perheid = '$tilrivirow[perheid]'
						and tilausrivi.tyyppi in ('W','M')
						GROUP BY tapahtuma.tuoteno";
			$uvresult = pupe_query($query);

			if (mysql_num_rows($uvresult) > 0) {
				while ($valmisteet = mysql_fetch_assoc($uvresult)) {
					$kaikenkaikkiaan_arvot_silloin[$tilrivirow["perheid"]][$valmisteet["tuoteno"]] = $valmisteet["summa"];
					$kaikenkaikkiaan_arvot_silloin_yht[$tilrivirow["perheid"]] += $valmisteet["summa"];
				}
			}
		}

		if (isset($kaikenkaikkiaan_arvot_silloin[$tilrivirow["perheid"]]) and isset($kaikenkaikkiaan_arvot_silloin_yht[$tilrivirow["perheid"]]) and $kaikenkaikkiaan_arvot_silloin_yht[$tilrivirow["perheid"]] > 0) {
			$kaikenkaikkiaan_arvot = $kaikenkaikkiaan_arvot_silloin[$tilrivirow["perheid"]][$tilrivirow["tuoteno"]] / $kaikenkaikkiaan_arvot_silloin_yht[$tilrivirow["perheid"]];
		}
		else {
			$kaikenkaikkiaan_arvot = 1;
		}

		// Suoritetaan j‰lkilaskenta valmisteelle
		$query = "	SELECT sum(kpl) valmistetut
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilrivirow[otunnus]'
					and perheid = '$tilrivirow[perheid]'
					and tuoteno = '$tilrivirow[tuoteno]'
					and tyyppi  = '{$tilausriviTyyppi}'";
		$resisa = pupe_query($query);
		$rowisa = mysql_fetch_assoc($resisa);

		$query = "	SELECT *
					from tapahtuma
					where yhtio = '$kukarow[yhtio]'
					and laji = 'valmistus'
					and rivitunnus = '$tilrivirow[tunnus]'
					order by tunnus";
		$res = pupe_query($query);

		while ($taparow = mysql_fetch_assoc($res)) {

			$uusihinta 		 = round(($uusiarvo*$kaikenkaikkiaan_arvot) / $valmkpl, 6);
			$tuoteno		 = $taparow["tuoteno"];
			$pvm			 = $taparow["laadittu"];
			$rivitunnus 	 = $taparow["rivitunnus"];
			$tapahtumatunnus = $taparow["tunnus"];

			// Lasketaan k‰‰ntˆpisteen saldomuutos
			$kaantopisteen_saldomuutos = round(($taparow["kpl"] * ($valmkpl/$rowisa["valmistetut"]))-$taparow["kpl"], 2);

			echo "<font class='message'>Korjataan tuotteen $tuoteno tapahtumat $pvm ($rivitunnus) l‰htien. Uusi ostohinta $uusihinta. ";

			if ($kaantopisteen_saldomuutos != 0) echo "Valmisteen kappalemuutos: $kaantopisteen_saldomuutos.";

			echo "</font><br>";

			jalkilaskentafunktio($tuoteno, $pvm, $uusihinta, $rivitunnus, $tapahtumatunnus, $kaantopisteen_saldomuutos);

			if ($kaantopisteen_saldomuutos != 0) {
				//P‰ivitet‰‰n myˆs tapahtuman kappalem‰‰r‰
				$query = "	UPDATE tapahtuma
							SET kpl	= kpl+$kaantopisteen_saldomuutos,
							selite  = concat(selite,'\nValmistus korjattu. Valmisteen kappalemuutos: $kaantopisteen_saldomuutos.')
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus 	= '$taparow[tunnus]'";
				$updresult = pupe_query($query);

				if ($taparow["ei_saldoa"] == "") {
					$query = "	UPDATE tuotepaikat
								SET saldo = saldo+$kaantopisteen_saldomuutos,
								saldoaika = now()
								WHERE yhtio   = '$kukarow[yhtio]'
								and tuoteno   = '$taparow[tuoteno]'
								and hyllyalue = '$taparow[hyllyalue]'
								and hyllynro  = '$taparow[hyllynro]'
								and hyllytaso = '$taparow[hyllytaso]'
								and hyllyvali = '$taparow[hyllyvali]'
								LIMIT 1";
					$result = pupe_query($query);

					if (mysql_affected_rows() == 0) {
						$query = "	UPDATE tuotepaikat
									SET saldo = saldo+$kaantopisteen_saldomuutos,
									saldoaika = now()
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$taparow[tuoteno]'
									and oletus != ''
									LIMIT 1";
						$result = pupe_query($query);

						if (mysql_affected_rows() == 0) {
							$query = "	UPDATE tuotepaikat
										SET saldo = saldo+$kaantopisteen_saldomuutos,
										saldoaika = now()
										WHERE yhtio = '$kukarow[yhtio]'
										AND tuoteno = '$taparow[tuoteno]'
										LIMIT 1";
							$result = pupe_query($query);
						}
					}

					if (in_array($tilrivirow["sarjanumeroseuranta"], array("E","F","G"))) {
						// P‰vitet‰‰n valmisteen er‰numeron era_kpl-kentt‰‰n oikea luku
						$query = "	UPDATE sarjanumeroseuranta
									SET era_kpl = era_kpl+$kaantopisteen_saldomuutos,
									muutospvm	= now(),
									muuttaja	= '$kukarow[kuka]'
									WHERE yhtio 		 = '$kukarow[yhtio]'
									and tuoteno			 = '$raakaainerow[tuoteno]'
									and myyntirivitunnus = '$raakaainerow[tunnus]'
									LIMIT 1";
						$result = pupe_query($query);
					}
				}
			}
		}

		if ($valmkpl != $rowisa["valmistetut"]) {
			$query = "	UPDATE tilausrivi
						SET varattu = 0
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tilrivirow[otunnus]'
						and tunnus  = '$tilrivirow[tunnus]'";
			$updresult = pupe_query($query);

			$query = "	UPDATE tilausrivi
						SET kpl=round(kpl*($valmkpl/$rowisa[valmistetut]),2), varattu=kpl
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tilrivirow[otunnus]'
						and perheid = '$tilrivirow[tunnus]'
						and tyyppi  = '{$tilausriviTyyppi}'";
			$updresult = pupe_query($query);
		}
	}
?>