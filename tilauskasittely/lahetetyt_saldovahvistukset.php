<?php

require "../inc/parametrit.inc";
require 'myyntires/paperitiliote_saldovahvistus.php';

if ($ajax_request) {
  exit;
}

if (!isset($nayta_pdf)) {
  $nayta_pdf = 0;
}

if ($nayta_pdf != 1) {
  echo "<font class='head'>".t("Lähetetyt saldovahvistukset")."</font><hr>";
}

if (!isset($ppa)) {
  $ppa = date('d');
}
if (!isset($kka)) {
  $kka = date('m', strtotime('now - 1 month'));

  //vuoden vaihde bugfix
  if ($kka == 12) {
    $vva = date('Y', strtotime('now - 1 year'));
  }
}
if (!isset($vva)) {
  $vva = date('Y');
}
if (!isset($ppl)) {
  $ppl = date('d');
}
if (!isset($kkl)) {
  $kkl = date('m');
}
if (!isset($vvl)) {
  $vvl = date('Y');
}

if (checkdate($kka, $ppa, $vva)) {
  $alku_paiva = date('Y-m-d', strtotime("{$vva}-{$kka}-{$ppa}"));
}
else {
  $alku_paiva = date('Y-m-d', strtotime("now - 1 month"));
}

if (checkdate($kkl, $ppl, $vvl)) {
  $loppu_paiva = date('Y-m-d', strtotime("{$vvl}-{$kkl}-{$ppl}"));
}
else {
  $loppu_paiva = date('Y-m-d', strtotime("now"));
}

$request = array(
  'tee'           => $tee,
  'ryhmittely_tyyppi'     => $ryhmittely_tyyppi,
  'ryhmittely_arvo'     => $ryhmittely_arvo,
  'lasku_tunnukset'     => $lasku_tunnukset,
  'saldovahvistus'     => $saldovahvistus,
  'ppa'           => $ppa,
  'kka'           => $kka,
  'vva'           => $vva,
  'ppl'           => $ppl,
  'kkl'           => $kkl,
  'vvl'           => $vvl,
  'alku_paiva'       => $alku_paiva,
  'loppu_paiva'       => $loppu_paiva,
  'lahetetyt'         => true,
  'saldovahvistus_tunnus'   => $saldovahvistus_tunnus,
);

$t = array(
  "email" => t("Email puuttuu"),
  "nayta_pdf" => t("Näytä pdf"),
  "tulosta_pdf" => t("Tulosta pdf"),
  "kohdistamaton" => t("Kohdistamaton suoritus"),
  "laheta_asiakkaalle" => t("Lähetä asiakkaalle"),
);

$request["t"] = $t;

$request['haku_tyypit'] = array(
  'ytunnus'   => t('Ytunnus'),
  'asiakasnro' => t('Asiakasnumero'),
  'nimi'     => t('Nimi'),
);

if (isset($saldovahvistus_tunnus)) {
  $query = "SELECT asiakas.kieli
          FROM saldovahvistukset
          JOIN asiakas ON (asiakas.yhtio = saldovahvistukset.yhtio AND asiakas.tunnus = saldovahvistukset.liitostunnus)
          WHERE saldovahvistukset.yhtio = '{$kukarow['yhtio']}'
          AND saldovahvistukset.tunnus = '{$saldovahvistus_tunnus}'";
  $kielirow = mysql_fetch_assoc(pupe_query($query));
}

$kieli = "";
if (!empty($kielirow)) {
  $kieli = $kielirow["kieli"];
}

$request['saldovahvistus_viestit'] = hae_saldovahvistus_viestit($kieli);

echo_lahetetyt_saldovahvistukset_kayttoliittyma($request);

