<?php

if (!function_exists("tilauksesta_varastosiirto")) {
  /**
   * Funktio jakaa myyntitilauksen tilausrivit varastosiirroiksi l‰hde-kohdevarasto kombinaatioiden avulla
   *
   * @global array $yhtiorow
   * @global array $kukarow
   * @global string $toim
   * @global string $pupe_root_polku
   * @param int     $myyntitilaus_tunnus
   * @param char    $kirjanpidollinen_varastosiirto N = normaali, K = kirjanpidollinen, P = palautus
   * @return boolean
   */


  function tilauksesta_varastosiirto($myyntitilaus_tunnus, $varastosiirto_tyyppi = 'N', $_poikkeavalaskutuspvm = '') {
    global $yhtiorow, $kukarow;

    if (empty($myyntitilaus_tunnus)) {
      return false;
    }

    $myyntitilaus         = hae_lasku($myyntitilaus_tunnus);
    $myyntitilaus['tilausrivit'] = hae_tilausrivit($myyntitilaus_tunnus, $varastosiirto_tyyppi, true);

    if ($varastosiirto_tyyppi == 'K') {
      $ok = kirjanpidollinen_varastosiirto($myyntitilaus, $_poikkeavalaskutuspvm);
    }
    elseif ($varastosiirto_tyyppi == 'N') {
      $ok = normaali_varastosiirto($myyntitilaus);
    }
    else {
      $ok = palautus_varastosiirto($myyntitilaus);
    }

    return $ok;
  }
}

if (!function_exists('kirjanpidollinen_varastosiirto')) {
  function kirjanpidollinen_varastosiirto($myyntitilaus, $_poikkeavalaskutuspvm) {
    global $kukarow, $yhtiorow;

    //Kirjanpidollisessa varastosiirrossa yhdell‰ myyntitilauksella voi olla vain 1 l‰hde-kohdevarasto kombinaatio.
    //Kombinaatio muodostuu L‰hde: lasku.varasto kohde: lasku.yhtio_toimipaikka.varasto.first
    $toimipaikan_varastot = hae_yhtion_toimipaikan_varastot($myyntitilaus['yhtio_toimipaikka']);

    $lahde_ja_kohde_varasto_yhdistelma = array(
      'lahdevarasto_tunnus'   => $myyntitilaus['varasto'],
      'kohdevarasto_tunnus'   => $toimipaikan_varastot[0]['tunnus'],
    );

    $myyntitilausrivit = array_find($myyntitilaus['tilausrivit'], $lahde_ja_kohde_varasto_yhdistelma);

    // Ei teh‰ mit‰‰ jos ei ole yht‰‰n rivi‰
    if (empty($myyntitilausrivit)) {
      return false;
    }

    aseta_kukarow_kesken(0);

    //Kaikilla myyntitilausriveill‰ on sama toimitustapa, joka m‰‰ritell‰‰n avainsanoissa SIIRTOVARASTOT. T‰llˆin voidaan viitata indeksill‰.
    $varastosiirto = luo_varastosiirto($lahde_ja_kohde_varasto_yhdistelma['lahdevarasto_tunnus'], $lahde_ja_kohde_varasto_yhdistelma['kohdevarasto_tunnus'], $myyntitilausrivit[0]['toimitustapa_tunnus'], $myyntitilaus['tunnus'], "K");

    if (empty($varastosiirto)) {
      return false;
    }

    aseta_kukarow_kesken($varastosiirto['tunnus']);

    foreach ($myyntitilausrivit as $tilausrivi) {
      $lisatyt_rivit1 = luo_varastosiirtorivi($varastosiirto, $tilausrivi, $lahde_ja_kohde_varasto_yhdistelma['lahdevarasto_tunnus'], 'K');
      linkkaa_myyntitilausrivi_varastosiirtoriviin($lisatyt_rivit1[0], $tilausrivi['tunnus']);
    }

    aseta_kukarow_kesken($myyntitilaus['tunnus']);

    $varastosiirtorivit = hae_tilausrivit($varastosiirto['tunnus'], 'K', false);
    $varastosiirtorivit = aseta_varastosiirto_vastaanotetuksi($varastosiirto, $varastosiirtorivit, $_poikkeavalaskutuspvm);
    paivita_myyntitilausrivien_tuotepaikat($varastosiirtorivit);
    linkkaa_varastosiirto_myyntitilaukseen($varastosiirto, $myyntitilaus);

    return true;
  }
}

