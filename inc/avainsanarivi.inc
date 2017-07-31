<?php

$ulos   = "";
$jatko  = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

$_selite = (mysql_field_name($result, $i) == "selite");
$_selitetark = (mysql_field_name($result, $i) == "selitetark");
$_selitetark_2 = (mysql_field_name($result, $i) == "selitetark_2");
$_selitetark_3 = (mysql_field_name($result, $i) == "selitetark_3");
$_selitetark_4 = (mysql_field_name($result, $i) == "selitetark_4");
$_selitetark_5 = (mysql_field_name($result, $i) == "selitetark_5");
$_jarjestys = (mysql_field_name($result, $i) == "jarjestys");

// Näitä kenttiä ei oletuksena näytetä
if ($_selitetark_5 or
  $_selitetark_4 or
  $_selitetark_3 or
  $_selitetark_2) {
  $tyyppi = 0;
}

if ($_selitetark_5 and
  $avain_sel['MYSQLALIAS'] == "SELECTED" or
  $avain_sel["SAHKTILTUN"] == "SELECTED" or
  $avain_sel['TYOM_TYOKENTAT'] == "SELECTED" or
  $avain_sel['TYOM_TYOSTATUS'] == "SELECTED") {
  $tyyppi = 1;
}

if ($_selitetark_4 and
  ($avain_sel['ASIAKASRYHMA'] == "SELECTED" or
    $avain_sel['HENKILO_OSASTO'] == "SELECTED" or
    $avain_sel['INVEN_LAJI'] == "SELECTED" or
    $avain_sel['MYSQLALIAS'] == "SELECTED" or
    $avain_sel['TRY'] == "SELECTED" or
    $avain_sel["SAHKTILTUN"] == "SELECTED" or
    $avain_sel['TYOM_TYOJONO'] == "SELECTED" or
    $avain_sel['TYOM_TYOKENTAT'] == "SELECTED" or
    $avain_sel['TYOM_TYOSTATUS'] == "SELECTED")) {
  $tyyppi = 1;
}

if ($_selitetark_3 and
  ($avain_sel['ASAVAINSANA'] == "SELECTED" or
    $avain_sel['ASIAKASLUOKKA'] == "SELECTED" or
    $avain_sel['HENKILO_OSASTO'] == "SELECTED" or
    $avain_sel['INVEN_LAJI'] == "SELECTED" or
    $avain_sel['MYSQLALIAS'] == "SELECTED" or
    $avain_sel['OSASTO'] == "SELECTED" or
    $avain_sel['PAIKALLISVARAST'] == "SELECTED" or
    $avain_sel['RAHTIKIRJA'] == "SELECTED" or
    $avain_sel['SARJANUMERON_LI'] == "SELECTED" or
    $avain_sel['SQLDBQUERY'] == "SELECTED" or
    $avain_sel['TOIMEHTO'] == "SELECTED" or
    $avain_sel['TRY'] == "SELECTED" or
    $avain_sel['TYOM_PRIORIT'] == "SELECTED" or
    $avain_sel['TYOM_TYOJONO'] == "SELECTED" or
    $avain_sel['TYOM_TYOKENTAT'] == "SELECTED" or
    $avain_sel['TYOM_TYOSTATUS'] == "SELECTED" or
    $avain_sel["SAHKTILTUN"] == "SELECTED" or
    $avain_sel['VARASTOLUOKKA'] == "SELECTED" or
    $avain_sel['VARTOIMTULOSTIN'] == "SELECTED" or
    $avain_sel['TOIMVAHVISTUS'] == "SELECTED" or
    $avain_sel["MYYNTITILASTO"] == "SELECTED" or
    $avain_sel['Y'] == "SELECTED")) {
  $tyyppi = 1;
}

if ($_selitetark_2 and
  ($avain_sel['ALV'] == "SELECTED" or
    $avain_sel['ALVULK'] == "SELECTED" or
    $avain_sel['TOIMMYYNTI'] == "SELECTED" or
    $avain_sel['ASAVAINSANA'] == "SELECTED" or
    $avain_sel['ASIAKASLUOKKA'] == "SELECTED" or
    $avain_sel['ASIAKASOSASTO'] == "SELECTED" or
    $avain_sel['ASIAKASRYHMA'] == "SELECTED" or
    $avain_sel['DPAVAINSANALAJI'] == "SELECTED" or
    $avain_sel['HENKILO_OSASTO'] == "SELECTED" or
    $avain_sel['INVEN_LAJI'] == "SELECTED" or
    $avain_sel['KAANTALVVIESTI'] == "SELECTED" or
    $avain_sel['KALETAPA'] == "SELECTED" or
    $avain_sel['KASSA'] == "SELECTED" or
    $avain_sel['KUKAASEMA'] == "SELECTED" or
    $avain_sel['LAHETETYYPPI'] == "SELECTED" or
    $avain_sel['LASKUTUS_SAATE'] == "SELECTED" or
    $avain_sel['LITETY'] == "SELECTED" or
    $avain_sel['MERCASVHKER'] == "SELECTED" or
    $avain_sel['MYSQLALIAS'] == "SELECTED" or
    $avain_sel['OLETUSPAIKKA'] == "SELECTED" or
    $avain_sel['OSASTO'] == "SELECTED" or
    $avain_sel['OSTOTILAUS_SAAT'] == "SELECTED" or
    $avain_sel['PAIKALLISVARAST'] == "SELECTED" or
    $avain_sel['PIIRI'] == "SELECTED" or
    $avain_sel['RAHTIKIRJA'] == "SELECTED" or
    $avain_sel['SARJANUMERON_LI'] == "SELECTED" or
    $avain_sel['SQLDBQUERY'] == "SELECTED" or
    $avain_sel['TOIMEHTO'] == "SELECTED" or
    $avain_sel['TOIMVAHVISTUS'] == "SELECTED" or
    $avain_sel['TOIMTAPAKV'] == "SELECTED" or
    $avain_sel['TRY'] == "SELECTED" or
    $avain_sel['TUOTEMERKKI'] == "SELECTED" or
    $avain_sel['TYOM_PRIORIT'] == "SELECTED" or
    $avain_sel['TYOM_TYOJONO'] == "SELECTED" or
    $avain_sel['TYOM_TYOKENTAT'] == "SELECTED" or
    $avain_sel['TYOM_TYOLINJA'] == "SELECTED" or
    $avain_sel['TYOM_TYOSTATUS'] == "SELECTED" or
    $avain_sel['VALMISTUSLINJA'] == "SELECTED" or
    $avain_sel['VARASTOLUOKKA'] == "SELECTED" or
    $avain_sel['VARTOIMTULOSTIN'] == "SELECTED" or
    $avain_sel["SAHKTILTUN"] == "SELECTED" or
    $avain_sel["OSTOTIL_TILTYYP"] == "SELECTED" or
    $avain_sel["RELEX_TUOTEPAR"] == "SELECTED" or
    $avain_sel["HAE_JA_SELAA"] == "SELECTED" or
    $avain_sel["MYYNTITILASTO"] == "SELECTED" or
    $avain_sel['Y'] == "SELECTED")) {
  $tyyppi = 1;
}

if (mysql_field_name($result, $i) == "perhe") {

  // Perhetunnus
  if ($lukitse_avaimeen != "") {
    $trow[$i] = $perhe = $lukitse_avaimeen;
  }
  elseif ($trow[$i] == 0) {
    $query = "SELECT max(perhe) perhe
              FROM avainsana
              WHERE yhtio = '$kukarow[yhtio]'";
    $pres = pupe_query($query);
    $perse = mysql_fetch_array($pres);

    $trow[$i] = $perhe = ($perse["perhe"]+1);

    //Päivitetään tämä jo tässä vaiheessa
    if ($tunnus > 0) {
      $query = "UPDATE avainsana
                SET perhe = '$perhe'
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$tunnus'";
      $pres = pupe_query($query);
    }
  }
  else {
    $perhe = $trow[$i];
  }

  $tyyppi = 4;
}

// Maksuehtojen js toimitustapojen kieliveriot
if ($_selite and $lukitse_avaimeen != "" and ($lukitse_laji == "TOIMTAPAKV" or $lukitse_laji == "MAKSUEHTOKV" or $lukitse_laji == "PAKKAUSKV")) {
  $ulos = "<td><input type='hidden' name='$nimi' value='$lukitse_avaimeen'>$lukitse_avaimeen</td>";
  $jatko = 0;
}

if ($_selite) {
  $avain_selite   = "SELECTED";
}

