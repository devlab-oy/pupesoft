<?xml version="1.0" encoding="ISO-8859-1"?>
<!-- Copyright -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<!-- Tekstit alkavat -->
<!-- XSL-rivit suomeksi -->
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
<xsl:variable name="txtBuyerPartyIdentifier">Ostajan asiakasnro</xsl:variable>
<xsl:variable name="txtEpiDateOptionDate">Laskun eräpäivä</xsl:variable>
<xsl:variable name="txtInvoiceTotalVatIncludedAmount">Laskun määrä</xsl:variable>
<xsl:variable name="txtEpiAccountID">Saajan pankkitili</xsl:variable>
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
<xsl:variable name="txtUnitPriceAmount">a-hinta</xsl:variable>
<xsl:variable name="txtRowDeliveryDates">Toimitus.pvm (jak)</xsl:variable>
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
<xsl:variable name="txtFaxNumber">Fax</xsl:variable>
<xsl:variable name="txtWebaddressIdentifier">WWW-osoite</xsl:variable>
<xsl:variable name="txtEmailaddressIdentifier">Sähköposti</xsl:variable>
<xsl:variable name="txtHomeTownName">Kotipaikka</xsl:variable>
<xsl:variable name="txtEur">euroa</xsl:variable>
<xsl:variable name="txtLink">Linkki</xsl:variable>
<xsl:variable name="txtAgreementIdentifier">Sopimusviite</xsl:variable>
<xsl:variable name="txtProposedAccountIdentifier">Tiliöintiehdotus</xsl:variable>
<xsl:variable name="txtSellerBic">Pankin Bic-tunnus</xsl:variable>
<xsl:variable name="txtDeliverer">Toimittaja</xsl:variable>
<xsl:variable name="txtManufacturer">Valmistaja</xsl:variable>
<xsl:variable name="txtInvoiceRecipientAddress">Laskutusosoite</xsl:variable>
<xsl:variable name="txtCashDiscountDate">Kassa-alennuspäivä</xsl:variable>
<xsl:variable name="txtCashDiscountPercent">Kassa-alennusprosentti</xsl:variable>
<xsl:variable name="txtCashDiscountAmount">Alennuksen veroll. määrä</xsl:variable>
<xsl:variable name="txtInvoiceSender">Laskun lähettäjä</xsl:variable>
<xsl:variable name="txtOriginalInvoice">Alkuperäinen lasku</xsl:variable>
<xsl:variable name="txtPriceListIdentifier">Hinnaston viite</xsl:variable>
<xsl:variable name="txtRequestOfQuotationIdentifier">Tarjouspyynnön viite</xsl:variable>
<xsl:variable name="txtDeliveryInfo">Toimitustiedot</xsl:variable>
<!-- Tekstit loppuivat -->
	<!-- Tutkitaan, mitä sarakkeita tuoteriveille tarvitaan. -->
	<xsl:variable name="foundArticleName"            select="count(//Finvoice/InvoiceRow/ArticleName) + count(//Finvoice/InvoiceRow/ArticleInfoUrlText) +
	                                                              count(//Finvoice/InvoiceRow/SubInvoiceRow/SubArticleName) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubArticleInfoUrlText)
																			       "/>
	<xsl:variable name="foundRowIdentifier"          select="count(//Finvoice/InvoiceRow/RowIdentifier) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowIdentifier) +
	                                                              count(//Finvoice/InvoiceRow/RowIdentifierUrlText) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowIdentifierUrlText)
	                                                             "/>
	<xsl:variable name="foundRowIdentifierDate"      select="count(//Finvoice/InvoiceRow/RowIdentifierDate) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowIdentifierDate)"/>
	<xsl:variable name="foundArticleIdentifier"      select="count(//Finvoice/InvoiceRow/ArticleIdentifier) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubArticleIdentifier)"/>
	<xsl:variable name="foundBuyerArticleIdentifier" select="count(//Finvoice/InvoiceRow/BuyerArticleIdentifier) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubBuyerArticleIdentifier)"/>
	<xsl:variable name="foundRowQuotationIdentifier" select="count(//Finvoice/InvoiceRow/RowQuotationIdentifier) + count(//Finvoice/InvoiceRow/RowQuotationIdentifierUrlText) +
	                                                              count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowQuotationIdentifier) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowQuotationIdentifierUrlText)
																			       "/>
	<xsl:variable name="foundDeliveredQuantity"      select="count(//Finvoice/InvoiceRow/DeliveredQuantity) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubDeliveredQuantity)"/>
	<xsl:variable name="foundOrderedQuantity"        select="count(//Finvoice/InvoiceRow/OrderedQuantity) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubOrderedQuantity)"/>
	<xsl:variable name="foundConfirmedQuantity"      select="count(//Finvoice/InvoiceRow/ConfirmedQuantity) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubConfirmedQuantity)"/>
	<xsl:variable name="foundUnitPriceAmount"        select="count(//Finvoice/InvoiceRow/UnitPriceAmount) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubUnitPriceAmount)"/>
	<xsl:variable name="foundRowDeliveryDates"       select="count(//Finvoice/InvoiceRow/RowDeliveryDate) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowDeliveryDate) +
														                       count(//Finvoice/InvoiceRow/StartDate) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubStartDate) +
														                       count(//Finvoice/InvoiceRow/EndDate) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubEndDate)
	                                                             "/>
	<xsl:variable name="foundRowDeliveryIdentifier"  select="count(//Finvoice/InvoiceRow/RowDeliveryIdentifier) + count(//Finvoice/InvoiceRow/RowDeliveryUrlText) +
	                                                              count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowDeliveryIdentifier) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowDeliveryUrlText)
																			       "/>
	<xsl:variable name="foundRowDiscountPercent"     select="count(//Finvoice/InvoiceRow/RowDiscountPercent) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowDiscountPercent)"/>
	<xsl:variable name="foundRowVatRatePercent"      select="count(//Finvoice/InvoiceRow/RowVatRatePercent) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowVatRatePercent)"/>
	<xsl:variable name="foundRowVatAmount"           select="count(//Finvoice/InvoiceRow/RowVatAmount) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowVatAmount)"/>
	<xsl:variable name="foundRowVatExcludedAmount"   select="count(//Finvoice/InvoiceRow/RowVatExcludedAmount) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowVatExcludedAmount)"/>
	<xsl:variable name="foundRowAmount"              select="count(//Finvoice/InvoiceRow/RowAmount) + count(//Finvoice/InvoiceRow/SubInvoiceRow/SubRowAmount)"/>

	<xsl:variable name="useRowCol1" select="$foundArticleName + $foundRowIdentifier + $foundRowIdentifierDate"/>
	<xsl:variable name="useRowCol2" select="$foundArticleIdentifier + $foundBuyerArticleIdentifier + $foundRowQuotationIdentifier"/>
	<xsl:variable name="useRowCol3" select="$foundDeliveredQuantity + $foundOrderedQuantity + $foundConfirmedQuantity"/>
	<xsl:variable name="useRowCol4" select="$foundUnitPriceAmount + $foundRowDeliveryDates + $foundRowDeliveryIdentifier"/>
	<xsl:variable name="useRowCol5" select="$foundRowDiscountPercent"/>
	<xsl:variable name="useRowCol6" select="$foundRowVatRatePercent"/>
	<xsl:variable name="useRowCol7" select="$foundRowVatAmount + $foundRowVatExcludedAmount"/>
	<xsl:variable name="useRowCol8" select="$foundRowAmount"/>
	
	<xsl:variable name="useColHdrRow1" select="$foundArticleName + $foundArticleIdentifier + $foundDeliveredQuantity +
	                                           $foundUnitPriceAmount + $foundRowDiscountPercent + $foundRowVatRatePercent +
															 $foundRowVatAmount + $foundRowAmount
														   "/>
	<xsl:variable name="useColHdrRow2" select="$foundRowIdentifier + $foundBuyerArticleIdentifier + $foundOrderedQuantity +
	                                           $foundRowDeliveryDates + $foundRowVatExcludedAmount
														   "/>
	<xsl:variable name="useColHdrRow3" select="$foundRowIdentifierDate + $foundRowQuotationIdentifier + $foundConfirmedQuantity +
	                                           $foundRowDeliveryIdentifier
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
					#invoiceStyle { font-size:small }
					#invoiceRowStyle { font-size:small }
					#invoiceRowStyle th { font-size:small; border-top-width: 1px; border-bottom-width: 1px; padding:1px }
					#invoiceRowStyle td { font-size:small; padding:1px }
					#subRowStyle { font-size:small; font-weight:bold; background-color:rgb(243,243,243) }
					#sellerDetailsStyle { font-size:xx-small }
					#preStyle { border-style:solid none solid none; border-top-width:thin; border-bottom-width:thin; margin:0px; padding-top:2px; padding-bottom:2px }
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
				<!-- Tehdään kolmen sarakkeen taulukko, johon tulostetaan tuoterivien yläpuoliset tiedot. -->
				<table width="100%" cellpadding="0" cellspacing="0">
					<tr id="invoiceStyle" valign="top" align="left">
						<!-- Ensimmäinen sarake -->
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
								<xsl:value-of select="."/><br/>
							</xsl:for-each>
							<xsl:if test="string-length(SellerContactPersonName) != 0">
								<xsl:value-of select="SellerContactPersonName"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(SellerCommunicationDetails/SellerEmailaddressIdentifier) != 0">
								<xsl:call-template name="FormatEmail">
									<xsl:with-param name="email" select="SellerCommunicationDetails/SellerEmailaddressIdentifier"/>
								</xsl:call-template>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(SellerCommunicationDetails/SellerPhoneNumberIdentifier) != 0">
								<xsl:value-of select="SellerCommunicationDetails/SellerPhoneNumberIdentifier"/>
								<br/>
							</xsl:if>
							<br/>
							<br/>
							<xsl:if test="count(InvoiceRecipientPartyDetails) != 0">
								<xsl:for-each select="InvoiceRecipientPartyDetails/InvoiceRecipientOrganisationName">
									<xsl:value-of select="."/><br/>
								</xsl:for-each>
								<xsl:if test="string-length(InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/InvoiceRecipientPostOfficeBoxIdentifier) != 0">
									<xsl:value-of select="InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/InvoiceRecipientPostOfficeBoxIdentifier"/>
									<br/>
								</xsl:if>
								<xsl:if test="string-length(InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/InvoiceRecipientStreetName) != 0">
									<xsl:value-of select="InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/InvoiceRecipientStreetName"/>
									<br/>
								</xsl:if>
								<xsl:if test="(string-length(InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/InvoiceRecipientPostCodeIdentifier) != 0) or
								              (string-length(InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/InvoiceRecipientTownName) != 0)
												 ">
									<xsl:value-of select="InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/InvoiceRecipientPostCodeIdentifier"/>
									<xsl:text> </xsl:text>
									<xsl:value-of select="InvoiceRecipientPartyDetails/InvoiceRecipientPostalAddressDetails/InvoiceRecipientTownName"/>
									<br/>
								</xsl:if>
							</xsl:if>
							<br/>
							<br/>
							<xsl:value-of select="$txtBuyerOrganisationName"/>:<br/>
							<xsl:for-each select="BuyerPartyDetails/BuyerOrganisationName">
								<xsl:value-of select="."/><br/>
							</xsl:for-each>
							<xsl:if test="string-length(BuyerPartyDetails/BuyerPostalAddressDetails/BuyerPostOfficeBoxIdentifier) != 0">
								<xsl:value-of select="BuyerPartyDetails/BuyerPostalAddressDetails/BuyerPostOfficeBoxIdentifier"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(BuyerPartyDetails/BuyerPostalAddressDetails/BuyerStreetName) != 0">
								<xsl:value-of select="BuyerPartyDetails/BuyerPostalAddressDetails/BuyerStreetName"/>
								<br/>
							</xsl:if>
							<xsl:if test="(string-length(BuyerPartyDetails/BuyerPostalAddressDetails/BuyerPostCodeIdentifier) != 0) or
							              (string-length(BuyerPartyDetails/BuyerPostalAddressDetails/BuyerTownName) != 0)
											 ">
								<xsl:value-of select="BuyerPartyDetails/BuyerPostalAddressDetails/BuyerPostCodeIdentifier"/>
								<xsl:text> </xsl:text>
								<xsl:value-of select="BuyerPartyDetails/BuyerPostalAddressDetails/BuyerTownName"/>
								<br/>
							</xsl:if>
							<br/>
							<xsl:if test="string-length(BuyerContactPersonName) != 0">
								<xsl:value-of select="BuyerContactPersonName"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(BuyerCommunicationDetails/BuyerEmailaddressIdentifier) != 0">
								<xsl:call-template name="FormatEmail">
									<xsl:with-param name="email" select="BuyerCommunicationDetails/BuyerEmailaddressIdentifier"/>
								</xsl:call-template>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(BuyerCommunicationDetails/BuyerPhoneNumberIdentifier) != 0">
								<xsl:value-of select="BuyerCommunicationDetails/BuyerPhoneNumberIdentifier"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(BuyerPartyDetails/BuyerOrganisationTaxCode) != 0">
								<xsl:value-of select="BuyerPartyDetails/BuyerOrganisationTaxCode"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(BuyerOrganisationUnitNumber) != 0">
								<xsl:value-of select="BuyerOrganisationUnitNumber"/>
								<br/>
							</xsl:if>
							<xsl:if test="(string-length(InvoiceRecipientContactPersonName) != 0) or
							              (string-length(InvoiceRecipientCommunicationDetails/InvoiceRecipientEmailaddressIdentifier) != 0) or
							              (string-length(InvoiceRecipientCommunicationDetails/InvoiceRecipientPhoneNumberIdentifier) != 0) or
							              (string-length(InvoiceRecipientPartyDetails/InvoiceRecipientPartyIdentifier) != 0) or
							              (string-length(InvoiceRecipientOrganisationUnitNumber) != 0)
							             ">
								<br/>
								<xsl:value-of select="$txtInvoiceRecipientContact"/>:<br/>
								<xsl:if test="(string-length(InvoiceRecipientContactPersonName) != 0)">
									<xsl:value-of select="InvoiceRecipientContactPersonName"/><br/>
								</xsl:if>
								<xsl:if test="(string-length(InvoiceRecipientCommunicationDetails/InvoiceRecipientEmailaddressIdentifier) != 0)">
									<xsl:value-of select="InvoiceRecipientCommunicationDetails/InvoiceRecipientEmailaddressIdentifier"/><br/>
								</xsl:if>
								<xsl:if test="(string-length(InvoiceRecipientCommunicationDetails/InvoiceRecipientPhoneNumberIdentifier) != 0)">
									<xsl:value-of select="InvoiceRecipientCommunicationDetails/InvoiceRecipientPhoneNumberIdentifier"/><br/>
								</xsl:if>
								<xsl:if test="(string-length(InvoiceRecipientPartyDetails/InvoiceRecipientPartyIdentifier) != 0)">
									<xsl:value-of select="InvoiceRecipientPartyDetails/InvoiceRecipientPartyIdentifier"/><br/>
								</xsl:if>
								<xsl:if test="(string-length(InvoiceRecipientOrganisationUnitNumber) != 0)">
									<xsl:value-of select="InvoiceRecipientOrganisationUnitNumber"/><br/>
								</xsl:if>
							</xsl:if>
						</td>
						<!-- Toinen sarake -->
						<td width="20%">
							<xsl:value-of select="$txtInvoiceDate"/>:<br/>
							<xsl:value-of select="$txtInvoiceNumber"/>:<br/>
							<xsl:if test="string-length(InvoiceDetails/SellerReferenceIdentifier) != 0">
								<xsl:value-of select="$txtSellerReferenceIdentifier"/>:<br/>
							</xsl:if>
							<xsl:if test="string-length(InvoiceDetails/OrderIdentifier) != 0">
								<xsl:value-of select="$txtOrderIdentifier"/>:<br/>
							</xsl:if>
							<xsl:if test="string-length(InvoiceDetails/AgreementIdentifier) != 0">
								<xsl:value-of select="$txtAgreement"/>:<br/>
							</xsl:if>
							<xsl:if test="string-length(BuyerPartyDetails/BuyerPartyIdentifier) != 0">
								<xsl:value-of select="$txtBuyerPartyIdentifier"/>:<br/>
							</xsl:if>
							<br/>
							<xsl:value-of select="$txtEpiDateOptionDate"/>:<br/>
							<xsl:value-of select="$txtInvoiceTotalVatIncludedAmount"/>:<br/>
							<xsl:value-of select="$txtEpiAccountID"/>:<br/>
							<xsl:if test="string-length(EpiDetails/EpiPartyDetails/EpiBfiPartyDetails/EpiBfiIdentifier) != 0">
								<xsl:value-of select="$txtSellerBic"/>:<br/>
							</xsl:if>
							<xsl:if test="string-length(EpiDetails/EpiPartyDetails/EpiBeneficiaryPartyDetails/EpiNameAddressDetails) != 0">
								<xsl:value-of select="$txtEpiNameAddressDetails"/>:<br/>
							</xsl:if>
							<xsl:value-of select="$txtEpiRemittanceInfoIdentifier"/>:<br/>
							<br/>
							<xsl:if test="string-length(PaymentStatusDetails/PaymentStatusCode) != 0">
								<xsl:value-of select="$txtPaymentStatusCode"/>:<br/>
							</xsl:if>
							<xsl:if test="string-length(PaymentStatusDetails/PaymentMethodText) != 0">
								<xsl:value-of select="$txtPaymentMethodText"/>:<br/>
							</xsl:if>
							<xsl:for-each select="InvoiceDetails/PaymentTermsDetails">
								<xsl:if test="string-length(PaymentOverDueFineDetails/PaymentOverDueFineFreeText) != 0">
									<xsl:value-of select="$txtPaymentOverDueFineDetails"/>:<br/>
								</xsl:if>
								<xsl:if test="string-length(PaymentOverDueFineDetails/PaymentOverDueFinePercent) != 0">
									<xsl:value-of select="$txtPaymentOverDueFinePercent"/>:<br/>
								</xsl:if>
								<br/>
								<xsl:if test="string-length(PaymentTermsFreeText) != 0">
									<xsl:value-of select="$txtPaymentTermsFreeText"/>:<br/>
								</xsl:if>
								<xsl:if test="string-length(CashDiscountDate) != 0">
									<xsl:value-of select="$txtCashDiscountDate"/>:<br/>
								</xsl:if>
								<xsl:if test="string-length(CashDiscountPercent) != 0">
									<xsl:value-of select="$txtCashDiscountPercent"/>:<br/>
								</xsl:if>
								<xsl:if test="string-length(CashDiscountAmount) != 0">
									<xsl:value-of select="$txtCashDiscountAmount"/>:<br/>
								</xsl:if>
							</xsl:for-each>
							<xsl:if test="string-length(normalize-space(InvoiceSenderPartyDetails/InvoiceSenderOrganisationName)) != 0">
								<br/><xsl:value-of select="$txtInvoiceSender"/>:
								<xsl:for-each select="InvoiceSenderPartyDetails/InvoiceSenderOrganisationName">
									<xsl:if test="string-length(normalize-space(.)) != 0">
										<br/>
									</xsl:if>
								</xsl:for-each>
							</xsl:if>
							<!--<br/>-->
						</td>
						<!-- Kolmas sarake -->
						<td width="30%">
							<xsl:call-template name="OutputDate">
								<xsl:with-param name="theDate" select="InvoiceDetails/InvoiceDate"/>
								<xsl:with-param name="theFormat" select="InvoiceDetails/InvoiceDate/@Format"/>
							</xsl:call-template>
							<br/>
							<xsl:value-of select="InvoiceDetails/InvoiceNumber"/>
							<br/>
							<xsl:if test="string-length(InvoiceDetails/SellerReferenceIdentifier) != 0">
								<xsl:choose>
									<xsl:when test="string-length(InvoiceDetails/SellerReferenceIdentifierUrlText) != 0">  
										<xsl:call-template name="FormatLink">
											<xsl:with-param name="link" select="InvoiceDetails/SellerReferenceIdentifierUrlText"/>
											<xsl:with-param name="text" select="InvoiceDetails/SellerReferenceIdentifier"/>
										</xsl:call-template>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="InvoiceDetails/SellerReferenceIdentifier"/>
									</xsl:otherwise>
								</xsl:choose>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(InvoiceDetails/OrderIdentifier) != 0">
								<xsl:choose>
									<xsl:when test="string-length(InvoiceDetails/OrderIdentifierUrlText) != 0">  
										<xsl:call-template name="FormatLink">
											<xsl:with-param name="link" select="InvoiceDetails/OrderIdentifierUrlText"/>
											<xsl:with-param name="text" select="InvoiceDetails/OrderIdentifier"/>
										</xsl:call-template>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="InvoiceDetails/OrderIdentifier"/>
									</xsl:otherwise>
								</xsl:choose>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(InvoiceDetails/AgreementIdentifier) != 0">
								<xsl:choose>
									<xsl:when test="string-length(InvoiceDetails/AgreementIdentifierUrlText) != 0">  
										<xsl:call-template name="FormatLink">
											<xsl:with-param name="link" select="InvoiceDetails/AgreementIdentifierUrlText"/>
											<xsl:with-param name="text" select="InvoiceDetails/AgreementIdentifier"/>
										</xsl:call-template>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="InvoiceDetails/AgreementIdentifier"/>
									</xsl:otherwise>
								</xsl:choose>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(BuyerPartyDetails/BuyerPartyIdentifier) != 0">
								<xsl:value-of select="BuyerPartyDetails/BuyerPartyIdentifier"/>
								<br/>
							</xsl:if>
							<br/>
							<xsl:call-template name="OutputDate">
								<xsl:with-param name="theDate" select="EpiDetails/EpiPaymentInstructionDetails/EpiDateOptionDate"/>
								<xsl:with-param name="theFormat" select="EpiDetails/EpiPaymentInstructionDetails/EpiDateOptionDate/@Format"/>
							</xsl:call-template>
							<br/>
							<xsl:call-template name="OutputAmount">
								<xsl:with-param name="amount" select="InvoiceDetails/InvoiceTotalVatIncludedAmount"/>
								<xsl:with-param name="currency" select="InvoiceDetails/InvoiceTotalVatIncludedAmount/@AmountCurrencyIdentifier"/>
							</xsl:call-template>
							<br/>
							<xsl:call-template name="OutputEpiAccountID">
								<xsl:with-param name="scheme" select="EpiDetails/EpiPartyDetails/EpiBeneficiaryPartyDetails/EpiAccountID/@IdentificationSchemeName"/>
								<xsl:with-param name="account" select="EpiDetails/EpiPartyDetails/EpiBeneficiaryPartyDetails/EpiAccountID"/>
							</xsl:call-template>
							<br/>
							<xsl:if test="string-length(EpiDetails/EpiPartyDetails/EpiBfiPartyDetails/EpiBfiIdentifier) != 0">
								<xsl:value-of select="EpiDetails/EpiPartyDetails/EpiBfiPartyDetails/EpiBfiIdentifier"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(EpiDetails/EpiPartyDetails/EpiBeneficiaryPartyDetails/EpiNameAddressDetails) != 0">
								<xsl:value-of select="EpiDetails/EpiPartyDetails/EpiBeneficiaryPartyDetails/EpiNameAddressDetails"/>
								<br/>
							</xsl:if>
							<xsl:call-template name="OutputEpiRemittanceInfoIdentifier">
								<xsl:with-param name="erii" select="EpiDetails/EpiPaymentInstructionDetails/EpiRemittanceInfoIdentifier"/>
							</xsl:call-template>
							<br/>
							<br/>
							<xsl:if test="string-length(PaymentStatusDetails/PaymentStatusCode) != 0">
								<xsl:choose>
									<xsl:when test="PaymentStatusDetails/PaymentStatusCode = 'NOTPAID'"><xsl:value-of select="$txtPaymentStatusNotPaid"/></xsl:when>
									<xsl:when test="PaymentStatusDetails/PaymentStatusCode = 'PARTLYPAID'"><xsl:value-of select="$txtPaymentStatusPartlyPaid"/></xsl:when>
									<xsl:when test="PaymentStatusDetails/PaymentStatusCode = 'PAID'"><xsl:value-of select="$txtPaymentStatusPaid"/></xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="PaymentStatusDetails/PaymentStatusCode"/>
									</xsl:otherwise>
								</xsl:choose>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(PaymentStatusDetails/PaymentMethodText) != 0">
								<xsl:value-of select="PaymentStatusDetails/PaymentMethodText"/>
								<br/>
							</xsl:if>
							<xsl:for-each select="InvoiceDetails/PaymentTermsDetails">
								<xsl:if test="string-length(PaymentOverDueFineDetails/PaymentOverDueFineFreeText) != 0">
									<xsl:value-of select="PaymentOverDueFineDetails/PaymentOverDueFineFreeText"/><br/>
								</xsl:if>
								<xsl:if test="string-length(PaymentOverDueFineDetails/PaymentOverDueFinePercent) != 0">
									<xsl:value-of select="PaymentOverDueFineDetails/PaymentOverDueFinePercent"/><xsl:text> %</xsl:text><br/>
								</xsl:if>
								<br/>
								<xsl:if test="string-length(PaymentTermsFreeText) != 0">
									<xsl:value-of select="PaymentTermsFreeText"/><br/>
								</xsl:if>
								<xsl:if test="string-length(CashDiscountDate) != 0">
									<xsl:call-template name="OutputDate">
										<xsl:with-param name="theDate" select="CashDiscountDate"/>
										<xsl:with-param name="theFormat" select="CashDiscountDate/@Format"/>
									</xsl:call-template>
									<br/>
								</xsl:if>
								<xsl:if test="string-length(CashDiscountPercent) != 0">
									<xsl:value-of select="CashDiscountPercent"/><xsl:text> %</xsl:text><br/>
								</xsl:if>
								<xsl:if test="string-length(CashDiscountAmount) != 0">
									<xsl:call-template name="OutputAmount">
										<xsl:with-param name="amount" select="CashDiscountAmount"/>
										<xsl:with-param name="currency" select="CashDiscountAmount/@AmountCurrencyIdentifier"/>
									</xsl:call-template>
									<br/>
								</xsl:if>
							</xsl:for-each>
							<xsl:if test="string-length(normalize-space(InvoiceSenderPartyDetails/InvoiceSenderOrganisationName)) != 0">
								<br/>
								<xsl:for-each select="InvoiceSenderPartyDetails/InvoiceSenderOrganisationName">
									<xsl:if test="string-length(normalize-space(.)) != 0">
										<xsl:value-of select="."/><br/>
									</xsl:if>
								</xsl:for-each>
							</xsl:if>
							<br/>
						</td>
						<!-- Kolmas sarake loppui -->
					</tr>
					<xsl:if test="string-length(InvoiceDetails/InvoiceFreeText) != 0">
						<tr id="invoiceStyle" valign="top" align="left">
							<td width="50%"></td>
							<td colspan="2">
								<xsl:value-of select="InvoiceDetails/InvoiceFreeText"/>
							</td>
						</tr>
					</xsl:if>
				</table>
				<br/>
				<!-- Tehdään tuoteriveille taulukko, johon tulee sarakkeita aiemmin päätelty määrä.	-->
				<table width="100%" cellpadding="0" cellspacing="0" frame="hsides" rules="groups">
					<thead>
					<tr id="invoiceRowStyle" align="left" valign="top">
						<xsl:if test="$useRowCol1 != 0">
							<th>
								<xsl:if test="$foundArticleName != 0">
									<xsl:value-of select="$txtArticleName"/><br/>
								</xsl:if>
								<xsl:if test="$foundRowIdentifier != 0">
									<xsl:value-of select="$txtRowIdentifier"/><br/>
								</xsl:if>
								<xsl:if test="$foundRowIdentifierDate != 0">
									<xsl:value-of select="$txtRowIdentifierDate"/><br/>
								</xsl:if>
							</th>
						</xsl:if>
						<xsl:if test="$useRowCol2 != 0">
							<th>
								<xsl:if test="$foundArticleIdentifier != 0">
									<xsl:value-of select="$txtArticleIdentifier"/><br/>
								</xsl:if>
								<xsl:if test="$foundBuyerArticleIdentifier != 0">
									<xsl:value-of select="$txtBuyerArticleIdentifier"/><br/>
								</xsl:if>
								<xsl:if test="$foundRowQuotationIdentifier != 0">
									<xsl:value-of select="$txtRowQuotationIdentifier"/><br/>
								</xsl:if>
							</th>
						</xsl:if>
						<xsl:if test="$useRowCol3 != 0">
							<th>
								<xsl:if test="$foundDeliveredQuantity != 0">
									<xsl:value-of select="$txtDeliveredQuantity"/><br/>
								</xsl:if>
								<xsl:if test="$foundOrderedQuantity != 0">
									<xsl:value-of select="$txtOrderedQuantity"/><br/>
								</xsl:if>
								<xsl:if test="$foundConfirmedQuantity != 0">
									<xsl:value-of select="$txtConfirmedQuantity"/><br/>
								</xsl:if>
							</th>
						</xsl:if>
						<xsl:if test="$useRowCol4 != 0">
							<th>
								<xsl:if test="$foundUnitPriceAmount != 0">
									<xsl:value-of select="$txtUnitPriceAmount"/><br/>
								</xsl:if>
								<xsl:if test="$foundRowDeliveryDates != 0">
									<xsl:value-of select="$txtRowDeliveryDates"/><br/>
								</xsl:if>
								<xsl:if test="$foundRowDeliveryIdentifier != 0">
									<xsl:value-of select="$txtRowDeliveryIdentifier"/><br/>
								</xsl:if>
							</th>
						</xsl:if>
						<xsl:if test="$useRowCol5 != 0">
							<th align="right">
								<xsl:value-of select="$txtRowDiscount"/>%
							</th>
						</xsl:if>
						<xsl:if test="$useRowCol6 != 0">
							<th align="right">
								<xsl:value-of select="$txtVat"/>%
							</th>
						</xsl:if>
						<xsl:if test="$useRowCol7 != 0">
							<th align="right">
								<xsl:if test="$foundRowVatAmount != 0">
									<xsl:value-of select="$txtVatAmount"/><br/>
								</xsl:if>
								<xsl:if test="$foundRowVatExcludedAmount != 0">
									<xsl:value-of select="$txtVatExcludedAmount"/><br/>
								</xsl:if>
							</th>
						</xsl:if>
						<xsl:if test="$useRowCol8 != 0">
							<th align="right">
								<xsl:value-of select="$txtRowAmount"/>
							</th>
						</xsl:if>
						<xsl:if test="$useColHdrRow1 + $useColHdrRow2 + $useColHdrRow3 = 0">
							<th>
							</th>
						</xsl:if>
					</tr>
					</thead>
					<tbody>
					<xsl:for-each select="InvoiceRow">
						<xsl:variable name="invoiceRowHTML">
							<xsl:call-template name="OutputRow">
								<xsl:with-param name="styleName"                          select="'invoiceRowStyle'"/>
								<xsl:with-param name="addEmptyRow">
									<xsl:if test="position() != 1">
										1
									</xsl:if>
								</xsl:with-param>
								<xsl:with-param name="articleIdentifier"                  select="ArticleIdentifier"/>
								<xsl:with-param name="articleName"                        select="ArticleName"/>
								<xsl:with-param name="articleInfoUrlText"                 select="ArticleInfoUrlText"/>
								<xsl:with-param name="buyerArticleIdentifier"             select="BuyerArticleIdentifier"/>
								<xsl:with-param name="deliveredQuantity"                  select="DeliveredQuantity"/>
								<xsl:with-param name="deliveredQuantityUnitCode"          select="DeliveredQuantity/@QuantityUnitCode"/>
								<xsl:with-param name="orderedQuantity"                    select="OrderedQuantity"/>
								<xsl:with-param name="orderedQuantityUnitCode"            select="OrderedQuantity/@QuantityUnitCode"/>
								<xsl:with-param name="confirmedQuantity"                  select="ConfirmedQuantity"/>
								<xsl:with-param name="confirmedQuantityUnitCode"          select="ConfirmedQuantity/@QuantityUnitCode"/>
								<xsl:with-param name="startDate"                          select="StartDate"/>
								<xsl:with-param name="startDateFormat"                    select="StartDate/@Format"/>
								<xsl:with-param name="endDate"                            select="EndDate"/>
								<xsl:with-param name="endDateFormat"                      select="EndDate/@Format"/>
								<xsl:with-param name="unitPriceAmount"                    select="UnitPriceAmount"/>
								<xsl:with-param name="unitPriceUnitCode"                  select="UnitPriceAmount/@UnitPriceUnitCode"/>
								<xsl:with-param name="rowIdentifier"                      select="RowIdentifier"/>
								<xsl:with-param name="rowIdentifierUrlText"               select="RowIdentifierUrlText"/>
								<xsl:with-param name="rowIdentifierDate"                  select="RowIdentifierDate"/>
								<xsl:with-param name="rowIdentifierDateFormat"            select="RowIdentifierDate/@Format"/>
								<xsl:with-param name="rowDeliveryIdentifier"              select="RowDeliveryIdentifier"/>
								<xsl:with-param name="rowDeliveryUrlText"                 select="RowDeliveryUrlText"/>
								<xsl:with-param name="rowDeliveryDate"                    select="RowDeliveryDate"/>
								<xsl:with-param name="rowDeliveryDateFormat"              select="RowDeliveryDate/@Format"/>
								<xsl:with-param name="rowQuotationIdentifier"             select="RowQuotationIdentifier"/>
								<xsl:with-param name="rowQuotationIdentifierUrlText"      select="RowQuotationIdentifierUrlText"/>
								<xsl:with-param name="rowDiscountPercent"                 select="RowDiscountPercent"/>
								<xsl:with-param name="rowVatRatePercent"                  select="RowVatRatePercent"/>
								<xsl:with-param name="rowVatAmount"                       select="RowVatAmount"/>
								<xsl:with-param name="rowVatExcludedAmount"               select="RowVatExcludedAmount"/>
								<xsl:with-param name="rowAmount"                          select="RowAmount"/>
								<xsl:with-param name="rowTerminalAddressText"             select="RowDeliveryDetails/RowTerminalAddressText"/>
								<xsl:with-param name="rowWaybillIdentifier"               select="RowDeliveryDetails/RowWaybillIdentifier"/>
								<xsl:with-param name="rowWaybillMakerText"                select="RowDeliveryDetails/RowWaybillMakerText"/>
								<xsl:with-param name="rowAgreementIdentifier"             select="RowAgreementIdentifier"/>
								<xsl:with-param name="rowAgreementIdentifierUrlText"      select="RowAgreementIdentifierUrlText"/>
								<xsl:with-param name="originalInvoiceNumber"              select="OriginalInvoiceNumber"/>
								<xsl:with-param name="rowPriceListIdentifier"             select="RowPriceListIdentifier"/>
								<xsl:with-param name="rowPriceListIdentifierUrlText"      select="RowPriceListIdentifierUrlText"/>
								<xsl:with-param name="rowRequestOfQuotationIdentifier"        select="RowRequestOfQuotationIdentifier"/>
								<xsl:with-param name="rowRequestOfQuotationIdentifierUrlText" select="RowRequestOfQuotationIdentifierUrlText"/>
								<xsl:with-param name="rowDelivererName1"                  select="RowDeliveryDetails/RowDelivererName[1]"/>
								<xsl:with-param name="rowDelivererName2"                  select="RowDeliveryDetails/RowDelivererName[2]"/>
								<xsl:with-param name="rowDelivererName3"                  select="RowDeliveryDetails/RowDelivererName[3]"/>
								<xsl:with-param name="rowDelivererIdentifier"             select="RowDeliveryDetails/RowDelivererIdentifier"/>
								<xsl:with-param name="rowDelivererCountryCode"            select="RowDeliveryDetails/RowDelivererCountryCode"/>
								<xsl:with-param name="rowDelivererCountryName"            select="RowDeliveryDetails/RowDelivererCountryName"/>
								<xsl:with-param name="rowManufacturerName1"               select="RowDeliveryDetails/RowManufacturerName[1]"/>
								<xsl:with-param name="rowManufacturerName2"               select="RowDeliveryDetails/RowManufacturerName[2]"/>
								<xsl:with-param name="rowManufacturerName3"               select="RowDeliveryDetails/RowManufacturerName[3]"/>
								<xsl:with-param name="rowManufacturerIdentifier"          select="RowDeliveryDetails/RowManufacturerIdentifier"/>
								<xsl:with-param name="rowManufacturerCountryCode"         select="RowDeliveryDetails/RowManufacturerCountryCode"/>
								<xsl:with-param name="rowManufacturerCountryName"         select="RowDeliveryDetails/RowManufacturerCountryName"/>
								<xsl:with-param name="rowShortProposedAccountIdentifier"  select="RowShortProposedAccountIdentifier"/>
								<xsl:with-param name="rowNormalProposedAccountIdentifier" select="RowNormalProposedAccountIdentifier"/>
								<xsl:with-param name="rowAccountDimensionText"            select="RowAccountDimensionText"/>
							</xsl:call-template>
							<xsl:if test="count(RowFreeText) != 0">
								<tr id="invoiceRowStyle">
									<td colspan="8">
										<pre>
											<xsl:for-each select="RowFreeText">
													<xsl:value-of select="."/><br/>
											</xsl:for-each>
										</pre>
									</td>
								</tr>
							</xsl:if>
						</xsl:variable>
						<xsl:if test="string-length(normalize-space($invoiceRowHTML)) != 0">
							<xsl:copy-of select="$invoiceRowHTML"/>
						</xsl:if>
						<!-- Tulostetaan SubInvoiceRow. -->
						<xsl:if test="count(SubInvoiceRow) = 1">
							<xsl:call-template name="OutputRow">
								<xsl:with-param name="styleName"                          select="'subRowStyle'"/>
								<xsl:with-param name="addEmptyRow">
									<xsl:if test="(position() != 1) or (string-length(normalize-space($invoiceRowHTML)) != 0)">
										1
									</xsl:if>
								</xsl:with-param>
								<xsl:with-param name="articleIdentifier"                  select="SubInvoiceRow/SubArticleIdentifier"/>
								<xsl:with-param name="articleName"                        select="SubInvoiceRow/SubArticleName"/>
								<xsl:with-param name="articleInfoUrlText"                 select="SubInvoiceRow/SubArticleInfoUrlText"/>
								<xsl:with-param name="buyerArticleIdentifier"             select="SubInvoiceRow/SubBuyerArticleIdentifier"/>
								<xsl:with-param name="deliveredQuantity"                  select="SubInvoiceRow/SubDeliveredQuantity"/>
								<xsl:with-param name="deliveredQuantityUnitCode"          select="SubInvoiceRow/SubDeliveredQuantity/@QuantityUnitCode"/>
								<xsl:with-param name="orderedQuantity"                    select="SubInvoiceRow/SubOrderedQuantity"/>
								<xsl:with-param name="orderedQuantityUnitCode"            select="SubInvoiceRow/SubOrderedQuantity/@QuantityUnitCode"/>
								<xsl:with-param name="confirmedQuantity"                  select="SubInvoiceRow/SubConfirmedQuantity"/>
								<xsl:with-param name="confirmedQuantityUnitCode"          select="SubInvoiceRow/SubConfirmedQuantity/@QuantityUnitCode"/>
								<xsl:with-param name="startDate"                          select="SubInvoiceRow/SubStartDate"/>
								<xsl:with-param name="startDateFormat"                    select="SubInvoiceRow/SubStartDate/@Format"/>
								<xsl:with-param name="endDate"                            select="SubInvoiceRow/SubEndDate"/>
								<xsl:with-param name="endDateFormat"                      select="SubInvoiceRow/SubEndDate/@Format"/>
								<xsl:with-param name="unitPriceAmount"                    select="SubInvoiceRow/SubUnitPriceAmount"/>
								<xsl:with-param name="unitPriceUnitCode"                  select="SubInvoiceRow/SubUnitPriceAmount/@UnitPriceUnitCode"/>
								<xsl:with-param name="rowIdentifier"                      select="SubInvoiceRow/SubRowIdentifier"/>
								<xsl:with-param name="rowIdentifierUrlText"               select="SubInvoiceRow/SubRowIdentifierUrlText"/>
								<xsl:with-param name="rowIdentifierDate"                  select="SubInvoiceRow/SubRowIdentifierDate"/>
								<xsl:with-param name="rowIdentifierDateFormat"            select="SubInvoiceRow/SubRowIdentifierDate/@Format"/>
								<xsl:with-param name="rowDeliveryIdentifier"              select="SubInvoiceRow/SubRowDeliveryIdentifier"/>
								<xsl:with-param name="rowDeliveryUrlText"                 select="SubInvoiceRow/SubRowDeliveryUrlText"/>
								<xsl:with-param name="rowDeliveryDate"                    select="SubInvoiceRow/SubRowDeliveryDate"/>
								<xsl:with-param name="rowDeliveryDateFormat"              select="SubInvoiceRow/SubRowDeliveryDate/@Format"/>
								<xsl:with-param name="rowQuotationIdentifier"             select="SubInvoiceRow/SubRowQuotationIdentifier"/>
								<xsl:with-param name="rowQuotationIdentifierUrlText"      select="SubInvoiceRow/SubRowQuotationIdentifierUrlText"/>
								<xsl:with-param name="rowDiscountPercent"                 select="SubInvoiceRow/SubRowDiscountPercent"/>
								<xsl:with-param name="rowVatRatePercent"                  select="SubInvoiceRow/SubRowVatRatePercent"/>
								<xsl:with-param name="rowVatAmount"                       select="SubInvoiceRow/SubRowVatAmount"/>
								<xsl:with-param name="rowVatExcludedAmount"               select="SubInvoiceRow/SubRowVatExcludedAmount"/>
								<xsl:with-param name="rowAmount"                          select="SubInvoiceRow/SubRowAmount"/>
								<xsl:with-param name="rowTerminalAddressText"             select="SubInvoiceRow/SubRowDeliveryDetails/SubRowTerminalAddressText"/>
								<xsl:with-param name="rowWaybillIdentifier"               select="SubInvoiceRow/SubRowDeliveryDetails/SubRowWaybillIdentifier"/>
								<xsl:with-param name="rowWaybillMakerText"                select="SubInvoiceRow/SubRowDeliveryDetails/SubRowWaybillMakerText"/>
								<xsl:with-param name="rowAgreementIdentifier"             select="SubInvoiceRow/SubRowAgreementIdentifier"/>
								<xsl:with-param name="rowAgreementIdentifierUrlText"      select="SubInvoiceRow/SubRowAgreementIdentifierUrlText"/>
								<xsl:with-param name="originalInvoiceNumber"              select="SubInvoiceRow/SubOriginalInvoiceNumber"/>
								<xsl:with-param name="rowPriceListIdentifier"             select="SubInvoiceRow/SubRowPriceListIdentifier"/>
								<xsl:with-param name="rowPriceListIdentifierUrlText"      select="SubInvoiceRow/SubRowPriceListIdentifierUrlText"/>
								<xsl:with-param name="rowRequestOfQuotationIdentifier"        select="SubInvoiceRow/SubRowRequestOfQuotationIdentifier"/>
								<xsl:with-param name="rowRequestOfQuotationIdentifierUrlText" select="SubInvoiceRow/SubRowRequestOfQuotationIdentifierUrlText"/>
								<xsl:with-param name="rowDelivererName1"                  select="SubInvoiceRow/SubRowDeliveryDetails/SubRowDelivererName[1]"/>
								<xsl:with-param name="rowDelivererName2"                  select="SubInvoiceRow/SubRowDeliveryDetails/SubRowDelivererName[2]"/>
								<xsl:with-param name="rowDelivererName3"                  select="SubInvoiceRow/SubRowDeliveryDetails/SubRowDelivererName[3]"/>
								<xsl:with-param name="rowDelivererIdentifier"             select="SubInvoiceRow/SubRowDeliveryDetails/SubRowDelivererIdentifier"/>
								<xsl:with-param name="rowDelivererCountryCode"            select="SubInvoiceRow/SubRowDeliveryDetails/SubRowDelivererCountryCode"/>
								<xsl:with-param name="rowDelivererCountryName"            select="SubInvoiceRow/SubRowDeliveryDetails/SubRowDelivererCountryName"/>
								<xsl:with-param name="rowManufacturerName1"               select="SubInvoiceRow/SubRowDeliveryDetails/SubRowManufacturerName[1]"/>
								<xsl:with-param name="rowManufacturerName2"               select="SubInvoiceRow/SubRowDeliveryDetails/SubRowManufacturerName[2]"/>
								<xsl:with-param name="rowManufacturerName3"               select="SubInvoiceRow/SubRowDeliveryDetails/SubRowManufacturerName[3]"/>
								<xsl:with-param name="rowManufacturerIdentifier"          select="SubInvoiceRow/SubRowDeliveryDetails/SubRowManufacturerIdentifier"/>
								<xsl:with-param name="rowManufacturerCountryCode"         select="SubInvoiceRow/SubRowDeliveryDetails/SubRowManufacturerCountryCode"/>
								<xsl:with-param name="rowManufacturerCountryName"         select="SubInvoiceRow/SubRowDeliveryDetails/SubRowManufacturerCountryName"/>
								<xsl:with-param name="rowShortProposedAccountIdentifier"  select="SubInvoiceRow/SubRowShortProposedAccountIdentifier"/>
								<xsl:with-param name="rowNormalProposedAccountIdentifier" select="SubInvoiceRow/SubRowNormalProposedAccountIdentifier"/>
								<xsl:with-param name="rowAccountDimensionText"            select="SubInvoiceRow/SubRowAccountDimensionText"/>
							</xsl:call-template>
							<xsl:for-each select="SubInvoiceRow/SubRowFreeText">
								<tr id="subRowStyle">
									<td colspan="8">
										<xsl:value-of select="."/>
									</td>
								</tr>
							</xsl:for-each>
						</xsl:if>
						<!-- SubInvoiceRow:n tulostus päättyi. -->
					</xsl:for-each>
				</tbody>
				</table>
				<!-- Tuoterivit on nyt tulostettu. -->
				<p align="right">
					<b><xsl:value-of select="$txtINVOICETOTAL"/>:<xsl:text> </xsl:text>
						<xsl:call-template name="OutputAmount">
							<xsl:with-param name="amount" select="InvoiceDetails/InvoiceTotalVatIncludedAmount"/>
							<xsl:with-param name="currency" select="InvoiceDetails/InvoiceTotalVatIncludedAmount/@AmountCurrencyIdentifier"/>
						</xsl:call-template>
					</b>
				</p>
				<!--
				<xsl:if test="string-length(InvoiceUrlText) != 0">
					<xsl:call-template name="FormatLink">
						<xsl:with-param name="link" select="InvoiceUrlText"/>
						<xsl:with-param name="text" select="InvoiceUrlNameText"/>
					</xsl:call-template>
					<br/>
				</xsl:if>
				-->
				<xsl:for-each select="InvoiceUrlText">
					<xsl:call-template name="OutputLinkAndText">
						<xsl:with-param name="pos" select="position()"/>
					</xsl:call-template>
					<br/>
				</xsl:for-each>
				<br/>
				<br/>
				<!-- Tulostetaan alv-erittely, jos sellainen laskulta löytyy. -->
				<xsl:choose>
					<xsl:when test="count(//InvoiceDetails/VatSpecificationDetails) != 0">
						<p>
							<table width="90%" cellpadding="0" cellspacing="0">
								<tr id="invoiceStyle" align="left">
									<td width="60%"/>
									<td>
										<b><xsl:value-of select="$txtVatSpecification"/>:</b>
									</td>
									<td width="1%"/>
									<td/>
								</tr>
								<xsl:if test="string-length(InvoiceDetails/InvoiceTotalVatExcludedAmount) != 0">
									<tr id="invoiceStyle" align="left">
										<td width="60%"/>
										<td>
											<xsl:value-of select="$txtVatExcludedAmount"/>:
										</td>
										<td width="1%"/>
										<td align="right">
											<xsl:value-of select="InvoiceDetails/InvoiceTotalVatExcludedAmount"/>
										</td>
									</tr>
								</xsl:if>
								<xsl:for-each select="InvoiceDetails/VatSpecificationDetails">
									<xsl:if test="(string-length(VatRatePercent) != 0) or (string-length(VatRateAmount) != 0)">
										<tr id="invoiceStyle" align="left">
											<td width="60%"/>
											<td>
												<xsl:value-of select="$txtVat"/><xsl:text> </xsl:text>
												<xsl:value-of select="VatRatePercent"/>
												<xsl:text> %:</xsl:text>
											</td>
											<td width="1%"/>
											<td align="right">
												<xsl:value-of select="VatRateAmount"/>
											</td>
										</tr>
									</xsl:if>
									<xsl:if test="string-length(VatFreeText) != 0">
										<tr id="invoiceStyle" align="left">
											<td width="60%"/>
											<td>
												<xsl:value-of select="VatFreeText"/>
											</td>
											<td width="1%"/>
											<td align="right">
											</td>
										</tr>
									</xsl:if>
								</xsl:for-each>
							</table>
						</p>
					</xsl:when>
				</xsl:choose>
				<!-- Tulostetaan tiliöintiehdotukset ja kustannuspaikka, jos ne laskulta löytyvät. -->
				<xsl:if test="(string-length(InvoiceDetails/ShortProposedAccountIdentifier) != 0) or (string-length(InvoiceDetails/NormalProposedAccountIdentifier) != 0) or (string-length(InvoiceDetails/AccountDimensionText) != 0)">
					<p>
						<table width="90%" cellpadding="0" cellspacing="0">
							<tr id="invoiceStyle" align="left">
								<td width="60%"/>
								<td>
									<xsl:value-of select="$txtShortProposedAccountIdentifier"/>:
								</td>
								<td width="1%"/>
								<td align="right">
									<xsl:value-of select="InvoiceDetails/ShortProposedAccountIdentifier"/>
								</td>
							</tr>
							<tr id="invoiceStyle" align="left">
								<td width="60%"/>
								<td>
									<xsl:value-of select="$txtNormalProposedAccountIdentifier"/>:
								</td>
								<td width="1%"/>
								<td align="right">
									<xsl:value-of select="InvoiceDetails/NormalProposedAccountIdentifier"/>
								</td>
							</tr>
							<tr id="invoiceStyle" align="left">
								<td width="60%"/>
								<td>
									<xsl:value-of select="$txtAccountDimension"/>:
								</td>
								<td width="1%"/>
								<td align="right">
									<xsl:value-of select="InvoiceDetails/AccountDimensionText"/>
								</td>
							</tr>
						</table>
					</p>
				</xsl:if>
				<!-- Tulostetaan virtuaaliviivakoodi, jos sellainen laskulta löytyy. -->
				<xsl:if test="string-length(VirtualBankBarcode) != 0">
					<p>
						<center>
							<b><xsl:value-of select="$txtVirtualBankBarcode"/>:</b>
							<br/>
							<xsl:value-of select="VirtualBankBarcode"/>
						</center>
					</p>
				</xsl:if>
				<xsl:if test="count(DeliveryPartyDetails) + count(DeliveryDetails) + count(DeliveryContactPersonName) + count(DeliveryCommunicationDetails) + count(DeliveryOrganisationUnitNumber) != 0">
				<p>
				<!-- Tehdään neljän sarakkeen taulukko, johon tulostetaan mm. toimitustiedot. -->
				<b><xsl:value-of select="$txtDeliveryInfo"/></b>
				<table width="100%" cellpadding="0" cellspacing="0">
					<tr id="invoiceStyle" valign="top" align="left">
						<!-- Ensimmäinen sarake -->
						<td width="20%">
							<xsl:if test="string-length(normalize-space(DeliveryPartyDetails/DeliveryOrganisationName)) != 0">
								<xsl:value-of select="$txtDeliveryParty"/>:
								<xsl:for-each select="DeliveryPartyDetails/DeliveryOrganisationName">
									<xsl:if test="string-length(normalize-space(.)) != 0">
										<br/>
									</xsl:if>
								</xsl:for-each>
							</xsl:if>
							<xsl:call-template name="OutputDeliveryParty">
								<xsl:with-param name="rowNumber">2</xsl:with-param>
								<xsl:with-param name="isDataColumn">0</xsl:with-param>
								<xsl:with-param name="titleText">
									<xsl:if test="string-length(normalize-space(DeliveryPartyDetails/DeliveryOrganisationName)) = 0">
										<xsl:value-of select="$txtDeliveryParty"/>:
									</xsl:if>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:choose>
								<xsl:when test="string-length(DeliveryDetails/DeliveryDate) != 0">
									<xsl:value-of select="$txtDeliveryDate"/>:<br/>
								</xsl:when>
								<xsl:when test="string-length(DeliveryDetails/DeliveryPeriodDetails) != 0">
									<xsl:value-of select="$txtDeliveryPeriodDetails"/>:<br/>
									<br/>
									<!-- Jakso tulostetaan kahdelle riville, jotta sekunnitkin mahtuisivat. -->
								</xsl:when>
							</xsl:choose>
							<xsl:if test="string-length(DeliveryDetails/DeliveryMethodText) != 0">
								<xsl:value-of select="$txtDeliveryMethod"/>:<br/>
							</xsl:if>
							<xsl:if test="string-length(DeliveryDetails/DeliveryTermsText) != 0">
								<xsl:value-of select="$txtDeliveryTerms"/>:<br/>
							</xsl:if>
							<xsl:if test="string-length(DeliveryDetails/TerminalAddressText) != 0">
								<xsl:value-of select="$txtTerminalAddress"/>:<br/>
							</xsl:if>
							<xsl:if test="string-length(DeliveryDetails/WaybillIdentifier) != 0">
								<xsl:value-of select="$txtWaybillIdentifier"/>:<br/>
							</xsl:if>
							<!-- Tätä elementtiä ei ole vielä hyväksytty virallisesti.
							<xsl:if test="string-length(DeliveryDetails/WaybillMakerText) != 0">
								<xsl:value-of select="$txtWaybillMaker"/>:<br/>
							</xsl:if>
							-->
							<br/>
						</td>
						<!-- Toinen sarake -->
						<td width="30%">
							<xsl:if test="string-length(normalize-space(DeliveryPartyDetails/DeliveryOrganisationName)) != 0">
								<xsl:for-each select="DeliveryPartyDetails/DeliveryOrganisationName">
									<xsl:if test="string-length(normalize-space(.)) != 0">
										<xsl:value-of select="."/><br/>
									</xsl:if>
								</xsl:for-each>
							</xsl:if>
							<xsl:call-template name="OutputDeliveryParty">
								<xsl:with-param name="rowNumber">2</xsl:with-param>
								<xsl:with-param name="isDataColumn">1</xsl:with-param>
							</xsl:call-template>
							<xsl:choose>
								<xsl:when test="string-length(DeliveryDetails/DeliveryDate) != 0">
									<xsl:call-template name="OutputDate">
										<xsl:with-param name="theDate" select="DeliveryDetails/DeliveryDate"/>
										<xsl:with-param name="theFormat" select="DeliveryDetails/DeliveryDate/@Format"/>
									</xsl:call-template>
									<br/>
								</xsl:when>
								<xsl:when test="string-length(DeliveryDetails/DeliveryPeriodDetails) != 0">
									<xsl:call-template name="OutputDate">
										<xsl:with-param name="theDate" select="DeliveryDetails/DeliveryPeriodDetails/StartDate"/>
										<xsl:with-param name="theFormat" select="DeliveryDetails/DeliveryPeriodDetails/StartDate/@Format"/>
									</xsl:call-template>
									-<br/>
									<!-- Jakso tulostetaan kahdelle riville, jotta sekunnitkin mahtuisivat. -->
									<xsl:call-template name="OutputDate">
										<xsl:with-param name="theDate" select="DeliveryDetails/DeliveryPeriodDetails/EndDate"/>
										<xsl:with-param name="theFormat" select="DeliveryDetails/DeliveryPeriodDetails/EndDate/@Format"/>
									</xsl:call-template>
									<br/>
								</xsl:when>
							</xsl:choose>
							<xsl:if test="string-length(DeliveryDetails/DeliveryMethodText) != 0">
								<xsl:value-of select="DeliveryDetails/DeliveryMethodText"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(DeliveryDetails/DeliveryTermsText) != 0">
								<xsl:value-of select="DeliveryDetails/DeliveryTermsText"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(DeliveryDetails/TerminalAddressText) != 0">
								<xsl:value-of select="DeliveryDetails/TerminalAddressText"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(DeliveryDetails/WaybillIdentifier) != 0">
								<xsl:value-of select="DeliveryDetails/WaybillIdentifier"/>
								<br/>
							</xsl:if>
							<!-- Tätä elementtiä ei ole vielä hyväksytty virallisesti.
							<xsl:if test="string-length(DeliveryDetails/WaybillMakerText) != 0">
								<xsl:value-of select="DeliveryDetails/WaybillMakerText"/>
								<br/>
							</xsl:if>
							-->
							<br/>
						</td>
						<!-- Kolmas sarake -->
						<td width="20%">
							<xsl:if test="string-length(normalize-space(DeliveryDetails/DelivererName)) != 0">
								<xsl:value-of select="$txtDeliverer"/>:
								<xsl:for-each select="DeliveryDetails/DelivererName">
									<xsl:if test="string-length(normalize-space(.)) != 0">
										<br/>
									</xsl:if>
								</xsl:for-each>
							</xsl:if>
							<xsl:call-template name="OutputDeliverer">
								<xsl:with-param name="rowNumber">1</xsl:with-param>
								<xsl:with-param name="isDataColumn">0</xsl:with-param>
								<xsl:with-param name="titleText">
									<xsl:if test="string-length(normalize-space(DeliveryDetails/DelivererName)) = 0">
										<xsl:value-of select="$txtDeliverer"/>:
									</xsl:if>
								</xsl:with-param>
							</xsl:call-template>
							<xsl:if test="string-length(normalize-space(DeliveryDetails/ManufacturerName)) != 0">
								<xsl:value-of select="$txtManufacturer"/>:
								<xsl:for-each select="DeliveryDetails/ManufacturerName">
									<xsl:if test="string-length(normalize-space(.)) != 0">
										<br/>
									</xsl:if>
								</xsl:for-each>
							</xsl:if>
							<xsl:if test="string-length(normalize-space(DeliveryDetails/ManufacturerIdentifier)) != 0">
								<xsl:if test="string-length(normalize-space(DeliveryDetails/ManufacturerName)) = 0">
									<xsl:value-of select="$txtManufacturer"/>:
								</xsl:if>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(normalize-space(DeliveryDetails/ManufacturerCountryCode)) + string-length(normalize-space(DeliveryDetails/ManufacturerCountryName)) != 0">
								<xsl:if test="string-length(normalize-space(DeliveryDetails/ManufacturerName)) + string-length(normalize-space(DeliveryDetails/ManufacturerIdentifier)) = 0">
									<xsl:value-of select="$txtManufacturer"/>:
								</xsl:if>
								<br/>
							</xsl:if>
						</td>
						<!-- Neljäs sarake -->
						<td width="30%">
							<xsl:if test="string-length(normalize-space(DeliveryDetails/DelivererName)) != 0">
								<xsl:for-each select="DeliveryDetails/DelivererName">
									<xsl:if test="string-length(normalize-space(.)) != 0">
										<xsl:value-of select="."/><br/>
									</xsl:if>
								</xsl:for-each>
							</xsl:if>
							<xsl:call-template name="OutputDeliverer">
								<xsl:with-param name="rowNumber">1</xsl:with-param>
								<xsl:with-param name="isDataColumn">1</xsl:with-param>
							</xsl:call-template>
							<xsl:if test="string-length(normalize-space(DeliveryDetails/ManufacturerName)) != 0">
								<xsl:for-each select="DeliveryDetails/ManufacturerName">
									<xsl:if test="string-length(normalize-space(.)) != 0">
										<xsl:value-of select="."/><br/>
									</xsl:if>
								</xsl:for-each>
							</xsl:if>
							<xsl:if test="string-length(normalize-space(DeliveryDetails/ManufacturerIdentifier)) != 0">
								<xsl:value-of select="DeliveryDetails/ManufacturerIdentifier"/><br/>
							</xsl:if>
							<xsl:if test="string-length(normalize-space(DeliveryDetails/ManufacturerCountryCode)) + string-length(normalize-space(DeliveryDetails/ManufacturerCountryName)) != 0">
								<xsl:value-of select="DeliveryDetails/ManufacturerCountryCode"/>
								<xsl:if test="(string-length(normalize-space(DeliveryDetails/ManufacturerCountryCode)) != 0) and (string-length(normalize-space(DeliveryDetails/ManufacturerCountryName)) != 0)">
									<xsl:text> / </xsl:text>
								</xsl:if>
								<xsl:value-of select="DeliveryDetails/ManufacturerCountryName"/><br/>
							</xsl:if>
						</td>
						<!-- Neljäs sarake loppui -->
					</tr>
				</table>
				</p>
				</xsl:if>
				<xsl:if test="count(SpecificationDetails/SpecificationFreeText) != 0">
					<pre id="preStyle">
						<xsl:for-each select="SpecificationDetails/SpecificationFreeText">
							<xsl:value-of select="."/><br/>
						</xsl:for-each>
					</pre>
				</xsl:if>
				<p>
				<!-- Sitten tulostetaan mahdolliset osamaksutiedot. -->
				<xsl:for-each select="PartialPaymentDetails">
					<table width="100%" cellpadding="0" cellspacing="0">
						<xsl:if test="string-length(PaidAmount)+string-length(UnPaidAmount)+string-length(InterestPercent)+string-length(ProsessingCostsAmount) != 0">
							<tr id="invoiceStyle" align="left">
								<td>
									<b><xsl:value-of select="$txtPartialPaymentDetails"/>:</b>
								</td>
							</tr>
						</xsl:if>
						<xsl:choose>
							<xsl:when test="string-length(PaidAmount) != 0">
								<td>
									<xsl:value-of select="$txtPaidAmount"/><xsl:text>: </xsl:text>
									<xsl:value-of select="PaidAmount"/>
								</td>
							</xsl:when>
							<xsl:otherwise>
								<td/>
							</xsl:otherwise>
						</xsl:choose>
						<xsl:choose>
							<xsl:when test="string-length(UnPaidAmount) != 0">
								<td align="right">
									<xsl:value-of select="$txtUnPaidAmount"/><xsl:text>: </xsl:text>
									<xsl:value-of select="UnPaidAmount"/>
								</td>
							</xsl:when>
							<xsl:otherwise>
								<td/>
							</xsl:otherwise>
						</xsl:choose>
						<xsl:choose>
							<xsl:when test="string-length(InterestPercent) != 0">
								<td align="right">
									<xsl:value-of select="$txtInterestPercent"/><xsl:text>: </xsl:text>
									<xsl:value-of select="InterestPercent"/>
								</td>
							</xsl:when>
							<xsl:otherwise>
								<td/>
							</xsl:otherwise>
						</xsl:choose>
						<xsl:choose>
							<xsl:when test="string-length(ProsessingCostsAmount) != 0">
								<td align="right">
									<xsl:value-of select="$txtProsessingCostsAmount"/><xsl:text>: </xsl:text>
									<xsl:value-of select="ProsessingCostsAmount"/>
								</td>
							</xsl:when>
							<xsl:otherwise>
								<td/>
							</xsl:otherwise>
						</xsl:choose>
						<td width="30%">
							<br/>
						</td>
						<tr id="invoiceStyle" align="left">
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
						<tr id="invoiceStyle" align="left">
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
					</table>
				</xsl:for-each>
				<!-- Loppuun tulostetaan myyjän yhteys- ja muita tietoja. -->
				<table width="100%" cellpadding="0" cellspacing="0">
					<tr id="sellerDetailsStyle">
						<td colspan="3">
							<hr/>
						</td>
					</tr>
					<tr id="sellerDetailsStyle" align="left" valign="top">
						<td>
							<xsl:value-of select="SellerPartyDetails/SellerOrganisationName"/>
							<br/>
							<xsl:if test="string-length(SellerPartyDetails/SellerPostalAddressDetails/SellerStreetName) != 0">
								<xsl:value-of select="SellerPartyDetails/SellerPostalAddressDetails/SellerStreetName"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(SellerPartyDetails/SellerPostalAddressDetails/SellerPostCodeIdentifier) + string-length(SellerPartyDetails/SellerPostalAddressDetails/SellerTownName) != 0">
								<xsl:value-of select="SellerPartyDetails/SellerPostalAddressDetails/SellerPostCodeIdentifier"/>
								<xsl:text> </xsl:text>
								<xsl:value-of select="SellerPartyDetails/SellerPostalAddressDetails/SellerTownName"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(SellerPartyDetails/SellerPostalAddressDetails/CountryName) != 0">
								<xsl:value-of select="SellerPartyDetails/SellerPostalAddressDetails/CountryName"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(SellerInformationDetails/SellerFreeText) != 0">
								<xsl:value-of select="SellerInformationDetails/SellerFreeText"/>
							</xsl:if>
						</td>
						<td>
							<xsl:if test="string-length(SellerInformationDetails/SellerPhoneNumber) != 0">
								<xsl:value-of select="$txtPhoneNumber"/><xsl:text>: </xsl:text>
								<xsl:value-of select="SellerInformationDetails/SellerPhoneNumber"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(SellerInformationDetails/SellerFaxNumber) != 0">
								<xsl:value-of select="$txtFaxNumber"/><xsl:text>: </xsl:text>
								<xsl:value-of select="SellerInformationDetails/SellerFaxNumber"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(SellerInformationDetails/SellerWebaddressIdentifier) != 0">
								<xsl:value-of select="$txtWebaddressIdentifier"/><xsl:text>: </xsl:text>
								<xsl:call-template name="FormatLink">
									<xsl:with-param name="link" select="SellerInformationDetails/SellerWebaddressIdentifier"/>
									<xsl:with-param name="text" select="SellerInformationDetails/SellerWebaddressIdentifier"/>
								</xsl:call-template>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(SellerInformationDetails/SellerCommonEmailaddressIdentifier) != 0">
								<xsl:value-of select="$txtEmailaddressIdentifier"/><xsl:text>: </xsl:text>
								<xsl:call-template name="FormatEmail">
									<xsl:with-param name="email" select="SellerInformationDetails/SellerCommonEmailaddressIdentifier"/>
								</xsl:call-template>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(SellerInformationDetails/InvoiceRecipientDetails/InvoiceRecipientAddress) != 0">
								<xsl:for-each select="SellerInformationDetails/InvoiceRecipientDetails">
									<xsl:value-of select="$txtInvoiceRecipientAddress"/><xsl:text>: </xsl:text>
									<xsl:value-of select="InvoiceRecipientAddress"/><xsl:text> / </xsl:text>
									<xsl:value-of select="InvoiceRecipientIntermediatorAddress"/>
									<br/>
								</xsl:for-each>
							</xsl:if>
						</td>
						<td>
							<xsl:if test="string-length(SellerInformationDetails/SellerHomeTownName) != 0">
								<xsl:value-of select="$txtHomeTownName"/><xsl:text>: </xsl:text>
								<xsl:value-of select="SellerInformationDetails/SellerHomeTownName"/>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(SellerPartyDetails/SellerOrganisationTaxCode) != 0">
								<xsl:value-of select="$txtTaxCode"/><xsl:text>: </xsl:text>
								<xsl:choose>
									<xsl:when test="string-length(SellerPartyDetails/SellerOrganisationTaxCodeUrlText) != 0">  
										<xsl:call-template name="FormatLink">
											<xsl:with-param name="link" select="SellerPartyDetails/SellerOrganisationTaxCodeUrlText"/>
											<xsl:with-param name="text" select="SellerPartyDetails/SellerOrganisationTaxCode"/>
										</xsl:call-template>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="SellerPartyDetails/SellerOrganisationTaxCode"/>
									</xsl:otherwise>
								</xsl:choose>
								<br/>
							</xsl:if>
							<xsl:if test="string-length(SellerInformationDetails/SellerVatRegistrationText) + string-length(SellerInformationDetails/SellerVatRegistrationDate) != 0">
								<xsl:value-of select="SellerInformationDetails/SellerVatRegistrationText"/>
								<xsl:text> </xsl:text>
								<xsl:call-template name="OutputDate">
									<xsl:with-param name="theDate" select="SellerInformationDetails/SellerVatRegistrationDate"/>
									<xsl:with-param name="theFormat" select="SellerInformationDetails/SellerVatRegistrationDate/@Format"/>
								</xsl:call-template>
								<br/>
							</xsl:if>
							<xsl:for-each select="SellerInformationDetails/SellerAccountDetails">
								<xsl:call-template name="OutputEpiAccountID">
									<xsl:with-param name="scheme" select="SellerAccountID/@IdentificationSchemeName"/>
									<xsl:with-param name="account" select="SellerAccountID"/>
								</xsl:call-template>
								<xsl:text> / </xsl:text>
								<xsl:value-of select="SellerBic"/>
								<br/>
							</xsl:for-each>
						</td>
					</tr>
				</table>
				</p>
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

	<!-- Template, joka valitsee tulostettavat laskun toimitustiedot. -->
	<xsl:template name="OutputDeliveryParty">
		<xsl:param name="rowNumber"/>
		<xsl:param name="isDataColumn"/>
		<xsl:param name="titleText"/>
		<xsl:if test="$rowNumber &lt;= 9"> <!-- Muista päivittää tähän iffiin suurimman sallitun rivin numero. -->
			<xsl:call-template name="OutputDeliveryParty2">
				<xsl:with-param name="elementText">
					<xsl:choose>
						<xsl:when test="$rowNumber = 1">
							<xsl:value-of select="DeliveryPartyDetails/DeliveryOrganisationName"/>
						</xsl:when>
						<xsl:when test="$rowNumber = 2">
							<xsl:value-of select="DeliveryPartyDetails/DeliveryPostalAddressDetails/DeliveryPostofficeBoxIdentifier"/>
						</xsl:when>
						<xsl:when test="$rowNumber = 3">
							<xsl:value-of select="DeliveryPartyDetails/DeliveryPostalAddressDetails/DeliveryStreetName"/>
						</xsl:when>
						<xsl:when test="$rowNumber = 4">
							<xsl:value-of select="DeliveryPartyDetails/DeliveryPostalAddressDetails/DeliveryPostCodeIdentifier"/>
							<xsl:text> </xsl:text>
							<xsl:value-of select="DeliveryPartyDetails/DeliveryPostalAddressDetails/DeliveryTownName"/>
						</xsl:when>
						<xsl:when test="$rowNumber = 5">
							<xsl:value-of select="DeliveryContactPersonName"/>
						</xsl:when>
						<xsl:when test="$rowNumber = 6">
							<xsl:value-of select="DeliveryCommunicationDetails/DeliveryEmailaddressIdentifier"/>
						</xsl:when>
						<xsl:when test="$rowNumber = 7">
							<xsl:value-of select="DeliveryCommunicationDetails/DeliveryPhoneNumberIdentifier"/>
						</xsl:when>
						<xsl:when test="$rowNumber = 8">
							<xsl:value-of select="DeliveryPartyDetails/DeliveryPartyIdentifier"/>
						</xsl:when>
						<xsl:when test="$rowNumber = 9">
							<xsl:value-of select="DeliveryOrganisationUnitNumber"/>
						</xsl:when>
						<!-- Muista päivittää iffiin suurimman sallitun rivin numero. -->
					</xsl:choose>
				</xsl:with-param>
				<xsl:with-param name="rowNumber" select="$rowNumber"/>
				<xsl:with-param name="isDataColumn" select="$isDataColumn"/>
				<xsl:with-param name="titleText" select="$titleText"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

	<!-- Template, joka tulostaa laskun toimitustietoja. -->
	<!-- Otsake titleText putoaa pois, kun se on yhden kerran tulostettu. -->
	<xsl:template name="OutputDeliveryParty2">
		<xsl:param name="elementText"/>
		<xsl:param name="rowNumber"/>
		<xsl:param name="isDataColumn"/>
		<xsl:param name="titleText"/>
		<xsl:choose>
			<xsl:when test="string-length(normalize-space($elementText)) != 0">
				<xsl:choose>
					<xsl:when test="$isDataColumn = 1">
						<xsl:value-of select="$elementText"/>
					</xsl:when>
					<xsl:when test="string-length($titleText) != 0">
						<xsl:value-of select="$titleText"/>
					</xsl:when>
				</xsl:choose>
				<br/>
				<xsl:call-template name="OutputDeliveryParty">
					<xsl:with-param name="rowNumber" select="$rowNumber + 1"/>
					<xsl:with-param name="isDataColumn" select="$isDataColumn"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="OutputDeliveryParty">
					<xsl:with-param name="rowNumber" select="$rowNumber + 1"/>
					<xsl:with-param name="isDataColumn" select="$isDataColumn"/>
					<xsl:with-param name="titleText" select="$titleText"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Template, joka valitsee tulostettavat toimittajatiedot. -->
	<xsl:template name="OutputDeliverer">
		<xsl:param name="rowNumber"/>
		<xsl:param name="isDataColumn"/>
		<xsl:param name="titleText"/>
		<xsl:if test="$rowNumber &lt;= 2"> <!-- Muista päivittää tähän iffiin suurimman sallitun rivin numero. -->
			<xsl:call-template name="OutputDeliverer2">
				<xsl:with-param name="elementText">
					<xsl:choose>
						<xsl:when test="$rowNumber = 1">
							<xsl:value-of select="DeliveryDetails/DelivererIdentifier"/>
						</xsl:when>
						<xsl:when test="($rowNumber = 2) and (string-length(normalize-space(DeliveryDetails/DelivererCountryCode)) + string-length(normalize-space(DeliveryDetails/DelivererCountryName)) != 0)">
							<xsl:value-of select="DeliveryDetails/DelivererCountryCode"/>
							<xsl:if test="(string-length(normalize-space(DeliveryDetails/DelivererCountryCode)) != 0) and (string-length(normalize-space(DeliveryDetails/DelivererCountryName)) != 0)">
								<xsl:text> / </xsl:text>
							</xsl:if>
							<xsl:value-of select="DeliveryDetails/DelivererCountryName"/>
						</xsl:when>
						<!-- Muista päivittää iffiin suurimman sallitun rivin numero. -->
					</xsl:choose>
				</xsl:with-param>
				<xsl:with-param name="rowNumber" select="$rowNumber"/>
				<xsl:with-param name="isDataColumn" select="$isDataColumn"/>
				<xsl:with-param name="titleText" select="$titleText"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

	<!-- Template, joka tulostaa toimittajatietoja. -->
	<!-- Otsake titleText putoaa pois, kun se on yhden kerran tulostettu. -->
	<xsl:template name="OutputDeliverer2">
		<xsl:param name="elementText"/>
		<xsl:param name="rowNumber"/>
		<xsl:param name="isDataColumn"/>
		<xsl:param name="titleText"/>
		<xsl:choose>
			<xsl:when test="string-length(normalize-space($elementText)) != 0">
				<xsl:choose>
					<xsl:when test="$isDataColumn = 1">
						<xsl:value-of select="$elementText"/>
					</xsl:when>
					<xsl:when test="string-length($titleText) != 0">
						<xsl:value-of select="$titleText"/>
					</xsl:when>
				</xsl:choose>
				<br/>
				<xsl:call-template name="OutputDeliverer">
					<xsl:with-param name="rowNumber" select="$rowNumber + 1"/>
					<xsl:with-param name="isDataColumn" select="$isDataColumn"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="OutputDeliverer">
					<xsl:with-param name="rowNumber" select="$rowNumber + 1"/>
					<xsl:with-param name="isDataColumn" select="$isDataColumn"/>
					<xsl:with-param name="titleText" select="$titleText"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
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
				<xsl:if test="string-length($theStartDate) != 0">
					<xsl:call-template name="OutputDate">
						<xsl:with-param name="theDate" select="$theStartDate"/>
						<xsl:with-param name="theFormat" select="$theStartDateFormat"/>
					</xsl:call-template>
				</xsl:if>
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
		<xsl:element name="a">
			<xsl:attribute name="href"><xsl:value-of select="$email_a"/></xsl:attribute>
			<xsl:value-of select="$email_d"/>
		</xsl:element>
	</xsl:template>
	<!-- Template, joka osaa muokata webbiosoitetta -->
	<xsl:template name="FormatLink">
		<xsl:param name="link"/>
		<xsl:param name="text"/>
		<xsl:variable name="link_lc" select="translate($link,'HTTPS','https')"/>
		<xsl:choose>
			<xsl:when test="(starts-with($link_lc,'http:')=true() or
		                    starts-with($link_lc,'https:')=true())">
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
		<xsl:param name="deliveredQuantity"/>
		<xsl:param name="deliveredQuantityUnitCode"/>
		<xsl:param name="orderedQuantity"/>
		<xsl:param name="orderedQuantityUnitCode"/>
      <xsl:param name="confirmedQuantity"/>
      <xsl:param name="confirmedQuantityUnitCode"/>
		<xsl:param name="startDate"/>
		<xsl:param name="startDateFormat"/>
		<xsl:param name="endDate"/>
		<xsl:param name="endDateFormat"/>
		<xsl:param name="unitPriceAmount"/>
		<xsl:param name="unitPriceUnitCode"/>
		<xsl:param name="rowIdentifier"/>
		<xsl:param name="rowIdentifierUrlText"/>
		<xsl:param name="rowIdentifierDate"/>
		<xsl:param name="rowIdentifierDateFormat"/>
		<xsl:param name="rowDeliveryIdentifier"/>
		<xsl:param name="rowDeliveryUrlText"/>
		<xsl:param name="rowDeliveryDate"/>
		<xsl:param name="rowDeliveryDateFormat"/>
      <xsl:param name="rowQuotationIdentifier"/>
		<xsl:param name="rowQuotationIdentifierUrlText"/>
      <xsl:param name="rowDiscountPercent"/>
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
		<xsl:param name="rowAccountDimensionText"/>
		<xsl:if test="string-length($addEmptyRow) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id">invoiceRowStyle</xsl:attribute>
				<td colspan="8">
					<br/>
				</td>
			</xsl:element>
		</xsl:if>
		<xsl:variable name="RowPartHTML">
		<xsl:if test="$useColHdrRow1 + $useColHdrRow2 + $useColHdrRow3 != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<xsl:if test="$useRowCol1 != 0">
					<td>
						<xsl:if test="$foundArticleName != 0">
								<xsl:choose>
									<xsl:when test="string-length($articleInfoUrlText) != 0">
										<xsl:element name="a">
											<xsl:attribute name="href"><xsl:value-of select="$articleInfoUrlText"/></xsl:attribute>
											<xsl:attribute name="target"><xsl:text>_blank</xsl:text></xsl:attribute>
											<xsl:value-of select="$articleName"/>
										</xsl:element>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="$articleName"/>
									</xsl:otherwise>
								</xsl:choose>
								<br/>
						</xsl:if>
						<xsl:if test="$foundRowIdentifier != 0">
							<xsl:choose>
								<xsl:when test="string-length($rowIdentifierUrlText) != 0">  
									<xsl:call-template name="FormatLink">
										<xsl:with-param name="link" select="$rowIdentifierUrlText"/>
										<xsl:with-param name="text" select="$rowIdentifier"/>
									</xsl:call-template>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="$rowIdentifier"/>
								</xsl:otherwise>
							</xsl:choose>
							<br/>
						</xsl:if>
						<xsl:if test="$foundRowIdentifierDate != 0">
							<xsl:if test="string-length($rowIdentifierDate) != 0">
								<xsl:call-template name="OutputDate">
									<xsl:with-param name="theDate" select="$rowIdentifierDate"/>
									<xsl:with-param name="theFormat" select="$rowIdentifierDateFormat"/>
								</xsl:call-template>
							</xsl:if>
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
							<xsl:choose>
								<xsl:when test="string-length($rowQuotationIdentifierUrlText) != 0">  
									<xsl:call-template name="FormatLink">
										<xsl:with-param name="link" select="$rowQuotationIdentifierUrlText"/>
										<xsl:with-param name="text" select="$rowQuotationIdentifier"/>
									</xsl:call-template>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="$rowQuotationIdentifier"/>
								</xsl:otherwise>
							</xsl:choose>
							<br/>
						</xsl:if>
					</td>
				</xsl:if>
				<xsl:if test="$useRowCol3 != 0">
					<td>
						<xsl:if test="$foundDeliveredQuantity != 0">
							<xsl:value-of select="$deliveredQuantity"/>
							<xsl:text> </xsl:text>
							<xsl:value-of select="$deliveredQuantityUnitCode"/>
							<br/>
						</xsl:if>
						<xsl:if test="$foundOrderedQuantity != 0">
							<xsl:value-of select="$orderedQuantity"/>
							<xsl:text> </xsl:text>
							<xsl:value-of select="$orderedQuantityUnitCode"/>
							<br/>
						</xsl:if>
						<xsl:if test="$foundConfirmedQuantity != 0">
							<xsl:value-of select="$confirmedQuantity"/>
							<xsl:text> </xsl:text>
							<xsl:value-of select="$confirmedQuantityUnitCode"/>
							<br/>
						</xsl:if>
					</td>
				</xsl:if>
				<xsl:if test="$useRowCol4 != 0">
					<td>
						<xsl:if test="$foundUnitPriceAmount != 0">
							<xsl:value-of select="$unitPriceAmount"/>
							<xsl:text> </xsl:text>
							<xsl:value-of select="$unitPriceUnitCode"/>
							<br/>
						</xsl:if>
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
							<xsl:choose>
								<xsl:when test="string-length($rowDeliveryUrlText) != 0">  
									<xsl:call-template name="FormatLink">
										<xsl:with-param name="link" select="$rowDeliveryUrlText"/>
										<xsl:with-param name="text" select="$rowDeliveryIdentifier"/>
									</xsl:call-template>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="$rowDeliveryIdentifier"/>
								</xsl:otherwise>
							</xsl:choose>
							<br/>
						</xsl:if>
					</td>
				</xsl:if>
				<xsl:if test="$useRowCol5 != 0">
					<td align="right">
						<xsl:value-of select="$rowDiscountPercent"/>
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
							<xsl:value-of select="$rowVatAmount"/>
							<br/>
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
		<xsl:if test="string-length($rowTerminalAddressText) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<b><xsl:value-of select="$txtTerminalAddress"/>:</b>
				</td>
				<td colspan="7">
					<xsl:value-of select="$rowTerminalAddressText"/>
				</td>
			</xsl:element>
		</xsl:if>
		<xsl:if test="string-length($rowWaybillIdentifier) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<b><xsl:value-of select="$txtWaybillIdentifier"/>:</b>
				</td>
				<td colspan="7">
					<xsl:value-of select="$rowWaybillIdentifier"/>
				</td>
			</xsl:element>
		</xsl:if>
		<!-- Tätä elementtiä ei ole vielä hyväksytty virallisesti.
		<xsl:if test="string-length($rowWaybillMakerText) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<b><xsl:value-of select="$txtWaybillMaker"/>:</b>
				</td>
				<td colspan="7">
					<xsl:value-of select="$rowWaybillMakerText"/>
				</td>
			</xsl:element>
		</xsl:if>
		-->
		<xsl:if test="string-length($rowAgreementIdentifier) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<b><xsl:value-of select="$txtAgreementIdentifier"/>:</b>
				</td>
				<td colspan="7">
					<xsl:choose>
						<xsl:when test="string-length($rowAgreementIdentifierUrlText) != 0">  
							<xsl:call-template name="FormatLink">
								<xsl:with-param name="link" select="$rowAgreementIdentifierUrlText"/>
								<xsl:with-param name="text" select="$rowAgreementIdentifier"/>
							</xsl:call-template>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="$rowAgreementIdentifier"/>
						</xsl:otherwise>
					</xsl:choose>
				</td>
			</xsl:element>
		</xsl:if>
		<xsl:if test="string-length($originalInvoiceNumber) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<b><xsl:value-of select="$txtOriginalInvoice"/>:</b>
				</td>
				<td colspan="7">
					<xsl:value-of select="$originalInvoiceNumber"/>
				</td>
			</xsl:element>
		</xsl:if>
		<xsl:if test="string-length($rowPriceListIdentifier) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<b><xsl:value-of select="$txtPriceListIdentifier"/>:</b>
				</td>
				<td colspan="7">
					<xsl:choose>
						<xsl:when test="string-length($rowPriceListIdentifierUrlText) != 0">  
							<xsl:call-template name="FormatLink">
								<xsl:with-param name="link" select="$rowPriceListIdentifierUrlText"/>
								<xsl:with-param name="text" select="$rowPriceListIdentifier"/>
							</xsl:call-template>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="$rowPriceListIdentifier"/>
						</xsl:otherwise>
					</xsl:choose>
				</td>
			</xsl:element>
		</xsl:if>
		<xsl:if test="string-length($rowRequestOfQuotationIdentifier) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<b><xsl:value-of select="$txtRequestOfQuotationIdentifier"/>:</b>
				</td>
				<td colspan="7">
					<xsl:choose>
						<xsl:when test="string-length($rowRequestOfQuotationIdentifierUrlText) != 0">  
							<xsl:call-template name="FormatLink">
								<xsl:with-param name="link" select="$rowRequestOfQuotationIdentifierUrlText"/>
								<xsl:with-param name="text" select="$rowRequestOfQuotationIdentifier"/>
							</xsl:call-template>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="$rowRequestOfQuotationIdentifier"/>
						</xsl:otherwise>
					</xsl:choose>
				</td>
			</xsl:element>
		</xsl:if>
		<xsl:if test="string-length($rowDelivererName1) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<b><xsl:value-of select="$txtDeliverer"/>: </b>
				</td>
				<td colspan="7">
					<xsl:value-of select="$rowDelivererName1"/>
					<xsl:if test="string-length($rowDelivererName2) != 0">
						<br/><xsl:value-of select="$rowDelivererName2"/>
					</xsl:if>
					<xsl:if test="string-length($rowDelivererName3) != 0">
						<br/><xsl:value-of select="$rowDelivererName3"/>
					</xsl:if>
				</td>
			</xsl:element>
		</xsl:if>
		<xsl:if test="string-length($rowDelivererIdentifier) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<xsl:if test="string-length($rowDelivererName1) + string-length($rowDelivererName2) + string-length($rowDelivererName3)= 0">
						<b><xsl:value-of select="$txtDeliverer"/>:</b>
					</xsl:if>
				</td>
				<td colspan="7">
					<xsl:value-of select="$rowDelivererIdentifier"/>
				</td>
			</xsl:element>
		</xsl:if>
		<xsl:if test="string-length($rowDelivererCountryCode) + string-length($rowDelivererCountryName) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<xsl:if test="string-length($rowDelivererName1) + string-length($rowDelivererName2) + string-length($rowDelivererName3) + string-length($rowDelivererIdentifier) = 0">
						<b><xsl:value-of select="$txtDeliverer"/>:</b>
					</xsl:if>
				</td>
				<td colspan="7">
					<xsl:value-of select="$rowDelivererCountryCode"/>
					<xsl:if test="(string-length(normalize-space($rowDelivererCountryCode)) != 0) and (string-length(normalize-space($rowDelivererCountryName)) != 0)">
						<xsl:text> / </xsl:text>
					</xsl:if>
					<xsl:value-of select="$rowDelivererCountryName"/>
				</td>
			</xsl:element>
		</xsl:if>
		<xsl:if test="string-length($rowManufacturerName1) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<b><xsl:value-of select="$txtManufacturer"/>: </b>
				</td>
				<td colspan="7">
					<xsl:value-of select="$rowManufacturerName1"/>
					<xsl:if test="string-length($rowManufacturerName2) != 0">
						<br/><xsl:value-of select="$rowManufacturerName2"/>
					</xsl:if>
					<xsl:if test="string-length($rowManufacturerName3) != 0">
						<br/><xsl:value-of select="$rowManufacturerName3"/>
					</xsl:if>
				</td>
			</xsl:element>
		</xsl:if>
		<xsl:if test="string-length($rowManufacturerIdentifier) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<xsl:if test="string-length($rowManufacturerName1) + string-length($rowManufacturerName2) + string-length($rowManufacturerName3)= 0">
						<b><xsl:value-of select="$txtManufacturer"/>:</b>
					</xsl:if>
				</td>
				<td colspan="7">
					<xsl:value-of select="$rowManufacturerIdentifier"/>
				</td>
			</xsl:element>
		</xsl:if>
		<xsl:if test="string-length($rowManufacturerCountryCode) + string-length($rowManufacturerCountryName) != 0">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<xsl:if test="string-length($rowManufacturerName1) + string-length($rowManufacturerName2) + string-length($rowManufacturerName3) = 0">
						<b><xsl:value-of select="$txtManufacturer"/>:</b>
					</xsl:if>
				</td>
				<td colspan="7">
					<xsl:value-of select="$rowManufacturerCountryCode"/>
					<xsl:if test="(string-length(normalize-space($rowManufacturerCountryCode)) != 0) and (string-length(normalize-space($rowManufacturerCountryName)) != 0)">
						<xsl:text> / </xsl:text>
					</xsl:if>
					<xsl:value-of select="$rowManufacturerCountryName"/>
				</td>
			</xsl:element>
		</xsl:if>
		<xsl:if test="(string-length($rowShortProposedAccountIdentifier) != 0) or (string-length($rowNormalProposedAccountIdentifier) != 0) or (string-length($rowAccountDimensionText) != 0)">
			<xsl:element name="tr">
			<xsl:attribute name="id"><xsl:value-of select="$styleName"/></xsl:attribute>
			<xsl:attribute name="valign">top</xsl:attribute>
				<td>
					<b><xsl:value-of select="$txtProposedAccountIdentifier"/>:</b>
				</td>
				<td colspan="7">
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
				</td>
			</xsl:element>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>
