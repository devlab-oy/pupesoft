<?php

function yrityspeli_kayttoliittyma(Array $params) {
  global $yhtiorow, $kukarow;

  $alkuaika              = $params['alkuaika'];
  $kokonaiskustannus     = $params['kokonaiskustannus'];
  $loppuaika             = $params['loppuaika'];
  $messages              = $params['messages'];
  $tilauksettomat_yhtiot = $params['tilauksettomat_yhtiot'];
  $tilausmaara           = $params['tilausmaara'];

  echo "<font class='head'>";
  echo t("L‰het‰ ostotilauksia yrityksille");
  echo "</font>";
  echo "<hr>";

  foreach ($messages as $message) {
    echo "<font class='error'>{$message}</font><br>";
  }

  echo "<font class='message'>Aikav‰li {$alkuaika} - {$loppuaika}</font><br><br>";

  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='GENEROI'>";

  echo "<table>";

  echo "<tr>";
  echo "<th>".t('Tilausten lukum‰‰r‰ per yritys')."</th>";
  echo "<td>";
  echo "<input type='text' name='tilausmaara' size='10' value='${tilausmaara}'/>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t('Tilausten keskim‰‰r‰inen arvo')."</th>";
  echo "<td>";
  echo "<input type='text' name='kokonaiskustannus' size='10' value='{$kokonaiskustannus}'/>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";
  echo "<br>";

  echo "<font class='message'>".t('Valitse yritykset')."</font><br><br>";

  echo "<table>";
  echo "<tr>";
  echo "<th>yhtiˆ</td>";
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
    echo "<td><input type='checkbox' name='valitut[]' value='{$yhtio['asiakas_tunnus']}' {$checked} {$disabled}></td>";
    echo "</tr>";
  }

  if (count($tilauksettomat_yhtiot) == 0) {
    echo t('Yht‰‰n tilauksetonta yrityst‰ ei lˆytynyt');
  }

  echo "</table>";
  echo "<br>";
  echo "<input type='submit' value='".t('L‰het‰ ostotilaukset yrityksille')."'>";
  echo "</form>";
}

function yrityspeli_hae_tilauksettomat_yhtiot($alkuaika, $loppuaika) {
  global $kukarow, $yhtiorow;

  $tilauksettomat_yhtiot = array();

  // Etsit‰‰n samasta tietokannasta kaikki muut yhtiˆt
  $query = "SELECT *
            FROM yhtio
            WHERE yhtio != '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    // katsotaan lˆytyykˆ t‰m‰ yhtiˆ meilt‰ asiakkaana (ytunnuksen mukaan)
    // lasketaan yhtiˆn avointen tilausten arvo (varattu * hinta)
    $tilausquery = "SELECT yhtio.nimi as yhtio_nimi,
                    asiakas.nimi as asiakas_nimi,
                    asiakas.ytunnus as asiakas_ytunnus,
                    asiakas.email as asiakas_email,
                    asiakas.tunnus as asiakas_tunnus,
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
                    GROUP BY yhtio.nimi,
                    asiakas.nimi,
                    asiakas.ytunnus,
                    asiakas.email,
                    asiakas.tunnus";
    $tilausresult = pupe_query($tilausquery);

    while ($tilausrow = mysql_fetch_assoc($tilausresult)) {
      $tilauksettomat_yhtiot[] = array(
        'asiakas_nimi'    => $tilausrow['asiakas_nimi'],
        'asiakas_ytunnus' => $tilausrow['asiakas_ytunnus'],
        'asiakas_tunnus'  => $tilausrow['asiakas_tunnus'],
        'asiakas_email'   => $tilausrow['asiakas_email'],
        'summa'           => round($tilausrow['summa']),
        'tilauksia'       => (int) $tilausrow['tilauksia'],
        'yhtio'           => $tilausrow['yhtio_nimi'],
      );
    }
  }

  return $tilauksettomat_yhtiot;
}

function yrityspeli_generoi_ostotilauksia(Array $params) {
  $asiakkaat          = $params['asiakkaat'];
  $kokonaiskustannus  = $params['kokonaiskustannus'];
  $tilausmaara        = $params['tilausmaara'];

  $response = array();

  if (empty($asiakkaat)) {
    $response[] = "Et valinnut yht‰‰n asiakasta";

    return $response;
  }

  foreach ($asiakkaat as $asiakas) {
    for ($i = 0; $i < $tilausmaara; $i++) {
      $response = yrityspeli_generoi_ostotilaus($asiakas, $kokonaiskustannus);
    }
  }

  return $response;
}

function yrityspeli_generoi_ostotilaus($asiakas, $kokonaiskustannus) {
  global $yhtiorow, $kukarow;

  require_once 'inc/luo_ostotilausotsikko.inc';

  $asiakas = hae_asiakas($asiakas);
  $toimittaja = yrityspeli_hae_toimittaja();
  $hintacounter = 0;
  $response = array();

  $params = array(
    'liitostunnus' => $toimittaja['tunnus'],
    'nimi'         => $asiakas['nimi'],
    'nimitark'     => $asiakas['nimitark'],
    'osoite'       => $asiakas['osoite'],
    'postino'      => $asiakas['postino'],
    'postitp'      => $asiakas['postitp'],
    'maa'          => $asiakas['maa'],
  );

  $ostotilaus = luo_ostotilausotsikko($params);
  $kukarow['kesken'] = $ostotilaus['tunnus'];

  while ($hintacounter < $kokonaiskustannus) {
    $trow = yrityspeli_tuotearvonta($toimittaja['tunnus']);

    if ($trow === false) {
      $response[] = "Yrityksell‰ {$yhtiorow['nimi']} ei ole sopivia tuotteita, jota voi tilata toimittajalta {$toimittaja['nimi']}.";

      return $response;
    }

    $kpl = rand(1, 3);
    $hintacounter += ($trow['myyntihinta'] * $kpl);

    $params = array(
      'kpl'      => $kpl,
      'laskurow' => $ostotilaus,
      'trow'     => $trow,
      'tuoteno'  => $trow['tuoteno'],
    );

    lisaa_rivi($params);
  }

  // p‰ivitet‰‰n tilaus valmiiksi
  $query = "UPDATE lasku SET alatila = 'A' WHERE tunnus='{$ostotilaus['tunnus']}'";
  $result = pupe_query($query);

  $response[] = "Tehtiin ostotilaus {$ostotilaus['tunnus']} yritykselle {$asiakas['nimi']}<br>";

  return $response;
}

function yrityspeli_hae_toimittaja() {
  global $yhtiorow, $kukarow;

  $query = "SELECT *
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tyyppi in ('', 'L')
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return 0;
  }

  $row = mysql_fetch_assoc($result);

  return $row;
}

function yrityspeli_tuotearvonta($toimittaja) {
  global $kukarow, $yhtiorow;

  // katsotaan mit‰ tuotteita t‰lt‰ toimittajalta voi tilata, ja arvotaan yksi
  $query = "SELECT tuote.*
            FROM tuote
            JOIN tuotteen_toimittajat on (tuotteen_toimittajat.yhtio = tuote.yhtio
              AND tuotteen_toimittajat.tuoteno = tuote.tuoteno
              AND tuotteen_toimittajat.liitostunnus = $toimittaja)
            WHERE tuote.yhtio = '{$kukarow['yhtio']}'
            AND tuote.status != 'P'
            AND tuote.myyntihinta  > 0
            AND tuote.tuotetyyppi NOT in ('A','B')
            ORDER BY RAND() LIMIT 0, 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return false;
  }

  return mysql_fetch_assoc($result);
}
