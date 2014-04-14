<?php

// Asiakas ja tuotetiedot
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
			// Asiakas
			$arska[$value][] = array();
			$current_customer = $value;
		}
		elseif (substr($value, 0, 3) == "LIN") {
			// Asiakas - Tuotteet
			// Tuotteen vaihtuessa nollataan current_customer ja price
			if ($current_product != $value) {
				$current_customer = '';
				$current_product_price = '';
			}

			$arska[$current_customer][] = $value;#[] = $value;#array();
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
			// Asiakas - Tuote -> Kpl - Hinta
			if (!empty($current_product) and !empty($current_customer)) $arska[$current_customer][][$current_product] = array($value, $current_product_price, $current_product_desc, $delivery_date);
		}
		else {
			// Kaikki tilauksen muut rivit talteen
			$edi_rivi_temp[] = $value;
		}

	}
}

// Etsitään sanomarivin alun index
$found = array_search("UNS+D", $edi_rivi_temp);

// Erotellaan sanoman alku ja loppu
$edi_rivi_loppu = array_splice($edi_rivi_temp, $found+1);
$edi_rivi_alku = array_diff($edi_rivi_temp, $edi_rivi_loppu);

$edi_rivi_alku = implode("\n", $edi_rivi_alku);
$edi_rivi_loppu = implode("\n", $edi_rivi_loppu);
var_dump($edi_rivi_alku);
foreach ($arska as $key => &$value) {
	
	// Tuunataan jokaiselle asiakkaalle oma editilaus ja ajetaan sisään
	if ($key != '') {

		$edi_rivi = $edi_rivi_alku;
		// echo "----ASIAKAS-----\n";
		// echo $key."\n";
		// echo "----TILASI TUOTTEET----\n";
		$edi_rivi .= "\n".$key."\n";
		foreach ($value as $k => $v) { 
			foreach ($v as $kii => $kippari) {
				$edi_rivi .= $kii."\n";
				$edi_rivi .= $kippari[2]."\n";
				$edi_rivi .= $kippari[0]."\n";
				$edi_rivi .= $kippari[3]."\n";
				$edi_rivi .= $kippari[1]."\n";
				// echo "TNO: {$kii}\n";
				// echo "KUVAUS: {$kippari[2]}\n";
				// echo "{$kippari[0]} KAPPALETTA\n";
				// echo "KAPPALEHINTA {$kippari[1]} EUROA\n";
				// echo "TOIMITUSPVM {$kippari[3]}\n";
			}
		}
		$edi_rivi .= $edi_rivi_loppu;
		// Paramit editilaus_in.incille
		//$filename = generoitu_faili;
		$tilaus_tyyppi = "edifact911";
		
		// Luodaan tiedosto
		$tiedosto = "/tmp/ediorders911_crossdock_split".uniqid(time()."_").".txt";
		file_put_contents($tiedosto, $edi_rivi);

		// Avataan tiedosto uudestaan
		if (!$fd = fopen($tiedosto, "r")) die("Filen '$tiedosto' ".t("avaus epäonnistui")."!\n");

		// Jotta filenimi ei varmasti katoaisi matkalla, tämä ylikirjoittaa edellisen mailfilen.
		$mailfile = $tiedosto;
		
		//require("editilaus_in.inc");
	}
	echo "\n\n";
}

//var_dump($arska);
//echo "tunkattu array: \n";
//var_dump($arska);
//$edi_rivi = 

exit;
