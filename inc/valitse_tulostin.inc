<?php

echo "<form method='post' action='$PHP_SELF?$ulisa'>
  <input type='hidden' name='lopetus' value='$lopetus'>
  <input type='hidden' name='toim' value='$toim'>
  <input type='hidden' name='tila' value='$tila'>
  <input type='hidden' name='raportti' value='$raportti'>
  <input type='hidden' name='top' value='$top'>
  <input type='hidden' name='tee' value='$tee'>
  <input type='hidden' name='otunnus' value='$otunnus'>
  <input type='hidden' name='laskunro' value='$laskunro'>
  <input type='hidden' name='ppa' value='$ppa'>
  <input type='hidden' name='kka' value='$kka'>
  <input type='hidden' name='vva' value='$vva'>
  <input type='hidden' name='ppl' value='$ppl'>
  <input type='hidden' name='kkl' value='$kkl'>
  <input type='hidden' name='vvl' value='$vvl'>
  <input type='hidden' name='try' value='$try'>
  <input type='hidden' name='osasto' value='$osasto'>
  <input type='hidden' name='toiminto' value='$toiminto'>
  <input type='hidden' name='toimittajaid' value='$toimittajaid'>
  <input type='hidden' name='arvomatikka' value='$arvomatikka'>
  <input type='hidden' name='naytainvtuot' value='$naytainvtuot'>
  <input type='hidden' name='tuoteno' value='$tuoteno'>
  <input type='hidden' name='ahyllyalue' value='$ahyllyalue'>
  <input type='hidden' name='ahyllynro'  value='$ahyllynro'>
  <input type='hidden' name='ahyllyvali' value='$ahyllyvali'>
  <input type='hidden' name='ahyllytaso' value='$ahyllytaso'>
  <input type='hidden' name='lhyllyalue' value='$lhyllyalue'>
  <input type='hidden' name='lhyllynro'  value='$lhyllynro'>
  <input type='hidden' name='lhyllyvali' value='$lhyllyvali'>
  <input type='hidden' name='lhyllytaso' value='$lhyllytaso'>
  <input type='hidden' name='naytasaldo' value='$naytasaldo'>
  <input type='hidden' name='piilotaToim_tuoteno' value='$piilotaToim_tuoteno'>
  <input type='hidden' name='toimittaja' value='$toimittaja'>
  <input type='hidden' name='asiakasid' value='$asiakasid'>
  <input type='hidden' name='tilausnumero' value='$tilausnumero'>
  <input type='hidden' name='saldot' value='$saldot'>
  <input type='hidden' name='toimas' value='$toimas'>
  <input type='hidden' name='ei_inventointi' value='$ei_inventointi'>
  <input type='hidden' name='prosmuutos' value='$prosmuutos'>
  <input type='hidden' name='kplmuutos' value='$kplmuutos'>
  <input type='hidden' name='tuotemerkki' value='$tuotemerkki'>
  <input type='hidden' name='rajaus' value='$rajaus'>
  <input type='hidden' name='jarjestys' value='$jarjestys'>
  <input type='hidden' name='debug' value='$debug'>
  <input type='hidden' name='naytanimitys' value='$naytanimitys'>
  <input type='hidden' name='ippa' value='$ippa'>
  <input type='hidden' name='ikka' value='$ikka'>
  <input type='hidden' name='ivva' value='$ivva'>
  <input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]'>
  <input type='hidden' name='sorttauskentan_jarjestys1' value='$sorttauskentan_jarjestys1'>
  <input type='hidden' name='sorttauskentan_jarjestys2' value='$sorttauskentan_jarjestys2'>
  <input type='hidden' name='sorttauskentan_jarjestys3' value='$sorttauskentan_jarjestys3'>
  <input type='hidden' name='sorttauskentan_jarjestys4' value='$sorttauskentan_jarjestys4'>
  <input type='hidden' name='kertakassa' value='$kertakassa'>
  <input type='hidden' name='status' value='{$status}'>
  <input type='hidden' name='varasto' value='{$varasto}'>
  <input type='hidden' name='ei_huomioida_tuotepaikkoja_avainsanoista' value='{$ei_huomioida_tuotepaikkoja_avainsanoista}'>
  <input type='hidden' name='otunnukset_array' value='$otunnukset_array'>
  <input type='hidden' name='varastoonvaikutus' value='$varastoonvaikutus'>
  <input type='hidden' name='varastonarvo' value='$varastonarvo'>
  <input type='hidden' name='kerayseran_numero' value='$kerayseran_numero'>
  <input type='hidden' name='kerayseran_tilaukset' value='$kerayseran_tilaukset'>
  <input type='hidden' name='reservipaikka' value='{$reservipaikka}' />
  <input type='hidden' name='tilaustyyppi' value='{$tilaustyyppi}' />
  <input type='hidden' name='keraysvyohyke' value='{$keraysvyohyke}' />
  <input type='hidden' name='vapaa_teksti' value='{$vapaa_teksti}' />
  <input type='hidden' name='kaikkiyhteensa' value='{$kaikkiyhteensa}' />";

