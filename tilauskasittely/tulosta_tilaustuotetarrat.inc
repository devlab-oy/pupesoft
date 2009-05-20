<?php
	
if(!function_exists("tulosta_tilaustuotetarrat")) {
	function tulosta_tilaustuotetarrat($otunnus, $tunnukset, $komento="", $tee="") {		
		global $yhtiorow, $kukarow, $pdf, $fonts, $h;

		if($tunnukset>0) {
			$lisa = "tilausrivi.tunnus in ($tunnukset) and ";
		}
		else {
			$lisa = "tilausrivi.otunnus in ($otunnus) and ";
		}
		
		//	Haetaan tilausrivit
		$query = "	SELECT 	tilausrivi.otunnus, tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.kpl, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso,
							tuote.sarjanumeroseuranta, tuote.yksikko, if(tuote.tarrakpl>0, tarrakpl, 1) tarrakpl, tuote.yksikko,
							sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.ostorivitunnus, sarjanumeroseuranta.myyntirivitunnus, sarjanumeroseuranta.lisatieto, sarjanumeroseuranta.tunnus sarjatunnus
					FROM tilausrivi
					JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '' and tuote.sarjanumeroseuranta!=''
					JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio=tilausrivi.yhtio and sarjanumeroseuranta.ostorivitunnus = tilausrivi.tunnus and sarjanumeroseuranta.myyntirivitunnus=0
					WHERE tilausrivi.yhtio='$kukarow[yhtio]' and $lisa tilausrivi.tyyppi = 'O'";
		$result = mysql_query($query) or pupe_error($query);
		
		if(mysql_num_rows($result) > 0) {
			
			require_once('pdflib/pupepdf.class.php');
			
			if (!function_exists('alku')) {
				function alku ($pdf, $row) {
					global $yhtiorow, $laskurow, $kukarow, $fonts, $tid, $h, $pdf;

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
			
			while($row = mysql_fetch_array($result)) {
				
				//	Ratkaistaan tulostusmäärä
				if(in_array($row["sarjanumeroseuranta"], array("S", "T", "U", "V"))) {
					$tarra_kpl = 1;
				}
				elseif(in_array(strtoupper($row["yksikko"]), array("KPL"))) {
					$tarra_kpl = $row["kpl"];
				}
				else {
					$tarra_kpl = $row["tarrakpl"];
				}
				
				for($i=0;$i<$tarra_kpl;$i++) {
					$pdf = alku($pdf, $row);
					
					$query = "	SELECT laskutettuaika
								FROM tilausrivi
								WHERE yhtio='$kukarow[yhtio]' and tunnus = '$row[ostorivitunnus]'";
					$alres = mysql_query($query) or pupe_error($query);
					$aikarow = mysql_fetch_array($alres);
					
					$query = "	SELECT *
								FROM sarjanumeroseuranta
								WHERE yhtio='$kukarow[yhtio]' and ostorivitunnus = '$row[ostorivitunnus]' and sarjanumero = '$row[sarjanumero]' and tuoteno = '$row[tuoteno]'";
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);
					
					if(table_exists("sarjanumeron_lisatiedot")) {
						$query = "	SELECT *
									FROM sarjanumeron_lisatiedot use index (yhtio_liitostunnus)
									WHERE yhtio		 = '$kukarow[yhtio]'
									and liitostunnus = '$row[sarjatunnus]'";
						$lisares = mysql_query($query) or pupe_error($query);
						$lisarow = mysql_fetch_array($lisares);
					}
					
					//	tälle on jokin speciaali tuotetarratyyppi
					if($row["tuotetarratyyppi"] != "") {
						require($row["tuotetarratyyppi"]);
					}
					else {

						$pdf->write($h, 1, 0, 75, $yhtiorow[nimi], "BIG", "L", "B");
						$pdf->write($h, 1, 80, 92,	tv1dateconv($aikarow["laskutettuaika"]), "norm", "R", "");
						
						$h-=$pdf->ln(2);

						$pdf->write($h, 1, 0, 20, "Tuote", "norm", "L", "B");
						$pdf->write($h, 1, 20, 0, "$row[tuoteno] - $row[nimitys]", "norm", "L", "");
						$h-=$pdf->ln(1.4);

						if(in_array($row["sarjanumeroseuranta"], array("E", "F", "G"))) {
							
							$pdf->write($h, 1, 0, 15, 	"Tilaus", "norm", "L", "B");
							$pdf->write($h, 1, 20, 45, 	"Erä", "norm", "L", "B");
							$pdf->write($h, 1, 50, 68, 	"Eräkoko", "norm", "L", "B");
							if($lisarow["pituus"]>0) {
								$pdf->write($h, 1, 73, 93, 	"Pituus", "norm", "L", "B");
							}
							$h-=$pdf->ln(1.25);
														
							$pdf->write($h, 1, 0, 15,   $row["otunnus"], "norm", "L", "");
							$pdf->write($h, 1, 20, 45,	$row["sarjanumero"], "norm", "L", "");
							$pdf->write($h, 1, 50, 68,	$sarjarow["kpl"].strtolower($row["yksikko"]), "norm", "L", "");
							if($lisarow["pituus"]>0) {
								$pdf->write($h, 1, 73, 93,	$lisarow["pituus"], "norm", "L", "");
							}
							$h-=$pdf->ln(1.5);
							
							$pdf->write($h, 1, 0, 15, 	"Lisätiedot", "norm", "L", "B");
							$h-=$pdf->ln(1.25);
							$pdf->write($h, 1, 0, 0,	"$row[lisatieto]", "norm", "L", "");
						}
						elseif(in_array($row["sarjanumeroseuranta"], array("S", "T", "U", "V"))) {
							$pdf->write($h, 1, 0, 15, 	"Tilaus", "norm", "L", "B");
							$pdf->write($h, 1, 20, 45, 	"S:nro", "norm", "L", "B");
							if($lisarow["pituus"]>0) {
								$pdf->write($h, 1, 73, 93, 	"Pituus", "norm", "L", "B");
							}
							$h-=$pdf->ln(1.25);
														
							$pdf->write($h, 1, 0, 15,   $row["otunnus"], "norm", "L", "");
							$pdf->write($h, 1, 20, 45,	$row["sarjanumero"], "norm", "L", "");
							if($lisarow["pituus"]>0) {
								$pdf->write($h, 1, 73, 93,	$lisarow["pituus"], "norm", "L", "");
							}
							$h-=$pdf->ln(1.5);
							
							$pdf->write($h, 1, 0, 15, 	"Lisätiedot", "norm", "L", "B");
							$h-=$pdf->ln(1.25);
							$pdf->write($h, 1, 0, 0,	"$row[lisatieto]", "norm", "L", "");
						}
					}
				}
			
				if($komento=="OPTIMOI") {
					$query = "	SELECT printteri2
								FROM varastopaikat
								WHERE varastopaikat.yhtio = '$kukarow[yhtio]' and varastopaikat.printteri2 != '' and
								concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0')) and
								concat(rpad(upper(loppuhyllyalue)  ,5,'0'),lpad(upper(loppuhyllynro)  ,5,'0')) >= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0'))
								LIMIT 1";
					$kirres = mysql_query($query) or pupe_error($query);
					$kirrow = mysql_fetch_array($kirres);
					$arvaakirjoitin[$kirrow["printteri2"]]++;
				}				
			}
			
			//	Haetaan sopivan varaston printteri2
			if($komento=="OPTIMOI" and is_array($arvaakirjoitin)) {
				arsort($arvaakirjoitin);
				$query = "	SELECT *
							FROM kirjoittimet
							WHERE yhtio='$kukarow[yhtio]' and tunnus = '".key($arvaakirjoitin)."'";
				$kirres = mysql_query($query) or pupe_error($query);
				$kirrow = mysql_fetch_array($kirres);
				$komento = $kirrow["komento"];
			}
			
			//	Lopetetaan aineisto ja laitetaan aineisto ulos
			$pdf_tilaustuotetarrafilenimi = "/tmp/$yhtiorow[yhtio]-Tilaustuotetarrat_".md5(uniqid(mt_rand(), true)).".pdf";
			$fh = fopen($pdf_tilaustuotetarrafilenimi, "w");

			if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdf_tilaustuotetarrafilenimi");
			fclose($fh);

			if ($komento == 'email') {
				$liite = $pdf_tilaustuotetarrafilenimi;
				$kutsu = "Tilaustuotetarrat $laskurow[tunnus]";

				if ($kukarow["extranet"] == '') {
					require("../inc/sahkoposti.inc");
				}
				else {
					require("sahkoposti.inc");
				}
			}
			elseif ($tee == 'NAYTATILAUS') {
				//Työnnetään tuo pdf vaan putkeen!
				echo file_get_contents($pdf_tilaustuotetarrafilenimi);
			}
			elseif ($komento != '' and $komento != 'edi') {
				echo t("Tilaustuotetarrat tulostuu")."...<br>";
				$line = exec("$komento $pdf_tilaustuotetarrafilenimi", $output, $returnvalue);
				if($returnvalue == 0) {
					echo "<font class='message'>".t("Tilaustuotetarrat tulostettiin")."</font><br>";
				}
				else {
					echo "<font class='error'>".t("VIRHE: Tilaustuotetarrojen tulostus epäonnistui..")."</font><br>";
				}
			}

			system("rm -f $pdf_tilaustuotetarrafilenimi");
			
		}		
	}	
}
	
?>