if (!function_exists('normaali_varastosiirto')) {
  function normaali_varastosiirto($myyntitilaus) {
    global $kukarow, $yhtiorow;

    //Jokaiselle tilausriville voi m‰‰ritt‰‰ erikseen l‰hde sek‰ kohde varaston
    //Mahdolliset kombinaatiot muodostuvat avainsana.laji = SIIRTOVARASTOT, lasku.varasto tai lasku.yhtio_toimipaikka.varastot perusteella
    $keys = array(
      'lahdevarasto_tunnus',
      'kohdevarasto_tunnus',
      'keraysvyohyke'
    );

    $lahde_ja_kohde_varasto_yhdistelmat = array_find_unique_values($myyntitilaus['tilausrivit'], $keys);

    if (empty($lahde_ja_kohde_varasto_yhdistelmat)) {
      return false;
    }

    $kommentit = array();
    $yhtiorow_temp = $yhtiorow;
    foreach ($lahde_ja_kohde_varasto_yhdistelmat as $lahde_ja_kohde_varasto_yhdistelma) {
      $lahdevarasto = hae_varasto($lahde_ja_kohde_varasto_yhdistelma['lahdevarasto_tunnus']);
      $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio'], $lahdevarasto['toimipaikka']);

      $myyntitilausrivit = array_find($myyntitilaus['tilausrivit'], $lahde_ja_kohde_varasto_yhdistelma);

      aseta_kukarow_kesken(0);
      //Kaikilla myyntitilausriveill‰ on sama toimitustapa, joka m‰‰ritell‰‰n avainsanoissa SIIRTOVARASTOT. T‰llˆin voidaan viitata indeksill‰.
      $varastosiirto = luo_varastosiirto($lahde_ja_kohde_varasto_yhdistelma['lahdevarasto_tunnus'], $lahde_ja_kohde_varasto_yhdistelma['kohdevarasto_tunnus'], $myyntitilausrivit[0]['toimitustapa_tunnus'], $myyntitilaus['tunnus']);

      if (empty($varastosiirto)) {
        return false;
      }

      //P‰ivitet‰‰n myyntitilausrivit var J ja tuotepaikka l‰hdevarastosta kohdevarastoon, jotta ne eiv‰t varaa saldoa kun varastosiirtorivi lis‰t‰‰n.
      $tilausrivi_tunnukset = array_column($myyntitilausrivit, 'tunnus');
      aseta_tilausrivien_var($tilausrivi_tunnukset, 'J');
      paivita_myyntitilausrivien_tuotepaikat($myyntitilausrivit, false);

      aseta_kukarow_kesken($varastosiirto['tunnus']);

      $_tuoteperheet = array();

      foreach ($myyntitilausrivit as $tilausrivi) {
        $lisatyt_rivit1 = luo_varastosiirtorivi($varastosiirto, $tilausrivi, $lahde_ja_kohde_varasto_yhdistelma['lahdevarasto_tunnus'], 'N');
        linkkaa_myyntitilausrivi_varastosiirtoriviin($lisatyt_rivit1[0], $tilausrivi['tunnus']);

        $_tunnus = $lisatyt_rivit1[0];

        if ($tilausrivi['perheid'] != 0 and $tilausrivi['perheid'] == $tilausrivi['tunnus']) {
          // MYYNNIN TUOTEPERHEEN ISƒRIVI
          $_tuoteperheet[$tilausrivi['perheid']] = $_tunnus;

          $query = "UPDATE tilausrivi SET
                    perheid     = tunnus
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$_tunnus}'";
          $updres = pupe_query($query);
        }
        elseif ($tilausrivi['perheid'] != 0 and $tilausrivi['perheid'] != $tilausrivi['tunnus']) {

          // MYYNNIN TUOTEPERHEEN LAPSIRIVI
          if (array_key_exists($tilausrivi['perheid'], $_tuoteperheet)) {

            $query = "UPDATE tilausrivi SET
                      perheid     = '{$_tuoteperheet[$tilausrivi['perheid']]}'
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$_tunnus}'";
            $updres = pupe_query($query);

            if ($tilausrivi['ohita_kerays'] != '') {
              $query = "UPDATE tilausrivin_lisatiedot SET
                        ohita_kerays         = '{$tilausrivi['ohita_kerays']}'
                        WHERE yhtio          = '{$kukarow['yhtio']}'
                        AND tilausrivitunnus = '{$_tunnus}'";
              $updres = pupe_query($query);
            }
          }
        }

        $kommentit[] = t('Tilauksen')." {$myyntitilaus['tunnus']} ".t('tilausrivi')." {$tilausrivi['tuoteno']} - {$tilausrivi['tilkpl']} {$tilausrivi['yksikko']} ".t('lis‰ttiin siirtolistalle')." {$varastosiirto['tunnus']}";
      }

      tilaus_valmis_siirtolista($varastosiirto);
    }

    aseta_kukarow_kesken($myyntitilaus['tunnus']);

    foreach ($kommentit as $kommentti) {
      echo "<font class='message'>$kommentti</font>";
      echo "<br/>";
    }
    echo "<br/>";

    $yhtiorow = $yhtiorow_temp;

    return true;
  }
}

