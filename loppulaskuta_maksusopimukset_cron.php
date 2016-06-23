<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  echo "Anna yhtiö!!!\n";
  die;
}

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
ini_set("display_errors", 0);

// otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Haetaan yhtion tiedot
$yhtiorow = hae_yhtion_parametrit($argv[1]);
$kukarow = hae_kukarow('admin', $argv[1]);

// Haetaan maksusopimukset
$query = "SELECT
          lasku.jaksotettu jaksotettu,
          concat_ws(' ',lasku.nimi, lasku.nimitark) nimi,
          lasku.tila,
          sum(IF (maksupositio.uusiotunnus > 0 AND uusiolasku.tila='L' AND uusiolasku.alatila='X', 1, 0)) AS laskutettu_kpl,
          sum(IF (maksupositio.uusiotunnus = 0, 1, 0)) tekematta_kpl,
          count(*) AS yhteensa_kpl,
          sum(IF (maksupositio.uusiotunnus = 0 OR (maksupositio.uusiotunnus > 0 AND uusiolasku.alatila!='X'), maksupositio.summa,0)) laskuttamatta,
          sum(IF (maksupositio.uusiotunnus > 0 AND uusiolasku.tila='L' AND uusiolasku.alatila='X', maksupositio.summa, 0)) laskutettu,
          sum(maksupositio.summa) yhteensa
          FROM lasku
          JOIN maksupositio ON maksupositio.yhtio = lasku.yhtio AND maksupositio.otunnus = lasku.tunnus
          JOIN maksuehto ON maksuehto.yhtio = lasku.yhtio AND maksuehto.tunnus = lasku.maksuehto AND maksuehto.jaksotettu != ''
          LEFT JOIN lasku uusiolasku ON maksupositio.yhtio = uusiolasku.yhtio AND maksupositio.uusiotunnus = uusiolasku.tunnus
          WHERE lasku.yhtio     = '{$kukarow['yhtio']}'
          AND lasku.jaksotettu  > 0
          AND lasku.tila        IN ('L','N','R','A','D')
          AND lasku.alatila    != 'X'
          GROUP BY jaksotettu, nimi, tila
          HAVING count(*) > sum(IF (maksupositio.uusiotunnus > 0 AND uusiolasku.tila='L' AND uusiolasku.alatila='X', 1, 0))
          AND (yhteensa_kpl - laskutettu_kpl <= 1)
          ORDER BY jaksotettu DESC";
$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  // Onko poistetun tilauksen takana loppulaskutusta odottava tilaus?
  if ($row["tila"] == 'D') {
    $query = "SELECT tunnus
              FROM lasku
              WHERE yhtio      = '{$kukarow['yhtio']}'
              AND vanhatunnus  = '{$row['jaksotettu']}'
              AND tila         IN ('L','N','R','A')
              AND alatila     != 'X'";
    $deleteds = pupe_query($query);

    if (mysql_num_rows($deleteds) == 0) {
      continue;
    }
  }
  // Tarkastetaan onko kaikki jo toimitettu ja tämä on good to go
  $query = "SELECT sum(IF (lasku.tila='L' AND lasku.alatila IN ('J','X'),1,0)) tilaok,
            sum(IF (tilausrivi.toimitettu='',1,0)) toimittamatta,
            count(*) toimituksia
            FROM lasku
            JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus     = lasku.tunnus
              AND tilausrivi.jaksotettu=lasku.jaksotettu
              AND tilausrivi.tyyppi     != 'D'
              AND tilausrivi.var        != 'P'
              AND tilausrivi.toimitettu != '')
            WHERE lasku.yhtio            = '{$kukarow['yhtio']}'
            AND lasku.jaksotettu         = '{$row['jaksotettu']}'
            AND lasku.tila               = 'L'
            AND lasku.alatila            IN ('J', 'X')
            GROUP BY lasku.jaksotettu
            HAVING tilaok = toimituksia
            AND toimittamatta            = 0";
  $toimitettu = pupe_query($query);
  if (mysql_affected_rows() > 0) {
    echo "\nLoppulaskutetaan maksusopimus: {$row['jaksotettu']}\n";
    loppulaskuta($row['jaksotettu']);
  }
}
