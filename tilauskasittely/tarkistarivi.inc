<?php

$params = array(
  'asiakas_tunnus' => $laskurow['liitostunnus'],
  'toimipaikka_tunnus' => $laskurow['yhtio_toimipaikka'],
);

$varastot_array = sallitut_varastot($params);

//sis‰‰n tulee $row jossa on tilausrivi kaikki tiedot

$riviok                         = 0;
$saako_hyvaksya                 = 0;
$ei_saa_hyvaksya_kaikkia_riveja = 0;
$saako_jalkitoimittaa           = 0;
$vastaavattuotteet              = 0;
$varaosavirhe                   = "";
$vastaavat_html                 = "";

pupeslave_start();

$tquery  = "SELECT *
            FROM tuote
            WHERE yhtio = '$kukarow[yhtio]'
            and tuoteno = '$row[tuoteno]'";
$tres  = pupe_query($tquery);
$trow   = mysql_fetch_assoc($tres); // haetaan tuotteen tiedot..

if ($toim == "YLLAPITO") {
  // Jos ollaan syˆtetty poikkeavat sopimuksen alku-/loppup‰iv‰t tilausriville, tarkistetaan ett‰ ne on oikeelliset
  if ($row["kerayspvm"] != '0000-00-00' or $row["toimaika"] != '0000-00-00') {

    // tilausrivi.kerayspvm == poikkeava alkup‰iv‰
    if ($row["kerayspvm"] != '0000-00-00' and $row["kerayspvm"] < $laskurow["sopimus_alkupvm"]) {
      $varaosavirhe .= t("VIRHE: Poikkeava alkup‰iv‰ ei voi olla vanhempi kun sopimuksen alkup‰iv‰!")."<br>";
      $riviok++;
      $tilausok++;
    }

    if ($row["kerayspvm"] != '0000-00-00' and $row["kerayspvm"] > $laskurow["sopimus_loppupvm"]) {
      $varaosavirhe .= t("VIRHE: Poikkeava alkup‰iv‰ ei voi olla uudempi kun sopimuksen loppup‰iv‰!")."<br>";
      $riviok++;
      $tilausok++;
    }

    // tilausrivi.toimaika == poikkeava loppup‰iv‰
    if ($row["toimaika"] != '0000-00-00' and $row["toimaika"] < $laskurow["sopimus_alkupvm"]) {
      $varaosavirhe .= t("VIRHE: Poikkeava loppup‰iv‰ ei voi olla vanhempi kun sopimuksen alkup‰iv‰!")."<br>";
      $riviok++;
      $tilausok++;
    }

    if ($row["toimaika"] != '0000-00-00' and $row["toimaika"] > $laskurow["sopimus_loppupvm"] and $laskurow["sopimus_loppupvm"] != "0000-00-00") {
      $varaosavirhe .= t("VIRHE: Poikkeava loppup‰iv‰ ei voi olla uudempi kun sopimuksen loppup‰iv‰!")."<br>";
      $riviok++;
      $tilausok++;
    }

    if ($row["kerayspvm"] != '0000-00-00' and $row["toimaika"] != '0000-00-00' and $row["kerayspvm"] > $row["toimaika"]) {
      $varaosavirhe .= t("VIRHE: Poikkeava alkup‰iv‰ ei voi olla uudempi kun poikkeava loppup‰iv‰!")."<br>";
      $riviok++;
      $tilausok++;
    }
  }
}

if ($trow['panttitili'] == 'K' and in_array($toim, array('PIKATILAUS', 'RIVISYOTTO', 'REKLAMAATIO'))) {
  $query = "SELECT panttitili
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$laskurow['liitostunnus']}'";
  $chk_res = pupe_query($query);
  $chk_row = mysql_fetch_assoc($chk_res);

  if ($chk_row['panttitili'] == 'K' and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS' or $toim == 'REKLAMAATIO')) {
    if ($row['varattu'] < 0) {
      if ($row['var2'] == 'PANT') {
        $varaosavirhe .= t("HUOM: Panttituote, hyvitet‰‰n p‰itt‰in alkuper‰inen panttiveloitus")."<br>";
      }
      else {
        $varaosavirhe .= t("HUOM: Panttitilillinen tuote, tuote hyvitet‰‰n panttitililt‰")."<br>";
      }
    }
    else {
      if ($row['var2'] == 'PANT') {
        $varaosavirhe .= t("HUOM: Panttituote, veloitetaan asiakkaalta")."<br>";
      }
      else {
        $varaosavirhe .= t("HUOM: Panttitilillinen tuote, veloitus siirret‰‰n panttitilille")."<br>";
      }
    }
  }
}

$_onko_myynti = in_array($toim, array('PIKATILAUS', 'RIVISYOTTO'));
$_onko_param = ($yhtiorow['myynti_jt_huom'] == 'K');

if ($_onko_param and $_onko_myynti and $kukarow['extranet'] == '') {

  if ($yhtiorow["varaako_jt_saldoa"] != "") {
    $lisavarattu = " + tilausrivi.varattu";
  }
  else {
    $lisavarattu = "";
  }

  $query = "SELECT *
            FROM lasku
            JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus  = lasku.tunnus
              AND tilausrivi.tuoteno  = '{$row['tuoteno']}'
              AND tilausrivi.tyyppi   = 'L'
              AND tilausrivi.var      = 'J'
              AND tilausrivi.jt {$lisavarattu} > 0
              AND tilausrivi.tunnus  != '{$row['tunnus']}'
            )
            WHERE lasku.yhtio         = '{$kukarow['yhtio']}'
            AND lasku.liitostunnus    = '{$laskurow['liitostunnus']}'
            AND lasku.tunnus         != '{$laskurow['tunnus']}'
            AND (lasku.tila = 'N' OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','BD','C','D','X')))";
  $chk_res = pupe_query($query);

  if (mysql_num_rows($chk_res) != 0) {
    $varaosavirhe .= t("HUOM: tuotetta on jo j‰lkitoimituksessa")."<br>";
  }
}

$_onko_param = ($yhtiorow['myynti_jt_huom'] == 'T');

if ($_onko_param and $_onko_myynti and $kukarow['extranet'] == '') {


  $query = "SELECT *
            FROM lasku
            JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus  = lasku.tunnus
              AND tilausrivi.tuoteno  = '{$row['tuoteno']}'
              AND tilausrivi.tyyppi   = 'T'
              AND tilausrivi.tunnus  != '{$row['tunnus']}'
            )
            WHERE lasku.yhtio         = '{$kukarow['yhtio']}'
            AND lasku.liitostunnus    = '{$laskurow['liitostunnus']}'
            AND lasku.tunnus         != '{$laskurow['tunnus']}'
            AND (lasku.tila = 'T' AND lasku.alatila IN ('','A'))";
  $chk_res = pupe_query($query);

  if (mysql_num_rows($chk_res) != 0) {
    $varaosavirhe .= t("HUOM: tuotetta on tarjouksella")."<br>";
  }
}

if (isset($alkupera_trow) and $yhtiorow["korvaavat_hyvaksynta"] != "" and strtoupper($alkupera_trow["tuoteno"]) != strtoupper($row["tuoteno"])
  and strtoupper($row["tuoteno"]) == strtoupper($krow2["tuoteno"])
  and (!isset($from_mikrotilaus) or (isset($from_mikrotilaus) and !$from_mikrotilaus))
  and !in_array($row["var2"], array("OK", "PK")) and $korvattiin_korvaavalla == "HIIOHEI!") {
  $varaosavirhe .= t("HUOM: t‰m‰ on korvaava tuote, haluatko hyv‰ksy‰?")."<br>";
  $riviok++;
  $tilausok++;
  $saako_hyvaksya++;
  $korvattiin_korvaavalla = "";
}

$logmaster_errors = logmaster_verify_row($row['tunnus'], $toim);

foreach ($logmaster_errors as $error) {
  echo "<font class='error'>";
  echo "{$error}<br><br>";
  echo "</font>";
  $tilausok++;
  $riviok++;
}

if ($kukarow['extranet'] != '' and $trow['sarjanumeroseuranta'] != '' and $row['varattu'] != 1) {
  $varaosavirhe .= t("HUOM: T‰m‰ on sarjanumeroseurannallinen tuote. Kappalem‰‰r‰ pit‰‰ olla 1")."!<br>";
  $riviok++;
  $tilausok++;
}

$pikaperustus_naytetaan = 0;

if (mysql_num_rows($tres) == 0) {
  $varaosavirhe .= t("VIRHE: Tuotetta ei lˆydy!")."<br>";
  $riviok++;
  $tilausok++;
  $pikaperustus_naytetaan = 1;
}

if ($row["var2"] == "PK" and ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") and $yhtiorow['reklamaation_hinnoittelu'] == 'K') {
  $varaosavirhe .= t("Palautuskielto!")."<br>";
  $riviok++;
  $tilausok++;
}

if (
  $row["hinta"] == 0
  and $row["tyyppi"] != "V"
  and !in_array($row["var2"], array("OK", "PK"))
  and !in_array(strtoupper($trow["tuotetyyppi"]), array('R', 'M'))
  and !in_array($toim, array("VALMISTAVARASTOON", "SIIRTOLISTA", "SIIRTOTYOMAARAYS", "MYYNTITILI"))
) {
  if ($trow['ei_saldoa'] == '' and ($row['perheid'] == 0 or $row['perheid'] == $row['tunnus'])) {
    $varaosavirhe .= t("HUOM: Hinta puuttuu!")."<br>";
    $riviok++;
    $tilausok++;
    $saako_hyvaksya++;
  }
}

$tilausrivin_kateraja = $yhtiorow["tilausrivin_kateraja"];
$tilausrivin_kate = laske_tilausrivin_kate($row, $kotisumma_alviton, $row["kehahin"], $kpl, $arow);

if ($tilausrivin_kateraja > 0 and
  $tilausrivin_kate < $tilausrivin_kateraja and $row["var2"] != "OK"
) {
  $varaosavirhe .= t("HUOM: Kate alhainen!")."<br>";
  $riviok++;
  $ei_saa_hyvaksya_kaikkia_riveja++;
  $tilausok++;
  $saako_hyvaksya++;
}