if (!function_exists('palautus_varastosiirto')) {
  function palautus_varastosiirto($myyntitilaus) {
    global $kukarow, $yhtiorow;

    $keys = array(
      'varasto',
      'palautus_varasto'
    );

    $lahde_ja_kohde_varasto_yhdistelmat = array_find_unique_values($myyntitilaus['tilausrivit'], $keys);

    $kommentit = array();
    foreach ($lahde_ja_kohde_varasto_yhdistelmat as $lahde_ja_kohde_varasto_yhdistelma) {
      $myyntitilausrivit = array_find($myyntitilaus['tilausrivit'], $lahde_ja_kohde_varasto_yhdistelma);

      $varastosiirto = hae_avoin_varastosiirto($lahde_ja_kohde_varasto_yhdistelma['varasto'], $lahde_ja_kohde_varasto_yhdistelma['palautus_varasto']);
      if (empty($varastosiirto)) {
        aseta_kukarow_kesken(0);
        //Kaikilla myyntitilausriveill‰ on sama toimitustapa, joka m‰‰ritell‰‰n avainsanoissa SIIRTOVARASTOT. T‰llˆin voidaan viitata indeksill‰.
        $varastosiirto = luo_varastosiirto($lahde_ja_kohde_varasto_yhdistelma['varasto'], $lahde_ja_kohde_varasto_yhdistelma['palautus_varasto'], '', $myyntitilaus['tunnus']);
      }

      if (empty($varastosiirto)) {
        return false;
      }

      aseta_kukarow_kesken($varastosiirto['tunnus']);
      foreach ($myyntitilausrivit as $tilausrivi) {
        $lisatyt_rivit1 = luo_varastosiirtorivi($varastosiirto, $tilausrivi, $lahde_ja_kohde_varasto_yhdistelma['varasto'], 'P');

        linkkaa_myyntitilausrivi_varastosiirtoriviin($lisatyt_rivit1[0], $tilausrivi['tunnus']);

        $kommentit[] = t('Tilauksen')." {$myyntitilaus['tunnus']} ".t('tilausrivi')." {$tilausrivi['tuoteno']} - ".($tilausrivi['tilkpl'] * -1)." {$tilausrivi['yksikko']} ".t('lis‰ttiin siirtolistalle')." {$varastosiirto['tunnus']}";
      }
    }

    aseta_kukarow_kesken($myyntitilaus['tunnus']);

    foreach ($kommentit as $kommentti) {
      echo "<font class='message'>$kommentti</font>";
      echo "<br/>";
    }
    echo "<br/>";

    return true;
  }
}

