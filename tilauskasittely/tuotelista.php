<?php

///* T�m� skripti k�ytt�� slave-tietokantapalvelinta *///
$useslave = 1;

if (@include "../inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

$kukarow['extranet'] = 'o';

enable_ajax();

// Liitetiedostot popup
if (isset($liite_popup_toiminto) and $liite_popup_toiminto == "AK") {
  liite_popup("AK", $tuotetunnus, $width, $height);
}
else {
  liite_popup("JS");
}

if (function_exists("js_popup")) {
  echo js_popup(-100);
}

// Jos tullaan sivuvalikosta extranetiss� tyhj�t��n kesken ettei lis�t� tuotteita v��r�lle tilaukselle
if ($kukarow['extranet'] != '') {
  $kukarow['kesken'] = '';
}

$query = "SELECT *
          FROM lasku
          WHERE tunnus = '$kukarow[kesken]'
          AND yhtio    = '$kukarow[yhtio]'";
$result   = pupe_query($query);
$laskurow = mysql_fetch_assoc($result);

// vientikieltok�sittely:
// +maa tarkoittaa ett� myynti on kielletty t�h�n maahan ja sallittu kaikkiin muihin
// -maa tarkoittaa ett� ainoastaan t�h�n maahan saa myyd�
// eli n�ytet��n vaan tuotteet jossa vienti kent�ss� on tyhj�� tai -maa.. ja se ei saa olla +maa

// Otetaan samassa queryss� selville mink� tyyppinen tilaus on k�ytt�j�ll� ollut kesken
// niin ettei esimerkiksi vied� k�ytt�j�� myyntitilaukselle jos h�nell� on ollut varastosiirto kesken
$kieltolisa = "";
unset($vierow);

if ($kukarow["kesken"] > 0) {
  $query  = "SELECT
             if (toim_maa != '', toim_maa, maa) maa,
             tila,
             liitostunnus,
             tilaustyyppi,
             clearing
             FROM lasku
             WHERE yhtio = '$kukarow[yhtio]'
             and tunnus  = '$kukarow[kesken]'";
  $vieres = pupe_query($query);
  $vierow = mysql_fetch_assoc($vieres);
}
elseif ($kukarow["extranet"] != "") {
  $query  = "SELECT if (toim_maa != '', toim_maa, maa) maa
             FROM asiakas
             WHERE yhtio = '$kukarow[yhtio]'
             and tunnus  = '$kukarow[oletus_asiakas]'";
  $vieres = pupe_query($query);
  $vierow = mysql_fetch_assoc($vieres);
}

if (isset($vierow) and $vierow["maa"] != "") {
  $kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' " .
    "or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
}

if ($kukarow["extranet"] != "") {
  $extra_poislisa = " and tuote.hinnastoon != 'E' ";
  $avainlisa      = " and avainsana.jarjestys < 10000 ";
}

$poislisa = " and (tuote.status not in ('P','X')
              or (SELECT sum(saldo)
              FROM tuotepaikat
              WHERE tuotepaikat.yhtio=tuote.yhtio
              AND tuotepaikat.tuoteno=tuote.tuoteno
              AND tuotepaikat.saldo > 0) > 0) ";

list($oleasrow, $valuurow) = hae_oletusasiakas($laskurow);

if ($kukarow["kuka"] != "" and $laskurow["tila"] != "") {

  if ($kukarow["extranet"] != "") {
    $tilauskasittely = "";
  }
  else {
    $tilauskasittely = "tilauskasittely/";
  }

  echo "  <form method='post' action='".$palvelin2.$tilauskasittely."tilaus_myynti.php'>
      <input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
      <input type='submit' value='".t("Takaisin tilaukselle")."'>
      </form><br><br>";
}

if (!isset($tee)) {
  $tee = '';
}

if (!isset($submit_button)) {
  $submit_button = '';
}

tarkista_tilausrivi();

