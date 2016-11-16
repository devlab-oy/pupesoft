<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }
  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

require '../inc/parametrit.inc';
require 'validation/Validation.php';

if ($tee == 'lataa_tiedosto') {
  $filepath = "/tmp/".$tmpfilenimi;
  if (file_exists($filepath)) {
    readfile($filepath);
    unlink($filepath);
  }
  else {
    echo "<font class='error'>".t("Tiedostoa ei ole olemassa")."</font>";
  }
  exit;
}

//ajax requestit tänne
if ($ajax_request) {

}

echo "<font class='head'>".t("Sarjanumerohistoria")."</font><hr>";
?>
<style>
  .date_input {
    width: 25px;
  }

  .date_year_input {
    width: 50px;
  }
</style>
<script>

</script>
<?php

$request = array(
  'tee'       => $tee,
  'sarjanumero'   => $sarjanumero,
  'asiakas'     => $asiakas,
  'asiakastunnus' => $asiakastunnus,
  'toimittaja'   => $toimittaja,
  'tuote'       => $tuote,
  'ppa'       => $ppa,
  'kka'       => $kka,
  'vva'       => $vva,
  'ppl'       => $ppl,
  'kkl'       => $kkl,
  'vvl'       => $vvl,
);

$request['alku_pvm'] = $request['ppa'].'.'.$request['kka'].'.'.$request['vva'];
$request['loppu_pvm'] = $request['ppl'].'.'.$request['kkl'].'.'.$request['vvl'];

//jos requestista ei tule päivää niin asetetaan tämäpäivä - 30
if (empty($request['ppa']) or empty($request['kka']) or empty($request['vva'])) {
  $request['alku_pvm'] = date('d.m.Y', strtotime('now - 30 day'));
  $pvm_array = explode('.', $request['alku_pvm']);
  $request['ppa'] = $pvm_array[0];
  $request['kka'] = $pvm_array[1];
  $request['vva'] = $pvm_array[2];
}

//jos requestista ei tule päivää niin asetetaan tämäpäivä
if (empty($request['ppl']) or empty($request['kkl']) or empty($request['vvl'])) {
  $request['loppu_pvm'] = date('d.m.Y', strtotime('now'));
  $pvm_array = explode('.', $request['loppu_pvm']);
  $request['ppl'] = $pvm_array[0];
  $request['kkl'] = $pvm_array[1];
  $request['vvl'] = $pvm_array[2];
}

if ($request['tee'] == 'nayta_tilaus') {
  require 'naytatilaus.inc';
}
else {
  echo_kayttoliittyma($request);

  if ($request['tee'] == 'hae_tilaukset') {
    $validations = array(
      'sarjanro'   => 'mitavaan',
      'asiakas'   => 'mitavaan',
      'asiakastunnus'   => 'mitavaan',
      'toimittaja' => 'mitavaan',
      'tuote'     => 'mitavaan',
      'alku_pvm'   => 'paiva',
      'loppu_pvm'   => 'paiva'
    );
    $required = array('alku_pvm', 'loppu_pvm');

    $validator = new FormValidator($validations, $required);

    if ($validator->validate($request)) {
      $tilaukset = hae_tilaukset($request);
      //esitellään tilaus tyypit tässä jotta validaatio luokka ei yritä valitoida niitä.
      $request['tyypit'] = array(
        'L'   => t("Myyntitilaus"),
        'O'   => t("Ostotilaus"),
        'A'   => t("Työmääräys"),
      );
      echo_tilaukset_raportti($tilaukset, $request);
    }
    else {
      echo $validator->getScript();
    }
  }
}

