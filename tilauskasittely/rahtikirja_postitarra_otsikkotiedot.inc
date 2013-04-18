<?php

	//Luodaan tarvittavat muuttujat rahtikirja_postitarra.inc ja rahtikirja_postitarra_pdf.inc

	if ($phpnimi == "rahtikirja_custom.php") {
		$postirow = $osoitelappurow;
		$rakir_row = $osoitelappurow;
	}
	else {
		$query = "SELECT * from lasku where yhtio = '$kukarow[yhtio]' and tunnus in ($otunnukset) order by tunnus limit 1";
		$tempr = mysql_query($query) or pupe_error($query);
		$postirow = mysql_fetch_array($tempr);
	}

	// haetaan varaston osoitetiedot, käytetään niitä lähetystietoina
	$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa
				FROM varastopaikat
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$postirow[varasto]'";
	$tempr = mysql_query($query) or pupe_error($query);
	$postirow_varasto = mysql_fetch_array($tempr);

	// jos varastolle on annettu joku osoite, käytetään sitä
	if ($postirow_varasto["nimi"] != "") {
		$postirow["yhtio_nimi"]     = $postirow_varasto["nimi"];
		$postirow['yhtio_nimitark']	= $postirow_varasto["nimitark"];
		$postirow["yhtio_osoite"]   = $postirow_varasto["osoite"];
		$postirow["yhtio_postino"]  = $postirow_varasto["postino"];
		$postirow["yhtio_postitp"]  = $postirow_varasto["postitp"];
		$postirow["yhtio_maa"]      = $postirow_varasto["maa"];
	}

	// jos meillä on printterin takana joku spessu osoitetieto niin käytetään sen tietoja lähettäjän tietoina
	if ($print["nimi"] != "") {
		$postirow["yhtio_nimi"]    = $print["nimi"];
		$postirow["yhtio_osoite"]  = $print["osoite"];
		$postirow["yhtio_postino"] = $print["postino"];
		$postirow["yhtio_postitp"] = $print["postitp"];
	}

	if ($postirow['rahtisopimus']=='') $postirow['rahtisopimus'] = "000000";
	if ($toitarow['sopimusnro']=='')   $toitarow['sopimusnro']	 = "000000";

	// postiennakko
	if ($rakir_row["jv"] != '' or $mehto['jv'] != '') {
		$postiennakkomaara  = "$yhteensa $postirow[valkoodi]";
		if ($yhtiorow["pankkiiban1"] != '') {
			$postiennakkotilino = "$yhtiorow[pankkiiban1]";
		}
		else {
			$postiennakkotilino = "$yhtiorow[pankkitili1]";
		}
		$postiennakkobic	= "$yhtiorow[pankkiswift1]";
		$postiennakkoviite  = "$viite";
	}
	else {
		$postiennakkomaara  = "";
		$postiennakkotilino = "";
		$postiennakkobic	= "";
		$postiennakkoviite  = "";
	}

	// Lisäpalvelut
	#3101	1 	Postiennakko  13)
	#3103	3 	Maksaja muu kuin lähettäjä  1)
	#3104	4 	Erilliskäsiteltävä
	#3105	5 	Kirjaaminen
	#3106	6 	Lauantaijakelu
	#3107	7 	Kuljetusyksikkö  (8)
	#
	#3108	8 	Vastaanottajan nimitiedon tallennus  10)
	#3115	15	Täsmäpaikkajakelu  1)
	#3118	18	Vaihtokuljetus  1)
	#3119	19	Hyllytyspalvelu  1)
	#3127	27	Laiteasennus  1) 3)
	#3131	31	2. laiteasennus  1) 3)
	#3132	32	3. Laiteasennus  1) 3)
	#3133	33	Iso laiteasennus  1) 3)
	#3134	34	Tietokoneasennus  1) 3)
	#3135	35	TV:n seinäasennus  1) 3)
	#3136	36	Kantoapu  1) 3)
	#3137	37	Kuljetus kierrätykseen  1) 3)
	#3138	38	Purku pakkauksesta  1) 3)
	#3139	  	Sähköinen saapumisilmoitus 1) 6)
	#3140	  	Vaihtoehtoinen noutopiste  1) 11)

	# 1)	Palvelussa on käytettävä EDI-sanomaa.
	# 2)	Tuotekoodi tulostetaan osoitekorttiin selväkielisenä tekstinä ja code 128 viivakoodina, tuotekoodi ilmoitetaan myös EDI-sanomassa ilman 2W-alkutunnistetta.
	# 3)	Asennus lisäpalvelujen käyttölogiikka avataan erillisessä ohjeessa.
	# 4)	Lisäpalvelukoodi ilmoitetaan EDI-sanomassa, lisäpalvelukoodit 3115 - 3138 tulostetaan osoitekortin alaosaan sulkuihin lisäpalvelun nimen perään.
	# 5)	Tulostetaan osoitekortin lisäpalveluruutuun.
	# 6)	Sähköinen saapumisilmoitus on Economy 16 ja Express Point 00/16 palveluihin kuuluva valinnainen ominaisuus.
	#		Osoitekorttiin tulostetaan EDI SSI-merkintä  ja Saapumisilmoitus korvataan Kuittiosalla tai irrotettava osa voidaan sovittaessa jättää pois.
	# 		Sähköinen saapumisilmoitus ilmoitetaan EDI sanomassa lisäpalvelukoodilla 3139.
	# 7)	Vastaanottajan puhelinnumero tulee tulostaa osoitekorttiin ja se ilmoitetaan myös EDI-sanomassa.
	# 8)	Pakollinen lisäpalvelu
	# 9)	Palvelussa tulee käyttää Sähköinen saapumisilmoitus palvelua (EDI SSI).
	# 10)	Vastaanottajan nimitiedon tallennus on palveluun kuuluva ominaisuus, osoitekorttiin ei tulosteta palveluun liittyviä merkintöjä, poikkeuksena Express Point ja Economy palvelu.
	# 11)	Vaihtoehtoinen noutopiste palvelussa lähettäjän tulostusohjelmistossa tulee olla asiaan liittyvä noutopaikkarekisteri.
	#		Osoitekortilla vaihtoehtoinen noutopiste näkyy vain osoitemerkinnöissä , katso malli.
	# 		Vaihtoehtoinen noutopiste palvelussa EDI-sanomassa ilmoitetaan lisäpalvelukoodi 3140, se poistaa Sähköinen saapumisilmoitus palveluun kuuluvan iPost ominaisuuden.
	# 12)	Itella Green kuuluu palvelujemme ominaisuuteen. "Itella Green - ilmastoystävällinen kuljetus" tai "Itella Green - climate friendly delivery" nimen voi tulostaa
	#		12 pisteen lihavoidun kirjaimin osoitekortin alaosan Lisätiedot -kenttään.
	# 13)	Postiennakkolähetyserän (edellyttää EDI-tietoja) jokaiseen lähetykseen kiinnitetään osoitekortti täydellisenä yksilöivää lähetystunnusta lukuun ottamatta samansisältöisenä.
	#		Postiennakkoerän kokonaissumma ja kokonaiskappalemäärä (kpl st. kenttään)
	# 	 	merkitään erän jokaiseen osoitekorttiin. Postiennakkolähetyserän EDI-sanomassa ilmoitetaan myös lisäpalvelutunnus 3102. Kappalemäärärajaus paketeille on 2-10 ja kuljetusyksiköille 2-99.
	# HUOM:	Samalle vastaanottajalle osoitetun lähetyserän (MPS, edellyttää EDI-tietoja) tietoja ei tulosteta osoitekorttiin,
	#		mutta ne ilmoitetaan EDI -sanomassa. Poikkeuksena Postiennakko, katso selite 13.

	$x = array();
	if ($yhteensa != '')				$x[]='1'; // Postiennakko
	if ($kollityht>1)					$x[]='2'; // Monipakettilähetys
	if ($rakir_row['merahti'] != 'K')	$x[]='3'; // Maksaja muu kuin lähettäjä
	if ($toitarow['lauantai'] != '')	$x[]='6'; // lauantaijakelu
	if ($toitarow['kuljyksikko'] != '')	$x[]='7'; // kuljetusyksikkökuljetus

	$selite_chk = $toitarow['virallinen_selite'] != '' ? $toitarow['virallinen_selite'] : $toitarow['selite'];

	// Oletuksena: Economy 16
	$keltainen 	= "Economy";
	$kuljetus  	= "";
	$keku 		= "16";

	if (strpos($selite_chk, "9")     !== FALSE) 				$keku = "9";
	if (strpos($selite_chk, "00")    !== FALSE) 				$keku = "00";
	if (strpos($selite_chk, "14")    !== FALSE) 				$keku = "14";
	if (strpos($selite_chk, "21")    !== FALSE) 				$keku = "21";
	if (strpos($selite_chk, "00/16") !== FALSE) 				$keku = "00/16";
	if (strpos($selite_chk, "Customer Return") !== FALSE) 		$keku = "ASIAKASPALAUTUS 14";
	if (strpos($selite_chk, "Undelivered Shipment") !== FALSE) 	$keku = "UNDELIVERED SHIPMENT";
	if (strpos($selite_chk, "Lisäarvokuljetus") !== FALSE) 		$keku = "LISÄARVOKULJETUS";
	if (strpos($selite_chk, "Priority Ulkomaa") !== FALSE) 		$keku = "PRIORITY ULKOMAA";

	// Jos on vaarallisia aineita, niin tulostetaan aina VAK/ADR-kortti
	if (count($vakit) > 0) {
		$keku		= "VV";
		$selite_chk	= "VAK/ADR";
	}

	if ($selite_chk != '') {
		$keltainen = str_replace($keku, '', $selite_chk);
		$kuljetus  = '';
	}

	#Express City 00			2W2124
	#Express Morning 9			2W2101
	#Express Business Day 14	2W2102
	#Express Point 00/16		2W2105
	#Economy 16					2W2103
	#Express Flex 21			2W2104
	#VAK/ADR 					2W2116
	#Lisäarvokuljetus  			2W2150
	#Asiakaspalautus 14			2W2108
	#Priority Ulkomaa			2W2015

	switch ($keku) {
		case "00":
			$tuoteviiva1="2W2124";
			$tuoteviiva2=">62W>52124";
			break;
		case "9":
			$tuoteviiva1="2W2101";
			$tuoteviiva2=">62W>52101";
			break;
		case "14":
			$tuoteviiva1="2W2102";
			$tuoteviiva2=">62W>52102";
			break;
		case "00/16":
			$tuoteviiva1="2W2105";
			$tuoteviiva2=">62W>52105";
			break;
		case "16":
			$tuoteviiva1="2W2103";
			$tuoteviiva2=">62W>52103";
			break;
		case "21":
			$tuoteviiva1="2W2104";
			$tuoteviiva2=">62W>52104";
			break;
		case "VV":
			$tuoteviiva1="2W2116";
			$tuoteviiva2=">62W>52116";
			break;
		case "LISÄARVOKULJETUS":
			$tuoteviiva1="2W2150";
			$tuoteviiva2=">62W>52150";
			break;
		case "ASIAKASPALAUTUS 14":
			$tuoteviiva1="2W2108";
			$tuoteviiva2=">62W>52108";
			break;
		case "UNDELIVERED SHIPMENT":
			$tuoteviiva1="2W2108";
			$tuoteviiva2=">62W>52108";
			break;
		case "PRIORITY ULKOMAA":
			$tuoteviiva1="2W2015";
			$tuoteviiva2=">62W>52015";
			break;
	}

	if ($toitarow['kuljyksikko'] != '') {
		$kuljetus  = "Kuljetusyksikkö";

		#Express City 00 kuljetusyksikkö			2W2124
		#Express Morning 9 kuljetusyksikkö			2W2143
		#Express Business Day 14 kuljetusyksikkö	2W2144
		#Express Flex 21 kuljetusyksikkö			2W2145
		#VAK/ADR kuljetusyksikkö  					2W2146
		#Lisäarvokuljetus kuljetusyksikkö			2W2149
		#Asiakaspalautus 14 kuljetusyksikkö 		2W2147

		switch ($keku) {
			case "00":
				$tuoteviiva1="2W2124";
				$tuoteviiva2=">62W>52124";
				break;
			case "9":
				$tuoteviiva1="2W2143";
				$tuoteviiva2=">62W>52143";
				break;
			case "14":
				$tuoteviiva1="2W2144";
				$tuoteviiva2=">62W>52144";
				break;
			case "21":
				$tuoteviiva1="2W2145";
				$tuoteviiva2=">62W>52145";
				break;
			case "VV":
				$tuoteviiva1="2W2146";
				$tuoteviiva2=">62W>52146";
				break;
			case "LISÄARVOKULJETUS":
				$tuoteviiva1="2W2149";
				$tuoteviiva2=">62W>52149";
				break;
			case "ASIAKASPALAUTUS 14":
				$tuoteviiva1="2W2147";
				$tuoteviiva2=">62W>52147";
				break;
			case "UNDELIVERED SHIPMENT":
				$tuoteviiva1="2W2147";
				$tuoteviiva2=">62W>52147";
				break;
			case "PRIORITY ULKOMAA":
				$tuoteviiva1="2W2015";
				$tuoteviiva2=">62W>52015";
				break;
		}
	}

	if (count($vakit) > 0) {
		// Tämä tyhjennetään kun tulostetaan VAK/ADR rahtikirja
		$keku = "";
	}

	if ($rahdinmaksaja == 'Lähettäjä') {
		$rahdinmaksaja		= "";
		$rahdinmaksajan_nr 	= "";
	}
	elseif(trim($rakir_row['rahtisopimus']) != "") {
		$rahdinmaksaja		= "Maksaja muu kuin lähettäjä. ";
		$rahdinmaksajan_nr 	= $rakir_row['rahtisopimus'];
	}
	else {
		$rahdinmaksaja		= "Maksaja muu kuin lähettäjä. ";
		$rahdinmaksajan_nr 	= "";
	}

	// Ulkomaan lähetykissä tarvitaan myös nelinumeroinen, asiakaskohtainen tunnushallintanumero
	if (strpos($toitarow['sopimusnro'], "@") !== FALSE) {
		list($toitarow['sopimusnro'], $toitarow['tunhalnro']) = explode("@", $toitarow['sopimusnro']);
	}

	$toitarow['sopimusnro'] = sprintf("%06s",$toitarow['sopimusnro']);	// sopimunumeron tulee olla kuus pitkä
	$sopnro  = $toitarow['sopimusnro']; // käytetään edi sanomassa

?>