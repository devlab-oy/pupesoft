<?php

require ("../inc/parametrit.inc");
require('konversio/huoltosykli_generointi.php');

echo "<font class='head'>".t("Huoltosyklien generointi")."</font><hr>";

$request = array();

generoi_huoltosyklit($request);

function generoi_huoltosyklit($request) {
  global $kukarow, $yhtiorow;

  $request['sammutin_koot'] = hae_sammuttimien_koot();
  $request['sammutin_tyypit'] = hae_sammuttimien_tyypit();
  $request['olosuhteet'] = array(
    'A'   => t('Sisällä'),
    'X'   => t('Ulkona'),
  );

  foreach ($request['sammutin_koot'] as $sammutin_koko) {
    foreach ($request['sammutin_tyypit'] as $sammutin_tyyppi) {
      foreach ($request['olosuhteet'] as $olosuhde => $selite) {
        $toimenpidetuote = paattele_toimenpide_tuote($sammutin_koko, $sammutin_tyyppi);

        if (!empty($toimenpidetuote)) {
          $query = "  INSERT INTO huoltosykli
                SET yhtio = '{$kukarow['yhtio']}',
                tyyppi = '{$sammutin_tyyppi}',
                koko = '{$sammutin_koko}',
                olosuhde = '{$olosuhde}',
                toimenpide = '{$toimenpidetuote['tuoteno']}',
                huoltovali = '3650',
                pakollisuus = '1',
                laatija = 'import',
                luontiaika = NOW()";
          pupe_query($query);

          echo "Luotiin huoltosykli {$sammutin_koko} {$sammutin_tyyppi} {$olosuhde} {$toimenpidetuote['tuoteno']}";
          echo "<br/>";
        }
      }
    }
  }
}

function paattele_toimenpide_tuote($sammutin_koko, $sammutin_tyyppi) {
  global $kukarow, $yhtiorow;

  if (stristr($sammutin_tyyppi, 'jauhe')) {
    $query = "  SELECT tuoteno
          FROM tuote
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND nimitys LIKE '%koeponnistus paineellinen {$sammutin_koko}%'";
  }
  else if (stristr($sammutin_tyyppi, 'hiili')) {
    $query = "  SELECT tuoteno
          FROM tuote
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND nimitys LIKE '%koeponnistus CO2-{$sammutin_koko}%'";
  }
  else if (stristr($sammutin_tyyppi, 'neste')) {
    $query = "  SELECT tuoteno
          FROM tuote
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND nimitys LIKE '%koeponnistus haloni {$sammutin_koko}%'";
  }

  $result = pupe_query($query);

  if (mysql_num_rows($result) == 1) {
    return mysql_fetch_assoc($result);
  }
  else {
    if (mysql_num_rows($result) == 0) {
      echo "Ei löytynyt toimenpide tuotetta: {$sammutin_koko} {$sammutin_tyyppi}";
      echo "<br/>";
    }
    else {
      echo "Löytyi monta toimenpide tuotetta: {$sammutin_koko} {$sammutin_tyyppi}";
      echo "<br/>";
    }
  }
}

require('inc/footer.inc');
