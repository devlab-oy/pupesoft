<?php

$pupe_DataTables = "saldovahvistus";

$useslave = 1;
session_start();

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }
  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

require "../inc/parametrit.inc";
require 'myyntires/paperitiliote_saldovahvistus.php';
require 'inc/pupeExcel.inc';

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

if ($ajax_request) {
  if ($merkkaa_lahetettavaksi == '1') {
    foreach ($saldovahvistus_rivit as $saldovahvistus_rivi) {
      $lasku_tunnukset_key = implode('', $saldovahvistus_rivi['lasku_tunnukset']);
      if ($lisays == 'true') {
        $saldovahvistusrivi = array(
          'laskun_avoin_paiva'    => $saldovahvistus_rivi['laskun_avoin_paiva'],
          'saldovahvistus_viesti' => $saldovahvistus_rivi['saldovahvistus_viesti'],
          'lasku_tunnukset'       => $saldovahvistus_rivi['lasku_tunnukset'],
          'ryhmittely_tyyppi'     => $saldovahvistus_rivi['ryhmittely_tyyppi'],
        );
        lisaa_sessioon_saldovahvistus_rivi($lasku_tunnukset_key, $saldovahvistusrivi);
      }
      else {
        unset($_SESSION['valitut_laskut'][$lasku_tunnukset_key]);
      }
    }
  }

  exit;
}


if (!isset($nayta_pdf)) {
  $nayta_pdf = 0;
}

if ($nayta_pdf != 1) {
  echo "<font class='head'>" . t("Saldovahvistus") . "</font><hr>";
}

if (!isset($avoin_saldo_rajaus)) {
  $avoin_saldo_rajaus = "";
}

if (!isset($lahetettavat_laskut)) {
  $lahetettavat_laskut = array();
}

if (!isset($pp)) {
  $pp = date('d');
}
if (!isset($kk)) {
  $kk = date('m');
}
if (!isset($vv)) {
  $vv = date('Y');
}

if (checkdate($kk, $pp, $vv)) {
  $paiva = date('Y-m-d', strtotime("{$vv}-{$kk}-{$pp}"));
}
else {
  $paiva = date('Y-m-d', strtotime("now"));
}

$request = array(
  'tee'                                   => $tee,
  'ryhmittely_tyyppi'                     => $ryhmittely_tyyppi,
  'saldovahvistus_rivi_ryhmittely_tyyppi' => $saldovahvistus_rivi_ryhmittely_tyyppi,
  'ryhmittely_arvo'                       => $ryhmittely_arvo,
  'pp'                                    => $pp,
  'kk'                                    => $kk,
  'vv'                                    => $vv,
  'paiva'                                 => $paiva,
  'saldovahvistus_viesti'                 => $saldovahvistus_viesti,
  'lasku_tunnukset'                       => $lasku_tunnukset,
  'tallenna_exceliin'                     => $tallenna_exceliin,
  'avoin_saldo_rajaus'                    => $avoin_saldo_rajaus,
  'lahetettavat_laskut'                   => $lahetettavat_laskut,
);

$t = array(
  "email" => t("Email puuttuu"),
  "nayta_pdf" => t("Näytä pdf"),
  "tulosta_pdf" => t("Tulosta pdf"),
  "kohdistamaton" => t('Kohdistamaton suoritus'),
);

$request["t"] = $t;

$request['laskut'] = array();
$request['valitut_laskut'] = array();

$request['ryhmittely_tyypit'] = array(
  'ytunnus'    => t('Ytunnus'),
  'asiakasnro' => t('Asiakasnumero'),
);

$_key = key($_SESSION['valitut_laskut']);

$request['saldovahvistus_viestit'] = hae_saldovahvistus_viestit($_SESSION['valitut_laskut'][$_key]['asiakas']['kieli']);

if ($request['tee'] == 'poista_valinnat') {
  unset($_SESSION['valitut_laskut']);
}

if ($nayta_pdf != 1 and $request['tee'] != 'laheta_sahkopostit') {
  echo_kayttoliittyma($request);

  echo "<br/>";
  echo "<br/>";
}