if (!function_exists('tilaus_valmis_siirtolista')) {

  function tilaus_valmis_siirtolista($laskurow) {
    global $kukarow, $yhtiorow;

    $toim = "SIIRTOLISTA";
    $tulostetaan = '';

    require 'tilauskasittely/tilaus-valmis-siirtolista.inc';
  }

}

if (!function_exists('luo_varastosiirtorivi')) {
  function luo_varastosiirtorivi($laskurow, $tilausrivi, $lahdevarasto_tunnus, $varastosiirtotyyppi = 'N', $perhekielto = 1) {
    global $kukarow, $yhtiorow;

    $trow = hae_tuote($tilausrivi['tuoteno']);

    // varastosiirtotyyppi:
    // N = normaali
    // K = kirjanpidollinen
    // G = relex transfer
    // P = palautus

    $tuoteno_echo     = $trow['tuoteno'];
    $yksikko_echo     = $trow['yksikko'];
    $toimaika         = $laskurow["toimaika"];
    $kerayspvm        = $laskurow["kerayspvm"];
    $tuoteno          = $trow["tuoteno"];
    $kpl              = ($varastosiirtotyyppi == 'P' ? $tilausrivi['varattu'] * -1 : $tilausrivi['varattu']);
    $jtkielto         = $laskurow['jtkielto'];
    $varasto          = $lahdevarasto_tunnus;
    $hinta            = "";
    $netto            = "";
    $korvaavakielto   = 1;
    $orvoteikiinnosta = "EITOD";

    // Generoiduille listoille ei laiteta v‰kisin
    if ($varastosiirtotyyppi != "G") {
      $var = 'H';
    }

    //Laitetaan tuote oletusvaraston tuotepaikalle
    $tuotteen_oletuspaikka = hae_tuotteen_tuotepaikat($tilausrivi['tuoteno'], $tilausrivi['kohdevarasto_tunnus']);

    if (empty($tuotteen_oletuspaikka)) {
      //Jos tuotteella ei ole tuotepaikkaa kohdevarastossa, luodaan se
      $tuotteen_oletuspaikka[0] = lisaa_tuotepaikka($tuoteno, "", "", "", "", "", "", 0, 0, $tilausrivi['kohdevarasto_tunnus']);
    }

    // otetaan eka lˆytynyt tuotepaikka kohdetuotepaikaksi
    $kohde_alue   = $tuotteen_oletuspaikka[0]['hyllyalue'];
    $kohde_nro    = $tuotteen_oletuspaikka[0]['hyllynro'];
    $kohde_vali   = $tuotteen_oletuspaikka[0]['hyllyvali'];
    $kohde_taso   = $tuotteen_oletuspaikka[0]['hyllytaso'];

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      ${'ale'.$alepostfix} = "";
    }

    if ($varastosiirtotyyppi == "G") {
      $toim = 'SIIRTOLISTA';
    }

    require 'lisaarivi.inc';

    return $lisatyt_rivit1;
  }
}

