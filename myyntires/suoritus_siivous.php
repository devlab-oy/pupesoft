<?php

if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

date_default_timezone_set('Europe/Helsinki');

$pupe_root_polku = dirname(dirname(__FILE__));

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku);
error_reporting(E_ALL);
ini_set("display_errors", 1);

require "inc/connect.inc";
require "inc/functions.inc";

// echo "\n";
// echo "Anna suoritusten tunnukset pilkulla eroteltuna: ";
// $user_input = fgets(STDIN);

// kaikki kohdistetut suoritukset, jotka on maksettu syyskuussa
mysql_query("set group_concat_max_len = 1000000");

$query = "SELECT group_concat(suoritus.tunnus) as lista
          FROM suoritus
          WHERE suoritus.kohdpvm != '0000-00-00'
          AND suoritus.maksupvm like '2015-09-%'";
$result = pupe_query($query);
$row = mysql_fetch_assoc($result);
$user_input = $row['lista'];

// varmistetaan, että kaikki arvot on numeroita
$suoritus_tunnukset = array_map('intval', explode(',', $user_input));

echo "\n";

foreach ($suoritus_tunnukset as $suoritus_tunnus) {
  // haetaan suoritus tietue
  $suoritus_row = hae_suoritus($suoritus_tunnus);

  if ($suoritus_row === false) {
    echo "Suoritusta {$suoritus_tunnus} ei löytynyt!\n\n";
    continue;
  }

  // tämä maksupäivä kaikkialle
  $maksupaiva = $suoritus_row['maksupvm'];

  // haetaan suorituksen_kohdistus tietue
  $suorituksen_kohdistus_row = hae_suorituksen_kohdistus($suoritus_tunnus);

  if ($suorituksen_kohdistus_row === false) {
    echo "Suorituksen {$suoritus_tunnus} kohdistusta ei löytynyt!\n\n";
    continue;
  }

  // haetaan suorituksen kaikki tiliöinnit
  $suorituksen_tiliointi_rows = hae_suorituksen_tilioinnit($suoritus_row);

  if ($suorituksen_tiliointi_rows === false) {
    echo "Suorituksen {$suoritus_tunnus} tiliöintejä ei löytynyt!\n\n";
    continue;
  }

  // haetaan lasku
  $lasku_row = hae_lasku($suorituksen_kohdistus_row);

  if ($lasku_row === false) {
    echo "Suorituksen {$suoritus_tunnus} laskua ei löytynyt!\n\n";
    continue;
  }

  // katsotaan, että maksupäivä ei ole ennen laskun päivää
  $maksupaiva = $lasku_row['tapvm'] > $maksupaiva ? $lasku_row['tapvm'] : $maksupaiva;

  // haetaan laskun tiliöinnit
  $laskun_tiliointi_rows = hae_laskun_tilioinnit($lasku_row);

  if ($laskun_tiliointi_rows === false) {
    echo "Suorituksen {$suoritus_tunnus} laskun tiliöintejä ei löytynyt!\n\n";
    continue;
  }

  echo "Suoritus {$suoritus_tunnus}: lasku {$lasku_row['laskunro']}, {$lasku_row['nimi']}, {$lasku_row['summa']} {$lasku_row['valkoodi']}\n";

  // lasketaan tehtyjä muutoksia
  $muutokset = 0;

  // korjataan suorituksen tiliöinnit
  while ($row = mysql_fetch_assoc($suorituksen_tiliointi_rows)) {
    if ($row['tapvm'] == $maksupaiva) {
      continue;
    }

    $query = "UPDATE tiliointi SET tapvm = '{$maksupaiva}' WHERE tunnus = {$row['tunnus']}";
    pupe_query($query);

    echo "Vaihdetaan suorituksen tiliöinnin päiväys. {$row['tilino']}: {$row['tapvm']} --> {$maksupaiva}\n";
    $muutokset++;
  }

  // korjataan laskun tiliöinnit
  while ($row = mysql_fetch_assoc($laskun_tiliointi_rows)) {
    if ($row['tapvm'] == $maksupaiva) {
      continue;
    }

    $query = "UPDATE tiliointi SET tapvm = '{$maksupaiva}' WHERE tunnus = {$row['tunnus']}";
    pupe_query($query);

    echo "Vaihdetaan laskun tiliöinnin päiväys. {$row['tilino']}: {$row['tapvm']} --> {$maksupaiva}\n";
    $muutokset++;
  }

  // korjataan suorituksen päiväykset
  if ($suoritus_row['kirjpvm'] != $maksupaiva or $suoritus_row['kohdpvm'] != $maksupaiva or $suoritus_row['maksupvm'] != $maksupaiva) {
    $query = "UPDATE suoritus SET
              kirjpvm  = '{$maksupaiva}',
              kohdpvm  = '{$maksupaiva}',
              maksupvm = '{$maksupaiva}'
              WHERE tunnus = {$suoritus_row['tunnus']}";
    pupe_query($query);

    if ($suoritus_row['kirjpvm'] != $maksupaiva) {
      echo "Vaihdetaan suorituksen kirjauspvm {$suoritus_row['kirjpvm']} --> {$maksupaiva}\n";
    }

    if ($suoritus_row['maksupvm'] != $maksupaiva) {
      echo "Vaihdetaan suorituksen maksupvm {$suoritus_row['maksupvm']} --> {$maksupaiva}\n";
    }

    if ($suoritus_row['kohdpvm'] != $maksupaiva) {
      echo "Vaihdetaan suorituksen kohdistuspvm {$suoritus_row['kohdpvm']} --> {$maksupaiva}\n";
    }

    $muutokset++;
  }

  // korjataan kohdistuksen suorituspäiväys
  if ($suorituksen_kohdistus_row['kirjauspvm'] != $maksupaiva) {
    $query = "UPDATE suorituksen_kohdistus SET
              kirjauspvm = '{$maksupaiva}'
              WHERE tunnus = {$suorituksen_kohdistus_row['tunnus']}";
    pupe_query($query);

    echo "Vaihdetaan kohdistuksen suorituspvm {$suorituksen_kohdistus_row['kirjauspvm']} --> {$maksupaiva}\n";
    $muutokset++;
  }

  // korjataan laskun maksupäivämäärä
  if ($lasku_row['mapvm'] != $maksupaiva) {
    $query = "UPDATE lasku SET
              mapvm = '{$maksupaiva}'
              WHERE tunnus = {$lasku_row['tunnus']}";
    pupe_query($query);

    echo "Vaihdetaan laskun maksupvm {$lasku_row['mapvm']} --> {$maksupaiva}\n";
    $muutokset++;
  }

  if ($muutokset == 0) {
    echo "Ei päivitettävää, kaikki kunnossa!\n";
  }

  echo "\n";
}

