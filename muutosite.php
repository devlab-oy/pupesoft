<?php
	
require "inc/parametrit.inc";

echo "<font class='head'>".t("Tiliˆintien muutos/selailu")."</font><hr>";

if (($tee == 'U' or $tee == 'P' or $tee == 'M' or $tee == 'J') and ($oikeurow['paivitys'] != 1)) {
	echo "<font class='error'>".t("Yritit p‰ivitt‰‰ vaikka sinulla ei ole siihen oikeuksia")."</font>";
	exit;
}

// Jaksotus
if ($tee == 'J') {
	require "inc/jaksota.inc";
}

// Otsikon muutokseen
if ($tee == 'M') {
	require "inc/muutosite.inc";
}

// Seuraava "tosite"
if ($tee == 'G') {
	$query = "SELECT tapvm, tunnus
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' 
				and tunnus > '$tunnus'
				and tila in ('H','Y','M','P','Q','X','U')
				ORDER by tunnus
				LIMIT 1";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
			$trow=mysql_fetch_array ($result);
			$tunnus=$trow['tunnus'];
			$tee = 'E';
	}
	else {
		echo "<font class='error'>".t("Ei seuraavaa tositetta")."</font><br>";
		$tee = 'E';
	}
}

// Tositeselailu
if ($tee == 'Y' or $tee == 'Z' or $tee == 'X' or $tee == 'W' or $tee == 'T' or $tee == 'S' or $tee == '≈' or $tee == 'ƒ') {

	if  ($tee == 'Z' or $tee == 'X' or $tee == 'W' or $tee == 'T' or $tee == 'S' or $tee == '≈' or $tee == 'ƒ') {

		// Etsit‰‰n virheet vain kuluvalta tilikaudelta!
		if ($tee == 'Z') {
			$query = "	SELECT ltunnus, tapvm, round(sum(summa),2) summa, 'n/a', 'n/a', 'n/a', selite
						FROM tiliointi use index (yhtio_tapvm_tilino)
						WHERE yhtio = '$kukarow[yhtio]' and korjattu='' and tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$yhtiorow[tilikausi_loppu]'
						GROUP BY ltunnus, tapvm
						HAVING summa <> 0";
		}

		if ($tee == 'X') {
			$query = "	SELECT ltunnus, tapvm, summa, 'n/a', 'n/a', 'n/a', selite
						FROM tiliointi use index (yhtio_tilino_tapvm), tili use index (tili_index)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]' and tili.yhtio = '$kukarow[yhtio]' and
						tiliointi.tilino = tili.tilino and
						korjattu='' and tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$yhtiorow[tilikausi_loppu]' and
						sisainen_taso like '3%' and kustp = '' and tiliointi.tilino!='$yhtiorow[myynti]' and tiliointi.tilino!='$yhtiorow[myynti_ei_eu]' and tiliointi.tilino!='$yhtiorow[myynti_eu]' and tiliointi.tilino!='$yhtiorow[varastonmuutos]' and tiliointi.tilino!='$yhtiorow[pyoristys]'";
		}

		if ($tee == 'W') {
			$query = "	SELECT ltunnus, count(*) maara, round(sum(summa),2) heitto, 'n/a', 'n/a', 'n/a', selite
						FROM tiliointi use index (yhtio_tilino_tapvm)
						WHERE yhtio='$kukarow[yhtio]' and korjattu='' and
						tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$yhtiorow[tilikausi_loppu]' and tilino = '$yhtiorow[ostovelat]'
						GROUP BY ltunnus
						HAVING maara > 1 and heitto <> 0";
		}

		if ($tee == 'T') {
			$query = "	SELECT ltunnus, count(*) maara, tila, 'n/a', 'n/a', 'n/a', selite
						FROM tiliointi use index (yhtio_tilino_tapvm)
						LEFT JOIN lasku ON  lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus
						WHERE tiliointi.yhtio='$kukarow[yhtio]' and korjattu='' and
						tiliointi.tapvm >= '$yhtiorow[tilikausi_alku]' and tiliointi.tapvm <= '$yhtiorow[tilikausi_loppu]' and tilino = '$yhtiorow[ostovelat]' and tila < 'R'
						GROUP BY ltunnus
						HAVING maara > 1";
		}
		
		if ($tee == 'S') {
			$query = "	SELECT lasku.tunnus, lasku.laskunro, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.tapvm,
						if(sum(ifnull(t1.summa, 0))=0,0,1)+if(sum(ifnull(t2.summa, 0))=0,0,1)+if(sum(ifnull(t3.summa, 0))=0,0,1) korjattu,
						count(distinct t1.tilino)+count(distinct t2.tilino)+count(distinct t3.tilino) saamistilej‰ 
						FROM lasku
						LEFT JOIN tiliointi t1 ON lasku.yhtio=t1.yhtio and lasku.tunnus=t1.ltunnus and t1.korjattu = '' and t1.tilino='$yhtiorow[myyntisaamiset]' 
						LEFT JOIN tiliointi t2 ON lasku.yhtio=t2.yhtio and lasku.tunnus=t2.ltunnus and t2.korjattu = '' and t2.tilino='$yhtiorow[factoringsaamiset]'
						LEFT JOIN tiliointi t3 ON lasku.yhtio=t3.yhtio and lasku.tunnus=t3.ltunnus and t3.korjattu = '' and t3.tilino='$yhtiorow[konsernimyyntisaamiset]'
						WHERE lasku.yhtio	= '$kukarow[yhtio]' 
						and lasku.tila		= 'U' 
						and lasku.alatila	= 'X'
						and lasku.tapvm >= '$yhtiorow[tilikausi_alku]' 
						and lasku.tapvm <= '$yhtiorow[tilikausi_loppu]'
						GROUP BY 1,2,3,4,5,6
						HAVING saamistilej‰ > 1 and korjattu > 0";
		}
						
		if ($tee == '≈') {
			$query = "	(SELECT distinct lasku.tunnus, lasku.laskunro, lasku.nimi, lasku.tapvm, tr1.tuoteno, s1.sarjanumero, if(tr1.alv>=500, 'MV', tr1.alv) alv1, if(tr2.alv>=500, 'MV', tr2.alv) alv2, l2.laskunro, l2.nimi
						FROM lasku
						JOIN tiliointi t1 ON lasku.yhtio=t1.yhtio and lasku.tunnus=t1.ltunnus and t1.korjattu = '' and t1.tilino='$yhtiorow[osto_marginaali]'
						JOIN tilausrivi tr1 ON lasku.yhtio=tr1.yhtio and lasku.tunnus=tr1.uusiotunnus and tr1.alv>=500 and tr1.kpl<0
						JOIN sarjanumeroseuranta s1 ON tr1.yhtio=s1.yhtio and tr1.tunnus=s1.ostorivitunnus
						JOIN tilausrivi tr2 ON s1.yhtio=tr2.yhtio and s1.myyntirivitunnus=tr2.tunnus
						JOIN lasku l2 ON tr2.yhtio=l2.yhtio and tr2.uusiotunnus=l2.tunnus
						WHERE lasku.yhtio	= '$kukarow[yhtio]' 
						and lasku.tila		= 'U' 
						and lasku.alatila	= 'X'
						and lasku.tapvm >= '$yhtiorow[tilikausi_alku]' 
						and lasku.tapvm <= '$yhtiorow[tilikausi_loppu]'
						HAVING (alv1 != 'MV' or alv2 != 'MV')
						ORDER by lasku.laskunro)
						
						UNION DISTINCT
					
						(SELECT distinct l2.tunnus, l2.laskunro, l2.nimi, l2.tapvm, tr1.tuoteno, s1.sarjanumero, if(tr2.alv>=500, 'MV', tr2.alv) alv2, if(tr1.alv>=500, 'MV', tr1.alv) alv1, lasku.laskunro, lasku.nimi
						FROM lasku
						JOIN tiliointi t1 ON lasku.yhtio=t1.yhtio and lasku.tunnus=t1.ltunnus and t1.korjattu = '' and t1.tilino='$yhtiorow[myynti_marginaali]'
						JOIN tilausrivi tr1 ON lasku.yhtio=tr1.yhtio and lasku.tunnus=tr1.uusiotunnus and tr1.alv>=500 and tr1.kpl>0
						JOIN sarjanumeroseuranta s1 ON tr1.yhtio=s1.yhtio and tr1.tunnus=s1.myyntirivitunnus
						JOIN tilausrivi tr2 ON s1.yhtio=tr2.yhtio and s1.ostorivitunnus=tr2.tunnus
						JOIN lasku l2 ON tr2.yhtio=l2.yhtio and tr2.uusiotunnus=l2.tunnus
						WHERE lasku.yhtio	= '$kukarow[yhtio]' 
						and lasku.tila		= 'U' 
						and lasku.alatila	= 'X'
						and lasku.tapvm >= '$yhtiorow[tilikausi_alku]' 
						and lasku.tapvm <= '$yhtiorow[tilikausi_loppu]'
						HAVING (alv1 != 'MV' or alv2 != 'MV')
						ORDER by l2.laskunro)";
		}
		
		if ($tee == 'ƒ') {
			$query = "	SELECT lasku.tunnus ltunnus, lasku.ytunnus, lasku.nimi, lasku.tapvm, lasku.summa, ifnull(sum(t1.summa),0) + ifnull(sum(t2.summa),0) + ifnull(sum(t3.summa),0) ero
						FROM lasku
						LEFT JOIN tiliointi t1 ON (lasku.yhtio = t1.yhtio and lasku.tunnus = t1.ltunnus and t1.korjattu = '' and t1.tilino = '$yhtiorow[myyntisaamiset]')
						LEFT JOIN tiliointi t2 ON (lasku.yhtio = t2.yhtio and lasku.tunnus = t2.ltunnus and t2.korjattu = '' and t2.tilino = '$yhtiorow[factoringsaamiset]')
						LEFT JOIN tiliointi t3 ON (lasku.yhtio = t3.yhtio and lasku.tunnus = t3.ltunnus and t3.korjattu = '' and t3.tilino = '$yhtiorow[konsernimyyntisaamiset]')
						WHERE lasku.yhtio = '$kukarow[yhtio]' 
						and lasku.tila	  = 'U' 
						and lasku.alatila = 'X'
						and lasku.mapvm  != '0000-00-00'
						and lasku.tapvm  >= '$yhtiorow[tilikausi_alku]' 
						and lasku.tapvm  <= '$yhtiorow[tilikausi_loppu]'
						GROUP BY ltunnus
						HAVING round(sum(t1.summa),2) != 0 or round(sum(t2.summa),2) != 0 or round(sum(t3.summa),2) != 0
						ORDER by lasku.laskunro";
		}
		
	}
	else { 

		$plisa = "";
		$lisa  = "";
		$vlisa = "";
		$summa = str_replace ( ",", ".", $summa);

		$tav += 0; // Tehd‰‰n pvmst‰ numeroita
		$tak += 0;
		$tap += 0;

		if ($tav > 0 and $tav < 1000) {
			$tav += 2000;
		}
		
		if ($tav != 0 and $tak != 0 and $tap != 0) {
			$plisa = " and tapvm = '$tav-$tak-$tap' ";
		}
		elseif ($tav != 0 and $tak != 0) {
			$plisa = " and tapvm >= '$tav-$tak-01' and tapvm < '".date("Y-m-d",mktime(0, 0, 0, $tak+1, 1, $tav))."' ";
		}
		elseif ($tav != 0) {
			$plisa = " and tapvm >= '$tav-01-01' and tapvm < '".date("Y-m-d",mktime(0, 0, 0, 1, 1, $tav+1))."' ";
		}
		else {
			$plisa = " and tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$yhtiorow[tilikausi_loppu]' ";
		}
		
		if (strlen($selite) > 0) {
			$lisa = " and selite";
			if ($ohita == 'on') {
				$lisa .= " not ";
			}
			$lisa .= " like '%" . $selite . "%'";
		}

		if (strlen($tilino) > 0) {
			$lisa .= " and tiliointi.tilino = '" . $tilino . "'";
		}

		if (strlen($summa) > 0) {
			$summa += 0; // tehd‰‰n siit‰ numero
			$lisa .= " and abs(tiliointi.summa) = $summa";
		}

		if (strlen($laatija) > 0) {
			$lisa .= " and tiliointi.laatija = '" . $laatija . "'";
		}

		if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {
			if (strlen($tositenro) > 0) {
				list($tositenro1, $tositenro2) = split("-",$tositenro);
				$tositenro1 = (int) $tositenro1;
				$tositenro2 = (int) $tositenro2;
				$tositenro = sprintf ('%02d', $tositenro1) . sprintf ('%06d', $tositenro2);
				$lisa .= " and tiliointi.tosite = '$tositenro' ";
			}
		}
		$summa = "";

		if ($viivatut != 'on') {
			$vlisa = "and tiliointi.korjattu=''";
		}
		else {
			$slisa = ", concat_ws('@', korjattu, korjausaika) korjaus";
		}

		$query = "	SELECT tiliointi.ltunnus, tiliointi.tapvm, tiliointi.summa, tili.tilino,
					tili.nimi, vero, selite $slisa
					FROM tiliointi use index (yhtio_tapvm_tilino), tili
					WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
					tili.yhtio = tiliointi.yhtio and tili.tilino = tiliointi.tilino
					$plisa
					$vlisa
					$lisa
					ORDER BY tiliointi.ltunnus desc, tiliointi.tunnus";
	}

	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Haulla ei lˆytynyt yht‰‰n tositetta")."</font>";
		$tyhja = 1;
	}
	else {
		echo "<table><tr>";
		for ($i = 1; $i < mysql_num_fields($result); $i++) {
			echo "<th>".t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";
		echo "<tr>";

		while ($trow = mysql_fetch_array ($result)) {
			// Ei anneta t‰m‰n h‰m‰t‰ meit‰!
			if ($trow[7] == '@0000-00-00 00:00:00') $trow[7] = '';
			if ($trow[7] == '0000-00-00 00:00:00') $trow[7] = '';

			//Laitetaan linkki tuonne pvm:‰‰n, n‰in voimme avata tositteita tab:eihin
			if (strlen($edtunnus) > 0) {

				// Tosite vaihtui
				if ($trow[0] != $edtunnus) {
					echo "<tr><th height='10' colspan='".mysql_num_fields($result)."'></th></tr><tr>";
				}
				else {
					echo "</tr><tr>";
				}
			}

			$edtunnus = $trow[0];

			for ($i=1; $i < mysql_num_fields($result); $i++) {
				if ($i == 1) {
					if (mysql_field_name($result,$i) == 'tapvm') {
						$trow[$i] = tv1dateconv($trow[$i]);
					}							
					echo "<td><a href = '$PHP_SELF?tee=E&tunnus=$edtunnus&viivatut=$viivatut'>$trow[$i]</td>";
				}
				elseif (is_numeric($trow[$i]) and (mysql_field_type($result,$i) == 'real' or mysql_field_type($result,$i) == 'int')) {
					echo "<td align='right'>$trow[$i]</td>";
				}
				elseif (mysql_field_name($result, $i) == "tapvm") {
					echo "<td>".tv1dateconv($trow[$i])."</td>";
				}
				else {
					echo "<td>$trow[$i]</td>";
				}
			}
		}
	}
	echo "</tr></table><br><br>";
	$tee = "";
}

