<?php

if (!empty($_REQUEST["naytetaan_kate"])) {
  setcookie("katteen_nayttaminen", $_REQUEST["naytetaan_kate"]);
}
elseif (!empty($_COOKIE["katteen_nayttaminen"])) {
  $_REQUEST["naytetaan_kate"] = $_COOKIE["katteen_nayttaminen"];
}

if (isset($_REQUEST["komento"]) and in_array("PDF_RUUDULLE", $_REQUEST["komento"])) {
  $nayta_pdf = 1; //Generoidaan .pdf-file
}
else {
  unset($nayta_pdf);
}

if (isset($_REQUEST['tulosta_maksusopimus']) and is_numeric(trim($_REQUEST['tulosta_maksusopimus']))) {
  $nayta_pdf = 1;
  $ohje = 'off';
}

if (isset($_REQUEST['ajax_toiminto']) and trim($_REQUEST['ajax_toiminto']) == 'tarkista_tehtaan_saldot') {
  $nayta_pdf = 1;
  $ohje = 'off';
}

if (isset($_POST["tappi"])) {
  if ($_POST["tappi"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

if (isset($_REQUEST['ajax_popup'])) {
  $no_head = true;
}

if (@include "../inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

if (@include "rajapinnat/logmaster/logmaster-functions.php");
elseif (@include "logmaster-functions.php");
else exit;

if ($tila == "KORVAMERKITSE" or $tila == "KORVAMERKITSE_AJAX") {

  $query = "SELECT otunnus
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$rivitunnus}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0 and mysql_result($result, 0) == $tilausnumero) {

    $korvamerkinta = mysql_real_escape_string($korvamerkinta);

    if (empty($korvamerkinta)) {
      $korvamerkinta = '.';
    }

    $query = "UPDATE tilausrivin_lisatiedot
              JOIN tilausrivi
                ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio
                AND tilausrivi.tunnus                     = tilausrivin_lisatiedot.tilausrivitunnus
              JOIN lasku
                ON lasku.yhtio = tilausrivin_lisatiedot.yhtio
                AND lasku.tunnus                          = tilausrivi.otunnus
              SET tilausrivin_lisatiedot.korvamerkinta = '{$korvamerkinta}'
              WHERE tilausrivin_lisatiedot.yhtio          = '{$kukarow['yhtio']}'
              AND tilausrivin_lisatiedot.tilausrivitunnus = '{$rivitunnus}'
              AND lasku.tunnus                            = '{$kukarow['kesken']}'";
    pupe_query($query);
  }

  if ($tila == "KORVAMERKITSE_AJAX") {
    echo json_encode("OK");
    exit;
  }

  $tila = '';
  $rivitunnus = '';
  $keratty = '';
  $kerattyaika = 0;
  $toimitettu = '';
  $toimitettuaika = 0;
}

if ($yhtiorow['tilausrivin_esisyotto'] == 'K' and isset($ajax_toiminto) and trim($ajax_toiminto) == 'esisyotto') {
  $params = array(
    'tilausnumero' => $tilausnumero,
    'tuoteno' => $tuoteno,
    'hinta_ajax' => $hinta,
    'hinta_esisyotetty' => $hinta_esisyotetty,
    'kpl' => $kpl,
    'ale1' => $ale1,
    'ale2' => $ale2,
    'ale3' => $ale3,
    'tilausrivi_alvillisuus' => $tilausrivi_alvillisuus,
    'netto' => $netto,
    'alv' => $alv
  );

  $result = tilausrivin_esisyotto($params);

  echo json_encode($result);
  exit;
}

$e1 = (isset($yhtiorow['tilauksen_myyntieratiedot']) and $yhtiorow['tilauksen_myyntieratiedot'] != '');
$e2 = (isset($yhtiorow['laiterekisteri_kaytossa']) and $yhtiorow['laiterekisteri_kaytossa'] != '');
$e3 = (isset($tappi) and $tappi == "lataa_tiedosto");
$e4 = isset($tmpfilenimi);

if (($e1 or $e2) and $e3 and $e4) {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

if ($kukarow['extranet'] == '') {
  if (isset($ajax_popup)) {
    require "tuotetiedot.inc";
    exit;
  }

  js_popup();
}

$oikeus_nahda_kate = ($kukarow["naytetaan_katteet_tilauksella"] == "Y"
  or $kukarow["naytetaan_katteet_tilauksella"] == "B"
  or ($kukarow["naytetaan_katteet_tilauksella"] == ""
    and ($yhtiorow["naytetaan_katteet_tilauksella"] == "Y"
      or $yhtiorow["naytetaan_katteet_tilauksella"] == "B")));

$naytetaanko_kate = ((empty($naytetaan_kate) or $naytetaan_kate != "E") and $oikeus_nahda_kate);

if ($tee == "PAIVITA_KASSALIPAS" and ($kukarow["dynaaminen_kassamyynti"] != "" or
    ($kukarow["dynaaminen_kassamyynti"] == "" and
      $yhtiorow["dynaaminen_kassamyynti"] != ""))
) {
  $paivita_kassalipas_query = "UPDATE lasku
                               SET kassalipas = '{$kertakassa}'
                               WHERE yhtio = '{$kukarow["yhtio"]}'
                               AND tunnus  = '{$kukarow["kesken"]}'";

  pupe_query($paivita_kassalipas_query);

  $tee = "";
}

$maksupaate_kassamyynti = (($yhtiorow['maksupaate_kassamyynti'] == 'K' and
    $kukarow["maksupaate_kassamyynti"] == "") or
  $kukarow["maksupaate_kassamyynti"] == "K");

if ($tee == "laheta_viesti" and $yhtiorow["vahvistusviesti_asiakkaalle"] == "Y") {
  require_once "inc/jt_ja_tyomaarays_valmis_viesti.inc";

  $viestin_lahetys_onnistui =
    laheta_vahvistusviesti($zoner_tunnarit["username"],
    $zoner_tunnarit["salasana"],
    $tilausnumero,
    true);

  $tee = "";
}

if ($yhtiorow["varastonarvon_jako_usealle_valmisteelle"] == "K" and isset($ajax_toiminto) and trim($ajax_toiminto) == 'tallenna_painoarvot') {

  foreach ($painoarvot as $tunnus => $painoarvo) {
    $tunnus = (int) $tunnus;
    $painoarvo = (float) $painoarvo;

    $query = "UPDATE tilausrivi
              SET valmistus_painoarvo = $painoarvo
              WHERE tunnus = $tunnus";
    pupe_query($query);
  }

  die();
}
$sahkoinen_tilausliitanta = @file_exists("../inc/sahkoinen_tilausliitanta.inc");
$sahkoinen_lahete = @file_exists("../inc/sahkoinen_lahete.class.inc");
$sahkoinen_lahete_toim = array('RIVISYOTTO', 'PIKATILAUS');

require 'validation/Validation.php';

if (isset($ajax_toiminto) and trim($ajax_toiminto) == 'tarkista_tehtaan_saldot') {

  if ($sahkoinen_tilausliitanta) {

    $hae = 'tarkista_tehtaan_saldot';
    $tunnus = (int) $id;
    $otunnus = (int) $otunnus;
    $tuoteno = $tuoteno;
    $myytavissa = (int) $myytavissa;
    $cust_id = $cust_id;
    $username = $username;
    $password = $password;
    $tt_tunnus = (int) $tt_tunnus;

    require "inc/sahkoinen_tilausliitanta.inc";
  }

  if (!isset($data)) $data = array('id' => 0, 'error' => true, 'error_msg' => utf8_encode(t("Haku ei onnistunut! Ole yhteydessä IT-tukeen")));

  echo json_encode($data);
  exit;
}

if (isset($livesearch_tee) and $livesearch_tee == "SARJANUMEROHAKU") {
  livesearch_sarjanumerohaku();
  exit;
}

if (isset($livesearch_tee) and $livesearch_tee == "TUOTEMERKKIHAKU") {
  livesearch_tuotemerkkihaku();
  exit;
}

enable_ajax();

$tilauskaslisa = "";

// extranet vai normipupe?
if (strpos(dirname(__FILE__), "/tilauskasittely") !== FALSE) {
  $tilauskaslisa = "tilauskasittely/";
}
if (isset($liite_popup_toiminto) and $liite_popup_toiminto == "AK") {
  liite_popup("AK", $tuotetunnus, $width, $height);
}
else {
  liite_popup("JS");
}

for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
  if (!isset(${'ale'.$alepostfix})) { ${'ale'.$alepostfix} = "";}
  if (!isset(${'ale_array'.$alepostfix})) { ${'ale_array'.$alepostfix} = "";}
}

if (!isset($alatila)) $alatila = "";
if (!isset($alv)) $alv = "";
if (!isset($alv_array)) $alv_array = "";
if (!isset($asiakasid)) $asiakasid = "";
if (!isset($asiakasOnProspekti)) $asiakasOnProspekti = "";
if (!isset($avaa_rekursiiviset)) $avaa_rekursiiviset = "";
if (!isset($etayhtio_totaalisumma)) $etayhtio_totaalisumma = 0;
if (!isset($from)) $from = "";
if (!isset($hinta)) $hinta = "";
if (!isset($hinta_array)) $hinta_array = "";
if (!isset($hintojen_vaihto)) $hintojen_vaihto = "JOO";
if (!isset($jarjesta)) $jarjesta = "";
if (!isset($jt_kayttoliittyma)) $jt_kayttoliittyma = "";
if (!isset($jysum)) $jysum = "";
if (!isset($jyvsumma)) $jyvsumma = "";
if (!isset($kaytiin_otsikolla)) $kaytiin_otsikolla = "";
if (!isset($kerayskka)) $kerayskka = 0;
if (!isset($keraysppa)) $keraysppa = 0;
if (!isset($kerayspvm)) $kerayspvm = "";
if (!isset($keraysvva)) $keraysvva = 0;
if (!isset($kommentti_select)) $kommentti_select = "";
if (!isset($kpl)) $kpl = "";
if (!isset($kpl2)) $kpl2 = "";
if (!isset($kpl_array)) $kpl_array = "";
if (!isset($kutsuja)) $kutsuja = "";
if (!isset($lead)) $lead = "";
if (!isset($lisavarusteita)) $lisavarusteita = "";
if (!isset($lisax)) $lisax = "";
if (!isset($livesearch_tee)) $livesearch_tee = "";
if (!isset($lopetus)) $lopetus = "";
if (!isset($luotunnusnippu)) $luotunnusnippu = "";
if (!isset($maksutapa)) $maksutapa = "";
if (!isset($menutila)) $menutila = "";
if (!isset($mista)) $mista = "";
if (!isset($muokkauslukko)) $muokkauslukko = "";
if (!isset($muokkauslukko_rivi)) $muokkauslukko_rivi = "";
if (!isset($myos_prospektit)) $myos_prospektit = "";
if (!isset($myyntikielto)) $myyntikielto = "";
if (!isset($myy_sarjatunnus)) $myy_sarjatunnus = "";
if (!isset($nayta_sostolisateksti)) $nayta_sostolisateksti = "";
if (!isset($netto)) $netto = "";
if (!isset($netto_array)) $netto_array = "";
if (!isset($olpaikalta)) $olpaikalta = "";
if (!isset($omalle_tilaukselle)) $omalle_tilaukselle = "";
if (!isset($orig_alatila)) $orig_alatila = "";
if (!isset($orig_tila)) $orig_tila = "";
if (!isset($osatoimkielto)) $osatoimkielto = "";
if (!isset($paikka)) $paikka = "";
if (!isset($paikka_array)) $paikka_array = "";
if (!isset($painotettukehayhteensa)) $painotettukehayhteensa = 0;
if (!isset($perheid)) $perheid = "";
if (!isset($pika_paiv_merahti)) $pika_paiv_merahti = "";
if (!isset($pkrow)) $pkrow = "";
if (!isset($projektilla)) $projektilla = "";
if (!isset($rahtihinta)) $rahtihinta = "";
if (!isset($rahtisopimus)) $rahtisopimus = "";
if (!isset($ruutulimit)) $ruutulimit = "";
if (!isset($saako_liitaa_laskuja_tilaukseen)) $saako_liitaa_laskuja_tilaukseen = "";
if (!isset($sarjanumero_dropdown)) $sarjanumero_dropdown = "";
if (!isset($smsnumero)) $smsnumero = "";
if (!isset($syotetty_ytunnus)) $syotetty_ytunnus = "";
if (!isset($tapa)) $tapa = "";
if (!isset($tee)) $tee = "";
if (!isset($tiedot_laskulta)) $tiedot_laskulta = "";
if (!isset($tila)) $tila = "";
if (!isset($tilausnumero)) $tilausnumero = "";
if (!isset($tilausrivilinkki)) $tilausrivilinkki = "";
if (!isset($tilaustyyppi)) $tilaustyyppi = "";
if (!isset($toimaika)) $toimaika = "";
if (!isset($toimittajan_tunnus)) $toimittajan_tunnus = "";
if (!isset($toimkka)) $toimkka = 0;
if (!isset($toimppa)) $toimppa = 0;
if (!isset($toimvva)) $toimvva = 0;
if (!isset($toim_kutsu)) $toim_kutsu = "";
if (!isset($trivtyrow)) $trivtyrow = "";
if (!isset($tulostetaan)) $tulostetaan = "";
if (!isset($tuotenimitys)) $tuotenimitys = "";
if (!isset($tuotenimitys_force)) $tuotenimitys_force = "";
if (!isset($tuoteno)) $tuoteno = "";
if (!isset($tuoteno_array)) $tuoteno_array = "";
if (!isset($tuotteenpainotettukehayht)) $tuotteenpainotettukehayht = array();
if (!isset($tyojono)) $tyojono = "";
if (!isset($ulos)) $ulos = "";
if (!isset($uusitoimitus)) $uusitoimitus = "";
if (!isset($valitsetoimitus)) $valitsetoimitus = "";
if (!isset($valitsetoimitus_vaihdarivi)) $valitsetoimitus_vaihdarivi = "";
if (!isset($var)) $var = "";
if (!isset($varaosakommentti)) $varaosakommentti = "";
if (!isset($varasto)) $varasto = "";
if (!isset($variaatio_tuoteno)) $variaatio_tuoteno = "";
if (!isset($var_array)) $var_array = "";
if (!isset($yksi_suoratoimittaja)) $yksi_suoratoimittaja = "";
if (!isset($ylatila)) $ylatila = "";
if (!isset($luottorajavirhe_ylivito_valmis)) $luottorajavirhe_ylivito_valmis = true;

if (empty($_POST["tilausrivi_alvillisuus"]) and isset($_COOKIE["tilausrivi_alvillisuus"])) {
  $tilausrivi_alvillisuus = $_COOKIE["tilausrivi_alvillisuus"];
}
elseif (!isset($tilausrivi_alvillisuus)) {
  $tilausrivi_alvillisuus = "";
}
else {
  $tilausrivi_alvillisuus = $_POST["tilausrivi_alvillisuus"];
}

if (!isset($valmiste_vai_raakaaine) and $toim == "VALMISTAVARASTOON") {
  $_cookie_isset = isset($_COOKIE["valmiste_vai_raakaaine"]);
  $valmiste_vai_raakaaine = $_cookie_isset ? $_COOKIE["valmiste_vai_raakaaine"] : 'valmiste';
}
elseif (!isset($valmiste_vai_raakaaine)) {
  $valmiste_vai_raakaaine = 'valmiste';
}

// Setataan lopetuslinkki, jotta pääsemme takaisin tilaukselle jos käydään jossain muualla
$tilmyy_lopetus = "{$palvelin2}{$tilauskaslisa}tilaus_myynti.php////toim=$toim//projektilla=$projektilla//tilausnumero=$tilausnumero//ruutulimit=$ruutulimit//tilausrivi_alvillisuus=$tilausrivi_alvillisuus//mista=$mista";

$_onko_valmistus = ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE");
$saldo_valmistuksella = ($_onko_valmistus and $yhtiorow["saldo_varastossa_valmistuksella"] == "S");

if ($lopetus != "") {
  // Lisätään tämä lopetuslinkkiin
  $tilmyy_lopetus = $lopetus."/SPLIT/".$tilmyy_lopetus;
}

if (isset($tulosta_maksusopimus) and is_numeric(trim($tulosta_maksusopimus))) {
  require 'tulosta_maksusopimus.inc';
  tulosta_maksusopimus($kukarow, $yhtiorow, $laskurow, $kieli);
  exit;
}

if ($livesearch_tee == "TUOTEHAKU") {
  $query   = "SELECT laskun_lisatiedot.*, lasku.*
              FROM lasku
              LEFT JOIN laskun_lisatiedot ON (
                laskun_lisatiedot.yhtio = lasku.yhtio AND
                laskun_lisatiedot.otunnus = lasku.tunnus
              )
              WHERE lasku.tunnus = '{$kukarow['kesken']}'
              AND lasku.yhtio    = '{$kukarow['yhtio']}'
              AND lasku.tila     != 'D'";
  $result = pupe_query($query);
  $laskurow = mysql_fetch_assoc($result);

  livesearch_tuotehaku();
  exit;
}

if (in_array($yhtiorow["livetuotehaku_tilauksella"], array("J", "K"))) {
  enable_ajax();
}

if ($kukarow["extranet"] == "") {
  echo "<script src='../js/tilaus.js'></script>";
  echo "<script src='../js/tilaus_myynti/tilaus_myynti.js'></script>";
  echo "<script src='../js/maksupaate.js'></script>";
}

if ((int) $luotunnusnippu > 0 and $tilausnumero == $kukarow["kesken"] and (int) $kukarow["kesken"] > 0) {
  $query = "UPDATE lasku
            SET tunnusnippu = tunnus
            where yhtio     = '$kukarow[yhtio]'
            and tunnus      = '$kukarow[kesken]'
            and tunnusnippu = 0";
  $result = pupe_query($query);

  $valitsetoimitus = $toim;
}

if ($kukarow["extranet"] == "" and in_array($toim, array("PIKATILAUS", "RIVISYOTTO", "TARJOUS")) and file_exists($pupe_root_polku . '/tilauskasittely/ostoskorin_haku.inc')) {
  require_once 'tilauskasittely/ostoskorin_haku.inc';
}

// Vaihdetaan tietyn projektin toiseen toimitukseen
//  HUOM: tämä käyttää aktivointia joten tämä on oltava aika alussa!! (valinta on onchage submit rivisyötössä joten noita muita paremetreja ei oikein voi passata eteenpäin..)
if ((int) $valitsetoimitus > 0 and $valitsetoimitus != $tilausnumero) {
  $tilausnumero = $valitsetoimitus;
  $from       = "VALITSETOIMITUS";
  $mista       = "";

  $query = "SELECT tila, alatila, tilaustyyppi
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$tilausnumero'";
  $result = pupe_query($query);
  $toimrow = mysql_fetch_assoc($result);

  $orig_tila    = $toimrow["tila"];
  $orig_alatila = $toimrow["alatila"];

  if ($toimrow["tila"] == "A" or (($toimrow["tila"] == "L" or $toimrow["tila"] == "N") and $toimrow["tilaustyyppi"] == "A")) {
    $toim = (strtolower($asentaja) == 'tyomaarays_asentaja' or $toim == 'TYOMAARAYS_ASENTAJA') ? "TYOMAARAYS_ASENTAJA" : "TYOMAARAYS";
  }
  elseif ($toimrow["tila"] == "L" or $toimrow["tila"] == "N") {
    if ($toim != "RIVISYOTTO" and $toim != "PIKATILAUS") $toim = "RIVISYOTTO";
  }
  elseif ($toimrow["tila"] == "T") {
    $toim = "TARJOUS";
  }
  elseif ($toimrow["tila"] == "C") {
    $toim = "REKLAMAATIO";
  }
  elseif ($toimrow["tila"] == "V") {
    $toim = "VALMISTAASIAKKAALLE";
  }
  elseif ($toimrow["tila"] == "W") {
    $toim = "VALMISTAVARASTOON";
  }
  elseif ($toimrow["tila"] == "R") {
    $toim = "PROJEKTI";
  }
}
elseif (in_array($valitsetoimitus, array("ENNAKKO", "EXTENNAKKO", "TARJOUS", "PIKATILAUS", "RIVISYOTTO", "VALMISTAASIAKKAALLE", "VALMISTAVARASTOON", "SIIRTOLISTA", "TYOMAARAYS", "TYOMAARAYS_ASENTAJA", "REKLAMAATIO", "PROJEKTI"))) {
  $uusitoimitus = $valitsetoimitus;
}

// Jos tilausnumero on jollain muulla käyttäjällä kesken
if (!aktivoi_tilaus($tilausnumero, $session, $orig_tila, $orig_alatila)) {

  // katsotaan onko muilla aktiivisena
  $result = tilaus_aktiivinen_kayttajalla($tilausnumero);

  if (mysql_num_rows($result) != 0) {
    $row = mysql_fetch_assoc($result);

    echo "<font class='error'>".t("Tilaus on aktiivisena käyttäjällä")." $row[nimi] ($row[kuka]). ".t("Tilausta ei voi tällä hetkellä muokata").".</font><br>";

    if (tarkista_oikeus("kayttajat.php", "", "1")) {
      echo "<br><form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
        <input type='hidden' name='selkuka' value='{$row['kuka']}'>
        <input type='hidden' name='toim' value='{$toim}'>
        <input type='hidden' name='tee' value='DELKESKEN'>
        <input type='hidden' name='orig_tila' value='$orig_tila'>
        <input type='hidden' name='orig_alatila' value='$orig_alatila'>
        <input type='submit' value='* ", t("Vapauta käyttäjän"), " $row[nimi] ($row[kuka]). ", t("keskenoleva tilaus"), " *'>
        </form>";
    }
  }

  exit();
}
else {
  // Näin ostataan valita pikatilaus
  if ($toim == "RIVISYOTTO" and isset($PIKATILAUS)) {
    $toim = "PIKATILAUS";
  }
  // Jos tullaan projektille pitää myös aktivoida $projektilla
  elseif ($toim == "PROJEKTI") {
    $projektilla = $tilausnumero;
  }
  elseif ($toim == "VALMISTAASIAKKAALLE" and $tilausnumero != "") {
    $tyyppiquery  = "SELECT tilaustyyppi FROM lasku WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$tilausnumero'";
    $tyyppiresult = pupe_query($tyyppiquery);

    if (mysql_num_rows($tyyppiresult) != 0) {
      $tyyppirow = mysql_fetch_assoc($tyyppiresult);

      if (strtoupper($tyyppirow['tilaustyyppi']) == 'W') {
        $toim = "VALMISTAVARASTOON";
      }
    }
    else {
      echo "<font class='error'>".t("Tilaus katosi")."!</font><br>";
      $tilausnumero = "";
    }
  }
}

if (($kukarow["extranet"] != '' and $toim != 'EXTRANET' and $toim != 'EXTRANET_REKLAMAATIO') or ($kukarow["extranet"] == "" and ($toim == "EXTRANET" or $toim == "EXTRANET_REKLAMAATIO"))) {
  //aika jännä homma jos tänne jouduttiin
  exit;
}

if ($tee == 'TARKISTA') {
  $uquery = "UPDATE lasku
             SET tilaustyyppi = 'L'
             WHERE yhtio      = '{$kukarow['yhtio']}'
             AND tunnus       = $tilausnumero
             AND tilaustyyppi = 'H'
             AND tila         = 'N'";

  $uresult = pupe_query($uquery);

  $lquery = "SELECT *
             FROM lasku
             WHERE yhtio = '{$kukarow['yhtio']}'
             AND tunnus  = $tilausnumero";

  $lresult  = pupe_query($lquery);
  $laskurow = mysql_fetch_assoc($lresult);

  $xquery = "SELECT *
             FROM tilausrivi
             WHERE yhtio  = '{$kukarow['yhtio']}'
             AND otunnus  = $tilausnumero
             AND tyyppi  != 'D'";

  $xresult = pupe_query($xquery);

  while ($xrow = mysql_fetch_assoc($xresult)) {
    // Lisätään tuoteno_array muttujaan vain isätuotteet,
    // koska muuten lapset lisätää perheen mukana JA itsenäisinä tuotteina
    if ($xrow["perheid"] == 0 or $xrow["perheid"] == $xrow["tunnus"]) {
      $tuoteno_array[]                   = $xrow['tuoteno'];
      $kpl_array[$xrow['tuoteno']]       = $xrow['tilkpl'];
      $kommentti_array[$xrow['tuoteno']] = $xrow['kommentti'];
    }

    $query = "UPDATE tilausrivi SET
              tyyppi      = 'D'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$xrow['tunnus']}'";

    $palauta_res = pupe_query($query);
  }

  $tee         = '';
  $tarkistettu = true;
}
else {
  $tarkistettu = false;
}

if ($tee == 'PAIVITA_SARJANUMERO' and $rivitunnus > 0) {
  $sarjanumero_dropdown = (int) $sarjanumero_dropdown;

  $query = "UPDATE sarjanumeroseuranta SET
            myyntirivitunnus     = 0,
            muuttaja             = '{$kukarow['kuka']}',
            muutospvm            = now()
            WHERE yhtio          = '{$kukarow['yhtio']}'
            AND myyntirivitunnus = '{$rivitunnus}'
            AND tuoteno          = '{$sarjanumero_dropdown_tuoteno}'";
  $upd_res = pupe_query($query);

  if ($sarjanumero_dropdown != 0) {

    $query = "UPDATE sarjanumeroseuranta SET
              myyntirivitunnus     = '{$rivitunnus}',
              muuttaja             = '{$kukarow['kuka']}',
              muutospvm            = now()
              WHERE yhtio          = '{$kukarow['yhtio']}'
              AND myyntirivitunnus = 0
              AND tunnus           = '{$sarjanumero_dropdown}'";
    $upd_res = pupe_query($query);
  }

  $tee = '';
}

if ($tee == 'TEE_MYYNTITILAUKSESTA_TARJOUS' and (int) $kukarow["kesken"] > 0 and tarkista_oikeus("tilaus_myynti.php", "TARJOUS")) {

  $kukarow['kesken'] = (int) $kukarow['kesken'];

  $query = "UPDATE lasku SET
            tila         = 'T',
            alatila      = '',
            tilaustyyppi = 'T'
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND tila     = 'N'
            AND alatila  IN ('','F')
            AND tunnus   = '{$kukarow['kesken']}'";
  $upd_res = pupe_query($query);

  if (mysql_affected_rows() != 0) {

    $query = "UPDATE tilausrivi SET
              tyyppi      = 'T'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tyyppi  = 'L'
              AND otunnus = '{$kukarow['kesken']}'";
    $upd_res = pupe_query($query);

    echo "<font class='message'>", t("Tilaus %d siirretty tarjoukseksi", "", $kukarow['kesken']), "!</font><br /><br />";
    $tee = "";
    $tilausnumero = 0;
    $kukarow['kesken'] = 0;
  }
}

if ($tee == 'DELKESKEN') {
  if (tarkista_oikeus("kayttajat.php", "", "1")) {
    $query = "UPDATE kuka SET kesken = 0 WHERE kuka = '{$selkuka}' and yhtio = '{$kukarow['yhtio']}'";
    $result = pupe_query($query);

    echo "<b>", t("Käyttäjän"), " {$selkuka} ", t("keskenoleva tilaus vapautettu"), "!</b><br><br>";
    $tee = "";
  }
}

// Extranet keississä asiakasnumero tulee käyttäjän takaa
if ($kukarow["extranet"] != '') {
  // Haetaan asiakkaan tunnuksella
  $query  = "SELECT *
             FROM asiakas
             WHERE yhtio = '$kukarow[yhtio]'
             AND tunnus  = '$kukarow[oletus_asiakas]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 1) {
    $extra_asiakas = mysql_fetch_assoc($result);
    $ytunnus   = $extra_asiakas["ytunnus"];
    $asiakasid   = $extra_asiakas["tunnus"];

    if ($toim == 'EXTRANET_REKLAMAATIO') {
      $ex_tila = "C";
    }
    else {
      $ex_tila = "N";
    }

    if ($kukarow["kesken"] > 0) {
      // varmistetaan, että TILAUS on oikeasti kesken ja tälle asiakkaalle
      $query = "SELECT *
                FROM lasku
                WHERE yhtio      = '$kukarow[yhtio]'
                AND tunnus       = '$kukarow[kesken]'
                AND liitostunnus = '$asiakasid'
                AND tila         = '$ex_tila'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $tilausnumero = $kukarow["kesken"];
      }
      else {
        $tilausnumero     = "";
        $kukarow["kesken"]  = "";
      }
    }
    else {
      // jos asiakkaalla jostakin syystä kesken oleva tilausnumero on kadonnut, niin haetaan "Myyntitilaus kesken" oleva tilaus aktiiviseksi
      $query = "SELECT *
                FROM lasku
                WHERE yhtio       = '{$kukarow['yhtio']}'
                AND liitostunnus  = '$asiakasid'
                AND tila          = '$ex_tila'
                AND alatila       = ''
                AND laatija       = '{$kukarow['kuka']}'
                AND clearing     != 'EXTENNAKKO'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        $kesken_row = mysql_fetch_assoc($result);
        $tilausnumero = $kukarow['kesken'] = $kesken_row["tunnus"];

        $query = "UPDATE kuka
                  SET kesken   = '$tilausnumero'
                  WHERE yhtio   = '{$kukarow['yhtio']}'
                  AND kuka      = '{$kukarow['kuka']}'
                  AND extranet != ''";
        $result = pupe_query($query);
      }
      else {
        $tilausnumero = "";
        $kukarow["kesken"] = "";
      }
    }
  }
  else {
    echo t("VIRHE: Käyttäjätiedoissasi on virhe! Ota yhteys järjestelmän ylläpitäjään.")."<br><br>";
    exit;
  }
}

if ((int) $valitsetoimitus_vaihdarivi > 0 and $tilausnumero == $kukarow["kesken"] and $kukarow["kesken"] > 0 and ($toim != "TARJOUS" and $toim != "EXTTARJOUS")) {

  $query = "  (  SELECT tunnus
                 FROM tilausrivi
                 WHERE yhtio         = '$kukarow[yhtio]'
                 and otunnus         = '$edtilausnumero'
                 and tyyppi         != 'D'
                 and tunnus          = '$rivitunnus'
                 and uusiotunnus     = 0
                 and toimitettuaika  = '0000-00-00 00:00:00'
                 )
                 UNION
                 (  SELECT tunnus
                 FROM tilausrivi
                 WHERE yhtio         = '$kukarow[yhtio]'
                 and otunnus         = '$edtilausnumero'
                 and tyyppi         != 'D'
                 and perheid         > 0
                 and perheid         = '$rivitunnus'
                 and uusiotunnus     = 0
                 and toimitettuaika  = '0000-00-00 00:00:00'
                 )";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $aikalisa = "";

    // Haetaan uuden otsikon kerayspvm ja toimaika siirrettäville tilausriveille
    // mikäli EI ole käytössä näiden tietojen käsinsyöttö
    if ($yhtiorow["splittauskielto"] != 'K') {
      $ajat_query = "SELECT kerayspvm,
                     toimaika
                     FROM lasku
                     WHERE yhtio = '$kukarow[yhtio]'
                     AND tunnus  = $valitsetoimitus_vaihdarivi";
      $ajat = mysql_fetch_assoc(pupe_query($ajat_query));

      $aikalisa = ", kerayspvm = '{$ajat["kerayspvm"]}', toimaika = '{$ajat["toimaika"]}'";
    }

    while ($aburow = mysql_fetch_assoc($result)) {
      // Vaihdetaan rivin otunnus
      $query = "UPDATE tilausrivi
                SET
                otunnus            = '$valitsetoimitus_vaihdarivi'
                $aikalisa
                WHERE yhtio        = '$kukarow[yhtio]'
                and otunnus        = '$edtilausnumero'
                and tunnus         = '$aburow[tunnus]'
                and uusiotunnus    = 0
                and toimitettuaika = '0000-00-00 00:00:00'";
      $updres = pupe_query($query);
    }
  }

  $rivitunnus = "";
  $keratty = "";
  $kerattyaika = 0;
  $toimitettu = '';
  $toimitettuaika = 0;
}

//jos jostain tullaan ilman $toim-muuttujaa
if ($toim == "") {
  $toim = "RIVISYOTTO";
}

//korjataan hintaa ja aleprossaa
$hinta  = str_replace(',', '.', $hinta);
$kpl   = str_replace(',', '.', $kpl);

for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
  ${'ale'.$alepostfix} = str_replace(',', '.', ${'ale'.$alepostfix});
}

//Ei olla pikatilauksella, mutta ollaan jostain syystä kuitenkin ilman asiakasta ja halutaan nyt liittää se
if (isset($liitaasiakasnappi) and $kukarow["extranet"] == "") {
  $tee  = "OTSIK";
  $tila = "vaihdaasiakas";
}

// Jos ylläpidossa on luotu uusi asiakas
if (isset($from) and $from == "ASIAKASYLLAPITO" and $yllapidossa == "asiakas" and $yllapidontunnus != '' and $tilausnumero == '') {
  $tee = "OTSIK";
  $asiakasid = $yllapidontunnus;
}

// asiakasnumero on annettu, etsitään tietokannasta...
$ehto1 = ($tee == "" or
  ($myos_prospektit == "TRUE" and ($toim == "TARJOUS" or $toim == "EXTTARJOUS")));

$ehto2 = (($kukarow["extranet"] != "" and (int) $kukarow["kesken"] == 0) or
  ($kukarow["extranet"] == "" and
    ($syotetty_ytunnus != '' or $asiakasid != '') and
    ($yhtiorow['pikatilaus_focus'] != "Y" or
      loytyyko_myyja_tunnuksella($myyjanumero))));

if ($ehto1 and $ehto2) {
  if (substr($ytunnus, 0, 1) == "£") {
    $ytunnus = $asiakasid;
  }
  else {
    $ytunnus = $syotetty_ytunnus;
  }

  $kutsuja    = "otsik.inc";
  $ahlopetus   = $tilmyy_lopetus."//from=ASIAKASYLLAPITO";

  if (@include "inc/asiakashaku.inc");
  elseif (@include "asiakashaku.inc");
  else exit;

  // Ei näytetä tilausta jos meillä on asiakaslista ruudulla
  if ($monta != 1) {
    $tee = "SKIPPAAKAIKKI";
  }
}

//Luodaan otsikko
if (
  ($tee == "" and
    (
      ($toim == "PIKATILAUS" and
        ((int) $kukarow["kesken"] == 0 and ($tuoteno != '' or $asiakasid != '')) or
        ((int) $kukarow["kesken"] > 0 and $asiakasid != '' and $kukarow["extranet"] == "")
      ) or
      ($from == "CRM" and $asiakasid != '')
    )
  ) or
  ($kukarow["extranet"] != "" and (int) $kukarow["kesken"] == 0)
) {

  require "{$tilauskaslisa}luo_myyntitilausotsikko.inc";

  if (!isset($tilaustyyppi)) $tilaustyyppi = "";
  if (!isset($yhtiotoimipaikka)) $yhtiotoimipaikka = '';

  $kukarow["hintojen_vaihto"] = $hintojen_vaihto;

  $tilausnumero = luo_myyntitilausotsikko($toim, $asiakasid, $tilausnumero, $myyjanumero, '', $kantaasiakastunnus, '', $tilaustyyppi, $yhtiotoimipaikka);
  $kukarow["kesken"] = $tilausnumero;
  $kaytiin_otsikolla = "NOJOO!";

  // Setataan lopetuslinkki uudestaan tässä, jotta pääsemme takaisin tilaukselle jos käydään jossain muualla
  $tilmyy_lopetus = "{$palvelin2}{$tilauskaslisa}tilaus_myynti.php////toim=$toim//projektilla=$projektilla//tilausnumero=$tilausnumero//ruutulimit=$ruutulimit//tilausrivi_alvillisuus=$tilausrivi_alvillisuus//mista=$mista";

  if ($lopetus != "") {
    // Lisätään tämä lopetuslinkkiin
    $tilmyy_lopetus = $lopetus."/SPLIT/".$tilmyy_lopetus;
  }
}

//Haetaan otsikon kaikki tiedot
if ((int) $kukarow["kesken"] > 0) {

  if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO" or $toim == "SIIRTOTYOMAARAYS" )) {
    $query  = "SELECT laskun_lisatiedot.*, lasku.*, tyomaarays.*
               FROM lasku
               JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio AND tyomaarays.otunnus = lasku.tunnus)
               LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
               WHERE lasku.tunnus  = '$kukarow[kesken]'
               AND lasku.yhtio     = '$kukarow[yhtio]'
               AND lasku.tila     != 'D'";
  }
  else {
    // pitää olla: siirtolista, sisäinen työmääräys, reklamaatio, tarjous, valmistus, myyntitilaus, ennakko, myyntitilaus, ylläpitosopimus, projekti
    $query   = "SELECT laskun_lisatiedot.*, lasku.*
                FROM lasku
                LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                WHERE lasku.tunnus = '$kukarow[kesken]'
                AND lasku.yhtio    = '$kukarow[yhtio]'
                AND lasku.tila     in ('G','S','C','T','V','N','E','L','0','R')
                AND (lasku.alatila != 'X' or lasku.tila = '0')";
  }
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<br><br><br>".t("VIRHE: Tilaustasi ei löydy tai se on mitätöity/laskutettu")."! ($kukarow[kesken])<br><br><br>";

    $query = "UPDATE kuka
              SET kesken = 0
              WHERE yhtio = '$kukarow[yhtio]'
              AND kuka    = '$kukarow[kuka]'";
    $result = pupe_query($query);
    exit;
  }

  $laskurow = mysql_fetch_assoc($result);

  if ($maksupaate_kassamyynti and isset($maksupaatetapahtuma)) {
    if ($maksupaatetapahtuma) {
      $korttimaksutapahtuman_status =
        maksa_maksupaatteella($laskurow, $kaikkiyhteensa, $korttimaksu, $peruutus);
    }

    $kaikkiyhteensa = isset($kaikkiyhteensa) ? $kaikkiyhteensa : false;
    $kateista_annettu = isset($kateista_annettu) ? $kateista_annettu : 0;

    list($loytyy_maksutapahtumia, $maksettavaa_jaljella, $kateismaksu["luottokortti"],
      $kateismaksu["pankkikortti"]) =
      jaljella_oleva_maksupaatesumma($laskurow["tunnus"], $kaikkiyhteensa);

    if ($loytyy_maksutapahtumia and ($maksettavaa_jaljella - $kateista_annettu) == 0 and
      ($kateismaksu["luottokortti"] != 0 or
        $kateismaksu["pankkikortti"] != 0)
    ) {
      $tee = "VALMIS";
      $seka = "kylla";
    }
  }

  if ($yhtiorow["extranet_poikkeava_toimitusosoite"] == "Y") {
    if (isset($poikkeava_toimitusosoite) and $poikkeava_toimitusosoite == "N") {
      $tnimi     = $laskurow["nimi"];
      $tnimitark = $laskurow["nimitark"];
      $tosoite   = $laskurow["osoite"];
      $tpostino  = $laskurow["postino"];
      $tpostitp  = $laskurow["postitp"];
      $toim_maa  = $laskurow["maa"];
    }

    if ($tnimi) {
      $toimitusosoite = array(
        "nimi"     => $tnimi,
        "nimitark" => $tnimitark,
        "osoite"   => $tosoite,
        "postino"  => $tpostino,
        "postitp"  => $tpostitp,
        "maa"      => $toim_maa
      );

      $laskurow = tallenna_toimitusosoite($toimitusosoite, $laskurow);
    }
  }

  $a_qry = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$laskurow['liitostunnus']}'";
  $a_res = pupe_query($a_qry);
  $asiakasrow = mysql_fetch_assoc($a_res);

  if ($kukarow['toimipaikka'] != $laskurow['yhtio_toimipaikka'] and $yhtiorow['myyntitilauksen_toimipaikka'] == 'A') {
    $kukarow['toimipaikka'] = $laskurow['yhtio_toimipaikka'];
    $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
  }

  // Jos käytössä "semi laaja"-reklamaatiokäsittely (X)
  // Ja tilaustyyppi ei ole takuu
  // Ja ohitetaan varastoprosessi eli "suoraan laskutukseen" (eilahetetta != '')
  // Halutaan tällöin simuloida lyhyttä reklamaatioprosessia
  if ($toim == "REKLAMAATIO" and $laskurow['eilahetetta'] != '' and $yhtiorow['reklamaation_kasittely'] == 'X' and $laskurow['tilaustyyppi'] != 'U') {
    $yhtiorow['reklamaation_kasittely'] = '';
  }

  if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0 and $yhtiorow["suoratoim_ulkomaan_alarajasumma"] > 0) {
    $yhtiorow["suoratoim_ulkomaan_alarajasumma"] = round(laskuval($yhtiorow["suoratoim_ulkomaan_alarajasumma"], $laskurow["vienti_kurssi"]), 0);
  }

  if ($laskurow["toim_maa"] == "") $laskurow["toim_maa"] = $yhtiorow['maa'];

  $toimtapa_kv = t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV");
}

if (($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS') and $yhtiorow['naytetaan_tilausvahvistusnappi'] != '') {
  $naytetaan_tilausvahvistusnappi = true;
}
else {
  $naytetaan_tilausvahvistusnappi = false;
}

if (($naytetaan_tilausvahvistusnappi or
    $toim == "TARJOUS" or $toim == "EXTTARJOUS") or
  (isset($laskurow["tilaustyyppi"]) and $laskurow["tilaustyyppi"] == "T") or
  $toim == "PROJEKTI") {

  // ekotetaan javascriptiä jotta saadaan pdf:ät uuteen ikkunaan
  js_openFormInNewWindow();
}

if ($toim == "EXTRANET") {
  $otsikko = t("Extranet-Tilaus");
}
elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
  $otsikko = t("Työmääräys");
}
elseif ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") {
  if ((isset($tilaustyyppi) and $tilaustyyppi == 'U') or $laskurow['tilaustyyppi'] == 'U') {
    $otsikko = t("Takuu");
  }
  else {
    $otsikko = t("Reklamaatio");
  }
}
elseif ($toim == "VALMISTAVARASTOON") {
  $otsikko = t("Varastoonvalmistus");
}
elseif ($toim == "SIIRTOLISTA") {
  $otsikko = t("Varastosiirto");
}
elseif ($toim == "SIIRTOTYOMAARAYS") {
  $otsikko = t("Sisäinen työmääräys");
}
elseif ($toim == "MYYNTITILI") {
  $otsikko = t("Myyntitili");
}
elseif ($toim == "VALMISTAASIAKKAALLE") {
  $otsikko = t("Asiakkaallevalmistus");
}
elseif ($toim == "TARJOUS") {
  $otsikko = t("Tarjous");
}
elseif ($toim == "EXTTARJOUS") {
  $otsikko = t("Ext-Tarjous");
}
elseif ($toim == "PROJEKTI") {
  $otsikko = t("Projekti");
}
elseif ($toim == "YLLAPITO") {
  $otsikko = t("Ylläpitosopimus");
}
elseif ($toim == "EXTENNAKKO") {
  $otsikko = t("Ext-Ennakkotilaus");
}
elseif ($toim == "ENNAKKO" or (isset($tilaustyyppi) and $tilaustyyppi == 'E')) {
  $otsikko = t("Ennakkotilaus");
}
else {
  $otsikko = t("Myyntitilaus");
}

//tietyissä keisseissä tilaus lukitaan (ei syöttöriviä eikä muota muokkaa/poista-nappuloita)
$muokkauslukko = $state = "";

# Laitetaan tilaus lukkoon, jos tilaus on lähetetty ulkoiseen varastoon
$check  = true;
$check &= (!empty($laskurow['lahetetty_ulkoiseen_varastoon']));
$check &= ($laskurow['lahetetty_ulkoiseen_varastoon'] != "0000-00-00 00:00:00");
$check &= ($laskurow['tila'] == 'N' or
          ($laskurow['tila'] == 'L' and in_array($laskurow['alatila'], array('A','B','BD','C'))) or
          ($laskurow['tila'] == 'G' and in_array($laskurow['alatila'], array('','J','KJ','A','B'))));

// Tilaukset ei saa mennä ikinä lukkoon, jos parametri ulkoinen_jarjestelma_lukko = K
if ($check and $yhtiorow['ulkoinen_jarjestelma_lukko'] != 'K') {
  $muokkauslukko = 'LUKOSSA';
}

//  Projekti voidaan poistaa vain jos meillä ei ole sillä mitään toimituksia
if (isset($laskurow["tunnusnippu"]) and $laskurow["tunnusnippu"] > 0 and $toim == "PROJEKTI") {
  $query   = "SELECT tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnusnippu]' and tila IN ('L','A','V','N')";
  $abures = pupe_query($query);
  $projektilask = (int) mysql_num_rows($abures);
}

if ($oikeurow['paivitys'] != '1' or ($toim == "MYYNTITILI" and isset($laskurow["alatila"]) and $laskurow["alatila"] == "V") or ($toim == "PROJEKTI" and $projektilask > 0) or (($toim == "TARJOUS" or $toim == "EXTTARJOUS") and $projektilla > 0) or (isset($laskurow["alatila"]) and $laskurow["alatila"] == "X")) {
  if ($laskurow["tila"] != '0') {
    $muokkauslukko   = "LUKOSSA";
  }
  $state = "DISABLED";
}

// Hyväksytään tarjous ja tehdään tilaukset
if ($kukarow["extranet"] == "" and $tee == "HYVAKSYTARJOUS" and $muokkauslukko == "") {

  ///* Reload ja back-nappulatsekki *///
  if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
    echo "<font class='error'> ".t("Taisit painaa takaisin tai päivitä nappia. Näin ei saa tehdä")."! </font>";
    exit;
  }

  // Kopsataan valitut rivit uudelle myyntitilaukselle
  require "tilauksesta_myyntitilaus.inc";

  $tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($kukarow["kesken"], '', '', '', '', '', $perusta_tilaustyyppi);
  if ($tilauksesta_myyntitilaus != '') echo "$tilauksesta_myyntitilaus<br><br>";

  $query = "UPDATE lasku SET alatila='B' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
  $result = pupe_query($query);

  //  Päivitetään myös muut tunnusnipun jäsenet sympatian vuoksi hyväksytyiksi
  $query = "SELECT tunnusnippu from lasku where yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = $laskurow[tunnusnippu]";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $row = mysql_fetch_assoc($result);

    $query = "UPDATE lasku SET alatila='T' where yhtio='$kukarow[yhtio]' and tunnusnippu = $row[tunnusnippu] and tunnus!='$kukarow[kesken]'";
    $result = pupe_query($query);
  }

  $query  = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
  $result = pupe_query($query);

  $aika=date("d.m.y @ G:i:s", time());
  echo "<font class='message'>$otsikko $kukarow[kesken] ".t("valmis")."!</font><br><br>";

  $tee        = '';
  $tilausnumero    = '';
  $laskurow      = '';
  $kukarow['kesken']  = '';
}

// Hylätään tarjous
if ($kukarow["extranet"] == "" and $tee == "HYLKAATARJOUS" and $muokkauslukko == "") {

  ///* Reload ja back-nappulatsekki *///
  if ((int) $kukarow["kesken"] == 0) {
    echo "<font class='error'> ".t("Taisit painaa takaisin tai päivitä nappia. Näin ei saa tehdä")."! </font>";
    exit;
  }

  if (isset($crm_tarjouspois)) {
    $hylkays_kommentti = " Tarjouksen hylkäyksen syy: $crm_tarjouspois";

    $query = "UPDATE lasku SET comments = CONCAT(comments, ' {$hylkays_kommentti}') where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
    $result = pupe_query($query);

    // Tehdään tarjouksen poistosta kommentti asiakasmemoon
    kalenteritapahtuma("Memo", "Tarjous asiakkaalle", "Tarjouksen hylkäyksen syy: {$crm_tarjouspois}", $laskurow["liitostunnus"], "", "", $laskurow["tunnus"]);
  }

  $query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
  $result = pupe_query($query);

  $query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
  $result = pupe_query($query);

  //Nollataan sarjanumerolinkit
  vapauta_sarjanumerot($toim, $kukarow["kesken"]);

  //  Päivitetään myös muut tunnusnipun jäsenet sympatian vuoksi hylätyiksi *** tämän voisi varmaan tehdä myös kaikki kerralla? ***
  $query = "SELECT tunnus from lasku where yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = $laskurow[tunnusnippu] and tunnus != '$kukarow[kesken]'";
  $abures = pupe_query($query);

  if (mysql_num_rows($abures) > 0) {
    while ($row = mysql_fetch_assoc($abures)) {
      $query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus=$row[tunnus]";
      $result = pupe_query($query);

      $query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus=$row[tunnus]";
      $result = pupe_query($query);

      //Nollataan sarjanumerolinkit
      vapauta_sarjanumerot($toim, $row["tunnus"]);
    }
  }

  $query  = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
  $result = pupe_query($query);

  $aika=date("d.m.y @ G:i:s", time());
  echo "<font class='message'>$otsikko $kukarow[kesken] ".t("valmis")."!</font><br><br>";

  $tee        = '';
  $tilausnumero    = '';
  $laskurow      = '';
  $kukarow['kesken']  = '';
}

// Laskutetaan myyntitili
if ($kukarow["extranet"] == "" and $tee == "LASKUTAMYYNTITILI") {
  $tilatapa = "LASKUTA";
  require "laskuta_myyntitilirivi.inc";
}

// Palautetaan myyntitili takaisin omaan varastoon
if ($kukarow["extranet"] == "" and $tee == "PALAUTAMYYNTITILI") {
  $tilatapa = "PALAUTA";
  require "laskuta_myyntitilirivi.inc";
}

// Laitetaan myyntitili takaisin lepäämään
if ($kukarow["extranet"] == "" and $tee == "LEPAAMYYNTITILI") {
  $tilatapa = "LEPAA";
  require "laskuta_myyntitilirivi.inc";
}

if ($tee == "MAKSUSOPIMUS") {
  require "maksusopimus.inc";
}

if ($tee == "LISAAKULUT") {
  require "lisaa_kulut.inc";
}

if ($kukarow["extranet"] == "" and $yhtiorow["myytitilauksen_kululaskut"] == "K" and $tee == "kululaskut") {
  echo "<font class='head'>".t("Kululaskut")."</font><hr>";
  require 'kululaskut.inc';
}

if (in_array($jarjesta, array("moveUp", "moveDown")) and $rivitunnus > 0) {

  if ($laskurow["tunnusnippu"] > 0 and ($toim != "TARJOUS" and $toim != "EXTTARJOUS")) {
    $query = "SELECT GROUP_CONCAT(tunnus) tunnukset
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tunnusnippu]' and tila IN ('L','G','E','V','W','N','R','A') and tunnusnippu>0";
    $result = pupe_query($query);
    $toimrow = mysql_fetch_assoc($result);

    $tunnarit = "$toimrow[tunnukset]";
  }
  else {
    $tunnarit = $kukarow["kesken"];
  }

  $query = "SELECT jarjestys, tunnus
            FROM tilausrivin_lisatiedot
            WHERE yhtio          = '$kukarow[yhtio]'
            and tilausrivitunnus = '$rivitunnus'";
  $abures = pupe_query($query);
  $aburow = mysql_fetch_assoc($abures);

  if (($jarjesta == "moveUp" and $yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") or ($jarjesta == "moveDown" and $yhtiorow["tilauksen_jarjestys_suunta"] == "DESC")) {
    $ehto = "and jarjestys<$aburow[jarjestys]";
    $j = "desc";
  }
  elseif (($jarjesta == "moveDown" and $yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") or ($jarjesta == "moveUp" and $yhtiorow["tilauksen_jarjestys_suunta"] == "DESC")) {
    $ehto = "and jarjestys>$aburow[jarjestys]";
    $j = "asc";
  }

  $query = "SELECT jarjestys, tilausrivin_lisatiedot.tunnus
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus $ehto
             WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
            and tilausrivi.tyyppi   != 'D'
            and tilausrivi.otunnus   IN ($tunnarit)
            and (tilausrivi.perheid=0 or tilausrivi.perheid=tilausrivi.tunnus)
            ORDER BY jarjestys $j
            LIMIT 1";
  $result = pupe_query($query);
  $kohderow = mysql_fetch_assoc($result);

  if ($kohderow["jarjestys"]>0 and $kohderow["tunnus"] != $rivitunnus) {
    //  Kaikki OK vaihdetaan data päikseen
    $query = "UPDATE tilausrivin_lisatiedot SET jarjestys = '$kohderow[jarjestys]' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$aburow[tunnus]'";
    $updres=pupe_query($query);

    $query = "UPDATE tilausrivin_lisatiedot SET jarjestys = '$aburow[jarjestys]' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$kohderow[tunnus]'";
    $updres=pupe_query($query);
  }
  else {
    echo "<font class='error'>".t("VIRHE: riviä ei voi siirtää!")."</font><br>";
  }

  $tyhjenna   = "JOO";
}

if ($sahkoinen_lahete and $kukarow["extranet"] == "" and (int) $kukarow['kesken'] > 0 and !empty($laskurow)) {

  // Tarkenne kenttä merkkaa sitä että voidaan käyttää tätä ominaisuutta reklamaation puolella
  $query = "SELECT asiakkaan_avainsanat.*
            FROM asiakkaan_avainsanat
            WHERE asiakkaan_avainsanat.yhtio       = '{$kukarow['yhtio']}'
            and asiakkaan_avainsanat.laji          = 'futur_sahkoinen_lahete'
            and asiakkaan_avainsanat.avainsana    != ''
            and asiakkaan_avainsanat.tarkenne     != ''
            AND asiakkaan_avainsanat.liitostunnus  = '{$laskurow['liitostunnus']}'";
  $as_avain_chk_res = pupe_query($query);

  if (mysql_num_rows($as_avain_chk_res) > 0) {
    array_push($sahkoinen_lahete_toim, 'REKLAMAATIO');
  }
}

// Poistetaan tilaus
if ($tee == 'POISTA' and $muokkauslukko == "" and $kukarow["mitatoi_tilauksia"] == "" and (int) $kukarow['kesken'] > 0) {

  // tilausta mitätöidessä laitetaan kaikki poimitut jt-rivit takaisin omille tilauksille
  $query = "SELECT tilausrivi.tunnus, tilausrivin_lisatiedot.vanha_otunnus
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.positio = 'JT')
            WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
            AND tilausrivi.tyyppi  != 'D'
            AND tilausrivi.otunnus  = '{$kukarow['kesken']}'";
  $jt_rivien_muisti_res = pupe_query($query);

  if (mysql_num_rows($jt_rivien_muisti_res) > 0) {
    $jt_saldo_lisa = $yhtiorow["varaako_jt_saldoa"] == "" ? ", jt = varattu, varattu = 0 " : '';

    while ($jt_rivien_muisti_row = mysql_fetch_assoc($jt_rivien_muisti_res)) {
      $query = "UPDATE tilausrivi SET
                otunnus     = '{$jt_rivien_muisti_row['vanha_otunnus']}',
                var         = 'J'
                $jt_saldo_lisa
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$jt_rivien_muisti_row['tunnus']}'";
      $jt_rivi_res = pupe_query($query);

      echo "<font class='message'>", t("Jälkitoimitus palautettiin tilaukselle"), " $jt_rivien_muisti_row[vanha_otunnus], ", t("ota yhteys asiakaspalveluun"), ".</font><br><br>";
    }
  }

  // valmistusriveille var tyhjäksi, että osataan mitätöidä ne seuraavassa updatessa
  // Valmistusten valmisteriveiltä pitää osata poistaa myös sarjanumerot
  if ($toim == 'VALMISTAVARASTOON' or $toim == 'VALMISTAASIAKKAALLE') {
    $query = "UPDATE tilausrivi SET var='' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and var='P'";
    $result = pupe_query($query);

    // Poistetaan valmistuksen poistamisen yhteydessä myös valmisteiden sarjanumerot
    vapauta_sarjanumerot("", $kukarow["kesken"]);
  }

  // poistetaan tilausrivit, mutta jätetään PUUTE rivit analyysejä varten...
  $query = "UPDATE tilausrivi
            SET tilausrivi.tyyppi = 'D', tilausrivi.laadittu = now(), tilausrivi.laatija = '$kukarow[kuka]'
            where tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.otunnus = '$kukarow[kesken]'
            and tilausrivi.var <> 'P'";
  $result = pupe_query($query);

  if ($sahkoinen_lahete) {

    $query = "SELECT yhtio_toimipaikka
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$kukarow['kesken']}'";
    $chk_toimipaikka_res = pupe_query($query);
    $chk_toimipaikka_row = mysql_fetch_assoc($chk_toimipaikka_res);

    if ($chk_toimipaikka_row['yhtio_toimipaikka'] != 0) {

      $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio'], $chk_toimipaikka_row['yhtio_toimipaikka']);

      if (mysql_num_rows($toimipaikat_res) != 0) {

        $toimipaikat_row = mysql_fetch_assoc($toimipaikat_res);

        if ($kukarow["extranet"] == "" and in_array($toim, $sahkoinen_lahete_toim) and $toimipaikat_row['liiketunnus'] != '') {

          require_once "inc/sahkoinen_lahete.class.inc";

          sahkoinen_lahete($laskurow);
        }
      }
    }
  }

  //Nollataan sarjanumerolinkit ja dellataan ostorivit
  vapauta_sarjanumerot($toim, $kukarow["kesken"]);

  //Poistetaan maksusuunnitelma
  $query = "DELETE from maksupositio WHERE yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]' and uusiotunnus=0";
  $result = pupe_query($query);

  // Poistetaan maksupositio pointteri
  $query = "UPDATE maksupositio set uusiotunnus = 0 where yhtio = '$kukarow[yhtio]' and uusiotunnus = '$kukarow[kesken]'";
  $result = pupe_query($query);

  //Poistetaan rahtikrijat
  $query = "DELETE from rahtikirjat WHERE yhtio='$kukarow[yhtio]' and otsikkonro='$kukarow[kesken]'";
  $result = pupe_query($query);

  $query = "UPDATE lasku SET alatila = tila, tila = 'D', comments = '$kukarow[nimi] ($kukarow[kuka]) ".t("mitätöi tilauksen ohjelmassa tilaus_myynti.php")." ".date("d.m.y @ G:i:s")."' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
  $result = pupe_query($query);

  $query  = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
  $result = pupe_query($query);

  //Poistetaan asennuskalenterimerkinnät
  $query = "  UPDATE kalenteri SET tyyppi = 'DELETEDasennuskalenteri' WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'asennuskalenteri' and liitostunnus = '$kukarow[kesken]'";
  $result = pupe_query($query);

  if ($kukarow["extranet"] == "" and $laskurow["tunnusnippu"] > 0 and (($toim != "TARJOUS" and $toim != "EXTTARJOUS") or (($toim == "TARJOUS" or $toim == "EXTTARJOUS") and $laskurow["tunnusnippu"] != $laskurow["tunnus"])) and $toim != "PROJEKTI") {

    $aika = date("d.m.y @ G:i:s", time());

    if ($projektilla > 0 and ($laskurow["tunnusnippu"] > 0 and $laskurow["tunnusnippu"] != $laskurow["tunnus"])) {

      echo "<font class='message'>".t("Osatoimitus")." ($aika) $kukarow[kesken] ".t("mitätöity")."!</font><br><br>";

      $tilausnumero = $laskurow["tunnusnippu"];

      //  Hypätään takaisin otsikolle
      echo "<font class='info'>".t("Palataan projektille odota hetki..")."</font><br>";

      if ($projektilla > 0) {
        echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$tilausnumero'>";
      }
      else {
        echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=$toim&valitsetoimitus=$tilausnumero'>";
      }
      exit;
    }
    elseif (($toim == "TARJOUS" or $toim == "EXTTARJOUS") and $laskurow["tunnusnippu"] > 0) {

      echo "<font class='message'>".t("Tarjous")." ($aika) $kukarow[kesken] ".t("mitätöity")."!</font><br><br>";

      $tilausnumero = $laskurow["tunnusnippu"];

      //  Hypätään takaisin otsikolle
      echo "<font class='info'>".t("Palataan tarjoukselle odota hetki..")."</font><br>";

      $query = "SELECT tunnus
                FROM lasku
                WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tunnusnippu]' and tila = 'T' and alatila != 'X'";
      $result = pupe_query($query);
      $row = mysql_fetch_assoc($result);

      echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$row[tunnus]'>";

      exit;
    }
    else {
      $tee        = '';
      $tilausnumero    = '';
      $laskurow      = '';
      $kukarow['kesken']  = '';
    }
  }
  else {

    if ($kukarow["extranet"] == "") {
      echo "<font class='message'>".t("$otsikko")." $kukarow[kesken] ".t("mitätöity")."!</font><br><br>";
    }

    $tee        = '';
    $tilausnumero    = '';
    $laskurow      = '';
    $kukarow['kesken']  = '';

    if ($kukarow["extranet"] != "") {
      echo "<font class='head'>$otsikko</font><hr><br><br>";
      echo "<font class='message'>".t("Tilauksesi poistettiin")."!</font><br><br>";

      $tee = "SKIPPAAKAIKKI";
    }
  }

  if ($kukarow["extranet"] == "" and $lopetus != '') {
    lopetus($lopetus, "META");
  }
}

//Tyhjenntään syöttökentät
if (isset($tyhjenna)) {

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    ${'ale'.$alepostfix} = "";
    ${'kayttajan_ale'.$alepostfix} = "";
    ${'ale_array'.$alepostfix} = "";
  }

  $alv         = "";
  $alv_array       = "";
  $hinta         = "";
  $hinta_array     = "";
  $kayttajan_alv     = "";
  $kayttajan_hinta   = "";
  $kayttajan_kpl     = "";
  $kayttajan_netto   = "";
  $kayttajan_var     = "";
  $kerayspvm       = "";
  $kommentti       = "";
  $kpl         = "";
  $kpl_array       = "";
  $netto         = "";
  $netto_array     = "";
  $paikat       = "";
  $paikka       = "";
  $paikka_array     = "";
  $perheid       = 0;
  $perheid2       = 0;
  $rivinumero     = "";
  $rivitunnus     = 0;
  $toimaika       = "";
  $tuotenimitys     = "";
  $tuoteno       = "";
  $var         = "";
  $variaatio_tuoteno   = "";
  $var_array       = "";
  $sopimuksen_lisatieto1 = "";
  $sopimuksen_lisatieto2 = "";
  $omalle_tilaukselle = "";
  $valmistuslinja     = "";
  $rekisterinumero    = "";
  $keratty = "";
  $kerattyaika = 0;
  $toimitettu = '';
  $toimitettuaika = 0;
}

if (!empty($valitse_tuotteetasiakashinnastoon)) {
  echo "<font class='head'>{$otsikko}</font><hr><br>";
  echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
      <input type='hidden' name='tee' value='tuotteetasiakashinnastoon'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='mista' value='$mista'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
      <input type='hidden' name='projektilla' value='$projektilla'>
      <input type='hidden' name='orig_tila' value='$orig_tila'>
      <input type='hidden' name='orig_alatila' value='$orig_alatila'>";

  echo "<table>";

  $_asiakashinta_kayttooikeus  = tarkista_oikeus("yllapito.php", "asiakashinta", "x");
  $_asikasalennus_kayttooikeus = tarkista_oikeus("yllapito.php", "asiakasalennus", "x") ;
  $_checked_h = $_asiakashinta_kayttooikeus ? 'checked' : '';
  $_checked_1 = empty($_checked_h) ? 'checked' : '';

  echo "<tr>";
  echo "<th>".t("Tallenna")."</th>";
  echo "<td>";

  if ($_asiakashinta_kayttooikeus) {
    echo "<label>";
    echo "<input type='checkbox' name='lisaa_asiakashinta' {$_checked_h}>";
    echo t("Lisää tilauksen hinnat asiakashintoihin");
    echo "</label>";
    echo "<br>";
  }

  if ($_asikasalennus_kayttooikeus) {
    if ($yhtiorow['myynnin_alekentat'] == 1) {
      echo "<label>";
      echo "<input type='checkbox' name='lisaa_asiakasalennus_1' {$_checked_1}>";
      echo t("Lisää tilauksen alennusprosentit asiakasalennuksiin");
      echo "</label>";
      echo "<br>";
    }
    else {
      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        echo "<label>";
        echo "<input type='checkbox' name='lisaa_asiakasalennus_{$alepostfix}' {${"_checked_".$alepostfix}}>";
        echo t("Lisää tilauksen Ale %s asiakasalennuksiin", "", $alepostfix);
        echo "</label>";
        echo "<br>";
      }
    }
  }

  echo "</td></tr>";

  if ($yhtiorow["myynti_asiakhin_tallenna"] == "V") {
    $mahdolliset_liitokset = array(
      "liitostunnus" => "Asiakkaalle",
      "ytunnus"      => "Y-tunnukselle"
    );

    if (!empty($asiakasrow["ryhma"])) {
      $mahdolliset_liitokset["asiakasryhma"] = "Asiakasryhmälle";
    }

    if (!empty($asiakasrow["piiri"])) {
      $mahdolliset_liitokset["piiri"] = "Piirille";
    }

    echo "<tr>";
    echo "<th>".t("Lisää alennus")."</th>";
    echo "<td><select id='asiakas_hinta_liitos' name='asiakas_hinta_liitos'>";

    foreach ($mahdolliset_liitokset as $liitos => $teksti) {
      echo "<option value='{$liitos}'>" . t($teksti) . "</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }

  echo "<tr><th>";
  echo t("Loppupäivämäärä")."</th><td>";
  echo t("PV"), " <input type='text' name='asiakas_hinta_loppupv' value='' size='3'> ";
  echo t("KK"), " <input type='text' name='asiakas_hinta_loppukk' value='' size='3'> ";
  echo t("VVVV"), " <input type='text' name='asiakas_hinta_loppuvv' value='' size='5'>";

  echo "</td><td class='back'>".t("Jätä loppupäivämäärä tyhjäksi jos haluat, että hinnat ovat voimassa toistaiseksi").".</td></tr>";
  echo "</table>";

  echo "<br>";
  echo "<input type='submit' value='".t("Siirrä tuotteet asiakashinnoiksi")."'>";

  echo "</form>";

  include "inc/footer.inc";
  exit;
}

if ($tee == "VALMIS"
  and in_array($toim, array("RIVISYOTTO", "PIKATILAUS", "TYOMAARAYS", "VALMISTAASIAKKAALLE"))
  and $kateinen != ''
  and $kukarow['extranet'] == ''
  and (
    $kukarow["kassamyyja"] != ''
    or (
      (
        $kukarow["dynaaminen_kassamyynti"] != ""
        or $yhtiorow["dynaaminen_kassamyynti"] != ""
      )
      and $kertakassa != ''
    )
  )
) {

  if (($kukarow["dynaaminen_kassamyynti"] != "" or $yhtiorow["dynaaminen_kassamyynti"] != "") and isset($kertakassa) and $kertakassa == 'EI_KASSAMYYNTIA') {
    $kassamyyja_kesken   = "";
    $kateisohitus    = "X";
  }
  elseif (!isset($kassamyyja_kesken) and !isset($seka)) {

    $query_maksuehto = "SELECT *
                        FROM maksuehto
                         WHERE yhtio  = '$kukarow[yhtio]'
                        and kateinen != ''
                        and kaytossa  = ''
                        and (sallitut_maat = '' or sallitut_maat like '%$laskurow[maa]%')";
    $maksuehtores = pupe_query($query_maksuehto);

    if (mysql_num_rows($maksuehtores) > 1) {
      echo "<font class='head'>$otsikko</font><hr><br>";
      echo "<table><tr><th>" . t("Maksutapa") . ":</th>";

      while ($maksuehtorow = mysql_fetch_assoc($maksuehtores)) {
        echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>";
        echo "<input type='hidden' name='poikkeava_kpvm' value='' class='poikkeava_kpvm'>";
        echo "<input type='hidden' name='kassamyyja_kesken' value='ei'>";
        echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
        echo "<input type='hidden' name='mista' value='$mista'>";
        echo "<input type='hidden' name='tee' value='VALMIS'>";
        echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";
        echo "<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";
        echo "<input type='hidden' name='kateinen' value='$kateinen'>";
        echo "<input type='hidden' name='valittu_kopio_tulostin' value='$valittu_kopio_tulostin'>";
        echo "<input type='hidden' name='kertakassa' value='$kertakassa'>";
        echo "<input type='hidden' name='toim' value='$toim'>";
        echo "<input type='hidden' name='orig_tila' value='$orig_tila'>";
        echo "<input type='hidden' name='orig_alatila' value='$orig_alatila'>";
        echo "<td><input type='submit' value='" .
          t_tunnus_avainsanat($maksuehtorow, "teksti", "MAKSUEHTOKV") . "'></td>";
        echo "</form>";
      }

      echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>";
      echo "<input type='hidden' name='poikkeava_kpvm' value='' class='poikkeava_kpvm'>";
      echo "<input type='hidden' name='kassamyyja_kesken' value='ei'>";
      echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
      echo "<input type='hidden' name='mista' value='$mista'>";
      echo "<input type='hidden' name='tee' value='VALMIS'>";
      echo "<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";
      echo "<input type='hidden' name='seka' value='X'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='kateinen' value='$kateinen'>";
      echo "<input type='hidden' name='kertakassa' value='$kertakassa'>";
      echo "<input type='hidden' name='orig_tila' value='$orig_tila'>";
      echo "<input type='hidden' name='orig_alatila' value='$orig_alatila'>";
      echo "<td><input type='submit' value='".t("Useita maksutapoja")."'></td>";
      echo "</form>";

      echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>";
      echo "<input type='hidden' name='poikkeava_kpvm' value='' class='poikkeava_kpvm'>";
      echo "<input type='hidden' name='kassamyyja_kesken' value='ei'>";
      echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
      echo "<input type='hidden' name='mista' value='$mista'>";
      echo "<input type='hidden' name='tee' value='VALMIS'>";
      echo "<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='kateinen' value='$kateinen'>";
      echo "<input type='hidden' name='kateisohitus' value='X'>";
      echo "<input type='hidden' name='kertakassa' value='$kertakassa'>";
      echo "<input type='hidden' name='orig_tila' value='$orig_tila'>";
      echo "<input type='hidden' name='orig_alatila' value='$orig_alatila'>";
      echo "<td><input type='submit' value='".t("Ei vielä laskuteta, siirrä tilaus keräykseen")."'></td>";
      echo "</form></tr>";

      if (!empty($yhtiorow['kateiskuitin_paivays'])) {
        echo "<tr><th>".t("Poikkeava päivämäärä").":</th><td colspan='2'>";
        echo "<input type='text' size='3' name='poikkeava_kpvmpp' id='poikkeava_kpvmpp' class='poikkeava_kpvm_syotto'> - ";
        echo "<input type='text' size='3' name='poikkeava_kpvmkk' id='poikkeava_kpvmkk' class='poikkeava_kpvm_syotto'> - ";
        echo "<input type='text' size='5' name='poikkeava_kpvmvv' id='poikkeava_kpvmvv' class='poikkeava_kpvm_syotto'> ";
        echo "(".t("pp-kk-vvvv").")</td></tr>";
      }

      echo "</table>";

      echo "<script type='text/javascript'>
          $(document).ready(function() {
           $('input.poikkeava_kpvm_syotto').on('input',function(e){
            $('input.poikkeava_kpvm').each(function(i) {
              dateval = $('#poikkeava_kpvmvv').attr('value')+'-'+$('#poikkeava_kpvmkk').attr('value')+'-'+$('#poikkeava_kpvmpp').attr('value');
              $(this).attr('value', dateval);
            });
           });
         });
          </script>";

      if (@include "inc/footer.inc");
      elseif (@include "footer.inc");
      exit;
    }
    else {
      // Mennään laskun maksuehdolla, jos yhtiöllä ei ole useampia käteismaksuehtoja
      $kassamyyja_kesken   = "ei";
      $maksutapa       = $laskurow["maksuehto"];
      $kateisohitus    = "";
    }
  }
  elseif ($kassamyyja_kesken == 'ei' and $seka == 'X') {
    $query_maksuehto = "SELECT *
                        FROM maksuehto
                        WHERE yhtio = '$kukarow[yhtio]'
                         AND kateinen != ''
                         AND kaytossa  = ''
                         AND (maksuehto.sallitut_maat = '' or maksuehto.sallitut_maat like '%$laskurow[maa]%')";
    $maksuehtores = pupe_query($query_maksuehto);

    $maksuehtorow = mysql_fetch_assoc($maksuehtores);

    echo "<font class='head'>$otsikko</font><hr><br>";
    echo "<form name='laskuri' id='laskuri' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'><table class='laskuri'>";

    echo "<input type='hidden' name='kassamyyja_kesken' value='ei'>";
    echo "<input type='hidden' name='poikkeava_kpvm' value='$poikkeava_kpvm'>";
    echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
    echo "<input type='hidden' name='mista' value='$mista'>";
    echo "<input type='hidden' name='tee' value='VALMIS'>";
    echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";
    echo "<input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>";
    echo "<input type='hidden' name='valittu_kopio_tulostin' value='$valittu_kopio_tulostin'>";
    echo "<input type='hidden' name='kateinen' value='$kateinen'>";
    echo "<input type='hidden' name='kertakassa' value='$kertakassa'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='seka' id='seka' value='X'>";
    echo "<input type='hidden' name='orig_tila' value='$orig_tila'>";
    echo "<input type='hidden' name='orig_alatila' value='$orig_alatila'>";
    echo "<input type='hidden' name='maksupaatetapahtuma' id='maksupaatetapahtuma' value=''>";
    echo "<input type='hidden' id='peruutus' name='peruutus' value>";

    echo "  <script type='text/javascript' language='JavaScript'>
      <!--
          function update_summa(kaikkiyhteensa) {

            kateinen = Number(document.getElementById('kateismaksu').value.replace(\",\",\".\"));
            pankki = Number(document.getElementById('pankkikortti').value.replace(\",\",\".\"));
            luotto = Number(document.getElementById('luottokortti').value.replace(\",\",\".\"));

            summa = kaikkiyhteensa - (kateinen + pankki + luotto);";


    if ($yhtiorow['sallitaanko_kateismyynti_laskulle'] != '') {
      echo "laskulle = Number(document.getElementById('laskulle').value.replace(\",\",\".\"));
            summa = summa - laskulle;";
    }

    echo "  summa = Math.round(summa*100)/100;

            if (summa == 0 && (document.getElementById('kateismaksu').value != '' ||
                document.getElementById('pankkikortti').value != '' ||
                document.getElementById('luottokortti').value != '' ||
                document.getElementById('laskulle').value != '')) {

              summa = 0.00;
              document.getElementById('hyvaksy_nappi').disabled = false;
              if(document.getElementById('korttimaksunappi')){
                  document.getElementById('korttimaksunappi').disabled = true;
              }

              document.getElementById('seka').value = 'kylla';
            } else {
              document.getElementById('hyvaksy_nappi').disabled = true;
              if(document.getElementById('korttimaksunappi')){
                  document.getElementById('korttimaksunappi').disabled = false;
              }";

    echo "  }
            document.getElementById('loppusumma').innerHTML = '<b>' + summa.toFixed(2) + '</b>';
          }
        -->
        </script>";

    $styyli = '';
    echo "<tr><th>".t("Laskun loppusumma")."</th><td align='right'>$kaikkiyhteensa</td><td>$laskurow[valkoodi]</td></tr>";

    echo "<tr><td>".t("Käteisellä")."</td><td><input type='text' name='kateismaksu[kateinen]' id='kateismaksu' value='{$kateismaksu['kateinen']}' size='7' autocomplete='off' onkeyup='update_summa(\"$kaikkiyhteensa\");'></td><td>$laskurow[valkoodi]</td></tr>";

    echo "<tr $styyli><td>".t("Pankkikortilla")."</td><td><input type='text' name='kateismaksu[pankkikortti]' id='pankkikortti' value='{$kateismaksu['pankkikortti']}' size='7' autocomplete='off' onkeyup='update_summa(\"$kaikkiyhteensa\");'></td><td>$laskurow[valkoodi]</td></tr>";
    echo "<tr $styyli><td>".t("Luottokortilla")."</td><td><input type='text' name='kateismaksu[luottokortti]' id='luottokortti' value='{$kateismaksu['luottokortti']}' size='7' autocomplete='off' onkeyup='update_summa(\"$kaikkiyhteensa\");'></td><td>$laskurow[valkoodi]</td></tr>";

    if ($yhtiorow['sallitaanko_kateismyynti_laskulle'] != '') {
      echo "<tr $styyli><td>".t("Laskulle")."</td><td><input type='text' name='kateismaksu[laskulle]' id='laskulle' value='{$kateismaksu['laskulle']}' size='7' autocomplete='off' onkeyup='update_summa(\"$kaikkiyhteensa\");'></td><td>$laskurow[valkoodi]</td></tr>";
    }

    $disabloi_hyvaksy = 'disabled';
    $totaalisumma = 0.00;

    echo "<tr><th>".t("Erotus")."</th><td name='loppusumma' id='loppusumma' align='right'><strong>$totaalisumma</strong></td><td>$laskurow[valkoodi]</td></tr>";
    echo "<tr><td class='back'><input type='submit' name='hyvaksy_nappi' id='hyvaksy_nappi' value='".t("Hyväksy")."' $disabloi_hyvaksy></td></tr>";

    echo "</table></form><br><br>";

    $formi  = "laskuri";
    $kentta = "kateismaksu";

    exit;
  }
}

if ($tee == 'PALAUTA_SIIVOTUT' and $kukarow['extranet'] != '') {
  $query = "SELECT tilausrivi.tuoteno, tilausrivi.tilkpl, tilausrivi.kommentti, tilausrivi.tunnus
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.positio = 'Ei varaa saldoa')
            WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus  = '$tilausnumero'
            AND tilausrivi.tyyppi  != 'D'";
  $palauta_siivotut_res = pupe_query($query);

  while ($palauta_siivotut_row = mysql_fetch_assoc($palauta_siivotut_res)) {
    $tuoteno_array[] = $palauta_siivotut_row['tuoteno'];
    $kpl_array[$palauta_siivotut_row['tuoteno']] = $palauta_siivotut_row['tilkpl'];
    $kommentti_array[$palauta_siivotut_row['tuoteno']] = $palauta_siivotut_row['kommentti'];

    $query = "UPDATE tilausrivi SET
              tyyppi      = 'D'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$palauta_siivotut_row['tunnus']}'";
    $palauta_res = pupe_query($query);
  }

  $tee = '';
}

if ($tee == 'VALMIS' and $kukarow['extranet'] != '') {
  $query = "SELECT tilausrivi.varattu
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.positio = 'Ei varaa saldoa')
            WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus  = '$tilausnumero'
            AND tilausrivi.tyyppi  != 'D'";
  $varattu_check_res = pupe_query($query);
  $varattu_nollana = false;

  while ($varattu_check_row = mysql_fetch_assoc($varattu_check_res)) {
    if ($varattu_check_row['varattu'] == 0) $varattu_nollana = true;
  }

  if ($varattu_nollana) $tee = '';
}

if (    $tee == "VALMIS"
  and (isset($kassamyyja_kesken) and $kassamyyja_kesken == 'ei')
  and ($kukarow["kassamyyja"] != '' or $kukarow["dynaaminen_kassamyynti"] != "" or $yhtiorow["dynaaminen_kassamyynti"] != "")
  and $kukarow['extranet'] == ''
  and $kateisohitus == ""
) {

  if ($kertakassa == '') {
    $kertakassa = $kukarow["kassamyyja"];
  }

  $query_maksuehto = "UPDATE lasku
                      SET maksuehto   = '$maksutapa',
                      kassalipas  = '$kertakassa'
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND tunnus  = '$kukarow[kesken]'";
  $maksuehtores = pupe_query($query_maksuehto);
}

// Tilaus valmis
if ($tee == "VALMIS" and ($muokkauslukko == "" or $toim == "PROJEKTI")) {

  ///* Reload ja back-nappulatsekki *///
  if ((int) $kukarow["kesken"] == 0) {
    echo "<font class='error'> ".t("Taisit painaa takaisin tai päivitä nappia. Näin ei saa tehdä")."! </font><br>";
    exit;
  }

  // Tsekataan jos ollaan tehty asiakkaallevalmistus jossa ei ole yhtään valmistettavaa riviä
  $msiirto = "";

  if ($toim == "VALMISTAASIAKKAALLE") {
    $query = "SELECT yhtio
              FROM tilausrivi
              WHERE yhtio = '$kukarow[yhtio]'
              AND otunnus = '$kukarow[kesken]'
              AND tyyppi  in ('W','M','V')
              AND varattu > 0";
    $sres  = pupe_query($query);

    if (mysql_num_rows($sres) == 0) {
      echo "<font class='message'> ".t("Ei valmistettavaa. Valmistus siirrettiin myyntipuolelle")."! </font><br>";

      if ($laskurow["alatila"] == "") {
        $utila = "N";
        $atila = "";
      }
      elseif ($laskurow["alatila"] == "J") {
        $utila = "N";
        $atila = "A";
      }
      else {
        $utila = "L";
        $atila = $laskurow["alatila"];
      }

      $query  = "UPDATE lasku set
                 tila        = '$utila',
                 alatila     = '$atila'
                 where yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$kukarow[kesken]'
                 and tila    = 'V'";
      $result = pupe_query($query);

      $msiirto = "MYYNTI";
    }
  }

  if ($kukarow["extranet"] == "" and $toim == "TARJOUS") {
    // Tulostetaan tarjous
    if (count($komento) == 0) {
      echo "<font class='head'>".t("Tarjous").":</font><hr><br>";

      $otunnus = $tilausnumero;
      $tulostimet[0] = "Tarjous";
      require "inc/valitse_tulostin.inc";
    }

    if (isset($nayta_pdf)) $tee = "NAYTATILAUS";

    require_once 'tulosta_tarjous.inc';

    tulosta_tarjous($otunnus, $komento["Tarjous"], $kieli, $tee, '', $verolliset_verottomat_hinnat,
      $naytetaanko_rivihinta, $naytetaanko_tuoteno, $liita_tuotetiedot, $naytetaanko_yhteissummarivi);

    $query = "UPDATE lasku SET alatila='A' where yhtio='$kukarow[yhtio]' and alatila='' and tunnus='$kukarow[kesken]'";
    $result = pupe_query($query);

    // Meillä voi olla versio..
    if ($laskurow["tunnusnippu"] > 0) {
      $result = pupe_query($query);

      $query  = "SELECT tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnusnippu]' and tunnus <= '$laskurow[tunnus]' and tila='T'";
      $result = pupe_query($query);

      $tarjous = $laskurow["tunnusnippu"]."/".mysql_num_rows($result);
    }
    else {
      $tarjous = $laskurow["tunnus"];
    }

    if (tarkista_oikeus("crm/kuittaamattomat.php")) {
      $mkk = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));
      $mhh = " 10:00:00";

      kalenteritapahtuma("Muistutus", "Tarjous asiakkaalle", "Muista tarjous $tarjous\n\n$laskurow[viesti]\n$laskurow[comments]\n$laskurow[sisviesti2]", $laskurow["liitostunnus"], "K", "", $laskurow["tunnus"], "'".$mkk.$mhh."'");
    }

    // Tilaus ei enää kesken...
    $query  = "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
    $result = pupe_query($query);

  }
  elseif ($toim == "EXTTARJOUS" or $toim == "EXTENNAKKO") {
    if (($laskurow['tila'] == 'T' or $laskurow['tila'] == 'N') and $laskurow['alatila'] == '' and $laskurow['liitostunnus'] < 0) {
      require 'inc/tarjouksen_splittaus.inc';
    }
  }
  elseif ($kukarow["extranet"] == "" and $toim == "SIIRTOTYOMAARAYS") {
    // Sisäinen työmääräys valmis
    require "tyomaarays/tyomaarays.inc";
  }
  elseif ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO")) {
    if ($kukarow["extranet"] == "" and $yhtiorow["tee_siirtolista_myyntitilaukselta"] == 'K' and $laskurow['tila'] == 'C' and $laskurow['alatila'] == '' and empty($tulosta)) {
      require 'tilauksesta_varastosiirto.inc';

      tilauksesta_varastosiirto($laskurow['tunnus'], 'P');
    }
    // Työmääräys valmis
    require "tyomaarays/tyomaarays.inc";
  }
  elseif ($kukarow["extranet"] == "" and ($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA" or $toim == "MYYNTITILI") and $msiirto == "") {
    if (($toim == "VALMISTAASIAKKAALLE" or $toim == "VALMISTAVARASTOON") and $yhtiorow['valmistuksien_kasittely'] == 'Y') {
      $valmistus_tunnukset = splittaa_valmistukset($kukarow["kesken"]);

      // Jos valmistuksien_kasittely == Valmistuksella voi olla vain yksi valmiste,
      // niin loopataan valmistusrivit läpi ja luodaan jokaiselle riville oma otsikko
      foreach ($valmistus_tunnukset as $valmistus_tunnus) {
        $laskurow = hae_lasku($valmistus_tunnus);
        require "tilaus-valmis-siirtolista.inc";
      }
    }
    else {
      // Siirtolista, myyntitili, valmistus valmis
      require "tilaus-valmis-siirtolista.inc";
    }
  }
  elseif ($toim == "PROJEKTI") {
    // Projekti, tällä ei ole mitään rivejä joten nollataan vaan muuttujat
    $tee        = '';
    $tilausnumero    = '';
    $laskurow      = '';
    $kukarow['kesken']  = '';
  }
  elseif ($toim == "EXTRANET_REKLAMAATIO") {
    $query  = "UPDATE lasku
               SET alatila = 'A'
               where yhtio = '$kukarow[yhtio]'
               and tunnus  = '$kukarow[kesken]'
               and tila    = 'C'
               and alatila = ''";
    $result = pupe_query($query);

    // tilaus ei enää kesken...
    $query  = "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
    $result = pupe_query($query);

    if ($yhtiorow['reklamaation_kasittely'] == 'U' or
      ($yhtiorow['reklamaation_kasittely'] == 'X' and $laskurow['tilaustyyppi'] != 'U')) {

      $oslapp = "email";
      $oslappkpl = 1;
      require "osoitelappu_pdf.inc";
    }
  }
  // Myyntitilaus valmis
  else {
    //Jos käyttäjä on myymässä tuotteita ulkomaan varastoista, niin laitetaan tilaus holdiin
    if ($toimitetaan_ulkomaailta == "YES" and $kukarow["tilaus_valmis"] == "3") {
      $kukarow["tilaus_valmis"] = "2";
    }

    //katotaan onko asiakkaalla yli 30 päivää vanhoja maksamattomia laskuja
    if ($kukarow['extranet'] != '' and ($kukarow['saatavat'] == 0 or $kukarow['saatavat'] == 2)) {
      $saaquery =  "SELECT
                    lasku.ytunnus,
                    sum(if (TO_DAYS(NOW())-TO_DAYS(erpcm) > 30, summa-saldo_maksettu, 0)) dd
                    FROM lasku use index (yhtio_tila_mapvm)
                    WHERE tila         = 'U'
                    AND alatila        = 'X'
                    AND mapvm          = '0000-00-00'
                    AND erpcm         != '0000-00-00'
                    AND lasku.ytunnus  = '$laskurow[ytunnus]'
                    AND lasku.yhtio    = '$kukarow[yhtio]'
                    GROUP BY 1
                    ORDER BY 1";
      $saaresult = pupe_query($saaquery);
      $saarow = mysql_fetch_assoc($saaresult);

      //ja jos on niin ne siirretään tilaus holdiin
      if ($saarow['dd'] > 0) {
        $kukarow["tilaus_valmis"] = "2";
      }
    }

    // Käyttäjä jonka tilaukset on hyväksytettävä
    if ($kukarow["tilaus_valmis"] == "2") {
      $query  = "UPDATE lasku set
                 tila        = 'N',
                 alatila     = 'F'
                 where yhtio='$kukarow[yhtio]'
                 and tunnus='$kukarow[kesken]'
                 and tila    = 'N'
                 and alatila = ''";
      $result = pupe_query($query);

      // tilaus ei enää kesken...
      $query  = "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
      $result = pupe_query($query);

    }
    else {

      if ($kukarow["extranet"] != "") {
        //Pyydetään tilaus-valmista olla echomatta mitään
        $silent = "SILENT";
      }

      $tilausvalmiskutsuja = "TILAUSMYYNTI";

      // Otetaan laskurow talteen
      $tm_laskurow_talteen = $laskurow;

      // tulostetaan lähetteet ja tilausvahvistukset tai sisäinen lasku..
      require "tilaus-valmis.inc";

      // Käsinsyötetty verkkokauppatilaus laskutettiin ja nyt se laitetaan takaisin keräysjonoon
      if ($tm_laskurow_talteen["tilaustyyppi"] == "W") {
        // Jos se on laskutettu, niin laitetaan se
        $query = "SELECT tunnus
                  from lasku
                  where yhtio = '$kukarow[yhtio]'
                  and tunnus = '$tm_laskurow_talteen[tunnus]'
                  and tila ='L'
                  and alatila = 'X'";
        $result = pupe_query($query);

        if ($laskurow = mysql_fetch_assoc($result)) {

          $query  = "UPDATE lasku set
                     sisainen = ''
                     where yhtio = '$kukarow[yhtio]'
                     and tunnus = '$laskurow[tunnus]'";
          pupe_query($query);

          $kukarow['kesken'] = $laskurow['tunnus'];

          laskutettu_myyntitilaus_tulostusjonoon($laskurow["tunnus"]);
        }
      }
    }
  }

  // ollaan käsitelty projektin osatoimitus joten palataan tunnusnipun otsikolle..
  if ($kukarow["extranet"] == "" and $laskurow["tunnusnippu"] > 0 and ($toim != "TARJOUS" and $toim != "EXTTARJOUS")) {

    $aika=date("d.m.y @ G:i:s", time());
    echo "<font class='message'>".t("Osatoimitus")." $otsikko $kukarow[kesken] ".t("valmis")."! ($aika) $kaikkiyhteensa $laskurow[valkoodi]</font><br><br>";

    if ($projektilla > 0 and $laskurow["tunnusnippu"] > 0 and $laskurow["tunnusnippu"] != $laskurow["tunnus"]) {
      $tilausnumero = $laskurow["tunnusnippu"];

      //  Päiviteään aina myös projektin aktiiviseksi jos se on ollut kesken
      $query = "UPDATE lasku SET
                alatila         = 'A'
                WHERE yhtio     = '$kukarow[yhtio]'
                and tunnusnippu = '$laskurow[tunnusnippu]'
                and tunnusnippu > 0
                and tila        = 'R'
                and alatila     = ''";
      $updres = pupe_query($query);

      //  Hypätään takaisin otsikolle
      echo "<font class='info'>".t("Palataan projektille odota hetki..")."</font><br>";
      echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF?toim=PROJEKTI&valitsetoimitus=$tilausnumero'>";
      exit;
    }
    else {
      $tee        = '';
      $tilausnumero    = '';
      $laskurow      = '';
      $kukarow['kesken']  = '';
    }
  }
  else {
    if ($kukarow["extranet"] == "") {
      $aika=date("d.m.y @ G:i:s", time());

      if (isset($splitatut) and sizeof($splitatut) > 0) {
        foreach ($splitatut as $value) {
          echo "<font class='message'>";
          echo $otsikko, ' ';
          echo $value, ' ';
          echo t("valmis");
          echo "! (" . $aika . ") ";
          echo $kaikkiyhteensa . $laskurow['valkoodi'];
          echo "</font><br />";
        }
        echo "<br />";
      }
      elseif (!isset($splitatut) and $luottorajavirhe_ylivito_valmis) {
        echo "<font class='message'>";
        echo $otsikko, ' ', $kukarow['kesken'], ' ';
        echo t("valmis");
        echo "! ($aika) $kaikkiyhteensa {$laskurow['valkoodi']}</font><br /><br />";
      }

      if ($maksupaate_kassamyynti and
        $maksuehtorow["kateinen"] != "" and $kateismaksu["kateinen"] != ""
      ) {
        $kateista_takaisin = $kateista_annettu - $kateismaksu["kateinen"];

        echo "<table class='kateis-table'>
                <thead>
                  <tr>
                    <th>" . t("Yhteensä") . "</th>
                    <th>" . t("Annettu") . "</th>
                    <th>" . t("Takaisin") . "</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>{$kateismaksu["kateinen"]} {$laskurow["valkoodi"]}</td>
                    <td>{$kateista_annettu} {$laskurow["valkoodi"]}</td>
                    <td>{$kateista_takaisin} {$laskurow["valkoodi"]}</td>
                  </tr>
                </tbody>
              </table><br>";
      }

      if (($kukarow["kassamyyja"] != '' or
          $kukarow["dynaaminen_kassamyynti"] != "" or
          $yhtiorow["dynaaminen_kassamyynti"] != "") and
        $kateinen != '' and
        $kukarow['extranet'] == '' and
        $kateisohitus == "" and
        (($yhtiorow["maksupaate_kassamyynti"] == "" and
            $kukarow["maksupaate_kassamyynti"] == "") or
          $kukarow["maksupaate_kassamyynti"] == "E")
      ) {
        echo "  <script type='text/javascript' language='JavaScript'>
            <!--
              function update_summa(kaikkiyhteensa) {
                kateinen = document.getElementById('kateisraha').value.replace(\",\",\".\");
                summa = 0;
                if (document.getElementById('kateisraha').value != '') {
                  summa = kateinen - kaikkiyhteensa;
                }
                summa = Math.round(summa*100)/100;
                document.getElementById('loppusumma').innerHTML = '<b>' + summa.toFixed(2) + '</b>';
              }
            -->
            </script>";

        echo "<form name='laskuri' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>";
        echo "<input type='hidden' name='toim' value='{$toim}'>";
        echo "<table class='laskuri'>";

        if (!isset($kateismaksu['kateinen']) or $kateismaksu['kateinen'] == '') {
          $yhteensa_teksti = t("Yhteensä");
        }
        else {
          $yhteensa_teksti = t("Käteinen");
          $kaikkiyhteensa = $kateismaksu['kateinen'];
        }

        echo "<tr><th>$yhteensa_teksti</th><td align='right'>$kaikkiyhteensa</td><td>$laskurow[valkoodi]</td></tr>";
        echo "<tr><th>".t("Annettu")."</th><td><input size='7' autocomplete='off' type='text' id='kateisraha' name='kateisraha' onkeyup='update_summa(\"$kaikkiyhteensa\");'></td><td>$laskurow[valkoodi]</td></tr>";
        echo "<tr><th>".t("Takaisin")."</th><td name='loppusumma' id='loppusumma' align='right'><strong>0.00</strong></td><td>$laskurow[valkoodi]</td></tr>";
        echo "</table>";
        echo "</form>";
        echo "<br>";

        require_once "tilauskasittely/tulosta_asiakkaan_kuitti.inc";

        $kuitti_params = array(
          "pdf_kuitti" => true,
          "pdf_kuitti_printdialog" => true,
          "avaa_lipas_lopuksi" => false
        );

        $kuittiurl = tulosta_asiakkaan_kuitti($laskurow["laskunro"], "", $kuitti_params);

        // Tulostusdialogi
        echo js_openPrintDialog($kuittiurl, "Tulosta kuitti");

        $formi  = "laskuri";
        $kentta = "kateisraha";
      }
    }

    $tee        = '';

    if ($luottorajavirhe_ylivito_valmis) {
      $tilausnumero    = '';
      $laskurow      = '';
      $kukarow['kesken']  = '';
      $tila = '';
    }

    if ($kukarow["extranet"] != "") {
      if ($toim == 'EXTRANET_REKLAMAATIO') {
        echo "<font class='head'>$otsikko</font><hr><br><br>";
        echo "<font class='message'>".t("Reklamaatio valmis. Palaamme asiaan")."!</font><br><br>";
      }
      else {
        echo "<font class='head'>$otsikko</font><hr><br><br>";
        echo "<font class='message'>".t("Tilaus valmis. Kiitos tilauksestasi")."!</font><br><br>";
      }
      $tee = "SKIPPAAKAIKKI";
    }
  }

  if ($kukarow["extranet"] == "" and $lopetus != '' and $luottorajavirhe_ylivito_valmis) {
    lopetus($lopetus, "META");
  }
}

if ($kukarow["extranet"] == "" and ((($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") and $tee == "LEPAA") or ($toim == "REKLAMAATIO" and $tee == "LEPAA" and
      ($yhtiorow['reklamaation_kasittely'] == '' or
        ($yhtiorow['reklamaation_kasittely'] == 'X' and $laskurow['tilaustyyppi'] == 'U'))))) {
  require "tyomaarays/tyomaarays.inc";
}

if ($kukarow["extranet"] == "" and $toim == "REKLAMAATIO" and $tee == "LEPAA" and
  ($yhtiorow['reklamaation_kasittely'] == 'U' or
    ($yhtiorow['reklamaation_kasittely'] == 'X' and $laskurow['tilaustyyppi'] != 'U'))) {

  $query  = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and kesken = '$tilausnumero'";
  $result = pupe_query($query);

  if ($laskurow['tilaustyyppi'] == 'U') {
    echo "<font class='message'>".t("Takuu: %s siirretty lepäämään", '', $tilausnumero).".</font><br><br>";
  }
  else {
    echo "<font class='message'>".t("Reklamaatio: %s siirretty lepäämään", '', $tilausnumero).".</font><br><br>";
  }

  $tee        = '';
  $tilausnumero    = '';
  $laskurow      = '';
  $kukarow['kesken']  = '';

  if ($kukarow["extranet"] == "" and $lopetus != '') {
    lopetus($lopetus, "META");
  }
}

if ($kukarow["extranet"] == "" and $toim == "REKLAMAATIO" and $tee == "ODOTTAA" and $yhtiorow['reklamaation_kasittely'] == 'U') {
  $tilausvalmiskutsuja = "TILAUSMYYNTI";

  // tulostetaan lähetteet ja tilausvahvistukset tai sisäinen lasku..
  require "tilaus-valmis.inc";
}

if ($kukarow["extranet"] == "" and $toim == 'REKLAMAATIO'
  and ($tee == 'VASTAANOTTO' or $tee == 'VALMIS') and
  ($yhtiorow['reklamaation_kasittely'] == 'U' or
    ($yhtiorow['reklamaation_kasittely'] == 'X' and $laskurow['tilaustyyppi'] != 'U'))) {
  // Joka tarkoittaa että "Reklamaatio on vastaanotettu
  // tämän jälkeen kun seuraavassa vaiheessa tullaan niin "Tulostetaan Purkulista"

  if (count($komento) == 0) {
    echo "<font class='head'>", t("Lähete"), ":</font><hr>";

    $tulostimet[0] = "Lähete";

    require "inc/valitse_tulostin.inc";
  }
  else {

    // Tulostetaan lähete
    $params = array(
      'laskurow'          => $laskurow,
      'sellahetetyyppi'       => "",
      'extranet_tilausvahvistus'   => "",
      'naytetaanko_rivihinta'    => "",
      'tee'            => "",
      'toim'            => $toim,
      'komento'           => $komento,
      'lahetekpl'          => "",
      'kieli'           => ""
    );

    pupesoft_tulosta_lahete($params);
  }

  $_tilaustyyppi = ($laskurow['tilaustyyppi'] != 'U');
  $_tilaustyyppi = ($_tilaustyyppi and $yhtiorow['reklamaation_kasittely'] == 'X');
  $sahkoinen_lahete_check = !empty($sahkoinen_lahete);
  $sahkoinen_lahete_check = ($sahkoinen_lahete_check and isset($generoi_sahkoinen_lahete));
  $sahkoinen_lahete_check = ($sahkoinen_lahete_check and trim($generoi_sahkoinen_lahete) != "");
  $sahkoinen_lahete_check = ($sahkoinen_lahete_check and !empty($sahkoinen_lahete_toim));
  $sahkoinen_lahete_check = ($sahkoinen_lahete_check and in_array($toim, $sahkoinen_lahete_toim));

  if ($_tilaustyyppi and  $sahkoinen_lahete_check and $kukarow["extranet"] == "") {

    require_once "inc/sahkoinen_lahete.class.inc";

    sahkoinen_lahete($laskurow);
  }

  if ($_tilaustyyppi and trim($laskurow['tilausvahvistus']) != ""
    and (strpos($laskurow['tilausvahvistus'], 'S') !== FALSE or strpos($laskurow['tilausvahvistus'], 'O') !== FALSE)) {

    $params_tilausvahvistus = array(
      'tee'            => $tee,
      'toim'           => $toim,
      'kieli'          => $kieli,
      'laskurow'       => $laskurow,
      'naytetaanko_rivihinta'    => $naytetaanko_rivihinta,
      'extranet_tilausvahvistus' => $extranet_tilausvahvistus,
    );

    laheta_tilausvahvistus($params_tilausvahvistus);
  }

  if ($tee == 'VALMIS' or $yhtiorow['reklamaation_kasittely'] == 'X') {
    $alatila_lisa = "AND alatila = ''";              // semilaaja reklamaatio & takuu
  }
  else {
    $alatila_lisa = "AND alatila = 'A'";             // laajin reklamaatio
  }
  $query = "UPDATE lasku set
            alatila     = 'B'
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$tilausnumero'
            AND tila    = 'C'
            $alatila_lisa";
  $result = pupe_query($query);

  tee_palautustilaus($laskurow);

  $query  = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and kesken = '$tilausnumero'";
  $result = pupe_query($query);

  if ($laskurow['tilaustyyppi'] == 'U') {
    echo "<font class='message'>".t("Takuu: %s kuitattu vastaanotetuksi", '', $tilausnumero).".</font><br><br>";
  }
  else {
    echo "<font class='message'>".t("Reklamaatio: %s kuitattu vastaanotetuksi", '', $tilausnumero).".</font><br><br>";
  }

  require 'tilauksesta_varastosiirto.inc';

  tilauksesta_varastosiirto($laskurow['tunnus'], 'P');

  // Laitetaan Intrastat-tiedot kuntoon, muuten laskujen ketjutus ei onnaa.
  $laskurow = palauta_intrastat_tiedot($laskurow, $laskurow['varasto'], TRUE);

  $tee           = '';
  $tilausnumero  = '';
  $laskurow      = '';

  aseta_kukarow_kesken(0);

  if ($kukarow["extranet"] == "" and $lopetus != '') {
    lopetus($lopetus, "META");
  }
}

if ($kukarow["extranet"] == "" and $toim == 'REKLAMAATIO'
  and ($tee == 'VALMIS_VAINSALDOTTOMIA' or $tee == 'VALMIS') and
  ($yhtiorow['reklamaation_kasittely'] == 'U' or
    ($yhtiorow['reklamaation_kasittely'] == 'X' and $laskurow['tilaustyyppi'] != 'U'))) {
  // Reklamaatio/takuu on valmis laskutettavaksi
  // katsotaan onko tilausrivit Unikko-järjestelmään
  if ($laskurow['tilaustyyppi'] == 'U' or $laskurow['tilaustyyppi'] == 'R') {
    $saldoton_lisa = "and tuote.ei_saldoa=''";
  }
  else {
    $saldoton_lisa = "";
  }
  $query = "SELECT tilausrivi.tunnus
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno $saldoton_lisa)
            WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus  = '{$tilausnumero}'
            AND tilausrivi.tyyppi  != 'D'";
  $varasto_chk_res = pupe_query($query);

  // Ei saa olla saldollisia tuotteita
  if (mysql_num_rows($varasto_chk_res) == 0) {
    $query = "UPDATE lasku set
              tila        = 'L',
              alatila     = 'D'
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$tilausnumero'
              AND tila    = 'C'
              AND alatila in ('A','B','C','')";
    $result = pupe_query($query);

    $query  = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and kesken = '$tilausnumero'";
    $result = pupe_query($query);

    if ($laskurow['tilaustyyppi'] == 'U') {
      echo "<font class='message'>".t("Takuu: %s kuitattu vastaanotetuksi", '', $tilausnumero).".</font><br><br>";
    }
    else {
      echo "<font class='message'>".t("Reklamaatio: %s kuitattu vastaanotetuksi", '', $tilausnumero).".</font><br><br>";
    }

    $tee        = '';
    $tilausnumero    = '';
    $laskurow      = '';
    $kukarow['kesken']  = '';

    if ($kukarow["extranet"] == "" and $lopetus != '') {
      lopetus($lopetus, "META");
    }
  }
  else {

    if ($laskurow['tilaustyyppi'] == 'U') {
      echo "<font class='error'>".t("VIRHE: Takuulla oli saldollisia tuotteita")."!</font><br><br>";
    }
    else {
      echo "<font class='error'>".t("VIRHE: Reklamaatiolla oli saldollisia tuotteita")."!</font><br><br>";
    }

    $tee = '';
  }
}

//Voidaan tietyissä tapauksissa kopsata tästä suoraan uusi tilaus
if ($uusitoimitus != "") {

  if ($uusitoimitus == "VALMISTAVARASTOON" or $valitsetoimitus == "VALMISTAASIAKKAALLE") {
    $aquery = "SELECT valmistukset.tunnus
               FROM lasku
               JOIN lasku valmistukset ON valmistukset.yhtio=lasku.yhtio and valmistukset.tunnusnippu=lasku.tunnusnippu and valmistukset.tila IN ('W','V')
               WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus='$tilausnumero' and lasku.tunnusnippu>0";
    $ares = pupe_query($aquery);

    if (mysql_num_rows($ares) > 0) {
      $arow = mysql_fetch_assoc($ares);
      $kopioitava_otsikko = $arow["tunnus"];
    }
  }
  else {
    $kopioitava_otsikko = $laskurow["tunnusnippu"];
  }

  if ($kopioitava_otsikko > 0) {
    $toim         = $uusitoimitus;
    $asiakasid       = $laskurow["liitostunnus"];
    $tee         = "OTSIK";
    $tiedot_laskulta  = "YES";
  }
}

//Muutetaan otsikkoa
if ($kukarow["extranet"] == "" and ($tee == "OTSIK" or ($toim != "PIKATILAUS" and !isset($laskurow["liitostunnus"])))) {

  //Tämä jotta myös rivisyötön alkuhomma toimisi
  $tee = "OTSIK";

  if ($toim == "VALMISTAVARASTOON" or $toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
    require "otsik_siirtolista.inc";
  }
  elseif (($toim == 'EXTTARJOUS' or $toim == "EXTENNAKKO") and isset($tarjous_tee)) {
    if (isset($tarjous_tee) and $tarjous_tee != 'luo_dummy_tarjous') {
      require 'inc/valitse_asiakas.inc';
    }
    else {
      $tee = "";
      $kukarow['kesken'] = $tilausnumero;

      $saate_teksti = pupesoft_cleanstring($saate_teksti);

      $query = "UPDATE lasku
                JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
                SET lasku.olmapvm = '{$viimeinen_voimassaolo_pvm}',
                laskun_lisatiedot.saate = '{$saate_teksti}'
                WHERE lasku.yhtio       = '{$kukarow['yhtio']}'
                AND lasku.tunnus        = '{$tilausnumero}'";
      pupe_query($query);
    }
  }
  else {
    require 'otsik.inc';
  }

  //Tässä halutaan jo hakea uuden tilauksen tiedot
  if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO" or $toim == "SIIRTOTYOMAARAYS" )) {
    $query  = "SELECT laskun_lisatiedot.*, lasku.*, tyomaarays.*
               FROM lasku
               JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio AND tyomaarays.otunnus = lasku.tunnus)
               LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
               WHERE lasku.tunnus  = '$kukarow[kesken]'
               AND lasku.yhtio     = '$kukarow[yhtio]'
               AND lasku.tila     != 'D'";
  }
  else {
    // pitää olla: siirtolista, sisäinen työmääräys, reklamaatio, tarjous, valmistus, myyntitilaus, ennakko, myyntitilaus, ylläpitosopimus, projekti
    $query   = "SELECT laskun_lisatiedot.*, lasku.*
                FROM lasku
                LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                WHERE lasku.tunnus = '$kukarow[kesken]'
                AND lasku.yhtio    = '$kukarow[yhtio]'
                AND lasku.tila     in ('G','S','C','T','V','N','E','L','0','R')
                AND (lasku.alatila != 'X' or lasku.tila = '0')";
  }
  $result = pupe_query($query);
  $laskurow = mysql_fetch_assoc($result);

  $a_qry = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$laskurow['liitostunnus']}'";
  $a_res = pupe_query($a_qry);
  $asiakasrow = mysql_fetch_assoc($a_res);

  $kaytiin_otsikolla = "NOJOO!";
}

// Jos käytössä "semi laaja"-reklamaatiokäsittely (X)
// Ja tilaustyyppi ei ole takuu
// Ja ohitetaan varastoprosessi eli "suoraan laskutukseen" (eilahetetta != '')
// Halutaan tällöin simuloida lyhyttä reklamaatioprosessia
if ($toim == "REKLAMAATIO" and $laskurow['eilahetetta'] != '' and $yhtiorow['reklamaation_kasittely'] == 'X' and $laskurow['tilaustyyppi'] != 'U') {
  $yhtiorow['reklamaation_kasittely'] = '';
}

if (($toim == 'EXTTARJOUS' or $toim == "EXTENNAKKO") and ((isset($tarjous_tee) and $tarjous_tee != 'luo_dummy_tarjous') or isset($action))) {
  require 'inc/valitse_asiakas.inc';
}

//lisätään rivejä tiedostosta
if ($tee == 'mikrotila' or $tee == 'file') {

  if ($kukarow["extranet"] == "" and $toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
    require 'mikrotilaus_siirtolista.inc';
  }
  else {
    require 'mikrotilaus.inc';
  }

  if ($tee == 'Y') {
    $tee = "";
  }
}

if ($tee == 'mikrotila_matriisi' or $tee == 'file_matriisi') {

  require 'mikrotilaus_matriisi.inc';

  if ($tee == 'Y') {
    $tee = "";
  }
}

// Tehdään rahoituslaskuelma
if ($tee == 'osamaksusoppari') {
  require 'osamaksusoppari.inc';
}

// Tehdään vakuutushakemus
if ($tee == 'vakuutushakemus') {
  require 'vakuutushakemus.inc';
}

// siirretään tilauksella olevat tuotteet asiakkaan asiakashinnoiksi
if ($tee == "tuotteetasiakashinnastoon" and in_array($toim, array("TARJOUS", "EXTTARJOUS", "PIKATILAUS", "RIVISYOTTO", "VALMISTAASIAKKAALLE", "TYOMAARAYS", "PROJEKTI"))) {

  $query = "SELECT tilausrivi.*,
            if (tuote.myyntihinta_maara = 0, 1, tuote.myyntihinta_maara) myyntihinta_maara
            FROM tilausrivi
            JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
            WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus  = '{$tilausnumero}'
            AND tilausrivi.tyyppi  != 'D'
            AND tilausrivi.var     != 'P'";
  $result = pupe_query($query);

  while ($tilausrivi = mysql_fetch_assoc($result)) {
    $hinta = $tilausrivi["hinta"];

    if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
      $hinta = laskuval($hinta, $laskurow["vienti_kurssi"]);
    }

    $hintapyoristys_echo = $hinta * generoi_alekentta_php($tilausrivi, 'M', 'kerto');

    $asiakas_hinta_liitos = isset($asiakas_hinta_liitos) ? $asiakas_hinta_liitos : "";

    switch ($asiakas_hinta_liitos) {
    case "ytunnus":
      $liitoslisa = "ytunnus = '{$asiakasrow["ytunnus"]}'";
      break;
    case "asiakasryhma":
      $liitoslisa = "asiakas_ryhma = '{$asiakasrow["ryhma"]}'";
      break;
    case "piiri":
      $liitoslisa = "piiri = '{$asiakasrow["piiri"]}'";
      break;
    default:
      $liitoslisa = "asiakas = '{$laskurow["liitostunnus"]}'";
    }

    $asiakas_hinta_loppupv = (int) $asiakas_hinta_loppupv;
    $asiakas_hinta_loppukk = (int) $asiakas_hinta_loppukk;
    $asiakas_hinta_loppuvv = (int) $asiakas_hinta_loppuvv;

    $loppupaivamaaralisa = "loppupvm = '{$asiakas_hinta_loppuvv}-{$asiakas_hinta_loppukk}-$asiakas_hinta_loppupv'";

    if (!empty($lisaa_asiakashinta) and (float) $hinta != 0) {
      $query = "SELECT *
                FROM asiakashinta
                where yhtio  = '$kukarow[yhtio]'
                and tuoteno  = '$tilausrivi[tuoteno]'
                and {$liitoslisa}
                and {$loppupaivamaaralisa}
                and hinta    = round($hintapyoristys_echo * $tilausrivi[myyntihinta_maara], $yhtiorow[hintapyoristys])
                and valkoodi = '$laskurow[valkoodi]'";
      $chk_result = pupe_query($query);

      if (mysql_num_rows($chk_result) == 0) {
        $query = "INSERT INTO asiakashinta SET
                  yhtio      = '$kukarow[yhtio]',
                  tuoteno    = '$tilausrivi[tuoteno]',
                  {$liitoslisa},
                  {$loppupaivamaaralisa},
                  hinta      = round($hintapyoristys_echo * $tilausrivi[myyntihinta_maara], $yhtiorow[hintapyoristys]),
                  valkoodi   = '$laskurow[valkoodi]',
                  alkupvm    = now(),
                  laatija    = '$kukarow[kuka]',
                  luontiaika = now(),
                  muuttaja   = '$kukarow[kuka]',
                  muutospvm  = now()";
        pupe_query($query);

        echo t("Lisättin tuote")." $tilausrivi[tuoteno] ".t("asiakkaan hinnastoon hinnalla").": ".hintapyoristys($hintapyoristys_echo)." $laskurow[valkoodi]<br>";
      }
      else {
        echo t("Tuote")." $tilausrivi[tuoteno] ".t("löytyi jo asiakashinnastosta").": ".hintapyoristys($hintapyoristys_echo)." $laskurow[valkoodi]<br>";
      }
    }

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {

      if (!empty(${"lisaa_asiakasalennus_".$alepostfix}) and (float) $tilausrivi["ale".$alepostfix] != 0) {
        $query = "SELECT *
                  FROM asiakasalennus
                  where yhtio     = '$kukarow[yhtio]'
                  and tuoteno     = '$tilausrivi[tuoteno]'
                  and alennuslaji = '$alepostfix'
                  and {$liitoslisa}
                  and {$loppupaivamaaralisa}
                  and alennus  = {$tilausrivi["ale".$alepostfix]}";
        $chk_result = pupe_query($query);

        if (mysql_num_rows($chk_result) == 0) {
          $query = "INSERT INTO asiakasalennus SET
                    yhtio       = '$kukarow[yhtio]',
                    tuoteno     = '$tilausrivi[tuoteno]',
                    alennuslaji = '$alepostfix',
                    {$liitoslisa},
                    {$loppupaivamaaralisa},
                    alennus    = {$tilausrivi["ale".$alepostfix]},
                    alkupvm    = now(),
                    laatija    = '$kukarow[kuka]',
                    luontiaika = now(),
                    muuttaja   = '$kukarow[kuka]',
                    muutospvm  = now()";
          pupe_query($query);

          echo t("Lisättin tuote")." $tilausrivi[tuoteno] ".t("asiakkaan alennuksiin alennukselle").": {$tilausrivi["ale".$alepostfix]}% <br>";
        }
        else {
          echo t("Tuote")." $tilausrivi[tuoteno] ".t("löytyi jo asiakasalenuksista").": {$tilausrivi["ale".$alepostfix]}% <br>";
        }
      }
    }
  }

  echo "<br>";
  $tee = "";
}

if ($kukarow["extranet"] == "" and $tee == 'jyvita') {
  if ((isset($valitut_rivit_jyvitys) and !empty($valitut_tilausrivi_tunnukset)) or (!isset($valitut_rivit_jyvitys) and empty($valitut_tilausrivi_tunnukset))) {
    require "jyvita_riveille.inc";
  }
  else {
    echo "<font class='error'>".t("Rivejä ei voitu jyvittää koska yhtään riviä ei oltu valittu")."</font><br/><br/>";
    $jysum   = '';
    $tila   = '';
    $tee   = '';
    $kiekat  = "";
    $summa    = "";
    $anna_ea = "";
    $hinta   = "";
  }
}

if ($kukarow['extranet'] == '' and $tee == 'kate_jyvita') {
  if ((isset($valitut_rivit_jyvitys) and !empty($valitut_tilausrivi_tunnukset)) or (!isset($valitut_rivit_jyvitys) and empty($valitut_tilausrivi_tunnukset))) {
    if ($jysum > 0) {
      $jysum = str_replace(',', '.', $jysum);
      require "inc/kate_jyvita_riveille.inc";
    }
    else {
      echo "<font class='error'>".t("Rivejä ei voitu jyvittää koska kate pitää olla suurempi kuin %s ja pienempi kuin %s", '', 0, 99.99)."</font><br/><br/>";
    }
  }
  else {
    echo "<font class='error'>".t("Rivejä ei voitu jyvittää koska yhtään riviä ei oltu valittu")."</font><br/><br/>";

  }
  $jysum   = '';
  $tila   = '';
  $tee   = '';
  $kiekat  = "";
  $summa    = "";
  $anna_ea = "";
  $hinta   = "";
}

// Lisätään tän asiakkaan valitut JT-rivit tälle tilaukselle
if (($tee == "JT_TILAUKSELLE" and $tila == "jttilaukseen" and $muokkauslukko == "")
  or ((
      (($yhtiorow['jt_automatiikka'] == 'X' or $yhtiorow['jt_automatiikka'] == 'W') and $toim == 'EXTRANET')
      or (($yhtiorow['jt_automatiikka'] == 'M' or $yhtiorow['jt_automatiikka'] == 'K') and ($toim == 'PIKATILAUS' or $toim == 'RIVISYOTTO'))
    )
    and (int) $kukarow['kesken'] > 0
    and $kaytiin_otsikolla == "NOJOO!"
    and $tee == ''
    and $laskurow["tila"] == "N"
    and in_array($laskurow["alatila"], array("", "A"))
  )
) {

  $tilaus_on_jo = "KYLLA";

  // Halutaan poimia heti kaikki jt-rivit extranet-tilauksille ensimmäisellä kerralla
  if ((
      (($yhtiorow['jt_automatiikka'] == 'X' or $yhtiorow['jt_automatiikka'] == 'W') and $toim == 'EXTRANET')
      or ($yhtiorow['jt_automatiikka'] == 'M' or $yhtiorow['jt_automatiikka'] == 'K')
    )
    and (int) $kukarow['kesken'] > 0
    and $kaytiin_otsikolla == "NOJOO!"
    and $tee == ''
  ) {

    if (isset($laskurow["varasto"]) and (int) $laskurow["varasto"] > 0) {
      $varasto = array((int) $laskurow["varasto"]);
    }
    else {

      $_varastotyyppi = $toim != 'EXTRANET' ? 'kaikki_varastot' : '';

      $params = array(
        'asiakas_tunnus' => $laskurow['liitostunnus'],
        'toimipaikka_tunnus' => $laskurow['yhtio_toimipaikka'],
        'toimitus_maa' => $laskurow["toim_nimi"] == "" ? $laskurow["maa"] : $laskurow["toim_maa"],
        'varastotyyppi' => $_varastotyyppi,
      );
      $varasto = sallitut_varastot($params);
    }

    //laitetaan myyntitilaukset jaksotettu talteen, että sitä voidaan käyttää jtselaus.php:ssä
    $myyntitilaus_jaksotettu = $laskurow['jaksotettu'];

    jt_toimita($laskurow["ytunnus"], $laskurow["liitostunnus"], $varasto, "", "", "tosi_automaaginen", "JATKA", "automaattinen_poiminta", '', '', 'MYYNTITILAUKSELTA');
    echo "<br>";

    $tyhjenna   = "JOO";
    $tee     = "";
  }
  else {
    require "jtselaus.php";

    $tyhjenna   = "JOO";
    $tee     = "";
  }
}

if ($tee == "MUUTA_EXT_ENNAKKO" and $kukarow['extranet'] == '') {

  $query = "UPDATE lasku
            SET clearing = ''
            WHERE yhtio='{$kukarow['yhtio']}'
            AND tunnus       = '{$tilausnumero}'
            AND tilaustyyppi = '{$tilaustyyppi}'
            AND tila         = '{$orig_tila}'
            AND alatila      = '{$orig_alatila}'
            AND clearing     = 'EXTENNAKKO'";
  $jauza = pupe_query($query);

  if (mysql_affected_rows() != 1) {
    echo "<font class='error'>".t("VIRHE: Tilausta %s ei muutettu normaaliksi ennakkotilaukseksi", "", $tilausnumero)."!</font><br><br>";
  }
  else {
    echo "<font class='message'>".t("Tilaus %s muutettiin normaaliksi ennakkotilaukseksi", "", $tilausnumero)."!</font><br><br>";
  }
}

// näytetään tilaus-ruutu...
if ($tee == '') {
  $formi = "tilaus";

  echo "<font class='head'>$otsikko</font><hr>";

  if (isset($viestin_lahetys_onnistui)) {
    if ($viestin_lahetys_onnistui) {
      echo "<font class='ok'>" . t("Vahvistusviesti lähetetty") . "</font><br>";
    }
    else {
      echo "<font class='error'>" . t("Vahvistusviestin lähetys epäonnistui") . "</font><br>";
    }
  }

  if ($kukarow['kesken'] != '0') {
    $tilausnumero = $kukarow['kesken'];
  }

  // Tässä päivitetään 'pikaotsikkoa' jos kenttiin on jotain syötetty ja arvoja vaihdettu
  if ($kukarow["kesken"] > 0 and (
      (isset($toimitustapa) and $toimitustapa != '' and $toimitustapa != $laskurow["toimitustapa"]) or
      (isset($rahtisopimus) and $rahtisopimus != '' and $rahtisopimus != $laskurow["rahtisopimus"]) or
      (isset($viesti) and $viesti != $laskurow["viesti"]) or
      (isset($asiakkaan_tilausnumero) and $asiakkaan_tilausnumero != $laskurow["asiakkaan_tilausnumero"]) or
      (isset($tilausvahvistus) and $tilausvahvistus != $laskurow["tilausvahvistus"]) or
      (isset($myyjanro) and $myyjanro > 0 and $myyjanro != $v_myyjanro) or
      (isset($myyja) and $myyja > 0 and $myyja != $laskurow["myyja"]) or
      (isset($maksutapa) and $maksutapa != ''))) {

    if ((int) $myyjanro > 0) {
      $apuqu = "SELECT *
                FROM kuka use index (yhtio_myyja)
                WHERE yhtio = '$kukarow[yhtio]'
                AND myyja   = '$myyjanro'
                AND myyja   > 0";
      $meapu = pupe_query($apuqu);

      if (mysql_num_rows($meapu) == 1) {
        $apuro = mysql_fetch_assoc($meapu);
        $myyja = $apuro['tunnus'];
        $pika_paiv_myyja = " myyja = '$myyja', ";
      }
      elseif (mysql_num_rows($meapu) > 1) {
        echo "<font class='error'>".t("Syöttämäsi myyjänumero")." $myyjanro ".t("löytyi usealla käyttäjällä")."!</font><br><br>";
      }
      else {
        echo "<font class='error'>".t("Syöttämäsi myyjänumero")." $myyjanro ".t("ei löytynyt")."!</font><br><br>";
      }
    }
    elseif ((int) $myyja > 0) {
      $pika_paiv_myyja = " myyja = '$myyja', ";
    }

    if ($toimitustapa != $laskurow['toimitustapa']) $toimitustavan_lahto = array();

    if ($maksutapa != '') {
      $laskurow["maksuehto"] = $maksutapa;
    }

    // haetaan maksuehdoen tiedot tarkastuksia varten
    $apuqu = "SELECT *
              FROM maksuehto
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$laskurow[maksuehto]'";
    $meapu = pupe_query($apuqu);

    $kassalipas = "";
    $nollaa_lahto = "";

    if (mysql_num_rows($meapu) == 1 and $toimitustapa != '') {
      $meapurow = mysql_fetch_assoc($meapu);

      // jos kyseessä oli käteinen
      if ($meapurow["kateinen"] != "") {
        // haetaan toimitustavan tiedot tarkastuksia varten
        $apuqu2 = "SELECT *
                   FROM toimitustapa
                   WHERE yhtio = '$kukarow[yhtio]'
                   AND selite  = '$toimitustapa'";
        $meapu2 = pupe_query($apuqu2);
        $meapu2row = mysql_fetch_assoc($meapu2);

        // Etukäteen maksetut tilaukset, ei sörkitä toimitustapaa enää
        $_etukateen_maksettu = ($laskurow['mapvm'] != '0000-00-00' and $laskurow['chn'] == '999');

        // ja toimitustapa ei ole nouto eikä kyseessä ole verkkokauppatilaus laitetaan toimitustavaksi nouto... hakee järjestyksessä ekan
        if ($meapu2row["nouto"] == "" and ($laskurow['tilaustyyppi'] != "W" and !$_etukateen_maksettu)) {
          $apuqu = "SELECT *
                    FROM toimitustapa
                    WHERE yhtio  = '$kukarow[yhtio]'
                    AND nouto   != ''
                    ORDER BY jarjestys
                    LIMIT 1";
          $meapu = pupe_query($apuqu);
          $apuro = mysql_fetch_assoc($meapu);

          $toimitustapa = $apuro['selite'];

          echo "<font class='error'>".t("Toimitustapa on oltava nouto, koska maksuehto on käteinen")."!</font><br><br>";
        }

        if (empty($laskurow["kassalipas"])) {
          $kassalipas = $kukarow["kassamyyja"];
        }
        else {
          $kassalipas = $laskurow["kassalipas"];
        }
      }
    }

    if ($toimitustapa != $laskurow["toimitustapa"]) {
      $apuqu2 = "SELECT merahti
                 FROM toimitustapa
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND selite  = '$toimitustapa'";
      $meapu2 = pupe_query($apuqu2);
      $meapu2row = mysql_fetch_assoc($meapu2);

      if ($laskurow["rahtivapaa"] != "" and $meapu2row["merahti"] == "") {
        if ($kukarow["extranet"] == "") {
          echo "<font class='error'>".t("HUOM: Rahtivapaat tilaukset lähetetään aina lähettäjän rahtisopimuksella")."!</font><br><br>";
        }

        $meapu2row["merahti"] = "K";
      }

      if ($meapu2row["merahti"] != $laskurow["kohdistettu"]) {
        if ($kukarow["extranet"] == "") {
          echo "<font class='error'>".t("HUOM: Käytettävä rahtisopimus vaihdettiin")."!</font><br><br>";
        }

        $pika_paiv_merahti = " kohdistettu = '$meapu2row[merahti]', ";
      }

      $nollaa_lahto = " toimitustavan_lahto = 0, ";
    }
    elseif ($laskurow["rahtivapaa"] != "" and $laskurow["kohdistettu"] == "") {
      if ($kukarow["extranet"] == "") {
        echo "<font class='error'>".t("HUOM: Rahtivapaat tilaukset lähetetään aina lähettäjän rahtisopimuksella")."!</font><br><br>";
      }

      $pika_paiv_merahti = " kohdistettu = 'K', ";
    }

    $asiakkaan_tilaunumero_lisa = "";

    if ((isset($asiakkaan_tilausnumero) and $asiakkaan_tilausnumero != $laskurow["asiakkaan_tilausnumero"])) {
      $asiakkaan_tilaunumero_lisa = "asiakkaan_tilausnumero = '$asiakkaan_tilausnumero',";
    }

    $query  = "UPDATE lasku SET
               toimitustapa    = '$toimitustapa',
               rahtisopimus    = '$rahtisopimus',
               viesti          = '$viesti',
               tilausvahvistus = '$tilausvahvistus',
               $asiakkaan_tilaunumero_lisa
               $pika_paiv_merahti
               $pika_paiv_myyja
               $nollaa_lahto
               kassalipas      = '$kassalipas',
               maksuehto       = '$laskurow[maksuehto]'
               WHERE yhtio     = '$kukarow[yhtio]'
               and tunnus      = '$kukarow[kesken]'";
    pupe_query($query);

    //Haetaan laskurow uudestaan
    if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO" or $toim == "SIIRTOTYOMAARAYS" )) {
      $query  = "SELECT laskun_lisatiedot.*, lasku.*, tyomaarays.*
                 FROM lasku
                 JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio AND tyomaarays.otunnus = lasku.tunnus)
                 LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                 WHERE lasku.tunnus  = '$kukarow[kesken]'
                 AND lasku.yhtio     = '$kukarow[yhtio]'
                 AND lasku.tila     != 'D'";
    }
    else {
      // pitää olla: siirtolista, sisäinen työmääräys, reklamaatio, tarjous, valmistus, myyntitilaus, ennakko, myyntitilaus, ylläpitosopimus, projekti
      $query   = "SELECT laskun_lisatiedot.*, lasku.*
                  FROM lasku
                  LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                  WHERE lasku.tunnus = '$kukarow[kesken]'
                  AND lasku.yhtio    = '$kukarow[yhtio]'
                  AND lasku.tila     in ('G','S','C','T','V','N','E','L','0','R')
                  AND (lasku.alatila != 'X' or lasku.tila = '0')";
    }
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      echo "<br><br><br>".t("VIRHE: Tilaustasi ei löydy tai se on mitätöity/laskutettu")."! ($kukarow[kesken])<br><br><br>";

      $query = "UPDATE kuka
                SET kesken = 0
                WHERE yhtio = '$kukarow[yhtio]'
                AND kuka    = '$kukarow[kuka]'";
      $result = pupe_query($query);
      exit;
    }

    $laskurow = mysql_fetch_assoc($result);

    // Päivitetään rahtikirjatiedot jos ne on syötetty
    if ($laskurow["alatila"] == "B" or $laskurow["alatila"] == "D" or $laskurow["alatila"] == "J" or $laskurow["alatila"] == "E") {
      $query4 = "UPDATE rahtikirjat
                 SET toimitustapa = '$laskurow[toimitustapa]',
                 merahti        = '$laskurow[kohdistettu]'
                 where yhtio    = '$kukarow[yhtio]'
                 and otsikkonro = '$kukarow[kesken]'
                 and tulostettu = '0000-00-00 00:00:00'";
      $result = pupe_query($query4);
    }
  }

  if ((int) $lead != 0) {
    $query  = "UPDATE kalenteri SET
               otunnus     = 0
               WHERE yhtio = '$kukarow[yhtio]'
               and tyyppi  = 'Lead'
               and otunnus = '$kukarow[kesken]'";
    $result = pupe_query($query);

    if ((int) $lead > 0) {
      $query  = "UPDATE kalenteri SET
                 otunnus     = '$kukarow[kesken]'
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tyyppi  = 'Lead'
                 and tunnus  = '$lead'";
      $result = pupe_query($query);
    }
  }

  // jos asiakasnumero on annettu
  if ($laskurow["liitostunnus"] <> 0) {
    if ($yhtiorow["tilauksen_jarjestys"] == "M" and in_array($toim, array("TARJOUS", "EXTTARJOUS", "PIKATILAUS", "RIVISYOTTO", "VALMISTAASIAKKAALLE", "SIIRTOLISTA", "TYOMAARAYS", "TYOMAARAYS_ASENTAJA", "REKLAMAATIO", "PROJEKTI"))) {
      $jarjlisa = "<td class='back' style='width:10px; padding:0px; margin:0px;'></td>";
    }
    else {
      $jarjlisa = "";
    }

    if ($kukarow["extranet"] == "") {
      echo "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value='$mista'>
          <input type='hidden' name='tee' value='OTSIK'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='tiedot_laskulta' value='YES'>
          <input type='hidden' name='asiakasid' value='$laskurow[liitostunnus]'>
          <input type='hidden' name='tyojono' value='$tyojono'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
          <input type='submit' ACCESSKEY='m' value='".t("Muuta Otsikkoa")."'>
          </form>";

      if (($toim == 'PIKATILAUS' or $toim == 'RIVISYOTTO') and tarkista_oikeus('tilaus_myynti.php', 'PIKATILAUS') and tarkista_oikeus('tilaus_myynti.php', 'RIVISYOTTO')) {
        if ($toim == 'PIKATILAUS') {
          $vaihdatoim = 'RIVISYOTTO';
          $vaihdaselite = t("Rivisyöttöön");
        }
        else {
          $vaihdatoim = 'PIKATILAUS';
          $vaihdaselite = t("Pikatilaukseen");
        }

        echo "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
            <input type='hidden' name='toim' value='$vaihdatoim'>
            <input type='hidden' name='tilausnumero' value='$tilausnumero'>
            <input type='hidden' name='mista' value='$mista'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
            <input type='hidden' name='projektilla' value='$projektilla'>
            <input type='hidden' name='orig_tila' value='$orig_tila'>
            <input type='hidden' name='orig_alatila' value='$orig_alatila'>
            <input type='submit' value='".t("Vaihda")." $vaihdaselite'>
            </form>";
      }
    }

    // otetaan maksuehto selville.. jaksotus muuttaa asioita
    $query = "SELECT *
              from maksuehto
              where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result)==1) {
      $maksuehtorow = mysql_fetch_assoc($result);

      if ($maksuehtorow['jaksotettu'] != '' and $kukarow["extranet"] == "") {
        echo "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
            <input type='hidden' name='tilausnumero' value='$tilausnumero'>
            <input type='hidden' name='mista' value='$mista'>
            <input type='hidden' name='tee' value='MAKSUSOPIMUS'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
            <input type='hidden' name='projektilla' value='$projektilla'>
            <input type='hidden' name='orig_tila' value='$orig_tila'>
            <input type='hidden' name='orig_alatila' value='$orig_alatila'>
            <input type='submit' value='".t("Maksusuunnitelma")."'>
            </form>";
      }
    }

    //  Tämä koko toiminto pitänee taklata paremmin esim. perheillä..
    if (file_exists("lisaa_kulut.inc")) {
      echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value='$mista'>
          <input type='hidden' name='tee' value='LISAAKULUT'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
          <input type='submit' value='".t("Lisaa kulut")."'>
          </form>";
    }

    if (tarkista_oikeus('tuote_selaus_haku.php')) {
      echo "<form action='tuote_selaus_haku.php' method='post'>
          <input type='hidden' name='toim_kutsu' value='$toim'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='tyojono' value='$tyojono'>
          <input type='hidden' name='orig_tila' value = '$orig_tila'>
          <input type='hidden' name='orig_alatila' value = '$orig_alatila'>
          <input type='submit' value='".t("Selaa tuotteita")."'>
          </form>";
    }

    // aivan karseeta, mutta joskus pitää olla näin asiakasystävällinen... toivottavasti ei häiritse ketään
    if ($kukarow["extranet"] == "" and ($kukarow["yhtio"] == "artr" or $kukarow['yhtio'] == 'orum')) {
      echo "<form action='../yhteensopivuus.php' method='post'>
          <input type='hidden' name='toim_kutsu' value='$toim'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
          <input type='submit' value='".t("Malliselain")."'>
          </form>";
    }

    if ($kukarow["extranet"] == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == 'REKLAMAATIO') and isset($sms_palvelin) and $sms_palvelin != "" and isset($sms_user)  and $sms_user != "" and isset($sms_pass)  and $sms_pass != "") {
      echo "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value='$mista'>
          <input type='hidden' name='tila' value='SYOTASMS'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='tyojono' value='$tyojono'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
          <input type='submit' value='".t("Lähetä tekstiviesti")."'>
          </form>";
    }

    echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
        <input type='hidden' name='tee' value='mikrotila'>
        <input type='hidden' name='tilausnumero' value='$tilausnumero'>
        <input type='hidden' name='mista' value='$mista'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
        <input type='hidden' name='tyojono' value='$tyojono'>
        <input type='hidden' name='projektilla' value='$projektilla'>
        <input type='hidden' name='orig_tila' value='$orig_tila'>
        <input type='hidden' name='orig_alatila' value='$orig_alatila'>
        <input type='hidden' name='lopetus' value='$tilmyy_lopetus'>";

    if ($toim != "VALMISTAVARASTOON") {
      echo "<input type='submit' value='".t("Lue tilausrivit tiedostosta")."'>";
    }
    else {
      echo "<input type='submit' value='".t("Lue valmistusrivit tiedostosta")."'>";
    }

    echo "</form>";

    if ($kukarow["extranet"] == "" and ($toim == "PIKATILAUS" or $toim == "RIVISYOTTO") and !empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and $laskurow["tila"] != 'G') {
      echo "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='jalkitoimita'>
            <input type='hidden' name='toim'       value = '$toim'>
            <input type='hidden' name='lopetus'     value = '$lopetus'>
            <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
            <input type='hidden' name='projektilla'   value = '$projektilla'>
            <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
            <input type='hidden' name='mista'       value = '$mista'>
            <input type='hidden' name='menutila'     value = '$menutila'>
            <input type='hidden' name='orig_tila'     value = '$orig_tila'>
            <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
            <input type='hidden' name='tila'       value = 'MUUTAKAIKKI'>
            <input type='submit' value='" . t("Muuta kaikki tilauksen rivit JT-muiden mukana") . "' onclick='return nappi_onclick_confirm(\"".t('Haluatko muuttaa kaikki tilausrivit jt-muiden mukana')."?\");' >
          </form> ";
    }

    if ($kukarow["extranet"] == "" and in_array($toim, array("PIKATILAUS", "RIVISYOTTO", "TARJOUS")) and file_exists($pupe_root_polku . '/tilauskasittely/varaosaselain_napit.inc')) {
      require_once 'tilauskasittely/varaosaselain_napit.inc';
    }

    if ($kukarow["extranet"] == "" and ($toim == "PIKATILAUS" or $toim == "RIVISYOTTO") and $yhtiorow["rahtikirjojen_esisyotto"] == "M") {
      echo "<form action='../rahtikirja.php' method='post'>
          <input type='hidden' name='tee' value=''>
          <input type='hidden' name='toim' value='lisaa'>
          <input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=LASKUTATILAUS'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='rahtikirjan_esisyotto' value='$toim'>
          <input type='hidden' name='id' value='$tilausnumero'>
          <input type='hidden' name='mista' value='$mista'>
          <input type='hidden' name='rakirno' value='$tilausnumero'>
          <input type='hidden' name='tunnukset' value='$tilausnumero'>
          <input type='submit' value='".t("Rahtikirjan esisyöttö")."'>
          </form>";
    }

    if ($yhtiorow["myyntitilauksen_liitteet"] != "" and tarkista_oikeus('yllapito.php', 'liitetiedostot')) {
      if ($laskurow["tunnusnippu"] > 0) {
        $id = $laskurow["tunnusnippu"];
      }
      else {
        $id = $laskurow["tunnus"];
      }

      echo "<form method='POST' action='{$palvelin2}yllapito.php?toim=liitetiedostot&from=tilausmyynti&ohje=off&haku[7]=@lasku&haku[8]=@$id&lukitse_avaimeen=$id&lukitse_laji=lasku'>
          <input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=VALITSETOIMITUS//tyojono=$tyojono'>
          <input type='hidden' name='toim_kutsu' value='$toim'>
          <input type='hidden' name='tyojono' value='$tyojono'>
          <input type='submit' value='" . t('Tilauksen liitetiedostot')."'>
          </form>";
    }

    if ($kukarow["extranet"] == "" and $saako_liitaa_laskuja_tilaukseen == "") {
      //katsotaan onko sarjanumerolle liitetty kulukeikka
      $query  = "SELECT *
                 from lasku
                 where yhtio      = '$kukarow[yhtio]'
                 and tila         = 'K'
                 and alatila      = 'T'
                 and liitostunnus = '$laskurow[tunnus]'
                 and ytunnus      = '$laskurow[tunnus]'";
      $keikkares = pupe_query($query);

      unset($kulurow);
      unset($keikkarow);

      if (mysql_num_rows($keikkares) == 1) {
        $keikkarow = mysql_fetch_assoc($keikkares);
      }

      if (isset($keikkarow) and $keikkarow["tunnus"] > 0) {
        $keikkalisa = "<input type='hidden' name='otunnus' value='$keikkarow[tunnus]'>
                <input type='hidden' name='keikanalatila' value='T'>";
      }
      else {
        $keikkalisa = "<input type='hidden' name='luouusikeikka' value='OKMYYNTITILAUS'>
                <input type='hidden' name='liitostunnus' value='$tilausnumero'>";
      }

      if ($kukarow["extranet"] == "" and $yhtiorow["myytitilauksen_kululaskut"] == "K") {
        echo "<form method='POST' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
            <input type='hidden' name='tee' value='kululaskut'>
            <input type='hidden' name='toiminto' value='kululaskut'>
            $keikkalisa
            <input type='hidden' name='tilausnumero' value='$tilausnumero'>
            <input type='hidden' name='mista' value='$mista'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=VALITSETOIMITUS'>
            <input type='hidden' name='projektilla' value='$projektilla'>
            <input type='hidden' name='orig_tila' value='$orig_tila'>
            <input type='hidden' name='orig_alatila' value='$orig_alatila'>
            <input type='submit' value='" . t('Liitä kululasku')."'>
            </form>";
      }
    }

    // Katsotaan voidaanko lisätä tuotteet asiakashinnastoon/asiakasalennuksiin
    $_normaali_kayttaja     = ($kukarow["extranet"] == "");
    $_kayttooikeus          = (tarkista_oikeus("yllapito.php", "asiakashinta", "x") or tarkista_oikeus("yllapito.php", "asiakasalennus", "x"));
    $_tarjous               = ($toim == "TARJOUS" or $toim == "EXTTARJOUS" or $laskurow["tilaustyyppi"] == "T");
    $_asiakashintaparametri = (in_array($yhtiorow["myynti_asiakhin_tallenna"], array('K', 'V')));
    $_tarjous_tai_parametri = ($_tarjous or $_asiakashintaparametri);
    $_sallittu_toiminto     = (in_array($toim, array("TARJOUS", "EXTTARJOUS", "PIKATILAUS", "RIVISYOTTO", "VALMISTAASIAKKAALLE", "TYOMAARAYS", "PROJEKTI")));

    if ($_normaali_kayttaja and $_kayttooikeus and $_tarjous_tai_parametri and $_sallittu_toiminto) {
      echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='valitse_tuotteetasiakashinnastoon' value='x'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value='$mista'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>";
      echo "<input type='submit' value='".t("Siirrä tuotteet asiakashinnoiksi")."'>";
      echo "</form>";
    }

    if ($kukarow["extranet"] == "" and (($toim == "TARJOUS" or $toim == "EXTTARJOUS") or $laskurow["tilaustyyppi"] == "T") and file_exists("osamaksusoppari.inc")) {
      echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='tee' value='osamaksusoppari'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value='$mista'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
          <input type='submit' value='".t("Rahoituslaskelma")."'>
          </form>";
    }

    if ($kukarow["extranet"] == "" and (($toim == "TARJOUS" or $toim == "EXTTARJOUS") or $laskurow["tilaustyyppi"] == "T") and file_exists("vakuutushakemus.inc")) {
      echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='tee' value='vakuutushakemus'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value='$mista'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
          <input type='submit' value='".t("Vakuutushakemus/Rekisteri-ilmoitus")."'>
          </form>";
    }

    // JT-rivit näytetään vain jos siihen on oikeus!
    if (tarkista_oikeus('jtselaus.php')) {

      pupeslave_start();

      if ($yhtiorow["varaako_jt_saldoa"] != "") {
        $lisavarattu = " + tilausrivi.varattu";
      }
      else {
        $lisavarattu = "";
      }

      $query  = "SELECT count(*) kpl
                 from tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
                 JOIN lasku USE INDEX (primary) ON (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and lasku.liitostunnus='$laskurow[liitostunnus]')
                 WHERE tilausrivi.yhtio     = '$kukarow[yhtio]'
                 and tilausrivi.tyyppi      in ('L','G')
                 and tilausrivi.var         = 'J'
                 and tilausrivi.keratty     = ''
                 and tilausrivi.uusiotunnus = 0
                 and tilausrivi.kpl         = 0
                 and tilausrivi.jt $lisavarattu  > 0";
      $jtapuresult = pupe_query($query);
      $jtapurow = mysql_fetch_assoc($jtapuresult);

      if ($jtapurow["kpl"] > 0) {
        echo "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
            <input type='hidden' name='projektilla' value='$projektilla'>
            <input type='hidden' name='tyojono' value='$tyojono'>
            <input type='hidden' name='tilausnumero' value='$tilausnumero'>
            <input type='hidden' name='mista' value='$mista'>
            <input type='hidden' name='orig_tila' value='$orig_tila'>
            <input type='hidden' name='orig_alatila' value='$orig_alatila'>";

        if (!isset($jt_kayttoliittyma) and $kukarow['extranet'] != '') {
          $jt_kayttoliittyma = 'kylla';
        }

        if ($jt_kayttoliittyma == "kylla") {
          echo "  <input type='hidden' name='jt_kayttoliittyma' value=''>
              <input type='submit' value='".t("Piilota JT-rivit")."'>";
        }
        else {
          echo "  <input type='hidden' name='jt_kayttoliittyma' value='kylla'>
              <input type='submit' value='".t("Näytä JT-rivit")."'>";
        }
        echo "</form>";
      }

      pupeslave_stop();
    }

    // aivan karseeta, mutta joskus pitää olla näin asiakasystävällinen... toivottavasti ei häiritse ketään
    if ($kukarow["extranet"] == "" and $kukarow["yhtio"] == "allr") {

      echo "<br>
          <form action='../yhteensopivuus.php' method='post'>
          <input type='hidden' name='toim' value='MP'>
          <input type='hidden' name='toim_kutsu' value='$toim'>
          <input type='submit' value='".t("MP-Selain")."'>
          </form>
          <form action='../yhteensopivuus.php' method='post'>
          <input type='hidden' name='toim' value='MO'>
          <input type='hidden' name='toim_kutsu' value='$toim'>
          <input type='submit' value='".t("Moposelain")."'>
          </form>
          <form action='../yhteensopivuus.php' method='post'>
          <input type='hidden' name='toim' value='MK'>
          <input type='hidden' name='toim_kutsu' value='$toim'>
          <input type='submit' value='".t("Kelkkaselain")."'>
          </form>
          <form action='../yhteensopivuus.php' method='post'>
          <input type='hidden' name='toim' value='MX'>
          <input type='hidden' name='toim_kutsu' value='$toim'>
          <input type='submit' value='".t("Crossiselain")."'>
          </form>
          <form action='../yhteensopivuus.php' method='post'>
          <input type='hidden' name='toim' value='AT'>
          <input type='hidden' name='toim_kutsu' value='$toim'>
          <input type='submit' value='".t("ATV-Selain")."'>
          </form>";
    }

    if ($kukarow["extranet"] == "" and $yhtiorow["konserni"] == "makia") {

      echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='tee' value='mikrotila_matriisi'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value='$mista'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='tyojono' value='$tyojono'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>";
      echo "<input type='submit' value='".t("Lue tuotematriisi")."'>";
      echo "</form>";
    }

    if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "REKLAMAATIO") {

      if (tarkista_oikeus("tyojono.php")) {

        if ($toim == 'TYOMAARAYS' or $toim == 'REKLAMAATIO') {
          $toim2 = '';
        }
        else {
          $toim2 = $toim;
        }

        echo "<form method='POST' action='{$palvelin2}tyomaarays/tyojono.php'>
            <input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=VALITSETOIMITUS//tyojono=$tyojono'>
            <input type='hidden' name='toim' value='$toim2'>
            <input type='hidden' name='indexvas' value='1'>
            <input type='hidden' name='tyojono' value='$tyojono'>
            <input type='submit' value='" . t('Työjono')."'>
            </form>";

        // Jos työjono on tyhjä niin otetaan se otsikolta
        if (!isset($tyojono) or $tyojono == "") {
          $tyojono_url = $laskurow["tyojono"];
        }
        else {
          $tyojono_url = $tyojono;
        }

        echo "<form method='POST' action='{$palvelin2}tyomaarays/asennuskalenteri.php?liitostunnus=$tilausnumero&tyojono=$tyojono_url#".date("j_n_Y")."'>
            <input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=VALITSETOIMITUS//tyojono=$tyojono'>
            <input type='hidden' name='toim' value='$toim2'>
            <input type='submit' value='" . t('Asennuskalenteri')."'>
            </form>";
      }

      if (tarkista_oikeus("tyom_tuntiraportti.php")) {
        echo "<form method='POST' action='{$palvelin2}raportit/tyom_tuntiraportti.php'>
            <input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=VALITSETOIMITUS//tyojono=$tyojono'>
            <input type='hidden' name='tee' value='raportoi'>
            <input type='hidden' name='tyojono' value='$tyojono'>
            <input type='hidden' name='asiakasid' value='$laskurow[liitostunnus]'>
            <input type='hidden' name='tyom_nro' value='$laskurow[tunnus]'>
            <input type='hidden' name='vva' value='".substr($laskurow['luontiaika'], 0, 4)."'>
            <input type='hidden' name='kka' value='".substr($laskurow['luontiaika'], 5, 2)."'>
            <input type='hidden' name='ppa' value='".substr($laskurow['luontiaika'], 8, 2)."'>
            <input type='hidden' name='vvl' value='".substr($laskurow['luontiaika'], 0, 4)."'>
            <input type='hidden' name='kkl' value='".substr($laskurow['luontiaika'], 5, 2)."'>
            <input type='hidden' name='ppl' value='".substr($laskurow['luontiaika'], 8, 2)."'>
            <input type='submit' value='" . t('Tuntiraportti')."'>
            </form>";
      }
    }

    //  Tarkistetaan, ettei asiakas ole prospekti, tarjoukselle voi liittää prospektiasiakkaan, josta voi tehdä suoraan tilauksen. Herjataan siis jos asiakas pitää päivittää ja tarkistaa
    if (($toim != "TARJOUS" and $toim != "EXTTARJOUS")) {
      if ($asiakasrow['laji'] == "R") {
        $asiakasOnProspekti = "JOO";
        echo "<br><font class='error'>".t("HUOM: Asiakas on prospektiasiakas, tarkista asiakasrekisterissä asiakkaan tiedot ja päivitä tiedot tilauksen otsikolle.")."</font>";
      }
    }

    echo "<br><br>\n";
  }

  //Oletetaan, että tilaus on ok, $tilausok muuttujaa summataan alempana jos jotain virheitä ilmenee
  $tilausok = 0;
  $sarjapuuttuu = 0;

  $apuqu = "SELECT * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
  $meapu = pupe_query($apuqu);
  $meapurow = mysql_fetch_assoc($meapu);

  if ($laskurow["liitostunnus"] != 0 and $meapurow["kateinen"] == "" and ($laskurow["laskutus_nimi"] == '' or $laskurow["laskutus_osoite"] == '' or $laskurow["laskutus_postino"] == '' or $laskurow["laskutus_postitp"] == '')) {
    if ($toim != 'VALMISTAVARASTOON' and $toim != 'SIIRTOLISTA' and $toim != 'SIIRTOTYOMAARAYS' and ($toim != "TARJOUS" and $toim != "EXTTARJOUS")) {
      echo "<font class='error'>".t("VIRHE: Tilauksen laskutusosoitteen tiedot ovat puutteelliset")."!</font><br><br>";
      $tilausok++;
    }
  }

  $_tm_saldoaikalisa = "";

  if (!empty($yhtiorow["saldo_kasittely"])) {
    if ($laskurow["kerayspvm"] != '0000-00-00') {
      $_tm_saldoaikalisa = $laskurow["kerayspvm"];
    }
    else {
      $_tm_saldoaikalisa = date("Y-m-d");
    }
  }

  if ($kukarow['extranet'] == '' and ($laskurow["liitostunnus"] != 0 or ($laskurow["liitostunnus"] == 0 and $kukarow["kesken"] > 0 and $toim != "PIKATILAUS"))) {

    echo "<form id='hae_asiakasta_formi' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>";
    echo "  <input type='hidden' name='tilausnumero' value='{$tilausnumero}'>
        <input type='hidden' name='mista' value='{$mista}'>
        <input type='hidden' name='toim' value='{$toim}'>
        <input type='hidden' name='lopetus' value='{$lopetus}'>
        <input type='hidden' name='ruutulimit' value = '{$ruutulimit}'>
        <input type='hidden' name='projektilla' value='{$projektilla}'>
        <input type='hidden' name='orig_tila' value='{$orig_tila}'>
        <input type='hidden' name='orig_alatila' value='{$orig_alatila}'>
        <input type='hidden' name='yhtiotoimipaikka' value='{$laskurow['yhtio_toimipaikka']}' />
        <input type='hidden' name='tilaustyyppi' value='{$laskurow['tilaustyyppi']}' />
        <input type='hidden' id='syotetty_ytunnus' name='syotetty_ytunnus' value=''>
        <input type='hidden' id='hae_asiakasta_hv_hidden' name='hintojen_vaihto' value='$hintojen_vaihto'>";
    echo "</form>";
  }

  // tässä alotellaan koko formi.. tämä pitää kirjottaa aina
  echo "<form name='tilaus' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' autocomplete='off'>
      <input type='hidden' id='tilausnumero' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='mista' value='$mista'>
      <input type='hidden' id='toim' name='toim' value='$toim'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
      <input type='hidden' name='tilaustyyppi' value='{$tilaustyyppi}'>
      <input type='hidden' name='projektilla' value='$projektilla'>
      <input type='hidden' name='orig_tila' value='$orig_tila'>
      <input type='hidden' name='orig_alatila' value='$orig_alatila'>
      <input type='hidden' name='tilausrivi_alvillisuus' value='$tilausrivi_alvillisuus'>";

  // kirjoitellaan otsikko
  echo "<table>";

  $myyntikielto = '';

  // jos asiakasnumero on annettu
  if ($laskurow["liitostunnus"] != 0 or ($laskurow["liitostunnus"] == 0 and $kukarow["kesken"] > 0 and $toim != "PIKATILAUS")) {

    // KAUTTALASKUTUSKIKKARE
    if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and ($koti_yhtio != $kukarow['yhtio'] or $asiakasrow['osasto'] != '6')) {
      $GLOBALS['eta_yhtio'] = "";
    }

    echo "<tr>$jarjlisa";

    if ($toim == "VALMISTAVARASTOON") {
      echo "<th align='left'>".t("Varastot").":</th>";
      echo "<td>$laskurow[ytunnus] $laskurow[nimi]</td>";
      echo "<th>".t("Toimituspäivä").":</td>";
      echo "<td>";
      echo tv1dateconv($laskurow["toimaika"]);
      echo "</td>";

      $query = "SELECT *
                FROM toimitustapa
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = 0";
      $tresult = pupe_query($query);
      $tm_toimitustaparow = mysql_fetch_assoc($tresult);
    }
    else {

      echo "<th>".t("Ytunnus");

      if ($asiakasrow["asiakasnro"] != "") {
        echo " / ".t("Asiakasnro");
      }

      echo ":</th><td>";

      if ($laskurow["liitostunnus"] == 0) {
        echo "<input type='hidden' name='orig_tila' value='$orig_tila'>";
        echo "<input type='hidden' name='orig_alatila' value='$orig_alatila'>";
        echo "<input type='submit' name='liitaasiakasnappi' value='".t("Liitä asiakas")."'>";
      }
      else {
        echo "<a href='{$palvelin2}raportit/asiakkaantilaukset.php?toim=MYYNTI&ytunnus={$laskurow['ytunnus']}&asiakasid={$laskurow['liitostunnus']}&lopetus={$tilmyy_lopetus}'>", tarkistahetu($laskurow['ytunnus']), "</a>";

        if ($asiakasrow["asiakasnro"] != "") {
          echo " / $asiakasrow[asiakasnro]";
        }

        if ($asiakasrow["ryhma"] != "") {
          echo " / {$asiakasrow['ryhma']}";
        }
      }

      echo "</td>";
      echo "<th>".t("Toimituspäivä").":</td>";
      echo "<td>";
      echo tv1dateconv($laskurow["toimaika"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr>{$jarjlisa}";
      echo "<th align='left'>", t("Asiakas"), ":</th>";

      echo "<td>";
      echo "<span id='hae_asiakasta_spani'>";

      if ($kukarow["extranet"] == "" and $laskurow["tila"] != "G" and $laskurow["tila"] != "S") {
        echo "<a href='{$palvelin2}crm/asiakasmemo.php?ytunnus={$laskurow['ytunnus']}&asiakasid={$laskurow['liitostunnus']}&from={$toim}&lopetus={$tilmyy_lopetus}//from=LASKUTATILAUS'>{$laskurow['nimi']}</a>";
        echo " <a id='hae_asiakasta_linkki'>";

        if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
          echo "<img src='".$palvelin2."pics/facelift/ukot.png'>";
        }
        else {
          echo "<img src='".$palvelin2."pics/lullacons/user-multiple.png'>";
        }

        echo "</a>";
      }
      else {
        echo "{$laskurow['nimi']}";
      }

      if ($laskurow["toim_nimi"] != $laskurow["nimi"]) {
        echo "<br>{$laskurow['toim_nimi']}";
      }

      if ($kukarow['extranet'] == "" and $yhtiorow['konserni'] == "indu") {
        echo "<br />";
        echo hae_chn_teksti($asiakasrow['chn']);
        if ($asiakasrow['verkkotunnus'] != '') echo " / {$asiakasrow['verkkotunnus']}";
      }

      echo "</span>";

      if ($kukarow["extranet"] == "") {

        echo "<span id='hae_asiakasta_piilospan' style='display:none'>";
        echo "<input type='text' name='hae_asiakasta_boksi' id='hae_asiakasta_boksi' value='' /> ";
        echo "<input type='button' name='hae_asiakasta_boksi_button' id='hae_asiakasta_boksi_button' value='", t("Vaihda asiakas"), "'>";
        echo "<div style='text-align:right;'>";
        echo "<span id='hae_asiakasta_hintavaihto_txt' style='position:relative; top:1px;'>" . t("Asiakashinnat ja -alennukset lasketaan uudestaan") . "</span>";
        echo "<input type='checkbox' id='hae_asiakasta_hintavaihto_cb' name='hintojen_vaihto' value='JOO' CHECKED />";
        echo "</div>";
        echo "</span>";
      }

      echo "</td>";

      echo "<th align='left'>".t("Toimitustapa").":</th>";

      // Lukitaan rahtikirjaan vaikuttavat tiedot jos/kun rahtikirja on tulostettu
      $query = "SELECT *
                FROM rahtikirjat
                WHERE yhtio     = '$kukarow[yhtio]'
                AND otsikkonro  = '$kukarow[kesken]'
                AND tulostettu != '0000-00-00 00:00:00'
                LIMIT 1";
      $rakre_chkres = pupe_query($query);

      $state_chk = "";

      if (mysql_num_rows($rakre_chkres) > 0) {
        $state_chk = 'disabled';
      }

      $_varasto = hae_varasto($laskurow['varasto']);

      $params = array(
        'asiakas_tunnus'      => $laskurow['liitostunnus'],
        'lasku_toimipaikka'   => $laskurow['yhtio_toimipaikka'],
        'varasto_toimipaikka' => $_varasto['toimipaikka'],
        'kohdevarasto'        => $laskurow['clearing'],
        'lahdevarasto'        => $laskurow['varasto'],
      );

      $toimitustavat = hae_toimitustavat($params);

      if (count($toimitustavat) == 0) {
        echo t("VIRHE: Käyttäjätiedoissasi on virhe! Ota yhteys järjestelmän ylläpitäjään."), "<br><br>";
        exit;
      }

      echo "<td><select name='toimitustapa' onchange='submit()' {$state_chk} ".js_alasvetoMaxWidth("toimitustapa", 200).">";

      foreach ($toimitustavat as $toimitustapa) {

        if (!empty($toimitustapa['sallitut_maat']) and !stristr($toimitustapa['sallitut_maat'], $laskurow['toim_maa'])) {
          continue;
        }

        if (($kukarow['extranet'] == "" and in_array($toimitustapa['extranet'], array('', 'M')))
          or ($kukarow['extranet'] != "" and in_array($toimitustapa['extranet'], array('K', 'M')))
          or $toimitustapa['selite'] == $laskurow['toimitustapa']
          or $toimitustapa['selite'] == $asiakasrow['toimitustapa']) {

          $sel = "";
          if ($toimitustapa["selite"] == $laskurow["toimitustapa"]) {
            $sel = 'selected';
            $tm_toimitustaparow   = $toimitustapa;
            $toimitustavan_tunnus = $toimitustapa['tunnus'];
          }

          echo "<option id='toimitustapa_$toimitustapa[tunnus]' value='$toimitustapa[selite]' $sel>";
          echo t_tunnus_avainsanat($toimitustapa, "selite", "TOIMTAPAKV");
          echo "</option>";
        }
      }
      echo "</select>";

      // HUOM: jos varsinainen on disabloitu niin siirretään tieto hidddenissä
      if ($state_chk == 'disabled') {
        echo "<input type='hidden' name='toimitustapa' value='$laskurow[toimitustapa]'>";
      }

      if ($laskurow["rahtivapaa"] != "") {
        echo " (", t("Rahtivapaa"), ") ";
      }

      if ($kukarow["extranet"] == "") {

        // näytetään vain jos ollaan menossa asiakkaan sopparilla
        if ($laskurow["kohdistettu"] == "") {
          //etsitään löytyykö rahtisopimusta
          $rahsop = hae_rahtisopimusnumero($laskurow["toimitustapa"], $laskurow["ytunnus"], $laskurow["liitostunnus"], true, "");

          if (mysql_num_rows($rahsop) > 0) {
            echo " <select name='rahtisopimus' onchange='submit()' {$state_chk} ".js_alasvetoMaxWidth("rahtisopimus", 200).">";

            while ($rahsoprow = mysql_fetch_assoc($rahsop)) {
              $sel = "";
              if ($rahsoprow['rahtisopimus'] == $laskurow['rahtisopimus']) $sel = "SELECTED";

              echo "<option value='{$rahsoprow['rahtisopimus']}' $sel>{$rahsoprow['rahtisopimus']} {$rahsoprow['selite']}</option>";
            }

            echo "</select>";
          }

          echo " <a href='{$palvelin2}yllapito.php?toim=rahtisopimukset&uusi=1&ytunnus={$laskurow['ytunnus']}&toimitustapa={$laskurow['toimitustapa']}&lopetus={$tilmyy_lopetus}//from=LASKUTATILAUS'>".t("Uusi Rahtisopimus")."</a>";
        }
      }

      echo "</td>";
    }

    echo "</tr>";
    echo "<tr>{$jarjlisa}";
    echo "<th align='left'>", t("Tilausnumero"), ":</th>";

    if ($laskurow["tunnusnippu"] > 0) {

      echo "<td><select name='valitsetoimitus' onchange='submit();' ".js_alasvetoMaxWidth("valitsetoimitus", 250).">";

      // Listataan kaikki toimitukset ja liitetään tarjous mukaan jos se tiedetään
      $hakulisa = "";

      if ($projektilla > 0 and $laskurow["tunnusnippu"] != $projektilla) {
        $hakulisa =" or lasku.tunnusnippu = '$projektilla'";
      }

      //  Valmistuksissa ei anneta sotkea myyntiä!
      if ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
        $ptilat = "'V','W'";
      }
      else {
        $ptilat = "'L','N','A','T','G','S','O','R','E'";
      }

      $vquery = " SELECT count(*) from lasku l where l.yhtio=lasku.yhtio and l.tunnusnippu=lasku.tunnusnippu and l.tunnus<=lasku.tunnus and l.tila='T'";

      $query = "SELECT lasku.tila, lasku.alatila, varastopaikat.nimitys varasto, lasku.toimaika,
                if (lasku.tila='T',if (lasku.tunnusnippu>0,concat(lasku.tunnusnippu,'/',($vquery)), concat(lasku.tunnusnippu,'/1')),lasku.tunnus) tilaus,
                lasku.tunnus tunnus,
                lasku.tilaustyyppi
                FROM lasku
                LEFT JOIN varastopaikat ON varastopaikat.yhtio = lasku.yhtio and varastopaikat.tunnus = lasku.varasto
                WHERE lasku.yhtio = '$kukarow[yhtio]'
                and (lasku.tunnusnippu = '$laskurow[tunnusnippu]' $hakulisa)
                and lasku.tila    IN ($ptilat)
                and if ('$tila' = 'MUUTA', alatila != 'X', lasku.tunnus=lasku.tunnus)
                GROUP BY lasku.tunnus";
      $toimres = pupe_query($query);

      if (mysql_num_rows($toimres) > 0) {

        while ($row = mysql_fetch_assoc($toimres)) {

          $sel = "";
          if ($row["tunnus"] == $kukarow["kesken"]) {
            $sel = "selected";
          }

          $laskutyyppi = $row["tila"];
          $alatila    = $row["alatila"];

          require "inc/laskutyyppi.inc";

          $tarkenne = " ";

          if ($row["tila"] == "V" and $row["tilaustyyppi"] == "V") {
            $tarkenne = " (".t("Asiakkaalle").") ";
          }
          elseif ($row["tila"] == "V" and  $row["tilaustyyppi"] == "W") {
            $tarkenne = " (".t("Varastoon").") ";
          }
          elseif (($row["tila"] == "N" or $row["tila"] == "L") and $row["tilaustyyppi"] == "R") {
            $tarkenne = " (".t("Reklamaatio").") ";
          }
          elseif (($row["tila"] == "N" or $row["tila"] == "L") and $row["tilaustyyppi"] == "A") {
            $laskutyyppi = "Työmääräys";
          }
          elseif ($row["tila"] == "N" and $row["tilaustyyppi"] == "E") {
            $laskutyyppi = "Ennakkotilaus kesken";
          }

          if ($row["alatila"] == "X") $disabled = "DISABLED";
          else $disabled = "";

          echo "<option value ='$row[tunnus]' $sel $disabled>".t("$laskutyyppi")." $tarkenne $row[tilaus] ".t("$alatila")." $row[varasto]</option>";
        }
      }
      echo "<optgroup label='".t("Perusta uusi")."'>";

      if (($toim == "TARJOUS" or $toim == "EXTTARJOUS") and $laskurow["alatila"] != "B") {
        echo "<option value='TARJOUS'>".t("Tarjouksen versio")."</option>";
      }
      else {
        if ($laskurow["tilaustyyppi"] == "E") {
          echo "<option value='ENNAKKO'>".t("Ennakkotilaus")."</option>";
        }
        elseif ($toim == "PIKATILAUS") {
          echo "<option value='PIKATILAUS'>".t("Toimitus")."</option>";
        }
        else {
          echo "<option value='RIVISYOTTO'>".t("Toimitus")."</option>";
        }
      }

      echo "</optgroup></select>";
    }
    elseif (($yhtiorow["myyntitilaus_osatoimitus"] == "K" or ($yhtiorow["myyntitilaus_osatoimitus"] == "T" and $laskurow['tila'] == 'L' and in_array($laskurow['alatila'], array('C','B')))) and ($toim == "RIVISYOTTO" or $toim == "PIKATILAUS" or $toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA")) {
      echo "<td><select name='luotunnusnippu' onchange='submit();'>";
      echo "<option value =''>$kukarow[kesken]</option>";
      echo "<option value ='$kukarow[kesken]'>".t("Tee osatoimitus")."</option>";
      echo "</select></td>";
    }
    else {
      echo "<td>$kukarow[kesken]</td>";
    }

    echo "<th>".t("Tilausviite").":</th><td>";
    echo "<input type='text' size='30' name='viesti' value='$laskurow[viesti]' $state><input type='submit' class='tallenna_btn' value='".t("Tallenna")."' $state></td></tr>\n";

    echo "<tr>$jarjlisa";

    if ($kukarow["extranet"] != "" and $kukarow["yhtio"] == 'orum') {
      echo "<th>&nbsp;</th>";
    }
    elseif ($toim != "SIIRTOTYOMAARAYS"  and $toim != "SIIRTOLISTA" and $toim != "VALMISTAVARASTOON") {
      echo "<th>".t("Tilausvahvistus").":</th>";
    }
    elseif (($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOLISTA") and $yhtiorow["varastosiirto_tilausvahvistus"] == "K") {
      echo "<th>".t("Siirtovahvistus").":</th>";
    }
    else {
      echo "<th>&nbsp;</th>";
    }

    if ($kukarow["extranet"] != "" and $kukarow["yhtio"] == 'orum') {
      echo "<td><input type='hidden' name='tilausvahvistus' value='$laskurow[tilausvahvistus]'>&nbsp;</td>";
    }
    elseif ($toim != "SIIRTOTYOMAARAYS"  and $toim != "SIIRTOLISTA" and $toim != "VALMISTAVARASTOON") {
      $extralisa = "";

      if ($kukarow["extranet"] != "") {
        $extralisa .= " and (avainsana.selite like '%S%' or avainsana.selite like '%O%') ";

        if ($kukarow['hinnat'] == 1) {
          $extralisa .= " and avainsana.selite not like '1%' ";
        }
      }

      $tresult = t_avainsana("TV", "", $extralisa);

      echo "<td><select name='tilausvahvistus' onchange='submit();' ".js_alasvetoMaxWidth("tilausvahvistus", 250)." $state>";
      echo "<option value=' '>".t("Ei Vahvistusta")."</option>";

      while ($row = mysql_fetch_assoc($tresult)) {
        $sel = "";
        if ($row["selite"]== $laskurow["tilausvahvistus"]) $sel = 'selected';
        echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
      }
      echo "</select></td>";

    }
    elseif (($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOLISTA") and $yhtiorow["varastosiirto_tilausvahvistus"] == "K") {
      echo "<td>".t("Kyllä")."</td>";
    }
    else {
      echo "<td>&nbsp;</td>";
    }

    if ($kukarow["extranet"] == "") {
      if ($toim != "VALMISTAVARASTOON") {
        echo "<th align='left'>".t("Myyjänro").":</th>";
      }
      else {
        echo "<th align='left'>".t("Laatija").":</th>";
      }

      $query = "(SELECT kuka.tunnus, kuka.kuka, kuka.nimi, kuka.myyja, kuka.asema
                 FROM kuka
                 WHERE kuka.yhtio    = '$kukarow[yhtio]'
                 AND kuka.tunnus     = '$laskurow[myyja]')
                 UNION
                  (SELECT kuka.tunnus, kuka.kuka, kuka.nimi, kuka.myyja, kuka.asema
                 FROM kuka
                 WHERE kuka.yhtio    = '$kukarow[yhtio]'
                 AND kuka.aktiivinen = 1
                 AND kuka.extranet   = '')
                 ORDER BY nimi";
      $yresult = pupe_query($query);

      $myyjanumero = empty($myyjanro) ? $myyjanumero : $myyjanro;

      if ($yhtiorow['pikatilaus_focus'] == 'Y' and empty($myyja)) {
        $required = 'required';

        if (!loytyyko_myyja_tunnuksella($myyjanumero)) {
          $tuoteno = '';
          $kentta  = 'myyjanro';
        }
      }

      while ($row = mysql_fetch_assoc($yresult)) {
        $sel = "";
        if (empty($laskurow['myyja'])) {
          if ($row['nimi'] == $kukarow['nimi']) {
            $sel = 'selected';
          }
        }
        else {
          if ($row['tunnus'] == $laskurow['myyja']) {
            $sel = 'selected';
            $myyjanumero = $row["myyja"];
          }
        }
        $options .= "<option value='{$row["tunnus"]}' {$sel}>{$row["nimi"]}</option>";
      }

      echo "<td>
              <input type='hidden' name='v_myyjanro' value='$myyjanumero'>
              <input id='myyjanro_id' name='myyjanro' size='8' value='{$myyjanumero}' {$required}
                     {$state}> " . t("tai")." ";
      echo "<select id='myyja_id' name='myyja' {$state}>";
      echo $options;
      echo "</select></td></tr>";

      if (trim($asiakasrow["fakta"]) != "" and $toim != "SIIRTOTYOMAARAYS"  and $toim != "SIIRTOLISTA" and $toim != "VALMISTAVARASTOON") {
        echo "<tr>$jarjlisa<th>".t("Asiakasfakta").":</th><td colspan='3'>";
        echo "<font class='asiakasfakta'>".str_replace("\n", "<br>", $asiakasrow['fakta'])."</font>&nbsp;</td></tr>\n";
      }

      // Katsotaan onko liitetiedostoja
      $liitequery = "SELECT tunnus, selite
                     FROM liitetiedostot USE INDEX (yhtio_liitos_liitostunnus)
                     WHERE yhtio      = '$kukarow[yhtio]'
                     AND liitos       = 'lasku'
                     AND liitostunnus = '$laskurow[tunnus]'";
      $liiteres = pupe_query($liitequery);

      if (mysql_num_rows($liiteres) > 0) {
        $liitemaara = 1;

        echo "<tr>$jarjlisa<th>".t("Liitetiedostot").":</th><td colspan='3'>";

        while ($liiterow = mysql_fetch_array($liiteres)) {
          echo js_openUrlNewWindow("{$palvelin2}view.php?id=$liiterow[tunnus]", t('Liite')." $liitemaara", NULL, 1000, 800)." $liiterow[selite]</a> ";
          $liitemaara++;
        }
        echo "</td></tr>";
      }

      if ($toim == 'TYOMAARAYS' or $toim == "TYOMAARAYS_ASENTAJA") {
        // Katsotaan onko kalenterimerkintöjä
        $query = "SELECT left(kalenteri.pvmalku, 10) pvmalku_sort,
                  kalenteri.pvmalku,
                  kalenteri.pvmloppu,
                  concat(left(kalenteri.pvmalku,16), '##', left(kalenteri.pvmloppu,16), '##', kuka.nimi, '##', kuka.kuka) asennuskalenteri
                  FROM  kalenteri
                  LEFT JOIN kuka ON kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
                  WHERE kalenteri.yhtio      = '$kukarow[yhtio]'
                  and kalenteri.tyyppi       = 'asennuskalenteri'
                  and kalenteri.liitostunnus = '$kukarow[kesken]'";
        $liiteres = pupe_query($query);

        if (mysql_num_rows($liiteres) > 0) {

          echo "<tr>$jarjlisa<th>".t("Asennustyöt").":</th><td colspan='3'>";

          $asekal_distinct_chk = array();

          while ($liiterow = mysql_fetch_array($liiteres)) {

            list($asekal_alku, $asekal_loppu, $asekal_nimi, $asekal_kuka) = explode("##", $liiterow["asennuskalenteri"]);

            $asekal_atstamp = mktime(substr($asekal_alku, 11, 2), substr($asekal_alku, 14, 2), 0, substr($asekal_alku, 5, 2), substr($asekal_alku, 8, 2), substr($asekal_alku, 0, 4));
            $asekal_ltstamp = mktime(substr($asekal_loppu, 11, 2), substr($asekal_loppu, 14, 2), 0, substr($asekal_loppu, 5, 2), substr($asekal_loppu, 8, 2), substr($asekal_loppu, 0, 4));

            $kaletunnit[$nimi] += ($ltstamp - $atstamp)/60;

            if ($toim == 'TYOMAARAYS' or $toim == "TYOMAARAYS_ASENTAJA") {

              if ($asekal_distinct_chk[$asekal_kuka][$laskurow['tunnus']] == $liiterow['pvmalku_sort'] and substr($asekal_alku, 5, 2).substr($asekal_alku, 8, 2).substr($asekal_alku, 0, 4) == substr($asekal_loppu, 5, 2).substr($asekal_loppu, 8, 2).substr($asekal_loppu, 0, 4)) {
                continue;
              }

              echo "$asekal_nimi: ".tv1dateconv($asekal_alku, "", "LYHYT");

              if ($kukarow['kuka'] == $asekal_kuka) {

                // to ADD or SUBSTRACT times NOTE that if you dont specify the UTC zone your result is the difference +- your server UTC delay.
                date_default_timezone_set('UTC');

                $query = "SELECT right(pvmalku, 8) pvmalku, right(pvmloppu, 8) pvmloppu
                          FROM kalenteri
                          WHERE yhtio  = '$kukarow[yhtio]'
                          AND kuka     = '$kukarow[kuka]'
                          AND kentta02 = '$laskurow[tunnus]'
                          AND pvmalku  like '".substr($asekal_alku, 0, 4)."-".substr($asekal_alku, 5, 2)."-".substr($asekal_alku, 8, 2)."%'
                          AND tyyppi   = 'kalenteri'";
                $tunti_chk_res = pupe_query($query);

                $tunnit = 0;
                $minuutit = 0;
                $tuntimaara = '';

                while ($tunti_chk_row = mysql_fetch_assoc($tunti_chk_res)) {
                  if (trim($tunti_chk_row['pvmalku']) != '' and trim($tunti_chk_row['pvmloppu']) != '') {
                    list($ah, $am, $as) = explode(":", $tunti_chk_row['pvmalku']);
                    list($lh, $lm, $ls) = explode(":", $tunti_chk_row['pvmloppu']);

                    list($temp_tunnit, $temp_minuutit) = explode(":", date("G:i", mktime($lh, $lm) - mktime($ah, $am)));

                    $tunnit += $temp_tunnit;
                    $minuutit += $temp_minuutit;
                  }
                }

                if ($tunnit != 0 or $minuutit != 0) {
                  $minuutit = $minuutit / 60;
                  $tuntimaara = " (".str_replace(".", ",", ($tunnit+$minuutit))."h)";
                }

                if ($tuntimaara != '') echo $tuntimaara;
              }

              if (substr($asekal_alku, 5, 2).substr($asekal_alku, 8, 2).substr($asekal_alku, 0, 4) != substr($asekal_loppu, 5, 2).substr($asekal_loppu, 8, 2).substr($asekal_loppu, 0, 4)) {
                echo " - ".tv1dateconv($asekal_loppu, "", "LYHYT");

                // to ADD or SUBSTRACT times NOTE that if you dont specify the UTC zone your result is the difference +- your server UTC delay.
                date_default_timezone_set('UTC');

                $query = "SELECT right(pvmalku, 8) pvmalku, right(pvmloppu, 8) pvmloppu
                          FROM kalenteri
                          WHERE yhtio  = '$kukarow[yhtio]'
                          AND kuka     = '$kukarow[kuka]'
                          AND kentta02 = '$laskurow[tunnus]'
                          AND pvmloppu like '".substr($asekal_loppu, 0, 4)."-".substr($asekal_loppu, 5, 2)."-".substr($asekal_loppu, 8, 2)."%'
                          AND tyyppi   = 'kalenteri'";
                $tunti_chk_res = pupe_query($query);

                $tunnit = 0;
                $minuutit = 0;
                $tuntimaara = '';

                while ($tunti_chk_row = mysql_fetch_assoc($tunti_chk_res)) {
                  if (trim($tunti_chk_row['pvmalku']) != '' and trim($tunti_chk_row['pvmloppu']) != '') {
                    list($ah, $am, $as) = explode(":", $tunti_chk_row['pvmalku']);
                    list($lh, $lm, $ls) = explode(":", $tunti_chk_row['pvmloppu']);

                    list($temp_tunnit, $temp_minuutit) = explode(":", date("G:i", mktime($lh, $lm) - mktime($ah, $am)));

                    $tunnit += $temp_tunnit;
                    $minuutit += $temp_minuutit;
                  }
                }

                if ($tunnit != 0 or $minuutit != 0) {
                  $minuutit = $minuutit / 60;
                  $tuntimaara = " (".str_replace(".", ",", ($tunnit+$minuutit))."h)";
                }

                if ($tuntimaara != '') echo $tuntimaara;
              }

              $asekal_distinct_chk[$asekal_kuka][$laskurow['tunnus']] = $liiterow['pvmalku_sort'];

              echo "<br>";
            }
            else {
              echo "$asekal_nimi: ".tv1dateconv($asekal_alku, "P")." - ".tv1dateconv($asekal_loppu, "P")."<br>";
            }
          }
          echo "</td></tr>";
        }
      }

      if (($toim == "TARJOUS" or $toim == "EXTTARJOUS")) {
        $kalequery = "SELECT yhteyshenkilo.nimi yhteyshenkilo, kuka1.nimi nimi1, kuka2.nimi nimi2, kalenteri.*
                      FROM kalenteri
                      LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.yhtio=kalenteri.yhtio and yhteyshenkilo.tyyppi = 'A'
                      LEFT JOIN kuka as kuka1 ON (kuka1.yhtio=kalenteri.yhtio and kuka1.kuka=kalenteri.kuka)
                      LEFT JOIN kuka as kuka2 ON (kuka2.yhtio=kalenteri.yhtio and kuka2.kuka=kalenteri.myyntipaallikko)
                      where kalenteri.liitostunnus = '$laskurow[liitostunnus]'
                      and (kalenteri.otunnus = 0 or kalenteri.otunnus = '$kukarow[kesken]')
                      and kalenteri.tyyppi         = 'Lead'
                      and kuittaus                 = 'K'
                      and kalenteri.yhtio          = '$kukarow[yhtio]'
                      and left(kalenteri.tyyppi,7) != 'DELETED'
                      ORDER BY kalenteri.pvmalku desc";
        $kaleresult = pupe_query($kalequery);

        if (mysql_num_rows($kaleresult) > 0) {
          echo "<tr>$jarjlisa<th>".t("Leadit").":</th><td colspan='3'>";
          echo "<select name='lead' onchange='submit();'>";
          echo "<option value='-1'>".t("Ei leadia")."</option>";

          while ($kalerow = mysql_fetch_assoc($kaleresult)) {

            $sel = "";
            if ($kalerow["otunnus"] == $kukarow["kesken"]) {
              $sel = "selected";
            }

            echo "<option value='$kalerow[tunnus]' $sel>".substr($kalerow["kentta01"], 0, 60)."</option>";
          }

          echo "</select></td></tr>";
        }
      }

      if ($asiakasrow["laji"] == "K" and $yhtiorow["yhtio"] == "artr") {
        echo "<tr>$jarjlisa<td class='back'></td>";
        echo "<td colspan='3' class='back'>";
        echo "<p class='error'>".t("HUOM: Tämä on korjaamo-asiakas, älä myy tälle asiakkaalle")."</p>";
        echo "</td></tr>";
      }
    }
    else {
      echo "<th>
              <label for='asiakkaan_tilausnumero'>" . t("Asiakkaan tilausnumero") . ":</label>
            </th>";
      echo "<td>
              <input type='text'
                     name='asiakkaan_tilausnumero'
                     id='asiakkaan_tilausnumero'
                     value='{$laskurow["asiakkaan_tilausnumero"]}'>

              <input type='submit' value='" . t("Tallenna") . "'>
            </td>";

      echo "<input type='hidden' size='30' name='myyja' value='$laskurow[myyja]'>";
      echo "</tr>";

      if ($yhtiorow["extranet_poikkeava_toimitusosoite"] == "Y") {
        $toim_eroaa = ($laskurow["nimi"] != $laskurow["toim_nimi"] or
          $laskurow["nimitark"] != $laskurow["toim_nimitark"] or
          $laskurow["osoite"] != $laskurow["toim_osoite"] or
          $laskurow["postitp"] != $laskurow["toim_postitp"] or
          $laskurow["postino"] != $laskurow["toim_postino"] or
          $laskurow["maa"] != $laskurow["toim_maa"]);

        if ($toim_eroaa) {
          $poikkeava_toimitusosoite = "Y";
        }

        $checked =
          (isset($poikkeava_toimitusosoite) and $poikkeava_toimitusosoite == "Y") ? "checked" : "";

        echo "<script>
                var handleCheckbox = function() {
                  checkBoxi = document.getElementById(\"toimCheck\");
                  if (checkBoxi.checked) {
                    document.getElementById(\"toimHidden\").disabled = true;
                    tilaus.submit();
                  } else {
                    if (confirm('" . t("Toimitusosoitteen tiedot poistetaan, oletko varma?") . "')) {
                      tilaus.submit();
                    } else {
                      checkBoxi.checked = true;
                    }
                  }
                };
              </script>";

        echo "<tr>
                <th>" . t("Poikkeava toimitusosoite") . "</th>
                <td>
                  <input id='toimHidden' type='hidden' name='poikkeava_toimitusosoite' value='N'>
                  <input id='toimCheck' type='checkbox'
                         name='poikkeava_toimitusosoite'
                         value='Y'
                         onclick='handleCheckbox();' {$checked}>
                </td>
              </tr>";
      }
    }
  }
  elseif ($kukarow["extranet"] == "") {
    // asiakasnumeroa ei ole vielä annettu, näytetään täyttökentät
    if ($kukarow["oletus_asiakas"] != 0) {
      $query  = "SELECT *
                 FROM asiakas
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$kukarow[oletus_asiakas]'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $extra_asiakas = mysql_fetch_assoc($result);
        $yt   = $extra_asiakas["ytunnus"];
      }
    }

    if ($kukarow["myyja"] != 0) {
      $my = $kukarow["myyja"];
    }
    else {
      $my = $myyjanumero;
    }

    if ($toim == "PIKATILAUS") {

      if ($yhtiorow['pikatilaus_focus'] == 'A' and isset($indexvas) and $indexvas == 1) {
        $kentta = 'syotetty_ytunnus';
      }
      elseif ($yhtiorow['pikatilaus_focus'] == 'M' and isset($indexvas) and $indexvas == 1) {
        $kentta = 'myyjanumero';
      }
      elseif ($yhtiorow['pikatilaus_focus'] == "Y") {
        if ($myyjanumero and !loytyyko_myyja_tunnuksella($myyjanumero)) {
          $my                = "";
          $myyjanumero_virhe = "<font class='error'>" . t("Virheellinen myyjänro") . "</font>";
          $kentta            = 'myyjanumero';
        }
        else {
          $kentta = empty($myyjanumero) ? 'myyjanumero' : 'tuoteno';
        }

        $required = 'required';

        // Tarvitaan, koska safari ei tue HTML5 validaatiota
        $javascript = "function hasHtml5Validation() {
                      return typeof document.createElement('input').checkValidity === 'function';
                     }

                     if (hasHtml5Validation()) {
                       $('form').submit(function (e) {
                         if (!this.checkValidity()) {
                           e.preventDefault();
                           var error = '<font class=\"error\">Myyjänumero on annettava</font>';
                           $('#myyjanumero_error').html(error);
                           $('input[name=myyjanumero]').focus();
                         } else {
                           $('#myyjanumero_error').html('');
                           e.target.submit();
                         }
                       });
                     }";
      }

      echo "<tr>$jarjlisa
        <th align='left'>".t("Asiakas")."</th>
        <td><input type='text' size='10' name='syotetty_ytunnus' value='$yt'></td>
        <th align='left'>".t("Postitp")."</th>
        <td><input type='text' size='10' name='postitp' value='$postitp'></td>
        </tr>";
      echo "<tr>$jarjlisa
        <th align='left'>".t("Myyjänro")."</th>
        <td>" .
        "<input " .
        "type='text' " .
        "size='10' " .
        "name='myyjanumero' " .
        "value='$my' " .
        "{$required}>" .
        "</td>
        </tr>";
    }
  }

  echo "</table>

  <span id='myyjanumero_error'>{$myyjanumero_virhe}</span>

  <script>{$javascript}</script>";

  if ($yhtiorow["extranet_poikkeava_toimitusosoite"] == "Y") {
    if (isset($poikkeava_toimitusosoite) and $poikkeava_toimitusosoite == "Y") {
      piirra_toimitusosoite($laskurow);
    }
  }

  if ($laskurow['tila'] == 'N' and $laskurow['alatila'] == 'F' and $laskurow['sisviesti3'] != '') {

    echo "<br>";

    echo "<table>";

    if (strpos($laskurow['sisviesti3'], '|||') !== false) {
      echo "<tr><th colspan='5'>", t("Selvitettävät lisärivit"), "</th></tr>";
      echo "<tr>";
      echo "<th>", t("Tuoteno"), "</th>";
      echo "<th>", t("Kpl"), "</th>";
      echo "<th>", t("Nimitys"), "</th>";
      echo "<th>", t("Rekno"), "</th>";
      echo "<th>", t("Lisäinfo"), "</th>";
      echo "</tr>";
    }
    else {
      echo "<tr><th>", t("Sisäinen viesti"), "</th></tr>";
    }

    foreach (explode("\n", $laskurow['sisviesti3']) as $_sisviesti3) {
      $_sisviesti3 = str_replace("|||", "</td><td>", $_sisviesti3);
      echo "<tr>";
      echo "<td>{$_sisviesti3}</td>";
      echo "</tr>";
    }

    echo "</table>";

  }

  echo "<br>";

  // Tarkastetaan onko asiakas myyntikiellossa
  if ($laskurow['liitostunnus'] > 0) {
    if ($asiakasrow['myyntikielto'] == 'K') {
      if ($kukarow['extranet'] != '') {
        echo "<font class='error'>", t("Luottorajasi on täynnä, ota yhteys asiakaspalveluun"), ".</font><br/>";
      }
      else {
        echo "<font class='error'>", t("Asiakas on myyntikiellossa"), "!</font><br/>";
      }

      $muokkauslukko = 'LUKOSSA';
      $myyntikielto = 'MYYNTIKIELTO';
    }
  }

  if ($smsnumero != "" and strlen("smsviesti") > 0) {

    if (strlen($smsviesti) > 160) {
      echo "<font class='error'>VIRHE: Tekstiviestin maksimipituus on 160 merkkiä!</font><br>";
      $tila = "SYOTASMS";
    }

    $smsnumero = str_replace("-", "", $smsnumero);
    $ok = 1;

    // Käytäjälle lähetetään tekstiviestimuistutus
    if ($smsnumero != '' and strlen($smsviesti) > 0 and strlen($smsviesti) < 160 and $sms_palvelin != "" and $sms_user != "" and $sms_pass != "") {

      $smsviesti = urlencode($smsviesti);

      $retval = file_get_contents("$sms_palvelin?user=$sms_user&pass=$sms_pass&numero=$smsnumero&viesti=$smsviesti");
      $smsviesti = urldecode($smsviesti);

      if (trim($retval) == "0") {
        $ok = 0;

        if ($yhtiorow["kalenterimerkinnat"] == "") {
          $kysely = "INSERT INTO kalenteri
                     SET tapa     = '".t("Teksiviesti")."',
                     asiakas      = '$laskurow[ytunnus]',
                     liitostunnus = '$laskurow[liitostunnus]',
                     kuka         = '$kukarow[kuka]',
                     yhtio        = '$kukarow[yhtio]',
                     tyyppi       = 'Memo',
                     pvmalku      = now(),
                     kentta01     = '$smsnumero\n$smsviesti',
                     laatija      = '$kukarow[kuka]',
                     luontiaika   = now()";
          $result = pupe_query($kysely);
        }

      }
    }

    if ($ok == 1) {
      echo "<font class='error'>VIRHE: Tekstiviestin lähetys epäonnistui! $retval</font><br><br>";
    }

    if ($ok == 0) {
      echo "<font class='message'>Tekstiviestimuistutus lähetetään!</font><br><br>";
    }
  }

  if ($tila == "SYOTASMS") {
    if ($smsviesti == "") {
      $smsviesti = "\n\n\nTerv. ".$kukarow["nimi"]."\n".$yhtiorow["nimi"];
    }

    echo "<table>
        <tr>
          <th>Puh.</th>
          <td><input type='text' size='20' name='smsnumero' value='$asiakasrow[gsm]'></td>
        </tr>
        <tr>
          <th>Viesti</th>
          <td><textarea name='smsviesti' cols='45' rows='6' wrap='soft'>$smsviesti</textarea></td>
          <td class='back' valign='bottom'><input type='submit' value = 'Lähetä'></td>
        </tr>
      </table>
      <br>";
  }

  //Kuitataan OK-var riville
  if (($kukarow["extranet"] == "" or $yhtiorow["korvaavat_hyvaksynta"] != ""  or $yhtiorow["vientikiellon_ohitus"] == "K" or $vastaavienkasittely == "kylla") and ($tila == "OOKOOAA" or $tila == "OOKOOAAKAIKKI")) {

    if ($tila == "OOKOOAAKAIKKI" and $tilausnumero != "" and $tilausnumero != 0) {
      $wherelisa = "AND otunnus = '{$tilausnumero}'";
    }
    else {
      $wherelisa = "AND tunnus = '{$rivitunnus}'";
    }

    $update_var2 = (isset($naytetaan_vastaavat) and trim($naytetaan_vastaavat) != "") ? "" : "OK";

    $query = "UPDATE tilausrivi
              SET var2 = '{$update_var2}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              {$wherelisa}";
    $result = pupe_query($query);

    $tapa     = "";
    $rivitunnus = "";
    $keratty = "";
    $kerattyaika = 0;
    $toimitettu = '';
    $toimitettuaika = 0;
  }

  if ($kukarow["extranet"] == "" and $tila == "LISATIETOJA_RIVILLE_OSTO_VAI_HYVITYS") {
    $query = "UPDATE tilausrivin_lisatiedot
              SET osto_vai_hyvitys   = '$osto_vai_hyvitys',
              muutospvm            = now(),
              muuttaja             = '$kukarow[kuka]'
              WHERE yhtio          = '$kukarow[yhtio]'
              and tilausrivitunnus = '$rivitunnus'";
    $result = pupe_query($query);

    $tila     = "";
    $rivitunnus = "";
    $keratty = "";
    $kerattyaika = 0;
    $toimitettu = '';
    $toimitettuaika = 0;
  }

  //Muokataan tilausrivin lisätietoa
  if ($kukarow["extranet"] == "" and ($tila == "LISATIETOJA_RIVILLE" or $tila == "ASPOSITIO_RIVILLE" or $tila == "PALAUTUSVARASTO") and (int) $kukarow["kesken"] > 0) {

    $query = "SELECT tilausrivi.tunnus
              FROM tilausrivi use index (yhtio_otunnus)
              LEFT JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
              WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
              AND tilausrivi.otunnus  = '$kukarow[kesken]'
              AND tilausrivi.tyyppi  != 'D'
              AND (tilausrivi.tunnus = '$rivitunnus' or (tilausrivi.perheid!=0 and tilausrivi.perheid = '$rivitunnus' and (tilausrivin_lisatiedot.ei_nayteta = 'P' or tilausrivi.tyyppi IN ('W','V'))) or (tilausrivi.perheid2!=0 and tilausrivi.perheid2 = '$rivitunnus' and (tilausrivin_lisatiedot.ei_nayteta = 'P' or tilausrivi.tyyppi IN ('W','V'))))
              ORDER BY tunnus";
    $lapsires = pupe_query($query);

    if ($tila == "LISATIETOJA_RIVILLE") {
      $updlisa = "positio = '$positio',";
    }
    elseif ($tila == "ASPOSITIO_RIVILLE") {
      $updlisa = "asiakkaan_positio = '$asiakkaan_positio',";
    }
    else {
      $updlisa = "palautus_varasto = '{$palautus_varasto}',";
    }

    while ($lapsi = mysql_fetch_assoc($lapsires)) {
      //  Päivitetään positio
      $query = "UPDATE tilausrivin_lisatiedot SET
                {$updlisa}
                muutospvm            = now(),
                muuttaja             = '{$kukarow["kuka"]}'
                WHERE yhtio          = '{$kukarow["yhtio"]}'
                AND tilausrivitunnus = '{$lapsi["tunnus"]}'";
      $result = pupe_query($query);
    }

    $tila          = "";
    $rivitunnus      = "";
    $positio        = "";
    $asiakkaan_positio = "";
    $lisaalisa        = "";
    $keratty = "";
    $kerattyaika = 0;
    $toimitettu = '';
    $toimitettuaika = 0;
  }

  if ($kukarow["extranet"] == "" and $tila == "LISLISAV") {
    //Päivitetään isän perheid jotta voidaan lisätä lisää lisävarusteita
    if ($spessuceissi == "OK") {
      $xperheidkaks = -1;
    }
    else {
      $xperheidkaks =  0;
    }

    $query = "UPDATE tilausrivi set
              perheid2    = $xperheidkaks
              where yhtio = '$kukarow[yhtio]'
              and tunnus  = '$rivitunnus'
              LIMIT 1";
    $updres = pupe_query($query);

    $tila     = "";
    $tapa     = "";
    $rivitunnus = "";
    $keratty = "";
    $kerattyaika = 0;
    $toimitettu = '';
    $toimitettuaika = 0;
  }

  if ($kukarow["extranet"] == "" and $tila == "MYYNTITILIRIVI") {
    $tilatapa = "PAIVITA";
    require "laskuta_myyntitilirivi.inc";
  }

  // ollaan muokkaamassa rivin tietoja, haetaan rivin tiedot ja poistetaan rivi..
  if ($tila == 'MUUTA' and (int) $kukarow["kesken"] > 0) {

    $rivitunnukset_array = array($rivitunnus);
    $toimita_kaikki_bool = false;

    if (isset($toimita_kaikki) and !is_array($toimita_kaikki) and trim($toimita_kaikki) != '') {
      $rivitunnukset_array = explode(',', $toimita_kaikki);
      $toimita_kaikki_bool = true;
    }

    foreach ($rivitunnukset_array as $rivitunnus) {
      if ($toimita_kaikki_bool) {
        $tila = "MUUTA";
        $tapa = "POISJTSTA";
      }

      $query  = "SELECT tilausrivin_lisatiedot.*, tilausrivi.*, tuote.sarjanumeroseuranta
                 FROM tilausrivi use index (PRIMARY)
                 LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
                 LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
                 where tilausrivi.yhtio = '$kukarow[yhtio]'
                 and tilausrivi.otunnus = '$kukarow[kesken]'
                 and tilausrivi.tunnus  = '$rivitunnus'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {

        $tilausrivi = mysql_fetch_assoc($result);

        if ($tapa == "VAIHDAJAPOISTA" and $tilausrivi["perheid"] != 0) {
          $tapa = "JT";
        }

        // Tehdään pari juttua jos tuote on sarjanumeroseurannassa
        if ($tilausrivi["sarjanumeroseuranta"] != '') {

          //Nollataan sarjanumero
          if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
            $tunken = "siirtorivitunnus";
          }
          elseif ($tilausrivi["varattu"] < 0) {
            $tunken = "ostorivitunnus";
          }
          else {
            $tunken = "myyntirivitunnus";
          }

          $query = "SELECT group_concat(tunnus) tunnukset
                    FROM sarjanumeroseuranta
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tuoteno = '$tilausrivi[tuoteno]'
                    and $tunken = '$tilausrivi[tunnus]'";
          $sarjares = pupe_query($query);
          $sarjarow = mysql_fetch_assoc($sarjares);

          //Pidetään sarjatunnus muistissa
          if ($tapa != "POISTA") {
            $myy_sarjatunnus = $sarjarow["tunnukset"];
          }

          // Otetaan sarjanumero talteen, jotta osataan muokkauksen jälkeen palauttaa oikea erä jos se vielä riittää
          if (isset($myy_sarjatunnus) and $myy_sarjatunnus != "") {
            $query = "SELECT sarjanumero
                      FROM sarjanumeroseuranta
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  IN ($myy_sarjatunnus)";
            $m_eranro = mysql_fetch_assoc(pupe_query($query));
          }

          if ($yhtiorow['laiterekisteri_kaytossa'] != '') {
            // Nollataan myyntirivitunnus laite-taulusta
            $spessukveri = "SELECT *
                            FROM sarjanumeroseuranta
                            WHERE myyntirivitunnus = '$rivitunnus'
                            ORDER BY luontiaika desc
                            LIMIT 1";
            $spessures = pupe_query($spessukveri);
            $spessurivi = mysql_fetch_assoc($spessures);

            $laiteupdate = "UPDATE laite
                            SET paikka = '',
                            muutospvm    = now(),
                            muuttaja     = '{$kukarow['kuka']}'
                            WHERE yhtio  = '{$kukarow['yhtio']}'
                            AND sarjanro = '{$spessurivi['sarjanumero']}'
                            AND tuoteno  = '{$spessurivi['tuoteno']}'
                            AND paikka   = '{$rivitunnus}'";
            pupe_query($laiteupdate);
          }
        }

        if ($tapa == "VAIHDA" and ($tilausrivi["sarjanumeroseuranta"] == "E" or $tilausrivi["sarjanumeroseuranta"] == "F" or $tilausrivi["sarjanumeroseuranta"] == "G")) {
          // Nollataan sarjanumerolinkit
          vapauta_sarjanumerot($toim, $kukarow["kesken"], " and tilausrivi.tunnus = '$rivitunnus' ");
        }

        // Poistetaan myös tuoteperheen lapset
        if ($tapa != "VAIHDA" and $tapa != "POISJTSTA" and $tapa != "PUUTE" and $tapa != "JT") {

          // Nollataan sarjanumerolinkit lapsien ja isän ja dellataan ostorivit
          vapauta_sarjanumerot($toim, $kukarow["kesken"], " and (tilausrivi.tunnus = '$rivitunnus' or tilausrivi.perheid = '$rivitunnus') ");

          $query = "DELETE FROM tilausrivi
                    WHERE perheid  = '$rivitunnus'
                    and tunnus    != '$rivitunnus'
                    and otunnus    = '$kukarow[kesken]'
                    and yhtio      = '$kukarow[yhtio]'";
          $result = pupe_query($query);
        }

        // Poistetaan myös tehdaslisävarusteet
        if ($tapa == "POISTA") {

          // Nollataan sarjanumerolinkit ja dellataan ostorivit
          vapauta_sarjanumerot($toim, $kukarow["kesken"], " and tilausrivi.perheid2   = '$rivitunnus' ");

          $query = "DELETE FROM tilausrivi
                    WHERE perheid2  = '$rivitunnus'
                    and tunnus     != '$rivitunnus'
                    and otunnus     = '$kukarow[kesken]'
                    and yhtio       = '$kukarow[yhtio]'";
          $result = pupe_query($query);

        }

        $_ei_jt_meilia = "";

        if ($tapa == "POISTA" and $kukarow["extranet"] == "" and ($toim == "PIKATILAUS" or $toim == "RIVISYOTTO") and !empty($tilausrivi['vanha_otunnus']) and $tilausrivi['vanha_otunnus'] != $tilausrivi['otunnus'] and $tilausrivi['positio'] == 'JT' and !empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and $yhtiorow['jt_automatiikka_mitatoi_tilaus'] == 'E') {

          $jt_saldo_lisa = $yhtiorow["varaako_jt_saldoa"] == "" ? ", jt = varattu, varattu = 0 " : '';

          // riviä poistettaessa laitetaan jt-rivi takaisin omalle tilaukselle
          $query = "UPDATE tilausrivi SET
                    otunnus     = '{$tilausrivi['vanha_otunnus']}',
                    var         = 'J'
                    {$jt_saldo_lisa}
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$tilausrivi['tunnus']}'";
          $jt_rivi_res = pupe_query($query);

          echo "<font class='message'>", t("Jälkitoimitus palautettiin tilaukselle"), " {$tilausrivi['vanha_otunnus']}</font><br /><br />";
          $_ei_jt_meilia = 'X';
        }
        elseif ($tapa != "POISJTSTA" and $tapa != "PUUTE" and $tapa != "JT") {
          if ($tapa == "POISTA") {
            // Mikäli tilausriviin liittyy ostorivi, niin poistetaan myös se
            $query = "SELECT tilausrivilinkki
                      FROM tilausrivin_lisatiedot
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tilausrivitunnus = '{$rivitunnus}'";
            $result = pupe_query($query);
            $_myyntirivi = mysql_fetch_assoc($result);

            if (mysql_num_rows($result) == 1 and $_myyntirivi["tilausrivilinkki"] != 0) {
              // Tarkistetaan, että ostotilaus on vielä kesken,
              // koska jos ei ole kesken on jo lähetetty eteenpäin
              $query = "DELETE tilausrivi.*
                        FROM tilausrivi
                          JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
                        WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                        AND tilausrivi.tunnus = '{$_myyntirivi["tilausrivilinkki"]}'
                        AND lasku.tila = 'O'
                        AND lasku.alatila = ''
                        AND tilausrivi.tyyppi = 'O'
                        AND tilausrivi.kpl = 0";
              $ostotilaus_tarkistus = mysql_fetch_assoc(pupe_query($query));

              if (mysql_affected_rows() > 0) {
                echo "<font class='error'>".t("Rivi poistettiin myös ostotilaukselta")."</font><br/><br/>";
              }
              else {
                echo "<font class='error'>".t("Ostotilaus ei ollut enää kesken tilassa, ei voitu poistaa riviä ostolta")."!</font><br/><br/>";
              }
            }
          }

          // Poistetaan muokattava tilausrivi
          $query = "DELETE FROM tilausrivi
                    WHERE tunnus = '$rivitunnus'";
          $result = pupe_query($query);
        }

        // Jos muokkaamme tilausrivin paikkaa ja se on speciaalikeissi, T,U niin laitetaan $paikka-muuttuja kuntoon
        if (substr($tapa, 0, 6) != "VAIHDA" and $tilausrivi["var"] == "T" and substr($paikka, 0, 3) != "¡¡¡") {
          $paikka = "¡¡¡".$tilausrivi["toimittajan_tunnus"];
        }

        if (substr($tapa, 0, 6) != "VAIHDA" and $tilausrivi["var"] == "U" and substr($paikka, 0, 3) != "!!!") {
          $paikka = "!!!".$tilausrivi["toimittajan_tunnus"];
        }

        $rekisterinumero = $tilausrivi['rekisterinumero'];
        $tuoteno = $tilausrivi['tuoteno'];

        if (in_array($tilausrivi["var"], array('S', 'U', 'T', 'R', 'J'))) {
          if ($yhtiorow["varaako_jt_saldoa"] == "") {
            $kpl = $tilausrivi['jt'];
          }
          else {
            $kpl = $tilausrivi['jt']+$tilausrivi['varattu'];
          }
        }
        elseif ($tilausrivi["var"] == "P" or
          ($yhtiorow["extranet_nayta_saldo"] and
            (($asiakasrow['extranet_tilaus_varaa_saldoa'] == "" and
                $yhtiorow["extranet_tilaus_varaa_saldoa"] == "E") or
              $asiakasrow["extranet_tilaus_varaa_saldoa"] == "E") and
            $laskurow["tilaustyyppi"] == "H")
        ) {
          $kpl = $tilausrivi['tilkpl'];
        }
        else {
          $kpl  = $tilausrivi['varattu'];
        }

        $query = "SELECT *
                  FROM tuote
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and  tuoteno = '$tilausrivi[tuoteno]'";
        $aresult = pupe_query($query);
        $tuoterow = mysql_fetch_assoc($aresult);

        // Tutkitaan onko tämä myyty ulkomaan alvilla
        list(, , , $tsek_alehinta_alv, ) = alehinta($laskurow, $tuoterow, $kpl, '', '', '');

        if ($tsek_alehinta_alv > 0) {
          $tuoterow["alv"] = $tsek_alehinta_alv;
        }

        // jos käytössä on myyntihinnan poikkeava määrä, kerrotaan hinta takaisin kuntoon.
        if ($tuoterow["myyntihinta_maara"] != 0) {
          $tilausrivi["hinta"] = $tilausrivi["hinta"] * $tuoterow["myyntihinta_maara"];
        }

        if ($tuoterow["alv"] != $tilausrivi["alv"] and $yhtiorow["alv_kasittely"] == "" and $tilausrivi["alv"] < 500) {
          $hinta = (float) $tilausrivi["hinta"] / (1+$tilausrivi['alv']/100) * (1+$tuoterow["alv"]/100);
        }
        else {
          $hinta = (float) $tilausrivi["hinta"];
        }

        if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
          $hinta  = hintapyoristys(laskuval($hinta, $laskurow["vienti_kurssi"]));
        }
        else {
          $hinta  = hintapyoristys($hinta);
        }

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          ${'ale'.$alepostfix} = $tilausrivi["ale{$alepostfix}"];
        }

        $netto = $tilausrivi['netto'];
        $alv = $tilausrivi['alv'];
        $kommentti = $tilausrivi['kommentti'];
        $ale_peruste = $tilausrivi['ale_peruste'];
        $kerayspvm = $tilausrivi['kerayspvm'];
        $toimaika = $tilausrivi['toimaika'];
        $hyllyalue = $tilausrivi['hyllyalue'];
        $hyllynro = $tilausrivi['hyllynro'];
        $hyllytaso = $tilausrivi['hyllytaso'];
        $hyllyvali = $tilausrivi['hyllyvali'];
        $rivinumero = $tilausrivi['tilaajanrivinro'];
        $jaksotettu = $tilausrivi['jaksotettu'];
        $perheid2 = $tilausrivi["perheid2"];
        $sopimuksen_lisatieto1 = $tilausrivi["sopimuksen_lisatieto1"];
        $sopimuksen_lisatieto2 = $tilausrivi["sopimuksen_lisatieto2"];
        $omalle_tilaukselle = $tilausrivi['omalle_tilaukselle'];
        $valmistuslinja = $tilausrivi['positio'];

        if ($tuoterow["alv"] < 500 and $yhtiorow["alv_kasittely_hintamuunnos"] == 'o') {
          // valittu ei näytetä alveja vaikka hinnat alvillisina
          if ($tilausrivi_alvillisuus == "E" and $yhtiorow["alv_kasittely"] == '') {
            $hinta = round($hinta / (1+$tuoterow["alv"]/100), $yhtiorow['hintapyoristys']);
          }
          // valittu näytetään alvit vaikka hinnat alvittomia
          if ($tilausrivi_alvillisuus == "K" and $yhtiorow["alv_kasittely"] == 'o') {
            $hinta = round($hinta * (1+$tuoterow["alv"]/100), $yhtiorow['hintapyoristys']);
          }
        }

        if ($yhtiorow['myyntitilausrivi_rekisterinumero'] == 'K' and stristr($kommentti, $tilausrivi['rekisterinumero'])) {
          $kommentti = str_replace("\n" . $tilausrivi['rekisterinumero'], '', $kommentti);
        }

        // useamman valmisteen reseptit...
        if (($tilausrivi['tyyppi'] == "W" and $tilausrivi["tunnus"] != $tilausrivi["perheid"]) or ($tilausrivi['tyyppi'] == "W" and $tapa == "VAIHDA")) {
          $perheid2 = -100;
        }

        if ($tilausrivi['hinta'] == '0.00') $hinta = '';

        // Muistetaan myös valittu paikka
        if ($tapa != "VAIHDA" and $hyllyalue != '') {
          $paikka = $hyllyalue."#!¡!#".$hyllynro."#!¡!#".$hyllyvali."#!¡!#".$hyllytaso;
        }

        if (!empty($paikka) and !empty($m_eranro)) {
          $paikka .= "#!¡!#".$m_eranro["sarjanumero"];
        }

        if ($tapa == "MUOKKAA") {
          $var  = $tilausrivi["var"];

          //Jos lasta muokataan, niin säilytetään sen perheid
          if ($tilausrivi["tunnus"] != $tilausrivi["perheid"] and $tilausrivi["perheid"] != 0) {
            $perheid = $tilausrivi["perheid"];
          }

          if ($tilausrivi["tunnus"] == $tilausrivi["perheid"] and $tilausrivi["perheid"] != 0) {
            $paikka = "";
          }

          $tila    = "MUUTA";
        }
        elseif ($tapa == "JT") {
          $var  = "J";
          $tila = "VARMUUTOS";
        }
        elseif ($tapa == "PUUTE") {
          $var     = "P";
          $tila = "VARMUUTOS";
        }
        elseif ($tapa == "POISJTSTA") {
          $var     = "";

          //Jos lasta muokataan, niin säilytetään sen perheid
          if ($tilausrivi["tunnus"] != $tilausrivi["perheid"] and $tilausrivi["perheid"] != 0) {
            $perheid = $tilausrivi["perheid"];
          }
          $tila = "VARMUUTOS";
        }
        elseif ($tapa == "VAIHDA") {
          $perheid  = $tilausrivi['perheid'];
          $tila    = "";
          $var    = $tilausrivi["var"];
          $var2     = $tilausrivi['var2'];
        }
        elseif ($tapa == "VAIHDAJAPOISTA") {
          $perheid = "";
          $tila    = "";
          if (substr($paikka, 0, 3) != "!!!" and substr($paikka, 0, 3) != "¡¡¡") $paikka = "";
        }
        elseif ($tapa == "MYYVASTAAVA") {
          // tuoteno, määrä, muut nollataan
          $tuoteno      = $vastaavatuoteno;
          $var        = '';
          $hinta        = '';
          $netto        = '';
          $paikka        = '';
          $alv        = '';
          $perheid      = 0;
          $perheid2      = 0;
          $tilausrivilinkki  = '';
          $toimittajan_tunnus  = '';
          // laitetaan tila tyhjäksi että se menee suoraan tilausriviksi.
          $tila = "";

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            ${'ale'.$alepostfix} = '';
          }
        }
        elseif ($tapa == "POISTA") {

          if ($_ei_jt_meilia == '' and $yhtiorow['jt_email'] != '' and $tilausrivi['positio'] == 'JT') {
            $kutsu = "";
            $subject = "";
            $content_body = "";

            $kutsu = "Jälkitoimitus";
            $subject = t("Jälkitoimitustuote poistettu");
            $content_body = $yhtiorow['nimi']."\n\n";

            $content_body .= "$kpl ".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")." ".t("poistettu jälkitoimituksesta tuotetta")." $tilausrivi[tuoteno] ".t("tilauksella")." $kukarow[kesken]\n\n\n";

            // Sähköpostin lähetykseen parametrit
            $parametri = array(
              "to"           => $yhtiorow["jt_email"],
              "cc"           => "",
              "subject"      => $subject,
              "ctype"        => "text",
              "body"         => $content_body,
              "attachements" => "",
            );

            pupesoft_sahkoposti($parametri);

            echo t("Lähetettiin jälkitoimitus-sähköposti")."...<br><br>";
          }

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            ${'ale'.$alepostfix} = '';
          }

          $rekisterinumero       = '';
          $tuoteno         = '';
          $kpl           = '';
          $var           = '';
          $hinta           = '';
          $netto           = '';
          $rivitunnus         = 0;
          $kommentti         = '';
          $kerayspvm         = '';
          $toimaika         = '';
          $paikka           = '';
          $alv           = '';
          $perheid         = 0;
          $perheid2         = 0;
          $tilausrivilinkki     = '';
          $toimittajan_tunnus     = '';
          $valmistuslinja        = '';
          $keratty = "";
          $kerattyaika = 0;
          $toimitettu = '';
          $toimitettuaika = 0;
        }

        if ($tila == "VARMUUTOS" and in_array($tapa, array("POISJTSTA","PUUTE","JT"))) {
          //otetaan varattukpl ja jtkpl muuttuja käyttöön
          $varattukpl = 0;
          $jtkpl = 0;

          // Katotaan varattu ja jt määrät kuntoon
          // POISJTSTA eli ollaan merkitsemässä riviä toimitettavaksi -> jt => 0 ja varattu => tilkpl
          if ($tapa == "POISJTSTA") {
            $varattukpl = $kpl;
            $jtkpl = 0;
          }
          // PUUTE eli ollaan tekemässä rivistä puuteriviä -> jt => 0 ja varattu => 0
          elseif ($tapa == "PUUTE") {
            $varattukpl = 0;
            $jtkpl = 0;
          }
          // JT eli ollaan tekemässä rivistä JT-riviä, merkitään jt ja varattu sen mukaan
          // varaavatko JT-rivit saldoa vai eivät;
          // EI -> jt => tilkpl ja varattu => 0// KYLLÄ -> jt => 0 ja varattu => tilkpl
          elseif ($tapa == "JT") {
            //varaako JT saldoa?
            if ($yhtiorow["varaako_jt_saldoa"] == "") {
              $varattukpl = 0;
              $jtkpl = $kpl;
            }
            else {
              $varattukpl = $kpl;
              $jtkpl = 0;
            }
          }

          // Jos ollaan toimittamassa riviä
          // tai jos ollaan käsittelemässä perheetöntä tuotetta
          // tai lapsituotetta
          // niin silloin halutaan päivittää vain kyseinen rivi eikä tarvitse päivitellä lapsia
          if ($tapa == "POISJTSTA"
            or $tilausrivi["perheid"] == ""
            or $tilausrivi["perheid"] != $tilausrivi["tunnus"]) {
            $query =  "UPDATE tilausrivi
                       SET var = '$var',
                       varattu     = $varattukpl,
                       jt          = $jtkpl
                       WHERE yhtio = '{$kukarow['yhtio']}'
                       AND tunnus  = '{$tilausrivi['tunnus']}'";
            pupe_query($query);
          }
          // Kun ollaan tekemässä isätuotteesta JT-riviä tai merkitsemässä sitä puutteeksi
          // niin tehdään samat jutu myös perheen lapsille
          else {
            $query = "SELECT tunnus,
                      tuoteno,
                      tilkpl
                      FROM tilausrivi
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND perheid = '{$tilausrivi['tunnus']}'
                      AND otunnus = '{$tilausrivi['otunnus']}'";
            $mriviresult = pupe_query($query);

            while ($muutettavarivi = mysql_fetch_assoc($mriviresult)) {
              // Katotaan onko varattukpl vai jtkpl käytössä ja laitetaan tilkpl siihen
              $tuotevarattukpl = 0;
              $tuotejtkpl      = 0;

              if ($varattukpl != 0) {
                $tuotevarattukpl = $muutettavarivi["tilkpl"];
              }
              elseif ($jtkpl != 0) {
                $tuotejtkpl = $muutettavarivi["tilkpl"];
              }

              $query =  "UPDATE tilausrivi
                         SET var = '$var',
                         varattu     = $tuotevarattukpl,
                         jt          = $tuotejtkpl
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tunnus  = '{$muutettavarivi['tunnus']}'";
              pupe_query($query);
            }
          }

          $tila = "";

          //tyhjennetään kaikki maholliset muuttujat ettei sitten laiteta lisää rivi kohtaan näitä tietoja ja yritetä lisätä sitä riviä uudestaan..
          $ale1 = "";
          $alv         = "";
          $hinta         = "";
          $kerayspvm       = "";
          $kommentti       = "";
          $kpl         = "";
          $netto         = "";
          $paikka       = "";
          $perheid       = 0;
          $perheid2       = 0;
          $rivinumero     = "";
          $rivitunnus     = 0;
          $toimaika       = "";
          $tuoteno       = "";
          $var = "";
        }
      }
    }
  }

  $_ei_extranet = ($kukarow["extranet"] == "");

  if ($_ei_extranet) {

    $_varastoon = ($toim == "VALMISTAVARASTOON");
    $_tila_check = (!in_array($tila, array('LISAAKERTARESEPTIIN', 'LISAAISAKERTARESEPTIIN')));

    if ($_varastoon and $_tila_check and empty($perheid)) {

      $perheid2 = 0;

      if ($valmiste_vai_raakaaine == 'valmiste' and !empty($tuoteno)) {

        $query = "SELECT *
                  FROM tuoteperhe
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND tyyppi     = 'R'
                  AND isatuoteno = '{$tuoteno}'";
        $tuoteperhe_chk_res = pupe_query($query);

        if (mysql_num_rows($tuoteperhe_chk_res) == 0) {
          $perheid2 = -100;
        }
      }
    }

    if ($tila == 'MUUTAKAIKKI') {
      if (!empty($tilausnumero)) {

        // Riippuen yhtiön parametristä, käsitellään jt eri tavalla
        if ($yhtiorow["varaako_jt_saldoa"] == "") {
          $updatelisa = "jt = if(var='P',tilkpl,if(var='J',jt,varattu)), varattu = 0,";
        }
        else {
          $updatelisa = "varattu = if(var='P',tilkpl,varattu),";
        }

        $query = "UPDATE tilausrivi
                  SET $updatelisa
                  var         = 'J',
                  kerayspvm   = '".date('Y-m-d', strtotime('now + 3 month'))."'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND otunnus = '{$tilausnumero}'";
        pupe_query($query);
      }
    }

    //Lisätään tuote tiettyyn tuoteperheeseen/reseptiin
    if ($tila == "LISAARESEPTIIN" and $teeperhe == "OK") {
      $query = "UPDATE tilausrivi
                SET perheid2 = '$isatunnus'
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$isatunnus'";
      $presult = pupe_query($query);
      $perheid2 = $isatunnus;
    }

    //Lisätään tuote tiettyyn tuoteperheeseen/reseptiin
    if ($tila == "LISAAKERTARESEPTIIN" and $teeperhe == "OK") {

      $query = "UPDATE tilausrivi
                SET
                perheid     = '$isatunnus'
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$isatunnus'";
      $presult = pupe_query($query);
      $perheid = $isatunnus;
    }

    //Lisätään tuote tiettyyn tuoteperheeseen/reseptiin
    if ($tila == "LISAAISAKERTARESEPTIIN") {
      if ($teeperhe == "OK") {

        $query = "UPDATE tilausrivi
                  SET
                  perheid     = '$isatunnus'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$isatunnus'";
        $presult = pupe_query($query);
        $perheid = $isatunnus;
      }

      // useamman valmisteen reseptit...
      $perheid2 = -100;
    }
  }

  if ($tuoteno != '') {
    $multi = "TRUE";

    if (@include "inc/tuotehaku.inc");
    elseif (@include "tuotehaku.inc");
    else exit;
  }

  //Lisätään rivi tai jos ollaan muuttamassa toimitettavaksi (POISJTSTA) tai puutteeksi niin silloin vain päivitellään rivin tiedot ei olla poistettu riviä joten ei myöskään tarvi lisätä sitä uuestaan & jos ollaan perheellinen ja tehhään koko perheestä JT-rivejä
  if ((trim($tuoteno) != '' or is_array($tuoteno_array)) and ($kpl != '' or is_array($kpl_array)) and ($tila != "MUUTA" and $tila != "VARMUUTOS") and $ulos == '' and ($variaatio_tuoteno == "" or (is_array($kpl_array) and array_sum($kpl_array) != 0))) {
    if (!is_array($tuoteno_array) and trim($tuoteno) != "") {
      $tuoteno_array[] = $tuoteno;
    }

    //Käyttäjän syöttämä hinta ja ale ja netto, pitää säilöä jotta tuotehaussakin voidaan syöttää nämä

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      ${'kayttajan_ale'.$alepostfix} = ${'ale'.$alepostfix};
    }

    $kayttajan_hinta  = $hinta;
    $kayttajan_netto   = strtoupper(trim($netto));
    $kayttajan_var    = $var;
    $kayttajan_kpl    = $kpl;
    $kayttajan_alv    = $alv;
    $kayttajan_paikka  = $paikka;
    $lisatty       = 0;
    $hyvityssaanto_indeksi         = 0;
    $hyvityssaanto_hinta_array       = "";
    $hyvityssaanto_ale_array       = "";
    $hyvityssaanto_kpl_array       = "";
    $hyvityssaanto_kommentti_array     = "";
    $hyvityssaanto_palautuskielto_array  = "";

    // Jos käytetään reklamaatioiden hinnoittelusääntöä ja käyttäjä ei ole väkisinhyväksynyt riviä
    if ($yhtiorow["reklamaation_hinnoittelu"] == "K" and ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") and $kayttajan_var != "H") {
      $hyvityssaanto_hinta_array = array();
      $hyvityssaanto_ale_array = array();
      $hyvityssaanto_kpl_array = array();
      $hyvityssaanto_kommentti_array = array();
      $hyvityssaanto_palautuskielto_array = array();

      $palautus = hae_hyvityshinta($laskurow["liitostunnus"], $tuoteno, $kpl);

      foreach ($palautus as $index => $arvot) {
        $tuoteno_array[] = $palautus[$index]["tuoteno"];
        $hyvityssaanto_hinta_array[$index][$tuoteno] = $palautus[$index]["hinta"];
        $hyvityssaanto_ale_array[$index][$tuoteno] = $palautus[$index]["ale"];
        $hyvityssaanto_kpl_array[$index][$tuoteno] = $palautus[$index]["kpl"] * -1;
        if (stripos($kommentti, $palautus[$index]["kommentti"]) === FALSE) $hyvityssaanto_kommentti_array[$index][$tuoteno] = $palautus[$index]["kommentti"];
        $hyvityssaanto_palautuskielto_array[$index][$tuoteno] = $palautus[$index]["palautuskielto"];
      }
    }

    // Valmistuksissa haetaan perheiden perheitä mukaan valmistukseen!!!!!! (vain kun rivi lisätään $rivitunnus == 0)
    if ($laskurow['tila'] == 'V'
      and $var != "W"
      and (int) $rivitunnus == 0
      and ($yhtiorow["rekursiiviset_reseptit"] == "Y" or ($yhtiorow["rekursiiviset_reseptit"] == "X" and $avaa_rekursiiviset == "JOO"))) {

      if ($kpl != '' and !is_array($kpl_array)) {
        $kpl_array[$tuoteno_array[0]] = $kayttajan_kpl;
      }

      //funktio populoi globaalit muuttujat $tuoteno_array $kpl_array $kommentti_array $lapsenlap_array
      pupesoft_lisaa_valmisteen_rekursiiviset_reseptit();
    }

    foreach ($tuoteno_array as $tuoteno) {

      $tuoteno = trim($tuoteno);

      $toimvva = (int) $toimvva;
      $toimkka = (int) $toimkka;
      $toimppa = (int) $toimppa;

      if (checkdate($toimkka, $toimppa, $toimvva)) {
        $toimaika = $toimvva."-".$toimkka."-".$toimppa;
      }

      $keraysvva = (int) $keraysvva;
      $kerayskka = (int) $kerayskka;
      $keraysppa = (int) $keraysppa;

      if (checkdate($kerayskka, $keraysppa, $keraysvva)) {
        $kerayspvm = $keraysvva."-".$kerayskka."-".$keraysppa;
      }

      if ($toim != "YLLAPITO") {
        if ($toimaika == "" or $toimaika == "0000-00-00") {
          $toimaika = $laskurow["toimaika"];
        }

        if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
          $kerayspvm = substr($laskurow["kerayspvm"], 0, 10);
        }
      }

      $varasto = $laskurow["varasto"];

      // Ennakkotilaukset,
      // Tarjoukset,
      // Ylläpitosopimukset,
      // ja Valmistukset eivät tee saldotsekkiä

      $_1 = ($laskurow["tilaustyyppi"] == "E");
      $_2 = ($laskurow["tila"] == "T");
      $_3 = ($laskurow["tilaustyyppi"] == "0");
      $_4 = ($laskurow["tila"] == "V");

      if ($_1 or $_2 or $_3 or $_4) {
        $varataan_saldoa = "EI";
      }
      else {
        $varataan_saldoa = "";
      }

      // Jos ei haluta JT-rivejä
      $jtkielto = $laskurow['jtkielto'];

      //Tehdään muuttujaswitchit
      if (is_array($hyvityssaanto_hinta_array)) {
        $hinta = $hyvityssaanto_hinta_array[$hyvityssaanto_indeksi][$tuoteno];
      }
      elseif (is_array($hinta_array)) {
        $hinta = $hinta_array[$tuoteno];
      }
      else {
        $hinta = $kayttajan_hinta;
      }

      // hyvityssäännön alennus yliajaa käyttäjän syöttämän alennuksen
      if (is_array($hyvityssaanto_ale_array)) {
        $ale1 = $hyvityssaanto_ale_array[$hyvityssaanto_indeksi][$tuoteno];

        for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          ${'ale'.$alepostfix} = 0;
        }
      }
      else {
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          if (is_array(${'ale_array'.$alepostfix})) {
            ${'ale'.$alepostfix} = ${'ale_array'.$alepostfix}[$tuoteno];
          }
          else {
            ${'ale'.$alepostfix} = ${'kayttajan_ale'.$alepostfix};
          }
        }
      }

      if (is_array($netto_array)) {
        $netto = strtoupper(trim($netto_array[$tuoteno]));
      }
      else {
        $netto = $kayttajan_netto;
      }

      if (is_array($var_array)) {
        $var = $var_array[$tuoteno];
      }
      else {
        $var = $kayttajan_var;
      }

      if (is_array($hyvityssaanto_kpl_array)) {
        $kpl = $hyvityssaanto_kpl_array[$hyvityssaanto_indeksi][$tuoteno];
      }
      elseif (is_array($kpl_array)) {
        $kpl = $kpl_array[$tuoteno];
      }
      else {
        $kpl = $kayttajan_kpl;
      }

      if (is_array($alv_array)) {
        $alv = $alv_array[$tuoteno];
      }
      else {
        $alv = $kayttajan_alv;
      }

      if (is_array($paikka_array)) {
        $paikka = $paikka_array[$tuoteno];
      }
      else {
        $paikka = $kayttajan_paikka;
      }

      if ($kukarow["extranet"] != '' and $toim == "EXTRANET_REKLAMAATIO") {
        $kpl = abs($kpl)*-1;
      }
      elseif ($kukarow["extranet"] != '') {
        $kpl = abs($kpl);
      }

      if (is_array($hyvityssaanto_kommentti_array)) {
        // jos myyjä on reklamaatiossa syöttänyt riville kommentin niin se laitetaan ensimmäiselle sille tarkoitetulle riville
        if ($kommentti !="") {
          $kommentti .= " ";
        }
        $kommentti .= $hyvityssaanto_kommentti_array[$hyvityssaanto_indeksi][$tuoteno];
      }
      elseif (isset($kommentti_array[$tuoteno])) {
        $kommentti = $kommentti_array[$tuoteno];
      }

      if (is_array($hyvityssaanto_palautuskielto_array)) {
        $hyvityssaannon_palautuskielto =  $hyvityssaanto_palautuskielto_array[$hyvityssaanto_indeksi][$tuoteno];
      }
      else {
        $hyvityssaannon_palautuskielto = "";
      }

      $query  = "SELECT *
                 FROM tuote
                 WHERE tuoteno = '$tuoteno'
                 AND yhtio     = '$kukarow[yhtio]'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        //Tuote löytyi
        $trow = mysql_fetch_assoc($result);

        //extranettajille ei myydä tuotteita joilla ei ole myyntihintaa
        if ($kukarow["extranet"] != '' and $trow["myyntihinta"] == 0 and $trow['ei_saldoa'] == '') {
          $varaosavirhe = t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!")."<br>";
          $trow    = "";
          $tuoteno = "";
          $kpl   = 0;
        }

        if ($kukarow["extranet"] != '' and trim($trow["vienti"]) != '') {

          // vientikieltokäsittely:
          // +maa tarkoittaa että myynti on kielletty tähän maahan
          // -maa tarkoittaa että ainoastaan tähän maahan saa myydä
          // eli näytetään vaan tuotteet jossa vienti kentässä on tyhjää tai -maa.. ja se ei saa olla +maa

          if (strpos(strtoupper($trow["vienti"]), strtoupper("+$laskurow[toim_maa]")) !== FALSE and strpos($trow["vienti"], "+") !== FALSE) {
            //ei saa myydä tähän maahan
            $varaosavirhe = t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!")."<br>";
            $trow    = "";
            $tuoteno = "";
            $kpl   = 0;
            $kielletty++;
          }

          if (strpos(strtoupper($trow["vienti"]), strtoupper("-$laskurow[toim_maa]")) === FALSE and strpos($trow["vienti"], "-") !== FALSE) {
            //ei saa myydä tähän maahan
            $varaosavirhe = t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!")."<br>";
            $trow    = "";
            $tuoteno = "";
            $kpl   = 0;
            $kielletty++;
          }
        }

        if ($trow['hinnastoon'] == 'V' and $toim != "SIIRTOLISTA" and $toim != 'VALMISTAVARASTOON') {
          //  katsotaan löytyyko asiakasalennus / asikakashinta

          // Reklamaatiolla ei huomioida kappalemääriin sidottuja alennuksia
          if ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") {
            $alemaara = 99999999999;
          }
          else {
            $alemaara = $kpl;
          }

          if (!saako_myyda_private_label($laskurow['liitostunnus'], $trow["tuoteno"], $alemaara)) {
            if ($kukarow['extranet'] != '') {
              $varaosavirhe .= t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!")."<br>";
            }
            else {
              $varaosavirhe .= t("VIRHE: Tuotetta ei saa myydä tälle asiakkaalle!")."<br>";
            }

            $trow    = "";
            $tuoteno = "";
            $kpl   = 0;
            $kielletty++;
          }
        }
      }
      elseif ($kukarow["extranet"] != '') {
        $varaosavirhe = t("VIRHE: Tuotenumeroa ei löydy järjestelmästä!")."<br>";
        $tuoteno = "";
        $kpl   = 0;
      }
      else {
        //Tuotetta ei löydy, aravataan muutamia muuttujia
        $trow["alv"] = $laskurow["alv"];
      }

      if ($tuoteno != '' and $kpl != 0) {
        require 'lisaarivi.inc';
      }

      $hinta   = '';
      $netto   = '';
      $var   = '';
      $kpl   = '';
      $alv   = '';
      $paikka  = '';
      $rekisterinumero = '';
      $hyvityssaanto_indeksi++;
      $lisatty++;

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        ${'ale'.$alepostfix} = '';
      }
    }

    // Jos tämä on suoratoimitusrivi päivitetään sille tallenettu toimitettuaika
    // $lisatty_tun ja $lisatied_row tulee lisaarivi.inc:stä...
    if (    $lisatty_tun > 0
      and isset($lisatied_row["suoraan_laskutukseen"])
      and $lisatied_row["suoraan_laskutukseen"] != ""
      and $lisatied_row["tilausrivilinkki"] > 0
    ) {
      //Tutkitaan löytyykö ostorivi ja sen toimitettuaika
      $query = "SELECT tilausrivin_lisatiedot.suoratoimitettuaika
                FROM tilausrivi
                LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
                WHERE tilausrivi.yhtio                          = '$kukarow[yhtio]'
                AND tilausrivi.tyyppi                           = 'O'
                AND tilausrivi.tunnus                           = '$tilausrivi[tilausrivilinkki]'
                AND tilausrivin_lisatiedot.suoratoimitettuaika != '0000-00-00'";
      $suoratoimresult = pupe_query($query);

      if ($suoratoimrow = mysql_fetch_assoc($suoratoimresult)) {

        $toimquery = "UPDATE tilausrivi
                      SET keratty   = '$kukarow[kuka]',
                      kerattyaika    = '$suoratoimrow[suoratoimitettuaika]',
                      toimitettu     = '$kukarow[kuka]',
                      toimitettuaika = '$suoratoimrow[suoratoimitettuaika]'
                      WHERE yhtio    = '$kukarow[yhtio]'
                      AND otunnus    = '$kukarow[kesken]'
                      AND tunnus     = '$lisatty_tun'";
        $toimupdres = pupe_query($toimquery);
      }
    }

    if ($lisavarusteita == "ON" and $perheid2 > 0) {
      //Päivitetään isälle perheid2 jotta tiedetään, että lisävarusteet on nyt lisätty
      $query = "UPDATE tilausrivi set
                perheid2    = '$perheid2'
                where yhtio = '$kukarow[yhtio]'
                and tunnus  = '$perheid2'";
      $updres = pupe_query($query);
    }

    if ($tapa == "VAIHDA" and $perheid2 > 0 and $kayttajan_paikka != "" and substr($kayttajan_paikka, 0, 3) != "¡¡¡" and substr($kayttajan_paikka, 0, 3) != "!!!") {
      //Päivitetään tehdaslisävarusteille kanssa sama varastopaikka kuin isätuotteelle
      $p2paikka = explode("#!¡!#", $kayttajan_paikka);

      $query = "UPDATE tilausrivi set
                hyllyalue    = '$p2paikka[0]',
                hyllynro     = '$p2paikka[1]',
                hyllyvali    = '$p2paikka[2]',
                hyllytaso    = '$p2paikka[3]'
                where yhtio  = '$kukarow[yhtio]'
                and perheid2 = '$perheid2'";
      $updres = pupe_query($query);
    }

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      ${'ale'.$alepostfix} = "";
      ${'ale_array'.$alepostfix} = "";
      ${'kayttajan_ale'.$alepostfix} = "";
    }

    $alv         = "";
    $alv_array       = "";
    $hinta         = "";
    $hinta_array     = "";
    $kayttajan_alv     = "";
    $kayttajan_hinta  = "";
    $kayttajan_kpl     = "";
    $kayttajan_netto   = "";
    $kayttajan_var     = "";
    $kerayspvm       = "";
    $kommentti       = "";
    $kpl         = "";
    $kpl_array       = "";
    $netto         = "";
    $netto_array     = "";
    $paikat       = "";
    $paikka       = "";
    $paikka_array     = "";
    $perheid       = 0;
    $perheid2       = 0;
    $rivinumero     = "";
    $rivitunnus     = 0;
    $toimaika       = "";
    $tuotenimitys     = "";
    $tuoteno       = "";
    $tuoteno_array     = "";
    $var         = "";
    $var_array       = "";
    $sopimuksen_lisatieto1 = "";
    $sopimuksen_lisatieto2 = "";
    if (!isset($lisaa_jatka)) $variaatio_tuoteno = "";
    $omalle_tilaukselle = "";
    $valmistuslinja     = "";
    $avaa_rekursiiviset = "";
    $tila = "";
    $keratty = "";
    $kerattyaika = 0;
    $toimitettu = '';
    $toimitettuaika = 0;
  }

  $logmaster_errors = logmaster_verify_order($laskurow['tunnus'], $toim);

  foreach ($logmaster_errors as $error) {
    echo "<font class='error'>";
    echo "{$error}<br><br>";
    echo "</font>";
    $tilausok++;
  }

  $numres_saatavt  = 0;

  if ((int) $kukarow["kesken"] > 0) {
    //Näytetäänko asiakkaan saatavat!
    $query  = "SELECT yhtio
               FROM tilausrivi
               WHERE yhtio  = '$kukarow[yhtio]'
               AND otunnus  = '$kukarow[kesken]'
               AND tyyppi  != 'D'";
    $numres = pupe_query($query);
    $numres_saatavt = mysql_num_rows($numres);
  }

  $_kukaextranet = ($kukarow['extranet'] == '');

  $_saako_nahda = ($kukarow['saatavat'] != '2' and ($kukarow['kassamyyja'] == '' or $kukarow['saatavat'] == '1'));
  $_saako_nayttaa = ($kaytiin_otsikolla == "NOJOO!" or $numres_saatavt == 0);
  $_saako = ($_saako_nahda and $_saako_nayttaa);

  $_kateinen = empty($meapurow['kateinen']);
  $_jv = empty($meapurow['jv']);
  $_kat_jv = ($_kateinen and $_jv);

  $_asiakas = ($laskurow['liitostunnus'] > 0);
  $_mika_toim = in_array($toim, array("RIVISYOTTO", "PIKATILAUS", "ENNAKKO", "EXTENNAKKO", "VALMISTAASIAKKAALLE"));
  $_mika_toim = ($_mika_toim and $laskurow['clearing'] != 'HYVITYS');

  $_luottoraja_ylivito = false;

  $_keratty_toimitettu  = ($laskurow["tila"] == "L" and in_array($laskurow["alatila"], array("B", "C", "D", "E")));
  $_luottoraja_ylitys   = (in_array($yhtiorow["luottorajan_ylitys"], array("J", "K")));
  $_keratty_ja_ylitetty = FALSE;

  if ($_kukaextranet and $_kat_jv and $_asiakas and $_saako and $_mika_toim) {

    // Parametrejä saatanat.php:lle
    $sytunnus          = $laskurow['ytunnus'];
    $sliitostunnus     = $laskurow['liitostunnus'];
    $eiliittymaa       = "ON";
    $luottorajavirhe   = "";
    $jvvirhe           = "";
    $ylivito           = 0;
    $trattavirhe       = "";
    $laji              = "MA";
    $grouppaus         = ($yhtiorow["myyntitilaus_saatavat"] == "Y") ? "ytunnus" : "";
    $_avoimia_yhteensa = 0;

    pupeslave_start();
    ob_start();
    require "raportit/saatanat.php";
    $retval = ob_get_contents();
    ob_end_clean();
    pupeslave_stop();

    $query_ale_lisa = generoi_alekentta('M');

    $query = "SELECT sum(round(
              hinta * (varattu+jt+kpl) * {$query_ale_lisa},
              {$yhtiorow['hintapyoristys']}
              )) rivihinta
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tyyppi  = 'L'
              AND otunnus = '{$laskurow['tunnus']}'";
    $_tilauksen_rivihinnat_res = pupe_query($query);
    $_tilauksen_rivihinnat_row = mysql_fetch_assoc($_tilauksen_rivihinnat_res);

    if (!empty($_avoimia_yhteensa)) {
      $_avoimia_yhteensa = (float) $_avoimia_yhteensa;
      $_tilauksen_rivihinnat_row['rivihinta'] = (float) $_tilauksen_rivihinnat_row['rivihinta'];

      $query = "UPDATE lasku SET
                luottoraja  = {$_avoimia_yhteensa} - {$_tilauksen_rivihinnat_row['rivihinta']}
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$laskurow['tunnus']}'";
      $upd_res = pupe_query($query);

      $laskurow['luottoraja'] = $_avoimia_yhteensa - $_tilauksen_rivihinnat_row['rivihinta'];
    }

    if (trim($retval) != "" and $kukarow['hinnat'] == 0 and $_saako_nayttaa) {
      echo "<br>$retval";
    }

    if ($luottorajavirhe != '') {
      echo "<br/>";
      echo "<font class='error'>", t("HUOM: Luottoraja ylittynyt"), ". </font>";

      if ($yhtiorow['luottorajan_ylitys'] != '' and !$_keratty_toimitettu) {
        echo "<font class='error'>", t("Ota yhteys luotonvalvontaan tai mitätöi myyntitilaus"), "! ";
        echo t("Asiakkaalle voi kuitenkin myydä käteismaksuehdolla"), ".";
        echo "</font>";
      }

      echo "<br/>";

      if ($_keratty_toimitettu and $_luottoraja_ylitys) {
        echo "<font class='error'>", t("Tilaus on jo kerätty ja/tai toimitettu"), ". ";
        echo t("Uusia rivejä ei voi luottorajan ylityttyä lisätä"), "! ";
        echo "</font><br />";
        $_keratty_ja_ylitetty = TRUE;
      }

      if ($yhtiorow['luottorajan_ylitys'] == "L" or $yhtiorow['luottorajan_ylitys'] == "M" or $_keratty_ja_ylitetty) {
        $muokkauslukko = 'LUKOSSA';
        $_luottoraja_ylivito = true;
      }
    }

    if ($jvvirhe != '') {
      echo "<br/>";
      echo "<font class='error'>", t("HUOM: Tämä on jälkivaatimusasiakas"), "!</font>";
      echo "<br/>";
    }

    $query = "UPDATE lasku SET
              erapaivan_ylityksen_summa = '{$ylivito}'
              WHERE yhtio               = '{$kukarow['yhtio']}'
              AND tunnus                = '{$laskurow['tunnus']}'";
    $upd_res = pupe_query($query);

    $laskurow['erapaivan_ylityksen_summa'] = $ylivito;

    if ($ylivito > 0) {

      echo "<br/>";
      echo "<font class='error'>".t("HUOM: Asiakkaalla on yli %s päivää sitten erääntyneitä laskuja, olkaa ystävällinen ja ottakaa yhteyttä myyntireskontran hoitajaan", $kukarow['kieli'], $yhtiorow['erapaivan_ylityksen_raja'])."!</font>";

      if ($yhtiorow['erapaivan_ylityksen_toimenpide'] != '') {
        echo " <font class='error'>", t("Asiakkaalle voi kuitenkin myydä käteismaksuehdolla"), ".";
        echo "</font>";
        echo "<br/>";
      }

      if ($yhtiorow['erapaivan_ylityksen_toimenpide'] == "L" or $yhtiorow['erapaivan_ylityksen_toimenpide'] == "M") {
        $muokkauslukko = 'LUKOSSA';
        $_luottoraja_ylivito = true;
      }
    }

    if ($trattavirhe != '') {
      echo "<br/>";
      echo "<font class='error'>".t("HUOM: Asiakkaalla on maksamattomia trattoja")."!<br></font>";
      echo "<br/>";
    }

    if ($yhtiorow["myyntitilaus_asiakasmemo"] == "K") {
      echo "<br>";
      $ytunnus  = $laskurow['ytunnus'];
      $asiakasid  = $laskurow['liitostunnus'];
      require "crm/asiakasmemo.php";
    }
  }
  elseif ($_mika_toim and $_kat_jv) {

    if ((float) $laskurow['luottoraja'] != 0) {
      if ((float) $asiakasrow['luottoraja'] != 0) {

        $query_ale_lisa = generoi_alekentta('M');

        $query = "SELECT sum(round(
                  hinta * (varattu+jt+kpl) * {$query_ale_lisa},
                  {$yhtiorow['hintapyoristys']}
                  )) rivihinta
                  FROM tilausrivi
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND tyyppi   = 'L'
                  AND var     != 'P'
                  AND otunnus  = '{$laskurow['tunnus']}'";
        $tilauksen_rivihinnat_res = pupe_query($query);
        $tilauksen_rivihinnat_row = mysql_fetch_assoc($tilauksen_rivihinnat_res);

        if ($tilauksen_rivihinnat_row['rivihinta'] + $laskurow['luottoraja'] > $asiakasrow['luottoraja']) {

          $luottorajavirhe = 'kyllä';

          echo "<br/>";
          echo "<font class='error'>", t("HUOM: Luottoraja ylittynyt"), ". </font>";

          if ($yhtiorow['luottorajan_ylitys'] != '' and !$_keratty_toimitettu) {
            echo "<font class='error'>", t("Ota yhteys luotonvalvontaan tai mitätöi myyntitilaus"), "! ";
            echo t("Asiakkaalle voi kuitenkin myydä käteismaksuehdolla"), ". ";
            echo "</font>";
          }

          echo "<br/>";

          if ($_keratty_toimitettu and $_luottoraja_ylitys) {
            echo "<font class='error'>", t("Tilaus on jo kerätty ja/tai toimitettu"), ". ";
            echo t("Uusia rivejä ei voi luottorajan ylityttyä lisätä"), "! ";
            echo "</font><br />";
            $_keratty_ja_ylitetty = TRUE;
          }

          if ($yhtiorow['luottorajan_ylitys'] == "L" or $yhtiorow['luottorajan_ylitys'] == "M" or $_keratty_ja_ylitetty) {
            $muokkauslukko = 'LUKOSSA';
            $_luottoraja_ylivito = true;
          }
        }
      }
    }

    $_ylivito_chk = $laskurow['erapaivan_ylityksen_summa'] * 100000;

    if ($_ylivito_chk > 0) {

      $ylivito = $laskurow['erapaivan_ylityksen_summa'];
      echo "<br/>";
      echo "<font class='error'>".t("HUOM: Asiakkaalla on yli %s päivää sitten erääntyneitä laskuja, olkaa ystävällinen ja ottakaa yhteyttä myyntireskontran hoitajaan", $kukarow['kieli'], $yhtiorow['erapaivan_ylityksen_raja'])."!</font>";

      if ($yhtiorow['erapaivan_ylityksen_toimenpide'] != '') {
        echo " <font class='error'>", t("Asiakkaalle voi kuitenkin myydä käteismaksuehdolla"), ".";
        echo "</font>";
        echo "<br/>";
      }

      if ($yhtiorow['erapaivan_ylityksen_toimenpide'] == "L" or $yhtiorow['erapaivan_ylityksen_toimenpide'] == "M") {
        $muokkauslukko = 'LUKOSSA';
        $_luottoraja_ylivito = true;
      }
    }
  }

  // Allr specific!
  if (file_exists("${pupe_root_polku}/allr_kamppikset.php")) {
    require "${pupe_root_polku}/allr_kamppikset.php";
  }

  //Syöttörivi
  if (($muokkauslukko == "" or (!empty($rivitunnus) and $_keratty_ja_ylitetty)) and ($toim != "PROJEKTI" or $rivitunnus != 0) or $toim == "YLLAPITO") {
    echo "<table><tr>$jarjlisa<td class='back'><font class='head'>".t("Lisää rivi")."</font></td></tr></table>";

    if ($toim == 'VALMISTAVARASTOON' and $tila != 'LISAAKERTARESEPTIIN' and $tila != 'LISAAISAKERTARESEPTIIN') {

      $_chk = array($valmiste_vai_raakaaine => 'checked') + array('raakaaine' => '', 'valmiste' => '');

      echo t("Raaka-aine"), " <input type='radio' name='valmiste_vai_raakaaine' value='raakaaine' {$_chk['raakaaine']} /> ";
      echo t("Valmiste"), " <input type='radio' name='valmiste_vai_raakaaine' value='valmiste' {$_chk['valmiste']} />";
    }

    echo "<input type='hidden' id='tilausrivin_esisyotto_parametri' value= '{$yhtiorow['tilausrivin_esisyotto']}' />";

    require "syotarivi.inc";
  }
  else {
    echo "</form></table>";
  }

  // Sarakemäärä ruudulla
  $sarakkeet = 0;
  $sarakkeet_alku = 0;

  // Sarakkeiden otsikot
  $headerit = "<tr>$jarjlisa<th>#</th>";
  $sarakkeet++;

  if ($toim == "REKLAMAATIO") {

    pupeslave_start();

    $query = "SELECT asiakas.*
              FROM asiakkaan_avainsanat
              JOIN asiakas ON (asiakas.yhtio=asiakkaan_avainsanat.yhtio and asiakas.tunnus=asiakkaan_avainsanat.liitostunnus)
              WHERE asiakkaan_avainsanat.yhtio    = '$kukarow[yhtio]'
              and asiakkaan_avainsanat.laji       = 'TPALAUTUS'
              and asiakkaan_avainsanat.avainsana != ''";
    $tpares = pupe_query($query);

    if (mysql_num_rows($tpares) > 0) {
      $siirtovarastot = hae_mahdolliset_siirto_varastot(array($laskurow['varasto']));
      if (!empty($siirtovarastot)) {
        $headerit .= "<th>".t("Palauta toimittajalle")."<br/>".t('Palauta varastoon')."</th>";
      }
      else {
        $headerit .= "<th>".t("Palauta toimittajalle")."</th>";
      }
      $sarakkeet++;

      $toimpalautusasiakkat = "";

      while ($trrow = mysql_fetch_assoc($tpares)) {
        $toimpalautusasiakkat .= $trrow['tunnus'].",";
      }

      $toimpalautusasiakkat = substr($toimpalautusasiakkat, 0, -1);
    }

    pupeslave_stop();
  }

  // erikoisceisi, jos halutaan PIENITUOTEKYSELY tilaustaulussa, mutta emme halua näyttää niitä kun lisätään lisävarusteita
  if ((($tuoteno != '' or (is_array($tuoteno_array) and count($tuoteno_array) > 1)) and $kpl == '' and $kukarow['extranet'] == '') or ($toim == "REKLAMAATIO" and isset($trow['tuoteno']) and $trow['tuoteno'] != '' and $kukarow['extranet'] == '')) {

    if ($toim == "REKLAMAATIO" and $tuoteno == '') {
      $tuoteno_lisa = $trow['tuoteno'];
    }
    elseif (is_array($tuoteno_array)) {
      $tuoteno_lisa = implode("','", $tuoteno_array);
    }
    else {
      $tuoteno_lisa = $tuoteno;
    }

    pupeslave_start();

    $query  = "SELECT *
               from tuote
               where tuoteno IN ('{$tuoteno_lisa}')
               and yhtio     = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 0) {

      while ($tuote = mysql_fetch_assoc($result)) {
        //kursorinohjausta
        if (($toim == "REKLAMAATIO" and $tuoteno == '') or (is_array($tuoteno_array) and count($tuoteno_array) > 1)) {
          $kentta = 'tuoteno';
        }
        else {
          $kentta = 'kpl';
        }

        echo "<br>";
        echo "<table>";
        echo "<tr>$jarjlisa<td class='back pnopad ptop'>";

        echo "<table>
          <tr><th colspan='2'>".t_tuotteen_avainsanat($tuote, 'nimitys')."</th></tr>
          <tr><th>", t("Tuoteno"), "</th><td>{$tuote['tuoteno']}</td></tr>";

        echo "<tr><th>".t("Hinta")."</th><td align='right'>".hintapyoristys($tuote['myyntihinta'])." $yhtiorow[valkoodi]</td></tr>";

        $myyntierahinnat = hae_alehinta_minkpl(hae_asiakkaan_minkpl($laskurow['liitostunnus']), $laskurow, $tuote);

        foreach ($myyntierahinnat as $myyntiera => $myyntierahinta) {
          if ($myyntiera > 1) echo "<tr><th>".t("Hinta")." > $myyntiera $tuote[yksikko]</th><td align='right'>".hintapyoristys($myyntierahinta["hinta"])." $yhtiorow[valkoodi]</td></tr>";
        }

        if ($yhtiorow['naytetaanko_ale_peruste_tilausrivilla'] != '') {
          $haettu_alehinta = alehinta($laskurow, $tuote, $kpl, $netto, $hinta, $ale);

          $ap_font = "<font>";
          $ap_text = "";

          // Onko asiakasalennusta?
          preg_match_all("/XXXALEPERUSTE:([0-9]*)/", $ale_peruste, $ap_match);

          foreach ($ap_match[1] as $apnumero) {
            if ($apnumero >= 5 and $apnumero < 13) {
              $ap_font  = "<font class='ok'>";
              $ap_text .= t("Asiakasalennus");
              break;
            }
          }

          // Onko asiakashintaa
          preg_match("/XXXHINTAPERUSTE:([0-9]*)/", $ale_peruste, $ap_match);

          // Jos tuote näytetään vain jos asiakkaalla on asiakasalennus tai asiakahinta niin skipataan se jos alea tai hintaa ei löydy
          if ($ap_match[1] > 1 and $ap_match[1] <= 13) {
            $ap_font = "<font class='ok'>";

            if ($ap_text != "") $ap_text .= " / ";
            $ap_text .= t("Asiakashinta");
          }

          if (isset($ale_peruste) and !empty($ale_peruste) and $haettu_alehinta > 1 and $yhtiorow['naytetaanko_ale_peruste_tilausrivilla'] == 'o') {
            echo "<tr><th>{$ap_font}".substr($ale_peruste, 0, strpos($ale_peruste, "Hinta: "))."</font></th><td align='right'>{$ap_font}".hintapyoristys($haettu_alehinta[0])." $yhtiorow[valkoodi]</font></td></tr>";
          }
          elseif ($ap_text != "" and $yhtiorow['naytetaanko_ale_peruste_tilausrivilla'] == 't') {
            echo "<tr><th>{$ap_font} {$ap_text}</font></th><td align='right'>{$ap_font}".hintapyoristys($haettu_alehinta[0])." $yhtiorow[valkoodi]</font></td></tr>";
          }
        }

        if ($tuote["nettohinta"] != 0) {
          echo "<tr><th>".t("Nettohinta")."</th><td align='right'>".hintapyoristys($tuote['nettohinta'])." $yhtiorow[valkoodi]</td></tr>";
          if ($tuote["myyntihinta_maara"] != 0) {
            echo "<tr><th>".t("Nettohinta")." $tuote[myyntihinta_maara] $tuote[yksikko]</th><td align='right'>".hintapyoristys($tuote['nettohinta'] * $tuote["myyntihinta_maara"])." $yhtiorow[valkoodi]</td></tr>";
          }
        }

        if ($tuote["myymalahinta"] != 0) {
          echo "<tr><th>".t("Myymalahinta")."</th><td align='right'>".hintapyoristys($tuote['myymalahinta'])." $yhtiorow[valkoodi]</td></tr>";
          if ($tuote["myyntihinta_maara"] != 0) {
            echo "<tr><th>".t("Myymalahinta")." $tuote[myyntihinta_maara] $tuote[yksikko]</th><td align='right'>".hintapyoristys($tuote['myymalahinta'] * $tuote["myyntihinta_maara"])." $yhtiorow[valkoodi]</td></tr>";
          }
        }

        if ($kukarow['extranet'] == '' and $naytetaanko_kate) {

          $epakurpantti = "";

          if ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y")) {
            if ($tuote['epakurantti100pvm'] != '0000-00-00') {
              $tuote['kehahin'] = 0;
              $epakurpantti = "(".t("Täysepäkurantti").")";
            }
            elseif ($tuote['epakurantti75pvm'] != '0000-00-00') {
              $tuote['kehahin'] = round($tuote['kehahin'] * 0.25, 6);
              $epakurpantti = "(".t("75% Epäkurantti").")";
            }
            elseif ($tuote['epakurantti50pvm'] != '0000-00-00') {
              $tuote['kehahin'] = round($tuote['kehahin'] * 0.5,  6);
              $epakurpantti = "(".t("Puoliepäkurantti").")";
            }
            elseif ($tuote['epakurantti25pvm'] != '0000-00-00') {
              $tuote['kehahin'] = round($tuote['kehahin'] * 0.75, 6);
              $epakurpantti = "(".t("25% Epäkurantti").")";
            }
          }

          if ($kukarow["yhtio"] == "srs") {
            echo "<tr><th>".t("Hinta 25% katteella")."</th><td align='right'>".hintapyoristys($tuote['kehahin'] / 0.75)." $yhtiorow[valkoodi]</td></tr>";
          }

          echo "<tr><th>".t("Keskihankintahinta")." $epakurpantti</th><td align='right'>".hintapyoristys($tuote['kehahin'])." $yhtiorow[valkoodi]</td></tr>";

          if ($tuote["myyntihinta_maara"] != 0) {
            echo "<tr><th>".t("Keskihankintahinta")." $epakurpantti $tuote[myyntihinta_maara] $tuote[yksikko]</th>";
            echo "<td align='right'>".hintapyoristys($tuote['kehahin'] * $tuote["myyntihinta_maara"])." $yhtiorow[valkoodi]</td></tr>";
          }
        }

        $query_ale_select_lisa = generoi_alekentta_select('erikseen', 'M');

        $cur_date = date('Y-m-d');
        $date_2yo = date("Y-m-d", mktime(0, 0, 0, date("n"), date("j"), date("Y")-2));

        // Jos kahden vuoden aikarajaus ylittyy, breikataan looppi
        while ($cur_date >= $date_2yo) {

          $pre_date = $cur_date;
          $cur_date = explode('-', $cur_date);
          $cur_date = date("Y-m-d", mktime(0, 0, 0, $cur_date[1]-1, $cur_date[2], $cur_date[0]));

          //haetaan viimeisin hinta millä asiakas on tuotetta ostanut
          $query = "SELECT tilausrivi.hinta,
                    tilausrivi.otunnus,
                    tilausrivi.laskutettuaika,
                    {$query_ale_select_lisa}
                    lasku.tunnus,
                    lasku_ux.tunnus AS ux_tunnus,
                    lasku_ux.laskunro AS ux_laskunro
                    FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika)
                    JOIN lasku USE INDEX (PRIMARY) ON (
                      lasku.yhtio                  = tilausrivi.yhtio AND
                      lasku.tunnus                 = tilausrivi.otunnus AND
                      lasku.liitostunnus           = '{$laskurow['liitostunnus']}' AND
                      lasku.tila                   = 'L' AND
                      lasku.alatila                = 'X'
                    )
                    JOIN lasku AS lasku_ux ON (
                      lasku_ux.yhtio               = lasku.yhtio AND
                      lasku_ux.tunnus              = tilausrivi.uusiotunnus
                    )
                    WHERE tilausrivi.yhtio         = '{$kukarow['yhtio']}'
                    AND tilausrivi.tyyppi          = 'L'
                    AND tilausrivi.tuoteno         = '{$tuote['tuoteno']}'
                    AND tilausrivi.laskutettuaika  <= '{$pre_date}'
                    AND tilausrivi.laskutettuaika  >= '{$cur_date}'
                    AND tilausrivi.kpl            != 0
                    ORDER BY tilausrivi.tunnus DESC
                    LIMIT 1";
          $viimhintares = pupe_query($query);

          if (mysql_num_rows($viimhintares) != 0) {
            $viimhinta = mysql_fetch_assoc($viimhintares);

            echo "<tr>";
            echo "<th>", t("Viimeisin hinta"), "</th>";
            echo "<td align='right'>";
            echo hintapyoristys($viimhinta["hinta"]);
            echo " {$yhtiorow['valkoodi']}";
            echo "</td>";
            echo "</tr>";

            for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              echo "<tr>";
              echo "<th>", t("Viimeisin alennus"), "{$alepostfix}</th>";
              echo "<td align='right'>", $viimhinta["ale{$alepostfix}"], " %</td>";
              echo "</tr>";
            }

            $_href_pre = "{$palvelin2}raportit/asiakkaantilaukset.php?tee=NAYTA&toim=MYYNTI";
            $_href_post = "&lopetus={$tilmyy_lopetus}//from=LASKUTATILAUS";

            echo "<tr>";
            echo "<th>", t("Tilausnumero"), "</th>";
            echo "<td align='right'>";
            echo "<a href='{$_href_pre}&tunnus={$viimhinta['tunnus']}{$_href_post}'>";
            echo $viimhinta['otunnus'];
            echo "</a>";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<th>", t("Lasku"), "</th>";
            echo "<td align='right'>";
            echo "<a href='{$_href_pre}&tunnus={$viimhinta['ux_tunnus']}{$_href_post}'>";
            echo $viimhinta['ux_laskunro'];
            echo "</a>";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<th>", t("Laskutettu"), "</th>";
            echo "<td align='right'>", tv1dateconv($viimhinta["laskutettuaika"]), "</td>";
            echo "</tr>";

            break;
          }
        }

        if ($trow["ei_saldoa"] == "") {

          $sallitut_maat_lisa = "";

          if ($laskurow["toim_maa"] != '') {
            $sallitut_maat_lisa = " and (varastopaikat.sallitut_maat like '%$laskurow[toim_maa]%' or varastopaikat.sallitut_maat = '') ";
          }

          // Käydään läpi tuotepaikat
          if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
            $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                      tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                      sarjanumeroseuranta.sarjanumero era,
                      concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
                      varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
                       FROM tuote
                      JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                      JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                        AND varastopaikat.tunnus                = tuotepaikat.varasto
                        $sallitut_maat_lisa)
                      JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
                      and sarjanumeroseuranta.tuoteno           = tuote.tuoteno
                      and sarjanumeroseuranta.hyllyalue         = tuotepaikat.hyllyalue
                      and sarjanumeroseuranta.hyllynro          = tuotepaikat.hyllynro
                      and sarjanumeroseuranta.hyllyvali         = tuotepaikat.hyllyvali
                      and sarjanumeroseuranta.hyllytaso         = tuotepaikat.hyllytaso
                      and sarjanumeroseuranta.myyntirivitunnus  = 0
                      and sarjanumeroseuranta.era_kpl          != 0
                      WHERE tuote.yhtio                         = '$kukarow[yhtio]'
                      and tuote.tuoteno                         = '{$tuote['tuoteno']}'
                      GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
                      ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
          }
          else {
            $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                      tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                      concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
                      varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
                       FROM tuote
                      JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                      JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                        AND varastopaikat.tunnus = tuotepaikat.varasto
                        $sallitut_maat_lisa)
                      WHERE tuote.yhtio          = '$kukarow[yhtio]'
                      and tuote.tuoteno          = '{$tuote['tuoteno']}'
                      ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
          }

          $varresult = pupe_query($query);

          $myytavissa_sum = 0;

          if (mysql_num_rows($varresult) > 0) {

            // katotaan jos meillä on tuotteita varaamassa saldoa joiden varastopaikkaa ei enää ole olemassa...
            list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($row["tuoteno"], 'ORVOT', '', '', '', '', '', '', '', $_tm_saldoaikalisa);
            $orvot *= -1;

            while ($saldorow = mysql_fetch_assoc($varresult)) {

              if (!isset($saldorow["era"])) $saldorow["era"] = "";

              list($saldo, $hyllyssa, $myytavissa, $sallittu) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], $laskurow["toim_maa"], $_tm_saldoaikalisa, $saldorow["era"]);

              //  Listataan vain varasto jo se ei ole kielletty
              if ($sallittu === TRUE) {
                // hoidetaan pois problematiikka jos meillä on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
                if ($orvot > 0) {
                  if ($myytavissa >= $orvot) {
                    // poistaan orpojen varaamat tuotteet tältä paikalta
                    $myytavissa = $myytavissa - $orvot;
                    $orvot = 0;
                  }
                  elseif ($orvot > $myytavissa) {
                    // poistetaan niin paljon orpojen saldoa ku voidaan
                    $orvot = $orvot - $myytavissa;
                    $myytavissa = 0;
                  }
                }

                if ($myytavissa != 0) {

                  $id2  = $saldorow['hyllyalue'].$saldorow['hyllynro'];
                  $id2 .= $saldorow['hyllyvali'].$saldorow['hyllytaso'];

                  $id2 = sanitoi_javascript_id($id2);

                  echo "<tr>";
                  echo "<th nowrap>";
                  echo "<a class='tooltip' id='{$id2}'>{$saldorow['nimitys']}</a>";
                  echo " {$saldorow['tyyppi']}";
                  echo "<div id='div_{$id2}' class='popup' style='width: 300px'>(";
                  echo "{$saldorow['hyllyalue']}-{$saldorow['hyllynro']}-";
                  echo "{$saldorow['hyllyvali']}-{$saldorow['hyllytaso']}";
                  echo ")</div>";
                  echo "</th>";

                  echo "<td align='right' nowrap>";
                  echo sprintf("%.2f", $myytavissa)." ".t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite");
                  echo " {$tuote['yksikko']}</td></tr>";
                }

                if ($saldorow["tyyppi"] != "E") {
                  $myytavissa_sum += $myytavissa;
                }
              }
            }
          }

          if ($myytavissa_sum == 0) {
            echo "<tr><th>".t("Myytävissä")."</th><td><font class='error'>".t("Tuote loppu")."</font></td></tr>";
          }
        }

        if ($toim == "REKLAMAATIO" and $toimpalautusasiakkat != "") {

          // Saako tuotteen palauttaa toimittajalle
          $query = "SELECT asiakas.tunnus, asiakas.nimi, if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
                    FROM tuotteen_toimittajat
                    JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
                    JOIN asiakas ON (toimi.yhtio = asiakas.yhtio AND toimi.ytunnus = asiakas.ytunnus and asiakas.tunnus in ({$toimpalautusasiakkat}))
                    LEFT JOIN tuotteen_avainsanat ON (tuotteen_toimittajat.yhtio = tuotteen_avainsanat.yhtio AND tuotteen_toimittajat.tuoteno = tuotteen_avainsanat.tuoteno AND tuotteen_avainsanat.laji = 'toimpalautus')
                    WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
                    AND tuotteen_toimittajat.tuoteno = '$trow[tuoteno]'
                    AND (tuotteen_avainsanat.selite is NULL or tuotteen_avainsanat.selite = '')
                    ORDER BY sorttaus";
          $abures = pupe_query($query);

          if (mysql_num_rows($abures) > 0) {
            while ($trrow = mysql_fetch_assoc($abures)) {
              echo "<tr><th>".t("Voidaan palauttaa toimittajalle")."</th><td>{$trrow['nimi']}</td></tr>";
            }
          }
        }

        echo "</table>";
        echo "</td>";

        if (in_array($toim, array('RIVISYOTTO', 'PIKATILAUS', 'REKLAMAATIO'))) {

          $oikeus_chk = tarkista_oikeus("tuote.php");

          $_html_rows = "";
          $_html = "<td class='back pnopad ptop'>{$jarjlisa}";

          $_html .= "<table>";
          $_html .= "<tr>";
          $_html .= "<th>".t("Laatija")."</th>";
          $_html .= "<th>".t("Pvm")."</th>";
          $_html .= "<th>".t("Määrä")."</th>";

          if ($oikeus_chk) {
            $_html .= "<th>".t("Kplhinta")."</th>";
            $_html .= "<th>".t("Rivihinta")."</th>";
          }

          $_html .= "</tr>";

          $_rows_added = 0;

          $cur_date = date('Y-m-d');
          $date_2yo = date("Y-m-d", mktime(0, 0, 0, date("n"), date("j"), date("Y")-2));

          // Jos kahden vuoden aikarajaus ylittyy, breikataan looppi
          while ($cur_date >= $date_2yo) {

            $pre_date = $cur_date;
            $cur_date = explode('-', $cur_date);
            $cur_date = date("Y-m-d", mktime(0, 0, 0, $cur_date[1]-1, $cur_date[2], $cur_date[0]));

            $query = "SELECT tilausrivi.*,
                      if (kuka.nimi IS NOT NULL AND kuka.nimi != '', kuka.nimi, tilausrivi.laatija) laatija
                      FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika)
                      JOIN lasku USE INDEX (PRIMARY) ON (
                        lasku.yhtio                 = tilausrivi.yhtio AND
                        lasku.tunnus                = tilausrivi.otunnus AND
                        lasku.liitostunnus          = '{$laskurow['liitostunnus']}' AND
                        lasku.tila                  = 'L' AND
                        lasku.alatila               = 'X'
                      )
                      LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.tunnus = lasku.myyja)
                      WHERE tilausrivi.yhtio        = '{$kukarow['yhtio']}'
                      AND tilausrivi.tyyppi         = 'L'
                      AND tilausrivi.tuoteno        = '{$tuote['tuoteno']}'
                      AND tilausrivi.laskutettuaika <= '{$pre_date}'
                      AND tilausrivi.laskutettuaika >= '{$cur_date}'
                      ORDER BY tilausrivi.laskutettuaika DESC, tilausrivi.tunnus DESC";
            $tapahtuma_chk_res = pupe_query($query);

            if (mysql_num_rows($tapahtuma_chk_res) > 0) {

              while ($tapahtuma_chk_row = mysql_fetch_assoc($tapahtuma_chk_res)) {

                $_html_rows .= "<tr>";
                $_html_rows .= "<td>{$tapahtuma_chk_row['laatija']}</td>";
                $_html_rows .= "<td>".tv1dateconv($tapahtuma_chk_row['laskutettuaika'])."</td>";
                $_html_rows .= "<td align='right'>";
                $_html_rows .= "{$tapahtuma_chk_row['kpl']} {$tapahtuma_chk_row['yksikko']}";
                $_html_rows .= "</td>";

                if ($oikeus_chk) {
                  // Onko verolliset hinnat?
                  if ($yhtiorow["alv_kasittely"] == "") {
                    $tapahtuma_chk_row['rivihinta'] *= (1 + $tapahtuma_chk_row["alv"] / 100);
                  }

                  $_kplhinta = $tapahtuma_chk_row['rivihinta'] / $tapahtuma_chk_row['kpl'];

                  $_html_rows .= "<td align='right'>";
                  $_html_rows .= hintapyoristys($_kplhinta);
                  $_html_rows .= "</td>";

                  $_html_rows .= "<td align='right'>";
                  $_html_rows .= hintapyoristys($tapahtuma_chk_row['rivihinta']);
                  $_html_rows .= "</td>";
                }

                $_html_rows .= "</tr>";
                $_rows_added++;

                if ($_rows_added == 5) {
                  break 2;
                }
              }
            }
          }

          if (!empty($_html_rows)) {
            echo $_html;
            echo $_html_rows;
            echo "</table>";
          }
        }

        echo "</td>";
        echo "</tr>";
        echo "</table>";

      }
    }

    pupeslave_stop();
  }

  // jos ollaan jo saatu tilausnumero aikaan listataan kaikki tilauksen rivit..
  if ((int) $kukarow["kesken"] > 0) {

    if ($kukarow['extranet'] != '' and $laskurow['rahtivapaa'] == '' and $asiakasrow['rahtivapaa'] == '' and ($asiakasrow['rahtivapaa_alarajasumma'] != 0 or $yhtiorow['rahtivapaa_alarajasumma'] != 0)) {

      $query_ale_lisa = generoi_alekentta('M');
      $poisrajatut_tuotteet = "'{$yhtiorow["rahti_tuotenumero"]}','{$yhtiorow["jalkivaatimus_tuotenumero"]}','{$yhtiorow["erilliskasiteltava_tuotenumero"]}'";
      $poisrajatut_tuotteet = lisaa_vaihtoehtoinen_rahti_merkkijonoon($poisrajatut_tuotteet);

      $query = "SELECT SUM(
                (tuote.myyntihinta / if ('{$yhtiorow['alv_kasittely']}' = '', (1+tilausrivi.alv/100), 1) * {$query_ale_lisa} * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt))
                +
                (tuote.myyntihinta / if ('{$yhtiorow['alv_kasittely']}' = '', (1+tilausrivi.alv/100), 1) * {$query_ale_lisa} * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * (tilausrivi.alv/100))
                ) rivihinta
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.otunnus = '{$kukarow['kesken']}'
                AND tilausrivi.tuoteno NOT IN ({$poisrajatut_tuotteet})";
      $tilriv_chk_res = pupe_query($query);
      $tilriv_chk_row = mysql_fetch_assoc($tilriv_chk_res);

      if ($tilriv_chk_row['rivihinta'] != '') {

        $rahtivapaa_alarajasumma = $asiakasrow['rahtivapaa_alarajasumma'] != 0 ? $asiakasrow['rahtivapaa_alarajasumma'] : $yhtiorow['rahtivapaa_alarajasumma'];
        $_jaljella = $rahtivapaa_alarajasumma - $tilriv_chk_row['rivihinta'];

        echo "<br /><table>";
        echo "<tr><th>", t("Rahtivapaaseen toimitukseen jäljellä"), "</th></tr>";
        echo "<tr><td>";

        echo $_jaljella > 0 ? sprintf("%.2f", $_jaljella) : 0;
        echo " {$laskurow['valkoodi']}";

        echo "</td></tr>";
        echo "</table>";
      }
    }

    $laskentalisa_riveille = "";

    if (($toim == "RIVISYOTTO" or $toim == "PIKATILAUS" or $toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") and $laskurow["tunnusnippu"] > 0 and $projektilla == "") {
      $tilrivity  = "'L','E'";

      $query = "SELECT GROUP_CONCAT(tunnus) tunnukset
                FROM lasku
                WHERE yhtio     = '$kukarow[yhtio]'
                and tunnusnippu = '$laskurow[tunnusnippu]'";
      $result = pupe_query($query);
      $toimrow = mysql_fetch_assoc($result);

      $tunnuslisa = " and tilausrivi.otunnus in ($toimrow[tunnukset]) ";
    }
    elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
      $tilrivity  = "'L'";
      $tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
    }
    elseif ($toim == "REKLAMAATIO") {
      $tilrivity  = "'L'";
      $tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
    }
    elseif (($toim == "TARJOUS" or $toim == "EXTTARJOUS")) {
      $tilrivity  = "'T'";
      $tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
    }
    elseif ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS" or $toim == "MYYNTITILI") {
      $tilrivity  = "'G'";
      $tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
    }
    elseif ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
      if ($toim == "VALMISTAASIAKKAALLE" and $yhtiorow["raaka_aineet_valmistusmyynti"] == "N") {
        $tilrivity  = "'L','W','M'";
      }
      else {
        $tilrivity  = "'L','V','W','M'";
      }

      $tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
    }
    elseif ($toim == "PROJEKTI") {
      $tilrivity  = "'L','G','E','V','W'";

      $query = "SELECT GROUP_CONCAT(tunnus) tunnukset
                FROM lasku
                WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu = '$laskurow[tunnusnippu]' and tila IN ('L','G','E','N','R','A') and tunnusnippu > 0";
      $result = pupe_query($query);
      $toimrow = mysql_fetch_assoc($result);

      $tunnuslisa = " and tilausrivi.otunnus in ($toimrow[tunnukset]) and (tilausrivi.perheid = tilausrivi.tunnus or tilausrivi.perheid = 0) and (tilausrivi.perheid2 = tilausrivi.tunnus or tilausrivi.perheid2 = 0)";
    }
    elseif ($toim == "YLLAPITO") {

      $dynamic_result = t_avainsana("SOPIMUS_KENTTA", "", "and avainsana.selitetark != ''");

      if (mysql_num_rows($dynamic_result) > 0) {
        $kommentti_select = "concat(tilausrivi.kommentti";
        $laskentalisa_riveille = " or (";
        // ketjutetaan kommentti, ja avainsanat samaan. Laitetaan html-koodia että avainsana on mustalla, muuten ne olisi samallavärillä kuin kommentti.
        while ($drow = mysql_fetch_assoc($dynamic_result)) {
          $kommentti_select .= ",if(tilausrivin_lisatiedot.{$drow["selite"]} !='',concat('<br><font color=\"black\">{$drow["selitetark"]}:</font> ',tilausrivin_lisatiedot.{$drow["selite"]}),'')";
          $laskentalisa_riveille .= "tilausrivin_lisatiedot.{$drow["selite"]} !='' or ";
        }
        $kommentti_select .= ") kommentti,";
        $laskentalisa_riveille = substr($laskentalisa_riveille, 0, -3).") ";
      }
      else {
        $kommentti_select = "tilausrivi.kommentti,";
      }

      $tilrivity  = "'L','0'";
      $tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
    }
    else {
      $tilrivity  = "'L','E'";
      $tunnuslisa = " and tilausrivi.otunnus='$kukarow[kesken]' ";
    }

    $tilauksen_jarjestys = $yhtiorow['tilauksen_jarjestys'];

    if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or ($yhtiorow['tyomaaraystiedot_tarjouksella'] == '' and ($toim == "TARJOUS" or $toim == "EXTTARJOUS")) or $toim == "PROJEKTI") {
      $sorttauslisa = "tuotetyyppi, ";
    }
    elseif ($toim == 'EXTRANET') {
      $sorttauslisa = "tilausrivin_lisatiedot.positio, ";
    }
    else {
      if ($tilauksen_jarjestys == '0' or $tilauksen_jarjestys == '1' or $tilauksen_jarjestys == '4' or $tilauksen_jarjestys == '5') {
        $sorttauslisa = "tilausrivi.perheid $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.perheid2 $yhtiorow[tilauksen_jarjestys_suunta],";
      }
      else {
        $sorttauslisa = "";
      }
    }

    // katotaan miten halutaan sortattavan
    $sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

    if (isset($ruutulimit) and $ruutulimit > 0) {
      list($ruutulimitalk, $ruutulimitlop) = explode("!¡!", $ruutulimit);

      $limitlisa = "LIMIT ".(int) ($ruutulimitalk-1).", ". (int) $ruutulimitlop;
    }
    else {
      $limitlisa = "";
    }

    $query  = "SELECT count(*) rivit, count(distinct otunnus) otunnukset
               FROM tilausrivi use index (yhtio_otunnus)
               WHERE tilausrivi.yhtio='$kukarow[yhtio]'
               $tunnuslisa
               and tilausrivi.tyyppi in ($tilrivity)";
    $ruuturesult = pupe_query($query);
    $ruuturow = mysql_fetch_assoc($ruuturesult);

    $rivilaskuri = $ruuturow["rivit"];

    if ($kukarow["naytetaan_katteet_tilauksella"] == "B" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "B")) {
      $kehahin_select = " tuote.kehahin ";
    }
    else {
      $kehahin_select = " round(if(tuote.epakurantti100pvm='0000-00-00', if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', tuote.kehahin, tuote.kehahin*0.75), tuote.kehahin*0.5), tuote.kehahin*0.25), 0),6) ";
    }

    // Tilausrivit
    $query  = "SELECT tilausrivin_lisatiedot.*, tilausrivi.*,
               if (tilausrivi.laskutettuaika!='0000-00-00', kpl, varattu) varattu,
               if (tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
               tuote.myyntihinta,
               $kehahin_select kehahin,
               tuote.sarjanumeroseuranta,
               tuote.yksikko,
               tilausrivi.yksikko AS tilausrivin_yksikko,
               tuote.status,
               tuote.ei_saldoa,
               tuote.vakkoodi,
               tilausrivi.ale_peruste,
               tuote.tunnus as tuote_tunnus,
               concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
               $kommentti_select
               $sorttauskentta
               FROM tilausrivi use index (yhtio_otunnus)
               LEFT JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno)
               LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
               WHERE tilausrivi.yhtio='$kukarow[yhtio]'
               $tunnuslisa
               and tilausrivi.tyyppi in ($tilrivity)
               ORDER BY tilausrivi.otunnus, $sorttauslisa sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus
               $limitlisa";
    $result = pupe_query($query);
    $tilausrivit_talteen = $result;

    if ($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
      if (isset($ruutulimit) and $ruutulimit > 0) {
        $rivino = $ruutulimit-1;
      }
      else {
        $rivino = 0;
      }
    }
    else {
      if (isset($ruutulimit) and $ruutulimit > 0) {
        $rivino = $rivilaskuri-($ruutulimit-1)+1;
      }
      else {
        $rivino = $rivilaskuri+1;
      }
    }

    $vak_chk_array = array();

    while ($vakrow = mysql_fetch_assoc($result)) {
      // poimitaan samalla suoratoimitustoimittaja, jos yhtiöparametreissa on sallittu suoratoimitukset vain yhdeltä toimittajalta
      if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('A', 'B', 'C', 'I', 'J'))) {
        if ($vakrow["toimittajan_tunnus"]) {
          $yksi_suoratoimittaja = $vakrow["toimittajan_tunnus"]; // jos tilauksella oli jo monta suoratoimittajaa, niin voi voi. vain viimeinen muistetaan ja sallitaan jatkossa. (backwards compatibility)
        }
      }
      if ($vakrow["vakkoodi"] != "0" and $vakrow["vakkoodi"] != "" and $vakrow["var"] != "P" and $vakrow["var"] != "J") {
        $vak_chk_array[$vakrow["tuoteno"]] = $vakrow["tuoteno"];
      }
    }

    if (mysql_num_rows($result) >=1) {
      mysql_data_seek($result, 0);
    }

    if (count($vak_chk_array) > 0) {
      $vakit_eri_tilaukselle = $tm_toimitustaparow["vaihtoehtoinen_vak_toimitustapa"] != "";
      $vak_toimitustapa = $tm_toimitustaparow["vaihtoehtoinen_vak_toimitustapa"];

      if ($vakit_eri_tilaukselle) {
        echo "<br><font class='error'>" . t("HUOM: Tämä toimitustapa ei salli VAK-tuotteita") .
          "! ($toimtapa_kv)</font><br>";
        echo "<font class='error'>$toimtapa_kv " .
          t("toimitustavan VAK-tuotteet siirretään omalle tilaukselleen toimitustavalla") .
          " {$vak_toimitustapa}.</font> ";
      }
      elseif ($kukarow['extranet'] == '') {
        // jos vak-toimituksissa halutaan käyttää vaihtoehtoista toimitustapaa
        if ($tm_toimitustaparow['vak_kielto'] != '' and $tm_toimitustaparow['vak_kielto'] != 'K') {

          $query = "SELECT tunnus
                    FROM toimitustapa
                    WHERE yhtio    = '$kukarow[yhtio]'
                    AND selite     = '$tm_toimitustaparow[vak_kielto]'
                    AND vak_kielto = ''";
          $vak_check_res = pupe_query($query);

          // CHECK! vaihtoehtoisen toimitustavan täytyy sallia vak-tuotteiden toimitus
          if (mysql_num_rows($vak_check_res) == 1) {
            $query = "UPDATE lasku SET
                      toimitustapa = '$tm_toimitustaparow[vak_kielto]'
                      WHERE yhtio  = '$kukarow[yhtio]'
                      AND tunnus   = '$laskurow[tunnus]'";
            $toimtapa_update_res = pupe_query($query);

            echo "<br><font class='error'>".t("HUOM: Tämä toimitustapa ei salli VAK-tuotteita")."! ($toimtapa_kv)</font><br>";
            echo "<font class='error'>$toimtapa_kv ".t("toimitustavan VAK-tuotteet toimitetaan vaihtoehtoisella toimitustavalla")." $tm_toimitustaparow[vak_kielto].</font> ";

            echo "<form name='tilaus' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>";
            echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
            echo "<input type='hidden' name='mista' value='$mista'>";
            echo "<input type='hidden' name='toim' value='$toim'>";
            echo "<input type='hidden' name='tee' value='$tee'>";
            echo "<input type='hidden' name='orig_tila' value='$orig_tila'>";
            echo "<input type='hidden' name='orig_alatila' value='$orig_alatila'>";
            echo "<input type='submit' name='tyhjenna' value='".t("OK")."'>";
            echo "</form>";
            echo "<br/><br/>";
          }
          else {
            echo "<br><font class='error'>".t("VIRHE: Tämä toimitustapa ei salli VAK-tuotteita")."! (".implode(",", $vak_chk_array).")</font><br>";
            echo "<font class='error'>".t("Valitse uusi toimitustapa")."!</font><br><br>";
          }
          $tilausok++;
        }
        elseif ($tm_toimitustaparow['vak_kielto'] == 'K') {
          echo "<br><font class='error'>".t("VIRHE: Tämä toimitustapa ei salli VAK-tuotteita")."! (".implode(",", $vak_chk_array).")</font><br>";
          echo "<font class='error'>".t("Valitse uusi toimitustapa")."!</font><br><br>";
          $tilausok++;
        }
      }
      else {
        if ($tm_toimitustaparow['vak_kielto'] == 'K' or ($tm_toimitustaparow['vak_kielto'] != '' and $tm_toimitustaparow['nouto'] == '')) {
          echo "<br><font class='error'>".t("VIRHE: Tämä toimitustapa ei salli VAK-tuotteita")."! (".implode(",", $vak_chk_array).")</font><br>";
          echo "<font class='error'>".t("Valitse uusi toimitustapa")."!</font><br><br>";
          $tilausok++;
        }
      }
    }

    // tarkistetaan kuuluuko kaikki reklamaation rivit samaan varastoon
    if ($kukarow["extranet"] == "" and $toim == "REKLAMAATIO" and
      ($yhtiorow['reklamaation_kasittely'] == 'U' or
        ($yhtiorow['reklamaation_kasittely'] == 'X' and $laskurow['tilaustyyppi'] != 'U'))) {

      $varasto_chk_array = array();
      $reklamaatio_saldoton_count = 0;

      while ($varasto_chk_row = mysql_fetch_assoc($result)) {
        if ($varasto_chk_row["ei_saldoa"] == "") {
          // Mihin varastoon
          $varasto_chk = kuuluukovarastoon($varasto_chk_row["hyllyalue"], $varasto_chk_row["hyllynro"]);
          $varasto_chk_array[$varasto_chk] = $varasto_chk;
        }
        else {
          $reklamaatio_saldoton_count++;
        }
      }

      if (count($varasto_chk_array) > 1) {
        echo "<br><font class='error'>".t("VIRHE: Tuotteet eivät kuulu samaan varastoon"), "!</font><br>";
        $tilausok++;
      }

      mysql_data_seek($result, 0);
    }

    echo "<br><table>";

    if ($toim == "TARJOUS" or $toim == "EXTTARJOUS" or $toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $laskurow["tilaustyyppi"] == "T" or $kukarow["yhtio"] == "savt") {
      $trivityyppi_result = t_avainsana("TRIVITYYPPI", "", "ORDER BY avainsana.selitetark");

      if (mysql_num_rows($trivityyppi_result) > 0) {
        $headerit .= "<th>".t("Tyyppi")."</th>";
        $sarakkeet++;
      }
    }

    if ($yhtiorow['myyntitilausrivi_rekisterinumero'] == 'K' and in_array($toim, array('RIVISYOTTO', 'PIKATILAUS', 'TARJOUS', 'REKLAMAATIO'))) {
      $headerit .= "<th>".t("Rekno")."</th>";
      $sarakkeet++;
    }

    $headerit .= "<th>".t("Nimitys")."</th>";
    $sarakkeet++;

    if ((($toim != "TARJOUS" and $toim != "EXTTARJOUS") or $yhtiorow['tarjouksen_tuotepaikat'] == "") and (($kukarow['extranet'] == '' or ($kukarow['extranet'] != '' and $yhtiorow['tuoteperhe_suoratoimitus'] == 'E')) or $yhtiorow['varastopaikan_lippu'] != '')) {
      $headerit .= "<th>".t("Paikka")."</th>";
      $sarakkeet++;
    }

    if ($saldo_valmistuksella) {
      $headerit .= "<th>".t("Myytävissä")."</th>";
      $sarakkeet++;
    }

    $headerit .= "<th>".t("Tuotenumero")."</th><th>".t("Määrä")."</th><th>".t("Tila")."</th>";
    $sarakkeet += 3;

    if ($_onko_valmistus and $yhtiorow["varastonarvon_jako_usealle_valmisteelle"] == "K") {
      $headerit .= "<th>".t("Arvo")."</th><th>".t("Lukitse arvo")."</th>";
      $sarakkeet += 2;
    }

    if ($toim == "VALMISTAVARASTOON" and $yhtiorow["kehahinta_valmistuksella"] == "K") {
      $headerit .= "<th>".t("Keha")."</th>";
      $headerit .="<th>".t("Keha * kpl")."</th>";
      $sarakkeet += 2;
    }

    if ($toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA") {

      if ($kukarow['hinnat'] >= 0) {
        $headerit .= "<th style='text-align:right;'>".t("Svh")."</th>";
        $sarakkeet++;
      }

      if ($kukarow['hinnat'] == 0) {

        $headerit .= "<th style='text-align:right;' nowrap>".t("Ale")." ";

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $headerit .= "$alepostfix + ";
        }

        $headerit = substr($headerit, 0, -3);
        $headerit .= "</th>";
        $sarakkeet++;

        $headerit .= "<th style='text-align:right;'>".t("Hinta")."</th>";
        $sarakkeet++;
      }

      $sarakkeet_alku = $sarakkeet;

      if ($kukarow['hinnat'] >= 0) {
        $headerit .= "<th style='text-align:right;'>".t("Rivihinta")."</th>";
        $sarakkeet++;
      }

      if ($kukarow['extranet'] == '' and $naytetaanko_kate) {
        $headerit .= "<th style='text-align:right;'>".t("Kate")."</th>";
        $sarakkeet++;
      }

      $headerit .= "<th style='text-align:right;'>".t("Alv%")."</th><td class='back'>&nbsp;</td>";
      $sarakkeet++;
    }
    else {
      $sarakkeet_alku = $sarakkeet;
    }
    $headerit .= "</tr>";

    if ($toim == "VALMISTAVARASTOON") {
      echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet' nowrap>";
      echo "<font class='head'>".t("Valmistusrivit")."</font>";
    }
    else {
      echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet' nowrap>";
      echo "<font class='head'>".t("Tilausrivit")."</font>";

      $sele = array("K" => "", "E" => "");

      if ($tilausrivi_alvillisuus == "") {
        if ($yhtiorow["alv_kasittely"] == "") {
          // verolliset hinnat
          $tilausrivi_alvillisuus = "K";
        }
        else {
          // verottomat hinnat
          $tilausrivi_alvillisuus = "E";
        }
      }

      if ($tilausrivi_alvillisuus == "E") {
        $sele["E"] = "checked";
      }
      else {
        $sele["K"] = "checked";
        $tilausrivi_alvillisuus = "K";
      }

      echo "
        <script>
            $(document).ready(function(){
              $('#rivientoiminnot').click(function(){
                 $('#div_rivientoiminnot').toggle();
              });
            });
            </script>";

      echo "<div id='div_rivientoiminnot' class='popup'>";

      if ($oikeus_nahda_kate and $kukarow["extranet"] == "") {
        $kate_sel["K"] = (!isset($naytetaan_kate) or $naytetaan_kate == "K") ? " checked" : "";
        $kate_sel["E"] = $naytetaan_kate == "E" ? " checked" : "";

        echo t("Tilausrivin kate").":<br>";
        echo "<form method='post'>
                <input type='hidden' name='tilausnumero' value='$tilausnumero'>
                <input type='hidden' name='mista' value='$mista'>
                <input type='hidden' name='tee' value='$tee'>
                <input type='hidden' name='toim' value='$toim'>
                <input type='hidden' name='lopetus' value='$lopetus'>
                <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
                <input type='hidden' name='projektilla' value='$projektilla'>
                <input type='hidden' name='tiedot_laskulta' value='$tiedot_laskulta'>
                <input type='hidden' name='orig_tila' value = '$orig_tila'>
                <input type='hidden' name='orig_alatila' value = '$orig_alatila'>
                <label>".t("Näytetään kate")."<input type='radio' name='naytetaan_kate' value='K' onclick='submit()'{$kate_sel["K"]}></label>
                <label>".t("Ei näytetä katetta")."<input type='radio' name='naytetaan_kate' value='E' onclick='submit()'{$kate_sel["E"]}></label>
              </form><br><br>";
      }

      if ($toim != "SIIRTOTYOMAARAYS"  and $toim != "SIIRTOLISTA" and $toim != "VALMISTAVARASTOON" and $kukarow['extranet'] == '') {
        echo t("Tilausrivin verollisuus").":<br>";
        echo "<form action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' method='post'>
            <input type='hidden' name='tilausnumero' value='$tilausnumero'>
            <input type='hidden' name='mista' value='$mista'>
            <input type='hidden' name='tee' value='$tee'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
            <input type='hidden' name='projektilla' value='$projektilla'>
            <input type='hidden' name='tiedot_laskulta' value='$tiedot_laskulta'>
            <input type='hidden' name='orig_tila' value = '$orig_tila'>
            <input type='hidden' name='orig_alatila' value = '$orig_alatila'>
            <label>".t("Verolliset hinnat").": <input type='radio' onclick='submit();' name='tilausrivi_alvillisuus' value='K' $sele[K]></label>
            <label>".t("Verottomat hinnat").": <input type='radio' onclick='submit();' name='tilausrivi_alvillisuus' value='E' $sele[E]></label>
            </form>";
      }

      if ($sahkoinen_tilausliitanta and ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'S' or $yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A')) {

        $style = "width: 15px; height: 15px; display: inline-table; border-radius: 50%; -webkit-border-radius: 50%; -moz-border-radius: 50%;";

        echo "<br><br>".t("Sähköinen tilausliitäntä").": <span class='tooltip' id='color_tooltip'><span style='{$style} background-color: #5D2; margin-right: 5px;'></span><span style='{$style} background-color: #FCF300; margin-right: 5px;'></span><span style='{$style} background-color: #E66; margin-right: 5px;'></span></span></a>";
        echo "<div id='div_color_tooltip' class='popup' style='width: 300px; line-height: 15px; height: 60px;'>";
        echo "<table>";
        echo "<tr><td class='back'><span style='{$style} background-color: #5D2;'></span></td><td class='back'><span style='float: right'>", t("kysytty määrä löytyy"), "</span></td></tr>";
        echo "<tr><td class='back'><span style='{$style} background-color: #FCF300;'></span></td><td class='back'><span style='float: right;'>", t("osa kysytystä määrästä löytyy"), "</span></td></tr>";
        echo "<tr><td class='back'><span style='{$style} background-color: #E66'></span></td><td class='back'><span style='float: right;'>", t("kysyttyä määrää ei löydy"), "</span></td></tr>";
        echo "<tr><td class='back'><img src='{$palvelin2}pics/lullacons/alert.png' /></td><td class='back'><span style='float: right;'>", t("kysyttyä tuotetta ei löydy"), "</span></td></tr>";
        echo "</table>";
        echo "</div>";
      }

      echo "</div>";

      echo "<img id='rivientoiminnot' src='$palvelin2/pics/lullacons/mini-edit.png' style='padding-bottom: 5px; padding-left: 15px; padding-right: 15px;'>";

      if ($yhtiorow["alv_kasittely_hintamuunnos"] == 'o') {
        // valittu ei näytetä alveja vaikka hinnat alvillisina
        if ($tilausrivi_alvillisuus == "E" and $yhtiorow["alv_kasittely"] == '') {
          echo "<font class='info'>(".t("Verottomat hinnat").")</font>";
        }
        // valittu näytetään alvit vaikka hinnat alvittomia
        if ($tilausrivi_alvillisuus == "K" and $yhtiorow["alv_kasittely"] == 'o') {
          echo "<font class='info'>(".t("Verolliset hinnat").")</font>";
        }
      }
    }

    if ($rivilaskuri > 0) {
      if ($rivilaskuri > 25) {

        echo "<form action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' method='post'>
            <input type='hidden' name='tilausnumero' value='$tilausnumero'>
            <input type='hidden' name='mista' value='$mista'>
            <input type='hidden' name='tee' value='$tee'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='projektilla' value='$projektilla'>
            <input type='hidden' name='tiedot_laskulta' value='$tiedot_laskulta'>
            <input type='hidden' name='orig_tila' value='$orig_tila'>
            <input type='hidden' name='orig_alatila' value='$orig_alatila'>
             ".t("Näytä rivit")." <select name='ruutulimit' onchange='submit();'>
            <option value=''>".t("Kaikki")."</option>";

        $ruuarray = array();
        $ruulask1 = 0;
        $ruulask2 = 1;
        $ruulask3 = 0;

        for ($ruulask1 = 0; $ruulask1<$rivilaskuri; $ruulask1++) {

          if ($ruulask2 == 25) {

            if ($ruutulimit == (($ruulask3+1)."!¡!".($ruulask1+1-$ruulask3))) $ruutusel = "SELECTED";
            else $ruutusel = "";

            echo "<option value='".($ruulask3+1)."!¡!".($ruulask1+1-$ruulask3)."' $ruutusel>".($ruulask3+1)." - ".($ruulask1+1)."</option>";

            $ruulask2 = 0;
            $ruulask3 = $ruulask1+1;
          }

          $ruulask2++;
        }

        echo "</select></form>";
      }

      $toimita_kaikki = array();

      # tehdään "Toimita kaikki"-nappula
      # tarkistetaan että tilauksella ei ole luottorajat ylittynyt ja
      # tilaus on tilassa "myyntitilaus odottaa jt-tuotteita"
      if (!$_luottoraja_ylivito and $laskurow['tila'] == 'N' and in_array($laskurow['alatila'], array('T','U'))) {
        $toimita_kaikki = toimita_kaikki_tarkistus($toimita_kaikki, $result, $laskurow, $asiakasrow, $muokkauslukko, $muokkauslukko_rivi);

        if (count($toimita_kaikki) > 0) {
          echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='toimita_kaikki'>";
          echo "<input type='hidden' name='toim'         value = '{$toim}'>";
          echo "<input type='hidden' name='lopetus'      value = '{$lopetus}'>";
          echo "<input type='hidden' name='ruutulimit'   value = '{$ruutulimit}'>";
          echo "<input type='hidden' name='projektilla'  value = '{$projektilla}'>";
          echo "<input type='hidden' name='tilausnumero' value = '{$tilausnumero}'>";
          echo "<input type='hidden' name='mista'        value = '{$mista}'>";
          echo "<input type='hidden' name='toimita_kaikki' value = '".implode(',', $toimita_kaikki)."'>";
          echo "<input type='hidden' name='menutila'     value = '{$menutila}'>";
          echo "<input type='hidden' name='orig_tila'    value = '{$orig_tila}'>";
          echo "<input type='hidden' name='orig_alatila' value = '{$orig_alatila}'>";
          echo "<input type='hidden' name='tila'         value = 'MUUTA'>";
          echo "<input type='hidden' name='tapa'         value = 'POISJTSTA'>";
          echo "<input type='submit' value = '".t("Toimita kaikki")."'>";
          echo "</form> ";
        }

        mysql_data_seek($result, 0);
      }

      echo "</td>";
      echo "</tr>";

      // Tsekataa onko tilausrivien varastojen toimipaikoilla lähdöt päällä, ja onko kyseisen lähdevaraston toimitustavalla lähtöjä
      if ($yhtiorow['toimipaikkakasittely'] == 'L') {
        $tilausrivien_varastot = tilausrivien_varastot($laskurow['tunnus']);

        foreach ($tilausrivien_varastot as $tilausrivin_varasto) {

          $v_toimipaikka = hae_varaston_toimipaikka($tilausrivin_varasto);
          $varasto = hae_varasto($tilausrivin_varasto);

          if (in_array($toim, array('RIVISYOTTO', 'PIKATILAUS')) and !empty($v_toimipaikka) and $varasto['tyyppi'] != 'E') {

            if ($v_toimipaikka['tunnus'] == 0) {
              $_toimipaikka = $kukarow['toimipaikka'];
              $kukarow['toimipaikka'] = 0;
            }

            $toimipaikan_yhtiorow = hae_yhtion_parametrit($kukarow['yhtio'], $v_toimipaikka['tunnus']);
            $kukarow['toimipaikka'] = (isset($_toimipaikka) ? $_toimipaikka : $kukarow['toimipaikka']);
            $_toimipaikan_kerayserat_mittatiedot = ($toimipaikan_yhtiorow['kerayserat'] == 'K');
            $toimipaikka_ja_varasto_ei_sama = ($v_toimipaikka['tunnus'] != $laskurow['yhtio_toimipaikka']);
            $tarvii_lahdon = ($laskurow['eilahetetta'] == '' and $laskurow['sisainen'] == '');
            $_toimitustapa = ($laskurow['toimitustapa'] != '');

            // jos varaston toimipaikka ei ole tilauksen toimipaikka, niin aina true.
            $tarvii_lahdon = ($toimipaikka_ja_varasto_ei_sama ? TRUE : $tarvii_lahdon);

            if ($_toimipaikan_kerayserat_mittatiedot and $tarvii_lahdon and $_toimitustapa) {

              $toimitustavat = hae_kaikki_toimitustavat();
              $toimitustapa = search_array_key_for_value_recursive($toimitustavat, 'selite', $laskurow['toimitustapa']);
              $toimitustapa = $toimitustapa[0];

              if (!empty($toimitustapa['tunnus'])) {

                $query = "SELECT *
                          FROM lahdot
                          WHERE yhtio              = '{$kukarow['yhtio']}'
                          AND liitostunnus         = {$toimitustapa['tunnus']}
                          AND varasto              = {$varasto['tunnus']}
                          AND aktiivi              = ''
                          AND ((pvm                 > CURRENT_DATE)
                          OR (pvm                   = CURRENT_DATE
                          AND viimeinen_tilausaika > CURRENT_TIME))";
                $lahdot_result = pupe_query($query);

                if (mysql_num_rows($lahdot_result) == 0) {
                  echo "<font class='error'>".t("VIRHE: Tilauksen toimitustavalla ei ole")." {$varasto['nimitys']} ".t("varastossa lähtöjä")."!</font><br><br>";
                  $tilausok++;
                }

              }

            }
          }

          unset($_toimipaikka);
        }
      }

      $tuotetyyppi        = "";
      $positio_varattu      = "";
      $varaosatyyppi        = "";
      $vanhaid           = "KALA";
      $borderlask          = 0;
      $pknum            = 0;
      $erikoistuote_tuoteperhe   = array();
      $tuoteperhe_kayty       = '';
      $edotunnus           = 0;
      $tuotekyslinkki        = "";
      $tuotekyslinkkilisa      = "";
      $aleperustelisa       = '';

      if ($yhtiorow['naytetaanko_ale_peruste_tilausrivilla'] != '') {
        $aleperustelisa = " or ale_peruste != ''";
      }

      if ($kukarow["extranet"] == "") {
        $query = "SELECT nimi, alanimi
                  from oikeu
                  where yhtio = '$kukarow[yhtio]'
                  and kuka    = '$kukarow[kuka]'
                  and nimi in ('tuote.php','tuvar.php')
                  ORDER BY nimi, alanimi
                  LIMIT 1";
        $tarkres = pupe_query($query);

        if ($tarkrow = mysql_fetch_assoc($tarkres)) {
          $tuotekyslinkki = $tarkrow["nimi"];

          if ($tarkrow["alanimi"] != "") {
            $tuotekyslinkkilisa = "toim=$tarkrow[alanimi]&";
          }
        }
      }

      if ($toim == 'EXTRANET' and $kukarow['extranet'] != '') {
        $ei_saldoa_varausaika = '';
        if ($asiakasrow['extranet_tilaus_varaa_saldoa'] != 'X') {
          if ($asiakasrow['extranet_tilaus_varaa_saldoa'] == '') {
            if ($yhtiorow['extranet_tilaus_varaa_saldoa'] != '') {
              $ei_saldoa_varausaika = $yhtiorow['extranet_tilaus_varaa_saldoa'];
            }
          }
          else {
            $ei_saldoa_varausaika = $asiakasrow['extranet_tilaus_varaa_saldoa'];
          }
        }
      }

      $puutetta_on = false;

      // Kootaan hintalaskurien datat valmiiksi.
      $rows = array();
      $hinta_laskurit = array();

      // Onko valmistettavaa?
      $_onkovalmistettavaa = FALSE;

      while ($row = mysql_fetch_assoc($result)) {
        $rows[] = $row;

        // Katotaan onko tilauksella valmistettavia rivejä
        if ($_onko_valmistus and !$_onkovalmistettavaa and in_array($row['tyyppi'], array('W', 'M', 'V')) and $row['varattu'] > 0) {
          $_onkovalmistettavaa = TRUE;
        }

        // Onko puutteita
        if ($row['var'] == 'P') {
          $puutetta_on = true;
        }

        if ($_onko_valmistus and $yhtiorow["varastonarvon_jako_usealle_valmisteelle"] == "K") {

          $perheid = $row['perheid'];

          // Alustetaan hinta_kokoelma jos sellaista ei vielä ole.
          if (isset($hinta_laskurit[$perheid]) === FALSE) {
            $hinta_laskurit[$perheid] = array(
              'raakaaineiden_kehahinta_summa' => 0,
              'valmisteiden_kehahinta_summa' => 0,
              'valmisteiden_kpl_summa' => 0,
              'valmisteissa_hinnaton' => false,
              'valmisteissa_painoarvoton' => false,
              'valmisteiden_painoarvot' => array(),
              'valmisteet' => array(),
            );
          }

          // Jos kyseessä raaka-aine
          if ($row["tyyppi"] != "W") {
            $hinta_laskurit[$perheid]['raakaaineiden_kehahinta_summa'] += $row["kehahin"] * $row["tilkpl"];
          }
          // Jos valmiste
          else {
            $hinta_laskurit[$perheid]['valmisteet'][] = $row;
            $hinta_laskurit[$perheid]['valmisteiden_kehahinta_summa'] += $row['kehahin'] * $row["tilkpl"];
            $hinta_laskurit[$perheid]['valmisteiden_kpl_summa'] += $row["tilkpl"];

            if ($row['kehahin'] == 0) {
              $hinta_laskurit[$perheid]['valmisteissa_hinnaton'] = true;
            }
            if (isset($row['valmistus_painoarvo']) === false) {
              $hinta_laskurit[$perheid]['valmisteissa_painoarvoton'] = true;
            }
          }
        }
      }

      if ($_onko_valmistus and $yhtiorow["varastonarvon_jako_usealle_valmisteelle"] == "K") {
        foreach ($hinta_laskurit as $perheid => $hinta_kokoelma) {
          // Jos valmisteissa on yksikin painoarvoton, lasketaan painoarvot uusiks.
          if ($hinta_kokoelma['valmisteissa_painoarvoton']) {
            // Jos valmisteissa ei ole hinnatonta, painoarvot lasketaan keskihankintahintojen mukaan.
            if ($hinta_kokoelma['valmisteissa_hinnaton']===false) {
              foreach ($hinta_kokoelma['valmisteet'] as $valmiste) {
                $hinta_laskurit[$perheid]['valmisteiden_painoarvot'][ $valmiste['tunnus'] ] = $valmiste['kehahin'] * $valmiste["tilkpl"] / $hinta_laskurit[$perheid]['valmisteiden_kehahinta_summa'];
              }
            }
            // Jos valmisteissa on hinnaton, painoarvot lasketaan kappalemäärien mukaan.
            else {
              foreach ($hinta_kokoelma['valmisteet'] as $valmiste) {
                $hinta_laskurit[$perheid]['valmisteiden_painoarvot'][ $valmiste['tunnus'] ] = $valmiste["tilkpl"] / $hinta_laskurit[$perheid]['valmisteiden_kpl_summa'];
              }
            }
          }
          // Jos kaikki valmisteet on painoarvollisia, yksinkertaisesti kopioidaan edelliset painoarvot.
          else {
            foreach ($hinta_kokoelma['valmisteet'] as $valmiste) {
              $hinta_laskurit[$perheid]['valmisteiden_painoarvot'][ $valmiste['tunnus'] ] = $valmiste['valmistus_painoarvo'];
            }
          }
        }

        echo '<input type="hidden" id="hinta_laskurit" value=\''.json_encode($hinta_laskurit).'\' />';
        echo '<input type="hidden" id="desimaalia" value="'.$yhtiorow['hintapyoristys'].'" />';
      }

      if ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A' and $toim != "VALMISTAVARASTOON") {
        $kommenttirivi_nakyviin = true;
      }
      else {
        $kommenttirivi_nakyviin = false;
      }

      $bordercolor = "";

      if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
        // Otetaan yhtiön css:stä SPEC_COLOR
        preg_match("/.*?\/\*(.*?(SPEC_COLOR))\*\//", $yhtiorow['active_css'], $varitmatch);
        preg_match("/(#[a-f0-9]{3,6});/i", $varitmatch[0], $varirgb);

        if (!empty($varirgb[1])) {
          $bordercolor = " $varirgb[1]";
        }
      }

      foreach ($rows as $row) {
        if ($toim == "VALMISTAVARASTOON" and $yhtiorow["kehahinta_valmistuksella"] == "K"
          and $row["tyyppi"] != "V" and isset($tuotteenpainotettukehayht["keha"])) {

          $_colspan = $sarakkeet_alku - 6;

          echo "<tr>{$jarjlisa}";
          echo "<td class='back' colspan='{$_colspan}'>&nbsp;</td>";
          echo "<th colspan='5' align='right'>";
          echo t("Valmisteen %s kehahinta * kpl yhteensä", '', $tuotteenpainotettukehayht["tuoteno"]);
          echo "</th>";
          echo "<td class='spec' align='right'>";
          echo sprintf("%.2f", $tuotteenpainotettukehayht["keha"]);
          echo "</td>";

          $tuotteenpainotettukehayht["keha"] = 0;
        }

        // Tuoteperheen lapset, jotka on merkitty puutteeksi
        if ($kukarow['extranet'] != '' and $row['tunnus'] != $row['perheid'] and strtoupper($row['var']) == 'P' and $row['perheid'] != 0) {

          list(, , $extranet_saldo_tarkistus) = saldo_myytavissa($row['tuoteno']);

          if ($extranet_saldo_tarkistus > 0) {
            $extranet_tarkistus_teksti = "<br /><br />".t("Myytävissä").": <font class='ok'>".t("Kyllä")."</font>

            &nbsp;<form action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' method='post'>
                <input type='hidden' name='tilausnumero' value='{$tilausnumero}'>
                <input type='hidden' name='mista' value='{$mista}'>
                <input type='hidden' name='tee' value='{$tee}'>
                <input type='hidden' name='toim' value='{$toim}'>
                <input type='hidden' name='lopetus' value='{$lopetus}'>
                <input type='hidden' name='projektilla' value='{$projektilla}'>
                <input type='hidden' name='tiedot_laskulta' value='{$tiedot_laskulta}'>
                <input type='hidden' name='tuoteno' value='{$row['tuoteno']}' />
                <input type='hidden' name='orig_tila' value='$orig_tila'>
                <input type='hidden' name='orig_alatila' value='$orig_alatila'>
                <input type='text' size='5' name='kpl' value='' /> <input type='submit' value='".t("Lisää tilaukselle")."' /></form>";

          }
          else {
            $extranet_tarkistus_teksti = "<br /><br />".t("Myytävissä").": <font class='error'>".t("Ei")."</font>";
          }
        }
        else {
          $extranet_tarkistus_teksti = "";
        }

        $vastaavattuotteet = 0;

        if (strpos($row['sorttauskentta'], 'ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ') !== FALSE) {
          $erikoistuote_tuoteperhe[$row['perheid']] = $row['sorttauskentta'];
        }

        // voidaan lukita tämä tilausrivi
        if ($row["uusiotunnus"] > 0 or $laskurow["tunnus"] != $row["otunnus"] or ($laskurow["tila"] == "V" and $row["kpl"] != 0)) {
          $muokkauslukko_rivi = "LUKOSSA";
        }
        else {
          $muokkauslukko_rivi = "";
        }

        if ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
          $row['varattu'] = $row['kpl'];
        }

        //Käännetään tän rivin hinta oikeeseen valuuttaan
        $row["kotihinta"] = $row["hinta"];

        if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
          $row["hinta"] = hintapyoristys(laskuval($row["hinta"], $laskurow["vienti_kurssi"]));
        }

        if ((($asiakasrow['extranet_tilaus_varaa_saldoa'] == "" and
              $yhtiorow["extranet_tilaus_varaa_saldoa"] == "E") or
            $asiakasrow["extranet_tilaus_varaa_saldoa"] == "E") and
          $laskurow["tilaustyyppi"] == "H"
        ) {
          $kplmaara = $row["tilkpl"];
        }
        else {
          $kplmaara = $row["varattu"] + $row["jt"];
        }

        // Tän rivin rivihinta
        $summa =
          $row["hinta"] *
          $kplmaara *
          generoi_alekentta_php($row, 'M', 'kerto', 'ei_erikoisale');

        $kotisumma  = $row["kotihinta"] * ($row["varattu"] + $row["jt"]) * generoi_alekentta_php($row, 'M', 'kerto', 'ei_erikoisale');

        // Tän rivin alviton rivihinta
        if ($yhtiorow["alv_kasittely"] == '') {

          // Jos meillä on marginaalimyyntiä/käänteinen alv
          if ($row["alv"] >= 500) {
            $alvkapu = 0;
          }
          else {
            $alvkapu = $row["alv"];
          }

          $summa_alviton     = $summa / (1+$alvkapu/100);
          $kotisumma_alviton   = $kotisumma / (1+$alvkapu/100);
        }
        else {
          $summa_alviton     = $summa;
          $kotisumma_alviton   = $kotisumma;
        }

        if ($row["hinta"] == 0.00)   $row["hinta"] = '';
        if ($summa == 0.00)     $summa = '';

        // Rivin tarkistukset
        if ($muokkauslukko == "" and $muokkauslukko_rivi == "") {
          require 'tarkistarivi.inc';

          //tarkistarivi.inc:stä saadaan $trow jossa on select * from tuote
        }

        if ($edotunnus == 0 or $edotunnus != $row["otunnus"]) {
          if ($edotunnus > 0) echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><br></td></tr>";
          if ($ruuturow["otunnukset"] > 1) echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'>".t("Toimitus").": $row[otunnus]</td></tr>";
          echo $headerit;
        }

        $edotunnus = $row["otunnus"];

        if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or ($yhtiorow['tyomaaraystiedot_tarjouksella'] == '' and ($toim == "TARJOUS" or $toim == "EXTTARJOUS")) or $toim == "PROJEKTI") {
          if ($tuotetyyppi == "" and $row["tuotetyyppi"] == '2 Työt') {
            $tuotetyyppi = 1;

            echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><br></td></tr>";
            echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><font class='head'>".t("Työt")."</font>:</td></tr>";
          }
        }
        elseif ($toim == 'EXTRANET' and $kukarow['extranet'] != '') {
          if ($positio_varattu == '' and $row['positio'] == 'Ei varaa saldoa') {
            $positio_varattu = 1;

            echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><br></td></tr>";
            echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><font class='head'>", t("Umpeutuneet tilausrivit"), "</font>:</td></tr>";
            echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><font class='message'>", t("Pahoittelumme! Alla olevien tilausrivien varausajat ovat umpeutuneet");

            if ($ei_saldoa_varausaika != '') {
              echo " (", t("varausaika"), " $ei_saldoa_varausaika ", t("tuntia"), ")";
            }

            echo "</font></td></tr>";
          }
        }

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $row["ale{$alepostfix}"] = (float) $row["ale{$alepostfix}"];
        }

        if ($row["hyllyalue"] == "") {
          $row["hyllyalue"] = "";
          $row["hyllynro"]  = "";
          $row["hyllyvali"] = "";
          $row["hyllytaso"] = "";
        }

        $class = " class='ptop' ";

        if ($row["var"] == "P") {
          $class = " class='ptop spec' ";
        }
        elseif ($row["var"] == "J") {
          $class = " class='ptop green' ";
        }
        elseif ($yhtiorow["puute_jt_oletus"] == "H") {
          //  Tarkastetaan saldo ja informoidaan käyttäjää
          list(, , $tsek_myytavissa) = saldo_myytavissa($trow["tuoteno"], '', '', '', '', '', '', '', '', $_tm_saldoaikalisa);

          if ($tsek_myytavissa < 0) {
            $class = " class='ptop spec' ";
          }
        }

        if ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
          if ($row["tyyppi"] == "W") {
            $class = " class='ptop spec' ";
          }
          else {
            $class = " class='ptop' ";
          }

          if ($vanhaid != $row["perheid"] and $vanhaid != 'KALA' and ($yhtiorow["raaka_aineet_valmistusmyynti"] != "N" or $toim != "VALMISTAASIAKKAALLE")) {
            echo "<tr>$jarjlisa<td class='back' colspan='$sarakkeet'><br></td></tr>";
          }
        }

        if ($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
          $rivino++;
        }
        else {
          $rivino--;
        }

        if ($muokkauslukko_rivi == "" and $yhtiorow["tilauksen_jarjestys"] == "M" and in_array($toim, array("TARJOUS", "EXTTARJOUS", "PIKATILAUS", "RIVISYOTTO", "VALMISTAASIAKKAALLE", "SIIRTOLISTA", "TYOMAARAYS", "TYOMAARAYS_ASENTAJA", "REKLAMAATIO", "PROJEKTI"))) {

          $buttonit =  "<div align='center'><form action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php#rivi_$rivino' name='siirra_$rivino' method='post'>
                  <input type='hidden' name='toim' value='$toim'>
                  <input type='hidden' name='lopetus' value='$lopetus'>
                  <input type='hidden' name='ruutulimit' value='$ruutulimit'>
                  <input type='hidden' name='projektilla' value='$projektilla'>
                  <input type='hidden' name='tilausnumero' value = '$tilausnumero'>
                  <input type='hidden' name='mista' value='$mista'>
                  <input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
                  <input type='hidden' name='ale_peruste' value='$row[ale_peruste]'>
                  <input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
                  <input type='hidden' name='menutila' value='$menutila'>
                  <input type='hidden' name='orig_tila' value='$orig_tila'>
                  <input type='hidden' name='orig_alatila' value='$orig_alatila'>
                  <input type='hidden' id='rivi_$rivino' name='jarjesta' value='$rivino'>";

          if (($rivino > 1 and $yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") or ($rivino < $rivilaskuri and $yhtiorow["tilauksen_jarjestys_suunta"] == "DESC")) {
            $buttonit .= "  <a href='#' onClick=\"getElementById('rivi_$rivino').value='moveUp'; document.forms['siirra_$rivino'].submit();\"><img src='".$palvelin2."pics/lullacons/arrow-single-up-green.png' height = '10' width = '10'></a><br>";
          }

          if (($rivilaskuri > $rivino and $yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") or ($rivino > 1 and $yhtiorow["tilauksen_jarjestys_suunta"] == "DESC")) {
            $buttonit .= "  <a href='#' onClick=\"getElementById('rivi_$rivino').value='moveDown'; document.forms['siirra_$rivino'].submit();\"><img src='".$palvelin2."pics/lullacons/arrow-single-down-red.png' height = '10' width = '10'></a>";
          }

          $buttonit .= "</form></div>";
        }
        else {
          $buttonit = "";
        }

        // Tuoteperheiden lapsille ei näytetä rivinumeroa
        if ($tilauksen_jarjestys != 'M' and $tuoteperhe_kayty != $row['perheid'] and (($row['perheid'] != 0 and ($tilauksen_jarjestys == '1' or $tilauksen_jarjestys == '5' or ($tilauksen_jarjestys == '4' or $tilauksen_jarjestys == '0' and $erikoistuote_tuoteperhe[$row['perheid']] == $row['sorttauskentta']))) or $row["perheid"] == $row["tunnus"] or ($row["perheid2"] == $row["tunnus"] and $row["perheid"] == 0) or ($row["perheid2"] == -1 or ($row["perheid"] == 0 and $row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U"))))) {

          if ($toim == "VALMISTAASIAKKAALLE" and $yhtiorow["raaka_aineet_valmistusmyynti"] == "N") {
            $pklisa = " and tilausrivi.tunnus = '$row[tunnus]'";
          }
          elseif (($row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")) or $row["perheid2"] == -1) {
            $pklisa = " and (tilausrivi.perheid = '$row[tunnus]' or tilausrivi.perheid2 = '$row[tunnus]' or tilausrivi.tunnus = '$row[tunnus]')";
          }
          elseif ($row["perheid"] == 0) {
            $pklisa = " and tilausrivi.perheid2 = '$row[perheid2]'";
          }
          else {
            $pklisa = " and (tilausrivi.perheid = '$row[perheid]' or tilausrivi.perheid2 = '$row[perheid]')";
          }

          $query = "SELECT
                    sum(if(kommentti != '' {$aleperustelisa} {$laskentalisa_riveille} or ('$GLOBALS[eta_yhtio]' != '' and '$koti_yhtio' = '$kukarow[yhtio]'), 1, 0)),
                    count(*)
                    FROM tilausrivi use index (yhtio_otunnus)
                    LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
                    WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                    $tunnuslisa
                    $pklisa
                    and tilausrivi.tyyppi  != 'D'";
          $pkres = pupe_query($query);
          $pkrow = mysql_fetch_row($pkres);

          $lisays = 0;

          if ($row["perheid2"] == -1) {

            foreach ($rows as $chkrow) {

              $_onko_perhe = ($row['perheid'] == $chkrow['perheid']);
              $_onko_lapsi = ($chkrow['perheid'] != $chkrow['tunnus']);

              if ($_onko_perhe and $_onko_lapsi) {
                $lisays++;
              }
            }

            unset($chkrow);
          }

          $pkrow[1] += $lisays;

          $pknum = $pkrow[0] + $pkrow[1];
          $borderlask = $pkrow[1];

          if ($kommenttirivi_nakyviin) {
            $pknum = $pkrow[1] * 2;
          }

          echo "<tr>";

          if ($jarjlisa != "") {
            echo "<td rowspan='$pknum' class='back' style='width:10px; padding:0px; margin:0px;'>$buttonit</td>";
          }

          $echorivino = $rivino;

          if ($yhtiorow['rivinumero_syotto'] != '') {
            if ($row['tilaajanrivinro'] != '' and $row['tilaajanrivinro'] != 0 and $echorivino != $row['tilaajanrivinro']) {
              $echorivino .= " &raquo; ($row[tilaajanrivinro])";
            }
          }

          if (($toim != "TARJOUS" and $toim != "EXTTARJOUS")) {
            if ($muokkauslukko_rivi == "" and $row["toimitettuaika"] == '0000-00-00 00:00:00' and $row["uusiotunnus"] == 0 and $laskurow["tunnusnippu"] > 0 and $yhtiorow["splittauskielto"] != "K") {
              $query = "SELECT lasku.tunnus
                        FROM lasku
                        WHERE lasku.yhtio      = '$kukarow[yhtio]'
                        and lasku.tunnusnippu  = '$laskurow[tunnusnippu]'
                        and lasku.tila         IN ('L','N','A','T','G','S','V','W','O')
                        and lasku.alatila     != 'X'";
              $toimres = pupe_query($query);

              if (mysql_num_rows($toimres) > 1) {
                $echorivino .= " &raquo; <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
                    <input type='hidden' name='toim'       value = '$toim'>
                    <input type='hidden' name='lopetus'     value = '$lopetus'>
                    <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                    <input type='hidden' name='projektilla'   value = '$projektilla'>
                    <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                    <input type='hidden' name='mista'       value = '$mista'>
                    <input type='hidden' name='edtilausnumero'   value = '$row[otunnus]'>
                    <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                    <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                    <input type='hidden' name='rivilaadittu'   value = '$row[laadittu]'>
                    <input type='hidden' name='menutila'     value = '$menutila'>
                    <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                    <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                    <select name='valitsetoimitus_vaihdarivi' onchange='submit();'>";

                while ($toimrow = mysql_fetch_assoc($toimres)) {
                  $sel = "";
                  if ($toimrow["tunnus"] == $row["otunnus"]) {
                    $sel = "selected";
                  }

                  $echorivino .= "<option value ='$toimrow[tunnus]' $sel>$toimrow[tunnus]</option>";
                }

                $echorivino .= "</select></form>";
              }
            }
            elseif ($muokkauslukko_rivi != ""and $laskurow["tunnusnippu"] > 0 and $yhtiorow["splittauskielto"] != "K") {
              $echorivino .= " &raquo; $row[otunnus]";
            }
          }

          // jos tuoteperheitä ei pidetä yhdessä, ei tehdä rowspannia eikä bordereita
          if ($tilauksen_jarjestys != '0' and $tilauksen_jarjestys != '1' and $tilauksen_jarjestys != '4' and $tilauksen_jarjestys != '5' and $tilauksen_jarjestys != '8') {
            $pknum = 0;
            $borderlask = 0;

            if ($row["kommentti"] != "" or ($row["ale_peruste"] != '' and $yhtiorow['naytetaanko_ale_peruste_tilausrivilla'] != '') or (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $koti_yhtio == $kukarow['yhtio'])) {
              echo "<td rowspan = '2' $class>$echorivino";

              if (($yhtiorow["salli_jyvitys_myynnissa"] == "V" and $kukarow['jyvitys'] == 'S') or $yhtiorow["salli_jyvitys_myynnissa"] == "S" or ($toim == 'TARJOUS' and $yhtiorow['salli_jyvitys_tarjouksella'] == 'S')) {
                echo "<input type='checkbox' class='valitut_rivit' name='valitut_rivit[]' value='{$row['tunnus']}' />";
              }

              echo "</td>";
            }
            else {
              echo "<td $class>$echorivino";

              if (($yhtiorow["salli_jyvitys_myynnissa"] == "V" and $kukarow['jyvitys'] == 'S') or $yhtiorow["salli_jyvitys_myynnissa"] == "S" or ($toim == 'TARJOUS' and $yhtiorow['salli_jyvitys_tarjouksella'] == 'S')) {
                echo "<input type='checkbox' class='valitut_rivit' name='valitut_rivit[]' value='{$row['tunnus']}' />";
              }

              echo "</td>";
            }
          }
          else {
            if ($row['perheid'] != 0 and ($tilauksen_jarjestys == '1' or $tilauksen_jarjestys == '0' or $tilauksen_jarjestys == '4' or $tilauksen_jarjestys == '5')) {
              $tuoteperhe_kayty = $row['perheid'];
            }
            echo "<td rowspan='$pknum' $class style='border-top: 1px solid{$bordercolor}; border-left: 1px solid{$bordercolor}; border-bottom: 1px solid{$bordercolor};'>$echorivino";

            if (($yhtiorow["salli_jyvitys_myynnissa"] == "V" and $kukarow['jyvitys'] == 'S') or $yhtiorow["salli_jyvitys_myynnissa"] == "S") {
              echo "<br/>";
              //haetaan perheen kaikki rivitunnukset, jotta niitä voidaan käyttää pyöristä valitut rivit toiminnallisuudessa
              //otunnus on queryssä mukana indeksien takia
              $query = "SELECT group_concat(tunnus) tunnukset
                        FROM tilausrivi
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND otunnus = '{$row['otunnus']}'
                        AND (tunnus = '{$row['tunnus']}' OR perheid = '{$row['perheid']}')";
              $perhe_result = pupe_query($query);
              $perhe_row = mysql_fetch_assoc($perhe_result);

              echo "<input type='checkbox' class='valitut_rivit' name='valitut_rivit[]' value='{$perhe_row['tunnukset']}' />";
            }

            echo "</td>";
          }
        }
        // normirivit tai jos tuoteperheitä ei pidetä yhdessä, näytetään lapsille rivinumerot
        elseif ($row["perheid"] == 0 and $row["perheid2"] == 0 or ($tilauksen_jarjestys != '0' and $tilauksen_jarjestys != '1' and $tilauksen_jarjestys != '4' and $tilauksen_jarjestys != '5' and $tilauksen_jarjestys != '8') or (($tilauksen_jarjestys == '0' or $tilauksen_jarjestys == '4') and $erikoistuote_tuoteperhe[$row['perheid']] == $row['sorttauskentta'] and $tuoteperhe_kayty != $row['perheid'])) {

          $echorivino = $rivino;

          if ($yhtiorow['rivinumero_syotto'] != '') {
            if ($row['tilaajanrivinro'] != '' and $row['tilaajanrivinro'] != 0 and $echorivino != $row['tilaajanrivinro']) {
              $echorivino .= " &raquo; ($row[tilaajanrivinro])";
            }
          }

          echo "<tr>";

          if ($kommenttirivi_nakyviin or $row["kommentti"] != "" or ($row["ale_peruste"] != '' and $yhtiorow['naytetaanko_ale_peruste_tilausrivilla'] != '') or (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $koti_yhtio == $kukarow['yhtio'])) {
            if ($jarjlisa != "") {
              echo "<td rowspan = '2' class='back' style='width:10px; padding:0px; margin:0px;'>$buttonit</td>";
            }

            echo "<td $class rowspan = '2'>$echorivino";

            if (($yhtiorow["salli_jyvitys_myynnissa"] == "V" and $kukarow['jyvitys'] == 'S') or $yhtiorow["salli_jyvitys_myynnissa"] == "S" or ($toim == 'TARJOUS' and $yhtiorow['salli_jyvitys_tarjouksella'] == 'S')) {
              echo "<input type='checkbox' class='valitut_rivit' name='valitut_rivit[]' value='{$row['tunnus']}' />";
            }
          }
          elseif ($tilauksen_jarjestys == '1' and $row['perheid'] != 0) {
            echo "<td $class>&nbsp;</td>";
          }
          else {
            if ($jarjlisa != "") {
              echo "<td class='back' style='width:10px; padding:0px; margin:0px;'>$buttonit</td>";
            }

            echo "<td $class>$echorivino";

            if (($yhtiorow["salli_jyvitys_myynnissa"] == "V" and $kukarow['jyvitys'] == 'S') or $yhtiorow["salli_jyvitys_myynnissa"] == "S" or ($toim == 'TARJOUS' and $yhtiorow['salli_jyvitys_tarjouksella'] == 'S')) {
              echo "<br/>";
              echo "<input type='checkbox' class='valitut_rivit' name='valitut_rivit[]' value='{$row['tunnus']}'/>";
            }
          }

          if (($toim != "TARJOUS" and $toim != "EXTTARJOUS")) {
            if ($muokkauslukko_rivi == "" and $row["toimitettuaika"] == '0000-00-00 00:00:00' and $row["uusiotunnus"] == 0 and $laskurow["tunnusnippu"] > 0 and $yhtiorow["splittauskielto"] != "K") {
              $query = "SELECT lasku.tunnus
                        FROM lasku
                        WHERE lasku.yhtio      = '$kukarow[yhtio]'
                        and lasku.tunnusnippu  = '$laskurow[tunnusnippu]'
                        and lasku.tila         IN ('L','N','A','T','G','S','V','W','O')
                        and lasku.alatila     != 'X'";
              $toimres = pupe_query($query);

              if (mysql_num_rows($toimres) > 1) {
                echo " &raquo; <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
                    <input type='hidden' name='toim'       value = '$toim'>
                    <input type='hidden' name='lopetus'     value = '$lopetus'>
                    <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                    <input type='hidden' name='projektilla'   value = '$projektilla'>
                    <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                    <input type='hidden' name='mista'       value = '$mista'>
                    <input type='hidden' name='edtilausnumero'   value = '$row[otunnus]'>
                    <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                    <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                    <input type='hidden' name='rivilaadittu'   value = '$row[laadittu]'>
                    <input type='hidden' name='menutila'     value = '$menutila'>
                    <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                    <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                    <select name='valitsetoimitus_vaihdarivi' onchange='submit();'>";

                while ($toimrow = mysql_fetch_assoc($toimres)) {
                  $sel = "";
                  if ($toimrow["tunnus"] == $row["otunnus"]) {
                    $sel = "selected";
                  }

                  echo "<option value ='$toimrow[tunnus]' $sel>$toimrow[tunnus]</option>";
                }

                echo "</select></form>";
              }
            }
            elseif ($muokkauslukko_rivi != ""and $laskurow["tunnusnippu"] > 0 and $yhtiorow["splittauskielto"] != "K") {
              echo " &raquo; $row[otunnus]";
            }
          }

          echo "</td>";
          $borderlask = 0;
          $pknum      = 0;
        }

        $classlisa = "";

        if (isset($pkrow[1]) and $borderlask == 1 and $pkrow[1] == 1 and $pknum == 1) {
          $classlisa = $class." style='border-top: 1px solid{$bordercolor}; border-bottom: 1px solid{$bordercolor}; border-right: 1px solid{$bordercolor};' ";
          $class    .= " style=' border-top: 1px solid{$bordercolor}; border-bottom: 1px solid{$bordercolor};' ";

          $borderlask--;
        }
        elseif (isset($pkrow[1]) and $borderlask == $pkrow[1] and $pkrow[1] > 0) {
          $classlisa = $class." style='border-top: 1px solid{$bordercolor}; border-right: 1px solid{$bordercolor};' ";
          $class    .= " style='border-top: 1px solid{$bordercolor};' ";

          $borderlask--;
        }
        elseif ($borderlask == 1) {
          if ($kommenttirivi_nakyviin or $row['kommentti'] != '' or ($yhtiorow['naytetaanko_ale_peruste_tilausrivilla'] != '' and $row['ale_peruste'] != '')) {
            $classlisa = $class." style='font-style:italic; border-right: 1px solid{$bordercolor};' ";
            $class    .= " style='font-style:italic; ' ";
          }
          else {
            $classlisa = $class." style='font-style:italic; border-bottom: 1px solid{$bordercolor}; border-right: 1px solid{$bordercolor};' ";
            $class    .= " style='font-style:italic; border-bottom: 1px solid{$bordercolor};' ";
          }

          $borderlask--;
        }
        elseif ($borderlask > 0 and $borderlask <= $pknum) {
          $classlisa = $class." style='font-style:italic; border-right: 1px solid{$bordercolor};' ";
          $class    .= " style='font-style:italic;' ";
          $borderlask--;
        }

        $vanhaid     = $row["perheid"];
        $trivityyulos = "";
        $paltoimiulos = "";

        if ($toim == "TARJOUS" or $toim == "EXTTARJOUS" or $toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $laskurow["tilaustyyppi"] == "T" or $kukarow["yhtio"] == "savt") {
          if ($muokkauslukko_rivi == "" and $row["ei_nayteta"] == "") {
            if (mysql_num_rows($trivityyppi_result) > 0) {
              //annetaan valita tilausrivin tyyppi
              $trivityyulos .= "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='lisatietoja'>
                        <input type='hidden' name='toim' value='$toim'>
                        <input type='hidden' name='lopetus' value='$lopetus'>
                        <input type='hidden' name='ruutulimit' value='$ruutulimit'>
                        <input type='hidden' name='projektilla' value='$projektilla'>
                        <input type='hidden' name='tilausnumero' value = '$tilausnumero'>
                        <input type='hidden' name='mista' value = '$mista'>
                        <input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
                        <input type='hidden' name='ale_peruste' value = '$row[ale_peruste]'>
                        <input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
                        <input type='hidden' name='menutila' value='$menutila'>
                        <input type='hidden' name='tila' value = 'LISATIETOJA_RIVILLE'>
                        <input type='hidden' name='orig_tila' value='$orig_tila'>
                        <input type='hidden' name='orig_alatila' value='$orig_alatila'>
                        <select name='positio' onchange='submit();'>";

              mysql_data_seek($trivityyppi_result, 0);

              $trivityyulos .= "<option value=''>".t("Valitse")."</option>";

              while ($trrow = mysql_fetch_assoc($trivityyppi_result)) {
                $sel = "";
                if ($trrow["selite"]==$row["positio"]) $sel = 'selected';
                $trivityyulos .= "<option value='$trrow[selite]' $sel>$trrow[selitetark]</option>";
              }

              $trivityyulos .= "</select></form>";
            }
          }
          elseif (mysql_num_rows($trivityyppi_result) > 0) {
            $trivityyulos = "&nbsp;";
          }
        }

        if ($toim == "REKLAMAATIO") {
          if ($muokkauslukko_rivi == "" and $row["ei_nayteta"] == "") {
            if (mysql_num_rows($tpares) > 0) {

              // Saako tuotteen palauttaa toimittajalle
              $query = "SELECT asiakas.tunnus, asiakas.nimi, if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
                        FROM tuotteen_toimittajat
                        JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
                        JOIN asiakas ON (toimi.yhtio = asiakas.yhtio AND toimi.ytunnus = asiakas.ytunnus and asiakas.tunnus in ({$toimpalautusasiakkat}))
                        LEFT JOIN tuotteen_avainsanat ON (tuotteen_toimittajat.yhtio = tuotteen_avainsanat.yhtio AND tuotteen_toimittajat.tuoteno = tuotteen_avainsanat.tuoteno AND tuotteen_avainsanat.laji = 'toimpalautus')
                        WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
                        AND tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
                        AND (tuotteen_avainsanat.selite is NULL or tuotteen_avainsanat.selite = '')
                        ORDER BY sorttaus";
              $abures = pupe_query($query);

              $toimittajat = array();
              if (mysql_num_rows($abures) > 0) {
                while ($trrow = mysql_fetch_assoc($abures)) {
                  $toimittajat[] = array(
                    'tunnus' => $trrow['tunnus'],
                    'nimi' => $trrow['nimi']
                  );
                }
              }

              if (!empty($toimittajat)) {
                $disalisa = "";
                if ($row["asiakkaan_positio"] < 0 or $row['palautus_varasto'] > 0) {
                  $disalisa = "DISABLED";
                }

                //annetaan valita tilausrivin tyyppi
                $paltoimiulos .= "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='lisatietoja'>
                          <input type='hidden' name='toim' value='$toim'>
                          <input type='hidden' name='lopetus' value='$lopetus'>
                          <input type='hidden' name='ruutulimit' value='$ruutulimit'>
                          <input type='hidden' name='projektilla' value='$projektilla'>
                          <input type='hidden' name='tilausnumero' value = '$tilausnumero'>
                          <input type='hidden' name='mista' value = '$mista'>
                          <input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
                          <input type='hidden' name='ale_peruste' value = '$row[ale_peruste]'>
                          <input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
                          <input type='hidden' name='menutila' value='$menutila'>
                          <input type='hidden' name='tila' value = 'ASPOSITIO_RIVILLE'>
                          <input type='hidden' name='orig_tila' value='$orig_tila'>
                          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
                          <select name='asiakkaan_positio' onchange='submit();' $state $disalisa>";

                $paltoimiulos .= "<option value=''>".t("Ei palauteta")."</option>";

                foreach ($toimittajat as $toimittaja) {
                  $sel = "";
                  if ($toimittaja["tunnus"] == abs($row["asiakkaan_positio"])) {
                    $sel = 'selected';
                  }
                  $paltoimiulos .= "<option value='{$toimittaja['tunnus']}' {$sel}>{$toimittaja['nimi']}</option>";
                }

                $paltoimiulos .= "</select>";
                $paltoimiulos .= "</form>";

                $disalisa = "";
                if ($row["asiakkaan_positio"] > 0) {
                  $disalisa = "DISABLED";
                }

              }

              $lahde_kohde_varastot = hae_mahdolliset_siirto_varastot(array($laskurow['varasto']));

              if (!empty($lahde_kohde_varastot) and saako_palauttaa_siirtovarastoon($row['tuoteno'])) {

                $paltoimiulos .= "<br/>";
                $paltoimiulos .= "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='lisatietoja'>
                          <input type='hidden' name='toim' value='$toim'>
                          <input type='hidden' name='lopetus' value='$lopetus'>
                          <input type='hidden' name='ruutulimit' value='$ruutulimit'>
                          <input type='hidden' name='projektilla' value='$projektilla'>
                          <input type='hidden' name='tilausnumero' value = '$tilausnumero'>
                          <input type='hidden' name='mista' value = '$mista'>
                          <input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
                          <input type='hidden' name='ale_peruste' value = '$row[ale_peruste]'>
                          <input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
                          <input type='hidden' name='menutila' value='$menutila'>
                          <input type='hidden' name='tila' value = 'PALAUTUSVARASTO'>
                          <input type='hidden' name='orig_tila' value='$orig_tila'>
                          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
                          <select name='palautus_varasto' onchange='submit();' $state $disalisa>";

                $disalisa = "";

                $paltoimiulos .= "<option value=''>".t("Ei palauteta")."</option>";

                $_varastot = array();

                foreach ($lahde_kohde_varastot as $_varasto) {
                  $_varastot[$_varasto['lahde_tunnus']] = array(
                    'lahde_nimi' => $_varasto['lahde_nimi'],
                    'lahde_tunnus' => $_varasto['lahde_tunnus']
                  );
                }

                foreach ($_varastot as $lahde_kohde_varasto) {

                  $sel = "";

                  if ($lahde_kohde_varasto["lahde_tunnus"] == abs($row["palautus_varasto"])) {
                    $sel = 'selected';
                  }

                  $paltoimiulos .= "<option value='{$lahde_kohde_varasto['lahde_tunnus']}' {$sel}>";
                  $paltoimiulos .= t('Varasto').": {$lahde_kohde_varasto['lahde_nimi']}";
                  $paltoimiulos .= "</option>";
                }

                $paltoimiulos .= "</select>";
                $paltoimiulos .= "</form>";
              }
              else {
                $paltoimiulos = t("Ei voida palauttaa");
              }
            }
          }
          elseif (mysql_num_rows($tpares) > 0) {
            $paltoimiulos = "&nbsp;";
          }
        }

        if ($trivityyulos != "") {
          echo "<td $class>$trivityyulos</td>";
        }

        if ($paltoimiulos != "") {
          echo "<td $class>$paltoimiulos</td>";
        }

        if ($yhtiorow['myyntitilausrivi_rekisterinumero'] == 'K' and in_array($toim, array('RIVISYOTTO', 'PIKATILAUS', 'TARJOUS', 'REKLAMAATIO'))) {
          echo "<td $class align='left'>";
          echo $row['rekisterinumero'];
          echo "</td>";
        }

        // Onko liitetiedostoja
        if ($kukarow['extranet'] != '') {
          $liitekuvat = liite_popup("TH", $row['tuote_tunnus']);
          $liitekuvat = "<div style='float: left;'>{$liitekuvat}</div>";
        }
        else {
          $liitekuvat = '';
        }

        echo "<td $class align='left'>{$liitekuvat}".t_tuotteen_avainsanat($row, "nimitys")."$extranet_tarkistus_teksti</td>";

        if ($kukarow['extranet'] == '' and $toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {

          if ($row["kpl"] != 0 and ($row["perheid"] == 0 or $row["perheid"] == $row["tunnus"])) {
            $tilatapa = "VALITSE";
            require 'laskuta_myyntitilirivi.inc';
          }
          else {
            echo "<td $class align='left'>&nbsp;</td>";
          }
        }
        elseif ((($toim != "TARJOUS" and $toim != "EXTTARJOUS") or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $muokkauslukko_rivi == "" and ($kukarow['extranet'] == '' or ($kukarow['extranet'] != '' and $yhtiorow['tuoteperhe_suoratoimitus'] == 'E')) and $trow["ei_saldoa"] == "") {
          if ($paikat != '') {

            echo "  <td $class align='left' nowrap>";

            //valitaan näytetävä lippu varaston tai yhtiön maanperusteella
            if ($selpaikkamaa != '' and $yhtiorow['varastopaikan_lippu'] != '') {
              echo "<img src='{$palvelin2}pics/flag_icons/gif/".strtolower($selpaikkamaa).".gif'>";
            }

            echo "<form method='post' name='paikat' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
                    <input type='hidden' name='toim'       value = '$toim'>
                    <input type='hidden' name='lopetus'     value = '$lopetus'>
                    <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                    <input type='hidden' name='projektilla'   value = '$projektilla'>
                    <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                    <input type='hidden' name='mista'       value = '$mista'>
                    <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                    <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                    <input type='hidden' name='rivilaadittu'   value = '$row[laadittu]'>
                    <input type='hidden' name='tuotenimitys'   value = '$row[nimitys]'>
                    <input type='hidden' name='menutila'     value = '$menutila'>
                    <input type='hidden' name='orig_tila'    value = '$orig_tila'>
                    <input type='hidden' name='orig_alatila'  value = '$orig_alatila'>
                    <input type='hidden' name='tila'       value = 'MUUTA'>
                    <input type='hidden' name='tapa'       value = 'VAIHDA'>
                    $paikat
                  </form>";
          }
          else {

            if ($varow['maa'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {
              echo "<td $class align='left' nowrap><font class='error'><img src='{$palvelin2}pics/flag_icons/gif/".strtolower($varow['maa']).".gif'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso] ($selpaikkamyytavissa) </font>";
            }
            elseif ($varow['maa'] != '' and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
              echo "<td $class align='left' nowrap><font class='error'>".strtoupper($varow['maa'])." $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso] ($selpaikkamyytavissa) </font>";
            }
            else {
              if (trim($row['hyllyalue']) == '' and trim($row['hyllynro']) == '' and trim($row['hyllyvali']) == '' and trim($row['hyllytaso']) == '' and trim($selpaikkamyytavissa) == '') {
                echo "<td $class align='left' nowrap> ";
                if ($row['var'] == 'U' or $row['var'] == 'T') echo t("Suoratoimitus");
              }
              else {
                echo "<td $class align='left' nowrap> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso] ($selpaikkamyytavissa) ";
              }
            }

            if (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and !in_array($row["var"], array('P', 'J', 'S', 'T'))) {
              $query  = "SELECT sarjanumeroseuranta.sarjanumero era, sarjanumeroseuranta.parasta_ennen
                         FROM sarjanumeroseuranta
                         WHERE yhtio          = '$kukarow[yhtio]'
                         and tuoteno          = '$row[tuoteno]'
                         and myyntirivitunnus = '$row[tunnus]'
                         LIMIT 1";
              $sarjares = pupe_query($query);
              $sarjarow = mysql_fetch_assoc($sarjares);

              echo ", $sarjarow[era]";

              if ($trow["sarjanumeroseuranta"] == "F") {
                echo " ".tv1dateconv($sarjarow["parasta_ennen"]);
              }
            }
          }

          if ($toim == "SIIRTOLISTA") {
            list(, , $kohde_myyssa) = saldo_myytavissa($row["tuoteno"], '', $laskurow["clearing"]);

            if ($kohde_myyssa != 0) echo "<br>".t("Kohdevarastossa")." ($kohde_myyssa)";
          }

          echo "</td>";
        }
        elseif ((($toim != "TARJOUS" and $toim != "EXTTARJOUS") or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $muokkauslukko_rivi == "" and ($kukarow['extranet'] == '' or ($kukarow['extranet'] != '' and $yhtiorow['tuoteperhe_suoratoimitus'] == 'E'))) {
          if ($paikat != '') {
            echo "  <td $class align='left'>
                  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='paikat'>
                    <input type='hidden' name='toim'       value = '$toim'>
                    <input type='hidden' name='lopetus'     value = '$lopetus'>
                    <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                    <input type='hidden' name='projektilla'   value = '$projektilla'>
                    <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                    <input type='hidden' name='mista'       value = '$mista'>
                    <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                    <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                    <input type='hidden' name='rivilaadittu'  value = '$row[laadittu]'>
                    <input type='hidden' name='tuotenimitys'   value = '$row[nimitys]'>
                    <input type='hidden' name='menutila'     value = '$menutila'>
                    <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                    <input type='hidden' name='orig_alatila'  value = '$orig_alatila'>
                    <input type='hidden' name='tila'       value = 'MUUTA'>
                    <input type='hidden' name='tapa'       value = 'VAIHDAJAPOISTA'>
                    $paikat
                  </form>
                </td>";
          }
          else {
            echo "<td $class align='left'>&nbsp;</td>";
          }
        }
        elseif ((($toim != "TARJOUS" and $toim != "EXTTARJOUS") or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $kukarow['extranet'] == '') {

          if ($varow['maa'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {
            echo "<td $class align='left'><font class='error'><img src='{$palvelin2}pics/flag_icons/gif/".strtolower($varow['maa']).".gif'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font></td>";
          }
          elseif ($varow['maa'] != '' and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
            echo "<td $class align='left'><font class='error'>".strtoupper($varow['maa'])." $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font></td>";
          }
          else {
            echo "<td $class align='left'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</td>";
          }
        }
        elseif ((($toim != "TARJOUS" and $toim != "EXTTARJOUS") or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $kukarow['extranet'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {

          if ($varow['maa'] != '' ) {
            echo "<td $class align='left'><img src='{$palvelin2}pics/flag_icons/gif/".strtolower($varow['maa']).".gif'></td>";
          }
          else {
            echo "<td $class align='left'></td>";
          }
        }

        if ($saldo_valmistuksella) {
          list($_saldo, $_hyllyssa, $_myytavissa) = saldo_myytavissa($row["tuoteno"], '', $row["varasto"]);
          echo "<td $class align='left'>$_myytavissa</td>";
        }

        if ($kukarow['extranet'] == '' and $tuotekyslinkki != "") {
          echo "<td $class>
                  <a href='{$palvelin2}$tuotekyslinkki?".$tuotekyslinkkilisa."tee=Z&tuoteno=".urlencode($row["tuoteno"])."&toim_kutsu=$toim&lopetus=$tilmyy_lopetus//from=LASKUTATILAUS'
                     class='tooltip'
                     data-content-url='?toim={$toim}" .
                       "&ajax_popup=true" .
                       "&tuoteno={$row["tuoteno"]}" .
                       "&yksikko={$row["yksikko"]}" .
                       "&paikka={$row["paikka"]}" .
                       "&keskihinta={$row["kehahin"]}" .
                       "&valuutta={$laskurow["valkoodi"]}" .
                       "&varasto={$laskurow["varasto"]}" .
                       "&vanhatunnus={$laskurow["vanhatunnus"]}'>$row[tuoteno]</a>";
        }
        else {
          echo "<td $class>$row[tuoteno]";
        }

        // Näytetäänkö sarjanumerolinkki
        if (($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "T" or $row["sarjanumeroseuranta"] == "V" or (($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") and $row["varattu"] < 0)) and $row["var"] != 'P' and $row["var"] != 'T' and $row["var"] != 'U') {

          if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOTYOMAARAYS") {
            $tunken1 = "siirtorivitunnus";
            $tunken2 = "siirtorivitunnus";
          }
          elseif (($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") and $row["tyyppi"] != "V") {
            // Valmisteet
            $tunken1 = "ostorivitunnus";
            $tunken2 = "ostorivitunnus";
          }
          elseif (($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") and $row["tyyppi"] == "V" ) {
            // Raaka-aineet
            $tunken1 = "myyntirivitunnus";
            $tunken2 = "myyntirivitunnus";
          }
          elseif ($row["varattu"] < 0) {
            $tunken1 = "ostorivitunnus";
            $tunken2 = "myyntirivitunnus";
          }
          else {
            $tunken1 = "myyntirivitunnus";
            $tunken2 = "myyntirivitunnus";
          }

          if ($kukarow['extranet'] != '') {

            // jos rivillä on virhe, ei piirretä sarjanumero-dropdownia
            if ($riviok == 0 and $row['var'] != 'J' and $row['var'] != 'P') {
              $query = "SELECT DISTINCT sarjanumeroseuranta.sarjanumero,
                        sarjanumeroseuranta.tunnus,
                        sarjanumeroseuranta.myyntirivitunnus
                        FROM sarjanumeroseuranta
                        JOIN tuotepaikat ON (tuotepaikat.yhtio = sarjanumeroseuranta.yhtio and tuotepaikat.tuoteno = sarjanumeroseuranta.tuoteno)
                        JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                          AND varastopaikat.tunnus               = tuotepaikat.varasto)
                        WHERE sarjanumeroseuranta.yhtio          = '{$kukarow['yhtio']}'
                        AND sarjanumeroseuranta.tuoteno          = '{$row['tuoteno']}'
                        AND sarjanumeroseuranta.hyllyalue        = tuotepaikat.hyllyalue
                        AND sarjanumeroseuranta.hyllynro         = tuotepaikat.hyllynro
                        AND sarjanumeroseuranta.hyllyvali        = tuotepaikat.hyllyvali
                        AND sarjanumeroseuranta.hyllytaso        = tuotepaikat.hyllytaso
                        AND sarjanumeroseuranta.myyntirivitunnus IN (0, {$row['tunnus']})
                        ORDER BY sarjanumero";
              $sarjares = pupe_query($query);

              echo "&nbsp;";
              echo "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='sarjdd'>
                  <input type='hidden' name='toim' value='$toim'>
                  <input type='hidden' name='lopetus' value='$lopetus'>
                  <input type='hidden' name='ruutulimit' value='$ruutulimit'>
                  <input type='hidden' name='projektilla' value='$projektilla'>
                  <input type='hidden' name='tilausnumero' value = '$tilausnumero'>
                  <input type='hidden' name='mista' value = '$mista'>
                  <input type='hidden' name='rivitunnus' value = '{$row['tunnus']}'>
                  <input type='hidden' name='ale_peruste' value = '$row[ale_peruste]'>
                  <input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
                  <input type='hidden' name='sarjanumero_dropdown_tuoteno' value='{$row['tuoteno']}' />
                  <input type='hidden' name='menutila' value='$menutila'>
                  <input type='hidden' name='orig_tila' value='$orig_tila'>
                  <input type='hidden' name='orig_alatila' value='$orig_alatila'>
                  <input type='hidden' name='tee' value = 'PAIVITA_SARJANUMERO'>
                  <select name='sarjanumero_dropdown' onchange='submit();'>
                  <option value=''>", t("Valitse sarjanumero"), "</option>";

              while ($sarjarow = mysql_fetch_assoc($sarjares)) {
                $sel = '';

                if ($sarjanumero_dropdown == $sarjarow['tunnus']) $sel = ' selected';
                elseif ($sel == '' and $sarjarow['myyntirivitunnus'] == $row['tunnus']) $sel = ' selected';

                echo "<option value='{$sarjarow['tunnus']}'{$sel}>{$sarjarow['sarjanumero']}</option>";
              }

              echo "  </select>
                  </form>";
            }
          }
          else {
            if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "T" or $row["sarjanumeroseuranta"] == "V") {
              $query = "SELECT count(distinct sarjanumero) kpl, min(sarjanumero) sarjanumero
                        FROM sarjanumeroseuranta
                        where yhtio = '$kukarow[yhtio]'
                        and tuoteno = '$row[tuoteno]'
                        and $tunken1 = '$row[tunnus]'";

              $snro_ok = t("S:nro ok");
              $snro   = t("S:nro");
            }
            else {
              $query = "SELECT sum(era_kpl) kpl, min(sarjanumero) sarjanumero
                        FROM sarjanumeroseuranta
                        where yhtio = '$kukarow[yhtio]'
                        and tuoteno = '$row[tuoteno]'
                        and $tunken1 = '$row[tunnus]'";

              $snro_ok = t("E:nro ok");
              $snro   = t("E:nro");
            }
            $sarjares = pupe_query($query);
            $sarjarow = mysql_fetch_assoc($sarjares);

            if ($muokkauslukko_rivi == "" and $sarjarow["kpl"] == abs($row["varattu"]+$row["jt"])) {
              echo " (<a href='{$palvelin2}{$tilauskaslisa}sarjanumeroseuranta.php?tuoteno=".urlencode($row["tuoteno"])."&$tunken2=$row[tunnus]&from=$toim&lopetus=$tilmyy_lopetus//from=LASKUTATILAUS#".urlencode($sarjarow["sarjanumero"])."' class='green'>$snro_ok</font></a>)";
            }
            elseif ($muokkauslukko_rivi == "") {
              echo " (<a href='{$palvelin2}{$tilauskaslisa}sarjanumeroseuranta.php?tuoteno=".urlencode($row["tuoteno"])."&$tunken2=$row[tunnus]&from=$toim&lopetus=$tilmyy_lopetus//from=LASKUTATILAUS'>$snro</a>)";

              if ($laskurow['sisainen'] != '' or $laskurow['ei_lahetetta'] != '') {
                $sarjapuuttuu++;
                $tilausok++;
              }
            }
          }
        }

        if ($yhtiorow['laiterekisteri_kaytossa'] != '' and $toim == "YLLAPITO") {
          // Piirretään käyttöliittymään liitettyjen laitteiden sarjanumerot
          $query = "SELECT
                    group_concat(laite.sarjanro SEPARATOR '<br>') sarjanumerot
                    FROM laitteen_sopimukset
                    JOIN laite ON laite.tunnus = laitteen_sopimukset.laitteen_tunnus
                    WHERE laitteen_sopimukset.sopimusrivin_tunnus = '{$row['tunnus']}'
                    ORDER BY laite.tunnus";
          $res = pupe_query($query);
          $sarjanumerotres = mysql_fetch_assoc($res);
          echo "<br>";
          if (!empty($sarjanumerotres['sarjanumerot'])) {
            echo "<br>Sarjanumerot:<br>{$sarjanumerotres['sarjanumerot']}<br>";
            echo "<a href='{$palvelin2}kopioi_laitteita.php?toiminto=KOPIOI&tilausrivin_tunnus={$row['tunnus']}&sopimusnumero=$tilausnumero&lopetus={$palvelin2}tilauskasittely/tilaus_myynti.php////tilausnumero=$tilausnumero//toim=YLLAPITO'>".t("Kopioi laitteita")."</a>";
          }
          echo "<br>";
          echo "<a href='{$palvelin2}/laiterekisteri.php?toiminto=LINKKAA&tilausrivin_tunnus={$row['tunnus']}&sopimusnumero=$tilausnumero&lopetus={$palvelin2}tilauskasittely/tilaus_myynti.php////tilausnumero=$tilausnumero//toim=YLLAPITO'>".t("Lisää laitteita")."</a>";
        }

        echo "</td>";

        if ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V" and $row["kpl"] != 0 and ($row["perheid"] == 0 or $row["perheid"] == $row["tunnus"])) {
          echo "<td $class align='right' nowrap><input type='text' size='5' name='kpl' value='$row[varattu]' autocomplete='off'></td>";
          echo "</form>";
        }
        elseif ($toim == "MYYNTITILI" and $laskurow["alatila"] == "V" and ($row["perheid"] == 0 or $row["perheid"] == $row["tunnus"])) {
          echo "<td $class align='right' nowrap>";

          if ($row["var"] == "B") {
            echo t("Palautettu");
          }
          elseif ($row["var"] == "A") {
            echo t("Laskutettu");
          }
          else {
            echo $row["varattu"] + $row["jt"];
          }

          echo "</td>";
          echo "</form>";
        }
        else {
          $kpl_ruudulle = kpl_ruudulle($row, $laskurow, $asiakasrow);

          if ($muokkauslukko_rivi == "" and $kpl_ruudulle < 0 and ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "G")) {

            echo "<td $class align='right' nowrap>";

            $sel1 = $sel2 = "";

            if ($row["osto_vai_hyvitys"] == "O") {
              $sel2 = "SELECTED";
            }
            else {
              $sel1 = "SELECTED";
            }

            echo "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='ovaih'>
                <input type='hidden' name='toim' value='$toim'>
                <input type='hidden' name='lopetus' value='$lopetus'>
                <input type='hidden' name='ruutulimit' value='$ruutulimit'>
                <input type='hidden' name='projektilla' value='$projektilla'>
                <input type='hidden' name='tilausnumero' value = '$tilausnumero'>
                <input type='hidden' name='mista' value = '$mista'>
                <input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
                <input type='hidden' name='ale_peruste' value = '$row[ale_peruste]'>
                <input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
                <input type='hidden' name='menutila' value='$menutila'>
                <input type='hidden' name='tila' value = 'LISATIETOJA_RIVILLE_OSTO_VAI_HYVITYS'>
                <input type='hidden' name='orig_tila' value='$orig_tila'>
                <input type='hidden' name='orig_alatila' value='$orig_alatila'>
                <select name='osto_vai_hyvitys' onchange='submit();'>
                <option value=''  $sel1>$kpl_ruudulle ".("Hyvitys")."</option>
                <option value='O' $sel2>$kpl_ruudulle ".("Osto")."</option>
                </select>
                </form></td>";
          }
          elseif ($kpl_ruudulle > 0 and $row["sarjanumeroseuranta"] == "S") {

            $query = "SELECT sarjanumeroseuranta.kaytetty
                      FROM sarjanumeroseuranta
                      WHERE sarjanumeroseuranta.yhtio          = '$kukarow[yhtio]'
                      and sarjanumeroseuranta.myyntirivitunnus = '$row[tunnus]'";
            $muutares = pupe_query($query);
            $muutarow = mysql_fetch_assoc($muutares);

            if ($muokkauslukko_rivi == "" and $muutarow["kaytetty"] != "") {
              echo "<td $class align='right' nowrap>";

              $sel1 = $sel2 = "";

              if ($row["osto_vai_hyvitys"] == "H") {
                $sel2 = "SELECTED";
              }
              else {
                $sel1 = "SELECTED";
              }

              echo "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='ovaih'>
                  <input type='hidden' name='toim' value='$toim'>
                  <input type='hidden' name='lopetus' value='$lopetus'>
                  <input type='hidden' name='ruutulimit' value='$ruutulimit'>
                  <input type='hidden' name='projektilla' value='$projektilla'>
                  <input type='hidden' name='tilausnumero' value = '$tilausnumero'>
                  <input type='hidden' name='mista' value = '$mista'>
                  <input type='hidden' name='rivitunnus' value = '$row[tunnus]'>
                  <input type='hidden' name='ale_peruste' value = '$row[ale_peruste]'>
                  <input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
                  <input type='hidden' name='menutila' value='$menutila'>
                  <input type='hidden' name='orig_tila' value='$orig_tila'>
                  <input type='hidden' name='orig_alatila' value='$orig_alatila'>
                  <input type='hidden' name='tila' value = 'LISATIETOJA_RIVILLE_OSTO_VAI_HYVITYS'>
                  <select name='osto_vai_hyvitys' onchange='submit();'>
                  <option value=''  $sel1>$kpl_ruudulle ".("Myynti")."</option>
                  <option value='H' $sel2>$kpl_ruudulle ".("Hyvitys")."</option>
                  </select>
                  </form></td>";

            }
            else {
              echo "<td $class align='right' nowrap>$kpl_ruudulle</td>";
            }
          }
          elseif (in_array($toim, array('VALMISTAVARASTOON', 'VALMISTAASIAKKAALLE', 'RIVISYOTTO', 'PIKATILAUS'))) {
            echo "<td {$class} align='right' nowrap>";
            echo "{$kpl_ruudulle} ".strtolower($row["tilausrivin_yksikko"]);

            if ($sahkoinen_tilausliitanta and isset($vastaavat_html) and trim($vastaavat_html) != '' and isset($vastaavat_table2) and trim($vastaavat_table2) != '' and isset($paarivin_saldokysely) and $paarivin_saldokysely and in_array($row['var'], array('U', 'T'))) {
              echo "<br />", $vastaavat_table2;
            }

            echo "</td>";
          }
          else {
            echo "<td $class align='right' nowrap>$kpl_ruudulle</td>";
          }
        }

        if (($toim == "VALMISTAVARASTOON" and $yhtiorow["kehahinta_valmistuksella"] == "K")
          or ($toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA")) {
          $classvar = $class;
        }
        else {
          if ($classlisa != "") {
            $classvar = $classlisa;
          }
          else {
            $classvar = $class;
          }
        }

        $var_temp = var_kaannos($row['var']);

        if ($row['var'] == 'J' and $row['jt_manual'] == 'K') {
          $var_temp = $var_temp . " - ".t("Manuaalinen");
        }
        elseif ($laskurow["tila"] != 'G' and $row['var'] == 'J' and strtotime($row['kerayspvm']) > strtotime($laskurow['kerayspvm']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and !empty($yhtiorow['jt_automatiikka'])) {
          $var_temp = $var_temp . " - ".t("Muiden mukana");
        }
        else {
          if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and $row['var'] == 'J') {
            $var_temp = $var_temp." - ".t("heti");
          }
        }

        echo "<td $classvar>$var_temp";

        if ($yhtiorow['tilausrivin_korvamerkinta'] == 'K' and !empty($row['korvamerkinta'])) {

          if ($row['korvamerkinta'] == '.') {
            $luokka = '';
          }
          else {
            $luokka = 'tooltip';
          }

          echo "&nbsp;<img src='{$palvelin2}pics/lullacons/info.png' class='{$luokka}' id='{$row['tunnus']}_info'>";
          echo "<div id='div_{$row['tunnus']}_info' class='popup'>";
          echo $row['korvamerkinta'];
          echo "</div>";
        }

        echo "</td>";

        if ($toim == "VALMISTAVARASTOON" and $yhtiorow['kehahinta_valmistuksella'] == "K") {
          echo "<td {$class} align='right' nowrap>";
          echo "{$row["kehahin"]}";
          echo "</td>";

          $painotettukeha = $kpl_ruudulle * $row["kehahin"];
          if ($row["tyyppi"] == "V") {
            $tuotteenpainotettukehayht["keha"] += $painotettukeha;
            $painotettukehayhteensa += $painotettukeha;
          }
          else {
            $tuotteenpainotettukehayht["tuoteno"] = $row["tuoteno"];
          }

          if ($classlisa != "") {
            $classvar = $classlisa;
          }
          else {
            $classvar = $class;
          }

          echo "<td {$classvar} align='right' nowrap>";
          echo "{$painotettukeha}";
          echo "</td>";
        }

        if ($_onko_valmistus and $yhtiorow["varastonarvon_jako_usealle_valmisteelle"] == "K") {
          echo "<td $class>";
          if ($row['tyyppi'] == 'W' and count($hinta_laskurit[$row['perheid']]['valmisteet']) > 1 and $hinta_laskurit[$row['perheid']]['raakaaineiden_kehahinta_summa']>0) {
            echo '<input type="text" name="valmiste_valuutta['.$row['tunnus'].']" data-tunnus="'.$row['tunnus'].'" data-perheid="'.$row['perheid'].'" />';
          }
          else {
            echo round($row['kehahin'] * $row['tilkpl'], $yhtiorow['hintapyoristys']);
          }
          echo '</td>';

          echo "<td $classvar>";
          if ($row['tyyppi'] == 'W') {
            echo '<input type="checkbox" class="valmiste_lukko" data-tunnus="'.$row['tunnus'].'" data-perheid="'.$row['perheid'].'" />';
          }
          echo '</td>';
        }

        if ($toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA") {

          $hinta = $row["hinta"];
          $netto = $row["netto"];
          $kpl   = $row["varattu"]+$row["jt"];

          if ($yhtiorow["alv_kasittely"] == "") {

            // Oletuksena verolliset hinnat ja ei käännettyä arvonlisäverovelvollisuutta
            if ($tilausrivi_alvillisuus == "E" and $row["alv"] < 500) {
              $alvillisuus_jako = 1 + $row["alv"] / 100;
            }
            else {
              // Oletukset
              $alvillisuus_jako = 1;
            }

            $hinta        = hintapyoristys($hinta / $alvillisuus_jako);
            $summa        = hintapyoristys($summa / $alvillisuus_jako);
            $myyntihinta  = hintapyoristys(tuotteen_myyntihinta($laskurow, $trow, 1) / $alvillisuus_jako);
          }
          else {
            // Oletuksena verottomat hinnat tai käännetty arvonlisäverovelvollisuus
            if ($tilausrivi_alvillisuus == "E" or $row["alv"] >= 600) {
              // Oletukset
              $alvillisuus_kerto = 1;
            }
            else {
              // Halutaan alvilliset hinnat
              $alvillisuus_kerto = 1 + $row["alv"] / 100;
            }

            $hinta        = hintapyoristys($hinta * $alvillisuus_kerto);
            $summa        = hintapyoristys($summa * $alvillisuus_kerto);
            $myyntihinta  = hintapyoristys(tuotteen_myyntihinta($laskurow, $trow, 1) * $alvillisuus_kerto);
          }

          $kplhinta = $hinta * generoi_alekentta_php($row, 'M', 'kerto', 'ei_erikoisale');

          if ($kukarow['hinnat'] == 1) {
            echo "<td $class align='right'>$myyntihinta</td>";
          }
          elseif ($kukarow['hinnat'] == 0) {

            if ($myyntihinta != $hinta) $myyntihinta = hintapyoristys($myyntihinta)." (".hintapyoristys($hinta).")";
            else $myyntihinta = hintapyoristys($myyntihinta);

            echo "<td $class align='right'>$myyntihinta</td>";

            if (!empty($row["netto"])) {
              echo "<td $class align='right'>".t("NETTO")."</td>";
            }
            else {
              echo "<td {$class} align='right'>";
              $ale_echo = "";
              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                $ale_echo .= $row["ale{$alepostfix}"]." + ";
              }

              $ale_echo = substr($ale_echo, 0, -3);
              echo "$ale_echo %</td>";
            }

            echo "<td $class align='right'>".hintapyoristys($kplhinta, 2)."</td>";
          }

          if ($kukarow['hinnat'] == 1) {
            echo "<td $class align='right'>".hintapyoristys($myyntihinta * ($row["varattu"] + $row["jt"]))."</td>";
          }
          elseif ($kukarow['hinnat'] == 0) {
            echo "<td $class align='right'>".hintapyoristys($summa)."</td>";
          }

          if ($kukarow['extranet'] == '' and $naytetaanko_kate) {
            // Tän rivin kate
            $kate = laske_tilausrivin_kate($row, $kotisumma_alviton, $row["kehahin"], $kpl, $arow);

            echo "<td $class align='right' nowrap>$kate%</td>";
          }

          if ($classlisa != "") {
            $classx = $classlisa;
          }
          else {
            $classx = $class;
          }

          if ($row["alv"] >= 500) {
            echo "<td $classx align='right' nowrap>";
            if ($row["alv"] >= 600) {
              echo t("K.V.");
            }
            else {
              echo t("M.V.");
            }
            echo "</td>";
          }
          else {
            echo "<td $classx align='right' nowrap>".($row["alv"] * 1)."</td>";
          }
        }

        echo "<td class='ptop back' nowrap>";

        if ($varaosavirhe != '') {
          echo "<font class='error'>$varaosavirhe</font>";
        }
        if ($varaosakommentti != '') {
          echo "<font class='info'>$varaosakommentti</font>";
        }

        $varaosavirhe = "";
        $varaosakommentti = "";

        if ((((($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or $row["perheid"] == 0) and $kukarow['extranet'] != '') or $kukarow['extranet'] == '') and (($muokkauslukko == "" and $muokkauslukko_rivi == "") or $_luottoraja_ylivito) or $toim == "YLLAPITO") {

          if (($yhtiorow['lapsituotteen_poiston_esto'] == 0 or (($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or $row["perheid"] == 0)) and
            ($kukarow['extranet'] == '' or ($kukarow['extranet'] != '' and $row['positio'] != 'JT'))) {

            if (empty($muokkauslukko_rivi) and (!$_luottoraja_ylivito or $_keratty_ja_ylitetty)) {

              $_btn_class = $_keratty_toimitettu ? 'muokkaa_btn' : '';

              echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='muokkaa' class='muokkaa_form'>
                  <input type='hidden' name='toim'         value = '$toim'>
                  <input type='hidden' name='lopetus'      value = '$lopetus'>
                  <input type='hidden' name='ruutulimit'   value = '$ruutulimit'>
                  <input type='hidden' name='projektilla'  value = '$projektilla'>
                  <input type='hidden' name='tilausnumero' value = '$tilausnumero'>
                  <input type='hidden' name='mista'        value = '$mista'>
                  <input type='hidden' name='rivitunnus'   value = '$row[tunnus]'>
                  <input type='hidden' name='keratty'      value = '$row[keratty]'>
                  <input type='hidden' name='kerattyaika'  value = '$row[kerattyaika]'>
                  <input type='hidden' name='toimitettu'   value = '$row[toimitettu]'>
                  <input type='hidden' name='toimitettuaika' value = '$row[toimitettuaika]'>
                  <input type='hidden' name='ale_peruste'  value = '$row[ale_peruste]'>
                  <input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
                  <input type='hidden' name='menutila'     value = '$menutila'>
                  <input type='hidden' name='tuotenimitys' value = '$row[nimitys]'>
                  <input type='hidden' name='orig_tila'    value = '$orig_tila'>
                  <input type='hidden' name='orig_alatila' value = '$orig_alatila'>
                  <input type='hidden' name='tila'         value = 'MUUTA'>
                  <input type='hidden' name='tapa'         value = 'MUOKKAA'>
                  <input type='hidden' id='keratty_ja_ylitetty_warning' value = '".t('Tilaus on jo kerätty ja/tai toimitettu. Oletko varma että haluat muokata riviä?')."'>
                  <input type='submit' class='{$_btn_class}' value='".t("Muokkaa")."'>
                  </form> ";
            }

            $poista_onclick = "";

            if ($row['vanha_otunnus'] != $tilausnumero) {
              //kyseessä JT-rivi tai JT-muiden mukana, joka tulee asiakkaan edellisiltä tilauksilta. Näille riveille halutaan poista nappiin alertti
              $poista_onclick = "onclick='return nappi_onclick_confirm(\"".t('Olet poistamassa automaattisesti lisätyn jälkitoimitusrivin oletko varma')."?\");'";
            }

            echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='poista'>
                <input type='hidden' name='toim'       value = '$toim'>
                <input type='hidden' name='lopetus'     value = '$lopetus'>
                <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                <input type='hidden' name='projektilla'   value = '$projektilla'>
                <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                <input type='hidden' name='mista'       value = '$mista'>
                <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                <input type='hidden' name='rivilaadittu'  value = '$row[laadittu]'>
                <input type='hidden' name='menutila'     value = '$menutila'>
                <input type='hidden' name='orig_tila'    value = '$orig_tila'>
                <input type='hidden' name='orig_alatila'  value = '$orig_alatila'>
                <input type='hidden' name='tila'       value = 'MUUTA'>
                <input type='hidden' name='tapa'       value = 'POISTA'>
                <input type='submit' class='poista_btn' value='".t("Poista")."' $poista_onclick>
                </form> ";
          }

          if ((($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or $row["perheid"] == 0) and $toim == 'VALMISTAVARASTOON' and $kukarow['extranet'] == '') {

            if ($row["perheid"] == 0) {
              $lisax = "<input type='hidden' name='teeperhe'  value = 'OK'>";
            }

            echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='lisaakertareseptiin'>
                <input type='hidden' name='toim'       value = '$toim'>
                <input type='hidden' name='lopetus'     value = '$lopetus'>
                <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                <input type='hidden' name='projektilla'   value = '$projektilla'>
                <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                <input type='hidden' name='mista'       value = '$mista'>
                <input type='hidden' name='tila'       value = 'LISAAKERTARESEPTIIN'>
                $lisax
                <input type='hidden' name='isatunnus'     value = '$row[tunnus]'>
                <input type='hidden' name='perheid'       value = '$row[perheid]'>
                <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                <input type='hidden' name='valmiste_vai_raakaaine' value='{$valmiste_vai_raakaaine}' />
                <input type='submit' value='".t("Lisää raaka-aine")."'>
                </form>";

            echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='lisaaisakertareseptiin'>
                <input type='hidden' name='toim'       value = '$toim'>
                <input type='hidden' name='lopetus'     value = '$lopetus'>
                <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                <input type='hidden' name='projektilla'   value = '$projektilla'>
                <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                <input type='hidden' name='mista'       value = '$mista'>
                <input type='hidden' name='tila'       value = 'LISAAISAKERTARESEPTIIN'>
                $lisax
                <input type='hidden' name='isatunnus'     value = '$row[tunnus]'>
                <input type='hidden' name='perheid'       value = '$row[perheid]'>
                <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                <input type='hidden' name='valmiste_vai_raakaaine' value='{$valmiste_vai_raakaaine}' />
                <input type='submit' value='".t("Lisää valmiste")."'>
                </form>";
          }
          elseif ((($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0 and ($toim != "VALMISTAASIAKKAALLE" or $yhtiorow["raaka_aineet_valmistusmyynti"] != "N")) or ($row["tunnus"] == $row["perheid2"] and $row["perheid2"] != 0) or (($toim == 'SIIRTOLISTA' or $toim == "SIIRTOTYOMAARAYS" or $toim == "TARJOUS" or $toim == "EXTTARJOUS" or $laskurow["tilaustyyppi"] == "T") and $row["perheid2"] == 0 and $row["perheid"] == 0)) and $kukarow['extranet'] == '') {

            if ($row["perheid2"] == 0 and $row["perheid"] == 0) {
              $lisax = "<input type='hidden' name='teeperhe'  value = 'OK'>";
            }

            if ($laskurow["tila"] == "V") {
              $nappulanteksti = t("Lisää reseptiin");
            }
            else {
              $nappulanteksti = t("Lisää tuote");
            }

            echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='lisaareseptiin'>
                <input type='hidden' name='toim'       value = '$toim'>
                <input type='hidden' name='lopetus'     value = '$lopetus'>
                <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                <input type='hidden' name='projektilla'   value = '$projektilla'>
                <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                <input type='hidden' name='mista'       value = '$mista'>
                <input type='hidden' name='tila'      value = 'LISAARESEPTIIN'>
                $lisax
                <input type='hidden' name='isatunnus'     value = '$row[tunnus]'>
                <input type='hidden' name='perheid'     value = '$row[perheid]'>
                <input type='hidden' name='perheid2'     value = '$row[perheid2]'>
                <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                <input type='submit' value='$nappulanteksti'>
                </form>";
          }

          // Jos JT-rivit varaa saldoa, niin ei anneta tän kysisen rivin syödä omaa saldoaan.
          if ($yhtiorow["varaako_jt_saldoa"] != "") {
            $_jt_tm_lisavarattu = $kpl_ruudulle;
          }
          else {
            $_jt_tm_lisavarattu = 0;
          }

          if ($row["var"] == "J" and !$_luottoraja_ylivito and (($row["ei_saldoa"] != "" or ($selpaikkamyytavissa+$_jt_tm_lisavarattu) >= $kpl_ruudulle) and $kukarow['extranet'] == '')) {
            echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='toimita'>
                <input type='hidden' name='toim'         value = '$toim'>
                <input type='hidden' name='lopetus'      value = '$lopetus'>
                <input type='hidden' name='ruutulimit'   value = '$ruutulimit'>
                <input type='hidden' name='projektilla'  value = '$projektilla'>
                <input type='hidden' name='tilausnumero' value = '$tilausnumero'>
                <input type='hidden' name='mista'        value = '$mista'>
                <input type='hidden' name='rivitunnus'   value = '$row[tunnus]'>
                <input type='hidden' name='ale_peruste'  value = '$row[ale_peruste]'>
                <input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
                <input type='hidden' name='menutila'     value = '$menutila'>
                <input type='hidden' name='orig_tila'    value = '$orig_tila'>
                <input type='hidden' name='orig_alatila' value = '$orig_alatila'>
                <input type='hidden' name='tila'         value = 'MUUTA'>
                <input type='hidden' name='tapa'         value = 'POISJTSTA'>
                <input type='submit' value = '".t("Toimita")."'>
                </form> ";
          }

          if ($yhtiorow['tilausrivin_korvamerkinta'] == 'K') {
            echo "<br>
                  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='korvamerkitse'>
                  <input type='hidden' name='toim'       value = '$toim'>
                  <input type='hidden' name='lopetus'     value = '$lopetus'>
                  <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                  <input type='hidden' name='projektilla'   value = '$projektilla'>
                  <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                  <input type='hidden' name='mista'       value = '$mista'>
                  <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                  <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                  <input type='hidden' name='rivilaadittu'   value = '$row[laadittu]'>
                  <input type='hidden' name='menutila'     value = '$menutila'>
                  <input type='hidden' name='tila'       value = 'KORVAMERKITSE'>
                  <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                  <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                  <span class='korvaspan' id='korvaspan_{$row['tunnus']}' style='display:none'>
                  <input type='Submit' style='margin:5px 0' value='".t("Korvamerkitse")."'>
                  <input type='text' class='korvamerkinta' style='padding:0; margin:0; position:relative; top:1px;' name='korvamerkinta' value = ''>
                  <img src='{$palvelin2}pics/lullacons/stop.png' alt='Peru' title='Peru' class='korvaperu' style='position:relative; top:4px;'>
                  </span>
                  </form>
                  <input type='Submit' class='korvabutton' style='margin:5px 0' id='korvabutton_{$row['tunnus']}' value='".t("Korvamerkitse")."'>

                  <script type='text/javascript' language='javascript'>

                  $('.korvaperu').click(function() {
                    $('.korvabutton').show();
                    $('.korvaspan').hide();
                    $('.korvamerkinta').val('');
                  });

                  $('#korvabutton_{$row['tunnus']}').click(function() {
                    $('.korvabutton').show();
                    $(this).hide();
                    $('.korvaspan').hide();
                    $('.korvamerkinta').val('');
                    $('#korvaspan_{$row['tunnus']}').show();
                  });

                  </script>";
          }


          if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {
            $napinnimi = t("Jälkitoim, heti");
          }
          else {
            $napinnimi = t("Jälkitoim");
          }

          if ((($row["tunnus"] == $row["perheid"] and $row["perheid"] != 0) or $row["perheid"] == 0)
            and ($row["var"] == 'P' or (in_array($row["var"], array('', 'H')) and ($toim == 'PIKATILAUS' or $toim == 'RIVISYOTTO')))
            and $saako_jalkitoimittaa == 0
            and $laskurow["jtkielto"] != "o"
            and $row["status"] != 'P'
            and $row["status"] != 'X'
            and !$_luottoraja_ylivito
            and $yhtiorow["puute_jt_oletus"] != "H"
          ) {

            echo "<br />";

            echo " <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='jalkitoimita'>
                  <input type='hidden' name='toim'       value = '$toim'>
                  <input type='hidden' name='lopetus'     value = '$lopetus'>
                  <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                  <input type='hidden' name='projektilla'   value = '$projektilla'>
                  <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                  <input type='hidden' name='mista'       value = '$mista'>
                  <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                  <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                  <input type='hidden' name='rivilaadittu'   value = '$row[laadittu]'>
                  <input type='hidden' name='menutila'     value = '$menutila'>
                  <input type='hidden' name='tila'       value = 'MUUTA'>
                  <input type='hidden' name='tapa'       value = 'VAIHDAJAPOISTA'>
                  <input type='hidden' name='var'       value = 'J'>
                  <input type='hidden' name='jt_muidenmukana' value = 'EI'>
                  <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                  <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                  <input type='submit' value='{$napinnimi}'>
                  </form> ";

            if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and $laskurow["tila"] != 'G') {
              echo " <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='jalkitoimita'>
                  <input type='hidden' name='toim'       value = '$toim'>
                  <input type='hidden' name='lopetus'     value = '$lopetus'>
                  <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                  <input type='hidden' name='projektilla'   value = '$projektilla'>
                  <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                  <input type='hidden' name='mista'       value = '$mista'>
                  <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                  <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                  <input type='hidden' name='rivilaadittu'   value = '$row[laadittu]'>
                  <input type='hidden' name='menutila'     value = '$menutila'>
                  <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                  <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                  <input type='hidden' name='tila'       value = 'MUUTA'>
                  <input type='hidden' name='tapa'       value = 'VAIHDAJAPOISTA'>
                  <input type='hidden' name='var'       value = 'J'>
                  <input type='hidden' name='jt_muidenmukana' value = 'KYLLA'>
                  <input type='submit' value='" . t("Jälkitoim, muiden mukana") . "'>
                  </form> ";
            }

            if (!empty($yhtiorow['jt_manual'])) {
              echo " <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='jalkitoimita'>
                  <input type='hidden' name='toim'       value = '$toim'>
                  <input type='hidden' name='lopetus'     value = '$lopetus'>
                  <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                  <input type='hidden' name='projektilla'   value = '$projektilla'>
                  <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                  <input type='hidden' name='mista'       value = '$mista'>
                  <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                  <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                  <input type='hidden' name='menutila'     value = '$menutila'>
                  <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                  <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                  <input type='hidden' name='tila'       value = 'MUUTA'>
                  <input type='hidden' name='tapa'       value = 'VAIHDAJAPOISTA'>
                  <input type='hidden' name='var'       value = 'J'>
                  <input type='hidden' name='jt_manual' value = 'KYLLA'>
                  <input type='submit' value='" . t("Jälkitoim, manuaalinen") . "'>
                  </form> ";
            }
          }

          if ($row["jt"] != 0 and $yhtiorow["puute_jt_oletus"] == "J" and !$_luottoraja_ylivito) {
            echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='puutetoimita'>
                <input type='hidden' name='toim'       value = '$toim'>
                <input type='hidden' name='lopetus'     value = '$lopetus'>
                <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                <input type='hidden' name='projektilla'   value = '$projektilla'>
                <input type='hidden' name='tilausnumero'  value = '$tilausnumero'>
                <input type='hidden' name='mista'       value = '$mista'>
                <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                <input type='hidden' name='rivilaadittu'   value = '$row[laadittu]'>
                <input type='hidden' name='menutila'     value = '$menutila'>
                <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                <input type='hidden' name='tila'       value = 'MUUTA'>
                <input type='hidden' name='tapa'       value = 'PUUTE'>
                <input type='hidden' name='var'       value = 'P'>
                <input type='submit' value='" . t("Puute") . "'>
                </form> ";
          }

          if ($saako_hyvaksya > 0 and !$_luottoraja_ylivito) {
            echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='hyvaksy'>
                <input type='hidden' name='toim'       value = '$toim'>
                <input type='hidden' name='lopetus'     value = '$lopetus'>
                <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                <input type='hidden' name='projektilla'   value = '$projektilla'>
                <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                <input type='hidden' name='mista'       value = '$mista'>
                <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                <input type='hidden' name='ale_peruste'    value = '$row[ale_peruste]'>
                <input type='hidden' name='rivilaadittu'   value = '$row[laadittu]'>
                <input type='hidden' name='menutila'     value = '$menutila'>
                <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                <input type='hidden' name='tila'       value = 'OOKOOAA'>
                <input type='submit' value='".t("Hyväksy")."'>
                </form> ";
          }

          if (isset($pikaperustus_naytetaan) and $pikaperustus_naytetaan > 0 and tarkista_oikeus("yllapito.php", "tuote!!!PIKAPERUSTA!!!true", "JOO") and !$_luottoraja_ylivito) {
            echo " <form method='post' action='{$palvelin2}yllapito.php' name='pikaperusta'>
                <input type='hidden' name='toim' value='tuote!!!PIKAPERUSTA!!!true'>
                <input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=LASKUTATILAUS''>
                <input type='hidden' name='uusi' value='1'>
                <input type='hidden' name='from' value='myyntitilaus'>
                <input type='hidden' name='ohje' value='off'>
                <input type='hidden' name='t[1]' value='{$row["tuoteno"]}'>
                <input type='hidden' name='t[16]' value='".hintapyoristys($row["hinta"])."'>
                <input type='submit' value='".t("Pikaperusta")."'>
                </form>";
          }

          if (!empty($row['jt_manual']) or (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and $row['var'] == 'J')) echo "<br />";

          if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and $row['var'] == 'J' and (strtotime($row['kerayspvm']) > strtotime($laskurow['kerayspvm']) or $row['jt_manual'] == 'K') and !$_luottoraja_ylivito) {
            echo " <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='jalkitoimita'>
                  <input type='hidden' name='toim'       value = '$toim'>
                  <input type='hidden' name='lopetus'     value = '$lopetus'>
                  <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                  <input type='hidden' name='projektilla'   value = '$projektilla'>
                  <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                  <input type='hidden' name='mista'       value = '$mista'>
                  <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                  <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                  <input type='hidden' name='rivilaadittu'   value = '$row[laadittu]'>
                  <input type='hidden' name='menutila'     value = '$menutila'>
                  <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                  <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                  <input type='hidden' name='tila'       value = 'MUUTA'>
                  <input type='hidden' name='tapa'       value = 'VAIHDAJAPOISTA'>
                  <input type='hidden' name='var'       value = 'J'>
                  <input type='hidden' name='jt_muidenmukana' value = 'EI'>
                  <input type='submit' value='{$napinnimi}'>
                  </form> ";
          }

          if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and $row['var'] == 'J' and strtotime($row['kerayspvm']) == strtotime($laskurow['kerayspvm']) and !$_luottoraja_ylivito and $laskurow["tila"] != 'G') {
            echo " <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='jalkitoimita'>
                  <input type='hidden' name='toim'       value = '$toim'>
                  <input type='hidden' name='lopetus'     value = '$lopetus'>
                  <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                  <input type='hidden' name='projektilla'   value = '$projektilla'>
                  <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                  <input type='hidden' name='mista'       value = '$mista'>
                  <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                  <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                  <input type='hidden' name='rivilaadittu'   value = '$row[laadittu]'>
                  <input type='hidden' name='menutila'     value = '$menutila'>
                  <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                  <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                  <input type='hidden' name='tila'       value = 'MUUTA'>
                  <input type='hidden' name='tapa'       value = 'VAIHDAJAPOISTA'>
                  <input type='hidden' name='var'       value = 'J'>
                  <input type='hidden' name='jt_muidenmukana' value = 'KYLLA'>
                  <input type='submit' value='" . t("Jälkitoim, muiden mukana"). "'>
                  </form> ";
          }

          if (!empty($yhtiorow['jt_manual']) and $row['var'] == 'J' and $row['jt_manual'] == '' and !$_luottoraja_ylivito) {
            echo " <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='jalkitoimita'>
                <input type='hidden' name='toim'       value = '$toim'>
                <input type='hidden' name='lopetus'     value = '$lopetus'>
                <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                <input type='hidden' name='projektilla'   value = '$projektilla'>
                <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                <input type='hidden' name='mista'       value = '$mista'>
                <input type='hidden' name='rivitunnus'     value = '$row[tunnus]'>
                <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
                <input type='hidden' name='menutila'     value = '$menutila'>
                <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                <input type='hidden' name='tila'       value = 'MUUTA'>
                <input type='hidden' name='tapa'       value = 'VAIHDAJAPOISTA'>
                <input type='hidden' name='var'       value = 'J'>
                <input type='hidden' name='jt_manual' value = 'KYLLA'>
                <input type='submit' value='" . t("Jälkitoim, manuaalinen") . "'>
                </form> ";
          }
        }
        elseif ($row["laskutettuaika"] != '0000-00-00') {
          echo "<font class='info'>".t("Laskutettu").": ".tv1dateconv($row["laskutettuaika"])."</font>";
        }
        elseif ($row["toimitettuaika"] != '0000-00-00 00:00:00') {
          echo "<font class='info'>".t("Toimitettu").": ".tv1dateconv($row["toimitettuaika"], "P")."</font>";
        }

        if ($muokkauslukko_rivi == "" and $kukarow["extranet"] == "" and ($toim == "TARJOUS" or $toim == "EXTTARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "SIIRTOTYOMAARAYS") and $riviok == 0) {
          //Tutkitaan tuotteiden lisävarusteita
          $query  = "SELECT *
                     FROM tuoteperhe
                     JOIN tuote ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.tuoteno
                     WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
                     and tuoteperhe.isatuoteno = '$row[tuoteno]'
                     and tuoteperhe.tyyppi     = 'L'
                     order by tuoteperhe.tuoteno";
          $lisaresult = pupe_query($query);

          if (mysql_num_rows($lisaresult) > 0 and ($row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")) or $row["perheid2"] == -1) {
            echo "</tr>";

            echo "  <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' autocomplete='off' name='lisavarusteet'>
                <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                <input type='hidden' name='mista'       value = '$mista'>
                <input type='hidden' name='toim'       value = '$toim'>
                <input type='hidden' name='lopetus'     value = '$lopetus'>
                <input type='hidden' name='ruutulimit'     value = '$ruutulimit'>
                <input type='hidden' name='projektilla'   value = '$projektilla'>
                <input type='hidden' name='orig_tila'     value = '$orig_tila'>
                <input type='hidden' name='orig_alatila'   value = '$orig_alatila'>
                <input type='hidden' name='lisavarusteita'   value = 'ON'>
                <input type='hidden' name='perheid2'     value = '$row[tunnus]'>";

            $lislask = 0;

            while ($prow = mysql_fetch_assoc($lisaresult)) {

              echo "<tr>$jarjlisa";

              if ($toim == "TARJOUS" or $toim == "EXTTARJOUS" or $laskurow["tilaustyyppi"] == "T") {
                echo "<td>&nbsp;</td>";
              }

              echo "<td class='ptop'>".t_tuotteen_avainsanat($prow, 'nimitys')."</td>";
              echo "<input type='hidden' name='tuoteno_array[$prow[tuoteno]]' value='$prow[tuoteno]'>";

              if ($row["var"] == "T") {
                echo "<input type='hidden' name='paikka_array[$prow[tuoteno]]' value='¡¡¡$row[toimittajan_tunnus]'>";
              }
              if ($row["var"] == "U") {
                echo "<input type='hidden' name='paikka_array[$prow[tuoteno]]' value='!!!$row[toimittajan_tunnus]'>";
              }

              echo "<td>&nbsp;</td>";
              echo "<td class='ptop'>$prow[tuoteno]</td>";
              echo "<td class='ptop' align='right'><input type='text' name='kpl_array[$prow[tuoteno]]' size='2' maxlength='8'></td>";

              echo "  <td class='ptop'><input type='text' name='var_array[$prow[tuoteno]]'   size='2' maxlength='1'></td>
                  <td class='ptop'><input type='text' name='netto_array[$prow[tuoteno]]' size='2' maxlength='1'></td>
                  <td class='ptop'><input type='text' name='hinta_array[$prow[tuoteno]]' size='5' maxlength='12'></td>";

              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                echo "<td class='ptop'><input type='text' name='ale_array{$alepostfix}[$prow[tuoteno]]' size='5' maxlength='6'></td>";
              }

              $lislask++;

              if ($lislask == mysql_num_rows($lisaresult)) {
                echo "<td class='back ptop'><input type='submit' class='lisaa_btn' value='".t("Lisää")."'></td>";
                echo "</form>";
              }

              echo "</tr>";
            }
          }
          elseif ($kukarow["extranet"] == "" and mysql_num_rows($lisaresult) > 0) {
            echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' autocomplete='off' name='lisaalisav'>
                <input type='hidden' name='tilausnumero' value='$tilausnumero'>
                <input type='hidden' name='mista' value = '$mista'>
                <input type='hidden' name='toim' value='$toim'>
                <input type='hidden' name='lopetus' value='$lopetus'>
                <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
                <input type='hidden' name='projektilla' value='$projektilla'>";

            if ($row["perheid2"] == 0 or ($row["var"] != "T" and $row["var"] != "U")) {
              echo "<input type='hidden' name='spessuceissi' value='OK'>";
            }

            echo "  <input type='hidden' name='tila' value='LISLISAV'>
                <input type='hidden' name='rivitunnus' value='$row[tunnus]'>
                <input type='hidden' name='ale_peruste' value = '$row[ale_peruste]'>
                <input type='hidden' name='rivilaadittu' value = '$row[laadittu]'>
                <input type='hidden' name='menutila' value='$menutila'>
                <input type='hidden' name='orig_tila' value='$orig_tila'>
                <input type='hidden' name='orig_alatila' value='$orig_alatila'>
                <input type='submit' value='".t("Lisää lisävarusteita")."'>
                </form> ";
          }
        }

        echo "</td></tr>";

        if (isset($GLOBALS['eta_yhtio']) and $GLOBALS['eta_yhtio'] != '' and $koti_yhtio == $kukarow['yhtio']) {
          $query = "SELECT *
                    FROM tuote
                    WHERE yhtio = '{$GLOBALS['eta_yhtio']}'
                    AND tuoteno = '{$row['tuoteno']}'";
          $tres_eta = pupe_query($query);
          $trow_eta = mysql_fetch_assoc($tres_eta);

          list($lis_hinta_eta, $lis_netto_eta, $lis_eta_ale_kaikki, $alehinta_alv_eta, $alehinta_val_eta) = alehinta($laskurow, $trow_eta, $kpl_ruudulle, '', '', '', '', $GLOBALS['eta_yhtio']);

          $row['kommentti'] .= "\n".t("Hinta").": ".hintapyoristys($lis_hinta_eta);

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            $row['kommentti'] .= ", ".t("Ale")."{$alepostfix}: ".($lis_eta_ale_kaikki["ale{$alepostfix}"]*1)."%";
          }

          $row['kommentti'] .= ", ".t("Alv").": ".($row['alv']*1)."%";

          $hintapyoristys_echo = $lis_hinta_eta;

          foreach ($lis_eta_ale_kaikki as $val) {
            $hintapyoristys_echo *= (1 - ($val / 100));
          }

          $etayhtio_totaalisumma +=  ($hintapyoristys_echo * $kpl_ruudulle);

          $row['kommentti'] .= ", ".t("Rivihinta").": ".hintapyoristys($hintapyoristys_echo * $kpl_ruudulle);
        }

        if ($kommenttirivi_nakyviin or $row['kommentti'] != '' or $yhtiorow['tilausrivin_korvamerkinta'] == 'A' or ($yhtiorow['naytetaanko_ale_peruste_tilausrivilla'] != '' and $row['ale_peruste'] != '')) {

          echo "<tr>";

          if ($borderlask == 0 and $pknum > 1) {
            $kommclass1 = " style='border-bottom: 1px solid{$bordercolor}; border-right: 1px solid{$bordercolor};'";
            $kommclass2 = " style='border-bottom: 1px solid{$bordercolor};'";
          }
          elseif ($pknum > 0) {
            $kommclass1 = " style='border-right: 1px solid{$bordercolor};'";
            $kommclass2 = " ";
          }
          else {
            $kommclass1 = "";
            $kommclass2 = " ";
          }

          echo "<td $kommclass1 colspan='".($sarakkeet-1)."' class='ptop'>";

          if ($yhtiorow['tilausrivin_korvamerkinta'] == 'A') {
            $kresult = t_avainsana("KORVAMERKKI");

            echo "<div style='float: right;'>";
            echo "<select name='korvamerkinta' class='korva_dd' id='korva_dd_$row[tunnus]'>";
            echo "<option value = ''> *** </option>";

            while ($krow = mysql_fetch_assoc($kresult)) {
              $sel = $row['korvamerkinta'] == $krow['selite'] ? 'SELECTED' : '';
              echo "<option value='$krow[selite]' $sel>$krow[selitetark]</option>";
            }
            echo "</select>";
            echo "</div>";
          }

          $font_color = "";
          if ($row['kommentti'] != '') {

            if ($row['vanha_otunnus'] != $tilausnumero) {
              $font_color = "color='green'";
            }

            echo t("Kommentti").": <font {$font_color} style='font-weight: bold;'>".str_replace("\n", "<br>", $row["kommentti"])."</font><br>";
          }

          if ($yhtiorow['naytetaanko_ale_peruste_tilausrivilla'] != '' and $row['ale_peruste'] != '') {
            $ap_font = "<font>";
            $ap_text = "";

            // Onko asiakasalennusta?
            preg_match_all("/XXXALEPERUSTE:([0-9]*)/", $row['ale_peruste'], $ap_match);

            foreach ($ap_match[1] as $apnumero) {
              if ($apnumero >= 5 and $apnumero < 13) {
                $ap_font  = "<font class='ok'>";
                $ap_text .= t("Asiakasalennus");
                break;
              }
            }

            // Onko asiakashintaa
            preg_match("/XXXHINTAPERUSTE:([0-9]*)/", $row['ale_peruste'], $ap_match);

            // Jos tuote näytetään vain jos asiakkaalla on asiakasalennus tai asiakahinta niin skipataan se jos alea tai hintaa ei löydy
            if ($ap_match[1] > 1 and $ap_match[1] <= 13) {
              $ap_font = "<font class='ok'>";

              if ($ap_text != "") $ap_text .= " / ";
              $ap_text .= t("Asiakashinta");
            }

            if ($yhtiorow['naytetaanko_ale_peruste_tilausrivilla'] == 'o') {
              echo $ap_font.substr($row["ale_peruste"], 0, strpos($row["ale_peruste"], "XXX"))."</font><br>";
            }
            elseif ($ap_text != "" and $yhtiorow['naytetaanko_ale_peruste_tilausrivilla'] == 't') {
              echo $ap_font.$ap_text."</font><br>";
            }
          }

          // tähän se taulu
          echo $vastaavat_html;

          if ($sahkoinen_tilausliitanta and isset($vastaavat_html) and trim($vastaavat_html) != '' and isset($vastaavat_table2) and trim($vastaavat_table2) != '' and isset($paarivin_saldokysely) and $paarivin_saldokysely and in_array($row['var'], array('U', 'T'))) {
            $vastaavat_html = $vastaavat_table = $vastaavat_table2 = "";
          }

          echo "</td>";
          echo "<td class='back ptop' nowrap></td>";
          echo "</tr>";

        }
      }

      if ($toim == "VALMISTAVARASTOON" and $yhtiorow["kehahinta_valmistuksella"] == "K") {
        $_colspan = $sarakkeet_alku - 6;

        echo "<tr>{$jarjlisa}";
        echo "<td class='back' colspan='{$_colspan}'>&nbsp;</td>";
        echo "<th colspan='5' align='right'>";
        echo t("Valmisteen %s kehahinta * kpl yhteensä", '', $tuotteenpainotettukehayht["tuoteno"]);
        echo "</th>";
        echo "<td class='spec' align='right'>";
        echo sprintf("%.2f", $tuotteenpainotettukehayht["keha"]);
        echo "</td>";

        $tuotteenpainotettukehayht["keha"] = 0;
      }

      $summa           = 0;   // Tilauksen verollinen loppusumma tilauksen valuutassa
      $summa_eieri      = 0;  // Tilauksen verollinen loppusumma tilauksen valuutassa ilman erikoisalennusta
      $arvo            = 0;  // Tilauksen veroton loppusumma tilauksen valuutassa
      $arvo_eieri        = 0;  // Tilauksen veroton loppusumma tilauksen valuutassa ilman erikoisalennusta
      $kotiarvo        = 0;  // Tilauksen veroton loppusumma yhtiön valuutassa
      $kotiarvo_eieri      = 0;  // Tilauksen veroton loppusumma yhtiön valuutassa ilman erikoisalennusta
      $kate          = 0;  // Tilauksen kate yhtiön valuutassa
      $kate_eieri        = 0;  // Tilauksen kate yhtiön valuutassa ilman erikoisalennusta
      $ostot          = 0;  // Tilauksen Ostot tilauksen valuutassa
      $ostot_eieri      = 0;  // Tilauksen Ostot tilauksen valuutassa ilman erikoisalennusta

      $summa_kotimaa       = 0;  // Kotimaan toimitusten verollinen loppusumma tilauksen valuutassa
      $summa_kotimaa_eieri   = 0;  // Kotimaan toimitusten verollinen loppusumma tilauksen valuutassa ilman erikoisalennusta
      $arvo_kotimaa      = 0;  // Kotimaan toimitusten veroton loppusumma tilauksen valuutassa
      $arvo_kotimaa_eieri    = 0;  // Kotimaan toimitusten veroton loppusumma tilauksen valuutassa ilman erikoisalennusta
      $kotiarvo_kotimaa    = 0;  // Kotimaan toimitusten veroton loppusumma yhtiön valuutassa
      $kotiarvo_kotimaa_eieri  = 0;  // Kotimaan toimitusten veroton loppusumma yhtiön valuutassa ilman erikoisalennusta
      $kate_kotimaa      = 0;  // Kotimaan toimitusten kate yhtiön valuutassa
      $kate_kotimaa_eieri    = 0;  // Kotimaan toimitusten kate yhtiön valuutassa ilman erikoisalennusta

      $summa_ulkomaa      = 0;  // Ulkomaan toimitusten verollinen loppusumma tilauksen valuutassa
      $summa_ulkomaa_eieri  = 0;  // Ulkomaan toimitusten verollinen loppusumma tilauksen valuutassa ilman erikoisalennusta
      $arvo_ulkomaa      = 0;  // Ulkomaan toimitusten veroton loppusumma tilauksen valuutassa
      $arvo_ulkomaa_eieri    = 0;  // Ulkomaan toimitusten veroton loppusumma tilauksen valuutassa ilman erikoisalennusta
      $kotiarvo_ulkomaa    = 0;  // Ulkomaan toimitusten veroton loppusumma yhtiön valuutassa
      $kotiarvo_ulkomaa_eieri  = 0;  // Ulkomaan toimitusten veroton loppusumma yhtiön valuutassa ilman erikoisalennusta
      $kate_ulkomaa      = 0;  // Ulkomaan toimitusten kate yhtiön valuutassa
      $kate_ulkomaa_eieri    = 0;  // Ulkomaan toimitusten kate yhtiön valuutassa ilman erikoisalennusta

      $tilauksen_tuotemassa = 0;
      $tilauksen_tuotetilavuus = 0;

      if ($kukarow['hinnat'] != -1 and $toim != "SIIRTOTYOMAARAYS" and $toim != "VALMISTAVARASTOON") {
        // Laskeskellaan tilauksen loppusummaa (mitätöidyt ja raaka-aineet eivät kuulu jengiin)
        $alvquery = "SELECT IF(ISNULL(varastopaikat.maa) or varastopaikat.maa='', '$yhtiorow[maa]', varastopaikat.maa) maa, group_concat(tilausrivi.tunnus) rivit
                     FROM tilausrivi
                     LEFT JOIN varastopaikat ON (varastopaikat.yhtio =
                       IF(tilausrivi.var = 'S',
                         IF((SELECT tyyppi_tieto
                             FROM toimi
                             WHERE yhtio         = tilausrivi.yhtio
                             AND tunnus          = tilausrivi.tilaajanrivinro) != '',
                              (SELECT tyyppi_tieto
                               FROM toimi
                               WHERE yhtio       = tilausrivi.yhtio
                               AND tunnus        = tilausrivi.tilaajanrivinro),
                              tilausrivi.yhtio),
                         tilausrivi.yhtio)
                       AND varastopaikat.tunnus  = tilausrivi.varasto)
                     WHERE tilausrivi.yhtio      = '$kukarow[yhtio]'
                     and tilausrivi.tyyppi       in ($tilrivity)
                     and tilausrivi.tyyppi       not in ('D','V','M')
                     and tilausrivi.var         != 'O'
                     $tunnuslisa
                     GROUP BY 1
                     ORDER BY 1";
        $alvresult = pupe_query($alvquery);

        // typekästätään koska joskus tulee spacena.. en tajua.
        $laskurow["erikoisale"] = (float) $laskurow["erikoisale"];

        if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
          $hinta_riv = "(tilausrivi.hinta/$laskurow[vienti_kurssi])";
          $hinta_myy = "(tuote.myyntihinta/$laskurow[vienti_kurssi])";
        }
        else {
          $hinta_riv = "tilausrivi.hinta";
          $hinta_myy = "tuote.myyntihinta";
        }

        if ($kukarow['hinnat'] == 1) {
          $lisat = "  $hinta_myy / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * (tilausrivi.alv/100) alv,
                $hinta_myy / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) rivihinta,
                $hinta_myy / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * (tilausrivi.alv/100) alv_ei_erikoisaletta,
                $hinta_myy / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) rivihinta_ei_erikoisaletta";
        }
        else {

          $query_ale_lisa = generoi_alekentta('M');
          $query_ale_lisa_ei_erik = generoi_alekentta('M', '', 'ei_erikoisale');

          if ((($asiakasrow['extranet_tilaus_varaa_saldoa'] == "" and
                $yhtiorow["extranet_tilaus_varaa_saldoa"] == "E") or
              $asiakasrow["extranet_tilaus_varaa_saldoa"] == "E") and
            $laskurow["tilaustyyppi"] == "H"
          ) {
            $kplkentta = "tilkpl";
          }
          else {
            $kplkentta = "varattu";
          }

          $lisat =
            "  if (tilausrivi.alv<500, {$hinta_riv} / if ('{$yhtiorow['alv_kasittely']}' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.{$kplkentta}+tilausrivi.jt) * {$query_ale_lisa} * (tilausrivi.alv/100), 0) alv,
                {$hinta_riv} / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.{$kplkentta}+tilausrivi.jt) * {$query_ale_lisa} rivihinta,
                if (tilausrivi.alv<500, {$hinta_riv} / if ('{$yhtiorow['alv_kasittely']}' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.{$kplkentta}+tilausrivi.jt) * {$query_ale_lisa_ei_erik} * (tilausrivi.alv/100), 0) alv_ei_erikoisaletta,
                {$hinta_riv} / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.{$kplkentta}+tilausrivi.jt) * {$query_ale_lisa_ei_erik} rivihinta_ei_erikoisaletta,
                tilausrivi.hinta / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.{$kplkentta}+tilausrivi.jt) * {$query_ale_lisa} kotirivihinta,
                tilausrivi.hinta / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.{$kplkentta}+tilausrivi.jt) * {$query_ale_lisa_ei_erik} kotirivihinta_ei_erikoisaletta";
        }

        while ($alvrow = mysql_fetch_assoc($alvresult)) {

          $aquery = "SELECT
                     tuote.sarjanumeroseuranta,
                     tuote.ei_saldoa,
                     tuote.tuoteno,
                     $kehahin_select kehahin,
                     tilausrivi.tunnus,
                     tilausrivi.varattu+tilausrivi.jt varattu,
                     tilausrivin_lisatiedot.osto_vai_hyvitys,
                     tuote.tuotemassa, (tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys) AS tuotetilavuus,
                     {$lisat}
                     FROM tilausrivi
                     JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
                     LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
                     WHERE tilausrivi.yhtio =  '{$kukarow['yhtio']}'
                     AND tilausrivi.otunnus =  '{$kukarow['kesken']}'
                     AND tilausrivi.tunnus  IN  ({$alvrow['rivit']})";
          $aresult = pupe_query($aquery);

          while ($arow = mysql_fetch_assoc($aresult)) {
            $rivikate     = 0;  // Rivin kate yhtiön valuutassa
            $rivikate_eieri  = 0;  // Rivin kate yhtiön valuutassa ilman erikoisalennusta

            if ($arow["sarjanumeroseuranta"] == "S") {
              //Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on myyntiä
              if ($arow["varattu"] > 0) {
                //Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on myyntiä
                $ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $arow["tunnus"]);
                $ostohinta = hinta_kuluineen($arow['tuoteno'], $ostohinta);

                // Kate = Hinta - Ostohinta
                $rivikate = $arow["kotirivihinta"] - ($ostohinta * $arow["varattu"]);
                $rivikate_eieri = $arow["kotirivihinta_ei_erikoisaletta"] - ($ostohinta * $arow["varattu"]);
              }
              elseif ($arow["varattu"] < 0 and $arow["osto_vai_hyvitys"] == "O") {
                //Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on OSTOA

                // Kate = 0
                $rivikate  = 0;
                $rivikate_eieri = 0;

                $ostot += $arow["kotirivihinta"];
                $ostot_eieri += $arow["kotirivihinta_ei_erikoisaletta"];
              }
              elseif ($arow["varattu"] < 0 and $arow["osto_vai_hyvitys"] == "") {
                //Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on HYVITYSTÄ

                //Tähän hyvitysriviin liitetyt sarjanumerot
                $query = "SELECT sarjanumero, kaytetty
                          FROM sarjanumeroseuranta
                          WHERE yhtio        = '$kukarow[yhtio]'
                          AND ostorivitunnus = '$arow[tunnus]'";
                $sarjares = pupe_query($query);

                $ostohinta = 0;

                while ($sarjarow = mysql_fetch_assoc($sarjares)) {

                  // Haetaan hyvitettävien myyntirivien kautta alkuperäiset ostorivit
                  $query  = "SELECT tilausrivi.rivihinta/tilausrivi.kpl ostohinta
                             FROM sarjanumeroseuranta
                             JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
                             WHERE sarjanumeroseuranta.yhtio          = '$kukarow[yhtio]'
                             and sarjanumeroseuranta.tuoteno          = '$arow[tuoteno]'
                             and sarjanumeroseuranta.sarjanumero      = '$sarjarow[sarjanumero]'
                             and sarjanumeroseuranta.kaytetty         = '$sarjarow[kaytetty]'
                             and sarjanumeroseuranta.myyntirivitunnus > 0
                             and sarjanumeroseuranta.ostorivitunnus   > 0
                             ORDER BY sarjanumeroseuranta.tunnus
                             LIMIT 1";
                  $sarjares1 = pupe_query($query);
                  $sarjarow1 = mysql_fetch_assoc($sarjares1);

                  $oh = hinta_kuluineen($arow['tuoteno'], $sarjarow1['ostohinta']);
                  $ostohinta += $oh;
                }

                $rivikate = $arow["kotirivihinta"] - $ostohinta;
                $rivikate_eieri = $arow["kotirivihinta_ei_erikoisaletta"] - $ostohinta;
              }
              else {
                $rivikate     = 0;
                $rivikate_eieri = 0;
              }
            }
            else {
              $khh = hinta_kuluineen($arow['tuoteno'], $arow["kehahin"]);
              $rivikate = $arow["kotirivihinta"] - ($khh*$arow["varattu"]);
              $rivikate_eieri = $arow["kotirivihinta_ei_erikoisaletta"] - ($khh*$arow["varattu"]);
            }

            if ($arow['varattu'] > 0) {

              $tilauksen_tuotemassa += $arow['varattu'] * $arow['tuotemassa'];
              $tilauksen_tuotetilavuus += $arow['varattu'] * $arow['tuotetilavuus'];

              if (trim(strtoupper($alvrow["maa"])) == trim(strtoupper($laskurow["toim_maa"]))) {
                $summa_kotimaa      += $arow["rivihinta"]+$arow["alv"];
                $summa_kotimaa_eieri  += $arow["rivihinta_ei_erikoisaletta"]+$arow["alv_ei_erikoisaletta"];
                $arvo_kotimaa      += $arow["rivihinta"];
                $arvo_kotimaa_eieri    += $arow["rivihinta_ei_erikoisaletta"];
                $kotiarvo_kotimaa    += $arow["kotirivihinta"];
                $kotiarvo_kotimaa_eieri  += $arow["kotirivihinta_ei_erikoisaletta"];
                $kate_kotimaa      += $rivikate;
                $kate_kotimaa_eieri    += $rivikate_eieri;
              }
              else {
                $summa_ulkomaa      += $arow["rivihinta"]+$arow["alv"];
                $summa_ulkomaa_eieri  += $arow["rivihinta_ei_erikoisaletta"]+$arow["alv_ei_erikoisaletta"];
                $arvo_ulkomaa      += $arow["rivihinta"];
                $arvo_ulkomaa_eieri    += $arow["rivihinta_ei_erikoisaletta"];
                $kotiarvo_ulkomaa    += $arow["kotirivihinta"];
                $kotiarvo_ulkomaa_eieri  += $arow["kotirivihinta_ei_erikoisaletta"];
                $kate_ulkomaa      += $rivikate;
                $kate_ulkomaa_eieri    += $rivikate_eieri;
              }
            }

            $summa      += hintapyoristys($arow["rivihinta"]+$arow["alv"]);
            $summa_eieri  += hintapyoristys($arow["rivihinta_ei_erikoisaletta"]+$arow["alv_ei_erikoisaletta"]);
            $arvo      += hintapyoristys($arow["rivihinta"]);
            $arvo_eieri    += hintapyoristys($arow["rivihinta_ei_erikoisaletta"]);
            $kotiarvo    += hintapyoristys($arow["kotirivihinta"]);
            $kotiarvo_eieri  += hintapyoristys($arow["kotirivihinta_ei_erikoisaletta"]);
            $kate      += $rivikate;
            $kate_eieri    += $rivikate_eieri;
          }
        }

        // jos loppusumma on isompi kuin tietokannassa oleva tietuen koko (10 numeroa + 2 desimaalia), niin herjataan
        if ($arvo_eieri != '' and abs($arvo_eieri) > 0) {
          if (abs($arvo_eieri) > 9999999999.99) {
            echo "<font class='error'>", t("VIRHE: liian iso loppusumma"), "!</font><br>";
            $tilausok++;
          }
        }

        //Jos myyjä on myymässä ulkomaan varastoista liian pienellä summalla
        if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
          $ulkom_huom = "<font class='error'>".t("HUOM: Summa on liian pieni ulkomaantoimitukselle. Raja on").": $yhtiorow[suoratoim_ulkomaan_alarajasumma] $laskurow[valkoodi]</font>";
        }
        elseif ($kukarow["extranet"] != "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
          if ($tm_toimitustaparow['ulkomaanlisa'] > 0) {
            $ulkom_huom = "<font class='message'>".t("Olet tilaamassa ulkomaanvarastosta, rahtikulut nousevat")." ".round(laskuval($tm_toimitustaparow["ulkomaanlisa"], $laskurow["vienti_kurssi"]), 0)." $laskurow[valkoodi] ".t("verran")." </font><br>";
          }
          else {
            $ulkom_huom = "";
          }
        }
        else {
          $ulkom_huom = "";
        }

        if ($toim != 'SIIRTOLISTA') {

          if ($kukarow['extranet'] == '' and in_array($toim, array('RIVISYOTTO', 'PIKATILAUS', 'TARJOUS')) and in_array($yhtiorow['tilaukselle_mittatiedot'], array('M', 'A'))) {

            if ($yhtiorow['tilaukselle_mittatiedot'] == 'A') {
              echo "<tr>$jarjlisa
                  <td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
                  <th colspan='5' align='right'>".t("Asiakasosasto").":</th>
                  <td class='spec' colspan='3' align='center'>{$asiakasrow['osasto']}</td>";
            }

            echo "<tr>$jarjlisa
                <td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
                <th colspan='5' align='right'>".t("Tilauksen kokonaispaino").":</th>
                <td class='spec' align='right'>".sprintf("%.2f", $tilauksen_tuotemassa)."</td>";
            echo "<td></td>";
            echo "<td class='spec'>KG</td>";
            echo "</tr>";
            echo "<tr>$jarjlisa
                <td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
                <th colspan='5' align='right'>".t("Tilauksen kokonaistilavuus").":</th>
                <td class='spec' align='right'>".sprintf("%.2f", $tilauksen_tuotetilavuus)."</td>";
            echo "<td></td>";
            echo "<td class='spec'>M3</td>";
            echo "</tr>";
          }

          if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0) {
            echo "<tr>$jarjlisa
                <td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
                <th colspan='5' align='right'>".t("Kotimaan myynti").":</th>
                <td class='spec' align='right'>".sprintf("%.2f", $arvo_kotimaa_eieri)."</td>";

            if ($kukarow['extranet'] == '' and $kotiarvo_kotimaa_eieri != 0 and $naytetaanko_kate) {
              echo "<td class='spec' align='right' nowrap>" . round(100 * $kate_eieri / ($kotiarvo_eieri-$ostot_eieri), 2) . "%</td>";
            }
            elseif ($kukarow['extranet'] == '' and $naytetaanko_kate) {
              echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
            }

            echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

            echo "<tr>$jarjlisa
              <td class='back' colspan='".($sarakkeet_alku-5)."' align='right'>$ulkom_huom</td>
              <th colspan='5' align='right'>".t("Ulkomaan myynti").":</th>
              <td class='spec' align='right'>".sprintf("%.2f", $arvo_ulkomaa_eieri)."</td>";

            if ($kukarow['extranet'] == '' and $kotiarvo_ulkomaa_eieri != 0 and $naytetaanko_kate) {
              echo "<td class='spec' align='right' nowrap>".sprintf("%.2f", 100*$kate_ulkomaa_eieri/($kotiarvo_ulkomaa_eieri-$ostot_eieri))."%</td>";
            }
            elseif ($kukarow['extranet'] == '' and $naytetaanko_kate) {
              echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
            }

            echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
          }
          else {
            echo "<tr>$jarjlisa
                <td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
                <th colspan='5' align='right'>".t("Veroton yhteensä").":</th>
                <td class='spec' align='right'>".sprintf("%.2f", $arvo_eieri)."</td>";

            if ($kukarow['extranet'] == '' and $kotiarvo_eieri != 0 and $kotiarvo_eieri-$ostot_eieri != 0 and $naytetaanko_kate) {
              echo "<td class='spec' align='right' nowrap>".sprintf("%.2f", 100*$kate_eieri/($kotiarvo_eieri-$ostot_eieri))."%</td>";
            }
            elseif ($kukarow['extranet'] == '' and $naytetaanko_kate) {
              echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
            }

            echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
          }
        }

        if ($laskurow["erikoisale"] > 0 and $kukarow['hinnat'] == 0) {
          echo "<tr>$jarjlisa
            <td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
            <th colspan='5' align='right'>".t("Erikoisalennus")." $laskurow[erikoisale]%:</th>
            <td class='spec' align='right'>".sprintf("%.2f", ($arvo_eieri-$arvo)*-1)."</td>";

          if ($kukarow['extranet'] == '' and $naytetaanko_kate) {
            echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
          }

          echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

          if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0) {
            echo "<tr>$jarjlisa
                <td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
                <th colspan='5' align='right'>".t("Kotimaan myynti").":</th>
                <td class='spec' align='right' nowrap>".sprintf("%.2f", $arvo_kotimaa)."</td>";

            if ($kukarow['extranet'] == '' and $kotiarvo_kotimaa != 0 and $naytetaanko_kate) {
              echo "<td class='spec' align='right'>".sprintf("%.2f", 100*$kate_kotimaa/($kotiarvo_kotimaa-$ostot))."%</td>";
            }
            elseif ($kukarow['extranet'] == '' and $naytetaanko_kate) {
              echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
            }

            echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

            echo "<tr>$jarjlisa
              <td class='back' colspan='".($sarakkeet_alku-5)."' align='right'>$ulkom_huom</td>
              <th colspan='5' align='right'>".t("Ulkomaan myynti").":</th>
              <td class='spec' align='right'>".sprintf("%.2f", $arvo_ulkomaa)."</td>";

            if ($kukarow['extranet'] == '' and $kotiarvo_ulkomaa != 0 and $naytetaanko_kate) {
              echo "<td class='spec' align='right' nowrap>".sprintf("%.2f", 100*$kate_ulkomaa/($kotiarvo_ulkomaa-$ostot))."%</td>";
            }

            echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
          }
          else {
            echo "<tr>$jarjlisa
                <td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
                <th colspan='5' align='right'>".t("Veroton yhteensä").":</th>
                <td class='spec' align='right'>".sprintf("%.2f", $arvo)."</td>";

            if ($kukarow['extranet'] == '' and $kotiarvo != 0 and $naytetaanko_kate) {
              echo "<td class='spec' align='right' nowrap>".sprintf("%.2f", 100*$kate/($kotiarvo-$ostot))."%</td>";
            }
            elseif ($kukarow['extranet'] == '' and $naytetaanko_kate) {
              echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
            }

            echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
          }
        }

        // EE keississä lasketaan veron määrää saman kaavan mukaan ku laskun tulostuksessa alvierittelyssä
        // ja sit lopuksi summataan $arvo+$alvinmaara jotta saadaan laskun verollinen loppusumma
        if (strtoupper($yhtiorow['maa']) == 'EE') {

          $alvinmaara = 0;

          if ($kukarow['hinnat'] == 1) {
            $alisat = " round($hinta_myy / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt),2)";
          }
          else {
            $alisat = " round({$hinta_riv} / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa},2)";
          }

          //Haetaan kaikki alvikannat riveiltä
          $alvquery = "SELECT DISTINCT alv
                       FROM tilausrivi
                       WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                       and tilausrivi.tyyppi  in ($tilrivity)
                       and tilausrivi.tyyppi  not in ('D','V','M')
                       $tunnuslisa
                       and tilausrivi.alv     < 500";
          $alvresult = pupe_query($alvquery);

          while ($alvrow = mysql_fetch_assoc($alvresult)) {

            $aquery = "SELECT
                       round(sum({$alisat} * (tilausrivi.alv / 100)),2) alvrivihinta
                       FROM tilausrivi
                       JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                       WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                       and tilausrivi.tyyppi  in ($tilrivity)
                       and tilausrivi.tyyppi  not in ('D','V','M')
                       $tunnuslisa
                       and tilausrivi.alv     = '$alvrow[alv]'";
            $aresult = pupe_query($aquery);
            $arow = mysql_fetch_assoc($aresult);

            $alvinmaara += $arow["alvrivihinta"];
          }

          $summa = $arvo+$alvinmaara;
        }

        //Käsin syötetty summa johon lasku pyöristetään
        if ($laskurow["hinta"] <> 0 and abs($laskurow["hinta"]-$summa) <= 0.5 and abs($summa) >= 0.5) {
          $summa = sprintf("%.2f", $laskurow["hinta"]);
        }

        // Jos laskun loppusumma pyöristetään lähimpään tasalukuun
        if ($yhtiorow["laskunsummapyoristys"] == 'o' or $asiakasrow["laskunsummapyoristys"] == 'o') {
          $summa = sprintf("%.2f", round($summa , 0));
        }

        if ($toim != 'SIIRTOLISTA') {
          echo "<tr>$jarjlisa
              <td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
              <th colspan='5' align='right'>".t("Verollinen yhteensä").":</th>";

          echo "<td class='spec' align='right'>".sprintf("%.2f", $summa)."</td>";
        }

        if ($kukarow['extranet'] == '' and $naytetaanko_kate) {
          echo "<td class='spec' align='right'>&nbsp;</td>";
        }

        echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

        $rahtivapaa_alarajasumma = 0;

        if (!empty($asiakasrow['rahtivapaa_alarajasumma'])) {
          $rahtivapaa_alarajasumma = (float) $asiakasrow["rahtivapaa_alarajasumma"];
        }
        else {
          $rahtivapaa_alarajasumma = (float) $yhtiorow["rahtivapaa_alarajasumma"];
        }

        if (isset($summa) and (float) $summa != 0) {
          $kaikkiyhteensa = yhtioval($summa, $laskurow["vienti_kurssi"]); // käännetään yhteensäsumma yhtiövaluuttaan
        }
        else {
          $kaikkiyhteensa = 0;
        }

        if ((($kaikkiyhteensa > $rahtivapaa_alarajasumma or $etayhtio_totaalisumma > $rahtivapaa_alarajasumma) and $rahtivapaa_alarajasumma != 0) or $laskurow["rahtivapaa"] != "") {
          echo "<tr>$jarjlisa<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td><th colspan='5' align='right'>".t("Rahtikulu").":</th><td class='spec' align='right'>0.00</td>";
          if ($kukarow['extranet'] == '' and $naytetaanko_kate) {
            echo "<td class='spec' align='right'>&nbsp;</td>";
          }
          echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
        }
        elseif ($yhtiorow["rahti_hinnoittelu"] == "P" or $yhtiorow["rahti_hinnoittelu"] == "o") {

          // haetaan rahtimaksu
          // hae_rahtimaksu-funktio palauttaa arrayn, jossa on rahtimatriisin hinta ja alennus
          // mahdollinen alennus (i.e. asiakasalennus) tulee dummy-tuotteelta, joka voi olla syötettynä toimitustavan taakse
          list($rah_hinta, $rah_ale, $rah_alv, $rah_netto) = hae_rahtimaksu($laskurow["tunnus"]);

          if ($rah_hinta > 0) {

            // muutetaan rahtihinta laskun valuuttaan, koska rahtihinta tulee matriisista aina yhtiön kotivaluutassa
            if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
              $rah_hinta = laskuval($rah_hinta, $laskurow["vienti_kurssi"]);
            }

            foreach ($rah_ale as $key => $val) {
              $rah_hinta *= (1 - ($val / 100));
            }

            // jos yhtiön tuotteiden myyntihinnat ovat arvonlisäverottomia ja lasku on verollinen, lisätään rahtihintaan arvonlisävero
            if ($yhtiorow['alv_kasittely'] != '' and $laskurow['alv'] != 0) {
              $rah_hinta = $rah_hinta * (1 + ($rah_alv / 100));
            }
          }

          echo "<tr>$jarjlisa<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td><th colspan='5' align='right'>".t("Rahtikulu")." ", t("verollinen");

          if (is_array($rah_ale) and count($rah_ale) > 0) {
            foreach ($rah_ale as $key => $val) {
              if ($val > 0) echo " ($key $val %)";
            }
          }

          echo ":</th><td class='spec' align='right'>".sprintf("%.2f", $rah_hinta)."</td>";
          if ($kukarow['extranet'] == '' and $naytetaanko_kate) {
            echo "<td class='spec' align='right'>&nbsp;</td>";
          }
          echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";

          echo "<tr>$jarjlisa<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td><th colspan='5' align='right'>".t("Loppusumma").":</th><td class='spec' align='right'>".sprintf("%.2f", $summa+$rah_hinta)."</td>";
          if ($kukarow['extranet'] == '' and $naytetaanko_kate) {
            echo "<td class='spec' align='right'>&nbsp;</td>";
          }
          echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
        }

        $_lahdot_toim_check_myynnit = (in_array($toim, array("RIVISYOTTO", "PIKATILAUS", "TYOMAARAYS")));
        $_lahdot_toim_check_siirrot = (in_array($toim, array("SIIRTOLISTA")) and $yhtiorow['siirtolistan_tulostustapa'] == 'U');
        $_lahdot_toim_check = ($_lahdot_toim_check_myynnit or $_lahdot_toim_check_siirrot);

        if ($yhtiorow['kerayserat'] == 'K' and $toimitustavan_tunnus > 0 and $kukarow['extranet'] == "" and $_lahdot_toim_check) {

          echo "<tr>{$jarjlisa}";
          echo "<td colspan='3' class='back'>";

          echo "<form action='' method='post' autocomplete='off'>
              <input type='hidden' name='tilausnumero' value='{$tilausnumero}'>
              <input type='hidden' name='mista' value='{$mista}'>
              <input type='hidden' name='toim' value='{$toim}'>
              <input type='hidden' name='lopetus' value='{$lopetus}'>
              <input type='hidden' name='ruutulimit' value = '{$ruutulimit}'>
              <input type='hidden' name='projektilla' value='{$projektilla}'>
              <input type='hidden' name='orig_tila' value='$orig_tila'>
              <input type='hidden' name='orig_alatila' value='$orig_alatila'>";

          echo "<table>";
          echo "<tr><th colspan='2'>", t("Lähdöt"), ":</th></tr>";
          echo "<tr>";

          if (!isset($toimitustavan_lahto)) $toimitustavan_lahto = array();

          if ($laskurow['toimitustavan_lahto'] > 0 and $laskurow['tila'] == 'L' and $laskurow['alatila'] == 'D') {

            $query = "SELECT *
                      FROM lahdot
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$laskurow['toimitustavan_lahto']}'";
            $lahdot_res = pupe_query($query);
            $lahdot_row = mysql_fetch_assoc($lahdot_res);

            $query = "SELECT nimitys
                      FROM varastopaikat
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$lahdot_row['varasto']}'";
            $varasto_chk_res = pupe_query($query);
            $varasto_chk_row = mysql_fetch_assoc($varasto_chk_res);

            echo "<td nowrap>{$varasto_chk_row['nimitys']}</td><td>";

            echo "<select name='toimitustavan_lahto[{$lahdot_row['varasto']}]' onchange='submit()' {$state}>";
            echo "<option value=''>", t("Valitse"), "</option>";

            $lahto = $lahdot_row['pvm'].' '.$lahdot_row['lahdon_kellonaika'];

            $ohjausmerkki_teksti = !empty($lahdot_row['ohjausmerkki']) ? " ({$lahdot_row['ohjausmerkki']})" : "";
            echo "<option value='{$lahdot_row['tunnus']}' selected>", tv1dateconv($lahto, "PITKA"), "{$ohjausmerkki_teksti}</option>";

            $toimitustavan_lahto[$lahdot_row['varasto']] = $lahdot_row['tunnus'];

            echo "</select>";
            echo "</td>";
          }
          else {
            // Haetaan kaikkien tilausrivien varastopaikat
            $chk_arr = tilausrivien_varastot($laskurow['tunnus']);

            $i_counter = 0;

            foreach ($chk_arr as $vrst) {

              $query = "SELECT nimitys
                        FROM varastopaikat
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  = '{$vrst}'";
              $varasto_chk_res = pupe_query($query);
              $varasto_chk_row = mysql_fetch_assoc($varasto_chk_res);

              if ($i_counter > 0) {
                echo "</tr><tr>";
              }

              // Tilaustyyppi 2, eli varastotäydennys, siirretään aina yhden päivän eteenpäin
              $eteenpain = ($laskurow["tilaustyyppi"] == 2) ? 1 : 0;

              // Haetaan seuraavat lähdöt
              $lahdot = seuraavat_lahtoajat($laskurow["toimitustapa"], $laskurow["prioriteettinro"], $vrst, 0, $eteenpain);

              echo "<td nowrap>{$varasto_chk_row['nimitys']}</td><td>";
              echo "<select name='toimitustavan_lahto[{$vrst}]' onchange='submit();' {$state}>";
              echo "<option value=''>", t("Valitse"), "</option>";

              $selectoitunut = FALSE;

              $toimitustavan_lahto_chk = $toimitustavan_lahto;

              foreach ($lahdot as $lahdot_row) {

                $lahto = $lahdot_row['pvm'].' '.$lahdot_row['lahdon_kellonaika'];

                $sel = (count($toimitustavan_lahto_chk) > 0 and in_array($lahdot_row['tunnus'], $toimitustavan_lahto_chk)) ? " selected" : ($laskurow['toimitustavan_lahto'] == $lahdot_row['tunnus'] ? " selected" : "");

                if ($sel != "") $selectoitunut = TRUE;

                if (!$selectoitunut and $sel == "" and $laskurow['toimitustavan_lahto'] == 0 and strtolower($state) != 'disabled' and (count($toimitustavan_lahto_chk) == 0 or !in_array($lahto, $toimitustavan_lahto_chk))) {
                  $sel = " selected";
                  $selectoitunut = TRUE;
                }

                if ($sel != "") {
                  $toimitustavan_lahto[$vrst] = $lahdot_row['tunnus'];
                }

                $ohjausmerkki_teksti = !empty($lahdot_row['ohjausmerkki']) ? " ({$lahdot_row['ohjausmerkki']})" : "";
                echo "<option value='{$lahdot_row['tunnus']}'{$sel}>", tv1dateconv($lahto, "PITKA"), "{$ohjausmerkki_teksti}</option>";
              }

              echo "</select>";
              echo "</td>";

              $i_counter++;
            }
          }

          echo "</tr></table>";
          echo "</form>";
          echo "</td>";
          echo "</tr>";
        }

        $sallijyvitys = FALSE;

        if ($kukarow["extranet"] == "") {
          if ($yhtiorow["salli_jyvitys_myynnissa"] == "" and ($kukarow['kassamyyja'] != '' or $kukarow['dynaaminen_kassamyynti'] != '') and $toim != 'SIIRTOLISTA') {
            $sallijyvitys = TRUE;
          }

          if ($yhtiorow["salli_jyvitys_myynnissa"] == "V" and $kukarow['jyvitys'] != '' and $toim != 'SIIRTOLISTA') {
            $sallijyvitys = TRUE;
          }

          if (($yhtiorow["salli_jyvitys_myynnissa"] == "K" or $yhtiorow["salli_jyvitys_myynnissa"] == "S") and $toim != 'SIIRTOLISTA') {
            $sallijyvitys = TRUE;
          }

          if ($toim == "TARJOUS" or $toim == "EXTTARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "PROJEKTI") {
            $sallijyvitys = TRUE;
          }

          if ($toim == 'TARJOUS' and $yhtiorow['salli_jyvitys_tarjouksella'] != '') {
            $sallijyvitys = TRUE;
          }

        }

        //annetaan mahdollisuus antaa loppusumma joka jyvitetään riveille arvoosuuden mukaan
        if ($sallijyvitys or $naytetaan_tilausvahvistusnappi) {

          echo "<tr>$jarjlisa";

          if ($jyvsumma == '') {
            $jyvsumma = '0.00';
          }

          if ($naytetaan_tilausvahvistusnappi or $toim == "TARJOUS" or $toim == "EXTTARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "PROJEKTI") {

            $tulcspani = "3";

            if ((($toim != "TARJOUS" and $toim != "EXTTARJOUS") or $yhtiorow['tarjouksen_tuotepaikat'] == "") and (($kukarow['extranet'] == '' or ($kukarow['extranet'] != '' and $yhtiorow['tuoteperhe_suoratoimitus'] == 'E')) or $yhtiorow['varastopaikan_lippu'] != '')) {
              $tulcspani = "4";
            }

            echo "<td colspan='$tulcspani' style='text-align: center;' nowrap>
                <form action='tulostakopio.php' method='post' name='tulostaform_tmyynti' id='tulostaform_tmyynti' class='multisubmit'>
                <input type='hidden' name='otunnus' value='$tilausnumero'>
                <input type='hidden' name='projektilla' value='$projektilla'>
                <input type='hidden' name='tee' value='TULOSTA'>
                <input type='hidden' name='lopetus' value='$tilmyy_lopetus//from=LASKUTATILAUS'>";

            echo "<select name='toim'>";

            if (file_exists("tulosta_tarjous.inc") and ($toim == "TARJOUS" or $toim == "EXTTARJOUS" or $laskurow["tilaustyyppi"] == "T" or $toim == "PROJEKTI")) {
              echo "<option value='TARJOUS'>".t("Tarjous")."</option>";

              if (tarkista_oikeus('tulostakopio.php', 'TARJOUS!!!VL')) {
                echo "<option value='TARJOUS!!!VL'>".("Tarjous VL")."</option>";
              }

              if (tarkista_oikeus('tulostakopio.php', 'TARJOUS!!!BR')) {
                echo "<option value='TARJOUS!!!BR'>".t("Tarjous BR")."</option>";
              }
            }

            if (file_exists("tulosta_tilausvahvistus_pdf.inc")) {
              echo "<option value='TILAUSVAHVISTUS'>".t("Tilausvahvistus")."</option>";
            }

            if (file_exists("tulosta_myyntisopimus.inc")) {
              echo "<option value='MYYNTISOPIMUS'>".t("Myyntisopimus")."</option>";

              if (tarkista_oikeus('tulostakopio.php', 'MYYNTISOPIMUS!!!VL')) {
                echo "<option value='MYYNTISOPIMUS!!!VL'>".t("Myyntisopimus VL")."</option>";
              }

              if (tarkista_oikeus('tulostakopio.php', 'MYYNTISOPIMUS!!!BR')) {
                echo "<option value='MYYNTISOPIMUS!!!BR'>".t("Myyntisopimus BR")."</option>";
              }
            }
            if (file_exists("tulosta_osamaksusoppari.inc")) {
              echo "<option value='OSAMAKSUSOPIMUS'>".t("Osamaksusopimus")."</option>";
            }
            if (file_exists("tulosta_luovutustodistus.inc")) {
              echo "<option value='LUOVUTUSTODISTUS'>".t("Luovutustodistus")."</option>";
            }
            if (file_exists("tulosta_vakuutushakemus.inc")) {
              echo "<option value='VAKUUTUSHAKEMUS'>".t("Vakuutushakemus")."</option>";
            }
            if (file_exists("../tyomaarays/tulosta_tyomaarays.inc")) {
              echo "<option value='TYOMAARAYS'>".t("Työmääräys")."</option>";
            }
            if (file_exists("tulosta_rekisteriilmoitus.inc")) {
              echo "<option value='REKISTERIILMOITUS'>".t("Rekisteröinti-ilmoitus")."</option>";
            }
            if ($toim == "PROJEKTI") {
              echo "<option value='TILAUSVAHVISTUS'>".t("Tilausvahvistus")."</option>";
            }

            echo "</select>
              <input type='submit' value='".t("Näytä")."' onClick=\"js_openFormInNewWindow('tulostaform_tmyynti', 'tulosta_myynti'); return false;\">
              <input type='submit' value='".t("Tulosta")."' onClick=\"js_openFormInNewWindow('tulostaform_tmyynti', 'samewindow'); return false;\">
              </form>";
            echo "</td>";

            if ($sarakkeet_alku-9 > 0) {
              echo "<td class='back' colspan='".($sarakkeet_alku-9)."'></td>";
            }
          }
          else {
            echo "<td class='back' colspan='".($sarakkeet_alku-5)."' nowrap>&nbsp;</td>";
          }

          if (strlen(sprintf("%.2f", $summa)) > 7) {
            $koko = strlen(sprintf("%.2f", $summa));
          }
          else {
            $koko = '7';
          }

          if ($toim != "PROJEKTI" and $sallijyvitys) {
            if ($toim == 'TARJOUS' and !empty($yhtiorow['salli_jyvitys_tarjouksella'])) {
              echo "  <th colspan='5'>".t("Pyöristä katetta").":</th>
                  <td class='spec'>
                  <form name='pyorista' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' autocomplete='off'>
                      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
                      <input type='hidden' name='mista'     value = '$mista'>
                      <input type='hidden' name='tee'     value = 'kate_jyvita'>
                      <input type='hidden' name='toim'     value = '$toim'>
                      <input type='hidden' name='lopetus'   value = '$lopetus'>
                      <input type='hidden' name='ruutulimit'   value = '$ruutulimit'>
                      <input type='hidden' name='tilausrivi_alvillisuus' value='$tilausrivi_alvillisuus'>
                      <input type='hidden' name='projektilla' value='$projektilla'>
                      <input type='hidden' name='orig_tila'   value='$orig_tila'>
                      <input type='hidden' name='orig_alatila' value='$orig_alatila'>";
            }
            else {
              $pyoristys_otsikko = "Pyöristä loppusummaa";
              $align = "";
              $loytyy_maksutapahtumia = false;

              if ($maksupaate_kassamyynti and $maksuehtorow["kateinen"] != "") {
                list($loytyy_maksutapahtumia, $maksettavaa_jaljella, $kateismaksu["luottokortti"],
                  $kateismaksu["pankkikortti"]) =
                  jaljella_oleva_maksupaatesumma($laskurow["tunnus"], $kaikkiyhteensa);

                $maksettavaa_jaljella = $maksettavaa_jaljella - $kateismaksu["kateinen"];

                if ($loytyy_maksutapahtumia) {
                  $pyoristys_otsikko = "Maksettavaa jäljellä";
                  $align = "align='right'";
                }
              }

              echo "  <th colspan='5' id='pyoristysOtsikko'>".t($pyoristys_otsikko).":</th>
                  <td id='pyoristysSarake' class='spec' {$align}>
                  <form name='pyorista' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' autocomplete='off'>
                      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
                      <input type='hidden' name='mista'     value = '$mista'>
                      <input type='hidden' name='tee'     value = 'jyvita'>
                      <input type='hidden' name='toim'     value = '$toim'>
                      <input type='hidden' name='lopetus'   value = '$lopetus'>
                      <input type='hidden' name='ruutulimit'   value = '$ruutulimit'>
                      <input type='hidden' name='tilausrivi_alvillisuus' value='$tilausrivi_alvillisuus'>
                      <input type='hidden' name='projektilla' value='$projektilla'>
                      <input type='hidden' name='orig_tila'   value='$orig_tila'>
                      <input type='hidden' name='orig_alatila' value='$orig_alatila'>";
            }

            if ($laskurow["hinta"] != 0 and (($yhtiorow["alv_kasittely"] == "" and abs($jysum - $summa) <= .50) or ($yhtiorow["alv_kasittely"] != "" and abs($jysum - $arvo) <= .50))) {
              $jysum = $laskurow["hinta"];
            }
            elseif ($tilausrivi_alvillisuus != 'E') {
              $jysum = $summa;
            }
            else {
              $jysum = $arvo;
            }

            if ($toim == 'TARJOUS' and !empty($yhtiorow['salli_jyvitys_tarjouksella'])) {
              echo "<input type='text' size='$koko' name='jysum' value='".sprintf("%.2f", 100*$kate_eieri/($kotiarvo_eieri-$ostot_eieri))."' Style='text-align:right' $state></td>";

              if ($kukarow['extranet'] == '' and $naytetaanko_kate) {
                echo "<td class='spec' align='right'>&nbsp;</td>";
              }

              echo "<td class='spec'>%</td>";
            }
            elseif ($loytyy_maksutapahtumia) {
              echo "{$maksettavaa_jaljella}</td>";
              echo "<td class='spec'>{$laskurow["valkoodi"]}</td>";
            }
            else {
              echo "<input type='text' size='$koko' name='jysum' value='".sprintf("%.2f", $jysum)."' Style='text-align:right' $state></td>";

              if ($kukarow['extranet'] == '' and $naytetaanko_kate) {
                echo "<td class='spec' align='right'>&nbsp;</td>";
              }
              echo "<td class='spec'>$laskurow[valkoodi]</td>";
            }

            if (!$loytyy_maksutapahtumia) {
              echo "<td class='back' colspan='2'><input type='submit' value='" . t("Pyöristä") .
                "' $state></form></td>";
            }
            else {
              echo "</form>";
            }

          }

          echo "</tr>";

          //jos vain tietyt henkilöt saavat jyvittää ja henkilöllä on jyvitys sekä osajyvitys päällä TAI kaikki saavat jyvittää
          if ($toim != 'TARJOUS' and (($yhtiorow["salli_jyvitys_myynnissa"] == "V" and $kukarow['jyvitys'] == 'S') or $yhtiorow["salli_jyvitys_myynnissa"] == "S")) {
            echo "<tr>";
            echo "<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>";
            echo "<th colspan='5'>".t("Pyöristä valitut rivit").":</th>";
            echo "<td class='spec'>";
            echo "<form id='jyvita_valitut_form' name='pyorista' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' autocomplete='off'>
                      <input type='hidden' name='tilausnumero'       value='$tilausnumero'>
                      <input type='hidden' name='mista'           value = '$mista'>
                      <input type='hidden' name='tee'           value = 'jyvita'>
                      <input type='hidden' name='valitut_rivit_jyvitys'   value = '1'>
                      <input type='hidden' name='toim'           value = '$toim'>
                      <input type='hidden' name='lopetus'         value = '$lopetus'>
                      <input type='hidden' name='ruutulimit'         value = '$ruutulimit'>
                      <input type='hidden' name='tilausrivi_alvillisuus'   value = '$tilausrivi_alvillisuus'>
                      <input type='hidden' name='projektilla'       value = '$projektilla'>
                      <input type='hidden' name='orig_tila'         value = '$orig_tila'>
                      <input type='hidden' name='orig_alatila'       value = '$orig_alatila'>";
            echo "<input type='text' size='$koko' name='jysum' value='' Style='text-align:right' $state></td>";
            echo "</td>";
            echo "<td class='spec'></td>";
            echo "<td class='spec'>$laskurow[valkoodi]</td>";
            echo "<td class='back' colspan='2'><input type='submit' value='".t("Pyöristä")."' $state></form></td>";
            echo "</tr>";
          }

          if ($toim == 'TARJOUS' and $yhtiorow['salli_jyvitys_tarjouksella'] == 'S') {
            echo "<tr>";
            echo "<td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>";
            echo "<th colspan='5'>".t("Pyöristä valittujen rivien katetta").":</th>";
            echo "<td class='spec'>";
            echo "<form id='jyvita_valitut_form' name='pyorista' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' autocomplete='off'>
                      <input type='hidden' name='tilausnumero'       value='$tilausnumero'>
                      <input type='hidden' name='mista'           value = '$mista'>
                      <input type='hidden' name='tee'           value = 'kate_jyvita'>
                      <input type='hidden' name='valitut_rivit_jyvitys'   value = '1'>
                      <input type='hidden' name='toim'           value = '$toim'>
                      <input type='hidden' name='lopetus'         value = '$lopetus'>
                      <input type='hidden' name='ruutulimit'         value = '$ruutulimit'>
                      <input type='hidden' name='tilausrivi_alvillisuus'   value = '$tilausrivi_alvillisuus'>
                      <input type='hidden' name='projektilla'       value = '$projektilla'>
                      <input type='hidden' name='orig_tila'         value = '$orig_tila'>
                      <input type='hidden' name='orig_alatila'       value = '$orig_alatila'>";
            echo "<input type='text' size='$koko' name='jysum' value='' Style='text-align:right' $state></td>";
            echo "</td>";
            echo "<td class='spec'></td>";
            echo "<td class='spec'>%</td>";
            echo "<td class='back' colspan='2'><input type='submit' value='".t("Pyöristä")."' $state></form></td>";
            echo "</tr>";
          }
        }

        if ($kukarow["extranet"] == "" and $yhtiorow["myytitilauksen_kululaskut"] == "K") {
          $kulusumma = liitettyjen_kululaskujen_summa($laskurow["tunnus"]);

          if ($kulusumma != 0) {
            echo "<tr>$jarjlisa
                <td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
                <th colspan='5' align='right'>".t("Liitetyt kululaskut").":</th>
                <td class='spec' align='right'>".sprintf("%.2f", $kulusumma)."</td>";

            if ($kukarow['extranet'] == '' and $kotiarvo_eieri != 0 and $kotiarvo_eieri-$ostot_eieri != 0 and $naytetaanko_kate) {
              echo "<td class='spec' align='right' nowrap>".sprintf("%.2f", 100*($kate-$kulusumma)/($kotiarvo-$ostot))."%</td>";
            }
            elseif ($kukarow['extranet'] == '' and $naytetaanko_kate) {
              echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
            }

            echo "<td class='spec'>$yhtiorow[valkoodi]</td></tr>";
          }
        }

        if (isset($etayhtio_totaalisumma) and $etayhtio_totaalisumma !=0) {

          echo "<tr><td class='back'><br></td></tr>";

          echo "<tr>$jarjlisa
              <td class='back' colspan='".($sarakkeet_alku-5)."'>&nbsp;</td>
              <th colspan='5' align='right'>".t("Asiakkaan")." ".t("Veroton yhteensä").":</th>
              <td class='spec' align='right'>".sprintf("%.2f", $etayhtio_totaalisumma);
          echo "</td>";

          if ($kukarow['extranet'] == '' and $kotiarvo != 0 and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
            echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
          }
          elseif ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
            echo "<td class='spec' align='right' nowrap>&nbsp;</td>";
          }

          echo "<td class='spec'>$laskurow[valkoodi]</td></tr>";
        }

      }
      elseif ($toim == "VALMISTAVARASTOON" and $yhtiorow["kehahinta_valmistuksella"] == "K") {
        $_colspan = $sarakkeet_alku - 6;

        echo "<tr>{$jarjlisa}";
        echo "<td class='back' colspan='{$_colspan}'>&nbsp;</td>";
        echo "<th colspan='5' align='right'>";
        echo t("Koko valmistuksen Kehahinta * kpl yhteensä");
        echo "</th>";
        echo "<td class='spec' align='right'>";
        echo sprintf("%.2f", $painotettukehayhteensa);
        echo "</td>";
      }

      echo "</table>";

      if ($kukarow["extranet"] != "" and $arvo_ulkomaa != 0 and $ulkom_huom != '') {
        echo "$ulkom_huom";
      }
    }
    else {
      echo "</td></tr>";
      echo "</table>";
      echo t("Ei rivejä")."...";

      $tilausok++;
    }

    // JT-rivikäyttöliittymä
    if ($jt_kayttoliittyma == "kylla" and $laskurow["liitostunnus"] != 0 and $toim != "TYOMAARAYS" and $toim != "TYOMAARAYS_ASENTAJA" and $toim != "REKLAMAATIO" and $toim != "VALMISTAVARASTOON" and $toim != "MYYNTITILI" and ($toim != "TARJOUS" and $toim != "EXTTARJOUS")) {

      //katotaan eka halutaanko asiakkaan jt-rivejä näkyviin
      if (isset($asiakasrow) and $asiakasrow["jtrivit"] == 0) {

        echo "<br>";

        $toimittaja    = "";
        $toimittajaid  = "";

        $asiakasno     = $laskurow["ytunnus"];
        $asiakasid    = $laskurow["liitostunnus"];

        $automaaginen   = "";
        $jarj       = "toimaika";
        $tee      = "JATKA";
        $tuotenumero  = "";
        $toimi      = "";
        $tilaus_on_jo   = "KYLLA";
        $suoratoimit    = "";
        $varastosta    = array();

        if ($toim == 'SIIRTOLISTA') {
          $toimi = "JOO";
        }

        $query = "SELECT *
                  FROM varastopaikat
                  WHERE yhtio = '$kukarow[yhtio]'";
        $vtresult = pupe_query($query);

        while ($vrow = mysql_fetch_assoc($vtresult)) {
          if ($vrow["tyyppi"] != 'E' or $laskurow["varasto"] == $vrow["tunnus"]) {
            $varastosta[$vrow["tunnus"]] = $vrow["tunnus"];
          }
        }

        if (mysql_num_rows($vtresult) != 0 and count($varastosta) != 0) {
          if ($kukarow['extranet'] != '') {
            echo "<font class='head'>", t("Sinun jälkitoimitusrivisi"), ":</font><br/>";
          }
          require 'jtselaus.php';
        }
        else {
          echo "<font class='message'>".t("Ei toimitettavia JT-rivejä!")."</font>";
        }

      }
    }
  }

  if ($puutetta_on and $tarkistettu) {
    echo "<font class='message'>".t("Tarkista tilaus, kaikille riveille ei riitä saldoa")."</font><br>";
  }
  elseif (!$puutetta_on and $tarkistettu) {
    echo "<font class='message'>".t("Tilaus OK, kaikille riveille riittää saldoa")."</font><br>";
  }

  // Voidaanko myydä kassamyyntinä:
  $_kassamyyntiok = (in_array($toim, array("RIVISYOTTO", "PIKATILAUS", "TYOMAARAYS"))
    or ($toim == "VALMISTAASIAKKAALLE" and !$_onkovalmistettavaa)
    or ($toim == 'REKLAMAATIO'
      and ($yhtiorow['reklamaation_kasittely'] == ''
        or ($yhtiorow['reklamaation_kasittely'] == 'X'
          and $laskurow['tilaustyyppi'] == 'U'))));

  // Jos tilausta ei voida hoitaa kassamyyntinä, niin ei voi myöskään laskuttaa maksupäätteellä
  if (!$_kassamyyntiok) $maksupaate_kassamyynti = FALSE;

  // tulostetaan loppuun parit napit..
  if ((int) $kukarow["kesken"] > 0 and (!isset($ruutulimit) or $ruutulimit == 0)) {
    if ($maksupaate_kassamyynti and
      $_kassamyyntiok and
      $maksuehtorow["kateinen"] != "" and
      $muokkauslukko == "" and
      $laskurow["liitostunnus"] != 0 and
      $tilausok == 0 and
      $rivilaskuri > 0 and
      $asiakasOnProspekti != "JOO"
    ) {
      $kateinen = isset($kateinen) ? $kateinen : "";
      $kateista_annettu = isset($kateista_annettu) ? $kateista_annettu : 0;
      $korttimaksutapahtuman_status =
        isset($korttimaksutapahtuman_status) ? $korttimaksutapahtuman_status : "";
      piirra_maksupaate_formi($laskurow, $kaikkiyhteensa, $kateinen, $maksettavaa_jaljella,
        $loytyy_maksutapahtumia, $kateismaksu, $kateista_annettu,
        $korttimaksutapahtuman_status);
    }

    echo "<br><table width='100%'><tr>$jarjlisa";

    if ($kukarow["extranet"] == "" and $toim == "MYYNTITILI" and $laskurow["alatila"] == "V") {
      echo "  <td class='back ptop'>
          <form name='laskuta' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='tee' value='LASKUTAMYYNTITILI'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value = '$mista'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
          <input type='submit' value='* ".t("Laskuta valitut rivit")." *'>
          </form></td>";

      echo "  <td class='back ptop'>
          <form name='laskuta' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='tee' value='PALAUTAMYYNTITILI'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value = '$mista'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
          <input type='submit' value='* ".t("Palauta valitut rivit omaan varastoon")." *'>
          </form></td>";

      echo "  <td class='back ptop'>
          <form name='lepaa' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='tee' value='LEPAAMYYNTITILI'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value = '$mista'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
          <input type='submit' value='* ".t("Jätä myyntitili lepäämään")." *'>
          </form></td>";

    }

    if ($kukarow["extranet"] == "" and $muokkauslukko == "" and ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA")) {
      echo "  <td class='back ptop'>
          <form name='tlepaamaan' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='tee' value='LEPAA'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value = '$mista'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
          <input type='submit' value='* ".t("Työmääräys lepäämään")." *'>
          </form></td>";

      if ($yhtiorow["vahvistusviesti_asiakkaalle"] == "Y") {
        require_once "inc/jt_ja_tyomaarays_valmis_viesti.inc";

        $aika = hae_vahvistusviesti_lahetetty($tilausnumero);

        $vahvistus_teksti = $aika ? t("Vahvistusviesti on lähetetty asiakkaalle viimeksi") .
          " " .
          "<time datetime='{$aika}'>{$aika}</time>" : "";

        echo
        "<td class='back ptop'>
                <form method='post'>
                  <input type='hidden' name='toim' value='{$toim}'>
                  <input type='hidden' name='lopetus' value='{$lopetus}'>
                  <input type='hidden' name='ruutulimit' value='{$ruutulimit}'>
                  <input type='hidden' name='projektilla' value='{$projektilla}'>
                  <input type='hidden' name='tee' value='laheta_viesti'>
                  <input type='hidden' name='tilausnumero' value='{$tilausnumero}'>
                  <input type='hidden' name='mista' value = '{$mista}'>
                  <input type='hidden' name='orig_tila' value='{$orig_tila}'>
                  <input type='hidden' name='orig_alatila' value='{$orig_alatila}'>
                  <input type='submit' value='" .
          t("Lähetä viesti valmistumisesta asiakkaalle") .
          "' onclick='return confirm(\"" .
          t("Oletko varma, että haluat lähettää asiakkaalle viestin työmääräyksen valmistumisesta?") .
          "\");'>
          <br>
          {$vahvistus_teksti}
                </form>
              </td>";
      }
    }

    if ($kukarow["extranet"] == "" and $muokkauslukko == "" and $toim == "REKLAMAATIO") {

      $napin_teksti = $laskurow['tilaustyyppi'] == 'U' ? "Takuu" : "Reklamaatio";

      echo "<td class='back ptop'>
          <form name='rlepaamaan' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value = '$mista'>
          <input type='hidden' name='tee' value='LEPAA'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>
          <input type='hidden' name='tilaustyyppi' value='{$laskurow['tilaustyyppi']}'>
          <input type='submit' value='* ".t("{$napin_teksti} lepäämään")." *'>
          </form></td>";
    }

    if ($kukarow["extranet"] == "" and $muokkauslukko == "" and ($toim == "TARJOUS" or $toim == "EXTTARJOUS") and $laskurow["liitostunnus"] != 0 and $tilausok == 0 and $rivilaskuri > 0) {

      echo "<td class='back ptop'>";

      //  Onko vielä optiorivejä?
      $query  = "SELECT tilausrivin_lisatiedot.tunnus
                 FROM lasku
                 JOIN tilausrivi ON  tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D'
                 JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus and tilausrivin_lisatiedot.positio = 'Optio'
                 WHERE lasku.yhtio = '$kukarow[yhtio]'
                 and lasku.tunnus  = '$kukarow[kesken]'";
      $optiotarkres = pupe_query($query);

      if (mysql_num_rows($optiotarkres) == 0) {

        if ($laskurow["tunnusnippu"] > 0 and (tarkista_oikeus("tilaus_myynti.php", "PROJEKTI") or tarkista_oikeus("tilaus_myynti.php", "TYOMAARAYS"))) {

          $tarjouslisa = " & <select name='perusta_tilaustyyppi'>";

          $tarjouslisa_normi = $tarjouslisa_projekti = $tarjouslisa_tyomaarays = "";

          $tarjouslisa_normi .= "<option value=''>".t("Perusta")." ".t("Normaalitilaus")."</option>";

          if (tarkista_oikeus("tilaus_myynti.php", "PROJEKTI")) {
            $tarjouslisa_projekti .= "<option value='PROJEKTI'>".t("Perusta")." ".t("Projekti")."</option>";
          }

          if (tarkista_oikeus("tilaus_myynti.php", "TYOMAARAYS")) {
            $tarjouslisa_tyomaarays .= "<option value='TYOMAARAYS'>".t("Perusta")." ".t("Työmääräys")."</option>";
          }

          if ($yhtiorow["hyvaksy_tarjous_tilaustyyppi"] == "T") {
            $tarjouslisa .= $tarjouslisa_tyomaarays.$tarjouslisa_normi.$tarjouslisa_projekti;
          }
          elseif ($yhtiorow["hyvaksy_tarjous_tilaustyyppi"] == "P") {
            $tarjouslisa .= $tarjouslisa_projekti.$tarjouslisa_normi.$tarjouslisa_tyomaarays;
          }
          else {
            $tarjouslisa .= $tarjouslisa_normi.$tarjouslisa_projekti.$tarjouslisa_tyomaarays;
          }

          $tarjouslisa .= "</select>";
        }
        else {
          $tarjouslisa = "";
        }

        if ($toim != 'EXTTARJOUS') {
          echo "  <form name='hyvaksy' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
              <input type='hidden' name='toim' value='$toim'>
              <input type='hidden' name='lopetus' value='$lopetus'>
              <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
              <input type='hidden' name='projektilla' value='$projektilla'>
              <input type='hidden' name='tee' value='HYVAKSYTARJOUS'>
              <input type='hidden' name='tilausnumero' value='$tilausnumero'>
              <input type='hidden' name='mista' value = '$mista'>
              <input type='hidden' name='orig_tila' value='$orig_tila'>
              <input type='hidden' name='orig_alatila' value='$orig_alatila'>
              <input type='submit' value='".t("Hyväksy tarjous")."'>$tarjouslisa
              </form>";
        }
      }
      elseif (mysql_num_rows($optiotarkres) > 0) {
        echo t("Poista optiot ennen tilauksen tekoa")."<br><br>";
      }

      if ($toim != 'EXTTARJOUS') {
        echo "  <br>
            <br>
            <form name='hylkaa' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' onsubmit=\"return confirm('Oletko varma että haluat hylätä tarjouksen $kukarow[kesken]?')\">";

        $tresult = t_avainsana("CRM_TARJOUSPOIS");

        if (mysql_num_rows($tresult) > 0) {
          echo t("Hylkäyksen syy").":";

          echo "<select name='crm_tarjouspois'>";

          while ($itrow = mysql_fetch_assoc($tresult)) {
            echo "<option value='$itrow[selitetark]' $sel>$itrow[selite]</option>";
          }
          echo "</select>";
        }

        echo "<input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
            <input type='hidden' name='projektilla' value='$projektilla'>
            <input type='hidden' name='tee' value='HYLKAATARJOUS'>
            <input type='hidden' name='tilausnumero' value='$tilausnumero'>
            <input type='hidden' name='mista' value = '$mista'>
            <input type='hidden' name='orig_tila' value='$orig_tila'>
            <input type='hidden' name='orig_alatila' value='$orig_alatila'>
            <input type='submit' value='".t("Hylkää tarjous")."'>
            </form>";
        echo "</td>";
      }
    }

    //Näytetään tilaus valmis nappi
    if (($muokkauslukko == "" or $toim == "PROJEKTI" or $toim == "YLLAPITO") and $laskurow["liitostunnus"] != 0 and $tilausok == 0 and $rivilaskuri > 0 and $asiakasOnProspekti != "JOO") {

      // Jos myyjä myy todella pienellä summalta varastosta joka sijaitsee ulkmailla niin herjataan heiman
      $javalisa = "";

      if ($kukarow["extranet"] == "" and $arvo_ulkomaa != 0 and $arvo_ulkomaa <= $yhtiorow["suoratoim_ulkomaan_alarajasumma"]) {
        echo "  <SCRIPT LANGUAGE=JAVASCRIPT>
            function ulkomaa_verify(){
              msg = '".t("Olet toimittamassa ulkomailla sijaitsevasta varastosta tuotteita")." $ulkomaa_kaikkiyhteensa $yhtiorow[valkoodi]! ".t("Oletko varma, että tämä on fiksua")."?';

              if (confirm(msg)) {
                return true;
              }
              else {
                skippaa_tama_submitti = true;
                return false;
              }
            }
            </SCRIPT>";

        $javalisa = "onSubmit = 'return ulkomaa_verify()'";
      }

      if ($nayta_sostolisateksti == "TOTTA" and $kukarow["extranet"] == "") {
        echo "  <SCRIPT LANGUAGE=JAVASCRIPT>
            function ostotilaus_verify(){
              msg = '".t("Olet päivittämässä ostotilausta päivittämällä tätä myyntitilausta")."! ".t("Oletko varma, että haluat päivittää ostotilausta myös")."?';

              if (confirm(msg)) {
                return true;
              }
              else {
                skippaa_tama_submitti = true;
                return false;
              }
            }
            </SCRIPT>";

        $tilausjavalisa = "onClick = 'return ostotilaus_verify()'";
      }

      if ($nayta_sostolisateksti == "HUOM" and $kukarow["extranet"] == "") {
        echo "  <SCRIPT LANGUAGE=JAVASCRIPT>
            function ostotilaus_verify(){
              msg = '".t("HUOM: Päivittämällä tätä myyntitilausta olet tekemässä uuden ostotilauksen vaikka sellainen on jo olemassa")."! ".t("Oletko varma, että haluat tehdä uuden ostotilauksen")."?';

              if (confirm(msg)) {
                return true;
              }
              else {
                skippaa_tama_submitti = true;
                return false;
              }
            }
            </SCRIPT>";

        $tilausjavalisa = "onClick = 'return ostotilaus_verify()'";
      }

      echo "<td class='back ptop'>";

      // otetaan maksuehto selville.. käteinen muuttaa asioita
      $query = "SELECT *
                from maksuehto
                where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
      $result = pupe_query($query);
      $maksuehtorow = mysql_fetch_assoc($result);

      // jos kyseessä on käteiskauppaa
      $kateinen = "";

      if ($maksuehtorow['kateinen'] != '') {
        $kateinen = "X";
      }

      if ($maksuehtorow['jaksotettu'] != '') {
        $query = "SELECT yhtio
                  FROM maksupositio
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND otunnus = '$laskurow[jaksotettu]'";
        $jaksoresult = pupe_query($query);
      }

      if ($laskurow['sisainen'] != '' and $maksuehtorow['jaksotettu'] != '') {
        echo "<font class='error'>".t("VIRHE: Sisäisellä laskulla ei voi olla maksusopimusta!")."</font>";
      }
      elseif ($maksuehtorow['jaksotettu'] != '' and mysql_num_rows($jaksoresult) == 0 and $kukarow["extranet"] == "") {
        echo "<font class='error'>".t("VIRHE: Tilauksella ei ole maksusopimusta!")."</font>";
      }
      elseif ($kukarow["extranet"] == "" and $toim == 'REKLAMAATIO' and
        ($yhtiorow['reklamaation_kasittely'] == 'U' or
          ($yhtiorow['reklamaation_kasittely'] == 'X' and $laskurow['tilaustyyppi'] != 'U'))) {

        $napin_teksti = $laskurow['tilaustyyppi'] == 'U' ? "Takuu" : "Reklamaatio";

        if ($mista == 'keraa') {

          $_takaisin_tunnus = $tilausnumero;

          if (!empty($laskurow['kerayslista'])) {
            $query = "SELECT GROUP_CONCAT(tunnus) tunnukset
                      FROM lasku
                      WHERE yhtio      = '{$kukarow['yhtio']}'
                      AND kerayslista != 0
                      AND kerayslista  = '{$laskurow['kerayslista']}'
                      AND tila         = 'C'
                      AND alatila      = 'C'";
            $takaisin_keraa_res = pupe_query($query);
            $takaisin_keraa_row = mysql_fetch_assoc($takaisin_keraa_res);

            $_takaisin_tunnus = $takaisin_keraa_row['tunnukset'];
          }

          echo "<td class='back ptop'>
              <form method='post' action='keraa.php'>
              <input type='hidden' name='id' value = '{$_takaisin_tunnus}'>
              <input type='hidden' name='toim' value = 'VASTAANOTA_REKLAMAATIO'>
              <input type='hidden' name='lasku_yhtio' value = '$kukarow[yhtio]'>
              <input type='submit' name='tila' value = '".t("Takaisin Hyllytykseen")."'>";
          echo "</form></td>";
        }
        else {

          if ($reklamaatio_saldoton_count == $rivilaskuri) {
            // Vain saldottomia tuotteita
            echo "<td class='back ptop'>
                <form name='rlepaamaan' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
                <input type='hidden' name='toim' value='$toim'>
                <input type='hidden' name='lopetus' value='$lopetus'>
                <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
                <input type='hidden' name='projektilla' value='$projektilla'>
                <input type='hidden' name='tilausnumero' value='$tilausnumero'>
                <input type='hidden' name='mista' value = '$mista'>
                <input type='hidden' name='orig_tila' value='$orig_tila'>
                <input type='hidden' name='orig_alatila' value='$orig_alatila'>
                <input type='hidden' name='tee' value = 'VALMIS_VAINSALDOTTOMIA'>
                <input type='submit' value='* ".t("{$napin_teksti} valmis")." *'>";
          }
          else {
            echo "<td class='back ptop'>
                <form name='rlepaamaan' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
                <input type='hidden' name='toim' value='$toim'>
                <input type='hidden' name='lopetus' value='$lopetus'>
                <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
                <input type='hidden' name='projektilla' value='$projektilla'>
                <input type='hidden' name='tilausnumero' value='$tilausnumero'>
                <input type='hidden' name='kaikkiyhteensa' value='$kaikkiyhteensa'>
                <input type='hidden' name='mista' value = '$mista'>
                <input type='hidden' name='orig_tila' value='$orig_tila'>
                <input type='hidden' name='orig_alatila' value='$orig_alatila'>";

            if (($mista == 'vastaanota' and in_array($laskurow["alatila"], array('A', 'B', 'C'))) or
              ($yhtiorow['reklamaation_kasittely'] == 'X' and $laskurow['tilaustyyppi'] != 'U' and in_array($laskurow["alatila"], array('', 'A', 'B', 'C')))) {
              echo "<input type='hidden' name='tee' value='VASTAANOTTO'>";
              echo "<input type='submit' value='* ".t("{$napin_teksti} Vastaanotettu")." *'>";
            }
            elseif ($mista != 'vastaanota' and ($laskurow["alatila"] == "" or $laskurow["alatila"] == "A")) {
              echo "<input type='hidden' name='tee' value='ODOTTAA'>";
              echo "<input type='submit' value='* ".t("{$napin_teksti} Odottaa Tuotteita saapuvaksi")." *'>";
            }

            if ($laskurow['yhtio_toimipaikka'] != 0) {

              $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio'], $laskurow['yhtio_toimipaikka']);

              if (mysql_num_rows($toimipaikat_res) != 0) {

                $toimipaikat_row = mysql_fetch_assoc($toimipaikat_res);

                if ($sahkoinen_lahete and $kukarow["extranet"] == "" and in_array($toim, $sahkoinen_lahete_toim) and $toimipaikat_row['liiketunnus'] != '') {

                  $query = "SELECT asiakkaan_avainsanat.*
                            FROM asiakkaan_avainsanat
                            WHERE asiakkaan_avainsanat.yhtio       = '{$kukarow['yhtio']}'
                            and asiakkaan_avainsanat.laji          = 'futur_sahkoinen_lahete'
                            and asiakkaan_avainsanat.avainsana    != ''
                            AND asiakkaan_avainsanat.liitostunnus  = '{$laskurow['liitostunnus']}'";
                  $as_avain_chk_res = pupe_query($query);

                  if (mysql_num_rows($as_avain_chk_res) > 0) {
                    echo "<br><br>", t("Lähetä sähköinen lähete"), " <input type='checkbox' name='generoi_sahkoinen_lahete' value='true' checked />";
                  }
                }
              }
            }
          }
          echo "</form></td>";
        }
      }
      elseif ($kukarow['tilaus_valmis'] != "4" and ($toim != 'REKLAMAATIO' or
          ($yhtiorow['reklamaation_kasittely'] == '' or
            ($yhtiorow['reklamaation_kasittely'] == 'X' and $laskurow['tilaustyyppi'] == 'U')) )) {

        if ($_kassamyyntiok and ($kateinen == "X" and $kukarow["kassamyyja"] != "") or $laskurow["sisainen"] != "") {
          $laskelisa = " / ".t("Laskuta")." $otsikko";
        }
        else {
          $laskelisa = "";
        }

        if ($laskurow['tilaustyyppi'] == 'H') {
          $tee_value = 'TARKISTA';
          $painike_txt = t("Tarkista tuotteiden saatavuus");
        }
        else {
          $tee_value = 'VALMIS';
          $painike_txt = $otsikko.' '.t("valmis").' '.$laskelisa;
        }

        $_takuu_tilaustyyppi = "";
        if ($toim == 'REKLAMAATIO' and $laskurow['tilaustyyppi'] == 'U') {
          $_takuu_tilaustyyppi = "<input type='hidden' name='tilaustyyppi' value = '$laskurow[tilaustyyppi]'>";
        }
        elseif ($laskurow['tilaustyyppi'] == 'W') {
         $_takuu_tilaustyyppi = "<input type='hidden' name='tilaustyyppi' value = 'W'>";
        }

        echo "<form name='kaikkyht' id='kaikkyht' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' $javalisa>
          <input type='hidden' name='toim' value='$toim'>
          $_takuu_tilaustyyppi
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='tee' value='$tee_value' id='kaikkyhtTee'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value = '$mista'>
          <input type='hidden' name='rahtipainohinta' value='$rahtihinta'>
          <input type='hidden' name='kaikkiyhteensa' value='".sprintf('%.2f', $summa)."'>
          <input type='hidden' name='orig_tila' value = '$orig_tila'>
          <input type='hidden' name='orig_alatila' value = '$orig_alatila'>";

        if ($yhtiorow['kerayserat'] == 'K' and $kukarow['extranet'] == "" and isset($toimitustavan_lahto)) {
          echo "<input type='hidden' name='toimitustavan_lahto' value='", urlencode(serialize($toimitustavan_lahto)), "' />";
        }

        if ($arvo_ulkomaa != 0) {
          echo "<input type='hidden' name='toimitetaan_ulkomaailta' value='YES'>";
        }
        else {
          echo "<input type='hidden' name='toimitetaan_ulkomaailta' value='NO'>";
        }

        if ($_kassamyyntiok) {
          echo "<input type='hidden' name='kateinen' value='$kateinen'>";
        }

        if (!$maksupaate_kassamyynti or $maksuehtorow["kateinen"] == "") {
          echo "<input type='submit' ACCESSKEY='V' value='$painike_txt'>";
        }

        if ($kukarow["extranet"] == "" and (!$_kassamyyntiok or $kateinen != "X" or $kukarow["kassamyyja"] == "") and ($yhtiorow["tee_osto_myyntitilaukselta"] == "Z" or $yhtiorow["tee_osto_myyntitilaukselta"] == "Q") and in_array($toim, array("PROJEKTI", "RIVISYOTTO", "PIKATILAUS"))) {
          $lisateksti = ($nayta_sostolisateksti == "TOTTA") ? " & ".t("Päivitä ostotilausta samalla") : " & ".t("Tee tilauksesta ostotilaus");

          echo "<input type='submit' name='tee_osto' value='$otsikko ".t("valmis")." $lisateksti' $tilausjavalisa> ";
        }

        if ($kateinen == '' and in_array($toim, array("RIVISYOTTO", "PIKATILAUS")) and !empty($yhtiorow["ennakkolasku_myyntitilaukselta"]) and !empty($yhtiorow["ennakkomaksu_tuotenumero"])) {

          $query = "SELECT yhtio
                    FROM maksupositio
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND otunnus = '$laskurow[jaksotettu]'
                    LIMIT 1";
          $jaksoresult = pupe_query($query);

          if (mysql_num_rows($jaksoresult) == 0 and $laskelisa == '') {
            echo "<br><br>";
            echo "<input type='submit' name='tee_100_ennakkolasku' value='".t("Ennakkolasku")." & $painike_txt'>";

            if ($yhtiorow["ennakkolasku_myyntitilaukselta"] == "B") {
              echo "<br>";
              echo "<input type='submit' name='tee_sis_100_ennakkolasku' value='".t("Sisäinen ennakkolasku")." & $painike_txt'>";
            }
          }
        }

        if ($yhtiorow['lahetteen_tulostustapa'] == 'I' and in_array($toim, array("RIVISYOTTO", "PIKATILAUS"))
          and $laskurow['tila'] == 'N' and $laskurow['alatila'] == '' and ($laskurow['eilahetetta'] != '' or $laskurow['sisainen'] != '')) {

          echo "<br />";
          echo "<br />";
          echo t("Tulosta lähete"), ": ";
          echo "<input type='hidden' name='tulosta_lahete_chkbx[]' value='default' />";
          echo "<input type='checkbox' name='tulosta_lahete_chkbx[]' value='1' checked />";

          $printterinro = 1;

          $komento = hae_lahete_printteri(
            $laskurow['varasto'],
            $laskurow['yhtio_toimipaikka'],
            $laskurow['tunnus'],
            '',
            $printterinro
          );

          echo "<input type='hidden' name='komento[Lähete]' value='{$komento}' />";
          echo "<br />";
          echo t("Tulosta keräyslista"), ": ";
          echo "<input type='hidden' name='tulosta_kerayslista_chkbx[]' value='default' />";
          echo "<input type='checkbox' name='tulosta_kerayslista_chkbx[]' value='1' checked />";
        }

        if ($yhtiorow['lahetteen_tulostustapa'] == "I" and in_array($toim, array("RIVISYOTTO", "PIKATILAUS", "REKLAMAATIO")) and
          (
            ($yhtiorow['lahetteen_tulostustapa'] == "I" and (($laskurow['tila'] == 'N' and $laskurow['alatila'] != '') or ($laskurow['tila'] == 'C' and $laskurow['alatila'] != ''))) or
            ($laskurow['tila'] == 'L' and in_array($laskurow['alatila'], array('B', 'C', 'D'))) or
            ($laskurow['tila'] == 'C' and in_array($laskurow['alatila'], array('B', 'C')))
          )
        ) {
          echo "<br/><br />";
          echo t("Tulosta uusi lähete"), " <input type='checkbox' name='tulosta_lahete_uudestaan' value='tulostetaan' /> ";

          $query = "SELECT *
                    FROM varastopaikat
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$laskurow['varasto']}'";
          $prires = pupe_query($query);

          if (mysql_num_rows($prires) > 0) {
            $prirow = mysql_fetch_array($prires);
            $apuprintteri = $prirow['printteri1']; // läheteprintteri
          }
          else {
            $apuprintteri = 0;
          }

          // Katsotaan onko avainsanoihin määritelty varaston toimipaikan läheteprintteriä
          if (!empty($laskurow['yhtio_toimipaikka'])) {
            $avainsana_where = " and avainsana.selite       = '{$laskurow['varasto']}'
                                 and avainsana.selitetark   = '{$laskurow['yhtio_toimipaikka']}'
                                 and avainsana.selitetark_2 = 'printteri1'";

            $tp_tulostin = t_avainsana("VARTOIMTULOSTIN", '', $avainsana_where, '', '', "selitetark_3");

            if (!empty($tp_tulostin)) {
              $apuprintteri = $tp_tulostin;
            }
          }

          echo "<select name='komento[Lähete]'>";
          echo "<option value=''>".t("Ei kirjoitinta")."</option>";

          $querykieli = "SELECT *
                         FROM kirjoittimet
                         WHERE yhtio  = '{$kukarow['yhtio']}'
                         AND komento != 'edi'
                         ORDER BY kirjoitin";
          $kires = pupe_query($querykieli);

          while ($kirow = mysql_fetch_assoc($kires)) {

            if ($apuprintteri != 0) {
              $sel = $kirow["tunnus"] == $apuprintteri ? "selected" : "";
            }
            elseif ($kirow["tunnus"] == $kukarow["kirjoitin"]) {
              $sel = "selected";
            }
            else {
              $sel = "";
            }

            echo "<option value='{$kirow['komento']}'{$sel}>{$kirow['kirjoitin']}</option>";
          }

          echo "</select>";
        }

        if ($_kassamyyntiok
          and $kukarow["extranet"] == ""
          and $kateinen == 'X'
          and ($kukarow["kassamyyja"] != '' or $kukarow["dynaaminen_kassamyynti"] != "" or $yhtiorow["dynaaminen_kassamyynti"] != "")
        ) {

          if (($kukarow["dynaaminen_kassamyynti"] != "" or $yhtiorow["dynaaminen_kassamyynti"] != "")) {
            echo "<br><br>".t("Valitse kassalipas").":<br>
                <select name='kertakassa' id='kertakassa'>
                <option value='EI_KASSAMYYNTIA'>".t("Ei kassamyyntiä")."</option>";

            $kassalipaslisa = $kukarow['toimipaikka'] != 0 ? "and toimipaikka IN (0, {$kukarow['toimipaikka']})" : "";

            $query = "SELECT *
                      FROM kassalipas
                      WHERE yhtio = '$kukarow[yhtio]'
                      {$kassalipaslisa}
                      ORDER BY nimi";
            $vares = pupe_query($query);

            while ($varow = mysql_fetch_assoc($vares)) {
              $sel='';

              if ($varow["tunnus"] == $laskurow["kassalipas"]) {
                $sel = 'selected';
              }

              echo "<option value='$varow[tunnus]' $sel>$varow[nimi]</option>";
            }

            echo "</select> ";
          }

          echo "<br><br>".t("Kuittikopio").":<br><select name='valittu_kopio_tulostin'>";
          echo "<option value=''>".t("Ei kuittikopiota")."</option>";

          // Tarkistetaan onko asiakkaalla sähköpostiosoitteet setattu
          if (!empty($asiakasrow["lasku_email"])) {
            echo "<option value='asiakasemail{$asiakasrow['lasku_email']}'>", t("Asiakkaan laskutussähköpostiin"), ": {$asiakasrow['lasku_email']}</option>";
          }

          if (!empty($asiakasrow["email"]) and $asiakasrow["email"] != $asiakasrow['lasku_email']) {
            echo "<option value='asiakasemail{$asiakasrow['email']}'>", t("Asiakkaan sähköpostiin"), ": {$asiakasrow['email']}</option>";
          }

          if (!empty($laskurow['toim_email'])) {
            echo "<option value='asiakasemail{$laskurow['toim_email']}'>", t("Asiakkaan sähköpostiin"), ": {$laskurow['toim_email']}</option>";
          }

          $querykieli = "SELECT *
                         FROM kirjoittimet
                         WHERE yhtio = '$kukarow[yhtio]'
                         ORDER BY kirjoitin";
          $kires = pupe_query($querykieli);

          while ($kirow = mysql_fetch_assoc($kires)) {
            echo "<option value='$kirow[tunnus]'>$kirow[kirjoitin]</option>";
          }

          echo "</select>";
        }

        if ($laskurow['yhtio_toimipaikka'] != 0) {

          $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio'], $laskurow['yhtio_toimipaikka']);

          if (mysql_num_rows($toimipaikat_res) != 0) {

            $toimipaikat_row = mysql_fetch_assoc($toimipaikat_res);

            if ($sahkoinen_lahete and $kukarow["extranet"] == "" and in_array($toim, $sahkoinen_lahete_toim) and $toimipaikat_row['liiketunnus'] != '') {

              $query = "SELECT asiakkaan_avainsanat.*
                        FROM asiakkaan_avainsanat
                        WHERE asiakkaan_avainsanat.yhtio       = '{$kukarow['yhtio']}'
                        and asiakkaan_avainsanat.laji          = 'futur_sahkoinen_lahete'
                        and asiakkaan_avainsanat.avainsana    != ''
                        AND asiakkaan_avainsanat.liitostunnus  = '{$laskurow['liitostunnus']}'";
              $as_avain_chk_res = pupe_query($query);

              if (mysql_num_rows($as_avain_chk_res) > 0) {
                echo "<br><br>", t("Lähetä sähköinen lähete"), " <input type='checkbox' name='generoi_sahkoinen_lahete' value='true' checked />";
              }
            }
          }
        }

        echo "</form>";
      }

      if ($yhtiorow['myyntitilaus_tarjoukseksi'] == 'K' and in_array($toim, array('RIVISYOTTO', 'PIKATILAUS')) and $laskurow['tila'] == 'N' and in_array($laskurow['alatila'], array('', 'F')) and tarkista_oikeus("tilaus_myynti.php", "TARJOUS")) {
        echo "  <br><br><form action='' method='post'>
            <input type='hidden' name='toim' value='{$toim}'>
            <input type='hidden' name='tilausnumero' value='{$tilausnumero}'>
            <input type='hidden' name='tee' value='TEE_MYYNTITILAUKSESTA_TARJOUS'>
            <input type='submit' value='", t("Tee tilauksesta tarjous"), "'>
            </form>";
      }

      echo "</td>";

    }
    elseif ($sarjapuuttuu > 0) {
      echo "<font class='error'>".t("VIRHE: Tilaukselta puuttuu sarjanumeroita!")."</font>";
    }

    if ($kukarow['extranet'] != '' and $laskurow["liitostunnus"] != 0 and $tilausok != 0 and $rivilaskuri > 0) {
      $query = "SELECT tilausrivi.varattu
                FROM tilausrivi
                JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.positio = 'Ei varaa saldoa')
                WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
                AND tilausrivi.otunnus  = '$tilausnumero'
                AND tilausrivi.tyyppi  != 'D'";
      $varattu_check_res = pupe_query($query);

      $varattu_nollana = false;

      while ($varattu_check_row = mysql_fetch_assoc($varattu_check_res)) {
        if ($varattu_check_row['varattu'] == 0) $varattu_nollana = true;
      }

      if ($varattu_nollana) {
        echo "<td class='back ptop'>";
        echo "
          <form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='tee' value='PALAUTA_SIIVOTUT'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value = '$mista'>
          <input type='hidden' name='takaisin' value = '$takaisin'>
          <input type='hidden' name='orig_tila' value='$orig_tila'>
          <input type='hidden' name='orig_alatila' value='$orig_alatila'>";
        echo "<input type='submit' value='", t("Palauta tilaukselle"), "'>";
        echo "</form>";
        echo "</td>";
      }
    }

    //  Projekti voidaan poistaa vain jos meillä ei ole sillä mitään toimituksia
    if ($laskurow["tunnusnippu"] > 0 and $toim == "PROJEKTI") {
      $query = "SELECT tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnusnippu]' and tila IN ('L','A','V','N')";
      $abures = pupe_query($query);

      $projektilask = mysql_num_rows($abures);
    }
    else {
      $projektilask = 0;
    }

    if (isset($saako_hyvaksya) and $saako_hyvaksya > 0 and $ei_saa_hyvaksya_kaikkia_riveja < 1) {
      echo "<form method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' name='hyvaksy'>
          <input type='hidden' name='toim'       value = '{$toim}'>
          <input type='hidden' name='lopetus'     value = '{$lopetus}'>
          <input type='hidden' name='ruutulimit'     value = '{$ruutulimit}'>
          <input type='hidden' name='projektilla'   value = '{$projektilla}'>
          <input type='hidden' name='tilausnumero'   value = '{$tilausnumero}'>
          <input type='hidden' name='mista'       value = '{$mista}'>
          <input type='hidden' name='rivitunnus'     value = '{$row['tunnus']}'>
          <input type='hidden' name='ale_peruste'   value = '$row[ale_peruste]'>
          <input type='hidden' name='rivilaadittu'   value = '{$row['laadittu']}'>
          <input type='hidden' name='menutila'     value = '{$menutila}'>
          <input type='hidden' name='orig_tila'     value = '{$orig_tila}'>
          <input type='hidden' name='orig_alatila'   value = '{$orig_alatila}'>
          <input type='hidden' name='tila'       value = 'OOKOOAAKAIKKI'>
          <input type='submit' value='", t("Hyväksy kaikki rivit"), "'>
          </form> ";
    }

    $ei_laskutettu = ($row["laskutettuaika"] == "0000-00-00" or !isset($row["laskutettuaika"]));
    $ei_valmistettu = TRUE;
    if ($laskurow["tila"] == "V" and $row["toimitettuaika"] != "0000-00-00 00:00:00" and isset($row["toimitettuaika"])) $ei_valmistettu = FALSE;

    if (($muokkauslukko == "" or $myyntikielto != '') and ($toim != "PROJEKTI" or ($toim == "PROJEKTI" and $projektilask == 0)) and $kukarow["mitatoi_tilauksia"] == "" and $ei_laskutettu and $ei_valmistettu) {
      echo "<SCRIPT LANGUAGE=JAVASCRIPT>
            function verify(){
              msg = '".t("Haluatko todella poistaa tämän tietueen?")."';

              if (confirm(msg)) {
                return true;
              }
              else {
                skippaa_tama_submitti = true;
                return false;
              }
            }
        </SCRIPT>";

      echo "<td align='right' class='back ptop'>
          <form name='mitatoikokonaan' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' onSubmit = 'return verify();'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
          <input type='hidden' name='projektilla' value='$projektilla'>
          <input type='hidden' name='tee' value='POISTA'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='mista' value = '$mista'>
          <input type='hidden' name='orig_tila' value = '$orig_tila'>
          <input type='hidden' name='orig_alatila' value = '$orig_alatila'>
          <input type='hidden' name='tilaustyyppi' value = '$laskurow[tilaustyyppi]'>
          <input type='submit' class='poista_btn' value='* ".t("Mitätöi koko")." $otsikko *'>
          </form></td>";
    }

    echo "</tr>";

    if ($kukarow['extranet'] != "" and $kukarow['hyvaksyja'] != '') {
      echo "  <tr>$jarjlisa
            <td align='left' class='back ptop'>
            <form action='tulostakopio.php' method='post' name='tulostakopio'>
            <input type='hidden' name='otunnus' value='$tilausnumero'>
            <input type='hidden' name='tilausnumero' value='$tilausnumero'>
            <input type='hidden' name='mista' value = '$mista'>
            <input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
            <input type='hidden' name='toim' value='TILAUSVAHVISTUS'>
            <input type='hidden' name='tee' value='NAYTATILAUS'>
            <input type='hidden' name='extranet_tilausvahvistus' value='1'>
            <input type='submit' name='NAYTATILAUS' value='".t("Näytä Tilausvahvistus")."'>
            </form>
            </td>
          </tr>";
    }
    if ($toim == "EXTENNAKKO" and $kukarow['extranet'] == '') {
      echo "<SCRIPT LANGUAGE=JAVASCRIPT>
            function veri_fyi(){
              msg = '".t("Haluatko todella muuttaa tilauksen normaaliksi ennakkotilaukseksi")."?';

              if (confirm(msg)) {
                return true;
              }
              else {
                skippaa_tama_submitti = true;
                return false;
              }
            }
        </SCRIPT>";

      echo "<tr>$jarjlisa
            <td align='left' class='back ptop'>
            <form name='muuta_ennakoksi' method='post' action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' onSubmit = 'return veri_fyi();'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
            <input type='hidden' name='projektilla' value='$projektilla'>
            <input type='hidden' name='tee' value='MUUTA_EXT_ENNAKKO'>
            <input type='hidden' name='tilausnumero' value='$tilausnumero'>
            <input type='hidden' name='mista' value = '$mista'>
            <input type='hidden' name='orig_tila' value = '$orig_tila'>
            <input type='hidden' name='orig_alatila' value = '$orig_alatila'>
            <input type='hidden' name='tilaustyyppi' value = '$laskurow[tilaustyyppi]'>
            <input type='submit' value='* ".t("Muuta %s normaaliksi ennakkotilaukseksi", "", $otsikko)."*'>
            </form>
            </td>
            </tr>";
    }

    if ($kukarow['extranet'] == ""
      and isset($yhtiorow['tilauksen_myyntieratiedot'])
      and $yhtiorow['tilauksen_myyntieratiedot'] != ''
      and $tilausok == 0
      and $rivilaskuri > 0
    ) {

      if (!isset($piirtele_valikko)) {
        echo "<tr>$jarjlisa
              <td align='left' class='back ptop'>
              <form name='excel_tuote_rapsa' method='post'>
              <input type='hidden' name='lopetus' value='$lopetus'>
              <input type='hidden' name='otunnus' value='$tilausnumero'>
              <input type='hidden' name='tilausnumero' value='$tilausnumero'>
              <input type='hidden' name='mista' value = '$mista'>
              <input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
              <input type='hidden' name='toim' value='$toim'>
              <input type='hidden' name='tee' value='$tee'>
              <input type='hidden' name='naantali' value='KIVAPAIKKA'>
              <input type='submit' name='piirtele_valikko' value='".t("Tuotetiedot")."'>
              </form>
              </td>
            </tr>";
      }
    }

    if ($yhtiorow['laiterekisteri_kaytossa'] != '' and $toim == "YLLAPITO" and !isset($piirtele_laiteluettelo)) {
      echo "<tr>$jarjlisa
            <td align='left' class='back ptop'>
            <form name='excel_laiteluettelo' method='post'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='otunnus' value='$tilausnumero'>
            <input type='hidden' name='tilausnumero' value='$tilausnumero'>
            <input type='hidden' name='mista' value = '$mista'>
            <input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='tee' value='$tee'>
            <input type='hidden' name='naantali' value='EIENAA'>
            <input type='submit' name='piirtele_laiteluettelo' value='".t("Laiteluettelo")."'>
            </form>
            </td>
          </tr>";
    }

    echo "</table>";
  }
}

if (isset($yhtiorow['tilauksen_myyntieratiedot'])
  and $yhtiorow['tilauksen_myyntieratiedot'] != ''
  and isset($naantali)
  and $naantali == "KIVAPAIKKA"
) {
  require "myyntierat_ja_tuotetiedot.inc";
}
elseif ($yhtiorow['laiterekisteri_kaytossa'] != '' and $toim == "YLLAPITO" and isset($naantali) and $naantali == "EIENAA") {
  require "laiteluettelo.php";
}

if (@include "inc/footer.inc");
elseif (@include "footer.inc");
else exit;

function loytyyko_myyja_tunnuksella($tunnus) {
  global $kukarow;

  $query  = "SELECT COUNT(*) AS maara
             FROM kuka
             WHERE yhtio = '{$kukarow['yhtio']}'
             AND myyja   = '{$tunnus}' AND myyja != ''";
  $result = pupe_query($query);

  $maara = mysql_fetch_assoc($result);

  return $maara['maara'] > 0;
}

function piirra_toimitusosoite($laskurow) {
  global $kukarow, $yhtiorow, $kentta;

  $maa_query = "SELECT DISTINCT koodi, nimi
                FROM maat
                WHERE nimi != ''
                ORDER BY koodi";

  $maa_result = pupe_query($maa_query);

  echo "<input type='hidden' id='focusKentta' name='kentta'><br>
        <table>
          <tr>
            <th colspan='2' align='left' class='ptop'>" . t("Toimitusosoite") . ":</th>
          </tr>
          <tr>
            <td class='ptop'>" . t("Nimi") . ":</td>
            <td>
              <input type='text'
                     name='tnimi'
                     value='{$laskurow["toim_nimi"]}'
                     placeholder='" . t("Nimi") . "'
                     onfocus='document.getElementById(\"focusKentta\").value = \"tnimitark\";'
                     onchange='submit();'>
            </td>
          </tr>
          <tr>
            <td class='ptop'></td>
            <td>
              <input type='text'
                     name='tnimitark'
                     value='{$laskurow["toim_nimitark"]}'
                     placeholder=" . t("'Nimi") . "'
                     onfocus='document.getElementById(\"focusKentta\").value = \"tosoite\";'
                     onchange='submit();'>
            </td>
          </tr>
          <tr>
            <td class='ptop'>" . t("Osoite") . ":</td>
            <td>
              <input type='text'
                     name='tosoite'
                     value='{$laskurow["toim_osoite"]}'
                     placeholder='" . t("Osoite") . "'
                     onfocus='document.getElementById(\"focusKentta\").value = \"tpostino\";'
                     onchange='submit();'>
            </td>
          </tr>
          <tr>
            <td class='ptop'>" . t("Postino") . " - " . t("Postitp") . ":</td>
            <td>
              <input type='text'
                     name='tpostino'
                     value='{$laskurow["toim_postino"]}'
                     placeholder='" . t("Postino") . "'
                     onfocus='document.getElementById(\"focusKentta\").value = \"tpostitp\";'
                     onblur='submit();'>
              <input type='text'
                     name='tpostitp'
                     value='{$laskurow["toim_postitp"]}'
                     placeholder='" . t("Postitp") . "'
                     onchange='submit();'>
            </td>
          </tr>
          <tr>
            <td class='ptop'>" . t("Maa") . "</td>
            <td>
              <select name='toim_maa'
                      onchange='submit()' " . js_alasvetoMaxWidth("toim_maa", 200) . ">";

  while ($maa = mysql_fetch_assoc($maa_result)) {
    $sel = "";

    if (strtoupper($laskurow["toim_maa"]) == strtoupper($maa["koodi"])) {
      $sel = "selected";
    }
    elseif ($laskurow["toim_maa"] == "" and
      strtoupper($maa["koodi"]) == strtoupper($yhtiorow["maa"])
    ) {
      $sel = "selected";
    }

    echo
    "<option value='" . strtoupper($maa["koodi"]) . "' {$sel}>" . t($maa["nimi"]) . "</option>";
  }

  echo "      </select>
            </td>
          </tr>
        </table>";
}

function tallenna_toimitusosoite($toimitusosoite, $laskurow) {
  global $kukarow;

  $query =
    "UPDATE lasku
     SET toim_nimi = '{$toimitusosoite["nimi"]}',
     toim_nimitark = '{$toimitusosoite["nimitark"]}',
     toim_osoite   = '{$toimitusosoite["osoite"]}',
     toim_postino  = '{$toimitusosoite["postino"]}',
     toim_postitp  = '{$toimitusosoite["postitp"]}',
     toim_maa      = '{$toimitusosoite["maa"]}'
     WHERE yhtio = '{$kukarow["yhtio"]}'
     AND  tunnus = {$laskurow["tunnus"]}";

  pupe_query($query);

  $laskurow["toim_nimi"]     = $toimitusosoite["nimi"];
  $laskurow["toim_nimitark"] = $toimitusosoite["nimitark"];
  $laskurow["toim_osoite"]   = $toimitusosoite["osoite"];
  $laskurow["toim_postino"]  = $toimitusosoite["postino"];
  $laskurow["toim_postitp"]  = $toimitusosoite["postitp"];
  $laskurow["toim_maa"]      = $toimitusosoite["maa"];

  return $laskurow;
}
