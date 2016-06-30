<?php

	function sarjanumeronlisatiedot_popup ($sarjatunnus, $yhtio = '', $class = '', $hinnat = '', $leveys= '', $eilisatietoja = '') {
		global $kukarow, $yhtiorow;

		// parametri $sarjatunnus jossa on sarjanumero-olion tunnus

		//Jos yhtiˆ tulee parametrin‰ niin katsotaa, ettei se ole ihan mit‰ sattuu
		if ($yhtio != "") {
			$query	= "	SELECT GROUP_CONCAT(distinct yhtio) yhtiot
						from yhtio
						where yhtio = '$kukarow[yhtio]' or (konserni = '$yhtiorow[konserni]' and konserni != '')";
			$pres = pupe_query($query);
			$prow = mysql_fetch_array($pres);

			$yhtiot = explode(",", $prow["yhtiot"]);

			if (in_array($yhtio, $yhtiot)) {
				$yhtiolisa = $yhtio;
			}
			else {
				$yhtiolisa = $kukarow["yhtio"];
			}
		}
		else {
			$yhtiolisa = $kukarow["yhtio"];
		}

		$query = "SELECT *
							FROM sarjanumeroseuranta use index (PRIMARY)
							WHERE sarjanumeroseuranta.yhtio	= '$yhtiolisa'
							AND sarjanumeroseuranta.tunnus	= '$sarjatunnus'";
		$sarjares = pupe_query($query);
		$sarjarow = mysql_fetch_array($sarjares);

		$sisviestit  = "<table width='100%'>";
		$text_output = "";

		if ($sarjarow["lisatieto"] != '' and $kukarow["extranet"] == "" and substr($hinnat,0,2) != "ME") {
			$sisviestit  .= "<tr><th class='ptop'>Sis‰inen viesti:</th><td colspan='2'>".str_replace("\n","<br>",$sarjarow["lisatieto"])."</td></tr>";
			$text_output .= "Sis‰inen viesti:";

			$kommentit = explode("\n", trim($sarjarow["lisatieto"]));

			foreach($kommentit as $kommentti) {
				$text_output .= "###".$kommentti."\n";
			}
		}

		//Haetaan myˆs ns. lis‰varusteperheet.
		if ($sarjarow["ostorivitunnus"] != 0) {

			$query = "	SELECT tilausrivi.tunnus, tilausrivi.uusiotunnus, tilausrivi.perheid2,
						round(tilausrivi.rivihinta/tilausrivi.kpl, '$yhtiorow[hintapyoristys]') rivihinta,
						tilausrivi.kpl,
						tilausrivi.tilkpl,
						tilausrivi.laskutettuaika osto_laskaika,
						DATEDIFF(now(), tilausrivi.laskutettuaika)	varpvm,
						tuote.alv,
						tuote.myyntihinta
						FROM tilausrivi use index (PRIMARY)
						JOIN tuote ON tilausrivi.tuoteno=tuote.tuoteno and tilausrivi.yhtio=tuote.yhtio
						WHERE tilausrivi.yhtio	= '$sarjarow[yhtio]'
						and tilausrivi.tunnus	= '$sarjarow[ostorivitunnus]'
						and tilausrivi.tyyppi  != 'D'";
			$tilrivires = pupe_query($query);

			if (mysql_num_rows($tilrivires) > 0) {
				$tilrivirow = mysql_fetch_array($tilrivires);

				// TArvitaan varastop‰ivien laskennassa
				$ostonpvm = $tilrivirow["osto_laskaika"];
				$osvarpvm = $tilrivirow["varpvm"];

				// Haetaan keikan tiedot jolla laite on ostettu sis‰‰n
				$query    = "	SELECT *
								FROM lasku
								WHERE tunnus = '$tilrivirow[uusiotunnus]'
								AND yhtio 	 = '$sarjarow[yhtio]'";
				$result   = pupe_query($query);
				$keikkarow = mysql_fetch_array($result);

				// Katsotaan onko t‰lle keikalle jo liitetty vaihto-omaisuuslaskuja (kotimaa, eu tai ei-eu)
				$query = "	SELECT sum(summa) summa, valkoodi, vienti
							FROM lasku
							WHERE yhtio		= '$sarjarow[yhtio]'
							and tila		= 'K'
							and laskunro	= '$keikkarow[laskunro]'
							and vanhatunnus<>0
							and vienti in ('C','F','I','J','K','L')
							GROUP BY valkoodi, vienti";
				$result = pupe_query($query);

				// jos on, haetaan liitettyjen laskujen
				if (mysql_num_rows($result) == 1) {
					$kulurow = mysql_fetch_array($result);
				}
				else {
					$kulurow = array(); // muuten tyhj‰‰
				}

				// Jos ollaan liitetty jo vaihto-omaisuuslasku, k‰ytet‰‰n sen vientikentt‰‰
				if ($kulurow["vienti"] != "") $keikkarow["vienti"] = $kulurow["vienti"];

				if ($kukarow["extranet"] == "") {
					$sisviestit .= "<tr><th>Ostohinta:</th><td align='right' colspan='2'>$tilrivirow[rivihinta] $yhtiorow[valkoodi]</td></tr>";

					if ($hinnat == "") {
						$text_output   .= "Ostohinta:### ### ###$tilrivirow[rivihinta] $yhtiorow[valkoodi]\n";
					}
				}

				$ostohinta = $tilrivirow["rivihinta"];
				$yhtion_oletus_alv = 1 + (((float) alv_oletus()) / 100);

				if ($sarjarow["kaytetty"] == '') $ostohinta_alv = $tilrivirow["rivihinta"] * $yhtion_oletus_alv; // HUOM Aina plus 24% alvia!!!!!
				else $ostohinta_alv = $tilrivirow["rivihinta"];

				// Katsotaan onko sarjanumerolle liitetty kulukeikka
				$query  = "	SELECT *
							from lasku
							where yhtio		 = '$kukarow[yhtio]'
							and tila		 = 'K'
							and alatila		 = 'S'
							and liitostunnus = '$sarjarow[tunnus]'
							and ytunnus 	 = '$sarjarow[tunnus]'";
				$keikkares = pupe_query($query);

				$kuluohinta	    = 0;
				$kuluohinta_alv = 0;

				if (mysql_num_rows($keikkares) > 0) {

					while ($kulukeikkarow = mysql_fetch_array($keikkares)) {

						// Haetaan kaikki keikkaan liitettyjen laskujen summa
						$query = "	SELECT tila, vanhatunnus, nimi,
									round(summa*if(maksu_kurssi!=0, maksu_kurssi, vienti_kurssi),2) summa,
									round(arvo*if(maksu_kurssi!=0, maksu_kurssi, vienti_kurssi),2) arvo
									FROM lasku
									WHERE yhtio		= '$kukarow[yhtio]'
									and tila 		IN ('K','X')
									and laskunro 	= '$kulukeikkarow[laskunro]'
									and vanhatunnus <> 0
									and vienti in ('B','E','H')";
						$result = pupe_query($query);

						while($kulukulurow = mysql_fetch_array($result)) {
							if ($kulukulurow["summa"] != 0) {
								$kuluohinta		+= $kulukulurow["arvo"];
								$kuluohinta_alv += $kulukulurow["summa"];

								if ($kukarow["extranet"] == "") {

									if ($kulukulurow["tila"] == "K") {
										$sisviestit .= "<tr><th>Kululasku:</th><td align='right' colspan='2'>$kulukulurow[arvo] $yhtiorow[valkoodi] " . ebid($kulukulurow['vanhatunnus']) . "</td></tr>";

										if ($hinnat == "") {
											$text_output   .= "Kululasku:### ### ###$kulukulurow[arvo] $yhtiorow[valkoodi]\n";
										}
									}
									else {
										$sisviestit .= "<tr><th>Sis‰inen kulu:</th><td align='right' colspan='2'>$kulukulurow[arvo] $yhtiorow[valkoodi] $kulukeikkarow[nimi]</td></tr>";

										if ($hinnat == "") {
											$text_output   .= "Sis‰inen kulu:### ### ###$kulukulurow[arvo] $yhtiorow[valkoodi]\n";
										}
									}
								}
							}
						}
					}
				}

				if ($tilrivirow["perheid2"] == $tilrivirow["tunnus"]) {
					$query = "	SELECT round(tilausrivi.rivihinta/$tilrivirow[kpl], '$yhtiorow[hintapyoristys]') rivihinta,
								tilausrivi.nimitys,
								tilausrivi.tuoteno,
								tuote.alv,
								if(tilausrivi.kpl != 0, round(tilausrivi.kpl/$tilrivirow[kpl], 2), round(tilausrivi.tilkpl/$tilrivirow[tilkpl], 2)) kpl,
								tuote.myyntihinta,
								tuote.sarjanumeroseuranta,
								tilausrivi.tunnus,
								tilausrivi.tyyppi,
								tilausrivin_lisatiedot.sistyomaarays_sarjatunnus
								FROM tilausrivi use index (yhtio_perheid2)
								LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
								JOIN tuote ON tilausrivi.tuoteno=tuote.tuoteno and tilausrivi.yhtio=tuote.yhtio
								WHERE tilausrivi.yhtio 		= '$sarjarow[yhtio]'
								and tilausrivi.tyyppi 	   != 'D'
								and tilausrivi.perheid2 	= '$tilrivirow[perheid2]'
								and tilausrivi.tunnus	   != tilausrivi.perheid2
								order by tilausrivi.tunnus";
					$tilrivires = pupe_query($query);

					if (mysql_num_rows($tilrivires) > 0) {

						$tehdasvaruste  = "<table width='100%'><tr><th colspan='3'>".t("Tehdaslis‰varusteet").":</th></tr>";
						$text_output1   = t("Tehdaslis‰varusteet").":\n";

						$lisaostohinta 		= 0;
						$lisaostohinta_alv 	= 0;
						$lisamyyntihinta	= 0;

						while ($tilrivirow2 = mysql_fetch_array($tilrivires)) {
							// jos kysess‰ on kotimainen vaihto-omaisuuslasku, pit‰‰ lis‰t‰ tuotteen hintaan alvi
							if ($keikkarow['vienti']=='C' or $keikkarow['vienti']=='J') {
								$alvit = $tilrivirow2["alv"];
							}
							else {
								$alvit = 0;
							}

							if ($tilrivirow2['sarjanumeroseuranta'] == 'S' and $tilrivirow2['tyyppi'] == 'G' and $tilrivirow2["sistyomaarays_sarjatunnus"] > 0) {
								$query = "	SELECT group_concat(sarjanumero) sarjanumero, round(sum(tilausrivi_osto.rivihinta / tilausrivi_osto.kpl) * $tilrivirow2[kpl], '$yhtiorow[hintapyoristys]') rivihinta
											FROM sarjanumeroseuranta use index (PRIMARY)
											LEFT JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON (tilausrivi_osto.yhtio = sarjanumeroseuranta.yhtio
												AND tilausrivi_osto.tunnus = sarjanumeroseuranta.ostorivitunnus)
											WHERE sarjanumeroseuranta.yhtio	= '$sarjarow[yhtio]'
											and sarjanumeroseuranta.tuoteno	= '$tilrivirow2[tuoteno]'
											and sarjanumeroseuranta.tunnus in ($tilrivirow2[sistyomaarays_sarjatunnus])";
								$lisavarsarjares = pupe_query($query);
								$lisavarsarjarow = mysql_fetch_array($lisavarsarjares);

								$tilrivirow2["rivihinta"] = $lisavarsarjarow['rivihinta'];
								$lisavarsarjalisa = "(".$lisavarsarjarow["sarjanumero"].")";
							}
							elseif ($tilrivirow2['sarjanumeroseuranta'] == 'S' and $tilrivirow2['tyyppi'] == 'O') {
								$query = "	SELECT group_concat(sarjanumero) sarjanumero
											FROM sarjanumeroseuranta use index (PRIMARY)
											WHERE sarjanumeroseuranta.yhtio	= '$sarjarow[yhtio]'
											and sarjanumeroseuranta.tuoteno	= '$tilrivirow2[tuoteno]'
											and sarjanumeroseuranta.ostorivitunnus = '$tilrivirow2[tunnus]'";
								$lisavarsarjares = pupe_query($query);
								$lisavarsarjarow = mysql_fetch_array($lisavarsarjares);

								$lisavarsarjalisa = "(".$lisavarsarjarow["sarjanumero"].")";
							}
							else {
								$lisavarsarjalisa = "";
							}

							$lisaostohinta 		+= $tilrivirow2["rivihinta"];
							$yhtion_oletus_alv = 1 + (((float) alv_oletus()) / 100);
							$lisaostohinta_alv 	+= $tilrivirow2["rivihinta"] * $yhtion_oletus_alv; // HUOM Aina plus 24% alvia!!!!!
							$lisamyyntihinta 	+= $tilrivirow2["myyntihinta"];

							if ($hinnat == "" and $kukarow["extranet"] == "") {
								$text_output1  .= "$tilrivirow2[nimitys] $lisavarsarjalisa### ###$tilrivirow2[kpl] KPL###".hintapyoristys($tilrivirow2["rivihinta"])." $yhtiorow[valkoodi]\n";
								$tehdasvaruste .= "<tr><td>$tilrivirow2[nimitys] $lisavarsarjalisa</td><td align='right'>$tilrivirow2[kpl] KPL</td><td align='right'>".hintapyoristys($tilrivirow2["rivihinta"])." $yhtiorow[valkoodi]</td></tr>";
							}
							elseif($hinnat == "MY") {
								if ($sarjarow["kaytetty"] != "") {
									$text_output1  .= "$tilrivirow2[nimitys] $lisavarsarjalisa### ###$tilrivirow2[kpl] KPL\n";
									$tehdasvaruste .= "<tr><td>$tilrivirow2[nimitys] $lisavarsarjalisa</td><td align='right'>$tilrivirow2[kpl] KPL</td><td align='right'>".hintapyoristys($tilrivirow2["myyntihinta"])." $yhtiorow[valkoodi]</td></tr>";
								}
								else {
									$text_output1  .= "$tilrivirow2[nimitys] $lisavarsarjalisa### ###$tilrivirow2[kpl] KPL###".hintapyoristys($tilrivirow2["myyntihinta"])." $yhtiorow[valkoodi]\n";
									$tehdasvaruste .= "<tr><td>$tilrivirow2[nimitys] $lisavarsarjalisa</td><td align='right'>$tilrivirow2[kpl] KPL</td><td align='right'>".hintapyoristys($tilrivirow2["myyntihinta"])." $yhtiorow[valkoodi]</td></tr>";
								}
							}
							elseif(substr($hinnat,0,2) == "ME") {
								$text_output1   .= "$tilrivirow2[nimitys] $lisavarsarjalisa### ###$tilrivirow2[kpl] KPL\n";
								$tehdasvaruste  .= "<tr><td>$tilrivirow2[nimitys] $lisavarsarjalisa</td><td align='right'>$tilrivirow2[kpl] KPL</td><td align='right'></td></tr>";
							}
						}

						$tehdasvaruste .= "</table>";
					}
				}

				if ($hinnat == "" and $kukarow["extranet"] == "") {
					$sisviestit .= "<tr><th>Lis‰varusteet:</th><td align='right' colspan='2'>".hintapyoristys($lisaostohinta)." $yhtiorow[valkoodi]</td></tr>";
					$sisviestit .= "<tr><th>Ostohinta yht:</th><td align='right' colspan='2'>".hintapyoristys($ostohinta+$lisaostohinta+$kuluohinta)." $yhtiorow[valkoodi]</td></tr>";
					$sisviestit .= "<tr><th>Ostohinta alv:</th><td align='right' colspan='2'>".hintapyoristys($ostohinta_alv+$lisaostohinta_alv+$kuluohinta_alv)." $yhtiorow[valkoodi]</td></tr>";

					$text_output .= "Lis‰varusteet:### ### ###".hintapyoristys($lisaostohinta)." $yhtiorow[valkoodi]\n";
					$text_output .= "Ostohinta yht:### ### ###".hintapyoristys($ostohinta+$lisaostohinta+$kuluohinta)." $yhtiorow[valkoodi]\n";
					$text_output .= "Ostohinta alv:### ### ###".hintapyoristys($ostohinta_alv+$lisaostohinta_alv+$kuluohinta_alv)." $yhtiorow[valkoodi]\n";
				}
				elseif($hinnat == "MY") {
					if ($sarjarow["kaytetty"] == "") {
						$sisviestit .= "<tr><td class='back'></td></tr>";
						$sisviestit .= "<tr><th>Myyntihinta:</th><td align='right' colspan='2'>".hintapyoristys($tilrivirow["myyntihinta"])." $yhtiorow[valkoodi]</td></tr>";
						$sisviestit .= "<tr><th>Lis‰varusteet:</th><td align='right' colspan='2'>".hintapyoristys($lisamyyntihinta)." $yhtiorow[valkoodi]</td></tr>";
						$sisviestit .= "<tr><th>Myyntihinta yht:</th><td align='right' colspan='2'>".hintapyoristys($tilrivirow["myyntihinta"]+$lisamyyntihinta)." $yhtiorow[valkoodi]</td></tr>";

						$text_output .= "Venehinta:### ### ###$tilrivirow[myyntihinta] $yhtiorow[valkoodi]\n";
						$text_output .= "Lis‰varusteet:### ### ###".hintapyoristys($lisamyyntihinta)." $yhtiorow[valkoodi]\n";
						$text_output .= "Myyntihinta yht:### ### ###".hintapyoristys($tilrivirow["myyntihinta"]+$lisamyyntihinta)." $yhtiorow[valkoodi]\n";
						$text_output .= "\n";
					}
					else {
						//Hmm?
					}
				}

				$hankintahinta = hintapyoristys($ostohinta+$lisaostohinta+$kuluohinta);
				$tuotemyyntihinta = hintapyoristys($tilrivirow["myyntihinta"]+$lisamyyntihinta);

				$text_output .= "\n".$text_output1;

			}
		}

		if ($sarjarow["myyntirivitunnus"] > 0 and $hinnat == "" and $kukarow["extranet"] == "") {

			$query = "	SELECT group_concat(tunnus) tunnukset
						FROM tilausrivi
						WHERE tilausrivi.yhtio	= '$sarjarow[yhtio]'
						and tilausrivi.perheid2	= '$sarjarow[myyntirivitunnus]'
						and tilausrivi.laskutettuaika != '0000:00:00'
						and tilausrivi.kpl != 0";
			$tilrivires = pupe_query($query);
			$tilrivirow = mysql_fetch_array($tilrivires);

			if ($tilrivirow["tunnukset"] != '') {
				$myty = $tilrivirow["tunnukset"];
			}
			else {
				$myty = $sarjarow["myyntirivitunnus"];
			}

			$query = "	SELECT
						round(sum(if(tilausrivi.alv<500, tilausrivi.rivihinta*(1+(tilausrivi.alv/100)), tilausrivi.rivihinta)/tilausrivi.kpl), 2) hinta,
						round((sum(tilausrivi.kate)/sum(tilausrivi.rivihinta)) * 100, 2) kate
						FROM tilausrivi use index (PRIMARY)
						WHERE tilausrivi.yhtio	= '$sarjarow[yhtio]'
						and tilausrivi.tunnus	in ($myty)
						and tilausrivi.laskutettuaika != '0000:00:00'
						and tilausrivi.kpl != 0";
			$tilrivires = pupe_query($query);

			if (mysql_num_rows($tilrivires) > 0) {
				$tilrivirow = mysql_fetch_array($tilrivires);

				// Halutaan tiet‰‰ varastop‰ivien laskennassa
				$myyty = "KYLLƒ";

				$sisviestit .= "<tr><td class='back'></td></tr>";
				$sisviestit .= "<tr><th>Myyntihinta:</th><td align='right' colspan='2'>".hintapyoristys($tilrivirow["hinta"])." $yhtiorow[valkoodi]</td></tr>";
				$sisviestit .= "<tr><th>Kate:</th><td align='right' colspan='2'>".hintapyoristys($tilrivirow["kate"])." %</td></tr>";
			}
		}

		$sisviestit .= "</table>";

		if ($eilisatietoja != 'on') {
			//Haetaan sarjanumeron lis‰tiedot:
			$query = "	SELECT
						sarjanumeron_lisatiedot.tunnus,
						a7.selitetark_2 Merkki,
						sarjanumeron_lisatiedot.Malli,
						sarjanumeron_lisatiedot.Vuosimalli,
						concat(sarjanumeron_lisatiedot.Hinta,' $yhtiorow[valkoodi] + Toim.kulut') 'Hintapyyntˆ',
						concat(sarjanumeron_lisatiedot.Toimituskulut, ' EUR') Toimituskulut,
						if (sarjanumeron_lisatiedot.julkaisu='', '<font style=\'color:#FF0000;\'>Ei</font>', '<font style=\'color:#00FF00;\'>Kyll‰</font>') Julkaisu,
						concat(a11.selitetark_2, '       ', a11.selitetark_3) Sijainti,
						sarjanumeron_lisatiedot.Rekisterinumero,
						a6.selitetark_2 Runkomateriaali,
						a18.selitetark_2 'V‰ri',
						a15.selitetark_2 Tyyppi,
						a20.selitetark_2 Runkotyyppi,
						a8.selitetark_2 'Moottorin Merkki',
						sarjanumeron_lisatiedot.Koneen_malli 'Moottorin Malli',
						concat(sarjanumeron_lisatiedot.Koneiden_lkm, ' KPL') 'Moottoreita',
						concat(sarjanumeron_lisatiedot.Teho_hv, ' HV') 'Moottorin Teho',
						a3.selitetark_2 'Moottorin Tyyppi',
						sarjanumeron_lisatiedot.Polttoaine Polttoaine,
						sarjanumeron_lisatiedot.Koneen_vuosimalli 'Moottorin Vuosimalli',
						a9.selitetark_2 'Ohjausj‰rjestelm‰',
						sarjanumeron_lisatiedot.Kayttotunnit 'Moottorin K‰yttˆtunnit',
						concat(sarjanumeron_lisatiedot.Polttoainetankki, ' L') Polttoainetankki,
						concat(sarjanumeron_lisatiedot.Kayttovesisailio, ' L') 'K‰yttˆvesis‰iliˆ',
						concat(sarjanumeron_lisatiedot.Septitankki, ' L') Septitankki,
						sarjanumeron_lisatiedot.Hyttien_lukumaara 'Hyttien lukum‰‰r‰',
						sarjanumeron_lisatiedot.Makuupaikkojen_lkm1 'Makuupaikkojen lkm',
						sarjanumeron_lisatiedot.WC_lukumaara 'WC lukum‰‰r‰',
						a19.selitetark_2 'WC ja suihku',
						sarjanumeron_lisatiedot.Liesin_Ceran_pinnalla 'Liesin Ceran pinnalla',
						sarjanumeron_lisatiedot.Nopeusmittari,
						sarjanumeron_lisatiedot.Kompassi,
						sarjanumeron_lisatiedot.Defroster,
						sarjanumeron_lisatiedot.Mikroaaltouuni,
						sarjanumeron_lisatiedot.Pakastin,
						sarjanumeron_lisatiedot.TV_antenni 'TV antenni',
						sarjanumeron_lisatiedot.GPS,
						sarjanumeron_lisatiedot.Satamapeite,
						sarjanumeron_lisatiedot.Ajokuomu,
						sarjanumeron_lisatiedot.Maasahko 'Maas‰hkˆ',
						sarjanumeron_lisatiedot.Generaattori,
						sarjanumeron_lisatiedot.Ankkuri,
						sarjanumeron_lisatiedot.Keulapotkuri,
						sarjanumeron_lisatiedot.Lamminvesijarjestelma 'L‰mminvesij‰rjestelm‰',
						sarjanumeron_lisatiedot.Trimmitasot,
						sarjanumeron_lisatiedot.Makeavesijarjestelma 'Makeavesij‰rjestelm‰',
						sarjanumeron_lisatiedot.Suihku_WCssa 'Suihku WC:ss‰',
						sarjanumeron_lisatiedot.Suihku_uimatasolla 'Suihku uimatasolla',
						sarjanumeron_lisatiedot.Sailytyspukki 'S‰ilytyspukki',
						sarjanumeron_lisatiedot.Raitatiikkilattia,
						sarjanumeron_lisatiedot.Myrkkymaalaus,
						sarjanumeron_lisatiedot.Runkovalonheittimet,
						sarjanumeron_lisatiedot.Vesi_WC 'Vesi WC',
						sarjanumeron_lisatiedot.Runkoikkunat,
						sarjanumeron_lisatiedot.Avotilan_poyta 'Avotilan poyt‰',
						sarjanumeron_lisatiedot.Kaksoisakkujarjestelma 'Kaksoisakkuj‰rjestelm‰',
						sarjanumeron_lisatiedot.Sumutorvi,
						sarjanumeron_lisatiedot.Keulaluukku,
						sarjanumeron_lisatiedot.Avotilan_penkki 'Avotilan penkki',
						sarjanumeron_lisatiedot.Liesituuletin,
						sarjanumeron_lisatiedot.Tuulilasin_peite 'Tuulilasin peite',
						sarjanumeron_lisatiedot.Aurinkokatos,
						sarjanumeron_lisatiedot.Avotilan_kuomu 'Avotilan kuomu',
						sarjanumeron_lisatiedot.Jaapalakone 'J‰‰palakone',
						sarjanumeron_lisatiedot.Liesi,
						sarjanumeron_lisatiedot.Kaiku,
						concat_ws(' ',sarjanumeron_lisatiedot.Jaakaappi_kpl,a2.selitetark_2) 'J‰‰k‰‰ppi',
						sarjanumeron_lisatiedot.Tutka,
						sarjanumeron_lisatiedot.Karttaplotteri,
						a17.selitetark_2 Valonheitin,
						a1.selitetark_2 Ankkurivinssi,
						sarjanumeron_lisatiedot.Lammitys 'L‰mmitys',
						sarjanumeron_lisatiedot.Lammityksen_malli 'L‰mmityksen Malli',
						sarjanumeron_lisatiedot.Lammityksen_vuosimalli 'L‰mmityksen Vuosimalli',
						sarjanumeron_lisatiedot.Lammityksen_polttoaine 'L‰mmityksen Polttoaine',
						sarjanumeron_lisatiedot.Lammityksen_lisatiedot 'L‰mmityksen Lis‰tiedot',
						sarjanumeron_lisatiedot.CD_ja_Radio_soitin 'CD ja Radio soitin',
						sarjanumeron_lisatiedot.Autopilotti,
						a16.selitetark_2 Uuni,
						sarjanumeron_lisatiedot.Televisio,
						a14.selitetark_2 Tyynysarja,
						a12.selitetark_2 Tiikkisarja,
						a10.selitetark_2 Pilssipumppu,
						sarjanumeron_lisatiedot.Sprinkleri
						FROM sarjanumeron_lisatiedot use index (yhtio_liitostunnus)
						LEFT JOIN avainsana a1 use index (yhtio_laji_selitetark) ON a1.yhtio=sarjanumeron_lisatiedot.yhtio and a1.laji='sarjanumeron_li' and a1.selitetark=sarjanumeron_lisatiedot.Ankkurivinssi and a1.selite='ANKKURIVINSSI'
						LEFT JOIN avainsana a2 use index (yhtio_laji_selitetark) ON a2.yhtio=sarjanumeron_lisatiedot.yhtio and a2.laji='sarjanumeron_li' and a2.selitetark=sarjanumeron_lisatiedot.Jaakaappi and a2.selite='JAAKAAPPI'
						LEFT JOIN avainsana a3 use index (yhtio_laji_selitetark) ON a3.yhtio=sarjanumeron_lisatiedot.yhtio and a3.laji='sarjanumeron_li' and a3.selitetark=sarjanumeron_lisatiedot.Koneistus and a3.selite='KONEISTUS'
						LEFT JOIN avainsana a4 use index (yhtio_laji_selitetark) ON a4.yhtio=sarjanumeron_lisatiedot.yhtio and a4.laji='sarjanumeron_li' and a4.selitetark=sarjanumeron_lisatiedot.Kuvatyyppi and a4.selite='KUVATYYPPI'
						LEFT JOIN avainsana a5 use index (yhtio_laji_selitetark) ON a5.yhtio=sarjanumeron_lisatiedot.yhtio and a5.laji='sarjanumeron_li' and a5.selitetark=sarjanumeron_lisatiedot.Laatuluokitus and a5.selite='LAATU'
						LEFT JOIN avainsana a6 use index (yhtio_laji_selitetark) ON a6.yhtio=sarjanumeron_lisatiedot.yhtio and a6.laji='sarjanumeron_li' and a6.selitetark=sarjanumeron_lisatiedot.Materiaali and a6.selite='MATERIAALI'
						LEFT JOIN avainsana a7 use index (yhtio_laji_selitetark) ON a7.yhtio=sarjanumeron_lisatiedot.yhtio and a7.laji='sarjanumeron_li' and a7.selitetark=sarjanumeron_lisatiedot.Merkki and a7.selite='MERKKI'
						LEFT JOIN avainsana a8 use index (yhtio_laji_selitetark) ON a8.yhtio=sarjanumeron_lisatiedot.yhtio and a8.laji='sarjanumeron_li' and a8.selitetark=sarjanumeron_lisatiedot.Koneen_merkki and a8.selite='MOOTTORINMERKKI'
						LEFT JOIN avainsana a9 use index (yhtio_laji_selitetark) ON a9.yhtio=sarjanumeron_lisatiedot.yhtio and a9.laji='sarjanumeron_li' and a9.selitetark=sarjanumeron_lisatiedot.Moottorin_ohjausjarjestelma and a9.selite='MOOTTORINOHJAUS'
						LEFT JOIN avainsana a10 use index (yhtio_laji_selitetark) ON a10.yhtio=sarjanumeron_lisatiedot.yhtio and a10.laji='sarjanumeron_li' and a10.selitetark=sarjanumeron_lisatiedot.Pilssipumppu and a10.selite='PILSSIPUMPPU'
						LEFT JOIN avainsana a11 use index (yhtio_laji_selitetark) ON a11.yhtio=sarjanumeron_lisatiedot.yhtio and a11.laji='sarjanumeron_li' and a11.selitetark=sarjanumeron_lisatiedot.Sijainti and a11.selite='SIJAINTI'
						LEFT JOIN avainsana a12 use index (yhtio_laji_selitetark) ON a12.yhtio=sarjanumeron_lisatiedot.yhtio and a12.laji='sarjanumeron_li' and a12.selitetark=sarjanumeron_lisatiedot.Tiikkisarja and a12.selite='TIIKKISARJA'
						LEFT JOIN avainsana a13 use index (yhtio_laji_selitetark) ON a13.yhtio=sarjanumeron_lisatiedot.yhtio and a13.laji='sarjanumeron_li' and a13.selitetark=sarjanumeron_lisatiedot.Toimituskulut and a13.selite='TOIMITUSKULUT'
						LEFT JOIN avainsana a14 use index (yhtio_laji_selitetark) ON a14.yhtio=sarjanumeron_lisatiedot.yhtio and a14.laji='sarjanumeron_li' and a14.selitetark=sarjanumeron_lisatiedot.Tyynysarja and a14.selite='TYYNYSARJA'
						LEFT JOIN avainsana a15 use index (yhtio_laji_selitetark) ON a15.yhtio=sarjanumeron_lisatiedot.yhtio and a15.laji='sarjanumeron_li' and a15.selitetark=sarjanumeron_lisatiedot.Tyyppi and a15.selite='TYYPPI'
						LEFT JOIN avainsana a16 use index (yhtio_laji_selitetark) ON a16.yhtio=sarjanumeron_lisatiedot.yhtio and a16.laji='sarjanumeron_li' and a16.selitetark=sarjanumeron_lisatiedot.Uuni and a16.selite='UUNI'
						LEFT JOIN avainsana a17 use index (yhtio_laji_selitetark) ON a17.yhtio=sarjanumeron_lisatiedot.yhtio and a17.laji='sarjanumeron_li' and a17.selitetark=sarjanumeron_lisatiedot.Valonheitin and a17.selite='VALONHEITIN'
						LEFT JOIN avainsana a18 use index (yhtio_laji_selitetark) ON a18.yhtio=sarjanumeron_lisatiedot.yhtio and a18.laji='sarjanumeron_li' and a18.selitetark=sarjanumeron_lisatiedot.Varirunko and a18.selite='VARIRUNKO'
						LEFT JOIN avainsana a19 use index (yhtio_laji_selitetark) ON a19.yhtio=sarjanumeron_lisatiedot.yhtio and a19.laji='sarjanumeron_li' and a19.selitetark=sarjanumeron_lisatiedot.WC_ja_suihku and a19.selite='VESSA'
						LEFT JOIN avainsana a20 use index (yhtio_laji_selitetark) ON a20.yhtio=sarjanumeron_lisatiedot.yhtio and a20.laji='sarjanumeron_li' and a20.selitetark=sarjanumeron_lisatiedot.Runkotyyppi and a20.selite='RUNKOTYYPPI'
						LEFT JOIN avainsana a21 use index (yhtio_laji_selitetark) ON a21.yhtio=sarjanumeron_lisatiedot.yhtio and a21.laji='sarjanumeron_li' and a21.selitetark=sarjanumeron_lisatiedot.Kilpi and a21.selite='KILPI'
						LEFT JOIN avainsana a22 use index (yhtio_laji_selitetark) ON a22.yhtio=sarjanumeron_lisatiedot.yhtio and a22.laji='sarjanumeron_li' and a22.selitetark=sarjanumeron_lisatiedot.Sprinkleri and a22.selite='SPRINKLERI'
						WHERE sarjanumeron_lisatiedot.yhtio		 = '$yhtiolisa'
						and sarjanumeron_lisatiedot.liitostunnus = '$sarjarow[tunnus]'";
			$lisatietores = pupe_query($query);
			$lisatietorow = mysql_fetch_assoc($lisatietores);

			if (mysql_num_rows($lisatietores) > 0 or ($kukarow["extranet"] == "" and $myyty == "")) {
				$lisatiedotlisa  = "<table width='100%'>";
				$lisatiedotlisa .= "<tr><th colspan='2'>".t("Lis‰tiedot").":</th></tr>";
				$text_output .= "\n";

				if ($kukarow["extranet"] == "" and $myyty == "" and $hinnat != "MY") {
					$lisatiedotlisa .= "<tr><td>".t("Varastointiaika")."</td><td>$osvarpvm ".t("pva").". (".tv1dateconv($ostonpvm).")</td></tr>";
					$text_output .= t("Varastointiaika")."###$osvarpvm ".t("pva").". (".tv1dateconv($ostonpvm).")\n";
				}
			}

			if (mysql_num_rows($lisatietores) > 0) {
				foreach ($lisatietorow as $ind => $val) {
					if(((!is_numeric($val) and $val != '') or (is_numeric($val) and $val != 0)) and $val != "0.00 L" and $val != '0000-00-00' and $ind != 'tunnus') {
						if ($val == "o") {
							$lisatiedotlisa .= "<tr><td>$ind</td><td>&nbsp;<img width='10px' heigth='10px' src='../pics/vihrea.png'></td></tr>";
							$text_output .= $ind."###On\n";
						}
						else {
							$lisatiedotlisa .= "<tr><td>$ind</td><td>$val</td></tr>";
							$text_output .= $ind."###".$val."\n";
						}
					}
				}
			}

			if (mysql_num_rows($lisatietores) > 0 or ($kukarow["extranet"] == "" and $myyty == "")) {
				$lisatiedotlisa .= "</table>";
			}

			$query  = "	SELECT *
						from liitetiedostot
						where yhtio = '$yhtiolisa'
						and liitos  = 'sarjanumeroseuranta'
						and liitostunnus = '$sarjatunnus'
						order by jarjestys, tunnus";
			$lisatietores = pupe_query($query);

			$kuvalisa = "";
			$kuvalisa_bin = array();

			if (mysql_num_rows($lisatietores) > 0) {
				while($lisatietorow = mysql_fetch_array($lisatietores)) {

					$kuvalisa .= "<img width='200px' src='../view.php?id=$lisatietorow[tunnus]'>";

					$query = "SELECT * from liitetiedostot where tunnus='$lisatietorow[tunnus]'";
					$liiteres = pupe_query($query);

					if (mysql_num_rows($liiteres) > 0) {
						$liiterow = mysql_fetch_array($liiteres);
						$kuvalisa_bin[] = array($liiterow["data"], $liiterow["image_width"], $liiterow["image_height"]);
					}
				}
			}
		}


		if ($leveys != '') {
			$leveys = " width='$leveys' ";
		}

		$divit  = "<div id='$sarjarow[tunnus]' class='$class'>";
		$divit .= "<table $leveys>";

		$divit .= "<tr><td class='back ptop' align='center' width='50%'>";
		$divit .= $sisviestit;
		$divit .= "</td>";
		$divit .= "<td class='back ptop' align='center' width='50%'>";
		$divit .= $kuvalisa;
		$divit .= "</td></tr>";

		$divit .= "<tr><td class='back ptop' align='center' width='50%'>";
		$divit .= $tehdasvaruste;
		$divit .= "</td>";
		$divit .= "<td class='back ptop' align='center' width='50%'>";
		$divit .= $lisatiedotlisa;
		$divit .= "</td></tr>";

		if ($class != "") {
			$divit .= "<tr><td colspan='2' class='back' align='center'><a href='#' onclick=\"popUp(event,'$sarjarow[tunnus]')\">Sulje</a></td></tr>";
		}

		$divit .= "</table>";
		$divit .= "</div>";

		return(array($divit, $text_output, $kuvalisa_bin, $hankintahinta, $tuotemyyntihinta));
	}
?>
