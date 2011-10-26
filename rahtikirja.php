<?php

	require ("inc/parametrit.inc");

	if (!isset($tee)) $tee = '';
	if (!isset($lasku_yhtio)) $lasku_yhtio = '';
	if (!isset($toimtila)) $toimtila = '';
	if (!isset($id)) $id = 0;
	if (!isset($erikoispakkaus)) $erikoispakkaus = 0;
	if (!isset($toimitustapa)) $toimitustapa = '';
	if (!isset($rahtikirjan_esisyotto)) $rahtikirjan_esisyotto = '';
	if (!isset($jarj)) $jarj = '';
	if (!isset($etsi)) $etsi = '';
	if (!isset($tuvarasto)) $tuvarasto = '';
	if (!isset($tupakkaamo)) $tupakkaamo = '';
	if (!isset($tumaa)) $tumaa = '';
	if (!isset($tutoimtapa)) $tutoimtapa = '';
	if (!isset($tutyyppi)) $tutyyppi = '';
	if (!isset($wherelasku)) $wherelasku = '';
	if (!isset($tilaustyyppi)) $tilaustyyppi = '';
	if (!isset($grouplisa)) $grouplisa = '';
	if (!isset($mista)) $mista = '';
	if (!isset($otsikkonro)) $otsikkonro = 0;
	if (!isset($ltun_linkklisa)) $ltun_linkklisa = '';
	if (!isset($kieli)) $kieli = '';
	if (!isset($lopetus)) $lopetus = '';
	if (!isset($tilausnumero)) $tilausnumero = '';
	if (!isset($kilot)) $kilot = '';
	if (!isset($kollit)) $kollit = '';
	if (!isset($kuutiot)) $kuutiot = '';
	if (!isset($lavametri)) $lavametri = '';
	if (!isset($montavalittu)) $montavalittu = '';

	if ($montavalittu == "kylla") {
		$toimitustavan_tarkistin = explode(",", $tunnukset);
		sort($toimitustavan_tarkistin);
		$id = $toimitustavan_tarkistin[0];
		$rakirno = $toimitustavan_tarkistin[0];
	}

	if ($tee == 'add' and $id == 'dummy' and $mista == 'rahtikirja-tulostus.php') {

		list($toimitustapa, $yhtio, $varasto, $crap) = explode("!!!!", $toimitustapa_varasto);

		$ltun_querylisa = '';
		$ltun_linkklisa = '';

		if (count($sel_ltun) > 0) {
			$ltun_querylisa = " and lasku.tunnus in (".implode(",", $sel_ltun).")";

			foreach ($sel_ltun as $ltun_x) {
				$ltun_linkklisa .= "&sel_ltun[]=$ltun_x";
			}
		}

		if ($jv == 'vainjv') {
			$jvehto = " having jv!='' ";
		}
		elseif ($jv == 'eivj') {
			$jvehto = " having jv='' ";
		}
		elseif ($jv == 'vainvak') {
			$vainvakilliset = " JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
								JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.vakkoodi not in ('','0')) ";
		}
		else {
			$jvehto = " ";
		}

		// haetaan kaikki distinct rahtikirjat..
		$query = "	SELECT group_concat(DISTINCT rahtikirjat.otsikkonro) tunnukset, max(rahtikirjat.otsikkonro)+1 rahtikirjanro
					FROM rahtikirjat
					JOIN lasku on (rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio and lasku.tila in ('L','G') and lasku.alatila = 'B' $ltun_querylisa)
					$vainvakilliset
					LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
					LEFT JOIN maksuehto on lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
					LEFT JOIN rahtisopimukset on lasku.ytunnus = rahtisopimukset.ytunnus and rahtikirjat.toimitustapa = rahtisopimukset.toimitustapa and rahtikirjat.rahtisopimus = rahtisopimukset.rahtisopimus
					WHERE rahtikirjat.yhtio	= '$kukarow[yhtio]'
					and rahtikirjat.tulostettu	= '0000-00-00 00:00:00'
					and rahtikirjat.toimitustapa	= '$toimitustapa'
					and rahtikirjat.tulostuspaikka	= '$varasto'
					$jvehto";
		$pakkaustieto_rakir_res = pupe_query($query);
		$pakkaustieto_rakir_row = mysql_fetch_assoc($pakkaustieto_rakir_res);

		$tutkimus = 0; // t�nne tulee luku

		// katotaan ollaanko sy�tetty jotain
		for ($i = 0; $i < count($pakkaus); $i++) {
			if (($kilot[$i] != '' or $kollit[$i] != '' or $kuutiot[$i] != '' or $lavametri[$i] != '') and $subnappi != '') {
				$tutkimus++;
			}
		}

		if ($tutkimus > 0) {
			for ($i = 0; $i < count($pakkaus); $i++) {

				if (($kilot[$i] != '' or $kollit[$i] != '' or $kuutiot[$i] != '' or $lavametri[$i] != '') and $subnappi != '') {

					$kilot[$i]		= str_replace(',', '.', $kilot[$i]);
					$kollit[$i]	 	= str_replace(',', '.', $kollit[$i]);
					$kuutiot[$i]	= str_replace(',', '.', $kuutiot[$i]);
					$lavametri[$i]	= str_replace(',', '.', $lavametri[$i]);

					// katotaan ollaanko sy�tetty useampia kiloja
					$kiloja = explode('/', $kilot[$i]);

					// jos ollaan annettu kauttaviivalla, niin oletetaan, ett� kolleja on niin monta kuin kilojakin sy�tetty
					if (count($kiloja) > 1) {
						$kollit[$i] = 1;
					}

					$rahtikirjanro = $pakkaustieto_rakir_row["rahtikirjanro"];

					// katotaan ollaanko sy�tetty useampia kiloja
					$kiloja = explode('/', $kilot[$i]);

					// jos ollaan annettu kauttaviivalla, niin oletetaan, ett� kolleja on niin monta kuin kilojakin sy�tetty
					if (count($kiloja) > 1) {
						$kollit[$i] = 1;
					}

					foreach ($kiloja as $yksikilo) {
						$query  = "	INSERT into rahtikirjat
									(poikkeava,rahtikirjanro,kilot,kollit,kuutiot,lavametri,merahti,otsikkonro,pakkaus,rahtisopimus,toimitustapa,tulostuspaikka,pakkauskuvaus,pakkauskuvaustark,viesti,pakkaustieto_tunnukset,yhtio) VALUES
									('','$rahtikirjanro','$yksikilo','$kollit[$i]','$kuutiot[$i]','$lavametri[$i]','','0','$pakkaus[$i]','','$toimitustapa','$varasto','$pakkauskuvaus[$i]','$pakkauskuvaustark[$i]','','$pakkaustieto_rakir_row[tunnukset]','$kukarow[yhtio]')";
						$result = pupe_query($query);
					}
				}
			}

			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=rahtikirja-tulostus.php?tee=tulosta&tultiin=koonti_eratulostus_pakkaustiedot&toimitustapa_varasto=$toimitustapa_varasto&jv=$jv&komento=$komento&valittu_rakiroslapp_tulostin=$valittu_rakiroslapp_tulostin&pakkaustieto_rahtikirjanro=$rahtikirjanro$ltun_linkklisa'>";
			exit;
		}
		else {
			$toim = 'lisaa';
		}
	}

	$logistiikka_yhtio = '';
	$logistiikka_yhtiolisa = '';
	$lasku_yhtio_originaali = $kukarow['yhtio'];

	if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
		$logistiikka_yhtio = $konsernivarasto_yhtiot;
		$logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

		if ($lasku_yhtio != '') {
			$kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);
			$yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
		}
	}
	else {
		$logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
	}

	js_popup();

	if (strpos($toim, '_') !== FALSE) {
		$tila = 'G';
	}
	else {
		$tila = 'L';
	}

	if ($toimtila != '') {
		$tila = $toimtila;
	}

	if ($id != 'dummy' and (int) $otsikkonro > 0 or (int) $id > 0) {
		if ((int) $otsikkonro > 0) {
			$hakutunnus	= $otsikkonro;
		}
		else {
			$hakutunnus	= $id;
		}

		$query = "SELECT tila FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus='$hakutunnus' LIMIT 1";
		$result = pupe_query($query);
		$row = mysql_fetch_assoc($result);

		$tila = $row["tila"];
	}

	if ($tee == 'NAYTATILAUS') {
		if ($logistiikka_yhtio != '' and $konsernivarasto_yhtiot != '') {
			echo "<font class='head'>",t("Yhti�n")," $yhtiorow[nimi] ",t("tilaus")," $tunnus:</font><hr>";
		}
		else {
			echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
		}

		require ("raportit/naytatilaus.inc");
		$id=0;
	}

	if ($id == '') $id = 0;

	// jos ollaan lis��m�ss� rahtikirjaa, niin katsotaan onko valitulla toimitustavalla erikoispakkauskielto
	if (count($erikoispakkaus) > 0) {
		$query = "	SELECT *
					FROM toimitustapa
					WHERE yhtio = '$kukarow[yhtio]'
					AND selite = '$toimitustapa'";
		$toimitustapa_res = pupe_query($query);
		$toimitustapa_row = mysql_fetch_assoc($toimitustapa_res);

		if ($toimitustapa_row['erikoispakkaus_kielto'] != '') {
			// jos toimitustavalla on erikoispakkauskielto, niin katsotaan onko jokin sy�tetty pakkaus erikoispakkaus
			foreach ($erikoispakkaus as $key_chk => $erikoispakkaus_kielto_chk) {
				if ($erikoispakkaus_kielto_chk != '' and ($kollit[$key_chk] != '' or $kilot[$key_chk] != '' or $kuutiot[$key_chk] != '' or $lavametri[$key_chk] != '')) {
					echo "<font class='error'>",t("Toimitustavalla on erikoispakkauskielto pakkaukselle")," $erikoispakkaus_kielto_chk!</font><br/>";
					$tee = '';
					break;
				}
			}
		}
	}

	if ($id > 0) {
		$tark = "	SELECT count(DISTINCT lasku.toimitustapa) toimitustapoja,
					count(DISTINCT if(toimitustapa.tulostustapa = 'K' or toimitustapa.tulostustapa = 'L', 'koonti', lasku.liitostunnus)) asiakkaita,
					count(DISTINCT if(toimitustapa.tulostustapa = 'K' or toimitustapa.tulostustapa = 'L', 'koonti', concat(lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa))) toim_osoitteita
					FROM lasku
					JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tunnus in ($tunnukset)";
		$tarkres = pupe_query($tark);
		$tarkistus_row = mysql_fetch_assoc($tarkres);

		if ($tarkistus_row["toimitustapoja"] > 1) {
			echo "<br><font class='error'>".t("VIRHE: Valituilla rahtikirjoilla on eri toimitustavat, ei voida jatkaa")."! </font><br>";
			$id = 0;
		}
		if ($tarkistus_row["asiakkaita"] > 1) {
			echo "<br><font class='error'>".t("VIRHE: Valituilla rahtikirjoilla on eri asiakkaat, ei voida jatkaa")."! </font><br>";
			$id = 0;
		}
		if ($tarkistus_row["toim_osoitteita"] > 1) {
			echo "<br><font class='error'>".t("VIRHE: Valituilla rahtikirjoilla on eri toimitusosoitteet, ei voida jatkaa")."! </font><br>";
			$id = 0;
		}
	}

	if ($id > 0) {

		$vakquery = "	SELECT ifnull(group_concat(DISTINCT tuote.tuoteno), '') vaktuotteet
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.vakkoodi not in ('','0'))
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						AND tilausrivi.otunnus IN ($tunnukset)
						AND tilausrivi.tyyppi = 'L'
						AND tilausrivi.var NOT IN ('P', 'J')";
		$vakresult = pupe_query($vakquery);
		$vakrow = mysql_fetch_assoc($vakresult);

		if ($vakrow['vaktuotteet'] != '') {
			$query = "	SELECT *
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						AND selite = '$toimitustapa'";
			$toimitustapa_res = pupe_query($query);
			$toimitustapa_row = mysql_fetch_assoc($toimitustapa_res);

			if ($toimitustapa_row['vak_kielto'] != '' and $toimitustapa_row['vak_kielto'] != 'K') {
				echo "<font class='message'>",t("Toimituksella on VAK-tuotteita ja toimitustavalla")," $toimitustapa ",t("on VAK-kielto"),"</font><br/>";
				echo "<font class='message'>$toimitustapa ",t("VAK-tuotteet toimitetaan toimitustavalla")," $toimitustapa_row[vak_kielto]</font><br/>";
				$toimitustapa = mysql_real_escape_string($toimitustapa_row['vak_kielto']);
				$tee = '';
			}
			elseif ($toimitustapa_row['vak_kielto'] == 'K') {
				echo "<br><font class='error'>".t("VIRHE: T�m� toimitustapa ei salli VAK-tuotteita")."! ($vakrow[vaktuotteet])</font><br>";
				$tee = '';
			}
		}
	}

	if ($toimitustapa != "") {
		$vak_toim_query = "	SELECT tunnus
							FROM toimitustapa
							WHERE yhtio = '$kukarow[yhtio]'
							AND selite = '$toimitustapa'
							AND jvkielto != ''";
		$vak_toim_result = pupe_query($vak_toim_query);

		if (mysql_num_rows($vak_toim_result) > 0 and $marow["jv"] != "") {
			echo "<br><font class='error'>".t("VIRHE: T�m� toimitustapa ei salli j�lkivaatimuksia")."!</font><br>";
			$tee = '';
		}
	}

	// liitet��n tilaus
	if ($tee == "lisaatilaus") {

		// Haetaan sopivia tilauksia
		$query = "	SELECT DISTINCT lasku.tunnus, lasku.nimi
					FROM lasku use index (tila_index)
					JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.toimitettu = '' and tilausrivi.keratty != '' and tilausrivi.keratty != 'saldoton'
					LEFT JOIN toimitustapa use index (selite_index) ON toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa
					LEFT JOIN varastopaikat on varastopaikat.yhtio = lasku.yhtio and varastopaikat.tunnus = lasku.varasto
					WHERE lasku.$logistiikka_yhtiolisa
					and lasku.tila = 'L'
					and lasku.alatila = 'C'
					and lasku.liitostunnus in ($asiakkaat)
					and ((toimitustapa.nouto is null or toimitustapa.nouto = '') or lasku.vienti != '')";
		$tilre = pupe_query($query);

		echo "<font class='head'>".t("Lis�� tilaus valittuun rahtikirjaan")."</font><hr>";

		echo "<table>";
		echo "<tr>";
		echo "<th>",t("Valittu rahtikirja"),"</th>";
		echo "<td>$rakirno</td>";
		echo "</tr>";
		echo "</table>";
		echo "<br><br>";

		echo "<table>";
		echo "<tr>";
		echo "<th>",t("Tilaus"),"</th>";
		echo "<th>",t("Asiakas"),"</th>";
		echo "</tr>";

		while ($row = mysql_fetch_assoc($tilre)) {
			echo "<tr class='aktiivi'>";
			echo "<td>$row[tunnus]</td>";
			echo "<td>$row[nimi]</td>";

			echo "	<td class='back'><form method='post' action='$PHP_SELF'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='change'>
					<input type='hidden' name='rakirno' value='$rakirno'>
					<input type='hidden' name='lasku_yhtio' value='$lasku_yhtio'>
					<input type='hidden' name='id' value='$id'>
					<input type='hidden' name='tunnukset' value='".$tunnukset,",",$row["tunnus"]."'>
					<input type='submit' value='".t("Liit� valittuun rahtikirjaan")."'>
					</form></td>";

			echo "</tr>";
		}

		echo "</table><br><br>";

		$tee 			= "";
		$rakirno 		= "";
		$lasku_yhtio 	= "";
		$id 			= "";
		$tunnukset 		= "";
	}

	// jos ollaan rahtikirjan esisy�t�ss� niin tehd��n lis�ys v�h�n helpommin
	if ($rahtikirjan_esisyotto != "" and $tee == "add" and $yhtiorow["rahtikirjojen_esisyotto"] == "M") {

		// esisy�tt� sallittu vain N tilassa oleville tilauksille
		$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otsikkonro' and tila='N'";
		$tilre = pupe_query($query);

		if (mysql_num_rows($tilre) == 0) {
			echo "<br><br><font class='error'>".t("Esisy�tt� sallittu vain kesken oleville myyntitilauksille")."! </font><br>";
			exit;
		}

		$tutkimus = 0;

		// dellataan kaikki rahtikirjat t�ll� otsikolla
		$query = "DELETE from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$otsikkonro'";
		$result = pupe_query($query);

		// katotaan ollaanko sy�tetty jotain
		for ($i = 0; $i < count($pakkaus); $i++) {
			if (($kilot[$i] != '' or $kollit[$i] != '' or $kuutiot[$i] != '' or $lavametri[$i] != '') and $subnappi != '') {
				$kilot[$i]		= str_replace(',', '.', $kilot[$i]);
				$kollit[$i]	 	= str_replace(',', '.', $kollit[$i]);
				$kuutiot[$i]	= str_replace(',', '.', $kuutiot[$i]);
				$lavametri[$i]	= str_replace(',', '.', $lavametri[$i]);

				// lis�t��n rahtikirjatiedot (laitetaan poikkeava kentt��n -9 niin tiedet��n ett� esisy�tetty)
				$query  = "INSERT INTO rahtikirjat
							(poikkeava,rahtikirjanro,kilot,kollit,kuutiot,lavametri,merahti,otsikkonro,pakkaus,rahtisopimus,toimitustapa,tulostuspaikka,pakkauskuvaus,pakkauskuvaustark,viesti,yhtio) values
							('-9','$otsikkonro','$kilot[$i]','$kollit[$i]','$kuutiot[$i]','$lavametri[$i]','$merahti','$otsikkonro','$pakkaus[$i]','$rahtisopimus','$toimitustapa','$tulostuspaikka','$pakkauskuvaus[$i]','$pakkauskuvaustark[$i]','$viesti','$kukarow[yhtio]')";
				$result = pupe_query($query);
				$tutkimus++;
			}
		}

		if ($tutkimus == 0 and trim($viesti) != "") {
			//Laitetaan viesti talteen vaikka ei oltais sy�tetty mitt��n kiloja
			$query  = "INSERT INTO rahtikirjat
						(poikkeava,rahtikirjanro,merahti,otsikkonro,rahtisopimus,toimitustapa,tulostuspaikka,viesti,yhtio) values
						('-9','$otsikkonro','$merahti','$otsikkonro','$rahtisopimus','$toimitustapa','$tulostuspaikka','$viesti','$kukarow[yhtio]')";
			$result = pupe_query($query);
			$tutkimus++;
		}

		if ($tutkimus > 0 and $subnappi != '') {
			// rullataan l�pi ja menn��n myyntiin
			$tee  = "";
			$toim = "";
			$id   = 0;
			// karsee h�kki mutta pit�� sanoa, ett� from on laskutatilaus niin p��st��n takasin muokkaukseen
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tilauskasittely/tilaus_myynti.php?toim=$rahtikirjan_esisyotto&aktivoinnista=true&from=LASKUTATILAUS'>";
		}
	}

	// lis�t��n sy�tetty kama rahtikirja-tauluun
	if ($tee == 'add') {
		$apu = 0; //apumuuttuja
		$tutkimus = 0; // t�nne tulee luku

		// katotaan ollaanko sy�tetty jotain
		for ($i = 0; $i < count($pakkaus); $i++) {
			if (($kilot[$i] != '' or $kollit[$i] != '' or $kuutiot[$i] != '' or $lavametri[$i] != '') and $subnappi != '') {
				$tutkimus++;
			}
		}

		// jos ollaan muokkaamassa rivej� poistetaan eka vanhat rahtikirjatiedot..
		if ($tutkimus > 0) {
			if (isset($muutos) and $muutos == 'yes') {
				$query = "DELETE from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$otsikkonro' and rahtikirjanro='$rakirno'";
				$result = pupe_query($query);

				// merkataan tilaus takaisin ker�tyksi, paitsi jos se on vientitilaus jolle vientitiedot on sy�tetty
				$query = "UPDATE lasku set alatila='C' where yhtio='$kukarow[yhtio]' and tunnus='$otsikkonro' and alatila!='E'";
				$result = pupe_query($query);

				//Voi k�yd� niin, ett� rahtikirja on jo tulostunut. Poistetaan mahdolliset tulostusflagit
				$query = "	UPDATE tilausrivi set toimitettu = '', toimitettuaika = ''
							where otunnus = '$otsikkonro' and yhtio = '$kukarow[yhtio]' and var not in ('P','J') and tyyppi='$tila'";
				$result  = pupe_query($query);

				//	Poistetaan kaikki lavaeloitukset
				$query = "	SELECT group_concat(distinct(concat('\'', pakkaus.pakkausveloitus_tuotenumero, '\''))) veloitukset
							FROM pakkaus
							JOIN tuote ON tuote.yhtio = pakkaus.yhtio and tuote.tuoteno=pakkaus.pakkausveloitus_tuotenumero
							WHERE pakkaus.yhtio = '$kukarow[yhtio]'
							and pakkaus.pakkausveloitus_tuotenumero != ''";
				$pakres = pupe_query($query);
				$pakrow = mysql_fetch_assoc($pakres);

				if ($pakrow["veloitukset"]!="") {
					$query = "	DELETE from tilausrivi
								where yhtio = '$kukarow[yhtio]'
								and otunnus = '$otsikkonro'
								and tuoteno IN ($pakrow[veloitukset])";
					$delres = pupe_query($query);
				}
			}

			if ($tila == 'L') {
				$alatilassa = " and lasku.alatila in ('C','E') ";
				$joinmaksuehto = " JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus ";
			}
			else {
				$alatilassa = " and lasku.alatila = 'C' ";
			}

			//	Jostain vuotaa muuttuja ja joudutaan ikuiseen looppiin. T�m� n�ytt�� toimivan
			if (!function_exists("lisaarivi")) {
				function lisaarivi ($otunnus, $tuoteno, $kpl, $hinta = "") {
					global $kukarow, $yhtiorow;

					$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
					$rhire = pupe_query($query);
					$laskurow = mysql_fetch_assoc($rhire);

					$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
					$rhire = pupe_query($query);
					$trow  = mysql_fetch_assoc($rhire);

					$varataan_saldoa 	= "EI";
					$kukarow["kesken"]	= $otunnus;
					$korvaavakielto 	= "ON";
					$toimaika			= $laskurow["toimaika"];
					$kerayspvm			= $laskurow["kerayspvm"];
					$jtkielto 			= $laskurow['jtkielto'];

					require("tilauskasittely/lisaarivi.inc");

					//	Merkataan t�m� rivi ker�tyksi ja toimitetuksi..
					if ($lisatyt_rivit1[0] != '') {
						$lisatty_rivitunnus = $lisatyt_rivit1[0];
					}
					else {
						$lisatty_rivitunnus = $lisatyt_rivit2[0];
					}

					if ($lisatty_rivitunnus != '') {
						$query = "	UPDATE tilausrivi set
									kerattyaika	= now(),
									keratty		= '$kukarow[kuka]'
									where yhtio = '$kukarow[yhtio]'
									and tunnus = '$lisatyt_rivit1[0]'";
						$updres = pupe_query($query);
					}
				}
			}

			$having = "";

			if ($yhtiorow['pakkaamolokerot'] != '') {
				$having = " HAVING ((rahtikirjat.otsikkonro is null or (rahtikirjat.otsikkonro is not null and lasku.pakkaamo > 0)
							and (rahtikirjat.pakkaus = 'KOLLI' or rahtikirjat.pakkaus = 'Rullakko')) or rahtikirjat.poikkeava = -9) and ";
			}
			else {
				$having = " HAVING (rahtikirjat.otsikkonro is null or rahtikirjat.poikkeava = -9) and ";
			}

			// Saadaanko n�ille tilauksille sy�tt�� rahtikirjoja
			$query = "	SELECT
						lasku.yhtio,
						rahtikirjat.otsikkonro,
						rahtikirjat.poikkeava,
						toimitustapa.nouto,
						lasku.vienti,
						rahtikirjat.pakkaus,
						lasku.pakkaamo
						FROM lasku use index (tila_index)
						JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.toimitettu = '' and tilausrivi.keratty != ''
						$joinmaksuehto
						LEFT JOIN toimitustapa use index (selite_index) ON toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa
						LEFT JOIN rahtikirjat use index (otsikko_index) ON rahtikirjat.otsikkonro=lasku.tunnus and rahtikirjat.yhtio=lasku.yhtio
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = '$tila'
						$alatilassa
						and lasku.tunnus in ($tunnukset)
						$having ((toimitustapa.nouto is null or toimitustapa.nouto='') or lasku.vienti!='')";
			$tilre = pupe_query($query);

			if (mysql_num_rows($tilre) == 0) {
				echo "<br><br><font class='error'> ".t("Taisit painaa takaisin tai p�ivit� nappia. N�in ei saa tehd�")."! </font><br>";
				exit;
			}

			echo "<font class='head'>".t("Lis�ttiin rahtikirjaan")."</font><hr>";
			echo "<table>";

			if ($yhtiorow['rahti_ja_kasittelykulut_kasin'] != '') {
				$k_rahtikulut = str_replace(',', '.', $k_rahtikulut);
				$k_kasitkulut = str_replace(',', '.', $k_kasitkulut);

				if ($k_rahtikulut > 0) {
					$query = "	UPDATE tilausrivi
								SET tyyppi='D',
								kommentti = concat(kommentti, ' $kukarow[kuka] muutti rahtikuluja rahtikirjan sy�t�ss�.')
								WHERE yhtio='$kukarow[yhtio]'
								and otunnus='$otsikkonro'
								and tuoteno='$yhtiorow[rahti_tuotenumero]'
								and uusiotunnus=0
								and tyyppi != 'D'";
					$result = pupe_query($query);

					lisaarivi($otsikkonro, $yhtiorow["rahti_tuotenumero"], 1, $k_rahtikulut);
				}

				if ($k_kasitkulut > 0) {
					$query = "	UPDATE tilausrivi
								SET tyyppi='D',
								kommentti = concat(kommentti, ' $kukarow[kuka] muutti k�sittelykuluja rahtikirjan sy�t�ss�.')
								WHERE yhtio='$kukarow[yhtio]'
								and otunnus='$otsikkonro'
								and tuoteno='$yhtiorow[kasittelykulu_tuotenumero]'
								and uusiotunnus=0
								and tyyppi != 'D'";
					$result = pupe_query($query);

					lisaarivi($otsikkonro, $yhtiorow["kasittelykulu_tuotenumero"], 1, $k_kasitkulut);
				}
			}

			for ($i=0; $i<count($pakkaus); $i++) {

				// katotaan ett� ollaan sy�tetty jotain
				if ($tutkimus > 0) {

					// ja insertataan vaan jos se on erisuurta ku nolla (n�in voidaan nollalla tai spacella tyhjent�� kentti�)
					if (($kilot[$i] != '' or $kollit[$i] != '' or $kuutiot[$i] != '' or $lavametri[$i] != '') and $subnappi != '') {

						$kilot[$i]		= str_replace(',', '.', $kilot[$i]);
						$kollit[$i]	 	= str_replace(',', '.', $kollit[$i]);
						$kuutiot[$i]	= str_replace(',', '.', $kuutiot[$i]);
						$lavametri[$i]	= str_replace(',', '.', $lavametri[$i]);

						if ($rakirno == '') {
							$query = "SELECT max(rahtikirjanro) rakirno from rahtikirjat where yhtio='$kukarow[yhtio]' and (otsikkonro='$otsikkonro' or otsikkonro='0')";
							$result = pupe_query($query);
							$rakirow = mysql_fetch_assoc($result);
							$rakirno = $rakirow["rakirno"]+1;
						}

						//T�ss� otetaan kaikkien tilausten tunnukset joille sy�tet��n rahtikirjan tiedot
						$tilaukset = explode(',', $tunnukset);

						// katotaan ollaanko sy�tetty useampia kiloja
						$kiloja = explode('/', $kilot[$i]);

						// jos ollaan annettu kauttaviivalla, niin oletetaan, ett� kolleja on niin monta kuin kilojakin sy�tetty
						if (count($kiloja) > 1) {
							$kollit[$i] = 1;
						}

						foreach ($tilaukset as $otsikkonro) {

							foreach ($kiloja as $yksikilo) {
								$query  = "	INSERT into rahtikirjat
											(poikkeava,rahtikirjanro,kilot,kollit,kuutiot,lavametri,merahti,otsikkonro,pakkaus,rahtisopimus,toimitustapa,tulostuspaikka,pakkauskuvaus,pakkauskuvaustark,viesti,yhtio) VALUES
											('','$rakirno','$yksikilo','$kollit[$i]','$kuutiot[$i]','$lavametri[$i]','$merahti','$otsikkonro','$pakkaus[$i]','$rahtisopimus','$toimitustapa','$tulostuspaikka','$pakkauskuvaus[$i]','$pakkauskuvaustark[$i]','$viesti','$kukarow[yhtio]')";
								$result = pupe_query($query);
							}

							if ($kollit[$i] == '')		$kollit[$i]		= 0;
							if ($kilot[$i] == '')		$kilot[$i]		= 0;
							if ($lavametri[$i] == '')	$lavametri[$i]	= 0;
							if ($kuutiot[$i] == '')		$kuutiot[$i]	= 0;

							// Lis�t��n my�s pakkausveloitus, mik�li sellainen on annettu
							$query = "	SELECT pakkaus.*
										FROM pakkaus
										JOIN tuote ON tuote.yhtio = pakkaus.yhtio and tuote.tuoteno = pakkaus.pakkausveloitus_tuotenumero
										WHERE pakkaus.yhtio = '$kukarow[yhtio]'
										and pakkaus.pakkaus = '$pakkaus[$i]'
										and pakkaus.pakkauskuvaus = '$pakkauskuvaus[$i]'
										and pakkaus.pakkausveloitus_tuotenumero != ''";
							$pakres = pupe_query($query);

							if (mysql_num_rows($pakres) == 1) {
								$pakrow = mysql_fetch_assoc($pakres);

								lisaarivi($otsikkonro, $pakrow["pakkausveloitus_tuotenumero"], $kollit[$i]);
							}

							if ($kilot[$i]!=0 or $kollit[$i]!=0 or $kuutiot[$i]!=0 or $lavametri[$i]!=0) {
								echo "<tr><td>$pakkauskuvaus[$i]</td><td>$pakkaus[$i]</td><td>$pakkauskuvaustark[$i]</td><td align='right'>$kollit[$i] kll</td><td align='right'>$kilot[$i] kg</td><td align='right'>$kuutiot[$i] m&sup3;</td><td align='right'>$lavametri[$i] m</td></tr>";
							}

							// Vain ekalle tilaukselle lis�t��n tiedot
							$kollit[$i]		= 0;
							$kilot[$i] 		= 0;
							$lavametri[$i] 	= 0;
							$kuutiot[$i] 	= 0;
							$kiloja			= array("0");

							$apu++;
						}

					}

					// menn��n valitsemaan seuraavaa
					$id = 0;
				}
			}

			echo "</table><br>";
		}

		// jos lis�ttiin jotain, merkataan rahtikirjatiedot sy�tetyksi..
		if ($apu > 0) {

			echo "<br>";

			// Haetaan laskun kaikki tiedot ja katsotaan onko kyseess� j�kivaatimus
			$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otsikkonro'";
			$result   = pupe_query($query);
			$laskurow = mysql_fetch_assoc($result);

			//Vientilaskuille alatilaa ei saa aina p�ivitt��
			if ($laskurow['alatila'] == 'E' or ($yhtiorow['intrastat_kaytossa'] == 'V' and $laskurow['vienti'] != '')) {
				$alatila = "E";
			}
			else {
				$alatila = "B";
			}

			// P�ivitet��n laskuille sy�tetyt tiedot
			$query = "	UPDATE lasku SET
						alatila			= '$alatila',
						kohdistettu		= '$merahti',
						rahtisopimus	= '$rahtisopimus',
						toimitustapa	= '$toimitustapa'
						where yhtio = '$kukarow[yhtio]' and tunnus in ($tunnukset)";
			$updateres = pupe_query($query);

			//Ainostaan tulostusaluuen mukaan splittaantuneet tilaukset yhdistet��n
			if ($yhtiorow["splittauskielto"] == "" and strpos($tunnukset,',') !== false and $yhtiorow['pakkaamolokerot'] != '') {

				$otsikko_tunnarit = explode(',',$tunnukset);
				sort($otsikko_tunnarit);

				$query = "	UPDATE tilausrivi SET
							otunnus		= '$otsikko_tunnarit[0]'
							where yhtio = '$kukarow[yhtio]' and otunnus in ($tunnukset)";
				$updateres = pupe_query($query);

				$query = "	UPDATE lasku SET
							tila		= 'D',
							comments	= concat(comments, ' $kukarow[kuka] poisti otsikot rahtikirjan sy�t�ss�. rivit liitettiin otsikolle $otsikko_tunnarit[0] ', now())
							where yhtio = '$kukarow[yhtio]' and tunnus in($tunnukset) and tunnus != $otsikko_tunnarit[0]";
				$updateres = pupe_query($query);

				$laskurow['tunnus'] = $otsikko_tunnarit[0];
			}

			// Katsotaan pit�isik� t�m� rahtikirja tulostaa heti...
			$query = "	SELECT *
						from toimitustapa
						where yhtio = '$kukarow[yhtio]'
						and selite = '$toimitustapa'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 1) {

				$row = $toimitustaparow = mysql_fetch_assoc($result);

				// S�ilytet��n t�m�
				$laskurow_rakirsyotto_original = $laskurow;

				// jos meill� on tuonti-itrastattiin kuuluva tilaus ja toimitustapaa on muutettu p�ivitet��n intrastat oletukset toimitustavan takaa
				if ($laskurow["ultilno"] == '-2' and $laskurow["toimitustapa"] != $toimitustapa) {
					$query = "	UPDATE lasku SET
								aktiivinen_kuljetus 				= '$row[aktiivinen_kuljetus]',
								aktiivinen_kuljetus_kansallisuus	= '$row[aktiivinen_kuljetus_kansallisuus]',
								bruttopaino 						= '$row[bruttopaino]',
								kauppatapahtuman_luonne 			= '$row[kauppatapahtuman_luonne]',
								kontti								= '$row[kontti]',
								kuljetusmuoto						= '$row[kuljetusmuoto]',
								lisattava_era 						= '$row[lisattava_era]',
								poistumistoimipaikka 				= '$row[poistumistoimipaikka]',
								poistumistoimipaikka_koodi 			= '$row[poistumistoimipaikka_koodi]',
								sisamaan_kuljetus					= '$row[sisamaan_kuljetus]',
								sisamaan_kuljetusmuoto  			= '$row[sisamaan_kuljetusmuoto]',
								sisamaan_kuljetus_kansallisuus		= '$row[sisamaan_kuljetus_kansallisuus]',
								vahennettava_era 					= '$row[vahennettava_era]'
								where yhtio = '$kukarow[yhtio]' and tunnus in ($tunnukset)";
					$updateres = pupe_query($query);
				}

				// t�m� toimitustapa pit�isi tulostaa nyt..
				if ($row['nouto']=='' and ($row['tulostustapa']=='H' or $row['tulostustapa']=='K')) {
					// rahtikirjojen tulostus vaatii seuraavat muuttujat:

					// $toimitustapa_varasto	toimitustavan selite!!!!varastopaikan tunnus
					// $tee						t�ss� pit�� olla teksti tulosta

					$toimitustapa_varasto = $toimitustapa."!!!!".$kukarow['yhtio']."!!!!".$tulostuspaikka;
					$tee				  = "tulosta";

					require ("rahtikirja-tulostus.php");

				} // end if tulostetaanko heti

				// Haetaan laskun kaikki tiedot uudestaan koska rahtikirja-tulostus.php ilikirjaa muuttujamme
				$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$laskurow_rakirsyotto_original[tunnus]'";
				$lasresult   = pupe_query($query);
				$laskurow = mysql_fetch_assoc($lasresult);

			} // end if l�ytyk� toimitustapa

			if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '' and $mista == 'keraa.php') {
				$keraaseen = 'mennaan';
			}

			// Katotaan haluttiinko osoitelappuja tai l�hetteit�
			$oslappkpl 		= (int) $oslappkpl;
			$termoslappkpl 	= (int) $termoslappkpl;
			$lahetekpl 		= (int) $lahetekpl;
			$dgdkpl			= (int) $dgdkpl;

			//tulostetaan faili ja valitaan sopivat printterit
			if ($laskurow['pakkaamo'] > 0 and $laskurow['varasto'] != '' and $laskurow['tulostusalue'] != '') {
				$query = "	SELECT pakkaamo.printteri1, pakkaamo.printteri3, varastopaikat.printteri5
							from pakkaamo
							join varastopaikat ON pakkaamo.yhtio = varastopaikat.yhtio and varastopaikat.tunnus = '$laskurow[varasto]'
							where pakkaamo.yhtio='$kukarow[yhtio]'
							and pakkaamo.tunnus='$laskurow[pakkaamo]'
							order by pakkaamo.tunnus";
			}
			elseif ($laskurow['tulostusalue'] != '' and $laskurow['varasto'] != '') {
				$query = "	SELECT varaston_tulostimet.printteri1, varaston_tulostimet.printteri3, varastopaikat.printteri5
							FROM varaston_tulostimet
							JOIN varastopaikat ON (varaston_tulostimet.yhtio = varastopaikat.yhtio and varastopaikat.tunnus = '$laskurow[varasto]')
							WHERE varaston_tulostimet.yhtio = '$kukarow[yhtio]'
							AND varaston_tulostimet.nimi = '$laskurow[tulostusalue]'
							AND varaston_tulostimet.varasto = '$laskurow[varasto]'
							ORDER BY varaston_tulostimet.prioriteetti, varaston_tulostimet.alkuhyllyalue";
			}
			elseif ($laskurow["varasto"] == '') {
				$query = "	SELECT *
							from varastopaikat
							where yhtio = '$kukarow[yhtio]'
							order by alkuhyllyalue,alkuhyllynro
							limit 1";
			}
			else {
				$query = "	SELECT *
							from varastopaikat
							where yhtio = '$kukarow[yhtio]' and tunnus='$laskurow[varasto]'
							order by alkuhyllyalue,alkuhyllynro";
			}
			$prires = pupe_query($query);

			if (mysql_num_rows($prires) > 0) {

				$prirow = mysql_fetch_assoc($prires);

				// k�teinen muuttuja viritet��n tilaus-valmis.inc:iss� jos maksuehto on k�teinen
				// ja silloin pit�� kaikki l�hetteet tulostaa aina printteri5:lle (lasku printteri)
				if ($kateinen == 'X') {
					$apuprintteri = $prirow['printteri5']; // laskuprintteri
				}
				else {
					$apuprintteri = $rakirsyotto_lahete_tulostin;
				}

				//haetaan l�hetteen tulostuskomento
				$query   = "SELECT * from kirjoittimet where yhtio = '$kukarow[yhtio]' and tunnus = '$apuprintteri'";
				$kirres  = pupe_query($query);
				$kirrow  = mysql_fetch_assoc($kirres);
				$komento = $kirrow['komento'];

				//haetaan osoitelapun tulostuskomento
				$query  = "SELECT * from kirjoittimet where yhtio = '$kukarow[yhtio]' and tunnus = '$rakirsyotto_oslapp_tulostin'";
				$kirres = pupe_query($query);
				$kirrow = mysql_fetch_assoc($kirres);
				$oslapp = $kirrow['komento'];

				//haetaan terminaaliosoitelapun tulostuskomento
				$query  = "SELECT * from kirjoittimet where yhtio = '$kukarow[yhtio]' and tunnus = '$rakirsyotto_termoslapp_tulostin'";
				$kirres = pupe_query($query);
				$kirrow = mysql_fetch_assoc($kirres);
				$termoslapp = $kirrow['komento'];

				//haetaan DGD-lomakkeen tulostuskomento
				$query  = "SELECT * from kirjoittimet where yhtio = '$kukarow[yhtio]' and tunnus = '$rakirsyotto_dgd_tulostin'";
				$kirres = pupe_query($query);
				$kirrow = mysql_fetch_assoc($kirres);
				$dgdkomento = $kirrow['komento'];
			}

			if ($rakirsyotto_lahete_tulostin != '' and $komento != "" and $lahetekpl > 0) {

				if (isset($dgdlle_tunnukset) and $dgdlle_tunnukset != "") {
					$lahetteet = explode(",", $dgdlle_tunnukset);
				}
				else {
					$lahetteet = array($laskurow["tunnus"]);
				}

				foreach ($lahetteet as $index => $laskutunnus) {

					// Haetaan laskun kaikki tiedot uudestaan koska haluamme l�hetteen per yhdistetty rahtikirja
					$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$laskutunnus'";
					$lasresult = pupe_query($query);
					$laskurow = mysql_fetch_assoc($lasresult);

					$params = array(
						'laskurow' => $laskurow,
						'sellahetetyyppi' => $sellahetetyyppi,
						'extranet_tilausvahvistus' => $extranet_tilausvahvistus,
						'naytetaanko_rivihinta' => $naytetaanko_rivihinta,
						'tee' => $tee,
						'toim' => $toim,
						'query_ale_lisa' => $query_ale_lisa,
						'komento' => $komento
					);

					pupesoft_tulosta_lahete($params);
				}
			}

			// Tulostetaan osoitelappu
			if ($rakirsyotto_oslapp_tulostin != "" and $oslapp != '' and $oslappkpl > 0) {

				if (isset($dgdlle_tunnukset) and $dgdlle_tunnukset != "") {
					$osoitelaput = explode(",", $dgdlle_tunnukset);
				}
				else {
					$osoitelaput = array($laskurow["tunnus"]);
				}

				foreach ($osoitelaput as $index => $laskutunnus) {

					$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$laskutunnus'";
					$osresult   = pupe_query($query);
					$laskurow = mysql_fetch_assoc($osresult);

					$tunnus = $laskurow["tunnus"];

					$oslaput_email = 1;

					if ($oslappkpl > 0 and $oslappkpl != '' and $oslapp != 'email') {
						$oslapp .= " -#$oslappkpl ";
					}
					elseif ($oslappkpl > 0 and $oslappkpl != '' and $oslapp == 'email') {
						$oslaput_email = $oslappkpl;
					}

					for ($i = 0; $i < $oslaput_email; $i++) {
						if ($toimitustaparow['osoitelappu'] == 'intrade') {
							require('tilauskasittely/osoitelappu_intrade_pdf.inc');
						}
						else {
							require ("tilauskasittely/osoitelappu_pdf.inc");
						}
					}
					unset($tunnus);
				}
			}

			// Tulostetaan terminaaliosoitelappu
			if ($rakirsyotto_termoslapp_tulostin != "" and $termoslapp != '' and $termoslappkpl > 0) {

				if (isset($dgdlle_tunnukset) and $dgdlle_tunnukset != "") {
					$terminaaliosoitelaput = explode(",", $dgdlle_tunnukset);
				}
				else {
					$terminaaliosoitelaput = array($laskurow["tunnus"]);
				}

				foreach ($terminaaliosoitelaput as $index => $laskutunnus) {
					$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$laskutunnus'";
					$lasresult   = pupe_query($query);
					$laskurow = mysql_fetch_assoc($lasresult);

					$tunnus = $laskurow["tunnus"];

					$oslaput_email = 1;

					if ($termoslappkpl > 0 and $termoslappkpl != '' and $termoslapp != 'email') {
						$termoslapp .= " -#$termoslappkpl ";
					}
					elseif ($termoslappkpl > 0 and $termoslappkpl != '' and $termoslapp == 'email') {
						$oslaput_email = $termoslappkpl;
					}

					$tiedot = "toimitusta";

					for ($i = 0; $i < $oslaput_email; $i++) {
						if ($toimitustaparow['osoitelappu'] == 'intrade') {
							require('tilauskasittely/osoitelappu_intrade_pdf.inc');
						}
						else {
							require ("tilauskasittely/osoitelappu_pdf.inc");
						}
					}
					unset($tunnus);
				}
			}

			// Tulostetaan DGD
			if ($rakirsyotto_dgd_tulostin != "" and $dgdkomento != '' and $dgdkpl > 0) {
				$tunnus = $laskurow["tunnus"];

				if ($dgdkpl > 0 and $dgdkpl != '' and $dgdkomento != 'email') {
					$dgdkomento .= " -#$dgdkpl ";
				}

				require ("tilauskasittely/tulosta_dgd.inc");

				$params_dgd = array(
				'kieli'			=> 'en',
				'laskurow'		=> $laskurow,
				'page'			=> NULL,
				'pdf'			=> NULL,
				'row'			=> NULL,
				'sivu'			=> 0,
				'tee'			=> $tee,
				'toim'			=> $toim,
				'norm'			=> $norm,
				);

				// Aloitellaan l�hetteen teko
				$params_dgd = alku_dgd($params_dgd);
				$params_dgd = rivi_dgd($params_dgd);
				$params_dgd = loppu_dgd($params_dgd);

				//tulostetaan sivu
				$params_dgd["komento"] = $dgdkomento;
				print_pdf_dgd($params_dgd);
			}

			echo "<br><br>";
		}
	}

	// meill� ei ole valittua tilausta
	if (($toim == 'lisaa' or $toim == 'lisaa_siirto') and $id == 0 and (string) $id != 'dummy') {

		if ($lasku_yhtio_originaali != '' and $kukarow['yhtio'] != $lasku_yhtio_originaali) {
			$logistiikka_yhtio = $konsernivarasto_yhtiot;
			$logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

			$yhtiorow = hae_yhtion_parametrit($lasku_yhtio_originaali);
			$kukarow['yhtio'] = $lasku_yhtio_originaali;
		}

		echo "<font class='head'>".t("Rahtikirjojen sy�tt�")."</font><hr>";

		$formi  = "find";
		$kentta = "etsi";

		echo "<table>";
		echo "<form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='toimtila' value='$tila'>";
		echo "<input type='hidden' name='text' value='etsi'>";
		echo "<input type='hidden' id='jarj' name='jarj' value='$jarj'>";
		echo "<tr><td>".t("Valitse varasto:")."</td><td><select name='tuvarasto' onchange='submit()'>";

		$query = "	SELECT tunnus, nimitys, yhtio
					FROM varastopaikat
					WHERE $logistiikka_yhtiolisa
					ORDER BY yhtio, tyyppi, nimitys";
		$result = pupe_query($query);

		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

		while ($row = mysql_fetch_assoc($result)){
			$sel = '';
			if (($row["tunnus"] == $tuvarasto) or ((isset($kukarow["varasto"]) and (int) $kukarow["varasto"] > 0 and in_array($row["tunnus"], explode(",", $kukarow['varasto']))) and $tuvarasto=='')) {
				$sel = 'selected';
				$tuvarasto = $row["tunnus"];
			}
			echo "<option value='$row[tunnus]' $sel>$row[nimitys]";
			if ($logistiikka_yhtio != '') {
				echo " ($row[yhtio])";
			}
			echo "</option>";
		}
		echo "</select>";

		$query = "	SELECT distinct maa
					FROM varastopaikat
					WHERE $logistiikka_yhtiolisa
					and maa != ''
					ORDER BY maa";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 1) {
			echo "<select name='tumaa' onchange='submit()'>";
			echo "<option value=''>".t("Kaikki")."</option>";

			while ($row = mysql_fetch_assoc($result)){
				$sel = '';
				if ($row["maa"] == $tumaa) {
					$sel = 'selected';
					$tumaa = $row["maa"];
				}
				echo "<option value='$row[maa]' $sel>$row[maa]</option>";
			}
			echo "</select>";
		}

		echo "</td>";

		echo "<td>".t("Valitse tilaustyyppi:")."</td><td><select name='tutyyppi' onchange='submit()'>";

		$sela = $selb = $selc = "";

		if ($tutyyppi == "NORMAA") {
			$sela = "SELECTED";
		}
		if ($tutyyppi == "ENNAKK") {
			$selb = "SELECTED";
		}
		if ($tutyyppi == "JTTILA") {
			$selc = "SELECTED";
		}
		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";
		echo "<option value='NORMAA' $sela>".t("N�yt� normaalitilaukset")."</option>";
		echo "<option value='ENNAKK' $selb>".t("N�yt� ennakkotilaukset")."</option>";
		echo "<option value='JTTILA' $selc>".t("N�yt� jt-tilaukset")."</option>";

		echo "</select></td></tr>";

		echo "<tr><td>".t("Valitse toimitustapa:")."</td><td><select name='tutoimtapa' onchange='submit()'>";

		$query = "	SELECT selite, min(tunnus) tunnus
					FROM toimitustapa
					WHERE $logistiikka_yhtiolisa
					GROUP BY selite
					ORDER BY selite";
		$result = pupe_query($query);

		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

		while($row = mysql_fetch_assoc($result)){
			$sel = '';
			if($row["selite"] == $tutoimtapa) {
				$sel = 'selected';
				$tutoimtapa = $row["selite"];
			}
			echo "<option value='$row[selite]' $sel>".t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV")."</option>";
		}

		echo "</select></td>";

		if ($yhtiorow['pakkaamolokerot'] != '') {
			echo "<td>".t("Valitse pakkaamo:")."</td><td><select name='tupakkaamo' onchange='submit()'>";

			$query = "	SELECT distinct nimi
						FROM pakkaamo
						WHERE $logistiikka_yhtiolisa
						ORDER BY nimi";
			$result = pupe_query($query);

			echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

			while($row = mysql_fetch_assoc($result)){
				$sel = '';
				if ($tupakkaamo == '') {
					if ($row['nimi'] == $kukarow['oletus_pakkaamo']) {
						$sel = 'selected';
						$tupakkaamo = $row['nimi'];
					}
				}
				else {
					if ($row['nimi'] == $tupakkaamo) {
						$sel = 'selected';
						$tupakkaamo = $row['nimi'];
					}
				}

				echo "<option value='$row[nimi]' $sel>".$row["nimi"]."</option>";
			}

			echo "</select></td></tr><tr>";
		}

		echo "<td>".t("Etsi tilausta").":</td><td><input type='text' name='etsi'>";
		echo "<input type='Submit' value='".t("Etsi")."'></form></td></tr>";

		echo "</table>";

		$haku = '';

		if (!is_numeric($etsi) and $etsi != '') {
			$haku .= "and lasku.nimi LIKE '%$etsi%'";
		}

		if (is_numeric($etsi) and $etsi != '') {

			//etsit��n my�s splittaantuneet
			$query = "	SELECT distinct vanhatunnus
						FROM lasku
						WHERE $logistiikka_yhtiolisa
						AND tunnus = '$etsi'
						AND tila = '$tila'
						AND alatila = 'C'";
			$vanhatre = pupe_query($query);
			$vanhatrow = mysql_fetch_assoc($vanhatre);

			if ($vanhatrow['vanhatunnus'] == 0) {
				$haku .= "and lasku.tunnus = '$etsi'";
			}
			else {
				$query = "	SELECT group_concat(tunnus SEPARATOR ',') tunnukset
				  			FROM lasku
							WHERE $logistiikka_yhtiolisa
							AND vanhatunnus = $vanhatrow[vanhatunnus]
							AND tila = '$tila'
							AND alatila = 'C'";
				$etsire = pupe_query($query);
				$etsirow = mysql_fetch_assoc($etsire);

				if ($etsirow["tunnukset"] != '') {
					$haku .= "and lasku.tunnus in($etsirow[tunnukset])";
				}
				else {
					echo "<font class='message'>".t("Sopivia tilauksia ei l�ytynyt")."...</font><br><br>";
				}
			}

		}

		if ($tuvarasto != '' and $tuvarasto != 'KAIKKI') {
			$haku .= " and lasku.varasto='$tuvarasto' ";
		}

		if ($yhtiorow['pakkaamolokerot'] != '') {
			if ($tupakkaamo == '' and $kukarow['oletus_pakkaamo'] != '') {
				$query = "	SELECT group_concat(tunnus SEPARATOR ',') tunnukset
				  			FROM pakkaamo
							WHERE $logistiikka_yhtiolisa
							AND nimi = '$kukarow[oletus_pakkaamo]'";
				$etsire = pupe_query($query);
				$etsirow = mysql_fetch_assoc($etsire);

				$haku .= " and lasku.pakkaamo in($etsirow[tunnukset])";

			}
			elseif ($tupakkaamo != '' and $tupakkaamo != 'KAIKKI') {
				$query = "	SELECT group_concat(tunnus SEPARATOR ',') tunnukset
				  			FROM pakkaamo
							WHERE $logistiikka_yhtiolisa
							AND nimi = '$tupakkaamo'";
				$etsire = pupe_query($query);
				$etsirow = mysql_fetch_assoc($etsire);

				$haku .= " and lasku.pakkaamo in($etsirow[tunnukset])";
			}
		}


		if ($tumaa != '') {
			$query = "	SELECT group_concat(tunnus) tunnukset
						FROM varastopaikat
						WHERE maa != '' and $logistiikka_yhtiolisa and maa = '$tumaa'";
			$maare = pupe_query($query);
			$maarow = mysql_fetch_assoc($maare);
			$haku .= " and lasku.varasto in ($maarow[tunnukset]) ";
		}

		if ($tutoimtapa != '' and $tutoimtapa != 'KAIKKI') {
			$haku .= " and lasku.toimitustapa='$tutoimtapa' ";
		}

		if ($tutyyppi != '' and $tutyyppi != 'KAIKKI') {
			if ($tutyyppi == "NORMAA") {
				$haku .= " and lasku.clearing='' ";
			}
			elseif($tutyyppi == "ENNAKK") {
				$haku .= " and lasku.clearing='ENNAKKOTILAUS' ";
			}
			elseif($tutyyppi == "JTTILA") {
				$haku .= " and lasku.clearing='JT-TILAUS' ";
			}
		}

		//jos myyntitilaus niin halutaan maksuehto mukaan
		if ($tila == 'L') {
			$selectmaksuehto 	= " if(maksuehto.jv='', 'OK', lasku.tunnus) jvgrouppi, ";
			$joinmaksuehto 		= " JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus ";
			$groupmaksuehto 	= " jvgrouppi, ";
		}
		else {
			$wherelasku = " and lasku.toim_nimi != '' ";
		}

		$lisawhere = "";

		if ($yhtiorow['kerayserat'] == 'K') {
			$lisawhere = " and ((rahtikirjat.otsikkonro is null or rahtikirjat.otsikkonro > 0) or rahtikirjat.poikkeava = -9) ";
		}
		elseif ($yhtiorow['pakkaamolokerot'] != '') {
			$lisawhere = " and ((rahtikirjat.otsikkonro is null or (rahtikirjat.otsikkonro is not null and lasku.pakkaamo > 0)
						   and (rahtikirjat.pakkaus = 'KOLLI' or rahtikirjat.pakkaus = 'Rullakko')) or rahtikirjat.poikkeava = -9) ";
		}
		else {
			$lisawhere = " and (rahtikirjat.otsikkonro is null or rahtikirjat.poikkeava = -9) ";
		}

		if ($yhtiorow["splittauskielto"] == "" and $yhtiorow['pakkaamolokerot'] != '') {
			$grouplisa = ", lasku.vanhatunnus, lasku.varasto, lasku.pakkaamo ";
			$selecttoimitustapaehto = " toimitustapa.tunnus kimppakyyti, ";
		}
		else {
			$selecttoimitustapaehto = " if (toimitustapa.tulostustapa='K', toimitustapa.tunnus, lasku.tunnus) kimppakyyti, ";
		}

		if($jarj != "") {
			$jarjx = " ORDER BY $jarj";
		}
		else {
			$jarjx = " ORDER BY laadittu";
		}

		// Haetaan sopivia tilauksia
		$query = "	SELECT
					lasku.yhtio yhtio,
					lasku.yhtio_nimi yhtio_nimi,
					lasku.toimitustapa toimitustapa,
					toimitustapa.nouto nouto,
					$selectmaksuehto
					$selecttoimitustapaehto
					lasku.vienti,
					date_format(lasku.luontiaika, '%Y-%m-%d') laadittux,
					date_format(lasku.toimaika, '%Y-%m-%d') toimaika,
					min(lasku.vanhatunnus) vanhatunnus,
					min(lasku.pakkaamo) pakkaamo,
					min(lasku.varasto) varasto,
					min(lasku.tunnus) tunnus,
					GROUP_CONCAT(distinct lasku.tunnus order by lasku.tunnus) tunnukset,
					GROUP_CONCAT(distinct ytunnus) ytunnus,
					min(tilausrivi.kerattyaika) kerattyaika,
					min(lasku.luontiaika) luontiaika,
					min(lasku.h1time) h1time,
					min(lasku.lahetepvm) lahetepvm,
					min(lasku.kerayspvm) kerayspvm,
					min(lasku.tunnus) mintunnus,
					if(lasku.tila='L',GROUP_CONCAT(distinct concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark) order by concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark) SEPARATOR '<br>'), GROUP_CONCAT(distinct lasku.nimi)) nimi,
					GROUP_CONCAT(distinct lasku.laatija order by lasku.laatija SEPARATOR '<br>') laatija,
					group_concat(DISTINCT concat_ws('\n\n', if(comments!='',concat('".t("L�hetteen lis�tiedot").":\n',comments),NULL), if(sisviesti2!='',concat('".t("Ker�yslistan lis�tiedot").":\n',sisviesti2),NULL)) SEPARATOR '\n') ohjeet,
					min(if(lasku.hyvaksynnanmuutos = '', 'X', lasku.hyvaksynnanmuutos)) prioriteetti,
					min(if(lasku.clearing = '', 'N', if(lasku.clearing = 'JT-TILAUS', 'J', if(lasku.clearing = 'ENNAKKOTILAUS', 'E', '')))) t_tyyppi,
					GROUP_CONCAT(DISTINCT varastopaikat.nimitys) varastonimi,
					GROUP_CONCAT(lasku.pakkaamo order by lasku.tunnus) pakkaamot,
					sum(rahtikirjat.kollit) kollit,
					count(distinct lasku.tunnus) tunnukset_lkm
					FROM lasku use index (tila_index)
					JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.toimitettu = '' and tilausrivi.keratty != '' and tilausrivi.keratty != 'saldoton'
					$joinmaksuehto
					LEFT JOIN toimitustapa use index (selite_index) ON toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa
					LEFT JOIN rahtikirjat use index (otsikko_index) ON rahtikirjat.otsikkonro=lasku.tunnus and rahtikirjat.yhtio=lasku.yhtio
					LEFT JOIN varastopaikat on varastopaikat.yhtio = lasku.yhtio and varastopaikat.tunnus = lasku.varasto
					WHERE lasku.$logistiikka_yhtiolisa
					and lasku.tila = '$tila'
					and lasku.alatila = 'C'
					$wherelasku
					$haku
					$tilaustyyppi
					$lisawhere
					and ((toimitustapa.nouto is null or toimitustapa.nouto = '') or lasku.vienti != '')
					GROUP BY lasku.yhtio, lasku.yhtio_nimi, lasku.toimitustapa, toimitustapa.nouto, $groupmaksuehto kimppakyyti, lasku.vienti, laadittux, toimaika $grouplisa
					$jarjx";
		$tilre = pupe_query($query);

		//piirret��n taulukko...
		if (mysql_num_rows($tilre) > 0) {

			echo "<br><table>";

			echo "<tr>";

			if ($logistiikka_yhtio != '') {
				echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='yhtio'; document.forms['find'].submit();\">".t("Yhti�")."</a></th>";
			}

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='prioriteetti'; document.forms['find'].submit();\">".t("Pri")."<br>
					  <a href='#' onclick=\"getElementById('jarj').value='varastonimi'; document.forms['find'].submit();\">".t("Varastoon")."</th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='tunnus'; document.forms['find'].submit();\">".t("Tilaus")."</th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='ytunnus'; document.forms['find'].submit();\">".t("Asiakas")."<br>
					  <a href='#' onclick=\"getElementById('jarj').value='nimi'; document.forms['find'].submit();\">".t("Nimi")."</th>";


			echo "<th valign='top'>
					<a href='#' onclick=\"getElementById('jarj').value='luontiaika'; document.forms['find'].submit();\">".t("Laadittu")."<br>
					<a href='#' onclick=\"getElementById('jarj').value='h1time'; document.forms['find'].submit();\">".t("Valmis")."<br>
					<a href='#' onclick=\"getElementById('jarj').value='lahetepvm'; document.forms['find'].submit();\">".t("Tulostettu")."<br>
					<a href='#' onclick=\"getElementById('jarj').value='kerattyaika'; document.forms['find'].submit();\">".t("Ker�tty")."</th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='kerayspvm'; document.forms['find'].submit();\">".t("Ker�ysaika")."<br>
					  <a href='#' onclick=\"getElementById('jarj').value='toimaika'; document.forms['find'].submit();\">".t("Toimitusaika")."</th>";

			echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='toimitustapa'; document.forms['find'].submit();\">".t("Toimitustapa")."</th>";

			if ($yhtiorow['pakkaamolokerot'] != '') {
				echo "<th valign='top'>".t("Kollit")."<br>".t("Rullakot")."</th>";
				echo "<th valign='top'>".t("Pakkaamo")."<br>".t("Lokero")."</th>";
			}
			echo "<th></th>";
			echo "</tr></form>";

			$osittaiset = array();

			while ($row = mysql_fetch_assoc($tilre)) {
				//chekkaus ett� kaikki splitatut tilaukset on ker�tty
				/* ei oteta huomioon niit� mist� puuttuu tulostusalue ja mill� on tietty alatila
				lis�� alatila B jos k�ytet��n ker��st� rahtikirjansy�tt��n halutessa */
				if ($yhtiorow["splittauskielto"] == "" and $yhtiorow['pakkaamolokerot'] != '') {
					$query = "	SELECT count(distinct lasku.tunnus) kpl, GROUP_CONCAT(DISTINCT if(lasku.tunnus not in ($row[tunnukset]), lasku.tunnus, null) order by lasku.tunnus) odottaa
								FROM lasku
								JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.toimitettu = ''
								WHERE lasku.$logistiikka_yhtiolisa
								AND lasku.tila in ('L','N','G')
								AND lasku.alatila not in ('X','V','D','B')
								AND lasku.tulostusalue != ''
								AND lasku.vanhatunnus = '$row[vanhatunnus]'
								AND lasku.varasto = '$row[varasto]'
								AND (lasku.pakkaamo = '$row[pakkaamo]' or (lasku.tila = 'N' or (lasku.tila = 'G' and lasku.alatila = 'J')))
								group by lasku.vanhatunnus";
					$vanhat_res = pupe_query($query);
					$vanhat_row = mysql_fetch_assoc($vanhat_res);
				}

				if ((isset($vanhat_row) and $vanhat_row['kpl'] == $row['tunnukset_lkm']) or (isset($vanhat_row) and $vanhat_row['kpl'] == 0) or $yhtiorow["splittauskielto"] != "" or $yhtiorow['pakkaamolokerot'] == '') {

					echo "<tr class='aktiivi'>";

					if ($logistiikka_yhtio != '') {
						echo "<td valign='top'>$row[yhtio_nimi]</td>";
					}

					if(trim($row["ohjeet"]) != "") {
						echo "<div id='div_$row[mintunnus]' class='popup' style='width: 500px;'>";
						echo t("Tilaukset").": ".$row["tunnukset"]."<br>";
						echo t("Laatija").": ".$row["laatija"]."<br><br>";
						echo str_replace("\n", "<br>", $row["ohjeet"])."<br>";
						echo "</div>";

						echo "<td valign='top' class='tooltip' id='$row[mintunnus]'>$row[t_tyyppi] $row[prioriteetti] <img src='$palvelin2/pics/lullacons/info.png'>";
					}
					else {
						echo "<td valign='top'>$row[t_tyyppi] $row[prioriteetti]";
					}

					echo "<br>$row[varastonimi]</td>";

					echo "<td valign='top'>".str_replace(',', '<br>', $row["tunnukset"])."</td>";
					echo "<td valign='top'>$row[ytunnus]<br>$row[nimi]</td>";

					$laadittu_e 	= tv1dateconv($row["luontiaika"], "P", "LYHYT");
					$h1time_e		= tv1dateconv($row["h1time"], "P", "LYHYT");
					$lahetepvm_e	= tv1dateconv($row["lahetepvm"], "P", "LYHYT");
					$kerattyaika_e	= tv1dateconv($row["kerattyaika"], "P", "LYHYT");
					$kerattyaika_e	= str_replace(substr($lahetepvm_e, 0, strpos($lahetepvm_e, " ")), "", $kerattyaika_e);
					$lahetepvm_e	= str_replace(substr($h1time_e, 0, strpos($h1time_e, " ")), "", $lahetepvm_e);
					$h1time_e		= str_replace(substr($laadittu_e, 0, strpos($laadittu_e, " ")), "", $h1time_e);

					echo "<td valign='top' nowrap align='right'>$laadittu_e<br>$h1time_e<br>$lahetepvm_e<br>$kerattyaika_e</td>";
					echo "<td valign='top' nowrap align='right'>".tv1dateconv($row["kerayspvm"], "", "LYHYT")."<br>".tv1dateconv($row["toimaika"], "", "LYHYT")."</td>";
					echo "<td valign='top'>$row[toimitustapa]</td>";

					if ($yhtiorow['pakkaamolokerot'] != '') {

						$kollit_chk = 0;
						$rullakot_chk = 0;

						$query = "	SELECT pakkaus, kollit
									FROM rahtikirjat
									WHERE $logistiikka_yhtiolisa
									AND otsikkonro in ($row[tunnukset])";
						$kollit_res = pupe_query($query);

						while ($kollit_row = mysql_fetch_assoc($kollit_res)) {
							if (trim(strtolower($kollit_row['pakkaus'])) == 'kolli') {
								$kollit_chk += $kollit_row['kollit'];
							}

							if (trim(strtolower($kollit_row['pakkaus'])) == 'rullakko') {
								$rullakot_chk += $kollit_row['kollit'];
							}
						}

						if ($kollit_chk == 0) {
							$kollit_chk = "";
						}

						if ($rullakot_chk == 0) {
							$rullakot_chk = "";
						}

						echo "<td valign='top'>";
						if ($kollit_chk > 0) {
							echo $kollit_chk;
						}
						else {
							echo "&nbsp;";
						}
						echo "<br>";
						if ($rullakot_chk > 0) {
							echo $rullakot_chk;
						}
						else {
							echo "&nbsp;";
						}
						echo "</td>";

						$query = "	SELECT nimi, lokero
									FROM pakkaamo
									WHERE $logistiikka_yhtiolisa
									AND tunnus in($row[pakkaamot])";
						$pakkaamoresult = pupe_query($query);

						echo "<td valign='top'>";
						if (mysql_num_rows($pakkaamoresult) > 0) {
							while ($pakkaamo_row = mysql_fetch_assoc($pakkaamoresult)) {
								echo $pakkaamo_row['nimi']."/".$pakkaamo_row['lokero']."<br>";
							}
						}
						else {
							echo "&nbsp;";
						}
						echo "</td>";
					}

					echo "	<form method='post' action='$PHP_SELF'>
							<td><input type='checkbox' name='kaikkicheckit[]' value='$row[tunnus]'></td>
							<input type='hidden' name='id' value='$row[tunnus]'>
							<input type='hidden' name='tunnukset' value='$row[tunnukset]'>
							<input type='hidden' name='dgdlle_tunnukset' value='$row[tunnukset]'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
							<input type='hidden' id='jarj' name='jarj' value='$jarj'>
							<input type='hidden' name='rakirno' value='$row[tunnus]'>
							<td class='back' valign='top'><input type='submit' name='tila' value='".t("Sy�t�")."'></td>
							</form>";
					echo "</tr>";
				}
				else {

					//kesken olevat
					$temp_osittaiset  = "";
					$temp_osittaiset .= "<tr class='aktiivi'>";

					if ($logistiikka_yhtio != '') {
						$temp_osittaiset .= "<td valign='top'>$row[yhtio_nimi]</td>";
					}

					if(trim($row["ohjeet"]) != "") {
						$temp_osittaiset .= "<div id='div_$row[mintunnus]' class='popup' style='width: 500px;'>";
						$temp_osittaiset .= t("Tilaukset").": ".$row["tunnukset"]."<br>";
						$temp_osittaiset .= t("Laatija").": ".$row["laatija"]."<br><br>";
						$temp_osittaiset .= str_replace("\n", "<br>", $row["ohjeet"])."<br>";
						$temp_osittaiset .= "</div>";

						$temp_osittaiset .= "<td valign='top' class='tooltip' id='$row[mintunnus]'>$row[t_tyyppi] $row[prioriteetti] <img src='$palvelin2/pics/lullacons/info.png'>";
					}
					else {
						$temp_osittaiset .= "<td valign='top'>$row[t_tyyppi] $row[prioriteetti]";
					}


					$temp_osittaiset .= "<br>$row[varastonimi]</td>";

					$temp_osittaiset .= "<td valign='top'>".str_replace(',', '<br>', $row["tunnukset"])."</td>";
					$temp_osittaiset .= "<td valign='top'>";

					$odotamme_naita = explode(",", $vanhat_row["odottaa"]);

					foreach ($odotamme_naita as $odn) {
						$temp_osittaiset .= "<a href='?toim=$toim&tee=NAYTATILAUS&tuvarasto=$tuvarasto&tumaa=$tumaa&tutyyppi=$tutyyppi&tutoimtapa=$tutoimtapa&tupakkaamo=$tupakkaamo&tunnus=$odn'>$odn</a><br>";
					}
					$temp_osittaiset .= "</td>";
					$temp_osittaiset .= "<td valign='top'>$row[ytunnus]<br>$row[nimi]</td>";

					$laadittu_e 	= tv1dateconv($row["luontiaika"], "P", "LYHYT");
					$h1time_e		= tv1dateconv($row["h1time"], "P", "LYHYT");
					$lahetepvm_e	= tv1dateconv($row["lahetepvm"], "P", "LYHYT");
					$kerattyaika_e	= tv1dateconv($row["kerattyaika"], "P", "LYHYT");
					$kerattyaika_e	= str_replace(substr($lahetepvm_e, 0, strpos($lahetepvm_e, " ")), "", $kerattyaika_e);
					$lahetepvm_e	= str_replace(substr($h1time_e, 0, strpos($h1time_e, " ")), "", $lahetepvm_e);
					$h1time_e		= str_replace(substr($laadittu_e, 0, strpos($laadittu_e, " ")), "", $h1time_e);

					$temp_osittaiset .= "<td valign='top' nowrap align='right'>$laadittu_e<br>$h1time_e<br>$lahetepvm_e<br>$kerattyaika_e</td>";
					$temp_osittaiset .= "<td valign='top' nowrap align='right'>".tv1dateconv($row["kerayspvm"], "", "LYHYT")."<br>".tv1dateconv($row["toimaika"], "", "LYHYT")."</td>";
					$temp_osittaiset .= "<td valign='top'>$row[toimitustapa]</td>";

					if ($yhtiorow['pakkaamolokerot'] != '') {

						$kollit_chk = 0;
						$rullakot_chk = 0;

						$query = "	SELECT pakkaus, kollit
									FROM rahtikirjat
									WHERE $logistiikka_yhtiolisa
									AND otsikkonro in ($row[tunnukset])";
						$kollit_res = pupe_query($query);

						while ($kollit_row = mysql_fetch_assoc($kollit_res)) {
							if (trim(strtolower($kollit_row['pakkaus'])) == 'kolli') {
								$kollit_chk += $kollit_row['kollit'];
							}

							if (trim(strtolower($kollit_row['pakkaus'])) == 'rullakko') {
								$rullakot_chk += $kollit_row['kollit'];
							}
						}

						if ($kollit_chk == 0) {
							$kollit_chk = "";
						}

						if ($rullakot_chk == 0) {
							$rullakot_chk = "";
						}

						$temp_osittaiset .= "<td valign='top'>";
						if ($kollit_chk > 0) {
							$temp_osittaiset .= $kollit_chk;
						}
						else {
							$temp_osittaiset .= "&nbsp;";
						}
						$temp_osittaiset .= "<br>";

						if ($rullakot_chk > 0) {
							$temp_osittaiset .= $rullakot_chk;
						}
						else {
							$temp_osittaiset .= "&nbsp;";
						}
						$temp_osittaiset .= "</td>";

						$query = "	SELECT nimi, lokero
									FROM pakkaamo
									WHERE $logistiikka_yhtiolisa
									AND tunnus in ($row[pakkaamot])";
						$pakkaamoresult = pupe_query(     $query);

						$temp_osittaiset .= "<td valign='top'>";
						if (mysql_num_rows($pakkaamoresult) > 0) {
							while ($pakkaamo_row = mysql_fetch_assoc($pakkaamoresult)) {
								$temp_osittaiset .= $pakkaamo_row['nimi']."/".$pakkaamo_row['lokero']."<br>";
							}
						}
						else {
							$temp_osittaiset .= "&nbsp;";
						}
						$temp_osittaiset .= "</td>";
					}

					$temp_osittaiset .= "<form method='post' action='$PHP_SELF'>";
					$temp_osittaiset .= "<td valign='top'>";

					$checkit = explode(",",$row["tunnukset"]);

					if (count($checkit) > 1) {
						foreach ($checkit as $key => $value) {
							$temp_osittaiset .= "<input type='checkbox' name='checktunnukset[]' value='$value'><br>";
						}
					}

					$temp_osittaiset .= "</td>";

					$temp_osittaiset .= "	<input type='hidden' name='id' value='$row[tunnus]'>
											<input type='hidden' name='tunnukset' value='$row[tunnukset]'>
											<input type='hidden' name='toim' value='$toim'>
											<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
											<input type='hidden' id='jarj' name='jarj' value='$jarj'>
											<input type='hidden' name='rakirno' value='$row[tunnus]'>
											<td class='back' valign='top'><input type='submit' name='tila' value='".t("Sy�t�")."'></td>
											</form>";
					$temp_osittaiset .= "</tr>";

					$osittaiset[] = $temp_osittaiset;
				}
			}
			echo "</table>";

			echo "	<script type='text/javascript' language='javascript'>
						$(document).ready(function(){
							$('#valitse_ruksatut_rahtikirjat').click(function(){
								$('input[name^=\"kaikkicheckit\"]').each(function(){
									if ($(this).is(':checked')) {
										//alert(index);
										var value = $(this).val() + ',' + $('#kaikki_ruksatut_tunnukset').val();

										$('#kaikki_ruksatut_tunnukset').val(value);
									}
								});

								var value = $('#kaikki_ruksatut_tunnukset').val().slice(0, -1);
								$('#kaikki_ruksatut_tunnukset').val(value);
							});
						});
					</script>";

			echo "	<br>
					<form method='post' action='$PHP_SELF'>
					<input type='hidden' id='kaikki_ruksatut_tunnukset' name='tunnukset' value=''>";

			echo "	<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
					<input type='hidden' id='jarj' name='jarj' value='$jarj'>
					<input type='hidden' name='montavalittu' value='kylla'>
					<input type='submit' id='valitse_ruksatut_rahtikirjat' name='tila' value='".t("Sy�t� rahtikirja valituille tilauksille")."'>
					</form><br>";

			if (count($osittaiset) > 0) {

				echo "<br><font class='head'>".t("Odottavat tilaukset")."</font><hr>";

				echo "<table>";
				echo "<tr>";
				if ($logistiikka_yhtio != '') {
					echo "<th valign='top'>".t("Yhti�")."</th>";
				}
				echo "<th valign='top'>".t("Pri")."<br>".t("Varastoon")."</th>";
				echo "<th valign='top'>".t("Tilaus")."</th>";
				echo "<th valign='top'>".t("Odottaa")."</th>";
				echo "<th valign='top'>".t("Asiakas")."<br>".t("Nimi")."</th>";
				echo "<th valign='top'>".t("Laadittu")."<br>".t("Valmis")."<br>".t("Tulostettu")."<br>".t("Ker�tty")."</th>";
				echo "<th valign='top'>".t("Ker�ysaika")."<br>".t("Toimitusaika")."</th>";
				echo "<th valign='top'>".t("Toimitustapa")."</th>";
				if ($yhtiorow['pakkaamolokerot'] != '') {
					echo "<th valign='top'>".t("Kollit")."<br>".t("Rullakot")."</th>";
					echo "<th valign='top'>".t("Pakkaamo")."<br>".t("Lokero")."</th>";
				}
				echo "<th valign='top'>".t("Valitse")."</th>";
				echo "</tr>";

				for ($i=0; $i < count($osittaiset); $i++) {
					echo $osittaiset[$i];
				}

				echo "</table>";
			}
		}
		else {
			echo "<font class='message'>".t("Sopivia tilauksia ei l�ytynyt")."...</font><br><br>";
		}
	}

	if (($toim == 'muokkaa' or $toim == 'muokkaa_siirto') and $id == 0 and (string) $id != 'dummy') {

		echo "<font class='head'>".t("Muokkaa rahtikirjatietoja")."</font><hr>";

		echo "<table>";
		echo "<form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='toimtila' value='$tila'>";
		echo "<input type='hidden' name='text' value='etsi'>";
		echo "<tr><td>".t("Valitse varasto:")."</td><td><select name='tuvarasto' onchange='submit()'>";

		$query = "	SELECT tunnus, nimitys, yhtio
					FROM varastopaikat
					WHERE $logistiikka_yhtiolisa
					ORDER BY tyyppi, nimitys";
		$result = pupe_query($query);

		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

		while ($row = mysql_fetch_assoc($result)){
			$sel = '';
			if (($row['tunnus'] == $tuvarasto) or ((isset($kukarow["varasto"]) and (int) $kukarow["varasto"] > 0 and in_array($row["tunnus"], explode(",", $kukarow['varasto']))) and $tuvarasto=='')) {
				$sel = 'selected';
				$tuvarasto = $row['tunnus'];
			}
			echo "<option value='$row[tunnus]' $sel>$row[nimitys]";
			if ($logistiikka_yhtio != '') echo " ($row[yhtio])";
			echo "</option>";
		}
		echo "</select>";

		$query = "	SELECT distinct maa
					FROM varastopaikat
					WHERE maa != '' and $logistiikka_yhtiolisa
					ORDER BY maa";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 1) {
			echo "<select name='tumaa' onchange='submit()'>";
			echo "<option value=''>".t("Kaikki")."</option>";

			while ($row = mysql_fetch_assoc($result)){
				$sel = '';
				if ($row['maa'] == $tumaa) {
					$sel = 'selected';
					$tumaa = $row['maa'];
				}
				echo "<option value='$row[maa]' $sel>$row[maa]</option>";
			}
			echo "</select>";
		}

		echo "</td>";

		echo "<td>".t("Valitse tilaustyyppi:")."</td><td><select name='tutyyppi' onchange='submit()'>";

		$sela = $selb = $selc = "";

		if ($tutyyppi == "NORMAA") {
			$sela = "SELECTED";
		}
		if ($tutyyppi == "ENNAKK") {
			$selb = "SELECTED";
		}
		if ($tutyyppi == "JTTILA") {
			$selc = "SELECTED";
		}
		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";
		echo "<option value='NORMAA' $sela>".t("N�yt� normaalitilaukset")."</option>";
		echo "<option value='ENNAKK' $selb>".t("N�yt� ennakkotilaukset")."</option>";
		echo "<option value='JTTILA' $selc>".t("N�yt� jt-tilaukset")."</option>";

		echo "</select></td></tr>";

		echo "<tr><td>".t("Valitse toimitustapa:")."</td><td><select name='tutoimtapa' onchange='submit()'>";

		$query = "	SELECT *
					FROM toimitustapa
					WHERE $logistiikka_yhtiolisa
					ORDER BY selite";
		$result =  pupe_query($query);

		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

		while ($row = mysql_fetch_assoc($result)){
			$sel = '';
			if ($row["selite"] == $tutoimtapa) {
				$sel = 'selected';
				$tutoimtapa = $row["selite"];
			}
			echo "<option value='$row[selite]' $sel>".t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV");
			if ($logistiikka_yhtio != '') echo " ($row[yhtio])";
			echo "</option>";
		}

		echo "</select></td>";

		echo "<td>".t("Etsi tilausta").":</td><td><input type='text' name='etsi'>";
		echo "<input type='Submit' value='".t("Etsi")."'></form></td></tr>";

		echo "</table>";

		$haku = '';

		if (!is_numeric($etsi) and $etsi != '') {
			$haku .= "and lasku.nimi LIKE '%$etsi%'";
		}

		if (is_numeric($etsi) and $etsi != '') {
			$haku .= "and lasku.tunnus='$etsi'";
		}

		if ($tuvarasto != '' and $tuvarasto != 'KAIKKI') {
			$haku .= " and lasku.varasto='$tuvarasto' ";
		}

		if ($tumaa != '') {
			$query = "	SELECT group_concat(tunnus) tunnukset
						FROM varastopaikat
						WHERE maa != '' and $logistiikka_yhtiolisa and maa = '$tumaa'";
			$maare = pupe_query($query);
			$maarow = mysql_fetch_assoc($maare);

			$haku .= " and lasku.varasto in ($maarow[tunnukset]) ";
		}

		if ($tutoimtapa != '' and $tutoimtapa != 'KAIKKI') {
			$haku .= " and lasku.toimitustapa='$tutoimtapa' ";
		}

		if ($tutyyppi != '' and $tutyyppi != 'KAIKKI') {
			if ($tutyyppi == "NORMAA") {
				$haku .= " and lasku.clearing='' ";
			}
			elseif($tutyyppi == "ENNAKK") {
				$haku .= " and lasku.clearing='ENNAKKOTILAUS' ";
			}
			elseif($tutyyppi == "JTTILA") {
				$haku .= " and lasku.clearing='JT-TILAUS' ";
			}
		}

		// pvm 30 pv taaksep�in
		$dd = date("d",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$mm = date("m",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$yy = date("Y",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

		// n�ytet��n tilauksia jota voisi muokata, tila L alatila B tai E tai sitten alatila D jos toimitustapa on HETI
		$query = "	SELECT lasku.yhtio yhtio,
					lasku.yhtio_nimi yhtio_nimi,
					rahtikirjat.rahtikirjanro rakirno,
		 			group_concat(distinct lasku.tunnus SEPARATOR ', ') 'tilaus',
					group_concat(distinct lasku.liitostunnus) asiakkaat,
					max(lasku.nimi) asiakas,
					max(concat_ws(' ', lasku.toimitustapa, vienti, ' ', varastopaikat.nimitys)) toimitustapa,
					max(date_format(lasku.luontiaika, '%Y-%m-%d')) laadittu,
					max(lasku.laatija) laatija,
					sum(kilot) kilot,
					sum(kollit) kollit,
					sum(kuutiot) kuutiot,
					sum(lavametri) lavametri
					FROM lasku use index (tila_index)
					JOIN toimitustapa use index (selite_index) ON toimitustapa.yhtio=lasku.yhtio and toimitustapa.selite=lasku.toimitustapa
					JOIN rahtikirjat use index (otsikko_index) ON rahtikirjat.yhtio=lasku.yhtio and rahtikirjat.otsikkonro=lasku.tunnus
					JOIN varastopaikat use index (PRIMARY) ON varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=rahtikirjat.tulostuspaikka
					WHERE lasku.$logistiikka_yhtiolisa
					AND	lasku.tila = '$tila'
					AND (lasku.alatila in ('B','E') or (lasku.alatila='D' and toimitustapa.tulostustapa='H'))
					$haku
					$tilaustyyppi
					GROUP BY 1,2,3
					ORDER BY lasku.toimitustapa, lasku.luontiaika desc";
		$tilre = pupe_query($query);

		if (mysql_num_rows($tilre) != 0) {
			echo "<br><table>";

			echo "<tr>";

			if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
				echo "<th align='left'>".t("Yhti�")."</th>";
			}

			echo "<th align='left'>".t("Tilaus")."</th>";
			echo "<th align='left'>".t("Rahtikirja")."</th>";
			echo "<th align='left'>".t("Asiakas")."</th>";
			echo "<th align='left'>".t("Toimitustapa")."</th>";
			echo "<th align='left'>".t("Laadittu")."</th>";
			echo "<th align='left'>".t("Tiedot Yhteens�")."</th>";

			while ($row = mysql_fetch_assoc($tilre)) {
				echo "<tr class='aktiivi'>";

				if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
					echo "<td>$row[yhtio_nimi]</td>";
				}

				echo "<td>$row[tilaus]</td>";
				echo "<td>$row[rakirno]</td>";
				echo "<td>$row[asiakas]</td>";
				echo "<td>$row[toimitustapa]</td>";
				echo "<td>".tv1dateconv($row["laadittu"])."</td>";

				$tiedot = "";
				if ($row['kollit'] > 0)		$tiedot .= "$row[kollit] kll<br>";
				if ($row['kilot'] > 0)		$tiedot .= (float) $row["kilot"]." kg<br>";
				if ($row['kuutiot'] > 0)	$tiedot .= "$row[kuutiot] m&sup3;<br>";
				if ($row['lavametri'] > 0)	$tiedot .= "$row[lavametri] m";

				echo "<td align='right'>$tiedot</td>";

				echo "<td class='back'>";

				echo "	<form method='post' action='$PHP_SELF'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tee' value='change'>
						<input type='hidden' name='rakirno' value='$row[rakirno]'>
						<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
						<input type='hidden' name='id' value='$row[tilaus]'>
						<input type='hidden' name='tunnukset' value='$row[tilaus]'>
						<input type='submit' value='".t("Muokkaa rahtikirjaa")."'>
						</form><br>";

				if ($row["tilaus"] != $edotsikkonro) {
					echo "<form method='post' action='$PHP_SELF'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='id' value='$row[tilaus]'>
							<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
							<input type='hidden' name='tunnukset' value='$row[tilaus]'>
							<input type='submit' value='".t("Lis�� rahtikirja tilaukselle")."'>
							</form><br>";
				}

				echo "<form method='post' action='$PHP_SELF'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tee' value='lisaatilaus'>
						<input type='hidden' name='rakirno' value='$row[rakirno]'>
						<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
						<input type='hidden' name='id' value='$row[tilaus]'>
						<input type='hidden' name='tunnukset' value='$row[tilaus]'>
						<input type='hidden' name='asiakkaat' value='$row[asiakkaat]'>
						<input type='submit' value='".t("Liit� tilaus rahtikirjaan")."'>
						</form><br><br>";


				echo "</td>";
				echo "</tr>";

				$edotsikkonro = $row["tilaus"];
			}
			echo "</table>";
		}
	}

	if (($id == 'dummy' and $mista == 'rahtikirja-tulostus.php') or $id != 0) {

		if ($id == 'dummy' and $mista == 'rahtikirja-tulostus.php') {
			echo "<font class='head'>".t("Sy�t� koonti-er�tulostus rahtikirjan pakkaustiedot")."</font><hr>";

			$ltun_linkklisa = '';

			if (count($sel_ltun) > 0) {
				foreach ($sel_ltun as $ltun_x) {
					$ltun_linkklisa .= "&sel_ltun[]=$ltun_x";
				}
			}
		}
		else {
			echo "<font class='head'>".t("Sy�t� rahtikirjan tiedot")."</font><hr>";

			$query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$id'";
			$resul = pupe_query($query);

			if (mysql_num_rows($resul) == 0) {
				die ("<font class='error'>".t("VIRHE Tilausta").": $id ".t("ei l�ydy")."!</font>");
			}

			$otsik = mysql_fetch_assoc($resul);

			if ($tila == 'L') {
				$query = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$otsik[maksuehto]'";
				$resul = pupe_query($query);

				if (mysql_num_rows($resul) == 0) {
					$marow = array();
				 	if ($otsik["erpcm"] == "0000-00-00") {
						echo ("<font class='error'>".t("VIRHE: Maksuehtoa ei l�ydy")."! $otsik[maksuehto]!</font>");
					}
				}
				else {
					$marow = mysql_fetch_assoc($resul);
				}
			}

			if (isset($checktunnukset) and is_array($checktunnukset)) {
				$tunnukset = implode(',', $checktunnukset);
			}
		}

		echo "	<script language='javascript'>
				function summaa_kollit(kollit_yht) {

					var currForm = kollit_yht.form;
					var isChecked = kollit_yht.checked;
					var nimi = 'kollit';
					var yht = 0;

					for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
						if (currForm.elements[elementIdx].name.substring(0,6) == nimi) {
							yht += Math.round(currForm.elements[elementIdx].value);
						}
					}

					if (isNaN(yht)) {
						currForm.oslappkpl.value=0;
					}
					else {
						currForm.oslappkpl.value=yht;
					}
				}
			</script> ";

		echo "<table>";
		echo "<form name='rahtikirjainfoa' action='$PHP_SELF?$ltun_linkklisa' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='add'>";
		echo "<input type='hidden' name='mista' value='$mista'>";
		echo "<input type='hidden' name='toim' value='$toim'>";

		if ($id == 'dummy' and $mista == 'rahtikirja-tulostus.php') {
			echo "<input type='hidden' name='toimitustapa_varasto' value='$toimitustapa_varasto'>";
			echo "<input type='hidden' name='jv' value='$jv'>";
			echo "<input type='hidden' name='komento' value='$komento'>";
			echo "<input type='hidden' name='valittu_rakiroslapp_tulostin' value='$valittu_rakiroslapp_tulostin'>";
		}
		else {
			echo "<input type='hidden' name='rahtikirjan_esisyotto' value='$rahtikirjan_esisyotto'>";
			echo "<input type='hidden' name='rakirno' value='$rakirno'>";
			echo "<input type='hidden' name='otsikkonro' value='$otsik[tunnus]'>";
			echo "<input type='hidden' name='dgdlle_tunnukset' value='$tunnukset'>";
			echo "<input type='hidden' name='montavalittu' value='$montavalittu'>";
			echo "<input type='hidden' name='tunnukset' value='$tunnukset'>";
			echo "<input type='hidden' name='lasku_yhtio' value='$otsik[yhtio]'>";

			echo "<tr><th align='left'>".t("Tilaus")."</th>";

			if ($tunnukset != "") echo "<td>$tunnukset</td>";
			else echo "<td>$otsik[tunnus]</td>";

			echo "<th align='left'>".t("Ytunnus")."</th><td>$otsik[ytunnus]</td></tr>";

			echo "<tr><th align='left'>".t("Asiakas")."</th><td>$otsik[nimi] $otsik[nimitark]<br>$otsik[osoite]<br>$otsik[postino] $otsik[postitp]</td>";
			echo "<th align='left'>".t("Toimitusosoite")."</th><td>$otsik[toim_nimi] $otsik[toim_nimitark]<br>$otsik[toim_osoite]<br>$otsik[toim_postino] $otsik[toim_postitp]</td></tr>";

			echo "<tr><th align='left'>".t("Ker�tty")."</th><td>$otsik[kerayspvm]</td>";
			echo "<th align='left'>".t("Maksuehto")."</th><td>".t_tunnus_avainsanat($marow, "teksti", "MAKSUEHTOKV", $kieli)."</td></tr>";

			if ($otsik["vienti"] == 'K')		$vientit = t("Vienti� EU:n ulkopuolelle");
			elseif ($otsik["vienti"] == 'E')	$vientit = t("EU Vienti�");
			else								$vientit = t("Kotimaan myynti�");

			echo "<tr><th align='left'>".t("Vienti")."</th><td>$vientit</td>";

			// haetaan kaikki toimitustavat
			$query  = "	SELECT *
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						and tulostustapa != 'X'
						order by jarjestys, selite";
			$result = pupe_query($query);

			echo "<th align='left'>".t("Toimitustapa")."</th><td>\n";

			$toitarow = array();

			echo "<select name='toimitustapa' onchange='submit()'>\n";

			while ($row = mysql_fetch_assoc($result)) {
				if ($otsik['toimitustapa'] == $row['selite'] and $toimitustapa=='') {
					$tulostustapa 	= $row['tulostustapa'];
					$select 		= 'selected';
					$toimitustapa 	= $row['selite'];
					$toitarow		= $row;
				}
				elseif ($toimitustapa == $row['selite']) {
					$tulostustapa 	= $row['tulostustapa'];
					$select 		= 'selected';
					$toimitustapa 	= $row['selite'];
					$toitarow		= $row;
				}
				else $select = '';

				echo "<option $select value='$row[selite]'>".t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV")."</option>\n";
			}

			echo "</select></td></tr>\n";


			// jos ei olla submitattu t�t� ruutua, otetaan merahti otsikolta
			if (!isset($merahti)) $merahti  = $otsik['kohdistettu'];

			//tehd��n rahtipopup..
			if ($merahti=="K") {
				$rahtihaku = $yhtiorow['ytunnus'];
				$mesel = "SELECTED";
				$nesel = "";
			}
			else {
				$rahtihaku = $otsik['ytunnus'];
				$nesel = "SELECTED";
				$mesel = "";
			}

			echo "<tr><th align='left'>".t("Rahdinmaksaja")."</th><td>";
			echo "<select name='merahti' onchange='submit()'>";
			echo "<option value=''  $nesel>".t("Vastaanottaja")."</option>";
			echo "<option value='K' $mesel>".t("L�hett�j�")."</option>";
			echo "</select></td>";

			//tehd��n rahtisopimuksen sy�tt�
			echo "<th align='left'>".t("Rahtisopimus")."</th><td>";

			//etsit��n l�ytyyk� rahtisopimusta
			$rsop = hae_rahtisopimusnumero($toimitustapa, $rahtihaku, $otsik["liitostunnus"]);
			$rahtisopimus = $rsop["rahtisopimus"];

			if ($otsik['rahtisopimus'] != '') {
				$rahtisopimus = $otsik['rahtisopimus'];
			}

			if ($rsop > 0) {
				$ylisa = "&tunnus=$rsop[tunnus]";
			}
			else {
				$ylisa = "&uusi=1&ytunnus=$rahtihaku&toimitustapa=$toimitustapa";
				$rsop["rahtisopimus"] = t("Lis�� rahtisopimus");
			}

			//
			if ($kukarow['yhtio'] == $lasku_yhtio_originaali) {
				echo "<a href='".$palvelin2."yllapito.php?toim=rahtisopimukset$ylisa&tee=add&lopetus=$PHP_SELF////toim=$toim//tunnukset=$tunnukset//lopetus=$lopetus//id=$id//tee=$tee//merahti=$merahti//tilausnumero=$tilausnumero//from=LASKUTATILAUS'>$rsop[rahtisopimus]</a><br/>";
			}

			echo "<input value='$rahtisopimus' type='text' name='rahtisopimus' size='20'></td></tr>";

			// haetaan kaikki varastot
			$query  = "	SELECT tunnus, nimitys
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY tyyppi, nimitys";
			$result = pupe_query($query);

			echo "<tr><th align='left'>".t("Varasto")."</th>";

			// jos l�ytyy enemm�n kuin yksi, tehd��n varasto popup..
			if (mysql_num_rows($result) > 1) {

				echo "<td><select name='tulostuspaikka'>";

				$query = "SELECT tulostuspaikka from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$id' limit 1";
				$rarrr = pupe_query($query);

				if (mysql_num_rows($rarrr)==1) {
					$roror          = mysql_fetch_assoc($rarrr);
					$tulostuspaikka = $roror['tulostuspaikka'];
				}

				if ((int) $kukarow["oletus_varasto"] != 0) $tulostuspaikka = $kukarow['oletus_varasto'];

				if ($tulostuspaikka == '') $tulostuspaikka=$otsik['varasto'];

				while ($row = mysql_fetch_assoc($result)) {
					if ($tulostuspaikka==$row['tunnus'])	$select='selected';
					else									$select='';

					echo "<option $select value='$row[tunnus]'>$row[nimitys]</option>";
				}
				echo "</select></td>";
			}
			else {
				$row = mysql_fetch_assoc($result);

				$tulostuspaikka = $row["tunnus"];

				echo "<td><input type='hidden' name='tulostuspaikka' value='$row[tunnus]'>$row[nimitys]</td>";
			}

			if (strtoupper($tulostustapa) == 'H' or strtoupper($tulostustapa) == 'K') {
				if ($otsik['pakkaamo'] > 0 and $otsik['varasto'] != '' and $otsik['tulostusalue'] != '') {
					$query = "	SELECT pakkaamo.printteri2, pakkaamo.printteri4, pakkaamo.printteri6
								from pakkaamo
								where pakkaamo.yhtio='$kukarow[yhtio]'
								and pakkaamo.tunnus='$otsik[pakkaamo]'
								order by pakkaamo.tunnus";
				}
				elseif ($otsik['tulostusalue'] != '' and $otsik['varasto'] != '') {
					$query = "	SELECT varaston_tulostimet.printteri2, varaston_tulostimet.printteri4, varaston_tulostimet.printteri6
								FROM varaston_tulostimet
								WHERE varaston_tulostimet.yhtio = '$kukarow[yhtio]'
								AND varaston_tulostimet.nimi = '$otsik[tulostusalue]'
								AND varaston_tulostimet.varasto = '$otsik[varasto]'
								ORDER BY varaston_tulostimet.prioriteetti, varaston_tulostimet.alkuhyllyalue";
				}
				else {
					$query = "SELECT * from varastopaikat where yhtio = '$kukarow[yhtio]' and tunnus = '$tulostuspaikka'";
				}

				$pres  = pupe_query($query);
				$print = mysql_fetch_assoc($pres);

				// haetaan rahtikirjan tyyppi
				$query    = "	SELECT *
								from avainsana
								where yhtio = '$kukarow[yhtio]'
								and laji = 'RAHTIKIRJA'
								and selite = '$toitarow[rahtikirja]'";
				$avainres = mysql_query($query) or pupe_error($query);
				$avainrow = mysql_fetch_assoc($avainres);

				if ($avainrow["selitetark_2"] == "1") {
					$kirjoitin_tunnus = $print["printteri6"]; // Rahtikirja A4
				}
				elseif ($avainrow["selitetark_2"] == "2") {
					$kirjoitin_tunnus = $print["printteri4"]; // Rahtikirja A5
				}
				elseif ($avainrow["selitetark_2"] == "3") {
					$kirjoitin_tunnus = $print["printteri2"]; // Rahtikirja matriisi
				}
				elseif ($toitarow['tulostustapa'] == 'H') {
					$kirjoitin_tunnus = $print["printteri4"]; // Rahtikirja A5
				}
				elseif (strpos($toitarow['rahtikirja'],'pdf') === false) {
					$kirjoitin_tunnus = $print["printteri2"]; // Rahtikirja matriisi
				}
				else {
					$kirjoitin_tunnus = $print["printteri6"]; // Rahtikirja A4
				}

				$query = "	SELECT *
							FROM kirjoittimet
							WHERE
							yhtio = '$kukarow[yhtio]'
							ORDER by kirjoitin";
				$kirre = pupe_query($query);

				echo "<th>".t("Rahtikirjatulostin")."</th><td><select name='komento'>";

				while ($kirrow = mysql_fetch_assoc($kirre)) {
					$sel = "";
					if ($kirrow['tunnus'] == $kirjoitin_tunnus) {
						$sel = "SELECTED";
					}

					echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
				}

				#echo "<option value='PDF_RUUDULLE'>".t('PDF Ruudulle')."</option>";
				echo "</select></td>";
			}
			else {
				echo "<th></th><td></td>";
			}

			echo "</tr>";

			$query = "	SELECT GROUP_CONCAT(distinct if(viesti!='',viesti,NULL) separator '. ') viesti
						from rahtikirjat use index (otsikko_index)
						where yhtio			= '$kukarow[yhtio]'
						and otsikkonro		= '$id'
						and rahtikirjanro	= '$rakirno'";
			$viestirar = pupe_query($query);

			$viestirarrow = mysql_fetch_assoc($viestirar);

			echo "<tr><th>".t("Kuljetusohje")."</th><td><textarea name='viesti' cols='30' rows='3'>$viestirarrow[viesti]</textarea></td><th></th><td></td></tr>";

			if ($otsik['pakkaamo'] > 0 and $yhtiorow['pakkaamolokerot'] != '') {
				if (strpos($tunnukset,',') !== false) {
					$query = "	SELECT GROUP_CONCAT(pakkaamo SEPARATOR ',') pakkaamot
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus in($tunnukset)";
					$pakkaamotres = pupe_query($query);
					$pakkaamotrow = mysql_fetch_assoc($pakkaamotres);
					$pakkaamotunnukset = " AND tunnus in($pakkaamotrow[pakkaamot]) ";
				}
				else {
					$pakkaamotunnukset = " AND tunnus = $otsik[pakkaamo] ";
				}

				$query = "	SELECT nimi, lokero
							FROM pakkaamo
							WHERE yhtio = '$kukarow[yhtio]'
							$pakkaamotunnukset";
				$lokero_chk_res = pupe_query($query);

				if (mysql_num_rows($lokero_chk_res) > 0) {
					while ($lokero_chk_row = mysql_fetch_assoc($lokero_chk_res)) {
						echo "<tr><th>".t("Pakkaamo")."</th><td>$lokero_chk_row[nimi]</td><th>".t("Lokero")."</th><td>$lokero_chk_row[lokero]</td></tr>";
					}
				}
			}

			// jos meill� on hetitulostettava j�lkivaatimus-tilaus niin (annetaan mahdollisuus tulostaa) TULOSTETAAN lasku heti
			if ((strtoupper($tulostustapa) == 'H' or strtoupper($tulostustapa) == 'K') and $marow["jv"] != "") {

				echo "<tr><td class='back'><br></td></tr>";
				echo "<tr>";
				echo "<th colspan='3'><font class='error'>".t("Valitse j�lkivaatimuslaskujen tulostuspaikka")."</font></th>";
				echo "<td><select name='rakirsyotto_laskutulostin'>";
				echo "<option value=''>".t("Ei tulosteta laskua")."</option>";

				//Haetaan varaston JV-kuittitulostin
				$query = "SELECT printteri7 FROM varastopaikat WHERE yhtio='$kukarow[yhtio]' and tunnus='$tulostuspaikka'";
				$jvres = pupe_query($query);
				$jvrow = mysql_fetch_assoc($jvres);

				$query = "	SELECT *
							from kirjoittimet
							where yhtio='$kukarow[yhtio]'
							ORDER BY kirjoitin";
				$kires = pupe_query($query);

				while ($kirow=mysql_fetch_assoc($kires)) {
					if ($kirow["tunnus"] == $jvrow["printteri7"]) {
						$sel = "SELECTED";
					}
					else {
						$sel = "";
					}

					echo "<option value='$kirow[tunnus]' $sel>$kirow[kirjoitin]</option>";
				}

				echo "</select></td></tr>";

			}
		}

		echo "</table>";

		//sitten tehd��n pakkaustietojen sy�tt�...
		echo "<br><font class='message'>".t("Sy�t� tilauksen pakkaustiedot")."</font><hr>";

		echo "<table>";

		if ($id != 'dummy' and $mista != 'rahtikirja-tulostus.php') {

			$query  = "	SELECT sum(tuotemassa*(varattu+kpl)) massa, sum(varattu+kpl) kpl, sum(if(tuotemassa!=0, varattu+kpl, 0)) kplok
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.otunnus IN ($tunnukset) and tilausrivi.var != 'J'";

			$painoresult = pupe_query($query);
			$painorow = mysql_fetch_assoc($painoresult);

			if ($painorow["kpl"] > 0) {
				$osumapros = round($painorow["kplok"] / $painorow["kpl"] * 100, 2);
			}
			else {
				$osumapros = "N/A";
			}

			echo "<font class='message'>".sprintf(t("Tilauksen paino tuoterekisterin tietojen mukaan on %s kg. %s%%:lle kappaleista on annettu paino."),$painorow["massa"],$osumapros)."</font><br>";

			//Tuotekannassa voi olla tuotteen mitat kahdella eri tavalla
			// leveys x korkeus x syvyys
			// leveys x korkeus x pituus
			$query = "	SHOW columns
						FROM tuote
						LIKE 'tuotepituus'";
			$spres = pupe_query($query);

			if (mysql_num_rows($spres) == 1) {
				$splisa = "tuotepituus";
			}
			else {
				$splisa = "tuotesyvyys";
			}

			$query  = "	SELECT round(sum(tuotekorkeus*tuoteleveys*$splisa*(varattu+kpl)),10) tilavuus, sum(varattu+kpl) kpl, sum(if(tuotekorkeus!=0 and tuoteleveys!=0 and $splisa!=0, varattu+kpl, 0)) kplok
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.otunnus IN ($tunnukset)";
			$tilavuusresult = pupe_query($query);
			$tilavuusrow = mysql_fetch_assoc($tilavuusresult);

			if ($tilavuusrow["kpl"] > 0) {
				$osumapros = round($tilavuusrow["kplok"] / $tilavuusrow["kpl"] * 100, 2);
			}
			else {
				$osumapros = "N/A";
			}

			$tilavuusrow["tilavuus"] = round($tilavuusrow["tilavuus"],3);

			echo "<font class='message'>".t("Tilauksen tilavuus tuoterekisterin tietojen mukaan on")." $tilavuusrow[tilavuus] m&sup3;. $osumapros".t("%:lle kappaleista on annettu tilavuus.")."</font><br>";
		}

		echo "<table>";
		echo "<tr><th>".t("Kollia")."</th><th>".t("Kg")."</th><th>m&sup3;</th><th>m</th><th align='left' colspan='3'>".t("Pakkaus")."</th></tr>";

		$i = 0;

		$query  = "	SELECT *
					FROM pakkaus
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY jarjestys";
		$result = pupe_query($query);

		while ($row = mysql_fetch_assoc($result)) {

			if (!isset($kollit[$i])) $kollit[$i] = '';
			if (!isset($kilot[$i])) $kilot[$i] = '';
			if (!isset($kuutiot[$i])) $kuutiot[$i] = '';
			if (!isset($lavametri[$i])) $lavametri[$i] = '';
			if (!isset($pakkauskuvaustark[$i])) $pakkauskuvaustark[$i] = '';

			if (strpos($tunnukset, ',') !== FALSE) {
				$rahti_otsikot = " AND otsikkonro in ($tunnukset) ";
				$rahti_rahtikirjanro = " AND rahtikirjanro in ($tunnukset) ";
			}
			else {
				if ($id == 'dummy') {
					$rahti_otsikot = " AND otsikkonro = 0 ";
					$rahti_rahtikirjanro = " AND rahtikirjanro = '0' ";
				}
				else {
					$rahti_otsikot = " AND otsikkonro = $id ";
					$rahti_rahtikirjanro = " AND rahtikirjanro = '$rakirno' ";
				}
			}

			$query = "	SELECT sum(kollit) kollit, sum(kilot) kilot, sum(kuutiot) kuutiot, sum(lavametri) lavametri, min(pakkauskuvaustark) pakkauskuvaustark
						FROM rahtikirjat use index (otsikko_index)
						WHERE yhtio			= '$kukarow[yhtio]'
						$rahti_otsikot
						$rahti_rahtikirjanro
						AND pakkaus			= '$row[pakkaus]'
						AND pakkauskuvaus	= '$row[pakkauskuvaus]'";
			$rarrr = pupe_query($query);

			if (mysql_num_rows($rarrr) == 1) {
				$roror = mysql_fetch_assoc($rarrr);

				if ($roror['kollit'] > 0)				$kollit[$i]				= $roror['kollit'];
				if ($roror['kilot'] > 0)				$kilot[$i]				= $roror['kilot'];
				if ($roror['kuutiot']  > 0)				$kuutiot[$i]			= $roror['kuutiot'];
				if ($roror['lavametri'] > 0)			$lavametri[$i]			= $roror['lavametri'];
				if ($roror['pakkauskuvaustark'] != '')	$pakkauskuvaustark[$i]	= $roror['pakkauskuvaustark'];
			}

			echo "<tr>
				<td><input type='hidden' name='pakkaus[$i]' value='$row[pakkaus]'>
				<input type='hidden' name='pakkauskuvaus[$i]' value='$row[pakkauskuvaus]'>";

			if ($row['erikoispakkaus'] != '') {
				echo "<input type='hidden' name='erikoispakkaus[$i]' value='$row[pakkaus]'>";
			}

			if ((strtoupper($tulostustapa) == 'E' or strtoupper($tulostustapa) == 'L') and $yhtiorow['oletus_rahtikirja_oslappkpl'] != 0) {
				echo "<input type='text' size='4' value='$kollit[$i]' name='kollit[$i]' onKeyUp='summaa_kollit(this);'></td>";
			}
			else {
				echo "<input type='text' size='4' value='$kollit[$i]' name='kollit[$i]'></td>";
			}

			echo "<td><input type='text' size='7' value='$kilot[$i]' name='kilot[$i]'></td>
				<td><input type='text' size='7' value='$kuutiot[$i]' name='kuutiot[$i]'></td>
				<td><input type='text' size='7' value='$lavametri[$i]' name='lavametri[$i]'></td>
				<td>".t_tunnus_avainsanat($row, "pakkaus", "PAKKAUSKV")."</td>
				<td>".t_tunnus_avainsanat($row, "pakkauskuvaus", "PAKKAUSKV")."</td>
				<td><input type='text' size='10' name='pakkauskuvaustark[$i]' value='$pakkauskuvaustark[$i]'></td>";

			echo "</tr>";

			$i++;
		}

		echo "</table>";

		if ($id != 'dummy' and $mista != 'rahtikirja-tulostus.php') {

			if ($yhtiorow['rahti_ja_kasittelykulut_kasin'] != '') {

				$query_ale_lisa = generoi_alekentta('M');

				echo "<br><table>";

				$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
				$rhire = pupe_query($query);

				if (mysql_num_rows($rhire) == 1) {
					$trow  = mysql_fetch_assoc($rhire);

					$query = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa
					 			FROM tilausrivi
								JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.otunnus = '$otsik[tunnus]'
								and tilausrivi.tuoteno = '$yhtiorow[rahti_tuotenumero]'
								and tilausrivi.tyyppi != 'D'";
					$rhire = pupe_query($query);
					$rrow  = mysql_fetch_assoc($rhire);

					if ($yhtiorow["alv_kasittely"] == '') {
						$k_rahtikulut = $rrow["summa"];
					}
					else {
						$k_rahtikulut = $rrow["arvo"];
					}

					echo "<tr><th>".t("Rahti").":</th><td><input type='text' size='6' name='k_rahtikulut' value='$k_rahtikulut'></td><td>$yhtiorow[valkoodi]</td></tr>";
				}

				$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[kasittelykulu_tuotenumero]'";
				$rhire = pupe_query($query);

				if (mysql_num_rows($rhire) == 1) {
					$trow  = mysql_fetch_assoc($rhire);

					$query = "	SELECT
								round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
								round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa
					 			FROM tilausrivi
								JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.otunnus = '$otsik[tunnus]'
								and tilausrivi.tuoteno = '$yhtiorow[kasittelykulu_tuotenumero]'
								and tilausrivi.tyyppi != 'D'";
					$rhire = pupe_query($query);
					$rrow  = mysql_fetch_assoc($rhire);

					if ($yhtiorow["alv_kasittely"] == '') {
						$k_kasitkulut = $rrow["summa"];
					}
					else {
						$k_kasitkulut = $rrow["arvo"];
					}

					echo "<tr><th>".t("K�sittelykulut").":</th><td><input type='text' size='6' name='k_kasitkulut' value='$k_kasitkulut'></td><td>$yhtiorow[valkoodi]</td></tr>";
				}

				echo "</table>";
			}

			//tulostetaan faili ja valitaan sopivat printterit
			if ($otsik['pakkaamo'] > 0 and $otsik['varasto'] != '' and $otsik['tulostusalue'] != '') {
				$query = "	SELECT pakkaamo.printteri1, pakkaamo.printteri3, varastopaikat.printteri5
							from pakkaamo
							join varastopaikat ON pakkaamo.yhtio = varastopaikat.yhtio and varastopaikat.tunnus = '$otsik[varasto]'
							where pakkaamo.yhtio='$kukarow[yhtio]'
							and pakkaamo.tunnus='$otsik[pakkaamo]'
							order by pakkaamo.tunnus";
			}
			elseif ($otsik['tulostusalue'] != '' and $otsik['varasto'] != '') {
				$query = "	SELECT varaston_tulostimet.printteri1, varaston_tulostimet.printteri3, varastopaikat.printteri5
							FROM varaston_tulostimet
							JOIN varastopaikat ON (varaston_tulostimet.yhtio = varastopaikat.yhtio and varastopaikat.tunnus = '$otsik[varasto]')
							WHERE varaston_tulostimet.yhtio = '$kukarow[yhtio]'
							AND varaston_tulostimet.nimi = '$otsik[tulostusalue]'
							AND varaston_tulostimet.varasto = '$otsik[varasto]'
							ORDER BY varaston_tulostimet.prioriteetti, varaston_tulostimet.alkuhyllyalue";
			}
			elseif ($otsik["varasto"] == '') {
				$query = "	SELECT *
							from varastopaikat
							where yhtio='$kukarow[yhtio]'
							order by alkuhyllyalue,alkuhyllynro
							limit 1";
			}
			else {
				$query = "	SELECT *
							from varastopaikat
							where yhtio='$kukarow[yhtio]' and tunnus='$otsik[varasto]'
							order by alkuhyllyalue,alkuhyllynro";
			}
			$prires = pupe_query($query);


			if (mysql_num_rows($prires) > 0) {
				$prirow= mysql_fetch_assoc($prires);

				$lahete_printteri = "";
				//l�hete
				if ($prirow['printteri1'] != '') {
					$lahete_printteri = $prirow['printteri1'];
				}

				$oslappu_printteri = "";
				//osoitelappu
				if ($prirow['printteri3'] != '') {
					$oslappu_printteri = $prirow['printteri3'];
				}
			}

			$query = "	SELECT *
						FROM kirjoittimet
						WHERE
						yhtio='$kukarow[yhtio]'
						ORDER by kirjoitin";
			$kirre = pupe_query($query);

			echo "<br><table>";

			if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != 'Y' or $mista != 'keraa.php') {

				$lahetekpl  = $yhtiorow['oletus_rahtikirja_lahetekpl'];

				echo "<tr><th>".t("L�hete").":</th><td>";
				echo "<select name='rakirsyotto_lahete_tulostin'>";
				echo "<option value=''>".t("Ei tulosteta")."</option>";

				while ($kirrow = mysql_fetch_assoc($kirre)) {
					$sel = "";
					if ($kirrow['tunnus'] == $lahete_printteri) {
						$sel = "SELECTED";
					}

					echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
				}
				mysql_data_seek($kirre, 0);

				echo "</select> ".t("Kpl").": <input type='text' size='4' name='lahetekpl' value='$lahetekpl'></td></tr>";
			}

			$oslappkpl = $yhtiorow['oletus_rahtikirja_oslappkpl'];
			$termoslappkpl = 0;

			echo "<tr><th>".t("Osoitelappu").":</th><td>";
			echo "<select name='rakirsyotto_oslapp_tulostin'>";
			echo "<option value=''>".t("Ei tulosteta")."</option>";

			while ($kirrow = mysql_fetch_assoc($kirre)) {
				$sel = "";
				if ($kirrow['tunnus'] == $oslappu_printteri) {
					$sel = "SELECTED";
				}

				echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
			}
			mysql_data_seek($kirre, 0);

			echo "</select> ".t("Kpl").": <input type='text' size='4' name='oslappkpl' value='$oslappkpl'></td></tr>";

			if (($toitarow["tulostustapa"] == "L" or $toitarow["tulostustapa"] == "K") and $toitarow["toim_nimi"] != '') {
				echo "<tr><th>".t("Terminaaliosoitelappu").":</th><td>";
				echo "<select name='rakirsyotto_termoslapp_tulostin'>";
				echo "<option value=''>".t("Ei tulosteta")."</option>";

				while ($kirrow = mysql_fetch_assoc($kirre)) {
					$sel = "";
					if ($kirrow['tunnus'] == $oslappu_printteri) {
						$sel = "SELECTED";
					}

					echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
				}
				mysql_data_seek($kirre, 0);

				echo "</select> ".t("Kpl").": <input type='text' size='4' name='termoslappkpl' value='$termoslappkpl'></td></tr>";
			}

			if ($vakrow['vaktuotteet'] != '') {

				echo "<tr><th>".t("DGD-lomake").":</th><td>";
				echo "<select name='rakirsyotto_dgd_tulostin'>";
				echo "<option value=''>".t("Ei tulosteta")."</option>";

				while ($kirrow = mysql_fetch_assoc($kirre)) {
					$sel = "";
					if ($kirrow['tunnus'] == $rakirsyotto_dgd_tulostin) {
						$sel = "SELECTED";
					}

					echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
				}
				mysql_data_seek($kirre, 0);

				if (!isset($dgdkpl)) $dgdkpl = 1;

				echo "</select> ".t("Kpl").": <input type='text' size='4' name='dgdkpl' value='$dgdkpl'></td></tr>";

				echo "<tr><td class='back'><font class='info'>",t("Tulosta my�s yleisrahtikirja"),"<br/>",t("VAK-postipaketille"),":</font></td>";
			    echo "<td class='back'><input type='checkbox' name='tulosta_vak_yleisrahtikirja' id='tulosta_vak_yleisrahtikirja'></td></tr>";
			}
		}

		echo "</table>";

		if ($tee == 'change') {
			echo "<input type='hidden' name='muutos' value='yes'>";
		}

		echo "<br><input type='hidden' name='id' value='$id'>";

		echo "<input name='subnappi' type='submit' value='".t("Valmis")."'>";

		echo "</form>";

		if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '' and $mista == 'keraa.php') {
			echo "<br><br><font class='message'>".t("Siirryt automaattisesti takaisin ker�� ohjelmaan")."!</font>";
		}
	}

	if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '' and $mista == 'keraa.php' and $keraaseen == 'mennaan') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tilauskasittely/keraa.php'>";
		exit;
	}

	require ("inc/footer.inc");
?>