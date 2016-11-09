<?php

if ($_POST["tee"] == 'lataa_tiedosto') {
  $lataa_tiedosto = 1;
}

if ($_POST["kaunisnimi"] != '') {
  $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require 'inc/parametrit.inc';

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
  <!--

  function toggleAll(toggleBox) {

    var currForm = toggleBox.form;
    var isChecked = toggleBox.checked;
    var nimi = toggleBox.name;

    for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
      if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,7) == nimi && currForm.elements[elementIdx].value != '".t("Ei valintaa")."' && currForm.elements[elementIdx].value != null) {
        currForm.elements[elementIdx].checked = isChecked;
      }
    }
  }

  function verify() {
    msg = '".t("Oletko varma?")."';

    if (confirm(msg)) {
      return true;
    }
    else {
      skippaa_tama_submitti = true;
      return false;
    }
  }

  //-->
  </script>";

echo "<font class='head'>", t("Tuotekuvien ylläpito"), "</font><hr>";

echo "<form method='post' action='yllapito_tuotekuvat.php'>";
echo "<table>";
echo "<input type='hidden' name='tee' value='LISTAA'>";

echo "<tr valign='top'>";
echo "<td>";
echo "<table>";
echo "<tr>";
echo "<td class='back'>";

// näytetään soveltuvat osastot
// tehdään avainsana query
$res2 = t_avainsana("OSASTO");

if (mysql_num_rows($res2) > 11) {
  echo "<div style='height:320px;overflow:auto;'>";
}

//############## TUOTEOSASTO
echo "<table>";
echo "<tr>";
echo "<th colspan='2'>", t("Tuoteosasto"), ":</th>";
echo "</tr>";
echo "<tr>";
echo "<td><input type='checkbox' name='mul_osa' onclick='toggleAll(this);'></td><td nowrap>", t("Ruksaa kaikki"), "</td>";
echo "</tr>";

if (isset($osasto) and $osasto != '') {
  $mul_osasto = explode(",", $osasto);
}

while ($rivi = mysql_fetch_array($res2)) {
  $mul_check = '';
  if (count($mul_osasto) > 0) {
    if (in_array($rivi['selite'], $mul_osasto)) {
      $mul_check = 'CHECKED';
    }
  }

  echo "<tr><td><input type='checkbox' name='mul_osasto[]' value='{$rivi['selite']}' {$mul_check}></td><td>{$rivi['selite']} - {$rivi['selitetark']}</td></tr>";
}

echo "</table>";

if (mysql_num_rows($res2) > 11) {
  echo "</div>";
}

echo "</td>";
echo "</tr>";
echo "</table>";
echo "</td>";

echo "<td>";
echo "<table>";
echo "<tr>";
echo "<td valign='top' class='back'>";

// näytetään soveltuvat tryt
// tehdään avainsana query
$res2 = t_avainsana("TRY");

if (mysql_num_rows($res2) > 11) {
  echo "<div style='height:320px;overflow:auto;'>";
}

//############## TUOTERYHMÄ
echo "<table>";
echo "<tr>";
echo "<th colspan='2'>", t("Tuoterymä"), ":</th>";
echo "</tr>";
echo "<tr>";
echo "<td><input type='checkbox' name='mul_try' onclick='toggleAll(this);'></td><td nowrap>", t("Ruksaa kaikki"), "</td>";
echo "</tr>";

if (isset($try) and $try != '') {
  $mul_try = explode(",", $try);
}

while ($rivi = mysql_fetch_array($res2)) {
  $mul_check = '';
  if (count($mul_try) > 0) {
    if (in_array($rivi['selite'], $mul_try)) {
      $mul_check = 'CHECKED';
    }
  }

  echo "<tr><td><input type='checkbox' name='mul_try[]' value='{$rivi['selite']}' {$mul_check}></td><td>{$rivi['selite']} - {$rivi['selitetark']}</td></tr>";
}

echo "</table>";

if (mysql_num_rows($res2) > 11) {
  echo "</div>";
}

echo "</td>";
echo "</tr>";
echo "</table>";
echo "</td>";

echo "<td>";
echo "<table>";
echo "<tr>";
echo "<td valign='top' class='back'>";

// näytetään soveltuvat tuotemerkit
$query = "  SELECT distinct tuotemerkki FROM tuote use index (yhtio_tuotemerkki) WHERE yhtio='$kukarow[yhtio]' and tuotemerkki != '' ORDER BY tuotemerkki";
$res2  = pupe_query($query);

if (mysql_num_rows($res2) > 11) {
  echo "<div style='height:320px;overflow:auto;'>";
}

