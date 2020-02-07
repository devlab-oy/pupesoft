<?php

if (@include "../inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

if (@include "verkkokauppa/ostoskori.inc") {
  $kori_polku = "../verkkokauppa/ostoskori.php";
}
elseif (@include "ostoskori.inc") {
  $kori_polku = "ostoskori.php";

  if ($tultiin == "futur") {
    $kori_polku .= "?ostoskori=".$ostoskori."&tultiin=".$tultiin;
  }
}
else exit;

if (!isset($submit_button)) $submit_button = '';

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

echo "<SCRIPT type='text/javascript'>
      <!--
        function sarjanumeronlisatiedot_popup(tunnus) {
          window.open('$PHP_SELF?tunnus='+tunnus+'&toiminto=sarjanumeronlisatiedot_popup', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=0,top=0,width=800,height=600');
        }
      //-->
      </SCRIPT>";

if (isset($toiminto) and $toiminto == "sarjanumeronlisatiedot_popup") {
  @include 'sarjanumeron_lisatiedot_popup.inc';

  if ($kukarow["extranet"] != "") {
    $hinnat = 'MY';
  }
  else {
    $hinnat = '';
  }

  list($divitx, , , , ) = sarjanumeronlisatiedot_popup($tunnus, '', '', $hinnat, '');
  echo "$divitx";
  exit;
}

echo "<font class='head'>".t("Tuoteselain").":</font><hr>";

if (!isset($toim_kutsu)) {
  $toim_kutsu = '';
}

if ($toim_kutsu == "") {
  $toim_kutsu = "RIVISYOTTO";
}

$query    = "SELECT * from lasku where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
$result   = mysql_query($query) or pupe_error($query);
$laskurow = mysql_fetch_assoc($result);

if (!isset($ostoskori)) {
  $ostoskori = '';
}

if (is_numeric($ostoskori)) {
  echo "<table><tr><td class='back'>";
  echo "  <form method='post' action='$kori_polku'>
        <input type='hidden' name='tee' value='poistakori'>
        <input type='hidden' name='ostoskori' value='$ostoskori'>
        <input type='hidden' name='pyytaja' value='haejaselaa'>
        <input type='submit' value='".t("Tyhjennä ostoskori")."'>
        </form>";
  echo "</td><td class='back'>";
  echo "  <form method='post' action='$kori_polku'>
        <input type='hidden' name='tee' value=''>
        <input type='hidden' name='ostoskori' value='$ostoskori'>
        <input type='hidden' name='pyytaja' value='haejaselaa'>
        <input type='submit' value='".t("Näytä ostoskori")."'>
        </form>";
  echo "</td></tr></table>";
}
elseif ($kukarow["kuka"] != "" and ($laskurow["tila"] == "L" or $laskurow["tila"] == "N" or $laskurow["tila"] == "T" or $laskurow["tila"] == "A" or $laskurow["tila"] == "S")) {

  if ($kukarow["extranet"] != "") {
    $toim_kutsu = "EXTRANET";
  }

  echo "  <form method='post' action='tilaus_myynti.php'>
        <input type='hidden' name='toim' value='$toim_kutsu'>
        <input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
        <input type='submit' value='".t("Takaisin tilaukselle")."'>
        </form><br><br>";
}
elseif ($kukarow["kuka"] != "" and $laskurow["tila"] == "O") {

  echo "  <form method='post' action='tilaus_osto.php'>
        <input type='hidden' name='aktivoinnista' value='true'>
        <input type='hidden' name='tee' value='AKTIVOI'>
        <input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
        <input type='submit' value='".t("Takaisin tilaukselle")."'>
        </form><br><br>";
}

if (!isset($tee)) {
  $tee = '';
}

if (!isset($orderlisa)) {
  $orderlisa = '';
}

// Näytetäänkö osaston ja tuoteryhmän selitteet
if ($yhtiorow['naytetaanko_osaston_ja_tryn_selite'] == "K") {
  $orderlisa = "ORDER BY avainsana.selitetark";
}
else {
  $orderlisa = "ORDER BY avainsana.jarjestys, avainsana.selite+0";
}

// Tarkistetaan tilausrivi
//and ($kukarow["kesken"] != 0
if (($tee == 'TI' or is_numeric($ostoskori)) and isset($tilkpl)) {

  if (is_numeric($ostoskori)) {
    $kori = check_ostoskori($ostoskori, $kukarow["oletus_asiakas"]);
    $kukarow["kesken"] = $kori["tunnus"];
  }

  // haetaan avoimen tilauksen otsikko
  if ($kukarow["kesken"] != 0) {
    $query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
    $laskures = mysql_query($query);
  }
  else {
    // Luodaan uusi myyntitilausotsikko
    if ($kukarow["extranet"] == "") {
      require_once "tilauskasittely/luo_myyntitilausotsikko.inc";

      if ($toim_kutsu != "") {
        $lmyytoim = $toim_kutsu;
      }
      else {
        $lmyytoim = "RIVISYOTTO";
      }

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
    $query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
    $laskures = mysql_query($query);
  }

  if ($kukarow["kesken"] != 0 and $laskures != '') {
    // tilauksen tiedot
    $laskurow = mysql_fetch_assoc($laskures);
  }

  if (is_numeric($ostoskori)) {
    echo "<font class='message'>Lisätään tuotteita ostoskoriin $ostoskori.</font><br>";
  }
  else {
    echo "<font class='message'>Lisätään tuotteita tilaukselle $kukarow[kesken].</font><br>";
  }

  // Käydään läpi formin kaikki rivit
  foreach ($tilkpl as $yht_i => $kpl) {

    if ((float) $kpl > 0 or ($kukarow["extranet"] == "" and (float) $kpl < 0)) {

      // haetaan tuotteen tiedot
      $query    = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tiltuoteno[$yht_i]'";
      $tuoteres = mysql_query($query);

      if (mysql_num_rows($tuoteres) == 0) {
        echo "<font class='error'>Tuotetta $tiltuoteno[$yht_i] ei löydy!</font><br>";
      }
      else {
        // tuote löytyi ok, lisätään rivi
        $trow = mysql_fetch_assoc($tuoteres);

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          ${'ale'.$alepostfix} = "";
        }

        $ytunnus         = $laskurow["ytunnus"];
        $kpl             = (float) $kpl;
        $kpl_echo      = (float) $kpl;
        $tuoteno         = $trow["tuoteno"];
        $toimaika        = $laskurow["toimaika"];
        $kerayspvm       = $laskurow["kerayspvm"];
        $hinta          = "";
        $netto          = "";
        $alv         = "";
        $var       = "";
        $varasto        = $laskurow["varasto"];
        $rivitunnus     = "";
        $korvaavakielto   = "";
        $jtkielto      = $laskurow['jtkielto'];
        $varataan_saldoa = "";
        $myy_sarjatunnus = $tilsarjatunnus[$yht_i];
        $paikka       = "";

        // jos meillä on ostoskori muuttujassa numero, niin halutaan lisätä tuotteita siihen ostoskoriin
        if (is_numeric($ostoskori)) {
          lisaa_ostoskoriin ($ostoskori, $laskurow["liitostunnus"], $tuoteno, $kpl);
          $kukarow["kesken"] = "";
        }
        elseif (file_exists("../tilauskasittely/lisaarivi.inc")) {
          require "../tilauskasittely/lisaarivi.inc";
        }
        else {
          require "lisaarivi.inc";
        }

        echo "<font class='message'>".t("Lisättiin")." $kpl_echo ".t_avainsana("Y", "", "and avainsana.selite='$trow[yksikko]'", "", "", "selite")." ".t("tuotetta")." $tiltuoteno[$yht_i].</font><br>";

        //Hanskataan sarjanumerollisten tuotteiden lisävarusteet
        if ($tilsarjatunnus[$yht_i] > 0 and $lisatty_tun > 0) {
          require "sarjanumeron_lisavarlisays.inc";

          lisavarlisays($tilsarjatunnus[$yht_i], $lisatty_tun);
        }
      } // tuote ok else
    } // end kpl > 0
  } // end foreach

  echo "<br>";

  $trow       = "";
  $ytunnus         = "";
  $kpl             = "";
  $tuoteno         = "";
  $toimaika        = "";
  $kerayspvm       = "";
  $hinta          = "";
  $netto          = "";
  $alv         = "";
  $var       = "";
  $varasto        = "";
  $rivitunnus     = "";
  $korvaavakielto   = "";
  $varataan_saldoa = "";
  $myy_sarjatunnus = "";
  $paikka       = "";
  $tee        = "";

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    ${'ale'.$alepostfix} = "";
  }
}

$jarjestys = "tuote.tuoteno";

$lisa          = "";
$ulisa         = "";
$toimtuotteet     = "";
$origtuotteet     = "";
$poislisa_mulsel   = "";

if (!isset($ojarj)) {
  $ojarj = '';
}

if (strlen($ojarj) > 0) {
  $ojarj = trim(mysql_real_escape_string($ojarj));

  if ($ojarj == 'tuoteno') {
    $jarjestys = 'tuote.tuoteno';
  }
  elseif ($ojarj == 'toim_tuoteno') {
    $jarjestys = 'tuote.tuoteno';
  }
  elseif ($ojarj == 'merkki') {
    $jarjestys = 'tuote.tuotemerkki';
  }
  elseif ($ojarj == 'nimitys') {
    $jarjestys = 'tuote.nimitys';
  }
  elseif ($ojarj == 'osasto') {
    $jarjestys = 'tuote.osasto';
  }
  elseif ($ojarj == 'try') {
    $jarjestys = 'tuote.try';
  }
  elseif ($ojarj == 'hinta') {
    $jarjestys = 'tuote.myyntihinta';
  }
  elseif ($ojarj == 'nettohinta') {
    $jarjestys = 'tuote.nettohinta';
  }
  elseif ($ojarj == 'aleryhma') {
    $jarjestys = 'tuote.aleryhma';
  }
  elseif ($ojarj == 'status') {
    $jarjestys = 'tuote.status';
  }
  else {
    $jarjestys = 'tuote.tuoteno';
  }
}

if (!isset($poistetut)) {
  $poistetut = '';
}

if ($poistetut != "") {
  $poischeck = "CHECKED";
  $ulisa .= "&poistetut=checked";
  $poislisa = "";

  if ($kukarow['extranet'] == "") {
    $poislisa  = " and (tuote.status not in ('p','x')
              or (tuote.status in ('p','x') and (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)) ";
  }
  else {
    $poislisa = "   and (tuote.status not in ('p','x')
                or (tuote.status in ('p','x') and (SELECT sum(saldo)
               FROM tuotepaikat
               JOIN varastopaikat on (varastopaikat.yhtio=tuotepaikat.yhtio
               and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
               and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
               and varastopaikat.tyyppi = '')
               WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0))";
  }
}
else {
  if ($kukarow['extranet'] == "") {
    $poislisa  = " and ((tuote.ei_saldoa != '' and tuote.status not in ('p','x'))
              or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0) ";
  }
  else {
    $poislisa = "   and ((tuote.ei_saldoa != '' and tuote.status not in ('p','x'))
                or (SELECT sum(saldo)
               FROM tuotepaikat
               JOIN varastopaikat on (varastopaikat.yhtio=tuotepaikat.yhtio
               and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
               and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
               and varastopaikat.tyyppi = '')
               WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)";
  }
  $poischeck = "";
}

