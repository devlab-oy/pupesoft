<?php
require "../inc/parametrit.inc";
require "../inc/edifact_functions.inc";
/*

if ($task == 'input') {

  $sanoma = str_replace("#@#", "'", $sanoma);

  if (strpos($sanoma, "DESADV") == true) {
    kasittele_rahtikirjasanoma($sanoma);
  }
  elseif (strpos($sanoma, "IFTSTA") == true) {
    kasittele_iftsta($sanoma);
  }
  elseif (strpos($sanoma, "IFTMBF") == true) {
    kasittele_bookkaussanoma($sanoma);
  }

}


if ($task == 'nollaa') {

  $taulut = array(
      "tilausrivi",
      "tilausrivin_lisatiedot",
      "lasku",
      "laskun_lisatiedot",
      "sarjanumeroseuranta",
      "liitetiedostot");

  foreach ($taulut as $taulu) {
    $query = "TRUNCATE TABLE {$taulu}";
    pupe_query($query);
  }

}


  echo "
  <font class='head'>".t("Testaus")."</font>


  <br><br>

  <form action='' method='post'>
    <input type='hidden' name='task' value='input' />

    <textarea name='sanoma'></textarea>

    <input type='submit' value='".t("Lue sanoma")."'>
  </form>

  <br><br>

  <form action='' method='post'>
    <input type='hidden' name='task' value='nollaa' />
    <input type='submit' value='".t("Nollaa tilanne")."'>
  </form>


  ";
*/

require "inc/footer.inc";
