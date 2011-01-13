<?xml version="1.0" encoding="ISO-8859-1"?>
<!-- V. 3.11.2010 19:38 -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" version="4.01" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>
	<!-- InvoiceDetails/DefinitionDetails -tietojen tulostuspaikan valinta: 1=ylös, 2=alas-->
	<xsl:variable name="InvoiceDetails_DefinitionDetails_paikka" select="1"/>
	<!-- Tekstit alkavat -->
	<xsl:variable name="txtINVOICE">LASKU</xsl:variable>
	<xsl:variable name="txtCREDITNOTE">HYVITYSLASKU</xsl:variable>
	<xsl:variable name="txtCOPY">KOPIO</xsl:variable>
	<xsl:variable name="txtSeller">Myyjä</xsl:variable>
	<xsl:variable name="txtTaxCode">Y-tunnus</xsl:variable>
	<xsl:variable name="txtBuyerOrganisationName">Ostaja</xsl:variable>
	<xsl:variable name="txtInvoiceRecipientContact">Laskun vastaanottajan yhteystiedot</xsl:variable>
	<xsl:variable name="txtInvoiceDate">Laskun päiväys</xsl:variable>
	<xsl:variable name="txtInvoiceNumber">Laskun numero</xsl:variable>
	<xsl:variable name="txtSellerReferenceIdentifier">Myyjän viite</xsl:variable>
	<xsl:variable name="txtOrderIdentifier">Tilaus / sopimus</xsl:variable>
	<xsl:variable name="txtAgreement">Sopimus</xsl:variable>
	<xsl:variable name="txtAgreementDate">Sopimuspäivä</xsl:variable>
	<xsl:variable name="txtBuyerPartyIdentifier">Ostajan asiakasnro</xsl:variable>
	<xsl:variable name="txtInvoiceDueDate">Maksun eräpäivä</xsl:variable>
	<xsl:variable name="txtEpiInstructedAmount">Maksun määrä</xsl:variable>
	<xsl:variable name="txtEpiAccountID">Maksun saajan tili</xsl:variable>
	<xsl:variable name="txtEpiNameAddressDetails">Maksun saajan nimi</xsl:variable>
	<xsl:variable name="txtEpiRemittanceInfoIdentifier">Viitenumero</xsl:variable>
	<xsl:variable name="txtPaymentStatusCode">Maksun tilanne</xsl:variable>
	<xsl:variable name="txtPaymentMethodText">Maksutapa</xsl:variable>
	<xsl:variable name="txtPaymentTermsFreeText">Maksuehto</xsl:variable>
	<xsl:variable name="txtPaymentOverDueFineDetails">Viivästystiedot</xsl:variable>
	<xsl:variable name="txtPaymentOverDueFinePercent">Viivästyskorko</xsl:variable>
	<xsl:variable name="txtDeliveryParty">Toimitusosoite</xsl:variable>
	<xsl:variable name="txtDeliveryDate">Toimituspäivä</xsl:variable>
	<xsl:variable name="txtDeliveryPeriodDetails">Toimitusjakso</xsl:variable>
	<xsl:variable name="txtDeliveryMethod">Toimitustapa</xsl:variable>
	<xsl:variable name="txtDeliveryTerms">Toimitusehdot</xsl:variable>
	<xsl:variable name="txtTerminalAddress">Terminaaliosoite</xsl:variable>
	<xsl:variable name="txtWaybillIdentifier">Rahtikirjan viite</xsl:variable>
	<xsl:variable name="txtWaybillMaker">Rahtikirjan tekijä</xsl:variable>
	<xsl:variable name="txtPaymentStatusNotPaid">Maksettava</xsl:variable>
	<xsl:variable name="txtPaymentStatusPartlyPaid">Osa maksettu</xsl:variable>
	<xsl:variable name="txtPaymentStatusPaid">Maksettu</xsl:variable>
	<xsl:variable name="txtArticleName">Tuote/palvelu</xsl:variable>
	<xsl:variable name="txtRowIdentifier">Tilausviite</xsl:variable>
	<xsl:variable name="txtRowIdentifierDate">Tilauspäivä</xsl:variable>
	<xsl:variable name="txtArticleIdentifier">Tuotetunnus</xsl:variable>
	<xsl:variable name="txtBuyerArticleIdentifier">Ostajan tuotenro</xsl:variable>
	<xsl:variable name="txtRowQuotationIdentifier">Tarjousviite</xsl:variable>
	<xsl:variable name="txtDeliveredQuantity">Toimitettu määrä</xsl:variable>
	<xsl:variable name="txtOrderedQuantity">Tilattu määrä</xsl:variable>
	<xsl:variable name="txtConfirmedQuantity">Vahvistettu määrä</xsl:variable>
	<xsl:variable name="txtUnitPriceAmount">Veroton a-hinta</xsl:variable>
	<xsl:variable name="txtUnitPriceVatIncludedAmount">Verollinen a-hinta</xsl:variable>
	<xsl:variable name="txtRowDeliveryDates">Toimituspvm (jak)</xsl:variable>
	<xsl:variable name="txtRowDeliveryIdentifier">Toimitusviite</xsl:variable>
	<xsl:variable name="txtRowDiscount">Alennus</xsl:variable>
	<xsl:variable name="txtVat">Alv</xsl:variable>
	<xsl:variable name="txtVatAmount">Alv-määrä</xsl:variable>
	<xsl:variable name="txtVatExcludedAmount">Veroton määrä</xsl:variable>
	<xsl:variable name="txtRowAmount">Yhteensä</xsl:variable>
	<xsl:variable name="txtINVOICETOTAL">LASKU YHTEENSÄ</xsl:variable>
	<xsl:variable name="txtVatSpecification">ALV-erittely</xsl:variable>
	<xsl:variable name="txtShortProposedAccountIdentifier">Tiliöintiehdotus (lyhyt)</xsl:variable>
	<xsl:variable name="txtNormalProposedAccountIdentifier">Tiliöintiehdotus (norm)</xsl:variable>
	<xsl:variable name="txtAccountDimension">Kustannuspaikka</xsl:variable>
	<xsl:variable name="txtVirtualBankBarcode">Virtuaaliviivakoodi</xsl:variable>
	<xsl:variable name="txtPartialPaymentDetails">Osamaksun tiedot</xsl:variable>
	<xsl:variable name="txtPaidAmount">Maksettu määrä</xsl:variable>
	<xsl:variable name="txtUnPaidAmount">Maksamaton määrä</xsl:variable>
	<xsl:variable name="txtInterestPercent">Korkokanta</xsl:variable>
	<xsl:variable name="txtProsessingCostsAmount">Toimitusmaksu</xsl:variable>
	<xsl:variable name="txtPartialPaymentDueDate">Osamaksun eräpäivä</xsl:variable>
	<xsl:variable name="txtVatIncludedAmount">Verollinen määrä</xsl:variable>
	<xsl:variable name="txtPartialPaymentReferenceIdentifier">Osamaksun viitenro</xsl:variable>
	<xsl:variable name="txtPhoneNumber">Puhelin</xsl:variable>
	<xsl:variable name="txtFaxNumber">Faksi</xsl:variable>
	<xsl:variable name="txtWebaddressIdentifier">WWW-osoite</xsl:variable>
	<xsl:variable name="txtEmailaddressIdentifier">Sähköposti</xsl:variable>
	<xsl:variable name="txtHomeTownName">Kotipaikka</xsl:variable>
	<xsl:variable name="txtEur">euroa</xsl:variable>
	<xsl:variable name="txtLink">Linkki</xsl:variable>
	<xsl:variable name="txtAgreementIdentifier">Sopimusviite</xsl:variable>
	<xsl:variable name="txtProposedAccountIdentifier">Tiliöintiehdotus</xsl:variable>
	<xsl:variable name="txtEpiBfiIdentifier">Pankin Bic-tunnus</xsl:variable>
	<xsl:variable name="txtDeliverer">Toimittaja</xsl:variable>
	<xsl:variable name="txtManufacturer">Valmistaja</xsl:variable>
	<xsl:variable name="txtInvoiceRecipientAddress">Laskutusosoite</xsl:variable>
	<xsl:variable name="txtCashDiscountDate">Kassa-alennuspäivä</xsl:variable>
	<xsl:variable name="txtCashDiscountPercent">Kassa-alennusprosentti</xsl:variable>
	<xsl:variable name="txtCashDiscountAmount">Kassa-alennuksen määrä</xsl:variable>
	<xsl:variable name="txtInvoiceSender">Laskun lähettäjä</xsl:variable>
	<xsl:variable name="txtOriginalInvoice">Alkup. laskun numero</xsl:variable>
	<xsl:variable name="txtPriceListIdentifier">Hinnaston viite</xsl:variable>
	<xsl:variable name="txtRequestOfQuotationIdentifier">Tarjouspyynnön viite</xsl:variable>
	<xsl:variable name="txtDeliveryInfo">Toimitustiedot</xsl:variable>
	<xsl:variable name="txtInvoicingPeriod">Laskutuskausi</xsl:variable>
	<xsl:variable name="txtOrdererName">Tilaajan nimi</xsl:variable>
	<xsl:variable name="txtAgreementType">Sopimuksen tyyppi</xsl:variable>
	<xsl:variable name="txtBuyersSellerId">Ostajan myyjäviite</xsl:variable>
	<xsl:variable name="txtSellersBuyerId">Asiakastunnus</xsl:variable>
	<xsl:variable name="txtBuyerReference">Ostajan viite</xsl:variable>
	<xsl:variable name="txtNotificationId">Ilmoitustunnus</xsl:variable>
	<xsl:variable name="txtNotificationDate">Ilmoituksen päiväys</xsl:variable>
	<xsl:variable name="txtRegNumberId">Rekisteröintitunnus</xsl:variable>
	<xsl:variable name="txtProjectRefId">Projektin tunnus</xsl:variable>
	<xsl:variable name="txtCreditLimit">Luottolimiitti</xsl:variable>
	<xsl:variable name="txtCreditInterest">Luottokorko</xsl:variable>
	<xsl:variable name="txtOperationLimit">Luoton käyttöraja</xsl:variable>
	<xsl:variable name="txtCashDiscountExlVat">Alviton kassa-alennus</xsl:variable>
	<xsl:variable name="txtCashDiscountVat">Kassa-alennuksen alv</xsl:variable>
	<xsl:variable name="txtFactoringPartyName">Rahoitusyhtiön nimi</xsl:variable>
	<xsl:variable name="txtFactoringPartyId">Rahoitusyhtiön tunnus</xsl:variable>
	<xsl:variable name="txtFactoringFreeText">Siirtolauseke</xsl:variable>
	<xsl:variable name="txtSalesPersonName">Myyjän nimi</xsl:variable>
	<xsl:variable name="txtAnyPartyIdentifier">Tunnus</xsl:variable>
	<xsl:variable name="txtAnyPartyOrgName">Nimi</xsl:variable>
	<xsl:variable name="txtAnyPartyOrgDep">Osasto</xsl:variable>
	<xsl:variable name="txtAnyPartyOrgUnit">Organisaatioyksikkö</xsl:variable>
	<xsl:variable name="txtSiteCode">Osapuolitunnus</xsl:variable>
	<xsl:variable name="txtAddress">Osoite</xsl:variable>
	<xsl:variable name="txtAveragePrice">Keskihinta</xsl:variable>
	<xsl:variable name="txtArticleGroupIdentifier">Tuoteryhmä</xsl:variable>
	<xsl:variable name="txtEanCode">EAN-koodi</xsl:variable>
	<xsl:variable name="txtRegistrationNumberId">Rekisteritunnus</xsl:variable>
	<xsl:variable name="txtSerialNumberId">Sarjanumero</xsl:variable>
	<xsl:variable name="txtRowActionCode">Tehtäväkoodi</xsl:variable>
	<xsl:variable name="txtClearanceId">Tullausviite</xsl:variable>
	<xsl:variable name="txtDeliveryNoteId">Lähetysluettelon tunnus</xsl:variable>
	<xsl:variable name="txtPlaceOfDischarge">Purkupaikka</xsl:variable>
	<xsl:variable name="txtFinalDestination">Määränpää</xsl:variable>
	<xsl:variable name="txtManufacturerArticleId">Valm. tuotetunnus</xsl:variable>
	<xsl:variable name="txtManufacturerOrderId">Valm. tilausviite</xsl:variable>
	<xsl:variable name="txtPackageLength">Pakkauksen pituus</xsl:variable>
	<xsl:variable name="txtPackageWidth">Pakkauksen leveys</xsl:variable>
	<xsl:variable name="txtPackageHeight">Pakkauksen korkeus</xsl:variable>
	<xsl:variable name="txtPackageWeight">Pakkauksen paino</xsl:variable>
	<xsl:variable name="txtPackageNetWeight">Nettopaino</xsl:variable>
	<xsl:variable name="txtPackageVolume">Pakkauksen tilavuus</xsl:variable>
	<xsl:variable name="txtTransportCarriageQuantity">Lavamäärä</xsl:variable>
	<xsl:variable name="txtDiscounts">Alennukset</xsl:variable>
	<xsl:variable name="txtInvoiceRow">laskurivi</xsl:variable>
	<xsl:variable name="txtRounding">Pyöristys</xsl:variable>
	<xsl:variable name="txtTargetCurrency">Kohdevaluutta</xsl:variable>
	<xsl:variable name="txtExchangeRate">Kurssi</xsl:variable>
	<xsl:variable name="txtOtherCurrency">Alkuperäinen määrä</xsl:variable>
	<xsl:variable name="txtShipmentOrg">Tavarantoimittaja</xsl:variable>
	<xsl:variable name="txtSourceCurrency">Alkuperäinen valuutta</xsl:variable>
	<xsl:variable name="txtReducedAmount">Alennettu maksun määrä</xsl:variable>
	<xsl:variable name="txtOfferedQuantity">Tarjottu määrä</xsl:variable>
	<xsl:variable name="txtPostDeliveredQuantity">Jälkitoimitettu määrä</xsl:variable>
	<xsl:variable name="txtInvoicedQuantity">Laskutettu määrä</xsl:variable>
	<xsl:variable name="txtRowUsedQuantity">Kulutettu määrä</xsl:variable>
	<xsl:variable name="txtRowPreviousMeterReadingDate">Edellinen mittariluentapäivä</xsl:variable>
	<xsl:variable name="txtRowLatestMeterReadingDate">Viimeisin mittariluentapäivä</xsl:variable>
	<xsl:variable name="txtRowCalculatedQuantity">Laskettu määrä</xsl:variable>
	<xsl:variable name="txtOriginalInvoiceIdentifier">Alkuperäisen laskun numero</xsl:variable>
	<xsl:variable name="txtOriginalInvoiceDate">Alkuperäisen laskun päiväys</xsl:variable>
	<xsl:variable name="txtOriginalDueDate">Alkuperäinen eräpäivä</xsl:variable>
	<xsl:variable name="txtOriginalInvoiceTotalAmount">Alkuperäinen maksettava määrä</xsl:variable>
	<xsl:variable name="txtOriginalEpiRemittanceInfoIdentifier">Alkuperäisen laskun viite</xsl:variable>
	<xsl:variable name="txtPaidDate">Maksupäivä</xsl:variable>
	<xsl:variable name="txtCollectionDate">Perintäpäivä</xsl:variable>
	<xsl:variable name="txtCollectionQuantity">Perintäkerrat</xsl:variable>
	<xsl:variable name="txtCollectionChargeAmount">Perintäkulut</xsl:variable>
	<xsl:variable name="txtInterestRate">Ylityskorko</xsl:variable>
	<xsl:variable name="txtInterestStartDate">Koron alkupäivä</xsl:variable>
	<xsl:variable name="txtInterestEndDate">Koron loppupäivä</xsl:variable>
	<xsl:variable name="txtInterestPeriodText">Korkojakso</xsl:variable>
	<xsl:variable name="txtInterestDateNumber">Korkopäivien lkm</xsl:variable>
	<xsl:variable name="txtInterestChargeAmount">Koron määrä</xsl:variable>
	<xsl:variable name="txtInterestChargeVatAmount">Koron alv</xsl:variable>
	<xsl:variable name="txtControllerIdentifier">Tarkastajan tunnus</xsl:variable>
	<xsl:variable name="txtControllerName">Tarkastajan nimi</xsl:variable>
	<xsl:variable name="txtControlDate">Tarkastuspäivä</xsl:variable>
	<xsl:variable name="txtContact">Yhteystiedot</xsl:variable>
	<!-- Tekstit loppuivat -->
	<!-- Tutkitaan, mitä sarakkeita tuoteriveille tarvitaan. -->
	<xsl:variable name="foundArticleName">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/ArticleName or
			                //Finvoice/InvoiceRow/ArticleInfoUrlText or
	                    //Finvoice/InvoiceRow/SubInvoiceRow/SubArticleName or
	                    //Finvoice/InvoiceRow/SubInvoiceRow/SubArticleInfoUrlText">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundRowIdentifier">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/RowIdentifier or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubRowIdentifier or
	                    //Finvoice/InvoiceRow/RowIdentifierUrlText or
	                    //Finvoice/InvoiceRow/SubInvoiceRow/SubRowIdentifierUrlText">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundRowIdentifierDate">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/RowIdentifierDate or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubRowIdentifierDate">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundArticleIdentifier">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/ArticleIdentifier or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubArticleIdentifier">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundBuyerArticleIdentifier">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/BuyerArticleIdentifier or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubBuyerArticleIdentifier">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundRowQuotationIdentifier">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/RowQuotationIdentifier or
			                //Finvoice/InvoiceRow/RowQuotationIdentifierUrlText or
	                    //Finvoice/InvoiceRow/SubInvoiceRow/SubRowQuotationIdentifier or
	                    //Finvoice/InvoiceRow/SubInvoiceRow/SubRowQuotationIdentifierUrlText">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundDeliveredQuantity">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/DeliveredQuantity or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubDeliveredQuantity">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundUnitPriceAmount">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/UnitPriceAmount or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubUnitPriceAmount">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundUnitPriceVatIncludedAmount">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/UnitPriceVatIncludedAmount or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubUnitPriceVatIncludedAmount">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundBothUnitPriceAmounts">
		<xsl:choose>
			<xsl:when test="$foundUnitPriceAmount + $foundUnitPriceVatIncludedAmount = 2">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundRowDeliveryDates">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/RowDeliveryDate or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubRowDeliveryDate or
										  //Finvoice/InvoiceRow/StartDate or
										  //Finvoice/InvoiceRow/SubInvoiceRow/SubStartDate or
											//Finvoice/InvoiceRow/EndDate or
											//Finvoice/InvoiceRow/SubInvoiceRow/SubEndDate">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundRowDeliveryIdentifier">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/RowDeliveryIdentifier or
			                //Finvoice/InvoiceRow/RowDeliveryIdentifierUrlText or
	                    //Finvoice/InvoiceRow/SubInvoiceRow/SubRowDeliveryIdentifier or
	                    //Finvoice/InvoiceRow/SubInvoiceRow/SubRowDeliveryIdentifierUrlText">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundRowAveragePriceAmount">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/RowAveragePriceAmount or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubRowAveragePriceAmount">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundRowVatRatePercent">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/RowVatRatePercent or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubRowVatRatePercent">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundRowVatAmount">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/RowVatAmount or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubRowVatAmount">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundRowVatExcludedAmount">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/RowVatExcludedAmount or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubRowVatExcludedAmount">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundRowAmount">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/RowAmount or
			                //Finvoice/InvoiceRow/SubInvoiceRow/SubRowAmount">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="foundRowDiscount">
		<xsl:choose>
			<xsl:when test="//Finvoice/InvoiceRow/RowDiscountTypeText or
                      //Finvoice/InvoiceRow/RowDiscountPercent or
                      //Finvoice/InvoiceRow/RowDiscountAmount or
                      //Finvoice/InvoiceRow/RowProgressiveDiscountDetails/RowDiscountTypeText or
                      //Finvoice/InvoiceRow/RowProgressiveDiscountDetails/RowDiscountPercent or
                      //Finvoice/InvoiceRow/RowProgressiveDiscountDetails/RowDiscountAmount or
                      //Finvoice/InvoiceRow/SubInvoiceRow/SubRowDiscountPercent or
                      //Finvoice/InvoiceRow/SubInvoiceRow/SubRowDiscountAmount or
                      //Finvoice/InvoiceRow/SubInvoiceRow/SubRowDiscountTypeText or
                      //Finvoice/InvoiceRow/SubInvoiceRow/SubRowProgressiveDiscountDetails/SubRowDiscountPercent or
                      //Finvoice/InvoiceRow/SubInvoiceRow/SubRowProgressiveDiscountDetails/SubRowDiscountAmount or
                      //Finvoice/InvoiceRow/SubInvoiceRow/SubRowProgressiveDiscountDetails/SubRowDiscountTypeText">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="useRowCol1" select="$foundArticleName + $foundRowIdentifier + $foundRowIdentifierDate"/>
	<xsl:variable name="useRowCol2" select="$foundArticleIdentifier + $foundBuyerArticleIdentifier + $foundRowQuotationIdentifier"/>
	<xsl:variable name="useRowCol3" select="$foundDeliveredQuantity"/>
	<xsl:variable name="useRowCol4" select="$foundRowDeliveryDates + $foundRowDeliveryIdentifier + $foundRowDiscount"/>
	<xsl:variable name="useRowCol5" select="$foundUnitPriceAmount + $foundUnitPriceVatIncludedAmount + $foundRowAveragePriceAmount + $foundRowDiscount"/>
	<xsl:variable name="useRowCol6" select="$foundRowVatRatePercent + $foundRowDiscount"/>
	<xsl:variable name="useRowCol7" select="$foundRowVatAmount + $foundRowVatExcludedAmount"/>
	<xsl:variable name="useRowCol8" select="$foundRowAmount"/>
	<xsl:variable name="useColHdrRow1" select="$foundArticleName + $foundArticleIdentifier + $foundDeliveredQuantity +
		$foundUnitPriceAmount + $foundUnitPriceVatIncludedAmount + $foundRowVatRatePercent + $foundRowVatAmount + $foundRowAmount
	"/>
	<xsl:variable name="useColHdrRow2" select="$foundRowIdentifier + $foundBuyerArticleIdentifier +
		$foundRowDeliveryDates + $foundRowAveragePriceAmount + $foundRowVatExcludedAmount + $foundBothUnitPriceAmounts
	"/>
	<xsl:variable name="useColHdrRow3" select="$foundRowIdentifierDate + $foundRowQuotationIdentifier +
		$foundRowDeliveryIdentifier + $foundRowDiscount
	"/>
	<!-- Käsitellään Finvoice. -->
	<xsl:template match="Finvoice">
		<html>
			<head>
				<title>
					<xsl:call-template name="OutputTitle">
						<xsl:with-param name="invoiceTypeText" select="InvoiceDetails/InvoiceTypeText"/>
						<xsl:with-param name="originCode" select="InvoiceDetails/OriginCode"/>
						<xsl:with-param name="originText" select="InvoiceDetails/OriginText"/>
					</xsl:call-template>
					<xsl:text> - </xsl:text>
					<xsl:value-of select="SellerPartyDetails/SellerOrganisationName"/>
					<xsl:text> - </xsl:text>
					<xsl:call-template name="OutputDate">
						<xsl:with-param name="theDate" select="InvoiceDetails/InvoiceDate"/>
						<xsl:with-param name="theFormat" select="InvoiceDetails/InvoiceDate/@Format"/>
					</xsl:call-template>
				</title>
				<!-- Netscapessa x-small on sama kuin xx-small, joten käytetään small. -->
				<style type="text/css">
				   body { background-color:white; color:black }
					h2 { text-align:center; letter-spacing:8px; z-index:2 }
					.invoiceStyle { font-size:small }
					.invoiceRowStyle { font-size:small }
					.invoiceRowStyle th { font-size:small; border-top-width: 1px; border-bottom-width: 1px; padding:1px }
					.invoiceRowStyle td { font-size:small; padding:1px }
					.subRowStyle { font-size:small; font-weight:bold; background-color:rgb(243,243,243) }
					.subRowStyle th { font-size:small; border-top-width: 1px; border-bottom-width: 1px; padding:1px }
					.subRowStyle td { font-size:small; padding:1px }
					.sellerDetailsStyle { font-size:xx-small }
					.preStyle { border-style:solid none solid none; border-top-width:thin; border-bottom-width:thin; margin:0px; padding-top:2px; padding-bottom:2px;}
					<!--
					.rowPreStyle { font-family:Monospace; white-space:pre; font-size:normal; padding-top:5px; padding-bottom:0px; padding-left:3px; }
					.subRowPreStyle { font-family:Monospace; white-space:pre; font-size:normal; font-weight:bold; background-color:rgb(243,243,243); padding-top:5px; padding-bottom:0px; padding-left:3px; }
					-->
					.rowPreStyle { font-size:normal; padding-top:5px; padding-bottom:0px; padding-left:3px; }
					.subRowPreStyle { font-size:normal; font-weight:bold; background-color:rgb(243,243,243); padding-top:5px; padding-bottom:0px; padding-left:3px; }
					.invoiceRowHrStyle {border-top: 1px dashed rgb(0,0,0); border-bottom-style: none; border-left-style: none; border-right-style: none;}
					.definitionDetailsStyle1 { border-style:solid none solid solid; border-top-width:1px; border-bottom-width:1px; border-left-width:1px; margin:0px; padding-top:2px; padding-bottom:2px; padding-left:2px; border-color:black black black black; }
					.definitionDetailsStyle2 { border-style:solid none solid none; border-top-width:1px; border-bottom-width:1px; margin:0px; padding-top:2px; padding-bottom:2px; border-color:black black black black; }
					.definitionDetailsStyle3 { border-style:solid solid solid none; border-top-width:1px; border-bottom-width:1px; border-right-width:1px; margin:0px; padding-top:2px; padding-bottom:2px; padding-right:2px; border-color:black black black black; }
				</style>
			</head>
			<body>
				<h2>
					<xsl:call-template name="OutputTitle">
						<xsl:with-param name="invoiceTypeText" select="InvoiceDetails/InvoiceTypeText"/>
						<xsl:with-param name="originCode" select="InvoiceDetails/OriginCode"/>
						<xsl:with-param name="originText" select="InvoiceDetails/OriginText"/>
					</xsl:call-template>
				</h2>
				<!-- Tehdään taulukko, johon tulostetaan tuoterivien yläpuoliset tiedot. -->
				<table width="100%" cellpadding="0" cellspacing="0">
				<tbody>
					<tr class="invoiceStyle" valign="top" align="left">
						<td width="50%">
							<xsl:value-of select="$txtSeller"/>:<br/>
							<xsl:choose>
								<xsl:when test="string-length(SellerPartyDetails/SellerPartyIdentifierUrlText) != 0">
									<xsl:value-of select="$txtTaxCode"/>:
									<xsl:call-template name="FormatLink">
										<xsl:with-param name="link" select="SellerPartyDetails/SellerPartyIdentifierUrlText"/>
										<xsl:with-param name="text" select="SellerPartyDetails/SellerPartyIdentifier"/>
									</xsl:call-template>
									<br/>
								</xsl:when>
								<xsl:when test="string-length(SellerPartyDetails/SellerPartyIdentifier) != 0">
									<xsl:value-of select="$txtTaxCode"/>: <xsl:value-of select="SellerPartyDetails/SellerPartyIdentifier"/>
									<br/>
								</xsl:when>
							</xsl:choose>
							<xsl:for-each select="SellerPartyDetails/SellerOrganisationName">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="."/>
								</xsl:call-template>
							</xsl:for-each>
							<xsl:for-each select="SellerPartyDetails/SellerOrganisationDepartment">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="."/>
								</xsl:call-template>
							</xsl:for-each>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText" select="SellerPartyDetails/SellerPostalAddressDetails/SellerPostOfficeBoxIdentifier"/>
							</xsl:call-template>
							<xsl:for-each select="SellerPartyDetails/SellerPostalAddressDetails/SellerStreetName">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="."/>
								</xsl:call-template>
							</xsl:for-each>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText">
									<xsl:call-template name="BuildString">
										<xsl:with-param name="txtText" select="SellerPartyDetails/SellerPostalAddressDetails/SellerPostCodeIdentifier"/>
										<xsl:with-param name="txtText2" select="SellerPartyDetails/SellerPostalAddressDetails/SellerTownName"/>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText">
									<xsl:call-template name="BuildString">
										<xsl:with-param name="txtText" select="SellerPartyDetails/SellerPostalAddressDetails/CountryCode"/>
										<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
										<xsl:with-param name="txtText2" select="SellerPartyDetails/SellerPostalAddressDetails/CountryName"/>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText" select="SellerContactPersonName"/>
							</xsl:call-template>
							<xsl:for-each select="SellerContactPersonFunction">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="."/>
								</xsl:call-template>
							</xsl:for-each>
							<xsl:for-each select="SellerContactPersonDepartment">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="."/>
								</xsl:call-template>
							</xsl:for-each>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtTextCopy">
									<xsl:call-template name="FormatEmail">
										<xsl:with-param name="email" select="SellerCommunicationDetails/SellerEmailaddressIdentifier"/>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText" select="SellerCommunicationDetails/SellerPhoneNumberIdentifier"/>
							</xsl:call-template>
							<br/>
							<br/>
							<xsl:if test="count(InvoiceRecipientPartyDetails) != 0">
								<xsl:for-each select="InvoiceRecipientPartyDetails/InvoiceRecipientOrganisationName">
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="."/>
									</xsl:call-template>
								</xsl:for-each>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/InvoiceRecipientPostOfficeBoxIdentifier"/>
								</xsl:call-template>
								<xsl:for-each select="InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/InvoiceRecipientStreetName">
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="."/>
									</xsl:call-template>
								</xsl:for-each>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText">
										<xsl:call-template name="BuildString">
											<xsl:with-param name="txtText" select="InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/InvoiceRecipientPostCodeIdentifier"/>
											<xsl:with-param name="txtText2" select="InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/InvoiceRecipientTownName"/>
										</xsl:call-template>
									</xsl:with-param>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText">
										<xsl:call-template name="BuildString">
											<xsl:with-param name="txtText" select="InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/CountryCode"/>
											<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
											<xsl:with-param name="txtText2" select="InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/CountryName"/>
										</xsl:call-template>
									</xsl:with-param>
								</xsl:call-template>
								<br/>
								<br/>
							</xsl:if>
							<xsl:value-of select="$txtBuyerOrganisationName"/>:<br/>
							<xsl:for-each select="BuyerPartyDetails/BuyerOrganisationName">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="."/>
								</xsl:call-template>
							</xsl:for-each>
							<xsl:for-each select="BuyerPartyDetails/BuyerOrganisationDepartment">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="."/>
								</xsl:call-template>
							</xsl:for-each>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText" select="BuyerPartyDetails/BuyerPostalAddressDetails/BuyerPostOfficeBoxIdentifier"/>
							</xsl:call-template>
							<xsl:for-each select="BuyerPartyDetails/BuyerPostalAddressDetails/BuyerStreetName">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="."/>
								</xsl:call-template>
							</xsl:for-each>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText">
									<xsl:call-template name="BuildString">
										<xsl:with-param name="txtText" select="BuyerPartyDetails/BuyerPostalAddressDetails/BuyerPostCodeIdentifier"/>
										<xsl:with-param name="txtText2" select="BuyerPartyDetails/BuyerPostalAddressDetails/BuyerTownName"/>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText">
									<xsl:call-template name="BuildString">
										<xsl:with-param name="txtText" select="BuyerPartyDetails/BuyerPostalAddressDetails/CountryCode"/>
										<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
										<xsl:with-param name="txtText2" select="BuyerPartyDetails/BuyerPostalAddressDetails/CountryName"/>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
							<br/>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText" select="BuyerContactPersonName"/>
							</xsl:call-template>
							<xsl:for-each select="BuyerContactPersonFunction">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="."/>
								</xsl:call-template>
							</xsl:for-each>
							<xsl:for-each select="BuyerContactPersonDepartment">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="."/>
								</xsl:call-template>
							</xsl:for-each>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtTextCopy">
									<xsl:call-template name="FormatEmail">
										<xsl:with-param name="email" select="BuyerCommunicationDetails/BuyerEmailaddressIdentifier"/>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText" select="BuyerCommunicationDetails/BuyerPhoneNumberIdentifier"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText" select="BuyerPartyDetails/BuyerOrganisationTaxCode"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText" select="BuyerOrganisationUnitNumber"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtText" select="BuyerSiteCode"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTextBR">
								<xsl:with-param name="txtTitleRowCopy"><br/><xsl:value-of select="$txtInvoiceRecipientContact"/>:</xsl:with-param>
								<xsl:with-param name="txtTextCopy">
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="InvoiceRecipientContactPersonName"/>
									</xsl:call-template>
									<xsl:for-each select="InvoiceRecipientContactPersonFunction">
										<xsl:call-template name="OutputTextBR">
											<xsl:with-param name="txtText" select="."/>
										</xsl:call-template>
									</xsl:for-each>
									<xsl:for-each select="InvoiceRecipientContactPersonDepartment">
										<xsl:call-template name="OutputTextBR">
											<xsl:with-param name="txtText" select="."/>
										</xsl:call-template>
									</xsl:for-each>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="FormatEmail">
												<xsl:with-param name="email" select="InvoiceRecipientCommunicationDetails/InvoiceRecipientEmailaddressIdentifier"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="InvoiceRecipientCommunicationDetails/InvoiceRecipientPhoneNumberIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="InvoiceRecipientPartyDetails/InvoiceRecipientPartyIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="InvoiceRecipientOrganisationUnitNumber"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="InvoiceRecipientSiteCode"/>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
						</td>
						<td width="5px"><br/></td>
						<td>
							<table cellpadding="0" cellspacing="0">
								<tbody>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtInvoiceDate"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="OutputDate">
												<xsl:with-param name="theDate" select="InvoiceDetails/InvoiceDate"/>
												<xsl:with-param name="theFormat" select="InvoiceDetails/InvoiceDate/@Format"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtInvoiceNumber"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/InvoiceNumber"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtOriginalInvoice"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/OriginalInvoiceNumber"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtInvoicingPeriod"/>
										<xsl:with-param name="txtText">
											<xsl:call-template name="OutputDatePeriod">
												<xsl:with-param name="theStartDate" select="InvoiceDetails/InvoicingPeriodStartDate"/>
												<xsl:with-param name="theStartDateFormat" select="InvoiceDetails/InvoicingPeriodStartDate/@Format"/>
												<xsl:with-param name="theEndDate" select="InvoiceDetails/InvoicingPeriodEndDate"/>
												<xsl:with-param name="theEndDateFormat" select="InvoiceDetails/InvoicingPeriodEndDate/@Format"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtSellerReferenceIdentifier"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="FormatLink">
												<xsl:with-param name="link" select="InvoiceDetails/SellerReferenceIdentifierUrlText"/>
												<xsl:with-param name="text" select="InvoiceDetails/SellerReferenceIdentifier"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtOrderIdentifier"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="FormatLink">
												<xsl:with-param name="link" select="InvoiceDetails/OrderIdentifierUrlText"/>
												<xsl:with-param name="text" select="InvoiceDetails/OrderIdentifier"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtRowIdentifierDate"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="OutputDate">
												<xsl:with-param name="theDate" select="InvoiceDetails/OrderDate"/>
												<xsl:with-param name="theFormat" select="InvoiceDetails/OrderDate/@Format"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtOrdererName"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/OrdererName"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtSalesPersonName"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/SalesPersonName"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtAgreement"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="FormatLink">
												<xsl:with-param name="link" select="InvoiceDetails/AgreementIdentifierUrlText"/>
												<xsl:with-param name="text" select="InvoiceDetails/AgreementIdentifier"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtAgreementType"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/AgreementTypeText"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtAgreementDate"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="OutputDate">
												<xsl:with-param name="theDate" select="InvoiceDetails/AgreementDate"/>
												<xsl:with-param name="theFormat" select="InvoiceDetails/AgreementDate/@Format"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtBuyerPartyIdentifier"/>
										<xsl:with-param name="txtText" select="BuyerPartyDetails/BuyerPartyIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtBuyersSellerId"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/BuyersSellerIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtSellersBuyerId"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/SellersBuyerIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtBuyerReference"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/BuyerReferenceIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtNotificationId"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/NotificationIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtNotificationDate"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="OutputDate">
												<xsl:with-param name="theDate" select="InvoiceDetails/NotificationDate"/>
												<xsl:with-param name="theFormat" select="InvoiceDetails/NotificationDate/@Format"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtRegNumberId"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/RegistrationNumberIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtControllerIdentifier"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/ControllerIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtControllerName"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/ControllerName"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtControlDate"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="OutputDate">
												<xsl:with-param name="theDate" select="InvoiceDetails/ControlDate"/>
												<xsl:with-param name="theFormat" select="InvoiceDetails/ControlDate/@Format"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtProjectRefId"/>
										<xsl:with-param name="txtText" select="InvoiceDetails/ProjectReferenceIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="emptyRow">1</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitle" select="$txtEpiInstructedAmount"/>
										<xsl:with-param name="txtTextCopy">
											<b>
												<xsl:call-template name="OutputAmount">
													<xsl:with-param name="amount" select="EpiDetails/EpiPaymentInstructionDetails/EpiInstructedAmount"/>
													<xsl:with-param name="currency" select="EpiDetails/EpiPaymentInstructionDetails/EpiInstructedAmount/@AmountCurrencyIdentifier"/>
												</xsl:call-template>
											</b>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitle" select="$txtInvoiceDueDate"/>
										<xsl:with-param name="txtTextCopy">
											<b>
												<xsl:call-template name="OutputDate">
													<xsl:with-param name="theDate" select="EpiDetails/EpiPaymentInstructionDetails/EpiDateOptionDate"/>
													<xsl:with-param name="theFormat" select="EpiDetails/EpiPaymentInstructionDetails/EpiDateOptionDate/@Format"/>
												</xsl:call-template>
											</b>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitle" select="$txtEpiNameAddressDetails"/>
										<xsl:with-param name="txtTextCopy">
											<b><xsl:value-of select="EpiDetails/EpiPartyDetails/EpiBeneficiaryPartyDetails/EpiNameAddressDetails"/></b>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitle" select="$txtEpiAccountID"/>
										<xsl:with-param name="txtTextCopy">
											<b>
												<xsl:call-template name="OutputEpiAccountID">
													<xsl:with-param name="scheme" select="EpiDetails/EpiPartyDetails/EpiBeneficiaryPartyDetails/EpiAccountID/@IdentificationSchemeName"/>
													<xsl:with-param name="account" select="EpiDetails/EpiPartyDetails/EpiBeneficiaryPartyDetails/EpiAccountID"/>
												</xsl:call-template>
											</b>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitle" select="$txtEpiBfiIdentifier"/>
										<xsl:with-param name="txtTextCopy">
											<b><xsl:value-of select="EpiDetails/EpiPartyDetails/EpiBfiPartyDetails/EpiBfiIdentifier"/></b>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitle" select="$txtEpiRemittanceInfoIdentifier"/>
										<xsl:with-param name="txtTextCopy">
											<b>
												<xsl:call-template name="OutputEpiRemittanceInfoIdentifier">
													<xsl:with-param name="erii" select="EpiDetails/EpiPaymentInstructionDetails/EpiRemittanceInfoIdentifier"/>
												</xsl:call-template>
											</b>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="emptyRow">1</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtCreditLimit"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="OutputAmount">
												<xsl:with-param name="amount" select="InvoiceDetails/CreditLimitAmount"/>
												<xsl:with-param name="currency" select="InvoiceDetails/CreditLimitAmount/@AmountCurrencyIdentifier"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtCreditInterest"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="BuildString">
												<xsl:with-param name="txtText" select="InvoiceDetails/CreditInterestPercent"/>
												<xsl:with-param name="txtText2">%</xsl:with-param>
												<xsl:with-param name="txtText2NotAlone">1</xsl:with-param>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtOperationLimit"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="OutputAmount">
												<xsl:with-param name="amount" select="InvoiceDetails/OperationLimitAmount"/>
												<xsl:with-param name="currency" select="InvoiceDetails/OperationLimitAmount/@AmountCurrencyIdentifier"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="emptyRow">1</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtPaymentStatusCode"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:if test="string-length(PaymentStatusDetails/PaymentStatusCode) != 0">
												<xsl:choose>
													<xsl:when test="PaymentStatusDetails/PaymentStatusCode = 'NOTPAID'">
														<xsl:value-of select="$txtPaymentStatusNotPaid"/>
													</xsl:when>
													<xsl:when test="PaymentStatusDetails/PaymentStatusCode = 'PARTLYPAID'">
														<xsl:value-of select="$txtPaymentStatusPartlyPaid"/>
													</xsl:when>
													<xsl:when test="PaymentStatusDetails/PaymentStatusCode = 'PAID'">
														<xsl:value-of select="$txtPaymentStatusPaid"/>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="PaymentStatusDetails/PaymentStatusCode"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:if>
										</xsl:with-param>
									</xsl:call-template>
									<!--<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtPaymentMethodText"/>
										<xsl:with-param name="txtText" select="PaymentStatusDetails/PaymentMethodText"/>
									</xsl:call-template>-->
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="emptyRow">1</xsl:with-param>
									</xsl:call-template>
									<xsl:for-each select="InvoiceDetails/PaymentTermsDetails">
										<xsl:call-template name="OutputTableRow">
											<xsl:with-param name="styleName" select="'invoiceStyle'"/>
											<xsl:with-param name="txtTitleNormal" select="$txtPaymentOverDueFineDetails"/>
											<!--<xsl:with-param name="txtText" select="PaymentOverDueFineDetails/PaymentOverDueFineFreeText"/>-->
											<xsl:with-param name="txtTextCopy">
												<xsl:for-each select="PaymentOverDueFineDetails/PaymentOverDueFineFreeText">
													<xsl:call-template name="OutputTextBR">
														<xsl:with-param name="txtText" select="."/>
													</xsl:call-template>
												</xsl:for-each>
											</xsl:with-param>
										</xsl:call-template>
										<xsl:call-template name="OutputTableRow">
											<xsl:with-param name="styleName" select="'invoiceStyle'"/>
											<xsl:with-param name="txtTitleNormal" select="$txtPaymentOverDueFinePercent"/>
											<xsl:with-param name="txtTextCopy">
												<xsl:call-template name="BuildString">
													<xsl:with-param name="txtText" select="PaymentOverDueFineDetails/PaymentOverDueFinePercent"/>
													<xsl:with-param name="txtText2">%</xsl:with-param>
													<xsl:with-param name="txtText2NotAlone">1</xsl:with-param>
												</xsl:call-template>
											</xsl:with-param>
										</xsl:call-template>
										<xsl:for-each select="PaymentTermsFreeText">
											<xsl:call-template name="OutputTableRow">
												<xsl:with-param name="styleName" select="'invoiceStyle'"/>
												<xsl:with-param name="txtTitleNormal" select="$txtPaymentTermsFreeText"/>
												<xsl:with-param name="txtText" select="."/>
											</xsl:call-template>
										</xsl:for-each>
										<xsl:call-template name="OutputTableRow">
											<xsl:with-param name="styleName" select="'invoiceStyle'"/>
											<xsl:with-param name="txtTitleNormal" select="$txtCashDiscountDate"/>
											<xsl:with-param name="txtTextCopy">
												<xsl:call-template name="OutputDate">
													<xsl:with-param name="theDate" select="CashDiscountDate"/>
													<xsl:with-param name="theFormat" select="CashDiscountDate/@Format"/>
												</xsl:call-template>
											</xsl:with-param>
										</xsl:call-template>
										<xsl:call-template name="OutputTableRow">
											<xsl:with-param name="styleName" select="'invoiceStyle'"/>
											<xsl:with-param name="txtTitleNormal" select="$txtCashDiscountPercent"/>
											<xsl:with-param name="txtTextCopy">
												<xsl:call-template name="BuildString">
													<xsl:with-param name="txtText" select="CashDiscountPercent"/>
													<xsl:with-param name="txtText2">%</xsl:with-param>
													<xsl:with-param name="txtText2NotAlone">1</xsl:with-param>
												</xsl:call-template>
											</xsl:with-param>
										</xsl:call-template>
										<xsl:call-template name="OutputTableRow">
											<xsl:with-param name="styleName" select="'invoiceStyle'"/>
											<xsl:with-param name="txtTitleNormal" select="$txtCashDiscountAmount"/>
											<xsl:with-param name="txtTextCopy">
												<xsl:call-template name="OutputAmount">
													<xsl:with-param name="amount" select="CashDiscountAmount"/>
													<xsl:with-param name="currency" select="CashDiscountAmount/@AmountCurrencyIdentifier"/>
												</xsl:call-template>
											</xsl:with-param>
										</xsl:call-template>
										<xsl:call-template name="OutputTableRow">
											<xsl:with-param name="styleName" select="'invoiceStyle'"/>
											<xsl:with-param name="txtTitleNormal" select="$txtCashDiscountExlVat"/>
											<xsl:with-param name="txtTextCopy">
												<xsl:call-template name="OutputAmount">
													<xsl:with-param name="amount" select="CashDiscountExcludingVatAmount"/>
													<xsl:with-param name="currency" select="CashDiscountExcludingVatAmount/@AmountCurrencyIdentifier"/>
												</xsl:call-template>
											</xsl:with-param>
										</xsl:call-template>
										<xsl:for-each select="CashDiscountVatDetails">
											<xsl:call-template name="OutputTableRow">
												<xsl:with-param name="styleName" select="'invoiceStyle'"/>
												<xsl:with-param name="txtTitleNormal" select="$txtCashDiscountVat"/>
												<xsl:with-param name="txtTextCopy">
													<xsl:value-of select="CashDiscountVatPercent"/><xsl:text> %: </xsl:text>
													<xsl:call-template name="OutputAmount">
														<xsl:with-param name="amount" select="CashDiscountVatAmount"/>
														<xsl:with-param name="currency" select="CashDiscountVatAmount/@AmountCurrencyIdentifier"/>
													</xsl:call-template>
												</xsl:with-param>
											</xsl:call-template>
										</xsl:for-each>
										<xsl:call-template name="OutputTableRow">
											<xsl:with-param name="styleName" select="'invoiceStyle'"/>
											<xsl:with-param name="txtTitleNormal" select="$txtReducedAmount"/>
											<xsl:with-param name="txtTextCopy">
												<xsl:call-template name="OutputAmount">
													<xsl:with-param name="amount" select="ReducedInvoiceVatIncludedAmount"/>
													<xsl:with-param name="currency" select="ReducedInvoiceVatIncludedAmount/@AmountCurrencyIdentifier"/>
												</xsl:call-template>
											</xsl:with-param>
										</xsl:call-template>
										<xsl:call-template name="OutputTableRow">
											<xsl:with-param name="styleName" select="'invoiceStyle'"/>
											<xsl:with-param name="emptyRow">1</xsl:with-param>
										</xsl:call-template>
									</xsl:for-each>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtInvoiceSender"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:for-each select="InvoiceSenderPartyDetails/InvoiceSenderOrganisationName">
												<xsl:call-template name="OutputTextBR">
													<xsl:with-param name="txtText" select="."/>
												</xsl:call-template>
											</xsl:for-each>
											<xsl:call-template name="OutputTextBR">
												<xsl:with-param name="txtText" select="InvoiceSenderPartyDetails/InvoiceSenderPartyIdentifier"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="emptyRow">1</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtFactoringPartyName"/>
										<xsl:with-param name="txtText" select="FactoringAgreementDetails/FactoringPartyName"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtFactoringPartyId"/>
										<xsl:with-param name="txtText" select="FactoringAgreementDetails/FactoringPartyIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitleNormal" select="$txtFactoringFreeText"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:for-each select="FactoringAgreementDetails/FactoringFreeText">
												<xsl:call-template name="OutputTextBR">
													<xsl:with-param name="txtText" select="."/>
												</xsl:call-template>
											</xsl:for-each>
										</xsl:with-param>
									</xsl:call-template>
									<!--<xsl:if test="string-length(normalize-space(InvoiceDetails/InvoiceFreeText)) != 0">
										<xsl:call-template name="OutputTableRow">
											<xsl:with-param name="styleName" select="'invoiceStyle'"/>
											<xsl:with-param name="emptyRow">1</xsl:with-param>
										</xsl:call-template>
										<tr class="invoiceStyle" valign="top" align="left">
											<td colspan="3">
												<xsl:value-of select="InvoiceDetails/InvoiceFreeText"/>
											</td>
										</tr>
									</xsl:if>-->
								</tbody>
							</table>
						</td>
					</tr>
					<xsl:if test="$InvoiceDetails_DefinitionDetails_paikka = 1">
						<xsl:variable name="countDD" select="count(InvoiceDetails/DefinitionDetails)"/>
						<xsl:variable name="nDDCol1" select="$countDD - floor($countDD div 2)"/>
						<xsl:variable name="definitionDetailsCol1">
							<xsl:for-each select="InvoiceDetails/DefinitionDetails">
								<xsl:if test="position() &lt;= $nDDCol1">
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitle" select="DefinitionHeaderText"/>
										<xsl:with-param name="txtText" select="DefinitionValue"/>
										<xsl:with-param name="txtText2" select="DefinitionValue/@QuantityUnitCode"/>
									</xsl:call-template>
								</xsl:if>
							</xsl:for-each>
						</xsl:variable>
						<xsl:variable name="definitionDetailsCol2">
							<xsl:for-each select="InvoiceDetails/DefinitionDetails">
								<xsl:if test="position() &gt; $nDDCol1">
									<xsl:call-template name="OutputTableRow">
										<xsl:with-param name="styleName" select="'invoiceStyle'"/>
										<xsl:with-param name="txtTitle" select="DefinitionHeaderText"/>
										<xsl:with-param name="txtText" select="DefinitionValue"/>
										<xsl:with-param name="txtText2" select="DefinitionValue/@QuantityUnitCode"/>
									</xsl:call-template>
								</xsl:if>
							</xsl:for-each>
						</xsl:variable>
						<xsl:if test="string-length(normalize-space($definitionDetailsCol1)) != 0">
							<tr valign="top" align="left">
								<td width="50%" class="definitionDetailsStyle1">
									<table cellpadding="0" cellspacing="0">
										<tbody>
											<xsl:copy-of select="$definitionDetailsCol1"/>
											<!--<xsl:call-template name="OutputTableRow">
												<xsl:with-param name="styleName" select="'invoiceStyle'"/>
												<xsl:with-param name="emptyRow">1</xsl:with-param>
											</xsl:call-template>-->
										</tbody>
									</table>
								</td>
								<td width="5px" class="definitionDetailsStyle2"><br/></td>
								<td class="definitionDetailsStyle3">
									<xsl:if test="string-length(normalize-space($definitionDetailsCol2)) != 0">
										<table cellpadding="0" cellspacing="0">
											<tbody>
												<xsl:copy-of select="$definitionDetailsCol2"/>
												<!--<xsl:call-template name="OutputTableRow">
													<xsl:with-param name="styleName" select="'invoiceStyle'"/>
													<xsl:with-param name="emptyRow">1</xsl:with-param>
												</xsl:call-template>-->
											</tbody>
										</table>
									</xsl:if>
								</td>
							</tr>
							<tr valign="top" align="left">
								<td colspan="3"><br/></td>
							</tr>
						</xsl:if>
					</xsl:if>
				</tbody>
				</table>
				<xsl:if test="string-length(normalize-space(InvoiceDetails/InvoiceFreeText)) != 0">
					<!-- Tehdään taulukko, johon tulostetaan vapaat tekstit. -->
					<table width="100%" cellpadding="0" cellspacing="0">
						<tbody>
							<tr class="invoiceStyle" valign="top" align="left">
								<td width="50%"><br/></td>
								<td width="5px"><br/></td>
								<td>
									<xsl:for-each select="InvoiceDetails/InvoiceFreeText">
										<xsl:call-template name="OutputTextBR">
											<xsl:with-param name="txtText" select="."/>
										</xsl:call-template>
									</xsl:for-each>
								</td>
							</tr>
						</tbody>
					</table>
				</xsl:if>
				<br/>
				<!-- Tehdään tuoteriveille taulukko, johon tulee sarakkeita aiemmin päätelty määrä.	-->
				<table width="100%" cellpadding="0" cellspacing="0" frame="hsides" rules="groups">
					<xsl:if test="InvoiceDetails/InvoiceTypeCode != 'INV03'">
						<thead>
							<tr class="invoiceRowStyle" align="left" valign="top">
								<xsl:if test="$useRowCol1 != 0">
									<th>
										<xsl:if test="$foundArticleName != 0">
											<xsl:value-of select="$txtArticleName"/>
											<br/>
										</xsl:if>
										<xsl:if test="$foundRowIdentifier != 0">
											<xsl:value-of select="$txtRowIdentifier"/>
											<br/>
										</xsl:if>
										<xsl:if test="$foundRowIdentifierDate != 0">
											<xsl:value-of select="$txtRowIdentifierDate"/>
											<br/>
										</xsl:if>
									</th>
								</xsl:if>
								<xsl:if test="$useRowCol2 != 0">
									<th>
										<xsl:if test="$foundArticleIdentifier != 0">
											<xsl:value-of select="$txtArticleIdentifier"/>
											<br/>
										</xsl:if>
										<xsl:if test="$foundBuyerArticleIdentifier != 0">
											<xsl:value-of select="$txtBuyerArticleIdentifier"/>
											<br/>
										</xsl:if>
										<xsl:if test="$foundRowQuotationIdentifier != 0">
											<xsl:value-of select="$txtRowQuotationIdentifier"/>
											<br/>
										</xsl:if>
									</th>
								</xsl:if>
								<xsl:if test="$useRowCol3 != 0">
									<th>
										<xsl:if test="$foundDeliveredQuantity != 0">
											<xsl:value-of select="$txtDeliveredQuantity"/>
											<br/>
										</xsl:if>
									</th>
								</xsl:if>
								<xsl:if test="$useRowCol4 != 0">
									<th>
										<xsl:if test="$foundRowDeliveryDates != 0">
											<xsl:value-of select="$txtRowDeliveryDates"/>
											<br/>
										</xsl:if>
										<xsl:if test="$foundRowDeliveryIdentifier != 0">
											<xsl:value-of select="$txtRowDeliveryIdentifier"/>
											<br/>
										</xsl:if>
										<xsl:if test="$foundRowDiscount != 0">
											<xsl:value-of select="$txtDiscounts"/>
											<br/>
										</xsl:if>
									</th>
								</xsl:if>
								<xsl:if test="$useRowCol5 != 0">
									<th align="right">
										<xsl:if test="$foundUnitPriceAmount != 0">
											<xsl:value-of select="$txtUnitPriceAmount"/>
											<br/>
										</xsl:if>
										<xsl:if test="$foundUnitPriceVatIncludedAmount != 0">
											<xsl:value-of select="$txtUnitPriceVatIncludedAmount"/>
											<br/>
										</xsl:if>
										<xsl:if test="$foundRowAveragePriceAmount != 0">
											<xsl:value-of select="$txtAveragePrice"/>
											<br/>
										</xsl:if>
									</th>
								</xsl:if>
								<xsl:if test="$useRowCol6 != 0">
									<th align="right">
										<xsl:if test="$foundRowVatRatePercent != 0">
											<xsl:value-of select="$txtVat"/>%
											<br/>
										</xsl:if>
									</th>
								</xsl:if>
								<xsl:if test="$useRowCol7 != 0">
									<th align="right">
										<xsl:if test="$foundRowVatAmount != 0">
											<xsl:value-of select="$txtVatAmount"/>
											<br/>
										</xsl:if>
										<xsl:if test="$foundRowVatExcludedAmount != 0">
											<xsl:value-of select="$txtVatExcludedAmount"/>
											<br/>
										</xsl:if>
									</th>
								</xsl:if>
								<xsl:if test="$useRowCol8 != 0">
									<th align="right">
										<xsl:value-of select="$txtRowAmount"/>
									</th>
								</xsl:if>
								<xsl:if test="$useColHdrRow1 + $useColHdrRow2 + $useColHdrRow3 = 0">
									<th></th>
								</xsl:if>
							</tr>
						</thead>
					</xsl:if>
					<tbody>
						<xsl:choose>
							<xsl:when test="/Finvoice/InvoiceDetails/InvoiceTypeCode = 'INV03'">
								<xsl:for-each select="InvoiceRow">
									<xsl:if test="count(RowOverDuePaymentDetails) != 0">
										<xsl:call-template name="OutputRowOverDuePaymentDetails">
											<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
											<xsl:with-param name="addEmptyRow">
												<xsl:if test="position() != 1">1</xsl:if>
											</xsl:with-param>
											<xsl:with-param name="rowOriginalInvoiceIdentifier" select="RowOverDuePaymentDetails/RowOriginalInvoiceIdentifier"/>
											<xsl:with-param name="rowOriginalInvoiceDate">
												<xsl:call-template name="OutputDate">
													<xsl:with-param name="theDate" select="RowOverDuePaymentDetails/RowOriginalInvoiceDate"/>
													<xsl:with-param name="theFormat" select="RowOverDuePaymentDetails/RowOriginalInvoiceDate/@Format"/>
												</xsl:call-template>
											</xsl:with-param>
											<xsl:with-param name="rowOriginalDueDate">
												<xsl:call-template name="OutputDate">
													<xsl:with-param name="theDate" select="RowOverDuePaymentDetails/RowOriginalDueDate"/>
													<xsl:with-param name="theFormat" select="RowOverDuePaymentDetails/RowOriginalDueDate/@Format"/>
												</xsl:call-template>
											</xsl:with-param>
											<xsl:with-param name="rowOriginalInvoiceTotalAmount" select="RowOverDuePaymentDetails/RowOriginalInvoiceTotalAmount"/>
											<xsl:with-param name="rowOriginalInvoiceTotalAmountCI" select="RowOverDuePaymentDetails/RowOriginalInvoiceTotalAmount/@AmountCurrencyIdentifier"/>
											<xsl:with-param name="rowOriginalEpiRemittanceInfoIdentifier" select="RowOverDuePaymentDetails/RowOriginalEpiRemittanceInfoIdentifier"/>
											<xsl:with-param name="rowPaidVatIncludedAmount" select="RowOverDuePaymentDetails/RowPaidVatIncludedAmount"/>
											<xsl:with-param name="rowPaidVatIncludedAmountCI" select="RowOverDuePaymentDetails/RowPaidVatIncludedAmount/@AmountCurrencyIdentifier"/>
											<xsl:with-param name="rowPaidDate">
												<xsl:call-template name="OutputDate">
													<xsl:with-param name="theDate" select="RowOverDuePaymentDetails/RowPaidDate"/>
													<xsl:with-param name="theFormat" select="RowOverDuePaymentDetails/RowPaidDate/@Format"/>
												</xsl:call-template>
											</xsl:with-param>
											<xsl:with-param name="rowUnPaidVatIncludedAmount" select="RowOverDuePaymentDetails/RowUnPaidVatIncludedAmount"/>
											<xsl:with-param name="rowUnPaidVatIncludedAmountCI" select="RowOverDuePaymentDetails/RowUnPaidVatIncludedAmount/@AmountCurrencyIdentifier"/>
											<xsl:with-param name="rowCollectionDate">
												<xsl:call-template name="OutputDate">
													<xsl:with-param name="theDate" select="RowOverDuePaymentDetails/RowCollectionDate"/>
													<xsl:with-param name="theFormat" select="RowOverDuePaymentDetails/RowCollectionDate/@Format"/>
												</xsl:call-template>
											</xsl:with-param>
											<xsl:with-param name="rowCollectionQuantity" select="RowOverDuePaymentDetails/RowCollectionQuantity"/>
											<xsl:with-param name="rowCollectionQuantityQUC" select="RowOverDuePaymentDetails/RowCollectionQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="rowCollectionChargeAmount" select="RowOverDuePaymentDetails/RowCollectionChargeAmount"/>
											<xsl:with-param name="rowCollectionChargeAmountCI" select="RowOverDuePaymentDetails/RowCollectionChargeAmount/@AmountCurrencyIdentifier"/>
											<xsl:with-param name="rowInterestRate" select="RowOverDuePaymentDetails/RowInterestRate"/>
											<xsl:with-param name="rowInterestStartDate">
												<xsl:call-template name="OutputDate">
													<xsl:with-param name="theDate" select="RowOverDuePaymentDetails/RowInterestStartDate"/>
													<xsl:with-param name="theFormat" select="RowOverDuePaymentDetails/RowInterestStartDate/@Format"/>
												</xsl:call-template>
											</xsl:with-param>
											<xsl:with-param name="rowInterestEndDate">
												<xsl:call-template name="OutputDate">
													<xsl:with-param name="theDate" select="RowOverDuePaymentDetails/RowInterestEndDate"/>
													<xsl:with-param name="theFormat" select="RowOverDuePaymentDetails/RowInterestEndDate/@Format"/>
												</xsl:call-template>
											</xsl:with-param>
											<xsl:with-param name="rowInterestPeriodText" select="RowOverDuePaymentDetails/RowInterestPeriodText"/>
											<xsl:with-param name="rowInterestDateNumber" select="RowOverDuePaymentDetails/RowInterestDateNumber"/>
											<xsl:with-param name="rowInterestChargeAmount" select="RowOverDuePaymentDetails/RowInterestChargeAmount"/>
											<xsl:with-param name="rowInterestChargeAmountCI" select="RowOverDuePaymentDetails/RowInterestChargeAmount/@AmountCurrencyIdentifier"/>
											<xsl:with-param name="rowInterestChargeVatAmount" select="RowOverDuePaymentDetails/RowInterestChargeVatAmount"/>
											<xsl:with-param name="rowInterestChargeVatAmountCI" select="RowOverDuePaymentDetails/RowInterestChargeVatAmount/@AmountCurrencyIdentifier"/>
										</xsl:call-template>
									</xsl:if>
									<xsl:for-each select="SubInvoiceRow">
										<xsl:if test="count(SubRowOverDuePaymentDetails) != 0">
											<xsl:call-template name="OutputRowOverDuePaymentDetails">
												<xsl:with-param name="styleName" select="'subRowStyle'"/>
												<xsl:with-param name="addEmptyRow">1</xsl:with-param>
												<xsl:with-param name="rowOriginalInvoiceIdentifier" select="SubRowOverDuePaymentDetails/SubRowOriginalInvoiceIdentifier"/>
												<xsl:with-param name="rowOriginalInvoiceDate">
													<xsl:call-template name="OutputDate">
														<xsl:with-param name="theDate" select="SubRowOverDuePaymentDetails/SubRowOriginalInvoiceDate"/>
														<xsl:with-param name="theFormat" select="SubRowOverDuePaymentDetails/SubRowOriginalInvoiceDate/@Format"/>
													</xsl:call-template>
												</xsl:with-param>
												<xsl:with-param name="rowOriginalDueDate">
													<xsl:call-template name="OutputDate">
														<xsl:with-param name="theDate" select="SubRowOverDuePaymentDetails/SubRowOriginalDueDate"/>
														<xsl:with-param name="theFormat" select="SubRowOverDuePaymentDetails/SubRowOriginalDueDate/@Format"/>
													</xsl:call-template>
												</xsl:with-param>
												<xsl:with-param name="rowOriginalInvoiceTotalAmount" select="SubRowOverDuePaymentDetails/SubRowOriginalInvoiceTotalAmount"/>
												<xsl:with-param name="rowOriginalInvoiceTotalAmountCI" select="SubRowOverDuePaymentDetails/SubRowOriginalInvoiceTotalAmount/@AmountCurrencyIdentifier"/>
												<xsl:with-param name="rowOriginalEpiRemittanceInfoIdentifier" select="SubRowOverDuePaymentDetails/SubRowOriginalEpiRemittanceInfoIdentifier"/>
												<xsl:with-param name="rowPaidVatIncludedAmount" select="SubRowOverDuePaymentDetails/SubRowPaidVatIncludedAmount"/>
												<xsl:with-param name="rowPaidVatIncludedAmountCI" select="SubRowOverDuePaymentDetails/SubRowPaidVatIncludedAmount/@AmountCurrencyIdentifier"/>
												<xsl:with-param name="rowPaidDate">
													<xsl:call-template name="OutputDate">
														<xsl:with-param name="theDate" select="SubRowOverDuePaymentDetails/SubRowPaidDate"/>
														<xsl:with-param name="theFormat" select="SubRowOverDuePaymentDetails/SubRowPaidDate/@Format"/>
													</xsl:call-template>
												</xsl:with-param>
												<xsl:with-param name="rowUnPaidVatIncludedAmount" select="SubRowOverDuePaymentDetails/SubRowUnPaidVatIncludedAmount"/>
												<xsl:with-param name="rowUnPaidVatIncludedAmountCI" select="SubRowOverDuePaymentDetails/SubRowUnPaidVatIncludedAmount/@AmountCurrencyIdentifier"/>
												<xsl:with-param name="rowCollectionDate">
													<xsl:call-template name="OutputDate">
														<xsl:with-param name="theDate" select="SubRowOverDuePaymentDetails/SubRowCollectionDate"/>
														<xsl:with-param name="theFormat" select="SubRowOverDuePaymentDetails/SubRowCollectionDate/@Format"/>
													</xsl:call-template>
												</xsl:with-param>
												<xsl:with-param name="rowCollectionQuantity" select="SubRowOverDuePaymentDetails/SubRowCollectionQuantity"/>
												<xsl:with-param name="rowCollectionQuantityQUC" select="SubRowOverDuePaymentDetails/SubRowCollectionQuantity/@QuantityUnitCode"/>
												<xsl:with-param name="rowCollectionChargeAmount" select="SubRowOverDuePaymentDetails/SubRowCollectionChargeAmount"/>
												<xsl:with-param name="rowCollectionChargeAmountCI" select="SubRowOverDuePaymentDetails/SubRowCollectionChargeAmount/@AmountCurrencyIdentifier"/>
												<xsl:with-param name="rowInterestRate" select="SubRowOverDuePaymentDetails/SubRowInterestRate"/>
												<xsl:with-param name="rowInterestStartDate">
													<xsl:call-template name="OutputDate">
														<xsl:with-param name="theDate" select="SubRowOverDuePaymentDetails/SubRowInterestStartDate"/>
														<xsl:with-param name="theFormat" select="SubRowOverDuePaymentDetails/SubRowInterestStartDate/@Format"/>
													</xsl:call-template>
												</xsl:with-param>
												<xsl:with-param name="rowInterestEndDate">
													<xsl:call-template name="OutputDate">
														<xsl:with-param name="theDate" select="SubRowOverDuePaymentDetails/SubRowInterestEndDate"/>
														<xsl:with-param name="theFormat" select="SubRowOverDuePaymentDetails/SubRowInterestEndDate/@Format"/>
													</xsl:call-template>
												</xsl:with-param>
												<xsl:with-param name="rowInterestPeriodText" select="SubRowOverDuePaymentDetails/SubRowInterestPeriodText"/>
												<xsl:with-param name="rowInterestDateNumber" select="SubRowOverDuePaymentDetails/SubRowInterestDateNumber"/>
												<xsl:with-param name="rowInterestChargeAmount" select="SubRowOverDuePaymentDetails/SubRowInterestChargeAmount"/>
												<xsl:with-param name="rowInterestChargeAmountCI" select="SubRowOverDuePaymentDetails/SubRowInterestChargeAmount/@AmountCurrencyIdentifier"/>
												<xsl:with-param name="rowInterestChargeVatAmount" select="SubRowOverDuePaymentDetails/SubRowInterestChargeVatAmount"/>
												<xsl:with-param name="rowInterestChargeVatAmountCI" select="SubRowOverDuePaymentDetails/SubRowInterestChargeVatAmount/@AmountCurrencyIdentifier"/>
											</xsl:call-template>
										</xsl:if>
									</xsl:for-each>
								</xsl:for-each>
							</xsl:when>
							<xsl:otherwise>
								<xsl:for-each select="InvoiceRow">
									<xsl:variable name="rowDefinitions">
										<xsl:for-each select="RowDefinitionDetails">
											<xsl:call-template name="OutputTableRow">
												<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
												<xsl:with-param name="txtTitle" select="RowDefinitionHeaderText"/>
												<xsl:with-param name="txtText" select="RowDefinitionValue"/>
												<xsl:with-param name="txtText2" select="RowDefinitionValue/@QuantityUnitCode"/>
											</xsl:call-template>
										</xsl:for-each>
									</xsl:variable>
									<xsl:variable name="deliveredQuantities">
										<xsl:for-each select="DeliveredQuantity">
											<xsl:call-template name="OutputTextBR">
												<xsl:with-param name="txtText">
													<xsl:call-template name="BuildString">
														<xsl:with-param name="txtText" select="."/>
														<xsl:with-param name="txtText2" select="./@QuantityUnitCode"/>
													</xsl:call-template>
												</xsl:with-param>
											</xsl:call-template>
										</xsl:for-each>
									</xsl:variable>
									<xsl:variable name="invoicedQuantities">
										<xsl:for-each select="InvoicedQuantity">
											<xsl:call-template name="OutputTableRow">
												<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
												<xsl:with-param name="txtTitle" select="$txtInvoicedQuantity"/>
												<xsl:with-param name="txtText" select="."/>
												<xsl:with-param name="txtText2" select="./@QuantityUnitCode"/>
											</xsl:call-template>
										</xsl:for-each>
									</xsl:variable>
									<xsl:variable name="discount1">
										<xsl:call-template name="OutputRowDiscount">
											<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
											<xsl:with-param name="discountNumber">1</xsl:with-param>
											<xsl:with-param name="rowDiscountTypeText" select="RowDiscountTypeText"/>
											<xsl:with-param name="rowDiscountPercent" select="RowDiscountPercent"/>
											<xsl:with-param name="rowDiscountAmount" select="RowDiscountAmount"/>
											<xsl:with-param name="rowDiscountAmountCurrencyIdentifier" select="RowDiscountAmount/@AmountCurrencyIdentifier"/>
										</xsl:call-template>
									</xsl:variable>
									<xsl:variable name="discountOffset">
										<xsl:choose>
											<xsl:when test="string-length(normalize-space($discount1)) = 0">0</xsl:when>
											<xsl:otherwise>1</xsl:otherwise>
										</xsl:choose>
									</xsl:variable>
									<xsl:variable name="discounts">
										<xsl:if test="$discountOffset = 1"><xsl:copy-of select="$discount1"/></xsl:if>
										<xsl:for-each select="RowProgressiveDiscountDetails">
											<xsl:call-template name="OutputRowDiscount">
												<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
												<xsl:with-param name="discountNumber" select="position() + $discountOffset"/>
												<xsl:with-param name="rowDiscountTypeText" select="RowDiscountTypeText"/>
												<xsl:with-param name="rowDiscountPercent" select="RowDiscountPercent"/>
												<xsl:with-param name="rowDiscountAmount" select="RowDiscountAmount"/>
												<xsl:with-param name="rowDiscountAmountCurrencyIdentifier" select="RowDiscountAmount/@AmountCurrencyIdentifier"/>
											</xsl:call-template>
										</xsl:for-each>
									</xsl:variable>
									<xsl:variable name="invoiceRowHTML">
										<xsl:call-template name="OutputRow">
											<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
											<xsl:with-param name="addEmptyRow">
												<xsl:if test="position() != 1">1</xsl:if>
											</xsl:with-param>
											<xsl:with-param name="articleIdentifier" select="ArticleIdentifier"/>
											<xsl:with-param name="articleName" select="ArticleName"/>
											<xsl:with-param name="articleInfoUrlText" select="ArticleInfoUrlText"/>
											<xsl:with-param name="buyerArticleIdentifier" select="BuyerArticleIdentifier"/>
											<xsl:with-param name="offeredQuantity" select="OfferedQuantity"/>
											<xsl:with-param name="offeredQuantityUnitCode" select="OfferedQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="deliveredQuantities" select="$deliveredQuantities"/>
											<xsl:with-param name="orderedQuantity" select="OrderedQuantity"/>
											<xsl:with-param name="orderedQuantityUnitCode" select="OrderedQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="confirmedQuantity" select="ConfirmedQuantity"/>
											<xsl:with-param name="confirmedQuantityUnitCode" select="ConfirmedQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="postDeliveredQuantity" select="PostDeliveredQuantity"/>
											<xsl:with-param name="postDeliveredQuantityUnitCode" select="PostDeliveredQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="invoicedQuantities" select="$invoicedQuantities"/>
											<xsl:with-param name="creditRequestedQuantity" select="CreditRequestedQuantity"/>
											<xsl:with-param name="creditRequestedQuantityUnitCode" select="CreditRequestedQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="returnedQuantity" select="ReturnedQuantity"/>
											<xsl:with-param name="returnedQuantityUnitCode" select="ReturnedQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="startDate" select="StartDate"/>
											<xsl:with-param name="startDateFormat" select="StartDate/@Format"/>
											<xsl:with-param name="endDate" select="EndDate"/>
											<xsl:with-param name="endDateFormat" select="EndDate/@Format"/>
											<xsl:with-param name="unitPriceAmount" select="UnitPriceAmount"/>
											<xsl:with-param name="unitPriceUnitCode" select="UnitPriceAmount/@UnitPriceUnitCode"/>
											<xsl:with-param name="unitPriceVatIncludedAmount" select="UnitPriceVatIncludedAmount"/>
											<xsl:with-param name="unitPriceVatIncludedUnitCode" select="UnitPriceVatIncludedAmount/@UnitPriceUnitCode"/>
											<xsl:with-param name="unitPriceBaseQuantity" select="UnitPriceBaseQuantity"/>
											<xsl:with-param name="unitPriceBaseQuantityUnitCode" select="UnitPriceBaseQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="rowIdentifier" select="RowIdentifier"/>
											<xsl:with-param name="rowIdentifierUrlText" select="RowIdentifierUrlText"/>
											<xsl:with-param name="rowIdentifierDate" select="RowIdentifierDate"/>
											<xsl:with-param name="rowIdentifierDateFormat" select="RowIdentifierDate/@Format"/>
											<xsl:with-param name="rowDeliveryIdentifier" select="RowDeliveryIdentifier"/>
											<xsl:with-param name="rowDeliveryIdentifierUrlText" select="RowDeliveryIdentifierUrlText"/>
											<xsl:with-param name="rowDeliveryDate" select="RowDeliveryDate"/>
											<xsl:with-param name="rowDeliveryDateFormat" select="RowDeliveryDate/@Format"/>
											<xsl:with-param name="rowQuotationIdentifier" select="RowQuotationIdentifier"/>
											<xsl:with-param name="rowQuotationIdentifierUrlText" select="RowQuotationIdentifierUrlText"/>
											<xsl:with-param name="rowDiscounts" select="$discounts"/>
											<xsl:with-param name="rowVatRatePercent" select="RowVatRatePercent"/>
											<xsl:with-param name="rowVatAmount" select="RowVatAmount"/>
											<xsl:with-param name="rowVatExcludedAmount" select="RowVatExcludedAmount"/>
											<xsl:with-param name="rowAmount" select="RowAmount"/>
											<xsl:with-param name="rowTerminalAddressText" select="RowDeliveryDetails/RowTerminalAddressText"/>
											<xsl:with-param name="rowWaybillIdentifier" select="RowDeliveryDetails/RowWaybillIdentifier"/>
											<xsl:with-param name="rowWaybillMakerText" select="RowDeliveryDetails/RowWaybillMakerText"/>
											<xsl:with-param name="rowAgreementIdentifier" select="RowAgreementIdentifier"/>
											<xsl:with-param name="rowAgreementIdentifierUrlText" select="RowAgreementIdentifierUrlText"/>
											<xsl:with-param name="originalInvoiceNumber" select="OriginalInvoiceNumber"/>
											<xsl:with-param name="rowPriceListIdentifier" select="RowPriceListIdentifier"/>
											<xsl:with-param name="rowPriceListIdentifierUrlText" select="RowPriceListIdentifierUrlText"/>
											<xsl:with-param name="rowRequestOfQuotationIdentifier" select="RowRequestOfQuotationIdentifier"/>
											<xsl:with-param name="rowRequestOfQuotationIdentifierUrlText" select="RowRequestOfQuotationIdentifierUrlText"/>
											<xsl:with-param name="rowDelivererName1" select="RowDeliveryDetails/RowDelivererName[1]"/>
											<xsl:with-param name="rowDelivererName2" select="RowDeliveryDetails/RowDelivererName[2]"/>
											<xsl:with-param name="rowDelivererName3" select="RowDeliveryDetails/RowDelivererName[3]"/>
											<xsl:with-param name="rowDelivererIdentifier" select="RowDeliveryDetails/RowDelivererIdentifier"/>
											<xsl:with-param name="rowDelivererCountryCode" select="RowDeliveryDetails/RowDelivererCountryCode"/>
											<xsl:with-param name="rowDelivererCountryName" select="RowDeliveryDetails/RowDelivererCountryName"/>
											<xsl:with-param name="rowManufacturerName1" select="RowDeliveryDetails/RowManufacturerName[1]"/>
											<xsl:with-param name="rowManufacturerName2" select="RowDeliveryDetails/RowManufacturerName[2]"/>
											<xsl:with-param name="rowManufacturerName3" select="RowDeliveryDetails/RowManufacturerName[3]"/>
											<xsl:with-param name="rowManufacturerIdentifier" select="RowDeliveryDetails/RowManufacturerIdentifier"/>
											<xsl:with-param name="rowManufacturerCountryCode" select="RowDeliveryDetails/RowManufacturerCountryCode"/>
											<xsl:with-param name="rowManufacturerCountryName" select="RowDeliveryDetails/RowManufacturerCountryName"/>
											<xsl:with-param name="rowShortProposedAccountIdentifier" select="RowShortProposedAccountIdentifier"/>
											<xsl:with-param name="rowNormalProposedAccountIdentifier" select="RowNormalProposedAccountIdentifier"/>
											<xsl:with-param name="rowProposedAccountText" select="RowProposedAccountText"/>
											<xsl:with-param name="rowAccountDimensionText" select="RowAccountDimensionText"/>
											<xsl:with-param name="rowAveragePriceAmount" select="RowAveragePriceAmount"/>
											<xsl:with-param name="articleGroupIdentifier" select="ArticleGroupIdentifier"/>
											<xsl:with-param name="eanCode" select="EanCode"/>
											<xsl:with-param name="rowRegistrationNumberIdentifier" select="RowRegistrationNumberIdentifier"/>
											<xsl:with-param name="serialNumberIdentifier" select="SerialNumberIdentifier"/>
											<xsl:with-param name="rowActionCode" select="RowActionCode"/>
											<xsl:with-param name="rowClearanceIdentifier" select="RowDeliveryDetails/RowClearanceIdentifier"/>
											<xsl:with-param name="rowDeliveryNoteIdentifier" select="RowDeliveryDetails/RowDeliveryNoteIdentifier"/>
											<xsl:with-param name="rowPlaceOfDischarge" select="RowDeliveryDetails/RowPlaceOfDischarge"/>
											<xsl:with-param name="rowFinalDestinationName1" select="RowDeliveryDetails/RowFinalDestinationName[1]"/>
											<xsl:with-param name="rowFinalDestinationName2" select="RowDeliveryDetails/RowFinalDestinationName[2]"/>
											<xsl:with-param name="rowFinalDestinationName3" select="RowDeliveryDetails/RowFinalDestinationName[3]"/>
											<xsl:with-param name="rowDefinitions" select="$rowDefinitions"/>
											<xsl:with-param name="rowOrdererName" select="RowOrdererName"/>
											<xsl:with-param name="rowSalesPersonName" select="RowSalesPersonName"/>
											<xsl:with-param name="rowProjectReferenceIdentifier" select="RowProjectReferenceIdentifier"/>
											<xsl:with-param name="rowManufacturerArticleIdentifier" select="RowDeliveryDetails/RowManufacturerArticleIdentifier"/>
											<xsl:with-param name="rowManufacturerOrderIdentifier" select="RowDeliveryDetails/RowManufacturerOrderIdentifier"/>
											<xsl:with-param name="rowPackageLength" select="RowDeliveryDetails/RowPackageDetails/RowPackageLength"/>
											<xsl:with-param name="rowPackageLengthQUC" select="RowDeliveryDetails/RowPackageDetails/RowPackageLength/@QuantityUnitCode"/>
											<xsl:with-param name="rowPackageWidth" select="RowDeliveryDetails/RowPackageDetails/RowPackageWidth"/>
											<xsl:with-param name="rowPackageWidthQUC" select="RowDeliveryDetails/RowPackageDetails/RowPackageWidth/@QuantityUnitCode"/>
											<xsl:with-param name="rowPackageHeight" select="RowDeliveryDetails/RowPackageDetails/RowPackageHeight"/>
											<xsl:with-param name="rowPackageHeightQUC" select="RowDeliveryDetails/RowPackageDetails/RowPackageHeight/@QuantityUnitCode"/>
											<xsl:with-param name="rowPackageWeight" select="RowDeliveryDetails/RowPackageDetails/RowPackageWeight"/>
											<xsl:with-param name="rowPackageWeightQUC" select="RowDeliveryDetails/RowPackageDetails/RowPackageWeight/@QuantityUnitCode"/>
											<xsl:with-param name="rowPackageNetWeight" select="RowDeliveryDetails/RowPackageDetails/RowPackageNetWeight"/>
											<xsl:with-param name="rowPackageNetWeightQUC" select="RowDeliveryDetails/RowPackageDetails/RowPackageNetWeight/@QuantityUnitCode"/>
											<xsl:with-param name="rowPackageVolume" select="RowDeliveryDetails/RowPackageDetails/RowPackageVolume"/>
											<xsl:with-param name="rowPackageVolumeQUC" select="RowDeliveryDetails/RowPackageDetails/RowPackageVolume/@QuantityUnitCode"/>
											<xsl:with-param name="rowTransportCarriageQuantity" select="RowDeliveryDetails/RowPackageDetails/RowTransportCarriageQuantity"/>
											<xsl:with-param name="rowTransportCarriageQuantityQUC" select="RowDeliveryDetails/RowPackageDetails/RowTransportCarriageQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="rowUsedQuantity" select="RowUsedQuantity"/>
											<xsl:with-param name="rowUsedQuantityQUC" select="RowUsedQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="rowPreviousMeterReadingDate">
												<xsl:call-template name="OutputDate">
													<xsl:with-param name="theDate" select="RowPreviousMeterReadingDate"/>
													<xsl:with-param name="theFormat" select="RowPreviousMeterReadingDate/@Format"/>
												</xsl:call-template>
											</xsl:with-param>
											<xsl:with-param name="rowLatestMeterReadingDate">
												<xsl:call-template name="OutputDate">
													<xsl:with-param name="theDate" select="RowLatestMeterReadingDate"/>
													<xsl:with-param name="theFormat" select="RowLatestMeterReadingDate/@Format"/>
												</xsl:call-template>
											</xsl:with-param>
											<xsl:with-param name="rowCalculatedQuantity" select="RowCalculatedQuantity"/>
											<xsl:with-param name="rowCalculatedQuantityQUC" select="RowCalculatedQuantity/@QuantityUnitCode"/>
										</xsl:call-template>
										<xsl:if test="count(RowFreeText) != 0">
											<tr class="rowPreStyle">
												<td colspan="8">
													<xsl:for-each select="RowFreeText">
														<xsl:if test="position() != 1"><br/></xsl:if>
														<xsl:value-of select="."/>
													</xsl:for-each>
												</td>
											</tr>
										</xsl:if>
									</xsl:variable>
									<xsl:if test="string-length(normalize-space($invoiceRowHTML)) != 0">
										<xsl:copy-of select="$invoiceRowHTML"/>
									</xsl:if>
									<xsl:if test="count(RowAnyPartyDetails) != 0">
										<tr class="invoiceRowStyle" valign="top" align="left">
											<td colspan="8">
												<table cellpadding="0" cellspacing="0">
													<tbody>
														<xsl:for-each select="RowAnyPartyDetails">
															<xsl:if test="(position() mod 2) != 0">
																<xsl:variable name="possu" select="position()"/>
																<xsl:if test="$possu != 1">
																	<tr class="invoiceRowStyle" valign="top" align="left">
																		<td colspan="8"><br/></td>
																	</tr>
																</xsl:if>
																<tr class="invoiceRowStyle" valign="top" align="left">
																	<xsl:call-template name="OutputAnyPartyDetails">
																		<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
																		<xsl:with-param name="info" select="$txtInvoiceRow"/>
																		<xsl:with-param name="anyPartyText" select="RowAnyPartyText"/>
																		<xsl:with-param name="anyPartyTextAnyPartyCode" select="RowAnyPartyText/@AnyPartyCode"/>
																		<xsl:with-param name="anyPartyIdentifier" select="RowAnyPartyIdentifier"/>
																		<xsl:with-param name="anyPartyOrganisationName1" select="RowAnyPartyOrganisationName[1]"/>
																		<xsl:with-param name="anyPartyOrganisationName2" select="RowAnyPartyOrganisationName[2]"/>
																		<xsl:with-param name="anyPartyOrganisationDepartment1" select="RowAnyPartyOrganisationDepartment[1]"/>
																		<xsl:with-param name="anyPartyOrganisationDepartment2" select="RowAnyPartyOrganisationDepartment[2]"/>
																		<xsl:with-param name="anyPartyOrganisationUnitNumber" select="RowAnyPartyOrganisationUnitNumber"/>
																		<xsl:with-param name="anyPartySiteCode" select="RowAnyPartySiteCode"/>
																		<xsl:with-param name="anyPartyStreetName1" select="RowAnyPartyPostalAddressDetails/RowAnyPartyStreetName[1]"/>
																		<xsl:with-param name="anyPartyStreetName2" select="RowAnyPartyPostalAddressDetails/RowAnyPartyStreetName[2]"/>
																		<xsl:with-param name="anyPartyStreetName3" select="RowAnyPartyPostalAddressDetails/RowAnyPartyStreetName[3]"/>
																		<xsl:with-param name="anyPartyTownName" select="RowAnyPartyPostalAddressDetails/RowAnyPartyTownName"/>
																		<xsl:with-param name="anyPartyPostCodeIdentifier" select="RowAnyPartyPostalAddressDetails/RowAnyPartyPostCodeIdentifier"/>
																		<xsl:with-param name="countryCode" select="RowAnyPartyPostalAddressDetails/CountryCode"/>
																		<xsl:with-param name="countryName" select="RowAnyPartyPostalAddressDetails/CountryName"/>
																		<xsl:with-param name="anyPartyPostOfficeBoxIdentifier" select="RowAnyPartyPostalAddressDetails/RowAnyPartyPostOfficeBoxIdentifier"/>
																	</xsl:call-template>
																	<td width="40px"><br/></td>
																	<xsl:call-template name="OutputAnyPartyDetails">
																		<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
																		<xsl:with-param name="info" select="$txtInvoiceRow"/>
																		<xsl:with-param name="anyPartyText" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyText"/>
																		<xsl:with-param name="anyPartyTextAnyPartyCode" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyText/@AnyPartyCode"/>
																		<xsl:with-param name="anyPartyIdentifier" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyIdentifier"/>
																		<xsl:with-param name="anyPartyOrganisationName1" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyOrganisationName[1]"/>
																		<xsl:with-param name="anyPartyOrganisationName2" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyOrganisationName[2]"/>
																		<xsl:with-param name="anyPartyOrganisationDepartment1" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyOrganisationDepartment[1]"/>
																		<xsl:with-param name="anyPartyOrganisationDepartment2" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyOrganisationDepartment[2]"/>
																		<xsl:with-param name="anyPartyOrganisationUnitNumber" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyOrganisationUnitNumber"/>
																		<xsl:with-param name="anyPartySiteCode" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartySiteCode"/>
																		<xsl:with-param name="anyPartyStreetName1" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyPostalAddressDetails/RowAnyPartyStreetName[1]"/>
																		<xsl:with-param name="anyPartyStreetName2" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyPostalAddressDetails/RowAnyPartyStreetName[2]"/>
																		<xsl:with-param name="anyPartyStreetName3" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyPostalAddressDetails/RowAnyPartyStreetName[3]"/>
																		<xsl:with-param name="anyPartyTownName" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyPostalAddressDetails/RowAnyPartyTownName"/>
																		<xsl:with-param name="anyPartyPostCodeIdentifier" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyPostalAddressDetails/RowAnyPartyPostCodeIdentifier"/>
																		<xsl:with-param name="countryCode" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyPostalAddressDetails/CountryCode"/>
																		<xsl:with-param name="countryName" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyPostalAddressDetails/CountryName"/>
																		<xsl:with-param name="anyPartyPostOfficeBoxIdentifier" select="../RowAnyPartyDetails[position() = $possu + 1]/RowAnyPartyPostalAddressDetails/RowAnyPartyPostOfficeBoxIdentifier"/>
																	</xsl:call-template>
																</tr>
															</xsl:if>
														</xsl:for-each>
													</tbody>
												</table>
											</td>
										</tr>
									</xsl:if>
									<xsl:for-each select="SubInvoiceRow">
										<xsl:variable name="subRowDefinitions">
											<xsl:for-each select="SubRowDefinitionDetails">
												<xsl:call-template name="OutputTableRow">
													<xsl:with-param name="styleName" select="'subRowStyle'"/>
													<xsl:with-param name="txtTitle" select="SubRowDefinitionHeaderText"/>
													<xsl:with-param name="txtText" select="SubRowDefinitionValue"/>
													<xsl:with-param name="txtText2" select="SubRowDefinitionValue/@QuantityUnitCode"/>
												</xsl:call-template>
											</xsl:for-each>
										</xsl:variable>
										<xsl:variable name="subDeliveredQuantities">
											<xsl:for-each select="SubDeliveredQuantity">
												<xsl:call-template name="OutputTextBR">
													<xsl:with-param name="txtText">
														<xsl:call-template name="BuildString">
															<xsl:with-param name="txtText" select="."/>
															<xsl:with-param name="txtText2" select="./@QuantityUnitCode"/>
														</xsl:call-template>
													</xsl:with-param>
												</xsl:call-template>
											</xsl:for-each>
										</xsl:variable>
										<xsl:variable name="subInvoicedQuantities">
											<xsl:for-each select="SubInvoicedQuantity">
												<xsl:call-template name="OutputTableRow">
													<xsl:with-param name="styleName" select="'subRowStyle'"/>
													<xsl:with-param name="txtTitle" select="$txtInvoicedQuantity"/>
													<xsl:with-param name="txtText" select="."/>
													<xsl:with-param name="txtText2" select="./@QuantityUnitCode"/>
												</xsl:call-template>
											</xsl:for-each>
										</xsl:variable>
										<xsl:variable name="subDiscount1">
											<xsl:call-template name="OutputRowDiscount">
												<xsl:with-param name="styleName" select="'subRowStyle'"/>
												<xsl:with-param name="discountNumber">1</xsl:with-param>
												<xsl:with-param name="rowDiscountTypeText" select="SubRowDiscountTypeText"/>
												<xsl:with-param name="rowDiscountPercent" select="SubRowDiscountPercent"/>
												<xsl:with-param name="rowDiscountAmount" select="SubRowDiscountAmount"/>
												<xsl:with-param name="rowDiscountAmountCurrencyIdentifier" select="SubRowDiscountAmount/@AmountCurrencyIdentifier"/>
											</xsl:call-template>
										</xsl:variable>
										<xsl:variable name="subDiscountOffset">
											<xsl:choose>
												<xsl:when test="string-length(normalize-space($subDiscount1)) = 0">0</xsl:when>
												<xsl:otherwise>1</xsl:otherwise>
											</xsl:choose>
										</xsl:variable>
										<xsl:variable name="subDiscounts">
											<xsl:if test="$subDiscountOffset = 1"><xsl:copy-of select="$subDiscount1"/></xsl:if>
											<xsl:for-each select="SubRowProgressiveDiscountDetails">
												<xsl:call-template name="OutputRowDiscount">
													<xsl:with-param name="styleName" select="'subRowStyle'"/>
													<xsl:with-param name="discountNumber" select="position() + $subDiscountOffset"/>
													<xsl:with-param name="rowDiscountTypeText" select="SubRowDiscountTypeText"/>
													<xsl:with-param name="rowDiscountPercent" select="SubRowDiscountPercent"/>
													<xsl:with-param name="rowDiscountAmount" select="SubRowDiscountAmount"/>
													<xsl:with-param name="rowDiscountAmountCurrencyIdentifier" select="SubRowDiscountAmount/@AmountCurrencyIdentifier"/>
												</xsl:call-template>
											</xsl:for-each>
										</xsl:variable>
										<xsl:call-template name="OutputRow">
											<xsl:with-param name="styleName" select="'subRowStyle'"/>
											<xsl:with-param name="addEmptyRow">1</xsl:with-param>
											<xsl:with-param name="articleIdentifier" select="SubArticleIdentifier"/>
											<xsl:with-param name="articleName" select="SubArticleName"/>
											<xsl:with-param name="articleInfoUrlText" select="SubArticleInfoUrlText"/>
											<xsl:with-param name="buyerArticleIdentifier" select="SubBuyerArticleIdentifier"/>
											<xsl:with-param name="offeredQuantity" select="SubOfferedQuantity"/>
											<xsl:with-param name="offeredQuantityUnitCode" select="SubOfferedQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="deliveredQuantities" select="$subDeliveredQuantities"/>
											<xsl:with-param name="orderedQuantity" select="SubOrderedQuantity"/>
											<xsl:with-param name="orderedQuantityUnitCode" select="SubOrderedQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="confirmedQuantity" select="SubConfirmedQuantity"/>
											<xsl:with-param name="confirmedQuantityUnitCode" select="SubConfirmedQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="postDeliveredQuantity" select="SubPostDeliveredQuantity"/>
											<xsl:with-param name="postDeliveredQuantityUnitCode" select="SubPostDeliveredQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="invoicedQuantities" select="$subInvoicedQuantities"/>
											<xsl:with-param name="creditRequestedQuantity" select="SubCreditRequestedQuantity"/>
											<xsl:with-param name="creditRequestedQuantityUnitCode" select="SubCreditRequestedQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="returnedQuantity" select="SubReturnedQuantity"/>
											<xsl:with-param name="returnedQuantityUnitCode" select="SubReturnedQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="startDate" select="SubStartDate"/>
											<xsl:with-param name="startDateFormat" select="SubStartDate/@Format"/>
											<xsl:with-param name="endDate" select="SubEndDate"/>
											<xsl:with-param name="endDateFormat" select="SubEndDate/@Format"/>
											<xsl:with-param name="unitPriceAmount" select="SubUnitPriceAmount"/>
											<xsl:with-param name="unitPriceUnitCode" select="SubUnitPriceAmount/@UnitPriceUnitCode"/>
											<xsl:with-param name="unitPriceVatIncludedAmount" select="SubUnitPriceVatIncludedAmount"/>
											<xsl:with-param name="unitPriceVatIncludedUnitCode" select="SubUnitPriceVatIncludedAmount/@UnitPriceUnitCode"/>
											<xsl:with-param name="unitPriceBaseQuantity" select="SubUnitPriceBaseQuantity"/>
											<xsl:with-param name="unitPriceBaseQuantityUnitCode" select="SubUnitPriceBaseQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="rowIdentifier" select="SubRowIdentifier"/>
											<xsl:with-param name="rowIdentifierUrlText" select="SubRowIdentifierUrlText"/>
											<xsl:with-param name="rowIdentifierDate" select="SubRowIdentifierDate"/>
											<xsl:with-param name="rowIdentifierDateFormat" select="SubRowIdentifierDate/@Format"/>
											<xsl:with-param name="rowDeliveryIdentifier" select="SubRowDeliveryIdentifier"/>
											<xsl:with-param name="rowDeliveryIdentifierUrlText" select="SubRowDeliveryIdentifierUrlText"/>
											<xsl:with-param name="rowDeliveryDate" select="SubRowDeliveryDate"/>
											<xsl:with-param name="rowDeliveryDateFormat" select="SubRowDeliveryDate/@Format"/>
											<xsl:with-param name="rowQuotationIdentifier" select="SubRowQuotationIdentifier"/>
											<xsl:with-param name="rowQuotationIdentifierUrlText" select="SubRowQuotationIdentifierUrlText"/>
											<xsl:with-param name="rowDiscounts" select="$subDiscounts"/>
											<xsl:with-param name="rowVatRatePercent" select="SubRowVatRatePercent"/>
											<xsl:with-param name="rowVatAmount" select="SubRowVatAmount"/>
											<xsl:with-param name="rowVatExcludedAmount" select="SubRowVatExcludedAmount"/>
											<xsl:with-param name="rowAmount" select="SubRowAmount"/>
											<xsl:with-param name="rowTerminalAddressText" select="SubRowDeliveryDetails/SubRowTerminalAddressText"/>
											<xsl:with-param name="rowWaybillIdentifier" select="SubRowDeliveryDetails/SubRowWaybillIdentifier"/>
											<xsl:with-param name="rowWaybillMakerText" select="SubRowDeliveryDetails/SubRowWaybillMakerText"/>
											<xsl:with-param name="rowAgreementIdentifier" select="SubRowAgreementIdentifier"/>
											<xsl:with-param name="rowAgreementIdentifierUrlText" select="SubRowAgreementIdentifierUrlText"/>
											<xsl:with-param name="originalInvoiceNumber" select="SubOriginalInvoiceNumber"/>
											<xsl:with-param name="rowPriceListIdentifier" select="SubRowPriceListIdentifier"/>
											<xsl:with-param name="rowPriceListIdentifierUrlText" select="SubRowPriceListIdentifierUrlText"/>
											<xsl:with-param name="rowRequestOfQuotationIdentifier" select="SubRowRequestOfQuotationIdentifier"/>
											<xsl:with-param name="rowRequestOfQuotationIdentifierUrlText" select="SubRowRequestOfQuotationIdentifierUrlText"/>
											<xsl:with-param name="rowDelivererName1" select="SubRowDeliveryDetails/SubRowDelivererName[1]"/>
											<xsl:with-param name="rowDelivererName2" select="SubRowDeliveryDetails/SubRowDelivererName[2]"/>
											<xsl:with-param name="rowDelivererName3" select="SubRowDeliveryDetails/SubRowDelivererName[3]"/>
											<xsl:with-param name="rowDelivererIdentifier" select="SubRowDeliveryDetails/SubRowDelivererIdentifier"/>
											<xsl:with-param name="rowDelivererCountryCode" select="SubRowDeliveryDetails/SubRowDelivererCountryCode"/>
											<xsl:with-param name="rowDelivererCountryName" select="SubRowDeliveryDetails/SubRowDelivererCountryName"/>
											<xsl:with-param name="rowManufacturerName1" select="SubRowDeliveryDetails/SubRowManufacturerName[1]"/>
											<xsl:with-param name="rowManufacturerName2" select="SubRowDeliveryDetails/SubRowManufacturerName[2]"/>
											<xsl:with-param name="rowManufacturerName3" select="SubRowDeliveryDetails/SubRowManufacturerName[3]"/>
											<xsl:with-param name="rowManufacturerIdentifier" select="SubRowDeliveryDetails/SubRowManufacturerIdentifier"/>
											<xsl:with-param name="rowManufacturerCountryCode" select="SubRowDeliveryDetails/SubRowManufacturerCountryCode"/>
											<xsl:with-param name="rowManufacturerCountryName" select="SubRowDeliveryDetails/SubRowManufacturerCountryName"/>
											<xsl:with-param name="rowShortProposedAccountIdentifier" select="SubRowShortProposedAccountIdentifier"/>
											<xsl:with-param name="rowNormalProposedAccountIdentifier" select="SubRowNormalProposedAccountIdentifier"/>
											<xsl:with-param name="rowProposedAccountText" select="SubRowProposedAccountText"/>
											<xsl:with-param name="rowAccountDimensionText" select="SubRowAccountDimensionText"/>
											<xsl:with-param name="rowAveragePriceAmount" select="SubRowAveragePriceAmount"/>
											<xsl:with-param name="articleGroupIdentifier" select="SubArticleGroupIdentifier"/>
											<xsl:with-param name="eanCode" select="SubEanCode"/>
											<xsl:with-param name="rowRegistrationNumberIdentifier" select="SubRowRegistrationNumberIdentifier"/>
											<xsl:with-param name="serialNumberIdentifier" select="SubSerialNumberIdentifier"/>
											<xsl:with-param name="rowActionCode" select="SubRowActionCode"/>
											<xsl:with-param name="rowClearanceIdentifier" select="SubRowDeliveryDetails/SubRowClearanceIdentifier"/>
											<xsl:with-param name="rowDeliveryNoteIdentifier" select="SubRowDeliveryDetails/SubRowDeliveryNoteIdentifier"/>
											<xsl:with-param name="rowPlaceOfDischarge" select="SubRowDeliveryDetails/SubRowPlaceOfDischarge"/>
											<xsl:with-param name="rowFinalDestinationName1" select="SubRowDeliveryDetails/SubRowFinalDestinationName[1]"/>
											<xsl:with-param name="rowFinalDestinationName2" select="SubRowDeliveryDetails/SubRowFinalDestinationName[2]"/>
											<xsl:with-param name="rowFinalDestinationName3" select="SubRowDeliveryDetails/SubRowFinalDestinationName[3]"/>
											<xsl:with-param name="rowDefinitions" select="$subRowDefinitions"/>
											<xsl:with-param name="rowOrdererName" select="SubRowOrdererName"/>
											<xsl:with-param name="rowSalesPersonName" select="SubRowSalesPersonName"/>
											<xsl:with-param name="rowProjectReferenceIdentifier" select="SubRowProjectReferenceIdentifier"/>
											<xsl:with-param name="rowManufacturerArticleIdentifier" select="SubRowDeliveryDetails/SubRowManufacturerArticleIdentifier"/>
											<xsl:with-param name="rowManufacturerOrderIdentifier" select="SubRowDeliveryDetails/SubRowManufacturerOrderIdentifier"/>
											<xsl:with-param name="rowPackageLength" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowPackageLength"/>
											<xsl:with-param name="rowPackageLengthQUC" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowPackageLength/@QuantityUnitCode"/>
											<xsl:with-param name="rowPackageWidth" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowPackageWidth"/>
											<xsl:with-param name="rowPackageWidthQUC" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowPackageWidth/@QuantityUnitCode"/>
											<xsl:with-param name="rowPackageHeight" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowPackageHeight"/>
											<xsl:with-param name="rowPackageHeightQUC" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowPackageHeight/@QuantityUnitCode"/>
											<xsl:with-param name="rowPackageWeight" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowPackageWeight"/>
											<xsl:with-param name="rowPackageWeightQUC" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowPackageWeight/@QuantityUnitCode"/>
											<xsl:with-param name="rowPackageNetWeight" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowPackageNetWeight"/>
											<xsl:with-param name="rowPackageNetWeightQUC" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowPackageNetWeight/@QuantityUnitCode"/>
											<xsl:with-param name="rowPackageVolume" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowPackageVolume"/>
											<xsl:with-param name="rowPackageVolumeQUC" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowPackageVolume/@QuantityUnitCode"/>
											<xsl:with-param name="rowTransportCarriageQuantity" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowTransportCarriageQuantity"/>
											<xsl:with-param name="rowTransportCarriageQuantityQUC" select="SubRowDeliveryDetails/SubRowPackageDetails/SubRowTransportCarriageQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="rowUsedQuantity" select="SubRowUsedQuantity"/>
											<xsl:with-param name="rowUsedQuantityQUC" select="SubRowUsedQuantity/@QuantityUnitCode"/>
											<xsl:with-param name="rowPreviousMeterReadingDate">
												<xsl:call-template name="OutputDate">
													<xsl:with-param name="theDate" select="SubRowPreviousMeterReadingDate"/>
													<xsl:with-param name="theFormat" select="SubRowPreviousMeterReadingDate/@Format"/>
												</xsl:call-template>
											</xsl:with-param>
											<xsl:with-param name="rowLatestMeterReadingDate">
												<xsl:call-template name="OutputDate">
													<xsl:with-param name="theDate" select="SubRowLatestMeterReadingDate"/>
													<xsl:with-param name="theFormat" select="SubRowLatestMeterReadingDate/@Format"/>
												</xsl:call-template>
											</xsl:with-param>
											<xsl:with-param name="rowCalculatedQuantity" select="SubRowCalculatedQuantity"/>
											<xsl:with-param name="rowCalculatedQuantityQUC" select="SubRowCalculatedQuantity/@QuantityUnitCode"/>
										</xsl:call-template>
										<xsl:if test="count(SubRowFreeText) != 0">
											<tr class="subRowPreStyle">
												<td colspan="8">
													<xsl:for-each select="SubRowFreeText">
														<xsl:if test="position() != 1"><br/></xsl:if>
														<xsl:value-of select="."/>
													</xsl:for-each>
												</td>
											</tr>
										</xsl:if>
										<xsl:if test="count(SubRowAnyPartyDetails) != 0">
											<tr class="subRowStyle" valign="top" align="left">
												<td colspan="8">
													<table cellpadding="0" cellspacing="0">
														<tbody>
															<xsl:for-each select="SubRowAnyPartyDetails">
																<xsl:if test="(position() mod 2) != 0">
																	<xsl:variable name="possu" select="position()"/>
																	<xsl:if test="$possu != 1">
																		<tr class="subRowStyle" valign="top" align="left">
																			<td colspan="8"><br/></td>
																		</tr>
																	</xsl:if>
																	<tr class="subRowStyle" valign="top" align="left">
																		<xsl:call-template name="OutputAnyPartyDetails">
																			<xsl:with-param name="styleName" select="'subRowStyle'"/>
																			<xsl:with-param name="info"/>
																			<xsl:with-param name="anyPartyText" select="SubRowAnyPartyText"/>
																			<xsl:with-param name="anyPartyTextAnyPartyCode" select="SubRowAnyPartyText/@AnyPartyCode"/>
																			<xsl:with-param name="anyPartyIdentifier" select="SubRowAnyPartyIdentifier"/>
																			<xsl:with-param name="anyPartyOrganisationName1" select="SubRowAnyPartyOrganisationName[1]"/>
																			<xsl:with-param name="anyPartyOrganisationName2" select="SubRowAnyPartyOrganisationName[2]"/>
																			<xsl:with-param name="anyPartyOrganisationDepartment1" select="SubRowAnyPartyOrganisationDepartment[1]"/>
																			<xsl:with-param name="anyPartyOrganisationDepartment2" select="SubRowAnyPartyOrganisationDepartment[2]"/>
																			<xsl:with-param name="anyPartyOrganisationUnitNumber" select="SubRowAnyPartyOrganisationUnitNumber"/>
																			<xsl:with-param name="anyPartySiteCode" select="SubRowAnyPartySiteCode"/>
																			<xsl:with-param name="anyPartyStreetName1" select="SubRowAnyPartyPostalAddressDetails/SubRowAnyPartyStreetName[1]"/>
																			<xsl:with-param name="anyPartyStreetName2" select="SubRowAnyPartyPostalAddressDetails/SubRowAnyPartyStreetName[2]"/>
																			<xsl:with-param name="anyPartyStreetName3" select="SubRowAnyPartyPostalAddressDetails/SubRowAnyPartyStreetName[3]"/>
																			<xsl:with-param name="anyPartyTownName" select="SubRowAnyPartyPostalAddressDetails/SubRowAnyPartyTownName"/>
																			<xsl:with-param name="anyPartyPostCodeIdentifier" select="SubRowAnyPartyPostalAddressDetails/SubRowAnyPartyPostCodeIdentifier"/>
																			<xsl:with-param name="countryCode" select="SubRowAnyPartyPostalAddressDetails/CountryCode"/>
																			<xsl:with-param name="countryName" select="SubRowAnyPartyPostalAddressDetails/CountryName"/>
																			<xsl:with-param name="anyPartyPostOfficeBoxIdentifier" select="SubRowAnyPartyPostalAddressDetails/SubRowAnyPartyPostOfficeBoxIdentifier"/>
																		</xsl:call-template>
																		<td width="40px"><br/></td>
																		<xsl:call-template name="OutputAnyPartyDetails">
																			<xsl:with-param name="styleName" select="'subRowStyle'"/>
																			<xsl:with-param name="info"/>
																			<xsl:with-param name="anyPartyText" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyText"/>
																			<xsl:with-param name="anyPartyTextAnyPartyCode" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyText/@AnyPartyCode"/>
																			<xsl:with-param name="anyPartyIdentifier" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyIdentifier"/>
																			<xsl:with-param name="anyPartyOrganisationName1" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyOrganisationName[1]"/>
																			<xsl:with-param name="anyPartyOrganisationName2" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyOrganisationName[2]"/>
																			<xsl:with-param name="anyPartyOrganisationDepartment1" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyOrganisationDepartment[1]"/>
																			<xsl:with-param name="anyPartyOrganisationDepartment2" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyOrganisationDepartment[2]"/>
																			<xsl:with-param name="anyPartyOrganisationUnitNumber" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyOrganisationUnitNumber"/>
																			<xsl:with-param name="anyPartySiteCode" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartySiteCode"/>
																			<xsl:with-param name="anyPartyStreetName1" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyPostalAddressDetails/SubRowAnyPartyStreetName[1]"/>
																			<xsl:with-param name="anyPartyStreetName2" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyPostalAddressDetails/SubRowAnyPartyStreetName[2]"/>
																			<xsl:with-param name="anyPartyStreetName3" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyPostalAddressDetails/SubRowAnyPartyStreetName[3]"/>
																			<xsl:with-param name="anyPartyTownName" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyPostalAddressDetails/SubRowAnyPartyTownName"/>
																			<xsl:with-param name="anyPartyPostCodeIdentifier" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyPostalAddressDetails/SubRowAnyPartyPostCodeIdentifier"/>
																			<xsl:with-param name="countryCode" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyPostalAddressDetails/CountryCode"/>
																			<xsl:with-param name="countryName" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyPostalAddressDetails/CountryName"/>
																			<xsl:with-param name="anyPartyPostOfficeBoxIdentifier" select="../SubRowAnyPartyDetails[position() = $possu + 1]/SubRowAnyPartyPostalAddressDetails/SubRowAnyPartyPostOfficeBoxIdentifier"/>
																		</xsl:call-template>
																	</tr>
																</xsl:if>
															</xsl:for-each>
														</tbody>
													</table>
												</td>
											</tr>
										</xsl:if>
									</xsl:for-each>
								</xsl:for-each>
							</xsl:otherwise>
						</xsl:choose>
					</tbody>
				</table>
				<!-- Tuoterivit on nyt tulostettu. -->
				<table width="100%" cellpadding="0" cellspacing="0">
					<tbody>
						<tr align="right">
							<td>
								<table cellpadding="0" cellspacing="0">
									<tbody>
										<tr align="left">
											<td colspan="5"><br/></td>
										</tr>
										<xsl:if test="string-length(InvoiceDetails/InvoiceTotalRoundoffAmount) != 0">
											<tr align="left">
												<td><xsl:value-of select="$txtRounding"/>:</td>
												<td width="5px"/>
												<xsl:call-template name="OutputAmount3Col">
													<xsl:with-param name="amount" select="InvoiceDetails/InvoiceTotalRoundoffAmount"/>
													<xsl:with-param name="currency" select="InvoiceDetails/InvoiceTotalRoundoffAmount/@AmountCurrencyIdentifier"/>
												</xsl:call-template>
											</tr>
										</xsl:if>
										<tr align="left">
											<td><b><xsl:value-of select="$txtINVOICETOTAL"/>:</b></td>
											<td width="5px"/>
											<xsl:call-template name="OutputAmount3Col">
												<xsl:with-param name="amount" select="InvoiceDetails/InvoiceTotalVatIncludedAmount"/>
												<xsl:with-param name="currency" select="InvoiceDetails/InvoiceTotalVatIncludedAmount/@AmountCurrencyIdentifier"/>
												<xsl:with-param name="useBold">1</xsl:with-param>
											</xsl:call-template>
										</tr>
										<xsl:if test="string-length(InvoiceDetails/TargetCurrencyCode) != 0">
											<tr align="left">
												<td><xsl:value-of select="$txtTargetCurrency"/>:</td>
												<td width="5px"/>
												<td colspan="2"></td>
												<td><xsl:value-of select="InvoiceDetails/TargetCurrencyCode"/></td>
											</tr>
										</xsl:if>
										<xsl:if test="string-length(InvoiceDetails/ExchangeRate) != 0">
											<tr align="left">
												<td><xsl:value-of select="$txtExchangeRate"/>:</td>
												<td width="5px"/>
												<xsl:call-template name="OutputAmount3Col">
													<xsl:with-param name="amount" select="InvoiceDetails/ExchangeRate"/>
												</xsl:call-template>
											</tr>
										</xsl:if>
										<xsl:choose>
											<xsl:when test="string-length(InvoiceDetails/OtherCurrencyAmountVatIncludedAmount) != 0">
												<tr align="left">
													<td><xsl:value-of select="$txtOtherCurrency"/>:</td>
													<td width="5px"/>
													<xsl:call-template name="OutputAmount3Col">
														<xsl:with-param name="amount" select="InvoiceDetails/OtherCurrencyAmountVatIncludedAmount"/>
														<xsl:with-param name="currency" select="InvoiceDetails/OtherCurrencyAmountVatIncludedAmount/@AmountCurrencyIdentifier"/>
													</xsl:call-template>
												</tr>
											</xsl:when>
											<xsl:when test="string-length(InvoiceDetails/SourceCurrencyCode) != 0">
												<tr align="left">
													<td><xsl:value-of select="$txtSourceCurrency"/>:</td>
													<td width="5px"/>
													<td colspan="2"></td>
													<td><xsl:value-of select="InvoiceDetails/SourceCurrencyCode"/></td>
												</tr>
											</xsl:when>
										</xsl:choose>
										<xsl:if test="(count(//InvoiceDetails/VatSpecificationDetails) != 0)">
											<tr align="left">
												<td colspan="5"><br/></td>
											</tr>
											<tr align="left">
												<td>
													<b><xsl:value-of select="$txtVatSpecification"/>:</b>
												</td>
												<td width="5px"/>
												<td colspan="3"></td>
											</tr>
											<xsl:if test="string-length(InvoiceDetails/InvoiceTotalVatExcludedAmount) != 0">
												<tr align="left">
													<td>
														<xsl:value-of select="$txtVatExcludedAmount"/>:
													</td>
													<td width="5px"/>
													<td align="right">
														<xsl:value-of select="InvoiceDetails/InvoiceTotalVatExcludedAmount"/>
													</td>
													<td colspan="2"></td>
												</tr>
											</xsl:if>
											<xsl:for-each select="InvoiceDetails/VatSpecificationDetails">
												<xsl:if test="(string-length(VatRatePercent) != 0) or (string-length(VatRateAmount) != 0)">
													<tr align="left">
														<td>
															<xsl:value-of select="$txtVat"/>
															<xsl:text> </xsl:text>
															<xsl:value-of select="VatRatePercent"/>
															<xsl:text> % </xsl:text><xsl:value-of select="VatCode"/>:
														</td>
														<td width="5px"/>
														<td align="right">
															<xsl:value-of select="VatRateAmount"/>
														</td>
														<td width="5px"/>
														<td>(<xsl:value-of select="VatBaseAmount"/>)</td>
													</tr>
												</xsl:if>
												<xsl:for-each select="VatFreeText">
													<xsl:if test="string-length(.) != 0">
														<tr align="left">
															<td colspan="5">
																<xsl:value-of select="."/>
															</td>
														</tr>
													</xsl:if>
												</xsl:for-each>
											</xsl:for-each>
										</xsl:if>
										<xsl:if test="(string-length(InvoiceDetails/ShortProposedAccountIdentifier) != 0) or (string-length(InvoiceDetails/NormalProposedAccountIdentifier) != 0) or (string-length(InvoiceDetails/AccountDimensionText) != 0)  or (string-length(InvoiceDetails/ProposedAccountText) != 0)">
											<tr align="left">
												<td colspan="5"><br/></td>
											</tr>
											<xsl:if test="string-length(InvoiceDetails/ShortProposedAccountIdentifier) != 0">
												<tr align="left">
													<td>
														<xsl:value-of select="$txtShortProposedAccountIdentifier"/>:
													</td>
													<td width="5px"/>
													<td align="right">
														<xsl:value-of select="InvoiceDetails/ShortProposedAccountIdentifier"/>
													</td>
													<td colspan="2"></td>
												</tr>
											</xsl:if>
											<xsl:if test="string-length(InvoiceDetails/NormalProposedAccountIdentifier) != 0">
												<tr align="left">
													<td>
														<xsl:value-of select="$txtNormalProposedAccountIdentifier"/>:
													</td>
													<td width="5px"/>
													<td align="right">
														<xsl:value-of select="InvoiceDetails/NormalProposedAccountIdentifier"/>
													</td>
													<td colspan="2"></td>
												</tr>
											</xsl:if>
											<xsl:if test="string-length(InvoiceDetails/ProposedAccountText) != 0">
												<tr align="left">
													<td colspan="5">
														<xsl:value-of select="$txtProposedAccountIdentifier"/><xsl:text>: </xsl:text><xsl:value-of select="InvoiceDetails/ProposedAccountText"/>
													</td>
												</tr>
											</xsl:if>
											<xsl:if test="string-length(InvoiceDetails/AccountDimensionText) != 0">
												<tr align="left">
													<td colspan="5">
														<xsl:value-of select="$txtAccountDimension"/><xsl:text>: </xsl:text><xsl:value-of select="InvoiceDetails/AccountDimensionText"/>
													</td>
												</tr>
											</xsl:if>
										</xsl:if>
										<tr align="left">
											<td><br/></td>
											<td width="5px"/>
											<td/>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
				<xsl:for-each select="InvoiceUrlText">
					<xsl:call-template name="OutputLinkAndText">
						<xsl:with-param name="pos" select="position()"/>
					</xsl:call-template>
					<br/>
				</xsl:for-each>
				<xsl:call-template name="FormatLink">
					<xsl:with-param name="link" select="EnclosureDetails/EnclosureDescriptionUrlText"/>
					<xsl:with-param name="text" select="EnclosureDetails/EnclosureDescriptionText"/>
				</xsl:call-template>
				<br/>
				<br/>
				<!-- Tulostetaan virtuaaliviivakoodi, jos sellainen laskulta löytyy. -->
				<xsl:if test="string-length(VirtualBankBarcode) != 0">
						<center>
							<b>
								<xsl:value-of select="$txtVirtualBankBarcode"/>:</b>
							<br/>
							<xsl:value-of select="VirtualBankBarcode"/>
						</center>
				</xsl:if>
				<xsl:if test="$InvoiceDetails_DefinitionDetails_paikka = 2">
					<xsl:variable name="definitionDetails">
						<xsl:for-each select="InvoiceDetails/DefinitionDetails">
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitle" select="DefinitionHeaderText"/>
								<xsl:with-param name="txtText" select="DefinitionValue"/>
								<xsl:with-param name="txtText2" select="DefinitionValue/@QuantityUnitCode"/>
							</xsl:call-template>
						</xsl:for-each>
					</xsl:variable>
					<xsl:if test="string-length(normalize-space($definitionDetails)) != 0">
						<table cellpadding="0" cellspacing="0">
							<tbody>
								<xsl:call-template name="OutputTableRow">
									<xsl:with-param name="styleName" select="'invoiceStyle'"/>
									<xsl:with-param name="emptyRow">1</xsl:with-param>
								</xsl:call-template>
								<xsl:copy-of select="$definitionDetails"/>
								<xsl:call-template name="OutputTableRow">
									<xsl:with-param name="styleName" select="'invoiceStyle'"/>
									<xsl:with-param name="emptyRow">1</xsl:with-param>
								</xsl:call-template>
							</tbody>
						</table>
					</xsl:if>
				</xsl:if>
				<xsl:variable name="deliveryInfoTable1">
					<table width="100%" cellpadding="0" cellspacing="0">
						<tbody>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtDeliveryParty"/>
								<xsl:with-param name="txtTextCopy">
									<xsl:for-each select="DeliveryPartyDetails/DeliveryOrganisationName">
										<xsl:value-of select="."/><br/>
									</xsl:for-each>
									<xsl:for-each select="DeliveryPartyDetails/DeliveryOrganisationDepartment">
										<xsl:value-of select="."/><br/>
									</xsl:for-each>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="DeliveryPartyDetails/DeliveryPostalAddressDetails/DeliveryPostofficeBoxIdentifier"/>
									</xsl:call-template>
									<xsl:for-each select="DeliveryPartyDetails/DeliveryPostalAddressDetails/DeliveryStreetName">
										<xsl:call-template name="OutputTextBR">
											<xsl:with-param name="txtText" select="."/>
										</xsl:call-template>
									</xsl:for-each>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText">
											<xsl:call-template name="BuildString">
												<xsl:with-param name="txtText" select="DeliveryPartyDetails/DeliveryPostalAddressDetails/DeliveryPostCodeIdentifier"/>
												<xsl:with-param name="txtText2" select="DeliveryPartyDetails/DeliveryPostalAddressDetails/DeliveryTownName"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="DeliveryContactPersonName"/>
									</xsl:call-template>
									<xsl:for-each select="DeliveryContactPersonFunction">
										<xsl:call-template name="OutputTextBR">
											<xsl:with-param name="txtText" select="."/>
										</xsl:call-template>
									</xsl:for-each>
									<xsl:for-each select="DeliveryContactPersonDepartment">
										<xsl:call-template name="OutputTextBR">
											<xsl:with-param name="txtText" select="."/>
										</xsl:call-template>
									</xsl:for-each>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="DeliveryCommunicationDetails/DeliveryEmailaddressIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="DeliveryCommunicationDetails/DeliveryPhoneNumberIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="DeliveryPartyDetails/DeliveryPartyIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText">
											<xsl:call-template name="BuildString">
												<xsl:with-param name="txtText" select="DeliveryOrganisationUnitNumber"/>
												<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
												<xsl:with-param name="txtText2" select="DeliverySiteCode"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtDeliveryDate"/>
								<xsl:with-param name="txtText">
									<xsl:call-template name="OutputDate">
										<xsl:with-param name="theDate" select="DeliveryDetails/DeliveryDate"/>
										<xsl:with-param name="theFormat" select="DeliveryDetails/DeliveryDate/@Format"/>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtDeliveryPeriodDetails"/>
								<xsl:with-param name="txtText">
									<xsl:call-template name="OutputDate">
										<xsl:with-param name="theDate" select="DeliveryDetails/DeliveryPeriodDetails/StartDate"/>
										<xsl:with-param name="theFormat" select="DeliveryDetails/DeliveryPeriodDetails/StartDate/@Format"/>
									</xsl:call-template>
								</xsl:with-param>
								<xsl:with-param name="txtTextDelimiter">-</xsl:with-param>
								<xsl:with-param name="txtText2">
									<xsl:call-template name="OutputDate">
										<xsl:with-param name="theDate" select="DeliveryDetails/DeliveryPeriodDetails/EndDate"/>
										<xsl:with-param name="theFormat" select="DeliveryDetails/DeliveryPeriodDetails/EndDate/@Format"/>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtDeliveryMethod"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/DeliveryMethodText"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtDeliveryTerms"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/DeliveryTermsText"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtTerminalAddress"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/TerminalAddressText"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtWaybillIdentifier"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/WaybillIdentifier"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtClearanceId"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/ClearanceIdentifier"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtDeliveryNoteId"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/DeliveryNoteIdentifier"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtPlaceOfDischarge"/>
								<xsl:with-param name="txtTextCopy">
									<xsl:for-each select="DeliveryDetails/PlaceOfDischarge">
										<xsl:call-template name="OutputTextBR">
											<xsl:with-param name="txtText" select="."/>
										</xsl:call-template>
									</xsl:for-each>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtFinalDestination"/>
								<xsl:with-param name="txtTextCopy">
									<xsl:for-each select="DeliveryDetails/FinalDestinationName">
										<xsl:call-template name="OutputTextBR">
											<xsl:with-param name="txtText" select="."/>
										</xsl:call-template>
									</xsl:for-each>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="emptyRow">1</xsl:with-param>
							</xsl:call-template>
						</tbody>
					</table>
				</xsl:variable>
				<xsl:variable name="deliveryInfoTable2">
					<table width="100%" cellpadding="0" cellspacing="0">
						<tbody>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtDeliverer"/>
								<xsl:with-param name="txtTextCopy">
									<xsl:for-each select="DeliveryDetails/DelivererName">
										<xsl:value-of select="."/><br/>
									</xsl:for-each>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="DeliveryDetails/DelivererIdentifier"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText">
											<xsl:call-template name="BuildString">
												<xsl:with-param name="txtText" select="DeliveryDetails/DelivererCountryCode"/>
												<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
												<xsl:with-param name="txtText2" select="DeliveryDetails/DelivererCountryName"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="emptyRow">1</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtShipmentOrg"/>
								<xsl:with-param name="txtTextCopy">
									<xsl:for-each select="DeliveryDetails/ShipmentPartyDetails/ShipmentOrganisationName">
										<xsl:value-of select="."/><br/>
									</xsl:for-each>
									<xsl:for-each select="DeliveryDetails/ShipmentPartyDetails/ShipmentOrganisationDepartment">
										<xsl:value-of select="."/><br/>
									</xsl:for-each>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="DeliveryDetails/ShipmentPartyDetails/ShipmentPostalAddressDetails/ShipmentStreetName"/>
									</xsl:call-template>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText">
											<xsl:call-template name="BuildString">
												<xsl:with-param name="txtText" select="DeliveryDetails/ShipmentPartyDetails/ShipmentPostalAddressDetails/ShipmentPostCodeIdentifier"/>
												<xsl:with-param name="txtText2" select="DeliveryDetails/ShipmentPartyDetails/ShipmentPostalAddressDetails/ShipmentTownName"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText" select="DeliveryDetails/ShipmentPartyDetails/ShipmentPartyIdentifier"/>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="emptyRow">1</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtManufacturer"/>
								<xsl:with-param name="txtTextCopy">
									<xsl:for-each select="DeliveryDetails/ManufacturerName">
										<xsl:value-of select="."/><br/>
									</xsl:for-each>
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtText">
											<xsl:call-template name="BuildString">
												<xsl:with-param name="txtText" select="DeliveryDetails/ManufacturerCountryCode"/>
												<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
												<xsl:with-param name="txtText2" select="DeliveryDetails/ManufacturerCountryName"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtManufacturerOrderId"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/ManufacturerOrderIdentifier"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtPackageLength"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/PackageDetails/PackageLength"/>
								<xsl:with-param name="txtText2" select="DeliveryDetails/PackageDetails/PackageLength/@QuantityUnitCode"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtPackageWidth"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/PackageDetails/PackageWidth"/>
								<xsl:with-param name="txtText2" select="DeliveryDetails/PackageDetails/PackageWidth/@QuantityUnitCode"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtPackageHeight"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/PackageDetails/PackageHeight"/>
								<xsl:with-param name="txtText2" select="DeliveryDetails/PackageDetails/PackageHeight/@QuantityUnitCode"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtPackageWeight"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/PackageDetails/PackageWeight"/>
								<xsl:with-param name="txtText2" select="DeliveryDetails/PackageDetails/PackageWeight/@QuantityUnitCode"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtPackageNetWeight"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/PackageDetails/PackageNetWeight"/>
								<xsl:with-param name="txtText2" select="DeliveryDetails/PackageDetails/PackageNetWeight/@QuantityUnitCode"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtPackageVolume"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/PackageDetails/PackageVolume"/>
								<xsl:with-param name="txtText2" select="DeliveryDetails/PackageDetails/PackageVolume/@QuantityUnitCode"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
								<xsl:with-param name="txtTitleNormal" select="$txtTransportCarriageQuantity"/>
								<xsl:with-param name="txtText" select="DeliveryDetails/PackageDetails/TransportCarriageQuantity"/>
								<xsl:with-param name="txtText2" select="DeliveryDetails/PackageDetails/TransportCarriageQuantity/@QuantityUnitCode"/>
							</xsl:call-template>
							<xsl:call-template name="OutputTableRow">
								<xsl:with-param name="styleName" select="'invoiceStyle'"/>
								<xsl:with-param name="emptyRow">1</xsl:with-param>
							</xsl:call-template>
						</tbody>
					</table>
				</xsl:variable>
				<xsl:if test="string-length(normalize-space($deliveryInfoTable1)) + string-length(normalize-space($deliveryInfoTable2)) != 0">
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<tr class="invoiceStyle" valign="top" align="left">
								<td colspan="3">
									<b><xsl:value-of select="$txtDeliveryInfo"/></b>
								</td>
							</tr>
							<tr class="invoiceStyle" valign="top" align="left">
								<td><xsl:copy-of select="$deliveryInfoTable1"/></td>
								<td width="40px"><br/></td>
								<td><xsl:copy-of select="$deliveryInfoTable2"/></td>
							</tr>
						</tbody>
					</table>
				</xsl:if>
				<xsl:if test="count(SpecificationDetails/SpecificationFreeText) != 0">
					<pre class="preStyle">
						<xsl:for-each select="SpecificationDetails/SpecificationFreeText">
							<xsl:value-of select="."/>
							<br/>
						</xsl:for-each>
					</pre>
				</xsl:if>
				<xsl:if test="count(AnyPartyDetails) != 0">
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<xsl:for-each select="AnyPartyDetails">
								<xsl:if test="(position() mod 2) != 0">
									<xsl:variable name="possu" select="position()"/>
									<tr class="invoiceRowStyle" valign="top" align="left">
										<td colspan="8"><br/></td>
									</tr>
									<tr class="invoiceStyle" valign="top" align="left">
										<xsl:call-template name="OutputAnyPartyDetails">
											<xsl:with-param name="styleName" select="'invoiceStyle'"/>
											<xsl:with-param name="info"/>
											<xsl:with-param name="anyPartyText" select="AnyPartyText"/>
											<xsl:with-param name="anyPartyTextAnyPartyCode" select="AnyPartyText/@AnyPartyCode"/>
											<xsl:with-param name="anyPartyIdentifier" select="AnyPartyIdentifier"/>
											<xsl:with-param name="anyPartyOrganisationName1" select="AnyPartyOrganisationName[1]"/>
											<xsl:with-param name="anyPartyOrganisationName2" select="AnyPartyOrganisationName[2]"/>
											<xsl:with-param name="anyPartyOrganisationDepartment1" select="AnyPartyOrganisationDepartment[1]"/>
											<xsl:with-param name="anyPartyOrganisationDepartment2" select="AnyPartyOrganisationDepartment[2]"/>
											<xsl:with-param name="anyPartyOrganisationUnitNumber" select="AnyPartyOrganisationUnitNumber"/>
											<xsl:with-param name="anyPartySiteCode" select="AnyPartySiteCode"/>
											<xsl:with-param name="anyPartyStreetName1" select="AnyPartyPostalAddressDetails/AnyPartyStreetName[1]"/>
											<xsl:with-param name="anyPartyStreetName2" select="AnyPartyPostalAddressDetails/AnyPartyStreetName[2]"/>
											<xsl:with-param name="anyPartyStreetName3" select="AnyPartyPostalAddressDetails/AnyPartyStreetName[3]"/>
											<xsl:with-param name="anyPartyTownName" select="AnyPartyPostalAddressDetails/AnyPartyTownName"/>
											<xsl:with-param name="anyPartyPostCodeIdentifier" select="AnyPartyPostalAddressDetails/AnyPartyPostCodeIdentifier"/>
											<xsl:with-param name="countryCode" select="AnyPartyPostalAddressDetails/CountryCode"/>
											<xsl:with-param name="countryName" select="AnyPartyPostalAddressDetails/CountryName"/>
											<xsl:with-param name="anyPartyPostOfficeBoxIdentifier" select="AnyPartyPostalAddressDetails/AnyPartyPostOfficeBoxIdentifier"/>
											<xsl:with-param name="anyPartyContactPersonName" select="AnyPartyContactPersonName"/>
											<xsl:with-param name="anyPartyContactPersonFunction1" select="AnyPartyContactPersonFunction[1]"/>
											<xsl:with-param name="anyPartyContactPersonFunction2" select="AnyPartyContactPersonFunction[2]"/>
											<xsl:with-param name="anyPartyContactPersonDepartment1" select="AnyPartyContactPersonDepartment[1]"/>
											<xsl:with-param name="anyPartyContactPersonDepartment2" select="AnyPartyContactPersonDepartment[2]"/>
											<xsl:with-param name="anyPartyEmailaddressIdentifier" select="AnyPartyCommunicationDetails/AnyPartyEmailaddressIdentifier"/>
											<xsl:with-param name="anyPartyPhoneNumberIdentifier" select="AnyPartyCommunicationDetails/AnyPartyPhoneNumberIdentifier"/>
										</xsl:call-template>
										<td width="40px"><br/></td>
										<xsl:call-template name="OutputAnyPartyDetails">
											<xsl:with-param name="styleName" select="'invoiceStyle'"/>
											<xsl:with-param name="info"/>
											<xsl:with-param name="anyPartyText" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyText"/>
											<xsl:with-param name="anyPartyTextAnyPartyCode" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyText/@AnyPartyCode"/>
											<xsl:with-param name="anyPartyIdentifier" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyIdentifier"/>
											<xsl:with-param name="anyPartyOrganisationName1" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyOrganisationName[1]"/>
											<xsl:with-param name="anyPartyOrganisationName2" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyOrganisationName[2]"/>
											<xsl:with-param name="anyPartyOrganisationDepartment1" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyOrganisationDepartment[1]"/>
											<xsl:with-param name="anyPartyOrganisationDepartment2" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyOrganisationDepartment[2]"/>
											<xsl:with-param name="anyPartyOrganisationUnitNumber" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyOrganisationUnitNumber"/>
											<xsl:with-param name="anyPartySiteCode" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartySiteCode"/>
											<xsl:with-param name="anyPartyStreetName1" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyPostalAddressDetails/AnyPartyStreetName[1]"/>
											<xsl:with-param name="anyPartyStreetName2" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyPostalAddressDetails/AnyPartyStreetName[2]"/>
											<xsl:with-param name="anyPartyStreetName3" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyPostalAddressDetails/AnyPartyStreetName[3]"/>
											<xsl:with-param name="anyPartyTownName" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyPostalAddressDetails/AnyPartyTownName"/>
											<xsl:with-param name="anyPartyPostCodeIdentifier" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyPostalAddressDetails/AnyPartyPostCodeIdentifier"/>
											<xsl:with-param name="countryCode" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyPostalAddressDetails/CountryCode"/>
											<xsl:with-param name="countryName" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyPostalAddressDetails/CountryName"/>
											<xsl:with-param name="anyPartyPostOfficeBoxIdentifier" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyPostalAddressDetails/AnyPartyPostOfficeBoxIdentifier"/>
											<xsl:with-param name="anyPartyContactPersonName" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyContactPersonName"/>
											<xsl:with-param name="anyPartyContactPersonFunction1" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyContactPersonFunction[1]"/>
											<xsl:with-param name="anyPartyContactPersonFunction2" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyContactPersonFunction[2]"/>
											<xsl:with-param name="anyPartyContactPersonDepartment1" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyContactPersonDepartment[1]"/>
											<xsl:with-param name="anyPartyContactPersonDepartment2" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyContactPersonDepartment[2]"/>
											<xsl:with-param name="anyPartyEmailaddressIdentifier" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyCommunicationDetails/AnyPartyEmailaddressIdentifier"/>
											<xsl:with-param name="anyPartyPhoneNumberIdentifier" select="../AnyPartyDetails[position() = $possu + 1]/AnyPartyCommunicationDetails/AnyPartyPhoneNumberIdentifier"/>
										</xsl:call-template>
									</tr>
								</xsl:if>
							</xsl:for-each>
						</tbody>
					</table>
				</xsl:if>
				<!-- Sitten tulostetaan mahdolliset osamaksutiedot. -->
				<xsl:for-each select="PartialPaymentDetails">
					<table width="100%" cellpadding="0" cellspacing="0">
						<tbody>
							<xsl:if test="position() = 1">
								<tr class="invoiceRowStyle">
									<td colspan="8"><br/></td>
								</tr>
							</xsl:if>
							<xsl:if test="string-length(PaidAmount)+string-length(UnPaidAmount)+string-length(InterestPercent)+string-length(ProsessingCostsAmount) != 0">
								<tr class="invoiceStyle" align="left">
									<td>
										<b><xsl:value-of select="$txtPartialPaymentDetails"/>:</b>
									</td>
								</tr>
								<tr class="invoiceStyle" align="left">
									<td>
										<xsl:call-template name="BuildString">
											<xsl:with-param name="txtTitle" select="$txtPaidAmount"/>
											<xsl:with-param name="txtText" select="PaidAmount"/>
										</xsl:call-template>
									</td>
									<td align="right">
										<xsl:call-template name="BuildString">
											<xsl:with-param name="txtTitle" select="$txtUnPaidAmount"/>
											<xsl:with-param name="txtText" select="UnPaidAmount"/>
										</xsl:call-template>
									</td>
									<td align="right">
										<xsl:call-template name="BuildString">
											<xsl:with-param name="txtTitle" select="$txtInterestPercent"/>
											<xsl:with-param name="txtText" select="InterestPercent"/>
										</xsl:call-template>
									</td>
									<td align="right">
										<xsl:call-template name="BuildString">
											<xsl:with-param name="txtTitle" select="$txtProsessingCostsAmount"/>
											<xsl:with-param name="txtText" select="ProsessingCostsAmount"/>
										</xsl:call-template>
									</td>
									<td width="30%">
										<br/>
									</td>
								</tr>
							</xsl:if>
							<tr class="invoiceStyle" align="left">
								<td>
									<b><xsl:value-of select="$txtPartialPaymentDueDate"/></b>
								</td>
								<td align="right">
									<b><xsl:value-of select="$txtVatIncludedAmount"/></b>
								</td>
								<td align="right">
									<b><xsl:value-of select="$txtVatExcludedAmount"/></b>
								</td>
								<td align="right">
									<b><xsl:value-of select="$txtPartialPaymentReferenceIdentifier"/></b>
								</td>
								<td width="30%">
									<br/>
								</td>
							</tr>
							<tr class="invoiceStyle" align="left">
								<td>
									<xsl:for-each select="PartialPaymentDueDate">
										<xsl:call-template name="OutputDate">
											<xsl:with-param name="theDate" select="."/>
											<xsl:with-param name="theFormat" select="@Format"/>
										</xsl:call-template>
										<br/>
									</xsl:for-each>
								</td>
								<td align="right">
									<xsl:for-each select="PartialPaymentVatIncludedAmount">
										<xsl:value-of select="."/>
										<br/>
									</xsl:for-each>
								</td>
								<td align="right">
									<xsl:for-each select="PartialPaymentVatExcludedAmount">
										<xsl:value-of select="."/>
										<br/>
									</xsl:for-each>
								</td>
								<td align="right">
									<xsl:for-each select="PartialPaymentReferenceIdentifier">
										<xsl:value-of select="."/>
										<br/>
									</xsl:for-each>
								</td>
								<td width="30%">
									<br/>
								</td>
							</tr>
						</tbody>
					</table>
				</xsl:for-each>
				<!-- Loppuun tulostetaan myyjän yhteys- ja muita tietoja. -->
				<table width="100%" cellpadding="0" cellspacing="0">
					<tbody>
						<tr class="sellerDetailsStyle">
							<td colspan="3">
								<hr/>
							</td>
						</tr>
						<tr class="sellerDetailsStyle" align="left" valign="top">
							<td>
								<xsl:value-of select="SellerPartyDetails/SellerOrganisationName"/>
								<br/>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="SellerInformationDetails/SellerOfficialPostalAddressDetails/SellerOfficialStreetName"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText">
										<xsl:call-template name="BuildString">
											<xsl:with-param name="txtText" select="SellerInformationDetails/SellerOfficialPostalAddressDetails/SellerOfficialPostCodeIdentifier"/>
											<xsl:with-param name="txtText2" select="SellerInformationDetails/SellerOfficialPostalAddressDetails/SellerOfficialTownName"/>
										</xsl:call-template>
									</xsl:with-param>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="SellerInformationDetails/SellerOfficialPostalAddressDetails/CountryName"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="SellerInformationDetails/SellerFreeText"/>
								</xsl:call-template>
							</td>
							<td>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtTitle" select="$txtPhoneNumber"/>
									<xsl:with-param name="txtText" select="SellerInformationDetails/SellerPhoneNumber"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtTitle" select="$txtFaxNumber"/>
									<xsl:with-param name="txtText" select="SellerInformationDetails/SellerFaxNumber"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtTitle" select="$txtWebaddressIdentifier"/>
									<xsl:with-param name="txtTextCopy">
										<xsl:call-template name="BuildString">
												<xsl:with-param name="txtText" select="SellerInformationDetails/SellerWebaddressIdentifier"/>
												<xsl:with-param name="txtTextUrl" select="SellerInformationDetails/SellerWebaddressIdentifier"/>
										</xsl:call-template>
									</xsl:with-param>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtTitle" select="$txtEmailaddressIdentifier"/>
									<xsl:with-param name="txtTextCopy">
										<xsl:call-template name="FormatEmail">
											<xsl:with-param name="email" select="SellerInformationDetails/SellerCommonEmailaddressIdentifier"/>
										</xsl:call-template>
									</xsl:with-param>
								</xsl:call-template>
								<xsl:for-each select="SellerInformationDetails/InvoiceRecipientDetails">
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtTitle" select="$txtInvoiceRecipientAddress"/>
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="BuildString">
													<xsl:with-param name="txtText" select="InvoiceRecipientAddress"/>
													<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
													<xsl:with-param name="txtText2" select="InvoiceRecipientIntermediatorAddress"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
								</xsl:for-each>
							</td>
							<td>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtTitle" select="$txtHomeTownName"/>
									<xsl:with-param name="txtText" select="SellerInformationDetails/SellerHomeTownName"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtTitle" select="$txtTaxCode"/>
									<xsl:with-param name="txtTextCopy">
										<xsl:call-template name="BuildString">
												<xsl:with-param name="txtText" select="SellerPartyDetails/SellerOrganisationTaxCode"/>
												<xsl:with-param name="txtTextUrl" select="SellerPartyDetails/SellerOrganisationTaxCodeUrlText"/>
										</xsl:call-template>
									</xsl:with-param>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtTextCopy">
										<xsl:call-template name="BuildString">
												<xsl:with-param name="txtText" select="SellerInformationDetails/SellerVatRegistrationText"/>
												<xsl:with-param name="txtText2">
													<xsl:call-template name="OutputDate">
														<xsl:with-param name="theDate" select="SellerInformationDetails/SellerVatRegistrationDate"/>
														<xsl:with-param name="theFormat" select="SellerInformationDetails/SellerVatRegistrationDate/@Format"/>
													</xsl:call-template>
												</xsl:with-param>
										</xsl:call-template>
									</xsl:with-param>
								</xsl:call-template>
								<xsl:for-each select="SellerInformationDetails/SellerAccountDetails">
									<xsl:call-template name="OutputTextBR">
										<xsl:with-param name="txtTextCopy">
											<xsl:call-template name="BuildString">
													<xsl:with-param name="txtText">
														<xsl:call-template name="OutputEpiAccountID">
															<xsl:with-param name="scheme" select="SellerAccountID/@IdentificationSchemeName"/>
															<xsl:with-param name="account" select="SellerAccountID"/>
														</xsl:call-template>
													</xsl:with-param>
													<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
													<xsl:with-param name="txtText2" select="SellerBic"/>
											</xsl:call-template>
										</xsl:with-param>
									</xsl:call-template>
								</xsl:for-each>
							</td>
						</tr>
					</tbody>
				</table>
			</body>
		</html>
	</xsl:template>
	<!-- Template, joka tulostaa yhteen kuuluvat linkin ja tekstin. -->
	<xsl:template name="OutputLinkAndText">
		<xsl:param name="pos"/>
		<xsl:call-template name="FormatLink">
			<xsl:with-param name="link" select="/Finvoice/InvoiceUrlText[position()=$pos]"/>
			<xsl:with-param name="text" select="/Finvoice/InvoiceUrlNameText[position()=$pos]"/>
		</xsl:call-template>
	</xsl:template>
	<!-- Template, joka tulostaa yhden tai kahden päiväyksen aikajakson. -->
	<xsl:template name="OutputDatePeriod">
		<xsl:param name="theDate"/>
		<xsl:param name="theDateFormat"/>
		<xsl:param name="theStartDate"/>
		<xsl:param name="theStartDateFormat"/>
		<xsl:param name="theEndDate"/>
		<xsl:param name="theEndDateFormat"/>
		<xsl:choose>
			<xsl:when test="string-length($theDate) != 0">
				<xsl:call-template name="OutputDate">
					<xsl:with-param name="theDate" select="$theDate"/>
					<xsl:with-param name="theFormat" select="$theDateFormat"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="OutputDate">
					<xsl:with-param name="theDate" select="$theStartDate"/>
					<xsl:with-param name="theFormat" select="$theStartDateFormat"/>
				</xsl:call-template>
				<xsl:if test="string-length($theEndDate) != 0">
					-
					<xsl:call-template name="OutputDate">
						<xsl:with-param name="theDate" select="$theEndDate"/>
						<xsl:with-param name="theFormat" select="$theEndDateFormat"/>
					</xsl:call-template>
				</xsl:if>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<!-- Template, joka osaa tulostaa päivämääriä. -->
	<xsl:template name="OutputDate">
		<xsl:param name="theDate"/>
		<xsl:param name="theFormat"/>
		<xsl:if test="string-length($theDate) != 0">
			<xsl:choose>
				<xsl:when test="substring($theDate,7,1)='0'">
					<xsl:value-of select="substring($theDate,8,1)"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="substring($theDate,7,2)"/>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:text>.</xsl:text>
			<xsl:choose>
				<xsl:when test="substring($theDate,5,1)='0'">
					<xsl:value-of select="substring($theDate,6,1)"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="substring($theDate,5,2)"/>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:text>.</xsl:text>
			<xsl:value-of select="substring($theDate,1,4)"/>
			<xsl:if test="$theFormat = 'CCYYMMDDHHMMSS'">
				<xsl:text> </xsl:text>
				<xsl:value-of select="substring($theDate,9,2)"/>
				<xsl:text>:</xsl:text>
				<xsl:value-of select="substring($theDate,11,2)"/>
				<!--<xsl:if test="substring($theDate,13,2)!='00'"> -->
				<xsl:text>:</xsl:text>
				<xsl:value-of select="substring($theDate,13,2)"/>
				<!--</xsl:if>-->
			</xsl:if>
		</xsl:if>
	</xsl:template>
	<!-- Template, joka osaa tulostaa tilinumeroita (IBAN ryhmiteltynä ja muut sellaisenaan). -->
	<xsl:template name="OutputEpiAccountID">
		<xsl:param name="scheme"/>
		<xsl:param name="account"/>
		<xsl:variable name="lenAccount" select="string-length($account)"/>
		<xsl:choose>
			<xsl:when test="$scheme = 'IBAN'">
				<xsl:choose>
					<xsl:when test="$lenAccount &lt; 5">
						<xsl:value-of select="$account"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="substring($account, 1, 4)"/>
						<xsl:text> </xsl:text>
						<xsl:choose>
							<xsl:when test="$lenAccount &lt; 9">
								<xsl:value-of select="substring($account, 5, $lenAccount - 4)"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="substring($account, 5, 4)"/>
								<xsl:text> </xsl:text>
								<xsl:choose>
									<xsl:when test="$lenAccount &lt; 13">
										<xsl:value-of select="substring($account, 9, $lenAccount - 8)"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="substring($account, 9, 4)"/>
										<xsl:text> </xsl:text>
										<xsl:choose>
											<xsl:when test="$lenAccount &lt; 17">
												<xsl:value-of select="substring($account, 13, $lenAccount - 12)"/>
											</xsl:when>
											<xsl:otherwise>
												<xsl:value-of select="substring($account, 13, 4)"/>
												<xsl:text> </xsl:text>
												<xsl:choose>
													<xsl:when test="$lenAccount &lt; 21">
														<xsl:value-of select="substring($account, 17, $lenAccount - 16)"/>
													</xsl:when>
													<xsl:otherwise>
														<xsl:value-of select="substring($account, 17, 4)"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="substring($account, 21, $lenAccount - 20)"/>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:otherwise>
										</xsl:choose>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:when test="($scheme = 'BBAN') and ($lenAccount = 14) and (string-length(translate($account, '0123456789', '')) = 0)">
				<xsl:value-of select="substring($account, 1, 6)"/>-<xsl:value-of select="substring($account, 7, 8)"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$account"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<!-- Template, joka osaa tulostaa rahasumman rahayksikön kera. -->
	<xsl:template name="OutputAmount">
		<xsl:param name="amount"/>
		<xsl:param name="currency"/>
		<xsl:if test="string-length($amount) != 0">
			<xsl:value-of select="$amount"/>
			<xsl:if test="string-length($currency) != 0">
				<xsl:text> </xsl:text>
				<xsl:choose>
					<xsl:when test="$currency = 'EUR'">
						<xsl:value-of select="$txtEur"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$currency"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
		</xsl:if>
	</xsl:template>
	<!-- Template, joka osaa tulostaa rahasumman rahayksikön kera kolmeen sarakkeeseen. -->
	<xsl:template name="OutputAmount3Col">
		<xsl:param name="amount"/>
		<xsl:param name="currency"/>
		<xsl:param name="useBold"/>
		<td align="right">
			<xsl:choose>
				<xsl:when test="$useBold = 1">
					<xsl:element name="b">
						<xsl:value-of select="$amount"/>
					</xsl:element>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$amount"/>
				</xsl:otherwise>
			</xsl:choose>
		</td>
		<td width="3px"/>
		<td align="left">
			<xsl:choose>
				<xsl:when test="$useBold = 1">
					<xsl:element name="b">
						<xsl:choose>
							<xsl:when test="$currency = 'EUR'">
								<xsl:value-of select="$txtEur"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="$currency"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:element>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="$currency = 'EUR'">
							<xsl:value-of select="$txtEur"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="$currency"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</td>
	</xsl:template>
	<!-- Template, joka osaa muokata sähköpostiosoitetta -->
	<xsl:template name="FormatEmail">
		<xsl:param name="email"/>
		<xsl:variable name="email_lc" select="translate($email,'MAILTO','mailto')"/>
		<xsl:choose>
			<xsl:when test="(starts-with($email_lc,'mailto:')=true())">
				<xsl:call-template name="OutputEmail">
					<xsl:with-param name="email_a" select="$email"/>
					<xsl:with-param name="email_d" select="substring-after($email,':')"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="OutputEmail">
					<xsl:with-param name="email_a" select="concat('mailto:',$email)"/>
					<xsl:with-param name="email_d" select="$email"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<!-- Template, joka osaa tulostaa sähköpostiosoitteen linkkinä. -->
	<xsl:template name="OutputEmail">
		<xsl:param name="email_a"/>
		<xsl:param name="email_d"/>
		<xsl:if test="string-length(normalize-space($email_d)) != 0">
			<xsl:element name="a">
				<xsl:attribute name="href"><xsl:value-of select="$email_a"/></xsl:attribute>
				<xsl:value-of select="$email_d"/>
			</xsl:element>
		</xsl:if>
	</xsl:template>
	<!-- Template, joka osaa muokata webbiosoitetta -->
	<xsl:template name="FormatLink">
		<xsl:param name="link"/>
		<xsl:param name="text"/>
		<xsl:variable name="link_lc" select="translate($link,'HTTPS','https')"/>
		<xsl:choose>
			<xsl:when test="string-length(normalize-space($link)) = 0">
				<xsl:value-of select="$text"/>
			</xsl:when>
			<xsl:when test="(starts-with($link_lc,'http:')=true() or starts-with($link_lc,'https:')=true())">
				<xsl:call-template name="OutputLink">
					<xsl:with-param name="link_a" select="$link"/>
					<xsl:with-param name="text" select="$text"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="OutputLink">
					<xsl:with-param name="link_a" select="concat('http://',$link)"/>
					<xsl:with-param name="text" select="$text"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<!-- Template, joka osaa tulostaa webbisivun linkkinä. -->
	<xsl:template name="OutputLink">
		<xsl:param name="link_a"/>
		<xsl:param name="text"/>
		<xsl:element name="a">
			<xsl:attribute name="href"><xsl:value-of select="$link_a"/></xsl:attribute>
			<xsl:attribute name="target"><xsl:text>_blank</xsl:text></xsl:attribute>
			<xsl:choose>
				<xsl:when test="string-length($text) != 0">
					<xsl:value-of select="$text"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$txtLink"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:element>
	</xsl:template>
	<!-- Template, joka osaa tulostaa tekstin neljän merkin ryhminä. -->
	<xsl:template name="OutputGrouped4">
		<xsl:param name="theText"/>
		<xsl:param name="isFirst"/>
		<xsl:variable name="len" select="string-length($theText)"/>
		<xsl:choose>
			<xsl:when test="$len &lt; 5">
				<xsl:value-of select="$theText"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="substring($theText, 1, 4)"/><xsl:text> </xsl:text>
				<xsl:call-template name="OutputGrouped4">
					<xsl:with-param name="theText" select="substring($theText, 5)"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<!-- Template, joka osaa tulostaa blankottoman ja etunollattoman viitenumeron ryhmiteltynä. -->
	<xsl:template name="OutputEpiRemittanceInfoIdentifierGrouped">
		<xsl:param name="erii"/>
		<xsl:param name="isFirst"/>
		<xsl:variable name="len" select="string-length($erii)"/>
		<xsl:choose>
			<xsl:when test="($len &lt; 5) or (($len = 5) and (isFirst != 0))">
				<xsl:value-of select="$erii"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="substring($erii, 1, 1)"/>
				<xsl:if test="(($len - 1) mod 5) = 0">
					<xsl:text> </xsl:text>
				</xsl:if>
				<xsl:call-template name="OutputEpiRemittanceInfoIdentifierGrouped">
					<xsl:with-param name="erii" select="substring($erii, 2)"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<!-- Template, joka osaa tulostaa blankottoman viitenumeron (etunollilla tai ilman). -->
	<xsl:template name="OutputSpacelessEpiRemittanceInfoIdentifier">
		<xsl:param name="erii"/>
		<xsl:choose>
			<xsl:when test="starts-with($erii,'0')=true()">
				<xsl:call-template name="OutputEpiRemittanceInfoIdentifier">
					<xsl:with-param name="erii" select="substring($erii, 2)"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="starts-with($erii,'RF')=true()">
				<xsl:call-template name="OutputGrouped4">
					<xsl:with-param name="theText" select="$erii"/>
					<xsl:with-param name="isFirst">1</xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="OutputEpiRemittanceInfoIdentifierGrouped">
					<xsl:with-param name="erii" select="$erii"/>
					<xsl:with-param name="isFirst">1</xsl:with-param>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<!-- Template, joka osaa tulostaa viitenumeron (etunollilla tai ilman). -->
	<xsl:template name="OutputEpiRemittanceInfoIdentifier">
		<xsl:param name="erii"/>
		<xsl:call-template name="OutputSpacelessEpiRemittanceInfoIdentifier">
			<xsl:with-param name="erii" select="translate($erii, ' ', '')"/>
		</xsl:call-template>
	</xsl:template>
	<!-- Template, joka tulostaa otsikkotiedon. -->
	<xsl:template name="OutputTitle">
		<xsl:param name="invoiceTypeText"/>
		<xsl:param name="originCode"/>
		<xsl:param name="originText"/>
		<xsl:choose>
			<xsl:when test="($invoiceTypeText = 'LASKU') or
						       ($invoiceTypeText = 'Lasku') or
						       ($invoiceTypeText = 'lasku') or
						       ($invoiceTypeText = 'FAKTURA') or
						       ($invoiceTypeText = 'Faktura') or
						       ($invoiceTypeText = 'faktura') or
								 ($invoiceTypeText = 'INVOICE') or
								 ($invoiceTypeText = 'Invoice') or
								 ($invoiceTypeText = 'invoice')
								">
				<xsl:value-of select="$txtINVOICE"/>
				<xsl:choose>
					<xsl:when test="($originCode = 'Copy') and
										(($originText = 'KOPIO') or
										 ($originText = 'Kopio') or
										 ($originText = 'kopio') or
										 ($originText = 'KOPIA') or
										 ($originText = 'Kopia') or
										 ($originText = 'kopia') or
										 ($originText = 'COPY')  or
										 ($originText = 'Copy')  or
										 ($originText = 'copy')
										)">
						(<xsl:value-of select="$txtCOPY"/>)
					</xsl:when>
					<xsl:otherwise>
						<xsl:if test="($originCode = 'Copy') and (string-length($originText) != 0)">
							(<xsl:value-of select="$originText"/>)
						</xsl:if>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:when test="($invoiceTypeText = 'HYVITYSLASKU') or
						       ($invoiceTypeText = 'Hyvityslasku') or
						       ($invoiceTypeText = 'hyvityslasku') or
						       ($invoiceTypeText = 'KREDITERING') or
						       ($invoiceTypeText = 'Kreditering') or
						       ($invoiceTypeText = 'kreditering') or
								 ($invoiceTypeText = 'CREDIT NOTE') or
								 ($invoiceTypeText = 'Credit Note') or
								 ($invoiceTypeText = 'Credit note') or
								 ($invoiceTypeText = 'credit note')
								">
				<xsl:value-of select="$txtCREDITNOTE"/>
				<xsl:choose>
					<xsl:when test="($originCode = 'Copy') and
										(($originText = 'KOPIO') or
										 ($originText = 'Kopio') or
										 ($originText = 'kopio') or
										 ($originText = 'KOPIA') or
										 ($originText = 'Kopia') or
										 ($originText = 'kopia') or
										 ($originText = 'COPY')  or
										 ($originText = 'Copy')  or
										 ($originText = 'copy')
										)">
						(<xsl:value-of select="$txtCOPY"/>)
					</xsl:when>
					<xsl:otherwise>
						<xsl:if test="($originCode = 'Copy') and (string-length($originText) != 0)">
							(<xsl:value-of select="$originText"/>)
						</xsl:if>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$invoiceTypeText"/>
				<xsl:if test="($originCode = 'Copy') and (string-length($originText) != 0)">
					(<xsl:value-of select="$originText"/>)
				</xsl:if>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<!-- Template, joka tulostaa laskurivin (InvoiceRow tai SubInvoiceRow) tietoja. -->
	<xsl:template name="OutputRow">
		<xsl:param name="styleName"/>
		<xsl:param name="addEmptyRow"/>
		<xsl:param name="articleIdentifier"/>
		<xsl:param name="articleName"/>
		<xsl:param name="articleInfoUrlText"/>
		<xsl:param name="buyerArticleIdentifier"/>
		<xsl:param name="offeredQuantity"/>
		<xsl:param name="offeredQuantityUnitCode"/>
		<xsl:param name="deliveredQuantities"/>
		<xsl:param name="orderedQuantity"/>
		<xsl:param name="orderedQuantityUnitCode"/>
		<xsl:param name="confirmedQuantity"/>
		<xsl:param name="confirmedQuantityUnitCode"/>
		<xsl:param name="postDeliveredQuantity"/>
		<xsl:param name="postDeliveredQuantityUnitCode"/>
		<xsl:param name="invoicedQuantities"/>
		<xsl:param name="creditRequestedQuantity"/>
		<xsl:param name="creditRequestedQuantityUnitCode"/>
		<xsl:param name="returnedQuantity"/>
		<xsl:param name="returnedQuantityUnitCode"/>
		<xsl:param name="startDate"/>
		<xsl:param name="startDateFormat"/>
		<xsl:param name="endDate"/>
		<xsl:param name="endDateFormat"/>
		<xsl:param name="unitPriceAmount"/>
		<xsl:param name="unitPriceUnitCode"/>
		<xsl:param name="unitPriceVatIncludedAmount"/>
		<xsl:param name="unitPriceVatIncludedUnitCode"/>
		<xsl:param name="unitPriceBaseQuantity"/>
		<xsl:param name="unitPriceBaseQuantityUnitCode"/>
		<xsl:param name="rowIdentifier"/>
		<xsl:param name="rowIdentifierUrlText"/>
		<xsl:param name="rowIdentifierDate"/>
		<xsl:param name="rowIdentifierDateFormat"/>
		<xsl:param name="rowDeliveryIdentifier"/>
		<xsl:param name="rowDeliveryIdentifierUrlText"/>
		<xsl:param name="rowDeliveryDate"/>
		<xsl:param name="rowDeliveryDateFormat"/>
		<xsl:param name="rowQuotationIdentifier"/>
		<xsl:param name="rowQuotationIdentifierUrlText"/>
		<xsl:param name="rowDiscounts"/>
		<xsl:param name="rowVatRatePercent"/>
		<xsl:param name="rowVatAmount"/>
		<xsl:param name="rowVatExcludedAmount"/>
		<xsl:param name="rowAmount"/>
		<xsl:param name="rowTerminalAddressText"/>
		<xsl:param name="rowWaybillIdentifier"/>
		<xsl:param name="rowWaybillMakerText"/>
		<xsl:param name="rowAgreementIdentifier"/>
		<xsl:param name="rowAgreementIdentifierUrlText"/>
		<xsl:param name="originalInvoiceNumber"/>
		<xsl:param name="rowPriceListIdentifier"/>
		<xsl:param name="rowPriceListIdentifierUrlText"/>
		<xsl:param name="rowRequestOfQuotationIdentifier"/>
		<xsl:param name="rowRequestOfQuotationIdentifierUrlText"/>
		<xsl:param name="rowDelivererName1"/>
		<xsl:param name="rowDelivererName2"/>
		<xsl:param name="rowDelivererName3"/>
		<xsl:param name="rowDelivererIdentifier"/>
		<xsl:param name="rowDelivererCountryCode"/>
		<xsl:param name="rowDelivererCountryName"/>
		<xsl:param name="rowManufacturerName1"/>
		<xsl:param name="rowManufacturerName2"/>
		<xsl:param name="rowManufacturerName3"/>
		<xsl:param name="rowManufacturerIdentifier"/>
		<xsl:param name="rowManufacturerCountryCode"/>
		<xsl:param name="rowManufacturerCountryName"/>
		<xsl:param name="rowShortProposedAccountIdentifier"/>
		<xsl:param name="rowNormalProposedAccountIdentifier"/>
		<xsl:param name="rowProposedAccountText"/>
		<xsl:param name="rowAccountDimensionText"/>
		<xsl:param name="rowAveragePriceAmount"/>
		<xsl:param name="articleGroupIdentifier"/>
		<xsl:param name="eanCode"/>
		<xsl:param name="rowRegistrationNumberIdentifier"/>
		<xsl:param name="serialNumberIdentifier"/>
		<xsl:param name="rowActionCode"/>
		<xsl:param name="rowClearanceIdentifier"/>
		<xsl:param name="rowDeliveryNoteIdentifier"/>
		<xsl:param name="rowPlaceOfDischarge"/>
		<xsl:param name="rowFinalDestinationName1"/>
		<xsl:param name="rowFinalDestinationName2"/>
		<xsl:param name="rowFinalDestinationName3"/>
		<xsl:param name="rowDefinitions"/>
		<xsl:param name="rowOrdererName"/>
		<xsl:param name="rowSalesPersonName"/>
		<xsl:param name="rowProjectReferenceIdentifier"/>
		<xsl:param name="rowManufacturerArticleIdentifier"/>
		<xsl:param name="rowManufacturerOrderIdentifier"/>
		<xsl:param name="rowPackageLength"/>
		<xsl:param name="rowPackageLengthQUC"/>
		<xsl:param name="rowPackageWidth"/>
		<xsl:param name="rowPackageWidthQUC"/>
		<xsl:param name="rowPackageHeight"/>
		<xsl:param name="rowPackageHeightQUC"/>
		<xsl:param name="rowPackageWeight"/>
		<xsl:param name="rowPackageWeightQUC"/>
		<xsl:param name="rowPackageNetWeight"/>
		<xsl:param name="rowPackageNetWeightQUC"/>
		<xsl:param name="rowPackageVolume"/>
		<xsl:param name="rowPackageVolumeQUC"/>
		<xsl:param name="rowTransportCarriageQuantity"/>
		<xsl:param name="rowTransportCarriageQuantityQUC"/>
		<xsl:param name="rowUsedQuantity"/>
		<xsl:param name="rowUsedQuantityQUC"/>
		<xsl:param name="rowPreviousMeterReadingDate"/>
		<xsl:param name="rowLatestMeterReadingDate"/>
		<xsl:param name="rowCalculatedQuantity"/>
		<xsl:param name="rowCalculatedQuantityQUC"/>
		<xsl:if test="string-length($addEmptyRow) != 0">
			<xsl:element name="tr">
				<xsl:attribute name="class"><xsl:value-of select="$styleName"/></xsl:attribute>
				<td colspan="8" class="invoiceRowHrStyle"><br/></td>
			</xsl:element>
		</xsl:if>
		<xsl:variable name="RowPartHTML">
			<xsl:if test="$useColHdrRow1 + $useColHdrRow2 + $useColHdrRow3 != 0">
				<xsl:element name="tr">
					<xsl:attribute name="class"><xsl:value-of select="$styleName"/></xsl:attribute>
					<xsl:attribute name="valign">top</xsl:attribute>
					<xsl:if test="$useRowCol1 != 0">
						<td>
							<xsl:if test="$foundArticleName != 0">
								<xsl:call-template name="FormatLink">
									<xsl:with-param name="link" select="$articleInfoUrlText"/>
									<xsl:with-param name="text" select="$articleName"/>
								</xsl:call-template>
								<br/>
							</xsl:if>
							<xsl:if test="$foundRowIdentifier != 0">
								<xsl:call-template name="FormatLink">
									<xsl:with-param name="link" select="$rowIdentifierUrlText"/>
									<xsl:with-param name="text" select="$rowIdentifier"/>
								</xsl:call-template>
								<br/>
							</xsl:if>
							<xsl:if test="$foundRowIdentifierDate != 0">
								<xsl:call-template name="OutputDate">
									<xsl:with-param name="theDate" select="$rowIdentifierDate"/>
									<xsl:with-param name="theFormat" select="$rowIdentifierDateFormat"/>
								</xsl:call-template>
								<br/>
							</xsl:if>
						</td>
					</xsl:if>
					<xsl:if test="$useRowCol2 != 0">
						<td>
							<xsl:if test="$foundArticleIdentifier != 0">
								<xsl:value-of select="$articleIdentifier"/>
								<br/>
							</xsl:if>
							<xsl:if test="$foundBuyerArticleIdentifier != 0">
								<xsl:value-of select="$buyerArticleIdentifier"/>
								<br/>
							</xsl:if>
							<xsl:if test="$foundRowQuotationIdentifier != 0">
								<xsl:call-template name="FormatLink">
									<xsl:with-param name="link" select="$rowQuotationIdentifierUrlText"/>
									<xsl:with-param name="text" select="$rowQuotationIdentifier"/>
								</xsl:call-template>
								<br/>
							</xsl:if>
						</td>
					</xsl:if>
					<xsl:if test="$useRowCol3 != 0">
						<td>
							<xsl:if test="$foundDeliveredQuantity != 0">
								<xsl:copy-of select="$deliveredQuantities"/>
							</xsl:if>
						</td>
					</xsl:if>
					<xsl:if test="$useRowCol4 != 0">
						<td>
							<xsl:if test="$foundRowDeliveryDates != 0">
								<xsl:call-template name="OutputDatePeriod">
									<xsl:with-param name="theDate" select="$rowDeliveryDate"/>
									<xsl:with-param name="theDateFormat" select="$rowDeliveryDateFormat"/>
									<xsl:with-param name="theStartDate" select="$startDate"/>
									<xsl:with-param name="theStartDateFormat" select="$startDateFormat"/>
									<xsl:with-param name="theEndDate" select="$endDate"/>
									<xsl:with-param name="theEndDateFormat" select="$endDateFormat"/>
								</xsl:call-template>
								<br/>
							</xsl:if>
							<xsl:if test="$foundRowDeliveryIdentifier != 0">
								<xsl:call-template name="FormatLink">
									<xsl:with-param name="link" select="$rowDeliveryIdentifierUrlText"/>
									<xsl:with-param name="text" select="$rowDeliveryIdentifier"/>
								</xsl:call-template>
								<br/>
							</xsl:if>
						</td>
					</xsl:if>
					<xsl:if test="$useRowCol5 != 0">
						<td align="right">
							<xsl:if test="$foundUnitPriceAmount != 0">
								<xsl:choose>
									<xsl:when test="string-length(normalize-space($unitPriceAmount)) != 0">
										<xsl:call-template name="OutputTextBR">
											<xsl:with-param name="txtText">
												<xsl:call-template name="BuildString">
													<xsl:with-param name="txtText">
														<xsl:call-template name="BuildString">
															<xsl:with-param name="txtText" select="$unitPriceAmount"/>
															<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
															<xsl:with-param name="txtText2" select="$unitPriceUnitCode"/>
														</xsl:call-template>
													</xsl:with-param>
													<xsl:with-param name="txtText2">
														<xsl:call-template name="BuildString">
															<xsl:with-param name="txtText" select="$unitPriceBaseQuantity"/>
															<xsl:with-param name="txtText2" select="$unitPriceBaseQuantityUnitCode"/>
														</xsl:call-template>
													</xsl:with-param>
												</xsl:call-template>
											</xsl:with-param>
										</xsl:call-template>
									</xsl:when>
									<xsl:otherwise>
										<br/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:if>
							<xsl:if test="$foundUnitPriceVatIncludedAmount != 0">
								<xsl:choose>
									<xsl:when test="string-length(normalize-space($unitPriceVatIncludedAmount)) != 0">
										<xsl:call-template name="OutputTextBR">
											<xsl:with-param name="txtText">
												<xsl:call-template name="BuildString">
													<xsl:with-param name="txtText">
														<xsl:call-template name="BuildString">
															<xsl:with-param name="txtText" select="$unitPriceVatIncludedAmount"/>
															<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
															<xsl:with-param name="txtText2" select="$unitPriceVatIncludedUnitCode"/>
														</xsl:call-template>
													</xsl:with-param>
													<xsl:with-param name="txtText2">
														<xsl:call-template name="BuildString">
															<xsl:with-param name="txtText" select="$unitPriceBaseQuantity"/>
															<xsl:with-param name="txtText2" select="$unitPriceBaseQuantityUnitCode"/>
														</xsl:call-template>
													</xsl:with-param>
												</xsl:call-template>
											</xsl:with-param>
										</xsl:call-template>
									</xsl:when>
									<xsl:otherwise>
										<br/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:if>
							<xsl:if test="$foundRowAveragePriceAmount != 0">
								<xsl:value-of select="$rowAveragePriceAmount"/>
							</xsl:if>
						</td>
					</xsl:if>
					<xsl:if test="$useRowCol6 != 0">
						<td align="right">
							<xsl:value-of select="$rowVatRatePercent"/>
						</td>
					</xsl:if>
					<xsl:if test="$useRowCol7 != 0">
						<td align="right">
							<xsl:if test="$foundRowVatAmount != 0">
								<xsl:value-of select="$rowVatAmount"/><br/>
							</xsl:if>
							<xsl:if test="$foundRowVatExcludedAmount != 0">
								<xsl:value-of select="$rowVatExcludedAmount"/>
							</xsl:if>
						</td>
					</xsl:if>
					<xsl:if test="$useRowCol8 != 0">
						<td align="right">
							<xsl:value-of select="$rowAmount"/>
						</td>
					</xsl:if>
				</xsl:element>
			</xsl:if>
		</xsl:variable>
		<xsl:if test="string-length(normalize-space($RowPartHTML)) != 0">
			<xsl:copy-of select="$RowPartHTML"/>
		</xsl:if>
		<xsl:if test="string-length(normalize-space($rowDiscounts)) != 0">
			<xsl:copy-of select="$rowDiscounts"/>
		</xsl:if>
		<xsl:variable name="rowTable1">
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="emptyRow">1</xsl:with-param>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtOfferedQuantity"/>
				<xsl:with-param name="txtText" select="$offeredQuantity"/>
				<xsl:with-param name="txtText2" select="$offeredQuantityUnitCode"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtOrderedQuantity"/>
				<xsl:with-param name="txtText" select="$orderedQuantity"/>
				<xsl:with-param name="txtText2" select="$orderedQuantityUnitCode"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtConfirmedQuantity"/>
				<xsl:with-param name="txtText" select="$confirmedQuantity"/>
				<xsl:with-param name="txtText2" select="$confirmedQuantityUnitCode"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtPostDeliveredQuantity"/>
				<xsl:with-param name="txtText" select="$postDeliveredQuantity"/>
				<xsl:with-param name="txtText2" select="$postDeliveredQuantityUnitCode"/>
			</xsl:call-template>
			<xsl:if test="string-length(normalize-space($invoicedQuantities)) != 0">
				<xsl:copy-of select="$invoicedQuantities"/>
			</xsl:if>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtRowUsedQuantity"/>
				<xsl:with-param name="txtText" select="$rowUsedQuantity"/>
				<xsl:with-param name="txtText2" select="$rowUsedQuantityQUC"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtRowPreviousMeterReadingDate"/>
				<xsl:with-param name="txtText" select="$rowPreviousMeterReadingDate"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtRowLatestMeterReadingDate"/>
				<xsl:with-param name="txtText" select="$rowLatestMeterReadingDate"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtRowCalculatedQuantity"/>
				<xsl:with-param name="txtText" select="$rowCalculatedQuantity"/>
				<xsl:with-param name="txtText2" select="$rowCalculatedQuantityQUC"/>
			</xsl:call-template>
			<!--
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtCreditRequestedQuantity"/>
				<xsl:with-param name="txtText" select="$creditRequestedQuantity"/>
				<xsl:with-param name="txtText2" select="$creditRequestedQuantityUnitCode"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtReturnedQuantity"/>
				<xsl:with-param name="txtText" select="$returnedQuantity"/>
				<xsl:with-param name="txtText2" select="$returnedQuantityUnitCode"/>
			</xsl:call-template>
			-->
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtArticleGroupIdentifier"/>
				<xsl:with-param name="txtText" select="$articleGroupIdentifier"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtEanCode"/>
				<xsl:with-param name="txtText" select="$eanCode"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtRegistrationNumberId"/>
				<xsl:with-param name="txtText" select="$rowRegistrationNumberIdentifier"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtSerialNumberId"/>
				<xsl:with-param name="txtText" select="$serialNumberIdentifier"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtRowActionCode"/>
				<xsl:with-param name="txtText" select="$rowActionCode"/>
			</xsl:call-template>
			<xsl:if test="string-length(normalize-space($rowDefinitions)) != 0">
				<xsl:copy-of select="$rowDefinitions"/>
			</xsl:if>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtAgreementIdentifier"/>
				<xsl:with-param name="txtText" select="$rowAgreementIdentifier"/>
				<xsl:with-param name="txtTextUrl" select="$rowAgreementIdentifierUrlText"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtOrdererName"/>
				<xsl:with-param name="txtText" select="$rowOrdererName"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtSalesPersonName"/>
				<xsl:with-param name="txtText" select="$rowSalesPersonName"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtOriginalInvoice"/>
				<xsl:with-param name="txtText" select="$originalInvoiceNumber"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtPriceListIdentifier"/>
				<xsl:with-param name="txtText" select="$rowPriceListIdentifier"/>
				<xsl:with-param name="txtTextUrl" select="$rowPriceListIdentifierUrlText"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtRequestOfQuotationIdentifier"/>
				<xsl:with-param name="txtText" select="$rowRequestOfQuotationIdentifier"/>
				<xsl:with-param name="txtTextUrl" select="$rowRequestOfQuotationIdentifierUrlText"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtProjectRefId"/>
				<xsl:with-param name="txtText" select="$rowProjectReferenceIdentifier"/>
			</xsl:call-template>
			<xsl:if test="(string-length($rowShortProposedAccountIdentifier) != 0) or (string-length($rowNormalProposedAccountIdentifier) != 0) or (string-length($rowAccountDimensionText) != 0)">
				<xsl:variable name="accounts">
					<xsl:if test="string-length($rowShortProposedAccountIdentifier) != 0">
						<xsl:value-of select="$rowShortProposedAccountIdentifier"/>
						<xsl:if test="(string-length($rowNormalProposedAccountIdentifier) != 0) or (string-length($rowAccountDimensionText) != 0)">
							<xsl:text>, </xsl:text>
						</xsl:if>
					</xsl:if>
					<xsl:if test="string-length($rowNormalProposedAccountIdentifier) != 0">
						<xsl:value-of select="$rowNormalProposedAccountIdentifier"/>
						<xsl:if test="string-length($rowAccountDimensionText) != 0">
							<xsl:text>, </xsl:text>
						</xsl:if>
					</xsl:if>
					<xsl:if test="string-length($rowAccountDimensionText) != 0">
						<xsl:value-of select="$rowAccountDimensionText"/>
					</xsl:if>
				</xsl:variable>
				<xsl:call-template name="OutputTableRow">
					<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
					<xsl:with-param name="txtTitle" select="$txtProposedAccountIdentifier"/>
					<xsl:with-param name="txtText" select="$accounts"/>
				</xsl:call-template>
			</xsl:if>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtProposedAccountIdentifier"/>
				<xsl:with-param name="txtText" select="$rowProposedAccountText"/>
			</xsl:call-template>
		</xsl:variable>
		<xsl:variable name="rowTable2">
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="emptyRow">1</xsl:with-param>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtDeliverer"/>
				<xsl:with-param name="txtText" select="$rowDelivererName1"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle"/>
				<xsl:with-param name="txtText" select="$rowDelivererName2"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle"/>
				<xsl:with-param name="txtText" select="$rowDelivererName3"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle"/>
				<xsl:with-param name="txtText" select="$rowDelivererIdentifier"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle"/>
				<xsl:with-param name="txtText" select="$rowDelivererCountryCode"/>
				<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
				<xsl:with-param name="txtText2" select="$rowDelivererCountryName"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtTerminalAddress"/>
				<xsl:with-param name="txtText" select="$rowTerminalAddressText"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtWaybillIdentifier"/>
				<xsl:with-param name="txtText" select="$rowWaybillIdentifier"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtClearanceId"/>
				<xsl:with-param name="txtText" select="$rowClearanceIdentifier"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtDeliveryNoteId"/>
				<xsl:with-param name="txtText" select="$rowDeliveryNoteIdentifier"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtPlaceOfDischarge"/>
				<xsl:with-param name="txtText" select="$rowPlaceOfDischarge"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtFinalDestination"/>
				<xsl:with-param name="txtText" select="$rowFinalDestinationName1"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle"/>
				<xsl:with-param name="txtText" select="$rowFinalDestinationName2"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle"/>
				<xsl:with-param name="txtText" select="$rowFinalDestinationName3"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtManufacturer"/>
				<xsl:with-param name="txtText" select="$rowManufacturerName1"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle"/>
				<xsl:with-param name="txtText" select="$rowManufacturerName2"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle"/>
				<xsl:with-param name="txtText" select="$rowManufacturerName3"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle"/>
				<xsl:with-param name="txtText" select="$rowManufacturerIdentifier"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle"/>
				<xsl:with-param name="txtText" select="$rowManufacturerCountryCode"/>
				<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
				<xsl:with-param name="txtText2" select="$rowManufacturerCountryName"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtManufacturerArticleId"/>
				<xsl:with-param name="txtText" select="$rowManufacturerArticleIdentifier"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
				<xsl:with-param name="txtTitle" select="$txtManufacturerOrderId"/>
				<xsl:with-param name="txtText" select="$rowManufacturerOrderIdentifier"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
				<xsl:with-param name="txtTitle" select="$txtPackageLength"/>
				<xsl:with-param name="txtText" select="$rowPackageLength"/>
				<xsl:with-param name="txtText2" select="$rowPackageLengthQUC"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
				<xsl:with-param name="txtTitle" select="$txtPackageWidth"/>
				<xsl:with-param name="txtText" select="$rowPackageWidth"/>
				<xsl:with-param name="txtText2" select="$rowPackageWidthQUC"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
				<xsl:with-param name="txtTitle" select="$txtPackageHeight"/>
				<xsl:with-param name="txtText" select="$rowPackageHeight"/>
				<xsl:with-param name="txtText2" select="$rowPackageHeightQUC"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
				<xsl:with-param name="txtTitle" select="$txtPackageWeight"/>
				<xsl:with-param name="txtText" select="$rowPackageWeight"/>
				<xsl:with-param name="txtText2" select="$rowPackageWeightQUC"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
				<xsl:with-param name="txtTitle" select="$txtPackageNetWeight"/>
				<xsl:with-param name="txtText" select="$rowPackageNetWeight"/>
				<xsl:with-param name="txtText2" select="$rowPackageNetWeightQUC"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
				<xsl:with-param name="txtTitle" select="$txtPackageVolume"/>
				<xsl:with-param name="txtText" select="$rowPackageVolume"/>
				<xsl:with-param name="txtText2" select="$rowPackageVolumeQUC"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTableRow">
				<xsl:with-param name="styleName" select="'invoiceRowStyle'"/>
				<xsl:with-param name="txtTitle" select="$txtTransportCarriageQuantity"/>
				<xsl:with-param name="txtText" select="$rowTransportCarriageQuantity"/>
				<xsl:with-param name="txtText2" select="$rowTransportCarriageQuantityQUC"/>
			</xsl:call-template>
		</xsl:variable>
		<xsl:if test="string-length(normalize-space($rowTable1)) + string-length(normalize-space($rowTable2)) != 0">
			<xsl:element name="tr">
				<xsl:attribute name="class"><xsl:value-of select="$styleName"/></xsl:attribute>
				<xsl:attribute name="valign">top</xsl:attribute>
				<td colspan="8">
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<xsl:element name="tr">
								<xsl:attribute name="class"><xsl:value-of select="$styleName"/></xsl:attribute>
								<xsl:attribute name="valign">top</xsl:attribute>
								<xsl:if test="string-length(normalize-space($rowTable1)) != 0">
									<td>
										<table width="100%" cellpadding="0" cellspacing="0">
											<tbody>
												<xsl:copy-of select="$rowTable1"/>
											</tbody>
										</table>
									</td>
									<td width="40px"><br/></td>
								</xsl:if>
								<xsl:if test="string-length(normalize-space($rowTable2)) != 0">
									<td>
										<table width="100%" cellpadding="0" cellspacing="0">
											<tbody>
												<xsl:copy-of select="$rowTable2"/>
											</tbody>
										</table>
									</td>
								</xsl:if>
							</xsl:element>
						</tbody>
					</table>
				</td>
			</xsl:element>
		</xsl:if>
	</xsl:template>
	<!-- Template, joka tulostaa laskurivin alennustiedot. -->
	<xsl:template name="OutputRowDiscount">
		<xsl:param name="styleName"/>
		<xsl:param name="discountNumber"/>
		<xsl:param name="rowDiscountTypeText"/>
		<xsl:param name="rowDiscountPercent"/>
		<xsl:param name="rowDiscountAmount"/>
		<xsl:param name="rowDiscountAmountCurrencyIdentifier"/>
		<xsl:if test="string-length(normalize-space($rowDiscountTypeText)) + string-length(normalize-space($rowDiscountPercent)) + string-length(normalize-space($rowDiscountAmount)) != 0">
			<xsl:element name="tr">
				<xsl:attribute name="class"><xsl:value-of select="$styleName"/></xsl:attribute>
				<xsl:attribute name="valign">top</xsl:attribute>
				<xsl:variable name="spanCol1">
					<xsl:choose>
						<xsl:when test="$useRowCol1 = 0">0</xsl:when>
						<xsl:otherwise>1</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="spanCol2">
					<xsl:choose>
						<xsl:when test="$useRowCol2 = 0">0</xsl:when>
						<xsl:otherwise>1</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="spanCol3">
					<xsl:choose>
						<xsl:when test="$useRowCol3 = 0">0</xsl:when>
						<xsl:otherwise>1</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="spanCols" select="$spanCol1 + $spanCol2 + $spanCol3"/>
				<xsl:if test="$spanCols > 0">
					<xsl:element name="td">
						<xsl:attribute name="colspan"><xsl:value-of select="$spanCols"/></xsl:attribute>
					</xsl:element>
				</xsl:if>
				<td>
					<xsl:choose>
						<xsl:when test="string-length(normalize-space($rowDiscountTypeText)) != 0">
							<xsl:value-of select="$rowDiscountTypeText"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="$txtRowDiscount"/><xsl:value-of select="$discountNumber"/>
						</xsl:otherwise>
					</xsl:choose>
				</td>
				<td align="right">
					<xsl:if test="string-length(normalize-space($rowDiscountPercent)) != 0">
						<xsl:value-of select="$rowDiscountPercent"/><xsl:text> %</xsl:text>
					</xsl:if>
				</td>
				<td align="right">
					<xsl:if test="string-length(normalize-space($rowDiscountAmount)) != 0">
						<xsl:call-template name="OutputAmount">
							<xsl:with-param name="amount" select="$rowDiscountAmount"/>
							<xsl:with-param name="currency" select="$rowDiscountAmountCurrencyIdentifier"/>
						</xsl:call-template>
					</xsl:if>
				</td>
				<td colspan="2">
					<!--<b><xsl:value-of select="$txtRowDiscount"/>:</b>-->
				</td>
			</xsl:element>
		</xsl:if>
	</xsl:template>
	<!-- Template, joka tulostaa taulukkorivin. -->
	<xsl:template name="OutputTableRow">
		<xsl:param name="styleName"/>
		<xsl:param name="txtTitle"/>
		<xsl:param name="txtTitleNormal"/>
		<xsl:param name="txtText"/>
		<xsl:param name="txtTextUrl"/>
		<xsl:param name="txtTextDelimiter"/>
		<xsl:param name="txtText2"/>
		<xsl:param name="txtTextCopy"/>
		<xsl:param name="emptyRow"/>
		<xsl:if test="(string-length(normalize-space($txtText)) + string-length(normalize-space($txtText2)) + string-length(normalize-space($txtTextCopy))  != 0) or ($emptyRow = 1)">
			<xsl:element name="tr">
				<xsl:attribute name="class"><xsl:value-of select="$styleName"/></xsl:attribute>
				<xsl:attribute name="valign">top</xsl:attribute>
				<td align="left">
					<xsl:if test="string-length(normalize-space($txtTitle)) != 0">
						<b><xsl:value-of select="$txtTitle"/>:</b>
					</xsl:if>
					<xsl:if test="string-length(normalize-space($txtTitleNormal)) != 0">
						<xsl:value-of select="$txtTitleNormal"/>:
					</xsl:if>
				</td>
				<td width="5px"><br/></td>
				<td align="left">
					<xsl:choose>
						<xsl:when test="$emptyRow = 1">
							<br/>
						</xsl:when>
						<xsl:when test="string-length(normalize-space($txtTextCopy)) != 0">
							<xsl:copy-of select="$txtTextCopy"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:call-template name="BuildString">
								<xsl:with-param name="txtText" select="$txtText"/>
								<xsl:with-param name="txtTextUrl" select="$txtTextUrl"/>
								<xsl:with-param name="txtTextDelimiter" select="$txtTextDelimiter"/>
								<xsl:with-param name="txtText2" select="$txtText2"/>
							</xsl:call-template>
						</xsl:otherwise>
					</xsl:choose>
				</td>
			</xsl:element>
		</xsl:if>
	</xsl:template>
	<!-- Template, joka kokoaa merkkijonon. -->
	<xsl:template name="BuildString">
		<xsl:param name="txtTitle"/>
		<xsl:param name="txtText"/>
		<xsl:param name="txtTextUrl"/>
		<xsl:param name="txtTextDelimiter"/>
		<xsl:param name="txtText2"/>
		<xsl:param name="txtText2NotAlone"/>
		<xsl:if test="(string-length(normalize-space($txtText)) != 0) or ((string-length(normalize-space($txtText2))  != 0) and (string-length($txtText2NotAlone) = 0))">
			<xsl:if test="string-length(normalize-space($txtTitle)) != 0">
				<xsl:value-of select="$txtTitle"/><xsl:text>: </xsl:text>
			</xsl:if>
			<xsl:if test="string-length(normalize-space($txtText)) != 0">
				<xsl:call-template name="FormatLink">
					<xsl:with-param name="link" select="$txtTextUrl"/>
					<xsl:with-param name="text" select="$txtText"/>
				</xsl:call-template>
			</xsl:if>
			<xsl:if test="string-length(normalize-space($txtText2)) != 0">
				<xsl:if test="string-length(normalize-space($txtText)) != 0">
					<xsl:text> </xsl:text>
					<xsl:if test="string-length($txtTextDelimiter) != 0">
						<xsl:value-of select="$txtTextDelimiter"/>
						<xsl:text> </xsl:text>
					</xsl:if>
				</xsl:if>
				<xsl:value-of select="$txtText2"/>
			</xsl:if>
		</xsl:if>
	</xsl:template>
	<!-- Template, joka tulostaa tekstin ja rivinvaihdon. -->
	<xsl:template name="OutputTextBR">
		<xsl:param name="txtTitleRowCopy"/>
		<xsl:param name="txtTitle"/>
		<xsl:param name="txtText"/>
		<xsl:param name="txtTextCopy"/>
		<xsl:choose>
			<xsl:when test="string-length(normalize-space($txtText)) != 0">
				<xsl:if test="string-length($txtTitleRowCopy) != 0">
					<xsl:copy-of select="$txtTitleRowCopy"/><br/>
				</xsl:if>
				<xsl:if test="string-length(normalize-space($txtTitle)) != 0">
					<xsl:value-of select="$txtTitle"/><xsl:text>: </xsl:text>
				</xsl:if>
				<xsl:value-of select="$txtText"/><br/>
			</xsl:when>
			<xsl:when test="string-length(normalize-space($txtTextCopy)) != 0">
				<xsl:if test="string-length($txtTitleRowCopy) != 0">
					<xsl:copy-of select="$txtTitleRowCopy"/><br/>
				</xsl:if>
				<xsl:if test="string-length(normalize-space($txtTitle)) != 0">
					<xsl:value-of select="$txtTitle"/><xsl:text>: </xsl:text>
				</xsl:if>
				<xsl:copy-of select="$txtTextCopy"/><br/>
			</xsl:when>
		</xsl:choose>
	</xsl:template>
	<!-- Template, joka tulostaa AnyParty-tiedot. -->
	<xsl:template name="OutputAnyPartyDetails">
		<xsl:param name="styleName"/>
		<xsl:param name="info"/>
		<xsl:param name="anyPartyText"/>
		<xsl:param name="anyPartyTextAnyPartyCode"/>
		<xsl:param name="anyPartyIdentifier"/>
		<xsl:param name="anyPartyOrganisationName1"/>
		<xsl:param name="anyPartyOrganisationName2"/>
		<xsl:param name="anyPartyOrganisationDepartment1"/>
		<xsl:param name="anyPartyOrganisationDepartment2"/>
		<xsl:param name="anyPartyOrganisationUnitNumber"/>
		<xsl:param name="anyPartySiteCode"/>
		<xsl:param name="anyPartyStreetName1"/>
		<xsl:param name="anyPartyStreetName2"/>
		<xsl:param name="anyPartyStreetName3"/>
		<xsl:param name="anyPartyTownName"/>
		<xsl:param name="anyPartyPostCodeIdentifier"/>
		<xsl:param name="countryCode"/>
		<xsl:param name="countryName"/>
		<xsl:param name="anyPartyPostOfficeBoxIdentifier"/>
		<xsl:param name="anyPartyContactPersonName"/>
		<xsl:param name="anyPartyContactPersonFunction1"/>
		<xsl:param name="anyPartyContactPersonFunction2"/>
		<xsl:param name="anyPartyContactPersonDepartment1"/>
		<xsl:param name="anyPartyContactPersonDepartment2"/>
		<xsl:param name="anyPartyEmailaddressIdentifier"/>
		<xsl:param name="anyPartyPhoneNumberIdentifier"/>
		<xsl:if test="string-length(normalize-space($anyPartyText)) != 0">
			<td>
				<table cellpadding="0" cellspacing="0">
					<tbody>
						<xsl:element name="tr">
							<xsl:attribute name="class"><xsl:value-of select="$styleName"/></xsl:attribute>
							<xsl:attribute name="align">left</xsl:attribute>
							<td colspan="3">
								<xsl:value-of select="$anyPartyText"/>
								<xsl:if test="string-length(normalize-space($anyPartyTextAnyPartyCode)) != 0">
									<xsl:text> </xsl:text>(<xsl:value-of select="$anyPartyTextAnyPartyCode"/>)
								</xsl:if>
								<xsl:if test="string-length(normalize-space($info)) != 0">
									<xsl:text> </xsl:text>(<xsl:value-of select="$info"/>)
								</xsl:if>
								:
							</td>
						</xsl:element>
						<xsl:call-template name="OutputTableRow">
							<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
							<xsl:with-param name="txtTitleNormal" select="$txtAnyPartyIdentifier"/>
							<xsl:with-param name="txtText" select="$anyPartyIdentifier"/>
						</xsl:call-template>
						<xsl:call-template name="OutputTableRow">
							<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
							<xsl:with-param name="txtTitleNormal" select="$txtAnyPartyOrgName"/>
							<xsl:with-param name="txtTextCopy">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyOrganisationName1"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyOrganisationName2"/>
								</xsl:call-template>
							</xsl:with-param>
						</xsl:call-template>
						<xsl:call-template name="OutputTableRow">
							<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
							<xsl:with-param name="txtTitleNormal" select="$txtAnyPartyOrgDep"/>
							<xsl:with-param name="txtTextCopy">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyOrganisationDepartment1"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyOrganisationDepartment2"/>
								</xsl:call-template>
							</xsl:with-param>
						</xsl:call-template>
						<xsl:call-template name="OutputTableRow">
							<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
							<xsl:with-param name="txtTitleNormal" select="$txtAddress"/>
							<xsl:with-param name="txtTextCopy">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyPostOfficeBoxIdentifier"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyStreetName1"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyStreetName2"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyStreetName3"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtTextCopy">
										<xsl:call-template name="BuildString">
											<xsl:with-param name="txtText" select="$anyPartyPostCodeIdentifier"/>
											<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
											<xsl:with-param name="txtText2" select="$anyPartyTownName"/>
										</xsl:call-template>
									</xsl:with-param>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtTextCopy">
										<xsl:call-template name="BuildString">
											<xsl:with-param name="txtText" select="$countryCode"/>
											<xsl:with-param name="txtTextDelimiter">/</xsl:with-param>
											<xsl:with-param name="txtText2" select="$countryName"/>
										</xsl:call-template>
									</xsl:with-param>
								</xsl:call-template>
							</xsl:with-param>
						</xsl:call-template>
						<xsl:call-template name="OutputTableRow">
							<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
							<xsl:with-param name="txtTitleNormal" select="$txtContact"/>
							<xsl:with-param name="txtTextCopy">
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyContactPersonName"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyContactPersonFunction1"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyContactPersonFunction2"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyContactPersonDepartment1"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyContactPersonDepartment2"/>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtTextCopy">
										<xsl:call-template name="FormatEmail">
											<xsl:with-param name="email" select="$anyPartyEmailaddressIdentifier"/>
										</xsl:call-template>
									</xsl:with-param>
								</xsl:call-template>
								<xsl:call-template name="OutputTextBR">
									<xsl:with-param name="txtText" select="$anyPartyPhoneNumberIdentifier"/>
								</xsl:call-template>
							</xsl:with-param>
						</xsl:call-template>
						<xsl:call-template name="OutputTableRow">
							<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
							<xsl:with-param name="txtTitleNormal" select="$txtAnyPartyOrgUnit"/>
							<xsl:with-param name="txtText" select="$anyPartyOrganisationUnitNumber"/>
						</xsl:call-template>
						<xsl:call-template name="OutputTableRow">
							<xsl:with-param name="styleName"><xsl:value-of select="$styleName"/></xsl:with-param>
							<xsl:with-param name="txtTitleNormal" select="$txtSiteCode"/>
							<xsl:with-param name="txtText" select="$anyPartySiteCode"/>
						</xsl:call-template>
					</tbody>
				</table>
			</td>
		</xsl:if>
	</xsl:template>
	<!-- Template, joka tulostaa korkolaskun tiedot. -->
	<xsl:template name="OutputRowOverDuePaymentDetails">
		<xsl:param name="styleName"/>
		<xsl:param name="addEmptyRow"/>
		<xsl:param name="rowOriginalInvoiceIdentifier"/>
		<xsl:param name="rowOriginalInvoiceDate"/>
		<xsl:param name="rowOriginalDueDate"/>
		<xsl:param name="rowOriginalInvoiceTotalAmount"/>
		<xsl:param name="rowOriginalInvoiceTotalAmountCI"/>
		<xsl:param name="rowOriginalEpiRemittanceInfoIdentifier"/>
		<xsl:param name="rowPaidVatIncludedAmount"/>
		<xsl:param name="rowPaidVatIncludedAmountCI"/>
		<xsl:param name="rowPaidDate"/>
		<xsl:param name="rowUnPaidVatIncludedAmount"/>
		<xsl:param name="rowUnPaidVatIncludedAmountCI"/>
		<xsl:param name="rowCollectionDate"/>
		<xsl:param name="rowCollectionQuantity"/>
		<xsl:param name="rowCollectionQuantityQUC"/>
		<xsl:param name="rowCollectionChargeAmount"/>
		<xsl:param name="rowCollectionChargeAmountCI"/>
		<xsl:param name="rowInterestRate"/>
		<xsl:param name="rowInterestStartDate"/>
		<xsl:param name="rowInterestEndDate"/>
		<xsl:param name="rowInterestPeriodText"/>
		<xsl:param name="rowInterestDateNumber"/>
		<xsl:param name="rowInterestChargeAmount"/>
		<xsl:param name="rowInterestChargeAmountCI"/>
		<xsl:param name="rowInterestChargeVatAmount"/>
		<xsl:param name="rowInterestChargeVatAmountCI"/>
		<xsl:if test="string-length($addEmptyRow) != 0">
			<xsl:element name="tr">
				<xsl:attribute name="class"><xsl:value-of select="$styleName"/></xsl:attribute>
				<td colspan="8" class="invoiceRowHrStyle"><br/></td>
			</xsl:element>
		</xsl:if>
		<xsl:element name="tr">
			<xsl:attribute name="class"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
			<xsl:call-template name="OutputRODPDGroup1">
				<xsl:with-param name="outputTitles">1</xsl:with-param>
				<xsl:with-param name="rowOriginalInvoiceIdentifier" select="$rowOriginalInvoiceIdentifier"/>
				<xsl:with-param name="rowOriginalInvoiceDate" select="$rowOriginalInvoiceDate"/>
				<xsl:with-param name="rowOriginalDueDate" select="$rowOriginalDueDate"/>
				<xsl:with-param name="rowOriginalInvoiceTotalAmount" select="$rowOriginalInvoiceTotalAmount"/>
				<xsl:with-param name="rowOriginalInvoiceTotalAmountCI" select="$rowOriginalInvoiceTotalAmountCI"/>
				<xsl:with-param name="rowOriginalEpiRemittanceInfoIdentifier" select="$rowOriginalEpiRemittanceInfoIdentifier"/>
				<xsl:with-param name="rowPaidVatIncludedAmount" select="$rowPaidVatIncludedAmount"/>
				<xsl:with-param name="rowPaidVatIncludedAmountCI" select="$rowPaidVatIncludedAmountCI"/>
				<xsl:with-param name="rowPaidDate" select="$rowPaidDate"/>
			</xsl:call-template>
			<xsl:call-template name="OutputRODPDGroup1">
				<xsl:with-param name="outputTitles">0</xsl:with-param>
				<xsl:with-param name="rowOriginalInvoiceIdentifier" select="$rowOriginalInvoiceIdentifier"/>
				<xsl:with-param name="rowOriginalInvoiceDate" select="$rowOriginalInvoiceDate"/>
				<xsl:with-param name="rowOriginalDueDate" select="$rowOriginalDueDate"/>
				<xsl:with-param name="rowOriginalInvoiceTotalAmount" select="$rowOriginalInvoiceTotalAmount"/>
				<xsl:with-param name="rowOriginalInvoiceTotalAmountCI" select="$rowOriginalInvoiceTotalAmountCI"/>
				<xsl:with-param name="rowOriginalEpiRemittanceInfoIdentifier" select="$rowOriginalEpiRemittanceInfoIdentifier"/>
				<xsl:with-param name="rowPaidVatIncludedAmount" select="$rowPaidVatIncludedAmount"/>
				<xsl:with-param name="rowPaidVatIncludedAmountCI" select="$rowPaidVatIncludedAmountCI"/>
				<xsl:with-param name="rowPaidDate" select="$rowPaidDate"/>
			</xsl:call-template>
			<xsl:call-template name="OutputRODPDGroup2">
				<xsl:with-param name="outputTitles">1</xsl:with-param>
				<xsl:with-param name="rowUnPaidVatIncludedAmount" select="$rowUnPaidVatIncludedAmount"/>
				<xsl:with-param name="rowUnPaidVatIncludedAmountCI" select="$rowUnPaidVatIncludedAmountCI"/>
				<xsl:with-param name="rowCollectionDate" select="$rowCollectionDate"/>
				<xsl:with-param name="rowCollectionQuantity" select="$rowCollectionQuantity"/>
				<xsl:with-param name="rowCollectionQuantityQUC" select="$rowCollectionQuantityQUC"/>
				<xsl:with-param name="rowCollectionChargeAmount" select="$rowCollectionChargeAmount"/>
				<xsl:with-param name="rowCollectionChargeAmountCI" select="$rowCollectionChargeAmountCI"/>
				<xsl:with-param name="rowInterestRate" select="$rowInterestRate"/>
				<xsl:with-param name="rowInterestStartDate" select="$rowInterestStartDate"/>
				<xsl:with-param name="rowInterestEndDate" select="$rowInterestEndDate"/>
				<xsl:with-param name="rowInterestPeriodText" select="$rowInterestPeriodText"/>
				<xsl:with-param name="rowInterestDateNumber" select="$rowInterestDateNumber"/>
				<xsl:with-param name="rowInterestChargeAmount" select="$rowInterestChargeAmount"/>
				<xsl:with-param name="rowInterestChargeAmountCI" select="$rowInterestChargeAmountCI"/>
				<xsl:with-param name="rowInterestChargeVatAmount" select="$rowInterestChargeVatAmount"/>
				<xsl:with-param name="rowInterestChargeVatAmountCI" select="$rowInterestChargeVatAmountCI"/>
			</xsl:call-template>
			<xsl:call-template name="OutputRODPDGroup2">
				<xsl:with-param name="outputTitles">0</xsl:with-param>
				<xsl:with-param name="rowUnPaidVatIncludedAmount" select="$rowUnPaidVatIncludedAmount"/>
				<xsl:with-param name="rowUnPaidVatIncludedAmountCI" select="$rowUnPaidVatIncludedAmountCI"/>
				<xsl:with-param name="rowCollectionDate" select="$rowCollectionDate"/>
				<xsl:with-param name="rowCollectionQuantity" select="$rowCollectionQuantity"/>
				<xsl:with-param name="rowCollectionQuantityQUC" select="$rowCollectionQuantityQUC"/>
				<xsl:with-param name="rowCollectionChargeAmount" select="$rowCollectionChargeAmount"/>
				<xsl:with-param name="rowCollectionChargeAmountCI" select="$rowCollectionChargeAmountCI"/>
				<xsl:with-param name="rowInterestRate" select="$rowInterestRate"/>
				<xsl:with-param name="rowInterestStartDate" select="$rowInterestStartDate"/>
				<xsl:with-param name="rowInterestEndDate" select="$rowInterestEndDate"/>
				<xsl:with-param name="rowInterestPeriodText" select="$rowInterestPeriodText"/>
				<xsl:with-param name="rowInterestDateNumber" select="$rowInterestDateNumber"/>
				<xsl:with-param name="rowInterestChargeAmount" select="$rowInterestChargeAmount"/>
				<xsl:with-param name="rowInterestChargeAmountCI" select="$rowInterestChargeAmountCI"/>
				<xsl:with-param name="rowInterestChargeVatAmount" select="$rowInterestChargeVatAmount"/>
				<xsl:with-param name="rowInterestChargeVatAmountCI" select="$rowInterestChargeVatAmountCI"/>
			</xsl:call-template>
			<td width="20%"><br/></td>
		</xsl:element>
	</xsl:template>
	<xsl:template name="OutputRODPDGroup1">
		<xsl:param name="outputTitles"/>
		<xsl:param name="rowOriginalInvoiceIdentifier"/>
		<xsl:param name="rowOriginalInvoiceDate"/>
		<xsl:param name="rowOriginalDueDate"/>
		<xsl:param name="rowOriginalInvoiceTotalAmount"/>
		<xsl:param name="rowOriginalInvoiceTotalAmountCI"/>
		<xsl:param name="rowOriginalEpiRemittanceInfoIdentifier"/>
		<xsl:param name="rowPaidVatIncludedAmount"/>
		<xsl:param name="rowPaidVatIncludedAmountCI"/>
		<xsl:param name="rowPaidDate"/>
		<td>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtOriginalInvoiceIdentifier"/>
				<xsl:with-param name="txtText" select="$rowOriginalInvoiceIdentifier"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtOriginalInvoiceDate"/>
				<xsl:with-param name="txtText" select="$rowOriginalInvoiceDate"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtOriginalDueDate"/>
				<xsl:with-param name="txtText" select="$rowOriginalDueDate"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtOriginalInvoiceTotalAmount"/>
				<xsl:with-param name="txtText">
					<xsl:call-template name="OutputAmount">
						<xsl:with-param name="amount" select="$rowOriginalInvoiceTotalAmount"/>
						<xsl:with-param name="currency" select="$rowOriginalInvoiceTotalAmountCI"/>
					</xsl:call-template>
				</xsl:with-param>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtOriginalEpiRemittanceInfoIdentifier"/>
				<xsl:with-param name="txtTextCopy">
					<xsl:call-template name="OutputEpiRemittanceInfoIdentifier">
						<xsl:with-param name="erii" select="$rowOriginalEpiRemittanceInfoIdentifier"/>
					</xsl:call-template>
				</xsl:with-param>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtPaidAmount"/>
				<xsl:with-param name="txtText">
					<xsl:call-template name="OutputAmount">
						<xsl:with-param name="amount" select="$rowPaidVatIncludedAmount"/>
						<xsl:with-param name="currency" select="$rowPaidVatIncludedAmountCI"/>
					</xsl:call-template>
				</xsl:with-param>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtPaidDate"/>
				<xsl:with-param name="txtText" select="$rowPaidDate"/>
			</xsl:call-template>
		</td>
	</xsl:template>
	
	<xsl:template name="OutputRODPDGroup2">
		<xsl:param name="outputTitles"/>
		<xsl:param name="rowUnPaidVatIncludedAmount"/>
		<xsl:param name="rowUnPaidVatIncludedAmountCI"/>
		<xsl:param name="rowCollectionDate"/>
		<xsl:param name="rowCollectionQuantity"/>
		<xsl:param name="rowCollectionQuantityQUC"/>
		<xsl:param name="rowCollectionChargeAmount"/>
		<xsl:param name="rowCollectionChargeAmountCI"/>
		<xsl:param name="rowInterestRate"/>
		<xsl:param name="rowInterestStartDate"/>
		<xsl:param name="rowInterestEndDate"/>
		<xsl:param name="rowInterestPeriodText"/>
		<xsl:param name="rowInterestDateNumber"/>
		<xsl:param name="rowInterestChargeAmount"/>
		<xsl:param name="rowInterestChargeAmountCI"/>
		<xsl:param name="rowInterestChargeVatAmount"/>
		<xsl:param name="rowInterestChargeVatAmountCI"/>
		<td>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtUnPaidAmount"/>
				<xsl:with-param name="txtText">
					<xsl:call-template name="OutputAmount">
						<xsl:with-param name="amount" select="$rowUnPaidVatIncludedAmount"/>
						<xsl:with-param name="currency" select="$rowUnPaidVatIncludedAmountCI"/>
					</xsl:call-template>
				</xsl:with-param>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtCollectionDate"/>
				<xsl:with-param name="txtText" select="$rowCollectionDate"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtCollectionQuantity"/>
				<xsl:with-param name="txtText" select="$rowCollectionQuantity"/>
				<xsl:with-param name="txtText2" select="$rowCollectionQuantityQUC"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtCollectionChargeAmount"/>
				<xsl:with-param name="txtText">
					<xsl:call-template name="OutputAmount">
						<xsl:with-param name="amount" select="$rowCollectionChargeAmount"/>
						<xsl:with-param name="currency" select="$rowCollectionChargeAmountCI"/>
					</xsl:call-template>
				</xsl:with-param>
			</xsl:call-template>
			<xsl:if test="string-length(normalize-space($rowInterestRate)) != 0">
				<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
					<xsl:with-param name="txtTitle" select="$txtInterestRate"/>
					<xsl:with-param name="txtText" select="$rowInterestRate"/>
					<xsl:with-param name="txtText2">%</xsl:with-param>
				</xsl:call-template>
			</xsl:if>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtInterestStartDate"/>
				<xsl:with-param name="txtText" select="$rowInterestStartDate"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtInterestEndDate"/>
				<xsl:with-param name="txtText" select="$rowInterestEndDate"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtInterestPeriodText"/>
				<xsl:with-param name="txtText" select="$rowInterestPeriodText"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtInterestDateNumber"/>
				<xsl:with-param name="txtText" select="$rowInterestDateNumber"/>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtInterestChargeAmount"/>
				<xsl:with-param name="txtText">
					<xsl:call-template name="OutputAmount">
						<xsl:with-param name="amount" select="$rowInterestChargeAmount"/>
						<xsl:with-param name="currency" select="$rowInterestChargeAmountCI"/>
					</xsl:call-template>
				</xsl:with-param>
			</xsl:call-template>
			<xsl:call-template name="OutputTitleOrTextBR">
				<xsl:with-param name="outputTitle" select="$outputTitles"/>
				<xsl:with-param name="txtTitle" select="$txtInterestChargeVatAmount"/>
				<xsl:with-param name="txtText">
					<xsl:call-template name="OutputAmount">
						<xsl:with-param name="amount" select="$rowInterestChargeVatAmount"/>
						<xsl:with-param name="currency" select="$rowInterestChargeVatAmountCI"/>
					</xsl:call-template>
				</xsl:with-param>
			</xsl:call-template>
		</td>
	</xsl:template>
	<xsl:template name="OutputTitleOrTextBR">
		<xsl:param name="outputTitle"/>
		<xsl:param name="txtTitle"/>
		<xsl:param name="txtTitleNormal"/>
		<xsl:param name="txtText"/>
		<xsl:param name="txtTextUrl"/>
		<xsl:param name="txtTextDelimiter"/>
		<xsl:param name="txtText2"/>
		<xsl:param name="txtTextCopy"/>
		<xsl:param name="emptyRow"/>
		<xsl:if test="(string-length(normalize-space($txtText)) + string-length(normalize-space($txtText2)) + string-length(normalize-space($txtTextCopy))  != 0) or ($emptyRow = 1)">
			<xsl:choose>
				<xsl:when test="$emptyRow = 1">
					<br/>
				</xsl:when>
				<xsl:when test="$outputTitle = 1">
					<xsl:if test="string-length(normalize-space($txtTitle)) != 0">
						<b><xsl:value-of select="$txtTitle"/>:</b><br/>
					</xsl:if>
					<xsl:if test="string-length(normalize-space($txtTitleNormal)) != 0">
						<xsl:value-of select="$txtTitleNormal"/>:<br/>
					</xsl:if>
				</xsl:when>
				<xsl:when test="string-length(normalize-space($txtTextCopy)) != 0">
					<xsl:copy-of select="$txtTextCopy"/><br/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="BuildString">
						<xsl:with-param name="txtText" select="$txtText"/>
						<xsl:with-param name="txtTextUrl" select="$txtTextUrl"/>
						<xsl:with-param name="txtTextDelimiter" select="$txtTextDelimiter"/>
						<xsl:with-param name="txtText2" select="$txtText2"/>
					</xsl:call-template>
					<br/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>
