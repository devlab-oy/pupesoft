<?php
//Kun tehdyt_työt näkymästä tulostetaan tarkastuspyötäkirjoja tai poikkeamaraportteja,
//tulee requestin mukana toim-muuttuja.
//Koska kyseinen muuttuja ei ole tässä tiedostossa käytössä asetetaan se tyhjäksi,
//koska muuten se osuisi tämän tiedoston oikeustarkistuksiin.
if (isset($_REQUEST['toim'])) {
  $_REQUEST['toim'] = '';
}

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }
  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

$filepath = dirname(__FILE__);
if (file_exists($filepath . '/inc/parametrit.inc')) {
  require_once($filepath . '/inc/parametrit.inc');
  require_once($filepath . '/inc/tyojono2_functions.inc');
  require_once($filepath . '/inc/laite_huolto_functions.inc');
}
else {
  require_once($filepath . '/parametrit.inc');
  require_once($filepath . '/tyojono2_functions.inc');
  require_once($filepath . '/laite_huolto_functions.inc');
}

if (!empty($kukarow['extranet'])) {
  pupesoft_require('inc/tyojono2_functions.inc');
  pupesoft_require('tilauskasittely/tarkastuspoytakirja_pdf.php');
  pupesoft_require('tilauskasittely/poikkeamaraportti_pdf.php');
  pupesoft_require('tilauskasittely/laskutuspoytakirja_pdf.php');
}

if (isset($livesearch_tee) and $livesearch_tee == "ASIAKASHAKU") {
  livesearch_asiakashaku();
  exit;
}

if (isset($livesearch_tee) and $livesearch_tee == "KOHDEHAKU") {
  livesearch_kohdehaku();
  exit;
}

if ($tee == 'lataa_tiedosto') {
  $filepath = "/tmp/" . $tmpfilenimi;
  if (file_exists($filepath)) {
    readfile($filepath);
    unlink($filepath);
  }
  else {
    echo "<font class='error'>" . t("Tiedostoa ei ole olemassa") . "</font>";
  }
  exit;
}

//Tänne tämän tiedoston ajax requestit
if ($ajax_request) {
  exit;
}

enable_ajax();

echo "<font class='head'>" . t("Laitehallinta") . "</font><hr>";
?>
<style>
  .paikka_tr_hidden {
    display: none;
  }

  .laitteet_table_hidden {
    display: none;
  }

  .paikka_tr_not_hidden {
  }

  .laiteet_table_not_hidden {
  }
</style>
<script src='<?php echo $palvelin2 ?>js/asiakas/asiakkaan_laite_puu.js'></script>
<script src='<?php echo $palvelin2 ?>js/tyomaarays/tyojono2.js'></script>
<script>
  $(document).ready(function() {
    massapaivitys_select_all();
    $('#laite_puu_wrapper').laitePuuPlugin();
    var laite_puu_plugin = $('#laite_puu_wrapper').data('laitePuuPlugin');
    laite_puu_plugin.bind_kohde_tr_click();
    laite_puu_plugin.bind_paikka_tr_click();
    laite_puu_plugin.bind_poista_kohde_button();
    laite_puu_plugin.bind_poista_paikka_button();
    laite_puu_plugin.bind_poista_laite_button();
    laite_puu_plugin.bind_aineisto_submit_button_click();
  });

  function massapaivitys_select_all() {
    $('#massapaivitys_select_all').on('click', function() {
      $('.massapaivitys_checkbox').prop('checked', this.checked);
    });
  }

</script>
<?php
$request = array(
    'tee'             => $tee,
    'asiakasid'       => $asiakasid,
    'asiakas_tunnus'  => $asiakas_tunnus,
    'kohde_tunnus'    => $kohde_tunnus,
    'omanumero'       => $omanumero,
    'sarjanumero'     => $sarjanumero,
    'ala_tee'         => $ala_tee,
    'lasku_tunnukset' => $lasku_tunnukset,
    'huoltosyklit'    => $huoltosyklit,
);

$request['laitteen_tilat'] = hae_laitteen_tilat();