if (count($request['lahetettavat_laskut']) > 0 and !empty($_SESSION['valitut_laskut']) and $request['tee'] == 'laheta_sahkopostit') {
  $lasku_tunnukset_temp = $request['lasku_tunnukset'];

  foreach ($request['lahetettavat_laskut'] as $_id) {
    if (array_key_exists($_id, $_SESSION['valitut_laskut'])) {
      $valittu_lasku = $_SESSION['valitut_laskut'][$_id];

      $request['lasku_tunnukset'] = $valittu_lasku['lasku_tunnukset'];
      $request['ryhmittely_tyyppi_temp'] = $valittu_lasku['ryhmittely_tyyppi'];

      $_req = $request;
      $_req['paiva'] = $valittu_lasku['laskun_avoin_paiva'];

      $lasku_temp = hae_myyntilaskuja_joilla_avoin_saldo($_req, true);
      $lasku_temp['saldovahvistus_viesti'] = $valittu_lasku['saldovahvistus_viesti'];
      $lasku_temp['laskun_avoin_paiva'] = $valittu_lasku['laskun_avoin_paiva'];
      $request['valitut_laskut'][$_id] = $lasku_temp;
    }
  }

  $request['lasku_tunnukset'] = $lasku_tunnukset_temp;
  unset($request['ryhmittely_tyyppi_temp']);
}
// Tämä blocki on sitä varten, että muistetaan käyttäjän aiemmat haut,
// jos käyttäjä vierailee toisissa softissa ja palaa tähän softaan myöhemmin
elseif (!empty($_SESSION['valitut_laskut'])) {
  $lasku_tunnukset_temp = $request['lasku_tunnukset'];
  foreach ($_SESSION['valitut_laskut'] as $valittu_lasku) {
    $request['lasku_tunnukset'] = $valittu_lasku['lasku_tunnukset'];
    $request['ryhmittely_tyyppi_temp'] = $valittu_lasku['ryhmittely_tyyppi'];

    $_req = $request;
    $_req['paiva'] = $valittu_lasku['laskun_avoin_paiva'];

    $lasku_temp = hae_myyntilaskuja_joilla_avoin_saldo($_req, true);
    $lasku_temp['saldovahvistus_viesti'] = $valittu_lasku['saldovahvistus_viesti'];
    $lasku_temp['laskun_avoin_paiva'] = $valittu_lasku['laskun_avoin_paiva'];
    $request['valitut_laskut'][] = $lasku_temp;
  }

  $request['lasku_tunnukset'] = $lasku_tunnukset_temp;
  unset($request['ryhmittely_tyyppi_temp']);
}

