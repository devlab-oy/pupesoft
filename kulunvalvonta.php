<?php

	if ($_POST["tallennaaineisto"] == "yes") {
		$lataa_tiedosto=1;
	}

	require ("inc/parametrit.inc");

	if ($tallennaaineisto == 'yes') {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	if ($toim == "HYVAKSYNTA") {
		echo "<font class='head'>".t("Tuntien hyv�ksynt� ja tarkastelu")."</font><hr>";
	}
	elseif ($toim == "VIENTI") {
		echo "<font class='head'>".t("Tuntien vienti")."</font><hr>";
	}
	elseif ($toim == "MYYNTI") {
		echo "<font class='head'>".t("Tuntiseuranta")."</font><hr>";
	}
	elseif ($toim == "KULUNVALVONTA") {
		echo "<font class='head'>".t("Kulunvalvonta")."</font><hr>";
	}
	elseif ($toim == "YHTEENVETO") {
		echo "<font class='head'>".t("Tuntiyhteenveto")."</font><hr>";
	}

	/*
		$user 		k�ytt�j� jonka tunteja k�sitell��n
		$ktunnus 	mit� erittelyrivi� k�sitell��n (sis��nkirjaus)
		$viivakoodi pit�� olla aina postatessa mukana, se kertoo kenen k�ytt�j�n tietoja muokataan

		"kirjaus" 	osiossa annetaan oikea k�ytt�liittym� tuntien sytt�miseen
		"erittele"	N�ytt�� aina erittelyt kunhan ktunnus on annettu

		toim=MYYNTI	Bongaa k�ytt�j�n viivakoodin

		toim=HYVAKSYNTA p��stet��n k�ytt�j� valitsemaan k�ytt�j�n viivakoodi, ja t�t�kautta p��st��n katselemaan ja hyv�ksyt��n tunteja. Pomomoodissa pit�� olla toim=hyvaksynta kokoajan...

		toim=VIENTI p��stet��n k�ytt�j� tutkimaan hyv�ksyttyj� tunteja ja viem��n ne tilipalveluun s�hk�postin liitetiedostona

		Jos ty� on hyv�ksytty, sit� ei pysty en�� muuttamaan. game over.

		Hyv�ksytyt kirjautumiset n�ytet��n k�ytt�j�lle parin viikon ajalta

		$muokkaaja ja $muokattu p�ivitet��n aina kun muokataan jotain kannassa. N�� n�ytet��n vaan pomon�kym�ss�...

		Hyv�ksy� ei pysty, jos erittelyiss� jotain vikaa

		$hyvaksy_ylityot === TRUE niin l�hetet��n ylity�tkin tilitoimistoon, jos FALSE niin ei l�hetet� ylit�it�

		$tilitoimiston_email sis�lt�� s�hk�postiosoitteen, johon tuntikirjaukset vied��n emailin liitetiedostona
	*/
	if (!function_exists("tarkista_erittelyn_oikeellisuus")) {
		function tarkista_erittelyn_oikeellisuus($yhtio, $kuka="", $sisaanaika="") {

			//	Arvotaan Where
			$where = " WHERE yhtio = '$yhtio'";
			if ($kuka != "") {
				$where .= " and kuka='$kuka'";
			}
			if ($sisaanaika != "") {
				$where .= "and aika='$sisaanaika'";
			}

			$where .= "and suunta='I'";

			$query = "	SELECT round(((
						(
							SELECT unix_timestamp(aika)
							FROM kulunvalvonta kv
							WHERE kv.yhtio=kulunvalvonta.yhtio and kv.kuka=kulunvalvonta.kuka and kv.suunta='O' and kv.aika > kulunvalvonta.aika
							orDER BY aika ASC
							LIMIT 1
							) - unix_timestamp(aika))/60),0) tyoaika,
						(
							SELECT sum(minuuttimaara)
							FROM kulunvalvonta kv2
							WHERE kv2.yhtio=kulunvalvonta.yhtio and kv2.kuka=kulunvalvonta.kuka and kv2.suunta='' and kv2.aika = kulunvalvonta.aika
							orDER BY aika ASC
							LIMIT 1
						) erittelyt
						FROM kulunvalvonta
						$where
						HAVING tyoaika<>erittelyt or tyoaika IS NULL or erittelyt IS NULL and tyoaika>0";
			$kirjtark = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($kirjtark) > 0) {
				return false;
			}

			return true;
		}
	}

	if (!function_exists("tallennaerittely")) {
		function tallennaerittely($user, $ktunnus, $etunnus, $minuuttimaara, $laatu, $otunnus="",$ylityo=0) {
			global $kukarow, $toim;

			$return = TRUE;
			if ($laatu == "") {
				echo "<font class='error'>".t("VIRHE: Ty�nlaatu on aina annettava.")."</font><br>";
				$return = FALSE;
			}

			if ($minuuttimaara <= 0) {
				echo "<font class='error'>".t("VIRHE: Ty�aika on virheellinen.")."</font><br>";
				$return = FALSE;
			}

			//	Tarkistetaan, ett� meill� on oikea kirjaus johon t�m� liittyy
			$query  = " SELECT *
						FROM kulunvalvonta
						WHERE yhtio = '$user[yhtio]' and kuka='$user[kuka]' and suunta = 'I' and tunnus = '$ktunnus'";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("VIRHE: Alkuper�inen kirjaus on kadoksissa.")."</font>";
				$return = FALSE;
			}

			if ($return) {
				$kulu   = mysql_fetch_array($result);

				//jos ollaan hyvaksyjia, olleen my�s muokkaajia
				if ($toim == "HYVAKSYNTA") {
					$muokkaaja = $kukarow["kuka"];
				}
				else {
					$muokkaaja = $user["kuka"];
				}
				$querylisa = "";
				//	P�ivitet��n vanhaa tai lis�t��n uusi jos meill� ei ole tunnusta
				if ($etunnus > 0) {
					$query = " UPDATE kulunvalvonta SET muokkaaja = '$muokkaaja', muokattu = now(),";
					$query2 = " WHERE yhtio='$user[yhtio]' and tunnus = '$etunnus'";
				}
				else {
					//luodaan uutta, jos ollaan hyvaksyjia niin muokataan toisen omaa
					$querylisa = "laatija = '$kukarow[kuka]', luontiaika = now(),";
					$query = " INSERT INTO kulunvalvonta SET";
					$query2 = "";
				}

				$query  .= "	$querylisa
								yhtio 			= '$user[yhtio]',
								kuka 			= '$user[kuka]',
								aika			= '$kulu[aika]',
								suunta 			= '',
								tyyppi			= '$laatu',
								otunnus			= '$otunnus',
								minuuttimaara	= '$minuuttimaara',
								ylityo			= '$ylityo'
								$query2";
				$result = mysql_query($query) or pupe_error($query);

			}


			return $return;
		}
	}

	/*
		Ty�tuntien yhteenveto

	*/
	if ($toim == "YHTEENVETO") {
		js_popup();

		function weekday_name($day, $month, $year) {
			// calculate weekday name
			$days = array(t("Maanantai"), t("Tiistai"), t("Keskiviikko"), t("Torstai"), t("Perjantai"), t("Lauantai"),t("Sunnuntai"));
			$nro = date("w", mktime(0, 0, 0, $month, $day, $year));
			if ($nro==0) $nro=6;
			else $nro--;

			return $days[$nro];
		}

		//kuukausien selaaminen kuntoon...
		if (isset($_GET["edkuukausi"])) {
			$kuukausi = $_GET["edkuukausi"];
			$vuosi = $_GET["vuosi"];
		}
		elseif (isset($_GET["seukuukausi"])) {
			$kuukausi = $_GET["seukuukausi"];
			$vuosi = $_GET["vuosi"];
		}
		elseif (!isset($_GET["edkuukausi"]) AND !isset($_GET["seukuukausi"])) {
			$kuukausi = date('m');
			$vuosi = date('Y');
			$edkuukausi = $kuukausi-1;
			$seukuukausi = $kuukausi+1;
		}

		$edkuukausi = sprintf("%02d",$kuukausi-1);
		$seukuukausi = sprintf("%02d",$kuukausi+1);

		//jos menn��n alle tammikuun, v�hennet��n vuodesta yksi.
		if ($kuukausi < 1) {
			$kuukausi = 12;
			$edkuukausi = 11;
			$seukuukausi = 13;
			$vuosi = $vuosi-1;
		}
		//jos yli joulukuun lis�t��n vuosiin yksi...
		if ($kuukausi > 12) {
			$kuukausi = sprintf("%02d",1);
			$seukuukausi = 2;
			$edkuukausi = 0;
			$vuosi = $vuosi+1;
		}
		//tulostetaan n�tit nappulat
		echo "<a href='$PHP_SELF?toim=YHTEENVETO&edkuukausi=$edkuukausi&vuosi=$vuosi'>&lt&lt</a> $kuukausi/$vuosi <a href='$PHP_SELF?toim=YHTEENVETO&seukuukausi=$seukuukausi&vuosi=$vuosi'>&gt&gt</a>";

		//kannasta hakua varten p�iv�m��ri� ja aikoja
		$kuukaudessa_paivia = cal_days_in_month(CAL_GREGORIAN,$kuukausi,$vuosi);
		$alkuaika =  "$vuosi-$kuukausi-01 00:00:00";
		$loppuaika = "$vuosi-$kuukausi-$kuukaudessa_paivia 23:59:59";

		$edviikko = date('W',mktime(0,0,0,$kuukausi,1,$vuosi));
		echo "<table><tr><th>&nbsp;</th>";
		//listataan otsikoihin kuukauden p�iv�t
		for ($i=1; $i < $kuukaudessa_paivia+1; $i++) {
			if (date('W',mktime(0,0,0,$kuukausi,$i,$vuosi)) != $edviikko) {
				echo "<th width='200'><font class='info'>".t("Yhteens�")."</font></th>";
			}
			$edviikko = date('W',mktime(0,0,0,$kuukausi,$i,$vuosi));
			echo "<th>" .t(substr(weekday_name($i,$kuukausi,$vuosi),0,2)) . "<br>".  $i . "</th>";

		}
		//etsit��n henkil�t, joiden tunteja listataan taulukkoon
		$query = "	SELECT DISTINCT kuka,
	 				(
	 					SELECT nimi
	 					FROM kuka
	 					WHERE kuka.yhtio=kulunvalvonta.yhtio and kuka.kuka=kulunvalvonta.kuka
	 					LIMIT 1)
	 				AS nimi
					FROM kulunvalvonta
	 				WHERE suunta NOT IN ('I','O') and hyvaksytty!='0000-00-00 00:00:00' and aika > '$alkuaika' and aika < '$loppuaika'
	 				ORDER BY nimi";

	 	$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 0) {
			echo "<br><font class='info'>Ei Erittelymerkint�j�</font>";
		}
		//listataan henkil�t yks kerrallaan, haetaan aina henkil� kerrallaan kuukausittaiset erittelyt
		while ($hyvaksytyt = mysql_fetch_array($result)) {
			if ($hyvaksytyt["kuka"] != $edkuka) {
				echo "</tr><td NOWRAP>$hyvaksytyt[nimi]</td>";
			}
			$edviikko = date('W',mktime(0,0,0,$kuukausi,1,$vuosi));
			$viikkosumma = 0;
			//k�yd��n kuukausi l�pi henkil�itt�in ja etsit��n hyv�ksyttyj� erittelyit�
			for ($i=1; $i < $kuukaudessa_paivia+1; $i++) {
				if (date('W',mktime(0,0,0,$kuukausi,$i,$vuosi)) != $edviikko) {
					echo "<td><font class='info'>". sprintf("%02d",floor($viikkosumma/60)). ":" . sprintf("%02d",$viikkosumma%60) . "</font></td>";
					$viikkosumma = 0;
				}
				$edviikko = date('W',mktime(0,0,0,$kuukausi,$i,$vuosi));

				$i2 = sprintf("%02d",$i);
				//haetaan kannasta henkil�n tunteja
				$alkuaika =  "$vuosi-$kuukausi-$i2 00:00:00";
				$loppuaika = "$vuosi-$kuukausi-$i2 23:59:59";
				$query2 = "	SELECT sum(minuuttimaara) as minuuttisumma
							FROM kulunvalvonta
							WHERE suunta NOT IN ('I','O') and hyvaksytty!='0000-00-00 00:00:00' and aika > '$alkuaika' and aika < '$loppuaika' AND kuka='$hyvaksytyt[kuka]' AND tyyppi != 'RUO'";

				$minuuttisummares = mysql_query($query2) or pupe_error($query2);

				$query3 = "	SELECT aika, minuuttimaara, lasku.nimi nimi, tyyppi, kuka, seuranta, kulunvalvonta.otunnus, if (kulunvalvonta.otunnus >0, concat_ws(' ',kulunvalvonta.otunnus,laskun_lisatiedot.seuranta,lasku.nimi),' ') tiedot
							FROM kulunvalvonta
							LEFT JOIN lasku ON lasku.tunnus=kulunvalvonta.otunnus
							LEFT JOIN laskun_lisatiedot ON laskun_lisatiedot.otunnus=kulunvalvonta.otunnus
							WHERE suunta NOT IN ('I','O') and hyvaksytty!='0000-00-00 00:00:00' and aika > '$alkuaika' and aika < '$loppuaika' AND kuka='$hyvaksytyt[kuka]' AND tyyppi != 'RUO'
							ORDER BY aika ";

				$lisatiedotres = mysql_query($query3) or pupe_error($query3);

				//jos erittelyit� l�ytyi, kerrotaan kokonaiserittelyaika ja listataan erittelyt popuppiin joka aukeaa kun hiiren kursori sattuu aikasumman p��lle
				if (mysql_num_rows($minuuttisummares) > 0) {
					$minuuttisumma = mysql_result($minuuttisummares,0);
					if ($minuuttisumma > 0) {
						$viikkosumma = $viikkosumma + $minuuttisumma;
						$tunnit = sprintf("%02d",floor($minuuttisumma/60));
						$minuutit = sprintf("%02d",$minuuttisumma%60);
						//lisatietoja-divin ID-numero, ei saa olla mill��n sama.
						$id=md5(uniqid());
						echo "
							<td style='color: red;'>
							<a href='#' class='tooltip' id='$id'>$tunnit:$minuutit</a>
							<div id='div_$id' class='popup'>";

						echo "<table><tr><td colspan='4' align='center'>$hyvaksytyt[nimi] $i2.$kuukausi.$vuosi</td></tr><tr><th>".t("Projekti")."</th><th>".t("Ty�n laatu")."</th><th>".t("Tuntim��r�")."</th></tr>";
						//l�tk�st��n popupin sis�lt�
						while ($lisatiedot = mysql_fetch_array($lisatiedotres)) {
							echo "<tr><td NOWRAP>$lisatiedot[tiedot]</td><td>$lisatiedot[tyyppi]</td><td align='center'>". sprintf("%02d",floor($lisatiedot["minuuttimaara"]/60)).":".sprintf("%02d",$lisatiedot["minuuttimaara"]%60)."</td></tr>";
						}
						echo "</table>
							</div>
							</td>";
					}
					else {
						echo "<td>&nbsp;</td>";
					}
				}
			}
			$edkuka = $hyvaksytyt["kuka"];
		}
		echo "</tr></table>";
	}

	/*
		Tuntien vienti EmCe-palkanlaskentaan

		Etsit��n hyv�ksytyt tunnit tietokannasta, tulostetaan ne ruudulle ja annetaan tallentaa tiedosto
		L�HETET��N ERIKSEEN NorMITUNNIT JA YLITY�TUNNIT (8h>, 40h>), lomap�iv�t jne.
		Kirjoitetaan tiedostoon samalla kun listataan n�it�.. hyv�ksyttyj� ei voi en�� muuttaa, tough luck!
		ty�numero = kustannuspaikka
		palkkalajeja:
		perustunnit (viikolla max 8h) = 5100
		vrk-ylity� perusosa = 5401
		50% vrk-ylity� (8h>10h) = 5402
		100% vrk-ylityo (10h>) = 5403
		vko-ylity� perusosa = 5500
		50% vko ylity� = 5501
		lomap�iv�t = ??
	*/
	if ($toim == "VIENTI") {

		/*

			Listojen tutkailu ja l�hetett�v�n tiedoston luominen (k�ytt�j� voi itse valita palkkakauden)
		*/

		//OLETUKSIA
		if (!isset($kuukausi) and !isset($vuosi)) {
			$kuukausi = date('m');
			$vuosi = date('Y');
		}
		//listataan selecteihin valinnat ja asetetaan oletukset niihin...
		$kuukaudetarr = array(	"Tammikuu" 		=> 1,
								"Helmikuu"		=> 2,
								"Maaliskuu"		=> 3,
								"Huhtikuu" 		=> 4,
								"Toukokuu"		=> 5,
								"Kes�kuu" 		=> 6,
								"Hein�kuu"		=> 7,
								"Elokuu" 		=> 8,
								"Syyskuu" 		=> 9,
								"Lokakuu"		=> 10,
								"Marraskuu"		=> 11,
								"Joulukuu"		=> 12);

		echo "<form action = '?toim=$toim' method='post' name='valitsepalkkakausi'>
				<table>
				<tr>
					<th>".t("Valitse palkkakausi")."</th>
					<td>
						<select name='vuosi' onchange='this.form.submit();'>";

		for ($i=date('Y'); $i > date('Y')-10; $i--) {
			if ($i == $vuosi) {
				echo "	<option value='$i' SELECTED>$i</option>";
			}
			else {
				echo "	<option value='$i'>$i</option>";
			}
		}
		echo "			</select>
						<select name='kuukausi' onchange='this.form.submit();'>";
		foreach ($kuukaudetarr as $kuukaudet => $arvo) {
			if ($kuukausi == $arvo) {
				echo "	<option value='$arvo' SELECTED>$kuukaudet</option>";
			}
			else {
				echo "	<option value='$arvo'>$kuukaudet</option>";
			}
		}

		echo "		</select>
				</tr>
				</table>
				</form><br><br>";

		//m��ritet��n kannasta haettava tilikauden alku ja loppu selectist� saatujen tietojen perusteella
		$tilikausi_alku =  $vuosi. "-". date('m',(mktime(00,00, 00, $kuukausi-1, date('d'), date('Y'))))."-26 00:00:00";
		$tilikausi_loppu = $vuosi. "-". date('m',(mktime(00,00, 00, $kuukausi, date('d'), date('Y'))))."-26 00:00:00";



		echo "<font class='message'>" . t("Tilikausi: "). date('d.m.Y',strtotime($tilikausi_alku))." - ". date('d.m.Y',strtotime($tilikausi_loppu))."</font><br>";

		//luetaan kannasta hyv�ksytyt eritellyt tunnit valitulta tilikaudelta ja kirjoitetaan ne tiedostoon..
		$query = "	SELECT aika, minuuttimaara, otunnus, tyyppi, kuka,ylityo,
					(
						SELECT nimi
						FROM kuka
						WHERE kuka.yhtio=kulunvalvonta.yhtio and kuka.kuka=kulunvalvonta.kuka
						LIMIT 1)
					AS nimi,
					(
						SELECT vieja
						FROM kulunvalvonta kvviety
						WHERE kvviety.yhtio='$kukarow[yhtio]' and kvviety.viety != '0000-00-00 00:00:00' and (unix_timestamp(kvviety.aika) > unix_timestamp('$tilikausi_alku') and unix_timestamp(kvviety.aika) < unix_timestamp('$tilikausi_loppu')) LIMIT 1 )
					AS vieja,
					(
						SELECT unix_timestamp(viety)
						FROM kulunvalvonta kvvieja
						WHERE kvvieja.yhtio='$kukarow[yhtio]' and kvvieja.viety=kulunvalvonta.viety
						LIMIT 1)
					AS viety
					FROM kulunvalvonta
					WHERE suunta NOT IN ('I','O') and hyvaksytty!='0000-00-00 00:00:00' and (unix_timestamp(aika) > unix_timestamp('$tilikausi_alku') and unix_timestamp(aika) < unix_timestamp('$tilikausi_loppu')) and minuuttimaara >14
					orDER BY nimi, aika, tunnus ";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			/*
				HYV�KSYT��NK� YLIT�IDEN L�HETYS TILITOIMISTOON?
				jos true, niin l�hetet��n ylity�tkin tilitoimistoon maksettaviksi
				jos virhe asetetaan trueksi, ei tietoja voida vied� palkanlaskentaan (esim jonkun henkil�tunnus puuttuu)
			*/
			$hyvaksy_ylityot = TRUE;
			$virhe = FALSE;
			$tiedostonimi = "/tmp/" . $kukarow["yhtio"] . "tuntierittely_" . date('d-m-Y_') . md5(uniqid()).".dat";
			$filekahva = fopen($tiedostonimi,"w+") or die('Tiedostovirhe');

			echo "<font class='message'>" . t("Tarkista tuntierittelyt")."</font><br>";
			echo "<table>
						<tr>
							<th>".t("Nimi")."</th><th>".t("Ty�numero")."</th><th>".t("Aika")."</th><th>".t("Tehty ty�")."</th>
						</tr>";

			$edkuka = mt_rand();
			$ylityosumma = "";
			while ($hyvaksytyt = mysql_fetch_array($result)) {

	        	//haetaan k�ytt�j�n henkil�tunnus kannasta jos ei ole jo tiedossa
				if ($edkuka != $hyvaksytyt["kuka"]) {
					$query = "	SELECT ytunnus
								FROM toimi
								WHERE yhtio='$kukarow[yhtio]' and nimi='$hyvaksytyt[kuka]'
								LIMIT 1";
					$htunnusresult = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($htunnusresult) == 0) {
						echo "<font class='error'><br>" . t("VIRHE: K�ytt�j�n")." ".$hyvaksytyt["nimi"]." ".t("nime� ja henkil�tunnusta ei l�ydy toimittajista")."<br></font>";
						$htunnusvirhe = $hyvaksytyt["kuka"]; //ei herjata samasta k�ytt�j�st� kuin kerran
						$virhe = TRUE;
					}
					else {
						$henkilotunnus = mysql_result($htunnusresult,0);
					}
				}

				//ollaan kasaamassa k�ytt�j�n saman p�iv�n tunnit rypp�isiin, otetaan siis vain vuosi,kuukausi ja aika paivamaarasta vertailuun mukaan...
				$paivamaara = substr($hyvaksytyt["aika"],0,10);

				//t�m� yhteenveto tulostetaan jokaisen p�iv�erittelyn lopuksi
				if ((isset($edkuka) and isset($samapaivamaara)) and ($samapaivamaara != $paivamaara or $edkuka != $hyvaksytyt["kuka"])) {
					echo "	</tr>
							<tr>
								<td colspan='3' align='right'>" . t("Yhteens�")."</td><td align='center'>$tunteja_yhteensa</td><td align='center'>$ylityosumma</td>
							</tr>
							<tr>
								<td class='back' colspan='5'>&nbsp;</td>
							</tr>";
					//nollataan summamuuttujat, jotka ovat p�iv� ja k�ytt�j�kohtaisia
					$tunteja_yhteensa = "";
					$ylityosumma = "";
				}

				//py�ristyksi� palkanlaskentaan (puolen tunnin tarkkuuksille)
				$tuntimaara = round(($hyvaksytyt["minuuttimaara"]/60),1);
				$tunnit = floor($tuntimaara);
				$puolikkaat = $tuntimaara - $tunnit;
				if ($puolikkaat >= 0 and $puolikkaat <0.25) {
					$tuntimaara = $tunnit;
				}
				elseif ($puolikkaat >= 0.25 and $puolikkaat < 0.5) {
					$tuntimaara = $tunnit + 0.5;
				}
				elseif ($puolikkaat >= 0.5 and $puolikkaat < 1.0) {
					$tuntimaara = $tunnit + 1;
				}

				//henkil�tunnuksen kirjottelu tiedostoon
				fwrite($filekahva, $henkilotunnus);


				//lasketaan p�ivitt�iset ylity�t
				if ($hyvaksytyt["ylityo"] == 1)  {
					$ylityo = $tuntimaara; //ty� on ylity�t�, l�tk�st��n se ylity�muuttujaan ja nollataan tuntimaara
					$tuntimaara = ""; //tuntimaara on nyt ylit�it�, nollataan t�m� sekaannusten v�ltt�miseksi

					//ollaanko tehty yli 8h vai yli 10h?
					if ($ylityosumma <= 2 AND $hyvaksy_ylityot === TRUE) {

						//muutetaan piste pilkuksi ja pistet��n pari desimaalia lis��
						$ylityo_desimaaleineen = str_replace('.',',',(sprintf('%0.2f',$ylityo)));

						//jos 50% VRK-ylity�t� kirjoitetaan tiedostoon t�m� ylity�palkkalaji
						fwrite($filekahva, ";5402;");
						fwrite($filekahva, $ylityo_desimaaleineen . ";");
					}
					elseif ($ylityosumma > 2 AND $hyvaksy_ylityot === TRUE) {

						//muutetaan piste pilkuksi ja pistet��n pari desimaalia lis��
						$ylityo_desimaaleineen = str_replace('.',',',(sprintf('%0.2f',$ylityo)));

						//jos 100% VRK-ylity�t� kirjoitetaan tiedostoon t�m� ylity�palkkalaji
						fwrite($filekahva, ";5403;");
						fwrite($filekahva, $ylityo_desimaaleineen . ";");
					}

					//lis�t��n ylit�iden loppusummausta
					$ylityosumma = $ylityosumma + $ylityo;
				}
				else {
					//ei oo ylity�t�, eli on normity�t�..kirjotetaan t�� peruspalkkalaji (5100)
					fwrite($filekahva, ";5100;");

					//muutetaan piste pilkuksi ja lis�t��n pari desimaalia
					$tuntimaara_desimaaleineen = str_replace('.',',',(sprintf('%0.2f',$tuntimaara)));

					//kirjoitetaan tuntimaara tiedostoon
					fwrite($filekahva, $tuntimaara_desimaaleineen . ";");

					//lis�t��n perust�iden summaa
					$tunteja_yhteensa = $tunteja_yhteensa + $tuntimaara;
				}

				//vikaks kirjotetaan kustannuspaikkatunnus, eli projektinumero tai ty�n laatu. Tutkitaan kumpi pistet��n kustannuspaikaks..
	 			if ($hyvaksytyt["otunnus"] != "0" and $hyvaksytyt["tyyppi"] != "") {
					$kustannuspaikka = $hyvaksytyt["otunnus"] . "\n";
				}
				//vain ty�n laatu m��ritelty, pistet��n se sit...
				elseif ($hyvaksytyt["otunnus"] == "0" and $hyvaksytyt["tyyppi"] != "") {
					$kustannuspaikka = $hyvaksytyt["tyyppi"] . "\n";
				}

				//kirjotetaan tiedostoon normituntien kustannuspaikka ja samal vaihdetaan rivi�
				if ($ylityo == "") {
					fwrite($filekahva, $kustannuspaikka);
				}
				//jos olikin ylit�it�, kirjoitetaan t�m�, ja lis�t��n viel� ylit�iden perusosa
				elseif ($hyvaksy_ylityot === TRUE and $ylityo != "") {
					fwrite($filekahva, $kustannuspaikka);
					fwrite($filekahva, $henkilotunnus . ";5401;" . $ylityo_desimaaleineen . ";" . $kustannuspaikka); //ylity�n perusosa
				}

				//tulostetaan tiedot riville
				$aika_ilman_sekunteja = date('d.m.Y H:i',strtotime($hyvaksytyt["aika"]));
				echo "<tr><td align='center'>$hyvaksytyt[nimi]</td>
							<td align='center'>$kustannuspaikka</td>
							<td align='center'>$aika_ilman_sekunteja</td>
							<td align='center'>$tuntimaara</td>
							<td align='center'>$ylityo</td>";

				$ylityo = ""; //vet�st��n tyhj�ks alkua varten...
				$samapaivamaara = $paivamaara; //l�tk�st��n p�iv�m��r� ja nimi apumuuttujiin, katsotaan v�h�n ylemp�n� onko sama p�iv�m��r� ja k�ytt�j� vai ei, jos ei niin vaihetaan taulukon rivi� johon eritell��n..
				$edkuka = $hyvaksytyt["kuka"];

				//katsotaan lopun virheilmoitusta varten onko n�� viety jo, otetaan talteen muuttujiin arvot jos on
				if ($hyvaksytyt["vieja"] != 0) {
					$vietyjo = TRUE;
					$vieja = $hyvaksytyt["vieja"];
					$vietypvm = date('d.m.Y H:i',$hyvaksytyt["viety"]);
				}

			}

			//vikalle riville viel� t�� yhteenveto
			echo "	</tr>
					<tr>
						<td colspan='3' align='right'>".t("Yhteens�")."</td><td align='center'>$tunteja_yhteensa</td><td align='center'>$ylityosumma</td>
					</tr>
					<tr><td class='back' colspan='5'>&nbsp;</td></tr>";

			$tunteja_yhteensa = 0;

			echo "</table>";
			fclose($filekahva);

			//jos on jo viety, niin kerrotaan se k�ytt�j�lle (mut annetaan mahdollisuus vied� uudelleen)
			if ($vietyjo === TRUE) {
				echo "<font class='error'>" . t("Tunnit on jo viety palkanlaskentaan!")."<br>".t("Viety: ") . $vietypvm . " ($vieja)" . "<br></font>";
			}
			if (!$hyvaksy_ylityot) {
				echo "<font class='message'>" . t("Ylit�it� ei vied� palkanlaskentaan")."<br></font>";
			}

			//jos on tapahtunut virhe (esim henkilotunnus puuttuu) ei anneta k�ytt�j�n vied� tietoja kantaan vaan lopetetaan t�h�n.
			if ($virhe === FALSE) {

				//tiedosto on nyt kasassa ja tulostettu ruudulle, nyt annetaan k�ytt�j�n klikata vientinappulaa, jonka j�lkeen l�hetet��n tiedosto liitetiedostona tilitoimiston s�hk�postiin
				echo "	<form action = '?toim=$toim' method='post' name='vientitilitoimistoon'>";

				if ($vietyjo === TRUE) {
					echo "	<input type='submit' value='Vie uudelleen tilitoimistoon' onclick=\"return confirm('Tiedot on jo l�hetetty palkanlaskentaan, oletko varma ett� tahdot l�hett�� ne uudestaan? ');\"> ";
				}
				else {
					echo "	<input type='submit' value='".t("Tallenna aineisto")."'>";
				}
			}
			echo "		<input type='hidden' name='tiedostonimi' value='$tiedostonimi'>
						<input type='hidden' name='tilikausi_alku' value='$tilikausi_alku'>
						<input type='hidden' name='tilikausi_loppu' value='$tilikausi_loppu'>
						<input type='hidden' name='tallennaaineisto' value='yes'>
						<input type='hidden' name='kaunisnimi' value='".$kukarow["yhtio"]."-Tuntikirjaukset_".date("dmY")."'>
						<input type='hidden' name='tmpfilenimi' value='".basename($tiedostonimi)."'>
					</form>";
		}
		else {
			echo "<font class='info'>".t("Ei tunteja")."</font><br>";
		}
	}

	if ($toim == 'MYYNTI') {
		//JOS VIIVAKOODI ON JO ASETETTU, OLLAAN ASETETTU SE HYVAKSYNNAN KAUTTA, ELI OLLAAN HYV�KSYJ�...
		if (!isset($viivakoodi)) {
			$query = "	SELECT nimi, left(md5(concat(tunnus,kuka)), 16) avain
						FROM kuka
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[tunnus]'";
			$vres = mysql_query($query) or pupe_error($query);
			$vrow = mysql_fetch_array($vres);
			$viivakoodi = $vrow["avain"]; //Jokaisella myyntitykill� on omat tunnukset, joten tuon viivakoodin l�yd�mme k�tev�sti suoraan kukarowsta
		}
		if (!isset($tee)) {
			$tee = "kirjaus";
		}
	}

	/*
		Tunnistetaan k�ytt�j� jonka juttuja muokataan, kuljetetaan t�t� viivakoodia kokoajan mukana
		$user muuttuja pit�� aina sis�ll��n kaikki k�ytt�j�n t
	*/
	$query  = "	SELECT *
				FROM kuka
				WHERE left(md5(concat(tunnus,kuka)), 16)='$viivakoodi'";
	$result = mysql_query($query) or pupe_error($query);
	$user = mysql_fetch_array($result);

	if (mysql_num_rows($result) == 0 and $toim == "HYVAKSYNTA") {
		$tee = "OHITAKAIKKI";
	}
	elseif ((mysql_num_rows($result) != 1 and $toim != "VIENTI" and $toim != "KULUNVALVONTA" and $toim != "YHTEENVETO") or (mysql_num_rows($result) != 1 and $toim == "KULUNVALVONTA" and isset($viivakoodi))) {
		echo "<br><font class='message'>".t("VIRHE: K�ytt�j�� ei l�ytynyt!")."</font>";
		echo "<meta http-equiv='refresh' content='2;URL=kulunvalvonta.php?toim=$toim'><br>";
		exit;
		$tee = "";
	}

	//	Erittelyiden ja tuntilis�ysten poisto
	if ($tee == 'poista_erittely') {
		if ($tunnus > 0) {
			$query = "	DELETE FROM kulunvalvonta
						WHERE yhtio = '$user[yhtio]' and kuka = '$user[kuka]' and tunnus='$tunnus'";
			$result = mysql_query($query) or pupe_error($query);
			echo "<font class='message'><br>" . t("Erittely poistettu")."</font><br";

			$tee = "erittele";
		}
	}

	if ($tee == 'poista_kirjaus') {
		if ($tunnus > 0) {
			$query = "	SELECT aika aikasisaan, (
							SELECT aika
							FROM kulunvalvonta kv
							WHERE kv.yhtio=kulunvalvonta.yhtio and kv.kuka=kulunvalvonta.kuka and kv.aika > kulunvalvonta.aika and suunta='O'
							LIMIT 1
						) AS aikaulos
						FROM kulunvalvonta
						WHERE yhtio = '$user[yhtio]' and kuka = '$user[kuka]' and tunnus = '$tunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($result);
			//poistetaan sis��nkirjautuminen
			$query = "	DELETE FROM kulunvalvonta
						WHERE yhtio = '$user[yhtio]' and kuka = '$user[kuka]' and aika='$row[aikasisaan]'";
			$result = mysql_query($query) or pupe_error($query);
			//poistetaan uloskirjautuminen
			$query = "	DELETE FROM kulunvalvonta
						WHERE yhtio = '$user[yhtio]' and kuka = '$user[kuka]' and aika='$row[aikaulos]'";
			$result = mysql_query($query) or pupe_error($query);
			echo "<font class='message'><br>" . t("Kirjaus poistettu")."</font><br";

			$tee = "kirjaus";
		}
	}

	//	Koitetaan kirjautua sis��n SQL
	if ($tee == "kirjaa") {

		//JOS $virhe on true, niin skipataan kaikki ja heitet��n virhe naamalle.
		$virhe = FALSE;

		//	Suoritetaan kirjaukset kantaan
		if ($toim == "MYYNTI" or $toim == "HYVAKSYNTA") {

			if ($sisaanaikaStr == "") {
				//	Tarkastetaan annetut luvut
				foreach(array("myyntisisaan_tunti", "myyntisisaan_minuutti", "myyntisisaan_kuukausi", "myyntisisaan_paiva", "myyntisisaan_vuosi") as $tark) {
					if ($$tark < 0 or !is_numeric($$tark)) {
						echo "<font class='error'>" . t("VIRHE: Aika ei kelpaa!")." ".$$tark."</font><br>";
						$virhe = TRUE;
					}
					if ($tark == "myyntisisaan_tunti" and $$tark>=24 ) {
						echo "<font class='error'>" . t("VIRHE: Tunnit pit�� olla alle 24..")."</font><br>";
						$virhe = TRUE;
					}
					elseif ($tark == "myyntisisaan_minuutti" and $$tark>=60 ) {
						echo "<font class='error'>" . t("VIRHE: Minuutit pit�� olla alle 60..")."</font><br>";
						$virhe = TRUE;
					}
					elseif ($tark == "myyntisisaan_paiva" and $$tark>31 ) {
						echo "<font class='error'>" . t("VIRHE: Kuukaudessa ei voi olla yli 31 p�iv��..")."</font><br>";
						$virhe = TRUE;
					}
					elseif ($tark == "myyntisisaan_kuukausi" and $$tark>12 ) {
						echo "<font class='error'>" . t("VIRHE: Vuodessa on vain 12 kuukautta..")."</font><br>";
						$virhe = TRUE;
					}
					elseif ($tark == "myyntisisaan_vuosi" and strlen($$tark) <> 4) {
						echo "<font class='error'>" . t("VIRHE: Vuodessa on normaalisti 4 numeroa..")."</font><br>";
						$virhe = TRUE;
					}
				}

				if (!$virhe) {
					$sisaanaikaStr = mktime($myyntisisaan_tunti, $myyntisisaan_minuutti, 0, $myyntisisaan_kuukausi, $myyntisisaan_paiva, $myyntisisaan_vuosi);
				}
			}

			if ($ulosaikaStr == "") {

				//	Tarkastetaan annetut luvut
				foreach(array("myyntiulos_tunti", "myyntiulos_minuutti", "myyntiulos_kuukausi", "myyntiulos_paiva", "myyntiulos_vuosi") as $tark) {
					if ($$tark < 0 or !is_numeric($$tark)) {
						echo "<font class='error'>" . t("VIRHE: Aika ei kelpaa!")." ".$$tark."</font><br>";
						$virhe = TRUE;
					}
					if ($tark == "myyntiulos_tunti" and $$tark>=24 ) {
						echo "<font class='error'>" . t("VIRHE: Tunnit pit�� olla alle 24..")."</font><br>";
						$virhe = TRUE;
					}
					elseif ($tark == "myyntiulos_minuutti" and $$tark>=60 ) {
						echo "<font class='error'>" . t("VIRHE: Minuutit pit�� olla alle 60..")."</font><br>";
						$virhe = TRUE;
					}
					elseif ($tark == "myyntiulos_paiva" and $$tark>31 ) {
						echo "<font class='error'>" . t("VIRHE: Kuukaudessa ei voi olla yli 31 p�iv��..")."</font><br>";
						$virhe = TRUE;
					}
					elseif ($tark == "myyntiulos_kuukausi" and $$tark>12 ) {
						echo "<font class='error'>" . t("VIRHE: Vuodessa on vain 12 kuukautta..")."</font><br>";
						$virhe = TRUE;
					}
					elseif ($tark == "myyntiulos_vuosi" and strlen($$tark) <> 4) {
						echo "<font class='error'>" . t("VIRHE: Vuodessa on normaalisti 4 numeroa..")."</font><br>";
						$virhe = TRUE;
					}
				}

				if (!$virhe) {
					$ulosaikaStr = mktime($myyntiulos_tunti, $myyntiulos_minuutti, 0, $myyntiulos_kuukausi, $myyntiulos_paiva, $myyntiulos_vuosi);
				}
			}

			//	Testataan, ett� sis��nkirjaus on ennen uloskirjausta
			if ($sisaanaikaStr > $ulosaikaStr) {
				echo "<font class='error'>" . t("VIRHE: Kirjaudu sis��n ennen uloskirjausta..")."</font><br>";
				$virhe = TRUE;
			}

			//	Testataan, ett� uloskirjaus ei ole tulevaisuudessa
			if ($ulosaikaStr > time()) {
				echo "<font class='error'>" . t("VIRHE: Et voi kirjautua ulos ennakkoon..")."</font><br>";
				$virhe = TRUE;
			}

			//	Muokataan vanhaan
			if ($tunnus_sisaan > 0 or $tunnus_ulos > 0) {

				//	Tarkastamme p��llekk�isyydet!
				$query = "	SELECT unix_timestamp(aika) sisaan,
								(
									SELECT unix_timestamp(aika)
									FROM kulunvalvonta kv
									WHERE kv.yhtio=kulunvalvonta.yhtio and kv.kuka=kulunvalvonta.kuka and kv.suunta='O' and kv.aika > kulunvalvonta.aika and kv.tunnus != '$tunnus_ulos'
									orDER BY aika ASC
									LIMIT 1
								) ulos
							FROM kulunvalvonta
							WHERE yhtio='$user[yhtio]' and kuka='$user[kuka]' and suunta='I' and tunnus!='$tunnus_sisaan'
							HAVING 	(
										(sisaan >= '$sisaanaikaStr' and sisaan < '$ulosaikaStr') or
										(sisaan < '$sisaanaikaStr' and ulos > '$ulosaikaStr') or
										(ulos > '$sisaanaikaStr' and ulos <= '$ulosaikaStr')
									)";
				$kirjtarkres = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($kirjtarkres) == 0) {
					//jos ollaan hyv�ksyji� niin ollaan muokkaajia
					if ($toim == "HYVAKSYNTA") {
						$muokkaaja = $kukarow["kuka"];
					}
					else {
						$muokkaaja = $user["kuka"];
					}
					//	Korjataan sis��nkirjaus
					$query  = "	UPDATE kulunvalvonta SET
									aika	= '".date("Y-m-d H:i:s", $sisaanaikaStr)."',
									muokkaaja = '$muokkaaja', muokattu = now()
								WHERE yhtio = '$user[yhtio]' and tunnus='$tunnus_sisaan'";
					$result = mysql_query($query) or pupe_error($query);

					$query  = "	UPDATE kulunvalvonta SET
									aika	= '".date("Y-m-d H:i:s", $ulosaikaStr)."',
									muokkaaja = '$muokkaaja', muokattu = now()
								WHERE yhtio = '$user[yhtio]' and tunnus='$tunnus_ulos'";
					$result = mysql_query($query) or pupe_error($query);
				}
				else {
					echo "<font class='error'>" . t("VIRHE: Aika menee p��llekk�in toisen kirjauksen kanssa")."</font><br>";
					$virhe = TRUE;
				}
			}
			elseif (!$virhe) {
				$query = "	SELECT unix_timestamp(aika) sisaan,
								(
									SELECT unix_timestamp(aika)
									FROM kulunvalvonta kv
									WHERE kv.yhtio=kulunvalvonta.yhtio and kv.kuka=kulunvalvonta.kuka and kv.suunta='O' and kv.aika > kulunvalvonta.aika
									orDER BY aika ASC
									LIMIT 1
								) ulos
							FROM kulunvalvonta
							WHERE yhtio='$user[yhtio]' and kuka='$user[kuka]' and suunta='I'
							HAVING 	(
										(sisaan >= '$sisaanaikaStr' and sisaan < '$ulosaikaStr') or
										(sisaan < '$sisaanaikaStr' and ulos > '$ulosaikaStr') or
										(ulos > '$sisaanaikaStr' and ulos <= '$ulosaikaStr')
									)";
				$kirjtarkres = mysql_query($query) or pupe_error($query);
				//jos ollaan hyv�ksyj� ja lis�t��n merkint�, niin muokataan k�ytt�j�tietoja, eli ollaan muokkaajia ;)
				$querylisa = "";
				if ($toim == "HYVAKSYNTA") {
					$querylisa = ", muokkaaja = '$kukarow[kuka]', muokattu = '".date("Y-m-d H:i:s")."'";
				}
				if (mysql_num_rows($kirjtarkres) == 0) {
					$query  = "INSERT INTO kulunvalvonta SET
								yhtio 	= '$user[yhtio]',
								laatija	= '$kukarow[kuka]',
								luontiaika = now(),
								kuka 	= '$user[kuka]',
								aika	= '".date("Y-m-d H:i:s", $sisaanaikaStr)."',
								suunta 	= 'I'
								$querylisa";
					$result = mysql_query($query) or pupe_error($query);
					//	otetaan t�st� koppi!
					$tunnus_sisaan = mysql_insert_id();

					$query  = "INSERT INTO kulunvalvonta SET
								yhtio 	= '$user[yhtio]',
								laatija	= '$kukarow[kuka]',
								luontiaika = now(),
								kuka 	= '$user[kuka]',
								aika	= '".date("Y-m-d H:i:s", $ulosaikaStr)."',
								suunta 	= 'O'
								$querylisa";
					$result = mysql_query($query) or pupe_error($query);

				}
				else {
					echo "<font class='error'>" . t("VIRHE: Aika menee p��llekk�in toisen kirjauksen kanssa")."</font><br>";
					$virhe = TRUE;
				}
			}

			// Ei virheit� menn��n erittelyyn
			if (!$virhe) {
				//jos tuntien erittely on yhti�n parametreiss� sallittu
				if ($yhtiorow["tuntikirjausten_erittely"] == 'E') {
					$tee = "erittele";
					unset($erittelysta);
					$ktunnus = $tunnus_sisaan;
				}
				//jos tuntien erittely ei ole sallittu yhti�n parametreiss�, niin kirjataan automaattisesti vain erittely (+ruokatunti jos yli 4h)
				else {
					$ktunnus = $tunnus_sisaan;
					$tyominuutit = ($ulosaikaStr-$sisaanaikaStr)/60;
					$ruokatunti = 0;
					if ($tyominuutit > 240) {
						//	Onko ruokkis jo kirjattu?
						$query = "	SELECT tunnus
									FROM kulunvalvonta
									WHERE yhtio='$user[yhtio]' and kuka='$user[kuka]' AND aika='".date("Y-m-d H:i:s", $sisaanaikaStr)."' and tyyppi = 'RUO'";
						$tarkres = mysql_query($query) or pupe_error($query);
						//jos ei oo, kirjataan ja vet�st��n ty�ajasta toi puol tuntia pois
						if (mysql_num_rows($tarkres) == 0) {
							tallennaerittely($user, $ktunnus, 0, 30, "RUO");
							$tyominuutit = $tyominuutit - 30;
						}
					}
					tallennaerittely($user, $ktunnus, 0, $tyominuutit, "TYO");
					$tee = "kirjaus";
				}

			}
			else {
				$tee = "kirjaus";
				$ktunnus = $tunnus_sisaan;
			}
		}
		else {

			//	Voisi pohtia pit�isik� n�m� viel� tsekata kertaalleen ennen insertti�?

			//	Kirjaudutaan sis��n
			if ($sisaanaikaStr) {

				if ($sisaanaikaStr > time()) {
					echo "<font class='error'>" . t("VIRHE: Et voi kirjautua sisaan ennakkoon..")."</font><br>";
					$virhe = TRUE;
				}

				if (!$virhe) {
					$query  = "INSERT INTO kulunvalvonta SET
								yhtio 	= '$user[yhtio]',
								kuka 	= '$user[kuka]',
								laatija	= '$kukarow[kuka]',
								luontiaika = now(),
								aika	= '".date("Y-m-d H:i:s", $sisaanaikaStr)."',
								suunta 	= 'I'";
					$result = mysql_query($query) or pupe_error($query);

					echo "<br><font class='message'>Kirjauduitte sis��n ".date("Y-m-d H:i:s", $sisaanaikaStr);
					echo "<meta http-equiv='refresh' content='2;URL=kulunvalvonta.php?toim=$toim'><br>";
					exit;
				}
			}

			//	Kirjaudutaan ulos
			if ($ulosaikaStr) {

				//	Testataan, ett� kirjautua ei ole tulevaisuudessa
				if ($ulosaikaStr > time()) {
					echo "<font class='error'>" . t("VIRHE: Et voi kirjautua ulos ennakkoon..")."</font><br>";
					$virhe = TRUE;
				}

				if (!$virhe) {
					$query  = "INSERT INTO kulunvalvonta SET
								yhtio 	= '$user[yhtio]',
								kuka 	= '$user[kuka]',
								laatija	= '$kukarow[kuka]',
								luontiaika = now(),
								aika	= '".date("Y-m-d H:i:s", $ulosaikaStr)."',
								suunta 	= 'O'";
					$result = mysql_query($query) or pupe_error($query);

					//jos tuntien erittely on yhti�n parametreiss� sallittu
					if ($yhtiorow["tuntikirjausten_erittely"] == 'E') {
						$tee = "erittele";
					}
					//jos tuntien erittely ei ole sallittu yhti�n parametreiss�, niin kirjataan automaattisesti vain erittely (+ruokatunti jos yli 4h)
					else {
						//etsit��n sis��nkirjautumistunnut
						$query = "	SELECT tunnus, unix_timestamp(aika) sisaanaika
									FROM kulunvalvonta
									WHERE yhtio='$user[yhtio]' AND kuka='$user[kuka]' AND aika = (
										SELECT aika
										FROM kulunvalvonta kv
										WHERE kv.aika < '".date("Y-m-d H:i:s", $ulosaikaStr)."' AND kv.kuka=kulunvalvonta.kuka AND kv.yhtio=kulunvalvonta.yhtio AND kv.suunta='I' ORDER BY aika DESC LIMIT 1
									)
									LIMIT 1";
						$result = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($result) == 1) {
							$ktunnus = mysql_result($result,0,"tunnus");
							$sisaanaika = mysql_result($result,0,"sisaanaika");
						}
						$tyominuutit = ($ulosaikaStr-$sisaanaika)/60;
						$ruokatunti = 0;
						if ($tyominuutit > 240) {
							//	Onko ruokkis jo kirjattu?
							$query = "	SELECT tunnus
										FROM kulunvalvonta
										WHERE yhtio='$user[yhtio]' and kuka='$user[kuka]' AND aika='".date("Y-m-d H:i:s", $sisaanaika)."' and tyyppi = 'RUO'";
							$tarkres = mysql_query($query) or pupe_error($query);
							//jos ei oo, kirjataan ja vet�st��n ty�ajasta toi puol tuntia pois
							if (mysql_num_rows($tarkres) == 0) {
								tallennaerittely($user, $ktunnus, 0, 30, "RUO");
								$tyominuutit = $tyominuutit - 30;
							}
						}
						tallennaerittely($user, $ktunnus, 0, $tyominuutit, "TYO");

						echo "<br><font class='message'>Kirjauduitte ulos ".date("Y-m-d H:i:s", $ulosaikaStr);
						echo "<meta http-equiv='refresh' content='2;URL=kulunvalvonta.php?toim=$toim'>";
						exit;
						$tee = "";
					}
				}
				else {
					// oli virheit� over and out!
				}
			}
		}
		$from = "kirjaus";
	}

	//	Uuden erittelymerkinnan lisays tietokantaan
	if ($tee == "tallennaerittely") {
		if (tallennaerittely($user, $ktunnus, $etunnus, ((int) $hours*60+$minutes), $laatu, $otunnus, $ylityo)) {
			$erittelysta = "";
		}
		$tee = "erittele";


	}

	if ($toim == 'HYVAKSYNTA') {

		if ($tee == "hyvaksy") {

			//eli ollaan jo hyv�ksyji�, ja ollaan nyt painettu hyv�ksytty -nappulaa jonkin tuntierittelun kohdalla. ei anneta hyv�ksy�, jos erittelyt ei ole kunnossa. muuten
			//P�ivitet��n tietokantaan n�ihin kirjauksiin hyv�ksytty=1 ja hyv�ksyj�n kukatunnus

			//	Tarkistetaan, ett� meill� on oikea kirjaus johon t�m� liittyy
			$query  = " SELECT *
						FROM kulunvalvonta
						WHERE yhtio = '$user[yhtio]' and kuka='$user[kuka]' and suunta = 'I' and tunnus = '$ktunnus'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$kulu = mysql_fetch_array($result);

				if (tarkista_erittelyn_oikeellisuus($kukarow["yhtio"], $kulu["kuka"], $kulu["aika"])) {

					//	P�ivitet��n erittelyt ja sis��nkirjaus
			    	$query = "	UPDATE kulunvalvonta
			        			SET hyvaksytty=now(), hyvaksyja='$kukarow[kuka]'
			         			WHERE yhtio='$kukarow[yhtio]' and kuka='$kulu[kuka]' and aika='$kulu[aika]' and suunta IN ('I', '')";
			        $result1 = mysql_query($query) or pupe_error($query);
					$tark = mysql_affected_rows();

					//	P�ivitet��n uloskirjaus
			        $query = "	UPDATE kulunvalvonta
			         			SET hyvaksytty=now(), hyvaksyja='$kukarow[kuka]'
			         			WHERE yhtio='$kukarow[yhtio]' and kuka='$kulu[kuka]' and aika>'$kulu[aika]' and suunta='O'
								ORDER BY aika ASC
								LIMIT 1";
			        $result2 = mysql_query($query) or pupe_error($query);
					$tark2 = mysql_affected_rows();
			        if ($tark > 0 and $tark2 > 0) {
			        	$tee = "kirjaus";
			        }
			        else {
			        	echo "<font class='error'><br>" . t("VIRHE: Hyv�ksynt� ep�onnistui")."<br></font>";
			        }
				}
				else {
					echo "<font class='error'><br>" . t("VIRHE: Yritit hyv�ksy� kirjausta jonka erittelyt eiv�t t�sm��")."<br></font>";
					$tee = "kirjaus";
				}
			}
			else {
				echo "<font class='error'>".t("VIRHE: Alkuper�inen kirjaus on kadoksissa.")."</font>";
			}
		}

		//haetaan kannasta k�ytt�j�t joilla on kirjauksia yleens�kin
		$query = "	SELECT nimi, left(md5(concat(tunnus,kuka)), 16) avain, kuka, yhtio
					FROM kuka
					WHERE yhtio='$kukarow[yhtio]'
					ORDER BY nimi";
		$result = mysql_query($query) or pupe_error($query);

		//	Oletuksena Virheelliset ja hyv�ksym�tt�m�t
		if (!isset($rajaus)) {
			$rajaus = "VH";
		}

		$sel=array($rajaus=>"SELECTED");

		//	Laitetaan tarkastukset muistiin!
		$tark=array();

		//	Generoidaan k�ytt�j�lista
		if (mysql_num_rows($result) > 0) {
			$uselect = "<select name='viivakoodi' onchange='this.form.submit();'>
						<option value=''>".t("N�yt� lista")."</option>";

			while ($erittelyita = mysql_fetch_array($result)) {
				if ($erittelyita["kuka"] == $viivakoodi) {
					$selected = "SELECTED";
				}
				else {
					$selected = "";
				}

				//	Jos listataan vaan virheellisii niin tsekataan onko meill� virheit� vai ei
				//	Joudutaan ziigaa rajaukset!
				$eOK = tarkista_erittelyn_oikeellisuus($erittelyita["yhtio"], $erittelyita["kuka"]);

				$query = "	SELECT tunnus
							FROM kulunvalvonta
							WHERE yhtio = '$erittelyita[yhtio]' and kuka='$erittelyita[kuka]' and hyvaksyja=''";
				$htarkres = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($htarkres)>0) {
					$tOK = FALSE;
				}
				else {
					$tOK = TRUE;
				}

				$tark[$erittelyita["kuka"]]["eOK"] = $eOK;
				$tark[$erittelyita["kuka"]]["tOK"] = $tOK;
				if ($rajaus != "") {
					if (	($rajaus == "V" and $eOK==FALSE) or
							($rajaus == "H" and $tOK==FALSE) or
							($rajaus == "VH" and ($tOK==FALSE or $eOK==FALSE))) {
						$tark[$erittelyita["kuka"]]["status"] = "OK";
					}

				}
				else {
					$tark[$erittelyita["kuka"]]["status"] = "OK";
				}

				//OK menuun!
				if ($tark[$erittelyita["kuka"]]["status"] == "OK") {
					$uselect .= "<option value='$erittelyita[avain]' $selected>$erittelyita[nimi]</option>";
				}
			}

			mysql_data_seek($result, 0);
		}

		echo "	<form action = '?toim=$toim' method='post' name='eritellyita'>
				<table>
					<tr><th>".t("N�yt�")."</th><th>".t("K�ytt�j�")."</th></tr>
					<tr>
						<td>
							<select name='rajaus' onchange='submit();'>
								<option value=''>".t("N�yt� kaikki")."</option>
								<option value='VH' ".$sel["VH"].">".t("Virheelliset tai hyv�ksym�tt�m�t")."</option>
								<option value='V' ".$sel["V"].">".t("Vain virheelliset")."</option>
								<option value='H' ".$sel["H"].">".t("Vain hyv�ksym�tt�m�t")."</option>
							</select>
						</td>
						<td>
							$uselect
						</td>
					</tr>
				</table>
				<input type='hidden' name='tee' value='kirjaus'>
				</form><br><br>";

		//jos viivakoodi on tyhj�, ei olla valittu tutkittavaa henkil��, tulostetaan kiva taulukko
		if ($viivakoodi == "") {
			echo "<table><tr><th>".t("Nimi")."</th><th>".t("Erittelyt")."</th><th>".t("Hyv�ksynn�t")."</th></tr>";

			while ($erittelyita = mysql_fetch_array($result)) {

				if ($tark[$erittelyita["kuka"]]["status"] == "OK") {
						echo "	<tr valign='middle'><td width='200'>$erittelyita[nimi]</td>";

						if ($tark[$erittelyita["kuka"]]["eOK"] === FALSE) {
							echo "<td align='center'><font class='error'>" . t("VIRHE")."</font></td>";
						}
						else {
							echo "<td align='center'><font class='message'>OK</font></td>";
						}

						//katsotaan onko hyvaksym�tt�mi� tunteja, pistet��n nimenper��n herjaa jos on (helpottaa taas pomoa)
						if ($tark[$erittelyita["kuka"]]["tOK"] === FALSE) {
							echo "<td align='center'><font class='error'>" . t("PUUTTUU")."</font></td>";
						}
						else {
							echo "<td align='center'><font class='message'>OK</font></td>";
						}
						echo "	<td class='back'>
									<form action = '?toim=$toim' method='post' name='eritellyita_taulukko'>
									<input type='hidden' name='viivakoodi' value='$erittelyita[avain]'>
									<input type='hidden' name='tee' value='kirjaus'>
									<input type='hidden' name='rajaus' value='$rajaus'>
									<input type='submit' value='".t("tarkastele")."'>
								</td>
								</tr></form>";
					}
				}

			echo "</table>";
		}

	}

	//	Kirjauslomake sis��n/ulos
	if ($tee == "kirjaus") {
		if ($toim == "MYYNTI" or $toim == "HYVAKSYNTA") {
			echo "<font class='message'>".t("Lis�� ty�tunnit")."</font><br>";

			//	Oletuksia!
			if (!isset($myyntisisaan_paiva)) 	$myyntisisaan_paiva 	= date("d");
			if (!isset($myyntisisaan_kuukausi))	$myyntisisaan_kuukausi	= date("m");
			if (!isset($myyntisisaan_vuosi))		$myyntisisaan_vuosi		= date("Y");
			if (!isset($myyntisisaan_tunti))		$myyntisisaan_tunti		= "08";
			if (!isset($myyntisisaan_minuutti))	$myyntisisaan_minuutti	= "00";

			if (!isset($myyntiulos_paiva))		$myyntiulos_paiva		= date("d");
			if (!isset($myyntiulos_kuukausi))	$myyntiulos_kuukausi	= date("m");
			if (!isset($myyntiulos_vuosi))		$myyntiulos_vuosi		= date("Y");
			if (!isset($myyntiulos_tunti))		$myyntiulos_tunti		= "16";
			if (!isset($myyntiulos_minuutti))	$myyntiulos_minuutti	= "00";

			echo "<table><tr><th align='center' colspan='5'>" . t("Aloitusaika")."<th>&nbsp;</th><th align='center' colspan='5'>". t("Lopetusaika")."</th></tr>
			<tr align='center'><th>" . t("P�iv�")."</th><th>" . t("Kuukausi")."</th><th>" . t("Vuosi")."</th><th>" . t("Tunti")."</th><th>" . t("Minuutti")."</th><th>&nbsp;</th>
				<th>" . t("P�iv�")."</th><th>" . t("Kuukausi")."</th><th>" . t("Vuosi")."</th><th>" . t("Tunti")."</th><th>" . t("Minuutti")."</th>
			</tr>
			<tr align='center'>
				<form action = '?oletus_erittely=$oletus_erittely' name='myyntimies' method='post' >
				<input type='hidden' name='viivakoodi' value='$viivakoodi'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tee' value='kirjaa'>
				<td><input type='text' name='myyntisisaan_paiva' 	value='$myyntisisaan_paiva' size='4'></td>
				<td><input type='text' name='myyntisisaan_kuukausi'	value='$myyntisisaan_kuukausi' size='4'></td>
				<td><input type='text' name='myyntisisaan_vuosi' 	value='$myyntisisaan_vuosi' size='4'></td>
				<td><input type='text' name='myyntisisaan_tunti' 	value = '$myyntisisaan_tunti' size='4' ></td>
				<td><input type='text' name='myyntisisaan_minuutti'	value='$myyntisisaan_minuutti' size='4'></td>
				<td>--</td>
				<td><input type='text' name='myyntiulos_paiva' 		value='$myyntiulos_paiva' size='4'></td>
				<td><input type='text' name='myyntiulos_kuukausi' 	value='$myyntiulos_kuukausi' size='4'></td>
				<td><input type='text' name='myyntiulos_vuosi' 		value='$myyntiulos_vuosi' size='4'></td>
				<td><input type='text' name='myyntiulos_tunti' 		value='$myyntiulos_tunti' size='4'></td>
				<td><input type='text' name='myyntiulos_minuutti' 	value='$myyntiulos_minuutti' size='4'></td>
				<td class='back'><input type='submit' value='".t("Kirjaa")."'></td>
			</tr>

			</form>
			</table><br><br><font class='message'>".t("Viimeisimm�t hyv�ksym�tt�m�t kirjauksesi")."</font><br>";

			//	K�ytt�j�n hyv�ksym�tt�m�t kirjaukset
			$query = "	SELECT unix_timestamp(aika) sisaan, tunnus, (
							SELECT unix_timestamp(aika)
							FROM kulunvalvonta kv
							WHERE kv.yhtio=kulunvalvonta.yhtio and kv.kuka=kulunvalvonta.kuka and kv.suunta='O' and kv.aika > kulunvalvonta.aika and hyvaksytty='0000-00-00 00:00:00'
							orDER BY aika ASC
							LIMIT 1
						) ulos,
						(
							SELECT tunnus
							FROM kulunvalvonta kv
							WHERE kv.yhtio=kulunvalvonta.yhtio and kv.kuka=kulunvalvonta.kuka and kv.suunta='O' and kv.aika > kulunvalvonta.aika and hyvaksytty='0000-00-00 00:00:00'
							orDER BY aika ASC
							LIMIT 1
						) ulostunnus, muokkaaja, muokattu,ylityo
						FROM kulunvalvonta
						WHERE yhtio='$user[yhtio]' and kuka='$user[kuka]' and suunta='I' and hyvaksyja=''
						orDER BY aika
						DESC LIMIT 40";
			$kirjautumisetres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($kirjautumisetres) > 0) {
				//Listataan kirjautumistiedot
				echo "<table>
					<tr><th>".t("Sis��nkirjautumisaika")."</th><th>".t("Uloskirjautumisaika")."</th>";
				if ($toim == "HYVAKSYNTA") {
					echo "<th>".t("Viimeisin muokkaaja")."</th><th>".t("Muokkausaika")."</th>";
				}

				echo "<th NOWRAP>".t("Erittelyt")."</th></tr>";
				echo "<tr>";

				while ($kirjautumisetrow = mysql_fetch_array($kirjautumisetres)) {
					//	Puretaan ulosajat splitill�
					list($ulosaika, $ulostunnus) = explode("#", $kirjautumisetrow['ulos']);

					if ($kirjautumisetrow["tunnus"] == $muokkaa) {
						echo "
							<tr>
								<form action = '?oletus_erittely=$oletus_erittely' method='post' name='tallenna'>
								<td NOWRAP>
									<input type='text' name='myyntisisaan_paiva' value='".date("d", $kirjautumisetrow["sisaan"])."' size='2'>
									<input type='text' name='myyntisisaan_kuukausi' value='".date("m", $kirjautumisetrow["sisaan"])."' size='2'>
									<input type='text' name='myyntisisaan_vuosi' value='".date("Y", $kirjautumisetrow["sisaan"])."' size='4'>
									<input type='text' name='myyntisisaan_tunti' value='".date("H", $kirjautumisetrow["sisaan"])."'size='2'>
									<input type='text' name='myyntisisaan_minuutti' value='".date("i", $kirjautumisetrow["sisaan"])."'size='2'>
								</td>
								<td NOWRAP>
									<input type='text' name='myyntiulos_paiva' value='".date("d", $kirjautumisetrow["ulos"])."' size='2'>
									<input type='text' name='myyntiulos_kuukausi' value='".date("m", $kirjautumisetrow["ulos"])."' size='2'>
									<input type='text' name='myyntiulos_vuosi' value='".date("Y", $kirjautumisetrow["ulos"])."' size='4'>
									<input type='text' name='myyntiulos_tunti' value='".date("H", $kirjautumisetrow["ulos"])."'size='2'>
									<input type='text' name='myyntiulos_minuutti' value='".date("i", $kirjautumisetrow["ulos"])."'size='2'>
								</td>
								<td colspan='3'>&nbsp;</td>
								<td class='back'>
									<input type='submit' value='".t("Tallenna")."'>
									<input type='hidden' name='tunnus_sisaan' value='$kirjautumisetrow[tunnus]'>
									<input type='hidden' name='tunnus_ulos' value='$kirjautumisetrow[ulostunnus]'>
									<input type='hidden' name='viivakoodi' value='$viivakoodi'>
									<input type='hidden' name='toim' value='$toim'>
									<input type='hidden' name='tee' value='kirjaa'>
									<input type='hidden' name='rajaus' value='$rajaus'>
								</td>
								</form>
							</tr>";
					}
					else {

						// Tsekataan onko n�m� ok
						if (!tarkista_erittelyn_oikeellisuus($user["yhtio"], $user["kuka"], date("Y-m-d H:i:s", $kirjautumisetrow["sisaan"]))) {
							$class='error';
						}
						else {
							$class='';
						}

						echo "
							<tr>
								<td><font class='$class'>".date("d.m.Y H:i", $kirjautumisetrow["sisaan"])."</font></td>
								<td><font class='$class'>".date("d.m.Y H:i", $kirjautumisetrow["ulos"])."</font></td>";

								if ($toim == "HYVAKSYNTA") {
									if ($kirjautumisetrow["muokkaaja"] == 0 and $kirjautumisetrow["muokattu"] == '0000-00-00 00:00:00') {
										$kirjautumisetrow["muokkaaja"] = "";
										$kirjautumisetrow["muokattu"] = "";
									}
									echo "	<td><font class='$class'>$kirjautumisetrow[muokkaaja]</font></td>
											<td><font class='$class'>$kirjautumisetrow[muokattu]</font></td>";
								}

								//Tuntikirjauksen erittelyt listattuna
								$query = "	SELECT kulunvalvonta.*, lasku.tunnus projekti
											FROM kulunvalvonta
											LEFT JOIN lasku ON lasku.yhtio=kulunvalvonta.yhtio and lasku.tunnus=kulunvalvonta.otunnus
											WHERE kulunvalvonta.yhtio='$user[yhtio]' and kuka='$user[kuka]' and unix_timestamp(kulunvalvonta.aika)='$kirjautumisetrow[sisaan]' and suunta=''";
								$result_eritellyt = mysql_query($query) or pupe_error($query);

								$eTot=0;
								echo "<td>
											<table width='100%' align='center' colspan='0' rowspan='0'>";
								while ($eritellyt = mysql_fetch_array($result_eritellyt)) {
									$hours = sprintf("%02d",floor(($eritellyt["minuuttimaara"]/60)));
									$minutes = sprintf("%02d",$eritellyt["minuuttimaara"]%60);

									echo "<tr>
											<td width='100'><font class='$class'><em>$eritellyt[projekti]</em></font></td>
											<td width='50'><font class='$class'><em>$eritellyt[tyyppi]</em></font></td>
											<td width='50'><font class='$class'><em>$hours:$minutes</em></font></td>
										</tr>";
									$eTot+=$eritellyt["minuuttimaara"];
								}
								$hours = sprintf("%02d",floor(($eTot/60)));
								$minutes = sprintf("%02d",$eTot%60);

								echo "<tr>
										<td colspan='2' align='right'></td>
										<td width='50'><font class='$class'><em>$hours:$minutes</em></font></td>
									</tr>";

								echo "</table>
										</td>";

								echo "
								<td class='back'>
									<form action = '?oletus_erittely=$oletus_erittely' method='post' name='muokkaa'>
										<input type='hidden' name='muokkaa' value='$kirjautumisetrow[tunnus]'>
										<input type='hidden' name='viivakoodi' value='$viivakoodi'>
										<input type='hidden' name='toim' value='$toim'>
										<input type='hidden' name='tee' value='kirjaus'>
										<input type='hidden' name='rajaus' value='$rajaus'>
										<input type='submit' value='".t("Muokkaa")."'>
									</form>
								</td>

								<td class='back'>
									<form action = '?oletus_erittely=$oletus_erittely' method='post' name='muokkaa'>
										<input type='hidden' name='ktunnus' value='$kirjautumisetrow[tunnus]'>
										<input type='hidden' name='viivakoodi' value='$viivakoodi'>
										<input type='hidden' name='toim' value='$toim'>
										<input type='hidden' name='tee' value='erittele'>
										<input type='hidden' name='rajaus' value='$rajaus'>
										<input type='submit' value='".t("Tarkastele erittelyit�")."'>
									</form>
								</td>

								<td class='back'>
									<form action = '?oletus_erittely=$oletus_erittely' method='post' name='muokkaa'>
										<input type='hidden' name='tunnus' value='$kirjautumisetrow[tunnus]'>
										<input type='hidden' name='viivakoodi' value='$viivakoodi'>
										<input type='hidden' name='toim' value='$toim'>
										<input type='hidden' name='tee' value='poista_kirjaus'>
										<input type='hidden' name='rajaus' value='$rajaus'>
										<input type='submit' value='".t("Poista")."' onclick=\"return confirm('Oletko varma, ett� haluat poistaa tuntikirjauksen?');\">
									</form>
								</td>";
						//	Jos ollaan hyv�ksynn�ss� ja kaikki on bueno saadaan hyv�ksy� t�m� kirjaus
						if ($toim == "HYVAKSYNTA") {
							if (tarkista_erittelyn_oikeellisuus($user["yhtio"], $user["kuka"], date("Y-m-d H:i:s", $kirjautumisetrow["sisaan"]))) {
								echo "
								<td class='back'>
									<form action = '?oletus_erittely=$oletus_erittely' method='post' name='muokkaa'>
										<input type='hidden' name='ktunnus' value='$kirjautumisetrow[tunnus]'>
										<input type='hidden' name='viivakoodi' value='$viivakoodi'>
										<input type='hidden' name='toim' value='$toim'>
										<input type='hidden' name='tee' value='hyvaksy'>
										<input type='hidden' name='rajaus' value='$rajaus'>
										<input type='submit' value='".t("Hyv�ksy")."' onclick=\"return confirm('Oletko varma, ett� haluat hyv�ksy� kirjauksen sek� erittelyt?');\">
									</form>
								</td></tr>";
							}
							else {
								echo "
								<td class='back'>
									<font class='error'>".t("Korjaa erittelyt")."</font>
								</td></tr>";
							}
						}
					}
				}
				echo "</table>";
			}
			else {
				echo "<font class='info'>".t("Ei hyv�ksym�tt�mi� kirjauksia")."</font><br>";
			}

			//tulostetaan jo hyv�ksytyt kirjaukset, jos mit��n aikaa ei ole valittu niin pistet��n oletuksena kahden viikon ajalta
			echo "<br><font class='message'>".t("Viimeisimm�t hyv�ksytyt kirjauksesi")."</font><br>";
			//pistet��n aika sekunteina taulukkoon
			$listausajatarr = array (	"Kahden viikon ajalta"	=> 1209600,
										"Kuukauden ajalta"		=> 2419200,
										"6kk ajalta"			=> 14515200,
										"Vuoden ajalta"			=> 29030400);

			//tulostetaan valintalista
			echo "
				<form action = '?toim=$toim' method='post' name='hyvaksymiset_aika'>
					<select name='listausaika' onChange='this.form.submit();'";
			foreach($listausajatarr as $selite => $aika) {
				if ($listausaika == $aika) {
					echo "<option value='$aika' SELECTED>".t("$selite") . "</option>";
				}
				else {
					echo "<option value='$aika'>".t("$selite") . "</option>";
				}
			}
			echo "
					</select>
					<input type='hidden' name='tee' value='kirjaus'>
					<input type='hidden' name='viivakoodi' value='$viivakoodi'>
				</form>";

			if (!isset($listausaika)) {
				$listausaika = 1209600;
			}

			//haetaan ja tulostetaan hyv�ksytyt kirjaukset valitulta ajalta
			$query = "	SELECT unix_timestamp(aika) sisaan, (
							SELECT unix_timestamp(aika)
							FROM kulunvalvonta kv
							WHERE kv.yhtio=kulunvalvonta.yhtio and kv.kuka=kulunvalvonta.kuka and kv.suunta='O' and kv.aika > kulunvalvonta.aika and hyvaksytty!='0000-00-00 00:00:00'
							orDER BY aika ASC
							LIMIT 1
						) ulos, (
							SELECT sum(minuuttimaara)
							FROM kulunvalvonta kv2
							WHERE kv2.aika=kulunvalvonta.aika and suunta NOT IN('I','O') and hyvaksytty!='0000-00-00 00:00:00'
						) erittelysumma
						FROM kulunvalvonta
						WHERE yhtio='$user[yhtio]' and kuka='$user[kuka]' and suunta='I' and (unix_timestamp(now())-unix_timestamp(aika))<$listausaika and hyvaksytty!='0000-00-00 00:00:00'
						ORDER BY aika
						DESC";
			$hyvaksytytkirjautumisetres = mysql_query($query) or pupe_error($query);


			if (mysql_num_rows($hyvaksytytkirjautumisetres)>0) {
				echo "<table><tr><th>" . t("Sis��nkirjautumisaika")."</th><th>" . t("Uloskirjautumisaika")."</th><th>" . t("Ty�aika")."</th></tr>";
				$viikkosumma = 0;
				while ($hyvaksytytkirjautumisetrow = mysql_fetch_array($hyvaksytytkirjautumisetres)) {
					$viikko = date('W', $hyvaksytytkirjautumisetrow["sisaan"]);

					if ($viikko != $vanhaviikko and isset($vanhaviikko)) {
						$viikkosumma_tunnit = sprintf("%02d",floor($viikkosumma/60));
						$viikkosumma_minuutit = sprintf("%02d",$viikkosumma%60);
						echo "<tr><td align='right' colspan='2'>".t("Yhteens�"). "</td><td>$viikkosumma_tunnit:$viikkosumma_minuutit</td></tr>";
						echo "<tr><td colspan='3' class='back'>&nbsp;</td></tr>";
	 					$viikkosumma = 0;
					}
					$tunnit = sprintf("%02d",floor($hyvaksytytkirjautumisetrow["erittelysumma"]/60));
					$minuutit = sprintf("%02d",$hyvaksytytkirjautumisetrow["erittelysumma"]%60);
					echo"<tr>
							<td>".date("d.m.Y H:i", $hyvaksytytkirjautumisetrow["sisaan"])."</td>
							<td>".date("d.m.Y H:i", $hyvaksytytkirjautumisetrow["ulos"])."</td>
							<td>" . $tunnit . ":" . $minuutit . "</td>
						</tr>";
					$vanhaviikko = $viikko;
					$viikkosumma = $viikkosumma + $hyvaksytytkirjautumisetrow["erittelysumma"];
				}

				//ja viel� vikalle kirjaukselle toi summa
				$viikkosumma_tunnit = sprintf("%02d",floor($viikkosumma/60));
				$viikkosumma_minuutit = sprintf("%02d",$viikkosumma%60);
				echo "<tr><td align='right' colspan='2'>".t("Yhteens�"). "</td><td>$viikkosumma_tunnit:$viikkosumma_minuutit</td></tr>";
				echo "</table>";
			}
			else {
				echo "<font class='info'>".t("Ei hyv�ksyttyj� kirjauksia")."</font>";
			}
		}
		else {
			// haetaan k�ytt�j�n vika kirjaus
			$query  = " SELECT *
						FROM kulunvalvonta
						WHERE yhtio = '$user[yhtio]' and kuka='$user[kuka]' and suunta IN ('O', 'I')
						orDER BY aika
						DESC LIMIT 1";
			$result = mysql_query($query) or pupe_error($query);
			$kulu   = mysql_fetch_array($result);

			//	P��st��n oletuksena kirjaukseen
			$kirjaukseen = true;

			//tarkastetaan onko k�ytt�j� eritellyt edellisen uloskirjautumisen tunnit
			if ($kulu["suunta"] == "O") {
				//	Haetaan edellinen sis��nkirjaus
				$query  = " SELECT aika, tunnus
							FROM kulunvalvonta
							WHERE yhtio = '$user[yhtio]' and kuka='$user[kuka]' and suunta = 'I'
							orDER BY aika
							DESC LIMIT 1";
				$result = mysql_query($query) or pupe_error($query);
				$edsisaan = mysql_fetch_array($result);

				//	Jos edellisess� kirjauksessa on virheit� menn��n takaisin erittelyyn
				if (!tarkista_erittelyn_oikeellisuus($user["yhtio"], $user["kuka"], $edsisaan["aika"])) {
					$kirjaukseen = FALSE;
					$tee = "erittele";

					//	T�t� tarttetaan jotta osaamme menn� oikeaan kirjaukseen!
					$ktunnus = $edsisaan["tunnus"];

					echo "<font class='error'>".t("Edellinen kirjaus on erittelem�tt�. Erittele kirjaus ennen sis��nkirjautumista.")."</font><br><br>";
				}
			}
			else {
				$ktunnus = $kulu["tunnus"];
			}

			//JOS KAIKKI ERITTELYT ON HOIDETTU, NIIN P��STET��N NorMAALISTI ETEENP�IN, MUUTEN MENN��N ERITTELYYN JATKAMAAN
			if ($toim == 'MYYNTI') {
				$tee = "napit";
				$myynti = "true";
			}

			if ($kirjaukseen === true) {

				// tehd��n selkokielinen suunta
				 if ($kulu["suunta"] == "I") $suunta = t("Sis�ll�");
				 else $suunta = t("Ulkona");

				// n�ytet��n k�ytt�j�n tietoja
				echo "<table>";
				echo "<tr><th>".t("Nimi")."</th><td>$user[nimi]</td></tr>";
				echo "<tr><th>".t("Tila")."</th><td>$suunta</td></tr>";
				echo "<tr><th>".t("Kirjattu")."</th><td>".tv1dateconv($kulu["aika"], "PITKA")."</td></tr>";
				echo "<tr><th>".t("Aika nyt")."</th><td>".tv1dateconv(date("Y-m-d H:i:s"), "PITKA")."</td></tr>";
				echo "</table>";

				// tehd��n k�ytt�liittym�napit
				echo "<br><br><font class='head'>".t("Valitse kirjaus")."</font><hr><br>";

				echo "<form name='napit' action = '?oletus_erittely=$oletus_erittely' method='post' autocomplete='off'>";
				echo "<input type='hidden' name='toim' value='$toim'>";
				echo "<input type='hidden' name='viivakoodi' value='$viivakoodi'>";

				 // jos ollaan viimeks kirjattu ulos, niin n�ytet��n vaan sis��n nappeja
				 if ($kulu["suunta"] == "O" or $kulu['suunta'] == "")  {
					echo "<input type='submit' accesskey='1' name='normin' value='".t("Kirjaudu sis��n")."' style='font-size: 25px;'><br>";
					echo "<input type='hidden' name='tee' value='kirjaa'>";
					echo "<input type='hidden' name='sisaanaikaStr' value='".time()."'>";

					$formi  = "napit";
					$kentta = "normin";
				 }

		  	  	// jos ollaan viimeks kirjattu sis��n, niin n�ytet��n vaan ulos nappeja
		  	  	if ($kulu["suunta"] == "I") {
			  	  	echo "<input type='submit' accesskey='1' name='normout' value='".t("Kirjaudu ulos")."' style='font-size: 25px;'><br>";
					echo "<input type='hidden' name='tee' value='kirjaa'>";
					echo "<input type='hidden' name='ktunnus' value='$ktunnus'>";
					echo "<input type='hidden' name='ulosaikaStr' value='".time()."'>";

			  	  	$formi  = "napit";
			  	  	$kentta = "normout";
		  	  	}

				if ($toim != 'MYYNTI') {
//					echo "<hr><input type='submit' name='peruuta' value='".t("Peruuta kirjaus")."'>";
				}
				echo "</form>";
			}
		}
	}

	//	Eritell��n tunnit
	if ($tee == 'erittele') {

		if ($ktunnus > 0) {

			//	Haetaan meid�n eritelt�v�n kirjauksen ty�tunnit
			$query = "	SELECT *,
							round((((
								SELECT unix_timestamp(aika)
								FROM kulunvalvonta kv
								WHERE kv.yhtio=kulunvalvonta.yhtio and kv.kuka=kulunvalvonta.kuka and kv.suunta='O' and kv.aika > kulunvalvonta.aika
								orDER BY aika ASC
								LIMIT 1
							) - unix_timestamp(aika))/60),0) tyoaika,
							(
								SELECT unix_timestamp(aika)
								FROM kulunvalvonta kv
								WHERE kv.yhtio=kulunvalvonta.yhtio and kv.kuka=kulunvalvonta.kuka and kv.suunta='O' and kv.aika > kulunvalvonta.aika
								orDER BY aika ASC
								LIMIT 1
							) ulos,
							(
								SELECT sum(minuuttimaara)
								FROM kulunvalvonta kv
								WHERE kv.yhtio=kulunvalvonta.yhtio and kv.kuka=kulunvalvonta.kuka and kv.suunta='' and kv.aika = kulunvalvonta.aika
								orDER BY aika ASC
								LIMIT 1
							) eritelty
						FROM kulunvalvonta
						WHERE yhtio = '$user[yhtio]' and tunnus = '$ktunnus'";
			$kirjausres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($kirjausres) == 0) {
				die("Kirjaus katosi!");
			}

			//	Otetaan kirjauksen tiedot talteen.
			$kirjausrow = mysql_fetch_array($kirjausres);

			//katsotaan onko ty�aika yli 4h, jos on niin l�tk�st��n ruokatunti 30minuuttia erittelyihin automaagisesti
			$ruokatunti = 0;
			if ($kirjausrow["tyoaika"] > 240) {
				//	Onko ruokkis jo kirkattu?
				$query = "	SELECT tunnus
							FROM kulunvalvonta
							WHERE yhtio='$user[yhtio]' and kuka='$user[kuka]' AND aika='$kirjausrow[aika]' and tyyppi = 'RUO'";
				$tarkres = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($tarkres) == 0) {
					tallennaerittely($user, $ktunnus, 0, 30, "RUO");
					$ruokatunti = 30;
				}
			}
			$erittelematta = ($kirjausrow["tyoaika"] - $kirjausrow["eritelty"] - $ruokatunti);

			echo "<font class='message'>".t("Ty�aika").": ".sprintf("%02d", floor($kirjausrow["tyoaika"]/60)).":".sprintf("%02d", $kirjausrow["tyoaika"]%60)."</font><br>";

			if ($erittelematta > 0) {
				echo  "<font class='message'>".t("Erittelem�tt�: ").sprintf("%02d", floor($erittelematta/60)).":".sprintf("%02d", $erittelematta%60)."<br><br></font><br>";
			}
			elseif ($erittelematta < 0) {
				echo  "<font class='error'>".t("Ylim��r�ist� erittely�: ").sprintf("%02d", floor(($erittelematta*-1)/60)).":".sprintf("%02d", ($erittelematta*-1)%60)."<br><br></font><br>";
			}

			echo "
			<table>
			<tr><th width='280'>".t("Projekti")."</th><th>".t("Ty�n laatu")."</th><th>".t("Tunnit")."</th><th>".t("Minuutit")."</th>";
			if ($toim == "HYVAKSYNTA") {
				echo "<th>" . t("Viimeisin muokkaaja")."</th>";
				echo "<th>" . t("Muokkausaika")."</th>";
			}

			echo "</tr>";

			//KYSELL��N JO ERITELLYT RIVIT TIETOKANNASTA
			$query = "	SELECT kulunvalvonta.*, concat(lasku.tunnus,' - ', lasku.nimi) projekti, if (avainsana.selitetark IS NULL, '".t("Ruokailu")."', avainsana.selitetark) tyon_laatu, muokkaaja, muokattu
						FROM kulunvalvonta
						LEFT JOIN lasku ON lasku.yhtio=kulunvalvonta.yhtio and lasku.tunnus=kulunvalvonta.otunnus
						LEFT JOIN avainsana ON avainsana.yhtio=kulunvalvonta.yhtio and avainsana.laji='KVERITTELY' and avainsana.selite=kulunvalvonta.tyyppi
						WHERE kulunvalvonta.yhtio='$user[yhtio]' and kuka='$user[kuka]' and kulunvalvonta.aika='$kirjausrow[aika]' and suunta=''";
			$result_eritellyt = mysql_query($query) or pupe_error($query);
			while ($eritellyt = mysql_fetch_array($result_eritellyt)) {

				if ($eritellyt["tunnus"] == $muokkaa) {

					echo "
					<tr>
						<form name='tallenna' action = '?oletus_erittely=$oletus_erittely' method='post'>
						  <td>
							  <select name='otunnus'>
							  <option value=''>".t("Valitse projekti jos mahdollista")."</option>";


					//Haetaan listoille projektit
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]' and lasku.tila = 'R' and alatila!='X'";
					$result = mysql_query($query) or pupe_error($query);
					$projeSel = array($eritellyt["otunnus"] => "SELECTED");
						while ($prow = mysql_fetch_array($result)) {
							echo "<option value='$prow[tunnus]' ".$projeSel[$prow["tunnus"]].">$prow[tunnus] - $prow[nimi]</option>";
						}

					echo "</select></td>";

					//	Haetaan listoille erittelyist�
					$kverittelyres = t_avainsana("KVERITTELY");

					echo "<td><select name='laatu'>";

					$avainSel = array($eritellyt["tyyppi"] => "SELECTED");

					while ($kverittelyrow = mysql_fetch_array($kverittelyres)) {
						echo "<option value=$kverittelyrow[selite] ".$avainSel[$kverittelyrow["selite"]].">$kverittelyrow[selitetark]</option>";
					}
					echo "</select></td>";

					//selvitet��n minuuttim��rist� tunnit ja minuutit lomaketta varten
					$hours = floor(($eritellyt['minuuttimaara']/60));
					$minutes = $eritellyt['minuuttimaara']%60;


					if ($eritellyt["ylityo"] == 1) {
						$ylityovalue = 0;
						$checked = "checked = 'yes'";
					}
					else {
						$ylityovalue = 1;
						$checked = "";
					}
					echo "<td><input type='text' name='hours' size='2' value='$hours' /></td>";
					echo "<td><input type='text' name='minutes' size='2' value='$minutes'></td>";

					echo "<td class='back'><input type='submit' value='".t("Tallenna")."'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tee' value='tallennaerittely'>
							<input type='hidden' name='etunnus' value='$eritellyt[tunnus]'>
							<input type='hidden' name='ktunnus' value='$kirjausrow[tunnus]'>
							<input type='hidden' name='viivakoodi' value='$viivakoodi'>
						</td>
						</form>
					</tr>";
				}
				else {
					//selvitet��n minuuttim��rist� tunnit ja minuutit
					$hours = floor(($eritellyt["minuuttimaara"]/60));
					$minutes = $eritellyt["minuuttimaara"]%60;

					//ylity�lle kauniimpi nimi
					if ($eritellyt["ylityo"] == 1) {
						$ylityo = "<font class='error'>" . t("Kyll�") . "</font>";
					}
					else {
						$ylityo = "";
					}
					echo "
					<tr>
						<td>$eritellyt[projekti]</td>
						<td>$eritellyt[tyon_laatu]</td>
						<td>$hours</td>
						<td>$minutes</td>";
					//Jos ollaan hyv�ksyji� niin n�ytet��n my�s viimeisin muokkaaja ja muokkausaika
					if ($toim == "HYVAKSYNTA") {
						if ($eritellyt["muokkaaja"] == 0 and $eritellyt["muokattu"] == '0000-00-00 00:00:00') {
							$eritellyt["muokkaaja"] = "";
							$eritellyt["muokattu"] = "";
						}
						echo "
						<td>$eritellyt[muokkaaja]</td>
						<td>$eritellyt[muokattu]</td>";
					}
						echo "
						<td class='back'>
							<form name='muokkaa' action = '?oletus_erittely=$oletus_erittely' method='post'>
								<input type='submit' value='muokkaa'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tee' value='erittele'>
								<input type='hidden' name='muokkaa' value='$eritellyt[tunnus]'>
								<input type='hidden' name='ktunnus' value='$kirjausrow[tunnus]'>
								<input type='hidden' name='viivakoodi' value='$viivakoodi'>
							</form>
						</td>
						<td class='back'>
							<form name='poista' action = '?oletus_erittely=$oletus_erittely' method='post'>
								<input type='hidden' name='tee' value='poista_erittely'>
								<input type='hidden' name='tunnus' value='$eritellyt[tunnus]'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='viivakoodi' value='$viivakoodi'>
								<input type='hidden' name='ktunnus' value='$kirjausrow[tunnus]'>
								<input type='submit' value='".t("Poista")."' onclick=\"return confirm('Oletko varma, ett� haluat poistaa erittelyn?');\">
							</form>
						</td>
					</tr>";

				}

			}

				//LIS�� ERITTELYYN
			if (tarkista_erittelyn_oikeellisuus($user["yhtio"], $user["kuka"], $kirjausrow["aika"]))  {
				echo "<br><font class='message'>Tunnit eritelty, voit nyt sulkea selaimen tai jatkaa ty�skentely�.</font><br>";
				if (!$toim == "HYVAKSYNTA") {
					echo "<meta http-equiv='refresh' content='10;URL=kulunvalvonta.php?toim=$toim'>";
				}
			}
			elseif ($erittelematta>0) {
				echo "<tr><td colspan='5' class='back'><br><font class='message'>".t("Lis�� uusi")."</font></td></tr>
						<tr>
						<td>
				  			<form name='erittely' action = '?oletus_erittely=$oletus_erittely' method='post' autocomplete='off'>
				  			<select name='otunnus'>
				  		  	<option value=''>".t("Valitse")."</option>";

						//luetaan kannasta kaikkien projektien nimet selectlistaan
						$query = "	SELECT *
							  		FROM lasku
							  		WHERE yhtio = '$kukarow[yhtio]' and lasku.tila = 'R' and alatila!='X'
									orDER BY tunnusnippu DESC";

						$result = mysql_query($query) or pupe_error($query);

						while ($prow = mysql_fetch_array($result)) {
							echo "<option value='$prow[tunnus]'>$prow[tunnusnippu] $prow[nimi]</option>";
						}
						echo "</select></td>";

						//avainsanat (tyon laatu) omaan selectlistaan
						$result = t_avainsana("KVERITTELY");

						echo "<td>
						  		<select name='laatu'>
						  	  	<option selected=yes value=''>".t("Valitse")."</option>";

						//	Arvotaan oletus
						if (isset($laatu)) {
							$avainSel = array($laatu => "SELECTED");
						}
						else {
							$avainSel = array($oletus_erittely => "SELECTED");
						}
						while ($kvrow = mysql_fetch_array($result)) {
							echo "<option value=$kvrow[selite] ".$avainSel[$kvrow["selite"]].">$kvrow[selitetark]</option>";
						}

						if ($erittelysta != "JOO") {
							$hours = floor($erittelematta/60);
							$minutes = $erittelematta%60;
						}
						else {
							$hours = $_REQUEST["hours"];
							$minutes = $_REQUEST["minutes"];
						}

						echo "	</select>
									</td>";
						echo "
							<td><input type='text' name='hours' size='2' value='$hours'/></td>
							<td><input type='text' name='minutes' size='2' value='$minutes'/></td>
							<td class='back'>
						  		<input type='submit' value='".t("Lis��")."'>
								<input type='hidden' name='erittelysta' value='JOO'>
								<input type='hidden' name='viivakoodi' value='$viivakoodi'>
						  		<input type='hidden' name='ktunnus' value='$ktunnus'>
						  		<input type='hidden' name='tyoaika' value='$tyoaika'>
						  		<input type='hidden' name='tee' value='tallennaerittely'>
								<input type='hidden' name='toim' value='$toim'></td>";
							echo "
						  	</tr>
							</form>";

			}

			echo "	</table><br><br>";

			if ($toim == "MYYNTI" or $toim == "HYVAKSYNTA") {
				if ($toim == "HYVAKSYNTA") {
					echo "	<form action = '?toim=$toim' method='post' name='tarkistajaform'>
								<input type='hidden' name='viivakoodi' value='$viivakoodi'>
								<input type='submit' value='".t("Palaa tuntikirjauksiin")."'>
								<input type='hidden' name='tee' value='kirjaus'>
							</form>";
				}
				else {
					echo "<a href='$PHP_SELF?toim=$toim&oletus_erittely=$oletus_erittely'>".t("Palaa tuntikirjauksiin")."</a>";
				}
			}
		}
		else {
			echo "emme tied� mit� oikein erittelemme!";
		}
	}

	//jos ei muuta, niin pistet��n alkuvalikkoa kehiin..
	if ($tee == "" and $toim == "KULUNVALVONTA") {

		if ($toim == 'MYYNTI') {
			echo "<meta http-equiv='refresh' content='5;URL=kulunvalvonta.php?toim=$toim'>";
		}
		else {

			echo "<br>";
			echo "<font class='head'>".t("Laita kortti lukijaan")."</font><br>";
			echo "<br>";
			echo "<form name='lukija' action = '?oletus_erittely=$oletus_erittely' method='post' autocomplete='off'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='kirjaus'>
					<input size='50' type='password' name='viivakoodi' value=''>";

			echo "</form>";

			// kursorinohjausta
			$formi  = "lukija";
			$kentta = "viivakoodi";
		}
	}

	$ei_kelloa = "X";
	require ("inc/footer.inc");

?>