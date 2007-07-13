<?php

	/*
		Pilkotaan finvoice aineistosta laskut omiksi tiedostoikseen

		Paluatetaan luotujen laskujen lukum‰‰r‰

		Koska tuossa SOAP kehyksen k‰sittelyss‰ oli jotain mystist‰, ilmeisesti johtuen tuosta bugista joten pelkk‰ xml-load ei fudannut oikein koodataan uudestaan kun homma toimii oikein
		TODO simple-xml voisi hoitaa t‰m‰n koko pashan
	*/

	function erittele_laskut($file) {

		global $laskut;

		$luotiinlaskuja = 0;
		unset($boob);
		unset($type);

		foreach (file($file) as $ln) {

			$ln = trim($ln); // varmistaan ett‰ ei j‰‰ roskia

			//	Haetaan Finvoicen tunnusmerkkej‰
			if (isset($type) and $type == "") {

				if (strpos($ln, "<SOAP-ENV:Envelope ") and strpos($ln, ">")) {
					echo "Lˆydettiin SOAP-ENV:Envelope aloitus!\n";
					$type 	= "SOAP";

					//	t‰m‰ on bugi PHP ohjelmistossa, t‰ll‰hetkell‰ korjaus on jo CVS:ss‰ mutta ei ilmeisesti viel‰ jakelussa
					//	n‰m‰ kikkareet tagin j‰lkeen aiheuttavat virheit‰ joten lis‰t‰‰n t‰h‰n toimiva, mutta varoituksia antava rivi tilalle
					$ln = "<SOAP-ENV:Envelope>";
				}

				if (strpos($ln, "<Finvoice Version=\"1.2\">") or strpos($ln, "<Finvoice Version=\"1.1\">")) {
					echo "Lˆydettiin uusi Finvoice aloitus!\n";
					$type = "FINVOICE";

					// 	lis‰t‰‰n varmasti oikeat DOCTYPE m‰‰ritys
					//	T‰m‰ siksi ett‰ haluamme k‰ytt‰‰ paikallista kuvausta eik‰ ladata kuvausta verkosta!
					$ln = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<!DOCTYPE Finvoice SYSTEM \"Finvoice.dtd\">\n<?xml-stylesheet type=\"text/xsl\" href=\"Finvoice.xsl\"?>\n".$ln;
				}
			}

			// samaa bugikorjausta kuin edell‰..
			if (isset($type) and $type == "SOAP" and strpos($ln, "eb:MessageHeader ")) {
				$ln = "<eb:MessageHeader>";
			}

			// meill‰ on tietueen tyyppi tiedossa, lis‰t‰‰n se muuttujaan
			if (isset($type) and $type != "") {
				$boob .= $ln."\n";
			}

			// 	etsit‰‰n lopputagia
			//	Meill‰ oli SOAP kehys, t‰‰lt‰ me saadaan EBID valittaja@sender-messageid
			if (isset($type) and $type == "SOAP" and strpos($ln, "</SOAP-ENV:Envelope>")) {

				//	Ladataan vaan t‰m‰ luotu aineisto
				$xml = simplexml_load_string($boob);

				// Etsit‰‰n l‰hett‰j‰
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

			// 	Tiedostosta lˆytyi finvoiceaineisto
			if (isset($type) and $type == "FINVOICE" and strpos($ln, "</Finvoice>")) {

				echo "Finvoiceaineisto p‰‰ttyy, tallennetaan aineisto\n";

				if ($ebid == "") {
					echo "Aineistossa ei ollut SOAP kehyst‰, generoidaan ebid\n";
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