<?php

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

// Etsit‰‰n otsikon p‰‰tt‰v‰n rivin index
$found = array_search("UNS+D", $edi_rivi_temp);
$otsikonloppu = $edi_rivi_temp[$found];
unset($edi_rivi_temp[$found]);
// Erotellaan sanoman alku ja loppu
$edi_rivi_loppu = array_splice($edi_rivi_temp, $found);
$edi_rivi_alku = array_diff($edi_rivi_temp, $edi_rivi_loppu);

$edi_rivi_alku = implode("\n", $edi_rivi_alku);
$edi_rivi_loppu = implode("\n", $edi_rivi_loppu);

foreach ($arska as $key => &$value) {
	
	// Tuunataan jokaiselle toimitusosoitteelle oma editilaus ja ajetaan sis‰‰n
	if ($key != '') {
		// Laitetaan toimitusosoite viel‰ header-segmenttiin($edi_rivi_alku) ennen headerin lopputagia
		$edi_rivi = $edi_rivi_alku."\n$key\n";
		$edi_rivi .= $otsikonloppu."\n";
		// echo "----TOIMITUSOSOITE-----\n";
		// echo $key."\n";
		// echo "----TILASI TUOTTEET----\n";
		//$edi_rivi .= "\n".$key."\n";

		foreach ($value as $k => $v) { 
			foreach ($v as $kii => $kippari) {
				$edi_rivi .= $kii."\n";
				$edi_rivi .= $kippari[2]."\n";
				$edi_rivi .= $kippari[0]."\n";
				$edi_rivi .= $kippari[3]."\n";
				$edi_rivi .= $kippari[1]."\n";
				$edi_rivi .= "PCD+12\n";
				// echo "TNO: {$kii}\n";
				// echo "KUVAUS: {$kippari[2]}\n";
				// echo "{$kippari[0]} KAPPALETTA\n";
				// echo "KAPPALEHINTA {$kippari[1]} EUROA\n";
				// echo "TOIMITUSPVM {$kippari[3]}\n";
			}
		}
		// Lis‰t‰‰n koko sanoman lopputagit
		$edi_rivi .= $edi_rivi_loppu;
		
		// Luodaan tiedosto
		$filename = "/tmp/ediorders911_crossdock_split".uniqid(time()."_").".txt";
		file_put_contents($filename, $edi_rivi);

		require("editilaus_in.inc");
	}

	echo "\n\n";
}
