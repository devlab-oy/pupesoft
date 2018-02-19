<?php

if ($_POST["tee"] == 'lataa_tiedosto') {
  $lataa_tiedosto = 1;
}

if ($_POST["kaunisnimi"] != '') {
  $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

// DataTables päälle
$pupe_DataTables = "kekkonen";

require 'inc/parametrit.inc';

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

// Liitetiedostot popup
if (isset($liite_popup_toiminto) and $liite_popup_toiminto == "AK") {
  liite_popup("AK", $tuotetunnus, $width, $height, $litety_id);
}
else {
  liite_popup("JS");
}

if (function_exists("js_popup")) {
  echo js_popup();
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
  echo "<div style='height:440px;overflow:auto;'>";
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
  echo "<div style='height:440px;overflow:auto;'>";
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
  echo "<div style='height:440px;overflow:auto;'>";
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

//############## Kemiallinen ominaisuus tuotteen avainsana
$query = "  SELECT distinct selite, selitetark FROM tuotteen_avainsanat WHERE yhtio='$kukarow[yhtio]' and laji = 'KEMOM' and kieli = '{$kukarow['kieli']}' ORDER BY selitetark";
$res2  = pupe_query($query);

// näytetäänkö tuloksissa kemiallinen ominaisuus vaikkei olisi mitään valittunakaan
$_kem = false;

if (mysql_num_rows($res2) > 0) {
  $_kem = true;
  echo "<td>";
  echo "<table>";
  echo "<tr>";
  echo "<td valign='top' class='back'>";

  if (mysql_num_rows($res2) > 11) {
    echo "<div style='height:440px;overflow:auto;'>";
  }

  echo "<table>";
  echo "<tr>";
  echo "<th colspan='2'>", t("Kemiallinen ominaisuus"), ":</th>";
  echo "</tr>";
  echo "<tr>";
  echo "<td><input type='checkbox' name='mul_kem' onclick='toggleAll(this);'></td><td nowrap>", t("Ruksaa kaikki"), "</td>";
  echo "</tr>";

  if (isset($kem) and $kem != '') {
    $mul_kem = explode(",", $kem);
  }

  while ($rivi = mysql_fetch_array($res2)) {
    $mul_check = '';
    if (count($mul_kem) > 0) {
      if (in_array($rivi['selite'], $mul_kem)) {
        $mul_check = 'CHECKED';
      }
    }

    echo "<tr><td><input type='checkbox' name='mul_kem[]' value='{$rivi['selite']}' {$mul_check}></td><td>{$rivi['selite']}</td></tr>";
  }

  echo "</table>";

  if (mysql_num_rows($res2) > 11) {
    echo "</div>";
  }

  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "</td>";
}

## haku
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

################ Haku liitetiedoston nimellä
echo "<table width='100%'>";
echo "<tr>";
echo "<th colspan='3'>", t("Liitetiedostohaku"), ":</th>";
echo "</tr>";
echo "<tr>";
echo "<td><input type='text' name='liitetiedosto' size='15' value='{$liitetiedosto}'></td>";
echo "</tr>";
echo "</table>";

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

$res = t_avainsana("LITETY");

while ($rows = mysql_fetch_assoc($res)) {

  $mul_check = '';
  if (count($mul_kay) > 0) {
    if (in_array($rows['selite'], $mul_kay)) {
      $mul_check = 'CHECKED';
    }
  }
  echo "<tr><td><input type='checkbox' name='mul_kay[]' value='{$rows['selite']}' {$mul_check}></td><td nowrap>{$rows['selitetark']}</td></tr>";
}

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
    list($tunnus, $ltiedtunnus, $kayttotarkoitus, $filetype) = explode('_', $val);

    $tunnus = mysql_real_escape_string($tunnus);
    $ltiedtunnus = mysql_real_escape_string($ltiedtunnus);
    $kayttotarkoitus = mysql_real_escape_string($kayttotarkoitus);
    $filetype = mysql_real_escape_string($filetype);

    echo "$tunnus ($filetype  $kayttotarkoitus)<br />";

    $query = "DELETE
              FROM liitetiedostot
              WHERE yhtio          = '$kukarow[yhtio]'
              AND tunnus           = '$ltiedtunnus'
              AND liitostunnus     = '$tunnus'
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
  $avainsana_lisa = "";
  $avainsana_selectlisa = "";

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

  if ($kem != '') {
    $avainsana_lisa = " JOIN tuotteen_avainsanat ON (tuotteen_avainsanat.yhtio = tuote.yhtio and tuotteen_avainsanat.laji = 'KEMOM' and tuotteen_avainsanat.tuoteno = tuote.tuoteno and tuotteen_avainsanat.kieli = '{$kukarow['kieli']}' and tuotteen_avainsanat.selite in ('".str_replace(',', '\',\'', $kem)."')) ";
    $avainsana_selectlisa = ", tuotteen_avainsanat.selite AS kem_selite";
  }
  elseif (count($mul_kem) > 0) {
    $sel_kem = "('".str_replace(',', '\',\'', implode(",", $mul_kem))."')";
    $avainsana_lisa = " JOIN tuotteen_avainsanat ON (tuotteen_avainsanat.yhtio = tuote.yhtio and tuotteen_avainsanat.laji = 'KEMOM' and tuotteen_avainsanat.tuoteno = tuote.tuoteno and tuotteen_avainsanat.kieli = '{$kukarow['kieli']}' and tuotteen_avainsanat.selite in $sel_kem) ";
    $avainsana_selectlisa = ", tuotteen_avainsanat.selite AS kem_selite";
  }
  elseif ($_kem) {
    $avainsana_lisa = "LEFT JOIN tuotteen_avainsanat ON (tuotteen_avainsanat.yhtio = tuote.yhtio and tuotteen_avainsanat.laji = 'KEMOM' and tuotteen_avainsanat.tuoteno = tuote.tuoteno and tuotteen_avainsanat.kieli = '{$kukarow['kieli']}')";
    $avainsana_selectlisa = ", tuotteen_avainsanat.selite AS kem_selite";
  }

  if ($liitetiedosto != '' and ($lisa != '' or ($kem != '' or count($mul_kem) > 0))) {
    $liitetiedosto = mysql_real_escape_string(trim($liitetiedosto));
    $lisa .= " and liitetiedostot.filename like '%{$liitetiedosto}%' ";
  }
  elseif ($liitetiedosto != '') {
    echo "<font class='error'>";

    if ($_kem) {
      echo t("Liitetiedostohaku yksinään on liian hidas. Lisää rajauksia joko osastoon, try, tuotemerkkiin  tai / ja kemialliseen ominaisuuteen.");
    }
    else {
      echo t("Liitetiedostohaku yksinään on liian hidas. Lisää rajauksia joko osastoon, try tai / ja tuotemerkkiin.");
    }

    echo "</font>";
    die;
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
             liitetiedostot.filename,
             liitetiedostot.filetype,
             liitetiedostot.kayttotarkoitus,
             liitetiedostot.tunnus id,
             liitetiedostot.image_height korkeus,
             liitetiedostot.image_width leveys,
             liitetiedostot.selite,
             liitetiedostot.liitos,
             if(liitetiedostot.muutospvm = '0000-00-00 00:00:00', liitetiedostot.luontiaika, liitetiedostot.muutospvm) muutospvm
             {$avainsana_selectlisa}
             FROM tuote
             INNER JOIN liitetiedostot ON (liitetiedostot.yhtio = tuote.yhtio
              AND liitetiedostot.liitos        = 'tuote'
              AND liitetiedostot.liitostunnus  = tuote.tunnus
              AND liitetiedostot.filename     != '')
             {$avainsana_lisa}
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

    echo t('Löytyi yhteensä'), " {$tuotekuvia_count} ", t('riviä') ."<br>";

    // lasketaan datatablesille sarakkeet
    $_sarakkeet = 11;
    $_status_sarake = "";
    $_status_search = "";
    $_korkeus_sarake = "";
    $_korkeus_search = "";
    $_leveys_sarake = "";
    $_leveys_search = "";
    $_ruksaa_sarake = "";
    $_ruksaa_search = "";

    // muutama sarake mahdollisesti lisää
    if (count($mul_sta) > 0 or $status != '') {
      $_status_sarake = "<th>". t('Status'). "</th>";
      $_status_search = "<td><input type='text' class='search_field' name='search_status'></td>";
      $_sarakkeet++;
    }
    if ($_kem) {
      $_kem_sarake = "<th>". t('Kemiallinen ominaisuus'). "</th>";
      $_kem_search = "<td><input type='text' class='search_field' name='search_kem'></td>";
      $_sarakkeet++;
    }
    if (count($mul_siz) > 0) {
      if (in_array('korkeus', $mul_siz)) {
        $_korkeus_sarake = "<th>". t('Korkeus'). "</th>";
        $_korkeus_search = "<td><input type='text' class='search_field' name='search_korkeus'></td>";
        $_sarakkeet++;
      }
      if (in_array('leveys', $mul_siz)) {
        $_leveys_sarake = "<th>". t('Leveys'). "</th>";
        $_leveys_search = "<td><input type='text' class='search_field' name='search_leveys'></td>";
        $_sarakkeet++;
      }
    }
    if ($mul_exl != 'tallennetaan') {
      $_ruksaa_sarake = "<th>". t('Ruksaa'). "</th>";
      $_ruksaa_search = "<td></td>";
      $_sarakkeet++;
    }

    pupe_DataTables(array(array($pupe_DataTables, $_sarakkeet, $_sarakkeet, true, false)));

    echo "<form method='post' action='yllapito_tuotekuvat.php' {$onsubmit}>";
    echo "<br>".t("Valitse kaikki listatut liitteet").": <input type='checkbox' name='mul_del' onclick='toggleAll(this);'><br>";
    echo "<table class='display dataTable' id='$pupe_DataTables'><thead>";
    echo "<tr>";
    echo "<th>", t('Tunnus'), "</th>";
    echo "<th>", t('Tuoteno'), "</th>";
    echo "<th>", t('Nimitys'), "</th>";
    echo "<th>", t('Osasto'), "</th>";
    echo "<th>", t('Tuoteryhma'), "</th>";
    echo "<th>", t('Tuotemerkki'), "</th>";
    echo $_status_sarake;
    echo "<th>", t('Tiedostonnimi'), "</th>";
    echo $_korkeus_sarake;
    echo $_leveys_sarake;
    echo "<th>", t('Käyttötarkoitus'), "</th>";
    echo $_kem_sarake;
    echo "<th>", t('Muutospäivä'), "</th>";
    echo "<th>", t('Selite'), "</th>";
    echo $_ruksaa_sarake;
    echo "<th></th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><input type='text' class='search_field' name='search_tunnus'></td>";
    echo "<td><input type='text' class='search_field' name='search_tuoteno'></td>";
    echo "<td><input type='text' class='search_field' name='search_nimitys'></td>";
    echo "<td><input type='text' class='search_field' name='search_osasto'></td>";
    echo "<td><input type='text' class='search_field' name='search_tuoteryhma'></td>";
    echo "<td><input type='text' class='search_field' name='search_tuotemerkki'></td>";
    echo $_status_search;
    echo "<td><input type='text' class='search_field' name='search_tiedostonimi'></td>";
    echo $_korkeus_search;
    echo $_leveys_search;
    echo "<td><input type='text' class='search_field' name='search_kayttotarkoitus'></td>";
    echo $_kem_search;
    echo "<td><input type='text' class='search_field' name='search_muutospaiva'></td>";
    echo "<td><input type='text' class='search_field' name='search_selite'></td>";
    echo $_ruksaa_search;
    echo "<td></td>";
    echo "</tr>";

    echo "</thead>";
    echo "<tbody>";

    // Exceliä käytetään sisäänlue datassa joten on laitettava sarakkeet sen mukaisesti (kaikkea ei siis saada laittaa mukaan vaikka mieli tekisi)
    if (isset($workbook) and $mul_exl == 'tallennetaan') {
      $excelsarake = 0;

      $worksheet->writeString($excelrivi, $excelsarake, t("Tuoteno"),       $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, t("Liitos"),        $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, t("Filename"),      $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, t("Kayttotarkoitus"),$format_bold);
      $excelsarake++;
      if ($_kem) {
        $worksheet->writeString($excelrivi, $excelsarake, t("Kemiallinen ominaisuus"), $format_bold);
        $excelsarake++;
      }
      $worksheet->writeString($excelrivi, $excelsarake, t("Muutospäivä"),   $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, t("Selite"),        $format_bold);
      $excelsarake++;
      $excelrivi++;
      $excelsarake = 0;
    }
    while ($row = mysql_fetch_array($result)) {

      $row['muutospvm'] = substr($row['muutospvm'], 0, 10);

      echo "<tr class='aktiivi'>";
      echo "<td valign='top'>", $row['tunnus'], "</td>";
      echo "<td valign='top'>", $row['tuoteno'], "</td>";

      if (isset($workbook) and $mul_exl == 'tallennetaan') {
        $worksheet->writeString($excelrivi, $excelsarake, $row['tuoteno'],  $format_bold);
        $excelsarake++;
      }

      // tehdään pop-up divi jos keikalla on kommentti...
      if ($row['filename'] != '') {
        if ((strtolower($row['kayttotarkoitus']) == 'tk' and $nayta_tk != 'naytetaan') or (strtolower($row['kayttotarkoitus']) == 'hr' and $nayta_hr != 'naytetaan') or (strtolower($row['kayttotarkoitus']) == 'th' and $nayta_th != 'naytetaan') or (strtolower($row['kayttotarkoitus']) == 'mu')) {
          echo "<td valign='top'>", $row['nimitys'], "</td>";
        }
        else {
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
      echo "<td valign='top'>", $row['filename'], "</td>";

      if (isset($workbook) and $mul_exl == 'tallennetaan') {
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
      if ($_kem) {
        echo "<td valign='top'>", $row['kem_selite'], "</td>";
      }
      echo "<td valign='top' align='right'>", $row['muutospvm'], "</td>";
      echo "<td valign='top'>", $row['selite'], "</td>";

      if ($mul_exl != 'tallennetaan') {
        echo "<td valign='top'><input type='checkbox' name='mul_del[]' value='", $row['tunnus'], "_", $row['ltiedtunnus'], "_", $row['kayttotarkoitus'], "_", $row['filetype'], "'></td>";
      }

      // Onko liitetiedostoja
      $liitteet = liite_popup("TN", $row["tunnus"], "", "", $row['id']);

      if ($liitteet != "") {
        echo "<td valign='top'>", $liitteet, "</td>";
      }

      if (isset($workbook) and $mul_exl == 'tallennetaan') {
        $worksheet->writeString($excelrivi, $excelsarake, $row['kayttotarkoitus'],     $format_bold);
        $excelsarake++;
        if ($_kem) {
          $worksheet->writeString($excelrivi, $excelsarake, $row['kem_selite'],     $format_bold);
          $excelsarake++;
        }
        $worksheet->writeString($excelrivi, $excelsarake, date("Y-m-d", $row['muutospvm']),     $format_bold);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, $row['selite'],     $format_bold);
        $excelsarake = 0;
        $excelrivi++;
      }

      echo "</tr>";

      $laskuri++;
    }
    echo "</tbody></table>";
    $colspan = '9';

    if (count($mul_siz) > 0 and in_array('korkeus', $mul_siz)) {
      $korkeus = 'on';
      $colspan++;
    }

    if (count($mul_siz) > 0 and in_array('leveys', $mul_siz)) {
      $leveys = 'on';
      $colspan++;
    }


    echo "<table><tr>";
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

    echo "</table>";
    echo "</form>";
  }
}

require 'inc/footer.inc';