if (in_array($row["var"], array('', 'H')) and $row["tyyppi"] != "E" and $row["tyyppi"] != "G") {
  if (($laskurow["tilaustyyppi"] != "H" and $row["varattu"] == 0) or
    ($laskurow["tilaustyyppi"] == "H" and $row["tilkpl"] == 0)
  ) {
    if ($kukarow['extranet'] != '' and $row['positio'] == 'Ei varaa saldoa') {
      //    $varaosavirhe .=
      //      t("HUOM: Rivi on liian vanha ja tuote ei varaa saldoa") .
      //      "! ($row[tilkpl] " .
      //      t("kpl") .
      //      ")<br/>";
    }
    else {
      $varaosavirhe .= t("VIRHE: M‰‰r‰ puuttuu!") . "<br>";
    }
    $riviok++;
    $tilausok++;
  }
}

if (in_array($row["var"], array('S', 'U', 'T', 'R', 'J')) and $row["tyyppi"] != "E") {
  if (($yhtiorow["varaako_jt_saldoa"] == "") and $row["jt"] <= 0) {
    $varaosavirhe .= t("VIRHE: M‰‰r‰ puuttuu tai se on negatiivinen!")."<br>";
    $riviok++;
    $tilausok++;
  }
  if ($yhtiorow["varaako_jt_saldoa"] != "" and $row["varattu"] <= 0) {
    $varaosavirhe .= t("VIRHE: M‰‰r‰ puuttuu tai se on negatiivinen!")."<br>";
    $riviok++;
    $tilausok++;
  }
}

if (in_array($row['var'], array('U', 'T', 'S')) and $meapurow['kateinen'] != '') {

  if ($row['var'] == 'U') {
    $varaosavirhe .= t("VIRHE: Et voi tilata toimittajalta asiakkaalle, koska maksuehto on k‰teinen!")."<br>";
  }
  elseif ($row['var'] == 'T') {
    $varaosavirhe .= t("VIRHE: Et voi tilata toimittajalta varastoon, koska maksuehto on k‰teinen!")."<br>";
  }
  else {
    $varaosavirhe .= t("VIRHE: Et voi tehd‰ varastosiirtoa, koska maksuehto on k‰teinen!")."<br>";
  }

  $riviok++;
  $tilausok++;
}

if ($row['var'] == 'U' and $meapurow['jv'] != '') {

  $varaosavirhe .= t("VIRHE: Et voi tilata toimittajalta asiakkaalle, koska maksuehto on j‰lkivaatimus!")."<br>";
  $riviok++;
  $tilausok++;
}

// katsotaan, ettei valmisteta ep‰kuranttia tuotetta
if (($toim == "VALMISTAVARASTOON" or $toim == 'VALMISTAASIAKKAALLE') and $trow["epakurantti25pvm"] != '0000-00-00' and ($row["tyyppi"] == "W" or $row["tyyppi"] == "M") and $riviok == 0) {
  $varaosavirhe .= t("VIRHE: Tuote on ep‰kurantti, sit‰ ei saa valmistaa!");
  $riviok++;
  $tilausok++;
}

if (in_array($row['var'], array('U', 'T', 'R')) and $trow['epakurantti25pvm'] != '0000-00-00') {
  $varaosavirhe .= t("HUOM: tuote on ep‰kurantti!")."<br>";
}

if ($row["hinta"] < 0) {
  $varaosavirhe .= t("VIRHE: Hinta ei voi olla negatiivinen!")."<br>";
  $riviok++;
  $tilausok++;
}

if (
  (in_array($row["var"], array('J' , 'S' , 'U', 'T')) and $row["jt"] < 0)
  or (in_array($row["tyyppi"], array('V', 'W', 'M', 'E')) and $row["varattu"] < 0 )
) {
  $varaosavirhe .= t("VIRHE: M‰‰r‰ ei saa olla negatiivinen!")."<br>";
  $riviok++;
  $tilausok++;
}

if ($row["var"] == "J" and $row["tyyppi"] == "E") {
  $varaosavirhe .= t("VIRHE: Et voi syˆtt‰‰ jt-rivi‰ ennakkotilaukseen!")."<br>";
  $riviok++;
  $tilausok++;
}

//  Pakkokommentoitava tuote
$_kommentoitava_kaikki = (in_array($trow["kommentoitava"], array("o", "p")));
$_kommentoitava_rekla  = ($trow["kommentoitava"] == "r" and in_array($toim, array("REKLAMAATIO", "EXTRANET_REKLAMAATIO")));
$_kommentoitava = ($_kommentoitava_kaikki or $_kommentoitava_rekla);

if ($_kommentoitava and $row["kommentti"] == "") {
  $varaosavirhe .= t("VIRHE: Tuotetta on kommentoitava!")."<br>";
  $riviok++;
  $tilausok++;
}

if (!in_array($row["var"], array('', 'P', 'J', 'H', 'S', 'T', 'U', 'V', 'A', 'B', 'R', 'O'))) {
  // T‰ss‰ varrien selitykset
  // P - Puute
  // J - Jt
  // H - V‰kisinhyv‰ksytty
  // T - Tilataan joltain toimittajalta omaan varastoon
  // U - Tilataan joltain toimittajalta suoraan asiakkaalle
  // V - Tehdaslis‰varusterivi
  // A - Myyntitilirivi joka laskutetaan asiakkaalta
  // B - Myyntitilirivi joka palautetaan myyntitilist‰ omaan varastoon
  // R - Rakennettava rivi
  // S - Myyntitilaukselle korvamerkitty varastosiirto
  // O - Optio

  $varaosavirhe .= t("VIRHE: Var voi olla tyhj‰, P, J, H, S, T, U, V, A, B, O tai R!")."<br>";
  $riviok++;
  $tilausok++;
}

if (in_array($row["tyyppi"], array('V', 'W')) and $row["perheid"] == 0 and $toim == "VALMISTAVARASTOON") {
  $varaosavirhe .= t("VIRHE: Tuote ei kuulu mihink‰‰n reseptiin!")."<br>";
  $riviok++;
  $tilausok++;
}

if ($row["var"] != '' and $toim == "SIIRTOTYOMAARAYS") {
  $varaosavirhe .= t("VIRHE: Sis‰isell‰ tyˆm‰‰r‰yksell‰ Var pit‰‰ olla tyhj‰!")."<br>";
  $riviok++;
  $tilausok++;
}

if (($row["perheid2"] == $row["tunnus"] or $row["perheid2"] == 0) and $row["sarjanumeroseuranta"] == "" and $toim == "SIIRTOTYOMAARAYS") {
  $varaosavirhe .= t("VIRHE: Jatkojalostettava tuote on oltava sarjanumeroeurannassa!")."<br>";
  $riviok++;
  $tilausok++;
}

if (($row["perheid2"] == $row["tunnus"] or $row["perheid2"] == 0) and $row["varattu"] != 1 and $toim == "SIIRTOTYOMAARAYS") {
  $varaosavirhe .= t("VIRHE: Jatkojalostettava m‰‰r‰ on oltava yksi!")."<br>";
  $riviok++;
  $tilausok++;
}

if ($row["perheid2"] == 0 and $row["sarjanumeroseuranta"] != "" and $toim == "SIIRTOTYOMAARAYS") {
  $varaosavirhe .= t("HUOM: Lis‰varusteita ei ole liitetty!")."<br>";
  $riviok++;
  $tilausok++;
}

if ($row["varattu"] <= 0 and $toim == "SIIRTOTYOMAARAYS") {
  $varaosavirhe .= t("HUOM: M‰‰r‰ on oltava suurempi kuin nolla!")."<br>";
  $riviok++;
  $tilausok++;
}

if (!in_array($row["netto"], array('', 'N', 'E'))) {
  $varaosavirhe .= t("VIRHE: Netto voi olla tyhj‰, N tai E!")."<br>";
  $riviok++;
  $tilausok++;
}

for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
  if ($row["netto"] == 'N' and (float) $row["ale{$alepostfix}"] != 0) {
    $varaosavirhe .= t("VIRHE: Nettoriville ei voi antaa alennusta!")."<br>";
    $riviok++;
    $tilausok++;
  }

  if ($row["ale{$alepostfix}"] != '' and ($row["ale{$alepostfix}"] < 0 or 100 < $row["ale{$alepostfix}"])) {
    $varaosavirhe .= t("VIRHE: Alennus on oltava 0-100!")."<br>";
    $riviok++;
    $tilausok++;
  }
}

if ($kukarow["extranet"] == "" and in_array($trow["status"], array("P", "X")) and !in_array($row["var2"], array("OK", "PK"))) {
  $varaosavirhe .= t("HUOM: T‰m‰ on poistuva tuote!")."<br>";
  $riviok++;
}

if (in_array($trow["status"], array("P", "X")) and $row["var"] == "J") {
  $varaosavirhe .= t("VIRHE: T‰m‰ on poistuva tuote, ei voida j‰tt‰‰ j‰lkitoimitukseen!")."<br>";
  $riviok++;
  $tilausok++;
}

// Jos tilaus on tehdastilaus, niin tilaukselle saa laittaa vain tehdastilaustuotteita
if ($laskurow['tilaustyyppi'] == '7' and $trow['status'] != 'T' and $trow['panttitili'] == '' and $trow["ei_saldoa"] == "") {
  $varaosavirhe .= t("VIRHE: Tuote ei ole tehdastilaustuote")."!<br>";
  $riviok++;
  $tilausok++;
}

if ($yhtiorow['tilausrivi_omalle_tilaukselle'] != '' and $row['omalle_tilaukselle'] != '' and fmod($row['varattu'], 1) != 0) {
  $varaosavirhe .= t("VIRHE: Kappalem‰‰r‰ pit‰‰ olla kokonaisluku")."!<br>";
  $riviok++;
  $tilausok++;
}

