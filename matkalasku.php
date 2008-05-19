<?php

require ("inc/parametrit.inc");
require ("inc/alvpopup.inc");

echo "<font class='head'>".t('Matkalasku / kulukorvaus')."</font><hr><br><br>";

if($tee == "POISTA") {

	$tunnus=$tilausnumero;
	$tee="D";
	$kutsuja="MATKALASKU";

    require("hyvak.php");

	$tee="";
	$tunnus=0;
	$tilausnumero=0;

}

function korjaa_ostovelka($ltunnus) {
	global $yhtiorow, $kukarow;

	if ($debug == 1) echo "Korjataan ostovelka laskulle $ltunnus<br>";

	if ($yhtiorow["ostovelat"] == "") {
		echo "Ostovelkatiliöinti puuttuu<br>";
		return false;
	}

	$query = "	select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$ltunnus'";
	$result=mysql_query($query) or pupe_error($query);
	$laskurow=mysql_fetch_array($result);
	
	if($laskurow["tila"] != "H") {
		echo "<font class='message'>Lasku on jo hyväksytty, laskun summaa ja osovelkatiliöintiä ei enää muutetta!</font><br>";
		return true;
	}
	
	$query = "	SELECT sum((-1*summa)) summa, count(*) kpl
				FROM tiliointi
				WHERE yhtio = '$kukarow[yhtio]'
				and ltunnus='$ltunnus'
				and korjattu=''
				and tilino != '{$yhtiorow["ostovelat"]}'";
	$summares=mysql_query($query) or pupe_error($query);
	$summarow=mysql_fetch_array($summares);

	//	Onko meillä jo ostovelkatiliöinti vai perustetaanko uusi?
	$query= "	SELECT *
				FROM tiliointi
				WHERE yhtio = '$kukarow[yhtio]'
				and ltunnus='$ltunnus'
				and tilino='{$yhtiorow["ostovelat"]}'
				and korjattu=''";
	$velkares=mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($velkares) == 1) {

		$velkarow = mysql_fetch_array($velkares);
		if ($debug == 1) echo "Löydettiin ostovelkatiliöinti tunnuksella {$velkarow["tunnus"]} tiliöintejä ({$summarow["kpl"]}) kpl<br>";

		$query = "	UPDATE tiliointi SET ";
		$where = "	WHERE yhtio='{$kukarow[yhtio]}' and tunnus='{$velkarow["tunnus"]}'";
	}
	else {

		if ($debug == 1) echo "Luodaan uusi ostovelkatiliöinti<br>";

		$query = "INSERT into tiliointi SET
					yhtio ='$kukarow[yhtio]',
					ltunnus = '$ltunnus',
					lukko = '1',
					tilino = '{$yhtiorow["ostovelat"]}',";
		$where="";
	}

	$query .= "		summa='{$summarow[summa]}',
					kustp= '',
					tapvm = '$laskurow[tapvm]',
					vero = '',
					tosite = '$tositenro',
					laatija = '$kukarow[kuka]',
					laadittu = now()";
	$query = $query.$where;
	$updres=mysql_query($query) or pupe_error($query);

	if ($debug == 1) echo "Korjattiin ostovelkatiliöinti uusi summa on {$summarow[summa]}";

	//	Päivitetään laskun summa
	if($laskurow["tilaustyyppi"] == "M") {
		$query = " update lasku set summa='".(-1*$summarow["summa"])."' where yhtio='$kukarow[yhtio]' and tunnus='$ltunnus'";
		$updres=mysql_query($query) or pupe_error($query);	
	}

	//	Ollaanko vielä synkissä?
	$query = "	SELECT sum(rivihinta) summa
				FROM tilausrivi
				WHERE yhtio='$kukarow[yhtio]' and otunnus='$ltunnus' and tyyppi='M'";
	$result=mysql_query($query) or pupe_error($query);
	
	$rivisumma=mysql_fetch_array($result);
	$ero=round($rivisumma["summa"],2)+round($summarow["summa"],2);
	if ($ero<>0) {
		echo "	<font class='error'>".t("VIRHE!!! Matkalasku ja kirjanpito ei täsmää!!!")."</font><br>
				<font class='message'>".t("Heitto on")." $ero [rivit {$rivirow[summa]}] (kp {$summarow["summa"]})</font><br>";
	}
}

function erittele_rivit($tilausnumero) {
	global $kukarow, $yhtiorow, $verkkolaskuvirheet_ok;
	
	$query = "	SELECT *
				FROM lasku
				WHERE yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
	$result=mysql_query($query) or pupe_error($query);
	$laskurow=mysql_fetch_array($result);
	
	if($laskurow["ebid"] == "") {
		return false;
	}
	
	$query = "	SELECT tunnus
				FROM toimi
				WHERE yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]' and laskun_erittely != ''";
	$toimires = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($toimires)==0) {
		die(t("Sori, mutta tän toimittajan laskuja EI SAA eritellä!"));
	}
	
	$query = "	SELECT tunnus
				FROM tilausrivi
				WHERE yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero' and tyyppi != 'D'
				LIMIT 1";
	$result=mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result) > 0) {
		return true;
	}
	
	$query = " 	DELETE
				FROM tiliointi
				WHERE yhtio='$kukarow[yhtio]' and ltunnus = '$tilausnumero'";
	$result = mysql_query($query) or pupe_error($query);	
	
	$file = $verkkolaskuvirheet_ok."/{$laskurow["ebid"]}";

	if(file_exists($file)) {

		// luetaan file muuttujaan
		$xmlstr = file_get_contents($file);

		if ($xmlstr === FALSE) {
			echo ("Tiedosto $file luku epäonnistui!\n");
			return ("Tiedosto $file luku epäonnistui!");
		}

		// luetaan sisään xml
		$xml = simplexml_load_string($xmlstr);
		
		require("inc/verkkolasku-in-pupevoice.inc");
				
		if(count($tuotetiedot)>0) {
			for($i=0; $i<count($tuotetiedot); $i++) {

				//	Näyttäisikö tämä korkoriviltä, otetaan se talteen ja laitetaan myös riville?
				if(preg_match("/KORKO\s+[0-9]{2}\.[0-9]{2}\.\s+-\s+[0-9]{2}\.[0-9]{2}\./", $info[$i])) {
					$korkosumma = (float) $rsumma[$i];
				}
				
				if($rkpl[$i] > 0) {
					
					$kpl = (float) $rkpl[$i];
					$hinta = (float) $rsumma[$i];
					
					if($hinta < 0) {
						$kpl = $kpl * -1;
						$hinta = $hinta * -1;
					}
					
					$kommentti = "";
					
					$rivihinta = (float) $kpl * (float) $hinta;
					
					if($riviaika[$i] != "") {
						$kommentti .= "Tapahtuma-aika: ".preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})/", "\$3.\$2. \$1", $riviaika[$i]);
					}

					if($riviaika[$i] != "") {
						$kommentti .= "Tapahtuma-aika: ".preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})/", "\$3.\$2. \$1", $riviaika[$i]);
					}
					
					$kommentti .= "<br>Tapahtuman selite: $info[$i]";
					if(preg_match("/([A-Z]{3})\s*([0-9\.,]*)/", $riviviite[$i], $match)) {
						$kommentti .= "<br>Alkupeärinen summa: $match[2] $match[1] ($hinta {$yhtiorow["valkoodi"]})";
					}

					$kommentti = preg_replace("/\.{2,}/", "", $kommentti);
					
					//echo "$kpl | $hinta | $kommentti\n";
					
					//echo print_r($rkpl[$i][0], true);
					//	Laitetaan tilausrivi kantaan
					$query = "	INSERT into tilausrivi set
								hyllyalue   = '0',
								hyllynro    = '0',
								hyllytaso   = '0',
								hyllyvali   = '0',
								laatija 	= '$kukarow[kuka]',
								laadittu 	= now(),
								yhtio 		= '$kukarow[yhtio]',
								tuoteno 	= '$tno',
								varattu 	= '0',
								yksikko 	= '',
								kpl 		= '$kpl',
								tilkpl 		= '$kpl',
								ale 		= '0',
								alv 		= '0',
								netto		= 'N',
								hinta 		= '$hinta',
								rivihinta 	= '$rivihinta',
								otunnus 	= '$tilausnumero',
								tyyppi 		= 'M',
								toimaika 	= '',
								kommentti 	= '".mysql_real_escape_string($kommentti)."',
								var 		= '',
								try			= '$trow[try]',
								osasto		= '$trow[osasto]',
								perheid		= '0',
								perheid2	= '0',									
								tunnus 		= '0',
								nimitys 	= '',
								kerattyaika = '$alkuaika',
								toimitettuaika = '$loppuaika'";
					$insres = mysql_query($query) or die($query);
					$lisatty_tun = mysql_insert_id();
					
					//	Päivitetään perheid2
					$query = " 	UPDATE tilausrivi set perheid2='$lisatty_tun'
								WHERE yhtio='$kukarow[yhtio]' and tunnus='$lisatty_tun'";
					$updres=mysql_query($query) or die($query);
					
					//	Tehdään oletustiliöinti
					$query = "INSERT into tiliointi set
									yhtio ='$kukarow[yhtio]',
									ltunnus = '$tilausnumero',
									tilino = '{$yhtiorow["selvittelytili"]}',
									kustp = '',
									kohde = '',
									projekti = '',
									tapvm = '$laskurow[tapvm]',
									summa = '$rivihinta',
									vero = '0',
									selite = 'EC selvittely',
									lukko = '1',
									tosite = '$tositenro',
									laatija = '$kukarow[kuka]',
									laadittu = now()";
					$result = mysql_query($query) or pupe_error($query);
					$isa = mysql_insert_id(); // Näin löydämme tähän liittyvät alvit....

					
					$query = "	INSERT INTO tilausrivin_lisatiedot SET
								yhtio				= '$kukarow[yhtio]',
								luontiaika			= now(),
								tilausrivitunnus	= '$lisatty_tun',
								laatija 			= '$kukarow[kuka]',
								tiliointirivitunnus = '$isa',
								kulun_kohdemaa		= '{$yhtiorow["maa"]}',
								kulun_kohdemaan_alv	= '',
								muutospvm			= now(),
								muuttaja			= '$kukarow[kuka]'";					
					$updres = mysql_query($query) or pupe_error($query);
					
					korjaa_ostovelka($tilausnumero);
					
				}				
			}
		}
		
		return $korkosumma;
	}
	else {
		return false;
	}
}

