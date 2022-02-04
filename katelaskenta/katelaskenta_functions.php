<?php

/*
 * katelaskenta_functions.php
 *
 * Sis‰lt‰‰ entist‰ koodia, jota on jaettu pienempiin funktioihin.
 * Tarvitaan, jotta tietyt toiminnot katelaskennan haku osiossa toimii.
 * Varmuutta ei ole mit‰ kaikkea n‰m‰ tekev‰t ja mit‰ ei tarvita.
 * Siistit‰‰n tiedostoa kun tulee tarpeellisesti.
 *
 * Katelaskennan omat funktiot ovat functions.katelaskenta.php -tiedostossa.
 */

/**
 * Funktio valmistelee hakutulokset templatea varten.
 *
 * Palauttaa muokatun hakutulostaulukon.
 *
 * @param type    $tuotteet
 */

function katelaskuri($hinta, $keha) {
  return number_format(100*((float) $hinta-(float) $keha) / (float) $hinta, 2);
}

function laske_kate($tuote) {
  $tuote['myyntikate'] = katelaskuri($tuote['myyntihinta'], $tuote['kehahin']);
  $tuote['myymalakate'] = katelaskuri($tuote['myymalahinta'], $tuote['kehahin']);
  $tuote['nettokate'] = katelaskuri($tuote['nettohinta'], $tuote['kehahin']);
  $tuote['asiakashinta_asiakas_myyntikate'] = katelaskuri($tuote['asiakashinta_hinta'], $tuote['kehahin']);
  return $tuote;
}

function valmistele_hakutulokset($tuotteet) {
  foreach ($tuotteet as $haku_funktio_key => $template_tuote) {
    foreach ($template_tuote as $avain => $arvo) { // $rows muuttuja tulee templaten ulkopuolelta
      // Merkit‰‰n nimitykseen "poistuva"
      if (strtoupper($arvo["status"]) == "P") {
        $tuotteet[$haku_funktio_key][$avain]["nimitys"] .= "<br> * " . t("Poistuva tuote");
      }

      $tuotteet[$haku_funktio_key][$avain]["myyntihinta"] = hintapyoristys($arvo["myyntihinta"], 2);
      $tuotteet[$haku_funktio_key][$avain]["myymalahinta"] = hintapyoristys($arvo["myymalahinta"], 2);
      $tuotteet[$haku_funktio_key][$avain]["nettohinta"] = hintapyoristys($arvo["nettohinta"], 2);
      $tuotteet[$haku_funktio_key][$avain]["kehahin"] = hintapyoristys($arvo["kehahin"], 2);
      $tuotteet[$haku_funktio_key][$avain]["asiakashinta_hinta"] = hintapyoristys($arvo["asiakashinta_hinta"], 2);
    }
  }

  return $tuotteet;
}
