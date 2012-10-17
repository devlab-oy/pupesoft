<?php

	require("../inc/parametrit.inc");

	echo "<font class='head'>".t("Sarjanumeroiden saldokorjaus")."</font><hr>";

	if ($kukarow["kuka"] == "admin") {
		echo "<form name='haku' method='post'>";
		echo "<input type='hidden' name='toiminto' value='TULOSTA'>";
		echo "<input type='submit' name='$subnimi' value='Korjaa!'>";
		echo "</form>";
	}
	else {
		echo "Vain admin käyttäjälle!";
	}

	if ($toiminto == 'TULOSTA' and $kukarow["kuka"] == "admin") {

		// haetaan tuotteet
		list($saldot, $lisavarusteet, $paikat) = hae_tuotteet();

		// korjataan saldot
		// nollataan eka kaikkien sarjanumerollisten tuotteiden saldot
		$query = "	UPDATE tuotepaikat, tuote SET
					tuotepaikat.saldo		= '0',
					tuotepaikat.saldoaika	= now(),
					tuotepaikat.muuttaja	= '$kukarow[kuka]',
					tuotepaikat.muutospvm	= now()
					WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
					and tuote.yhtio			= tuotepaikat.yhtio
					and tuote.tuoteno		= tuotepaikat.tuoteno
					and tuote.sarjanumeroseuranta != ''";
		$paikres = pupe_query($query);

		// päivitetään eka virheelliset varastopaikat
		foreach($paikat as $tuote => $kpl) {

			list($tuoteno, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $sarjanumerotunnus) = explode("#!#", $tuote);

			// katotaan löytyykö paikka mikä oli sarjanumerolla
			$query = "	SELECT *
						FROM tuotepaikat
						WHERE tuoteno	= '$tuoteno'
						and yhtio		= '$kukarow[yhtio]'
						and hyllyalue	= '$hyllyalue'
						and hyllynro	= '$hyllynro'
						and hyllyvali	= '$hyllyvali'
						and hyllytaso	= '$hyllytaso'";
			$alkuresult = pupe_query($query);
			$alkurow = mysql_fetch_array($alkuresult);

			// katotaan onko paikka OK
			$tunnus = kuuluukovarastoon($hyllyalue, $hyllynro);

			# jos sarjanumeron tiedoissa on joku väärä varastopaikka, niin haetaan ekan varaston eka paikka ja laitetaan se sinne!
			if ($tunnus == 0) {

				echo "<br>Päivitettiin sarjanumeron varastopaikka $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso -> ";

				$query = "	SELECT alkuhyllyalue, alkuhyllynro, tunnus
							FROM varastopaikat
							WHERE yhtio = '$kukarow[yhtio]' AND varasto_status != 'P'
							ORDER BY alkuhyllyalue, alkuhyllynro
							LIMIT 1";
				$ekavarres = pupe_query($query);
				$ekavarrow = mysql_fetch_array($ekavarres);

				$hyllyalue = $ekavarrow["alkuhyllyalue"];
				$hyllynro  = $ekavarrow["alkuhyllynro"];
				$hyllytaso = $hyllyvali = 0;

				$query = "	UPDATE sarjanumeroseuranta SET
							hyllyalue = '$hyllyalue',
							hyllynro  = '$hyllynro',
							hyllytaso = '$hyllytaso',
							hyllyvali = '$hyllyvali'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$sarjanumerotunnus'";
				$ekavarres = pupe_query($query);

				echo "$hyllyalue-$hyllynro-$hyllyvali-$hyllytaso";
			}
		}

		// haetaan tuotteet
		list($saldot, $lisavarusteet, $paikat) = hae_tuotteet();

		// päivitetään saldot
		foreach($saldot as $tuote => $kpl) {

			list($tuoteno, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode("#!#", $tuote);

			// katotaan löytyykö paikka mikä oli sarjanumerolla
			$query = "	SELECT *
						FROM tuotepaikat
						WHERE tuoteno	= '$tuoteno'
						and yhtio		= '$kukarow[yhtio]'
						and hyllyalue	= '$hyllyalue'
						and hyllynro	= '$hyllynro'
						and hyllyvali	= '$hyllyvali'
						and hyllytaso	= '$hyllytaso'";
			$alkuresult = pupe_query($query);
			$alkurow = mysql_fetch_array($alkuresult);

			// katotaan onko paikka OK (pitäs olla kyllä kaikki OK!)
			$tunnus = kuuluukovarastoon($hyllyalue, $hyllynro);

			if ($tunnus == 0) {
				echo "<h1>miten tänne tultiin $query</h1>";
			}

			// jos paikka on OK
			if ($tunnus != 0) {

				// tuotteella ei ollut perustettu tätä paikkaa
				if (mysql_num_rows($alkuresult) == 0) {
					// katotaaan eka onko joku paikka jo oletus
					$query = "	SELECT *
								FROM tuotepaikat
								WHERE tuoteno	= '$tuoteno'
								and yhtio		= '$kukarow[yhtio]'
								and oletus		!= ''";
					$paikres = pupe_query($query);

					if (mysql_num_rows($paikres) == 0) {
						$oletus = 'X';
					}
					else {
						$oletus = '';
					}

					$query = "	INSERT into tuotepaikat SET
								tuoteno		= '$tuoteno',
								yhtio		= '$kukarow[yhtio]',
								hyllyalue	= '$hyllyalue',
								hyllynro	= '$hyllynro',
								hyllyvali	= '$hyllyvali',
								hyllytaso	= '$hyllytaso',
								saldo		= '$kpl',
								saldoaika	= now(),
								laatija		= '$kukarow[kuka]',
								luontiaika	= now(),
								muuttaja	= '$kukarow[kuka]',
								muutospvm	= now(),
								oletus		= '$oletus'";
					$paikres = pupe_query($query);

					$tapahtumaquery = "	INSERT into tapahtuma set
										yhtio 		= '$kukarow[yhtio]',
										tuoteno 	= '$tuoteno',
										kpl 		= 0,
										kplhinta	= 0,
										hinta 		= 0,
										laji 		= 'uusipaikka',
										hyllyalue 	= '$hyllyalue',
										hyllynro 	= '$hyllynro',
										hyllyvali 	= '$hyllyvali',
										hyllytaso 	= '$hyllytaso',
										selite 		= '".t("Sarjanumeroiden saldoissa lisättiin tuotepaikka")." $hyllyalue $hyllynro $hyllyvali $hyllytaso',
										laatija 	= '$kukarow[kuka]',
										laadittu 	= now()";
					$tapahtumaresult = pupe_query($tapahtumaquery);
				}
				elseif (mysql_num_rows($alkuresult) == 1) {
					$query = "	UPDATE tuotepaikat SET
								saldo			= '$kpl',
								saldoaika		= now(),
								muuttaja		= '$kukarow[kuka]',
								muutospvm		= now()
								WHERE tuoteno	= '$tuoteno'
								and yhtio		= '$kukarow[yhtio]'
								and hyllyalue	= '$hyllyalue'
								and hyllynro	= '$hyllynro'
								and hyllyvali	= '$hyllyvali'
								and hyllytaso	= '$hyllytaso'";
					$alkuresult = pupe_query($query);
				}
				else {
					echo "Tuotteella on useampi SAMA tuotepaikka!?!?! unpossible.";
				}

				echo "<br>Tuote $tuoteno saldo muutettu $kpl paikalla $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso";
			}

		}

		// haetaan tuotteet
		list($saldot, $lisavarusteet, $paikat) = hae_tuotteet();

		// korjataan saldo_varatut
		// nollataan eka kaikkien tuotteiden saldo_varattu
		$query = "	UPDATE tuotepaikat SET
					saldo_varattu	= '0',
					saldoaika		= now(),
					muuttaja		= '$kukarow[kuka]',
					muutospvm		= now()
					WHERE yhtio		= '$kukarow[yhtio]'";
		$paikres = pupe_query($query);

		foreach($lisavarusteet as $tuote => $kpl) {

			list($tuoteno, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode("#!#", $tuote);

			$query = "	SELECT saldo, saldo_varattu
						FROM tuotepaikat
						WHERE tuoteno	= '$tuoteno'
						and yhtio		= '$kukarow[yhtio]'
						and hyllyalue	= '$hyllyalue'
						and hyllynro	= '$hyllynro'
						and hyllyvali	= '$hyllyvali'
						and hyllytaso	= '$hyllytaso'";
			$alkuresult = pupe_query($query);
			$alkurow = mysql_fetch_array($alkuresult);

			// katotaan onko paikka OK
			$tunnus = kuuluukovarastoon($hyllyalue, $hyllynro);

			// pitäisi olla kaikki OK
			if ($tunnus == 0) {
				echo "<h1>tänne ei pitäs tulla $query</h1>";
			}

			// jos paikka on OK
			if ($tunnus != 0) {

				// tuotteella ei ollut perustettu tätä paikkaa
				if (mysql_num_rows($alkuresult) == 0) {

					// katotaaan eka onko joku paikka jo oletus
					$query = "	SELECT *
								FROM tuotepaikat
								WHERE tuoteno	= '$tuoteno'
								and yhtio		= '$kukarow[yhtio]'
								and oletus		!= ''";
					$paikres = pupe_query($query);

					if (mysql_num_rows($paikres) == 0) {
						$oletus = 'X';
					}
					else {
						$oletus = '';
					}

					$query = "	INSERT into tuotepaikat SET
								tuoteno		= '$tuoteno',
								yhtio		= '$kukarow[yhtio]',
								hyllyalue	= '$hyllyalue',
								hyllynro	= '$hyllynro',
								hyllyvali	= '$hyllyvali',
								hyllytaso	= '$hyllytaso',
								saldo_varattu = '$kpl',
								saldoaika	= now(),
								laatija		= '$kukarow[kuka]',
								luontiaika	= now(),
								muuttaja	= '$kukarow[kuka]',
								muutospvm	= now(),
								oletus		= '$oletus'";
					$paikres = pupe_query($query);

					$tapahtumaquery = "	INSERT into tapahtuma set
										yhtio 		= '$kukarow[yhtio]',
										tuoteno 	= '$tuoteno',
										kpl 		= 0,
										kplhinta	= 0,
										hinta 		= 0,
										laji 		= 'uusipaikka',
										hyllyalue 	= '$hyllyalue',
										hyllynro 	= '$hyllynro',
										hyllyvali 	= '$hyllyvali',
										hyllytaso 	= '$hyllytaso',
										selite 		= '".t("Sarjanumeroiden saldoissa lisättiin tuotepaikka")." $hyllyalue $hyllynro $hyllyvali $hyllytaso',
										laatija 	= '$kukarow[kuka]',
										laadittu 	= now()";
					$result = pupe_query($tapahtumaquery);

				}
				elseif (mysql_num_rows($alkuresult) == 1) {
					$query = "	UPDATE tuotepaikat SET
								saldo_varattu	= '$kpl',
								saldoaika		= now(),
								muuttaja		= '$kukarow[kuka]',
								muutospvm		= now()
								WHERE tuoteno	= '$tuoteno'
								and yhtio		= '$kukarow[yhtio]'
								and hyllyalue	= '$hyllyalue'
								and hyllynro	= '$hyllynro'
								and hyllyvali	= '$hyllyvali'
								and hyllytaso	= '$hyllytaso'";
					$alkuresult = pupe_query($query);
				}
				else {
					echo "Tuotteella on useampi SAMA tuotepaikka!?!?! wtf?";
				}

				echo "<br>Tuote $tuoteno saldo_varattu muutettu $kpl paikalla $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso";
			}
		}

	}

	require ("../inc/footer.inc");

	function hae_tuotteet() {

		global $kukarow;

		$lisavarusteet = array();
		$saldot = array();
		$paikat = array();

		// Näytetään kaikki vapaana/myymättä olevat sarjanumerot
		$query	= "	SELECT sarjanumeroseuranta.*,
					if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys) nimitys,
					tuote.myyntihinta 									tuotemyyntihinta,
					lasku_osto.tunnus									osto_tunnus,
					lasku_osto.nimi										osto_nimi,
					lasku_myynti.tunnus									myynti_tunnus,
					lasku_myynti.nimi									myynti_nimi,
					lasku_myynti.tila									myynti_tila,
					(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)		ostohinta,
					tilausrivi_osto.perheid2							osto_perheid2,
					tilausrivi_osto.kpl									osto_kpl,
					tilausrivi_osto.tilkpl								osto_tilkpl,
					(tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)	myyntihinta,
					varastopaikat.nimitys								varastonimi,
					sarjanumeroseuranta.lisatieto						lisatieto,
					tilausrivi_myynti.laskutettuaika					myynti_laskutettuaika,
					tilausrivi_osto.laskutettuaika						osto_laskutettuaika,
					concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka,
					sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso
					FROM sarjanumeroseuranta
					LEFT JOIN tuote use index (tuoteno_index) ON sarjanumeroseuranta.yhtio = tuote.yhtio and sarjanumeroseuranta.tuoteno = tuote.tuoteno
					LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus = sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus = sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio = sarjanumeroseuranta.yhtio and lasku_myynti.tunnus = tilausrivi_myynti.otunnus
					LEFT JOIN lasku lasku_osto use index (PRIMARY) ON lasku_osto.yhtio = sarjanumeroseuranta.yhtio and lasku_osto.tunnus = tilausrivi_osto.otunnus
					LEFT JOIN varastopaikat ON sarjanumeroseuranta.yhtio = varastopaikat.yhtio
					AND concat(rpad(upper(varastopaikat.alkuhyllyalue)  ,5,'0'),lpad(upper(varastopaikat.alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
					AND concat(rpad(upper(varastopaikat.loppuhyllyalue) ,5,'0'),lpad(upper(varastopaikat.loppuhyllynro) ,5,'0')) >= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
					WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					AND sarjanumeroseuranta.myyntirivitunnus != -1
					AND (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
					AND tilausrivi_osto.laskutettuaika != '0000-00-00'
					ORDER BY sarjanumeroseuranta.kaytetty, sarjanumeroseuranta.tuoteno, sarjanumeroseuranta.myyntirivitunnus";
		$sarjares = pupe_query($query);

		while ($sarjarow = mysql_fetch_array($sarjares)) {

			// lasketaan lisävarusteet
			if ($sarjarow["osto_perheid2"] > 0) {
				$query = "	SELECT
							tilausrivi.tuoteno,
							tilausrivi.nimitys,
							if(tilausrivi.kpl!=0, round(tilausrivi.kpl/$sarjarow[osto_kpl],2), round(tilausrivi.tilkpl/$sarjarow[osto_tilkpl],2)) kpl
							FROM tilausrivi use index (yhtio_perheid2)
							WHERE tilausrivi.yhtio 		= '$sarjarow[yhtio]'
							and tilausrivi.tyyppi 	   != 'D'
							and tilausrivi.perheid2 	= '$sarjarow[osto_perheid2]'
							and tilausrivi.tunnus	   != tilausrivi.perheid2
							order by tilausrivi.tunnus";
				$tilrivires = pupe_query($query);

				while ($tilrivirow2 = mysql_fetch_array($tilrivires)) {
					$key = $tilrivirow2["tuoteno"]."#!#".$sarjarow["hyllyalue"]."#!#".$sarjarow["hyllynro"]."#!#".$sarjarow["hyllyvali"]."#!#".$sarjarow["hyllytaso"];
					$lisavarusteet[$key] += $tilrivirow2["kpl"];
				}
			}

			// pitää uppercaseta!
			$sarjarow["tuoteno"]   = strtoupper($sarjarow["tuoteno"]);
			$sarjarow["hyllyalue"] = strtoupper($sarjarow["hyllyalue"]);
			$sarjarow["hyllynro"]  = strtoupper($sarjarow["hyllynro"]);
			$sarjarow["hyllyvali"] = strtoupper($sarjarow["hyllyvali"]);
			$sarjarow["hyllytaso"] = strtoupper($sarjarow["hyllytaso"]);

			// normituotteet
			$key = $sarjarow["tuoteno"]."#!#".$sarjarow["hyllyalue"]."#!#".$sarjarow["hyllynro"]."#!#".$sarjarow["hyllyvali"]."#!#".$sarjarow["hyllytaso"];
			$saldot[$key] += 1;

			$key = $sarjarow["tuoteno"]."#!#".$sarjarow["hyllyalue"]."#!#".$sarjarow["hyllynro"]."#!#".$sarjarow["hyllyvali"]."#!#".$sarjarow["hyllytaso"]."#!#".$sarjarow["tunnus"];
			$paikat[$key] += 1;
		}

		ksort($lisavarusteet);
		ksort($saldot);
		ksort($paikat);

		return array($saldot, $lisavarusteet, $paikat);
	}

?>