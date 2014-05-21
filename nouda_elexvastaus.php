<?php

require('inc/parametrit.inc');

echo "<font class='head'>".t("Nouda ELEX vastaussanoma").":</font><hr><br>";

$responseFunction = array(  "TDE" => "Hylkäys",
              "TEP" => "Menettelyyn luovutuksen epääminen",
              "TER" => "Virhe",
              "THY" => "Hyväksyminen",
              "TLP" => "Lisäselvityspyyntö ",
              "TLU" => "Vientimenettelyyn luovutus",
              "TMI" => "Mitätöinti",
              "TOI" => "Oikaisupäätös",
              "TPV" => "Poistumisvahvistus ",
              "TTI" => "Tullin ilmoitus",
              "TVA" => "Vastaanotto");

$xmlstring = "        <FIExportResponse>
                <Message>
                    <sender>003702454428-TESTI</sender>
                    <recipient>003712345678</recipient>
                    <issue>2008-02-27T05:15:00.000+02:00</issue>
                    <reference>208058000473</reference>
                    <controlReference>1204082112625</controlReference>
                    <version>1.0</version>
                </Message>
                <Response>
                    <function>TDE</function>
                    <ContactCustomsOffice>
                        <CustomsOffice>
                            <Location>
                                <qualifier>Z</qualifier>
                                <identification>FI002000</identification>
                            </Location>
                            <Party>
                <name1>FI002000 Sähköinen tullauskeskus</name1>
                <name2>Tulli</name2>
            <Contact>
            <name>ELEX</name>
                </Contact>
              <Communication>
                  <telephone>020 090 00</telephone>
                 <telefax>020 391 115</telefax>
              </Communication>
              </Party>
                        </CustomsOffice>
                    </ContactCustomsOffice>
                    <Status/>
                    <Declaration>
            <customsReference>29900108165000301</customsReference>
              <rejection>2008-02-27T05:15:13.688+02:00</rejection>
                        <GoodsShipment>
                            <UCR>
                                <traderReference>90051913</traderReference>
                                <additionalTraderReference>M233-5294NK</additionalTraderReference>
                            </UCR>
                        </GoodsShipment>
                    </Declaration>
                    <Error> <!-- näitä voi olla usieita -->
                        <Pointer>
                            <documentSection>10</documentSection>
                            <sequence>1</sequence>
                        </Pointer>
                        <additionalDescription>Asiakirjan koodi (N952)  ei löydy tietokannasta</additionalDescription>
                        <validation>12</validation>
                    </Error>
                </Response>
            </FIExportResponse>
";


$xml = simplexml_load_string($xmlstring);

echo $xml->Message->sender."<br>";
echo $xml->Message->recipient."<br>";
echo $xml->Message->issue."<br>";
echo $xml->Message->reference."<br>";
echo $xml->Message->controlReference."<br>";
echo $xml->Message->version."<br>";

echo $xml->Response->function."<br>";

echo $xml->Response->ContactCustomsOffice->CustomsOffice->Location->qualifier."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Location->identification."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->identity."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->identityExtension."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->codeListResponsibleAgency."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->name1."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->name2."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->Adress->line."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->Adress->city."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->Adress->postCode."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->Adress->country."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->Contact->name."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->Communication->telephone."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->Communication->telefax."<br>";
echo $xml->Response->ContactCustomsOffice->CustomsOffice->Party->Communication->email."<br>";

echo $xml->Response->Status->effective."<br>";
echo $xml->Response->Status->Pointer->documentSection."<br>";
echo $xml->Response->Status->Pointer->sequence."<br>";

echo $xml->Response->Control->limit."<br>";

echo $xml->Response->Declaration->acceptance."<br>";
echo $xml->Response->Declaration->customsReference."<br>";
echo $xml->Response->Declaration->MRN."<br>";
echo $xml->Response->Declaration->rejection."<br>";
echo $xml->Response->Declaration->GoodsShipment->UCR->traderReference."<br>";
echo $xml->Response->Declaration->GoodsShipment->UCR->additionalTraderReference."<br>";

foreach ($xml->Response->Error as $error) {
  echo $error->Pointer->documentSection."<br>";
  echo $error->Pointer->sequence."<br>";
  echo $error->additionalDescription."<br>";
  echo $error->validation."<br>";
}

echo $xml->Response->AdditionalInformation->statement."<br>";
echo $xml->Response->AdditionalInformation->statementDescription."<br>";

require ('inc/footer.inc');
