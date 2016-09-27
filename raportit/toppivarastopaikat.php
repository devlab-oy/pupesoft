<?php

// Enabloidaan, että Apache flushaa kaiken mahdollisen ruudulle kokoajan.
ini_set('implicit_flush', 1);
ob_implicit_flush(1);

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 2;

// Ei käytetä pakkausta
$compression = FALSE;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }
  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

require "../inc/parametrit.inc";

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

require 'inc/pupeExcel.inc';
require 'inc/ProgressBar.class.php';

ini_set("memory_limit", "5G");
?>
<script type="text/javascript">
  function tarkista() {
    var saako_submit = true;

    var all_values = $(".ahylly").map(function() {
      return $(this).val();
    }).get();
    var ahylly_not_empty_values = all_values.filter(function(v) {
      return v !== ''
    });

    all_values = $(".lhylly").map(function() {
      return $(this).val();
    }).get();
    var lhylly_not_empty_values = all_values.filter(function(v) {
      return v !== ''
    });

    //jos ei olla rajattu ahylly, lhylly tai varastolla
    if ((ahylly_not_empty_values.length === 0 && lhylly_not_empty_values.length === 0) && $('.varastot:checked').length === 0) {
      alert($('#valitse_varasto').html());
      saako_submit = false;
    }

    return saako_submit;
  }
</script>

<?php

echo "<div id='valitse_varasto' style='display:none;'>".t("Valitse varasto tai rajaa varastopaikalla")."</div>";

echo "<font class='head'>".t("Varastopaikkojen keräysseuranta")."</font><hr>";

$kaikki_lisa_kentat = array(
  0   => array(
    'kolumni'   => 'tuote.tuotekorkeus',
    'header'   => t('Tuotekorkeus'),
    'checked'   => '',
  ),
  1   => array(
    'kolumni'   => 'tuote.tuoteleveys',
    'header'   => t('Tuoteleveys'),
    'checked'   => '',
  ),
  2   => array(
    'kolumni'   => 'tuote.tuotesyvyys',
    'header'   => t('Tuotesyvyys'),
    'checked'   => '',
  ),
  3   => array(
    'kolumni'   => 'tuote.tuotemassa',
    'header'   => t('Tuotemassa'),
    'checked'   => '',
  ),
  4   => array(
    'kolumni'   => 'tuote.status',
    'header'   => t('Status'),
    'checked'   => '',
  ),
  5   => array(
    'kolumni'   => 'tuote.luontiaika',
    'header'   => t('Luontiaika'),
    'checked'   => '',
  ),
  6   => array(
    'kolumni'   => 'tuote.tuoteno',
    'header'   => t('Tuotenumero'),
    'checked'   => ($tee == '' ? "checked='checked'" : ''),
  ),
  7   => array(
    'kolumni'   => 'tuote.nimitys',
    'header'   => t('Nimitys'),
    'checked'   => ($tee == '' ? "checked='checked'" : ''),
  ),
  8   => array(
    'kolumni'   => 'tuote.ostoehdotus',
    'header'   => t('Ostoehdotus'),
    'checked'   => '',
  ),
);

//for looppi vain sen takia, että saadaan synkattua formista valitut kentät, mahdollisien kenttien kanssa
foreach ($kaikki_lisa_kentat as $kentat_index => &$kentat_value) {
  if ((isset($lisa_kentat) and in_array($kentat_index, $lisa_kentat)) or (!empty($kentat_value['checked']))) {
    $kentat_value['checked'] = "checked='checked'";
  }
  else {
    $kentat_value['checked'] = "";
  }
}

