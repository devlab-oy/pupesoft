<?php

if (!function_exists('pupevoice_otsik')) {
	function pupevoice_otsik($tootxml, $lasrow, $kieli, $frow, $masrow, $myyrow, $tyyppi) {
		global $yhtiorow;

		$fstat = fstat($tootxml);

		if ($fstat["size"] == 0) {
			//tehd‰‰n verkkolasku oliot
			fputs($tootxml, "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n");
			fputs($tootxml, "<Pupevoice Version=\"0.99\">\n");
		}

		// Kirjotetaan laskun tietoja pupevoice XML-muotoon
		fputs($tootxml, "<Invoice>\n");
		fputs($tootxml, "<CHN>\n");

		xml_add("Paper", 								substr($lasrow['chn'], 0, 1), 	$tootxml);
		xml_add("Einvoice", 							substr($lasrow['chn'], 1, 1), 	$tootxml);
		xml_add("Edi", 									substr($lasrow['chn'], 2, 1), 	$tootxml);
		xml_add("Email", 								$yhtiorow['admin_email'],		$tootxml);

		if (strtoupper($yhtiorow['kieli']) == 'SE' or $kieli == "SE") {
			xml_add("Language",							"SV",							$tootxml);
		}

		fputs($tootxml, "</CHN>\n");
		fputs($tootxml, "<SellerPartyInformation>\n");

		// jos meill‰ on lasku menossa ulkomaille ja se laskutetaan eri ovttunnuksella
		if ($lasrow["yhtio_ovttunnus"] != "" and $lasrow["yhtio_ovttunnus"] != $yhtiorow["ovttunnus"]) {

			// haetaan toimipaikan tiedot
			$alhqur = "select * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and vat_numero='$lasrow[yhtio_ovttunnus]'";
			$alhire = mysql_query($alhqur) or pupe_error($alhqur);

			// k‰ytet‰‰n t‰n toimipaikan yhteystietoja
			if (mysql_num_rows($alhire) == 1) {
				$alhiro = mysql_fetch_array($alhire);
				$yhtiorow['puhelin']	= $alhiro['puhelin'];
				$yhtiorow['fax']		= $alhiro['fax'];
			}
		}

		xml_add("SellerPartyIdentifier", 				$lasrow['ovttunnus'],		$tootxml);
		xml_add("SellerPartyDomicile", 					$lasrow['kotipaikka'], 		$tootxml);
		xml_add("SellerOrganisationName", 				$lasrow['nimi'], 			$tootxml);
		xml_add("SellerStreetName", 					$lasrow['osoite'], 			$tootxml);
		xml_add("SellerPostCode", 						$lasrow['postino'], 		$tootxml);
		xml_add("SellerTownName", 						$lasrow['postitp'], 		$tootxml);
		xml_add("SellerCountryName", 					$lasrow['maa'],				$tootxml);
		xml_add("SellerPhoneNumber", 					$yhtiorow['puhelin'], 		$tootxml);
		xml_add("SellerFaxNumber", 						$yhtiorow['fax'], 			$tootxml);

		if ($masrow["factoring"] != "") {
			xml_add("SellerAccountName1", 				$frow['pankkinimi1'],		$tootxml);
			xml_add("SellerAccountID1", 				$frow['pankkitili1'],		$tootxml);
			xml_add("SellerAccountName2", 				$frow['pankkinimi2'],		$tootxml);
			xml_add("SellerAccountID2", 				$frow['pankkitili2'],		$tootxml);
			xml_add("SellerAccountName3", 				"",							$tootxml);
			xml_add("SellerAccountID3", 				"",							$tootxml);
		}
		elseif ($masrow["pankkinimi1"] != "") {
			xml_add("SellerAccountName1", 				$masrow['pankkinimi1'],		$tootxml);
			xml_add("SellerAccountID1", 				$masrow['pankkitili1'],		$tootxml);
			xml_add("SellerAccountName2", 				$masrow['pankkinimi2'],		$tootxml);
			xml_add("SellerAccountID2", 				$masrow['pankkitili2'],		$tootxml);
			xml_add("SellerAccountName3", 				$masrow['pankkinimi3'],		$tootxml);
			xml_add("SellerAccountID3", 				$masrow['pankkitili3'],		$tootxml);
		}
		else {
			xml_add("SellerAccountName1", 				$yhtiorow['pankkinimi1'],	$tootxml);
			xml_add("SellerAccountID1", 				$yhtiorow['pankkitili1'],	$tootxml);
			xml_add("SellerAccountName2", 				$yhtiorow['pankkinimi2'],	$tootxml);
			xml_add("SellerAccountID2", 				$yhtiorow['pankkitili2'],	$tootxml);
			xml_add("SellerAccountName3", 				$yhtiorow['pankkinimi3'],	$tootxml);
			xml_add("SellerAccountID3", 				$yhtiorow['pankkitili3'],	$tootxml);
		}

		xml_add("SellerVatRegistrationText", 			t("Alv.Rek"), 		$tootxml);
		xml_add("SellerContactPerson", 					$myyrow['nimi'],	$tootxml);

		fputs($tootxml, "</SellerPartyInformation>\n");
		fputs($tootxml, "<InvoiceDetails>\n");

		xml_add("InvoiceType",							$tyyppi, 										$tootxml);
		xml_add("InvoicedPartyIdentifier",				$lasrow['ytunnus'], 							$tootxml);
		xml_add("InvoicedPartyOVT",						$lasrow['ovttunnus'],							$tootxml);
		xml_add("InvoicedPartyEBA",						$lasrow['verkkotunnus'], 						$tootxml);
		xml_add("InvoicedOrganisationName",				"$lasrow[nimi] $lasrow[nimitark]", 				$tootxml);
		xml_add("InvoicedStreetName",					$lasrow['osoite'], 								$tootxml);
		xml_add("InvoicedPostCode",						$lasrow['postino'],								$tootxml);
		xml_add("InvoicedTownName",						$lasrow['postitp'],								$tootxml);
		xml_add("InvoicedCountryName",					$lasrow['maa'], 								$tootxml);
		xml_add("InvoiceNumber",						$lasrow['laskunro'],							$tootxml);
		xml_add("InvoiceCurrency",						$lasrow['valkoodi'],							$tootxml);
		xml_add("InvoicePaymentReference",				$lasrow['viite'], 								$tootxml);
		xml_add("InvoiceDate",							vlas_dateconv($lasrow['tapvm']), 				$tootxml);
		xml_add("OrderIdentifier",						'', 											$tootxml); //Ostajan tilausnumero, eii oo viel‰ olemassa
		xml_add("DeliveredPartyName",					"$lasrow[toim_nimi] $lasrow[toim_nimitark]",	$tootxml);
		xml_add("DeliveredPartyStreetName",				$lasrow['toim_osoite'], 						$tootxml);
		xml_add("DeliveredPartyPostCode",				$lasrow['toim_postino'], 						$tootxml);
		xml_add("DeliveredPartyTownName",				$lasrow['toim_postitp'], 						$tootxml);
		xml_add("DeliveredPartyCountryName",			$lasrow['toim_maa'], 							$tootxml);
		xml_add("DeliveredPartyOVT",					$lasrow['toim_ovttunnus'], 						$tootxml);
		xml_add("DueDate",								vlas_dateconv($lasrow['erpcm']),				$tootxml);
		xml_add("InvoiceTotalVatExcludedAmount",		$lasrow['arvo'],								$tootxml);
		xml_add("InvoiceTotalVatAmount",				round($lasrow['summa']-$lasrow['arvo'], 2),		$tootxml);
		xml_add("InvoiceTotalVatIncludedAmount",		$lasrow['summa'],								$tootxml);
		xml_add("PaymentTerms",							$masrow['teksti']." ".$masrow['kassa_teksti'],	$tootxml);
		xml_add("PaymentOverDueFinePercent",			$lasrow['viikorkopros'], 						$tootxml);
		xml_add("InvoiceDeliveryMethod",				$lasrow['toimitustapa'], 						$tootxml);

		//Laitetaan kassa-alennustietoja
		xml_add("CashDiscountDate",						vlas_dateconv($lasrow['kapvm']), 				$tootxml);
		xml_add("CashDiscountBaseAmount",				$lasrow['summa'],			 					$tootxml);
		xml_add("CashDiscountPercent",					$masrow['kassa_alepros'],	 					$tootxml);
		xml_add("CashDiscountAmount",					$lasrow['kasumma'],			 					$tootxml);
		xml_add("InvoiceReferenceFreeText",				$lasrow['viesti'],								$tootxml);
		xml_add("InvoiceFreeText",						$lasrow['sisviesti1'],							$tootxml);
	}
}


