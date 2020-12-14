<?php

if ($kollityht > 0) {

  // Onko varastokohtainen bentteserveri?
  if (isset($varasto) and $varasto > 0) {
    $bs_host = (isset($bentte_host[$varasto])) ? $bentte_host[$varasto] : $bentte_host;
    $bs_user = (isset($bentte_user[$varasto])) ? $bentte_user[$varasto] : $bentte_user;
    $bs_pass = (isset($bentte_pass[$varasto])) ? $bentte_pass[$varasto] : $bentte_pass;
    $bs_path = (isset($bentte_path[$varasto])) ? $bentte_path[$varasto] : $bentte_path;
    $bs_skey  = (isset($bentte_skey[$varasto])) ? $bentte_skey[$varasto] : $bentte_skey;
  }
  else {
    $bs_host = $bentte_host;
    $bs_user = $bentte_user;
    $bs_pass = $bentte_pass;
    $bs_path = $bentte_path;
    $bs_skey = $bentte_path;
  }

  $bentte_siirto = TRUE;

  require "rahtikirja_dpd_siirto.inc";

  unset($bentte_siirto);
}