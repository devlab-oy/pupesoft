<?php

// Pupesoft -> Unifaun XML Posting datasiirto

// jos on laitettu kollikenttään 0 niin ei yritetä luoda siirtotiedostoa
if ($yhtiorow['kerayserat'] != 'K' and $kollityht > 0) {

  if ($unifaun_xp_developerid != "" and $unifaun_xp_user != "" and $unifaun_xp_pin != "") {

    $unifaun_xp_devi  = $unifaun_xp_developerid;
    $unifaun_xp_user  = $unifaun_xp_user;
    $unifaun_xp_pass  = $unifaun_xp_pin;
    $unifaun_xp_fail  = $unifaun_xp_fail;
    $unifaun_xp_succ  = $unifaun_xp_succ;
    $unifaun_packsoft = !empty($unifaun_packsoft);

    require "rahtikirja_unifaun_uo_siirto.inc";
  }
  else {
    echo "<br><font class='error'>".t("UNIFAUN-aineiston luontiin tarvittavia parametreja puuttuu!")."</font><br>";
  }
}
