<?php

if (!isset($alv)) $alv = "";
if (!isset($kentta)) $kentta = "";
if (!isset($kommentti)) $kommentti = "";
if (!isset($netto)) $netto = "";
if (!isset($perheid2)) $perheid2 = "";
if (!isset($rivinumero)) $rivinumero = "";
if (!isset($rivitunnus)) $rivitunnus = "";
if (!isset($keratty)) $keratty = "";
if (!isset($kerattyaika)) $kerattyaika = "";
if (!isset($toimitettu)) $toimitettu = "";
if (!isset($toimitettuaika)) $toimitettuaika = "";
if (!isset($toimaika)) $toimaika = "";
if (!isset($var)) $var = "";
if (!isset($varaosavirhe)) $varaosavirhe = "";

$debug = 0;

if ($debug == 1) {
  echo " kukarow[extranet] = $kukarow[extranet] | toim = $toim | miniliittyma = $miniliittyma | syotarivi = $syotarivi | rivinumero = $rivinumero | tila = $tila | tapa = $tapa | keratty = $keratty <br>";
}

$variaaritorivi = FALSE;

if ($yhtiorow['variaatiomyynti'] == "K" and $kukarow["extranet"] == '' and (is_array($tuoteno_array) or $tuoteno != "" or $variaatio_tuoteno) and $ulos == "" and $rivitunnus == "") {
  require "syota_variaatiorivi.inc";
}

