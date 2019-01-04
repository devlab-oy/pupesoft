<?php

if ($tee ==  "VALMIS" or $tee == "LEPAA") {
  //tulostetaan tilaus kun se on valmis
  $otunnus = $laskurow["tunnus"];

  if ($toim != "REKLAMAATIO") {
    $tulostimet[0] = "Työmääräys";
    $tulostimet[1] = "Huoltosaate";
    require_once "tyomaarays/tulosta_tyomaarays.inc";
    require_once "tyomaarays/tulosta_huoltosaate.inc";
  }
  else {
    if ((isset($tilaustyyppi) and $tilaustyyppi == 'U') or $laskurow['tilaustyyppi'] == 'U') {
      // Katsotaan onko avainsanoihin määritelty varaston toimipaikan läheteprintteriä takuulähetettä varten laajassa toimipaikkakäsittelyssä
      $onkolaajattoimipaikat = ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikkares = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikkares) > 0) ? TRUE : FALSE;

      if (!empty($laskurow['yhtio_toimipaikka']) and $onkolaajattoimipaikat) {
        $avainsana_where = " and avainsana.selite       = '{$laskurow['varasto']}'
                             and avainsana.selitetark   = '{$laskurow['yhtio_toimipaikka']}'
                             and avainsana.selitetark_2 = 'printteri1'";

        $tp_tulostin = t_avainsana("VARTOIMTULOSTIN", '', $avainsana_where, '', '', "selitetark_3");

        // käsinvalittu printteri
        if (!empty($tp_tulostin)) {
          $apuprintteri = $valittu_tulostin = $tp_tulostin;
          $kappaleet = "";
          //haetaan lähetteen tulostuskomento
          $query = "SELECT komento
                    FROM kirjoittimet
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tunnus  = '$apuprintteri'";
          $kirres = pupe_query($query);
          $kirrow = mysql_fetch_assoc($kirres);

          $komento = $kirrow['komento'];
        }
        else {
          $tulostimet[0] = "Takuu";
          $tulostimet[1] = "Lähete";
        }
      }
      else {
        $tulostimet[0] = "Takuu";
        $tulostimet[1] = "Lähete";
      }
    }
    else {
      $tulostimet[0] = "Reklamaatio";
    }

    require_once "tilauskasittely/tulosta_lahete_kerayslista.inc";
  }

  if (count($komento) == 0) {
    if (strtolower($tulostimet[0]) == 'takuu' and (isset($tilaustyyppi) and $tilaustyyppi == 'U') or $laskurow['tilaustyyppi'] == 'U') {
      $tilaustyyppi = 'U';

      echo "<font class='head'>", t("Takuu"), ":</font><hr>";
    }
    else {
      echo "<font class='head'>", t($tulostimet[0]), ":</font><hr>";
    }

    require "inc/valitse_tulostin.inc";
  }
  elseif ($kappaleet > 0) {
    if ($toim != "REKLAMAATIO" and !in_array($komento["Työmääräys"], array("email", "-88")) and $tyomtyyppi != 'Z') {
      $komento["Työmääräys"] .= " -# $kappaleet ";
    }
    elseif (isset($komento["Reklamaatio"]) and !in_array($komento["Reklamaatio"], array("email", "-88"))) {
      $komento["Reklamaatio"] .= " -# $kappaleet ";
    }
    elseif (isset($komento["Takuu"]) and !in_array($komento["Takuu"], array("email", "-88"))) {
      $komento["Takuu"] .= " -# $kappaleet ";
    }

    if ($toim != "REKLAMAATIO" and !empty($komento["Huoltosaate"]) and !in_array($komento["Huoltosaate"], array("email", "-88")) and $tyomtyyppi != 'Z') {
      $komento["Huoltosaate"] .= " -# {$kappaleet} ";
    }
  }

  if (!empty($komento["Työmääräys"]) and $komento["Työmääräys"] == "-88") {
    js_openFormInNewWindow();

    echo "<br><form id='tulostakopioform_tm_$laskurow[tunnus]' name='tulostakopioform_tm_$laskurow[tunnus]' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
        <input type='hidden' name='otunnus' value='$laskurow[tunnus]'>
        <input type='hidden' name='toim' value='TYOMAARAYS'>
        <input type='hidden' name='tee' value='NAYTATILAUS'>
        <input type='hidden' name='kieli' value='$kieli'>
        <input type='hidden' name='tyomtyyppi' value='$tyomtyyppi'>
        <input type='submit' value='".t("Näytä työmääräys").": $laskurow[tunnus]' onClick=\"js_openFormInNewWindow('tulostakopioform_tm_$laskurow[tunnus]', ''); return false;\"></form><br>";

    $lopetus = "";
  }

  if (!empty($komento["Huoltosaate"]) and $komento["Huoltosaate"] == "-88") {
    js_openFormInNewWindow();

    echo "<br><form id='tulostakopioform_hs_$laskurow[tunnus]' name='tulostakopioform_hs_$laskurow[tunnus]' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
        <input type='hidden' name='otunnus' value='$laskurow[tunnus]'>
        <input type='hidden' name='toim' value='HUOLTOSAATE'>
        <input type='hidden' name='tee' value='NAYTATILAUS'>
        <input type='hidden' name='kieli' value='$kieli'>
        <input type='submit' value='".t("Näytä huoltosaate").": $laskurow[tunnus]' onClick=\"js_openFormInNewWindow('tulostakopioform_hs_$laskurow[tunnus]', ''); return false;\"></form><br>";

    $lopetus = "";
  }

  if (!empty($komento["Reklamaatio"]) and $komento["Reklamaatio"] == "-88") {
    js_openFormInNewWindow();

    echo "<br><form id='tulostakopioform_rm_$laskurow[tunnus]' name='tulostakopioform_rm_$laskurow[tunnus]' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
        <input type='hidden' name='otunnus' value='$laskurow[tunnus]'>
        <input type='hidden' name='toim' value='REKLAMAATIO'>
        <input type='hidden' name='tee' value='NAYTATILAUS'>
        <input type='hidden' name='kieli' value='$kieli'>
        <input type='submit' value='".t("Näytä reklamaatio").": $laskurow[tunnus]' onClick=\"js_openFormInNewWindow('tulostakopioform_rm_$laskurow[tunnus]', ''); return false;\"></form><br>";

    $lopetus = "";
  }

  if (!empty($komento["Takuu"]) and $komento["Takuu"] == "-88") {
    js_openFormInNewWindow();

    echo "<br><form id='tulostakopioform_tk_$laskurow[tunnus]' name='tulostakopioform_tk_$laskurow[tunnus]' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
        <input type='hidden' name='otunnus' value='$laskurow[tunnus]'>
        <input type='hidden' name='toim' value='TAKUU'>
        <input type='hidden' name='tee' value='NAYTATILAUS'>
        <input type='hidden' name='kieli' value='$kieli'>
        <input type='submit' value='".t("Näytä takuu").": $laskurow[tunnus]' onClick=\"js_openFormInNewWindow('tulostakopioform_tk_$laskurow[tunnus]', ''); return false;\"></form><br>";

    $lopetus = "";
  }

  if (!empty($komento["Lähete"]) and $komento["Lähete"] == "-88") {
    js_openFormInNewWindow();

    echo "<br><form id='tulostakopioform_la_$laskurow[tunnus]' name='tulostakopioform_la_$laskurow[tunnus]' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
        <input type='hidden' name='otunnus' value='$laskurow[tunnus]'>
        <input type='hidden' name='toim' value='LAHETE'>
        <input type='hidden' name='tee' value='NAYTATILAUS'>
        <input type='hidden' name='kieli' value='$kieli'>
        <input type='submit' value='".t("Näytä lähete").": $laskurow[tunnus]' onClick=\"js_openFormInNewWindow('tulostakopioform_la_$laskurow[tunnus]', ''); return false;\"></form><br>";

    $lopetus = "";
  }

  if ($toim == "REKLAMAATIO" and !empty($komento["Lähete"]) and $komento["Lähete"] != "-88") {
    // Tulostetaan lähete
    $params = array(
      'laskurow'          => $laskurow,
      'sellahetetyyppi'       => "",
      'extranet_tilausvahvistus'   => "",
      'naytetaanko_rivihinta'    => "",
      'tee'            => "",
      'toim'            => $toim,
      'komento'           => $komento,
      'lahetekpl'          => "",
      'kieli'           => ""
    );

    pupesoft_tulosta_lahete($params);
  }


  if (($toim == "REKLAMAATIO" and
      ((!empty($komento["Reklamaatio"]) and $komento["Reklamaatio"] != "-88") or
        (!empty($komento["Takuu"]) and $komento["Takuu"] != "-88"))) or
    ($toim != "REKLAMAATIO" and
      ((!empty($komento["Työmääräys"]) and $komento["Työmääräys"] != "-88") or
        (!empty($komento["Huoltosaate"]) and $komento["Huoltosaate"] != "-88")))) {

    if ($laskurow["tila"] == 'U') {
      $where = " uusiotunnus='$laskurow[tunnus]' ";
    }
    else {
      $where = " otunnus='$laskurow[tunnus]' ";
    }

    // Haetaan asiakkaan tiedot
    $query = "SELECT luokka,
              puhelin,
              if (asiakasnro!='', asiakasnro, ytunnus) asiakasnro,
              asiakasnro as asiakasnro_aito,
              kerayserat,
              kieli, 
              asiakasviivakoodi
              FROM asiakas
              WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);
    $asrow = mysql_fetch_array($result);

    // katotaan miten halutaan sortattavan
    if ($toim == "REKLAMAATIO") {
      $sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
      $order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

      if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
      else $pjat_sortlisa = "";

    }
    else {
      $sorttauskentta = generoi_sorttauskentta($yhtiorow["tyomaarayksen_jarjestys"]);
      $order_sorttaus = $yhtiorow["tyomaarayksen_jarjestys_suunta"];

      if ($yhtiorow["tyomaarayksen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
      else $pjat_sortlisa = "";
    }

    $query_ale_lisa = generoi_alekentta('M');

    //työmääräyksen rivit
    $query = "SELECT tilausrivi.*,
              round(tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1), '$yhtiorow[hintapyoristys]') hinta,
              round(tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}, $yhtiorow[hintapyoristys]) rivihinta_verollinen,
              round(tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
              tuote.sarjanumeroseuranta,
              $sorttauskentta,
              if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
              if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
              tuote.sarjanumeroseuranta,
              tuote.eankoodi,
              abs(tilausrivin_lisatiedot.asiakkaan_positio) asiakkaan_positio
              FROM tilausrivi
              LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
              JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
              JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
              WHERE tilausrivi.otunnus  = '$otunnus'
              and tilausrivi.yhtio      = '$kukarow[yhtio]'
              and tilausrivi.tyyppi    != 'D'
              ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
    $riresult = pupe_query($query);

    $tilausnumeroita = $otunnus;

    //generoidaan rivinumerot
    $rivinumerot = array();

    $kal = 1;

    while ($row = mysql_fetch_array($riresult)) {
      $rivinumerot[$row["tunnus"]] = $kal;
      $kal++;
    }

    mysql_data_seek($riresult, 0);

    // Aloitellaan työmääräyksen teko
    if ($toim != "REKLAMAATIO") {

      if ($tyomtyyppi == 'Z') {
        tulosta_tyomaaraystarra_zebra($laskurow, $komento[$tulostimet[0]], $kappaleet);
      }
      else {
        if ($toim == "SIIRTOTYOMAARAYS") {
          $tyyppi = "SISAINEN";
        }
        elseif ((isset($tyomtyyppi) and $tyomtyyppi == "O") or $kukarow['hinnat'] != 0) {
          $tyyppi = "O";
        }
        elseif (isset($tyomtyyppi) and $tyomtyyppi == "P") {
          $tyyppi = "P";
        }
        elseif (isset($tyomtyyppi) and $tyomtyyppi == "A") {
          $tyyppi = "";
        }
        elseif (isset($tyomtyyppi) and $tyomtyyppi == "Q") {
          $tyyppi = "Q";
        }
        else {
          $tyyppi = $yhtiorow["tyomaaraystyyppi"];
        }

        $params_tyomaarays = array(
          "asrow"         => $asrow,
          "boldi"         => $boldi,
          "edtuotetyyppi" => "",
          "iso"           => $iso,
          "kala"          => 0,
          "kieli"         => $kieli,
          "komento"       => $komento[$tulostimet[0]],
          "laskurow"      => $laskurow,
          "lineparam"     => $lineparam,
          "norm"          => $norm,
          "page"          => NULL,
          "pdf"           => NULL,
          "perheid"       => 0,
          "perheid2"      => 0,
          "pieni"         => $pieni,
          "pieni_boldi"   => $pieni_boldi,
          "rectparam"     => $rectparam,
          "returnvalue"   => 0,
          "rivinkorkeus"  => $rivinkorkeus,
          "rivinumerot"   => $rivinumerot,
          "row"           => NULL,
          "sivu"          => 1,
          "tee"           => $tee,
          "thispage"      => NULL,
          "toim"          => $toim,
          "tots"          => 0,
          "tyyppi"        => $tyyppi);

        $params_huoltosaate = $params_tyomaarays;

        // Aloitellaan lomakkeen teko
        $params_tyomaarays = tyomaarays_alku($params_tyomaarays);

        if ($yhtiorow["tyomaarayksen_palvelutjatuottet"] == "") {
          // Ekan sivun otsikot
          $params_tyomaarays['kala'] -= $params_tyomaarays['rivinkorkeus']*3;
          $params_tyomaarays = tyomaarays_rivi_otsikot($params_tyomaarays);
        }

        while ($row = mysql_fetch_assoc($riresult)) {
          $params_tyomaarays["row"] = $row;
          $params_tyomaarays = tyomaarays_rivi($params_tyomaarays);
        }

        if (($toim == 'TYOMAARAYS_ASENTAJA' or $toim == 'TYOMAARAYS') and $yhtiorow['tyomaarays_tulostus_lisarivit'] == 'L') {
          $params_tyomaarays["tots"] = 1;
          $params_tyomaarays = tyomaarays_loppu_lisarivit($params_tyomaarays);
        }
        else {
          $params_tyomaarays["tots"] = 1;
          $params_tyomaarays = tyomaarays_loppu($params_tyomaarays);
        }

        //tulostetaan sivu
        tyomaarays_print_pdf($params_tyomaarays);

        if (!empty($tulostimet[1]) and $tulostimet[1] == "Huoltosaate") {
          $params_huoltosaate = array(
            "_hsiso"       => $_hsiso,
            "kala"         => 0,
            "kieli"        => $kieli,
            "komento"      => $komento["Huoltosaate"],
            "laskurow"     => $laskurow,
            "_hsnorm"      => $_hsnorm,
            "page"         => NULL,
            "pdf"          => NULL,
            "_hspieni"     => $_hspieni,
            "_hsrectparam" => $_hsrectparam,
            "returnvalue"  => 0,
            "row"          => NULL,
            "sivu"         => 1,
            "tee"          => $tee,
            "thispage"     => NULL,
            "yhteensa"     => 0);

          // Aloitellaan lomakkeen teko
          $params_huoltosaate = huoltosaate_alku($params_huoltosaate);

          // Ekan sivun otsikot
          $params_huoltosaate = huoltosaate_rivi_otsikot($params_huoltosaate);

          mysql_data_seek($riresult, 0);

          $yhteensa = 0;

          while ($row = mysql_fetch_assoc($riresult)) {
            $yhteensa += $row['rivihinta_verollinen'];
            $params_huoltosaate["row"] = $row;
            $params_huoltosaate = huoltosaate_rivi($params_huoltosaate);
          }

          $params_huoltosaate["tots"] = 1;
          $params_huoltosaate["yhteensa"] = $yhteensa;
          $params_huoltosaate = huoltosaate_loppu($params_huoltosaate);

          //tulostetaan sivu
          huoltosaate_print_pdf($params_huoltosaate);
        }
      }
    }
    else {

      if (strtolower($tulostimet[0]) == 'takuu') {
        $tyyppi = "TAKUU";
      }
      else {
        $tyyppi = "REKLAMAATIO";
      }

      $params_kerayslista = array(
        'asrow'             => $asrow,
        'boldi'             => $boldi,
        'iso'               => $iso,
        'kala'              => 0,
        'kieli'             => $kieli,
        'komento'           => '',
        'laskurow'          => $laskurow,
        'norm'              => $norm,
        'page'              => '',
        'paino'             => 0,
        'pdf'               => NULL,
        'perheid'           => 0,
        'perheid2'          => 0,
        'pieni'             => $pieni,
        'pieni_boldi'       => $pieni_boldi,
        'rectparam'         => $rectparam,
        'rivinkorkeus'      => $rivinkorkeus,
        'rivinumerot'       => $rivinumerot,
        'row'               => NULL,
        'sivu'              => 1,
        'tee'               => $tee,
        'thispage'          => NULL,
        'tilausnumeroita'   => $tilausnumeroita,
        'toim'              => $toim,
        'tots'              => 0,
        'tyyppi'            => $tyyppi,
        'kerayslistatyyppi' => '');


      // Aloitellaan keräyslistan teko
      $params_kerayslista = alku_kerayslista($params_kerayslista);

      while ($row = mysql_fetch_assoc($riresult)) {
        $params_kerayslista["row"] = $row;
        $params_kerayslista = rivi_kerayslista($params_kerayslista);
      }

      $params_kerayslista["tots"] = 1;
      $params_kerayslista = loppu_kerayslista($params_kerayslista);

      //tulostetaan sivu
      $params_kerayslista["komento"] = $komento[$tulostimet[0]];
      print_pdf_kerayslista($params_kerayslista);
    }
  }
}

