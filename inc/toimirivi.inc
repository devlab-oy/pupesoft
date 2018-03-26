<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if ($i == 0) { //yhtiötä ei näytetä
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "tilino") {
  $ulos = "<td>".livesearch_kentta("mainform", "TILIHAKU", $nimi, 270, $trow[$i], "EISUBMIT")."</td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "tilinumero") { //suoraveloitus suoritetaan automaattisesti
  $maxsize++; //Tilaa mahdolliselle viivalle
}

if (mysql_field_name($result, $i) == "oletus_suoravel_pankki") { //suoraveloitus suoritetaan automaattisesti

  $query = "SELECT nimi, tilino, tunnus
            FROM yriti
            WHERE yhtio  = '$kukarow[yhtio]'
            and kaytossa = ''";
  $vresult = pupe_query($query);

  $sel="";
  if ((int)$trow[$i] == 0) {
    $sel="selected";
  }
  $ulos = "<td><select name='$nimi'><option value = '0' $sel>".t("Ei suoriteta automaattisesti");

  while ($vrow=mysql_fetch_array($vresult)) {

    $sel="";
    if ($trow[$i] == $vrow['tunnus']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[nimi] ($vrow[tilino])";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if  ((mysql_field_name($result, $i) == "oletus_suoraveloitus") or
  (mysql_field_name($result, $i) == "oletus_hyvaksynnanmuutos") or
  (mysql_field_name($result, $i) == "konserniyhtio")) {

  $ulos  = "<td><select name='$nimi'>";

  $chk1 = '';
  $chk2 = '';

  if (trim($trow[$i]) == "") {
    $chk1 = 'selected';
  }
  else {
    $chk2 = 'selected';
  }

  $ulos .= "<option value=''  $chk1>".t("Ei")."</option>";
  $ulos .= "<option value='o' $chk2>".t("Kyllä")."</option>";

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "oletus_valkoodi") { //valuutta
  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY jarjestys";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";

    if ($trow[$i] == $vrow['nimi']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[nimi]' $sel>$vrow[nimi]";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "oletus_hyvak1" and $alias_set == "KAYTTAJA") {
  $ulos = "<td><input type='hidden' name='$nimi' value=''>".t("Matkustaja itse")."</td>";
  $jatko = 0;
}

if ((mysql_field_name($result, $i) == "oletus_hyvak1" and $alias_set != "KAYTTAJA") or
  (mysql_field_name($result, $i) == "oletus_hyvak2") or
  (mysql_field_name($result, $i) == "oletus_hyvak3") or
  (mysql_field_name($result, $i) == "oletus_hyvak4") or
  (mysql_field_name($result, $i) == "oletus_hyvak5")) { //hyväksyjät

  $query = "SELECT kuka, nimi
            FROM kuka
            WHERE yhtio   = '$kukarow[yhtio]'
            and hyvaksyja = 'o'
            ORDER BY nimi";
  $vresult = pupe_query($query);

  $sel="";
  if (trim($trow[$i]) == "") {
    $sel = "selected";
  }
  $ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei kukaan");

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['kuka']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[kuka]' $sel>$vrow[nimi]";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "nimi" and $alias_set == "KAYTTAJA") {

  $query = "SELECT kuka, nimi
            FROM kuka
            WHERE yhtio   = '$kukarow[yhtio]'
            AND extranet  = ''
            AND nimi     != ''
            ORDER BY nimi";
  $vresult = pupe_query($query);

  $sel="";
  if (trim($trow[$i]) == "") {
    $sel = "selected";
  }
  $ulos = "<td><select name='$nimi'>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['kuka']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[kuka]' $sel>$vrow[nimi]";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kustannuspaikka") { //kustannuspaikat
  $query = "SELECT tunnus, nimi, koodi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and kaytossa != 'E'
            and tyyppi    = 'K'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($query);

  $sel="";
  if (trim($trow[$i]) == "") {
    $sel = "selected";
  }
  $ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei kustannuspaikkaa")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";

    if ($trow[$i] == $vrow['tunnus']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kohde") { //kohteet
  $query = "SELECT tunnus, nimi, koodi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and tyyppi    = 'O'
            and kaytossa != 'E'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($query);

  $sel="";
  if (trim($trow[$i]) == "") {
    $sel = "selected";
  }
  $ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei kohdetta")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['tunnus']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "projekti") { //projektit

  $query = "SELECT tunnus, nimi, koodi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and tyyppi    = 'P'
            and kaytossa != 'E'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($query);

  $sel="";
  if (trim($trow[$i]) == "") {
    $sel = "selected";
  }
  $ulos = "<td><select name='$nimi'><option value='' $sel>".t("Ei projektia")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['tunnus']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;

}

if (mysql_field_name($result, $i) == "oletus_vienti") { //oletusvienti

  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos = "<td>
    <select name='$nimi'>
      <option value='A' {$sel["A"]}>".t("Kotimaa")."</option>
      <option value='B' {$sel["B"]}>".t("Kotimaa huolinta/rahti")."</option>
      <option value='C' {$sel["C"]}>".t("Kotimaa vaihto-omaisuus")."</option>
      <option value='J' {$sel["J"]}>".t("Kotimaa Raaka-aine")."</option>
      <option value='D' {$sel["D"]}>".t("EU")."</option>
      <option value='E' {$sel["E"]}>".t("EU huolinta/rahti")."</option>
      <option value='F' {$sel["F"]}>".t("EU vaihto-omaisuus")."</option>
      <option value='K' {$sel["K"]}>".t("EU Raaka-aine")."</option>
      <option value='G' {$sel["G"]}>".t("ei-EU")."</option>
      <option value='H' {$sel["H"]}>".t("ei-EU huolinta/rahti")."</option>
      <option value='I' {$sel["I"]}>".t("ei-EU vaihto-omaisuus")."</option>
      <option value='L' {$sel["L"]}>".t("ei-EU Raaka-aine")."</option>
    </select>
    </td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "toimittajaryhma") {
  $vresult = t_avainsana("TOIMITTAJARYHMA");

  $ulos = "<td><select name='{$nimi}'>";

  $ulos .= "<option value = ''>".t("Ei ryhmää")."</option>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel = $trow[$i] == $vrow['selite'] ? 'selected' : '';
    $teksti = $vrow['selitetark'] != '' ? "{$vrow['selite']} {$vrow['selitetark']}" : $vrow['selite'];
    $ulos .= "<option value = '{$vrow['selite']}' {$sel}>{$teksti}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kauppatapahtuman_luonne") {
  $vresult = t_avainsana("KT");

  $ulos = "<td><select name='$nimi'>";

  $sel="";
  if ((int)$trow[$i] == 0) {
    $sel = "selected";
  }
  $ulos .= "<option value = '' $sel>".t("Ei valintaa")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if (strtoupper($trow[$i]) == strtoupper($vrow["selite"])) {
      $sel = "selected";
    }
    $ulos .= "<option value = '".strtoupper($vrow["selite"])."' $sel>$vrow[selitetark]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == 'kuljetus') {

  $ulos = "<td>";

  $toimtapares = t_avainsana("TOIMITUSTAPA_OS");

  if (mysql_num_rows($toimtapares) > 0) {

    $ulos .= "<select name = '{$nimi}'>";
    $ulos .= "<option value=''>".t("Ei oletustoimitustapaa")."</option>";

    while ($toimtaparow  = mysql_fetch_array($toimtapares)) {
      $sel = $trow[$i] == $toimtaparow['selite'] ? "selected" : "";
      $ulos .= "<option value='{$toimtaparow['selite']}' {$sel}>".trim("{$toimtaparow['selite']} {$toimtaparow['selitetark']}")."</option>";
    }

    $ulos .= "</select>";
  }
  else {
    $ulos .= "<input type='text' name='{$nimi}' value='{$trow[$i]}' />";
  }

  $ulos .= "</td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kuljetusmuoto") {
  $vresult = t_avainsana("KM");

  $ulos = "<td><select name='$nimi'>";

  $sel="";
  if ((int)$trow[$i] == 0) {
    $sel = "selected";
  }
  $ulos .= "<option value = '' $sel>".t("Ei valintaa")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if (strtoupper($trow[$i]) == strtoupper($vrow["selite"])) {
      $sel = "selected";
    }
    $ulos .= "<option value = '".strtoupper($vrow["selite"])."' $sel>$vrow[selitetark]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "maa" or mysql_field_name($result, $i) == "maa_lahetys" or mysql_field_name($result, $i) == "ultilno_maa") {
  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi'>";

  $ulos .= "<option value = '' ></option>";

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel="";
    if (strtoupper($trow[$i]) == strtoupper($vrow[0])) {
      $sel = "selected";
    }
    elseif (mysql_field_name($result, $i) != "ultilno_maa" and $trow[$i] == "" and $trow["maa"] != "" and strtoupper($vrow[0]) == $trow["maa"]) {
      $sel = "selected";
    }
    elseif (mysql_field_name($result, $i) != "ultilno_maa" and $trow[$i] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["maa"])) {
      $sel = "selected";
    }

    $ulos .= "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kieli") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
    $sel = "";
    if ($trow[$i] == $sanakirja_kieli) {
      $sel = "selected";
    }
    elseif ($trow[$i] == "" and $sanakirja_kieli == $yhtiorow["kieli"]) {
      $sel = "selected";
    }

    $ulos .= "<option value='$sanakirja_kieli' $sel>".t($sanakirja_kieli_nimi)."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "fakta") {
  $ulos = "<td><textarea cols='55' rows='7' name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "ohjeitapankille") {
  $ulos = "<td><textarea cols='35' rows='7' name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "laskun_erittely") {

  $selo = $sels = "";
  if ($trow[$i] == 'S') $sels = "selected";
  else $selo = "selected";

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value='' $selo>".t("Ostolaskua ei voi eritellä")."</option>";
  $ulos .= "<option value='S' $sels>".t("Ostolaskun voi eritellä")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kulujen_laskeminen_hintoihin") {

  $sel = $trow[$i] == 'K' ? "selected" : "";

  $ulos  = "<td><select name='{$nimi}'>";
  $ulos .= "<option value=''>".t("Hintoihin ei lasketa kuluprosenttia")."</option>";
  $ulos .= "<option value='K' {$sel}>".t("Hintoihin lasketaan kuluprosentti")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "tyyppi") {

  $sel = array();
  $sel[$trow[$i]] = "selected";

  if ($laji == "K") {
    $ulos  = "<td><select name='$nimi'>";
    $ulos .= "<option value='K'  $sel[K]>".t("Matkalaskukäyttäjä")."</option>";
    $ulos .= "<option value='P'  $sel[P]>".t("Poistettu / Ei käytössä")."</option>";
    $ulos .= "</select></td>";
  }
  else {
    $ulos  = "<td><select name='$nimi'>";
    $ulos .= "<option value=''>".t("Normaali toimittaja")."</option>";
    $ulos .= "<option value='L'  $sel[L]>".t("Kaikkien tuotteiden toimittaja")."</option>";
    $ulos .= "<option value='K'  $sel[K]>".t("Matkalaskukäyttäjä")."</option>";
    $ulos .= "<option value='P'  $sel[P]>".t("Poistettu / Ei käytössä")."</option>";
    $ulos .= "<option value='PP' $sel[PP]>".t("Poistettu / Ei käytössä (Poistetaan myös kaikki Tuotteen toimittajatiedot)")."</option>";
    $ulos .= "</select></td>";
  }

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "tilino_alv") {

  // tutkitaan ollaanko jossain toimipaikassa alv-rekisteröity
  $query = "SELECT *
            FROM yhtion_toimipaikat
            WHERE yhtio     = '$kukarow[yhtio]'
            and maa        != ''
            and vat_numero != ''
            and toim_alv   != ''";
  $alhire = pupe_query($query);

  // ollaan alv-rekisteröity
  if (mysql_num_rows($alhire) >= 1) {

    $ulos  = "<td><select name='$nimi'>";
    $ulos .= "<option value='$yhtiorow[alv]'>$yhtiorow[alv] - $yhtiorow[nimi], $yhtiorow[kotipaikka], $yhtiorow[maa]</option>";

    while ($vrow = mysql_fetch_array($alhire)) {
      $sel = "";
      if ($trow[$i] == $vrow['toim_alv']) {
        $sel = "selected";
      }
      $ulos .= "<option value='$vrow[toim_alv]' $sel>$vrow[toim_alv] - $vrow[nimi], $vrow[kotipaikka], $vrow[maa]</option>";
    }

    $ulos .= "</select>";
    $ulos .= "</td>";
    $jatko = 0;
  }
  else {
    $ulos  = "<td>";
    $ulos .= "$yhtiorow[alv]";
    $ulos .= "<input type='hidden' name='$nimi' value=''>";
    $ulos .= "</td>";
    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "verovelvollinen") {

  $sel = "";
  if ($trow[$i] != "") $sel = "selected";

  $query = "SELECT nimi FROM maat WHERE koodi='{$yhtiorow["maa"]}' limit 1";
  $maare = pupe_query($query);
  $maaro = mysql_fetch_assoc($maare);


  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value=''>".t("Toimittaja ei ole verovelvollinen maassa").": $maaro[nimi]</option>";
  $ulos .= "<option value='$yhtiorow[maa]' $sel>".t("Toimittaja on verovelvollinen maassa").": $maaro[nimi]</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "maksukielto") {

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value=''>".t("Toimittaja ei ole maksukiellossa")."</option>";
  $ulos .= "<option value='K'  $sel[K] >".t("Toimittaja on maksukiellossa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "hintojenpaivityssykli") {
  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value='0'  $sel[0] >".t("Ei muistuteta tuotepäälliköitä hintapäivityksistä")."</option>";
  $ulos .= "<option value='1'  $sel[1] >".t("Tuotepäälliköitä muistutetaan hintapäivityksistä kerran vuodessa")."</option>";
  $ulos .= "<option value='2'  $sel[2] >".t("Tuotepäälliköitä muistutetaan hintapäivityksistä 2 kertaa vuodessa")."</option>";
  $ulos .= "<option value='3'  $sel[3] >".t("Tuotepäälliköitä muistutetaan hintapäivityksistä 3 kertaa vuodessa")."</option>";
  $ulos .= "<option value='4'  $sel[4] >".t("Tuotepäälliköitä muistutetaan hintapäivityksistä 4 kertaa vuodessa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

// ei näytetä jos yhtiö ei suoratoimita mitään

if (mysql_field_name($result, $i) == "suoratoimitus") {
  if ($yhtiorow["tee_osto_myyntitilaukselta"] != '') {

    $sel = array_fill_keys(array('U', 'X', '-'), '');
    $sel[$trow[$i]] = " SELECTED";

    $ulos  = "<td><select name='$nimi'>";

    $st_opt   = (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('X', 'Y', 'V', 'W', 'I', 'J', 'Z', 'A', 'C'))) ? '1' : '0';  // eka merkki = varastoon
    $st_opt  .= (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'Y', 'W', 'J', 'Q', 'B', 'C')))      ? '1' : '0';       // toka merkki = asiakkaalle

    switch ($st_opt) {
    case '11': // sallittu molemmille
      $ulos .= "<option value='' >"             .t("Suoratoimitukset sallittu asiakkaalle ja varastoon")."</option>";
      $ulos .= "<option value='U' {$sel['U']} >".t("Suoratoimitukset sallittu asiakkaalle")."</option>";
      $ulos .= "<option value='X' {$sel['X']} >".t("Suoratoimitukset sallittu varastoon")."</option>";
      break;
    case '10': // sallittu vain varastoon
      $ulos .= "<option value='' >"             .t("Suoratoimitukset sallittu varastoon")."</option>";
      break;
    case '01': //sallittu vain asiakkaalle
      $ulos .= "<option value='' >"             .t("Suoratoimitukset sallittu asiakkaalle")."</option>";
      break;
    }
    $ulos .= "<option value='-' {$sel['-']} >".t("Suoratoimitukset kielletty")."</option>";
    $ulos .= "</select></td>";

    $jatko = 0;
  }
  else {
    $tyyppi = 0;
  }
}

if (mysql_field_name($result, $i) == "edi_kuvaus") {

  $path_parts = pathinfo($trow[$i]);

  $sel = array($path_parts['filename'] => 'selected') + array('editilaus_out_teccom.inc' => '', 'editilaus_out_wabco.inc' => '', 'editilaus_out_excel.inc' => '', 'editilaus_out_elma.inc' => '', 'editilaus_out_xml.inc' => '',  'editilaus_out_xml_stap.inc' => '','editilaus_out_futur.inc' => '');

  $ulos  = "<td><select name='{$nimi}'>";
  $ulos .= "<option value=''>".t("Ei käytössä")."</option>";
  $ulos .= "<option value='editilaus_out_teccom.inc' {$sel['editilaus_out_teccom']}>".t("TecCom sähköinen tilaus")."</option>";
  $ulos .= "<option value='editilaus_out_wabco.inc' {$sel['editilaus_out_wabco']}>".t("Wabco sähköinen tilaus")."</option>";
  $ulos .= "<option value='editilaus_out_excel.inc' {$sel['editilaus_out_excel']}>".t("Excel-ostotilaus")."</option>";
  $ulos .= "<option value='editilaus_out_elma.inc' {$sel['editilaus_out_elma']}>".t("Elma-editilaus")."</option>";
  $ulos .= "<option value='editilaus_out_xml.inc' {$sel['editilaus_out_xml']}>".t("Xml-ostotilaus")."</option>";
  $ulos .= "<option value='editilaus_out_xml_stap.inc' {$sel['editilaus_out_xml_stap']}>".t("Xml-ostotilaus Staples")."</option>";
  $ulos .= "<option value='editilaus_out_futur.inc' {$sel['editilaus_out_futur']}>".t("Futur-ostotilaus")."</option>";

  if (@file_exists("inc/sahkoinen_tilausliitanta.inc")) {

    $hae = "option_edi_kuvaus";

    require "inc/sahkoinen_tilausliitanta.inc";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "asn_sanomat") {

  $sel[$trow[$i]] = "selected";

  $ulos  = "<td><select name='$nimi'>";
  $ulos .= "<option value=''>".t("ASN-sanomista ja laskuista ei luoda saapumisia")."</option>";
  $ulos .= "<option value='K' $sel[K]>".t("ASN-sanomista ja TecCom-laskuista luodaan saapumisia")."</option>";
  $ulos .= "<option value='L' $sel[L]>".t("TecCom-laskuista luodaan saapumisia")."</option>";
  $ulos .= "<option value='M' $sel[M]>".t("Maventa-laskuista luodaan saapumisia")."</option>";
  $ulos .= "<option value='F' $sel[F]>".t("Finvoice-laskuista luodaan saapumisia")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "tehdas_saldo_tarkistus") {

  $sel = $trow[$i] == 'K' ? " selected" : "";

  $ulos  = "<td><select name='{$nimi}'>";
  $ulos .= "<option value=''>".t("Ei tarkisteta tehdassaldoja suoratoimitusta tehdessä myyntitilaukselta")."</option>";
  $ulos .= "<option value='K' {$sel}>".t("Tarkistetaan tehdassaldot suoratoimitusta tehdessä myyntitilaukselta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;

}

if (mysql_field_name($result, $i) == "pikaperustus") {

  $sel = $trow[$i] == 'X' ? " selected" : "";

  $ulos  = "<td><select name='{$nimi}'>";
  $ulos .= "<option value=''>".t("Toimittaja ei ole valittavissa kun perustetaan uutta tuotetta")."</option>";
  $ulos .= "<option value='X'{$sel}>".t("Toimittaja on valittavissa kun perustetaan uutta tuotetta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "sahkoinen_automaattituloutus") {

  $sel = array($trow[$i] => 'selected') + array('E' => '', 'K' => '', 'J' => '');

  $ulos  = "<td><select name='{$nimi}'>";
  $ulos .= "<option value=''>".t("Käytetään yhtiön toimipaikan oletusta")."</option>";
  $ulos .= "<option value='E'{$sel['E']}>".t("Sähköisen laskun sisäänluvun yhteydessä ei tulouteta tuotteita varastoon")."</option>";
  $ulos .= "<option value='K'{$sel["K"]}>".t("Tuotteet tuloutetaan varastoon automaattisesti sähköisen laskun sisäänluvun yhteydessä")."</option>";
  $ulos .= "<option value='J'{$sel["J"]}>".t("Tuotteet tuloutetaan varastoon ja jälkilasketaan automaattisesti sähköisen laskun sisäänluvun yhteydessä")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "ostotilauksen_kasittely") {

  $myynnista_osto_avainsanat = t_avainsana("MYYNNISTA_OSTO");

  if (mysql_num_rows($myynnista_osto_avainsanat) > 0) {

    $ulos  = "<td><select name='{$nimi}'>";
    $ulos .= "<option value=''>".t("Yhtiön / toimipaikan oletus")."</option>";

    while ($myynnista_osto_avainsanat_row = mysql_fetch_assoc($myynnista_osto_avainsanat)) {

      $sel = $myynnista_osto_avainsanat_row['selite'] == $trow[$i] ? " selected" : "";

      $ulos .= "<option value='{$myynnista_osto_avainsanat_row['selite']}'{$sel}>{$myynnista_osto_avainsanat_row['selitetark']}</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }
  else {
    $tyyppi = 0;
  }
}