if ($request['tee'] == 'hae_lahetetyt_saldovahvistukset') {
  js_openFormInNewWindow();
  $request['saldovahvistukset'] = hae_lahetetyt_saldovahvistukset($request);

  echo_lahetetyt_saldovahvistukset($request);
}
elseif ($request['tee'] == 'NAYTATILAUS' or $request['tee'] == 'tulosta_saldovahvistus_pdf') {
  //requestissa tulee saldovahvistus_tunnus. Tällöin $saldovahvistus arrayssa on vain yksi solu
  $saldovahvistus = hae_lahetetyt_saldovahvistukset($request);

  $saldovahvistus['saldovahvistus_viesti'] = search_array_key_for_value_recursive($request['saldovahvistus_viestit'], 'selite', $saldovahvistus['saldovahvistus_viesti']);
  $saldovahvistus['saldovahvistus_viesti'] = $saldovahvistus['saldovahvistus_viesti'][0];
  $saldovahvistus['laskun_avoin_paiva'] = $saldovahvistus['avoin_saldo_pvm'];

  if ($saldovahvistus['ryhmittely_tyyppi'] == 'ytunnus') {
    $boss = true;
  }
  else {
    $boss = false;
  }

  $saldovahvistus['tiliotepvm'] = $saldovahvistus['laskun_avoin_paiva'];

  $pdf_filepath = hae_saldovahvistus_pdf($saldovahvistus, $boss);

  if ($request['tee'] == 'NAYTATILAUS') {
    echo file_get_contents($pdf_filepath);
  }
  elseif ($request['tee'] == 'tulosta_saldovahvistus_pdf') {
    $kirjoitin_komento = hae_kayttajan_kirjoitin();

    exec($kirjoitin_komento['komento'].' '.$pdf_filepath);
  }

  //unset, jotta käyttöliittymään tulisi rajausten mukaiset saldovahvistukset.
  unset($request['saldovahvistus_tunnus']);

  $request['saldovahvistukset'] = hae_lahetetyt_saldovahvistukset($request);
  echo_lahetetyt_saldovahvistukset($request);
}
elseif ($request['tee'] == 'laheta_sahkoposti') {
  list($lahetetyt_count, $ei_lahetetty_count, $ei_lahetetyt) = generoi_saldovahvistus_sahkopostit($request, true);
}

echo "<br/>";
echo "<br/>";

echo "<font class='head'>".t("Lähetettyjen saldovahvistusten selaaminen")."</font><hr>";

echo_lahetettyjen_saldovahvistusten_selaaminen_kayttoliittyma($request);

if ($request['tee'] == 'hae_lahetetyt_saldovahvistukset_aikavali') {
  js_openFormInNewWindow();
  $request['saldovahvistukset'] = hae_lahetetyt_saldovahvistukset($request);

  echo_lahetetyt_saldovahvistukset_aika_vali($request);
}