//Echotaan saldovahvistukset, kun tehdään käyttöliittymästä haku
//tai jos sessioon on tallennettu saldovahvistusrivejä edellisellä hakukerroilla ja ollaan välissä käyty jossain muussa ohjelmassa.
if ($request['tee'] == 'aja_saldovahvistus' or (!empty($request['valitut_laskut']) and $request['tee'] == 'valitut_laskut_haettu')) {

  js_openFormInNewWindow();
  if ($request['tee'] == 'aja_saldovahvistus') {
    $request['laskut'] = hae_myyntilaskuja_joilla_avoin_saldo($request);
  }

  if (!empty($request['tallenna_exceliin'])) {
    $excel_filepath = generoi_custom_excel_tiedosto($request);

    echo_tallennus_formi($excel_filepath, t('Saldovahvistus'));
  }

  echo_saldovahvistukset($request);
}
elseif ($request['tee'] == 'NAYTATILAUS' or $request['tee'] == 'tulosta_saldovahvistus_pdf') {
  //requestissa tulee tietyn ytunnuksen lasku_tunnuksia. Tällöin $laskut arrayssa on vain yksi solu
  $laskut = hae_myyntilaskuja_joilla_avoin_saldo($request, true);

  //Jos saldovahvistus_rivi löytyy jo valittujen rivien joukosta, niin haetaan riville tallennetut viesti ja päivämäärä sessiosta
  $lasku_tunnukset_temp = implode('', $laskut['lasku_tunnukset']);
  if (array_key_exists($lasku_tunnukset_temp, $_SESSION['valitut_laskut'])) {
    $laskut['saldovahvistus_viesti'] = search_array_key_for_value_recursive($request['saldovahvistus_viestit'], 'selite', $_SESSION['valitut_laskut'][$lasku_tunnukset_temp]['saldovahvistus_viesti']);
    $laskut['saldovahvistus_viesti'] = $laskut['saldovahvistus_viesti'][0];
    $laskut['laskun_avoin_paiva'] = $_SESSION['valitut_laskut'][$lasku_tunnukset_temp]['laskun_avoin_paiva'];
  }
  else {
    $laskut['saldovahvistus_viesti'] = search_array_key_for_value_recursive($request['saldovahvistus_viestit'], 'selite', $request['saldovahvistus_viesti']);
    $laskut['saldovahvistus_viesti'] = $laskut['saldovahvistus_viesti'][0];
    $laskut['laskun_avoin_paiva'] = $request['paiva'];
  }

  if ($request['ryhmittely_tyyppi'] == 'ytunnus') {
    $boss = true;
  }
  else {
    $boss = false;
  }

  $laskut['tiliotepvm'] = "{$request['vv']}-{$request['kk']}-{$request['pp']}";

  //Valittu saldovahvistusviesti
  $pdf_filepath = hae_saldovahvistus_pdf($laskut, $boss);

  if ($request['tee'] == 'NAYTATILAUS') {
    echo file_get_contents($pdf_filepath);
  }
  elseif ($request['tee'] == 'tulosta_saldovahvistus_pdf') {
    $kirjoitin_komento = hae_kayttajan_kirjoitin();

    exec($kirjoitin_komento['komento'] . ' ' . $pdf_filepath);
  }

  //unset, jotta käyttöliittymään tulisi rajausten mukaiset laskut.
  unset($request['lasku_tunnukset']);

  $request['laskut'] = hae_myyntilaskuja_joilla_avoin_saldo($request);
  echo_saldovahvistukset($request);
}
elseif ($request['tee'] == 'laheta_sahkopostit') {
  list($lahetetyt_count, $ei_lahetetty_count, $ei_lahetetyt) = generoi_saldovahvistus_sahkopostit($request);

  echo_kayttoliittyma($request);

  echo "<br/>";
  echo "<br/>";
  echo '<font class="message">' . $lahetetyt_count . ' ' . t('sähköpostia lähetetty') . '</font>';
  if ($ei_lahetetty_count > 0) {
    echo "<br />";
    echo '<font class="message">'.$ei_lahetetty_count.' '.t('sähköpostin lähettäminen epäonnistui').'</font>';

    if (count($ei_lahetetyt) > 0) {
      echo "<br /><br />";

      foreach ($ei_lahetetyt as $ei_lahetetty_nimi) {
        echo "<font class='message'>";
        echo t("Asiakkaan %s sähköpostin lähettäminen epäonnistui", "", $ei_lahetetty_nimi);
        echo "</font><br />";
      }
    }
  }
}

?>
<style>
  tr.border_bottom td {
    border-bottom: 3pt solid black;
  }

  .hidden {
    display: none;
  }
