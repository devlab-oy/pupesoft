<?php

	if (!function_exists("editilaus_out_futur")) {
		function editilaus_out_futur($params) {
			global $kukarow, $yhtiorow;

			extract($params);

			$summa = $toim != '' ? $laskurow['summa'] : '';

		   	//tilauksen otsikko
		   	$edi_order  = "*IS from:721111720-1 to:IKH,ORDERS*id:{$laskurow['tunnus']} version:AFP-1.0 *MS\n";
		   	$edi_order .= "*MS {$laskurow['tunnus']}\n";
		   	$edi_order .= "*RS OSTOTIL\n";

			// int(11), otetaan 6 viimeisintä numeroa
		   	$edi_order .= "OSTOTIL.OT_NRO:".substr($laskurow['tunnus'], -6)."\n";
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
			$edi_order .= "OSTOTIL.OT_VIITTEENNE:{$laskurow['sisviesti2']}\n";
			$edi_order .= "OSTOTIL.OT_SUMMA:{$summa}\n";
			$edi_order .= "OSTOTIL.OT_VALUUTTAKOODI:{$laskurow['valkoodi']}\n";
			$edi_order .= "OSTOTIL.OT_KLAUSUULI1:\n";
			$edi_order .= "OSTOTIL.OT_KLAUSUULI2:\n";
			$edi_order .= "OSTOTIL.OT_KULJETUSOHJE:\n";
			$edi_order .= "OSTOTIL.OT_LAHETYSTAPA:\n";
			$edi_order .= "OSTOTIL.OT_VAHVISTUS_FAKSILLA:\n";
			$edi_order .= "OSTOTIL.OT_FAKSI:\n";

			$edi_order .= "OSTOTIL.OT_ASIAKASNRO:{$asiakasnro}\n";
			$edi_order .= "OSTOTIL.OT_YRITYS:".trim($lisatiedot['laskutus_nimi'].' '.$lisatiedot['laskutus_nimitark'])."\n";
			$edi_order .= "OSTOTIL.OT_KATUOSOITE:{$lisatiedot['laskutus_osoite']}\n";
			$edi_order .= "OSTOTIL.OT_POSTITOIMIPAIKKA:{$lisatiedot['laskutus_postitp']}\n";
			$edi_order .= "OSTOTIL.OT_POSTINRO:{$lisatiedot['laskutus_postino']}\n";
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
			$edi_order .= "OSTOTIL.OT_TOIMITUS_YRITYS:".substr(trim($laskurow['toim_nimi'].' '.$laskurow['toim_nimitark']), 0, 30)."\n";
			$edi_order .= "OSTOTIL.OT_TOIMITUS_NIMI:\n";
			$edi_order .= "OSTOTIL.OT_TOIMITUS_KATUOSOITE:".substr(trim($laskurow['toim_osoite']), 0, 25)."\n";
			$edi_order .= "OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA:".substr(trim($laskurow['toim_postitp']), 0, 25)."\n";
			$edi_order .= "OSTOTIL.OT_TOIMITUS_POSTINRO:{$laskurow['toim_postino']}\n";
			$edi_order .= "OSTOTIL.OT_TOIMITUS_MAAKOODI:{$laskurow['toim_maa']}\n";
		   	$edi_order .= "*RE OSTOTIL\n";

		   	$i = 1;

			$query = "SELECT * FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$laskurow['tunnus']}'";
			$tilausrivi_res = pupe_query($query);

			while ($tilausrivi_row = mysql_fetch_assoc($tilausrivi_res)) {

				if ($yhtiorow["varaako_jt_saldoa"] == "") {
					$kpl = round($tilausrivi_row['jt'] * 1, 0);
				}
				else {
					$kpl = round($tilausrivi_row['varattu'] * 1, 0);
				}

				if ($tilausrivi_row['var'] == 'P') {
					$kpl = 0;
				}

	   			$edi_order .= "*RS OSTOTILRIV {$i}\n";
	    		$edi_order .= "OSTOTILRIV.OTR_NRO:".substr($laskurow['tunnus'], -6)."\n";
	    		$edi_order .= "OSTOTILRIV.OTR_RIVINRO:".substr($tilausrivi_row['tunnus'], -6)."\n";
	    		$edi_order .= "OSTOTILRIV.OTR_TOIMITTAJANRO:\n";
	    		$edi_order .= "OSTOTILRIV.OTR_TUOTEKOODI:{$tilausrivi_row['tuoteno']}\n";
	    		$edi_order .= "OSTOTILRIV.OTR_NIMI:{$tilausrivi_row['nimitys']}\n";

				$edi_order .= "OSTOTILRIV.OTR_TILATTUMAARA:{$kpl}\n";

	    		$edi_order .= "OSTOTILRIV.OTR_RIVISUMMA:{$tilausrivi_row['rivihinta']}\n";
	    		$edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:{$tilausrivi_row['hinta']}\n";

				if ($toim == '') {
		    		// $edi_order .= "OSTOTILRIV.OTR_ALENNUS:".generoi_alekentta_php($tilausrivi_row, 'M')."\n";
		    		$edi_order .= "OSTOTILRIV.OTR_ALENNUS:0\n";
				}

				$edi_order .= "OSTOTILRIV.OTR_VEROKANTA:{$tilausrivi_row['alv']}\n";

				$edi_order .= "OSTOTILRIV.OTR_VIITE:{$tilausrivi_row['kommentti']}\n";
	    		$edi_order .= "OSTOTILRIV.OTR_OSATOIMITUSKIELTO:\n";
	    		$edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUSKIELTO:\n";
	    		$edi_order .= "OSTOTILRIV.OTR_YKSIKKO:{$tilausrivi_row['yksikko']}\n";

				if ($tilausrivi_row['var'] == 'J') {
	    			$edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUS:J\n";
	    		}

	    		$edi_order .= "*RE  OSTOTILRIV $i\n";
	    		$i++;
			}

		   	$edi_order .= "*ME\n";
		   	$edi_order .= "*IE";

		   	$edi_order = iconv("UTF-8", "ISO-8859-1", $edi_order);

			$edipolku = "/tmp";

			$filename = $edipolku."/editilaus_out_futur".date("Ymd")."-".md5(uniqid(rand(),true)).".txt";
			file_put_contents($filename,$edi_order);

			$ftphost = $ftphost_unikko;
			$ftpuser = $ftpuser_unikko;
			$ftppass = $ftppass_unikko;
			$ftppath = $ftppath_unikko;
			
			$yhtiorow['alert_email'] = 'sami@devlab.fi';
			$yhtiorow['postittaja_email'] = 'sami@devlab.fi';
			
			$ftpfile = $unlink_filename = $filename;
			
			require('inc/ftp-send.inc');
			
			unlink($unlink_filename);

			return $palautus;
		}
	}