if ($request['tee'] == 'hae_asiakas' and empty($kukarow['extranet'])) {
  $request['haettu_asiakas'] = hae_asiakas($request);
}
else {
  $request['haettu_asiakas'] = hae_extranet_kayttajaan_liitetty_asiakas();
  $request['liitostunnus'] = $request['haettu_asiakas']['tunnus'];
  $request['toim'] = 'TEHDYT_TYOT';
}

if (!empty($request['haettu_asiakas'])) {
  echo "<font class='head'>{$request['haettu_asiakas']['nimi']}</font>";
  echo "<br/>";
  echo "<br/>";
}

if (empty($kukarow['extranet']) and $request['ala_tee'] != 'echo_massapaivitys_form') {
  echo_kayttoliittyma($request);

  echo "<br/>";
  echo "<br/>";
}

if (!empty($request['haettu_asiakas'])) {
  $asiakkaan_kohteet = hae_asiakkaan_kohteet_joissa_laitteita($request);

  $pdf_tiedostot = array();

  if ($request['ala_tee'] == 'tulosta_kalustoraportti') {
    $asiakkaan_kohteet['yhtio'] = $yhtiorow;
    $asiakkaan_kohteet['asiakas'] = $request['haettu_asiakas'];
    $asiakkaan_kohteet['logo'] = base64_encode(hae_yhtion_lasku_logo());
    $pdf_tiedostot = array(tulosta_kalustoraportti($asiakkaan_kohteet));
    $pdf_nimi = t('Kalustoraportti');

    unset($request['ala_tee']);
    $asiakkaan_kohteet = hae_asiakkaan_kohteet_joissa_laitteita($request);
  }
  else if ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' or $request['ala_tee'] == 'tulosta_poikkeamaraportti') {
    $pdf_tiedostot = ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' ? PDF\Tarkastuspoytakirja\hae_tarkastuspoytakirjat($request['lasku_tunnukset']) : PDF\Poikkeamaraportti\hae_poikkeamaraportit($request['lasku_tunnukset']));
    $pdf_nimi = ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' ? t('Tarkastuspöytäkirja') : t('Poikkeamaraportti'));
    //lasku_tunnukset pitää unsetata koska niitä käytetään hae_tyomaarays funkkarissa
    unset($request['lasku_tunnukset']);
  }
  else if ($request['ala_tee'] == 'tulosta_laskutuspoytakirja') {
    $pdf_tiedostot = array(\PDF\Laskutuspoytakirja\hae_laskutuspoytakirja($request['lasku_tunnukset']));
    $pdf_nimi = t('Laskutuspyötäkirja');
    //lasku_tunnukset pitää unsetata koska niitä käytetään hae_tyomaarays funkkarissa
    unset($request['lasku_tunnukset']);
  }
  else if ($request['ala_tee'] == 'echo_massapaivitys_form') {
    $laitteiden_huoltosyklit = hae_laitteiden_huoltosyklit($request['haettu_asiakas']);
    echo_massapaivitys_form($laitteiden_huoltosyklit, $request['haettu_asiakas']);
  }
  else if ($request['ala_tee'] == 'paivita_huoltosyklit') {
    $updated = paivita_huoltosyklit();
    echo "<font class='message'>" . $updated . " kpl päivitetty onnistuneesti</font>";
    echo "<br/>";
    echo "<br/>";
  }

  if (!empty($kukarow['extranet'])) {
    $js = hae_tyojono2_js();
    $css = hae_tyojono2_css();

    echo $js;
    echo $css;

    $request['tyojonot'] = hae_tyojonot($request);
    $request['tyostatukset'] = hae_tyostatukset($request);

    $request['tyomaaraykset'] = hae_tyomaaraykset($request);
    $request['tyomaaraykset'] = kasittele_tyomaaraykset($request);

    echo "<div id='tyojono_wrapper'>";
    //Tarkastuspöytäkirjan ja poikkeamaraportin tulostus logiikka suoritetaan
    //tyojono_wrapper divin sisällä, jotta työjono leiskaan liitetty js-toiminnallisuus skulaa
    foreach ($pdf_tiedostot as $pdf_tiedosto) {
      if (!empty($pdf_tiedosto)) {
        echo_tallennus_formi($pdf_tiedosto, ($request['ala_tee'] == 'tulosta_tarkastuspoytakirja' ? t("Tarkastuspöytakirja") : t("Poikkeamaraportti")), 'pdf');
      }
    }
    echo_tyomaaraykset_table($request);
    echo "</div>";
    echo "<br/>";
    echo "<br/>";
  }
  else if (empty($kukarow['extranet']) and !empty($pdf_tiedostot)) {
    foreach ($pdf_tiedostot as $pdf_tiedosto) {
      if (!empty($pdf_tiedosto)) {

        echo_tallennus_formi($pdf_tiedosto, $pdf_nimi, 'pdf');
      }
    }
  }

  if ($request['ala_tee'] != 'echo_massapaivitys_form') {
    echo "<div id='laite_puu_wrapper'>";
    echo_kohteet_table($asiakkaan_kohteet, $request);
    echo "</div>";
  }
}

