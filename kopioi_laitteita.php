<?php

if (strpos($_SERVER['SCRIPT_NAME'], "kopioi_laitteita.php") !== FALSE) {
  require "inc/parametrit.inc";
}

echo "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
  <!--

  function toggleAll(toggleBox) {

    var currForm = toggleBox.form;
    var isChecked = toggleBox.checked;
    var nimi = toggleBox.name;
    for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
      if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,20) == nimi) {
        currForm.elements[elementIdx].checked = isChecked;
      }
    }
  }

  //-->
</script>";

if ($yhtiorow['laiterekisteri_kaytossa'] == '') die(t("Yhtiˆn parametrit - Laiterekisteri ei ole k‰ytˆss‰"));

$request = array(
  'tilausrivin_tunnus'   => isset($tilausrivin_tunnus) ? $tilausrivin_tunnus : '',
  'sopimusnumero'        => isset($sopimusnumero) ? $sopimusnumero : '',
  'toiminto'             => isset($toiminto) ? $toiminto : '',
  'kopioitavat_laitteet' => isset($kopioitavat_laitteet) ? $kopioitavat_laitteet : array(),
  'valitut_kohderivit'   => isset($valitut_kohderivit) ? $valitut_kohderivit : array(),
  'lopetus'              => isset($lopetus) ? $lopetus : ''
);

echo "<font class='head'>".t("Kopioi sopimusrivin %s laitteita", '', $tilausrivin_tunnus)."</font><hr>";

if ($request['toiminto'] == 'TEE' and count($request['kopioitavat_laitteet']) > 0 and count($request['valitut_kohderivit'] > 0)) {
  kopioi_laitteet_kohderiveille($request);
  $request['toiminto'] = 'KOPIOI';
  $request['kopioitavat_laitteet'] = array();
  $request['valitut_kohderivit'] = array();
}

echo_piirra_kopiointi_formi($request);
require "inc/footer.inc";

function echo_piirra_kopiointi_formi($request = array()) {
  echo "<form id='kopiointi_form'>";
  echo "<table>";
  echo "<tr><th>".t('Sopimusrivin laitteet')."</th><th>".t('Muut sopimusrivit ja niihin liitetyt laitteet')."</th></tr>";
  echo "<tr><td>";
  $kopioitavat_laitteet = hae_kopioitavat_laitteet($request['tilausrivin_tunnus']);
  echo_piirra_laiterivit($kopioitavat_laitteet);
  echo "</td>";
  echo "<td>";
  $kohdesopimusrivit = hae_kohdesopimusrivit($request['sopimusnumero']);
  echo_piirra_sopimusrivit($kohdesopimusrivit);
  echo "</td></tr>";
  echo "</table>";
  echo "<input type='hidden' name='tilausrivin_tunnus' value='{$request['tilausrivin_tunnus']}' />";
  echo "<input type='hidden' name='sopimusnumero' value='{$request['sopimusnumero']}' />";
  echo "<input type='hidden' name='lopetus' value='{$request['lopetus']}' />";
  echo "<input type='hidden' name='toiminto' value='TEE' />";
  echo "<input type='submit' value='".t('Kopioi valitut laitteet valituille riveille')."' />";
  echo "</form>";
}

function echo_piirra_laiterivit($kopioitavat_laitteet) {
  echo "<table>";
  echo "<tr><td>".t("Ruksaa kaikki")." <input type='checkbox' name='kopioitavat_laitteet' onclick='toggleAll(this);'></td></tr>";
  foreach ($kopioitavat_laitteet as $laite) {
    echo "<tr><td style='border: 1px solid black;'>";
    echo "<input type='checkbox' name='kopioitavat_laitteet[]'  value='{$laite['tunnus']}'/>";
    echo "{$laite['sarjanro']}<br>({$laite['tuotenro']})";
    echo "</td></tr>";
  }
  echo "</table>";
}

