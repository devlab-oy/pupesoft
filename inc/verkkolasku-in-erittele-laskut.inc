<?php
	function erittele_laskut($file) {

		global $laskut;

		$luotiinlaskuja = 0;
		$boob			= "";
		$type			= "";

		$poistettavat = array('/','.',':'); //Merkit, jotka poistetaan filennimest‰

		foreach (file($file) as $ln) {

			$ln = trim($ln); // varmistaan ett‰ ei j‰‰ roskia

			//Haetaan Finvoicen tunnusmerkkej‰
			if (preg_match("/\<SOAP\-ENV\:Envelope.*\>/", $ln) > 0) {
				$type 	= "SOAP";
			}

			if (preg_match("/\<Finvoice Version\=\"1\.[0-3]\"\>/", $ln) > 0) {
				$type = "FINVOICE";

				// 	lis‰t‰‰n varmasti oikeat DOCTYPE m‰‰ritys
				//	T‰m‰ siksi ett‰ haluamme k‰ytt‰‰ paikallista kuvausta eik‰ ladata kuvausta verkosta!
				$ln = "<?xml version=\"1.0\" encoding=\"ISO-8859-15\"?>\n<!DOCTYPE Finvoice SYSTEM \"datain/Finvoice.dtd\">\n<?xml-stylesheet type=\"text/xsl\" href=\"datain/Finvoice.xsl\"?>\n".$ln;
			}

			//	Meill‰ oli SOAP kehys, t‰‰lt‰ me saadaan EBID sender-messageid
			if (isset($type) and $type == "SOAP" and preg_match("/\<eb\:MessageId\>(.*?)\<\/eb\:MessageId\>/", $ln, $messageid) > 0) {
				$ebid = $messageid[1];
				$ebid = str_replace($poistettavat,"_",$ebid);
			}

			if ($type == "FINVOICE") $boob .= $ln."\n"; // Rakennetaan varsinainen tiedosto

			// 	Tiedostosta lˆytyi finvoiceaineisto
			if ($type == "FINVOICE" and strpos($ln, "</Finvoice>") !== FALSE) {
				if ($ebid == "") {
					$ebid = "gen".substr(md5(rand()),0,20);
				}

				$filenimi = "finvoice-$ebid.xml";
				$save = file_put_contents($laskut."/".$filenimi, $boob);

				if ($save !== false) {
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