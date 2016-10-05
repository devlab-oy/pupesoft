<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto=1;
  }
  if ($_POST["kaunisnimi"] != '') {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

require "inc/parametrit.inc";

if ($tee == "NAYTATILAUS") {
  readfile($filenimi);
  exit;
}

if (strtolower($toim) == 'oletusvarasto') {

  if ($kukarow['oletus_varasto'] == 0) {
    echo "<font class='error'>", t("Oletusvarastoa ei ole asetettu käyttäjälle"), ".</font><br />";

    require "inc/footer.inc";
    exit;
  }

  $oletusvarasto_chk = $kukarow['oletus_varasto'];
}
else {
  $oletusvarasto_chk = 0;
}

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

$status = isset($status) ? $status : '';

echo "<font class='head'>", t("Tulosta inventointilista"), "</font><hr>";

echo "<form name='inve' method='post' enctype='multipart/form-data' autocomplete='off'>";
echo "<input type='hidden' name='toim' value='$toim'>";
echo "<input type='hidden' name='tee' value='TULOSTA'>";

// Monivalintalaatikot (osasto, try tuotemerkki...)
// Määritellään mitkä latikot halutaan mukaan
echo "<br><table>";
echo "<tr><th>", t("Rajaa tuotteita"), "</th><td nowrap>";

// selitetark   = näytettävät monivalintalaatikot, jos tyhjää, otetaan oletus alhaalla
// selitetark_2 = mitkä näytettävistä monivalintalaatikoista on normaaleja alasvetovalikoita
$query = "SELECT selite, selitetark, REPLACE(selitetark_2, ', ', ',') selitetark_2
          FROM avainsana
          WHERE yhtio  = '$kukarow[yhtio]'
          AND laji     = 'INVLISTA_OSTRY'
          AND selite  != ''";
$avainsana_result = pupe_query($query);
$avainsana_row = mysql_fetch_assoc($avainsana_result);

// Monivalintalaatikot (osasto, try tuotemerkki...)
// Määritellään mitkä latikot halutaan mukaan
if (trim($avainsana_row['selite']) != '') {

  $monivalintalaatikot = explode(",", $avainsana_row['selite']);

  if (trim($avainsana_row['selitetark'] != '')) {
    $monivalintalaatikot_normaali = explode(",", $avainsana_row['selitetark']);
  }
  else {
    $monivalintalaatikot_normaali = array();
  }
}
else {
  // Oletus
  $monivalintalaatikot = array("OSASTO", "TRY");
  $monivalintalaatikot_normaali = array();
}

require "tilauskasittely/monivalintalaatikot.inc";

echo "</td></tr>";
echo "<tr><th>".t("Listaa vain myydyimmät:")."</th>";
echo "<td><input type='text' size='6' name='top'> ".t("tuotetta");
echo "</td></tr>";

echo "<tr><th>".t("Näytä eniten varastonarvoon vaikuttavaa")."</th>";
echo "<td><input type='text' size='6' name='varastoonvaikutus'> ".t("tuotetta");
echo "</td></tr>";

echo "<tr><th>".t("Näytä vain tuotteet joiden varaston arvo on yli")."</th>";
echo "<td><input type='text' size='9' name='varastonarvo'> ".$yhtiorow["valkoodi"]."</td>";
echo "</tr>";

echo "<tr><td class='back'>", t("ja/tai"), "...</td></tr>";

echo "<tr><th>".t("Anna alkuvarastopaikka:")."</th>";
echo "<td>", hyllyalue("ahyllyalue", '');
echo "  <input type='text' size='6' maxlength='5' name='ahyllynro'>
    <input type='text' size='6' maxlength='5' name='ahyllyvali'>
      <input type='text' size='6' maxlength='5' name='ahyllytaso'>";
echo "</td></tr>";

echo "<tr><th>".t("ja loppuvarastopaikka:")."</th>";
echo "<td>", hyllyalue("lhyllyalue", '');
echo "  <input type='text' size='6' maxlength='5' name='lhyllynro'>
    <input type='text' size='6' maxlength='5' name='lhyllyvali'>
    <input type='text' size='6' maxlength='5' name='lhyllytaso'>";
echo "</td></tr>";

$query = "SELECT *
          FROM varastopaikat
          WHERE yhtio  = '{$kukarow['yhtio']}'
          AND tyyppi  != 'P'
          ORDER BY nimitys";
$kresult = pupe_query($query);

echo "<tr><th>", t("Varastot"), "</th>";
echo "<td><select name='varasto'>";
echo "<option value=''>", t("Valitse"), "</option>";

while ($krow = mysql_fetch_assoc($kresult)) {
  $sel = (!empty($oletusvarasto_chk) and $oletusvarasto_chk == $krow['tunnus']) ? "selected" : "";

  echo "<option value='{$krow['tunnus']}' {$sel}>{$krow['nimitys']}</option>";
}

echo "</select></td></tr>";

if ($yhtiorow['kerayserat'] != '') {
  // Haetaan keraysvyohykkeet
  $query = "SELECT tunnus, nimitys
            FROM keraysvyohyke
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND nimitys != ''";
  $kresult = pupe_query($query);

  // Keräysvyöhyke dropdown
  echo "<tr><th>", t("Keräysvyöhyke"), "</th>";
  echo "<td><select name='keraysvyohyke'>";
  echo "<option value=''>", t("Valitse"), "</option>";

  while ($krow = mysql_fetch_assoc($kresult)) {
    echo "<option value='{$krow['tunnus']}'>{$krow['nimitys']}</option>";
  }

  echo "</select></td></tr>";
}

echo "<tr><td class='back'>", t("ja/tai"), "...</td></tr>";

echo "<tr><th>".t("Anna toimittajanumero(ytunnus):")."</th>";
echo "<td><input type='text' size='25' name='toimittaja'>";
echo "</td></tr>";

echo "<tr><td class='back'>", t("ja/tai"), "...</td></tr>";

echo "<tr><th>", t("Valitse tuotemerkki:"), "</th>";

$query = "SELECT distinct tuotemerkki
          FROM tuote use index (yhtio_tuotemerkki)
          WHERE yhtio      = '{$kukarow['yhtio']}'
          {$poislisa}
          and tuotemerkki != ''
          ORDER BY tuotemerkki";
$sresult = pupe_query($query);

echo "<td><select name='tuotemerkki'>";
echo "<option value=''>", t("Ei valintaa"), "</option>";

while ($srow = mysql_fetch_assoc($sresult)) {
  echo "<option value='{$srow['tuotemerkki']}'>{$srow['tuotemerkki']}</option>";
}

echo "</td></tr>";

echo "<tr><td class='back'><br /></td></tr>";

$sel = array((isset($raportti) ? $raportti : '') => "SELECTED");
echo "<tr>";
echo "<td class='back' colspan='2'>".t("tai inventoi raportin avulla")."...</th></tr>";
echo "<tr><th>".t("Valitse raportti")."</th>";
echo "<td>
    <select name='raportti'>
      <option value=''>".t("Valitse")."</option>
      <option value='vaarat' ".$sel['vaarat'].">".t("Väärät saldot")."</option>
      <option value='loppuneet' ".$sel['loppuneet'].">".t("Loppuneet tuotteet")."</option>
      <option value='negatiiviset' ".$sel['negatiiviset'].">".t("Kaikki miinus-saldolliset")."</option>
      <option value='tapahtumia' ".$sel['tapahtumia'].">".t("Tuotteet joilla tulo, myynti, valmistus tai varastonsiirtotapahtumia")."</option>
    </select>";
echo "</td></tr>";

if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

if (!isset($kkl)) $kkl = date("m");
if (!isset($vvl)) $vvl = date("Y");
if (!isset($ppl)) $ppl = date("d");

echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>";
echo "<td><input type='text' name='ppa' value='$ppa' size='3'>
  <input type='text' name='kka' value='$kka' size='3'>
    <input type='text' name='vva' value='$vva' size='5'></td>";
echo "</tr>";
echo "<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>";
echo "<td>  <input type='text' name='ppl' value='$ppl' size='3'>
  <input type='text' name='kkl' value='$kkl' size='3'>
  <input type='text' name='vvl' value='$vvl' size='5'>";

echo "<tr><td class='back'><br></td></tr>";
echo "<tr><td class='back' colspan='2'>", t("Valitse ehdoista"), "...</th></tr>";

echo "<tr><th>".t("Listaa tuotteet:")."</th>";

$sel1 = "";
$sel2 = "";

if ($arvomatikka == 'S') {
  $sel1 = "SELECTED";
}
elseif ($arvomatikka == 'N') {
  $sel2 = "SELECTED";
}

echo "<td><select name='arvomatikka'>";
echo "<option value=''>".t("Kaikki")."</option>";
echo "<option value='S' $sel1>".t("Saldolliset, ei negatiivisia")."</option>";
echo "<option value='N' $sel2>".t("Saldo ei ole nolla")."</option></select>";
echo "</td></tr>";

$sel1 = "";
$sel2 = "";

if ($naytasaldo == 'H') {
  $sel1 = "SELECTED";
}
elseif ($naytasaldo == 'S') {
  $sel2 = "SELECTED";
}

echo "<tr><th>".t("Tulosta hyllyssä oleva määrä:")."</th>";
echo "<td><select name='naytasaldo'>";
echo "<option value=''>".t("Ei näytetä määrää")."</option>";
echo "<option value='H' $sel1>".t("Hyllyssä oleva määrä")."</option>";
echo "<option value='S' $sel2>".t("Saldo")."</option></select>";
echo "</td></tr>";

echo "<tr><th>".t("Listaa myös tuotteet jotka ovat inventoitu kahden viikon sisällä:")."</th>
    <td><input type='checkbox' name='naytainvtuot' ".((isset($naytainvtuot) and $naytainvtuot!='') ? 'CHECKED' : '')."></td>
    </tr>";

if ($piilotaToim_tuoteno != "") {
  $checkPiilotaToim_tuoteno = "CHECKED";
}

echo "<tr><th>".t("Älä tulosta toimittajan tuotenumeroa listauksiin:")."</th>
  <td><input type='checkbox' name='piilotaToim_tuoteno' value='Y' $checkPiilotaToim_tuoteno></td>
  </tr>";

$invaste_tuotepaikat_result = t_avainsana("INVASTEPAIKKA");

if (mysql_num_rows($invaste_tuotepaikat_result) > 0) {

  echo "<tr>";
  echo "<th>", t("Ei huomioida inventointiaste-raportin hylkäämiä tuotepaikkoja"), "</th>";
  echo "<td>";
  echo "<input type='checkbox' name='ei_huomioida_tuotepaikkoja_avainsanoista[]' {$chk} /><br />";

  while ($invaste_tuotepaikat_row = mysql_fetch_assoc($invaste_tuotepaikat_result)) {
    echo "{$invaste_tuotepaikat_row['selite']}<br />";
  }

  echo "</td>";
  echo "</tr>";
}

echo "<tr>";
echo "<th>", t("Tee vain Excel-raportti"), "</th>";
echo "<td>";
echo "<input type='checkbox' name='ei_inventointi' /><br />";
echo "</td>";
echo "</tr>";

echo "<tr><th>".t("Listaa vain tuotteet joita ei ole inventoitu päivämääränä tai sen jälkeen:")."</th>
  <td><input type='text' name='ippa' value='$ippa' size='3'>
  <input type='text' name='ikka' value='$ikka' size='3'>
  <input type='text' name='ivva' value='$ivva' size='5'></td>
</tr>";

if ($yhtiorow['laaja_inventointilista'] != "") {
  if (!isset($vapaa_teksti)) $vapaa_teksti = "";

  echo "<tr>";
  echo "<th>", t("Vapaa teksti"), "</th>";
  echo "<td><textarea name='vapaa_teksti' rows='4'>{$vapaa_teksti}</textarea></td>";
  echo "</tr>";
}
else {
  echo "<input type='hidden' name='vapaa_teksti' value='' />";
}

$sel = $status == 'EI' ? 'selected' : '';

echo "<tr><th>", t("Tuotteen status:"), "</th>";
echo "<td>";
echo "<select name='status'>";
echo "<option value=''>", t("Kaikki tuotteet"), "</option>";
echo "<option value = 'EI' {$sel['EI']}>".t("Ei listata poistettuja tuotteita")."</option>";
echo product_status_options($status);
echo "</select>";
echo "</td>";
echo "</tr>";

if ($yhtiorow['kerayserat'] == 'K') {

  $query = "SELECT count(tunnus) AS cnt FROM varaston_hyllypaikat WHERE yhtio = '{$kukarow['yhtio']}'";
  $cnt_chk_res = pupe_query($query);
  $cnt_chk_row = mysql_fetch_assoc($cnt_chk_res);

  if ($cnt_chk_row['cnt'] > 0) {
    echo "<tr><th>", t("Reservipaikka"), "</th>";
    echo "<td><select name='reservipaikka'>";
    echo "<option value=''>", t("Valitse"), "</option>";
    echo "<option value='E'>", t("Ei"), "</option>";
    echo "<option value='K'>", t("Kyllä"), "</option>";
    echo "</select></td></tr>";
  }
}

echo "<tr><th>", t("Järjestä lista:"), "</th>";

$sel1 = "";
$sel2 = "";
$sel3 = "";

if ($jarjestys == 'tuoteno') {
  $sel2 = "SELECTED";
}
elseif ($jarjestys == 'osastotrytuoteno') {
  $sel3 = "SELECTED";
}
elseif ($jarjestys == 'nimityssorttaus') {
  $sel4 = "SELECTED";
}
else {
  $sel1 = "SELECTED";
}

echo "<td><select name='jarjestys'>";
echo "<option value=''  $sel1>".t("Osoitejärjestykseen")."</option>";
echo "<option value='tuoteno' $sel2>".t("Tuotenumerojärjestykseen")."</option>";
echo "<option value='nimityssorttaus' $sel4>".t("Nimitysjärjestykseen")."</option>";
echo "<option value='osastotrytuoteno' $sel3>".t("Osasto/Tuoteryhmä/Tuotenumerojärjestykseen")."</option>";

echo "</td></tr>";

echo "<tr><th>", t("Järjestä sorttauskenttä"), ":</th>";

$selsorttaus = array();

if ($sorttauskentan_jarjestys1 == 'hyllytaso') {
  $selsorttaus['hyllytaso'] = "SELECTED";
}
elseif ($sorttauskentan_jarjestys1 == 'hyllynro') {
  $selsorttaus['hyllynro'] = "SELECTED";
}
elseif ($sorttauskentan_jarjestys1 == 'hyllyvali') {
  $selsorttaus['hyllyvali'] = "SELECTED";
}
else {
  $selsorttaus['hyllyalue'] = "SELECTED";
}

echo "<td><select name='sorttauskentan_jarjestys1'>";
echo "<option value='hyllyalue' {$selsorttaus['hyllyalue']}>", t("Hyllyalue"), "</option>";
echo "<option value='hyllynro' {$selsorttaus['hyllynro']}>", t("Hyllynro"), "</option>";
echo "<option value='hyllyvali' {$selsorttaus['hyllyvali']}>", t("Hyllyvali"), "</option>";
echo "<option value='hyllytaso' {$selsorttaus['hyllytaso']}>", t("Hyllytaso"), "</option>";
echo "</select>";

$selsorttaus = array();

if ($sorttauskentan_jarjestys2 == 'hyllytaso') {
  $selsorttaus['hyllytaso'] = "SELECTED";
}
elseif ($sorttauskentan_jarjestys2 == 'hyllyalue') {
  $selsorttaus['hyllyalue'] = "SELECTED";
}
elseif ($sorttauskentan_jarjestys2 == 'hyllyvali') {
  $selsorttaus['hyllyvali'] = "SELECTED";
}
else {
  $selsorttaus['hyllynro'] = "SELECTED";
}

echo "<select name='sorttauskentan_jarjestys2'>";
echo "<option value='hyllyalue' {$selsorttaus['hyllyalue']}>", t("Hyllyalue"), "</option>";
echo "<option value='hyllynro' {$selsorttaus['hyllynro']}>", t("Hyllynro"), "</option>";
echo "<option value='hyllyvali' {$selsorttaus['hyllyvali']}>", t("Hyllyvali"), "</option>";
echo "<option value='hyllytaso' {$selsorttaus['hyllytaso']}>", t("Hyllytaso"), "</option>";
echo "</select>";

$selsorttaus = array();

if ($sorttauskentan_jarjestys3 == 'hyllytaso') {
  $selsorttaus['hyllytaso'] = "SELECTED";
}
elseif ($sorttauskentan_jarjestys3 == 'hyllynro') {
  $selsorttaus['hyllynro'] = "SELECTED";
}
elseif ($sorttauskentan_jarjestys3 == 'hyllyalue') {
  $selsorttaus['hyllyalue'] = "SELECTED";
}
else {
  $selsorttaus['hyllyvali'] = "SELECTED";
}

echo "<select name='sorttauskentan_jarjestys3'>";
echo "<option value='hyllyalue' {$selsorttaus['hyllyalue']}>", t("Hyllyalue"), "</option>";
echo "<option value='hyllynro' {$selsorttaus['hyllynro']}>", t("Hyllynro"), "</option>";
echo "<option value='hyllyvali' {$selsorttaus['hyllyvali']}>", t("Hyllyvali"), "</option>";
echo "<option value='hyllytaso' {$selsorttaus['hyllytaso']}>", t("Hyllytaso"), "</option>";
echo "</select>";

$selsorttaus = array();

if ($sorttauskentan_jarjestys4 == 'hyllyalue') {
  $selsorttaus['hyllyalue'] = "SELECTED";
}
elseif ($sorttauskentan_jarjestys4 == 'hyllynro') {
  $selsorttaus['hyllynro'] = "SELECTED";
}
elseif ($sorttauskentan_jarjestys4 == 'hyllyvali') {
  $selsorttaus['hyllyvali'] = "SELECTED";
}
else {
  $selsorttaus['hyllytaso'] = "SELECTED";
}

echo "<select name='sorttauskentan_jarjestys4'>";
echo "<option value='hyllyalue' {$selsorttaus['hyllyalue']}>", t("Hyllyalue"), "</option>";
echo "<option value='hyllynro' {$selsorttaus['hyllynro']}>", t("Hyllynro"), "</option>";
echo "<option value='hyllyvali' {$selsorttaus['hyllyvali']}>", t("Hyllyvali"), "</option>";
echo "<option value='hyllytaso' {$selsorttaus['hyllytaso']}>", t("Hyllytaso"), "</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

echo "</table>";

echo "<br><input type='submit' name='tulosta' value='", t("Aja"), "'>";
echo "</form><br><br>";

// jos paat ykköseks niin ei koita muokata/tulostaa fileä, vaan ekottaa filen polun ja nimen
$debug = "0";


if ($tee == 'TULOSTA' and isset($tulosta)) {

  $tulostimet[0] = "Inventointi";
  if (count($komento) == 0) {
    require "inc/valitse_tulostin.inc";
  }

  if ($ippa != '' and $ikka != '' and $ivva != '') {
    $idate = $ivva."-".$ikka."-".$ippa." 00:00:00";
    $invaamatta = " and tuotepaikat.inventointiaika <= '{$idate}'";
  }

  $rajauslisa = "";
  $rajauslisatuote = "";

  // jos ollaan ruksattu nayta myös inventoidut
  if ($naytainvtuot == '') {
    $rajauslisa .= " and tuotepaikat.inventointiaika <= date_sub(now(),interval 14 day) ";
  }

  if (!empty($ei_huomioida_tuotepaikkoja_avainsanoista)) {
    $rajauslisa .= ei_huomioida_tuotepaikkoja_avainsanoista(true, 'tuotepaikat');
  }

  if (!empty($varasto)) {
    $rajauslisa .= " and tuotepaikat.varasto = '".(int) $varasto."' ";
  }

  // jos ei haluta invata poistettuja tuotteita
  if ($status == 'EI') {
    $rajauslisatuote .= " and tuote.status != 'P' ";
  }
  elseif (!empty($status)) {
    $rajauslisatuote .= " and tuote.status = '".mysql_real_escape_string($status)."' ";
  }

  // jos ollaan ruksattu vain saldolliset tuotteet
  if ($arvomatikka == 'S') {
    $extra = " and tuotepaikat.saldo > 0 ";
  }
  elseif ($arvomatikka == 'N') {
    $extra = " and tuotepaikat.saldo != 0 ";
  }
  else {
    $extra = "";
  }

  function il_topmyydyt($top, $where, $kutsujoinlisa, $rajauslisa, $rajauslisatuote, $invaamatta, $extra) {
    global $kukarow, $kutsu;

    $tuotenoarray = array();

    //näytetään vain $top myydyintä tuotetta
    $kutsu .= " ".t("Listaa vain myydyimmät:")." $top ".t("tuotetta");

    //Rullaava 6 kuukautta taaksepäin
    $kka = date("m", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
    $vva = date("Y", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
    $ppa = date("d", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));

    $query = "SELECT tuote.tuoteno, sum(rivihinta) summa
              FROM tilausrivi use index (yhtio_tyyppi_osasto_try_laskutettuaika)
              JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $rajauslisatuote
              JOIN tuotepaikat use index (tuote_index) ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno $rajauslisa $invaamatta $extra
              $kutsujoinlisa
              LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                AND inventointilistarivi.tila              = 'A')
              WHERE tilausrivi.yhtio                       = '$kukarow[yhtio]'
              and tilausrivi.tyyppi                        = 'L'
              $where
              and tilausrivi.laskutettuaika                >= '$vva-$kka-$ppa'
              AND inventointilistarivi.tunnus IS NULL
              GROUP BY 1
              ORDER BY summa desc
              LIMIT $top";
    $tuotresult = pupe_query($query);

    while ($tuotrow = mysql_fetch_assoc($tuotresult)) {
      $tuotenoarray[] = $tuotrow["tuoteno"];
    }

    if (count($tuotenoarray) > 0) {
      return " and tuote.tuoteno in ('".implode("','", $tuotenoarray)."') ";
    }
    else {
      return "";
    }
  }

  function il_varvaikutus($varastonarvo, $varastoonvaikutus, $where, $kutsujoinlisa, $rajauslisa, $rajauslisatuote, $invaamatta, $extra) {
    global $kukarow, $kutsu;

    $tuotenoarray = array();

    // Näytä Eniten Varastonarvoon Vaikuttavaa Tuotetta
    $having = "";
    $limit = "";

    if ($varastonarvo > 0) {
      $kutsu .= " ".t("Varastonarvo yli").": $varastonarvo ".$yhtiorow["valkoodi"];
      $having = " HAVING varasto >= {$varastonarvo} ";
    }

    if ($varastoonvaikutus > 0) {
      $kutsu .= " ".t("Eniten varastonarvoon vaikuttavaa").": $varastoonvaikutus ".t("tuotetta");
      $limit = " LIMIT {$varastoonvaikutus} ";
    }

    $query = "SELECT sum(
              if(  tuote.sarjanumeroseuranta = 'S',
                (  SELECT tuotepaikat.saldo*if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl), avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)*0.75), avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)*0.5), avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)*0.25), 0)
                  FROM sarjanumeroseuranta
                  LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
                  LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                  WHERE sarjanumeroseuranta.yhtio             = tuotepaikat.yhtio
                  and sarjanumeroseuranta.tuoteno             = tuotepaikat.tuoteno
                  and sarjanumeroseuranta.myyntirivitunnus   != -1
                  and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
                  and tilausrivi_osto.laskutettuaika         != '0000-00-00'
                ),
                tuotepaikat.saldo*if(tuote.epakurantti100pvm='0000-00-00', if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0)
              )
              ) varasto,
              tuote.tuoteno
              FROM tuotepaikat
              JOIN tuote ON tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio and tuote.ei_saldoa = '' {$rajauslisatuote}
              LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                  AND inventointilistarivi.tuotepaikkatunnus  = tuotepaikat.tunnus
                  AND inventointilistarivi.tila               = 'A')
              {$kutsujoinlisa}
              WHERE tuotepaikat.yhtio                         = '{$kukarow["yhtio"]}'
              and tuotepaikat.saldo                           <> 0
              {$rajauslisa}
              {$invaamatta}
              {$extra}
              {$where}
              GROUP BY tuote.tuoteno
              {$having}
              ORDER BY varasto DESC
              {$limit}";
    $varresult =  pupe_query($query);

    while ($varrow = mysql_fetch_assoc($varresult)) {
      $tuotenoarray[] = $varrow["tuoteno"];
    }

    if (count($tuotenoarray) > 0) {
      return " and tuote.tuoteno in ('".implode("','", $tuotenoarray)."') ";
    }
    else {
      return "";
    }
  }

  $from            = "";
  $kutsu             = "";
  $sorttauskentan_jarjestys   = "";
  $varastoonvaikutus       = (float) $varastoonvaikutus;
  $varastonarvo         = (float) $varastonarvo;
  $top             = (float) $top;

  if ($sorttauskentan_jarjestys1 == '' or $sorttauskentan_jarjestys2 == '' or $sorttauskentan_jarjestys3 == '' or $sorttauskentan_jarjestys4 == '') {
    $sorttauskentan_jarjestys = "concat(lpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0'))";
  }
  else {
    $sorttauskentan_jarjestys = 'concat(';

    if ($sorttauskentan_jarjestys1 != '') {
      $sorttauskentan_jarjestys1 = mysql_real_escape_string($sorttauskentan_jarjestys1);
      $sorttauskentan_jarjestys .= "lpad(upper(tuotepaikat.{$sorttauskentan_jarjestys1}), 5, '0'),";
    }

    if ($sorttauskentan_jarjestys2 != '') {
      $sorttauskentan_jarjestys2 = mysql_real_escape_string($sorttauskentan_jarjestys2);
      $sorttauskentan_jarjestys .= "lpad(upper(tuotepaikat.{$sorttauskentan_jarjestys2}), 5, '0'),";
    }

    if ($sorttauskentan_jarjestys3 != '') {
      $sorttauskentan_jarjestys3 = mysql_real_escape_string($sorttauskentan_jarjestys3);
      $sorttauskentan_jarjestys .= "lpad(upper(tuotepaikat.{$sorttauskentan_jarjestys3}), 5, '0'),";
    }

    if ($sorttauskentan_jarjestys4 != '') {
      $sorttauskentan_jarjestys4 = mysql_real_escape_string($sorttauskentan_jarjestys4);
      $sorttauskentan_jarjestys .= "lpad(upper(tuotepaikat.{$sorttauskentan_jarjestys4}), 5, '0'),";
    }

    $sorttauskentan_jarjestys = substr($sorttauskentan_jarjestys, 0, -1);

    $sorttauskentan_jarjestys .= ')';
  }

  //hakulause, tämä on sama kaikilla vaihtoehdolilla ja group by lause joka on sama kaikilla
  $select  = " tuote.tuoteno, tuote.nimitys, tuote.sarjanumeroseuranta, tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuote.nimitys, tuote.yksikko, concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) varastopaikka, inventointiaika, tuotepaikat.saldo, tuotepaikat.tunnus as tuotepaikkatunnus,
  $sorttauskentan_jarjestys sorttauskentta";
  $groupby = " tuote.tuoteno, tuote.nimitys, tuote.sarjanumeroseuranta, tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuote.nimitys, tuote.yksikko, varastopaikka, inventointiaika, tuotepaikat.saldo ";

  $joinlisa = "";

  // Reservipaikka ja keräysvyöhyke rajaus vain jos keräyserät parametri on asetettu.
  $sub_ehto1 = (!empty($reservipaikka) and $yhtiorow["kerayserat"] == 'K');
  $sub_ehto2 = (!empty($keraysvyohyke) and $yhtiorow["kerayserat"] == 'K');

  if ($sub_ehto1 or $sub_ehto2) {
    $ressulisa = $reservipaikka != '' ? "varaston_hyllypaikat.reservipaikka = '".mysql_real_escape_string($reservipaikka)."' AND " : "";
    $vyohykelisa = $keraysvyohyke != '' ? "varaston_hyllypaikat.keraysvyohyke = '".mysql_real_escape_string($keraysvyohyke)."' AND " : "";

    $joinlisa = " JOIN varaston_hyllypaikat ON (
            {$ressulisa}
            {$vyohykelisa}
            varaston_hyllypaikat.yhtio = tuotepaikat.yhtio
            AND varaston_hyllypaikat.hyllyalue = tuotepaikat.hyllyalue
            AND varaston_hyllypaikat.hyllynro = tuotepaikat.hyllynro
            AND varaston_hyllypaikat.hyllytaso = tuotepaikat.hyllytaso
            AND varaston_hyllypaikat.hyllyvali = tuotepaikat.hyllyvali) ";
  }

  if (empty($piilotaToim_tuoteno)) {
    $select .= ", group_concat(distinct tuotteen_toimittajat.toim_tuoteno) toim_tuoteno ";
    $lefttoimi = " LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno ";
  }

  $_tuote_chk = (!empty($lisa) or !empty($toimittaja) or !empty($tuotemerkki));
  $_tuote_chk = ($_tuote_chk or (!empty($ahyllyalue) and !empty($lhyllyalue)) or (!empty($varasto)));

  if ($_tuote_chk) {
    ///* Inventoidaan *///
    $where = "";
    $kutsujoinlisa = "";

    ///* Inventoidaan monivalintalaatikon tietojen perusteella *//
    if ($lisa != '') {
      $yhtiotaulu = "tuote";
      $from     = " FROM tuote use index (osasto_try_index) ";
      $join     = " JOIN tuotepaikat USE INDEX (tuote_index) ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno {$rajauslisa} {$invaamatta} {$extra} ";
      $join2    .= " LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                      AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                      AND inventointilistarivi.tila = 'A')";
      $where    = " $lisa
                    and tuote.ei_saldoa = ''
                    AND inventointilistarivi.tunnus IS NULL
                    {$rajauslisatuote}";
    }

    if ($tuotemerkki != '') {
      ///* Inventoidaan tuotemerkin perusteella *///
      $kutsu .= " ".t("Tuotemerkki").": {$tuotemerkki} ";

      if ($from == '') {
        $yhtiotaulu = "tuote";
        $from     = " FROM tuote use index (osasto_try_index) ";
        $join     = " JOIN tuotepaikat USE INDEX (tuote_index) ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno {$rajauslisa} {$invaamatta} {$extra} ";
        $join2    .= " LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                        AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                        AND inventointilistarivi.tila = 'A')";
        $where .= " AND inventointilistarivi.tunnus IS NULL";
      }

      $where .= " and tuote.tuotemerkki = '$tuotemerkki' {$rajauslisatuote}";
    }

    if ($ahyllyalue != '' and $lhyllyalue != '') {
      ///* Inventoidaan tietty varastoalue *///
      $apaikka = strtoupper(sprintf("%-05s", $ahyllyalue)).strtoupper(sprintf("%05s", $ahyllynro)).strtoupper(sprintf("%05s", $ahyllyvali)).strtoupper(sprintf("%05s", $ahyllytaso));
      $lpaikka = strtoupper(sprintf("%-05s", $lhyllyalue)).strtoupper(sprintf("%05s", $lhyllynro)).strtoupper(sprintf("%05s", $lhyllyvali)).strtoupper(sprintf("%05s", $lhyllytaso));

      $kutsu .= " ".t("Varastopaikat").": {$apaikka} - {$lpaikka} ";

      if ($from == '') {
        $yhtiotaulu = "tuotepaikat";
        $from     = " FROM tuotepaikat ";
        $join     = " JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuote.ei_saldoa = '' {$rajauslisatuote}";
        $join2    .= " LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                        AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                        AND inventointilistarivi.tila = 'A')";
        $where    = "  and concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso),5, '0')) >=
                concat(rpad(upper('$ahyllyalue'), 5, '0'),lpad(upper('$ahyllynro'), 5, '0'),lpad(upper('$ahyllyvali'), 5, '0'),lpad(upper('$ahyllytaso'),5, '0'))
                and concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso),5, '0')) <=
                concat(rpad(upper('$lhyllyalue'), 5, '0'),lpad(upper('$lhyllynro'), 5, '0'),lpad(upper('$lhyllyvali'), 5, '0'),lpad(upper('$lhyllytaso'),5, '0'))
                AND inventointilistarivi.tunnus IS NULL $rajauslisa $invaamatta $extra ";
      }
      else {
        $join .= "  and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'),lpad(upper(tuotepaikat.hyllyvali) ,5,'0'),lpad(upper(tuotepaikat.hyllytaso) ,5,'0')) >=
              concat(rpad(upper('$ahyllyalue'), 5, '0'),lpad(upper('$ahyllynro'), 5, '0'),lpad(upper('$ahyllyvali'), 5, '0'),lpad(upper('$ahyllytaso'),5, '0'))
              and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'),lpad(upper(tuotepaikat.hyllyvali) ,5,'0'),lpad(upper(tuotepaikat.hyllytaso) ,5,'0')) <=
              concat(rpad(upper('$lhyllyalue'), 5, '0'),lpad(upper('$lhyllynro'), 5, '0'),lpad(upper('$lhyllyvali'), 5, '0'),lpad(upper('$lhyllytaso'),5, '0'))";
      }
    }

    if ($varasto != '') {
      ///* Inventoidaan tietty varasto *///

      $kutsu .= " ".t("Varasto").": {$varasto}";

      if ($from == '') {
        $yhtiotaulu = "tuotepaikat";
        $from     = " FROM tuotepaikat ";
        $join     = " JOIN tuote use index (tuoteno_index) ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuote.ei_saldoa = '' {$rajauslisatuote}";
        $join2    .= " LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                        AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                        AND inventointilistarivi.tila = 'A')";
        $where    = " AND inventointilistarivi.tunnus IS NULL $rajauslisa $invaamatta $extra ";
      }
      else {
        $join .= " and tuotepaikat.varasto = '{$varasto}' ";
      }
    }

    if ($toimittaja != '') {
      ///* Inventoidaan tietyn toimittajan tuotteet *///
      $kutsu .= " ".t("Toimittaja:")."{$toimittaja} ";

      if ($from == '') {
        $yhtiotaulu = "tuotteen_toimittajat";

        $from = " FROM tuotteen_toimittajat
                   JOIN toimi ON toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus";

        $join = " JOIN tuotepaikat use index (tuote_index) ON tuotepaikat.yhtio=tuotteen_toimittajat.yhtio and tuotepaikat.tuoteno=tuotteen_toimittajat.tuoteno $rajauslisa $invaamatta $extra
                   JOIN tuote on tuote.yhtio=tuotteen_toimittajat.yhtio and tuote.tuoteno=tuotteen_toimittajat.tuoteno and tuote.ei_saldoa = '' {$rajauslisatuote}";
        $join2    .= " LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                        AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                        AND inventointilistarivi.tila = 'A')";

        $where = " and toimi.ytunnus = '$toimittaja'
                   AND inventointilistarivi.tunnus IS NULL";

        $kutsujoinlisa = " JOIN tuotteen_toimittajat
                            ON (tuotteen_toimittajat.yhtio = tuote.yhtio
                            AND tuotteen_toimittajat.tuoteno = tuote.tuoteno)
                           JOIN toimi
                            ON (toimi.yhtio = tuotteen_toimittajat.yhtio
                            AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)";
      }
      else {
        $join      .= " JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno
                JOIN toimi ON toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus and toimi.ytunnus = '$toimittaja' ";
      }

      $lefttoimi = "";
    }

    if ($joinlisa != "") {
      $join .= $joinlisa;
    }

    if ($lisa_parametri != "") {
      $join .= $lisa_parametri;
    }

    if ($top > 0) {
      $where .= il_topmyydyt($top, $where, $kutsujoinlisa, $rajauslisa, $rajauslisatuote, $invaamatta, $extra);
    }

    if ($varastoonvaikutus > 0 or $varastonarvo > 0) {
      $where .= il_varvaikutus($varastonarvo, $varastoonvaikutus, $where, $kutsujoinlisa, $rajauslisa, $rajauslisatuote, $invaamatta, $extra);
    }

    if ($jarjestys == 'tuoteno') {
      $orderby = " tuoteno, sorttauskentta ";
    }
    elseif ($jarjestys == 'osastotrytuoteno') {
      $orderby = " osasto, try, tuoteno, sorttauskentta ";
    }
    elseif ($jarjestys == 'nimityssorttaus') {
      $orderby = " nimitys, sorttauskentta ";
    }
    else {
      $orderby = " sorttauskentta, tuoteno ";
    }

    $query = "SELECT $select
              $from
              $join
              $join2
              $lefttoimi
              WHERE $yhtiotaulu.yhtio  = '$kukarow[yhtio]'
              $where
              GROUP BY $groupby
              ORDER BY $orderby";
    $saldoresult = pupe_query($query);

    if (mysql_num_rows($saldoresult) == 0) {
      echo "<font class='error'>", t("Ei löytynyt rivejä"), "</font><br><br>";
      $tee='';
    }
  }
  elseif ($raportti != '') {
    ///* Inventoidaan jonkun raportin avulla *///

    if ($raportti == 'loppuneet') {

      $kutsu = " ".t("Loppuneet tuotteet")." ({$ppa}.{$kka}.{$vva} - {$ppl}.{$kkl}.{$vvl}) ";

      if ($jarjestys == 'tuoteno') {
        $orderby = " tuoteno, sorttauskentta ";
      }
      elseif ($jarjestys == 'osastotrytuoteno') {
        $orderby = " osasto, try, tuoteno, sorttauskentta ";
      }
      elseif ($jarjestys == 'nimityssorttaus') {
        $orderby = " nimitys, sorttauskentta ";
      }
      else {
        $orderby = " sorttauskentta, tuoteno ";
      }

      $query = "SELECT {$select}
                FROM tuotepaikat use index (saldo_index)
                JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno AND tuote.ei_saldoa = '' {$rajauslisatuote})
                {$joinlisa}
                {$lefttoimi}
                LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                  AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                  AND inventointilistarivi.tila              = 'A')
                WHERE tuotepaikat.yhtio                      = '{$kukarow['yhtio']}'
                AND tuotepaikat.saldoaika                    >= '{$vva}-{$kka}-{$ppa} 00:00:00'
                AND tuotepaikat.saldoaika                    <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
                and tuotepaikat.saldo                        <= 0
                {$rajauslisa}
                {$invaamatta}
                {$extra}
                AND inventointilistarivi.tunnus IS NULL
                group by $groupby
                ORDER BY $orderby";
      $saldoresult = pupe_query($query);
    }

    if ($raportti == 'vaarat') {

      $kutsu = " ".t("Väärät Saldot")." ({$ppa}.{$kka}.{$vva} - {$ppl}.{$kkl}.{$vvl}) ";

      if ($jarjestys == 'tuoteno') {
        $orderby = " tuotepaikat.tuoteno, sorttauskentta ";
      }
      elseif ($jarjestys == 'osastotrytuoteno') {
        $orderby = " osasto, try, tuoteno, sorttauskentta ";
      }
      elseif ($jarjestys == 'nimityssorttaus') {
        $orderby = " nimitys, sorttauskentta ";
      }
      else {
        $orderby = " sorttauskentta, tuotepaikat.tuoteno ";
      }

      $query = "SELECT DISTINCT {$select}
                FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
                JOIN tuotepaikat USE INDEX (tuote_index) ON (tuotepaikat.yhtio = tilausrivi.yhtio AND tuotepaikat.tuoteno = tilausrivi.tuoteno {$extra})
                JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno AND tuote.ei_saldoa = '')
                {$joinlisa}
                {$lefttoimi}
                LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                  AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                  AND inventointilistarivi.tila              = 'A')
                WHERE tilausrivi.yhtio                       = '{$kukarow['yhtio']}'
                and tilausrivi.tyyppi                        = 'L'
                AND tilausrivi.laskutettuaika                >= '{$vva}-{$kka}-{$ppa}'
                AND tilausrivi.laskutettuaika                <= '{$vvl}-{$kkl}-{$ppl}'
                and tilausrivi.tilkpl                        <> tilausrivi.kpl
                and tilausrivi.var                           in ('H','')
                and tuotepaikat.hyllyalue                    = tilausrivi.hyllyalue
                and tuotepaikat.hyllynro                     = tilausrivi.hyllynro
                and tuotepaikat.hyllyvali                    = tilausrivi.hyllyvali
                and tuotepaikat.hyllytaso                    = tilausrivi.hyllytaso
                AND inventointilistarivi.tunnus IS NULL
                group by $groupby
                ORDER BY $orderby";
      $saldoresult = pupe_query($query);
    }

    if ($raportti == 'negatiiviset') {

      $kutsu = " ".t("Tuotteet miinus-saldolla")." ({$ppa}.{$kka}.{$vva} - {$ppl}.{$kkl}.{$vvl}) ";

      if ($jarjestys == 'tuoteno') {
        $orderby = " tuoteno, sorttauskentta ";
      }
      elseif ($jarjestys == 'osastotrytuoteno') {
        $orderby = " osasto, try, tuoteno, sorttauskentta ";
      }
      elseif ($jarjestys == 'nimityssorttaus') {
        $orderby = " nimitys, sorttauskentta ";
      }
      else {
        $orderby = " sorttauskentta, tuoteno ";
      }

      $query = "SELECT {$select}
                FROM tuotepaikat use index (saldo_index)
                JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno AND tuote.ei_saldoa = '' {$rajauslisatuote})
                {$joinlisa}
                {$lefttoimi}
                LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                  AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                  AND inventointilistarivi.tila              = 'A')
                WHERE tuotepaikat.yhtio                      = '{$kukarow['yhtio']}'
                and tuotepaikat.saldo                        < 0
                $rajauslisa
                $invaamatta
                $extra
                AND inventointilistarivi.tunnus IS NULL
                group by $groupby
                ORDER BY $orderby";
      $saldoresult = pupe_query($query);
    }

    if ($raportti == 'tapahtumia') {

      $kutsu = " ".t("Tuotteet joilla tulo, myynti, valmistus tai varastonsiirtotapahtumia")." ($ppa.$kka.$vva-$ppl.$kkl.$vvl) ";

      if ($jarjestys == 'tuoteno') {
        $orderby = " tuoteno, sorttauskentta ";
      }
      elseif ($jarjestys == 'osastotrytuoteno') {
        $orderby = " osasto, try, tuoteno, sorttauskentta ";
      }
      elseif ($jarjestys == 'nimityssorttaus') {
        $orderby = " nimitys, sorttauskentta ";
      }
      else {
        $orderby = " sorttauskentta, tuoteno ";
      }

      $query = "SELECT $select
                FROM tapahtuma use index (yhtio_laji_laadittu)
                JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tapahtuma.yhtio and tuote.tuoteno = tapahtuma.tuoteno and tuote.ei_saldoa = '' {$rajauslisatuote})
                JOIN tuotepaikat use index (tuote_index)  ON (tuotepaikat.yhtio = tapahtuma.yhtio
                                        AND tuotepaikat.tuoteno   = tapahtuma.tuoteno
                                        AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
                                        AND tuotepaikat.hyllynro  = tapahtuma.hyllynro
                                        AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
                                        AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
                                        {$rajauslisa} {$invaamatta} {$extra})
                LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                  AND inventointilistarivi.tuotepaikkatunnus      = tuotepaikat.tunnus
                  AND inventointilistarivi.tila                   = 'A')
                {$lefttoimi}
                WHERE tapahtuma.yhtio                             = '$kukarow[yhtio]'
                AND tapahtuma.laji                                IN ('tulo', 'laskutus', 'valmistus', 'siirto')
                AND tapahtuma.laadittu BETWEEN '$vva-$kka-$ppa' and '$vvl-$kkl-$ppl'
                AND inventointilistarivi.tunnus IS NULL
                GROUP BY $groupby
                ORDER BY $orderby";
      $saldoresult = pupe_query($query);
    }

    if (mysql_num_rows($saldoresult) == 0) {
      echo "<font class='error'>".t("Yhtään tuotetta ei löytynyt")."!</font><br><br>";
      $tee = '';
    }
  }
  elseif ($tila == "SIIVOUS") {
    $query = "SELECT {$select}
              FROM tuotepaikat use index (primary)
              JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno AND tuote.ei_saldoa = '' {$rajauslisatuote})
              {$joinlisa}
              {$lefttoimi}
              LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                AND inventointilistarivi.tila              = 'A')
              WHERE tuotepaikat.yhtio                      = '{$kukarow['yhtio']}'
              AND tuotepaikat.tunnus                       IN ({$saldot})
              {$rajauslisa}
              {$invaamatta}
              AND inventointilistarivi.tunnus IS NULL
              GROUP BY {$groupby}
              ORDER BY sorttauskentta, tuoteno";
    $saldoresult = pupe_query($query);
  }
  else {
    echo "<font class='error'>".t("Yhtään tuotetta ei löytynyt")."!</font><br><br>";
    $tee = '';
  }
}

