<?php

	//	Haetaan tilausrivit
	$query = "	SELECT tilausrivi.tuoteno, tilausrivi.nimitys, asiakkaan_positio.tunnus asiakkaan_positio, asiakkaan_positio.positio, kaavaripidin.valmistaja, kaavaripidin.tyyppi
				FROM tilausrivi
				JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = ''
				LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus
				LEFT JOIN asiakkaan_positio ON asiakkaan_positio.yhtio = tilausrivin_lisatiedot.yhtio and asiakkaan_positio.tunnus = tilausrivin_lisatiedot.asiakkaan_positio
				LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio = asiakkaan_positio.yhtio and asiakkaan_kohde.tunnus = asiakkaan_positio.asiakkaan_kohde
				LEFT JOIN kaavaripidin ON kaavaripidin.yhtio = asiakkaan_positio.yhtio and kaavaripidin.tunnus = asiakkaan_positio.kaavaripidin
				WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.otunnus = '$laskurow[tunnus]' and tilausrivi.tyyppi != 'D'";
	$result = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result)>0) {
		
		//	Aloitetaan aineisto
		if(!function_exists("tulosta_tilaustuotettarra")) {
			
			require_once('pdflib/pupepdf.class.php');
			
			if (!function_exists('alku')) {
				function alku ($pdf, $row) {
					global $yhtiorow, $laskurow, $kukarow, $fonts, $tid, $h;

					if(!is_object($pdf)) {
						//	Aloitellaan piirtelyä
						$pdf = new pdf;
						$pdf->enable('template');

						$pdf->set_default('margin-top', 	mm_pt(5));
						$pdf->set_default('margin-bottom', 	mm_pt(5));
						$pdf->set_default('margin-left', 	mm_pt(5));
						$pdf->set_default('margin-right',	mm_pt(5));

						$fonts["norm"]['height']	= 10;
						$fonts["norm"]['font']		= "Helvetica";

						$fonts["BIG"]['height']		= 14;
						$fonts["BIG"]['font']		= "Helvetica";

						//	Asetetaan localet
						$pdf->setLocales($laskurow["maa"], $laskurow["valkoodi"]);

					}

					//	Tehdään uusi sivu
					$pdf->addPage("10.1x3.8cm");

					$h=32;
					
					return $pdf;
				}
			}
		}
		
		//	kasataan aineisto
		while($row = mysql_fetch_array($result)) {
			$pdf = alku($pdf, $row);
			
			$s=$pdf->teeSarakkeet(array(20));

			$pdf->write($h, 1, 0, 0, $yhtiorow[nimi], "BIG", "L", "B");
			$h-=$pdf->ln(2);
			
			$pdf->write($h, 1, 0, $s[0], "Position", "norm", "L", "B");
			$pdf->write($h, 1, $s[0], 0, "$row[asiakkaan_positio] - $row[positio]", "norm", "L", "");
			$h-=$pdf->ln(1.25);

			$pdf->write($h, 1, 0, $s[0], "Holder", "norm", "L", "B");
			$pdf->write($h, 1, $s[0], 0, "$row[tyyppi] - $row[valmistaja]", "norm", "L", "");
			$h-=$pdf->ln(1.25);

			$pdf->write($h, 1, 0, $s[0], "Product", "norm", "L", "B");
			$pdf->write($h, 1, $s[0], 0, "$row[tuoteno] - $row[nimitys]", "norm", "L", "");
			$h-=$pdf->ln(1.25);

			$pdf->write($h, 1, 0, $s[0], "Order", "norm", "L", "B");
			$pdf->write($h, 1, $s[0], 0, "$laskurow[tunnusnippu] - $laskurow[viesti]", "norm", "L", "");
			
		}
		
		//	Lopetetaan aineisto ja laitetaan aineisto ulos
		$pdf_tilaustuotettarrafilenimi = "/tmp/$yhtiorow[yhtio]-Tilaustuotetarrat_".md5(uniqid(mt_rand(), true)).".pdf";
		$fh = fopen($pdf_tilaustuotettarrafilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdf_tilaustuotettarrafilenimi");
		fclose($fh);
		
		if ($komento["Toimituksen tuotetarrat"] == 'email') {
			$liite = $pdf_tilaustuotettarrafilenimi;
			$kutsu = "Tilaustuotettarrat $laskurow[tunnus]";
			
			if ($kukarow["extranet"] == '') {
				require("../inc/sahkoposti.inc");
			}
			else {
				require("sahkoposti.inc");
			}
		}
		elseif ($komento["Toimituksen tuotetarrat"] != '' and $komento["Toimituksen tuotetarrat"] != 'edi') {

			//	Haetaan kirjoittimen nimi..
			$querykieli = "	select *
							from kirjoittimet
							where yhtio='$kukarow[yhtio]' and komento='{$komento["Toimituksen tuotetarrat"]}'";
			$kires = mysql_query($querykieli) or pupe_error($querykieli);
			if(mysql_num_rows($kires) > 0) {
				$kirow = mysql_fetch_array($kires);
				if ($silent == "") {
					echo t("Tilaustuotetarrat tulostuu kirjoittimelle").": $kirow[kirjoitin]<br>\n";
				}
				unset($returnvalue);
				$line = exec("{$komento["Toimituksen tuotetarrat"]} $pdf_tilaustuotettarrafilenimi", $output, $returnvalue);
			}
			if(mysql_num_rows($result) == 0 or $returnvalue!=0) {
				if ($silent == "") {
					echo "<font class='error'>".t("Tilaustuotetarrojen tulostus epäonnistui")."!!!</font><br>\n";
				}
			}
		}
		elseif ($tee == 'NAYTATILAUS') {
			//Työnnetään tuo pdf vaan putkeen!
			echo file_get_contents($pdf_tilaustuotettarrafilenimi);
		}
		
		system("rm -f $pdf_tilaustuotettarrafilenimi");

	}
	else {
		echo "ei rivei";
	}
	
?>
