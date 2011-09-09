<?php

	if (!function_exists("editilaus_out_futur")) {
		function editilaus_out_futur($params) {
			global $kukarow, $yhtiorow, $ftphost_unikko, $ftpuser_unikko, $ftppass_unikko, $ftppath_unikko;

			extract($params);

			$summa = $toim == 'REKLAMAATIO' ? $laskurow['summa'] : '';

		   	//tilauksen otsikko
		   	$edi_order  = "*IS from:721111720-1 to:IKH,ORDERS*id:{$laskurow['tunnus']} version:AFP-1.0 *MS\n";
		   	$edi_order .= "*MS {$laskurow['tunnus']}\n";
		   	$edi_order .= "*RS OSTOTIL\n";

		   	$edi_order .= "OSTOTIL.OT_NRO:{$laskurow['tunnus']}\n";
			// 4 = Örum
		   	$edi_order .= "OSTOTIL.OT_TOIMITTAJANRO:4\n";
			$edi_order .= "OSTOTIL.OT_TILAUSTYYPPI:{$tilaustyyppi}\n";
			$edi_order .= "OSTOTIL.OT_TILAUSAIKA:\n";
			$edi_order .= "OSTOTIL.OT_KASITTELIJA:\n";
			$edi_order .= "OSTOTIL.OT_TOIMITUSAIKA:\n";
			$edi_order .= "OSTOTIL.OT_TOIMITUSTAPA:{$toimitustapa}\n";
			$edi_order .= "OSTOTIL.OT_TOIMITUSEHTO:{$laskurow['toimitusehto']}\n";
			$edi_order .= "OSTOTIL.OT_MAKSUEHTO:{$laskurow['maksuehto']}\n";
			$edi_order .= "OSTOTIL.OT_VIITTEEMME:{$laskurow['viesti']}\n";

			// Onko asiakkaan takana hyllypaikka jossa tavarat säilytetään
			$query = "	SELECT avainsana
						FROM asiakkaan_avainsanat
						WHERE yhtio = '$kukarow[yhtio]'
						AND liitostunnus = '$laskurow[liitostunnus]'
						AND laji = 'JMALMIHYLLY'";
			$aares = pupe_query($query);

			if (mysql_num_rows($aares) > 0) {
				$aarow = mysql_fetch_assoc($aares);
				$laskurow['sisviesti2'] .= ";;".$aarow["avainsana"];
			}

			$edi_order .= "OSTOTIL.OT_VIITTEENNE:{$laskurow['sisviesti2']}\n";
			$edi_order .= "OSTOTIL.OT_SUMMA:{$summa}\n";
			$edi_order .= "OSTOTIL.OT_VALUUTTAKOODI:{$laskurow['valkoodi']}\n";
			$edi_order .= "OSTOTIL.OT_KLAUSUULI1:\n";
			$edi_order .= "OSTOTIL.OT_KLAUSUULI2:\n";
			$edi_order .= "OSTOTIL.OT_KULJETUSOHJE:\n";
			$edi_order .= "OSTOTIL.OT_LAHETYSTAPA:\n";
			$edi_order .= "OSTOTIL.OT_VAHVISTUS_FAKSILLA:\n";
			$edi_order .= "OSTOTIL.OT_FAKSI:\n";

			$lask_yritys = trim($lisatiedot['laskutus_nimi'].' '.$lisatiedot['laskutus_nimitark']);
			$lask_osoite = $lisatiedot['laskutus_osoite'];
			$lask_postitp = $lisatiedot['laskutus_postitp'];
			$lask_postino = $lisatiedot['laskutus_postino'];

			$edi_order .= "OSTOTIL.OT_ASIAKASNRO:{$asiakasrow['herminator']}\n";
			$edi_order .= "OSTOTIL.OT_YRITYS:{$lask_yritys}\n";
			$edi_order .= "OSTOTIL.OT_KATUOSOITE:{$lask_osoite}\n";
			$edi_order .= "OSTOTIL.OT_POSTITOIMIPAIKKA:{$lask_postitp}\n";
			$edi_order .= "OSTOTIL.OT_POSTINRO:{$lask_postino}\n";
			$edi_order .= "OSTOTIL.OT_YHTEYSHENKILO:\n";
			$edi_order .= "OSTOTIL.OT_YHTEYSHENKILONPUH:\n";
			$edi_order .= "OSTOTIL.OT_YHTEYSHENKILONFAX:\n";
			$edi_order .= "OSTOTIL.OT_MYYNTI_YRITYS:{$yhtiorow['nimi']}\n";
			$edi_order .= "OSTOTIL.OT_MYYNTI_KATUOSOITE:{$yhtiorow['osoite']}\n";
			$edi_order .= "OSTOTIL.OT_MYYNTI_POSTITOIMIPAIKKA:{$yhtiorow['postitp']}\n";
			$edi_order .= "OSTOTIL.OT_MYYNTI_POSTINRO:{$yhtiorow['postino']}\n";
			$edi_order .= "OSTOTIL.OT_MYYNTI_MAAKOODI:{$yhtiorow['maa']}\n";
			$edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILO:\n";
			$edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONPUH:\n";
			$edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONFAX:\n";

			$toim_yritys 	= substr(trim($laskurow['toim_nimi'].' '.$laskurow['toim_nimitark']), 0, 30);
			$toim_osoite 	= substr(trim($laskurow['toim_osoite']), 0, 25);
			$toim_postitp	= substr(trim($laskurow['toim_postitp']), 0, 25);
			$toim_postino	= trim($laskurow['toim_postino']);
			$toim_maa 	 	= trim($laskurow['toim_maa']);

			if ($toim_maa != '' and strtoupper($toim_maa) != strtoupper($yhtiorow["maa"])) {
				$toim_yritys  = '';
				$toim_osoite  = '';
				$toim_postitp = '';
				$toim_postino = '';
				$toim_maa	  = '';
			}
			// jos toimitusosoite eroaa asiakkaan toimitusosoitteesta (tai jos toimitusosoite on tyhjä niin asiakkaan osoitteesta), niin silloin se lisätään aineistoon
			elseif (trim($asiakasrow['toim_osoite']) != '') {
				// Kauttalaskutusasiakkaille ei tyhjennetä
				if ($asiakasrow["osasto"] != "6" and $toim_osoite == substr(trim($asiakasrow['toim_osoite']), 0, 25) and $toim_postitp == substr(trim($asiakasrow['toim_postitp']), 0, 25) and $toim_postino == trim($asiakasrow['toim_postino'])) {
					$toim_yritys 	= '';
					$toim_osoite 	= '';
					$toim_postitp 	= '';
					$toim_postino 	= '';
					$toim_maa 		= '';
				}
			}
			else {
				if ($toim_osoite == substr(trim($asiakasrow['osoite']), 0, 25) and $toim_postitp == substr(trim($asiakasrow['postitp']), 0, 25) and $toim_postino == trim($asiakasrow['postino'])) {
					$toim_yritys 	= '';
					$toim_osoite 	= '';
					$toim_postitp	= '';
					$toim_postino 	= '';
					$toim_maa 		= '';
				}
			}

			$edi_order .= "OSTOTIL.OT_TOIMITUS_YRITYS:{$toim_yritys}\n";
			#$edi_order .= "OSTOTIL.OT_TOIMITUS_NIMI:\n";
			$edi_order .= "OSTOTIL.OT_TOIMITUS_KATUOSOITE:{$toim_osoite}\n";
			$edi_order .= "OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA:{$toim_postitp}\n";
			$edi_order .= "OSTOTIL.OT_TOIMITUS_POSTINRO:{$toim_postino}\n";
			$edi_order .= "OSTOTIL.OT_TOIMITUS_MAAKOODI:{$toim_maa}\n";
		   	$edi_order .= "*RE OSTOTIL\n";

		   	$i = 1;

			$query = "	SELECT tilausrivi.*, tilausrivin_lisatiedot.tilausrivilinkki
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.panttitili = '' AND tuote.ei_saldoa = '')
						JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
							AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
						AND tilausrivi.var != 'U'";
			$tilausrivi_res = pupe_query($query);

			if (mysql_num_rows($tilausrivi_res) == 0) return false;

			while ($tilausrivi_row = mysql_fetch_assoc($tilausrivi_res)) {

				if ($tilausrivi_row['tilausrivilinkki'] > 0) {
					$query = "SELECT tyyppi FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilausrivi_row['tilausrivilinkki']}'";
					$suoratoimitus_chk_res = pupe_query($query);
					$suoratoimitus_chk_row = mysql_fetch_assoc($suoratoimitus_chk_res);

					if ($suoratoimitus_chk_row['tyyppi'] == 'O') {
						continue;
					}
				}

				$kpl = round(($tilausrivi_row['jt'] + $tilausrivi_row['varattu']), 0);

				if ($tilausrivi_row['var'] == 'P') {
					$kpl = round($tilausrivi_row['tilkpl'] * 1, 0);
				}

				// HUOM! Reklamaatioissa laitetaan nettohyvitysyksikköhinta
				$hinta = $tilausrivi_row['hinta'] * generoi_alekentta_php($tilausrivi_row, 'M', 'kerto');

				// nettorivihinta
				$rivihinta = $kpl * $hinta;

				$frivinro = substr($tilausrivi_row['tunnus'], -4);

				// Nolla räjähtää, joten laitetaan sinne tällanen
				if ($frivinro == 0) {
					$frivinro = 5555;
				}

	   			$edi_order .= "*RS OSTOTILRIV {$i}\n";
	    		$edi_order .= "OSTOTILRIV.OTR_NRO:{$laskurow['tunnus']}\n";
	    		$edi_order .= "OSTOTILRIV.OTR_RIVINRO:$frivinro\n";
	    		$edi_order .= "OSTOTILRIV.OTR_TOIMITTAJANRO:\n";
	    		$edi_order .= "OSTOTILRIV.OTR_TUOTEKOODI:{$tilausrivi_row['tuoteno']}\n";
	    		$edi_order .= "OSTOTILRIV.OTR_NIMI:{$tilausrivi_row['nimitys']}\n";

				$edi_order .= "OSTOTILRIV.OTR_TILATTUMAARA:{$kpl}\n";

	    		$edi_order .= "OSTOTILRIV.OTR_RIVISUMMA:{$rivihinta}\n";

				if ($toim == 'REKLAMAATIO') {
		    		$edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:{$hinta}\n";

					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
		    			$edi_order .= "OSTOTILRIV.OTR_ALENNUS_{$alepostfix}:0\n";
					}
				}
				else {

		    		$edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:{$tilausrivi_row['hinta']}\n";

					$ale = generoi_alekentta_php($tilausrivi_row, 'M', 'erikseen');
					for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
		    			$edi_order .= "OSTOTILRIV.OTR_ALENNUS_{$alepostfix}:".$ale["ale".$alepostfix]."\n";
					}
				}

				$edi_order .= "OSTOTILRIV.OTR_VEROKANTA:{$tilausrivi_row['alv']}\n";

				#$edi_order .= "OSTOTILRIV.OTR_VIITE:{$tilausrivi_row['kommentti']}\n";
	    		$edi_order .= "OSTOTILRIV.OTR_OSATOIMITUSKIELTO:\n";
	    		$edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUSKIELTO:\n";
	    		$edi_order .= "OSTOTILRIV.OTR_YKSIKKO:{$tilausrivi_row['yksikko']}\n";

				if ($tilausrivi_row['var'] == 'J') {
	    			$edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUS:J\n";
	    		}

	    		$edi_order .= "*RE  OSTOTILRIV $i\n";
	    		$i++;

				// var J voi olla vain jos tilaustyyppi ei ole 7
				// var J rivi laitetaan unikkoon ja päivitetään rivin var H:ksi ettei se jää roikkumaan JT-selaukseen
				if ($tilausrivi_row['var'] == 'J') {
					$query = "UPDATE tilausrivi SET var = 'H' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilausrivi_row['tunnus']}'";
					$update_res = pupe_query($query);
				}
			}

			if ($i == 1) {
				return false;
			}

		   	$edi_order .= "*ME\n";
		   	$edi_order .= "*IE\n";

			$edipolku = "/tmp";

			$filename = $edipolku."/{$laskurow['tunnus']}.dat";
			file_put_contents($filename,$edi_order);

			$ftphost = $ftphost_unikko;
			$ftpuser = $ftpuser_unikko;
			$ftppass = $ftppass_unikko;
			$ftppath = $ftppath_unikko;

			$ftpfile = $unlink_filename = $filename;

			if (file_exists("ftp-send.inc")) {
				require("ftp-send.inc");
			}
			elseif (file_exists("../inc/ftp-send.inc")) {
				require("../inc/ftp-send.inc");
			}
			else {
				require("inc/ftp-send.inc");
			}

			// unlink($unlink_filename);

			return $palautus;
		}
	}
