<?php
	function erittele_laskut($file) {

		global $laskut;

		$luotiinlaskuja = 0;
		$type = "";
		$boob = "";
		$soap = "";
		$ebid = "";

		$poistettavat = array('/','.',':'); //Merkit, jotka poistetaan filennimestä

		foreach (file($file) as $ln) {

			$ln = trim($ln); // varmistaan että ei jää roskia

			//Haetaan Finvoicen tunnusmerkkejä
			if (preg_match("/\<SOAP\-ENV\:Envelope.*\>/i", $ln) > 0) {
				$type = "SOAP";
			}

			if (preg_match("/\<Finvoice.*?Version\=\"1\.[0-3]\".*?\>/i", $ln) > 0) {
				$type = "FINVOICE";

				if (strpos($ln, "Version=\"1.3\"") !== FALSE) {
					$finvoice_dtd = "datain/Finvoice_1.3.dtd";
					$finvoice_xsl = "datain/Finvoice_1.3.xsl";
				}
				else {
					$finvoice_dtd = "datain/Finvoice.dtd";
					$finvoice_xsl = "datain/Finvoice.xsl";
				}

				// 	lisätään varmasti oikeat DOCTYPE määritys
				//	Tämä siksi että haluamme käyttää paikallista kuvausta eikä ladata kuvausta verkosta!
				$ln = "<?xml version=\"1.0\" encoding=\"ISO-8859-15\"?>\n<!DOCTYPE Finvoice SYSTEM \"$finvoice_dtd\">\n<?xml-stylesheet type=\"text/xsl\" href=\"$finvoice_xsl\"?>\n".$ln;
			}

			//	Meillä oli SOAP kehys, täältä me saadaan EBID sender-messageid
			if (isset($type) and $type == "SOAP" and preg_match("/\<eb\:MessageId\>(.*?)\<\/eb\:MessageId\>/i", $ln, $messageid) > 0) {
				$ebid = $messageid[1];
				$ebid = str_replace($poistettavat,"_",$ebid);
			}

			if ($type == "SOAP") $soap .= $ln; // Tässä soap kehys
			if ($type == "FINVOICE") $boob .= $ln."\n"; // Rakennetaan varsinainen finvoice tiedosto

			// 	Tiedostosta löytyi finvoiceaineisto
			if ($type == "FINVOICE" and stripos($ln, "</Finvoice>") !== FALSE) {

				// Oteaan SOAP-kehyksestä Receiver PartyId koska se ei nyt aina tulekaan niin kauniisti Finvoicessa, esim jos toimittajalla on huono asiakasrekisteri
				if ($soap != "" and preg_match("/\<eb\:To\>\<eb\:PartyId\>(.*?)\<\/eb\:PartyId\>\<eb\:Role\>Receiver\<\/eb\:Role\>\<\/eb\:To\>/i", $soap, $receiverparty) > 0) {

					// Jos tieto löytyy
					if (trim($receiverparty[1]) != "") {
						// Löytyykö BuyerOrganisationUnitNumber-segmentti
						if (preg_match("/\<BuyerOrganisationUnitNumber\>.*?\<\/BuyerOrganisationUnitNumber\>/i", $boob, $buyermatch)) {
							// Jos löytyy niin muokataan
							$boob = str_replace($buyermatch[0], "<BuyerOrganisationUnitNumber>".$receiverparty[1]."</BuyerOrganisationUnitNumber>", $boob);
						}
						else {
							// Muuten lisätään
							$boob = str_replace("</BuyerPartyDetails>", "</BuyerPartyDetails>\n<BuyerOrganisationUnitNumber>".$receiverparty[1]."</BuyerOrganisationUnitNumber>", $boob);
						}
					}
				}

				if ($ebid == "") {
					$ebid = "gen".substr(md5(rand()),0,20);
				}

				$path_parts = pathinfo($file);
				$filenimi = "finvoice-{$path_parts["filename"]}-$ebid.xml";
				$save = file_put_contents($laskut."/".$filenimi, $boob);

				if ($save !== FALSE) {
					$luotiinlaskuja++;
				}
				else {
					echo "Laskutiedoston tallennuksessa tapahtui virhe!\n";
				}

				// Haetaan taas uutta aineistoa
				$type = "";
				$boob = "";
				$soap = "";
				$ebid = "";
			}
		}

		$xml = @simplexml_load_file($file);

		if ($xml !== FALSE) {

			$i = 1;

			// Unikon laskuaineisto
			if (isset($xml->LedgerJournalTable->LedgerJournalName) and $xml->LedgerJournalTable->LedgerJournalName == "ORAO") {

				$vakio_alku = '<?xml version="1.0"?><LedgerJournal><LedgerJournalTable><LedgerJournalName>ORAO</LedgerJournalName><Name>Ostolaskut Unikosta</Name>';
				$vakio_loppu = '</LedgerJournalTable></LedgerJournal>';

				// Lisätään ekaan tiedostoon alkutagit
				$xmlnimi = "ORAO-$i-".md5(uniqid(mt_rand(), true)).".xml";
				file_put_contents($laskut."/".$xmlnimi, $vakio_alku);

				foreach ($xml->LedgerJournalTable->LedgerJournalTrans as $lasku) {

					if ((string) $lasku->AccountType == "Vend" and $i > 1) {
						// Kun toimittaja vaihtuu, kirjoitetaan lopputagit...
						file_put_contents($laskut."/".$xmlnimi, $vakio_loppu, FILE_APPEND);

						// ...ja keksitään uusi tiedostonimi...
						$xmlnimi = "ORAO-$i-".md5(uniqid(mt_rand(), true)).".xml";

						// ...ja kirjotetaan sinne alkutagit
						file_put_contents($laskut."/".$xmlnimi, $vakio_alku.$lasku->asXML());
					}
					else {
						file_put_contents($laskut."/".$xmlnimi, $lasku->asXML(), FILE_APPEND);
					}

					$i++;
				}

				// Lisätään viimeiseen tiedostoon lopputagit
				file_put_contents($laskut."/".$xmlnimi, $vakio_loppu, FILE_APPEND);
				$luotiinlaskuja++;
			}
			elseif (isset($xml->Document->attributes()->Type) and $xml->Document->attributes()->Type == "Invoice") {
				// Teccom laskuaineisto

				$path_parts = pathinfo($file);

				rename($file, "{$laskut}/teccominvoice_{$path_parts['basename']}");
			}
		}

		return $luotiinlaskuja;
	}
?>