<?php

if (!function_exists('finvoice_otsik')) {
  function finvoice_otsik($tootfinvoice, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow, $tulos_ulos, $silent, $nosoap = "") {
    global $yhtiorow, $err_finvoice, $tulos_ulos, $palvelin2, $tulostettavat_email;

    //  Koitetaan ollaanko virheettömiä
    //  Jos tapahtuu virhe, eli tietoja puuttuu niin tuota loppua ei saa tehdä tai koko aineisto voi mennä virheelliseksi!
    $err_finvoice = 0;

    //varmuudeksi alku aina puhtaalta pöydältä
    $senderpartyid         = '';
    $senderintermediator   = '';
    $receiverpartyid       = '';
    $receiverintermediator = '';

    // MessageID
    $mid = date("YmdHis")."-".$lasrow['laskunro'];

    $senderpartyid       = str_replace(" ", "", $yhtiorow['finvoice_senderpartyid']);
    $senderintermediator = str_replace(" ", "", $yhtiorow['finvoice_senderintermediator']);

    //  Osataanko käsitellä tämän pankin finvoiceaineistoa?
    switch ($senderintermediator) {
    case 'NDEAFIHH':
      $tulostuspalvelu  = "tulostukseen";
      break;
    case 'PSPBFIHH':
      $tulostuspalvelu  = "003718062728810P";
      break;
    case 'DABAFIHH':
      $tulostuspalvelu  = "003718062728810P";
      break;
    case 'HELSFIHH':
      $tulostuspalvelu  = "TULOSTUSPALVELU";
      break;
    case 'OKOYFIHH':
      $tulostuspalvelu  = "TULOSTUSPALVELU";
      break;
    case '003710948874':
      if ($yhtiorow['verkkolasku_lah'] == 'talenom') {
        $tulostuspalvelu  = "Tulostukseen";
      }
      else {
        $tulostuspalvelu  = "EKIRJE";
      }
      break;
    case '003723327487':
      if ($yhtiorow['verkkolasku_lah'] == 'trustpoint') {
        $tulostuspalvelu  = "TULOSTUSPALVELU";
      }
      else {
        $tulostuspalvelu  = "TULOSTUS";
      }
      break;
    case '003721291126':
      $tulostuspalvelu  = "PRINT";
      break;
    case '003709227412':
      $tulostuspalvelu  = "TULOSTUSPALVELU";
      break;
    case 'ARVATO':
      $tulostuspalvelu  = "TULOSTUSPALVELU";
      break;
    case 'ITELFIHH':
      $tulostuspalvelu  = "TULOSTUSPALVELU";
      break;
    default:
      // Katsotaan löytyykö avainsanoista sopivaa, muuten laitetaan tyhjäksi
      $avainsana       = t_avainsana("FINV_TULPALVELU", '', "AND selite = '{$senderintermediator}'", '', '', "selitetark");
      $tulostuspalvelu = $avainsana ? $avainsana : "";
    }

    //  Aloitellaan aineiston luontia. Kaikki tiedot pitää olla kunnossa, tai chn 112 "Pupesoft-Finvoice: Verkkolasku Pupesoftista-Pupesoftiin"
    if (($senderpartyid != "" and $senderintermediator != "" and $tulostuspalvelu != "") or $lasrow["chn"] == "112") {

      // Selvitetään vastaanottajan verkkolaskutunnus sekä vastaanottajan operaattori
      if ($yhtiorow["verkkolasku_lah"] == "iPost") {
        $receiverpartyid = $lasrow["verkkotunnus"];
        $receiverintermediator = "003710948874";
      }
      elseif (strpos($lasrow["verkkotunnus"], "@") !== FALSE) {
        list($receiverpartyid, $receiverintermediator) = explode("@", $lasrow["verkkotunnus"]);

        // Verkkotunnus voi olla sähköpostiosoite:
        if (($yhtiorow["verkkolasku_lah"] == "ppg" or $yhtiorow["verkkolasku_lah"] == "arvato" or $yhtiorow["verkkolasku_lah"] == "trustpoint") and strpos($receiverintermediator, ".") !== FALSE) {
          $receiverpartyid = $lasrow["verkkotunnus"];
          $receiverintermediator = "EMAIL";
        }
      }
      else {
        $receiverpartyid = $lasrow["verkkotunnus"];
        $receiverintermediator = "";
      }

      // Jos vastaanottajan operaattori puuttuu ja käytössä on Apix, laitetaan Apixin verkkolaskuosoite vastaanottajan operaattoriksi
      if ($receiverintermediator == "" and $yhtiorow["verkkolasku_lah"] == "apix") {
        $receiverintermediator = "003723327487";
      }

      // Jos vastaanottajan operaattori puuttuu laitetaan lähettäjän operaattori.
      // Maventalla ja Talenomilla tämä saa olla tyhjää.
      if ($receiverintermediator == "" and !in_array($yhtiorow["verkkolasku_lah"], array("maventa", "talenom"))) {
        $receiverintermediator = $senderintermediator;
      }

      // Jos vastaanottaja on tyhjää tai ollaan valittu lasku tulostuspalveluun chn 100 "Verkkolasku, tulostuspalvelu"
      if (($receiverpartyid == "" and $yhtiorow["verkkolasku_lah"] != "apix") or $lasrow["chn"] == "100") {
        $receiverpartyid = $tulostuspalvelu;

        if ($yhtiorow["verkkolasku_lah"] == "maventa") {
          $receiverintermediator = $tulostuspalvelu;
        }
      }
      if ($receiverintermediator == '' and $yhtiorow["verkkolasku_lah"] == "maventa")  {
        $receiverintermediator = 'n/a';
      }

      // Talenomiin lähetetään kaikki laskut, joten tägätään itse läheteyt sähköpostilaskut ja sisäiset laskut näin
      if ($yhtiorow["verkkolasku_lah"] == "talenom" and ($lasrow["chn_orig"] == "666" or $lasrow["chn_orig"] == "667")) {
        $receiverpartyid = "Email";

        // halutaan lähettää lasku suoraan asiakkaalle sähköpostilla.. mutta ei nollalaskua
        if ($lasrow["chn_orig"] == "666" and $lasrow["summa"] != 0) {
          $tulostettavat_email[] = $lasrow["tunnus"];
        }
      }

      // Tulostetaan virhe jos pakotettiin lasku tulostuspalveluun
      if ($receiverpartyid == $tulostuspalvelu and $lasrow["chn"] != "100" and $lasrow["chn"] != "112" and $silent == "") {
        $tulos_ulos .= "<font class='error'>".t("Asiakkaalta puuttuu verkkotunnus, lasku menee tulostuspalveluun.")." $lasrow[nimi] $lasrow[laskunro]</font><br>\r\n<br>\r\n";
      }

      //   Tehdään SOAP, paitsi jos halutaan vain katsoa ruudulla
      if ($nosoap == "") {
        fputs($tootfinvoice, "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:eb=\"http://www.oasis-open.org/committees/ebxml-msg/schema/msg-header-2_0.xsd\">\r\n");
        fputs($tootfinvoice, "<SOAP-ENV:Header>\r\n");
        fputs($tootfinvoice, "<eb:MessageHeader xmlns:eb=\"http://www.oasis-open.org/committees/ebxml-msg/schema/msg-header-2_0.xsd\" SOAP-ENV:mustUnderstand=\"1\" eb:version=\"2.0\">\r\n");

        fputs($tootfinvoice, "<eb:From>\r\n");
        xml_add("eb:PartyId", $senderpartyid, $tootfinvoice);
        xml_add("eb:Role", "Sender", $tootfinvoice);
        fputs($tootfinvoice, "</eb:From>\r\n");

        fputs($tootfinvoice, "<eb:From>\r\n");
        xml_add("eb:PartyId", $senderintermediator, $tootfinvoice);
        xml_add("eb:Role", "Intermediator", $tootfinvoice);
        fputs($tootfinvoice, "</eb:From>\r\n");

        fputs($tootfinvoice, "<eb:To>\r\n");
        if ($yhtiorow["verkkolasku_lah"] == "trustpoint" and $receiverintermediator == "EMAIL") {
          xml_add("eb:PartyId", $receiverintermediator , $tootfinvoice);
        }
        else {
          xml_add("eb:PartyId", $receiverpartyid, $tootfinvoice);
        }
        xml_add("eb:Role", "Receiver", $tootfinvoice);
        fputs($tootfinvoice, "</eb:To>\r\n");

        fputs($tootfinvoice, "<eb:To>\r\n");
        xml_add("eb:PartyId", $receiverintermediator, $tootfinvoice);
        xml_add("eb:Role", "Intermediator", $tootfinvoice);
        fputs($tootfinvoice, "</eb:To>\r\n");

        xml_add("eb:CPAId", "yoursand-mycpa", $tootfinvoice); //  Ei ilmeisesti juuri merkittävä
        xml_add("eb:ConversationId", "nnnnn", $tootfinvoice);  //   Ei ilmeisesti juuri merkittävä
        xml_add("eb:Service", "Routing", $tootfinvoice); //  Ei ilmeisesti juuri merkittävä
        xml_add("eb:Action", "ProcessInvoice", $tootfinvoice);
        fputs($tootfinvoice, "<eb:MessageData>\r\n");
        xml_add("eb:MessageId", $mid, $tootfinvoice);
        xml_add("eb:Timestamp", date("Y-m-d")."T".date("H:i:s")."+02", $tootfinvoice);
        fputs($tootfinvoice, "<eb:RefToMessageId/>\r\n");
        fputs($tootfinvoice, "</eb:MessageData>\r\n");
        fputs($tootfinvoice, "</eb:MessageHeader>\r\n");
        fputs($tootfinvoice, "</SOAP-ENV:Header>\r\n");
        fputs($tootfinvoice, "<SOAP-ENV:Body>\r\n");
        fputs($tootfinvoice, "<eb:Manifest eb:id=\"Manifest\" eb:version=\"2.0\">\r\n");
        fputs($tootfinvoice, "<eb:Reference eb:id=\"Finvoice\" xlink:href=\"$mid\">\r\n");
        fputs($tootfinvoice, "<eb:schema eb:location=\"http://www.pankkiyhdistys.fi/verkkolasku/finvoice/finvoice.xsd\" eb:version=\"2.0\"/>\r\n");
        fputs($tootfinvoice, "</eb:Reference>\r\n");
        fputs($tootfinvoice, "</eb:Manifest>\r\n");
        fputs($tootfinvoice, "</SOAP-ENV:Body>\r\n");
        fputs($tootfinvoice, "</SOAP-ENV:Envelope>\r\n");
      }

      // yhtiötiedot
      $y_tiedot_ok  = FALSE;
      $y_nimi       = $lasrow["yhtio_nimi"];
      $y_osoite     = $lasrow["yhtio_osoite"];
      $y_postino    = $lasrow["yhtio_postino"];
      $y_postitp    = $lasrow["yhtio_postitp"];
      $y_maa        = $lasrow["yhtio_maa"];
      $y_kotipaikka = $lasrow["yhtio_kotipaikka"];
      $y_vatnumero  = $lasrow["yhtio_ovttunnus"];
      $y_iban       = $yhtiorow['pankkiiban1'];
      $y_swift      = $yhtiorow['pankkiswift1'];

      if ((int) $lasrow["yhtio_toimipaikka"] != 0) {
        // haetaan toimipaikan tiedot
        $alhqur = "SELECT puhelin, fax, email, www from yhtion_toimipaikat where yhtio='$lasrow[yhtio]' and tunnus='$lasrow[yhtio_toimipaikka]'";
        $alhire = pupe_query($alhqur);

        if (mysql_num_rows($alhire) == 1) {
          $alhiro = mysql_fetch_assoc($alhire);

          $y_tiedot_ok = TRUE;
          $y_puhelin   = $alhiro['puhelin'];
          $y_fax       = $alhiro['fax'];
          $y_email     = $alhiro['email'];
          $y_www       = $alhiro['www'];
        }
      }

      // jos meillä on lasku menossa ulkomaille ja se laskutetaan eri ovttunnuksella
      if ($lasrow["yhtio_ovttunnus"] != "" and $lasrow["yhtio_ovttunnus"] != $yhtiorow["ovttunnus"]) {
        // haetaan toimipaikan tiedot
        $alhqur = "SELECT puhelin, fax, email, www from yhtion_toimipaikat where yhtio='$lasrow[yhtio]' and vat_numero='$lasrow[yhtio_ovttunnus]'";
        $alhire = pupe_query($alhqur);

        if (mysql_num_rows($alhire) == 1) {
          $alhiro = mysql_fetch_assoc($alhire);

          $y_tiedot_ok = TRUE;
          $y_puhelin   = $alhiro['puhelin'];
          $y_fax       = $alhiro['fax'];
          $y_email     = $alhiro['email'];
          $y_www       = $alhiro['www'];
        }
      }

      // jos ei olla haettu muualta, niin haetaan tiedot yhtiöltä
      if (!$y_tiedot_ok) {
        // haetaan toimipaikan tiedot
        $alhqur = "SELECT puhelin, fax, email, www from yhtio where yhtio='$lasrow[yhtio]'";
        $alhire = pupe_query($alhqur);

        if (mysql_num_rows($alhire) == 1) {
          $alhiro = mysql_fetch_assoc($alhire);

          $y_puhelin = $alhiro['puhelin'];
          $y_fax     = $alhiro['fax'];
          $y_email   = $alhiro['email'];
          $y_www     = $alhiro['www'];
        }
      }

      // haetaan asiakkaan tiedot
      $asquery = "SELECT * from asiakas where yhtio='$lasrow[yhtio]' and tunnus='$lasrow[liitostunnus]'";
      $asiakasre = pupe_query($asquery);
      $asiakasrow = mysql_fetch_assoc($asiakasre);

      //laitetaan setit kuntoon..
      fputs($tootfinvoice, "<?xml version=\"1.0\" encoding=\"ISO-8859-15\"?>\r\n");

      if ($nosoap == "NOSOAP") {
        fputs($tootfinvoice, "<?xml-stylesheet type=\"text/xsl\" href=\"{$palvelin2}datain/Finvoice.xsl\"?>\r\n");
      }
      else {
        fputs($tootfinvoice, "<?xml-stylesheet type=\"text/xsl\" href=\"Finvoice.xsl\"?>\r\n");
      }

      $messagetransmissiondetails_elementti = true;

      $pankit_array = array("AABAFI22",
                            "CITIFIHX",
                            "DABAFIHH",
                            "DABAFIHX",
                            "DNBAFIHX",
                            "ESSEFIHX",
                            "HANDFIHH",
                            "HELSFIHH",
                            "ITELFIHH",
                            "NDEAFIHH",
                            "OKOYFIHH",
                            "POPFFI22",
                            "SBANFIHH",
                            "SWEDFIHH");
      // Mikäli käytössä on Maventa ja lasku on menossa pankkiverkkoon, niin ei laiteta mukaan MessageTransmissionDetails-elementtiä
      if ($yhtiorow["verkkolasku_lah"] == "maventa" and in_array($receiverintermediator, $pankit_array)) {
        $messagetransmissiondetails_elementti = false;
      }

      //tästä lähtee Finvoice koodi
      fputs($tootfinvoice, "<Finvoice Version=\"2.01\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"Finvoice2.01.xsd\">\r\n");

      if ($messagetransmissiondetails_elementti) {
        fputs($tootfinvoice, "<MessageTransmissionDetails>\r\n");

        fputs($tootfinvoice, "<MessageSenderDetails>\r\n");
        xml_add("FromIdentifier", $senderpartyid, $tootfinvoice, 35, 2);
        xml_add("FromIntermediator", $senderintermediator, $tootfinvoice, 35, 2);
        fputs($tootfinvoice, "</MessageSenderDetails>\r\n");

        fputs($tootfinvoice, "<MessageReceiverDetails>\r\n");

        // PPG:llä ja Arvatolla tässä voi olla sähköpostiosoite, siksi pitempi.
        $toid_len = ($yhtiorow["verkkolasku_lah"] == "ppg" or $yhtiorow["verkkolasku_lah"] == "arvato")? 76 : 35;

        xml_add("ToIdentifier", $receiverpartyid, $tootfinvoice, $toid_len, 2);
        xml_add("ToIntermediator", $receiverintermediator, $tootfinvoice, 35, 2);
        fputs($tootfinvoice, "</MessageReceiverDetails>\r\n");

        fputs($tootfinvoice, "<MessageDetails>\r\n");
        xml_add("MessageIdentifier", $mid, $tootfinvoice, 48, 2);
        xml_add("MessageTimeStamp", date("Y-m-d")."T".date("H:i:s")."+02", $tootfinvoice, 35, 2);
        xml_add("RefToMessageIdentifier", "", $tootfinvoice, 48);
        fputs($tootfinvoice, "</MessageDetails>\r\n");

        fputs($tootfinvoice, "</MessageTransmissionDetails>\r\n");
      }

      fputs($tootfinvoice, "<SellerPartyDetails>\r\n");
      xml_add("SellerPartyIdentifier", tulosta_ytunnus($yhtiorow['ytunnus'], $y_maa), $tootfinvoice, 35);
      xml_add("SellerOrganisationName", $y_nimi, $tootfinvoice, 70, 2);
      xml_add("SellerOrganisationTaxCode", tulosta_ytunnus(preg_replace("/^0037/", "", $y_vatnumero), $y_maa, "VATNUMERO"), $tootfinvoice, 35);

      fputs($tootfinvoice, "<SellerPostalAddressDetails>\r\n");
      xml_add("SellerStreetName", $y_osoite, $tootfinvoice, 35, 2);
      xml_add("SellerTownName", $y_postitp, $tootfinvoice, 35, 2);
      xml_add("SellerPostCodeIdentifier", $y_postino, $tootfinvoice, 35, 2);
      xml_add("CountryCode", $y_maa, $tootfinvoice, 2, 2);
      xml_add("CountryName", $y_maa, $tootfinvoice, 35);
      fputs($tootfinvoice, "</SellerPostalAddressDetails>\r\n");
      fputs($tootfinvoice, "</SellerPartyDetails>\r\n");

      xml_add("SellerOrganisationUnitNumber", $y_vatnumero, $tootfinvoice, 35);
      xml_add("SellerContactPersonName", $myyrow['nimi'], $tootfinvoice, 35);

      fputs($tootfinvoice, "<SellerCommunicationDetails>\r\n");
      xml_add("SellerPhoneNumberIdentifier", $y_puhelin, $tootfinvoice, 35);
      xml_add("SellerEmailaddressIdentifier", $y_email, $tootfinvoice, 70);
      fputs($tootfinvoice, "</SellerCommunicationDetails>\r\n");

      fputs($tootfinvoice, "<SellerInformationDetails>\r\n");
      xml_add("SellerHomeTownName", $y_kotipaikka, $tootfinvoice, 35);
      xml_add("SellerVatRegistrationText", t("Alv.Rek"), $tootfinvoice, 35);
      xml_add("SellerPhoneNumber", $y_puhelin, $tootfinvoice, 35);
      xml_add("SellerFaxNumber", $y_fax, $tootfinvoice, 35);
      xml_add("SellerCommonEmailaddressIdentifier", $y_email, $tootfinvoice, 70);
      xml_add("SellerWebaddressIdentifier", $y_www, $tootfinvoice, 35);

      fputs($tootfinvoice, "<SellerAccountDetails>\r\n");
      xml_add("SellerAccountID IdentificationSchemeName=\"IBAN\"", str_replace(" ", "", $pankkitiedot['pankkiiban1']), $tootfinvoice, 35, 2);
      xml_add("SellerBic IdentificationSchemeName=\"BIC\"", $pankkitiedot['pankkiswift1'], $tootfinvoice, 11, 8);
      fputs($tootfinvoice, "</SellerAccountDetails>\r\n");

      if ($pankkitiedot['pankkiiban2']!='') {
        fputs($tootfinvoice, "<SellerAccountDetails>\r\n");
        xml_add("SellerAccountID IdentificationSchemeName=\"IBAN\"", str_replace(" ", "", $pankkitiedot['pankkiiban2']), $tootfinvoice, 35, 2);
        xml_add("SellerBic IdentificationSchemeName=\"BIC\"", $pankkitiedot['pankkiswift2'], $tootfinvoice, 11, 8);
        fputs($tootfinvoice, "</SellerAccountDetails>\r\n");
      }
      if ($pankkitiedot['pankkiiban3']!='') {
        fputs($tootfinvoice, "<SellerAccountDetails>\r\n");
        xml_add("SellerAccountID IdentificationSchemeName=\"IBAN\"", str_replace(" ", "", $pankkitiedot['pankkiiban3']), $tootfinvoice, 35, 2);
        xml_add("SellerBic IdentificationSchemeName=\"BIC\"", $pankkitiedot['pankkiswift3'], $tootfinvoice, 11, 8);
        fputs($tootfinvoice, "</SellerAccountDetails>\r\n");
      }

      fputs($tootfinvoice, "</SellerInformationDetails>\r\n");

      $laskutusosoitetiedot = array();

      if (strpos($_SERVER['SCRIPT_NAME'], "karhu.php") === FALSE) {
        $laskosoiteeri = "";

        if ($yhtiorow["verkkolasku_lah"] != "trustpoint") {
          $laskosoiteeri = "AND CONCAT(laskutus_nimi, laskutus_osoite, laskutus_postino, laskutus_postitp, laskutus_maa) != '$lasrow[nimi]$lasrow[osoite]$lasrow[postino]$lasrow[postitp]$lasrow[maa]'";
        }

        //  Oisko toista laskutusosoitetta?
        $query = "SELECT laskutus_nimi,
                  laskutus_nimitark,
                  laskutus_osoite,
                  laskutus_postino,
                  laskutus_postitp,
                  laskutus_maa
                  FROM laskun_lisatiedot
                  WHERE yhtio        = '{$lasrow['yhtio']}'
                  AND laskutus_nimi != ''
                  {$laskosoiteeri}
                  AND otunnus        = '{$lasrow['tunnus']}'
                  LIMIT 1";
        $tarkres = pupe_query($query);

        if (mysql_num_rows($tarkres) == 1) {
          $laskutusosoitetiedot = mysql_fetch_assoc($tarkres);
        }
      }
      elseif (strpos($_SERVER['SCRIPT_NAME'], "karhu.php") !== FALSE
        and ($asiakasrow["maksukehotuksen_osoitetiedot"] == 'B' or ($asiakasrow["maksukehotuksen_osoitetiedot"] == '' and $yhtiorow["maksukehotuksen_osoitetiedot"] == 'K'))
        and $asiakasrow["laskutus_nimi"] != '') {

        $laskutusosoitetiedot["laskutus_nimi"]     = $asiakasrow["laskutus_nimi"];
        $laskutusosoitetiedot["laskutus_nimitark"] = $asiakasrow["laskutus_nimitark"];
        $laskutusosoitetiedot["laskutus_osoite"]   = $asiakasrow["laskutus_osoite"];
        $laskutusosoitetiedot["laskutus_postitp"]  = $asiakasrow["laskutus_postitp"];
        $laskutusosoitetiedot["laskutus_postino"]  = $asiakasrow["laskutus_postino"];
        $laskutusosoitetiedot["laskutus_maa"]      = $asiakasrow["laskutus_maa"];
      }

      if (isset($laskutusosoitetiedot["laskutus_nimi"]) and $laskutusosoitetiedot["laskutus_nimi"] != "") {
        fputs($tootfinvoice, "<InvoiceRecipientPartyDetails>\r\n");
        xml_add("InvoiceRecipientPartyIdentifier", tulosta_ytunnus($lasrow['ytunnus'], $lasrow['maa'], ""), $tootfinvoice, 35);

        $laskutus_nimitieto = trim($laskutusosoitetiedot["laskutus_nimi"]." ".$laskutusosoitetiedot["laskutus_nimitark"]);
        $laskutus_nimitieto_array = mb_str_split($laskutus_nimitieto, 35, 2);

        foreach ($laskutus_nimitieto_array as $laskutus_nimi_osa) {
          xml_add("InvoiceRecipientOrganisationName", $laskutus_nimi_osa, $tootfinvoice, 35, 2);
        }

        if ($yhtiorow["verkkolasku_lah"] != "maventa") {
          xml_add("InvoiceRecipientOrganisationTaxCode", tulosta_ytunnus($lasrow['ytunnus'], $lasrow['maa'], "VATNUMERO"), $tootfinvoice, 35); // Maventa ei salli tyhjänä, optionaalinen
        }
        fputs($tootfinvoice, "<InvoiceRecipientPostalAddressDetails>\r\n");
        xml_add("InvoiceRecipientStreetName", $laskutusosoitetiedot["laskutus_osoite"], $tootfinvoice, 35, 2);
        xml_add("InvoiceRecipientTownName", $laskutusosoitetiedot["laskutus_postitp"], $tootfinvoice, 35, 2);
        xml_add("InvoiceRecipientPostCodeIdentifier", $laskutusosoitetiedot["laskutus_postino"], $tootfinvoice, 35, 2);
        xml_add("CountryCode", $laskutusosoitetiedot["laskutus_maa"], $tootfinvoice, 2, 2);
        xml_add("CountryName", $laskutusosoitetiedot["laskutus_maa"], $tootfinvoice, 35);
        fputs($tootfinvoice, "</InvoiceRecipientPostalAddressDetails>\r\n");
        fputs($tootfinvoice, "</InvoiceRecipientPartyDetails>\r\n");

        xml_add("InvoiceRecipientOrganisationUnitNumber", $lasrow['ovttunnus'], $tootfinvoice, 35);
      }

      fputs($tootfinvoice, "<BuyerPartyDetails>\r\n");
      xml_add("BuyerPartyIdentifier", tulosta_ytunnus($lasrow['ytunnus'], $lasrow['maa'], ""), $tootfinvoice, 35);

      // Nimikentän pituus
      $nimikenpit = 70;

      if ($yhtiorow["verkkolasku_lah"] == "talenom") {
        $nimikenpit = 35;
      }

      $asiakas_nimitieto = trim($lasrow['nimi']." ".$lasrow['nimitark']);
      $asiakas_nimitieto_array = mb_str_split($asiakas_nimitieto, $nimikenpit, 2);

      foreach ($asiakas_nimitieto_array as $asiakas_nimi_osa) {
        xml_add("BuyerOrganisationName", $asiakas_nimi_osa, $tootfinvoice, $nimikenpit, 2);
      }

      xml_add("BuyerOrganisationTaxCode", tulosta_ytunnus($lasrow['ytunnus'], $lasrow['maa'], "VATNUMERO"), $tootfinvoice, 35);

      fputs($tootfinvoice, "<BuyerPostalAddressDetails>\r\n");
      xml_add("BuyerStreetName", $lasrow['osoite'], $tootfinvoice, 35, 2);
      xml_add("BuyerTownName", $lasrow['postitp'], $tootfinvoice, 35, 2);
      xml_add("BuyerPostCodeIdentifier", $lasrow['postino'], $tootfinvoice, 35, 2);
      xml_add("CountryCode", $lasrow['maa'], $tootfinvoice, 2, 2);
      xml_add("CountryName", $lasrow['maa'], $tootfinvoice, 35);
      fputs($tootfinvoice, "</BuyerPostalAddressDetails>\r\n");
      fputs($tootfinvoice, "</BuyerPartyDetails>\r\n");

      xml_add("BuyerOrganisationUnitNumber", $lasrow['ovttunnus'], $tootfinvoice, 35);

      $asiakkaan_yhteyshenkilo = "";

      if ($yhtiorow["tilauksen_yhteyshenkilot"] == "K") {
        //  Onko meillä kaupallista käsittelijää, falseback on tekninen yhteyshenkilö
        $query = "SELECT kk.nimi kaupallinen_kasittelija, kt.nimi tekninen_yhteyshenkilo
                  FROM lasku
                  JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
                  LEFT JOIN yhteyshenkilo kk ON kk.yhtio=laskun_lisatiedot.yhtio and kk.tunnus=laskun_lisatiedot.yhteyshenkilo_kaupallinen and kk.tyyppi = 'A'
                  LEFT JOIN yhteyshenkilo kt ON kt.yhtio=laskun_lisatiedot.yhtio and kt.tunnus=laskun_lisatiedot.yhteyshenkilo_tekninen and kt.tyyppi = 'A'
                  WHERE lasku.yhtio  = '$lasrow[yhtio]'
                  and lasku.laskunro = '$lasrow[laskunro]'
                  and lasku.tila     = 'L'";
        $tarkres = pupe_query($query);

        while ($tarkrow = mysql_fetch_assoc($tarkres)) {
          if ($asiakkaan_yhteyshenkilo == "") {
            if ($tarkrow["kaupallinen_kasittelija"] != "") {
              $asiakkaan_yhteyshenkilo = $tarkrow["kaupallinen_kasittelija"];
            }
            elseif ($tarkrow["tekninen_yhteyshenkilo"] != "") {
              $asiakkaan_yhteyshenkilo = $tarkrow["tekninen_yhteyshenkilo"];
            }
          }
        }
      }

      if ($asiakkaan_yhteyshenkilo == "") {
        $asiakkaan_yhteyshenkilo = $lasrow['tilausyhteyshenkilo'];
      }

      xml_add("BuyerContactPersonName", $asiakkaan_yhteyshenkilo, $tootfinvoice, 35);

      if ($yhtiorow["verkkolasku_lah"] == "trustpoint" and $receiverintermediator == "EMAIL") {
        fputs($tootfinvoice, "<BuyerCommunicationDetails>\r\n");
        xml_add("BuyerEmailaddressIdentifier", $receiverpartyid, $tootfinvoice, 35);
        fputs($tootfinvoice, "</BuyerCommunicationDetails>\r\n");
      }

      fputs($tootfinvoice, "<DeliveryPartyDetails>\r\n");
      xml_add("DeliveryPartyIdentifier", tulosta_ytunnus($lasrow['ytunnus'], $lasrow['maa'], ""), $tootfinvoice, 35);

      $asiakas_toimnimitieto = trim($lasrow['toim_nimi']." ".$lasrow['toim_nimitark']);
      $asiakas_toimnimitieto_array = mb_str_split($asiakas_toimnimitieto, 35, 2);

      foreach ($asiakas_toimnimitieto_array as $asiakas_toimnimi_osa) {
        xml_add("DeliveryOrganisationName", $asiakas_toimnimi_osa, $tootfinvoice, 35, 2);
      }

      xml_add("DeliveryOrganisationTaxCode", tulosta_ytunnus($lasrow['ytunnus'], $lasrow['maa'], "VATNUMERO"), $tootfinvoice, 35);

      fputs($tootfinvoice, "<DeliveryPostalAddressDetails>\r\n");
      xml_add("DeliveryStreetName", $lasrow['toim_osoite'], $tootfinvoice, 35, 2);
      xml_add("DeliveryTownName", $lasrow['toim_postitp'], $tootfinvoice, 35, 2);
      xml_add("DeliveryPostCodeIdentifier", $lasrow['toim_postino'], $tootfinvoice, 35, 2);
      xml_add("CountryCode", $lasrow['toim_maa'], $tootfinvoice, 2);
      xml_add("CountryName", $lasrow['toim_maa'], $tootfinvoice, 35);
      fputs($tootfinvoice, "</DeliveryPostalAddressDetails>\r\n");
      fputs($tootfinvoice, "</DeliveryPartyDetails>\r\n");

      xml_add("DeliveryOrganisationUnitNumber", $lasrow['toim_ovttunnus'], $tootfinvoice, 35);

      fputs($tootfinvoice, "<DeliveryDetails>\r\n");
      fputs($tootfinvoice, "<DeliveryPeriodDetails>\r\n");
      xml_add("StartDate Format=\"CCYYMMDD\"", vlas_dateconv($toimaikarow["mint"]), $tootfinvoice);
      xml_add("EndDate Format=\"CCYYMMDD\"", vlas_dateconv($toimaikarow["maxt"]), $tootfinvoice);
      fputs($tootfinvoice, "</DeliveryPeriodDetails>\r\n");
      xml_add("DeliveryMethodText", $lasrow['toimitustapa'], $tootfinvoice, 512);
      xml_add("DeliveryTermsText", $lasrow['toimitusehto'], $tootfinvoice, 512);

      // Kauttalaskutus
      if ($lasrow["chn"] == "112" and $asiakasrow["osasto"] == "6" and $lasrow["yhtio"] == "artr") {
        xml_add("TerminalAddressText", "KAUTTALASKUTUS", $tootfinvoice);
      }

      fputs($tootfinvoice, "</DeliveryDetails>\r\n");

      fputs($tootfinvoice, "<InvoiceDetails>\r\n");

      // Tsekataan laskun tyyppikoodi INV
      if ($lasrow['arvo'] >= 0) {
        xml_add("InvoiceTypeCode CodeListAgencyIdentifier=\"SPY\"", "INV01", $tootfinvoice);
        xml_add("InvoiceTypeText", "LASKU", $tootfinvoice, 35, 1);
      }
      else {
        xml_add("InvoiceTypeCode CodeListAgencyIdentifier=\"SPY\"", "INV02", $tootfinvoice);
        xml_add("InvoiceTypeText", "HYVITYSLASKU", $tootfinvoice, 35, 1);
      }

      xml_add("OriginCode", "Original", $tootfinvoice);
      xml_add("InvoiceNumber", $lasrow['laskunro'], $tootfinvoice, 20, 1);
      xml_add("InvoiceDate Format=\"CCYYMMDD\"", vlas_dateconv($lasrow['tapvm']), $tootfinvoice);
      xml_add("SellerReferenceIdentifier", substr($lasrow['tilausnumerot'], 0, 35), $tootfinvoice, 35);

      if ($yhtiorow["verkkolasku_lah"] == "talenom" or $yhtiorow["verkkolasku_lah"] == "arvato") {
        xml_add("SellersBuyerIdentifier", substr($asiakasrow["asiakasnro"], 0, 35), $tootfinvoice, 35);
      }
      elseif (trim($lasrow['asiakkaan_tilausnumero']) != "") {
        xml_add("SellersBuyerIdentifier", substr($lasrow['asiakkaan_tilausnumero'], 0, 35), $tootfinvoice, 35);
      }
      else {
        xml_add("SellersBuyerIdentifier", substr($lasrow['viesti'], 0, 35), $tootfinvoice, 35);
      }

      if (trim($lasrow['asiakkaan_tilausnumero']) != "") {
        xml_add("OrderIdentifier", substr($lasrow['asiakkaan_tilausnumero'], 0, 35), $tootfinvoice, 35);
      }
      else {
        xml_add("OrderIdentifier", substr($lasrow['viesti'], 0, 35), $tootfinvoice, 35);
      }

      xml_add("AgreementIdentifier", substr($lasrow['viesti'], 0, 35), $tootfinvoice, 35);

      xml_add("InvoiceTotalVatExcludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($lasrow['arvo'], 2), $tootfinvoice);
      xml_add("InvoiceTotalVatAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp(($lasrow['summa']-$lasrow['arvo']), 2), $tootfinvoice);
      xml_add("InvoiceTotalVatIncludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($lasrow['summa'], 2), $tootfinvoice);
    }
    elseif ($silent == "" or strpos($_SERVER['SCRIPT_NAME'], "valitse_laskutettavat_tilaukset.php") !== FALSE  or strpos($_SERVER['SCRIPT_NAME'], "uudelleenluo_laskuaineisto.php") !== FALSE) {
      if ($senderpartyid == "" or $senderintermediator == "") {
        $tulos_ulos .= t("Finvoiceaineistoa ei voitu luoda. Yhtiolta puuttuu finvoice_senderpartyid tai finvoice_senderintermediator tunnus. Tulosta lasku manuaalisesti!")." ".$lasrow["laskunro"]."<br>\r\n<br>\r\n";
      }
      elseif ($tulostuspalvelu == "") {
        $tulos_ulos .= t("Finvoiceaineiston lähetystä pankkiin '$yhtiorow[finvoice_senderintermediator]' ei vielä tueta. Tulosta lasku manuaalisesti!")." ".$lasrow["laskunro"]."<br>\r\n<br>\r\n";
      }
      else {
        $tulos_ulos .= t("Finvoiceaineistoa ei voitu luoda. Yhtiolta puuttuu ovttunnus, SWIFT tai IBAN tunnus. Tulosta lasku manuaalisesti!")." ".$lasrow["laskunro"]."<br>\r\n<br>\r\n";
      }
      $err_finvoice++;
    }
    else {
      $err_finvoice++;
    }
  }
}

if (!function_exists('finvoice_alvierittely')) {
  function finvoice_alvierittely($tootfinvoice, $lasrow, $alvrow) {
    global $err_finvoice, $kieli;

    if ($err_finvoice == 0) {
      fputs($tootfinvoice, "<VatSpecificationDetails>\r\n");
      xml_add("VatBaseAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($alvrow['rivihinta']), $tootfinvoice);
      xml_add("VatRatePercent", pp($alvrow['alv']), $tootfinvoice);
      xml_add("VatRateAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($alvrow['alvrivihinta']), $tootfinvoice);
      if ($alvrow["alv"] > 600) {
        xml_add("VatFreeText", t("Ostaja verovelvollinen AVL 8c §", $kieli), $tootfinvoice, 70);
      }
      fputs($tootfinvoice, "</VatSpecificationDetails>\r\n");
    }
  }
}

if (!function_exists('finvoice_otsikko_loput')) {
  function finvoice_otsikko_loput($tootfinvoice, $lasrow, $masrow, $pankkitiedot) {
    global $err_finvoice, $yhtiorow, $kieli;

    if ($err_finvoice == 0) {

      if ($lasrow['mainosteksti'] != '') {
        $lasrow['sisviesti1'] .= "|{$lasrow['mainosteksti']}";
      }

      $InvoiceFreeTexts = explode("|", trim($lasrow["sisviesti1"]));

      $kommpituus = 512;

      if ($yhtiorow['verkkolasku_lah'] == 'talenom') {
        $kommpituus = 70;
      }

      foreach ($InvoiceFreeTexts as $InvoiceFreeText) {
        if (trim($InvoiceFreeText) != "") {

          foreach (explode("|", wordwrap($InvoiceFreeText, $kommpituus, "|")) as $InvFreeT) {
            xml_add("InvoiceFreeText", $InvFreeT, $tootfinvoice);
          }
        }
      }

      fputs($tootfinvoice, "<PaymentTermsDetails>\r\n");
      xml_add("PaymentTermsFreeText", pp($masrow['teksti']), $tootfinvoice, 70);
      xml_add("InvoiceDueDate Format=\"CCYYMMDD\"", pp(vlas_dateconv($lasrow['erpcm'])), $tootfinvoice);

      if ($lasrow['kasumma'] != 0) {
        xml_add("CashDiscountDate Format=\"CCYYMMDD\"", pp(vlas_dateconv($lasrow['kapvm'])), $tootfinvoice);
        xml_add("CashDiscountBaseAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($lasrow['summa'], 2), $tootfinvoice);
        xml_add("CashDiscountPercent", pp($masrow['kassa_alepros']), $tootfinvoice);
        xml_add("CashDiscountAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($lasrow['kasumma'], 2), $tootfinvoice);
      }

      fputs($tootfinvoice, "<PaymentOverDueFineDetails>\r\n");
      xml_add("PaymentOverDueFineFreeText", t("Viivästyskorko", $kieli)." ".pp($lasrow['viikorkopros'])."%", $tootfinvoice, 512);
      xml_add("PaymentOverDueFinePercent", pp($lasrow['viikorkopros'], 2), $tootfinvoice);
      fputs($tootfinvoice, "</PaymentOverDueFineDetails>\r\n");
      fputs($tootfinvoice, "</PaymentTermsDetails>\r\n");
      fputs($tootfinvoice, "</InvoiceDetails>\r\n");

      fputs($tootfinvoice, "<PaymentStatusDetails>\r\n");
      xml_add("PaymentStatusCode", "NOTPAID", $tootfinvoice);
      fputs($tootfinvoice, "</PaymentStatusDetails>\r\n");

      if ($yhtiorow['verkkolasku_lah'] == 'talenom' and isset($masrow["factoring_id"])) {
        fputs($tootfinvoice, "<FactoringAgreementDetails>\r\n");
        xml_add("FactoringAgreementIdentifier", $pankkitiedot["pankkinimi1"], $tootfinvoice, 35);
        xml_add("FactoringTypeCode", "3", $tootfinvoice, 35);
        fputs($tootfinvoice, "</FactoringAgreementDetails>\r\n");
      }
    }
  }
}

if (!function_exists('finvoice_rivi')) {
  function finvoice_rivi($tootfinvoice, $tilrow, $lasrow, $vatamount, $laskutyyppi = 0) {
    global $err_finvoice, $yhtiorow, $kukarow, $tullinimike_ja_alkuperamaa, $kieli;

    if ($laskutyyppi == 10 or $laskutyyppi == 12) {
      $tilrow['hinta'] = 0;
      $tilrow['rivihinta'] = 0;
      $tilrow["rivihinta_verollinen"] = 0;
      $vatamount = 0;
      $alennukset_yhteensa = 0;

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        $tilrow["ale{$alepostfix}"] = 0;
      }
    }

    if ($err_finvoice == 0) {
      fputs($tootfinvoice, "<InvoiceRow>\r\n");
      xml_add("ArticleIdentifier", $tilrow['tuoteno'], $tootfinvoice, 35);
      xml_add("ArticleName", $tilrow['nimitys'], $tootfinvoice, 100);
      xml_add("DeliveredQuantity QuantityUnitCode=\"$tilrow[yksikko]\"", pp($tilrow['kpl'], 2), $tootfinvoice);
      xml_add("OrderedQuantity QuantityUnitCode=\"$tilrow[yksikko]\"", pp($tilrow['tilkpl'], 2), $tootfinvoice);
      xml_add("UnitPriceAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($tilrow['hinta'], $yhtiorow["hintapyoristys"], 5, 2), $tootfinvoice);
      xml_add("UnitPriceVatIncludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($tilrow['hinta_verollinen'], $yhtiorow["hintapyoristys"], 5, 2), $tootfinvoice);

      if ($tilrow['tilaajanrivinro'] != 0) {
        xml_add("RowIdentifier", $tilrow['tilaajanrivinro'], $tootfinvoice, 35);
      }

      if ((int) str_replace("-", "", substr($tilrow['tilauspaiva'], 0, 10)) < (int) str_replace("-", "", substr($tilrow['toimitettuaika'], 0, 10))) {
        xml_add("RowIdentifierDate Format=\"CCYYMMDD\"", vlas_dateconv($tilrow['tilauspaiva']), $tootfinvoice);
      }

      xml_add("RowDeliveryIdentifier", $tilrow['otunnus'], $tootfinvoice, 35);
      xml_add("RowDeliveryDate Format=\"CCYYMMDD\"", vlas_dateconv($tilrow['toimitettuaika']), $tootfinvoice);

      // Normifreetext
      if ($yhtiorow["verkkolasku_lah"] != "talenom") {
        finvoice_row_freetext($tootfinvoice, $tilrow, $lasrow);
      }

      $alennukset_yhteensa = generoi_alekentta_php($tilrow, 'M', 'plain');

      xml_add("RowDiscountPercent", pp($alennukset_yhteensa, 2), $tootfinvoice);
      xml_add("RowVatRatePercent", pp($tilrow['alv'], 2, 5, 2), $tootfinvoice);
      xml_add("RowVatAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($vatamount, $yhtiorow["hintapyoristys"], 5, 2), $tootfinvoice);  // veron määrä
      xml_add("RowVatExcludedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($tilrow['rivihinta'], $yhtiorow["hintapyoristys"], 5, 2), $tootfinvoice);  // veroton rivihinta
      xml_add("RowAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($tilrow["rivihinta_verollinen"], $yhtiorow["hintapyoristys"], 5, 2), $tootfinvoice);  // verollinen rivihinta

      if ($yhtiorow["verkkolasku_lah"] == "talenom" and !empty($tilrow['tiliointirivitunnus'])) {
        $query = "SELECT tiliointi.tilino,
                  kustp.nimi kustannuspaikka,
                  projekti.nimi projekti,
                  kohde.nimi kohde
                  FROM tiliointi
                  LEFT JOIN kustannuspaikka kustp ON (tiliointi.yhtio=kustp.yhtio and tiliointi.kustp=kustp.tunnus)
                  LEFT JOIN kustannuspaikka projekti ON (tiliointi.yhtio=projekti.yhtio and tiliointi.projekti=projekti.tunnus)
                  LEFT JOIN kustannuspaikka kohde ON (tiliointi.yhtio=kohde.yhtio and tiliointi.kohde=kohde.tunnus)
                  WHERE tiliointi.yhtio = '$kukarow[yhtio]'
                  AND tiliointi.tunnus = {$tilrow['tiliointirivitunnus']}";
        $tilires = pupe_query($query);
        $tilirow = mysql_fetch_assoc($tilires);

        xml_add("SalesAccountNumber", $tilirow['tilino'], $tootfinvoice);
      }

      // Talenom freetext
      if ($yhtiorow["verkkolasku_lah"] == "talenom") {
        finvoice_row_freetext($tootfinvoice, $tilrow, $lasrow);
      }

      fputs($tootfinvoice, "</InvoiceRow>\r\n");
    }
  }
}

if (!function_exists('finvoice_row_freetext')) {
  function finvoice_row_freetext($tootfinvoice, $tilrow, $lasrow) {
    global $yhtiorow, $kukarow, $tullinimike_ja_alkuperamaa, $kieli;

    $lisakomm = "";

    if ($tilrow['tuoteno'] != $yhtiorow["kuljetusvakuutus_tuotenumero"] and $tilrow['tuoteno'] != $yhtiorow["laskutuslisa_tuotenumero"] and $tilrow['seuraava_otunnus'] != $tilrow["otunnus"]) {

      // Tämä on tilauksen vika rivi, laitetaan työmäärystiedot tähän
      $query = "SELECT tyomaarays.*
                FROM lasku
                JOIN tyomaarays ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
                WHERE lasku.yhtio      = '$kukarow[yhtio]'
                and lasku.tilaustyyppi = 'A'
                and lasku.tunnus       = '$tilrow[otunnus]'";
      $tyomres = pupe_query($query);

      if (mysql_num_rows($tyomres) > 0) {
        while ($tyomrow = mysql_fetch_assoc($tyomres)) {

          $lisakomm .= "|".t("Työmääräys", $kieli). ": $tyomrow[otunnus]";

          $al_res = t_avainsana("TYOM_TYOKENTAT", $kieli, "and avainsana.selitetark != '' and avainsana.nakyvyys in ('K','L','J')");

          while ($al_row = mysql_fetch_assoc($al_res)) {

            $kentta = $al_row["selite"];

            if (((!is_numeric($tyomrow[$kentta]) and trim($tyomrow[$kentta]) != '') or (is_numeric($tyomrow[$kentta]) and $tyomrow[$kentta] != 0)) and trim($tyomrow[$kentta]) != '0000-00-00') {
              if (strtoupper($al_row["selitetark_2"]) == "TEXT") {
                $lisakomm .= "|$al_row[selitetark]: ".$tyomrow[$kentta];
              }
              else {
                if (strtoupper($al_row["selitetark_2"]) == "DATE") {
                  $tyomrow[$kentta] = tv1dateconv($tyomrow[$kentta]);
                }
                elseif ($kentta == "suorittaja") {
                  $query = "SELECT nimi
                            FROM kuka
                            WHERE yhtio = '$kukarow[yhtio]'
                            and kuka    = '".$tyomrow[$kentta]."'";
                  $yresult = pupe_query($query);
                  $row = mysql_fetch_assoc($yresult);

                  $tyomrow[$kentta] = $row["nimi"];
                }
                elseif ($kentta == "merkki") {
                  $yresult = t_avainsana("SARJANUMERON_LI", $kieli, "and avainsana.selite = 'MERKKI' and avainsana.selitetark = '".$tyomrow[$kentta]."'");

                  if (mysql_num_rows($yresult) > 0) {
                    $row = mysql_fetch_assoc($yresult);

                    $tyomrow[$kentta] = $row["selitetark_2"];
                  }
                }

                $lisakomm .= "|$al_row[selitetark]: ".$tyomrow[$kentta];
              }
            }
          }
        }
      }
    }

    // Laitetaan tilausnumero tähän
    if ($yhtiorow["verkkolasku_lah"] == "talenom") {
      $lisakomm .= "|".t("Tilaus", $kieli).": ".$tilrow["otunnus"];
    }

    // Laitetaan tilausviite tähän
    if (!empty($tilrow["laskuviesti"])) {
      $lisakomm .= "|".t("Viitteenne").": ".$tilrow["laskuviesti"];
    }

    // Laitetaan asiakkaan tilausnumero tähän
    if (!empty($tilrow['asiakkaan_tilausnumero'])) {
      $lisakomm .= "|".t("Tilauksenne", $kieli).": ".$tilrow['asiakkaan_tilausnumero'];
    }

    if (!empty($tullinimike_ja_alkuperamaa) and $tullinimike_ja_alkuperamaa != "") {

      // Haetaan tullinimike ja alkuperämaa
      $query = "SELECT alkuperamaa,
                if (jarjestys = 0, 9999, jarjestys) sorttaus
                FROM tuotteen_toimittajat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$tilrow['tuoteno']}'
                ORDER BY sorttaus
                LIMIT 1";
      $alkuperamaa_res = pupe_query($query);
      $alkuperamaa_row = mysql_fetch_assoc($alkuperamaa_res);

      $tullinimike_ja_alkuperamaa_text = t("Tullinimike", $kieli).": {$tilrow["tullinimike"]}";

      if ($tullinimike_ja_alkuperamaa == "A") {
        // Tsekataan onko tämä valmiste
        $query = "SELECT tunnus
                  FROM tuoteperhe
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND isatuoteno = '{$tilrow['tuoteno']}'
                  LIMIT 1";
        $alkuperamaa_res = pupe_query($query);

        if (mysql_num_rows($alkuperamaa_res)) {
          // Valmisteet ovat kotimaan tuotantoa
          $tullinimike_ja_alkuperamaa_text .= " ".t("Alkuperämaa", $kieli).":  {$yhtiorow['maa']}";
        }
        elseif ($alkuperamaa_row['alkuperamaa'] != '') {
          $tullinimike_ja_alkuperamaa_text .= " ".t("Alkuperämaa", $kieli).":  {$alkuperamaa_row['alkuperamaa']}";
        }
      }
      elseif ($tullinimike_ja_alkuperamaa == "C") {
        // Kaikki tuotteet ovat kotimaan tuotantoa
        $tullinimike_ja_alkuperamaa_text .= " ".t("Alkuperämaa", $kieli).":  {$yhtiorow['maa']}";
      }

      $tilrow["kommentti"] .= "|$tullinimike_ja_alkuperamaa_text";
    }

    // Siivotaan myös lisäkommentti
    $lisakomm = pupesoft_invoicestring($lisakomm);

    $RowFreeTexts = explode("|", trim(trim($lisakomm)."|".trim($tilrow["kommentti"]), "|"));

    foreach ($RowFreeTexts as $RowFreeText) {
      if (trim($RowFreeText) != "") {
        foreach (explode("|", wordwrap($RowFreeText, 512, "|")) as $RowFreeT) {
          xml_add("RowFreeText", $RowFreeT, $tootfinvoice);
        }
      }
    }
  }
}

if (!function_exists('finvoice_lasku_loppu')) {
  function finvoice_lasku_loppu($tootfinvoice, $lasrow, $pankkitiedot, $masrow, $liitteita = false) {
    global $yhtiorow, $err_finvoice;

    if ($err_finvoice == 0) {
      fputs($tootfinvoice, "<EpiDetails>\r\n");

      fputs($tootfinvoice, "<EpiIdentificationDetails>\r\n");
      xml_add("EpiDate Format=\"CCYYMMDD\"", vlas_dateconv($lasrow['tapvm']), $tootfinvoice);
      xml_add("EpiReference", $lasrow['viite'], $tootfinvoice);
      fputs($tootfinvoice, "</EpiIdentificationDetails>\r\n");

      fputs($tootfinvoice, "<EpiPartyDetails>\r\n");
      fputs($tootfinvoice, "<EpiBfiPartyDetails>\r\n");
      xml_add("EpiBfiIdentifier IdentificationSchemeName=\"BIC\""  , $pankkitiedot["pankkiswift1"], $tootfinvoice, 11, 8);
      fputs($tootfinvoice, "</EpiBfiPartyDetails>\r\n");

      fputs($tootfinvoice, "<EpiBeneficiaryPartyDetails>\r\n");

      if (isset($masrow["factoring_id"])) {
        xml_add("EpiNameAddressDetails", $pankkitiedot["pankkinimi1"], $tootfinvoice, 35, 2);
      }
      else {
        xml_add("EpiNameAddressDetails", $yhtiorow['nimi'], $tootfinvoice, 35, 2);
        xml_add("EpiBei", tulosta_ytunnus($yhtiorow['ytunnus'], $yhtiorow['maa']), $tootfinvoice, 11, 8);
      }

      xml_add("EpiAccountID IdentificationSchemeName=\"IBAN\"", str_replace(" ", "", $pankkitiedot["pankkiiban1"]), $tootfinvoice, 34);

      fputs($tootfinvoice, "</EpiBeneficiaryPartyDetails>\r\n");
      fputs($tootfinvoice, "</EpiPartyDetails>\r\n");

      fputs($tootfinvoice, "<EpiPaymentInstructionDetails>\r\n");
      xml_add("EpiRemittanceInfoIdentifier IdentificationSchemeName=\"SPY\"", spyconv($lasrow['viite']), $tootfinvoice, 35, 2);
      xml_add("EpiInstructedAmount AmountCurrencyIdentifier=\"$lasrow[valkoodi]\"", pp($lasrow['summa']), $tootfinvoice);
      xml_add("EpiCharge ChargeOption=\"SHA\"", "SHA", $tootfinvoice);
      xml_add("EpiDateOptionDate Format=\"CCYYMMDD\"", vlas_dateconv($lasrow['erpcm']), $tootfinvoice);
      fputs($tootfinvoice, "</EpiPaymentInstructionDetails>\r\n");
      fputs($tootfinvoice, "</EpiDetails>\r\n");

      if ($yhtiorow["verkkolasku_lah"] == "apix") {
        xml_add("InvoiceUrlNameText", "APIX_PDFFILE", $tootfinvoice);

        if ($liitteita) {
          xml_add('InvoiceUrlNameText', 'APIX_ATTACHMENT', $tootfinvoice);
        }

        xml_add("InvoiceUrlText", "file://Apix_invoice_$lasrow[laskunro].pdf", $tootfinvoice);

        if ($liitteita) {
          xml_add('InvoiceUrlText', "file://Apix_attachments_{$lasrow["laskunro"]}.zip", $tootfinvoice);
        }
      }

      fputs($tootfinvoice, "</Finvoice>\r\n");
    }
  }
}