// Ajetaan raportti
if ($tee != '') {

  $apaikka = strtoupper(sprintf("%-05s", $ahyllyalue)).strtoupper(sprintf("%05s", $ahyllynro)).strtoupper(sprintf("%05s", $ahyllyvali)).strtoupper(sprintf("%05s", $ahyllytaso));
  $lpaikka = strtoupper(sprintf("%-05s", $lhyllyalue)).strtoupper(sprintf("%05s", $lhyllynro)).strtoupper(sprintf("%05s", $lhyllyvali)).strtoupper(sprintf("%05s", $lhyllytaso));

  $apaikka = array(
    'ahyllyalue' => $ahyllyalue,
    'ahyllynro'   => $ahyllynro,
    'ahyllyvali' => $ahyllyvali,
    'ahyllytaso' => $ahyllytaso,
  );

  $lpaikka = array(
    'lhyllyalue' => $lhyllyalue,
    'lhyllynro'   => $lhyllynro,
    'lhyllyvali' => $lhyllyvali,
    'lhyllytaso' => $lhyllytaso,
  );

  $lisa = ($toppi != '' and is_numeric($toppi)) ? " LIMIT $toppi " : "";

  $header_values = array(
    'tuoteno'           => array(
      'header' => t('Tuoteno'),
      'order'   => 0
    ),
    'nimitys'           => array(
      'header' => t('Tuotteen nimi'),
      'order'   => 10
    ),
    'varaston_nimitys'       => array(
      'header' => t('Varasto'),
      'order'   => 20
    ),
    'keraysvyohykkeen_nimitys'   => array(
      'header' => t('Keräysvyöhyke'),
      'order'   => 30
    ),
    'hylly'             => array(
      'header' => t('Varastopaikka'),
      'order'   => 40
    ),
    'saldo'            => array(
      'header' => t('Saldo'),
      'order' => 50
    ),
    'kpl_valittu_aika'       => array(
      'header' => t('Keräystä'),
      'order'   => 60
    ),
    'kpl_valittu_aika_pvm'     => array(
      'header' => t('Keräystä/Päivä'),
      'order'   => 70
    ),
    'kpl_kerays'         => array(
      'header' => t('Kpl/Keräys'),
      'order'   => 80
    ),
    'kpl_6'             => array(
      'header' => t('Keräystä tästä päivästä 6kk'),
      'order'   => 90
    ),
    'kpl_12'           => array(
      'header' => t('Keräystä tästä päivästä 12kk'),
      'order'   => 100
    ),
    'poistettu'           => array(
      'header' => t('Poistettu varastopaikka'),
      'order'   => 110
    ),
    'tuotekorkeus'         => array(
      'header' => t('Tuotteen korkeus'),
      'order'   => 11
    ),
    'tuoteleveys'         => array(
      'header' => t('Tuotteen leveys'),
      'order'   => 12
    ),
    'tuotesyvyys'         => array(
      'header' => t('Tuotteen syvyys'),
      'order'   => 13
    ),
    'tuotemassa'         => array(
      'header' => t('Tuotteen massa'),
      'order'   => 14
    ),
    'status'           => array(
      'header' => t('Status'),
      'order'   => 15
    ),
    'luontiaika'         => array(
      'header' => t('Tuotteen Luontiaika'),
      'order'   => 16
    ),
    'ostoehdotus'         => array(
      'header' => t('Ostoehdotus'),
      'order'   => 17
    ),
  );
  $force_to_string = array(
    'tuoteno'
  );

  if (!empty($summaa_varastopaikalle)) {
    list($rivit, $saldolliset) = hae_rivit("PAIKKA", $kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka, $varastot, $keraysvyohykkeet, $kaikki_lisa_kentat, $kerayksettomat_tuotepaikat, $lisa);
  }
  else {
    list($rivit, $saldolliset) = hae_rivit("TUOTE", $kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka, $varastot, $keraysvyohykkeet, $kaikki_lisa_kentat, $kerayksettomat_tuotepaikat, $lisa);
  }

  if (count($rivit) > 0) {

    if (!empty($tee_excel)) {
      $xls_filename = generoi_excel_tiedosto($rivit, $header_values, $force_to_string);
      echo_tallennus_formi($xls_filename);
    }

    nayta_ruudulla($rivit, $header_values, $force_to_string, $ppa, $kka, $vva, $ppl, $kkl, $vvl, 'right_align_numbers');
  }
  else {
    echo "<br><font class='error'>".t("Yhtään keräystä ei löytynyt")."</font><br><br>";
  }

  if (count($saldolliset) > 0) {
    echo_tulosta_inventointilista($saldolliset);
  }

  $tee = "";
}