if (mysql_field_name($result, $i) == "laji") {

  $al_laji         = trim(strtoupper($trow[$i]));
  $lukitse_laji       = trim(strtoupper($lukitse_laji));
  $avain_sel[$trow[$i]] = "SELECTED";
  $mysqlaliasarraysetti = $trow[$i];

  $ulos = "<td><select name='$nimi' onchange='submit();'>";

  if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Tuotteiden avainsanat")."'>";
  if ($lukitse_laji== "" or $lukitse_laji == "Y") $ulos .= "<option value='Y' $avain_sel[Y]>".t("Yksikko")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TRY") $ulos .= "<option value='TRY' $avain_sel[TRY]>".t("Tuoteryhmä")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "PS_ALA_TRY") $ulos .= "<option value='PS_ALA_TRY' $avain_sel[PS_ALA_TRY]>" . t("PupeShop alaryhmä") . "</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "THR") $ulos .= "<option value='THR' $avain_sel[THR]>".t("Tuotehinnastoryhmä")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "OSASTO") $ulos .= "<option value='OSASTO' $avain_sel[OSASTO]>".t("Tuoteosasto")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TUOTEMERKKI") $ulos .= "<option value='TUOTEMERKKI' $avain_sel[TUOTEMERKKI]>".t("Tuotemerkki")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "MALLI") $ulos .= "<option value='MALLI' $avain_sel[MALLI]>".t("Tuotemalli")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "MALLITARKENNE") $ulos .= "<option value='MALLITARKENNE' $avain_sel[MALLITARKENNE]>".t("Tuotemallitarkenne")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TUOTEULK") $ulos .= "<option value='TUOTEULK' $avain_sel[TUOTEULK]>".t("Tuotteiden avainsanojen laji")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "VARASTOLUOKKA") $ulos .= "<option value='VARASTOLUOKKA' $avain_sel[VARASTOLUOKKA]>".t("Varastoluokka")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "VARASTORYHMA") $ulos .= "<option value='VARASTORYHMA' $avain_sel[VARASTORYHMA]>".t("Varastoryhmä")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "SARJANUMERON_LI") $ulos .= "<option value='SARJANUMERON_LI' $avain_sel[SARJANUMERON_LI]>".t("Sarjanumeron lisätieto")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "PARAMETRI") $ulos .= "<option value='PARAMETRI' $avain_sel[PARAMETRI]>".t("Tuotteen parametri")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "LISATIETO") $ulos .= "<option value='LISATIETO' $avain_sel[LISATIETO]>".t("Tuotteen lisätieto")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TARRATYYPPI") $ulos .= "<option value='TARRATYYPPI' $avain_sel[TARRATYYPPI]>".t("Tuotteen tarratyyppi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "VALMISTUSLINJA") $ulos .= "<option value='VALMISTUSLINJA' $avain_sel[VALMISTUSLINJA]>".t("Tuotteen valmistuslinja")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "HAMMASTUS") $ulos .= "<option value='HAMMASTUS' $avain_sel[HAMMASTUS]>".t("Tuotteen hammastus")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "OLETUSPAIKKA") $ulos .= "<option value='OLETUSPAIKKA' $avain_sel[OLETUSPAIKKA]>".t("Tuoteryhmän tai tuotestatuksen oletuspaikka")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "ELINKAARI") $ulos .= "<option value='ELINKAARI' {$avain_sel['ELINKAARI']}>".t("Tuotteen elinkaarityyppi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KOPIOITUOTE") $ulos .= "<option value='KOPIOITUOTE' {$avain_sel['KOPIOITUOTE']}>".t("Kopioi tuote parametri")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "MAARYHMA") $ulos .= "<option value='MAARYHMA' {$avain_sel['MAARYHMA']}>".t("Sallittu maaryhmä")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "WOO_FIELD") $ulos .= "<option value='WOO_FIELD' {$avain_sel['WOO_FIELD']}>".t("WooCommercen kenttä")."</option>";
  if ($lukitse_laji== "") $ulos .= "</optgroup>";

  if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Asiakkaiden avainsanat")."'>";
  if ($lukitse_laji== "" or $lukitse_laji == "ASIAKASLUOKKA") $ulos .= "<option value='ASIAKASLUOKKA' $avain_sel[ASIAKASLUOKKA]>".t("Asiakasluokka")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "ASIAKASOSASTO") $ulos .= "<option value='ASIAKASOSASTO' $avain_sel[ASIAKASOSASTO]>".t("Asiakasosasto")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "ASIAKASRYHMA") $ulos .= "<option value='ASIAKASRYHMA' $avain_sel[ASIAKASRYHMA]>".t("Asiakasryhma")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "ASIAKASTILA") $ulos .= "<option value='ASIAKASTILA' $avain_sel[ASIAKASTILA]>".t("Asiakastila")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "PIIRI") $ulos .= "<option value='PIIRI' $avain_sel[PIIRI]>".t("Asiakkaan piiri")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "ASAVAINSANA") $ulos .= "<option value='ASAVAINSANA' $avain_sel[ASAVAINSANA]>".t("Asiakkaan avainsanojen laji")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "EXTASAVAINSANA") $ulos .= "<option value='EXTASAVAINSANA' $avain_sel[EXTASAVAINSANA]>".t("Extranet-asiakkaan avainsanojen laji")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "LAANI") $ulos .= "<option value='LAANI' $avain_sel[LAANI]>".t("Lääni")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KUNTA") $ulos .= "<option value='KUNTA' $avain_sel[KUNTA]>".t("Kunta")."</option>";
  if ($lukitse_laji== "") $ulos .= "</optgroup>";

  if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Yhtiön avainsanat")."'>";
  if ($lukitse_laji== "" or $lukitse_laji == "TV") $ulos .= "<option value='TV' $avain_sel[TV]>".t("Tilausvahvistus")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TOIMVAHVISTUS") $ulos .= "<option value='TOIMVAHVISTUS' $avain_sel[TOIMVAHVISTUS]>".t("Toimitusvahvistus")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "MYSQLALIAS") $ulos .= "<option value='MYSQLALIAS' $avain_sel[MYSQLALIAS]>".t("Tietokantasarakkeen nimialias")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TOIMITUSTAPA_OS") $ulos .= "<option value='TOIMITUSTAPA_OS' $avain_sel[TOIMITUSTAPA_OS]>".t("Toimitustapa ostolle (kuljetus)")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KUKAASEMA") $ulos .= "<option value='KUKAASEMA' $avain_sel[KUKAASEMA]>".t("Käytäjän asema")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "ALVULK") $ulos .= "<option value='ALVULK' $avain_sel[ALVULK]>".t("Ulkomaan ALV%")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "ALV") $ulos .= "<option value='ALV' $avain_sel[ALV]>".t("ALV%")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "SEURANTA") $ulos .= "<option value='SEURANTA' $avain_sel[SEURANTA]>".t("Tilauksen seurantaluokka")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TOIMEHTO") $ulos .= "<option value='TOIMEHTO' $avain_sel[TOIMEHTO]>".t("Toimitusehto")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "HENKILO_OSASTO") $ulos .= "<option value='HENKILO_OSASTO' $avain_sel[HENKILO_OSASTO]>".t("Henkilöosasto")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KVERITTELY") $ulos .= "<option value='KVERITTELY' $avain_sel[KVERITTELY]>".t("Kulunvalvonnan erittely")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KONEENOSA") $ulos .= "<option value='KONEENOSA' $avain_sel[KONEENOSA]>".t("Koneenosa")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "SAHKTILTUN") $ulos .= "<option value='SAHKTILTUN' {$avain_sel['SAHKTILTUN']}>".t("Sähköisen tilausliitännän tunnukset")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "VEHT_RAHTI") $ulos .= "<option value='VEHT_RAHTI' {$avain_sel['VEHT_RAHTI']}>".t("Vaihtoehtoinen rahtituotenumero")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "FINV_TULPALVELU") $ulos .= "<option value='FINV_TULPALVELU' {$avain_sel['FINV_TULPALVELU']}>".t("Finvoice senderintermediator tulostuspalvelu")."</option>";
  if ($lukitse_laji== "") $ulos .= "</optgroup>";

  if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Varaston avainsanat")."'>";
  if ($lukitse_laji== "" or $lukitse_laji == "KERAYSLISTA") $ulos .= "<option value='KERAYSLISTA' $avain_sel[KERAYSLISTA]>".t("Keräyslista")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "LAHETETYYPPI") $ulos .= "<option value='LAHETETYYPPI' $avain_sel[LAHETETYYPPI]>".t("Lähetetyyppi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "RAHTIKIRJA") $ulos .= "<option value='RAHTIKIRJA' $avain_sel[RAHTIKIRJA]>".t("Rahtikirjatyyppi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TOIMTAPAKV") $ulos .= "<option value='TOIMTAPAKV' $avain_sel[TOIMTAPAKV]>".t("Toimitustavan kieliversio")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TOIMTAPALP") $ulos .= "<option value='TOIMTAPALP' {$avain_sel['TOIMTAPALP']}>".t("Toimitustavan lajittelupiste")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TOIMTAPAVKN") $ulos .= "<option value='TOIMTAPAVKN' {$avain_sel['TOIMTAPAVKN']}>".t("Toimitustavan verkkokauppanimi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TERMINAALIALUE") $ulos .= "<option value='TERMINAALIALUE' $avain_sel[TERMINAALIALUE]>".t("Varaston terminaalialue")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "SSCC") $ulos .= "<option value='SSCC' $avain_sel[SSCC]>".t("SSCC-koodi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "GS1_SSCC") $ulos .= "<option value='GS1_SSCC' {$avain_sel['GS1_SSCC']}>".t("GS1 SSCC")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "SIIRTO_VASTNRO") $ulos .= "<option value='SIIRTO_VASTNRO' {$avain_sel['SIIRTO_VASTNRO']}>".t("Siirtolistan vastaanottonumero")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "PAKKAUSKV") $ulos .= "<option value='PAKKAUSKV' $avain_sel[PAKKAUSKV]>".t("Pakkaustiedon kieliversio")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "INVASTEPAIKKA") $ulos .= "<option value='INVASTEPAIKKA' {$avain_sel['INVASTEPAIKKA']}>".t("Inventointiaste-raportin hylkäämät tuotepaikat")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "SIIRTOVARASTOT") $ulos .= "<option value='SIIRTOVARASTOT' {$avain_sel['SIIRTOVARASTOT']}>".t("Varastosta-varastoon myyntitilauksesta siirtolista sallinta")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KERAYSERA_TK") $ulos .= "<option value='KERAYSERA_TK' $avain_sel[KERAYSERA_TK]>".t("Käyttäjän oikeus tulostaa kaikki keräyserät varastossa")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TOIMMYYNTI") $ulos .= "<option value='TOIMMYYNTI' {$avain_sel['TOIMMYYNTI']}>".t("Toimipaikan myyntioikeus")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "VARTOIMTULOSTIN") $ulos .= "<option value='VARTOIMTULOSTIN' {$avain_sel['VARTOIMTULOSTIN']}>".t("Varaston toimipaikan tulostin")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "OPTISCANKOM") $ulos .= "<option value='OPTISCANKOM' $avain_sel[OPTISCANKOM]>".t("Optiscan oletuskeräyskommentti")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "ULKJARJLAHETE") $ulos .= "<option value='ULKJARJLAHETE' {$avain_sel['ULKJARJLAHETE']}>".t("Ulkoisen varastojärjestelmän lähete-email")."</option>";
  if ($lukitse_laji== "") $ulos .= "</optgroup>";

  if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Työmääräysten avainsanat")."'>";
  if ($lukitse_laji== "" or $lukitse_laji == "TYOM_TYOJONO") $ulos .= "<option value='TYOM_TYOJONO' $avain_sel[TYOM_TYOJONO]>".t("Työmääräysten työjono")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TYOM_TYOSTATUS") $ulos .= "<option value='TYOM_TYOSTATUS' $avain_sel[TYOM_TYOSTATUS]>".t("Työmääräysten työstatus")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TYOM_TYOLINJA") $ulos .= "<option value='TYOM_TYOLINJA' $avain_sel[TYOM_TYOLINJA]>".t("Työmääräysten työlinja")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TYOM_TYOKENTAT") $ulos .= "<option value='TYOM_TYOKENTAT' $avain_sel[TYOM_TYOKENTAT]>".t("Työmääräysten kentät")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TYOM_PRIORIT") $ulos .= "<option value='TYOM_PRIORIT' $avain_sel[TYOM_PRIORIT]>".t("Työmääräysten prioriteetti")."</option>";
  if ($lukitse_laji== "") $ulos .= "</optgroup>";

  if ($lukitse_laji == "") $ulos .= "<optgroup label='" . t("Reseptien avainsanat") . "'>";
  if ($lukitse_laji == "" or $lukitse_laji == "RESEPTI_KENTAT") $ulos .= "<option value='RESEPTI_KENTAT' $avain_sel[RESEPTI_KENTAT]>" . t("Reseptien kentät") . "</option>";
  if ($lukitse_laji == "") $ulos .= "</optgroup>";

  if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Viennin avainsanat")."'>";
  if ($lukitse_laji== "" or $lukitse_laji == "KT") $ulos .= "<option value='KT' $avain_sel[KT]>".t("Kauppatapahtuman luonne")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TULLI") $ulos .= "<option value='TULLI' $avain_sel[TULLI]>".t("Poistumistoimipaikka")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KM") $ulos .= "<option value='KM' $avain_sel[KM]>".t("Kuljetusmuoto")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "C") $ulos .= "<option value='C' $avain_sel[C]>".t("CHN tietue")."</option>";
  if ($lukitse_laji == "" or $lukitse_laji == "RAJATOIMIPAIKAT") $ulos .= "<option value='RAJATOIMIPAIKAT' $avain_sel[RAJATOIMIPAIKAT]>" . t("Aiotut rajatoimipaikat (ja maat)") . "</option>";
  if ($lukitse_laji == "" or $lukitse_laji == "MAARATOIMPAIKKA") $ulos .= "<option value='MAARATOIMPAIKKA' $avain_sel[MAARATOIMPAIKKA]>" . t("Määrätoimipaikka (ja maa)") . "</option>";
  if ($lukitse_laji== "") $ulos .= "</optgroup>";

  if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Maksuehtojen avainsanat")."'>";
  if ($lukitse_laji== "" or $lukitse_laji == "LASKUKUVAUS") $ulos .= "<option value='LASKUKUVAUS' $avain_sel[LASKUKUVAUS]>".t("Maksuposition kuvaus")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KARHUVIESTI") $ulos .= "<option value='KARHUVIESTI' $avain_sel[KARHUVIESTI]>".t("Maksukehotusviesti")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "MAKSUEHTOKV") $ulos .= "<option value='MAKSUEHTOKV' $avain_sel[MAKSUEHTOKV]>".t("Maksuehdon kieliversio")."</option>";
  if ($lukitse_laji== "") $ulos .= "</optgroup>";

  if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("CRM avainsanat")."'>";
  if ($lukitse_laji== "" or $lukitse_laji == "KALETAPA") $ulos .= "<option value='KALETAPA' $avain_sel[KALETAPA]>".t("CRM yhteydenottotapa")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "CRM_ROOLI") $ulos .= "<option value='CRM_ROOLI' $avain_sel[CRM_ROOLI]>".t("Yhteyshenkilön rooli")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "CRM_SUORAMARKKI") $ulos .= "<option value='CRM_SUORAMARKKI' $avain_sel[CRM_SUORAMARKKI]>".t("Yhteyshenkilön suoramarkkinointitiedot")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "CRM_HAAS") $ulos .= "<option value='CRM_HAAS' {$avain_sel['CRM_HAAS']}>".t("Haas USR-kentät")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "CRM_TARJOUSPOIS") $ulos .= "<option value='CRM_TARJOUSPOIS' {$avain_sel['CRM_TARJOUSPOIS']}>".t("CRM viesti tarjouksen hylkäyksestä")."</option>";
  if ($lukitse_laji== "") $ulos .= "</optgroup>";

  if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Muut avainsanat")."'>";
  if ($lukitse_laji== "" or $lukitse_laji == "VAKIOVIESTI") $ulos .= "<option value='VAKIOVIESTI' $avain_sel[VAKIOVIESTI]>".t("Laskun vakioviesti")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "MAINOSTXT_LASKU") $ulos .= "<option value='MAINOSTXT_LASKU' {$avain_sel['MAINOSTXT_LASKU']}>".t("Laskun mainosteksti")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "VAKIOVIESTI_TIL") $ulos .= "<option value='VAKIOVIESTI_TIL' {$avain_sel['VAKIOVIESTI_TIL']}>".t("Tilausvahvistuksen vakioviesti")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KAANTALVVIESTI") $ulos .= "<option value='KAANTALVVIESTI' $avain_sel[KAANTALVVIESTI]>".t("Tilauksen/Laskun käänteisen verotuksen viesti")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "LITETY") $ulos .= "<option value='LITETY' $avain_sel[LITETY]>".t("Liitetiedostotyyppi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TIL-LITETY") $ulos .= "<option value='TIL-LITETY' ".$avain_sel["TIL-LITETY"].">".t("Tilauksen liitetiedostotyyppi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "JAKELULISTA") $ulos .= "<option value='JAKELULISTA' $avain_sel[JAKELULISTA]>".t("Email jakelulista")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "LUETTELO") $ulos .= "<option value='LUETTELO' $avain_sel[LUETTELO]>".t("Luettelotyyppi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TRIVITYYPPI") $ulos .= "<option value='TRIVITYYPPI' $avain_sel[TRIVITYYPPI]>".t("Tilausrivin tyyppi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "LASKUTUS_SAATE") $ulos .= "<option value='LASKUTUS_SAATE' $avain_sel[LASKUTUS_SAATE]>".t("Laskun sähköpostisaatekirje asiakkaalle")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "PALAUTUS_SAATE") $ulos .= "<option value='PALAUTUS_SAATE' $avain_sel[PALAUTUS_SAATE]>".t("Palautuksen sähköpostisaatekirje asiakkaalle")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TV_LISATIETO") $ulos .= "<option value='TV_LISATIETO' $avain_sel[TV_LISATIETO]>".t("Tilausvahvistuksen lisätiedot")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "INVEN_LAJI") $ulos .= "<option value='INVEN_LAJI' $avain_sel[INVEN_LAJI]>".t("Inventoinnin laji")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "HAE_JA_SELAA") $ulos .= "<option value='HAE_JA_SELAA' $avain_sel[HAE_JA_SELAA]>".t("Hae ja selaa -ohjelman parametrit")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "INVLISTA_OSTRY") $ulos .= "<option value='INVLISTA_OSTRY' $avain_sel[INVLISTA_OSTRY]>".t("Inventointilista -ohjelman monivalintalaatikot")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "PUUTEKOMM") $ulos .= "<option value='PUUTEKOMM' $avain_sel[PUUTEKOMM]>".t("Puuterivin kommentti")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "EXTRANET_TEEMA") $ulos .= "<option value='EXTRANET_TEEMA' $avain_sel[EXTRANET_TEEMA]>".t("Extranet ulkoasuteema")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "EXTRANET_H") $ulos .= "<option value='EXTRANET_H' $avain_sel[EXTRANET_H]>".t("Extranet etusivun otsikkoteksti")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "SOPIMUS_KENTTA") $ulos .= "<option value='SOPIMUS_KENTTA' $avain_sel[SOPIMUS_KENTTA]>".t("Ylläpitosopimuksen rivien kentät")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "LABEL") $ulos .= "<option value='LABEL' {$avain_sel['LABEL']}>".t("Myyntitilauksen luokitus")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "OSTOTIL_TILTYYP") $ulos .= "<option value='OSTOTIL_TILTYYP' {$avain_sel['OSTOTIL_TILTYYP']}>".t("Ostotilauksen tilaustyyppi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TARJOUS_VAKIOTE") $ulos .= "<option value='TARJOUS_VAKIOTE' {$avain_sel['TARJOUS_VAKIOTE']}>".t("Tarjouksen vakioteksti")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KATEISOTTO") $ulos .= "<option value='KATEISOTTO' {$avain_sel['KATEISOTTO']}>".t("Käteisoton luonne")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KOHDE_TYYPPI") $ulos .= "<option value='KOHDE_TYYPPI' {$avain_sel['KOHDE_TYYPPI']}>".t("Kohteen tyyppi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "MYYNNISTA_OSTO") $ulos .= "<option value='MYYNNISTA_OSTO' {$avain_sel['MYYNNISTA_OSTO']}>".t("Ostotilauksen käsittely myyntitilauksella")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "PAIKALLISVARAST") $ulos .= "<option value='PAIKALLISVARAST' {$avain_sel['PAIKALLISVARAST']}>".t("Paikallisvarasto")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "HINNAT_CRON") $ulos .= "<option value='HINNAT_CRON' {$avain_sel['HINNAT_CRON']}>".t("Hinnat-cronin aikaleima (vvvv-kk-pp tt:mm:ss)")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TUOTE_EXP_CRON") $ulos .= "<option value='TUOTE_EXP_CRON' {$avain_sel['TUOTE_EXP_CRON']}>".t("Tuote Export-cronin aikaleima (vvvv-kk-pp tt:mm:ss)")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "SALDOVAHVISTUS") $ulos .= "<option value='SALDOVAHVISTUS' {$avain_sel['SALDOVAHVISTUS']}>".t("Saldovahvistuksen viesti")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "ASIAKASHINNASTO") $ulos .= "<option value='ASIAKASHINNASTO' {$avain_sel['ASIAKASHINNASTO']}>".t("Asiakashinnaston svh hinta-kenttä")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "DPAVAINSANALAJI") $ulos .= "<option value='DPAVAINSANALAJI' {$avain_sel['DPAVAINSANALAJI']}>".t("Dynaamisen puun avainsanojen laji")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "LITETY_TKIRJAST") $ulos .= "<option value='LITETY_TKIRJAST' {$avain_sel['LITETY_TKIRJAST']}>".t("Tiedostokirjastossa selailtava tiedostotyyppi")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TOIMIT_TKIRJAST") $ulos .= "<option value='TOIMIT_TKIRJAST' {$avain_sel['TOIMIT_TKIRJAST']}>".t("Tiedostokirjastossa näkyvä toimittaja")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "AIHEAL_LAATUAS")  $ulos .= "<option value='AIHEAL_LAATUAS' {$avain_sel['AIHEAL_LAATUAS']}>" . t("Laatuasiakirjaselaimessa näytettävä aihealue") . "</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "LITETY_LAATUAS")  $ulos .= "<option value='LITETY_LAATUAS' {$avain_sel['LITETY_LAATUAS']}>" . t("Laatuasiakirjaselaimessa näytettävä tiedostotyyppi") . "</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "AIHEAL_LUVLIS")  $ulos .= "<option value='AIHEAL_LUVLIS' {$avain_sel['AIHEAL_LUVLIS']}>" . t("Luvat ja lisenssit selaimessa näytettävä aihealue") . "</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "LITETY_LUVLIS")  $ulos .= "<option value='LITETY_LUVLIS' {$avain_sel['LITETY_LUVLIS']}>" . t("Luvat ja lisenssit selaimessa näytettävä tiedostotyyppi") . "</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "SOP_LAITLUET")    $ulos .= "<option value='SOP_LAITLUET' {$avain_sel['SOP_LAITLUET']}>" . t("Sopimuksen laiteluettelon valinnaisen sarakkeen tuotekoodi") . "</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "RELEX_TUOTEPAR")  $ulos .= "<option value='RELEX_TUOTEPAR' {$avain_sel['RELEX_TUOTEPAR']}>".t("Relex tuoteparametrit")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "JTVALMISVIESTI")  $ulos .= "<option value='JTVALMISVIESTI' {$avain_sel['JTVALMISVIESTI']}>".t("SMS-viesti, joka lähetetään asiakkalle, kun jälkitoimitustuotteet on kerätty")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TYOMVALMVIEST")   $ulos .= "<option value='TYOMVALMVIEST' {$avain_sel['TYOMVALMVIEST']}>".t("SMS-viesti, joka lähetetään asiakkalle, kun työmääräys on valmis")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TOIM_TAPA_TA")   $ulos .= "<option value='TOIM_TAPA_TA' {$avain_sel['TOIM_TAPA_TA']}>".t("Toimitustavan PostNordille lähtevä TransportAccount")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "TOIMITTAJARYHMA")   $ulos .= "<option value='TOIMITTAJARYHMA' {$avain_sel['TOIMITTAJARYHMA']}>".t("Toimittajaryhmä")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KORVAMERKKI")   $ulos .= "<option value='KORVAMERKKI' {$avain_sel['KORVAMERKKI']}>".t("Tilausrivin korvamerkinnän vaihtoehdot")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "HUOLTOP_EMAIL") $ulos .= "<option value='HUOLTOP_EMAIL' {$avain_sel['HUOLTOP_EMAIL']}>".t("RMA portaalin huoltopyyntöemail")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "POSTEN_TKOODI")   $ulos .= "<option value='POSTEN_TKOODI' {$avain_sel['POSTEN_TKOODI']}>".t("Logmaster OutboundDelivery ja MaterialMaster sanomien kentät")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "UNIFAUN_REF") $ulos .= "<option value='UNIFAUN_REF' {$avain_sel['UNIFAUN_REF']}>".t("Unifaun lähettäjän viite")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "KUITIN_VAKTEXT") $ulos .= "<option value='KUITIN_VAKTEXT' {$avain_sel['KUITIN_VAKTEXT']}>".t("Kuitin vakioteksti")."</option>";
  if ($lukitse_laji== "" or $lukitse_laji == "STCK_LST_FLTR") $ulos .= "<option value='STCK_LST_FLTR' {$avain_sel['STCK_LST_FLTR']}>".t("Varastosaldoraportin rajaus")."</option>";
  if ($lukitse_laji== "") $ulos .= "</optgroup>";

  if ($lukitse_laji== "") $ulos .= "<optgroup label='".t("Verkkokaupan avainsanat")."'>";
  if ($lukitse_laji== "" or $lukitse_laji == "VERKKOKAULINKKI") $ulos .= "<option value='VERKKOKAULINKKI' $avain_sel[VERKKOKAULINKKI]>".t("Verkkokauppalinkki")."</option>";
  if ($lukitse_laji== "") $ulos .= "</optgroup>";

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "nakyvyys") {
  if (in_array("SELECTED", array($avain_sel["TRY"],
        $avain_sel["OSASTO"],
        $avain_sel["PS_ALA_TRY"]))) {
    $sel = array();
    $sel[$trow[$i]] = "SELECTED";

    $ulos = "<td><select name = '$nimi'>";
    $ulos .= "<option value = ''>".t("Näytetään verkkokaupassa")."</option>";
    $ulos .= "<option value = 'E' $sel[E]>".t("Ei näytetä verkkokaupassa")."</option>";
    $ulos .= "</select></td>";
  }
  elseif ($avain_sel["TYOM_TYOKENTAT"] == "SELECTED") {
    $sel = array();
    $sel[$trow[$i]] = "SELECTED";

    $ulos = "<td><select name = '$nimi'>";
    $ulos .= "<option value = ''>".t("Näytetään työmääräyksellä")."</option>";
    $ulos .= "<option value = 'K' $sel[K]>".t("Näytetään työmääräyksellä ja laskulla")."</option>";
    $ulos .= "<option value = 'L' $sel[L]>".t("Näytetään laskulla")."</option>";
    $ulos .= "<option value = 'H' $sel[H]>".t("Näytetään huoltosaatteella")."</option>";
    $ulos .= "<option value = 'I' $sel[I]>".t("Näytetään huoltosaatteella ja työmääräyksellä")."</option>";
    $ulos .= "<option value = 'J' $sel[J]>".t("Näytetään huoltosaatteella ja työmääräyksellä ja laskulla")."</option>";
    $ulos .= "<option value = 'S' $sel[S]>".t("Sisäinen tieto")."</option>";
    $ulos .= "</select></td>";
  }
  else {
    $tyyppi = 0;
  }

  $jatko = 0;
}

