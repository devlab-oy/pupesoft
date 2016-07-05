<?php

	if ($tee == 'osamaksusoppari' and $tulosta_osamakusoppari == "") {
		echo "<font class='head'>".t("Rahoituslaskelma")."</font><hr>";
	}

	if ($tee == 'osamaksusoppari') {
		$jaannosvelka_vaihtokohteesta	= str_replace(",", ".", $jaannosvelka_vaihtokohteesta);
		$perustamiskustannus			= str_replace(",", ".", $perustamiskustannus);
		$muutluottokustannukset			= str_replace(",", ".", $muutluottokustannukset);
		$erankasittelymaksu				= str_replace(",", ".", $erankasittelymaksu);
		$viitekorko						= str_replace(",", ".", $viitekorko);
		$marginaalikorko				= str_replace(",", ".", $marginaalikorko);
		$tilinavausmaksu				= str_replace(",", ".", $tilinavausmaksu);
		$sopajankorko					= str_replace(",", ".", $sopajankorko);
	}

	// Päivitetään tiedot tietokantaan
	if ($tee == 'osamaksusoppari' and $tilausnumero > 0 and isset($omaformi)) {
		$query = "	SELECT tunnus
					FROM laskun_lisatiedot
					WHERE yhtio	= '$kukarow[yhtio]'
					and otunnus = '$tilausnumero'";
		$res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($res) > 0) {
			$query  = "	UPDATE laskun_lisatiedot
						SET rahlaskelma_rahoitettava_positio 		= '$rahlaskelma_rahoitettava_positio',
						rahlaskelma_jaannosvelka_vaihtokohteesta	= '$jaannosvelka_vaihtokohteesta',
						rahlaskelma_perustamiskustannus 			= '$perustamiskustannus',
						rahlaskelma_muutluottokustannukset 			= '$muutluottokustannukset',
						rahlaskelma_sopajankorko 					= '$sopajankorko',
						rahlaskelma_maksuerienlkm 					= '$maksuerienlkm',
						rahlaskelma_luottoaikakk 					= '$luottoaikakk',
						rahlaskelma_ekaerpcm 						= '$vva-$kka-$ppa',
						rahlaskelma_erankasittelymaksu				= '$erankasittelymaksu',
						rahlaskelma_tilinavausmaksu 				= '$tilinavausmaksu',
						rahlaskelma_viitekorko 						= '$viitekorko',
						rahlaskelma_marginaalikorko 				= '$marginaalikorko',
						rahlaskelma_lyhennystapa 					= '$lyhennystapa',
						rahlaskelma_poikkeava_era					= '$poikkeava_era',
						rekisteilmo_omistaja						= '$rekisteilmo_omistaja'
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus	= '$tilausnumero' ";
			$res = mysql_query($query) or pupe_error($query);
		}
		else {
			$query  = "	INSERT INTO laskun_lisatiedot
						SET yhtio 									= '$kukarow[yhtio]',
						otunnus 									= '$tilausnumero',
						rahlaskelma_rahoitettava_positio 			= '$rahlaskelma_rahoitettava_positio',
						rahlaskelma_jaannosvelka_vaihtokohteesta	= '$jaannosvelka_vaihtokohteesta',
						rahlaskelma_perustamiskustannus 			= '$perustamiskustannus',
						rahlaskelma_muutluottokustannukset 			= '$muutluottokustannukset',
						rahlaskelma_sopajankorko 					= '$sopajankorko',
						rahlaskelma_maksuerienlkm 					= '$maksuerienlkm',
						rahlaskelma_luottoaikakk 					= '$luottoaikakk',
						rahlaskelma_ekaerpcm 						= '$vva-$kka-$ppa',
						rahlaskelma_erankasittelymaksu				= '$erankasittelymaksu',
						rahlaskelma_tilinavausmaksu 				= '$tilinavausmaksu',
						rahlaskelma_viitekorko 						= '$viitekorko',
						rahlaskelma_marginaalikorko 				= '$marginaalikorko',
						rahlaskelma_lyhennystapa 					= '$lyhennystapa',
						rahlaskelma_poikkeava_era					= '$poikkeava_era',
						rekisteilmo_omistaja						= '$rekisteilmo_omistaja'";
			$res = mysql_query($query) or pupe_error($query);
		}

		if (isset($VALMIS)) {
			$tee = "";
		}
	}

	if ($nrahdemo == 1 and $nrahtee == "POISTA") {
		$query  = "	UPDATE laskun_lisatiedot
					SET rahlaskelma_nfref = '0'
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus	= '$tilausnumero' ";
		$res = mysql_query($query) or pupe_error($query);

		$nrahtee = "";
	}

	if ($tee == 'osamaksusoppari') {

		// Laskun tiedot
		$query 	= "	SELECT laskun_lisatiedot.*, lasku.*
					FROM lasku
					LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
					WHERE lasku.tunnus	= '$tilausnumero'
					and lasku.yhtio		= '$kukarow[yhtio]'";
		$result  	= mysql_query($query) or pupe_error($query);
		$laskurow   = mysql_fetch_array($result);

		// Asiakkaan tiedot
		$query 	= "	SELECT *
					FROM asiakas
					WHERE tunnus	= '$laskurow[liitostunnus]'
					and yhtio		= '$kukarow[yhtio]'";
		$result  	= mysql_query($query) or pupe_error($query);
		$asiakasrow = mysql_fetch_array($result);

		if (!function_exists('summarivit')) {
			function summarivit($osasto, $laskurow) {
				global $yhtiorow, $kukarow;

				$total 	= 0;

				$query_ale_lisa = generoi_alekentta('M');

				$query = "	SELECT tilausrivi.*,
				 			round(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}, 2) rivihinta,
							tuote.tuoteno, tilausrivin_lisatiedot.positio
							FROM tilausrivi
							JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
							WHERE tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.tyyppi in  ('L','T','U')
							$osasto";
				$riresult = mysql_query($query) or pupe_error($query);

				while ($row = mysql_fetch_array($riresult)) {
					$total += $row["rivihinta"]; 		// lasketaan tilauksen loppusummaa MUUT RIVIT.. (pitää olla laskuttamaton, muuten erikoisale on jo jyvitetty)
				}
				return $total;
			}
		}

		// A. Rahoitettava positio
		$rahlaskelma_rahoitettava_positio = $laskurow["rahlaskelma_rahoitettava_positio"];

		// 1. Uusi vene
		$uusivene = summarivit("HAVING tilausrivin_lisatiedot.positio='Vene' or tilausrivin_lisatiedot.positio='Moottori'", $laskurow);

		// 2. Uudet tarvikkeet
		$uusitarvike = summarivit("HAVING (tilausrivin_lisatiedot.positio not like '%vene%' and tilausrivin_lisatiedot.positio not like '%moottori%') and tuote.tuoteno not in ('$yhtiorow[rahti_tuotenumero]', '$yhtiorow[ennakkomaksu_tuotenumero]')", $laskurow);

		// 3. Toimituskulut
		$toimkulu = summarivit("HAVING tuote.tuoteno = '$yhtiorow[rahti_tuotenumero]'", $laskurow);

		// 4 = 1+2+3
		$kateishinta_kaupan_kohteesta = $uusivene+$uusitarvike+$toimkulu;

		// 5. Hyvitys vaihtoveneestä
		$hyvitys_vaihtoveneesta = summarivit("HAVING (tilausrivin_lisatiedot.positio='Vaihtovene' or tilausrivin_lisatiedot.positio='Vaihtomoottori')", $laskurow) * -1;

		// 6. Jäännösvelka vaihtokohteesta
		$jaannosvelka_vaihtokohteesta = $laskurow["rahlaskelma_jaannosvelka_vaihtokohteesta"];

		// 7 = 5+6
		$hyvitysosuus_vaihtokohteesta = $hyvitys_vaihtoveneesta - $jaannosvelka_vaihtokohteesta;

		// 8. Ennakkomaksu
		// 9. Muut maskut ennen toimitusta
		$ennakkomaksu = 0;
		$muut_maksut_ennen_toimitusta = 0;

		if ($rahlaskelma_rahoitettava_positio > 0) {
			$query = "	SELECT *
						FROM maksupositio
						WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$laskurow[jaksotettu]' and tunnus != '$rahlaskelma_rahoitettava_positio'
						order by summa desc";
			$result = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($result) > 0) {
				$ennlask = 0;

				while ($positiorow = mysql_fetch_array($result)) {

					if ($yhtiorow["alv_kasittely"] != '') {
						$positiorow["summa"] = round($positiorow["summa"] * (1+($laskurow["alv"]/100)), 2);
					}

					if ($ennlask == 0) {
						$ennakkomaksu += $positiorow["summa"];
					}
					else {
						$muut_maksut_ennen_toimitusta += $positiorow["summa"];
					}
					$ennlask++;
				}
			}
		}

		// 10 = 7+8+9
		$kateismaksuosuus_yhteensa = $hyvitysosuus_vaihtokohteesta + $ennakkomaksu + $muut_maksut_ennen_toimitusta;
		//$kateismaksuosuus_yhteensa = round(round($kateismaksuosuus_yhteensa / 0.05) * 0.05, 2);

		// 11 = 4-10
		$velkaosuus_kateishinnasta = $kateishinta_kaupan_kohteesta - $kateismaksuosuus_yhteensa;

		// 12 Luottosuhteen perustamiskustannus
		$perustamiskustannus = $laskurow["rahlaskelma_perustamiskustannus"];

		// 13 Muut luottokustannukset
		$muutluottokustannukset = $laskurow["rahlaskelma_muutluottokustannukset"];

		// 14 = 11+12+13
		$rahoitettavaa_yhteensa = $velkaosuus_kateishinnasta + $perustamiskustannus + $muutluottokustannukset;

		// Sopimusajan korko
		$sopajankorko = $laskurow["rahlaskelma_sopajankorko"];

		// Osamaksuerien lukumäärä
		$maksuerienlkm = $laskurow["rahlaskelma_maksuerienlkm"];

		// Luottoaika kuukautta
		$luottoaikakk = $laskurow["rahlaskelma_luottoaikakk"];

		// Maksuväli kuukausissa
		if ($maksuerienlkm != 0) {
			$maksuvali_kk = round($luottoaikakk / $maksuerienlkm, 2) * 1;
		}
		else {
			$maksuvali_kk = 0;
		}


		// Ensimmäisen erän eräpäivämäärä
		if ($laskurow["rahlaskelma_ekaerpcm"] != '0000-00-00') {
			$kka = substr($laskurow["rahlaskelma_ekaerpcm"],5,2);
			$vva = substr($laskurow["rahlaskelma_ekaerpcm"],0,4);
			$ppa = substr($laskurow["rahlaskelma_ekaerpcm"],8,2);
		}
		else {
			$kka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$vva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$ppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		}

		$ekaerpcm 		 = $ppa.".".$kka.".".$vva;
		$ekaerpcm_nordea = ($ppa*1).".".($kka*1).".".$vva;

		// Viimeisen erän eräpvm
		$vikaerpcm = date("d.m.Y",mktime(0, 0, 0, $kka+$luottoaikakk-1, $ppa, $vva));

		// Viimeinen erä
		$poikkeava_era = $laskurow["rahlaskelma_poikkeava_era"];

		// Käsittelymaksu per erä
		$erankasittelymaksu = $laskurow["rahlaskelma_erankasittelymaksu"];

		// Tasaerälainan kokonaiskorkokustannus & Tasalyhennys kokonaiskorkokustannus
		$korko_te 	= 0;
		$maksut_te	= array();
		$korko_tl 	= 0;
		$maksut_tl	= array();

		// Korkoa maksetaan koko pääomasta nykyhetkestä ekaan eräpäivään saakka
		$kkn = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		$vvn = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		$ppn = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));

		$query  	= "SELECT TO_DAYS('$vva-$kka-$ppa')-TO_DAYS('$vvn-$kkn-$ppn') ero";
		$dateresult = mysql_query($query) or pupe_error($query);
		$daterow    = mysql_fetch_array($dateresult);
		$alkukaika  = $daterow["ero"]/360*12;

		// Oletuksena tulee yhden maksuvälin verran korkoa koko pääomasta
		if ($alkukaika > 0) {
			//$korko_te += round($sopajankorko / 100 * $alkukaika / 12 * $rahoitettavaa_yhteensa, 2);

			$maksuerienlkm_for 			= $maksuerienlkm;
			$luottoaikakk_for 			= $luottoaikakk;
			$rahoitettavaa_yhteensa_for = $rahoitettavaa_yhteensa;
		}
		else {
			$maksuerienlkm_for 			= $maksuerienlkm;
			$luottoaikakk_for 			= $luottoaikakk;
			$rahoitettavaa_yhteensa_for = $rahoitettavaa_yhteensa;
		}

		// Tasaerälainan kokonaiskorkokustannus & Tasalyhennys kokonaiskorkokustannus
		$paaoma_te	= $rahoitettavaa_yhteensa_for;
		$paaoma_tl	= $rahoitettavaa_yhteensa_for;

		// Lasketaan annuiteetti, eli tasaerä
		// $N = rahoitettava osuus yhteensä
		// $p = vuotuinen korkokanta
		// $n = maksuerien lukumäärä
		// $m = maksukertojen lukumäärä vuodessa
		// $A = tasaerälainan tasaerälyhennys
		$N = $p = $n = $m = $A = 0;

		if ($rahoitettavaa_yhteensa_for > 0 and $sopajankorko > 0 and $maksuerienlkm_for > 0 and $luottoaikakk_for > 0) {
			$N = $rahoitettavaa_yhteensa_for;
			$p = $sopajankorko;
			$n = $maksuerienlkm_for;
			$m = ($maksuerienlkm_for/$luottoaikakk_for);
			$a = $luottoaikakk_for;

			// Annuiteetin (Tasaerän) laskukaava
			if ($poikkeava_era != 0) {
				$A = sprintf('%.2f', ((($N-$poikkeava_era/(pow((($p/(12/$m))/100+1), ($a/$m))))*(pow((($p/(12/$m))/100+1), (($a/$m)-1)))*(($p/(12/$m))/100))/(((pow((($p/(12/$m))/100+1), (($a/$m)-1))))-1)));
			}
			else {
				$A = sprintf('%.2f', ((pow(1 + $p/(100*$m*12), $n) * $p/(100*$m*12)) / (pow(1 + $p/(100*$m*12), $n) - 1)) * $N);
			}

			for($kk = 0; $kk < $maksuerienlkm_for; $kk++) {
				// Koron osuus tästä maksuerästä
				$korko = round(($p/100/($m*12) * $paaoma_te),2);

				// Kumulatiivinen korko
				$korko_te += $korko;

				// Kuinka paljon tämä erä lyhentää rahoitettavaa pääomaa
				$lyhennys =  $A - $korko;

				// Jäljellä oleva pääoma
				$paaoma_te = $paaoma_te - $lyhennys;

				$maksut_te[$kk]['paaoma']	= $paaoma_te;
				$maksut_te[$kk]['korko']	= $korko;
				$maksut_te[$kk]['lyhennys']	= $lyhennys;
				$maksut_te[$kk]['summa']	= $A;
			}

			if ($paaoma_te != 0) {
				$maksut_te[($maksuerienlkm_for-1)]['paaoma']	-= $paaoma_te;
				$maksut_te[($maksuerienlkm_for-1)]['summa']		+= $paaoma_te;
				$maksut_te[($maksuerienlkm_for-1)]['lyhennys']	+= $paaoma_te;
			}

			//Tasaerälainan poikkeava vika erä
			if ($poikkeava_era > 0) {
				$ind = count($maksut_te)-1;

				$maksut_te[$ind]['paaoma']		= 0;
				$maksut_te[$ind]['lyhennys']	= $poikkeava_era-$maksut_te[$ind]['korko'];
				$maksut_te[$ind]['summa']		= $poikkeava_era;
			}

			// Tasalyhennyslainan laskuri
			if ($poikkeava_era > 0) {
				$paaomaera_tl = ($paaoma_tl-($poikkeava_era / (1+($p/100/12)))) / ($maksuerienlkm_for-1);
			}
			else {
				$paaomaera_tl = $paaoma_tl / $maksuerienlkm_for;
			}

			for($kk = 0; $kk < $maksuerienlkm; $kk++) {
				// Koron osuus tästä maksuerästä
				$korko = round(($paaomaera_tl + ($p/100/($m*12) * ($paaoma_tl-($kk*$paaomaera_tl)))) - $paaomaera_tl, 2);

				// Kumulatiivinen korko
				$korko_tl += $korko;

				$maksut_tl[$kk]['korko']	= sprintf('%.2f', $korko);
				$maksut_tl[$kk]['lyhennys']	= sprintf('%.2f', $paaomaera_tl);
				$maksut_tl[$kk]['summa']	= sprintf('%.2f', $korko+$paaomaera_tl);

			}

			if ($poikkeava_era > 0) {
				$ind = count($maksut_tl)-1;

				$maksut_tl[$ind]['paaoma']	  = 0;
				$maksut_tl[$ind]['lyhennys']  = $poikkeava_era-$maksut_tl[$ind]['korko'];
				$maksut_tl[$ind]['summa']	  = $poikkeava_era;
			}

			/*
			echo "<table>";
			for($kk = 0; $kk < $maksuerienlkm_for; $kk++) {
				echo "<tr><td>$kk</td><td>".round($maksut_te[$kk]['paaoma'],2)."</td><td>".$maksut_te[$kk]['lyhennys']."</td><td>".$maksut_te[$kk]['korko']."</td><td>".$maksut_te[$kk]['summa']."</td></tr>";
			}
			echo "</table><br>";
			*/
			/*
			echo "<table>";
			for($kk = 0; $kk < $maksuerienlkm_for; $kk++) {
				echo "<tr><td>$kk</td><td>".round($maksut_tl[$kk]['paaoma'],2)."</td><td>".$maksut_tl[$kk]['lyhennys']."</td><td>".$maksut_tl[$kk]['korko']."</td><td>".$maksut_tl[$kk]['summa']."</td></tr>";
			}
			echo "</table>";
			*/
		}

		// 15 Korko sopimusajalta eräpäiviä noudatettaessa
		$koron_osuus_te = $korko_te;
		$koron_osuus_tl = $korko_tl;

		// 16 Tilinavausmaksu
		$tilinavausmaksu = $laskurow["rahlaskelma_tilinavausmaksu"];

		// 17 Käsittelymaksut yhteensä
		$kasittelymaksut_yhteensa = ($luottoaikakk * $erankasittelymaksu);

		// 18 = 12+13+15+16+17
		$luottokustannus_yhteensa_te = $perustamiskustannus + $muutluottokustannukset + $koron_osuus_te + $kasittelymaksut_yhteensa;
		$luottokustannus_yhteensa_tl = $perustamiskustannus + $muutluottokustannukset + $koron_osuus_tl + $kasittelymaksut_yhteensa;

		// 19 KSL 9.2 § mukainen luottohinta
		$ksl_luottohinta_te = $kateishinta_kaupan_kohteesta + $luottokustannus_yhteensa_te;
		$ksl_luottohinta_tl = $kateishinta_kaupan_kohteesta + $luottokustannus_yhteensa_tl;

		// Lyhennystapa
		$lyhennystapa = $laskurow["rahlaskelma_lyhennystapa"];

		// Viiterkorko
		$viitekorko = $laskurow["rahlaskelma_viitekorko"];
		$viitearray = array(1 => "Kiinteä", 2 => "Nordea Prime", 3 => "1 kk Euribor", 4 => "3 kk Euribor", 5 => "6 kk Euribor", 6 => "1 kk Stibor", 7 => "3 kk Stibor");

		//marginaalikorko
		$marginaalikorko = $laskurow["rahlaskelma_marginaalikorko"];

		// 20 KSL 9:11 §:n mukainen tod. vuosikorko
		if ($velkaosuus_kateishinnasta != 0 and $luottoaikakk != 0 and $sopajankorko != 0) {
			$ksl_vuosikorko = round(($kasittelymaksut_yhteensa + $perustamiskustannus + $muutluottokustannukset) * 100 * 2400 / $velkaosuus_kateishinnasta / ($luottoaikakk + 1) / 100 + $sopajankorko, 2);

			//$ksl_vuosikorko = $sopajankorko + (200 * 12 * (($erankasittelymaksu*$maksuerienlkm)+$perustamiskustannus + $muutluottokustannukset)) / ($rahoitettavaa_yhteensa * ($maksuerienlkm + $maksuerienlkm/$luottoaikakk));
			//$ksl_vuosikorko = (200 * 12 * $luottokustannus_yhteensa_te) / ($rahoitettavaa_yhteensa * ($maksuerienlkm + $maksuerienlkm/$luottoaikakk));
		}
		else {
			$ksl_vuosikorko = 0;
		}


		if ($tulosta_osamakusoppari == "") {

			echo "<form method='post' name='mainform'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='tilausnumero' value='$tilausnumero'>
					<input type='hidden' name='omaformi' value='1'>
					<input type='hidden' name='tee' value='osamaksusoppari'>";

			echo "<table>";

			$query = "	SELECT yhtio
						FROM oikeu
						WHERE yhtio	= '$kukarow[yhtio]'
						and kuka	= '$kukarow[kuka]'
						and nimi	= 'yllapito.php'
						and alanimi = 'asiakas!!!VAHITTAISMYYNTI!!!true&laji=H'
						and paivitys != ''";
			$oikeuresult1 = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($oikeuresult1) > 0) {
				echo "<tr>
						<th>".t("Asiakas")."</th>
						<td align='right'><a href='".$palvelin2."yllapito.php?toim=asiakas!!!VAHMYY_RAHHAK!!!true&laji=H&tunnus=$laskurow[liitostunnus]&lopetus=$PHP_SELF////toim=$toim//lopetus=$lopetus//tee=$tee//tilausnumero=$tilausnumero//from=LASKUTATILAUS'>$laskurow[nimi]</a></td><td></td></tr>";
			}


			$query = "	SELECT sarjanumeroseuranta.tunnus
						FROM tilausrivi
						JOIN sarjanumeroseuranta ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.myyntirivitunnus
						JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.positio='VENE'
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus = '$tilausnumero'
						LIMIT 1";
			$venvelisres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($venvelisres) > 0) {

				$lisrow = mysql_fetch_array($venvelisres);

				$query = "	SELECT *
							FROM sarjanumeron_lisatiedot use index (yhtio_liitostunnus)
							WHERE yhtio		 = '$kukarow[yhtio]'
							and liitostunnus = '$lisrow[tunnus]'";
				$lisares = mysql_query($query) or pupe_error($query);
				$lisarow = mysql_fetch_array($lisares);

				if ($lisarow["tunnus"] != 0) {
					$ylisa = "&tunnus=$lisarow[tunnus]";
				}
				else {
					$ylisa = "&liitostunnus=$lisrow[tunnus]&uusi=1";
				}

				echo "<tr>
						<th>".t("Veneen tiedot")."</th>
						<td align='right'><a href='../yllapito.php?toim=sarjanumeron_lisatiedot!!!RAHHAK!!!true$ylisa&lopetus=$PHP_SELF////toim=$toim//lopetus=$lopetus//tee=$tee//tilausnumero=$tilausnumero//from=LASKUTATILAUS'>".t("Veneen lisätiedot")."</a></td><td></td></tr>";
			}

			$query = "	SELECT sarjanumeroseuranta.tunnus
						FROM tilausrivi
						JOIN sarjanumeroseuranta ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
						JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.positio='VAIHTOVENE'
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus = '$tilausnumero'
						LIMIT 1";
			$venvelisres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($venvelisres) > 0) {

				$lisrow = mysql_fetch_array($venvelisres);

				$query = "	SELECT *
							FROM sarjanumeron_lisatiedot use index (yhtio_liitostunnus)
							WHERE yhtio		 = '$kukarow[yhtio]'
							and liitostunnus = '$lisrow[tunnus]'";
				$lisares = mysql_query($query) or pupe_error($query);
				$lisarow = mysql_fetch_array($lisares);

				if ($lisarow["tunnus"] != 0) {
					$ylisa = "&tunnus=$lisarow[tunnus]";
				}
				else {
					$ylisa = "&liitostunnus=$lisrow[tunnus]&uusi=1";
				}

				echo "<tr>
						<th>".t("Vaihtoveneen tiedot")."</th>
						<td align='right'><a href='../yllapito.php?toim=sarjanumeron_lisatiedot!!!RAHHAK!!!true$ylisa&lopetus=$PHP_SELF////toim=$toim//lopetus=$lopetus//tee=$tee//tilausnumero=$tilausnumero//from=LASKUTATILAUS'>".t("Vaihtoveneen lisätiedot")."</a></td><td></td></tr>";
			}

			echo "<tr>
					<th>".t("Rekisteröinti- ja vakuutustiedot")."</th>
					<td align='right'><a href='$PHP_SELF?toim=$toim&tee=vakuutushakemus&tilausnumero=$tilausnumero&from=LASKUTATILAUS&lopetus=$PHP_SELF////toim=$toim//lopetus=$lopetus//tee=$tee//tilausnumero=$tilausnumero//from=LASKUTATILAUS'>".t("Rekisteröinti- ja Vakuutustiedot")."</a></td><td></td></tr>";

			$query = "	SELECT *
						FROM maksupositio
						WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$laskurow[jaksotettu]'
						order by summa desc";
			$result = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($result) > 0) {
				echo "<tr>
						<th>".t("A. Valitse rahoitettava osuus")."</th>";

				echo "<td><select name='rahlaskelma_rahoitettava_positio' onchange='submit();'>";

				while ($positiorow = mysql_fetch_array($result)) {

					if ($rahlaskelma_rahoitettava_positio == $positiorow["tunnus"]) {
						$sel = "SELECTED";
					}
					else {
						$sel = "";
					}

					echo "<option value='$positiorow[tunnus]' $sel>$positiorow[kuvaus] $positiorow[summa] $laskurow[valkoodi]</option>";
				}

				echo "</select></td><td></td></tr>";
			}

			// Hetaana mutamia oletuksia avainsanoista
			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji	='MYSQLALIAS'
						and selite	like 'osamaksusoppari.%'";
			$al_res = mysql_query($query) or pupe_error($query);

			$avain_oletukset = array();

			while ($al_row = mysql_fetch_array($al_res)) {
				$avain_oletukset[$al_row["selite"]] = $al_row["selitetark"];
			}
			
			$sel = array();
			$sel[$laskurow["rekisteilmo_omistaja"]] = "selected";

			echo "<tr>
					<th>".t("Rahoitusyhtiö")."</th>
					<td align='right'>
					<select name='rekisteilmo_omistaja'>
					<option value=''>".t("Valitse")."</option>
					<option value='OSTAJA' $sel[OSTAJA]>".t("Ostaja")."</option>
					<option value='NORDEA' $sel[NORDEA]>".t("Nordea Rahoitus")."</option>
					<option value='GE'     $sel[GE]>".t("GE Money")."</option>
					<option value='POHJOLA'$sel[POHJOLA]>".t("Pohjola Pankki")."</option>
					<option value='SAMPO'  $sel[SAMPO]>".t("Sampo Rahoitus")."</option>
					<option value='SANTAN' $sel[SANTAN]>".t("Santander")."</option>
					<option value='HANDEL' $sel[HANDEL]>".t("Handelsbanken Rahoitus")."</option>";
			echo "	</select>
					</td><td></td></tr>";

			echo "<tr>
					<th>".t("1. Käteishinta kaupan kohteesta vakiovarustein")."</th>
					<td align='right'>".sprintf('%.2f',$uusivene)."</td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr>
					<th>".t("2. Lisävarusteet yhteensä")."</th>
					<td align='right'>".sprintf('%.2f',$uusitarvike)."</td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr>
					<th>".t("3. Toimituskulut")."</th>
					<td align='right'>".sprintf('%.2f',$toimkulu)."</td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr>
					<th>".t("4. Käteishinta kaupan kohteesta (1+2+3)")."</th>
					<td align='right'>".sprintf('%.2f',$kateishinta_kaupan_kohteesta)."</td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr>
					<th>".t("5. Hyvityshinta vaihtokohteesta")."</th>
					<td align='right'>".sprintf('%.2f',$hyvitys_vaihtoveneesta)."</td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr>
					<th valign='top'>".t("6. Jäännösvelka vaihtokohteesta")."</th>
					<td align='right' valign='top'><input type='text' name='jaannosvelka_vaihtokohteesta' size='15' style='text-align:right' value='$jaannosvelka_vaihtokohteesta'></td><td valign='top'>$laskurow[valkoodi]</td></tr>";

			if ($nrahdemo == 1 and isset($old_debt) and checkdate($old_debtkk, $old_debtpp, $old_debtvv)) {
				$nrahtee = "old_debt";

				require("nordea_rahoitus_ulos.inc");

				$nrahtee = "";
			}

			if($nrahdemo == 1) {
				if(!isset($old_debtkk)) $old_debtkk = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				if(!isset($old_debtvv)) $old_debtvv = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				if(!isset($old_debtpp)) $old_debtpp = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));

				echo "<tr>
					<th>".t("Hae jäännösvelka vaihtokohteesta")."</th>
					<td align='right'>
					<input type='text' name='old_debtpp' size='3' style='text-align:right' value='$old_debtpp'>
					<input type='text' name='old_debtkk' size='3' style='text-align:right' value='$old_debtkk'>
					<input type='text' name='old_debtvv' size='5' style='text-align:right' value='$old_debtvv'></td><td><input type='submit' name='old_debt' value='".t("Hae")."'></td>";
				echo "</tr>";
			}

			echo "<tr>
					<th>".t("7. Hyvitysosuus vaihtokohteesta (5-6)")."</th>
					<td align='right'>".sprintf('%.2f',$hyvitysosuus_vaihtokohteesta)."</td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr>
					<th>".t("8. Etumaksu tilattaessa")."</th>
					<td align='right'>".sprintf('%.2f',$ennakkomaksu)."</td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr>
					<th>".t("9. Muut maksut ennen toimitusta")."</th>
					<td align='right'>".sprintf('%.2f',$muut_maksut_ennen_toimitusta)."</td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr>
					<th>".t("10. Käteismaksuosuus yhteensä (7+8+9)")."</th>
					<td align='right'>".sprintf('%.2f',$kateismaksuosuus_yhteensa)."</td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr>
					<th>".t("11. Velkaosuus käteishinnasta (4-10)")."</th>
					<td align='right'>".sprintf('%.2f',$velkaosuus_kateishinnasta)."</td><td>$laskurow[valkoodi]</td></tr>";

			if ($maksuerienlkm == 0 and $perustamiskustannus == 0) {
				$perustamiskustannus = $avain_oletukset["osamaksusoppari.perustamiskustannus"];
			}

			echo "<tr>
					<th>".t("12. Luottosuhteen perustamiskustannus")."</th>
					<td align='right'><input type='text' name='perustamiskustannus' size='15' style='text-align:right' value='$perustamiskustannus'></td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr>
					<th>".t("13. Muut luottokustannukset")."</th>
					<td align='right'><input type='text' name='muutluottokustannukset' size='15' style='text-align:right' value='$muutluottokustannukset'></td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr>
					<th>".t("14. Rahoitettava osuus yhteensä (11+12+13)")."</th>
					<td align='right'>".sprintf('%.2f',$rahoitettavaa_yhteensa)."</td><td>$laskurow[valkoodi]</td></tr>";


			echo "<tr><td class='back'><br></td></tr>";

			$sel1 = "";
			$sel2 = "";


			if ($lyhennystapa == "1") {
				$sel1 = "SELECTED";
			}
			elseif($lyhennystapa == "2") {
				$sel2 = "SELECTED";
			}

			echo "<tr>
				<th>".t("Lyhennystapa")."</th>
				<td align='right'>
				<select name='lyhennystapa'>
				<option value='2' $sel2>".t("Annuiteetti")."</option>
				<option value='1' $sel1>".t("Tasalyhenteinen")."</option>
				</select>
				</td><td></td></tr>";

			$sel1 = "";
			$sel2 = "";
			$sel3 = "";
			$sel4 = "";
			$sel5 = "";
			$sel6 = "";
			$sel7 = "";

			if ($viitekorko == "1") {
				$sel1 = "SELECTED";
			}
			elseif($viitekorko == "2") {
				$sel2 = "SELECTED";
			}
			elseif($viitekorko == "3") {
				$sel3 = "SELECTED";
			}
			elseif($viitekorko == "4") {
				$sel4 = "SELECTED";
			}
			elseif($viitekorko == "5") {
				$sel5 = "SELECTED";
			}
			elseif($viitekorko == "6") {
				$sel6 = "SELECTED";
			}
			elseif($viitekorko == "7") {
				$sel7 = "SELECTED";
			}

			/*
			1 = Kiinteä
			2 = Nordea Prime
			3 = 1 kk Euribor
			4 = 3 kk Euribor
			5 = 6 kk Euribor
			6 = 1 kk Stibor
			7 = 3 kk Stibor
			*/

			if ($laskurow["rahlaskelma_nfref"] == 0) {
				echo "<tr>
						<th>".t("Viitekorko")."</th>
						<td align='right'>
						<select name='viitekorko'>
						<option value='1' $sel1>".t("Kiinteä")."</option>
						<option value='2' $sel2>".t("Nordea Prime")."</option>
						<option value='3' $sel3>".t("1 kk Euribor")."</option>
						<option value='4' $sel4>".t("3 kk Euribor")."</option>
						<option value='5' $sel5>".t("6 kk Euribor")."</option>
						<option value='6' $sel5>".t("1 kk Stibor")."</option>
						<option value='7' $sel5>".t("3 kk Stibor")."</option>
						</select>
						</td><td></td></tr>";
			}
			else {
				echo "<tr>
						<th>".t("Viitekorko")."</th>
						<input type='hidden' name='viitekorko' value='1'>
						<td align='right'>".t("Kiinteä")."</td><td></td></tr>";
			}

			/*
			echo "<tr>
				<th>".t("Marginaali")."</th>
				<td align='right'><input type='text' name='marginaalikorko' size='15' style='text-align:right' value='$marginaalikorko'></td><td>%</td></tr>";
			*/

			if ($maksuerienlkm == 0 and $sopajankorko == 0) {
				$sopajankorko = $avain_oletukset["osamaksusoppari.sopajankorko"];
			}

			echo "<tr>
					<th>".t("Sopimusajan korko")."</th>
					<td align='right'><input type='text' name='sopajankorko' size='15' style='text-align:right' value='$sopajankorko'></td><td>%</td></tr>";

			echo "	<script language='javascript'>
					function kklkm() {
						document.mainform.luottoaikakk.value=document.mainform.maksuerienlkm.value;
					}
				</script> ";



			echo "<tr>
					<th>".t("Osamaksuerien lukumäärä")."</th>
					<td align='right'><input type='text' name='maksuerienlkm' size='15' style='text-align:right' value='$maksuerienlkm' onKeyUp='kklkm();'></td><td>".t("kpl")."</td></tr>";

			echo "<tr>
				<th>".t("Luottoaika kuukautta")."</th>
				<td align='right'><input type='text' name='luottoaikakk' size='15' style='text-align:right' value='$luottoaikakk'></td><td>".t("kk")."</td></tr>";

			echo "<tr>
				<th>".t("Ensimmäisen erän eräpäivämäärä")."</th>
				<td align='right'>
				<input type='text' name='ppa' size='3' style='text-align:right' value='$ppa'>
				<input type='text' name='kka' size='3' style='text-align:right' value='$kka'>
				<input type='text' name='vva' size='5' style='text-align:right' value='$vva'></td><td></td></tr>";


			echo "<tr>
				<th>".t("Viimeisen erän eräpäivämäärä")."</th>
				<td align='right'>$vikaerpcm</td><td></td></tr>";

			echo "<tr>
				<th>".t("Viimeisen erän suuruus")."</th>
				<td align='right'><input type='text' name='poikkeava_era' size='15' style='text-align:right' value='$poikkeava_era'></td><td>$laskurow[valkoodi]</td></tr>";

			if ($maksuerienlkm == 0 and $erankasittelymaksu == 0) {
				$erankasittelymaksu = $avain_oletukset["osamaksusoppari.erankasittelymaksu"];
			}

			echo "<tr>
				<th>".t("Käsittelymaksu per erä")."</th>
				<td align='right'><input type='text' name='erankasittelymaksu' size='15' style='text-align:right' value='$erankasittelymaksu'></td><td>$laskurow[valkoodi]</td></tr>";

			echo "<tr><td class='back'><br></td></tr>";


			if ($lyhennystapa == 1) {

				$ind = 0;
				$ekaera  = $maksut_tl[$ind]['summa'];

				$ind = count($maksut_tl)-1;
				$vikaera = $maksut_tl[$ind]['summa'];


				echo "<tr>
						<th>".t("Ensimmäinen erä")."</th>
						<td align='right'>$ekaera</td><td>$laskurow[valkoodi]</td></tr>";

				echo "<tr>
						<th>".t("Viimeinen erä")."</th>
						<td align='right'>$vikaera</td><td>$laskurow[valkoodi]</td></tr>";

				echo "<tr>
						<th>".t("15. Korko sopimusajalta eräpäiviä noudatettaessa")."</th>
						<td align='right'>$koron_osuus_tl</td><td>$laskurow[valkoodi]</td></tr>";

			}
			else {
				$ind = count($maksut_te)-1;
				$vikaera = $maksut_te[$ind]['summa'];

				echo "<tr>
						<th>".t("Annuiteettierä")."</th>
						<td align='right'>$A</td><td>$laskurow[valkoodi]</td></tr>";

				echo "<tr>
						<th>".t("Viimeinen erä")."</th>
						<td align='right'>$vikaera</td><td>$laskurow[valkoodi]</td></tr>";

				echo "<tr>
						<th>".t("15. Korko sopimusajalta eräpäiviä noudatettaessa")."</th>
						<td align='right'>$koron_osuus_te</td><td>$laskurow[valkoodi]</td></tr>";
			}

			echo "<tr><td class='back'><br></td></tr>";

			echo "<tr>
					<th>".t("16. Tilinavausmaksu")."</th>
					<td align='right'><input type='text' name='tilinavausmaksu' size='15' style='text-align:right' value='$tilinavausmaksu'></td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr>
					<th>".t("17. Käsittelymaksu yhteensä")."</th>
					<td align='right'>".sprintf('%.2f', $kasittelymaksut_yhteensa)."</td><td>$laskurow[valkoodi]</td></tr>";


			if ($lyhennystapa == 1) {
				echo "<tr>
						<th>".t("18. Luottokustannus yhteensä")."</th>
						<td align='right'>".sprintf('%.2f', $luottokustannus_yhteensa_tl)."</td><td>$laskurow[valkoodi]</td></tr>";


				echo "<tr>
						<th>".t("19. KSL 9.2 § mukainen luottohinta")."</th>
						<td align='right'>".sprintf('%.2f', $ksl_luottohinta_tl)."</td><td>$laskurow[valkoodi]</td></tr>";
			}
			else {
				echo "<tr>
						<th>".t("18. Luottokustannus yhteensä")."</th>
						<td align='right'>".sprintf('%.2f', $luottokustannus_yhteensa_te)."</td><td>$laskurow[valkoodi]</td></tr>";


				echo "<tr>
						<th>".t("19. KSL 9.2 § mukainen luottohinta")."</th>
						<td align='right'>".sprintf('%.2f', $ksl_luottohinta_te)."</td><td>$laskurow[valkoodi]</td></tr>";
			}


			echo "<tr>
					<th>".t("20. KSL 9:11 §:n mukainen tod. vuosikorko")."</th>
					<td align='right'>".sprintf('%.2f', $ksl_vuosikorko)."</td><td>%</td></tr>";

			echo "<tr><th>".t("Tallenna").":</th><td><input type='submit' name='PAIVIT' value='".t("Tallenna/Päivitä")."'></td><td></td></tr>";
			echo "<tr><th>".t("Tallenna").":</th><td><input type='submit' name='VALMIS' value='".t("Tallenna ja siirry takaisin tilaukselle")."'></td><td></td></tr>";
			echo "</form>";

			echo "<tr><td class='back'><br></td></tr>";

			if ($nrahdemo == 1 and $tee == 'osamaksusoppari' and $tilausnumero > 0 and isset($omaformi_2)) {
				require("nordea_rahoitus_ulos.inc");
			}

			if ($nrahdemo == 1 and $rahoitettavaa_yhteensa > 0 and $viitekorko == 1 and $maksuerienlkm > 0 and $luottoaikakk > 0 and $laskurow["rahlaskelma_nfref"] == 0 and !isset($omaformi_2)) {
				echo "<tr><th></th><td colspan='2'>";
				echo "<form method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='lopetus' value='$lopetus'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='omaformi_2' value='1'>
						<input type='hidden' name='tee' value='osamaksusoppari'>";
				echo "<input type='submit' value='".t("Tee Nordea-Online hakemus")."'>";
				echo "</form>";
				echo "</td></tr>";
			}
			elseif ($nrahdemo == 1 and $rahoitettavaa_yhteensa > 0 and $laskurow["rahlaskelma_nfref"] > 0 and !isset($omaformi_2)) {
				require("nordea_rahoitus_ulos.inc");
			}

			echo "<tr><td class='back'><br></td></tr>";
			echo "<tr><th>".t("Näytä lomake").":</th>";

			echo "<form name='valmis' action='tulostakopio.php' method='post'>
					<input type='hidden' name='tee' value='NAYTATILAUS'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='otunnus' value='$tilausnumero'>";

			echo "<td>";
			echo "<select name='toim'>";
			echo "<option value='TARJOUS'>Tarjous</value>";
			echo "<option value='MYYNTISOPIMUS'>Myyntisopimus</value>";
			echo "<option value='OSAMAKSUSOPIMUS'>Osamaksusopimus</value>";
			echo "<option value='LUOVUTUSTODISTUS'>Luovutustodistus</value>";
			echo "<option value='VAKUUTUSHAKEMUS'>Vakuutushakemus</value>";
			echo "<option value='REKISTERIILMOITUS'>Rekisteröinti-ilmoitus</value>";
			echo "</select></td>";
			echo "<td><input type='submit' value='".t("Näytä")."'></td>";
			echo "</form></tr>";
			echo "</table>";

		}
	}
?>