<?php
//sisään tulee $row jossa on tilausrivi kaikki tiedot

$riviok         = 0;
$saako_hyvaksya     = 0;
$saako_jalkitoimittaa   = 0;
$vastaavattuotteet    = 0;
$varaosavirhe       = "";
$vastaavat_html      = "";

$tquery  = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
$tres  = pupe_query($tquery);
$trow   = mysql_fetch_assoc($tres); // haetaan tuotteen tiedot..

if ($toim == "YLLAPITO") {
  // Jos ollaan syötetty poikkeavat sopimuksen alku-/loppupäivät tilausriville, tarkistetaan että ne on oikeelliset
  if ($row["kerayspvm"] != '0000-00-00' or $row["toimaika"] != '0000-00-00') {

    $sopimusquery = "SELECT sopimus_alkupvm, sopimus_loppupvm
                     FROM laskun_lisatiedot
                     WHERE yhtio = '{$kukarow["yhtio"]}'
                     AND otunnus = '{$laskurow["tunnus"]}'";
    $sopimus_result = pupe_query($sopimusquery);
    $sopimus_row = mysql_fetch_assoc($sopimus_result);

    // tilausrivi.kerayspvm == poikkeava alkupäivä
    if ($row["kerayspvm"] != '0000-00-00' and $row["kerayspvm"] < $sopimus_row["sopimus_alkupvm"]) {
      $varaosavirhe .= t("VIRHE: Poikkeava alkupäivä ei voi olla vanhempi kun sopimuksen alkupäivä!")."<br>";
      $riviok++;
      $tilausok++;
    }

    if ($row["kerayspvm"] != '0000-00-00' and $row["kerayspvm"] > $sopimus_row["sopimus_loppupvm"]) {
      $varaosavirhe .= t("VIRHE: Poikkeava alkupäivä ei voi olla uudempi kun sopimuksen loppupäivä!")."<br>";
      $riviok++;
      $tilausok++;
    }

    // tilausrivi.toimaika == poikkeava loppupäivä
    if ($row["toimaika"] != '0000-00-00' and $row["toimaika"] < $sopimus_row["sopimus_alkupvm"]) {
      $varaosavirhe .= t("VIRHE: Poikkeava loppupäivä ei voi olla vanhempi kun sopimuksen alkupäivä!")."<br>";
      $riviok++;
      $tilausok++;
    }

    if ($row["toimaika"] != '0000-00-00' and $row["toimaika"] > $sopimus_row["sopimus_loppupvm"] and $sopimus_row["sopimus_loppupvm"] != "0000-00-00") {
      $varaosavirhe .= t("VIRHE: Poikkeava loppupäivä ei voi olla uudempi kun sopimuksen loppupäivä!")."<br>";
      $riviok++;
      $tilausok++;
    }

    if ($row["kerayspvm"] != '0000-00-00' and $row["toimaika"] != '0000-00-00' and $row["kerayspvm"] > $row["toimaika"]) {
      $varaosavirhe .= t("VIRHE: Poikkeava alkupäivä ei voi olla uudempi kun poikkeava loppupäivä!")."<br>";
      $riviok++;
      $tilausok++;
    }
  }
}

if ($trow['panttitili'] == 'K' and in_array($toim, array('PIKATILAUS', 'RIVISYOTTO', 'REKLAMAATIO'))) {
  $query = "SELECT panttitili FROM asiakas WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$laskurow['liitostunnus']}'";
  $chk_res = pupe_query($query);
  $chk_row = mysql_fetch_assoc($chk_res);

  if ($chk_row['panttitili'] == 'K' and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS' or $toim == 'REKLAMAATIO')) {
    if ($row['varattu'] < 0) {
      if ($row['var2'] == 'PANT') {
        $varaosavirhe .= t("HUOM: Panttituote, hyvitetään päittäin alkuperäinen panttiveloitus")."<br>";
      }
      else {
        $varaosavirhe .= t("HUOM: Panttitilillinen tuote, tuote hyvitetään panttitililtä")."<br>";
      }
    }
    else {
      if ($row['var2'] == 'PANT') {
        $varaosavirhe .= t("HUOM: Panttituote, veloitetaan asiakkaalta")."<br>";
      }
      else {
        $varaosavirhe .= t("HUOM: Panttitilillinen tuote, veloitus siirretään panttitilille")."<br>";
      }
    }
  }
}

if (isset($alkupera_trow) and $yhtiorow["korvaavat_hyvaksynta"] != "" and $alkupera_trow["tuoteno"] != $row["tuoteno"] and !in_array($row["var2"], array("OK", "PK")) and $korvattiin_korvaavalla == "HIIOHEI!") {
  $varaosavirhe .= t("HUOM: tämä on korvaava tuote, haluatko hyväksyä?")."<br>";
  $riviok++;
  $tilausok++;
  $saako_hyvaksya++;
}

if ($kukarow['extranet'] != '' and $trow['sarjanumeroseuranta'] != '' and $row['varattu'] != 1) {
  $varaosavirhe .= t("HUOM: Tämä on sarjanumeroseurannallinen tuote. Kappalemäärä pitää olla 1")."!<br>";
  $riviok++;
  $tilausok++;
}

$korvattiin_korvaavalla = "";
$pikaperustus_naytetaan = 0;