</style>
<script>
  $(document).ready(function() {
    bind_saldovahvistus_rivi_valinta_checkbox_click();
    bind_valitse_kaikki_checkbox_click();
    bind_valitse_kaikki_lahetettavaksi();
    bind_valitse_lahetettavaksi();
    $('#valitse_kaikki_lahetettavaksi').attr('checked', 'checked');
    $('#valitse_kaikki_lahetettavaksi').trigger('click');
    $('#valitse_kaikki_lahetettavaksi').attr('checked', 'checked');
  });

  function bind_saldovahvistus_rivi_valinta_checkbox_click() {
    $('.saldovahvistus_rivi_valinta').click(function() {
      var lisays;
      if ($(this).is(':checked')) {
        lisays = true;
      }
      else {
        lisays = false;
      }

      var lasku_tunnukset = $(this).parent().parent().find('.nayta_pdf_td .lasku_tunnus').map(function() {
        return $(this).val();
      }).get();

      var saldovahvistus_rivit = [];

      var saldovahvistus_rivi = {
        lasku_tunnukset: lasku_tunnukset,
        laskun_avoin_paiva: $(this).parent().parent().find('.laskun_avoin_paiva').val(),
        saldovahvistus_viesti: $(this).parent().parent().find('.saldovahvistus_viesti').html(),
        ryhmittely_tyyppi: $(this).parent().parent().find('.ryhmittely_tyyppi').val()
      };

      saldovahvistus_rivit.push(saldovahvistus_rivi);
      tallenna_sessioon(saldovahvistus_rivit, lisays);
    });
  }

  function add_ids(that, $_id) {
    if ($(that).is(':checked')) {
      $_hidden = $('<input type=\'hidden\' />');
      $_hidden.attr('name', 'lahetettavat_laskut[]');
      $_hidden.attr('class', $_id);
      $_hidden.attr('value', $_id);
      $('#lahetysformi').append($_hidden);
    }
    else {
      $('.' + $_id).each(function() {
        $(this).remove();
      });
    }
  }

  function bind_valitse_lahetettavaksi() {
    $('.saldovahvistus_rivi_sahkoposti_valinta').on('click', function() {
      $_id = $(this).prev('input.saldovahvistus_rivi_sahkoposti_valinta_id').val();

      add_ids(this, $_id);
    });
  }

  function bind_valitse_kaikki_lahetettavaksi() {
    $('#valitse_kaikki_lahetettavaksi').on('click', function() {
      var $table = $(this).parent().parent().parent().parent(),
          $_checkboxes = $table.find('.saldovahvistus_rivi_sahkoposti_valinta');

      if ($(this).is(':checked')) {
        $_checkboxes.each(function() {
          $(this).attr('checked', 'checked');
          $_id = $(this).prev('input.saldovahvistus_rivi_sahkoposti_valinta_id').val();
          add_ids(this, $_id);
        });
      }
      else {
        $_checkboxes.each(function() {
          $(this).removeAttr('checked');
          $_id = $(this).prev('input.saldovahvistus_rivi_sahkoposti_valinta_id').val();
          add_ids(this, $_id);
        });
      }
    });
  }

  function bind_valitse_kaikki_checkbox_click() {
    $('#valitse_kaikki').click(function() {
      var $table = $(this).parent().parent().parent().parent();
      var lisays;

      if ($(this).is(':checked')) {
        lisays = true;
        $table.find('.saldovahvistus_rivi').find('.saldovahvistus_rivi_valinta').attr('checked', 'checked');
      }
      else {
        lisays = false;
        $table.find('.saldovahvistus_rivi').find('.saldovahvistus_rivi_valinta').removeAttr('checked');
      }

      var saldovahvistus_rivit = [];
      $table.find('.saldovahvistus_rivi').each(function() {
        var lasku_tunnukset = $(this).find('.nayta_pdf_td .lasku_tunnus').map(function() {
          return $(this).val();
        }).get();
        saldovahvistus_rivit.push({
          lasku_tunnukset: lasku_tunnukset,
          laskun_avoin_paiva: $(this).parent().parent().find('.laskun_avoin_paiva').val(),
          saldovahvistus_viesti: $(this).parent().parent().find('.saldovahvistus_viesti').html(),
          ryhmittely_tyyppi: $(this).parent().parent().find('.ryhmittely_tyyppi').val()
        });
      });

      tallenna_sessioon(saldovahvistus_rivit, lisays);
    });
  }

  function tallenna_sessioon(saldovahvistus_rivit, lisays) {
    $.ajax({
      async: true,
      type: 'POST',
      data: {
        ajax_request: 1,
        no_head: 'yes',
        merkkaa_lahetettavaksi: 1,
        lisays: lisays,
        saldovahvistus_rivit: saldovahvistus_rivit
      },
      url: 'saldovahvistus.php'
    });
  }

  function tarkista(message) {
    var ok = true;
    ok = confirm(message);

    return ok;
  }
</script>
<?php

require 'inc/footer.inc';

function lisaa_sessioon_saldovahvistus_rivi($lasku_tunnukset_key, $saldovahvistusrivi) {
  global $kukarow, $yhtiorow;

  if (!isset($_SESSION['valitut_laskut'][$lasku_tunnukset_key])) {
    $_SESSION['valitut_laskut'][$lasku_tunnukset_key] = $saldovahvistusrivi;
    return true;
  }

  return false;
}

