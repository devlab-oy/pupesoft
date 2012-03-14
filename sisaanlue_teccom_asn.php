<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		require ("inc/parametrit.inc");
	}
	else {
		error_reporting(E_ALL);
		ini_set("display_errors", 1);

		require ("inc/connect.inc");
		require ("inc/functions.inc");

		if ($argv[1] == '') {
			echo "Yhtiötä ei ole annettu, ei voida toimia\n";
			die;
		}
		else {
			$kukarow["yhtio"] = $argv[1];
		}
	}

	// määritellään polut
	if (!isset($teccomkansio)) {
		$teccomkansio = "/home/teccom";
	}
	if (!isset($teccomkansio_valmis)){
		$teccomkansio_valmis = "/home/teccom/valmis";
	}
	if (!isset($teccomkansio_error)) {
		$teccomkansio_error = "/home/teccom/error";
	}

	// setataan käytetyt muuttujat:
	$asn_numero					= "";
	$kpl						= "";
	$kplt						= "";
	$kukarow["kuka"] 			= "crond";
	$laatikkoind				= "";
	$laatikonnumerot			= "";
	$pakkauslista 				= "";
	$poikkeukset 				= array("123001", "123067", "123310", "123312", "123342", "123108", "123035", "123049", "123317","123441","123080","123007");
	$positio					= "";
	$rivilla					= "";
	$suba						= "";
	$subb						= "";
	$tavarantoimittajanumero 	= "";
	$tiedosto_sisalto			= "";
	$tilausnumerot				= "";
	$tilnro						= "";
	$toimituspvm				= "";
	$tuote						= "";
	$tuotteet					= "";
	$vastaanottaja				= "";

	if ($handle = opendir($teccomkansio)) {

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($teccomkansio."/".$file)) {

				$tiedosto = $teccomkansio."/".$file;

				$xml = @simplexml_load_file($tiedosto);
				$tiedosto_sisalto = file_get_contents($tiedosto);
				$tiedosto_sisalto = mysql_real_escape_string($tiedosto_sisalto);

				if ($xml !== FALSE) {

				 	$lisays = array();
					$tilausnumerot = "";

					// $tavarantoimittajanumero ja $asn_numero arvoa pitää olla tai ei tule toimimaan.
					$tavarantoimittajanumero = (string) $xml->DesAdvHeader->SellerParty->PartyNumber;
					$tavarantoimittajanumero = utf8_decode($tavarantoimittajanumero);

					if (strtoupper($tavarantoimittajanumero) == "ELRING") {
						$tavarantoimittajanumero = "123312";
					}
					elseif (strtoupper($tavarantoimittajanumero) == "BOSCH") {
						$tavarantoimittajanumero = "123067";
					}
					elseif (strtoupper($tavarantoimittajanumero) == "NISSENS") {
						$tavarantoimittajanumero = "123403";
					}
					elseif ($tavarantoimittajanumero == "112") {
						$tavarantoimittajanumero = "123442";
					}
					elseif (strtoupper($tavarantoimittajanumero) == "LES-7") {
						$tavarantoimittajanumero = "123080";
					}				

					$asn_numero  = (string) $xml->DesAdvHeader->DesAdvId;
					$asn_numero = utf8_decode($asn_numero);

					$toimituspvm = tv3dateconv($xml->DesAdvHeader->DeliveryDate->Date);
					$vastaanottaja = (string) $xml->DesAdvHeader->DeliveryParty->PartyNumber." , ".trim($xml->DesAdvHeader->DeliveryParty->Address->Name1);
					$vastaanottaja = utf8_decode($vastaanottaja);

					// Haetaan pakkauslistan referenssinumero, mikäli löytyy
					if (isset($xml->Package->Package->PkgRef->PkgRefNumber) and $xml->Package->Package->PkgRef->PkgRefNumber != "") {
						$pakkauslista = $xml->Package->Package->PkgRef->PkgRefNumber;
						$pakkauslista = utf8_decode($pakkauslista);
						// Mikäli paketin sisällä on paketti
					}
					elseif (isset($xml->Package->PkgRef->PkgRefNumber) and $xml->Package->PkgRef->PkgRefNumber != "") {
						$pakkauslista = $xml->Package->PkgRef->PkgRefNumber;
						$pakkauslista = utf8_decode($pakkauslista);
						// normaali tapaus
					}
					elseif (in_array($tavarantoimittajanumero, $poikkeukset)) {
						$pakkauslista = $asn_numero;
						// poikkeustapauksissa
					}
					elseif (isset($xml->Package->PkgInfo->PacketKind)) {
						$pakkauslista = (string) $xml->Package->PkgInfo->PacketKind;
						// poikkeuksen poikkeukset
					}
					else {
						$pakkauslista = $asn_numero;
						// jos mikään ei mätsää, niin laitetaan asn-numero
					}

					$p=1; $c=1;$d=1;
					// haetaan tuotteet riveittäin
					foreach ($xml->Package as $paketti) {

						if ($tavarantoimittajanumero == "123085" and $paketti->PkgNumber == 0) {
							foreach ($paketti->Package as $paketti) {
								foreach ($paketti->PkgItem as $xxx) {
									$tuote = (string) $xxx->ProductId->ProductNumber;
									$tuote = utf8_decode($tuote);
									$tuote2 = (string) $xxx->ProductId->BuyerProductNumber;
									$tuote2 = utf8_decode($tuote2); // tämä on overkilliä muillakuin 123067 ja 123310 mutta kuitenkin.
									$lisays[$p][$c]['ProductId'] = $tuote;
									$lisays[$p][$c]['BuyerProductNumber'] = $tuote2;
									$c++;
								}
								
								foreach ($paketti->PkgId as $xxx) {
									$ident = (string) $xxx->PkgIdentNumber;
									$ident = utf8_decode($ident);
									$lisays[$p][1]['PkgIdentNumber'] = '0'.$ident;
									$lisays[$p][1]['SSCC'] = '0'.$ident;
									
								}
								
								$p++;
							}
						}
						elseif (!isset($paketti->PkgItem->ProductId->ProductNumber) and $tavarantoimittajanumero != "123312" ) {
							$paketti = $paketti->Package;
						}

						if ($tavarantoimittajanumero == "123312" and trim($paketti->PkgNumber) == 1) {
							$lisays[$p][$c]['ProductId'] = "";
						}

						if ($tavarantoimittajanumero != "123085") {
							foreach ($paketti->PkgItem as $xxx) {
								$tuote = (string) $xxx->ProductId->ProductNumber;
								$tuote = utf8_decode($tuote);
								$tuote2 = (string) $xxx->ProductId->BuyerProductNumber;
								$tuote2 = utf8_decode($tuote2); // tämä on overkilliä muillakuin 123067 ja 123310 mutta kuitenkin.
								$lisays[$p][$c]['ProductId'] = $tuote;
								$lisays[$p][$c]['BuyerProductNumber'] = $tuote2;
								$c++;
							}
						}
						$p++;
						$c=1;

					}

					
					$p=1; $c=1;
					// tuotteiden kappalemäärät
					foreach ($xml->Package as $paketti2) {
						
						if ($tavarantoimittajanumero == "123085" and $paketti2->PkgNumber == 0) {
							foreach ($paketti2->Package as $paketti22) {
								foreach ($paketti22->PkgItem as $yyy) {
									$kpl = (float) $yyy->DeliveredQuantity->Quantity;
									$lisays[$p][$c]['DeliveredQuantity'] = $kpl;
									$c++;
								}
							$p++;
							}
						}
						elseif (!isset($paketti2->PkgItem->DeliveredQuantity->Quantity) and $tavarantoimittajanumero != "123312") {
							$paketti2 = $paketti2->Package;
						}

						if ($tavarantoimittajanumero == "123312" and trim($paketti2->PkgNumber) == 1) {
							$lisays[$p][$c]['DeliveredQuantity'] = "";
						}
						if ($tavarantoimittajanumero != "123085") {
							foreach ($paketti2->PkgItem as $yyy) {
								$kpl = (float) $yyy->DeliveredQuantity -> Quantity;
								$lisays[$p][$c]['DeliveredQuantity'] = $kpl;
								$c++;
							}
						}
						$p++;
						$c=1;
					}

					$p=1; $c=1;
					// tuotteen rivipositio
					foreach ($xml->Package as $paketti3) {
						
						if ($tavarantoimittajanumero == "123085" and $paketti3->PkgNumber == 0) {
							foreach ($paketti3->Package as $paketti3) {
								foreach ($paketti3->PkgItem as $zzz) {
									//$positio = (string) $zzz->PositionNumber;
									$positio = (string) $zzz->OrderItemRef->BuyerOrderItemRef;
									$positio = utf8_decode($positio);
									$lisays[$p][$c]['PositionNumber'] = (int) $positio;
									$c++;
								}
								$p++;
							}
						}
						elseif ($tavarantoimittajanumero == "123342") {
							foreach ($paketti3->PkgItem as $www) {
								$positio = (int) $www->OrderRef->BuyerOrderNumber;
								$lisays[$p][$c]['PositionNumber'] = $positio;
								$c++;
							}
						}
						elseif (!isset($paketti3->PkgItem->PositionNumber) and $tavarantoimittajanumero != "123312") {
							$paketti3 = $paketti3->Package;
						}

						if ($tavarantoimittajanumero != "123085" and $tavarantoimittajanumero != "123342"){
							foreach ($paketti3->PkgItem as $zzz) {
									$positio = (string) $zzz->OrderItemRef->BuyerOrderItemRef;
									$positio = utf8_decode($positio);
									$lisays[$p][$c]['PositionNumber'] = (int) $positio;
									$c++;
							}
						}
						$p++;
						$c=1;
					}

					$p=1; $c=1;
					// rivin tilaajan tilausnumero
					foreach ($xml->Package as $tuotteelta_tilausno) {
						
						if ($tavarantoimittajanumero == "123085" and $tuotteelta_tilausno->PkgNumber == 0) {
							foreach ($tuotteelta_tilausno->Package as $tuotteelta_tilausno) {
								foreach ($tuotteelta_tilausno->PkgItem as $www) {
									$tilnro = (int) $www->OrderRef->BuyerOrderNumber;
									$lisays[$p][$c]['BuyerOrderNumber'] = $tilnro;
									$c++;
								}
							$p++;
							}
						}
						elseif (!isset($tuotteelta_tilausno->PkgItem->OrderRef->BuyerOrderNumber) and $tavarantoimittajanumero != "123312") {
							$tuotteelta_tilausno = $tuotteelta_tilausno->Package;
						}

						if ($tavarantoimittajanumero == "123312" and trim($tuotteelta_tilausno->PkgNumber) == 1) {
							$lisays[$p][$c]['BuyerOrderNumber'] = "";
						}

						if ($tavarantoimittajanumero != "123085") {
							foreach ($tuotteelta_tilausno->PkgItem as $www) {
								$tilnro = (int) $www->OrderRef->BuyerOrderNumber;
								$lisays[$p][$c]['BuyerOrderNumber'] = $tilnro;
								$c++;
							}
						}
						$p++;
						$c=1;
					}

					// tarvitaan pakkauksen "PkgIdentNumber"
					// on poikkeuksia ja poikkeuksen poikkeuksia ja niillekki vielä poikkeus
					$p=1; $c=1;
					foreach ($xml->Package as $laatikosta) {

						if (isset($laatikosta->PkgId)) {
							foreach ($laatikosta->PkgId as $ident) {
								$laatikkoind = (string) $ident->PkgIdentNumber;
								$laatikkoind = utf8_decode($laatikkoind);
								// SSCC-koodi on periaatteessa sama mutta lyhentämättömänä tulevaisuutta varten keikalle
								$sscc = $laatikkoind;

								if (($tavarantoimittajanumero == "123001" or $tavarantoimittajanumero == "123049" or $tavarantoimittajanumero == "123108") and strlen($laatikkoind) >10) {
									$sscc = $laatikkoind;
									$laatikkoind = substr($laatikkoind,10);
		
								}
								elseif (($tavarantoimittajanumero == "123001" or $tavarantoimittajanumero == "123108") and strlen($laatikkoind) < 10) {
									$sscc = $laatikkoind;
									$laatikkoind = '0'.$laatikkoind;
								}
								elseif ($tavarantoimittajanumero == "123342") {
									$sscc = $laatikkoind;
									$laatikkoind = substr($laatikkoind,8);
								}
								else {
									$laatikkoind = $laatikkoind;
									$sscc = $laatikkoind;
								}
								if ($tavarantoimittajanumero !="123085") {
									$lisays[$p][$c]['PkgIdentNumber'] = $laatikkoind;
									$lisays[$p][$c]['SSCC'] = $sscc;
								}
							}
						}
						elseif ($tavarantoimittajanumero == "123312" and trim($laatikosta->PkgNumber) == 1) {
							$lisays[$p][$c]['PkgIdentNumber'] = "TOTAL PACKS";
						}
						elseif ((trim($laatikosta->PkgNumber) == 1 or trim($laatikosta->PkgNumber) == 0 or trim($laatikosta->PkgNumber) == "01") and 
						(strtoupper($laatikosta->PkgInfo->PacketKind) == "TOTAL PACKS" or 
							strtoupper($laatikosta->PkgInfo->PacketKind) == "TOTALPACKS" or 
							strtoupper($laatikosta->PkgInfo->PacketKindFreeText) == "TOTAL PACKS" or 
							strtoupper($laatikosta->PkgInfo->PacketKindFreeText) == "TOTALPACKS")) {
								$lisays[$p][$c]['PkgIdentNumber'] = "TOTAL PACKS";
						}
						elseif ($tavarantoimittajanumero == "123441" and !isset($laatikosta->PkgId->PkgIdentNumber)) {
							$lisays[$p][$c]['PkgIdentNumber'] = $asn_numero;
						}
						elseif ($tavarantoimittajanumero == "123007" and !isset($laatikosta->PkgId->PkgIdentNumber) and (isset($laatikosta->PkgInfo->PacketKind) and (strtoupper($laatikosta->PkgInfo->PacketKind) != "TOTAL PACKS" or strtoupper($laatikosta->PkgInfo->PacketKind) != "TOTALPACKS"))) {
							$laatikkoind = (string) $laatikosta->PkgInfo->PacketKind;
							$laatikkoind = utf8_decode($laatikkoind);	
							$lisays[$p][$c]['PkgIdentNumber'] = $laatikkoind;
						}
						else {
							$lisays[$p][$c]['PkgIdentNumber'] = $asn_numero;
						}
						$p++;
					}

					$pakettienlukumaara = count($lisays);
					$eka_insert = array();

					if ($tavarantoimittajanumero != "" and $asn_numero != "") {

						$tarkinsert = " SELECT tunnus
										FROM asn_sanomat
										WHERE yhtio = '$kukarow[yhtio]'
										AND toimittajanumero = '$tavarantoimittajanumero'
										AND asn_numero = '$asn_numero'";
						$checkinsertresult = pupe_query($tarkinsert);

						if (mysql_num_rows($checkinsertresult) > 0) {
							echo "Sanomalle $asn_numero ja toimittajalle $tavarantoimittajanumero löytyy tietokannasta jo sanomat, ei lisätä uudestaan sanomia\n";
							rename($teccomkansio."/".$file, $teccomkansio_error."/".$file);
						}
						else {
							for ($i = 1; $i <= $pakettienlukumaara; $i++) {
								// otetaan talteen arrayn ensimmäisen rivin viimeisestä solusta laatikon tunnisteid ja laitetaan se jokaisen rivin tietoihin
								if (isset($lisays[$i][1]["PkgIdentNumber"])) {
									$laatikkoid = $lisays[$i][1]["PkgIdentNumber"];
									$laatikkoid = utf8_decode($laatikkoid);
									$SSCC		= $lisays[$i][1]["SSCC"];
									$SSCC 		= utf8_decode($SSCC);
									
									if ($tavarantoimittajanumero == "123220" or $tavarantoimittajanumero == "123080") {
										$SSCC = $asn_numero;
									}
								}
								else {
									$laatikkoid = "";
									$SSCC		= "";
								}

								foreach ($lisays[$i] as $value) {

									if (($laatikkoid == "TOTAL PACKS" and ($tavarantoimittajanumero == "123220" or $tavarantoimittajanumero == "123080") and $value["ProductId"] != "" and $value["DeliveredQuantity"] != '') or 
										($laatikkoid != "TOTAL PACKS" and ($value["ProductId"] != "" and $value["DeliveredQuantity"] != ''))) { // emme halua tietyltä toimittajalta keräyslaatikon aiheuttavan turhaa hälytystä
										
		 								$sqlinsert =  "		INSERT INTO asn_sanomat SET
		 													yhtio 				= '$kukarow[yhtio]',
															laji				= 'asn',
		 													toimittajanumero	= '$tavarantoimittajanumero',
		 													asn_numero			= '$asn_numero',
															sscc_koodi			= '$SSCC',
		 													saapumispvm 		= '$toimituspvm',
		 													vastaanottaja 		= '$vastaanottaja',
		 													tilausnumero 		= '$value[BuyerOrderNumber]',
		 													paketinnumero		= '$i',
		 													paketintunniste 	= '$laatikkoid',
		 													lahetyslistannro 	= '$pakkauslista',
		 													toim_tuoteno		= '$value[ProductId]',
															toim_tuoteno2		= '$value[BuyerProductNumber]',
		 													kappalemaara		= '$value[DeliveredQuantity]',
		 													tilausrivinpositio	= '$value[PositionNumber]',
		 													laatija 			= '$kukarow[kuka]',
		 													luontiaika 			= now()";
		 								$result = pupe_query($sqlinsert);
			
		 								$eka_insert[] = mysql_insert_id(); // Otetaan insertin ensimmäinen tunnus talteen. tätä käytetään liitetiedostoissa liitostunnuksena.
									}
								}
							}

							$filesize = strlen($tiedosto_sisalto);

							$tecquery = "	INSERT INTO liitetiedostot SET
											yhtio    			= '$kukarow[yhtio]',
											liitos   			= 'asn_sanomat',
											liitostunnus 		= '$eka_insert[0]',
											data     			= '$tiedosto_sisalto',
											selite   			= '$tavarantoimittajanumero ASN_sanoman $asn_numero tiedosto',
											filename 			= '$file',
											filesize 			= '$filesize',
											filetype 			= 'text/xml',
											image_width			= '',
											image_height		= '',
											image_bits			= '',
											image_channels		= '',
											kayttotarkoitus		= 'TECCOM-ASN',
											jarjestys			= '1',
											laatija				= '$kukarow[kuka]',
											luontiaika			= now()";
							$Xresult = pupe_query($tecquery);
							rename($teccomkansio."/".$file, $teccomkansio_valmis."/".$file);
						}

						unset($eka_insert); // unsetataan tämä arvo aina kierroksen lopussa koska halutaan seuraavan kierrokselta ensimmäisen insertin Id talteen
						unset($lisays);
						unset($tilausnumerot);
						unset($rivilla);
						unset($tuotteet);
						unset($kplt);
					}
					else {
						echo t("Virhe! Tavarantoimittajan numero puuttuu sekä ASN-numero puuttuu, tai materiaali ei ole ASN-sanoma")."\n";
						rename($teccomkansio."/".$file, $teccomkansio_error."/".$file);
					}
				}
				else {
					echo t("Tiedosto ei ole XML-sanoma").": $tiedosto\n\n";
					rename($teccomkansio."/".$file, $teccomkansio_error."/".$file);
				}
			}
		}

		require ("inc/asn_kohdistus.inc");
		asn_kohdistus();

	}
	else {
		echo "Hakemistoa $teccomkansio ei löydy\n";
	}

?>