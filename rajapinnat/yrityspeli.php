<?php

// T‰m‰ ohjelma generoi myyntitilauksia kaikille yrityksille joilla ei niit‰ entuudestaan ole

// Kutsutaanko CLI:st‰
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

$pupe_root_polku = dirname(dirname(__FILE__));

require "{$pupe_root_polku}/inc/connect.inc";
require "{$pupe_root_polku}/inc/functions.inc";
require_once "{$pupe_root_polku}/tilauskasittely/luo_myyntitilausotsikko.inc";

if (!$php_cli) {                                  
  require "{$pupe_root_polku}/inc/parametrit.inc";

  if (!isset($tee)) $tee = '';

  if ($tee == 'GENEROI') {
    if (empty($valitut)) {
      echo "<br><font class='error'>".t('Et valinnut yht‰‰n yrityst‰')."</font><br><br>";
    }
    else {
      $tilaukset = generoi_myyntitilauksia($valitut);
      echo "<br>".t('Tilaukset luotu')."<br><br>";
    }

    $tee = '';
  }

  if (empty($tee)) {
    echo_yrityspeli_kayttoliittyma();
  }

  require ("{$pupe_root_polku}/inc/footer.inc");
}

function echo_yrityspeli_kayttoliittyma() {
  global $yhtiorow, $kukarow;

  $tilauksettomat_yhtiot = hae_tilauksettomat_yhtiot();

  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='GENEROI'>";

  echo "<table>";
  echo "<tr>";
  
  echo "</tr>";
  echo "</table>";

  echo "<table>";
  echo "<tr>";
  echo "<th colspan='10'>".t('Valitse yritykset')."</th>";
  echo "</tr>";

  foreach ($tilauksettomat_yhtiot as $tilaukseton_yhtio) {
    echo "<tr>";
    echo "<td>$tilaukseton_yhtio</td>";
    echo "<td><input type='checkbox' name='valitut[]' value='{$tilaukseton_yhtio}'/></td>";
    echo "</tr>";
  }

  echo "</table>";
  echo "<br>";
  echo "<input type='submit' value='".t('Luo myyntitilauksia valituille yrityksille')."'>";
  echo "</form>";
}

function hae_tilauksettomat_yhtiot() {
  global $yhtiorow;

  $tilauksettomat_yhtiot = array();

  $query = "SELECT * 
            FROM yhtio
            WHERE yhtio NOT IN ('{$yhtiorow['yhtio']}')";
  $result = pupe_query($query);
  while ($row = mysql_fetch_assoc($result)) {
    $tilausquery = "SELECT count(distinct lasku.tunnus) avoimia_tilauksia 
                    FROM lasku
                    JOIN tilausrivi 
                     ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus) 
                    WHERE lasku.yhtio = '{$row['yhtio']}' 
                    AND lasku.tila = 'N'";
    $tilausresult = pupe_query($tilausquery);
    $tilausrow = mysql_fetch_assoc($tilausresult);
    if (empty($tilausrow['avoimia_tilauksia'])) {
      $tilauksettomat_yhtiot[] = $tilausrow['yhtio'];
    }
  }
$tilauksettomat_yhtiot[] = 'signa';
$tilauksettomat_yhtiot[] = 'signa';
  return $tilauksettomat_yhtiot;
}

function generoi_myyntitilauksia($yhtiot) {
  foreach ($yhtiot as $yhtio) {
    $asiakas = hae_oletusasiakkuus($yhtio);
    if (!empty($asiakas)) {
      for ($i=0; $i < 3; $i++) { 
        luo_tilausotsikot_ja_tilausrivit($yhtio, $asiakas);
      }
    }
  }
}

function hae_oletusasiakkuus($yhtio) {
  $query = "SELECT * 
            FROM asiakas
            WHERE /*yhtio = '{$yhtio}'
            AND*/ ovttunnus = '003718733595'
            LIMIT 1";
  $result = pupe_query($query);
  return mysql_fetch_assoc($result);
}

function luo_tilausotsikot_ja_tilausrivit($yhtio, $asiakas) {
  global $kukarow;

  $alkuperainen_yhtio = $kukarow['yhtio'];
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

  // Lis‰t‰‰n tuotteet
  $tuoteriveja = rand(1, 3);
  $painoarvokerroin = 1;

  // Painoarvo, vakio 1000 eur = 1 = 100%
  $kokonaiskustannus = 1000 * $painoarvokerroin;

  $hintacounter = 0;
  while ($hintacounter < $kokonaiskustannus) {
    $trow = tuotearvonta($yhtio);
    $hinta = rand(55, 122);
    $kpl = rand(1,3);

    $hintacounter += ($hinta * $kpl);
    $params = array(
      'trow' => $trow,
      'laskurow' => $laskurow,
      'tuoteno' => $trow['tuoteno'],
      'hinta' => $hinta,
      'kpl' => $kpl,
      'varataan_saldoa' => 'EI',
      'alv' => 0.0
    );

    lisaa_rivi($params);
  }

  $pupe_root_polku = dirname(dirname(__FILE__));
  // Tilaus valmiiksi
  require "{$pupe_root_polku}/tilauskasittely/tilaus-valmis.inc";

  $kukarow['kesken'] = '';
  $kukarow['yhtio'] = $alkuperainen_yhtio;
  return;
}

function tuotearvonta($yhtio) {
  $query = "SELECT * 
            FROM tuote 
            WHERE yhtio = '{$yhtio}' 
            ORDER BY RAND() LIMIT 0,1";
  $result = pupe_query($query);
  return mysql_fetch_assoc($result);
}