if (trim($trow["vienti"]) != '' and $row["tyyppi"] != "V" and $row["tyyppi"] != "W" and ($row["var2"] != "OK" or $yhtiorow["vientikiellon_ohitus"] != "K")) {

  // vientikieltok‰sittely:
  // +maa tarkoittaa ett‰ myynti on kielletty t‰h‰n maahan
  // -maa tarkoittaa ett‰ ainoastaan t‰h‰n maahan saa myyd‰
  // eli n‰ytet‰‰n vaan tuotteet jossa vienti kent‰ss‰ on tyhj‰‰ tai -maa.. ja se ei saa olla +maa

  if (strpos(strtoupper($trow["vienti"]), strtoupper("+$laskurow[toim_maa]")) !== FALSE and strpos($trow["vienti"], "+") !== FALSE) {
    //ei saa myyd‰ t‰h‰n maahan
    $varaosavirhe .= t("VIRHE: T‰t‰ tuotetta ei saa myyd‰ maahan")." $laskurow[toim_maa]!<br>";
    $riviok++;
    $tilausok++;
    $kielletty++;

    // Vientikiellossa olevan tuotteen voi hyv‰ksy‰ myyntitilaukselle
    if ($yhtiorow["vientikiellon_ohitus"] == "K" and $kukarow["extranet"] == "") {
      $saako_hyvaksya++;
    }
  }

  if (strpos(strtoupper($trow["vienti"]), strtoupper("-$laskurow[toim_maa]")) === FALSE and strpos($trow["vienti"], "-") !== FALSE) {
    //ei saa myyd‰ t‰h‰n maahan
    $varaosavirhe .= t("VIRHE: T‰t‰ tuotetta ei saa myyd‰ maahan")." $laskurow[toim_maa]!!<br>";
    $riviok++;
    $tilausok++;
    $kielletty++;

    // Vientikiellossa olevan tuotteen voi hyv‰ksy‰ myyntitilaukselle
    if ($yhtiorow["vientikiellon_ohitus"] == "K" and $kukarow["extranet"] == "") {
      $saako_hyvaksya++;
    }
  }
}

// Otetaan oikea kappalem‰‰r‰ k‰sittelyyn
if (in_array($row["var"], array('J', 'S', 'T', 'U'))) {
  // jos yhtiˆn parametreiss‰ jt-rivit varaavat saldoa, laitetaan "varattu kpl"-m‰‰r‰, muuten "jt kpl"-m‰‰r‰
  $kpl_st = $yhtiorow['varaako_jt_saldoa'] != '' ? $row['varattu'] : $row['jt'];
}
elseif ($row["var"] == 'P') {
  $kpl_st = $row['tilkpl'];
}
else {
  $kpl_st = $row['varattu'];
}

if (isset($myyntierahuom) and is_array($myyntierahuom) and in_array($row['tunnus'], $myyntierahuom) and $kpl_st != $org_kpl) {

  $mimyhuom = "HUOM: Rivin m‰‰r‰ on pyˆristetty";

  if ($trow["minimi_era"] == $kpl_st and in_array($yhtiorow['minimimaara_pyoristys'], array('K', 'E'))) {
    $mimyhuom .= " minimier‰‰n";
  }
  elseif ($trow['myynti_era'] > 0 and in_array($yhtiorow['myyntiera_pyoristys'], array('K', 'S'))) {
    $mimyhuom .= " t‰yteen myyntier‰‰n";
  }

  // K‰‰nnet‰‰n teksti
  $mimyhuom = t($mimyhuom)."!";

  if ($trow['myynti_era'] > 0) {
    $mimyhuom .= "<br>".t("Myyntier‰ on").": $trow[myynti_era]";
  }

  if ($trow["minimi_era"] > 0) {
    $mimyhuom .= "<br>".t("Minimier‰ on").": $trow[minimi_era]";
  }

  $varaosavirhe .= $mimyhuom."<br>";
}
else {
  if ($trow['myynti_era'] > 0 and $trow['myynti_era'] != 1 and $trow['myynti_era'] != $kpl_st and !in_array($yhtiorow['myyntiera_pyoristys'], array('K', 'S'))) {
    $mimyhuom = t("HUOM: Tuotteella on myyntier‰"). " " . $trow['myynti_era'];
    $varaosakommentti .= $mimyhuom."<br>";
  }
}

//T‰ss‰ on kaikki saldoihin ja varastopaikkoihin liittyv‰t tsekit, eli EI tehd‰ saldottomille tuotteille, eik‰ tuoteperheen saldollisille lapsille jos koko perhe suoratoimitetaan
$paikat  = "";

$_onko_isatuote = (!empty($row['perheid']) and $row['perheid'] == $row['tunnus']);
$_onko_lapsituote = (!empty($row['perheid']) and $row['perheid'] != $row['tunnus']);
//Jos tilausrivi on lapsituote ja se on varastosiirtotilassa,
//niin laitetaan tilausrivin paikka dropdown disabled tilaan
$paikka_disabled = "";
if ($_onko_lapsituote and $row['var'] == 'S') {
  $paikka_disabled = "disabled";
}