pupesoft_require("inc/footer.inc");

function paivita_huoltosyklit() {
  global $request;

  $count = 0;
  foreach ($request['huoltosyklit'] as $sykli) {

    $query = "SELECT huoltovali
              FROM huoltosykli
              WHERE tunnus = {$sykli['huoltosykli_tunnus']}";
    $result = pupe_query($query);
    $max_huoltovali = mysql_result($result, 0);

    $uusi_huoltovali = $sykli['huoltovali'];

    if ($sykli['huoltovali'] <= $max_huoltovali and $sykli['update'] == 1) {
      $query = "UPDATE huoltosyklit_laitteet
                SET huoltovali = {$uusi_huoltovali}
                WHERE tunnus IN ({$sykli['tunnukset']})";
      $result = pupe_query($query);
      $count = $count + mysql_affected_rows();
    }
  }

  return $count;
}

function echo_kayttoliittyma($request = array()) {
  global $kukarow, $yhtiorow, $palvelin2;

  echo "<input type='hidden' id='down_arrow' value='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
  echo "<input type='hidden' id='right_arrow' value='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />";
  echo "<input type='hidden' id='oletko_varma_confirm_message' value='" . t("Oletko varma") . "' />";
  echo "<input type='hidden' id='poisto_epaonnistui_message' value='" . t("Poisto epäonnistui") . "' />";
  echo "<input type='hidden' id='poistettu_message' value='" . t("Poistettu") . "' />";

  echo "<form method='POST' action='' name='asiakas_haku'>";
  echo "<input type='hidden' id='tee' name='tee' value='hae_asiakas' />";

  echo "<table>";

  echo "<tr>";
  echo "<th>" . t('Asiakas') . "</th>";
  echo "<td>";
  echo livesearch_kentta('asiakas_haku', 'ASIAKASHAKU', 'asiakas_tunnus', 300, $request['asiakas_tunnus'], 'NOAUTOSUBMIT');
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t('Kohde') . "</th>";
  echo "<td>";
  echo livesearch_kentta('asiakas_haku', 'KOHDEHAKU', 'kohde_tunnus', 300, $request['kohde_tunnus'], 'NOAUTOSUBMIT');
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t('Omanumero') . "</th>";
  echo "<td>";
  echo "<input type='text' name='omanumero' value='{$request['omanumero']}' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t('Sarjanumero') . "</th>";
  echo "<td>";
  echo "<input type='text' name='sarjanumero' value='{$request['sarjanumero']}' />";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<input type='submit' value='" . t("Hae") . "' />";

  echo "</form>";
}

