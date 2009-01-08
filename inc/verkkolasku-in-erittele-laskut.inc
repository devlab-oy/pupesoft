<?php
	function erittele_laskut($file) {

		global $laskut;

		$luotiinlaskuja = 0;
		$boob="";
		$type="";
		
		$poistettavat=array('/','.',':'); //Merkit, jotka poistetaan filennimestä
		
		echo "Aloitan laskujen erittelyn\n";
		foreach (file($file) as $ln) {

			$ln = trim($ln); // varmistaan että ei jää roskia
			//Haetaan Finvoicen tunnusmerkkejä
			if ((strpos($ln, "<SOAP-ENV:Envelope ")!==false) and (strpos($ln, ">")>0)) {
				//echo "Löydettiin SOAP-ENV:Envelope aloitus!\n";
				$type 	= "SOAP";
			}
			if ((strpos($ln, "<Finvoice Version=\"1.2\">")!==false) or (strpos($ln, "<Finvoice Version=\"1.1\">")!==false)) {
				//echo "Löydettiin uusi Finvoice aloitus!\n";
				$type = "FINVOICE";

				// 	lisätään varmasti oikeat DOCTYPE määritys
				//	Tämä siksi että haluamme käyttää paikallista kuvausta eikä ladata kuvausta verkosta!
				$ln = "<?xml version=\"1.0\" encoding=\"ISO-8859-15\"?>\n<!DOCTYPE Finvoice SYSTEM \"Finvoice.dtd\">\n<?xml-stylesheet type=\"text/xsl\" href=\"Finvoice.xsl\"?>\n".$ln;
			}
			//	Meillä oli SOAP kehys, täältä me saadaan EBID sender-messageid
			if (isset($type) and $type == "SOAP" and strpos($ln, "<eb:MessageId>")!==false) {
				$ebid = substr($ln,14);
				$ebid = substr($ebid,0,strpos($ebid,"<"));
				$ebid = str_replace($poistettavat,"_",$ebid);
				
				//echo "Löysin messageheaderin $ln --> $ebid\n";
			}
			
			if ($type == "FINVOICE") $boob .= $ln."\n"; // Rakennetaan varsinainen tiedosto
			
			// 	Tiedostosta löytyi finvoiceaineisto
			if ($type == "FINVOICE" and strpos($ln, "</Finvoice>")!==false) {

				//echo "Finvoiceaineisto päättyy, tallennetaan aineisto\n";

				if ($ebid == "") {
					//echo "Aineistossa ei ollut SOAP kehystä, generoidaan ebid\n";
					$ebid = "gen".substr(md5(rand()),0,20);
				}

				$filenimi = "finvoice-$ebid.xml";
				$save = file_put_contents($laskut."/".$filenimi, $boob);

				if ($save !== false) {
					//echo "Laskutiedosto '$filenimi' tallennettu\n";
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