if (!empty($kateismaksu["kateinen"])) {
  echo "<input type='hidden' name='kateismaksu[kateinen]'
               value='{$kateismaksu["kateinen"]}'>";
}

if (!empty($kateismaksu["pankkikortti"])) {
  echo "<input type='hidden' name='kateismaksu[pankkikortti]'
               value='{$kateismaksu["pankkikortti"]}'>";
}

if (!empty($kateismaksu["luottokortti"])) {
  echo "<input type='hidden' name='kateismaksu[luottokortti]'
               value='{$kateismaksu["luottokortti"]}'>";
}

if ($nappikeikalle == 'menossa') {
  echo "<input type='hidden' name='nappikeikalla' value='ollaan'>";
}

if (isset($generoi_sahkoinen_lahete)) {
  echo "<input type='hidden' name='generoi_sahkoinen_lahete' value='{$generoi_sahkoinen_lahete}' />";
}

echo "<table>";

$mista = 'asiakas';
$valittu = "";

if ($toim == "OSTO" or $tulostimet[0] == "Ostotilaus") {

  $chk = ((isset($nimitykset) and $nimitykset != '') or (!isset($nimitykset) and $yhtiorow['ostotilaustyyppi'] == 'A')) ? 'checked' : '';

  echo "<tr><th>", t("Tulosta nimitykset ostotilaukselle"), "</th><td><input type='checkbox' name='nimitykset' {$chk}></td></tr>";

  $chk = empty($asiakkaiden_nimet) ? '' : 'checked';
  echo "<tr><th>", t("Tulosta myös asiakkaiden nimet"), "</th><td><input type='checkbox' name='asiakkaiden_nimet' {$chk}></td></tr>";

  $mista = 'toimi';

  $editsql = "SELECT *
              FROM toimi
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$toimittajaid'";
  $edires = pupe_query($editsql);
  $editoimrow = mysql_fetch_assoc($edires);
}

if ($toim == "LAHETE" and $yhtiorow["lahete_tyyppi_tulostus"] != '' and $otunnus != '') {
  echo "<tr><th>".t("Tulosta")."</th><td><select name='sellahetetyyppi'>";

  $lahetetyyppi = pupesoft_lahetetyyppi($otunnus);

  $vresult = t_avainsana("LAHETETYYPPI");

  while ($row = mysql_fetch_array($vresult)) {
    $sel = "";
    if ($row["selite"] == $lahetetyyppi) $sel = 'selected';

    echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
  }

  echo "</select></td></tr>";

  $mista = 'asiakas';
}

