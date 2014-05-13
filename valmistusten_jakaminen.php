<?php

require 'inc/parametrit.inc';
require 'valmistuslinjat.inc';
require 'valmistus.class.php';

$tee = (isset($tee)) ? $tee : '';

// Jaetaan valmistus ja sen valmisteet ja niiden raaka-aineet
if ($tee == "jaa_valmistus") {
  // Yritet‰‰n jakaa valmistus
  try {
    $kopion_id = jaa_valmistus($valmistus, $jaettavat_valmisteet);
  } catch (Exception $e) {
    $errors = "Virhe valmistuksen jakamisessa, ". $e->getMessage();
  }
}

// VIEW //
echo "<font class='head'>" . t("Valmistusten jakaminen") . "</font>";
echo "<hr>";

// Haetaan kaikki valmistukset
$valmistukset = Valmistus::all();

if (! $valmistukset) {
  echo t("Ei jaettavia valmistuksia");
}

// Loopataan valmistukset l‰pi
foreach($valmistukset as $valmistus) {
  // N‰ytet‰‰n vain ne valmistukset joilla on valmisteita ja ovat tilassa Odottaa valmistusta
  if (count($valmistus->tuotteet()) > 0 and in_array($valmistus->getTila(), array(Valmistus::ODOTTAA, Valmistus::KESKEYTETTY))) {
    echo "<table>";
    echo "<tr>
        <th>" . t("Valmistus") . "</th>
        <th>" . t("Tila") . "</th>
        <th>" . t("Nimitys") . "</th>
        <th>" . t("Viite") . "</th>
        <th>" . t("M‰‰r‰") . "</th>
        <th>" . t("Valmistettu") . "</th>
      </tr>";

    echo "<form method='POST'>";
    echo "<input type='hidden' name='tee' value='jaa_valmistus'>";
    echo "<input type='hidden' name='valmistus' value='" . $valmistus->tunnus() . "'>";

    // Loopataan valmistuksen valmisteet
    foreach ($valmistus->tuotteet() as $valmiste) {
      echo "<tr>";
      echo "<td>" . $valmistus->tunnus() . "</td>";
      echo "<td>" . $valmistus->getTila() . "</td>";
      echo "<td>" . $valmiste['nimitys'] . "</td>";
      echo "<td>" . $valmistus->viesti() . "</td>";
      echo "<td>" . $valmiste['varattu'] . $valmiste['yksikko'] . "</td>";
      echo "<td><input type='text' size='8' name='jaettavat_valmisteet[{$valmiste['tunnus']}]' value='" . $valmiste['varattu'] . "'></td>";
      echo "</tr>";
    }
    echo "<tr><td colspan='5'><input type='submit' value='" . t("Jaa") . "'></td></tr>";
    echo "</form>";
    echo "</table>";
  }
}

// Virheilmoitukset
if (!empty($errors))  {
  echo "<font class='error'>";
  echo t($errors);
  echo "</font>";
}

// FOOTER
require ("inc/footer.inc");
