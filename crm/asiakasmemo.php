<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
		require ("../inc/parametrit.inc");

		//Jos ylläpidossa on luotu uusi asiakas
		if ($yllapidossa == "asiakas" and $yllapidontunnus != '') {
			$asiakasid 	= $yllapidontunnus;
		}
	}

	echo "<font class='head'>".t("Asiakasmemo")."</font><hr>";

	if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
	    if ($ytunnus == '' and (int) $asiakasid == 0) {

			js_popup(-100);

			echo "<br><table>";
			echo "<tr>
					<th>".t("Asiakas").":</th>
					<form method = 'post'>
					<td><input type='text' size='30' name='ytunnus'> ",asiakashakuohje(),"</td>
					<td class='back'><input type='submit' value='".t("Jatka")."'></td>
					</tr>";
			echo "</form>";
			echo "</table>";
		}

		if ($ytunnus != '' or $asiakasid > 0) {
			$kutsuja 	= "asiakasemo.php";
			$ahlopetus 	= "{$palvelin2}crm/asiakasmemo.php////";

			require ("inc/asiakashaku.inc");
		}
	}

	$asmemo_lopetus = "{$palvelin2}crm/asiakasmemo.php////ytunnus=$ytunnus//asiakasid=$asiakasid";

	if ($lopetus != "") {
		// Lisätään tämä lopetuslinkkiin
		$asmemo_lopetus = $lopetus."/SPLIT/".$asmemo_lopetus;
	}

	///* Asiakas on valittu *///
	if ($ytunnus != '') {
		///* Jos ollaan käyty ylläpidossa *///
		if ($yllapidossa == 'asiakas') {
			$yhtunnus = '';
		}

		if ($tee == "SAHKOPOSTI") {

			list($email, $ekuka) = explode("###", $email);

			// Haetaan muistiinpano
			$query = "	SELECT kalenteri.*, asiakas.nimi, asiakas.nimitark, asiakas.toim_nimi, asiakas.toim_nimitark, asiakas.asiakasnro
						FROM kalenteri
						LEFT JOIN asiakas ON (kalenteri.yhtio = asiakas.yhtio and kalenteri.liitostunnus = asiakas.tunnus)
						WHERE kalenteri.tunnus = '$tunnus'";
			$res = pupe_query($query);
			$row = mysql_fetch_array($res);

			$meili = "\n$kukarow[nimi] ".t("lähetti sinulle asiakasmemon").".\n\n\n";
			$meili .= t("Tapa").": $row[tapa]\n\n";
			$meili .= t("Ytunnus").": $row[asiakas]\n";
			$meili .= t("Asiakasnumero").": $row[asiakasnro]\n";
			$meili .= t("Asiakas").": $row[nimi] $row[nimitark] $row[toim_nimi] $row[toim_nimitark]\n";
			$meili .= t("Pävämäärä").": ".tv1dateconv($row["pvmalku"])."\n\n";
			$meili .= t("Viesti").":\n".str_replace("\r\n","\n", $row["kentta01"])."\n\n";
			$meili .= "-----------------------\n\n";

			$tulos = mail($email, mb_encode_mimeheader(t("Asiakasmemo")." $yhtiorow[nimi]", "ISO-8859-1", "Q"), $meili,"From: ".mb_encode_mimeheader($kukarow["nimi"], "ISO-8859-1", "Q")." <$kukarow[eposti]>\nReply-To: ".mb_encode_mimeheader($kukarow["nimi"], "ISO-8859-1", "Q")." <".$row["eposti"].">\n", "-f $yhtiorow[postittaja_email]");

			if ($row["tyyppi"] == "Lead") {
				$eviesti = "$kukarow[nimi] lähetti leadin osoitteeseen: $email";
			}
			else {
				$eviesti = "$kukarow[nimi] lähetti memon osoitteeseen: $email";
			}

			$kysely = "	INSERT INTO kalenteri
						SET tapa 		= '$row[tapa]',
						asiakas  		= '$row[asiakas]',
						liitostunnus 	= '$row[liitostunnus]',
						henkilo  		= '$row[henkilo]',
						kuka     		= '$kukarow[kuka]',
						yhtio    		= '$kukarow[yhtio]',
						tyyppi   		= 'Memo',
						pvmalku  		= now(),
						kentta01 		= '$eviesti',
						perheid 		= '$row[tunnus]',
						laatija			= '$kukarow[kuka]',
						luontiaika		= now()";
			$result = pupe_query($kysely);

			if ($row["tyyppi"] == "Lead") {
				$kysely = "	INSERT INTO kalenteri
							SET tapa 		= '$row[tapa]',
							asiakas  		= '$row[asiakas]',
							liitostunnus 	= '$row[liitostunnus]',
							henkilo  		= '$row[henkilo]',
							kuka     		= '$ekuka',
							myyntipaallikko	= '$row[myyntipaallikko]',
							yhtio    		= '$kukarow[yhtio]',
							tyyppi   		= '$row[tyyppi]',
							pvmalku  		= '$row[pvmalku]',
							kentta01 		= '$row[kentta01]',
							kuittaus 		= '$row[kuittaus]',
							laatija			= '$kukarow[kuka]',
							luontiaika		= now()";
				$result = pupe_query($kysely);
			}
			//echo "<br>Sähköposti lähetetty osoitteeseen: $email<br><br>";

			$tunnus = "";
			$tee 	= "";
			$meili 	= "";
		}

		///* Allrightin asiakasanalyysi*///
		if ($tee == "ASANALYYSI") {

			echo "<table>";
			echo "<form method='POST'>
					<input type='hidden' name='tee' value='LISAAASANALYYSI'>
					<input type='hidden' name='ytunnus' value='$ytunnus'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='asiakasid' value='$asiakasid'>
					<input type='hidden' name='yhtunnus' value='$yhtunnus'>";

			echo "<tr><th colspan='2' align='left'><br>".t("Asiakasvertailu").":</th></tr>";
			echo "<tr><td>".t("Miten tuotteet esillä").":</td><td><textarea name='esilla' cols='40' rows=5' wrap='hard'></textarea></td></tr>";
			echo "<tr><td>".t("Myymälän ileisilme").":</td><td><textarea name='yleisilme' cols='40' rows='5' wrap='hard'></textarea></td></tr>";
			echo "<tr><th colspan='2' align='left'><br>".t("Tuotteiden jakauma merkeittäin").":</th></tr>";
			echo "<tr><td>".t("Puvut").":</td><td><textarea name='puvut' 			cols='40' rows='2' wrap='hard'></textarea></td></tr>";
			echo "<tr><td>".t("Kypärät").":</td><td><textarea name='kyparat' 		cols='40' rows='2' wrap='hard'></textarea></td></tr>";
			echo "<tr><td>".t("Saappaat").":</td><td><textarea name='saappaat' 		cols='40' rows='2' wrap='hard'></textarea></td></tr>";
			echo "<tr><td>".t("Hanskat").":</td><td><textarea name='hanskat'		cols='40' rows='2' wrap='hard'></textarea></td></tr>";
			echo "<tr><th><br></th><th></th></tr>";
			echo "<tr><td>".t("Muut huomiot").":</td><td><textarea name='muuta' 	cols='40' rows='5' wrap='hard'></textarea></td></tr>";
			echo "<tr><td>".t("Keskustelut/sovitut extrat").":</td><td><textarea name='extrat' 	cols='40' rows='5' wrap='hard'></textarea></td></tr>";
			echo "<tr><th align='left'><input type='submit' name='submit' value='".t("Tallenna")."'></th></tr>";
			echo "</form></table>";
		}

		///* Lisätään uusi memeotietue*///
		if ($tee == "UUSIMEMO" and $tyyppi == "Muistutus" and $muistutusko == "") {
			$muistutusko 	= "Muistutus";
			$tee 			= "";
		}
		else {
			$muistutusko 	= "";
		}

		if ($tee == "UUSIMEMO") {

			if (checkdate($mkka, $mppa, $mvva)) {
				$pvmalku  = "'$mvva-$mkka-$mppa $mhh:$mmm:00'";
			}
			else {
				$pvmalku  = "'".date("Y-m-d H:i:s")."'";
			}

			if ($kuittaus == '' and ($tyyppi == "Muistutus" or $tyyppi == "Lead")) {
				$kuittaus = 'K';
			}

			if ($kuka == "") {
				$kuka = $kukarow["kuka"];
			}

			if ($korjaus == '') {
				if ($viesti != '') {
					$kysely = "	INSERT INTO kalenteri
								SET tapa 		= '$tapa',
								asiakas  		= '$ytunnus',
								liitostunnus 	= '$asiakasid',
								henkilo  		= '$yhtunnus',
								kuka     		= '$kuka',
								myyntipaallikko	= '$myyntipaallikko',
								yhtio    		= '$kukarow[yhtio]',
								tyyppi   		= '$tyyppi',
								pvmalku  		= $pvmalku,
								kentta01 		= '$viesti',
								kuittaus 		= '$kuittaus',
								laatija			= '$kukarow[kuka]',
								luontiaika		= now()";
					$result = pupe_query($kysely);
					$muist = mysql_insert_id();

					if ($tyyppi == "Muistutus") {

						$query = "	SELECT *
									FROM kuka
									WHERE yhtio	= '$kukarow[yhtio]'
									and kuka	= '$kuka'";
						$result = pupe_query($query);
						$row = mysql_fetch_array($result);

						// Käyttäjälle lähetetään tekstiviestimuistutus
						if ($row["puhno"] != '' and strlen($viesti) > 0 and $sms_palvelin != "" and $sms_user != "" and $sms_pass != "") {

							$ok = 1;

							$teksti = substr("Muistutus $yhtiorow[nimi]. $tapa. ".$viesti, 0, 160);
							$teksti = urlencode($teksti);

							$retval = file_get_contents("$sms_palvelin?user=$sms_user&pass=$sms_pass&numero=$row[puhno]&viesti=$teksti&not_before_date=$mvva-$mkka-$mppa&not_before=$mhh:$mmm:00&yhtio=$kukarow[yhtio]&kalenteritunnus=$muist");

							if (trim($retval) == "0") $ok = 0;

							if ($ok == 1) {
								echo "<font class='error'>VIRHE: Tekstiviestin lähetys epäonnistui! $retval</font><br><br>";
							}

							if ($ok == 0) {
								echo "<font class='message'>Tekstiviestimuistutus lehetetään!</font><br><br>";
							}
						}
					}
					$aputyyppi 			= $tyyppi;
					$tapa     			= "";
					$viesti   			= "";
					$tunnus   			= "";
					$tyyppi	   			= "";
					$mvva 				= "";
					$mkka 				= "";
					$mppa 				= "";
					$mhh 				= "";
					$mmm				= "";
					$kuka				= "";
					$kuittaus 			= "";
					$myyntipaallikko 	= "";
				}
			}
			else {
				$kysely = "	UPDATE kalenteri
							SET tapa 		= '$tapa',
							asiakas  		= '$ytunnus',
							liitostunnus 	= '$asiakasid',
							henkilo  		= '$yhtunnus',
							kuka     		= '$kuka',
							myyntipaallikko	= '$myyntipaallikko',
							yhtio    		= '$kukarow[yhtio]',
							tyyppi   		= '$tyyppi',
							pvmalku  		= $pvmalku,
							kentta01 		= '$viesti',
							kuittaus 		= '$kuittaus',
							muuttaja		= '$kukarow[kuka]',
							muutospvm		= now()
							WHERE tunnus = '$korjaus'";
				$result = pupe_query($kysely);

				$aputyyppi 			= $tyyppi;
				$tapa     			= "";
				$viesti   			= "";
				$tunnus   			= "";
				$tyyppi	   			= "";
				$mvva 				= "";
				$mkka 				= "";
				$mppa 				= "";
				$mhh 				= "";
				$mmm				= "";
				$kuka				= "";
				$kuittaus 			= "";
				$myyntipaallikko 	= "";
			}

			$tee = "";
		}

		// tallenetaan uutena ominaisuutena liitetiedostoja memolle.
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

			if ($korjaus == '') {
				$liitostunnus = $muist;
			}
			else {
				$liitostunnus = $korjaus;
			}

			$tallennustring = "Liitetiedosto ".$aputyyppi;

			$id = tallenna_liite("userfile", $aputyyppi, $liitostunnus, $tallennustring);

		}

		if ($tee == "LISAAASANALYYSI") {
			if ($esilla != '' || $yleisilme != '') {
				$kysely = "	INSERT INTO kalenteri
							SET tapa = 'asiakasanalyysi',
							asiakas  = '$ytunnus',
							liitostunnus = '$asiakasid',
							henkilo  = '$yhtunnus',
							kuka     = '$kukarow[kuka]',
							yhtio    = '$kukarow[yhtio]',
							kentta01 = '$esilla',
							kentta02 = '$yleisilme',
							kentta03 = '$puvut',
							kentta04 = '$kyparat',
							kentta05 = '$saappaat',
							kentta06 = '$hanskat',
							kentta07 = '$muuta',
							kentta08 = '$extrat',
							tyyppi   = 'Memo',
							pvmalku  = now()";
				$result = pupe_query($kysely);
			}
			$tee = '';
		}

		if ($tee == "POISTAMEMO") {
			$kysely = "	UPDATE kalenteri
						SET
						tyyppi = concat('DELETED ',tyyppi)
						WHERE tunnus		= '$tunnus'
						and yhtio			= '$kukarow[yhtio]'
						and asiakas			= '$ytunnus'
						and liitostunnus 	= '$asiakasid'";
			$result = pupe_query($kysely);

			$kysely = "	UPDATE liitetiedostot
						SET 	liitos = concat('DELETED ',liitos)
						WHERE 	liitostunnus 	= '$tunnus'
						AND 	liitos			= '$liitostyyppi'
						and 	yhtio			= '$kukarow[yhtio]'";

			$result = pupe_query($kysely);

			$tee = '';
		}

		if ($tee == "KORJAAMEMO") {

			// Haetaan viimeisin muistiinpano
			$query = "	SELECT *
						FROM kalenteri
						WHERE liitostunnus 	= '$asiakasid'
						and tyyppi			in ('Memo','Muistutus','Kuittaus','Lead','Myyntireskontraviesti')
						and tapa		   != 'asiakasanalyysi'
						and yhtio			= '$kukarow[yhtio]'
						and (perheid=0 or tunnus=perheid)
						ORDER BY tunnus desc
						LIMIT 1";
			$res = pupe_query($query);
			$korjrow = mysql_fetch_array($res);

			$tapa     			= $korjrow["tapa"];
			$viesti   			= $korjrow["kentta01"];
			$yhtunnus 			= $korjrow["henkilo"];
			$tunnus   			= $korjrow["tunnus"];
			$tyyppi	   			= $korjrow["tyyppi"];
			$mvva 				= substr($korjrow["pvmalku"],0,4);
			$mkka 				= substr($korjrow["pvmalku"],5,2);
			$mppa 				= substr($korjrow["pvmalku"],8,2);

			$mhh 				= substr($korjrow["pvmalku"],11,2);
			$mmm 				= substr($korjrow["pvmalku"],14,2);

			$kuka				= $korjrow["kuka"];
			$kuittaus 			= $korjrow["kuittaus"];
			$myyntipaallikko 	= $korjrow["myyntipaallikko"];

			if ($tyyppi == "Muistutus") {
				$muistutusko 	= 'Muistutus';
			}

			$tee = "";
		}

		if ($tee == 'paivita_tila') {
			$tee2 = $tee;
			$tee = '';

			$query_update = "	UPDATE asiakas SET tila = '$astila'
								WHERE yhtio	= '$kukarow[yhtio]'
                                and ytunnus	= '$ytunnus'
                                and tunnus	= '$asiakasid'";
			$result_update = pupe_query($query_update);

			echo t("Vaihdettiin asiakkaan tila")."<br/>";
		}

		if ($tee == '') {

			///* Yhteyshenkilön tiedot, otetaan valitun yhteyshenkilön tiedot talteen  *///
			if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
				$query = "	SELECT *
							FROM yhteyshenkilo
							WHERE yhtio		 = '$kukarow[yhtio]'
							and liitostunnus = '$asiakasid'
							and tyyppi 		 = 'A'
							ORDER BY nimi";
				$result = pupe_query($query);

				$yhenkilo = "<form method='POST'>
							<input type='hidden' name='ytunnus' value='$ytunnus'>
							<input type='hidden' name='lopetus' value='$lopetus'>
							<input type='hidden' name='asiakasid' value='$asiakasid'>
							<select name='yhtunnus' Onchange='submit();'>
							<option value=''>".t("Yleistiedot")."</option>";

				while ($row = mysql_fetch_array($result)) {

					if($yhtunnus == $row["tunnus"]) {
						$sel      = 'SELECTED';
						$yemail   = $row["email"];
						$ynimi    = $row["nimi"];
						$yfax     = $row["fax"];
						$ygsm     = $row["gsm"];
						$ypuh     = $row["puh"];
						$ywww     = $row["www"];
						$ytitteli = $row["titteli"];
						$yfakta   = $row["fakta"];
					}
					else {
						$sel = '';
					}

					$yhenkilo .= "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
				}
				$yhenkilo .= "</select></form>";

				//Näytetään asiakkaan tietoja jos yhteyshenkilöä ei olla valittu
				if ($yhtunnus == "kaikki" or $yhtunnus == '') {
					$yemail   = $asiakasrow["email"];
					$ynimi    = "";
					$ygsm     = $asiakasrow["gsm"];
					$ypuh     = $asiakasrow["puhelin"];
					$yfax     = $asiakasrow["fax"];
					$ywww     = "";
					$ytitteli = "";
					$yfakta   = $asiakasrow["fakta"];
				}

				///* Asiakaan tiedot ja yhteyshenkilön tiedot *///
				echo "<table>";

				echo "<tr>";
				echo "<th align='left'>".t("Laskutusasiakas").":</th>";
				echo "<th align='left'>".t("Toimitusasiakas").":</th>";
				echo "<th align='left'>".t("Muut tiedot").":</th>";
				echo "<th align='left'>".t("Toiminnot").":</th>";
				echo "</tr>";


				//asiakkaan toimitusosoite
				if ($asiakasrow['toim_osoite']=='') {
					$asiakasrow['toim_nimi']     = $asiakasrow['nimi'];
					$asiakasrow['toim_nimitark'] = $asiakasrow['nimitark'];
					$asiakasrow['toim_osoite']   = $asiakasrow['osoite'];
					$asiakasrow['toim_postino']  = $asiakasrow['postino'];
					$asiakasrow['toim_postitp']  = $asiakasrow['postitp'];
				}


				echo "<tr>";
				echo "<td>$asiakasrow[nimi]</td><td>$asiakasrow[toim_nimi]</td><td>$yhenkilo</td>";

				$query = "	SELECT yhtio
							FROM oikeu
							WHERE yhtio	= '$kukarow[yhtio]'
							and kuka	= '$kukarow[kuka]'
							and nimi	= 'yllapito.php'
							and alanimi = 'asiakas'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) > 0) {
					echo "<td><a href='{$palvelin2}yllapito.php?toim=asiakas&tunnus=$asiakasid&lopetus=$asmemo_lopetus'>".t("Luo uusi yhteyshenkilö")."</a></td>";
				}
				else {
					echo "<td>".t("(Luo uusi yhteyshenkilö)")."</td>";
				}

				echo "</tr>";
				echo "<tr>";
				echo "<td>$asiakasrow[nimitark]</td><td>$asiakasrow[toim_nimitark]</td><td>".t("Puh").": $ypuh</td>";


				if (mysql_num_rows($result) > 0 and $yhtunnus != '') {
					echo "<td><a href='{$palvelin2}yllapito.php?toim=asiakas&tunnus=$asiakasid&lopetus=$asmemo_lopetus'>".t("Muuta yhteyshenkilön tietoja")."</a></td>";
				}
				else {
					echo "<td></td>";
				}

				echo "</tr>";
				echo "<tr>";
				echo "<td>$asiakasrow[osoite]</td><td>$asiakasrow[toim_osoite]</td><td>".t("Fax").": $yfax</td>";

				// Päivämäärät rappareita varten
				$kka = date("m",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
				$vva = date("Y",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
				$ppa = date("d",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
				$kkl = date("m");
				$vvl = date("Y");
				$ppl = date("d");

				$query = "	SELECT yhtio
							FROM oikeu
							WHERE yhtio	= '$kukarow[yhtio]'
							and kuka	= '$kukarow[kuka]'
							and nimi	= 'crm/kalenteri.php'
							and alanimi = ''";
				$result = pupe_query($query);

				if (mysql_num_rows($result) > 0) {
					echo "<td><a href='{$palvelin2}crm/kalenteri.php?lopetus=$asmemo_lopetus'>".t("Kalenteri")."</a></td>";
				}
				else {
					echo "<td>".t("Kalenteri")."</td>";
				}


				echo "</tr>";
				echo "<tr>";
				echo "<td>$asiakasrow[postino] $asiakasrow[postitp]</td><td>$asiakasrow[toim_postino] $asiakasrow[toim_postitp]</td><td>".t("Gsm").": $ygsm</td>";


				$query = "	SELECT yhtio
							FROM oikeu
							WHERE yhtio	= '$kukarow[yhtio]'
							and kuka	= '$kukarow[kuka]'
							and nimi	= 'raportit/myyntiseuranta.php'
							and alanimi = ''";
				$result = pupe_query($query);

				if (mysql_num_rows($result) > 0) {
					echo "<td><a href='{$palvelin2}raportit/asiakasinfo.php?ytunnus=$ytunnus&asiakasid={$asiakasrow["tunnus"]}&rajaus=MYYNTI&tee=go&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&tuoteosasto2=kaikki&yhtiot[]=$kukarow[yhtio]&jarjestys[]=&lopetus=$asmemo_lopetus'>".t("Myynninseuranta")."</a></td>";
				}
				else {
					echo "<td>".t("Myynninseuranta")."</td>";
				}

				echo "</tr>";
				echo "<tr>";
				echo "<td>$asiakasrow[fakta]</td><td></td><td>".t("Email").": $yemail";

				if ($yemail != "") {
					echo " &nbsp; <a href=\"mailto:$yemail\">".t("Email")."</a>";
				}

				echo "</td>";

				$query = "	SELECT yhtio
							FROM oikeu
							WHERE yhtio	= '$kukarow[yhtio]'
							and kuka	= '$kukarow[kuka]'
							and nimi	= 'raportit/asiakasinfo.php'
							and alanimi = ''";
				$result = pupe_query($query);

				if (mysql_num_rows($result) > 0) {
					echo "<td><a href='{$palvelin2}raportit/asiakasinfo.php?ytunnus=$ytunnus&asiakasid={$asiakasrow["tunnus"]}&rajaus=ALENNUKSET&lopetus=$asmemo_lopetus'>".t("Alennustaulukko")."</a></td>";
				}
				else {
					echo "<td><u>".t("Alennustaulukko")."</u></td>";
				}

				echo "</tr>";
				echo "<tr><td colspan='2'></td><td>".t("Tila").": ";
				echo "<form method='POST'>";
				echo "<input type='hidden' name='ytunnus' value='$ytunnus'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='asiakasid' value='$asiakasid'>
						<input type='hidden' name='tee' value='paivita_tila'>";
				echo "<select name='astila' Onchange='submit();'>";
				echo "<option value=''>".t("Ei tilaa")."</option>";


				$asosresult = t_avainsana("ASIAKASTILA");

				if ($tee2 == "") {
					$astila = $asiakasrow['tila'];
				}
				while ($asosrow = mysql_fetch_array($asosresult)) {
					$sel2 = '';
					if ($astila == $asosrow["selite"]) {
                        			$sel2 = "selected";
					}
					echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
        		}

				echo "</select></form>";
				echo "</td><td><a href='{$palvelin2}budjetinyllapito_tat.php?toim=ASIAKAS&ytunnus=$ytunnus&asiakasid={$asiakasrow["tunnus"]}&submit_button=joo&alkuvv=".date("Y")."&alkukk=01&loppuvv=".date("Y")."&loppukk=12&lopetus=$asmemo_lopetus'>".t("Asiakkaan myyntitavoitteet")."</a></td></tr>";

				if ($yfakta != '' or $ytitteli != '' or $ynimi != '') {
					echo "<tr><td colspan='2'>".t("Valittu yhteyshenkilö").": $ytitteli $ynimi</td><td colspan='2'>$yfakta</td></tr>";
				}

				echo "</table><br>";
			}

			///* Syötä memo-tietoa *///
			if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
				echo "<table width='620'>";

				echo "	<form method='POST' enctype='multipart/form-data'>
						<input type='hidden' name='tee' 		value='UUSIMEMO'>
						<input type='hidden' name='korjaus' 	value='$tunnus'>
						<input type='hidden' name='yhtunnus' 	value='$yhtunnus'>
						<input type='hidden' name='ytunnus' 	value='$ytunnus'>
						<input type='hidden' name='lopetus' 	value='$lopetus'>
						<input type='hidden' name='asiakasid' 	value='$asiakasid'>
						<input type='hidden' name='muistutusko' value='$muistutusko'>";

				if ($tyyppi == "Kuittaus") {
					echo "<input type='hidden' name='kuka' value='$kuka'>";
				}

				echo "<tr><th>".t("Lisää")."</th>";

				if ($yhtunnus > 0) {
					echo "<th>".t("Yhteyshenkilö").": $ynimi</th>";
				}
				else {
					echo "<td></td>";
				}

				$sel = array();
				$sel[$tyyppi] = "SELECTED";

				echo "<td><select name='tyyppi' Onchange='submit();'>
						<option value='Memo' $sel[Memo]>".t("Memo")."</option>
						<option value='Muistutus' $sel[Muistutus]>".t("Muistutus")."</option>
						<option value='Lead' $sel[Lead]>".t("Lead")."</option>
						<option value='Myyntireskontraviesti' $sel[Myyntireskontraviesti]>".t("Myyntireskontraviesti")."</option>";

				if ($tyyppi == "Kuittaus") {
					echo "<option value='Kuittaus' $sel[Kuittaus]>".t("Kuittaus")."</option>";
				}

				echo "</select></td>";

				echo "	<tr><th>".t("Tallenna tiedosto liitteeksi")."</th>";
				echo "	<td colspan='2'><input type = 'file' name = 'userfile' />";
				echo "	<input type='hidden' name='teeliite'	value='tallenna_pdf'>";
				echo "  <input type='hidden' name='yhtunnus' 	value='$yhtunnus'>
						<input type='hidden' name='ytunnus' 	value='$ytunnus'>
						<input type='hidden' name='asiakasid' 	value='$asiakasid'>";
				echo "	</td></tr>";

				echo "<tr><td colspan='3'><textarea cols='83' rows='3' name='viesti' wrap='hard'>$viesti</textarea></td></tr>";

				if ($tyyppi == "Muistutus") {
					echo "	<tr>
							<th>".t("Yhteydenottaja").":</th>
							<td colspan='2'><select name='kuka'>
							<option value='$kukarow[kuka]'>".t("Itse")."</option>";

					$query = "	SELECT distinct kuka.tunnus, kuka.nimi, kuka.kuka
								FROM kuka, oikeu
								WHERE kuka.yhtio	= '$kukarow[yhtio]'
								and oikeu.yhtio		= kuka.yhtio
								and oikeu.kuka		= kuka.kuka
								and oikeu.nimi		= 'crm/kalenteri.php'
								and kuka.kuka 		<> '$kukarow[kuka]'
								ORDER BY kuka.nimi";
					$result = pupe_query($query);

					while ($row = mysql_fetch_array($result)) {
						if ($row["kuka"] == $kuka) {
							$sel = "SELECTED";
						}
						else {
							$sel = "";
						}

						echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
					}
					echo "</select></td></tr>";

					if (!isset($mkka))
						$mkka = date("m");
					if (!isset($mvva))
						$mvva = date("Y");
					if (!isset($mppa))
						$mppa = date("d");
					if (!isset($mhh))
						$mhh = "08";
					if (!isset($mmm))
						$mmm = "00";

					echo "<tr><th>".t("Muistutuspäivämäärä (pp-kk-vvvv tt:mm)")."</th>
							<td colspan='2'><input type='text' name='mppa' value='$mppa' size='3'>-
							<input type='text' name='mkka' value='$mkka' size='3'>-
							<input type='text' name='mvva' value='$mvva' size='5'>
							&nbsp;&nbsp;
							<input type='text' name='mhh' value='$mhh' size='3'>:
							<input type='text' name='mmm' value='$mmm' size='3'></td></tr>";

					if ($kuittaus == "E") {
						$sel = "CHECKED";
					}
					else {
						$sel = "";
					}

					echo"	<tr>
							<th>".t("Ei kuittausta").":</th><td colspan='2'><input type='checkbox' name='kuittaus' value='E' $sel>
							</td>
							</tr>";
				}
				if ($tyyppi == "Lead") {

					echo "	<tr>
							<th>".t("Leadia valvoo").":</th>
							<td colspan='2'><select name='myyntipaallikko'>";

					$query = "	SELECT distinct kuka.tunnus, kuka.nimi, kuka.kuka
								FROM kuka, oikeu
								WHERE kuka.yhtio	= '$kukarow[yhtio]'
								and oikeu.yhtio		= kuka.yhtio
								and oikeu.kuka		= kuka.kuka
								and oikeu.nimi		= 'crm/kalenteri.php'
								and kuka.asema   like '%MP%'
								ORDER BY kuka.nimi";
					$result = pupe_query($query);

					while ($row = mysql_fetch_array($result)) {
						if ($row["myyntipaallikko"] == $myyntipaallikko) {
							$sel = "SELECTED";
						}
						else {
							$sel = "";
						}

						echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
					}
					echo "</select></td></tr>";


					echo "	<tr>
							<th>".t("Leadia hoitaa").":</th>
							<td colspan='2'><select name='kuka'>
							<option value='$kukarow[kuka]'>$kukarow[nimi]</option>";

					$query = "	SELECT distinct kuka.tunnus, kuka.nimi, kuka.kuka
								FROM kuka, oikeu
								WHERE kuka.yhtio	= '$kukarow[yhtio]'
								and oikeu.yhtio		= kuka.yhtio
								and oikeu.kuka		= kuka.kuka
								and oikeu.nimi		= 'crm/kalenteri.php'
								and kuka.kuka 		<> '$kukarow[kuka]'
								ORDER BY kuka.nimi";
					$result = pupe_query($query);

					while ($row = mysql_fetch_array($result)) {
						if ($row["kuka"] == $kuka) {
							$sel = "SELECTED";
						}
						else {
							$sel = "";
						}

						echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
					}
					echo "</select></td></tr>";

					if (!isset($lkka)) $lkka = date("m",mktime(0, 0, 0, date("m"), date("d")+7, date("Y")));
					if (!isset($lvva)) $lvva = date("Y",mktime(0, 0, 0, date("m"), date("d")+7, date("Y")));
					if (!isset($lppa)) $lppa = date("d",mktime(0, 0, 0, date("m"), date("d")+7, date("Y")));
					if (!isset($lhh))  $lhh = "10";
					if (!isset($lmm))  $lmm = "00";

					echo "<tr><th>".t("Muistutuspäivämäärä (pp-kk-vvvv tt:mm)")."</th>
							<td colspan='2'><input type='text' name='mppa' value='$lppa' size='3'>-
							<input type='text' name='mkka' value='$lkka' size='3'>-
							<input type='text' name='mvva' value='$lvva' size='5'>
							&nbsp;&nbsp;
							<input type='text' name='mhh' value='$lhh' size='3'>:
							<input type='text' name='mmm' value='$lmm' size='3'></td></tr>";
				}

				echo "<tr><th>".t("Tapa").":</th>";

				$vresult = t_avainsana("KALETAPA");

				echo "<td colspan='2'><select name='tapa'>";

				while ($vrow = mysql_fetch_array($vresult)) {
					$sel="";

					if ($tapa == $vrow["selitetark"]) {
						$sel = "selected";
					}
					echo "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark]</option>";
				}

				echo "</select></td></tr>";

				echo "	<tr>
						<td colspan='3' align='right' class='back'>
						<input type='submit' value='".t("Tallenna")."'>
						</form>
						</td></tr>";

				echo "	<td colspan='3' align='right' class='back'>
						<form method='POST'>
						<input type='hidden' name='tee' 		value='KORJAAMEMO'>
						<input type='hidden' name='yhtunnus' 	value='$yhtunnus'>
						<input type='hidden' name='ytunnus' 	value='$ytunnus'>
						<input type='hidden' name='lopetus' 	value='$lopetus'>
						<input type='hidden' name='asiakasid' 	value='$asiakasid'>
						<input type='submit' name='submit' value='".t("Korjaa viimeisintä")."'>
						</form>
						</td></tr>";

				echo "</table>";
				echo "<br>";
			}

			///* Haetaan memosta sisalto asiakkaan kohdalta *///
			echo "<table width='620'>";

			if ($naytapoistetut == '') {
				$lisadel = " and left(kalenteri.tyyppi,7) != 'DELETED'";
			}

			$query = "	SELECT kalenteri.tyyppi, tapa, kalenteri.asiakas ytunnus, yhteyshenkilo.nimi yhteyshenkilo,
						if(kuka.nimi!='',kuka.nimi, kalenteri.kuka) laatija, kentta01 viesti, left(pvmalku,10) paivamaara,
						kentta02, kentta03, kentta04, kentta05, kentta06, kentta07, kentta08,
						lasku.tunnus laskutunnus, lasku.tila laskutila, lasku.alatila laskualatila, kuka2.nimi laskumyyja, lasku.muutospvm laskumpvm,
						kalenteri.tunnus, kalenteri.perheid, if(kalenteri.perheid!=0, kalenteri.perheid, kalenteri.tunnus) sorttauskentta
						FROM kalenteri
						LEFT JOIN yhteyshenkilo ON kalenteri.yhtio=yhteyshenkilo.yhtio and kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.tyyppi = 'A'
						LEFT JOIN kuka ON kalenteri.yhtio=kuka.yhtio and kalenteri.kuka=kuka.kuka
						LEFT JOIN lasku ON kalenteri.yhtio=lasku.yhtio and kalenteri.otunnus=lasku.tunnus
						LEFT JOIN kuka kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE kalenteri.liitostunnus = '$asiakasid'
						$lisadel
						and kalenteri.yhtio = '$kukarow[yhtio]' ";

			if ($yhtunnus > 0) {
				$query .= " and henkilo='$yhtunnus'";
			}

			$query .= "	ORDER by sorttauskentta desc, kalenteri.tunnus";

			if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") === FALSE) {
				$query .= "	LIMIT 5 ";
			}
			$res = pupe_query($query);

			while ($memorow = mysql_fetch_array($res)) {
				if ($memorow["tapa"] == "asiakasanalyysi") {
					echo "<tr>
						<th>$memorow[tyyppi]</th><th>$memorow[laatija]</th><th>$memorow[laatija]@$memorow[paivamaara]
						</th><th>".t("Tapa").": $memorow[tapa]</th><th>".t("Yhteyshenkilö").": $memorow[yhteyshenkilo]</th>
						</tr>
						<tr>
						<td colspan='4'><pre>".t("Miten tuotteet esillä").":<br>$memorow[viesti]<br><br>".t("Myymälän yleisilme").":<br>$memorow[kentta02]<br><br>".t("Tuotteiden jakauma merkeittäin").":<br>".t("Puvut").":<br>$memorow[kentta03]<br>".t("Kypärät").":<br>$memorow[kentta04]<br>".t("Saappaat").":<br>$memorow[kentta05]<br>".t("Hanskat").":<br>$memorow[kentta06]<br>".t("Muut huomiot").":<br>$memorow[kentta07]<br><br>".t("Keskustelut/sovitut extrat").":<br>$memorow[kentta08]<br></pre></td>
						</tr>";
				}
				else {
					if ($memorow["perheid"] == 0) {
						echo "<tr>";
						echo "	<th>$memorow[tyyppi]</th>
								<th>$memorow[laatija]</th>
								<th>".tv1dateconv($memorow["paivamaara"])."</th>
								<th>".t("Tapa").": $memorow[tapa]</th>
								<th>".t("Yhteyshenkilö").": $memorow[yhteyshenkilo]</th>";

						if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE and substr($memorow['tyyppi'],0,7) != 'DELETED') {
							echo "<th><a href='$PHP_SELF?tunnus=$memorow[tunnus]&ytunnus=$ytunnus&asiakasid=$asiakasid&yhtunnus=$yhtunnus&tee=POISTAMEMO&liitostyyppi=$memorow[tyyppi]&lopetus=$lopetus'>".t("Poista")."</a></th>";
						}
						else {
							echo "<th></th>";
						}
						echo "</tr>";
					}

					echo "<tr><td colspan='6'>".str_replace("\n", "<br>", trim($memorow["viesti"]))."";

					if ($memorow["laskutunnus"] > 0) {
						$laskutyyppi = $memorow["laskutila"];
						$alatila	 = $memorow["laskualatila"];

						//tehdään selväkielinen tila/alatila
						require "inc/laskutyyppi.inc";

						echo "<br><br>".t("$laskutyyppi")." ".t("$alatila").":  <a href='{$palvelin2}raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$memorow[laskutunnus]&lopetus=$asmemo_lopetus'>$memorow[laskutunnus]</a> / ".tv1dateconv($memorow["laskumpvm"])."  ($memorow[laskumyyja])";
					}

					if ($memorow["laskutunnus"] == 0 and $memorow["tyyppi"] == "Lead") {
						echo "<br><br><a href='{$palvelin2}tilauskasittely/tilaus_myynti.php?toim=TARJOUS&tee=&from=CRM&asiakasid=$asiakasid&lead=$memorow[tunnus]'>".t("Tee tarjous")."</a>";
					}

					if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE and $memorow["perheid"] == 0 and ($memorow["tyyppi"] == "Memo" or $memorow["tyyppi"] == "Lead")) {
						echo "<tr><td colspan='3' align='right'>".t("Lähetä käyttäjälle").":</td><td colspan='3'>";
						echo "<form method='POST'>";
						echo "<input type='hidden' name='tee' value='SAHKOPOSTI'>";
						echo "<input type='hidden' name='tunnus' value='$memorow[tunnus]'>";
						echo "<input type='hidden' name='yhtunnus' value='$yhtunnus'>";
						echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
						echo "<input type='hidden' name='lopetus' value='$lopetus'>";
						echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
						echo "<select name='email' onchange='submit()'><option value=''>".t("Valitse käyttäjä")."</option>";

						$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
						$result = pupe_query($query);
						$konsernit = "";

						while ($row = mysql_fetch_array($result)) {
							$konsernit .= " '".$row["yhtio"]."' ,";
						}
						$lisa2 = " yhtio in (".substr($konsernit, 0, -1).") ";

						$query  = "SELECT distinct kuka, nimi, eposti FROM kuka WHERE $lisa2 and extranet='' and eposti != '' ORDER BY nimi";
						$vares = pupe_query($query);

						while ($varow = mysql_fetch_array($vares)) {
							echo "<option value='$varow[eposti]###$varow[kuka]'>$varow[nimi]</option>";
						}

						echo "</select>";
						echo "</form>";
						echo "</td></tr>";
					}
				}

				echo "</td></tr>";

				$liitetiedostot = listaaliitetiedostot($memorow['tunnus'],$memorow['tyyppi']);

				if ($liitetiedostot != '') {
					echo "<tr><th colspan='2'>".t("Liitetiedosto")."</th><td colspan='4'>".$liitetiedostot."</td></tr>";
				}
			}

			echo "</table>";

			if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE ) {
				if ($naytapoistetut == "") {
					echo "<br>";
					echo "<a href='$PHP_SELF?naytapoistetut=OK&ytunnus=$ytunnus&asiakasid=$asiakasid&yhtunnus=$yhtunnus&lopetus=$lopetus'>".t("Näytä poistetut muistiinpanot")."</a>";
				}
				else {
					echo "<br>";
					echo "<a href='$PHP_SELF?naytapoistetut=&ytunnus=$ytunnus&asiakasid=$asiakasid&yhtunnus=$yhtunnus&lopetus=$lopetus'>".t("Näytä aktiiviset muistiinpanot"). "</a>";
				}
			}
		}
   	}

	if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
		require ("../inc/footer.inc");
	}

	function listaaliitetiedostot($kalenteritunnus,$tyyppi) {
		GLOBAL $palvelin2,$kukarow;
		$out = "";

		$query = "	SELECT tunnus, filename
					FROM liitetiedostot
					WHERE yhtio = '$kukarow[yhtio]'
					AND liitostunnus = '$kalenteritunnus'
					AND liitos = '$tyyppi'";
		$res = pupe_query($query);

		while ($row = mysql_fetch_array($res)) {
			$out .= "<a href='{$palvelin2}view.php?id=$row[tunnus]' target='Attachment'>".t('Näytä liite')."</a> ".$row['filename']."<br>\n";
		}

		return $out;

	}
?>