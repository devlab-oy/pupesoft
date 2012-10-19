<?php

	require ("inc/parametrit.inc");

	if (isset($livesearch_tee) and $livesearch_tee == "KAYTTAJAHAKU") {
		livesearch_kayttajahaku($toim);
		exit;
	}

	enable_ajax();

	echo "<font class='head'>";

	if (!isset($toim)) 				$toim = "";
	if (!isset($tee)) 				$tee = "";
	if (!isset($ytunnus_oa)) 		$ytunnus_oa = "";
	if (!isset($ytunnus_oat)) 		$ytunnus_oat = "";
	if (!isset($firname)) 			$firname = "";
	if (!isset($generoitupass)) 	$generoitupass = "";
	if (!isset($kumpi)) 			$kumpi = "";
	if (!isset($oletus_asiakas)) 	$oletus_asiakas = "";
	if (!isset($oletus_profiili))	$oletus_profiili = '';
	if (!isset($oletus_asiakastiedot)) $oletus_asiakastiedot = '';
	if (!isset($myyja))				$myyja = "";

	if (isset($toim) and $toim == 'extranet') {
		echo "Extranet-";
	}

	echo t("Käyttäjähallinta"),":</font><hr>";

	// tää on tällänän kikka.. älkää seotko.. en jaksa pyörittää toimia joka formista vaikka pitäs..
	if (isset($toim)) $PHP_SELF = $PHP_SELF."?toim={$toim}";

	if (isset($generatepass) and $generatepass != "") {
		$generoitupass = trim(shell_exec("openssl rand -base64 12"));
		$tee = "MUUTA";
		$firname = "";
	}

	if (isset($muutparametrit)) {
		list ($tee, $selkuka, $kumpi, $yoaid) = explode("#", $muutparametrit);

		if ($kumpi == 1) {
			$ytunnus_oa_id = $asiakasid;
			$oletus_asiakastiedot = $yoaid;
		}
		else {
			$oletus_asiakas = $yoaid;
			$ytunnus_oat_id = $asiakasid;
		}

		$ytunnus_oa = "";
		$ytunnus_oat = "";
	}

	if ($tee == "MUUTA" and $ytunnus_oa != "" and $ytunnus_oa != '0') {
		$muutparametrit = "MUUTA#{$selkuka}#1#{$oletus_asiakastiedot}";
		$ytunnus 		= $ytunnus_oa;
		$asiakasid 		= "";
		$ytunnus_oat  	= "";

		require ("inc/asiakashaku.inc");

		if ($monta == 1) {
			$ytunnus_oa_id	= $asiakasid;
			$asiakasid 		= "";
			$tee			= "MUUTA";
			$firname		= "";
			$kumpi			= 1;
			$ytunnus_oa		= $ytunnus;
		}
		else {
			$tee = "eimitään";
		}
	}
	elseif (isset($ytunnus_oa) and $ytunnus_oa == '0') {
		// Nollalla saa poistettua aletus_asiakkaan
		$krow["oletus_asiakas"] = "";
		$oletus_asiakas = "";
		$tee = "MUUTA";
		$firname = "";

		$query = "UPDATE kuka SET oletus_asiakas = '' WHERE tunnus = '{$selkuka}'";
		$result = pupe_query($query);
	}

	if ($tee == "MUUTA" and $ytunnus_oat != "" and $ytunnus_oat != '0') {
		$muutparametrit = "MUUTA#{$selkuka}#2#{$oletus_asiakas}";
		$ytunnus 		= $ytunnus_oat;
		$asiakasid 		= "";
		$ytunnus_oa  	= "";

		require ("inc/asiakashaku.inc");

		if ($monta == 1) {
			$ytunnus_oat_id	= $asiakasid;
			$asiakasid 		= "";
			$tee			= "MUUTA";
			$firname		= "";
			$kumpi			= 2;
			$ytunnus_oat	= $ytunnus;
		}
		else {
			$tee = "eimitään";
		}
	}
	elseif (isset($ytunnus_oat) and $ytunnus_oat == '0') {
		// Nollalla saa poistettua aletus_asiakkaan
		$krow["oletus_asiakastiedot"] = "";
		$oletus_asiakastiedot = "";
		$tee = "MUUTA";
		$firname = "";

		$query = "UPDATE kuka SET oletus_asiakastiedot = '' WHERE tunnus = '{$selkuka}'";
		$result = pupe_query($query);
	}

	// Poistetaan koko käyttäjä tältä yriykseltä!!
	if ($tee == 'deluser') {
		$query = "DELETE from kuka WHERE kuka = '{$selkuka}' and yhtio = '{$kukarow['yhtio']}'";
		$result = pupe_query($query);

		$query = "DELETE from oikeu WHERE kuka = '{$selkuka}' and kuka != '' and profiili = '' and yhtio = '{$kukarow['yhtio']}'";
		$result = pupe_query($query);

		echo "<b>",t("Käyttäjä")," {$selkuka} ",t("poistettu"),"!</b><br>";

		$selkuka = $kukarow['tunnus'];
		$tee = "";
	}

	if ($tee == 'delkesken') {
		$query = "UPDATE kuka SET kesken = 0 WHERE kuka = '{$selkuka}' and yhtio = '{$kukarow['yhtio']}'";
		$result = pupe_query($query);

		echo "<b>",t("Käyttäjän")," {$selkuka} ",t("keskenoleva tilaus vapautettu"),"!</b><br>";

		$selkuka = $kukarow['tunnus'];
		$tee = "";
	}

	if ($tee == 'deloikeu') {
		$query = "	UPDATE kuka
					SET profiilit 	= '',
					muuttaja		= '{$kukarow['kuka']}',
					muutospvm		= now()
					WHERE kuka = '{$selkuka}'
					AND yhtio = '{$kukarow['yhtio']}'";
		$result = pupe_query($query);

		$query = "DELETE from oikeu WHERE kuka = '{$selkuka}' and kuka != '' and profiili = '' and yhtio = '{$kukarow['yhtio']}'";
		$result = pupe_query($query);

		echo "<b>",t("Käyttäjän")," {$selkuka} ",t("käyttöoikeudet")," ",t("poistettu"),"!</b><br>";

		$selkuka = $kukarow['tunnus'];
		$tee = "";
	}

	// Poistetaan käyttäjän salasana
	if ($tee == 'delpsw') {
		$query = "	UPDATE kuka
					SET salasana = '',
					muuttaja	 = '{$kukarow['kuka']}',
					muutospvm	 = now()
					WHERE kuka = '{$selkuka}'";
		$result = pupe_query($query);

		echo "<b>",t("Käyttäjän")," {$selkuka} ",t("salasana poistettu"),"!</b><br>";
		$selkuka = $kukarow['tunnus'];
	}

	// Otetaan valitut organisaatiosegementit
	if (isset($dynaaminenkukamaxsyvyys)) {
		$hierarkia = array();

		for ($muli = 1; $muli <= $dynaaminenkukamaxsyvyys; $muli++) {
			if (isset(${"mul_kuka".$muli}) and count(${"mul_kuka".$muli}) > 0) {
				$hierarkia[] = implode(",", ${"mul_kuka".$muli});
			}
		}

		$hierarkia = implode(",", $hierarkia);
	}
	else {
		$hierarkia = "";
	}

	if ($tee == "UUSI" or $tee == "MUUTA") {

		$myyja = (int) $myyja;

		if ($myyja != 0) {

			if (strlen($myyja) > 11) {
				echo "<font class='error'>",t("Myyjänumero enintään 5 merkkiä"),"</font><br>";
				$jatka = 1; // ei perusteta
				unset($submit_button);
			}
			else {
				$query = "	SELECT tunnus
							FROM kuka
							WHERE yhtio = '{$kukarow["yhtio"]}'
							AND myyja = $myyja
							AND kuka != '{$kuka}'";
				$resmyyja = pupe_query($query);

				if (mysql_num_rows($resmyyja) > 0) {
					echo "<font class='error'>",t("Myyjänumero on jo käytössä"),": $myyja.</font><br><br>";
					$jatka = 1; // ei perusteta
					unset($submit_button);
				}
			}
		}
	}

	// Perustetaan uusi käyttäjä
	if ($tee == 'UUSI') {

		$yhtio = $kukarow['yhtio'];

		$query   = "SELECT * FROM kuka WHERE kuka = '{$ktunnus}' and yhtio <> '{$yhtio}'";
		$reskuka = pupe_query($query);

		$query   = "SELECT * FROM kuka WHERE kuka = '{$ktunnus}' and yhtio = '{$yhtio}'";
		$resyh   = pupe_query($query);

		if (mysql_num_rows($resyh) > 0) {
			$monta = mysql_fetch_array($resyh);
			echo "<font class='error'>",t("Käyttäjä")," {$monta['kuka']} ({$monta['nimi']}) ",t("on jo yrityksessä")," {$yhtio}.</font><br>";
			$jatka = 1; // ei perusteta
		}

		$salasana = "";

		if (mysql_num_rows($reskuka) > 0 and $jatka != 1 and $kopsaakuka == "JOO") {
			$monta = mysql_fetch_array($reskuka);

			$firname 						= $monta['nimi'];
			$ktunnus 						= $monta['kuka'];
			$phonenum 						= $monta['puhno'];
			$email 							= $monta['eposti'];
			$lang 							= $monta['kieli'];
			$ip								= $monta['ip'];
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
			$naytetaan_asiakashinta			= $monta['naytetaan_asiakashinta'];
			$naytetaan_tuotteet				= $monta['naytetaan_tuotteet'];
			$naytetaan_tilaukset			= $monta['naytetaan_tilaukset'];
			$profile 						= $monta['profiilit'];
			$piirit	 						= $monta['piirit'];
			$oletus_profiili				= $monta['oletus_profiili'];

			echo "<font class='message'>",t("Käyttäjä")," {$monta['kuka']} ({$monta['nimi']}) ",t("löytyi muista yrityksistä."),"<br>";
			echo t("Hänelle lisätään nyt myös oikeudet yritykselle")," {$yhtio}.<br>",t("Käyttäjätiedot kopioidaan yhtiöstä")," {$monta['yhtio']}.</font><br>";
		}

		if (strlen($ktunnus) > 0 and $jatka != 1) {

			if (count($profiili) > 0) {
				$profile = implode(",", $profiili);
			}

			if (count($piiri) > 0) {
				$piirit = implode(",", $piiri);
			}

			if (trim($password) != '') {
				$password = md5(trim($password));
			}

			if ($salasana == "" and trim($password) != '') {
				$salasana = $password; // jos meillä ei ole kopioitua salasanaa toisesta yrityksestä, käytetään syötettyä
			}

			if (count($varasto) > 0) {
				$varasto = implode(",", $varasto);
			}

			if (count($keraysvyohyke) > 0) {
				if (count($keraysvyohyke) == 1 and $keraysvyohyke[0] == 'default') {
					unset($keraysvyohyke);
					$keraysvyohyke = '';
				}
				else {
					unset($keraysvyohyke[0]);
					$keraysvyohyke = implode(",", $keraysvyohyke);
				}
			}

			$oletus_profiili = mysql_real_escape_string(trim($oletus_profiili));

			$query = "	INSERT into kuka
						SET nimi 						= '{$firname}',
						kuka 							= '{$ktunnus}',
						puhno 							= '{$phonenum}',
						eposti 							= '{$email}',
						kieli 							= '{$lang}',
						ip								= '{$ip}',
						taso 							= '{$taso}',
						hinnat							= '{$hinta}',
						saatavat						= '{$saatavat}',
						osasto							= '{$osasto}',
						salasana						= '{$salasana}',
						keraajanro 						= '{$keraajanro}',
						myyja 							= '{$myyja}',
						tuuraaja						= '{$tuuraaja}',
						varasto 						= '{$varasto}',
						oletus_varasto					= '{$oletus_varasto}',
						oletus_pakkaamo					= '{$oletus_pakkaamo}',
						kirjoitin 						= '{$kirjoitin}',
						kassamyyja 						= '{$kassamyyja}',
						dynaaminen_kassamyynti 			= '{$dynaaminen_kassamyynti}',
						jyvitys							= '{$jyvitys}',
						oletus_asiakas 					= '{$oletus_asiakas}',
						oletus_asiakastiedot 			= '{$oletus_asiakastiedot}',
						oletus_profiili					= '{$oletus_profiili}',
						oletus_ohjelma 					= '{$oletus_ohjelma}',
						resoluutio						= '{$resoluutio}',
						extranet						= '{$extranet}',
						hyvaksyja						= '{$hyvaksyja}',
						hyvaksyja_maksimisumma			= '{$hyvaksyja_maksimisumma}',
						hierarkia						= '{$hierarkia}',
						lomaoikeus						= '{$lomaoikeus}',
						asema							= '{$asema}',
						toimipaikka						= '{$toimipaikka}',
						mitatoi_tilauksia				= '{$mitatoi_tilauksia}',
						naytetaan_katteet_tilauksella 	= '{$naytetaan_katteet_tilauksella}',
						naytetaan_asiakashinta 			= '{$naytetaan_asiakashinta}',
						naytetaan_tuotteet				= '{$naytetaan_tuotteet}',
						naytetaan_tilaukset				= '{$naytetaan_tilaukset}',
						profiilit 						= '{$profile}',
						piirit							= '{$piirit}',
						fyysinen_sijainti				= '{$fyysinen_sijainti}',
						keraysvyohyke					= '{$keraysvyohyke}',
						laatija							= '{$kukarow['kuka']}',
						luontiaika						= now(),
						budjetti						= '{$budjetti}',
						yhtio 							= '{$yhtio}'";
			$result = pupe_query($query);
			$selkuka = mysql_insert_id();

			echo "<font class='message'>",t("Käyttäjä perustettu"),"! ({$selkuka})</font><br><br>";

			echo "<font class='error'>",t("Valitse nyt käyttäjän oletusasiakas"),"!</font><br><br>";

			if ($yhtio != $kukarow["yhtio"]) {
				$selkuka = "";
			}

			//päivitetään oikeudet jos profiileja on olemassa
			$profiilit = explode(',', trim($profile));

			//poistetaan käyttäjän vanhat profiilioikeudet
			$query = "	DELETE FROM oikeu
						WHERE yhtio = '{$yhtio}'
						AND kuka = '{$ktunnus}'
						AND kuka != ''
						AND profiili = ''
						AND lukittu = ''";
			$pres = pupe_query($query);

			if (count($profiilit) > 0 and $profiilit[0] !='') {

				//käydään uudestaan profiili läpi
				foreach($profiilit as $prof) {

					$query = "	SELECT *
								FROM oikeu
								WHERE yhtio = '{$yhtio}'
								AND kuka = '{$prof}'
								AND profiili = '{$prof}'";
					$pres = pupe_query($query);

					while ($trow = mysql_fetch_array($pres)) {
						//joudumme tarkistamaan ettei tätä oikeutta ole jo tällä käyttäjällä.
						//voi olla esim jos se on lukittuna annettu
						$query = "	SELECT yhtio, paivitys
									FROM oikeu
									WHERE yhtio		= '{$yhtio}'
									and kuka		= '{$ktunnus}'
									and sovellus	= '{$trow['sovellus']}'
									and nimi		= '{$trow['nimi']}'
									and alanimi 	= '{$trow['alanimi']}'";
						$tarkesult = pupe_query($query);
						$tarkesultrow = mysql_fetch_array($tarkesult);

						if (mysql_num_rows($tarkesult) == 0) {
							$query = "	INSERT into oikeu SET
										kuka		= '{$ktunnus}',
										sovellus	= '{$trow['sovellus']}',
										nimi		= '{$trow['nimi']}',
										alanimi 	= '{$trow['alanimi']}',
										paivitys	= '{$trow['paivitys']}',
										nimitys		= '{$trow['nimitys']}',
										jarjestys 	= '{$trow['jarjestys']}',
										jarjestys2	= '{$trow['jarjestys2']}',
										hidden		= '{$trow['hidden']}',
										yhtio		= '{$yhtio}'";
							$rresult = pupe_query($query);
						}
						elseif ($trow["paivitys"] == '1' and $tarkesultrow["paivitys"] != '1') {
							$query = "	UPDATE oikeu SET paivitys = '1'
										WHERE kuka		= '{$ktunnus}'
										AND sovellus	= '{$trow['sovellus']}'
										AND nimi		= '{$trow['nimi']}'
										AND alanimi 	= '{$trow['alanimi']}'
										AND nimitys		= '{$trow['nimitys']}'
										AND jarjestys 	= '{$trow['jarjestys']}'
										AND jarjestys2	= '{$trow['jarjestys2']}'
										AND hidden		= '{$trow['hidden']}'
										AND yhtio		= '{$yhtio}'";
							$rresult = pupe_query($query);
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
			echo "<font class='error'>",t("Uutta käyttäjää ei luotu"),"!</font><br><br>";
			$tee = "MUUTA";

			if ($kopsaakuka == "JOO") {
				$selkuka = "KOPSAAUUSI";
			}
			else {
				$selkuka = "UUSI";
			}

			$firname = "";
		}
	}

	// Muutetaanko jonkun muun oikeuksia??
	if (isset($selkuka) and $selkuka != '') {
		$query = "SELECT * FROM kuka WHERE tunnus = '{$selkuka}'";
	}
	else {
		$query = "SELECT * FROM kuka WHERE tunnus = '{$kukarow['tunnus']}'";
	}

	$result = pupe_query($query);
	$selkukarow = mysql_fetch_array($result);

	//muutetaan kayttajan tietoja tai syotetaan uuden kayttajan tiedot
	if ($tee == 'MUUTA') {

		$yhtio = $kukarow['yhtio'];

		if (strlen($firname) > 0 and isset($submit_button)) {

			if (count($profiili) > 0) {
				$profile = implode(",", $profiili);
			}

			if (count($piiri) > 0) {
				$piirit = implode(",", $piiri);
			}

			//päivitetään salasana
			if (trim($password) != '') {
				$password = md5(trim($password));

				$query = "	UPDATE kuka
							SET salasana = '{$password}',
							api_key		 = md5(concat(yhtio, kuka, '{$password}')),
							muuttaja	 = '{$kukarow['kuka']}',
							muutospvm	 = now()
							WHERE kuka = '{$kuka}'";
				$result = pupe_query($query);
			}

			if (count($varasto) > 0) {
				$varasto = implode(",", $varasto);
			}

			if (count($keraysvyohyke) > 0) {
				if (count($keraysvyohyke) == 1 and $keraysvyohyke[0] == 'default') {
					unset($keraysvyohyke);
					$keraysvyohyke = '';
				}
				else {
					unset($keraysvyohyke[0]);
					$keraysvyohyke = implode(",", $keraysvyohyke);
				}
			}

			$query = "	UPDATE kuka
						SET nimi 						= '{$firname}',
						puhno 							= '{$phonenum}',
						eposti 							= '{$email}',
						kieli 							= '{$lang}',
						ip								= '{$ip}',
						taso 							= '{$taso}',
						hinnat							= '{$hinnat}',
						saatavat						= '{$saatavat}',
						keraajanro 						= '{$keraajanro}',
						myyja 							= '{$myyja}',
						tuuraaja						= '{$tuuraaja}',
						osasto							= '{$osasto}',
						varasto 						= '{$varasto}',
						oletus_varasto 					= '{$oletus_varasto}',
						oletus_pakkaamo					= '{$oletus_pakkaamo}',
						kirjoitin 						= '{$kirjoitin}',
						oletus_asiakas 					= '{$oletus_asiakas}',
						oletus_asiakastiedot 			= '{$oletus_asiakastiedot}',
						oletus_profiili					= '{$oletus_profiili}',
						resoluutio 						= '{$resoluutio}',
						extranet						= '{$extranet}',
						hyvaksyja						= '{$hyvaksyja}',
						hyvaksyja_maksimisumma			= '{$hyvaksyja_maksimisumma}',
						hierarkia						= '{$hierarkia}',
						lomaoikeus						= '{$lomaoikeus}',
						asema							= '{$asema}',
						toimipaikka						= '{$toimipaikka}',
						mitatoi_tilauksia				= '{$mitatoi_tilauksia}',
						kassamyyja 						= '{$kassamyyja}',
						dynaaminen_kassamyynti			= '{$dynaaminen_kassamyynti}',
						jyvitys							= '{$jyvitys}',
						oletus_ohjelma 					= '{$oletus_ohjelma}',
						naytetaan_katteet_tilauksella 	= '{$naytetaan_katteet_tilauksella}',
						naytetaan_asiakashinta 			= '{$naytetaan_asiakashinta}',
						naytetaan_tuotteet				= '{$naytetaan_tuotteet}',
						naytetaan_tilaukset				= '{$naytetaan_tilaukset}',
						profiilit 						= '{$profile}',
						piirit							= '{$piirit}',
						fyysinen_sijainti				= '{$fyysinen_sijainti}',
						keraysvyohyke					= '{$keraysvyohyke}',
						muuttaja						= '{$kukarow['kuka']}',
						muutospvm						= now(),
						budjetti						= '{$budjetti}'
						WHERE kuka	= '{$kuka}'
						AND yhtio	= '{$yhtio}'";
			$result = pupe_query($query);

			$query = "	SELECT nimi, kuka, tunnus
						FROM kuka
						WHERE tunnus = '{$selkuka}'";
			$result = pupe_query($query);
			$selkukarow = mysql_fetch_array($result);

			//päivitetään oikeudet jos profiileja on olemassa
			$profiilit = explode(',', trim($profile));

			//poistetaan käyttäjän vanhat profiilioikeudet
			$query = "	DELETE FROM oikeu
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND kuka = '{$kuka}'
						AND kuka != ''
						AND profiili = ''
						AND lukittu = ''";
			$pres = pupe_query($query);

			if (count($profiilit) > 0 and $profiilit[0] != '') {
				//käydään uudestaan profiili läpi
				foreach($profiilit as $prof) {
					$query = "	SELECT *
								FROM oikeu
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND kuka = '{$prof}'
								AND profiili = '{$prof}'";
					$pres = pupe_query($query);

					while ($trow = mysql_fetch_array($pres)) {
						//joudumme tarkistamaan ettei tätä oikeutta ole jo tällä käyttäjällä.
						//voi olla esim jos se on lukittuna annettu
						$query = "	SELECT yhtio, paivitys
									FROM oikeu
									WHERE yhtio		= '{$kukarow['yhtio']}'
									AND kuka		= '{$kuka}'
									AND sovellus	= '{$trow['sovellus']}'
									AND nimi		= '{$trow['nimi']}'
									AND alanimi 	= '{$trow['alanimi']}'";
						$tarkesult = pupe_query($query);
						$tarkesultrow = mysql_fetch_array($tarkesult);

						if (mysql_num_rows($tarkesult) == 0) {
							$query = "	INSERT into oikeu SET
										kuka		= '{$kuka}',
										sovellus	= '{$trow['sovellus']}',
										nimi		= '{$trow['nimi']}',
										alanimi 	= '{$trow['alanimi']}',
										paivitys	= '{$trow['paivitys']}',
										nimitys		= '{$trow['nimitys']}',
										jarjestys 	= '{$trow['jarjestys']}',
										jarjestys2	= '{$trow['jarjestys2']}',
										hidden		= '{$trow['hidden']}',
										yhtio		= '{$kukarow['yhtio']}'";
							$rresult = pupe_query($query);
						}
						elseif ($trow["paivitys"] == '1' and $tarkesultrow["paivitys"] != '1') {
							$query = "	UPDATE oikeu SET paivitys = '1'
										WHERE kuka		= '{$kuka}'
										AND sovellus	= '{$trow['sovellus']}'
										AND nimi		= '{$trow['nimi']}'
										AND alanimi 	= '{$trow['alanimi']}'
										AND nimitys		= '{$trow['nimitys']}'
										AND jarjestys 	= '{$trow['jarjestys']}'
										AND jarjestys2	= '{$trow['jarjestys2']}'
										AND hidden		= '{$trow['hidden']}'
										AND yhtio		= '{$kukarow['yhtio']}'";
							$rresult = pupe_query($query);
						}
					}
				}
			}

			$tee = "";
		}
		else {
			//tama siis vain jos muutetaan jonkun tietoja
			if ($selkuka != "UUSI" and $selkuka != "KOPSAAUUSI") {

				$query = "SELECT * FROM kuka";

				if ($selkukarow['kuka'] != '') {
					$query .= " WHERE kuka = '{$selkukarow['kuka']}' and yhtio = '{$yhtio}'";
				}
				else {
					$profiilit = $profile;
					$query .= " WHERE session = '{$session}' and yhtio = '{$yhtio}'";
				}

				$result = pupe_query($query);
				$krow = mysql_fetch_array ($result);

				if (mysql_num_rows($result) != 1) {
					echo t("VIRHE: Hakkerointia!");
					exit;
				}
			}

			echo "<form method='post' autocomplete='off'>";

			if ($toim == 'extranet') {
				echo "<table>";
				echo "<tr><td class='back'>";
			}

			echo "<table>";

			if ($selkuka != "UUSI" and $selkuka != "KOPSAAUUSI") {
				echo "	<input type='hidden' name='tee' value='MUUTA'>
						<input type='hidden' name='selkuka' value='{$selkukarow['tunnus']}'>
						<input type='hidden' name='kuka' value='{$selkukarow['kuka']}'>";
				echo "<tr><th align='left'>",t("Käyttäjätunnus"),":</th><td><b>{$krow['kuka']}</b> ",t("Lastlogin"),": {$krow['lastlogin']}</td></tr>";
			}
			else {

				if ($selkuka == "KOPSAAUUSI") {
					echo "<input type='hidden' name='kopsaakuka' value='JOO'>";
				}

				echo "<input type='hidden' name='tee' value='UUSI'>";
				echo "<tr><th align='left'>",t("Käyttäjätunnus"),":</th>
				<td><input type='text' size='50' maxlength='10' name='ktunnus'></td></tr>";
			}

			if ($selkuka != "KOPSAAUUSI") {

				echo "<tr><th align='left'>",t("Salasana"),":</th><td><input type='text' size='50' maxlength='30' name='password' value='{$generoitupass}'></td><td class='back'> <a href='?generatepass=y&selkuka={$selkuka}&toim={$toim}'>",t("Generoi salasana"),"</a></td></tr>";
				echo "<tr><th align='left'>",t("Nimi"),":</th><td><input type='text' size='50' value='{$krow['nimi']}' maxlength='30' name='firname'></td></tr>";
				echo "<tr><th align='left'>",t("Puhelinnumero"),":</th><td><input type='text' size='50' value='{$krow['puhno']}' maxlength='30' name='phonenum'></td></tr>";
				echo "<tr><th align='left'>",t("Sähköposti"),":&nbsp;</th><td><input type='text' size='50' value='{$krow['eposti']}' maxlength='50' name='email'></td></tr>";
				if ($toim == 'extranet') echo "<tr><th align='left'>",t("IP"),":&nbsp;</th><td><input type='text' size='16' value='{$krow['ip']}' maxlength='15' name='ip'></td></tr>";
				echo "<tr><th align='left'>",t("Kieli"),":&nbsp;</th><td><select name='lang'>";

				$query  = "show columns from sanakirja";
				$fields =  mysql_query($query);

				while ($apurow = mysql_fetch_array($fields)) {
					if (strlen($apurow[0]) == 2) {
						$sel = "";
						if ($krow["kieli"] == $apurow[0] or ($krow["kieli"] == "" and $apurow[0] == $yhtiorow["kieli"])) {
							$sel = "selected";
						}
						if ($apurow[0] != "tunnus") {
							$query = "SELECT distinct nimi from maat where koodi = '{$apurow[0]}'";
							$maare = pupe_query($query);
							$maaro = mysql_fetch_array($maare);
							$maa   = strtolower($maaro["nimi"]);
							if ($maa=="") $maa = $apurow[0];
							echo "<option value='{$apurow[0]}' {$sel}>",t($maa),"</option>";
						}
					}
				}
				echo "</select></td></tr>";

				if ($toim != 'extranet') {

					echo "<tr><th align='left'>",t("Hyväksyjä"),":</td>";

					if ($krow["hyvaksyja"] != '') {
						$chk = "CHECKED";
					}
					else {
						$chk = "";
					}
					echo "<td><input type='checkbox' name='hyvaksyja' {$chk}></td></tr>";

					$sel9 = $sel3 = $sel2 = $sel1 = "";

					if ($krow["taso"] == "1") {
						$sel1 = "SELECTED";
					}
					if ($krow["taso"] == "2") {
						$sel2 = "SELECTED";
					}
					if ($krow["taso"] == "3") {
						$sel3 = "SELECTED";
					}
					if ($krow["taso"] == "9") {
						$sel9 = "SELECTED";
					}

					echo "<tr><th align='left'>",t("Hyväksyjätaso"),":</th>";
					echo "<td><select name='taso'>";
					echo "<option value='9' {$sel9}>",t("Aloittelijahyväksyjä, tiliöintejä ei näytetä"),"</option>";
					echo "<option value='1' {$sel1}>",t("Perushyväksyjä, tiliöntejä ei voi muuttaa"),"</option>";
					echo "<option value='2' {$sel2}>",t("Tehohyväksyjä, tiliöntejä voi muuttaa"),"</option>";
					echo "<option value='3' {$sel3}>",t("Tehohyväksyjä, lukittujakin tiliöintejä voi muuttaa"),"</option>";
					echo "</select></td></tr>";

					if (!isset($krow['hyvaksyja_maksimisumma'])) $krow['hyvaksyja_maksimisumma'] = 0;

					echo "<tr><th align='left'>",t("Hyväksyjän maksimisumma"),":</th>";
					echo "<td><input type='text' name='hyvaksyja_maksimisumma' value='{$krow['hyvaksyja_maksimisumma']}'></td></tr>";

					echo "<tr><th align='left'>",t("Sijainen laskujen hyväksynnässä"),":</th><td>";

					// Haetaan mahdolliset tuuraajat
					$query = "	SELECT kuka, nimi
								FROM kuka
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND hyvaksyja != ''
								AND extranet = ''
								ORDER BY nimi";
					$hyvaksyja_result = pupe_query($query);

					echo "<select name = 'tuuraaja'>";
					echo "<option value=''>",t("Ei sijaista"),"</option>";

					while ($hyvaksyja_row = mysql_fetch_assoc($hyvaksyja_result)) {
						$sel = ($krow["tuuraaja"] == $hyvaksyja_row['kuka']) ? ' selected' : '';
						echo "<option value='{$hyvaksyja_row['kuka']}'{$sel}>{$hyvaksyja_row['nimi']}</option>";
					}

					echo "</select>";
					echo "</td></tr>";

					echo "<tr><th align='left'>",t("Hierarkia ja esimiehet"),":</th><td>";

					$monivalintalaatikot = array('DYNAAMINEN_KUKA');
					$monivalintalaatikot_normaali = array();

					if (!isset($dynaaminenkukamaxsyvyys) and isset($krow['hierarkia']) and trim($krow['hierarkia']) != '') {
						$monivalintarajaus_dynaaminen = $krow['hierarkia'];
					}

					require ("tilauskasittely/monivalintalaatikot.inc");

					echo "</td></tr>";

					$avainsana_result = t_avainsana('TERMINAALIALUE', '', " and selite != '' ");

					if (mysql_num_rows($avainsana_result) > 0) {
						echo "<tr><th align='left'>",t("Käyttäjän fyysinen sijainti"),":</th>";
						echo "<td><select name='fyysinen_sijainti'><option value=''>",t("Ei sijaintia"),"</option>";

						while ($terminaalialue_row = mysql_fetch_assoc($avainsana_result)) {

							$sel = $krow['fyysinen_sijainti'] == $terminaalialue_row['selite'] ? ' selected' : '';

							echo "<option value='{$terminaalialue_row['selite']}'{$sel}>{$terminaalialue_row['selite']}</option>";
						}

						echo "</td></tr>";
					}

					$query = "SELECT tunnus, nimitys FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
					$keraysvyohyke_result = pupe_query($query);

					if (mysql_num_rows($keraysvyohyke_result) > 0) {

						echo "<tr><th align='left'>",t("Keräysvyöhyke"),":</th><td>";

						echo "<input type='hidden' name='keraysvyohyke[]' value='default' />";

						while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result)) {

							$checked = in_array($keraysvyohyke_row['tunnus'], explode(",", $krow['keraysvyohyke'])) ? ' checked' : '';

							echo "<input type='checkbox' name='keraysvyohyke[]' value='{$keraysvyohyke_row['tunnus']}'{$checked}>{$keraysvyohyke_row['nimitys']}<br />";
						}

						echo "</td></tr>";
					}

				}
				else {

					$sel = array_fill_keys(array($krow['taso']), " selected") + array_fill_keys(array('1','2','3','4','9'), '');

					echo "<tr><th align='left'>",t("Taso"),":</th>";

					echo "<td><select name='taso'>";
					echo "<option value='1' {$sel[1]}>",t("Tehotilaaja, tilaukset menee suoraan tomitukseen"),"</option>";
					echo "<option value='2' {$sel[2]}>",t("Aloittelijatilaaja, tilaukset hyväksytetään ennen toimitusta"),"</option>";
					echo "<option value='3' {$sel[3]}>",t("Tehotilaaja, tilaukset menee suoraan toimitukseen MAISTA RIIPPUMATTA"),"</option>";
					echo "<option value='4' {$sel[4]}>",t("Aloittelijatilaaja, tilauksia ei saa laittaa valmiiksi"),"</option>";

					if ($kukarow['yhtio'] == 'artr') {
						echo "<option value='9' {$sel[9]}>",t("Tehotilaaja, hyväksytyt työmääräykset tilataan automaattisesti"),"</option>";
					}
					echo "</select></td></tr>";
				}

				$sel0 = $sel1 = $sel2 = "";

				if (!isset($krow["hinnat"]) and $toim == 'extranet') {
					$sel1 = "SELECTED";
				}
				if ($krow["hinnat"] == 0) {
					$sel0 = "SELECTED";
				}
				if ($krow["hinnat"] == 1) {
					$sel1 = "SELECTED";
				}
				if ($krow["hinnat"] == -1) {
					$sel2 = "SELECTED";
				}

				echo "<tr><th align='left'>",t("Hinnat"),":</th>";

				echo "<td><select name='hinnat'>";
				echo "<option value='0'  {$sel0}>",t("Normaali"),"</option>";
				echo "<option value='1'  {$sel1}>",t("Näytetään vain tuotteen myyntihinta"),"</option>";
				echo "<option value='-1' {$sel2}>",t("Hintoja ei näytetä"),"</option>";
				echo "</select></td></tr>";

				if ($toim == 'extranet') {
					$sel0 = $sel1 = $sel2 = $sel3 = "";

					if (!isset($krow["saatavat"])) {
						$sel2 = "SELECTED";
					}

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

					echo "<tr><th align='left'>",t("Saatavat"),":</th>";

					echo "<td><select name='saatavat'>";
					echo "<option value='0' {$sel0}>",t("Näytetään saatavat, jätetään kesken jos maksamattomia laskuja"),"</option>";
					echo "<option value='1' {$sel1}>",t("Näytetään saatavat, siirretään aina tulostusjonoon"),"</option>";
					echo "<option value='2' {$sel2}>",t("Ei naytetä saatavia, jätetään kesken jos maksamattomia laskuja"),"</option>";
					echo "<option value='3' {$sel3}>",t("Ei naytetä saatavia, siirretään aina tulostusjonoon"),"</option>";
					echo "</select></td></tr>";
				}

				if ($toim != 'extranet') {
					echo "<tr><th align='left'>",t("Myyjänro"),":</th><td><input type='text' name='myyja' value='{$krow['myyja']}' size='8'></td></tr>";
					echo "<tr><th align='left'>",t("Kerääjänro"),":</th><td><input type='text' name='keraajanro' value='{$krow['keraajanro']}' size='5'></td></tr>";

					echo "<tr><th align='left'>",t("Osasto"),":</td>";
					echo "<td><select name='osasto'><option value=''>",t("Ei osastoa"),"</option>";

					$vares = t_avainsana("HENKILO_OSASTO");

					while ($varow = mysql_fetch_array($vares)) {
						$sel = '';
						if ($varow['selite'] == $krow["osasto"]) $sel = 'selected';
						echo "<option value='{$varow['selite']}' {$sel}>{$varow['selitetark']}</option>";
					}

					echo "</select></td></tr>";
				}

				echo "<tr><th align='left'>",t("Valitse käyttäjän oletusvarasto"),":</td>";
				echo "<td>";

				$query  = "	SELECT *
							FROM varastopaikat
							WHERE yhtio = '{$kukarow['yhtio']}'
							ORDER BY tyyppi, nimitys";
				$vares = pupe_query($query);

				echo "<select name='oletus_varasto'>";
				echo "<option value='0'>",t("Oletusvarasto"),"</option>";
				while ($varow = mysql_fetch_array($vares)) {
					$sel = '';
					if ($varow['tunnus'] == $krow['oletus_varasto']) {
						$sel = 'selected';
					}
					echo "<option value='{$varow['tunnus']}' {$sel}>{$varow['nimitys']}</option>";
				}
				echo "</select>";

				echo "<tr><th align='left'>",t("Valitse varastot, joista käyttäjä saa myydä"),":</td>";
				echo "<td>";

				$query  = "	SELECT *
							FROM varastopaikat
							WHERE yhtio = '{$kukarow['yhtio']}'
							ORDER BY tyyppi, nimitys";
				$vares = pupe_query($query);

				$varastot_array = explode(",", $krow["varasto"]);

				while ($varow = mysql_fetch_array($vares)) {
					$sel = $eri = '';
					if (in_array($varow['tunnus'], $varastot_array)) $sel = 'CHECKED';
					if ($varow["tyyppi"] == "E") $eri = "(E)";
					echo "<input type='checkbox' name='varasto[]' value='{$varow['tunnus']}' {$sel}> {$varow['nimitys']} {$eri}<br>";
				}

				echo "</td><td class='back'>",t("Ilman rajausta saa myydä kaikista normaalivarastoista"),"</td></tr>";

				if ($toim != 'extranet' and $yhtiorow['pakkaamolokerot'] != '') {
					echo "<tr><th align='left'>",t("Oletus pakkaamo"),":</td>";
					echo "<td><select name='oletus_pakkaamo'><option value=''>",t("Ei pakkaamoa"),"</option>";

					$query  = "SELECT DISTINCT nimi FROM pakkaamo WHERE yhtio = '{$kukarow['yhtio']}'";
					$pakkaamores = pupe_query($query);

					while ($pakkaamorow = mysql_fetch_array($pakkaamores)) {
						$sel = '';
						if ($pakkaamorow['nimi'] == $krow["oletus_pakkaamo"]) $sel = 'selected';
						echo "<option value='{$pakkaamorow['nimi']}' {$sel}>{$pakkaamorow['nimi']}</option>";
					}

					echo "</select></td></tr>";
				}


				if ($toim != 'extranet') {
					echo "<tr><th align='left'>",t("Henkilökohtainen tulostin:"),"</td>";
					echo "<td><select name='kirjoitin'><option value=''>",t("Ei oletuskirjoitinta"),"</option>";

					$query  = "SELECT tunnus, kirjoitin FROM kirjoittimet WHERE yhtio = '{$kukarow['yhtio']}'";
					$vares = pupe_query($query);

					while ($varow = mysql_fetch_array($vares)) {
						$sel = '';
						if ($varow['tunnus'] == $krow["kirjoitin"]) $sel = 'selected';
						echo "<option value='{$varow['tunnus']}' {$sel}>{$varow['kirjoitin']}</option>";
					}

					echo "</select></td></tr>";

					echo "<tr><th align='left'>",t("Kassamyyjä / oletuskassalipas"),":</td>";
					echo "<td><select name='kassamyyja'><option value=''>",t("Ei oletuskassalipasta"),"</option>";

					$kassalipaslisa = $krow['toimipaikka'] != 0 ? "and (toimipaikka = 0 or toimipaikka = {$krow['toimipaikka']})" : "";

					$query = "SELECT * FROM kassalipas WHERE yhtio = '{$kukarow['yhtio']}' {$kassalipaslisa} ORDER BY nimi";
					$vares = pupe_query($query);

					while ($varow = mysql_fetch_array($vares)) {
						$sel = '';
						if ($varow['tunnus'] == $krow["kassamyyja"]) $sel = 'selected';
						echo "<option value='{$varow['tunnus']}' {$sel}>{$varow['nimi']}</option>";
					}

					echo "</select></td></tr>";

					echo "<tr><th align='left'>",t("Kassamyyjä / kassalippaan valinta tilauksella"),":</td>";

					$sel1 = "";
					$sel2 = "";

					if ($krow["dynaaminen_kassamyynti"] == "") {
						$sel1 = "selected";
					}
					else {
						$sel2 = "selected";
					}
					echo "<td><select name='dynaaminen_kassamyynti'>";
					echo "<option value='' {$sel1}>",t("Kassalipasta ei voi valita tilauksella"),"</option>";
					echo "<option value='o' {$sel2}>",t("Kassalippaan voi valita tilauksella"),"</option>";
					echo "</select></td>";

					$sel0 = $sel1 = "";

					if ($krow["saatavat"] == "0") {
						$sel0 = "SELECTED";
					}
					if ($krow["saatavat"] == "1") {
						$sel1 = "SELECTED";
					}

					echo "<tr><th align='left'>",t("Saatavat"),":</th>";

					echo "<td><select name='saatavat'>";
					echo "<option value='0' {$sel0}>",t("Ei näytetä saatavia kassakäyttäjänä"),"</option>";
					echo "<option value='1' {$sel1}>",t("Näytetään saatavat kassakäyttäjänä"),"</option>";
					echo "</select></td></tr>";
				}
				else {
					echo "<tr><th align='left'>",t("Tilaukset suoraan laskutukseen"),":</td>";

					if ($krow["hyvaksyja"] != '') {
						$chk = "CHECKED";
					}
					else {
						$chk = "";
					}
					echo "<td><input type='checkbox' name='hyvaksyja' {$chk}></td></tr>";
				}

				if ($selkuka != "UUSI") {

					echo "<tr><th align='left'>",t("Oletusasiakas"),":</th><td>";
					echo "<table><tr>";
					echo "<td><input type='text' name='ytunnus_oa'></td>";

					if ($kumpi == "1" and $ytunnus_oa_id != "") $krow["oletus_asiakas"] = $ytunnus_oa_id;
					elseif ($oletus_asiakas != '') $krow["oletus_asiakas"] = $oletus_asiakas;

					if ($krow["oletus_asiakas"] != "") {

						$query = "SELECT * FROM asiakas WHERE tunnus = '{$krow['oletus_asiakas']}'";
						$vares = pupe_query($query);

						if (mysql_num_rows($vares) == 1) {
							$varow = mysql_fetch_array($vares);

							echo "<td>{$varow['ytunnus']} {$varow['nimi']} {$varow['nimitark']}<br>{$varow['toim_ovttunnus']} {$varow['toim_nimi']} {$varow['toim_nimitark']} {$varow['toim_postitp']}
									<input type='hidden' name='oletus_asiakas' value='{$krow['oletus_asiakas']}'></td>";
						}
						else {
							echo "<td>",t("Asiakas ei löydy"),"!</td>";
						}

					}
					else {
						echo "<td>",t("Oletusasiakasta ei ole syötetty"),"!</td>";
					}

					echo "</tr></table>";
					echo "</td></tr>";

					if ($toim == 'extranet') {
						echo "<tr><th align='left'>",t("Oletusasiakastiedot"),":</th><td>";
						echo "<table><tr>";
						echo "<td><input type='text' name='ytunnus_oat'></td>";

						if ($kumpi == "2" and $ytunnus_oat_id != "") $krow["oletus_asiakastiedot"] = $ytunnus_oat_id;
						elseif ($oletus_asiakastiedot != '') $krow["oletus_asiakastiedot"] = $oletus_asiakastiedot;

						if ($krow["oletus_asiakastiedot"] != "") {

							$query = "SELECT * FROM asiakas WHERE tunnus = '{$krow['oletus_asiakastiedot']}'";
							$vares = pupe_query($query);

							if (mysql_num_rows($vares) == 1) {
								$varow = mysql_fetch_array($vares);

								echo "<td>{$varow['ytunnus']} {$varow['nimi']} {$varow['nimitark']}<br>{$varow['toim_ovttunnus']} {$varow['toim_nimi']} {$varow['toim_nimitark']} {$varow['toim_postitp']}
										<input type='hidden' name='oletus_asiakastiedot' value='{$krow['oletus_asiakastiedot']}'></td>";
							}
							else {
								echo "<td>",t("Asiakas ei löydy"),"!</td>";
							}
						}
						else {
							echo "<td>",t("Asiakastietoasiakasta ei ole syötetty"),"!</td>";
						}
						echo "</tr></table>";
						echo "</td></tr>";

						echo "<tr><th align='left'>",t("Oletusprofiili"),":</th><td>";
						echo "<table><tr><td><select name='oletus_profiili'>";

						$query = "	SELECT DISTINCT profiili
									FROM oikeu
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND profiili != ''
									AND profiili LIKE 'extranet%'
									ORDER BY profiili";
						$pres = pupe_query($query);

						echo "<option value=''></option>";

						while ($prow = mysql_fetch_assoc($pres)) {
							$sel = $krow['oletus_profiili'] == $prow['profiili'] ? ' selected' : '';
							echo "<option value='{$prow['profiili']}'{$sel}>{$prow['profiili']}</option>";
						}

						echo "</select></td></tr></table>";
						echo "</td></tr>";
					}
				}

				if ($toim == 'extranet') {
					$sel = array_fill_keys(array($krow["naytetaan_asiakashinta"]), " selected") + array('A' => '');

					echo "<tr><th align='left'>",t("Näytetään asiakashinta tuotehaussa"),":</th>
							<td><select name='naytetaan_asiakashinta'>
							<option value=''>",t("Näytetään tuotteen myyntihinta"),"</option>
							<option value='A' {$sel['A']}>",t("Näytetään asiakashinta"),"</option>
							</select></td></tr>";


					$sel = array_fill_keys(array($krow["naytetaan_tuotteet"]), " selected") + array('A' => '');

					echo "<tr><th align='left'>",t("Näytettävät tuotteet verkkokaupan tuotehaussa"),":</th>
							<td><select name='naytetaan_tuotteet'>
							<option value=''>",t("Näytetään kaikki tuotteet"),"</option>
							<option value='A' {$sel['A']}>",t("Näytetään tuotteet joilla on asiakashinta tai asiakasale"),"</option>
							</select></td></tr>";

					$sel = array_fill_keys(array($krow["naytetaan_tilaukset"]), " selected") + array('O' => '');

					echo "<tr><th align='left'>",t("Näytettävät tilaukset verkkokaupassa"),":</th>
							<td><select name='naytetaan_tilaukset'>
							<option value=''>",t("Näytetään kaikki tilaukset"),"</option>
							<option value='O' {$sel['O']}>",t("Näytetään vain omat tilaukset"),"</option>
							</select></td></tr>";

					$sel = array_fill_keys(array($krow["asema"]), " selected") + array('NE' => '');

					echo "<tr><th align='left'>",t("Näytetäänkö poistetut tuotteet extranetissä"),":</th>
							<td><select name='asema'>
							<option value=''>",t("Ei näytetä extranetissä"),"</option>
							<option value='NE' {$sel['NE']}>",t("Näytetään extranetissä"),"</option>
							</select></td></tr>";

				}

				if ($toim != 'extranet') {
					echo "<tr><th align='left'>",t("Oletusohjelma"),":</th><td><select name='oletus_ohjelma'>";
					echo "<option value=''>",t("Ei oletusta"),"</option>";

					$query  = "	SELECT DISTINCT concat_ws('##',sovellus,nimi,alanimi) nimi, nimitys, sovellus
								FROM oikeu
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND kuka = '{$krow['kuka']}'
								ORDER BY sovellus, nimitys";
					$vares = pupe_query($query);

					while ($varow = mysql_fetch_array($vares)) {
						$sel = '';
						if ($varow['nimi'] == $krow["oletus_ohjelma"]) $sel = 'selected';

						echo "<option value='{$varow['nimi']}' {$sel}>",t($varow["sovellus"])," - ",t($varow["nimitys"]),"</option>";
					}
					echo "</select></td></tr>";


					$sel1 = $sel2 = $sel3 = "";

					if ($krow['resoluutio'] == "N") {
						$sel1 = "SELECTED";
					}
					if (!isset($krow["resoluutio"]) or $krow['resoluutio'] == "I") {
						$sel2 = "SELECTED";
					}
					if ($krow['resoluutio'] == "P") {
						$sel3 = "SELECTED";
					}

					echo "<tr><th align='left'>",t("Näytön koko"),":</th>
							<td><select name='resoluutio'>
							<option value='I' {$sel2}>",t("Iso"),"</option>
							<option value='N' {$sel1}>",t("Normaali"),"</option>
							<option value='P' {$sel3}>",t("Pieni"),"</option>
							</select></td></tr>";

					if ($krow['naytetaan_katteet_tilauksella'] == "") {
						$sel1 = "SELECTED";
						$sel2 = "";
						$sel3 = "";
						$sel4 = "";
					}
					if ($krow['naytetaan_katteet_tilauksella'] == "Y") {
						$sel1 = "";
						$sel2 = "SELECTED";
						$sel3 = "";
						$sel4 = "";
					}
					if ($krow['naytetaan_katteet_tilauksella'] == "N") {
						$sel1 = "";
						$sel2 = "";
						$sel3 = "SELECTED";
						$sel4 = "";
					}
					if ($krow['naytetaan_katteet_tilauksella'] == "B") {
						$sel1 = "";
						$sel2 = "";
						$sel3 = "";
						$sel4 = "SELECTED";
					}

					echo "<tr><th align='left'>",t("Katteet näytetään tilauksentekovaiheessa ja CRM:ssä"),":</th>
							<td><select name='naytetaan_katteet_tilauksella'>
							<option value=''  {$sel1}>",t("Oletus"),"</option>
							<option value='Y' {$sel2}>",t("Kate näytetään"),"</option>
							<option value='N' {$sel3}>",t("Katetta ei näytetä"),"</option>
							<option value='B' {$sel4}>",t("Bruttokate näytetään tilauksentekovaiheessa ja tuotekyselyssä"),"</option>
							</select></td></tr>";

					echo "<tr><th align='left'>",t("Lomaoikeus"),":</th>
							<td><input type='text' name='lomaoikeus' size='3' value='{$krow['lomaoikeus']}'></td></tr>";

					echo "<tr><th align='left'>",t("Asema"),":</td>";
					echo "<td><select name='asema'><option value=''>",t("Ei asemaa"),"</option>";

					$vares = t_avainsana("KUKAASEMA");

					while ($varow = mysql_fetch_array($vares)) {
						$sel = '';
						if ($varow['selite'] == $krow["asema"]) $sel = 'selected';
						echo "<option value='{$varow['selite']}' {$sel}>{$varow['selitetark']}</option>";
					}

					echo "</select></td></tr>";

					echo "<tr><th align='left'>",t("Toimipaikka"),":</td>";
					echo "<td><select name='toimipaikka'><option value=''>",t("Oletustoimipaikka"),"</option>";

					$query = "SELECT * FROM yhtion_toimipaikat WHERE yhtio = '{$kukarow['yhtio']}' AND vat_numero = '' ORDER BY nimi";
					$vares = pupe_query($query);

					while ($varow = mysql_fetch_array($vares)) {
						$sel = '';
						if ($varow['tunnus'] == $krow["toimipaikka"]) $sel = 'selected';
						echo "<option value='{$varow['tunnus']}' {$sel}>{$varow['ovtlisa']} {$varow['nimi']}</option>";
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

						echo "<tr><th align='left'>",t("Jyvitys"),":</td>";
						echo "<td><select name='jyvitys'>
								<option value='' {$sel1}>",t("Ei saa jyvittää myyntitilauksella"),"</option>
								<option value='X' {$sel2}>",t("Saa jyvittää myyntitilauksella"),"</option>";
						echo "</select></td></tr>";
					}
				}

				$sel = $krow['mitatoi_tilauksia'] == 'X' ? ' selected' : '';

				echo "<tr><th align='left'>",t("Tilausten mitätöiminen"),":</td>";
				echo "<td><select name='mitatoi_tilauksia'>
						<option value=''>",t("Käyttäjä saa mitätöidä tilauksia"),"</option>
						<option value='X' {$sel}>",t("Käyttäjä ei saa mitätöidä tilauksia"),"</option>";
				echo "</select></td></tr>";

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
				else {
					$andextra = " and profiili not like 'extranet%' ";
				}

				// Onko tämä extranetkäyttäjä
				if ($toim == "extranet") {
					echo "<input type='hidden' name='extranet' value='X'>";
				}
				else {
					echo "<input type='hidden' name='extranet' value=''>";
				}


				$query = "	SELECT DISTINCT profiili
							FROM oikeu
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND profiili != ''
							{$andextra}
							ORDER BY profiili";
				$pres = pupe_query($query);

				$profiilit = explode(',', $krow["profiilit"]);

				echo "<tr><th valign='top'>",t("Profiilit"),":</th><td>";

				while ($prow = mysql_fetch_array($pres)) {

					$chk = "";

					if (count($profiilit) > 0) {
						foreach($profiilit as $prof) {
							if ($prow["profiili"] == $prof) {
								$chk = "CHECKED";
							}
						}
					}

					echo "<input type='checkbox' name='profiili[]' value='{$prow['profiili']}' {$chk}> {$prow['profiili']}<br>";
				}
				echo "</td></tr>";


				if ($toim != 'extranet') {

					$piirit = explode(',', $krow["piirit"]);

					echo "<tr><th valign='top'>",t("Valitse asiakaspiirit, joihin käyttäjä saa myydä"),":</th><td>";

					$pres = t_avainsana("PIIRI");

					while ($prow = mysql_fetch_array($pres)) {

						$chk = "";
						if(in_array($prow["selite"], $piirit)) {
							$chk = "CHECKED";
						}

						echo "<input type='checkbox' name='piiri[]' value='{$prow['selite']}' {$chk}>{$prow['selite']} - {$prow['selitetark']}<br>";
					}
					echo "</td><td class='back'>",t("Ilman rajausta käyttäjä voi myydä kaikkiin piireihin"),"</td></tr>";
				}

				if ($toim != 'extranet') {
					echo "<tr><th>".t('Käyttäjän budjetti').":</th><td><input type='text' name='budjetti' value='{$krow['budjetti']}' size='12'></td></tr>";
				}
			}

			echo "</table>";
			echo "</td>";

			if ($toim == 'extranet') {
				$queryoik = "	SELECT tunnus
								FROM oikeu
								WHERE nimi LIKE '%yllapito.php'
								AND alanimi = 'extranet_kayttajan_lisatiedot'
								AND kuka = '{$kukarow['kuka']}'
								AND yhtio = '{$yhtiorow['yhtio']}'";
				$res = pupe_query($queryoik);

				if (mysql_num_rows($res) > 0) {
					require ("inc/extranet_kayttajan_lisatiedot.inc");
					echo "<td class='back'>";
					echo "<iframe id='extranet_lisatiedot_iframe' name='extranet_lisatiedot_iframe' src='yllapito.php?toim=extranet_kayttajan_lisatiedot&from=yllapito&ohje=off&haku[4]=@{$selkuka}&lukitse_avaimeen={$selkuka}' style='height: 700px; width: 700px; border: 0px; display: inline;' scrolling='yes' border='0' frameborder='0'></iFrame>";
					echo "</td>";
				}
				echo "</tr>";
				echo "</table>";
			}

			if ($selkuka == "UUSI" or $selkuka == "KOPSAAUUSI") {
				echo "<br><input type='submit' value='",t("Perusta uusi käyttäjä"),"'></form>";
			}
			else {
				echo "<br><input type='submit' name='submit_button' value='",t("Päivitä käyttäjän")," {$krow['kuka']} ",t("tiedot"),"'></form>";
				echo "</td></tr></table>";

				echo "<br><br><br><hr>";

				echo "<table><tr><td class='back'>";

				echo "<form method='post'>
					<input type='hidden' name='selkuka' value='{$selkukarow['kuka']}'>
					<input type='hidden' name='tee' value='delkesken'>
					<input type='submit' value='* ",t("Vapauta käyttäjän")," {$selkukarow['nimi']} ",t("keskenoleva tilaus")," *'>
					</form>";
				echo "</td><td class='back'>";

				echo "<form method='post'>
					<input type='hidden' name='selkuka' value='{$selkukarow['kuka']}'>
					<input type='hidden' name='tee' value='delpsw'>
					<input type='submit' value='** ",t("Poista käyttäjän")," {$selkukarow['nimi']} ",t("salasana")," **'>
					</form>";
				echo "</td><td class='back'>";

				echo "<form method='post'>
					<input type='hidden' name='selkuka' value='{$selkukarow['kuka']}'>
					<input type='hidden' name='tee' value='deloikeu'>
					<input type='submit' value='*** ",t("Poista käyttäjän")," {$selkukarow['nimi']} ",t("käyttöoikeudet")," ***'>
					</form>";
				echo "</td><td class='back'>";

				echo "<form method='post'>
					<input type='hidden' name='selkuka' value='{$selkukarow['kuka']}'>
					<input type='hidden' name='tee' value='deluser'>
					<input type='submit' value='**** ",t("Poista käyttäjä")," {$selkukarow['nimi']} ****'>
					</form>";
				echo "</td></tr></table>";
			}
		}
	}

	if ($tee == "") {

		echo "<br>";
		echo "<table>";
		echo "<form method='post' name='kayttajaformi' id='kayttajaformi'><input type='hidden' name='tee' value='MUUTA'>";

		echo "<tr><th>",t("Hae")," {$toim} ",t("käyttäjä"),":</th>";
		echo "<td>",livesearch_kentta("kayttajaformi", "KAYTTAJAHAKU", "selkuka", 300),"</td>";
		echo "<td><input type='submit' value='",t("Muokkaa käyttäjän tietoja"),"'></td></tr></form>";


		echo "<form method='post' name='kayttajaformi2' id='kayttajaformi2'><input type='hidden' name='tee' value='MUUTA'>";
		echo "<tr><th>",t("Valitse")," {$toim} ",t("käyttäjä"),":</th>";
		echo "<td><select name='selkuka'>";

		if ($toim == "extranet") $extrsel = "X";
		else $extrsel = "";

		$query = "	SELECT kuka.nimi, kuka.kuka, kuka.tunnus, if(count(oikeu.tunnus) > 0, 0, 1) aktiivinen
					FROM kuka
					LEFT JOIN oikeu ON (oikeu.yhtio = kuka.yhtio AND oikeu.kuka = kuka.kuka)
					WHERE kuka.yhtio = '{$kukarow['yhtio']}'
					AND kuka.extranet = '{$extrsel}'
					GROUP BY 1,2,3
					ORDER BY aktiivinen, kuka.nimi";
		$kukares = pupe_query($query);

		echo "<optgroup label='",t("Aktiiviset käyttäjät"),"'>";

		$edakt = 0;
		$poislisa = "";

		while ($kurow = mysql_fetch_assoc($kukares)) {
			if ($selkukarow["tunnus"] == $kurow["tunnus"]) $sel = "selected";
			else $sel = "";

			if ($kurow["aktiivinen"] != $edakt) {
				echo "</optgroup><optgroup label='",t("Poistetut käyttäjät"),"'>";
				$poislisa = "*";
			}

			echo "<option value='{$kurow['tunnus']}' {$sel}>{$poislisa} {$kurow['nimi']} ({$kurow['kuka']})</option>";

			$edakt = $kurow["aktiivinen"];
		}

		echo "</optgroup></select></td><td><input type='submit' value='",t("Muokkaa käyttäjän tietoja"),"'></td></tr></form>";

		echo "<form method='post'>
				<input type='hidden' name='tee' value='MUUTA'>
				<input type='hidden' name='selkuka' value='UUSI'>";
		echo "<tr><th>",t("Perusta uusi käyttäjä"),":</th><td></td><td><input type='submit' value='",t("Luo uusi käyttäjä"),"'></td></tr></form>";

		echo "<form method='post'>
				<input type='hidden' name='tee' value='MUUTA'>
				<input type='hidden' name='selkuka' value='KOPSAAUUSI'>";
		echo "<tr><th>",t("Kopioi käyttäjä toisesta yrityksestä"),":</th><td></td><td><input type='submit' value='",t("Luo uusi käyttäjä"),"'></td></tr></form>";


		echo "</table>";
	}

	require("inc/footer.inc");
