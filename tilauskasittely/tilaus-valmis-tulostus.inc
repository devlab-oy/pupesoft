<?php

//sisään halutaan $laskurow jossa on tulostettavan tilauksen tiedot
//jos tilauksia on useita $laskurow muuttujassa on jonkun tilauksen tiedot

if (isset($laskurow['varasto']) and $laskurow['varasto'] != 0) {
  // katotaan ihan aluksi, että meillä on edellytykset tulostukselle...
  $query  = "SELECT * FROM varastopaikat WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$laskurow['varasto']}'";
  $prires = pupe_query($query);

  if (mysql_num_rows($prires) != 0) {
    $prirow = mysql_fetch_assoc($prires);

    if ($prirow["printteri1"] != "-88") {
      $query  = "SELECT *
                 FROM kirjoittimet
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND tunnus  = '$prirow[printteri1]'";
      $kirres = pupe_query($query);

      if (mysql_num_rows($kirres) == 0) {
        die ("<font class='error'>VIRHE: Varaston $prirow[nimitys] tulostinta ei löydy. Lähetteitä ei voida tulostaa...</font>");
      }
    }
  }
  else {
    die ("<font class='error'>Yhtään varastoa ei ole määritelty. Lähetteitä ei voida tulostaa...</font>");
  }
}

// tarkistetan tulostuksessa syntyviä virheitä
$virheellinen = "";
$returnvalue  = 0;
$komento      = "";

$onkolaajattoimipaikat = ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikat_res) > 0) ? TRUE : FALSE;

$siirtolistat_kerataan_myos = false;

if ($yhtiorow['kerayserat'] == 'K' and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
  $siirtolistat_kerataan_myos = true;
}

if (strpos($_SERVER['SCRIPT_NAME'], "tulosta_keraysera.php") === FALSE) {
  $query = "LOCK TABLE
            lasku WRITE";
  pupe_query($query);

  $tilausalatila = "A";
  $siirtoalatila = "J";
}
else {
  // Keräsyerien luonnista kun tullaa, niin ei lukita tauluja koska tilauket on päivitetty eri alatilaa.
  $tilausalatila = "KA";
  $siirtoalatila = "KJ";
}

if ($toim == 'VASTAANOTA_REKLAMAATIO' and in_array($yhtiorow['reklamaation_kasittely'], array('U', 'X'))) {
  $queryc = "SELECT sum(if(tila='C' and alatila='B', 1, 0)) ok, count(*) kaikki
             FROM lasku
             WHERE tunnus in ($tilausnumeroita)
             and yhtio    = '$kukarow[yhtio]'";
}
else {
  if ($siirtolistat_kerataan_myos) {
    $queryc = "SELECT sum(if((tila='N' and alatila='$tilausalatila') or (tila='G' and alatila='$siirtoalatila'), 1, 0)) ok, count(*) kaikki
               FROM lasku
               WHERE tunnus in ($tilausnumeroita)
               and yhtio    = '$kukarow[yhtio]'";
  }
  else {
    $queryc = "SELECT sum(if(tila='N' and alatila='$tilausalatila', 1, 0)) ok, count(*) kaikki
               FROM lasku
               WHERE tunnus in ($tilausnumeroita)
               and yhtio    = '$kukarow[yhtio]'";
  }
}

$chk_result = pupe_query($queryc);
$chk_row = mysql_fetch_assoc($chk_result);