function echo_kalustoraportti_form($haettu_asiakas) {
  global $kukarow, $yhtiorow;

  echo "<form method='POST' action='' name='tulosta_kalustoraportti'>";
  echo "<input type='hidden' id='tee' name='tee' value='hae_asiakas' />";
  echo "<input type='hidden' id='ala_tee' name='ala_tee' value='tulosta_kalustoraportti' />";
  echo "<input type='hidden' id='asiakasid' name='asiakasid' value='{$haettu_asiakas['tunnus']}' />";
  echo "<input type='submit' value='" . t("Tallenna kalustoraportti PDF") . "' />";
  echo "</form>";

  $lopetus = "{$palvelin2}asiakkaan_laite_hallinta.php////tee=hae_asiakas//asiakasid={$haettu_asiakas['tunnus']}";

  if (empty($kukarow['extranet'])) {
    echo "<form method='POST' action='' name='huoltosyklien_massapaivitys'>";
    echo "<input type='hidden' id='tee' name='tee' value='hae_asiakas' />";
    echo "<input type='hidden' id='lopetus' name='lopetus' value='{$lopetus}' />";
    echo "<input type='hidden' id='ala_tee' name='ala_tee' value='echo_massapaivitys_form' />";
    echo "<input type='hidden' id='asiakasid' name='asiakasid' value='{$haettu_asiakas['tunnus']}' />";
    echo "<input type='submit' value='" . t("Huoltovälien massapäivitys") . "' />";
    echo "</form>";
  }
}

function echo_massapaivitys_form($laitteiden_huoltosyklit, $asiakas) {

  echo "<form method='POST' action=''>";
  echo "<input type='hidden' id='tee' name='tee' value='hae_asiakas' />";
  echo "<input type='hidden' id='ala_tee' name='ala_tee' value='paivita_huoltosyklit' />";
  echo "<input type='hidden' id='asiakasid' name='asiakasid' value='{$asiakas['tunnus']}' />";


  echo "<table>";
  echo "<tr>";
  echo "<th><input type='checkbox' id='massapaivitys_select_all' checked ></th>";
  echo "<th>" . t("Huoltosykli") . "</th>";
  echo "<th>" . t("Toimenpide") . "</th>";
  echo "<th>" . t("Huoltoväli") . "</th>";
  echo "</tr>";

  if (!empty($laitteiden_huoltosyklit)) {
    foreach ($laitteiden_huoltosyklit as $index => $sykli) {

      echo "<tr>";

      echo "<td>";
      echo "<input type='checkbox' class='massapaivitys_checkbox' name='huoltosyklit[$index][update]' value='1' checked >";
      echo "</td>";

      echo "<td>";
      echo "<input type='hidden' name='huoltosyklit[$index][tunnukset]' value='" . $sykli['tunnukset'] . "' />";
      echo "<input type='hidden' name='huoltosyklit[$index][huoltosykli_tunnus]' value='" . $sykli['huoltosykli_tunnus'] . "' />";
      echo $sykli['kuvaus'];
      echo "</td>";

      echo "<td>";
      echo $sykli['nimitys'];
      echo "</td>";

      echo "<td>";
      echo "<select name='huoltosyklit[$index][huoltovali]'>";

      $huoltovali_options = huoltovali_options($sykli['huoltosykli_tunnus']);
      foreach ($huoltovali_options as $key => $val) {
        echo "<option value='" . $key . "'>" . $val['dropdown_text'] . "</option>";
      }

      echo "</select>";

      echo "</td>";

      echo "</tr>";
    }
  }
  echo "</table>";
  echo "<input type='submit' value='" . t("Päivitä") . "' />";
  echo "</form>";
}