// Käyttöliittymä
if ($tee == '') {

  // ehdotetaan 7 päivää taaksepäin
  if (!isset($kka))                     $kka = date("m", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));
  if (!isset($vva))                     $vva = date("Y", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));
  if (!isset($ppa))                     $ppa = date("d", mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));
  if (!isset($kkl))                     $kkl = date("m");
  if (!isset($vvl))                     $vvl = date("Y");
  if (!isset($ppl))                     $ppl = date("d");
  if (!isset($tee))                     $tee = "";
  if (!isset($toppi))                   $toppi = "";
  if (!isset($ahyllyalue))              $ahyllyalue = "";
  if (!isset($ahyllynro))               $ahyllynro = "";
  if (!isset($ahyllyvali))              $ahyllyvali = "";
  if (!isset($ahyllytaso))              $ahyllytaso = "";
  if (!isset($lhyllyalue))              $lhyllyalue = "";
  if (!isset($lhyllynro))               $lhyllynro = "";
  if (!isset($lhyllyvali))              $lhyllyvali = "";
  if (!isset($lhyllytaso))              $lhyllytaso = "";
  if (!isset($summaa_varastopaikalle))  $summaa_varastopaikalle = "";
  if (!isset($tee_excel))               $tee_excel = "";

  $query = "SELECT tunnus, nimitys
            FROM varastopaikat
            WHERE yhtio       = '{$kukarow['yhtio']}'
                  AND tyyppi != 'P'
            ORDER BY nimitys ASC";
  $result = pupe_query($query);
  $kaikki_varastot = array();

  while ($varasto = mysql_fetch_assoc($result)) {
    if (isset($varastot) and in_array($varasto['tunnus'], $varastot)) {
      $checked = "checked = 'checked'";
    }
    else {
      $checked = "";
    }
    $kaikki_varastot[$varasto['tunnus']] = array(
      'nimitys'   => $varasto['nimitys'],
      'checked'   => $checked,
    );
  }

  if ($yhtiorow['kerayserat'] == "K") {
    $query = "SELECT tunnus, nimitys
              FROM keraysvyohyke
              WHERE yhtio  = '{$kukarow['yhtio']}'
              AND nimitys != ''";
    $result = pupe_query($query);

    $kaikki_keraysvyohykkeet = array();

    while ($keraysvyohyke = mysql_fetch_assoc($result)) {
      if (isset($keraysvyohykkeet) and in_array($keraysvyohyke['tunnus'], $keraysvyohykkeet)) {
        $checked = "checked = 'checked'";
      }
      else {
        $checked = "";
      }
      $kaikki_keraysvyohykkeet[$keraysvyohyke['tunnus']] = array(
        'nimitys'   => $keraysvyohyke['nimitys'],
        'checked'   => $checked,
      );
    }
  }



  echo "<br>";
  echo "<form method='POST'>";

  echo "<table>";
  echo "<input type='hidden' name='tee' value='kaikki' />";

  $checked = empty($summaa_varastopaikalle) ? '' : 'checked = "checked"';

  echo "<tr>";
  echo "<th>".t('Summaa per varasto')."/".t('keräysvyöhyke')."</th>";
  echo "<td><input type='checkbox' name='summaa_varastopaikalle' $checked /></td>";
  echo "</tr>";

  $checked = empty($kerayksettomat_tuotepaikat) ? '' : 'checked = "checked"';

  echo "<tr>";
  echo "<th>".t("Näytä myös ne tuotepaikat joilta ei ole keräyksiä")."</th>";
  echo "<td><input type='checkbox' name='kerayksettomat_tuotepaikat' {$checked}/> (".t("Lasketaan keräykset vain aktiivisilta tuotepaikoilta").")</td>";
  echo "</tr>";

  echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppa' value='$ppa' size='3'>
      <input type='text' name='kka' value='$kka' size='3' />
      <input type='text' name='vva' value='$vva' size='5' /></td>
      </tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppl' value='$ppl' size='3' />
      <input type='text' name='kkl' value='$kkl' size='3' />
      <input type='text' name='vvl' value='$vvl' size='5' /></td>";

  echo "<tr><th>".t("Anna alkuvarastopaikka").":</th>
      <td>", hyllyalue("ahyllyalue", $ahyllyalue), "
      <input class='ahylly' type='text' size='6' name='ahyllynro' value='$ahyllynro' />
      <input class='ahylly' type='text' size='6' name='ahyllyvali' value='$ahyllyvali' />
      <input class='ahylly' type='text' size='6' name='ahyllytaso' value='$ahyllytaso' />
      </td></tr>";

  echo "<tr><th>".t("ja loppuvarastopaikka").":</th>
      <td>", hyllyalue("lhyllyalue", $lhyllyalue), "
      <input class='lhylly' type='text' size='6' name='lhyllynro' value='$lhyllynro' />
      <input class='lhylly' type='text' size='6' name='lhyllyvali' value='$lhyllyvali' />
      <input class='lhylly' type='text' size='6' name='lhyllytaso' value='$lhyllytaso' />
      </td></tr>";

  echo "<tr><th>".t("Listaa vain näin monta kerätyintä tuotetta").":</th>
      <td><input type='text' size='6' name='toppi' value='$toppi' /></td>";

  echo "<tr>";
  echo "<th>".t("Varastot")."</th>";
  echo "<td>";

  foreach ($kaikki_varastot as $varasto_index => $varasto) {
    echo "<input class='varastot' type='checkbox' name='varastot[]' value='{$varasto_index}' {$varasto['checked']} />";
    echo " {$varasto['nimitys']}";
    echo "<br/>";
  }
  echo "</td>";
  echo "</tr>";

  if ($yhtiorow['kerayserat'] == "K" and !empty($kaikki_keraysvyohykkeet)) {
    echo "<tr>";
    echo "<th>".t("Keräysvyöhykkeet")."</th>";

    echo "<td>";
    foreach ($kaikki_keraysvyohykkeet as $keraysvyohykkeet_index => $keraysvyohykkeet) {
      echo "<input class='keraysvyohykkeet' type='checkbox' name='keraysvyohykkeet[]' value='{$keraysvyohykkeet_index}' {$keraysvyohykkeet['checked']} />";
      echo " {$keraysvyohykkeet['nimitys']}";
      echo "<br/>";
    }
    echo "</td>";

    echo "</tr>";
  }

  echo "<tr>";
  echo "<th>".t("Lisäkentät")."</th>";

  echo "<td>";

  foreach ($kaikki_lisa_kentat as $lisa_kentat_index => $lisa_kentat) {
    echo "<input class='keraysvyohykkeet' type='checkbox' name='lisa_kentat[]' value='{$lisa_kentat_index}' {$lisa_kentat['checked']} />";
    echo " {$lisa_kentat['header']}";
    echo "<br/>";
  }
  echo "</td>";
  echo "<td class='back'>".t("Ei käytössä jos summataan per varasto/keräysvyöhyke")."</td>";
  echo "</tr>";

  $checked = empty($tee_excel) ? '' : 'checked = "checked"';

  echo "<tr>";
  echo "<th>".t('Tee Excel')."</th>";
  echo "<td><input type='checkbox' name='tee_excel' $checked /></td></tr>";

  echo "</table>";
  echo '<br/>';
  echo "<input type='submit' value='".t("Aja raportti")."' onclick='return tarkista();'/>";
  echo '</form>';
}

