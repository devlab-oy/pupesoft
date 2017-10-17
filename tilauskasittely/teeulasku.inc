<?php

// tarvitaan $tunnukset muuttuja, jossa on kaikki laskuun kuuluvat tilaukset mysql muodossa: 'nnn','nnn','nnn'
// palautetaan $laskurow array jossa on uuden U-laskun tiedot

$tulos_ulos_ulasku = "";

// lasketaan laskulle loppusumma.. ja otetaan kaikki mahdolliset eriävät tiedot talteen
$arvo          = 0;
$summa          = 0;
$summa_valuutassa     = 0;
$kate          = 0;
$bruttopaino      = 0;
$viestit         = "";
$comments         = "";
$sisviestit1       = "";
$sisviestit2       = "";
$tilausyhteyshenkilo   = "";
$asiakkaan_tilausnumero  = "";
$kohde           = "";
$useita_toimtapoja    = "EI";

$query  = "SELECT
           sum(arvo) arvo,
           sum(arvo_valuutassa) arvo_valuutassa,
           sum(arvo/if(vienti_kurssi=0, 1, vienti_kurssi)) arvo_valuutassa_matikka,
           sum(summa) summa,
           sum(summa_valuutassa) summa_valuutassa,
           sum(pyoristys) pyoristys,
           sum(pyoristys_valuutassa) pyoristys_valuutassa,
           sum(kate) kate,
           sum(bruttopaino) bruttopaino,
           group_concat(distinct if(trim(comments)='', NULL, trim(comments))                 SEPARATOR ' / ') comments,
           group_concat(distinct if(trim(sisviesti1)='', NULL, trim(sisviesti1))               SEPARATOR ' / ') sisviestit1,
           group_concat(distinct if(trim(sisviesti2)='', NULL, trim(sisviesti2))               SEPARATOR ' / ') sisviestit2,
           group_concat(distinct if(trim(viesti)='', NULL, trim(viesti))                   SEPARATOR ' / ') viestit,
           group_concat(distinct if(trim(tilausyhteyshenkilo)='', NULL, trim(tilausyhteyshenkilo))     SEPARATOR ' / ') tilausyhteyshenkilo,
           group_concat(distinct if(trim(asiakkaan_tilausnumero)='', NULL, trim(asiakkaan_tilausnumero))   SEPARATOR ' / ') asiakkaan_tilausnumero,
           group_concat(distinct if(trim(kohde)='', NULL, trim(kohde))                   SEPARATOR ' / ') kohde,
           count(distinct toimitustapa) toimitustavat,
           count(tunnus) tilausmaara
           FROM lasku
           WHERE yhtio = '$kukarow[yhtio]'
           and tunnus  in ($tunnukset)";
$uusiuresult = pupe_query($query);

