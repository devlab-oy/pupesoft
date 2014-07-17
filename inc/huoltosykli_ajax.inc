<?php

require_once('inc/laite_huolto_functions.inc');

if ($action == 'hae_huoltovali_options') {
  $huoltovali_options = huoltovali_options($huoltosykli_tunnus);

  $oletus = reset($huoltovali_options);

  $options_string = "<option value='0'>Ei huoltoväliä</option>";

  foreach ($huoltovali_options as $key => $val) {
    if ($key == $oletus['days']) {
      $selected = 'selected';
    }

    $options_string .= "<option value='{$key}' {$selected}>{$val['dropdown_text']}</option>";

    $selected = null;
  }

  echo $options_string;
}

if ($action == 'hae_huoltosykli_options') {

  $o_qry = "SELECT olosuhde
            FROM paikka
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = $paikka";
  $o_res = pupe_query($o_qry);
  $olosuhde = mysql_result($o_res, 0);

  if ($tuoteno == 'MUISTUTUS') {
    $sammutin = array(
        'tyyppi' => 'muistutus',
        'koko'   => '',
    );
  }
  else {
    $query = "SELECT sammutin_tyyppi.selite AS tyyppi,
              sammutin_koko.selite AS koko
              FROM tuote
              JOIN tuotteen_avainsanat AS sammutin_tyyppi
              ON ( sammutin_tyyppi.yhtio = tuote.yhtio
                AND sammutin_tyyppi.tuoteno = tuote.tuoteno
                AND sammutin_tyyppi.laji = 'sammutin_tyyppi' )
              JOIN tuotteen_avainsanat AS sammutin_koko
              ON ( sammutin_koko.yhtio = tuote.yhtio
                AND sammutin_koko.tuoteno = tuote.tuoteno
                AND sammutin_koko.laji = 'sammutin_koko' )
              WHERE tuote.yhtio = '{$kukarow['yhtio']}'
              AND tuote.tuoteno = '{$tuoteno}'";
    $result = pupe_query($query);
    $sammutin = mysql_fetch_assoc($result);
  }

  if (!empty($sammutin)) {
    $huoltosykli_options = hae_huoltosyklit_tyyppi_koko($sammutin['tyyppi'], $sammutin['koko'], $tyyppi);
    $options_string = "<option value='0'>Ei huoltosykliä</option>";

    $sel = $set = '';
    foreach ($huoltosykli_options as $val) {
      if ($val['olosuhde'] == $olosuhde and $val['koko'] == $sammutin['koko'] and $val['tyyppi'] == $sammutin['tyyppi'] and $set == '' ) {
        $sel = 'selected';
        $set = 1;
      }
      $options_string .= "<option value='{$val['tunnus']}' $sel>{$val['dropdown_text']}</option>";
      $sel = '';
    }
  }
  else {
    $options_string = "<option value='0'>Ei huoltosykliä</option>";
  }

  echo $options_string;
}

if ($action == 'hae_huoltovalit') {
  $huoltovalit = huoltovali_options();

  array_walk_recursive($huoltovalit, function(&$value, $key) {
            if (is_string($value)) {
              $value = utf8_encode($value);
            }
          });

  echo json_encode($huoltovalit);
}

exit;
