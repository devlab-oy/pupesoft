<?php

session_start();

require_once('../inc/parametrit.inc');
require_once('inc/tyojono2_functions.inc');
require_once('inc/laite_huolto_functions.inc');

if (isset($livesearch_tee) and $livesearch_tee == "ASIAKASHAKU") {
  livesearch_asiakashaku();
  exit;
}

if (isset($livesearch_tee) and $livesearch_tee == "KOHDEHAKU") {
  livesearch_kohdehaku();
  exit;
}

if (!isset($tee)) {
  $tee = '';
}
if (!isset($ala_tee)) {
  $ala_tee = '';
}
if (!isset($toim)) {
  $toim = '';
}
if (!isset($lasku_tunnukset)) {
  $lasku_tunnukset = '';
}
if (!isset($toimitusaika_haku)) {
  $toimitusaika_haku = '';
}
if (!isset($laite_tunnus)) {
  $laite_tunnus = '';
}
if (!isset($ajax_request)) {
  $ajax_request = '';
}

if ($tee == 'lataa_tiedosto') {
  $filepath = "/tmp/" . $tmpfilenimi;
  if (file_exists($filepath)) {
    readfile($filepath);
    unlink($filepath);
  }
  exit;
}

//AJAX requestit tänne
if ($ajax_request) {
  if ($action == 'paivita_tyomaaraysten_tyojonot') {
    if (!empty($lasku_tunnukset)) {
      $params = array(
          'tyojono' => $tyojono,
      );
      $ok = paivita_tyojono_ja_tyostatus_tyomaarayksille($lasku_tunnukset, $params);

      echo $ok;
    }
  }
  exit;
}

require_once('tilauskasittely/tarkastuspoytakirja_pdf.php');
require_once('tilauskasittely/poikkeamaraportti_pdf.php');
require_once('tilauskasittely/tyolista_pdf.php');
require_once('tilauskasittely/laskutuspoytakirja_pdf.php');

echo "<font class='head'>" . t("Laitehuoltojen työjono") . ":</font>";
echo "<hr/>";
echo "<br/>";
$js = hae_tyojono2_js();
$css = hae_tyojono2_css();

echo $js;
echo $css;

enable_ajax();

$request = array(
    'ala_tee'         => $ala_tee,
    'toim'            => $toim,
    'lasku_tunnukset' => $lasku_tunnukset,
    'laite_tunnus'    => $laite_tunnus,
    'asiakas_tunnus'  => $asiakas_tunnus,
    'kohde_tunnus'    => $kohde_tunnus,
    'tyojono'         => $tyojono,
    'tyostatus'       => $tyostatus,
    'toimitusaika'    => $toimitusaika,
);

if (!isset($request['toimitusaika'])) {
  $request['toimitusaika'] = 28;
}

$request['tyojonot'] = hae_tyojonot($request);

$request['tyostatukset'] = hae_tyostatukset($request);

echo "<div id='tyojono_wrapper'>";

echo "<div id='message_box_success'>";
echo '<font class="message">' . t('Päivitys onnistui') . '</font>';
echo "<br/>";
echo "<br/>";
echo "</div>";

echo "<div id='message_box_fail'>";
echo '<font class="message">' . t('Päivitys epäonnistui') . '</font>';
echo "<br/>";
echo "<br/>";
echo "</div>";

if ($request['ala_tee'] == 'tyhjenna_hakuehdot') {
  tyhjenna_sessio();
}

if (isset($_SESSION['tyojono_hakuehdot'])) {
  aseta_hakuehdot($request);
}

if (is_string($request['lasku_tunnukset']) and !empty($request['lasku_tunnukset'])) {
  $request['lasku_tunnukset'] = explode(',', $lasku_tunnukset);
}

