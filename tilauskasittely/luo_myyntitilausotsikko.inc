<?php

if (!function_exists("luo_myyntitilausotsikko")) {
  function luo_myyntitilausotsikko($toim, $asiakasid, $tilausnumero = '', $myyjanro = '', $viesti = '', $kantaasiakastunnus = '', $ohjelmamoduli = '', $tilaustyyppi = '', $yhtiotoimipaikka = '', $ei_ketjutus = '', $eilahe_osatoim = '', $toimitustapa = '') {
    global $kukarow, $yhtiorow, $session;

    $query  = "SELECT *,
               if(asiakas.gsm != '', asiakas.gsm,
                 if(asiakas.tyopuhelin != '', asiakas.tyopuhelin,
                   if(asiakas.puhelin != '', asiakas.puhelin, ''))) AS toim_puh
               FROM asiakas
               WHERE yhtio = '$kukarow[yhtio]'
               and tunnus  = '$asiakasid'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      $asiakasrow  = mysql_fetch_assoc($result);

      if (empty($asiakasrow['maksuehto'])) {
        $query = "SELECT *
                  FROM maksuehto
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND kaytossa = ''
                  ORDER BY jarjestys ASC
                  LIMIT 1";
        $result = pupe_query($query);

        $maksuehto_row = mysql_fetch_assoc($result);
        $asiakasrow['maksuehto'] = $maksuehto_row['tunnus'];
      }

      $ytunnus           = $asiakasrow["ytunnus"];
      $nimi              = $asiakasrow["nimi"];
      $nimitark          = $asiakasrow["nimitark"];
      $osoite            = $asiakasrow["osoite"];
      $postino           = $asiakasrow["postino"];
      $postitp           = $asiakasrow["postitp"];
      $maa               = $asiakasrow["maa"];
      $tnimi             = $asiakasrow["toim_nimi"];
      $tnimitark         = $asiakasrow["toim_nimitark"];
      $tosoite           = $asiakasrow["toim_osoite"];
      $tpostino          = $asiakasrow["toim_postino"];
      $tpostitp          = $asiakasrow["toim_postitp"];
      $toim_maa          = $asiakasrow["toim_maa"];
      $tpuh              = $asiakasrow["toim_puh"];
      $laskutus_nimi     = $asiakasrow["laskutus_nimi"];
      $laskutus_nimitark = $asiakasrow["laskutus_nimitark"];
      $laskutus_osoite   = $asiakasrow["laskutus_osoite"];
      $laskutus_postino  = $asiakasrow["laskutus_postino"];
      $laskutus_postitp  = $asiakasrow["laskutus_postitp"];
      $laskutus_maa      = $asiakasrow["laskutus_maa"];
      $kolm_nimi         = $asiakasrow["kolm_nimi"];
      $kolm_nimitark     = $asiakasrow["kolm_nimitark"];
      $kolm_osoite       = $asiakasrow["kolm_osoite"];
      $kolm_postino      = $asiakasrow["kolm_postino"];
      $kolm_postitp      = $asiakasrow["kolm_postitp"];
      $kolm_maa          = $asiakasrow["kolm_maa"];
      $kolm_ovttunnus    = $asiakasrow["kolm_maa"];
      $verkkotunnus      = $asiakasrow["verkkotunnus"];
      $piiri             = $asiakasrow["piiri"];
      $laskutyyppi       = $asiakasrow["laskutyyppi"];
      $lahetetyyppi      = pupesoft_lahetetyyppi(0, $asiakasrow['tunnus']);

      if ((int) $kukarow["kesken"] == 0 and $asiakasrow['toimipaikka'] != 0) {
        if ($kukarow['toimipaikka'] != $asiakasrow['toimipaikka'] and $yhtiorow['myyntitilauksen_toimipaikka'] == 'A') {
          $kukarow['toimipaikka'] = $asiakasrow['toimipaikka'];
          $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
        }
      }

      $kohde  = "";
      $eilahe = "";
      $sisainen = "";

      if (($kukarow['extranet'] != '' and $kukarow['hyvaksyja'] != "") or $yhtiorow['lahetteen_tulostustapa'] == 'I'
        or $kukarow['eilahetetta'] == 'o') {
        $eilahe = "o";
      }

      // Luodaan suoraan sisäinen lasku, jos asiakkaan taakse on näin valittu
      if ($asiakasrow['chn'] == "888") {
        $sisainen = "o";
      }

      if (isset($asiakasrow["spec_ytunnus"]) and $asiakasrow["spec_ytunnus"] != '') {
        $ytunnus         = $asiakasrow["spec_ytunnus"];
        $asiakasrow["ytunnus"]   = $asiakasrow["spec_ytunnus"];
      }

      if (isset($asiakasrow["spec_tunnus"]) and $asiakasrow["spec_tunnus"] != '') {
        $asiakasid         = $asiakasrow["spec_tunnus"];
        $asiakasrow["tunnus"]   = $asiakasrow["spec_tunnus"];
      }

      if ($toim == "TARJOUS" or $asiakasrow["vienti"] =='K') {
        $kolm_nimi     = $asiakasrow["kolm_nimi"];
        $kolm_nimitark   = $asiakasrow["kolm_nimitark"];
        $kolm_osoite   = $asiakasrow["kolm_osoite"];
        $kolm_postino   = $asiakasrow["kolm_postino"];
        $kolm_postitp   = $asiakasrow["kolm_postitp"];
      }

      $maksuehto = $asiakasrow["maksuehto"];

      if ($myyjanro != "") {
        $apuqu = "SELECT *
                  from kuka use index (yhtio_myyja)
                  where yhtio = '$kukarow[yhtio]'
                  and myyja   = '$myyjanro'
                  AND myyja   > 0";
        $meapu = pupe_query($apuqu);

        if (mysql_num_rows($meapu)==1) {
          $apuro = mysql_fetch_assoc($meapu);
          $myyja = $apuro['tunnus'];
        }
        elseif (mysql_num_rows($meapu)>1) {
          echo "<font class='error'>".t("Syöttämäsi myyjänumero")." $myyjanro ".t("löytyi usealla käyttäjällä")."!</font><br><br>";
          $myyja = 0;
        }
        else {
          echo "<font class='error'>".t("Syöttämäsi myyjänumero")." $myyjanro ".t("ei löytynyt")."!</font><br><br>";
          $myyja = 0;
        }
      }

      if (!isset($myyja) or $myyja == 0) {
        $myyja = $kukarow["tunnus"];
      }

      $alv         = $asiakasrow["alv"];
      $ovttunnus       = $asiakasrow["ovttunnus"];
      $toim_ovttunnus   = $asiakasrow["toim_ovttunnus"];
      $chn         = $asiakasrow["chn"];
      $maksuteksti     = "";
      $tilausvahvistus  = $asiakasrow["tilausvahvistus"];
      $laskutusvkopv     = $asiakasrow["laskutusvkopv"];
      $vienti       = $asiakasrow["vienti"];

      if ($ei_ketjutus != "") {
        $ketjutus     = 'o';
      }
      else {
        $ketjutus    = $asiakasrow["ketjutus"];
      }

      $valkoodi       = $asiakasrow["valkoodi"];
      $sisviesti1      = $asiakasrow["sisviesti1"];
      $sisviesti2      = $asiakasrow["sisviesti2"];
      $rahtivapaa     = $asiakasrow["rahtivapaa"];

      if ($asiakasrow["myynti_kommentti1"] != "") {
        $comments = trim($comments." ".$asiakasrow["myynti_kommentti1"]);
      }

      if ($asiakasrow["tilaus_viesti"] != "") {
        $viesti = trim($viesti." ".$asiakasrow["tilaus_viesti"]);
      }

      //annetaan extranet-tilaukselle aina paras prioriteetti
      if ($kukarow["extranet"] != '' and empty($yhtiorow['extranet_keraysprioriteetti'])) {
        $query = "SELECT distinct selite
                  FROM avainsana
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and laji     = 'asiakasluokka'
                  and selite  != ''
                  ORDER BY 1
                  LIMIT 1";
        $prioresult = pupe_query($query);
        $priorow = mysql_fetch_assoc($prioresult);

        $luokka = $priorow["selite"];
      }
      else {
        $luokka = $asiakasrow["luokka"];
      }

      $erikoisale  = $asiakasrow["erikoisale"];

      if ($yhtiorow["splittauskielto"] != '') {
        $splittauskielto = "E";
      }
      else {
        $splittauskielto = "";
      }

      $jtkielto     = $asiakasrow['jtkielto'];
      $toimitustapa   = !empty($toimitustapa) ? $toimitustapa : $asiakasrow["toimitustapa"];

      // Toimitusehto
      if (trim($asiakasrow["toimitusehto"]) != "") {
        $yhtiorow['oletus_toimitusehto'] = $asiakasrow["toimitusehto"];
      }

      $toimitusehto  = substr($yhtiorow['oletus_toimitusehto'], 0, 3);
      $kasitoimehto  = substr($yhtiorow['oletus_toimitusehto'], 4);

      $toimitusehto2 = substr($yhtiorow['oletus_toimitusehto2'], 0, 3);
      $kasitoimehto2 = substr($yhtiorow['oletus_toimitusehto2'], 4);

      // Käytetäänkö lähettäjän vai vastaanottajan rahtisopparia
      $apuqu = "SELECT *
                from toimitustapa
                where yhtio = '$kukarow[yhtio]'
                and selite  = '$toimitustapa'";
      $meapu = pupe_query($apuqu);
      $apuro = mysql_fetch_assoc($meapu);

      $maksaja = $apuro['merahti'];

      if ($yhtiorow['nouto_suoraan_laskutukseen'] == 'K' and in_array($toim, array("RIVISYOTTO", "PIKATILAUS")) and $apuro['nouto'] != '') {
        $eilahe = 'o';
      }

      // Onko toimitusehdon takana määritelty rahdinmaksaja? Tämä yliajaa toimitustavan takana olevan.
      $toimehto_tresult = t_avainsana("TOIMEHTO", "", " and trim(concat_ws(' ', selite, selitetark)) = trim('$yhtiorow[oletus_toimitusehto]') ");

      if (mysql_num_rows($toimehto_tresult) > 0) {
        $toimehto_row = mysql_fetch_assoc($toimehto_tresult);

        if ($toimehto_row["selitetark_3"] == "LAHETTAJAN_SOPIMUS") {
          $maksaja = "K";
        }
        elseif ($toimehto_row["selitetark_3"] == "VASTAANOTTAJAN_SOPIMUS") {
          $maksaja = "";
        }
      }

      $yhteysquery = "SELECT nimi
                      FROM yhteyshenkilo
                      where yhtio              = '$kukarow[yhtio]'
                      and liitostunnus         = '$asiakasrow[tunnus]'
                      and tyyppi               = 'A'
                      and tilausyhteyshenkilo != ''
                      and oletusyhteyshenkilo != ''
                      ORDER BY nimi
                      LIMIT 1";
      $yhteysresult = pupe_query($yhteysquery);

      if ($yhteysrow = mysql_fetch_assoc($yhteysresult)) {
        $tilausyhteyshenkilo = $yhteysrow['nimi'];
      }

      $yhteyshenkilo_kaupallinen  = "";
      $yhteyshenkilo_tekninen    = "";

      if ($toim == "PIKATILAUS" and in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'X', 'Y', 'A', 'B', 'C', 'V', 'W', 'I', 'J'))) {

        $myynnista_osto_avainsanat = t_avainsana("MYYNNISTA_OSTO");

        if (mysql_num_rows($myynnista_osto_avainsanat) > 0) {
          $ostotilauksen_kasittely = $yhtiorow['ostotilauksen_kasittely'];
        }
      }
    }
    else {
      //yhtiön oletusalvi!
      $xwquery = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
      $xwtres  = pupe_query($xwquery);
      $xwtrow  = mysql_fetch_assoc($xwtres);

      $alv = (float) $xwtrow["selite"];

      $ytunnus = "*";

      // Jos on varasto setattuna, pässätään eteenpäin
      $varasto = 0;

      if ($toim == "REKLAMAATIO" and $tilaustyyppi == 'U' and $yhtiorow['takuuvarasto'] != 0) {
        $varasto = $yhtiorow['takuuvarasto'];
      }
      elseif (strpos($kukarow["varasto"], ',') === FALSE and (int) $kukarow["varasto"] > 0) {
        // Jos kukarow varasto on vain YKSI varasto, niin silloin valitaan $kukarow['varasto']
        $varasto = $kukarow['varasto'];
      }
      elseif ($yhtiorow['oletusvarasto_tilaukselle'] == 'O' and (int) $kukarow["oletus_varasto"] > 0) {
        // jos kukarow['oletus_varasto'] valittu ja myyntitilauksella ei ole varastoa valittuna, valitaan käyttäjän oletusvarasto
        $varasto = $kukarow['oletus_varasto'];
      }
    }

    if ($valkoodi == '') {
      $valkoodi = $yhtiorow["valkoodi"]."##";
    }
    else {
      $query = "SELECT nimi, kurssi
                FROM valuu
                WHERE yhtio = '$kukarow[yhtio]'
                and nimi= '$valkoodi'";
      $vresult = pupe_query($query);

      if (mysql_num_rows($vresult) == 1) {
        $vrow = mysql_fetch_assoc($vresult);

        $valkoodi = $vrow["nimi"]."##".$vrow["kurssi"];
      }
      else {
        $valkoodi = $yhtiorow["valkoodi"]."##";
      }
    }

    $toimvv = date("Y");
    $toimkk = date("m");
    $toimpp = date("d");

    $kervv = date("Y");
    $kerkk = date("m");
    $kerpp = date("d");

    $alv_velvollisuus = "";

    // jos meillä on lasku menossa ulkomaille
    if ($maa != "" and $maa != $yhtiorow["maa"]) {
      // tutkitaan ollaanko siellä alv-rekisteröity
      $alhqur = "SELECT *
                 FROM yhtion_toimipaikat
                 WHERE yhtio     = '$kukarow[yhtio]'
                 AND maa         = '$maa'
                 AND vat_numero != ''";
      $alhire = pupe_query($alhqur);

      // ollaan alv-rekisteröity, aina kotimaa myynti ja alvillista
      if (mysql_num_rows($alhire) == 1) {

        $alhiro  = mysql_fetch_assoc($alhire);

        // haetaan maan oletusalvi
        $query = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='ALVULK' and selitetark!='' and selitetark_2='$maa' limit 1";
        $alhire = pupe_query($query);

        // jos ei löydy niin mennään erroriin
        if (mysql_num_rows($alhire) == 0) {
          echo "<font class='error'>Oletus ALV-kantaa ei löydy asiakkaan maahan $maa! Ei voida jatkaa!</font><br>";
          exit;
        }
        else {
          $apuro  = mysql_fetch_assoc($alhire);
          // nämä tässä keisissä aina näin
          $alv = $apuro["selite"];
          $vienti = "";
          $alv_velvollisuus = $alhiro["vat_numero"];
        }
      }
    }

    if ($toim == "PIKATILAUS") {
      $mainostxt_res = t_avainsana('MAINOSTXT_LASKU');

      if (mysql_num_rows($mainostxt_res) > 0) {
        $mainostxt_row = mysql_fetch_assoc($mainostxt_res);
        $mainosteksti = trim($mainostxt_row['selite']."\n".$mainostxt_row['selitetark']);
      }
    }

    if ($yhtiorow['myyntitilaus_osatoimitus'] == 'E' and in_array($toim, array("RIVISYOTTO", "PIKATILAUS"))) {
      $osatoimitus = "o";
    }

    $tilaustyyppi = $tilaustyyppi != '' ? $tilaustyyppi : "N";

    if ($toim == "TARJOUS") {
      $tilaustyyppi = 'T';
    }
    elseif ($toim == "EXTRANET_REKLAMAATIO") {
      $tilaustyyppi = 'R';
    }
    elseif ($toim == "MYYNTITILI") {
      $tilaustyyppi = "M";
    }
    elseif ($toim == "TYOMAARAYS") {
      $tilaustyyppi = "A";
    }
    elseif ($toim == "EXTENNAKKO") {
      // Kun asiakas tekee ext-ennakkotilausta extranetissä
      $tilaustyyppi = "E";
      $clearing  = "EXTENNAKKO";
    }

    $jatka  = "JATKA";
    $tee  = "OTSIK";
    $override_ytunnus_check = "YES";
    $ei_kayttoliittymaa = "kylla";

    if (isset($kukarow["hintojen_vaihto"])) {
      $hintojen_vaihto = $kukarow["hintojen_vaihto"];
    }

    require "otsik.inc";

    if ($session == "") {
      return $id;
    }
    else {
      return $tilausnumero;
    }
  }
}