function hae_rivit($tyyppi, $kukarow, $vva, $kka, $ppa, $vvl, $kkl, $ppl, $apaikka, $lpaikka, $varastot, $keraysvyohykkeet, $lisa_kentat, $kerayksettomat_tuotepaikat, $lisa) {
  global $yhtiorow;

  $ostoehdotukset = array(
    ''   => t("Ehdotetaan ostoehdotusohjelmissa tilattavaksi"),
    'E'   => ("Ei ehdoteta ostoehdotusohjelmissa tilattavaksi"),
  );

  if (strtotime("$vva-$kka-$ppa") < strtotime('now - 12 months')) {
    $_date = "AND tilausrivi.kerattyaika >= '$vva-$kka-$ppa 00:00:00'
          AND tilausrivi.kerattyaika <= '$vvl-$kkl-$ppl 23:59:59'";
  }
  else {
    $_date = "AND tilausrivi.kerattyaika >= Date_sub(CURRENT_DATE, INTERVAL 12 month)";
  }

  $tuotepaikka_where = "";
  $a = array_filter($apaikka);
  $l = array_filter($lpaikka);

  if (!empty($a) or !empty($l)) {
    $ahyllyalue = $apaikka['ahyllyalue'];
    $ahyllynro = $apaikka['ahyllynro'];
    $ahyllyvali = $apaikka['ahyllyvali'];
    $ahyllytaso = $apaikka['ahyllytaso'];
    $lhyllyalue = $lpaikka['lhyllyalue'];
    $lhyllynro = $lpaikka['lhyllynro'];
    $lhyllyvali = $lpaikka['lhyllyvali'];
    $lhyllytaso = $lpaikka['lhyllytaso'];

    $tuotepaikka_where = "and concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'),lpad(upper(tilausrivi.hyllyvali) ,5,'0'),lpad(upper(tilausrivi.hyllytaso) ,5,'0')) >=
          concat(rpad(upper('$ahyllyalue'), 5, '0'),lpad(upper('$ahyllynro'), 5, '0'),lpad(upper('$ahyllyvali'), 5, '0'),lpad(upper('$ahyllytaso'),5, '0'))
          and concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'),lpad(upper(tilausrivi.hyllyvali) ,5,'0'),lpad(upper(tilausrivi.hyllytaso) ,5,'0')) <=
          concat(rpad(upper('$lhyllyalue'), 5, '0'),lpad(upper('$lhyllynro'), 5, '0'),lpad(upper('$lhyllyvali'), 5, '0'),lpad(upper('$lhyllytaso'),5, '0'))";
  }

  $varasto_lisa1 = "";
  $varasto_lisa2 = "";

  if (!empty($varastot)) {
    $varasto_lisa1 = " AND tuotepaikat.varasto IN (".implode(",", $varastot).") ";
    $varasto_lisa2 = " AND tilausrivi.varasto IN (".implode(",", $varastot).") ";
  }

  $tuote_select = "";
  $keraysvyohyke_select = "";
  $keraysvyohyke_join = "";
  $varaston_hyllypaikat_join = "";
  $group = ",";

  if ($yhtiorow['kerayserat'] == "K") {
    $keraysvyohyke_select = "keraysvyohyke.nimitys as keraysvyohykkeen_nimitys,";
    $keraysvyohyke_join = " JOIN keraysvyohyke ON (keraysvyohyke.yhtio = vh.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)";
    $varaston_hyllypaikat_join = " JOIN varaston_hyllypaikat AS vh
                    ON (
                      vh.yhtio = tilausrivi.yhtio
                      AND vh.hyllyalue = tilausrivi.hyllyalue
                      AND vh.hyllynro = tilausrivi.hyllynro
                      AND vh.hyllytaso = tilausrivi.hyllytaso
                      AND vh.hyllyvali = tilausrivi.hyllyvali";

    if (!empty($keraysvyohykkeet)) {
      $varaston_hyllypaikat_join .= "  AND vh.keraysvyohyke IN (".implode(",", $keraysvyohykkeet).")";
    }

    $varaston_hyllypaikat_join .= ")";

    $group .= "keraysvyohykkeen_nimitys,";
  }

  if ($tyyppi == "TUOTE") {
    $tuote_statukset = product_statuses();

    $checked_count = 0;

    if (!empty($lisa_kentat)) {
      foreach ($lisa_kentat as $lisa_kentta) {
        if (!empty($lisa_kentta['checked'])) {
          $tuote_select .= $lisa_kentta['kolumni'].', ';
          $group .= $lisa_kentta['kolumni'].', ';
          $checked_count++;
        }
      }

      // Ruksattiin jotain lisävalintoita (tuotekohtaisia), voidaan näyttää saldo
      if ($checked_count > 0) {
        $tuote_select .= "tuotepaikat.saldo,";
        $group .= "tuotepaikat.saldo,";
      }
    }

    $tuote_select .= "tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, ";
    $tuote_select .= "CONCAT_WS(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) as hylly, ";
    $tuote_select .= "group_concat(distinct tuotepaikat.tunnus) paikkatun, ";

    $group .= "tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, hylly,";

    if (empty($kerayksettomat_tuotepaikat)) {
      $tuote_select .= "if (tuotepaikat.tunnus IS NULL , 1, 0) poistettu, ";
      $group .= "poistettu,";
    }
  }

  $group = rtrim($group, " ,");

  if (!empty($kerayksettomat_tuotepaikat)) {

    $kerayksettomat_tuotepaikat_varaston_hyllypaikat_join = str_replace('tilausrivi', 'tuotepaikat', $varaston_hyllypaikat_join);
    $kerayksettomat_tuotepaikat_group = str_replace('tilausrivi', 'tuotepaikat', $group);
    $kerayksettomat_tuotepaikka_where = str_replace('tilausrivi', 'tuotepaikat', $tuotepaikka_where);
    $kerayksettomat_tuote_select = str_replace('tilausrivi', 'tuotepaikat', $tuote_select);

    $query = "SELECT varastopaikat.nimitys as varaston_nimitys,
              {$keraysvyohyke_select}
              {$kerayksettomat_tuote_select}
              sum(if (tilausrivi.kerattyaika >= '$vva-$kka-$ppa 00:00:00' AND tilausrivi.kerattyaika <= '$vvl-$kkl-$ppl 23:59:59', 1, 0)) kpl_valittu_aika,
              sum(if (tilausrivi.kerattyaika >= '$vva-$kka-$ppa 00:00:00' AND tilausrivi.kerattyaika <= '$vvl-$kkl-$ppl 23:59:59', tilausrivi.kpl+tilausrivi.varattu, 0)) tuokpl_valittu_aika,
              sum(if (tilausrivi.kerattyaika >= Date_sub(CURRENT_DATE, INTERVAL 6 month), 1, 0)) kpl_6,
              sum(if (tilausrivi.kerattyaika >= Date_sub(CURRENT_DATE, INTERVAL 6 month), tilausrivi.kpl+tilausrivi.varattu, 0)) tuo_kpl_6,
              sum(if (tilausrivi.kerattyaika >= Date_sub(CURRENT_DATE, INTERVAL 12 month), 1, 0)) kpl_12,
              sum(if (tilausrivi.kerattyaika >= Date_sub(CURRENT_DATE, INTERVAL 12 month), tilausrivi.kpl+tilausrivi.varattu, 0)) tuo_kpl_12
              FROM tuotepaikat
              JOIN tuote USE INDEX (tuoteno_index) ON (tuotepaikat.yhtio = tuote.yhtio
                AND tuotepaikat.tuoteno  = tuote.tuoteno
                AND tuote.ei_saldoa      = '')
              JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                AND varastopaikat.tunnus = tuotepaikat.varasto)
              {$kerayksettomat_tuotepaikat_varaston_hyllypaikat_join}
              {$keraysvyohyke_join}
              LEFT JOIN tilausrivi ON ( tilausrivi.tyyppi = 'L'
                AND tilausrivi.yhtio     = tuotepaikat.yhtio
                AND tilausrivi.hyllyalue = tuotepaikat.hyllyalue
                AND tilausrivi.hyllynro  = tuotepaikat.hyllynro
                AND tilausrivi.hyllyvali = tuotepaikat.hyllyvali
                AND tilausrivi.hyllytaso = tuotepaikat.hyllytaso
                AND tilausrivi.tuoteno   = tuotepaikat.tuoteno
                {$_date})
              WHERE tuotepaikat.yhtio    = '{$kukarow['yhtio']}'
              {$kerayksettomat_tuotepaikka_where}
              {$varasto_lisa1}
              GROUP BY 1
              {$kerayksettomat_tuotepaikat_group}
              ORDER BY kpl_valittu_aika DESC
              $lisa";
  }
  else {
    $query = "SELECT varastopaikat.nimitys as varaston_nimitys,
              {$keraysvyohyke_select}
              {$tuote_select}
              sum(if (tilausrivi.kerattyaika >= '$vva-$kka-$ppa 00:00:00' AND tilausrivi.kerattyaika <= '$vvl-$kkl-$ppl 23:59:59', 1, 0)) kpl_valittu_aika,
              sum(if (tilausrivi.kerattyaika >= '$vva-$kka-$ppa 00:00:00' AND tilausrivi.kerattyaika <= '$vvl-$kkl-$ppl 23:59:59', tilausrivi.kpl+tilausrivi.varattu, 0)) tuokpl_valittu_aika,
              sum(if (tilausrivi.kerattyaika >= Date_sub(CURRENT_DATE, INTERVAL 6 month), 1, 0)) kpl_6,
              sum(if (tilausrivi.kerattyaika >= Date_sub(CURRENT_DATE, INTERVAL 6 month), tilausrivi.kpl+tilausrivi.varattu, 0)) tuo_kpl_6,
              sum(if (tilausrivi.kerattyaika >= Date_sub(CURRENT_DATE, INTERVAL 12 month), 1, 0)) kpl_12,
              sum(if (tilausrivi.kerattyaika >= Date_sub(CURRENT_DATE, INTERVAL 12 month), tilausrivi.kpl+tilausrivi.varattu, 0)) tuo_kpl_12
              FROM tilausrivi
              JOIN tuote USE INDEX (tuoteno_index) ON (tilausrivi.yhtio = tuote.yhtio
                AND tilausrivi.tuoteno   = tuote.tuoteno
                AND tuote.ei_saldoa      = '')
              JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
                AND varastopaikat.tunnus = tilausrivi.varasto)
              {$varaston_hyllypaikat_join}
              {$keraysvyohyke_join}
              LEFT JOIN tuotepaikat USE INDEX (yhtio_tuoteno_paikka) ON ( tilausrivi.yhtio = tuotepaikat.yhtio
                AND tilausrivi.hyllyalue = tuotepaikat.hyllyalue
                AND tilausrivi.hyllynro  = tuotepaikat.hyllynro
                AND tilausrivi.hyllyvali = tuotepaikat.hyllyvali
                AND tilausrivi.hyllytaso = tuotepaikat.hyllytaso
                AND tilausrivi.tuoteno   = tuotepaikat.tuoteno )
              WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
              AND tilausrivi.tyyppi      = 'L'
              {$tuotepaikka_where}
              {$_date}
              {$varasto_lisa2}
              GROUP BY 1
              {$group}
              ORDER BY kpl_valittu_aika DESC
              $lisa";
  }

  $result = pupe_query($query);

  //päiviä aikajaksossa
  $epa1 = (int)date('U', mktime(0, 0, 0, $kka, $ppa, $vva));
  $epa2 = (int)date('U', mktime(0, 0, 0, $kkl, $ppl, $vvl));

  //Diff in workdays (5 day week)
  $pva = abs($epa2 - $epa1) / 60 / 60 / 24 / 7 * 5;

  $poistettu = t('Poistettu');

  $rows = array();
  $saldolliset = array();

  if (mysql_num_rows($result) > 0) {
    $progress_bar = new ProgressBar(t("Haetaan tiedot"));
    $progress_bar->initialize(mysql_num_rows($result));
  }

  while ($row = mysql_fetch_assoc($result)) {

    if (isset($progress_bar)) {
      $progress_bar->increase();
    }

    if ($tyyppi == 'TUOTE') {
      if (!empty($lisa_kentat['nimitys']['checked'])) {
        $row['nimitys'] = t_tuotteen_avainsanat($row, 'nimitys');
      }
      if (isset($row['status']) and array_key_exists($row['status'], $tuote_statukset)) {
        $row['status'] = $tuote_statukset[$row['status']];
      }

      if (isset($row['ostoehdotus']) and array_key_exists($row['ostoehdotus'], $ostoehdotukset)) {
        $row['ostoehdotus'] = $ostoehdotukset[$row['ostoehdotus']];
      }
      elseif (isset($row['ostoehdotus']) and !array_key_exists($row['ostoehdotus'], $ostoehdotukset)) {
        $row['ostoehdotus'] = t("Tuntematon");
      }
    }

    $row['kpl_kerays'] = number_format($row["kpl_valittu_aika"] > 0 ? round($row["tuokpl_valittu_aika"] / $row["kpl_valittu_aika"]) : 0, 0);
    $row['kpl_valittu_aika_pvm'] = number_format($row["kpl_valittu_aika"] / $pva, 0);

    if (is_numeric($row['poistettu'])) {
      if ($row['poistettu'] == 1) {
        $row['poistettu'] = $poistettu;
      }
      elseif ($row['poistettu'] == 0) {
        $saldolliset[] = $row["paikkatun"];
        $row['poistettu'] = '';
      }
    }

    unset($row['tuokpl_valittu_aika']);
    unset($row['tuo_kpl_6']);
    unset($row['tuo_kpl_12']);
    unset($row['paikkatun']);

    $rows[] = $row;
  }

  echo "<br/>";

  return array($rows, $saldolliset);
}