if ($toim == "TILAUSVAHVISTUS"
  and $yhtiorow["tilausvahvistus_tyyppi_tulostus"] != ''
  and $otunnus != '') {

  $query2 = "SELECT tilausvahvistus, liitostunnus
             FROM lasku
             WHERE yhtio = '$kukarow[yhtio]'
             and tunnus  = '$otunnus'";
  $vresult2 = pupe_query($query2);
  $row2 = mysql_fetch_assoc($vresult2);

  if (trim($row2['tilausvahvistus']) == "") {
    $query2 = "SELECT tilausvahvistus
               FROM asiakas
               WHERE yhtio = '{$kukarow['yhtio']}'
               AND tunnus  = '{$row2['liitostunnus']}'";
    $vresult2 = pupe_query($query2);
    $row2 = mysql_fetch_assoc($vresult2);
  }

  if (trim($row2['tilausvahvistus']) == "") {
    $row2['tilausvahvistus'] = $yhtiorow['tilausvahvistustyyppi'];
  }

  $tuoteperheetyhdessa = FALSE;
  $verollisethinnat = FALSE;

  if ($kukarow['extranet'] != "" and $kukarow['hyvaksyja'] != '') {
    $naytatvale = 2; // jos mellä on tilausvahvistuksessa kakkonen, ei haluta nähä aleja
  }
  elseif (strpos($row2['tilausvahvistus'], '12') !== FALSE) {
    $naytatvale = 12; // jos meillä on tilausvahvistuksessa 12, niin näytetään hinta, viivakoodi, ale1, ja erikoisale
    if (strpos($row2['tilausvahvistus'], 'Y') !== FALSE) {
      $tuoteperheetyhdessa = TRUE;
    }
  }
  elseif (strpos($row2['tilausvahvistus'], '11') !== FALSE) {
    $naytatvale = 11; // jos meillä on tilausvahvistuksessa seiska, niin tehdään Näytetään asiakkaan hinta, viivakoodi ja jt-rivit normaaleina. Ei alennuksia
    if (strpos($row2['tilausvahvistus'], 'Y') !== FALSE) {
      $tuoteperheetyhdessa = TRUE;
    }
  }
  elseif (strpos($row2['tilausvahvistus'], '10') !== FALSE) {
    $naytatvale = 10; // jos meillä on tilausvahvistuksessa kymppi, niin tehdään Näytetään asiakkaan hinta, alennus, viivakoodi ja jt-rivit normaaleina
    if (strpos($row2['tilausvahvistus'], 'Y') !== FALSE) {
      $tuoteperheetyhdessa = TRUE;
    }
  }
  elseif (strpos($row2['tilausvahvistus'], '8') !== FALSE) {
    $naytatvale = 8; // jos meillä on tilausvahvistuksessa kasi, niin tehdään EAN tilausvahvistus asiakashinnalla ja ei näytetä alennuksia
  }
  elseif (strpos($row2['tilausvahvistus'], '7') !== FALSE) {
    $naytatvale = 7; // jos meillä on tilausvahvistuksessa seiska, niin tehdään EAN tilausvahvistus
    if (strpos($row2['tilausvahvistus'], 'Y') !== FALSE) {
      $tuoteperheetyhdessa = TRUE;
    }
  }
  elseif (strpos($row2['tilausvahvistus'], '6') !== FALSE) {
    $naytatvale = 6; // jos meillä on tilausvahvistuksessa kutonen, niin tehdään customoitu tilausvahvistus
    if (strpos($row2['tilausvahvistus'], 'Y') !== FALSE) {
      $tuoteperheetyhdessa = TRUE;
    }
  }
  elseif (strpos($row2['tilausvahvistus'], '5') !== FALSE) {
    $naytatvale = 5; // jos mellä on tilausvahvistuksessa vitonen, niin haetaan hinnat toisesta pupesta
    if (strpos($row2['tilausvahvistus'], 'Y') !== FALSE) {
      $tuoteperheetyhdessa = TRUE;
    }
  }
  elseif (strpos($row2['tilausvahvistus'], '4') !== FALSE) {
    $naytatvale = 4; // jos mellä on tilausvahvistuksessa nelonen, ei haluta nähdä alennuksia, näytetään tilausrivin hinta ja rivihinta
    if (strpos($row2['tilausvahvistus'], 'Y') !== FALSE) {
      $tuoteperheetyhdessa = TRUE;
    }
  }
  elseif (strpos($row2['tilausvahvistus'], '3') !== FALSE) {
    $naytatvale = 3; // jos mellä on tilausvahvistuksessa kolmonen, ei haluta nähdä hintoja, pelkästään kpl-määrät
    if (strpos($row2['tilausvahvistus'], 'Y') !== FALSE) {
      $tuoteperheetyhdessa = TRUE;
    }
  }
  elseif (strpos($row2['tilausvahvistus'], '2') !== FALSE) {
    $naytatvale = 2; // jos mellä on tilausvahvistuksessa kakkonen, ei haluta nähä aleja
    if (strpos($row2['tilausvahvistus'], 'Y') !== FALSE) {
      $tuoteperheetyhdessa = TRUE;
    }
  }
  elseif (strpos($row2['tilausvahvistus'], '1') !== FALSE) {
    $naytatvale = 1; // jos mellä on tilausvahvistuksessa ykkönen, halutaan nähä alet
    if (strpos($row2['tilausvahvistus'], 'Y') !== FALSE) {
      $tuoteperheetyhdessa = TRUE;
    }
  }
  else {
    $naytatvale = 1; // halutaan nähä alet
  }

  if (strpos($laskurow['tilausvahvistus'], 'V') !== FALSE) {
    $verollisethinnat = TRUE;
  }

  $tvsel[$naytatvale] = "SELECTED";

  echo "<tr><th>".t("Tyyppi")."</th><td><select name='seltvtyyppi'>";

  foreach (pupesoft_tilausvahvistustyypit() as $tv_tyyppi => $tv_selite) {
    echo "<option value = '$tv_tyyppi' $tvsel[$tv_tyyppi]>".t($tv_selite)."</option>";
  }

  echo "</select></td></tr>";
}

