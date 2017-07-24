<?php

// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow array, $yhtiorow array
$tilaus_valmis_ulos     = "";
$ohjausmerkkilisa       = "";
$luottorajavirhe        = "";
$ylivito                = 0;
$jtkielto               = "";
$myy_varastosta_varasto = $laskurow['varasto'];

///* Reload ja back-nappulatsekki *///
if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
  echo "<font class='error'> ".t("Taisit painaa takaisin tai p‰ivit‰ nappia. N‰in ei saa tehd‰")."! </font>";
  exit;
}

// k‰ytet‰‰n laskun varastoa jos se on, muuten varasto alkuper‰iselt‰ tilaukselta
$vanha_varasto = empty($alkuperainen_varasto) ? 0 : $alkuperainen_varasto;
$_varasto      = empty($laskurow['varasto']) ? $vanha_varasto : $laskurow['varasto'];

if ($_varasto != 0 and $yhtiorow['toimipaikkakasittely'] == 'L') {
  $query = "SELECT toimipaikka
            FROM varastopaikat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = {$_varasto}";
  $_tp_res = pupe_query($query);
  $_tp_row = mysql_fetch_assoc($_tp_res);

  if ($_tp_row['toimipaikka'] != $laskurow['yhtio_toimipaikka']) {
    $kukarow['toimipaikka'] = $_tp_row['toimipaikka'];
    $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
  }
}

if ($laskurow['tila'] == 'N' and $laskurow['alatila'] == '') {
  //Kampanjoiden tarkistus ja tuotteiden lis‰ys jos osuu johonkin.
  tarkista_osuuko_myyntitilaus_kampanjaan($laskurow['tunnus']);
}

//jos tilaustyyppi on 2 ja ohjausmerkki on tyhj‰‰, niin pistet‰‰n ohjausmerkiksi tilaustyypin selkokielinen nimi
if ($laskurow['tilaustyyppi'] == "2" and trim($laskurow['ohjausmerkki']) == "") {
  $ohjausmerkkilisa = ", ohjausmerkki = 'Varastot‰ydennys'";
  $laskurow["ohjausmerkki"] = "Varastot‰ydennys";
}

// p‰ivitet‰‰n jtkielto jos jaksotettu, niin ei vahingossakaan lis‰t‰ jt-rivej‰ v‰‰rille otsikoille esim editilauksiin jt_automatiikan kautta.
if ($laskurow['jaksotettu'] > 0) {
  $jtkielto = ", jtkielto = 'Y'";
  $laskurow["jtkielto"] = "Y";
}

// Luodaan sadanprossan ennakkolasku
if (!empty($tee_100_ennakkolasku) or !empty($tee_sis_100_ennakkolasku)) {

  $sisainen = FALSE;

  if (!empty($tee_sis_100_ennakkolasku)) {
    $sisainen = TRUE;
  }

  $maksuehto = tee_ennakkolasku($laskurow['tunnus'], $sisainen);

  if ($maksuehto) {
    $laskurow['maksuehto'] = $maksuehto;
  }

  $lopetus="";
}

// p‰ivitet‰‰n vanhatunnus ja v‰h‰n muutakin laskulle
$query  = "UPDATE lasku SET vanhatunnus=tunnus $ohjausmerkkilisa $jtkielto
           WHERE yhtio     = '$kukarow[yhtio]'
           AND tunnus      = '$laskurow[tunnus]'
           AND vanhatunnus = 0";
$oldtur = pupe_query($query);

$laskurow['ohjausmerkki'] = paivita_ohjausmerkki($laskurow);

// p‰ivitet‰‰n myˆs muuttuja
$laskurow["vanhatunnus"] = $laskurow["tunnus"];

// Katsotaan onko asiakas myyntikiellossa
$query  = "SELECT *
           FROM asiakas
           WHERE yhtio = '$kukarow[yhtio]'
           AND tunnus  = '$laskurow[liitostunnus]'";
$tilausvalmis_asiakas_res = pupe_query($query);
$tilausvalmis_asiakas_row = mysql_fetch_assoc($tilausvalmis_asiakas_res);

// otetaan maksuehto t‰ss‰ heti alussa selville.. k‰teinen ja itsetulostus muuttaa asioita
$query = "SELECT *
          FROM maksuehto
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tunnus  = '{$laskurow['maksuehto']}'";
$maksuehto_result = pupe_query($query);
$maksuehtorow = mysql_fetch_assoc($maksuehto_result);

// Myynti estete‰‰n jos luottoraja on ylitetty (K - kaikki estet‰‰n, E -  kaikki muu ku myyjien tekem‰ estet‰‰n)
// Jos maksuehto on k‰teinen, sallitaan luottorajan ylitys
// samma pÂ erapaivan_ylityksen_raja

// Jos tilaus on jo ker‰tty/toimitettu, niin ei pys‰ytet‰ sit‰ vaikka sit‰ muokataankin
$_keratty_toimitettu = ($laskurow["tila"] == "L" and in_array($laskurow["alatila"], array("B", "C", "D", "E")));

$luottoraja_chk = ((in_array($yhtiorow["luottorajan_ylitys"], array("K", "J", "L", "M"))
    or (in_array($yhtiorow["luottorajan_ylitys"], array("E", "F"))
      and ($kukarow["extranet"] != ""
        or $tilausvalmiskutsuja != "TILAUSMYYNTI")))
  and !$_keratty_toimitettu
  and $tilausvalmis_asiakas_row['luottoraja'] != 0
  and $maksuehtorow['kateinen'] == '' and empty($maksuehtorow['jv'])
  and strpos($_SERVER['SCRIPT_NAME'], "korkolasku.php") === FALSE) ? 1 : 0;

$erapaiva_chk   =  ($maksuehtorow['kateinen'] == ''
  and empty($maksuehtorow['jv'])
  and !$_keratty_toimitettu
  and (in_array($yhtiorow["erapaivan_ylityksen_toimenpide"], array("K", "J", "L", "M"))
    or (in_array($yhtiorow["erapaivan_ylityksen_toimenpide"], array("E", "F"))
      and ($kukarow["extranet"] != ""
        or $tilausvalmiskutsuja != "TILAUSMYYNTI")))) ? 1 : 0;

// tarkistetaan ettei kyseess‰ ole hyvitys
if ($luottoraja_chk or $erapaiva_chk) {

  $query_ale_lisa = generoi_alekentta('M');

  $query = "SELECT SUM(
            tilausrivi.hinta * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}
            ) kaikkiyhteensa_chk
            FROM tilausrivi
            WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
            AND tilausrivi.tyyppi  != 'D'
            AND tilausrivi.var     != 'P'
            AND tilausrivi.otunnus  = '{$laskurow['tunnus']}'";
  $chk_kaikkiyhteensa_res = pupe_query($query);
  $chk_kaikkiyhteensa_row = mysql_fetch_assoc($chk_kaikkiyhteensa_res);

  // nollataan luottoraja_chk ja er‰p‰iv‰_chk hyvityksiss‰, koska hyvitykset saa menn‰ l‰pi
  // luottorajan ja er‰p‰iv‰n ylityksen tarkistuksesta
  if ($chk_kaikkiyhteensa_row['kaikkiyhteensa_chk'] < 0) {
    $luottoraja_chk = 0;
    $erapaiva_chk = 0;
  }
}

if ($luottoraja_chk or $erapaiva_chk) {

  // Parametrej‰ saatanat.php:lle
  $sytunnus        = $laskurow['ytunnus'];
  $sliitostunnus   = $laskurow['liitostunnus'];
  $eiliittymaa     = "ON";
  $luottorajavirhe = "";
  $jvvirhe         = "";
  $ylivito         = 0;
  $trattavirhe     = "";
  $laji            = "MA";
  $grouppaus       = ($yhtiorow["myyntitilaus_saatavat"] == "Y") ? "ytunnus" : "";

  ob_start();

  if ($toim != "EXTRANET" and $toim != "EXTENNAKKO") {
    require "raportit/saatanat.php";
  }
  else {
    require "saatanat.php";
  }

  ob_end_clean();

  if ($luottoraja_chk == 0) { $luottorajavirhe = ""; }
  if ($erapaiva_chk   == 0) { $ylivito         = 0; }
}

$query = "SELECT tilausrivi.tunnus as tilausrivitunnus,
          tilausrivi.kommentti,
          tilausrivin_lisatiedot.rekisterinumero
          FROM tilausrivi
          JOIN tilausrivin_lisatiedot
          ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
            AND tilausrivin_lisatiedot.tilausrivitunnus  = tilausrivi.tunnus
            AND tilausrivin_lisatiedot.rekisterinumero  != '' )
          WHERE tilausrivi.yhtio                         = '{$kukarow['yhtio']}'
          AND tilausrivi.otunnus                         = '{$laskurow['tunnus']}'";
$rekisterinumero_result = pupe_query($query);

while ($rekisterinumero_row = mysql_fetch_assoc($rekisterinumero_result)) {
  $kommentti = "";
  if ($rekisterinumero_row['kommentti'] != "") {
    $kommentti .= "{$rekisterinumero_row['kommentti']}\n";
  }

  if (!stristr($kommentti, $rekisterinumero_row['rekisterinumero'])) {
    $kommentti .= $rekisterinumero_row['rekisterinumero'];
  }

  $query = "UPDATE tilausrivi
            SET kommentti = '{$kommentti}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$rekisterinumero_row['tilausrivitunnus']}'";
  pupe_query($query);
}

if ($ylivito > 0 and in_array($yhtiorow['erapaivan_ylityksen_toimenpide'], array('J', 'L'))) {
  $luottorajavirhe_ylivito_valmis = false;
}
elseif (!empty($luottorajavirhe) and in_array($yhtiorow['luottorajan_ylitys'], array('J', 'L'))) {
  $luottorajavirhe_ylivito_valmis = false;
}
else {
  $luottorajavirhe_ylivito_valmis = true;
}