// Olemassaolevaa tiliˆinti‰ muutetaan, joten yliviivataan rivi ja annetaan perustettavaksi
if ($tee == 'P') {

	$query = "SELECT tilino, kustp, kohde, projekti, summa, vero, selite, tapvm, tosite
				FROM tiliointi
				WHERE tunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]' and tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$yhtiorow[tilikausi_loppu]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo t("Tiliˆinti‰ ei lˆydy tai se on lukitulla tilikaudella! Systeemivirhe!");
		exit;
	}

	$tiliointirow = mysql_fetch_array($result);

	$tili = $tiliointirow['tilino'];
	$kustp = $tiliointirow['kustp'];
	$kohde = $tiliointirow['kohde'];
	$projekti = $tiliointirow['projekti'];
	$summa = $tiliointirow['summa'];
	$vero = $tiliointirow['vero'];
	$selite = $tiliointirow['selite'];
	$tiliointipvm = $tiliointirow['tapvm'];
	$tositenro = $tiliointirow['tosite'];
	$ok = 1;

	// Etsit‰‰n kaikki tiliˆintirivit, jotka kuuluvat t‰h‰n tiliˆintiin ja lasketaan niiden summa
	$query = "	SELECT sum(summa) FROM tiliointi
				WHERE aputunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]' and tiliointi.korjattu='' GROUP BY aputunnus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 0) {
		$summarow = mysql_fetch_array($result);
		$summa += $summarow[0];
		$query = "UPDATE tiliointi SET korjattu = '$kukarow[kuka]', korjausaika = now()
					WHERE aputunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]' and tiliointi.korjattu=''";
		$result = mysql_query($query) or pupe_error($query);
	}

	$query = "UPDATE tiliointi
				SET korjattu = '$kukarow[kuka]', korjausaika = now()
				WHERE tunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$tee = 'E'; // N‰ytet‰‰n milt‰ tosite nyt n‰ytt‰‰
}

