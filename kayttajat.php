<?php
	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Käyttäjähallinta").":</font><hr>";

	// tää on tällänän kikka.. älkää seotko.. en jaksa pyörittää toimia joka formista vaikka pitäs..
	$PHP_SELF = $PHP_SELF."?toim=$toim";

	if ($generatepass != "") {
		$generoitupass = trim(shell_exec("openssl rand -base64 12"));
		$tee = "MUUTA";
		$firname = "";
	}

	if (isset($muutparametrit)) {
		list ($tee, $selkuka, $selyhtio) = split("#", $muutparametrit);
		$ytunnus = "";
	}

	if ($tee == "MUUTA" and $ytunnus != "" and $ytunnus != '0') {
		$muutparametrit = "MUUTA#$selkuka#$selyhtio";
		$asiakasid = "";
		require ("inc/asiakashaku.inc");

		if ($monta == 1) {
			$krow["oletus_asiakas"] = $asiakasid;
			$tee = "MUUTA";
			$firname = "";
		}
		else {
			$tee = "eimitään";
		}
	}
	elseif ($ytunnus == '0') {
		// Nollalla saa poistettua aletus_asiakkaan
		$krow["oletus_asiakas"] = "";
		$tee = "MUUTA";
		$firname = "";
		
		$query = "UPDATE kuka SET oletus_asiakas = '' WHERE tunnus='$selkuka'";
		$result = mysql_query($query) or pupe_error($query);
	}

	// Poistetaan koko käyttäjä tältä yriykseltä!!
	if ($tee == 'deluser') {
		$query = "delete from kuka WHERE kuka='$selkuka' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "delete from oikeu WHERE kuka='$selkuka' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<b>".t("Käyttäjä")." $selkuka ".t("poistettu")."!</b><br>";
		$selkuka=$kukarow['tunnus'];
	}

	// Poistetaan käyttäjän salasana
	if ($tee == 'delpsw') {
		$query = "	UPDATE kuka
					SET salasana = '',
					muuttaja	 = '$kukarow[kuka]', 
					muutospvm	 = now()
					WHERE kuka='$selkuka'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<b>".t("Käyttäjän")." $selkuka ".t("salasana poistettu")."!</b><br>";
		$selkuka=$kukarow['tunnus'];
	}

	// Perustetaan uusi käyttäjä
	if ($tee == 'UUSI') {

		if ($selyhtio != '') {
			$yhtio = $selyhtio;
		}
		else {
			$yhtio = $kukarow['yhtio'];
		}

		$query   = "SELECT * FROM kuka WHERE kuka='$ktunnus' and yhtio<>'$yhtio'";
		$reskuka = mysql_query($query) or pupe_error($query);

		$query   = "SELECT * FROM kuka WHERE kuka='$ktunnus' and yhtio='$yhtio'";
		$resyh   = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($resyh) > 0) {
			$monta = mysql_fetch_array($resyh);
			echo "<font class='error'>".t("Käyttäjä")." $monta[kuka] ($monta[nimi]) ".t("on jo yrityksessä")." $yhtio.</font><br>";
			$jatka=1; // ei perusteta
		}

		$salasana = "";

		if (mysql_num_rows($reskuka) > 0 and $jatka != 1) {
			$monta = mysql_fetch_array($reskuka);

			$firname 						= $monta['nimi'];
			$ktunnus 						= $monta['kuka'];
			$phonenum 						= $monta['puhno'];
			$email 							= $monta['eposti'];
			$lang 							= $monta['kieli'];
			$taso 							= $monta['taso'];
			$hinta 							= $monta['hinnat'];
			$saatavat 						= $monta['saatavat'];
			$salasana 						= $monta['salasana'];
			$kassamyyja 					= $monta['kassamyyja'];
			$dynaaminen_kassamyynti			= $monta['dynaaminen_kassamyynti'];
			$jyvitys 						= $monta['jyvitys'];			
			$oletus_ohjelma 				= $monta['oletus_ohjelma'];
			$resoluutio 					= $monta['resoluutio'];
			$extranet 						= $monta['extranet'];
			$hyvaksyja 						= $monta['hyvaksyja'];
			$naytetaan_katteet_tilauksella	= $monta['naytetaan_katteet_tilauksella'];
			$profile 						= $monta['profiilit'];

			echo "<font class='message'>".t("Käyttäjä")." $monta[kuka] ($monta[nimi]) ".t("löytyi muista yrityksistä.")."<br>";
			echo t("Hänelle lisätään nyt myös oikeudet yritykselle")." $yhtio.<br>".t("Käyttäjätiedot kopioidaan yhtiöstä")." $monta[yhtio].</font><br>";
		}

		if (strlen($ktunnus) > 0 and $jatka != 1) {

			if (count($profiili) > 0) {
				foreach($profiili as $prof) {
					$profile .= $prof.",";
				}
				$profile = substr($profile,0,-1);
			}

			$password = md5(trim($password));
			if ($salasana == "") $salasana = $password; // jos meillä ei ole kopioitua salasanaa toisesta yrityksestä, käytetään syötettyä

			$query = "	INSERT into kuka
						SET nimi 		= '$firname',
						kuka 			= '$ktunnus',
						puhno 			= '$phonenum',
						eposti 			= '$email',
						kieli 			= '$lang',
						taso 			= '$taso',
						hinnat			= '$hinta',
						saatavat		= '$saatavat',
						osasto			= '$osasto',
						salasana		= '$salasana',
						keraajanro 		= '$keraajanro',
						myyja 			= '$myyja',
						varasto 		= '$varasto',
						kirjoitin 		= '$kirjoitin',
						kassamyyja 		= '$kassamyyja',
						dynaaminen_kassamyynti = '$dynaaminen_kassamyynti',
						jyvitys			= '$jyvitys',
						oletus_asiakas 	= '$oletus_asiakas',
						oletus_ohjelma 	= '$oletus_ohjelma',
						resoluutio		= '$resoluutio',
						extranet		= '$extranet',
						hyvaksyja		= '$hyvaksyja',
						lomaoikeus		= '$lomaoikeus',
						asema			= '$asema',
						toimipaikka		= '$toimipaikka',
						naytetaan_katteet_tilauksella = '$naytetaan_katteet_tilauksella',
						profiilit 		= '$profile',
						laatija			= '$kukarow[kuka]',
						luontiaika		= now(),
						yhtio 			= '$yhtio'";
			$result = mysql_query($query) or pupe_error($query);
			$selkuka = mysql_insert_id();

			echo "<font class='message'>".t("Käyttäjä perustettu")."! ($selkuka)</font><br><br>";

			echo "<font class='error'>".t("Valitse nyt käyttäjän oletusasiakas")."!</font><br><br>";

			if ($yhtio != $kukarow["yhtio"]) {
				$selkuka = "";
			}

			//päivitetään oikeudet jos profiileja on olemassa
			$profiilit = explode(',', trim($profile));

			//poistetaan käyttäjän vanhat profiilioikeudet
			$query = "	DELETE FROM oikeu
						WHERE yhtio='$yhtio' and kuka='$ktunnus' and lukittu=''";
			$pres = mysql_query($query) or pupe_error($query);

			if (count($profiilit) > 0 and $profiilit[0] !='') {

				//käydään uudestaan profiili läpi
				foreach($profiilit as $prof) {

					$query = "	SELECT *
								FROM oikeu
								WHERE yhtio='$yhtio' and kuka='$prof' and profiili='$prof'";
					$pres = mysql_query($query) or pupe_error($query);

					while ($trow = mysql_fetch_array($pres)) {
						//joudumme tarkistamaan ettei tätä oikeutta ole jo tällä käyttäjällä.
						//voi olla esim jos se on lukittuna annettu
						$query = "	SELECT yhtio
									FROM oikeu
									WHERE kuka		= '$ktunnus'
									and sovellus	= '$trow[sovellus]'
									and nimi		= '$trow[nimi]'
									and alanimi 	= '$trow[alanimi]'
									and paivitys	= '$trow[paivitys]'
									and nimitys		= '$trow[nimitys]'
									and jarjestys 	= '$trow[jarjestys]'
									and jarjestys2	= '$trow[jarjestys2]'
									and yhtio		= '$yhtio'";
						$tarkesult = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($tarkesult) == 0) {
							$query = "	INSERT into oikeu
										SET
										kuka		= '$ktunnus',
										sovellus	= '$trow[sovellus]',
										nimi		= '$trow[nimi]',
										alanimi 	= '$trow[alanimi]',
										paivitys	= '$trow[paivitys]',
										nimitys		= '$trow[nimitys]',
										jarjestys 	= '$trow[jarjestys]',
										jarjestys2	= '$trow[jarjestys2]',
										yhtio		= '$yhtio'";
							$rresult = mysql_query($query) or pupe_error($query);
						}
					}
				}
			}

			if ($toim == "extranet") {
				$tee     = "MUUTA";
				$firname = "";
			}
			else {
				$tee = "";
			}
		}
		else {
			echo "<font class='error'>".t("Uutta käyttäjää ei luotu")."!</font><br>";
			$tee     = "MUUTA";
			$selkuka = "UUSI";
			$firname = "";
		}
	}

	// Muutetaanko jonkun muun oikeuksia??
	if ($selkuka != '') {
		$query = "SELECT * FROM kuka WHERE tunnus='$selkuka'";
	}
	else {
		$query = "SELECT * FROM kuka WHERE tunnus='$kukarow[tunnus]'";
	}

	$result = mysql_query($query) or pupe_error($query);
	$selkukarow = mysql_fetch_array($result);

	//muutetaan kayttajan tietoja tai syotetaan uuden kayttajan tiedot
	if ($tee == 'MUUTA') {

		if ($selyhtio != '') {
			$yhtio = $selyhtio;
		}
		else {
			$yhtio = $kukarow['yhtio'];
		}

		if (strlen($firname) > 0) {

			if (count($profiili) > 0) {
				foreach($profiili as $prof) {
					$profile .= $prof.",";
				}
				$profile = substr($profile,0,-1);
			}

			//päivitetään salasana
			if (trim($password) != '') {
				$password = md5(trim($password));

				$query = "	UPDATE kuka
							SET salasana = '$password',
							muuttaja	 = '$kukarow[kuka]', 
							muutospvm	 = now()
							WHERE kuka='$kuka'";
				$result = mysql_query($query) or pupe_error($query);
			}

			$query = "	UPDATE kuka
						SET nimi 		= '$firname',
						puhno 			= '$phonenum',
						eposti 			= '$email',
						kieli 			= '$lang',
						taso 			= '$taso',
						hinnat			= '$hinnat',
						saatavat		= '$saatavat',
						keraajanro 		= '$keraajanro',
						myyja 			= '$myyja',
						osasto			= '$osasto',
						varasto 		= '$varasto',
						kirjoitin 		= '$kirjoitin',
						oletus_asiakas 	= '$oletus_asiakas',
						resoluutio 		= '$resoluutio',
						extranet		= '$extranet',
						hyvaksyja		= '$hyvaksyja',
						lomaoikeus		= '$lomaoikeus',
						asema			= '$asema',
						toimipaikka		= '$toimipaikka',
						kassamyyja 		= '$kassamyyja',
						dynaaminen_kassamyynti = '$dynaaminen_kassamyynti',
						jyvitys			= '$jyvitys',
						oletus_ohjelma 	= '$oletus_ohjelma',
						naytetaan_katteet_tilauksella = '$naytetaan_katteet_tilauksella',
						profiilit 		= '$profile',
						muuttaja		= '$kukarow[kuka]', 
						muutospvm		= now()
						WHERE kuka='$kuka' and yhtio='$yhtio'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	SELECT nimi, kuka, tunnus
						FROM kuka
						WHERE tunnus='$selkuka'";
			$result = mysql_query($query) or pupe_error($query);
			$selkukarow = mysql_fetch_array($result);

			//päivitetään oikeudet jos profiileja on olemassa
			$profiilit = explode(',', trim($profile));

			//poistetaan käyttäjän vanhat profiilioikeudet
			$query = "	DELETE FROM oikeu
						WHERE yhtio='$kukarow[yhtio]' and kuka='$kuka' and lukittu=''";
			$pres = mysql_query($query) or pupe_error($query);

			if (count($profiilit) > 0 and $profiilit[0] != '') {
				//käydään uudestaan profiili läpi
				foreach($profiilit as $prof) {
					$query = "	SELECT *
								FROM oikeu
								WHERE yhtio='$kukarow[yhtio]' and kuka='$prof' and profiili='$prof'";
					$pres = mysql_query($query) or pupe_error($query);

					while ($trow = mysql_fetch_array($pres)) {
						//joudumme tarkistamaan ettei tätä oikeutta ole jo tällä käyttäjällä.
						//voi olla esim jos se on lukittuna annettu
						$query = "	SELECT yhtio
									FROM oikeu
									WHERE kuka		= '$kuka'
									and sovellus	= '$trow[sovellus]'
									and nimi		= '$trow[nimi]'
									and alanimi 	= '$trow[alanimi]'
									and paivitys	= '$trow[paivitys]'
									and nimitys		= '$trow[nimitys]'
									and jarjestys 	= '$trow[jarjestys]'
									and jarjestys2	= '$trow[jarjestys2]'
									and yhtio		= '$kukarow[yhtio]'";
						$tarkesult = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($tarkesult) == 0) {
							$query = "	INSERT into oikeu
										SET
										kuka		= '$kuka',
										sovellus	= '$trow[sovellus]',
										nimi		= '$trow[nimi]',
										alanimi 	= '$trow[alanimi]',
										paivitys	= '$trow[paivitys]',
										nimitys		= '$trow[nimitys]',
										jarjestys 	= '$trow[jarjestys]',
										jarjestys2	= '$trow[jarjestys2]',
										yhtio		= '$kukarow[yhtio]'";
							$rresult = mysql_query($query) or pupe_error($query);
						}
					}
				}
			}
			$tee = "";
		}
		else {
			//tama siis vain jos muutetaan jonkun tietoja
			if ($selkuka != "UUSI") {

				$query = "SELECT * FROM kuka";

				if ($selkukarow['kuka'] != '') {
					$query .= " WHERE kuka = '$selkukarow[kuka]' and yhtio = '$yhtio'";
				}
				else {
					$profiilit = $profile;
					$query .= " WHERE session='$session' and yhtio = '$yhtio'";
				}

				$result = mysql_query($query) or pupe_error($query);
				$krow = mysql_fetch_array ($result);

				if (mysql_num_rows($result) != 1) {
					echo t("VIRHE: Hakkerointia!");
					exit;
				}
			}

			echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<table>";

			if ($selkuka != "UUSI") {
				echo "<input type='hidden' name='tee' value='MUUTA'>
						<input type='hidden' name='selkuka' value='$selkukarow[tunnus]'>
					  <input type='hidden' name='kuka' value='$selkukarow[kuka]'>";
				echo "<tr><th align='left'>".t("Käyttäjätunnus").":</th><td><b>$krow[kuka]</b> ".t("Lastlogin").": $krow[lastlogin]</td></tr>";
			}
			else {
				echo "<input type='hidden' name='tee' value='UUSI'>";
				echo "<tr><th align='left'>".t("Käyttäjätunnus").":</th>
				<td><input type='text' size='50' maxlenght='30' name='ktunnus'></td></tr>";
			}
			echo "<tr><th align='left'>".t("Salasana").":</th><td><input type='text' size='50' maxlenght='30' name='password' value='$generoitupass'></td><td class='back'> <a href='?generatepass=y&selkuka=$selkuka&toim=$toim'>".t("Generoi salasana")."</a></td></tr>";
			echo "<tr><th align='left'>".t("Nimi").":</th><td><input type='text' size='50' value='$krow[nimi]' maxlenght='30' name='firname'></td></tr>";
			echo "<tr><th align='left'>".t("Puhelinnumero").":</th><td><input type='text' size='50' value='$krow[puhno]' maxlenght='30' name='phonenum'></td></tr>";
			echo "<tr><th align='left'>".t("Sähköposti").":&nbsp;</th><td><input type='text' size='50' value='$krow[eposti]' maxlenght='50' name='email'></td></tr>";
			echo "<tr><th align='left'>".t("Kieli").":&nbsp;</th><td><select name='lang'>";

			$query  = "show columns from sanakirja";
			$fields =  mysql_query($query);

			while ($apurow = mysql_fetch_array($fields)) {
				$sel = "";
				if ($krow["kieli"] == $apurow[0] or ($krow["kieli"] == "" and $apurow[0] == $yhtiorow["kieli"])) {
					$sel = "selected";
				}
				if ($apurow[0] != "tunnus") {
					$query = "select distinct nimi from maat where koodi='$apurow[0]'";
					$maare = mysql_query($query);
					$maaro = mysql_fetch_array($maare);
					$maa   = strtolower($maaro["nimi"]);
					if ($maa=="") $maa = $apurow[0];
					echo "<option value='$apurow[0]' $sel>".t($maa)."</option>";
				}
			}

			if ($toim != 'extranet') {
				echo "</select></td></tr>";

				$sel9 = $sel2 = $sel1 = "";

				if ($krow["taso"] == "9") {
					$sel9 = "SELECTED";
				}
				if ($krow["taso"] == "1") {
					$sel1 = "SELECTED";
				}
				if ($krow["taso"] == "2") {
					$sel2 = "SELECTED";
				}
				echo "<tr><th align='left'>".t("Taso").":</th>";

				echo "<td><select name='taso'>";
				echo "<option value='9' $sel9>".t("Taso 9 Aloittelijahyväksyjä, tiliöintejä ei näytetä")."</option>";
				echo "<option value='1' $sel1>".t("Taso 1 Perushyväksyjä, tiliöntejä ei voi muuttaa")."</option>";
				echo "<option value='2' $sel2>".t("Taso 2 Tehohyväksyjä, tiliöntejä voi muuttaa")."</option>";
				echo "</select></td></tr>";
			}
			else {
				$sel2 = $sel1 = "";

				if ($krow["taso"] == "1") {
					$sel1 = "SELECTED";
				}
				if ($krow["taso"] == "2") {
					$sel2 = "SELECTED";
				}
				echo "<tr><th align='left'>".t("Taso").":</th>";

				echo "<td><select name='taso'>";
				echo "<option value='1' $sel1>".t("Taso 1 Tehotilaaja, tilaukset menee suoraan tomitukseen")."</option>";
				echo "<option value='2' $sel2>".t("Taso 2 Aloittelijatilaaja, tilaukset hyväksytetään ennen toimitusta")."</option>";
				echo "</select></td></tr>";
			}

			if ($toim == 'extranet') {
				$sel0 = $sel1 = "";

				if ($krow["hinnat"] == "0") {
					$sel0 = "SELECTED";
				}
				if ($krow["hinnat"] == "1") {
					$sel1 = "SELECTED";
				}
				echo "<tr><th align='left'>".t("Hinnat").":</th>";

				echo "<td><select name='hinnat'>";
				echo "<option value='0' $sel0>".t("Normaali")."</option>";
				echo "<option value='1' $sel1>".t("Näytetään vain tuotteen myyntihinta")."</option>";
				echo "</select></td></tr>";
			}

			if ($toim == 'extranet') {
				$sel0 = $sel1 = "";

				if ($krow["saatavat"] == "0") {
					$sel0 = "SELECTED";
				}
				if ($krow["saatavat"] == "1") {
					$sel1 = "SELECTED";
				}
				if ($krow["saatavat"] == "2") {
					$sel2 = "SELECTED";
				}
				if ($krow["saatavat"] == "3") {
					$sel3 = "SELECTED";
				}

				echo "<tr><th align='left'>".t("Saatavat").":</th>";

				echo "<td><select name='saatavat'>";
				echo "<option value='0' $sel0>".t("Näytetään saatavat, jätetään kesken jos maksamattomia laskuja")."</option>";
				echo "<option value='1' $sel1>".t("Näytetään saatavat, siirretään aina tulostusjonoon")."</option>";
				echo "<option value='2' $sel2>".t("Ei naytetä saatavia, jätetään kesken jos maksamattomia laskuja")."</option>";
				echo "<option value='3' $sel3>".t("Ei naytetä saatavia, siirretään aina tulostusjonoon")."</option>";
				echo "</select></td></tr>";
			}

			if ($toim != 'extranet') {
				echo "<tr><th align='left'>".t("Myyjänro").":</th><td><input type='text' name='myyja' value='$krow[myyja]' size='5'></td></tr>";
				echo "<tr><th align='left'>".t("Kerääjänro").":</th><td><input type='text' name='keraajanro' value='$krow[keraajanro]' size='5'></td></tr>";

				echo "<tr><th align='left'>".t("Osasto").":</td>";
				echo "<td><select name='osasto'><option value=''>".t("Ei osastoa")."</option>";

				$query  = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='HENKILO_OSASTO' order by selite";
				$vares = mysql_query($query) or pupe_error($query);

				while ($varow = mysql_fetch_array($vares))
				{
					$sel='';
					if ($varow['selite']==$krow["osasto"]) $sel = 'selected';
					echo "<option value='$varow[selite]' $sel>$varow[selitetark]</option>";
				}

				echo "</select></td></tr>";
			}

			echo "<tr><th align='left'>".t("Myy varastosta").":</td>";
			echo "<td><select name='varasto'><option value='0'>".t("Oletus")."</option>";

			$query  = "SELECT tunnus, nimitys FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
			$vares = mysql_query($query) or pupe_error($query);

			while ($varow = mysql_fetch_array($vares)) {
				$sel='';
				if ($varow['tunnus']==$krow["varasto"]) $sel = 'selected';
				echo "<option value='$varow[tunnus]' $sel>$varow[nimitys]</option>";
			}

			echo "</select></td></tr>";
			
			if ($toim != 'extranet') {
				echo "<tr><th align='left'>".t("Henkilökohtainen tulostin:")."</td>";
				echo "<td><select name='kirjoitin'><option value=''>".t("Ei oletuskirjoitinta")."</option>";

				$query  = "SELECT tunnus, kirjoitin FROM kirjoittimet WHERE yhtio='$kukarow[yhtio]'";
				$vares = mysql_query($query) or pupe_error($query);

				while ($varow = mysql_fetch_array($vares)) {
					$sel='';
					if ($varow['tunnus']==$krow["kirjoitin"]) $sel = 'selected';
					echo "<option value='$varow[tunnus]' $sel>$varow[kirjoitin]</option>";
				}

				echo "</select></td></tr>";


				echo "<tr><th align='left'>".t("Kassamyyjä").":</td>";
				echo "<td><select name='kassamyyja'><option value=''>".t("Ei kassamyyjä")."</option>";

				$query  = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='KASSA' order by selite";
				$vares = mysql_query($query) or pupe_error($query);

				while ($varow = mysql_fetch_array($vares)) {
					$sel='';
					if ($varow['selite']==$krow["kassamyyja"]) $sel = 'selected';
					echo "<option value='$varow[selite]' $sel>$varow[selitetark]</option>";
				}

				echo "</select></td></tr>";
				
				echo "<tr><th align='left'>".t("Dynaaminen kassamyyjä").":</td>";

				$sel1="";
				$sel2="";

				if ($krow["dynaaminen_kassamyynti"] == "") {
					$sel1 = "selected";
				}
				else {
					$sel2 = "selected";
				}
				echo "<td><select name='dynaaminen_kassamyynti'>";
				echo "<option value='' $sel1>".t("Normaalimyyjä ei toimi kassamyyjänä")."</option>";
				echo "<option value='o' $sel2>".t("Normaalimyyjä voi toimia tarpeen mukaan kassamyyjänä")."</option>";
				echo "</select></td>";

				echo "<tr><th align='left'>".t("Hyväksyjä").":</td>";

				if ($krow["hyvaksyja"] != '') {
					$chk = "CHECKED";
				}
				else {
					$chk = "";
				}
				echo "<td><input type='checkbox' name='hyvaksyja' $chk></td></tr>";
			}

			if ($selkuka != "UUSI") {

				echo "<tr><th align='left'>".t("Oletusasiakas").":</th><td>";
				echo "<input type='text' name='ytunnus'>";

				if ($asiakasid != "") $krow["oletus_asiakas"] = $asiakasid;

				if ($krow["oletus_asiakas"] != "") {

					$query = "select * from asiakas where tunnus='$krow[oletus_asiakas]'";
					$vares = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($vares) == 1) {
						$varow = mysql_fetch_array($vares);

						echo " $varow[ytunnus] $varow[nimi]";

						if ($varow["toim_ovttunnus"] != "") {
							echo " / $varow[toim_ovttunnus] $varow[toim_nimi]";
						}

						echo "<input type='hidden' name='oletus_asiakas' value='$krow[oletus_asiakas]'>";
					}
					else {
						echo " ".t("Asiakas ei löydy")."!";
					}
				}
				else {
					echo " ".t("Oletusasiakasta ei ole syötetty")."!";
				}

				echo "</td></tr>";
			}

			if ($toim != 'extranet') {
				echo "<tr><th align='left'>".t("Oletusohjelma").":</th><td><select name='oletus_ohjelma'>";
				echo "<option value=''>".t("Ei oletusta")."</option>";

				$query  = "SELECT distinct nimi, nimitys, sovellus FROM oikeu WHERE yhtio='$kukarow[yhtio]' and kuka='$krow[kuka]' ORDER by sovellus, nimitys";
				$vares = mysql_query($query) or pupe_error($query);

				while ($varow = mysql_fetch_array($vares))
				{
					$sel='';
					if ($varow['nimi'] == $krow["oletus_ohjelma"]) $sel = 'selected';

					echo "<option value='$varow[nimi]' $sel>".t($varow["sovellus"])." - ".t($varow["nimitys"])."</option>";
				}
				echo "</select></td></tr>";


				$sel1 = "SELECTED";

				if ($krow['resoluutio'] == "N") {
					$sel1 = "SELECTED";
					$sel2 = "";
					$sel3 = "";
				}
				if ($krow['resoluutio'] == "I") {
					$sel2 = "SELECTED";
					$sel1 = "";
					$sel3 = "";
				}
				if ($krow['resoluutio'] == "P") {
					$sel3 = "SELECTED";
					$sel1 = "";
					$sel2 = "";
				}

				echo "<tr><th align='left'>".t("Näytön koko").":</th>
						<td><select name='resoluutio'>
						<option value='P' $sel3>".t("Pieni")."</option>
						<option value='N' $sel1>".t("Normaali")."</option>
						<option value='I' $sel2>".t("Iso")."</option>
						</select></td></tr>";

				if ($krow['naytetaan_katteet_tilauksella'] == "") {
					$sel1 = "SELECTED";
					$sel2 = "";
					$sel3 = "";
				}
				if ($krow['naytetaan_katteet_tilauksella'] == "Y") {
					$sel1 = "";
					$sel2 = "SELECTED";
					$sel3 = "";
				}
				if ($krow['naytetaan_katteet_tilauksella'] == "N") {
					$sel1 = "";
					$sel2 = "";
					$sel3 = "SELECTED";
				}

				echo "<tr><th align='left'>".t("Katteet näytetään tilauksentekovaiheessa").":</th>
						<td><select name='naytetaan_katteet_tilauksella'>
						<option value=''  $sel1>".t("Oletus")."</option>
						<option value='Y' $sel2>".t("Kate näytetään")."</option>
						<option value='N' $sel3>".t("Katetta ei näytetä")."</option>
						</select></td></tr>";
						
				echo "<tr><th align='left'>".t("Lomaoikeus").":</th>
						<td><input type='text' name='lomaoikeus' size='3' value='$krow[lomaoikeus]'></td></tr>";
						
				echo "<tr><th align='left'>".t("Asema").":</td>";
				echo "<td><select name='asema'><option value=''>".t("Ei asemaa")."</option>";

				$query  = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='KUKAASEMA' order by jarjestys, selite";
				$vares = mysql_query($query) or pupe_error($query);

				while ($varow = mysql_fetch_array($vares)) {
					$sel='';
					if ($varow['selite']==$krow["asema"]) $sel = 'selected';
					echo "<option value='$varow[selite]' $sel>$varow[selitetark]</option>";
				}

				echo "</select></td></tr>";
				
				
				echo "<tr><th align='left'>".t("Toimipaikka").":</td>";
				echo "<td><select name='toimipaikka'><option value=''>".t("Oletustoimipaikka")."</option>";

				$query  = "SELECT * FROM yhtion_toimipaikat WHERE yhtio='$kukarow[yhtio]' order by nimi";
				$vares = mysql_query($query) or pupe_error($query);

				while ($varow = mysql_fetch_array($vares)) {
					$sel='';
					if ($varow['tunnus']==$krow["toimipaikka"]) $sel = 'selected';
					echo "<option value='$varow[tunnus]' $sel>$varow[ovtlisa] $varow[nimi]</option>";
				}

				echo "</select></td></tr>";
			
				//	Jos vain valitut henkilöt saa jyvitellä hintoja näytetään tämän valinta
				if($yhtiorow["salli_jyvitys_myynnissa"] == "V") {
					
					if ($krow['jyvitys'] == "") {
						$sel1 = "SELECTED";
						$sel2 = "";
					}
					else {
						$sel1 = "";
						$sel2 = "SELECTED";
					}
					
					echo "<tr><th align='left'>".t("Jyvitys").":</td>";
					echo "<td><select name='jyvitys'>
							<option value='' $sel1>".t("Ei saa jyvittää myyntitilauksella")."</option>
							<option value='X' $sel2>".t("Saa jyvittää myyntitilauksella")."</option>";
					echo "</select></td></tr>";					
				}
			}
			$andextra = "";

			if ($krow['extranet'] == "") {
				$sel1 = "SELECTED";
				$sel2 = "";
			}
			if ($krow['extranet'] != "" or $toim == "extranet") {
				$sel2 = "SELECTED";
				$sel1 = "";
				$andextra = " and profiili like 'extranet%' ";
			}

			// oNko tämä extranetkäyttäjä
			if ($toim == "extranet") {
				echo "<input type='hidden' name='extranet' value='X'>";
			}
			else {
				echo "<input type='hidden' name='extranet' value=''>";
			}


			$query = "	SELECT distinct profiili
						FROM oikeu
						WHERE yhtio='$kukarow[yhtio]' and profiili!='' $andextra
						ORDER BY profiili";
			$pres = mysql_query($query) or pupe_error($query);

			$profiilit = explode(',', $krow["profiilit"]);

			while ($prow = mysql_fetch_array($pres)) {

				$chk = "";

				if (count($profiilit) > 0) {
					foreach($profiilit as $prof) {
						if ($prow["profiili"] == $prof) {
							$chk = "CHECKED";
						}
					}
				}

				echo "<tr><th>Profiili:</th><td><input type='checkbox' name='profiili[]' value='$prow[profiili]' $chk> $prow[profiili]</td></tr>";
			}


			if ($selkuka == "UUSI" and $toim != "extranet") {
				$query = "select yhtio, nimi from yhtio order by yhtio";
				$yhres = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($yhres) > 1) {
					echo "<tr><th align='left'>".t("Yhtio").":</th><td><select name='selyhtio'>";

					while ($yhrow = mysql_fetch_array ($yhres)) {
						$sel = "";

						if ($yhtiorow["yhtio"] == $yhrow["yhtio"]) {
							$sel = "SELECTED";
						}

						echo "<option value='$yhrow[yhtio]' $sel>$yhrow[nimi]";
					}

					echo "</select></td></tr>";
				}
			}
			else {
				echo "<input type='hidden' name='selyhtio' value='$kukarow[yhtio]'>";
			}

			echo "</table>";

			if ($selkuka == "UUSI") {
				echo "<br><input type='submit' value='".t("Perusta uusi käyttäjä")."'></form>";
			}
			else {
				echo "<br><input type='submit' value='".t("Päivitä käyttäjän")." $krow[kuka] ".t("tiedot")."'></form>";
				echo "</td></tr></table>";

				echo "<hr>";

				echo "<table><tr><td class='back'>";

				echo "<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='selkuka' value='$selkukarow[kuka]'>
					<input type='hidden' name='tee' value='delpsw'>
					<input type='submit' value='".t("Poista käyttäjän")." $selkukarow[nimi] ".t("salasana")."'>
					</form>";

				echo "</td><td class='back'>";

				echo "<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='selkuka' value='$selkukarow[kuka]'>
					<input type='hidden' name='tee' value='deluser'>
					<input type='submit' value='*** ".t("Poista käyttäjä")." $selkukarow[nimi] ***'>
					</form>";

				echo "</td></tr></table>";
			}
			exit;
		}
	}


	if ($tee == "") {

		echo "<br><table>
				<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='MUUTA'>
				<tr>
					<th>".t("Valitse")." ".$toim." ".t("käyttäjä").":</th>
					<td><select name='selkuka'>";

		if ($toim == "extranet") $extrsel = "X";
		else $extrsel = "";

		$query = "	SELECT distinct(nimi), kuka, tunnus
					FROM kuka
					WHERE yhtio='$kukarow[yhtio]' and extranet='$extrsel'
					ORDER BY nimi";
		$kukares = mysql_query($query) or pupe_error($query);

		while ($kurow=mysql_fetch_array($kukares)) {
			if ($selkukarow["tunnus"] == $kurow["tunnus"]) $sel = "selected";
			else $sel = "";
			echo "<option value='$kurow[tunnus]' $sel>$kurow[nimi] ($kurow[kuka])</option>";
		}

		echo "</select></td><td><input type='submit' value='".t("Muokkaa käyttäjän tietoja")."'></td></form>";


		echo "<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='MUUTA'>
				<input type='hidden' name='selkuka' value='UUSI'>";
		echo "<tr><th>".t("Perusta uusi käyttäjä").":</th><td></td><td><input type='submit' value='".t("Luo uusi käyttäjä")."'></td></tr>";


		echo "</table>";
	}

	require("inc/footer.inc");

?>