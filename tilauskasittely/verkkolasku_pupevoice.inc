<?php

if (!function_exists('pupevoice_otsik')) {
  function pupevoice_otsik($tootxml, $lasrow, $kieli, $pankkitiedot, $masrow, $myyrow, $tyyppi, $toimaikarow) {
    global $yhtiorow;

    $fstat = fstat($tootxml);

    if ($fstat["size"] == 0) {
      //tehdään verkkolasku oliot
      fputs($tootxml, "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n");
      fputs($tootxml, "<Pupevoice Version=\"0.99\">\n");
    }

    // Kirjotetaan laskun tietoja pupevoice XML-muotoon
    fputs($tootxml, "<Invoice>\n");
    fputs($tootxml, "<CHN>\n");

    xml_add("Paper",                 substr($lasrow['chn'], 0, 1),   $tootxml);
    xml_add("Einvoice",               substr($lasrow['chn'], 1, 1),   $tootxml);
    xml_add("Edi",                   substr($lasrow['chn'], 2, 1),   $tootxml);
    xml_add("Email",                 $yhtiorow['alert_email'],    $tootxml);

    if (strtoupper($yhtiorow['kieli']) == 'SE' or $kieli == "SE") {
      xml_add("Language", "SV", $tootxml);
    }
    elseif (strtoupper($yhtiorow['kieli']) == 'EE' or $kieli == "EE") {
      xml_add("Language", "ET", $tootxml);
    }

    fputs($tootxml, "</CHN>\n");
    fputs($tootxml, "<SellerPartyInformation>\n");

    // yhtiötiedot
    $y_tiedot_ok  = FALSE;
    $y_nimi     = $lasrow["yhtio_nimi"];
    $y_osoite     = $lasrow["yhtio_osoite"];
    $y_postino     = $lasrow["yhtio_postino"];
    $y_postitp     = $lasrow["yhtio_postitp"];
    $y_maa       = $lasrow["yhtio_maa"];
    $y_kotipaikka  = $lasrow["yhtio_kotipaikka"];
    $y_vatnumero  = $lasrow["yhtio_ovttunnus"]; // Huom tätä ei tule muuttaa, Itella tunnustaa lähettäjän tästä kentästä.
    $y_iban     = $yhtiorow['pankkiiban1'];
    $y_swift     = $yhtiorow['pankkiswift1'];

    if ((int) $lasrow["yhtio_toimipaikka"] != 0) {
      // haetaan toimipaikan tiedot
      $alhqur = "SELECT puhelin, fax, email, www from yhtion_toimipaikat where yhtio='$lasrow[yhtio]' and tunnus='$lasrow[yhtio_toimipaikka]'";
      $alhire = pupe_query($alhqur);

      if (mysql_num_rows($alhire) == 1) {
        $alhiro = mysql_fetch_assoc($alhire);

        $y_tiedot_ok  = TRUE;
        $y_puhelin    = $alhiro['puhelin'];
        $y_fax      = $alhiro['fax'];
        $y_email    = $alhiro['email'];
        $y_www      = $alhiro['www'];
      }
    }

    // jos meillä on lasku menossa ulkomaille ja se laskutetaan eri ovttunnuksella
    if ($lasrow["yhtio_ovttunnus"] != "" and $lasrow["yhtio_ovttunnus"] != $yhtiorow["ovttunnus"]) {
      // haetaan toimipaikan tiedot
      $alhqur = "SELECT puhelin, fax, email, www from yhtion_toimipaikat where yhtio='$lasrow[yhtio]' and vat_numero='$lasrow[yhtio_ovttunnus]'";
      $alhire = pupe_query($alhqur);

      if (mysql_num_rows($alhire) == 1) {
        $alhiro = mysql_fetch_assoc($alhire);

        $y_tiedot_ok  = TRUE;
        $y_puhelin    = $alhiro['puhelin'];
        $y_fax      = $alhiro['fax'];
        $y_email    = $alhiro['email'];
        $y_www      = $alhiro['www'];
      }
    }

    // jos ei olla haettu muualta, niin haetaan tiedot yhtiöltä
    if (!$y_tiedot_ok) {
      // haetaan toimipaikan tiedot
      $alhqur = "SELECT puhelin, fax, email, www from yhtio where yhtio='$lasrow[yhtio]'";
      $alhire = pupe_query($alhqur);

      if (mysql_num_rows($alhire) == 1) {
        $alhiro = mysql_fetch_assoc($alhire);

        $y_puhelin    = $alhiro['puhelin'];
        $y_fax      = $alhiro['fax'];
        $y_email    = $alhiro['email'];
        $y_www      = $alhiro['www'];
      }
    }

    // haetaan asiakkaan tiedot
    $asquery = "SELECT * from asiakas where yhtio='$lasrow[yhtio]' and tunnus='$lasrow[liitostunnus]'";
    $asiakasre = pupe_query($asquery);
    $asiakasrow = mysql_fetch_assoc($asiakasre);

    xml_add("SellerPartyIdentifier",         $yhtiorow['ovttunnus'],                          $tootxml);
    xml_add("SellerPartyDomicile",           $y_kotipaikka,                              $tootxml);
    xml_add("SellerPartyVat",             $y_vatnumero,                              $tootxml);
    xml_add("SellerOrganisationName",         $y_nimi,                                 $tootxml);
    xml_add("SellerStreetName",           $y_osoite,                                 $tootxml);
    xml_add("SellerPostCode",             $y_postino,                               $tootxml);
    xml_add("SellerTownName",             $y_postitp,                               $tootxml);
    xml_add("SellerCountryName",           $y_maa,                                  $tootxml);
    xml_add("SellerPhoneNumber",           $y_puhelin,                               $tootxml);
    xml_add("SellerFaxNumber",             $y_fax,                                 $tootxml);
    xml_add("SellerEmail",               $y_email,                                 $tootxml);

    xml_add("SellerAccountName1",           $pankkitiedot['pankkinimi1'],                      $tootxml);
    xml_add("SellerAccountID1",           $pankkitiedot['pankkitili1'],                      $tootxml);
    xml_add("SellerAccountIBAN1",           str_replace(" ", "", $pankkitiedot["pankkiiban1"]),            $tootxml);
    xml_add("SellerAccountBIC1",           $pankkitiedot["pankkiswift1"],                      $tootxml);

    xml_add("SellerAccountName2",           $pankkitiedot['pankkinimi2'],                      $tootxml);
    xml_add("SellerAccountID2",           $pankkitiedot['pankkitili2'],                      $tootxml);
    xml_add("SellerAccountIBAN2",           str_replace(" ", "", $pankkitiedot["pankkiiban2"]),            $tootxml);
    xml_add("SellerAccountBIC2",           $pankkitiedot["pankkiswift2"],                      $tootxml);

    xml_add("SellerAccountName3",           $pankkitiedot['pankkinimi3'],                      $tootxml);
    xml_add("SellerAccountID3",           $pankkitiedot['pankkitili3'],                      $tootxml);
    xml_add("SellerAccountIBAN3",           str_replace(" ", "", $pankkitiedot["pankkiiban3"]),            $tootxml);
    xml_add("SellerAccountBIC3",           $pankkitiedot["pankkiswift3"],                      $tootxml);

    xml_add("SellerVatRegistrationText",       t("Alv.Rek"),                               $tootxml);
    xml_add("SellerContactPerson",           $myyrow['nimi'],                            $tootxml);

    fputs($tootxml, "</SellerPartyInformation>\n");
    fputs($tootxml, "<InvoiceDetails>\n");

    xml_add("InvoiceType",              $tyyppi,                       $tootxml);
    xml_add("InvoicedPartyIdentifier",        tulosta_ytunnus($lasrow['ytunnus']),                 $tootxml);
    xml_add("InvoicedPartyOVT",            $lasrow['ovttunnus'],                $tootxml);
    xml_add("InvoicedPartyEBA",            $lasrow['verkkotunnus'],               $tootxml);
    xml_add("InvoicedCustNumber",          $asiakasrow['asiakasnro'],               $tootxml);
    xml_add("InvoicedOrganisationName",        trim($lasrow['nimi']." ".$lasrow['nimitark']),     $tootxml);
    xml_add("InvoicedStreetName",          $lasrow['osoite'],                   $tootxml);
    xml_add("InvoicedPostCode",            $lasrow['postino'],                  $tootxml);
    xml_add("InvoicedTownName",            $lasrow['postitp'],                  $tootxml);
    xml_add("InvoicedCountryName",          $lasrow['maa'],                   $tootxml);
    xml_add("InvoiceNumber",            $lasrow['laskunro'],                $tootxml);
    xml_add("InvoiceCurrency",            $lasrow['valkoodi'],                $tootxml);
    xml_add("InvoicePaymentReference",        $lasrow['viite'],                   $tootxml);
    xml_add("InvoiceDate",              vlas_dateconv($lasrow['tapvm']),           $tootxml);
    xml_add("OrderIdentifier",            substr($lasrow['asiakkaan_tilausnumero'], 0, 35),  $tootxml);
    xml_add("DeliveredPartyName",          trim($lasrow['toim_nimi']." ".$lasrow['toim_nimitark']),  $tootxml);
    xml_add("DeliveredPartyStreetName",        $lasrow['toim_osoite'],               $tootxml);
    xml_add("DeliveredPartyPostCode",        $lasrow['toim_postino'],               $tootxml);
    xml_add("DeliveredPartyTownName",        $lasrow['toim_postitp'],               $tootxml);
    xml_add("DeliveredPartyCountryName",      $lasrow['toim_maa'],                 $tootxml);
    xml_add("DeliveredPartyOVT",          $lasrow['toim_ovttunnus'],               $tootxml);

    $query = "SELECT *
              FROM laskun_lisatiedot
              WHERE yhtio = '$lasrow[yhtio]'
              AND otunnus = '$lasrow[tunnus]'";
    $lasosres = pupe_query($query);
    $lasosrow = mysql_fetch_assoc($lasosres);

    if ($lasosrow['laskutus_nimi'] != '') {
      xml_add("InvoiceRecipientOrganisationName",  trim($lasosrow['laskutus_nimi']." ".$lasosrow['laskutus_nimitark']), $tootxml);
      xml_add("InvoiceRecipientStreetName",     $lasosrow['laskutus_osoite'],            $tootxml);
      xml_add("InvoiceRecipientTownName",      $lasosrow['laskutus_postitp'],            $tootxml);
      xml_add("InvoiceRecipientPostCodeIdentifier", $lasosrow['laskutus_postino'],          $tootxml);
      xml_add("InvoiceRecipientCountryCode",    $lasosrow['laskutus_maa'],              $tootxml);
    }

    // Nämä solut lisätään vain jos pupevoice on lähdössä servinettiin
    if ($yhtiorow['verkkolasku_lah'] == "servinet") {

      //haetaan toimitustavan tiedot
      $query = "SELECT *
                FROM toimitustapa
                where yhtio ='$lasrow[yhtio]'
                and selite = '$lasrow[toimitustapa]'";
      $terminaali_result = pupe_query($query);
      $terminaali_row = mysql_fetch_assoc($terminaali_result);

      if (!empty($terminaali_row['toim_ovttunnus'])) {
        xml_add("CrossdockPartyName",         trim($terminaali_row['toim_nimi']." ".$terminaali_row['toim_nimitark']),       $tootxml);
        xml_add("CrossdockPartyStreetName",   $terminaali_row['toim_osoite'],     $tootxml);
        xml_add("CrossdockPartyPostCode",     $terminaali_row['toim_postino'],    $tootxml);
        xml_add("CrossdockPartyTownName",     $terminaali_row['toim_postitp'],    $tootxml);
        xml_add("CrossdockPartyCountryCode",  $terminaali_row['toim_maa'],        $tootxml);
        xml_add("CrossdockPartyOVT",          $terminaali_row['toim_ovttunnus'],  $tootxml);
      }
    }

    xml_add("DueDate",                vlas_dateconv($lasrow['erpcm']),          $tootxml);
    xml_add("InvoiceTotalVatExcludedAmount",    $lasrow['arvo'],                  $tootxml);
    xml_add("InvoiceTotalVatAmount",        round($lasrow['summa']-$lasrow['arvo'], 2),      $tootxml);
    xml_add("InvoiceTotalVatIncludedAmount",    $lasrow['summa'],                  $tootxml);
    xml_add("InvoiceTotalRounding",          $lasrow['pyoristys'] * -1,              $tootxml);
    xml_add("PaymentTerms",              $masrow['teksti'],                  $tootxml);
    xml_add("PaymentOverDueFinePercent",      $lasrow['viikorkopros'],               $tootxml);
    xml_add("InvoiceDeliveryMethod",        $lasrow['toimitustapa'],               $tootxml);
    xml_add("InvoiceDeliveryTerm",          $lasrow['toimitusehto'],               $tootxml);

    //Laitetaan kassa-alennustietoja
    xml_add("CashDiscountDate",            vlas_dateconv($lasrow['kapvm']),           $tootxml);
    xml_add("CashDiscountBaseAmount",        $lasrow['summa'],                   $tootxml);
    xml_add("CashDiscountPercent",          $masrow['kassa_alepros'],               $tootxml);
    xml_add("CashDiscountAmount",          $lasrow['kasumma'],                   $tootxml);
    xml_add("InvoiceReferenceFreeText",        substr($lasrow['viesti'], 0, 35),          $tootxml);
    xml_add("InvoiceFreeText",            $lasrow['sisviesti1'],                $tootxml);
  }
}