$query = "SELECT tuote.*,
          ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi = 'P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
          ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi = 'V' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') osaluettelo
          FROM tuote
          JOIN asiakashinta on (tuote.yhtio=asiakashinta.yhtio and tuote.tuoteno=asiakashinta.tuoteno and asiakashinta.asiakas={$kukarow['oletus_asiakas']})
          WHERE tuote.yhtio = '$kukarow[yhtio]'
          and tuote.tuotetyyppi NOT IN ('A','B')
          ORDER BY tuote.tuoteno";
$result = pupe_query($query);

if (mysql_num_rows($result) > 0) {


  // Rakennetaan array
  while ($mrow = mysql_fetch_assoc($result)) {

    $rows[$mrow["tuoteno"]] = $mrow;

    if ($mrow["tuoteperhe"] == $mrow["tuoteno"]) {
      $riikoko = 1;
      $isat_array = array();
      $kaikki_array = array($mrow["tuoteno"]);

      for ($isa=0; $isa < $riikoko; $isa++) {
        list($isat_array, $kaikki_array, $rows) = tuoteselaushaku_tuoteperhe($mrow["tuoteno"], $kaikki_array[$isa], $isat_array, $kaikki_array, $rows, 'P');

        if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y") {
          $riikoko = count($kaikki_array);
        }
      }
    }

    if ($mrow["osaluettelo"] == $mrow["tuoteno"] ) {
      $riikoko = 1;
      $isat_array = array();
      $kaikki_array = array($mrow["tuoteno"]);

      for ($isa=0; $isa < $riikoko; $isa++) {
        list($isat_array, $kaikki_array, $rows) = tuoteselaushaku_tuoteperhe($mrow["tuoteno"], $kaikki_array[$isa], $isat_array, $kaikki_array, $rows, 'V');

        if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y") {
          $riikoko = count($kaikki_array);
        }
      }
    }
  }

  if (!empty($yhtiorow["saldo_kasittely"])) {
    $saldoaikalisa = date("Y-m-d");
  }
  else {
    $saldoaikalisa = "";
  }

  piirra_formin_aloitus();

  if (count($rows) == 1) {
    $muoto = 'tuote';
  }
  else {
    $muoto = 'tuotetta';
  }

  echo "&raquo;  ".count($rows)." ", t($muoto)."<br/><br/>";

  if (count($rows) > 0) {
    echo "<table>";
    echo "<tr>";

    echo "<td class='back'>&nbsp;</td>";
    echo "<th>&nbsp;</th>";

    echo "<th>".t("Tuoteno")."</a>";
    echo "</th>";

    echo "<th>".t("Nimitys")."</th>";

    if ($kukarow['hinnat'] >= 0) {
      echo "<th>".t("Hinta")."</th>";
    }

    if ($oikeurow["paivitys"] == 1 and $kukarow["kuka"] != "") {
      echo "<th>&nbsp;</th>";
    }

    echo "</tr>";
  }

  $yht_i     = 0;
  $alask     = 0;
  $isan_kuva = '';
  $bordercolor = " #555555";

  if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
    // Otetaan yhti�n css:st� SPEC_COLOR
    preg_match("/.*?\/\*(.*?(SPEC_COLOR))\*\//", $yhtiorow['active_css'], $varitmatch);
    preg_match("/(#[a-f0-9]{3,6});/i", $varitmatch[0], $varirgb);

    if (!empty($varirgb[1])) {
      $bordercolor = " $varirgb[1]";
    }
  }

  foreach ($rows as $row_key => &$row) {

    if ($kukarow['extranet'] != '') {
      $hae_ja_selaa_asiakas = (int) $kukarow['oletus_asiakas'];
    }
    else {
      $hae_ja_selaa_asiakas = (int) $laskurow['liitostunnus'];
    }

    $rivin_yksikko = t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite");

    echo "<tr class='aktiivi'>";
    echo "<td class='back'></td>";

    $vari = "";


    if (strtoupper($row["status"]) == "P") {
      $vari = "tumma";
      $row["nimitys"] .= "<br> * ".t("Poistuva tuote");
    }

    $tuotteen_lisatiedot = tuotteen_lisatiedot($row["tuoteno"]);

    if (count($tuotteen_lisatiedot) > 0) {
      $row["nimitys"] .= "<ul>";
      foreach ($tuotteen_lisatiedot as $tuotteen_lisatiedot_arvo) {
        $row["nimitys"] .= "<li>$tuotteen_lisatiedot_arvo[kentta] &raquo; ".url_or_text($tuotteen_lisatiedot_arvo['selite'])."</li>";
      }
      $row["nimitys"] .= "</ul>";
    }

    // Peek ahead
    $row_seuraava = current($rows);

    if (($row["tuoteperhe"] == $row["tuoteno"] and $row["tuoteperhe"] != $row_seuraava["tuoteperhe"] and $row_seuraava["tuoteperhe"] != "") or
      ($row["osaluettelo"] == $row["tuoteno"] and $row["osaluettelo"] != $row_seuraava["osaluettelo"] and $row_seuraava["osaluettelo"] != "")) {
      $classleft = "";
      $classmidl = "";
      $classrigh = "";
    }
    elseif ($row["tuoteperhe"] == $row["tuoteno"] or $row["osaluettelo"] == $row["tuoteno"]) {
      $classleft = "style='border-top: 1px solid{$bordercolor}; border-left: 1px solid{$bordercolor};' ";
      $classmidl = "style='border-top: 1px solid{$bordercolor};' ";
      $classrigh = "style='border-top: 1px solid{$bordercolor}; border-right: 1px solid{$bordercolor};' ";
    }
    elseif (($row["tuoteperhe"] != "" and $row["tuoteperhe"] != $row_seuraava["tuoteperhe"]) or
      ($row["osaluettelo"] != "" and $row["osaluettelo"] != $row_seuraava["osaluettelo"])) {
      $classleft = "style='border-bottom: 1px solid{$bordercolor}; border-left: 1px solid{$bordercolor};' ";
      $classmidl = "style='border-bottom: 1px solid{$bordercolor};' ";
      $classrigh = "style='border-bottom: 1px solid{$bordercolor}; border-right: 1px solid{$bordercolor};' ";
    }
    elseif ($row["tuoteperhe"] != '' or $row["osaluettelo"] != '') {
      $classleft = "style='border-left: 1px solid{$bordercolor};' ";
      $classmidl = "";
      $classrigh = "style='border-right: 1px solid{$bordercolor};' ";
    }
    else {
      $classleft = "";
      $classmidl = "";
      $classrigh = "";
    }

    // Onko liitetiedostoja
    $liitteet = liite_popup("TH", $row["tunnus"]);

    if ($liitteet) {
      $isan_kuva = 'l�ytyi';
    }
    else {
      $isan_kuva = '';
    }

    // jos ei l�ydet� kuvaa is�tuotteelta, niin katsotaan ne lapsilta
    if (trim($liitteet) == '' and (trim($row["tuoteperhe"]) == trim($row["tuoteno"]) or trim($row["osaluettelo"]) == trim($row["tuoteno"])) and $isan_kuva != '') {

      if ($row["osaluettelo"] != "") {
        $tuoteperhe_tyyppi = "V";
      }
      else {
        $tuoteperhe_tyyppi = "P";
      }

      $query = "SELECT tuote.tunnus
                FROM tuoteperhe
                JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio and tuote.tuoteno = tuoteperhe.tuoteno )
                WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
                and tuoteperhe.isatuoteno = '$row[tuoteno]'
                and tuoteperhe.tyyppi     = '$tuoteperhe_tyyppi'";
      $lapsires = pupe_query($query);

      while ($lapsirow = mysql_fetch_assoc($lapsires)) {
        // Onko lapsien liitetiedostoja
        $liitteet = liite_popup("TH", $lapsirow["tunnus"]);

        if ($liitteet != '') break;
      }
    }

    if ($liitteet != "") {
      echo "<td class='$vari' style='vertical-align: top;'>$liitteet</td>";
    }
    else {
      echo "<td class='$vari'></td>";
    }

    $linkkilisa = "";

    echo "<td valign='top' class='$vari' $classleft>$row[tuoteno] $linkkilisa ";

    // tehd��n extranetiss� pop-up divi jos tuoteella kuvaus...
    if ($kukarow["extranet"] != "" and $row["kuvaus"] != "" and $yhtiorow["extranet_nayta_kuvaus"] == "Y") {

      $kuvaus = str_replace($kuvaus_htmlfrom, $kuvaus_htmlto, t_tuotteen_avainsanat($row, 'kuvaus'));

      if ($kuvaus != $row['kuvaus'] and strpos($kuvaus, "*") !== FALSE) {
        $kuvausarray   = explode('*', str_replace(array('<ul>', '</ul>'), ' ', $kuvaus));
        $lit           = '<ul>';

        foreach ($kuvausarray as $liarvo) {
          if (trim($liarvo) != '') {
            $lit .= '<li>' . trim($liarvo) . '</li>';
          }
        }

        $lit .= '</ul>';

        $row['kuvaus'] = $lit;
      }

      $_title = t("N�yt� kuvaus");
      echo "<img id='$row[tuoteno]' class='tooltip' src='$palvelin2/pics/lullacons/info.png'>";
      echo "<div id='div_$row[tuoteno]' class='popup' style='width: 900px;'>";
      echo  $row['kuvaus'];
      echo "</div>";
    }

    echo "</td>";

    echo "<td valign='top' class='$vari' $classmidl>";
    echo t_tuotteen_avainsanat($row, 'nimitys');
    echo "</td>";

    piirra_hinta($row, $oleasrow, $valuurow, $vari, $classmidl, $laskurow);
    piirra_ostoskoriin_lisays($row);

    echo "</tr>";
  }

  echo "</table>";
  echo "</form>";

}
else {
  echo "<br/>", t("Yht��n tuotetta ei l�ytynyt"), "!";
}

