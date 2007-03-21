<?php

	/*
		Pilkotaan finvoice aineistosta laskut omiksi tiedostoikseen

		Paluatetaan luotujen laskujen lukumäärä

		Koska tuossa SOAP kehyksen käsittelyssä oli jotain mystistä, ilmeisesti johtuen tuosta bugista joten pelkkä xml-load ei fudannut oikein koodataan uudestaan kun homma toimii oikein
		TODO simple-xml voisi hoitaa tämän koko pashan
	*/

	function erittele_laskut($file) {

		global $laskut;

		$luotiinlaskuja = 0;
		unset($boob);
		unset($type);

		foreach (file($file) as $ln) {

			$ln = trim($ln); // varmistaan että ei jää roskia

			//	Haetaan Finvoicen tunnusmerkkejä
			if (isset($type) and $type == "") {

				if (strpos($ln, "<SOAP-ENV:Envelope ") and strpos($ln, ">")) {
					echo "Löydettiin SOAP-ENV:Envelope aloitus!\n";
					$type 	= "SOAP";

					//	tämä on bugi PHP ohjelmistossa, tällähetkellä korjaus on jo CVS:ssä mutta ei ilmeisesti vielä jakelussa
					//	nämä kikkareet tagin jälkeen aiheuttavat virheitä joten lisätään tähän toimiva, mutta varoituksia antava rivi tilalle
					$ln = "<SOAP-ENV:Envelope>";
				}

				if (strpos($ln, "<Finvoice Version=\"1.2\">") or strpos($ln, "<Finvoice Version=\"1.1\">")) {
					echo "Löydettiin uusi Finvoice aloitus!\n";
					$type = "FINVOICE";

					// 	lisätään varmasti oikeat DOCTYPE määritys
					//	Tämä siksi että haluamme käyttää paikallista kuvausta eikä ladata kuvausta verkosta!
					$ln = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<!DOCTYPE Finvoice SYSTEM \"Finvoice.dtd\">\n<?xml-stylesheet type=\"text/xsl\" href=\"Finvoice.xsl\"?>\n".$ln;
				}
			}

			// samaa bugikorjausta kuin edellä..
			if (isset($type) and $type == "SOAP" and strpos($ln, "eb:MessageHeader ")) {
				$ln = "<eb:MessageHeader>";
			}

			// meillä on tietueen tyyppi tiedossa, lisätään se muuttujaan
			if (isset($type) and $type != "") {
				$boob .= $ln."\n";
			}

			// 	etsitään lopputagia
			//	Meillä oli SOAP kehys, täältä me saadaan EBID valittaja@sender-messageid
			if (isset($type) and $type == "SOAP" and strpos($ln, "</SOAP-ENV:Envelope>")) {

				//	Ladataan vaan tämä luotu aineisto
				$xml = simplexml_load_string($boob);

				// Etsitään lähettäjä
				$from = $xml->xpath('Header/MessageHeader/From');

				if (sizeof($from) > 0) {
					foreach ($from as $party) {
						if (in_array("Sender", $party->xpath('Role'))) {
							$xsender = $party->xpath('PartyId');
							$sender = $xsender[0];
						}
						if (in_array("Intermediator", $party->xpath('Role'))) {
							$xvalittaja	= $party->xpath('PartyId');
							$valittaja = $xvalittaja[0];
						}
					}
				}

				$xid 	= $xml->xpath('Header/MessageHeader/MessageData/MessageId');
				$id = $xid[0];

				// SOAP headeri oli,
				if ($valittaja == "") $valittaja = "UK".substr(md5(rand()),0,5);
				if ($sender == "") $sender = "UK".substr(md5(rand()),0,5);
				if ($id == "") $id = date("Y-m-d-H-i-s");

				$ebid = $sender."@".$valittaja."-".$id;

				// Haetaan taas uutta aineistoa
				$type = "";
				$boob = "";
			}

			// 	Tiedostosta löytyi finvoiceaineisto
			if (isset($type) and $type == "FINVOICE" and strpos($ln, "</Finvoice>")) {

				echo "Finvoiceaineisto päättyy, tallennetaan aineisto\n";

				if ($ebid == "") {
					echo "Aineistossa ei ollut SOAP kehystä, generoidaan ebid\n";
					$ebid = date("Y-m-d-H-i-s");
				}

				$filenimi = "finvoice-$ebid.xml";
				$save = file_put_contents($laskut."/".$filenimi, $boob);

				if ($save !== false) {
					echo "Laskutiedosto '$filenimi' tallennettu\n";
					$luotiinlaskuja++;
				}
				else {
					echo "Laskutiedoston tallennuksessa tapahtui virhe!\n";
				}

				// Haetaan taas uutta aineistoa
				$type = "";
				$boob = "";
				$ebid = "";
			}
		}

		return $luotiinlaskuja;
	}

?>