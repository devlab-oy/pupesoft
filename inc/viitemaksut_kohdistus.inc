<?php

	######################################
	#
	# automaattikohdistetaan viitemaksut
	#
	######################################

	echo "<font class='message'>".t("Automaattikohdistetaan viitemaksuja")."</font><br>";

	$query = "	SELECT
				suoritus.tunnus suoritunnus,
				suoritus.yhtio suoriyhtio,
				suoritus.kirjpvm kirjpvm,
				suoritus.summa suoritettu,
				suoritus.kurssi suorituskurssi,
				suoritus.tilino stilino,
				suoritus.valkoodi suoritusvaluutta,
				lasku.summa-lasku.saldo_maksettu laskutettu,
				lasku.summa_valuutassa-lasku.saldo_maksettu_valuutassa laskutettu_valuutassa,
				lasku.tunnus laskutunnus,
				lasku.tapvm tapvm,
				lasku.summa laskusumma,
				lasku.summa_valuutassa laskusumma_valuutassa,
				lasku.kapvm kapvm,
				lasku.saldo_maksettu saldo_maksettu,
				lasku.saldo_maksettu_valuutassa saldo_maksettu_valuutassa,
				lasku.pyoristys pyoristys,
				lasku.pyoristys_valuutassa pyoristys_valuutassa,
				lasku.kasumma kasumma,
				lasku.kasumma_valuutassa kasumma_valuutassa,
				lasku.valkoodi laskuvaluutta,
				lasku.vienti_kurssi laskukurssi,
				yhtio.myynninkassaale alennustili,
				yhtio.myyntisaamiset myyntisaamiset,
				yhtio.konsernimyyntisaamiset konsernimyyntisaamiset,
				yhtio.factoringsaamiset factoringsaamiset,
				yhtio.alv alvtili,
				yhtio.varasto varasto,
				yhtio.varastonmuutos varastomuu,
				yhtio.pyoristys pyoristys,
				yhtio.valkoodi yhtiovaluutta,
				yhtio.myynninvaluuttaero valuuttaerotili,
				yhtio.selvittelytili,
				yriti.factoring factoring,
				yriti.oletus_rahatili kassatili,
				yriti.oletus_selvittelytili,
				factoring.pankki_tili ftilino,
				(select count(*) from tiliointi WHERE tiliointi.yhtio = suoritus.yhtio AND tiliointi.ltunnus = lasku.tunnus AND tiliointi.tilino = IF(yriti.factoring != '', yhtio.factoringsaamiset, yhtio.myyntisaamiset)) saamisetkpl
				FROM yhtio
				JOIN lasku use index (yhtio_tila_mapvm) ON (lasku.yhtio = yhtio.yhtio and lasku.tila = 'U' and lasku.alatila = 'X' and lasku.mapvm = '0000-00-00')
				JOIN yriti ON (yriti.yhtio = yhtio.yhtio)
				JOIN suoritus use index (yhtio_viite) ON (suoritus.yhtio = lasku.yhtio and suoritus.viite = lasku.viite and suoritus.valkoodi=lasku.valkoodi and suoritus.tilino = yriti.tilino and suoritus.kohdpvm = '0000-00-00' and suoritus.ltunnus=0)
				JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio and maksuehto.tunnus = lasku.maksuehto)
				LEFT JOIN factoring ON (factoring.yhtio = lasku.yhtio and factoring.valkoodi = lasku.valkoodi and factoring.factoringyhtio = maksuehto.factoring)
				HAVING saamisetkpl > 0 and (
				(laskuvaluutta  = yhtiovaluutta and (((kapvm >= adddate(kirjpvm,-4) and abs(laskusumma-saldo_maksettu-kasumma-suoritettu-lasku.pyoristys) 					 							< 0.01)) or (abs(laskusumma-saldo_maksettu-suoritettu-lasku.pyoristys) 						  			< 0.01))) or
				(laskuvaluutta != yhtiovaluutta and (((kapvm >= adddate(kirjpvm,-4) and abs(laskusumma_valuutassa-saldo_maksettu_valuutassa-kasumma_valuutassa-suoritettu-lasku.pyoristys_valuutassa)	< 0.01)) or (abs(laskusumma_valuutassa-saldo_maksettu_valuutassa-suoritettu-lasku.pyoristys_valuutassa)	< 0.01)))
				)";
	$suorires = mysql_query($query) or pupe_error($query);

	while ($row = mysql_fetch_array($suorires)) {

		if (strtoupper($row["suoritusvaluutta"]) != strtoupper($row['yhtiovaluutta'])) {
			$alennus = round(($row["laskutettu_valuutassa"] - $row["suoritettu"]) * $row["suorituskurssi"], 2);
			$alennus_valuutassa = round($row["laskutettu_valuutassa"] - $row["suoritettu"], 2);
		}
		else {
			$alennus = round($row["laskutettu"] - $row["suoritettu"], 2);
			$alennus_valuutassa = round($row["laskutettu"] - $row["suoritettu"], 2);
		}

		#Katsotaan ensin, ettei t‰t‰ laskua ole jo suoritettu/maksettu
		#Eli keissi jossa asiakas maksaa saman laskun kahteen kertaan samassa viiteaineistossa
		$query = "	SELECT tunnus, liitostunnus, maksuehto
					FROM lasku
					WHERE tunnus = '$row[laskutunnus]'
					and mapvm = '0000-00-00'";
		$lasresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($lasresult) == 1 and ($row["ftilino"] == $row["stilino"] or $row["ftilino"] == "")) {
			$malaskurow = mysql_fetch_array($lasresult);

			#Etsit‰‰n asiakas, jos se olisi konsernin j‰sen
			$query = "	SELECT konserniyhtio
						FROM asiakas
						WHERE tunnus = '$malaskurow[liitostunnus]'
						and yhtio    = '$row[suoriyhtio]'
						and konserniyhtio != ''";
			$asresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($asresult) > 0) {
				$row["myyntisaamiset"] = $row["konsernimyyntisaamiset"];
			}

			# Onko t‰m‰ sittenkin factoringia
			if ($row["factoring"] != "") {
				$row["myyntisaamiset"] = $row["factoringsaamiset"];
			}

			# Myyntisaamiset
			$query = "INSERT INTO tiliointi (yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, summa_valuutassa, valkoodi, selite) values ('$row[suoriyhtio]', 'automaattikohdistus', now(), '$row[kirjpvm]', '$row[laskutunnus]', '$row[myyntisaamiset]', -$row[laskutettu], -$row[laskutettu_valuutassa], '$row[laskuvaluutta]', 'Automaattikohdistettu asiakkaan suoritus')";
			$insres = mysql_query($query) or pupe_error($query);

			if (strtoupper($row["suoritusvaluutta"]) != strtoupper($row['yhtiovaluutta'])) {
				$suoritettu_kassaan = round($row["suoritettu"] * $row["suorituskurssi"], 2);
				$suoritettu_kassaan_valuutassa = $row["suoritettu"];
			}
			else {
				$suoritettu_kassaan = $row["suoritettu"];
				$suoritettu_kassaan_valuutassa = $row["suoritettu"];
			}

			# Kassatili
			$query = "INSERT INTO tiliointi (yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, summa_valuutassa, valkoodi, selite) values ('$row[suoriyhtio]', 'automaattikohdistus', now(), '$row[kirjpvm]', '$row[laskutunnus]', '$row[kassatili]', $suoritettu_kassaan, '$suoritettu_kassaan_valuutassa', '$row[suoritusvaluutta]', 'Automaattikohdistettu asiakkaan suoritus')";
			$insres = mysql_query($query) or pupe_error($query);

			// Mahdollinen kassa-alennus
			if ($alennus != 0) {
				// Kassa-alessa on huomioitava alv, joka voi olla useita vientej‰
				$totkasumma = 0;
				$totkasumma_valuutassa = 0;

				#Etsit‰‰n myynti-tiliˆinnit
				$query = "	SELECT summa, vero, kustp, kohde, projekti, summa_valuutassa, valkoodi
							FROM tiliointi use index (tositerivit_index)
							WHERE ltunnus 	 = '$row[laskutunnus]'
							and yhtio   	 = '$row[suoriyhtio]'
							and tapvm   	 = '$row[tapvm]'
							and abs(summa)  <> 0
							and tilino 		<> '$row[myyntisaamiset]'
							and tilino	    <> '$row[konsernimyyntisaamiset]'
							and tilino 		<> '$row[alvtili]'
							and tilino 		<> '$row[varasto]'
							and tilino 		<> '$row[varastomuu]'
							and tilino 		<> '$row[pyoristys]'
							and tilino 		<> '$row[alennustili]'
							and tilino		<> '$row[factoringsaamiset]'
							and korjattu	 = ''";
				$tilres = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($tilres) == 0) {
					$query = "	INSERT INTO tiliointi
								SET yhtio			= '$row[suoriyhtio]',
								laatija 			= 'automaattikohdistus',
								laadittu 			= now(),
								tapvm 				= '$row[kirjpvm]',
								ltunnus 			= '$row[laskutunnus]',
								tilino 				= '$row[alennustili]',
								summa 				= '$alennus',
								summa_valuutassa	= '$alennus_valuutassa',
								valkoodi			= '$row[suoritusvaluutta]',
								vero				= '0',
								selite 				= 'Automaattikohdistettu asiakkaan suoritus (alv ongelma)'";
					$insres = mysql_query($query) or pupe_error($query);
				}
				else {

					$aputunnus = 0;

					while ($tiliointirow = mysql_fetch_array($tilres)) {
						$alv 	= 0;
						$alv_valuutassa = 0;
						$summa 	= sprintf('%.2f', $tiliointirow["summa"] * -1 * (1+$tiliointirow["vero"]/100) / $row["laskusumma"] * $alennus);
						$summa_valuutassa = sprintf('%.2f', $tiliointirow["summa_valuutassa"] * -1 * (1+$tiliointirow["vero"]/100) / $row["laskusumma_valuutassa"] * $alennus_valuutassa);

						if ($tiliointirow["vero"] != 0) {
							#Netotetaan alvi
							#$alv:ssa on alennuksen alv:n maara
							$alv = sprintf('%.2f', $summa - $summa / (1 + ($tiliointirow["vero"]/100)));
							$alv_valuutassa = sprintf('%.2f', $summa_valuutassa - $summa_valuutassa / (1 + ($tiliointirow["vero"]/100)));

							#$summa on alviton alennus
							$summa -= $alv;
							$summa_valuutassa -= $alv_valuutassa;
						}

						// Kuinka paljon olemme kumulatiivisesti tiliˆineet
						$totkasumma += $summa + $alv;
						$totkasumma_valuutassa += $summa_valuutassa + $alv_valuutassa;

						# Etsit‰‰n korjattava vienti
						if ($alv != 0) {
							#Lis‰t‰‰n myynnin kassa-alennuskirjaus ilman veroa
							$query = "	INSERT INTO tiliointi
										SET yhtio			= '$row[suoriyhtio]',
										laatija 			= 'automaattikohdistus',
										laadittu 			= now(),
										kustp				= '$tiliointirow[kustp]',
										kohde				= '$tiliointirow[kohde]',
										projekti			= '$tiliointirow[projekti]',
										tapvm 				= '$row[kirjpvm]',
										ltunnus 			= '$row[laskutunnus]',
										tilino 				= '$row[alennustili]',
										summa 				= '$summa',
										summa_valuutassa 	= '$summa_valuutassa',
										valkoodi			= '$row[suoritusvaluutta]',
										vero				= '$tiliointirow[vero]',
										selite 				= 'Automaattikohdistettu asiakkaan suoritus (Kassa-alennus)'";
							$insres = mysql_query($query) or pupe_error($query);
							$aputunnus = mysql_insert_id();

							#Kirjataan myˆs kassa-alennuksen arvonlis‰verot
							$query = "	INSERT into tiliointi
										SET yhtio 			= '$row[suoriyhtio]',
										ltunnus 			= '$row[laskutunnus]',
										tilino 				= '$row[alvtili]',
										tapvm 				= '$row[kirjpvm]',
										summa 				= '$alv',
										summa_valuutassa 	= '$alv_valuutassa',
										valkoodi			= '$row[suoritusvaluutta]',
										vero 				= '',
										selite 				= 'Automaattikohdistettu asiakkaan suoritus (Kassa-alennuksen alv)',
										lukko 				= '1',
										laatija 			= 'automaattikohdistus',
										laadittu 			= now(),
										aputunnus 			= '$aputunnus'";
							$insres = mysql_query($query) or pupe_error($query);
						}
						else {
							#Lis‰t‰‰n verottoman myynnin kassa-alennuskirjaus
							$query = "	INSERT INTO tiliointi
										SET yhtio			= '$row[suoriyhtio]',
										laatija 			= 'automaattikohdistus',
										laadittu 			= now(),
										kustp				= '$tiliointirow[kustp]',
										kohde				= '$tiliointirow[kohde]',
										projekti			= '$tiliointirow[projekti]',
										tapvm 				= '$row[kirjpvm]',
										ltunnus 			= '$row[laskutunnus]',
										tilino 				= '$row[alennustili]',
										summa 				= '$alennus',
										summa_valuutassa 	= '$alennus_valuutassa',
										valkoodi			= '$row[suoritusvaluutta]',
										selite 				= 'Automaattikohdistettu asiakkaan suoritus (Kassa-Alennus)'";
							$insres = mysql_query($query) or pupe_error($query);
							$aputunnus = mysql_insert_id();
						}
					}

					//Hoidetaan mahdolliset pyˆristykset
					$heitto = $totkasumma - $alennus;

					if (abs($heitto) >= 0.01) {
						$query = "	UPDATE tiliointi SET
									summa = summa - $totkasumma + $alennus,
									summa_valuutassa = summa_valuutassa - $totkasumma_valuutassa + $alennus_valuutassa,
									WHERE tunnus = '$aputunnus' and yhtio='$row[suoriyhtio]'";
						$xresult = mysql_query($query) or pupe_error($query);

						$aputunnus = 0;
					}
				}
			}

			// tuliko valuuttaeroa?
			if (strtoupper($row["suoritusvaluutta"]) != strtoupper($row['yhtiovaluutta'])) {

				$valero = round($row["laskutettu"] - $suoritettu_kassaan - $alennus, 2);

				if (abs($valero) >= 0.01) {
					$query = "	INSERT INTO tiliointi (yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
			            		VALUES ('$row[suoriyhtio]','automaattikohdistus',now(), '$row[kirjpvm]', '$row[laskutunnus]', '$row[valuuttaerotili]', $valero, 'Automaattikohdistettu asiakkaan suoritus')";
					$result = mysql_query($query) or pupe_error($query);
				}
			}

			#Merkit‰‰n lasku maksetuksi
			$query = "UPDATE lasku SET mapvm='$row[kirjpvm]' WHERE tunnus='$row[laskutunnus]'";
			$updres = mysql_query($query) or pupe_error($query);

			#Ja suoritus kirjatuksi
			$query = "UPDATE suoritus SET kohdpvm=now(), ltunnus='$row[laskutunnus]', summa='0' WHERE tunnus='$row[suoritunnus]'";
			$updres = mysql_query($query) or pupe_error($query);
		}
	}



	#####################################
	#
	# siirret‰‰n feilanneet laskuiksi...
	#
	#####################################

	echo "<font class='message'>".t("Tehd‰‰n kohdistamattomista laskuja...")."</font><br>";

	$query = "	SELECT
				suoritus.tunnus tunnus,
				suoritus.yhtio yhtio,
				suoritus.summa summa,
				suoritus.kirjpvm,
				suoritus.valkoodi suoritusvaluutta,
				suoritus.kurssi suorituskurssi,
				suoritus.nimi_maksaja,
				yhtio.myyntisaamiset,
				yhtio.valkoodi yhtiovaluutta,
				yhtio.factoringsaamiset,
				yriti.factoring,
				yriti.oletus_rahatili,
				yriti.oletus_selvittelytili
				FROM yhtio
				JOIN yriti ON (yriti.yhtio = yhtio.yhtio)
				JOIN suoritus ON (suoritus.yhtio = yhtio.yhtio AND suoritus.tilino = yriti.tilino AND suoritus.kohdpvm = '0000-00-00' AND suoritus.ltunnus = 0)
				ORDER BY yhtio";
	$suorires = mysql_query($query) or pupe_error($query);

	$laskut = array();

	while ($row = mysql_fetch_array($suorires)) {

		if (strtoupper($row["suoritusvaluutta"]) != strtoupper($row['yhtiovaluutta'])) {
			$summa = round($row["summa"] * $row["suorituskurssi"],2);
			$summa_valuutassa = $row["summa"];
		}
		else {
			$summa = $row["summa"];
			$summa_valuutassa = $row["summa"];
		}

		if (!isset($laskut[$row["yhtio"]])) {
			$query = "INSERT into lasku set yhtio = '$row[yhtio]', tapvm = now(), tila = 'X', laatija = 'viitesiirrot', luontiaika = now()";
			$insres = mysql_query($query) or pupe_error($query);

			$laskut[$row["yhtio"]] = mysql_insert_id();
		}

		if ($laskut[$row["yhtio"]] > 0) {

			# Onko t‰m‰ sittenkin factoringia
			if ($row["factoring"] != "") {
				$row["myyntisaamiset"] = $row["factoringsaamiset"];
			}

			# Jos meill‰ on oletus selvittelytili niin pistet‰‰n aina sinne tilille
			if ($row["oletus_selvittelytili"] != "") {
				$row["myyntisaamiset"] = $row["oletus_selvittelytili"];
			}

			$maksaja_espattu = mysql_real_escape_string($row["nimi_maksaja"]);

			# Myyntisaamiset tai selvittely
			$query = "INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, summa_valuutassa, valkoodi, selite, lukko) values ('$row[yhtio]','automaattikohdistus',now(),'$row[kirjpvm]','".$laskut[$row["yhtio"]]."','$row[myyntisaamiset]', -$summa, -$summa_valuutassa, '$row[suoritusvaluutta]', ' $maksaja_espattu maksoi viitteell‰ v‰‰rin', '1')";
			$insres = mysql_query($query) or pupe_error($query);
			$tlast_id = mysql_insert_id();

			# Kassatili
			$query = "INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, summa_valuutassa, valkoodi, selite, aputunnus, lukko) values ('$row[yhtio]','automaattikohdistus',now(),'$row[kirjpvm]','".$laskut[$row["yhtio"]]."','$row[oletus_rahatili]', $summa, $summa_valuutassa, '$row[suoritusvaluutta]', ' $maksaja_espattu maksoi viitteell‰ v‰‰rin', '$tlast_id', '1')";
			$insres = mysql_query($query) or pupe_error($query);

			$query = "UPDATE suoritus SET ltunnus='$tlast_id' WHERE tunnus='$row[tunnus]'";
			$updres = mysql_query($query) or pupe_error($query);
		}
		else {
			echo "<font class='error'>VIRHE: Tositteen otsikkoa ei saatu luotua!</font><br>";
		}
	}

?>