if (@include "inc/footer.inc");
elseif (@include "footer.inc");
else exit;

function hae_oletusasiakas($laskurow) {
  global $kukarow;

  $query = "SELECT *,
            toimipaikka AS yhtio_toimipaikka
            FROM asiakas
            WHERE yhtio='$kukarow[yhtio]'
            AND tunnus='$kukarow[oletus_asiakas]'";

  $oleasres                 = pupe_query($query);
  $oleasrow                 = mysql_fetch_assoc($oleasres);
  $oleasrow["liitostunnus"] = $oleasrow["tunnus"];

  $query = "SELECT *
            FROM valuu
            WHERE yhtio='$kukarow[yhtio]'
            AND nimi='$oleasrow[valkoodi]'";

  $valuures = pupe_query($query);
  $valuurow = mysql_fetch_assoc($valuures);

  // k�ytt�j�n maa
  $oleasrow["varastomaa"] = $laskurow["toim_maa"];

  if ($oleasrow["varastomaa"] == "") {
    $oleasrow["varastomaa"] = $oleasrow["toim_maa"];
  }

  if ($oleasrow["varastomaa"] == "") {
    $oleasrow["varastomaa"] = $oleasrow["maa"];
  }

  return array($oleasrow, $valuurow);
}

function piirra_ostoskoriin_lisays($row) {
  global $oikeurow, $kukarow, $vari, $yht_i, $hae_ja_selaa_row, $_etukateen_maksettu;

  if (empty($_etukateen_maksettu) and $oikeurow["paivitys"] == 1) {
    if (($row["tuoteperhe"] == "" or $row["tuoteperhe"] == $row["tuoteno"] or $row["tyyppi"] == "V") and $row["osaluettelo"] == "") {
      echo "<td align='right' class='$vari' style='vertical-align: top;' nowrap>";
      echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
      echo "<table>";
      echo "<tr><td>".t('Kpl')."</td><td><input type='text' size='3' name='tilkpl[$yht_i]'>";
      echo "<a id='anchor_{$yht_i}' href='#' name='{$yht_i}'><input class='tuote_submit' id='{$yht_i}' type='submit' value = '" . t("Lis��") . "'></a>";
      echo "</td></tr>";
      echo "</table>";
      echo "</td>";
      $yht_i++;
    }
    else {
      echo "<td align='right' class='$vari' style='vertical-align: top;' nowrap></td>";
    }
  }
}