function hae_suoritus($suoritus_tunnus) {
  if ($suoritus_tunnus === false) {
    return false;
  }

  $query = "SELECT *
            FROM suoritus
            WHERE tunnus = {$suoritus_tunnus}";
  $result = pupe_query($query);

  if (mysql_num_rows($result) !== 1) {
    return false;
  }

  return mysql_fetch_assoc($result);
}

function hae_suorituksen_kohdistus($suoritus_tunnus) {
  if ($suoritus_tunnus === false) {
    return false;
  }

  // haetaan lasku, johon suoritus on kohdistettu
  $query = "SELECT *
            FROM suorituksen_kohdistus
            WHERE suoritustunnus = {$suoritus_tunnus}";
  $result = pupe_query($query);

  if (mysql_num_rows($result) !== 1) {
    return false;
  }

  return mysql_fetch_assoc($result);
}

function hae_suorituksen_tilioinnit($suoritus_row) {
  if ($suoritus_row === false) {
    return false;
  }

  $tiliointi_tunnus = $suoritus_row['ltunnus'];

  // haetaan suorituksen tiliöinnit
  $query = "SELECT *
            FROM tiliointi
            WHERE ltunnus = (SELECT ltunnus FROM tiliointi WHERE tunnus = {$tiliointi_tunnus})
            AND korjattu = ''";
  $result = pupe_query($query);

  if (mysql_num_rows($result) === 0) {
    return false;
  }

  return $result;
}

function hae_lasku($kohdistus_row) {
  if ($kohdistus_row === false) {
    return false;
  }

  $laskun_tunnus = $kohdistus_row['laskutunnus'];

  // haetaan lasku
  $query = "SELECT *
            FROM lasku
            WHERE tunnus = {$laskun_tunnus}";
  $result = pupe_query($query);

  if (mysql_num_rows($result) !== 1) {
    return false;
  }

  return mysql_fetch_assoc($result);
}

function hae_laskun_tilioinnit($lasku_row) {
  if ($lasku_row === false) {
    return false;
  }

  $laskun_tunnus = $lasku_row['tunnus'];

  // haetaan laskun tiliöinnit
  $query = "SELECT *
            FROM tiliointi
            WHERE ltunnus = {$laskun_tunnus}
            AND korjattu = ''
            AND selite like 'Manuaalisesti kohdistettu%'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) === 0) {
    return false;
  }

  return $result;
}
