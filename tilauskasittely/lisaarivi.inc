<?php
	//tarvitsemme
	// $ytunnus --> jossa on asiakkaan ytunnus
	// $rivinumero kent‰ss‰ on joko tilaajan rivinumero tai konserninsis‰isiss‰ kaupoissa sis‰inen toimittajanumero
	// kukarow[kesken] --> jossa on k‰ytt‰j‰ll‰ keskenoleva tilausnumero

	// $kpl 			--> jossa on tilattu kappalem‰‰r‰
	// $tuoteno 		--> jossa on tilattava tuotenumero
	// $toimaika		--> arvioitu toimitusaika
	// $kerayspvm		--> toivottu ker‰ysaika
	// $hinta 			--> k‰ytt‰j‰n syˆtt‰m‰ hinta
	// $netto 			--> k‰ytt‰j‰n syˆtt‰m‰ netto
	// $ale 			--> k‰ytt‰j‰n syˆtt‰m‰ ale
	// $var 			--> H,J,P varrit
	// $varasto 		--> myyd‰‰n vain t‰st‰ varastosta
	// $paikka 			--> myyd‰‰n vain t‰lt‰ paikalta
	// $rivitunnus		--> tietokannan tunnus jolle rivi lis‰t‰‰n
	// $korvaavakielto	--> Jos erisuuri kuin tyhj‰ niin ei myyd‰ korvaavia
	// $varataan_saldoa --> Jos == EI niin ei varata saldoa (tietyiss‰ keisseiss‰), tai siis ei ainakan tehd‰ saldotsekki‰
	// $kutsuja			--> Kuka t‰t‰ skripti‰ kutsuu
	// $myy_sarjatunnus --> Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
	// $jaksotettu		--> Kuuluuko tilausrivi mukaan jaksotukseen

	//palauttaa arrayn $lisatyt_rivit jossa on kaikkien rivien tunnukset jotka t‰‰ systeemi lis‰si

	//muutetaan pilkkuja pisteiksi
	$kpl 	= str_replace(',','.',$kpl);
	$hinta 	= str_replace(',','.',$hinta);
	$ale 	= str_replace(',','.',$ale);

	$alkupera_trow		= $trow; // alkuper‰inen tuoterow talteen..

	$saldoyhteensa		= 0; // tahan summaillaan saldoa yhteens‰
	$lapsiyhteensa		= 0;
	$suoratoimeikiitos = '';

	$lop				= "";

	$tuoteperhe			= array(); // Tuoteperheiden lapset menev‰t t‰nne

	$kaikkituotteet		= array();  // t‰h‰n arrayseen lis‰t‰‰n kaikki tuotteet ketjusta
	$kaikkituotteet[]	= strtoupper($tuoteno); // ekaksi lis‰t‰‰n ketjuun kysytty tuote
	$var				= strtoupper($var); // varri isoks
	$netto				= strtoupper($netto); // netto isoks

	$toimittamatta		= $kpl;	//N‰in paljon ollaan toimittamassa

	// n‰ihin arraysiin tulee sitten myyt‰viss‰ olevat tuotteet, kappaleet ja varastopaikat
	$myy_tuoteno   = array();
	$myy_kpl       = array();
	$myy_hyllyalue = array();
	$myy_hyllynro  = array();
	$myy_hyllytaso = array();
	$myy_hyllyvali = array();
	$myy_varasto   = array();
	$myy_kpl_myyty = array();
	$myy_hintakerr = array();
	$myy_alekerroi = array();

	$lisatyt_rivit = array();

	// debuggia varten, laita 1 jos haluut messuja
	$lisaarivi_debug = 0;

	if ($laskurow['tila'] == 'O') {
		$rivityyppi = 'O';	// Ostotilausrivi
	}
	elseif ($laskurow["tilaustyyppi"] == 'E') {
		$rivityyppi = 'E'; // Ennakkotilausrivi
	}
	elseif ($laskurow["tila"] == 'T') {
		$rivityyppi = 'T'; // Tarjousrivi
	}
	elseif($laskurow["tila"] == 'G') {
		$rivityyppi = 'G';	// Siirtolistatilausrivi
	}
	elseif($laskurow["tila"] == 'V') {
		$rivityyppi = 'V';	// Valmistustilausrivi
	}
	elseif($laskurow["tila"] == 'Z') {
		$rivityyppi = 'Z';	// Terminaalitoimitus
	}
	elseif($laskurow["tila"] == 'B') {
		$rivityyppi = 'B';	// Ostoskori
	}
	else {
		$rivityyppi = 'L'; // Myyntitilausrivi
	}


	// Jos kyseess‰ on rivi joka halutaan tilata ihan normaalisti joltain toimittajalta
	if (substr($paikka,0,3) == "###") {
		$var 		 = "T";
		$rivinumero  = substr($paikka, 3);
		$paikka		 = "#";
	}
	elseif ($var == "T" and substr($paikka,0,3) != "###" and substr($paikka,0,3) != "!!!") {
		$var 		= "";
		$rivinumero	= "";
	}

	if (substr($paikka,0,3) == "!!!") {
		$var 		 = "U";
		$rivinumero  = substr($paikka, 3);
		$paikka		 = "#";
	}
	elseif ($var == "U" and substr($paikka,0,3) != "!!!") {
		$var 		= "";
		$rivinumero	= "";
	}

	// Jos kyseess‰ on suoratoimitus joka tilataan toisesta firmasta suoraan
	if (substr($paikka,0,3) == "@@@") {

		if (strpos($paikka, "&&&") !== FALSE) {
			$superpaikka = substr($paikka, 0, strpos($paikka, "&&&"));
			$loppupaikka = substr($paikka, strpos($paikka, "&&&")+3);

			$lop = "LOP#".$toimittamatta;
			$toimittamatta = $loppupaikka;
		}
		else {
			$superpaikka = $paikka;
		}

		$var 		 = "S";
		$paikka 	 = substr($superpaikka, strpos($superpaikka,'#')+1);
		$rivinumero  = substr($superpaikka, 3, strpos($superpaikka,'#')-3);
	}
	elseif ($var == "S" and substr($paikka,0,3) != "@@@") {
		$var 		= "";
		$rivinumero	= "";
		$suoratoimeikiitos = "ei";
	}

	//echo "$paikka<br>";
	//echo "$var<br>";
	//echo "$rivinumero<br>";

	$perhelisa  = "";
	$perhelisa2 = "";

	//Haetaan myˆs tuoteperheet tai reseptit, t‰ss‰ parametrit niille
	if ($laskurow['tila'] == 'V') {
		$perhelisa = " and tuoteperhe.tyyppi='R' ";
	}
	else {
		$perhelisa = " and tuoteperhe.tyyppi in ('P','') ";
	}

	if ($laskurow['vienti'] != '') {
		$perhelisa2 = " and tuote.vienti != 'K' ";
	}


	//Tutkitaan onko t‰m‰ hyvitysrivi
	if ((float) $kpl < 0 and trim($var) == "") {
		//Laitetaan varriksi Y jotta ei tarvitse menn‰ myyntihaaraan
		$var = "Y";
	}

	if ($laskurow['tila'] != 'O' and trim($var) == "") {

		// Etsit‰‰n korvaavia tuotteita
		if ($trow["ei_saldoa"] == "" and $korvaavakielto == "") {
			// Tutkitaan ensin saako t‰lle asiakkaalle myyd‰ korvaavia t‰st‰ tuoteryhm‰st‰
			$query = "  select yhtio
						from korvaavat_kiellot
						where yhtio = '$kukarow[yhtio]'
						and ytunnus in ('$ytunnus','*')
						and osasto  in ('$trow[osasto]','*')
						and try     in ('$trow[try]','*')
						and laji    = 'K'";
			$kkres  = mysql_query($query) or die($query);

			// Jos result on nolla, niin saadaan myyd‰ korvaavia
			if (mysql_num_rows($kkres) == 0 and $rivityyppi == 'L') {

				// Tehd‰‰n homma siten, ett‰ etsit‰‰n kaikki korvaavat jo t‰ss‰ vaiheessa arrayseen
				$query = "select * from korvaavat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
				$kres1	= mysql_query($query) or die($query);

				while ($krow1 = mysql_fetch_array($kres1)) {
					// K‰yd‰‰n l‰pi kaikki korvaavat...
					$query = "select * from korvaavat where id='$krow1[id]' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
					$kres2  = mysql_query($query) or die($query);

					while ($krow2 = mysql_fetch_array($kres2)) {
						if (!in_array(strtoupper($krow2["tuoteno"]), $kaikkituotteet)) $kaikkituotteet[]=strtoupper($krow2["tuoteno"]); // lis‰t‰‰n tuoteno arrayseen
					}

				} // end k‰yd‰‰n l‰pi korvaavat
			} // end saako myyd‰ korvaavia
		} // end etsit‰‰nkˆ korvaavia

		// Haetaan tuoteperheet, jos $paikka on erisuuri kuin tyhj‰ niin vaihdetaan vain jonkun tuotteen paikkaa eik‰ p‰ivitet‰ tuoteperheit‰
		// tai jos muokataan tuoteperheen lasta ei menn‰ uudestaan t‰h‰n haaraan
		if ($paikka == "" and $perheid == "") {

			foreach($kaikkituotteet as $isatuoteno) {

				$query = "	SELECT tuoteperhe.*
							FROM tuoteperhe, tuote
							WHERE tuoteperhe.yhtio = tuote.yhtio
							and tuoteperhe.tuoteno = tuote.tuoteno
							and isatuoteno = '$isatuoteno'
							and tuoteperhe.yhtio = '$kukarow[yhtio]'
							$perhelisa
							$perhelisa2
							ORDER by tuoteno";
				$perheresult = mysql_query($query) or pupe_error($query);

				while ($perherow = mysql_fetch_array($perheresult)) {
					//T‰t‰ tuoteperhett‰ halutaan myyd‰
					if (!in_array(strtoupper($perherow["tuoteno"]), $tuoteperhe)) $tuoteperhe[]=strtoupper($perherow["tuoteno"]); // lis‰t‰‰n tuoteno arrayseen
					if (!in_array(strtoupper($perherow["tuoteno"]), $kaikkituotteet)) $kaikkituotteet[]=strtoupper($perherow["tuoteno"]); // lis‰t‰‰n tuoteno arrayseen
				}
			}
		}

		//Kaikkien arrayden indeksien ‰iti
		$i = 0;

		// Sitten k‰yd‰‰n kaikkien tuotteiden saldot l‰pi kaikilta varastopaikoilta
		foreach ($kaikkituotteet as $tuoteno) {

			// katotaan jos meill‰ on tuotteita varaamassa saldoa joiden varastopaikkaa ei en‰‰ ole olemassa...
			$orvot = saldo_myytavissa($tuoteno, "ORVOT") * -1;

			// lasketaan saldo tuotepaikoille (ORDER BY TƒRKEE!)
			$query = "	SELECT tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, tuotepaikat.tuoteno, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, sum(tuotepaikat.saldo) saldo
						FROM tuote
						LEFT JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
						LEFT JOIN varastopaikat ON tuotepaikat.yhtio = varastopaikat.yhtio
						and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(tuotepaikat.hyllynro ,5,'0')) >= concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(alkuhyllynro  ,5,'0'))
						and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(tuotepaikat.hyllynro ,5,'0')) <= concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(loppuhyllynro ,5,'0'))
						WHERE tuote.yhtio='$kukarow[yhtio]'
						and tuote.tuoteno='$tuoteno'
						GROUP BY 1,2,3,4,5,6,7,8
						ORDER BY tuotepaikat.oletus DESC, tuotepaikat.saldo DESC";
			$zres = mysql_query($query) or die($query);

			while ($zrow = mysql_fetch_array($zres)) {

				if ($zrow["ei_saldoa"] == "") {
					// Saldolliset tuotteet
					$query = "	SELECT sum(varattu) varattu
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$tuoteno'
								and varattu > 0
								and tyyppi in ('L','G','V')
								and hyllyalue = '$zrow[hyllyalue]' and hyllynro = '$zrow[hyllynro]' and hyllyvali = '$zrow[hyllyvali]' and hyllytaso = '$zrow[hyllytaso]'";
					$vres = mysql_query($query) or die($query);
					$vrow = mysql_fetch_array($vres);

					$saldo = $zrow["saldo"] - $vrow["varattu"]; // saldo = saldo - ennakkopoistot

					// hoidetaan pois problematiikka jos meill‰ on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
					if ($orvot > 0) {
						if ($saldo >= $orvot) {
							// poistaan orpojen varaamat tuotteet t‰lt‰ paikalta
							$saldo = $saldo - $orvot;
							$orvot = 0;
						}
						elseif ($orvot > $saldo) {
							// poistetaan niin paljon orpojen saldoa ku voidaan
							$orvot = $orvot - $saldo;
							$saldo = 0;
						}
					}

				}
				else {
					// Saldottomat tuotteet
					$saldo = $toimittamatta;
					$zrow["hyllyalue"]	= "";
					$zrow["hyllynro"]	= "";
					$zrow["hyllyvali"]	= "";
					$zrow["hyllytaso"] 	= "";
					$zrow["varasto"]	= "";
				}

				if ($varataan_saldoa == "EI" or ($saldo > 0 and ($zrow["varastotyyppi"] == '' or $zrow["varastotyyppi"] == 'V' or $laskurow["varasto"] == $zrow["varasto"]))) {
					// lis‰t‰‰n tuotteen tiedot arrayseen, koska t‰m‰n tuotteen voi myyd‰
					$myy_tuoteno[$i]   = strtoupper($tuoteno);
					$myy_kpl[$i]       = $saldo;
					$myy_hyllyalue[$i] = $zrow["hyllyalue"];
					$myy_hyllynro[$i]  = $zrow["hyllynro"];
					$myy_hyllyvali[$i] = $zrow["hyllyvali"];
					$myy_hyllytaso[$i] = $zrow["hyllytaso"];
					$myy_varasto[$i]   = $zrow["varasto"];

					if (!in_array(strtoupper($tuoteno),$tuoteperhe)) {
						$myy_tyyppi[$i] = "NORMAALI";
						$saldoyhteensa += $saldo; // summaillaan kaikkien isien saldoa yhteens‰.
					}
					else {
						$myy_tyyppi[$i] = "LAPSITUOTE";
						$lapsiyhteensa += $saldo; // summaillaan kaikkien lapsien saldoa yhteens‰.
					}

					//Kasvatetaan indeksi‰
					$i++;
				}
			} // end while tuotepaikat
		} // end foreach myyt‰v‰t tuotteet

		//tutkitaan ensin saako t‰lle asiakkaalle myyd‰ osatoimituksen t‰st‰ tuoteryhm‰st‰
		$query = "  select yhtio
					from korvaavat_kiellot
					where yhtio = '$kukarow[yhtio]'
					and ytunnus in ('$ytunnus','*')
					and osasto  in ('$trow[osasto]','*')
					and try     in ('$trow[try]','*')
					and laji    = 'O'";
		$kkres  = mysql_query($query) or die($query);

		// jos result on nolla, saadaan myyd‰ osatoimitus tai jos meill‰ on saldoa tarpeeksi
		if (mysql_num_rows($kkres) == 0 or $saldoyhteensa >= $toimittamatta) {

			// nyt meill‰ on $myy_ arrayperheess‰ kaikki tuotteet ja varastopaikat jolta voidaan myyd‰ jotain..
			// loopataan kaikki tuotteet l‰pi

			//Myyd‰‰n tietylt‰ paikalta
			if ($paikka != '') {
				$paikat = split('#',$paikka);

				foreach ($myy_tuoteno as $i => $tuoteno) {
					if ($myy_kpl[$i] >= $toimittamatta and $paikat[0] == $myy_hyllyalue[$i] and $paikat[1]  == $myy_hyllynro[$i] and $paikat[2] == $myy_hyllyvali[$i] and $paikat[3] == $myy_hyllytaso[$i]) {
						//Otetaan t‰m‰
						$myy_tuoteno   = array();
						$myy_kpl       = array();
						$myy_hyllyalue = array();
						$myy_hyllynro  = array();
						$myy_hyllytaso = array();
						$myy_hyllyvali = array();
						$myy_varasto   = array();
						$myy_kpl_myyty = array();

						$myy_tuoteno[]   = strtoupper($tuoteno);
						$myy_kpl[]       = $toimittamatta;
						$myy_hyllyalue[] = $paikat[0];
						$myy_hyllynro[]  = $paikat[1];
						$myy_hyllyvali[] = $paikat[2];
						$myy_hyllytaso[] = $paikat[3];

						break;
					}
				}
			}

			// Myyd‰‰n tietyst‰ varastosta
			if ((int) $varasto != 0 and $paikka == '') {
				foreach ($myy_tuoteno as $i => $tuoteno) {
					if ($myy_varasto[$i] != $varasto and $myy_varasto[$i] != "") {
						if ($myy_tyyppi[$i] == "NORMAALI") {
							$saldoyhteensa -= $myy_kpl[$i];
						}
						else {
							$lapsiyhteensa -= $myy_kpl[$i];
						}

						unset($myy_tuoteno[$i]);
						unset($myy_kpl[$i]);
						unset($myy_hyllyalue[$i]);
						unset($myy_hyllynro[$i]);
						unset($myy_hyllyvali[$i]);
						unset($myy_hyllytaso[$i]);
						unset($myy_varasto[$i]);
						unset($myy_tyyppi[$i]);
					}
				}
			}

			foreach ($myy_tuoteno as $i => $tuoteno) {
				//T‰ss‰ loopataan vain is‰/normaleja tuotteita
				if ($myy_tyyppi[$i] == "NORMAALI") {
					// jos ei olla viel‰ toimitettu kaikkea, niin lis‰t‰‰n tilausrivi
					if ($toimittamatta > 0) {
						if ($myy_kpl[$i] >= $toimittamatta or $varataan_saldoa == "EI") {
							$myy_kpl[$i] = $toimittamatta; // jos t‰ll‰ paikalla on saldoa enemm‰n kun tarvitaan, otetaan sitten vaan tarvittava m‰‰r‰
						}

						$myy_kpl_myyty[$i] += $myy_kpl[$i];

						// status on lis‰t‰‰n is‰ ja perhe jos virheit‰ ei tule
						$perheok 	= 0;

						//Is‰ halutaan nyt ainakin myyd‰
						$myynama 	= array();
						$myynama[] 	= $i;

						if (count($tuoteperhe) > 0) {
							//riitt‰‰kˆ lasten saldot
							$query = "	SELECT tuoteperhe.*
										FROM tuoteperhe, tuote
										WHERE tuoteperhe.yhtio = tuote.yhtio
										and tuoteperhe.tuoteno = tuote.tuoteno
										and isatuoteno = '$tuoteno'
										and tuoteperhe.yhtio = '$kukarow[yhtio]'
										$perhelisa
										$perhelisa2
										ORDER by tuoteno";
							$perheresult = mysql_query($query) or pupe_error($query);

							while($perherow = mysql_fetch_array($perheresult)) {

								$lapsitoimittamatta = $perherow["kerroin"]*$myy_kpl[$i];

								foreach ($myy_tuoteno as $j => $lapsituoteno) {

									if(strtoupper($lapsituoteno) == strtoupper($perherow["tuoteno"]) and $myy_tyyppi[$j] == "LAPSITUOTE") {

										// laitetaan lasten kertoimet kuntoon
										$myy_hintakerr[$j] = $perherow["hintakerroin"];
										$myy_alekerroi[$j] = $perherow["alekerroin"];

										// jos ei olla viel‰ toimitettu kaikkea, niin lis‰t‰‰n tilausrivi
										if ($lapsitoimittamatta > 0 and (($myy_kpl[$j] - $myy_kpl_myyty[$j]) > 0 or $varataan_saldoa == "EI")) {

											if (($myy_kpl[$j] - $myy_kpl_myyty[$j] >= $lapsitoimittamatta) or $varataan_saldoa == "EI") {
												// jos t‰ll‰ paikalla on saldoa enemm‰n kun tarvitaan, otetaan sitten vaan tarvittava m‰‰r‰
												// ja splitataan loput omaan paikaaan arrayhin jotta voidaan sit myyd‰ ne seuraavalla kiekalla jos on tarvis
												$myy_tuoteno[]	 = $myy_tuoteno[$j];
												$myy_kpl[]		 = $myy_kpl[$j] - $lapsitoimittamatta;
												$myy_hyllyalue[] = $myy_hyllyalue[$j];
												$myy_hyllynro[]	 = $myy_hyllynro[$j];
												$myy_hyllyvali[] = $myy_hyllyvali[$j];
												$myy_hyllytaso[] = $myy_hyllytaso[$j];
												$myy_varasto[]	 = $myy_varasto[$j];
												$myy_tyyppi[]	 = $myy_tyyppi[$j];

												$myy_kpl[$j] = $lapsitoimittamatta;
											}

											$myy_kpl_myyty[$j] += $myy_kpl[$j];

											$myynama[] = $j;

											// hoidellaan saldojen summailua kuntoon
											$lapsitoimittamatta -= $myy_kpl[$j];
										}
									}
								}

								if ($lapsitoimittamatta > 0) {
									//t‰m‰n lapsen saldo ei riit‰
									$perheok++;
								}
							}
						}

						//Jos homma on ok niin lis‰t‰‰n rivi
						if ($perheok == 0) {

							foreach($myynama as $indeksi) {
								//K‰sinsyˆtetyt hinnat ja alennukset eiv‰t p‰de korvaaviin tuotteisiin, eik‰ tuoteperheiden lapsiin
								if (strtoupper($myy_tuoteno[$indeksi]) != strtoupper($alkupera_trow["tuoteno"])) {
									$hinta	= "";
									$ale	= "";
									$netto	= "";
									$alv 	= "";
								}

								// Haetaan k‰sitelt‰v‰n tuotteen tiedot
								$query = "	SELECT *
											FROM tuote
											WHERE yhtio='$kukarow[yhtio]' and tuoteno='$myy_tuoteno[$indeksi]'";
								$tres = mysql_query($query) or die($query);
								$trow = mysql_fetch_array($tres);

								$saasto_hinta = $hinta;
								$saasto_ale   = $ale;
								$saasto_netto = $netto;
								$saasto_alv   = $alv;

								if ($kukarow["extranet"] != '') {
									require ("alehinta.inc");
									require ("alv.inc");
								}
								else {
									require ("../inc/alehinta.inc");
									require ("alv.inc");
								}

								// jos ollaan laittamassa muuta tuotetta kun mit‰ tilattiin, lis‰t‰‰n rivikommentti
								if (strtoupper($myy_tuoteno[$indeksi]) != strtoupper($alkupera_trow["tuoteno"]) and $myy_tyyppi[$indeksi] == "NORMAALI") {
									$kommentti .= " ".t("Korvaa tuotteen")." $alkupera_trow[tuoteno]";
								}

								if ($kutsuja == "EDITILAUS" or $lisaarivi_debug == 1) {
									$edi_ulos .= " --> ".t("Myyd‰‰n")." $myy_tuoteno[$indeksi] ".t("paikalta")." $myy_hyllyalue[$indeksi]-$myy_hyllynro[$indeksi]-$myy_hyllyvali[$indeksi]-$myy_hyllytaso[$indeksi]: $myy_kpl[$indeksi] ".t("kpl")." ".t("Ale")." $ale% ".t("Hinta")." $hinta $aperuste ".t("ALV")." $alv% $rivikomm\n";
									if ($lisaarivi_debug == 1) echo "$edi_ulos<br>";
								}

								// Tilatut ja varatut kappaleet
								$tilattu = $myy_kpl[$indeksi];
								$varattu = $myy_kpl[$indeksi];

								// Jos hinta ja alekertoimet on setattu
								if (isset($myy_hintakerr[$indeksi])) {
									$hinta = round($hinta * $myy_hintakerr[$indeksi],2);
								}

								if (isset($myy_alekerroi[$indeksi])) {
									if ((int) $myy_alekerroi[$indeksi] == -1) {
										$ale = 100.00;
									}
									else {
										$ale = round($ale * $myy_alekerroi[$indeksi],2);
									}
								}

								//Jos ei varata saldoa niin ei varata saldoa
								if($varataan_saldoa == "EI" and $rivityyppi == 'G') {
									$varattu = 0;
								}

								$query = "	INSERT into tilausrivi set
											hyllyalue   = '$myy_hyllyalue[$indeksi]',
											hyllynro    = '$myy_hyllynro[$indeksi]',
											hyllytaso   = '$myy_hyllytaso[$indeksi]',
											hyllyvali   = '$myy_hyllyvali[$indeksi]',
											tilaajanrivinro = '$rivinumero',
											laatija 	= '$kukarow[kuka]',
											laadittu 	= now(),
											yhtio 		= '$kukarow[yhtio]',
											tuoteno 	= '$myy_tuoteno[$indeksi]',
											varattu 	= '$varattu',
											yksikko 	= '$trow[yksikko]',
											kpl 		= '0',
											tilkpl 		= '$tilattu',
											ale 		= '$ale',
											alv 		= '$alv',
											netto		= '$netto',
											hinta 		= '$hinta',
											kerayspvm 	= '$kerayspvm',
											otunnus 	= '$kukarow[kesken]',
											tyyppi 		= '$rivityyppi',
											toimaika 	= '$toimaika',
											kommentti 	= '$kommentti',
											var 		= '',
											try			= '$trow[try]',
											osasto		= '$trow[osasto]',
											perheid		= '$perheid',
											tunnus 		= '$rivitunnus',
											nimitys 	= '$trow[nimitys]',
											jaksotettu	= '$jaksotettu'";

								if ($trow['ei_saldoa']!='') {
									$query .= ", kerattyaika = now()";
								}

								$result = mysql_query($query) or die($query);
								$lisatty_tun = mysql_insert_id();

								$lisatyt_rivit[] = $lisatty_tun;

								if ($myy_tyyppi[$indeksi] == "NORMAALI") {
									// lapsille oikea perheid
									$perheid_lisaarivi = $lisatty_tun;

									// hoidellaan saldojen summailua kuntoon
									$toimittamatta -= $myy_kpl[$indeksi];
								}

								if ($myy_tyyppi[$indeksi] == "NORMAALI" and $myy_sarjatunnus > 0) {

									if ($varattu > 0) {
										$tunken = "myyntirivitunnus";
									}
									else {
										$tunken = "ostorivitunnus";
									}

									$query = "	UPDATE sarjanumeroseuranta
												SET $tunken 		 = '$lisatty_tun'
												WHERE yhtio			 = '$kukarow[yhtio]'
												and tunnus			 = '$myy_sarjatunnus'
												and tuoteno			 = '$myy_tuoteno[$indeksi]'
												and $tunken 		 = 0";
									$sres = mysql_query($query) or pupe_error($query);

									$myy_sarjatunnus = 0;

									if(mysql_affected_rows() == 1) {
										//echo "Sarjanumero linkattu tilausriviin!<br>";
									}
								}

								if ($myy_tyyppi[$indeksi] == "LAPSITUOTE") {
									//P‰ivitet‰‰n is‰n perhetunnus
									if ($rivityyppi == 'V') {
										//jos on valmistusprosessi kyseess‰ niin is‰n tyyppi vaihdetaan W:ksi koska sen ei pid‰ varata saldoa!
										$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi', tyyppi='W' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi";
										$result = mysql_query($query) or pupe_error($query);

										$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus=$lisatty_tun";
										$result = mysql_query($query) or pupe_error($query);
									}
									else {
										//Hoidetaan saldottomat is‰t samaan varastoon kuin lapsetkin jotta varastosplittaukset eiv‰t menisi hassusti
										$query = "UPDATE tilausrivi SET hyllyalue='$myy_hyllyalue[$indeksi]' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi and hyllyalue=''";
										$result = mysql_query($query) or pupe_error($query);

										$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus in ('$perheid_lisaarivi','$lisatty_tun')";
										$result = mysql_query($query) or pupe_error($query);
									}
								}

								$rivitunnus	= "";
								$kommentti 	= "";

								$hinta = $saasto_hinta;
								$ale   = $saasto_ale;
								$netto = $saasto_netto;
								$alv   = $saasto_alv;

							} // T‰ll‰ rundilla myydyt
						} // Jos tuoteperhett‰ lˆytyi riitt‰v‰sti tai myytiin pelk‰‰ is‰‰
					} // Jos vil‰ on toimittamatta kappaleita
				} // end if toimittamatta > 0
			} // end foreach myy_tuoteno

			// Tyhjennet‰‰n mahdolliset hinnat , ennen puutehaaraa
// kommentoidaan t‰‰ niin arkistoidaan puutehinta joka olisi oikeesti menny
//			$hinta	= "";
//			$ale	= "";
//			$netto	= "";
//			$alv 	= "";

		} // end saadaanko myyd‰ osatoimitus
	}



	// jos tultiin ulos loopista, ett‰ meill‰ on toimittamattomia rivej‰, tehd‰‰n niist‰ puuterivi
	// tai jos tullaan niin, ett‰ var-kent‰ss‰ on J tai P tai jotain
	if ($toimittamatta > 0 or trim($var) != '' or $laskurow['tila'] == 'O') {

		//Tyhjennet‰‰n apumuuttuja
		if ($var == "Y")	{
			$var = "";
		}

		$puu_tuoteno 	= array();
		$puu_kpl 		= array();
		$puu_hintake	= array();
		$puu_alekerr	= array();
		$puu_var		= array();


		//Tehd‰‰n suoratoimitusautomaatio jos kyseess‰ on extranet tai ediasiakas
		if (($yhtiorow["suoratoim_automaatio"] != '' or $kukarow["extranet"] != '' or $kutsuja == "EDITILAUS") and $var == "" and $toimittamatta > 0 and $suoratoimeikiitos == "") {
			$suora_tuoteno 	= $alkupera_trow["tuoteno"];
			$suora_kpl		= $toimittamatta;
			require("suoratoimitusvalinta.inc");

			if (sizeof($suoratoim_array) > 0) {
				if (strpos($suoratoim_array[0], "&&&") !== FALSE) {
					$superpaikka = substr($suoratoim_array[0], 0, strpos($suoratoim_array[0], "&&&"));
					$loppupaikka = substr($suoratoim_array[0], strpos($suoratoim_array[0], "&&&")+3);

					$lop = "LOP#".$toimittamatta;
					$toimittamatta = $loppupaikka;
				}
				else {
					$superpaikka = $suoratoim_array[0];
				}

				$var 		 = "S";
				$paikka 	 = substr($superpaikka, strpos($superpaikka,'#')+1);
				$rivinumero  = substr($superpaikka, 3, strpos($superpaikka,'#')-3);
			}
		}


		if (substr($var,0,4) == "LOJ#" or substr($var,0,4) == "LOP#" or substr($lop,0,4) == "LOP#") {
			//Lis‰t‰‰n syˆtetyt ja laitetaan loput JT:seen tai puutteiksi
			$puu_tuoteno[]	= $alkupera_trow["tuoteno"];
			$puu_kpl[] 		= $toimittamatta;
			$puu_hintake[]	= "X";
			$puu_alekerr[]	= "X";
			$puu_var[]		= $var;

			if (substr($var,0,4) == "LOJ#") {
				$puu_tuoteno[]	= $alkupera_trow["tuoteno"];
				$puu_kpl[] 		= substr($var,4) - $toimittamatta;
				$puu_hintake[]	= "X";
				$puu_alekerr[]	= "X";
				$puu_var[]		= "";
			}
			elseif(substr($lop,0,4) == "LOP#") {
				$puu_tuoteno[]	= $alkupera_trow["tuoteno"];
				$puu_kpl[] 		= substr($lop,4) - $toimittamatta;
				$puu_hintake[]	= "X";
				$puu_alekerr[]	= "X";
				$puu_var[]		= "P";
			}
		}
		else {
			$puu_tuoteno[]	= $alkupera_trow["tuoteno"];
			$puu_kpl[]	 	= $toimittamatta;
			$puu_hintake[]	= "X";
			$puu_alekerr[]	= "X";
			$puu_var[]		= $var;
		}

		if ($laskurow['tila'] != 'O') {

			$query = "	SELECT tuoteperhe.*
						FROM tuoteperhe, tuote
						WHERE tuoteperhe.yhtio = tuote.yhtio
						and tuoteperhe.tuoteno = tuote.tuoteno
						and isatuoteno = '$alkupera_trow[tuoteno]'
						and tuoteperhe.yhtio = '$kukarow[yhtio]'
						$perhelisa
						$perhelisa2
						ORDER by tuoteno";
			$perheresult = mysql_query($query) or pupe_error($query);

			while ($perherow = mysql_fetch_array($perheresult)) {
				$puu_tuoteno[]	= $perherow["tuoteno"];
				$puu_kpl[] 		= $toimittamatta*$perherow["kerroin"];
				$puu_hintake[]	= $perherow["hintakerroin"];
				$puu_alekerr[]	= $perherow["alekerroin"];
				$puu_var[]		= $var;
			}
		}

		foreach($puu_tuoteno as $i => $tuoteno) {
			$kpl = $puu_kpl[$i];
			$var = $puu_var[$i];

			$query = "	SELECT *
						FROM tuote
						WHERE tuoteno = '$tuoteno' and yhtio='$kukarow[yhtio]'";
			$tuoteresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tuoteresult) == 0) {
				// tuotetta ei lˆydy, lis‰t‰‰n silti tuoteno kantaan?
				$trow["tuoteno"] = $kaikkituotteet[0];
			}
			else {
				$trow = mysql_fetch_array($tuoteresult);
			}

			//K‰sinsyˆteyt hinnat nollataan kun lis‰t‰‰n tuoteperheiden lapsia
			//Ostossa splittaukset menee aina samalla hinnalla
			if ($i > 0 and $laskurow['tila'] != 'O') {
				$hinta		= "";
				$ale		= "";
				$netto		= "";
				$alv 		= "";
				$rivinumero = "";
			}

			if ($laskurow['tila'] == 'O' and ($hinta == "" or $hinta == 0)) {
				$query = "	SELECT ostohinta
							FROM tuotteen_toimittajat
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$trow[tuoteno]'
							and liitostunnus = '$laskurow[liitostunnus]'
							LIMIT 1";
				$rarres = mysql_query($query) or pupe_error($query);
				$rarrow	 = mysql_fetch_array($rarres);

				$hinta = $rarrow["ostohinta"];
			}

			if ($laskurow['tila'] != 'O') {
				if ($kukarow["extranet"] != '') {
					require ("alehinta.inc");
				}
				else {
					require ("../inc/alehinta.inc");
				}
			}

			require ("alv.inc");

			if ($laskurow['tila'] != 'O') {
				switch ($var) {
					case 'J' : // J‰lkitoimitukseen
						$varattu 	= 0;
						$jt 		= $kpl;
						break;
					case 'P' : // Puute
						$varattu 	= 0;
						$jt 		= 0;
						break;
					case 'H' : // Pakkohyv‰ksynt‰
						$varattu 	= $kpl;
						$jt 		= 0;
						break;
					case 'O' : // Ookooaus-hyv‰ksynt‰
						$varattu 	= $kpl;
						$jt 		= 0;
						break;
					case 'V' : // Tehdaslis‰varuste
						$varattu 	= $kpl;
						$jt 		= 0;
						break;
					case 'T' : // Tilataan toimittajalta
						$varattu 	= 0;
						$jt 		= $kpl;
						break;
					case 'U' : // Tilataan toimittajalta asiakkaalle
						$varattu 	= 0;
						$jt 		= $kpl;
						break;
					case 'S' : // Suoratoimitus-JT
						$varattu 	= 0;
						$jt 		= $kpl;
						break;
					case '' :
						$kpl 		= $toimittamatta;
						$varattu 	= 0;
						$jt 		= 0;

						//Sirtolistoissa defaultti on JT jos saldo ei riit‰.
						// Tai jos yhtiˆlle on konffattu, ett‰ se haluaa ylimemen‰t jteiksi
						if ($rivityyppi == 'G' or ($yhtiorow["puute_jt_oletus"] == "J" and $kpl > 0)) {
							$jt 	= $kpl;
							$var	= "J";
						}
						// Terminaalitoimitukset tai ostoskorit; var tyhj‰‰ ja varattu normisti
						elseif ($rivityyppi == 'Z' or $rivityyppi == 'B') {
							$var 	= "";
							$varattu = $kpl;
						}
						// Hyvitysrivit menee normaaleina
						elseif($kpl < 0) {
							$var 	= "";
							$varattu = $kpl;
						}
						// Normaalisti kaikki menee puuteeksi.
						else {
							$var 	= "P";
						}
						break;
					default :
						echo t("VIRHE: Virheellinen arvo kent‰ss‰ hyvaksy")." '$var'";
				}
				if (trim($var) == 'P' and $kutsuja == "EDITILAUS") {
					$kommentti .= t("Tuote Loppu.");
				}
			}
			else {
				$var 		= "";
				$varattu 	= $kpl;
			}

			$p_hyllyalue	= "";
			$p_hyllynro		= "";
			$p_hyllyvali	= "";
			$p_hyllytaso	= "";

			if($paikka != '') {
				//K‰ytt‰j‰n syˆtt‰m‰ paikka
				$paikat = split('#',$paikka);

				$p_hyllyalue	= $paikat[0];
				$p_hyllynro		= $paikat[1];
				$p_hyllyvali	= $paikat[2];
				$p_hyllytaso	= $paikat[3];
			}
			else {
				// haetaan viel‰ tuotteen oletuspaikka puuteriville, jotta seuranta ois helpompaa
				//jos laskurow[varasto] on setattu niin yritet‰‰n lˆyt‰‰ paikka siit‰ varastosta
				$query  = "	SELECT tuotepaikat.*, varastopaikat.tunnus varastotunnus
							FROM tuotepaikat
							LEFT JOIN varastopaikat ON tuotepaikat.yhtio = varastopaikat.yhtio
							and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(tuotepaikat.hyllynro ,5,'0')) >= concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(alkuhyllynro  ,5,'0'))
							and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(tuotepaikat.hyllynro ,5,'0')) <= concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(loppuhyllynro ,5,'0'))
							where tuotepaikat.yhtio = '$kukarow[yhtio]'
							and tuotepaikat.tuoteno = '$trow[tuoteno]'
							ORDER BY tuotepaikat.oletus DESC";
				$result = mysql_query($query) or die($query);

				$loydetytpaikat = 0;

				if (mysql_num_rows($result) != 0) {

					//Yritet‰‰n lˆyt‰‰ paikka laskurow[varasto]:sta
					while($olerow = mysql_fetch_array($result)) {
						if ($olerow["varastotunnus"] == $laskurow["varasto"]) {
							$p_hyllyalue	= $olerow["hyllyalue"];
							$p_hyllynro		= $olerow["hyllynro"];
							$p_hyllyvali	= $olerow["hyllyvali"];
							$p_hyllytaso	= $olerow["hyllytaso"];

							$loydetytpaikat++;
							break;
						}
					}
					//Jos ei lˆytynyt niin myyd‰‰n oletuspaikalta
					if ($loydetytpaikat == 0) {

						mysql_data_seek($result,0);
						$olerow = mysql_fetch_array($result);

						$p_hyllyalue	= $olerow["hyllyalue"];
						$p_hyllynro		= $olerow["hyllynro"];
						$p_hyllyvali	= $olerow["hyllyvali"];
						$p_hyllytaso	= $olerow["hyllytaso"];
					}
				}
			}

			if ($kutsuja == "EDITILAUS" or $lisaarivi_debug == 1) {
				if ($var == "J") {
					$edi_ulos .= " --> ".t("JT")." $trow[tuoteno] ".t("paikalta")." $p_hyllyalue-$p_hyllynro-$p_hyllyvali-$p_hyllytaso: $kpl ".t("kpl")." ".t("Ale")." $ale ".t("Hinta")." $hinta $aperuste $rivikomm\n";
				}
				elseif($var == "P") {
					$edi_ulos .= " --> ".t("PUUTE")." $trow[tuoteno] ".t("paikalta")." $p_hyllyalue-$p_hyllynro-$p_hyllyvali-$p_hyllytaso: $kpl ".t("kpl")." ".t("Ale")." $ale ".t("Hinta")." $hinta $aperuste $rivikomm\n";
				}
				elseif($var == "S") {
					$edi_ulos .= " --> ".t("SUORATOIMITUS")." $trow[tuoteno] ".t("toimittajalta")." $rivinumero: $kpl ".t("kpl")." ".t("Ale")." $ale ".t("Hinta")." $hinta $aperuste $rivikomm\n";
				}


				if ($lisaarivi_debug == 1) echo "$edi_ulos<br>";
			}


			// Jos hinta ja alekertoimet on setattu
			if ($puu_hintake[$i] != "X") {
				$hinta = round($hinta * $puu_hintake[$i],2);
			}

			if ($puu_alekerr[$i] != "X") {
				if ((int) $puu_alekerr[$i] == -1) {
					$ale = 100.00;
				}
				else {
					$ale = round($ale * $puu_alekerr[$i],2);
				}
			}


			$trow["tuoteno"] = strtoupper($trow["tuoteno"]);

			// lis‰t‰‰n puuterivi
			$query = "	INSERT into tilausrivi set
						hyllyalue   = '$p_hyllyalue',
						hyllynro    = '$p_hyllynro',
						hyllyvali   = '$p_hyllyvali',
						hyllytaso   = '$p_hyllytaso',
						tilaajanrivinro = '$rivinumero',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now(),
						yhtio 		= '$kukarow[yhtio]',
						tuoteno 	= '$trow[tuoteno]',
						varattu 	= '$varattu',
						yksikko 	= '$trow[yksikko]',
						kpl 		= '0',
						tilkpl 		= '$kpl',
						jt			= '$jt',
						ale 		= '$ale',
						alv 		= '$alv',
						netto		= '$netto',
						hinta 		= '$hinta',
						kerayspvm 	= '$kerayspvm',
						otunnus 	= '$kukarow[kesken]',
						tyyppi 		= '$rivityyppi',
						toimaika 	= '$toimaika',
						kommentti 	= '$kommentti',
						var 		= '$var',
						try			= '$trow[try]',
						osasto		= '$trow[osasto]',
						perheid		= '$perheid',
						tunnus 		= '$rivitunnus',
						nimitys 	= '$trow[nimitys]',
						jaksotettu	= '$jaksotettu'";

			if ($trow['ei_saldoa']!='') {
				$query .= ", kerattyaika = now()";
			}
			$result = mysql_query($query) or die($query);
			$lisatty_tun = mysql_insert_id();

			$lisatyt_rivit[] = $lisatty_tun;

			if ($i == 0) {
				// lapsille oikea perheid
				$perheid_lisaarivi = $lisatty_tun;

				if ($varattu+$jt != 0 and $myy_sarjatunnus > 0 and $trow["tuoteno"] == $alkupera_trow["tuoteno"]) {

					if ($varattu+$jt > 0) {
						$tunken = "myyntirivitunnus";
					}
					else {
						$tunken = "ostorivitunnus";
					}

					$query = "	SELECT *
								FROM sarjanumeroseuranta
								WHERE tunnus	= '$myy_sarjatunnus'
								and tuoteno		= '$trow[tuoteno]'";
					$sres = mysql_query($query) or pupe_error($query);
					$srow = mysql_fetch_array($sres);

					if ($var == "S") {
						// jos syˆtet‰‰n sarjanumertollinen tuote suoratoimitukseen niin liitet‰‰n t‰m‰ myyntirivi siihen kiinni
						$query = "	INSERT INTO sarjanumeroseuranta
									SET
									yhtio				= '$kukarow[yhtio]',
									tuoteno				= '$trow[tuoteno]',
									lisatieto			= 'Suoratoimitetaan yhtiˆst‰: $srow[yhtio]. $srow[lisatieto]',
									sarjanumero			= '$srow[sarjanumero]',
									myyntirivitunnus 	= '$lisatty_tun'";
					}
					else {
						$query = "	UPDATE sarjanumeroseuranta
									SET $tunken 		 = '$lisatty_tun'
									WHERE yhtio			 = '$kukarow[yhtio]'
									and tunnus			 = '$myy_sarjatunnus'
									and tuoteno			 = '$trow[tuoteno]'
									and $tunken 		 = 0";
					}
					$sres = mysql_query($query) or pupe_error($query);
					$myy_sarjatunnus = 0;
				}
			}

			if (count($puu_tuoteno) > 1 and $laskurow['tila'] != 'O' and $lop == "") {
				//P‰ivitet‰‰n is‰n perhetunnus
				if ($rivityyppi == 'V') {
					//jos on valmistusprosessi kyseess‰ niin is‰n tyyppi vaihdetaan W:ksi koska sen ei pid‰ varata saldoa!
					$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi', tyyppi='W' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi";
					$result = mysql_query($query) or pupe_error($query);

					$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus=$lisatty_tun";
					$result = mysql_query($query) or pupe_error($query);
				}
				else {
					//Hoidetaan saldottomat is‰t samaan varastoon kuin lapsetkin jotta varastosplittaukset eiv‰t menisi hassusti
					$query = "UPDATE tilausrivi SET hyllyalue='$p_hyllyalue' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi and hyllyalue=''";
					$result = mysql_query($query) or pupe_error($query);

					$query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus in ('$perheid_lisaarivi','$lisatty_tun')";
					$result = mysql_query($query) or pupe_error($query);
				}
			}
			$rivitunnus	= "";
			$kommentti 	= "";
		}
	}
?>