if (!function_exists('luo_varastosiirto')) {
  function luo_varastosiirto($lahdevarasto_tunnus, $kohdevaraston_tunnus, $toimitustapa_tunnus, $myyntitilaus_tunnus, $varastosiirtotyyppi = 'N') {
    global $kukarow, $yhtiorow;

    $toimitustapa = hae_toimitustapa($toimitustapa_tunnus);
    $toimitustapa = $toimitustapa['selite'];

    $jatka        = 'joo';
    $tilausnumero = 0;
    $clearing     = $kohdevaraston_tunnus;
    $toimpp       = $kerpp = date("j");
    $toimkk       = $kerkk = date("n");
    $toimvv       = $kervv = date("Y");

    if ($varastosiirtotyyppi == "G") {
      $comments   = t("Relex-varastosiirto");
      $viesti     = t("Relex-varastosiirto");

      $_kohdevarasto = hae_varasto($kohdevaraston_tunnus);
      $viesti .= " - {$_kohdevarasto['nimitys']}";
    }
    else {
      $comments   = $kukarow["nimi"]." ".t("Myyntitilaus/varastonsiirto").": {$myyntitilaus_tunnus}";
      $viesti     = $kukarow["nimi"]." ".t("Myyntitilaus/varastonsiirto").": {$myyntitilaus_tunnus}";
    }

    $varasto      = $lahdevarasto_tunnus;
    $toim         = "SIIRTOLISTA";

    // Kirjanpidollinen siirto
    if ($varastosiirtotyyppi == "K") {
      $chn = 'KIR';
    }

    // Generoitu siirto
    if ($varastosiirtotyyppi == "G") {
      $chn = 'GEN';
    }

    $lahdevarasto = hae_varasto($lahdevarasto_tunnus);
    $yhtio_toimipaikka = $lahdevarasto['toimipaikka'];

    require "otsik_siirtolista.inc";

    return hae_lasku($id);
  }
}

if (!function_exists('aseta_varastosiirto_vastaanotetuksi')) {
  function aseta_varastosiirto_vastaanotetuksi($varastosiirto, $varastosiirtorivit, $_poikkeavalaskutuspvm) {
    global $kukarow, $yhtiorow;

    if (empty($varastosiirto) or empty($varastosiirtorivit)) {
      return array();
    }

    $t1                               = $t2 = $t3 = $t4 = $tunnus = array();
    $tee                              = 'paikat';
    $vainlistaus                      = '';
    $echotaanko                       = false;
    $id                               = $varastosiirto['tunnus'];
    $_kirjanpidollinen_varastosiirto  = true;

    foreach ($varastosiirtorivit as &$varastosiirtorivi) {

      $kohde_varasto_tuotepaikat    = hae_tuotteen_tuotepaikat($varastosiirtorivi['tuoteno'], $varastosiirto['clearing']);

      if (empty($kohde_varasto_tuotepaikat)) {
        //Jos kohde varastossa ei ole t‰lle tuotteelle hyllypaikkaa niin luodaan ns. default-paikka
        //$siirtolista['clearing'] = kohdevarasto tunnus
        $kohde_varasto_tuotepaikka = lisaa_tuotepaikka($varastosiirtorivi['tuoteno'], "", "", "", "", "", "", 0, 0, $varastosiirto['clearing']);
      }
      else {
        $kohde_varasto_tuotepaikka = $kohde_varasto_tuotepaikat[0];
      }

      $varastosiirtorivi['kohde_hyllyalue']   = $kohde_varasto_tuotepaikka['hyllyalue'];
      $varastosiirtorivi['kohde_hyllynro']    = $kohde_varasto_tuotepaikka['hyllynro'];
      $varastosiirtorivi['kohde_hyllyvali']   = $kohde_varasto_tuotepaikka['hyllyvali'];
      $varastosiirtorivi['kohde_hyllytaso']   = $kohde_varasto_tuotepaikka['hyllytaso'];

      $tunnus[]                               = $varastosiirtorivi['tunnus'];
      $t1[$varastosiirtorivi['tunnus']]       = $varastosiirtorivi['kohde_hyllyalue'];
      $t2[$varastosiirtorivi['tunnus']]       = $varastosiirtorivi['kohde_hyllynro'];
      $t3[$varastosiirtorivi['tunnus']]       = $varastosiirtorivi['kohde_hyllyvali'];
      $t4[$varastosiirtorivi['tunnus']]       = $varastosiirtorivi['kohde_hyllytaso'];
    }

    require 'vastaanota.php';

    return $varastosiirtorivit;
  }
}

