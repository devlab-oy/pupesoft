<?php

	if (!function_exists('lisavarlisays')) {
		function lisavarlisays ($sarjatun, $lisatty_tun) {
			global $kukarow, $yhtiorow;

			$query 	= "	SELECT *
						from lasku
						where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
			$result  	= mysql_query($query) or pupe_error($query);
			$laskurow   = mysql_fetch_array($result);

			//Tutkitaan lisävarusteita
			$query = "	SELECT tilausrivi_osto.perheid2,
						sarjanumeroseuranta.hyllyalue,
						sarjanumeroseuranta.hyllynro,
						sarjanumeroseuranta.hyllyvali,
						sarjanumeroseuranta.hyllytaso,
						concat_ws('#!¡!#', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) paikka
						FROM sarjanumeroseuranta
						JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus and tilausrivi_osto.tunnus=tilausrivi_osto.perheid2
						WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
						and sarjanumeroseuranta.tunnus  = '$sarjatun'";
			$sarjares = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sarjares) == 1) {
				$sarjarow_isa = mysql_fetch_array($sarjares);

				// Ostorivi
				$query = "	SELECT if(kpl!=0, kpl, if(varattu!=0, varattu, tilkpl)) kpl
							FROM tilausrivi
							WHERE yhtio  = '$kukarow[yhtio]'
							and tunnus   = '$sarjarow_isa[perheid2]'
							and perheid2!= 0";
				$sarjares = mysql_query($query) or pupe_error($query);
				$ostorow = mysql_fetch_array($sarjares);

				// Haetaan muut lisävarusteet
				$query = "	SELECT tilausrivi.tuoteno,
							round(tilausrivi.kpl/$ostorow[kpl],2) kpl,
							round(tilausrivi.varattu/$ostorow[kpl],2) varattu,
							round(tilausrivi.tilkpl/$ostorow[kpl],2) tilkpl,
							tilausrivi.tilkpl sistyomaarayskpl, tilausrivi.var, tilausrivi.tyyppi,
							concat_ws('#!¡!#', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
							tilausrivi.hyllyalue,
							tilausrivi.hyllynro,
							tilausrivi.hyllyvali,
							tilausrivi.hyllytaso,
							tilausrivin_lisatiedot.sistyomaarays_sarjatunnus,
							tilausrivi.perheid2
							FROM tilausrivi use index (yhtio_perheid2)
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
							WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
							and tilausrivi.perheid2 = '$sarjarow_isa[perheid2]'
							and tilausrivi.tyyppi  != 'D'
							and tilausrivi.tunnus  != '$sarjarow_isa[perheid2]'
							and tilausrivi.perheid2!= 0";
				$sarjares1 = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sarjares1) > 0) {
					$query = "	SELECT tunnus, perheid2, (varattu+jt+kpl) kpl
								FROM tilausrivi
								WHERE yhtio  = '$kukarow[yhtio]'
								and tunnus   = '$lisatty_tun'";
					$myyres = mysql_query($query) or pupe_error($query);
					$myyrow = mysql_fetch_array($myyres);

					if (abs($myyrow["kpl"]) != 1) {
						return t("VIRHE: Jos tuotteella on lisävausteita on tilausrivin tilattumäärän oltava 1!");
					}

					if ($myyrow["perheid2"] == 0) {
						$query = "	UPDATE tilausrivi
						 			SET perheid2 = tunnus
									WHERE yhtio  = '$kukarow[yhtio]'
									and tunnus   = '$lisatty_tun'";
						$myyres = mysql_query($query) or pupe_error($query);

						$myyrow["perheid2"] = $myyrow["tunnus"];
					}

					while ($sarjarow = mysql_fetch_array($sarjares1)) {

						// haetaan tuotteen tiedot
						$query = "	SELECT *
									from tuote
									where yhtio = '$kukarow[yhtio]'
									and tuoteno = '$sarjarow[tuoteno]'";
						$tuoteres = mysql_query($query);

						if (mysql_num_rows($tuoteres) == 0) {
							echo "<font class='error'>Tuotetta $sarjarow[tuoteno] ei löydy!</font><br>";
						}
						else {
							// tuote löytyi ok, lisätään rivi
							$trow = mysql_fetch_array($tuoteres);

							$ytunnus         = $laskurow["ytunnus"];

							if ($sarjarow["tyyppi"] == 'G') {
								$kpl		 = $sarjarow["sistyomaarayskpl"];
								$var		 = "";
							}
							elseif ($sarjarow["var"] != 'P') {
								if ($sarjarow["kpl"] != 0) {
									$kpl	 = $sarjarow["kpl"];
								}
								else {
									$kpl	 = $sarjarow["varattu"];
								}
								$var		 = "";
							}
							else {
								$kpl		 = $sarjarow["tilkpl"];
								$var		 = "P";
							}

							$tuoteno         = $sarjarow["tuoteno"];
							$perheid2		 = $myyrow["perheid2"];

							//Tutkitaan vielä ettei tämä lisävaruste ole jo tän tuotteen alla
							$query = "	SELECT tunnus, tilkpl, varattu, jt, var
										FROM tilausrivi use index (yhtio_perheid2)
										WHERE yhtio  = '$kukarow[yhtio]'
										and tuoteno  = '$tuoteno'
										and perheid2 = '$perheid2'";
							$tarkres = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($tarkres) == 0) {

								$toimaika 	     = $laskurow["toimaika"];
								$kerayspvm	     = $laskurow["kerayspvm"];
								$hinta 		     = "";
								$netto 		     = "";
								$alv		     = "";

								for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
									${'ale'.$alepostfix} = "";
								}

								if ($sarjarow_isa["paikka"] != "#!¡!##!¡!##!¡!#") {
									$paikka		 = $sarjarow_isa["paikka"];
								}

								$varasto 	     = "";
								$rivitunnus		 = "";
								$korvaavakielto	 = "";
								$jtkielto 		 = $laskurow['jtkielto'];

								// Ennakkotilaukset, Tarjoukset, Ylläpitosopimukset ja Valmistukset eivät tee saldotsekkiä
								if ($laskurow["tilaustyyppi"] == "E" or $laskurow["tilaustyyppi"] == "T" or $laskurow["tilaustyyppi"] == "0" or $laskurow["tila"] == "V") {
									$varataan_saldoa = "EI";
								}
								else {
									$varataan_saldoa = "";
								}

								$perheid 		 = 0;

								// Jos lisävaruste on pultattu kiinni laitteeseen sisäisellä työmääräyksellä
								// niin silloin, jos lisävauste on sarjanumeroitava, niin sarjanumeron tunnus tulee
								// lisävarusterivin sistyomaarays_sarjatunnus-kentässä
								if ($sarjarow["tyyppi"] == 'G' and $sarjarow["sistyomaarays_sarjatunnus"] > 0) {
									$query = "	SELECT tunnus, myyntirivitunnus
												FROM sarjanumeroseuranta
												WHERE yhtio	= '$kukarow[yhtio]'
												and tunnus  = '$sarjarow[sistyomaarays_sarjatunnus]'
												and siirtorivitunnus = '$sarjarow[perheid2]'";
									$tarkres = mysql_query($query) or pupe_error($query);

									if (mysql_num_rows($tarkres) == 1) {
										$myy_sarjatunnus = $sarjarow["sistyomaarays_sarjatunnus"];
									}
								}

								// jos meillä on ostoskori muuttujassa numero, niin halutaan lisätä tuotteita siihen ostoskoriin
								if (is_numeric($ostoskori)) {
									lisaa_ostoskoriin ($ostoskori, $laskurow["liitostunnus"], $tuoteno, $kpl);
									$kukarow["kesken"] = "";
								}
								elseif (file_exists("../tilauskasittely/lisaarivi.inc")) {
									require ("../tilauskasittely/lisaarivi.inc");
								}
								else {
									require ("lisaarivi.inc");
								}

								echo "<font class='message'>Lisättiin $kpl kpl tuotetta $tuoteno.</font><br>";
							}
							else {
								// päivitetään varmuuden vuoksi tehdaslisävarusteen paikaksi sama kuin veneen paikka ja poistetaan JT-status jos rivi on JT-ssä
								$tarkrow = mysql_fetch_array($tarkres);

								if ($tarkrow["var"] == "J") {

									$Ulisa = " , var='' ";

									if ($yhtiorow["varaako_jt_saldoa"] == "") {
										$Ulisa .= " , varattu = jt, jt = 0 ";
									}
								}
								else {
									$Ulisa = "";
								}

								$query = "	UPDATE tilausrivi
											SET hyllyalue	= '$sarjarow_isa[hyllyalue]',
											hyllynro		= '$sarjarow_isa[hyllynro]',
											hyllyvali		= '$sarjarow_isa[hyllyvali]',
											hyllytaso		= '$sarjarow_isa[hyllytaso]'
											$Ulisa
											WHERE yhtio	= '$kukarow[yhtio]'
											and tunnus 	= '$tarkrow[tunnus]'";
								$tarkres = mysql_query($query) or pupe_error($query);
							}
						}
					}

					echo "<br><br>";
				}
			}

			return "OK";
		}
	}

?>