if (
  $trow["ei_saldoa"] == ""
  and !in_array($row["tyyppi"], array('E', 'W'))
  and (
    !in_array($row["var"], array('T', 'U'))
    or $yhtiorow['tuoteperhe_suoratoimitus'] == ''
    or $row["perheid"] == 0
    or $row["perheid"] == $row["tunnus"]
  )
) {
  // tarkistetaan varaston tyyppi
  $query = "SELECT *
            FROM varastopaikat
            WHERE
            concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0')) and
            concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0'))
            and yhtio = '$kukarow[yhtio]'";
  $varesult = pupe_query($query);
  $varow = mysql_fetch_assoc($varesult);

  if ($varow["tyyppi"] == "V" and $laskurow["varasto"] != $varow["tunnus"] and !in_array($row["var2"], array("OK", "PK"))) {
    $varaosavirhe .= t("HUOM: Tuote sijaitsee erikoisvarastossa")."!<br>";
    $riviok++;
    $tilausok++;
    $saako_hyvaksya++;
  }

  if (!empty($yhtiorow["saldo_kasittely"])) {
    $saldoaikalisa = $row["kerayspvm"];
  }
  else {
    $saldoaikalisa = "";
  }

  $paikatlask   = 0;
  $selpaikka    = "";
  $selpaikkamaa  = "";
  $kotipaikatlask = 0;
  $omapaikatraja   = 2;
  $selpaikkamyytavissa = 0;

  // Katsotaan onko t‰lle tuotteelle toimittajaa ja oletusvienti on jotain vaihto-omaisuutta (Tilaa-toimittajalta drop-downiin mukaan)
  if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('A', 'B', 'C', 'I', 'J')) and $yksi_suoratoimittaja != '') {
    $where_yksi_suoratoimittaja = " AND toimi.tunnus = {$yksi_suoratoimittaja} ";
  }
  else {
    $where_yksi_suoratoimittaja = "";
  }

  if (($kukarow["extranet"] == "" or $yhtiorow['tuoteperhe_suoratoimitus'] == 'E') and ($toim == "TARJOUS" or ($yhtiorow["tee_osto_myyntitilaukselta"] != '' and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS'))) and $row["var"] != 'V') {
    $query = "SELECT tyyppi_tieto, liitostunnus, toimi.nimi, toimi.suoratoimitus, tuotteen_toimittajat.tehdas_saldo, toimi.tehdas_saldo_tarkistus
              FROM tuotteen_toimittajat use index (yhtio_tuoteno)
              JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus)
              WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
              and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
              $where_yksi_suoratoimittaja
              ORDER BY if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys)";
    $toimpaikres  = pupe_query($query);

    if (mysql_num_rows($toimpaikres) > 0) {
      $omapaikatraja = 1;
    }
  }

  // katotaan jos myyntitilauksella voidaan tehd‰ valmistusta, niin pudotetaan dropdownin rajaa
  if (($kukarow["extranet"] == "" and $yhtiorow["tee_valmistus_myyntitilaukselta"] != '') and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS')) {
    $omapaikatraja = 1;
  }

  // Katotaan voidaanko t‰m‰ myyd‰ joltain toiselta paikalta
  if (!in_array($row["var"], array('J', 'S', 'T', 'P')) and $kpl_st > 0 and in_array($trow["sarjanumeroseuranta"], array('E', 'F', 'G'))) {

    $paikat .= "<option value='EIERAAVIELA#!°!#EIEI'>".t("Valitse er‰")."</option>";
    $paikatlask++;
    $omapaikatraja = 1;

    if ($trow["sarjanumeroseuranta"] == "F") {
      $pepvmlisa1 = " sarjanumeroseuranta.parasta_ennen, ";
      $pepvmlisa2 = ", 18";
    }
    else {
      $pepvmlisa1 = "";
      $pepvmlisa2 = "";
    }

    $query = "SELECT
              sarjanumeroseuranta.sarjanumero era,
              tuote.ei_saldoa,
              tuote.tuoteno,
              tuote.vakkoodi,
              tuote.yhtio,
              tuotepaikat.hyllyalue,
              tuotepaikat.hyllynro,
              tuotepaikat.hyllytaso,
              tuotepaikat.hyllyvali,
              tuotepaikat.oletus,
              varastopaikat.erikoistoimitus_alarajasumma,
              varastopaikat.maa varastomaa,
              varastopaikat.nimitys,
              varastopaikat.tunnus varasto,
              varastopaikat.tyyppi varastotyyppi,
              concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
              if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi,
              $pepvmlisa1
              group_concat(sarjanumeroseuranta.ostorivitunnus) ostorivitunnus
               FROM tuote
              JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
              JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                AND varastopaikat.tunnus                = tuotepaikat.varasto)
              JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
              and sarjanumeroseuranta.tuoteno           = tuote.tuoteno
              and sarjanumeroseuranta.hyllyalue         = tuotepaikat.hyllyalue
              and sarjanumeroseuranta.hyllynro          = tuotepaikat.hyllynro
              and sarjanumeroseuranta.hyllyvali         = tuotepaikat.hyllyvali
              and sarjanumeroseuranta.hyllytaso         = tuotepaikat.hyllytaso
              and sarjanumeroseuranta.myyntirivitunnus  = 0
              and sarjanumeroseuranta.era_kpl          != 0
              WHERE tuote.yhtio                         = '$kukarow[yhtio]'
              and tuote.tuoteno                         = '$row[tuoteno]'
              GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17 $pepvmlisa2
              ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
  }
  else {
    $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
              tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
              concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
              varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi, varastopaikat.erikoistoimitus_alarajasumma, tuote.vakkoodi, '' era
               FROM tuote
              JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
              JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                AND varastopaikat.tunnus = tuotepaikat.varasto)
              WHERE tuote.yhtio          = '$kukarow[yhtio]'
              and tuote.tuoteno          = '$row[tuoteno]'
              ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
  }

  $omavarastores  = pupe_query($query);

  // K‰yd‰‰n l‰pi oman varaston paikat
  if (
    in_array($yhtiorow["puute_jt_oletus"], array('H', 'O'))
    or (in_array($row["var"], array('', 'H', 'S', 'T', 'U', 'J')) and mysql_num_rows($omavarastores) >= $omapaikatraja)
    or (in_array($row["var"], array('S', 'T', 'U', 'J')) and mysql_num_rows($omavarastores) > 0)
  ) {
    if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
      $query  = "SELECT sarjanumeroseuranta.sarjanumero era
                 FROM sarjanumeroseuranta
                 WHERE yhtio          = '$kukarow[yhtio]'
                 and tuoteno          = '$row[tuoteno]'
                 and myyntirivitunnus = '$row[tunnus]'
                 LIMIT 1";
      $sarjares = pupe_query($query, $GLOBALS["masterlink"]);
      $sarjarow = mysql_fetch_assoc($sarjares);
    }

    while ($alkurow = mysql_fetch_assoc($omavarastores)) {
      // or ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"] and $row['var'] != 'S')
      // Varastosiirto toiminnallisuudessa asetetaan var S riveille l‰hdevaraston tuotteen hyllynumerot tilausriville,
      // jotta ne varaavat saldoa l‰hdevarastossa.
      // T‰st‰ syyst‰ yll‰olevassa iffiss‰ pit‰‰ olla $row['var'] != 'S',
      // koska muuten varastosiirron kautta k‰ytt‰j‰ saisi myyntioikeuden mahdolliseesti sellaiseen varastoon, johon k‰ytt‰j‰ll‰ ei ole myyntioikeutta.

      if (!isset($maksuehtorow)) {
        $maksuehtorow = array("jv" => "");
      }

      if ($alkurow["hyllyalue"] != "!!M"
        and (
          (
            $alkurow['varastotyyppi'] == 'E'
            and $kukarow['kassamyyja'] == ''
            and ($maksuehtorow["jv"] == "")
            and $alkurow['erikoistoimitus_alarajasumma'] > 0
            and (float) $row['myyntihinta'] >= (float) $alkurow['erikoistoimitus_alarajasumma']
          )
          or ($alkurow["varastotyyppi"] != "E" and $laskurow["varasto"] == 0 and (in_array($alkurow["varasto"], $varastot_array) or count($varastot_array) == 0))
          or $laskurow["varasto"] == $alkurow["varasto"]
          or (in_array($alkurow["varasto"], $varastot_array) and $laskurow["varasto"] == 0)
          or ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"] and $row['var'] != 'S')
        )
      ) {

        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);

        $myytavissa = (float) $myytavissa;
        $lisa_row   = array();

        if (    isset($alkurow["ostorivitunnus"])
          and $alkurow["ostorivitunnus"] != ""
          and in_array($trow["sarjanumeroseuranta"], array("E", "F", "G"))
        ) {
          //Jos er‰ on keksitty k‰sin t‰‰lt‰ ker‰yksest‰
          $query = "SELECT tyyppi, (varattu+kpl+jt) kpl, tunnus
                    FROM tilausrivi
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tuoteno = '$row[tuoteno]'
                    and tunnus  in ($alkurow[ostorivitunnus])";
          $lisa_res = pupe_query($query);
          $lisa_row = mysql_fetch_assoc($lisa_res);
        }

        if ((in_array($yhtiorow["puute_jt_oletus"], array('H', 'O')) and $row["tyyppi"] != 'V') or
          $myytavissa >= $kpl_st or
          ($row["var"] != "P" and $toim != "TARJOUS" and
            (($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"] and $trow["sarjanumeroseuranta"] != "E" and $trow["sarjanumeroseuranta"] != "F" and $trow["sarjanumeroseuranta"] != "G") or
              ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"] and ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and ($lisa_row["tyyppi"] == "O" or $lisa_row["kpl"] < 0 or $lisa_row["tunnus"] == $row["tunnus"]) and $sarjarow["era"] == $alkurow["era"])))) {

          $sel = "";

          if ($trow["sarjanumeroseuranta"] != "E" and $trow["sarjanumeroseuranta"] != "F" and $trow["sarjanumeroseuranta"] != "G" and !in_array($row["var"], array("P", "S")) and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
            $sel = "SELECTED";

            $selpaikka = "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";
            $selpaikkamaa = $alkurow['varastomaa'];
            $selpaikkamyytavissa = $myytavissa;
          }
          elseif (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and $sarjarow["era"] == $alkurow["era"] and !in_array($row["var"], array("P", "S")) and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
            $sel = "SELECTED";

            $selpaikka = "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";
            $selpaikkamaa = $alkurow['varastomaa'];
            $selpaikkamyytavissa = $myytavissa;

            if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
              $selpaikka .= " $alkurow[era]";
            }
          }

          $paikat .= "<option value='$alkurow[hyllyalue]#!°!#$alkurow[hyllynro]#!°!#$alkurow[hyllyvali]#!°!#$alkurow[hyllytaso]";

          if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
            $paikat .= "#!°!#$alkurow[era]";
          }

          $title  = "";

          if (strtoupper($alkurow['varastomaa']) != strtoupper($yhtiorow['maa'])) {
            $title .= strtoupper($alkurow['varastomaa'])." ";
          }

          $title .= "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";

          if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
            $title .= ", $alkurow[era]";
          }

          $title .= " ($myytavissa)";

          if ($trow["sarjanumeroseuranta"] == "F") {
            $title .= " ".tv1dateconv($alkurow["parasta_ennen"]);
          }

          $paikat .= "' $sel title='$title'>$title</option>";

          // jos toimitetaan erikoisvarastosta, niin ilmoitetaan siit‰
          if ($alkurow['varasto'] != $laskurow["varasto"] and $tm_toimitustaparow["nouto"] == "" and $sel != '' and !in_array($row["var2"], array("OK", "PK")) and $kukarow['kassamyyja'] == '' and $row['varattu'] > 0 and $alkurow['varastotyyppi'] == 'E' and $alkurow['erikoistoimitus_alarajasumma'] > 0 and (float) $row['myyntihinta'] >= (float) $alkurow['erikoistoimitus_alarajasumma']) {
            $varaosakommentti .= t("Toimitusaika 2pv.")."<br/>";
          }

          $paikatlask++;
          $kotipaikatlask++;
        }
      }

    }
  }
  //  Jos ollaan livahdettu puutteeksi koitetaan josko t‰t‰ voitasiin myyd‰ erikoisvarastosta
  elseif ($row["var"] == "P" and mysql_num_rows($omavarastores) > 0) {
    while ($alkurow = mysql_fetch_assoc($omavarastores)) {

      list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);

      if ($myytavissa > 0 and ($alkurow["varastotyyppi"] == "E" or $alkurow["hyllyalue"] == "!!M") and (($alkurow['vakkoodi'] == '' or $alkurow['vakkoodi'] == '0') or ($alkurow['vakkoodi'] != '' and $alkurow['vakkoodi'] != '0' and $tm_toimitustaparow["vak_kielto"] != 'K'))) {

        if ($kukarow["extranet"] != "") {
          if ($laskurow["toim_maa"] == $alkurow["varastomaa"]) {

            $ale_lisa_kala = generoi_alekentta_php($row, 'M', 'kerto');

            if ($yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] != 0 and $yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] > $row["hinta"]*$row["tilkpl"]*$ale_lisa_kala) {
              $varaosakommentti .= t("Tuote ainoastaan noudettavissa myym‰lˆist‰ joissa varastoituna")."!<br>";
            }
            elseif ($yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] != 0 and $yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] < $row["hinta"]*$row["tilkpl"]*$ale_lisa_kala) {
              //j‰tet‰‰n puutteeksi
            }
          }

        }
        else {
          $varaosakommentti .= t("Tuote saatavilla erikoisvarastossa")."!<br>";
        }

        break;
      }
    }

    if ($row['var'] == 'P' and $trow['status'] == 'T') {
      $varaosakommentti .= " ".t("T‰m‰ on tehdastilaustuote ja on saatavilla tilauksena")."!<br>";
    }
  }
  else {
    // meill‰ on vaan yks paikka..
    $alkurow = mysql_fetch_assoc($omavarastores);
    list(, , $selpaikkamyytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);
  }

  // K‰yd‰‰n l‰pi tuotteen toimittajat
  if ((
      $kukarow["extranet"] == ""
      or $yhtiorow['tuoteperhe_suoratoimitus'] == 'E'
    )
    and in_array($row["var"], array('', 'H', 'S', 'T', 'U', 'J', 'P'))
    and (
      $toim == "TARJOUS"
      or (!in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('', 'Z', 'Q')))
    )
    and $kpl_st > 0
    and $toim != "SIIRTOTYOMAARAYS"
  ) {
    if ($kotipaikatlask == 0 and !in_array($trow["sarjanumeroseuranta"], array('E', 'F', 'G'))) {
      $paikat = "<option value=''>".t("J‰t‰ puutteeksi")."</option>".$paikat;
      $paikatlask++;
    }

    if (isset($toimpaikres) and mysql_num_rows($toimpaikres) >=1) {
      while ($krow = mysql_fetch_assoc($toimpaikres)) {
        $sel1 = "";
        $sel2 = "";

        // tarkistetaan voiko tuotteen tilata suoraan asiakkaalle tai varastoon. oletuksena voi...
        $tmp_varastoon   = 1;
        $tmp_asiakkaalle = 1;

        // ...kiellet‰‰n tilaamasta varastoon jos yhtiˆparametri ohjaa katsomaan tuotteelta ja siell‰ ei ole sallittu...
        if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('V', 'W', 'I', 'J'))
          and !in_array($trow['suoratoimitus'], array('S', 'X'))) {         // vain S ja X sallivat tilauksen varastoon
          $tmp_varastoon = 0;
        }

        // ...kiellet‰‰n tilaamasta asiakkaalle jos yhtiˆparametri ohjaa katsomaan tuotteelta ja siell‰ ei ole sallittu...
        if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('W', 'J'))
          and !in_array($trow['suoratoimitus'], array('S', 'U'))) {        // vain S ja U sallivat tilauksen asiakkaalle
          $tmp_asiakkaalle = 0;
        }

        // katsotaan onko tuote suoratoimituskelpoinen (vain ei tuoteperheet, tai tuoteperheiden saldolliset is‰t tai jos yhtiˆll‰ suoratoimitetaan perheet per tuote)
        if ((   // jos on tuoteperheen lapsi, pit‰‰ lapsien suoratoimitus olla sallittu
            $yhtiorow['tuoteperhe_suoratoimitus'] == ''
            or $row["perheid"] == 0
            or $row["perheid"] == $row["tunnus"]
          )
          and ( // onko suoratoimitus yleens‰k‰‰n sallittu
            in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'X', 'Y', 'A', 'B', 'C'))
            or (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('V', 'W', 'I', 'J')) and ($tmp_varastoon == 1 or $tmp_asiakkaalle == 1))
          )
        ) {
          // piirret‰‰nkˆ oletukseksi tilata suoratoimituksena varastoon? ...
          if ($row["toimittajan_tunnus"] == $krow["liitostunnus"] and $row["hyllyalue"] == "" and $row["hyllynro"] == "") {

            if (($row["var"] == 'T' or ($row["var"] == "J" and $row["suoraan_laskutukseen"] == "")) and in_array($krow["suoratoimitus"], array('', 'X')) and $tmp_varastoon == 1) {
              $sel1          = "SELECTED";
              $selpaikka        = "$krow[nimi] --> ".t("tilaa")." ".t("varastoon");
              $selpaikkamaa      = "";
              $selpaikkamyytavissa = 0;
            }
            // ...ent‰ asiakkaalle?
            elseif (in_array($krow["suoratoimitus"], array('', 'U')) and $tmp_asiakkaalle == 1) {
              $sel2          = "SELECTED";
              $selpaikka        = "$krow[nimi] --> ".t("tilaa")." ".t("asiakkaalle");
              $selpaikkamaa      = "";
              $selpaikkamyytavissa = 0;
            }

            if (($sel1 != "" or $sel2 != "") and $krow['tehdas_saldo_tarkistus'] != '' and $row['varattu'] > $krow['tehdas_saldo']) {
              $varaosavirhe .= t("HUOM: Toimittajan tehtaan saldo on %d", "", $krow['tehdas_saldo']).".<br>";
            }
          }

          if (!in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'B')) and in_array($krow["suoratoimitus"], array('', 'X')) and $tmp_varastoon == 1) {
            // tallennetaan riville toimittajan tunnus
            $title = "$krow[nimi] --> ".t("tilaa")." ".t("varastoon");
            $paikat .= "<option value='°°°$krow[liitostunnus]' title='$title' $sel1>$title</option>";
            $paikatlask++;
          }

          if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'Y', 'W', 'J', 'B', 'C')) and in_array($krow["suoratoimitus"], array('', 'U')) and $tmp_asiakkaalle == 1) {
            $title = "$krow[nimi] --> ".t("tilaa")." ".t("asiakkaalle");
            $paikat .= "<option value='!!!$krow[liitostunnus]' title='$title' $sel2>$title</option>";
            $paikatlask++;
          }
        }
      }
    }
  }
  elseif ($kotipaikatlask == 0 and $kpl_st > 0 and $laskurow["tila"] != 'V') {
    $paikat = "<option value=''>".t("J‰t‰ puutteeksi")."</option>".$paikat;
    $paikatlask++;
  }

  //  Tarkistetaan viel‰ voisimmeko kenties ehk‰ mahdollisesti valmistaa t‰t‰ asiakkaalle?
  if ($kukarow["extranet"] == "" and $yhtiorow["tee_valmistus_myyntitilaukselta"] != '' and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS')) {

    $query = "SELECT *
              FROM tuoteperhe
              WHERE yhtio    = '$kukarow[yhtio]'
              and isatuoteno = '$row[tuoteno]'
              and tyyppi     = 'R'
              LIMIT 1";
    $pres = pupe_query($query);

    if (mysql_num_rows($pres) == 1) {
      $query = "SELECT varastopaikat.nimitys varasto, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali,tuotepaikat.hyllytaso
                FROM tuote
                JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                  AND varastopaikat.tunnus = tuotepaikat.varasto)
                WHERE tuote.yhtio          = '$kukarow[yhtio]'
                and tuote.tuoteno          = '$row[tuoteno]'";
      $valmpaikatres = pupe_query($query);

      while ($valmpaikatrow  = mysql_fetch_assoc($valmpaikatres)) {
        $sel = "";
        if ($row["hyllyalue"] == $valmpaikatrow["hyllyalue"] and $row["hyllynro"] == $valmpaikatrow["hyllynro"] and $row["hyllyvali"] == $valmpaikatrow["hyllyvali"] and $row["hyllytaso"] == $valmpaikatrow["hyllytaso"] and ($row["var"] == "R" or ($row["tilausrivilinkki"] > 0))) {
          $sel = "SELECTED";
        }

        // tallennetaan riville toimittajan tunnus
        $title = "$valmpaikatrow[varasto] --> ".t("valmista")." ($valmpaikatrow[hyllyalue]-$valmpaikatrow[hyllynro]-$valmpaikatrow[hyllyvali]-$valmpaikatrow[hyllytaso])";
        $paikat .= "<option value='°°V$valmpaikatrow[hyllyalue]#!°!#$valmpaikatrow[hyllynro]#!°!#$valmpaikatrow[hyllyvali]#!°!#$valmpaikatrow[hyllytaso]' title='$title' $sel>$title</option>";
        $paikatlask++;
      }
    }
  }

  $_siirtolista_myyntitilaukselta = ($yhtiorow['tee_siirtolista_myyntitilaukselta'] == 'K');
  $_ei_ole_takuu = ($laskurow["tilaustyyppi"] != "U");

  if ($_siirtolista_myyntitilaukselta and
    $_ei_ole_takuu and
    !in_array($toim, array("VALMISTAVARASTOON", "SIIRTOLISTA", "SIIRTOTYOMAARAYS", "REKLAMAATIO")) and
    in_array($laskurow['tila'], array('N', 'T')) and
    in_array($laskurow['alatila'], array('', 'F'))) {

    $sel4 = "";

    if (!empty($laskurow['varasto'])) {
      $kohde_varastot = array($laskurow['varasto']);
    }
    elseif (empty($laskurow['varasto']) and !empty($laskurow['yhtio_toimipaikka'])) {
      $kohde_varastot = hae_yhtion_toimipaikan_varastot($laskurow['yhtio_toimipaikka']);
      $kohde_varastot = array_column($kohde_varastot, 'tunnus');
    }
    else {
      $kohde_varastot = array();
    }

    $siirto_varastot = hae_mahdolliset_siirto_varastot($kohde_varastot, $row['tuoteno']);

    if (!isset($_isa_saldot)) {
      $_isa_saldot = array();
    }

    foreach ($siirto_varastot as $siirto_varasto) {

      $saldo_myytavissa = $siirto_varasto['tuote']['myytavissa'];
      if ($_onko_isatuote) {
        $tuoteperhe_saldomyytavissa = tuoteperhe_myytavissa($trow['tuoteno'], '', '', $siirto_varasto['lahde_tunnus']);
        $saldo_myytavissa = $tuoteperhe_saldomyytavissa[$siirto_varasto['lahde_nimi']];
        $_isa_saldot[$row['perheid']] = array(
          'saldo_myytavissa' => $saldo_myytavissa,
          'var' => $row['var'],
        );
      }
      elseif ($_onko_lapsituote) {
        if ($_isa_saldot[$row['perheid']]['var'] == 'S') {
          $saldo_myytavissa = $_isa_saldot[$row['perheid']]['saldo_myytavissa'];
        }
        else {
          continue;
        }
      }

      //Jos tilausrivin toimittajan_tunnus ja siirto_varasto.tunnus on samat,
      //tarkoittaa se ett‰ tilausriville on valittu kyseinen siirtovarasto
      //ja lisaarivi vaiheessa saldot on riitt‰neet.
      $_normirivi_onko_siirto = (
        !isset($tuoteperhe_saldomyytavissa)
        and $siirto_varasto['tuote']['myytavissa'] <= 0
        and $row['toimittajan_tunnus'] != $siirto_varasto['tunnus']
      );
      $_perherivi_onko_siirto = (
        isset($tuoteperhe_saldomyytavissa)
        and $tuoteperhe_saldomyytavissa[$siirto_varasto['lahde_nimi']] <= 0
        and $row['toimittajan_tunnus'] != $siirto_varasto['tunnus']
      );

      if ($_normirivi_onko_siirto or $_perherivi_onko_siirto) {
        continue;
      }

      if ($row['var'] == 'S' and $siirto_varasto['tunnus'] == $row['toimittajan_tunnus']) {
        $sel4 = "SELECTED";
      }

      $siirto_varastot_text = t('Varastosiirto'). " {$siirto_varasto['lahde_nimi']} --> {$siirto_varasto['kohde_nimi']} ".t('Myyt‰viss‰').": {$saldo_myytavissa} ({$siirto_varasto['toimitustapa_selite']})";
      $paikat .= "<option value='°°S{$siirto_varasto['tunnus']}°°S{$siirto_varasto['lahde_tunnus']}°°S{$siirto_varasto['kohde_tunnus']}' {$sel4}>{$siirto_varastot_text}</option>";
      $paikatlask++;
      $sel4 = "";
    }
  }

  if ($paikat != "" and $paikatlask > 1 and $row["hyllyalue"] != "!!M") {
    $paikat = "<select name='paikka' class='minpad_select' style='width:120px;' onchange='submit();' {$paikka_disabled}>".$paikat."</select>";
  }
  elseif ($row["hyllyalue"] == "!!M") {
    $paikat = " $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]";
  }
  else {
    $paikat = "";
  }
}
// voidaan valita paikka myˆs valmistusten valmisteille
elseif (
  $trow["ei_saldoa"] == ""
  and $row["tyyppi"] == 'W'
  and ($row["perheid"] == 0
    or $row["perheid"] == $row["tunnus"]
  )
) {

  $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
            tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
            concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
            varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi, varastopaikat.erikoistoimitus_alarajasumma, tuote.vakkoodi, '' era
             FROM tuote
            JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno and tuotepaikat.hyllyalue != '!!M'
            JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
              AND varastopaikat.tunnus = tuotepaikat.varasto)
            WHERE tuote.yhtio          = '$kukarow[yhtio]'
            and tuote.tuoteno          = '$row[tuoteno]'
            ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
  $omavarastores  = pupe_query($query);

  if (!isset($paikatlask)) $paikatlask = 0;

  // K‰yd‰‰n l‰pi oman varaston paikat
  while ($alkurow = mysql_fetch_assoc($omavarastores)) {

    if ($laskurow["varasto"] == $alkurow["varasto"]
      or (in_array($alkurow["varasto"], $varastot_array) and $laskurow["varasto"] == 0)
      or ($laskurow["varasto"] == 0 and count($varastot_array) == 0)
      or ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"])
    ) {

      $sel = "";

      if ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
        $sel = "SELECTED";

        $selpaikka = "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";
        $selpaikkamaa = $alkurow['varastomaa'];
      }

      $paikat .= "<option value='$alkurow[hyllyalue]#!°!#$alkurow[hyllynro]#!°!#$alkurow[hyllyvali]#!°!#$alkurow[hyllytaso]";

      $title  = "";

      if (strtoupper($alkurow['varastomaa']) != strtoupper($yhtiorow['maa'])) {
        $title .= strtoupper($alkurow['varastomaa'])." ";
      }

      $title .= "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";

      $paikat .= "' $sel title='$title'>$title</option>";
      $paikatlask++;
    }
  }

  if ($paikat != "" and $paikatlask > 1) {
    $paikat = "<select name='paikka' class='minpad_select' style='width:120px;' onchange='submit();' {$paikka_disabled}>".$paikat."</select>";
  }
  elseif ($row["hyllyalue"] == "!!M") {
    $paikat = " $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]";
  }
  else {
    $paikat = "";
  }
}
// jos kyseess‰ on tuoteperheen suoratoimitus suoraan asiakkaalle, niin tehd‰‰n is‰dropdown (dropdownin sis‰ltˆ haetaan lapsilta).
elseif (
  (  $yhtiorow['tuoteperhe_suoratoimitus'] == 'E'
    or ($kukarow['extranet'] == '' and $yhtiorow['tuoteperhe_suoratoimitus'] == 'M'))
  and in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'W', 'J', 'Y', 'B', 'C'))
  and $trow["ei_saldoa"] != ""
  and $row["tyyppi"] == "L"
  and $kpl_st > 0
  and $row["tunnus"] == $row["perheid"]
  and in_array($row["var"], array('P', 'J', 'U', 'T'))
) {

  $query = "SELECT tuoteno
            FROM tilausrivi
            WHERE yhtio = '$kukarow[yhtio]'
            AND perheid = '$row[tunnus]'
            AND otunnus = '$row[otunnus]'";
  $perhe_tuoteno_res = pupe_query($query, $GLOBALS["masterlink"]);

  $perheen_tuotteet = '';

  while ($perhe_tuoteno_row = mysql_fetch_assoc($perhe_tuoteno_res)) {
    $perheen_tuotteet .= "'$perhe_tuoteno_row[tuoteno]',";
  }

  $perheen_tuotteet = substr($perheen_tuotteet, 0, -1);

  $query = "SELECT DISTINCT liitostunnus, tyyppi_tieto, toimi.nimi
            FROM tuotteen_toimittajat use index (yhtio_tuoteno)
            JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus)
            WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
            and tuotteen_toimittajat.tuoteno in ($perheen_tuotteet)
            ORDER BY if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys)";
  $toimpaikres  = pupe_query($query);

  $paikat = "<option value=''>".t("J‰t‰ j‰lkitoimitukseen")."</option>".$paikat;

  while ($krow  = mysql_fetch_assoc($toimpaikres)) {
    $sel1 = "";

    if ($row["toimittajan_tunnus"] == $krow["liitostunnus"] and $row["hyllyalue"] == "" and $row["hyllynro"] == "") {
      $sel1 = 'SELECTED';
    }

    $title = "$krow[nimi] --> ".t("tilaa")." ".t("asiakkaalle");
    $paikat .= "<option value='!!!$krow[liitostunnus]' title='$title' $sel1>$title</option>";
  }

  $paikat = "<select name='paikka' class='minpad_select' style='width:120px;' onchange='submit();' {$paikka_disabled}>".$paikat."</select>";
}