if($tee == "UUDELLEENKASITTELE" and $toim == "SUPER") {
	
	echo "<font class='message'>".t("Poistetaan vanhat tiliöinnit ja laskut")."</font><br>";
	$query = "	DELETE
				FROM tilausrivi
				WHERE yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero' and tyyppi = 'M'";
	$result=mysql_query($query) or pupe_error($query);

	$tee = "ERITTELE";
}

if($tee == "ERITTELE") {

	//	Onko tässä jo jotain tilausrivejä?
	$query = "	SELECT tunnus
				FROM tilausrivi
				WHERE yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero' and tyyppi = 'M'
				LIMIT 1";
	$result=mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result) == 0) {
		
		echo "<font class='message'>".t("Koitetaan arpoa laskutusrivit laskulta")."</font><br>";
		
		$hinta = (float) erittele_rivit($tilausnumero);

		//	Lisätään korko ja korjataan ostovelka
		if($hinta > 0) {
			$tuoteno = "KORKO";
			$lisaa = "JOO";
			$kpl = 1;
			$tyyppi = "B";		
		}
	}
	
	$tee = "MUOKKAA";
}



if ($tee == "UUSI") {

	//	tarkastetaan että käyttäjälle voidaan perustaa matkalaskuja
	$query = "	SELECT * FROM toimi WHERE yhtio='$kukarow[yhtio]' and nimi='$kukarow[nimi]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$trow=mysql_fetch_array($result);
	}
	else {
		die("<font class='error'>".t("Lisää itsesi ensin toimittajaksi.")."</font>");
	}

	/*
		Täältä löytyy kaikki verottajan ulkomaanpäivärahat, sekä ohjeet niiden käsittelyyn
		http://www.vero.fi/default.asp?article=5516&domain=VERO_MAIN&path=5,298,305,316&language=FIN

		(tai hakusanalla päivärahat yyyy)
	*/

	$query = "	SELECT *
				FROM tuote
				JOIN tili ON tili.yhtio=tuote.yhtio and tili.tilino=tuote.tilino
				WHERE tuote.yhtio='$kukarow[yhtio]'
				and tuotetyyppi in ('A','B')
				and status !='P'
				and tuote.tilino!=''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		die ("<font class='error'>".t("Sinulla ei ole yhtään viranomaistuotetta perustettuna.")."</font>");
	}

	if ($ytunnus != '') {
		require ("inc/asiakashaku.inc");

		if ($asiakasid>0) {
			// Perustetaan lasku
			$query = "INSERT into lasku set
						yhtio 			= '$kukarow[yhtio]',
						valkoodi 		= 'EUR',
						hyvak1 			= '$kukarow[kuka]',
						hyvak2 			= '$trow[oletus_hyvak2]',
						hyvak3 			= '$trow[oletus_hyvak3]',
						hyvak4 			= '$trow[oletus_hyvak4]',
						hyvak5 			= '$trow[oletus_hyvak5]',
						hyvaksyja_nyt 	= '$kukarow[kuka]',
						ytunnus 		= '$ytunnus',
						tilinumero 		= '$trow[tilinumero]',
						nimi 			= '$trow[nimi]',
						nimitark 		= '".t("Matkalasku")."',
						osoite 			= '$trow[osoite]',
						osoitetark 		= '$trow[osoitetark]',
						postino 		= '$trow[postino]',
						postitp 		= '$trow[postitp]',
						maa 			=  '$trow[maa]',
						toim_nimi 		= '$asiakasrow[nimi]',
						toim_nimitark 	= '".t("Matkalasku")."',
						toim_osoite 	= '$asiakasrow[osoite]',
						toim_postino 	= '$asiakasrow[postino]',
						toim_postitp 	= '$asiakasrow[postitp]',
						toim_maa 		= '$asiakasrow[maa]',
						vienti 			= '$asiakasrow[vienti]',
						ebid 			= '',
						tila 			= 'H',
						swift 			= '$trow[swift]',
						pankki1 		= '$trow[pankki1]',
						pankki2 		= '$trow[pankki2]',
						pankki3 		= '$trow[pankki3]',
						pankki4 		= '$trow[pankki4]',
						vienti_kurssi 	= '1',
						laatija 		= '$kukarow[kuka]',
						luontiaika 		= now(),
						liitostunnus 	= '{$trow["tunnus"]}',
						hyvaksynnanmuutos = '$trow[oletus_hyvaksynnanmuutos]',
						suoraveloitus 	= '',
						tilaustyyppi	= 'M'";

			$result = mysql_query($query) or pupe_error($query);
			$tilausnumero = mysql_insert_id();

			//	Tänne voisi laittaa myös tuon asiakasidn jos tästä voitaisiin lähettää myös lasku asiakkaalle
			$query = "INSERT into laskun_lisatiedot set
						yhtio = '$kukarow[yhtio]',
						otunnus = '$tilausnumero'";

			$result = mysql_query($query) or pupe_error($query);

			$tee="MUOKKAA";
		}
		else {
			$tee="";
		}
	}
	else {
		echo "<font class='error'>".t("VIRHE!!! Anna asiakkaan nimi")."</font><br>";
		$tee="";
	}
}

//	Joitain asioita kai pitää muutella..
if ($tee == "TALLENNA") {
	if ((int)$tilausnumero == 0) {
		echo "<font class='error'>".t("Matkalaskun numero puuttuu")."</font>";
		$tee="";
	}
	else {
		//	Koitetaan tallennella kuva
		if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
			if ($kuvaselite == "") {
				$errormsg = t("Anna kuvalle selite");
			}
			else {
				//	chekataan erroit
				switch ($_FILES['userfile']['error']) {
					case 1:
					case 2:
						$errormsg .= t("Kuva on liian suuri, suurin sallittu koko on")." ".ini_get('post_max_size');
						break;
					case 3:
						$errormsg .= t("Kuvan lataus keskeytyi")."!";
						break;
					case 6:
					case 7:
					case 8:
						$errormsg .= t("Tallennus epäonnistui")."!";
						break;
					case 0:
						//	OK tallennetaan

						// otetaan file extensio
						$path_parts = pathinfo($_FILES['userfile']['name']);
						$ext = $path_parts['extension'];
						if (strtoupper($ext) == "JPEG") $ext = "jpg";

						$query = "SHOW variables like 'max_allowed_packet'";
						$result = mysql_query($query) or pupe_error($query);
						$varirow = mysql_fetch_array($result);

						$filetype = $_FILES['userfile']['type'];
						$filesize = $_FILES['userfile']['size'];
						$filename = $_FILES['userfile']['name'];

						// extensio pitää olla oikein
						if (strtoupper($ext) != "JPG" and strtoupper($ext) != "PNG" and strtoupper($ext) != "GIF" and strtoupper($ext) != "PDF") {
							$errormsg .= "<font class='error'>".t("Ainoastaan .jpg .gif .png .pdf tiedostot sallittuja")."!</font>";
						}
						elseif ($filesize > $varirow[1]) {
							$errormsg .= "<font class='error'>".t("Liitetiedosto on liian suuri")."! ($varirow[1]) </font>";
						}
						else {
							// lisätään kuva
							$query = "	insert into liitetiedostot set
											yhtio    = '$kukarow[yhtio]',
											liitos   = 'lasku',
											liitostunnus = '$tilausnumero',
											data     = '".addslashes(file_get_contents($_FILES['userfile']['tmp_name']))."',
											selite   = '$kuvaselite',
											filename = '{$_FILES["userfile"]["name"]}',
											filesize = '{$_FILES["userfile"]["size"]}',
											filetype = '{$_FILES["userfile"]["type"]}'";
							$insre = mysql_query($query) or pupe_error($query);

							$kuvaselite = "";
						}
						break;
			}
			}

			if ($errormsg != "") {
				echo "<font class='error'>$errormsg</font><br>";
		}
		}

		$query = "	UPDATE lasku SET
							viite='$viesti'
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
		$updres=mysql_query($query) or pupe_error($query);

		$tee="MUOKKAA";
	}
}

