<?php

function yrityspeli_kayttoliittyma(Array $params) {
  global $yhtiorow, $kukarow;

  $alkuaika              = $params['alkuaika'];
  $kokonaiskustannus     = $params['kokonaiskustannus'];
  $loppuaika             = $params['loppuaika'];
  $messages              = $params['messages'];
  $tilauksettomat_yhtiot = $params['tilauksettomat_yhtiot'];
  $tilausmaara           = $params['tilausmaara'];
  $valitut_tryt          = $params['valitut_tryt'];
  $toimipaikat           = $params['toimipaikat'];

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
  echo "<th>yhtiö</th>";
  echo "<th>asiakas</th>";
  echo "<th>ytunnus</th>";
  echo "<th>email</th>";
  echo "<th>tilauksia</th>";
  echo "<th>summa</th>";
  echo "<th>Tuoteryhmä</th>";
  echo "<th>toimipaikka</th>";
  echo "<th></th>";
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

    echo "<td>";
    echo "<select name='valitut_tryt[{$yhtio["asiakas_tunnus"]}]'>";
    $result = t_avainsana('TRY', '', 'ORDER BY selite + 0');
    while ($tryrow = mysql_fetch_assoc($result)) {
      $sel = $valitut_tryt[$yhtio['asiakas_tunnus']] == $tryrow["selite"] ? " selected" : "";
      echo "<option value='{$tryrow["selite"]}'{$sel}>{$tryrow["selite"]} - {$tryrow["selitetark"]}</option>";
    }
    echo "</select>";
    echo "</td>";

    echo "<td>";
    echo "<select name='toimipaikat[{$yhtio["asiakas_tunnus"]}]'>";
    echo "<option>Ei toimipaikkaa</option>";
    foreach (hae_toimipaikat() as $toimipaikka) {
      $sel = $toimipaikka["tunnus"] == $toimipaikat[$yhtio["asiakas_tunnus"]] ? " selected" : "";
      echo "<option value='{$toimipaikka["tunnus"]}'{$sel}>{$toimipaikka["nimi"]}</option>";
    }
    echo "</select>";
    echo "</td>";

    echo "<td><input type='checkbox' name='valitut[]' value='{$yhtio['asiakas_tunnus']}' {$checked} {$disabled}></td>";
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

function yrityspeli_hae_tilauksettomat_yhtiot($alkuaika, $loppuaika) {
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
                    asiakas.tunnus as asiakas_tunnus,
                    count(distinct lasku.tunnus) as tilauksia,
                    sum((tilausrivi.varattu + tilausrivi.jt) * tilausrivi.hinta) as summa
                    FROM yhtio
                    JOIN asiakas ON (asiakas.yhtio = '{$kukarow['yhtio']}'
                      AND REPLACE(asiakas.ytunnus,'-','') = REPLACE(yhtio.ytunnus,'-',''))
                    LEFT JOIN lasku ON (lasku.yhtio = yhtio.yhtio
                      AND lasku.tila IN ('N','L')
                      AND lasku.luontiaika BETWEEN '$alkuaika' AND '$loppuaika')
                    LEFT JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
                      AND tilausrivi.otunnus = lasku.tunnus
                      AND tilausrivi.tyyppi = 'L'
                      AND tilausrivi.var != 'P')
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
  $asiakkaat         = $params['asiakkaat'];
  $kokonaiskustannus = $params['kokonaiskustannus'];
  $tilausmaara       = $params['tilausmaara'];
  $valitut_tryt      = $params['valitut_tryt'];
  $toimipaikat       = $params['toimipaikat'];

  $response = array();

  if (empty($asiakkaat)) {
    $response[] = "Et valinnut yhtään asiakasta";

    return $response;
  }

  foreach ($asiakkaat as $asiakas) {
    $try         = $valitut_tryt[$asiakas];
    $toimipaikka = $toimipaikat[$asiakas];
    for ($i = 0; $i < $tilausmaara; $i++) {
      $params = array(
        "asiakas"           => $asiakas,
        "kokonaiskustannus" => $kokonaiskustannus,
        "try"               => $try,
        "toimipaikka"       => $toimipaikka,
      );
      $generate = yrityspeli_generoi_ostotilaus($params);
      $response = array_merge($response, $generate);
    }
  }

  return $response;
}

