<?php

function hae_tyojono2_js() {
  global $palvelin2;

  ob_start();
  ?>
  <script src='<?php echo $palvelin2 ?>js/tyomaarays/tyojono2.js'></script>
  <script src='<?php echo $palvelin2 ?>js/valid_date_plugin.js'></script>
  <script>
    $(document).ready(function() {
      if ($('#tyojono_wrapper').length > 0) {
        $('#tyojono_wrapper').tyojonoPlugin();
        var tyojono_plugin = $('#tyojono_wrapper').data('tyojonoPlugin');

        tyojono_plugin.bind_all_events();

        $('.toimitettuaika').validDatePlugin();
        var validDatePlugin = $('.toimitettuaika').data('validDatePlugin');
        validDatePlugin.validate_date();
      }
    });
  </script>
  <?php
  $js = ob_get_clean();

  return $js;
}

function hae_tyojono2_css() {
  ob_start();
  ?>
  <style>
    .laite_table_tr_hidden {
      display: none;
    }

    .laite_table_tr {
      background-color: #DADDDE;
    }

    .laite_tr {
      background-color: #DADDDE;
    }
    #message_box_success {
      display:none;
    }
    #message_box_fail {
      display:none;
    }
    select[disabled] {
      background-color: gainsboro;
    }
  </style>
  <?php
  $js = ob_get_clean();

  return $js;
}

