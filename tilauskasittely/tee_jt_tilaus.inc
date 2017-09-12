<?php

// $tunnukset jossa on k‰sitelt‰vien tilausrivien tunnukset (tuoteperhe)
// $tunnusarray array jossa on k‰sitelt‰vien tilausrivien tunnukset arrayssa
// $kpl[$tunnukset]   = monta toimitetaan
// $loput[$tunnukset] = KAIKKI/POISTA/JATA/MITA/SUORA mit‰ tehd‰‰n lopuille
// $suoratoimpaikat = paikat josta myyd‰‰n suoratoimituksia
// $tilaus_on_jo = KYLLA niin lis‰t‰‰n rivit $kukarow[kesken] tilaukselle
// $varastosta = mist‰ varastosta myyd‰‰n

if (!function_exists("tee_jt_tilaus")) {
  function tee_jt_tilaus($tunnukset, $tunnusarray, $kpl, $loput, $suoratoimpaikat = array(), $tilaus_on_jo = '', $varastosta = '', $jt_huomioi_pvm = '', $mista_tullaan = '') {
    global $yhtiorow, $kukarow, $toim, $automaattinen_poiminta, $alkuperainen_varasto;

    $debug = 0;

    if ($yhtiorow["varaako_jt_saldoa"] != "") {
      $lisavarattu = " + tilausrivi.varattu";
    }
    else {
      $lisavarattu = "";
    }

    if ($loput[$tunnukset] == 'MITA') {
      if ($toim == "ENNAKKO") {
        $query = "UPDATE tilausrivi SET
                  tyyppi      = 'D'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  in ($tunnukset)
                  and kpl     = 0
                  and tyyppi  = 'E'";
      }
      else {
        $query = "UPDATE tilausrivi SET
                  tyyppi      = 'D'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  in ($tunnukset)
                  and kpl     = 0
                  and var     = 'J'
                  and tyyppi  in ('L','G')";
      }
      $tresult = pupe_query($query);

      $query = "UPDATE sarjanumeroseuranta set myyntirivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and myyntirivitunnus in ($tunnukset)";
      $sarjares = pupe_query($query);

    }
    else {
      if ($toim == "ENNAKKO") {
        $query = "SELECT tilausrivi.*, tuote.ei_saldoa, tilausrivin_lisatiedot.suoraan_laskutukseen, lasku.vienti_kurssi, tilausrivin_lisatiedot.tilausrivilinkki
                  FROM tilausrivi use index (PRIMARY)
                  JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
                  JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
                  LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
                  WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                  and tilausrivi.tunnus  = '$tunnusarray[0]'
                  and tilausrivi.varattu > 0
                  and tilausrivi.tyyppi  = 'E'";
      }
      else {
        $query = "SELECT tilausrivi.*, tilausrivi.jt $lisavarattu jt, tuote.ei_saldoa, tilausrivin_lisatiedot.suoraan_laskutukseen, lasku.osatoimitus, lasku.vienti_kurssi, tilausrivin_lisatiedot.tilausrivilinkki
                  FROM tilausrivi use index (PRIMARY)
                  JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
                  JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
                  LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
                  WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                  and tilausrivi.tunnus  = '$tunnusarray[0]'
                  and tilausrivi.jt $lisavarattu  > 0
                  and tilausrivi.kpl     = 0
                  and tilausrivi.var     = 'J'
                  and tilausrivi.tyyppi  in ('L','G')";
      }
      $rivitres = pupe_query($query);

      if (mysql_num_rows($rivitres) == 0) {
        echo "Tilausrivi‰ '$tunnusarray[0]' ei lˆytynyt<br><br>$query";
        exit;
      }

      ///* T‰m‰ on alkuper‰isen rivinipun eka ja vanhin rivi, k‰ytet‰‰n sit‰ otsikon tekoa varten *///
      $isarivirow = mysql_fetch_assoc($rivitres);

      ///* Etsit‰‰n alkuper‰isen-rivin laskun kaikki tiedot *///
      if ($tilaus_on_jo == "KYLLA") {
        //Tullaan myntitilauksen jtrivijutusta
        $query = "SELECT *
                  FROM lasku
                  WHERE yhtio = '$kukarow[yhtio]' and
                  tunnus      = '$kukarow[kesken]' and
                  tila        in ('N', 'L', 'E', 'G', 'C', 'A')";
        $stresult = pupe_query($query);

        if (mysql_num_rows($stresult) == 0) {
          echo "Otsikkoa '$kukarow[kesken]' ei lˆytynyt";
          exit;
        }

        $query = "SELECT *
                  FROM laskun_lisatiedot
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND otunnus = '$kukarow[kesken]'";
        $lisatiedot_result = pupe_query($query);
        $lisatiedot_row = mysql_fetch_assoc($lisatiedot_result);
      }
      elseif ($isarivirow["osatoimitus"] != "") {
        $query = "SELECT *
                  FROM lasku
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and tunnus   = '$isarivirow[otunnus]'
                  and tila     in ('N','L', 'A')
                  and alatila != 'X'";
        $stresult = pupe_query($query);

        if (mysql_num_rows($stresult) == 0) {
          echo "Otsikkoa '$isarivirow[otunnus]' ei lˆytynyt";
          exit;
        }

        $query = "SELECT *
                  FROM laskun_lisatiedot
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND otunnus = '$isarivirow[otunnus]'";
        $lisatiedot_result = pupe_query($query);
        $lisatiedot_row = mysql_fetch_assoc($lisatiedot_result);
      }
      else {
        // tila pit‰‰ olla l‰hete, tilaus, ennakko, tyˆm‰‰r‰ys tai siirtolista
        $query = "SELECT *
                  FROM lasku
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$isarivirow[otunnus]'
                  and tila    in ('N', 'L', 'E', 'G', 'C', 'A')";
        $stresult = pupe_query($query);

        if (mysql_num_rows($stresult) == 0) {
          echo "Otsikkoa '$isarivirow[otunnus]' ei lˆytynyt";
          exit;
        }
        $otsikkorivi = mysql_fetch_assoc($stresult);

        $query = "SELECT *
                  FROM laskun_lisatiedot
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND otunnus = '$isarivirow[otunnus]'";
        $lisatiedot_result = pupe_query($query);
        $lisatiedot_row = mysql_fetch_assoc($lisatiedot_result);

        if ($debug==1) echo t("Perusotsikko lˆytyi")." $otsikkorivi[nimi] $isarivirow[otunnus]<br>";

        $erpcmlisa1 = "";
        $erpcmlisa2 = "";

        if ($toim == "ENNAKKO") {
          // Tutkitaan perusotsikon er‰p‰iv‰m‰‰r‰‰
          $erpquery = "SELECT *
                       from maksuehto
                       where yhtio = '$kukarow[yhtio]'
                       and tunnus  = '$otsikkorivi[maksuehto]'";
          $erpres = pupe_query($erpquery);
          $meapurow = mysql_fetch_assoc($erpres);

          list($erpcmvv, $erpcmkk, $erpcmpp) = explode('-', $otsikkorivi["erpcm"]);

          $nyt = (int) date("Ymd");
          $erp = (int) $erpcmvv.$erpcmkk.$erpcmpp;

          //Jos alkuper‰isell‰ tilauksella on er‰p‰iv‰m‰‰r‰ syˆtetty k‰sin niin huomioidaan se nyt rivien poiminnassa
          if ($meapurow["erapvmkasin"] != "" and checkdate($erpcmkk, $erpcmpp, $erpcmvv) and $erp > $nyt) {
            $erpcmlisa1 = " and lasku.erpcm = '$otsikkorivi[erpcm]' ";
            $erpcmlisa2 = " erpcm = '$otsikkorivi[erpcm]', ";
          }

          $tila    = 'N';
          $alatila  = 'E';
        }
        elseif ($otsikkorivi['tila']== 'G') {
          $tila     = 'G';
          $alatila   = 'P';
        }
        else {
          $tila    = 'N';
          $alatila  = 'J';
        }

        // Vain saman alkuper‰isen tilauksen rivej‰ samalle otsikolle
        if ($otsikkorivi['jtkielto'] == "Y") {
          // v‰‰rink‰ytet‰‰n hyvaksyja_nyt kentt‰‰...
          $yhdlisa = " and lasku.hyvaksyja_nyt = '$otsikkorivi[tunnus]' ";
        }
        else {
          $yhdlisa = "";
        }

        // Katso kohdistettu ja rahtivapaa IF ELSE alempaa otsikon luonnin yhteydess‰
        if ($yhtiorow["jt_rahti"] == "B") {
          // Ennakot/J‰lkitoimitukset ovat aina rahtivapaita
          // Rahtivapaat tilaukset l‰hetet‰‰n aina l‰hett‰j‰n rahtisopimuksella
          $kohdistettulisa = " and lasku.kohdistettu = 'K'";
          $kohdistettulisa .= " and lasku.rahtivapaa = 'o'";
        }
        elseif ($yhtiorow["jt_rahti"] == "C") {
          // Ennakoihin/J‰lkitoimituksiin lis‰t‰‰n aina rahtikulu
          $kohdistettulisa = " and lasku.kohdistettu = '{$otsikkorivi["kohdistettu"]}'";
          $kohdistettulisa .= " and lasku.rahtivapaa = ''";
        }
        elseif ($yhtiorow["jt_rahti"] == "D") {
          // Ennakot/J‰lkitoimitukset ovat rahtivapaita, jos tilauksen arvo on yli rahtivapausrajan (tilaus-valmis.inc hoitaa rahtikulun kuntoon)
          $kohdistettulisa = " and lasku.kohdistettu = '{$otsikkorivi["kohdistettu"]}'";
          $kohdistettulisa .= " and lasku.rahtivapaa = ''";
        }
        else {
          // Ennakko/J‰lkitoimitus on rahtivapaa, jos alkuper‰inen tilaus oli rahtivapaa
          $kohdistettulisa = " and lasku.kohdistettu = '{$otsikkorivi["kohdistettu"]}'";
          $kohdistettulisa .= " and lasku.rahtivapaa = '{$otsikkorivi['rahtivapaa']}'";
        }

        // Huomioidaanko asiakkaan_tilausnumero
        if ($yhtiorow['jt_asiakkaan_tilausnumero'] == "K") {
          $atlisa = " and lasku.asiakkaan_tilausnumero = '$otsikkorivi[asiakkaan_tilausnumero]' ";
        }
        else {
          $atlisa = "";
        }

        $lisatjoin  = "";
        $lisatehdot = "";

        // Laskun lis‰tietoja ei ole siirtolistoilla,
        // joten joinataan laskun lis‰tiedot vain kun ei siirtolista
        if ($otsikkorivi["tila"] != 'G') {
          $lisatjoin = "JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio = lasku.yhtio
                          AND laskun_lisatiedot.otunnus = lasku.tunnus";

          $lisatehdot = "AND laskun_lisatiedot.laskutus_nimi     = '$lisatiedot_row[laskutus_nimi]'
                         AND laskun_lisatiedot.laskutus_nimitark = '$lisatiedot_row[laskutus_nimitark]'
                         AND laskun_lisatiedot.laskutus_osoite   = '$lisatiedot_row[laskutus_osoite]'
                         AND laskun_lisatiedot.laskutus_postino  = '$lisatiedot_row[laskutus_postino]'
                         AND laskun_lisatiedot.laskutus_postitp  = '$lisatiedot_row[laskutus_postitp]'
                         AND laskun_lisatiedot.laskutus_maa      = '$lisatiedot_row[laskutus_maa]'";
        }
        elseif ($otsikkorivi["tila"] == 'G' and $yhtiorow["jt_siirtolistojen_yhdistaminen"] == 'K') {
          // Ollaan vapauttamassa siirtolistaa,
          // katsotaan halutaanko v‰ltt‰m‰tt‰ uusi otsikko aina
          // vai saako siirtolistoja yhdist‰‰ jos voi
          // Kuitenkin niin, ett‰ mahdollisuuksien mukaan ei eroteta samalla siirtolistalla olleita rivej‰
          // - t‰m‰ tehd‰‰n vanhan siirtolistanumeron mukaan
          $lisatjoin = "JOIN tilausrivi
                          ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus)
                        JOIN tilausrivin_lisatiedot
                          ON (tilausrivin_lisatiedot.yhtio = lasku.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)";

          $lisatehdot = "AND tilausrivin_lisatiedot.vanha_otunnus = {$otsikkorivi["tunnus"]}";
        }

        // Tarvitsemmeko uuden otsikon?
        $query = "SELECT lasku.*
                  FROM lasku
                  $lisatjoin
                  WHERE lasku.yhtio          = '$kukarow[yhtio]'
                  AND lasku.laatija          = '$kukarow[kuka]'
                  AND lasku.alatila          = '$alatila'
                  AND lasku.tila             = '$tila'
                  AND lasku.myyja            = '$otsikkorivi[myyja]'
                  AND lasku.ytunnus          = '$otsikkorivi[ytunnus]'
                  AND lasku.nimi             = '$otsikkorivi[nimi]'
                  AND lasku.nimitark         = '$otsikkorivi[nimitark]'
                  AND lasku.osoite           = '$otsikkorivi[osoite]'
                  AND lasku.postino          = '$otsikkorivi[postino]'
                  AND lasku.postitp          = '$otsikkorivi[postitp]'
                  AND lasku.toim_nimi        = '$otsikkorivi[toim_nimi]'
                  AND lasku.toim_nimitark    = '$otsikkorivi[toim_nimitark]'
                  AND lasku.toim_osoite      = '$otsikkorivi[toim_osoite]'
                  AND lasku.toim_postino     = '$otsikkorivi[toim_postino]'
                  AND lasku.toim_postitp     = '$otsikkorivi[toim_postitp]'
                  AND lasku.toimitustapa     = '$otsikkorivi[toimitustapa]'
                  AND lasku.maksuehto        = '$otsikkorivi[maksuehto]'
                  AND lasku.vienti           = '$otsikkorivi[vienti]'
                  AND lasku.alv              = '$otsikkorivi[alv]'
                  AND lasku.ketjutus         = '$otsikkorivi[ketjutus]'
                  AND lasku.jaksotettu       = '$otsikkorivi[jaksotettu]'
                  AND lasku.toimitusehto     = '$otsikkorivi[toimitusehto]'
                  AND lasku.valkoodi         = '$otsikkorivi[valkoodi]'
                  AND lasku.vienti_kurssi    = '$otsikkorivi[vienti_kurssi]'
                  AND lasku.erikoisale       = '$otsikkorivi[erikoisale]'
                  AND lasku.eilahetetta      = '$isarivirow[suoraan_laskutukseen]'
                  AND lasku.tunnusnippu      = '$otsikkorivi[tunnusnippu]'
                  AND lasku.piiri            = '$otsikkorivi[piiri]'
                  AND lasku.kolmikantakauppa = '{$otsikkorivi['kolmikantakauppa']}'
                  $lisatehdot
                  {$kohdistettulisa}
                  $erpcmlisa1
                  {$atlisa}
                  $yhdlisa";
        $stresult = pupe_query($query);
      }

      if (mysql_num_rows($stresult) != 0) {
        $otsikkorivi = mysql_fetch_assoc($stresult);
        $id = $otsikkorivi['tunnus'];
        if ($debug == 1) echo t("Lis‰‰n laskun olemassaolevaan")." $otsikkorivi[nimi] $id<br>";
      }
      else {
        // tehd‰‰n vanhasta laskusta 1:1 kopio...
        $query = "INSERT into lasku set ";

        $_magentojt = ($otsikkorivi['ohjelma_moduli'] == 'MAGENTOJT' OR $otsikkorivi['ohjelma_moduli'] == 'MAGENTO');

        for ($i=0; $i<mysql_num_fields($stresult); $i++) {

          $fieldname = mysql_field_name($stresult, $i);

          // Tilaan laitetaan jotain speciaalia
          if ($fieldname == 'tila') {
            if ($toim == "ENNAKKO") {
              $query .= "tila='N',";
            }
            elseif ($otsikkorivi['tila']== 'G') {
              $query .= "tila='G',";
            }
            else {
              $query .= "tila='N',";
            }
          }
          // alatilaan laitetaan jotain speciaalia
          elseif ($fieldname == 'alatila') {
            if ($toim == "ENNAKKO") {
              $query .= "alatila='E',";
            }
            elseif ($otsikkorivi['tila']== 'G') {
              $query .= "alatila='P',";
            }
            else {
              $query .= "alatila='J',";
            }
          }
          // tilaustyyppi
          elseif ($fieldname == 'tilaustyyppi') {
            if ($toim == "ENNAKKO") {
              $query .= "tilaustyyppi='N',";
            }
            elseif ($otsikkorivi['tila']== 'G') {
              $query .= "tilaustyyppi='".$otsikkorivi[$fieldname]."',";
            }
            else {
              $query .= "tilaustyyppi='".$otsikkorivi[$fieldname]."',";
            }
          }
          // laatijaksi klikkaaja
          elseif ($fieldname == 'laatija') {
            $query .= "laatija='$kukarow[kuka]',";
          }
          // varasto
          elseif ($fieldname == 'varasto') {
            // Nollataan varasto, koska alkuper‰isen tilauksen varastolla ei ole uudella otsikolla virkaa.
            $query .= "varasto=0,";
          }
          // ker‰ysaika ja toimitusaikaan now
          elseif ($fieldname == 'kerayspvm' or $fieldname == 'toimaika') {

            if ($yhtiorow["saldo_kasittely"] == 'U' and $toim == 'ENNAKKO') {
              $query .= $fieldname."='".$otsikkorivi[$fieldname]."',";
            }
            else {
              $query .= $fieldname."=now(),";
            }
          }
          // Katotaan oisko k‰sinsyˆtetty er‰p‰iv‰m‰‰r‰
          elseif ($fieldname == 'erpcm') {
            if ($erpcmlisa2 != '') {
              $query .= $erpcmlisa2;
            }
            else {
              $query .= "erpcm='',";
            }
          }
          // jaksotetuissa on hyv‰ pist‰‰ vanhatunnus alk.per. otsikon vanhatunnukseksi, ett‰ maksusopimuksissa osataan ketjuttaa tilaukset loppulaskutuksen kohdalla
          elseif ($fieldname == 'vanhatunnus') {
            if ($otsikkorivi['jaksotettu'] > 0) {
              $query .= "vanhatunnus='$otsikkorivi[vanhatunnus]',";
            }
            else {
              $query .= "vanhatunnus='',";
            }
          }

          // n‰m‰ kent‰t tyhjennet‰‰n aina
          elseif ($fieldname == 'kapvm' or
            $fieldname == 'tapvm' or
            $fieldname == 'erpcm' or
            $fieldname == 'olmapvm' or
            $fieldname == 'summa' or
            $fieldname == 'kasumma' or
            $fieldname == 'hinta' or
            $fieldname == 'kate' or
            $fieldname == 'arvo' or
            $fieldname == 'maksuaika' or
            $fieldname == 'lahetepvm' or
            $fieldname == 'h1time' or
            $fieldname == 'viite' or
            $fieldname == 'laskunro' or
            $fieldname == 'mapvm' or
            $fieldname == 'kerayslista' or
            $fieldname == 'viikorkoeur' or
            $fieldname == 'tullausnumero' or
            $fieldname == 'laskutuspvm' or
            $fieldname == 'laskuttaja' or
            $fieldname == 'laskutettu' or
            $fieldname == 'lahetepvm' or
            $fieldname == 'maksaja' or
            $fieldname == 'maksettu' or
            $fieldname == 'maa_maara' or
            $fieldname == 'kuljetusmuoto' or
            $fieldname == 'vakisin_kerays' or
            $fieldname == 'siirtolistan_vastaanotto' or
            $fieldname == 'kauppatapahtuman_luonne' or
            $fieldname == 'sisamaan_kuljetus' or
            $fieldname == 'sisamaan_kuljetusmuoto' or
            $fieldname == 'poistumistoimipaikka' or
            $fieldname == 'poistumistoimipaikka_koodi') {
            $query .= $fieldname."='',";
          }
          elseif ($fieldname == 'kate_korjattu' or
            $fieldname == 'lahetetty_ulkoiseen_varastoon') {
            $query .= $fieldname." = NULL,";
          }
          elseif ($fieldname == 'tilausvahvistus') {

            $tv_query = "SELECT tilausvahvistus, tilausvahvistus_jttoimituksista
                         FROM asiakas
                         where yhtio = '$yhtiorow[yhtio]'
                         and tunnus  = '$otsikkorivi[liitostunnus]'";
            $tv_res = pupe_query($tv_query);
            $tv_row = mysql_fetch_assoc($tv_res);

            if ($tv_row["tilausvahvistus_jttoimituksista"] == "K" or ($yhtiorow["tilausvahvistus_jttoimituksista"] == "K" and $tv_row["tilausvahvistus_jttoimituksista"] != "E")) {
              if (trim($otsikkorivi["tilausvahvistus"]) != "") {
                $query .= $fieldname."='$otsikkorivi[tilausvahvistus]',";
              }
              else {
                $query .= $fieldname."='$tv_row[tilausvahvistus]',";
              }
            }
            else {
              $query .= $fieldname."='',";
            }
          }
          elseif ($fieldname == 'hyvaksyja_nyt' and $otsikkorivi['jtkielto'] == "Y") {
            $query .= $fieldname."='$otsikkorivi[tunnus]',";
          }
          // n‰m‰ kent‰t tyhjennet‰‰n jos jtrivit kootaan yhdelle otsikolle (siis normikeissi)
          elseif ($fieldname == 'sisviesti1' or
            $fieldname == 'tilausyhteyshenkilo' or
            $fieldname == 'asiakkaan_tilausnumero' or
            $fieldname == 'kohde' or
            $fieldname == 'viesti') {

            // Ei nollata asiakkaan tilausnumeroa, jos se pit‰‰ huomioida otsikon etsinn‰ss‰
            $_asiakkaan_tilno = ($fieldname == 'asiakkaan_tilausnumero');
            $_asiakkaan_tilno &= ($yhtiorow['jt_asiakkaan_tilausnumero'] == "K" or $_magentojt);

            if ($toim == "ENNAKKO" or $otsikkorivi['jtkielto'] == "Y" or $_asiakkaan_tilno) {
              $query .= $fieldname."='".$otsikkorivi[$fieldname]."',";
            }
            else {
              $query .= $fieldname."='',";
            }
          }
          elseif ($fieldname == 'sisviesti2') {

            if ($mista_tullaan == "TULLAAN_VALMISTUKSESTA") {
              $query .= $fieldname."='Tehty valmistuksen kautta',";
            }
            else {
              $query .= $fieldname."='".$otsikkorivi[$fieldname]."',";
            }
          }
          // kommentti speciaalia
          elseif ($fieldname == 'comments') {
            if ($toim == "ENNAKKO" or $otsikkorivi['jtkielto'] == "Y") {
              if ($toim == "ENNAKKO") {
                $query .= $fieldname."=trim(concat('".t("ENNAKKOTILAUS")."\n', '".$otsikkorivi[$fieldname]."')),";
              }
              else {
                $query .= $fieldname."=trim(concat('".t("JƒLKITOIMITUS")."\n', '".$otsikkorivi[$fieldname]."')),";
              }
            }
            else {
              if ($toim == "ENNAKKO") {
                $query .= $fieldname."='".t("ENNAKKOTILAUS")."',";
              }
              else {
                $query .= $fieldname."='".t("JƒLKITOIMITUS")."',";
              }
            }
          }
          //HUOM: Laitetaan clearing kentt‰‰n jotain speciaalia
          elseif ($fieldname == 'clearing') {
            if ($toim == "ENNAKKO") {
              $query .= "clearing='ENNAKKOTILAUS',";
            }
            elseif ($otsikkorivi['tila'] == 'G') {
              $query .= "clearing='".$otsikkorivi[$fieldname]."',";
            }
            else {
              $query .= "clearing='JT-TILAUS',";
            }
          }
          elseif ($fieldname == 'eilahetetta') {
            if ($mista_tullaan == "TULLAAN_VALMISTUKSESTA") $query .= "eilahetetta='',";
            else $query .= "eilahetetta='".$isarivirow["suoraan_laskutukseen"]."',";
          }
          elseif ($fieldname == 'kohdistettu') {
            // riipastaan tarkoituksella tyhj‰‰, hoidetaan homma 'rahtivapaa' haarassa
          }
          elseif ($fieldname == 'rahtivapaa') {
            // katso myˆs otsikon haku rivilt‰ 197

            if ($yhtiorow["jt_rahti"] == "B") {
              // Ennakot/J‰lkitoimitukset ovat aina rahtivapaita
              // Rahtivapaat tilaukset l‰hetet‰‰n aina l‰hett‰j‰n rahtisopimuksella
              $query .= "rahtivapaa = 'o',";
              $query .= "kohdistettu = 'K',";
            }
            elseif ($yhtiorow["jt_rahti"] == "C") {
              // Ennakoihin/J‰lkitoimituksiin lis‰t‰‰n aina rahtikulu
              $query .= "rahtivapaa = '',";
              $query .= "kohdistettu = '{$otsikkorivi["kohdistettu"]}',";
            }
            elseif ($yhtiorow["jt_rahti"] == "D") {
              // Ennakot/J‰lkitoimitukset ovat rahtivapaita, jos tilauksen arvo on yli rahtivapausrajan (tilaus-valmis.inc hoitaa rahtikulun kuntoon)
              $query .= "rahtivapaa = '',";
              $query .= "kohdistettu = '{$otsikkorivi["kohdistettu"]}',";
            }
            else {
              // Ennakko/J‰lkitoimitus on rahtivapaa, jos alkuper‰inen tilaus oli rahtivapaa
              $query .= "rahtivapaa = '".$otsikkorivi[$fieldname]."',";
              $query .= "kohdistettu = '{$otsikkorivi["kohdistettu"]}',";
            }
          }
          elseif ($fieldname=='maksuehto') {

            $_maksuehto = $otsikkorivi[$fieldname];

            // katsotaan onko asiakkaan maksuehto muuttunut j‰lkivaatimukseksi
            // T‰t‰ ei tehd‰ jos tilauksen maksuehto on "jaksotettu"
            $maksuehto_query = "SELECT tunnus
                                FROM maksuehto
                                where yhtio     = '$kukarow[yhtio]'
                                and tunnus      = '$otsikkorivi[maksuehto]'
                                and jaksotettu != ''";
            $maksuehto_result = pupe_query($maksuehto_query);

            if (mysql_num_rows($maksuehto_result) == 0) {

              $maksuehto_query = "SELECT maksuehto.jv, maksuehto.tunnus
                                  FROM asiakas
                                  join maksuehto ON asiakas.yhtio = maksuehto.yhtio and asiakas.maksuehto = maksuehto.tunnus
                                  where asiakas.yhtio = '$yhtiorow[yhtio]'
                                  and asiakas.tunnus  = '$otsikkorivi[liitostunnus]'";
              $maksuehto_result = pupe_query($maksuehto_query);
              $maksuehtorow = mysql_fetch_assoc($maksuehto_result);

              if ($maksuehtorow['jv'] != '') {
                $_maksuehto = $maksuehtorow["tunnus"];
              }
            }

            $query .= $fieldname."='$_maksuehto',";
          }
          elseif ($fieldname == 'ohjelma_moduli') {

            if ($_magentojt) {
              $query .= $fieldname."='MAGENTOJT',";
            }
            else {
              $query .= $fieldname."='".$otsikkorivi[$fieldname]."',";
            }

          }
          // ja kaikki muut paitsi tunnus sellaisenaan
          elseif ($fieldname != 'tunnus') {
            $query .= $fieldname."='".$otsikkorivi[$fieldname]."',";
          }
        }

        $query = substr($query, 0, -1);
        $stresult = pupe_query($query);
        $id = mysql_insert_id($GLOBALS["masterlink"]);

        $query = "INSERT INTO laskun_lisatiedot SET ";

        for ($i = 0; $i < mysql_num_fields($lisatiedot_result); $i++) {

          $fieldname = mysql_field_name($lisatiedot_result, $i);

          if ($fieldname == 'laatija') {
            $query .= $fieldname."='$kukarow[kuka]',";
          }
          elseif ($fieldname == 'luontiaika') {
            $query .= $fieldname."=now(),";
          }
          elseif ($fieldname == 'otunnus') {
            $query .= $fieldname."='$id',";
          }
          elseif ($fieldname != 'tunnus') {
            $query .= $fieldname."='".$lisatiedot_row[$fieldname]."',";
          }
        }

        $query = substr($query, 0, -1);
        $lisatiedot_result = pupe_query($query);

        if ($debug==1) echo t("Perustin laskun")." $otsikkorivi[nimi] $id<br>";
      }

      // Otetaan alkuper‰inen varasto talteen, jotta osataan hakea $yhtiorow oikein tilaus-valmis.inc:ss‰
      $alkuperainen_varasto = $otsikkorivi['varasto'];

      ///* T‰ss‰ meill‰ on otsikko hallussa se on $id muuttujassa*///

      $kpl[$tunnukset] = str_replace(",", ".", $kpl["$tunnukset"]);

      // T‰ss‰ haarassa k‰ytt‰j‰ on syˆtt‰nyt jonkun kappalem‰‰r‰n
      if ($kpl[$tunnukset] > 0 or $loput[$tunnukset] == 'KAIKKI' or $loput[$tunnukset] == 'VAKISIN') {

        $jt_vakisin = FALSE;

        if ($loput[$tunnukset] == 'KAIKKI' or $loput[$tunnukset] == 'VAKISIN') {
          if ($toim == "ENNAKKO") {
            $kpl[$tunnukset] = $isarivirow["varattu"];
          }
          else {
            $kpl[$tunnukset] = $isarivirow["jt"];
          }

          if ($loput[$tunnukset] == 'VAKISIN') {
            $jt_vakisin = TRUE;
          }

          $loput[$tunnukset] = 'POISTA';
        }

        if ($toim == "ENNAKKO") {
          $isa_kerroin  = $kpl[$tunnukset] / $isarivirow["varattu"];
          $isa_kpl    = $isarivirow["varattu"];
        }
        else {
          $isa_kerroin   = $kpl[$tunnukset] / $isarivirow["jt"];
          $isa_kpl    = $isarivirow["jt"];
        }

        if (($kpl[$tunnukset] <= $isa_kpl and $kpl[$tunnukset] > 0) or $jt_vakisin === TRUE) {

          $luodut_rivit = array();
          $luodut_kpl = array();

          foreach ($tunnusarray as $tunnus_nyt) {
            if ($toim == "ENNAKKO") {
              $query = "SELECT tilausrivi.*, tuote.ei_saldoa, tilausrivin_lisatiedot.vanha_otunnus, tilausrivin_lisatiedot.tilausrivilinkki
                        FROM tilausrivi
                        LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
                        JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
                        WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                        and tilausrivi.tunnus  = '$tunnus_nyt'
                        and tilausrivi.varattu > 0
                        and tilausrivi.tyyppi  = 'E'";
            }
            else {
              $query = "SELECT tilausrivi.*, tilausrivi.jt $lisavarattu jt, tuote.ei_saldoa, tilausrivin_lisatiedot.vanha_otunnus, tilausrivin_lisatiedot.tilausrivilinkki
                        FROM tilausrivi
                        LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
                        JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
                        WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                        and tilausrivi.tunnus  = '$tunnus_nyt'
                        and tilausrivi.jt $lisavarattu > 0
                        and tilausrivi.kpl     = 0
                        and tilausrivi.var     = 'J'
                        and tilausrivi.tyyppi  in ('L','G')";
            }
            $rivitres = pupe_query($query);
            $lisataanrow = mysql_fetch_assoc($rivitres);

            // jos maksupositioilla tehty j‰lkitoimitustilaus, laitetaan uudelle riville kanssa jaksotettu, jotta lˆydet‰‰n maksusopimuksissa t‰m‰ ja ei anneta loppulaskuttaa liian aikaisin.
            $jaksotettu = $lisataanrow['jaksotettu'];

            if ($toim == "ENNAKKO") {
              $lisataanrow_kpl = round($lisataanrow["varattu"] * $isa_kerroin, 2);

              if ($yhtiorow["saldo_kasittely"] == 'U') {
                $toimaika = $lisataanrow['toimaika'];
                $kerayspvm = $lisataanrow['kerayspvm'];
              }
              else {
                $toimaika = "";
                $kerayspvm = "";
              }
            }
            else {
              $lisataanrow_kpl = round($lisataanrow["jt"] * $isa_kerroin, 2);
            }

            if ($debug == 1) echo t("Yritet‰‰n myyd‰ tuotetta").": $lisataanrow[tuoteno] $lisataanrow_kpl kappaletta.<br>";

            $query = "SELECT *
                      FROM tuote
                      WHERE yhtio  = '$kukarow[yhtio]'
                      and  tuoteno = '$lisataanrow[tuoteno]'";
            $aresult = pupe_query($query);
            $trow = mysql_fetch_assoc($aresult);

            $query = "SELECT *
                      FROM lasku
                      WHERE tunnus = '$id'";
            $aresult = pupe_query($query);
            $laskurow = mysql_fetch_assoc($aresult);

            $talteen_kpl    = $kpl; // lis‰‰rivi rikkoo t‰n, otetaan talteen
            $kukarow["kesken"]  = $id;
            $tuoteno        = $lisataanrow["tuoteno"];
            $netto          = $lisataanrow["netto"];

            for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              ${'ale'.$alepostfix} = $lisataanrow["ale{$alepostfix}"];
            }

            $kpl        = $lisataanrow_kpl;
            $varasto        = $varastosta;
            $korvaavakielto     = "Y";
            $perhekielto       = "Y";
            $osatoimkielto    = "Y";

            if ($jt_vakisin === TRUE) {
              $var = "H";
            }
            else {
              $var = "";
            }

            // Toimitetaanko t‰ss‰ suoratoimitus?
            // $suoratoimpaikat[$tunnus_nyt] --> Suoratoimitusosotrivin varastoonvienti/valmistus ja siit‰ syntyneen tapahtuman tunnus!
            // T‰st‰ saadaan paikka josta t‰‰ suoratoimitus-jt-rivi nyt toimitetaan
            if (isset($suoratoimpaikat[$tunnus_nyt]) and $suoratoimpaikat[$tunnus_nyt] > 0) {

              $paikkaquery = "SELECT concat_ws('#!°!#',hyllyalue,hyllynro,hyllyvali,hyllytaso) paikka
                              FROM tapahtuma
                              WHERE yhtio = '$kukarow[yhtio]'
                              AND tunnus  = '$suoratoimpaikat[$tunnus_nyt]'";
              $paikkares = pupe_query($paikkaquery);
              $paikkarow = mysql_fetch_array($paikkares);

              $paikka = $paikkarow['paikka'];
            }
            else {
              $paikka = "";
            }

            $perheid = $lisataanrow["perheid"];
            $perheid2 = $lisataanrow["perheid2"];

            // Rivin kommentti
            $kommentti = "";

            if (trim($lisataanrow['kommentti']) != "") {
              // Alkuper‰isen rivin kommentti
              $kommentti .= trim($lisataanrow['kommentti']);
            }

            // Rivin nimitys voi olla ollut k‰sinsyˆtetty, joten otetaan se t‰ss‰ talteen
            $tuotenimitys = "";

            if (trim($lisataanrow['nimitys']) != "" and !empty($yhtiorow["nimityksen_muutos_tilauksella"])) {
              $tuotenimitys = trim($lisataanrow['nimitys']);
            }

            unset($orig_laskurow);

            if ($lisataanrow["vanha_otunnus"] > 0) {
              $query = "SELECT tunnus, asiakkaan_tilausnumero, viesti, tilausyhteyshenkilo, sisviesti1, kohde
                        FROM lasku
                        WHERE tunnus  = '{$lisataanrow["vanha_otunnus"]}'
                        AND tila     != 'T'";
              $aresult = pupe_query($query);

              if (mysql_num_rows($aresult) > 0) {
                $orig_laskurow = mysql_fetch_assoc($aresult);
                $alkupotunnus = $lisataanrow["vanha_otunnus"];
              }
            }

            if (!isset($orig_laskurow)) {
              $query = "SELECT tunnus, asiakkaan_tilausnumero, viesti, tilausyhteyshenkilo, sisviesti1, kohde
                        FROM lasku
                        WHERE tunnus = '{$lisataanrow["otunnus"]}'";
              $aresult = pupe_query($query);

              if (mysql_num_rows($aresult) > 0) {
                $orig_laskurow = mysql_fetch_assoc($aresult);
                $alkupotunnus = $lisataanrow["otunnus"];
              }
            }

            if (isset($orig_laskurow)) {

              // Katotaan onko alkuper‰isell‰ tilauksella k‰sinsyˆtetty rahti joka tulisi nyt laskuttaa
              if ($yhtiorow["rahti_hinnoittelu"] == 'K' and $laskurow["rahtivapaa"] == "" and $laskurow["osatoimitus"] == "") {
                $rahtituotelisa = "'$yhtiorow[rahti_tuotenumero]'";
                $rahtituotelisa = lisaa_vaihtoehtoinen_rahti_merkkijonoon($rahtituotelisa);

                $query = "SELECT tunnus
                          FROM tilausrivi
                          WHERE yhtio  = '$kukarow[yhtio]'
                          AND otunnus  = '$alkupotunnus'
                          AND tyyppi  != 'D'
                          AND tuoteno IN ({$rahtituotelisa})
                          AND varattu != 0
                          AND kpl      = 0
                          AND var      in ('','H')";
                $rares = pupe_query($query);

                if (mysql_num_rows($rares) > 0) {
                  // Siirret‰‰n rahti t‰lle tilaukselle
                  $query = "UPDATE tilausrivi
                            SET otunnus = $id,
                            tyyppi       = 'L'
                            WHERE yhtio  = '$kukarow[yhtio]'
                            AND otunnus  = '$alkupotunnus'
                            AND tyyppi  != 'D'
                            AND tuoteno  IN ({$rahtituotelisa})
                            AND varattu != 0
                            AND kpl      = 0
                            AND var      in ('','H')";
                  pupe_query($query);
                }
              }

              // Haetaan asiakkaan kieli, jotta saadaan systeemikommentit oikealle kielelle
              $query = "SELECT kieli
                        FROM asiakas
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  = '{$otsikkorivi['liitostunnus']}'";
              $kielirow = mysql_fetch_assoc(pupe_query($query));
              $kieli = $kielirow["kieli"];

              // Joitain tietoja alkuper‰iselt‰ tilaukselta, vain jos osatoimitus ei ole estetty ja jtrivit yhdistet‰‰n samalle otsikolle ja kyseess‰ ei ole ennakkotilaus
              if ($otsikkorivi["osatoimitus"] == "" and $toim != "ENNAKKO" and $otsikkorivi['jtkielto'] != "Y") {

                if (strpos($kommentti, t("Alkuper‰inen tilaus", $kieli).": ".trim($orig_laskurow['tunnus'])) === FALSE) {
                  if (trim($kommentti) != "") $kommentti .= " / ";
                  // Tilausrivin systeemikommentti
                  $kommentti .= t("Alkuper‰inen tilaus", $kieli).": ".trim($orig_laskurow['tunnus']);
                }

                if (trim($orig_laskurow['asiakkaan_tilausnumero']) != "" and strpos($kommentti, t("Asiakkaan tilaus", $kieli).": ".trim($orig_laskurow['asiakkaan_tilausnumero'])) === FALSE) {
                  if (trim($kommentti) != "") $kommentti .= " / ";
                  // Tilausrivin systeemikommentti
                  $kommentti .= t("Asiakkaan tilaus", $kieli).": ".trim($orig_laskurow['asiakkaan_tilausnumero']);
                }

                if (trim($orig_laskurow['viesti']) != "" and strpos($kommentti, t("Tilausviite", $kieli).": ".trim($orig_laskurow['viesti'])) === FALSE) {
                  if (trim($kommentti) != "") $kommentti .= " / ";
                  // Tilausrivin systeemikommentti
                  $kommentti .= t("Tilausviite", $kieli).": ".trim($orig_laskurow['viesti']);
                }

                if (trim($orig_laskurow['tilausyhteyshenkilo']) != "" and strpos($kommentti, t("Tilaaja", $kieli).": ".trim($orig_laskurow['tilausyhteyshenkilo'])) === FALSE) {
                  if (trim($kommentti) != "") $kommentti .= " / ";
                  // Tilausrivin systeemikommentti
                  $kommentti .= t("Tilaaja", $kieli).": ".trim($orig_laskurow['tilausyhteyshenkilo']);
                }

                if (trim($orig_laskurow['sisviesti1']) != "" and strpos($kommentti, t("Kommentti", $kieli).": ".trim($orig_laskurow['sisviesti1'])) === FALSE) {
                  if (trim($kommentti) != "") $kommentti .= " / ";
                  // Tilausrivin systeemikommentti
                  $kommentti .= t("Kommentti", $kieli).": ".trim($orig_laskurow['sisviesti1']);
                }

                if (trim($orig_laskurow['kohde']) != "" and strpos($kommentti, t("Kohde", $kieli).": ".trim($orig_laskurow['kohde'])) === FALSE) {
                  if (trim($kommentti) != "") $kommentti .= " / ";
                  // Tilausrivin systeemikommentti
                  $kommentti .= t("Kohde", $kieli).": ".trim($orig_laskurow['kohde']);
                }

              }
              elseif ($otsikkorivi["osatoimitus"] == "" and $otsikkorivi['jtkielto'] == "Y") {
                if (strpos($kommentti, t("Alkuper‰inen tilaus", $kieli).": ".trim($orig_laskurow['tunnus'])) === FALSE) {
                  if (trim($kommentti) != "") $kommentti .= " / ";
                  // Tilausrivin systeemikommentti
                  $kommentti .= t("Alkuper‰inen tilaus", $kieli).": ".trim($orig_laskurow['tunnus']);
                }
              }
              elseif ($toim == "ENNAKKO") {
                if (strpos($kommentti, t("Ennakkotilaus", $kieli).": ".trim($orig_laskurow['tunnus'])) === FALSE and $toim == "ENNAKKO") {
                  if (trim($kommentti) != "") $kommentti .= " / ";
                  // Tilausrivin systeemikommentti
                  $kommentti .= t("Ennakkotilaus", $kieli).": ".trim($orig_laskurow['tunnus']);
                }
              }
            }

            if ($automaattinen_poiminta != "" and $toim != "ENNAKKO" and $tilaus_on_jo == "KYLLA" and strpos($kommentti, t("Toimitettavissa oleva JT-rivi", $kieli)."!") === FALSE) {
              if (trim($kommentti) != "") $kommentti .= " / ";
              // Tilausrivin systeemikommentti
              $kommentti .= t("Toimitettavissa oleva JT-rivi", $kieli)."!";
            }

            if ($jt_huomioi_pvm != "") {
              // Jos JT-rivit varaavat saldoa, niin tulevaisuudessa toimitettavat rivit varaavat nykyhetken saldoa vaikka "JTSPEC" tarkoittaa, ett‰ JT-rivien varauksia ei huomioida
              $jtspec = "JTSPEC2";
            }
            else {
              $jtspec = "JTSPEC";
            }

            // Tutkitaan onko t‰m‰ myyty ulkomaan alvilla
            list(, , , $tsek_alehinta_alv, ) = alehinta($laskurow, $trow, $kpl, '', '', '');

            if ($tsek_alehinta_alv > 0) {
              $trow["alv"] = $tsek_alehinta_alv;
            }

            // jos is‰rivi oli k‰‰nteinen alvi, niin laitetaan t‰m‰kin rivi k‰‰nteiseksi
            if ($isarivirow["alv"] >= 600) {
              $alv = $trow["alv"] + 600;
            }

            if ($yhtiorow["alv_kasittely"] == "" and $trow["alv"] != $lisataanrow["alv"] and $lisataanrow["alv"] < 500) {
              $hinta = sprintf('%.2f', round($lisataanrow["hinta"] / (1+$lisataanrow['alv']/100) * (1+$trow["alv"]/100), $yhtiorow['hintapyoristys']));
            }
            else {
              $hinta = $lisataanrow["hinta"];
            }

            // jos k‰ytˆss‰ on myyntihinnan poikkeava m‰‰r‰, kerrotaan hinta takaisin kuntoon.
            if ($trow["myyntihinta_maara"] != 0) {
              $hinta = $hinta * $trow["myyntihinta_maara"];
            }

            if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
              //K‰ytet‰‰n alkuper‰isen JT-rivin otsikon valuuttakurssia
              $hinta = laskuval($hinta, $isarivirow["vienti_kurssi"]);
            }

            if ($yhtiorow['kerayserat'] == 'K' and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
              $siirtolista_query = "SELECT tt.tunnus AS tunnus
                                    FROM tilausrivin_lisatiedot AS tt
                                    JOIN tilausrivi AS t
                                    ON (t.yhtio = tt.yhtio
                                      AND t.tunnus          = tt.tilausrivitunnus
                                      AND t.tyyppi          = 'G' )
                                    WHERE tt.yhtio          = '{$kukarow['yhtio']}'
                                    AND tt.tilausrivilinkki = {$tunnus_nyt}";
              $siirtolista_result = pupe_query($siirtolista_query);
              $siirtolista_row = mysql_fetch_assoc($siirtolista_result);
            }

            // Setataan lisaarivi.inc varten mist‰ tullaan muuttujaan JT_TILAUS,
            // jotta ei pyˆristett‰isi en‰‰ t‰ss‰ vaiheessa t‰ysiin myyntieriin,
            // jos kerran ollaan jo kertaalleen hyv‰ksytty, ett‰ myyd‰‰n vajaa er‰
            // niin ei pit‰isi en‰‰ t‰ss‰ vaiheessa olla tarvetta pyˆristell‰ t‰yteen er‰‰n.
            // Otetaan kuitenkin varmuuden vuoksi talteen alkuper‰inen mist‰ tulaan
            // ja setataan se sitten kun ollaan tultu takasin lisaarivi.incst‰.
            $from_tee_jt_tilaus = TRUE;

            require "lisaarivi.inc";

            $kpl = $talteen_kpl; // lis‰‰rivi rikko t‰n, otetaan haltuun

            if ($debug==1) echo t("Lis‰ttiin tuote").": $tuoteno kpl: $lisataanrow_kpl<br>";

            // Pidet‰‰n muistissa kaikki lis‰tyt rivit
            if (count($lisatyt_rivit1) > 0) {
              // Myyntihaara
              foreach ($lisatyt_rivit1 as $lisatty_tun) {
                $luodut_rivit[$lisatty_tun] = $lisatty_tun;

                //Pidet‰‰n muistissa lis‰tyt kappaleet
                if (isset($luodut_kpl[$tunnus_nyt])) $luodut_kpl[$tunnus_nyt] += $lisataanrow_kpl;
                else $luodut_kpl[$tunnus_nyt] = $lisataanrow_kpl;

                if (!empty($siirtolista_row)) {
                  $query = "UPDATE tilausrivin_lisatiedot
                            SET tilausrivilinkki = {$lisatty_tun}
                            WHERE yhtio = '{$kukarow['yhtio']}'
                            AND tunnus  = {$siirtolista_row['tunnus']}";
                  pupe_query($query);
                }
              }
            }

            if (count($lisatyt_rivit2) > 0) {
              // Puute - JT - Ostohaara
              foreach ($lisatyt_rivit2 as $lisatty_tun) {
                $luodut_rivit[] = $lisatty_tun;
              }
            }

            //Jos saldo ei riitt‰nyt
            if (count($lisatyt_rivit2) > 0) {
              foreach ($luodut_rivit as $poistetaan) {
                $query = "DELETE FROM tilausrivi
                          WHERE yhtio = '$kukarow[yhtio]'
                          and tunnus  = $poistetaan
                          and kpl     = 0";
                $tresult = pupe_query($query);

                $query = "UPDATE sarjanumeroseuranta set myyntirivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and myyntirivitunnus=$poistetaan";
                $sarjares = pupe_query($query);

                if ($debug==1) echo t("Saldo ei riitt‰nyt. Tehd‰‰n rollback")."<br>";
              }

              unset($luodut_rivit);
              unset($luodut_kpl);
              break;
            }

            if (count($lisatyt_rivit1) > 0) {
              // Korjataan sarjanumero-oliot, eli siirret‰‰n ne jtrivilt‰/ennakkorivilt‰ tilausriville
              $query = "SELECT myyntirivitunnus, count(*) kpl
                        FROM sarjanumeroseuranta
                        WHERE sarjanumeroseuranta.yhtio          = '$kukarow[yhtio]'
                        and sarjanumeroseuranta.tuoteno          = '$lisataanrow[tuoteno]'
                        and sarjanumeroseuranta.myyntirivitunnus = '$lisataanrow[tunnus]'
                        group by myyntirivitunnus";
              $sarjares = pupe_query($query);
              $sarjarow = mysql_fetch_assoc($sarjares);

              foreach ($lisatyt_rivit1 as $lisatty_tun) {
                $query = "SELECT *
                          FROM tilausrivi
                          WHERE yhtio = '$kukarow[yhtio]'
                          and otunnus = '$id'
                          and tunnus  = '$lisatty_tun'";
                $lisres = pupe_query($query);
                $lisrow = mysql_fetch_assoc($lisres);

                if ($lisrow["varattu"] != 0) {
                  $lisrow["varattu"] = (int) abs(round($lisrow["varattu"], 0));

                  $query = "UPDATE sarjanumeroseuranta
                            SET myyntirivitunnus = '$lisrow[tunnus]'
                            WHERE yhtio          = '$kukarow[yhtio]'
                            and tuoteno          = '$lisataanrow[tuoteno]'
                            and myyntirivitunnus = '$lisataanrow[tunnus]'
                            ORDER BY tunnus
                            LIMIT $lisrow[varattu]";
                  $cores = pupe_query($query);
                }

                //Kopioidaan tilausrivin lisatiedot
                $query = "SELECT *
                          FROM tilausrivin_lisatiedot
                          WHERE tilausrivitunnus='$lisataanrow[tunnus]' and yhtio ='$kukarow[yhtio]'";
                $monistares2 = pupe_query($query);

                if (mysql_num_rows($monistares2) > 0) {
                  $monistarow2 = mysql_fetch_assoc($monistares2);

                  $kysely = "  UPDATE tilausrivin_lisatiedot SET ";

                  // Ei monisteta tunnusta
                  for ($i=0; $i < mysql_num_fields($monistares2)-1; $i++) {

                    $fieldname = mysql_field_name($monistares2, $i);

                    switch ($fieldname) {
                    case 'yhtio':
                    case 'tilausrivitunnus':
                    case 'tiliointirivitunnus':
                    case 'toimittajan_tunnus':
                    case 'luontiaika':
                    case 'laatija':
                      $values .= "";
                      break;
                    case 'positio':
                      if (($kukarow['extranet'] != '' or $yhtiorow['jt_automatiikka_mitatoi_tilaus'] == 'E') and $tilaus_on_jo == "KYLLA") {
                        $kysely .= "positio='JT',";
                      }
                      else {
                        $kysely .= $fieldname."='".$monistarow2[$fieldname]."',";
                      }
                      break;
                    default:
                      $kysely .= $fieldname."='".$monistarow2[$fieldname]."',";
                    }
                  }

                  $kysely  = substr($kysely, 0, -1);
                  $kysely .= " WHERE yhtio='$kukarow[yhtio]' and tilausrivitunnus='$lisrow[tunnus]'";
                  $insres2 = pupe_query($kysely);
                }

                // jos alkuper‰inen rivi oli suoratoimitettu asiakkaalle, merkataan rivit ker‰tyksi ja toimitetuksi
                if ($isarivirow["suoraan_laskutukseen"] != "" and $mista_tullaan != "TULLAAN_VALMISTUKSESTA") {

                  //Tutkitaan lˆytyykˆ ostorivi ja sen toimitettuaika
                  $query = "SELECT tilausrivin_lisatiedot.suoratoimitettuaika
                            FROM tilausrivi
                            LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
                            WHERE tilausrivi.yhtio                          = '$kukarow[yhtio]'
                            and tilausrivi.tyyppi                           = 'O'
                            and tilausrivi.tunnus                           = '$isarivirow[tilausrivilinkki]'
                            and tilausrivin_lisatiedot.suoratoimitettuaika != '0000-00-00'";
                  $suoratoimresult = pupe_query($query);

                  if ($suoratoimrow = mysql_fetch_assoc($suoratoimresult)) {
                    $toimitettuaika = " '$suoratoimrow[suoratoimitettuaika]' ";
                  }
                  else {
                    $toimitettuaika = " now() ";
                  }

                  $toimquery = "UPDATE tilausrivi
                                SET keratty   = '$kukarow[kuka]',
                                kerattyaika    = $toimitettuaika,
                                toimitettu     = '$kukarow[kuka]',
                                toimitettuaika = $toimitettuaika,
                                kommentti      = trim(concat(kommentti, ' ".t("Suoratoimitus", $kieli)."'))
                                WHERE yhtio    = '$kukarow[yhtio]'
                                AND tunnus     = '$lisrow[tunnus]'";
                  $toimupdres = pupe_query($toimquery);
                }
              }

              //Jos jotakin kopioitavan rivin sarjanumeroista ei saatu kopioitua niin irrotetaan se silt‰ rivilt‰
              $query = "UPDATE sarjanumeroseuranta
                        SET myyntirivitunnus = 0
                        WHERE sarjanumeroseuranta.yhtio          = '$kukarow[yhtio]'
                        and sarjanumeroseuranta.tuoteno          = '$lisataanrow[tuoteno]'
                        and sarjanumeroseuranta.myyntirivitunnus = '$lisataanrow[tunnus]'";
              $cores = pupe_query($query);
            }
          }

          if (count($luodut_rivit) > 0) {
            // Katotaan mit‰ tehd‰‰n lopuille
            // Poistetaan loput
            if ($loput[$tunnukset] == 'POISTA') {
              foreach ($tunnusarray as $tunnus_nyt) {
                if ($toim == "ENNAKKO") {
                  $query = "SELECT tilausrivi.varattu jt
                            FROM tilausrivi
                            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                            and tilausrivi.tunnus  = '$tunnus_nyt'";
                }
                else {
                  $query = "SELECT tilausrivi.jt $lisavarattu jt
                            FROM tilausrivi
                            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                            and tilausrivi.tunnus  = '$tunnus_nyt'";
                }
                $rivitres = pupe_query($query);

                $poista_update_lisa = "";

                if (mysql_num_rows($rivitres) == 1) {
                  $row = mysql_fetch_assoc($rivitres);

                  if ($row["jt"] > $luodut_kpl[$tunnus_nyt]) {

                    $jaljella = $row["jt"] - $luodut_kpl[$tunnus_nyt];

                    if ($toim == "ENNAKKO" or $yhtiorow["varaako_jt_saldoa"] != "") {
                      $poista_update_lisa = ", varattu = '$jaljella', jt = '0' ";

                    }
                    else {
                      $poista_update_lisa = ", jt = '$jaljella', varattu = '0' ";
                    }
                  }
                  elseif ($row['jt'] == $luodut_kpl[$tunnus_nyt]) {
                    $poista_update_lisa = ", varattu = '0', jt = '0' ";
                  }
                }

                $uusi_kommentti = t("Toimitettu tilauksella", $kieli).": " . $kukarow['kesken'];

                $query = "UPDATE tilausrivi SET
                          tyyppi      = 'D',
                          kommentti   = IF(kommentti = '', '$uusi_kommentti', CONCAT(kommentti, ' / ', '$uusi_kommentti'))
                          $poista_update_lisa
                          WHERE yhtio = '$kukarow[yhtio]'
                          and tunnus  = '$tunnus_nyt'
                          and kpl     = 0";
                $tresult = pupe_query($query);

                if ($debug==1) echo t("Poistetaan loput rivilt‰").": $tunnus_nyt<br>";
              }
            }
            // J‰tet‰‰n loput
            else {
              foreach ($tunnusarray as $tunnus_nyt) {
                if ($toim == "ENNAKKO") {
                  $query = "SELECT tilausrivi.varattu jt
                            FROM tilausrivi
                            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                            and tilausrivi.tunnus  = '$tunnus_nyt'";
                }
                else {
                  $query = "SELECT tilausrivi.jt $lisavarattu jt
                            FROM tilausrivi
                            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                            and tilausrivi.tunnus  = '$tunnus_nyt'";
                }
                $rivitres = pupe_query($query);

                if (mysql_num_rows($rivitres) == 1) {
                  $row = mysql_fetch_assoc($rivitres);

                  if ($row["jt"] > $luodut_kpl[$tunnus_nyt]) {

                    $jaljella = $row["jt"] - $luodut_kpl[$tunnus_nyt];

                    if ($toim == "ENNAKKO" or $yhtiorow["varaako_jt_saldoa"] != "") {
                      $query = "UPDATE tilausrivi
                                SET varattu = '$jaljella'
                                WHERE yhtio = '$kukarow[yhtio]'
                                and tunnus  = '$tunnus_nyt'
                                and kpl     = 0";
                    }
                    else {
                      $query = "UPDATE tilausrivi
                                SET jt = '$jaljella'
                                WHERE yhtio = '$kukarow[yhtio]'
                                and tunnus  = '$tunnus_nyt'
                                and kpl     = 0";
                    }
                    $tresult = pupe_query($query);

                    if ($debug==1) echo t("J‰tet‰‰n loput rivilt‰").": $tunnus_nyt<br>";

                  }
                  else {
                    $query = "DELETE FROM tilausrivi
                              WHERE yhtio = '$kukarow[yhtio]'
                              and tunnus  = '$tunnus_nyt'
                              and kpl     = 0";
                    $tresult = pupe_query($query);

                    if ($debug==1) echo t("J‰tet‰‰n loput, mutta j‰tett‰v‰‰ ei ollut, joten dellataan rivi").": $tunnus_nyt<br>";
                  }
                }
              }
            }

            //Korjataan perheid:t uusilla riveill‰
            $query = "SELECT perheid, min(tunnus) uusiperheid
                      FROM tilausrivi
                      WHERE yhtio  = '$kukarow[yhtio]'
                      and otunnus  = '$id'
                      and perheid != 0
                      and tyyppi  != 'D'
                      GROUP by perheid";
            $copresult = pupe_query($query);

            while ($coprivirow = mysql_fetch_assoc($copresult)) {
              $query = "UPDATE tilausrivi
                        SET perheid = '$coprivirow[uusiperheid]'
                        WHERE yhtio = '$kukarow[yhtio]'
                        and otunnus = '$id'
                        and perheid = '$coprivirow[perheid]'";
              $cores = pupe_query($query);
            }

            //Korjataan perheid2:t uusilla riveill‰
            $query = "SELECT perheid2, min(tunnus) uusiperheid2
                      FROM tilausrivi
                      WHERE yhtio   = '$kukarow[yhtio]'
                      and otunnus   = '$id'
                      and perheid2 != 0
                      and tyyppi   != 'D'
                      GROUP by perheid2";
            $copresult = pupe_query($query);

            while ($coprivirow = mysql_fetch_assoc($copresult)) {
              $query = "UPDATE tilausrivi
                        SET perheid2 = '$coprivirow[uusiperheid2]'
                        WHERE yhtio  = '$kukarow[yhtio]'
                        and otunnus  = '$id'
                        and perheid2 = '$coprivirow[perheid2]'";
              $cores = pupe_query($query);
            }
          }
        }
        else {
          echo "<br>".t("Syˆtit liian ison numeron! Ei voitu tehd‰ mit‰‰n").".<br><br>";
        }
      }
    }
  }
}

