<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	require ("inc/connect.inc");
	require ("inc/functions.inc");

	if ($argv[1] == '') {
		echo "Yhtiötä ei ole annettu, ei voida toimia\n";
		die;
	}
	else {
		$kukarow["yhtio"] = $argv[1];
	}

	error_reporting(E_ALL);
	ini_set("display_errors", 1);

	$kansio 		= "/Users/jamppa/Sites/devlab/pupesoft/teccom";		   // muuta näiden polut oikeiksi
	$kansio_valmis 	= "/Users/jamppa/Sites/devlab/pupesoft/teccom/valmis"; // muuta näiden polut oikeiksi
	$kansio_error 	= "/Users/jamppa/Sites/devlab/pupesoft/teccom/error";  // muuta näiden polut oikeiksi
	 
	// setataan käytetyt muuttujat:
	$tiedosto_sisalto = "";
	$pakkauslista 	= "";
	$tuotteet		= "";
	$tuote			= "";
	$kplt			= "";
	$kpl			= "";
	$rivilla		= "";
	$positio		= "";
	$tilausnumerot	= "";
	$tilnro			= "";
	$asn_numero		= "";
	$toimituspvm	= "";
	$vastaanottaja	= "";
	$laatikonnumerot = "";
	$laatikkoind	= "";
	$suba			= "";
	$subb			= "";
	$tavarantoimittajanumero = "";
	$poikkeukset = array("123001", "123067", "123310", "123312", "123342", "123108", "123035", "123049", "123317","123441");
	$kukarow["kuka"]	= "crond";
	
	if ($handle = opendir($kansio)) {

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($kansio."/".$file)) {

				$tiedosto = $kansio."/".$file;

				$xml = simplexml_load_file($tiedosto);
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
					
					$asn_numero  = (string) $xml->DesAdvHeader->DesAdvId;
					$asn_numero = utf8_decode($asn_numero);
					
					$toimituspvm = tv3dateconv($xml->DesAdvHeader->DeliveryDate->Date);
					$vastaanottaja = (string) $xml->DesAdvHeader->DeliveryParty->PartyNumber." , ".$xml->DesAdvHeader->DeliveryParty->Address->Name1;
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

					$p=1; $c=1;
					// haetaan tuotteet riveittäin
					foreach ($xml->Package as $paketti) {

						if (!isset($paketti->PkgItem->ProductId->ProductNumber) and $tavarantoimittajanumero != "123312") {
							$paketti = $paketti->Package;
						}

						if ($tavarantoimittajanumero == "123312" and trim($paketti->PkgNumber) == 1) {
							$lisays[$p][$c]['ProductId'] = "";
						}
						
						foreach ($paketti->PkgItem as $xxx) {
							$tuote = (string) $xxx->ProductId->ProductNumber;
							$tuote = utf8_decode($tuote);
							if ($tavarantoimittajanumero == "123067") {
								$tuote = $tuote."090";
							}
							if ($tavarantoimittajanumero == "123453")	{
								$suba = substr($tuote,0,3);
								$subb = substr($tuote,4);
								$tuote = $suba."-".$subb;
 							}						
							$lisays[$p][$c]['ProductId'] = $tuote;
							$c++;
						}
						$p++;
						$c=1;
						
					}

					$p=1; $c=1;
					// tuotteiden kappalemäärät
					foreach ($xml->Package as $paketti2) {
						if (!isset($paketti2->PkgItem->DeliveredQuantity->Quantity) and $tavarantoimittajanumero != "123312") {
							$paketti2 = $paketti2->Package;
						}
						
						if ($tavarantoimittajanumero == "123312" and trim($paketti2->PkgNumber) == 1) {
							$lisays[$p][$c]['DeliveredQuantity'] = "";
						}
						
						foreach ($paketti2->PkgItem as $yyy) {
							$kpl = (float) $yyy->DeliveredQuantity -> Quantity;
							$lisays[$p][$c]['DeliveredQuantity'] = $kpl;
							$c++;
						}
						$p++;
						$c=1;
					}

					$p=1; $c=1;
					// tuotteen rivipositio
					foreach ($xml->Package as $paketti3) {
						if (!isset($paketti3->PkgItem->PositionNumber) and $tavarantoimittajanumero != "123312") {
							$paketti3 = $paketti3->Package;
						}
						
						if ($tavarantoimittajanumero == "123312" and trim($paketti3->PkgNumber) == 1) {
							$lisays[$p][$c]['PositionNumber'] = "";
						}
						
						foreach ($paketti3->PkgItem as $zzz) {
							$positio = (string) $zzz->PositionNumber;
							$positio = utf8_decode($positio);
							$lisays[$p][$c]['PositionNumber'] = $positio;
							$c++;
						}
						$p++;
						$c=1;
					}

					$p=1; $c=1;
					// rivin tilaajan tilausnumero
					foreach ($xml->Package as $tuotteelta_tilausno) {
						if (!isset($tuotteelta_tilausno->PkgItem->OrderRef->BuyerOrderNumber) and $tavarantoimittajanumero != "123312") {
							$tuotteelta_tilausno = $tuotteelta_tilausno->Package;
						}
						
						if ($tavarantoimittajanumero == "123312" and trim($tuotteelta_tilausno->PkgNumber) == 1) {
							$lisays[$p][$c]['BuyerOrderNumber'] = "";
						}
						
						foreach ($tuotteelta_tilausno->PkgItem as $www) {
							$tilnro = (int) $www->OrderRef->BuyerOrderNumber;
							$lisays[$p][$c]['BuyerOrderNumber'] = $tilnro;
							$c++;
						}
						$p++;
						$c=1;
					}

					// tarvitaan pakkauksen "PkgIdentNumber"
					// on poikkeuksia ja poikkeuksen poikkeuksia			
					$p=1; $c=1;
					foreach ($xml->Package as $laatikosta) {
						if (($laatikosta->PkgId->PkgIdentNumber == "" or $laatikosta->PkgId->PkgIdentNumber == "0") and $tavarantoimittajanumero != "123312") {
							$laatikosta = $laatikosta->Package;
						}
						if (isset($laatikosta->PkgId)) {
							foreach ($laatikosta->PkgId as $ident) {
								$laatikkoind = (string) $ident->PkgIdentNumber;
								$laatikkoind = utf8_decode($laatikkoind);
								
								if ($tavarantoimittajanumero == "123085") {
									$laatikkoind = "0".$laatikkoind;
								}
								elseif (($tavarantoimittajanumero == "123001" or $tavarantoimittajanumero == "123049") and strlen($laatikkoind) >10) {
									$laatikkoind = substr($laatikkoind,10);
								}
								elseif ($tavarantoimittajanumero == "123342") {
									$laatikkoind = substr($laatikkoind,8);
								}
								else {
									$laatikkoind = $laatikkoind;
								}
								$lisays[$p][$c]['PkgIdentNumber'] = $laatikkoind;
							}
						}
						elseif ($tavarantoimittajanumero == "123312" and trim($laatikosta->PkgNumber) == 1) {
							$lisays[$p][$c]['PkgIdentNumber'] = "TOTAL PACKS";
						}
						elseif ($tavarantoimittajanumero == "123441" and !isset($laatikosta->PkgId->PkgIdentNumber)) {
							$lisays[$p][$c]['PkgIdentNumber'] = $asn_numero;
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
										AND toimittajanro = '$tavarantoimittajanumero'
										AND asn_numero = '$asn_numero'";
						$checkinsertresult = mysql_query($tarkinsert) or pupe_error($tarkinsert);

						if (mysql_num_rows($checkinsertresult) > 0) {
							echo "Sanomalle $asn_numero ja toimittajalle $tavarantoimittajanumero löytyy tietokannasta jo sanomat, ei lisätä uudestaan sanomia\n";
							rename($kansio."/".$file, $kansio_error."/".$file);
						}
						else {

							for ($i = 1; $i <= $pakettienlukumaara; $i++) {

								// otetaan talteen arrayn ensimmäisen rivin viimeisestä solusta laatikon tunnisteid ja laitetaan se jokaisen rivin tietoihin
								if (isset($lisays[$i][1]["PkgIdentNumber"])) {
									$laatikkoid = $lisays[$i][1]["PkgIdentNumber"];
									$laatikkoid = utf8_decode($laatikkoid);
								}
								else {
									$laatikkoid = "";
								}

								foreach ($lisays[$i] as $value) {
									$sqlinsert =  "		INSERT INTO asn_sanomat SET
														yhtio 				= '$kukarow[yhtio]',
														toimittajanro 		= '$tavarantoimittajanumero', 
														asn_numero			= '$asn_numero',
														saapumispvm 		= '$toimituspvm',
														vastaanottaja 		= '$vastaanottaja',
														tilausnumero 		= '$value[BuyerOrderNumber]', 
														paketinnumero		= '$i',
														paketintunniste 	= '$laatikkoid',
														lahetyslistannro 	= '$pakkauslista',
														toimittajan_tuoteno	= '$value[ProductId]',
														kappalemaara		= '$value[DeliveredQuantity]', 
														tilausrivinpositio	= '$value[PositionNumber]',
														laatija 			= '$kukarow[kuka]',
														luontiaika 			= now()";
									$result = mysql_query($sqlinsert) or pupe_error($sqlinsert);
									$eka_insert[] = mysql_insert_id(); // Otetaan insertin ensimmäinen tunnus talteen. tätä käytetään liitetiedostoissa liitostunnuksena.
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
							$Xresult = mysql_query($tecquery) or die ("$tecquery\n\n".mysql_error());

							rename($kansio."/".$file, $kansio_valmis."/".$file);
						}
	
						unset($eka_insert); // unsetataan tämä arvo aina kierroksen lopussa koska halutaan seuraavan kierrokselta ensimmäisen insertin Id talteen
						unset($lisays);
						unset($tilausnumerot);
						unset($rivilla);
						unset($tuotteet);
						unset($kplt);
					}
					else {
						echo "Odottamaton virhe, tavarantoimittajan numero puuttuu sekä ASN-numero puuttuu, tai materiaali ei ole ASN-sanoma\n";
						rename($kansio."/".$file, $kansio_error."/".$file);
					}
				}
				else {
					echo "Odottamaton virhe, materiaali ei ole ASN-sanoma\n";
					rename($kansio."/".$file, $kansio_error."/".$file);
				}
			}
		}
	}
	else {
		echo "Hakemistoa $kansio ei löydy\n";
	}

?>