<?php

// Orders 91.1 tulee ostajan tiedot ennen vastaanottajaa, joten meid‰n pit‰‰ heti t‰ss‰ vaiheessa kaivaa jo vastaanottajan tiedot
$order_tietosisalto = file_get_contents($filename);

$order_tietosisalto = str_replace("\n","", $order_tietosisalto);
$order_tietosisalto = explode("'", $order_tietosisalto);

// Toimitus ja tuotetiedot
$arska = array();

$current_customer = '';
$current_product = '';
$current_product_price = '';
$current_product_desc = '';
$delivery_date = '';
$edi_rivi_temp = array();

foreach ($order_tietosisalto as $key => $value) {

	$value = trim($value);

	if ($value != "") {

		if (substr($value, 0, 3) == "LOC") {
			// Toimitusosoite
			$value = str_replace("LOC+8", "NAD+DP", $value);
			$arska[$value][] = array();
			$current_customer = $value;
		}
		elseif (substr($value, 0, 3) == "LIN") {
			// Toimitusosoite - Tuotteet
			// Tuotteen vaihtuessa nollataan current_customer ja price
			if ($current_product != $value) {
				$current_customer = '';
				$current_product_price = '';
			}

			$arska[$current_customer][] = $value;
			$current_product = $value;
		}
		elseif (substr($value, 0, 6) == "NAD+CZ") {
			// Joissakin keisseiss‰ NAD+SE puuttuu mutta tilalla on NAD+CZ
			$value = str_replace("NAD+CZ", "NAD+SE", $value);
			$edi_rivi_temp[] = $value;
		}
		elseif (substr($value, 0, 3) == "PRI") {
			// Tuotehinta
			if (!empty($current_product)) $current_product_price = $value;
		}
		elseif (substr($value, 0, 3) == "IMD") {
			// Tuotekuvaus
			if (!empty($current_product)) $current_product_desc = $value;
		}
		elseif (substr($value, 0, 3) == "DTM") {
			// Tuotekohtainen toimitusaika
			if (!empty($current_product)) $delivery_date = $value;
		}
		elseif (substr($value, 0, 3) == "QTY") {
			// Toimitusosoite - Tuote -> Kpl - Hinta
			if (!empty($current_product) and !empty($current_customer)) $arska[$current_customer][][$current_product] = array($value, $current_product_price, $current_product_desc, $delivery_date);
		}
		else {
			// Kaikki tilauksen muut rivit talteen
			$edi_rivi_temp[] = $value;
		}

	}
}

unset($order_tietosisalto);

// Etsit‰‰n otsikon p‰‰tt‰v‰n rivin index
$found = array_search("UNS+D", $edi_rivi_temp);
$otsikonloppu = $edi_rivi_temp[$found];
unset($edi_rivi_temp[$found]);

// Erotellaan sanoman alku ja loppu
$edi_rivi_loppu = array_splice($edi_rivi_temp, $found);
$edi_rivi_alku = array_diff($edi_rivi_temp, $edi_rivi_loppu);

$edi_rivi_alku = implode("\n", $edi_rivi_alku);
$edi_rivi_loppu = implode("\n", $edi_rivi_loppu);

$kasitellaan_splitattuja_crossdock_edifact911 = "JOO";

foreach ($arska as $key => &$value) {
	
	// Tuunataan jokaiselle toimitusosoitteelle oma editilaus ja ajetaan sis‰‰n
	if ($key != '') {
		// Laitetaan toimitusosoite viel‰ header-segmenttiin($edi_rivi_alku) ennen headerin lopputagia
		$edi_rivi = $edi_rivi_alku."\n$key\n";
		$edi_rivi .= $otsikonloppu."\n";

		foreach ($value as $k => $v) { 
			foreach ($v as $kii => $kippari) {
				// EAN-koodi
				$edi_rivi .= $kii."\n";
				// Tuotekuvaus
				$edi_rivi .= $kippari[2]."\n";
				// Kappalemaara
				$edi_rivi .= $kippari[0]."\n";
				// Toimituspaivamaara
				$edi_rivi .= $kippari[3]."\n";
				// T‰ll‰ segmentill‰ kerrotaan ett‰ yksitt‰isen tilausrivin tiedot loppuu ja voidaan lis‰t‰ se tilaukselle
				$edi_rivi .= "PCD+12\n";
			}
		}
		// Lis‰t‰‰n koko sanoman lopputagit
		$edi_rivi .= $edi_rivi_loppu;
		
		// Luodaan tiedosto
		$filename = "/tmp/ediorders911_crossdock_split".uniqid(time()."_").".txt";
		file_put_contents($filename, $edi_rivi);

		// Splitist‰ oletaan takaisin lukukelpoisia edifact 91.1 tilauksia editilaus_in.incille
		$edi_tyyppi = $tyyppi = "edifact911";

		// avataan tiedosto uudestaan
		if (!$fd = fopen($filename, "r")) die("Filen '$filename' ".t("avaus ep‰onnistui")."!\n");

		//jotta filenimi ei varmasti katoaisi matkalla, t‰m‰ ylikirjoittaa edellisen mailfilen.
		$mailfile = $filename;
		require("editilaus_in.inc");

		$edi_rivi = $filename = $mailfile = '';
	}
}