function hae_laitteiden_huoltosyklit($asiakas) {
  global $kukarow, $yhtiorow;

  $query = "SELECT CONCAT(t6.tyyppi, ' ', t6.koko, 'kg ', t8.selitetark) AS kuvaus,
            t6.huoltovali,
            t5.huoltosykli_tunnus,
            t7.nimitys,
            GROUP_CONCAT(t5.tunnus) AS tunnukset
            FROM asiakas t1
            JOIN kohde t2
            ON ( t2.yhtio = t1.yhtio
              AND t2.asiakas = t1.tunnus )
            JOIN paikka t3
              ON ( t3.yhtio = t2.yhtio
              AND t3.kohde = t2.tunnus )
            JOIN laite t4
            ON ( t4.yhtio = t3.yhtio
              AND t4.paikka = t3.tunnus )
            JOIN huoltosyklit_laitteet t5
            ON ( t5.yhtio = t4.yhtio
              AND t5.laite_tunnus = t4.tunnus )
            JOIN huoltosykli t6
            ON ( t6.yhtio = t5.yhtio
              AND t6.tunnus = t5.huoltosykli_tunnus )
            JOIN tuote t7
            ON ( t7.yhtio = t6.yhtio
              AND t7.tuoteno = t6.toimenpide )
            JOIN avainsana t8
            ON ( t8.yhtio = t6.yhtio
              AND t8.selite = t6.olosuhde)
            WHERE t1.yhtio = '{$kukarow['yhtio']}'
            AND t1.tunnus = {$asiakas['tunnus']}
            GROUP BY CONCAT(t6.tyyppi,t6.koko,t6.olosuhde,t6.toimenpide)";

  $result = pupe_query($query);
  $laitteiden_huoltosyklit = array();
  while ($laitteen_huoltosykli = mysql_fetch_assoc($result)) {
    $laitteiden_huoltosyklit[] = $laitteen_huoltosykli;
  }

  return $laitteiden_huoltosyklit;
}

function tulosta_kalustoraportti($kohteet) {
  global $kukarow, $yhtiorow;

  $filepath = kirjoita_json_tiedosto($kohteet, 'Kalustoraportti');
  return aja_ruby($filepath, 'kalustoraportti');
}

function echo_kohteet_table($asiakkaan_kohteet = array(), $request = array()) {
  global $palvelin2, $lopetus, $kukarow;

  $haettu_asiakas = $request['haettu_asiakas'];

  $lopetus = "{$palvelin2}asiakkaan_laite_hallinta.php////tee=hae_asiakas//asiakasid={$haettu_asiakas['tunnus']}";

  echo_kalustoraportti_form($haettu_asiakas);
  echo "<br/>";
  echo "<br/>";

  echo "<table>";

  echo "<tr>";
  echo "<th>" . t("Kohteen nimi") . "</th>";
  echo "<th>" . t("Paikan nimi") . "</th>";
  echo "<th>" . t("Laitteet") . "</th>";
  echo "</tr>";

  echo "<tr>";
  echo "<td>";
  if (empty($kukarow['extranet'])) {
    echo "<a href='yllapito.php?toim=kohde&uusi=1&lopetus={$lopetus}&valittu_asiakas={$haettu_asiakas['tunnus']}'><button>" . t("Luo uusi kohde") . "</button></a>";
  }
  echo "</td>";

  echo "<td>";
  echo "</td>";

  echo "<td>";
  echo "</td>";

  echo "</tr>";

  if (!empty($asiakkaan_kohteet['kohteet'])) {
    foreach ($asiakkaan_kohteet['kohteet'] as $kohde_index => $kohde) {
      echo_kohde_tr($kohde_index, $kohde, $kohde['asiakas_tunnus']);
    }
  }

  echo "</table>";
}

function echo_kohde_tr($kohde_index, $kohde, $asiakas_tunnus) {
  global $palvelin2, $lopetus, $kukarow;

  echo "<tr class='kohde_tr hidden'>";

  echo "<td>";
  if (empty($kukarow['extranet'])) {
    echo "<button class='poista_kohde'>" . t("Poista kohde") . "</button>";
  }
  echo "&nbsp";
  echo "<input type='hidden' class='kohde_tunnus' value='{$kohde_index}' />";
  if (empty($kukarow['extranet'])) {
    echo "<a href='yllapito.php?toim=kohde&lopetus={$lopetus}&tunnus={$kohde_index}'>" . $kohde['kohde_nimi'] . "</a>";
  }
  else {
    echo $kohde['kohde_nimi'];
  }
  echo "&nbsp";
  echo "<img class='porautumis_img' src='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";

  if ($kohde['kohde_poistettu'] == 1) {
    echo "<br/>";
    echo '<font class="error">' . t('Poistettu') . '</font>';
  }
  echo "</td>";

  echo "<td>";
  //paikan nimi tyhjä
  echo "</td>";

  echo "<td>";
  //laitteet tyhjä
  echo "</td>";

  echo "</tr>";
  echo_paikka_tr($kohde_index, $asiakas_tunnus, $kohde['paikat']);
}

