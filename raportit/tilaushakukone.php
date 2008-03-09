<?php

if (isset($_POST["tee"])) {
	if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}
	
//parametrit
if (strpos($_SERVER['SCRIPT_NAME'], "tilaushakukone.php")  !== FALSE) {
	require('../inc/parametrit.inc');
	js_popup();
	js_showhide();
	enable_ajax();
}

if (isset($tee) and $tee == "lataa_tiedosto") {
	readfile("/tmp/".$tmpfilenimi);
	exit;
}


//echo "<pre>".print_r($_REQUEST, true)."</pre>";
if($tee == "ASIAKASHAKU") {
	tee_asiakashaku($haku, $formi);
}

if($toim == "TARJOUSHAKUKONE") {
	$laskutilat = "'T'";
	$hakukysely = "tarjoushakukone";
}
elseif ($toim == "TILAUSHAKUKONE") {
	$laskutilat = "'R','L','N'";
	$hakukysely = "tilaushakukone";
}

//	Haetaan sallitut seurannat
$query = "	SELECT group_concat(distinct selite SEPARATOR \"','\") lajit
			FROM avainsana
			WHERE yhtio = '{$kukarow["yhtio"]}' and laji = 'SEURANTA' and selitetark_2 = 'kale'
			GROUP BY laji";
$abures = mysql_query($query) or pupe_error($query);
$aburow = mysql_fetch_array($abures);
if($aburow["lajit"] != "") {
	$lajilisa = " and seuranta IN ('','{$aburow["lajit"]}')";
}

if($tee == "VAIHDAPP") {
	
	//	Haetaan vanhan projektipäällikkön nimi
	$query = " 	SELECT kuka.*
				FROM laskun_lisatiedot
				JOIN kuka ON kuka.yhtio=laskun_lisatiedot.yhtio and kuka.kuka=laskun_lisatiedot.projektipaallikko
				WHERE laskun_lisatiedot.yhtio = '$kukarow[yhtio]' and otunnus='$tarjous' and projektipaallikko!=''";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);
	$wanha_pp=$row;
	
	//	Haetaan kaikki tunnukset
	$query = "	select group_concat(tunnus) tunnukset
	 			from lasku
				WHERE yhtio = '$kukarow[yhtio]' and (tunnus=$tarjous or tunnusnippu='$tarjous') and tila IN ('L','G','E','V','W','N','R')";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);

	$query = "	update laskun_lisatiedot set 
					projektipaallikko='$projektipaallikko'
				WHERE yhtio = '$kukarow[yhtio]' and otunnus IN ($row[tunnukset])";
	$result = mysql_query($query) or pupe_error($query);
	
	$query = "select tila from lasku where yhtio = '$kukarow[yhtio]' and tunnus='$tarjous'";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);
	
	if($row["tila"] == "R") {
		$teksti = "Projektin";
		$teksti2 = "projektille";		
	}
	else {
		$teksti = "Tilauksen";
		$teksti2 = "tilaukselle";
	}
	
	//	Lähetetään käyttäjille viestit jos tämä oli projekti
	$header  = "From: <$yhtiorow[postittaja_email]>\n";
	$header .= "MIME-Version: 1.0\n" ;

	$subject = t("$teksti $tarjous projektipäällikkö vaihdettu");
			
	//	Mailataan iha eka sille uudelle projektipäällikölle
	$query = " select * from kuka where yhtio = '$kukarow[yhtio]' and kuka='$projektipaallikko'";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);
	
	$msg = "Teidät on valittu projektipäälliköksi $teksti2 $tarjous\n\n";
	mail($row["eposti"], $subject, $msg, $header, " -f $yhtiorow[postittaja_email]");
	
	//	Informoidaan myös vanhaa jotta sekin tietää jostain jotain, edes nyt..
	if($wanha_pp["eposti"] != "") {
		$msg = "$teksti $tarjous projektipääliköksi vaihdettiin $projektipaallikko\n\n";
		mail($wanha_pp["eposti"], $subject, $msg, $header, " -f $yhtiorow[postittaja_email]");
	}
	
	//	Myös joku muu haluaa tästä tietää, laitetaan niille muille projektipäälliköille myös postia!
	$query = "	SELECT distinct kuka, eposti
				FROM kuka
				WHERE yhtio = '$kukarow[yhtio]' and asema = 'PP' and kuka != '$projektipaallikko' and kuka != '{$wanha_pp["kuka"]}'";
	$kres=mysql_query($query) or pupe_error($query);
	while($krow=mysql_fetch_array($kres)){
		$msg = "Projektipäällikkö vaihdettiin $teksti2 $tarjous.\n\nUusi projektipaallikko on $projektipaallikko\n\n";
		//mail($krow["eposti"], $subject, $msg, $header, " -f $yhtiorow[postittaja_email]");
	}
	$tee = "NAYTA";
}