// Katsotaan, että tämä keräyslista ei ole jo tulostettu
if ((int) $chk_row["ok"] == (int) $chk_row["kaikki"] and (int) $chk_row["ok"] != 0) {

  // jos tilauksia on useita niin laitetaan niille yhteinen kerayslista-tunnus
  if ($laskuja > 1) {
    $tunnukset = explode(',', $tilausnumeroita);
    $kerayslistatunnus = trim($tunnukset[0]);
  }
  else {
    $kerayslistatunnus = 0;
  }

  // Päivitetään tilaus pois jonosta ja avataan lukot
  if ($toim == 'VASTAANOTA_REKLAMAATIO' and in_array($yhtiorow['reklamaation_kasittely'], array('U', 'X'))) {
    $query = "UPDATE lasku SET
              alatila     = 'C',
              lahetepvm   = now(),
              hyvak3      = '{$kukarow['kuka']}',
              h3time      = now(),
              kerayslista = '{$kerayslistatunnus}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              and tila    = 'C'
              and alatila = 'B'
              and tunnus  in ({$tilausnumeroita})";
    pupe_query($query);
  }
  else {
    $query = "UPDATE lasku SET
              tila        = 'L',
              alatila     = 'A',
              lahetepvm   = now(),
              hyvak3      = '{$kukarow['kuka']}',
              h3time      = now(),
              kerayslista = '{$kerayslistatunnus}'
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  in ($tilausnumeroita)
              AND tila    = 'N'
              AND alatila = '$tilausalatila'";
    pupe_query($query);

    if ($yhtiorow['kerayserat'] != '' and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
      $query = "UPDATE lasku SET
                alatila     = 'A',
                lahetepvm   = now(),
                hyvak3      = '{$kukarow['kuka']}',
                h3time      = now(),
                kerayslista = '{$kerayslistatunnus}'
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  in ($tilausnumeroita)
                AND tila    = 'G'
                AND alatila = '$siirtoalatila'";
      pupe_query($query);
    }
  }

  if (strpos($_SERVER['SCRIPT_NAME'], "tulosta_keraysera.php") === FALSE) {
    // poistetaan lukot
    $query = "UNLOCK TABLES";
    pupe_query($query);
  }

  if ($laskurow['liitostunnus'] != 999999999) {
    //haetaan asiakkaan tiedot
    $query = "SELECT luokka,
              puhelin,
              if (asiakasnro!='', asiakasnro, ytunnus) asiakasnro,
              asiakasnro as asiakasnro_aito,
              kerayserat,
              kieli,
              asiakasviivakoodi
              FROM asiakas
              WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
    $riresult = pupe_query($query);
    $asrow = mysql_fetch_assoc($riresult);
  }
  else {
    $asrow = array(
      'luokka' => '',
      'puhelin' => '',
      'asiakasnro' => '',
      'asiakasnro_aito' => '',
      'kerayserat' => '',
      'kieli' => '',
      'asiakasviivakoodi' => '',
    );
  }

  if ($yhtiorow['saldotarkistus_tulostusjonossa'] != '' and $asrow['kerayserat'] != 'H') {

    if ($yhtiorow['saldotarkistus_tulostusjonossa'] == 'P') {
      $var_wherelisa = "AND var = 'P'";
    }
    elseif ($yhtiorow['saldotarkistus_tulostusjonossa'] == 'JT') {
      $var_wherelisa = "AND var = 'J'";
    }
    else {
      $var_wherelisa = "AND var IN ('P','J')";
    }

    $query = "SELECT *
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus IN ($tilausnumeroita)
              AND tilkpl  > 0
              {$var_wherelisa}";
    $var_chk_res = pupe_query($query);

    if (mysql_num_rows($var_chk_res) > 0) {

      while ($var_chk_row = mysql_fetch_assoc($var_chk_res)) {

        list($var_saldo, $var_hyllyssa, $var_myytavissa) = saldo_myytavissa($var_chk_row['tuoteno'], 'JTSPEC', 0, '', $var_chk_row['hyllyalue'], $var_chk_row['hyllynro'], $var_chk_row['hyllyvali'], $var_chk_row['hyllytaso']);

        if ($var_myytavissa > 0) {

          if ($var_myytavissa >= $var_chk_row['tilkpl']) {

            $query = "UPDATE tilausrivi SET
                      varattu     = tilkpl,
                      var         = ''
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$var_chk_row['tunnus']}'";
            $upd_res = pupe_query($query);
          }
          else {

            $uuden_rivin_varattu = $var_chk_row['tilkpl'] - $var_myytavissa;

            $poikkeukset = array(
              'tilausrivi.varattu' => 0,
              'tilausrivi.tilkpl' => $uuden_rivin_varattu
            );

            kopioi_tilausrivi($var_chk_row['tunnus'], $poikkeukset);

            $query = "UPDATE tilausrivi SET
                      tilkpl      = '{$var_myytavissa}',
                      varattu     = '{$var_myytavissa}',
                      var         = ''
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$var_chk_row['tunnus']}'";
            $upd_res = pupe_query($query);
          }
        }
      }
    }
  }

  $ei_pakkaamoa = !isset($ei_pakkaamoa) ? '' : $ei_pakkaamoa;

  $laskurow['pakkaamo'] = pakkaamo($tilausnumeroita, "yes", $ei_pakkaamoa);

  //keräyslistan tulostusta varten
  require_once "tulosta_lahete_kerayslista.inc";

  if ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $asrow['kerayserat'] == 'A')) {
    $kerayseran_numero = tee_keraysera_painon_perusteella($laskurow, $tilausnumeroita);
  }

  $query = "SELECT ulkoinen_jarjestelma
            FROM varastopaikat
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$laskurow[varasto]'";
  $result = pupe_query($query);
  $varastorow = mysql_fetch_assoc($result);

  $select_lisa       = "";
  $where_lisa        = "";
  $lisa1             = "";
  $pjat_sortlisa     = "";
  $kerayslistatyyppi = "";

  if ($varastorow["ulkoinen_jarjestelma"] == "G") {
    $kerayslistatyyppi = "EXCEL2";
  }
  elseif ($varastorow["ulkoinen_jarjestelma"] == "C") {
    $kerayslistatyyppi = "EXCEL1";
  }
  elseif ($asrow['kerayserat'] == 'H') {
    $kerayslistatyyppi = "LAVAKERAYS";
  }

  // keräyslistalle ei oletuksena tulosteta saldottomia tuotteita
  if ($yhtiorow["kerataanko_saldottomat"] == '') {
    $lisa1 = " and tuote.ei_saldoa = '' ";
  }

  if ($laskurow["tila"] == "V") {
    $sorttauskentta = generoi_sorttauskentta($yhtiorow["valmistus_kerayslistan_jarjestys"]);
    $order_sorttaus = $yhtiorow["valmistus_kerayslistan_jarjestys_suunta"];

    if ($yhtiorow["valmistus_kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

    // Summataan rivit yhteen (HUOM: unohdetaan kaikki perheet!)
    if ($yhtiorow["valmistus_kerayslistan_jarjestys"] == "S") {
      $select_lisa = "sum(tilausrivi.kpl) kpl, sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, '' perheid, '' perheid2, ";
      $where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
    }
  }
  else {
    if ($yhtiorow['kerayserat'] == 'K' and isset($keraysvyohyke) and trim($keraysvyohyke) != '') {
      $query = "SELECT keraysjarjestys
                FROM keraysvyohyke
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$keraysvyohyke}'";
      $ker_res = pupe_query($query);
      $ker_row = mysql_fetch_assoc($ker_res);

      if ($ker_row['keraysjarjestys'] == 'P') {
        $sorttauskentta = 'tuote.tuotemassa as sorttauskentta';
      }
      elseif ($ker_row['keraysjarjestys'] == 'V') {
        $sorttauskentta = "concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno) as sorttauskentta";
      }
      else {
        $sorttauskentta = 'vh.indeksi as sorttauskentta';
      }
    }
    else {
      $sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
      $order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

      if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

      // Summataan rivit yhteen (HUOM: unohdetaan kaikki perheet!)
      if ($yhtiorow["kerayslistan_jarjestys"] == "S") {
        $select_lisa = "sum(tilausrivi.kpl) kpl, sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, '' perheid, '' perheid2, ";
        $where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
      }
    }
  }

  // Asiakaaalla lavakerays
  if ($asrow['kerayserat'] == 'H') {
    require "inc/lavakeraysparametrit.inc";
    require "inc/tulosta_lavakeraystarrat_tec.inc";

    $select_lisa .= $lavakeraysparam;
    $pjat_sortlisa = "tilausrivin_lisatiedot.alunperin_puute,lavasort,";
  }

  // keräyslistan rivit
  if (($yhtiorow['kerayserat'] == 'K' and isset($keraysvyohyke) and trim($keraysvyohyke) != '') or ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $asrow['kerayserat'] == 'A'))) {

    if ($yhtiorow['kerayserat'] == 'K') $vhlisa = "JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso AND vh.keraysvyohyke = kerayserat.keraysvyohyke)";
    else $vhlisa = "";

    $query = "SELECT tilausrivi.*,
              lasku.tila AS lasku_tila,
              lasku.alatila AS lasku_alatila,
              tuote.sarjanumeroseuranta,
              kerayserat.kpl as tilkpl,
              kerayserat.kpl as varattu,
              kerayserat.tunnus as ker_tunnus,
              kerayserat.pakkaus,
              kerayserat.pakkausnro,
              {$sorttauskentta}
              FROM kerayserat
              JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi AND tilausrivi.tyyppi != 'D')
              JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno {$lisa1})
              JOIN lasku ON ( lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus )
              {$vhlisa}
              WHERE kerayserat.otunnus IN ({$tilausnumeroita})
              AND kerayserat.yhtio     = '{$kukarow['yhtio']}'
              ORDER BY sorttauskentta";
  }
  else {
    $query = "SELECT tilausrivi.*,
              lasku.tila AS lasku_tila,
              lasku.alatila AS lasku_alatila,
              $select_lisa
              $sorttauskentta,
              if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
              if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
              tuote.sarjanumeroseuranta,
              tuote.eankoodi
              FROM tilausrivi
              LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
              JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
              JOIN lasku ON ( lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus )
              WHERE tilausrivi.otunnus  in ($tilausnumeroita)
              and tilausrivi.yhtio      = '$kukarow[yhtio]'
              and tilausrivi.tyyppi    != 'D'
              $lisa1
              $where_lisa
              ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
  }
  $riresult = pupe_query($query);

  //generoidaan rivinumerot
  $rivinumerot = array();

  $kal = 1;

  while ($row = mysql_fetch_assoc($riresult)) {
    if ($row['lasku_tila'] == 'G' and $row['lasku_alatila'] == 'A') {
      if ($row['varattu'] == 0 and $row['tilkpl'] == 9999.99) {
        //siirretään kaikki mitä paikalla on
        $vapaana = 0;

        $query = "SELECT saldo
                  from tuotepaikat
                  where tuoteno = '$row[tuoteno]'
                  and yhtio     = '$kukarow[yhtio]'
                  and hyllyalue = '$row[hyllyalue]'
                  and hyllynro  = '$row[hyllynro]'
                  and hyllyvali = '$row[hyllyvali]'
                  and hyllytaso = '$row[hyllytaso]'";
        $alkuresult = pupe_query($query);
        $alkurow = mysql_fetch_array($alkuresult);

        //ennakkopoistot
        $query = "SELECT sum(varattu) keratty
                  FROM tilausrivi
                  WHERE tyyppi  in ('L','G','V')
                  and yhtio     = '$kukarow[yhtio]'
                  and tuoteno   = '$row[tuoteno]'
                  and varattu   > 0
                  and hyllyalue = '$row[hyllyalue]'
                  and hyllynro  = '$row[hyllynro]'
                  and hyllyvali = '$row[hyllyvali]'
                  and hyllytaso = '$row[hyllytaso]'";
        $varatutresult = pupe_query($query);
        $varatutrow = mysql_fetch_array($varatutresult);

        $vapaana = $alkurow["saldo"] - $varatutrow["keratty"];

        if ($vapaana <= 0) {
          $vapaana = 0;
        }

        $query = "UPDATE tilausrivi set tilkpl='$vapaana', varattu='$vapaana'
                  where tunnus='$row[tunnus]'
                  and yhtio='$kukarow[yhtio]'";
        pupe_query($query);
      }
    }

    if ($yhtiorow['kerayserat'] == 'K' and isset($keraysvyohyke) and trim($keraysvyohyke) != '') {
      $rivinumerot[$row["ker_tunnus"]] = $kal;
    }
    else {
      $rivinumerot[$row["tunnus"]] = $kal;
    }
    $kal++;
  }

  // Yhdistettäessä tilauksia samalle keräyslistalle haetaan kaikkien tilausten kommentit yms. tiedot mukaan
  $query = "SELECT
            GROUP_CONCAT(DISTINCT if(tilausyhteyshenkilo != '', tilausyhteyshenkilo, NULL) SEPARATOR ', ') tilaajat,
            GROUP_CONCAT(DISTINCT if(asiakkaan_tilausnumero != '', asiakkaan_tilausnumero, NULL) SEPARATOR ', ') asiakkaan_tilausnumerot,
            GROUP_CONCAT(DISTINCT if(kohde != '', kohde, NULL) SEPARATOR ', ') kohteet,
            GROUP_CONCAT(DISTINCT if(viesti != '', viesti, NULL) SEPARATOR ', ') tilausviitteet,
            GROUP_CONCAT(DISTINCT if(ohjausmerkki != '', ohjausmerkki, NULL) SEPARATOR ', ') ohjausmerkit,
            GROUP_CONCAT(DISTINCT if(comments != '', comments, NULL) SEPARATOR ', ') kommentti1,
            GROUP_CONCAT(DISTINCT if(sisviesti2 != '', sisviesti2, NULL) SEPARATOR ', ') kommentti2
            FROM lasku
            WHERE tunnus IN ({$tilausnumeroita})
            AND yhtio    = '{$kukarow['yhtio']}'";
  $tilausviiteres = pupe_query($query);
  $tilausviiterow = mysql_fetch_assoc($tilausviiteres);

  $laskurow["tilausyhteyshenkilo"] = $tilausviiterow["tilaajat"];
  $laskurow["asiakkaan_tilausnumero"] = $tilausviiterow["asiakkaan_tilausnumerot"];
  $laskurow["kohde"] = $tilausviiterow["kohteet"];
  $laskurow['viesti'] = $tilausviiterow['tilausviitteet'];
  $laskurow["ohjausmerkki"] = $tilausviiterow["ohjausmerkit"];
  $laskurow["comments"] = $tilausviiterow["kommentti1"];
  $laskurow["sisviesti2"] = $tilausviiterow["kommentti2"];

  if (mysql_num_rows($riresult) > 0) {
    mysql_data_seek($riresult, 0);
  }

  if (!isset($kieli)) $kieli = "";
  if (!isset($toim)) $toim = "";
  if (!isset($tyyppi)) $tyyppi = "";

  $kerayseran_numero = !isset($kerayseran_numero) ? 0 : $kerayseran_numero;

  if ($toim != "LAVAKERAYS" and $yhtiorow["lahetteen_tulostustapa"] != "X") {

    $pdfdoc = isset($pdf_kaikki_tul) ? $pdf_kaikki_tul : NULL;
    $ekasivu = !empty($vikasivu) ? $vikasivu : 1;

    $params_kerayslista = array(
      'asrow'             => $asrow,
      'boldi'             => $boldi,
      'iso'               => $iso,
      'iso_boldi'         => $iso_boldi,
      'kala'              => 0,
      'kieli'             => $kieli,
      'komento'           => '',
      'laskurow'          => $laskurow,
      'norm'              => $norm,
      'page'              => '',
      'paino'             => 0,
      'pdf'               => $pdfdoc,
      'perheid'           => 0,
      'perheid2'          => 0,
      'pieni'             => $pieni,
      'pieni_boldi'       => $pieni_boldi,
      'rectparam'         => $rectparam,
      'rivinkorkeus'      => $rivinkorkeus,
      'rivinumerot'       => $rivinumerot,
      'row'               => NULL,
      'sivu'              => $ekasivu,
      'tee'               => $tee,
      'thispage'          => NULL,
      'tilausnumeroita'   => $tilausnumeroita,
      'toim'              => $toim,
      'tots'              => 0,
      'tyyppi'            => $tyyppi,
      'kerayseran_numero' => $kerayseran_numero,
      'kerayslistatyyppi' => $kerayslistatyyppi,
    );

    // Aloitellaan keräyslistan teko
    $params_kerayslista = alku_kerayslista($params_kerayslista);

    while ($row = mysql_fetch_assoc($riresult)) {
      $params_kerayslista["row"] = $row;

      $params_kerayslista = rivi_kerayslista($params_kerayslista);
    }

    $params_kerayslista["tama_lista_sivu"] = $vikasivu;

    $params_kerayslista["tots"] = 1;
    $params_kerayslista = loppu_kerayslista($params_kerayslista);

    $vikasivu = $params_kerayslista["sivu"];

    $valittu_tulostin = !isset($valittu_tulostin) ? '' : $valittu_tulostin;

    if ($valittu_tulostin == "-88") {
      $komento = "-88";
    }
    else {
      $komento = hae_lahete_printteri(
        $laskurow['varasto'],
        $laskurow['yhtio_toimipaikka'],
        $tilausnumeroita,
        $valittu_tulostin,
        "0"
      );
    }
  }

  if ($toim == "LAVAKERAYS" and !empty($valittu_lavakerayslista_tulostin)) {
    require_once "lavakerayslista_pdf.inc";

    $query  = "SELECT *
               FROM kirjoittimet
               WHERE yhtio = '$kukarow[yhtio]'
               AND tunnus = '$valittu_lavakerayslista_tulostin'";
    $kirres = pupe_query($query);
    $kirrow = mysql_fetch_assoc($kirres);

    $params_lavatarra = array(
      'norm'         => $norm,
      'pieni'        => $pieni,
      'pieni_boldi'  => $pieni_boldi,
      'boldi'        => $boldi,
      'iso'          => $iso,
      'iso_boldi'    => $iso_boldi,
      'rectparam'    => $rectparam,
      'komento'      => $kirrow["komento"],
      'toimitustapa' => $laskurow['toimitustapa'],
      'pdf'          => NULL,
      'lavanumero'   => 0,
      'kala'         => 720,
      'tilaukset'    => NULL,
      'tilausnumerot'=> NULL,
      'tee'          => $tee,
      'firstpage'    => NULL,
      'sivunvaihto'  => 30);

    mysql_data_seek($riresult, 0);

    $lavanumero = 1;
    $lava_referenssiluku = 0;
    $lavat = array();
    $rivinumerot = array();
    $tilausnumerot = array();
    $puuterivit = array();
    $kal = 1;

    while ($row = mysql_fetch_assoc($riresult)) {
      if ($row['var'] == "P") {
        $puuterivit[] = $row;
      }

      if (empty($lavat[$lavanumero][$row['otunnus']])) {
        $lavat[$lavanumero][$row['otunnus']] = 0;
      }

      if ($lava_referenssiluku >= lavakerayskapasiteetti) {
        $lavanumero++;
        $lava_referenssiluku=0;
      }

      // myynti_era = 1 / mallitarkenne = 400 poikkeus
      if ((int) $row['myynti_era'] == 1 and (int) $row['mallitarkenne'] == 400) {
        $row['myynti_era'] = 6;
      }

      $lavat[$lavanumero][$row['otunnus']] += round(($row['varattu']+$row['kpl'])/$row['myynti_era'], 2);
      $lava_referenssiluku += ($row['tilkpl'] * $row['lavakoko']);

      $tilausnumerot[$row["otunnus"]] = $row["otunnus"];
      $rivinumerot[$row["tunnus"]] = $kal;
      $kal++;
    }

    $params_lavatarra['tilausnumerot'] = $tilausnumerot;
    $params_lavatarra['lavat'] = $lavat;
    $params_lavatarra['tulostusaika'] = date("Y-m-d H:i:s");
    $params_lavatarra['puuterivit'] = $puuterivit;
    $params_lavatarra = sivu_lavakerayslista($params_lavatarra);

    print_pdf_lavakerayslista($params_lavatarra);

    // HB-kerayslista tulostin
    setcookie("hb_kerayslista_tulostin", $valittu_lavakerayslista_tulostin, time()+60*60*24*90, "/");
  }

  if ($toim == "LAVAKERAYS" and !empty($valittu_lavakeraystarra_tulostin)) {
    $query  = "SELECT *
               FROM kirjoittimet
               WHERE yhtio = '$kukarow[yhtio]'
               AND tunnus = '$valittu_lavakeraystarra_tulostin'";
    $kirres = pupe_query($query);
    $kirrow = mysql_fetch_assoc($kirres);

    // HB-keraystarra tulostin
    setcookie("hb_keraystarra_tulostin", $kirrow['tunnus'], time()+60*60*24*90, "/");

    if (!empty($kirrow['komento'])) {
      tulosta_lavakeraystarrat_tec($riresult, $rivinumerot, $kirrow['komento']);
    }
  }

  // Tulostetaan lista kerrallaan vain jos ei tulosteta kaikkia listoja kerrallaan
  if ($toim != "LAVAKERAYS" and $komento != "" and $yhtiorow["lahetteen_tulostustapa"] != "X" and empty($pdf_kaikki_tul)) {
    // tulostetaan sivu
    $params_kerayslista["komento"] = $komento;

    print_pdf_kerayslista($params_kerayslista);

    // jos halutaan MYÖS lähete
    if ($yhtiorow["lahetteen_tulostustapa"] == "L") {
      $lahetteen_tulostustapa_query = "SELECT *
                                       FROM lasku
                                       WHERE yhtio = '{$kukarow["yhtio"]}'
                                       AND tunnus  in ($tilausnumeroita)";
      $lahetteen_tulostustapa_result = pupe_query($lahetteen_tulostustapa_query);

      // Loopataan kaikki keräyslistan tilaukset ja tulostetaan lähete
      while ($lahetteen_tulostustapa_row = mysql_fetch_assoc($lahetteen_tulostustapa_result)) {
        $params = array(
          'laskurow' => $lahetteen_tulostustapa_row,
          'sellahetetyyppi' => "",
          'extranet_tilausvahvistus' => "",
          'naytetaanko_rivihinta' => "",
          'tee' => "",
          'toim' => $toim,
          'komento' => $komento,
          'lahetekpl' => "",
          'kieli' => ""
        );

        pupesoft_tulosta_lahete($params);
      }
    }

    $returnvalue = 0;
  }

  elseif ($yhtiorow["lahetteen_tulostustapa"] == "X") {
    $returnvalue = 0;
  }
  elseif (!empty($pdf_kaikki_tul) or $toim == "LAVAKERAYS") {
    $returnvalue = 0;
  }
  else {
    $returnvalue = 1;
  }

  //Merkataan epäonnistuneeksi ja palautetaan tulostusjonoon
  if ($returnvalue != 0) {
    echo "<br><font class='error'>".t("Lähetteen tulostus epäonnistui")."! ".t("Tilaus")." $laskurow[tunnus] ".t("siirrettiin tulostusjonoon").". ".t("Käy tulostamassa lähete tulostusjonosta").": <a href='lahetteen_tulostusjono.php'>".t("Tulostusjono")."</a>.</font><br><br>";
    $virheellinen = "X";

    if ($toim == 'VASTAANOTA_REKLAMAATIO' and in_array($yhtiorow['reklamaation_kasittely'], array('U', 'X'))) {
      $query = "UPDATE lasku SET
                alatila     = 'B',
                lahetepvm   = '0000-00-00 00:00:00',
                hyvak3      = '',
                h3time      = '0000-00-00 00:00:00',
                kerayslista = 0
                WHERE yhtio = '$kukarow[yhtio]'
                and tila    = 'C'
                and tunnus  in ($tilausnumeroita)";
      pupe_query($query);
    }
    else {
      $query = "UPDATE lasku SET
                tila        = 'N',
                alatila     = 'A',
                lahetepvm   = '0000-00-00 00:00:00',
                hyvak3      = '',
                h3time      = '0000-00-00 00:00:00',
                kerayslista = 0
                WHERE yhtio = '$kukarow[yhtio]'
                and tila    = 'L'
                and tunnus  in ($tilausnumeroita)";
      pupe_query($query);

      if ($yhtiorow['kerayserat'] != '' and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
        $query = "UPDATE lasku SET
                  alatila     = 'J',
                  lahetepvm   = '0000-00-00 00:00:00',
                  hyvak3      = '',
                  h3time      = '0000-00-00 00:00:00',
                  kerayslista = 0
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tila    = 'G'
                  and tunnus  in ($tilausnumeroita)";
        pupe_query($query);
      }
    }
  }

  // jos meillä ei ollut lähetteen tulostusongelmia
  if ($virheellinen != 'X') {

    $ei_pakkaamoa_lisaviesti = '';

    if (isset($ei_pakkaamoa_selected) and $ei_pakkaamoa_selected == 'checked' and $ei_pakkaamoa == '') {
      $ei_pakkaamoa_lisaviesti = "sisviesti2 = concat(sisviesti2, ' $kukarow[kuka] ".t("halusi tilauksen pakkaamolokeroihin")." ".tv1dateconv(date("Y-m-d H:i:s"))."')";

      $query = "UPDATE lasku SET
                {$ei_pakkaamoa_lisaviesti}
                WHERE yhtio = '{$kukarow['yhtio']}'
                and tunnus  in ({$tilausnumeroita})";
      pupe_query($query);
    }
  }

  if ($valittu_tulostin == "-88") {
    echo "<br>";
  }
}
else {
  echo "<font class='error'>".t("VIRHE: Lähete/Keräyslista on jo tulostettu!")."</font><br>";

  if (strpos($_SERVER['SCRIPT_NAME'], "tulosta_keraysera.php") === FALSE) {
    // poistetaan lukot
    $query = "UNLOCK TABLES";
    $res   = pupe_query($query);
  }
}