function echo_paikka_tr($kohde_index, $asiakas_tunnus, $paikat = array()) {
  global $palvelin2, $lopetus, $kukarow;


  echo "<tr class='paikka_tr_hidden paikat_{$kohde_index}'>";
  echo "<td>";
  if (empty($kukarow['extranet'])) {
    echo "<a href='yllapito.php?toim=paikka&uusi=1&&lopetus={$lopetus}&valittu_kohde={$kohde_index}'><button>" . t("Luo kohteelle uusi paikka") . "</button></a>";
  }
  echo "</td>";

  echo "<td>";
  echo "</td>";

  echo "<td>";
  echo "</td>";

  echo "</tr>";
  if (!empty($paikat)) {
    foreach ($paikat as $paikka_index => $paikka) {
      echo "<tr class='paikat_tr paikka_tr_hidden paikat_{$kohde_index}'>";

      echo "<td>";
      echo "<input type='hidden' class='paikka_tunnus' value='{$paikka_index}' />";
      echo "</td>";

      echo "<td>";
      if (empty($kukarow['extranet'])) {
        echo "<a href='yllapito.php?toim=paikka&lopetus={$lopetus}&tunnus={$paikka_index}'>{$paikka['paikka_nimi']}</a>";
      }
      else {
        echo $paikka['paikka_nimi'];
      }
      echo "&nbsp";
      echo "<img class='porautumis_img' src='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
      echo "<br/>";
      if (empty($kukarow['extranet'])) {
        echo "<button class='poista_paikka'>" . t("Poista paikka") . "</button>";
      }

      if ($paikka['paikka_poistettu'] == 1) {
        echo "<br/>";
        echo '<font class="error">' . t('Poistettu') . '</font>';
      }
      echo "</td>";

      echo "<td>";
      if (empty($kukarow['extranet'])) {
        echo "<a href='yllapito.php?toim=laite&asiakas_tunnus={$asiakas_tunnus}&uusi=1&lopetus={$lopetus}&valittu_paikka={$paikka_index}'><button>" . t("Luo paikkaan uusi laite") . "</button></a>";
      }
      echo "<br/>";
      echo_laitteet_table($paikka['laitteet']);
      echo "</td>";

      echo "</tr>";
    }
  }
}

function echo_laitteet_table($laitteet = array()) {
  global $palvelin2, $lopetus, $kukarow;

  echo "<table class='laitteet_table_hidden'>";
  echo "<tr>";
  echo "<th>" . t("Oma numero") . "</th>";
  echo "<th>" . t("Tuotenumero") . "</th>";
  echo "<th>" . t("Tuotteen nimi") . "</th>";
  echo "<th>" . t("Sijainti") . "</th>";
  echo "<th>" . t("Tila") . "</th>";
  if (empty($kukarow['extranet'])) {
    echo "<th>" . t("Kopioi") . "</th>";
    echo "<th>" . t("Poista") . "</th>";
  }
  echo "</tr>";

  foreach ($laitteet as $laite) {
    echo "<tr>";

    echo "<td>";
    echo $laite['oma_numero'];
    echo "</td>";

    echo "<td>";
    if (empty($kukarow['extranet'])) {
      echo "<a href='yllapito.php?toim=laite&asiakas_tunnus={$laite['asiakas_tunnus']}&lopetus={$lopetus}&tunnus={$laite['laite_tunnus']}'>{$laite['tuoteno']}</a>";
    }
    else {
      echo $laite['tuoteno'];
    }
    echo "</td>";

    echo "<td>";
    echo $laite['tuote_nimi'];
    echo "</td>";

    echo "<td>";
    echo $laite['sijainti'];
    echo "</td>";

    echo "<td class='tila'>";
    echo $laite['tilan_selite'];
    echo "</td>";

    if (empty($kukarow['extranet'])) {
      echo "<td>";
      if (!empty($laite['laite_tunnus'])) {
        echo "<form method='POST' action='{$palvelin2}yllapito.php?toim=laite&kopioi_rivi=on&asiakas_tunnus={$laite['asiakas_tunnus']}&lopetus={$lopetus}&tunnus={$laite['laite_tunnus']}'>";
        echo "<input type='submit' value='" . t('Kopioi laite') . "' />";
        echo "</form>";
      }
      echo "</td>";

      echo "<td>";
      if (!empty($laite['laite_tunnus'])) {
        echo "<input type='hidden' class='laite_tunnus' value='{$laite['laite_tunnus']}' />";
        echo "<button class='poista_laite'>" . t("Poista laite") . "</button>";
      }
      echo "</td>";
    }

    echo "</tr>";
  }
  echo "</table>";
}