function echo_saldovahvistukset($request) {
  global $kukarow, $yhtiorow, $pupe_DataTables, $palvelin2;

  //  echo "<table class='display'>";

  pupe_DataTables(array(array($pupe_DataTables, 6, 9, false, false, true)));
  echo "<table class='display dataTable' id='{$pupe_DataTables}'>";

  echo "<thead>";

  echo "<tr>";
  echo "<th>" . t('Päivämäärä') . "</th>";
  echo "<th>" . t('Ytunnus') . "</th>";
  echo "<th>" . t('Asiakasnumero') . "</th>";
  echo "<th>" . t('Nimi') . "</th>";
  echo "<th>" . t('Saldo') . "</th>";
  echo "<th>" . t('Viesti') . "</th>";
  echo "<th>", t("Muistissa"), "</th>";
  echo "<th>", t("Lähetä"), "</th>";
  echo "<th class='hidden'></th>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><input type='text' class='search_field' name='search_paivamaara'></td>";
  echo "<td><input type='text' class='search_field' name='search_ytunnus'></td>";
  echo "<td><input type='text' class='search_field' name='search_asiakasnumero'></td>";
  echo "<td><input type='text' class='search_field' name='search_nimi'></td>";
  echo "<td><input type='text' class='search_field' name='search_saldo'></td>";
  echo "<td><input type='text' class='search_field' name='search_viesti'></td>";
  echo "<td><input type='checkbox' id='valitse_kaikki' CHECKED /></td>";
  echo "<td><input type='checkbox' id='valitse_kaikki_lahetettavaksi' /></td>";
  echo "<td class='hidden'></td>";
  echo "</tr>";

  echo "</thead>";

  echo "<tbody>";

  $kpl = count($request['laskut']);
  $i = 1;
  $viimeinen = false;
  foreach ($request['laskut'] as $lasku) {
    if ($i == $kpl) {
      $viimeinen = true;
    }

    if (!empty($request['avoin_saldo_rajaus'])) {

      $_rajaus = (float) $request['avoin_saldo_rajaus'];
      $_avoin_summa = $lasku['avoin_saldo_summa'];

      $_pos = ($_rajaus > 0 and $_avoin_summa < $_rajaus);
      $_neg = ($_rajaus < 0 and $_avoin_summa > $_rajaus);

      if ($_pos or $_neg) {
        continue;
      }
    }

    echo_saldovahvistus_rivi($lasku, $request, false, $viimeinen);
    $i++;
  }

  foreach ($request['valitut_laskut'] as $lasku) {
    echo_saldovahvistus_rivi($lasku, $request, true);
  }

  echo "</tbody>";

  echo "</table>";

  echo "<form id='lahetysformi' method='POST' action = ''>";
  echo "<input type='hidden' name='tee' value='laheta_sahkopostit' />";
  echo "<input type='hidden' name='ryhmittely_tyyppi' value='{$request['ryhmittely_tyyppi']}' />";
  echo "<input type='hidden' name='pp' value='{$request['pp']}' />";
  echo "<input type='hidden' name='kk' value='{$request['kk']}' />";
  echo "<input type='hidden' name='vv' value='{$request['vv']}' />";
  echo "<input type='submit' value='".t('Lähetä saldovahvistukset asiakkaille')."' />";
  echo "</form><br><br>";

  echo "<form method='POST' action=''>";
  echo "<input type='hidden' name='tee' value='poista_valinnat' />";
  echo "<input type='hidden' name='pp' value='{$request['pp']}' />";
  echo "<input type='hidden' name='kk' value='{$request['kk']}' />";
  echo "<input type='hidden' name='vv' value='{$request['vv']}' />";
  echo "<input type='submit' value='".t('Poista kaikki kerätyt saldovahvistusrivit')."' onclick='return tarkista(\"".t('Oletko varma että haluat poistaa kaikki valitut')."\");' />";
  echo "</form>";
}