if ($toim == "TILAUSVAHVISTUS") {
  echo "<tr><th>", t("Näytetäänkö rivihinta"), "</th><td><select name='naytetaanko_rivihinta'>";
  echo "<option value=''>", t("Näytetään rivihinta"), "</option>";
  echo "<option value='ei_rivihintaa'>", t("Ei näytetä rivihintaa"), "</option>";
  echo "</select></td></tr>";
}

if ($toim == "TARJOUS") {
  echo "<tr><th>", t("Näytetäänkö verolliset vai verottomat hinnat"), "</th><td><select name='verolliset_verottomat_hinnat'>";
  echo "<option value=''>", t("Haetaan yhtiön parametreistä"), "</option>";
  echo "<option value='verolliset'>", t("Verolliset"), "</option>";
  echo "<option value='verottomat'>", t("Verottomat"), "</option>";
  echo "</select></td></tr>";

  echo "<tr><th>", t("Näytetäänkö rivihinta"), "</th><td><select name='naytetaanko_rivihinta'>";
  echo "<option value=''>", t("Näytetään hinnat"), "</option>";
  echo "<option value='ei_rivihintaa'>", t("Ei näytetä rivihintaa"), "</option>";
  echo "<option value='kokonaan_ilman_hintoja'>", t("Kokonaan ilman hintoja"), "</option>";
  echo "</select></td></tr>";

  echo "<tr><th>" . t("Näytetäänkö yhteissummarivi") .
    "</th><td><select name='naytetaanko_yhteissummarivi'>";
  echo "<option value=''>" . t("Näytetään yhteissummarivi") . "</option>";
  echo "<option value='E'>" . t("Ei näytetä yhteissummariviä") . "</option>";
  echo "</select></td></tr>";

  echo "<tr><th>", t("Näytetäänkö tuotenumerot"), "</th><td><select name='naytetaanko_tuoteno'>";
  echo "<option value=''>", t("Kyllä"), "</option>";
  echo "<option value='ei'>", t("Ei"), "</option>";
  echo "</select></td></tr>";
}

echo "<tr><th>".t("Kpl")."</th><td><input type='text' name='kappaleet' size='3'></td></tr>";

echo "<tr><th>".t("Valitse kieli")."</th>";

///* Haetaan asiakkaan kieli *///
if ($otunnus != '' or $laskunro != '') {

  $querykieli = "SELECT liitostunnus, toim_email
                 FROM lasku
                 WHERE";

  if ($otunnus > 0) {
    $querykieli .= " tunnus='$otunnus' ";
  }
  elseif ($laskunro > 0 and $toim != "OSTO") {
    if ($otunnus > 0) {
      $querykieli .= " and laskunro='$laskunro' ";
    }
    else {
      $querykieli .= " tila='U' and laskunro='$laskunro' ";
    }
  }
  else {
    $querykieli .= " and tunnus='$kukarow[kesken]' ";
  }

  $querykieli .= " AND yhtio ='$kukarow[yhtio]'";
  $resultkieli = pupe_query($querykieli);
  $asrow = mysql_fetch_assoc($resultkieli);

  $toim_email = "";
  if (!empty($asrow['toim_email'])) $toim_email = $asrow['toim_email'];

  $lasku_email = "";
  if ($mista == 'asiakas') $lasku_email = ", lasku_email";

  $querykieli = "SELECT kieli, email $lasku_email
                 FROM $mista
                 WHERE tunnus = '$asrow[liitostunnus]'
                 AND yhtio    = '$kukarow[yhtio]'";
  $resultkieli = pupe_query($querykieli);
  $asrow = mysql_fetch_array($resultkieli);

  if ($asrow["kieli"] != '') {
    $sel[$asrow["kieli"]] = "SELECTED";
  }
  else {
    $sel[$yhtiorow["kieli"]] = "SELECTED";
  }
}
else {
  $sel[$yhtiorow["kieli"]] = "SELECTED";
}

