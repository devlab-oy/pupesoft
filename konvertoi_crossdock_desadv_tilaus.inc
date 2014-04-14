<?php

// Tallennetaan NAD+SE -segmentti
$nadse_segmentti = "";
$arska = array();
$current_customer = '';
$current_product = '';
foreach ($order_tietosisalto as $key => $value) {

	$value = trim($value);

	if ($value != "") {

		// Asiakas
		if (substr($value, 0, 3) == "LOC") {
			// Asiakkaan vaihtuessa nollataan current_product
			//if ($current_customer != $value and $current_customer != '') $current_product = '';
			$arska[$value][] = array();
			$current_customer = $value;
		}
		elseif (substr($value, 0, 3) == "LIN") {
			// Asiakas - Tuotteet
			// Tuotteen vaihtuessa nollataan current_store
			if ($current_product != $value) $current_customer = '';

			$arska[$current_customer][] = $value;#[] = $value;#array();
			$current_product = $value;
		}
		elseif (substr($value, 0, 3) == "QTY") {
			// Asiakas - Tuotteet - Kpl
			if (!empty($current_product) and !empty($current_customer)) $arska[$current_customer][][$current_product] = $value;
			//if (isset($arska[$current_customer[$current_product]])) $arska[$current_customer][][] = $value;
		}
		else {
			$edi_rivi .= $value."\n";
		}

		if (substr($value, 0, 6) == "NAD+SE") {
			$nadse_segmentti = $value;
		}
	}
}

foreach ($arska as $key => &$value) {
	
	if ($key != '') {
		echo "----ASIAKAS-----\n";
		echo $key."\n";
		echo "----TILASI TUOTTEET----\n";
		foreach ($value as $k => $v) { 
			foreach ($v as $kii => $kippari) {
				echo "TNO: {$kii}\n";
				echo "$kippari KAPPALETTA\n";
			}
		}
	}
	echo "\n\n";
}

//echo "tunkattu array: \n";
//var_dump($arska);
//$edi_rivi = 

exit;