// Lis‰t‰‰n tiliˆintirivi
if ($tee == 'U') {
	$summa = str_replace ( ",", ".", $summa);
	$selausnimi = 'tili'; // Minka niminen mahdollinen popup on?

	require "inc/tarkistatiliointi.inc";

	$tiliulos = $ulos;
	$ulos = '';

	$query = "SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 1) {
		echo t("Laskua ei en‰‰ lˆydy! Systeemivirhe!");
		exit;
	}
	else {
		$laskurow = mysql_fetch_array($result);
	}

	// Tarvitaan kenties tositenro
	if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {

		if ($tositenro != 0) {
			$query = "	SELECT tosite FROM tiliointi
						WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and tosite='$tositenro'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
				$tositenro = 0;
			}
		}
		else {
			//T‰ll‰ ei viel‰ ole tositenroa. Yritet‰‰n jotain
			switch ($laskurow['tila']) {
				case "X" : // T‰m‰ on muistiotosite, sill‰ voi olla vain yksi tositenro
					$query = "SELECT distinct tosite FROM tiliointi
								WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) != 1) {
						echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
						$tositenro=0;
					}
					else {
						$tositerow=mysql_fetch_array ($result);
						$tositenro = $tositerow['tosite'];
					}
					break;

				case 'U' : //T‰m‰ on myyntilasku
					$query = "SELECT tosite FROM tiliointi
									WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus'";
					$result = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($result) != 0) {

						// T‰lle saamme tositenron myyntisaamisista
						if ($laskurow['tapvm'] == $tiliointipvm) {
							$query = "	SELECT tosite FROM tiliointi
										WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and
										tapvm='$tiliointipvm' and tilino = '$yhtiorow[myyntisaamiset]' and
										summa = $laskurow[summa]";
							$result = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($result) == 0) {
								echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
								$tositenro=0;
							}
							else {
								$tositerow=mysql_fetch_array ($result);
								$tositenro = $tositerow['tosite'];
							}
						}
						else {

							// T‰lle saamme tositenron jostain samanlaisesta viennist‰
							if ($laskurow['tapvm'] != $tiliointipvm) {
								$query = "	SELECT tosite FROM tiliointi
											WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and
											tapvm='$tiliointipvm' and tilino != '$yhtiorow[myyntisaamiset]' and
											summa != $laskurow[summa]";
								$result = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($result) == 0) {
									echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
									$tositenro=0;
								}
								else {
									$tositerow=mysql_fetch_array ($result);
									$tositenro = $tositerow['tosite'];
								}
							}
						}
					}
					else {
						echo t("Tositenumeron tarkistus ei onnistu, koska tositteen kaikki tiliˆinnit puuttuvat")."<br>";
						$tositenro=0;
					}
				default: //T‰m‰n pit‰isi olla nyt ostolasku

					// T‰lle saamme tositenron ostoveloista
					if ($laskurow['tapvm'] == $tiliointipvm) {
						$query = "	SELECT tosite FROM tiliointi
									WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and
									tapvm='$tiliointipvm' and tilino = '$yhtiorow[ostovelat]' and
									summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2) * -1";
						$result = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($result) == 0) {
							echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
							$tositenro = 0;
						}
						else {
							$tositerow = mysql_fetch_array ($result);
							$tositenro = $tositerow['tosite'];
						}
					}

					// T‰lle saamme tositenron ostoveloista
					if ($laskurow['mapvm'] == $tiliointipvm) {
						$query = "	SELECT tosite FROM tiliointi
									WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and
									tapvm='$tiliointipvm' and tilino = '$yhtiorow[ostovelat]' and
									summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2)";
						$result = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($result) == 0) {
							echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
							$tositenro=0;
						}
						else {
							$tositerow=mysql_fetch_array ($result);
							$tositenro = $tositerow['tosite'];
						}
					}
			}
		}
		echo "<font class='message'>Tiliˆintirivi liitettiin tositteeseen $tositenro</font><br>";
	}

	$tee = 'E';

	if ($ok != 1) {
		require "inc/teetiliointi.inc";
		if ($jaksota == 'on') {
			$tee = 'U';
			require "inc/jaksota.inc"; // Jos jotain jaksotetaan on $tee J
		}
	}
}

