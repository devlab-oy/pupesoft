<?php

// jos halutaan generoida ostotilaus tämän tilauksen riveistä
// tarvitaan myyntitilauksen tunnus muuttujassa $otunnus

if (!function_exists("tilauksesta_ostotilaus")) {
  function tilauksesta_ostotilaus($otunnus, $tyyppi) {
    global $yhtiorow, $kukarow, $toim, $pupe_root_polku, $komento;

    $tilauksesta_ostotilaus = "";
    $lisa_varattusiirto = "";

    if ($tyyppi == "T") {
      $vars = " and tilausrivi.var  = 'T' ";
    }
    elseif ($tyyppi == "U") {
      $vars = " and tilausrivi.var  = 'U' ";
    }
    elseif ($tyyppi == "KAIKKI") {
      $vars = " and tilausrivi.var != 'P' ";
    }
    else {
      echo t("VIRHE: Funktiota kutsuttiin väärällä parametrilla")."!<br><br>";
      return false;
    }

    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$otunnus'";
    $result = pupe_query($query);
    $myytilrow = mysql_fetch_assoc($result);

    //otetaan ensin vain lisävarusteettomat tuotteet tai lisävarusteperheiden isät
    $query = "SELECT tilausrivin_lisatiedot.*, tilausrivi.*, tilausrivi.tunnus rivitunnus, tuote.ei_saldoa, tuote.sarjanumeroseuranta
              FROM tilausrivi
              LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
              JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
              WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
              and tilausrivi.otunnus  = '$otunnus'
              and tilausrivi.tyyppi  != 'D'
              $vars
              and (tilausrivi.perheid2 = tilausrivi.tunnus or tilausrivi.perheid2 = 0)
              ORDER BY tilausrivi.perheid2, tilausrivi.perheid, tilausrivi.tunnus";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      $tunnukset = array();

      while ($rivi = mysql_fetch_assoc($result)) {

        $ostotilauksen_kasittely = $myytilrow['ostotilauksen_kasittely'];

        // Tuoteperheiden ohita_kerays-lapset ohitetaan tässä, koska ne haetaan automaattisesti $tilrivires-kyslyssä
        if ($rivi['perheid'] > 0 and $rivi['perheid'] != $rivi['rivitunnus'] and $rivi['ohita_kerays'] != "") {
          continue;
        }

        //  Jos tehdään vaan tämä tilaus niin haetaan tämän oletustoimittaja, by default
        if ($tyyppi == "KAIKKI" and $rivi["toimittajan_tunnus"] == 0) {
          $query = "SELECT liitostunnus, if (jarjestys = 0, 9999, jarjestys) sorttaus
                    FROM tuotteen_toimittajat
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tuoteno = '$rivi[tuoteno]'
                    ORDER BY sorttaus
                    LIMIT 1";
          $abures = pupe_query($query);

          if (mysql_num_rows($abures) == 1) {
            $aburow = mysql_fetch_assoc($abures);

            $query = "UPDATE tilausrivin_lisatiedot
                      SET
                      toimittajan_tunnus   = '$aburow[liitostunnus]',
                      muutospvm            = now(),
                      muuttaja             = '$kukarow[kuka]'
                      WHERE yhtio          = '$kukarow[yhtio]'
                      and tilausrivitunnus = '$rivi[rivitunnus]'";
            pupe_query($query);

            $rivi["toimittajan_tunnus"] = $aburow["liitostunnus"];
          }
          elseif ($rivi['ei_saldoa'] != '') {
            // jos ollaan erikseen laitettu saldottomalle tuotteelle toimittaja niin ehkä sitä halutaan myös ostaa,
            // skipataan siis vaan ne saldottomat tuotteet joille ei ole määritelty toimittajaa
            if ($rivi['perheid'] == $rivi['rivitunnus']) {
              // saldottomat tuotteet laitetaan jälkkäriin jos ne on tuoteperheen isiä ja toimittajaa ei löydy
              $query = "UPDATE tilausrivi
                        SET var = 'J'
                        WHERE yhtio = '$kukarow[yhtio]'
                        AND tunnus  = '$rivi[rivitunnus]'";
              $ei_saldoa_res = pupe_query($query);
            }

            continue;
          }
          else {
            $tilauksesta_ostotilaus .= "<font class='error'>".t("VIRHE: Toimittajaa ei löydy riville").": '$rivi[tunnus]'<br>";
          }
        }

        // tehdään aluksi vähän oikeellisuustarkastuksia
        $query = "SELECT * from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and tuoteno='$rivi[tuoteno]' and liitostunnus='$rivi[toimittajan_tunnus]'";
        $erres = pupe_query($query);
        $ttrow = mysql_fetch_assoc($erres);

        // tehdään aluksi vähän oikeellisuustarkastuksia
        $query = "SELECT * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$ttrow[liitostunnus]'";
        $erres = pupe_query($query);

        if (mysql_num_rows($erres) != 1) {
          $tilauksesta_ostotilaus .= "<font class='error'>".t("VIRHE: Toimittajan tietoja ei löytynyt tuotteelta").": $rivi[tuoteno]!<br>";
        }
        else {
          // toimittaja löytyi, tehdään tästä ostotilaus
          $tiltoi = mysql_fetch_assoc($erres);

          $toim_ostilkas = $tiltoi['ostotilauksen_kasittely'];
          $toim_ostilkas_chk = (is_numeric($toim_ostilkas) and $toim_ostilkas == 4);
          $toim_ostilkas_chk_tyhja = (trim($toim_ostilkas) == '');

          $ostilkas_chk = (is_numeric($ostotilauksen_kasittely) and $ostotilauksen_kasittely == 4);
          $myynti_ostilkas_chk = ($toim_ostilkas_chk_tyhja and $ostilkas_chk);

          $kumpi_ostotilkas = ($toim_ostilkas_chk or $myynti_ostilkas_chk);

          if ($kumpi_ostotilkas) {
            $ohjausmerkki = '';
          }
          else {
            $ohjausmerkki = $myytilrow['ohjausmerkki'] ;
          }

          // Katsotaan onko toimittajalla avoimia ostotilauksia
          // Pitää olla myös oikeat osoitetiedot
          if ($tyyppi == "U" or ($tyyppi == 'KAIKKI' and $yhtiorow["tee_osto_myyntitilaukselta"] == 'Q')) {
            $toim_nimi     = $myytilrow["toim_nimi"];
            $toim_nimitark = $myytilrow["toim_nimitark"];
            $toim_osoite   = $myytilrow["toim_osoite"];
            $toim_postino  = $myytilrow["toim_postino"];
            $toim_postitp  = $myytilrow["toim_postitp"];
            $toim_maa      = $myytilrow["toim_maa"];
          }
          else {
            $toim_nimi     = $yhtiorow["nimi"];
            $toim_nimitark = $yhtiorow["nimitark"];
            $toim_osoite   = $yhtiorow["osoite"];
            $toim_postino  = $yhtiorow["postino"];
            $toim_postitp  = $yhtiorow["postitp"];
            $toim_maa      = $yhtiorow["maa"];
          }

          $valkoodi = $tiltoi["oletus_valkoodi"];

          if ($valkoodi == '') {
            $valkoodi = $yhtiorow["valkoodi"];
          }
          //jos toimittajalla joku muu kuin oletus ostotilauksen_kasittely niin käytetään sitä
          if ($tiltoi['ostotilauksen_kasittely'] != '') {
            $ostotilauksen_kasittely = $tiltoi['ostotilauksen_kasittely'];
          }
          // Pikatilaus menee aina omalle otsikolle per myyntitilaus
          $tunnusnippu = ($ostotilauksen_kasittely == 5) ? $myytilrow['tunnus'] : 0;

          $vquery = "SELECT nimi, kurssi, tunnus
                     FROM valuu
                     WHERE yhtio = '$kukarow[yhtio]'
                     and nimi    = '$valkoodi'";
          $vresult = pupe_query($vquery);
          $vrow = mysql_fetch_array($vresult);

          $commentsit = trim(t("Myyntitilaus").": $myytilrow[tunnus] \n".t("Myyntitilauksen toimitustapa").": $myytilrow[toimitustapa] \n".$tiltoi["comments"]);

          $ostotil_tiltyyp_res = t_avainsana("OSTOTIL_TILTYYP");

          if (mysql_num_rows($ostotil_tiltyyp_res) > 0) {
            // Otetaan eka...
            $ostotil_tiltyyp_row = mysql_fetch_assoc($ostotil_tiltyyp_res);
            $tilaustyyppi = $ostotil_tiltyyp_row['selite'];
          }
          else {
            // oletuksena normaali
            $tilaustyyppi = 2;
          }

          if ($tyyppi == "U" and $yhtiorow['suoratoimitusvarasto'] != 0) {
            $oletusostovarasto = $yhtiorow['suoratoimitusvarasto'];
          }
          elseif ($myytilrow['varasto'] != 0) {
            $oletusostovarasto = $myytilrow['varasto'];
          }
          elseif ($kukarow['oletus_ostovarasto'] != 0) {
            $oletusostovarasto = $kukarow['oletus_ostovarasto'];
          }
          else {
            $oletusostovarasto = 0;
          }

          $ostotilauksen_kasittely_wherelisa = "";

          // Käsitellään myyntitilauksen otsikolla setattu "ostotilauksen käsittely"
          switch ($ostotilauksen_kasittely) {
          case '5':
            // Pikaostotilaus aina omalle otsikolle
            $ostotilauksen_kasittely_wherelisa .= "
              and tunnusnippu = '{$tunnusnippu}'";
            //Ei breikkiä. Mennään myös 4-haaraan.
          case '4':
            $ostotilauksen_kasittely_wherelisa .= "
              and toim_nimi    = '{$toim_nimi}'
              and toim_nimitark  = '{$toim_nimitark}'
              and toim_osoite    = '{$toim_osoite}'
              and toim_postino  = '{$toim_postino}'
              and toim_postitp  = '{$toim_postitp}'
              and toim_maa    = '{$toim_maa}'
              and varasto     = '{$oletusostovarasto}'
              and ohjausmerkki   = '{$ohjausmerkki}'
              and tilaustyyppi  = '$tilaustyyppi'
              and vanhatunnus   = '{$myytilrow['yhtio_toimipaikka']}'";
            break;
          case '2':
          case '3':
            $ostotilauksen_kasittely_wherelisa .= "
              and toim_nimi    = '{$toim_nimi}'
              and toim_nimitark  = '{$toim_nimitark}'
              and toim_osoite    = '{$toim_osoite}'
              and toim_postino  = '{$toim_postino}'
              and toim_postitp  = '{$toim_postitp}'
              and toim_maa    = '{$toim_maa}'
              and tilaustyyppi  = '$tilaustyyppi'";
            break;
          case '1':
          case '0':
          default:
            $ostotilauksen_kasittely_wherelisa .= "
              and toim_nimi    = '{$toim_nimi}'
              and toim_nimitark  = '{$toim_nimitark}'
              and toim_osoite    = '{$toim_osoite}'
              and toim_postino  = '{$toim_postino}'
              and toim_postitp  = '{$toim_postitp}'
              and toim_maa    = '{$toim_maa}'
              and left(luontiaika,10) = left(now(),10)
              and laatija     = '{$kukarow['kuka']}'
              and tilaustyyppi  = '$tilaustyyppi'";
            break;
          }

          $query = "SELECT *
                    FROM lasku
                    WHERE yhtio       = '$kukarow[yhtio]'
                    AND liitostunnus  = '$tiltoi[tunnus]'
                    AND tila          = 'O'
                    AND chn          != 'GEN'
                    AND alatila       = ''
                    {$ostotilauksen_kasittely_wherelisa}";
          $jtsre = pupe_query($query);

          // ei löydy, tehdään uus otsikko
          if (mysql_num_rows($jtsre) == 0) {

            $suoratoimitus = "";

            // Onko suoratoimitus
            if ($tyyppi == "U" or ($tyyppi == 'KAIKKI' and $yhtiorow["tee_osto_myyntitilaukselta"] == 'Q')) {
              $suoratoimitus = " , suoraveloitus = 'S' ";
            }

            // Vanhatunnus-kenttään laitetaan ostotilauksella toimipaikan tunnus
            $query = "INSERT INTO lasku SET
                      alatila             = '',
                      comments            = '$commentsit',
                      huolitsija          = '$tiltoi[huolitsija]',
                      jakelu              = '$jakelu',
                      kerayspvm           = '$myytilrow[kerayspvm]',
                      kuljetus            = '$tiltoi[kuljetus]',
                      laatija             = '$kukarow[kuka]',
                      liitostunnus        = '$tiltoi[tunnus]',
                      luontiaika          = now(),
                      maa                 = '$tiltoi[maa]',
                      maksuteksti         = '$tiltoi[maksuteksti]',
                      myyja               = '$myytilrow[myyja]',
                      nimi                = '$tiltoi[nimi]',
                      nimitark            = '$tiltoi[nimitark]',
                      ohjausmerkki        = '$ohjausmerkki',
                      osoite              = '$tiltoi[osoite]',
                      ovttunnus           = '$tiltoi[ovttunnus]',
                      postino             = '$tiltoi[postino]',
                      postitp             = '$tiltoi[postitp]',
                      sisviesti1          = '$myytilrow[sisviesti1]',
                      tila                = 'O',
                      tilaustyyppi        = '$tilaustyyppi',
                      tilausyhteyshenkilo = '$kukarow[nimi]',
                      toimaika            = '$myytilrow[toimaika]',
                      toimitusehto        = '$tiltoi[toimitusehto]',
                      toimitustapa        = '$myytilrow[toimitustapa]',
                      toim_maa            = '$toim_maa',
                      toim_nimi           = '$toim_nimi',
                      toim_nimitark       = '$toim_nimitark',
                      toim_osoite         = '$toim_osoite',
                      toim_ovttunnus      = '$tiltoi[ovttunnus]',
                      toim_postino        = '$toim_postino',
                      toim_postitp        = '$toim_postitp',
                      tunnusnippu         = '{$tunnusnippu}',
                      valkoodi            = '$valkoodi',
                      vanhatunnus         = '{$myytilrow['yhtio_toimipaikka']}',
                      varasto             = '{$oletusostovarasto}',
                      verkkotunnus        = '$myytilrow[liitostunnus]',
                      vienti_kurssi       = '$vrow[kurssi]',
                      viesti              = '$myytilrow[viesti]',
                      viikorkopros        = '$yhtiorow[viivastyskorko]',
                      yhtio               = '$kukarow[yhtio]',
                      ytunnus             = '$tiltoi[ytunnus]'
                      {$suoratoimitus}";
            pupe_query($query);
            $tunnus = (string) mysql_insert_id($GLOBALS["masterlink"]);

            // hintojen ja alennuksien hakuun tarvitaan laskurow, tehdään tässä array kuntoon alehinta_osto() funktiota varten
            $jtsro = array(
              'liitostunnus'   => $tiltoi['tunnus'],
              'valkoodi'     => $valkoodi,
              'ytunnus'     => $tiltoi['ytunnus'],
              'vienti_kurssi' => $vrow['kurssi']
            );
          }
          else {
            // tilaus löyty, otetaan tunnus
            $jtsro = mysql_fetch_assoc($jtsre);
            $tunnus = $jtsro["tunnus"];
          }

          if (in_array($ostotilauksen_kasittely, array('1', '3', '5'))) $tunnukset[$tunnus] = $tunnus;

          // Lisätään joukkoon myös lisävarusteperheiden lapset
          // sekä ohita_kerays-tyyppiset tuoteperheiden lapset, jotka ovat jääneet eri var-tilaan kuin isät
          $query = "(SELECT
                     tilausrivi.*,
                     tilausrivin_lisatiedot.ohita_kerays,
                     tilausrivi.tunnus rivitunnus,
                     tilausrivi.perheid2 p2,
                     tilausrivi.perheid p1,
                     jt+varattu kpl,
                     tilausrivin_lisatiedot.tilausrivilinkki tilausrivilinkki
                     FROM tilausrivi
                     LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
                     where tilausrivi.yhtio  = '$kukarow[yhtio]'
                     and tilausrivi.otunnus  = '$otunnus'
                     and tilausrivi.tyyppi  != 'D'
                     and (tilausrivi.tunnus = '$rivi[tunnus]' or tilausrivi.perheid2 = '$rivi[tunnus]'))

                     UNION

                     (SELECT
                     tilausrivi.*,
                     tilausrivin_lisatiedot.ohita_kerays,
                     tilausrivi.tunnus rivitunnus,
                     tilausrivi.perheid2 p2,
                     tilausrivi.perheid p1,
                     tilkpl kpl,
                     tilausrivin_lisatiedot.tilausrivilinkki tilausrivilinkki
                     FROM tilausrivi
                     JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus and tilausrivin_lisatiedot.ohita_kerays != '')
                     WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                     and tilausrivi.otunnus  = '$otunnus'
                     and tilausrivi.tyyppi  != 'D'
                     and tilausrivi.tunnus  != '$rivi[tunnus]'
                     and tilausrivi.perheid  = '$rivi[tunnus]')
                     ORDER BY p2, p1, rivitunnus";
          $tilrivires = pupe_query($query);

          $pid1 = 0;
          $pid2 = 0;

          while ($tilrivirow = mysql_fetch_assoc($tilrivires)) {

            if ($oletusostovarasto != 0) {
              $oletuspaikka_lisa = "AND tuotepaikat.varasto = '{$oletusostovarasto}'";
            }
            else {
              $oletuspaikka_lisa = "AND tuotepaikat.oletus != ''";
            }

            // haetaan oletuspaikan tiedot niin laitetaan se riville
            $query = "SELECT tuotepaikat.*
                      FROM tuotepaikat
                      WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
                      AND tuotepaikat.tuoteno = '{$tilrivirow['tuoteno']}'
                      {$oletuspaikka_lisa}
                      LIMIT 1";
            $jtsre = pupe_query($query);
            $jtstu = mysql_fetch_assoc($jtsre);

            // varattu määrää saa päivittää vain jos kpl = 0 ja uusiotunnus = 0, tila = O, alatila =''
            $query =  "SELECT tilausrivi.*
                       FROM tilausrivi
                       JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'O' and lasku.alatila = '')
                       LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
                       WHERE tilausrivi.yhtio     = '{$kukarow["yhtio"]}'
                       AND tilausrivi.otunnus     = '$tunnus'
                       AND tilausrivi.tunnus      = '{$tilrivirow["tilausrivilinkki"]}'
                       AND tilausrivi.kpl         = 0
                       AND tilausrivi.uusiotunnus = ''";
            $upsres = pupe_query($query);

            if (mysql_num_rows($upsres) == 1) {
              $loytyi = mysql_fetch_assoc($upsres);

              $query =  "UPDATE tilausrivi
                         SET varattu = '{$tilrivirow["kpl"]}',
                         tilkpl      = '{$tilrivirow["kpl"]}'
                         WHERE yhtio = '{$kukarow["yhtio"]}'
                         AND tunnus  = '{$loytyi["tunnus"]}'";
              $upsaresult = pupe_query($query);

              $lisatty_tun = $loytyi["tunnus"];
            }
            else {

              // hintojen ja alennuksien hakuun tarvitaan tuoterow, tehdään tässä array kuntoon alehinta_osto() funktiota varten
              $tuote_row = array(
                'tuoteno'   => $tilrivirow['tuoteno']
              );

              list($hinta, $netto, $ale, ) = alehinta_osto($jtsro, $tuote_row, $tilrivirow['kpl'], '', '', '');

              // lisätään ostotilausrivi
              $query = "INSERT into tilausrivi set
                        hyllyalue       = '$jtstu[hyllyalue]',
                        hyllynro        = '$jtstu[hyllynro]',
                        hyllyvali       = '$jtstu[hyllyvali]',
                        hyllytaso       = '$jtstu[hyllytaso]',
                        tilaajanrivinro = '',
                        laatija         = '$kukarow[kuka]',
                        laadittu        = now(),
                        yhtio           = '$kukarow[yhtio]',
                        tuoteno         = '$tilrivirow[tuoteno]',
                        varattu         = '$tilrivirow[kpl]',
                        yksikko         = '$tilrivirow[yksikko]',
                        kpl             = 0,
                        kpl2            = 0,
                        tilkpl          = '$tilrivirow[kpl]',
                        jt              = 0,
                        hinta           = '$hinta',
                        netto           = '$netto',
                        ale1            = '$ale[ale1]',
                        ale2            = '$ale[ale2]',
                        ale3            = '$ale[ale3]',
                        alv             = '',
                        kerayspvm       = '$tilrivirow[kerayspvm]',
                        otunnus         = '$tunnus',
                        tyyppi          = 'O',
                        toimaika        = '$tilrivirow[toimaika]',
                        kommentti       = '$tilrivirow[kommentti]',
                        var             = '',
                        try             = '$tilrivirow[try]',
                        osasto          = '$tilrivirow[osasto]',
                        perheid         = '{$pid1}',
                        perheid2        = '{$pid2}',
                        nimitys         = '$tilrivirow[nimitys]',
                        jaksotettu      = ''";
              pupe_query($query);
              $lisatty_tun = mysql_insert_id($GLOBALS["masterlink"]);

              // Lisätään ostorivin lisätiedot
              $query = "INSERT INTO tilausrivin_lisatiedot
                        SET tilausrivitunnus   = '$lisatty_tun',
                        luontiaika = now(),
                        laatija    = '$kukarow[kuka]',
                        yhtio      = '$kukarow[yhtio]'";
              pupe_query($query);
            }

            // Onko suoratoimitus
            if ($tyyppi == "U" or ($tyyppi == 'KAIKKI' and $yhtiorow["tee_osto_myyntitilaukselta"] == 'Q')) {
              $suoraan_laskutukseen = "o";
            }
            else {
              $suoraan_laskutukseen = "";
            }

            // Päivitetään myyntirivin lisätiedot
            $query = "UPDATE tilausrivin_lisatiedot
                      SET tilausrivilinkki   = '$lisatty_tun',
                      toimittajan_tunnus   = '$ttrow[liitostunnus]',
                      suoraan_laskutukseen = '$suoraan_laskutukseen',
                      muutospvm            = now(),
                      muuttaja             = '$kukarow[kuka]'
                      WHERE yhtio          = '$kukarow[yhtio]'
                      and tilausrivitunnus = '$tilrivirow[rivitunnus]'";
            pupe_query($query);

            $tilauksesta_ostotilaus .= "<font class='message'>Tuote: $tilrivirow[tuoteno] $tilrivirow[kpl] kpl lisätty ostotilaukselle: $tunnus</font><br>";

            // Lisävarusteperheen isä
            if ($tilrivirow["rivitunnus"] == $tilrivirow["perheid2"] and mysql_num_rows($tilrivires) > 1) {
              $pid2 = $lisatty_tun;

              $query = "UPDATE tilausrivi
                        set perheid2 = {$pid2}
                        where yhtio = '$kukarow[yhtio]'
                        and tunnus  = {$lisatty_tun}";
              pupe_query($query);
            }

            // ohita_kerays-perheen isä
            if ($tilrivirow['rivitunnus'] == $tilrivirow['perheid'] and mysql_num_rows($tilrivires) > 1) {
              $pid1 = $lisatty_tun;

              $query = "UPDATE tilausrivi
                        SET perheid = {$pid1}
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  = {$lisatty_tun}";
              pupe_query($query);
            }

            // jos ohita_kerays-perheen lapsi on jätetty puutteeksi myyntitilaukselle, niin päivitetään myös se JT-riviksi.
            if ($tilrivirow['rivitunnus'] != $tilrivirow['perheid'] and $tilrivirow['perheid'] > 0 and mysql_num_rows($tilrivires) > 1) {
              $query = "UPDATE tilausrivi
                        SET var   = 'J',
                        jt          = if('{$yhtiorow["varaako_jt_saldoa"]}'  = '', tilkpl, 0),
                        varattu     = if('{$yhtiorow["varaako_jt_saldoa"]}' != '', tilkpl, 0),
                        hyllyalue   = '',
                        hyllynro    = '',
                        hyllyvali   = '',
                        hyllytaso   = ''
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  = {$tilrivirow['rivitunnus']}";
              pupe_query($query);
            }
          }
        }

        // tehdään rivistä tavallinen JT jos meillä oli joku tilattava rivi
        if ($tyyppi == "KAIKKI" or $rivi["var"] == "T" or $rivi["var"] == "U") {
          $query = "UPDATE tilausrivi
                    set var = 'J'
                    where yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$rivi[rivitunnus]'";
          pupe_query($query);

          // Vapautetaan JT-rivin erät jos niitä on
          if ($rivi["sarjanumeroseuranta"] == "E" or $rivi["sarjanumeroseuranta"] == "F" or $rivi["sarjanumeroseuranta"] == "G") {
            vapauta_sarjanumerot($toim, $rivi["otunnus"], " and tilausrivi.tunnus = '$rivi[rivitunnus]' ");
          }

          if ($yhtiorow["varaako_jt_saldoa"] == "") {
            // Siirretään kappaleet, jos ne on jääny väärälle puolelle
            $query = "UPDATE tilausrivi
                      set jt = varattu, varattu = 0
                      where yhtio  = '$kukarow[yhtio]'
                      and tunnus   = '$rivi[rivitunnus]'
                      and jt       = 0
                      and varattu != 0";
            pupe_query($query);
          }
        }
      }

      // Katsotaan oliko kolmikantakauppaa
      $triangulation = triangulation($otunnus) != "" ? "K" : "";

      $query = "UPDATE lasku SET
                kolmikantakauppa = '{$triangulation}'
                WHERE yhtio      = '{$kukarow['yhtio']}'
                AND tunnus       = '{$otunnus}'";
      $upd_res = pupe_query($query);

      // "Lähetetään heti"-ominaisuus
      if (count($tunnukset) > 0) {

        foreach ($tunnukset as $otunnus) {

          $query = "SELECT lasku.*,
                    toimi.edi_palvelin,
                    toimi.edi_kayttaja,
                    toimi.edi_salasana,
                    toimi.edi_kuvaus,
                    toimi.edi_polku,
                    toimi.email
                    FROM lasku
                    JOIN toimi ON (toimi.yhtio = lasku.yhtio AND toimi.tunnus = lasku.liitostunnus)
                    WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                    AND lasku.tunnus = '{$otunnus}'";
          $ostotilaus_res = pupe_query($query);
          $laskurow = mysql_fetch_assoc($ostotilaus_res);

          $edi_array = array(
            $laskurow['edi_palvelin'],
            $laskurow['edi_kayttaja'],
            $laskurow['edi_salasana'],
            $laskurow['edi_kuvaus'],
            $laskurow['edi_polku']
          );

          if (count(array_filter($edi_array)) > 0) {
            $komento['Ostotilaus'] = 'edi';
          }
          elseif (!empty($laskurow['email'])) {
            $komento['Ostotilaus'] = "toimittajaemail{$laskurow['email']}";
            $luodaan_aineisto = true;
          }
          else {
            $komento['Ostotilaus'] = '';
          }

          if (!empty($komento['Ostotilaus'])) {
            require 'tilauskasittely/tulosta_ostotilaus.inc';
          }

          if (isset($luodaan_aineisto) and $luodaan_aineisto) {
            // päivitetään tässä tilaus tulostetuksi
            $query = "UPDATE lasku SET
                      h1time    = now(),
                      hyvak1    = '{$kukarow['kuka']}',
                      lahetepvm = now(),
                      alatila   = 'A'
                      WHERE tunnus = '{$otunnus}'";
            $result = pupe_query($query);
          }
        }
      }

    }

    return $tilauksesta_ostotilaus;
  }
}