// tarkastetaan onko t‰t‰ tuotetta varattuna siirtolistalla tai ostotilauksella
if ($kukarow["extranet"] == "" and $trow["ei_saldoa"] == "" and $row["tyyppi"] != "E" and $kpl_st > 0) {
  $query = "SELECT
            kohde.nimitys as kohde,
            lahde.nimitys as lahde,
            lasku.tila,
            lasku.alatila,
            lasku.tunnus,
            tilausrivi.yksikko,
            sum(tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl) as kpl
            FROM lasku USE INDEX (tila_index)
            JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus )
            LEFT JOIN varastopaikat lahde USE INDEX (PRIMARY) ON (lahde.yhtio = lasku.yhtio
              AND lahde.tunnus = lasku.varasto)
            LEFT JOIN varastopaikat kohde USE INDEX (PRIMARY) ON (kohde.yhtio = lasku.yhtio
              AND kohde.tunnus = lasku.clearing)
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            AND lasku.tila = 'G'
            AND lasku.alatila in ('','J','A','B','C','D','E','T','P')
            AND lasku.tunnus != '{$laskurow["tunnus"]}'
            AND tilausrivi.tyyppi != 'D'
            AND tilausrivi.tuoteno = '{$trow['tuoteno']}'
            GROUP BY 1, 2, 3, 4, 5, 6";
  $siirtores = pupe_query($query);

  while ($siirtorow = mysql_fetch_assoc($siirtores)) {
    $_yksikko = t_avainsana("Y", "", "and avainsana.selite='$siirtorow[yksikko]'", "", "", "selite");
    $_siirtolistalla = t("Siirtolistalla");
    $alatila = $siirtorow["alatila"];
    $laskutyyppi = $siirtorow["tila"];

    //tehd‰‰n selv‰kielinen tila/alatila
    require "inc/laskutyyppi.inc";

    $varaosakommentti .= "{$_siirtolistalla} {$siirtorow['tunnus']}: ";
    $varaosakommentti .= "{$siirtorow['kpl']} {$_yksikko} {$siirtorow['lahde']} -> ";
    $varaosakommentti .= "{$siirtorow['kohde']} ({$alatila})<br>";
  }

  // katotaan onko viel‰ ostotilauksella!
  $tulossa_query = "SELECT
                    IFNULL(min(toimaika), '') as paivamaara,
                    IFNULL(max(toimaika), '') as paivamaara2,
                    sum(varattu) as kpl,
                    max(yksikko) as yksikko,
                    group_concat(distinct otunnus) as tilaukset
                    FROM tilausrivi
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tuoteno = '{$row['tuoteno']}'
                    AND varattu > 0
                    AND tyyppi IN ('O','W')";
  $tulossa_result = pupe_query($tulossa_query);
  $tulossa_row = mysql_fetch_assoc($tulossa_result);

  if (mysql_num_rows($tulossa_result) > 0 and $tulossa_row["paivamaara"] != '' and $tulossa_row["kpl"] > 0) {
    $_maara = $tulossa_row['kpl'];
    $_pvm = tv1dateconv($tulossa_row['paivamaara']);
    $_pvm2 = tv1dateconv($tulossa_row['paivamaara2']);
    $_tulossa = t("tulossa");
    $_yksikko = t_avainsana("Y", "", "and avainsana.selite='$tulossa_row[yksikko]'", "", "", "selite");

    $varaosakommentti .= "{$_maara} {$_yksikko} {$_tulossa} {$_pvm}";

    if ($tulossa_row["paivamaara"] != $tulossa_row["paivamaara2"]) {
      $varaosakommentti .= " - {$_pvm2}";
    }

    $varaosakommentti .= "<br>";
  }
}

