<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="/">
        <html>
            <head>
                <title>Invoice</title>
                 <style type="text/css">
                    html {
                        background-color: #eeeeee;
                        }
                    table {
                        background: #cccccc;

                    }
                    table th {
                        font-style: bold;
                        text-align: left;
                        background-color: #cccccc;
                    }
                    table td {
                        background-color: #ffffff;
                        padding: 0px;
                        margin: 0px;
                    }

                    .hilight {
                        color: red;
                    }
                </style>
            </head>
            <body>
                <h1>Invoice</h1>
                <xsl:apply-templates select="Invoice/InvoiceHeader"/>
                <br />
                <table>
                    <tbody>
                        <tr>
                            <th>Row no.</th>
                            <th>Product<br />(Maker product no. / EAN / Designation)</th>
                            <th>Quantity</th>
                            <th>Item Price</th>
                            <th>Total</th>
                        </tr>
                        
                        <xsl:apply-templates select="Invoice/InvoiceDetail"/>

                    </tbody>
                </table>
                <br />

                <xsl:apply-templates select="Invoice/InvoiceSummary"/>

            </body>
        </html>
    </xsl:template>

    <xsl:template match="Invoice/InvoiceHeader">
        <table>
            <tbody>
                <tr>
                    <th>Document number:</th>
                    <td><xsl:value-of select="InvoiceId"/></td>
                    <th>Issue Date:</th>
                    <td>
                        <xsl:call-template name="formatdate">
                            <xsl:with-param name="datestr" select="InvoiceIssueDate/Date"/>
                        </xsl:call-template>
                    </td>
                </tr>

                <xsl:apply-templates select="DesAdvRef"/>

                <xsl:if test="OrderRef">
                    <tr>
                        <th>Order References:</th>
                        <td>Purchaser Order ID</td>
                        <td>Supplier Order ID</td>
                        <td>Order Date</td>
                    </tr>
                </xsl:if>
                <xsl:apply-templates select="OrderRef"/>

                <tr>
                    <th>Supplier:</th>
                    <td><xsl:value-of select="SellerParty/Address/Name1"/></td>
                    <td><xsl:value-of select="SellerParty/PartyNumber"/></td>
                    <td />
                </tr>
                <tr>
                    <th />
                    <td><xsl:value-of select="SellerParty/Address/Street1"/></td>
                    <th colspan="2"/>
                </tr>
                <tr>
                    <th />
                    <td>
                        <xsl:value-of select="SellerParty/Address/PostalCode"/> 
                        <xsl:value-of select="SellerParty/Address/City"/> 
                        (<xsl:value-of select="SellerParty/Address/CountryCode"/>)
                    </td>
                    <th colspan="2"/>
                </tr>
                <tr />
                <tr>
                    <th>Purchaser:</th>
                    <td><xsl:value-of select="BuyerParty/Address/Name1"/></td>
                    <td><xsl:value-of select="BuyerParty/PartyNumber"/></td>
                    <th />
                </tr>
                <tr>
                    <th />
                    <td><xsl:value-of select="BuyerParty/Address/Street1"/></td>
                    <th colspan="2"/>
                </tr>
                <tr>
                    <th />
                    <td>
                        <xsl:value-of select="BuyerParty/Address/PostalCode"/> 
                        <xsl:value-of select="BuyerParty/Address/City"/> 
                        (<xsl:value-of select="BuyerParty/Address/CountryCode"/>)
                    </td>
                    <th colspan="2"/>
                </tr>
                <tr />
                <tr>
                    <th>Invoice Recipient:</th>
                    <td><xsl:value-of select="InvoiceOrg/InvoiceParty/Address/Name1"/></td>
                    <td><xsl:value-of select="InvoiceOrg/InvoiceParty/PartyNumber"/></td>
                    <th />
                </tr>
                <tr>
                    <th />
                    <td><xsl:value-of select="InvoiceOrg/InvoiceParty/Address/Street1"/></td>
                    <th colspan="2"/>
                </tr>
                <tr />
                <tr>
                    <th />
                    <td>
                        <xsl:value-of select="InvoiceOrg/InvoiceParty/Address/PostalCode"/> 
                        <xsl:value-of select="InvoiceOrg/InvoiceParty/Address/City"/> 
                        (<xsl:value-of select="InvoiceOrg/InvoiceParty/Address/CountryCode"/>)
                    </td>
                    <th colspan="2"/>
                </tr>
                <tr>
                    <th>VAT ID No.:</th>
                    <td><xsl:value-of select="InvoiceOrg/InvoiceParty/PartyNumber"/></td>
                    <th />
                </tr>
                <tr>
                    <th>Tax treatment:</th>
                    <td><xsl:value-of select="TaxTreatmentCode/@Value"/></td>
                    <th colspan="2"/>
                </tr>
                <tr>
                    <th>Due Date:</th>
                    <td>
                        <xsl:call-template name="formatdate">
                            <xsl:with-param name="datestr" select="InvoiceDueDate/Date"/>
                        </xsl:call-template>
                    </td>
                    <th colspan="2" />
                </tr>
                <tr>
                    <th>Currency:</th>
                    <td><xsl:value-of select="Currency"/></td>
                    <th colspan="2" />
                </tr>

                <xsl:apply-templates select="PaymentInstruction"/>
                <xsl:apply-templates select="FreeText"/>

            </tbody>
            </table>
    </xsl:template>

    <xsl:template match="DesAdvRef">
        <tr>
            <th>Delivery References:</th>
            <td>Document number:</td>
            <td />
            <td>Issue Date:</td>
        </tr>
        <tr>
            <th />
            <td><xsl:value-of select="DocumentNumber"/></td>
            <td />
            <td>
                <xsl:call-template name="formatdate">
                    <xsl:with-param name="datestr" select="Date"/>
                </xsl:call-template>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="OrderRef">
        <tr>
            <th />
            <td><xsl:value-of select="BuyerOrderNumber"/></td>
            <td><xsl:value-of select="SellerOrderNumber"/></td>
            <td>
                <xsl:call-template name="formatdate">
                    <xsl:with-param name="datestr" select="Date"/>
                </xsl:call-template>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="PaymentInstruction">
        <tr>
            <th>Payment:</th>
            <td colspan="4"><xsl:value-of select="."/></td>
        </tr>
    </xsl:template>

    <xsl:template match="FreeText">
        <tr>
            <th><xsl:value-of select="@Caption"/>:</th>
            <td colspan="4"><xsl:value-of select="."/></td>
        </tr>
    </xsl:template>

    <xsl:template match="Invoice/InvoiceDetail">
        <tr>
            <td><xsl:value-of select="PositionNumber"/></td>
            <td>
                <xsl:value-of select="ProductId/MakerCode"/>
                <a title="Pupesoft tuotekysely" href="tuote.php?tuoteno={ProductId/ProductNumber}&amp;tyyppi=TOIMTUOTENO&amp;tee=Z">
                    <xsl:value-of select="ProductId/ProductNumber"/>
                </a><br />
                <xsl:value-of select="ProductId/Ean"/><br />
                <xsl:value-of select="ProductDescription"/>
            </td>
            <td><xsl:value-of select="Quantity"/> <xsl:value-of select="Quantity/@UoM"/></td>
            <td><xsl:value-of select="UnitPrice"/></td>
            <td><xsl:value-of select="TotalPrice"/></td>
        </tr>

        <xsl:apply-templates select="AllowOrCharge"/>
        <xsl:apply-templates select="Tax"/>

        <tr>
            <td colspan="3" />
            <td></td>
            <td><xsl:value-of select="TotalValue"/></td>
        </tr>
        
        <xsl:if test="DesAdvRef">
            <tr>
                <td></td>
                <td>Delivery Reference:</td>
                <td colspan="3">
                    <xsl:if test="DesAdvItemRef">
                        Delivery item <xsl:value-of select="DesAdvItemRef/PositionNumber" /> in 
                    </xsl:if >
                Despatch advice <xsl:value-of select="DesAdvRef/DocumentNumber" />
                <xsl:if test="Date">
                    dated
                    <xsl:call-template name="formatdate">
                        <xsl:with-param name="datestr" select="Date"/>
                    </xsl:call-template>
                </xsl:if>
            </td>

            </tr>
        </xsl:if>
    
        <xsl:if test="OrderRef">
            <tr>
                <td></td>
                <td>Order Reference:</td>
                <td colspan="3">
                    <xsl:if test="OrderItemRef">
                        Order line item <xsl:value-of select="OrderItemRef/SellerOrderItemRef" /> in 
                    </xsl:if >
                Order <xsl:value-of select="OrderRef/SellerOrderNumber" />
                dated
                <xsl:call-template name="formatdate">
                    <xsl:with-param name="datestr" select="OrderRef/Date"/>
                </xsl:call-template>
                (supplier)
            </td>
            </tr>
        </xsl:if>

        <xsl:if test="OrderRef/BuyerOrderNumber">
            <tr>
                <td></td>
                <td></td>
                <td colspan="3">
                    <xsl:if test="OrderItemRef/BuyerOrderItemRef">
                        Order line item <xsl:value-of select="OrderItemRef/BuyerOrderItemRef" /> in 
                    </xsl:if >
                Order <xsl:value-of select="OrderRef/BuyerOrderNumber" />
                dated
                <xsl:call-template name="formatdate">
                    <xsl:with-param name="datestr" select="OrderRef/Date"/>
                </xsl:call-template>
                (purchaser)
            </td>
            </tr>
        </xsl:if>

        <xsl:apply-templates select="DeliveryPartyNumber"/>

        <xsl:for-each select="FreeText">
            <tr>
                <td />
                <td><xsl:value-of select="@Caption"/></td>
                <td colspan="3"><xsl:value-of select="."/></td>
            </tr>
        </xsl:for-each>

        <tr>
            <th colspan="5"> </th>
        </tr>
    </xsl:template>

    <xsl:template match="InvoiceDetail/Tax">
        <tr>
            <td></td>
            <td>Plus tax:</td>
            <td class="hilight"><xsl:value-of select="TaxCode"/></td>
            <td><xsl:value-of select="Percent"/>%</td>
            <td></td>
        </tr>
    </xsl:template>

    <xsl:template match="Invoice/InvoiceDetail/AllowOrCharge">
        <tr>
            <td colspan="2" />
            <td class="hilight"><xsl:value-of select="AllowOrChargeDescription"/></td>
            <td></td>
            <td><xsl:if test="AllowOrChargeIdentifier/@Value = 'Allow'">-</xsl:if><xsl:value-of select="Amount"/></td>
        </tr>
    </xsl:template>

    <xsl:template match="Invoice/InvoiceDetail/DeliveryPartyNumber">
        <tr>
            <td />
            <td>Ship to:</td>
            <td colspan="3"><xsl:value-of select="."/></td>
        </tr>
    </xsl:template>
    
    <xsl:template match="Invoice/InvoiceSummary">
        <table>
            <tbody>
                <tr>
                    <th colspan="4">Total Amounts</th>
                </tr>
                <tr>
                    <td>Subtotal:</td>
                    <td>Net Price</td>
                    <td></td>
                    <td><xsl:value-of select="InvoiceTotals/InvoiceNetValue/Amount"/></td>
                </tr>

                <xsl:apply-templates select="Tax"/>

                <tr>
                    <td></td>
                    <td>Gross price</td>
                    <td></td>
                    <td><xsl:value-of select="InvoiceTotals/InvoiceGrossValue/Amount"/></td>
                </tr>

                <xsl:for-each select="AllowOrCharge">
                    <tr>
                        <td>Additional charges:</td>
                        <td><xsl:value-of select="AllowOrChargeDescription"/> (<xsl:value-of select="AllowOrChargeCode"/>)</td>
                        <td><xsl:value-of select="Percent"/>%</td>
                        <td><xsl:if test="AllowOrChargeIdentifier/@Value = 'Allow'">-</xsl:if><xsl:value-of select="Amount"/></td>
                    </tr>
                </xsl:for-each>

                <xsl:apply-templates select="InvoiceTotals/PrepaidAmount"/>
                <xsl:apply-templates select="InvoiceTotals/InvoiceAmountPayable"/>

                <tr>
                    <th colspan="4">Summary</th>
                </tr>

                <xsl:apply-templates select="TaxTotals"/>
                <xsl:apply-templates select="TaxCodeDescription"/>
                
            </tbody>
        </table>
    </xsl:template>

    <xsl:template match="InvoiceSummary/Tax">
        <tr>
            <td></td>
            <td><xsl:value-of select="TaxCode"/></td>
            <td><xsl:value-of select="Percent"/> %</td>
            <td><xsl:value-of select="Amount"/></td>
        </tr>
    </xsl:template>

    <xsl:template match="InvoiceSummary/TaxTotals">
        <tr>
            <td>Tax Totals:</td>
            <td><xsl:value-of select="TaxCode"/></td>
            <td><xsl:value-of select="Percent"/> %</td>
            <td><xsl:value-of select="Amount"/></td>
        </tr>
    </xsl:template>

    <xsl:template match="InvoiceSummary/TaxCodeDescription">
        <tr>
            <td>Tax Types:</td>
            <td><xsl:value-of select="TaxCode"/></td>
            <td><xsl:value-of select="TaxDescription"/></td>
            <td/>
        </tr>
    </xsl:template>

    <xsl:template match="PrepaidAmount">
        <tr>
            <td></td>
            <td>Already paid</td>
            <td></td>
            <td><xsl:value-of select="Amount"/></td>
        </tr>
    </xsl:template>

    <xsl:template match="InvoiceAmountPayable">
       <tr>
            <td>Total:</td>
            <td>Payment due</td>
            <td/>
            <td><xsl:value-of select="Amount"/></td>
        </tr>
    </xsl:template>

    <xsl:template name="formatdate">
        <xsl:param name="datestr" />
        <xsl:variable name="dd">
            <xsl:value-of select="substring($datestr,7,2)" />
        </xsl:variable>
        <xsl:variable name="mm">
            <xsl:value-of select="substring($datestr,5,2)" />
        </xsl:variable>
        <xsl:variable name="yyyy">
            <xsl:value-of select="substring($datestr,1,4)" />
        </xsl:variable>
        <xsl:value-of select="$dd" />
        <xsl:value-of select="'.'" />
        <xsl:value-of select="$mm" />
        <xsl:value-of select="'.'" />
        <xsl:value-of select="$yyyy" />
    </xsl:template>

</xsl:stylesheet>
