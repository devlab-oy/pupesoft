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
				yriti.factoring factoring,
				yriti.oletus_rahatili kassatili,
				factoring.pankki_tili ftilino
				FROM yhtio
				JOIN lasku use index (yhtio_tila_mapvm) ON (lasku.yhtio = yhtio.yhtio and lasku.tila = 'U' and lasku.alatila = 'X' and lasku.mapvm = '0000-00-00')
				JOIN yriti ON (yriti.yhtio = yhtio.yhtio)
				JOIN suoritus use index (yhtio_viite) ON (suoritus.yhtio = lasku.yhtio and suoritus.viite = lasku.viite and suoritus.valkoodi=lasku.valkoodi and suoritus.tilino = yriti.tilino and suoritus.kohdpvm = '0000-00-00' and suoritus.ltunnus=0)
				JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio and maksuehto.tunnus = lasku.maksuehto)
				LEFT JOIN factoring ON (factoring.yhtio = lasku.yhtio and factoring.valkoodi = lasku.valkoodi and factoring.factoringyhtio = maksuehto.factoring)
				HAVING 
				(laskuvaluutta  = yhtiovaluutta and (kapvm >= adddate(kirjpvm,-4) and abs(laskusumma-saldo_maksettu-kasumma-suoritettu) < 0.01) or (abs(laskusumma-saldo_maksettu-suoritettu) < 0.01)) or
				(laskuvaluutta != yhtiovaluutta and (kapvm >= adddate(kirjpvm,-4) and abs(laskusumma_valuutassa-saldo_maksettu_valuutassa-kasumma_valuutassa-suoritettu) < 0.01) or (abs(laskusumma_valuutassa-saldo_maksettu_valuutassa-suoritettu) < 0.01))";
	$suorires = mysql_query($query) or pupe_error($query);

	while ($row = mysql_fetch_array($suorires)) {				
		
		if (strtoupper($row["suoritusvaluutta"]) != strtoupper($row['yhtiovaluutta'])) {
			$alennus = round(($row["laskutettu_valuutassa"]-$row["suoritettu"]) * $row["suorituskurssi"],2);
		}
		else {
			$alennus = $row["laskutettu"]-$row["suoritettu"];
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

			#Onko t‰m‰ sittenkin factoringia
			if ($row["factoring"] != "") {
				$row["myyntisaamiset"] = $row["factoringsaamiset"];
			}

			#Myyntisaamiset
			$query = "INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite) values ('$row[suoriyhtio]','automaattikohdistus',now(),'$row[kirjpvm]','$row[laskutunnus]','$row[myyntisaamiset]',-$row[laskutettu],'Automaattikohdistettu asiakkaan suoritus')";
			$insres = mysql_query($query) or pupe_error($query);

			if (strtoupper($row["suoritusvaluutta"]) != strtoupper($row['yhtiovaluutta'])) {
				$suoritettu_kassaan = round($row["suoritettu"] * $row["suorituskurssi"],2);
			}
			else {
				$suoritettu_kassaan = $row["suoritettu"];
			}
			
			#Kassatili
			$query = "INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite) values ('$row[suoriyhtio]','automaattikohdistus',now(),'$row[kirjpvm]','$row[laskutunnus]','$row[kassatili]',$suoritettu_kassaan,'Automaattikohdistettu asiakkaan suoritus')";
			$insres = mysql_query($query) or pupe_error($query);

			#Mahdollinen kassa-alennus
			if($alennus != 0) {
				#$alennus muuttujassa on kassa-alennus mahdollisine arvonlis‰veroineen

				#Etsit‰‰n myynti-tiliˆinnit
				$query = "	SELECT summa, vero
							FROM tiliointi use index (tositerivit_index)
							WHERE ltunnus 	 = '$row[laskutunnus]'
							and yhtio   	 = '$row[suoriyhtio]'
							and tapvm   	 = '$row[tapvm]'
							and abs(summa)  <> 0
							and tilino 		<> '$row[myyntisaamiset]'
							and tilino 		<> '$row[alvtili]'
							and tilino 		<> '$row[varasto]'
							and tilino 		<> '$row[varastomuu]'
							and tilino 		<> '$row[pyoristys]'
							and tilino 		<> '$row[alennustili]'
							and tilino		<> '$yhtiorow[factoringsaamiset]' 
							and korjattu = ''";
				$tilres = mysql_query($query) or pupe_error($query);

				# jos lˆytyy vaan yksi myynti-tiliˆinti tehd‰‰n kassa-ale kirjaus.. mit‰ jos lˆytyy useampi!?!?!?
				if (mysql_num_rows($tilres) == 1) {
					while ($tiliointirow = mysql_fetch_array($tilres)) {
						$alv 	= 0;
						$summa 	= sprintf('%.2f', $tiliointirow["summa"] * -1 * (1+$tiliointirow["vero"]/100) / $row["laskusumma"] * $alennus);

						if ($tiliointirow["vero"] != 0) {
							#Netotetaan alvi
							#$alv:ssa on alennuksen alv:n maara
							$alv = sprintf('%.2f', $summa - $summa / (1 + ($tiliointirow["vero"]/100)));

							#$summa on alviton alennus
							$summa -= $alv;
						}

						# Etsit‰‰n korjattava vienti
						if ($alv != 0) {
							#Lis‰t‰‰n myynnin kassa-alennuskirjaus ilman veroa
							$query = "	INSERT INTO tiliointi
										SET yhtio	= '$row[suoriyhtio]',
										laatija 	= 'automaattikohdistus',
										laadittu 	= now(),
										tapvm 		= '$row[kirjpvm]',
										ltunnus 	= '$row[laskutunnus]',
										tilino 		= '$row[alennustili]',
										summa 		= '$summa',
										vero		= '$tiliointirow[vero]',
										selite 		= 'Automaattikohdistettu asiakkaan suoritus (Kassa-alennus)'";
							$insres = mysql_query($query) or pupe_error($query);
							$aputunnus = mysql_insert_id();

							#Kirjataan myˆs kassa-alennuksen arvonlis‰verot
							$query = "	INSERT into tiliointi
										SET yhtio 	= '$row[suoriyhtio]',
										ltunnus 	= '$row[laskutunnus]',
										tilino 		= '$row[alvtili]',
										tapvm 		= '$row[kirjpvm]',
										summa 		= '$alv',
										vero 		= '',
										selite 		= 'Automaattikohdistettu asiakkaan suoritus (Kassa-alennuksen alv)',
										lukko 		= '1',
										laatija 	= 'automaattikohdistus',
										laadittu 	= now(),
										aputunnus 	= '$aputunnus'";
							$insres = mysql_query($query) or pupe_error($query);
						}
						else {
							#Lis‰t‰‰n verottoman myynnin kassa-alennuskirjaus
							$query = "	INSERT INTO tiliointi
										SET yhtio	= '$row[suoriyhtio]',
										laatija 	= 'automaattikohdistus',
										laadittu 	= now(),
										tapvm 		= '$row[kirjpvm]',
										ltunnus 	= '$row[laskutunnus]',
										tilino 		= '$row[alennustili]',
										summa 		= '$alennus',
										selite 		= 'Automaattikohdistettu asiakkaan suoritus (Kassa-Alennus)'";
							$insres = mysql_query($query) or pupe_error($query);
						}
					}
				}
			}
			
			// tuliko valuuttaeroa?
			if (strtoupper($row["suoritusvaluutta"]) != strtoupper($row['yhtiovaluutta'])) {
				
				$valero = round($row["laskutettu"] - $suoritettu_kassaan - $alennus,2);

				if (abs($valero) >= 0.01) {
					$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
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
				yriti.oletus_rahatili
				FROM yhtio, suoritus, yriti
				WHERE suoritus.kohdpvm = '0000-00-00'
				AND suoritus.tilino=yriti.tilino
				AND yriti.yhtio=yhtio.yhtio
				AND suoritus.yhtio=yhtio.yhtio
				AND suoritus.ltunnus = 0
				ORDER BY yhtio";
	$suorires = mysql_query($query) or pupe_error($query);

	$laskut = array();

	while ($row = mysql_fetch_array($suorires)) {
		
		if (strtoupper($row["suoritusvaluutta"]) != strtoupper($row['yhtiovaluutta'])) {
			$row["summa"] = round($row["summa"] * $row["suorituskurssi"],2);
		}
		
		if (!isset($laskut[$row["yhtio"]])) {
			$query = "INSERT into lasku set yhtio = '$row[yhtio]', tapvm = now(), tila = 'X', laatija = 'viitesiirrot', luontiaika = now()";
			$insres = mysql_query($query) or pupe_error($query);

			$laskut[$row["yhtio"]] = mysql_insert_id();
		}

		if ($laskut[$row["yhtio"]] > 0) {
			#Onko t‰m‰ sittenkin factoringia
			if ($row["factoring"] != "") {
				$row["myyntisaamiset"] = $row["factoringsaamiset"];
			}

			#Myyntisaamiset
			$query = "INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite,lukko) values ('$row[yhtio]','automaattikohdistus',now(),'$row[kirjpvm]','".$laskut[$row["yhtio"]]."','$row[myyntisaamiset]', -$row[summa],' maksoi viitteell‰ v‰‰rin','1')";
			$insres = mysql_query($query) or pupe_error($query);
			$tlast_id = mysql_insert_id();

			#Kassatili
			$query = "INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite,aputunnus,lukko) values ('$row[yhtio]','automaattikohdistus',now(),'$row[kirjpvm]','".$laskut[$row["yhtio"]]."','$row[oletus_rahatili]', $row[summa],' maksoi viitteell‰ v‰‰rin','$tlast_id','1')";
			$insres = mysql_query($query) or pupe_error($query);

			$query = "UPDATE suoritus SET ltunnus='$tlast_id' WHERE tunnus='$row[tunnus]'";
			$updres = mysql_query($query) or pupe_error($query);
		}
		else {
			echo "<font class='error'>VIRHE: Tositteen otsikkoa ei saatu luotua!</font><br>";
		}
	}

?>