if ($yhtiorow["pura_osaluettelot"] != "") {
  $varaosakommentti .= osaluettelo_hinta_tarkistus($laskurow["tunnus"], $row["tunnus"]);
}

//  Onko t‰t‰ ostotilauksella?
if ($kukarow["extranet"] == "" and $trow["ei_saldoa"] == "" and $row["tilausrivilinkki"] > 0) {
  $query = "SELECT tilausrivi.otunnus,
            lasku.nimi,
            lasku.liitostunnus,
                  lasku.tila,
                  lasku.alatila,
                  lasku.toim_nimi,
                  lasku.toim_nimitark,
                  lasku.toim_osoite,
                  lasku.toim_postino,
                  lasku.toim_postitp,
                  lasku.toim_maa,
                  left(lasku.luontiaika, 10) luontiaika,
                  lasku.laatija
            FROM tilausrivi
            JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
            and tilausrivi.tunnus  = '$row[tilausrivilinkki]'
            and tilausrivi.tyyppi  = 'O'";
  $suoratoimostores = pupe_query($query);

  if (mysql_num_rows($suoratoimostores) > 0) {
    $suoratoimostorow = mysql_fetch_assoc($suoratoimostores);

    $ostotilaus_linkkilisa = "";

    if ($suoratoimostorow["tila"] == "O") {
      $tilaustyypilla = t("Ostotilauksella");

      if ($suoratoimostorow["luontiaika"] == date("Y-m-d") and
        $suoratoimostorow["tila"]      == 'O' and
        $suoratoimostorow["alatila"]    == '' and
        $suoratoimostorow["laatija"]    == $kukarow['kuka']
      ) {
        $nayta_sostolisateksti = "TOTTA";
      }
      else {
        $nayta_sostolisateksti = "HUOM";
      }

      $backlinkki = "tilaus_myynti.php////toim={$toim}//tee={$tee}//tilausnumero={$tilausnumero}//mista={$mista}//tila={$tila}//orig_tila={$laskurow['tila']}//orig_alatila={$laskurow['alatila']}";
      $ostotilaus_linkkilisa = "<a href='{$palvelin2}tilauskasittely/tilaus_osto.php?tee=AKTIVOI&tilausnumero={$suoratoimostorow['otunnus']}&from=MYYNTITILAUS&lopetus={$backlinkki}'>";
    }
    elseif ($suoratoimostorow["tila"] == "V" or $suoratoimostorow["tila"] == "W") {
      $tilaustyypilla = t("Valmistuksella");
    }

    $varaosakommentti .= "$tilaustyypilla: $suoratoimostorow[nimi] ";

    if ($ostotilaus_linkkilisa != "") $varaosakommentti .= $ostotilaus_linkkilisa;

    $varaosakommentti .= "$suoratoimostorow[otunnus]";

    if ($ostotilaus_linkkilisa != "") $varaosakommentti .= "</a>";

    $varaosakommentti .= "<br>";
  }
}