function hae_tilaukset($request) {
  global $kukarow, $yhtiorow;

  $sarjanumero_where = "";
  if (!empty($request['sarjanumero'])) {
    $sarjanumero_where = " AND sarjanumeroseuranta.sarjanumero LIKE '%{$request['sarjanumero']}%' ";
  }

  $asiakas_where = "";
  if (!empty($request['asiakas'])) {
    $asiakas_where .= " AND asiakas.nimi LIKE '%{$request['asiakas']}%' ";
  }

  if (!empty($request['asiakastunnus'])) {
    $asiakas_where .= " AND asiakas.tunnus = '{$request['asiakastunnus']}' ";
  }

  $toimittaja_where = "";
  if (!empty($request['toimittaja'])) {
    $toimittaja_where = " AND toimi.nimi LIKE '%{$request['toimittaja']}%' ";
  }

  $tuoteno_where = "";
  if (!empty($request['tuote'])) {
    $tuoteno_where = " AND tilausrivi.nimitys LIKE '%{$request['tuote']}%' ";
  }

  $lasku_where = "";
  if ($request['alku_pvm'] and $request['loppu_pvm']) {
    //loppu_pvm + 1 day, koska queryssä between ja luontiaika on datetime
    $lasku_where = " AND lasku.luontiaika BETWEEN '".date('Y-m-d', strtotime($request['alku_pvm']))."' AND '".date('Y-m-d', strtotime($request['loppu_pvm'].' + 1 day'))."'";
  }

  $queryt = array();
  //jos haetaan asiakkaita haetaan vain myyntitilauksia ja työmääräyksiä koska ostotilauksilla ei ole asiakasta
  if (!empty($request['asiakas']) or !empty($request['asiakastunnus'])) {
    $queryt[] = "  (
            SELECT lasku.tunnus,
            toimi.nimi as nimi,
            lasku.luontiaika,
            lasku.summa,
            'A' as tyyppi,
            sarjanumeroseuranta.sarjanumero as sarjanumero,
            tilausrivi.nimitys as tuote
            FROM sarjanumeroseuranta
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
              AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
              AND tilausrivi.tyyppi = 'L'
              {$tuoteno_where} )
            JOIN lasku
            ON ( lasku.yhtio = tilausrivi.yhtio
              AND lasku.tunnus = tilausrivi.otunnus
              {$lasku_where} )
            JOIN toimi
            ON ( toimi.yhtio = lasku.yhtio
              AND toimi.tunnus = lasku.liitostunnus
              {$toimittaja_where} )
            WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
            {$sarjanumero_where}
            )";
    $queryt[] = "  (
            SELECT lasku.tunnus,
            asiakas.nimi as nimi,
            lasku.luontiaika,
            lasku.summa,
            'L' as tyyppi,
            sarjanumeroseuranta.sarjanumero as sarjanumero,
            tilausrivi.nimitys as tuote
            FROM sarjanumeroseuranta
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
              AND tilausrivi.tunnus = sarjanumeroseuranta.myyntirivitunnus
              AND tilausrivi.tyyppi = 'L'
              {$tuoteno_where} )
            JOIN lasku
            ON ( lasku.yhtio = tilausrivi.yhtio
              AND lasku.tunnus = tilausrivi.otunnus
              {$lasku_where} )
            JOIN asiakas
            ON ( asiakas.yhtio = lasku.yhtio
              AND asiakas.tunnus = lasku.liitostunnus
              {$asiakas_where} )
            WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
            {$sarjanumero_where}
            )";
  }

  //jos haetaan toimittajia haetaan vain ostotilauksia koska myyntitilauksia ja työmääräyksiä ei ole toimittajia
  if (!empty($request['toimittaja'])) {
    $queryt[] = "  (
            SELECT lasku.tunnus,
            toimi.nimi as nimi,
            lasku.luontiaika,
            lasku.summa,
            'O' as tyyppi,
            sarjanumeroseuranta.sarjanumero as sarjanumero,
            tilausrivi.nimitys as tuote
            FROM sarjanumeroseuranta
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
              AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
              AND tilausrivi.tyyppi = 'O'
              {$tuoteno_where} )
            JOIN lasku
            ON ( lasku.yhtio = tilausrivi.yhtio
              AND lasku.tunnus = tilausrivi.otunnus
              {$lasku_where} )
            JOIN toimi
            ON ( toimi.yhtio = lasku.yhtio
              AND toimi.tunnus = lasku.liitostunnus
              {$toimittaja_where} )
            WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
            {$sarjanumero_where}
            )";
  }

  if (!empty($queryt)) {
    foreach ($queryt as $q) {
      $query = $q.' UNION';
    }
    //poistetaan vika " UNION"
    $query = substr($query, 0, -6);
  }
  else {
    //jos queryt on empty asiakkaaseen tai toimittajaan ei ole syötetty mitään. tällöin haetaan kaikista tilaustyypeistä
    $query = "  (
            SELECT lasku.tunnus,
            asiakas.nimi as nimi,
            lasku.luontiaika,
            lasku.summa,
            'L' as tyyppi,
            sarjanumeroseuranta.sarjanumero as sarjanumero,
            tilausrivi.nimitys as tuote
            FROM sarjanumeroseuranta
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
              AND tilausrivi.tunnus = sarjanumeroseuranta.myyntirivitunnus
              AND tilausrivi.tyyppi = 'L'
              {$tuoteno_where} )
            JOIN lasku
            ON ( lasku.yhtio = tilausrivi.yhtio
              AND lasku.tunnus = tilausrivi.otunnus
              {$lasku_where} )
            JOIN asiakas
            ON ( asiakas.yhtio = lasku.yhtio
              AND asiakas.tunnus = lasku.liitostunnus
              {$asiakas_where} )
            WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
            {$sarjanumero_where}
            )
            UNION
            (
            SELECT lasku.tunnus,
            toimi.nimi as nimi,
            lasku.luontiaika,
            lasku.summa,
            'A' as tyyppi,
            sarjanumeroseuranta.sarjanumero as sarjanumero,
            tilausrivi.nimitys as tuote
            FROM sarjanumeroseuranta
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
              AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
              AND tilausrivi.tyyppi = 'L'
              {$tuoteno_where} )
            JOIN lasku
            ON ( lasku.yhtio = tilausrivi.yhtio
              AND lasku.tunnus = tilausrivi.otunnus
              {$lasku_where} )
            JOIN toimi
            ON ( toimi.yhtio = lasku.yhtio
              AND toimi.tunnus = lasku.liitostunnus
              {$toimittaja_where} )
            WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
            {$sarjanumero_where}
            )
            UNION
            (
            SELECT lasku.tunnus,
            toimi.nimi as nimi,
            lasku.luontiaika,
            lasku.summa,
            'O' as tyyppi,
            sarjanumeroseuranta.sarjanumero as sarjanumero,
            tilausrivi.nimitys as tuote
            FROM sarjanumeroseuranta
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = sarjanumeroseuranta.yhtio
              AND tilausrivi.tunnus = sarjanumeroseuranta.ostorivitunnus
              AND tilausrivi.tyyppi = 'O'
              {$tuoteno_where} )
            JOIN lasku
            ON ( lasku.yhtio = tilausrivi.yhtio
              AND lasku.tunnus = tilausrivi.otunnus
              {$lasku_where} )
            JOIN toimi
            ON ( toimi.yhtio = lasku.yhtio
              AND toimi.tunnus = lasku.liitostunnus
              {$toimittaja_where} )
            WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
            {$sarjanumero_where}
            )";
  }
  $query = $query."ORDER BY tyyppi, luontiaika";
  $result = pupe_query($query);

  $tilaukset = array();
  while ($tilaus = mysql_fetch_assoc($result)) {
    $tilaukset[] = $tilaus;
  }

  return $tilaukset;
}

