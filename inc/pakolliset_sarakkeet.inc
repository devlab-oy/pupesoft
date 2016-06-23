<?php

/*
  Ylläpidetään taulujen pakollisia saraketietoja

  list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattavat, $saakopoistaa, $oletukset, $eisaaollatyhja) = pakolliset_sarakkeet($table);
*/

// määritellään pakolliset sarakkeet
if (!function_exists("pakolliset_sarakkeet")) {
  function pakolliset_sarakkeet($table, $otsikot = "") {
    global $kukarow, $yhtiorow;

    $pakolliset     = array();
    $kielletyt      = array();
    $wherelliset    = array();
    $eiyhtiota      = "";
    $joinattavat    = array();
    $saakopoistaa   = FALSE;
    $oletukset      = array();
    $eisaaollatyhja = array();

    switch ($table) {
    case "tuote" :
      $pakolliset   = array("TUOTENO");

      if ($yhtiorow["palvelutuotteiden_kehahinnat"] == "K") {
        $kielletyt  = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM", "VIHAHIN", "VIHAPVM");
      }
      else {
        $kielletyt  = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM", "KEHAHIN", "VIHAHIN", "VIHAPVM");
      }

      $wherelliset  = array("TUOTENO");
      $saakopoistaa = TRUE;
      break;
    case "tuotepaikat" :
      $pakolliset   = array("TUOTENO", "HYLLYALUE", "HYLLYNRO", "HYLLYVALI", "HYLLYTASO", "OLETUS");
      $kielletyt    = array(
        "LAATIJA",
        "LUONTIAIKA",
        "MUUTTAJA",
        "MUUTOSPVM",
        "HYLLYPAIKKA",
        "SALDO",
        "SALDO_VARATTU",
        "SALDOAIKA",
        "INVENTOINTIAIKA",
        "INVENTOINTIPOIKKEAMA",
      );
      $joinattavat  = array("TUOTENO" => "tuote");
      $saakopoistaa = TRUE;
      break;
    case "budjetti" :
      $pakolliset   = array("KAUSI", "TASO", "SUMMA");
      $wherelliset  = array("KAUSI", "TASO", "SUMMA", "KUSTP", "KOHDE", "PROJEKTI");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "asiakas" :
      $pakolliset    = array("YTUNNUS");

      if ($yhtiorow['asiakkaan_tarkenne'] == '') {
        $wherelliset = array("YTUNNUS", "OVTTUNNUS", "TOIM_OVTTUNNUS");
      }
      elseif ($yhtiorow['asiakkaan_tarkenne'] == 'A') {
        $wherelliset = array("YTUNNUS", "ASIAKASNRO");
      }

      $kielletyt     = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");

      if ($yhtiorow["myyntitilaus_saatavat"] == "Y") {
        $kielletyt[] = "LUOTTORAJA";
      }
      break;
    case "toimi" :
      $pakolliset  = array("YTUNNUS");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset = array("YTUNNUS", "OVTTUNNUS");
      break;
    case "tullinimike" :
      $pakolliset  = array("CN", "KIELI");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "tili" :
      $pakolliset  = array("TILINO");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "maksuehto" :
      $pakolliset  = array("TEKSTI");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "asiakaskommentti" :
      $pakolliset  = array("YTUNNUS", "TUOTENO");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "asiakashinta" :
      $pakolliset   = array("HINTA");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("YTUNNUS", "ASIAKAS_RYHMA", "TUOTENO", "RYHMA", "ASIAKAS");
      $saakopoistaa = TRUE;
      break;
    case "asiakasalennus" :
      $pakolliset   = array("ALENNUS");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("YTUNNUS", "ASIAKAS_RYHMA", "TUOTENO", "RYHMA", "ASIAKAS");
      $saakopoistaa = TRUE;
      break;
    case "toimittajahinta" :
      $pakolliset   = array("HINTA");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("TUOTENO", "RYHMA", "TOIMITTAJA", "YTUNNUS", "LAJI");
      $saakopoistaa = TRUE;
      break;
    case "toimittajaalennus" :
      $pakolliset   = array("ALENNUS");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("TUOTENO", "RYHMA", "TOIMITTAJA", "YTUNNUS");
      $saakopoistaa = TRUE;
      break;
    case "perusalennus" :
      $pakolliset   = array("RYHMA");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("RYHMA");
      break;
    case "yhteyshenkilo" :
      if (is_array($otsikot) and in_array("YTUNNUS", $otsikot) and !in_array("LIITOSTUNNUS", $otsikot)) {
        $pakolliset = array("TYYPPI", "NIMI");
      }
      else {
        $pakolliset = array("LIITOSTUNNUS", "TYYPPI", "NIMI");
      }
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $joinattavat  = array("YTUNNUS" => "asiakas");
      $saakopoistaa = TRUE;
      break;
    case "avainsana" :
      $pakolliset   = array("LAJI", "SELITE");
      $wherelliset  = array("LAJI", "SELITE", "SELITETARK", "SELITETARK_2", "KIELI");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $joinattavat  = array("PERHE" => "avainsana");
      $saakopoistaa = TRUE;
      break;
    case "tuoteperhe" :
      $pakolliset   = array("ISATUOTENO", "TUOTENO", "TYYPPI");
      $wherelliset  = array("ISATUOTENO", "TUOTENO", "TYYPPI");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "yhtion_toimipaikat" :
      $pakolliset   = array("OVTLISA", "NIMI");
      $wherelliset  = array("OVTLISA", "NIMI");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "rahtimaksut" :
      $pakolliset   = array("TOIMITUSTAPA", "KILOTALKU", "KILOTLOPPU", "KMALKU", "KMLOPPU");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "tuotteen_avainsanat" :
      $pakolliset   = array("TUOTENO", "LAJI", "KIELI");
      $wherelliset  = array("TUOTENO", "LAJI", "KIELI");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $joinattavat  = array("TUOTENO" => "tuote");
      $saakopoistaa = TRUE;
      break;
    case "kalenteri" :
      $pakolliset   = array("KUKA", "ASIAKAS", "PVMALKU");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "yhteensopivuus_auto" :
      $pakolliset   = array("MERKKI", "MALLI", "MALLITARKENNE", "KORITYYPPI", "CC", "MOOTTORITYYPPI", "POLTTOAINE", "SYLINTERIMAARA", "SYLINTERINHALKAISIJA", "TEHO_KW", "TEHO_HV", "ALKUKK", "ALKUVUOSI", "LOPPUKK", "LOPPUVUOSI", "LISATIEDOT", "AUTODATA");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("MERKKI", "MALLI", "MALLITARKENNE", "KORITYYPPI", "CC", "MOOTTORITYYPPI", "POLTTOAINE", "SYLINTERIMAARA", "SYLINTERINHALKAISIJA", "TEHO_KW", "TEHO_HV", "ALKUKK", "ALKUVUOSI", "LOPPUKK", "LOPPUVUOSI", "LISATIEDOT", "AUTODATA");
      break;
    case "yhteensopivuus_auto_2" :
      $pakolliset   = array("AUMERKKI", "AUTUN");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("AUMERKKI", "AUTUN");
      break;
    case "yhteensopivuus_mp" :
      $pakolliset   = array("TYYPPI", "MERKKI", "CC", "MALLI", "VM");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("TYYPPI", "MERKKI", "CC", "MALLI", "VM");
      break;
    case "yhteensopivuus_tuote" :
      $pakolliset   = array("TYYPPI", "ATUNNUS", "TUOTENO");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("TYYPPI", "ATUNNUS", "TUOTENO");
      $saakopoistaa = TRUE;
      break;
    case "yhteensopivuus_tuote_lisatiedot" :
      $pakolliset   = array("YHTEENSOPIVUUS_TUOTE_TUNNUS", "OTSIKKO", "ARVO", "KIELI");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      // HUOM: tässä hieman fuulataan joinattavia. Tsekkaa koodi lue_datassa, tämä on spessukeissi....
      $joinattavat  = array("TUOTENO" => "yhteensopivuus_tuote");
      $saakopoistaa = TRUE;
      break;
    case "yhteensopivuus_tuote_sensori" :
      $pakolliset   = array("TYYPPI", "ATUNNUS", "TUOTENO", "SENSORITUOTENO");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("TYYPPI", "ATUNNUS", "TUOTENO", "SENSORITUOTENO", "SENSORIRYHMA");
      $saakopoistaa = TRUE;
      break;
    case "yhteensopivuus_tuote_sensori_lisatiedot" :
      $pakolliset   = array("YHTEENSOPIVUUS_TUOTE_SENSORI_TUNNUS");
      $wherelliset  = array("YHTEENSOPIVUUS_TUOTE_SENSORI_TUNNUS", "OTSIKKO", "ARVO");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      // HUOM: tässä hieman fuulataan joinattavia. Tsekkaa koodi lue_datassa, tämä on spessukeissi....
      $joinattavat  = array("TUOTENO" => "yhteensopivuus_tuote_sensori");
      $saakopoistaa = TRUE;
      break;
    case "yhteensopivuus_rekisteri" :
      $pakolliset   = array("REKNO", "AJONEUVOLAJI", "AUTOID", "MAA");
      $kielletyt    = array();
      $saakopoistaa = TRUE;
      break;
    case "rekisteritiedot_lisatiedot" :
      $pakolliset   = array("VALMISTENUMERO");
      $kielletyt    = array();
      $eiyhtiota    = "EILAATIJAA";
      $saakopoistaa = TRUE;
      break;
    case "yhteensopivuus_valmistenumero" :
      $pakolliset   = array("TUOTENO", "VALMISTENUMERO");
      $kielletyt    = array();
      $eiyhtiota    = "EILAATIJAA";
      $saakopoistaa = TRUE;
      break;
    case "tuotteen_orginaalit" :
      $pakolliset   = array("TUOTENO", "ORIG_TUOTENO");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("TUOTENO", "ORIG_TUOTENO", "MERKKI");
      $saakopoistaa = TRUE;
      break;
    case "autodata" :
      $pakolliset   = array("AUTODATAID");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "autodata_tuote" :
      $pakolliset   = array("TUOTERYHMA", "OP_REF");
      $kielletyt    = array();
      break;
    case "rekisteritiedot" :
      $pakolliset   = array("REKNO", "MAA");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "sanakirja" :
      $pakolliset   = array("FI");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $eiyhtiota    = "TRIP";
      break;
    case "tuotteen_toimittajat" :
      $pakolliset   = array("TUOTENO", "LIITOSTUNNUS");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM", "TEHDAS_SALDO_PAIVITETTY");
      $joinattavat  = array("TUOTENO" => "tuote");
      $saakopoistaa = TRUE;
      break;
    case "tuotteen_toimittajat_pakkauskoot" :
      $pakolliset   = array("TOIM_TUOTENO_TUNNUS");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("PAKKAUSKOKO", "YKSIKKO");
      $saakopoistaa = TRUE;
      break;
    case "tuotteen_toimittajat_tuotenumerot" :
      $pakolliset   = array("TOIM_TUOTENO_TUNNUS");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("TUOTENO", "VIIVAKOODI");
      $saakopoistaa = TRUE;
      break;
    case "kuka" :
      $pakolliset   = array("KUKA");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "todo" :
      $pakolliset   = array("KUVAUS", "PRIORITEETTI", "PYYTAJA");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "hinnasto" :
      $pakolliset   = array("TUOTENO", "HINTA");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("LAJI", "MAA", "VALKOODI");
      $joinattavat  = array("TUOTENO" => "tuote");
      $saakopoistaa = TRUE;
      break;
    case "rahtisopimukset" :
      $pakolliset  = array("YTUNNUS", "RAHTISOPIMUS", "TOIMITUSTAPA");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "toimitustapa" :
      $pakolliset  = array("SELITE", "TULOSTUSTAPA");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "tuotteen_alv" :
      $pakolliset  = array("TUOTENO", "MAA");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $joinattavat = array("TUOTENO" => "tuote");
      break;
    case "sarjanumeron_lisatiedot" :
      $pakolliset  = array("LIITOSTUNNUS");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "kustannuspaikka" :
      $pakolliset  = array("TYYPPI", "NIMI");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "taso" :
      $pakolliset  = array("TYYPPI", "TASO", "NIMI");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "etaisyydet" :
      $pakolliset  = array("VARASTO_POSTINO", "POSTINO", "KM", "POSTITP");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset = array("VARASTO_POSTINO", "POSTINO");
      $saakopoistaa = TRUE;
      break;
    case "abc_parametrit" :
      $pakolliset  = array("TYYPPI", "LUOKKA");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset = array("TYYPPI", "LUOKKA");
      break;
    case "pakkaus" :
      $pakolliset  = array("PAKKAUS", "PAKKAUSKUVAUS");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset = array("PAKKAUS", "PAKKAUSKUVAUS");
      break;
    case "liitetiedostot" :
      $pakolliset  = array("LIITOS", "LIITOSTUNNUS", "KAYTTOTARKOITUS", "FILENAME");
      $kielletyt   = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM", "DATA", "IMAGE_BITS", "IMAGE_CHANNELS", "IMAGE_HEIGHT", "IMAGE_WIDTH", "FILETYPE", "FILESIZE");
      break;
    case "asiakkaan_avainsanat" :
      if (is_array($otsikot) and in_array("YTUNNUS", $otsikot) and !in_array("LIITOSTUNNUS", $otsikot)) {
        $pakolliset  = array("LAJI", "AVAINSANA");
        $wherelliset = array("LAJI", "AVAINSANA", "KIELI");
      }
      else {
        $pakolliset  = array("LIITOSTUNNUS", "LAJI", "AVAINSANA");
        $wherelliset = array("LIITOSTUNNUS", "LAJI", "AVAINSANA", "KIELI");
      }

      $joinattavat  = array("LIITOSTUNNUS" => "asiakas");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "dynaaminen_puu_avainsanat" :
      $pakolliset   = array("LIITOSTUNNUS", "LAJI", "AVAINSANA", "TARKENNE");
      $wherelliset  = array("LIITOSTUNNUS", "LAJI", "AVAINSANA", "TARKENNE");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "puun_alkio" :
      $pakolliset   = array("KIELI", "PUUN_TUNNUS", "LIITOS");
      $kielletyt    = array("LAJI", "LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("KIELI", "PUUN_TUNNUS", "LIITOS", "JARJESTYS");
      $saakopoistaa = TRUE;
      break;
    case "auto_vari" :
      $pakolliset   = array("MERKKI", "VARIKOODI", "VARINIMI", "VARINUMERO");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("ALKUVUOSI", "LOPPUVUOSI", "METALLIC", "VARIAATIO");
      $saakopoistaa = TRUE;
      break;
    case "auto_vari_tuote" :
      $pakolliset   = array("VARIKOODI", "TUOTENO", "NIMITYS");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "auto_vari_korvaavat" :
      $pakolliset   = array("VARIKOODI", "KORVAAVA_VARIKOODI");
      $wherelliset  = array("VARIKOODI", "KORVAAVA_VARIKOODI", "AVK_TUNNUS");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "autoid_lisatieto" :
      $pakolliset   = array("TYPNR");
      $wherelliset  = array("TYPNR");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "vak" :
    case "vak_imdg" :
      $pakolliset   = array("YK_NRO");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $wherelliset  = array("NIMI_JA_KUVAUS", "LUOKKA", "LUOKITUSKOODI", "PAKKAUSRYHMA", "LIPUKKEET", "ERITYISMAARAYKSET");
      break;
    case "extranet_kayttajan_lisatiedot" :
      $pakolliset   = array("LIITOSTUNNUS", "LAJI", "SELITE");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "varaston_hyllypaikat":
      $pakolliset   = array("VARASTO", "HYLLYALUE", "HYLLYNRO", "HYLLYVALI", "HYLLYTASO");
      $wherelliset  = array("VARASTO", "HYLLYALUE", "HYLLYNRO", "HYLLYVALI", "HYLLYTASO");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "toimitustavan_lahdot":
      $pakolliset   = array("LAHDON_VIIKONPVM", "LAHDON_KELLONAIKA", "LIITOSTUNNUS");
      $wherelliset  = array("LAHDON_VIIKONPVM", "LAHDON_KELLONAIKA", "LIITOSTUNNUS", "VIIMEINEN_TILAUSAIKA", "KERAILYN_ALOITUSAIKA", "TERMINAALIALUE", "ASIAKASLUOKKA", "VARASTO");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "lahdot":
      $pakolliset   = array("PVM", "LAHDON_VIIKONPVM", "LAHDON_KELLONAIKA", "LIITOSTUNNUS");
      $wherelliset  = array("PVM", "LAHDON_VIIKONPVM", "LAHDON_KELLONAIKA", "LIITOSTUNNUS", "VIIMEINEN_TILAUSAIKA", "KERAILYN_ALOITUSAIKA", "TERMINAALIALUE", "ASIAKASLUOKKA", "VARASTO");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "laite" :
      $pakolliset = array("SARJANRO", "TUOTENO");
      $kielletyt = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "laitteen_sopimukset" :
      $pakolliset = array("LAITTEEN_TUNNUS", "SOPIMUSRIVIN_TUNNUS");
      $saakopoistaa = TRUE;
      break;
    case "rahtikirjanumero":
      $pakolliset   = array("RAHTIKIRJANRO");
      $wherelliset  = array();
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      break;
    case "pakkauskoodit":
      $pakolliset   = array("PAKKAUS", "RAHDINKULJETTAJA", "KOODI");
      $wherelliset  = array("PAKKAUS", "RAHDINKULJETTAJA", "KOODI");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "rahdinkuljettajat":
      $pakolliset   = array("NIMI", "KOODI");
      $wherelliset  = array("NIMI", "KOODI", "NEUTRAALI");
      $kielletyt    = array("LAATIJA", "LUONTIAIKA", "MUUTTAJA", "MUUTOSPVM");
      $saakopoistaa = TRUE;
      break;
    case "customers_users" :
      $pakolliset = array("USER_ID", "CUSTOMER_ID");
      $kielletyt    = array("YHTIO");
      $eiyhtiota    = "EITUNNUSTAKAAN";
      $saakopoistaa = TRUE;
      break;
    default :
      $pakolliset   = array("TUNTEMATON_TAULU");
      $kielletyt    = array();
    }

    // Haetaan kenttien pakollisuudet sekä oletukset MySQL aliaksista. Selitetark_2 on profiilin nimi, joten haetaan vain "oletusprofiilista"
    $query = "SELECT selite, selitetark_3, selitetark_4
              FROM avainsana use index (yhtio_laji_selite)
              WHERE yhtio      = '{$kukarow["yhtio"]}'
              AND laji         = 'MYSQLALIAS'
              AND selite       LIKE '$table.%'
              AND selitetark_2 = 'Default'";
    $result = pupe_query($query);

    while ($row = mysql_fetch_assoc($result)) {
      // Selitteessä on tietokannan sarakkeen nimi muodossa asiakas.luottoraja
      if (strpos($row["selite"], ".") !== FALSE) {
        list($taulu, $sarake) = explode(".", $row["selite"]);
        $sarake = strtoupper(trim($sarake));

        // Pakollisuus
        if ($row["selitetark_3"] == "PAKOLLINEN" and !in_array($sarake, $pakolliset)) {
          $eisaaollatyhja[] = $sarake;
        }

        // Oletusarvo
        if ($row["selitetark_4"] != "") {
          $oletukset[$sarake] = $row["selitetark_4"];
        }
      }
    }

    return array($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattavat, $saakopoistaa, $oletukset, $eisaaollatyhja);
  }
}