/**
 * Piirt�� formin aloitustagin ja hidden inputit
 */
function piirra_formin_aloitus() {
  global $kukarow, $variaatio;

  $variaatio_query_param = isset($variaatio) ? "&variaatio={$variaatio}" : "";

  echo "<form action='?submit_button=1' name='lisaa' method='post' autocomplete='off' id='lisaaformi'>";
  echo "<input type='hidden' name='tee' value = 'TI'>";
  echo "<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>";
}


/**
 * Tarkistetaan tilausrivin tiedot ja echotetaan ruudulle lis�tyt tuotteet
 */
function tarkista_tilausrivi() {
  global $tee, $tilkpl, $kukarow, $yhtiorow, $toim, $tiltuoteno, $myyntierahuom, $lisatty_tun, $hae_ja_selaa_row;

  pupemaster_start();

  if ($tee == 'TI' and isset($tilkpl)) {

    // haetaan avoimen tilauksen otsikko
    if ($kukarow["kesken"] != 0) {
      $query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
      $laskures = pupe_query($query);
    }
    else {
      // Luodaan uusi myyntitilausotsikko
      if ($kukarow["extranet"] == "") {
        require_once "tilauskasittely/luo_myyntitilausotsikko.inc";

        $lmyytoim = "RIVISYOTTO";
        $tilausnumero = luo_myyntitilausotsikko($lmyytoim, 0);
        $kukarow["kesken"] = $tilausnumero;
        $kaytiin_otsikolla = "NOJOO!";
      }
      else {
        require_once "luo_myyntitilausotsikko.inc";
        $tilausnumero = luo_myyntitilausotsikko("EXTRANET", $kukarow["oletus_asiakas"]);
        $kukarow["kesken"] = $tilausnumero;
        $kaytiin_otsikolla = "NOJOO!";
      }

      // haetaan avoimen tilauksen otsikko
      $query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
      $laskures = pupe_query($query);
    }

    if ($kukarow["kesken"] != 0 and $laskures != '') {
      // tilauksen tiedot
      $laskurow = mysql_fetch_assoc($laskures);
    }

    echo "<font class='message'>" . t("Lis�t��n tuotteita tilaukselle") . " $kukarow[kesken].</font><br>";

    // K�yd��n l�pi formin kaikki rivit
    foreach ($tilkpl as $yht_i => $kpl) {

      $kpl = str_replace(',', '.', $kpl);

      if ((float) $kpl > 0 or ($kukarow["extranet"] == "" and (float) $kpl < 0) or ($yhtiorow['reklamaation_kasittely'] == 'U' and $toim == 'EXTRANET_REKLAMAATIO' and (float) $kpl != 0)) {

        if ($yhtiorow['reklamaation_kasittely'] == 'U' and $toim == 'EXTRANET_REKLAMAATIO') {
          $kpl = abs($kpl) * -1;
        }

        // haetaan tuotteen tiedot
        $query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tiltuoteno[$yht_i]'";
        $tuoteres = pupe_query($query);

        if (mysql_num_rows($tuoteres) == 0) {
          echo "<font class='error'>" . t("Tuotetta %s ei l�ydy", "", $tiltuoteno[$yht_i]) . "!</font><br>";
        }
        else {
          // tuote l�ytyi ok, lis�t��n rivi
          $trow = mysql_fetch_assoc($tuoteres);

          $ytunnus = $laskurow["ytunnus"];
          $kpl = (float) $kpl;
          $kpl_echo = (float) $kpl;
          $tuoteno = $trow["tuoteno"];
          $yllapita_toim_stash = $toim;

          $toimaika = $laskurow["toimaika"];
          $kerayspvm = $laskurow["kerayspvm"];
          $toim = "RIVISYOTTO";
          $hinta = "";
          $netto = "";

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            ${'ale' . $alepostfix} = "";
          }

          // Jos ale1 annettu
          if (!empty($_REQUEST['ale1'][$yht_i]) and empty($kukarow['extranet']) and $hae_ja_selaa_row['selitetark_2'] == 'K') {
            $ale1 = $_REQUEST['ale1'][$yht_i];
          }
          $alv = "";
          $var = "";
          $varasto = $laskurow["varasto"];
          $rivitunnus = "";
          $korvaavakielto = "";
          $jtkielto = $laskurow['jtkielto'];
          $varataan_saldoa = "";
          $paikka = "";

          // jos meill� on ostoskori muuttujassa numero, niin halutaan lis�t� tuotteita siihen ostoskoriin
          if (file_exists("../tilauskasittely/lisaarivi.inc")) {
            require "../tilauskasittely/lisaarivi.inc";
          }
          else {
            require "lisaarivi.inc";
          }

          $toim = $yllapita_toim_stash;
          echo "<font class='message'>" . t("Lis�ttiin") . " $kpl_echo " . t_avainsana("Y", "", " and avainsana.selite='$trow[yksikko]'", "", "", "selite") . " " . t("tuotetta") . " $tiltuoteno[$yht_i].</font><br>";

          if (isset($myyntierahuom) and count($myyntierahuom) > 0) {

            $mimyhuom = "HUOM: Rivin m��r� on py�ristetty";

            if ($trow["minimi_era"] == $kpl_st and in_array($yhtiorow['minimimaara_pyoristys'], array('K', 'E'))) {
              $mimyhuom .= " minimier��n";
            }
            elseif ($trow['myynti_era'] > 0 and in_array($yhtiorow['myyntiera_pyoristys'], array('K', 'S'))) {
              $mimyhuom .= " t�yteen myyntier��n";
            }

            // K��nnet��n teksti
            $mimyhuom = t($mimyhuom) . "!";

            if ($trow['myynti_era'] > 0) {
              $mimyhuom .= " " . t("Myyntier� on") . ": $trow[myynti_era]";
            }

            if ($trow["minimi_era"] > 0) {
              $mimyhuom .= " " . t("Minimier� on") . ": $trow[minimi_era]";
            }

            echo "<font class='error'>" . $mimyhuom . "</font><br>";
          }
        } // tuote ok else
      } // end kpl > 0
    } // end foreach

    echo "<br>";

    $trow = "";
    $ytunnus = "";
    $kpl = "";
    $tuoteno = "";
    $toimaika = "";
    $kerayspvm = "";
    $hinta = "";
    $netto = "";
    $alv = "";
    $var = "";
    $varasto = "";
    $rivitunnus = "";
    $korvaavakielto = "";
    $varataan_saldoa = "";
    $paikka = "";
    $tee = "";

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      ${'ale' . $alepostfix} = '';
    }
  }

  pupemaster_stop();
}

function piirra_hinta($row, $oleasrow, $valuurow, $vari, $classmidl, $laskurow) {
  global $kukarow, $yhtiorow, $verkkokauppa;

  if ($kukarow['hinnat'] >= 0) {
    $myyntihinta = hintapyoristys($row["myyntihinta"]) . " $yhtiorow[valkoodi]";

    if ($kukarow["extranet"] != "" and $kukarow["naytetaan_asiakashinta"] != "") {
      list($hinta,
        $netto,
        $ale_kaikki,
        $alehinta_alv,
        $alehinta_val) = alehinta($oleasrow, $row, 1, '', '', '');

      // alvillinen -> alviton
      // alv pois
      if ($yhtiorow['alv_kasittely'] == '' and $oleasrow['alv'] == 0 and $row['alv'] != 0) {
        $hinta = $hinta / (1 + $row['alv'] / 100);
      }

      $myyntihinta_echotus = $hinta * generoi_alekentta_php($ale_kaikki, 'M', 'kerto');
      $myyntihinta         = hintapyoristys($myyntihinta_echotus) . " $alehinta_val";
    }

    echo "<td valign='top' class='$vari' align='right' $classmidl nowrap>";
    echo $myyntihinta;
    echo "</td>";
  }
}
