<?php

ini_set("memory_limit", "5G");

//86400 = 1day
set_time_limit(86400);

$compression = FALSE;

require("../inc/parametrit.inc");
require_once('TuoteCSVDumper.php');
require_once('AsiakasCSVDumper.php');
require_once('AsiakasalennusCSVDumper.php');
require_once('YhteyshenkiloCSVDumper.php');
require_once('KohdeCSVDumper.php');
require_once('PaikkaCSVDumper.php');
require_once('LaiteCSVDumper.php');
require_once('TuotteenavainsanaLaiteCSVDumper.php');
require_once('TuotteenavainsanaLaite2CSVDumper.php');
require_once('TuotteenavainsanaToimenpideCSVDumper.php');
require_once('TuoteryhmaCSVDumper.php');
require_once('HuoltosykliCSVDumper.php');
require_once('TarkastuksetKantaCSVDumper.php');
require_once('TarkastuksetCSVDumper.php');
require_once('dump.php');
require_once('kaato_rivit.php');

$request = array(
    'action'           => $action,
    'konversio_tyyppi' => $konversio_tyyppi,
    'kukarow'          => $kukarow
);

$request['konversio_tyypit'] = array(
    'check'                => t('Tarkasta tarkastukset'),
    'tuote'                => t('Tuote'),
    'tuotteen_avainsanat'  => t('Tuotteen avainsanat'),
    'tuotteen_avainsanat2' => t('Tuotteen avainsanat laite tarkistus'),
    'asiakas'              => t('Asiakas'),
    'kohde'                => t('Kohde'),
    'paikka'               => t('Paikka'),
    'laite'                => t('Laite'),
    'yhteyshenkilo'        => t('Yhteyshenkilö'),
    'asiakasalennus'       => t('Asiakasalennus'),
    'huoltosykli'          => t('Huoltosykli'),
    'tarkastukset_kanta'   => t('Tarkastukset kanta'),
    'tarkastukset'         => t('Tarkastukset'),
    'paivita_tarkastukset' => t('Päivitä seuraavat tulevat tapahtumat'),
    'kaikki'               => t('Kaikki'),
    'dump'                 => t('Parametrit'),
    'kaato'                => t('Kaato tiedot')
);

