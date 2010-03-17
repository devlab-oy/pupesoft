<?php
	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Uuden yrityksen ohjattu perustaminen").":</font><hr>";

	$error = 0;

	if ($tila == 'parametrit') {
		// Tee yritys täällä
		if ($yhtio == '') {
			echo "<font class='error'>".t("Yritykselle on annettava tunnus")."</font><br>";
			$error = 1;
		}

		if ($nimi == '') {
			echo "<font class='error'>".t("Yritykselle on annettava nimi")."</font><br>";
			$error = 1;
		}

		if ($valuutta == '') {
			echo "<font class='error'>".t("Valuutta on annettava")."</font><br>";
			$error = 1;
		}

		$query = "SELECT nimi from yhtio where yhtio='$yhtio'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$uusiyhtiorow=mysql_fetch_array($result);
			echo "<font class='error'>".t("Tunnus $yhtio on jo käytössä (".$uusiyhtiorow['nimi'].")")."</font><br>";
			$error = 1;
		}

		if ($error == 0) {
			$query = "INSERT into yhtio SET yhtio='$yhtio', nimi='$nimi'";
			$result = mysql_query($query) or pupe_error($query);
			// Tehdään haluttu valuutta
			$query = "INSERT into valuu SET yhtio='$yhtio', nimi='$valuutta', kurssi=1, jarjestys=1";
			$result = mysql_query($query) or pupe_error($query);
		}
		else  {
			unset($tila);
		}
	}

	if ($tila == 'ulkonako') {
		if ($fromyhtio != '') {
			$query = "SELECT * from yhtio where yhtio='$fromyhtio'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Kopioitava yritys ei löydy")."</font><br>";
				$error = 1;
			}

			$query = "	SELECT tunnus
						FROM yhtio
						WHERE yhtio = '$yhtio'";
			$yht_res = mysql_query($query) or pupe_error($query);
			$yht_row = mysql_fetch_assoc($yht_res);

			if ($error == 0) {
				$row = mysql_fetch_array($result);
				$query = "	UPDATE yhtio SET
							konserni = '',
							ytunnus = '$row[ytunnus]',
							ovttunnus = '$row[ovttunnus]',
							kotipaikka = '$row[kotipaikka]',
							osoite = '$row[osoite]',
							postino = '$row[postino]',
							postitp = '$row[postitp]',
							maa = '$row[maa]',
							laskutus_nimi = '$row[laskutus_nimi]',
							laskutus_osoite = '$row[laskutus_osoite]',
							laskutus_postino = '$row[laskutus_postino]',
							laskutus_postitp = '$row[laskutus_postitp]',
							laskutus_maa = '$row[laskutus_maa]',
							kieli = '$row[kieli]',
							valkoodi = '$row[valkoodi]',
							fax = '$row[fax]',
							puhelin = '$row[puhelin]',
							email = '$row[email]',
							www = '$row[www]',
							ean = '$row[ean]',
							pankkinimi1 = '$row[pankkinimi1]',
							pankkitili1 = '$row[pankkitili1]',
							pankkiiban1 = '$row[pankkiiban1]',
							pankkiswift1 = '$row[pankkiswift1]',
							pankkinimi2 = '$row[pankkinimi2]',
							pankkitili2 = '$row[pankkitili2]',
							pankkiiban2 = '$row[pankkiiban2]',
							pankkiswift2 = '$row[pankkiswift2]',
							pankkinimi3 = '$row[pankkinimi3]',
							pankkitili3 = '$row[pankkitili3]',
							pankkiiban3 = '$row[pankkiiban3]',
							pankkiswift3 = '$row[pankkiswift3]',
							kassa = '$row[kassa]',
							pankkikortti = '$row[pankkikortti]',
							luottokortti = '$row[luottokortti]',
							kassaerotus = '$row[kassaerotus]',
							kateistilitys = '$row[kateistilitys]',
							myynti = '$row[myynti]',
							myynti_eu = '$row[myynti_eu]',
							myynti_ei_eu = '$row[myynti_ei_eu]',
							myynti_marginaali = '$row[myynti_marginaali]',
							osto_marginaali = '$row[osto_marginaali]',
							myyntisaamiset = '$row[myyntisaamiset]',
							luottotappiot = '$row[luottotappiot]',
							factoringsaamiset = '$row[factoringsaamiset]',
							konsernimyyntisaamiset = '$row[konsernimyyntisaamiset]',
							ostovelat = '$row[ostovelat]',
							konserniostovelat = '$row[konserniostovelat]',
							valuuttaero = '$row[valuuttaero]',
							myynninvaluuttaero = '$row[myynninvaluuttaero]',
							kassaale = '$row[kassaale]',
							myynninkassaale = '$row[myynninkassaale]',
							muutkulut = '$row[muutkulut]',
							pyoristys = '$row[pyoristys]',
							varasto = '$row[varasto]',
							raaka_ainevarasto = '$row[raaka_ainevarasto]',
							varastonmuutos = '$row[varastonmuutos]',
							raaka_ainevarastonmuutos = '$row[raaka_ainevarastonmuutos]',
							varastonmuutos_valmistuksesta = '$row[varastonmuutos_valmistuksesta]',
							matkalla_olevat = '$row[matkalla_olevat]',
							alv = '$row[alv]',
							siirtosaamiset = '$row[siirtosaamiset]',
							siirtovelka = '$row[siirtovelka]',
							konsernisaamiset = '$row[konsernisaamiset]',
							konsernivelat = '$row[konsernivelat]',
							selvittelytili = '$row[selvittelytili]',
							tilikausi_alku = '$row[tilikausi_alku]',
							tilikausi_loppu = '$row[tilikausi_loppu]',
							ostoreskontrakausi_alku = '$row[ostoreskontrakausi_alku]',
							ostoreskontrakausi_loppu = '$row[ostoreskontrakausi_loppu]',
							myyntireskontrakausi_alku = '$row[myyntireskontrakausi_alku]',
							myyntireskontrakausi_loppu = '$row[myyntireskontrakausi_loppu]',
							tullin_asiaknro = '$row[tullin_asiaknro]',
							tullin_lupanro = '$row[tullin_lupanro]',
							tullikamari = '$row[tullikamari]',
							tullipaate = '$row[tullipaate]',
							tulli_vahennettava_era = '$row[tulli_vahennettava_era]',
							tulli_lisattava_era = '$row[tulli_lisattava_era]',
							kotitullauslupa = '$row[kotitullauslupa]',
							tilastotullikamari = '$row[tilastotullikamari]',
							intrastat_sarjanro = '$row[intrastat_sarjanro]',
							int_koodi = '$row[int_koodi]',
							viivastyskorko = '$row[viivastyskorko]',
							karhuerapvm = '$row[karhuerapvm]',
							kuluprosentti = '$row[kuluprosentti]',
							luontiaika = '$row[luontiaika]'
							WHERE tunnus = '$yht_row[tunnus]'
							AND yhtio = '$yhtio'";
				$result = mysql_query($query) or pupe_error($query);
			}

			$query = "SELECT * from yhtion_parametrit where yhtio='$fromyhtio'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Kopioitava yritys ei löydy")."</font><br>";
				$error = 1;
			}

			if ($error == 0) {
				$row = mysql_fetch_array($result);
				$query = "	INSERT INTO yhtion_parametrit SET
							yhtio = '$yhtio',
							admin_email = '$row[admin_email]',
							alert_email = '$row[alert_email]',
							varauskalenteri_email = '$row[varauskalenteri_email]',
							tuotekopio_email = '$row[tuotekopio_email]',
							verkkolasku_lah = '$row[verkkolasku_lah]',
							finvoice_senderpartyid = '$row[finvoice_senderpartyid]',
							finvoice_senderintermediator = '$row[finvoice_senderintermediator]',
							verkkotunnus_vas = '$row[verkkotunnus_vas]',
							verkkotunnus_lah = '$row[verkkotunnus_lah]',
							verkkosala_vas = '$row[verkkosala_vas]',
							verkkosala_lah = '$row[verkkosala_lah]',
							lasku_tulostin = '$row[lasku_tulostin]',
							pankkitiedostot = '$row[pankkitiedostot]',
							lasku_polku_http = '$row[lasku_polku_http]',
							kuvapankki_polku = '$row[kuvapankki_polku]',
							postittaja_email = '$row[postittaja_email]',
							lahetteen_tulostustapa = '$row[lahetteen_tulostustapa]',
							lahetteen_jarjestys = '$row[lahetteen_jarjestys]',
							lahetteen_jarjestys_suunta = '$row[lahetteen_jarjestys_suunta]',
							laskun_jarjestys = '$row[laskun_jarjestys]',
							laskun_jarjestys_suunta = '$row[laskun_jarjestys_suunta]',
							tilauksen_jarjestys = '$row[tilauksen_jarjestys]',
							tilauksen_jarjestys_suunta = '$row[tilauksen_jarjestys_suunta]',
							kerayslistan_jarjestys = '$row[kerayslistan_jarjestys]',
							kerayslistan_jarjestys_suunta = '$row[kerayslistan_jarjestys_suunta]',
							valmistus_kerayslistan_jarjestys = '$row[valmistus_kerayslistan_jarjestys]',
							valmistus_kerayslistan_jarjestys_suunta = '$row[valmistus_kerayslistan_jarjestys_suunta]',
							ostotilauksen_jarjestys = '$row[ostotilauksen_jarjestys]',
							ostotilauksen_jarjestys_suunta = '$row[ostotilauksen_jarjestys_suunta]',
							pakkaamolokerot = '$row[pakkaamolokerot]',
							lahete_nouto_allekirjoitus = '$row[lahete_nouto_allekirjoitus]',
							lahete_tyyppi_tulostus = '$row[lahete_tyyppi_tulostus]',
							laskutyyppi = '$row[laskutyyppi]',
							viivakoodi_laskulle = '$row[viivakoodi_laskulle]',
							koontilaskut_yhdistetaan = '$row[koontilaskut_yhdistetaan]',
							tilausvahvistustyyppi = '$row[tilausvahvistustyyppi]',
							tilausvahvistus_lahetys = '$row[tilausvahvistus_lahetys]',
							tilausvahvistus_tallenna = '$row[tilausvahvistus_tallenna]',
							varastosiirto_tilausvahvistus = '$row[varastosiirto_tilausvahvistus]',
							ostotilaustyyppi = '$row[ostotilaustyyppi]',
							ostotilaukseen_toimittajan_toimaika = '$row[ostotilaukseen_toimittajan_toimaika]',
							tyomaaraystyyppi = '$row[tyomaaraystyyppi]',
							viivakoodi_purkulistaan = '$row[viivakoodi_purkulistaan]',
							laskutuskielto = '$row[laskutuskielto]',
							rahti_hinnoittelu = '$row[rahti_hinnoittelu]',
							rahti_tuotenumero = '$row[rahti_tuotenumero]',
							kasittelykulu_tuotenumero = '$row[kasittelykulu_tuotenumero]',
							maksuehto_tuotenumero = '$row[maksuehto_tuotenumero]',
							ennakkomaksu_tuotenumero = '$row[ennakkomaksu_tuotenumero]',
							alennus_tuotenumero = '$row[alennus_tuotenumero]',
							lisakulu_tuotenumero = '$row[lisakulu_tuotenumero]',
							lisakulu_prosentti = '$row[lisakulu_prosentti]',
							lisakulun_lisays = '$row[lisakulun_lisays]',
							tuotteen_oletuspaikka = '$row[tuotteen_oletuspaikka]',
							alv_kasittely = '$row[alv_kasittely]',
							asiakashinta_netto = '$row[asiakashinta_netto]',
							puute_jt_oletus = '$row[puute_jt_oletus]',
							puute_jt_kerataanko = '$row[puute_jt_kerataanko]',
							kerataanko_jos_vain_puute_jt = '$row[kerataanko_jos_vain_puute_jt]',
							jt_automatiikka = '$row[jt_automatiikka]',
							jt_rahti = '$row[jt_rahti]',
							jt_rivien_kasittely = '$row[jt_rivien_kasittely]',
							suoratoim_automaatio = '$row[suoratoim_automaatio]',
							kerayslistojen_yhdistaminen = '$row[kerayslistojen_yhdistaminen]',
							karayksesta_rahtikirjasyottoon = '$row[karayksesta_rahtikirjasyottoon]',
							rahtikirjojen_esisyotto = '$row[rahtikirjojen_esisyotto]',
							rahtikirjan_kollit_ja_lajit = '$row[rahtikirjan_kollit_ja_lajit]',
							laskunsummapyoristys = '$row[laskunsummapyoristys]',
							hintapyoristys = '$row[hintapyoristys]',
							viitteen_kasinsyotto = '$row[viitteen_kasinsyotto]',
							suoratoim_ulkomaan_alarajasumma = '$row[suoratoim_ulkomaan_alarajasumma]',
							erikoisvarastomyynti_alarajasumma = '$row[erikoisvarastomyynti_alarajasumma]',
							erikoisvarastomyynti_alarajasumma_rivi = '$row[erikoisvarastomyynti_alarajasumma_rivi]',
							rahtivapaa_alarajasumma = '$row[rahtivapaa_alarajasumma]',
							logo = '',
							lasku_logo = '',
							lasku_logo_positio = '',
							lasku_logo_koko = '',
							naytetaan_katteet_tilauksella = '$row[naytetaan_katteet_tilauksella]',
							tilauksen_yhteyshenkilot = '$row[tilauksen_yhteyshenkilot]',
							tilauksen_seuranta = '$row[tilauksen_seuranta]',
							tilauksen_kohteet = '$row[tilauksen_kohteet]',
							tarjouksen_voi_versioida = '$row[tarjouksen_voi_versioida]',
							dokumentaatiohallinta = '$row[dokumentaatiohallinta]',
							nimityksen_muutos_tilauksella = '$row[nimityksen_muutos_tilauksella]',
							poistuvat_tuotteet = '$row[poistuvat_tuotteet]',
							automaattinen_tuotehaku = '$row[automaattinen_tuotehaku]',
							jyvita_alennus = '$row[jyvita_alennus]',
							salli_jyvitys_myynnissa = '$row[salli_jyvitys_myynnissa]',
							rivinumero_syotto = '$row[rivinumero_syotto]',
							tee_osto_myyntitilaukselta = '$row[tee_osto_myyntitilaukselta]',
							automaattinen_jt_toimitus = '$row[automaattinen_jt_toimitus]',
							dynaaminen_kassamyynti = '$row[dynaaminen_kassamyynti]',
							kerayspoikkeama_kasittely = '$row[kerayspoikkeama_kasittely]',
							kerayspoikkeamaviestin_lahetys = '$row[kerayspoikkeamaviestin_lahetys]',
							oletus_toimitusehto = '$row[oletus_toimitusehto]',
							oletus_toimitusehto2 = '$row[oletus_toimitusehto2]',
							sad_lomake_tyyppi = '$row[sad_lomake_tyyppi]',
							tarjouksen_voimaika = '$row[tarjouksen_voimaika]',
							tarjouksen_tuotepaikat = '$row[tarjouksen_tuotepaikat]',
							tarjouksen_alv_kasittely = '$row[tarjouksen_alv_kasittely]',
							splittauskielto = '$row[splittauskielto]',
							rekursiiviset_reseptit = '$row[rekursiiviset_reseptit]',
							rekursiiviset_tuoteperheet = '$row[rekursiiviset_tuoteperheet]',
							valmistusten_yhdistaminen = '$row[valmistusten_yhdistaminen]',
							valmistuksen_etusivu = '$row[valmistuksen_etusivu]',
							rahtikirjan_kopiomaara = '$row[rahtikirjan_kopiomaara]',
							kerataanko_saldottomat = '$row[kerataanko_saldottomat]',
							saldo_kasittely = '$row[saldo_kasittely]',
							ytunnus_tarkistukset = '$row[ytunnus_tarkistukset]',
							vienti_erittelyn_tulostus = '$row[vienti_erittelyn_tulostus]',
							epakur_kehahin_paivitys = '$row[epakur_kehahin_paivitys]',
							oletus_lahetekpl = '$row[oletus_lahetekpl]',
							oletus_oslappkpl = '$row[oletus_oslappkpl]',
							oletus_rahtikirja_lahetekpl = '$row[oletus_rahtikirja_lahetekpl]',
							oletus_rahtikirja_oslappkpl = '$row[oletus_rahtikirja_oslappkpl]',
							oslapp_rakir_logo = '$row[oslapp_rakir_logo]',
							rahti_ja_kasittelykulut_kasin = '$row[rahti_ja_kasittelykulut_kasin]',
							synkronoi = '$row[synkronoi]',
							myyntitilaus_osatoimitus = '$row[myyntitilaus_osatoimitus]',
							myyntitilaus_asiakasmemo = '$row[myyntitilaus_asiakasmemo]',
							myyntitilauksen_liitteet = '$row[myyntitilauksen_liitteet]',
							varastopaikan_lippu = '$row[varastopaikan_lippu]',
							varaako_jt_saldoa = '$row[varaako_jt_saldoa]',
							korvaavan_hinta_ylaraja = '$row[korvaavan_hinta_ylaraja]',
							korvaavat_hyvaksynta = '$row[korvaavat_hyvaksynta]',
							monikayttajakalenteri = '$row[monikayttajakalenteri]',
							automaattinen_asiakasnumerointi = '$row[automaattinen_asiakasnumerointi]',
							asiakasnumeroinnin_aloituskohta = '$row[asiakasnumeroinnin_aloituskohta]',
							asiakkaan_tarkenne = '$row[asiakkaan_tarkenne]',
							haejaselaa_konsernisaldot = '$row[haejaselaa_konsernisaldot]',
							viikkosuunnitelma = '$row[viikkosuunnitelma]',
							kalenterimerkinnat = '$row[kalenterimerkinnat]',
							variaatiomyynti = '$row[variaatiomyynti]',
							luontiaika = now()";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
		else {
				$query = "INSERT into yhtion_parametrit SET yhtio='$yhtio'";
				$result = mysql_query($query) or pupe_error($query);
		}
	}

	if ($tila == 'perusta') {
		if ($fromyhtio != '') {

			$query = "SELECT css, css_extranet, css_verkkokauppa, css_pieni from yhtion_parametrit where yhtio='$fromyhtio'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Kopioitava yritys ei löydy")."</font><br>";
				$error = 1;
			}

			if ($error == 0) {
				$uusiyhtiorow=mysql_fetch_array($result);

				$query = "	SELECT tunnus
							FROM yhtion_parametrit
							WHERE yhtio = '$yhtio'";
				$yht_res = mysql_query($query) or pupe_error($query);
				$yht_row = mysql_fetch_assoc($yht_res);

				$query = "	UPDATE yhtion_parametrit SET
				 			css = '$uusiyhtiorow[css]', 
							css_extranet = '$uusiyhtiorow[css_extranet]',
							css_verkkokauppa = '$uusiyhtiorow[css_verkkokauppa]',
							css_pieni = '$uusiyhtiorow[css_pieni]'
							WHERE tunnus = '$yht_row[tunnus]'
							AND yhtio = '$yhtio'";
				//$result = mysql_query($query) or pupe_error($query);
			}
		}
		else {
				$query = "INSERT into yhtion_parametrit SET yhtio='$yhtio'";
				//$result = mysql_query($query) or pupe_error($query);
		}

	}

	if ($tila == 'menut') {
		if ($fromyhtio != '') {
			$query = "INSERT into oikeu (sovellus,nimi,alanimi,paivitys,lukittu,nimitys,jarjestys,jarjestys2,yhtio)
			SELECT sovellus,nimi,alanimi,paivitys,lukittu,nimitys,jarjestys,jarjestys2,'$yhtio' FROM oikeu WHERE yhtio='$fromyhtio' and profiili='' and kuka=''";
			$result = mysql_query($query) or pupe_error($query);
		}
	}

	if ($tila == 'profiilit') {
		if (is_array($profiilit)) {
			foreach($profiilit as $prof) {
				$query = "	SELECT *
							FROM oikeu
							WHERE yhtio='$fromyhtio' and kuka='$prof' and profiili='$prof'";
				$pres = mysql_query($query) or pupe_error($query);

				while ($trow = mysql_fetch_array($pres)) {
					$query = "	INSERT into oikeu
									SET
									kuka		= '$trow[kuka]',
									sovellus	= '$trow[sovellus]',
									nimi		= '$trow[nimi]',
									alanimi 	= '$trow[alanimi]',
									paivitys	= '$trow[paivitys]',
									nimitys		= '$trow[nimitys]',
									jarjestys 	= '$trow[jarjestys]',
									jarjestys2	= '$trow[jarjestys2]',
									profiili	= '$trow[profiili]',
									yhtio		= '$yhtio'";
					$rresult = mysql_query($query) or pupe_error($query);
				}
			}
		}
	}

	if ($tila == 'kayttaja') {
		$query = "	SELECT kuka FROM oikeu WHERE yhtio='$yhtio'";
		$pres = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($pres) > 0) {
			if (count($profiilit) == 0) {
				echo "<font class='error'>Ainakin yksi profiili on valittava</font><br>";
				$error = 1;
			}
		}

		if ($error == 0) {
			//Tehdään käyttäjä
			$profile = '';
			if (is_array($profiilit)) {
				if (count($profiilit) > 0) {
					foreach($profiilit as $prof) {
						$profile .= $prof.",";
					}
					$profile = substr($profile,0,-1);
				}
			}

			$query = "SELECT salasana, nimi FROM kuka WHERE kuka='$kuka' limit 1";
			$pres = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($pres) > 0) {
				$krow = mysql_fetch_array($pres);
				$salasana = $krow['salasana'];
				$nimi = $krow['nimi'];
				echo "<font class='message'>$krow[nimi] ".t("Käyttäjä löytyi muistakin yrityksistä. Tietoja kopioitiin!"),"<br></font>";
			}
			else $salasana = md5($salasana);

			$query = "INSERT into kuka SET
				yhtio = '$yhtio',
				nimi = '$nimi',
				salasana = '$salasana',
				kuka  = '$kuka',
				profiilit = '$profile'
			";
			$result = mysql_query($query) or pupe_error($query);

			//Oikeudet
			if (is_array($profiilit)) {
				foreach($profiilit as $prof) {

					$query = "	SELECT *
								FROM oikeu
								WHERE yhtio='$yhtio' and kuka='$prof' and profiili='$prof'";
					$pres = mysql_query($query) or pupe_error($query);

					while ($trow = mysql_fetch_array($pres)) {
						//joudumme tarkistamaan ettei tätä oikeutta ole jo tällä käyttäjällä.
						//voi olla jossain toisessa profiilissa
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
									and yhtio		= '$yhtio'";
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
										yhtio		= '$yhtio'";
							$rresult = mysql_query($query) or pupe_error($query);

						}
					}
				}
			}
		}
		else {
			$tila = 'profiilit';
		}
	}

	if ($tila == 'tili') {
		if ($fromyhtio != '') {
			$query = "SELECT * FROM tili where yhtio='$fromyhtio'";
			$kukar = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($kukar))
			{
				$query = "insert into tili (nimi, sisainen_taso, tilino, ulkoinen_taso, alv_taso, yhtio, oletusalv) values ('$row[nimi]','$row[sisainen_taso]','$row[tilino]','$row[ulkoinen_taso]', '$row[alv_taso]', '$yhtio', '$row[oletusalv]')";
				$upres = mysql_query($query) or pupe_error($query);
			}

			$query = "SELECT * FROM taso where yhtio='$fromyhtio'";
			$kukar = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($kukar))
			{
				$query = "insert into taso (tyyppi, laji, taso, nimi, yhtio) values ('$row[tyyppi]','$row[laji]','$row[taso]','$row[nimi]','$yhtio')";
				$upres = mysql_query($query) or pupe_error($query);
			}
		}
	}

	if ($tila == 'avainsana') {
		if (is_array($avainsanat) and $eimitaan=='') {
			foreach($avainsanat as $avain) {
				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio='$fromyhtio' and laji='$avain'";
				$pres = mysql_query($query) or pupe_error($query);
				while ($trow = mysql_fetch_array($pres)) {
					$query = "	INSERT into avainsana
									SET
									jarjestys		= '$trow[jarjestys]',
									laatija			= '$kukarow[laatija]',
									laji			= '$trow[laji]',
									luontiaika 		=  now(),
									selite			= '$trow[selite]',
									selitetark		= '$trow[selitetark]',
									selitetark_2	= '$trow[selitetark_2]',
									selitetark_3	= '$trow[selitetark_3]',
									kieli			= '$trow[kieli]',
									yhtio			= '$yhtio'";
					$rresult = mysql_query($query) or pupe_error($query);
				}
			}
		}
	}

	if ($tila == 'kirjoitin') {
		if ($fromyhtio != '') {
			$query = "SELECT * FROM kirjoittimet where yhtio='$fromyhtio'";
			$kukar = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($kukar))
			{
				$query = "	INSERT INTO kirjoittimet SET
							yhtio = '$yhtio',
							fax = '$row[fax]',
							kirjoitin = '$row[kirjoitin]',
							komento = '$row[komento]',
							merkisto = '$row[merkisto]',
							nimi = '$row[nimi]',
							osoite = '$row[osoite]',
							postino = '$row[postino]',
							postitp = '$row[postitp]',
							puhelin = '$row[puhelin]',
							yhteyshenkilo = '$row[yhteyshenkilo]',
							ip = '$row[ip]',
							luontiaika = now()";
				$upres = mysql_query($query) or pupe_error($query);
			}
		}
	}

	if ($tila == 'maksuehto') {
		if ($fromyhtio != '') {
			$query = "SELECT * FROM maksuehto where yhtio='$fromyhtio'";
			$kukar = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($kukar)) {
				$query = "	INSERT INTO maksuehto SET
							yhtio = '$yhtio',
							teksti = '$row[teksti]',
							rel_pvm = '$row[rel_pvm]',
							abs_pvm = '$row[abs_pvm]',
							kassa_relpvm = '$row[kassa_relpvm]',
							kassa_abspvm = '$row[kassa_abspvm]',
							kassa_alepros = '$row[kassa_alepros]',
							osamaksuehto1 = '$row[osamaksuehto1]',
							osamaksuehto2 = '$row[osamaksuehto2]',
							summanjakoprososa2 = '$row[summanjakoprososa2]',
							jv = '$row[jv]',
							kateinen = '$row[kateinen]',
							suoraveloitus = '$row[suoraveloitus]',
							factoring = '$row[factoring]',
							pankkiyhteystiedot = '$row[pankkiyhteystiedot]',
							itsetulostus = '$row[itsetulostus]',
							jaksotettu = '$row[jaksotettu]',
							erapvmkasin = '$row[erapvmkasin]',
							sallitut_maat = '$row[sallitut_maat]',
							kaytossa = '$row[kaytossa]',
							jarjestys = '$row[jarjestys]',
							luontiaika = now()";
				$upres = mysql_query($query) or pupe_error($query);
			}
		}
	}

	if ($tila == 'toimitustapa') {
		if ($fromyhtio != '') {
			$query = "SELECT * FROM toimitustapa where yhtio='$fromyhtio'";
			$kukar = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($kukar)) {
				$query = "	INSERT INTO toimitustapa SET
							yhtio = '$yhtio',
							selite = '$row[selite]',
							tulostustapa = '$row[tulostustapa]',
							rahtikirja = '$row[rahtikirja]',
							osoitelappu = '$row[osoitelappu]',
							rahdinkuljettaja = '$row[rahdinkuljettaja]',
							sopimusnro = '$row[sopimusnro]',
							jvkulu = '$row[jvkulu]',
							jvkielto = '$row[jvkielto]',
							vak_kielto = '$row[vak_kielto]',
							nouto = '$row[nouto]',
							lauantai = '$row[lauantai]',
							kuljyksikko = '$row[kuljyksikko]',
							merahti = '$row[merahti]',
							multi_jv = '$row[multi_jv]',
							extranet = '$row[extranet]',
							ei_pakkaamoa = '$row[ei_pakkaamoa]',
							kuluprosentti = '$row[kuluprosentti]',
							toim_nimi = '$row[toim_nimi]',
							toim_nimitark = '$row[toim_nimitark]',
							toim_osoite = '$row[toim_osoite]',
							toim_postino = '$row[toim_postino]',
							toim_postitp = '$row[toim_postitp]',
							toim_maa = '$row[toim_maa]',
							maa_maara = '$row[maa_maara]',
							sisamaan_kuljetus = '$row[sisamaan_kuljetus]',
							sisamaan_kuljetus_kansallisuus = '$row[sisamaan_kuljetus_kansallisuus]',
							sisamaan_kuljetusmuoto = '$row[sisamaan_kuljetusmuoto]',
							kontti = '$row[kontti]',
							aktiivinen_kuljetus = '$row[aktiivinen_kuljetus]',
							aktiivinen_kuljetus_kansallisuus = '$row[aktiivinen_kuljetus_kansallisuus]',
							kauppatapahtuman_luonne = '$row[kauppatapahtuman_luonne]',
							kuljetusmuoto = '$row[kuljetusmuoto]',
							poistumistoimipaikka_koodi = '$row[poistumistoimipaikka_koodi]',
							ulkomaanlisa = '$row[ulkomaanlisa]',
							sallitut_maat = '$row[sallitut_maat]',
							jarjestys = '$row[jarjestys]',
							luontiaika = now()";
				$upres = mysql_query($query) or pupe_error($query);
			}
		}
	}

	if ($tila == 'varasto') {
		if ($varasto != '') {
			$varasto = mysql_real_escape_string($varasto);

			$query = "SELECT * FROM kirjoittimet where yhtio = '$yhtio' LIMIT 1";
			$kirjoitin_res = mysql_query($query) or pupe_error($query);
			$kirjoitin_row = mysql_fetch_assoc($kirjoitin_res);

			$query = "	INSERT INTO varastopaikat SET
						yhtio = '$yhtio',
						alkuhyllyalue = 'A00',
						alkuhyllynro = '00',
						loppuhyllyalue = 'Z99',
						loppuhyllynro = '99',
						printteri0 = '$kirjoitin_row[tunnus]',
						printteri1 = '$kirjoitin_row[tunnus]',
						printteri2 = '$kirjoitin_row[tunnus]',
						printteri3 = '$kirjoitin_row[tunnus]',
						printteri4 = '$kirjoitin_row[tunnus]',
						printteri5 = '$kirjoitin_row[tunnus]',
						printteri6 = '$kirjoitin_row[tunnus]',
						printteri7 = '$kirjoitin_row[tunnus]',
						nimitys = '$varasto',
						tyyppi = '',
						nimi = '$varasto',
						nimitark = '',
						osoite = '',
						postino = '',
						postitp = '',
						maa = 'FI',
						maa_maara = '',
						sisamaan_kuljetus = '',
						sisamaan_kuljetus_kansallisuus = '',
						sisamaan_kuljetusmuoto = 0,
						kontti = 0,
						aktiivinen_kuljetus = '',
						aktiivinen_kuljetus_kansallisuus = '',
						kauppatapahtuman_luonne = 0,
						kuljetusmuoto = 0,
						poistumistoimipaikka_koodi = '',
						sallitut_maat = '',
						luontiaika = now()";
			$upres = mysql_query($query) or pupe_error($query);
		}
		unset($tila);
		unset($yhtio);
		unset($nimi);
		unset($valuutta);
	}


