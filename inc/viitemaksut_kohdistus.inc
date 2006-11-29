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
				if(suoritus.valkoodi='MUU', round(suoritus.summa*if(lasku.vienti_kurssi!=0,lasku.vienti_kurssi,1),2), suoritus.summa) suoritettu,
				lasku.summa-lasku.saldo_maksettu laskutettu,
				lasku.tunnus laskutunnus,
				lasku.tapvm tapvm,
				lasku.summa laskusumma,
				lasku.kapvm kapvm,
				lasku.saldo_maksettu saldo_maksettu,
				lasku.kasumma kasumma,
				yhtio.myynninkassaale alennustili,
				yhtio.myyntisaamiset myyntisaamiset,
				yhtio.konsernimyyntisaamiset konsernimyyntisaamiset,
				yhtio.factoringsaamiset factoringsaamiset,
				yriti.factoring factoring,
				yhtio.alv alvtili,
				yhtio.varasto varasto,
				yhtio.varastonmuutos varastomuu,
				yhtio.pyoristys pyoristys,
				yriti.oletus_rahatili kassatili
				FROM yhtio
				JOIN lasku use index (yhtio_tila_mapvm) ON lasku.yhtio=yhtio.yhtio and lasku.tila = 'U' and lasku.alatila = 'X' and lasku.mapvm = '0000-00-00'
				JOIN yriti ON yriti.yhtio=yhtio.yhtio
				JOIN suoritus use index (yhtio_viite) ON suoritus.yhtio=lasku.yhtio and suoritus.viite=lasku.viite and suoritus.tilino=yriti.tilino and suoritus.kohdpvm = '0000-00-00'
				HAVING (kapvm >= adddate(kirjpvm,-4) and abs(laskusumma-saldo_maksettu-kasumma-suoritettu) < 0.01) or (abs(laskusumma-saldo_maksettu-suoritettu) < 0.01)";
	$suorires = mysql_query($query) or pupe_error($query);

	while ($row = mysql_fetch_array($suorires)) {

		$suorite	 			= $row["suoritunnus"];
		$lasku 					= $row["laskutunnus"];
		$yhtio 					= $row["suoriyhtio"];
		$myyntisaamiset			= $row["myyntisaamiset"];
		$suoritettu 			= $row["suoritettu"];
		$laskutettu 			= $row["laskutettu"];
		$alennustili			= $row["alennustili"];
		$kirjpvm 				= $row["kirjpvm"];
		$kassatili				= $row["kassatili"];
		$alvtili				= $row["alvtili"];
		$varasto				= $row["varasto"];
		$varastomuu				= $row["varastomuu"];
		$pyoristys				= $row["pyoristys"];
		$tapvm					= $row["tapvm"];
		$laskusumma				= $row["laskusumma"];
		$konsernimyyntisaamiset	= $row["konsernimyyntisaamiset"];
		$factoringsaamiset 		= $row["factoringsaamiset"];
		$factoring 				= $row["factoring"];
		$alennus				= $laskutettu-$suoritettu;

		#Katsotaan ensin, ettei tätä laskua ole jo suoritettu/maksettu
		#Eli keissi jossa asiakas maksaa saman laskun kahteen kertaan samassa viiteaineistossa
		$query = "	SELECT tunnus, ytunnus, maksuehto
					FROM lasku
					WHERE tunnus = '$lasku'
					and mapvm = '0000-00-00'";
		$lasresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($lasresult) == 1) {

			$malaskurow = mysql_fetch_array($lasresult);

			$malasytunnus 	= $malaskurow["ytunnus"];
			$malasmehto 	= $malaskurow["maksuehto"];

			#Etsitään asiakas, jos se olisi konsernin jäsen
			$query = "	SELECT konserniyhtio
						FROM asiakas
						WHERE ytunnus 	 = '$malasytunnus'
						and yhtio   	 = '$yhtio'";
			$asresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($asresult) > 0) {
				$asiakasrow = mysql_fetch_array($asresult);

				if ($asiakasrow["konserniyhtio"] != "") {
					$myyntisaamiset = $konsernimyyntisaamiset;
				}
			}

			#Onko tämä sittenkin factoringia
			if ($factoring != "") {
				$myyntisaamiset = $factoringsaamiset;
			}

			#Myyntisaamiset
			$query = "INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite) values ('$yhtio','automaattikohdistus',now(),'$kirjpvm','$lasku','$myyntisaamiset',-$laskutettu,'Automaattikohdistettu asiakkaan suoritus')";
			$insres = mysql_query($query) or pupe_error($query);

			#Kassatili
			$query = "INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite) values ('$yhtio','automaattikohdistus',now(),'$kirjpvm','$lasku','$kassatili',$suoritettu,'Automaattikohdistettu asiakkaan suoritus')";
			$insres = mysql_query($query) or pupe_error($query);

			#Mahdollinen kassa-alennus
			if($alennus != 0) {
				#$alennus muuttujassa on kassa-alennus mahdollisine arvonlisäveroineen

				#Etsitään myynti-tiliöinnit
				$query = "	SELECT summa, vero
							FROM tiliointi use index (tositerivit_index)
							WHERE ltunnus 	 = '$lasku'
							and yhtio   	 = '$yhtio'
							and tapvm   	 = '$tapvm'
							and abs(summa)  <> 0
							and tilino 		<> '$myyntisaamiset'
							and tilino 		<> '$alvtili'
							and tilino 		<> '$varasto'
							and tilino 		<> '$varastomuu'
							and tilino 		<> '$pyoristys'
							and tilino 		<> '$alennustili'
							and korjattu = ''";
				$tilres = mysql_query($query) or pupe_error($query);

				# jos löytyy vaan yksi myynti-tiliöinti tehdään kassa-ale kirjaus.. mitä jos löytyy useampi!?!?!?
				if (mysql_num_rows($tilres) == 1) {

					while ($tiliointirow = mysql_fetch_array($tilres)) {
						$alv 	= 0;
						$summa 	= sprintf('%.2f', $tiliointirow["summa"] * -1 * (1+$tiliointirow["vero"]/100) / $laskusumma * $alennus);

						if ($tiliointirow["vero"] != 0) {
							#Netotetaan alvi
							#$alv:ssa on alennuksen alv:n maara
							$alv = sprintf('%.2f', $summa - $summa / (1 + ($tiliointirow["vero"]/100)));

							#$summa on alviton alennus
							$summa -= $alv;
						}

						# Etsitään korjattava vienti
						if ($alv != 0) {

							#Lisätään myynnin kassa-alennuskirjaus ilman veroa
							$query = "	INSERT INTO tiliointi
										SET yhtio	= '$yhtio',
										laatija 	= 'automaattikohdistus',
										laadittu 	= now(),
										tapvm 		= '$kirjpvm',
										ltunnus 	= '$lasku',
										tilino 		= '$alennustili',
										summa 		= '$summa',
										vero		= '$tiliointirow[1]',
										selite 		= 'Automaattikohdistettu asiakkaan suoritus (Kassa-alennus)'";
							$insres = mysql_query($query) or pupe_error($query);
							$aputunnus = mysql_insert_id();

							#Kirjataan myös kassa-alennuksen arvonlisäverot
							$query = "	INSERT into tiliointi
										SET yhtio 	= '$yhtio',
										ltunnus 	= '$lasku',
										tilino 		= '$alvtili',
										tapvm 		= '$kirjpvm',
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

							#Lisätään verottoman myynnin kassa-alennuskirjaus
							$query = "	INSERT INTO tiliointi
										SET yhtio	= '$yhtio',
										laatija 	= 'automaattikohdistus',
										laadittu 	= now(),
										tapvm 		= '$kirjpvm',
										ltunnus 	= '$lasku',
										tilino 		= '$alennustili',
										summa 		= '$alennus',
										selite 		= 'Automaattikohdistettu asiakkaan suoritus (Kassa-Alennus)'";
							$insres = mysql_query($query) or pupe_error($query);
						}
					}
				}
			}

			#Merkitään lasku maksetuksi
			$query = "UPDATE lasku SET mapvm='$kirjpvm' WHERE tunnus='$lasku'";
			$updres = mysql_query($query) or pupe_error($query);

			#Ja suoritus kirjatuksi
			$query = "UPDATE suoritus SET kohdpvm=now(), ltunnus='$lasku', summa='0' WHERE tunnus='$suorite'";
			$updres = mysql_query($query) or pupe_error($query);

			#echo("Kohdistettu suoritus $suorite ja lasku $lasku\n");
		}
	}



	#####################################
	#
	# siirretään feilanneet laskuiksi...
	#
	#####################################

	echo "<font class='message'>".t("Tehdään kohdistamattomista laskuja...")."</font><br>";

	$query = "	SELECT suoritus.tunnus tunnus, suoritus.yhtio yhtio, yhtio.myyntisaamiset, suoritus.summa summa, suoritus.kirjpvm, yriti.oletus_rahatili,suoritus.nimi_maksaja, yhtio.factoringsaamiset, yriti.factoring
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

		$suorite 			= $row[0];
		$yhtio 				= $row[1];
		$myyntisaamiset		= $row[2];
		$suoritettu 		= $row[3];
		$kirjpvm 			= $row[4];
		$kassatili			= $row[5];
		$maksaja			= $row[6];
		$factoringsaamiset 	= $row[7];
		$factoring 			= $row[8];

		if (!isset($laskut[$yhtio])) {
			$query = "INSERT into lasku set yhtio = '$yhtio', tapvm = now(), tila = 'X', laatija = 'viitesiirrot', luontiaika = now()";
			$insres = mysql_query($query) or pupe_error($query);

			$laskut[$yhtio] = mysql_insert_id();
		}

		if ($laskut[$yhtio] > 0) {
			#Onko tämä sittenkin factoringia
			if ($factoring != "") {
				$myyntisaamiset = $factoringsaamiset;
			}

			#Myyntisaamiset
			$query = "INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite,lukko) values ('$yhtio','automaattikohdistus',now(),'$kirjpvm','$laskut[$yhtio]','$myyntisaamiset',-$suoritettu,' maksoi viitteellä väärin','1')";
			$insres = mysql_query($query) or pupe_error($query);
			$tlast_id = mysql_insert_id();

			#Kassatili
			$query = "INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite,aputunnus,lukko) values ('$yhtio','automaattikohdistus',now(),'$kirjpvm','$laskut[$yhtio]','$kassatili','$suoritettu',' maksoi viitteellä väärin','$tlast_id','1')";
			$insres = mysql_query($query) or pupe_error($query);

			$query = "UPDATE suoritus SET ltunnus='$tlast_id' WHERE tunnus='$suorite'";
			$updres = mysql_query($query) or pupe_error($query);
		}
		else {
			echo "<font class='error'>VIRHE: Tositteen otsikkoa ei saatu luotua!</font><br>";
		}
	}

?>
