<?php

require "../../inc/parametrit.inc";

// Mahdolliset ajot
$presta_ajot = array(
  'asiakasryhmat' => 'Asiakasryhmät',
  'asiakkaat'     => 'Asiakkaat',
  'kategoriat'    => 'Tuotekategoriat',
  'tuotteet'      => 'Tuotteet',
  'tuotekuvat'    => 'Tuotekuvat',
  'saldot'        => 'Tuotesaldot',
  'asiakashinnat' => 'Hinnastohinnat, asiakashinnat ja -alennukset',
  'tilaukset'     => 'Hae tilaukset',
);

$tee = empty($tee) ? '' : $tee;

echo "<font class='head'>".t("PrestaShop rajapinta")."</font><hr>";

echo "<form name='prestashop' method='post' action='{$palvelin2}/rajapinnat/presta/presta_kayttoliittyma.php'>";
echo "<input type='hidden' name='tee' value='aja'/>";

echo "<table>";
echo "<tbody>";

echo "<tr>";
echo "<th class='ptop'>";
echo t("Valitse ajo");
echo "</th>";
echo "<td>";

foreach ($presta_ajot as $ajo => $kuvaus) {
  echo "<label>";
  echo "<input type='checkbox' name='ajolista[]' value='{$ajo}'> {$kuvaus}";
  echo "</label>";
  echo "<br>";
}

echo "</td>";
echo "</tr>";

echo "</tbody>";
echo "</table>";

echo "<br>";
echo "<input type='submit' value='" . t('Aja') . "'>";

echo "</form>";

if ($tee == 'aja') {
  // Hyväksytään inputtina vaan $presta_ajot avaimia
  $sallitut  = array_keys($presta_ajot);
  $ajettavat = array_intersect($sallitut, $ajolista);
  $ajettavat = implode(',', $ajettavat);

  $komento = "{$pupe_root_polku}/rajapinnat/presta/presta_tuote_export.php '{$kukarow['yhtio']}' '{$ajettavat}'";

  exec("php {$komento} > /dev/null 2> /dev/null &");

  echo "<br><br>";
  echo "<font class='message'>Ajot aloitettu!</font>";
}