if($tee == "HYLKAATARJOUS") {

	$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$otunnus' and tila = 'T'";
	$result = mysql_query($query) or pupe_error($query);

	$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$otunnus' and tyyppi = 'T'";
	$result = mysql_query($query) or pupe_error($query);

	//Nollataan sarjanumerolinkit
	$query    = "	SELECT tilausrivi.tunnus, (tilausrivi.varattu+tilausrivi.jt) varattu, tilausrivi.tuoteno, tuote.sarjanumeroseuranta
					FROM tilausrivi use index (yhtio_otunnus)
					JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					and tilausrivi.otunnus='$$otunnus'";
	$sres = mysql_query($query) or pupe_error($query);

	while ($srow = mysql_fetch_array($sres)) {
		if ($srow["varattu"] < 0) {
			// dellataan koko rivi jos sitä ei ole vielä myyty
			$query = "delete from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]' and myyntirivitunnus=0";
			$sarjares = mysql_query($query) or pupe_error($query);
			
			if (mysql_affected_rows() == 0) {
				// merkataan osorivitunnus nollaksi
				$query = "update sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
		}
		else {
			// merktaan myyntirivitunnus nollaks
			if ($srow["sarjanumeroseuranta"] == "E" or $srow["sarjanumeroseuranta"] == "F") {
				$query = "	DELETE FROM sarjanumeroseuranta
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$srow[tuoteno]'
							and myyntirivitunnus = '$srow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
			else {
				$query = "	UPDATE sarjanumeroseuranta
							SET myyntirivitunnus = 0
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$srow[tuoteno]'
							and myyntirivitunnus = '$srow[tunnus]'";
				$sarjares = mysql_query($query) or pupe_error($query);
			}
		}
	}

	//	Päivitetään myös muut tunnusnipun jäsenet sympatian vuoksi hylätyiksi *** tämän voisi varmaan tehdä myös kaikki kerralla? ***
	$query = "select tunnus from lasku where yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = $tarjous and tila = 'T'";
	$abures = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($abures) > 0) {
		while ($row = mysql_fetch_array($abures)) {
			$query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus=$row[tunnus]";
			$result = mysql_query($query) or pupe_error($query);

			$query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus=$row[tunnus] and tyyppi = 'T'";
			$result = mysql_query($query) or pupe_error($query);

			//Nollataan sarjanumerolinkit
			$query    = "	SELECT tilausrivi.tunnus, (tilausrivi.varattu+tilausrivi.jt) varattu, tilausrivi.tuoteno, tuote.sarjanumeroseuranta
							FROM tilausrivi use index (yhtio_otunnus)
							JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
							WHERE tilausrivi.yhtio='$kukarow[yhtio]'
							and tilausrivi.otunnus='$row[tunnus]'";
			$sres = mysql_query($query) or pupe_error($query);

			while ($srow = mysql_fetch_array($sres)) {
				if ($srow["varattu"] < 0) {
					// dellataan koko rivi jos sitä ei ole vielä myyty
					$query = "delete from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]' and myyntirivitunnus=0";
					$sarjares = mysql_query($query) or pupe_error($query);
					
					if (mysql_affected_rows() == 0) {
						// merkataan osorivitunnus nollaksi
						$query = "update sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$srow[tuoteno]' and ostorivitunnus='$srow[tunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
					}
				}
				else {
					// merkataan myyntirivitunnus nollaks
					if ($srow["sarjanumeroseuranta"] == "E" or $srow["sarjanumeroseuranta"] == "F") {
						$query = "	DELETE FROM sarjanumeroseuranta
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$srow[tuoteno]'
									and myyntirivitunnus = '$srow[tunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
					}
					else {
						$query = "	UPDATE sarjanumeroseuranta
									SET myyntirivitunnus = 0
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$srow[tuoteno]'
									and myyntirivitunnus = '$srow[tunnus]'";
						$sarjares = mysql_query($query) or pupe_error($query);
					}
				}
			}
		}
	}

	$tee = "";
	
	//	Suoritetaan viimeisin kysely uudestaan..
	$aja_kysely = "tmpquery";		
	$hakupalkki = "OHI";
}

if($tee == "SULJEPROJEKTI") {
	$query = "	SELECT lasku.tunnus, projekti
				FROM lasku
				JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus and projekti>0
				WHERE lasku.yhtio='$kukarow[yhtio]' and tila='R' and alatila='B' and lasku.tunnus='$tarjous'";
	$result = mysql_query($query) or pupe_error($query);

	if(mysql_num_rows($result)== 1) {
		$aburow=mysql_fetch_array($result);

		//	Suljetaan projekti
		$query = "update lasku set alatila='X', mapvm=now() where yhtio = '$kukarow[yhtio]' and tunnus='$tarjous'";
		$updres = mysql_query($query) or pupe_error($query);

		$query = "update kustannuspaikka set kaytossa='' where yhtio = '$kukarow[yhtio]' and tunnus='$aburow[projekti]'";
		$updres = mysql_query($query) or pupe_error($query);

		echo "<font class='message'>".t("Suljettiin projekti '$tarjous'")."</font><br>";
	}
	else {
		echo "<font class='error'>".t("VIRHE!!! Tilaus '$tarjous' ei ole projekti tai se on väärässä tilassa")."</font><br>";
	}

	$tee 		= "";
	$tarjous 	= "";
	$aja_kysely	= "tmpquery";
}

require('inc/kalenteri.inc');		

if($tee == "NAYTA") {
	
	if($lopetus != "") {
		echo "<div id='tarjouskalenteri_$tarjous'>";
	}
		
	//	Määritellään kalenteritietoja jos meillä on uusi kalenteri
	
	//	Tämä on siis kalenteri jota meidän pitäisi käsitellä
	$kaleDIV = "tarjouskalenteri_$tarjous";
		
	//	Jos kalenteria ei ole vielä määritetty niin se pitää tehdä uudestaan
	if($kaleID != $kaleDIV) {
		$kaleID 						= $kaleDIV;
		$kalenteri["div"] 				= $kaleDIV;
		$kalenteri["URL"] 				= "tilaushakukone.php";
		$kalenteri["url_params"]		= array("toim", "setti", "tarjous", "tee");
		$kalenteri["nakyma"]			= "TAPAHTUMALISTAUS";	
		$kalenteri["sallittu_nakyma"]	= "";
		$kalenteri["laskutilat"]		= $laskutilat;
		$kalenteri["otunnus"]			= $tarjous;
		$kalenteri["kalenteri_kuka"]	= array($kukarow["kuka"]);
		$kalenteri["kalenteri_ketka"]	= array("myyjat","keraajat", "projektipaallikot");
		$kalenteri["kalenteri_jako"]	= array("tilaus");
		
		alusta_kalenteri($kalenteri);
	}
	
	//	Haetaan kaikki projektiotsikot
	$query = "	SELECT 	lasku.laatija, lasku.tunnus tarjous, lasku.tila, lasku.alatila, lasku.liitostunnus, lasku.jaksotettu,
							lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.maa,
							lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa,
							laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa, laskun_lisatiedot.projektipaallikko,
							date_format(lasku.luontiaika, '%d.%m. %Y') avattu,
						versio.tunnus tunnus, versio.luontiaika,
							concat_ws(' ',versio.nimi, versio.nimitark) asiakas, 
							DATEDIFF(versio.luontiaika, date_sub(now(), INTERVAL laskun_lisatiedot.tarjouksen_voimaika day)) pva, 
							date_format(versio.luontiaika, '%d.%m. %Y') avattu_versio,
							date_format((select min(kerayspvm) from lasku l where l.yhtio=lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu and l.tila IN ($laskutilat) and alatila != 'X'), '%d.%m. %Y') seuraava,
							datediff((select min(kerayspvm) from lasku l where l.yhtio=lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu and l.tila IN ($laskutilat) and alatila != 'X'), now()) seuraava_aika,							
						laskun_lisatiedot.seuranta,
						asiakkaan_kohde.kohde,
						laatija.nimi laatija_nimi,
						yhteyshenkilo.nimi yhteyshenkilo_nimi, yhteyshenkilo.email yhteyshenkilo_email, yhteyshenkilo.puh yhteyshenkilo_puh,
						kaupallinen_kasittelija.nimi kaupallinen_kasittelija_nimi, kaupallinen_kasittelija.email kaupallinen_kasittelija_email, kaupallinen_kasittelija.puh kaupallinen_kasittelija_puh,
						if(lasku.tunnusnippu>0,(select group_concat(distinct tunnus) from lasku l where l.yhtio = lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu),lasku.tunnus) versiot,
						if(lasku.tunnusnippu>0,(select count(*) from lasku l where l.yhtio = lasku.yhtio and l.tila IN ($laskutilat) and l.tunnusnippu=lasku.tunnusnippu),1) versioita
						
				FROM lasku
				JOIN lasku versio ON versio.yhtio = lasku.yhtio and versio.tunnus = if(lasku.tila='T',(select max(l.tunnus) from lasku l where l.yhtio = lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu),lasku.tunnus)
				LEFT JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio = versio.yhtio and laskun_lisatiedot.otunnus=versio.tunnus
				LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=laskun_lisatiedot.yhtio and asiakkaan_kohde.tunnus=laskun_lisatiedot.asiakkaan_kohde
				LEFT JOIN kuka laatija ON laatija.yhtio=lasku.yhtio and laatija.kuka=lasku.laatija
				LEFT JOIN yhteyshenkilo ON yhteyshenkilo.yhtio=laskun_lisatiedot.yhtio and yhteyshenkilo.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen
				LEFT JOIN yhteyshenkilo kaupallinen_kasittelija ON kaupallinen_kasittelija.yhtio=laskun_lisatiedot.yhtio and kaupallinen_kasittelija.tunnus=laskun_lisatiedot.yhteyshenkilo_kaupallinen
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tila IN ($laskutilat) and lasku.tunnus = '$tarjous'";
	$laskures = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($laskures) == 0) {
		die("OHO! tälläistä tarjousta ei löydy $tarjous");
	}
	$laskurow = mysql_fetch_array($laskures);
	if($laskurow["tila"] == "T") {
		$laji = "TARJOUS";
	}
	else {
		$laji = "TILAUS";
	}
	
	//	Haetaan kaikki oleellinen data koskien nippua
	$select 	= "	";

	$select2 	= "	kalenteri.laatija	laatija, kalenteri.tunnus	tunnus";

	$qlisa = "";

	//	Järjestetään kaikki meidän tapahtumat
	if($laji == "TARJOUS") {
		$qlisa = "
		UNION
		/*	Uusi tarjous/versio avattu	*/	
		(	
			SELECT 'lasku' laji, 'alku' laji_tark, UNIX_TIMESTAMP(date(luontiaika)) aika, luontiaika, '1' vali, lasku.laatija laatija, lasku.tunnus tunnus
			FROM lasku
			WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tarjous]' and tila IN ($laskutilat)
		)
		UNION					
		/*	Eka kontakti	*/	
		(	
			SELECT 'kontakti_oletettu' laji, 'kontakti_1' laji_tark, UNIX_TIMESTAMP(date(date_add(luontiaika, INTERVAL 14 DAY))) aika, luontiaika, '1' vali, lasku.laatija laatija, lasku.tunnus tunnus
			FROM lasku
			WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]' and tila IN ($laskutilat)
		)					
		UNION
		/*	Toka kontakti	*/	
		(	
			SELECT 'kontakti_oletettu' laji, 'kontakti_2' laji_tark, UNIX_TIMESTAMP(date(date_add(luontiaika, INTERVAL 45 DAY))) aika, luontiaika, '1' vali, lasku.laatija laatija, lasku.tunnus tunnus
			FROM lasku
			WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]' and tila IN ($laskutilat)
		)					
		UNION					
		/*	Viimeinen voitelu	*/	
		(	
			SELECT 'kontakti_oletettu' laji, 'kontakti_3' laji_tark, UNIX_TIMESTAMP(date(date_add(luontiaika, INTERVAL 76 DAY))) aika, luontiaika, '1' vali, lasku.laatija laatija, lasku.tunnus tunnus
			FROM lasku
			WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]' and tila IN ($laskutilat)
		)
		UNION
		/*	Haetaan kaikki kontaktoinnit */
		(
			SELECT 'kontakti' laji, tyyppi laji_tark, UNIX_TIMESTAMP(date(pvmalku)) aika,  luontiaika, '1' vali, kalenteri.laatija laatija, kalenteri.tunnus tunnus
			FROM kalenteri
			WHERE yhtio = '$kukarow[yhtio]' and otunnus IN ($laskurow[versiot]) and tyyppi = 'kontakti_1'
		)
		UNION
		(
			SELECT 'kontakti' laji, tyyppi laji_tark, UNIX_TIMESTAMP(date(pvmalku)) aika,  luontiaika, '1' vali, kalenteri.laatija laatija, kalenteri.tunnus tunnus
			FROM kalenteri
			WHERE yhtio = '$kukarow[yhtio]' and otunnus IN ($laskurow[versiot]) and tyyppi = 'kontakti_2'
		)
		UNION
		(
			SELECT 'kontakti' laji, tyyppi laji_tark, UNIX_TIMESTAMP(date(pvmalku)) aika,  luontiaika, '1' vali, kalenteri.laatija laatija, kalenteri.tunnus tunnus
			FROM kalenteri
			WHERE yhtio = '$kukarow[yhtio]' and otunnus IN ($laskurow[versiot]) and tyyppi = 'kontakti_3'
		)
		";
	}
	elseif($laji == "TILAUS") {
		$qlisa = "
		UNION
		/*	Toimitus Avattu	*/	
		(	
			SELECT 'lasku' laji, 'perustettu' laji_tark, UNIX_TIMESTAMP(date(luontiaika)) aika, luontiaika, '1' vali, lasku.laatija laatija, lasku.tunnus tunnus
			FROM lasku
			WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tarjous]' and tila IN ($laskutilat) and tila NOT IN ('R')
		)		
		UNION
		/*	Toimitus keräykseen	*/	
		(	
			SELECT 'lasku' laji, 'keräys' laji_tark, UNIX_TIMESTAMP(date(kerayspvm)) aika, luontiaika, '1' vali, lasku.laatija laatija, lasku.tunnus tunnus
			FROM lasku
			WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tarjous]' and laskutettu = 0 and tila IN ($laskutilat) and tila NOT IN ('R')
		)
		UNION
		/*	Toimitus toimitettavana	*/	
		(	
			SELECT 'lasku' laji, 'toimitus' laji_tark, UNIX_TIMESTAMP(date(toimaika)) aika, luontiaika, '1' vali, lasku.laatija laatija, lasku.tunnus tunnus
			FROM lasku
			WHERE lasku.yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tarjous]' and laskutettu = 0 and tila IN ($laskutilat) and tila NOT IN ('R')
		)
		UNION
		/*	Toimitus laskutettu	*/	
		(	
			SELECT 'lasku' laji, 'laskutus' laji_tark, UNIX_TIMESTAMP(date(laskutettu)) aika, luontiaika, '1' vali, lasku.laatija laatija, lasku.tunnus tunnus
			FROM lasku
			WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tarjous]' and laskunro > 0 and tila = 'U'
			GROUP BY laskunro
		)
		";
	}
	
	$qlisa .= "
		UNION
		(
			SELECT 'kalenteri' laji, tyyppi laji_tark, UNIX_TIMESTAMP(date(pvmalku)) aika,  luontiaika, DATEDIFF(pvmloppu, pvmalku) vali, laatija, tunnus
			FROM kalenteri
			WHERE yhtio = '$kukarow[yhtio]' and otunnus IN ({$laskurow["versiot"]}) and tyyppi IN ('Memo', 'Muistutus')	
		)";
	$data = kalequery($qlisa);
		
	//	Pudotetaan pois ne jo kontaktoidut tapaukset
	foreach($dada as $aika => $t) {
		foreach($t as $k => $row) {
			if($row["laji"] != "kontakti_oletettu" or ($kontaktit[$row["laji_tark"]] != "ON")) {
				$data["lista"][$aika][$k] = $row;
			} 
		}
	}
	
	if($nakyma != "TAPAHTUMALISTAUS") {
		$fontclass = "info";
	}
	else {
		$fontclass = "";
	}
	/*	
		Luodaan lisää tietoa arrayhin

	foreach($data["lista"] as $aika => &$t) {
		foreach($t as $k => &$kalerow) {
			
			//	Jos tämä on kopioitu aika..
			if($kalerow["aika"] != $aika) {
				continue;
			}
						
			//	Liitetään lisää tapahtumia..
			if($kalerow["vali"] > 1) {
				for($i=1;$i<=$kalerow["vali"]; $i++) {
					$data["lista"][strtotime("+$i days", $aika)][] = &$kalerow;
				}
			}
			
			if($kalerow["laji"] == "kontakti_oletettu") {
				
				$kalerow["otsikko"] = "Kontaktointi";
				
				if($kontaktit[$kalerow["laji_tark"]] != "ON") {
					$query = "	SELECT datediff('".date("Y-m-d", $kalerow["aika"])."', now())";
					$abures = mysql_query($query) or pupe_error($query);
					$aburow = mysql_fetch_array($abures);

					if($aburow[0] > 10){
						$color = "green";
						$teksti = "Tällä nyt ei oo viel kiirus..";
					}
					elseif($aburow[0] >= 5){
						$color = "blue";
						$teksti = "Asiakkaan taustat jo selvitetty..?";
					}
					elseif($aburow[0] >= 1){
						$color = "orange";
						$teksti = "Numero pitäis olla jo valittu..!";
					}
					elseif($aburow[0] >= -1){
						$color = "orange";
						$teksti = "Pitäis olla jo!";
					}
					else {
						$color = "red";
						$teksti = "!#!#!#!!!!!!!#!#!#!#!#!1 13 !";
					}

					$kalerow["ulos"] .= "<font style='color: $color;'>".t($teksti)."</font><div style='text-align: right;'<a href='#' onclick=\"popUp(event, 'ajax_menu', '50', '20', 'tilaushakukone.php?tarjous=$tarjous&toim=$toim&setti=$setti&nakyma=$nakyma&tee=KALENTERITAPAHTUMA&tyyppi=$kalerow[laji_tark]&otunnus=$laskurow[tunnus]&pvm=".date("Y-m-d", $kalerow["aika"])."&liitostunnus=$laskurow[liitostunnus]'); return false;\"><font class='$fontclass'>".t("Kuittaa suoritetuksi")."</font></a><div>";
				}
				else {
					$kalerow["laji"] = "SKIPPAA";
				}
			}
		}
	}
	*/

	//echo "<pre>".print_r($data, true)."</pre>";
	//echo "<pre>".print_r($kontaktit, true)."</pre>";
		
	// Haetaan kalenterimerkinnät
	$query = "	SELECT count(*) tapahtumia
				FROM kalenteri
				WHERE yhtio = '$kukarow[yhtio]' and otunnus IN ($laskurow[versiot])";
	$abures = mysql_query($query) or pupe_error($query);
	$aburow = mysql_fetch_array($abures);
	$laskurow["tapahtumia"] = (int) $aburow["tapahtumia"];

	$query = "	SELECT tunnus, kuka, nimi
				FROM kuka
				WHERE yhtio = '$kukarow[yhtio]' and asema = 'PP'
				ORDER BY nimi";
	$yresult = mysql_query($query) or pupe_error($query);
	$ppaall = "";
	while($row = mysql_fetch_array($yresult)) {
		$sel = "";
		if ($row["kuka"] == $laskurow["projektipaallikko"]) {
			$sel = 'selected';
		}
		$ppaall .= "<option value='$row[kuka]' $sel>{$row["nimi"]}</option>\n";
	}
	
	echo "	<center>
			<table width = '600'>
				<caption>".t("Perustiedot")."</caption>
				<tr>
					<td class='back' colspan='10'>
						<table width = '600'>
							<tr>
								<th>".t("Asiakas")."</th>
								<th>".t("Toimitusosoite")."</th>
								<th>".t("Laskutusosoite")."</th>						
							</tr>
							<tr>
								<td>{$laskurow["nimi"]} {$laskurow["nimitark"]}<br>{$laskurow["osoite"]}<br>{$laskurow["postino"]} {$laskurow["postitp"]}<br>{$laskurow["maa"]}</td>
								<td>{$laskurow["toim_nimi"]} {$laskurow["toim_nimitark"]}<br>{$laskurow["toim_osoite"]}<br>{$laskurow["toim_postino"]} {$laskurow["toim_postitp"]}<br>{$laskurow["toim_maa"]}</td>
								<td>{$laskurow["laskutus_nimi"]} {$laskurow["laskutus_nimitark"]}<br>{$laskurow["laskutus_osoite"]}<br>{$laskurow["laskutus_postino"]} {$laskurow["laskutus_postitp"]}</td>
							</tr>										
						</table>
					</td>
				</tr>
				<tr>
					<th>".t("Avannut")."</th>
					<th>".t("Avattu")."</th>
					<th>".t("Projektipäällikkö")."</th>
					<th>".t("Toimituksia")."</th>
					<th>".t("Seuraava toimitus")."</th>
				</tr>
				<tr>
					<td>{$laskurow["laatija_nimi"]}</td>
					<td>{$laskurow["avattu"]}</td>
					<td>
						<select id='pp' name='projektipaallikko' onchange=\"sndReq('tarjouskalenteri_$tarjous', 'tilaushakukone.php?ohje=off&toim=$toim&setti=$setti&tee=VAIHDAPP&nakyma=$vaihda&tarjous=$tarjous&projektipaallikko='+this.options[this.selectedIndex].value, 'kasittele_$tarjous', true);\">
						<option value = ''>".t("Valitse projektipäällikkö")."</option>
						$ppaall
						</select>
					</td>
					<td>{$laskurow["versioita"]}</td>						
					<td><font style='color: ".color_code($laskurow["seuraava_aika"], 20).";'>{$laskurow["seuraava"]}</font></td>
				</tr>
				<tr>
					<th colspan='2'>".t("Tekninen yhteyshenkilö")."</th>
					<th colspan='2'>".t("Kaupallinen käsittelijä")."</th>
					<th>".t("Yhteydenottoja")."</th>
				</tr>
				<tr>
					<td colspan='2'>{$laskurow["yhteyshenkilo_nimi"]}<br>{$laskurow["yhteyshenkilo_email"]}<br>{$laskurow["yhteyshenkilo_puh"]}</td>
					<td colspan='2'>{$laskurow["kaupallinen_kasittelija_nimi"]}<br>{$laskurow["kaupallinen_kasittelija_email"]}<br>{$laskurow["kaupallinen_kasittelija_puh"]}</td>
					<td>{$laskurow["tapahtumia"]}</td>						
				</tr>
			</table>";
	
	$summa_laskutettu = $summa_saatu = $laskutettu_kpl = $saatu_kpl = 0;
	$maksuerittely = "
		<table width='600'>
			<caption>".t("Maksuerittely")."</caption>";
			
	if($laskurow["jaksotettu"]<>0) {
		$query = "	SELECT maksupositio.*, round(osuus,0) osuus,
					if(lasku.laskutettu>0, myre.summa, maksupositio.summa) summa,
					concat_ws(' ', maksuehto.teksti, kuvaus) maksuehto, 
					maksupositio.lisatiedot, maksupositio.ohje,
					if(lasku.laskunro>0,lasku.laskunro, '') laskunro, 
					if(myre.tapvm!='0000-00-00', date_format(myre.tapvm, '%d.%m.%Y'), '') tapvm,
					if(myre.erpcm!='0000-00-00', date_format(myre.erpcm, '%d.%m.%Y'), '') erpcm,
					datediff(myre.erpcm, now()) pva,
					if(myre.mapvm!='0000-00-00', date_format(myre.mapvm, '%d.%m.%Y'), '') mapvm
					FROM maksupositio
					LEFT JOIN lasku ON lasku.yhtio=maksupositio.yhtio and lasku.tunnus=maksupositio.uusiotunnus
					LEFT JOIN lasku myre ON myre.yhtio=lasku.yhtio and myre.laskunro=lasku.laskunro and myre.tila='U'
					LEFT JOIN maksuehto on maksuehto.yhtio=maksupositio.yhtio and maksuehto.tunnus=maksupositio.maksuehto
					WHERE maksupositio.yhtio = '$kukarow[yhtio]' and otunnus = '$laskurow[jaksotettu]'
					ORDER BY tunnus";
		$maksusopres = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($maksusopres) > 0) {
			$maksuteksti = t("Maksusopimus");
			$maksuerittely .= "
			<tr>
				<th>#</th><th>".t("Osuus")."</th><th>".t("Maksatustiedot")."</th><th>".t("Summa")."</th><th>".t("Laskunro")."</th><th>".t("Laskun pvm")."</th><th>".t("Eräpäivä")."</th><th>".t("Maksupäivä")."</th>
			</tr>";
			while($maksusoprow = mysql_fetch_array($maksusopres)) {
				$i++;
				if(trim($maksusoprow["lisatiedot"]) != "") $maksusoprow["lisatiedot"] = "<br><em>$maksusoprow[lisatiedot]</em>";
				else $maksusoprow["lisatiedot"] = "";

				if(trim($maksusoprow["ohje"]) != "") $maksusoprow["ohje"] = "<br><b>$maksusoprow[ohje]</b>";
				else $maksusoprow["ohje"] = "";

				if($maksusoprow["mapvm"] == "") {
					$vari = "style='color: ".color_code($maksusoprow["pva"]+7, 14).";'";
				}
				else {
					$vari = "";
				}

				$maksuerittely .= "
					<tr class='aktiivi'>
						<td>$i</td>
						<td align='center'>{$maksusoprow[osuus]}</td>
						<td>{$maksusoprow[maksuehto]}{$maksusoprow["lisatiedot"]}{$maksusoprow["ohje"]}</td>
						<td align='right' NOWRAP>".number_format($maksusoprow["summa"], 2, ',', ' ')."</td>
						<td align='center' NOWRAP>{$maksusoprow[laskunro]}</td>
						<td align='center' NOWRAP>{$maksusoprow[tapvm]}</td>
						<td align='center' $vari NOWRAP>{$maksusoprow[erpcm]}</td>	
						<td align='center' NOWRAP>{$maksusoprow[mapvm]}</td>					
					</tr>";
					
				if($maksusoprow["laskunro"] > 0) {
					$summa_laskutettu+=$maksusoprow["summa"];
					$laskutettu_kpl++;					
				}
				if($maksusoprow["mapvm"] != "") {
					$summa_saatu+=$maksusoprow["summa"];
					$saatu_kpl++;					
				}
			}
		}
		else {
			$maksuteksti = "<font class='error'>".t("MAKSUSOPIMUS PUUTTUU!!")."</font>";
		}
	}		
	else {
		
		$maksuteksti = t("Toimituksesta");
		
		$summaquery = "sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))";
		$query = "	SELECT 	lasku.tunnus,
							maksuehto.teksti,
							if(lasku.laskutettu>0, myre.summa, (
								SELECT $summaquery
								FROM tilausrivi
								WHERE tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi != 'D')
							) summa, 
							if(lasku.laskunro>0,lasku.laskunro, '') laskunro, 
							if(myre.tapvm!='0000-00-00', date_format(myre.tapvm, '%d.%m.%Y'), '') tapvm,
							if(myre.erpcm!='0000-00-00', date_format(myre.erpcm, '%d.%m.%Y'), '') erpcm,
							datediff(myre.erpcm, now()) pva,
							if(myre.mapvm!='0000-00-00', date_format(myre.mapvm, '%d.%m.%Y'), '') mapvm
					FROM lasku
					LEFT JOIN lasku myre ON myre.yhtio=lasku.yhtio and lasku.laskunro > 0 and myre.laskunro=lasku.laskunro and myre.tila='U'
					LEFT JOIN maksuehto on maksuehto.yhtio=lasku.yhtio and maksuehto.tunnus=lasku.maksuehto
					WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnusnippu = '$tarjous' and lasku.tila in ($laskutilat)
					ORDER BY lasku.laskunro DESC";
		$maksusopres = mysql_query($query) or pupe_error($query);		
		
		if(mysql_num_rows($maksusopres)>0) {
			$maksuerittely .= "
				<tr>
					<th>".t("Toimitus")."</th><th>".t("Maksatustiedot")."</th><th>".t("Summa")."</th><th>".t("Laskunro")."</th><th>".t("Laskun pvm")."</th><th>".t("Eräpäivä")."</th><th>".t("Maksupäivä")."</th>
				</tr>";

			while($maksusoprow = mysql_fetch_array($maksusopres)) {
				if($maksusoprow["mapvm"] == "") {
					$vari = "style='color: ".color_code($maksusoprow["pva"]+7, 14).";'";
				}
				else {
					$vari = "";
				}
			
				$maksuerittely .= "
					<tr class='aktiivi'>
						<td align='center'>{$maksusoprow["tunnus"]}</td>
						<td>{$maksusoprow["teksti"]}</td>
						<td align='right' NOWRAP>".number_format($maksusoprow["summa"], 2, ',', ' ')."</td>
						<td align='center' NOWRAP>{$maksusoprow["laskunro"]}</td>
						<td align='center' NOWRAP>{$maksusoprow["tapvm"]}</td>
						<td align='center' $vari NOWRAP>{$maksusoprow["erpcm"]}</td>	
						<td align='center' NOWRAP>{$maksusoprow["mapvm"]}</td>					
					</tr>";

				if($maksusoprow["laskunro"] > 0) {
					$summa_laskutettu+=$maksusoprow["summa"];
					$laskutettu_kpl++;					
				}
				if($maksusoprow["mapvm"] != "") {
					$summa_saatu+=$maksusoprow["summa"];
					$saatu_kpl++;					
				}
			}
		}
		else {
			$maksuerittely .= "
				<tr class='aktiivi'>
					<td><font class=''>".t("Yhtään toimitusta ei vielä lähetetty")."</font></td>
				</tr>";						
		}
	}	
	$maksuerittely .= "</table>";
	
	if($laji == "TILAUS") {
		$summaquery = "sum(tilausrivi.hinta * if(tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))";
		$query = "	SELECT $summaquery summa
					FROM lasku
					LEFT JOIN tilausrivi ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi != 'D'
					WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tunnusnippu = '$tarjous' and lasku.tila IN ($laskutilat)";
		$summares = mysql_query($query) or pupe_error($query);
		$summarow = mysql_fetch_array($summares);

		echo "
			<table width='600'>
			<caption>".t("Maksuyhteenveto")."</caption>
			<tr class='aktiivi'>
				<td class='tumma'>".t("Arvo eur")."</td>
				<td align='right'>".number_format($summarow["summa"], 2, ',', ' ')."</td>
				<td class='tumma'>".t("Laskutettu")."</td>
				<td align='right'>".number_format($summa_laskutettu, 2, ',', ' ')."</td>
				<td align='center'>".number_format(100*$summa_laskutettu/$summarow["summa"], 2, ',', ' ')."%</td>
				<td class='tumma'>".t("Laskuttamatta")."</td>
				<td align='right'>".number_format($summarow["summa"]-$summa_laskutettu, 2, ',', ' ')."</td>
			</tr>
			<tr class='aktiivi'>
				<td class='tumma'>".t("Tapa").":</td>
				<td align='right'>$maksuteksti</td>
				<td class='tumma'>".t("Maksettu")."</td>
				<td align='right'>".number_format($summa_saatu, 2, ',', ' ')."</td>
				<td align='center'>".number_format(100*$summa_saatu/$summa_laskutettu, 2, ',', ' ')."%</td>
				<td class='tumma'>".t("Saamatta")."</td>
				<td align='right'>".number_format($summa_laskutettu-$summa_saatu, 2, ',', ' ')."</td>
			</tr>
			<tr class='aktiivi'>
				<td class='back' colspan='7' align='right'><a href=\"javascript:showhide('maksuerittely_{$laskurow["tunnus"]}');\">".t("Näytä/Piilota maksuerittely")."</a></td>
			</tr>
			</table>";

		echo "
			<div id='maksuerittely_{$laskurow["tunnus"]}' style='display: none;'>
				$maksuerittely
			</div>";
			
	}
	elseif($laji == "TARJOUS") {
		echo "
		$maksuerittely
		";
	}
	
	$query = "	SELECT avainsana.selite, (select tunnus from liitetiedostot where liitetiedostot.yhtio = avainsana.yhtio and liitos = 'LASKU' and kayttotarkoitus=avainsana.selite LIMIT 1) liite
				FROM avainsana
				WHERE yhtio = '$kukarow[yhtio]' and avainsana.laji='TIL-LITETY' and avainsana.selitetark_2='PAKOLLINEN'
				HAVING liite IS NULL";
	$liiteres = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($liiteres) > 0) {
		$class = "error";		
	}
	else $class = "message";

	$menu = array();
	$query = "	SELECT tunnus, luontiaika, laatija, selite, filename
				FROM liitetiedostot
				WHERE yhtio = '$kukarow[yhtio]' and liitos = 'LASKU' and liitostunnus IN ($laskurow[versiot])
				ORDER BY kayttotarkoitus";
	$liiteres = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($liiteres)>0) {
		$kayttotarkoitus = "";
		while($liiterow = mysql_fetch_array($liiteres)) {
			if($edkt != $liiterow["kayttotarkoitus"]) {
				$query = " select selitetark from avainsanat where yhtio='$kukarow[yhtio]' and laji='TIL-LITETY' and selite = '$liiterow[kayttotarkoitus]'";
				$res = mysql_query($query) or pupe_error($query);
				$r = mysql_fetch_array($res);
				$menu[] = array("VALI" => $r["selitetark"]);
			}

			$menu[] = array("TEKSTI" => $liiterow["selite"], "HREF" => "../view.php?id=$liiterow[tunnus]", "TARGET" => "_blank");

			$edkt = $row["kayttotarkoitus"];
		}		
	}

	$menu[]	= array("VALI" => "Toiminnot");
	$menu[] = array("TEKSTI" => "Muokkaa liitteitä", "HREF" => "../liitetiedostot.php?liitos=lasku&id=$tarjous&lopetus=".urlencode("raportit/tilaushakukone.php?toim=$toim&setti=$setti&hakukysely=$hakukysely&hakupalkki=OHI&aja_kysely=tmpquery&tarjous=$tarjous"), "TARGET" => "page");
	
	$data["tiedot"]["menut"][] = tee_menu($menu, "<font class='$class'>".t("Liitetiedostot")."</font>");
	
	echo "<table>
		<tr>
			<td class='back'>".kalenteri($data)."</td>
		</tr>
		</table>";
	
	echo "</center>";

	if($lopetus != "") {

		echo "
		</div>
		<form name='lopetusformi' action='$lopetus' method='post'>
			<input type='submit' value='".t("Siirry sinne mistä tulit")."'>
		</form>";
	}
}

