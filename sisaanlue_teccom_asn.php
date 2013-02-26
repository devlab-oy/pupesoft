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
		$teccomkansio_valmis = "/home/teccom/ok";
	}
	if (!isset($teccomkansio_error)) {
		$teccomkansio_error = "/home/teccom/error";
	}

	// setataan käytetyt muuttujat:
	$asn_numero					= "";
	$kukarow["kuka"] 			= "crond";
	$poikkeukset 				= array("123001", "123067", "123310", "123312", "123342", "123108", "123035", "123049", "123317","123441","123080","123007","123453");
	$tavarantoimittajanumero 	= "";
	$tiedosto_sisalto			= "";
	$toimituspvm				= "";
	$vastaanottaja				= "";

	function loop_packet($xml_element, $parameters) {
		global $kukarow;
		static $paketti_nro = 0;
		static $tunnus_liitetiedostoon = 0;

		$tavarantoimittajanumero = $parameters["tavarantoimittajanumero"];
		$asn_numero              = $parameters["asn_numero"];
		$toimituspvm             = $parameters["toimituspvm"];
		$vastaanottaja           = $parameters["vastaanottaja"];
		$pakkauslista            = $parameters["pakkauslista"];
		$pakettinumero 			 = $parameters["pakettinumero"];
		$sscc					 = $parameters["sscc"];
		$laatikkoind			 = $parameters["laatikkoind"];
		
		foreach ($xml_element as $key => $element) {

			// Tämä on tuote-elementti
			if ($key == "PkgItem") {

				$tuote					= (string) $element->ProductId->ProductNumber;
				$tuote					= utf8_decode(trim($tuote));
				$tuote2					= (string) $element->ProductId->BuyerProductNumber;
				$tuote2					= utf8_decode(trim($tuote2)); 
				$kpl					= (float) $element->DeliveredQuantity->Quantity;
				$tilausrivinpositio		= (int) $element->OrderItemRef->BuyerOrderItemRef;
				$tuotteelta_tilausno	= (int) $element->OrderRef->BuyerOrderNumber;
				
				if ($kpl > 0.0 and $tuote != "") {

					$toinen_tuoteno = "";

					if ($tavarantoimittajanumero == "123067") {
						$orgtuote = $tuote;
						$lyhennetty_tuoteno = substr($tuote, 0, -3);
						$jatkettu_tuoteno = $lyhennetty_tuoteno."090";

						if ($tuote2 != "") {
							$toinen_tuoteno = ",'{$tuote2}'";
						}

						$poikkeus_tuoteno =" in ('{$orgtuote}','{$lyhennetty_tuoteno}','{$jatkettu_tuoteno}' {$toinen_tuoteno})";
					}
					elseif ($tavarantoimittajanumero == "123453") {
						$suba = substr($tuote,0,3);
						$subb = substr($tuote,3);
						$tuote_x = $suba."-".$subb;
						$yhteen = $tuote_x;

						if ($tuote2 != "") {
							$toinen_tuoteno = ",'{$tuote2}'";
						}

						$poikkeus_tuoteno = " in ('{$tuote_x}','{$yhteen}' {$toinen_tuoteno}) ";
					}
					elseif ($tavarantoimittajanumero == "123178") {
						$orgtuote = $tuote;
						$lyhennetty = substr($tuote,3);

						if ($tuote2 != "") {
							$lyhennetty_toinen = substr($tuote2,3);
							$toinen_tuoteno = ",'{$tuote2}','{$lyhennetty_toinen}'";
						}

						$poikkeus_tuoteno = " in ('{$orgtuote}','{$lyhennetty}' {$toinen_tuoteno}) ";
					}
					elseif ($tavarantoimittajanumero == "123084") {
						$orgtuote = $tuote;
						$lyhennetty = ltrim($tuote,'0');

						if ($tuote2 != "") {
							$lyhennetty_toinen = ltrim($tuote2,'0');
							$toinen_tuoteno = ",'{$tuote2}','{$lyhennetty_toinen}'";
						}

						$poikkeus_tuoteno = " in ('{$orgtuote}','{$lyhennetty}' {$toinen_tuoteno}) ";
					}
					else {
						if ($tuote2 != "") {
							$toinen_tuoteno = ",'{$tuote2}'";
						}

						$poikkeus_tuoteno = " in ('{$tuote}' {$toinen_tuoteno}) ";
					}

					$query = "	SELECT tuotteen_toimittajat.tuotekerroin
								FROM toimi
								JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = toimi.yhtio
															AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
															#AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
															AND tuotteen_toimittajat.toim_tuoteno {$poikkeus_tuoteno}
															AND tuotteen_toimittajat.toim_tuoteno != '')
								WHERE toimi.yhtio = '{$kukarow['yhtio']}'
								AND toimi.toimittajanro = '{$tavarantoimittajanumero}'
								AND toimi.tyyppi != 'P'
								AND toimi.asn_sanomat = 'K'";
					$tuotekerroin_chk_res = pupe_query($query);

					if (mysql_num_rows($tuotekerroin_chk_res) > 0) {
						$tuotekerroin_chk_row = mysql_fetch_assoc($tuotekerroin_chk_res);

						if ($tuotekerroin_chk_row['tuotekerroin'] != 0) {
							$kpl /= $tuotekerroin_chk_row['tuotekerroin'];
						}
					}

					// tämä siksi ettei haluta tallentaa 0 rivejä kantaan.
					$sqlinsert =  " INSERT INTO asn_sanomat SET
									yhtio 				= '$kukarow[yhtio]',
									laji				= 'asn',
									toimittajanumero	= '$tavarantoimittajanumero',
									asn_numero			= '$asn_numero',
									sscc_koodi			= '$sscc',
									saapumispvm 		= '$toimituspvm',
									vastaanottaja 		= '$vastaanottaja',
									tilausnumero 		= '$tuotteelta_tilausno',
									paketinnumero		= '$paketti_nro',
									paketintunniste 	= '$laatikkoind',
									lahetyslistannro 	= '$pakkauslista',
									toim_tuoteno		= '$tuote',
									toim_tuoteno2		= '$tuote2',
									kappalemaara		= '$kpl',
									tilausrivinpositio	= '$tilausrivinpositio',
									laatija 			= '$kukarow[kuka]',
									luontiaika 			= now()";
					$result = pupe_query($sqlinsert);
					$tunnus_liitetiedostoon = mysql_insert_id();
				}
			}

			// Tämä on paketti-elementti
			if ($key == "Package") {
				$paketti_nro++;				

				$parameters = array(
					"tavarantoimittajanumero" 	=> $tavarantoimittajanumero,
					"asn_numero"				=> $asn_numero, 
					"toimituspvm"				=> $toimituspvm, 
					"vastaanottaja"				=> $vastaanottaja, 
					"pakkauslista"				=> $pakkauslista, 
					"pakettinumero"				=> $pakettinumero,	
					);
				
				if (isset($element->PkgId->PkgIdentNumber) and $tavarantoimittajanumero != "123007") {	
						$laatikko = (string) $element->PkgId->PkgIdentNumber;
						$laatikko = utf8_decode($laatikko);
						$koodi = $laatikko;

						if (($tavarantoimittajanumero == "123001" or $tavarantoimittajanumero == "123049" or $tavarantoimittajanumero == "123108") and strlen($laatikko) >10) {
							$parameters["sscc"] = $laatikko;
							$parameters["laatikkoind"] = substr($laatikko,10);
						}
						elseif (($tavarantoimittajanumero == "123001" or $tavarantoimittajanumero == "123108") and strlen($laatikko) < 10) {
							$parameters["sscc"] = $laatikko;
							$parameters["laatikkoind"] = '0'.$laatikko;
						}
						elseif ($tavarantoimittajanumero == "123342") {
							$parameters["sscc"] = $laatikko;
							$parameters["laatikkoind"] = substr($laatikko,8);
						}
						else {
							$parameters["sscc"]			= $koodi;
							$parameters["laatikkoind"]	= $laatikko;
						}
				}
				elseif ($tavarantoimittajanumero == "123441" and !isset($element->PkgId->PkgIdentNumber)) {
					$parameters["laatikkoind"]	= $asn_numero;
					$parameters["sscc"]			= $asn_numero;
					
				}
				elseif ($tavarantoimittajanumero == "123007") {
					// emme luekkaan tämän toimittajan PacketKind-arvoa, vaan generoidaan tietyistä arvoista.
					$laatikko = (int) $element->PkgNumber;
					$parameters["laatikkoind"]	= $asn_numero.$laatikko;
					$parameters["sscc"]			= $asn_numero.$laatikko;
				}
				elseif ($tavarantoimittajanumero == "123220" or $tavarantoimittajanumero == "123080") {
					$parameters["laatikkoind"]	= $asn_numero;
					$parameters["sscc"]			= $asn_numero;
				}
				else {
					$parameters["laatikkoind"]	= $asn_numero;
					$parameters["sscc"]			= $asn_numero;
				}

				loop_packet($element, $parameters);
			}
		}
		
		return $tunnus_liitetiedostoon;
	}

	if ($handle = opendir($teccomkansio)) {

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($teccomkansio."/".$file)) {

				$tiedosto = $teccomkansio."/".$file;

				// Otetaan tiedoston sisältö muuttujaan
				$xml_content = file_get_contents($tiedosto);
				// Poistetaan kaikki "non-printable" merkit
				$xml_content = preg_replace("/[^[:print:]]/", "", $xml_content);
				// Korvataan "UTF-16" string "UTF-8":lla, koska XML pitää olla UTF-8
				$xml_content = str_replace("\"UTF-16\"", "\"UTF-8\"", $xml_content);
				// Muutetaan muuttujan enkoodaus vielä UTF-8:ksi
				$xml_content = iconv("UTF-8", "UTF-8//IGNORE", $xml_content);
				// Tehdään muuttujasta XML olio
				$xml = @simplexml_load_string($xml_content);

				$tiedosto_sisalto = file_get_contents($tiedosto);
				$tiedosto_sisalto = mysql_real_escape_string($tiedosto_sisalto);

				if ($xml !== FALSE) {

					// $tavarantoimittajanumero ja $asn_numero arvoa pitää olla tai ei tule toimimaan.
					$tavarantoimittajanumero = (string) $xml->DesAdvHeader->SellerParty->PartyNumber;
					$tavarantoimittajanumero = utf8_decode($tavarantoimittajanumero);

					if (strtoupper($tavarantoimittajanumero) == "ELRING") {
						$tavarantoimittajanumero = "123312";
					}
					elseif (strtoupper($tavarantoimittajanumero) == "BOSCH" OR strtoupper($tavarantoimittajanumero) == "AA_FI") {
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

					$parameters = array(
						"tavarantoimittajanumero" 	=> $tavarantoimittajanumero,
						"asn_numero"				=> $asn_numero, 
						"toimituspvm"				=> $toimituspvm, 
						"vastaanottaja"				=> $vastaanottaja, 
						"pakkauslista"				=> $pakkauslista, 
						);
					
					// tässä kohdassa tarkistetaan että löytyykö ASN-sanoma jo kannasta, jos ei niin kutsutaan rekursiivista funkkaria.
				
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
							// loop_packet funktio tekee kaikki lisäykset asn-sanomatauluun ja palauttaa viimeisen lisätyn rivin mysql_id() joka laitetaan liitetiedostoon.
							$tunnus_liitetiedostoon = loop_packet($xml, $parameters);
						
							$filesize = strlen($tiedosto_sisalto);

							$tecquery = "	INSERT INTO liitetiedostot SET
											yhtio    			= '$kukarow[yhtio]',
											liitos   			= 'asn_sanomat',
											liitostunnus 		= '$tunnus_liitetiedostoon',
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