if (mysql_num_rows($uusiuresult) > 0) {
  $sumrow = mysql_fetch_assoc($uusiuresult);

  $arvo          = round($sumrow["arvo"], 2);
  $arvo_valuutassa    = round($sumrow["arvo_valuutassa"], 2);
  $summa          = round($sumrow["summa"], 2);
  $summa_valuutassa    = round($sumrow["summa_valuutassa"], 2);
  $pyoristys        = round($sumrow["pyoristys"], 2);
  $pyoristys_valuutassa  = round($sumrow["pyoristys_valuutassa"], 2);
  $kate          = round($sumrow["kate"], 2);
  $bruttopaino      = round($sumrow["bruttopaino"], 2);

  // EE keississä lasketaan veron määrää saman kaavan mukaan ku laskun tulostuksessa alvierittelyssä
  // ja sit lopuksi summataan $summa+$alvinmaara jotta saadaan laskun verollinen loppusumma
  // Tämä operaatiota ei tehdä _valuutassa summalle
  // Tämä operaatio on tarpeellinen vain jos tilauksia on laskulla enemmän kuin yksi
  if (strtoupper($yhtiorow['maa']) == 'EE' and $sumrow["tilausmaara"] > 1) {

    $alvinmaara = 0;

    //Haetaan kaikki alvikannat riveiltä
    $alvquery = "SELECT DISTINCT alv
                 FROM tilausrivi
                 WHERE yhtio = '$kukarow[yhtio]'
                 and otunnus in ($tunnukset)
                 and tyyppi  = 'L'
                 and alv     < 500";
    $alvresult = pupe_query($alvquery);

    while ($alvrow = mysql_fetch_assoc($alvresult)) {

      $aquery = "SELECT
                 round(sum(tilausrivi.rivihinta * (tilausrivi.alv / 100)),2) alvrivihinta
                 FROM tilausrivi
                 JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                 WHERE tilausrivi.otunnus in ($tunnukset)
                 and tilausrivi.yhtio     = '$kukarow[yhtio]'
                 and tilausrivi.alv       = '$alvrow[alv]'
                 and tilausrivi.tyyppi    = 'L'";
      $aresult = pupe_query($aquery);
      $arow = mysql_fetch_assoc($aresult);

      $alvinmaara += $arow["alvrivihinta"];
    }

    $summa = $arvo+$alvinmaara;
  }

  // generoidaan vientikurssi
  if ($sumrow["arvo_valuutassa_matikka"] == 0) {
    $vienti_kurssi = 1;
  }
  else {
    $vienti_kurssi = round($arvo/$sumrow["arvo_valuutassa_matikka"], 9);
  }

  $viestit        = $sumrow["viestit"];
  $comments        = $sumrow["comments"];
  $sisviestit1      = $sumrow["sisviestit1"];
  $sisviestit2      = $sumrow["sisviestit2"];
  $tilausyhteyshenkilo  = $sumrow["tilausyhteyshenkilo"];
  $asiakkaan_tilausnumero  = $sumrow["asiakkaan_tilausnumero"];
  $kohde          = $sumrow["kohde"];

  if ($viestit == " / ")           $viestit         = "";
  if ($comments == " / ")         $comments         = "";
  if ($sisviestit1 == " / ")         $sisviestit1       = "";
  if ($sisviestit2 == " / ")         $sisviestit2       = "";
  if ($tilausyhteyshenkilo == " / ")     $tilausyhteyshenkilo   = "";
  if ($asiakkaan_tilausnumero == " / ")   $asiakkaan_tilausnumero = "";
  if ($kohde == " / ")           $kohde           = "";
}

// haetaan laskulle otsikkotiedot ketjun ekalta laskulta
$query  = "SELECT *
           FROM lasku
           WHERE yhtio = '$kukarow[yhtio]'
           and tunnus  in ($tunnukset)
           ORDER BY tunnus
           LIMIT 1";
$laskusplitres = pupe_query($query);
$lasrow = mysql_fetch_assoc($laskusplitres);

//haetaan laskun oletettu tulostuskieli ja t-funktioidaan sen kielen mukaisesti kantaa tallennettavat fraasit
$querykieli = "SELECT kieli
               FROM asiakas
               WHERE tunnus = '$lasrow[liitostunnus]'
               AND yhtio    = '$kukarow[yhtio]'";
$resultkieli = pupe_query($querykieli);
$kielirow = mysql_fetch_assoc($resultkieli);

$query  = "SELECT distinct toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp, toim_maa
           from lasku
           where yhtio = '$kukarow[yhtio]'
           and tunnus  in ($tunnukset)";
$uusiuresult = pupe_query($query);

// jos meillä on useita toimitusosoitteita, niin ei kirjoiteta vain ekaa siihen otsikolle. se on sekavaa.. kijotetaan vain "useita"
if (mysql_num_rows($uusiuresult) != 1) {
  $lasrow["toim_nimi"]     = t("Useita", $kielirow["kieli"]);
  $lasrow["toim_nimitark"] = t("Useita", $kielirow["kieli"]);
  $lasrow["toim_osoite"]   = t("Useita", $kielirow["kieli"]);
  $lasrow["toim_postino"]  = t("Useita", $kielirow["kieli"]);
  $lasrow["toim_postitp"]  = t("Useita", $kielirow["kieli"]);
  $lasrow["toim_maa"]      = $lasrow["maa"];
}

