<?php

$laskuformaatti 		= "INVOIC.D.96A";

// parseroidaan xml
$yhtio 					= array_shift($xml->xpath('Group2/NAD[@e3035="IV"]/@eC082.3039'));
$verkkotunnus_vas 		= array_shift($xml->xpath('Group2/NAD[@e3035="MR"]/@eC082.3039'));
$laskun_tyyppi 			= array_shift($xml->xpath('BGM/@eC002.1001'));
$laskun_numero 			= array_shift($xml->xpath('BGM/@e1004'));
$laskun_ebid 			= array_shift($xml->xpath('Group1/RFF[@eC506.1153="ZEB"]/@eC506.1154'));
$laskun_tapvm			= array_shift($xml->xpath('DTM[@eC507.2005="3"]/@eC507.2380'));
$laskun_lapvm			= array_shift($xml->xpath('DTM[@eC507.2005="3"]/@eC507.2380'));
$laskun_erapaiva		= array_shift($xml->xpath('Group8[PAT/@e4279="1"]/DTM[@eC507.2005="13"]/@eC507.2380'));
$laskun_kapvm 			= "";
$laskun_kasumma 		= "";
$laskuttajan_ovt		= array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@eC082.3039'));
$laskuttajan_nimi		= utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="II"]/@eC080.3036.1')));
$laskuttajan_vat		= array_shift($xml->xpath('Group2/Group3/RFF[@eC506.1153="VA"]/@eC506.1154'));
$laskuttajan_valkoodi   = "";
$laskun_pankkiviite		= array_shift($xml->xpath('Group1/RFF[@eC506.1153="PQ"]/@eC506.1154'));
$laskun_asiakastunnus	= array_shift($xml->xpath('Group2[NAD/@e3035="IV"]/Group3/RFF/@eC506.1154'));
$laskun_summa_eur		= (float) array_shift($xml->xpath('Group48/MOA[@eC516.5025="9" and @eC516.6345="EUR"]/@eC516.5004'));
$laskun_tilausviite 	= "";
$kauttalaskutus			= "";
$laskun_toimitunnus		= 0;

$toim_asiakkaantiedot = array();
$toim_asiakkaantiedot["toim_ovttunnus"]	= trim(array_shift($xml->xpath('Group2/NAD[@e3035="DP"]/@eC082.3039')));
$toim_asiakkaantiedot["ytunnus"] 		= trim(array_shift($xml->xpath('Group2/NAD[@e3035="DP"]/@eC082.3039')));
$toim_asiakkaantiedot["nimi"] 			= trim(utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="DP"]/@eC080.3036.1'))));
$toim_asiakkaantiedot["osoite"] 		= trim(utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="DP"]/@eC059.3042.1'))));
$toim_asiakkaantiedot["postino"] 		= trim(utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="DP"]/@e3251'))));
$toim_asiakkaantiedot["postitp"] 		= trim(utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="DP"]/@e3164'))));

$ostaja_asiakkaantiedot = array();
$ostaja_asiakkaantiedot["toim_ovttunnus"]	= trim(array_shift($xml->xpath('Group2/NAD[@e3035="BY"]/@eC082.3039')));
$ostaja_asiakkaantiedot["ytunnus"] 			= trim(array_shift($xml->xpath('Group2/NAD[@e3035="BY"]/@eC082.3039')));
$ostaja_asiakkaantiedot["nimi"] 			= trim(utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="BY"]/@eC080.3036.1'))));
$ostaja_asiakkaantiedot["osoite"] 			= trim(utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="BY"]/@eC059.3042.1'))));
$ostaja_asiakkaantiedot["postino"] 			= trim(utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="BY"]/@e3251'))));
$ostaja_asiakkaantiedot["postitp"] 			= trim(utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="BY"]/@e3164'))));

