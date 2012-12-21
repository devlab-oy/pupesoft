<?php

	$rtuoteno			= array();
	$laskuformaatti 	= "UNIKKO-INVOICE";
	$yhtio 				= "20428100";
	$verkkotunnus_vas	= "003720428100";
	$laskun_tyyppi 		= "UNIKKO-INVOICE";
	$a 					= 0;
	$ealvi		 		= array("");
	$lisavat			= array("");

	foreach ($xml->LedgerJournalTable->LedgerJournalTrans as $lasku) {

		if ($lasku->AccountType == "Vend") {
			$kommentti			= (string) trim($lasku->Voucher);
			$yhtio				= "20428100";
			$verkkotunnus_vas	= "003720428100";
			$laskun_tyyppi		= "UNIKKO-INVOICE";
			$laskun_numero		= (string) trim($lasku->Invoice);
			$laskun_ebid		= "UNIKKO-INVOICE";
			$laskun_tapvm		= (string) tv2dateconv(trim($lasku->TransDate));
			$laskun_lapvm		= (string) tv2dateconv(trim($lasku->DocumentDate));
			$laskun_erapaiva	= (string) tv2dateconv(trim($lasku->Due));
			$laskun_kapvm		= (string) tv2dateconv(trim($lasku->DateCashDisc));
			$laskun_kasumma		= (float) trim($lasku->CashDiscAmount);
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
			$laskun_toimitunnus				= 0;
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

	#error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	#ini_set("display_errors", 0);

?>