// Jos laskulla on useita toimitustapoja niin laitetaan sinne tekstiksi "USEITA"
if ($sumrow["toimitustavat"] > 1) {
  $lasrow["toimitustapa"]     = t("Useita", $kielirow["kieli"]);
  $useita_toimtapoja = "KYLLA";
}

// haetaan maksuehdon tiedot, jotta osataan tiliöidä käteismyynti kassaan
$query = "SELECT *
          FROM maksuehto
          WHERE yhtio = '$kukarow[yhtio]'
          and tunnus  = '$lasrow[maksuehto]'";
$xresult = pupe_query($query);

if (mysql_num_rows($xresult) == 0) {
  $mehtorow = array();

  if ($lasrow["erpcm"] == "0000-00-00") {
    $tulos_ulos_ulasku .= "<font class='message'><br>".t("Maksuehtoa", $kielirow["kieli"])." $lasrow[maksuehto] ".t("ei löydy", $kielirow["kieli"])."! ".t("Tunnus", $kielirow["kieli"])." $lasrow[tunnus] ".t("Laskunro", $kielirow["kieli"])." $lasrow[laskunro] ".t("epäonnistui pahasti", $kielirow["kieli"])."!</font><br><br>\r\n";
  }
}
else {
  $mehtorow = mysql_fetch_assoc($xresult);
}

// jos kyseessä on käteislasku, laitetaan maksaja ja maksupäivä mukaan
$maksupaiva = "''";
$maksaja    = "''";

if ($mehtorow['kateinen'] != '' or (float) $summa == 0) {
  $maksaja    = "$kukarow[kuka]";
  $maksupaiva = "now()";

  if ($mehtorow['kateinen'] != '' and $poikkeava_pvm != "") {
    $maksupaiva = "'$laskvv-$laskkk-$laskpp'";
  }
}
elseif ($yhtiorow["verkkolasku_lah"] == "talenom") {
  // Talenom hoitaa myyntireskontraa, joten ei jätetä laskuja auki Pupen reskontraan
  $maksaja    = "$kukarow[kuka]";
  $maksupaiva = "'$lasrow[erpcm]'";
}

///* Lasketaan Kassa-alennus *///
$kassaale         = round($summa*$mehtorow['kassa_alepros']/100, 2);
$kassaale_valuutassa   = round($summa_valuutassa*$mehtorow['kassa_alepros']/100, 2);

$lisa_comments   = "";
$lisa_viestit  = "";

// Jos kyseessä on factoring niin laitetaan laskun kommenttiin talteen factoring siirtoteksti
if (isset($mehtorow["factoring_id"])) {
  $query = "SELECT *
            FROM factoring
            WHERE yhtio  = '$kukarow[yhtio]'
            and tunnus   = '$mehtorow[factoring_id]'
            and valkoodi = '$lasrow[valkoodi]'";
  $fres = pupe_query($query);

  if (mysql_num_rows($fres) > 0) {
    $frow = mysql_fetch_assoc($fres);

    $kieliind = "teksti_".$kielirow["kieli"];

    if (trim($frow[$kieliind]) != "" and stripos($sisviestit1, $frow[$kieliind]) === FALSE) $lisa_comments .= trim($frow[$kieliind])." \n\n";
    elseif (stripos($sisviestit1, $frow["teksti_fi"]) === FALSE) $lisa_comments .= trim($frow["teksti_fi"])." \n\n";
  }
}

// Jos kyseessä on direct_debit niin laitetaan laskun kommenttiin infoteksti
if (isset($mehtorow["directdebit_id"])) {
  $query = "SELECT *
            FROM directdebit
            WHERE yhtio  = '$kukarow[yhtio]'
            and tunnus   = '$mehtorow[directdebit_id]'";
  $ddres = pupe_query($query);

  if (mysql_num_rows($ddres) > 0) {
    $ddrow = mysql_fetch_assoc($ddres);

    $kieliind = "teksti_".$kielirow["kieli"];

    if (trim($ddrow[$kieliind]) != "" and stripos($sisviestit1, $ddrow[$kieliind]) === FALSE) $lisa_comments .= trim($ddrow[$kieliind])." \n\n";
    elseif (stripos($sisviestit1, $ddrow["teksti_fi"]) === FALSE) $lisa_comments .= trim($ddrow["teksti_fi"])." \n\n";
  }
}