//############## TUOTEMERKKI
echo "<table>";
echo "<tr>";
echo "<th colspan='2'>", t("Tuotemerkki"), ":</th>";
echo "</tr>";
echo "<tr>";
echo "<td><input type='checkbox' name='mul_tmr' onclick='toggleAll(this);'></td><td nowrap>", t("Ruksaa kaikki"), "</td>";
echo "</tr>";

if (isset($tmr) and $tmr != '') {
  $mul_tmr = explode(",", $tmr);
}

while ($rivi = mysql_fetch_array($res2)) {
  $mul_check = '';
  if (count($mul_tmr) > 0) {
    if (in_array($rivi['tuotemerkki'], $mul_tmr)) {
      $mul_check = 'CHECKED';
    }
  }

  echo "<tr><td><input type='checkbox' name='mul_tmr[]' value='{$rivi['tuotemerkki']}' {$mul_check}></td><td>{$rivi['tuotemerkki']}</td></tr>";
}

echo "</table>";

if (mysql_num_rows($res2) > 11) {
  echo "</div>";
}

echo "</td>";
echo "</tr>";
echo "</table>";
echo "</td>";

echo "<td>";
echo "<table>";
echo "<tr>";
echo "<td valign='top' class='back'>";

//############## TUNNUS (voidaan hakea tunnusvälillä)
echo "<table width='100%'>";
echo "<tr>";
echo "<th colspan='3'>", t("Tuotenumerohaku"), ":</th>";
echo "</tr>";
echo "<tr>";
echo "<td><input type='text' name='tuoteno' size='15' value='{$tuoteno}'></td>";
echo "</tr>";
echo "</table>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td valign='top' class='back'>";

//############## KÄYTTÖTARKOITUS (onko thumbnail vai tuotekuva)
echo "<table width='100%'>";
echo "<tr>";
echo "<th colspan='2'>", t("Käyttötarkoitus"), ":</th>";
echo "</tr>";
echo "<tr>";
echo "<td><input type='checkbox' name='mul_kay' onclick='toggleAll(this);'></td><td nowrap>", t("Ruksaa kaikki"), "</td>";
echo "</tr>";

if (isset($kayt) and $kayt != '') {
  $mul_kay = explode(",", $kayt);
}