/// Jos BY tiedot puuttuu niin otetaan IV kentistä
if ($ostaja_asiakkaantiedot["nimi"] == '') {
	$ostaja_asiakkaantiedot["toim_ovttunnus"]	= trim(array_shift($xml->xpath('Group2/NAD[@e3035="IV"]/@eC082.3039')));
	$ostaja_asiakkaantiedot["ytunnus"] 			= trim(array_shift($xml->xpath('Group2/NAD[@e3035="IV"]/@eC082.3039')));
	$ostaja_asiakkaantiedot["nimi"] 			= trim(utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="IV"]/@eC080.3036.1'))));
	$ostaja_asiakkaantiedot["osoite"] 			= trim(utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="IV"]/@eC059.3042.1'))));
	$ostaja_asiakkaantiedot["postino"] 			= trim(utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="IV"]/@e3251'))));
	$ostaja_asiakkaantiedot["postitp"] 			= trim(utf8_decode(array_shift($xml->xpath('Group2/NAD[@e3035="IV"]/@e3164'))));
}

$laskuttajan_toimittajanumero = "";

// jos ei löytynyt EUR summaa kokeillaan ilman valkoodia
if ($laskun_summa_eur == 0) {
	$laskun_summa_eur = (float) array_shift($xml->xpath('Group48/MOA[@eC516.5025="9" and @eC516.6345=""]/@eC516.5004'));
}

// jos ei sillonkaan löytynyt summaa niin haetaan kokonaan ilman valkoodi segmenttiä
if ($laskun_summa_eur == 0) {
	$laskun_summa_eur = (float) array_shift($xml->xpath('Group48/MOA[@eC516.5025="9"]/@eC516.5004'));
}

//Tuotetiedot
$tuotetiedot = $xml->xpath('Group25');
$i 			 = 0;
$rtuoteno	 = array();
$ealvi		 = array("");
$lisavat	 = array("");

if (count($tuotetiedot) > 0) {
	foreach ($tuotetiedot as $tuotetieto) {

		// Varmistetaan, että Group25 pitää sisällään tuotenumeron
		if (array_shift($tuotetieto->xpath('LIN/@eC212.7140')) != NULL) {

			// Tuotekoodi
			$rtuoteno[$i]["tuoteno"] = array_shift($tuotetieto->xpath('LIN/@eC212.7140'));

			// Veroton rivihinta
			$rtuoteno[$i]["rivihinta"] = array_shift($tuotetieto->xpath('Group26/MOA[@eC516.5025="203"]/@eC516.5004'));

			$xvat[$i] = $tuotetieto->xpath('Group33/TAX[@eC241.5153="VAT"]/@eC243.5278');

			if (is_array($xvat[$i])) {
				$ealvi[$i] = $rtuoteno[$i]["alv"] = array_shift($xvat[$i]);
			}
			else {
				$ealvi[$i] = $rtuoteno[$i]["alv"] = "";
			}

			$rtuoteno[$i]["riviinfo"] = array_shift($tuotetieto-> xpath('IMD/@eC273.7008.1'));

			// Laskutettu määrä
			$rtuoteno[$i]["kpl"] = array_shift($tuotetieto-> xpath('QTY[@eC186.6063="12"]/@eC186.6060'));

			// Jotkut toimittajat lähettää laskutetun määrän 47 segmentissä eikä 12 niinku kuuluis
			if ((float) $rtuoteno[$i]["kpl"] == 0) {
				$rtuoteno[$i]["kpl"] = array_shift($tuotetieto-> xpath('QTY[@eC186.6063="47"]/@eC186.6060'));
			}

			$rtuoteno[$i]["tilaajanrivinro"] = array_shift($tuotetieto->xpath('Group29/RFF[@eC506.1153="CR"]/@eC506.1154'));

			//	Viite (EuroCard)
			$rtuoteno[$i]["riviviite"] = array_shift($tuotetieto->xpath('Group29/RFF[@eC506.1153="VN"]/@eC506.1154'));

			//	Tapahtumapäivä
			$rtuoteno[$i]["laskutettuaika"] = array_shift($tuotetieto->xpath('DTM[@eC507.2005="11"]/@eC507.2380'));

			$i++;
		}
	}
}

// Verotiedot
$vattiedot	= $xml->xpath('Group50');
$i			= 0;

if (count($vattiedot) > 0) {
	foreach ($vattiedot as $vattieto) {
		$lisavat[$i] = array_shift($vattieto->xpath('TAX/@eC243.5278'));
		$i++;
	}
}

?>