if ($tee == 'TULOSTA' and isset($tulosta)) {
  if (mysql_num_rows($saldoresult) > 0 ) {

    //kirjoitetaan  faili levylle..
    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $filenimi = "/tmp/".preg_replace("/[^a-z0-9\-_]/i", "", t("Inventointilista")."-".md5(uniqid(mt_rand(), true))).".txt";
    $fh = fopen($filenimi, "w+");

    $pp = date('d');
    $kk = date('m');
    $vv = date('Y');
    $kello = date('H:i:s');

    //rivinleveys default
    $rivinleveys = 137;

    if ($yhtiorow['laaja_inventointilista'] != "") {
      $kokonaissivumaara = ceil(mysql_num_rows($saldoresult) / 16);
    }
    else {
      $kokonaissivumaara = ceil(mysql_num_rows($saldoresult) / 17);
    }

    //haetaan inventointilista numero tässä vaiheessa
    $query = "SELECT max(tunnus) listanro
              FROM inventointilista";
    $result = pupe_query($query);
    $lrow = mysql_fetch_assoc($result);

    $listanro = $lrow["listanro"]+1;
    $listaaika = date("Y-m-d H:i:s");

    $excelrivit = array();
    $excelheaderit = array("paikka", "tuoteno");

    $ots  = t("Inventointilista")." $kutsu\t".t("Sivu")." <SIVUNUMERO>\n".t("Listanumero").": $listanro\t\t$yhtiorow[nimi]\t\t$pp.$kk.$vv - $kello\n\n";

    $excel_info  = t("Inventointilista")."\n";
    $excel_info .= trim($kutsu)."\n";
    $excel_info .= t("Listanumero").": ".$listanro ."\n";
    $excel_info .= $yhtiorow['nimi']."\n";
    $excel_info .= "$pp.$kk.$vv - $kello";

    if ($yhtiorow['laaja_inventointilista'] != "") {
      $excel_info .= "\n".t("Vapaa teksti").": {$vapaa_teksti}";
      array_unshift($excelheaderit, "#");

      $ots .= t("Vapaa teksti").": {$vapaa_teksti}\n\n";

      $ots .= sprintf('%-5.5s', "#");
    }

    $ots .= sprintf('%-18.14s',   t("Paikka"));
    $ots .= sprintf('%-21.21s',   t("Tuoteno"));

    // Ei näytetä toim_tuotenumeroa, nimitys voi olla pidempi
    if ($piilotaToim_tuoteno == "") {
      $ots .= sprintf('%-21.21s',   t("Toim.Tuoteno"));

      if ($yhtiorow['laaja_inventointilista'] != "") {
        $ots .= sprintf('%-35.33s',   t("Nimitys"));
      }
      else {
        $ots .= sprintf('%-40.38s',   t("Nimitys"));
      }

      $excelheaderit[] = "toim.tuoteno";
      $excelheaderit[] = "nimitys";
    }
    else {

      if ($yhtiorow['laaja_inventointilista'] != "") {
        $ots .= sprintf('%-55.53s',   t("Nimitys"));
      }
      else {
        $ots .= sprintf('%-60.58s',   t("Nimitys"));
      }

      $excelheaderit[] = "nimitys";
    }

    if ($naytasaldo == 'H') {
      $rivinleveys += 10;
      $ots .= sprintf('%-10.10s', t("Hyllyssä"));
      $katkoviiva = '__________';

      $excelheaderit[] = "hyllyssä";
    }
    elseif ($naytasaldo == 'S') {
      $rivinleveys += 10;
      $ots .= sprintf('%-10.10s', t("Saldo"));
      $katkoviiva = '__________';

      $excelheaderit[] = "saldo";
    }

    array_push($excelheaderit, "määrä", "yksikkö", "tilkpl", "varattu/ker");

    $ots .= sprintf('%-7.7s',    t("Määrä"));
    $ots .= sprintf('%-9.9s',     t("Yksikkö"));
    $ots .= sprintf('%-7.7s',     t("Tilkpl"));

    if ($yhtiorow['laaja_inventointilista'] != "") {
      $ots .= sprintf('%-7.7s',  t("Var/Ker"));
      $ots .= sprintf('%-5.5s', "  #");

      $excelheaderit[] = "#";
    }
    else {
      $ots .= sprintf('%-13.13s',  t("Varattu/Ker"));
    }

    $ots .= "\n";
    $ots .= "_______________________________________________________________________________________________________________________________________$katkoviiva\n";
    fwrite($fh, str_replace("<SIVUNUMERO>", "1 / {$kokonaissivumaara}", $ots));
    $ots = chr(12).$ots;

    // oma rivilaskuri excelille kun siinä ei vaihdeta sivua
    $xr = $rivit = 1;
    $sivulaskuri = 1;

    if ($ei_inventointi == "") {

      $_vapaa_teksti = mysql_real_escape_string($vapaa_teksti);

      $query = "INSERT INTO inventointilista SET
                yhtio        = '{$kukarow['yhtio']}',
                vapaa_teksti = '{$_vapaa_teksti}',
                naytamaara   = '{$naytasaldo}',
                muuttaja     = '{$kukarow['kuka']}',
                laatija      = '{$kukarow['kuka']}',
                luontiaika   = now(),
                muutospvm    = now(),
                tunnus       = '{$listanro}'";
      $munresult = pupe_query($query, $GLOBALS["masterlink"]);
    }

    $rivinro = 1;

    if ($yhtiorow['laaja_inventointilista'] != "") {
      $maxrivit = 17;
    }
    else {
      $maxrivit = 18;
    }

    while ($tuoterow = mysql_fetch_assoc($saldoresult)) {

      if ($oletusvarasto_chk > 0 and kuuluukovarastoon($tuoterow["hyllyalue"], $tuoterow["hyllynro"], $oletusvarasto_chk) == 0) continue;

      // Joskus halutaan vain tulostaa lista, mutta ei oikeasti invata tuotteita
      if ($ei_inventointi == "") {

        if ($yhtiorow['laaja_inventointilista'] != "") {
          list($_saldo, $_hyllyssa, $_myytavissa) = saldo_myytavissa($tuoterow["tuoteno"], '', '', '', $tuoterow["hyllyalue"], $tuoterow["hyllynro"], $tuoterow["hyllyvali"], $tuoterow["hyllytaso"]);
        }
        else {
          $_hyllyssa = 0;
        }

        $query = "INSERT INTO inventointilistarivi SET
                  yhtio             = '{$kukarow['yhtio']}',
                  tila              = 'A',
                  aika              = null,
                  otunnus           = '{$listanro}',
                  tuoteno           = '{$tuoterow['tuoteno']}',
                  hyllyalue         = '{$tuoterow['hyllyalue']}',
                  hyllynro          = '{$tuoterow['hyllynro']}',
                  hyllyvali         = '{$tuoterow['hyllyvali']}',
                  hyllytaso         = '{$tuoterow['hyllytaso']}',
                  rivinro           = '{$rivinro}',
                  hyllyssa          = '{$_hyllyssa}',
                  laskettu          = '{$_hyllyssa}',
                  tuotepaikkatunnus = '{$tuoterow['tuotepaikkatunnus']}',
                  muuttaja          = '{$kukarow['kuka']}',
                  laatija           = '{$kukarow['kuka']}',
                  luontiaika        = now(),
                  muutospvm         = now()";
        $munresult = pupe_query($query, $GLOBALS["masterlink"]);
      }

      if ($rivit >= $maxrivit) {
        $sivulaskuri++;
        fwrite($fh, str_replace("<SIVUNUMERO>", "{$sivulaskuri} / {$kokonaissivumaara}", $ots));
        $rivit = 1;
      }

      if ($naytasaldo != '') {

        //katotaan mihin varastooon tilausrivillä tuotepaikka kuuluu
        $rivipaikka = kuuluukovarastoon($tuoterow["hyllyalue"], $tuoterow["hyllynro"]);

        $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto,
                  varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                  tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                  concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
                  varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
                   FROM tuote
                  JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
                  JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                    AND varastopaikat.tunnus = tuotepaikat.varasto
                    AND varastopaikat.tunnus = '{$rivipaikka}')
                  WHERE tuote.yhtio          = '{$kukarow['yhtio']}'
                  AND tuote.tuoteno          = '{$tuoterow['tuoteno']}'
                  ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
        $sresult = pupe_query($query);

        $rivipaikkahyllyssa   = 0;
        $rivivarastohyllyssa  = 0;
        $rivipaikkasaldo      = 0;
        $rivivarastosaldo     = 0;

        if (mysql_num_rows($sresult) > 0) {

          while ($saldorow = mysql_fetch_assoc($sresult)) {

            list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($saldorow["tuoteno"], '', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], '', '', $saldorow["era"]);

            if ($saldorow['hyllyalue'] == $tuoterow['hyllyalue'] and $saldorow['hyllynro'] == $tuoterow['hyllynro'] and $saldorow['hyllyvali'] == $tuoterow['hyllyvali'] and $saldorow['hyllytaso'] == $tuoterow['hyllytaso']) {
              $rivipaikkahyllyssa  += $hyllyssa;
              $rivipaikkasaldo += $saldo;
            }

            $rivivarastohyllyssa += $hyllyssa;
            $rivivarastosaldo += $saldo;
          }
        }
      }
      else {
        $rivipaikkahyllyssa   = 0;
        $rivivarastohyllyssa   = 0;
        $rivipaikkasaldo     = 0;
        $rivivarastosaldo     = 0;
      }

      if ($tuoterow["inventointiaika"]=='0000-00-00 00:00:00') {
        $tuoterow["inventointiaika"] = t("Ei inventoitu");
      }

      $prn = "\n";

      if ($rivit > 1) $prn .= "\n";

      if ($yhtiorow['laaja_inventointilista'] != "") {
        $prn .= sprintf('%-5.5s', $rivinro);
        $excelrivit[$xr]['rivinro'] =  $rivinro;
      }

      $prn .= sprintf('%-18.14s',   $tuoterow["varastopaikka"]);
      $excelrivit[$xr]['varastopaikka'] =  $tuoterow["varastopaikka"];

      $prn .= sprintf('%-21.21s',   $tuoterow["tuoteno"]);
      $excelrivit[$xr]['tuoteno'] =  $tuoterow["tuoteno"];

      // Jos valittu toim_tuoteno piilotus ei sitä piirretä (säästetään tilaa)
      if ($piilotaToim_tuoteno == "") {
        $prn .= sprintf('%-21.21s',   $tuoterow["toim_tuoteno"]);
        $excelrivit[$xr]['toim_tuoteno'] =  $tuoterow["toim_tuoteno"];

        if ($yhtiorow['laaja_inventointilista'] != "") {
          $prn .= sprintf('%-35.33s',   t_tuotteen_avainsanat($tuoterow, 'nimitys'));
        }
        else {
          $prn .= sprintf('%-40.38s',   t_tuotteen_avainsanat($tuoterow, 'nimitys'));
        }

        $excelrivit[$xr]['nimitys'] =  t_tuotteen_avainsanat($tuoterow, 'nimitys');
      }
      else {

        if ($yhtiorow['laaja_inventointilista'] != "") {
          $prn .= sprintf('%-55.53s',   t_tuotteen_avainsanat($tuoterow, 'nimitys'));
        }
        else {
          // Jos toim_tuoteno ei nnäytetä, tämä voi olla pidempi
          $prn .= sprintf('%-60.58s',   t_tuotteen_avainsanat($tuoterow, 'nimitys'));
        }

        $excelrivit[$xr]['nimitys'] =  t_tuotteen_avainsanat($tuoterow, 'nimitys');
      }

      if ($naytasaldo == 'H') {
        if ($rivipaikkahyllyssa != $rivivarastohyllyssa) {
          $prn .= sprintf('%-10.10s', $rivipaikkahyllyssa."(".$rivivarastohyllyssa.")");
          $excelrivit[$xr]['hyllyssä'] =  $rivipaikkahyllyssa."(".$rivivarastohyllyssa.")";
        }
        else {
          $prn .= sprintf('%-10.10s', $rivipaikkahyllyssa);
          $excelrivit[$xr]['hyllyssä'] =  $rivipaikkahyllyssa;
        }
      }
      elseif ($naytasaldo == 'S') {
        if ($rivipaikkasaldo != $rivivarastosaldo) {
          $prn .= sprintf('%-10.10s', $rivipaikkasaldo."(".$rivivarastosaldo.")");
          $excelrivit[$xr]['saldo'] =  $rivipaikkasaldo."(".$rivivarastosaldo.")";
        }
        else {
          $prn .= sprintf('%-10.10s', $rivipaikkasaldo);
          $excelrivit[$xr]['saldo'] =  $rivipaikkasaldo;
        }
      }

      $prn .= sprintf('%-7.7s',   "_____");
      $excelrivit[$xr]['määrä'] = ' ';

      $prn .= sprintf('%-9.9s',   t_avainsana("Y", "", "and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite"));
      $excelrivit[$xr]['yksikkö'] = t_avainsana("Y", "", "and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite");


      //katsotaan onko tuotetta tilauksessa
      $query = "SELECT sum(varattu) varattu, min(toimaika) toimaika
                FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
                WHERE yhtio = '$kukarow[yhtio]'
                and tuoteno = '$tuoterow[tuoteno]'
                and varattu > 0
                and tyyppi  = 'O'";
      $result1 = pupe_query($query);
      $prow    = mysql_fetch_assoc($result1);

      $prn .= sprintf('%-7.7d',   $prow["varattu"]);
      $excelrivit[$xr]['tilkpl'] = $prow["varattu"];

      //Haetaan kerätty määrä
      $query = "SELECT ifnull(sum(if(keratty!='',tilausrivi.varattu,0)),0) keratty,  ifnull(sum(tilausrivi.varattu),0) ennpois
                FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
                WHERE yhtio    = '{$kukarow['yhtio']}'
                and tyyppi     in ('L','G','V')
                and tuoteno    = '{$tuoterow['tuoteno']}'
                and varattu    <> 0
                and laskutettu = ''
                and hyllyalue  = '{$tuoterow['hyllyalue']}'
                and hyllynro   = '{$tuoterow['hyllynro']}'
                and hyllyvali  = '{$tuoterow['hyllyvali']}'
                and hyllytaso  = '{$tuoterow['hyllytaso']}'";
      $hylresult = pupe_query($query);
      $hylrow = mysql_fetch_assoc($hylresult);

      $hylrow['ennpois'] = fmod($hylrow['ennpois'], 1) == 0 ? round($hylrow['ennpois']) : $hylrow['ennpois'];
      $hylrow['keratty'] = fmod($hylrow['keratty'], 1) == 0 ? round($hylrow['keratty']) : $hylrow['keratty'];

      if ($yhtiorow['laaja_inventointilista'] != "") {
        $prn .= sprintf('%-7.7s', "{$hylrow['ennpois']}/{$hylrow['keratty']}");
        $prn .= sprintf('%-5.5s', str_pad($rivinro, 5, " ", STR_PAD_LEFT));
      }
      else {
        $prn .= sprintf('%-13.13s', "{$hylrow['ennpois']}/{$hylrow['keratty']}");
      }

      $excelrivit[$xr]['varattu/ker'] = "{$hylrow['ennpois']}/{$hylrow['keratty']}";

      if ($tuoterow["sarjanumeroseuranta"] != "") {
        $query = "SELECT sarjanumeroseuranta.sarjanumero,
                  sarjanumeroseuranta.siirtorivitunnus,
                  tilausrivi_osto.nimitys,
                  tilausrivi_osto.perheid2 osto_perheid2,
                  tilausrivi_osto.tunnus osto_rivitunnus,
                  sarjanumeroseuranta.tunnus,
                  round(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl, 2) ostohinta,
                  era_kpl
                  FROM sarjanumeroseuranta
                  LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
                  LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                  WHERE sarjanumeroseuranta.yhtio           = '{$kukarow['yhtio']}'
                  and sarjanumeroseuranta.tuoteno           = '{$tuoterow['tuoteno']}'
                  and sarjanumeroseuranta.myyntirivitunnus != -1
                  and (  (sarjanumeroseuranta.hyllyalue    = '{$tuoterow['hyllyalue']}'
                       and sarjanumeroseuranta.hyllynro     = '{$tuoterow['hyllynro']}'
                       and sarjanumeroseuranta.hyllyvali    = '{$tuoterow['hyllyvali']}'
                       and sarjanumeroseuranta.hyllytaso    = '{$tuoterow['hyllytaso']}')
                     or ('{$tuoterow['oletus']}' != '' and
                      (  SELECT tunnus
                        FROM tuotepaikat tt
                        WHERE sarjanumeroseuranta.yhtio     = tt.yhtio and sarjanumeroseuranta.tuoteno = tt.tuoteno and sarjanumeroseuranta.hyllyalue = tt.hyllyalue
                        and sarjanumeroseuranta.hyllynro    = tt.hyllynro and sarjanumeroseuranta.hyllyvali = tt.hyllyvali and sarjanumeroseuranta.hyllytaso = tt.hyllytaso) is null))
                  and ((tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') and tilausrivi_osto.laskutettuaika != '0000-00-00')
                  ORDER BY sarjanumero";
        $sarjares = pupe_query($query);

        if (mysql_num_rows($sarjares) > 0) {

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {

            if ($sarjarow["nimitys"] == $tuoterow["nimitys"]) $sarjarow["nimitys"] = "";

            if ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"]) {
              $ztun = $sarjarow["osto_perheid2"];
            }
            else {
              $ztun = $sarjarow["siirtorivitunnus"];
            }

            if ($ztun > 0) {
              $query = "SELECT tilausrivi.tuoteno, sarjanumeroseuranta.sarjanumero
                        FROM tilausrivi
                        LEFT JOIN sarjanumeroseuranta ON (tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus)
                        WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.tunnus='$ztun'";
              $siires = pupe_query($query);
              $siirow = mysql_fetch_assoc($siires);

              $fnlina22 = " / ".t("Lisävarusteena").": {$siirow['tuoteno']} {$siirow['sarjanumero']}";
            }
            else {
              $fnlina22 = "";
            }

            $prn .= "\n";

            $prn .= sprintf('%-28.28s', "");
            $prn .= sprintf('%-42.42s', $sarjarow["sarjanumero"]);
            $prn .= sprintf('%-74.74s', $sarjarow["nimitys"].$fnlina22);

            if ($rivit >= $maxrivit) {
              fwrite($fh, $ots);
              $rivit = 1;
            }
          }
        }
      }

      $prn .= "\n";
      $prn .= "_______________________________________________________________________________________________________________________________________$katkoviiva";

      fwrite($fh, $prn);
      $rivit++;
      $xr++;
      $rivinro++;
    }

    fclose($fh);

    //käännetään kauniiksi

    if ($debug == '1') {
      echo "filenimi = {$filenimi}<br>";
    }
    else {
      $params = array(
        'chars'    => $rivinleveys,
        'filename' => $filenimi,
        'mode'     => 'landscape',
      );

      // konveroidaan postscriptiksi
      $filenimi_ps = pupesoft_a2ps($params);

      if ($komento["Inventointi"] == "-88") {
        system("ps2pdf -sPAPERSIZE=a4 {$filenimi_ps} ".$filenimi.".pdf");

        js_openFormInNewWindow();

        echo "<br><form id='inventointi_listat_{$listanro}' name='inventointi_listat_{$listanro}' method='post' action='{$palvelin2}inventointi_listat.php' autocomplete='off'>
              <input type='hidden' name='tee' value='NAYTATILAUS'>
              <input type='hidden' name='nayta_pdf' value='1'>
              <input type='hidden' name='filenimi' value='{$filenimi}.pdf'>
              <input type='submit' value='".t("Inventointilista").": {$listanro}' onClick=\"js_openFormInNewWindow('inventointi_listat_{$listanro}', ''); return false;\"></form><br>";
      }
      elseif ($komento["Inventointi"] == 'email') {

        system("ps2pdf -sPAPERSIZE=a4 {$filenimi_ps} ".$filenimi.".pdf");

        $liite = $filenimi.".pdf";
        $kutsu = t("Inventointilista")."_$listanro";

        require "inc/sahkoposti.inc";
      }
      elseif ($komento["Inventointi"] == 'excel') {

        include 'inc/pupeExcel.inc';

        $worksheet    = new pupeExcel();
        $excelrivi    = 0;
        $excelsarake = 0;

        $worksheet->writeString($excelrivi++, $excelsarake++, $excel_info, array("bold" => TRUE));

        $excelrivi++;
        $excelsarake = 0;

        foreach ($excelheaderit as  $value) {
          $worksheet->writeString($excelrivi, $excelsarake++, $value, array("bold" => TRUE));
        }

        $excelrivi++;
        $excelsarake = 0;

        foreach ($excelrivit as $key => $value) {

          if ($yhtiorow['laaja_inventointilista'] != "") {
            $worksheet->writeString($excelrivi, 0, $excelrivit[$key]['rivinro']);
            $excelsarake = 1;
          }

          foreach ($excelrivit[$key] as $_k => $value) {
            if ($yhtiorow['laaja_inventointilista'] != "" and $_k == 'rivinro') {
              continue;
            }

            $worksheet->writeString($excelrivi, $excelsarake++, $value);
          }

          if ($yhtiorow['laaja_inventointilista'] != "") {
            $worksheet->writeString($excelrivi, $excelsarake, $excelrivit[$key]['rivinro']);
          }

          $excelrivi++;
          $excelsarake = 0;
        }

        $excelnimi = $worksheet->close();

        $lopullinen_nimi = $kutsu = t("Inventointilista") . "_" . $listanro . ".xlsx";

        echo "<table>";
        echo "<tr><th>".t("Tallenna Excel-tiedosto").":</th>";
        echo "<form method='post' class='multisubmit'>";
        echo "<input type='hidden' name='toim' value='$toim'>";
        echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
        echo "<input type='hidden' name='kaunisnimi' value='$lopullinen_nimi'>";
        echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
        echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
        echo "</table><br>";

        require "inc/footer.inc";
        die;

      }
      elseif ($komento["Inventointi"] != '') {
        // itse print komento...
        $line = exec("{$komento['Inventointi']} {$filenimi_ps}");
      }

      echo "<font class='message'>", t("Inventointilista tulostuu!"), "</font><br><br>";

      //poistetaan tmp file samantien kuleksimasta...
      unlink($filenimi);
      unlink($filenimi_ps);
    }

    $tee = "";
  }
}

require "inc/footer.inc";