if ($lasrow["vienti"] == 'E') {

  if ($lasrow['kolmikantakauppa'] != "") {
    if (stripos($sisviestit1, "VAT 0% Triangulation.") === FALSE) $lisa_comments .= "VAT 0% Triangulation. \n";
  }
  else {
    //  Tarkastetaan onko laskulla vain saldotonta kamaa, se muuttaa kommenttia..
    $query = "SELECT tilausrivi.tunnus
              FROM tilausrivi
              JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa='')
              WHERE tilausrivi.yhtio='$kukarow[yhtio]'
              and tilausrivi.otunnus in ($tunnukset)";
    $abulisares = pupe_query($query);

    if (mysql_num_rows($abulisares) == 0) {
      if (stripos($sisviestit1, "Reverse charge, intangible service.") === FALSE) $lisa_comments .= "Reverse charge, intangible service. \n";
    }
    else {
      if (stripos($sisviestit1, "VAT 0% Intra community supply.") === FALSE) $lisa_comments .= "VAT 0% Intra community supply. \n";
    }
  }
}

if ($lasrow["vienti"] == 'K') {
  //  Tarkastetaan onko laskulla vain saldotonta kamaa, se muuttaa kommenttia..
  $query = "SELECT tilausrivi.tunnus
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa='')
            WHERE tilausrivi.yhtio='$kukarow[yhtio]'
            and tilausrivi.otunnus in ($tunnukset)";
  $abulisares = pupe_query($query);

  if (mysql_num_rows($abulisares) == 0) {
    if (stripos($sisviestit1, "VAT 0%") === FALSE) $lisa_comments .= "VAT 0% \n";
  }
  else {
    if (stripos($sisviestit1, "VAT 0% Export of goods outside the European Union.") === FALSE) $lisa_comments .= "VAT 0% Export of goods outside the European Union. \n";
  }
}

if ($lasrow["vienti"] != '') {
  // Laitetaan kollimäärät ja kilot mukaan kommenttiin vientilaskuille
  $query = "SELECT pakkaus, pakkauskuvaus, pakkauskuvaustark, sum(kilot) kilot, sum(kollit) kollit
            FROM rahtikirjat
            WHERE otsikkonro in ($tunnukset)
            and yhtio        = '$kukarow[yhtio]'
            GROUP BY pakkaus, pakkauskuvaus, pakkauskuvaustark
            HAVING kollit > 0";
  $rahtiresult = pupe_query($query);

  while ($rahtirow = mysql_fetch_assoc($rahtiresult)) {

    // Haetaan pakkauksen tiedot
    $query = "SELECT *
              from pakkaus
              WHERE yhtio        = '$kukarow[yhtio]'
              and pakkaus        = '$rahtirow[pakkaus]'
               and pakkauskuvaus = '$rahtirow[pakkauskuvaus]'";
    $spakkausresult = pupe_query($query);
    $spakkausrow = mysql_fetch_assoc($spakkausresult);

    $rahtirow_pakkaus = t_tunnus_avainsanat($spakkausrow, "pakkaus", "PAKKAUSKV", $kielirow["kieli"]);
    $rahtirow_pakkauskuvaus = t_tunnus_avainsanat($spakkausrow, "pakkauskuvaus", "PAKKAUSKV", $kielirow["kieli"]);

    if ($rahtirow_pakkaus != $rahtirow_pakkauskuvaus) {
      $rahtirow_pakkaus .= " ".$rahtirow_pakkauskuvaus;
    }

    // Tämä on käsin syötetty, ei voida kääntää
    if ($rahtirow["pakkauskuvaustark"] != '') {
      $rahtirow_pakkaus .= ", ".$rahtirow["pakkauskuvaustark"];
    }

    $ralisvie = "$rahtirow[kollit] x ".trim($rahtirow_pakkaus).", ".t("yhteensä", $kielirow["kieli"])." $rahtirow[kilot] kg.";

    if (stripos($sisviestit1, $ralisvie) === FALSE) $lisa_comments .= $ralisvie." \n";
  }
}

