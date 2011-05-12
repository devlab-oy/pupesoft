<?php
	 error_reporting(E_ALL);
	 ini_set("display_errors", 1);

/*
echo "\n\nLASKUNTIEDOT:\n";
echo "01: ".$yhtio."\n";
echo "02: ".$verkkotunnus_vas."\n";
echo "03: ".$laskun_tyyppi."\n";
echo "04: ".$laskun_numero."\n";
echo "05: ".$laskun_ebid."\n";
echo "06: ".$laskun_paiva."\n";
echo "07: ".$laskun_erapaiva."\n";
echo "08: ".$laskuttajan_ovt."\n";
echo "09: ".$laskuttajan_nimi."\n";
echo "10: ".$laskuttajan_vat."\n";
echo "11: ".$laskun_pankkiviite."\n";
echo "12: ".$laskun_asiakastunnus."\n";
echo "13: ".$laskun_summa_eur."\n";
echo "14: ".$laskun_tilausviite."\n";
echo "15: ".$kauttalaskutus."\n";
echo "16: ".$laskun_asiakkaan_tilausnumero."\n";
echo "17: ".$toim_asiakkaantiedot["toim_ovttunnus"]."\n";
echo "18: ".$toim_asiakkaantiedot["ytunnus"]."\n";
echo "19: ".$toim_asiakkaantiedot["nimi"]."\n";
echo "20: ".$toim_asiakkaantiedot["osoite"]."\n";
echo "21: ".$toim_asiakkaantiedot["postino"]."\n";
echo "22: ".$toim_asiakkaantiedot["postitp"]."\n";
echo "23: ".$ostaja_asiakkaantiedot["toim_ovttunnus"]."\n";
echo "24: ".$ostaja_asiakkaantiedot["ytunnus"]."\n";
echo "25: ".$ostaja_asiakkaantiedot["nimi"]."\n";
echo "26: ".$ostaja_asiakkaantiedot["osoite"]."\n";
echo "27: ".$ostaja_asiakkaantiedot["postino"]."\n";
echo "28: ".$ostaja_asiakkaantiedot["postitp"]."\n";
echo "29: teccom-only/unikko ".$laskuttajan_toimittajanumero."\n";

*/

	$rtuoteno				= array();
	$laskuformaatti 		= "UNIKKO-INVOICE";
	$yhtio 					= "20428100";
	$verkkotunnus_vas		= "003720428100";
	$laskun_tyyppi 			= "UNIKKO-INVOICE";
	$a = 0;
	
	foreach ($xml->LedgerJournalTable->LedgerJournalTrans as $lasku) {

		if ($lasku->AccountType == "Vend") {
			$kommentti			= (string) trim($lasku->Voucher);
			$yhtio				= "20428100";
			$verkkotunnus_vas	= "003720428100";
			$laskun_tyyppi		= "UNIKKO-INVOICE";
			$laskun_numero		= (string) trim($lasku->Invoice);
			$laskun_ebid		= "UNIKKO-INVOICE";
			$laskun_paiva		= (string) tv2dateconv(trim($lasku->DocumentDate));
			$laskun_erapaiva	= (string) tv2dateconv(trim($lasku->Due));
			$laskuttajan_ovt	= "";
			$laskuttajan_nimi	= pupesoft_cleanstring($lasku->Txt);
			$laskuttajan_vat	= "";
			$laskuttajan_valkoodi = (string) trim($lasku->Currency);

			if (trim($lasku->PaymId) != "0000") {
				$laskun_pankkiviite = (string) trim($lasku->PaymId);
			}
			else {
				$laskun_pankkiviite = "";
			}

			$laskun_asiakastunnus = "";

			if ((float) $lasku->AmountCurCredit != 0) {
				$laskun_summa_eur = (float) $lasku->AmountCurCredit;
			}
			else {
				$laskun_summa_eur = (float) $lasku->AmountCurDebit * -1; // materiaali rivi 244
			}

			$laskun_tilausviite				= "";
			$kauttalaskutus					= "";
			$laskun_asiakkaan_tilausnumero	= "";
			$toim_asiakkaantiedot	   		= "";
			$toim_asiakkaantiedot	   		= "";
			$toim_asiakkaantiedot	   		= "";
			$toim_asiakkaantiedot	   		= "";
			$toim_asiakkaantiedot	   		= "";
			$toim_asiakkaantiedot	   		= "";
			$ostaja_asiakkaantiedot	   		= "";
			$ostaja_asiakkaantiedot	   		= "";
			$ostaja_asiakkaantiedot	   		= "";
			$ostaja_asiakkaantiedot	   		= "";
			$ostaja_asiakkaantiedot	   		= "";
			$ostaja_asiakkaantiedot	   		= "";
			$laskuttajan_toimittajanumero	= (string) trim($lasku->AccountNum);
		}
		/*
	  	RIVINTIEDOT:
		$rtuoteno[]["ale"]
		$rtuoteno[]["alv"]
		$rtuoteno[]["hinta"]
		$rtuoteno[]["kauttalaskutus"]
		$rtuoteno[]["kommentti"]
		$rtuoteno[]["kpl"]
		$rtuoteno[]["laskutettuaika"]
		$rtuoteno[]["nimitys"]
		$rtuoteno[]["rivihinta"]
		$rtuoteno[]["riviinfo"]
		$rtuoteno[]["riviviite"]
		$rtuoteno[]["tilaajanrivinro"]
		$rtuoteno[]["tuoteno"]
		$rtuoteno[]["yksikko"]
		$rtuoteno[]["tilinumero"]
		$rtuoteno[]["valkoodi"]
		$rtuoteno[]["rivihinta_valuutassa"]
	 	*/
	
		if ($lasku->AccountType == "Ledger") {
			
			// jos lasku tulee EU-alueelta tai EU-alueen ulkopuolelta niin laitetaan ALV-prosentiksi 0
			if (trim($lasku->TaxGroup) == "EUO" or trim($lasku->TaxGroup) == "EXIM") {
				$rtuoteno[$a]["alv"] = 0;
			}
			else {
				$rtuoteno[$a]["alv"] = (int) trim($lasku->TaxItemGroup);
			}	
			
			// Tiliointi riveillä Debit on velka, ja credit on hyvitys.
			if ((float) $lasku->AmountCurDebit != 0) {
				$rtuoteno[$a]["rivihinta_valuutassa"] = (float) $lasku->AmountCurDebit - (float) $lasku->FixedTaxAmount;
			}
			else {
				$rtuoteno[$a]["rivihinta_valuutassa"] = ((float) $lasku->AmountCurCredit - (float) $lasku->FixedTaxAmount) *-1; 
			}
			
			$rtuoteno[$a]["valkoodi"] = (string) trim($lasku->Currency);
			
			$rtuoteno[$a]["tilinumero"] = (string) trim($lasku->AccountNum);
			$a++;
		}
	}
		
	error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	ini_set("display_errors", 0);

?>