function echo_tilaukset_raportti($tilaukset, $request = array()) {
  global $kukarow, $yhtiorow, $palvelin2;

  $lopetus = "{$_SERVER['PHP_SELF']}////tee=hae_tilaukset//sarjanumero={$request['sarjanumero']}//asiakas={$request['asiakas']}//asiakastunnus={$request['asiakastunnus']}//toimittaja={$request['toimittaja']}//tuote={$request['tuote']}//ppa={$request['ppa']}//kka={$request['kka']}//vva={$request['vva']}//ppl={$request['ppl']}//kkl={$request['kkl']}//vvl={$request['vvl']}";

  echo "<table>";
  echo "<thead>";
  echo "<tr>";
  echo "<th>".t('Tilausnumero')."</th>";
  echo "<th>".t('Asiakkaan')." / ".t('toimittajan nimi')."</th>";
  echo "<th>".t('Tuote')."</th>";
  echo "<th>".t('Sarjanumero')."</th>";
  echo "<th>".t('Luontiaika')."</th>";
  echo "<th>".t('Summa')."</th>";
  echo "<th>".t('Tyyppi')."</th>";
  echo "</tr>";
  echo "</thead>";
  foreach ($tilaukset as $tilaus) {
    echo "<tr class='aktiivi'>";
    echo "<td><a href='sarjanumerohistoria.php?tee=nayta_tilaus&tunnus={$tilaus['tunnus']}&lopetus={$lopetus}'>{$tilaus['tunnus']}</a></td>";
    echo "<td>{$tilaus['nimi']}</td>";
    echo "<td>{$tilaus['tuote']}</td>";
    echo "<td>";
    echo "<a href='{$palvelin2}tilauskasittely/sarjanumeroseuranta.php?indexvas=1&sarjanumero_haku={$tilaus['sarjanumero']}&lopetus={$lopetus}'>{$tilaus['sarjanumero']}</a>";
    echo "</td>";
    echo "<td>".tv1dateconv($tilaus['luontiaika'])."</td>";
    echo "<td td align='right'>{$tilaus['summa']}</td>";
    echo "<td>{$request['tyypit'][$tilaus['tyyppi']]}</td>";
    echo "</tr>";
  }
  echo "</table>";
}