//Laitetaan laskun viestikenttään talteen tiettyjä lisätietoja
if ($mehtorow["jv"] != '') {
  $lisa_viestit  .= t("Kuitti jälkivaatimuksesta", $kielirow["kieli"]);
  $jvlisvie = t("Kuitataan jälkivaatimuksella saaduksi. TÄTÄ LASKUA EI TULE MAKSAA!", $kielirow["kieli"]);

  if (stripos($sisviestit1, $jvlisvie) === FALSE) $lisa_comments .= $jvlisvie." \n";
}

// Haetaan avainsanoista vakioviesti
$query = "SELECT *
          FROM avainsana
          WHERE yhtio = '$kukarow[yhtio]'
          AND kieli   = '$kielirow[kieli]'
          AND laji    = 'VAKIOVIESTI'
          ORDER BY jarjestys
          LIMIT 1";
$vakioviesti_result = pupe_query($query);

if (mysql_num_rows($vakioviesti_result) == 1) {
  $vakioviesti_row = mysql_fetch_assoc($vakioviesti_result);

  if (stripos($sisviestit1, $vakioviesti_row["selite"]) === FALSE) $lisa_comments .= trim($vakioviesti_row["selite"]." ".$vakioviesti_row["selitetark"])." \n";
}

// Lisätään järjestelmän generoimat kommentit laskun kommenttikenttään
if ($lisa_comments != "") {
  $sisviestit1 = trim($lisa_comments." ".$sisviestit1);
}

// Lisätään järjestelmän generoimat viestit laskun viestikenttään
if ($lisa_viestit != "") {
  $viestit = trim($lisa_viestit." ".$viestit);
}

$mainosteksti = $lasrow['mainosteksti'];

//Siivotaan kommenteista kaikki kummalliset merkit pois
$comments = pupesoft_invoicestring($comments, "\r\n");
$viestit = pupesoft_invoicestring($viestit, "\r\n");
$sisviestit1 = pupesoft_invoicestring($sisviestit1, "\r\n");
$sisviestit2 = pupesoft_invoicestring($sisviestit2, "\r\n");
$tilausyhteyshenkilo = pupesoft_invoicestring($tilausyhteyshenkilo, "\r\n");
$asiakkaan_tilausnumero = pupesoft_invoicestring($asiakkaan_tilausnumero, "\r\n");
$kohde = pupesoft_invoicestring($kohde, "\r\n");
$mainosteksti = pupesoft_invoicestring($mainosteksti, "\r\n");

$fields = "yhtio";
$values = "'$kukarow[yhtio]'";