if (!function_exists('pupevoice_alvierittely')) {
	function pupevoice_alvierittely($tootxml, $alvrow) {

		fputs($tootxml, "<VatSpecificationDetails>\n");
		xml_add("VatRatePercent",					$alvrow[0], $tootxml);
		xml_add("VatBaseAmount",					$alvrow[1], $tootxml);
		xml_add("VatRateAmount",					$alvrow[2], $tootxml);
		fputs($tootxml, "</VatSpecificationDetails>\n");
	}
}

if (!function_exists('pupevoice_rivi')) {
	function pupevoice_rivi($tootxml, $tilrow, $vatamount, $totalvat) {

		fputs($tootxml, "<Row>\n");
		xml_add("OrderIdentifier",					$tilrow['otunnus'], 				$tootxml); // t‰m‰ on tilauksen numero meill‰, ei kuulu standardiin mutta se on pakko lis‰t‰
		xml_add("ArticleIdentifier",				$tilrow['tuoteno'], 				$tootxml);
		xml_add("ArticleName",						$tilrow['nimitys'], 				$tootxml);
		xml_add("DeliveredQuantity",				$tilrow['kpl'], 					$tootxml);
		xml_add("DeliveredQuantityUnitCode",		$tilrow['yksikko'],					$tootxml);
		xml_add("DeliveryDate",						vlas_dateconv($tilrow['toimaika']),	$tootxml);
		xml_add("UnitPrice",						$tilrow['hinta'], 					$tootxml);
		xml_add("RowIdentifier",					$tilrow['tilaajanrivinro'], 		$tootxml); // t‰nne laitetaan asiakkaan rivinumero, niin saavat parseroida senkin laskuista
		xml_add("RowDiscountPercent",				$tilrow['ale'], 					$tootxml);
		xml_add("RowVatRatePercent",				$tilrow['alv'], 					$tootxml);
		xml_add("RowVatAmount",						$vatamount, 						$tootxml); // veron m‰‰r‰
		xml_add("RowTotalVatExcludedAmount",		$tilrow['rivihinta'],		 		$tootxml); // veroton rivihinta
		xml_add("RowTotalVatIncludedAmount",		$totalvat, 							$tootxml); // verollinen rivihinta
		xml_add("RowFreeText",						$tilrow['kommentti'],		 		$tootxml);
		fputs($tootxml, "</Row>\n");
	}
}

if (!function_exists('pupevoice_lasku_loppu')) {
	function pupevoice_lasku_loppu($tootxml) {

		fputs($tootxml, "</InvoiceDetails>\n");
		fputs($tootxml, "</Invoice>\n");
	}
}

if (!function_exists('pupevoice_aineisto_loppu')) {
	function pupevoice_aineisto_loppu($tootxml) {

		$fstat = fstat($tootxml);

		if ($fstat["size"] > 0) {
			fputs($tootxml, "</Pupevoice>\n");
		}
	}
}

?>