if (mysql_num_rows($tres) == 0) {
  $varaosavirhe .= t("VIRHE: Tuotetta ei löydy!")."<br>";
  $riviok++;
  $tilausok++;
  $pikaperustus_naytetaan = 1;
}

if ($row["var2"] == "PK" and ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") AND $yhtiorow['reklamaation_hinnoittelu'] == 'K') {
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

if ($row["varattu"] == 0 and in_array($row["var"], array('','H')) and $row["tyyppi"] != "E" and $row["tyyppi"] != "G") {
  if ($kukarow['extranet'] != '' and $row['positio'] == 'Ei varaa saldoa') {
    // $varaosavirhe .= t("HUOM: Rivi on liian vanha ja tuote ei varaa saldoa")."! ($row[tilkpl] ".t("kpl").")<br/>";
  }
  else {
    $varaosavirhe .= t("VIRHE: Määrä puuttuu!")."<br>";
  }
  $riviok++;
  $tilausok++;
}

if (in_array($row["var"], array('S','U','T','R', 'J')) and $row["tyyppi"] != "E") {
  if (($yhtiorow["varaako_jt_saldoa"] == "") and $row["jt"] == 0) {
    $varaosavirhe .= t("VIRHE: Määrä puuttuu!")."<br>";
    $riviok++;
    $tilausok++;
  }
  if ($yhtiorow["varaako_jt_saldoa"] != "" and $row["varattu"] == 0) {
    $varaosavirhe .= t("VIRHE: Määrä puuttuu!")."<br>";
    $riviok++;
    $tilausok++;
  }
}

if (in_array($row['var'], array('U','T')) and $meapurow['kateinen'] != '') {

  if ($row['var'] == 'U') {
    $varaosavirhe .= t("VIRHE: Et voi tilata toimittajalta asiakkaalle, koska maksuehto on käteinen!")."<br>";
  }
  else {
    $varaosavirhe .= t("VIRHE: Et voi tilata toimittajalta varastoon, koska maksuehto on käteinen!")."<br>";
  }

  $riviok++;
  $tilausok++;
}

// katsotaan, ettei valmisteta epäkuranttia tuotetta
if (($toim == "VALMISTAVARASTOON" OR $toim == 'VALMISTAASIAKKAALLE') AND $trow["epakurantti25pvm"] != '0000-00-00' AND ($row["tyyppi"] == "W" OR $row["tyyppi"] == "M") AND $riviok == 0) {
  $varaosavirhe .= t("VIRHE: Tuote on epäkurantti, sitä ei saa valmistaa!");
  $riviok++;
  $tilausok++;
}

if (in_array($row['var'], array('U','T', 'R')) and $trow['epakurantti25pvm'] != '0000-00-00') {
  $varaosavirhe .= t("HUOM: tuote on epäkurantti!")."<br>";
}

if ($row["hinta"] < 0) {
  $varaosavirhe .= t("VIRHE: Hinta ei voi olla negatiivinen!")."<br>";
  $riviok++;
  $tilausok++;
}

if (
  (in_array($row["var"], array('J' ,'S' ,'U', 'T')) and $row["jt"] < 0)
  or (in_array($row["tyyppi"], array('V', 'W', 'M', 'E')) and $row["varattu"] < 0 )
) {
  $varaosavirhe .= t("VIRHE: Määrä ei saa olla negatiivinen!")."<br>";
  $riviok++;
  $tilausok++;
}

if ($row["var"] == "J" and $row["tyyppi"] == "E") {
  $varaosavirhe .= t("VIRHE: Et voi syöttää jt-riviä ennakkotilaukseen!")."<br>";
  $riviok++;
  $tilausok++;
}

//  Pakkokommentoitava tuote
if ($trow["kommentoitava"] != "" and $row["kommentti"] == "") {
  $varaosavirhe .= t("VIRHE: Tuotetta on kommentoitava!")."<br>";
  $riviok++;
  $tilausok++;
}

if (!in_array($row["var"], array('','P','J','H','S','T','U','V','A','B','R','O'))) {
  // Tässä varrien selitykset
  // P - Puute
  // J - Jt
  // H - Väkisinhyväksytty
  // T - Tilataan joltain toimittajalta omaan varastoon
  // U - Tilataan joltain toimittajalta suoraan asiakkaalle
  // V - Tehdaslisävarusterivi
  // A - Myyntitilirivi joka laskutetaan asiakkaalta
  // B - Myyntitilirivi joka palautetaan myyntitilistä omaan varastoon
  // R - Rakennettava rivi
  // S - who knows?
  // O - Optio


  $varaosavirhe .= t("VIRHE: Var voi olla tyhjä, P, J, H, S, T, U, V, A, B, O tai R!")."<br>";
  $riviok++;
  $tilausok++;
}

if ($row["tyyppi"] == 'V' and $row["perheid"] == 0 and $toim == "VALMISTAVARASTOON") {
  $varaosavirhe .= t("VIRHE: Tuote ei kuulu mihinkään reseptiin!")."<br>";
  $riviok++;
  $tilausok++;
}

if ($row["var"] != '' and $toim == "SIIRTOTYOMAARAYS") {
  $varaosavirhe .= t("VIRHE: Sisäisellä työmääräyksellä Var pitää olla tyhjä!")."<br>";
  $riviok++;
  $tilausok++;
}

if (($row["perheid2"] == $row["tunnus"] or $row["perheid2"] == 0) and $row["sarjanumeroseuranta"] == "" and $toim == "SIIRTOTYOMAARAYS") {
  $varaosavirhe .= t("VIRHE: Jatkojalostettava tuote on oltava sarjanumeroeurannassa!")."<br>";
  $riviok++;
  $tilausok++;
}

if (($row["perheid2"] == $row["tunnus"] or $row["perheid2"] == 0) and $row["varattu"] != 1 and $toim == "SIIRTOTYOMAARAYS") {
  $varaosavirhe .= t("VIRHE: Jatkojalostettava määrä on oltava yksi!")."<br>";
  $riviok++;
  $tilausok++;
}

if ($row["perheid2"] == 0 and $row["sarjanumeroseuranta"] != "" and $toim == "SIIRTOTYOMAARAYS") {
  $varaosavirhe .= t("HUOM: Lisävarusteita ei ole liitetty!")."<br>";
  $riviok++;
  $tilausok++;
}

if ($row["varattu"] <= 0 and $toim == "SIIRTOTYOMAARAYS") {
  $varaosavirhe .= t("HUOM: Määrä on oltava suurempi kuin nolla!")."<br>";
  $riviok++;
  $tilausok++;
}

if (!in_array($row["netto"], array('','N','E'))) {
  $varaosavirhe .= t("VIRHE: Netto voi olla tyhjä, N tai E!")."<br>";
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

if ($kukarow["extranet"] == "" and in_array($trow["status"], array("P","X")) and !in_array($row["var2"], array("OK", "PK"))) {
  $varaosavirhe .= t("HUOM: Tämä on poistuva tuote!")."<br>";
  $riviok++;
}

if (in_array($trow["status"], array("P","X")) and $row["var"] == "J") {
  $varaosavirhe .= t("VIRHE: Tämä on poistuva tuote, ei voida jättää jälkitoimitukseen!")."<br>";
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
  $varaosavirhe .= t("VIRHE: Kappalemäärä pitää olla kokonaisluku")."!<br>";
  $riviok++;
  $tilausok++;
}

if (trim($trow["vienti"]) != '' and $row["tyyppi"] != "V" and $row["tyyppi"] != "W" and ($row["var2"] != "OK" or $yhtiorow["vientikiellon_ohitus"] != "K")) {

  // vientikieltokäsittely:
  // +maa tarkoittaa että myynti on kielletty tähän maahan
  // -maa tarkoittaa että ainoastaan tähän maahan saa myydä
  // eli näytetään vaan tuotteet jossa vienti kentässä on tyhjää tai -maa.. ja se ei saa olla +maa

  if (strpos(strtoupper($trow["vienti"]), strtoupper("+$laskurow[toim_maa]")) !== FALSE and strpos($trow["vienti"], "+") !== FALSE) {
    //ei saa myydä tähän maahan
    $varaosavirhe .= t("VIRHE: Tätä tuotetta ei saa myydä maahan")." $laskurow[toim_maa]!<br>";
     $riviok++;
     $tilausok++;
    $kielletty++;

    // Vientikiellossa olevan tuotteen voi hyväksyä myyntitilaukselle
    if ($yhtiorow["vientikiellon_ohitus"] == "K" and $kukarow["extranet"] == "") {
      $saako_hyvaksya++;
    }
  }

  if (strpos(strtoupper($trow["vienti"]), strtoupper("-$laskurow[toim_maa]")) === FALSE and strpos($trow["vienti"], "-") !== FALSE) {
    //ei saa myydä tähän maahan
    $varaosavirhe .= t("VIRHE: Tätä tuotetta ei saa myydä maahan")." $laskurow[toim_maa]!!<br>";
     $riviok++;
     $tilausok++;
    $kielletty++;

    // Vientikiellossa olevan tuotteen voi hyväksyä myyntitilaukselle
    if ($yhtiorow["vientikiellon_ohitus"] == "K" and $kukarow["extranet"] == "") {
      $saako_hyvaksya++;
    }
  }
}

if ($row["var"] == 'S') {
  // otetaan maksuehto selville..
  $query = "SELECT *
            from maksuehto
            where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
  $maksuehtoresult = pupe_query($query);
  $maksuehtorow = mysql_fetch_assoc($maksuehtoresult);

  if ($maksuehtorow["jv"] != "") {
    $varaosavirhe .= t("VIRHE: Et voi suoratoimittaa tuotetta jos maksuehtona on jälkivaatimus!")."<br>";
    $riviok++;
    $tilausok++;
  }
}

// Otetaan oikea kappalemäärä käsittelyyn
if (in_array($row["var"], array('J', 'S', 'T', 'U'))) {
  // jos yhtiön parametreissä jt-rivit varaavat saldoa, laitetaan "varattu kpl"-määrä, muuten "jt kpl"-määrä
  $kpl_st = $yhtiorow['varaako_jt_saldoa'] != '' ? $row['varattu'] : $row['jt'];
}
elseif ($row["var"] == 'P') {
  $kpl_st = $row['tilkpl'];
}
else {
  $kpl_st = $row['varattu'];
}

if (isset($myyntierahuom) and is_array($myyntierahuom) and in_array($row['tunnus'], $myyntierahuom) and $kpl_st != $org_kpl) {

  $mimyhuom = "HUOM: Rivin määrä on pyöristetty";

  if ($trow["minimi_era"] > 0) {
    $mimyhuom .= " minimierään";
  }

  if ($trow['myynti_era'] > 0 and $yhtiorow['myyntiera_pyoristys'] == 'K') {
    if ($trow["minimi_era"] > 0) {
      $mimyhuom .= " tai";
    }

    $mimyhuom .= " täyteen myyntierään";
  }

  // Käännetään teksti
  $mimyhuom = t($mimyhuom)."!";

  if ($trow['myynti_era'] > 0) {
    $mimyhuom .= " ".t("Myyntierä on").": $trow[myynti_era]";
  }

  if ($trow["minimi_era"] > 0) {
    $mimyhuom .= " ".t("Minimierä on").": $trow[minimi_era]";
  }

  $varaosavirhe .= $mimyhuom."<br>";
}

//Tässä on kaikki saldoihin ja varastopaikkoihin liittyvät tsekit, eli EI tehdä saldottomille tuotteille, eikä tuoteperheen saldollisille lapsille jos koko perhe suoratoimitetaan
$paikat  = "";

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

  if ($yhtiorow["saldo_kasittely"] == "T") {
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

  // Katsotaan onko tälle tuotteelle toimittajaa ja oletusvienti on jotain vaihto-omaisuutta (Tilaa-toimittajalta drop-downiin mukaan)
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

  // katotaan jos myyntitilauksella voidaan tehdä valmistusta, niin pudotetaan dropdownin rajaa
  if (($kukarow["extranet"] == "" and $yhtiorow["tee_valmistus_myyntitilaukselta"] != '') and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS')) {
    $omapaikatraja = 1;
  }

  // Katotaan voidaanko tämä myydä joltain toiselta paikalta
  if (!in_array($row["var"], array('J','S','U','T','P')) and $kpl_st > 0 and in_array($trow["sarjanumeroseuranta"], array('E', 'F', 'G'))) {

    $paikat .= "<option value='EIERAAVIELA#!¡!#EIEI'>".t("Valitse erä")."</option>";
    $paikatlask++;

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
              JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
              and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
              and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
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
              JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
              and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
              and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
              WHERE tuote.yhtio = '$kukarow[yhtio]'
              and tuote.tuoteno = '$row[tuoteno]'
              ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
  }

  $omavarastores  = pupe_query($query);
  $varastot_array = array();

  if ($kukarow["varasto"] != "") {
    $varastot_array = explode(",", $kukarow["varasto"]);
  }

  // Käydään läpi oman varaston paikat
  if (
    in_array($yhtiorow["puute_jt_oletus"], array('H','O'))
    or (in_array($row["var"], array('','H','S','T','U','J')) and mysql_num_rows($omavarastores) >= $omapaikatraja)
    or (in_array($row["var"], array('S','T','U','J')) and mysql_num_rows($omavarastores) > 0)
  ) {
    if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
         $query  = "SELECT sarjanumeroseuranta.sarjanumero era
                    FROM sarjanumeroseuranta
                    WHERE yhtio          = '$kukarow[yhtio]'
                    and tuoteno          = '$row[tuoteno]'
                    and myyntirivitunnus = '$row[tunnus]'
                    LIMIT 1";
         $sarjares = pupe_query($query);
         $sarjarow = mysql_fetch_assoc($sarjares);
    }

    while ($alkurow = mysql_fetch_assoc($omavarastores)) {

      if ($alkurow["hyllyalue"] != "!!M"
        and (
          (
            $alkurow['varastotyyppi'] == 'E'
            and $kukarow['kassamyyja'] == ''
            and $maksuehtorow["jv"] == ""
            and $alkurow['erikoistoimitus_alarajasumma'] > 0
            and (float) $row['myyntihinta'] >= (float) $alkurow['erikoistoimitus_alarajasumma']
          )
          or ($alkurow["varastotyyppi"] != "E" and $laskurow["varasto"] == 0 and (in_array($alkurow["varasto"], $varastot_array) or count($varastot_array) == 0))
          or $laskurow["varasto"] == $alkurow["varasto"]
          or (in_array($alkurow["varasto"], $varastot_array) and $laskurow["varasto"] == 0)
          or ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"])
        )
      ) {

        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);

        $myytavissa = (float) $myytavissa;
        $lisa_row   = array();

        if ($alkurow["ostorivitunnus"] != "" and in_array($trow["sarjanumeroseuranta"], array("E","F","G"))) {
          //Jos erä on keksitty käsin täältä keräyksestä
          $query = "SELECT tyyppi, (varattu+kpl+jt) kpl, tunnus
                    FROM tilausrivi
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tuoteno = '$row[tuoteno]'
                    and tunnus  in ($alkurow[ostorivitunnus])";
          $lisa_res = pupe_query($query);
          $lisa_row = mysql_fetch_assoc($lisa_res);
        }

        if ((in_array($yhtiorow["puute_jt_oletus"], array('H','O')) and $row["tyyppi"] != 'V') or
          $myytavissa >= $kpl_st or
          ($row["var"] != "P" and $toim != "TARJOUS" and
          (($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"] and $trow["sarjanumeroseuranta"] != "E" and $trow["sarjanumeroseuranta"] != "F" and $trow["sarjanumeroseuranta"] != "G") or
           ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"] and ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and ($lisa_row["tyyppi"] == "O" or $lisa_row["kpl"] < 0 or $lisa_row["tunnus"] == $row["tunnus"]) and $sarjarow["era"] == $alkurow["era"])))) {

          $sel = "";

          if ($trow["sarjanumeroseuranta"] != "E" and $trow["sarjanumeroseuranta"] != "F" and $trow["sarjanumeroseuranta"] != "G" and !in_array($row["var"], array("P","S")) and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
            $sel = "SELECTED";

            $selpaikka = "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";
            $selpaikkamaa = $alkurow['varastomaa'];
            $selpaikkamyytavissa = $myytavissa;
          }
          elseif (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and $sarjarow["era"] == $alkurow["era"] and !in_array($row["var"], array("P","S")) and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
            $sel = "SELECTED";

            $selpaikka = "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso]";
            $selpaikkamaa = $alkurow['varastomaa'];
            $selpaikkamyytavissa = $myytavissa;

            if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
              $selpaikka .= " $alkurow[era]";
            }
          }

          $paikat .= "<option value='$alkurow[hyllyalue]#!¡!#$alkurow[hyllynro]#!¡!#$alkurow[hyllyvali]#!¡!#$alkurow[hyllytaso]";

          if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
            $paikat .= "#!¡!#$alkurow[era]";
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

          // jos toimitetaan erikoisvarastosta, niin käyttäjän pitää hyväksyä se
          if ($alkurow['varasto'] != $laskurow["varasto"] and $tm_toimitustaparow["nouto"] == "" and $sel != '' and !in_array($row["var2"], array("OK", "PK")) and $kukarow['kassamyyja'] == '' and $row['varattu'] > 0 and $alkurow['varastotyyppi'] == 'E' and $alkurow['erikoistoimitus_alarajasumma'] > 0 and (float) $row['myyntihinta'] >= (float) $alkurow['erikoistoimitus_alarajasumma']) {
            $varaosavirhe .= t("HUOM: toimitetaan erikoisvarastosta, toimitusaika 2pv. Haluatko hyväksyä")."?<br/>";
            $saako_hyvaksya++;

            $riviok++;
            $tilausok++;
          }

          $paikatlask++;
          $kotipaikatlask++;
        }
      }

    }
  }
  //  Jos ollaan livahdettu puutteeksi koitetaan josko tätä voitasiin myydä erikoisvarastosta
  elseif ($row["var"] == "P" and mysql_num_rows($omavarastores) > 0) {
    while ($alkurow = mysql_fetch_assoc($omavarastores)) {

      list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);

      if ($myytavissa > 0 and ($alkurow["varastotyyppi"] == "E" or $alkurow["hyllyalue"] == "!!M") and (($alkurow['vakkoodi'] == '' or $alkurow['vakkoodi'] == '0') or ($alkurow['vakkoodi'] != '' and $alkurow['vakkoodi'] != '0' and $tm_toimitustaparow["vak_kielto"] != 'K'))) {

        if ($kukarow["extranet"] != "") {
          if ($laskurow["toim_maa"] == $alkurow["varastomaa"]) {

            $ale_lisa_kala = generoi_alekentta_php($row, 'M', 'kerto');

            if ($yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] != 0 and $yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] > $row["hinta"]*$row["tilkpl"]*$ale_lisa_kala) {
              $varaosakommentti .= t("Tuote ainoastaan noudettavissa myymälöistä joissa varastoituna")."!<br>";
            }
            elseif ($yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] != 0 and $yhtiorow["erikoisvarastomyynti_alarajasumma_rivi"] < $row["hinta"]*$row["tilkpl"]*$ale_lisa_kala) {
              //jätetään puutteeksi
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
      $varaosakommentti .= " ".t("Tämä on tehdastilaustuote ja on saatavilla tilauksena")."!<br>";
    }
  }
  else {
    // meillä on vaan yks paikka..
    $alkurow = mysql_fetch_assoc($omavarastores);
    list(, , $selpaikkamyytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);
  }

  // Käydään läpi tuotteen toimittajat
  if ((
      $kukarow["extranet"] == ""
      or $yhtiorow['tuoteperhe_suoratoimitus'] == 'E'
    )
    and in_array($row["var"], array('','H','S','T','U','J','P'))
    and (
      $toim == "TARJOUS"
      or (!in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('','Z','Q')))
    )
    and $kpl_st > 0
    and $toim != "SIIRTOTYOMAARAYS"
  ) {
    if ($kotipaikatlask == 0 and !in_array($trow["sarjanumeroseuranta"], array('E', 'F', 'G'))) {
      $paikat = "<option value=''>".t("Jätä puutteeksi")."</option>".$paikat;
      $paikatlask++;
    }

    while ($krow = mysql_fetch_assoc($toimpaikres)) {
      $sel1 = "";
      $sel2 = "";

      // tarkistetaan voiko tuotteen tilata suoraan asiakkaalle tai varastoon. oletuksena voi...
      $tmp_varastoon   = 1;
      $tmp_asiakkaalle = 1;

      // ...kielletään tilaamasta varastoon jos yhtiöparametri ohjaa katsomaan tuotteelta ja siellä ei ole sallittu...
      if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('V', 'W', 'I', 'J'))
        and !in_array($trow['suoratoimitus'], array('S', 'X'))) {         // vain S ja X sallivat tilauksen varastoon
        $tmp_varastoon = 0;
      }

      // ...kielletään tilaamasta asiakkaalle jos yhtiöparametri ohjaa katsomaan tuotteelta ja siellä ei ole sallittu...
      if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('W', 'J'))
         and !in_array($trow['suoratoimitus'], array('S', 'U'))) {        // vain S ja U sallivat tilauksen asiakkaalle
        $tmp_asiakkaalle = 0;
      }

      // katsotaan onko tuote suoratoimituskelpoinen (vain ei tuoteperheet, tai tuoteperheiden saldolliset isät tai jos yhtiöllä suoratoimitetaan perheet per tuote)
      if ((   // jos on tuoteperheen lapsi, pitää lapsien suoratoimitus olla sallittu
          $yhtiorow['tuoteperhe_suoratoimitus'] == ''
          or $row["perheid"] == 0
          or $row["perheid"] == $row["tunnus"]
        )
        and ( // onko suoratoimitus yleensäkään sallittu
          in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'X', 'Y', 'A', 'B', 'C'))
          or (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('V', 'W', 'I', 'J')) and ($tmp_varastoon == 1 or $tmp_asiakkaalle == 1))
        )
      ) {
        // piirretäänkö oletukseksi tilata suoratoimituksena varastoon? ...
        if ($row["toimittajan_tunnus"] == $krow["liitostunnus"] and $row["hyllyalue"] == "" and $row["hyllynro"] == "") {

          if (($row["var"] == 'T' or ($row["var"] == "J" and $row["suoraan_laskutukseen"] == "")) and in_array($krow["suoratoimitus"], array('', 'X')) and $tmp_varastoon == 1) {
            $sel1          = "SELECTED";
            $selpaikka        = "$krow[nimi] --> ".t("tilaa")." ".t("varastoon");
            $selpaikkamaa      = "";
            $selpaikkamyytavissa = 0;
          }
          // ...entä asiakkaalle?
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
          $paikat .= "<option value='¡¡¡$krow[liitostunnus]' title='$title' $sel1>$title</option>";
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
  elseif ($kotipaikatlask == 0 and $kpl_st > 0 and $laskurow["tila"] != 'V') {
    $paikat = "<option value=''>".t("Jätä puutteeksi")."</option>".$paikat;
    $paikatlask++;
  }

  //  Tarkistetaan vielä voisimmeko kenties ehkä mahdollisesti valmistaa tätä asiakkaalle?
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
                JOIN varastopaikat ON varastopaikat.yhtio = tuote.yhtio
                  and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
                  and  concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
                WHERE tuote.yhtio = '$kukarow[yhtio]'
                and tuote.tuoteno = '$row[tuoteno]'";
      $valmpaikatres = pupe_query($query);

      while ($valmpaikatrow  = mysql_fetch_assoc($valmpaikatres)) {
        $sel = "";
        if ($row["hyllyalue"] == $valmpaikatrow["hyllyalue"] and $row["hyllynro"] == $valmpaikatrow["hyllynro"] and $row["hyllyvali"] == $valmpaikatrow["hyllyvali"] and $row["hyllytaso"] == $valmpaikatrow["hyllytaso"] and ($row["var"] == "R" or ($row["tilausrivilinkki"] > 0))) {
          $sel = "SELECTED";
        }

        // tallennetaan riville toimittajan tunnus
        $title = "$valmpaikatrow[varasto] --> ".t("valmista")." ($valmpaikatrow[hyllyalue]-$valmpaikatrow[hyllynro]-$valmpaikatrow[hyllyvali]-$valmpaikatrow[hyllytaso])";
        $paikat .= "<option value='¡¡V$valmpaikatrow[hyllyalue]#!¡!#$valmpaikatrow[hyllynro]#!¡!#$valmpaikatrow[hyllyvali]#!¡!#$valmpaikatrow[hyllytaso]' title='$title' $sel>$title</option>";
        $paikatlask++;
      }
    }
  }

  if ($paikat != "" and $paikatlask > 1 and $row["hyllyalue"] != "!!M") {
    $paikat = "<select name='paikka' style='width:120px;' onchange='submit();'>".$paikat."</select>";
  }
  elseif ($row["hyllyalue"] == "!!M") {
    $paikat = " $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]";
  }
  else {
    $paikat = "";
  }
}
// voidaan valita paikka myös valmistusten valmisteille
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
            JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
            and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
            and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
            WHERE tuote.yhtio = '$kukarow[yhtio]'
            and tuote.tuoteno = '$row[tuoteno]'
            ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
  $omavarastores  = pupe_query($query);
  $varastot_array = array();

  if ($kukarow["varasto"] != "") {
    $varastot_array = explode(",", $kukarow["varasto"]);
  }

  if (!isset($paikatlask)) $paikatlask = 0;

  // Käydään läpi oman varaston paikat
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

      $paikat .= "<option value='$alkurow[hyllyalue]#!¡!#$alkurow[hyllynro]#!¡!#$alkurow[hyllyvali]#!¡!#$alkurow[hyllytaso]";

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
    $paikat = "<select name='paikka' style='width:120px;' onchange='submit();'>".$paikat."</select>";
  }
  elseif ($row["hyllyalue"] == "!!M") {
    $paikat = " $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]";
  }
  else {
    $paikat = "";
  }
}
// jos kyseessä on tuoteperheen suoratoimitus suoraan asiakkaalle, niin tehdään isädropdown (dropdownin sisältö haetaan lapsilta).
elseif (
  (  $yhtiorow['tuoteperhe_suoratoimitus'] == 'E'
    or ($kukarow['extranet'] == '' and $yhtiorow['tuoteperhe_suoratoimitus'] == 'M'))
  and in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'W', 'J', 'Y', 'B', 'C'))
  and $trow["ei_saldoa"] != ""
  and $row["tyyppi"] == "L"
  and $kpl_st > 0
  and $row["tunnus"] == $row["perheid"]
  and in_array($row["var"], array('P','J','U','T'))
) {

  $query = "SELECT tuoteno
            FROM tilausrivi
            WHERE yhtio = '$kukarow[yhtio]'
            AND perheid = '$row[tunnus]'
            AND otunnus = '$row[otunnus]'";
  $perhe_tuoteno_res = pupe_query($query);

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

  $paikat = "<option value=''>".t("Jätä jälkitoimitukseen")."</option>".$paikat;

  while ($krow  = mysql_fetch_assoc($toimpaikres)) {
    $sel1 = "";

    if ($row["toimittajan_tunnus"] == $krow["liitostunnus"] and $row["hyllyalue"] == "" and $row["hyllynro"] == "") {
      $sel1 = 'SELECTED';
    }

    $title = "$krow[nimi] --> ".t("tilaa")." ".t("asiakkaalle");
    $paikat .= "<option value='!!!$krow[liitostunnus]' title='$title' $sel1>$title</option>";
  }

  $paikat = "<select name='paikka' style='width:120px;' onchange='submit();'>".$paikat."</select>";
}

