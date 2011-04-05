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
	
	$kansio 		= "/Users/jamppa/Sites/devlab/pupesoft/teccom";		   // muuta näiden polut oikeiksi
	$kansio_valmis 	= "/Users/jamppa/Sites/devlab/pupesoft/teccom/valmis"; // muuta näiden polut oikeiksi
	$kansio_error 	= "/Users/jamppa/Sites/devlab/pupesoft/teccom/error";  // muuta näiden polut oikeiksi
	 
	// setataan käytetyt muuttujat:
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
	$tavarantoimittajanumero = "";
	$kukarow["kuka"]	= "crond";
	
	if ($handle = opendir($kansio)) {

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($kansio."/".$file)) {

				$tiedosto = $kansio."/".$file;

				$xml = simplexml_load_file($tiedosto);

				if ($xml !== FALSE) {

				 	$lisays = array();

					$tilausnumerot = "";

					// Haetaan pakkauslistan referenssinumero, mikäli löytyy
					if (isset($xml->Package->Package->PkgRef->PkgRefNumber)) {
						$pakkauslista = $xml->Package->Package->PkgRef->PkgRefNumber;
						$pakkauslista = utf8_decode($pakkauslista);
						// Mikäli paketin sisällä on paketti
						// esim. Mann
					}
					elseif (isset($xml->Package->PkgRef->PkgRefNumber)) {
						$pakkauslista = $xml->Package->PkgRef->PkgRefNumber;
						$pakkauslista = utf8_decode($pakkauslista);
						// normaali tapaus
					}
					else {
						$pakkauslista = "";
					}


					$p=1; $c=1;
					// haetaan tuotteet riveittäi
					foreach ($xml->Package as $paketti) {

						if (!isset($paketti->PkgItem->ProductId->ProductNumber)) {
							$paketti = $paketti->Package;
						}

						foreach ($paketti->PkgItem as $xxx) {
							$tuote = (string) $xxx->ProductId->ProductNumber;
							$tuote = utf8_decode($tuote);							
							$lisays[$p][$c]['ProductId'] = $tuote;
							$c++;
						}
						$p++;
						$c=1;
					}

					$p=1; $c=1;
					// tuotteiden kappalemäärät
					foreach ($xml->Package as $paketti2) {
						if (!isset($paketti2->PkgItem->DeliveredQuantity->Quantity)) {
							$paketti2 = $paketti2->Package;
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
						if (!isset($paketti3->PkgItem->PositionNumber)) {
							$paketti3 = $paketti3->Package;
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
						if (!isset($tuotteelta_tilausno->PkgItem->OrderRef->BuyerOrderNumber)) {
							$tuotteelta_tilausno = $tuotteelta_tilausno->Package;
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
					$p=1; $c=1;
					foreach ($xml->Package as $laatikosta) {
						if ($laatikosta->PkgId->PkgIdentNumber == "" or $laatikosta->PkgId->PkgIdentNumber == "0") {
							$laatikosta = $laatikosta->Package;
						}
						
						if (isset($laatikosta->PkgId)) {
							foreach ($laatikosta->PkgId as $ident) {
								$laatikkoind = (string) $ident->PkgIdentNumber;
								$laatikkoind = utf8_decode($laatikkoind);
								$lisays[$p][$c]['PkgIdentNumber'] = $laatikkoind;
							}
						}
						$p++;
					}

					// nämä 2 arvoa pitää olla tai ei tule toimimaan.
					$tavarantoimittajanumero = (string) $xml->DesAdvHeader->SellerParty->PartyNumber;
					$tavarantoimittajanumero = utf8_decode($tavarantoimittajanumero);
					$asn_numero  = (string) $xml->DesAdvHeader->DesAdvId;
					$asn_numero = utf8_decode($asn_numero);
					
					$toimituspvm = tv3dateconv($xml->DesAdvHeader->DeliveryDate->Date);
					$vastaanottaja = (string) $xml->DesAdvHeader->DeliveryParty->PartyNumber." , ".$xml->DesAdvHeader->DeliveryParty->Address->Name1;
					$vastaanottaja = utf8_decode($vastaanottaja);
					
					$pakettienlukumaara = count($lisays);
					$eka_insert = array();

					if ($tavarantoimittajanumero != "" and $asn_numero != "") {

						$tarkinsert = " SELECT tunnus
										FROM asn_sanomat
										WHERE yhtio = '$kukarow[yhtio]'
										AND toimittajanumero = '$tavarantoimittajanumero'
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
														toimittajanumero 	= '$tavarantoimittajanumero', 
														asn_numero			= '$asn_numero',
														saapumispvm 		= '$toimituspvm',
														vastaanottaja 		= '$vastaanottaja',
														tilausnumero 		= '$value[BuyerOrderNumber]', #
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

							$tallennaliitekantaan = tallenna_liite($tiedosto, "asn_sanomat", $eka_insert[0], "$tavarantoimittajanumero ASN_sanoman $asn_numero tiedosto");
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