if ($kukarow["extranet"] != "") {

  // Käytetään alempana
  $query = "SELECT * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
  $oleasres = mysql_query($query) or pupe_error($query);
  $oleasrow = mysql_fetch_assoc($oleasres);

  $query = "SELECT * from valuu where yhtio='$kukarow[yhtio]' and nimi='$oleasrow[valkoodi]'";
  $olhires = mysql_query($query) or pupe_error($query);
  $olhirow = mysql_fetch_assoc($olhires);

  $extra_poislisa = " and tuote.hinnastoon != 'E' ";
  $avainlisa = " and avainsana.jarjestys < 10000";
}
else {
  $extra_poislisa = "";
  $avainlisa = "";
}

// vientikieltokäsittely:
// +maa tarkoittaa että myynti on kielletty tähän maahan ja sallittu kaikkiin muihin
// -maa tarkoittaa että ainoastaan tähän maahan saa myydä
// eli näytetään vaan tuotteet jossa vienti kentässä on tyhjää tai -maa.. ja se ei saa olla +maa
$kieltolisa = "";
unset($vierow);

if ($kukarow["kesken"] > 0) {
  $query  = "SELECT if(toim_maa != '', toim_maa, maa) maa
             FROM lasku
             WHERE yhtio = '$kukarow[yhtio]'
             and tunnus  = '$kukarow[kesken]'";
  $vieres = mysql_query($query) or pupe_error($query);
  $vierow = mysql_fetch_assoc($vieres);
}
elseif ($verkkokauppa != "") {
  $vierow = array();

  if ($maa != "") {
    $vierow["maa"] = $maa;
  }
  else {
    $vierow["maa"] = $yhtiorow["maa"];
  }
}
elseif ($kukarow["extranet"] != "") {
  $query  = "SELECT if(toim_maa != '', toim_maa, maa) maa
             FROM asiakas
             WHERE yhtio = '$kukarow[yhtio]'
             and tunnus  = '$kukarow[oletus_asiakas]'";
  $vieres = mysql_query($query) or pupe_error($query);
  $vierow = mysql_fetch_assoc($vieres);
}

