<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-tulostus.php") !== FALSE) {
		require("inc/parametrit.inc");
	}

	echo "<font class='head'>".t("Rahtikirjojen tulostus")."</font><hr>";

	$laskutettu = "";

	if ($tee == 'tulosta' and $laskukomento == '' and $jv != 'eijv') {
		//	ehkä ei halutakkaan aina tulostaa.. who knows.
		//	echo "<font class='error'>Valitse laskujen tulostuspaikka! Tai ruksaa 'Älä tulosta jälkivaatimuksia'!</font><br><br>";
		//	$tee = "";
	}

	if ($tee == 'tulosta') {
		
		list($toimitustapa, $varasto, $crap) = explode("!!!!", $toimitustapa_varasto);
		
		// haetaan toimitustavan tiedot
		$query    = "select * from toimitustapa where yhtio = '$kukarow[yhtio]' and selite = '$toimitustapa'";
		$toitares = mysql_query($query) or pupe_error($query);
		$toitarow = mysql_fetch_array($toitares);

		// haetaan rahtikirjan tyyppi
		$query    = "select * from avainsana where yhtio = '$kukarow[yhtio]' and laji = 'RAHTIKIRJA' and selite = '$toitarow[rahtikirja]'";
		$avainres = mysql_query($query) or pupe_error($query);
		$avainrow = mysql_fetch_array($avainres);

		// haetaan printterin tiedot
		$query = "select * from varastopaikat where yhtio = '$kukarow[yhtio]' and tunnus = '$varasto'";
		$pres  = mysql_query($query) or pupe_error($query);
		$print = mysql_fetch_array($pres);

		if ($komento != "") {
			$kirjoitin_tunnus = $komento; // jos ollaan valittu oma printteri
			
		}
		elseif ($avainrow["selitetark_2"] == "1") {
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

		// haetaan printterille tulostuskomento
		$query = "select * from kirjoittimet where yhtio = '$kukarow[yhtio]' and tunnus = '$kirjoitin_tunnus'";
		$pres  = mysql_query($query) or pupe_error($query);
		$print = mysql_fetch_array($pres);

		$kirjoitin = $print['komento'];
		$merkisto  = $print['merkisto'];
		$pvm       = date("j.n.Y");

		if ($kirjoitin == '') die (t("Valitsemallesi varastolle ole ole määritelty tarvittavaa rahtikirja-tulostinta")." ($mika)!");

		echo "<font class='message'>".t("Tulostetaan rahtikirjat toimitustavalle").": $toimitustapa<br>".t("Kirjoitin").": $print[kirjoitin]</font><hr>";

		// jos meillä printterille joku spessu osoitetieto niin käytetään sen tietoja lähettäjän tietoina
		if ($print["nimi"] != "") {
			$yhtiorow["nimi"]    = $print["nimi"];
			$yhtiorow["osoite"]  = $print["osoite"];
			$yhtiorow["postino"] = $print["postino"];
			$yhtiorow["postitp"] = $print["postitp"];
			$yhtiorow["puhelin"] = $print["puhelin"];
		}

		// emuloidaan transactioita mysql LOCK komennolla
		$query ="LOCK TABLES rahtikirjat WRITE, tilausrivi WRITE, tapahtuma WRITE, tuote WRITE, lasku WRITE, tiliointi WRITE, tuotepaikat WRITE, sanakirja WRITE, rahtisopimukset READ, rahtimaksut READ, maksuehto READ, varastopaikat READ, kirjoittimet READ, asiakas READ, kuka READ, avainsana READ, pankkiyhteystiedot READ, yhtion_toimipaikat READ, tuotteen_alv READ, maat READ";
		$res   = mysql_query($query) or pupe_error($query);

		if ($jv == 'vainjv') {
			echo t("Vain jälkivaatimukset").".";
			$jvehto = " having jv!='' ";
		}
		elseif ($jv == 'eivj') {
			echo t("Ei jälkivaatimuksia").".";
			$jvehto = " having jv='' ";
		}
		elseif ($jv == 'vainvak') {
			echo t("Vain VAK").". ";
			$vainvakilliset = " JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
							JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.vakkoodi != '') ";
		}
		else {
			$jvehto = " ";
		}
		
		// haetaan kaikki distinct rahtikirjat..
		$query = "	SELECT distinct lasku.ytunnus, lasku.toim_maa, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_ovttunnus, lasku.toim_postino, lasku.toim_postitp,
					lasku.maa, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.ovttunnus, lasku.postino, lasku.postitp,
					rahtikirjat.merahti, rahtikirjat.rahtisopimus, if(maksuehto.jv is null,'',maksuehto.jv) jv, lasku.alv, lasku.vienti, rahtisopimukset.muumaksaja
					FROM rahtikirjat
					JOIN lasku on rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio and lasku.tila in ('L','G') and lasku.alatila = 'B'
					$vainvakilliset
					LEFT JOIN maksuehto on lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
					LEFT JOIN rahtisopimukset on lasku.ytunnus = rahtisopimukset.ytunnus and rahtikirjat.toimitustapa = rahtisopimukset.toimitustapa and rahtikirjat.rahtisopimus = rahtisopimukset.rahtisopimus
					WHERE rahtikirjat.tulostettu	= '0000-00-00 00:00:00'
					and rahtikirjat.yhtio			= '$kukarow[yhtio]'
					and rahtikirjat.toimitustapa	= '$toimitustapa'
					and rahtikirjat.tulostuspaikka	= '$varasto'
					$jvehto
					order by lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, rahtikirjat.merahti, rahtikirjat.rahtisopimus";
		$rakir_res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($rakir_res) == 0) {
			echo "<font class='message'>".t("Yhtään tulostettavaa rahtikirjaa ei löytynyt").".</font><br><br>";
		}

		while ($rakir_row = mysql_fetch_array($rakir_res)) {
			// muutama muuttuja tarvitaan
			$pakkaus       	= array();
			$kilot         	= array();
			$kollit        	= array();
			$kuutiot       	= array();
			$lavametri     	= array();
			$lotsikot      	= array();
			$astilnrot		= array();
			$vakit         	= array();
			$kilotyht      	= 0;
			$lavatyht      	= 0;
			$kollityht     	= 0;
			$kuutiotyht    	= 0;
			$tulostuskpl   	= 0;
			$otunnukset    	= "";
			$tunnukset     	= "";
			$rahtikirjanro 	= "";


			if ($rakir_row['merahti'] == 'K') {
				$rahdinmaksaja = "Lähettäjä";
			}
			else {
				$rahdinmaksaja = "Vastaanottaja"; //tämä on defaultti
			}

			// Katsotaan onko tämä koontikuljetus
			if ($toitarow["tulostustapa"] == "K" or $toitarow["tulostustapa"] == "L") {
				// Monen asiakkaan rahtikirjat tulostuu aina samalle paperille
				$asiakaslisa = " ";

				//Toimitusosoitteeksi halutaan tässä tapauksessa toimitustavan takaa löytyvät
				$rakir_row["toim_maa"]		= $toitarow["toim_maa"];
				$rakir_row["toim_nimi"]		= $toitarow["toim_nimi"];
				$rakir_row["toim_nimitark"]	= $toitarow["toim_nimitark"];
				$rakir_row["toim_osoite"]	= $toitarow["toim_osoite"];
				$rakir_row["toim_postino"]	= $toitarow["toim_postino"];
				$rakir_row["toim_postitp"]	= $toitarow["toim_postitp"];

			}
			else {
				// Normaalissa keississä ainoastaan saman toimitusasiakkaan kirjat menee samalle paperille
				$asiakaslisa = "and lasku.ytunnus			= '$rakir_row[ytunnus]'
								and lasku.toim_maa			= '$rakir_row[toim_maa]'
								and lasku.toim_nimi			= '$rakir_row[toim_nimi]'
								and lasku.toim_nimitark		= '$rakir_row[toim_nimitark]'
								and lasku.toim_osoite		= '$rakir_row[toim_osoite]'
								and lasku.toim_ovttunnus	= '$rakir_row[toim_ovttunnus]'
								and lasku.toim_postino		= '$rakir_row[toim_postino]'
								and lasku.toim_postitp		= '$rakir_row[toim_postitp]' ";
			}

			if ($rakir_row['jv'] != '') {
				$jvehto = " having jv!='' ";
			}
			else {
				$jvehto = " having jv='' ";
			}

			// haetaan tälle rahtikirjalle kuuluvat tunnukset
			$query = "	SELECT rahtikirjat.tunnus rtunnus, lasku.tunnus otunnus, merahti, lasku.ytunnus, if(maksuehto.jv is null,'',maksuehto.jv) jv, lasku.asiakkaan_tilausnumero
						FROM rahtikirjat
						join lasku on rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio and lasku.tila in ('L','G') and lasku.alatila = 'B'
						left join maksuehto on lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
						WHERE rahtikirjat.tulostettu	= '0000-00-00 00:00:00'
						and rahtikirjat.yhtio			= '$kukarow[yhtio]'
						and rahtikirjat.toimitustapa	= '$toimitustapa'
						and rahtikirjat.tulostuspaikka	= '$varasto'
						$asiakaslisa
						and rahtikirjat.merahti			= '$rakir_row[merahti]'
						and rahtikirjat.rahtisopimus	= '$rakir_row[rahtisopimus]'
						$jvehto";
			$res   = mysql_query($query) or pupe_error($query);

			while ($rivi = mysql_fetch_array($res)) {
				//otetaan kaikki otsikkonumerot ja rahtikirjanumerot talteen... tarvitaan myöhemmin hauissa
				$otunnukset   .="'$rivi[otunnus]',";
				$tunnukset    .="'$rivi[rtunnus]',";

				//otsikkonumerot talteen, nämä printataan paperille
				if (!in_array($rivi['otunnus'], $lotsikot)) {
					$lotsikot[] 	= $rivi['otunnus'];
					$astilnrot[]	= $rivi['asiakkaan_tilausnumero'];
				}
				// otetaan jokuvaan rtunnus talteen uniikisi numeroksi
				// tarvitaan postin rahtikirjoissa
				$rtunnus = $rivi["rtunnus"];
			}

			if (mysql_num_rows($res) > 0) {
				mysql_data_seek($res,0);
				$rivi = mysql_fetch_array($res);

				//vikat pilkut pois
				$otunnukset = substr($otunnukset,0,-1);
				$tunnukset  = substr($tunnukset,0,-1);

				//summataan kaikki painot yhteen
				$query = "	SELECT pakkaus, sum(kilot), sum(kollit), sum(kuutiot), sum(lavametri)
							FROM rahtikirjat
							WHERE tunnus in ($tunnukset) and yhtio='$kukarow[yhtio]'
							group by pakkaus order by pakkaus";
				$pakka = mysql_query($query) or pupe_error($query);

				while ($pak = mysql_fetch_array($pakka)) {
					$pakkaus[]   = $pak[0];
					
					if ($pak[1] > 0 or $pak[2] > 0) {
						$kilot[]     = $pak[1];
						$kollit[]    = $pak[2];
					}
					
					$kuutiot[]   = $pak[3];
					$lavametri[] = $pak[4];
					$kilotyht   += $pak[1];
					$kollityht  += $pak[2];
					$kuutiotyht += $pak[3];
					$lavatyht   += $pak[4];
				}

				$tulostuskpl = $kollityht;

				// merkataan tilausrivit toimitetuiksi..
				$query = "	UPDATE tilausrivi
							set toimitettu = '$kukarow[kuka]', toimitettuaika=now()
							where otunnus in ($otunnukset)
							and yhtio = '$kukarow[yhtio]'
							and var not in ('P','J')
							and tyyppi = 'L'";
				$ures  = mysql_query($query) or pupe_error($query);

				//haetaan rahtikirjan kaikki vakkoodit arrayseen
				$query = "	SELECT distinct(vakkoodi)
							from tilausrivi,tuote
							where otunnus in ($otunnukset)
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tuote.tuoteno = tilausrivi.tuoteno
							and tuote.yhtio = tilausrivi.yhtio
							and vakkoodi <> '0'
							and vakkoodi <> ' '
							and var in ('','H')
							and tilausrivi.tyyppi in ('L','G')";
				$vres = mysql_query($query) or pupe_error($query);
				
				while ($vak = mysql_fetch_array($vres)) {
					$vakit[] = $vak[0];
				}

				// nyt on kaikki tiedot rahtikirjaa varten haettu..
				//
				// arrayt:
				// toitarow, otsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
				// $rakir_row:sta löytyy asiakkaan tiedot
				//
				////ja $rivi:stä ytunnus
				//
				// muuttujat:
				// otunnukset, pvm, rahdinmaksaja, toimitustapa, kolliyht, kilotyht, kuutiotyht, kirjoitin
				// jv tapauksissa on myös aputeksti, rahtihinta, rahdinhinta, yhteensa, summa, jvhinta, jvtext, lasno ja viite muuttujat
				// rtunnus jossa on uniikki numero
				//
				// tulostetaan rahtikirja

				// merkataan rahtikirjat tulostetuksi..
				$query = "	UPDATE rahtikirjat
							set tulostettu=now()
							where tunnus in ($tunnukset)
							and yhtio = '$kukarow[yhtio]'";
				$ures  = mysql_query($query) or pupe_error($query);


				// näitä tarvitaan vain JV-keiseissa, mutta pitää nollata tietty joka luupilla
				$lasno		= "";
				$viite		= "";
				$summa		= "";
				$jvtext		= "";
				$jvhinta	= "";
				$yhteensa	= "";
				$aputeksti	= "";
				$rahinta 	= "";

				// jos kyseessä on jälkivaatimus
				if ($rakir_row['jv'] != '') {
					// jos toimitustapa hanskaa monivarastojälkivaatimukset
					if ($toitarow["multi_jv"] != "") {
						require ("rahtikirja-tulostus-jv-multi.inc");
					}
					// toimitustapa ei hanskaa monivarastojälkivaatimukset
					else {
						$tee 			= "TARKISTA";
						$laskutakaikki 	= "KYLLA";
						$silent		 	= "KYLLA";
						$laskutettavat 	= $otunnukset;

						if ($laskutulostin != '') {
							$valittu_tulostin = $laskutulostin;
						}

						$query = "	UPDATE lasku
									set alatila='D'
									where tunnus in ($laskutettavat)
									and yhtio = '$kukarow[yhtio]'";
						$result = mysql_query($query) or pupe_error($query);

						$rivitunnukset = $tunnukset;
						chdir('tilauskasittely');

						require ("verkkolasku.php");

						chdir('../');
						$tunnukset = $rivitunnukset;


						// Nämä muuttujat tulevat toivottavasti ulos verkkolasku.php:stä
						// $jvhinta jossa on jälkivaatimuskulut
						// $rahinta jossa on rahtikulut
						// $laskurow jossa on laskutetun laskun tiedot

						$yhteensa = $laskurow['summa'];
						$summa    = $laskurow['summa'] - $jvhinta - $rahinta;

						$jvtext  = "<li>".t("Jälkivaatimuskulu").": $jvhinta $yhtiorow[valkoodi]";
						$jvtext .= "<li>".t("Loppusumma yhteensä").": $yhteensa $yhtiorow[valkoodi]";

						$aputeksti = t("JÄLKIVAATIMUS");
					}
				}

				echo "<font class='message'>".t("Asiakas")." $rakir_row[toim_nimi]</font><li>".t("Yhdistetään tilaukset").": ";

				foreach($lotsikot as $doit) echo "$doit ";

				echo "$rahinta $jvtext";

				// tarvitaan tietää, että onko kyseessä kopio
				$tulostakopio = "";

				// tulostetaan toimitustavan määrittelemä rahtikirja
				if (file_exists("tilauskasittely/$toitarow[rahtikirja]")) {
					require("tilauskasittely/$toitarow[rahtikirja]");
				}
				else {
					echo "<li><font class='error'>".t("VIRHE: Rahtikirja-tiedostoa")." 'tilauskasittely/$toitarow[rahtikirja]' ".t("ei löydy")."!</font>";
				}

				$query = "	UPDATE rahtikirjat
							set rahtikirjanro='$rahtikirjanro'
							where tunnus in ($tunnukset)
							and yhtio = '$kukarow[yhtio]'";
				$ures  = mysql_query($query) or pupe_error($query);

				// jos ei JV merkataan rahtikirjat tulostetuksi otsikollekkin..
				if ($rakir_row['jv'] == '') {
					// kotimaan myynti menee alatilaan D
					$query = "UPDATE lasku set alatila = 'D' where tunnus in ($otunnukset) and vienti = '' and yhtio='$kukarow[yhtio]'";
					$ures  = mysql_query($query) or pupe_error($query);

					// vientilaskut menee alatilaan B
					$query = "UPDATE lasku set alatila = 'B' where tunnus in ($otunnukset) and vienti != '' and yhtio='$kukarow[yhtio]'";
					$ures  = mysql_query($query) or pupe_error($query);

					// verkkolaskutettavat EU-viennit menee alatilaan D, jos niillä on tarpeeksi lisätietoja
					$query = "	UPDATE lasku set
								alatila = 'D',
								bruttopaino = '$kilotyht'
								where yhtio = '$kukarow[yhtio]'
								and tunnus in ($otunnukset)
								and vienti = 'E'
								and chn = '020'
								and maa_maara != ''
								and kauppatapahtuman_luonne > 0
								and kuljetusmuoto != ''";
					$ures  = mysql_query($query) or pupe_error($query);
				}

				// päivitetään laskuille oletus intrastat tiedot toimittajan takaa jos sellaisia löytyy ja mitään ei ole vielä päivitetty
				$query = "	UPDATE lasku SET
							maa_maara						     = '$toitarow[maa_maara]',
							sisamaan_kuljetus                    = '$toitarow[sisamaan_kuljetus]',
							sisamaan_kuljetus_kansallisuus       = '$toitarow[sisamaan_kuljetus_kansallisuus]',
							sisamaan_kuljetusmuoto               = '$toitarow[sisamaan_kuljetusmuoto]',
							kontti                               = '$toitarow[kontti]',
							aktiivinen_kuljetus                  = '$toitarow[aktiivinen_kuljetus]',
							aktiivinen_kuljetus_kansallisuus     = '$toitarow[aktiivinen_kuljetus_kansallisuus]',
							kauppatapahtuman_luonne              = '$toitarow[kauppatapahtuman_luonne]',
							kuljetusmuoto                        = '$toitarow[kuljetusmuoto]',
							poistumistoimipaikka_koodi           = '$toitarow[poistumistoimipaikka_koodi]'
							WHERE yhtio = '$kukarow[yhtio]' and
							tunnus in ($otunnukset) and
							maa_maara = '' and
							sisamaan_kuljetus = '' and
							sisamaan_kuljetus_kansallisuus = '' and
							sisamaan_kuljetusmuoto = '' and
							kontti = '' and
							aktiivinen_kuljetus = '' and
							aktiivinen_kuljetus_kansallisuus = '' and
							kuljetusmuoto = '' and
							poistumistoimipaikka_koodi = ''";
				$ures  = mysql_query($query) or pupe_error($query);

			}
			echo "<br>";

		} // end while haetaan kaikki distinct rahtikirjat..


		// poistetaan lukko
		$query = "UNLOCK TABLES";
		$res   = mysql_query($query) or pupe_error($query);

		if ($toitarow['tulostustapa'] == 'H' or $toitarow['tulostustapa'] == 'K') {
			$tee = 'XXX';
		}
		else {
			$tee = '';
		}

		echo "<br>";

	} // end tee==tulosta

	if($tee == '') {

		// haetaan kaikki distinct toimitustavat joille meillä on rahtikirjoja tulostettavana..
		$query = "	select distinct lasku.toimitustapa, varastopaikat.tunnus, varastopaikat.nimitys, varastopaikat.printteri7
					from rahtikirjat
					join lasku on rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio and lasku.tila in ('L','G') and lasku.alatila = 'B'
					join toimitustapa on lasku.yhtio = toimitustapa.yhtio 
					and lasku.toimitustapa = toimitustapa.selite 
					and toimitustapa.tulostustapa in ('E','L') 
					and toimitustapa.nouto = ''
					left join maksuehto on lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
					left join varastopaikat on varastopaikat.yhtio=rahtikirjat.yhtio and varastopaikat.tunnus=rahtikirjat.tulostuspaikka
					where rahtikirjat.tulostettu = '0000-00-00 00:00:00'
					and rahtikirjat.yhtio = '$kukarow[yhtio]'
					ORDER BY varastopaikat.tunnus, lasku.toimitustapa";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			echo "<form action='rahtikirja-tulostus.php' method='post'>";
			echo "<input type='hidden' name='tee' value='tulosta'>";

				echo "<table>";
			echo "<tr><td>".t("Valitse toimitustapa").":</td>";
			echo "<td><select name='toimitustapa_varasto' onchange=\"document.getElementById('kirjoitin').options.selectedIndex=document.getElementById('K'+this.value.substr(this.value.indexOf('!!!!!')+5)).index;\">";

			while ($rakir_row = mysql_fetch_array($result)) {
				$sel = "";
				if($rakir_row["tunnus"] == $kukarow["varasto"] and $varasto == "") {
					$sel = "selected";
					$varasto = $rakir_row["tunnus"];
				}
				echo "<option value='$rakir_row[toimitustapa]!!!!$rakir_row[tunnus]!!!!!$rakir_row[printteri7]' $sel>$rakir_row[nimitys] - $rakir_row[toimitustapa]</option>";
			}

			echo "</select></td></tr>";

			echo "<tr><td>".t("Tulosta kaikki rahtikirjat").":</td>";
			echo "<td><input type='radio' name='jv' value='' checked></td></tr>";

			echo "<tr><td>".t("Tulosta vain jälkivaatimukset").":</td>";
			echo "<td><input type='radio' name='jv' value='vainjv'></td></tr>";

			echo "<tr><td>".t("Älä tulosta jälkivaatimuksia").":</td>";
			echo "<td><input type='radio' name='jv' value='eijv'></td></tr>";
			
			echo "<tr><td>".t("Tulosta vain rahtikirjoja joilla on VAK-koodeja").":</td>";
			echo "<td><input type='radio' name='jv' value='vainvak'></td></tr>";

			echo "<tr><td>".t("Valitse jälkivaatimuslaskujen tulostuspaikka").":</td>";
			echo "<td><select id='kirjoitin' name='laskukomento'>";
			echo "<option value=''>".t("Ei kirjoitinta")."</option>";

			$query = "SELECT printteri7 FROM varastopaikat WHERE yhtio='$kukarow[yhtio]' and tunnus='$varasto'";
			$jvres = mysql_query($query) or pupe_error($query);
			$jvrow = mysql_fetch_array($jvres);
			$e = $jvrow["printteri7"];
			$sel = array();
			$sel[$e] = "SELECTED";

			
			$query = "	select *
						from kirjoittimet
						where yhtio='$kukarow[yhtio]'
						ORDER BY kirjoitin";
			$kires = mysql_query($query) or pupe_error($query);
			
			
			
			while ($kirow = mysql_fetch_array($kires)) {
				echo "<option id='K$kirow[tunnus]' value='$kirow[komento]' ".$sel[$kirow["tunnus"]].">$kirow[kirjoitin]</option>";
			}

			echo "</select></td></tr>";
			
			echo "<tr><td>".t("Valitse tulostin").":</td>";
			echo "<td><select name='komento'>";
			echo "<option value='' SELECTED>".t("Oletustulostimelle")."</option>";
			
			mysql_data_seek($kires, 0);			
			
			while ($kirow = mysql_fetch_array($kires)) {
				echo "<option id='K$kirow[tunnus]' value='$kirow[tunnus]'>$kirow[kirjoitin]</option>";
			}
			
			echo "</select></td></tr>";
			
			echo "</table><br>";
			echo "<input type='submit' value='".t("Tulosta rahtikirjat")."'>";
			echo "</form>";
		}
		else {
			echo "<br><br><br><font class='message'>".t("Yhtään tulostettavaa rahtikirjaa ei löytynyt").".</font><br><br>";
		}

		require("inc/footer.inc");
	}

?>