function hae_tyomaaraykset($request) {
  global $kukarow, $yhtiorow, $temp;

  $lasku_where = "";
  $kohde_join = "";
  if (!empty($request['lasku_tunnukset'])) {
    $lasku_where = "AND lasku.tunnus IN ('" . implode("','", $request['lasku_tunnukset']) . "')";
  }

  //tämä käytössä asiakaan_laite_hallinta.php
  if (!empty($request['liitostunnus'])) {
    $lasku_where .= " AND lasku.liitostunnus = '{$request['liitostunnus']}'";
  }

  if ($request['toim'] == 'TEHDYT_TYOT') {
    $lasku_where .= " AND lasku.tila = 'L'
                      AND lasku.alatila = 'D'";
    $lasku_order = " tilausrivi.toimitettuaika DESC,";
  }
  else {
    $lasku_where .= " AND lasku.tila = 'A'";
    $lasku_order = " lasku.toimaika ASC,";
  }

  if (!empty($request['asiakas_tunnus'])) {
    $lasku_where .= " AND lasku.liitostunnus = '{$request['asiakas_tunnus']}'";
  }

  if (!empty($request['kohde_tunnus'])) {
    $kohde_join .= " AND kohde.tunnus = '{$request['kohde_tunnus']}'";
  }

  if (!empty($request['toimitusaika'])) {
    if (is_numeric($request['toimitusaika'])) {
      if ($request['toim'] == 'TEHDYT_TYOT') {
        $lasku_where .= " AND lasku.toimaika > DATE_SUB(CURRENT_DATE, INTERVAL {$request['toimitusaika']} DAY)
                          AND lasku.toimaika <= CURRENT_DATE";
      }
      else {
        $lasku_where .= " AND lasku.toimaika < DATE_ADD(CURRENT_DATE, INTERVAL {$request['toimitusaika']} DAY)
                          AND lasku.toimaika > DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)";
      }
    }
    else if ($request['toimitusaika'] == 'X') {
      //Näytä myöhässä olevat
      $lasku_where .= " AND lasku.toimaika < DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)";
    }
  }

  $tyomaarays_join = "";
  if (!empty($request['tyojono']) and $request['tyojono'] != 'KAIKKI') {
    $tyomaarays_join .= " AND tyomaarays.tyojono = '{$request['tyojono']}'";
  }
  else if (!empty($request['tyojono']) and $request['tyojono'] == 'EIJONOSSA') {
    $tyomaarays_join .= " AND tyomaarays.tyojono = ''";
  }

  if (!empty($request['tyostatus']) and $request['tyostatus'] != 'KAIKKI') {
    $tyomaarays_join .= " AND tyomaarays.tyostatus = '{$request['tyostatus']}'";
  }
  else if (!empty($request['tyostatus']) and $request['tyostatus'] == 'EISTATUSTA') {
    $tyomaarays_join .= " AND tyomaarays.tyostatus = ''";
  }

  if ($temp === 1) {
    $tmp = "_temp";
  }
  else {
    $tmp = "";
  }

  $query = "SELECT
            lasku.tunnus AS lasku_tunnus,
            lasku.ytunnus AS asiakas_ytunnus,
            lasku.nimi AS asiakas_nimi,
            kohde.tunnus AS kohde_tunnus,
            kohde.nimi AS kohde_nimi,
            kohde.asiakas AS asiakas_tunnus,
            paikka.nimi AS paikka_nimi,
            a3.selitetark AS paikka_olosuhde,
            laite.tuoteno,
            laite.oma_numero,
            laite.sarjanro AS sarjanumero,
            laite.sijainti AS laite_sijainti,
            laite.tunnus AS laite_tunnus,
            tuote.nimitys AS tuote_nimi,
            lasku.toimaika AS toimaika,
            a1.selite AS tyojonokoodi,
            a1.selitetark AS tyojono,
            a2.selite AS tyostatus_koodi,
            a2.selitetark AS tyostatus,
            a2.selitetark_2 AS tyostatusvari,
            tilausrivi.tunnus AS tilausrivi_tunnus,
            tilausrivi.nimitys AS tehtava_tyo,
            tilausrivi.kommentti AS tilausrivi_kommentti,
            tilausrivi.toimitettuaika,
            tilausrivin_lisatiedot.tilausrivilinkki AS poikkeus_tunnus,
            IF(tilausrivin_lisatiedot.tilausrivilinkki != 0, CONCAT_WS(' -> ', poikkeus_rivi.nimitys, tilausrivi.nimitys), '') AS poikkeus_teksti,
            poistettu_laite.tuoteno AS poistettu_laite
            FROM lasku{$tmp} AS lasku
            JOIN tyomaarays{$tmp} AS tyomaarays
            ON ( tyomaarays.yhtio = lasku.yhtio
              AND tyomaarays.otunnus = lasku.tunnus
              {$tyomaarays_join} )
            JOIN tilausrivi{$tmp} AS tilausrivi
            ON ( tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
              AND tilausrivi.var != 'P' )
            JOIN tilausrivin_lisatiedot{$tmp} AS tilausrivin_lisatiedot
            ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
            LEFT JOIN tilausrivi AS poikkeus_rivi
            ON ( poikkeus_rivi.yhtio = tilausrivin_lisatiedot.yhtio
              AND poikkeus_rivi.tunnus = tilausrivin_lisatiedot.tilausrivilinkki )
            LEFT JOIN tilausrivin_lisatiedot AS poikkeus_toimenpide_tilausrivi_lisatiedot
            ON ( poikkeus_toimenpide_tilausrivi_lisatiedot.yhtio = poikkeus_rivi.yhtio
              AND poikkeus_toimenpide_tilausrivi_lisatiedot.tilausrivitunnus = poikkeus_rivi.tunnus )
            LEFT JOIN laite AS poistettu_laite
            ON ( poistettu_laite.yhtio = poikkeus_toimenpide_tilausrivi_lisatiedot.yhtio
              AND poistettu_laite.tunnus = poikkeus_toimenpide_tilausrivi_lisatiedot.asiakkaan_positio )
            JOIN laite
            ON ( laite.yhtio = tilausrivin_lisatiedot.yhtio
              AND laite.tunnus = tilausrivin_lisatiedot.asiakkaan_positio
              AND laite.tila != 'P' )
            JOIN tuote
            ON ( tuote.yhtio = laite.yhtio
              AND tuote.tuoteno = laite.tuoteno )
            JOIN paikka
            ON ( paikka.yhtio = laite.yhtio
              AND paikka.tunnus = laite.paikka )
            JOIN avainsana a3
            ON ( a3.yhtio = paikka.yhtio
              AND a3.selite = paikka.olosuhde
              AND a3.laji = 'OLOSUHDE')
            JOIN kohde
            ON ( kohde.yhtio = paikka.yhtio
              AND kohde.tunnus = paikka.kohde
              {$kohde_join})
            LEFT JOIN avainsana a1
            ON ( a1.yhtio = tyomaarays.yhtio
              AND a1.laji = 'TYOM_TYOJONO'
              AND a1.selite = tyomaarays.tyojono )
            LEFT JOIN avainsana a2
            ON ( a2.yhtio = tyomaarays.yhtio
              AND a2.laji = 'TYOM_TYOSTATUS'
              AND a2.selite = tyomaarays.tyostatus )
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            {$lasku_where}
            ORDER BY {$lasku_order}
            kohde.tunnus ASC,
            laite.oma_numero ASC";
  $result = pupe_query($query);

  $tyomaaraykset = array();
  while ($tyomaarays = mysql_fetch_assoc($result)) {
    $tyomaaraykset[] = $tyomaarays;
  }

  return $tyomaaraykset;
}