function hae_laitteen_tilat() {
  global $kukarow, $yhtiorow;

  $result = t_avainsana('LAITE_TILA');
  $tilat = array();
  while ($tila = mysql_fetch_assoc($result)) {
    $tilat[] = $tila;
  }

  return $tilat;
}

function hae_asiakas($request) {
  global $kukarow, $yhtiorow;

  $asiakas_tunnus = $request['asiakas_tunnus'];
  if (!empty($request['asiakasid'])) {
    $asiakas_tunnus = $request['asiakasid'];
  }

  $where = "";
  if (!empty($asiakas_tunnus)) {
    $where = "AND asiakas.tunnus = $asiakas_tunnus";
  }

  if (!empty($request['kohde_tunnus'])) {
    $kohde_join = " JOIN kohde AS k1
                    ON ( k1.yhtio = asiakas.yhtio
                        AND k1.asiakas = asiakas.tunnus
                        AND k1.tunnus = {$request['kohde_tunnus']})";
  }

  $laite_join = "";
  if (!empty($request['omanumero'])) {
    $laite_join = " JOIN kohde AS k2
                    ON (k2.yhtio = asiakas.yhtio
                        AND k2.asiakas = asiakas.tunnus )
                    JOIN paikka AS p
                    ON ( p.yhtio = k2.yhtio
                        AND p.kohde = k2.tunnus )
                    JOIN laite AS l
                    ON ( l.yhtio = p.yhtio
                        AND l.paikka = p.tunnus
                        AND l.omanumero = '{$request['omanumero']}' )";
  }

  if (!empty($request['sarjanumero'])) {
    $laite_join .= "JOIN kohde AS k3
                    ON (k3.yhtio = asiakas.yhtio
                        AND k3.asiakas = asiakas.tunnus )
                    JOIN paikka AS p1
                    ON ( p1.yhtio = k3.yhtio
                        AND p1.kohde = k3.tunnus )
                    JOIN laite AS l1
                    ON ( l1.yhtio = p1.yhtio
                        AND l1.paikka = p1.tunnus
                        AND l1.sarjanumero = '{$request['sarjanumero']}' )";
  }

  $query = "SELECT asiakas.*
            FROM asiakas
            {$kohde_join}
            {$laite_join}
            WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
            {$where}";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function hae_asiakkaan_kohteet_joissa_laitteita($request) {
  global $kukarow;

  $select = "";
  $join = "";
  $group = "";
  if (!empty($request['ala_tee']) and $request['ala_tee'] != 'paivita_huoltosyklit') {
    $select = " ta1.selite as sammutin_tyyppi,
                ta2.selite as sammutin_koko,
                huoltosykli.huoltovali as huoltovali,";

    $join = " LEFT JOIN tuotteen_avainsanat ta1
              ON ( ta1.yhtio = tuote.yhtio
                AND ta1.tuoteno = tuote.tuoteno
                AND ta1.laji = 'sammutin_tyyppi' )
              LEFT JOIN tuotteen_avainsanat ta2
              ON ( ta2.yhtio = tuote.yhtio
                AND ta2.tuoteno = tuote.tuoteno
                AND ta2.laji = 'sammutin_koko' )
              LEFT JOIN huoltosykli
              ON ( huoltosykli.yhtio = laite.yhtio
                AND huoltosykli.tyyppi = ta1.selite
                AND huoltosykli.koko = ta2.selite
                AND huoltosykli.olosuhde = paikka.olosuhde )";

    //groupataan laite_tunnuksen mukaan koska laitteella voi olla monta huoltosykliä
    $group = "GROUP BY laite.tunnus";
  }

  $query = "SELECT asiakas.tunnus as asiakas_tunnus,
            kohde.tunnus as kohde_tunnus,
            kohde.nimi as kohde_nimi,
            kohde.poistettu as kohde_poistettu,
            paikka.tunnus as paikka_tunnus,
            paikka.nimi as paikka_nimi,
            paikka.poistettu as paikka_poistettu,
            tuote.nimitys as tuote_nimi,
            laite.tunnus as laite_tunnus,
            {$select}
            laite.*
            FROM kohde
            JOIN asiakas
            ON ( asiakas.yhtio = kohde.yhtio
              AND asiakas.tunnus = kohde.asiakas )
            LEFT JOIN paikka
            ON ( paikka.yhtio = kohde.yhtio
              AND paikka.kohde = kohde.tunnus )
            LEFT JOIN laite
            ON ( laite.yhtio = paikka.yhtio
              AND laite.paikka = paikka.tunnus )
            LEFT JOIN tuote
            ON ( tuote.yhtio = laite.yhtio
              AND tuote.tuoteno = laite.tuoteno )
            {$join}
            WHERE kohde.yhtio = '{$kukarow['yhtio']}'
            AND kohde.asiakas = {$request['haettu_asiakas']['tunnus']}
            {$group}";
  $result = pupe_query($query);

  $asiakkaan_kohteet = array();
  while ($kohde = mysql_fetch_assoc($result)) {
    $laitteen_tila = search_array_key_for_value_recursive($request['laitteen_tilat'], 'selite', $kohde['tila']);
    //key:llä on tarkoitus löytyä vain yksi resultti, siksi voidaan viitata indeksillä.
    $kohde['tilan_selite'] = $laitteen_tila[0]['selitetark'];
    $asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['kohde_nimi'] = $kohde['kohde_nimi'];
    $asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['kohde_tunnus'] = $kohde['kohde_tunnus'];
    $asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['kohde_poistettu'] = $kohde['kohde_poistettu'];
    $asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['asiakas_tunnus'] = $kohde['asiakas_tunnus'];

    if ($request['ala_tee'] == 'tulosta_kalustoraportti' and !empty($kohde['laite_tunnus'])) {
      $kohde['viimeiset_tapahtumat'] = hae_laitteen_viimeiset_tapahtumat($kohde['laite_tunnus']);
    }

    if (!empty($kohde['paikka_tunnus']) and $request['ala_tee'] != 'tulosta_kalustoraportti') {
      $asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['paikat'][$kohde['paikka_tunnus']]['laitteet'][] = $kohde;
      $asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['paikat'][$kohde['paikka_tunnus']]['paikka_nimi'] = $kohde['paikka_nimi'];
      $asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['paikat'][$kohde['paikka_tunnus']]['paikka_poistettu'] = $kohde['paikka_poistettu'];
    }
    else if (!empty($kohde['paikka_tunnus']) and $request['ala_tee'] == 'tulosta_kalustoraportti') {
      if (!empty($kohde['laite_tunnus'])) {
        if (onko_laitteella_poikkeus($kohde['laite_tunnus'])) {
          $kohde['poikkeus'] = 'X';
        }
        else {
          $kohde['poikkeus'] = '';
        }
        $asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['paikat'][$kohde['paikka_tunnus']]['laitteet'][] = $kohde;
        $asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['paikat'][$kohde['paikka_tunnus']]['paikka_nimi'] = $kohde['paikka_nimi'];
        $asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['paikat'][$kohde['paikka_tunnus']]['paikka_poistettu'] = $kohde['paikka_poistettu'];
      }
    }
  }

  return $asiakkaan_kohteet;
}
