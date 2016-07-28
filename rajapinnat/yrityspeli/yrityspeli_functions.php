<?php

function echo_yrityspeli_kayttoliittyma(Array $params) {
  global $yhtiorow, $kukarow;

  $kokonaiskustannus = $params['kokonaiskustannus'];
  $messages          = $params['messages'];
  $tilausmaara       = $params['tilausmaara'];

  // Tarkastellaan aina onko kuluvalle viikolle luotu tilauksia
  $alkuaika = date("Y-m-d", strtotime('monday this week'));
  $loppuaika = date("Y-m-d", strtotime('sunday this week'));

  echo "<font class='head'>";
  echo t("Generoi myyntitilauksia yrityksille");
  echo "</font>";
  echo "<hr>";

  foreach ($messages as $message) {
    echo "<font class='error'>{$message}</font><br>";
  }

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
    $tilausquery = "SELECT yhtio.yhtio,
                    min(yhtio.nimi) as nimi,
                    count(distinct lasku.tunnus) as tilauksia,
                    sum(tilausrivi.varattu * tilausrivi.hinta) as summa
                    FROM yhtio
                    LEFT JOIN lasku ON (lasku.yhtio = yhtio.yhtio
                      AND lasku.tila         IN ('N','L')
                      AND lasku.luontiaika BETWEEN '$alkuaika' AND '$loppuaika')
                    LEFT JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
                      AND tilausrivi.otunnus = lasku.tunnus)
                    WHERE yhtio.yhtio        = '{$row['yhtio']}'
                    GROUP BY yhtio.yhtio";
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

function generoi_myyntitilauksia(Array $params) {
  $kauppakeskus_myyra = $params['kauppakeskus_myyra'];
  $kokonaiskustannus  = $params['kokonaiskustannus'];
  $tilausmaara        = $params['tilausmaara'];
  $yhtiot             = $params['yhtiot'];

  $response = array();

  if (empty($yhtiot)) {
    $response[] = "Et valinnut yhtään yritystä";

    return $response;
  }

  foreach ($yhtiot as $yhtio) {
    $asiakas = hae_oletusasiakkuus($yhtio, $kauppakeskus_myyra);

    if (empty($asiakas)) {
      $response[] = "Yhtiöllä '{$yhtio}' ei ole 'Kauppakeskus Myyrä' perustettuna, ei voitu luoda tilauksia.";

      continue;
    }

    for ($i=0; $i < $tilausmaara; $i++) {
      luo_tilausotsikot_ja_tilausrivit($yhtio, $asiakas, $kokonaiskustannus);
    }
  }

  return $response;
}

function hae_oletusasiakkuus($yhtio, $kauppakeskus_myyra) {
  $query = "SELECT *
            FROM asiakas
            WHERE yhtio   = '{$yhtio}'
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
  $response = array();

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
      $response[] = "Yrityksellä {$yhtiorow['nimi']} ei ole sopivia tuotteita, ei voitu luoda tilauksia.";

      return $response;
    }

    $kpl = rand(1, 3);
    $hintacounter += ($trow['myyntihinta'] * $kpl);

    $params = array(
      'trow' => $trow,
      'laskurow' => $laskurow,
      'tuoteno' => $trow['tuoteno'],
      'kpl' => $kpl
    );

    lisaa_rivi($params);
  }

  $response[] = "Perustettiin tilaus {$tilausnumero} yritykselle {$yhtiorow['nimi']}<br>";

  // Tilaus valmiiksi
  require "tilauskasittely/tilaus-valmis.inc";

  $yhtiorow = $alkuperainen_yhtio;
  $kukarow = $alkuperainen_kuka;

  $kukarow['kesken'] = '';

  return $response;
}

function tuotearvonta($yhtio) {
  $query = "SELECT *
            FROM tuote
            WHERE yhtio      = '{$yhtio}'
            AND status      != 'P'
            AND myyntihinta  > 0
            AND tuotetyyppi  NOT in ('A','B')
            ORDER BY RAND() LIMIT 0, 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return false;
  }

  return mysql_fetch_assoc($result);
}
