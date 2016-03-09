<?php

// Tällä ohjelmalla voidaan generoida myyntitilauksia kuluvalle viikolle yrityksille joilla ei
// vielä sellaisia ole

require "../inc/parametrit.inc";
require "tilauskasittely/luo_myyntitilausotsikko.inc";

if (!isset($tee)) $tee = '';
if (!isset($kokonaiskustannus)) $kokonaiskustannus = 1000;
if (!isset($tilausmaara)) $tilausmaara = 3;

$kauppakeskus_myyra = '003732419754';

echo "<font class='head'>", t("Generoi myyntitilauksia yrityksille"), "</font><hr>";

if ($tee == 'GENEROI') {
  if (empty($valitut)) {
    echo "<font class='error'>Et valinnut yhtään yritystä</font><br><br>";
  }
  else {
    $tilaukset = generoi_myyntitilauksia($valitut, $kokonaiskustannus, $tilausmaara, $kauppakeskus_myyra);
  }

  $tee = '';
}

if (empty($tee)) {
  echo_yrityspeli_kayttoliittyma($kokonaiskustannus, $tilausmaara);
}

require "inc/footer.inc";

function echo_yrityspeli_kayttoliittyma($kokonaiskustannus, $tilausmaara) {
  global $yhtiorow, $kukarow;

  // Tarkastellaan aina onko kuluvalle viikolle luotu tilauksia
  $alkuaika = date("Y-m-d", strtotime('monday this week'));
  $loppuaika = date("Y-m-d", strtotime('sunday this week'));

  echo "<font class='message'>Aikaväli {$alkuaika} - {$loppuaika}</font><br><br>";

  $tilauksettomat_yhtiot = hae_tilauksettomat_yhtiot($alkuaika, $loppuaika);

  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='GENEROI'>";

  echo "<table>";

  echo "<tr>";
  echo "<th>".t('Tilausten lukumäärä per yritys')."</th>";
  echo "<td>";
  echo "<input type='text' name='tilausmaara' size='10' value='${tilausmaara}'/>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t('Tilausten keskimääräinen arvo')."</th>";
  echo "<td>";
  echo "<input type='text' name='kokonaiskustannus' size='10' value='{$kokonaiskustannus}'/>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";
  echo "<br>";

  echo "<font class='message'>".t('Valitse yritykset')."</font><br><br>";

  echo "<table>";
  echo "<tr>";
  echo "<th>nimi</td>";
  echo "<th>tilauksia</td>";
  echo "<th>summa</td>";
  echo "<th></td>";
  echo "</tr>";

  foreach ($tilauksettomat_yhtiot as $yhtio) {
    $checked = (int) $yhtio['tilauksia'] == 0 ? 'checked' : '';

    echo "<tr>";
    echo "<td>{$yhtio['nimi']}</td>";
    echo "<td>{$yhtio['tilauksia']}</td>";
    echo "<td>{$yhtio['summa']}</td>";
    echo "<td><input type='checkbox' name='valitut[]' value='{$yhtio['yhtio']}' $checked></td>";
    echo "</tr>";
  }

  if (count($tilauksettomat_yhtiot) == 0) {
    echo t('Yhtään tilauksetonta yritystä ei löytynyt');
  }

  echo "</table>";
  echo "<br>";
  echo "<input type='submit' value='".t('Luo myyntitilauksia valituille yrityksille')."'>";
  echo "</form>";
}