// Tositeen n‰yttˆ muokkausta varten
if ($tee == 'E' or $tee == 'F') {

	// N‰ytet‰‰n laskun tai tositteen tiedot....
	$query = "SELECT tila, concat_ws('@', ifnull(kuka.nimi, lasku.laatija), lasku.luontiaika) Laatija,
					ytunnus, lasku.nimi, nimitark, osoite, osoitetark, postino, postitp, maa,
					lasku.valkoodi,
					concat_ws(' / ',tapvm, mapvm) 'tapvm / mapvm',
					if(kasumma = 0,'',
					if (tila = 'U',
					if(lasku.valkoodi='$yhtiorow[valkoodi]',concat_ws('@',kasumma, kapvm),concat(kasumma, ' (', round(kasumma/if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2),lasku.valkoodi,')', '@', kapvm)),
					if(lasku.valkoodi='$yhtiorow[valkoodi]',concat_ws('@',kasumma, kapvm),concat(kasumma, ' (', round(kasumma*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2),'$yhtiorow[valkoodi])', '@', kapvm)))) kassa_ale,
					if (tila = 'U',
					if(lasku.valkoodi='$yhtiorow[valkoodi]', concat_ws('@', summa, erpcm),concat(summa, ' (', round(summa/if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2),lasku.valkoodi,')', '@', erpcm)),
					if(lasku.valkoodi='$yhtiorow[valkoodi]', concat_ws('@', summa, erpcm),concat(summa, ' (', round(summa*if(maksu_kurssi=0,vienti_kurssi,maksu_kurssi),2),'$yhtiorow[valkoodi])', '@', erpcm))) summa,
					concat_ws('@', ifnull((select nimi from kuka where kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.hyvak1), lasku.hyvak1), if(h1time='0000-00-00 00:00:00', null, h1time)) Hyv‰ksyj‰1,
					concat_ws('@', ifnull((select nimi from kuka where kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.hyvak2), lasku.hyvak2), if(h2time='0000-00-00 00:00:00', null, h2time)) Hyv‰ksyj‰2,
					concat_ws('@', ifnull((select nimi from kuka where kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.hyvak3), lasku.hyvak3), if(h3time='0000-00-00 00:00:00', null, h3time)) Hyv‰ksyj‰3,
					concat_ws('@', ifnull((select nimi from kuka where kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.hyvak4), lasku.hyvak4), if(h4time='0000-00-00 00:00:00', null, h4time)) Hyv‰ksyj‰4,
					concat_ws('@', ifnull((select nimi from kuka where kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.hyvak5), lasku.hyvak5), if(h5time='0000-00-00 00:00:00', null, h5time)) Hyv‰ksyj‰5,
					concat_ws('@', ifnull((select nimi from kuka where kuka.yhtio=lasku.yhtio and kuka.kuka=lasku.maksaja), lasku.maksaja), maksuaika) Maksaja,
					tilinumero, concat_ws(' ', viite, viesti, sisviesti1) Maksutieto,
					maa, ultilno, pankki1, pankki2, pankki3, pankki4, swift, clearing, maksutyyppi,
					ebid,
					toim_osoite, '' toim_osoitetark, toim_postino, toim_postitp, toim_maa, alatila, vienti, comments, yriti.nimi maksajanpankkitili, lasku.laskunro
					FROM lasku
					LEFT JOIN yriti ON lasku.yhtio=yriti.yhtio and maksu_tili=yriti.tunnus
					LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka 
					WHERE lasku.tunnus = '$tunnus' and lasku.yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 1) {
		echo t("Tiliˆinti‰ ei lˆydy! Systeemivirhe!");
		exit;
	}

	$trow = mysql_fetch_array($result);

	// jos pit‰‰ n‰ytt‰‰ keikan tietoja
	if ($tee2 == "1") {

		// jos myyntilasku
		if ($trow["tila"] == "U" or $trow["tila"] == "L") {
			$query = "	select maa_maara,kauppatapahtuman_luonne,
						kuljetusmuoto,sisamaan_kuljetus,
						sisamaan_kuljetusmuoto,sisamaan_kuljetus_kansallisuus,
						kontti,aktiivinen_kuljetus,
						aktiivinen_kuljetus_kansallisuus,
						poistumistoimipaikka,poistumistoimipaikka_koodi,
						bruttopaino,lisattava_era,vahennettava_era
						from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
			$keikres = mysql_query($query) or pupe_error($query);
			$keikrow = mysql_fetch_array($keikres);
		}
		// muissa keiseissa varmaan sitten keikkajuttuja
		else {
			$query = "	select laskunro from lasku where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus='$tunnus'";
			$keikres = mysql_query($query) or pupe_error($query);
			$keekrow = mysql_fetch_array($keikres);

			$query = "	select laskunro keikka, maa_lahetys 'l‰hetysmaa',
						kuljetusmuoto, kauppatapahtuman_luonne, rahti, rahti_etu, rahti_huolinta, erikoisale, bruttopaino, toimaika, comments
						from lasku where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus='' and laskunro='$keekrow[laskunro]'";
			$keikres = mysql_query($query) or pupe_error($query);
			$keikrow = mysql_fetch_array($keikres);

			$query = "select vanhatunnus, summa, nimi from lasku where yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keekrow[laskunro]' and vanhatunnus!='0' and vanhatunnus!='$tunnus'";
			$muutkeikres = mysql_query($query) or pupe_error($query);
		}
	}

	$laskuntila = $trow['tila'];
	$laskunpvm  = $trow[11];

	echo "<table><tr><td valign='top'>"; // T‰m‰ taulukko pit‰‰ sis‰ll‰‰n kaikki tositteen perustiedot

	// Yleiset tiedot
	echo "<table>"; // T‰m‰ aloittaa vasemman sarakkeen


	list($aa, $bb) = explode("@", $trow[1]);	
	echo "<tr><th>" . t(mysql_field_name($result,1)) ."</th><td>$aa@".tv1dateconv($bb, "P")."</td></tr>";			

	if ($trow[0] != 'X') {
		// Laskut
		for ($i = 2; $i < 10; $i++) {
			echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>$trow[$i]</td></tr>";
		}

		echo "</table></td><td valign='top'>"; // Lopetettiin vasen sarake

		echo "<table>"; // T‰ss‰ tehd‰‰n uusi sarake oikealle
		//Ei n‰ytet‰ myyntilaskulla hyv‰ksynt‰‰ vain toimitustiedot

		if ($trow['tila'] == 'U' or $trow['tila'] == 'L') {

			//Perustiedot
			if ($tee2 != 1) {
				for ($i = 10; $i < 14; $i++) {
					if ($i == 11) {
						list($aa, $bb) = explode(" / ", $trow[$i]);
						
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>".tv1dateconv($aa)." / ".tv1dateconv($bb)."</td></tr>"; 	
					}
					elseif ($i == 13) {
						list($aa, $bb) = explode("@", $trow[$i]);
						
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>$aa@".tv1dateconv($bb)."</td></tr>";
					}
					else {
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>$trow[$i]</td></tr>"; 	
					}													
				}
				for ($i = 32; $i < 37; $i++) {
					echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>$trow[$i]</td></tr>";
				}
			}
			else { //Laajennetut
				for ($i = 0; $i < 14; $i++) {
					echo "<tr><th>" . t(mysql_field_name($keikres,$i)) ."</th><td>$keikrow[$i]</td></tr>";
				}
			}
		}
		else {
			// Perustiedot
			if ($tee2 != 1) {
				for ($i = 10; $i < 19; $i++) {
					if ($i == 11) {
						list($aa, $bb) = explode(" / ", $trow[$i]);
						
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>".tv1dateconv($aa)." / ".tv1dateconv($bb)."</td></tr>"; 	
					}
					elseif ($i >= 13 and $i <= 18) {
						list($aa, $bb) = explode("@", $trow[$i]);
						
						if ($bb != "" and substr($bb,0,10) != "0000-00-00") {
							$aa = $aa."@";
						}
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>$aa".tv1dateconv($bb, "P")."</td></tr>";
					}
					else {
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>$trow[$i]</td></tr>"; 	
					}
				}
			}
			else { //Laajennetut
				for ($i = 0; $i < 11; $i++) {
					echo "<tr><th>" . t(mysql_field_name($keikres,$i)) ."</th><td>$keikrow[$i]</td></tr>";
				}
				if (mysql_num_rows($muutkeikres) != 0) {
					echo "<tr><th>keikan muut laskut</td><td>";
					while ($muutkeikrow = mysql_fetch_array($muutkeikres)) {
						echo "<a href='muutosite.php?tee=E&tunnus=$muutkeikrow[vanhatunnus]'>$muutkeikrow[nimi] ($muutkeikrow[summa])</a><br>";
					}
					echo "</td></tr>";
				}
			}
		}
		echo "</table></td><td valign='top'>"; // Lopetettiin t‰m‰ sarake

		echo "<table>"; // T‰ss‰ tehd‰‰n uusi sarake oikealle

		$alatila     = $trow['alatila'];
		$laskutyyppi = $trow['tila'];

		require "inc/laskutyyppi.inc";

		echo "<tr><th>".t("Tila").":</th><td>".t("$laskutyyppi")." ".t("$alatila")."</td></tr>";

		// Myynnille
		if ($trow['tila'] == 'U' or $trow['tila'] == 'L') {
			for ($i = 21; $i < 22; $i++) {
					echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>$trow[$i]</td></tr>";
			}
		}
		else {
			//Ulkomaan ostolaskuille
			if (strtoupper($trow[9]) != 'FI') {
				//Lis‰t‰‰n maksaja
				echo "<tr><th>".t("Maksaja")."</th><td>$trow[Maksaja]</td></tr>";
					
				for ($i = 21; $i < 29; $i++) {
					if ($trow[$i] != '') {
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>$trow[$i]</td></tr>";
					}
				}
			}
			else {
				//Kotimaan ostolaskuille
				for ($i = 19; $i < 22; $i++) {
					if ($i == 19) {
						list($aa, $bb) = explode("@", $trow[$i]);
						
						if ($bb != "" and substr($bb,0,10) != "0000-00-00") {
							$aa = $aa."@";
						}
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>$aa".tv1dateconv($bb, "P")."</td></tr>";
					}
					else {
						echo "<tr><th>" . t(mysql_field_name($result,$i)) ."</th><td>$trow[$i]</td></tr>";
					}
				}
			}
			if ($trow['maksajanpankkitili'] != '') {
				echo "<tr><th>".t("Oma pankkitili")."</th><td>$trow[maksajanpankkitili]</td></tr>";
			}
		}
		// en jaksa mietti‰ indeksilukuja perkele!
		if ($trow["comments"] != '')
			echo "<tr><th>".t("Kommentti")."</th><td>$trow[comments]</td></tr>";

		// tehd‰‰n lasku linkki
		echo "<tr><th>".t("Laskun kuva")."</th><td>".ebid($tunnus) ."</td></tr>";

	}
	else {
		// kommentti n‰kyviin
		if ($trow["comments"] != '') {
			echo "<tr><th>".t("Kommentti")."</th><td>$trow[comments]</td></tr>";
		}

		// Muu tosite
		echo "<tr><td>".t("Muu tosite")."</td>";

		// tehd‰‰n lasku linkki
		echo "<td>".ebid($tunnus) ."</td></tr>";

	}
	echo "<tr>";
	echo "<th></th><td>";

	// N‰ytet‰‰n nappi vain jos siihen on oikeus
	if ($oikeurow['paivitys'] == 1) {
		echo "<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'tee' value='M'>
				<input type = 'hidden' name = 'tila' value=''>
				<input type = 'hidden' name = 'tunnus' value='$tunnus'>
				<input type = 'submit' value = '".t("Muuta tietoja")."'>
				</form>";
	}
	

	$queryoik = "SELECT tunnus from oikeu where nimi like '%liitetiedostot.php' and kuka='{$kukarow['kuka']}' and yhtio='{$yhtiorow['yhtio']}'";
	$res = mysql_query($queryoik) or pupe_error($queryoik);

	if (mysql_num_rows($res) == 1 and $trow["ebid"] == "") {
		echo "<form method='get' action='liitetiedostot.php?liitos=lasku&id=$tunnus'>
			<input type='hidden' name='id' value='$tunnus'>
			<input type='hidden' name='liitos' value='lasku'>
			<input type='submit' value='" . t('Muokkaa liitteit‰')."'>
			</form>";
	}

	// N‰ytet‰‰n nappi vain jos tieoja on
	if ($trow['vienti'] != '' and $trow['vienti'] != 'A' and $trow['vienti'] != 'D' and $trow['vienti'] != 'G') {
		if ($tee2 != 1) {
			echo "<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'tee' value='$tee'>
				<input type = 'hidden' name = 'tee2' value='1'>
				<input type = 'hidden' name = 'tunnus' value='$tunnus'>
				<input type = 'submit' value = '".t("Lis‰tiedot")."'></form>";
		}
		else {
			echo "<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'tee' value='$tee'>
				<input type = 'hidden' name = 'tunnus' value='$tunnus'>
				<input type = 'submit' value = '".t("Normaalitiedot")."'></form>";
		}		
	}

	
	
	if ($trow['tila'] == 'U') {
		echo "<form action = 'tilauskasittely/tulostakopio.php' method='post'>
			<input type='hidden' name='otunnus' value='$tunnus'>
			<input type='hidden' name='TOIM' value='LASKU'>
			<input type='hidden' name='tee' value='NAYTATILAUS'>
			<input type='submit' value='" . t('N‰yt‰ laskun PDF')."'></form>";
	}
	
	echo "</tr>";
	echo "</table></td></tr>"; //Lopetettiin viimeinen sarake
	echo "</table>";
	
	// Tehd‰‰n nappula, jolla voidaan vaihtaa n‰kym‰ksi tilausrivit/tiliˆintirivit
	if ($tee == 'F') {
		$ftee = 'E';
		$fnappula = t('N‰yt‰ tiliˆinnit');
	}
	else {
		$ftee = 'F';
		$fnappula = t('N‰yt‰ tilausrivit');
	}

	echo "<form action = '$PHP_SELF' method='post'>
		<input type = 'hidden' name = 'tee' value='$ftee'>
		<input type = 'hidden' name = 'tunnus' value='$tunnus'>
		<tr><td><input type = 'submit' value = '$fnappula'></form>
		<form action = '$PHP_SELF' method='post'>
			<input type = 'hidden' name = 'tee' value='G'>
			<input type = 'hidden' name = 'tunnus' value='$tunnus'>
			<td><input type = 'submit' value = '".t("Seuraava")."'></form>";

	if ($tee == 'F') {
		// Laskun tilausrivit
		require "inc/tilausrivit.inc";
		$tee = '';
	}
	else {
		// Tositteen tiliˆintirivit...
		require "inc/tiliointirivit.inc";
		$tee = "";
	}

}