function echo_kayttoliittyma($request) {
  global $kukarow, $yhtiorow;

  echo "<form action='' method='POST'>";
  echo "<input type='hidden' name='tee' value='hae_tilaukset' />";
  echo "<table>";

  echo "<tr>";
  echo "<th>".t("Sarjanumero")."</th>";
  echo "<td>";
  echo "<input type='text' name='sarjanumero' value='{$request['sarjanumero']}' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Asiakas")."</th>";
  echo "<td>";
  echo "<input type='text' name='asiakas' value='{$request['asiakas']}' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Toimittaja")."</th>";
  echo "<td>";
  echo "<input type='text' name='toimittaja' value='{$request['toimittaja']}' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Tuote")."</th>";
  echo "<td>";
  echo "<input type='text' name='tuote' value='{$request['tuote']}' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Päivämääräväli")."</th>";
  echo "<td>";
  echo "<input type='text' class='date_input' name='ppa' value='{$request['ppa']}' />";
  echo " ";
  echo "<input type='text' class='date_input' name='kka' value='{$request['kka']}' />";
  echo " ";
  echo "<input type='text' class='date_year_input' name='vva' value='{$request['vva']}' />";

  echo " - ";

  echo "<input type='text' class='date_input' name='ppl' value='{$request['ppl']}' />";
  echo " ";
  echo "<input type='text' class='date_input' name='kkl' value='{$request['kkl']}' />";
  echo " ";
  echo "<input type='text' class='date_year_input' name='vvl' value='{$request['vvl']}' />";

  echo "</td>";
  echo "</tr>";

  echo "</table>";
  echo "<br/>";
  echo "<input type='submit' value='".t('Hae')."' />";
  echo "</form><br><br>";
}

require 'inc/footer.inc';