if ($tee == "MUOKKAA") {
	if ((int)$tilausnumero == 0) {
		echo "<font class='error'>".t("Matkalaskun numero puuttuu")."</font>";
		$tee="";
	}
	else {

		if($poistakuva > 0) {
			$query = " delete from liitetiedostot WHERE yhtio = '$kukarow[yhtio]' and liitos='lasku' and liitostunnus='$tilausnumero' and tunnus = '$poistakuva'";
			$result = mysql_query($query) or pupe_error($query);
			if(mysql_affected_rows()==0) {
				echo "<font class='error'>".t("VIRHE!!! Koititte poistaa liitetiedoston jota ei ole!")."</font><br>";
			}
		}

		$query 	= "	select *
						from lasku
						where tunnus='$tilausnumero' and yhtio='$kukarow[yhtio]' and tilaustyyppi IN ('M', '') and tila IN ('H','Y','M','P','Q')";
		$result  	= mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 0) {
			die("<font class='error'>".t("Matkalaskun numero puuttuu")."</font>");
		}
		else {
			$laskurow   = mysql_fetch_array($result);
		}

		if($kuivat != "JOO") {
			if($perheid2> 0) {
				$query	= "	SELECT tilausrivi.*, tuote.tuotetyyppi, tuote.tilino,
							tilausrivin_lisatiedot.tiliointirivitunnus,
							tilausrivin_lisatiedot.kulun_kohdemaa,
							tilausrivin_lisatiedot.kulun_kohdemaan_alv,
							kustp.tunnus kustp,
							kohde.tunnus kohde,
							projekti.tunnus projekti,
							if(tuotetyyppi='A', tuote.vienti, tilausrivin_lisatiedot.kulun_kohdemaa) vienti
							FROM tilausrivi use index (PRIMARY)
							LEFT JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus
							LEFT JOIN tiliointi ON tiliointi.yhtio=tilausrivin_lisatiedot.yhtio and tiliointi.tunnus=tilausrivin_lisatiedot.tiliointirivitunnus
							LEFT JOIN kustannuspaikka kustp ON tiliointi.yhtio=kustp.yhtio and tiliointi.kustp=kustp.tunnus
							LEFT JOIN kustannuspaikka projekti ON tiliointi.yhtio=projekti.yhtio and tiliointi.projekti=projekti.tunnus
							LEFT JOIN kustannuspaikka kohde ON tiliointi.yhtio=kohde.yhtio and tiliointi.kohde=kohde.tunnus
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and otunnus = '$tilausnumero'
							and perheid2 = $perheid2
							and tilausrivi.tunnus = perheid2";				
				$abures = mysql_query($query) or pupe_error($query);
				$tapahtumarow = mysql_fetch_array($abures);
			}
			
			//	Muokataan kuluriviä, poistetaan koko nippu ja laitetaan muokattavaksi
			if ($rivitunnus>0) {
				$query	= "	SELECT tilausrivi.*, tuote.tuotetyyppi, tuote.tilino,
							tilausrivin_lisatiedot.tiliointirivitunnus,
							tilausrivin_lisatiedot.kulun_kohdemaa,
							tilausrivin_lisatiedot.kulun_kohdemaan_alv,
							kustp.tunnus kustp,
							kohde.tunnus kohde,
							tiliointi.tilino tiliointitili,
							projekti.tunnus projekti
							FROM tilausrivi use index (PRIMARY)
							LEFT JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus
							LEFT JOIN tiliointi ON tiliointi.yhtio=tilausrivin_lisatiedot.yhtio and tiliointi.tunnus=tilausrivin_lisatiedot.tiliointirivitunnus
							LEFT JOIN kustannuspaikka kustp ON tiliointi.yhtio=kustp.yhtio and tiliointi.kustp=kustp.tunnus
							LEFT JOIN kustannuspaikka projekti ON tiliointi.yhtio=projekti.yhtio and tiliointi.projekti=projekti.tunnus
							LEFT JOIN kustannuspaikka kohde ON tiliointi.yhtio=kohde.yhtio and tiliointi.kohde=kohde.tunnus
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and otunnus = '$tilausnumero'
							and tilausrivi.tunnus  = '$rivitunnus'";
				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) == 1) {
					$tilausrivi = mysql_fetch_array($result);

					// Poistetaan muokattava tilausrivi
					if ($tilausrivi["perheid"]>0) {
						$query = "	DELETE from tilausrivi
									WHERE yhtio = '$kukarow[yhtio]' and perheid = '$rivitunnus'";
						$result = mysql_query($query) or pupe_error($query);
					}
					else {
						$tiliointisumma=$tilausrivi["summa"];

						$query = "	DELETE from tilausrivi
									WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$rivitunnus'";
						$result = mysql_query($query) or pupe_error($query);
					}

					//	Poistetaan myös vastaava tiliöinti
					$query = "	DELETE from tiliointi
								WHERE yhtio = '$kukarow[yhtio]'
								and ltunnus = '$tilausnumero'
								and (		tunnus = '{$tilausrivi["tiliointirivitunnus"]}'
										or 	aputunnus = '{$tilausrivi["tiliointirivitunnus"]}'
									)";
					$result = mysql_query($query) or pupe_error($query);
					if (mysql_affected_rows() == 0) {
						echo "<font class='error'>".t("Tiliöintirivin poistaminen epäonnistui! Matkalasku ja kp voi olla out of sync!!!")."</font><br><br>";
					}

					//	Fiksataan ostovelka
					korjaa_ostovelka($tilausnumero);

					//	Jos muokataan otetaan dada talteen
					if ($tapa == "MUOKKAA") {
						list($pv, $aika)=explode(" ",$tilausrivi["kerattyaika"]);
						list($alkuvv,$alkukk,$alkupp)=explode("-",$pv);
						list($alkuhh,$alkumm)=explode(":",$aika);

						list($pv, $aika)=explode(" ",$tilausrivi["toimitettuaika"]);
						list($loppuvv,$loppukk,$loppupp)=explode("-",$pv);
						list($loppuhh,$loppumm)=explode(":",$aika);


						$kpl		= $tilausrivi["kpl"];
						$hinta		= round($tilausrivi["hinta"], 2);
						$kommentti	= $tilausrivi["kommentti"];
						$rivitunnus	= $tilausrivi["tunnus"];
						$kustp		= $tilausrivi["kustp"];
						$kohde		= $tilausrivi["kohde"];
						$projekti	= $tilausrivi["projekti"];

						if($toim == "SUPER") {
							$tilino = $tilausrivi["tiliointitili"];
						}
						
						//	Otetaan tuote suoraan riviltä
						if($vaihda_tuote != "") {
							$tuoteno = $vaihda_tuote;
							$tyyppi = $vaihda_tyyppi;
						}
						else {
							$tuoteno	= $tilausrivi["tuoteno"];
							$tyyppi		= $tilausrivi["tuotetyyppi"];
						}

						$alvulk		= $tilausrivi["kulun_kohdemaan_alv"];
						$maa		= $tilausrivi["kulun_kohdemaa"];
					}
					else {
						$tyhjenna="joo";
						unset($tapahtumarow);
						$perheid2 = 0;
					}
				}
			}
		}

		//	Koitetaan lisätä uusi rivi!
		if ($tuoteno != "" and isset($lisaa) and $kuivat != "JOO") {
			
			$query = "	select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and tuotetyyppi='$tyyppi' and status !='P'";
			$tres=mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($tres)<>1) {
				echo "<font class='error'>".t("VIRHE!!! Viranomaistuote puuttuu")." $tuoteno $query</font><br>";
			}
			else {
				$trow=mysql_fetch_array($tres);
			}

			$tyyppi			= $trow["tuotetyyppi"];
			$tuoteno_array 	= array();
			$errori			= "";

			if ($tyyppi == "A") {

				/*
					Päivärahoilla ratkaistaan päivät
					Samalla oletetaan että puolipäiväraha on aina P+tuoteno
				*/

				//	Lasketaan tunnit
				$alkupp = sprintf("%02d", $alkupp);
				$alkukk = sprintf("%02d", $alkukk);
				$alkuvv = (int) $alkuvv;
				$alkuhh = sprintf("%02d", $alkuhh);
				$alkumm = sprintf("%02d", $alkumm);

				$loppupp = sprintf("%02d", $loppupp);
				$loppukk = sprintf("%02d", $loppukk);
				$loppuvv = (int) $loppuvv;
				$loppuhh = sprintf("%02d", $loppuhh);
				$loppumm = sprintf("%02d", $loppumm);

				if (($alkupp>=1 and $alkupp<=31) and ($alkukk>=1 and $alkukk<=12) and $alkuvv>0 and ($alkuhh>=0 and $alkuhh<=24) and ($loppupp>=1 and $loppupp<=31) and ($loppukk>=1 and $loppukk<=12) and $loppuvv>0 and ($loppuhh>=0 and $loppuhh<=24)) {
					$alku=mktime($alkuhh, $alkumm, 0, $alkukk, $alkupp, $alkuvv);
					$loppu=mktime($loppuhh, $loppumm, 0, $loppukk, $loppupp, $loppuvv);

					//	Tarkastetaan että tällä välillä ei jo ole jotain arvoa
					//	HUOM! Koitetaan tarkastaa kaikki käyttäjän matkalaskut..
					$query = "	SELECT lasku.toim_nimi, lasku.summa, lasku.tapvm tapvm,
									tilausrivi.nimitys, tilausrivi.tuoteno, date_format(tilausrivi.kerattyaika, '%d.%m.%Y') kerattyaika, date_format(tilausrivi.toimitettuaika, '%d.%m.%Y') toimitettuaika, tilausrivi.kommentti kommentti
								FROM lasku
								LEFT JOIN tilausrivi on tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi 	= 'M'
								JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuotetyyppi IN ('A')
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and lasku.tilaustyyppi	= 'M'
								and lasku.tila IN ('H','Y','M','P','Q')
								and liitostunnus = '$laskurow[liitostunnus]'
								and (	(kerattyaika >= '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm' and kerattyaika < '$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm') or
										(kerattyaika <  '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm' and toimitettuaika > '$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm') or
										(toimitettuaika > '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm' and toimitettuaika <= '$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm'))
								GROUP BY otunnus";
					$result = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($result)>0) {
						$errori .= "<font class='error'>".t("VIRHE!!! Päivämäärä on menee päällekkäin toisen matkalaskun kanssa")."</font><br>";

						$errori .= "<table><tr><th>".t("Asiakas")."</th><th>".t("viesti")."</th><th>".t("Summa/tapvm")."</th><th>".t("Tuote")."</th><th>".t("Ajalla")."</th><th>".t("Viesti")."</th></tr>";
						while ($erow=mysql_fetch_array($result)) {
							$errori .=  "<tr>
											<td>{$erow[toim_nimi]}</td>
											<td>{$erow[viesti]}</td>
											<td>{$erow[summa]}@{$erow[tapvm]}</td>
											<td>{$erow[tuoteno]} - {$erow[nimitys]}</td>
											<td>{$erow[kerattyaika]} - {$erow[toimitettuaika]}</td>
											<td>{$erow[kommentti]}</td>
										</tr>";
						}
						$errori .= "</table><br><br>";
					}

					if($loppuvv.$loppukk.$loppupp>date("Ymd")) {
						$errori .= "<font class='error'>".t("VIRHE!!! Matkalaskua ei voi tehdä etukäteen!")."</font><br>";
					}

					$paivat=$puolipaivat=$ylitunnit=$tunnit=0;

					//	montako tuntia on oltu matkalla?
					$tunnit=($loppu-$alku)/3600;
					$paivat=floor($tunnit/24);

					$alkuaika	= "$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm:00";
					$loppuaika	= "$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm:00";

					$ylitunnit=$tunnit-($paivat*24);

					if ($ylitunnit > 10) {
						$paivat++;
					}
					elseif ($ylitunnit > 6 and $trow["vienti"] == "FI") {
						
						//	Tarkastetaan että päivärahalle on puolipäiväraha
						$query = "	select * from tuote where yhtio='$kukarow[yhtio]' and tuotetyyppi='$tyyppi' and tuoteno='P$tuoteno'";
						$tres2=mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($tres2)<>1) {
							$errori .= t("<font class='error'>".t("VIRHE!!! Viranomaistuote puuttuu (2). Puolipäivärahaa ei voitu lisätä!")."</font><br>");
						}
						else {
							$trow2=mysql_fetch_array($tres2);
							$puolipaivat++;
							
							$tuoteno_array[$trow2["tuoteno"]]	=$trow2["tuoteno"];
							$kpl_array[$trow2["tuoteno"]]		=$puolipaivat;

							$hinta=$trow2["myyntihinta"];
							$hinta_array[$trow2["tuoteno"]]		= $hinta;

							$selite.="<br>{$trow2["tuoteno"]} - {$trow2["nimitys"]} $puolipaivat kpl á $hinta";
							
						}
					}
					elseif ($ylitunnit <= 10 and $trow["vienti"] != "FI" and $paivat == 0) {
						$errori .= "<font class='error'>".t("VIRHE!!! Ulkomaanpäivärahalla on oltava vähintään 10 tuntia")."</font><br>";
						
						//	Tänne pitäisi joskus koodata se puolikas ulkomaanpäiväraha..
					}
					
					//	Lisätään myös saldoton isatuote jotta tiedämme mistä puolipäiväraha periytyy!
					if ($paivat>0 or $puolipaivat > 0) {
						$tuoteno_array[$tuoteno]			=$tuoteno;
						$kpl_array[$tuoteno]				=$paivat;

						$hinta=$trow["myyntihinta"];
						$hinta_array[$trow["tuoteno"]]		= $hinta;

						$selite="{$trow["tuoteno"]} - {$trow["nimitys"]} $paivat kpl á $hinta";
					}

					$selite .= "<br>Ajalla: $alkupp.$alkukk.$alkuvv klo. $alkuhh:$alkumm - $loppupp.$loppukk.$loppuvv klo. $loppuhh:$loppumm";

					//echo "SAATIIN päivarahoja: $paivat puolipäivärahoja: $puolipaivat<br>";
				}
				else {
					$errori .= "<font class='error'>".t("VIRHE!!! Päivärahalle on annettava alku ja loppuaika")."</font><br>";
				}
			}
			elseif ($tyyppi == "B") {
				if ($kpl==0) {
					$errori .= "<font class='error'>".t("VIRHE!!! kappalemäärä on annettava")."</font><br>";
				}

				if ($kommentti == "" and $trow["kommentoitava"] != "") {
					$errori .= "<font class='error'>".t("VIRHE!!! Kululle on annettava selite")."</font><br>";
				}

				if ($trow["myyntihinta"]>0) {
					$hinta=$trow["myyntihinta"];
				}
				$hinta = str_replace ( ",", ".", $hinta);
				if ($hinta<=0) {
					$errori .= "<font class='error'>".t("VIRHE!!! Kulun hinta puuttuu")."</font><br>";
				}

				$tuoteno_array[$trow["tuoteno"]]	= $trow["tuoteno"];
				$kpl_array[$trow["tuoteno"]]		= $kpl;
				$hinta_array[$trow["tuoteno"]]		= $hinta;

				$selite="{$trow["tuoteno"]} - {$trow["nimitys"]} $kpl kpl á $hinta";
			}

			//	poistetan return carriage ja newline -> <br>
			$kommentti = str_replace("\n","<br>",str_replace("\r","",$kommentti));
			if ($kommentti != "") {
				$selite .="<br><i>$kommentti</i>";
			}

			//	Lisätään annetut rivit
			$perheid=$isatunnus=0;

			if ($errori == "") {
				$tuoteno_array = array_reverse($tuoteno_array);
				foreach($tuoteno_array as $lisaa_tuoteno) {

					//	Haetaan tuotteen tiedot
					$query = "	SELECT *
								FROM tuote
								JOIN tili ON tili.yhtio=tuote.yhtio and tili.tilino=tuote.tilino
								WHERE tuote.yhtio		= '$kukarow[yhtio]'
								and tuotetyyppi			= '$tyyppi'
								and tuoteno				= '$lisaa_tuoteno'
								and status 				!= 'P'
								and tuote.tilino		!= ''";
					$tres=mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($tres) == 1) {
						$trow=mysql_fetch_array($tres);

						$kpl 	= str_replace(",",".",$kpl_array[$trow["tuoteno"]]);
						$hinta 	= str_replace(",",".",$hinta_array[$trow["tuoteno"]]);
						$rivihinta = round($kpl*$hinta,$yhtiorow['hintapyoristys']);

						//	Ratkaistaan alv..
						if ($tyyppi == "B") {
							//	Haetaan tuotteen oletusalv jos ollaan ulkomailla, tälläin myös kotimaan alv on aina zero
							if ($maa != "" and $maa != $yhtiorow["maa"]) {
								if ($alvulk == "") {
									$query = "select * from tuotteen_alv where yhtio='$kukarow[yhtio]' and maa='$maa' and tuoteno='$tuoteno' limit 1";
									$alhire = mysql_query($query) or pupe_error($query);
									$alvrow=mysql_fetch_array($alhire);
									$alvulk=$alvrow["alv"];
								}

								$vero=0;
							}
							else {
								$vero = $trow["alv"];
							}
						}
						else {
							$vero = 0;
						}
						
						//	Otetaan korvaava tilinumero
						if($tilino == "" or $toim != "SUPER") {
							$tilino = $trow["tilino"];
						}
						
						if($hardcoded_alv == 1) {

							$query  = "	SELECT oletusalv
										FROM tili
										WHERE yhtio = '$kukarow[yhtio]'
										and tilino = '$tilino'";
							$verores = mysql_query($query) or pupe_error($query);
							$verorow = mysql_fetch_array($verores);
							$vero = $verorow[0];
							
							if($vero == 99) $vero = 0;
						}
						
						$query = "	INSERT into tilausrivi set
									hyllyalue   = '0',
									hyllynro    = '0',
									hyllytaso   = '0',
									hyllyvali   = '0',
									laatija 	= '$kukarow[kuka]',
									laadittu 	= now(),
									yhtio 		= '$kukarow[yhtio]',
									tuoteno 	= '$lisaa_tuoteno',
									varattu 	= '0',
									yksikko 	= '$trow[yksikko]',
									kpl 		= '$kpl',
									tilkpl 		= '$kpl',
									ale 		= '0',
									alv 		= '$vero',
									netto		= 'N',
									hinta 		= '$hinta',
									rivihinta 	= '$rivihinta',
									otunnus 	= '$tilausnumero',
									tyyppi 		= 'M',
									toimaika 	= '',
									kommentti 	= '".mysql_real_escape_string($kommentti)."',
									var 		= '',
									try			= '$trow[try]',
									osasto		= '$trow[osasto]',
									perheid		= '$perheid',
									perheid2	= '$perheid2',									
									tunnus 		= '$rivitunnus',
									nimitys 	= '$trow[nimitys]',
									kerattyaika = '$alkuaika',
									toimitettuaika = '$loppuaika'";
						$insres = mysql_query($query) or die($query);
						$lisatty_tun = mysql_insert_id();

						if ($isatunnus == 0) $isatunnus=$lisatty_tun;

						//	Jos meillä on splitattu rivi niin pidetään nippu kasassa
						if ($perheid == 0 and count($tuoteno_array)>1) {
							$perheid=$lisatty_tun;

							$query = " 	UPDATE tilausrivi set perheid='$lisatty_tun'
										WHERE yhtio='$kukarow[yhtio]' and tunnus='$perheid'";
							$updres=mysql_query($query) or die($query);
						}

						if ( (int) $perheid2 == 0) {
							$perheid2=$lisatty_tun;

							$query = " 	UPDATE tilausrivi set perheid2='$lisatty_tun'
										WHERE yhtio='$kukarow[yhtio]' and tunnus='$perheid2'";
							$updres=mysql_query($query) or die($query);
						}
						
						//	Jos muokattiin perheen isukkia halutaan oikea kommentti!
						if( (int) $perheid2 == 0 or $perheid2==$lisatty_tun) {
							$tapahtumarow["kommentti"] = $kommentti;
						}
						
						$rivitunnus=0;
						$summa+=$rivihinta;
					}
					else {
						echo "<font class='error'>".t("VIRHE!!! Viranomaistuote puuttuu (1)")." $lisaa_tuoteno</font><br>";
					}
				}

				/*
					Hoidetaan kirjanpito
					copypastea teetiliointi.inc
				*/

				$summa = round($summa,2);

				if ($vero != 0) { // Netotetaan alvi
					$alv = round($summa - $summa / (1 + ($vero / 100)),2);
					$summa -= $alv;
				}

				if (($kpexport != 1) and (strtoupper($yhtiorow['maa']) == 'FI')) $tositenro=0; // Jos tätä ei tarvita
				
				if($toim == "SUPER" and $tilino > 0 and $trow["tilino"] != $tilino) {
					echo "<font class='message'>".t("HUOM! tiliöidään poikkeavalle tilille '$tilino'<br>");
					$trow["tilino"] = $tilino;					
				}
								
				$query = "INSERT into tiliointi set
								yhtio ='$kukarow[yhtio]',
								ltunnus = '$tilausnumero',
								tilino = '{$tilino}',
								kustp = '$kustp',
								kohde = '$kohde',
								projekti = '$projekti',
								tapvm = '$laskurow[tapvm]',
								summa = '$summa',
								vero = '$vero',
								selite = '".mysql_real_escape_string($selite)."',
								lukko = '1',
								tosite = '$tositenro',
								laatija = '$kukarow[kuka]',
								laadittu = now()";
				$result = mysql_query($query) or pupe_error($query);
				$isa = mysql_insert_id(); // Näin löydämme tähän liittyvät alvit....

				if ($vero != 0) { // Tiliöidään alv
				        $query = "INSERT into tiliointi set
							yhtio ='$kukarow[yhtio]',
							ltunnus = '$tilausnumero',
							tilino = '{$yhtiorow["alv"]}',
							kustp = '',
							kohde = '',
							projekti = '',
							tapvm = '$laskurow[tapvm]',
							summa = '$alv',
							vero = '',
							selite = '".mysql_real_escape_string($selite)."',
							lukko = '1',
							laatija = '$kukarow[kuka]',
							laadittu = now(),
							aputunnus = $isa";
					$result = mysql_query($query) or pupe_error($query);
				}

				//	Laitetaan lisätietoihin ainakin se ulkomaanalv jne..
				$query  = "	SELECT *
							FROM tilausrivin_lisatiedot
							WHERE yhtio			 = '$kukarow[yhtio]'
							and tilausrivitunnus = '$isatunnus'";
				$lisatied_res = mysql_query($query) or pupe_error($query);
				
				if (mysql_num_rows($lisatied_res) > 0) {;
					$query = "	UPDATE tilausrivin_lisatiedot SET ";
					$where = "	WHERE yhtio='{$kukarow[yhtio]}' and tilausrivitunnus = '$isatunnus'";
				}
				else {
					$query = "	INSERT INTO tilausrivin_lisatiedot SET
								yhtio				= '$kukarow[yhtio]',
								luontiaika			= now(),
								tilausrivitunnus	= '$isatunnus',
								laatija 			= '$kukarow[kuka]',";
					$where = "";
				}

				$query .= "	tiliointirivitunnus = '$isa',
							kulun_kohdemaa		= '$maa',
							kulun_kohdemaan_alv	= '$alvulk',
							muutospvm			= now(),
							muuttaja			= '$kukarow[kuka]'";
				$query  = $query.$where;
				$updres = mysql_query($query) or pupe_error($query);

				//	Fiksataan ostovelka
				korjaa_ostovelka($tilausnumero);
				$tyhjenna="JOO";
			}
		}

		if ($tyhjenna != "") {
			$tuoteno="";
			//$tyyppi="";
			$kommentti="";
			$rivitunnus="";
			//$perheid2="";

			$kpl="";
			$hinta="";

			unset($alkupp);
			unset($alkukk);
			unset($alkuvv);
			unset($alkuhh);
			unset($alkumm);

			unset($loppupp);
			unset($loppukk);
			unset($loppuvv);
			unset($loppuhh);
			unset($loppumm);

		}
		
		//	Haetaan tapahtuman rivitiedot jos se on valittu

		// kirjoitellaan otsikko
		echo "<table>";
		echo "<tr>";
		echo "<th align='left'>".t("Asiakas").":</th>";

		echo "<td>$laskurow[nimi]<br>$laskurow[nimitark]<br>$laskurow[osoite]<br>$laskurow[postino] $laskurow[postitp]</td>";

		echo "</tr>";
		
		if (	
				$laskurow["tila"] == "H" and $laskurow["hyvaksyja_nyt"] == $kukarow["kuka"] or
				($toim == "SUPER" and strtotime($laskurow["tapvm"]) > strtotime($yhtiorow["tilikausi_alku"]) and strtotime($laskurow["tapvm"]) < strtotime($yhtiorow["tilikausi_loppu"])) 
			) {

			// tässä alotellaan koko formi.. tämä pitää kirjottaa aina
			echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off' enctype='multipart/form-data'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='tee' value='TALLENNA'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='toim' value='$toim'>";

			if($laskurow["tilaustyyppi"] == "M") {
				echo "	<tr><th>".t("Viite")."</th>
						<td><input type='text' size='30' name='viesti' value='{$laskurow["viite"]}'></td></tr>";
				
			}
			else {
				echo "	<tr><th>".t("Viite")."</th>
						<td>{$laskurow["viite"]}</td></tr>";
			}
			echo "<tr>";
			echo "<th align='left'>".t("Kuittikopiot").":</th>";

			echo "<td>";

			$query = "select * from liitetiedostot where yhtio='{$kukarow[yhtio]}' and liitos='lasku' and liitostunnus='$tilausnumero'";
			$liiteres=mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($liiteres)>0) {
				while ($liiterow=mysql_fetch_array($liiteres)) {
					if ($laskurow["hyvaksyja_nyt"] == $kukarow["kuka"]) {
						$lisa = "&nbsp;&nbsp;&nbsp;&nbsp;<a href='matkalasku.php?tee=$tee&tilausnumero=$tilausnumero&poistakuva={$liiterow["tunnus"]}'>*".t("poista")."*</a>";
					}
					else {
						$lisa = "";
					}
					echo "<a href='$PHP_SELF?tee=$tee&tilausnumero=$tilausnumero&id={$liiterow["tunnus"]}'>{$liiterow["selite"]}</a>$lisa <br>\n";
				}
			}

			echo "</td></tr>";

			echo "	<tr>
						<td class='back' colspan='2'><br></td>
					</tr>
					<tr>
						<td class='back' colspan='2'><font class='message'>".t("Liitä kuittikopio")."</font></td>
					</tr>

					<tr>
						<th>".t("Kuvan selite")."</th><th>".t("Tiedosto")."</th>
					</tr>
					<tr>
						<td><input type='text' name='kuvaselite' value='$kuvaselite'></td>
						<td><input name='userfile' type='file' onchange='submit();'></td>
						<td class='back'><input type='submit' value='".t("Tallenna")."'</td>
					</tr>";
			echo "</form>
					</table><br>";

			echo "<table>";
			
			if($laskurow["tilaustyyppi"] == "M" or $lisaatapahtumaan == "OK" or $toim == "SUPER") {
				if($perheid2 > 0) {
					echo "<tr><td class='back' colspan = '7'><font class='message'>Valitse kulutyyppi matkalle:</font> <font class='head'>$tapahtumarow[kommentti]</font></td></tr>";
				}
				else {
					echo "<tr><td class='back' colspan = '7'><font class='message'>Kulu lisätään uuteen tapahtumaan</font></td></tr>";
				}
				
				//	Vaihdettaessa tuotetta pitää pysyä oikeassa tyypissä
				if($tyyppi != "") {
					$a = array($tyyppi);
				}
				else {
					$a = array("A","B");
				}
				
				foreach($a as $viranomaistyyppi) {

					$tyyppi_nimi="";
					switch ($viranomaistyyppi) {
						case "A":
							$tyyppi_nimi="Päiväraha";
							$lisat = " and left(tuote.tuoteno, 3) != 'PPR'";
							break;
						case "B":
							$tyyppi_nimi="Muu kulu";
							$lisat = "";	
							break;
					}

					$query = "	SELECT *
								FROM tuote
								JOIN tili ON tili.yhtio=tuote.yhtio and tili.tilino=tuote.tilino
								WHERE tuote.yhtio='$kukarow[yhtio]'
								and tuotetyyppi='$viranomaistyyppi'
								and status !='P'
								and tuote.tilino!=''
								$lisat
								ORDER BY tuote.vienti IN ('$yhtiorow[maa]') DESC, tuote.vienti ASC, tuote.nimitys";
					$tres=mysql_query($query) or pupe_error($query);
					$valinta="";
					if (mysql_num_rows($tres)>0){
						
						if($rivitunnus > 0) {
							$onchange = "document.getElementById('tuoteno').value=this.value; document.getElementById('kuivat').value='JOO'; document.getElementById('lisaarivi').submit(); return false;";
						}
						else {
							$onchange = "submit();";
						}
						
						$valinta = "<select name='tuoteno' $extra onchange=\"$onchange\"><option value=''>".t("Lisää $tyyppi_nimi")."</option>";

						while ($trow=mysql_fetch_array($tres)) {
							if ($trow["tuoteno"] == $tuoteno) {
								$sel="selected";
							}
							else {
								$sel="";
							}

							if($viranomaistyyppi == "A" and $trow["vienti"] != $yhtiorow["maa"]) {
								$valinta .= "<option value='$trow[tuoteno]' $sel>$trow[vienti] - $trow[nimitys]</option>";
							}
							else {
								$valinta .= "<option value='$trow[tuoteno]' $sel>$trow[nimitys]</option>";
							}

						}
						$valinta .= "</select>";
					}

					if ($valinta != "") {
						echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
						echo "<input type='hidden' name='tee' value='$tee'>";
						echo "<input type='hidden' name='lopetus' value='$lopetus'>";
						echo "<input type='hidden' name='toim' value='$toim'>";
						echo "<input type='hidden' name='perheid2' value='$perheid2'>";					
						echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
						echo "<input type='hidden' name='tyyppi' value='$viranomaistyyppi'>";
						echo "<td class='back'>$valinta</td>";
						echo "</form>";
					}				
				}

				if($perheid2 > 0 and $perheid2 != $tilausrivi["tunnus"]) {
					echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
					echo "<input type='hidden' name='tee' value='$tee'>";
					echo "<input type='hidden' name='lopetus' value='$lopetus'>";
					echo "<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";						
					echo "<tr><td class='back'><input type='submit' value='".t("Uusi tapahtuma")."'></td></tr>";
					echo "</form>";						
				}
			}
		}
		else {
			echo "	<tr><th>".t("Viite")."</th>
					<td>{$laskurow["viite"]}</td></tr>";
		}

		echo "</table><br><br>";

		if ($tyyppi != "" and $tuoteno != "") {
			
			$query = "	select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and tuotetyyppi='$tyyppi' and status !='P'";
			$tres=mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($tres) == 1){
				$trow=mysql_fetch_array($tres);
			}
			else {
				die("<font class='error'>".t("VIRHE!!! Viranomaistuote puuttuu (3)")."</font><br>");
			}

			echo "<font class='message'>".t("Lisää")." $trow[nimitys]</font><hr><br>$errori";
			echo "<form id='lisaarivi' action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='$tee'>";
			echo "<input type='hidden' name='lopetus' value='$lopetus'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
			echo "<input type='hidden' id='kuivat' name='kuivat' value=''>";			
			echo "<input type='hidden' id='tuoteno' name='tuoteno' value='$tuoteno'>";
			echo "<input type='hidden' name='rivitunnus' value='$rivitunnus'>";
			echo "<input type='hidden' name='perheid2' value='$perheid2'>";			
			echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
			
			if($rivitunnus > 0) {
				echo "<font class='error'>HUOM! Jos et lisää riviä se poistetaan erittelystä/matkalaskusta</font><br>";
			}
			
			echo "<table><tr>";
			
			if($tapa != "MUOKKAA" and $perheid2>0) {
				if(!isset($kustp)) {
					$kustp = $tapahtumarow["kustp"];
				}
				if(!isset($kohde)) {
					$kohde = $tapahtumarow["kohde"];
				}
				if(!isset($projekti)) {
					$projekti = $tapahtumarow["projekti"];
				}
				if(!isset($maa)) {
					$maa = $tapahtumarow["maa"];
				}
			}
			
			//	Tehdään kustannuspaikkamenut
			$query = "SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);
			$kustannuspaikka = "<select name = 'kustp' style=\"width: 100px\"><option value = ' '>".t("Ei kustannuspaikkaa");
			while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
				$valittu = "";
				if ($kustannuspaikkarow[0] == $kustp) {
					$valittu = "selected";
				}
				$kustannuspaikka .= "<option value = '$kustannuspaikkarow[0]' $valittu>$kustannuspaikkarow[1]";
			}
			$kustannuspaikka .= "</select>";

			$query = "SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);
			$kustannuspaikka .= " <select name = 'kohde' style=\"width: 100px\"><option value = ' '>".t("Ei kohdetta");
			while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
				$valittu = "";
				if ($kustannuspaikkarow[0] == $kohde) {
					$valittu = "selected";
				}
				$kustannuspaikka .= "<option value = '$kustannuspaikkarow[0]' $valittu>$kustannuspaikkarow[1]";
			}
			$kustannuspaikka .= "</select>";

			$query = "SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);
			$kustannuspaikka .= " <select name = 'projekti' style=\"width: 100px\"><option value = ' '>".t("Ei projektia");
			while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
				$valittu = "";
				if ($kustannuspaikkarow[0] == $projekti) {
					$valittu = "selected";
				}
				$kustannuspaikka .= "<option value = '$kustannuspaikkarow[0]' $valittu>$kustannuspaikkarow[1]";
			}
			$kustannuspaikka .= "</select>";


			if (!isset($alkukk)) $alkukk=date("m");
			if (!isset($alkuvv)) $alkuvv=date("Y");

			if (!isset($loppukk)) $loppukk=date("m");
			if (!isset($loppuvv)) $loppuvv=date("Y");

			if ($tyyppi == "A") {
				echo "<th>".t("Kustannuspaikka")."</th><th>".t("Alku")."</th><th>".t("Loppu")."</th><th>".t("Hinta")."</th></tr>";
				echo "<tr><td>$kustannuspaikka</td><td><input type='text' name='alkupp' value='$alkupp' size='3' maxlength='2'> <input type='text' name='alkukk' value='$alkukk' size='3' maxlength='2'> <input type='text' name='alkuvv' value='$alkuvv' size='5' maxlength='4'> ".t("klo").":<input type='text' name='alkuhh' value='$alkuhh' size='3' maxlength='2'>:<input type='text' name='alkumm' value='$alkumm' size='3' maxlength='2'>&nbsp;</td>
						<td>&nbsp;<input type='text' name='loppupp' value='$loppupp' size='3' maxlength='2'> <input type='text' name='loppukk' value='$loppukk' size='3' maxlength='2'> <input type='text' name='loppuvv' value='$loppuvv' size='5' maxlength='4'> ".t("klo").":<input type='text' name='loppuhh' value='$loppuhh' size='3' maxlength='2'>:<input type='text' name='loppumm' value='$loppumm' size='3' maxlength='2'></td><td align='center'>$trow[myyntihinta]</td>";
				$cols=4;
				$leveys=80;
			}
			elseif ($tyyppi == "B") {
				$lisa = "";
				if ($maa != "" and $maa != $yhtiorow["maa"]) {
					$lisa = "<th>".t("Ulkomaan ALV")."</th>";
					$cols=6;
				}
				else {
					$cols=5;
				}

				echo "<th>".t("Kustannuspaikka")."</th><th>".t("Kohdemaa")."</th><th>".t("Kpl")."</th><th>".t("Hinta")."</th><th>".t("Alv")."</th>$lisa</tr>";
				echo "<tr><td>$kustannuspaikka</td>";

				$query = "	SELECT distinct koodi, nimi
							FROM maat
							WHERE nimi != ''
							ORDER BY koodi";
				$vresult = mysql_query($query) or pupe_error($query);

 				echo "<td><select name='maa' onchange='submit();' style='width: 150px;'>";

				while ($vrow = mysql_fetch_array($vresult)) {
					$sel = "";
					if ($maa == "" and $yhtiorow["maa"] == $vrow["koodi"]) {
						$sel = "selected";
					}
					elseif ($maa == $vrow["koodi"]) {
						$sel = "selected";
					}

					echo "<option value = '$vrow[koodi]' $sel>".t($vrow["nimi"])."</option>";
				}

 				echo "</select></td>";

				echo "<td><input type='text' name='kpl' value='$kpl' size='6'></td>";

				//	Hinta saadaan antaa, jos meillä ei ole ennettu hintaa
				if ($trow["myyntihinta"] > 0) {
					echo "<td align='center'><input type='hidden' name='hinta' value='$trow[myyntihinta]'>$trow[myyntihinta]</td>";
				}
				else {
					echo "<td><input type='text' name='hinta' value='$hinta' size='8'></td>";
				}

				if ($maa != "" and $maa != $yhtiorow["maa"]) {

					echo "<td>0 %</td>";

					//	Haetaan oletusalv tuotteelta
					if ($alvulk == "") {
					$query = "select * from tuotteen_alv where yhtio='$kukarow[yhtio]' and maa='$maa' and tuoteno='$tuoteno' limit 1";
					$alhire = mysql_query($query) or pupe_error($query);
						$alvrow=mysql_fetch_array($alhire);
						$alvulk=$alvrow["alv"];
						if ($alvulk == "") {
							echo "<font class='error'>".t("Kulun arvonlisäveroa kohdemaassa ei ole määritelty")."</font><br>";
						}
					}

					echo "<td>".alv_popup_oletus("alvulk",$alvulk, $maa, 'lista')."</td>";
				}
				else {
					echo "<td>{$trow["alv"]}</td>";
				}

				$leveys=50;
			}

			echo "<td class='back'><input type='submit' name='lisaa' value='".t("Lisää")."'></td></tr>";


			echo "<tr><th colspan='$cols'>".t("Kommentti")."</th></tr>";
			echo "<tr><td colspan='$cols'><textarea name='kommentti' rows='4' cols='80'>".str_replace("<br>","\n",$kommentti)."</textarea></td>";
			
			if ($toim == "SUPER") {
				echo "<tr><th colspan='$cols'>".t("Poikkeava tilinumero, oletus on")." '$trow[tilino]'</th></tr>";
				if($tilino == $trow[tilino] or $kuivat == "JOO" or $kulupoiminta == "JOO") {
					$tilino	= "";
				}
				
				echo "<tr><td colspan='$cols'><input type='text' name='tilino' value = '$tilino' size='8'></td>";
			}
			
			echo "<td class='back'><input type='submit' name='tyhjenna' value='".t("Tyhjennä")."'></td>";
			
			if($laskurow["tilaustyyppi"] != "M") {
				//	Jos laskun ja rivien loppusumma heittää näytetään erotus..
				$query = "	SELECT sum(rivihinta) summa
							FROM tilausrivi
							WHERE yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero' and tyyppi='M'";
				$result=mysql_query($query) or pupe_error($query);
				$rivisumma=mysql_fetch_array($result);
				if( (float) $rivisumma["summa"] + ((float) $hinta * (float) $kpl) !=  (float) $laskurow["summa"]) {
					echo "<tr><td class='back' align='right' colspan='3'>".("Käsittelemättä")."</td><td class='back' align='right' colspan='2'><font clasS='error'>".number_format(( (float) $laskurow["summa"] - ((float) $rivisumma["summa"] + ((float) $hinta * (float) $kpl))),2, ', ', ' ')."</font></td></tr>";
				}
			}
			
			echo "</tr></table></form>";

		}

		/*	
			rivit
			Piilotetaan rivit joilla ei ole kappaleita (päiväraha, jos vain puolikas..)
		*/
		
		$sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);
		$summa = "sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))";
		$query = "	SELECT tilausrivi.*, tuotetyyppi, $sorttauskentta,
					
					if (tuote.tuotetyyppi='A' or tuote.tuotetyyppi='B', concat(date_format(kerattyaika, '%d.%m.%Y %k:%i'),' - ',date_format(toimitettuaika, '%d.%m.%Y %k:%i')), '') ajalla,
					concat_ws('/',kustp.nimi,kohde.nimi,projekti.nimi) kustannuspaikka, 
					if(tilausrivi.perheid=0, tilausrivi.tunnus,
					(select max(tunnus) from tilausrivi t use index(yhtio_otunnus) where tilausrivi.yhtio = t.yhtio and tilausrivi.otunnus = t.otunnus and tilausrivi.perheid=t.perheid and tilausrivi.tyyppi=t.tyyppi)) viimonen,
					if(tilausrivi.perheid=0, tilausrivi.tunnus, tilausrivi.perheid) perhe,
					tiliointi.tilino tilino,
					tilausrivin_lisatiedot.kulun_kohdemaa, kulun_kohdemaa
					FROM tilausrivi use index(yhtio_otunnus)
					LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
					LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus
					LEFT JOIN tiliointi ON tiliointi.yhtio=tilausrivin_lisatiedot.yhtio and tiliointi.tunnus=tilausrivin_lisatiedot.tiliointirivitunnus
					LEFT JOIN kustannuspaikka kustp ON tiliointi.yhtio=kustp.yhtio and tiliointi.kustp=kustp.tunnus
					LEFT JOIN kustannuspaikka projekti ON tiliointi.yhtio=projekti.yhtio and tiliointi.projekti=projekti.tunnus
					LEFT JOIN kustannuspaikka kohde ON tiliointi.yhtio=kohde.yhtio and tiliointi.kohde=kohde.tunnus
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					and otunnus='$tilausnumero'
					and tilausrivi.tyyppi='M'
					ORDER BY tilausrivi.perheid2, tilausrivi.tunnus";
		$result = mysql_query($query) or pupe_error($query);

		$saa_hyvaksya = "";
		
		if (mysql_num_rows($result) > 0) {
			
			echo "<br><br><font class='message'>".t("Kulurivit")."</font><hr><table cellpadding='0' cellspacing='0' width='800'>";

			$saa_hyvaksya="JOO";

			echo "<tr><th style='text-align: left; width: 20px;'>&nbsp;#</th><th>".t("Kulu")."</th><th>".t("Kustannuspaikka")."</th><th>".t("Kpl")."</th><th>".t("Hinta")."</th><th>".t("Yhteensä")."</th></tr>";
			$eka="joo";
			$edperhe = $edperheid2 = 0;
			$summa=0;
			while ($row=mysql_fetch_array($result)) {
			
				if (($row["perhe"] != $edperhe) and $eka != "joo" and $row["perheid2"] == $edperheid2) {
					echo "<tr><td class='back' colspan = '5' height='10' style='border-right: 1px solid;'></td></tr>";
				}

				if (($row["perheid2"] != $edperheid2) and $eka != "joo") {
					echo "<tr><td class='back' colspan = '6' height='25'></td></tr>";
				}
				
				echo "<tr>";
				
				$diva = $divl = false;
				if($edperheid2 == 0) {
					$diva = true;
				}
				elseif ($row["perheid2"] != $edperheid2) {
					$diva = true;					
				}
				elseif ($rivei == 1) {
					$divl = true;
				}
				else {
					$divm = true;
				}
								
				unset($border);
				
				//	Lisätään style kaikien solujen oikeaan reunaan
				$b = "border-right: 1px solid;";
				$border["t"]["r"] .= $b;
				$border["k"]["r"] .= $b;
				$border["a"]["r"] .= $b;
				
				//	Loput on tapauskohtaisia
				if($diva) {
					$b = "border-top: 1px solid;";
					$border["t"]["m"] .= $b;
					$border["t"]["r"] .= $b;

					
					$query = "	SELECT count(*), sum(if(perheid=0 or perheid=tilausrivi.tunnus,1,0)) isia, sum(if(kommentti !='' and (perheid = 0 or perheid=tilausrivi.tunnus),1,0)) kommentteja, sum(if(tuotetyyppi='A' and (perheid = 0 or perheid=tilausrivi.tunnus),0,0)) aikoja
								FROM tilausrivi
								LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and otunnus = $tilausnumero and perheid2 = $row[perheid2] and tilausrivi.tyyppi = 'M'";
					$abures = mysql_query($query) or pupe_error($query);
					$aburow = mysql_fetch_array($abures);
					if($toim == "SUPER") {
						$v = 3;
					}
					else {
						$v = 1;
					}
					
					$valeja = ($aburow[0]*$v)+$aburow[1]+$aburow[2]+$aburow[3]+$aburow[4]-1;
					$rivei = $aburow[0];
					$tapahtumia++;

					$teksti = "$row[kommentti]";

					echo "<td rowspan = '$valeja' style='border-top: 1px solid; border-left: 1px solid;  border-bottom: 1px solid;'>&nbsp;$tapahtumia</td>";
				}
				if($divl or $rivei == 1) {
					
					$b = "border-bottom: 1px solid;";
					if($row["tuotetyyppi"] == "A") {
						$border["a"]["m"] .= $b;
						$border["a"]["r"] .= $b;
					}
					elseif($row["kommentti"] != "") {
						$border["k"]["m"] .= $b;
						$border["k"]["r"] .= $b;
					}
					else {
						$border["t"]["m"] .= $b;
						$border["t"]["r"] .= $b;
					}										
				}
				
				//echo "<pre>".print_r($border, true)."</pre>";
				
				$eka="";
				
				if($laskurow["tilaustyyppi"] != "M" and $row["tuoteno"] == "") {
					

					$query = "	SELECT *
								FROM tuote
								JOIN tili ON tili.yhtio=tuote.yhtio and tili.tilino=tuote.tilino
								WHERE tuote.yhtio='$kukarow[yhtio]'
								and tuotetyyppi='B'
								and status !='P'
								and tuote.tilino!=''
								ORDER BY tuote.vienti IN ('$yhtiorow[maa]') DESC, tuote.vienti ASC, tuote.nimitys";
					$tres=mysql_query($query) or pupe_error($query);
					$valinta="";
					if (mysql_num_rows($tres)>0){
						$valinta = "<select name='vaihda_tuote' onchange='submit();' style='width: 125px;'><option value=''>".t("Määrittele kulu")."</option>";

						while ($trow=mysql_fetch_array($tres)) {
							if ($trow["tuoteno"] == $row["tuoteno"]) {
								$sel="selected";
							}
							else {
								$sel="";
							}
							
							$valinta .= "<option value='$trow[tuoteno]' $sel>$trow[nimitys]</option>";

						}
						$valinta .= "</select>";
					}
					
					echo "<td style='font-weight: bold; ".$border["t"]["m"]."'>
							<form action = '$PHP_SELF#ankkuri_{$row["tunnus"]}' method='post' autocomplete='off'>
								<input type='hidden' name='tee' value='$tee'>
								<input type='hidden' name='lopetus' value='$lopetus'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tilausnumero' value='$tilausnumero'>
								<input type='hidden' name='tapa' value='MUOKKAA'>
								<input type='hidden' name='vaihda_tyyppi' value='B'>
								<input type='hidden' name='kulupoiminta' value='JOO'>
								<input type='hidden' name='rivitunnus' value='{$row["perhe"]}'>
								$valinta
							</form></td>";

					$edrivi = $row["tunnus"];
				}
				else {
					echo "<td style='font-weight: bold; ".$border["t"]["m"]."'>$row[nimitys]<a name='ankkuri_{$row["tunnus"]}'></a></td>";
				}
				
				echo "<td style='".$border["t"]["m"]."'>$row[kustannuspaikka]</td>";
				echo "<td align='right' style='font-weight: bold; ".$border["t"]["m"]."'>$row[kpl]</td>";
				echo "<td align='right' style='font-weight: bold; ".$border["t"]["m"]."'>".number_format($row["hinta"],2, ', ', ' ')."</td>";
				echo "<td align='right' style='font-weight: bold; ".$border["t"]["r"]."'>".number_format($row["rivihinta"],2, ', ', ' ')."</td>";
				
				//	Aina kun perhe vaihtuu voidaan näyttää nappulat!
				if (($row["perhe"] != $edperhe) and 
					$row["tuoteno"] != "" and 
					(
						(($laskurow["hyvaksyja_nyt"] == $kukarow["kuka"] and $kukarow["taso"] == 2) or 
						($laskurow["hyvak1"] == $kukarow["kuka"] and $laskurow["h1time"] == '0000-00-00 00:00:00')) or
						($toim == "SUPER" and strtotime($laskurow["tapvm"]) > strtotime($yhtiorow["tilikausi_alku"]) and strtotime($laskurow["tapvm"]) < strtotime($yhtiorow["tilikausi_loppu"])) 
					)) {
						
					echo "<td class='back'><div style='float:left;'><form action = '$PHP_SELF' method='post' autocomplete='off'>";
					echo "<input type='hidden' name='tee' value='$tee'>";
					echo "<input type='hidden' name='lopetus' value='$lopetus'>";
					echo "<input type='hidden' name='toim' value='$toim'>";
					echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
					echo "<input type='hidden' name='tapa' value='MUOKKAA'>";
					echo "<input type='hidden' name='rivitunnus' value='$row[perhe]'>";
					echo "<input type='hidden' name='perheid2' value='$row[perheid2]'>";
					echo "<input type='submit' value='".t("Muokkaa")."'>";
					echo "</form></div>";
					
					if($row["perheid2"] != $row["tunnus"] or $rivei == 1 and $laskurow["tilaustyyppi"] == "M") {
						echo "<div style='float:left;'><form action = '$PHP_SELF' method='post' autocomplete='off'>";
						echo "<input type='hidden' name='tee' value='$tee'>";
						echo "<input type='hidden' name='lopetus' value='$lopetus'>";
						echo "<input type='hidden' name='toim' value='$toim'>";
						echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
						echo "<input type='hidden' name='tapa' value='POISTA'>";
						echo "<input type='hidden' name='rivitunnus' value='$row[perhe]'>";
						echo "<input type='hidden' name='perheid2' value='$row[perheid2]'>";						
						echo "<input type='submit' value='".t("Poista")."'>";
						echo "</form></div>";						
					}
					
					if($row["perheid2"] == $row["tunnus"]) {
						echo "<div style='float:left;'><form action = '$PHP_SELF' method='post' autocomplete='off'>";
						echo "<input type='hidden' name='tee' value='$tee'>";
						echo "<input type='hidden' name='lopetus' value='$lopetus'>";
						echo "<input type='hidden' name='toim' value='$toim'>";
						echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
						echo "<input type='hidden' name='perheid2' value='$row[perheid2]'>";
						echo "<input type='hidden' name='lisaatapahtumaan' value='OK'>";						
						echo "<input type='submit' value='".t("Lisää kulu tapahtumaan")."'>";
						echo "</form></div>";						
					}
					
					echo "</td>";
				}
								
				echo "</tr>";
								
				//	Kommentit aina vain perheen loppuun!
				if ($row["tunnus"] == $row["viimonen"]) {
					
					if($row["tuotetyyppi"] != "A") {
						echo "<tr><th>".t("kohdemaa").":</th><td colspan='4' style=\"font-style: italic; border-right: 1px solid;\">".maa($row[kulun_kohdemaa])."</td></tr>";
					}

					if($toim == "SUPER") {
						$query = "	SELECT nimi
									FROM tili
									WHERE yhtio = '{$kukarow["yhtio"]}' and tilino = '$row[tilino]'";
						$tilires=mysql_query($query) or pupe_error($query);
						$tilirow = mysql_fetch_array($tilires);
						echo "<tr><th>".t("Tilino").":</th><td colspan='4' style=\"font-style: italic; border-right: 1px solid;\">$row[tilino] {$tilirow["nimi"]}</td></tr>";
					}					
					
					if($row["kommentti"] != "") {
						echo "<tr><th style='".$border["k"]["m"]."'>".t("Kommentti").":</th><td colspan='4' style=\"font-style: italic; ".$border["k"]["r"]."\">$row[kommentti]</td></tr>";
					}
															
					if($row["tuotetyyppi"] == "A") {
						echo "<tr><th style='".$border["a"]["m"]."'>".t("Ajalla").":</th><td colspan='4' style=\"font-style: italic; ".$border["a"]["r"]."\">$row[ajalla]</td></tr>";
					}
				}
				
				
				$summa+=$row["rivihinta"];
				$edperhe = $row["perhe"];
				$edperheid2 = $row["perheid2"];
				$rivei--;
			}

			echo "<tr><td colspan='3' class='back'></td><th colspan='2' style='text-align:right;'>".t("Yhteensä")."</th><th style='text-align:right;'>".number_format($summa,2, ', ', ' ')."</th></tr>";
			
		}
		else {
			echo "<table>";
		}
		
		if($laskurow["tilaustyyppi"] != "M") {
			echo "<tr><td colspan='3' class='back'></td><th colspan='2' style='text-align:right;'>".t("Laskun summa")."</th><th style='text-align:right;'>".number_format($laskurow["summa"],2, ', ', ' ')."</th></tr>";
			
			//	Jos laskun ja rivien loppusumma heittää näytetään erotus..
			$query = "	SELECT sum(rivihinta) summa
						FROM tilausrivi
						WHERE yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero' and tyyppi='M'";
			$result=mysql_query($query) or pupe_error($query);
			$rivisumma=mysql_fetch_array($result);

			if(round($rivisumma["summa"], 2) != round($laskurow["summa"], 2)) {
				echo "<tr><td colspan='3' class='back'></td><th colspan='2' style='text-align:right;'><font class='error'>".t("Käsittelemättä")."</font></th><th style='text-align:right;'><font class='error'>".number_format(($laskurow["summa"] - $rivisumma["summa"]),2, ', ', ' ')."</font></th></tr>";
			}				
			else {
				echo "	<tr><td class='back'><br></td></tr>
						<tr>";

				echo "		<td colspan='4' class='back' align='right'></td>";

				if($lopetus != "") {
					echo "<form name='palaa' action='".urldecode($lopetus)."' method='post'>
					<td class='back' colspan='2' align='right'><input type='submit' value='".t("Palaa sinne mistä tulitkin")."'></td></form></tr></table>";
				}
				else {
					echo "<form name='palaa' action='$PHP_SELF' method='post'>
					<td class='back' colspan='2' align='right'><input type='submit' value='".t("Palaa")."'></td></form></tr></table>";
				}
			}
		}
		else {
			echo "	<tr><td class='back'><br></td></tr>
					<tr>";

			echo "		<td colspan='4' class='back' align='right'></td>";
			
			if($lopetus != "") {
				echo "<form name='palaa' action='".urldecode($lopetus)."' method='post'>
				<td class='back' colspan='2' align='right'><input type='submit' value='".t("Palaa sinne mistä tulitkin")."'></td></form></tr></table>";
			}
			else {
				echo "<form name='palaa' action='$PHP_SELF' method='post'>
				<td class='back' colspan='2' align='right'><input type='submit' value='".t("Palaa")."'></td></form></tr></table>";
			}
		}
		
		if($toim == "SUPER") {
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off' onsubmit=\"return confirm('".t("Oletko varma, että haluat käsitellä kululaskun uudestaan.\\n\\nLaskun uudelleenkäsittely poistaa kaikki erittelyrivit ja tiliöinnit.\\n\\nTietoja EI VOI PALAUTTAA.")."')\">";
			echo "<input type='hidden' name='tee' value='UUDELLEENKASITTELE'>";
			echo "<input type='hidden' name='lopetus' value='$lopetus'>";
			echo "<input type='hidden' name='toim' value='$toim'>";					
			echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
			echo "<td class='back' colspan='4'><input type = 'submit' value='".t("Uudelleenkäsittele lasku")."'></td>";
			echo "</form>";

		}
		
	}

	if ($id>0) {
		echo "<iframe src='view.php?id=$id' name='alaikkuna' width='100%' height='60%' align='bottom' scrolling='auto'></iframe>";
	}
}