if (strlen($tee) == 0) {

	if (strlen($formi) == 0) {
		$formi = 'valikko';
		$kentta = 'tap';
	}

	echo "<form name = 'valikko' action = '$PHP_SELF' method='post'><table>";
	echo "<tr>
		  <td>".t("Etsi tositetta")."</td>
		  <td>".t("tapahtumapvm")."</td>
		  <td>
		  	<input type='hidden' name='tee' value='Y'>
			<input type='text' name='tap' maxlength='2' size=2>
			<input type='text' name='tak' maxlength='2' size=2>
			<input type='text' name='tav' maxlength='4' size=4></td>
		  <td></td>
		  </tr>
		  <tr>
		  <td></td>
		  <td>summa</td>
		  <td><input type='text' name='summa' size=10></td>
		  <td></td>
		  </tr>
		  <tr>
		  <td></td>
		  <td>tili</td>
		  <td><input type='text' name='tilino' size=10></td>
		  <td></td>
		  </tr>
		  <tr>
		  <td></td>
		  <td>".t("osa selitteest‰")."</td>
		  <td><input type='text' name='selite' maxlength='15' size=10></td>
		  <td><input type='checkbox' name='ohita' maxlength='15' size=10>".t("Ohita n‰m‰")."</td>
		  </tr>
		  <tr>
		  <td></td>
		  <td>laatija</td>
		  <td><input type='text' name='laatija' size=10></td>
		  <td></td>
		  </tr>";

	//$kpexport tulee salanasat.php:st‰
	if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {
		echo "
		  <tr>
		  <td></td>
		  <td>".t("tositenumero")."</td>
		  <td><input type='text' name='tositenro' size=10></td>
		  <td></td>
		  </tr>";
	}

	echo "<tr>
		  	<td></td>
		  	<td>".t("n‰yt‰ muutetut rivit")."</td>
		  	<td><input type='checkbox' name='viivatut'></td>
		  	<td><input type = 'submit' value = '".t("Etsi")."'></form></td></tr>
		
		  	<tr>
		  	<td>".t("Etsi virhett‰")."</td>
		  	<td>".t("n‰yt‰ tositteet, jotka eiv‰t t‰sm‰‰")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=Z' method='post'><input type = 'submit' value = '".t("N‰yt‰")."'></form></td>
		  	</tr>
			
			<tr>
		  	<td></td>
		  	<td>".t("n‰yt‰ tositteet, joilta puuttuu kustannuspaikka")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=X' method='post'><input type = 'submit' value = '".t("N‰yt‰")."'></form></td>
		  	</tr>
		
			<tr>
		  	<td></td>
		  	<td>".t("n‰yt‰ tositteet, joiden ostovelat ei t‰sm‰‰")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=W' method='post'><input type = 'submit' value = '".t("N‰yt‰")."'></form></td>
		  	</tr>
		
			<tr>
		  	<td></td>
		  	<td>".t("n‰yt‰ tositteet, joiden tila tuntuu v‰‰r‰lt‰")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=T' method='post'><input type = 'submit' value = '".t("N‰yt‰")."'></form></td>
		  	</tr>
			
			<tr>
		  	<td></td>
		  	<td>".t("n‰yt‰ tositteet, joiden myyntisaamiset ovat v‰‰rin")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=S' method='post'><input type = 'submit' value = '".t("N‰yt‰")."'></form></td>
			</tr>
			
			<tr>
		  	<td></td>
		  	<td>".t("n‰yt‰ tositteet, joiden marginaaliverotiliˆinnit ovat v‰‰rin")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=≈' method='post'><input type = 'submit' value = '".t("N‰yt‰")."'></form></td>
			</tr>

			<tr>
		  	<td></td>
		  	<td>".t("n‰yt‰ maksetut laskut, joilla on myyntisaamisia")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=ƒ' method='post'><input type = 'submit' value = '".t("N‰yt‰")."'></form></td>
			</tr>


			</table>";
}

require "inc/footer.inc";


?>
