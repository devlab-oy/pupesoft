<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Kauppasopimuksen tiedot")."</font><hr>";

$class[$tee] = "class='lisaa_btn'";

$query = "SELECT laskun_lisatiedot.*, lasku.*,
          laskun_lisatiedot.tunnus laskun_lisatiedottunnus,
          lasku.tunnus laskutunnus
          FROM lasku
          LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
          WHERE lasku.tunnus = '$kukarow[kesken]'
          AND lasku.yhtio    = '$kukarow[yhtio]'
          AND lasku.tunnus   = $tilausnumero";
$result = pupe_query($query);
$laskurow = mysql_fetch_assoc($result);

echo "<form method='post' action=''>
      <input type='hidden' name='tee' value='ostajantiedot'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='mista' value='$mista'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='lopetus' value='$tilmyy_lopetus'>
      <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
      <input type='hidden' name='projektilla' value='$projektilla'>
      <input type='hidden' name='orig_tila' value='$orig_tila'>
      <input type='hidden' name='orig_alatila' value='$orig_alatila'>
      <input type='submit' {$class["ostajantiedot"]} value='".t("Ostajan tiedot")."'>
      </form>";

// Tarvitaanko maksusopimus
$query = "SELECT *
          from maksuehto
          where yhtio = '$kukarow[yhtio]'
          and tunnus  = '$laskurow[maksuehto]'";
$result = pupe_query($query);

if (mysql_num_rows($result)==1) {
  $maksuehtorow = mysql_fetch_assoc($result);

  if ($maksuehtorow['jaksotettu'] != '' and $kukarow["extranet"] == "") {
    echo "  <form method='post' action=''>
        <input type='hidden' name='tilausnumero' value='$tilausnumero'>
        <input type='hidden' name='mista' value='$mista'>
        <input type='hidden' name='tee' value='MAKSUSOPIMUS'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
        <input type='hidden' name='projektilla' value='$projektilla'>
        <input type='hidden' name='orig_tila' value='$orig_tila'>
        <input type='hidden' name='orig_alatila' value='$orig_alatila'>
        <input type='submit' {$class["MAKSUSOPIMUS"]} value='".t("Maksusuunnitelma")."'>
        </form>";
  }
}

echo "<form method='post' action=''>
      <input type='hidden' name='tee' value='toimitusehdot'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='mista' value='$mista'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='lopetus' value='$tilmyy_lopetus'>
      <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
      <input type='hidden' name='projektilla' value='$projektilla'>
      <input type='hidden' name='orig_tila' value='$orig_tila'>
      <input type='hidden' name='orig_alatila' value='$orig_alatila'>
      <input type='submit' {$class["toimitusehdot"]} value='".t("Toimitusehdot")."'>
      </form>";

echo "<form method='post' action=''>
      <input type='hidden' name='tee' value='osamaksusoppari'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='mista' value='$mista'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='lopetus' value='$tilmyy_lopetus'>
      <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
      <input type='hidden' name='projektilla' value='$projektilla'>
      <input type='hidden' name='orig_tila' value='$orig_tila'>
      <input type='hidden' name='orig_alatila' value='$orig_alatila'>
      <input type='submit' {$class["osamaksusoppari"]} value='".t("Rahoituslaskelma")."'>
      </form>";

echo "<form method='post' action=''>
      <input type='hidden' name='tee' value='vakuutushakemus'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='mista' value='$mista'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='lopetus' value='$tilmyy_lopetus'>
      <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
      <input type='hidden' name='projektilla' value='$projektilla'>
      <input type='hidden' name='orig_tila' value='$orig_tila'>
      <input type='hidden' name='orig_alatila' value='$orig_alatila'>
      <input type='submit' {$class["vakuutushakemus"]} value='".t("Vakuutushakemus/Rekisteri-ilmoitus")."'>
      </form>";

echo "<br><br>";

// Tehd‰‰n rahoituslaskelma
if ($tee == 'osamaksusoppari') {
  require 'ajoneuvomyynti/osamaksusoppari.inc';
}

// Tehd‰‰n vakuutushakemus
if ($tee == 'vakuutushakemus') {
  require 'ajoneuvomyynti/vakuutushakemus.inc';
}

if ($tee == "MAKSUSOPIMUS") {
  require "tilauskasittely/maksusopimus.inc";
}

if ($tee == "ostajantiedot") {
  require "ajoneuvomyynti/ostajantiedot.inc";
}

if ($tee == "toimitusehdot") {
  require "ajoneuvomyynti/toimitusehdot.inc";
}

require("inc/footer.inc");