//  Onko tätä varattuna siirtolistalla?
if ($kukarow["extranet"] == "" and $trow["ei_saldoa"] == "" and $row["tyyppi"] != "E" and $kpl_st > 0 and in_array($row["var"], array("J","U","T","P","R"))) {

  $query = "SELECT kohde.nimitys kohde, lahde.nimitys lahde, tila, alatila, sum(varattu+jt+kpl) kpl, lasku.tunnus, tilausrivi.yksikko
            FROM lasku USE INDEX (tila_index)
            JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D' and tilausrivi.tuoteno = '$trow[tuoteno]')
            LEFT JOIN varastopaikat lahde USE INDEX (PRIMARY) ON (lahde.yhtio = lasku.yhtio and lahde.tunnus = lasku.varasto)
            LEFT JOIN varastopaikat kohde USE INDEX (PRIMARY) ON (kohde.yhtio = lasku.yhtio and kohde.tunnus = lasku.clearing)
            WHERE lasku.yhtio  = '$kukarow[yhtio]'
            and lasku.tila     = 'G'
            and lasku.alatila  in ('','J','A','B','C','D','E','T','P')
            and lasku.tunnus  != '{$laskurow["tunnus"]}'
            GROUP BY tuoteno, lasku.tunnus";
  $siirtores = pupe_query($query);

  while ($siirtorow = mysql_fetch_assoc($siirtores)) {
    $laskutyyppi = $siirtorow["tila"];
    $alatila   = $siirtorow["alatila"];

    //tehdään selväkielinen tila/alatila
    require "inc/laskutyyppi.inc";

    $varaosakommentti .= t("Siirtolistalla")." ($siirtorow[tunnus]) on: $siirtorow[kpl] {".t_avainsana("Y", "", "and avainsana.selite='$siirtorow[yksikko]'", "", "", "selite")."} $siirtorow[lahde] -> $siirtorow[kohde] ($alatila)<br>";
  }

  // katotaan onko vielä ostotilauksella!
  $tulossa_query = "SELECT IFNULL(min(toimaika),'') paivamaara, IFNULL(max(toimaika),'') paivamaara2, sum(varattu) kpl, max(yksikko) yksikko
                    FROM tilausrivi
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tuoteno = '$row[tuoteno]'
                    AND varattu > 0
                    AND tyyppi  IN ('O','W')";
  $tulossa_result = pupe_query($tulossa_query);
  $tulossa_row = mysql_fetch_assoc($tulossa_result);

  if (mysql_num_rows($tulossa_result) > 0 and $tulossa_row["paivamaara"] != '' and $tulossa_row["kpl"] > 0) {
    $varaosakommentti .= "$tulossa_row[kpl] ".t_avainsana("Y", "", "and avainsana.selite='$tulossa_row[yksikko]'", "", "", "selite")." ".t("Tulossa")." ".tv1dateconv($tulossa_row['paivamaara']);
    if ($tulossa_row["paivamaara"] != $tulossa_row["paivamaara2"]) {
      $varaosakommentti .= " - ".tv1dateconv($tulossa_row['paivamaara2']);
    }
    $varaosakommentti .= "<br>";
  }
}