if (!$variaaritorivi and $kukarow["extranet"] == '' and $toim == "PIKATILAUS") {
  // Pikatilauksen syöttörivi
  echo "<table><tr>$jarjlisa";

  if ($tila == "LISAAKERTARESEPTIIN" or $tila == "LISAAISAKERTARESEPTIIN" or $tila == "LISAARESEPTIIN") {
    echo "<th align='left'>".t("Reseptiin")."</th>";
  }

  if ($yhtiorow['myyntitilausrivi_rekisterinumero'] == 'K') {
    $tilauksen_rekisterinumerot = hae_tilauksen_rekisterinumerot($laskurow['tunnus']);
    echo "  <th align='left'>".t("Rekisterinumero")."</th>";
  }

  echo "  <th align='left'>".t("Tuotenumero")."</th>
      <th align='left'>".t("Määrä")."</th>
      <th align='left'>".t("Tila")."</th>";

  if ($kukarow['hinnat'] == 0) echo "<th align='left'>".t("Hinta")."</th>";

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    echo "<th align='left'>".t("Ale")."{$alepostfix}</th>";
  }

  echo "  <th align='left'>".t("Netto")."</th>";

  if ($yhtiorow['tilausrivi_omalle_tilaukselle'] != '' and $tuoteno != '') {
    echo "<th align='left'>", t("Omalle tilaukselle"), "</th>";
  }

  if ($yhtiorow['tilausrivin_esisyotto'] == 'K' and $naytetaanko_kate) {
    echo "<th align='left'>", t("Yks.hinta"), "</th>";
    echo "<th align='left'>", t("Rivihinta"), "</th>";
    echo "<th align='left'>", t("Kate"), "</th>";
  }

  echo "  <td valign='top' class='back'></td>
      </tr>";

  echo "<tr>$jarjlisa";

  if ($tila == "LISAAKERTARESEPTIIN") {
    echo "<td valign='top' style='color: #00FF00;'>".t("Raaka-Aine")."</td>";
  }
  if ($tila == "LISAAISAKERTARESEPTIIN") {
    echo "<td valign='top' style='color: #00FF00;'>".t("Valmiste")."</td>";
  }
  if ($tila == "LISAARESEPTIIN") {
    echo "<td valign='top' style='color: #00FF00;'>".t("Tuote")."</td>";
  }

  if ($yhtiorow['myyntitilausrivi_rekisterinumero'] == 'K') {
    echo "<td valign='top'>";
    echo "<input type='text' name='rekisterinumero' size='8' value='{$rekisterinumero}'/>";
    echo "<br/>";
    if (count($tilauksen_rekisterinumerot) > 0) {
      echo "<select name='jo_syotetty_rekisterinumero'>";
      echo "<option value=''>".t('Ei valintaa')."</option>";
      $i = 0;
      foreach ($tilauksen_rekisterinumerot as $syotetty_rekisterinumero) {
        $sel = "";
        if ($i == 0) {
          $sel = "SELECTED";
        }
        echo "<option value='{$syotetty_rekisterinumero['rekisterinumero']}' {$sel}>{$syotetty_rekisterinumero['rekisterinumero']}</option>";
        $i++;
      }
      echo "</select>";
    }
    echo "</td>";
  }

  if ($ulos == '') {
    if (in_array($yhtiorow["livetuotehaku_tilauksella"], array("J", "K"))) {
      echo "<td valign='top'>".livesearch_kentta("tilaus", "TUOTEHAKU", "tuoteno", 200, $tuoteno)."</td>";
    }
    else {
      echo "<td valign='top'><input type='text' name='tuoteno' size='15' maxlength='30' value='$tuoteno'></td>";
    }
  }
  else {
    echo "<td valign='top'>$ulos</td>";
  }

  echo "  <td valign='top'><input type='text' name='kpl' size='5' maxlength='13' value='$kpl'></td>
      <td valign='top'><input type='text' name='var' size='2' maxlength='1' value='$var'></td>";

  if ($kukarow['hinnat'] == 0) {
    $siemenluku = strlen((int) $hinta) > 5 ? strlen((int) $hinta) : 5;

    echo "<td valign='top'><input type='text' name='hinta' size='".($siemenluku+$yhtiorow['hintapyoristys'])."' maxlength='".(12+$yhtiorow['hintapyoristys']-2)."' value='$hinta'></td>";
  }

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    echo "<td valign='top'>";

    if (strpos((string) $yhtiorow['myynnin_alekentat_muokkaus'], (string) $alepostfix) !== FALSE) {
      echo "<input type='text' name='ale{$alepostfix}' size='5' maxlength='6' value='", ${'ale'.$alepostfix}, "' />";
    }
    else {
      echo "<input type='hidden' name='ale{$alepostfix}' value='", ${'ale'.$alepostfix}, "' />", ${'ale'.$alepostfix};
    }

    echo "</td>";
  }

  echo "  <td valign='top'><input type='text' name='netto' size='2' maxlength='1' value='$netto'></td>";

  if ($yhtiorow['tilausrivi_omalle_tilaukselle'] != '' and $tuoteno != '') {
    $sel = (isset($omalle_tilaukselle) and trim($omalle_tilaukselle) != '') ? ' selected' : '';

    echo "<td><select name='omalle_tilaukselle'>";
    echo "<option value=''>", t("Ei"), "</option>";
    echo "<option value='K'{$sel}>", t("Kyllä"), "</option>";
    echo "</select></td>";
  }

  if ($yhtiorow['tilausrivin_esisyotto'] == 'K' and $naytetaanko_kate) {
    echo "<td><span id='ykshinta_rivi_laskenta'></span></td>";
    echo "<td><span id='rivihinta_rivi_laskenta'></span></td>";
    echo "<td><span id='kate_rivi_laskenta'></span></td>";
  }

  echo "  <td valign='top' class='back'><input type='submit' class='lisaa_btn' value='".t("Lisää")."'></td>
      <td valign='top' class='back'>
      <input type='hidden' name='perheid' value = '$perheid'>
      <input type='hidden' name='perheid2' value = '$perheid2'>
      <input type='hidden' name='keratty' value = '$keratty'>
      <input type='hidden' name='kerattyaika' value = '$kerattyaika'>
      <input type='hidden' name='toimitettu' value = '$toimitettu'>
      <input type='hidden' name='toimitettuaika' value = '$toimitettuaika'>
      <input type='hidden' name='takaisin' value = '$takaisin'>
      <input type='submit' class='poista_btn' name='tyhjenna' value='".t("Tyhjennä")."'> <font class='error'>$varaosavirhe</font></td>
    </tr>";

  // jos meillä on rivi muuttuja täällä, niin ollaan muokkaamassa rivejä..
  if ($rivitunnus != '') {

    // ALVimuutoksen takia: eli päässätään rivin alvi jos sitä muokataan niin se ei automaattisesti vaihdu oletukseksi
    echo "<input type='hidden' name='alv' value = '$alv'>";

    $nrospanni = 6;

    if ($kukarow['hinnat'] != 0) {
      $nrospanni--;
    }

    $nrospanni += $yhtiorow['myynnin_alekentat'] - 1;

    if ($yhtiorow['tilausrivi_omalle_tilaukselle'] != '' and $tuoteno != '') {
      $nrospanni++;
    }

    if ($yhtiorow['tilausrivin_esisyotto'] == 'K' and $naytetaanko_kate) {
      $nrospanni += 3;
    }

    // Jos "Tilaukset jaetaan osiin keräyspäivän mukaan" niin näytetään päivien syöttökentät per tilaus
    if ($yhtiorow["splittauskielto"] == "K") {
      echo "<tr>$jarjlisa";
      echo "<th align='left' colspan='".floor($nrospanni/2)."'>".t("Keräysaika")."</th>";
      echo "<th align='left' colspan='".ceil($nrospanni/2)."'>".t("Toimitusaika")."</th>";
      echo "</tr>";
    }

    if ($toimaika == "" or $toimaika == "0000-00-00") {
      $toimaika = $laskurow["toimaika"];
    }

    if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
      $kerayspvm = $laskurow["kerayspvm"];
    }

    if ($toimaika == "" ) {
      $toimkka = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $toimvva = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $toimppa = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    }
    else {
      $toimkka = substr($toimaika, 5, 2);
      $toimvva = substr($toimaika, 0, 4);
      $toimppa = substr($toimaika, 8, 2);
    }

    if ($kerayspvm == "") {
      $kerayskka = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $keraysvva = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $keraysppa = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    }
    else {
      $kerayskka = substr($kerayspvm, 5, 2);
      $keraysvva = substr($kerayspvm, 0, 4);
      $keraysppa = substr($kerayspvm, 8, 2);
    }

    // Jos "Tilaukset jaetaan osiin keräyspäivän mukaan" niin näytetään päivien syöttökentät per tilaus
    if ($yhtiorow["splittauskielto"] == "K") {
      echo "<tr>$jarjlisa";
      echo "<td valign='top' colspan='".floor($nrospanni/2)."'><input type='text' size='3' name='keraysppa' value='$keraysppa'><input type='text' size='3' name='kerayskka' value='$kerayskka'><input type='text' size='5' name='keraysvva' value='$keraysvva'></td>";
      echo "<td valign='top' colspan='".ceil($nrospanni/2)."'><input type='text' size='3' name='toimppa' value='$toimppa'><input type='text' size='3' name='toimkka' value='$toimkka'><input type='text' size='5' name='toimvva' value='$toimvva'></td>";
      echo "</tr>";
    }
    else {
      echo "<input type='hidden' name='keraysppa' value='$keraysppa'>";
      echo "<input type='hidden' name='kerayskka' value='$kerayskka'>";
      echo "<input type='hidden' name='keraysvva' value='$keraysvva'>";
      echo "<input type='hidden' name='toimppa' value='$toimppa'>";
      echo "<input type='hidden' name='toimkka' value='$toimkka'>";
      echo "<input type='hidden' name='toimvva' value='$toimvva'>";
    }

    if ($yhtiorow['rivinumero_syotto'] != '') {
      $nrospanni--;
    }

    echo "<tr>$jarjlisa<th colspan='$nrospanni' align='left'>".t("Rivikommentti").":</th>";

    if ($yhtiorow['rivinumero_syotto'] != '') {
      echo "<th align='left'>".t("Rivinro")."</th>";
    }
    echo "</tr>";

    echo "<tr>$jarjlisa<td valign='top' colspan='$nrospanni'><textarea name='kommentti' cols='70' rows='2' wrap='soft'>$kommentti</textarea></td>";

    if ($yhtiorow['rivinumero_syotto'] != '') {
      echo "<td valign='top'><input type='text' size='2' name='tilrivinumero' value='$rivinumero'></td>";
      $nrospanni++;
    }
    echo "</tr>";
  }

  if ($rivitunnus != '') {
    if ($yhtiorow["nimityksen_muutos_tilauksella"] == "Y" and (($toim == "TARJOUS" or $toim == "EXTTARJOUS") or $laskurow["tilaustyyppi"] == "T")) {
      echo "<tr>{$jarjlisa}
          <th colspan='{$nrospanni}' align='left'>", t("Tuotteen nimitys"), "</th>
          </tr><tr>{$jarjlisa}
          <td valign='top' colspan='{$nrospanni}'><input type='text' maxlength='100' size='50' name='tuotenimitys' value = '{$tuotenimitys}'></td></tr>";
    }

    //Jos meillä on marginaalimyyntiä/käänteinen alv niin pitää muistaa ja kertoa se myös lisääriville
    if (isset($alv) and $alv >= 500) {
      echo "<input type='hidden' name='alv' value='$alv'>";
    }

    echo "<input type='hidden' name='rivitunnus' value='$rivitunnus'>";
    echo "<input type='hidden' name='ale_peruste' value='$ale_peruste'>";
    echo "<input type='hidden' name='rivilaadittu' value='$rivilaadittu'>";
    echo "<input type='hidden' name='menutila' value='$menutila'>";
    echo "<input type='hidden' name='rivinumero' value='$rivinumero'>";
    echo "<input type='hidden' name='myy_sarjatunnus' value='$myy_sarjatunnus'>";
    echo "<input type='hidden' name='jaksotettu' value='$jaksotettu'>";
    echo "<input type='hidden' name='paikka' value='$paikka'>";
    echo "<input type='hidden' name='kpl2' value='$kpl2'>";
  }

  echo "</table></form>";
}
elseif (!$variaaritorivi and $kukarow["extranet"] == '' and ($toim == "RIVISYOTTO" or $toim == "ENNAKKO" or $toim == "EXTENNAKKO" or $toim == "VALMISTAASIAKKAALLE" or $toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or (isset($aputoim) and $aputoim == "RIVISYOTTO") or $toim == "YLLAPITO" or $toim == "TARJOUS" or $toim == "EXTTARJOUS" or $toim == "REKLAMAATIO")) {
  // Rivisyötön syöttörivi
  echo "<table>
      <tr>$jarjlisa";

  if ($tila == "LISAAKERTARESEPTIIN" or $tila == "LISAAISAKERTARESEPTIIN" or $tila == "LISAARESEPTIIN") {
    echo "<th align='left'>".t("Reseptiin")."</th>";
  }

  if ($yhtiorow['myyntitilausrivi_rekisterinumero'] == 'K' and in_array($toim, array('RIVISYOTTO', 'TARJOUS', 'REKLAMAATIO'))) {
    $tilauksen_rekisterinumerot = hae_tilauksen_rekisterinumerot($laskurow['tunnus']);
    echo "  <th align='left'>".t("Rekisterinumero")."</th>";
  }

  echo "  <th align='left'>".t("Tuoteno")."</th>
      <th align='left'>".t("Määrä")."</th>
      <th align='left'>".t("Tila")."</th>";

  if ($kukarow['hinnat'] == 0) echo "<th align='left'>".t("Hinta")."</th>";

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    echo "<th align='left'>".t("Ale")."{$alepostfix}</th>";
  }

  echo "  <th align='left'>".t("Netto")."</th>
      <th align='left'>".t("Alv")."</th>";

  if ($yhtiorow['tilausrivi_omalle_tilaukselle'] != '' and $tuoteno != '') {
    echo "<th align='left'>", t("Omalle tilaukselle"), "</th>";
  }

  if ($yhtiorow['tilausrivin_esisyotto'] == 'K' and $naytetaanko_kate and in_array($toim, array('RIVISYOTTO', 'TARJOUS'))) {
    echo "<th align='left'>", t("Yks.hinta"), "</th>";
    echo "<th align='left'>", t("Rivihinta"), "</th>";
    echo "<th align='left'>", t("Kate"), "</th>";
  }

  if ($toim == "VALMISTAASIAKKAALLE" and $yhtiorow['rekursiiviset_reseptit'] == 'X') {
    echo "<th align='left'>", t("Pura lapset"), "</th>";
  }

  if ($yhtiorow['rivinumero_syotto'] != '') {
    echo "<th align='left'></th>";
  }

  echo "<td valign='top' class='back'></td></tr>";

  echo "<tr>$jarjlisa";

  if ($tila == "LISAAKERTARESEPTIIN") {
    echo "<td valign='top' style='color: #00FF00;'>".t("Raaka-Aine")."</td>";
  }
  if ($tila == "LISAAISAKERTARESEPTIIN") {
    echo "<td valign='top' style='color: #00FF00;'>".t("Valmiste")."</td>";
  }
  if ($tila == "LISAARESEPTIIN") {
    echo "<td valign='top' style='color: #00FF00;'>".t("Tuote")."</td>";
  }

  if ($yhtiorow['myyntitilausrivi_rekisterinumero'] == 'K' and in_array($toim, array('RIVISYOTTO', 'TARJOUS', 'REKLAMAATIO'))) {
    echo "<td valign='top'>";
    echo "<input type='text' name='rekisterinumero' size='8' value='{$rekisterinumero}'/>";
    if (count($tilauksen_rekisterinumerot) > 0) {
      echo "<br/>";
      echo "<select name='jo_syotetty_rekisterinumero'>";
      echo "<option value=''>".t('Ei valintaa')."</option>";
      $sel = "SELECTED";
      foreach ($tilauksen_rekisterinumerot as $syotetty_rekisterinumero) {
        if ($syotetty_rekisterinumero['rekisterinumero'] == '') {
          $sel = '';
          continue;
        }
        echo "<option value='{$syotetty_rekisterinumero['rekisterinumero']}' {$sel}>{$syotetty_rekisterinumero['rekisterinumero']}</option>";
        $sel = '';
      }
      echo "</select>";
    }
    echo "</td>";
  }

  if ($ulos == '') {
    if (in_array($yhtiorow["livetuotehaku_tilauksella"], array("J", "K"))) {
      // Laitetaan $tila "submit" parametriin, joten jos siellä on jotain ei submitata (valmistuksessa)
      echo "<td valign='top'>".livesearch_kentta("tilaus", "TUOTEHAKU", "tuoteno", 200, $tuoteno, $tila)."</td>";
    }
    else {
      echo "<td valign='top'><input type='text' name='tuoteno' size='15' maxlength='30' value='$tuoteno'></td>";
    }
  }
  else {
    echo "<td valign='top'>$ulos</td>";
  }

  echo "<td valign='top'><input type='text' size='5' name='kpl' maxlength='13' value='$kpl'></td>";

  $selh = $selj = $selp = $selo = "";

  if ($var == "H") {
    $selh = "SELECTED";
  }
  elseif ($var == "J") {
    $selj = "SELECTED";
  }
  elseif ($var == "P") {
    $selp = "SELECTED";
  }
  elseif ($var == "W") {
    $selw = "SELECTED";
  }
  elseif ($var == "O") {
    $selo = "SELECTED";
  }

  echo "<td valign='top'><select name='var'>";
  if ($toim == "EXTENNAKKO") {
    echo "<option value='O' $selo>".t("Optio")."</option>";
  }
  echo "<option value=''>".t("Oletus")."</option>";
  echo "<option value='J' $selj>".t("Jälkitoim")."</option>";
  echo "<option value='H' $selh>".t("Hyväksy")."</option>";
  echo "<option value='P' $selp>".t("Puute")."</option>";

  if ($laskurow["tila"] == 'V' and $laskurow["tilaustyyppi"] == 'V') {
    echo "<option value='W' $selw>".t("Varastosta")."</option>";
  }

  echo "</select></td>";

  if ($kukarow['hinnat'] == 0) {
    $siemenluku = strlen((int) $hinta) > 5 ? strlen((int) $hinta) : 5;

    echo "<td valign='top'><input type='text' size='".($siemenluku+$yhtiorow['hintapyoristys'])."' name='hinta' value='$hinta'></td>";
  }

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    echo "<td valign='top'>";

    if (strpos((string) $yhtiorow['myynnin_alekentat_muokkaus'], (string) $alepostfix) !== FALSE) {
      echo "<input type='text' name='ale{$alepostfix}' size='5' maxlength='6' value='", ${'ale'.$alepostfix}, "' />";
    }
    else {
      echo "<input type='hidden' name='ale{$alepostfix}' value='", ${'ale'.$alepostfix}, "' />", ${'ale'.$alepostfix};
    }

    echo "</td>";
  }

  $chke = $chkn = "";

  if ($netto == 'N') {
    $chkn = 'SELECTED';
  }
  elseif ($netto == "E") {
    $chke = 'SELECTED';
  }

  echo "<td valign='top'><select name='netto'>";
  echo "<option value=''>".t("Oletus")."</option>";
  echo "<option value='N' $chkn>".t("N-Netto")."</option>";
  echo "<option value='E' $chke>".t("E-Netto")."</option>";
  echo "</select></td>";

  // vaikka aluperin olisi marginaalimyyntiä/käänteinen alv (alv >= 500), saa alvia muuttaa rivillä
  if (isset($alv) and $alv >= 500 and $alv < 600) {
    echo "<td valign='top'>";
    echo t("M.V.");
    echo "</td>";
    echo "<input type='hidden' name='alv' value='$alv'>";
  }
  else {
    echo "<td valign='top'>".alv_popup_oletus('alv', $alv, $laskurow["maa"])."</td>";
  }

  if ($yhtiorow['tilausrivi_omalle_tilaukselle'] != '' and $tuoteno != '') {
    $sel = (isset($omalle_tilaukselle) and trim($omalle_tilaukselle) != '') ? ' selected' : '';

    echo "<td><select name='omalle_tilaukselle'>";
    echo "<option value=''>", t("Ei"), "</option>";
    echo "<option value='K'{$sel}>", t("Kyllä"), "</option>";
    echo "</select></td>";
  }

  if ($yhtiorow['tilausrivin_esisyotto'] == 'K' and $naytetaanko_kate and in_array($toim, array('RIVISYOTTO', 'TARJOUS'))) {
    echo "<td><span id='ykshinta_rivi_laskenta'></span></td>";
    echo "<td><span id='rivihinta_rivi_laskenta'></span></td>";
    echo "<td><span id='kate_rivi_laskenta'></span></td>";
  }

  if ($toim == "VALMISTAASIAKKAALLE" and $yhtiorow['rekursiiviset_reseptit'] == 'X') {
    echo "<td>";

    $sel = (!empty($avaa_rekursiiviset)) ? "selected" : "";

    echo "<select name='avaa_rekursiiviset'>";
    echo "<option value = ''>".t("Ei")."</option>";
    echo "<option value = 'JOO' $sel>".t("Kyllä")."</option>";
    echo "</select>";
    echo "</td>";
  }

  if ($yhtiorow['rivinumero_syotto'] != '') {
    echo "<td valign='top'></td>";
  }

  echo "<td valign='top' class='back'>";
  echo "<input type='hidden' name='aputapa' value='$tapa'>";
  echo "<input type='submit' class='lisaa_btn' value='".t("Lisää")."'> <font class='error'>$varaosavirhe</font></td></tr>";

  echo "<tr>$jarjlisa";

  if ($tila == "LISAAKERTARESEPTIIN" or $tila == "LISAAISAKERTARESEPTIIN" or $tila == "LISAARESEPTIIN") {
    echo "<th></th>";
  }

  $nrospanni = 6;

  if ($yhtiorow['myyntitilausrivi_rekisterinumero'] == 'K' and in_array($toim, array('RIVISYOTTO', 'TARJOUS', 'REKLAMAATIO', 'PIKATILAUS'))) {
    $nrospanni++;
  }

  if ($toim == "VALMISTAASIAKKAALLE" and $yhtiorow['rekursiiviset_reseptit'] == 'X') {
    $nrospanni++;
  }

  $nrospanni_vah = 0;

  if ($kukarow['hinnat'] == 0) {
    $nrospanni++;
  }

  if ($yhtiorow['tilausrivi_omalle_tilaukselle'] != '' and $tuoteno != '') {
    $nrospanni++;
  }

  if ($yhtiorow['tilausrivin_esisyotto'] == 'K' and $naytetaanko_kate and in_array($toim, array('RIVISYOTTO', 'TARJOUS'))) {
    $nrospanni += 3;
  }

  if ($yhtiorow['rivinumero_syotto'] != '') {
    $nrospanni++;
    $nrospanni_vah++;
  }

  $nrospanni += $yhtiorow['myynnin_alekentat'] - 1;

  if ($toim == "YLLAPITO") {
    echo "<th align='left'>".t("Poikkeava Alkupäivä")."</th>";
    echo "<th align='left' colspan='2'>".t("Poikkeava Loppupäivä")."</th>";
    $nrospanni_vah += 3;
  }
  elseif ($yhtiorow["splittauskielto"] == "K") {
    echo "<th align='left'>".t("Keräysaika")."</th>";
    echo "<th align='left' colspan='2'>".t("Toimitusaika")."</th>";
    $nrospanni_vah += 3;
  }

  echo "<th colspan='".($nrospanni-$nrospanni_vah)."' align='left'>".t("Rivikommentti")."</th>";

  if ($yhtiorow['rivinumero_syotto'] != '') {
    echo "<th align='left'>".t("Rivinro")."</th>";
  }

  echo "<td valign='top' class='back'></td></tr>";
  echo "<tr>$jarjlisa";

  if ($tila == "LISAAKERTARESEPTIIN" or $tila == "LISAAISAKERTARESEPTIIN" or $tila == "LISAARESEPTIIN") {
    echo "<td></td>";
  }

  if ($toim != "YLLAPITO") {
    if ($toimaika == "" or $toimaika == "0000-00-00") {
      $toimaika = $laskurow["toimaika"];
    }

    if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
      $kerayspvm = $laskurow["kerayspvm"];
    }

    if ($toimaika == "" ) {
      $toimkka = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $toimvva = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $toimppa = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    }
    else {
      $toimkka = substr($toimaika, 5, 2);
      $toimvva = substr($toimaika, 0, 4);
      $toimppa = substr($toimaika, 8, 2);
    }

    if ($kerayspvm == "" ) {
      $kerayskka = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $keraysvva = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $keraysppa = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    }
    else {
      $kerayskka = substr($kerayspvm, 5, 2);
      $keraysvva = substr($kerayspvm, 0, 4);
      $keraysppa = substr($kerayspvm, 8, 2);
    }
  }
  else {
    // Ylläpitosoppareissa toimaika tarkoittaa poikkeavaa sopimuksen alkupäivää ja keräyspvm poikkeavaa loppupäivää
    if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
      $kerayskka = "";
      $keraysvva = "";
      $keraysppa = "";
    }
    else {
      $kerayskka = substr($kerayspvm, 5, 2);
      $keraysvva = substr($kerayspvm, 0, 4);
      $keraysppa = substr($kerayspvm, 8, 2);
    }

    if ($toimaika == "" or $toimaika == "0000-00-00") {
      $toimkka = "";
      $toimvva = "";
      $toimppa = "";
    }
    else {
      $toimkka = substr($toimaika, 5, 2);
      $toimvva = substr($toimaika, 0, 4);
      $toimppa = substr($toimaika, 8, 2);
    }
  }

  // Jos Ylläpitosopimus tai "Tilaukset jaetaan osiin keräyspäivän mukaan" niin näytetään päivien syöttökentät per tilaus
  if ($toim == "YLLAPITO" or $yhtiorow["splittauskielto"] == "K") {
    echo "<td valign='top'><input type='text' size='3' name='keraysppa' value='$keraysppa'><input type='text' size='3' name='kerayskka' value='$kerayskka'><input type='text' size='5' name='keraysvva' value='$keraysvva'></td>";
    echo "<td valign='top' colspan='2'><input type='text' size='3' name='toimppa' value='$toimppa'><input type='text' size='3' name='toimkka' value='$toimkka'><input type='text' size='5' name='toimvva' value='$toimvva'></td>";
    $kommentit_columns = 50;
  }
  else {
    echo "<input type='hidden' name='keraysppa' value='$keraysppa'>";
    echo "<input type='hidden' name='kerayskka' value='$kerayskka'>";
    echo "<input type='hidden' name='keraysvva' value='$keraysvva'>";
    echo "<input type='hidden' name='toimppa' value='$toimppa'>";
    echo "<input type='hidden' name='toimkka' value='$toimkka'>";
    echo "<input type='hidden' name='toimvva' value='$toimvva'>";
    $kommentit_columns = 75;
  }

  echo "<td valign='top' colspan='".($nrospanni-$nrospanni_vah)."'><textarea name='kommentti' cols='$kommentit_columns' rows='2' wrap='soft'>$kommentti</textarea></td>";

  if ($yhtiorow['rivinumero_syotto'] != '') {
    echo "<td valign='top'><input type='text' size='2' name='tilrivinumero' value='$rivinumero'></td>";
  }

  if (($yhtiorow["nimityksen_muutos_tilauksella"] == "Y" and ($toim == "RIVISYOTTO" or $toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or (($toim == "TARJOUS" or $toim == "EXTTARJOUS") or $laskurow["tilaustyyppi"] == "T"))) or ($yhtiorow["nimityksen_muutos_tilauksella"] == "T" and ($toim == "TARJOUS" or $toim == "EXTTARJOUS"))) {
    echo "</tr>
      <tr>$jarjlisa
        <th colspan='{$nrospanni}' align='left'>".t("Tuotteen nimitys")."</th>
        </tr>
      <tr>$jarjlisa
        <td valign='top' colspan='{$nrospanni}'><input type='text' maxlength='100' size='75' name='tuotenimitys' value = '$tuotenimitys'></td>";
  }

  echo "<td valign='top' class='back'><input type='submit' class='poista_btn' name='tyhjenna' value='".t("Tyhjennä")."'>";

  if ($toim == "YLLAPITO") {

    // Katsotaan halutaanko dynaamisia kenttiä näkyviin
    $yllapito_dynamic_result = t_avainsana("SOPIMUS_KENTTA", "", "and avainsana.selitetark != ''");

    if (mysql_num_rows($yllapito_dynamic_result) > 0) {
      echo "</td></tr><tr><td colspan='7'>";
      echo "<table>";
      echo "<tr>";
      while ($yllapito_dynamic_row = mysql_fetch_assoc($yllapito_dynamic_result)) {
        echo "<th>{$yllapito_dynamic_row["selitetark"]}</th>";
        echo "<td><textarea name='{$yllapito_dynamic_row["selite"]}' cols='32' rows='4' wrap='soft'>".${$yllapito_dynamic_row["selite"]}."</textarea></td>";
      }
      echo "</tr>";
      echo "</table>";
    }
  }

  // jos meillä on rivi muuttuja täällä, niin ollaan muokkaamassa rivejä..
  if ($rivitunnus!='') {
    echo "<input type='hidden' name='rivitunnus' value='$rivitunnus'>";
    echo "<input type='hidden' name='keratty'      value = '$keratty'>";
    echo "<input type='hidden' name='kerattyaika'  value = '$kerattyaika'>";
    echo "<input type='hidden' name='toimitettu'   value = '$toimitettu'>";
    echo "<input type='hidden' name='toimitettuaika' value = '$toimitettuaika'>";
    echo "<input type='hidden' name='ale_peruste' value='$ale_peruste'>";
    echo "<input type='hidden' name='rivilaadittu' value='$rivilaadittu'>";
    echo "<input type='hidden' name='menutila' value='$menutila'>";
    echo "<input type='hidden' name='myy_sarjatunnus' value='$myy_sarjatunnus'>";
    echo "<input type='hidden' name='jaksotettu' value='$jaksotettu'>";
    echo "<input type='hidden' name='rivinumero' value='$rivinumero'>";
    echo "<input type='hidden' name='paikka' value='$paikka'>";
    echo "<input type='hidden' name='kpl2' value='$kpl2'>";
  }

  echo "<input type='hidden' name='perheid' value = '$perheid'>";
  echo "<input type='hidden' name='perheid2' value = '$perheid2'>";

  echo "</td></tr></table></form>";
}
elseif (!$variaaritorivi and $kukarow["extranet"] == '' and ($toim == "SIIRTOLISTA" or $toim == "MYYNTITILI" or $toim == "VALMISTAVARASTOON" or strpos($_SERVER['SCRIPT_NAME'], "valmista_tilaus.php") !== FALSE or $toim == "SIIRTOTYOMAARAYS")) {
  // Rivisyötön syöttörivi
  echo "<table>
      <tr>$jarjlisa";

  if ($tila == "LISAAKERTARESEPTIIN" or $tila == "LISAAISAKERTARESEPTIIN" or $tila == "LISAARESEPTIIN") {
    echo "<th align='left'>".t("Reseptiin")."</th>";
  }

  echo "<th align='left'>".t("Tuoteno")."</th>";
  echo "<th align='left'>".t("Määrä")."</th>";

  if ($toim == "VALMISTAVARASTOON" and $yhtiorow['valmistuksien_kasittely'] == 'Y') {
    echo "<th align='left'>".t("Valmistuslinja")."</th>";
  }

  if ($toim == "VALMISTAVARASTOON" and $yhtiorow['rekursiiviset_reseptit'] == 'X') {
    echo "<th align='left'>".t("Pura lapset")."</th>";
  }

  echo "</tr>";

  echo "<tr>$jarjlisa";

  if ($tila == "LISAAKERTARESEPTIIN") {
    echo "<input type='hidden' name='valmiste_vai_raakaaine' value='{$valmiste_vai_raakaaine}' />";
    echo "<td valign='top' style='color: #00FF00;'>".t("Raaka-Aine")."</td>";
  }
  if ($tila == "LISAAISAKERTARESEPTIIN") {
    echo "<input type='hidden' name='valmiste_vai_raakaaine' value='{$valmiste_vai_raakaaine}' />";
    echo "<td valign='top' style='color: #00FF00;'>".t("Valmiste")."</td>";
  }
  if ($tila == "LISAARESEPTIIN") {
    echo "<td valign='top' style='color: #00FF00;'>".t("Tuote")."</td>";
  }

  if ($ulos == '') {
    if (in_array($yhtiorow["livetuotehaku_tilauksella"], array("J", "K"))) {
      // Laitetaan $tila "submit" parametriin, joten jos siellä on jotain ei submitata (valmistuksessa)
      echo "<td valign='top'>".livesearch_kentta("tilaus", "TUOTEHAKU", "tuoteno", 200, $tuoteno, $tila)."</td>";
    }
    else {
      echo "<td valign='top'><input type='text' name='tuoteno' size='15' maxlength='30' value='$tuoteno'></td>";
    }
  }
  else {
    echo "<td valign='top'>$ulos</td>";
  }

  echo "<td valign='top'><input type='text' size='5' name='kpl' maxlength='13' value='$kpl'></td>";

  if ($toim == "VALMISTAVARASTOON" and $yhtiorow['valmistuksien_kasittely'] == 'Y') {
    echo "<td>";

    $valmistuslinja_result = t_avainsana("VALMISTUSLINJA");

    echo "<select name='valmistuslinja'>";
    echo "<option value = ''>".t("Oletus")."</option>";

    while ($valmistuslinja_row = mysql_fetch_array($valmistuslinja_result)) {
      $sel = ($valmistuslinja_row["selite"] == $valmistuslinja) ? "selected" : "";
      echo "<option value='{$valmistuslinja_row["selite"]}' $sel>{$valmistuslinja_row["selitetark"]}</option>";
    }

    echo "</select>";
    echo "</td>";
  }

  if ($toim == "VALMISTAVARASTOON" and $yhtiorow['rekursiiviset_reseptit'] == 'X') {
    echo "<td>";

    $sel = (!empty($avaa_rekursiiviset)) ? "selected" : "";

    echo "<select name='avaa_rekursiiviset'>";
    echo "<option value = ''>".t("Ei")."</option>";
    echo "<option value = 'JOO' $sel>".t("Kyllä")."</option>";
    echo "</select>";
    echo "</td>";
  }

  echo "<td valign='top' class='back'><input type='submit' class='lisaa_btn' value='".t("Lisää")."'> <font class='error'>$varaosavirhe</font></td></tr>";

  if (($toim == "VALMISTAVARASTOON" and $yhtiorow['valmistuksien_kasittely'] == 'Y') or $yhtiorow["splittauskielto"] == "K") {

    echo "<tr>$jarjlisa";

    if ($tila == "LISAAKERTARESEPTIIN" or $tila == "LISAAISAKERTARESEPTIIN" or $tila == "LISAARESEPTIIN") {
      echo "<th></th>";
    }

    if ($toim == "VALMISTAVARASTOON" and $yhtiorow['valmistuksien_kasittely'] == 'Y') {
      echo "<th align='left'>".t("Toivottu keräysajankohta")."</th>";
      echo "<th align='left' colspan='2'>".t("Toivottu valmistusajankohta")."</th>";
    }
    // Jos "Tilaukset jaetaan osiin keräyspäivän mukaan" niin näytetään päivien syöttökentät per tilaus
    elseif ($yhtiorow["splittauskielto"] == "K") {
      echo "  <th align='left'>".t("Keräysaika")."</th>
          <th align='left'>".t("Toimitusaika")."</th>";
    }

    echo "</tr>";

    echo "<tr>$jarjlisa";

    if ($tila == "LISAAKERTARESEPTIIN" or $tila == "LISAAISAKERTARESEPTIIN" or $tila == "LISAARESEPTIIN") {
      echo "<td valign='top'></td>";
    }

    if ($toimaika == "" or $toimaika == "0000-00-00") {
      $toimaika = $laskurow["toimaika"];
    }

    if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
      $kerayspvm = $laskurow["kerayspvm"];
    }

    if ($toimaika == "") {
      $toimkka = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $toimvva = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $toimppa = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    }
    else {
      $toimkka = substr($toimaika, 5, 2);
      $toimvva = substr($toimaika, 0, 4);
      $toimppa = substr($toimaika, 8, 2);
    }

    if ($kerayspvm == "") {
      $kerayskka = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $keraysvva = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $keraysppa = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    }
    else {
      $kerayskka = substr($kerayspvm, 5, 2);
      $keraysvva = substr($kerayspvm, 0, 4);
      $keraysppa = substr($kerayspvm, 8, 2);
    }

    // Jos "Tilaukset jaetaan osiin keräyspäivän mukaan" niin näytetään päivien syöttökentät per tilaus
    if ($yhtiorow["splittauskielto"] == "K" or ($toim == "VALMISTAVARASTOON" and $yhtiorow['valmistuksien_kasittely'] == 'Y')) {
      $colspan = $yhtiorow["splittauskielto"] == "K" ? 1 : 2;
      echo "<td valign='top'><input type='text' size='3' name='keraysppa' value='$keraysppa'><input type='text' size='3' name='kerayskka' value='$kerayskka'><input type='text' size='5' name='keraysvva' value='$keraysvva'></td>";
      echo "<td valign='top' colspan='{$colspan}'><input type='text' size='3' name='toimppa' value='$toimppa'><input type='text' size='3' name='toimkka' value='$toimkka'><input type='text' size='5' name='toimvva' value='$toimvva'></td>";
    }
    else {
      echo "<input type='hidden' name='keraysppa' value='$keraysppa'>";
      echo "<input type='hidden' name='kerayskka' value='$kerayskka'>";
      echo "<input type='hidden' name='keraysvva' value='$keraysvva'>";
      echo "<input type='hidden' name='toimppa' value='$toimppa'>";
      echo "<input type='hidden' name='toimkka' value='$toimkka'>";
      echo "<input type='hidden' name='toimvva' value='$toimvva'>";
    }

    echo "</tr>";
  }


  echo "<tr>$jarjlisa";

  if ($tila == "LISAAKERTARESEPTIIN" or $tila == "LISAAISAKERTARESEPTIIN" or $tila == "LISAARESEPTIIN") {
    echo "<th></th>";
  }

  if ($toim == "VALMISTAVARASTOON" and $yhtiorow['valmistuksien_kasittely'] == 'Y') {
    $colspan = 3;
  }
  elseif ($toim == "VALMISTAVARASTOON" and $yhtiorow['rekursiiviset_reseptit'] == 'X') {
    $colspan = 3;
  }
  else {
    $colspan = 2;
  }

  echo "<th colspan='{$colspan}' align='left'>".t("Kommentti")."</th>
      </tr>";

  echo "<tr>$jarjlisa";

  if ($tila == "LISAAKERTARESEPTIIN" or $tila == "LISAAISAKERTARESEPTIIN" or $tila == "LISAARESEPTIIN") {
    echo "<td valign='top'></td>";
  }

  echo "<td valign='top' colspan='{$colspan}'><textarea name='kommentti' cols='40' rows='2' wrap='soft'>$kommentti</textarea></td>
      <td valign='top' class='back'><input type='submit' class='poista_btn' name='tyhjenna' value='".t("Tyhjennä")."'>";

  // jos meillä on rivi muuttuja täällä, niin ollaan muokkaamassa rivejä..
  if ($rivitunnus!='') {
    echo "<input type='hidden' name='rivitunnus' value='$rivitunnus'>";
    echo "<input type='hidden' name='ale_peruste' value='$ale_peruste'>";
    echo "<input type='hidden' name='rivilaadittu' value='$rivilaadittu'>";
    echo "<input type='hidden' name='menutila' value='$menutila'>";
    echo "<input type='hidden' name='myy_sarjatunnus' value='$myy_sarjatunnus'>";
    echo "<input type='hidden' name='jaksotettu' value='$jaksotettu'>";
    echo "<input type='hidden' name='rivinumero' value='$rivinumero'>";
    echo "<input type='hidden' name='paikka' value='$paikka'>";
    echo "<input type='hidden' name='kpl2' value='$kpl2'>";
  }

  echo "<input type='hidden' name='perheid' value = '$perheid'>";
  echo "<input type='hidden' name='perheid2' value = '$perheid2'>";

  echo "</td></tr></table></form>";
}
elseif (!$variaaritorivi and $miniliittyma == "JOO") {

  echo "  <form name='rivi' action = '?ojarj=$array[0]$ulisa$ankka' method='post' autocomplete='off'>
      <input type='hidden' name='tee' value = 'TI'>
      <input type='hidden' name='toim' value = '$toim'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='hidden' name='toim_kutsu' value = '$toim_kutsu'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='oldmerkki' value = '$merkki'>
      <input type='hidden' name='oldcc' value = '$cc'>
      <input type='hidden' name='oldmalli' value = '$malli'>
      <input type='hidden' name='oldvm' value = '$vm'>
      <input type='hidden' name='merkki' value = '$merkki'>
      <input type='hidden' name='cc' value = '$cc'>
      <input type='hidden' name='malli' value = '$malli'>
      <input type='hidden' name='vm' value = '$vm'>";

  // näytetään tilauksen syöttörivi... aina.
  echo "<table>";
  echo "<tr>$jarjlisa";
  echo "<input type='hidden' name='tuoteno' value='$hi_tuoteno'>
      <td valign='top'><input type='text' name='kpl' size='5' maxlength='13' value='$kpl' onKeyUp='DisableText();'></td>";

  echo "  <td valign='top'> $varaosavirhe </td> ";

  echo "<td valign='top'><input type='submit' class='lisaa_btn' value='".t("Lisää")."'>";

  echo "</td></tr></table></form>";

}
elseif (!$variaaritorivi and $kukarow["extranet"] != '') {
  // Extranetin syöttörivi

  if ($toimaika == "" or $toimaika == "0000-00-00") {
    $toimkka = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    $toimvva = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    $toimppa = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
  }
  else {
    $toimkka = substr($toimaika, 5, 2);
    $toimvva = substr($toimaika, 0, 4);
    $toimppa = substr($toimaika, 8, 2);
  }

  echo "<input type='hidden' name='myy_sarjatunnus' value='{$myy_sarjatunnus}'>";

  echo "<table>
      <tr>$jarjlisa";

  echo "  <th align='left'>".t("Tuotenumero")."</th>
      <th align='left'>".t("Määrä")."</th>";

  if (isset($extranet_alennukset) and $extranet_alennukset == 'JOO') {
    for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
      echo "<th align='left'>".t("Ale")."{$alepostfix}</th>";
    }
  }

  echo"<tr>$jarjlisa";

  echo "<input type='hidden' name='var' value='$var'>";

  if ($ulos == '') {
    if ($yhtiorow["livetuotehaku_tilauksella"] == "J") {
      echo "<td valign='top'>" .
        livesearch_kentta("tilaus", "TUOTEHAKU", "tuoteno", 200, $tuoteno) .
        "</td>";
    }
    else {
      echo "<td valign='top'>
              <input type='text' name='tuoteno' size='15' maxlength='30' value='$tuoteno'>
            </td>";
    }
  }
  else {
    echo "<td valign='top'>$ulos</td>";
  }

  echo "  <td valign='top'><input type='text' name='kpl' size='5' maxlength='13' value='$kpl'></td>";

  if (isset($extranet_alennukset) and $extranet_alennukset == 'JOO') {
    for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
      echo "<td align='top'><input type='text' name='ale{$alepostfix}' size='5' maxlength='6' value='", ${'ale'.$alepostfix}, "' </td>";
    }
    $_th_lisa = "<th></th>";
    $_td_lisa = "<td></td>";
  }

  echo "
      <td valign='top' class='back'><input type='submit' class='lisaa_btn' value='".t("Lisää")."'></td>
      <td valign='top' class='back'><input type='submit' class='poista_btn' name='tyhjenna' value='".t("Tyhjennä")."'> <font class='error'>$varaosavirhe</font></td>
    </tr>";

  echo "<tr colspan='4'><th colspan='2'>".t("Toimitusaika")."</th>{$_th_lisa}</tr>";
  echo "<tr>";
  echo "<td valign='top' colspan='2'>";
  echo "<input type='text' size='3' name='toimppa' value='$toimppa'>";
  echo "<input type='text' size='3' name='toimkka' value='$toimkka'>";
  echo "<input type='text' size='5' name='toimvva' value='$toimvva'>";
  echo "</td>{$_td_lisa}</tr>";

  echo "<tr>$jarjlisa<th colspan='2'>".t("Rivikommentti").":</th>{$_th_lisa}</tr>";
  echo "<tr>$jarjlisa<td valign='top' colspan='2'><textarea name='kommentti' cols='30' rows='2' wrap='soft'>$kommentti</textarea>";
  echo "</td>{$_td_lisa}</tr></table></form>";
}

// Kursorinohjausta
if ($formi == '') {
  $formi = 'tilaus';
}

if ($kentta == '') {
  $kentta = 'tuoteno';
}
