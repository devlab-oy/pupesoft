<?php

$_file = debug_backtrace();

$stackstrace = array_map(function($_arr) {
    if (strpos($_arr['file'], 'tilaus-valmis.inc') !== FALSE) {
      return 'tilaus-valmis.inc';
    }
    if (strpos($_arr['file'], 'keraa.php') !== FALSE) {
      return 'keraa.php';
    }
  }, $_file);

if (in_array('tilaus-valmis.inc', $stackstrace) and
  !empty($tilausnumerot) and
  is_array($tilausnumerot) and
  $laskurow['varasto'] == 139 and
  $laskurow['yhtio_toimipaikka'] == 27) {

  $kukarow['toimipaikka'] = 27;
  $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

  $otunnukset = implode(",", $tilausnumerot);

  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  IN ({$otunnukset})";
  $laskures = pupe_query($query);
  $laskurow = mysql_fetch_assoc($laskures);

  $query = "SELECT GROUP_CONCAT(tunnus) as tunnukset
            FROM tilausrivi
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND tyyppi  != 'D'
            AND otunnus  IN ({$otunnukset})";
  $tilrivres = pupe_query($query);
  $tilrivrow = mysql_fetch_assoc($tilrivres);

  if ($toim == 'REKLAMAATIO') {
    $_printteri = "printteri9";
  }
  else {
    $_printteri = "printteri1";
  }

  $avainsana_where = " and avainsana.selite       = '{$laskurow['varasto']}'
                       and avainsana.selitetark   = '{$laskurow['yhtio_toimipaikka']}'
                       and avainsana.selitetark_2 = '{$_printteri}'";

  $tp_tulostin = t_avainsana("VARTOIMTULOSTIN", '', $avainsana_where, '', '', "selitetark_3");

  if (empty($tp_tulostin)) {
    $tp_tulostin = 616;
  }

  $query = "SELECT *
            FROM kirjoittimet
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$tp_tulostin}'";
  $printterires = pupe_query($query);
  $printterirow = mysql_fetch_assoc($printterires);

  $params = array(
    'laskurow'                 => $laskurow,
    'sellahetetyyppi'          => '',
    'extranet_tilausvahvistus' => "",
    'naytetaanko_rivihinta'    => "",
    'tee'                      => 'P',
    'toim'                     => '',
    'komento'                  => $printterirow['komento'],
    'lahetekpl'                => $yhtiorow["oletus_lahetekpl"],
    'kieli'                    => '',
    'koontilahete'             => $otunnukset,
    'koontilahete_tilausrivit' => $tilrivrow['tunnukset'],
  );

  pupesoft_tulosta_lahete($params);
}

if (in_array('keraa.php', $stackstrace) and
  !empty($laskurow) and
  $laskurow['varasto'] == 139 and
  $laskurow['yhtio_toimipaikka'] == 27) {
  $lahetekpl = 0;
}
elseif (isset($lahetekpl_alkuperainen)) {
  $lahetekpl = $lahetekpl_alkuperainen;
}