function echo_saldovahvistus_rivi($saldovahvistusrivi, $request, $valitut = false, $viimeinen = false) {
  global $kukarow, $yhtiorow, $palvelin2;

  $lopetus = $palvelin2 . "tilauskasittely/saldovahvistus.php////tee={$request['tee']}//ryhmittely_tyyppi={$request['ryhmittely_tyyppi']}//ryhmittely_arvo={$request['ryhmittely_arvo']}//pp={$request['pp']}//kk={$request['kk']}//vv={$request['vv']}//saldovahvistus_viesti={$request['saldovahvistus_viesti']}";

  $tr_class = "";
  if ($viimeinen) {
    $tr_class = "border_bottom";
  }

  if ($valitut) {
    $saldovahvistusrivi['laskun_avoin_paiva'] = $saldovahvistusrivi['laskun_avoin_paiva'];
    $saldovahvistusrivi_class = "saldovahvistus_rivi_valittu";
  }
  else {
    $saldovahvistusrivi['laskun_avoin_paiva'] = date('Y-m-d', strtotime($request['paiva']));
    $saldovahvistusrivi_class = "saldovahvistus_rivi";
  }

  echo "<tr class='{$saldovahvistusrivi_class} aktiivi {$tr_class}'>";

  echo "<td valign='top'>";
  echo "<input type='hidden' class='laskun_avoin_paiva' value='{$saldovahvistusrivi['laskun_avoin_paiva']}' />";
  echo "<input type='hidden' class='ryhmittely_tyyppi' value='{$saldovahvistusrivi['ryhmittely_tyyppi']}' />";
  echo date('d.m.Y', strtotime($saldovahvistusrivi['laskun_avoin_paiva']));
  echo "</td>";

  echo "<td valign='top'>{$saldovahvistusrivi['ytunnus']}</td>";
  echo "<td valign='top'>";
  $i = 0;
  $asiakasnumerot_string = "";
  foreach ($saldovahvistusrivi['asiakasnumerot'] as $asiakasnumero) {
    $asiakasnumero['asiakasnumero'] = "<a href='{$palvelin2}yllapito.php?toim=asiakas&tunnus={$asiakasnumero['asiakas_tunnus']}&lopetus={$lopetus}'>{$asiakasnumero['asiakasnumero']}</a>";
    $asiakasnumerot_string .= $asiakasnumero['asiakasnumero'] . ' / ';
    if ($i != 0 and $i % 10 == 0) {
      $asiakasnumerot_string = substr($asiakasnumerot_string, 0, -3);
      $asiakasnumerot_string .= '<br/>';
    }
    $i++;
  }
  $asiakasnumerot_string = substr($asiakasnumerot_string, 0, -3);
  echo $asiakasnumerot_string;
  echo "</td>";
  echo "<td valign='top'>{$saldovahvistusrivi['asiakas_nimi']}</td>";
  echo "<td valign='top' align='right'>";
  echo $saldovahvistusrivi['avoin_saldo_summa'];
  echo "</td>";

  if ($valitut) {
    $saldovahvistusrivi['saldovahvistus_viesti'] = $saldovahvistusrivi['saldovahvistus_viesti'];
  }
  else {
    $saldovahvistusrivi['saldovahvistus_viesti'] = $request['saldovahvistus_viesti'];
  }

  echo "<td class='saldovahvistus_viesti' valign='top'>";
  echo $saldovahvistusrivi['saldovahvistus_viesti'];
  if ($saldovahvistusrivi['asiakas']['talhal_email'] == '') {
    echo "<br/>";
    echo "<font class='error'>{$request["t"]["email"]}</font>";
  }
  echo "</td>";

  echo "<td>";
  echo "<input type='checkbox' class='saldovahvistus_rivi_valinta' CHECKED />";
  echo "</td>";

  $_id = implode('', $saldovahvistusrivi['lasku_tunnukset']);

  echo "<td>";
  echo "<input type='hidden' class='saldovahvistus_rivi_sahkoposti_valinta_id' value='{$_id}' />";
  echo "<input type='checkbox' class='saldovahvistus_rivi_sahkoposti_valinta' />";
  echo "</td>";

  // .nayta_pdf_td ja .lasku_tunnus, jotta .saldovahvistus_rivi_valinta löytää lasku_tunnukset, jotka lähtee ajaxin mukana
  echo "<td class='back nayta_pdf_td'>";
  echo "<form method='POST' action='' id='{$_id}' name='{$_id}' autocomplete='off'>";
  echo "<input type='submit' value='{$request["t"]["nayta_pdf"]}' onClick=\"js_openFormInNewWindow('{$_id}', '{$_id}'); return false;\">";
  echo "<input type='hidden' name='tee' value='NAYTATILAUS' />";
  echo "<input type='hidden' name='nayta_pdf' value='1' />";
  echo "<input type='hidden' name='pp' value='{$request['pp']}' />";
  echo "<input type='hidden' name='kk' value='{$request['kk']}' />";
  echo "<input type='hidden' name='vv' value='{$request['vv']}' />";
  echo "<input type='hidden' name='saldovahvistus_viesti' value='{$saldovahvistusrivi['saldovahvistus_viesti']}' />";
  echo "<input type='hidden' name='ryhmittely_tyyppi' value='{$request['ryhmittely_tyyppi']}' />";
  echo "<input type='hidden' name='saldovahvistus_rivi_ryhmittely_tyyppi' value='{$saldovahvistusrivi['ryhmittely_tyyppi']}' />";
  echo "<input type='hidden' name='ryhmittely_arvo' value='{$request['ryhmittely_arvo']}' />";
  foreach ($saldovahvistusrivi['lasku_tunnukset'] as $lasku_tunnus) {
    echo "<input type='hidden' class='lasku_tunnus' name='lasku_tunnukset[]' value='{$lasku_tunnus}' />";
  }
  echo "</form>";

  echo "<br/>";

  echo "<form method='POST' action=''>";
  echo "<input type='submit' value='{$request["t"]["tulosta_pdf"]}' />";
  echo "<input type='hidden' name='tee' value='tulosta_saldovahvistus_pdf' />";
  echo "<input type='hidden' name='pp' value='{$request['pp']}' />";
  echo "<input type='hidden' name='kk' value='{$request['kk']}' />";
  echo "<input type='hidden' name='vv' value='{$request['vv']}' />";
  echo "<input type='hidden' name='saldovahvistus_viesti' value='{$saldovahvistusrivi['saldovahvistus_viesti']}' />";
  echo "<input type='hidden' name='saldovahvistus_rivi_ryhmittely_tyyppi' value='{$saldovahvistusrivi['ryhmittely_tyyppi']}' />";
  echo "<input type='hidden' name='ryhmittely_tyyppi' value='{$request['ryhmittely_tyyppi']}' />";
  echo "<input type='hidden' name='ryhmittely_arvo' value='{$request['ryhmittely_arvo']}' />";
  foreach ($saldovahvistusrivi['lasku_tunnukset'] as $lasku_tunnus) {
    echo "<input type='hidden' name='lasku_tunnukset[]' value='{$lasku_tunnus}' />";
  }
  echo "</form>";
  echo "</td>";

  echo "</tr>";

  if (!$valitut) {
    //Oletuksena lisätään kaikki haetut saldovahvistusrivit valituiksi
    $lasku_tunnukset_key = implode('', $saldovahvistusrivi['lasku_tunnukset']);
    lisaa_sessioon_saldovahvistus_rivi($lasku_tunnukset_key, $saldovahvistusrivi);
  }
}