if ($toim == 'TEHDYT_TYOT') {
  if ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' or $request['ala_tee'] == 'tulosta_poikkeamaraportti') {
    $pdf_tiedostot = ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' ? PDF\Tarkastuspoytakirja\hae_tarkastuspoytakirjat($request['lasku_tunnukset']) : PDF\Poikkeamaraportti\hae_poikkeamaraportit($request['lasku_tunnukset']));

    foreach ($pdf_tiedostot as $pdf_tiedosto) {
      if (!empty($pdf_tiedosto)) {
        echo_tallennus_formi($pdf_tiedosto, ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' ? t("Tarkastuspöytakirja") : t("Poikkeamaraportti")), 'pdf');
      }
    }
  }
  if ($request['ala_tee'] == 'tulosta_laskutuspoytakirja') {
    $pdf_tiedosto = \PDF\Laskutuspoytakirja\hae_laskutuspoytakirja($request['lasku_tunnukset']);

    if (!empty($pdf_tiedosto)) {
      echo_tallennus_formi($pdf_tiedosto, 'Laskutuspoytakirja', 'pdf');
    }
    else {
      echo t("Laskutuspoytäkirjan generointi epäonnistui");
    }
  }
}
else {
  if ($request['ala_tee'] == 'merkkaa_tehdyksi' and !empty($request['lasku_tunnukset'])) {
    merkkaa_tyomaarays_tehdyksi($request);
  }
  else if ($request['ala_tee'] == 'merkkaa_tehdyksi' and empty($request['lasku_tunnukset'])) {
    echo "<font class='error'>" . t('Yhtään työtä ei merkattu tehdyksi') . "</font>";
  }

  if ($request['ala_tee'] == 'merkkaa_kadonneeksi') {
    merkkaa_laite_kadonneeksi($request);
  }

  if ($request['ala_tee'] == 'tulosta_tyolista') {

    $multi = false;

    //requestista voi tulla lasku_tunnukset, joko stringinä tai arraynä
    //Jos se tulee arraynä niin arrayn solu voi pitää sisällään joko yhden tai useamman lasku_tunnuksen pilkulla eroteltuna
    //tästä syystä todella epäselvää
    //lasku_tunnukset_temp halutaan olevan yksiulotteinen array tunnuksista
    $lasku_tunnukset_temp = array();
    if (is_array($lasku_tunnukset)) {
      foreach ($lasku_tunnukset as $tunnus) {
        $tunnus = explode(',', $tunnus);
        $tunnukset[] = $tunnus;
        foreach ($tunnus as $t) {
          $lasku_tunnukset_temp[] = $t;
        }
      }
      $lasku_tunnukset = $tunnukset;
      $multi = true;
    }
    else {
      $lasku_tunnukset = explode(',', $lasku_tunnukset);
      $lasku_tunnukset_temp = $lasku_tunnukset;
    }

    $pdf_tiedosto = \PDF\Tyolista\hae_tyolistat($lasku_tunnukset, $multi);
    if (!empty($pdf_tiedosto)) {

      if (strpos($pdf_tiedosto, '_')) {
        preg_match('~_(.*?).pdf~', $pdf_tiedosto, $osat);
        $number = '_' . $osat[1];
        $uusi_nimi = 'Tyolista';
      }
      else {
        $number = null;
        $uusi_nimi = 'Kaikki_tyolistat';
      }

      echo_tallennus_formi($pdf_tiedosto, $uusi_nimi, 'pdf', $number);
      aseta_tyomaaraysten_status($lasku_tunnukset_temp, 'T');
    }
    else {
      echo t("Työlista tiedostojen luonti epäonnistui");
    }
  }
}

//lasku_tunnukset pitää unsetata koska niitä käytetään hae_tyomaarays funkkarissa
unset($request['lasku_tunnukset']);

echo_tyojono_kayttoliittyma($request);

echo "<br/>";
echo "<br/>";

if ($request['ala_tee'] != 'tyhjenna_hakuehdot') {
  tallenna_haut_sessioon($request);
}

$request['tyomaaraykset'] = hae_tyomaaraykset($request);
$request['tyomaaraykset'] = kasittele_tyomaaraykset($request);
echo_tyomaaraykset_table($request);

echo "</div>";

require ("inc/footer.inc");