echo "<td><select name='kieli'>";

foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
  echo "<option value='$sanakirja_kieli' $sel[$sanakirja_kieli]>".t($sanakirja_kieli_nimi)."</option>";
}

echo "</select></td></tr>";

foreach ($tulostimet as $tulostin) {

  if ($tulostin == 'Purkulista' and $yhtiorow['suuntalavat'] == 'S' and $toim != 'REKLAMAATIO') {

    $valitut_suuntalavat = array();
    $valitut_suuntalisa  = "";

    if (isset($valitut_lavat) and $valitut_lavat != "") {
      $valitut_suuntalavat = explode(",", $valitut_lavat);
      $valitut_suuntalisa = " AND suuntalavat.tunnus in (".mysql_real_escape_string($valitut_lavat).")";
    }

    $query = "SELECT DISTINCT suuntalavat.tunnus, suuntalavat.sscc
              FROM suuntalavat
              JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus)
              WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
              AND suuntalavat.tila    = 'S'
              AND (suuntalavat.kaytettavyys = 'L' OR (suuntalavat.kaytettavyys = 'Y' AND suuntalavat_saapuminen.saapuminen = '{$otunnus}'))
              {$valitut_suuntalisa}";
    $suuntalavat_res = pupe_query($query);

    if (mysql_num_rows($suuntalavat_res) == 0) continue;
  }

  if ($toim == "PROFORMA" or $toim == "VIENTILASKU" or $toim == "LASKU") {
    echo "<tr><th>", t("Tullinimike ja alkuperämaa")."</th>";
    echo "<td colspan='3'>";
    echo "<select name='tullinimike_ja_alkuperamaa'>";
    echo "<option value=''>" . t("Ei tulosteta") . "</option>";
    echo "<option value='A'>" . t("Tulosta tullinimike ja alkuperämaa") . "</option>";
    echo "<option value='B'>" . t("Tulosta vain tullinimike") . "</option>";
    echo "<option value='C'>" . t("Tulosta tullinimike ja alkuperämaa (Kotimaan tuotantoa)") . "</option>";
    echo "</select>";
    echo "</td></tr>";
  }

  if ($toim == "TARJOUS") {
    echo "<tr>";
    echo "<th>" . t("Liitä tuotetiedot") . "</th>";
    echo "<td>";
    echo "<select name='liita_tuotetiedot'>";
    echo "<option value=''>" . t("Ei") . "</option>";
    echo "<option value='Y'>" . t("Kyllä") . "</option>";
    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }

  echo "<tr><th>".t("Valitse")." '".t("$tulostin")."' ".t("tulostuspaikka")."</th>";
  echo "<td><select name='komento[$tulostin]'>";

  if (empty($ei_inventointi)) {
    echo "<option value=''>".t("Ei kirjoitinta")."</option>";

    if (strpos($_SERVER['SCRIPT_NAME'], "tulostakopio.php") === FALSE and in_array($tulostin, array('Työmääräys', 'Huoltosaate', 'Reklamaatio', 'Takuu', 'Tarjous', 'Inventointi'))) {
      echo "<option value='-88'>".t('PDF Ruudulle')."</option>";
    }
    else {
      echo "<option value='PDF_RUUDULLE'>".t('PDF Ruudulle')."</option>";
    }
  }

  if ($tulostimet[0] == "Inventointi") {
    echo "<option value='excel'>".t('Excel-tiedosto')."</option>";
  }

  if (!empty($editoimrow['email']) and ($toim == "OSTO" or $tulostimet[0] == 'Ostotilaus')) {
    echo "<option value='toimittajaemail{$editoimrow['email']}'>".t("Toimittajan sähköpostiin").": {$editoimrow['email']}</option>";
  }

  if (!empty($yhtiorow['ostotilaus_email']) and ($toim == "OSTO" or $tulostimet[0] == 'Ostotilaus')) {
    echo "<option value='toimittajaemail{$yhtiorow['ostotilaus_email']}'>".t("Ostotilaus sähköpostiin").": {$yhtiorow['ostotilaus_email']}</option>";
  }

  if (($toim == 'TARJOUS' or $toim == 'MYYNTISOPIMUS' or $toim == 'LAHETE' or $toim == 'TILAUSVAHVISTUS' or $toim == 'LASKU') and $asrow['email'] != '') {
    echo "<option value='asiakasemail$asrow[email]'>".t("Asiakkaan sähköpostiin").": $asrow[email]</option>";

    if (!empty($toim_email)) {
      echo "<option value='asiakasemail$toim_email'>".t("Asiakkaan sähköpostiin").": $toim_email</option>";
    }
  }

  if ($toim == 'LASKU' and trim($asrow['lasku_email']) != '') {
    echo "<option value='asiakasemail$asrow[lasku_email]'>".t("Asiakkaan laskutussähköpostiin").": $asrow[lasku_email]</option>";
  }

  if ($toim == 'TILAUSVAHVISTUS') {
    echo "<option value='excel_lahete_geodis_wilson'>".t("Excel-tilausvahvistus")."</option>";
    echo "<option value='excel_lahete_hinnoilla'>".t("Excel-tilausvahvistus hintatiedoilla")."</option>";
  }

  if ($toim == 'TARJOUS') {
    echo "<option value='excel_lahete_hinnoilla'>".t("Excel-tarjous hintatiedoilla")."</option>";
  }

  if (isset($editoimrow) and $editoimrow["edi_kuvaus"] != '' and
    (preg_match("/(excel|wabco|xml|sendorder)/", $editoimrow['edi_kuvaus']) or
      ($editoimrow["edi_palvelin"] != '' and $editoimrow["edi_kayttaja"] != '' and $editoimrow["edi_salasana"] != '' and $editoimrow["edi_polku"] != '') or
      ($editoimrow['edi_palvelin'] == "localhost" and $editoimrow["edi_polku"] != ''))) {
    $rajaus = "";
    $valittu = "edi";
  }
  else {
    $rajaus = " and komento != 'edi' ";
  }

  if (empty($ei_inventointi)) {
    $querykieli = "SELECT *
                   from kirjoittimet
                   where yhtio='$kukarow[yhtio]'
                   $rajaus
                   ORDER BY kirjoitin";
    $kires = pupe_query($querykieli);

    $seepra = false;

    while ($kirow=mysql_fetch_array($kires)) {

      $sel = "";
      if ($kirow["tunnus"] == $kukarow["kirjoitin"] and $valittu != "edi") {
        $sel = "SELECTED";
      }
      if ($kirow["komento"] == "edi" and $valittu == "edi") {
        $sel = "SELECTED";
      }

      if ($kirow['merkisto'] != 0) $seepra = true;

      echo "<option value='$kirow[komento]'$sel>$kirow[kirjoitin]</option>";
    }
  }

  echo "</select></td>";
  echo "</tr>";

  if ($tulostin == 'Työmääräys' and $kukarow['hinnat'] == 0) {

    echo "<tr><th>".t("Työmääräystyyppi")."</th><td>";
    echo "<select name='tyomtyyppi'>";

    $sel = array();
    $sel[$yhtiorow["tyomaaraystyyppi"]] = "selected";

    echo "<option value = 'A'>".t("Tuotetiedot, hinnat ja alennukset")."</option>";
    echo "<option value = 'Q' $sel[Q]>".t("Tuotetiedot, hinnat ja alennukset (Verolliset hinnat)")."</option>";
    echo "<option value = 'O' $sel[O]>".t("Tuotetiedot")."</option>";
    echo "<option value = 'P' $sel[P]>".t("Tuotetiedot ja varastopaikka")."</option>";

    // Katsotaan onko Zebra-tulostinta käytössä.
    // Oletetaan että silloin merkistö ei ole 0

    if ($seepra) echo "<option value = 'Z'>", t("Työmääräystarra Zebra"), "</option>";

    echo "</select>";
    echo "</td></tr>";
  }

  if ($tulostin == 'Purkulista') {

    $colspanni = 3;

    if ($yhtiorow['suuntalavat'] == 'S' and $toim != 'REKLAMAATIO') {
      echo "<tr><th>", t("Valitse suuntalavat"), "</th><td>";

      while ($suuntalavat_row = mysql_fetch_assoc($suuntalavat_res)) {
        $ched = (in_array($suuntalavat_row["tunnus"], $valitut_suuntalavat)) ? "CHECKED" : "";

        echo "<input type='checkbox' name='suuntalavat[]' value='{$suuntalavat_row['tunnus']}' $ched> {$suuntalavat_row['sscc']}<br />";
      }

      echo "</td></tr>";

      $colspanni = 4;
    }

    echo "<tr><th>".t("Valitse")." ABC- ".t("luokkarajausperuste")."</th><td>";
    echo "<select name='abcvalintamenu'>";

    echo "<option value = ''>".t("Ei rajausta")."</option>";
    echo "<option value = 'TM'>".t("Myynti")."</option>";
    echo "<option value = 'TK'>".t("Myyntikate")."</option>";
    echo "<option value = 'TR'>".t("Myyntirivi")."</option>";
    echo "<option value = 'TP'>".t("Myyntikappaleet")."</option>";


    echo "</select>";
    echo "</td></tr>";

    echo "<tr><th rowspan='$colspanni'>".t("Järjestele purkulista")."</th>";

    if ($yhtiorow['suuntalavat'] == 'S' and $toim != 'REKLAMAATIO') {
      echo "<td><input type='radio' name='sorttaus' value='indeksi' checked='checked' />", t("hyllypaikan indeksin mukaan"), "</td></tr><tr>";
      $checked = '';
    }
    else {
      $checked = " checked='checked'";
    }

    echo "<td><input type='radio' name='sorttaus' value='tuote'> ".t("tuotenumeron mukaan")."</td></tr>";

    echo "<tr><td><input type='radio' name='sorttaus' value='toim_tuote' {$checked}> ".t("toimittajan tuotenumeron mukaan")."</td></tr>";
    echo "<tr><td><input type='radio' name='sorttaus' value='paikka'> ".t("varastopaikan mukaan")."</td></tr>";

    if ($yhtiorow['viivakoodi_purkulistaan'] == 'K') {
      $span = 4;
    }
    else {
      $span = 3;
    }

    echo "<tr><th rowspan='$span'>".t("Tulostettavat rivit")."</th><td><input type='radio' name='mitkarivit' value='kaikki'> ".t("kaikki rivit")."</td></tr>";
    echo "<tr><td><input type='radio' name='mitkarivit' value='viematta' CHECKED> ".t("saldoille viemättä olevat rivit")."</td></tr>";
    echo "<tr><td><input type='radio' name='mitkarivit' value='viedyt'> ".t("saldoille viedyt rivit")."</td></tr>";
    if ($yhtiorow['viivakoodi_purkulistaan'] == 'K') {
      echo "<tr><td><input type='radio' name='mitkarivit' value='koodittomat'> ".t("Viivakoodittomat rivit")."</td></tr>";
    }

    echo "<tr>";
    echo "<th>".t("Tee excel tullinimikkeistä")."</th>";

    echo "<td>";
    echo "<input type='checkbox' name='tee_excel' value='1' />";
    echo "</td>";
    echo "</tr>";
  }
}

if (isset($laskurow) and $laskurow['kirjoitin'] == "edi") {
  echo "<tr><td colspan='2' class='back'><font class='error'>".t("HUOM: EDI-tiedosto on jo kerran lähetetty toimittajalle")."!</font></td><tr>";
}

echo "</table><br><input type='submit' name='tulosta' value='".t("Tulosta")."'></form>";

if (strpos($_SERVER['SCRIPT_NAME'], "keikka.php") === FALSE) {
  //lopetetaan tähän, muuten esim rivit.inc nollaa kukarow[kesken] muuttujan ja moni muukin asia toimisi kovin heikosti...
  require "inc/footer.inc";
  exit;
}