if (!function_exists('linkkaa_myyntitilausrivi_varastosiirtoriviin')) {
  function linkkaa_myyntitilausrivi_varastosiirtoriviin($varastosiirtorivi_tunnus, $myyntitilausirivi_tunnus) {
    global $kukarow, $yhtiorow;

    if (empty($varastosiirtorivi_tunnus) or empty($myyntitilausirivi_tunnus)) {
      return false;
    }

    $query = "UPDATE tilausrivin_lisatiedot
              SET tilausrivilinkki = {$myyntitilausirivi_tunnus}
              WHERE yhtio          = '{$kukarow['yhtio']}'
              AND tilausrivitunnus = {$varastosiirtorivi_tunnus}";
    pupe_query($query);

    return true;
  }
}

if (!function_exists('hae_tilausrivit')) {
  function hae_tilausrivit($otunnus, $varastosiirtotyyppi = 'N', $onko_myyntitilaus = true) {
    global $kukarow, $yhtiorow;

    $tilausrivi_where       = '';
    $varasto_avainsana_join = '';
    $select                 = '';
    $lasku_join             = "";

    if ($varastosiirtotyyppi == 'N') {
      $tilausrivi_where = "AND t.var = 'S'";

      $varasto_avainsana_join = '
          JOIN avainsana AS a
          ON ( a.yhtio = tl.yhtio
            AND a.tunnus = tl.toimittajan_tunnus )
          JOIN varastopaikat AS v_lahdevarasto
          ON ( v_lahdevarasto.tunnus = a.selite )
          JOIN varastopaikat AS v_kohdevarasto
          ON ( v_kohdevarasto.tunnus = a.selitetark )
          LEFT JOIN varaston_hyllypaikat
          ON ( varaston_hyllypaikat.yhtio = t.yhtio
            AND varaston_hyllypaikat.hyllyalue = t.hyllyalue
            AND varaston_hyllypaikat.hyllynro = t.hyllynro
            AND varaston_hyllypaikat.hyllyvali = t.hyllyvali
            AND varaston_hyllypaikat.hyllytaso = t.hyllytaso)';

      $select = ' v_lahdevarasto.tunnus AS lahdevarasto_tunnus,
                  v_kohdevarasto.tunnus AS kohdevarasto_tunnus,
                  a.selitetark_2 AS toimitustapa_tunnus,
                  varaston_hyllypaikat.keraysvyohyke AS keraysvyohyke,
                  tl.ohita_kerays,';
    }
    else {
      //Kirjanpidollinen ja palautus t‰h‰n haaraan
      $tilausrivi_where = " AND t.var not in ('P','J','O','S')";

      if ($varastosiirtotyyppi == 'P') {
        $lasku_join   = " JOIN lasku AS l
                          ON ( l.yhtio = t.yhtio
                            AND l.tunnus = t.otunnus )";
        $select     = "l.varasto AS varasto, ";
        $tilausrivi_where .= " AND tl.palautus_varasto != 0 ";
      }
    }

    if ($onko_myyntitilaus) {
      $tilausrivi_where .= " AND t.tyyppi = 'L'";
    }
    else {
      $tilausrivi_where .= " AND t.tyyppi = 'G'";
    }

    $query   = "SELECT t.*,
                {$select}
                t.hyllyalue AS lahde_hyllyalue,
                t.hyllynro AS lahde_hyllynro,
                tl.palautus_varasto AS palautus_varasto,
                tl.toimittajan_tunnus,
                tl.tilausrivitunnus AS tilausrivitunnus,
                tl.tilausrivilinkki AS tilausrivilinkki,
                tl.kohde_hyllyalue AS kohde_hyllyalue,
                tl.kohde_hyllynro AS kohde_hyllynro,
                tl.kohde_hyllytaso AS kohde_hyllytaso,
                tl.kohde_hyllyvali AS kohde_hyllyvali
                FROM tilausrivi AS t
                {$lasku_join}
                JOIN tuote
                ON ( tuote.yhtio = t.yhtio
                  AND tuote.tuoteno       = t.tuoteno
                  AND tuote.ei_saldoa     = '')
                JOIN tilausrivin_lisatiedot AS tl
                ON (tl.yhtio = t.yhtio
                  AND tl.tilausrivitunnus = t.tunnus )
                {$varasto_avainsana_join}
                WHERE t.yhtio             = '{$kukarow['yhtio']}'
                AND t.otunnus             = {$otunnus}
                {$tilausrivi_where}
                ORDER BY t.perheid, t.tunnus";
    $result = pupe_query($query);

    $tilausrivit = array();

    while ($tilausrivi = mysql_fetch_assoc($result)) {
      $tilausrivit[] = $tilausrivi;
    }

    return $tilausrivit;
  }
}

