<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "tuoteno") {
  $tuoteno = $trow[$i]; // otetaan tää talteen

  if ($uusi == "") {
    $tyyppi = 3;

    // Tuotteen saa poistaa mikäli sillä ei ole yhtään tapahtumaa
    $query = "SELECT yhtio
              FROM tapahtuma USE INDEX (yhtio_laji_tuoteno)
              WHERE yhtio = '$kukarow[yhtio]'
              and laji    in ('epäkurantti','inventointi','kulutus','laskutus','siirto','tulo','valmistus')
              and tuoteno = '$tuoteno'
              LIMIT 1";
    $sdtres = pupe_query($query);

    if (mysql_num_rows($sdtres) == 0) {
      // Ei saa olla myöskään yhtään tilausriviä (Puuterivit sallitaan)
      $query = "SELECT yhtio
                FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laadittu)
                WHERE yhtio   = '$kukarow[yhtio]'
                and tyyppi    in ('0','B','E','F','G','L','M','N','O','T','V','W','Z')
                and tuoteno   = '$tuoteno'
                and laadittu  > '0000-00-00 00:00:00'
                and var      != 'P'
                LIMIT 1";
      $sdtres = pupe_query($query);

      if (mysql_num_rows($sdtres) == 0) {
        $poistolukko = "";
      }
    }
  }
  else {

    // Jos automaattinen tuotenumeron ehdotus on päällä...
    if ($trow[$i] == '' and ($yhtiorow['seuraava_tuotenumero'] == 'K' or ($yhtiorow['seuraava_tuotenumero'] == 'O' and isset($tee_myos_tuotteen_toimittaja_liitos)))) {
      $virheviesti = '';
      $autom_wherelisa = '';
      $autom_selectlisa = 'MAX(CAST(tuote.tuoteno AS UNSIGNED))';

      if (!empty($kaatokoodi_tuotteen_tuotenumeron_alku)) {
        $autom_wherelisa = "AND tuote.tuoteno LIKE '{$kaatokoodi_tuotteen_tuotenumeron_alku}%'";
      }

      $query = "SELECT IF({$autom_selectlisa} = 18446744073709551615, 'error', {$autom_selectlisa} + 1) AS seuraava_tuoteno
                FROM tuote
                WHERE tuote.yhtio = '{$kukarow['yhtio']}'
                AND tuote.tuoteno REGEXP '[^0-9]' = 0
                {$autom_wherelisa}";
      $autom_tuoteno_res = pupe_query($query);
      $autom_tuoteno_row = mysql_fetch_assoc($autom_tuoteno_res);

      if ($autom_tuoteno_row['seuraava_tuoteno'] == 'error') {
        $virheviesti  = "<br /><font class='error'>";
        $virheviesti .= t("Seuraava tuotenumero on liian suuri (isompi kuin %s)", "", "18446744073709551615");
        $virheviesti .= "</font>";
      }
      else {
        $trow[$i] = $autom_tuoteno_row['seuraava_tuoteno'];
      }
    }

    $ulos = "<td><input type = 'hidden' name = 'uusi' value = '$uusi'><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'>{$virheviesti}</td>";
    $jatko=0;

    // Jos on uusi tuote niin kaikki lukot on auki
    $poistolukko = "";
  }
}

if (mysql_field_name($result, $i) == "tilino" or
  mysql_field_name($result, $i) == "tilino_eu" or
  mysql_field_name($result, $i) == "tilino_ei_eu" or
  mysql_field_name($result, $i) == "tilino_kaanteinen" or
  mysql_field_name($result, $i) == "tilino_marginaali" or
  mysql_field_name($result, $i) == "tilino_osto_marginaali" or
  mysql_field_name($result, $i) == "tilino_triang") {

  $ulos = "<td>".livesearch_kentta("mainform", "TILIHAKU", $nimi, 270, $trow[$i], "EISUBMIT")."</td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "nimitys") {

  //  Jos meillä on jokin viranomaistuote huomautetaan että nimitystä ei venkslailla
  $query  = "SELECT tunnus
             FROM tuote
             WHERE yhtio     = '$kukarow[yhtio]'
             and tuoteno     = '$tuoteno'
             and tuotetyyppi in ('A','B')";
  $sresult = pupe_query($query);

  if (mysql_num_rows($sresult) != 0) {
    $ulos = "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'></td><td class = 'back'><font class='info'>".t("HUOM: Viranomaistuotteen nimiystä ei tulisi muuttaa!")."</font></td>";
    $jatko=0;
  }
}