if ($request['action'] == 'aja_konversio') {
  echo_kayttoliittyma($request);
  echo "<br/>";

  switch ($request['konversio_tyyppi']) {
    case 'check':
      tarkasta_tarkastukset();
      break;
    case 'tuote':
      $dumper = new TuoteCSVDumper($request['kukarow']);
      break;

    case 'tuotteen_avainsanat':
      $dumper = new TuotteenavainsanaLaiteCSVDumper($request['kukarow']);
      break;

    case 'tuotteen_avainsanat2':
      $dumper = new TuotteenavainsanaLaite2CSVDumper($request['kukarow']);
      break;

    case 'asiakas':
      $dumper = new AsiakasCSVDumper($request['kukarow']);
      break;

    case 'yhteyshenkilo':
      $dumper = new YhteyshenkiloCSVDumper($request['kukarow']);
      break;

    case 'kohde':
      $dumper = new KohdeCSVDumper($request['kukarow']);
      break;

    case 'asiakasalennus':
      $dumper = new AsiakasalennusCSVDumper($request['kukarow']);
      break;

    case 'paikka':
      $dumper = new PaikkaCSVDumper($request['kukarow']);
      break;

    case 'laite':
      $dumper = new LaiteCSVDumper($request['kukarow']);
      break;

    case 'huoltosykli':
      $dumper = new HuoltosykliCSVDumper($request['kukarow']);
      break;

    case 'tarkastukset_kanta':
      $dumper = new TarkastuksetKantaCSVDumper($request['kukarow']);
      break;

    case 'tarkastukset':
      $tiedostot = lue_tiedostot('/tmp/konversio/tarkastukset/');
      echo "alku:" . date('Y-m-d H:i:s');
      foreach ($tiedostot as $tiedosto) {
        echo $tiedosto . '<br/>';
//        $output = exec("/Applications/MAMP/bin/php/php5.4.10/bin/php tarkastukset.php {$kukarow['yhtio']} {$tiedosto}", $arr, $ret);
        $output = exec("php tarkastukset.php {$kukarow['yhtio']} {$tiedosto}", $arr, $ret);
      }
      echo "loppu:" . date('Y-m-d H:i:s');
      break;

    case 'paivita_tarkastukset':
      paivita_tulevat_tapahtumat();
      break;

    case 'kaikki':
      luo_kaato_tiedot();
      echo t('Tuote') . ':';
      $dumper = new TuoteCSVDumper($request['kukarow']);
      $dumper->aja();
      echo "<br/>";
      echo "<br/>";
      echo t('Toimenpidetuotteiden avainsanat') . ':';
      $dumper = new TuotteenavainsanaToimenpideCSVDumper($request['kukarow']);
      $dumper->aja();
      echo "<br/>";
      echo "<br/>";
      echo t('Tuoteryhmät') . ':';
      $dumper = new TuoteryhmaCSVDumper($request['kukarow']);
      $dumper->aja();
      echo "<br/>";
      echo "<br/>";
      echo t('Laite tuotteiden avainsanat') . ':';
      $dumper = new TuotteenavainsanaLaiteCSVDumper($request['kukarow']);
      $dumper->aja();
      echo "<br/>";
      echo "<br/>";
      echo t('Laite tuotteiden avainsanat 2 (varmistus)') . ':';
      $dumper = new TuotteenavainsanaLaite2CSVDumper($request['kukarow']);
      $dumper->aja();
      echo "<br/>";
      echo "<br/>";
      echo t('Asiakkaat') . ':';
      $dumper = new AsiakasCSVDumper($request['kukarow']);
      $dumper->aja();
      echo "<br/>";
      echo "<br/>";
      echo t('Yhteyshenkilöt') . ':';
      $dumper = new YhteyshenkiloCSVDumper($request['kukarow']);
      $dumper->aja();
      echo "<br/>";
      echo "<br/>";
      echo t('Asiakasalennukset') . ':';
      $dumper = new AsiakasalennusCSVDumper($request['kukarow']);
      $dumper->aja();
      echo "<br/>";
      echo "<br/>";
      echo t('Kohteet') . ':';
      $dumper = new KohdeCSVDumper($request['kukarow']);
      $dumper->aja();
      echo "<br/>";
      echo "<br/>";
      echo t('Paikat') . ':';
      $dumper = new PaikkaCSVDumper($request['kukarow']);
      $dumper->aja();
      echo "<br/>";
      echo "<br/>";
      echo t('Laitteet') . ':';
      $dumper = new LaiteCSVDumper($request['kukarow']);
      $dumper->aja();
      echo "<br/>";
      echo "<br/>";
      echo t('Huoltosyklit') . ':';
      $dumper = new HuoltosykliCSVDumper($request['kukarow']);
      $dumper->aja();
      break;

    case 'dump':
      dump_seed_data();
      break;

    case 'kaato':
      luo_kaato_tiedot();
      break;

    default:
      die('Ei onnistu tämä');
      break;
  }

  if (!in_array($request['konversio_tyyppi'], array('kaikki', 'tarkastukset')) and isset($dumper)) {
    $dumper->aja();
  }

  if ($request['konversio_tyyppi'] == 'tuote') {
    $dumper = new TuotteenavainsanaToimenpideCSVDumper($request['kukarow']);
    $dumper->aja();

//    $dumper = new TuoteryhmaCSVDumper($request['kukarow']);
//    $dumper->aja();
//    $dumper = new TuotteenavainsanaLaiteCSVDumper($request['kukarow']);
//    $dumper->aja();

    $dumper = new TuotteenavainsanaLaite2CSVDumper($request['kukarow']);
    $dumper->aja();
  }
}
else if ($request['action'] == 'poista_konversio_aineisto_kannasta') {
  $query_array = array(
      'DELETE FROM asiakas WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM yhteyshenkilo WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM tuote WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM kohde WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM paikka WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM laite WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM asiakasalennus WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM tuotteen_avainsanat WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM avainsana WHERE yhtio = "' . $kukarow['yhtio'] . '" AND laji = "TRY"',
      'DELETE FROM huoltosykli WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM huoltosyklit_laitteet WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM tyomaarays WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM lasku WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM laskun_lisatiedot WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM tilausrivi WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM tilausrivin_lisatiedot WHERE yhtio = "' . $kukarow['yhtio'] . '"',
      'DELETE FROM tiliointi WHERE yhtio = "' . $kukarow['yhtio'] . '"',
  );
  foreach ($query_array as $query) {
    pupe_query($query);
  }

  echo t('Poistettu');
  echo "<br/>";

  echo_kayttoliittyma($request);
}
else {
  echo_kayttoliittyma($request);
}