if (isset($vierow) and $vierow["maa"] != "") {
  $kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
}

if (file_exists('sarjanumeron_lisatiedot_popup.inc')) {
  require "sarjanumeron_lisatiedot_popup.inc";
}

echo "<form action = '?toim_kutsu=$toim_kutsu' method = 'post'>";
echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

if (!isset($tultiin)) {
  $tultiin = '';
}

if ($tultiin == "futur") {
  echo " <input type='hidden' name='tultiin' value='$tultiin'>";
}

echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

$monivalintalaatikot = array("DYNAAMINEN_TUOTE");
$monivalintalaatikot_normaali = array();
$monivalintarajaus_dynaaminen = "14433,14444,14447,14457,14459,14460";

if (file_exists("tilauskasittely/monivalintalaatikot.inc")) {
  require "tilauskasittely/monivalintalaatikot.inc";
}
else {
  require "monivalintalaatikot.inc";
}

echo "<br><table style='display:inline;' valign='top'>";
echo "<tr><th>", t("Näytä tehdastilaustuotteet"), " </th><td nowrap valign='top' colspan='2'><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td></tr></table>";

echo "<br><br>";
echo "<tr><td><input type='Submit' name='submit_button' id='submit_button' value = '".t("Etsi tuotteet")."'></form>";
echo "<form><input type='submit' name='submit_button2' id='submit_button2' value = '".t("Tyhjennä")."'></form></td></tr></table><br>";

// Halutaanko saldot koko konsernista?
$query = "SELECT *
          FROM yhtio
          WHERE konserni='$yhtiorow[konserni]' and konserni != ''";
$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result) > 0 and $yhtiorow["haejaselaa_konsernisaldot"] != "") {
  $yhtiot = array();

  while ($row = mysql_fetch_assoc($result)) {
    $yhtiot[] = $row["yhtio"];
  }
}
else {
  $yhtiot = array();
  $yhtiot[] = $kukarow["yhtio"];
}

if (isset($sort) and $sort != '') {
  $sort = trim(mysql_real_escape_string($sort));
}

if (!isset($sort)) {
  $sort = '';
}

if ($sort == 'asc') {
  $sort = 'desc';
  $edsort = 'asc';
}
else {
  $sort = 'asc';
  $edsort = 'desc';
}

if (!isset($submit_button)) {
  $submit_button = '';
}

