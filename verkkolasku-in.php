<?php
	if (!isset($kutsuja)) require ("inc/connect.inc");
	require ("inc/functions.inc");
	unset($yhtiorow);
	$komm='';
	$toim='';
	$laskuvirhe='';

	// $yhtio:ssa on nyt laskun VASTAANOTTAJAN ovt-tunnus ja $verkkotunnus_vas:ssa on VASTAANOTTAJAN verkkolaskutunnus
	$verkkotunnus_vas = trim($verkkotunnus_vas);

	// elma lähetätä joskus verkkotunnusta todella kummallisessa muodossa.. kokeillaan arvailla oikein
	$pos = strpos($verkkotunnus_vas,"@");
	if ($pos !== FALSE) {
		$verkkotunnus_vas = substr($verkkotunnus_vas, 0, $pos); // otetaan info ekaan ättämerkkiin asti
	}

	if ($yhtio != 0) {
		// etsitään vastaanottavaa yritystä ytunnuksella..
		$query = "SELECT * FROM yhtio WHERE ovttunnus = '$yhtio'";
		$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

		if (mysql_num_rows($result) == 1) {
			$yhtiorow = mysql_fetch_array($result);
		}
	}

	if (!isset($yhtiorow) and $yhtio != 0) {
		// etsitään vastaanottavaa yritystä ytunnuksella..
		$yyhtio = substr($yhtio,4,8) * 1; // Poistetaan mahdolliset nollat
		$query = "SELECT * FROM yhtio WHERE ytunnus = '$yyhtio'";
		$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

		if (mysql_num_rows($result) == 1) {
			$yhtiorow = mysql_fetch_array($result);
		}
	}

	if (!isset($yhtiorow) and $verkkotunnus_vas != "") {
		// kokeillaan löytää yritys sitten verkkotunnuksella..
		$query = "SELECT * FROM yhtion_parametrit WHERE verkkotunnus_vas = '$verkkotunnus_vas'";
		$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

		if (mysql_num_rows($result) == 1) {
			$paramrow = mysql_fetch_array($result);
			// löydettiin parametreistä oikea yhtiö haetaan yhtiorow
			$query = "select * from yhtio where yhtio = '$paramrow[yhtio]'";
			$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

			if (mysql_num_rows($result) == 1) {
				$yhtiorow = mysql_fetch_array($result);
			}
		}
	}

	if (!isset($yhtiorow) and $yhtio != 0) {
		// viimeinen hätäkeino, katotaan onko joku lisätieto ongelma
		$query = "SELECT * FROM yhtio WHERE ovttunnus like '$yhtio%'";
		$result = mysql_query($query) or die ("$query<br><br>".mysql_error());

		if (mysql_num_rows($result) == 1) {
			$yhtiorow = mysql_fetch_array($result);
		}
	}

	if (!isset($yhtiorow)) {
		// ei löydetty VASTAANOTTAVAA yhtiötä, lähetetään meili kaikille tän serverin admineille!
		$query = "select group_concat(distinct admin_email) admin_email from yhtion_parametrit where admin_email != ''";
		$result = mysql_query($query) or die ("$query<br><br>".mysql_error());
		$yhtiorow = mysql_fetch_array($result);

		$laskuvirhe .= "Laskun vastaanottajaa ei löytynyt ovt-tunnuksella '$yhtio' eikä verkkotunnuksella '$verkkotunnus_vas'!\n";
		$toim = 'E';
	}
	else {
		// haetaan yhtiön parametrit
		$query = "	SELECT *
					FROM yhtion_parametrit
					WHERE yhtio='$yhtiorow[yhtio]'";
		$result = mysql_query($query)
				or die ("Kysely ei onnistu yhtio $query");

		if (mysql_num_rows($result) == 1) {
			$yhtion_parametritrow = mysql_fetch_array($result);
			// lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
			foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
				$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
			}
		}
	}

	if ($toim != 'E') {
		$yhtio=$yhtiorow['yhtio'];
		// $yhtiossa on nyt yhtion tunnus (eli esim Arwidsonin arwi)

		// Perusta lasku
		$summa=$laskun_summa_eur;
		$valkoodi='EUR';
		if (($laskun_tyyppi=="381") and ($summa>0)) {
			$summa=$summa*-1;
		}

		$tpp = (int) substr($laskun_paiva,6,2);
		$tpk = (int) substr($laskun_paiva,4,2);
		$tpv = (int) substr($laskun_paiva,0,4);
		$erp = (int) substr($laskun_erapaiva,6,2);
		$erk = (int) substr($laskun_erapaiva,4,2);
		$erv = (int) substr($laskun_erapaiva,0,4);

		$viite=$laskun_pankkiviite;
		$ebid=$laskun_ebid;
		$selite="Lasno: ".$laskun_numero;

		//poistetaan ovt tunnuksesta ekat neljä ja otetaan kaheksan seuraavaa ja numeroks
		$ytunnus = (int) substr($laskuttajan_ovt,4,8);
		$ok = 0;

		$query  = "SELECT * FROM toimi WHERE ovttunnus='$laskuttajan_ovt' and yhtio='$yhtio'";
		$result = mysql_query($query) or die ("$query<br><br>".mysql_error());
		if (mysql_num_rows($result) == 1) $ok = 1;

		if ($ok == 0) {
			// Yritetään laventaa ytunnuksella
			$query  = "SELECT * FROM toimi WHERE ytunnus='$ytunnus' and yhtio='$yhtio'";
			$result = mysql_query($query) or die ("$query<br><br>".mysql_error());
			if (mysql_num_rows($result) == 1) $ok = 1;
		}

		if ($ok == 0) {
			// Yritetään tarkentaa nimellä
			$query = "SELECT * FROM toimi WHERE ytunnus='$ytunnus' and yhtio='$yhtio' and nimi='$laskuttajan_nimi'";
			$result = mysql_query($query) or die ("$query<br><br>".mysql_error());
			if (mysql_num_rows($result) == 1) $ok = 1;
		}

		if ($ok == 0) {
			// kokeillaan pelkällä nimellä
			$query = "SELECT * FROM toimi WHERE yhtio='$yhtio' and nimi='$laskuttajan_nimi'";
			$result = mysql_query($query) or die ("$query<br><br>".mysql_error());
			if (mysql_num_rows($result) == 1) $ok = 1;
		}

		if ($ok == 0) {
			$laskuvirhe .= "Emme löytäneet toimittajaa ytunnus: '$ytunnus' nimi: '$laskuttajan_nimi' yhtiöltä: '$yhtio'";
			$toim = 'E';
		}

		if ($toim != 'E') {

			$trow = mysql_fetch_array($result);

			// katotaan pitääks vaihtaa oletus_vientiä
			if ($trow['oletus_vienti'] != 'C' and $trow['oletus_vienti'] != 'F' and $trow['oletus_vienti'] != 'I') {

				// katotaan löydettäskö tälle vähän ostorivejä
				$chkriviok = 0;

				for ($i=0; $i<count($tuoteno); $i++) {

					$ostotunnus = (int) $rrivino[$i];

					// ei tehdä turhaa hakua jos meillä ei ole tullu rivitunnusta
					if ($ostotunnus != 0) {
						$chkquery = "SELECT *
									FROM tilausrivi
									where yhtio = '$yhtio' and
									tunnus = '$ostotunnus' and
									tyyppi = 'O'";
						$checkostot = mysql_query($chkquery) or die ("$chkquery<br><br>".mysql_error());

						if (mysql_num_rows($checkostot) == 1) {
							$chkriviok++;
						}
					}
				}

				// löydettiin mätchi ostoriveiltä, laitetaan vaihto-omaisuus täppi päälle
				if ($chkriviok != 0 ) {
					if (strtoupper($trow["maakoodi"]) == strtoupper($yhtiorow["maakoodi"])) {
						// kyseessä kotimaa
						$trow['oletus_vienti'] = 'C';
					}
					else {
						$chkquery = "select * from maat where koodi = '$trow[maakoodi]' and eu != ''";
						$checkostot = mysql_query($chkquery) or die ("$chkquery<br><br>".mysql_error());

						if (mysql_num_rows($checkostot) != 0) {
							// kyseessä EU
							$trow['oletus_vienti'] = 'F';
						}
						else {
							// kyseessä EI-EU
							$trow['oletus_vienti'] = 'I';
						}
					}
				}
			}

			$ytunnus = $trow["ytunnus"];
			$verkapunimi = $trow["nimi"];

			$hyvak[1] = $trow['oletus_hyvak1'];
			$hyvak[2] = $trow['oletus_hyvak2'];
			$hyvak[3] = $trow['oletus_hyvak3'];
			$hyvak[4] = $trow['oletus_hyvak4'];
			$hyvak[5] = $trow['oletus_hyvak5'];

			$oletustili=$trow['tilino'];
			$oletuskust=$trow['kustannuspaikka'];
			$oletuskohde=$trow['kohde'];
			$oletusprojekti=$trow['projekti'];

			$selite= $trow['nimi'] . " " . $trow['nimitark'] . " Lasno: " . $laskun_numero;

			//Onko tälle asiakastunnukselle erityinen kustannuspaikka?
			$query = "SELECT *
							FROM tiliointisaanto
							WHERE ttunnus = '$trow[tunnus]' and yhtio = '$yhtio' and tilino = 0 and kuvaus = '$laskun_asiakastunnus'";
			$result = mysql_query($query) or die ("$query<br><br>".mysql_error());
			if (mysql_num_rows($result) != 0) {
				$tiliointirow=mysql_fetch_array ($result);
				$oletuskust = $tiliointirow['kustp'];
			}

			// errorcheckit...

			$val = checkdate($tpk, $tpp, $tpv);
			if (!$val) {
				// Laitetaan sitten ajopäivä
				list($tpv,$tpk,$tpp) = split("-",strftime("%Y-%m-%d", mktime(0,0,0,date("m"),date("d"),date("Y"))));
				$komm = "(verkkolas@" . date('Y-m-d') .") Tiedoista puuttui päiväys. Tarkista asia laskulta!<br>" . $komm;
			}

			// oletus alv nolla paitsi jos toimittajalla on oletuksena jotain kotimaahan liittyvää ni haetaan oletus avainsanoista...
			$oletusalvi = 0;

			if ($trow['oletus_vienti'] == "A" or $trow['oletus_vienti'] == "B" or $trow['oletus_vienti'] == "C" or $trow['oletus_vienti'] == "J") {

				$query = "SELECT selite
							FROM avainsana
							WHERE yhtio = '$yhtio' and laji = 'alv' and selitetark != ''
							ORDER BY jarjestys,selite";
				$avainresult = mysql_query($query) or die ("$query".mysql_error());

				if (mysql_num_rows($avainresult) != 1) {
					$laskuvirhe .= "Yrityksen $yhtio oletus ALV% puuttuu tai niitä on monta!\n";
					$toim = 'E';
				}
				else {
					$avainrow = mysql_fetch_array($avainresult);
					$oletusalvi = $avainrow['selite'];
				}
			}

			// jos eräpäivää ei tule laskulta, otetaan toimittajan oletus
			if ($erp == 0 and $erk == 0 and $erv == 0) {
				$err = $trow["oletus_erapvm"];
				// jos oletustakaan ei ole, laitetaan lasku erääntymään huomenna...
				if ($err == 0) {
					list($erv,$erk,$erp) = split("-",strftime("%Y-%m-%d", mktime(0,0,0,date("m"),date("d")+1,date("Y"))));
				}
				$komm = "(verkkolas@" . date('Y-m-d') .") Tiedoista puuttui eräpvm. Tarkista asia laskulta!<br>" . $komm;
			}

			if ($err > 0) {
				$newer = strftime("%Y-%m-%d", mktime(0,0,0,$tpk,$tpp+$err,$tpv));
				$erp = (int) substr($newer, 8, 2);
				$erk = (int) substr($newer, 5, 2);
				$erv = (int) substr($newer, 0, 4);
				$err = 0;
			}

			$kar   = $trow['oletus_kapvm'];
			$kapro = $trow['oletus_kapro'];

			$kap = 0;
			$kak = 0;
			$kav = 0;
			if ($kar > 0) {
				$newer = strftime("%Y-%m-%d", mktime(0,0,0,$tpk,$tpp+$kar,$tpv));
				$kap = (int) substr($newer, 8, 2);
				$kak = (int) substr($newer, 5, 2);
				$kav = (int) substr($newer, 0, 4);
				$kar = 0;
			}

			$val = checkdate($erk, $erp, $erv);
			if (!$val) {
				$laskuvirhe .= "Virheellinen eräpvm $erv-$erk-$erp\n";
				$toim = 'E';
			}

			$kassaale = 0;
			if ($kapro != 0) {
				$kassaale = $summa * $kapro / 100;
				$kapro = 0;
			}
			$kassaale = round($kassaale,2);

			if ($kak > 0 and $kap > 0 and $kav > 0 and $kassaale > 0) {

				$val = checkdate($kak, $kap, $kav);
				if (!$val) {
					$laskuvirhe .= "Virheellinen kassaeräpvm '$kav-$kak-$kap' kassaale '$kassaale'\n";
					$toim = 'E';
				}
				else {
					if ($kassaale == 0) {
						$laskuvirhe .= "Kassapvm on, mutta kassa-ale puuttu\n";
						$toim = 'E';
					}
				}
			}

			$summa = round($summa,2);

			if ($summa == 0.0) {
				$laskuvirhe .= "Laskulta puuttuu summa\n";
				$toim = 'E';
			}

			if (strlen($viite) > 0) {
				require "inc/tarkistaviite.inc";
				if ($ok == 0) {
					$laskuvirhe .= "Viite on väärin\n";
					$toim = 'E';
				}
			}

			if ((strlen($viite) > 0) and (strlen($viesti) > 0)) {
				$laskuvirhe .= "Viitettä ja viestiä ei voi antaa yhtaikaa\n";
				$toim = 'E';
			}

			$query = "SELECT kurssi FROM valuu WHERE nimi = '$valkoodi' and yhtio = '$yhtio'";
			$result = mysql_query($query) or die ("$query<br><br>".mysql_error());
			if (mysql_num_rows($result) != 1) {
				$laskuvirhe .= "Valuuttaa $valkoodi ei löytynytkään!\n";
				$toim='E';
			}
			$vrow=mysql_fetch_array ($result);

			$hyvaksyja_nyt = '';
			$tila = "M";

			$hyvak[5] = trim($hyvak[5]);
			$hyvak[4] = trim($hyvak[4]);
			$hyvak[3] = trim($hyvak[3]);
			$hyvak[2] = trim($hyvak[2]);
			$hyvak[1] = trim($hyvak[1]);

			if (strlen($hyvak[5]) > 0) {
				$hyvaksyja_nyt=$hyvak[5];
				$tila = "H";
			}
			if (strlen($hyvak[4]) > 0) {
				$hyvaksyja_nyt=$hyvak[4];
				$tila = "H";
			}
			if (strlen($hyvak[3]) > 0) {
				$hyvaksyja_nyt=$hyvak[3];
				$tila = "H";
			}
			if (strlen($hyvak[2]) > 0) {
				$hyvaksyja_nyt=$hyvak[2];
				$tila = "H";
			}
			if (strlen($hyvak[1]) > 0) {
				$hyvaksyja_nyt=$hyvak[1];
				$tila = "H";
			}
			$olmapvm = $erv . "-" . $erk . "-" . $erp;
			if ($kap != 0) {
				$olmapvm = $kav . "-" . $kak . "-" . $kap;
			}

			if ($toim != 'E') {
				// Kirjoitetaan lasku
				$query = "INSERT into lasku set
							yhtio = '$yhtio',
							summa = '$summa',
							kasumma = '$kassaale',
							erpcm = '$erv-$erk-$erp',
							kapvm = '$kav-$kak-$kap',
							olmapvm = '$olmapvm',
							valkoodi = '$valkoodi',
							hyvak1 = '$hyvak[1]',
							hyvak2 = '$hyvak[2]',
							hyvak3 = '$hyvak[3]',
							hyvak4 = '$hyvak[4]',
							hyvak5 = '$hyvak[5]',
							hyvaksyja_nyt = '$hyvaksyja_nyt',
							ytunnus = '$ytunnus',
							tilinumero = '$trow[tilinumero]',
							nimi = '$trow[nimi]',
							nimitark = '$trow[nimitark]',
							osoite = '$trow[osoite]',
							osoitetark = '$trow[osoitetark]',
							postino = '$trow[postino]',
							postitp = '$trow[postitp]',
							maa =  '$trow[maa]',
							maakoodi =  '$trow[maakoodi]',
							viite = '$viite',
							viesti = '$viesti',
							sisviesti1 = '$sis1',
							sisviesti2 = '$sis2',
							tapvm = '$tpv-$tpk-$tpp',
							vienti = '$trow[oletus_vienti]',
							ebid = '$ebid',
							tila = '$tila',
							vienti_kurssi = '$vrow[kurssi]',
							laatija = 'verkkolas',
							liitostunnus = '$trow[tunnus]',
							luontiaika = now(),
							pankki1='$trow[pankki1]',
							pankki2='$trow[pankki2]',
							pankki3='$trow[pankki3]',
							pankki4='$trow[pankki4]',
							ultilno='$trow[ultilno]',
							swift='$trow[swift]',
							suoraveloitus='$trow[oletus_suoraveloitus]',
							hyvaksynnanmuutos='$trow[oletus_hyvaksynnanmuutos]',
							comments='$komm'";

				$result = mysql_query($query) or die ("$query<br><br>".mysql_error());
				$tunnus = mysql_insert_id ($link);
				$omasumma = round($summa * $vrow['kurssi'],2);

				// Tehdään oletustiliöinnit, ostovelat
				$vassumma = -1 * $omasumma;

				//Tutkitaan otsovelkatiliä
				if ($trow["konserniyhtio"] != '') {
					$ostovelat = $yhtiorow["konserniostovelat"];
				}
				else {
					$ostovelat = $yhtiorow["ostovelat"];
				}


				$query = "INSERT into tiliointi set
							yhtio = '$yhtio',
							ltunnus = '$tunnus',
							tilino = '$ostovelat',
							kustp = '',
							tapvm = '$tpv-$tpk-$tpp',
							summa = '$vassumma',
							selite = '$selite',
							vero = '0',
							lukko = '1',
							laatija = 'verkkolas',
							laadittu = now()";
				$result = mysql_query($query) or die ("$query\n\n".mysql_error());

				// Oletuskulutiliöinti, jos sellainen on. Tätä EI tehdä, jos toimittajalla on omia sääntöjä tai laskulla on useita alv-kantoja
				$query = "	SELECT *
							FROM tiliointisaanto
							WHERE ttunnus = '$trow[tunnus]' and yhtio = '$yhtio' and tilino != 0";
				$result = mysql_query($query) or die ("$query\n\n".mysql_error());

				// Onko laskuriveillä useita alv-verokantoja?
				if (sizeof($vat) > 0) {
					$ealvi=array_unique($vat);
				}
				else {
					//Katsotaan alv laskuerittelystä
					$ealvi[0] = $lisavat[0];
				}
				//echo "Alviarrayn koko on ". sizeof($ealvi). "\n";

				if ((mysql_num_rows($result) == 0) and (sizeof($ealvi) == 1)) { // Tehdän pelkät oletustiliöinnit

					if ($oletustili > 0) {
						$tili = $oletustili;
					}
					else {
						$tili = $yhtiorow['muutkulut'];
					}

					$vero=$oletusalvi;
					if ($ealvi[0] != '') $vero=$ealvi[0]; //Tuliko jotain verkkolaskulta
					else {
						if ($hardcoded_alv==1) { //salasana.php:stä
							$query = "SELECT * FROM tili WHERE tilino = '$tili' and yhtio = '$yhtio'";
							$tiliresult = mysql_query($query) or die ("$query\n\n".mysql_error());
							if (mysql_num_rows($tiliresult) == 1) {
								$tilirow = mysql_fetch_array ($tiliresult);
								if($tilirow['oletusalv'] == 99) $tilirow['oletusalv'] = 0;
								$vero = $tilirow['oletusalv'];
							}
						}
					}

					$kukarow['yhtio'] = $yhtio;
					$kukarow['kuka'] = 'verkkolas';
					$verkkolaskuveroton = round($omasumma / (1 + ($vero / 100)),2);
					$summa = $omasumma;
					$kustp = $oletuskust;
					$kohde = $oletuskohde;
					$projekti = $oletusprojekti;
					require "inc/teetiliointi.inc";
					// jos kyseessä on jonkin rahti/huolintakuluja, tiliöidään varastonarvoon
					if (($trow['oletus_vienti'] != 'A') and ($trow['oletus_vienti'] != 'D') and ($trow['oletus_vienti'] != 'G') and ($trow['oletus_vienti'] != '')) {

						$varastotili = $yhtiorow['varasto'];

						if (($trow['oletus_vienti']=='C') or ($trow['oletus_vienti']=='F') or ($trow['oletus_vienti']=='I')) {
								$varastotili = $yhtiorow['matkalla_olevat'];
						}

						$query = "INSERT into tiliointi set
									yhtio ='$kukarow[yhtio]',
									ltunnus = '$tunnus',
									tilino = '$varastotili',
									kustp= '',
									tapvm = '$tpv-$tpk-$tpp',
									summa = '$verkkolaskuveroton',
									selite = '$selite',
									vero = '',
									lukko = '',
									laatija = '$kukarow[kuka]',
									laadittu = now()";
						$result = mysql_query($query) or die ("$query\n\n".mysql_error());

						$query = "INSERT into tiliointi set
									yhtio ='$kukarow[yhtio]',
									ltunnus = '$tunnus',
									tilino = '$yhtiorow[varastonmuutos]',
									kustp = '$kustp',
									kohde = '$kohde',
									projekti = '$projekti',
									tapvm = '$tpv-$tpk-$tpp',
									summa = $verkkolaskuveroton*-1,
									selite = '$selite',
									vero = '',
									lukko = '',
									laatija = '$kukarow[kuka]',
									laadittu = now()";
						$result = mysql_query($query) or die ("$query\n\n".mysql_error());

						// Jos tämä lasku menee varastoon, tehdään valmiiksi keikka
						if ($trow['oletus_vienti'] == 'C' or $trow['oletus_vienti'] == 'F' or $trow['oletus_vienti'] == 'I') {
							require ("inc/verkkolasku-in-luo-keikkafile.inc");
						}
					}
				}
				else { // Tehdään rivikohtaiset tiliöinnit
					$i=0;
					$totsumma = 0;
					$verkkolaskuveroton = 0;
					$vtontot = 0;
					for ($i==0; $i<sizeof($tuoteno); $i++) {
						if ((float) $rsumma[$i] != 0) {
							$kustp = $oletuskust;
							$selite = utf8_decode(str_replace ("'", " ", $info[$i])); // Poistaa SQL-virheen mahdollisuuden
							$tuote = utf8_decode(str_replace ("'", " ", $tuoteno[$i])); // Poistaa SQL-virheen mahdollisuuden

							$query = "SELECT tilino, kustp FROM tiliointisaanto
										WHERE ttunnus = '$trow[tunnus]' and yhtio = '$yhtio' and
												mintuote <= '$tuote' and maxtuote >= '$tuote' and tilino != 0";
							$result = mysql_query($query) or die ("$query\n\n".mysql_error());

							if (mysql_num_rows($result) == 0) { // Sopiva sääntöä ei löytynyt

								$query = "SELECT tilino, kustp FROM tiliointisaanto
											WHERE ttunnus = '$trow[tunnus]' and yhtio = '$yhtio' and kuvaus LIKE '%". $selite ."%' and tilino != 0";

								$result = mysql_query($query) or die ("$query\n\n".mysql_error());

								if (mysql_num_rows($result) == 0) { // Hmm, mikään sääntö ei kelpaa

									if ($oletustili > 0) { // Toimittajan oletustili
										$tili = $oletustili;
									}
									else { // Yleinen kulutili
										$tili = $yhtiorow['muutkulut'];
									}
								}
								else {
									$tiliointirow=mysql_fetch_array ($result);
									$tili = $tiliointirow['tilino'];
									if ($tiliointirow['kustp'] != '') $kustp = $tiliointirow['kustp'];
								}
							}
							else {
								$tiliointirow=mysql_fetch_array ($result);
								$tili = $tiliointirow['tilino'];
								if ($tiliointirow['kustp'] != '') $kustp = $tiliointirow['kustp'];
							}

							$summa = (float) $rsumma[$i];

							if ($laskun_tyyppi=="381") { //Hyvityslasku
								$summa=$summa*-1;
							}

							//if (strlen($vat[$i]) == 0) $vat[$i]=$oletusalvi;
							$vero = (float) $vat[$i];

							$verkkolaskuveroton += $summa;
							$vtonsumma = $summa;
							$vtontot += $summa;
							$summa = round($summa * (1+($vero/100)),2);
							$totsumma += $summa;
							$selite= $selite . " " . $trow['nimi'] . " " . $trow['nimitark'] . " Lasno: " . $laskun_numero;
							$kukarow['yhtio'] = $yhtio;
							$kukarow['kuka'] = 'verkkolas';
							require "inc/teetiliointi.inc";
							// jos kyseessä on jonkin rahti/huolintakuluja, tiliöidään varastonmuutokseen
							if (($trow['oletus_vienti'] != 'A') and ($trow['oletus_vienti'] != 'D') and ($trow['oletus_vienti'] != 'G') and ($trow['oletus_vienti'] != '')) {
								$query = "INSERT into tiliointi set
											yhtio ='$kukarow[yhtio]',
											ltunnus = '$tunnus',
											tilino = '$yhtiorow[varastonmuutos]',
											kustp = '$kustp',
											kohde = '$kohde',
											projekti = '$projekti',
											tapvm = '$tpv-$tpk-$tpp',
											summa = $vtonsumma *-1,
											selite = '$selite',
											vero = '',
											lukko = '',
											laatija = '$kukarow[kuka]',
											laadittu = now()";
								$result = mysql_query($query) or die ("$query\n\n".mysql_error());
							}
						}
						$selite= $trow['nimi'] . " " . $trow['nimitark'] . " Lasno: " . $laskun_numero;
					}
					if (round(abs($totsumma - $omasumma),2) >= 0.01) { // Tuli pyöristyseroja
						$query = "INSERT into tiliointi set
							yhtio = '$yhtio',
							ltunnus = '$tunnus',
							tilino = '$yhtiorow[pyoristys]',
							kustp = '',
							tapvm = '$tpv-$tpk-$tpp',
							summa = $omasumma - $totsumma,
							selite = '$selite',
							vero = '0',
							lukko = '',
							laatija = 'verkkolas',
							laadittu = now()";
						$result = mysql_query($query) or die ("$query\n\n".mysql_error());
					}
					// jos kyseessä on jonkin rahti/huolintakuluja, tiliöidään varastonarvoon
					if (($trow['oletus_vienti'] != 'A') and ($trow['oletus_vienti'] != 'D') and ($trow['oletus_vienti'] != 'G') and ($trow['oletus_vienti'] != '')) {

						$varastotili = $yhtiorow['varasto'];
						if (($trow['oletus_vienti']=='C') or ($trow['oletus_vienti']=='F') or ($trow['oletus_vienti']=='I')) {
								$varastotili = $yhtiorow['matkalla_olevat'];
						}

						$query = "INSERT into tiliointi set
									yhtio ='$kukarow[yhtio]',
									ltunnus = '$tunnus',
									tilino = '$varastotili',
									kustp= '',
									tapvm = '$tpv-$tpk-$tpp',
									summa = '$vtontot',
									selite = '$selite',
									vero = '',
									lukko = '',
									laatija = '$kukarow[kuka]',
									laadittu = now()";
						$result = mysql_query($query) or die ("$query\n\n".mysql_error());

						// Jos tämä lasku menee varastoon, tehdään valmiiksi keikka
						if ($trow['oletus_vienti'] == 'C' or $trow['oletus_vienti'] == 'F' or $trow['oletus_vienti'] == 'I') {
							require ("inc/verkkolasku-in-luo-keikkafile.inc");
						}
					}
				}

				// Jos meillä on suoraveloitus
				if ($trow['oletus_suoraveloitus'] != '') {
					if ($trow['oletus_suoravel_pankki'] > 0) { //Toimittajalla on pankkitili, teemme eräpäivälle suorituksen valmiiksi
						// Oletustiliöinnit
						// Ostovelat
						$query = "INSERT into tiliointi set
								yhtio ='$kukarow[yhtio]',
								ltunnus = '$tunnus',
								tilino = '$ostovelat',
								tapvm = '$erv-$erk-$erp',
								summa = '$omasumma',
								vero = 0,
								lukko = '',
								laatija = '$kukarow[kuka]',
								laadittu = now()";
						$xresult = mysql_query($query) or die ("$query\n\n".mysql_error());

						// Rahatili
						$query = "INSERT into tiliointi set
									yhtio ='$kukarow[yhtio]',
									ltunnus = '$tunnus',
									tilino = '$yhtiorow[selvittelytili]',
									tapvm = '$erv-$erk-$erp',
									summa = $vassumma,
									vero = 0,
									lukko = '',
									laatija = '$kukarow[kuka]',
									laadittu = now()";
						$xresult = mysql_query($query) or die ("$query\n\n".mysql_error());
						if ($tila == 'M') {
							$query = "UPDATE lasku set
								tila = 'Y',
								mapvm = '$erv-$erk-$erp',
								maksu_kurssi = 1
								WHERE tunnus='$tunnus'";
							$xresult = mysql_query($query) or die ("$query\n\n".mysql_error());
							$laskuvirhe .= t('Lasku merkittiin suoraan maksetuksi')."\n";
						}
					}
					else { // Tämä koskee vain suoraveloitusta
						if ($tila == 'M') {
							$query = "UPDATE lasku set
								tila = 'Q'
								WHERE tunnus='$tunnus'";
							$xresult = mysql_query($query) or die ("$query\n\n".mysql_error());
							$laskuvirhe .= t('Lasku merkittiin odottamaan suoritusta')."\n";
						}
					}
				}
			}
		}
	}

	if ($kutsuja != "php" and $toim != "E") echo "OK!";

?>