function echo_kayttoliittyma($request) {
  global $kukarow, $yhtiorow;

  echo "<form method='POST' action=''>";
  echo "<input type='hidden' name='tee' value='aja_saldovahvistus' />";
  echo "<table>";

  echo "<tr>";
  echo "<th>" . t('Ryhmittely') . ":</th>";
  echo "<td>";
  echo "<select name='ryhmittely_tyyppi'>";
  $sel = "";
  foreach ($request['ryhmittely_tyypit'] as $ryhmittely_tyyppi_key => $ryhmittely_tyyppi) {
    if ($request['ryhmittely_tyyppi'] == $ryhmittely_tyyppi_key) {
      $sel = "SELECTED";
    }
    echo "<option value='{$ryhmittely_tyyppi_key}' {$sel}>{$ryhmittely_tyyppi}</option>";
    $sel = "";
  }
  echo "</select>";
  echo "<input type='text' name='ryhmittely_arvo' value='{$request['ryhmittely_arvo']}'/>";

  echo '(' . t('tyhjä') . ' = ' . t('saat kaikki ytunnukset') . ')';
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t('Päivämäärä') . ":</th>";
  echo "<td>";
  echo "<input type='text' name='pp' size='3' value='{$request['pp']}' />";
  echo "<input type='text' name='kk' size='3' value='{$request['kk']}' />";
  echo "<input type='text' name='vv' size='5' value='{$request['vv']}' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t('Saldovahvistuksen viesti') . ":</th>";
  echo "<td>";
  echo "<select name='saldovahvistus_viesti'>";
  $sel = "";
  foreach ($request['saldovahvistus_viestit'] as $saldovahvistus_viesti) {
    if ($request['saldovahvistus_viesti'] == $saldovahvistus_viesti['selite']) {
      $sel = "SELECTED";
    }
    echo "<option value='{$saldovahvistus_viesti['selite']}' {$sel}>{$saldovahvistus_viesti['selite']}</option>";
    $sel = "";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t('Avoin saldo rajaus').":</th>";
  echo "<td>";
  echo "<input type='text' name='avoin_saldo_rajaus' value='{$request['avoin_saldo_rajaus']}' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t('Tallenna exceliin')."</th>";
  echo "<td>";
  $chk = "";
  if (!empty($request['tallenna_exceliin'])) {
    $chk = "CHECKED";
  }
  echo "<input type='checkbox' name='tallenna_exceliin' {$chk} />";
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "<input type='submit' value='" . t('Aja') . "' />";
  echo "</form>";

  if (!empty($_SESSION['valitut_laskut'])) {
    echo "<form method='POST' action=''>";
    echo "<input type='hidden' name='tee' value='valitut_laskut_haettu' />";
    echo "<input type='hidden' name='pp' value='{$request['pp']}' />";
    echo "<input type='hidden' name='kk' value='{$request['kk']}' />";
    echo "<input type='hidden' name='vv' value='{$request['vv']}' />";
    echo "<input type='submit' value='".t("Hae kerätyt saldovahvistusrivit (%d kpl)", "", count($_SESSION['valitut_laskut']))."' />";
    echo "</form>";


    echo "<form method='POST' action=''>";
    echo "<input type='hidden' name='tee' value='poista_valinnat' />";
    echo "<input type='hidden' name='pp' value='{$request['pp']}' />";
    echo "<input type='hidden' name='kk' value='{$request['kk']}' />";
    echo "<input type='hidden' name='vv' value='{$request['vv']}' />";
    echo "<input type='submit' value='".t('Poista kaikki kerätyt saldovahvistusrivit')."' onclick='return tarkista(\"".t('Oletko varma että haluat poistaa kaikki valitut')."\");' />";
    echo "</form>";
  }
}

function generoi_custom_excel_tiedosto($request) {
  global $kukarow, $yhtiorow;

  $xls = new pupeExcel();
  $rivi = 0;
  $sarake = 0;

  $xls->write($rivi, $sarake, t('Päivämäärä'), array("bold" => TRUE));
  $sarake++;

  $xls->write($rivi, $sarake, t('Ytunnus'), array("bold" => TRUE));
  $sarake++;

  $xls->write($rivi, $sarake, t('Asiakasnumero'), array("bold" => TRUE));
  $sarake++;

  $xls->write($rivi, $sarake, t('Nimi'), array("bold" => TRUE));
  $sarake++;

  $xls->write($rivi, $sarake, t('Saldo'), array("bold" => TRUE));
  $sarake++;

  $xls->write($rivi, $sarake, t('Viesti'), array("bold" => TRUE));
  $sarake++;

  $xls->write($rivi, $sarake, t('Valittu'), array("bold" => TRUE));
  $sarake++;

  $rivi++;
  $sarake = 0;

  foreach ($request['valitut_laskut'] as $valittu_rivi) {
    $xls->write($rivi, $sarake, date('d.m.Y', strtotime($valittu_rivi['laskun_avoin_paiva'])));
    $sarake++;

    $xls->write($rivi, $sarake, $valittu_rivi['ytunnus']);
    $sarake++;

    $asiakasnumerot_string = "";
    foreach ($valittu_rivi['asiakasnumerot'] as $asiakasnumero) {
      $asiakasnumerot_string .= $asiakasnumero['asiakasnumero'] . ' / ';
    }
    $asiakasnumerot_string = substr($asiakasnumerot_string, 0, -3);

    $xls->write($rivi, $sarake, $asiakasnumerot_string);
    $sarake++;

    $xls->write($rivi, $sarake, $valittu_rivi['asiakas_nimi']);
    $sarake++;

    $xls->write($rivi, $sarake, $valittu_rivi['avoin_saldo_summa']);
    $sarake++;

    $xls->write($rivi, $sarake, $valittu_rivi['saldovahvistus_viesti']);
    $sarake++;

    $xls->write($rivi, $sarake, t('Kyllä'));
    $sarake++;

    $rivi++;
    $sarake = 0;
  }

  foreach ($request['laskut'] as $saldovahvistusrivi) {
    $xls->write($rivi, $sarake, date('d.m.Y', strtotime($request['paiva'])));
    $sarake++;

    $xls->write($rivi, $sarake, $saldovahvistusrivi['ytunnus']);
    $sarake++;

    $asiakasnumerot_string = "";
    foreach ($saldovahvistusrivi['asiakasnumerot'] as $asiakasnumero) {
      $asiakasnumerot_string .= $asiakasnumero['asiakasnumero'] . ' / ';
    }
    $asiakasnumerot_string = substr($asiakasnumerot_string, 0, -3);

    $xls->write($rivi, $sarake, $asiakasnumerot_string);
    $sarake++;

    $xls->write($rivi, $sarake, $saldovahvistusrivi['asiakas_nimi']);
    $sarake++;

    $xls->write($rivi, $sarake, $saldovahvistusrivi['avoin_saldo_summa']);
    $sarake++;

    $xls->write($rivi, $sarake, $request['saldovahvistus_viesti']);
    $sarake++;

    $xls->write($rivi, $sarake, t('Ei'));
    $sarake++;

    $rivi++;
    $sarake = 0;
  }

  return $xls->close();
}