function hae_tilauksettomat_yhtiot($alkuaika, $loppuaika) {
  global $kukarow, $yhtiorow;

  $tilauksettomat_yhtiot = array();

  $query = "SELECT *
            FROM yhtio
            WHERE yhtio NOT IN ('{$kukarow['yhtio']}')";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    $tilausquery = "SELECT lasku.yhtio,
                    min(lasku.yhtio_nimi) as nimi,
                    count(*) as tilauksia,
                    sum(tilausrivi.varattu * tilausrivi.hinta) as summa
                    FROM lasku
                    JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
                      AND tilausrivi.otunnus = lasku.tunnus)
                    WHERE lasku.yhtio = '{$row['yhtio']}'
                    AND lasku.tila IN ('N','L')
                    AND lasku.luontiaika BETWEEN '$alkuaika' AND '$loppuaika'
                    GROUP BY yhtio";
    $tilausresult = pupe_query($tilausquery);
    $tilausrow = mysql_fetch_assoc($tilausresult);

    $tilauksettomat_yhtiot[] = array(
      'yhtio' => $tilausrow['yhtio'],
      'nimi' => $tilausrow['nimi'],
      'tilauksia' => $tilausrow['tilauksia'],
      'summa' => round($tilausrow['summa']),
    );
  }

  return $tilauksettomat_yhtiot;
}

function generoi_myyntitilauksia($yhtiot, $kokonaiskustannus, $tilausmaara, $kauppakeskus_myyra) {
  foreach ($yhtiot as $yhtio) {
    $asiakas = hae_oletusasiakkuus($yhtio, $kauppakeskus_myyra);

    if (empty($asiakas)) {
      echo "<font class='error'>";
      echo "Yhtiöllä '{$yhtio}' ei ole 'Kauppakeskus Myyrä' perustettuna, ei voitu luoda tilauksia.";
      echo "</font>";

      echo "<br><br>";

      continue;
    }

    for ($i=0; $i < $tilausmaara; $i++) {
      luo_tilausotsikot_ja_tilausrivit($yhtio, $asiakas, $kokonaiskustannus);
    }
  }
}

function hae_oletusasiakkuus($yhtio, $kauppakeskus_myyra) {
  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$yhtio}'
            AND ovttunnus = '{$kauppakeskus_myyra}'
            LIMIT 1";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function luo_tilausotsikot_ja_tilausrivit($yhtio, $asiakas, $kokonaiskustannus) {
  global $yhtiorow, $kukarow;

  $alkuperainen_yhtio = $yhtiorow;
  $alkuperainen_kuka  = $kukarow;

  $yhtiorow = hae_yhtion_parametrit($yhtio);
  $kukarow['kesken'] = '';
  $kukarow['yhtio'] = $yhtio;

  // Luodaan uusi myyntitilausotsikko
  $tilausnumero = luo_myyntitilausotsikko("RIVISYOTTO", $asiakas["tunnus"]);

  $kukarow["kesken"] = $tilausnumero;

  // Haetaan avoimen tilauksen otsikko
  $query    = "SELECT *
               FROM lasku
               WHERE yhtio='{$yhtio}'
               AND tunnus='{$tilausnumero}'";
  $laskures = pupe_query($query);
  $laskurow = mysql_fetch_assoc($laskures);

  // Lisätään tuotteet
  $tuoteriveja = rand(1, 3);

  $hintacounter = 0;

  while ($hintacounter < $kokonaiskustannus) {
    $trow = tuotearvonta($yhtio);

    if ($trow === false) {
      echo "<font class='error'>";
      echo "Yrityksellä {$yhtiorow['nimi']} ei ole sopivia tuotteita, ei voitu luoda tilauksia.<br>";
      echo "</font>";

      return;
    }

    $kpl = rand(1,3);
    $hintacounter += ($trow['myyntihinta'] * $kpl);

    $params = array(
      'trow' => $trow,
      'laskurow' => $laskurow,
      'tuoteno' => $trow['tuoteno'],
      'kpl' => $kpl
    );

    lisaa_rivi($params);
  }

  echo "Perustettiin tilaus {$tilausnumero} yritykselle {$yhtiorow['nimi']}<br>";

  // Tilaus valmiiksi
  require "tilauskasittely/tilaus-valmis.inc";

  $yhtiorow = $alkuperainen_yhtio;
  $kukarow = $alkuperainen_kuka;

  $kukarow['kesken'] = '';
}

function tuotearvonta($yhtio) {
  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '{$yhtio}'
            AND status != 'P'
            AND myyntihinta > 0
            AND tuotetyyppi NOT in ('A','B')
            ORDER BY RAND() LIMIT 0, 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return false;
  }

  return mysql_fetch_assoc($result);
}
