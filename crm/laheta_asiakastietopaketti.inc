<?php
	//Haetaan viikon päivät
	viikonpaivat($kausi);

	$query = "	SELECT asiakas.ytunnus, asiakas.asiakasnro, kalenteri.yhtio, left(kalenteri.pvmalku,10) pvmalku,
				asiakas.nimi, asiakas.nimitark, asiakas.osoite, asiakas.postino, asiakas.maa, asiakas.postitp,
				asiakas.toim_nimi, asiakas.toim_nimitark, asiakas.toim_osoite, asiakas.toim_postino, asiakas.toim_maa, asiakas.toim_postitp,
				asiakas.puhelin, asiakas.fax, asiakas.email,
				kentta01, kentta02, kentta03, kentta04, if(right(pvmalku,8)='00:00:00','',right(pvmalku,8)) aikaalku, if(right(pvmloppu,8)='00:00:00','',right(pvmloppu,8)) aikaloppu
				FROM kalenteri
				LEFT JOIN kuka ON kalenteri.kuka=kuka.kuka and kalenteri.yhtio=kuka.yhtio
				LEFT JOIN asiakas ON kalenteri.yhtio=asiakas.yhtio and kalenteri.liitostunnus=asiakas.tunnus
				WHERE kalenteri.yhtio = '$kukarow[yhtio]'
				and kalenteri.kuka = '$kukarow[kuka]'
				and pvmalku >= '$viikkoalku'
				and pvmalku <= '$viikkoloppu'
				and tapa = 'Viikkosuunnitelma'
				and tyyppi in ('kalenteri','memo')
				order by pvmalku";
	$aresult = pupe_query($query);

	while ($row = mysql_fetch_assoc($aresult)) {

		$viesti  = "";
		$viesti .= "Asiakas:  $row[ytunnus]\r\n";
		$viesti .= "Asiakasno:  $row[asiakasnro]\r\n";
		$viesti .= "Nimi:     $row[nimi]\r\n";
		$viesti .= "Nimitark: $row[nimitark]\r\n";
		$viesti .= "Osoite:   $row[osoite]\r\n";
		$viesti .= "Postino:  $row[postino]\r\n";
		$viesti .= "Postitp:  $row[postitp]\r\n";
		$viesti .= "Maa:      $row[maa]\r\n\r\n";

		$viesti .= "Toimitusosoite:   $row[toim_osoite]\r\n";
		$viesti .= "Toimituspostino:  $row[toim_postino]\r\n";
		$viesti .= "Toimituspostitp:  $row[toim_postitp]\r\n";
		$viesti .= "Toimitusmaa:      $row[toim_maa]\r\n\r\n";

		$viesti .= "Puhelin:     $row[puhelin]\r\n";
		$viesti .= "Fax:         $row[fax]\r\n";
		$viesti .= "Sähköposti:  $row[email]\r\n\r\n";

		$query = "	SELECT *
					FROM yhteyshenkilo
					WHERE yhtio 	 = '$kukarow[yhtio]'
					and liitostunnus = '$row[ytunnus]'
					and tyyppi 		 = 'A'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			$viesti .= "Asiakkaan yhteyshenkilöt:\r\n";

			while ($arow = mysql_fetch_assoc($result)) {
				$viesti .= "Nimi:       $arow[nimi]\r\n";
				$viesti .= "Titteli:    $arow[titteli]\r\n";
				$viesti .= "Puhelin:    $arow[puh]\r\n";
				$viesti .= "GSM:        $arow[gsm]\r\n";
				$viesti .= "Fax:        $arow[fax]\r\n";
				$viesti .= "Sähköposti: $arow[email]\r\n";
				$viesti .= "Kotisivut:  $arow[www]\r\n\r\n";
			}
		}

		$vuosi = date("Y")-1;

		$query = "	SELECT asiakas.ytunnus, kalenteri.yhtio, left(kalenteri.pvmalku,10) pvmalku, kuka.nimi kukanimi,
					asiakas.nimi, asiakas.nimitark, asiakas.osoite, asiakas.postino, asiakas.maa, asiakas.postitp,
					asiakas.toim_nimi, asiakas.toim_nimitark, asiakas.toim_osoite, asiakas.toim_postino, asiakas.toim_maa, asiakas.toim_postitp,
					kentta01, kentta02, kentta03, kentta04, if(right(pvmalku,8)='00:00:00','',right(pvmalku,8)) aikaalku, if(right(pvmloppu,8)='00:00:00','',right(pvmloppu,8)) aikaloppu
					FROM kalenteri
					LEFT JOIN kuka ON kalenteri.kuka=kuka.kuka and kalenteri.yhtio=kuka.yhtio
					LEFT JOIN asiakas ON kalenteri.yhtio=asiakas.yhtio and kalenteri.asiakas=asiakas.ytunnus
					WHERE kalenteri.yhtio='$kukarow[yhtio]'
					and pvmalku >= '$vuosi-01-01'
					and pvmalku <= '$viikkoloppu'
					and kalenteri.asiakas  = '$row[ytunnus]'
					and tyyppi in ('kalenteri','memo')
					order by pvmalku desc";
		$bresult = pupe_query($query);

		if (mysql_num_rows($bresult) > 0) {
			$viesti .= "Uusimmat toimenpiteet:\r\n";

			while ($brow=mysql_fetch_assoc($bresult)) {
				$viesti .= "Laatija:    $brow[kukanimi]\r\n";
				$viesti .= "Päivämäärä: $brow[pvmalku]\r\n";
				$viesti .= "Tapa:       $brow[tapa]\r\n";
				$viesti .= "Laatija:    $brow[kentta01]\r\n\r\n";
			}
		}

		//Tehdään raportit
		if (!isset($kka))
			$kka = date("m",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
		if (!isset($vva))
			$vva = date("Y",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
		if (!isset($ppa))
			$ppa = date("d",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));

		if (!isset($kkl))
			$kkl = date("m");
		if (!isset($vvl))
			$vvl = date("Y");
		if (!isset($ppl))
			$ppl = date("d");

		//edellinen vuosi
		$vvaa = $vva - '1';
		$vvll = $vvl - '1';

		$raportti   = "";
		$osraportti = "";

		$query =  "	select
					tilausrivi.osasto,
					tilausrivi.try,
					sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kate,0)) kateedyht,
					sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.rivihinta,0)) myyntiedyht,
					sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kpl,0)) kpledyht,
					sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',tilausrivi.kate,0)) katecuryht,
					sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',tilausrivi.rivihinta,0)) myynticuryht,
					sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',tilausrivi.kpl,0)) kplcuryht
					from tilausrivi use index (yhtio_tyyppi_laskutettuaika),lasku, asiakas
					where tilausrivi.yhtio 	= '$kukarow[yhtio]'
					and tilausrivi.tyyppi 	= 'L'
					and ((tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl') or (tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'))
					and lasku.yhtio     	= tilausrivi.yhtio
					and lasku.tunnus    	= tilausrivi.uusiotunnus
					and asiakas.yhtio   	= tilausrivi.yhtio
					and asiakas.ytunnus 	= lasku.ytunnus
					and asiakas.ytunnus		= '$row[ytunnus]'
					GROUP by tilausrivi.osasto, tilausrivi.try
					ORDER BY tilausrivi.osasto+0, tilausrivi.try+0";
		$yhtresulta = pupe_query($query);

		if (mysql_num_rows($yhtresulta) != 0) {

			//tehdään ensin summamuuttujat
			while ($yhtrow = mysql_fetch_assoc ($yhtresulta)) {

				if ($yhtrow["osasto"] != $edosasto) {
					$i = $yhtrow["osasto"];

					$osastomyynticuryht[$i] = 0;
					$osastokatecuryht[$i]   = 0;

					$osastomyyntiedyht[$i] = 0;
					$osastokateedyht[$i]   = 0;

					$osastokplcuryht[$i]  = 0;
					$osastokpledyht[$i]   = 0;
				}

				$osastomyynticuryht[$i] += $yhtrow['myynticuryht'];
				$osastomyyntiedyht[$i] += $yhtrow['myyntiedyht'];

				$osastokplcuryht[$i]  += $yhtrow['kplcuryht'];
				$osastokpledyht[$i]   += $yhtrow['kpledyht'];

				$kaikkimyynticuryht += $yhtrow['myynticuryht'];
				$kaikkimyyntiedyht  += $yhtrow['myyntiedyht'];

				$kaikkikplcuryht  += $yhtrow['kplcuryht'];
				$kaikkikpledyht   += $yhtrow['kpledyht'];

				$edosasto = $yhtrow["osasto"];
			}

			//kelataan alkuun
			mysql_data_seek($yhtresulta,0);

			$raportti .= "Tuoteryhmä\tMyynti\tIndeksi Myynti\tKpl\tIndeksi Kpl\r\n";
			$osraportti .= "Osasto\tMyynti\tIndeksi Myynti\tKpl\tIndeksi Kpl\r\n";

			//ja tulostetaan kaikki rivit ruudulle
			while ($yhtrow = mysql_fetch_assoc ($yhtresulta)) {

				//tässä tulee yhteensärivi
				if ($yhtrow["osasto"] != $edosasto) {
					$i = $yhtrow["osasto"];

					if (($osastomyynticuryht[$i] != 0) and ($osastomyyntiedyht[$i] != 0))
						$indlvtot = sprintf('%.2f',$osastomyynticuryht[$i] / $osastomyyntiedyht[$i]);
					else $indlvtot = "n/a";

					if (($osastokplcuryht[$i] != 0) and ($osastokpledyht[$i] != 0))
						$indkpltot = sprintf('%.2f',$osastokplcuryht[$i] / $osastokpledyht[$i]);
					else $indkpltot = "n/a";

					// tehdään avainsana query
					$sresult = t_avainsana("OSASTO", "", "and avainsana.selite ='$yhtrow[osasto]'");
					$srow = mysql_fetch_assoc($sresult);

					$raportti .= "$yhtrow[osasto] $srow[selitetark] yhteensä\t";
					$raportti .= str_replace(".",",",$osastomyynticuryht[$i])."\t";
					$raportti .= str_replace(".",",",$indlvtot)."\t";
					$raportti .= str_replace(".",",",$osastokplcuryht[$i])."\t";
					$raportti .= str_replace(".",",",$indkpltot)."\t";
					$raportti .= "\r\n";


					$osraportti .= "$yhtrow[osasto] $srow[selitetark] yhteensä\t";
					$osraportti .= str_replace(".",",",$osastomyynticuryht[$i])."\t";
					$osraportti .= str_replace(".",",",$indlvtot)."\t";
					$osraportti .= str_replace(".",",",$osastokplcuryht[$i])."\t";
					$osraportti .= str_replace(".",",",$indkpltot)."\t";
					$osraportti .= "\r\n";

				}

				if (($yhtrow['myynticuryht'] != 0) and ($yhtrow['myyntiedyht'] != 0))
					$indlv = sprintf('%.2f',$yhtrow['myynticuryht'] / $yhtrow['myyntiedyht']);
				else $indlv = "n/a";

				if (($yhtrow['kplcuryht'] != 0) and ($yhtrow['kpledyht'] != 0))
					$indkpl = sprintf('%.2f',$yhtrow['kplcuryht'] / $yhtrow['kpledyht']);
				else $indkpl = "n/a";

				// tehdään avainsana query
				$sresult = t_avainsana("TRY", "", "and avainsana.selite ='$yhtrow[try]'");
				$srow = mysql_fetch_assoc($sresult);

				$raportti .= "Tuoteryhmä $yhtrow[try] $srow[selitetark]\t";
				$raportti .= str_replace(".",",",$yhtrow["myynticuryht"])."\t";
				$raportti .= str_replace(".",",",$indlv)."\t";
				$raportti .= str_replace(".",",",$yhtrow["kplcuryht"])."\t";
				$raportti .= str_replace(".",",",$indkpl)."\t";
				$raportti .= "\r\n";

				$edosasto = $yhtrow["osasto"];
			}
		}

		$bound = uniqid(time()."_") ;

		$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\r\n";
		$header .= "MIME-Version: 1.0\r\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

		$content = "--$bound\r\n" ;

		$content .= "Content-Type: text/plain; charset=\"iso-8859-1\"\r\n";
		$content .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
		$content .= "$viesti\r\n\r\n";

		if ($osraportti != '') {
			$content .= "--$bound\r\n" ;

			$content .= "Content-Type: application/vnd.ms-excel\r\n" ;
			$content .= "Content-Transfer-Encoding: base64\r\n" ;
			$content .= "Content-Disposition: attachment; filename=\"osastoseuranta.xls\"\r\n\r\n";
			$content .= chunk_split(base64_encode($osraportti));
			$content .= "\r\n";
		}

		if ($raportti != '') {
			$content .= "--$bound\r\n" ;

			$content .= "Content-Type: application/vnd.ms-excel\r\n" ;
			$content .= "Content-Transfer-Encoding: base64\r\n" ;
			$content .= "Content-Disposition: attachment; filename=\"tuoteryhmäseuranta.xls\"\r\n\r\n";
			$content .= chunk_split(base64_encode($raportti));
			$content .= "\r\n";
		}

		$to = $kukarow['eposti'];

		mail($to, mb_encode_mimeheader(t("Asiakkaan tietopaketti")." $row[ytunnus] $row[nimi]", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
	}
?>