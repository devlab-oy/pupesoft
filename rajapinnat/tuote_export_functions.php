<?php

function tuote_export_tee_querylisa_resultista($tyyppi, array $tulokset) {

  $poimitut = '';

  // $tulokset = array(
  //   [0] => array ("muuttuneet_tuotenumerot" => "'3','4'"),
  //   [1] => array ("muuttuneet_tuotenumerot" => "'12','24'"´),
  //   [2] => array ("muuttuneet_tuotenumerot" => "'5'" )
  // );
  // -> 3','4','12','24','5'

  if (isset($tulokset[0][$tyyppi]) and !empty($tulokset[0][$tyyppi])) {
    $poimitut = $tulokset[0][$tyyppi];
  }
  if (isset($tulokset[1][$tyyppi]) and !empty($tulokset[1][$tyyppi])) {
    if (empty($poimitut)) {
      $poimitut .= $tulokset[1][$tyyppi];
    }
    else {
      $poimitut .= ",{$tulokset[1][$tyyppi]}";
    }

  }
  if (isset($tulokset[2][$tyyppi]) and !empty($tulokset[2][$tyyppi])) {
    if (empty($poimitut)) {
      $poimitut = $tulokset[2][$tyyppi];
    }
    else {
      $poimitut .= ",{$tulokset[2][$tyyppi]}";
    }
  }

  if (!empty($poimitut)) {
    if ($tyyppi == 'muuttuneet_tuotenot') {
      $result = " AND tuote.tuoteno IN ($poimitut) ";
    }
    elseif ($tyyppi == 'muuttuneet_ryhmat') {
      $result = " AND tuote.aleryhma IN ($poimitut) ";
    }
    else {
      $result = '';
    }
  }

  return $result;
}

function tuote_export_hae_hintamuutoksia_sisaltavat_tuotenumerot() {
  global $kukarow, $datetime_checkpoint;

  $kaikki_arvot = array();

  $query1 = "SELECT group_concat('\'',tuoteno,'\'') muuttuneet_tuotenot
             FROM asiakashinta
             WHERE yhtio   = '{$kukarow['yhtio']}'
             AND muutospvm >= '{$datetime_checkpoint}'
             AND tuoteno !=''";
  $result1 = pupe_query($query1);
  $row1 = mysql_fetch_assoc($result1);
  $kaikki_arvot[] = $row1;

  $query2 = "SELECT group_concat('\'',tuoteno,'\'') muuttuneet_tuotenot
             FROM asiakasalennus
             WHERE yhtio   = '{$kukarow['yhtio']}'
             AND muutospvm >= '{$datetime_checkpoint}'
             AND tuoteno !=''";
  $result2 = pupe_query($query2);
  $row2 = mysql_fetch_assoc($result2);
  $kaikki_arvot[] = $row2;

  $result = tuote_export_tee_querylisa_resultista('muuttuneet_tuotenot', $kaikki_arvot);

  return $result;
}

function tuote_export_hae_hintamuutoksia_sisaltavat_tuoteryhmat() {
  global $kukarow, $datetime_checkpoint;

  $kaikki_arvot = array();

  $query3 = "SELECT group_concat('\'',ryhma,'\'') muuttuneet_ryhmat
             FROM asiakashinta
             WHERE yhtio   = '{$kukarow['yhtio']}'
             AND muutospvm >= '{$datetime_checkpoint}'
             AND ryhma !=''";
  $result3 = pupe_query($query3);
  $row3 = mysql_fetch_assoc($result3);
  $kaikki_arvot[] = $row3;

  $query4 = "SELECT group_concat('\'',ryhma,'\'') muuttuneet_ryhmat
             FROM asiakasalennus
             WHERE yhtio   = '{$kukarow['yhtio']}'
             AND muutospvm >= '{$datetime_checkpoint}'
             AND ryhma !=''";
  $result4 = pupe_query($query4);
  $row4 = mysql_fetch_assoc($result4);
  $kaikki_arvot[] = $row4;

  $query5 = "SELECT group_concat('\'',ryhma,'\'') muuttuneet_ryhmat
             FROM perusalennus
             WHERE yhtio   = '{$kukarow['yhtio']}'
             AND muutospvm >= '{$datetime_checkpoint}'
             AND ryhma !=''";
  $result5 = pupe_query($query5);
  $row5 = mysql_fetch_assoc($result5);
  $kaikki_arvot[] = $row5;

  $result = tuote_export_tee_querylisa_resultista('muuttuneet_ryhmat', $kaikki_arvot);

  return $result;
}
