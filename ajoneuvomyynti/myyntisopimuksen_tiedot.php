<?php

require "../inc/parametrit.inc";

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

echo "  <form method='post' action='{$palvelin2}tilauskasittely/tilaus_myynti.php'>
    <input type='hidden' name='toim' value='RIVISYOTTO'>
    <input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
    <input type='submit' value='".t("Takaisin tilaukselle")."'>
    </form>";

echo "<form method='post' action=''>
      <input type='hidden' name='tee' value='ostajantiedot'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='lopetus' value='$lopetus'>
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
    echo "<form method='post' action=''>
          <input type='hidden' name='tee' value='MAKSUSOPIMUS'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='submit' {$class["MAKSUSOPIMUS"]} value='".t("Maksusuunnitelma")."'>
          </form>";
  }
}

echo "<form method='post' action=''>
      <input type='hidden' name='tee' value='toimitusehdot'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='submit' {$class["toimitusehdot"]} value='".t("Toimitusehdot")."'>
      </form>";

echo "<form method='post' action=''>
      <input type='hidden' name='tee' value='osamaksusoppari'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='submit' {$class["osamaksusoppari"]} value='".t("Rahoituslaskelma")."'>
      </form>";

echo "<form method='post' action=''>
      <input type='hidden' name='tee' value='vakuutushakemus'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='submit' {$class["vakuutushakemus"]} value='".t("Vakuutustiedot")."'>
      </form>";

echo "<form method='post' action=''>
      <input type='hidden' name='tee' value='kaupankohde'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='submit' {$class["kaupankohde"]} value='".t("Kaupan kohde")."'>
      </form>";

 echo "<form method='post' action=''>
       <input type='hidden' name='tee' value='vaihtokohde'>
       <input type='hidden' name='tilausnumero' value='$tilausnumero'>
       <input type='hidden' name='lopetus' value='$lopetus'>
       <input type='submit' {$class["vaihtokohde"]} value='".t("Vaihtoajoneuvo")."'>
       </form>";

echo "<br><br>";

// Tehdään rahoituslaskelma
if ($tee == 'osamaksusoppari') {
  require 'ajoneuvomyynti/osamaksusoppari.inc';
}

// Tehdään vakuutushakemus
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

if ($tee == "kaupankohde" or $tee == "vaihtokohde") {
  require "ajoneuvomyynti/ajoneuvon_tiedot.inc";
}

js_openFormInNewWindow();

echo "<br><br><table>";
echo "<tr><th>".t("Näytä lomake").":</th>";

echo "<form name='valmis' action='{$palvelin2}tilauskasittely/tulostakopio.php' method='post' name='tulostaform_osamaksusoppari' id='tulostaform_osamaksusoppari' class='multisubmit'>
      <input type='hidden' name='tee' value='NAYTATILAUS'>
      <input type='hidden' name='otunnus' value='$tilausnumero'>";

echo "<td>";
echo "<select name='toim'>";
echo "<option value='MYYNTISOPIMUS'>Kauppasopimus</value>";
echo "<option value='OSAMAKSUSOPIMUS'>Osamaksusopimus</value>";
echo "<option value='LUOVUTUSTODISTUS'>Luovutustodistus</value>";
echo "<option value='VAKUUTUSHAKEMUS'>Vakuutushakemus</value>";
echo "<option value='REKISTERIILMOITUS'>Rekisteröinti-ilmoitus</value>";
echo "<option value='TARJOUS'>Tarjous</value>";
echo "</select></td>";
echo "<td><input type='submit' value='".t("Näytä")."' onClick=\"js_openFormInNewWindow('tulostaform_osamaksusoppari', 'tulosta_osamaksusoppari'); return false;\"></td>";
echo "</form></tr>";
echo "</table>";

require "inc/footer.inc";