function yrityspeli_generoi_ostotilaus(Array $params) {
  global $yhtiorow, $kukarow;

  $asiakas           = $params["asiakas"];
  $kokonaiskustannus = $params["kokonaiskustannus"];
  $try               = $params["try"];
  $toimipaikka       = $params["toimipaikka"];

  require_once 'inc/luo_ostotilausotsikko.inc';

  $asiakas = hae_asiakas($asiakas);
  $toimittaja = yrityspeli_hae_toimittaja();
  $hintacounter = 0;
  $response = array();

  if ($toimittaja === false) {
    $response[] = "Yrityksellä {$yhtiorow['nimi']} ei ole yhtään toimittajaa.";

    return $response;
  }

  $params = array(
    'liitostunnus' => $toimittaja['tunnus'],
  );

  $ostotilaus = luo_ostotilausotsikko($params);

  $query = "UPDATE lasku
            SET nimi     = '{$asiakas["nimi"]}',
                nimitark = '{$asiakas["nimitark"]}',
                osoite   = '{$asiakas["osoite"]}',
                postino  = '{$asiakas["postino"]}',
                postitp  = '{$asiakas["postitp"]}',
                maa      = '{$asiakas["maa"]}'
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND tunnus = {$ostotilaus["tunnus"]}";
  pupe_query($query);

  if ($toimipaikka && $toimipaikka > 0) {
    $query = "SELECT *
              FROM yhtion_toimipaikat
              WHERE yhtio = '{$kukarow["yhtio"]}'
                AND tunnus = {$toimipaikka}
              LIMIT 1";
    $result = pupe_query($query);
    $toimipaikkarow = mysql_fetch_assoc($result);

    if (!empty($toimipaikkarow)) {
      $query = "UPDATE lasku
                SET vanhatunnus   = {$toimipaikkarow["tunnus"]},
                    toim_nimi     = '{$toimipaikkarow["nimi"]}',
                    toim_nimitark = '{$yhtiorow["ovttunnus"]}-{$toimipaikkarow["ovtlisa"]}',
                    toim_osoite   = '{$toimipaikkarow["osoite"]}',
                    toim_postino  = '{$toimipaikkarow["postino"]}',
                    toim_postitp  = '{$toimipaikkarow["postitp"]}',
                    toim_maa      = '{$toimipaikkarow["maa"]}'
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND tunnus = {$ostotilaus["tunnus"]}";
      pupe_query($query);
    }
  }

  $kukarow['kesken'] = $ostotilaus['tunnus'];

  while ($hintacounter < $kokonaiskustannus) {
    $trow = yrityspeli_tuotearvonta($toimittaja['tunnus'], $try);

    if ($trow === false) {
      $response[] = "Yrityksellä {$yhtiorow['nimi']} ei ole sopivia tuotteita, jota voi tilata toimittajalta {$toimittaja['nimi']}.";

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

  // päivitetään tilaus valmiiksi
  $query = "UPDATE lasku SET alatila = 'A' WHERE tunnus='{$ostotilaus['tunnus']}'";
  $result = pupe_query($query);

  $response[] = "Tehtiin ostotilaus {$ostotilaus['tunnus']} yritykselle {$asiakas['nimi']}<br>";

  $params = array(
    'otunnus'        => $ostotilaus['tunnus'],
    'email'          => $asiakas['email'],
    'toimipaikkarow' => $toimipaikkarow,
  );

  $response[] = yrityspeli_tulosta_ostotilaus($params);

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
    return false;
  }

  $row = mysql_fetch_assoc($result);

  return $row;
}

function yrityspeli_tuotearvonta($toimittaja, $try = null) {
  global $kukarow, $yhtiorow;

  if ($try) {
    $trylisa = "AND tuote.try = '{$try}'";
  }
  else {
    $trylisa = "";
  }

  // katsotaan mitä tuotteita tältä toimittajalta voi tilata, ja arvotaan yksi
  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND status != 'P'
            AND myyntihinta  > 0
            AND tuotetyyppi NOT in ('A','B')
            {$trylisa}
            ORDER BY RAND() LIMIT 0, 1";
  $result = pupe_query($query);
  
  $tuote = mysql_fetch_assoc($result);
  $supplier_query = "SELECT *
                     FROM tuotteen_toimittajat
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tuoteno = '{$tuote['tuoteno']}'
                     AND liitostunnus = '{$toimittaja}'";
  $supplier_result = pupe_query($supplier_query);

  // jos tuoteella ei ole toimittajaa, laitetaan kantaan toimittajaksi
  if (mysql_num_rows($supplier_result) == 0) {
    $supplier_create_query = "INSERT INTO tuotteen_toimittajat SET
                  yhtio        = '{$kukarow['yhtio']}',
                  tuoteno      = '{$tuote['tuoteno']}',
                  liitostunnus = '{$toimittaja}',
                  laatija      = '{$kukarow['kuka']}',
                  ostohinta    = '{$tuote['myyntihinta']}',
                  luontiaika   = now(),
                  muutospvm    = now(),
                  muuttaja     = '{$kukarow['kuka']}'";
    pupe_query($supplier_create_query);  
  }
  $query = "SELECT tuote.*
            FROM tuote
            JOIN tuotteen_toimittajat on (tuotteen_toimittajat.yhtio = tuote.yhtio
              AND tuotteen_toimittajat.tuoteno = tuote.tuoteno
              AND tuotteen_toimittajat.liitostunnus = $toimittaja)
            WHERE tuote.yhtio = '{$kukarow['yhtio']}'
            AND tuote.status != 'P'
            AND tuote.myyntihinta  > 0
            AND tuote.tuotetyyppi NOT in ('A','B')
            {$trylisa}
            ORDER BY RAND() LIMIT 0, 1";
  $result = pupe_query($query);
  return mysql_fetch_assoc($result);
}

function yrityspeli_tulosta_ostotilaus(Array $params) {
  // komento pitää olla global, jotta tulosta_ostotilaus funkkarit saa siitä kiinni
  global $kukarow, $yhtiorow, $komento;

  $otunnus        = $params['otunnus'];
  $email          = $params['email'];
  $toimipaikkarow = $params['toimipaikkarow'];

  $kieli = 'fi';
  $komento = array('Ostotilaus' => "toimittajaemail{$email}");
  $silent = 'kyllä';
  $kukarow['toimipaikka'] = $toimipaikkarow['tunnus'];
  $yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);
  $nimitykset = "on!";
  require 'tilauskasittely/tulosta_ostotilaus.inc';

  return "Lähetettiin ostotilaus {$tunnus} sähköpostilla {$email}";
}