if($tee == "") {
	if($toim == "TARJOUSHAKUKONE" or $toim == "TILAUSHAKUKONE") {
		
		if($excel == "YES") {
			if(include_once('Spreadsheet/Excel/Writer.php')) {

				//keksitään failille joku varmasti uniikki nimi:
				$excelnimi = "Tilausraportti_".date("d-m-Y")."_".mt_rand(0,9999).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Raportti');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;		
			}	
		}
		
		if($setti == "viikkis") {
			$alatila_tilaus		= array("A", "B", "C", "D", "E", "V", "J", "F", "T", "U");
			$alatila_projekti	= array("", "A", "B");						
			$hakupalkki 		= "OHI";
			$hakukysely			= "";
		}
		elseif($setti == "omat") {
			if($toim == "TILAUSHAKUKONE") {
				$alatila		= array("A", "B", "C", "D", "E", "V", "J", "F", "T", "U");
			}
			elseif($toim == "TARJOUSHAKUKONE") {
				$alatila		= array("", "B");
			}
			$laatija			= array($kukarow["kuka"]);
			$hakupalkki 		= "mini";
			$hakukysely			= "";			
		}
		
		aja_kysely();

		if($hakupalkki != "OHI") {
			
			if($hakupalkki == "mini") {
				echo "<br><a href=\"javascript:showhide('hakupalkki');\">".t("Näytä hakukriteerit")."</a><br><br><div id = 'hakupalkki' style='display:none'>";
			}
			
			if($toim == "TARJOUSHAKUKONE") {
				$isa_teksti 	= "Tarjous";
				$lapsi_teksti 	= "Versio";
				$taulu			= "tarjous";
				$taulu2			= "versio";	
			}
			else {
				$isa_teksti 	= "Tilaus";
				$lapsi_teksti 	= "Toimitus";
				$taulu			= "tilaus";
				$taulu2			= "tilaus";								
			}
			
			echo "	<font class='message'>".t("Anna hakuparametrit").":</font>
					<form method='post' id='tiedot_form'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='hakupalkki' value='mini'>
					<table width='600px'>";
			
			if($toim == "TARJOUSHAKUKONE") {
				echo "	
						<tr>
							<th>".t("Seuranta")."</th><th>".t("Laatija")."</th><th>".t("Myyjä")."</th>
						</tr>
						<tr>";
			}
			elseif($toim == "TILAUSHAKUKONE") {
				echo "
						<tr>
							<th>".t("Seuranta")."</th><th>".t("Laatija")."</th><th>".t("Myyjä")."</th>
						</tr>
						<tr>";
			}
			
			//seuranta
			$query = "	SELECT selite, concat_ws(' - ',selite, selitetark)
						FROM avainsana
						WHERE yhtio='$kukarow[yhtio]' and laji='SEURANTA'
						ORDER BY jarjestys, selitetark";
			$mryresult = mysql_query($query) or pupe_error($query);
			echo "		<td>
							<select name='seuranta[]' multiple='TRUE' size='8'>";
			while($row = mysql_fetch_array($mryresult)) {
				$sel = "";
				if (array_search($row[0], $seuranta) !== false) {
					$sel = 'selected';
				}
				echo "		<option value='$row[0]' $sel>$row[1]</option>";
			}
			
			echo "			<option value='TYHJA'>".t("Seuranta puuttuu")."</option>
							</select>
							<br>
							".t("Seurannoittain").": <input type='checkbox' name='group[laskun_lisatiedot.seuranta]' value='checked' {$group["laskun_lisatiedot.seuranta"]}> prio: <input type='text' name='prio[laskun_lisatiedot.seuranta]' value='{$prio["laskun_lisatiedot.seuranta"]}' size='2'>
						</td>";


			$query = "	SELECT distinct(kuka) kuka, kuka.nimi nimi
						FROM lasku
						JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.kuka = lasku.laatija
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila IN ($laskutilat)";
			$abures = mysql_query($query) or pupe_error($query);

			echo "		<td>
							<select name='laatija[]' multiple='TRUE' size='8'>";
			while($row = mysql_fetch_array($abures)) {
				$sel = "";
				if (array_search($row[0], $laatija) !== false) {
					$sel = 'selected';
				}
				echo "		<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "			</select>
							<br>
							".t("Laatijoittain").": <input type='checkbox' name='group[lasku.laatija]' value='checked' {$group["lasku.laatija"]}> prio: <input type='text' name='prio[lasku.laatija]' value='{$prio["lasku.laatija"]}' size='2'>
			
						</td>";

			$query = "	SELECT distinct(lasku.myyja) myyja, kuka.nimi
						FROM lasku
						JOIN kuka ON kuka.yhtio=lasku.yhtio and kuka.tunnus = lasku.myyja
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila IN ($laskutilat)";
			$abures = mysql_query($query) or pupe_error($query);
			echo "		<td>
							<select name='myyja[]' multiple='TRUE' size='8'>";
			while($row = mysql_fetch_array($abures)) {
				$sel = "";
				if (array_search($row[0], $myyja) !== false) {
					$sel = 'selected';
				}
				echo "		<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "			</select>
							<br>
							".t("Myyjittäin").": <input type='checkbox' name='group[lasku.myyja]' value='checked' {$group["lasku.myyja"]}> prio: <input type='text' name='prio[lasku.myyja]' value='{$prio["lasku.myyja"]}' size='2'>
						</td>
						</tr>
						</table>
						<br>
						<table width='600px'>";


			if($toim == "TARJOUSHAKUKONE") {
				echo "
						<tr>
							<th>".t("Tila")."</th><th>".t("Vienti")."</th><th>".t("Maa")."</th>
						</tr>
						<tr>";
			}
			elseif($toim == "TILAUSHAKUKONE") {
				echo "
						<tr>
							<th>".t("Tilauksen tila")."</th><th>".t("Projektin tila")."</th><th>".t("Vienti")."</th><th>".t("Maa")."</th>
						</tr>
						<tr>";
			}


			if($toim == "TARJOUSHAKUKONE") {
				$sel =  array();
				if(count($alatila) > 0) {
					foreach($alatila as $t) {
						$sel[$t] = "SELECTED";
					}		
				}				
				echo "		<td>
								<select name='alatila[]' multiple='TRUE' size='8'>
								<option value='' {$sel[""]}>".t("Kesken")."</option>
								<option value='A' {$sel["A"]}>".t("Tulostettu")."</option>
								<option value='B' {$sel["B"]}>".t("Hyväksytty")."</option>
								<option value='T' {$sel["T"]}>".t("Tilaus tehty")."</option>
								<option value='X' {$sel["X"]}>".t("Hylätty")."</option>
								</select>
							</td>";
			}
			elseif($toim == "TILAUSHAKUKONE") {
				$sel =  array();
				if(count($alatila_tilaus) > 0) {
					foreach($alatila_tilaus as $t) {
						$sel[$t] = "SELECTED";
					}		
				}
								
				echo "		<td>
								<select name='alatila_tilaus[]' multiple='TRUE' size='8'>
								<optgroup label='".("Myyntitilaus tuotannossa")."'>
									<option value='A' {$sel["A"]}>".t("Keräyslista tulostettu/tulostusjonossa")."</option>
									<option value='B' {$sel["B"]}>".t("Rahtikirjatiedot syötetty")."</option>
									<option value='C' {$sel["C"]}>".t("Kerätty")."</option>
									<option value='D' {$sel["D"]}>".t("Toimitettu")."</option>
									<option value='E' {$sel["E"]}>".t("Vientitiedot syötetty")."</option>
									<option value='V' {$sel["V"]}>".t("Laskutusvalmis")."</option>
									<option value='X' {$sel["X"]}>".t("Laskutettu")."</option>																																
								</optgroup>
								<optgroup label='".("Myyntitilaus kesken")."'>
									<option value='' {$sel[""]}>".t("Kesken")."</option>
									<option value='J' {$sel["J"]}>".t("JT Poiminnassa")."</option>
									<option value='E' {$sel["E"]}>".t("Ennakko poiminnassa")."</option>
									<option value='F' {$sel["F"]}>".t("Odottaa hyväksyntää")."</option>
									<option value='T' {$sel["T"]}>".t("Odottaa JT-tuotteita")."</option>
									<option value='U' {$sel["U"]}>".t("Kokonaistoimitus odottaa JT-tuotteita")."</option>
								</optgroup>
								</select>
							</td>";
				$sel =  array();
				if(count($alatila_projekti) > 0) {
					foreach($alatila_projekti as $t) {
						$sel[$t] = "SELECTED";
					}		
				}
				echo "		<td>
								<select name='alatila_projekti[]' multiple='TRUE' size='8'>
								<option value='' {$sel[""]}>".t("Projekti kesken")."</option>
								<option value='A' {$sel["A"]}>".t("Projekti aktiivisena")."</option>
								<option value='B' {$sel["B"]}>".t("Projekti valmis")."</option>
								<option value='X' {$sel["X"]}>".t("Projekti suljettu")."</option>
								</select>
							</td>";							
			}
			
			$sel =  array();
			if(count($vienti) > 0) {
				foreach($vienti as $t) {
					$sel[$t] = "SELECTED";
				}		
			}
			
			echo "		<td>
							<select name='vienti[]' multiple='TRUE' size='8'>
							<option value='' {$sel[""]}>".t("Kotimaa")."</option>
							<option value='E' {$sel["E"]}>".t("Eu")."</option>
							<option value='K' {$sel["K"]}>".t("Ei-Eu")."</option>
							</select>
							<br>
							".t("Vienneittäin").": <input type='checkbox' name='group[lasku.vienti]' value='checked' {$group["lasku.vienti"]}> prio: <input type='text' name='prio[lasku.vienti]' value='{$prio["lasku.vienti"]}' size='2'>
						</td>";


			$query = "	SELECT distinct(maa)
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and tila = 'T' and maa != ''";
			$result = mysql_query($query) or pupe_error($query);
			$maat = "";
			while($maarow = mysql_fetch_array($result)) {
				$maat .= "<option value='$maarow[maa]'>".maa($maarow[maa])."</option>";
			}

			$sel =  array();					
			if(count($vienti) > 0) {
				foreach($vienti as $t) {
					$sel[$t] = "SELECTED";
				}		
			}
			echo "		<td>
							<select name='maa[]' multiple='TRUE' size='8'>
							$maat
							</select>
							<br>
							".t("Maittain").": <input type='checkbox' name='group[lasku.maa]' value='checked' {$group["lasku.maa"]}> prio: <input type='text' name='prio[lasku.maa]' value='{$prio["lasku.maa"]}' size='2'>
						</td>";
						
			echo "	</tr>
					</table>
					<br>
					<br>
					<table>
						<tr>
							<td class='back'>
								<table>
								<caption style='text-align: center;'>Rajaa aika</caption>
								<tr>
									<th>".t("Alkupäivä")."</th><th>".t("Loppupäivä")."</th<td></td>
								</tr>
								<tr>
									<td>
										<input type='text' name='ppa' value='$ppa' size='3'>
										<input type='text' name='kka' value='$kka' size='3'>
										<input type='text' name='vva' value='$vva' size='5'>
									</td>
									<td>
										<input type='text' name='ppl' value='$ppl' size='3'>
										<input type='text' name='kkl' value='$kkl' size='3'>
										<input type='text' name='vvl' value='$vvl' size='5'>
									</td>
								</tr>

								<tr>
									<th colspan = '2'>".t("Ajalta")."</th>
								</tr>";

							$sel =  array();
							$sel[$viimeisin] = "SELECTED";
							
							$sel2 =  array();
							$sel2[$tapahtuma] = "SELECTED";								
							echo "
								<tr>
									<td colspan = '2'>
										<select name='viimeisin'>
										<option value=''>".t("Valitse aika")."</option>
										<option value='1' $sel[1]>".t("Viimeisin viikko")."</option>
										<option value='2' $sel[2]>".t("Viimeiset 2 viikkoa")."</option>
										<option value='4' $sel[4]>".t("Viimeisin kuukausi")."</option>
										<option value='8' $sel[8]>".t("Viimeisin 2 kuukautta")."</option>
										<option value='16' $sel[16]>".t("Viimeisin 4 kuukautta")."</option>
										</select>
									</td>
								</tr>

								<tr>
									<th colspan = '2'>".t("Tapahtuma")."</th>
								</tr>
								<tr>";
								
								if($toim == "TARJOUSHAKUKONE") {
									echo "
										<td colspan = '2'>
											<select name='tapahtuma'>
											<option value=''>".t("Viimeisin tarjous tehty")."</option>
											<option value='A' {$sel2["A"]}>".t("Versio avattu")."</option>
											<option value='K' {$sel2["K"]}>".t("Viimeisin kalenteritapahtuma")."</option>										
											</select>
										</td>";									
								}
								elseif($toim == "TILAUSHAKUKONE") {
									echo "
										<td colspan = '2'>
											<select name='tapahtuma'>
											<option value='A' {$sel2["A"]}>".t("Tilaus avattu")."</option>
											<option value='K' {$sel2["K"]}>".t("Viimeisin kalenteritapahtuma")."</option>										
											</select>
										</td>";									
								}
								echo "
								</tr>
								</table>
							</td>
							
							<td class='back' valign = 'top' width='10'></td>
							
							<td class='back'>";

							if($toim == "TARJOUSHAKUKONE") {
								$sel =  array();
								$sel[$voimassa] = "SELECTED";
								
								echo "
								<table>
								<caption style='text-align: center;'>Rajaa jaksoihin</caption>
								<tr>
									<th colspan = '2'>".t("Voimassa")."</th>
								</tr>
								<tr>
									<td colspan = '2'>
										<select name='voimassa'>
										<option value='' {$sel[""]}>".t("Valitse voimassaoloaika")."</option>
										<option value='0' {$sel["0"]}>".t("On jo umpeutunut")."</option>
										<option value='1' {$sel["1"]}>".t("Umpeutuu viikon kuluessa")."</option>
										<option value='2' {$sel["2"]}>".t("Umpeutuu 2 viikon kuluessa")."</option>
										<option value='4' {$sel["4"]}>".t("Umpeutuu 4 viikon kuluessa")."</option>
										<option value='8' {$sel["8"]}>".t("Umpeutuu 2 kk kuluessa")."</option>									
										</select>
									</td>
								</tr>
								</table>";
							}
							elseif($toim == "TILAUSHAKUKONE") {
								echo "
								<table>
								<tr>
									<td class='back' width='150'>&nbsp;</td>
								</tr>
								</table>";
							}
							
							echo "
							</td>
							
							<td class='back' valign = 'top' width='10'>&nbsp;</td>
							
							<td class='back'>
								<table>
								<caption style='text-align: center;'>Muut rajaukset</caption>
								<tr>
									<th>".t("Summa min")."</th><th>".t("Summa max")."</th<td></td>
								</tr>

								<tr>
									<td>
										<input type='text' name='summamin' value='$summamin' size='10'>
									</td>
									<td>
										<input type='text' name='summamax' value='$summamax' size='10'>
									</td>
								</tr>

								<tr>
									<th colspan = '2'>".t("Lukumäärä")."</th>
								</tr>

								<tr>
									<td colspan = '2'>
										<input type='text' name='lkm' value='$lkm' size='5'>
									</td>
								</tr>
								</table>
							</td>	
						</tr>
						<tr>
							<td class='back' colspan = '6'><br>
								<table>
								<caption style='text-align: center;'>Järjestely</caption>
								<tr>
								<tr>
									<th>".t("1. Sarake")."</th><th>".t("2. Sarake")."</th><th>".t("3. Sarake")."</th>
								</tr>
								";
								
								for($i=0;$i<3;$i++) {
									
									if(!isset($jarj[$i]) and $i == 0) $jarj[$i] = "tarjous";
									
									$sel =  array();
									$sel[$jarj[$i]] = "SELECTED";

									if(!isset($suunta[$i])) $suunta[$i] = "DESC";
									$sel2 =  array();
									$sel2[$suunta[$i]] = "SELECTED";
									$lisat = "";
									if($toim == "TARJOUSHAKUKONE") {
										
										$lisat = "	<option value='tunnus' {$sel["tunnus"]}>".t("Tarjous")."</option>
													<option value='lasku.nimi' {$sel["lasku.nimi"]}>".t("Asiakas")."</option>";
									} 
									elseif($toim == "TILAUSHAKUKONE") {
										$lisat = "	<option value='tunnus' {$sel["tunnus"]}>".t("Tilaus")."</option>
													<option value='lasku.nimi' {$sel["lasku.nimi"]}>".t("Asiakas")."</option>";
									}
									
									echo "
										<td>
											<select name='jarj[$i]'>
												<option value=''>".t("Valitse sarake")."</option>
												$lisat
												<option value='seuranta' {$sel["seuranta"]}>".t("Seuranta")."</option>
												<option value='laskun_lisatiedot.asiakkaan_kohde' {$sel["laskun_lisatiedot.asiakkaan_kohde"]}>".t("Kohde")."</option>
												<option value='lasku.laatija' {$sel["lasku.laatija"]}>".t("Laatija")."</option>
												<option value='lasku.myyja' {$sel["lasku.myyja"]}>".t("Myyja")."</option>																																			
												<option value='summa_' {$sel["summa_"]}>".t("Summa")."</option>
												<option value='pva' {$sel["pva"]}>".t("Voimassa")."</option>
												<option value='maa' {$sel["maa"]}>".t("Maa")."</option>
											</select>
											<select name='suunta[$i]'>
												<option value='DESC'  {$sel2["DESC"]}>".t("Laskeva")."</option>
												<option value='ASC' {$sel2["ASC"]}>".t("Nouseva")."</option>												
											</select>
										</td>";
								}
								echo "
								</tr>
								</table>							
							</td>
						</tr>
						<tr>
							<td class='back' colspan = '6'><br>
								<table>
								<caption style='text-align: center;'>Muut valinnat</caption>
								<tr>
									<th>".t("Tallenna excel").":</th><td><input type='checkbox' name='excel' value='YES'></td>
								</tr>
								</table>
							</td>
						<tr><td class='back' colspan = '6'><br>".nayta_kyselyt($hakukysely)."<br></td></tr>
						<tr><td class='back'><input type = 'submit' value='".t("Aja kysely")."'></td></tr>
					</table>
					<br>
					</form>";
		}
		
		if($hakupalkki == "mini") {
			echo "<br><a href=\"javascript:showhide('hakupalkki');\">".t("Piilota hakukriteerit")."</a>
			</div><br>";
		}
		
		$lasku_rajaus = $lisatiedot_rajaus = $versio_rajaus = $having_rajaus = $lkm_rajaus = "";
		
		if(is_numeric($voimassa)) {
			$having_rajaus .= " and pva < ".($voimassa * 7);
			
			//	Viritetään alatila
			$alatila = array("", "A");
		}
		
		if(count($seuranta) > 0) {
			$lisatiedot_rajaus .= " and laskun_lisatiedot.seuranta IN ('".implode("','", $seuranta)."')";
		}
		
		if(count($laatija) > 0) {
			$lasku_rajaus .= " and lasku.laatija IN ('".implode("','", $laatija)."')";
		}

		if(count($myyja) > 0) {
			$lasku_rajaus .= " and lasku.myyja IN ('".implode("','", $myyja)."')";
		}

		if(count($alatila) > 0) {
			$lasku_rajaus .= " and lasku.alatila IN ('".implode("','", $alatila)."')";
		}
		
		if(count($alatila_tilaus) > 0 and count($alatila_projekti) > 0) {
			$lasku_rajaus .= " and 	((lasku.tila IN ('N', 'L') and lasku.alatila IN ('".implode("','", $alatila_tilaus)."')) 
										or 
									(lasku.tila = 'R' and lasku.alatila IN ('".implode("','", $alatila_projekti)."')))";
		}
		elseif(count($alatila_tilaus) > 0) {
			$lasku_rajaus .= " and 	lasku.tila IN ('N', 'L') and lasku.alatila IN ('".implode("','", $alatila_tilaus)."') and lasku.tila != 'R'";
		}
		elseif(count($alatila_projekti) > 0) {
			$lasku_rajaus .= " and 	lasku.tila = 'R' and lasku.alatila IN ('".implode("','", $alatila_projekti)."') and lasku.tila NOT IN ('N','L')";
		}
		
		if(count($vienti) > 0) {
			$lasku_rajaus .= " and lasku.vienti IN ('".implode("','", $vienti)."')";
		}

		if(count($maa) > 0) {
			$lasku_rajaus .= " and lasku.maa IN ('".implode("','", $maa)."')";
		}

		if($viimeisin > 0) {
			if($tapahtuma == "A") {
				$lasku_rajaus .= " and lasku.luontiaika >= DATE_SUB(now(), INTERVAL $viimeisin WEEK)";
			}
			elseif($tapahtuma == "K") {
				$having_rajaus .= " and viimeisin_kaletapahtuma >= DATE_SUB(now(), INTERVAL $viimeisin WEEK)";
				$viimeisin_kaletapahtuma = "OK";
			}
			else {
				$versio_rajaus .= " and versio.luontiaika >= DATE_SUB(now(), INTERVAL $viimeisin WEEK)";
			}
			
			$ppa = $kka = $vva = $ppl = $kkl = $vvl = "";
		}
		
		if($ppa > 0 and $kka > 0 and $vva > 0) {
			$ppa = sprintf("%02d", $ppa);
			$kka = sprintf("%02d", $kka);
			if($tapahtuma == "A") {
				$lasku_rajaus .= " and lasku.luontiaika >= '$vva-$kka-$ppa'";
			}
			elseif($tapahtuma == "K") {
				$having_rajaus .= " and viimeisin_kaletapahtuma >= '$vva-$kka-$ppa'";
				$viimeisin_kaletapahtuma = "OK";
			}
			else {
				$versio_rajaus .= " and versio.luontiaika >= '$vva-$kka-$ppa'";
			}			
		}
		if($ppl > 0 and $kkl > 0 and $vvl > 0) {
			$ppl = sprintf("%02d", $ppl);
			$kkl = sprintf("%02d", $kkl);			
			if($tapahtuma == "") {
				$lasku_rajaus .= " and lasku.luontiaika <= '$vvl-$kkl-$ppl'";
			}
			elseif($tapahtuma == "K") {
				$having_rajaus .= " and viimeisin_kaletapahtuma <= '$vvl-$kkl-$ppl'";
				$viimeisin_kaletapahtuma = "OK";
			}
			else {
				$versio_rajaus .= " and versio.luontiaika <= '$vvl-$kkl-$ppl'";
			}
		}
		
		if($summamin > 0) {
			$having_rajaus .= " and summa_ >= $summamin";
		}
		if($summamax > 0) {
			$having_rajaus .= " and summa_ <= $summamax";
		}
	}
	else {
		$lasku_rajaus = " and lasku.laatija = '$kukarow[kuka]' and lasku.alatila IN ('','A')";
	}

	if($lasku_rajaus == "" and $lisatiedot_rajaus == "" and $versio_rajaus == "" and $having_rajaus == "") {
		die("Tee edes jotain valintoja!");
	}
	
	if($having_rajaus != "") {
		$having_rajaus = "HAVING ".substr($having_rajaus, 4);
	}

	if($lkm > 0) {
		$lkm_rajaus = "LIMIT $lkm";
	}
	
	if(count($group)>0) {
		
		//	Oreder by määrätään täällä aina
		$order = array();
		$jarj = array();
		
		//	sortataan..
		for($i=0;$i<count($prio);$i++) {
			$v = current($prio);
			if($v == 0) $prio[key($prio)]=max($prio)+1;
			next($prio);
		}
		
		$g = array();
		
		foreach($group as $gk => $gv) {
			$g[$prio[$gk]] = $gk;
			$jarj[$prio[$gk]] = $gk;
		}
		
		//	sortataan nää arrayt
		ksort($g);
		ksort($jarj);

		$group_by = "GROUP BY ".implode(", ", $g)."";
	}
	
	$order = "ORDER BY ";
	if(is_array($jarj)) {
		foreach($jarj as $key => $jarj) {
			if($jarj != "") {
				if(in_array($jarj, array("pva", "summa_"))) {
					$order .= "$jarj+0 ".$suunta[$key].", ";
				}
				else {
					$order .= "$jarj ".$suunta[$key].", ";
				}
			}
		}		
	}
	
	if($toim == "TARJOUSHAKUKONE") {
		$order .= "lasku.tunnus ASC";
	}
	elseif($toim == "TILAUSHAKUKONE") {
		$order .= "lasku.tunnus DESC";
	}
		
	if($viimeisin_kaletapahtuma == "OK") {
		$viimeisin_kaletapahtuma = ", (	SELECT max(pvmalku)
			FROM kalenteri
			WHERE kalenteri.yhtio=lasku.yhtio and otunnus IN ((select group_concat(l.tunnus) from lasku l use index (yhtio_tunnusnippu) where l.yhtio=lasku.yhtio and l.tunnusnippu>0 and l.tunnusnippu = lasku.tunnusnippu))
		) viimeisin_kaletapahtuma";
	}
	else $viimeisin_kaletapahtuma = "";
	
	//	Jäsennellään.. 
	if($toim == "TARJOUSHAKUKONE") {
		$summa = "
		(
			SELECT sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))
			FROM tilausrivi
			WHERE tilausrivi.yhtio=versio.yhtio and tilausrivi.otunnus=versio.tunnus and tyyppi != 'D'
		)";
	}
	elseif($toim == "TILAUSHAKUKONE") {
		$summa = "(
			SELECT sum(tilausrivi.hinta * if('o' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))
		 	FROM lasku l
		 	JOIN tilausrivi ON tilausrivi.yhtio=l.yhtio and tilausrivi.otunnus=l.tunnus and tyyppi != 'D' and tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]'
		
		 	WHERE lasku.yhtio=lasku.yhtio and tunnusnippu=lasku.tunnusnippu and lasku.tila IN ('R','L','N')
		)";
	}
	
	//	Grouppaus toimii aina samalla kaavalla
	if(count($group)>0) {

		$q = "";
		foreach($g as $k) {
			if($k == "lasku.myyja") {
				$k = "myyja.nimi myyja";
				$myyja_join = "	LEFT JOIN kuka myyja ON myyja.yhtio=lasku.yhtio and myyja.tunnus=lasku.myyja";
			}
			elseif($k == "lasku.vienti") {
				$k = "if(lasku.vienti='E', '".t("Eurooppa")."', if(lasku.vienti='K', '".t("Kaukomaat")."', 'Kotimaa')) vienti";
			}
			
			$q .= "$k, ";
		}
		
		$q .= "sum($summa) Yhteensä, count(*) Kpl, ";
		$c = count($group);
	}
	elseif($toim == "TARJOUSHAKUKONE") {
		$q = "lasku.tunnus tarjous, laskun_lisatiedot.seuranta, concat_ws(' ',versio.nimi, versio.nimitark) asiakas, asiakkaan_kohde.kohde, lasku.laatija, $summa Yhteensä, if(versio.alatila IN ('', 'A'), DATEDIFF(versio.luontiaika, date_sub(now(), INTERVAL laskun_lisatiedot.tarjouksen_voimaika day)), '') pva,";
		$c = 6;
	}
	elseif($toim == "TILAUSHAKUKONE") {
		$q = "lasku.tunnus tarjous, laskun_lisatiedot.seuranta, concat_ws(' ',lasku.nimi, lasku.nimitark) asiakas, asiakkaan_kohde.kohde, lasku.laatija, summa Yhteensä, ";
		$c = 6;		
	}
	
	//	Tässä kasataan kysely kasaan
	if($toim == "TARJOUSHAKUKONE") {
		$query = "	SELECT 	$q
							lasku.tila, lasku.alatila, lasku.tunnus
							$viimeisin_kaletapahtuma
					FROM lasku
					JOIN lasku versio ON versio.yhtio = lasku.yhtio and versio.tunnus = if(lasku.tunnusnippu>0,(select max(l.tunnus) from lasku l where l.yhtio = lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu),lasku.tunnus) $versio_rajaus
					JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio = versio.yhtio and laskun_lisatiedot.otunnus=versio.tunnus $lisatiedot_rajaus
					LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=laskun_lisatiedot.yhtio and asiakkaan_kohde.tunnus=laskun_lisatiedot.asiakkaan_kohde
					$myyja_join
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila IN ($laskutilat) and lasku.tunnus = lasku.tunnusnippu
					$lasku_rajaus
					$having_rajaus
					$group_by
					$order
					$lkm_rajaus";
	}
	elseif($toim == "TILAUSHAKUKONE") {
		//	Haetaan kaikki Tilausotsikot
		$query = "	SELECT 	$q
							lasku.tila, lasku.alatila, lasku.tunnus
							$viimeisin_kaletapahtuma
					FROM lasku
					JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus $lisatiedot_rajaus
					LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=laskun_lisatiedot.yhtio and asiakkaan_kohde.tunnus=laskun_lisatiedot.asiakkaan_kohde
					$myyja_join
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila IN ($laskutilat) and lasku.tunnus = lasku.tunnusnippu
					$lasku_rajaus
					$having_rajaus
					$group_by
					$order
					$lkm_rajaus";
	}
	//echo $query."<br>";
	
	$laskures = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($laskures)>0) {
		
		echo "	<div id='main'>
				<table border='0' cellpadding='2' cellspacing='1' width = '1000'>
					<tr>";
				
				for($i=0;$i<=$c; $i++) {
					$o = trim(str_replace("_", " ", mysql_field_name($laskures, $i)));					
					echo "<th>$o</th>";
					
					if(isset($workbook)) {
						$worksheet->write($excelrivi, $i, ucfirst(t($o)), $format_bold);
					}
				}
				
				if(isset($workbook)) {
					$excelrivi++;
				}
								
				echo "
					</tr>";
		$gt = 0;
		while($laskurow = mysql_fetch_array($laskures)) {
			
			echo "<tr  class='aktiivi'>";
			for($i=0;$i<=$c; $i++) {
				
				if(mysql_field_name($laskures, $i) == "pva") {
					if($laskurow["pva"] > 5) {
						$color = "green"; 
					}
					elseif($laskurow["pva"] > 0) {
						$color = "orange"; 
					}
					else {
						$color = "red"; 				
					}
					
					echo "<td><font style='color:$color;'>$laskurow[pva]</font></td>";

					if(isset($workbook)) {
						$worksheet->write($excelrivi, $i, $laskurow["pva"]);
					}
				}
				elseif(mysql_field_name($laskures, $i) == "tila") {
					$laskutyyppi = $laskurow["tila"];
					$alatila	 = $laskurow["alatila"];
					require "inc/laskutyyppi.inc";
					
					echo "<td>".t($laskutyyppi)." ".t($alatila)."</td>";

					if(isset($workbook)) {
						$worksheet->write($excelrivi, $i, t($laskutyyppi)." ".t($alatila));
					}
				}
				elseif(mysql_field_type($laskures, $i) == "real") {					
					echo "<td align = 'right'>".number_format($laskurow[$i], 2, ',', ' ')."</td>";
					
					if(isset($workbook)) {
						$worksheet->write($excelrivi, $i, number_format($laskurow[$i], 2, ',', ' '));
					}
				}
				else {					
					echo "<td>$laskurow[$i]</td>";
					
					if(isset($workbook)) {
						$worksheet->write($excelrivi, $i, $laskurow[$i]);
					}
				}
				
				if(mysql_field_name($laskures, $i) == "Yhteensä") {
					$colspan = $i;
					$gt += $laskurow[$i];
				}
				
			}
			
			$excelrivi++;
			
			if(count($group)==0) {
				echo "					
						<td class='back'><a id = 'kasittele_$laskurow[tunnus]' href=\"javascript: sndReq('tarjouskalenteri_$laskurow[tunnus]', 'tilaushakukone.php?ohje=off&toim=$toim&setti=$setti&tee=NAYTA&nakyma=TAPAHTUMALISTAUS&tarjous=$laskurow[tunnus]', 'kasittele_$laskurow[tunnus]', true);\">Käsittele</a></td>";
			}
			else {
				echo "</tr>";
			}

			echo "<tr><td class='back' colspan = '8'><div id='tarjouskalenteri_$laskurow[tunnus]'></div></td></tr>";
		}
		
		echo "<tr><td colspan='$colspan' class='back'></td><td class='back' align = 'right'><font class='message'>".number_format($gt, 2, ',', ' ')."</font></td></tr>";
		echo "</table></div>";
		
		if(isset($workbook) and $excelrivi>0) {
			// We need to explicitly close the workbook
			$workbook->close();

			echo "<table>";
			echo "<tr><th>".t("Tallenna tulos").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='".ucfirst($excelnimi)."'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
		}
		
		//	Jos meillä on tarjous halutaan se varmaan aktivoida?
		if($tarjous > 0) {
			echo "
				<script type='text/javascript' language='JavaScript'>
					javascript: sndReq('tarjouskalenteri_$tarjous', 'tilaushakukone.php?ohje=off&toim=$toim&setti=$setti&tee=NAYTA&nakyma=TAPAHTUMALISTAUS&tarjous=$tarjous', 'kasittele_$tarjous', true);
				</script>";
		}
		
		if($setti == "viikkis") {
			
			echo "<br><br><table width = '800'>
					<caption>".t("Saadut suoritukset 30 päivän ajalta")."</caption>
					<tr><th>".t("Asiakas")."</th><th>".t("Summa")."</th><th>".t("Eräpäivä")."</th><th>".t("Maksettu")."</th><th>".t("Pva")."</th></tr>";
				
			$query = "	SELECT concat_ws(' ', nimi, nimitark) asiakas, summa, date_format(erpcm, '%d. %m. %Y') erpcm, date_format(mapvm, '%d. %m. %Y') mapvm, datediff(erpcm, mapvm) pva
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and mapvm > DATE_SUB(now(), INTERVAL 30 DAY) and mapvm>'0000-00-00' and tila = 'U'
						ORDER BY mapvm DESC";
			$result = mysql_query($query) or pupe_error($query);

			$summa = $pva = 0;
			while($row = mysql_fetch_array($result)) {
				if($row["pva"] >= 0) {
					$class = "message";
				}
				else {
					$class = "error";
				}
				
				echo "	<tr class = 'aktiivi'>
							<td>$row[asiakas]</td>
							<td align='right'>".number_format($row["summa"], 2, ',', ' ')."</td>
							<td align='center' >$row[erpcm]</td>
							<td align='center' >$row[mapvm]</td>
							<td align='center' ><font class='$class'>$row[pva]</font></td>							
						</tr>";
				$summa += $row["summa"];
				$pva += $row["pva"];
			}
			echo "</tr>";
			
			$pva = round($pva/mysql_num_rows($result), 2);
			if($pva >= 0) {
				$class = "message";
			}
			else {
				$class = "error";
			}
			
			echo "<tr><td class = 'back' colspan='1' align='center'><font class='info'>".t("HUOM! Osasuorituksia ei huomioida")."!!</font></td><td class = 'back' align='right'><font class='message'>".number_format($summa, 2, ',', ' ')."</font></td><td class='back' colspan='2'></td><td class = 'back' align='right'><font class='$class'>$pva</font></td></tr>
				</table>";
		}
	}		
}

require ("inc/footer.inc");

?>