if (!function_exists('pupevoice_alvierittely')) {
  function pupevoice_alvierittely($tootxml, $alvrow) {

    fputs($tootxml, "<VatSpecificationDetails>\n");
    xml_add("VatRatePercent",          $alvrow['alv'], $tootxml);
    xml_add("VatBaseAmount",          $alvrow['rivihinta'], $tootxml);
    xml_add("VatRateAmount",          $alvrow['alvrivihinta'], $tootxml);
    fputs($tootxml, "</VatSpecificationDetails>\n");
  }
}

if (!function_exists('pupevoice_rivi')) {
  function pupevoice_rivi($tootxml, $tilrow, $vatamount) {
    global $yhtiorow, $kukarow;

    // haetaan asiakkaan tilausnumero
    $astilno_query = "SELECT asiakkaan_tilausnumero
                      FROM lasku
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus = '{$tilrow['otunnus']}'";
    $astilno_res = pupe_query($astilno_query);
    $astilno_row = mysql_fetch_assoc($astilno_res);

    $tilaustunnus = empty($astilno_row['asiakkaan_tilausnumero']) ? $tilrow['otunnus'] : substr($astilno_row['asiakkaan_tilausnumero'], 0, 35);
    fputs($tootxml, "<Row>\n");
    xml_add("OrderIdentifier",          $tilaustunnus,             $tootxml); // tämä on tilauksen numero meillä, ei kuulu standardiin mutta se on pakko lisätä
    xml_add("RowDeliveryIdentifier",      $tilrow['otunnus'],             $tootxml); // tämä on tilauksen numero meillä, tämä taas kuuluu standardiin
    xml_add("ArticleIdentifier",        $tilrow['tuoteno'],             $tootxml);
    xml_add("ArticleEANIdentifier",        $tilrow['eankoodi'],             $tootxml);
    xml_add("ArticleName",            $tilrow['nimitys'],             $tootxml);

    $toim_query = "SELECT toim_tuoteno, if (jarjestys = 0, 9999, jarjestys) sorttaus
                   FROM tuotteen_toimittajat
                   WHERE yhtio = '$kukarow[yhtio]'
                   AND tuoteno = '$tilrow[tuoteno]'
                   ORDER BY sorttaus
                   LIMIT 1";
    $toim_tuoteno_res = pupe_query($toim_query);
    $toim_tuoteno_row = mysql_fetch_assoc($toim_tuoteno_res);

    $toim_tuoteno = trim($toim_tuoteno_row['toim_tuoteno']) != '' ? $toim_tuoteno_row['toim_tuoteno'] : $tilrow['tuoteno'];

    xml_add("ManufacturerArticleIdentifier",  $toim_tuoteno,                $tootxml); // toimittajan tuotekoodi

    xml_add("DeliveredQuantity",        $tilrow['kpl'],               $tootxml);
    xml_add("DeliveredQuantityUnitCode",    $tilrow['yksikko'],              $tootxml);
    xml_add("DeliveryDate",            vlas_dateconv($tilrow['toimitettuaika']),  $tootxml);
    xml_add("UnitPrice",            $tilrow['hinta'],               $tootxml);
    xml_add("RowIdentifier",          $tilrow['tilaajanrivinro'],         $tootxml); // tänne laitetaan asiakkaan rivinumero, niin saavat parseroida senkin laskuista

    $alennukset_yhteensa = generoi_alekentta_php($tilrow, 'M', 'plain');

    xml_add("RowDiscountPercent",        $alennukset_yhteensa,            $tootxml);

    xml_add("RowVatRatePercent",        $tilrow['alv'],               $tootxml);
    xml_add("RowVatAmount",            $vatamount,                 $tootxml); // veron määrä
    xml_add("RowTotalVatExcludedAmount",    $tilrow['rivihinta'],             $tootxml); // veroton rivihinta
    xml_add("RowTotalVatIncludedAmount",    $tilrow["rivihinta_verollinen"],       $tootxml); // verollinen rivihinta

    // Jos tämä on tän tilauksen vika rivi
    if ($tilrow['tuoteno'] != $yhtiorow["kuljetusvakuutus_tuotenumero"] and $tilrow['tuoteno'] != $yhtiorow["laskutuslisa_tuotenumero"] and $tilrow['seuraava_otunnus'] != $tilrow["otunnus"]) {
      $lisakomm = "";

      // Laitetaan tilausviite tähän
      if ($tilrow["laskuviesti"] != "") {
        $lisakomm .= $tilrow["laskuviesti"];
      }

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

      if (trim($lisakomm) != "") {
        // Siivotaan myös lisäkommentti
        $lisakomm = pupesoft_invoicestring($lisakomm);

        $tilrow["kommentti"] = trim($lisakomm)."|".trim($tilrow["kommentti"]);
      }
    }

    xml_add("RowFreeText", substr(trim(trim($tilrow["kommentti"]), "|"), 0, 512),    $tootxml);
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