// Ei monisteta tunnusta
for ($teeulasku_i=1; $teeulasku_i < mysql_num_fields($laskusplitres)-1; $teeulasku_i++) {

  $fieldname = mysql_field_name($laskusplitres, $teeulasku_i);

  $fields .= ", ".$fieldname;

  switch ($fieldname) {
  case 'arvo':
    $values .= ", '$arvo'";
    break;
  case 'arvo_valuutassa':
    $values .= ", '$arvo_valuutassa'";
    break;
  case 'pyoristys':
    $values .= ", '$pyoristys'";
    break;
  case 'pyoristys_valuutassa':
    $values .= ", '$pyoristys_valuutassa'";
    break;
  case 'vienti_kurssi':
    $values .= ", '$vienti_kurssi'";
    break;
  case 'asiakkaan_tilausnumero':
    $values .= ", '$asiakkaan_tilausnumero'";
    break;
  case 'bruttopaino':
    $values .= ", '$bruttopaino'";
    break;
  case 'comments':
    $values .= ", '$comments'";
    break;
  case 'kasumma':
    $values .= ", '$kassaale'";
    break;
  case 'kasumma_valuutassa':
    $values .= ", '$kassaale_valuutassa'";
    break;
  case 'kate':
    $values .= ", '$kate'";
    break;
  case 'kate_korjattu':
  case 'lahetetty_ulkoiseen_varastoon':
    $values .= ", NULL";
    break;
  case 'kohde':
    $values .= ", '$kohde'";
    break;
  case 'mainosteksti':
    $values .= ", '{$mainosteksti}'";
    break;
  case 'laskutettu':
    $values .= ", now()";
    break;
  case 'laskuttaja':
    $values .= ", '$kukarow[kuka]'";
    break;
  case 'maksaja':
    $values .= ", '$maksaja'";
    break;
  case 'mapvm':
    $values .= ", $maksupaiva";
    break;
  case 'summa':
    $values .= ", '$summa'";
    break;
  case 'summa_valuutassa':
    $values .= ", '$summa_valuutassa'";
    break;
  case 'tilausyhteyshenkilo':
    $values .= ", '$tilausyhteyshenkilo'";
    break;
  case 'viesti':
    $values .= ", '$viestit'";
    break;
  case 'sisviesti1':
    $values .= ", '$sisviestit1'";
    break;
  case 'sisviesti2':
    $values .= ", '$sisviestit2'";
    break;
  case 'alatila':
    $values .= ", ''";
    break;
  case 'tila':
    $values .= ", 'U'";
    break;
  default:
    $values .= ", '".$lasrow[$fieldname]."'";
  }
}

$kysely  = "INSERT into lasku ($fields) VALUES ($values)";
$uusiotsikko = pupe_query($kysely);

// otetaan uuden otsikon tunnus talteen
$uusiotunnus = (string) mysql_insert_id($GLOBALS["masterlink"]);

// haetaan laskun lisätiedot ketjun ekalta laskulta
$query = "SELECT *
          FROM laskun_lisatiedot
          WHERE yhtio = '$kukarow[yhtio]'
          AND otunnus = '$lasrow[tunnus]'";
$lisatiedot_result = pupe_query($query);
$lisatiedot_row = mysql_fetch_assoc($lisatiedot_result);

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
    $query .= $fieldname."='$uusiotunnus',";
  }
  elseif ($fieldname != 'tunnus') {
    $query .= $fieldname."='".$lisatiedot_row[$fieldname]."',";
  }
}

$query = substr($query, 0, -1);
$lisatiedot_result = pupe_query($query);

// haetaan nyt varmuuden vuoksi vielä insertoidut tiedot takaisin..
$query = "SELECT *
          FROM lasku
          WHERE yhtio = '$kukarow[yhtio]'
          and tunnus  = '$uusiotunnus'";
$uusiuresult = pupe_query($query);

if (mysql_num_rows($uusiuresult)==0) die (t("Lasku")." $uusiotunnus ".t("katosi")."!");
$laskurow = mysql_fetch_assoc($uusiuresult);

// päivitetään tilausriveille uuden otsikon tiedot (ei päivitetä Puute ja Jt rivejä laskutetuiksi)
$query = "UPDATE tilausrivi
          SET uusiotunnus = '$laskurow[tunnus]'
          WHERE otunnus in ($tunnukset)
          and yhtio     = '$kukarow[yhtio]'
          and var       not in ('P','J','O','S')";
$ulasr = pupe_query($query);

// katotaan montako tilausta ollaan käsittelemässä
$montakotilausta = explode(",", $tunnukset);

$asiakas_apu_query = "SELECT *
                      FROM asiakas
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$laskurow[liitostunnus]'";
$asiakas_apu_res = pupe_query($asiakas_apu_query);
$asiakas_apu_row = mysql_fetch_assoc($asiakas_apu_res);

// katotaan miten halutaan sortattavan
// haetaan asiakkaan tietojen takaa sorttaustiedot
$order_sorttaus = '';

