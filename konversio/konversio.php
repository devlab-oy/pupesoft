<?php

ini_set("memory_limit", "5G");
$compression=false;
require "../inc/parametrit.inc";
require_once 'VauriopoytakirjaCSVDumper.php';

$request = array(
  'action'       => $action,
  'konversio_tyyppi'   => $konversio_tyyppi,
  'kukarow'       => $kukarow
);

$request['konversio_tyypit'] = array(
  'vauriopoytakirja' => t('Vauriopoytakirja'),
);

if ($request['action'] == 'aja_konversio') {
  echo_kayttoliittyma($request);
  echo "<br/>";

  switch ($request['konversio_tyyppi']) {
  case 'vauriopoytakirja':
    $dumper = new VauriopoytakirjaCSVDumper($kukarow);
    break;

  default:
    die('Ei onnistu tämä');
    break;
  }

  $dumper->aja();
}
elseif ($request['action'] == 'poista_konversio_aineisto_kannasta') {
  $query_array = array(
    'DELETE FROM tyomaarays',
    'DELETE FROM lasku',
    'DELETE FROM laskun_lisatiedot',
    'DELETE FROM tilausrivi',
    'DELETE FROM tilausrivin_lisatiedot',
  );
  foreach ($query_array as $query) {
    pupe_query($query);
  }

  echo t('Poistettu');
  echo "<br/>";

  echo_kayttoliittyma($request);
}
else {
  echo_kayttoliittyma($request);
}

require 'inc/footer.inc';

function echo_kayttoliittyma($request) {
  global $kukarow, $yhtiorow;

  echo "<form action='' method='POST'>";
  echo "<input type='hidden' name='action' value='aja_konversio' />";
  echo "<table>";

  echo "<tr>";
  echo "<th>".t('Tiedosto')."</th>";
  echo "<td>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t('Konversio tyyppi')."</th>";
  echo "<td>";
  echo "<select name='konversio_tyyppi'>";
  foreach ($request['konversio_tyypit'] as $konversio_tyyppi => $selitys) {
    $sel = "";
    if ($request['konversio_tyyppi'] == $konversio_tyyppi) {
      $sel = "SELECTED";
    }
    echo "<option value='{$konversio_tyyppi}' {$sel}>{$selitys}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<input type='submit' value='".t('Lähetä')."' />";
  echo "</form>";

  echo "<form action='' method='POST'>";
  echo "<input type='hidden' name='action' value='poista_konversio_aineisto_kannasta' />";
  echo "<input type='submit' value='".t('Poista koko konversio aineisto')."' />";
  echo "</form>";
}