require('inc/footer.inc');

function echo_kayttoliittyma($request) {
  global $kukarow, $yhtiorow;

  echo "<form action='' method='POST'>";
  echo "<input type='hidden' name='action' value='aja_konversio' />";
  echo "<table>";

  echo "<tr>";
  echo "<th>" . t('Tiedosto') . "</th>";
  echo "<td>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t('Konversio tyyppi') . "</th>";
  echo "<td>";
  echo "<select name='konversio_tyyppi'>";
  foreach ($request['konversio_tyypit'] as $konversio_tyyppi => $selitys) {
    $sel = "";
    if ($request['konversio_tyyppi'] == $konversio_tyyppi) {
      $sel = "SELECTED";
    }
    echo "<option value='{$konversio_tyyppi}' {$sel}>{$selitys}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<input type='submit' value='" . t('Lähetä') . "' />";
  echo "</form>";

  echo "<form action='' method='POST'>";
  echo "<input type='hidden' name='action' value='poista_konversio_aineisto_kannasta' />";
  echo "<input type='submit' value='" . t('Poista koko konversio aineisto') . "' />";
  echo "</form>";
}

function lue_tiedostot($polku) {
  $tiedostot = array();
  $handle = opendir($polku);
  if ($handle) {
    while (false !== ($tiedosto = readdir($handle))) {
      if ($tiedosto != "." && $tiedosto != ".." && $tiedosto != '.DS_Store') {
        if (is_file($polku . $tiedosto)) {
          $tiedostot[] = $polku . $tiedosto;
        }
      }
    }
    closedir($handle);
  }

  return $tiedostot;
}

function tarkasta_tarkastukset() {
  global $kukarow, $yhtiorow;

  $vanhat_tarkastukset = hae_vanhat_tarkastukset();
  $huoltosyklit = hae_kaikkien_laitteiden_huoltosyklit();

  $laite_koodit = array_keys($huoltosyklit);

  $oikein = 0;
  $vaarin = 0;
  $ei_olemassa = 0;
  $ei_liitettyja_huoltosykleja = 0;
  $ei_oikeaa_huoltosyklia = 0;
  foreach ($vanhat_tarkastukset as &$vanha_tarkastus) {

    if ($vanha_tarkastus['TUOTENRO'] == 'A990001' or $vanha_tarkastus['TUOTENRO'] == '990001' or $vanha_tarkastus['TUOTENRO'] == '990011') {
      $vanha_tarkastus['TUOTENRO'] = 'KAYNTI';
    }

    $uusi_paiva = $huoltosyklit[$vanha_tarkastus['LAITE']]['huoltosyklit'][$vanha_tarkastus['TUOTENRO']]['seuraava_tapahtuma'];
    if ($uusi_paiva == null) {
      if (!in_array($vanha_tarkastus['LAITE'], $laite_koodit)) {
        echo "Laite {$vanha_tarkastus['LAITE']} EI OLEMASSA toimenpide {$vanha_tarkastus['TUOTENRO']} pitäisi olla {$vanha_tarkastus['ED']}";
        $ei_olemassa++;
        $vaarin++;
      }
      else {
        $debug = $huoltosyklit[$vanha_tarkastus['LAITE']];
        $huoltosykli_tuotenumerot = array_keys($huoltosyklit[$vanha_tarkastus['LAITE']]['huoltosyklit']);
        if (!in_array($vanha_tarkastus['TUOTENRO'], $huoltosykli_tuotenumerot)) {
          $laite = hae_laite_koodilla($vanha_tarkastus['LAITE']);
          $huoltosykli = hae_huoltosykli2($vanha_tarkastus['TUOTENRO'], $vanha_tarkastus['VALI']);

          if (!empty($laite) and !empty($huoltosykli)) {
            $ok = liita_huoltosykli_laitteeseen2($laite, $huoltosykli, $vanha_tarkastus['ED']);
            if ($ok) {
              echo "Huoltosykli {$huoltosykli['toimenpide']} liitetty laitteeseen {$laite['tuoteno']}";
              $oikein++;
            }
            else {
              echo "Huoltosyklin liitto ERROR2";
              $vaarin++;
            }
          }
          else {
            $vaarin++;
            echo "Huoltosyklin liitto ERROR {$laite['tuoteno']} {$huoltosykli['toimenpide']} - {$vanha_tarkastus['TUOTENRO']} {$vanha_tarkastus['VALI']}";
            if (empty($laite)) {
              echo " Laite tyhjä";
            }
            if (empty($huoltosykli)) {
              echo " Huoltosykli tyhjä";
            }
          }
        }
        else {
          echo "seuraava_tapahtuma null";
          $vaarin++;
        }
      }
      echo "<br/>";
      continue;
    }

    if ($uusi_paiva != $vanha_tarkastus['ED']) {
      $vaarin++;
      echo "Laite {$vanha_tarkastus['LAITE']} toimenpide {$vanha_tarkastus['TUOTENRO']} {$uusi_paiva} pitäisi olla {$vanha_tarkastus['ED']} vanha huoltovali: " . ($vanha_tarkastus['VALI'] / 12) . 'v';
      echo "<br/>";
    }
    else {
      $oikein++;
      echo "Oikein oli";
      echo "<br/>";
    }
  }

  echo "<br/>";
  echo "<br/>";
  echo "Oikein: {$oikein} Vaarin: {$vaarin}";
  echo "<br/>";
  echo "(ei olemassa: {$ei_olemassa} ei liitetty huoltosykleja: {$ei_liitettyja_huoltosykleja} ei oikeaa_huoltosyklia: {$ei_oikeaa_huoltosyklia}";

  echo "<br/>";
  echo "<br/>";

//  koeponnistus_tarkastus();
}

function tarkasta_tarkastukset2() {
  global $kukarow, $yhtiorow;

  $huoltosyklit = hae_kaikkien_laitteiden_huoltosyklit();
  $laite_koodit = array_keys($huoltosyklit);
  $vanhat_tarkastukset = hae_vanhat_tarkastukset($laite_koodit);

  $oikein = 0;
  $vaarin = 0;
  foreach ($huoltosyklit as $laite_koodi => $huoltosykli) {
    foreach ($huoltosykli['huoltosyklit'] as $toimenpide_tuoteno => $toimenpide) {
      foreach ($vanhat_tarkastukset as $vanha_tarkastus) {
        if ($vanha_tarkastus['LAITE'] == $laite_koodi and $vanha_tarkastus['TUOTENRO'] == $toimenpide_tuoteno) {
          if ($vanha_tarkastus['ED'] != $toimenpide['seuraava_tapahtuma']) {
            $vaarin++;
            echo "Laite {$vanha_tarkastus['LAITE']} toimenpide {$vanha_tarkastus['TUOTENRO']} {$toimenpide['seuraava_tapahtuma']} pitäisi olla {$vanha_tarkastus['ED']}";
            echo "<br/>";
          }
          else {
            $oikein++;
            echo "Oikein oli";
            echo "<br/>";
          }
        }
      }
    }
  }

  echo "<br/>";
  echo "<br/>";
  echo "Oikein: {$oikein} Vaarin: {$vaarin}";
}

function hae_kaikkien_laitteiden_huoltosyklit() {
  global $kukarow, $yhtiorow;

  $query = "SELECT laite.tuoteno AS laite_tuoteno,
            laite.koodi AS laite_koodi,
            hl.tunnus AS huoltosyklit_laitteet_tunnus,
            hl.viimeinen_tapahtuma,
            hl.huoltovali,
            h.toimenpide AS toimenpide_tuoteno
            FROM laite
            LEFT JOIN huoltosyklit_laitteet AS hl
            ON (hl.yhtio = laite.yhtio
              AND hl.laite_tunnus = laite.tunnus )
            LEFT JOIN huoltosykli AS h
            ON (h.yhtio = hl.yhtio
              AND h.tunnus = hl.huoltosykli_tunnus )
            WHERE laite.yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $huoltovalit = huoltovali_options();
  $huoltosyklit = array();
  while ($huoltosykli = mysql_fetch_assoc($result)) {
    if ($huoltosykli['viimeinen_tapahtuma'] == null) {
      $huoltosykli['seuraava_tapahtuma'] = '0000-00-00 00:00:00';
    }
    else {
      if (!in_array($huoltosykli['huoltovali'], array_keys($huoltovalit))) {
        $huoltosykli['seuraava_tapahtuma'] = "HUOLTOVÄLIÄ {$huoltosykli['huoltovali']} EI MAHDOLLINEN";
      }
      else {
        $huoltosykli['seuraava_tapahtuma'] = date('Y-m-d', strtotime("{$huoltosykli['viimeinen_tapahtuma']} + {$huoltovalit[$huoltosykli['huoltovali']]['years']} years")) . ' 00:00:00';
      }
    }
    $huoltosyklit[$huoltosykli['laite_koodi']]['huoltosyklit'][$huoltosykli['toimenpide_tuoteno']] = $huoltosykli;
  }

  return $huoltosyklit;
}

function hae_vanhat_tarkastukset($laite_koodit = array()) {
  global $kukarow, $yhtiorow;

  $where = "";
  if (!empty($laite_koodit)) {
    $where = " AND LAITE IN ('" . implode("','", $laite_koodit) . "')";
  }
  $query = "SELECT ID,
            LAITE,
            TARKASTUS,
            TUOTENRO,
            NIMIKE,
            ED,
            VALI,
            TUNNUS
            FROM tarkastukset
            WHERE STATUS = 'Ilmoitettu'
            {$where}";
  $result = pupe_query($query);
  $tarkastukset = array();
  while ($tarkastus = mysql_fetch_assoc($result)) {
    $tarkastukset[] = $tarkastus;
  }

  return $tarkastukset;
}

function koeponnistus_tarkastus() {
  $query = "SELECT laite,
            tuotenro,
            nimike,
            ed,
            seur,
            STATUS,
            vali
            FROM   tarkastukset
            WHERE STATUS = 'ilmoitettu'";
  $result = pupe_query($query);
  $ilmoitetut = array();
  while ($ilmoitettu = mysql_fetch_assoc($result)) {
    $ilmoitetut[$ilmoitettu['laite']][$ilmoitettu['tuotenro']] = $ilmoitettu;
  }

  $query = "SELECT laite,
            tuotenro,
            nimike,
            ed,
            seur,
            STATUS,
            vali,
            DATE_ADD(ed, INTERVAL vali MONTH) AS seuraava_tapahtuma
            FROM   tarkastukset
            WHERE STATUS = 'valmis'
            GROUP BY laite
            ORDER BY laite DESC, ed DESC;";
  $result = pupe_query($query);

  $vaarin = 0;
  $riveja = 0;
  while ($koeponnistus_rivi = mysql_fetch_assoc($result)) {
    $riveja++;

    $ilmoitettu_koeponnistus = $ilmoitetut[$koeponnistus_rivi['laite']][$koeponnistus_rivi['tuotenro']];

    if ($ilmoitettu_koeponnistus == false) {
//      echo "Ei ilmoitettua koeponnistusta. Laite: {$koeponnistus_rivi['laite']} toimenpide: {$koeponnistus_rivi['tuotenro']}";
//      echo "<br/>";
//      echo "vali: {$koeponnistus_rivi['vali']}";
//      echo "<br/>";
//      echo "Laitteen edellinen tapahtuma: ".date('Y-m-d', strtotime($koeponnistus_rivi['ed']));
//      echo "<br/>";
//      echo "Tuleva tapahtuma pitäisi olla: ".date('Y-m-d', strtotime($koeponnistus_rivi['seuraava_tapahtuma']));
//      echo "<br/>";
//      $vaarin++;
    }
    else {
      if ($ilmoitettu_koeponnistus['ed'] != $koeponnistus_rivi['seuraava_tapahtuma']) {
        $sykli = (strtotime($koeponnistus_rivi['ed']) - strtotime($ilmoitettu_koeponnistus['ed'])) / 60 / 60 / 24 / 365;
        echo "Vanhan järjestelmän bugiko? Laite: {$koeponnistus_rivi['laite']} toimenpide: {$koeponnistus_rivi['tuotenro']}";
        echo "<br/>";
        echo "vali: {$koeponnistus_rivi['vali']}";
        echo "<br/>";
        echo "Laitteen edellinen tapahtuma: " . date('Y-m-d', strtotime($koeponnistus_rivi['ed']));
        echo "<br/>";
        echo "Tuleva tapahtuma pitäisi olla: " . date('Y-m-d', strtotime($koeponnistus_rivi['seuraava_tapahtuma']));
        echo "<br/>";
        echo "Tuleva tapahtuma onkin: " . date('Y-m-d', strtotime($ilmoitettu_koeponnistus['ed']));
        echo "<br/>";
        echo "syklin todellinen pituus: " . abs($sykli);
        echo "<br/>";
        $vaarin++;
      }
    }
    echo "<br/>";
  }

  echo "<br/>";
  echo "{$riveja} joissa {$vaarin} ongelmaa";
}

function paivita_tulevat_tapahtumat() {
  global $kukarow, $yhtiorow;

  $vanhat_tarkastukset = hae_vanhat_tarkastukset();
  $huoltosyklit = hae_kaikkien_laitteiden_huoltosyklit();

  foreach ($vanhat_tarkastukset as $vanha_tarkastus) {
    if ($vanha_tarkastus['TUOTENRO'] == 'A990001' or $vanha_tarkastus['TUOTENRO'] == '990001' or $vanha_tarkastus['TUOTENRO'] == '990011') {
      $vanha_tarkastus['TUOTENRO'] = 'KAYNTI';
    }

    $uusi_paiva = $huoltosyklit[$vanha_tarkastus['LAITE']]['huoltosyklit'][$vanha_tarkastus['TUOTENRO']]['seuraava_tapahtuma'];

    if (!empty($vanha_tarkastus['ED']) and $uusi_paiva != $vanha_tarkastus['ED']) {
      $uusi_huoltosykli = $huoltosyklit[$vanha_tarkastus['LAITE']]['huoltosyklit'][$vanha_tarkastus['TUOTENRO']];
      if (!empty($uusi_huoltosykli)) {
        $huoltosyklit_laitteet_tunnus = $huoltosyklit[$vanha_tarkastus['LAITE']]['huoltosyklit'][$vanha_tarkastus['TUOTENRO']]['huoltosyklit_laitteet_tunnus'];
        $vanhan_jarjestelman_viimeinen_tapahtuma = date('Y-m-d', strtotime("{$vanha_tarkastus['ED']} - {$vanha_tarkastus['VALI']} month"));
        paivita_viimeinen_tapahtuma($huoltosyklit_laitteet_tunnus, $vanhan_jarjestelman_viimeinen_tapahtuma, $vanha_tarkastus['VALI']);
      }
    }
  }
}

function paivita_viimeinen_tapahtuma($huoltosyklit_laitteet_tunnus, $vanhan_jarjestelman_viimeinen_tapahtuma, $vanhan_jarjestelman_huoltovali) {
  global $kukarow, $yhtiorow;

  if (empty($huoltosyklit_laitteet_tunnus)) {
    echo "Päivitys epäonnistui";
    echo "<br/>";
    return false;
  }
  else {
    echo "Päivitetty";
    echo "<br/>";
  }

  $huoltovali_options = huoltovali_options();
  $vanhan_jarjestelman_huoltovali = search_array_key_for_value_recursive($huoltovali_options, 'months', $vanhan_jarjestelman_huoltovali);
  $vanhan_jarjestelman_huoltovali = $vanhan_jarjestelman_huoltovali[0];

  $huoltosyklit_laitteet_update = "";
  if ($vanhan_jarjestelman_huoltovali['days'] > 0) {
    $huoltosyklit_laitteet_update = "  huoltovali = '{$vanhan_jarjestelman_huoltovali['days']}',";
  }

  $query = "UPDATE huoltosyklit_laitteet SET
            {$huoltosyklit_laitteet_update}
            viimeinen_tapahtuma = '" . date('Y-m-d', strtotime($vanhan_jarjestelman_viimeinen_tapahtuma)) . "'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$huoltosyklit_laitteet_tunnus}'";

  pupe_query($query);
}