if (isset($lahetetyt_count) and isset($ei_lahetetty_count)) {
  echo "<br/>";
  echo "<br/>";
  echo '<font class="message">'.$lahetetyt_count.' '.t('sähköpostia lähetetty').'</font>';
  if ($ei_lahetetty_count > 0) {
    echo "<br/>";
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
  .saldovahvistukset_per_paiva_not_hidden {
    cursor: pointer;
  }
  .saldovahvistukset_per_paiva_hidden {
    cursor: pointer;
  }

  .saldovahvistusrivi_not_hidden {
  }
  .saldovahvistusrivi_hidden {
    display: none;
  }

  .arrow_img {
    float: right;
  }
</style>
<script>
  $(document).ready(function() {
    bind_saldovahvistus_paiva_tr();
  });

  function bind_saldovahvistus_paiva_tr() {
    $('.saldovahvistus_paiva_tr').click(function() {
      var childress_class = $(this).find('.group_class').val();
      if ($(this).hasClass('saldovahvistukset_per_paiva_hidden')) {
        $('.' + childress_class).addClass('saldovahvistusrivi_not_hidden');
        $('.' + childress_class).removeClass('saldovahvistusrivi_hidden');

        $(this).addClass('saldovahvistukset_per_paiva_not_hidden');
        $(this).removeClass('saldovahvistukset_per_paiva_hidden');

        $(this).find('.arrow_img').attr('src', $('#right_arrow').val());
      }
      else {
        $('.' + childress_class).addClass('saldovahvistusrivi_hidden');
        $('.' + childress_class).removeClass('saldovahvistusrivi_not_hidden');

        $(this).addClass('saldovahvistukset_per_paiva_hidden');
        $(this).removeClass('saldovahvistukset_per_paiva_not_hidden');

        $(this).find('.arrow_img').attr('src', $('#down_arrow').val());
      }
    });
  }
</script>
<?php

echo "<input type='hidden' id='down_arrow' value='{$palvelin2}pics/lullacons/arrow-single-down-green.png' />";
echo "<input type='hidden' id='right_arrow' value='{$palvelin2}pics/lullacons/arrow-single-right-green.png' />";

require 'inc/footer.inc';

function echo_lahetetyt_saldovahvistukset($request) {
  global $kukarow, $yhtiorow;

  echo "<table>";
  if (!empty($request['saldovahvistukset'])) {
    foreach ($request['saldovahvistukset'] as $saldovahvistus_per_ktunnus_ytunnus) {
      foreach ($saldovahvistus_per_ktunnus_ytunnus as $saldovahvistus_per_ytunnus) {
        foreach ($saldovahvistus_per_ytunnus as $saldovahvistus) {
          echo_lahetetty_saldovahvistus_rivi($saldovahvistus, $request);
        }
      }
    }
  }
  else {
    echo "<tr>";
    echo "<td>".t('Ei lähetettyjä saldovahvistuksia')."</td>";
    echo "</tr>";
  }

  echo "</table>";
}

function echo_lahetetty_saldovahvistus_rivi($saldovahvistusrivi, $request, $hidden = false) {
  global $kukarow, $yhtiorow;

  $tr_class = "saldovahvistusrivi_not_hidden";
  if ($hidden) {
    $group_class = "laskut_".str_replace('-', '', $saldovahvistusrivi['lahetys_paiva']);
    $tr_class = "saldovahvistusrivi_hidden {$group_class}";
  }
  echo "<tr class='{$tr_class}'>";

  echo "<td valign='top'>";
  $i = 0;
  $asiakasnumerot_string = "";
  //asiakasnumerot array:ssä on asiakasnumero ja tunnus, jotta uusi saldovahvistusnäkymässä asiakasnumero linkki osaa ohjata oikeaan asiakkaaseen
  foreach ($saldovahvistusrivi['asiakasnumerot'] as $asiakasnumero_ja_tunnus) {
    $asiakasnumerot_string .= $asiakasnumero_ja_tunnus['asiakasnumero'].' / ';
    if ($i != 0 and $i % 10 == 0) {
      $asiakasnumerot_string = substr($asiakasnumerot_string, 0, -3);
      $asiakasnumerot_string .= '<br/>';
    }
    $i++;
  }
  $asiakasnumerot_string = substr($asiakasnumerot_string, 0, -3);
  echo $asiakasnumerot_string;
  echo "</td>";

  echo "<td valign='top'>";
  echo $saldovahvistusrivi['asiakas_nimi'];
  echo "</td>";

  echo "<td valign='top'>";
  echo $saldovahvistusrivi['avoin_saldo_summa'];
  echo "</td>";

  echo "<td valign='top' class='back'>";
  echo "<form method='POST' action='' id='".implode('', $saldovahvistusrivi['lasku_tunnukset'])."' name='".implode('', $saldovahvistusrivi['lasku_tunnukset'])."' autocomplete='off'>";
  echo "<input type='submit' value='{$request["t"]["nayta_pdf"]}' onClick=\"js_openFormInNewWindow('".implode('', $saldovahvistusrivi['lasku_tunnukset'])."', '".implode('', $saldovahvistusrivi['lasku_tunnukset'])."'); return false;\">";
  echo "<input type='hidden' name='tee' value='NAYTATILAUS' />";
  echo "<input type='hidden' name='nayta_pdf' value='1' />";
  echo "<input type='hidden' name='saldovahvistus_viesti' value='{$request['saldovahvistus_viesti']}' />";
  echo "<input type='hidden' name='ryhmittely_tyyppi' value='{$request['ryhmittely_tyyppi']}' />";
  echo "<input type='hidden' name='ryhmittely_arvo' value='{$request['ryhmittely_arvo']}' />";
  echo "<input type='hidden' name='saldovahvistus_tunnus' value='{$saldovahvistusrivi['saldovahvistus_tunnus']}' />";
  echo "</form>";

  echo "<br/>";

  echo "<form method='POST' action=''>";
  echo "<input type='submit' value='{$request["t"]["tulosta_pdf"]}' />";
  echo "<input type='hidden' name='tee' value='tulosta_saldovahvistus_pdf' />";
  echo "<input type='hidden' name='saldovahvistus_viesti' value='{$request['saldovahvistus_viesti']}' />";
  echo "<input type='hidden' name='ryhmittely_tyyppi' value='{$request['ryhmittely_tyyppi']}' />";
  echo "<input type='hidden' name='ryhmittely_arvo' value='{$request['ryhmittely_arvo']}' />";
  echo "<input type='hidden' name='saldovahvistus_tunnus' value='{$saldovahvistusrivi['saldovahvistus_tunnus']}' />";
  echo "</form>";

  echo "<br/>";

  echo "<form method='POST' action=''>";
  echo "<input type='submit' value='{$request["t"]["laheta_asiakkaalle"]}' />";
  echo "<input type='hidden' name='tee' value='laheta_sahkoposti' />";
  echo "<input type='hidden' name='saldovahvistus[saldovahvistus_viesti]' value='{$saldovahvistusrivi['saldovahvistus_viesti']}' />";
  echo "<input type='hidden' name='saldovahvistus[laskun_avoin_paiva]' value='{$saldovahvistusrivi['avoin_saldo_pvm']}' />";
  echo "<input type='hidden' name='saldovahvistus[saldovahvistus_tunnus]' value='{$saldovahvistusrivi['saldovahvistus_tunnus']}' />";
  echo "<input type='hidden' name='saldovahvistus[ryhmittely_tyyppi]' value='{$saldovahvistusrivi['ryhmittely_tyyppi']}' />";
  echo "</form>";
  echo "</td>";

  echo "</tr>";
}

function echo_lahetetyt_saldovahvistukset_kayttoliittyma($request) {
  global $kukarow, $yhtiorow;

  echo "<table>";

  echo "<tr>";
  echo "<th>".t('Haku').":</th>";
  echo "<td>";

  echo "<form method='POST' action=''>";

  echo "<input type='hidden' name='tee' value='hae_lahetetyt_saldovahvistukset' />";

  echo "<select name='ryhmittely_tyyppi'>";
  $sel = "";
  foreach ($request['haku_tyypit'] as $ryhmittely_tyyppi_key => $ryhmittely_tyyppi) {
    if ($request['ryhmittely_tyyppi'] == $ryhmittely_tyyppi_key) {
      $sel = "SELECTED";
    }
    echo "<option value='{$ryhmittely_tyyppi_key}' {$sel}>{$ryhmittely_tyyppi}</option>";
    $sel = "";
  }
  echo "</select>";

  echo "<input type='text' name='ryhmittely_arvo' value='{$request['ryhmittely_arvo']}' />";

  echo "<input type='submit' value='".t('Etsi')."' />";

  echo "</form>";

  echo "</td>";
  echo "</tr>";

  echo "</table>";
}

function echo_lahetettyjen_saldovahvistusten_selaaminen_kayttoliittyma($request) {
  global $kukarow, $yhtiorow;

  echo "<form method='POST' action=''>";

  echo "<input type='hidden' name='tee' value='hae_lahetetyt_saldovahvistukset_aikavali' />";

  echo "<table>";

  echo "<tr>";
  echo "<th>".t('Alkupäivämäärä').":</th>";
  echo "<td>";
  echo "<input type='text' name='ppa' value='{$request['ppa']}' size='3' />";
  echo "<input type='text' name='kka' value='{$request['kka']}' size='3' />";
  echo "<input type='text' name='vva' value='{$request['vva']}' size='5' />";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t('Loppupäivämäärä').":</th>";
  echo "<td>";
  echo "<input type='text' name='ppl' value='{$request['ppl']}' size='3' />";
  echo "<input type='text' name='kkl' value='{$request['kkl']}' size='3' />";
  echo "<input type='text' name='vvl' value='{$request['vvl']}' size='5' />";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<input type='submit' value='".t('Etsi')."' />";

  echo "</form>";
}

function echo_lahetetyt_saldovahvistukset_aika_vali($request) {
  global $kukarow, $yhtiorow, $palvelin2;

  echo "<table>";

  echo "<tr>";
  echo "<th>".t('Lähetetyt saldovahvistukset')."</th>";
  echo "</tr>";

  if (!empty($request['saldovahvistukset'])) {
    foreach ($request['saldovahvistukset'] as $paiva => $saldovahvistukset_per_paiva) {
      echo "<tr class='saldovahvistus_paiva_tr saldovahvistukset_per_paiva_hidden'>";

      echo "<td>";
      echo date('d.m.Y', strtotime($paiva));
      echo "<input type='hidden' class='group_class' value='laskut_".str_replace('-', '', $paiva)."' />";
      echo "<img class='arrow_img' src='{$palvelin2}pics/lullacons/arrow-single-down-green.png' />";
      echo "</td>";

      echo "<td>";
      echo count($saldovahvistukset_per_paiva);
      echo "</td>";

      foreach ($saldovahvistukset_per_paiva as $saldovahvistusrivi_ktunnus) {
        foreach ($saldovahvistusrivi_ktunnus as $saldovahvistusrivi) {
          echo_lahetetty_saldovahvistus_rivi($saldovahvistusrivi, $request, true);
        }
      }

      echo "</tr>";
    }
  }
  else {
    echo "<tr>";
    echo "<td>".t('Ei lähetettyjä saldovahvistuksia')."</td>";
    echo "</tr>";
  }

  echo "</table>";
}