//  Onko tätä ostotilauksella?
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
  $varaosavirhe .= t("VIRHE: Tuotteelle ei löytynyt ALV-prosenttia asiakkaan maahan!")."<br>";
  $riviok++;
  $tilausok++;
  $saako_jalkitoimittaa++;
  $saako_hyvaksya = 0;
}

if ($row['tuoteno'] != '' and ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A'  or (($row["var"] == 'J' or $row["var"] == 'P') and !in_array($row["var2"], array("OK", "PK"))))) {

  // Haetaan vastaavuusketjut korvaavat ketjun päätuotteella
  require_once 'vastaavat.class.php';

  $vastaavat = new Vastaavat($row["tuoteno"]);

  if ($vastaavat->onkovastaavia()) {

    if ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A' and in_array($row["var2"], array("OK", "PK"))) {

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
                <input style='float: right;' type='Submit' value='".t("Näytä vastaavat tuotteet")."'>
                </form></th>";
      $vastaavat_html .= "</tr></table>";
      $vastaavattuotteet = 1;
    }
    else {

      // Loopataan kaikki tuotteen vastaavuusketjut
      foreach (explode(",", $vastaavat->getIDt()) as $ketju) {

        $vastaavat_table = "";

        // Haetaan tuotteet ketjukohtaisesti
        $_tuotteet = $vastaavat->tuotteet($ketju);

        foreach ($_tuotteet as $_tuote) {

          $vaihtoehtoinen = ($_tuote['vaihtoehtoinen'] == 'K') ? "<font color=#F00>".t("Vaihtoehtoinen, tarkista sopivuus")."!</font>" : "";

          $chk_varasto = $laskurow['varasto'] == 0 ? explode(",", $kukarow['varasto']) : $laskurow['varasto'];

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

            // poistetaan alvit (jos hintamuunnos päällä)
            if (isset($tilausrivi_alvillisuus) and $tilausrivi_alvillisuus == "E" and $yhtiorow["alv_kasittely"] == '' and $yhtiorow["alv_kasittely_hintamuunnos"] == 'o') {
              $V_hinta = round($V_hinta / (1+$row['alv']/100), $yhtiorow['hintapyoristys']);
            }
            // lisataan alvit (jos hintamuunnos päällä)
            if (isset($tilausrivi_alvillisuus) and $tilausrivi_alvillisuus == "K" and $yhtiorow["alv_kasittely"] == 'o' and $yhtiorow["alv_kasittely_hintamuunnos"] == 'o') {
              $V_hinta = round($V_hinta * (1+$row['alv']/100), $yhtiorow['hintapyoristys']);
            }

            $vastaavat_table .= " $vaihtoehtoinen</td>"
                  ."<td>{$tvrow["nimitys"]}</td>"
                   ."<td align='right'><font class='{$myytavissa_font}'>".sprintf("%.2f", $vmyytavissa)."</font></td>"
                   ."<td align='right'>".sprintf("%.2f", $V_hinta)."</td>";

            if ($sahkoinen_tilausliitanta AND ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'S' or $yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A')) {

              $query = "SELECT tt.tehdas_saldo,
                        tt.tehdas_saldo_paivitetty,
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
               $tehtaan_saldon_font = $tehdas_saldo_row['tehdas_saldo'] < 1 ? "error" : "ok";

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
                 $vastaavat_table .= "<font class='{$tehtaan_saldon_font}'>".sprintf("%.2f", $tehdas_saldo_row['tehdas_saldo'])."</font>";
               }

              $vastaavat_table .= "</td>";

              if ($tehdas_saldo_row['selitetark_2'] != "") {
                $vastaavat_table .= "<td align='right'></td>";
              }
              else {
                $vastaavat_table .= "<td class='{$_tuote['vastaavat_tunnus']}_{$_tilattu}_tehdas_saldo_paivitetty' align='right'>".tv1dateconv($tehdas_saldo_row['tehdas_saldo_paivitetty'], "PITKA")."</td>";
              }

              $vastaavat_table .= "<td>";

              if ($tehdas_saldo_row['selitetark_2'] != "") {
                $hae = 'nappi';
                $rivitunnus = $row['tunnus'];
                $_vastaavat_tunnus = $_tuote['vastaavat_tunnus'];
                $_vastaavat_tuoteno = $_tuote['toim_tuoteno'];

                require("inc/sahkoinen_tilausliitanta.inc");
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
                          <input type='Submit' value='".t("Tilaa tuote")."'>
                          </form>";
            }

            $vastaavat_table .= "</td></tr>";
          }
        }

        if ($vastaavat_table != "") {
          if ($vastaavattuotteet == 0) {
            $vastaavat_html .= "<table width='100%'>";
          }

          $vastaavat_html .= "<tr><th>".t("Vastaava")." ".t("Tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("Myytävissä")."</th><th>".t("Myyntihinta")."</th>";

          if ($sahkoinen_tilausliitanta and ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'S' or $yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A')) {
            $vastaavat_html .= "<th>".t("Toimittaja")."</th><th>".t("Toimittajan saldo")."</th><th>".t("Toimittajan saldo päivitetty")."</th>";
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
                      <input type='Submit' value='".t("Piilota vastaavat tuotteet")."'>
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

if ($sahkoinen_tilausliitanta and (!isset($vastaavat_html) or trim($vastaavat_html) == '') and (!isset($vastaavat_table) or trim($vastaavat_table) == '') and in_array($row['var'], array('U','T'))) {

  $query = "SELECT tt.tehdas_saldo,
            tt.tehdas_saldo_paivitetty,
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

    require("inc/sahkoinen_tilausliitanta.inc");

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