if (mysql_num_rows($asiakas_apu_res) == 1) {
  $sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["laskun_jarjestys"] != "" ? $asiakas_apu_row["laskun_jarjestys"] : $yhtiorow["laskun_jarjestys"]);
  $order_sorttaus = $asiakas_apu_row["laskun_jarjestys_suunta"] != "" ? $asiakas_apu_row["laskun_jarjestys_suunta"] : $yhtiorow["laskun_jarjestys_suunta"];
}
else {
  $sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);
  $order_sorttaus = $yhtiorow["laskun_jarjestys_suunta"];
}

// Asiakkaan / yhtiön laskutyyppi
if ($laskurow['laskutyyppi'] == -9 or $laskurow['laskutyyppi'] == 0) {
  //jos laskulta löytyvät laskutyyppi on Oletus käytetään asiakkaan tai yhtiön oletus laskutyyppiä
  if (isset($asiakas_apu_row['laskutyyppi']) and $asiakas_apu_row['laskutyyppi'] != -9) {
    $laskutyyppi = $asiakas_apu_row['laskutyyppi'];
  }
  else {
    $laskutyyppi = $yhtiorow['laskutyyppi'];
  }
}
else {
  $laskutyyppi = $laskurow['laskutyyppi'];
}

// jos meillä on enemmän kuin yksi tilaus ja kyseessä ei ole laskutyyppi PLAIN, lasketaan välisummat tilausriveillä
if (count($montakotilausta) > 1 and $laskutyyppi != 1 and $laskutyyppi != 6) {

  if ($yhtiorow["laskun_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
  else $pjat_sortlisa = "";

  //laitetaan välisummat rivin kommenttiin aina kun otunnus vaihtuu
  $query = "SELECT tilausrivi.otunnus, tilausrivi.tunnus,
            $sorttauskentta,
            if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi
            FROM tilausrivi
            JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
            WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
            and tilausrivi.otunnus  in ($tunnukset)
            and tilausrivi.tuoteno  not in ('$yhtiorow[kuljetusvakuutus_tuotenumero]','$yhtiorow[laskutuslisa_tuotenumero]')
            and tilausrivi.kpl     != 0
            ORDER BY tilausrivi.otunnus, $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
  $valsures = pupe_query($query);

  $edotunnus = '';
  $edtunnus  = '';

  while ($valsurow = mysql_fetch_assoc($valsures)) {
    if ($valsurow["otunnus"] != $edotunnus and $edotunnus != '') {

      $query = "SELECT summa, valkoodi, nimi, toim_nimi, osoite, toim_osoite, vienti_kurssi, toimitustapa
                FROM lasku
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$edotunnus'";
      $sumsresult = pupe_query($query);
      $sumsrow  = mysql_fetch_assoc($sumsresult);

      if ($sumsrow["valkoodi"] != '' and trim(strtoupper($sumsrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $sumsrow["vienti_kurssi"] != 0) {
        $sumvieku = $sumsrow["vienti_kurssi"];
      }
      else {
        $sumvieku = 1;
      }

      $query = "SELECT sum(round((rivihinta/$sumvieku)*(1+alv/100), 2)) summa
                FROM tilausrivi
                WHERE yhtio  = '$kukarow[yhtio]'
                and tuoteno  not in ('$yhtiorow[kuljetusvakuutus_tuotenumero]','$yhtiorow[laskutuslisa_tuotenumero]')
                and tyyppi  != 'D'
                and otunnus  = '$edotunnus'";
      $sumsresult = pupe_query($query);
      $sumsrow2  = mysql_fetch_assoc($sumsresult);

      $sumsrow["summa"] = sprintf('%.2f', $sumsrow2["summa"]);

      // jos meillä on eri toimitusosoite kun laskutusosoite, kirjotetaan toimitusosoitteen asiakasnimi yhteensäriville kommenttiin...
      if ((strtoupper($sumsrow["nimi"]) != strtoupper($sumsrow["toim_nimi"]) or strtoupper($sumsrow["osoite"]) != strtoupper($sumsrow["toim_osoite"])) and $sumsrow["toim_nimi"] != "") {
        $sumskommentti = "$sumsrow[toim_nimi] $sumsrow[toim_osoite] ".t("yhteensä", $kielirow["kieli"]).": $sumsrow[summa] $sumsrow[valkoodi].";
      }
      else {
        $sumskommentti = t("Tilaus", $kielirow["kieli"])." $edotunnus ".t("yhteensä", $kielirow["kieli"]).": $sumsrow[summa] $sumsrow[valkoodi].";
      }

      if ($useita_toimtapoja == "KYLLA") {
        $sumskommentti .= "\n".t("Toimitustapa", $kielirow["kieli"])." : $sumsrow[toimitustapa]";
      }

      $query = "UPDATE tilausrivi
                SET kommentti = concat(kommentti, '\n', '$sumskommentti')
                WHERE tunnus = '$edtunnus'
                and yhtio    = '$kukarow[yhtio]'";
      $paivres = pupe_query($query);
    }

    $edotunnus = $valsurow["otunnus"];
    $edtunnus  = $valsurow["tunnus"];
  }

  //laitetaan välisumma vikallekin tilaukselle
  if ($edtunnus != "") {

    $query = "SELECT summa, valkoodi, nimi, toim_nimi, osoite, toim_osoite, vienti_kurssi, toimitustapa
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$edotunnus'";
    $sumsresult = pupe_query($query);
    $sumsrow  = mysql_fetch_assoc($sumsresult);

    if ($sumsrow["valkoodi"] != '' and trim(strtoupper($sumsrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $sumsrow["vienti_kurssi"] != 0) {
      $sumvieku = $sumsrow["vienti_kurssi"];
    }
    else {
      $sumvieku = 1;
    }

    $query = "SELECT sum(round((rivihinta/$sumvieku)*(1+alv/100), 2)) summa
              FROM tilausrivi
              WHERE yhtio  = '$kukarow[yhtio]'
              and tuoteno  not in ('$yhtiorow[kuljetusvakuutus_tuotenumero]','$yhtiorow[laskutuslisa_tuotenumero]')
              and tyyppi  != 'D'
              and otunnus  = '$edotunnus'";
    $sumsresult = pupe_query($query);
    $sumsrow2  = mysql_fetch_assoc($sumsresult);

    $sumsrow["summa"] = sprintf('%.2f', $sumsrow2["summa"]);

    // jos meillä on eri toimitusosoite kun laskutusosoite, kirjotetaan toimitusosoitteen asiakasnimi yhteensäriville kommenttiin...
    if ((strtoupper($sumsrow["nimi"]) != strtoupper($sumsrow["toim_nimi"]) or
        strtoupper($sumsrow["osoite"]) != strtoupper($sumsrow["toim_osoite"])) and $sumsrow["toim_nimi"] != "") {
      $sumskommentti = "$sumsrow[toim_nimi] $sumsrow[toim_osoite] ".t("yhteensä", $kielirow["kieli"]).": $sumsrow[summa] $sumsrow[valkoodi].";
    }
    else {
      $sumskommentti = t("Tilaus", $kielirow["kieli"])." $edotunnus ".t("yhteensä", $kielirow["kieli"]).": $sumsrow[summa] $sumsrow[valkoodi].";
    }

    if ($useita_toimtapoja == "KYLLA") {
      $sumskommentti .= "\n".t("Toimitustapa", $kielirow["kieli"])." : $sumsrow[toimitustapa]";
    }

    $query   = "UPDATE tilausrivi SET kommentti = concat(kommentti, '\n', '$sumskommentti') WHERE tunnus='$edtunnus' and yhtio='$kukarow[yhtio]'";
    $paivres = pupe_query($query);
  }
}

// tehdään laskun tiliöinnit.. tarvitaan $lasku array
$lasku = $laskurow;

// haetaan asiakkaan tiedot (esim konserniyhtiö)
$konsrow = $asiakas_apu_row;

require 'teelaskuntiliointi.inc';

//echotaan rudulle jos kyseessä ei ole batch-ajo
if ($tulos_ulos == "" and $silent == "") {
  echo $tulos_ulos_ulasku;
}
