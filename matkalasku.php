<?php
require ("inc/parametrit.inc");

echo "<font class='head'>".t('Matkalaskut')."</font><hr><br><br>";

//	tarkastetaan ett‰ k‰ytt‰j‰lle voidaan perustaa matkalaskuja
$query = "	SELECT * FROM toimi WHERE yhtio='$kukarow[yhtio]' and nimi='$kukarow[nimi]'";
$result = mysql_query($query) or pupe_error($query);

if(mysql_num_rows($result)==1) {
	$trow=mysql_fetch_array($result);
}
else {
	die("<font class='error'>".t("Lis‰‰ itsesi ensin toimittajaksi.")."</font>");
}

if($tee=="UUSI") {
	if ($ytunnus != '') {
		require ("inc/asiakashaku.inc");

		if($asiakasid>0) {
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
						liitostunnus 	= '$asiakasid',
						hyvaksynnanmuutos = '$trow[oletus_hyvaksynnanmuutos]',
						suoraveloitus 	= '',
						tilaustyyppi	= 'M'";

			$result = mysql_query($query) or pupe_error($query);
			$tilausnumero = mysql_insert_id();

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

if($tee=="MUOKKAA") {
	if((int)$tilausnumero==0) {
		echo "<font class='error'>".t("Matkalaskun numero puuttuu")."</font>";
		$tee="";
	}
	else {
		
		$query 	= "	select *
						from lasku
						where tunnus='$tilausnumero' and yhtio='$kukarow[yhtio]' and tilaustyyppi='M' and tila='H'";
		$result  	= mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result)==0) {
			die("<font class='error'>".t("Matkalaskun numero puuttuu")."</font>");
		}
		else {
			$laskurow   = mysql_fetch_array($result);
		}
		
		//	Muokataan kulurivi‰, poistetaan koko nippu ja laitetaan muokattavaksi
		if($rivitunnus>0) {
			$query	= "	SELECT tilausrivi.*, tuote.tuotetyyppi
						FROM tilausrivi use index (PRIMARY)
						LEFT JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
						where tilausrivi.yhtio = '$kukarow[yhtio]'
						and otunnus = '$tilausnumero'
						and tilausrivi.tunnus  = '$rivitunnus'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$tilausrivi  = mysql_fetch_array($result);
				
				// Poistetaan muokattava tilausrivi
				$query = "	DELETE from tilausrivi
							WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$rivitunnus'";
				$result = mysql_query($query) or pupe_error($query);
				
				// Poistetaan muokattava tilausrivi
				if($tilausrivi["perheid"]>0) {
					$query = "	DELETE from tilausrivi
								WHERE yhtio = '$kukarow[yhtio]' and perheid = '$rivitunnus'";
					$result = mysql_query($query) or pupe_error($query);
				}
				
				//	Jos muokataan otetaan dada talteen
				if($tapa=="MUOKKAA") {
					list($pv, $aika)=explode(" ",$tilausrivi["kerattyaika"]);
					list($alkuvv,$alkukk,$alkupp)=explode("-",$pv);
					list($alkuhh,$alkumm)=explode(":",$aika);
					
					list($pv, $aika)=explode(" ",$tilausrivi["toimitettuaika"]);
					list($loppuvv,$loppukk,$loppupp)=explode("-",$pv);
					list($loppuhh,$loppumm)=explode(":",$aika);

					$tuoteno	= $tilausrivi["tuoteno"];
					$kpl		= $tilausrivi["kpl"];
					$hinta		= $tilausrivi["hinta"];					
					$kommentti	= $tilausrivi["kommentti"];
					$rivitunnus	= $tilausrivi["tunnus"];
					
					$tyyppi		= $tilausrivi["tuotetyyppi"];
				}
				else {
					$tyhjenna="joo";
				}
			}
		}
		
		//	Koitetaan lis‰t‰ uusi rivi!
		if($tuoteno!="" and isset($lisaa)) {

			$query = "	select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
			$tres=mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($tres)<>1) {
				echo "<font class='error'>".t("VIRHE!!! Viranomaistuote0 puuttuu")." $lisaa_tuoteno $query</font><br>";
			}
			else {
				$trow=mysql_fetch_array($tres);
			}
			
			$tyyppi			= $trow["tuotetyyppi"];
			$tuoteno_array 	= array();
			$errori			= "";
			
			if($tyyppi=="A") {

				/*
					P‰iv‰rahoilla ratkaistaan p‰iv‰t
					Samalla oletetaan ett‰ puolip‰iv‰raha on aina P+tuoteno
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
				
				if(($alkupp>=1 and $alkupp<=31) and ($alkukk>=1 and $alkukk<=12) and $alkuvv>0 and ($alkuhh>=0 and $alkuhh<=24) and ($loppupp>=1 and $loppupp<=31) and ($loppukk>=1 and $loppukk<=12) and $loppuvv>0 and ($loppuhh>=0 and $loppuhh<=24)) {
					$alku=mktime($alkuhh, $alkumm, 0, $alkukk, $alkupp, $alkuvv);
					$loppu=mktime($loppuhh, $loppumm, 0, $loppukk, $loppupp, $loppuvv);
					
					//	Tarkastetaan ett‰ t‰ll‰ v‰lill‰ ei jo ole jotain arvoa
					$query = "	SELECT tilausrivi.tunnus
								FROM tilausrivi
								JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tyyppi 	= 'M'
								and (	(kerattyaika >= '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm' and kerattyaika <= '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm') or 
										(kerattyaika <  '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm' and toimitettuaika > '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm') or
										(toimitettuaika >= '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm' and toimitettuaika <= '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm'))
								and tuotetyyppi IN ('A','B','E')";
					$result = mysql_query($query) or pupe_error($query);

					if(mysql_num_rows($result)>0) {
						$errori .= "<font class='error'>".t("VIRHE!!! Annettu p‰iv‰m‰‰r‰ menee yli jo annetun p‰iv‰m‰‰r‰n kanssa")."</font><br>";
					}
					
					$paivat=$puolipaivat=$ylitunnit=$tunnit=0;
					
					//	montako tuntia on oltu matkalla?
					$tunnit=($loppu-$alku)/3600;
					$paivat=floor($tunnit/24);
					
					$ylitunnit=$tunnit-($paivat*24);
					
					if($ylitunnit>10) {
						$paivat++;
					}
					elseif($ylitunnit>6 and $trow["vienti"]=="FI") {
						$puolipaivat++;
					}
					elseif($ylitunnit<=10 and $trow["vienti"]!="FI") {
						$errori .= "<font class='error'>".t("VIRHE!!! Ulkomaanp‰iv‰rahalla on oltava v‰hint‰‰n 10 tuntia")."</font><br>";
					}
					
					if($paivat>0) {
						$tuoteno_array[$tuoteno]				=$tuoteno;
						$kpl_array[$tuoteno]					=$paivat;

						$alkuaika_array[$tuoteno]				="$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm:00";
						$loppuaika_array[$tuoteno]				="$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm:00";						
					}
					
					if($puolipaivat>0) {
						//	Tarkastetaan ett‰ p‰iv‰rahalle on puolip‰iv‰raha
						$query = "	select * from tuote where yhtio='$kukarow[yhtio]' and tuotetyyppi='$tyyppi' and tuoteno='P$tuoteno'";
						$tres2=mysql_query($query) or pupe_error($query);

						if(mysql_num_rows($tres2)<>1) {
							die("<font class='error'>".t("VIRHE!!! Viranomaistuote2 puuttuu")." $query	</font><br>");
						}
						else {
							$trow2=mysql_fetch_array($tres2);
						}
						
						$tuoteno_array[$trow2["tuoteno"]]		=$trow2["tuoteno"];
						$kpl_array[$trow2["tuoteno"]]			=$puolipaivat;

						$alkuaika_array[$trow2["tuoteno"]]		="$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm:00";
						$loppuaika_array[$trow2["tuoteno"]]		="$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm:00";													
					}
					
					//echo "SAATIIN p‰ivarahoja: $paivat puolip‰iv‰rahoja: $puolipaivat<br>";
				}
				else {
					$errori .= "<font class='error'>".t("VIRHE!!! P‰iv‰rahalle on annettava alku ja loppuaika")."</font><br>";
				}
			}
			elseif($tyyppi=="B") {
				if($kpl<=0) {
					$errori .= "<font class='error'>".t("VIRHE!!! kappalem‰‰r‰ on annettava")."</font><br>";
				}
				
				if($kommentti == "" and $trow["kommentoitava"]!="") {
					$errori .= "<font class='error'>".t("VIRHE!!! Kululle on annettava selite")."</font><br>";
				}
				
				if($trow["myyntihinta"]>0) {
					$hinta=$trow["myyntihinta"];
				}
				
				if($hinta<=0) {
					$errori .= "<font class='error'>".t("VIRHE!!! Kulun hinta puuttuu")."</font><br>";
				}
				
				$tuoteno_array[$trow["tuoteno"]]	= $trow["tuoteno"];
				$kpl_array[$trow["tuoteno"]]		= $kpl;				
				$hinta_array[$trow["tuoteno"]]		= $hinta;
			}				
			
			//	Lis‰t‰‰n annetut rivit
			$perheid=0;
			
			if($errori == "") {
				foreach($tuoteno_array as $lisaa_tuoteno) {

					//	Haetaan tuotteen tiedot
					$query = "	select * from tuote where yhtio='$kukarow[yhtio]' and tuotetyyppi='$tyyppi' and tuoteno='$lisaa_tuoteno'";
					$tres=mysql_query($query) or pupe_error($query);
					if(mysql_num_rows($tres)<>1) {
						echo "<font class='error'>".t("VIRHE!!! Viranomaistuote1 puuttuu")." $lisaa_tuoteno $query</font><br>";
					}
					else {
						$trow=mysql_fetch_array($tres);
					}

					$kpl 	= str_replace(",",".",$kpl_array[$trow["tuoteno"]]);
					$hinta 	= str_replace(",",".",$hinta_array[$trow["tuoteno"]]);
					$rivihinta = round($kpl*$hinta,2);
					
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
								alv 		= '0',
								netto		= 'N',
								hinta 		= '$hinta',
								rivihinta 	= '$rivihinta',
								otunnus 	= '$tilausnumero',
								tyyppi 		= 'M',
								toimaika 	= '',
								kommentti 	= '$kommentti',
								var 		= '',
								try			= '$trow[try]',
								osasto		= '$trow[osasto]',
								perheid		= '$perheid',
								tunnus 		= '$rivitunnus',
								nimitys 	= '$trow[nimitys]',
								kerattyaika = '$alkuaika_array[$tuoteno]',
								toimitettuaika = '$loppuaika_array[$tuoteno]'";
					$insres = mysql_query($query) or die($query);
					$lisatty_tun = mysql_insert_id();

					//	Laitetaan lis‰tietoihin kustannuspaikat jne..
					$query  = "	SELECT *
								FROM tilausrivin_lisatiedot
								WHERE yhtio			 = '$kukarow[yhtio]'
								and tilausrivitunnus = '$lisatty_tun'";
					$lisatied_res = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($lisatied_res) > 0) {
						$lisatied_row = mysql_fetch_array($lisatied_res);

						$query = "	UPDATE tilausrivin_lisatiedot
									SET ei_nayteta 	= '$myy_lapsi_ein[$indeksi]'
									WHERE tunnus 	= '$lisatty_tun'";
						$result = mysql_query($query) or pupe_error($query);
					}
					else {
						$query = "	INSERT INTO tilausrivin_lisatiedot
									SET yhtio		= '$kukarow[yhtio]',
									tilausrivitunnus= '$lisatty_tun',
									ei_nayteta 		= '$myy_lapsi_ein[$indeksi]',
									lisatty			= now(),
									lisannyt 		= '$kukarow[kuka]'";
						$result = mysql_query($query) or pupe_error($query);
					}					
					
					$rivitunnus=0;
										
					//	Jos meill‰ on splitattu rivi niin pidet‰‰n nippu kasassa
					if($perheid == 0 and count($tuoteno_array)>1) {
						$perheid=$lisatty_tun;

						$query = " 	UPDATE tilausrivi set perheid='$lisatty_tun'
									WHERE yhtio='$kukarow[yhtio]' and tunnus='$perheid'";
						$updres=mysql_query($query) or die($query);
					}
				}
				$tyhjenna="JOO";
				
				//	P‰ivitet‰‰n laskun summa
				$query = "	SELECT sum(rivihinta) summa
							FROM tilausrivi
							WHERE yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero' and tyyppi='M'";
				$result=mysql_query($query) or pupe_error($query);
				$summarow=mysql_fetch_array($result);
				
				$query = " update lasku set summa='$summarow[summa]' where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
				$updres=mysql_query($query) or pupe_error($query);
			}
		}
		
		if($tyhjenna!="") {
			$tuoteno="";
			//$tyyppi="";
			$kommentti="";
			$rivitunnus="";
			
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
		
		
		// kirjoitellaan otsikko
		echo "<table>";

		// t‰ss‰ alotellaan koko formi.. t‰m‰ pit‰‰ kirjottaa aina
		echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='tee' value='$tee'>";

		echo "<tr>";
		echo "<th align='left'>".t("Asiakas").":</th>";

		echo "<td>$laskurow[toim_nimi]<br>$laskurow[toim_nimitark]<br>$laskurow[toim_osoite]<br>$laskurow[toim_postino] $laskurow[toim_postitp]</td>";

		echo "</tr>";
		echo "</form></table><br>";

		echo "<table><tr>";
		echo "<th colspan='4'>".t('Lis‰‰ kulu').":</th>";
		
		foreach(array("A","B") as $tyyppi) {
			
			$tyyppi_nimi="";
			switch ($tyyppi) {
				case "A":
					$tyyppi_nimi="P‰iv‰raha";
					break;
				case "B":
					$tyyppi_nimi="Muu kulu";
					break;
			}
						
			$query = "	select * from tuote where yhtio='$kukarow[yhtio]' and tuotetyyppi='$tyyppi'";
			$tres=mysql_query($query) or pupe_error($query);
			$valinta="";
			if(mysql_num_rows($tres)>1){
				$valinta = "<select name='tuoteno' onchange='submit();'><option value=''>".t("Lis‰‰ $tyyppi_nimi")."</option>";
				
				while($trow=mysql_fetch_array($tres)) {
					if($trow["tuoteno"]==$tuoteno) {
						$sel="selected";
					}
					else {
						$sel="";
					}
					$valinta .= "<option value='$trow[tuoteno]' $sel>$trow[nimitys]</option>";
				}
				$valinta .= "</select>";
			}
			elseif(mysql_num_rows($tres)==1) {
				$trow=mysql_fetch_array($tres);
				$valinta = "<input type='hidden' name='tuoteno' value='$trow[tuoteno]'><input type='submit' value='".t("Lis‰‰")." $trow[nimitys]'>";
			}
			
			if($valinta != "") {
				echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
				echo "<input type='hidden' name='tee' value='$tee'>";
				echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
				echo "<input type='hidden' name='tyyppi' value='A'>";		
				echo "<td class='back'>$valinta</td>";
				echo "</form>";					
			}
		}
		
		echo "</table><br><br>";
				
		if($tyyppi!="" and $tuoteno != "") {
			
			$query = "	select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
			$tres=mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($tres)==1){
				$trow=mysql_fetch_array($tres);
				$tyyppi=$trow["tuotetyyppi"];
			}
			else {
				die("<font class='error'>".t("VIRHE!!! Viranomaistuote3 puuttuu")."</font><br>");
			}
			
			echo "<font class='message'>".t("Lis‰‰")." $trow[nimitys]</font><hr><br>$errori";
			
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='$tee'>";
			echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";			
			echo "<input type='hidden' name='rivitunnus' value='$rivitunnus'>";			
			echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";		
			echo "<table><tr>";

			//	Tehd‰‰n kustannuspaikkamenut
			$query = "SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);
			$kustannuspaikka = "<select name = 'kustp'><option value = ' '>".t("Ei kustannuspaikkaa");
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
			$kustannuspaikka .= "<select name = 'kohde'><option value = ' '>".t("Ei kohdetta");
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
			$kustannuspaikka .= "<select name = 'projekti'><option value = ' '>".t("Ei projektia");
			while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
				$valittu = "";
				if ($kustannuspaikkarow[0] == $projekti) {
					$valittu = "selected";
				}
				$kustannuspaikka .= "<option value = '$kustannuspaikkarow[0]' $valittu>$kustannuspaikkarow[1]";
			}
			$kustannuspaikka .= "</select>";
			
			
			if(!isset($alkukk)) $alkukk=date("m");
			if(!isset($alkuvv)) $alkuvv=date("Y");

			if(!isset($loppukk)) $loppukk=date("m");
			if(!isset($loppuvv)) $loppuvv=date("Y");
			
			if($tyyppi=="A") {
				echo "<th>".t("Kustannuspaikka")."</th><th>".t("Alku")."</th><th>".t("Loppu")."</th></tr>";
				echo "<tr><td>$kustannuspaikka</td><td><input type='text' name='alkupp' value='$alkupp' size='3' maxlength='2'> <input type='text' name='alkukk' value='$alkukk' size='3' maxlength='2'> <input type='text' name='alkuvv' value='$alkuvv' size='5' maxlength='4'> ".t("klo").":<input type='text' name='alkuhh' value='$alkuhh' size='3' maxlength='2'>:<input type='text' name='alkumm' value='$alkumm' size='3' maxlength='2'>&nbsp;</td>
						<td>&nbsp;<input type='text' name='loppupp' value='$loppupp' size='3' maxlength='2'> <input type='text' name='loppukk' value='$loppukk' size='3' maxlength='2'> <input type='text' name='loppuvv' value='$loppuvv' size='5' maxlength='4'> ".t("klo").":<input type='text' name='loppuhh' value='$loppuhh' size='3' maxlength='2'>:<input type='text' name='loppumm' value='$loppumm' size='3' maxlength='2'></td>";
				$cols=3;
				$leveys=80;
			}
			elseif($tyyppi=="B") {
				echo "<th>".t("Kustannuspaikka")."</th><th>".t("Kpl")."</th><th>".t("Hinta")."</th></tr>";
				echo "<tr><td>$kustannuspaikka</td><td><input type='text' name='kpl' value='$kpl' size='4'></td>";
				
				//	Hinta saadaan antaa, jos meill‰ ei ole ennettu hintaa
				if($trow["myyntihinta"] > 0) {
					echo "<td><input type='hidden' name='hinta' value='$trow[myyntihinta]'>$trow[myyntihinta]</td>";
				}
				else {
					echo "<td><input type='text' name='hinta' value='$hinta' size='4'></td>";
				}
				
				$cols=3;
				$leveys=50;				
			}
			
			echo "<td class='back'><input type='submit' name='lisaa' value='".t("Lis‰‰")."'></td></tr>";
			
			
			echo "<tr><th colspan='$cols'>".t("Kommentti")."</th></tr>";
			echo "<tr><td colspan='$cols'><input type='text' name='kommentti' value='$kommentti' size='$leveys'></td>";			
			echo "<td class='back'><input type='submit' name='tyhjenna' value='".t("Tyhjenn‰")."'></td></tr></table></form>";
						
		}
		
		//	rivit
		echo "<br><br><font class='message'>".t("Rivit")."</font><hr><table>";
		$sorttauskentta = generoi_sorttauskentta(4);	
		$query = "	SELECT tilausrivi.*, tuotetyyppi, $sorttauskentta,
					if(tuote.tuotetyyppi='A' or tuote.tuotetyyppi='B', concat(date_format(kerattyaika, '%d.%m.%Y %k:%i'),' - ',date_format(toimitettuaika, '%d.%m.%Y %k:%i')), '') ajalla
					FROM tilausrivi
					LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
					WHERE tilausrivi.yhtio='$kukarow[yhtio]'
					and otunnus='$tilausnumero'
					and tyyppi='M'
					ORDER BY sorttauskentta, tunnus";
		$result = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result)>0) {
			echo "<tr><th>".t("Kulu")."</th><th>".t("Kpl")."</th><th>".t("Hinta")."</th></tr>";
			$eka="joo";
			while($row=mysql_fetch_array($result)) {
				
				if(($row["perheid"]==$row["tunnus"] or $row["perheid"]==0) and $eka!="joo") {
					echo "<tr><td class='back' height='5'></td></tr>";
				}
				$eka="";
				
				echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
				echo "<input type='hidden' name='tee' value='$tee'>";
				echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
				echo "<input type='hidden' name='tapa' value='MUOKKAA'>";		
				echo "<input type='hidden' name='rivitunnus' value='$row[tunnus]'>";
				
				echo "<tr><td>$row[nimitys]</td><td>$row[kpl]</td><td>$row[hinta]</td>";
				
				if($row["perheid"]==$row["tunnus"] or $row["perheid"]==0) {
					echo "<td class='back'><input type='submit' value='".t("Muokkaa")."'></td>";
				}
				echo "</tr>";
				
				if($row["kommentti"]!="") {
					echo "<tr><th>".t("Kommentti").":</th><td colspan='2'>$row[kommentti]</td></tr>";
				}

				if(in_array($row["tuotetyyppi"], array("A","B"))) {
					echo "<tr><th>".t("Ajalla").":</th><td colspan='2'>$row[ajalla]</td></tr>";
				}
						
				echo "</tr></form>";
				
			}			
		}
	}
	
	echo "<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
			<tr><td class='back'><br></td></tr>
			<tr><td colspan='4' class='back' align='right'><input type='submit' value='".t("Palaa")."'></td></tr></table>";
	
}

if($tee == "") {    
	echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='UUSI'>";
	echo "<table><tr>";
	echo "<th>".t('Perusta uusi matkalasku asiakkaalle')."</th>";
	echo "<td class='back'><input type='text' size='30' name='ytunnus'></td>";	
	echo "<td class='back'><input type='Submit' value='".t("Perusta")."'></td>";
	echo "</tr></table>";
	echo "</form>";
	
	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				and tila = 'H'
				and tilaustyyppi = 'M'";
	$result=mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result)) {
		
		echo "<br><br><font class='message'>".t("Avoimet matkalaskut")."</font><hr>";
		
		echo "<table><tr><th>".t("Asiakas")."</th><tr>";
		while($row=mysql_fetch_array($result)) {
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='MUOKKAA'>";
			echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";			
			echo "<tr>";
			echo "<td>$row[toim_nimi]</td>";
			echo "<td class='back'><input type='Submit' value='".t("Muokkaa")."'></td>";
			echo "</tr>";
			echo "</form>";

		}
		echo "</table>";
	}
}


require("inc/footer.inc");
?>