function hae_laite_koodilla($koodi) {
  global $kukarow, $yhtiorow;

  $query = "SELECT *
            FROM laite
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND koodi = '{$koodi}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function hae_huoltosykli2($toimenpide, $vali_kk) {
  global $kukarow, $yhtiorow;

  $huoltovali_options = huoltovali_options();
  $huoltovali = search_array_key_for_value_recursive($huoltovali_options, 'months', $vali_kk);
  $huoltovali = $huoltovali[0];

  //Kyseessä arvailu haku. Vanhojen tarkastuksien perusteella ei tiedetä mm. olosuhdetta niin liitetään laitteeseen huoltosykli jonka olosuhde on sisällä
  $query = "SELECT *
            FROM huoltosykli
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND toimenpide = '{$toimenpide}'
            AND olosuhde = 'A'
            AND huoltovali = '{$huoltovali['days']}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    $query = "SELECT *
              FROM huoltosykli
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND toimenpide = '{$toimenpide}'
              AND huoltovali = '{$huoltovali['days']}'
              LIMIT 1";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      $query = "SELECT *
                FROM huoltosykli
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND toimenpide = '{$toimenpide}'
                AND olosuhde = 'A'
                LIMIT 1";
      $result = pupe_query($query);
    }
  }

  return mysql_fetch_assoc($result);
}

