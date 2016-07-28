<?php

function echo_yrityspeli_kayttoliittyma(Array $params) {
  global $yhtiorow, $kukarow;

  $alkuaika              = $params['alkuaika'];
  $kokonaiskustannus     = $params['kokonaiskustannus'];
  $loppuaika             = $params['loppuaika'];
  $messages              = $params['messages'];
  $tilauksettomat_yhtiot = $params['tilauksettomat_yhtiot'];
  $tilausmaara           = $params['tilausmaara'];

  echo "<font class='head'>";
  echo t("Lähetä ostotilauksia yrityksille");
  echo "</font>";
  echo "<hr>";

  foreach ($messages as $message) {
    echo "<font class='error'>{$message}</font><br>";
  }

  echo "<font class='message'>Aikaväli {$alkuaika} - {$loppuaika}</font><br><br>";

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
  echo "<th>yhtiö</td>";
  echo "<th>asiakas</td>";
  echo "<th>ytunnus</td>";
  echo "<th>email</td>";
  echo "<th>tilauksia</td>";
  echo "<th>summa</td>";
  echo "<th></td>";
  echo "</tr>";

  foreach ($tilauksettomat_yhtiot as $yhtio) {
    $checked = $yhtio['tilauksia'] == 0 ? 'checked' : '';
    $disabled = '';

    if (empty($yhtio['asiakas_email'])) {
      $checked = '';
      $disabled = 'disabled';
    }

    echo "<tr class='aktiivi'>";
    echo "<td>{$yhtio['yhtio']}</td>";
    echo "<td>{$yhtio['asiakas_nimi']}</td>";
    echo "<td>{$yhtio['asiakas_ytunnus']}</td>";
    echo "<td>{$yhtio['asiakas_email']}</td>";
    echo "<td class='text-right'>{$yhtio['tilauksia']}</td>";
    echo "<td class='text-right'>{$yhtio['summa']}</td>";
    echo "<td><input type='checkbox' name='valitut[]' value='{$yhtio['yhtio']}' {$checked} {$disabled}></td>";
    echo "</tr>";
  }

  if (count($tilauksettomat_yhtiot) == 0) {
    echo t('Yhtään tilauksetonta yritystä ei löytynyt');
  }

  echo "</table>";
  echo "<br>";
  echo "<input type='submit' value='".t('Lähetä ostotilaukset yrityksille')."'>";
  echo "</form>";
}

function hae_tilauksettomat_yhtiot($alkuaika, $loppuaika) {
  global $kukarow, $yhtiorow;

  $tilauksettomat_yhtiot = array();

  // Etsitään samasta tietokannasta kaikki muut yhtiöt
  $query = "SELECT *
            FROM yhtio
            WHERE yhtio != '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    // katsotaan löytyykö tämä yhtiö meiltä asiakkaana (ytunnuksen mukaan)
    // lasketaan yhtiön avointen tilausten arvo (varattu * hinta)
    $tilausquery = "SELECT yhtio.nimi as yhtio_nimi,
                    asiakas.nimi as asiakas_nimi,
                    asiakas.ytunnus as asiakas_ytunnus,
                    asiakas.email as asiakas_email,
                    count(distinct lasku.tunnus) as tilauksia,
                    sum(tilausrivi.varattu * tilausrivi.hinta) as summa
                    FROM yhtio
                    JOIN asiakas ON (asiakas.yhtio = '{$kukarow['yhtio']}'
                      AND asiakas.ytunnus = yhtio.ytunnus)
                    LEFT JOIN lasku ON (lasku.yhtio = yhtio.yhtio
                      AND lasku.tila IN ('N','L')
                      AND lasku.luontiaika BETWEEN '$alkuaika' AND '$loppuaika')
                    LEFT JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
                      AND tilausrivi.otunnus = lasku.tunnus)
                    WHERE yhtio.yhtio = '{$row['yhtio']}'
                    GROUP BY yhtio.nimi, asiakas.nimi, asiakas.ytunnus, asiakas.email";
    $tilausresult = pupe_query($tilausquery);

    while ($tilausrow = mysql_fetch_assoc($tilausresult)) {
      $tilauksettomat_yhtiot[] = array(
        'asiakas_nimi'    => $tilausrow['asiakas_nimi'],
        'asiakas_ytunnus' => $tilausrow['asiakas_ytunnus'],
        'asiakas_email'   => $tilausrow['asiakas_email'],
        'summa'           => round($tilausrow['summa']),
        'tilauksia'       => (int) $tilausrow['tilauksia'],
        'yhtio'           => $tilausrow['yhtio_nimi'],
      );
    }
  }

  return $tilauksettomat_yhtiot;
}

function generoi_ostotilauksia(Array $params) {
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