if (!function_exists("hae_toimitettavat_tilausrivit")) {
  function hae_toimitettavat_tilausrivit($tilaus_tunnus, $varastot, $jtspec, $toim) {

    global $yhtiorow, $kukarow;

    // Jos tilauksen j‰lkitoimitusrivit ovat osatoimituskiellossa, saamme toimittaa joko tilauksen kaikki tuotteet tai ei mit‰‰n.
    // lasku.jtkielto = 'Z' "Asiakkaan j‰lkitoimituksia ei osatoimiteta"

    // Palautetaan true jos tilauksen kaikilla tuotteilla on saldoa
    // Palautetaan false jos kaikilla tuotteille *EI OLE* saldoa

    // Haetaan tilauksen kaikkien jt-rivien m‰‰r‰t (vain myynti- ja ennakkotilaus rivit)
    if ($toim == 'ENNAKKO') {
      $query = "SELECT tilausrivi.tuoteno,
                sum(tilausrivi.varattu + tilausrivi.jt) tuote_kpl
                FROM tilausrivi
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.otunnus = '{$tilaus_tunnus}'
                AND tilausrivi.tyyppi  = 'E'
                AND tilausrivi.varattu + tilausrivi.jt > 0
                GROUP BY tilausrivi.tuoteno";
      $result = pupe_query($query);
    }
    else {
      $query = "SELECT tilausrivi.tuoteno,
                sum(tilausrivi.varattu + tilausrivi.jt) tuote_kpl
                FROM tilausrivi
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.otunnus = '{$tilaus_tunnus}'
                AND tilausrivi.tyyppi  = 'L'
                AND tilausrivi.var     = 'J'
                AND tilausrivi.varattu + tilausrivi.jt > 0
                GROUP BY tilausrivi.tuoteno";
    }

    while ($tilausrivi_row = mysql_fetch_assoc($result)) {

      // Haetaan tuotteen myyt‰viss‰ m‰‰r‰
      list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tilausrivi_row['tuoteno'], $jtspec, $varastot, $kukarow['yhtio'], '', '', '', '', '', '', '');

      // Jos myyt‰viss‰ on v‰hemm‰n mit‰ halutaan toimittaa
      if ($myytavissa < $tilausrivi_row['tuote_kpl']) {
        return false;
      }
    }

    return true;
  }
}