$_vartoimtulostin = ($avain_sel['VARTOIMTULOSTIN'] == 'SELECTED');
$_vartoimtulostin_selitekentta = ($_vartoimtulostin and $_selite);
$_vartoimtulostin_selitetarkkentta = ($_vartoimtulostin and $_selitetark);

$_siirtovarastot = ($avain_sel['SIIRTOVARASTOT'] == 'SELECTED');
$_siirtovarastot_kentat = ($_siirtovarastot and ($_selite or $_selitetark));

$_valittu_laji = ($_vartoimtulostin or $_siirtovarastot);
$_laji_ja_kentan_tarkistus = ($_vartoimtulostin_selitekentta or $_siirtovarastot_kentat);

if ($_valittu_laji and $_laji_ja_kentan_tarkistus) {

  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND tyyppi  != 'P'
            ORDER BY nimitys ASC";
  $varasto_result = pupe_query($query);

  $ulos .= "<td><select name = '{$nimi}'>";

  while ($varasto = mysql_fetch_assoc($varasto_result)) {

    $sel = $varasto['tunnus'] == $trow[$i] ? "selected" : "";

    $ulos .= "<option value = '{$varasto['tunnus']}' {$sel}>{$varasto['nimitys']}</option>";
    $sel = "";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($_vartoimtulostin and $_selitetark_2) {

  $sel = $trow[$i] == 'printteri9' ? "selected" : "";

  $ulos .= "<td><select name = '{$nimi}'>";
  $ulos .= "<option value='printteri1'>".t("Lähete")."</option>";
  $ulos .= "<option value='printteri9' $sel>".t("Purkulista")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($_vartoimtulostin and $_selitetark_3) {

  $ulos .= "<td><select name = '{$nimi}'>";

  $query  = "SELECT tunnus, kirjoitin
             FROM kirjoittimet
             WHERE yhtio  = '{$kukarow['yhtio']}'
             AND komento != 'EDI'
             ORDER BY kirjoitin";
  $vares = pupe_query($query);

  while ($varow = mysql_fetch_assoc($vares)) {
    $sel = $varow['tunnus'] == $trow[$i] ? 'selected' : '';
    $ulos .= "<option value='{$varow['tunnus']}' {$sel}>{$varow['kirjoitin']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($_siirtovarastot and $_selitetark_2) {
  $query = "SELECT *
            FROM toimitustapa
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $toimitustapa_result = pupe_query($query);

  $ulos .= "<td><select name = '$nimi'>";
  $sel = "";
  while ($toimitustapa = mysql_fetch_assoc($toimitustapa_result)) {
    if ($toimitustapa['tunnus'] == $trow[$i]) {
      $sel = "SELECTED";
    }
    $ulos .= "<option value = '{$toimitustapa['tunnus']}' {$sel}>{$toimitustapa['selite']}</option>";
    $sel = "";
  }
  $ulos .= "</select></td>";

  $jatko = 0;
  $tyyppi = 1;
}

if ($avain_sel['TOIMTAPAVKN'] == 'SELECTED') {
  if ($_selite) {
    $query = "SELECT *
              FROM toimitustapa
              WHERE yhtio = '{$kukarow['yhtio']}'";
    $toimitustapa_result = pupe_query($query);

    $ulos .= "<td><select name = '$nimi'>";
    $sel = "";
    while ($toimitustapa = mysql_fetch_assoc($toimitustapa_result)) {
      if ($toimitustapa['selite'] == $trow[$i]) {
        $sel = "SELECTED";
      }
      $ulos .= "<option value = '{$toimitustapa['selite']}' {$sel}>{$toimitustapa['selite']}</option>";
      $sel = "";
    }
    $ulos .= "</select></td>";

    $jatko = 0;
    $tyyppi = 1;
  }
}

if ($avain_sel['CRM_HAAS'] == 'SELECTED') {

  if ($_selite) {
    $sel = $trow[$i] == 'K' ? "selected" : "";

    $ulos .= "<td>";
    $ulos .= "<select name = '{$nimi}'>";
    $ulos .= "<option value = ''>".t("Ei näytetä")."</option>";
    $ulos .= "<option value = 'K' {$sel}>".t("Näytetään")."</option>";
    $ulos .= "</select>";
    $ulos .= "</td>";
    $jatko = 0;
  }

  if ($_selitetark) {
    $vresult = t_avainsana("CRM_ROOLI");

    $ulos = "<td><select name='{$nimi}'><option value=''>".t("Ei roolia")."</option>";

    while ($row = mysql_fetch_assoc($vresult)) {
      $sel = $row["selite"] == $trow[$i] ? 'selected' : '';
      $ulos .= "<option value='{$row['selite']}' {$sel}>{$row['selite']} - {$row['selitetark']}</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }
}

if ($avain_sel['KOPIOITUOTE'] == 'SELECTED') {
  if ($_selite) {
    $sel = $trow[$i] == 'OSTOEHDOTUS' ? "selected" : "";

    $ulos .= "<td>";
    $ulos .= "<select name = '{$nimi}'>";
    $ulos .= "<option value = 'TT'>".t("Kopioi tuotteen toimittajat")."</option>";
    $ulos .= "<option value = 'OSTOEHDOTUS' {$sel}>".t("Ehdotetaan ostoehdotusohjelmissa tilattavaksi")."</option>";
    $ulos .= "</select>";
    $ulos .= "</td>";
    $jatko = 0;
  }

  if ($_selitetark) {
    $sel = $trow[$i] == 'E' ? "selected" : "";

    $ulos .= "<td>";
    $ulos .= "<select name = '{$nimi}'>";
    $ulos .= "<option value = 'K'>".t("Kyllä")."</option>";
    $ulos .= "<option value = 'E' {$sel}>".t("Ei")."</option>";
    $ulos .= "</select>";
    $ulos .= "</td>";
    $jatko = 0;
  }
}

if ($avain_sel['GS1_SSCC'] == 'SELECTED') {

  if ($_selite) {
    $toimitustavat = hae_kaikki_toimitustavat();

    $ulos .= "<td>";
    $ulos .= "<select name='{$nimi}'>";

    $ulos .= "<option value='kaikki'>".t("Kaikki toimitustavat")."</optino>";

    foreach ($toimitustavat as $toimitustapa) {
      $sel = $trow[$i] == $toimitustapa['selite'] ? "selected" : "";
      $ulos .= "<option value='{$toimitustapa['selite']}' {$sel}>{$toimitustapa['selite']}</optino>";
    }

    $ulos .= "</select>";
    $ulos .= "</td>";
    $jatko = 0;
  }

  if ($_selitetark) {
    $ulos .= "<td>";
    $ulos .= "<select name='{$nimi}'>";

    foreach (range(0, 9) as $n) {
      $sel = (int) $trow[$i] == $n ? "selected" : "";
      $ulos .= "<option value='{$n}' {$sel}>{$n}</optino>";
    }

    $ulos .= "</select>";
    $ulos .= "</td>";
    $jatko = 0;
  }
}



$_toimmyynti = ($avain_sel['TOIMMYYNTI'] == 'SELECTED' and $_selite);

$_laji_ja_kentan_tarkistus = ($_vartoimtulostin_selitetarkkentta or $_toimmyynti);

if ($_laji_ja_kentan_tarkistus) {
  $toimipaikat = hae_toimipaikat();

  $ei_toimipaikkaa = array(
    'tunnus' => 0,
    'nimi' => t('Ei toimipaikkaa'),
  );
  array_unshift($toimipaikat, $ei_toimipaikkaa);

  $ulos .= "<td>";
  $ulos .= "<select name = '$nimi'>";
  $sel = "";
  foreach ($toimipaikat as $toimipaikka) {
    if ($toimipaikka['tunnus'] == $trow[$i]) {
      $sel = "SELECTED";
    }
    $ulos .= "<option value = '{$toimipaikka['tunnus']}' {$sel}>{$toimipaikka['nimi']}</option>";
    $sel = "";
  }
  $ulos .= "</select>";
  $ulos .= "</td>";

  $jatko = 0;
  $tyyppi = 1;
}

if ($avain_sel['TOIMMYYNTI'] == 'SELECTED' and $_selitetark) {
  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio = '{$kukarow['yhtio']}'
            ORDER BY nimitys ASC";
  $varasto_result = pupe_query($query);

  $ulos .= "<td>";
  $ulos .= "<select name = '$nimi'>";
  $sel = "";
  while ($varasto = mysql_fetch_assoc($varasto_result)) {
    if ($varasto['tunnus'] == $trow[$i]) {
      $sel = "SELECTED";
    }
    $ulos .= "<option value = '{$varasto['tunnus']}' {$sel}>{$varasto['nimitys']}</option>";
    $sel = "";
  }
  $ulos .= "</select>";
  $ulos .= "</td>";

  $jatko = 0;
  $tyyppi = 1;
}

if ($avain_sel['OLETUSPAIKKA'] == 'SELECTED') {

  if ($_selite) {
    $ulos .= "<td>";
    $ulos .= "<input type='text' name='{$nimi}' size='35' maxlength='65535' value='{$trow[$i]}' />";
    $ulos .= t("Oletuspaikka pitää antaa muodossa hyllyalue-hyllynro-hyllyvali-hyllytaso");
    $ulos .= "</td>";

    $jatko = 0;
  }

  if ($_selitetark) {
    $ulos  = "<td><select name='{$nimi}'>";
    $ulos .= product_status_options($trow[$i]);
    $ulos .= "</select></td>";

    $jatko = 0;
  }

  if ($_selitetark_2) {
    $ulos = '<td>';
    $ulos .= livesearch_kentta('a', 'TUOTERYHMAHAKU', $nimi, 251, $trow[$i], '', '', '', 'ei');
    $ulos .= '</td>';

    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "kieli") {

  $kieli = $trow[$i];

  $ulos = "<td><select name='$nimi'>";
  $sel  = "";

  //  näytetäään vain kielet joilla on
  if ($perhe > 0) {
    if ($lukitse_laji != "") {
      $laji = $lukitse_laji;
    }
    else {
      $laji = $al_laji;
    }

    $query = "SELECT group_concat(kieli) kielet
              FROM avainsana
              WHERE yhtio = '$kukarow[yhtio]'
              and perhe   = '$perhe'
              and laji    = '$laji'";
    $res = pupe_query($query);
    $kilerow = mysql_fetch_array($res);
    $kielet = $kilerow["kielet"];
  }

  foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
    if (strpos($kielet, $sanakirja_kieli) === FALSE or $sanakirja_kieli == $trow[$i]) {
      $sel = "";

      if ($trow[$i] == $sanakirja_kieli) {
        $sel = "SELECTED";
      }
      elseif ($trow[$i] == "" and $sanakirja_kieli == $yhtiorow["kieli"]) {
        $sel = "SELECTED";
      }

      $ulos .= "<option value='$sanakirja_kieli' $sel>".t($sanakirja_kieli_nimi)."</option>";
    }
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($avain_sel["TARJOUS_VAKIOTE"] == "SELECTED" and ($_selite or $_selitetark_2 or $_selitetark_3 or $_selitetark_4)) {
  $tyyppi = 0;
}

if ($avain_sel["KERAYSLISTA"] == "SELECTED" and $_selite) {

  $handle = opendir("tilauskasittely");

  $ulos  = "<td><select name='$nimi'>";

  while ($file = readdir($handle)) {
    if (strpos($file, "kerayslista") !== FALSE) {
      $sel = "";

      if ($file == $trow[$i]) {
        $sel = "SELECTED";
      }

      $ulos .= "<option value='$file' $sel>".t($file)."</option>";
    }
  }
  closedir($handle);

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel["SAHKTILTUN"] == "SELECTED" and $_selite) {

  $vresult = hae_yhtion_toimipaikat($kukarow['yhtio']);

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value='0'>".t("Valitse toimipaikka")."</option>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel = "";

    if ($vrow["tunnus"] == $trow[$i]) {
      $sel = "SELECTED";
    }

    $ulos .= "<option value='{$vrow["tunnus"]}' $sel>".trim($vrow["nimi"]." ".$vrow["postitp"]." ".$vrow["ovtlisa"])."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel['OSTOTIL_TILTYYP'] == 'SELECTED' and mysql_field_name($result, $i) == 'selitetark_2') {

  $sel = $trow[$i] != '' ? " selected" : "";

  $ulos = "<td><select name='{$nimi}'>";
  $ulos .= "<option value=''>".t("Ei")."</option>";
  $ulos .= "<option value='x'{$sel}>".t("Kyllä")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel["RAHTIKIRJA"] == "SELECTED" and $_selite) {

  $handle = opendir("tilauskasittely");

  $ulos  = "<td><select name='$nimi'>";

  $ulos .= "<option value=''></option>";

  while ($file = readdir($handle)) {
    if (substr($file, 0, 10) == 'rahtikirja') {
      $sel = "";

      if ($file == $trow[$i]) {
        $sel = "SELECTED";
      }

      $ulos .= "<option value='$file' $sel>".t($file)."</option>";
    }
  }
  closedir($handle);

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel["TARRATYYPPI"] == "SELECTED" and $_selite) {

  $handle = opendir("tilauskasittely");

  $ulos  = "<td><select name='$nimi'>";

  $ulos .= "<option value=''></option>";

  while ($file = readdir($handle)) {
    if (substr($file, 0, 16) == 'tilaustuotetarra') {
      $sel = "";

      if ($file == $trow[$i]) {
        $sel = "SELECTED";
      }

      $ulos .= "<option value='$file' $sel>".t($file)."</option>";
    }
  }
  closedir($handle);

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel["SARJANUMERON_LI"] == "SELECTED" and $_selite) {

  $selitteet = array(  'ANKKURIVINSSI', 'JAAKAAPPI', 'KONEISTUS', 'KUVATYYPPI', 'LAATU', 'MATERIAALI', 'MERKKI', 'MOOTTORINMERKKI', 'MOOTTORINOHJAUS',
    'PILSSIPUMPPU', 'SIJAINTI', 'TIIKKISARJA', 'TOIMITUSKULUT', 'TYYNYSARJA', 'TYYPPI', 'UUNI', 'VALONHEITIN', 'VARIRUNKO', 'VESSA',
    'RUNKOTYYPPI', 'SPRINKLERI', 'KILPI');

  $ulos  = "<td><select name='$nimi'>";

  foreach ($selitteet as $selite) {
    if (strtoupper($selite) == strtoupper($trow[$i])) {
      $sel = "SELECTED";
    }
    else {
      $sel = "";
    }

    $ulos .= "<option value='$selite' $sel>$selite</option>";

  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel['MYYNNISTA_OSTO'] == "SELECTED" and $_selite) {

  $sel = array($trow[$i] => " selected") + array_fill_keys(array('1', '2', '3', '4', '5'), '');

  $ulos  = "<td><select name='{$nimi}'>";
  $ulos .= "<option value='0'>".t("Tilauksen rivit lisätään olemassa olevalle ostotilaukselle per toimittaja per käyttäjä per päivä")."</option>";
  $ulos .= "<option value='1' {$sel[1]}>".t("Tilauksen rivit lisätään olemassa olevalle ostotilaukselle per toimittaja per käyttäjä per päivä, lähetetään heti")."</option>";
  $ulos .= "<option value='2' {$sel[2]}>".t("Tilauksen rivit lisätään olemassa olevalle ostotilaukselle per toimittaja")."</option>";
  $ulos .= "<option value='3' {$sel[3]}>".t("Tilauksen rivit lisätään olemassa olevalle ostotilaukselle per toimittaja, lähetetään heti")."</option>";
  $ulos .= "<option value='4' {$sel[4]}>".t("Tilauksen rivit lisätään olemassa olevalle ostotilaukselle per toimittaja per varasto per ohjausmerkki")."</option>";
  $ulos .= "<option value='5' {$sel[5]}>".t("Tilauksen rivit lisätään olemassa olevalle ostotilaukselle per toimittaja per varasto per ohjausmerkki, lähetetään heti")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel["ALV"] == "SELECTED" and $_selitetark) {

  $sel = trim($trow[$i]) != "" ? " SELECTED" : "";

  $ulos  = "<td><select name='{$nimi}'>";
  $ulos .= "<option value=''>".t("Tämä ei ole oletusverokanta")."</option>";
  $ulos .= "<option value='o'{$sel}>".t("Tämä on oletusverokanta")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel["SAHKTILTUN"] == "SELECTED" and $_selitetark) {

  $query = "SELECT nimi, tunnus
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tyyppi  not in ('P','K')";
  $toimi_chk_res = pupe_query($query);

  if (mysql_num_rows($toimi_chk_res) > 0) {

    $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300)." onchange='submit();'>";
    $ulos .= "<option value=''>".t("Valitse toimittaja")."</option>";

    while ($row = mysql_fetch_assoc($toimi_chk_res)) {
      $sel = "";

      if ($row["tunnus"] == $trow[$i]) {
        $sel = 'selected';
      }

      $ulos .= "<option value='{$row['tunnus']}' {$sel}>{$row['nimi']}</option>";
    }

    $ulos .= "</select></td>";
  }
  else {
    $ulos = "<td><input type='text' name='{$nimi}' value='{$trow[$i]}'></td>";
  }

  $jatko = 0;
}

if ($avain_sel["ALVULK"] == "SELECTED" and $_selitetark_2) {

  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'>";

  $ulos .= "<option value = '' ></option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
      $sel = "SELECTED";
    }
    elseif ($trow[$i] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["maa"])) {
      $sel = "SELECTED";
    }
    $ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel['INVEN_LAJI'] == "SELECTED" and $_selitetark_2) {

  $ulos = "<td>".livesearch_kentta("mainform", "TILIHAKU", $nimi, 270, $trow[$i], "EISUBMIT")."</td>";

  $jatko = 0;
}

if ($avain_sel['INVEN_LAJI'] == "SELECTED" and $_selitetark_3) {

  $sel1 = '';
  $sel2 = '';

  if ($trow[$i] == '') {
    $sel1 = 'SELECTED';
  }
  elseif ($trow[$i] == 'X') {
    $sel2 = 'SELECTED';
  }

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value='' $sel1>".t("Ei")."</option>";
  $ulos .= "<option value='X' $sel2>".t("Kyllä")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel["TOIMEHTO"] == "SELECTED" and $_selitetark_3) {

  $sel1 = '';
  $sel2 = '';

  if ($trow[$i] == 'LAHETTAJAN_SOPIMUS') {
    $sel1 = 'SELECTED';
  }
  elseif ($trow[$i] == 'VASTAANOTTAJAN_SOPIMUS') {
    $sel2 = 'SELECTED';
  }

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value=''>".t("Käytetään toimitustavan/asiakkaan oletusrahtisopimusta")."</option>";
  $ulos .= "<option value='LAHETTAJAN_SOPIMUS' $sel1>".t("Käytetään lähettäjän rahtisopimusta")."</option>";
  $ulos .= "<option value='VASTAANOTTAJAN_SOPIMUS' $sel2>".t("Käytetään vastaanottajan rahtisopimusta")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel["TOIMVAHVISTUS"] == "SELECTED" and $_selitetark_2 and $trow[$i-2] == "toimitusvahvistus_email.inc") {

  $sel = $trow[$i] == 'eihintaa' ? "selected" : "";

  $ulos = "<td><select name = '{$nimi}'>";
  $ulos .= "<option value = ''>".t("Näytetään hinta")."</option>";
  $ulos .= "<option value = 'eihintaa' {$sel}>".t("Ei näytetä hintaa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}
elseif ($avain_sel["TOIMVAHVISTUS"] == "SELECTED" and $_selitetark_2 and $trow[$i-2] != "toimitusvahvistus_email.inc") {
  $tyyppi = 0;
  $jatko  = 0;
}

if ($avain_sel["TOIMVAHVISTUS"] == "SELECTED" and $_selitetark_3 and $trow[$i-3] == "toimitusvahvistus_email.inc") {

  $sel1 = $trow[$i] == '' ? "selected" : "";
  $sel2 = $trow[$i] == 'M' ? "selected" : "";
  $sel3 = $trow[$i] == 'K' ? "selected" : "";

  $ulos = "<td><select name = '{$nimi}'>";
  $ulos .= "<option value = '' $sel1>".t("Vain asiakkaalle")."</option>";
  $ulos .= "<option value = 'M' $sel2>".t("Vain myyjälle")."</option>";
  $ulos .= "<option value = 'K' $sel3>".t("Asiakkaalle ja myyjälle")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($avain_sel["TOIMEHTO"] == "SELECTED" and $_selitetark_4) {
  $tyyppi = 0;
  $jatko  = 0;
}

if (($avain_sel["KARHUVIESTI"] == "SELECTED" or $avain_sel["TV_LISATIETO"] == "SELECTED") and ($_selite or $_selitetark_2 or $_selitetark_3)) {
  $tyyppi = 0;
  $jatko  = 0;
}

if (($avain_sel["KARHUVIESTI"] == "SELECTED"
    or $avain_sel["TV_LISATIETO"] == "SELECTED"
    or $avain_sel['KAANTALVVIESTI'] == "SELECTED"
    or $avain_sel['TARJOUS_VAKIOTE'] == "SELECTED"
    or $avain_sel["SALDOVAHVISTUS"] == "SELECTED"
    or $avain_sel["MYYNTITILASTO"] == "SELECTED")
  and $_selitetark) {
  $ulos = "<td><textarea rows=10 cols=50 name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

if (($avain_sel["TUOTEMERKKI"] == "SELECTED"
    or $avain_sel["MYYNTITILASTO"] == "SELECTED")
  and $_selitetark_2) {
  $ulos = "<td><textarea rows=10 cols=50 name='{$nimi}'>{$trow[$i]}</textarea></td>";
  $jatko = 0;
}

if (($avain_sel["OSASTO"] == "SELECTED"
    or $avain_sel["MYYNTITILASTO"] == "SELECTED"
    or $avain_sel["TRY"] == "SELECTED")
  and $_selitetark_3) {
  $ulos = "<td><textarea rows=10 cols=50 name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

if (($avain_sel['LASKUTUS_SAATE'] == "SELECTED" or $avain_sel['PALAUTUS_SAATE'] == "SELECTED" or $avain_sel['KAANTALVVIESTI'] == "SELECTED") and $_selitetark_2) {
  $ulos = "<td><textarea rows=30 cols=50 name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

if (($avain_sel['VAKIOVIESTI'] == "SELECTED" or $avain_sel['MAINOSTXT_LASKU'] == "SELECTED" or $avain_sel['VAKIOVIESTI_TIL'] == "SELECTED") and ($_selite or $_selitetark)) {
  $ulos = "<td><textarea rows=5 cols=50 name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

// Tuoteryhmissä voidaan antaa oletuskeräysprioriteetti selitetark_4 kenttään
if ($avain_sel["LITETY_TKIRJAST"] == "SELECTED" and mysql_field_name($result, $i) == "selite") {

  $vresult = t_avainsana("LITETY");

  $ulos = "<td><select name = '$nimi'>";

  while ($row = mysql_fetch_assoc($vresult)) {
    $sel = ($row["selite"] == $trow[$i]) ? ' selected' : '';
    $ulos .= "<option value = '{$row["selite"]}'$sel>{$row["selite"]} - {$row["selitetark"]}</option>";
  }
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($avain_sel["LITETY_LAATUAS"] == "SELECTED" and $_selite) {
  $vresult = t_avainsana("AIHEAL_LAATUAS");

  $ulos = "<td><select name = '{$nimi}'>";

  while ($row = mysql_fetch_assoc($vresult)) {
    $sel = ($row["selite"] == $trow[$i]) ? ' selected' : '';
    $ulos .= "<option value = '{$row["selite"]}'$sel>{$row["selite"]}</option>";
  }
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($avain_sel["LITETY_LUVLIS"] == "SELECTED" and $_selite) {
  $vresult = t_avainsana("AIHEAL_LUVLIS");

  $ulos = "<td><select name = '{$nimi}'>";

  while ($row = mysql_fetch_assoc($vresult)) {
    $sel = ($row["selite"] == $trow[$i]) ? ' selected' : '';
    $ulos .= "<option value = '{$row["selite"]}'$sel>{$row["selite"]}</option>";
  }
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($avain_sel["TOIMIT_TKIRJAST"] == "SELECTED" and $_selite) {
  $query = "SELECT toimi.tunnus,
            toimi.nimi
            FROM toimi
            WHERE toimi.yhtio       = '{$kukarow["yhtio"]}'
            AND toimi.oletus_vienti IN ('C', 'J', 'F', 'K', 'I', 'L')
            AND toimi.tyyppi        NOT IN ('P', 'PP')
            ORDER BY toimi.nimi";

  $toimittaja_result = pupe_query($query);

  $ulos = "<td><select name='{$nimi}' onclick='submit();'>";

  while ($toimittaja = mysql_fetch_assoc($toimittaja_result)) {
    $sel = ($toimittaja["tunnus"] == $trow[$i]) ? ' selected' : '';
    $ulos .= "<option value='{$toimittaja["tunnus"]}' $sel>{$toimittaja["nimi"]}</option>";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

// Tuoteryhmissä voidaan antaa oletuskeräysprioriteetti selitetark_4 kenttään
if ($avain_sel["TRY"] == "SELECTED" and $_selitetark_4) {
  $vresult = t_avainsana("ASIAKASLUOKKA");

  $ulos = "<td><select name = '$nimi'>";
  $ulos .= "<option value = ''>".t("Ei keräysprioriteettiä")."</option>";

  while ($row = mysql_fetch_assoc($vresult)) {
    $sel = ($row["selite"] == $trow[$i]) ? ' selected' : '';
    $ulos .= "<option value = '{$row["selite"]}'$sel>{$row["selite"]} - {$row["selitetark"]}</option>";
  }
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($avain_sel["DPAVAINSANALAJI"] == "SELECTED" and $_selitetark_2) {

  $sel = $trow[$i] == 'TUOTE' ? "selected" : "";

  $ulos = "<td><select name = '{$nimi}'>";
  $ulos .= "<option value = 'ASIAKAS'>".t("Asiakas")."</option>";
  $ulos .= "<option value = 'TUOTE' {$sel}>".t("Tuote")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($avain_sel["ASIAKASHINNASTO"] == "SELECTED" and $_selite) {

  $sel = $trow[$i] == 'myymalahinta' ? "selected" : "";

  $ulos = "<td><select name = '{$nimi}'>";
  $ulos .= "<option value = 'myyntihinta'>".t("Myyntihinta")."</option>";
  $ulos .= "<option value = 'myymalahinta' {$sel}>".t("Myymälähinta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (($avain_sel["TYOM_PRIORIT"] == "SELECTED" or $avain_sel["TYOM_TYOJONO"] == "SELECTED" or $avain_sel["TYOM_TYOSTATUS"] == "SELECTED") and $_selitetark_3) {

  if ($trow[$i] == 'OLETUS') {
    $sel = "SELECTED";
  }

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value='' $sel>".t("Ei oletus")."</option>";
  $ulos .= "<option value='OLETUS' $sel>".t("Oletus")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel['TYOM_TYOJONO'] == "SELECTED" and $_selitetark_4) {

  $ulos = "<td><select name = '$nimi'>";
  $ulos .= "<option value=''>".t('Ei oletusvastuuhenkilöä')."</option>";
  $query = "SELECT *
            FROM kuka
            WHERE yhtio    = '{$kukarow['yhtio']}'
            AND aktiivinen = 1
            AND extranet   = ''
            ORDER BY nimi";
  $vastuuresult = pupe_query($query);

  while ($vastuurow = mysql_fetch_assoc($vastuuresult)) {
    $sel = '';
    if ($trow[$i] == $vastuurow['tunnus']) {
      $sel = 'selected';
    }
    $ulos .= "<option value='{$vastuurow['kuka']}' $sel>{$vastuurow['nimi']} ({$vastuurow['kuka']})</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel['TYOM_TYOLINJA'] == "SELECTED" and $_selite) {

  $query = "SELECT selite, selitetark
            FROM avainsana
            WHERE yhtio = '$kukarow[yhtio]' and laji = 'TYOM_TYOJONO'
            ORDER BY jarjestys, selitetark_2";
  $vresult = pupe_query($query);

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value = ''>".t("Kaikki työjonot")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow[0]) {
      $sel = "selected";
    }

    $ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel['TYOM_TYOLINJA'] == "SELECTED" and $_selitetark) {

  $query = "SELECT kuka, nimi
            FROM kuka
            WHERE yhtio    = '$kukarow[yhtio]'
            AND aktiivinen = 1
            AND extranet   = ''
            ORDER BY nimi";
  $vresult = pupe_query($query);

  $ulos = "<td><select name='$nimi'>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow[0]) {
      $sel = "selected";
    }

    $ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel["RAHTIKIRJA"] == "SELECTED" and $_selitetark_2) {

  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos = "<td><select name = '$nimi'>";
  $ulos .= "<option value = ''>".t("Oletus")."</option>";
  $ulos .= "<option value = '1' $sel[1]>".t("Tulostintyyppi")." - ".t("Laser A4")."</option>";
  $ulos .= "<option value = '2' $sel[2]>".t("Tulostintyyppi")." - ".t("Laser A5")."</option>";
  $ulos .= "<option value = '3' $sel[3]>".t("Tulostintyyppi")." - ".t("Matriisi")."</option>";
  $ulos .= "<option value = 'hrx' {$sel['hrx']}>".t("Tulostintyyppi")." - ".t("HRX")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel['VARASTORYHMA'] == 'SELECTED' and $_selitetark) {
  $query = "SELECT tunnus, nimitys
            FROM varastopaikat
            WHERE yhtio = '{$kukarow['yhtio']}' AND tyyppi != 'P'";
  $varasto_res = pupe_query($query);

  $ulos .= "<td>";

  $varastot = explode(",", $trow[$i]);

  while ($varasto_row = mysql_fetch_assoc($varasto_res)) {
    $chk = in_array($varasto_row['tunnus'], $varastot) ? ' checked' : '';
    $ulos .= "<input type='checkbox' name='varastot_array[]' value='$varasto_row[tunnus]'$chk> $varasto_row[nimitys]<br/>";
  }

  $ulos .= "</td>";
  $jatko = 0;
}

if ($avain_sel["HAE_JA_SELAA"] == "SELECTED" and $_selite) {
  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos = "<td><select name = '$nimi'>";
  $ulos .= "<option value = 'A' $sel[A]>".t("Hae ja selaa -ohjelmassa käytetään alkuperäistä ulkoasua")."</option>";
  $ulos .= "<option value = 'B' $sel[B]>".t("Hae ja selaa -ohjelmassa käytetään uutta ulkoasua")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($avain_sel["HAE_JA_SELAA"] == "SELECTED" and $_selitetark_2) {
  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos = "<td><select name = '$nimi'>";
  $ulos .= "<option value = '' {$sel['']}>".t("Hae ja selaa -ohjelmassa ei voida syöttää alennusta")."</option>";
  $ulos .= "<option value = 'K' {$sel['K']}>".t("Hae ja selaa -ohjelmassa voidaan syöttää alennus")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "jarjestys") {
  if ($trow[$i] == "" and $ajarjestys != "") {
    $trow[$i] = $ajarjestys;
  }
}

if ($avain_sel['KATEISOTTO'] == "SELECTED" and $_selite) {
  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $query = "SELECT tunnus,
            concat(tilino,' - ',nimi) as dropdown_text
            FROM tili
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $tilires = pupe_query($query);
  $tilit = array();
  while ($tili = mysql_fetch_assoc($tilires)) {
    $tilit[] = $tili;
  }

  $ulos = "<td><select name = '$nimi'>";
  $ulos .= "<option value = '' >".t("Valitse tili")."</option>";
  foreach ($tilit as $tili) {
    $sel="";
    if ($trow[$i] == $tili['tunnus']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '{$tili['tunnus']}' $sel>".t($tili['dropdown_text'])."</option>";
  }
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($avain_sel['KERAYSERA_TK'] == "SELECTED" and $_selite) {

  $query = "SELECT *
            FROM kuka
            WHERE yhtio     = '$kukarow[yhtio]'
            AND nimi       != ''
            AND extranet    = ''
            AND (keraajanro > 0 or kuka = '$kukarow[kuka]')
            AND aktiivinen  = 1
            ORDER BY nimi";
  $varresult = pupe_query($query);

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  while ($kukkarow = mysql_fetch_assoc($varresult)) {

    $sel = $trow[$i] == $kukkarow['kuka'] ? " selected" : "";

    $ulos .= "<option value='{$kukkarow['kuka']}'{$sel}>{$kukkarow['nimi']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel['KERAYSERA_TK'] == "SELECTED" and $_selitetark) {

  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio  = '$kukarow[yhtio]'
            AND nimitys != ''
            AND tyyppi  != 'P'
            ORDER BY tyyppi,nimitys";
  $varresult = pupe_query($query);

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  while ($kukkarow = mysql_fetch_assoc($varresult)) {

    $sel = $trow[$i] == $kukkarow['tunnus'] ? " selected" : "";

    $ulos .= "<option value='{$kukkarow['tunnus']}'{$sel}>{$kukkarow['nimitys']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel['RELEX_TUOTEPAR'] == "SELECTED" and $_selite) {

  $query = "DESC tuote";
  $varresult = pupe_query($query);

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  while ($kukkarow = mysql_fetch_assoc($varresult)) {
    $sel = $trow[$i] == $kukkarow['Field'] ? " selected" : "";
    $ulos .= "<option value='{$kukkarow['Field']}'{$sel}>{$kukkarow['Field']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($avain_sel['RELEX_TUOTEPAR'] == "SELECTED" and $_selitetark) {

  $oper_array = array('on'              => '=',
    'not'             => '!=',
    'in'              => 'in',
    'not in'          => 'not in',
    'like_pre'        => '%like',
    'like_post'       => 'like%',
    'like_both'       => '%like%',
    'gt'              => '>',
    'lt'              => '<',
    'gte'             => '>=',
    'lte'             => '<=',
  );

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  foreach ($oper_array as $key => $value) {

    $sel = $trow[$i] == $key ? " selected" : "";

    $ulos .= "<option value='{$key}'{$sel}>{$value}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($_selite and $avain_sel["RESEPTI_KENTAT"] == "SELECTED") {
  $kentat = array("piirustusnumero", "osanumero", "positiokentta");

  $resepti_kentat_result = t_avainsana("RESEPTI_KENTAT");

  while ($resepti_kentta = mysql_fetch_assoc($resepti_kentat_result)) {
    if (in_array($resepti_kentta["selite"], $kentat) and $trow[$i] != $resepti_kentta["selite"]) {
      $key = array_search($resepti_kentta["selite"], $kentat);
      unset($kentat[$key]);
    }
  }

  $ulos = "<td>";
  $ulos .= "<select name='{$nimi}'" . js_alasvetoMaxWidth($nimi, 300) . ">";

  foreach ($kentat as $kentta) {
    $ulos .= "<option value='{$kentta}'>" . t(ucfirst($kentta)) . "</option>";
  }

  $ulos .= "</select>";
  $ulos .= "</td>";

  $jatko = 0;
}

if ($avain_sel["TIL-LITETY"] == "SELECTED" and $_selitetark_2) {
  $ulos = "<th><label for='valitut_operaattorit'>" . t("Verkkolaskuoperaattorit") . "</label></th>";

  $ulos .= "<td><input type='hidden' id='verkkolaskuoperaattorit' name='{$nimi}'>";

  $ulos .= "<select id='valitut_operaattorit' name='valitut_operaattorit[]' multiple>";

  $valitut = explode(",", $trow[$i]);

  foreach ($valitut as $valittu) {
    $sel[$valittu] = " selected";
  }

  $ulos .= "<option value='apix'{$sel["apix"]}>Apix";

  $ulos .= "</select>";

  $jatko = 0;
}

if ($avain_sel['KUITIN_VAKTEXT'] == "SELECTED") {
  if ($_selite || $_jarjestys) {
    $tyyppi = 0;
  }
  elseif ($_selitetark) {
    $ulos = "<td><textarea name='{$nimi}' rows='10' cols='50'>{$trow[$i]}</textarea></td>";
    $jatko = 0;
  }
}

if ($avain_sel['STCK_LST_FLTR'] == "SELECTED") {
  if ($_selite) {
    $sel = array();
    $sel[$trow[$i]] = " selected";

    $ulos = "<td>";
    $ulos .= "<select name='{$nimi}' onchange='submit()'>";
    $ulos .= "<option value='tuotemerkki'{$sel["tuotemerkki"]}>" . t("Tuotemerkki") . "</option>";
    $ulos .= "<option value='try'{$sel["try"]}>" . t("Tuoteryhmä") . "</option>";
    $ulos .= "<option value='osasto'{$sel["osasto"]}>" . t("Tuoteosasto") . "</option>";
    $ulos .= "</select>";
    $ulos .= "</td>";

    $jatko = 0;
  }
  elseif ($_selitetark) {
    $sel = array();
    $sel[$trow[$i]] = " selected";

    $ulos = "<td>";
    $ulos .= "<select name='{$nimi}'>";
    $ulos .= "<option value='on'{$sel["on"]}>" . t("Lisätään raporttiin") . "</option>";
    $ulos .= "<option value='off'{$sel["off"]}>" . t("Poistetaan raportista") . "</option>";
    $ulos .= "</select>";
    $ulos .= "</td>";

    $jatko = 0;
  }
  elseif ($_selitetark_2) {
    if (empty($trow["selite"]) || $trow["selite"] == "tuotemerkki") {
      $tuotemerkkiresult = t_avainsana("tuotemerkki", '', 'ORDER BY selite ASC');

      $ulos = "<td>";
      $ulos .= "<select name='{$nimi}'>";

      while ($tuotemerkkirow = mysql_fetch_assoc($tuotemerkkiresult)) {
        $sel = $trow[$i] == $tuotemerkkirow["selite"] ? " selected" : "";
        $ulos .= "<option value='{$tuotemerkkirow["selite"]}'{$sel}>{$tuotemerkkirow["selite"]}</option>";
      }

      $ulos .= "</select>";
      $ulos .= "</td>";

      $tyyppi = 1;
      $jatko = 0;
    }
    elseif ($trow["selite"] == "try") {
      $tryresult = t_avainsana("try", '', 'ORDER BY selite ASC');

      $ulos = "<td>";
      $ulos .= "<select name='{$nimi}'>";

      while ($tryrow = mysql_fetch_assoc($tryresult)) {
        $sel = $trow[$i] == $tryrow["selite"] ? " selected" : "";
        $ulos .= "<option value='{$tryrow["selite"]}'{$sel}>{$tryrow["selitetark"]}</option>";
      }

      $ulos .= "</select>";
      $ulos .= "</td>";

      $tyyppi = 1;
      $jatko = 0;
    }
    elseif ($trow["selite"] == "osasto") {
      $osastoresult = t_avainsana("osasto", '', 'ORDER BY selite ASC');

      $ulos = "<td>";
      $ulos .= "<select name='{$nimi}'>";

      while ($osastorow = mysql_fetch_assoc($osastoresult)) {
        $sel = $trow[$i] == $osastorow["selite"] ? " selected" : "";
        $ulos .= "<option value='{$osastorow["selite"]}'{$sel}>{$osastorow["selitetark"]}</option>";
      }

      $ulos .= "</select>";
      $ulos .= "</td>";

      $tyyppi = 1;
      $jatko = 0;
    }
  }
  elseif ($_jarjestys) {
    $tyyppi = 0;
  }
}

if ($avain_sel['PS_ALA_TRY'] == "SELECTED" and $_selite) {
  $ulos = "<td><select name='{$nimi}' " . js_alasvetoMaxWidth($nimi, 300) . ">";

  $try_result = t_avainsana('try', '', "AND selitetark <> ''");

  while ($tryrow = mysql_fetch_assoc($try_result)) {
    $sel = $tryrow["selite"] == $trow[$i] ? " selected" : "";

    $ulos .= "<option{$sel} value='{$tryrow["selite"]}'>
                {$tryrow["selite"]} - {$tryrow["selitetark"]}
              </option>";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($avain_sel['PS_ALA_TRY'] == "SELECTED" and $_selitetark_2) {
  $ulos  = "<th>" . t("Nimitys") . "</th>
            <td><input type='text' size='35' value='{$trow[$i]}' name='{$nimi}'></td>";
  $jatko = 0;
}

// Unifaun lähettäjän viite
if ($avain_sel['UNIFAUN_REF'] == 'SELECTED') {
  // ei näytetä selitetarkia tai järjestystä
  if ($_selitetark or $_jarjestys) {
    $tyyppi = 0;
  }

  if ($_selite) {
    $sel = $trow[$i] == 'tunnus' ? 'selected' : '';

    $ulos = "<td>";
    $ulos .= "<select name='{$nimi}'>";
    $ulos .= "<option value='viesti'>" . t('Tilausviite') . "</option>";
    $ulos .= "<option value='tunnus' {$sel}>" . t('Tilausnumero') . "</option>";
    $ulos .= "</select>";
    $ulos .= "</td>";

    $jatko = 0;
  }
}