function echo_tulosta_inventointilista($saldolliset) {
  echo "<br><br><form method='POST' action='../inventointi_listat.php'>";
  echo "<input type='hidden' name='tee' value='TULOSTA'>";

  $saldot = "";
  foreach ($saldolliset as $saldo) {
    $saldot .= "$saldo,";
  }
  $saldot = substr($saldot, 0, -1);

  echo "<input type='hidden' name='saldot' value='$saldot'>";
  echo "<input type='hidden' name='tulosta' value='JOO'>";
  echo "<input type='hidden' name='tila' value='SIIVOUS'>";
  echo "<input type='hidden' name='ei_inventointi' value='EI'>";
  echo "<input type='submit' value='".t("Tulosta inventointilista")."'></form><br><br>";
}

function echo_tallennus_formi($xls_filename) {
  echo "<form method='post' class='multisubmit'>";
  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Tallenna excel aineisto").":</th>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='lataa_tiedosto' value='1'>";
  echo "<input type='hidden' name='kaunisnimi' value='".t('Keraysseuranta').".xlsx'>";
  echo "<input type='hidden' name='tmpfilenimi' value='{$xls_filename}'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";
  echo "<br/>";
}

function nayta_ruudulla(&$rivit, $header_values, $force_to_string, $ppa, $kka, $vva, $ppl, $kkl, $vvl, $callback_function) {
  echo "<table><tr>
  <th>".t("Valittu kausi")."</th>
  <td>{$ppa}</td>
  <td>{$kka}</td>
  <td>{$vva}</td>
  <th>-</th>
  <td>{$ppl}</td>
  <td>{$kkl}</td>
  <td>{$vvl}</td>
  </tr></table><br>";

  echo_rows_in_table($rivit, $header_values, $force_to_string, $callback_function);
}

function right_align_numbers($header, $solu, $force_to_string) {
  if (!stristr($header, 'tunnus')) {
    if (is_numeric($solu) and !in_array($header, $force_to_string)) {
      $align = "align='right'";
    }
    else {
      $align = "";
    }
    if (is_numeric($solu) and !ctype_digit($solu) and !in_array($header, $force_to_string)) {
      $solu = number_format($solu, 0);
    }

    echo "<td $align>{$solu}</td>";
  }
}

require "inc/footer.inc";