// Jos ennakkolaskun automatisointi on p‰‰ll‰ (asiakas > yhtiˆn oletus) ja maksuehtona on "osamaksu", laitetaan tilaus holdiin
if ((!isset($vapauta_tilaus_keraykseen) or !$vapauta_tilaus_keraykseen)
  and $maksuehtorow['jaksotettu'] != ''
  and $laskurow['tila'] == 'N'
  and ($laskurow['alatila'] == '' or $laskurow['alatila'] == 'B')
  and vapauta_maksusopimus_kysely($laskurow['jaksotettu']) == 0
  and ($tilausvalmis_asiakas_row['maksusopimus_toimitus'] == 'X' or ($tilausvalmis_asiakas_row['maksusopimus_toimitus'] == '' and $yhtiorow['maksusopimus_toimitus'] == 'X'))) {

  if ($laskurow['alatila'] == '') {
    $query = "UPDATE lasku SET
              alatila     = 'B'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$laskurow['tunnus']}'
              AND tila    = 'N'";
    $myyntikielto_res = pupe_query($query);

    echo "<font class='error'>", t("Tilaus laitetaan odottamaan, kunnes ennakkolaskut ovat maksettu"), ".</font><br/><br/>";
  }

  // Laitetaan lopetus tyhj‰ksi, niin ei refreshata ruutua jos tultiin muokkauksesta tms.
  $lopetus = "";
}
elseif ($luottorajavirhe != '' or $ylivito > 0) {

  // nollataan toimitustavan_lahto
  $toimitustavan_lahto_update = "";

  // Jos myyj‰t saa hyv‰ksy‰, mutta EDI/EXTRANET ei mee l‰pi jos luottotiedot on kuralla niin laitetaan F-alatilaan.
  // Jos kukaan ei saa myyd‰ kun luottotiedot on kuralla niin sillo laitetaan kaikki G-alatilaan
  if (($tilausvalmiskutsuja == "EDITILAUS" or $tilausvalmiskutsuja == "VERKKOKAUPPA" or $kukarow["extranet"] != "") and (($luottorajavirhe != '' and in_array($yhtiorow["luottorajan_ylitys"], array("E", "F"))) or ($ylivito > 0 and in_array($yhtiorow["erapaivan_ylityksen_toimenpide"], array("E", "F"))))) {

    // laitetaan tilaan Odottaa hyv‰ksynt‰‰ tai kesken
    if ($yhtiorow["luottorajan_ylitys"] == "F" or $yhtiorow["erapaivan_ylityksen_toimenpide"] == "F") {
      $alatila = "";
    }
    else {
      $alatila = "F";
    }
  }
  elseif ($toim == "REKLAMAATIO") {
    $alatila = "";
  }
  else {

    // laitetaan tilaan Odottaa asiakkaan suoritusta tai kesken
    // jos kuitenkin oltiin jo hyv‰ksym‰ss‰ tilausta, j‰tet‰‰n hyv‰ksytt‰v‰ksi
    if ($laskurow['alatila'] == 'F') {
      $alatila = "F";
    }
    elseif ($yhtiorow["luottorajan_ylitys"] == "J" or $yhtiorow["erapaivan_ylityksen_toimenpide"] == "J" or $yhtiorow["luottorajan_ylitys"] == "L" or $yhtiorow["erapaivan_ylityksen_toimenpide"] == "L") {
      $alatila = "";
    }
    else {
      $alatila = "G";
    }
  }

  // Tarkastetaan onko myyntitilaus naitettu ostoon.
  $query = "SELECT group_concat(DISTINCT tilausrivi.otunnus) ostotilaukset
            FROM tilausrivin_lisatiedot
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio
              AND tilausrivi.tunnus                      = tilausrivin_lisatiedot.tilausrivilinkki
            )
            WHERE tilausrivin_lisatiedot.yhtio           = '{$kukarow['yhtio']}'
            AND tilausrivin_lisatiedot.vanha_otunnus     = '{$laskurow['tunnus']}'
            AND tilausrivin_lisatiedot.tilausrivilinkki != 0
            LIMIT 1";
  $suoratoimitus_result = pupe_query($query);
  $tilausrivin_lisatieto_row = mysql_fetch_assoc($suoratoimitus_result);

  if ($tilausrivin_lisatieto_row['ostotilaukset'] != "") {
    $query = "UPDATE lasku
              SET alatila = 'G'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  IN ({$tilausrivin_lisatieto_row['ostotilaukset']})
              AND tila    = 'O'
              AND alatila = ''";
    pupe_query($query);
  }

  if ($toim == "REKLAMAATIO" and !$luottorajavirhe_ylivito_valmis) {
    $tilaupd = "tila = 'C',";
  }
  else {
    $tilaupd = "";
  }

  $query = "UPDATE lasku SET
            alatila             = '$alatila',
            {$tilaupd}
            toimitustavan_lahto = 0
            WHERE yhtio         = '$kukarow[yhtio]'
            AND tunnus          = '$laskurow[tunnus]'";
  $myyntikielto_res = pupe_query($query);

  $errortext = "";

  if ($luottorajavirhe != "") {
    $errortext = t("Asiakkaan luottoraja on ylitetty") . ". ";
  }

  if ($ylivito > 0) {
    $errortext .= t("Asiakkaalla on yli %s pv sitten er‰‰ntyneit‰ laskuja", $kukarow['kieli'], $yhtiorow['erapaivan_ylityksen_raja']) . ". ";
  }

  // Laitetaan lopetus tyhj‰ksi, niin ei refreshata ruutua jos tultiin muokkauksesta tms.
  $lopetus = "";

  if ($toim == "REKLAMAATIO") {
    $tee        = '';

    if ($luottorajavirhe_ylivito_valmis) {
      $tilausnumero    = '';
      $laskurow      = '';
      $kukarow['kesken']  = '';
    }

    echo "<font class='error'>" . $errortext . t("Reklamaatiota ei merkit‰ valmiiksi"), ".</font><br/><br/>";
  }
  else {
    if ($yhtiorow['kerayserat'] == 'K' and isset($toimitustavan_lahto) and is_string($toimitustavan_lahto)) {
      $toimitustavan_lahto = unserialize(urldecode($toimitustavan_lahto));
    }

    echo "<font class='error'>" . $errortext . t("Tilausta ei merkit‰ valmiiksi"), ".</font><br/><br/>";
  }
}
elseif ($tilausvalmis_asiakas_row['myyntikielto'] == 'K' and $laskurow["clearing"] != "KORKOLASKU") {
  $query = "UPDATE lasku SET
            alatila     = ''
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$laskurow[tunnus]'";
  $myyntikielto_res = pupe_query($query);

  echo "<font class='error'>", t("Asiakas on myyntikiellossa"), ". ", t("Tilausta ei merkit‰ valmiiksi"), ".</font><br/>";

  // Laitetaan lopetus tyhj‰ksi, niin ei refreshata ruutua jos tultiin muokkauksesta tms.
  $lopetus = "";
}
elseif ($laskurow["tilaustyyppi"] == 'E') {
  //Ennakkotilaukset

  //tila on E ja alatila on A kun on ennakkotilaus
  $query  = "UPDATE lasku
             SET tila = 'E',
             alatila   = 'A'
             WHERE tunnus='$laskurow[tunnus]'
             and tila  in ('N','L','E')
             and yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  $laskurow["tila"] = "E";
  $laskurow["alatila"] = "A";

  // ennakkotilauksissa tyyppi on E ja rivit ei ole ennakkopoistossa
  $query = "UPDATE tilausrivi
            SET tyyppi  = 'E'
            WHERE yhtio = '$kukarow[yhtio]'
            and otunnus = '$laskurow[tunnus]'
            and tyyppi  in ('L','E')";
  $result = pupe_query($query);

  if (trim($laskurow['tilausvahvistus']) != "") {
    //
    // LƒHETETƒƒN TILAUSVAHVISTUS
    //
    // tulostetaan t‰ss‰, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin
    $params_tilausvahvistus = array(
      'tee'            => $tee,
      'toim'            => $toim,
      'kieli'            => $kieli,
      'komento'          => $komento,
      'laskurow'          => $laskurow,
      'naytetaanko_rivihinta'    => $naytetaanko_rivihinta,
      'extranet_tilausvahvistus'  => $extranet_tilausvahvistus,
    );

    laheta_tilausvahvistus($params_tilausvahvistus);
  }
}
elseif ($laskurow["tilaustyyppi"] == '0') {
  // Yll‰pitosopimus
  // tila on 0 ja alatila on V
  $query  = "UPDATE lasku
             SET tila = '0',
             alatila      = 'V'
             WHERE yhtio  = '$kukarow[yhtio]'
             and tunnus   = '$laskurow[tunnus]'
             and tila     in ('N','L','0')
             and alatila != 'X'";
  $result = pupe_query($query);

  // tyyppi on 0 ja rivit ei ole ennakkopoistossa
  $query = "UPDATE tilausrivi
            SET tyyppi  = '0'
            WHERE yhtio = '$kukarow[yhtio]'
            and otunnus = '$laskurow[tunnus]'
            and tyyppi  in ('L','0')";
  $result = pupe_query($query);
}
elseif ($laskurow["tila"] == 'T') {
  //Tarjoukset

  //tila on T kun kyseess‰ on tarjous
  $query  = "UPDATE lasku
             SET tila = 'T',
             alatila = 'A'
             WHERE tunnus='$laskurow[tunnus]'
             and tila ='T'
             and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  // tarjouksissa tyyppi on T ja rivit ei ole ennakkopoistossa
  $query = "UPDATE tilausrivi
            SET tyyppi  = 'T'
            WHERE yhtio = '$kukarow[yhtio]'
            and otunnus = '$laskurow[tunnus]'
            and tyyppi  in ('L','T')";
  $result = pupe_query($query);
}
elseif ($kukarow["extranet"] == "" and $toim == "REKLAMAATIO" and $tee == "ODOTTAA" and $yhtiorow['reklamaation_kasittely'] == 'U') {

  // Reklamaatio p‰ivitet‰‰n tilaan 'odottaa tuotteita'
  $query = "UPDATE lasku set
            alatila     = 'A'
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$tilausnumero'
            AND tila    = 'C'";
  $result = pupe_query($query);

  $query  = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and kesken = '$tilausnumero'";
  $result = pupe_query($query);

  if ($laskurow['tilaustyyppi'] == 'U') {
    echo "<font class='message'>".t("Takuu: %s siirretty odottamaan tuotteita", '', $tilausnumero).".</font><br><br>";
  }
  else {
    echo "<font class='message'>".t("Reklamaatio: %s siirretty odottamaan tuotteita", '', $tilausnumero).".</font><br><br>";
  }

  $tee        = '';
  $tilausnumero    = '';
  $laskurow      = '';
  $kukarow['kesken']  = '';

  if ($lopetus != '') {
    lopetus($lopetus, "META");
  }
}
else {
  // T‰ss‰ on normaalit tilaukset

  // Luodaan valituista riveist‰ suoraan normaali ostotilaus
  if (($kukarow["extranet"] == "" or ($kukarow['extranet'] != '' and $yhtiorow['tuoteperhe_suoratoimitus'] == 'E')) and $yhtiorow["tee_osto_myyntitilaukselta"] != '') {
    require "tilauksesta_ostotilaus.inc";

    // Jos halutaan tehd‰ tilauksesta ostotilauksia, niin tehd‰‰n kaikista ostotilaus
    if ($tee_osto != "") {
      $tilauksesta_ostotilaus = tilauksesta_ostotilaus($kukarow["kesken"], 'KAIKKI');

      // P‰ivitet‰‰n tilaukselle, ett‰ sit‰ ei osatoimiteta jos koko tilauksesta tehtiin ostotilaus
      $query  = "UPDATE lasku set
                 osatoimitus = 'o'
                 where yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$kukarow[kesken]'";
      $result = pupe_query($query);
    }
    else {
      $tilauksesta_ostotilaus  = tilauksesta_ostotilaus($kukarow["kesken"], 'T');
      $tilauksesta_ostotilaus .= tilauksesta_ostotilaus($kukarow["kesken"], 'U');
    }

    if ($tilauksesta_ostotilaus != '') echo "$tilauksesta_ostotilaus<br><br>";
  }

  if ($kukarow["extranet"] == "" and $yhtiorow["tee_valmistus_myyntitilaukselta"] != '') {
    //  Voimme myˆs tehd‰ tilaukselta suoraan valmistuksia!
    require "tilauksesta_valmistustilaus.inc";

    $tilauksesta_valmistustilaus = tilauksesta_valmistustilaus($kukarow["kesken"]);
    if ($tilauksesta_valmistustilaus != '') echo "$tilauksesta_valmistustilaus<br><br>";
  }

  //Tarkistetaan onko myyntitilaukselle tehty varastosiirto ja jos ei ole niin tehd‰‰n
  $_tehdaan_varastosiirto = false;

  if ($kukarow["extranet"] == "" and $yhtiorow["tee_siirtolista_myyntitilaukselta"] == 'K' and $laskurow['tila'] == 'N') {
    $query = "SELECT tilausrivi.tunnus
              FROM tilausrivi
              WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
              AND tilausrivi.otunnus = {$laskurow['tunnus']}
              AND tilausrivi.var     = 'S'";
    $varastosiirto_result = pupe_query($query);

    if (mysql_num_rows($varastosiirto_result) > 0) {
      $_tehdaan_varastosiirto = true;
    }
  }

  if ($_tehdaan_varastosiirto) {
    require 'tilauksesta_varastosiirto.inc';

    tilauksesta_varastosiirto($laskurow['tunnus'], 'N');
  }

  // Jos meill‰ ei ole ker‰ysprioriteettia t‰ll‰ tilauksella,
  // katsotaan onko t‰lle prioriteetti tuoteryhm‰n takaa.
  if ($laskurow["hyvaksynnanmuutos"] == "") {

    $keraysprioriteetti_query = "SELECT avainsana.selitetark_4
                                 FROM tilausrivi
                                 JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
                                   AND tuote.tuoteno           = tilausrivi.tuoteno)
                                 JOIN avainsana ON (avainsana.yhtio = tilausrivi.yhtio
                                   AND avainsana.laji          = 'TRY'
                                   AND avainsana.selitetark_4 != ''
                                   AND avainsana.selite        = tuote.try)
                                 WHERE tilausrivi.yhtio        = '{$kukarow["yhtio"]}'
                                 AND tilausrivi.otunnus        = '{$laskurow["tunnus"]}'
                                 AND tilausrivi.var            not in ('P','J','O','S')
                                 ORDER by avainsana.selitetark_4 ASC
                                 LIMIT 1";
    $keraysprioriteetti_res = pupe_query($keraysprioriteetti_query);

    // p‰ivitet‰‰n prioriteetti laskulle
    if (mysql_num_rows($keraysprioriteetti_res) == 1) {
      $keraysprioriteetti_row = mysql_fetch_assoc($keraysprioriteetti_res);
      $keraysprioriteetti_query  = "UPDATE lasku SET
                                    hyvaksynnanmuutos = '{$keraysprioriteetti_row["selitetark_4"]}'
                                    WHERE yhtio       = '{$kukarow["yhtio"]}'
                                    AND tunnus        = '{$laskurow["tunnus"]}'";
      $keraysprioriteetti_res = pupe_query($keraysprioriteetti_query);
    }
  }

  // katotaan ollaanko valittu joku poikkeava kassalipas
  if ($kertakassa != '') {

    // Ohitetaan kassamyynti
    if ($kertakassa == 'EI_KASSAMYYNTIA') {
      $kertakassa = "";
    }

    $query = "UPDATE lasku SET kassalipas = '$kertakassa' where yhtio='$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]'";
    $result = pupe_query($query);

    $laskurow['kassalipas'] = $kertakassa;
  }

  if ($laskurow["tila"] == 'N' and ($laskurow["alatila"] == 'U' or $laskurow["alatila"] == '') and $laskurow["osatoimitus"] != '') {
    // Osatoimitus kielletty, mutta kaikki rivit toimitettavissa
    $query = "SELECT *
              from tilausrivi
              where yhtio  = '$kukarow[yhtio]'
              and otunnus  = '$laskurow[tunnus]'
              and keratty  = ''
              and var      = 'J'
              and tyyppi  != 'D'
              and varattu  > 0";
    $keres = pupe_query($query);

    if (mysql_num_rows($keres) == 0) {
      //Muutetaan tulostettavaksi
      $query = "UPDATE lasku SET alatila = '' where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]'";
      $result = pupe_query($query);
      $laskurow["alatila"] = "";
      $laskurow["osatoimitus"] = "";
    }
  }
  // Aktivoituiko "Odottaa jt-rivej‰" tilaus?
  elseif (($laskurow["tila"] == 'N' or $laskurow["tila"] == 'G') and ($laskurow["alatila"] == 'T' or $laskurow["alatila"] == 'U')) {
    // Onko jotain tulostettavaa?
    $query = "SELECT *
              from tilausrivi
              where yhtio  = '$kukarow[yhtio]'
              and otunnus  = '$laskurow[tunnus]'
              and keratty  = ''
              and var      not in ('P','J','O','S')
              and tyyppi  != 'D'
              and varattu  > 0";
    $keres = pupe_query($query);

    if (mysql_num_rows($keres) > 0) {
      //Muutetaan tulostettavaksi
      $query = "UPDATE lasku SET alatila = '' where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]'";
      $result = pupe_query($query);
      $laskurow["alatila"] = "";
      $laskurow["osatoimitus"] = "";
    }
  }

  // Tarkistetaan onko ensimm‰inen kerta kun tilaus klikataan valmiiksi
  $tsekkitsekki = ($laskurow['hyvak1'] == '' and $laskurow['h1time'] == '0000-00-00 00:00:00') ? TRUE : FALSE;
  if ($tsekkitsekki and $yhtiorow["lisakulu_tuotenumero"] != "") {
    $query = "SELECT *
              FROM toimitustapa
              WHERE yhtio = '$kukarow[yhtio]'
              AND selite  = '$laskurow[toimitustapa]'";
    $result = pupe_query($query);
    $torow = mysql_fetch_assoc($result);

    // jos toimitustavalle on setattu lis‰kuluraja, lis‰kulu sek‰ yhtiˆll‰ on lis‰kulun tuotenumero
    if ($torow['lisakulu_summa'] != 0 and $torow['lisakulu'] != 0) {

      $query = "SELECT tunnus
                FROM tilausrivi
                WHERE yhtio  = '{$kukarow['yhtio']}'
                AND otunnus  = '{$laskurow['tunnus']}'
                AND tyyppi  != 'D'
                AND tuoteno  = '{$yhtiorow['lisakulu_tuotenumero']}'";
      $result = pupe_query($query);

      $query = "SELECT *
                FROM tuote
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$yhtiorow['lisakulu_tuotenumero']}'";
      $rhire = pupe_query($query);

      $query_ale_lisa = generoi_alekentta('M');

      $tilausriviquery = "SELECT tilausrivi.tuoteno, (tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt) * $query_ale_lisa) rivihinta, (tilausrivi.varattu+tilausrivi.jt) kplmaara
                          FROM tilausrivi
                          WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                          AND tilausrivi.otunnus  = '$laskurow[tunnus]'
                          AND tilausrivi.tyyppi  != 'D'";
      $rivihintaresult = pupe_query($tilausriviquery);

      $lisakulu_alarajasumma_rivit = 0;

      while ($totaalirivi = mysql_fetch_assoc($rivihintaresult)) {
        $lisakulu_alarajasumma_rivit += $totaalirivi["rivihinta"];
      }

      // lis‰t‰‰n toimituslis‰kulu jos toimituslis‰kulua ei ole tilauksella ja lis‰kulutuote lˆytyy JA rajasumma ei t‰yty
      if (mysql_num_rows($rhire) == 1 and mysql_num_rows($result) == 0 and $lisakulu_alarajasumma_rivit != 0 and ($lisakulu_alarajasumma_rivit < $torow['lisakulu_summa'])) {

        $trow = mysql_fetch_assoc($rhire);

        $otunnus = $laskurow['tunnus'];
        $hinta   = $torow['lisakulu'];
        $nimitys = $trow['nimitys'] != "" ? $trow['nimitys'] : t("Toimitustavan lis‰kulu");

        list($hinta, $alv) = alv($laskurow, $trow, $hinta, '', '');

        $query  = "INSERT into tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
                   values (now(), '{$hinta}', '{$netto}', '1', '1', '{$otunnus}', '{$trow['tuoteno']}', '{$nimitys}', '{$kukarow['yhtio']}', 'L', '{$alv}', '')";
        $addtil = pupe_query($query);

        $hinta = money_format('%.2n', $hinta);

        $tilaus_valmis_ulos .= t("Tilaukselle lis‰ttiin toimitustavan lis‰kulua").": {$hinta} {$laskurow['valkoodi']}<br>";
      }
    }
  }
  //p‰ivitet‰‰n tilaus valmisaika, kun painetaan ekan kerran tilaus valmiiksi
  if ($laskurow['h1time'] == "0000-00-00 00:00:00") {
    $query = "UPDATE lasku
              SET h1time = now(),
              hyvak1      = '$kukarow[kuka]'
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$laskurow[tunnus]'";
    $result = pupe_query($query);

    $laskurow['h1time'] = date('Y-m-d H:i:s');
    $laskurow['hyvak1'] = $kukarow["kuka"];
  }

  //p‰ivitet‰‰n tilaukselle kuka tilauksen hyv‰ksyi ja milloin
  if ($laskurow['tila'] == "N" and $laskurow['alatila'] == "F") {
    $query = "UPDATE lasku
              SET h2time = now(),
              hyvak2      = '$kukarow[kuka]'
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$laskurow[tunnus]'";
    $result = pupe_query($query);

    $laskurow['h2time'] = date('Y-m-d H:i:s');
    $laskurow['hyvak2'] = $kukarow["kuka"];

    // laitetaan EXT-tilauksen myyjaksi tilauksen valmiiksi klikkaaja
    if ($yhtiorow['ext_tilauksen_hyvaksyja_myyjaksi'] == 'X' and strrpos($lopetus, "toim=EXTRANET") !== FALSE) {
      $query = "UPDATE lasku
                SET myyja = '$kukarow[tunnus]'
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$laskurow[tunnus]'";
      $result = pupe_query($query);

      $laskurow['myyja'] = $kukarow["tunnus"];
    }
  }

  $kateinen = "";
  $jaksotettu = "";
  $itsetulostus = "";

  if (mysql_num_rows($maksuehto_result) == 1) {

    // jos kyseess‰ on k‰teiskauppaa
    if ($maksuehtorow['kateinen'] != '') {
      $kateinen = "X";
    }
    // jos kyseess‰ on jaksotusta
    if ($maksuehtorow['jaksotettu'] != '') {
      $jaksotettu = "X";
    }
    //jos kyseess‰ on itsetulostusmaksuehto
    if ($maksuehtorow['itsetulostus'] != '') {
      $itsetulostus = "X";
    }
  }

  if ($yhtiorow["kerataanko_saldottomat"] == '') {
    $lisasalker = " and tuote.ei_saldoa = '' ";
  }
  else {
    $lisasalker = " ";
  }

  // Jos otsikko on ker‰tty tai pidemm‰ll‰
  if ($laskurow["tila"] == "L" and in_array($laskurow["alatila"], array('C', 'D', 'J', 'E'))) {
    $query = "UPDATE tilausrivi
              SET keratty = '$kukarow[kuka]', kerattyaika = now()
              WHERE yhtio  = '$kukarow[yhtio]'
              and otunnus  = '$laskurow[tunnus]'
              and keratty  = ''
              and var      not in ('P','J','O','S')
              and tyyppi  != 'D'";
    $result = pupe_query($query);

    // Laitetaan rivit toimitetuiksi jos otsikko sanoo toimitettu
    if ($laskurow["alatila"] == 'D' or $laskurow["alatila"] == 'J' or $laskurow["alatila"] == 'E') {
      $query = "UPDATE tilausrivi
                SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
                WHERE yhtio     = '$kukarow[yhtio]'
                and otunnus     = '$laskurow[tunnus]'
                and keratty    != ''
                and toimitettu  = ''
                and var         not in ('P','J','O','S')
                and tyyppi     != 'D'";
      $result = pupe_query($query);
    }

    if (!empty($sahkoinen_lahete) and isset($generoi_sahkoinen_lahete) and trim($generoi_sahkoinen_lahete) != "" and $kukarow["extranet"] == "" and !empty($sahkoinen_lahete_toim) and in_array($toim, $sahkoinen_lahete_toim)) {

      require_once "inc/sahkoinen_lahete.class.inc";

      sahkoinen_lahete($laskurow);
    }

    $tilaus_valmis_ulos .= "<font class='message'>".t("HUOM: Tilaus oli jo ker‰tty! Jos tarvitset ker‰yslistan, tulosta ker‰yslistan kopio").".</font><br><br>";

    // Jos yhtiˆn parametreiss‰ halutaaan, ett‰ tulostetaan l‰hete ja ker‰yslista aina
    if (isset($tulosta_lahete_uudestaan) and trim($tulosta_lahete_uudestaan) != '' and isset($komento) and isset($komento['L‰hete']) and trim($komento['L‰hete'] != "")) {

      // Tulostetaan l‰hete
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

    // jos on myyntitilaus ja ei haluttu tulostaa l‰hetett‰, merkataan rivit ker‰tyksi
    // ja toimitetuksi ja laitetaan alatila D
    if ($laskurow['eilahetetta'] != '' or $laskurow['sisainen'] != '') {

      if ($laskurow['sisainen'] == '' and ($kateinen == 'X' or $itsetulostus == "X"
          or $laskurow["vienti"] != '')) {

        // Jos kyseess‰ on k‰teismyynti‰ tai maksuehdolla on itsetulostust‰pp‰ tai vienti‰,
        // merkataan tilaukset ker‰tyksi mutta ei toimitetuksi ja alatila C/B
        // tai toimitettu tilaus muutetaan maksuehdosta k‰teiseksi, tila pysyy D
        if ($laskurow["alatila"] == 'D') {
          $alat = "D";
        }
        elseif ($laskurow["vienti"] != '') {
          $alat = "B";
        }
        else {
          $alat = "C";
        }

        $query  = "UPDATE lasku set tila='L', alatila='$alat', lahetepvm=now()
                   WHERE tunnus='$laskurow[tunnus]'
                   and yhtio='$kukarow[yhtio]'";
        $result = pupe_query($query);
      }
      else {
        // Jos kyseess‰  EI ole k‰teismyynti‰ eik‰ itsetulostusta eik‰ vienti‰ TAI t‰m‰ on sis‰inen niin merkataan tilaus toimitetuksi
        $query  = "UPDATE tilausrivi
                   SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
                   WHERE otunnus   = '$laskurow[tunnus]'
                   and yhtio       = '$kukarow[yhtio]'
                   and var         not in ('P','J','O','S')
                   and keratty    != ''
                   and toimitettu  = ''
                   and tyyppi     != 'D'";
        $result = pupe_query($query);

        if ($jaksotettu == 'X') {
          $query = "UPDATE lasku SET
                    tila         = 'L',
                    alatila      = 'J',
                    lahetepvm    = now()
                    WHERE tunnus = '$laskurow[tunnus]'
                    AND yhtio    = '$kukarow[yhtio]'
                    AND alatila  not IN ('J', 'D')";
          $result = pupe_query($query);

          $tilaus_valmis_ulos .= t("Maksusopimustilaus siirretty odottamaan loppulaskutusta").": $laskurow[tunnus] $laskurow[nimi]<br>\n";
        }
        else {
          $query  = "UPDATE lasku set tila='L', alatila='D', lahetepvm=now()
                     WHERE tunnus = '$laskurow[tunnus]'
                     and yhtio    = '$kukarow[yhtio]'";
          $result = pupe_query($query);
        }
      }

      if ($laskurow["sisainen"] != ''
        or ($kateinen == 'X' and $kateisohitus == ""
          and ($kukarow["kassamyyja"] != '' or $kertakassa != "")
          and $laskurow["vienti"] == ''
          and $laskurow["alatila"] == 'D')
      ) {
        //laskutetaan koko tilaus heti jos se on sisainen lasku
        // tai maksuehto vaihdettu k‰teismyynniksi
        $laskutettavat  = $laskurow["tunnus"];
        $tee            = "TARKISTA";
        $laskutakaikki  = "KYLLA";
        $silent         = "VIENTI";

        if ($kukarow["kirjoitin"] != 0 and $valittu_tulostin == 0) {
          $valittu_tulostin = $kukarow["kirjoitin"];
        }
        elseif ($valittu_tulostin == 0) {
          $valittu_tulostin = "AUTOMAAGINEN_VALINTA";
        }

        require "tilauskasittely/verkkolasku.php";

        unset($valittu_tulostin);
      }
    }
  }
  else {
    // t‰ss‰ k‰yd‰‰n l‰pi v‰h‰n n‰it‰ rahtijuttuja
    // katotaan saadaanko tilaus ilmaseks jos ollaan yli rajojen
    // haetaan asiakkaan alarajasumma
    $as_que = "SELECT rahtivapaa_alarajasumma
               FROM asiakas
               WHERE yhtio                 = '$kukarow[yhtio]'
               AND tunnus                  = '$laskurow[liitostunnus]'
               AND rahtivapaa_alarajasumma > 0";
    $as_res = pupe_query($as_que);

    $rahtivapaa_alarajasumma = 0;

    if (mysql_num_rows($as_res) == 1) {
      $as_row = mysql_fetch_assoc($as_res);
      $rahtivapaa_alarajasumma = (float) $as_row["rahtivapaa_alarajasumma"];
    }
    else {
      $rahtivapaa_alarajasumma = (float) $yhtiorow["rahtivapaa_alarajasumma"];
    }

    $query_ale_lisa = generoi_alekentta('M');

    $tilausriviquery = "SELECT tilausrivi.tuoteno, (tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt) * $query_ale_lisa) rivihinta, (tilausrivi.varattu+tilausrivi.jt) kplmaara
                        FROM tilausrivi
                        WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                        AND tilausrivi.otunnus  = '$laskurow[tunnus]'
                        AND tilausrivi.tyyppi  != 'D'";
    $rivihintaresult = pupe_query($tilausriviquery);

    $rahtivapaa_alarajasumma_rivit = 0;

    while ($totaalirivi = mysql_fetch_assoc($rivihintaresult)) {
      // KAUTTALASKUTUSKIKKARE
      if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $koti_yhtio == $kukarow['yhtio']) {
        $rahtivapaa_alarajasumma_rivit += etayhtio_hinta($totaalirivi["tuoteno"], $totaalirivi["kplmaara"], $laskurow);
      }
      else {
        $rahtivapaa_alarajasumma_rivit += $totaalirivi["rivihinta"];
      }
    }

    // kaikkiyhteens‰ ja rahtivapaa_alarajasumma on verollisia jos myyntihinnat ovat verollisia, tai verottomia vice versa
    if ($rahtivapaa_alarajasumma_rivit > $rahtivapaa_alarajasumma and $rahtivapaa_alarajasumma != 0) {
      $tilaus_valmis_ulos .= t("Tilauksen arvo ylitti rahtivapaasumman! Merkattiin tilaus rahtivapaaksi.")."<br>";

      // Rahtivapaat tilaukset l‰hetet‰‰n aina l‰hett‰j‰n rahtisopimuksella
      $query = "UPDATE lasku
                SET rahtivapaa  = 'o',
                kohdistettu = 'K'
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$laskurow[tunnus]'";
      $rahtivapres = pupe_query($query);

      $laskurow["kohdistettu"] = 'K';
      $laskurow["rahtivapaa"]  = 'o';
    }
    elseif ($yhtiorow["rahti_hinnoittelu"] == "o" or $yhtiorow["rahti_hinnoittelu"] == "P") {
      // jos rahtihinnoittelu tapahtuu tilauksen hinnan (alviton) perusteella
      // tai rahtihinnoittelu tapahtuu tilauksen painon mukaan automaattisesti niin tehd‰‰n se t‰ss‰

      // t‰h‰n ker‰t‰‰n tilausnumerot, joista lasketaan rahti
      $rahtimaksu_tunnukset = array();

      $query = "SELECT *
                FROM toimitustapa
                WHERE yhtio = '$kukarow[yhtio]'
                AND selite  = '$laskurow[toimitustapa]'";
      $result = pupe_query($query);
      $torow = mysql_fetch_assoc($result);

      $rahtinro_tuoteno_lisa = trim($torow['rahti_tuotenumero']) != '' ? "'{$yhtiorow['rahti_tuotenumero']}', '{$torow['rahti_tuotenumero']}'" : "'{$yhtiorow['rahti_tuotenumero']}'";
      $rahtinro_tuoteno_lisa = lisaa_vaihtoehtoinen_rahti_merkkijonoon($rahtinro_tuoteno_lisa);

      // jos kyseess‰ on JT-tilaus, katsotaan pit‰‰kˆ lis‰t‰ rahtikulua usealle tilaukselle
      if ($laskurow["clearing"] == "JT-TILAUS" and $yhtiorow["jt_rahti"] == "E") {
        // etsit‰‰n kaikki is‰tilaukset milt‰ n‰m‰ JT-tuotteet ovat tulleet ja katsotaan onko niill‰ jo rahtikulu (lis‰t‰‰n rahtikulu niille jolla ei ole)
        $query = "SELECT DISTINCT vanha_otunnus
                  FROM tilausrivi
                  JOIN tilausrivin_lisatiedot USE INDEX (tilausrivitunnus) ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
                  AND tilausrivin_lisatiedot.tilausrivitunnus  = tilausrivi.tunnus)
                  WHERE tilausrivi.yhtio                       = '$kukarow[yhtio]'
                  AND tilausrivi.otunnus                       = '$laskurow[tunnus]'
                  AND tilausrivi.tyyppi                       != 'D'";
        $step1re = pupe_query($query);

        while ($step1row = mysql_fetch_assoc($step1re)) {
          // etsit‰‰n ist‰tilausten kaikki lapset
          $query = "SELECT group_concat(DISTINCT tilausrivi.otunnus) uudet_otunnukset
                    FROM tilausrivin_lisatiedot
                    JOIN tilausrivi on (tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus AND tilausrivi.tyyppi != 'D')
                    WHERE tilausrivin_lisatiedot.yhtio       = '$kukarow[yhtio]'
                    and tilausrivin_lisatiedot.vanha_otunnus = '$step1row[vanha_otunnus]'";
          $step2re = pupe_query($query);
          $step2row = mysql_fetch_assoc($step2re);

          // katsotaan onko lapsitilauksilla rahtikulu syˆtetty
          $query = "SELECT tunnus
                    FROM tilausrivi
                    WHERE yhtio  = '$kukarow[yhtio]'
                    AND otunnus  in ($step2row[uudet_otunnukset])
                    AND tuoteno  IN ($rahtinro_tuoteno_lisa)
                    AND tyyppi  != 'D'";
          $step3re = pupe_query($query);

          // jos ei niin pit‰‰ rahdittaa!
          if (mysql_num_rows($step3re) == 0) {
            if (!in_array($step2row["uudet_otunnukset"], $rahtimaksu_tunnukset)) $rahtimaksu_tunnukset[] = $step2row["uudet_otunnukset"];
          }
        }
      }
      else {
        $rahtimaksu_tunnukset[] = $laskurow["tunnus"];
      }

      $rahtimaksu_tunnukset = implode(",", $rahtimaksu_tunnukset);

      $query = "SELECT tunnus
                FROM tilausrivi
                WHERE yhtio  = '$kukarow[yhtio]'
                AND otunnus  = '$laskurow[tunnus]'
                AND tyyppi  != 'D'
                AND tuoteno  IN ($rahtinro_tuoteno_lisa)";
      $result = pupe_query($query);

      //jos tilauksella on jo rahtikulu niin ei lis‰t‰ sit‰ uudestaan
      if (mysql_num_rows($result) == 0) {

        // jos toimitustapana on joku nouto niin ei lis‰t‰ rahtikulua
        if ($torow["nouto"] == "") {

          // haetaan rahtimaksu
          list($rah_hinta, $rah_ale, $rah_alv, $rah_netto) = hae_rahtimaksu($rahtimaksu_tunnukset);

          // haetaan rahtituotteen tiedot
          $query = "SELECT *
                    FROM tuote
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tuoteno = '$yhtiorow[rahti_tuotenumero]'";
          $rhire = pupe_query($query);

          //lis‰t‰‰n rahtikulurivi vain jos rahtikulu on erisuuri kuin nolla, nollarivej‰ on ilmeisesti turha lis‰t‰ (ja rahtikulutuote lˆytyy)
          if ($rah_hinta > 0 and mysql_num_rows($rhire) == 1) {

            $trow     = mysql_fetch_assoc($rhire);
            $otunnus = $laskurow['tunnus'];
            $nimitys = $laskurow["toimitustapa"];

            $query_ale_lrivi = "";
            $rah_alet = "";

            for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              if (isset($rah_ale["ale{$alepostfix}"]) and $rah_ale["ale{$alepostfix}"] > 0) {
                $query_ale_lrivi .= " ale{$alepostfix} = '".$rah_ale['ale'.$alepostfix]."',";
                $rah_alet .= ", ".t("Ale")."$alepostfix: ".$rah_ale["ale{$alepostfix}"]."%";
              }
            }

            $query = "INSERT into tilausrivi SET
                      laadittu = now(),
                      hinta    = '$rah_hinta',
                      netto    = '$rah_netto',
                      varattu  = '1',
                      tilkpl   = '1',
                      otunnus  = '$otunnus',
                      tuoteno  = '$trow[tuoteno]',
                      nimitys  = '$nimitys',
                      yhtio    = '$kukarow[yhtio]',
                      tyyppi   = 'L',
                      $query_ale_lrivi
                      alv      = '$rah_alv'";
            $addtil = pupe_query($query);

            $tilaus_valmis_ulos .= t("Tilaukselle lis‰ttiin rahtikulua").": $rah_hinta $laskurow[valkoodi]$rah_alet<br>";
          }

          if ($yhtiorow["jalkivaatimus_tuotenumero"] == "") {
            $yhtiorow["jalkivaatimus_tuotenumero"] = $yhtiorow["rahti_tuotenumero"];
          }

          $query = "SELECT tunnus
                    FROM tilausrivi
                    WHERE yhtio  = '$kukarow[yhtio]'
                    AND otunnus  = '$laskurow[tunnus]'
                    AND tyyppi  != 'D'
                    AND tuoteno  = '$yhtiorow[jalkivaatimus_tuotenumero]'";
          $result = pupe_query($query);

          $query = "SELECT *
                    FROM tuote
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tuoteno = '$yhtiorow[jalkivaatimus_tuotenumero]'";
          $rhire = pupe_query($query);

          // lis‰t‰‰n j‰lkivaatimuskulu jos sit‰ ei ole tilauksella ja tuote lˆytyy
          if (mysql_num_rows($rhire) == 1 and mysql_num_rows($result) == 0 and $torow["jvkulu"] != 0 and $maksuehtorow["jv"] != "") {

            $trow = mysql_fetch_assoc($rhire);

            $otunnus   = $laskurow['tunnus'];
            $hinta     = $torow["jvkulu"]; // jvkulu
            $nimitys   = t("J‰lkivaatimuskulu", $tilausvalmis_asiakas_row['kieli']);

            list($hinta, $alv) = alv($laskurow, $trow, $hinta, '', '');

            $query  = "INSERT into tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
                       values (now(), '$hinta', '$netto', '1', '1', '$otunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '')";
            $addtil = pupe_query($query);

            $hinta = money_format('%.2n', $hinta);

            $tilaus_valmis_ulos .= t("Tilaukselle lis‰ttiin j‰lkivaatimuskulua").": $hinta $laskurow[valkoodi]<br>";
          }

          // lis‰t‰‰n erilliskasitelt‰v‰kulu jos sit‰ ei ole tilauksella ja tuote lˆytyy ja se on toimitustavan takana setattu
          if ($torow['erilliskasiteltavakulu'] != 0 and $yhtiorow["erilliskasiteltava_tuotenumero"] != "") {

            $query = "SELECT tunnus
                      FROM tilausrivi
                      WHERE yhtio  = '{$kukarow['yhtio']}'
                      AND otunnus  = '{$laskurow['tunnus']}'
                      AND tyyppi  != 'D'
                      AND tuoteno  = '{$yhtiorow['erilliskasiteltava_tuotenumero']}'";
            $result = pupe_query($query);

            $query = "SELECT *
                      FROM tuote
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tuoteno = '{$yhtiorow['erilliskasiteltava_tuotenumero']}'";
            $rhire = pupe_query($query);

            // lis‰t‰‰n j‰lkivaatimuskulu jos sit‰ ei ole tilauksella ja tuote lˆytyy
            if (mysql_num_rows($rhire) == 1 and mysql_num_rows($result) == 0) {

              $trow = mysql_fetch_assoc($rhire);

              $otunnus   = $laskurow['tunnus'];
              $hinta     = $torow["erilliskasiteltavakulu"]; // jvkulu
              $nimitys   = t("Erillisk‰sitelt‰v‰kulu");

              list($hinta, $alv) = alv($laskurow, $trow, $hinta, '', '');

              $query  = "INSERT into tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
                         values (now(), '{$hinta}', '{$netto}', '1', '1', '{$otunnus}', '{$trow['tuoteno']}', '{$nimitys}', '{$kukarow['yhtio']}', 'L', '{$alv}', '')";
              $addtil = pupe_query($query);

              $hinta = money_format('%.2n', $hinta);

              $tilaus_valmis_ulos .= t("Tilaukselle lis‰ttiin erillisk‰sitelt‰v‰kulua").": {$hinta} {$laskurow['valkoodi']}<br>";
            }
          }
        }
      }

    }
    elseif ($yhtiorow["rahti_hinnoittelu"] == "") {
      // jos tilauksella on jo rahtikulu niin merkataan se rahtivapaaksi, ettei rahtikulu mene uudestaan laskutuksen yhteydess‰!
      if ($laskurow["rahtivapaa"] == '' and onko_rahtituote_syotetty($laskurow, TRUE) > 0) {
        $tilaus_valmis_ulos .= t("Tilauksella oli k‰sin syˆtetty rahtikulu! Merkattiin tilaus rahtivapaaksi, ettei kulua lis‰t‰ en‰‰ automaattisesti laskutuksessa.")."<br>";

        // Rahtivapaat tilaukset l‰hetet‰‰n aina l‰hett‰j‰n rahtisopimuksella
        $query = "UPDATE lasku
                  SET rahtivapaa = 'O',
                  kohdistettu = 'K'
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tunnus  = '$laskurow[tunnus]'";
        $rahtivapres = pupe_query($query);

        $laskurow["kohdistettu"] = 'K';
        $laskurow["rahtivapaa"]  = 'o';
      }
    }

    // Jos on kassamyynti‰ ja se on k‰teist‰ niin tulostetaan lasku heti ilman l‰hetett‰
    if (($kateinen == 'X' and $kateisohitus == "" and ($kukarow["kassamyyja"] != '' or $kertakassa != "") and $laskurow["vienti"] == '') or $laskurow["clearing"] == "KORKOLASKU") {

      $query  = "UPDATE tilausrivi
                 SET kerattyaika = now(), keratty = '$kukarow[kuka]'
                 WHERE otunnus   = '$laskurow[tunnus]'
                 and yhtio       = '$kukarow[yhtio]'
                 and var         not in ('P','J','O','S')
                 and keratty     = ''
                 and toimitettu  = ''
                 and tyyppi     != 'D'";
      $result = pupe_query($query);

      $query = "UPDATE tilausrivi
                SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
                WHERE otunnus   = '$laskurow[tunnus]'
                and var         not in ('P','J','O','S')
                and yhtio       = '$kukarow[yhtio]'
                and keratty    != ''
                and toimitettu  = ''
                and tyyppi     != 'D'";
      $result = pupe_query($query);

      $query = "UPDATE lasku
                SET tila = 'L', alatila = 'D'
                WHERE tunnus = '$laskurow[tunnus]'
                and yhtio    = '$kukarow[yhtio]'";
      $result = pupe_query($query);

      //tulostetaan k‰teislasku...
      $laskutettavat = $laskurow["tunnus"];
      $tee = "TARKISTA";
      $laskutakaikki = "KYLLA";
      $silent = "VIENTI";

      if (!empty($yhtiorow['kateiskuitin_paivays']) and !empty($poikkeava_kpvm)) {
        list($poikkeava_laskvv, $poikkeava_laskkk, $poikkeava_laskpp) = explode("-", $poikkeava_kpvm);

        $poikkeava_laskkk = sprintf('%02d', $poikkeava_laskkk);
        $poikkeava_laskpp = sprintf('%02d', $poikkeava_laskpp);

        if (!checkdate($poikkeava_laskkk, $poikkeava_laskpp, $poikkeava_laskvv)) {
          echo "<font class='error'>".t("Poikkeava laskunp‰iv‰m‰‰r‰ virheellinen")."!</font><br>";
        }
        elseif (strtotime($yhtiorow['tilikausi_alku']) > strtotime("$poikkeava_laskvv-$poikkeava_laskkk-$poikkeava_laskpp")
          or strtotime($yhtiorow['tilikausi_loppu']) < strtotime("$poikkeava_laskvv-$poikkeava_laskkk-$poikkeava_laskpp")) {
          echo "<font class='error'>".t("Poikkeava laskunp‰iv‰m‰‰r‰ virheellinen")." ".t("Tilikausi on suljettu")."!</font><br>";
        }
        else {
          $laskkk = $poikkeava_laskkk;
          $laskpp = $poikkeava_laskpp;
          $laskvv = $poikkeava_laskvv;
        }
      }

      if ($laskurow['ohjelma_moduli'] == 'MAGENTO') {
        $valittu_tulostin = "";
      }
      elseif ($kukarow["kirjoitin"] != 0 and $valittu_tulostin == 0) {
        $valittu_tulostin = $kukarow["kirjoitin"];
      }
      elseif ($valittu_tulostin == 0) {
        $valittu_tulostin = "AUTOMAAGINEN_VALINTA";
      }

      require "tilauskasittely/verkkolasku.php";

      /**
       * Tulostetaan kassakuitti
       */
      require_once "tilauskasittely/tulosta_asiakkaan_kuitti.inc";

      $kuitin_parametrit = array("kateinen" => $kateista_annettu);

      tulosta_asiakkaan_kuitti($laskurow["laskunro"], $kukarow["kuittitulostin"],
        $kuitin_parametrit);

      unset($valittu_tulostin);
    }
    // jos on myyntitilaus ja ei haluttu tulostaa l‰hetett‰, merkataan rivit ker‰tyksi ja toimitetuksi ja laitetaan alatila D
    elseif (($laskurow['eilahetetta'] != '' or $laskurow['sisainen'] != '') and $kateisohitus == "") {

      // Jos tullaan JT-selauksen kautta niin ei laiteta vahvistuksia jos yhtiˆll‰ ei oo erikseen haluttu niit‰
      if (isset($tilausvalmiskutsuja) and $tilausvalmiskutsuja == "JTSELAUS" and (($yhtiorow["tilausvahvistus_jttoimituksista"] == "" and $tilausvalmis_asiakas_row["tilausvahvistus_jttoimituksista"] != "K") or $tilausvalmis_asiakas_row["tilausvahvistus_jttoimituksista"] == "E")) {
        $laskurow['tilausvahvistus'] = "";
      }

      // vaikka laitetaan sis‰iseen laskutukseen tai ei haluttu l‰hetett‰, mutta annetaan mahdollisuus l‰hett‰‰ tilausvahvistus omaan s‰hkˆpostiin
      if (trim($laskurow['tilausvahvistus']) != "" and ($yhtiorow['tilausvahvistus_sisaisista'] == "S" or ($yhtiorow['tilausvahvistus_sisaisista'] == "U" and $laskurow['sisainen'] == '') or strpos($laskurow['tilausvahvistus'], 'O') !== FALSE or $yhtiorow['lahetteen_tulostustapa'] == "I")) {
        $params_tilausvahvistus = array(
          'tee'            => $tee,
          'toim'            => $toim,
          'kieli'            => $kieli,
          'komento'          => $komento,
          'laskurow'          => $laskurow,
          'naytetaanko_rivihinta'    => $naytetaanko_rivihinta,
          'extranet_tilausvahvistus'  => $extranet_tilausvahvistus,
        );

        laheta_tilausvahvistus($params_tilausvahvistus);
      }

      // Jos yhtiˆn parametreiss‰ halutaaan, ett‰ tulostetaan l‰hete ja ker‰yslista aina
      if ($yhtiorow['lahetteen_tulostustapa'] == "I" and $tilausvalmiskutsuja != "JTSELAUS") {

        $query = "UPDATE lasku
                  SET tila = 'N',
                  alatila     = 'A'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$laskurow['tunnus']}'";
        pupe_query($query);

        if (!empty($sahkoinen_lahete) and isset($generoi_sahkoinen_lahete) and trim($generoi_sahkoinen_lahete) != "" and $kukarow["extranet"] == "" and !empty($sahkoinen_lahete_toim) and in_array($toim, $sahkoinen_lahete_toim)) {

          require_once "inc/sahkoinen_lahete.class.inc";

          sahkoinen_lahete($laskurow);
        }

        $_kerayslista_chkbx = !empty($tulosta_kerayslista_chkbx);
        $_kerayslista_chkbx = ($_kerayslista_chkbx and count($tulosta_kerayslista_chkbx) > 1);
        $_kerayslista_chkbx = ($_kerayslista_chkbx or !isset($tulosta_kerayslista_chkbx));

        if ($toim != "REKLAMAATIO" and $_kerayslista_chkbx) {
          // Tulostetaan ker‰yslista
          $tilausnumeroita = $laskurow["tunnus"];
          require "tilaus-valmis-tulostus.inc";
        }

        $_lahete_chkbx = !empty($tulosta_lahete_chkbx);
        $_lahete_chkbx = ($_lahete_chkbx and count($tulosta_lahete_chkbx) > 1);
        $_lahete_chkbx = ($_lahete_chkbx or !isset($tulosta_lahete_chkbx));

        if ($_lahete_chkbx) {
          // Tulostetaan l‰hete
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

        // Jos tehdaspalautus
        if (file_exists("$tilausvalmis_asiakas_row[toimitusvahvistus]") and $tilausvalmis_asiakas_row["toimitusvahvistus"] == "editilaus_out_futur.inc") {

          // jos $laskurow on jo populoitu, otetaan se talteen ja palautetaan t‰m‰n j‰lkeen
          $tmp_laskurow = $laskurow;

          $myynti_vai_osto = 'M';

          require "editilaus_out_futur.inc";

          $laskurow = $tmp_laskurow;
        }
      }

      // Laitetaan t‰ss‰kin haarassa Intrastat-tiedot kuntoon, muuten laskujen ketjutus ei onnaa. (varasto = 0 ,eli menn‰‰n yhtiˆn oletusmaalla)
      $laskurow = palauta_intrastat_tiedot($laskurow, 0, TRUE);

      if ($laskurow['sisainen'] == '' and ($kateinen == 'X' or $itsetulostus == "X" or $laskurow["vienti"] != '')) {
        // Jos kyseess‰ on k‰teismyynti‰ tai jos maksuehtolla on itsetulostust‰pp‰ tai vienti‰, merkataan tilaukset ker‰tyksi mutta ei toimitetuksi ja alatila C/B
        $query  = "UPDATE tilausrivi
                   SET kerattyaika = now(), keratty = '$kukarow[kuka]'
                   WHERE otunnus   = '$laskurow[tunnus]'
                   and yhtio       = '$kukarow[yhtio]'
                   and var         not in ('P','J','O','S')
                   and keratty     = ''
                   and toimitettu  = ''
                   and tyyppi     != 'D'";
        $result = pupe_query($query);

        if ($laskurow["vienti"] != '') {
          $alat = "B";
        }
        else {
          $alat = "C";
        }

        $query  = "UPDATE lasku set tila='L', alatila='$alat', lahetepvm=now()
                   WHERE tunnus='$laskurow[tunnus]'
                   and yhtio='$kukarow[yhtio]'";
        $result = pupe_query($query);
      }
      else {
        // Jos kyseess‰  EI ole k‰teismyynti‰ eik‰ itsetulostusta eik‰ vienti‰ TAI t‰m‰ on sis‰inen niin merkataan tilaus toimitetuksi
        $query  = "UPDATE tilausrivi
                   SET kerattyaika = now(), keratty = '$kukarow[kuka]'
                   WHERE otunnus   = '$laskurow[tunnus]'
                   and yhtio       = '$kukarow[yhtio]'
                   and var         not in ('P','J','O','S')
                   and keratty     = ''
                   and toimitettu  = ''
                   and tyyppi     != 'D'";
        $result = pupe_query($query);

        $query  = "UPDATE tilausrivi
                   SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
                   WHERE otunnus   = '$laskurow[tunnus]'
                   and yhtio       = '$kukarow[yhtio]'
                   and var         not in ('P','J','O','S')
                   and keratty    != ''
                   and toimitettu  = ''
                   and tyyppi     != 'D'";
        $result = pupe_query($query);

        if ($jaksotettu == 'X') {
          $query = "UPDATE lasku SET
                    tila         = 'L',
                    alatila      = 'J',
                    lahetepvm    = now()
                    WHERE tunnus = '$laskurow[tunnus]'
                    AND yhtio    = '$kukarow[yhtio]'
                    AND alatila  not IN ('J', 'D')";
          $result = pupe_query($query);

          $tilaus_valmis_ulos .= t("Maksusopimustilaus siirretty odottamaan loppulaskutusta").": $laskurow[tunnus] $laskurow[nimi]<br>\n";
        }
        else {
          $query  = "UPDATE lasku set tila='L', alatila='D', lahetepvm=now()
                     WHERE tunnus = '$laskurow[tunnus]'
                     and yhtio    = '$kukarow[yhtio]'";
          $result = pupe_query($query);
        }

        // Jos meill‰ on osatoimituskielto, ni menn‰‰n katsomaan pit‰‰kˆ tilaus j‰tt‰‰ odottamaan tuotteita
        if ($laskurow['osatoimitus'] != '') {
          require "tilauskasittely/tilaus-valmis-valitsetila.inc";
        }
      }

      if ($laskurow["sisainen"] != '') {
        //laskutetaan koko tilaus heti jos se on sisainen lasku
        $laskutettavat  = $laskurow["tunnus"];
        $tee       = "TARKISTA";
        $laskutakaikki   = "KYLLA";
        $silent       = "VIENTI";

        if ($kukarow["kirjoitin"] != 0 and $valittu_tulostin == 0) {
          $valittu_tulostin = $kukarow["kirjoitin"];
        }
        elseif ($valittu_tulostin == 0) {
          $valittu_tulostin = "AUTOMAAGINEN_VALINTA";
        }

        require "tilauskasittely/verkkolasku.php";

        unset($valittu_tulostin);
      }
    }
    else {
      // Katotaan mit‰ tehd‰‰n l‰hetteelle

      // Ensin on pakko splitata l‰hete varastoittain
      // tyhjennet‰‰n laskun varastokentt‰, siell‰ oli tieto varastoon optimoinnista mutta nyt se kertoo mihin varastoon l‰hete kuuluu laittaa
      $laskurow['varasto'] = 0;

      //Toimitustavan hetier‰ tarvitaan
      $query = "SELECT * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$laskurow[toimitustapa]'";
      $result = pupe_query($query);
      $totarowx = mysql_fetch_assoc($result);

      // Maksuehdon jv tieto tarvitaan
      $query = "SELECT jv from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
      $result = pupe_query($query);
      $maehrowx = mysql_fetch_assoc($result);

      // t‰ss‰ v‰liss‰ katsotaan onko tilausrivi_omalle_tilaukselle-parametri p‰‰ll‰
      // jos on, niin splitataan ne tilausrivit, joille ollaan valittu "omalle_tilaukselle" tilausrivi‰ syˆtett‰ess‰
      // splittaus tapahtuu niin, ett‰ jokaiselle tilausriville j‰‰ yksi kappale
      // eli jos "omalle_tilausriville"-tilausrivin kpl = 5, niin tehd‰‰n 4 uutta rivi‰ ja jokaisella viidell‰ tilausrivill‰ on 1 kpl
      // Otetaan mukaan myˆs varattu = 1 rivit niin saadaan omalle_tilaukselle-t‰pp‰ kuntoon myˆs mahdolliselle tuoteperheelle.
      if ($yhtiorow['tilausrivi_omalle_tilaukselle'] != '') {

        $query = "SELECT tilausrivin_lisatiedot.omalle_tilaukselle, tilausrivi.*
                  FROM tilausrivi
                  JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus and tilausrivin_lisatiedot.omalle_tilaukselle != '')
                  WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
                  AND tilausrivi.otunnus  = '{$laskurow['tunnus']}'
                  AND tilausrivi.tyyppi  != 'D'
                  AND tilausrivi.varattu  >= 1
                  AND tilausrivi.var      not in ('P','J','O','S')
                  AND (tilausrivi.perheid = 0 OR tilausrivi.perheid=tilausrivi.tunnus)
                  ORDER BY tilausrivi.tunnus";
        $result = pupe_query($query);

        while ($row = mysql_fetch_assoc($result)) {

          $query = "SELECT *
                    FROM tilausrivi
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$row['tunnus']}'";
          $splitres = pupe_query($query);
          $splitrow = mysql_fetch_assoc($splitres);

          mysql_data_seek($splitres, 0);

          // tehd‰‰n $row[varattu] verran tilausrivej‰
          for ($xx = 1; $xx < $splitrow['varattu']; $xx++) {

            $fields = "yhtio";
            $values = "'{$kukarow['yhtio']}'";

            // Ei monisteta tunnusta
            for ($i = 1; $i < mysql_num_fields($splitres) - 1; $i++) {

              $fieldname = mysql_field_name($splitres, $i);
              $fields .= ", ".$fieldname;

              switch ($fieldname) {
              case 'varattu':
                $values .= ", 1";
                break;
              case 'kate_korjattu':
                $values .= ", NULL";
                break;
              default:
                $values .= ", '{$splitrow[$fieldname]}'";
              }
            }

            $kysely  = "INSERT INTO tilausrivi ({$fields}) VALUES ({$values})";
            $uusires = pupe_query($kysely);

            $split_tilausrivin_tunnus = mysql_insert_id($GLOBALS["masterlink"]);

            $query = "SELECT *
                      FROM tilausrivin_lisatiedot
                      WHERE yhtio          = '{$kukarow['yhtio']}'
                      AND tilausrivitunnus = '{$row['tunnus']}'";
            $lisatiedot_result = pupe_query($query);
            $lisatiedot_row = mysql_fetch_array($lisatiedot_result);

            $fields = "yhtio";
            $values = "'{$kukarow['yhtio']}'";

            // Ei monisteta tunnusta
            for ($i = 1; $i < mysql_num_fields($lisatiedot_result) - 1; $i++) {

              $fieldname = mysql_field_name($lisatiedot_result, $i);
              $fields .= ", ".$fieldname;

              switch ($fieldname) {
              case 'tilausrivitunnus':
                $values .= ", '{$split_tilausrivin_tunnus}'";
                break;
              default:
                $values .= ", '".$lisatiedot_row[$fieldname]."'";
              }
            }

            $query = "INSERT into tilausrivin_lisatiedot ($fields) VALUES ($values)";
            $lisatiedot_result = pupe_query($query);

            // tuoteperhe-keissi
            if ($row['perheid'] > 0 and $row['tunnus'] == $row['perheid']) {
              // p‰ivitet‰‰n splitatulle riville perheid
              $query = "UPDATE tilausrivi
                        SET perheid = '{$split_tilausrivin_tunnus}'
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  = '{$split_tilausrivin_tunnus}'";
              $update_res = pupe_query($query);

              $query = "SELECT *
                        FROM tilausrivi
                        WHERE yhtio  = '{$kukarow['yhtio']}'
                        AND otunnus  = '{$laskurow['tunnus']}'
                        AND tyyppi  != 'D'
                        AND perheid  = '{$row['tunnus']}'
                        AND tunnus  != '{$row['tunnus']}'";
              $lapsires = pupe_query($query);

              while ($lapsirow = mysql_fetch_assoc($lapsires)) {

                $fields = "yhtio";
                $values = "'{$kukarow['yhtio']}'";

                // Ei monisteta tunnusta
                for ($i = 1; $i < mysql_num_fields($lapsires) - 1; $i++) {

                  $fieldname = mysql_field_name($lapsires, $i);
                  $fields .= ", ".$fieldname;

                  switch ($fieldname) {
                  case 'varattu':
                    $values .= ", '".($lapsirow['varattu'] / $row['varattu'])."'";
                    break;
                  case 'perheid':
                    $values .= ", {$split_tilausrivin_tunnus}";
                    break;
                  case 'kate_korjattu':
                    $values .= ", NULL";
                    break;
                  default:
                    $values .= ", '{$lapsirow[$fieldname]}'";
                  }
                }

                $kysely  = "INSERT INTO tilausrivi ({$fields}) VALUES ({$values})";
                $uusires = pupe_query($kysely);

                $split_tilausrivin_tunnus_lapsi = mysql_insert_id($GLOBALS["masterlink"]);

                $query = "SELECT *
                          FROM tilausrivin_lisatiedot
                          WHERE yhtio          = '{$kukarow['yhtio']}'
                          AND tilausrivitunnus = '{$lapsirow['tunnus']}'";
                $lisatiedot_result = pupe_query($query);
                $lisatiedot_row = mysql_fetch_array($lisatiedot_result);

                $fields = "yhtio";
                $values = "'{$kukarow['yhtio']}'";

                // Ei monisteta tunnusta
                for ($i = 1; $i < mysql_num_fields($lisatiedot_result) - 1; $i++) {

                  $fieldname = mysql_field_name($lisatiedot_result, $i);
                  $fields .= ", ".$fieldname;

                  switch ($fieldname) {
                  case 'tilausrivitunnus':
                    $values .= ", '{$split_tilausrivin_tunnus_lapsi}'";
                    break;
                  case 'omalle_tilaukselle':
                    $values .= ", 'K'";
                    break;
                  default:
                    $values .= ", '".$lisatiedot_row[$fieldname]."'";
                  }
                }

                $query = "INSERT into tilausrivin_lisatiedot ($fields) VALUES ($values)";
                $lisatiedot_result = pupe_query($query);
              }
            }
          }

          if ($row['perheid'] > 0 and $row['tunnus'] == $row['perheid']) {
            // jos on tuoteperhe, niin p‰ivitet‰‰n lapsille varattu
            $query = "SELECT *
                      FROM tilausrivi
                      WHERE yhtio  = '{$kukarow['yhtio']}'
                      AND otunnus  = '{$laskurow['tunnus']}'
                      AND tyyppi  != 'D'
                      AND perheid  = '{$row['tunnus']}'
                      AND tunnus  != '{$row['tunnus']}'";
            $lapsires = pupe_query($query);

            while ($lapsirow = mysql_fetch_assoc($lapsires)) {
              $query = "UPDATE tilausrivi
                        SET varattu = '".($lapsirow['varattu'] / $row['varattu'])."'
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND otunnus = '{$laskurow['tunnus']}'
                        AND tunnus  = '{$lapsirow['tunnus']}'";
              $update_res = pupe_query($query);

              // p‰ivitet‰‰n alkuoer‰inen lapsirivi
              $query = "UPDATE tilausrivin_lisatiedot
                        SET omalle_tilaukselle = 'K'
                        WHERE yhtio          = '{$kukarow['yhtio']}'
                        AND tilausrivitunnus = '{$lapsirow['tunnus']}'";
              $lisatiedot_result = pupe_query($query);
            }
          }

          // p‰ivitet‰‰n alkuper‰isen rivin varattu-kentt‰‰n 1
          $query = "UPDATE tilausrivi
                    SET varattu = 1
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$row['tunnus']}'";
          $update_res = pupe_query($query);
        }
      }

      $kerayserat_select = $yhtiorow['kerayserat'] == 'K' ? "vh.keraysvyohyke, " : "";
      $kerayserat_join = $yhtiorow['kerayserat'] == 'K' ? " LEFT JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)" : "";

      if ($yhtiorow["splittauskielto"] == "K") {
        $sorttaus = ", tilausrivi.kerayspvm, sorttauskentta";
      }
      else {
        $sorttaus = ", sorttauskentta";
      }

      if ($yhtiorow['kerayserat'] == 'K') {
        $sorttaus = ", vh.keraysvyohyke {$sorttaus}";
      }

      $vakit_eri_tilaukselle = $totarowx["vaihtoehtoinen_vak_toimitustapa"] != "";

      if ($vakit_eri_tilaukselle) {
        $tuote_select = "tuote.vakkoodi AS vakkoodi,";
        $tuote_join = "JOIN tuote ON (tuote.tuoteno = tilausrivi.tuoteno
                         AND tuote.yhtio = tilausrivi.yhtio)";
        $sorttaus = ", tuote.vakkoodi {$sorttaus}";
      }
      else {
        $tuote_select = "";
        $tuote_join = "";
      }

      //haetaan kaikkien tilausrivien varastopaikat (varattu+kpl koska magenton tilaukset on laskutettu jo t‰ss‰ vaiheessa...)
      $query = "SELECT tilausrivi.otunnus,
                tilausrivi.perheid,
                tilausrivi.tunnus,
                tilausrivi.hyllyalue,
                tilausrivi.hyllynro,
                tilausrivi.hyllyvali,
                tilausrivi.hyllytaso,
                tilausrivi.kerayspvm,
                tilausrivi.toimaika,
                tilausrivin_lisatiedot.erikoistoimitus_myynti,
                if (tilausrivin_lisatiedot.omalle_tilaukselle != '', if(tilausrivi.perheid > 0, tilausrivi.perheid, tilausrivi.tunnus), 0) omalle_tilaukselle,
                if (tilausrivin_lisatiedot.omalle_tilaukselle != '', concat(tilausrivi.perheid,tilausrivi.tunnus), 0) omalle_tilaukselle_sort,
                tilausrivin_lisatiedot.ohita_kerays,
                {$kerayserat_select}
                {$tuote_select}
                concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta
                FROM tilausrivi
                JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays = '')
                {$kerayserat_join}
                {$tuote_join}
                WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                and tilausrivi.otunnus  = '$laskurow[tunnus]'
                AND tilausrivi.tyyppi  != 'D'
                and (tilausrivi.hyllyalue != '' OR tilausrivin_lisatiedot.omalle_tilaukselle != '')
                and tilausrivi.varattu+tilausrivi.kpl > 0
                and tilausrivi.var      not in ('P','J','O','S')
                ORDER BY omalle_tilaukselle_sort $sorttaus";
      $result = pupe_query($query);

      //t‰ss‰ muuttujassa on sit kaikki tunnukset splitin j‰lkeen
      $tilausnumerot   = array();
      $tilausnumerot[] = $laskurow["tunnus"];

      //mihin varastoon l‰hete kuuluu. aluksi x
      $varasto = $edvarasto = $kerayspvm = $edkerayspvm = $keraysvyohyke = $edkeraysvyohyke = $edomalle_tilaukselle = "x";
      $tulostusalue = $edtulostusalue = "x";

      //jos tilaus splittaantuu, niin laitetaan muihin otsikoihin ensimm‰isen tilauksen tunnus vanhaksitunnukseksi
      $temp_laskutunnus = $laskurow['tunnus'];

      $varasto_kpl_array = array();
      $tulalue_kpl_array = array();

      $ohjausmerkki_nro  = 1;
      $tilaus_splitattu   = FALSE;
      $splitattu_vakiksi = false;
      unset($laskusplitres);

      if ($yhtiorow['kerayserat'] == 'K' and isset($toimitustavan_lahto) and is_string($toimitustavan_lahto)) {
        $toimitustavan_lahto = unserialize(urldecode($toimitustavan_lahto));
      }

      while ($row = mysql_fetch_assoc($result)) {

        // Mihin varastoon
        $varasto = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);

        // Mik‰ tulostusalue
        $tulostusalue = onkotulostusalueita($row["hyllyalue"], $row["hyllynro"], $varasto);

        // Mik‰ ker‰ysvyˆhyke
        $keraysvyohyke = $row['keraysvyohyke'];

        // Mik‰ ker‰ysvyˆhyke
        $omalle_tilaukselle = $row['omalle_tilaukselle'];

        // Intrastat-tiedot
        $laskurow = palauta_intrastat_tiedot($laskurow, $varasto);

        // varasto vaihtuu, splitataan tilaus, paitsi jos kyseess‰ on jv-tilaus jonka rahtikirja tulostetaan heti, tai jos spliauskielto on trigattu
        // omalle_tilaukselle - tilauksia ei splitata muuten ku omiksi tilauksiksi (eli ei esim splitata varastoittain tai vyˆhykkeitt‰in)
        if ((  ((($varasto != $edvarasto and $edvarasto != 'x' and $edomalle_tilaukselle == 0) or
                ($tulostusalue != $edtulostusalue and $edtulostusalue != 'x' and $edtulostusalue != '' and $edomalle_tilaukselle == 0) or
                ($keraysvyohyke != $edkeraysvyohyke and $edkeraysvyohyke != 'x' and $edkeraysvyohyke != '' and $edomalle_tilaukselle == 0)
              ) and ($maehrowx["jv"] == '' or $totarowx["tulostustapa"] != 'H') and $laskurow["splittauskielto"] != "E"
            ) or
            ($row["kerayspvm"] != $edkerayspvm and $edkerayspvm != 'x' and $edomalle_tilaukselle == 0 and $yhtiorow["splittauskielto"] == "K") or
            ($omalle_tilaukselle != $edomalle_tilaukselle and $edomalle_tilaukselle != 'x') or
            ($vakit_eri_tilaukselle and $row["vakkoodi"] != "" and !$splitattu_vakiksi)
          )
          and $laskurow["osatoimitus"] == '') {

          $tilaus_splitattu = TRUE;

          // Meid‰n on p‰ivitett‰v‰ tunnusnippu
          if ($yhtiorow["splittauskielto"] == "K" and $laskurow["tunnusnippu"] == 0) {
            $query = "UPDATE lasku set tunnusnippu = tunnus where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]' and tunnusnippu = 0";
            $upresult = pupe_query($query);
            $laskurow["tunnusnippu"] = $laskurow["tunnus"];
          }

          if (!is_resource($laskusplitres)) {
            // Jotta saadaa lasku ja sen lis‰tiedot kopsattua kivasti jos se splittaantuu
            $query = "SELECT *
                      FROM lasku
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$laskurow[tunnus]'";
            $laskusplitres = pupe_query($query);

            $query = "SELECT *
                      FROM laskun_lisatiedot
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND otunnus = '$laskurow[tunnus]'";
            $laskulisatiedotsplitres = pupe_query($query);
            $lisatiedot_row = mysql_fetch_assoc($laskulisatiedotsplitres);
          }

          $fields = "yhtio";
          $values = "'$kukarow[yhtio]'";

          $split_kohdistettu  = $laskurow["kohdistettu"];
          $split_rahtivapaa  = $laskurow["rahtivapaa"];
          $split_toimitustapa = $laskurow["toimitustapa"];

          $query = "SELECT ulkoinen_jarjestelma, erikoistoimitus_toimitustapa
                    FROM varastopaikat
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$varasto}'";
          $ulkoinen_jarjestelma_res = pupe_query($query);
          $varaston_row = mysql_fetch_assoc($ulkoinen_jarjestelma_res);

          // Erikoistoimitukset rahtivapaana
          // Rahtivapaat tilaukset l‰hetet‰‰n aina l‰hett‰j‰n rahtisopimuksella
          if ($row['erikoistoimitus_myynti'] == 1 and $varaston_row['erikoistoimitus_toimitustapa'] != '') {
            $split_kohdistettu  = "K";
            $split_rahtivapaa  = "o";
            $split_toimitustapa = $varaston_row["erikoistoimitus_toimitustapa"];
          }

          if ($vakit_eri_tilaukselle and $row["vakkoodi"] != "") {
            $splitattu_vakiksi = true;
            $split_toimitustapa = $totarowx["vaihtoehtoinen_vak_toimitustapa"];
          }

          if ($yhtiorow['kerayserat'] == 'K' and isset($toimitustavan_lahto) and isset($toimitustavan_lahto[$varasto]) and $toimitustavan_lahto[$varasto] > 0) {
            $toimitustavan_lahto_arvo = $toimitustavan_lahto[$varasto];
            $laskurow['toimitustavan_lahto'] = $toimitustavan_lahto_arvo;
          }
          else {
            $toimitustavan_lahto_arvo = 0;
          }

          $laskurow['ohjausmerkki'] = paivita_ohjausmerkki($laskurow);

          // Ei monisteta tunnusta
          for ($i=1; $i < mysql_num_fields($laskusplitres)-1; $i++) {

            $fieldname = mysql_field_name($laskusplitres, $i);

            $fields .= ", ".$fieldname;

            switch ($fieldname) {
            case 'alatila':
              $values .= ", ''";
              break;
            case 'tila':
              $values .= ", 'N'";
              break;
            case 'vanhatunnus':
              $values .= ", '$temp_laskutunnus'";
              break;
            case 'varasto':
              $values .= ", '$varasto'";
              break;
            case 'tulostusalue':
              $values .= ", '$tulostusalue'";
              break;
            case 'toimitustavan_lahto':
              $values .= ", '$toimitustavan_lahto_arvo'";
              break;
            case 'ohjausmerkki':
              if ($row['omalle_tilaukselle'] > 0 and trim($laskurow['ohjausmerkki']) != "") {
                $values .= ", '{$ohjausmerkki_nro}-{$laskurow['ohjausmerkki']}'";
                $ohjausmerkki_nro++;
              }
              elseif ($row['omalle_tilaukselle'] > 0 and trim($laskurow['ohjausmerkki']) == "") {
                $values .= ", '{$ohjausmerkki_nro}-{$laskurow['vanhatunnus']}'";
                $ohjausmerkki_nro++;
              }
              else {
                $values .= ", '{$laskurow['ohjausmerkki']}'";
              }
              break;
            case 'kohdistettu':
              $values .= ", '$split_kohdistettu'";
              break;
            case 'rahtivapaa':
              $values .= ", '$split_rahtivapaa'";
              break;
            case 'toimitustapa':
              $values .= ", '$split_toimitustapa'";
              break;
            case 'kate_korjattu':
            case 'lahetetty_ulkoiseen_varastoon':
              $values .= ", NULL";
              break;
            default:
              $values .= ", '".$laskurow[$fieldname]."'";
            }
          }

          $kysely  = "INSERT into lasku ($fields) VALUES ($values)";
          $uusires = pupe_query($kysely);

          $laskurow['tunnus'] = mysql_insert_id($GLOBALS["masterlink"]);

          // Tallennetaan kaikki splitit
          $tilausnumerot[] = $laskurow["tunnus"];

          $fields = "yhtio";
          $values = "'$kukarow[yhtio]'";

          // Ei monisteta tunnusta
          for ($i = 1; $i < mysql_num_fields($laskulisatiedotsplitres) - 1; $i++) {

            $fieldname = mysql_field_name($laskulisatiedotsplitres, $i);

            $fields .= ", ".$fieldname;

            switch ($fieldname) {
            case 'otunnus':
              $values .= ", '$laskurow[tunnus]'";
              break;
            default:
              $values .= ", '".$lisatiedot_row[$fieldname]."'";
            }
          }

          $query = "INSERT into laskun_lisatiedot ($fields) VALUES ($values)";
          $uusires = pupe_query($query);
        }
        elseif (!$tilaus_splitattu) {
          // Pidet‰‰n lukua kuinka paljon mist‰kin varastosta on rivi‰, jotka j‰‰ alkuper‰iselle tilaukselle
          if ($varasto > 0) {
            if (isset($varasto_kpl_array[$varasto])) $varasto_kpl_array[$varasto]++;
            else $varasto_kpl_array[$varasto] = 1;
          }

          if ($tulostusalue != "") {
            if (isset($tulalue_kpl_array[$tulostusalue])) $tulalue_kpl_array[$tulostusalue]++;
            else $tulalue_kpl_array[$tulostusalue] = 1;
          }
        }

        //  edellinen varasto talteen
        $edvarasto = $varasto;

        // edellinen tulostusalue talteen
        $edtulostusalue = $tulostusalue;

        // edellinen keraysvyohyke
        $edkeraysvyohyke = $keraysvyohyke;

        //  edellinen kerayspvm talteen
        $edkerayspvm = $row["kerayspvm"];

        //  edellinen omalle_tilaukselle talteen
        $edomalle_tilaukselle = $row["omalle_tilaukselle"];

        //jos tilausrivin laskunumero on eri
        if ($row['otunnus'] != $laskurow['tunnus']) {
          $query = "UPDATE tilausrivi
                    SET otunnus='$laskurow[tunnus]'
                    WHERE tunnus='$row[tunnus]'
                    AND yhtio='$kukarow[yhtio]'";
          $ohno  = pupe_query($query);

          if ($row['perheid'] > 0 and $row['perheid'] == $row['tunnus']) {

            $query = "SELECT tilausrivin_lisatiedot.ohita_kerays
                      FROM tilausrivi
                      JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays != '')
                      WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
                      AND tilausrivi.otunnus  = '{$row['otunnus']}'
                      AND tilausrivi.perheid  = '{$row['perheid']}'
                      AND tilausrivi.tunnus  != '{$row['perheid']}'";
            $ohnoes_res = pupe_query($query);

            if (mysql_num_rows($ohnoes_res) > 0 or $row['omalle_tilaukselle'] > 0) {
              $query = "UPDATE tilausrivi
                        SET otunnus = '{$laskurow['tunnus']}'
                        WHERE yhtio  = '{$kukarow['yhtio']}'
                        AND otunnus  = '{$row['otunnus']}'
                        AND perheid  = '{$row['tunnus']}'
                        AND tunnus  != '{$row['tunnus']}'";
              $ohno  = pupe_query($query);
            }
          }
        }
      }

      // Valitaan paras varasto ja tulostusalue alkuper‰iselle osalla
      asort($varasto_kpl_array);
      asort($tulalue_kpl_array);

      $varasto_kpl_array = array_keys($varasto_kpl_array);
      $tulalue_kpl_array = array_keys($tulalue_kpl_array);

      // Jos tilauksella ei ole yht‰‰n sopivaa rivi‰ (esim vaan JT/PUUTE), s‰ilytet‰‰n varasto jonka k‰ytt‰j‰ valitsi
      if (count($varasto_kpl_array) == 0) {
        $varasto = $myy_varastosta_varasto;
      }
      else {
        $varasto = array_pop($varasto_kpl_array);
      }

      $tulostusalue = array_pop($tulalue_kpl_array);

      $query = "SELECT ulkoinen_jarjestelma, erikoistoimitus_toimitustapa
                FROM varastopaikat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$varasto}'";
      $ulkoinen_jarjestelma_res = pupe_query($query);
      $varaston_row = mysql_fetch_assoc($ulkoinen_jarjestelma_res);

      // Intrastat-tiedot
      $laskurow = palauta_intrastat_tiedot($laskurow, $varasto);

      // Rahtivapaat tilaukset l‰hetet‰‰n aina l‰hett‰j‰n rahtisopimuksella
      $toimitustapalisa = ($row['erikoistoimitus_myynti'] == 1 and $varaston_row['erikoistoimitus_toimitustapa'] != '') ? ", toimitustapa = '$varaston_row[erikoistoimitus_toimitustapa]', rahtivapaa = 'o', kohdistettu = 'K' " : '';

      if ($yhtiorow['kerayserat'] == 'K' and isset($toimitustavan_lahto) and isset($toimitustavan_lahto[$varasto]) and $toimitustavan_lahto[$varasto] > 0) {
        $toimitustavan_lahto_arvo = $toimitustavan_lahto[$varasto];
        $laskurow['toimitustavan_lahto'] = $toimitustavan_lahto_arvo;
      }
      else {
        $toimitustavan_lahto_arvo = 0;
      }

      $laskurow['ohjausmerkki'] = paivita_ohjausmerkki($laskurow);

      $ohjausmerkkilisa = "";

      // Jos sarjatilaus ja ohjausmerkki puuttuu
      if ($omalle_tilaukselle > 0 and trim($laskurow['ohjausmerkki']) == "") {
        $ohjausmerkkilisa = " , ohjausmerkki = '{$ohjausmerkki_nro}-{$laskurow['vanhatunnus']}' ";
      }

      //p‰ivitet‰‰n laskulle tieto mihin varastoon se kuuluu
      $query = "UPDATE lasku set
                varasto                          = '$varasto',
                tulostusalue                     = '$tulostusalue',
                toimitustavan_lahto              = '$toimitustavan_lahto_arvo',
                ultilno                          = '$laskurow[ultilno]',
                aktiivinen_kuljetus              = '$laskurow[aktiivinen_kuljetus]',
                aktiivinen_kuljetus_kansallisuus = '$laskurow[aktiivinen_kuljetus_kansallisuus]',
                bruttopaino                      = '$laskurow[bruttopaino]',
                kauppatapahtuman_luonne          = '$laskurow[kauppatapahtuman_luonne]',
                kontti                           = '$laskurow[kontti]',
                kuljetusmuoto                    = '$laskurow[kuljetusmuoto]',
                lisattava_era                    = '$laskurow[lisattava_era]',
                maa_lahetys                      = '$laskurow[maa_lahetys]',
                maa_maara                        = '$laskurow[maa_maara]',
                poistumistoimipaikka             = '$laskurow[poistumistoimipaikka]',
                poistumistoimipaikka_koodi       = '$laskurow[poistumistoimipaikka_koodi]',
                sisamaan_kuljetus                = '$laskurow[sisamaan_kuljetus]',
                sisamaan_kuljetusmuoto           = '$laskurow[sisamaan_kuljetusmuoto]',
                sisamaan_kuljetus_kansallisuus   = '$laskurow[sisamaan_kuljetus_kansallisuus]',
                vahennettava_era                 = '$laskurow[vahennettava_era]'
                {$ohjausmerkkilisa}
                {$toimitustapalisa}
                where yhtio                      = '$kukarow[yhtio]'
                and tunnus                       = '$temp_laskutunnus'";
      $varresult = pupe_query($query);

      $laskurow["varasto"] = $varasto;

      // Jos tilaus splittaantui moneen osaan
      if (count($tilausnumerot) > 1) {
        $tilauksia = 1;
        $numerot = implode(', ', $tilausnumerot);

        foreach ($tilausnumerot as $tilausnumero) {
          $upviesti = t("Tilaus jakaantunut")." ".count($tilausnumerot)." ".t("tilaukseksi").": ".$numerot." ".t("Tilaus").": ".$tilauksia."/".count($tilausnumerot);

          $query = "UPDATE lasku set sisviesti2 = concat_ws(' ', sisviesti2, '$upviesti')
                    where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
          $upresult = pupe_query($query);

          $tilauksia++;
        }
      }

      // Laitetaan splittien toim ja kerpvm ‰t kuntoon / viikkojutut on pakko nollata splitille
      if ($yhtiorow["splittauskielto"] == "K" and count($tilausnumerot) > 1) {
        foreach ($tilausnumerot as $tilausnumero) {
          $query = "SELECT min(kerayspvm) kerayspvm, min(toimaika) toimaika
                    from tilausrivi
                    where yhtio='$kukarow[yhtio]'
                    and otunnus='$tilausnumero'
                    AND tyyppi != 'D'";
          $result = pupe_query($query);
          $toimaikatilrirow = mysql_fetch_assoc($result);

          $query = "SELECT kerayspvm, toimaika
                    from lasku
                    where yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero'";
          $result = pupe_query($query);
          $toimaikalaskurow = mysql_fetch_assoc($result);

          if ($toimaikatilrirow["kerayspvm"] != substr($toimaikalaskurow["kerayspvm"], 0, 10) or $toimaikatilrirow["toimaika"] != $toimaikalaskurow["toimaika"]) {
            $query = "UPDATE lasku
                      set kerayspvm   = '$toimaikatilrirow[kerayspvm]',
                      toimaika    = '$toimaikatilrirow[toimaika]',
                      keraysvko   = '',
                      toimvko     = ''
                      where yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero'";
            $upresult = pupe_query($query);
          }
        }
      }

      // Varmistetaan, ett‰ jokaisella tilauksella on l‰htˆ
      foreach ($tilausnumerot as $tilausnumero) {
        $query = "SELECT *
                  FROM lasku
                  WHERE yhtio             = '$kukarow[yhtio]'
                  AND tunnus              = '$tilausnumero'
                  AND toimitustavan_lahto = 0";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {

          $lahtorow = mysql_fetch_assoc($result);

          if ($yhtiorow['kerayserat'] != 'K') continue;

          // Tilaustyyppi 2, eli varastot‰ydennys, siirret‰‰n aina yhden p‰iv‰n eteenp‰in
          $eteenpain = ($lahtorow["tilaustyyppi"] == 2) ? 1 : 0;

          $lahdot = seuraavat_lahtoajat($lahtorow["toimitustapa"], $lahtorow["prioriteettinro"], $lahtorow["varasto"], 0, $eteenpain);

          if ($lahdot !== FALSE) {
            // Otetaan eka l‰htˆ
            $valitu_lahto = array_shift($lahdot);

            // Haetaan l‰hdˆn takaa ohjausmerkki
            $lahtorow['toimitustavan_lahto'] = $valitu_lahto['tunnus'];
            $_ohjausmerkki = paivita_ohjausmerkki($lahtorow, false);

            $query = "UPDATE lasku
                      SET toimitustavan_lahto = '$valitu_lahto[tunnus]'
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND tunnus  = '$tilausnumero'";
            $upresult = pupe_query($query);
          }
        }
      }

      //tyhjennet‰‰n muuttujat
      $varasto   = '';
      $edvarasto   = '';

      // loopataan kaikki yhdest‰ tilauksesta syntyneet l‰hetteet
      // jos halutaan tilausvahvistus aluper‰isest‰ tilauksesta
      if ($laskurow["tunnusnippu"] > 0 or $yhtiorow["tilausvahvistus_lahetys"] == 1) {

        // Jos tullaan JT-selauksen kautta niin ei laiteta vahvistuksia jos yhtiˆll‰ ei oo erikseen haluttu niit‰
        if (isset($tilausvalmiskutsuja) and $tilausvalmiskutsuja == "JTSELAUS" and (($yhtiorow["tilausvahvistus_jttoimituksista"] == "" and $tilausvalmis_asiakas_row["tilausvahvistus_jttoimituksista"] != "K") or $tilausvalmis_asiakas_row["tilausvahvistus_jttoimituksista"] == "E")) {
          $laskurow['tilausvahvistus'] = "";
        }

        if (trim($laskurow['tilausvahvistus']) != "") {
          //
          // LƒHETETƒƒN TILAUSVAHVISTUS
          //
          // T‰ss‰ haarassa tulostetaan tilausvahvistus KOKO tilauksesta
          $params_tilausvahvistus = array(
            'tee'            => $tee,
            'toim'            => $toim,
            'kieli'            => $kieli,
            'komento'          => $komento,
            'laskurow'          => $laskurow,
            'naytetaanko_rivihinta'    => $naytetaanko_rivihinta,
            'extranet_tilausvahvistus'  => $extranet_tilausvahvistus,
          );

          laheta_tilausvahvistus($params_tilausvahvistus);
        }

        ///* T‰ss‰ katotaan onko l‰hetteell‰ tosiaan mit‰‰n ker‰tt‰v‰‰ *///

        //  Ja rullataan kaikki tilaukset l‰pi
        foreach ($tilausnumerot as $tilausnumero) {
          //luetaan laskun tiedot uudestaan t‰ss‰, per splitti
          $query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
          $result = pupe_query($query);
          $laskurow = mysql_fetch_assoc($result);

          if (!empty($sahkoinen_lahete) and isset($generoi_sahkoinen_lahete) and trim($generoi_sahkoinen_lahete) != "" and $kukarow["extranet"] == "" and !empty($sahkoinen_lahete_toim) and in_array($toim, $sahkoinen_lahete_toim)) {

            require_once "inc/sahkoinen_lahete.class.inc";

            sahkoinen_lahete($laskurow);
          }

          $kutsuja = "tilaus-valmis.inc";

          require "tilaus-valmis-valitsetila.inc";
        }
      }
      else {
        foreach ($tilausnumerot as $tilausnumero) {

          //luetaan laskun tiedot uudestaan t‰ss‰, per splitti
          $query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tilausnumero'";
          $result = pupe_query($query);
          $laskurow = mysql_fetch_assoc($result);

          // Jos tullaan JT-selauksen kautta niin ei laiteta vahvistuksia jos yhtiˆll‰ ei oo erikseen haluttu niit‰
          if (isset($tilausvalmiskutsuja) and $tilausvalmiskutsuja == "JTSELAUS" and (($yhtiorow["tilausvahvistus_jttoimituksista"] == "" and $tilausvalmis_asiakas_row["tilausvahvistus_jttoimituksista"] != "K") or $tilausvalmis_asiakas_row["tilausvahvistus_jttoimituksista"] == "E")) {
            $laskurow['tilausvahvistus'] = "";
          }

          if (trim($laskurow['tilausvahvistus']) != "") {
            //
            // LƒHETETƒƒN TILAUSVAHVISTUS
            //
            // tulostetaan t‰ss‰, niin saadaan vahvistukseen koko tilaus, ennenkun sen splitaatan eri varastoihin
            $params_tilausvahvistus = array(
              'tee'            => $tee,
              'toim'            => $toim,
              'kieli'            => $kieli,
              'komento'          => $komento,
              'laskurow'          => $laskurow,
              'naytetaanko_rivihinta'    => $naytetaanko_rivihinta,
              'extranet_tilausvahvistus'  => $extranet_tilausvahvistus,
            );

            laheta_tilausvahvistus($params_tilausvahvistus);
          }

          if (!empty($sahkoinen_lahete) and isset($generoi_sahkoinen_lahete) and trim($generoi_sahkoinen_lahete) != "" and $kukarow["extranet"] == "" and !empty($sahkoinen_lahete_toim) and in_array($toim, $sahkoinen_lahete_toim)) {

            require_once "inc/sahkoinen_lahete.class.inc";

            sahkoinen_lahete($laskurow);
          }

          ///* T‰ss‰ katotaan onko l‰hetteell‰ tosiaan mit‰‰n ker‰tt‰v‰‰ *///
          $kutsuja = "tilaus-valmis.inc";

          require "tilaus-valmis-valitsetila.inc";
        }
      }

      // katsotaan onko yht‰‰n jt-rivi‰ tilauksella
      // jos jt-rivej‰ lˆytyy ja yhtiˆn parametreiss‰ jt_email ei ole tyhj‰, l‰hetet‰‰n j‰lkitoimitus-s‰hkˆposti jt-email-kent‰ss‰ olevaan s‰hkˆpostiosoitteeseen
      foreach ($tilausnumerot as $tilausnumero) {

        $query = "SELECT lasku.nimi,
                  lasku.nimitark,
                  lasku.ytunnus,
                  lasku.yhtio_toimipaikka,
                  asiakas.asiakasnro,
                  tilausrivi.tuoteno,
                  tilausrivi.hyllyalue,
                  tilausrivi.hyllynro,
                  tilausrivi.hyllyvali,
                  tilausrivi.hyllytaso,
                  tilausrivi.varasto,
                  tuote.nimitys,
                  tuote.tuotemerkki,
                  tuote.tahtituote,
                  tuote.hinnastoon,
                  tuote.status,
                  lasku.yhtio,
                  tilausrivi.jt+tilausrivi.varattu kpl
                  FROM lasku
                  JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND
                            tilausrivi.otunnus  = lasku.tunnus AND
                            tilausrivi.tyyppi  != 'D' AND
                            tilausrivi.var      = 'J' AND
                            tilausrivi.tyyppi   in ('L','G') AND
                            tilausrivi.jt+tilausrivi.varattu > 0)
                  JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                  JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
                  WHERE lasku.yhtio             = '$kukarow[yhtio]'
                  AND lasku.tunnus              = '$tilausnumero'";
        $jt_chk_res = pupe_query($query);

        if (mysql_num_rows($jt_chk_res) > 0) {

          $kutsu = "";
          $subject = "";
          $content_body_header = "";
          $content_body = "";

          $jt_chk_row = mysql_fetch_assoc($jt_chk_res);

          if ($yhtiorow['jt_email'] == '') continue;

          $kutsu = "J‰lkitoimitus";
          $subject = t("Tuotetta j‰tetty j‰lkitoimitukseen");
          $content_body_header  = $yhtiorow['nimi']."\n".t("Asiakas", $kieli).": $jt_chk_row[nimi] $jt_chk_row[nimitark]\n\n";
          $content_body_header .= t("Ytunnus", $kieli).": $jt_chk_row[ytunnus]\n";
          $content_body_header .= t("Asiakasnumero", $kieli).": $jt_chk_row[asiakasnro]\n\n";

          mysql_data_seek($jt_chk_res, 0);

          while ($jt_chk_row = mysql_fetch_assoc($jt_chk_res)) {

            if ($yhtiorow['jt_email_tilauksessa'] == 'K') {

              $tulossa_query = "SELECT sum(varattu) tulossa_kpl
                                FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
                                WHERE yhtio        = '{$kukarow['yhtio']}'
                                AND tuoteno        = '{$jt_chk_row['tuoteno']}'
                                AND tyyppi         = 'O'
                                AND laskutettuaika = '0000-00-00'
                                AND varasto        = '{$jt_chk_row['varasto']}'";
              $tulossa_result = pupe_query($tulossa_query);
              $tulossa_row = mysql_fetch_assoc($tulossa_result);

              list($_, $__, $myytavissa) = saldo_myytavissa($jt_chk_row['tuoteno'], '', $jt_chk_row['varasto']);

              if ($tulossa_row['tulossa_kpl'] >= $myytavissa) {
                continue;
              }
            }

            $content_body .= "$jt_chk_row[kpl] kpl ".t("j‰lkitoimituksen tuotenumerosta")." $jt_chk_row[tuoteno] ".t("tilauksella")." $tilausnumero\n\n";
            $content_body .= t("Tuotepaikka").": $jt_chk_row[hyllyalue]-$jt_chk_row[hyllynro]-$jt_chk_row[hyllyvali]-$jt_chk_row[hyllyvtaso]\n\n";
            $content_body .= t("Nimitys").": $jt_chk_row[nimitys]\n";
            $content_body .= t("Tuotemerkki").": $jt_chk_row[tuotemerkki]\n";
            $content_body .= t("Tahtituote").": $jt_chk_row[tahtituote]\n";
            $content_body .= t("Hinnastoon").": $jt_chk_row[hinnastoon]\n";
            $content_body .= t("Status").": $jt_chk_row[status]\n\n\n";
          }

          if (!empty($content_body)) {
            $content_body = $content_body_header.$content_body;

            // S‰hkˆpostin l‰hetykseen parametrit
            $parametri = array(
              "to"           => $yhtiorow["jt_email"],
              "cc"           => "",
              "subject"      => $subject,
              "ctype"        => "text",
              "body"         => $content_body,
              "attachements" => "",
            );

            pupesoft_sahkoposti($parametri);

            echo t("L‰hetettiin j‰lkitoimitus-s‰hkˆposti")."...<br><br>";
          }
        }
      }

      @include 'inc/pks_lahete.inc';
    }
  } // end jos yksikin rivi ker‰tty else haara
}

//tyhjennet‰‰n ett‰ se ei kummittele uudella tilauksella
$hinta = '';

if ($luottorajavirhe_ylivito_valmis) {
  // tilaus ei en‰‰ kesken...
  $query  = "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
  $result = pupe_query($query);
}

if (!isset($silent) or $silent == "") {
  echo $tilaus_valmis_ulos;
}
