<?php

  //katsotaan että kukarow kesken ja $tilausnumero stemmaavat keskenään
  if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero!='' or $kukarow["kesken"] != 0)) {
    echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
    exit;
  }

  if (isset($jatka)) {


      $query = "INSERT into ";
      $postquery = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";

      $query2 = "INSERT into ";
      $postquery2 = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";

      $query3 = "INSERT into ";
      $postquery3 = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";



    if (strlen(trim($tnimi)) == 0) {
      $tnimi     = $nimi;
      $tnimitark = $nimitark;
      $tosoite   = $osoite;
      $tpostino  = $postino;
      $tpostitp  = $postitp;
      $toim_maa  = $maa;
    }

    if (strlen(trim($laskutus_nimi)) == 0) {
      $laskutus_nimi    = $nimi;
      $laskutus_nimitark  = $nimitark;
      $laskutus_osoite  = $osoite;
      $laskutus_postino   = $postino;
      $laskutus_postitp   = $postitp;
      $laskutus_maa     = $maa;
    }

    if ($tilausyhteyshenkilo == '' and $yhteyshenkilo_tilaus != '') {
      $tilausyhteyshenkilo = $yhteyshenkilo_tilaus;
    }





    // Käyttäjän toimipaikka ALV-velvollisuuden takaa tai alasvetovalikosta
    if (isset($yhtiotoimipaikka) and trim($yhtiotoimipaikka) != '') $kukarow['toimipaikka'] = $yhtiotoimipaikka;

    $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

    // yhtiön tiedot
    $yhtionimi      = $yhtiorow["nimi"];
    $yhtioosoite    = $yhtiorow["osoite"];
    $yhtiopostino   = $yhtiorow["postino"];
    $yhtiopostitp   = $yhtiorow["postitp"];
    $yhtiomaa       = $yhtiorow["maa"];
    $yhtioovttunnus   = $yhtiorow["ovttunnus"];
    $yhtiokotipaikka  = $yhtiorow["kotipaikka"];
    $yhtioalv_tilino  = $yhtiorow["alv"];
    $yhtiotoimipaikka = $kukarow['toimipaikka'];



    if (($voimaika_vv == "" or !is_numeric($voimaika_vv)) or ($voimaika_kk == "" or !is_numeric($voimaika_kk)) or ($voimaika_pp == "" or !is_numeric($voimaika_pp))) {
      $voimaika_vv = "0000";
      $voimaika_kk = "00";
      $voimaika_pp = "00";
    }


    $query .= " lasku_temp";


    $query .= " SET
          aktiivinen_kuljetus         = '',
          alatila               = '$alatila',
          alv                 = '$alv',
          alv_tili              = '$yhtioalv_tilino',
          asiakkaan_tilausnumero        = '$asiakkaan_tilausnumero',
          chn                 = '$chn',
          clearing              = '$clearing',
          comments              = '$comments',
          eilahetetta             = '$eilahe',
          lahetetyyppi            = '$lahetetyyppi',
          laskutyyppi             = '{$laskutyyppi}',
          erikoisale              = '$erikoisale',
          erpcm               = '$erpcmvv-$erpcmkk-$erpcmpp',
          huolitsija              = '$huolitsija',
          hyvaksynnanmuutos           = '$luokka',
          prioriteettinro           = '$prioriteettinro',
          jakelu                = '$jakelu',
          jaksotettu              = '$jaksotettu',
          jtkielto              = '$jtkielto',
          kassalipas              = '$kassalipas',
          kauppatapahtuman_luonne       = '$kauppatapahtuman_luonne',
          kerayspvm               = '$kervv-$kerkk-$kerpp',
          keraysvko               = '$keraysvko',
          ketjutus              = '$ketjutus',
          kohde               = '$kohde',
          kohdistettu             = '$maksaja',
          kolmikantakauppa          = '{$kolmikantakauppa}',
          kuljetus              = '$kuljetus',
          kuljetusmuoto           = '',
          label               = '{$label}',
          laskutusvkopv             = '$laskutusvkopv',
          liitostunnus            = '$liitostunnus',
          maa                 = '$maa',
          maksuehto               = '$maksuehto',
          maksuteksti             = '$maksuteksti',
          myyja                 = '$myyja',
          allekirjoittaja           = '$allekirjoittaja',
          nimi                = '$nimi',
          nimitark              = '$nimitark',
          ohjausmerkki            = '$ohjausmerkki',
          olmapvm               = '$voimaika_vv-$voimaika_kk-$voimaika_pp',
          osatoimitus             = '$osatoimitus',
          osoite                = '$osoite',
          ostotilauksen_kasittely       = '{$ostotilauksen_kasittely}',
          ovttunnus               = '$ovttunnus',
          piiri                 = '$piiri',
          postino               = '$postino',
          postitp               = '$postitp',
          rahtivapaa              = '$rahtivapaa',
          sisainen              = '$sisainen',
          sisviesti1              = '$sisviesti1',
          sisviesti2              = '$sisviesti2',
          sisviesti3              = '$sisviesti3',
          splittauskielto           = '$splittauskielto',
          tilaustyyppi            = '$tilaustyyppi',
          tilausvahvistus           = '$tilausvahvistus',
          tilausyhteyshenkilo         = '$tilausyhteyshenkilo',
          toimaika              = '2011-01-01',
          toimitusehto            = '$toimitusehto',
          toimitustapa            = '$toimitustapa',
          toimvko               = '$toimvko',
          toim_maa              = '$toim_maa',
          toim_nimi               = '$tnimi',
          toim_nimitark             = '$tnimitark',
          toim_osoite             = '$tosoite',
          toim_ovttunnus            = '$toim_ovttunnus',
          toim_postino            = '$tpostino',
          toim_postitp            = '$tpostitp',
          tunnusnippu             = '$tunnusnippu',
          valkoodi              = '$valkoodi',
          varasto               = '$varasto',
          verkkotunnus            = '$verkkotunnus',
          vienti                = '$vienti',
          vienti_kurssi           = '$kurssi',
          viesti                = '$viesti',
          viikorkopros            = '$yhtiorow[viivastyskorko]',
          yhtio                 = '$kukarow[yhtio]',
          yhtio_kotipaikka          = '$yhtiokotipaikka',
          yhtio_maa             = '$yhtiomaa',
          yhtio_nimi              = '$yhtionimi',
          yhtio_osoite            = '$yhtioosoite',
          yhtio_ovttunnus           = '$yhtioovttunnus',
          yhtio_postino           = '$yhtiopostino',
          yhtio_postitp           = '$yhtiopostitp',
          yhtio_toimipaikka         = '$yhtiotoimipaikka',
          ytunnus               = '$ytunnus'";

    if ($toim == "MYYNTITILI") {
      if ($ylatila=='') {
        $ylatila='G';
      }
      $tee = "";
    }
    elseif ($toim == "VALMISTAASIAKKAALLE") {
      if ($ylatila=='') {
        $ylatila='V';
      }
      $tee = "";
    }
    elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
      if ($ylatila=='') {
        $ylatila='A';
      }
      $tee = "";
    }
    elseif ($toim == "REKLAMAATIO") {
      if ($ylatila=='') {
        $ylatila='C';
      }
      $tee = "";
    }
    elseif ($toim == "EXTRANET_REKLAMAATIO") {
      if ($ylatila=='') {
        $ylatila='C';
      }
      $tee = "";
    }
    elseif ($toim == "EXTTARJOUS") {
      if ($ylatila=='') {
        $ylatila='T';
      }
      $tee = "";
    }
    elseif ($toim == "TARJOUS") {
      if ($ylatila=='') {
        $ylatila='T';
      }
      $tee = "";
    }
    elseif ($toim == "PROJEKTI") {
      if ($ylatila=='') {
        $ylatila='R';
      }
      $tee = "";
    }
    elseif ($toim == "WEBTILAUSTARJOUS") {
      if ($ylatila=='') {
        $ylatila='F';
      }
      $tee = "";
    }
    else {
      if ($ylatila=='') {
        $ylatila='N';
      }
      $tee = "";
    }

    if (($toim == "PROJEKTI" or $yhtiorow["splittauskielto"] == "K") and $kukarow["kesken"] > 0) {
      $abuq = " SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero'";
      $abures = pupe_query($abuq);
      $originaali["lasku"] = mysql_fetch_assoc($abures);

      $abuq = " SELECT *
            FROM laskun_lisatiedot
            WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$tilausnumero'";
      $abures = pupe_query($abuq);
      $originaali["laskun_lisatiedot"] = mysql_fetch_assoc($abures);
    }

    $query .= ", tila = '$ylatila'";
    $query .= $postquery;

    $result = pupe_query($query);
    $id = mysql_insert_id();


    // Setataan laskun aluperäiset tilat
    $orig_tila    = $ylatila;
    $orig_alatila = $alatila;



    if ((int) $kukarow["kesken"] == 0 and $id != 0 and $id !== FALSE) {

      $query = "  UPDATE kuka
            SET kesken = '$id'
            WHERE yhtio = '$kukarow[yhtio]' AND
            kuka = '$kukarow[kuka]' AND
            session = '$session' AND
            session != ''";
      $result = pupe_query($query);

      $kukarow["kesken"] = $id;
      $tilausnumero = $id;

      //  jos tehtiin tarjouksesta uusi versio kopioidaan vanhat rivit uudeksi pohjaksi..
      //  Koska meillä on jo tässävaiheessa tunnusnippu, on tämän oltava uusi versio, eikä uusi tarjous

      if ( ($toim == "TARJOUS" or $toim == "EXTTARJOUS") and $yhtiorow["tarjouksen_voi_versioida"] != "") {







        // Haetaan tämän nipun viimeisin tarjous
        $query = "  SELECT tunnus from lasku_temp where yhtio='$kukarow[yhtio]' and tunnusnippu='$tunnusnippu' and tunnus<'$kukarow[kesken]' and tila = 'T' order by tunnus desc LIMIT 1";
        $abures = pupe_query($query);
        $aburow = mysql_fetch_assoc($abures);

        //  Haetaan rivit ja kopioidaan ne uuteen tarjoukseen.. as they are..
        $query = "SELECT * from tilausrivi_temp WHERE yhtio = '$kukarow[yhtio]' and otunnus='$aburow[tunnus]'";
        $monistares = pupe_query($query);

        $korjaaPerheet=array();

        if (mysql_num_rows($monistares)>0) {
          $fields = mysql_field_name($monistares,0);

          for ($i=1; $i < mysql_num_fields($monistares)-1; $i++) { // Ei monisteta tunnusta
            $fields .= ", ".mysql_field_name($monistares,$i);
          }

          while ($monistarow = mysql_fetch_assoc($monistares)) {

            $values = "";

            if ($values == "") {
              $values = "('".$monistarow["yhtio"]."'";
            }
            else {
              $values .= "), ('".$monistarow["yhtio"]."'";
            }

            for ($i=1; $i < mysql_num_fields($monistares)-1; $i++) { // Ei monisteta tunnusta

              $fieldname = mysql_field_name($monistares,$i);

              switch (mysql_field_name($monistares,$i)) {
                case "otunnus";
                  $values .= ", '$tilausnumero'";
                  break;
                case "laatija";
                  $values .= ", '$kukarow[kuka]'";
                  break;
                case "luontiaika";
                case "laadittu";
                  $values .= ", now()";
                  break;
                case "kerayspvm";
                  $values .= ", '$kervv-$kerkk-$kerpp'";
                  break;
                case "toimaika";
                  $values .= ", '$toimvv-$toimkk-$toimpp'";
                  break;
                case "kate_korjattu";
                  $values .= ", NULL";
                  break;
                default;
                  $values .= ", '".$monistarow[$fieldname]."'";
                  break;
              }
            }

            $values .= ")";




            //  Ja insertoidaan rivit kantaan
            $query = "  INSERT INTO tilausrivi_temp ($fields) VALUES $values";
            $insres=pupe_query($query);
            $lisattyRivitunnus = mysql_insert_id();

            //  Otetaan korjattava perheid talteen
            if ($monistarow["perheid"] > 0 and $monistarow["perheid"] == $monistarow["tunnus"]) {
              $korjaaPerheet[$monistarow["perheid"]] = $lisattyRivitunnus;
            }

            //  Oliko meillä lisatietoja?
            $query = "SELECT * from tilausrivin_lisatiedot_temp WHERE yhtio = '$kukarow[yhtio]' and tilausrivitunnus='$monistarow[tunnus]'";
            $monistares2 = pupe_query($query);

            if (mysql_num_rows($monistares2)>0) {

              $fields2 = mysql_field_name($monistares2,0);

              for ($i=1; $i < mysql_num_fields($monistares2)-1; $i++) { // Ei monisteta tunnusta
                $fields2 .= ", ".mysql_field_name($monistares2,$i);
              }

              $values2 ="";

              while ($monistarow2 = mysql_fetch_assoc($monistares2)) {

                if ($values2 == "") {
                  $values2 = "('".$monistarow2["yhtio"]."'";
                }
                else {
                  $values2 .= "), ('".$monistarow2["yhtio"]."'";
                }

                for ($i=1; $i < mysql_num_fields($monistares2)-1; $i++) { // Ei monisteta tunnusta

                  $fieldname = mysql_field_name($monistares2,$i);

                  switch (mysql_field_name($monistares2,$i)) {
                    case "laatija";
                      $values2 .= ", '$kukarow2[kuka]'";
                      break;
                    case "luontiaika";
                      $values2 .= ", now()";
                      break;
                    case "tilausrivitunnus";
                      $values2 .= ", '$lisattyRivitunnus'";
                      break;
                    default;
                      $values2 .= ", '".$monistarow2[$fieldname]."'";
                      break;
                  }
                }
              }

              $values2 .= ")";

              //  Ja insertoidaan rivit kantaan
              $query = "  INSERT INTO tilausrivin_lisatiedot_temp ($fields2) VALUES $values2";
              $insres2=pupe_query($query);

            }
          }
        }
      }
    }







    // varmistetaan, että meillä on kukarow
    if ((int) $kukarow["kesken"] != 0) {

        $query = "  UPDATE lasku_temp
              SET tunnusnippu = '$kukarow[kesken]'
              WHERE tunnus    = '$kukarow[kesken]'
              and tunnusnippu = 0";
        $result = pupe_query($query);


      if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO" or ($toim == "TARJOUS" or $toim == "EXTTARJOUS") or $tilaustyyppi == "T" or $tilaustyyppi == "A") {
        //otetaan kantaa pyörän malliin
        if ($merkki2 != 'eimenu' and $merkki2 != '') {
          $merkki = $merkki2;
        }

        $query2 .= "tyomaarays_temp SET
              yhtio     = '$kukarow[yhtio]',
              kotipuh     = '$kotipuh',
              tyopuh      = '$tyopuh',
              myyjaliike    = '$myyjaliike',
              ostopvm     = '$ostopvmvv-$ostopvmkk-$ostopvmpp',
              rekno       = '$rekno',
              mittarilukema   = '$mittarilukema',
              merkki      = '$merkki',
              mallivari     = '$mallivari',
              valmnro     = '$valmnro',
              tuotu       = '$tuotuvv-$tuotukk-$tuotupp',
              luvattu     = '$luvattuvv-$luvattukk-$luvattupp',
              komm1       = '$komm1',
              komm2       = '$komm2',
              suorittaja    = '$suorittaja',
              viite     = '$viite',
              prioriteetti  = '$prioriteetti',
              jalleenmyyja  = '$jalleenmyyja',
              tehdas      = '$tehdas',
              tyoaika     = '$tyoaika',
              vikakoodi   = '$vikakoodi',
              takuunumero   = '$takuunumero',
              tyokoodi    = '$tyokoodi',
              hyvaksy     = '$hyvaksy',
              otunnus     = '$kukarow[kesken]'";

        //Nämä kentät päivitetään vain, jos päivitetään sitä työmääräykseltä tai muualta missä nuo kaksi kenttää näkyy..
        if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or ((($toim == 'TARJOUS' or $toim == 'EXTTARJOUS') or $srow["tilaustyyppi"] == "T") and $yhtiorow["tyomaaraystiedot_tarjouksella"] == "")) {
          $query2 .= ", tyojono     = '$tyojono',
                  tyostatus   = '$tyostatus'";
        }
        $query2 .= $postquery2;
        $result = pupe_query($query2);
      }

      if (in_array($toim, array("PROJEKTI", "RIVISYOTTO", "PIKATILAUS", "TYOMAARAYS", "TYOMAARAYS_ASENTAJA", "REKLAMAATIO", "TARJOUS", "EXTTARJOUS", "ENNAKKO","EXTENNAKKO", "EXTRANET", "YLLAPITO", "MYYNTITILI", "VALMISTAASIAKKAALLE", "WEBTILAUSTARJOUS","EXTRANET_REKLAMAATIO"))) {

        if (is_array($yllapito_kk)) {
          $sopimus_kk = implode(",", $yllapito_kk);
        }
        else {
          $sopimus_kk = "";
        }

        if (is_array($yllapito_pp)) {
          $sopimus_pp = implode(",", $yllapito_pp);
        }
        else {
          $sopimus_pp = "";
        }

        $sopimus_info = '';
        if($toim != 'EXTENNAKKO' and $toim != 'EXTTARJOUS') {
          $sopimus_info = "sopimus_lisatietoja      = '$sopimus_lisatietoja',";
        }

        $query3 .= "laskun_lisatiedot_temp SET
              yhtio           = '$kukarow[yhtio]',
              sopimus_kk          = '$sopimus_kk',
              sopimus_pp          = '$sopimus_pp',
              sopimus_alkupvm       = '$yllapito_alkuvv-$yllapito_alkukk-$yllapito_alkupp',
              sopimus_loppupvm      = '$yllapito_loppuvv-$yllapito_loppukk-$yllapito_loppupp',
              {$sopimus_info}
              kolm_ovttunnus        = '$kolm_ovttunnus',
              kolm_nimi         = '$kolm_nimi',
              kolm_nimitark       = '$kolm_nimitark',
              kolm_osoite         = '$kolm_osoite',
              kolm_postino        = '$kolm_postino',
              kolm_postitp        = '$kolm_postitp',
              kolm_maa          = '$kolm_maa',
              laskutus_nimi       = '$laskutus_nimi',
              laskutus_nimitark     = '$laskutus_nimitark',
              laskutus_osoite       = '$laskutus_osoite',
              laskutus_postino      = '$laskutus_postino',
              laskutus_postitp      = '$laskutus_postitp',
              laskutus_maa        = '$laskutus_maa',
              toimitusehto2       = '$toimitusehto2',
              projektipaallikko     = '$projektipaallikko',
              yhteyshenkilo_kaupallinen = '$yhteyshenkilo_kaupallinen',
              yhteyshenkilo_tekninen    = '$yhteyshenkilo_tekninen',
              seuranta          = '$seuranta',
              projekti          = '$projekti',
              saate           = '$saate',
              tunnusnippu_tarjous     = '$tunnusnippu_tarjous',
              rivihintoja_ei_nayteta    = '$rivihintoja_ei_nayteta',
              kasinsyotetty_viite     = '$kasinsyotetty_viite',
              kantaasiakastunnus      = '$kantaasiakastunnus',
              asiakkaan_kohde       = '{$tkohde}',
              otunnus           = '$kukarow[kesken]'";
        $query3 .= $postquery3;
        $result = pupe_query($query3);
      }
    }
  }

  // Palutetaan puhtaat kukarow ja yhtiörow
  $kukarow  = hae_kukarow($kukarow['kuka'], $kukarow['yhtio']);
  $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

  unset($alv);
