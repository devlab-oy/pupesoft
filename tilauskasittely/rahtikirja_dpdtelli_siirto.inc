<?php

if ($kollityht > 0) {

  // Onko varastokohtainen bentteserveri?
  if (isset($varasto) and $varasto > 0) {
    $dpdtelli_host = (isset($dpd_telli_host[$varasto])) ? $dpd_telli_host[$varasto] : $dpd_telli_host;
    $dpdtelli_user = (isset($dpd_telli_user[$varasto])) ? $dpd_telli_user[$varasto] : $dpd_telli_user;
    $dpdtelli_pass = (isset($dpd_telli_pass[$varasto])) ? $dpd_telli_pass[$varasto] : $dpd_telli_pass;
    $dpdtelli_path = (isset($dpd_telli_path[$varasto])) ? $dpd_telli_path[$varasto] : $dpd_telli_path;
  }
  else {
    $dpdtelli_host = $dpd_telli_host;
    $dpdtelli_user = $dpd_telli_user;
    $dpdtelli_pass = $dpd_telli_pass;
    $dpdtelli_path = $dpd_telli_path;
  }

  $dpd_telli_siirto = TRUE;

  require "rahtikirja_dpd_siirto.inc";

  unset($dpd_telli_siirto);
}