<?php

// T‰ll‰ ohjelmalla voidaan generoida myyntitilauksia kuluvalle viikolle yrityksille joilla ei
// viel‰ sellaisia ole

require "../inc/parametrit.inc";
require "tilauskasittely/luo_myyntitilausotsikko.inc";

if (!isset($tee)) $tee = '';
if (!isset($painoarvo)) $painoarvo = 100;
if (!isset($tilausmaara)) $tilausmaara = 3;

$kauppakeskus_myyra = '003732419754';

if ($tee == 'GENEROI') {
  if (empty($valitut)) {
    echo "<br><font class='error'>".t('Et valinnut yht‰‰n yrityst‰')."</font><br><br>";
  }
  else {
    $tilaukset = generoi_myyntitilauksia($valitut, $painoarvo, $tilausmaara, $kauppakeskus_myyra);
    echo "<br>".t('Tilaukset luotu')."<br><br>";
  }

  $tee = '';
}

if (empty($tee)) {
  echo_yrityspeli_kayttoliittyma($painoarvo, $tilausmaara);
}

require "inc/footer.inc";

function echo_yrityspeli_kayttoliittyma($painoarvo, $tilausmaara) {
  global $yhtiorow, $kukarow;

  $tilauksettomat_yhtiot = hae_tilauksettomat_yhtiot();

  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='GENEROI'>";

  echo "<table>";
  echo "<tr>";
  echo "<th>".t('Tilausten painoarvo')."</th>";

  echo "<td>";
  echo "<input type='text' name='painoarvo' size='3' value='{$painoarvo}'/>%";
  echo "</td>";

  echo "<td class='back'>";
  echo "Painoarvo vaikuttaa tilaussumman suuruuteen. <br>
    Esim. normaalitilauksen summa on n. 1000 euroa (100%),<br>
    painoarvo 80 antaa tilaussummaksi n. 800 euroa (80%)<br>
    ja painoarvo 150 antaa tilaussummaksi n. 1500 euroa (150%).";
  echo "</td>";

  echo "</tr>";
  echo "<tr>";

  echo "<th>".t('Tilausten lukum‰‰r‰')."</th>";

  echo "<td>";
  echo "<input type='text' name='tilausmaara' size='2' value='${tilausmaara}'/>";
  echo "</td>";

  echo "<td class='back'>";
  echo "Montako automaattitilausta luodaan per yritys";
  echo "</td>";

  echo "</tr>";

  echo "</table>";
  echo "<br><br>";
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

  if (count($tilauksettomat_yhtiot) == 0) {
    echo t('Yht‰‰n tilauksetonta yrityst‰ ei lˆytynyt');
  }

  echo "</table>";
  echo "<br>";
  echo "<input type='submit' value='".t('Luo myyntitilauksia valituille yrityksille')."'>";
  echo "</form>";
}

function hae_tilauksettomat_yhtiot() {
  global $kukarow, $yhtiorow;

  $tilauksettomat_yhtiot = array();

  // Tarkastellaan aina onko kuluvalle viikolle luotu tilauksia
  $alkuaika = date("Y-m-d", strtotime('monday this week'));
  $loppuaika = date("Y-m-d", strtotime('sunday this week'));

  $query = "SELECT *
            FROM yhtio
            WHERE yhtio NOT IN ('{$kukarow['yhtio']}')";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    $tilausquery = "SELECT count(*) as tilauksia
                    FROM lasku
                    WHERE lasku.yhtio = '{$row['yhtio']}'
                    AND lasku.tila IN ('N','L')
                    AND lasku.luontiaika BETWEEN '$alkuaika' AND '$loppuaika'";
    $tilausresult = pupe_query($tilausquery);
    $tilausrow = mysql_fetch_assoc($tilausresult);

    if ($tilausrow['avoimia_tilauksia'] == 0) {
      $tilauksettomat_yhtiot[] = $row['yhtio'];
    }
  }

  return $tilauksettomat_yhtiot;
}

function generoi_myyntitilauksia($yhtiot, $painoarvo, $tilausmaara, $kauppakeskus_myyra) {
  foreach ($yhtiot as $yhtio) {
    $asiakas = hae_oletusasiakkuus($yhtio, $kauppakeskus_myyra);

    if (empty($asiakas)) {
      continue;
    }

    for ($i=0; $i < $tilausmaara; $i++) {
      luo_tilausotsikot_ja_tilausrivit($yhtio, $asiakas, $painoarvo);
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

function luo_tilausotsikot_ja_tilausrivit($yhtio, $asiakas, $painoarvo) {
  global $yhtiorow, $kukarow;

  $painoarvokerroin   = $painoarvo / 100;
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

  // Lis‰t‰‰n tuotteet
  $tuoteriveja = rand(1, 3);

  // Painoarvo, vakio 1, => Tilauksen kokonaisarvo n. 1000 euroa
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
  require "tilauskasittely/tilaus-valmis.inc";

  $yhtiorow = $alkuperainen_yhtio;
  $kukarow = $alkuperainen_kuka;

  $kukarow['kesken'] = '';
  $kukarow['yhtio'] = $alkuperainen_yhtio;
}

function tuotearvonta($yhtio) {
  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '{$yhtio}'
            ORDER BY RAND() LIMIT 0, 1";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}