if (!function_exists("luo_tarjouspohja")) {
  function luo_tarjouspohja($params) {
    global $kukarow, $yhtiorow, $session;

    // valitut_asiakastunnukset on special case tapaus. valitut_asiakastunnukset on setattu ainoastaan silloin kun dummytarjoukselle halutaan liittää asiakkaita.
    // clearing on special case, kun dummy tarjoukselta halutaan luoda asiakkaille oikeat tarjoukset, niin clearing pitää setata näihin tarjouksiin
    $toim = $params['toim'];
    $asiakasid = $params['asiakasid'];
    $tilausnumero = $params['tilausnumero'];
    $valitut_asiakastunnukset = $params['valitut_asiakastunnukset'];
    $alv = $params['alv'];
    $viesti = $params['viesti'];

    //Jos asiakasid < 0 niin kyseessä on special case. Tässä tapauksessa luodaan Extranet Dummytarjousta.
    $query = "SELECT *
              FROM kuka
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$asiakasid'";
    $result = pupe_query($query);
    $asiakasrow  = mysql_fetch_assoc($result);

    $query = "SELECT *
              FROM maksuehto
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND kaytossa    = ''
              AND jv          = ''
              AND kateinen    = ''
              AND jaksotettu  = ''
              AND erapvmkasin = ''
              AND (sallitut_maat = '' OR sallitut_maat LIKE '%$yhtiorow[maa]%')
              ORDER BY jarjestys ASC
              LIMIT 1";
    $result = pupe_query($query);
    $maksuehto_row = mysql_fetch_assoc($result);

    $query = "SELECT *
              FROM toimitustapa
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND nouto      = ''
              AND vak_kielto = ''
              AND (sallitut_maat = '' OR sallitut_maat LIKE '%$yhtiorow[maa]%')
              ORDER BY jarjestys ASC
              LIMIT 1";
    $result = pupe_query($query);
    $default_toimitustapa_row  = mysql_fetch_assoc($result);

    $jatka  = "JATKA";
    $tee  = "OTSIK";
    $override_ytunnus_check = "YES";
    $ei_kayttoliittymaa = "kylla";

    $nimi = t("Tarjouspohja");
    $osoite = t("Tarjouspohjan osoite");
    $postitp = t("Tarjouspohjan postitoimipaikka");
    $postino = t("Tarjouspohjan postinumero");
    $maa = $yhtiorow['maa'];

    $maksuehto = $maksuehto_row['tunnus'];
    $toimitustapa = $default_toimitustapa_row['selite'];

    if ($toim == "TARJOUS") {
      $clearing = "EXTTARJOUS";
      $tilaustyyppi = 'T';
    }
    else {
      $clearing = "EXTENNAKKO";
      $tilaustyyppi = 'E';
    }

    if (!empty($tilausnumero)) {
      $query = "SELECT lasku.*, laskun_lisatiedot.sopimus_lisatietoja
                FROM lasku
                JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                WHERE lasku.yhtio='{$kukarow['yhtio']}'
                AND lasku.tunnus = $tilausnumero";
      $result = pupe_query($query);

      $dummy_tarjous_row = mysql_fetch_assoc($result);
      $sopimus_lisatietoja = $dummy_tarjous_row['sopimus_lisatietoja'];
    }

    require "otsik.inc";

    if (is_array($valitut_asiakastunnukset) and count($valitut_asiakastunnukset) > 0) {
      if (!empty($dummy_tarjous_row['sopimus_lisatietoja'])) {
        $olemassaolevat = explode(",", $dummy_tarjous_row['sopimus_lisatietoja']);
        $lisattavat = array_diff($valitut_asiakastunnukset, $olemassaolevat);
        $asiakas_tunnukset = $dummy_tarjous_row['sopimus_lisatietoja'] . ',' . implode(',', $lisattavat);
      }
      else {
        $asiakas_tunnukset = implode(',', $valitut_asiakastunnukset);
      }
      $query = "UPDATE laskun_lisatiedot
                SET sopimus_lisatietoja = '{$asiakas_tunnukset}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$tilausnumero}'";
      pupe_query($query);
    }

    return $tilausnumero;
  }
}