if ($tee == "") {

	echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='UUSI'>";
	echo "<input type='hidden' name='lopetus' value='$lopetus'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<table><tr>";
	echo "<th>".t("Perusta uusi matkalasku asiakkaalle")."</th>";
	echo "<td class='back'><input type='text' size='30' name='ytunnus'></td>";
	echo "<td class='back'><input type='Submit' value='".t("Perusta")."'></td>";
	echo "</tr></table>";
	echo "</form>";

	$query = "	SELECT lasku.*, kuka.nimi kayttaja
				FROM lasku
				LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and kuka.kuka=lasku.hyvak1
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and tila = 'H'
				and (
					(hyvak2='{$kukarow["kuka"]}' and h2time='0000-00-00 00:00:00') or
					(hyvak3='{$kukarow["kuka"]}' and h3time='0000-00-00 00:00:00') or
					(hyvak4='{$kukarow["kuka"]}' and h4time='0000-00-00 00:00:00') or
					(hyvak5='{$kukarow["kuka"]}' and h5time='0000-00-00 00:00:00')
				)
				and tilaustyyppi = 'M'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result)) {

		echo "<br><br><font class='message'>".t("Hyväksynnässä olevat matkalaskut")."</font><hr>";

		echo "<table><tr><th>".t("Käyttäjä")."</th><th>".t("Asiakas")."</th><th>".t("Viesti")."</th><th>".t("Summa")."</th><tr>";
		while ($row=mysql_fetch_array($result)) {
			echo "<tr>";
			echo "<td>$row[kayttaja]</td>";
			echo "<td>$row[toim_nimi]</td>";
			echo "<td>$row[viite]</td>";
			echo "<td>$row[summa]</td>";
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='MUOKKAA'>";
			echo "<input type='hidden' name='lopetus' value='$lopetus'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";

			if ($kukarow["taso"] == 2) {
				echo "<td class='back'><input type='Submit' value='".t("Muokkaa")."'></td>";
			}
			else {
				echo "<td class='back'><input type='Submit' value='".t("Tarkastele")."'></td>";
			}
			echo "</form>";
			echo "</tr>";

		}
		echo "</table>";
	}

	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				and hyvak1='{$kukarow["kuka"]}' and h1time = '0000-00-00 00:00:00'
				and tila = 'H'
				and tilaustyyppi = 'M'";
	$result=mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result)) {

		echo "<br><br><font class='message'>".t("Avoimet matkalaskusi")."</font><hr>";

		echo "<table><tr><th>".t("Asiakas")."</th><th>".t("Viesti")."</th><th>".t("Summa")."</th><tr>";
		while ($row=mysql_fetch_array($result)) {
			echo "<tr>";
			echo "<td>$row[toim_nimi]</td>";
			echo "<td>$row[viite]</td>";
			echo "<td>$row[summa]</td>";
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='MUOKKAA'>";
			echo "<input type='hidden' name='lopetus' value='$lopetus'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";
			echo "<td class='back'><input type='Submit' value='".t("Muokkaa")."'></td>";
			echo "</form>";
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='POISTA'>";
			echo "<input type='hidden' name='lopetus' value='$lopetus'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";
			echo "<td class='back'><input type='Submit' value='".t("Poista")."'></td>";
			echo "</form>";
			echo "</tr>";
		}
		echo "</table>";
	}

	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				and tila IN ('H','Y','M','P','Q') and h1time != '0000-00-00 00:00:00'
				and hyvak1 = '{$kukarow[kuka]}'
				and tilaustyyppi = 'M'";
	$result=mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result)) {

		echo "<br><br><font class='message'>".t("Vanhat matkalaskusi")."</font><hr>";

		echo "<table><tr><th>".t("Asiakas")."</th><th>".t("Viesti")."</th><th>".t("Summa")."</th><th>".t("Tila")."</th><tr>";
		while ($row=mysql_fetch_array($result)) {

			$laskutyyppi=$row["tila"];
			$alatila=$row["alatila"];

			//tehdään selväkielinen tila/alatila
			require "inc/laskutyyppi.inc";

			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='MUOKKAA'>";
			echo "<input type='hidden' name='lopetus' value='$lopetus'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";
			echo "<tr>";
			echo "<td>$row[toim_nimi]</td>";
			echo "<td>$row[viite]</td>";
			echo "<td>$row[summa]</td>";
			echo "<td>".t($laskutyyppi)."</td>";
			echo "<td class='back'><input type='Submit' value='".t("Tarkastele")."'></td>";
			echo "</tr>";
			echo "</form>";

		}
		echo "</table>";
	}

}

require("inc/footer.inc");

?>