if ($submit_button != '' and ($lisa != '' or $lisa_parametri != '')) {

  if (!isset($lisatiedot)) $lisatiedot = '';

  $query = "SELECT
            ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi='P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
            ifnull((SELECT id FROM korvaavat use index (yhtio_tuoteno) where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1), tuote.tuoteno) korvaavat,
            tuote.tuoteno,
            tuote.nimitys,
            tuote.osasto,
            tuote.try,
            tuote.tuotemerkki,
            tuote.myyntihinta,
            tuote.nettohinta,
            tuote.aleryhma,
            tuote.status,
            tuote.ei_saldoa,
            tuote.yksikko,
            tuote.tunnus,
            (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
            tuote.sarjanumeroseuranta,
            tuote.status
            FROM tuote use index (tuoteno, nimitys)
            $lisa_parametri
            WHERE tuote.yhtio     = '$kukarow[yhtio]'
            AND tuote.tuotetyyppi NOT IN ('A', 'B')
            $kieltolisa
            $lisa
            $extra_poislisa
            $poislisa
            ORDER BY $jarjestys $sort
            LIMIT 500";
  $result = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($result) > 0) {
    $rows = array();

    // Rakennetaan array ja laitetaan korvaavat mukaan
    while ($mrow = mysql_fetch_assoc($result)) {
      if ($mrow["korvaavat"] != $mrow["tuoteno"]) {
        $query = "SELECT
                  ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi='P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
                  korvaavat.id korvaavat,
                  tuote.tuoteno,
                  tuote.nimitys,
                  tuote.osasto,
                  tuote.try,
                  tuote.tuotemerkki,
                  tuote.myyntihinta,
                  tuote.nettohinta,
                  tuote.aleryhma,
                  tuote.status,
                  tuote.ei_saldoa,
                  tuote.yksikko,
                  tuote.tunnus,
                  (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
                  tuote.sarjanumeroseuranta,
                  tuote.status
                  FROM korvaavat
                  JOIN tuote ON tuote.yhtio=korvaavat.yhtio and tuote.tuoteno=korvaavat.tuoteno
                  WHERE korvaavat.yhtio  = '$kukarow[yhtio]'
                  and korvaavat.id       = '$mrow[korvaavat]'
                  and korvaavat.tuoteno != '$mrow[tuoteno]'
                  $kieltolisa
                  $poislisa
                  ORDER BY korvaavat.jarjestys, korvaavat.tuoteno";
        $kores = mysql_query($query) or pupe_error($query);

        if (mysql_num_rows($kores) > 0) {

          $krow = mysql_fetch_assoc($kores);
          $ekakorva = $krow["korvaavat"];

          mysql_data_seek($kores, 0);

          if (!isset($rows[$ekakorva.$mrow["tuoteno"]])) $rows[$ekakorva.$mrow["tuoteno"]] = $mrow;

          while ($krow = mysql_fetch_assoc($kores)) {

            $krow["mikakorva"] = $mrow["tuoteno"];

            if (!isset($rows[$ekakorva.$krow["tuoteno"]])) $rows[$ekakorva.$krow["tuoteno"]] = $krow;
          }
        }
        else {
          $rows[$mrow["tuoteno"]] = $mrow;
        }
      }
      else {
        $rows[$mrow["tuoteno"]] = $mrow;

        if (!function_exists("tuoteselaushaku_tuoteperhe")) {
          function tuoteselaushaku_tuoteperhe($esiisatuoteno, $tuoteno, $isat_array, $kaikki_array, $rows) {
            global $kukarow, $kieltolisa, $poislisa;

            if (in_array($tuoteno, $isat_array)) {
              //echo "FUULI! TEET IKUISEN LUUPIN!!!!!!!!<br>";
            }
            else {
              $isat_array[] = $tuoteno;

              $query = "SELECT
                        '$esiisatuoteno' tuoteperhe,
                        tuote.tuoteno korvaavat,
                        tuote.tuoteno,
                        tuote.nimitys,
                        tuote.osasto,
                        tuote.try,
                        tuote.tuotemerkki,
                        tuote.myyntihinta,
                        tuote.nettohinta,
                        tuote.aleryhma,
                        tuote.status,
                        tuote.ei_saldoa,
                        tuote.yksikko,
                        tuote.tunnus,
                        (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
                        tuote.sarjanumeroseuranta,
                        tuote.status
                        FROM tuoteperhe
                        JOIN tuote ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.tuoteno
                        WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
                        and tuoteperhe.isatuoteno = '$tuoteno'
                        $kieltolisa
                        $poislisa
                        ORDER BY tuoteperhe.tuoteno";
              $kores = mysql_query($query) or pupe_error($query);

              while ($krow = mysql_fetch_assoc($kores)) {
                $rows[$krow["tuoteperhe"].$krow["tuoteno"]] = $krow;
                $kaikki_array[]  = $krow["tuoteno"];
              }
            }

            return array($isat_array, $kaikki_array, $rows);
          }


        }

        if ($mrow["tuoteperhe"] == $mrow["tuoteno"]) {
          $riikoko     = 1;
          $isat_array   = array();
          $kaikki_array   = array($mrow["tuoteno"]);

          for ($isa=0; $isa < $riikoko; $isa++) {
            list($isat_array, $kaikki_array, $rows) = tuoteselaushaku_tuoteperhe($mrow["tuoteno"], $kaikki_array[$isa], $isat_array, $kaikki_array, $rows);

            if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y") {
              $riikoko = count($kaikki_array);
            }
          }
        }
      }
    }

    if ($yhtiorow["saldo_kasittely"] == "T") {
      $saldoaikalisa = date("Y-m-d");
    }
    else {
      $saldoaikalisa = "";
    }
    echo "<br/>";
    echo "<table>";
    echo "<tr>";

    echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=tuoteno$ulisa'>", t("Tuoteno"), "</a>";

    if ($lisatiedot != "") {
      echo "<br/><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=toim_tuoteno$ulisa'>", t("Toim Tuoteno");
    }

    echo "</th>";

    echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=merkki$ulisa'>", t("Merkki")."</th>";
    echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=nimitys$ulisa'>", t("Nimitys")."</th>";
    echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=osasto$ulisa'>", t("Osasto");
    echo "<br><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=try$ulisa'>", t("Try"), "</th>";
    echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=hinta$ulisa'>", t("Hinta");

    if ($lisatiedot != "" and $kukarow["extranet"] == "") {
      echo "<br/><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=nettohinta$ulisa'>", t("Nettohinta");
    }

    echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=aleryhma$ulisa'>", t("Aleryhmä");

    if ($lisatiedot != "" and $kukarow["extranet"] == "") {
      echo "<br/><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&sort=$sort&ojarj=status$ulisa'>", t("Status");
    }

    echo "</th>";

    echo "<th>", t("Myytävissä"), "</th>";

    if ($lisatiedot != "" and $kukarow["extranet"] == "") {
      echo "<th>".t("Hyllyssä")."</th>";
    }

    if ($oikeurow["paivitys"] == 1 and ($kukarow["kuka"] != "" or is_numeric($ostoskori))) {
      echo "<th>&nbsp;</th>";
    }

    echo "</tr>";

    $edtuoteno = "";

    $yht_i = 0; // tää on meiän indeksi

    echo "<form action='?submit_button=1&sort=$edsort&ojarj=$ojarj$ulisa' name='lisaa' method='post'>";
    echo "<input type='hidden' name='tee' value = 'TI'>";
    echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
    echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

    if ($tultiin == "futur") {
      echo " <input type='hidden' name='tultiin' value='$tultiin'>";
    }

    $alask = 0;

    foreach ($rows as $ind => $row) {
      // Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausriviltä
      if ($row["sarjanumeroseuranta"] == "S" and ($row["tuoteperhe"] == "" or $row["tuoteperhe"] == $row["tuoteno"])) {
        $query  = "SELECT sarjanumeroseuranta.*, tilausrivi_osto.nimitys nimitys, tilausrivi_myynti.tyyppi, lasku_myynti.nimi myynimi, lasku_myynti.tunnus myytunnus
                   FROM sarjanumeroseuranta
                   LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
                   LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                   LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
                   WHERE sarjanumeroseuranta.yhtio           in ('".implode("','", $yhtiot)."')
                   and sarjanumeroseuranta.tuoteno           = '$row[tuoteno]'
                   and sarjanumeroseuranta.myyntirivitunnus != -1
                   and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.tyyppi='T')
                   and tilausrivi_osto.laskutettuaika       != '0000-00-00'
                   order by nimitys";
        $sarjares = mysql_query($query) or pupe_error($query);

        // Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausriviltä
        $sarjalask = 0;

        if (mysql_num_rows($sarjares) > 0) {

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            if ($sarjarow["nimitys"] != "") {
              $row["nimitys"] = $sarjarow["nimitys"];
            }

            if ($sarjarow["yhtio"] != $kukarow["yhtio"]) {
              $row["sarjanumero"] = $sarjarow["sarjanumero"]." ($sarjarow[yhtio])";
            }
            else {
              $row["sarjanumero"] = $sarjarow["sarjanumero"];
            }

            $row["sarjatunnus"] = $sarjarow["tunnus"];
            $row["sarjayhtio"] = $sarjarow["yhtio"];

            if ($sarjalask > 0) {
              $row["korvaavat"] = $ind.$sarjalask;
              array_splice($rows, $alask, 0, array($ind.$sarjalask => $row));
            }
            else {
              $rows[$ind] = $row;
            }

            $sarjalask++;
          }
        }
      }

      $alask++;
    }

    foreach ($rows as $key => $row) {

      if ($kukarow['extranet'] != '') {
        if (!saako_myyda_private_label($kukarow["oletus_asiakas"], $row["tuoteno"])) {
          continue;
        }
      }

      $vari = "";

      if (isset($row["mikakorva"])) {
        $vari = 'spec';
        $row["nimitys"] .= "<br> * ".t("Korvaa tuotteen").": $row[mikakorva]";
      }
      if (strtoupper($row["status"]) == "P") {
        $vari = "tumma";
        $row["nimitys"] .= "<br> * ".t("Poistuva tuote");
      }

      if (isset($lapsien_maara) and $lapsien_maara > 0) {
        $lapsien_maara--;
        continue;
      }

      // Peek ahead haksaus, next() ei toiminut tässä
      $stop = "";
      $temppi_key = "";
      foreach ($rows as $temp_key => $temp_row) {
        if ($temp_key == $key) {
          $stop = "yes";
        }
        elseif ($stop == "yes") {
          $row_seuraava = $temp_row;
          break;
        }
      }

      if ($row["tuoteperhe"] == $row["tuoteno"] and $row["tuoteperhe"] != $row_seuraava["tuoteperhe"]) {
        $classleft = "";
        $classmidl = "";
        $classrigh = "";
      }
      elseif ($row["tuoteperhe"] == $row["tuoteno"]) {
        $classleft = "style='border-top: 1px solid; border-left: 1px solid;' ";
        $classmidl = "style='border-top: 1px solid;' ";
        $classrigh = "style='border-top: 1px solid; border-right: 1px solid;' ";
      }
      elseif ($row["tuoteperhe"] != "" and $row["tuoteperhe"] != $row_seuraava["tuoteperhe"]) {
        $classleft = "style='border-bottom: 1px solid; border-left: 1px solid;' ";
        $classmidl = "style='border-bottom: 1px solid;' ";
        $classrigh = "style='border-bottom: 1px solid; border-right: 1px solid;' ";
      }
      elseif ($row["tuoteperhe"] != '') {
        $classleft = "style='border-left: 1px solid;' ";
        $classmidl = "";
        $classrigh = "style='border-right: 1px solid;' ";
      }
      else {
        $classleft = "";
        $classmidl = "";
        $classrigh = "";
      }

      //Saldo chekit
      $echous = "";
      $riittaako_saldo = "";

      if ($row["tuoteperhe"] == $row["tuoteno"]) {
        // Tuoteperheen isä
        if ($kukarow["extranet"] != "") {
          $saldot = tuoteperhe_myytavissa($row["tuoteno"], "KAIKKI", "", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

          $kokonaismyytavissa = 0;

          foreach ($saldot as $varasto => $myytavissa) {
            $kokonaismyytavissa += $myytavissa;
          }

          if ($kokonaismyytavissa > 0) {
            $echous .= "<td valign='top' class='green' $classrigh>".t("On")."</td>";
          }
          else {
            $echous .= "<td valign='top' class='red' $classrigh>".t("Ei")."</td>";
            if ($poistetut == "") {
              $riittaako_saldo = "EI";
            }
          }
        }
        else {
          $saldot = tuoteperhe_myytavissa($row["tuoteno"], "", "KAIKKI", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

          $echous .= "<td valign='top' $classrigh>";
          $echous .= "<table width='100%'>";

          $ei_tyhja = "";
          foreach ($saldot as $varaso => $saldo) {
            if ($saldo != 0) {
              $ei_tyhja = 'yes';
              $echous .= "<tr><td class='$vari' nowrap>$varaso</td><td class='$vari' align='right' nowrap>".sprintf("%.2f", $saldo)." ".t_avainsana("Y", "", "and avainsana.selite='$row[yksikko]'", "", "", "selite")."</td></tr>";
            }
          }

          if ($ei_tyhja == '') {
            if ($poistetut == "") {
              $riittaako_saldo = "EI";
            }
          }

          $echous .= "</table></td>";
        }
      }
      elseif ($row['ei_saldoa'] != '') {
        if ($kukarow["extranet"] != "") {
          $echous .= "<td valign='top' class='green' $classrigh>".t("On")."</td>";
        }
        else {
          $echous .= "<td valign='top' class='green' $classrigh>".t("Saldoton")."</td>";
        }
      }
      elseif ($row["sarjanumeroseuranta"] == "S" and ($row["tuoteperhe"] == "" or $row["tuoteperhe"] == $row["tuoteno"])) {
        if ($kukarow["extranet"] != "") {
          $echous .= "<td valign='top' class='$vari' $classrigh>$row[sarjanumero] ";
        }
        else {
          $echous .= "<td valign='top' class='$vari' $classrigh><a onClick=\"javascript:sarjanumeronlisatiedot_popup('$row[sarjatunnus]')\">$row[sarjanumero]</a> ";
        }

        if ($row["sarjayhtio"] == $kukarow["yhtio"] and ($kukarow["kuka"] != "" or is_numeric($ostoskori))) {
          $echous .= "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
          $echous .= "<input type='hidden' name='tilsarjatunnus[$yht_i]' value = '$row[sarjatunnus]'>";
          $echous .= "<input type='checkbox' name='tilkpl[$yht_i]' value='1'> ";
          $yht_i++;
        }

        $echous .= "</td>";

        if ($lisatiedot != "" and $kukarow["extranet"] == "") {
          $echous .= "<td class='$vari' $classrigh></td>";
        }
      }
      elseif ($kukarow["extranet"] != "") {

        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], "", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

        if ($myytavissa > 0) {
          $echous .= "<td valign='top' class='green' $classrigh>".t("On")."</td>";
        }
        else {

          $echous .= "<td valign='top' class='red' $classrigh>".t("Ei")."</td>";

          if ($poistetut == "") {
            $riittaako_saldo = "EI";
          }
        }
      }
      else {
        if ($laskurow["toim_maa"] != '') {
          $sallitut_maat_lisa = " and (varastopaikat.sallitut_maat like '%$laskurow[toim_maa]%' or varastopaikat.sallitut_maat = '') ";
        }

        // Käydään läpi tuotepaikat
        if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F") {
          $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                    tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                    sarjanumeroseuranta.sarjanumero era,
                    concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
                    varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
                     FROM tuote
                    JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                    JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
                    $sallitut_maat_lisa
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
                    WHERE tuote.yhtio                         in ('".implode("','", $yhtiot)."')
                    and tuote.tuoteno                         = '$row[tuoteno]'
                    GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
                    ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
        }
        else {
          $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                    tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                    concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
                    varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
                     FROM tuote
                    JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                    JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
                    $sallitut_maat_lisa
                    and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
                    and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
                    WHERE tuote.yhtio in ('".implode("','", $yhtiot)."')
                    and tuote.tuoteno = '$row[tuoteno]'
                    ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
        }
        $varresult = mysql_query($query) or pupe_error($query);

        $echous .= "<td valign='top' $classrigh>";

        if (mysql_num_rows($varresult) > 0) {
          $hyllylisa = "";
          $echous .= "<table width='100%'>";

          // katotaan jos meillä on tuotteita varaamassa saldoa joiden varastopaikkaa ei enää ole olemassa...
          list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($row["tuoteno"], 'ORVOT', '', '', '', '', '', '', '', $saldoaikalisa);
          $orvot *= -1;

          $normi_myytavissa = "";
          while ($saldorow = mysql_fetch_assoc($varresult)) {

            list($saldo, $hyllyssa, $myytavissa, $sallittu) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $saldorow["era"]);

            //  Listataan vain varasto jo se ei ole kielletty
            if ($sallittu === TRUE) {
              // hoidetaan pois problematiikka jos meillä on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
              if ($orvot > 0) {
                if ($myytavissa >= $orvot and $saldorow["yhtio"] == $kukarow["yhtio"]) {
                  // poistaan orpojen varaamat tuotteet tältä paikalta
                  $myytavissa = $myytavissa - $orvot;
                  $orvot = 0;
                }
                elseif ($orvot > $myytavissa and $saldorow["yhtio"] == $kukarow["yhtio"]) {
                  // poistetaan niin paljon orpojen saldoa ku voidaan
                  $orvot = $orvot - $myytavissa;
                  $myytavissa = 0;
                }
              }

              if ($myytavissa != 0 or ($lisatiedot != "" and $kukarow["extranet"] == "" and $hyllyssa != 0)) {
                $echous .= "  <tr>
                          <td class='$vari' nowrap>$saldorow[nimitys] $saldorow[tyyppi]</td>
                          <td class='$vari' align='right' nowrap>".sprintf("%.2f", $myytavissa)." ".t_avainsana("Y", "", "and avainsana.selite='$row[yksikko]'", "", "", "selite")."</td>
                          </tr>";
                $normi_myytavissa = "yep";
              }

              if ($lisatiedot != "" and $kukarow["extranet"] == "" and $hyllyssa != 0) {

                $hyllylisa .= "  <tr>
                          <td class='$vari' align='right' nowrap>".sprintf("%.2f", $hyllyssa)."</td>
                          </tr>";

              }
            }
          }

          if ($normi_myytavissa == "") {
            if ($poistetut == "") {
              $riittaako_saldo = "EI";
            }
          }

          $echous .= "</table></td>";
        }
        $echous .= "</td>";


        if ($lisatiedot != "" and $kukarow["extranet"] == "") {
          $echous .= "<td valign='top' $classrigh>";

          if (mysql_num_rows($varresult) > 0 and $hyllylisa != "") {

            $echous .= "<table width='100%'>";
            $echous .= $hyllylisa;
            $echous .= "</table></td>";
          }
          $echous .= "</td>";
        }
      }

      // jos kyseessä on isätuote ja sen saldo on loppu, lasketaan lapsien määrä
      if ($row["tuoteperhe"] == $row["tuoteno"] and $riittaako_saldo == "EI") {
        $lapsien_maara = -1;
        foreach ($rows as $lapsirivit) {
          if ($lapsirivit["tuoteperhe"] == $row["tuoteno"]) {
            $lapsien_maara++;
          }
        }
        continue;
      }
      elseif ($riittaako_saldo == "EI") {
        continue;
      }



      if (isset($row['sarjatunnus']) and $row["sarjatunnus"] > 0 and $kukarow["extranet"] == "" and function_exists("sarjanumeronlisatiedot_popup")) {
        if ($lisatiedot != "") {
          echo "<tr><td colspan='7' class='back'><br></td></tr>";
        }
        else {
          echo "<tr><td colspan='8' class='back'><br></td></tr>";
        }
      }

      echo "<tr>";



      if (!isset($originaalit)) {
        $orginaaalit = table_exists("tuotteen_orginaalit");
      }

      $linkkilisa = "";

      //  Liitetään originaalitietoja
      if ($orginaaalit === true) {
        $id = md5(uniqid());

        $query = "SELECT *
                  FROM tuotteen_orginaalit
                  WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$row[tuoteno]'";
        $orgres = mysql_query($query) or pupe_error($query);

        if (mysql_num_rows($orgres)>0) {
          $linkkilisa = "<div id='div_$id' class='popup' style='width: 300px'>
            <table width='300px' align='center'>
            <caption><font class='head'>Tuotteen originaalit</font></caption>
            <tr>
              <th>".t("Tuotenumero")."</th>
              <th>".t("Merkki")."</th>
              <th>".t("Hinta")."</th>
            </tr>";

          while ($orgrow = mysql_fetch_assoc($orgres)) {

            $linkkilisa .= "<tr>
                  <td>$orgrow[orig_tuoteno]</td>
                  <td>$orgrow[merkki]</td>
                  <td>$orgrow[orig_hinta]</td>
                </tr>";
          }

          $linkkilisa .= "</table></div>";

          if ($kukarow["extranet"] != "") {
            $linkkilisa .= "&nbsp;&nbsp;<a src='#' class='tooltip' id='$id'><img src='pics/lullacons/info.png' height='13'></a>";
          }
          else {
            $linkkilisa .= "&nbsp;&nbsp;<a src='#' class='tooltip' id='$id'><img src='../pics/lullacons/info.png' height='13'></a>";
          }
        }
      }

      if ($kukarow["extranet"] != "") {
        echo "<td valign='top' class='$vari' $classleft>$row[tuoteno] $linkkilisa ";
      }
      else {
        echo "<td valign='top' class='$vari' $classleft><a href='../tuote.php?tuoteno=".urlencode($row["tuoteno"])."&tee=Z'>$row[tuoteno]</a>$linkkilisa ";
      }

      if ($lisatiedot != "") {
        echo "<br>$row[toim_tuoteno]";
      }

      echo "</td>";

      echo "<td valign='top' class='$vari' $classmidl>$row[tuotemerkki]</td>";
      echo "<td valign='top' class='$vari' $classmidl>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";
      echo "<td valign='top' class='$vari' $classmidl>$row[osasto]<br>$row[try]</td>";

      $myyntihinta = hintapyoristys($row["myyntihinta"]). " $yhtiorow[valkoodi]";

      // jos kyseessä on extranet asiakas yritetään näyttää kaikki hinnat oikeassa valuutassa
      if ($kukarow["extranet"] != "") {
        if ($oleasrow["valkoodi"] != $yhtiorow["valkoodi"]) {

          $myyntihinta = hintapyoristys($row["myyntihinta"])." $yhtiorow[valkoodi]";

          $query = "SELECT *
                    from hinnasto
                    where yhtio  = '$kukarow[yhtio]'
                    and tuoteno  = '$row[tuoteno]'
                    and valkoodi = '$oleasrow[valkoodi]'
                    and laji     = ''
                    and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
                    order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
                    limit 1";
          $olhires = mysql_query($query) or pupe_error($query);

          if (mysql_num_rows($olhires) == 1) {
            $olhirow = mysql_fetch_assoc($olhires);
            $myyntihinta = hintapyoristys($olhirow["hinta"])." $olhirow[valkoodi]";
          }
          elseif ($olhirow["kurssi"] != 0) {
            $myyntihinta = hintapyoristys(yhtioval($row["myyntihinta"], $olhirow["kurssi"])). " $oleasrow[valkoodi]";
          }
        }
      }
      else {
        $query = "SELECT distinct valkoodi, maa
                  from hinnasto
                  where yhtio = '$kukarow[yhtio]'
                  and tuoteno = '$row[tuoteno]'
                  and laji    = ''
                  order by maa, valkoodi";
        $hintavalresult = mysql_query($query) or pupe_error($query);

        while ($hintavalrow = mysql_fetch_assoc($hintavalresult)) {

          // katotaan onko tuotteelle valuuttahintoja
          $query = "SELECT *
                    from hinnasto
                    where yhtio  = '$kukarow[yhtio]'
                    and tuoteno  = '$row[tuoteno]'
                    and valkoodi = '$hintavalrow[valkoodi]'
                    and maa      = '$hintavalrow[maa]'
                    and laji     = ''
                    and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
                    order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
                    limit 1";
          $hintaresult = mysql_query($query) or pupe_error($query);

          while ($hintarow = mysql_fetch_assoc($hintaresult)) {
            $myyntihinta .= "<br>$hintarow[maa]: ".hintapyoristys($hintarow["hinta"])." $hintarow[valkoodi]";
          }
        }
      }

      echo "<td valign='top' class='$vari' align='right' $classmidl nowrap>$myyntihinta";

      if ($lisatiedot != "" and $kukarow["extranet"] == "") {
        echo "<br>".hintapyoristys($row["nettohinta"])." $yhtiorow[valkoodi]";
      }

      echo "</td>";

      echo "<td valign='top' class='$vari' $classmidl>$row[aleryhma]";

      if ($lisatiedot != "" and $kukarow["extranet"] == "") {
        echo "<br>$row[status]";
      }

      echo "</td>";

      $edtuoteno = $row["korvaavat"];

      echo $echous;

      if ($oikeurow["paivitys"] == 1 and ($kukarow["kuka"] != "" or is_numeric($ostoskori))) {
        echo "<td valign='top' align='right' class='$vari' nowrap>";

        if ($tultiin == "futur") {
          echo " <input type='hidden' name='tultiin' value='$tultiin'>";
        }

        echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
        echo "<input type='text' size='3' name='tilkpl[$yht_i]'> ";
        echo "<input type='submit' value = '".t("Lisää")."'>";
        echo "</td>";
        $yht_i++;
      }

      // Onko liitetiedostoja
      $liitteet = liite_popup("TN", $row["tunnus"]);

      if ($liitteet != "") echo "<td class='back' style='vertical-align: top;'>$liitteet</td>";

      echo "</tr>";

      if (isset($row['sarjatunnus']) and $row["sarjatunnus"] > 0 and $kukarow["extranet"] == "" and function_exists("sarjanumeronlisatiedot_popup")) {
        list($kommentit, $text_output, $kuvalisa_bin, $ostohinta, $tuotemyyntihinta) = sarjanumeronlisatiedot_popup($row["sarjatunnus"], $row["sarjayhtio"], '', '', '100%', '');

        if ($lisatiedot != "") {
          echo "<tr><td colspan='7'>$kommentit</td></tr>";
        }
        else {
          echo "<tr><td colspan='6'>$kommentit</td></tr>";
        }
      }
    }

    echo "</form>";
    echo "</table>";

  }
  else {
    echo t("Yhtään tuotetta ei löytynyt")."!";
  }

  if (mysql_num_rows($result) == 500) {
    echo "<br><br><font class='message'>".t("Löytyi yli 500 tuotetta, tarkenna hakuasi")."!</font>";
  }
}

if (@include "inc/footer.inc");
elseif (@include "footer.inc");
else exit;