// Käyttöliittymä

	if (isset($tila)) {
		$query = "SELECT nimi from yhtio where yhtio='$yhtio'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>Perustettava yritys on kadonnut!</font><br>";
			exit;
		}
		$uusiyhtiorow=mysql_fetch_array($result);

		echo "<table>
		<tr><td>$yhtio</td><td>$uusiyhtiorow[nimi]</td></tr>
		</table><br><br>";
	}

	if ($tila == 'parametrit') {
		// yritysvalinta
		$query = "SELECT yhtio, nimi FROM yhtio WHERE yhtio != '$yhtio'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='ulkonako'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Miltä yritykseltä kopioidaan tiedot ja parametrit?").":</th><td><select name='fromyhtio'>
		<option value=''>".t("Ei kopioida")."</option>";

		while ($uusiyhtiorow=mysql_fetch_array($result)) {
			echo "<option value='$uusiyhtiorow[yhtio]'>$uusiyhtiorow[nimi] ($uusiyhtiorow[yhtio])</option>";
		}

		echo "</select></td></tr>";
		echo "<tr><th></th><td><input type='submit' value='".t('Valitse')."'></td></tr></table></form>";
	}

	if ($tila == 'ulkonako') {
		// yritysvalinta
		$query = "SELECT yhtio, nimi FROM yhtio WHERE yhtio != '$yhtio'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='perusta'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Miltä yritykseltä kopioidaan ulkonäkö?").":</th><td><select name='fromyhtio'>
		<option value=''>".t("Ei kopioida")."</option>";

		while ($uusiyhtiorow=mysql_fetch_array($result)) {
			echo "<option value='$uusiyhtiorow[yhtio]'>$uusiyhtiorow[nimi] ($uusiyhtiorow[yhtio])</option>";
		}

		echo "</select></td></tr>";
		echo "<tr><th></th><td><input type='submit' value='".t('Valitse')."'></td></tr></table></form>";
	}

	if ($tila == 'perusta') {
		// yritysvalinta
		$query = "SELECT yhtio, nimi FROM yhtio WHERE yhtio != '$yhtio'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='menut'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Miltä yritykseltä kopioidaan menut?").":</th><td><select name='fromyhtio'>
		<option value=''>".t("Ei kopioida")."</option>";

		while ($uusiyhtiorow=mysql_fetch_array($result)) {
			echo "<option value='$uusiyhtiorow[yhtio]'>$uusiyhtiorow[nimi] ($uusiyhtiorow[yhtio])</option>";
		}

		echo "</select></td></tr>";
		echo "<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr></table></form>";
	}

	if ($tila == 'menut') {
		// profiilit
		$query = "SELECT distinct profiili FROM oikeu WHERE yhtio = '$fromyhtio' and profiili != ''";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='profiilit'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<input type='hidden' name = 'fromyhtio' value='$fromyhtio'>
		<table>
		<tr><th>".t("Mitkä profiilit kopioidaan?").":</th><td></td></tr>";

		while ($profiilirow=mysql_fetch_array($result)) {
			echo "<tr><td>$profiilirow[profiili]</td><td><input type='checkbox' name = 'profiilit[]' value='$profiilirow[profiili]' checked></td></tr>";
		}

		echo "<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr></table></form>";
	}

	if ($tila == 'profiilit') {
		// käyttäjät
		$query = "SELECT distinct profiili FROM oikeu WHERE yhtio = '$yhtio' and profiili != ''";
		$result = mysql_query($query) or pupe_error($query);

		if (!isset($kuka)) {
				$kuka = $kukarow['kuka'];
				$nimi = $kukarow['nimi'];
		}

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='kayttaja'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Anna käyttäjätunnus").":</th><td><input type='text' name = 'kuka' value='$kuka'></td></tr>
		<tr><th>".t("Nimi").":</th><td><input type='text' name = 'nimi' value='$nimi'></td></tr>
		<tr><th>".t("Salasana")."</th><td><input type='text' name = 'salasana' value='$salasana'></td></tr>
		<tr><th>".t("Profiilit")."</th><td></td></tr>";

		while ($profiilirow=mysql_fetch_array($result)) {
			echo "<th>$profiilirow[profiili]</th><td><input type='checkbox' name = 'profiilit[]' value='$profiilirow[profiili]'></td></tr>";
		}

		echo "<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr></table></form>";
	}

	if ($tila == 'kayttaja') {
		// tilit ja tasot
		$query = "SELECT distinct tili.yhtio, yhtio.nimi FROM tili, yhtio WHERE tili.yhtio=yhtio.yhtio";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='tili'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Miltä yritykseltä kopioidaan tilikartta?").":</th><td><select name='fromyhtio'>
		<option value=''>".t("Ei kopioida")."</option>";

		while ($uusiyhtiorow=mysql_fetch_array($result)) {
			echo "<option value='$uusiyhtiorow[yhtio]'>$uusiyhtiorow[nimi]</option>";
		}

		echo "</select></td></tr>";
		echo "<tr><th></th><td><input type='submit' value='".t('Kopioi')."'></td></tr></table></form>";
	}

	if ($tila == 'tili') {
		// avainsanat
		$query = "SELECT yhtio, nimi FROM yhtio WHERE yhtio != '$yhtio'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='avainsana'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Miltä yritykseltä kopioidaan avainsanat?").":</th><td><select name='fromyhtio'>";

		while ($uusiyhtiorow=mysql_fetch_array($result)) {
			echo "<option value='$uusiyhtiorow[yhtio]'>$uusiyhtiorow[nimi] ($uusiyhtiorow[yhtio])</option>";
		}

		echo "</select></td></tr>";
		echo "<tr><td><INPUT type='checkbox' name='eimitaan' value='x'>".t("Avainsanoja ei kopioida")."</td><td></td</tr>";
		echo "<tr><td>".t("Mitkä avainsanatyypit kopioidaan")."</td><td></td></tr>";
		echo "	<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='Y'>".t("Yksikko")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TRY'>".t("Tuoteryhmä")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='OSASTO'>".t("Tuoteosasto")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TUOTEMERKKI'>".t("Tuotemerkki")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='S' >".t("Tuotteen status")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TUOTEULK'>".t("Tuotteiden avainsanojen laji")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='VARASTOLUOKKA'>".t("Varastoluokka")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='SARJANUMERON_LI'>".t("Sarjanumeron lisätieto")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='PARAMETRI'>".t("Tuotteen parametri")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TARRATYYPPI'>".t("Tuotteen tarratyyppi")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASIAKASLUOKKA'>".t("Asiakasluokka")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASIAKASOSASTO'>".t("Asiakasosasto")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASIAKASRYHMA'>".t("Asiakasryhma")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASIAKASTILA'>".t("Asiakastila")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='PIIRI'>".t("Asiakkaan piiri")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='LASKT_EMAIL'>".t("Laskutustiedot autom. sähköpostitukseen")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='LASKT_EMAIL_SOP'>".t("Laskutustiedot autom. sähköpostitukseen (Sopimus)")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASAVAINSANA'>".t("Asiakkaan avainsanojen laji")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='EXTASAVAINSANA'>".t("Extranet-asiakkaan avainsanojen laji")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='TV'>".t("Tilausvahvistus")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='LAHETETYYPPI'>".t("Lähetetyyppi")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='KALETAPA'>".t("CRM yhteydenottotapa")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='MYSQLALIAS'>".t("Tietokantasarakkeen nimialias")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TOIMITUSTAPA_OS'>".t("Toimitustapa ostolle (kuljetus)")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='KUKAASEMA'>".t("Käytäjän asema")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='ALVULK'>".t("Ulkomaan ALV%")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='ALV'>".t("ALV%")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='SEURANTA'>".t("Tilauksen seurantaluokka")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='pakkaus'>".t("Pakkaus")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='TOIMEHTO'>".t("Toimitusehto")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='HENKILO_OSASTO'>".t("Henkilöosasto")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='RAHTIKIRJA'>".t("Rahtikirjatyyppi")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='KERAYSLISTA'>".t("Keräyslista")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TOIMTAPAKV'>".t("Toimitustavan kieliversio")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='KVERITTELY'>".t("Kulunvalvonnan erittely")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TYOM_TYOJONO'>".t("Työmääräysten työjono")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TYOM_TYOSTATUS'>".t("Työmääräysten työstatus")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TYOM_TYOLINJA'>".t("Työmääräysten työlinja")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='KT'>".t("Kauppatapahtuman luonne")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='TULLI'>".t("Poistumistoimipaikka")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='KM'>".t("Kuljetusmuoto")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='C'>".t("CHN tietue")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='LASKUKUVAUS'>".t("Maksuposition kuvaus")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='KARHUVIESTI'>".t("Karhuviesti")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='MAKSUEHTOKV'>".t("Maksuehdon kieliversio")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='CRM_ROOLI'>".t("Yhteyshenkilön rooli")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='CRM_SUORAMARKKI'>".t("Yhteyshenkilön suoramarkkinointitiedot")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='VAKIOVIESTI'>".t("Vakioviesti")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='LITETY'>".t("Liitetiedostotyyppi")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TIL-LITETY'>".t("Tilauksen liitetiedostotyyppi")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='JAKELULISTA'>".t("Email jakelulista")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='LUETTELO'>".t("Luettelotyyppi")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TRIVITYYPPI'>".t("Tilausrivin tyyppi")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox' CHECKED name='avainsanat[]'  value='LASKUTUS_SAATE'>".t("Laskun sähköpostisaatekirje asiakkaalle")."</td></tr>
				<tr><td></td><td><INPUT type='checkbox'  name='avainsanat[]'  value='TV_LISATIETO'>".t("Tilausvahvistuksen lisätiedot")."</td></tr>";
		echo "<tr><th></th><td><input type='submit' value='".t('Kopioi')."'></td></tr></table></form>";
	}

	if ($tila == 'avainsana') {
		//tulostimet
		$query = "SELECT distinct kirjoittimet.yhtio, yhtio.nimi FROM kirjoittimet, yhtio WHERE kirjoittimet.yhtio=yhtio.yhtio";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='kirjoitin'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Miltä yritykseltä kopioidaan kirjoittimet?").":</th><td><select name='fromyhtio'>
		<option value=''>".t("Ei kopioida")."</option>";

		while ($uusiyhtiorow=mysql_fetch_array($result)) {
			echo "<option value='$uusiyhtiorow[yhtio]'>$uusiyhtiorow[nimi]</option>";
		}

		echo "</select></td></tr>";
		echo "<tr><th></th><td><input type='submit' value='".t('Kopioi')."'></td></tr></table></form>";
	}

	if ($tila == 'kirjoitin') {
		//maksuehdot
		$query = "SELECT distinct maksuehto.yhtio, yhtio.nimi FROM maksuehto, yhtio WHERE maksuehto.yhtio=yhtio.yhtio";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='maksuehto'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Miltä yritykseltä kopioidaan maksuehdot?").":</th><td><select name='fromyhtio'>
		<option value=''>".t("Ei kopioida")."</option>";

		while ($uusiyhtiorow=mysql_fetch_array($result)) {
			echo "<option value='$uusiyhtiorow[yhtio]'>$uusiyhtiorow[nimi]</option>";
		}

		echo "</select></td></tr>";
		echo "<tr><th></th><td><input type='submit' value='".t('Kopioi')."'></td></tr></table></form>";
	}

	if ($tila == 'maksuehto') {
		//toimitustavat
		$query = "SELECT distinct toimitustapa.yhtio, yhtio.nimi FROM toimitustapa, yhtio WHERE toimitustapa.yhtio=yhtio.yhtio";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='toimitustapa'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Miltä yritykseltä kopioidaan toimitustavat?").":</th><td><select name='fromyhtio'>
		<option value=''>".t("Ei kopioida")."</option>";

		while ($uusiyhtiorow=mysql_fetch_array($result)) {
			echo "<option value='$uusiyhtiorow[yhtio]'>$uusiyhtiorow[nimi]</option>";
		}

		echo "</select></td></tr>";
		echo "<tr><th></th><td><input type='submit' value='".t('Kopioi')."'></td></tr></table></form>";
	}

	if ($tila == 'toimitustapa') {
		//varasto
		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='varasto'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>Anna varaston nimi</th><td><input type='text' name = 'varasto' value='$varasto'></td></tr>
		<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr></table></form>";
	}

	if (!isset($tila)) {
		if (!isset($valuutta)) $valuutta = 'EUR';
		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='parametrit'><table>
		<tr><th>Anna uuden yrityksen tunnus</th><td><input type='text' name = 'yhtio' value='$yhtio' size='10' maxlength='5'></td></tr>
		<tr><th>Anna uuden yrityksen nimi</th><td><input type='text' name = 'nimi' value='$nimi'></td></tr>
		<tr><th>Anna uuden yrityksen oletusvaluutta</th><td><input type='text' name = 'valuutta' value='$valuutta' maxlength='3'></td></tr>
		<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr>
		</table></form>";
	}
	require("inc/footer.inc");
?>