if ($row["alv"] == -999.99) {
  $varaosavirhe .= t("VIRHE: Tuotteelle ei lˆytynyt ALV-prosenttia asiakkaan maahan!")."<br>";
  $riviok++;
  $tilausok++;
  $saako_jalkitoimittaa++;
  $saako_hyvaksya = 0;
}

if ($toim != "VALMISTAVARASTOON" and $row['tuoteno'] != '' and ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A'  or (($row["var"] == 'J' or $row["var"] == 'P') and !in_array($row["var2"], array("OK", "PK"))))) {

  // Haetaan vastaavuusketjut korvaavat ketjun p‰‰tuotteella
  require_once 'vastaavat.class.php';

  $vastaavat = new Vastaavat($row["tuoteno"]);

  if ($vastaavat->onkovastaavia()) {

    $ketjut = explode(",", $vastaavat->getIDt());
    $ketjun_tuotteet = array();
    foreach ($ketjut as $ketju) {
      $ketjun_tuotteet[$ketju] = $vastaavat->tuotteet($ketju);
    }

    if ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A' and in_array($row["var2"], array("OK", "PK"))) {

      $lisa_huomautus = "";
      // Loopataan kaikki tuotteen vastaavuusketjut
      foreach ($ketjut as $ketju) {
        // Haetaan tuotteet ketjukohtaisesti
        $_tuotteet = $ketjun_tuotteet[$ketju];

        foreach ($_tuotteet as $_tuote) {

          if ($_tuote["tuoteno"] != $row["tuoteno"]) {
            list(, , $vmyytavissa) = saldo_myytavissa($_tuote["tuoteno"], '', $laskurow['varasto']);

            if ($vmyytavissa > 0) {
              $lisa_huomautus .= "<div style='float:right; position:relative; top:4px; right:8px;'><font class='error'>";
              $lisa_huomautus .= t("Vastaavaa tuotetta paikallisvarastossa");
              $lisa_huomautus .= "!</font></div>";
              break 2;
            }
          }
        }
      }

      $vastaavat_html = "<table width='100%'><tr>";
      $vastaavat_html .= "<th style='width:100%;'><form method='post' name='hyvaksy'>
                <input type='hidden' name='toim'          value = '$toim'>
                <input type='hidden' name='lopetus'      value = '$lopetus'>
                <input type='hidden' name='ruutulimit'   value = '$ruutulimit'>
                <input type='hidden' name='projektilla'  value = '$projektilla'>
                <input type='hidden' name='tilausnumero' value = '$tilausnumero'>
                <input type='hidden' name='mista'        value = '$mista'>
                <input type='hidden' name='rivitunnus'   value = '$row[tunnus]'>
                <input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
                <input type='hidden' name='menutila'     value = '$menutila'>
                <input type='hidden' name='tila'         value = 'OOKOOAA'>
                <input type='hidden' name='vastaavienkasittely' value = 'kylla'>
                <input type='hidden' name='naytetaan_vastaavat' value = 'true' />
                <input style='float:right;' type='submit' value='".t("N‰yt‰ vastaavat tuotteet")."'>
                $lisa_huomautus
                </form></th>";
      $vastaavat_html .= "</tr></table>";
      $vastaavattuotteet = 1;
    }
    else {

      // Loopataan kaikki tuotteen vastaavuusketjut
      foreach ($ketjut as $ketju) {

        $vastaavat_table = "";

        // Haetaan tuotteet ketjukohtaisesti
        $_tuotteet = $ketjun_tuotteet[$ketju];

        foreach ($_tuotteet as $_tuote) {

          $vaihtoehtoinen = ($_tuote['vaihtoehtoinen'] == 'K') ? "<font color=#F00>".t("Vaihtoehtoinen, tarkista sopivuus")."!</font>" : "";

          $chk_varasto = $laskurow['varasto'] == 0 ? $varastot_array : $laskurow['varasto'];

          list(, , $vmyytavissa) = saldo_myytavissa($_tuote["tuoteno"], '', $chk_varasto);

          $query = "SELECT status
                    FROM tuote
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tuoteno = '{$_tuote['tuoteno']}'";
          $chk_status_res = pupe_query($query);
          $chk_status_row = mysql_fetch_assoc($chk_status_res);

          if ($chk_status_row['status'] == 'P' and $vmyytavissa == 0) continue;

          if ((($vmyytavissa > 0 or $yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'S') and strtoupper($_tuote['tuoteno']) != strtoupper($row["tuoteno"])) or $yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A') {

            $tvrow = hae_tuote($_tuote['tuoteno']);

            list($V_hinta, $V_netto, $V_ale, $V_alehinta_alv, $V_alehinta_val) = alehinta($laskurow, $tvrow, $row['tilkpl'], '', '', '');

            $myytavissa_font = $vmyytavissa < 1 ? "error" : "ok";

            $vastaavat_table .= "<tr class='back aktiivi'>"
              ."<td>";

            if ($kukarow['extranet'] == '' and $tuotekyslinkki != "") {
              $vastaavat_table .= "<a href='{$palvelin2}$tuotekyslinkki?".$tuotekyslinkkilisa."tee=Z&tuoteno=".urlencode($_tuote["tuoteno"])."&toim_kutsu=$toim&lopetus=$tilmyy_lopetus//from=LASKUTATILAUS'>";
            }

            $vastaavat_table .= $_tuote["tuoteno"];

            if ($kukarow['extranet'] == '' and $tuotekyslinkki != "") {
              $vastaavat_table .= "</a>";
            }

            // poistetaan alvit (jos hintamuunnos p‰‰ll‰)
            if (isset($tilausrivi_alvillisuus) and $tilausrivi_alvillisuus == "E" and $yhtiorow["alv_kasittely"] == '' and $yhtiorow["alv_kasittely_hintamuunnos"] == 'o') {
              $V_hinta = round($V_hinta / (1+$row['alv']/100), $yhtiorow['hintapyoristys']);
            }
            // lisataan alvit (jos hintamuunnos p‰‰ll‰)
            if (isset($tilausrivi_alvillisuus) and $tilausrivi_alvillisuus == "K" and $yhtiorow["alv_kasittely"] == 'o' and $yhtiorow["alv_kasittely_hintamuunnos"] == 'o') {
              $V_hinta = round($V_hinta * (1+$row['alv']/100), $yhtiorow['hintapyoristys']);
            }

            $vastaavat_table .= " $vaihtoehtoinen</td>"
              ."<td>{$tvrow["nimitys"]}</td>"
              ."<td align='right'><font class='{$myytavissa_font}'>".sprintf("%.2f", $vmyytavissa)."</font></td>"
              ."<td align='right'>".sprintf("%.2f", $V_hinta)."</td>";

            if ($sahkoinen_tilausliitanta and ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'S' or $yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A')) {

              $query = "SELECT tt.tehdas_saldo,
                        tt.liitostunnus,
                        tt.toim_tuoteno,
                        tt.tunnus AS tt_tunnus,
                        toimi.nimi AS toimittajan_nimi,
                        avainsana.selitetark_2,
                        avainsana.selitetark_3,
                        avainsana.selitetark_4,
                        avainsana.selitetark_5,
                        if (tt.jarjestys = 0, 9999, tt.jarjestys) sorttauskentta
                        FROM tuotteen_toimittajat AS tt
                        JOIN toimi ON (toimi.yhtio = tt.yhtio AND toimi.tunnus = tt.liitostunnus)
                        LEFT JOIN avainsana ON (toimi.yhtio = avainsana.yhtio AND avainsana.laji = 'SAHKTILTUN' AND avainsana.selite = '{$laskurow['yhtio_toimipaikka']}' AND toimi.tunnus = avainsana.selitetark)
                        WHERE tt.yhtio = '{$kukarow['yhtio']}'
                        AND tt.tuoteno = '{$_tuote['tuoteno']}'
                        ORDER BY sorttauskentta
                        LIMIT 1";
              $tehdas_saldo_res = pupe_query($query);
              $tehdas_saldo_row = mysql_fetch_assoc($tehdas_saldo_res);

              $tuotteen_toimittajan_tunnus = $tehdas_saldo_row['liitostunnus'];

              $_tilattu = (int) $row['tilkpl'];
              $_tuote['toim_tuoteno'] = $tehdas_saldo_row['toim_tuoteno'];

              $vastaavat_table .= "<td>{$tehdas_saldo_row['toimittajan_nimi']}</td>";

              $vastaavat_table .= "<td align='right'>";

              if ($tehdas_saldo_row['selitetark_2'] != "") {
                $vastaavat_table .= "<div class='availability {$_tuote['vastaavat_tunnus']}_{$_tilattu}_availability' /><span class='{$_tuote['vastaavat_tunnus']}_{$_tilattu}_loading'></span></div>&nbsp;";
                $vastaavat_table .= "<input type='hidden' class='{$_tuote['vastaavat_tunnus']}_custid' value='{$tehdas_saldo_row['selitetark_2']}' />";
                $vastaavat_table .= "<input type='hidden' class='{$_tuote['vastaavat_tunnus']}_username' value='{$tehdas_saldo_row['selitetark_3']}' />";
                $vastaavat_table .= "<input type='hidden' class='{$_tuote['vastaavat_tunnus']}_password' value='{$tehdas_saldo_row['selitetark_4']}' />";
                $vastaavat_table .= "<input type='hidden' class='{$_tuote['vastaavat_tunnus']}_suppliernumber' value='{$tehdas_saldo_row['selitetark_5']}' />";
                $vastaavat_table .= "<input type='hidden' class='{$_tuote['vastaavat_tunnus']}_tt_tunnus' value='{$tehdas_saldo_row['tt_tunnus']}' />";
              }

              if ($tehdas_saldo_row['selitetark_2'] == '') {

                if (!empty($laskurow['varasto'])) {
                  $kohde_varastot = array($laskurow['varasto']);
                }
                elseif (empty($laskurow['varasto']) and !empty($laskurow['yhtio_toimipaikka'])) {
                  $kohde_varastot = hae_yhtion_toimipaikan_varastot($laskurow['yhtio_toimipaikka']);
                  $kohde_varastot = array_column($kohde_varastot, 'tunnus');
                }
                else {
                  $kohde_varastot = array();
                }

                $siirto_varastot = hae_mahdolliset_siirto_varastot($kohde_varastot, $_tuote['tuoteno']);

                $_eivoisiirtaa = false;
                if (!in_array($laskurow['tila'], array('N', 'T')) and !in_array($laskurow['alatila'], array('', 'F'))) $_eivoisiirtaa = true;

                $_siirrettavissa = $siirto_varastot[0]['tuote']['myytavissa'];
                $siirrettavissa_saldon_font = $_siirrettavissa < 1 ? "error" : "ok";

                if ($_eivoisiirtaa and $siirrettavissa_saldon_font == "ok") {
                  $vastaavat_table .= "(";
                }

                $vastaavat_table .= "<font class='{$siirrettavissa_saldon_font}'>".sprintf("%.2f", $_siirrettavissa)."</font>";

                if ($_eivoisiirtaa and $siirrettavissa_saldon_font == "ok") {
                  $vastaavat_table .= ") <font class='error'>".sprintf("%.2f", 0)."</font>";
                }
              }

              $vastaavat_table .= "</td>";
              $vastaavat_table .= "<td>";

              if ($tehdas_saldo_row['selitetark_2'] != "") {
                $hae = 'nappi';
                $rivitunnus = $row['tunnus'];
                $_vastaavat_tunnus = $_tuote['vastaavat_tunnus'];
                $_vastaavat_tuoteno = $_tuote['toim_tuoteno'];

                require "inc/sahkoinen_tilausliitanta.inc";
              }

              $vastaavat_table .= "</td>";
            }

            $vastaavat_table .= "<td align='left'>";

            if ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] != 'A' or (strtoupper($_tuote['tuoteno']) != strtoupper($row["tuoteno"]))) {
              $vastaavat_table .= "<form method='post' name='jalkitoimita'>
                          <input type='hidden' name='toim'       value = '$toim'>
                          <input type='hidden' name='lopetus'     value = '$lopetus'>
                          <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                          <input type='hidden' name='projektilla'   value = '$projektilla'>
                          <input type='hidden' id='tilausnumero' name='tilausnumero'   value = '$tilausnumero'>
                          <input type='hidden' name='mista'       value = '$mista'>
                          <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                          <input type='hidden' name='vastaavatuoteno' value = '$_tuote[tuoteno]'>
                          <input type='hidden' name='menutila'     value = '$menutila'>
                          <input type='hidden' name='tila'       value = 'MUUTA'>
                          <input type='hidden' name='tapa'       value = 'MYYVASTAAVA'>
                          <input type='submit' value='".t("Tilaa tuote")."'>
                          </form>";
            }

            $vastaavat_table .= "</td></tr>";
          }
        }

        if ($vastaavat_table != "") {
          if ($vastaavattuotteet == 0) {
            $vastaavat_html .= "<table width='100%'>";
          }

          $vastaavat_html .= "<tr><th>".t("Vastaava")." ".t("Tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("Myyt‰viss‰")."</th><th>".t("Myyntihinta")."</th>";

          if ($sahkoinen_tilausliitanta and ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'S' or $yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A')) {
            $vastaavat_html .= "<th>".t("Toimittaja")."</th><th>".t("Siirrett‰viss‰")."</th>";
            $vastaavat_html .= "<th>".t("Tarkista toimittajan saldo")."</th>";
          }

          $vastaavat_html .= "<th>";

          if ($vastaavattuotteet == 0) {
            $vastaavat_html .= "<form method='post' name='hyvaksy'>
                      <input type='hidden' name='toim'       value = '$toim'>
                      <input type='hidden' name='lopetus'     value = '$lopetus'>
                      <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                      <input type='hidden' name='projektilla'   value = '$projektilla'>
                      <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                      <input type='hidden' name='mista'       value = '$mista'>
                      <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                      <input type='hidden' name='rivilaadittu'   value = '$row[laadittu]'>
                      <input type='hidden' name='menutila'     value = '$menutila'>
                      <input type='hidden' name='tila'       value = 'OOKOOAA'>
                      <input type='hidden' name='vastaavienkasittely' value = 'kylla'>
                      <input type='submit' value='".t("Piilota vastaavat tuotteet")."'>
                      </form>";
          }

          $vastaavat_html .= "</th>";

          $vastaavat_html .= "</tr>$vastaavat_table";
          $vastaavattuotteet = 1;
        }
      }

      if ($vastaavat_html != "") {
        $vastaavat_html .= "</table>";
      }
    }
  }
}