if (!function_exists('paivita_myyntitilausrivien_tuotepaikat')) {
  function paivita_myyntitilausrivien_tuotepaikat($tilausrivit, $onko_varastosiirtorivi = true) {
    global $kukarow, $yhtiorow;

    if (empty($tilausrivit)) {
      return false;
    }

    foreach ($tilausrivit as $tilausrivi) {

      $update = "AND tunnus = {$tilausrivi['tunnus']}";
      if ($onko_varastosiirtorivi) {
        $update = "AND tunnus = {$tilausrivi['tilausrivilinkki']}";
      }

      $query = "UPDATE tilausrivi SET
                hyllyalue   = '{$tilausrivi['kohde_hyllyalue']}',
                hyllynro    = '{$tilausrivi['kohde_hyllynro']}',
                hyllytaso   = '{$tilausrivi['kohde_hyllytaso']}',
                hyllyvali   = '{$tilausrivi['kohde_hyllyvali']}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                {$update}";
      pupe_query($query);
    }

    return true;
  }
}

if (!function_exists('linkkaa_varastosiirto_myyntitilaukseen')) {
  function linkkaa_varastosiirto_myyntitilaukseen($varastosiirto, $myyntitilaus) {
    global $kukarow, $yhtiorow;

    if (empty($varastosiirto) or empty($myyntitilaus)) {
      return false;
    }

    // T‰ll‰ merkataan jos ollaan tehty KIRJANPIDOLLINEN varastosiirto.
    $query = "UPDATE lasku SET
              varastosiirto_tunnus = {$varastosiirto['tunnus']}
              WHERE yhtio          = '{$kukarow['yhtio']}'
              AND tunnus           = {$myyntitilaus['tunnus']}";
    pupe_query($query);

    return true;
  }
}

if (!function_exists('hae_avoin_varastosiirto')) {
  function hae_avoin_varastosiirto($lahdevarasto_tunnus, $kohdevarasto_tunnus, $varastosiirtotyyppi = 'N') {
    global $kukarow, $yhtiorow;

    if (empty($lahdevarasto_tunnus) or empty($kohdevarasto_tunnus)) {
      return false;
    }

    $lisa = "";

    if ($varastosiirtotyyppi == "G") {
      $lisa = " and chn = 'GEN' ";
    }

    $query   = "SELECT *
                FROM lasku
                WHERE yhtio  = '{$kukarow['yhtio']}'
                AND tila     = 'G'
                AND alatila  = ''
                {$lisa}
                AND varasto  = '{$lahdevarasto_tunnus}'
                AND clearing = '{$kohdevarasto_tunnus}'";
    $result   = pupe_query($query);

    return mysql_fetch_assoc($result);
  }
}
