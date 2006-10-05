#!/usr/bin/php
<?php

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

	// pitää siirtyä www roottiin
	chdir("/var/www/html/pupesoft");

	// määritellään polut
    $laskut    = "/home/verkkolaskut";
    $oklaskut  = "/home/verkkolaskut/ok";
    $errlaskut = "/home/verkkolaskut/error";
    //$laskut    = "/home/jarmo/verkkolaskut";
    //$oklaskut  = "/home/jarmo/verkkolaskut/ok";
    //$errlaskut = "/home/jarmo/verkkolaskut/error";
	require ("inc/connect.inc");

	if ($handle = opendir($laskut)) {

		while (false !== ($file = readdir($handle))) {

			if (is_file($laskut."/".$file)) {

				//echo "Tiedosto on: $file\n";
				$xmlstr = file_get_contents($laskut."/".$file);
				$xml    = simplexml_load_string($xmlstr);

			    $xyhtio 				= $xml->xpath('Group2/NAD[@e3035="IV"]/@eC082.3039');
				$xverkkotunnus_vas 		= $xml->xpath('Group2/NAD[@e3035="MR"]/@eC082.3039');
			    $xlaskun_tyyppi 		= $xml->xpath('BGM/@eC002.1001');
			    $xlaskunro 				= $xml->xpath('BGM/@e1004');
			    $xlaskun_ebid 			= $xml->xpath('Group1/RFF[@eC506.1153="ZEB"]/@eC506.1154');
			    $xlaskun_paiva			= $xml->xpath('DTM[@eC507.2005="3"]/@eC507.2380');
			    $xlaskun_erapaiva		= $xml->xpath('Group8[PAT/@e4279="1"]/DTM[@eC507.2005="13"]/@eC507.2380');
			    $xlaskuttajan_ovt		= $xml->xpath('Group2/NAD[@e3035="II"]/@eC082.3039');
			    $xlaskuttaja			= $xml->xpath('Group2/NAD[@e3035="II"]/@eC080.3036.1');
			    $xlaskun_pankkiviite	= $xml->xpath('Group1/RFF[@eC506.1153="PQ"]/@eC506.1154');
			    $xlaskun_summa_eur		= $xml->xpath('Group48/MOA[@eC516.5025="9" and @eC516.6345="EUR"]/@eC516.5004');
			    $xlaskun_pankkiviite	= $xml->xpath('Group1/RFF[@eC506.1153="PQ"]/@eC506.1154');
			    $xlaskun_asiakastunnus	= $xml->xpath('Group2[NAD/@e3035="IV"]/Group3/RFF/@eC506.1154');

				if ((float) $xlaskun_summa_eur[0] == 0) {
					$xlaskun_summa_eur = $xml->xpath('Group48/MOA[@eC516.5025="9" and @eC516.6345=""]/@eC516.5004');
				}

				if ((float) $xlaskun_summa_eur[0] == 0) {
					$xlaskun_summa_eur = $xml->xpath('Group48/MOA[@eC516.5025="9"]/@eC516.5004');
				}
				
				//echo "Laskun perustiedot parseroitu!\n";
				$tuotetiedot = $xml->xpath('Group25');

				//echo "Tuotetiedot löydetty! Niitä on " . sizeof($tuotetiedot) . "\n";
				$i=0;
				$xtuoteno	= array();
				$tuoteno	= array();
				$xrsumma	= array();
				$rsumma		= array();
				$xvat		= array();
				$vat 		= array();
				$xrinfo 	= array();
				$info 		= array();
				$xrkpl 		= array();
				$rkpl 		= array();
				$xrivino 	= array();
				$rrivino	= array();

				if (sizeof($tuotetiedot) > 0) {

					foreach ($tuotetiedot as $tuotetieto) {
						$xtuoteno[$i]	= $tuotetieto->xpath('LIN/@eC212.7140');
						$tuoteno[$i]	= $xtuoteno[$i][0];
						// veroton rivihinta
						$xrsumma[$i]	= $tuotetieto->xpath('Group26/MOA[@eC516.5025="203"]/@eC516.5004');
						$rsumma[$i]		= $xrsumma[$i][0];
						$xvat[$i]		= $tuotetieto->xpath('Group33/TAX[@eC241.5153="VAT"]/@eC243.5278');
						if (is_array($xvat[$i])) $vat[$i] = $xvat[$i][0]; // Näin voimme pohtia oliko täällä tuo segmentti
						$xrinfo[$i] 	= $tuotetieto-> xpath('IMD/@eC273.7008.1');
						$info[$i] 		= $xrinfo[$i][0];
						// laskutettu määrä
						$xrkpl[$i] 		= $tuotetieto-> xpath('QTY[@eC186.6063="12"]/@eC186.6060');
						$rkpl[$i] 		= $xrkpl[$i][0];
						$xrivino[$i] 	= $tuotetieto->xpath('Group29/RFF[@eC506.1153="CR"]/@eC506.1154');
						$rrivino[$i]	= $xrivino[$i][0];


						// jotkut toimittajat lähettää laskutetun summan 47 segmentissä eikä 12 niinku kuuluis
						if ((float) $rkpl[$i] == 0) {
							$xrkpl[$i] = $tuotetieto-> xpath('QTY[@eC186.6063="47"]/@eC186.6060');
							$rkpl[$i]  = $xrkpl[$i][0];
						}
						//echo "Tuoteno: $tuoteno[$i] '$info[$i]' Kpl: $rkpl[$i] ";
						//echo "Summa: $rsumma[$i] ($vat[$i])\n";
						$i++;
/*	Ei oteta vielä käyttöön					
						//Jos tuotetiedot ovatkin erittelyissä (Schenker)
						$tarkennetiedot = $xml->xpath('Group25/Group38');
						echo "Tarkenteet löydetty! Niitä on " . sizeof($tarkennetiedot) . "\n";
						if (sizeof($tarkennetiedot) > 0) {
							foreach ($tarkennetiedot as $tarkennetieto) {
								$xtuoteno[$i]	= $tarkennetieto->xpath('ALC/@eC214.7161');
								$tuoteno[$i]	= $xtuoteno[$i][0];
								// veroton rivihinta
								$xrsumma[$i]	= $tarkennetieto->xpath('Group41/MOA/@eC516.5004');
								$rsumma[$i]		= $xrsumma[$i][0];
								$xvat[$i]		= $tarkennetieto->xpath('Group43/TAX[@eC241.5153="VAT"]/@eC243.5278');
								if (is_array($xvat[$i])) $vat[$i] = $xvat[$i][0]; // Näin voimme pohtia oliko täällä tuo segmentti
								$xrinfo[$i] 	= $tarkennetieto-> xpath('ALC/@eC214.7160.1');
								$info[$i] 		= $xrinfo[$i][0];
								$rkpl=0;					
								// laskutettu määrä
								$xrivino[$i] 	= $tarkennetieto->xpath('Group29/RFF[@eC506.1153="CR"]/@eC506.1154');
								$rrivino[$i]	= $xrivino[$i][0];
								echo "Tarkenne: $tuoteno[$i] '$info[$i]' Kpl: $rkpl[$i] ";
								echo "Summa: $rsumma[$i] ($vat[$i])\n";
								$i++;
							}
						}
*/
					}
				}

				$yhtio 					= $xyhtio[0];
				$verkkotunnus_vas 		= $xverkkotunnus_vas[0];
			    $laskun_tyyppi 			= $xlaskun_tyyppi[0];
			    $laskun_numero 			= $xlaskunro[0];
			    $laskun_ebid 			= $xlaskun_ebid[0];
			    $laskun_paiva			= $xlaskun_paiva[0];
			    $laskun_erapaiva		= $xlaskun_erapaiva[0];
			    $laskuttajan_ovt		= $xlaskuttajan_ovt[0];
			    $laskuttajan_nimi		= $xlaskuttaja[0];
			    $laskun_pankkiviite		= $xlaskun_pankkiviite[0];
			    $laskun_summa_eur		= (float) $xlaskun_summa_eur[0];
			    $laskun_pankkiviite 	= $xlaskun_pankkiviite[0];
			    $laskun_asiakastunnus	= $xlaskun_asiakastunnus[0];
			    $kutsuja				= 'php';
				
				$lisavat=array();
				$xlisavat=array();
				$i=0;
					
				$vattiedot = $xml->xpath('Group50');
				if (sizeof($vattiedot) > 0) {
					foreach ($vattiedot as $vattieto) {
						$xlisavat[$i]	= $vattieto->xpath('TAX/@eC243.5278');
						$lisavat[$i]	= $xlisavat[$i][0];
						//echo "ALV on $lisavat[$i]!\n";
						$i++;
					}
				}
				
			    // Tässä kutusutaan itse laskun tekoa!
			    require ("verkkolasku-in.php");

			    if ($toim != 'E') {
			    	$sub = "$yhtiorow[nimi] vastaanotti laskun $verkapunimi:lta";
			    	$meili = "Verkkkolasku $verkapunimi yritykselle $yhtiorow[nimi]\nTiedosto: $file\nLaskunro: $laskun_numero\nPäivä: $laskun_paiva\nSumma: $laskun_summa_eur\nViite: $laskun_pankkiviite\nEnsimmäinen hyväksyjä: $hyvaksyja_nyt\n$laskuvirhe\n";
			    	system("mv " . $laskut . "/" . $file . " " . $oklaskut . "/" . $file);
			    }
			    else {
					$sub = "Verkkolaskun vastaanotto epäonnistui";
					$meili = "Verkkkolasku $verkapunimi yritykselle $yhtiorow[nimi]\nTiedosto: $file\nLaskunro: $laskun_numero\nPäivä: $laskun_paiva\nSumma: $laskun_summa_eur\nViite: $laskun_pankkiviite\nEpäonnistumisen syy(t): $laskuvirhe\n";
					system("mv " . $laskut . "/" . $file . " " . $errlaskut . "/" . $file);
				}
				//echo $meili;	
			    $tulos = mail($yhtiorow['admin_email'], $sub, $meili, "From: <mailer@pupesoft.com>\n");

				if ($tulos === FALSE) echo t("Sähköpostin lähetys epäonnistui").": $yhtiorow[admin_email]<br>";
				//echo "\nFrom: $yhtiorow[admin_email]\n$sub\n$meili\n";
			}
		}
	}

?>