if (($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO") and $tee == 'LEPAA') {
  $query = "UPDATE kuka SET kesken=0 WHERE session='$session'";
  $result = pupe_query($query);

  $tee = '';
  $tilausnumero = '';
  $laskurow = '';
  $kukarow['kesken'] = '';

  if ($lopetus != '') {
    lopetus($lopetus, "META");
  }
}

if (($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") and $tee == 'VALMIS') {

  $_alatilapaiv = "";

  if ($laskurow["tila"] == 'A' and !empty($yhtiorow['kerataanko_tyomaaraykset'])) {

    $ttq = "SELECT tulostustapa
            FROM toimitustapa
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND selite  = '{$laskurow['toimitustapa']}'";
    $ttres = pupe_query($ttq);
    $ttrow = mysql_fetch_assoc($ttres);

    if ($laskurow["vienti"] == '' and $ttrow["tulostustapa"] == "X" and $laskurow["nouto"] == "") {

      // Jos meillä on maksupositioita laskulla, tulee se siirtää alatilaan J
      if ($laskurow['jaksotettu'] != 0) {
        $_alatilapaiv = ", alatila='J' ";
      }
      else {
        $_alatilapaiv = ", alatila='D' ";
      }
    }
    else {
      $_alatilapaiv = ", alatila='C' ";
    }
  }

  $query  = "UPDATE lasku
             SET tila='L'
             {$_alatilapaiv}
             WHERE tunnus='$laskurow[tunnus]'
             and tila='A'
             and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  // Katotaan löytyykö tilausrivejä
  $query = "SELECT *
            from tilausrivi
            where yhtio  = '$kukarow[yhtio]'
            and otunnus  = '$laskurow[tunnus]'
            and tyyppi  != 'D'";
  $keres = pupe_query($query);

  // Yhtään riviä ei ole
  if (mysql_num_rows($keres) == 0) {
    $query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
    $result = pupe_query($query);

    // tilaus ei enää kesken...
    $query  = "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
    $result = pupe_query($query);
  }
  else {
    // Tlausta on päivitetty matkan varrella, joten otetaan tuore laskurow
    $query = "SELECT laskun_lisatiedot.*, lasku.*, tyomaarays.*
              FROM lasku
              JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio AND tyomaarays.otunnus = lasku.tunnus)
              LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
              WHERE lasku.tunnus = '$kukarow[kesken]'
              AND lasku.yhtio    = '$kukarow[yhtio]'";
    $result = pupe_query($query);
    $laskurow = mysql_fetch_assoc($result);

    require "tilauskasittely/tilaus-valmis.inc";
  }

  // Lähetetään tekstiviesti asiakkaalle työmääräyksen valmistumisesta jo tässä vaiheessa,
  // jos kerättäviä tuotteita ei ole, muutoin viestin lähetys tapahtuu manuaalisesti
  $ei_kerattavia_riveja = (isset($kerattavia) and $kerattavia == 0);

  if ($yhtiorow['vahvistusviesti_asiakkaalle'] == "Y" and $ei_kerattavia_riveja) {
    require "inc/jt_ja_tyomaarays_valmis_viesti.inc";
    laheta_vahvistusviesti($zoner_tunnarit["username"],
      $zoner_tunnarit["salasana"],
      $tilausnumero,
      true);
  }

  $tee = "";
}

if ($toim == "REKLAMAATIO" and $tee == 'VALMIS') {

  if (!tee_palautustilaus($laskurow)) {
    $query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
    $result = pupe_query($query);

    // tilaus ei enää kesken...
    $query  = "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
    $result = pupe_query($query);
  }
  else {
    $query  = "UPDATE lasku
               SET tila = 'L'
               WHERE tunnus = '$laskurow[tunnus]'
               and tila     = 'C'
               and yhtio    = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    require "tilauskasittely/tilaus-valmis.inc";

    $tee = "";
  }
}

if ($toim == "SIIRTOTYOMAARAYS" and $tee == 'VALMIS') {
  $query  = "UPDATE lasku
             SET alatila = 'J'
             WHERE tunnus = '$laskurow[tunnus]'
             and tila     = 'S'
             and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  //Katotaan löytyykö tilausrivejä
  $query = "SELECT *
            from tilausrivi
            where yhtio  = '$kukarow[yhtio]'
            and otunnus  = '$laskurow[tunnus]'
            and tyyppi  != 'D'";
  $keres = pupe_query($query);

  //Yhtään riviä ei ole
  if (mysql_num_rows($keres) == 0) {
    $query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
    $result = pupe_query($query);

    // tilaus ei enää kesken...
    $query  = "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
    $result = pupe_query($query);
  }
  else {
    require "tilauskasittely/tilaus-valmis-siirtolista.inc";
  }

  $tee = "";
}