function liita_huoltosykli_laitteeseen2($laite, $huoltosykli, $seuraava_tapahtuma) {
  global $kukarow, $yhtiorow;

  if (empty($seuraava_tapahtuma)) {
    return false;
  }

  $query = "SELECT *
            FROM huoltosyklit_laitteet
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND huoltosykli_tunnus = '{$huoltosykli['tunnus']}'
            AND laite_tunnus = '{$laite['tunnus']}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 0) {
    return false;
  }

  $huoltovali_options = huoltovali_options();
  $huoltovali = search_array_key_for_value_recursive($huoltovali_options, 'days', $huoltosykli['huoltovali']);
  $huoltovali = $huoltovali[0];
  if (empty($huoltovali)) {
    return false;
  }
  $viimeinen_tapahtuma = date('Y-m-d', strtotime("{$seuraava_tapahtuma} - {$huoltovali['years']} years"));

  $query = "INSERT INTO huoltosyklit_laitteet
            SET yhtio = '{$kukarow['yhtio']}',
            huoltosykli_tunnus = '{$huoltosykli['tunnus']}',
            laite_tunnus = '{$laite['tunnus']}',
            huoltovali = '{$huoltosykli['huoltovali']}',
            pakollisuus = '1',
            viimeinen_tapahtuma = '{$viimeinen_tapahtuma}',
            laatija = 'import',
            luontiaika = NOW()";
  pupe_query($query);

  return true;
}
