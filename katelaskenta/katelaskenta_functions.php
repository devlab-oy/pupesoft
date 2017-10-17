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


function valmistele_hakutulokset($tuotteet) {
  foreach ($tuotteet as $avain => $arvo) { // $rows muuttuja tulee templaten ulkopuolelta
    // Merkit‰‰n nimitykseen "poistuva"
    if (strtoupper($arvo["status"]) == "P") {
      $tuotteet[$avain]["nimitys"] .= "<br> * " . t("Poistuva tuote");
    }

    $tuotteet[$avain]["myyntihinta"] = hintapyoristys($arvo["myyntihinta"], 2);
    $tuotteet[$avain]["myymalahinta"] = hintapyoristys($arvo["myymalahinta"], 2);
    $tuotteet[$avain]["nettohinta"] = hintapyoristys($arvo["nettohinta"], 2);
    $tuotteet[$avain]["kehahin"] = hintapyoristys($arvo["kehahin"], 2);
  }

  return $tuotteet;
}
