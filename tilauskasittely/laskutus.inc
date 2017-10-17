<?php

// tarvitaan $kukarow['kesken'] jossa on laskutettava tilaus
// ja $laskpp $laskkk $laskvv jos halutaan määritellä poikkeava laskutuspvm
$tulos_ulos_laskutus = "";

// jos erisuuri kun nollaa tai tyhjää niin käytetään laskutuspvm:ää
if ($poikkeava_pvm != '') {
  //poikkeava
  $laskutuspvm = $laskvv."-".$laskkk."-".$laskpp;
  $laskutus_tapahtumapvm = $laskvv."-".$laskkk."-".$laskpp." 23:59:59";
}
else {
  // today
  $laskutuspvm = date("Y-m-d");
  $laskutus_tapahtumapvm = date("Y-m-d H:i:s");
}

// Etsitään lasku
$query = "SELECT *
          FROM lasku
          WHERE tunnus  = '$kukarow[kesken]'
          and yhtio     = '$kukarow[yhtio]'
          and tila      = 'L'
          and alatila  != 'V'
          and alatila  != 'X'";
$laskutusres = pupe_query($query);

if (mysql_num_rows($laskutusres) == 0) {
  $tulos_ulos_laskutus .=  "<font class='error'>".t("Tilaus ei löydy! Oletko painanut back-nappia! Jos et ole, ilmoita virheestä")."!</font>\r\n<br>$query\r\n";
}
else {

  // tilaus löytyi kaikki hyvin....
  $orow = mysql_fetch_assoc($laskutusres);

  // Tsekataan valuuttakurssi kuntoon
  if (!empty($yhtiorow['myyntilaskujen_kurssipaiva']) and trim(strtoupper($orow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

    $valpaiva = "";

    if ($yhtiorow['myyntilaskujen_kurssipaiva'] == 3) {
      // Valuuttakurssi laskutushetken mukaan, eli now()
      $valpaiva = date("Y-m-d");
    }
    elseif ($yhtiorow['myyntilaskujen_kurssipaiva'] == 2) {
      // Valuuttakurssi laskun päiväyksen mukaan
      $valpaiva = $laskutuspvm;
    }
    elseif ($yhtiorow['myyntilaskujen_kurssipaiva'] == 1) {
      // Valuuttakurssi tilauksen toimitusajan mukaan (valitaan se päivä jollon suurin osa riveistä on toimitettu)
      if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K') {
        $toimitettuaikak = " if (keratty = 'saldoton' and toimaika != '0000-00-00', toimaika, toimitettuaika) ";
      }
      elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
        $toimitettuaikak = " if (toimaika != '0000-00-00', toimaika, toimitettuaika) ";
      }
      else {
        $toimitettuaikak = " toimitettuaika ";
      }

      $query = "SELECT date_format($toimitettuaikak, '%Y-%m-%d') toimitettupaiva, count(*) maara
                FROM tilausrivi
                WHERE tilausrivi.otunnus = '$kukarow[kesken]'
                and tilausrivi.yhtio     = '$kukarow[yhtio]'
                and tilausrivi.var       not in ('P','J','O','S')
                and tilausrivi.tyyppi    = 'L'
                GROUP BY toimitettupaiva
                ORDER BY maara desc
                LIMIT 1";
      $toimaikares = pupe_query($query);

      if ($toimaikarow = mysql_fetch_assoc($toimaikares)) {
        $valpaiva = $toimaikarow["toimitettupaiva"];
      }
    }

    if (!empty($valpaiva)) {
      // Haetaan oikean päivän kurssi
      if ($valpaiva == date("Y-m-d")) {
        $query = "SELECT kurssi
                  FROM valuu
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND nimi    = '$orow[valkoodi]'";
      }
      else {
        $query = "SELECT kurssi
                  FROM valuu_historia
                  WHERE kotivaluutta = '$yhtiorow[valkoodi]'
                  AND valuutta       = '$orow[valkoodi]'
                  AND kurssipvm      <= '$valpaiva'
                  ORDER BY kurssipvm DESC
                  LIMIT 1";
      }
      $valres = pupe_query($query);

      if ($valrow = mysql_fetch_assoc($valres)) {
        // Kurssi löytyi ja se vaihtuu
        if (!empty($valrow['kurssi']) and $orow["vienti_kurssi"] != $valrow['kurssi']) {

          // Korjataan tilausrivien hinnat, jotta laskun valuuttamääräinen summa pysyy samana
          $query = "SELECT tilausrivi.hinta, tilausrivi.tunnus
                    FROM tilausrivi
                    WHERE tilausrivi.otunnus = '$kukarow[kesken]'
                    and tilausrivi.yhtio     = '$kukarow[yhtio]'";
          $presult = pupe_query($query);

          while ($trow = mysql_fetch_assoc($presult)) {
            $hinta = round($trow['hinta'] / $orow["vienti_kurssi"] * $valrow['kurssi'], 6);

            $query = "UPDATE tilausrivi
                      SET hinta = '$hinta'
                      WHERE tunnus = '$trow[tunnus]'
                      and yhtio    = '$kukarow[yhtio]'";
            pupe_query($query);
          }

          $orow["vienti_kurssi"] = $valrow['kurssi'];

          $query = "UPDATE lasku
                    SET vienti_kurssi = '$valrow[kurssi]'
                    WHERE tunnus = '$kukarow[kesken]'
                    and yhtio    = '$kukarow[yhtio]'";
          pupe_query($query);
        }
      }
      else {
        $tulos_ulos_laskutus .=  "<font class='error'>".t("VIRHE: päivälle %s ei löytynyt valuuttakurssia", "", $valpaiva)." $orow[valkoodi]!";
      }
    }
  }

  // Etsitään tilausrivit
  $laskutuksen_tilausrivi_query = "SELECT tilausrivi.*, tilausrivin_lisatiedot.osto_vai_hyvitys, tilausrivin_lisatiedot.tunnus lisatietotunnus
                                   FROM tilausrivi
                                   LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
                                   WHERE tilausrivi.otunnus = '$kukarow[kesken]'
                                   and tilausrivi.yhtio     = '$kukarow[yhtio]'
                                   and tilausrivi.var       not in ('P','J','O','S')
                                   and tilausrivi.tyyppi    = 'L'
                                   ORDER BY tilausrivi.tunnus";

  $query = "SELECT panttitili
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$orow['liitostunnus']}'";
  $asiakas_panttitili_chk_res = pupe_query($query);
  $asiakas_panttitili_chk_row = mysql_fetch_assoc($asiakas_panttitili_chk_res);

  // jos asiakkaalla on panttitili käytössä, katsotaan tilausrivien tuotteet läpi onko niissä panttitilillisiä tuotteita
  if ($asiakas_panttitili_chk_row['panttitili'] == 'K') {

    $presult = pupe_query($laskutuksen_tilausrivi_query);

    while ($trow = mysql_fetch_assoc($presult)) {

      // jos tilausrivi ei ole cronin generoima (silloin var2-kenttään tallennetaan PANT-teksti)
      // cron-ohjelma on panttitili_cron.php
      if ($trow['var2'] != 'PANT') {

        $query = "SELECT panttitili
                  FROM tuote
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$trow['tuoteno']}'";
        $tuote_panttitili_chk_res = pupe_query($query);
        $tuote_panttitili_chk_row = mysql_fetch_assoc($tuote_panttitili_chk_res);

        if ($tuote_panttitili_chk_row['panttitili'] == 'K') {
          if ($trow['varattu'] < 0) {

            $tilausrivin_tunnus_chk = $trow['tunnus'];
            $pantti_kpl = abs($trow['varattu']);

            $wherelisa_panttitili = '';

            if ($orow['clearing'] == 'HYVITYS') {
              $query = "SELECT GROUP_CONCAT(lx_otsikko.tunnus) AS tunnukset
                        FROM lasku AS ux_otsikko
                        JOIN lasku AS lx_otsikko ON (lx_otsikko.yhtio = ux_otsikko.yhtio AND lx_otsikko.laskunro = ux_otsikko.laskunro AND lx_otsikko.tila = 'L' AND lx_otsikko.alatila = 'X')
                        WHERE ux_otsikko.yhtio = '{$kukarow['yhtio']}'
                        AND ux_otsikko.tunnus  = '{$orow['vanhatunnus']}'";
              $vanhatunnus_chk_res = pupe_query($query);
              $vanhatunnus_chk_row = mysql_fetch_assoc($vanhatunnus_chk_res);

              if ($vanhatunnus_chk_row['tunnukset'] != '') {
                $wherelisa_panttitili = "AND myyntitilausnro IN ({$vanhatunnus_chk_row['tunnukset']})";
              }
            }

            // etsitään vanhin pantti
            $query = "SELECT *
                      FROM panttitili
                      WHERE yhtio           = '{$kukarow['yhtio']}'
                      AND asiakas           = '{$orow['liitostunnus']}'
                      AND tuoteno           = '{$trow['tuoteno']}'
                      {$wherelisa_panttitili}
                      AND status            = ''
                      AND kaytettypvm       = '0000-00-00'
                      AND kaytettytilausnro = 0
                      ORDER BY myyntipvm ASC";
            $vanhin_pantti_res = pupe_query($query);

            while ($vanhin_pantti_row = mysql_fetch_assoc($vanhin_pantti_res)) {

              $query = "UPDATE panttitili SET
                        status            = 'K',
                        kaytettypvm       = now(),
                        kaytettytilausnro = '{$trow['otunnus']}'
                        WHERE yhtio       = '{$kukarow['yhtio']}'
                        AND tunnus        = '{$vanhin_pantti_row['tunnus']}'";
              $pantti_upd = pupe_query($query);

              $tilkom_upd = trim($trow['kommentti']) != '' ? $trow['kommentti']." ".t("Panttitilillinen tuote, tuote hyvitetty panttitililtä")."." : t("Panttitilillinen tuote, tuote hyvitetty panttitililtä").".";

              if ($vanhin_pantti_row['kpl'] >= $pantti_kpl) {
                // käytetään niin monta panttia kuin on tarvis
                $vanhin_pantti_row['kpl'] -= $pantti_kpl;

                // Panttia jää yli....
                if ($vanhin_pantti_row['kpl'] > 0) {

                  $ale_query_insert_lisa = '';

                  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                    $ale_query_insert_lisa .= " ale{$alepostfix} = '".$vanhin_pantti_row["ale{$alepostfix}"]."', ";
                  }

                  $query = "INSERT INTO panttitili SET
                            yhtio             = '{$kukarow['yhtio']}',
                            asiakas           = '{$vanhin_pantti_row['asiakas']}',
                            tuoteno           = '{$vanhin_pantti_row['tuoteno']}',
                            status            = '',
                            kpl               = '{$vanhin_pantti_row['kpl']}',
                            hinta             = '{$vanhin_pantti_row['hinta']}',
                            alv               = '{$vanhin_pantti_row['alv']}',
                            erikoisale        = '{$vanhin_pantti_row['erikoisale']}',
                            {$ale_query_insert_lisa}
                            myyntipvm         = '{$vanhin_pantti_row['myyntipvm']}',
                            myyntitilausnro   = '{$vanhin_pantti_row['myyntitilausnro']}',
                            kaytettypvm       = '0000-00-00',
                            kaytettytilausnro = 0";
                  $uusi_pantti_res = pupe_query($query);

                  $query = "UPDATE panttitili SET
                            kpl         = {$pantti_kpl}
                            WHERE yhtio = '{$kukarow['yhtio']}'
                            AND tunnus  = '{$vanhin_pantti_row['tunnus']}'";
                  $pantti_upd = pupe_query($query);
                }

                $ale_query_update_lisa = '';

                for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                  $ale_query_update_lisa .= " ale{$alepostfix} = 0, ";
                }

                $query = "UPDATE tilausrivi SET
                          ale1        = 100,
                          {$ale_query_update_lisa}
                          kommentti   = '{$tilkom_upd}'
                          WHERE yhtio = '{$kukarow['yhtio']}'
                          AND tunnus  = '{$tilausrivin_tunnus_chk}'";
                $pantti_update = pupe_query($query);

                break;
              }
              else {
                // jos panttitilillä ei ole tarpeeksi, käytetään se mitä on ja splitataan rivi

                $rfields = "yhtio";
                $rvalues = "'{$kukarow['yhtio']}'";

                for ($i = 1; $i < mysql_num_fields($presult) - 1; $i++) { // Ei tunnusta

                  $fieldname = mysql_field_name($presult, $i);

                  $rfields .= ", ".$fieldname;

                  switch ($fieldname) {
                  case 'varattu':
                    $rvalues .= ", -".($pantti_kpl - $vanhin_pantti_row['kpl']);
                    break;
                  case 'tunnus':
                  case 'kate_korjattu':
                    $rvalues .= ", NULL";
                    break;
                  default:
                    $rvalues .= ", '".mysql_real_escape_string($trow[$fieldname])."'";
                  }
                }

                $kysely_insertti = "INSERT into tilausrivi ($rfields) VALUES ($rvalues)";
                $insresultti = pupe_query($kysely_insertti);
                $insid_uusi  = mysql_insert_id($GLOBALS["masterlink"]);

                $pantti_kpl -= $vanhin_pantti_row['kpl'];

                //Kopioidaan tilausrivin lisatiedot
                $query = "SELECT *
                          FROM tilausrivin_lisatiedot
                          WHERE tilausrivitunnus = '{$tilausrivin_tunnus_chk}'
                          and yhtio              = '{$kukarow['yhtio']}'";
                $monistares2 = pupe_query($query);

                if (mysql_num_rows($monistares2) > 0) {
                  $monistarow2 = mysql_fetch_assoc($monistares2);

                  $rfields = "yhtio";
                  $rvalues = "'{$kukarow['yhtio']}'";

                  for ($i = 1; $i < mysql_num_fields($monistares2) - 1; $i++) { // Ei tunnusta

                    $fieldname = mysql_field_name($monistares2, $i);

                    $rfields .= ", ".$fieldname;

                    switch ($fieldname) {
                    case 'tilausrivitunnus':
                      $rvalues .= ", '{$insid_uusi}'";
                      break;
                    case 'tunnus':
                    case 'kate_korjattu':
                      $rvalues .= ", NULL";
                      break;
                    default:
                      $rvalues .= ", '".mysql_real_escape_string($monistarow2[$fieldname])."'";
                    }
                  }

                  $kysely_insertti = "INSERT into tilausrivin_lisatiedot ($rfields) VALUES ($rvalues)";
                  $insresultti = pupe_query($kysely_insertti);
                }

                $ale_query_update_lisa = '';

                for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                  $ale_query_update_lisa .= " ale{$alepostfix} = 0, ";
                }

                $query = "UPDATE tilausrivi SET
                          varattu     = -".abs($vanhin_pantti_row['kpl']).",
                          ale1        = 100,
                          {$ale_query_update_lisa}
                          kommentti   = '{$tilkom_upd}'
                          WHERE yhtio = '{$kukarow['yhtio']}'
                          AND tunnus  = '{$tilausrivin_tunnus_chk}'";
                $pantti_update = pupe_query($query);
                $tilausrivin_tunnus_chk = $insid_uusi;
              }
            }
          }
          elseif ($trow['varattu'] > 0) {
            if ($orow['clearing'] == 'HYVITYS') {

              // haetaan alkuperäisen tilauksen vanhatunnus
              $query = "SELECT GROUP_CONCAT(lx_otsikko.tunnus) AS tunnukset
                        FROM lasku AS ux_otsikko
                        JOIN lasku AS lx_otsikko ON (lx_otsikko.yhtio = ux_otsikko.yhtio AND lx_otsikko.laskunro = ux_otsikko.laskunro AND lx_otsikko.tila = 'L' AND lx_otsikko.alatila = 'X')
                        WHERE ux_otsikko.yhtio = '{$kukarow['yhtio']}'
                        AND ux_otsikko.tunnus  = '{$orow['vanhatunnus']}'";
              $vanhatunnus_chk_res = pupe_query($query);
              $vanhatunnus_chk_row = mysql_fetch_assoc($vanhatunnus_chk_res);

              if ($vanhatunnus_chk_row['tunnukset'] != '') {
                $wherelisa_panttitili = "AND kaytettytilausnro IN ({$vanhatunnus_chk_row['tunnukset']})";

                // etsitään vanhin pantti, ja merkitään se käyttämättömäksi
                $query = "SELECT *
                          FROM panttitili
                          WHERE yhtio      = '{$kukarow['yhtio']}'
                          AND asiakas      = '{$orow['liitostunnus']}'
                          AND tuoteno      = '{$trow['tuoteno']}'
                          AND kpl          = '{$trow['varattu']}'
                          {$wherelisa_panttitili}
                          AND status      != ''
                          AND kaytettypvm != '0000-00-00'
                          ORDER BY myyntipvm ASC
                          LIMIT 1";
                $vanhin_pantti_res = pupe_query($query);

                if (mysql_num_rows($vanhin_pantti_res) > 0) {

                  $vanhin_pantti_row = mysql_fetch_assoc($vanhin_pantti_res);

                  $query = "UPDATE panttitili SET
                            status            = '',
                            kaytettypvm       = '0000-00-00',
                            kaytettytilausnro = 0
                            WHERE yhtio       = '{$kukarow['yhtio']}'
                            AND tunnus        = '{$vanhin_pantti_row['tunnus']}'";
                  $update_res = pupe_query($query);
                }
              }
            }
            else {

              $ale_query_insert_lisa = '';

              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                $ale_query_insert_lisa .= " ale{$alepostfix} = '".$trow["ale{$alepostfix}"]."', ";
              }

              $query = "INSERT INTO panttitili SET
                        yhtio           = '{$kukarow['yhtio']}',
                        asiakas         = '{$orow['liitostunnus']}',
                        tuoteno         = '{$trow['tuoteno']}',
                        status          = '',
                        kpl             = '{$trow['varattu']}',
                        hinta           = '{$trow['hinta']}',
                        alv             = '{$trow['alv']}',
                        erikoisale      = '{$trow['erikoisale']}',
                        {$ale_query_insert_lisa}
                        myyntipvm       = now(),
                        myyntitilausnro = '{$trow['otunnus']}'";
              $panttitili_upd = pupe_query($query);

              $tilkom_upd = trim($trow['kommentti']) != '' ? $trow['kommentti']."\n".t("Panttitilillinen tuote, veloitus siirretty panttitilille")."." : t("Panttitilillinen tuote, veloitus siirretty panttitilille").".";

              $ale_query_update_lisa = '';

              for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                $ale_query_update_lisa .= " ale{$alepostfix} = 0, ";
              }

              $query = "UPDATE tilausrivi SET
                        ale1        = 100,
                        {$ale_query_update_lisa}
                        kommentti   = '{$tilkom_upd}'
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  = '{$trow['tunnus']}'";
              $panttitili_upd = pupe_query($query);
            }
          }
        }
      }
    }
  }

  // query on ylempänä
  $presult = pupe_query($laskutuksen_tilausrivi_query);

  // nollataan kumulatiivisia muuttujia
  // EE keississä $loppusumma-muuttujassa lasketaan veron määrää saman kaavan mukaan ku laskun tulostuksessa alvierittelyssä
  // ja sit lopuksi summataan $loppusumma+$arvosumma jotta saadaan laskun verollinen loppusumma
  $loppusumma       = 0;
  $loppusumma_valuutassa   = 0;
  $arvosumma        = 0;
  $arvosumma_valuutassa  = 0;
  $katesumma        = 0;

  while ($trow = mysql_fetch_assoc($presult)) {

    // Lisätään tilausrivin_lisatiedot, jos ne puuttuu, kaikilla myyntiriveillä on oltava ne
    if (empty($trow['lisatietotunnus'])) {
      $lisaq = "INSERT INTO tilausrivin_lisatiedot
                SET yhtio        = '{$kukarow['yhtio']}',
                tilausrivitunnus = '{$trow['tunnus']}',
                vanha_otunnus    = '{$trow['otunnus']}',
                luontiaika       = now(),
                laatija          = '{$kukarow['kuka']}'";
      pupe_query($lisaq);
    }

    // Nollataan loopin muuttujat
    $hinta          = 0;
    $hinta_valuutassa      = 0;
    $rivihinta        = 0;
    $rivihinta_valuutassa  = 0;
    $kate          = 0;

    // Marginaaliverotus
    if ($trow["alv"] >= 500 and $trow["alv"] < 600) {
      // Kappalehinta alennuksineen
      $alennukset = generoi_alekentta_php($trow, 'M', 'kerto');
      $hinta = $trow["hinta"] * $alennukset;

      $rivihinta        = round((float) $hinta * $trow["varattu"], $yhtiorow["hintapyoristys"]);                                  // Rivihinta alennuksineen
      $rivihinta_valuutassa  = round((float) laskuval($hinta * $trow["varattu"], $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);                  // Rivihinta alennuksineen valuutassa

      // kumuloidaan loppusummia
      if (strtoupper($yhtiorow['maa']) == 'EE') {
        $loppusumma         += $rivihinta * ($trow["alv"] / 100);                                                                    // Kumuloidaan verollinen loppusumma valuutassa
        $loppusumma_valuutassa  += $rivihinta_valuutassa * ($trow["alv"] / 100);                                            // Kumuloidaan verollinen loppusumma
      }
      else {
        $loppusumma       += $rivihinta;                                                              // Kumuloidaan verollinen loppusumma
        $loppusumma_valuutassa   += $rivihinta_valuutassa;                                                        // Kumuloidaan verollinen loppusumma valuutassa
      }

      $arvosumma        += $rivihinta;                                                                // Kumuloidaan veroton loppusumma
      $arvosumma_valuutassa  += $rivihinta_valuutassa;                                                          // Kumuloidaan veroton loppusumma valuutassa

      $hinta_valuutassa      = round((float) laskuval($hinta, $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);                            // Kappalehinta alennuksineen valuutassa
      $hinta           = round((float) $hinta, $yhtiorow["hintapyoristys"]);                                            // Kappalehinta alennuksineen
    }
    // Myyntihinnat sisältävät arvonlisäveron
    elseif ($yhtiorow["alv_kasittely"] == '') {

      // Käänteinen verotus
      if ($trow["alv"] >= 600) {
        $trow["alv"] = 0;
      }

      // Verollinen kappalehinta alennuksineen
      $alennukset = generoi_alekentta_php($trow, 'M', 'kerto');
      $hinta = $trow["hinta"] * $alennukset;

      $rivihinta        = round((float) $hinta * $trow["varattu"] / (1 + ($trow["alv"] / 100)), $yhtiorow["hintapyoristys"]);                    // Veroton rivihinta alennuksineen
      $rivihinta_valuutassa  = round((float) laskuval($hinta * $trow["varattu"] / (1 + ($trow["alv"] / 100)), $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);    // Veroton rivihinta alennuksineen valuutassa

      // kumuloidaan loppusummia
      if (strtoupper($yhtiorow['maa']) == 'EE') {
        $loppusumma         += $rivihinta * ($trow["alv"] / 100);                                                                    // Kumuloidaan verollinen loppusumma valuutassa
        $loppusumma_valuutassa  += $rivihinta_valuutassa * ($trow["alv"] / 100);                                            // Kumuloidaan verollinen loppusumma
      }
      else {
        $loppusumma         += round((float) $hinta * $trow["varattu"], $yhtiorow["hintapyoristys"]);                                // Kumuloidaan verollinen loppusumma valuutassa
        $loppusumma_valuutassa  += round((float) laskuval($hinta * $trow["varattu"], $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);
      }

      $arvosumma        += $rivihinta;                                                                                                                          // Kumuloidaan veroton loppusumma
      $arvosumma_valuutassa  += $rivihinta_valuutassa;                                                                                                               // Kumuloidaan veroton loppusumma valuutassa

      $hinta_valuutassa      = round((float) laskuval($hinta / (1 + ($trow["alv"] / 100)), $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);            // Veroton kappalehinta alennuksineen valuutassa
      $hinta           = round((float) $hinta / (1 + ($trow["alv"] / 100)), $yhtiorow["hintapyoristys"]);                              // Veroton kappalehinta alennuksineen
    }
    // Myyntihinnat ovat verottomia
    else {

      // Käänteinen verotus
      if ($trow["alv"] >= 600) {
        $trow["alv"] = 0;
      }

      $alennukset = generoi_alekentta_php($trow, 'M', 'kerto');
      $hinta = $trow["hinta"] * $alennukset;                                                                // Veroton kappalehinta alennuksineen

      $rivihinta        = round((float) $hinta * $trow["varattu"], $yhtiorow["hintapyoristys"]);                                  // Veroton kappalehinta alennuksineen
      $rivihinta_valuutassa  = round((float) laskuval($hinta * $trow["varattu"], $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);                  // Veroton kappalehinta alennuksineen valuutassa

      // kumuloidaan loppusummia
      if (strtoupper($yhtiorow['maa']) == 'EE') {
        $loppusumma         += $rivihinta * ($trow["alv"] / 100);                                                                    // Kumuloidaan verollinen loppusumma valuutassa
        $loppusumma_valuutassa  += $rivihinta_valuutassa * ($trow["alv"] / 100);                                            // Kumuloidaan verollinen loppusumma
      }
      else {
        $loppusumma         += round((float) $hinta * $trow["varattu"] * (1 + ($trow["alv"] / 100)), $yhtiorow["hintapyoristys"]);                      // Kumuloidaan verollinen loppusumma valuutassa
        $loppusumma_valuutassa  += round((float) laskuval($hinta * $trow["varattu"] * (1 + ($trow["alv"] / 100)), $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);  // Kumuloidaan verollinen loppusumma
      }

      $arvosumma        += $rivihinta;                                                                // Kumuloidaan veroton loppusumma
      $arvosumma_valuutassa  += $rivihinta_valuutassa;                                                          // Kumuloidaan veroton loppusumma valuutassa

      $hinta_valuutassa    = round((float) laskuval($hinta, $orow["vienti_kurssi"]), $yhtiorow["hintapyoristys"]);                            // Veroton kappalehinta alennuksineen valuutassa
      $hinta          = round((float) $hinta, $yhtiorow["hintapyoristys"]);                                            // Veroton kappalehinta alennuksineen
    }

    // Etsitään tuote
    $query = "SELECT kehahin, ei_saldoa, epakurantti25pvm, epakurantti50pvm, epakurantti75pvm, epakurantti100pvm, sarjanumeroseuranta
              FROM tuote
              WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$trow[tuoteno]'";
    $laskutusres = pupe_query($query);

    if (mysql_num_rows($laskutusres) == 0) {
      $tulos_ulos_laskutus .=  "<font class='error'>".t("Tuote")." $trow[tuoteno] ".t("ei löydy")."! ".t("Tilauksella")." $kukarow[kesken]!</font><br>\r\n";
      $kate          = 0;
      $hinta          = 0;
      $hinta_valuutassa      = 0;
      $rivihinta_valuutassa  = 0;
      $rivihinta        = 0;
    }
    else {

      $turow = mysql_fetch_assoc($laskutusres);

      // Lasketaan tilausrivin arvo ja kate
      if     ($turow['epakurantti100pvm'] != '0000-00-00') $turow['kehahin'] = 0;
      elseif  ($turow['epakurantti75pvm'] != '0000-00-00') $turow['kehahin'] = round($turow['kehahin'] * 0.25, 6);
      elseif   ($turow['epakurantti50pvm'] != '0000-00-00') $turow['kehahin'] = round($turow['kehahin'] * 0.5,  6);
      elseif   ($turow['epakurantti25pvm'] != '0000-00-00') $turow['kehahin'] = round($turow['kehahin'] * 0.75, 6);

      //Paivitetaan tuotteen saldo jos saldoa on varattu
      if ($turow['ei_saldoa'] == '' and $trow['varattu'] != 0) {

        if ($turow["sarjanumeroseuranta"] == "E" or $turow["sarjanumeroseuranta"] == "F" or $turow["sarjanumeroseuranta"] == "G") {
          if ($trow["varattu"] > 0) {

            // Haetaan eka erätiedot
            $query = "SELECT ostorivitunnus, myyntirivitunnus, sarjanumero
                      FROM sarjanumeroseuranta
                      WHERE yhtio          = '$kukarow[yhtio]'
                      and tuoteno          = '$trow[tuoteno]'
                      and myyntirivitunnus = '$trow[tunnus]'
                      LIMIT 1";
            $lisa_res = pupe_query($query);
            $lisa_row = mysql_fetch_assoc($lisa_res);

            if ($lisa_row["ostorivitunnus"] != $lisa_row["myyntirivitunnus"]) {
              // Normikeissi jossa erä on jo tuloutettu

              // luodaan muuttuja jota pienennetään luupissa
              $eraayht = $trow["varattu"];

              // luupataan niin kauan kuin tarvitaan jotta kaikki varatut on hanskattu
              while ($eraayht > 0) {

                // koitetaan löytää vapaita ostettuja eriä mitä myydä
                $query =   "SELECT era_kpl, tunnus, ostorivitunnus
                            FROM sarjanumeroseuranta
                            WHERE yhtio          = '$kukarow[yhtio]'
                            and tuoteno          = '$trow[tuoteno]'
                            and ostorivitunnus   > 0
                            and myyntirivitunnus = 0
                            and sarjanumero      = '$lisa_row[sarjanumero]'
                            and era_kpl          > 0
                            and hyllyalue        = '$trow[hyllyalue]'
                            and hyllynro         = '$trow[hyllynro]'
                            and hyllyvali        = '$trow[hyllyvali]'
                            and hyllytaso        = '$trow[hyllytaso]'
                            ORDER BY era_kpl DESC, tunnus
                            LIMIT 1";
                $erajaljella_res = pupe_query($query);

                // jos löytyy ostettuja eriä myytäväks niin mennään tänne
                if (mysql_num_rows($erajaljella_res) == 1) {
                  $erajaljella_row = mysql_fetch_assoc($erajaljella_res);

                  // luodaan muuttuja jossa annetaan kyselyille määrämuutoksen
                  $eravahennetaan = 0;

                  // jos löydetyn erän määrä riittää koko tarvittavaan määrään niin pannaan kaikki haisemaan
                  if ($erajaljella_row["era_kpl"] >= $eraayht) {
                    $eravahennetaan = $eraayht;
                    $lisataanrivi = "";
                  }
                  // muuten vaan löydetyn erän verran
                  else {
                    $eravahennetaan = $erajaljella_row["era_kpl"];
                    $lisataanrivi = "KYLLAKIITOS";
                  }

                  // vähennetään ostoriviltä tarvittava määrä
                  $query = "UPDATE sarjanumeroseuranta
                            SET era_kpl       = era_kpl-$eravahennetaan,
                            muutospvm            = now(),
                            muuttaja             = '$kukarow[kuka]'
                            WHERE yhtio          = '$kukarow[yhtio]'
                            and tuoteno          = '$trow[tuoteno]'
                            and ostorivitunnus   = '$erajaljella_row[ostorivitunnus]'
                            and myyntirivitunnus = 0
                            and sarjanumero      = '$lisa_row[sarjanumero]'
                            and tunnus           = '$erajaljella_row[tunnus]'";
                  $lisa_res = pupe_query($query);

                  // haetaan päivitettävä myyntirivi jotta voidaan kloonata se myöhemmin tarvittaessa
                  $query = "SELECT *
                            FROM sarjanumeroseuranta
                            WHERE yhtio          = '$kukarow[yhtio]'
                            and tuoteno          = '$trow[tuoteno]'
                            and myyntirivitunnus = '$trow[tunnus]'
                            and era_kpl          = 0
                            LIMIT 1";
                  $erahakures = pupe_query($query);
                  $erahakurow = mysql_fetch_assoc($erahakures);

                  // lisätään myyntiriville sama määrä, ja samalla päivitetään ostorivitunnus oikeaksi
                  $query = "UPDATE sarjanumeroseuranta
                            SET era_kpl       = era_kpl+$eravahennetaan,
                            ostorivitunnus = '$erajaljella_row[ostorivitunnus]',
                            muutospvm      = now(),
                            muuttaja       = '$kukarow[kuka]'
                            WHERE yhtio    = '$kukarow[yhtio]'
                            and tunnus     = '$erahakurow[tunnus]'";
                  $lisa_res = pupe_query($query);

                  // lisätään uusi kloonattu rivi
                  if ($lisataanrivi != '') {
                    $query = "INSERT INTO sarjanumeroseuranta values (";

                    for ($ikentta=0; $ikentta < mysql_num_fields($erahakures)-1; $ikentta++) {

                      $fieldname = mysql_field_name($erahakures, $ikentta);

                      if ($fieldname == 'laatija') {
                        $query .= "'".$kukarow["kuka"]."',";
                      }
                      elseif ($fieldname == 'luontiaika') {
                        $query .= "now(),";
                      }
                      else {
                        $query .= "'".$erahakurow[$fieldname]."',";
                      }
                    }
                    $query .= "0)";

                    $lisa_res = pupe_query($query);
                  }

                  // vähennetään luupattavaa määrää
                  $eraayht -= $eravahennetaan;
                }
                // Ei löytynyt (enää) ostettuja eriä, eli "myyty pakkaselle" niin luodaan uusi erärivi jossa ostorivitunnus on nolla jonne laitetaan loppumäärä.
                else {

                  $query = "INSERT into sarjanumeroseuranta
                            SET yhtio     = '$kukarow[yhtio]',
                            tuoteno        = '$trow[tuoteno]',
                            myyntirivitunnus= 0,
                            ostorivitunnus = '$trow[tunnus]',
                            era_kpl        = '0',
                            laatija        = '$kukarow[kuka]',
                            luontiaika     = now(),
                            hyllyalue      = '$trow[hyllyalue]',
                            hyllynro       = '$trow[hyllynro]',
                            hyllyvali      = '$trow[hyllyvali]',
                            hyllytaso      = '$trow[hyllytaso]',
                            sarjanumero    = '$lisa_row[sarjanumero]'";
                  $lisa_res = pupe_query($query);

                  $query = "UPDATE sarjanumeroseuranta
                            SET era_kpl       = $eraayht,
                            ostorivitunnus       = '$trow[tunnus]',
                            muutospvm            = now(),
                            muuttaja             = '$kukarow[kuka]'
                            WHERE yhtio          = '$kukarow[yhtio]'
                            and tuoteno          = '$trow[tuoteno]'
                            and myyntirivitunnus = '$trow[tunnus]'
                            and era_kpl          = 0
                            ORDER BY tunnus
                            LIMIT 1";
                  $lisa_res = pupe_query($query);

                  // vähennetään luupattavaa määrää
                  $eraayht = 0;

                }
              }
            }
            else {
              // Laskutetaan vaikka ei olla vielä ostettu
              $query = "UPDATE sarjanumeroseuranta
                        SET era_kpl = $trow[varattu]*-1,
                        ostorivitunnus       = 0
                        WHERE yhtio          = '$kukarow[yhtio]'
                        and tuoteno          = '$trow[tuoteno]'
                        and ostorivitunnus   = '$trow[tunnus]'
                        and myyntirivitunnus = 0
                        and sarjanumero      = '$lisa_row[sarjanumero]'
                        ORDER BY tunnus
                        LIMIT 1";
              $lisa_res = pupe_query($query);

              $query = "UPDATE sarjanumeroseuranta
                        SET era_kpl = $trow[varattu]
                        WHERE yhtio          = '$kukarow[yhtio]'
                        and tuoteno          = '$trow[tuoteno]'
                        and myyntirivitunnus = '$trow[tunnus]'
                        ORDER BY tunnus
                        LIMIT 1";
              $lisa_res = pupe_query($query);
            }
          }
        }

        ///* Päivitetään saldo tilausrivin osoittamalla paikalla *///
        $query = "UPDATE tuotepaikat
                  SET saldo = saldo-$trow[varattu],
                  saldo_varattu = saldo_varattu - if($trow[tunnus] != $trow[perheid2] and $trow[perheid2]>0, $trow[varattu], 0),
                  saldoaika     = now()
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and tuoteno   = '$trow[tuoteno]'
                  and hyllyalue = '$trow[hyllyalue]'
                  and hyllynro  = '$trow[hyllynro]'
                  and hyllytaso = '$trow[hyllytaso]'
                  and hyllyvali = '$trow[hyllyvali]'";
        $laskutusres = pupe_query($query);

        if (mysql_affected_rows() == 0) {
          $tulos_ulos_laskutus .= "<font class='error'>".t("Saldoa tuotteelle")." $trow[tuoteno] ".t("ei voitu päivittää paikalla")." $trow[hyllyalue]-$trow[hyllynro]-$trow[hyllytaso]-$trow[hyllyvali].</font><br>\r\n";

          ///* Päivitetään saldo tuotteen oletuspaikalla jos tilausrivillä ei ollut sopivaa paikkaa *///
          $query = "UPDATE tuotepaikat
                    SET saldo = saldo-$trow[varattu],
                    saldo_varattu  = saldo_varattu - if($trow[tunnus] != $trow[perheid2] and $trow[perheid2]>0, $trow[varattu], 0),
                    saldoaika      = now()
                    WHERE yhtio    = '$kukarow[yhtio]'
                    and tuoteno    = '$trow[tuoteno]'
                    and oletus    != ''
                    ORDER BY tunnus
                    LIMIT 1";
          $laskutusres = pupe_query($query);

          if (mysql_affected_rows() == 0) {
            $tulos_ulos_laskutus .= "<font class='error'>".t("Saldoa tuotteelle")." $trow[tuoteno] ".t("ei voitu päivittää oletuspaikalla").".<br></font>\r\n";

            ///* Päivitetään saldo jollekin sopivalle paikalle jos oletuspaikkaa ei löytynyt *///
            $query = "UPDATE tuotepaikat
                      SET saldo = saldo-$trow[varattu],
                      saldo_varattu = saldo_varattu - if($trow[tunnus] != $trow[perheid2] and $trow[perheid2]>0, $trow[varattu], 0),
                      saldoaika     = now(),
                      oletus        = ''
                      WHERE yhtio   = '$kukarow[yhtio]'
                      and tuoteno   = '$trow[tuoteno]'
                      ORDER BY tunnus
                      LIMIT 1";
            $laskutusres = pupe_query($query);

            if (mysql_affected_rows() == 0) {
              $tulos_ulos_laskutus .= "<font class='error'>".t("Tuotteella")." $trow[tuoteno] ".t("ei ole yhtään varastopaikkaa").". ".t("Perustetaan uusi").".<br></font>\r\n";

              $query = "INSERT into tuotepaikat (yhtio, hyllyalue, hyllynro, hyllyvali, hyllytaso, oletus, tuoteno, saldo, saldoaika)
                        VALUES ('$kukarow[yhtio]','Z','99','99','99','X','$trow[tuoteno]',$trow[varattu]*-1,now())";
              $laskutusres = pupe_query($query);

              $tapahtumaquery = "INSERT into tapahtuma set
                                 yhtio     = '$kukarow[yhtio]',
                                 tuoteno   = '$trow[tuoteno]',
                                 kpl       = 0,
                                 kplhinta  = 0,
                                 hinta     = 0,
                                 laji      = 'uusipaikka',
                                 hyllyalue = '$hyllyalue',
                                 hyllynro  = '$hyllynro',
                                 hyllyvali = '$hyllyvali',
                                 hyllytaso = '$hyllytaso',
                                 selite    = '".t("Tuotteella ei ole yhtään varastopaikkaa, perustettiin uusi").": Z 99 99 99',
                                 laatija   = '$kukarow[kuka]',
                                 laadittu  = now()";
              $tapahtumaresult = pupe_query($tapahtumaquery);

            }
          }
        }
      }

      // lasketaan kate
      if ($turow['ei_saldoa'] == '' and ($turow["sarjanumeroseuranta"] == "S" or $turow["sarjanumeroseuranta"] == "G") and $trow["varattu"] > 0) {
        //Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on myyntiä
        $ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $trow["tunnus"]);

        // Kate = Hinta - Ostohinta
        $kate = $hinta - $ostohinta;

        //Tämän sarjanumeroklöntin ostohinta keskimäärin
        $turow["kehahin"] = $ostohinta;

        //tapahtuman otsikko
        $tapahtumattyyppi = "laskutus";
      }
      elseif ($turow['ei_saldoa'] == '' and ($turow["sarjanumeroseuranta"] == "S" or $turow["sarjanumeroseuranta"] == "G") and $trow["varattu"] < 0 and $trow["osto_vai_hyvitys"] == "O") {
        //Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on OSTOA

        // Kate = 0
        $kate         = 0;
        $turow["kehahin"]   = $hinta;

        //tapahtuman otsikko
        $tapahtumattyyppi = "tulo";
      }
      elseif ($turow['ei_saldoa'] == '' and ($turow["sarjanumeroseuranta"] == "S" or $turow["sarjanumeroseuranta"] == "G") and $trow["varattu"] < 0 and $trow["osto_vai_hyvitys"] == "") {
        //Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on HYVITYSTÄ

        //Tähän hyvitysriviin liitetyt sarjanumerot
        $query = "SELECT *
                  FROM sarjanumeroseuranta
                  WHERE yhtio        = '$kukarow[yhtio]'
                  and ostorivitunnus = '$trow[tunnus]'";
        $sarjares = pupe_query($query);

        $ostohinta = 0;

        while ($sarjarow = mysql_fetch_assoc($sarjares)) {

          // Haetaan hyvitettävien myyntirivien kautta alkuperäiset ostorivit
          $query  = "SELECT sarjanumeroseuranta.*
                     FROM sarjanumeroseuranta
                     JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
                     WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
                     and sarjanumeroseuranta.tuoteno           = '$trow[tuoteno]'
                     and sarjanumeroseuranta.sarjanumero       = '$sarjarow[sarjanumero]'
                     and sarjanumeroseuranta.kaytetty          = '$sarjarow[kaytetty]'
                     and sarjanumeroseuranta.myyntirivitunnus  > 0
                     and sarjanumeroseuranta.ostorivitunnus    > 0
                     and sarjanumeroseuranta.tunnus           != '$sarjarow[tunnus]'
                     ORDER BY sarjanumeroseuranta.tunnus DESC
                     LIMIT 1";
          $sarjares1 = pupe_query($query);

          if (mysql_num_rows($sarjares1) == 0) {
            // Jos sarjanumeroa ei ikinä olla ostettu, mutta se on myyty miinukselle ja nyt halutaan perua kaupat (Tarkistukset tehty verkkolasku.phpssä)
            // Jätetään tästä treissi
            $query = "UPDATE tilausrivi
                      SET var2 = 'EIOST'
                      WHERE tunnus = $trow[tunnus]
                      and yhtio    = '$kukarow[yhtio]'";
            $sarjaupdres = pupe_query($query);

            $ostohinta += 0;
          }
          else {
            // Normikeissi jossa sarjanumero on ostettu ja nyt kaupat hyvitetään
            $sarjarow1 = mysql_fetch_assoc($sarjares1);

            $ostohinta += sarjanumeron_ostohinta("tunnus", $sarjarow1["tunnus"]);

            // Tehdään sarjanumerotauluun uusi vapaa sarjanumero-olio
            $query = "UPDATE sarjanumeroseuranta
                      SET myyntirivitunnus=ostorivitunnus, ostorivitunnus='$sarjarow1[ostorivitunnus]'
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$sarjarow[tunnus]'";
            $sarjares1 = pupe_query($query);

            //Jos nyt hyvitettävä sarjanumero onkin jo myyty eteenpäin.
            if ($sarjarow["myyntirivitunnus"] > 0) {
              $myritu = $sarjarow["myyntirivitunnus"];
            }
            else {
              $myritu = 0;
            }

            //  Jos meillä on In-Out erä merkataan myös erän kappalemäärä
            if ($turow["sarjanumeroseuranta"] == "G") {
              $eraKpl = $sarjarow["era_kpl"];
            }
            else {
              $eraKpl = 0;
            }

            $query = "INSERT into sarjanumeroseuranta
                      (yhtio, tuoteno, sarjanumero, lisatieto, ostorivitunnus, myyntirivitunnus, kaytetty, era_kpl, laatija, luontiaika, takuu_alku, takuu_loppu, parasta_ennen, hyllyalue, hyllynro, hyllyvali, hyllytaso)
                      VALUES ('$kukarow[yhtio]','$sarjarow[tuoteno]','$sarjarow[sarjanumero]','$sarjarow[lisatieto]','$sarjarow1[ostorivitunnus]','$myritu','$sarjarow1[kaytetty]', '$eraKpl', '$kukarow[kuka]',now(),'$sarjarow1[takuu_alku]','$sarjarow1[takuu_loppu]', '$sarjarow1[parasta_ennen]', '$sarjarow[hyllyalue]', '$sarjarow[hyllynro]', '$sarjarow[hyllyvali]', '$sarjarow[hyllytaso]')";
            $sarjares1 = pupe_query($query);
          }
        }

        // Kate = Hinta - Alkuperäinen ostohinta
        $kate = $hinta - round($ostohinta/abs($trow["varattu"]), 6);

        //Tämän sarjanumeroklöntin ostohinta keskimäärin
        $turow["kehahin"] = round($ostohinta/abs($trow["varattu"]), 6);

        //tapahtuman otsikko
        $tapahtumattyyppi = "laskutus";
      }
      else {
        // Kate = Hinta - Kehahin (alviton)
        $kate = $hinta - $turow["kehahin"];

        //tapahtuman otsikko
        $tapahtumattyyppi = "laskutus";
      }

      // tehdään tapahtuma
      $query = "INSERT into tapahtuma set
                yhtio      = '$kukarow[yhtio]',
                tuoteno    = '$trow[tuoteno]',
                kpl        =  $trow[varattu] * -1,
                hinta      = '$turow[kehahin]',
                kplhinta   = '$hinta',
                laji       = '$tapahtumattyyppi',
                hyllyalue  = '$trow[hyllyalue]',
                hyllynro   = '$trow[hyllynro]',
                hyllytaso  = '$trow[hyllytaso]',
                hyllyvali  = '$trow[hyllyvali]',
                selite     = '$orow[nimi] $orow[nimitark] ($hinta) [$rivihinta]',
                laatija    = '$kukarow[kuka]',
                rivitunnus = '$trow[tunnus]',
                laadittu   = '$laskutus_tapahtumapvm'";
      $updlaskutusres = pupe_query($query);

      // Tilausrivin kate (alviton)
      $kate = round((float) $kate * $trow["varattu"], $yhtiorow["hintapyoristys"]);

    } // tuote ei löydy else haara

    // kumuloidaan
    $katesumma += $kate;

    // Päivitetään tilausrivi
    $query = "UPDATE tilausrivi
              SET kate         = '$kate',
              rivihinta            = '$rivihinta',
              rivihinta_valuutassa = '$rivihinta_valuutassa',
              kpl                  = varattu,
              varattu              = 0,
              laskutettu           = '$kukarow[kuka]',
              laskutettuaika       = '$laskutuspvm'
              WHERE tunnus         = $trow[tunnus]
              and yhtio            = '$kukarow[yhtio]'";
    $laskutusres = pupe_query($query);
  }


  // $loppusumma muuttujassa oli vain veron määrä, nyt lisätään siihen veroton summa ja saadaan verollinen loppusumma
  // Vain EE keississä
  if (strtoupper($yhtiorow['maa']) == 'EE') {
    $loppusumma         += $arvosumma;
    $loppusumma_valuutassa  += $arvosumma_valuutassa;
  }

  // tehdään maksuehtokäsittely vain jos eräpäivää ei ole syötetty jo laskulle...
  if ($orow["erpcm"] == "0000-00-00") {
    // Etsitään maksuehto
    $query = "SELECT *
              FROM maksuehto
              WHERE tunnus = '$orow[maksuehto]' and yhtio = '$kukarow[yhtio]'";
    $presult = pupe_query($query);

    if (mysql_num_rows($presult) == 0) {
      $tulos_ulos_laskutus .= "Laskutus.inc: ".t("Maksuehtoa ei löydy! Maksuehto")." $orow[maksuehto] ".t("Yhtiö")." $kukarow[yhtio] ".t("Tilaus")." $kukarow[kesken]<br>\r\n";
    }

    $xrow = mysql_fetch_assoc($presult);

    if ($xrow['abs_pvm'] === null and $xrow["rel_pvm"] != 0) {
      if (isset($erpcmlaskenta) and $erpcmlaskenta == "NOW") {
        $erapvm = "adddate(curdate(), interval $xrow[rel_pvm] day)";
      }
      else {
        $erapvm = "adddate('$laskutuspvm', interval $xrow[rel_pvm] day)";
      }
    }
    elseif ($xrow['abs_pvm'] !== null) {
      $erapvm = "'$xrow[abs_pvm]'";
    }
    else {
      $erapvm = "'$laskutuspvm'";
    }

    if ($xrow['kassa_abspvm'] !== null or $xrow["kassa_relpvm"] > 0) {
      if ($xrow['kassa_abspvm'] === null) {
        $kassa_erapvm = "adddate('$laskutuspvm', interval $xrow[kassa_relpvm] day)";
      }
      else {
        $kassa_erapvm = "'$xrow[kassa_abspvm]'";
      }
      $kassa_loppusumma         = round((float) $loppusumma * $xrow['kassa_alepros']/100, 2);
      $kassa_loppusumma_valuutassa   = round((float) $loppusumma_valuutassa * $xrow['kassa_alepros']/100, 2);
    }
    else {
      $kassa_erapvm           = "''";
      $kassa_loppusumma         = "";
      $kassa_loppusumma_valuutassa   = "";
    }
  }
  else {
    // otetaan eräpvm suoraan tilaukselta
    $erapvm                    = "'$orow[erpcm]'";
    $kassa_erapvm           = "''";
    $kassa_loppusumma         = "";
    $kassa_loppusumma_valuutassa   = "";
  }

  $pyorerotus        = 0;
  $pyorerotus_valuutassa  = 0;
  $loppusumma       = round((float) $loppusumma, 2);
  $loppusumma_valuutassa   = round((float) $loppusumma_valuutassa, 2);
  $arvosumma        = round((float) $arvosumma, 2);
  $arvosumma_valuutassa  = round((float) $arvosumma_valuutassa, 2);
  $katesumma        = round((float) $katesumma, 2);

  // jos maksetaan käteisellä pyöristetään lähimpään viiteen senttiin
  if ($xrow["kateinen"] == 'p' and $yhtiorow['maa'] != 'EE') {
    // kotivaluutassa
    if (trim(strtoupper($orow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
      $pyorerotus = round((float) $loppusumma, 2) - round((float) round((float) $loppusumma / 0.05) * 0.05, 2);
    }
    // valuutassa
    if (trim(strtoupper($orow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
      $pyorerotus_valuutassa = round((float) $loppusumma_valuutassa, 2) - round((float) round((float) $loppusumma_valuutassa / 0.05) * 0.05, 2);
    }
  }

  // Etsitään asiakas
  $query = "SELECT laskunsummapyoristys
            FROM asiakas
            WHERE tunnus = '$orow[liitostunnus]'
            and yhtio    = '$kukarow[yhtio]'";
  $asres = pupe_query($query);
  $asrow = mysql_fetch_assoc($asres);

  // käsin syötetty summa johon lasku pyöristetään kotivaluutassa
  if ($orow["hinta"] <> 0) {
    if (abs($orow["hinta"] - $loppusumma) <= 0.5 and trim(strtoupper($orow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
      $pyorerotus = $loppusumma - $orow["hinta"];
    }

    // käsin syötetty summa johon lasku pyöristetään valuutassa
    if (abs($orow["hinta"] - $loppusumma_valuutassa) <= 0.5 and trim(strtoupper($orow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
      $pyorerotus_valuutassa = $loppusumma_valuutassa - $orow["hinta"];
    }
  }

  // jos halutaan pyöristää laskun loppusumma lähimpään tasalukuun
  if ($yhtiorow["laskunsummapyoristys"] != '' or $asrow["laskunsummapyoristys"] != '') {
    // kotivaluutassa
    if (trim(strtoupper($orow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
      $pyorerotus = $loppusumma - round((float) $loppusumma, 0);
    }
    // valuutassa
    if (trim(strtoupper($orow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
      $pyorerotus_valuutassa = $loppusumma_valuutassa - round((float) $loppusumma_valuutassa, 0);
    }
  }

  // katotaan mikä viivästyskorko laitetaan
  $viivastyskorko = $orow['viikorkopros'] == 0 ? 0 : $yhtiorow['viivastyskorko'];

  if ($orow["clearing"] == "KORKOLASKU") {
    $viivastyskorko = 0;
  }

  // HUOM: Tallennetaan PYÖRISTETTY loppusumma laskun loppusummaksi.
  $loppusumma = round($loppusumma - $pyorerotus, 2);
  $loppusumma_valuutassa = round($loppusumma_valuutassa - $pyorerotus_valuutassa, 2);

  // Jos kotivaluutassa niin viedään samat luvut näihin kenttiin
  if (trim(strtoupper($orow["valkoodi"])) == trim(strtoupper($yhtiorow["valkoodi"]))) {
    $loppusumma_valuutassa = $loppusumma;
    $pyorerotus_valuutassa = $pyorerotus;
  }

  // jos veroton summa on nolla ja verollinen summa on myös hyvin lähellä nollaa
  // niin tehdään nollalasku
  if ($arvosumma == 0 and abs($loppusumma) <= 0.05 and $pyorerotus == 0) {
    $arvosumma = 0;
    $arvosumma_valuutassa = 0;
    $loppusumma = 0;
    $loppusumma_valuutassa = 0;
    $pyorerotus = 0;
    $pyorerotus_valuutassa = 0;
    $kassa_loppusumma = 0;
    $kassa_loppusumma_valuutassa = 0;
  }

  $query = "UPDATE lasku
            SET alatila          = 'V',
            arvo                 = '$arvosumma',
            arvo_valuutassa      = '$arvosumma_valuutassa',
            kate                 = '$katesumma',
            summa                = '$loppusumma',
            summa_valuutassa     = '$loppusumma_valuutassa',
            pyoristys            = '$pyorerotus',
            pyoristys_valuutassa = '$pyorerotus_valuutassa',
            erpcm                = $erapvm,
            kapvm                = $kassa_erapvm,
            kasumma              = '$kassa_loppusumma',
            kasumma_valuutassa   = '$kassa_loppusumma_valuutassa',
            viikorkopros         = '$viivastyskorko',
            laskuttaja           = '$kukarow[kuka]',
            tapvm                = '$laskutuspvm',
            maksuehto            = '$orow[maksuehto]',
            maksuteksti          = if(maksuteksti='','$xrow[teksti]',maksuteksti),
            laskutettu           = now()
            WHERE tunnus         = '$kukarow[kesken]'
            and yhtio            = '$kukarow[yhtio]'";
  $laskutusres = pupe_query($query);

  $id = 0;

  // Ei enää kesken
  $query  = "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kesken='$kukarow[kesken]'";
  $laskutusres = pupe_query($query);

} // end tilaus ei löydy else haara

//echotaan rudulle jos kyseessä ei ole batch-ajo
if ($tulos_ulos == "" and $silent == "") {
  echo $tulos_ulos_laskutus;
}