$mul_check = '';
if (count($mul_kay) > 0) {
  if (in_array('th', $mul_kay)) {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='mul_kay[]' value='th' {$mul_check}></td><td nowrap>", t("Thumbnail"), "</td></tr>";

$mul_check = '';
if (count($mul_kay) > 0) {
  if (in_array('tk', $mul_kay)) {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='mul_kay[]' value='tk' {$mul_check}></td><td nowrap>", t("Tuotekuva"), "</td></tr>";

$mul_check = '';
if (count($mul_kay) > 0) {
  if (in_array('hr', $mul_kay)) {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='mul_kay[]' value='hr' {$mul_check}></td><td nowrap>", t("Painokuva"), "</td></tr>";

$mul_check = '';
if (count($mul_kay) > 0) {
  if (in_array('mu', $mul_kay)) {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='mul_kay[]' value='mu' {$mul_check}></td><td nowrap>", t("Muu"), "</td></tr>";

echo "</table>";

echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td valign='top' class='back'>";

//############## NÄYTÄ (jos halutaan näyttää myös tuotekuvien popup-kuvat)
echo "<table width='100%'>";
echo "<tr>";
echo "<th colspan='2'>", t("Näytä"), ":</th>";
echo "</tr>";

$mul_check = '';
if ($nayta_tk != '') {
  if ($nayta_tk == 'naytetaan') {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='nayta_tk' value='naytetaan' {$mul_check}></td><td nowrap>", t("Tuotekuvat"), "</td></tr>";

$mul_check = '';
if ($nayta_hr != '') {
  if ($nayta_hr == 'naytetaan') {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='nayta_hr' value='naytetaan' {$mul_check}></td><td nowrap>", t("Painokuvat"), "</td></tr>";


$mul_check = '';
if ($nayta_th != '') {
  if ($nayta_th == 'naytetaan') {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='nayta_th' value='naytetaan' {$mul_check}></td><td nowrap>", t("Thumbnailit"), "</td></tr>";
echo "</table>";

echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td valign='top' class='back'>";

//############## SELITE (voidaan rajata selite -> tyhjä / ei tyhjä)
echo "<table width='100%'>";
echo "<tr>";
echo "<th colspan='2'>".t("Selite").":</th>";
echo "</tr>";

if (isset($sel) and $sel != '') {
  $mul_sel = explode(",", $sel);
}

$mul_check = '';
if (count($mul_sel) > 0 and in_array('ei_tyhja', $mul_sel)) {
  $mul_check = 'CHECKED';
}

echo "<tr><td><input type='checkbox' name='mul_sel[]' value='ei_tyhja' {$mul_check}></td><td nowrap>", t("Ei tyhjä"), "</td></tr>";

$mul_check = '';
if (count($mul_sel) > 0 and in_array('tyhja', $mul_sel)) {
  $mul_check = 'CHECKED';
}

echo "<tr><td><input type='checkbox' name='mul_sel[]' value='tyhja' {$mul_check}></td><td nowrap>", t("Tyhjä"), "</td></tr>";
echo "</table>";

echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td valign='top' class='back'>";

//############## STATUS (P ja X)
echo "<table width='100%'>";
echo "<tr>";
echo "<th colspan='2'>", t("Status"), ":</th>";
echo "</tr>";

if (isset($status) and $status != '') {
  $mul_sta = explode(",", $status);
}

$mul_check = '';
if (count($mul_sta) > 0 and in_array('P', $mul_sta)) {
  $mul_check = 'CHECKED';
}

echo "<tr><td><input type='checkbox' name='mul_sta[]' value='P' {$mul_check}></td><td nowrap>", t("Näytä myös status P"), "</td></tr>";

$mul_check = '';
if (count($mul_sta) > 0 and in_array('X', $mul_sta)) {
  $mul_check = 'CHECKED';
}

echo "<tr><td><input type='checkbox' name='mul_sta[]' value='X' {$mul_check}></td><td nowrap>", t("Näytä myös status X"), "</td></tr>";
echo "</table>";

echo "</td>";
echo "</tr>";

echo "</table>";
echo "</td>";

echo "<td>";
echo "<table>";
echo "<tr>";
echo "<td valign='top' class='back'>";

//############## SIVUTUS (sivutus tai näytetään kaikki)
echo "<table width='100%'>";
echo "<tr>";
echo "<th colspan='2'>".t("Sivutus").":</th>";
echo "</tr>";

$mul_check = '';
if ($mul_siv == 'ei_sivutusta') {
  $mul_check = 'CHECKED';
}

echo "<tr><td><input type='checkbox' name='mul_siv' value='ei_sivutusta' {$mul_check}></td><td nowrap>".t("Ei sivutusta")."</td></tr>";

echo "</table>";

echo "</td>";

echo "</tr>";
echo "<tr>";

echo "<td valign='top' class='back'>";

//############## Tiedoston pääte
echo "<table width='100%'>";
echo "<tr>";
echo "<th colspan='2'>".t("Tiedoston pääte").":</th>";
echo "</tr>";

echo "<tr><td><input type='checkbox' name='mul_ext' onclick='toggleAll(this);'></td><td nowrap>".t("Ruksaa kaikki")."</td></tr>";

$mul_check = '';
if ($mul_ext != '') {
  if (in_array('png', $mul_ext)) {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='mul_ext[]' value='png' ", $mul_check, "></td><td nowrap>".t("Png")."</td></tr>";

$mul_check = '';
if ($mul_ext != '') {
  if (in_array('jpeg', $mul_ext)) {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='mul_ext[]' value='jpeg' ", $mul_check, "></td><td nowrap>".t("Jpeg")."</td></tr>";

$mul_check = '';
if ($mul_ext != '') {
  if (in_array('gif', $mul_ext)) {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='mul_ext[]' value='gif' ", $mul_check, "></td><td nowrap>".t("Gif")."</td></tr>";

$mul_check = '';
if ($mul_ext != '') {
  if (in_array('bmp', $mul_ext)) {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='mul_ext[]' value='bmp' ", $mul_check, "></td><td nowrap>".t("Bmp")."</td></tr>";

$mul_check = '';
if ($mul_ext != '') {
  if (in_array('pdf', $mul_ext)) {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='mul_ext[]' value='pdf' ", $mul_check, "></td><td nowrap>".t("Pdf")."</td></tr>";

echo "</table>";

echo "</td>";

echo "</tr>";

echo "<tr>";
echo "<td valign='top' class='back'>";

//############## Kuvan mitat (korkeus, leveys)
echo "<table width='100%'>";
echo "<tr>";
echo "<th colspan='2'>", t("Kuvan mitat"), ":</th>";
echo "</tr>";

echo "<tr><td><input type='checkbox' name='mul_siz' onclick='toggleAll(this);'></td><td nowrap>", t("Ruksaa kaikki"), "</td></tr>";

if (isset($korkeus) and $korkeus != '') {
  $mul_siz[] = 'korkeus';
}

$mul_check = '';
if (count($mul_siz) > 0) {
  if (in_array('korkeus', $mul_siz)) {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='mul_siz[]' value='korkeus' {$mul_check}></td><td nowrap>", t("Korkeus"), "</td></tr>";

if (isset($leveys) and $leveys != '') {
  $mul_siz[] = 'leveys';
}

$mul_check = '';
if (count($mul_siz) > 0) {
  if (in_array('leveys', $mul_siz)) {
    $mul_check = 'CHECKED';
  }
}

echo "<tr><td><input type='checkbox' name='mul_siz[]' value='leveys' {$mul_check}></td><td nowrap>", t("Leveys"), "</td></tr>";

echo "</table>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td valign='top' class='back'>";

//############## Verkkokauppa näkyvyys
echo "<table width='100%'>";
echo "<tr>";
echo "<th colspan='2'>".t("Näkyvyys").":</th>";
echo "</tr>";

$vain_check = $ei_check = $tyhja_check ='';

if ($verkkokauppa_tuotteet == 'vain') {
  $vain_check = 'CHECKED';
}
elseif ($verkkokauppa_tuotteet == 'ei') {
  $ei_check = 'CHECKED';
}
else {
  $tyhja_check = 'CHECKED';
}

echo "<tr><td>";
echo "<input type='radio' name='verkkokauppa_tuotteet' value='' {$tyhja_check}>";
echo "</td><td nowrap>".t("Ei näkyvyys rajausta")."</td></tr>";

echo "<tr><td>";
echo "<input type='radio' name='verkkokauppa_tuotteet' value='vain' {$vain_check}>";
echo "</td><td nowrap>".t("Vain verkkokauppa-tuotteet")."</td></tr>";

echo "<tr><td>";
echo "<input type='radio' name='verkkokauppa_tuotteet' value='ei' {$ei_check}>";
echo "</td><td nowrap>".t("Ei verkkokauppa-tuotteita")."</td></tr>";

echo "</table>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td valign='top' class='back'>";

//############## Exceliin tallennusvaihtoehto
echo "<table width='100%'>";
echo "<tr>";
echo "<th colspan='2'>".t("Tallenna").":</th>";
echo "</tr>";

$mul_check = '';
if ($mul_exl == 'tallennetaan') {
  $mul_check = 'CHECKED';
}

echo "<tr><td><input type='checkbox' name='mul_exl' value='tallennetaan' ", $mul_check, "></td><td nowrap>".t("Exceliin")."</td></tr>";

echo "</table>";
echo "</td>";
echo "</tr>";

echo "</table>";
echo "</td>";
echo "</tr>";
echo "</table>";

echo "<br/>";
echo "<input type='submit' value='".t('Hae')."'>";
echo "</form>";

echo "<br/><br/>";

if ($tee == '' and count($mul_del) > 0) {

  echo t('Poistettiin kuvat tuotteista: '), "<br />";

  foreach ($mul_del as $key => $val) {
    list($tunnus, $ltunnus, $ltiedtunnus, $kayttotarkoitus, $filetype) = explode('_', $val);

    $tunnus = mysql_real_escape_string($tunnus);
    $ltunnus = mysql_real_escape_string($ltunnus);
    $ltiedtunnus = mysql_real_escape_string($ltiedtunnus);
    $kayttotarkoitus = mysql_real_escape_string($kayttotarkoitus);
    $filetype = mysql_real_escape_string($filetype);

    echo "$tunnus ($filetype  $kayttotarkoitus)<br />";

    $query = "DELETE
              FROM liitetiedostot
              WHERE yhtio          = '$kukarow[yhtio]'
              AND tunnus           = '$ltiedtunnus'
              AND liitostunnus     = '$ltunnus'
              AND kayttotarkoitus  = '$kayttotarkoitus'
              AND liitos           = 'tuote'
              AND filename        != ''
              AND filetype         = '$filetype'";
    $result = pupe_query($query);
  }
}

if ($tee == 'LISTAA') {

  //* Tämä skripti käyttää slave-tietokantapalvelinta *//
  $useslave = 1;

  require 'inc/connect.inc';

  // näitä käytetään queryssä
  $sel_osasto   = "";
  $sel_tuoteryhma = "";
  $sel_tuotemerkki = "";
  $sel_kayttotarkoitus = "";
  $sel_selite = "";

  $lisa  = "";
  $orderlisa = "";

  if ($osasto != '') {
    $lisa .= " and tuote.osasto in ('".str_replace(',', '\',\'', $osasto)."') ";
  }
  elseif (count($mul_osasto) > 0) {
    $sel_osasto = "('".str_replace(',', '\',\'', implode(",", $mul_osasto))."')";
    $lisa .= " and tuote.osasto in $sel_osasto ";
  }

  if ($try != '') {
    $lisa .= " and tuote.try in ('".str_replace(',', '\',\'', $try)."') ";
  }
  elseif (count($mul_try) > 0) {
    $sel_tuoteryhma = "('".str_replace(',', '\',\'', implode(",", $mul_try))."')";
    $lisa .= " and tuote.try in $sel_tuoteryhma ";
  }

  if ($tmr != '') {
    $lisa .= " and tuote.tuotemerkki in ('".str_replace(',', '\',\'', $tmr)."') ";
  }
  elseif (count($mul_tmr) > 0) {
    $sel_tuotemerkki = "('".str_replace(',', '\',\'', implode(",", $mul_tmr))."')";
    $lisa .= " and tuote.tuotemerkki in $sel_tuotemerkki ";
  }

  if ($kayt != '') {
    $lisa .= " and liitetiedostot.kayttotarkoitus in ('".str_replace(',', '\',\'', $kayt)."') ";
  }
  elseif (count($mul_kay) > 0) {
    $sel_kayttotarkoitus = "('".str_replace(',', '\',\'', implode(",", $mul_kay))."')";
    $lisa .= " and liitetiedostot.kayttotarkoitus in $sel_kayttotarkoitus ";
  }

  if ($sel != '') {
    if ($sel == 'tyhja') {
      $lisa .= " and liitetiedostot.selite = '' ";
    }
    elseif ($sel == 'ei_tyhja') {
      $lisa .= " and liitetiedostot.selite != '' ";
    }
  }
  elseif (count($mul_sel) > 0 and count($mul_sel) < 2) {
    $sel_selite = "('".str_replace(',', '\',\'', implode(",", $mul_sel))."')";
    if (in_array('ei_tyhja', $mul_sel)) {
      $lisa .= " and liitetiedostot.selite != '' ";
    }
    else {
      $lisa .= " and liitetiedostot.selite = '' ";
    }
  }

  if ($status != '') {
    $lisa .= " and tuote.status in ('".str_replace(',', '\',\'', $status)."') ";
  }
  elseif (count($mul_sta) > 0) {
    $sel_status = "('A','T','".str_replace(',', '\',\'', implode(",", $mul_sta))."')";
    $lisa .= " and tuote.status in $sel_status ";
  }
  else {
    $lisa .= " and tuote.status in ('A','T') ";
  }

  if ($korkeus == 'on') {
    $mul_siz[] = 'korkeus';
  }

  if ($leveys == 'on') {
    $mul_siz[] = 'leveys';
  }

  if (count($mul_ext) > 0) {
    $lisa .= " and (";
    foreach ($mul_ext as $file_ext) {
      $lisa .= " liitetiedostot.filename like '%.$file_ext' or";
    }
    $lisa = substr($lisa, 0, -2); // vika "or" pois
    $lisa .= ")";
  }
  //      else {
  //        $lisa .= "  and liitetiedostot.filetype like 'image/%' ";
  //      }

  if ($verkkokauppa_tuotteet ==  "vain") {
    $lisa .= "AND tuote.status != 'P'
              AND tuote.tuotetyyppi NOT in ('A','B')
              AND tuote.tuoteno != ''
              AND tuote.nakyvyys != ''";
  }

  if ($verkkokauppa_tuotteet ==  "ei") {
    $lisa .= "AND tuote.nakyvyys = ''";
  }

  if ($tuoteno != '') {
    $tuoteno = mysql_real_escape_string(trim($tuoteno));
    $lisa .= " and tuote.tuoteno like '$tuoteno%' ";
  }

  $orderlisa = "tuote.osasto, tuote.try, tuote.tuoteno";

  // haetaan halutut tuotteet
  $query  = "SELECT
             if(tuote.try is not null, tuote.try, 0) try,
             if(tuote.osasto is not null, tuote.osasto, 0) osasto,
             tuote.tuoteno,
             tuote.tuotemerkki,
             tuote.nimitys,
             tuote.tunnus,
             tuote.status,
             liitetiedostot.tunnus ltiedtunnus,
             liitetiedostot.liitostunnus ltunnus,
             liitetiedostot.filename,
             liitetiedostot.filetype,
             liitetiedostot.kayttotarkoitus,
             liitetiedostot.tunnus id,
             liitetiedostot.image_height korkeus,
             liitetiedostot.image_width leveys,
             liitetiedostot.selite,
             liitetiedostot.liitos
             FROM tuote
             INNER JOIN liitetiedostot ON (liitetiedostot.yhtio = tuote.yhtio
              AND liitetiedostot.liitos        = 'tuote'
              AND liitetiedostot.liitostunnus  = tuote.tunnus
              AND liitetiedostot.filename     != '')
             WHERE tuote.yhtio                 = '{$kukarow["yhtio"]}'
             $lisa
             ORDER BY $orderlisa";
  $result = pupe_query($query);

  // scripti balloonien tekemiseen
  js_popup();

  $tuotekuvia_count = mysql_num_rows($result);

  if ($tuotekuvia_count == 0) {
    echo "<font class='error'>";
    echo t("Haulla ei löytynyt yhtään kuvaa.");
    echo "</font>";
  }
  else {
    if ($mul_exl == 'tallennetaan') {
      if (include 'Spreadsheet/Excel/Writer.php') {

        //keksitään failille joku varmasti uniikki nimi:
        list($usec, $sec) = explode(' ', microtime());
        mt_srand((float) $sec + ((float) $usec * 100000));
        $excelnimi = md5(uniqid(mt_rand(), true)).".xls";

        $workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
        $workbook->setVersion(8);
        $worksheet =& $workbook->addWorksheet('Sheet 1');

        $format_bold =& $workbook->addFormat();
        $format_bold->setBold();

        $excelrivi = 0;
      }
    }

    $laskuri = 0;

    if ($mul_exl != 'tallennetaan') {
      $onsubmit = "onSubmit='return verify();'";
    }
    else {
      $onsubmit = '';
    }

    echo "<form method='post' action='yllapito_tuotekuvat.php' {$onsubmit}>";
    echo "<table>";

    echo "<tr>";
    echo "<td valign='top' align='left' colspan='10' class='back'>";
    echo t('Löytyi yhteensä'), " {$tuotekuvia_count} ", t('riviä');
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>", t('Tunnus'), "</th>";
    echo "<th>", t('Tuoteno'), "</th>";
    echo "<th>", t('Nimitys'), "</th>";
    echo "<th>", t('Osasto'), "</th>";
    echo "<th>", t('Tuoteryhma'), "</th>";
    echo "<th>", t('Tuotemerkki'), "</th>";
    if (count($mul_sta) > 0 or $status != '') {
      echo "<th>", t('Status'), "</th>";
    }
    echo "<th>", t('Liitostunnus'), "</th>";
    echo "<th>", t('Tiedostonnimi'), "</th>";
    if (count($mul_siz) > 0) {
      if (in_array('korkeus', $mul_siz)) {
        echo "<th>", t('Korkeus'), "</th>";
      }
      if (in_array('leveys', $mul_siz)) {
        echo "<th>", t('Leveys'), "</th>";
      }
    }
    echo "<th>", t('Käyttötarkoitus'), "</th>";
    echo "<th>", t('Selite'), "</th>";
    if ($mul_exl != 'tallennetaan') {
      echo "<th>", t('Ruksaa'), "<br />".t('kaikki')." <input type='checkbox' name='mul_del' onclick='toggleAll(this);'></th>";
    }
    echo "</tr>";

    if ($limit != '') {
      if ($limit > $tuotekuvia_count) {
        $i = ($tuotekuvia_count / 50);
        $limit = $limit - ($i * 50);
        mysql_data_seek($result, $limit);
      }
      else {
        mysql_data_seek($result, $limit);
      }
    }

    // Exceliä käytetään sisäänlue datassa joten on laitettava sarakkeet sen mukaisesti (kaikkea ei siis saada laittaa mukaan vaikka mieli tekisi)
    if (isset($workbook) and $mul_exl == 'tallennetaan') {
      $excelsarake = 0;

      $worksheet->writeString($excelrivi, $excelsarake, t("Tuoteno"),       $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, t("Liitostunnus"),     $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, t("Liitos"),         $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, t("Filename"),       $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, t("Kayttotarkoitus"),     $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, t("Selite"),     $format_bold);
      $excelsarake++;
      $excelrivi++;
      $excelsarake = 0;
    }
    while ($row = mysql_fetch_array($result)) {

      echo "<tr class='aktiivi'>";
      echo "<td valign='top'>", $row['tunnus'], "</td>";
      echo "<td valign='top'>", $row['tuoteno'], "</td>";

      if (isset($workbook) and $mul_exl == 'tallennetaan') {
        $worksheet->writeString($excelrivi, $excelsarake, $row['tuoteno'],     $format_bold);
        $excelsarake++;
      }

      // tehdään pop-up divi jos keikalla on kommentti...
      if ($row['filename'] != '') {
        if ((strtolower($row['kayttotarkoitus']) == 'tk' and $nayta_tk != 'naytetaan') or (strtolower($row['kayttotarkoitus']) == 'hr' and $nayta_hr != 'naytetaan') or (strtolower($row['kayttotarkoitus']) == 'th' and $nayta_th != 'naytetaan') or (strtolower($row['kayttotarkoitus']) == 'mu')) {
          echo "<td valign='top'>", $row['nimitys'], "</td>";
        }
        else {
          echo "<div id='div_", $row['tunnus'], "_", $row['kayttotarkoitus'], "' class='popup' style='width: ", $row['leveys'], "px; height: ", $row['korkeus'], "px;'>";
          echo "<img src='view.php?id=", $row['id'], "' height='", $row['korkeus'], "' width='", $row['leveys'], "'>";
          echo "</div>";
          echo "<td valign='top' class='tooltip' id='", $row['tunnus'], "_", $row['kayttotarkoitus'], "'>", $row['nimitys'], "</td>";
        }
      }
      else {
        echo "<td valign='top'>", $row['nimitys'], "</td>";
      }

      echo "<td valign='top'>", $row['osasto'], "</td>";
      echo "<td valign='top'>", $row['try'], "</td>";
      echo "<td valign='top'>", $row['tuotemerkki'], "</td>";
      if (count($mul_sta) > 0 or $status != '') {
        echo "<td valign='top'>", $row['status'], "</td>";
      }
      echo "<td valign='top'>", $row['ltunnus'], "</td>";
      echo "<td valign='top'>", $row['filename'], "</td>";

      if (isset($workbook) and $mul_exl == 'tallennetaan') {
        $worksheet->writeString($excelrivi, $excelsarake, $row['ltunnus'],     $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, $row['liitos'],     $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, $row['filename'],     $format_bold);
        $excelsarake++;
      }

      if (count($mul_siz) > 0) {
        if (in_array('korkeus', $mul_siz)) {
          echo "<td valign='top' align='right'>", $row['korkeus'], " px</td>";
        }

        if (in_array('leveys', $mul_siz)) {
          echo "<td valign='top' align='right'>", $row['leveys'], " px</td>";
        }
      }

      echo "<td valign='top' align='right'>", $row['kayttotarkoitus'], "</td>";
      echo "<td valign='top'>", $row['selite'], "</td>";

      if ($mul_exl != 'tallennetaan') {
        echo "<td valign='top'><input type='checkbox' name='mul_del[]' value='", $row['tunnus'], "_", $row['ltunnus'], "_", $row['ltiedtunnus'], "_", $row['kayttotarkoitus'], "_", $row['filetype'], "'></td>";
      }

      if (isset($workbook) and $mul_exl == 'tallennetaan') {
        $worksheet->writeString($excelrivi, $excelsarake, $row['kayttotarkoitus'],     $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, $row['selite'],     $format_bold);
        $excelsarake = 0;
        $excelrivi++;
      }

      echo "</tr>";

      $laskuri++;

      if ($laskuri == 50 and $mul_siv == '' and $mul_exl == '') {
        break;
      }
    }

    $colspan = '9';

    if (count($mul_siz) > 0 and in_array('korkeus', $mul_siz)) {
      $korkeus = 'on';
      $colspan++;
    }

    if (count($mul_siz) > 0 and in_array('leveys', $mul_siz)) {
      $leveys = 'on';
      $colspan++;
    }

    echo "<tr>";
    echo "<td valign='top' align='left' colspan='", $colspan, "' class='back'>";
    echo t('Löytyi yhteensä'), " {$tuotekuvia_count} ", t('riviä');
    echo "</td>";

    echo "<td valign='top' align='left' class='back'>";

    if (isset($workbook) and $mul_exl == 'tallennetaan') {
      // We need to explicitly close the workbook
      $workbook->close();

      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='Tuotekuvat.xls'>";
      echo "<input type='hidden' name='tmpfilenimi' value='", $excelnimi, "'>";
      echo "<input type='submit' value='", t("Tallenna tulos"), "'>";
    }
    else {
      echo "&nbsp;";
    }

    echo "</td>";

    if ($mul_exl != 'tallennetaan') {
      echo "<td valign='top' align='center' colspan='1' class='back'>";
      echo "<input type='submit' value='", t('Poista'), "'>";
      echo "</td>";
    }
    echo "</tr>";

    if ($mul_siv == '' and $mul_exl == '') {
      if ($sel_osasto != '') {
        $osasto = $sel_osasto;
        $osasto = str_replace('(\'', '', $osasto);
        $osasto = str_replace('\')', '', $osasto);
        $osasto = str_replace('\',\'', ',', $osasto);
      }

      if ($sel_tuoteryhma != '') {
        $try = $sel_tuoteryhma;
        $try = str_replace('(\'', '', $try);
        $try = str_replace('\')', '', $try);
        $try = str_replace('\',\'', ',', $try);
      }

      if ($sel_tuotemerkki != '') {
        $tmr = $sel_tuotemerkki;
        $tmr = str_replace('(\'', '', $tmr);
        $tmr = str_replace('\')', '', $tmr);
        $tmr = str_replace('\',\'', ',', $tmr);
      }

      if ($sel_kayttotarkoitus != '') {
        $kayt = $sel_kayttotarkoitus;
        $kayt = str_replace('(\'', '', $kayt);
        $kayt = str_replace('\')', '', $kayt);
        $kayt = str_replace('\',\'', ',', $kayt);
      }

      if ($sel_selite != '') {
        $sel = $sel_selite;
        $sel = str_replace('(\'', '', $sel);
        $sel = str_replace('\')', '', $sel);
        $sel = str_replace('\',\'', ',', $sel);
      }

      if ($sel_status != '') {
        $status = $sel_status;
        $status = str_replace('(\'', '', $status);
        $status = str_replace('\')', '', $status);
        $status = str_replace('\',\'', ',', $status);
      }

      mysql_data_seek($result, 0);
      $i = ceil((float) ($tuotekuvia_count / 50));
      $limit = 0;
      echo "<tr>";
      echo "<td valign='top' align='center' colspan='10' class='back'>";

      $limitx = (($sivu - 1) * 50) - 50;
      if ($limitx >= 0) {
        $alkuun = "<a href='$PHP_SELF?tee=LISTAA&sivu=1&limit=0&tuoteno=".urlencode($tuoteno)."&osasto=$osasto&try=$try&tmr=$tmr&kayt=$kayt&sel=$sel&nayta_tk=$nayta_tk&nayta_hr=$nayta_hr&nayta_th=$nayta_th&korkeus=$korkeus&leveys=$leveys&status=$status'>";
        echo $alkuun, "&lt;&lt; ", t('Ensimmäinen'), "</a>&nbsp;&nbsp;";

        $y = $sivu - 1;
        $edellinen = "<a href='$PHP_SELF?tee=LISTAA&sivu=".(int)$y."&limit=$limitx&tuoteno=".urlencode($tuoteno)."&osasto=$osasto&try=$try&tmr=$tmr&kayt=$kayt&sel=$sel&nayta_tk=$nayta_tk&nayta_hr=$nayta_hr&nayta_th=$nayta_th&korkeus=$korkeus&leveys=$leveys&status=$status'>";
        echo $edellinen, "&lt;&lt; ", t('Edellinen'), "</a>&nbsp;&nbsp;";
      }

      for ($y = 1; $y <= $i; $y++) {
        if ($sivu != '' and $sivu == (int)$y) {
          echo "<font style='font-weight: bold;'>", $y, "</font>";
          $edellinen = "ok";
          $seuraava = "ok";
        }
        elseif ($sivu == '' and (int)$y == '1') {
          echo "<font style='font-weight: bold;'>", $y, "</font>";
          $edellinen = "<a href='$PHP_SELF?tee=LISTAA&sivu=".(int)$y."&limit=$limit&tuoteno=".urlencode($tuoteno)."&osasto=$osasto&try=$try&tmr=$tmr&kayt=$kayt&sel=$sel&nayta_tk=$nayta_tk&nayta_hr=$nayta_hr&nayta_th=$nayta_th&korkeus=$korkeus&leveys=$leveys&status=$status'>";
          $seuraava = "ok";
        }
        else {
          if ($seuraava == "ok") {
            $seuraava = "<a href='$PHP_SELF?tee=LISTAA&sivu=".(int)$y."&limit=$limit&tuoteno=".urlencode($tuoteno)."&osasto=$osasto&try=$try&tmr=$tmr&kayt=$kayt&sel=$sel&nayta_tk=$nayta_tk&nayta_hr=$nayta_hr&nayta_th=$nayta_th&korkeus=$korkeus&leveys=$leveys&status=$status'>";
          }

          echo "<a href='$PHP_SELF?tee=LISTAA&sivu=".(int)$y."&limit=$limit&tuoteno=".urlencode($tuoteno)."&osasto=$osasto&try=$try&tmr=$tmr&kayt=$kayt&sel=$sel&nayta_tk=$nayta_tk&nayta_hr=$nayta_hr&nayta_th=$nayta_th&korkeus=$korkeus&leveys=$leveys&status=$status'>$y</a>";
        }
        $limit += 50;
        if (($y % '40') == 0) {
          echo "<br />";
        }
        else {
          echo "&nbsp;&nbsp;";
        }
      }
      if ($seuraava != 'ok' and $seuraava != '') {
        echo $seuraava, " ", t('Seuraava'), " &gt;&gt;</a>&nbsp;&nbsp;";

        $limit -= 50;
        $loppuun = "<a href='$PHP_SELF?tee=LISTAA&sivu=".(int)$i."&limit=$limit&tuoteno=".urlencode($tuoteno)."&osasto=$osasto&try=$try&tmr=$tmr&kayt=$kayt&sel=$sel&nayta_tk=$nayta_tk&nayta_hr=$nayta_hr&nayta_th=$nayta_th&korkeus=$korkeus&leveys=$leveys&status=$status'>";
        echo $loppuun, " ", t('Viimeinen'), " &gt;&gt;</a>";
      }
      echo "</td>";
      echo "</tr>";
    }

    echo "</table>";
    echo "</form>";
  }
}

require 'inc/footer.inc';