$paarivin_saldokysely = false;

if ($sahkoinen_tilausliitanta and (!isset($vastaavat_html) or trim($vastaavat_html) == '') and (!isset($vastaavat_table) or trim($vastaavat_table) == '') and in_array($row['var'], array('U', 'T'))) {

  $query = "SELECT tt.tehdas_saldo,
            tt.liitostunnus,
            tt.toim_tuoteno,
            tt.tunnus AS tt_tunnus,
            toimi.nimi AS toimittajan_nimi,
            avainsana.selitetark_2,
            avainsana.selitetark_3,
            avainsana.selitetark_4,
            avainsana.selitetark_5,
            if (tt.jarjestys = 0, 9999, tt.jarjestys) sorttauskentta
            FROM tuotteen_toimittajat AS tt
            JOIN toimi ON (toimi.yhtio = tt.yhtio AND toimi.tunnus = tt.liitostunnus)
            LEFT JOIN avainsana ON (toimi.yhtio = avainsana.yhtio AND avainsana.laji = 'SAHKTILTUN' AND avainsana.selite = '{$laskurow['yhtio_toimipaikka']}' AND toimi.tunnus = avainsana.selitetark)
            WHERE tt.yhtio = '{$kukarow['yhtio']}'
            AND tt.tuoteno = '{$row['tuoteno']}'
            ORDER BY sorttauskentta
            LIMIT 1";
  $tehdas_saldo_res = pupe_query($query);
  $tehdas_saldo_row = mysql_fetch_assoc($tehdas_saldo_res);

  if ($tehdas_saldo_row['selitetark_2'] != "" and $tehdas_saldo_row['liitostunnus'] == $row['toimittajan_tunnus']) {

    $vastaavat_html = "<table width='100%'><tr style='width: 100%;'><th style='text-align: center;'>";
    $vastaavat_table = "";

    $hae = 'nappi';
    $rivitunnus = $row['tunnus'];
    $_vastaavat_tunnus = $row['tunnus'];
    $_vastaavat_tuoteno = $tehdas_saldo_row['toim_tuoteno'];
    $_tilattu = (int) $row['tilkpl'];

    $th_rivi = true;

    $vastaavat_table2 = "<div class='availability {$_vastaavat_tunnus}_{$_tilattu}_availability' /><span class='{$_vastaavat_tunnus}_{$_tilattu}_loading'></span></div>&nbsp;";
    $vastaavat_table2 .= "<input type='hidden' class='{$_vastaavat_tunnus}_custid' value='{$tehdas_saldo_row['selitetark_2']}' />";
    $vastaavat_table2 .= "<input type='hidden' class='{$_vastaavat_tunnus}_username' value='{$tehdas_saldo_row['selitetark_3']}' />";
    $vastaavat_table2 .= "<input type='hidden' class='{$_vastaavat_tunnus}_password' value='{$tehdas_saldo_row['selitetark_4']}' />";
    $vastaavat_table2 .= "<input type='hidden' class='{$_vastaavat_tunnus}_suppliernumber' value='{$tehdas_saldo_row['selitetark_5']}' />";
    $vastaavat_table2 .= "<input type='hidden' class='{$_vastaavat_tunnus}_tt_tunnus' value='{$tehdas_saldo_row['tt_tunnus']}' />";

    require "inc/sahkoinen_tilausliitanta.inc";

    $vastaavat_html .= $vastaavat_table;
    $vastaavat_html .= "</th></tr></table>";

    $vastaavattuotteet = 1;
    $paarivin_saldokysely = true;
  }
  else {

    $query = "SELECT tt.tehdas_saldo,
              toimi.tehdas_saldo_tarkistus
              FROM tuotteen_toimittajat AS tt
              JOIN toimi ON (toimi.yhtio = tt.yhtio AND toimi.tunnus = tt.liitostunnus)
              WHERE tt.yhtio      = '{$kukarow['yhtio']}'
              AND tt.tuoteno      = '{$row['tuoteno']}'
              AND tt.liitostunnus = '{$row['toimittajan_tunnus']}'
              LIMIT 1";
    $tehdas_saldo_res = pupe_query($query);
    $tehdas_saldo_row = mysql_fetch_assoc($tehdas_saldo_res);

    if ($tehdas_saldo_row['tehdas_saldo'] != 0 or $tehdas_saldo_row['tehdas_saldo_tarkistus'] != '') {
      $vastaavat_html = "<table width='100%'><tr style='width: 100%;'><th style='text-align: center;'>";
      $vastaavat_table = "";

      $vastaavat_html .= "<div style='float: right;'>".t("Tehtaan saldo").": ".round($tehdas_saldo_row['tehdas_saldo'])."</div>";

      $vastaavat_html .= "</th></tr></table>";
      $vastaavattuotteet = 1;
    }
  }
}

pupeslave_stop();