if (mysql_field_name($result, $i) == "tuotetyyppi") {
  $sel = array_fill_keys(array($trow[$i]), " selected") + array_fill_keys(array('A', 'B', 'R', 'K', 'M'), '');

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  if ($laji == "V" or $trow[$i] == "A" or $trow[$i] == "B") {
    $ulos .= "<option value='A' $sel[A]>".t("Päiväraha")."</option>";
    $ulos .= "<option value='B' $sel[B]>".t("Muu kulu")."</option>";
  }
  else {
    $ulos .= "<option value=''>".t("Normaali / Valmiste")."</option>";
    $ulos .= "<option value='R' $sel[R]>".t("Raaka-aine")."</option>";
    $ulos .= "<option value='K' $sel[K]>".t("Palvelu")."</option>";
    $ulos .= "<option value='M' $sel[M]>".t("Muu/Informatiivinen")."</option>";
  }
  $ulos .= "</select></td>";

  $jatko=0;
}

if (mysql_field_name($result, $i) == "vienti") {
  $ulos = "<td>";
  $ulos .= "<input type='text' name='{$nimi}' value='{$trow[$i]}' />";

  $query = "SELECT *
            FROM avainsana
            WHERE yhtio = '{$kukarow['yhtio']}'
            and laji    = 'maaryhma'";
  $maaryhmares = pupe_query($query);

  if (mysql_num_rows($maaryhmares) > 0) {
    $ulos .= "&nbsp;";

    $ulos .= "<select name='maaryhma_vienti' ".js_alasvetoMaxWidth($nimi, 300).">";
    $ulos .= "<option value = ''>".t("Ei maaryhmää")."</option>";

    while ($maaryhmarow = mysql_fetch_assoc($maaryhmares)) {
      $sel = $trow[$i] == $maaryhmarow['selite'] ? " selected" : "";
      $ulos .= "<option value='{$maaryhmarow['selite']}'{$sel}>{$maaryhmarow['selitetark']}</option>";
    }

    $ulos .= "</select>";
  }

  $ulos .= "</td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "myynninseuranta") {
  $sel = $trow[$i] == "E" ? " selected" : "";

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = ''>".t("Tuote otetaan huomioon myynninseurannassa")."</option>";
  $ulos .= "<option value = 'E'{$sel}>".t("Tuotetta ei oteta huomioon myynninseurannassa eikä ABC-analysoinnissa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kehahin" or
  mysql_field_name($result, $i) == "vihahin" or
  mysql_field_name($result, $i) == "vihapvm" or
  mysql_field_name($result, $i) == "saldo" or
  mysql_field_name($result, $i) == "saldoaika" or
  mysql_field_name($result, $i) == "epakurantti25pvm" or
  mysql_field_name($result, $i) == "epakurantti50pvm" or
  mysql_field_name($result, $i) == "epakurantti75pvm" or
  mysql_field_name($result, $i) == "epakurantti100pvm") {
  $tyyppi = 2;
}

if (mysql_field_name($result, $i) == "kehahin" and $yhtiorow["palvelutuotteiden_kehahinnat"] == "K" and ($trow["ei_saldoa"] != "" or $uusi != "")) {
  $tyyppi = 1;
}

if (mysql_field_name($result, $i) == "kehahin" and $yhtiorow["kehahinarvio_ennen_ensituloa"] == "K" and $poistolukko == "") {
  $tyyppi = 1;
}

if ($laji != "V" and
  (mysql_field_name($result, $i) == "try"
    or mysql_field_name($result, $i) == "osasto"
    or mysql_field_name($result, $i) == "tuotemerkki"
    or mysql_field_name($result, $i) == "malli"
    or mysql_field_name($result, $i) == "mallitarkenne"
    or mysql_field_name($result, $i) == "valmistuslinja")) {

  $sresult = t_avainsana(mysql_field_name($result, $i));
  $sarnimi = mysql_field_name($result, $i);

  // jos avainsanoja on perustettu tehdään dropdown
  if (mysql_num_rows($sresult) > 0) {
    $ulos = "<td><select id='$sarnimi' name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

    $ulos .= "<option value = ''>".t("Ei valintaa")."</option>";

    while ($srow = mysql_fetch_array($sresult)) {
      $sel = '';
      if ($trow[$i] == $srow["selite"] and $srow["selite"] != "") {
        $sel = "selected";
      }

      $ulos .= "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
    }

    $ulos .= "</select>";
    $ulos .= " <a href='#' onclick=\"js_open_yllapito('$sarnimi','toim=avainsana&lukitse_laji=$sarnimi');\"><img src='pics/lullacons/add.png'>".t("Uusi")." ".t(mysql_field_name($result, $i))."</a></td>";

    $ulos .= "</td>";
    $jatko = 0;
  }
}

if (mysql_field_name($result, $i) == "valuutta") {
  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY jarjestys";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow[0] and $vrow[0] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[0]' $sel>$vrow[0]</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "ei_saldoa") {

  $sel = $sela = "";
  if ($trow[$i] != '') {
    $sel = "SELECTED";
  }
  else {
    $sela = "SELECTED";
  }

  if ($poistolukko != "") {
    $ulos  = "<td>";

    if ($sela != "") $ulos .= t("Saldollinen tuote");
    else $ulos .= t("Saldoton tuote");

    $ulos .= "<input type='hidden' name='$nimi' value='$trow[$i]'></td>";
  }
  else {
    $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
    $ulos .= "<option value = '' $sela>".t("Saldollinen tuote")."</option>";
    $ulos .= "<option value = 'o' $sel>".t("Saldoton tuote")." (".t("Ei varastokirjanpitoa").")</option>";
    $ulos .= "</select></td>";
  }


  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kommentoitava") {

  $sel = $sela = "";
  if ($trow[$i] != '') {
    $sel = "SELECTED";
  }
  else {
    $sela = "SELECTED";
  }

  $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = '' $sela>".t("Normaalikäsittely")."</option>";
  $ulos .= "<option value = 'o' $sel>".t("Tuotteelle on annettava rivikommentti")."</option>";
  $ulos .= "<option value = 'r' $sel>".t("Tuotteelle on annettava reklamaatiolla rivikommentti")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "sarjanumeroseuranta") {

  $sel = array_fill_keys(array($trow[$i]), "selected") + array_fill_keys(array('S', 'T', 'V', 'E', 'F', 'G'), '');

  $query = "SELECT count(*) saldo
            FROM tuotepaikat
            WHERE yhtio  = '$kukarow[yhtio]'
            and tuoteno  = '$tuoteno'
            and saldo   != 0";
  $vresult = pupe_query($query);
  $vrow = mysql_fetch_array($vresult);

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  if ($poistolukko == "" or $trow[$i] == "" or ($vrow["saldo"] == 0 and in_array($trow[$i], array("", "T", "V", "E", "F")))) {
    $ulos .= "<option value = ''>".t("Ei seurantaa")."</option>";
  }
  if ($poistolukko == "" or $sel["S"] == "selected" or ($vrow["saldo"] == 0 and $trow[$i] == "S")) {
    $ulos .= "<option value = 'S' $sel[S]>".t("Sarjanumeroseuranta. Osto-Myynti / In-Out varastonarvo")." (S)</option>";
  }
  if ($poistolukko == "" or $sel["T"] == "selected" or ($vrow["saldo"] == 0 and in_array($trow[$i], array("", "T", "V", "E", "F")))) {
    $ulos .= "<option value = 'T' $sel[T]>".t("Sarjanumeroseuranta. Myynti / Keskihinta-varastonarvo")." (T)</option>";
  }
  if ($poistolukko == "" or $sel["V"] == "selected" or ($vrow["saldo"] == 0 and in_array($trow[$i], array("", "T", "V", "E", "F")))) {
    $ulos .= "<option value = 'V' $sel[V]>".t("Sarjanumeroseuranta. Osto-Myynti / Keskihinta-varastonarvo")." (V)</option>";
  }
  if ($poistolukko == "" or $sel["E"] == "selected" or in_array($trow[$i], array("E", "F")) or ($vrow["saldo"] == 0 and in_array($trow[$i], array("", "T", "V", "E", "F")))) {
    $ulos .= "<option value = 'E' $sel[E]>".t("Eränumeroseuranta. Osto-Myynti / Keskihinta-varastonarvo")." (E)</option>";
  }
  if ($poistolukko == "" or $sel["F"] == "selected" or in_array($trow[$i], array("E", "F")) or ($vrow["saldo"] == 0 and in_array($trow[$i], array("", "T", "V", "E", "F")))) {
    $ulos .= "<option value = 'F' $sel[F]>".t("Eränumeroseuranta parasta-ennen päivällä. Osto-Myynti / Keskihinta-varastonarvo")." (F)</option>";
  }
  if ($poistolukko == "" or $sel["G"] == "selected") {
    $ulos .= "<option value = 'G' $sel[G]>".t("Eränumeroseuranta. Osto-Myynti / In-Out varastonarvo")." (G)</option>";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "automaattinen_sarjanumerointi") {
  $sel       = array();
  $apu       = $trow[$i];
  $sel[$apu] = " selected";

  $ulos = "<td>
             <select name='{$nimi}'>
               <option value='0'{$sel["0"]}>" . t("Ei automaattista sarjanumerointia") . "</option>
               <option value='1'{$sel["1"]}>" . t("Automaattinen sarjanumerointi kaavalla tuotteen nimitys + juokseva numero") . "</option>
               <option value='2'{$sel["2"]}>" . t("Automaattinen sarjanumerointi kaavalla vuosi + 1000 alkava juokseva numero") . "</option>
             </select>
           </td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "aleryhma") {

  $query = "SELECT ryhma, if (ryhma!=selite, concat_ws(' - ', ryhma, selite), ryhma) selitetark
            FROM perusalennus
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY ryhma";
  $vresult = pupe_query($query);

  $ulos = "<td><select id='aleryhma' name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = ''>".t("Ei ryhmää")."</option>";

  while ($srow = mysql_fetch_array($vresult)) {
    $sel = '';
    if ($trow[$i] == $srow["ryhma"] and $srow["ryhma"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value='$srow[ryhma]' $sel>$srow[selitetark]</option>";
  }

  $ulos .= "</select>";
  $ulos .= " <a href='#' onclick=\"js_open_yllapito('aleryhma','toim=perusalennus');\"><img src='pics/lullacons/add.png'>".t("Uusi ryhmä")."</a></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kuvaus" or mysql_field_name($result, $i) == "lyhytkuvaus" or mysql_field_name($result, $i) == "mainosteksti" or mysql_field_name($result, $i) == "tilausrivi_kommentti" or mysql_field_name($result, $i) == "kerayskommentti" or mysql_field_name($result, $i) == "purkukommentti" or mysql_field_name($result, $i) == "ostokommentti") {
  $ulos = "<td><textarea cols='50' rows='10' name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "yksikko") {
  $vresult = t_avainsana("Y");
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if (strtoupper($trow[$i]) == strtoupper($vrow["selite"]) and $vrow["selite"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "alkuperamaa") {
  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";

  $ulos .= "<option value = '' ></option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow[0] and $vrow[0] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[0]' $sel>$vrow[1]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "status") {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= product_status_options($trow[$i]);
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "alv") {
  if ($trow[$i]=='') $trow[$i]=alv_oletus();
  $ulos = "<td>".alv_popup($nimi, $trow[$i])."</td>";
  $jatko = 0;
}

// ei piirretä ellei yhtiöparametri ohjeista katsomaan täältä
if (mysql_field_name($result, $i) == "suoratoimitus") {
  if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('V', 'W', 'I', 'J'))) {

    $sel = array_fill_keys(array('S', 'U', 'X'), '');
    $sel[$trow[$i]] = " SELECTED";

    $ulos = "<td><select name='$nimi'".js_alasvetoMaxWidth($nimi, 300).">
      <option value=''>".t("Myyntitilaukselta ei voi luoda ostorivejä tästä tuotteesta")."</option>";

    if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('W', 'J'))) {
      $ulos .= "<option value='S' {$sel['S']}>".t("Myyntitilaukselta voi luoda ostorivejä tästä tuotteesta varastoon tai asiakkaalle")."</option>
            <option value='U' {$sel['U']}>".t("Myyntitilaukselta voi luoda ostorivejä tästä tuotteesta asiakkaalle")."</option>
            <option value='X' {$sel['X']}>".t("Myyntitilaukselta voi luoda ostorivejä tästä tuotteesta varastoon")."</option>";
    }
    else {
      $ulos .= "<option value='S' {$sel['S']}>".t("Myyntitilaukselta voi luoda ostorivejä tästä tuotteesta varastoon")."</option>";
    }

    $ulos .= "</select></td>";
    $jatko = 0;
  }
  else {
    $tyyppi = 0;
  }
}

if (mysql_field_name($result, $i) == "hinnastoon") {

  $sel = array_fill_keys(array($trow[$i]), " selected") + array_fill_keys(array('E', 'W', 'V'), '');

  $ulos = "<td><select name = '$nimi' ".js_alasvetoMaxWidth($nimi, 300).">
    <option value=''>".t("Tuote näytetään hinnastossa, mutta ei verkkokaupassa")."</option>
    <option value='E' {$sel['E']}>".t("Tuotetta ei näytetä hinnastossa, eikä verkkokaupassa")."</option>
    <option value='W' {$sel['W']}>".t("Tuote näkyy hinnastossa ja verkkokaupassa")."</option>
    <option value='V' {$sel['V']}>".t("Tuote näkyy hinnastossa sekä verkkokaupassa jos asiakkaalla asiakasalennus tai asiakashinta")."</option>
    </select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "myyjanro") {

  $query = "select distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
  $vresult = pupe_query($query);

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = '' >".t("Ei myyjää")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['myyja'] and $vrow["myyja"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "ostajanro") {

  $query = "SELECT distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
  $vresult = pupe_query($query);

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = '' >".t("Ei ostajaa")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['myyja'] and $vrow["myyja"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
if (mysql_field_name($result, $i) == "tuotepaallikko") {

  $query = "SELECT distinct myyja, nimi from kuka where yhtio='$kukarow[yhtio]' and myyja>0 order by myyja";
  $vresult = pupe_query($query);

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = '' >".t("Ei tuotepäällikköä")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['myyja'] and $vrow["myyja"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[myyja]' $sel>$vrow[myyja] - $vrow[nimi]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "projekti" or mysql_field_name($result, $i) == "kohde" or mysql_field_name($result, $i) == "kustp") {

  if (mysql_field_name($result, $i) == "projekti") $kptyyppi = "P";
  if (mysql_field_name($result, $i) == "kohde")    $kptyyppi = "O";
  if (mysql_field_name($result, $i) == "kustp")    $kptyyppi = "K";

  $tpque = "SELECT tunnus, nimi, koodi
            from kustannuspaikka
            where yhtio   = '$kukarow[yhtio]'
            and tyyppi    = '$kptyyppi'
            and kaytossa != 'E'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($tpque);

  $sel="";
  if ($trow[$i] == "") {
    $sel = "selected";
  }
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300)."><option value='' $sel>".t("Ei valintaa")."</option>";

  while ($vrow = mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow["tunnus"]) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "kuvaus") {
  if ($laji == "V") {
    $sel[$trow[$i]] = "SELECTED";
    $ulos = "<td>";
    $ulos .= "<select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
    $ulos .= "<option value = '' >".t("Ei ilmoiteta vuosi-ilmoituksessa")."</option>";
    $ulos .= "<option value = '50' $sel[50]>".t("50 Päivärahat  ja ateriakorvaukset")."</option>";
    $ulos .= "<option value = '56' $sel[56]>".t("56 Verovapaa kilometrikorvaus")."</option>";
    $ulos .= "</select>";
    $ulos .= "</td>";
  }
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "tarratyyppi") {

  $sel=array($trow[$i] => "SELECTED");

  $vresult = t_avainsana("TARRATYYPPI");

  if (mysql_num_rows($vresult)>0) {
    $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">
            <option value=''>".t("Ei tilaustuotetarraa")."</option>";
    while ($vrow = mysql_fetch_array($vresult)) {
      $ulos .= "<option value='$vrow[selite]' ".$sel[$vrow["selite"]].">$vrow[selite] - $vrow[selitetark]</option>";
    }
    $ulos .= "</select></td>";
  }
  else {
    $tyyppi = 0;
  }

  $jatko = 0;
}


//* Nämä sarakkeet eivät välttämättä löydy kaikista installaatioista (Laitemyyntispecific)*//
if (mysql_field_name($result, $i) == "vari") {
  $vresult = t_avainsana("SARJANUMERON_LI", "", "and avainsana.selite='VARIRUNKO'");

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = '' >".t("Valitse rungon väri")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['selitetark'] and $vrow["selitetark"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
if (mysql_field_name($result, $i) == "laitetyyppi") {
  $vresult = t_avainsana("SARJANUMERON_LI", "", "and avainsana.selite='TYYPPI'");

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = '' >".t("Valitse tyyppi")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['selitetark'] and $vrow["selitetark"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
if (mysql_field_name($result, $i) == "runkotyyppi") {
  $vresult = t_avainsana("SARJANUMERON_LI", "", "and avainsana.selite='RUNKOTYYPPI'");

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = '' >".t("Valitse tyyppi")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['selitetark'] and $vrow["selitetark"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
if (mysql_field_name($result, $i) == "kilpi") {
  $vresult = t_avainsana("SARJANUMERON_LI", "", "and avainsana.selite='KILPI'");

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = '' >".t("Valitse kilpi")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['selitetark'] and $vrow["selitetark"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
if (mysql_field_name($result, $i) == "materiaali") {
  $vresult = t_avainsana("SARJANUMERON_LI", "", "and avainsana.selite='MATERIAALI'");

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = '' >".t("Valitse veneen materiaali")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['selitetark'] and $vrow["selitetark"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
if (mysql_field_name($result, $i) == "koneistus") {
  $vresult = t_avainsana("SARJANUMERON_LI", "", "and avainsana.selite='KONEISTUS'");

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = '' >".t("Valitse veneen koneistus")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['selitetark'] and $vrow["selitetark"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}
if (mysql_field_name($result, $i) == "sprinkleri") {
  $vresult = t_avainsana("SARJANUMERON_LI", "", "and avainsana.selite='SPRINKLERI'");

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value = '' >".t("Valitse tyyppi")."</option>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";
    if ($trow[$i] == $vrow['selitetark'] and $vrow["selitetark"] != "") {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "yksin_kerailyalustalle") {

  $sel = array_fill_keys(array($trow[$i]), " selected") + array_fill_keys(array('K', 'E'), '');

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value=''>".t("Ei")."</option>";
  $ulos .= "<option value='K'{$sel['K']}>".t("Kyllä")." - ".t("Ehdoton")."</option>";
  $ulos .= "<option value='E'{$sel['E']}>".t("Kyllä")." - ".t("Ehdollinen")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if ((mysql_field_name($result, $i) == "vakkoodi" or mysql_field_name($result, $i) == "vak_imdg_koodi") and $yhtiorow["vak_kasittely"] != "") {

  $sel = array();
  $sel[$trow[$i]] = ' selected';

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";

  $query_taulu = mysql_field_name($result, $i) == "vak_imdg_koodi" ? "vak_imdg" : "vak";

  $query = "SELECT tunnus, concat_ws(' / ', yk_nro, substring(nimi_ja_kuvaus,1,70), luokka, luokituskoodi, pakkausryhma, lipukkeet, erityismaaraykset, rajoitetut_maarat_ja_poikkeusmaarat_1) nimi
            FROM {$query_taulu}
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $vak_res = pupe_query($query);

  $ulos .= "<option value=''>".t("Valitse")."</option>";

  while ($vak_row = mysql_fetch_assoc($vak_res)) {

    $selected = $vak_row['tunnus'] == $trow[$i] ? ' selected' : '';

    $ulos .= "<option value='{$vak_row['tunnus']}'{$selected}>{$vak_row['nimi']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "keraysvyohyke") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value=''>".t("Ei kuulu keräysvyöhykkeeseen")."</option>";

  $query = "SELECT tunnus, nimitys FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
  $keraysvyohyke_result = pupe_query($query);

  while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result)) {

    $selected = $keraysvyohyke_row['tunnus'] == $trow[$i] ? ' selected' : '';

    $ulos .= "<option value='{$keraysvyohyke_row['tunnus']}'{$selected}>{$keraysvyohyke_row['nimitys']}</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "panttitili") {

  $sel = trim($trow[$i]) == 'K' ? ' selected' : '';

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value=''>".t("Ei käytössä")."</option>";
  $ulos .= "<option value='K'{$sel}>".t("Käytössä")."</option>";
  $ulos .= "</select>";

  if (trim($trow[$i]) == 'K') {
    $query_ale_lisa = generoi_alekentta('M', 'panttitili');

    $query = "SELECT
              round(sum(hinta*kpl* {$query_ale_lisa}), 2) as rivihinta
              FROM panttitili
              WHERE panttitili.yhtio = '{$kukarow['yhtio']}'
              AND panttitili.tuoteno = '{$trow['tuoteno']}'
              AND panttitili.status  = ''";
    $panttitili_result = pupe_query($query);
    $panttitili_row = mysql_fetch_assoc($panttitili_result);

    $ulos .= " <font class='message'>".t("Tuotetta panttitileillä yhteensä").": {$panttitili_row['rivihinta']} {$yhtiorow['valkoodi']}</font>";
  }

  $ulos .= "</td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "meria_saastuttava") {

  $sel = trim($trow[$i]) == 'K' ? ' selected' : '';

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value=''>".t("Ei")."</option>";
  $ulos .= "<option value='K'{$sel}>".t("Kyllä")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "ostoehdotus") {

  $sel = trim($trow[$i]) == 'E' ? ' selected' : '';

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 300).">";
  $ulos .= "<option value=''>".t("Ehdotetaan ostoehdotusohjelmissa tilattavaksi")."</option>";
  $ulos .= "<option value='E'{$sel}>".t("Ei ehdoteta ostoehdotusohjelmissa tilattavaksi")."</option>";
  $ulos .= "</select></td>";
  $jatko = 0;
}