function kasittele_tyomaaraykset($request) {
  global $kukarow, $yhtiorow;

  $tyomaaraykset_temp = array();
  $kohteiden_toimajat = array();
  foreach ($request['tyomaaraykset'] as $tyomaarays) {
    $key = 'toimaika';
    if ($tyomaarays['tyostatus_koodi'] == 'X') {
      $key = 'toimitettuaika';
    }

    if (empty($kohteiden_toimajat[$tyomaarays['kohde_tunnus']])) {
      $kohteiden_toimajat[$tyomaarays['kohde_tunnus']][] = date('Y-m-d', strtotime($tyomaarays[$key]));
    }

    if (!empty($tyomaarays['poistettu_laite'])) {
      $tyomaarays['poikkeus_teksti'] .= '<br/>' . $tyomaarays['poistettu_laite'] . ' -> ' . $tyomaarays['tuoteno'];
    }

    //Kohteen toimitusajoista viimeisin sinne insertöity toimaika
    $toimajat = array_values($kohteiden_toimajat[$tyomaarays['kohde_tunnus']]);
    $kohteen_viimeisin_toimaika = end($toimajat);
    $paiva_temp = date('Y-m-d', strtotime("{$kohteen_viimeisin_toimaika} + 30 days"));
    //Jos käsittelyssä oleva työmääräyksen toimaika on 30 päivän sisällä ensimmäisestä työmääräyksen toimajasta niin groupataan
    if ($tyomaarays['tyostatus_koodi'] == 'X') {
      if ($paiva_temp <= $tyomaarays[$key]) {
        $toimaika = $kohteen_viimeisin_toimaika;
      }
      else {
        $toimaika = $tyomaarays[$key];
        $kohteiden_toimajat[$tyomaarays['kohde_tunnus']][] = $tyomaarays[$key];
      }
    }
    else {
      if ($paiva_temp >= $tyomaarays[$key]) {
        $toimaika = $kohteen_viimeisin_toimaika;
      }
      else {
        $toimaika = $tyomaarays[$key];
        $kohteiden_toimajat[$tyomaarays['kohde_tunnus']][] = $tyomaarays[$key];
      }
    }

    if (!isset($tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']])) {
      $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']] = array(
          'asiakas_ytunnus' => $tyomaarays['asiakas_ytunnus'],
          'asiakas_nimi'    => $tyomaarays['asiakas_nimi'],
          'kohde_tunnus'    => $tyomaarays['kohde_tunnus'],
          'kohde_nimi'      => $tyomaarays['kohde_nimi'],
      );
    }

    $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['toimajat'][] = $tyomaarays[$key];

    //jos kohteen kaikilla työmääräyksillä on sama työstatus, asetetaan se myös kohteelle, jotta se voidaan näyttää ylemmällä tasolla taulukko näkymässä
    if (isset($tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyostatus'])) {
      if ($tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyostatus'] != $tyomaarays['tyostatus']) {
        $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyostatus'] = '';
        $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyostatus_koodi'] = '';
        $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyostatusvari'] = '';
      }
      else {
        $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyostatus'] = $tyomaarays['tyostatus'];
        $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyostatus_koodi'] = $tyomaarays['tyostatus_koodi'];
        $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyostatusvari'] = $tyomaarays['tyostatusvari'];
      }
    }
    else {
      $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyostatus'] = $tyomaarays['tyostatus'];
      $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyostatus_koodi'] = $tyomaarays['tyostatus_koodi'];
      $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyostatusvari'] = $tyomaarays['tyostatusvari'];
    }

    //jos kohteen kaikilla työmääräyksillä on sama työjono, asetetaan se myös kohteelle, jotta se voidaan näyttää ylemmällä tasolla taulukko näkymässä
    if (isset($tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyojonokoodi'])) {
      if ($tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyojonokoodi'] != $tyomaarays['tyojonokoodi']) {
        $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyojonokoodi'] = '';
        $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyojono'] = '';
      }
      else {
        $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyojonokoodi'] = $tyomaarays['tyojonokoodi'];
        $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyojono'] = $tyomaarays['tyojono'];
      }
    }
    else {
      $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyojonokoodi'] = $tyomaarays['tyojonokoodi'];
      $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyojono'] = $tyomaarays['tyojono'];
    }

    $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['lasku_tunnukset'][] = $tyomaarays['lasku_tunnus'];
    $tyomaaraykset_temp[$toimaika][$tyomaarays['kohde_tunnus']]['tyomaaraykset'][] = $tyomaarays;
  }

  return $tyomaaraykset_temp;
}

function echo_tyojono_kayttoliittyma($request) {

  if ($request['toim'] == 'TEHDYT_TYOT') {
    $toimitusaika_haku_array = array(
        '' => t('Näytä kaikki'),
        7  => t('1 viikko sitten tehdyt'),
        14 => t('2 viikkoa sitten tehdyt'),
        21 => t('3 viikkoa sitten tehdyt'),
        28 => t('4 viikkoa sitten tehdyt'),
    );
  }
  else {
    $toimitusaika_haku_array = array(
        ''  => t('Näytä kaikki'),
        'X' => t('Näytä myöhässä olevat'),
        7   => t('1 viikon päästä erääntyvät'),
        14  => t('2 viikon päästä erääntyvät'),
        21  => t('3 viikon päästä erääntyvät'),
        28  => t('4 viikon päästä erääntyvät'),
    );
  }

  echo "<form method='POST' action='' name='tyojono_haku'>";
  echo "<input type='hidden' name='ala_tee' value='hae' />";
  echo "<table>";

  echo "<tr>";
  echo "<th>" . t('Asiakas') . "</th>";
  echo "<td>";
  echo livesearch_kentta('tyojono_haku', 'ASIAKASHAKU', 'asiakas_tunnus', 300, $request['asiakas_tunnus'], 'NOAUTOSUBMIT');
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t('Kohde') . "</th>";
  echo "<td>";
  echo livesearch_kentta('tyojono_haku', 'KOHDEHAKU', 'kohde_tunnus', 300, $request['kohde_tunnus'], 'NOAUTOSUBMIT');
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t('Toimitus') . "</th>";
  echo "<td>";
  echo "<select id='toimitusaika_select' name='toimitusaika'>";
  foreach ($toimitusaika_haku_array as $toimitusaika_value => $toimitusaika) {
    $sel = "";
    if ($toimitusaika_value == $request['toimitusaika']) {
      $sel = "SELECTED";
    }
    echo "<option value='{$toimitusaika_value}' {$sel}>{$toimitusaika}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t('Työjono') . "</th>";
  echo "<td>";
  echo "<select name='tyojono' class='tyojono_sort'>";
  foreach ($request['tyojonot'] as $tyojono) {
    $sel = "";
    if ($tyojono['value'] == $request['tyojono']) {
      $sel = "SELECTED";
    }
    echo "<option value='{$tyojono['value']}' {$sel}>{$tyojono['text']}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t('Työstatus') . "</th>";
  echo "<td>";
  echo "<select name='tyostatus' class='tyostatus_sort'>";
  foreach ($request['tyostatukset'] as $tyostatus) {
    $sel = "";
    if ($tyostatus['value'] == $request['tyostatus']) {
      $sel = "SELECTED";
    }
    echo "<option value='{$tyostatus['value']}' {$sel}>{$tyostatus['text']}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<input type='submit' value='" . t('Hae') . "' />";
  echo "</form>";

  echo "<form method='POST' action='' name='tyojono_haku'>";
  echo "<input type='hidden' name='ala_tee' value='tyhjenna_hakuehdot' />";
  echo "<input type='submit' value='" . t('Tyhjennä') . "' />";
  echo "</form>";
}

function echo_tyomaaraykset_table($request = array()) {
  global $kukarow, $yhtiorow, $palvelin2;

  echo "<table>";
  echo "<thead>";

  $toimitus_string = "Toimitetaan";
  if ($request['toim'] == 'TEHDYT_TYOT') {
    $toimitus_string = "Toimitettu";
  }
  echo "<tr>";
  echo "<th>" . t("Kpl") . "</th>";
  echo "<th>" . t("Ytunnus") . "<br>" . t("Asiakas") . "</th>";
  echo "<th>" . t("Kohde") . "</th>";
  echo "<th>" . t($toimitus_string) . "</th>";
  echo "<th>" . t("Työjono") . "/<br>" . t("Työstatus") . "</th>";
  echo "<th>";
  if (!empty($request['tyomaaraykset']) and $request['toim'] != 'TEHDYT_TYOT') {
    echo "<form class='multisubmit' name='tulosta_tyolistat' method='POST'>";
    echo "<input type='hidden' name='ala_tee' value='tulosta_tyolista' />";
    foreach ($request['tyomaaraykset'] as $tyomaarays_per_paiva) {
      foreach ($tyomaarays_per_paiva as $tyomaarays) {
        echo '<input type="hidden" name="lasku_tunnukset[]" value="' . implode(',', $tyomaarays['lasku_tunnukset']) . '">';
      }
    }
    echo "<input type='submit' value='" . t("Tallenna tyolistat PDF") . "' />";
    echo "</form>";
  }
  echo "</th>";

  echo "</tr>";

  echo "</thead>";

  echo "<tbody>";

  foreach ($request['tyomaaraykset'] as $tyomaarays_per_kohde) {
    foreach ($tyomaarays_per_kohde as $tyomaarays) {
      echo "<tr class='kohde_tr_hidden aktiivi'>";
      echo "<td>";
      echo count($tyomaarays['lasku_tunnukset']);
      echo "</td>";

      echo "<td>";
      echo $tyomaarays['asiakas_ytunnus'] . '<br/>' . $tyomaarays['asiakas_nimi'];
      echo "</td>";

      echo "<td>";
      echo $tyomaarays['kohde_nimi'];
      echo "</td>";

      echo "<td>";
      $unique_values = array_unique($tyomaarays['toimajat']);
      $fn = function($values) {
                $return = array();
                foreach ($values as $value) {
                  $return[] = date('d.m.Y', strtotime($value));
                }
                return $return;
              };
      echo implode('<br/>', $fn($unique_values));
      echo "</td>";

      if ($request['toim'] == 'TEHDYT_TYOT') {
        echo "<td>";
        echo $tyomaarays['tyojono'];
        echo "<br/>";
        echo $tyomaarays['tyostatus'];
        echo "</td>";

        echo "<td>";
        echo "<form class='multisubmit' name='tulosta_tarkastuspoytakirja'>";
        echo "<input type='hidden' name='ala_tee' value='tulosta_tarkastuspoytakirja' />";
        echo "<input type='hidden' name='toim' value='{$request['toim']}' />";
        echo "<input type='hidden' name='lasku_tunnukset' value='" . implode(',', array_unique($tyomaarays['lasku_tunnukset'])) . "' />";
        echo "<input type='submit' value='" . t("Tallenna tarkastuspyötäkirja PDF") . "' />";
        echo "</form>";
        echo "<br/>";
        echo "<form class='multisubmit' name='tulosta_laskutuspoytakirja'>";
        echo "<input type='hidden' name='ala_tee' value='tulosta_laskutuspoytakirja' />";
        echo "<input type='hidden' name='toim' value='{$request['toim']}' />";
        echo "<input type='hidden' name='lasku_tunnukset' value='" . implode(',', array_unique($tyomaarays['lasku_tunnukset'])) . "' />";
        echo "<input type='submit' value='" . t("Tallenna laskutuspyötäkirja PDF") . "' />";
        echo "</form>";
        echo "</td>";
      }
      else {
        if (!empty($tyomaarays["tyostatusvari"])) {
          $td_taustavari = "style='background-color: {$tyomaarays['tyostatusvari']};'";
        }
        else {
          $td_taustavari = "";
        }
        echo "<td {$td_taustavari}>";

        echo "<select class='tyojono_muutos_kohde'>";
        foreach ($request['tyojonot'] as $tyojono) {
          $sel = $tyomaarays['tyojonokoodi'] == $tyojono['value'] ? ' SELECTED' : '';
          echo "<option value='{$tyojono['value']}' {$sel}>{$tyojono['text']}</option>";
        }
        echo "</select>";
        echo "<br/>";
        echo "<select class='tyostatus_muutos' disabled='disabled'>";
        foreach ($request['tyostatukset'] as $tyostatus) {
          $sel = $tyomaarays['tyostatus_koodi'] == $tyostatus['value'] ? ' SELECTED' : '';
          echo "<option value='{$tyostatus['value']}' {$sel}>{$tyostatus['text']}</option>";
        }
        echo "</select>";

        echo "</td>";

        echo "<td>";
        echo "<form class='multisubmit' name='tulosta_tyolista'>";
        echo "<input type='hidden' name='ala_tee' value='tulosta_tyolista' />";
        echo "<input type='hidden' class='lasku_tunnukset' name='lasku_tunnukset' value='" . implode(',', $tyomaarays['lasku_tunnukset']) . "' />";
        echo "<input type='submit' value='" . t("Tallenna työlista PDF") . "' />";
        echo "</form>";
        echo "</td>";
      }

      echo "</tr>";

      echo_tyomaarays_rivi($request, $tyomaarays['tyomaaraykset']);
    }
  }

  if (empty($request['tyomaaraykset'])) {
    echo "<tr>";
    echo "<td colspan = '6' align='center'>" . t('Ei työmääräyksiä') . "</td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";
}

function echo_tyomaarays_rivi($request, $laitteet) {
  global $kukarow, $yhtiorow, $palvelin2;

  $today = date('d.m.Y');
  $lopetus = "{$palvelin2}tyomaarays/tyojono2.php////tee=hae//asiakas_tunnus={$request['asiakas_tunnus']}//kohde_tunnus={$request['kohde_tunnus']}//tyojono={$request['tyojono']}//tyostatus={$request['tyostatus']}//toimitusaika={$request['toimitusaika']}";

  echo "<tr class='laite_table_tr_hidden'>";
  echo "<form class='multisubmit tyomaarayshallinta' name='tyomaarayshallinta'>";
  echo "<input type='hidden' name='ala_tee' value='merkkaa_tehdyksi' />";

  echo "<td colspan='1'>";
  echo "</td>";
  echo "<td colspan='4'>";

  echo "<table class='laite_table'>";
  echo "<tr>";
  echo "<th>";
  echo t("#");
  $toimitus_string = "Toimitettu";
  if ($request['toim'] != 'TEHDYT_TYOT') {
    $toimitus_string = "Toimitetaan";
    echo "<br/>";
    echo "<input type='checkbox' class='select_kaikki' />";
  }
  echo "</th>";
  echo "<th>" . t("Oma numero") . "</th>";
  echo "<th>" . t("Sarjanumero") . "</th>";
  echo "<th>" . t("Laite") . "</th>";
  echo "<th>" . t("Paikka") . "</th>";
  echo "<th>" . t("Sijainti") . "</th>";
  echo "<th>" . t("Tehtävä työ") . "</th>";
  echo "<th>" . t($toimitus_string) . "</th>";
  echo "<th>" . t("Kommentti") . "</th>";
  echo "<th>" . t("Poikkeus") . "</th>";
  echo "<th>" . t("Työjono") . "/<br>" . t("Työstatus") . "</th>";
  echo "<th>" . t("Poikkeama") . "</th>";
  echo "</tr>";

  foreach ($laitteet as $laite) {
    echo "<tr class='laite_tr'>";

    echo "<td>";
    if ($request['toim'] != 'TEHDYT_TYOT') {
      echo "<input type='checkbox' class='laite_checkbox' />";
    }
    echo "<input type='hidden' class='lasku_tunnus' value='{$laite['lasku_tunnus']}' />";
    echo "</td>";

    echo "<td>";
    echo $laite['oma_numero'];
    echo "</td>";

    echo "<td>";
    if ($request['toim'] != 'TEHDYT_TYOT') {
      echo "<a href='{$palvelin2}yllapito.php?toim=laite&asiakas_tunnus={$laite['asiakas_tunnus']}&lopetus={$lopetus}&tunnus={$laite['laite_tunnus']}'>";
      echo $laite['sarjanumero'];
      echo "</a>";
    }
    else {
      echo $laite['sarjanumero'];
    }
    echo "</td>";

    echo "<td>";
    echo $laite['tuote_nimi'];
    echo "</td>";

    echo "<td>";
    echo $laite['paikka_nimi'];
    echo "</td>";

    echo "<td>";
    echo $laite['laite_sijainti'];
    echo "</td>";

    echo "<td>";
    echo $laite['tehtava_tyo'];
    echo "</td>";

    echo "<td>";
    echo date('d.m.Y', strtotime($laite['toimaika']));
    echo "</td>";

    echo "<td>";
    $kommentti = wordwrap($laite['tilausrivi_kommentti'], 40, '<br/>');
    echo '<font class="error">' . $kommentti . '</font>';
    echo "</td>";

    echo "<td>";
    echo "<font class='error'>{$laite['poikkeus_teksti']}</font>";
    echo "</td>";

    if ($request['toim'] == 'TEHDYT_TYOT') {
      if (empty($laite['tyojono'])) {
        $tyojono = t('Tekijä tuntematon');
      }
      else {
        $tyojono = $laite['tyojono'];
      }
      echo "<td>";
      echo $tyojono;
      echo "<br/>";
      echo $laite['tyostatus'];
      echo "</td>";

      echo "<td>";
      if ($laite['poikkeus_tunnus']) {
        echo "<font class='error'>" . t("Poikkeus") . "</font>";
        echo "<br/>";
        echo "<form class='multisubmit' name='tulosta_poikkeamaraportti' method='POST'>";
        echo "<input type='hidden' name='toim' value='{$request['toim']}' />";
        echo "<input type='hidden' name='ala_tee' value='tulosta_poikkeamaraportti' />";
        echo "<input type='hidden' name='lasku_tunnukset' value='{$laite['lasku_tunnus']}' />";
        echo "<input type='submit' value='" . t('Tulosta poikkeamaraportti') . "' />";
        echo "</form>";
      }
      echo "</td>";
    }
    else {
      if (!empty($laite["tyostatusvari"])) {
        $td_taustavari = "style='background-color: {$laite['tyostatusvari']};'";
      }
      else {
        $td_taustavari = "";
      }
      echo "<td {$td_taustavari}>";

      echo "<select class='tyojono_muutos_lasku'>";
      foreach ($request['tyojonot'] as $tyojono) {
        $sel = $laite['tyojonokoodi'] == $tyojono['value'] ? ' SELECTED' : '';
        echo "<option value='{$tyojono['value']}' {$sel}>{$tyojono['text']}</option>";
      }
      echo "</select>";
      echo "<br/>";
      echo "<select class='tyostatus_muutos' disabled='disabled'>";
      foreach ($request['tyostatukset'] as $tyostatus) {
        $sel = $laite['tyostatus_koodi'] == $tyostatus['value'] ? ' SELECTED' : '';
        echo "<option value='{$tyostatus['value']}' {$sel}>{$tyostatus['text']}</option>";
      }
      echo "</select>";

      echo "</td>";

      echo "<td>";
      echo "<a href='{$palvelin2}tilauskasittely/laitteen_vaihto.php?tilausrivi_tunnus={$laite['tilausrivi_tunnus']}&ala_tee=laitteen_vaihto&lopetus={$lopetus}'><button type='button'>" . t("Laitteen vaihto") . "</button></a>";
      echo "<br/>";
      echo "<a href='{$palvelin2}tilauskasittely/tilaus_myynti.php?toim=TYOMAARAYS&tilausnumero={$laite['lasku_tunnus']}&laite_tunnus={$laite['laite_tunnus']}&lopetus={$lopetus}'><button type='button'>" . t("Muu") . "</button></a>";
      echo "<br/>";
      //Vaikka lasku_tunnukset annetaan array:nä niin tarkoitus ei ole antaa ikinä enempää kuin yksi lasku_tunnus tämän linkin kautta
      echo "<a href='{$palvelin2}tyomaarays/tyojono2.php?toim={$request['toim']}&ala_tee=merkkaa_kadonneeksi&lasku_tunnukset[]={$laite['lasku_tunnus']}&laite_tunnus={$laite['laite_tunnus']}'><button type='button'>" . t("Kateissa") . "</button></a>";
      echo "</td>";
    }

    echo "</tr>";
  }

  echo "</table>";
  echo "</td>";

  echo "<td>";
  if ($request['toim'] != 'TEHDYT_TYOT') {
    echo "<input type='text' class='toimitettuaika' name='toimitettuaika' value='{$today}' />";
    echo "<br/>";
    echo "<button class='merkkaa_tehdyksi' type='button'>" . t('Merkkaa tehdyksi') . "</button>";
    echo "<br/>";
    echo "<button class='muuta' type='button'>" . t('Muuta päivämäärää') . "</button>";
    echo "<br/>";
    echo "<button class='poista' type='button'>" . t('Poista') . "</button>";
    echo "<div class='error_element'></div>";
  }
  echo "</td>";

  echo "</form>";
  echo "</tr>";
}

function merkkaa_laite_kadonneeksi($request) {
  global $kukarow, $yhtiorow;

  //K = Kateissa
  aseta_tyomaaraysten_status($request['lasku_tunnukset'], 'K');
  aseta_laitteen_tila($request['laite_tunnus'], 'K');
}

function hae_tyojonot() {
  global $kukarow, $yhtiorow;

  $tyojono_result = t_avainsana("TYOM_TYOJONO");
  $tyojonot = array();
  $tyojonot[] = array(
      'value' => 'KAIKKI',
      'text'  => t('Kaikki'),
  );
  $tyojonot[] = array(
      'value' => 'EIJONOSSA',
      'text'  => t('Ei jonossa'),
  );
  while ($tyojono_row = mysql_fetch_assoc($tyojono_result)) {
    $tyojono_row['value'] = $tyojono_row['selite'];
    $tyojono_row['text'] = $tyojono_row['selitetark'];
    $tyojonot[] = $tyojono_row;
  }

  return $tyojonot;
}

function hae_tyostatukset() {
  global $kukarow, $yhtiorow;

  $tyostatus_result = t_avainsana("TYOM_TYOSTATUS");
  $tyostatukset = array();
  $tyostatukset[] = array(
      'value' => 'KAIKKI',
      'text'  => t('Kaikki'),
  );
  $tyostatukset[] = array(
      'value' => 'EISTATUSTA',
      'text'  => t('Ei statusta'),
  );
  while ($tyostatus_row = mysql_fetch_assoc($tyostatus_result)) {
    $tyostatus_row['value'] = $tyostatus_row['selite'];
    $tyostatus_row['text'] = $tyostatus_row['selitetark'];
    $tyostatukset[] = $tyostatus_row;
  }

  return $tyostatukset;
}

function hae_laitteen_viimeiset_ja_seuraavat_tapahtumat($laite_tunnus) {
  global $kukarow, $yhtiorow;

  $huoltovalit = huoltovali_options();

  $query = "SELECT huoltosyklit_laitteet.viimeinen_tapahtuma,
            huoltosyklit_laitteet.huoltovali,
            tuotteen_avainsanat.selite AS tapahtuman_tyyppi
            FROM huoltosyklit_laitteet
            JOIN huoltosykli
            ON ( huoltosykli.yhtio = huoltosyklit_laitteet.yhtio
              AND huoltosykli.tunnus = huoltosyklit_laitteet.huoltosykli_tunnus )
            JOIN tuote as toimenpide_tuote
            ON ( toimenpide_tuote.yhtio = huoltosykli.yhtio
              AND toimenpide_tuote.tuoteno = huoltosykli.toimenpide )
            JOIN tuotteen_avainsanat
            ON ( tuotteen_avainsanat.yhtio = toimenpide_tuote.yhtio
              AND tuotteen_avainsanat.tuoteno = toimenpide_tuote.tuoteno
              AND tuotteen_avainsanat.laji = 'tyomaarayksen_ryhmittely')
            WHERE huoltosyklit_laitteet.yhtio = '{$kukarow['yhtio']}'
            AND huoltosyklit_laitteet.laite_tunnus = '{$laite_tunnus}'
            ORDER BY tuotteen_avainsanat.selitetark DESC";
  $result = pupe_query($query);

  $viimeiset_tapahtumat = array();
  while ($viimeinen_tapahtuma = mysql_fetch_assoc($result)) {
    $huoltovali = $huoltovalit[$viimeinen_tapahtuma['huoltovali']];
    $seuraava_tapahtuma = date('Y-m-d', strtotime("{$viimeinen_tapahtuma['viimeinen_tapahtuma']} + {$huoltovali['years']} years"));
    $viimeiset_tapahtumat[$viimeinen_tapahtuma['tapahtuman_tyyppi']]['viimeinen_tapahtuma'] = $viimeinen_tapahtuma['viimeinen_tapahtuma'];
    $viimeiset_tapahtumat[$viimeinen_tapahtuma['tapahtuman_tyyppi']]['seuraava_tapahtuma'] = $seuraava_tapahtuma;
  }

  return $viimeiset_tapahtumat;
}

function onko_laitteella_poikkeus($laite_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "SELECT *
            FROM tilausrivin_lisatiedot
            JOIN tilausrivi as poikkeus_rivi
            ON ( poikkeus_rivi.yhtio = tilausrivin_lisatiedot.yhtio
              AND poikkeus_rivi.tunnus = tilausrivin_lisatiedot.tilausrivilinkki )
            WHERE tilausrivin_lisatiedot.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivin_lisatiedot.asiakkaan_positio = '{$laite_tunnus}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    return true;
  }

  return false;
}

function tyhjenna_sessio() {
  global $kukarow, $yhtiorow;

  unset($_SESSION['tyojono_hakuehdot']);
}

function tallenna_haut_sessioon($request) {
  global $kukarow, $yhtiorow;

  $_SESSION['tyojono_hakuehdot']['asiakas_tunnus'] = $request['asiakas_tunnus'];
  $_SESSION['tyojono_hakuehdot']['kohde_tunnus'] = $request['kohde_tunnus'];
  $_SESSION['tyojono_hakuehdot']['tyojono'] = $request['tyojono'];
  $_SESSION['tyojono_hakuehdot']['tyostatus'] = $request['tyostatus'];
  $_SESSION['tyojono_hakuehdot']['toimitusaika'] = $request['toimitusaika'];
}

function aseta_hakuehdot(&$request) {
  global $kukarow, $yhtiorow;

  $request['asiakas_tunnus'] = $_SESSION['tyojono_hakuehdot']['asiakas_tunnus'];
  $request['kohde_tunnus'] = $_SESSION['tyojono_hakuehdot']['kohde_tunnus'];
  $request['tyojono'] = $_SESSION['tyojono_hakuehdot']['tyojono'];
  $request['tyostatus'] = $_SESSION['tyojono_hakuehdot']['tyostatus'];
  $request['toimitusaika'] = $_SESSION['tyojono_hakuehdot']['toimitusaika'];
}

function kasittele_laskutunnukset($lasku_tunnukset) {
  global $kukarow, $yhtiorow;

  //requestista voi tulla lasku_tunnukset, joko stringinä tai arraynä
  //Jos se tulee arraynä niin arrayn solu voi pitää sisällään joko yhden tai useamman lasku_tunnuksen pilkulla eroteltuna
  //tästä syystä todella epäselvää
  //lasku_tunnukset_temp halutaan olevan yksiulotteinen array tunnuksista
  if (!is_array($lasku_tunnukset)) {
    return $lasku_tunnukset;
  }

  $lasku_tunnukset_temp = array();
  foreach ($lasku_tunnukset as $tunnus) {
    $tunnus = explode(',', $tunnus);
    foreach ($tunnus as $t) {
      $lasku_tunnukset_temp[] = $t;
    }
  }

  return $lasku_tunnukset_temp;
}

function tyolista_message_divit() {
  global $kukarow, $yhtiorow;

  echo "<div id='message_box_success'>";
  echo '<font class="message">' . t('Päivitys onnistui') . '</font>';
  echo "<br/>";
  echo "<br/>";
  echo "</div>";

  echo "<div id='message_box_fail'>";
  echo '<font class="message">' . t('Päivitys epäonnistui') . '</font>';
  echo "<br/>";
  echo "<br/>";
  echo "</div>";
}