function echo_piirra_sopimusrivit($kohdesopimusrivit) {
  global $request;
  echo "<table>";
  foreach ($kohdesopimusrivit as $sopimusrivi) {
    if ($sopimusrivi['tunnus'] == $request['tilausrivin_tunnus']) continue;
    echo "<tr><td style='border: 1px solid black;'>";
    echo "<input type='checkbox' name='valitut_kohderivit[]'  value='{$sopimusrivi['tunnus']}'/>";
    echo "{$sopimusrivi['tuoteno']}";
    piirra_sopimusrivin_laitteet($sopimusrivi['tunnus']);
    echo "</td></tr>";
  }
  echo "</table>";
}

function piirra_sopimusrivin_laitteet($tilausrivin_tunnus) {
  echo "<br><br>";
  $query = "SELECT
            group_concat(laite.sarjanro SEPARATOR '<br>') sarjanumerot
            FROM laitteen_sopimukset
            JOIN laite ON laite.tunnus = laitteen_sopimukset.laitteen_tunnus
            WHERE laitteen_sopimukset.sopimusrivin_tunnus = '{$tilausrivin_tunnus}'
            ORDER BY laite.tunnus";
  $res = pupe_query($query);
  $sarjanumerotres = mysql_fetch_assoc($res);
  if (!empty($sarjanumerotres['sarjanumerot'])) {
    echo t("Sarjanumerot").": <br> {$sarjanumerotres['sarjanumerot']}";
  }
}

function hae_kopioitavat_laitteet($tilausrivin_tunnus) {
  global $kukarow;
  $laitteet = array();

  $query = "SELECT laite.*,
            laitteen_sopimukset.sopimusrivin_tunnus linkattu
            FROM laitteen_sopimukset
            JOIN laite
              ON (laite.yhtio = laitteen_sopimukset.yhtio
                AND laite.tunnus                        = laitteen_sopimukset.laitteen_tunnus)
            WHERE laitteen_sopimukset.yhtio             = '{$kukarow['yhtio']}'
            AND laitteen_sopimukset.sopimusrivin_tunnus = '{$tilausrivin_tunnus}'";
  $result = pupe_query($query);
  while ($row = mysql_fetch_assoc($result)) {
    $laitteet[] = array(
      'tunnus' => $row['tunnus'],
      'sarjanro' => $row['sarjanro'],
      'tuotenro' => $row['tuoteno'],
      'linkattu' => $row['linkattu']
    );
  }

  return $laitteet;
}

function hae_kohdesopimusrivit($sopimusnumero) {
  global $kukarow;
  $sopimusrivit = array();

  $query = "SELECT *
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$sopimusnumero}'";
  $result = pupe_query($query);
  while ($row = mysql_fetch_assoc($result)) {
    $sopimusrivit[] = array(
      'tuoteno' => $row['tuoteno'],
      'nimitys' => $row['nimitys'],
      'tunnus'  => $row['tunnus']
    );
  }

  return $sopimusrivit;
}

function kopioi_laitteet_kohderiveille($request) {
  foreach ($request['valitut_kohderivit'] as $sopimusrivi) {
    foreach ($request['kopioitavat_laitteet'] as $laite) {
      liita_laite_sopimusriviin($laite, $sopimusrivi);
    }
  }
  // P‰ivitet‰‰n viel‰ m‰‰r‰t oikein sopimuksen riveille
  $paivita_params = $request['valitut_kohderivit'];
  paivita_sopimusrivit($paivita_params);
}

function liita_laite_sopimusriviin($laite, $sopimusrivi) {
  global $kukarow;
  $ok = voidaanko_laite_liittaa_riviin($laite, $sopimusrivi);
  if ($ok) {
    $query = "INSERT INTO laitteen_sopimukset
              SET sopimusrivin_tunnus = '{$sopimusrivi}',
              laitteen_tunnus = '{$laite}',
              yhtio           = '{$kukarow['yhtio']}'";
    pupe_query($query);
  }
}

function voidaanko_laite_liittaa_riviin($laite, $sopimusrivi) {
  global $kukarow;
  $query = "SELECT *
            FROM laitteen_sopimukset
            WHERE yhtio             = '{$kukarow['yhtio']}'
            AND sopimusrivin_tunnus = '{$sopimusrivi}'
            AND laitteen_tunnus     = '{$laite}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return true;
  }
  return false;
}
