<?php

ini_set("memory_limit", "5G");

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

//Tänne tämän tiedoston ajax requestit
if ($ajax_request) {
  if ($poista_laskut_ja_tilioinnit) {
    poista_laskut($lasku_tunnukset);
    poista_tilioinnit($lasku_tunnukset);

    echo json_encode(true);
    exit;
  }
}

if ($toim == 'KATEINEN') {
  echo "<font class='head'>".t("Käteismyyntien hyväksyminen")."</font><hr>";
}
else {
  echo "<font class='head'>".t("Laskuaineiston hyväksyminen")."</font><hr>";
}
?>
<style>
  .lasku_tr_hidden {
    display:none;
  }
  .lasku_tr {
    background: none repeat scroll 0 0 #DADDDE;
  }
</style>
<script>
  $(document).ready(function() {
    bind_paiva_tr_click();

    bind_poista_laskut();

    append_hyvaksymis_message();
  });

  function bind_paiva_tr_click() {
    $('.paiva_tr_hidden').click(function(event) {
      if (event.target.nodeName === 'TD' || event.target.nodeName === 'IMG') {
        var paivittain_key = $(this).find('.paivittain_key').val();
        if ($(this).hasClass('paiva_tr_hidden')) {
          $(this).addClass('paiva_tr');
          $(this).removeClass('paiva_tr_hidden');


          $(this).parent().find('.' + paivittain_key).addClass('lasku_tr');
          $(this).parent().find('.' + paivittain_key).removeClass('lasku_tr_hidden');

          $(this).find('.porautumis_img').attr('src', $('#right_arrow').val());
        }
        else {
          $(this).removeClass('paiva_tr');
          $(this).addClass('paiva_tr_hidden');

          $(this).parent().find('.' + paivittain_key).removeClass('lasku_tr');
          $(this).parent().find('.' + paivittain_key).addClass('lasku_tr_hidden');

          $(this).find('.porautumis_img').attr('src', $('#down_arrow').val());
        }

        var nakyvien_lasku_tr_lkm = $('.lasku_tr').length;

        if (nakyvien_lasku_tr_lkm > 0) {
          //jos jokin päivä / päivät on avattu vain sen päivän / päivien laskut pitää olla disabloimatta, muut disabloituna
          $('.lasku_tr_hidden').find('.lasku_tunnus').attr('disabled', 'disabled');
        }
        else {
          //jos kaikki laskut on piilotettu kaikki inputit pitää olla disabloimatta
          $('.lasku_tr_hidden').find('.lasku_tunnus').removeAttr('disabled');
        }
      }
    });
  }

  function bind_poista_laskut() {
    $('.poista_paivan_laskuaineisto').click(function(event) {
      event.preventDefault();
      var ok = confirm($('#confirm_message').val());
      if (ok) {
        var paiva_tr = $(this).parent().parent();
        var paivittain_key = $(paiva_tr).find('.paivittain_key').val();

        var lasku_trs = $(paiva_tr).parent().find('.' + paivittain_key);
        var lasku_tunnukset = [];
        $.each(lasku_trs, function(index, value) {
          if ($(value).find('.lasku_tunnus').val() !== undefined) {
            lasku_tunnukset.push($(value).find('.lasku_tunnus').val());
          }
          else {
            //kyseessä virheellinen lasku. sen tunnus on eri kentässä kuin virheettömien.
            lasku_tunnukset.push($(value).find('.lasku_tunnus_virheellinen').val());
          }
        });

        var request_obj = poista_laskut_ja_tilioinnit(lasku_tunnukset);

        request_obj.done(function() {
          if (console && console.log) {
            console.log('Laskujen ja tiliöintien poisto onnistui');
          }

          //paiva_tr
          $(paiva_tr).remove();
          $(lasku_trs).remove();
        }).fail(function() {
          if (console && console.log) {
            console.log('Laskujen ja tiliöintien poisto EPÄONNISTUI');
          }
          alert($('#poisto_epaonnistui_message').val());
        });
      }
    });
  }

  function poista_laskut_ja_tilioinnit(lasku_tunnukset) {
    return $.ajax({
      async: true,
      type: 'POST',
      dataType: 'JSON',
      data: {
        lasku_tunnukset: lasku_tunnukset
      },
      url: 'laskuaineiston_hyvaksyminen.php?ajax_request=1&poista_laskut_ja_tilioinnit=1&no_head=yes'
    });
  }

  function append_hyvaksymis_message() {
    $('tr').click(function() {
      var hyvaksy_message = $('#hyvaksymis_message').val();
      if ($('.paiva_tr').length === 0) {
        hyvaksy_message = hyvaksy_message.replace('kappaletta', 'kaikki');
      }
      else {
        hyvaksy_message = hyvaksy_message.replace('kappaletta', $('.paiva_tr').length + ' ' + $('#paiva_message').val());
      }
      $('#hyvaksy_wrapper').html(hyvaksy_message);
    });
  }
</script>
<?php

$request = array(
  'tee'             => $tee,
  'toim'             => $toim,
  'lasku_tunnukset'       => $lasku_tunnukset,
  'lasku_tyyppi_rajoitin'     => $lasku_tyyppi_rajoitin,
  'lasku_alku_pvm_rajoitin'   => $lasku_alku_pvm_rajoitin,
  'lasku_loppu_pvm_rajoitin'   => $lasku_loppu_pvm_rajoitin,
);

if (empty($request['lasku_alku_pvm_rajoitin'])) {
  $request['lasku_alku_pvm_rajoitin'] = date('d.m.Y', strtotime('now - 30 day'));
}

if (empty($request['lasku_loppu_pvm_rajoitin'])) {
  $request['lasku_loppu_pvm_rajoitin'] = date('d.m.Y');
}

if ($request['tee'] == 'hyvaksy_laskuaineisto' and !empty($request['lasku_tunnukset'])) {
  hyvaksy_lasku_aineisto($request);
}

$laskut_paivakohtaisesti = hae_hyvaksyttava_laskuaineisto($request);

echo_rajoitin_table($request);
echo "<br/>";
echo "<br/>";
if (!empty($laskut_paivakohtaisesti)) {
  echo "<input type='hidden' id='confirm_message' value='".t("Oletko varma että haluat poistaa päivän laskuaineiston")."?' />";
  echo "<input type='hidden' id='poisto_epaonnistui_message' value='".t("Poisto epäonnistui")."!' />";
  echo "<input type='hidden' id='hyvaksymis_message' value='".t("Olet hyväksymässä")." kappaletta ".t("aineistot")."' />";
  echo "<input type='hidden' id='paiva_message' value='".t("päivän")."' />";

  echo "<form action='' method='POST'>";
  echo "<input type='hidden' name='tee' value='hyvaksy_laskuaineisto' />";
  echo "<input id='hyvaksy_button' type='submit' value='".t("Hyväksy virheettömät laskut")."' onclick=\"return confirm('".t("Oletko varma että haluat hyväksyä laskut")."?')\" />";
  echo "<span id='hyvaksy_wrapper'>";
  echo t("Olet hyväksymässä kaikki aineistot");
  echo "</span>";
  echo "<br/>";
  echo "<br/>";
  echo_laskuaineisto_table($laskut_paivakohtaisesti, $request);
  echo "</form>";
}
else {
  echo t("Ei hyväksyttäviä / hyväksyttyjä laskuja järjestelmässä");
}

function hyvaksy_lasku_aineisto($request) {
  global $kukarow, $yhtiorow;

  $onnistuneet_insertoinnit = array(
    'laskut'   => array(),
    'tilioinnit' => array()
  );

  $tiliointi_query = "SELECT *
                      FROM futur_tiliointi
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND ltunnus IN ('".implode("', '", $request['lasku_tunnukset'])."')";
  $tilitointi_result = pupe_query($tiliointi_query);
  $tilioinnit = array();
  while ($tiliointi = mysql_fetch_assoc($tilitointi_result)) {
    unset($tiliointi['hyvaksytty']);
    unset($tiliointi['kuka_hyvaksyi']);
    unset($tiliointi['hyvaksytty_aika']);
    $tiliointi['laadittu'] = date('Y-m-d H:i:s');

    $tilioinnit[$tiliointi['ltunnus']][] = $tiliointi;
  }

  $query = "SELECT *
            FROM futur_lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  IN ('".implode("', '", $request['lasku_tunnukset'])."')";
  $result = pupe_query($query);
  while ($lasku = mysql_fetch_assoc($result)) {
    $lasku_tunnus_temp = $lasku['tunnus'];
    unset($lasku['hyvaksytty']);
    unset($lasku['kuka_hyvaksyi']);
    unset($lasku['hyvaksytty_aika']);
    unset($lasku['tunnus']);
    $lasku['luontiaika'] = date('Y-m-d H:i:s');


    $copy_query = "INSERT INTO
                   lasku (".implode(", ", array_keys($lasku)).")
                   VALUES('".implode("', '", array_values($lasku))."')";

    pupe_query($copy_query);

    if (mysql_affected_rows() == 0) {
      continue;
    }

    $uusi_lasku_tunnus = mysql_insert_id();

    $query = "SELECT *
              FROM futur_laskun_lisatiedot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$lasku_tunnus_temp}'";
    $lresult = pupe_query($query);
    $laskun_lisatiedot = mysql_fetch_assoc($lresult);
    unset($laskun_lisatiedot['tunnus']);
    $laskun_lisatiedot['otunnus'] = $uusi_lasku_tunnus;
    $laskun_lisatiedot['muutospvm'] = date('Y-m-d H:i:s');
    $laskun_lisatiedot['muuttaja'] = $kukarow['kuka'];
    $laskun_lisatiedot['luontiaika'] = date('Y-m-d H:i:s');

    $copy_query = "INSERT INTO
                   laskun_lisatiedot (".implode(", ", array_keys($laskun_lisatiedot)).")
                   VALUES('".implode("', '", array_values($laskun_lisatiedot))."')";

    pupe_query($copy_query);

    if (mysql_affected_rows() > 0) {
      //laskun insertöiminen onnistui voidaan insertöidä myös tiliöinnit
      foreach ($tilioinnit[$lasku_tunnus_temp] as $tiliointi) {
        $tiliointi_tunnus_temp = $tiliointi['tunnus'];
        unset($tiliointi['tunnus']);
        $tiliointi['ltunnus'] = $uusi_lasku_tunnus;

        $copy_query = "INSERT INTO
                       tiliointi (".implode(", ", array_keys($tiliointi)).")
                       VALUES('".implode("', '", array_values($tiliointi))."')";

        pupe_query($copy_query);

        if (mysql_affected_rows() > 0) {
          $onnistuneet_insertoinnit['tilioinnit'][] = $tiliointi_tunnus_temp;
        }
      }
      $onnistuneet_insertoinnit['laskut'][] = $lasku_tunnus_temp;
    }
  }

  merkkaa_laskuaineisto_hyvaksytyksi($onnistuneet_insertoinnit);
}


function merkkaa_laskuaineisto_hyvaksytyksi($onnistuneet_insertoinnit) {
  global $kukarow, $yhtiorow;

  if (!empty($onnistuneet_insertoinnit['laskut'])) {
    $query = "UPDATE futur_lasku
              SET hyvaksytty = 1,
              kuka_hyvaksyi   = '{$kukarow['kuka']}',
              hyvaksytty_aika = NOW()
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND tunnus      IN ('".implode("', '", $onnistuneet_insertoinnit['laskut'])."')";
    pupe_query($query);
  }

  if (!empty($onnistuneet_insertoinnit['tilioinnit'])) {
    $query = "UPDATE futur_tiliointi
              SET hyvaksytty = 1,
              kuka_hyvaksyi   = '{$kukarow['kuka']}',
              hyvaksytty_aika = NOW()
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND tunnus      IN ('".implode("', '", $onnistuneet_insertoinnit['tilioinnit'])."')";
    pupe_query($query);
  }
}


function hae_hyvaksyttava_laskuaineisto($request) {
  global $kukarow, $yhtiorow;

  if ($request['toim'] == 'KATEINEN') {
    $kassalippaat = hae_kassalippaat();
  }

  if (!empty($request['toim']) and $request['toim'] == 'KATEINEN') {
    $lasku_where = " AND futur_lasku.kassalipas != ''";
  }
  else {
    $lasku_where = " AND futur_lasku.kassalipas = ''";
  }

  if (!empty($request['lasku_tyyppi_rajoitin'])) {
    if ($request['lasku_tyyppi_rajoitin'] == 'vain_hyvaksytyt') {
      $lasku_where .= " AND futur_lasku.hyvaksytty = 1";
    }
    else {
      $lasku_where .= " AND futur_lasku.hyvaksytty = 0";
    }
  }
  else {
    //defaulttina requestista ei tule lasku_tyyppi_rajoitinta. valitaan vain hyvaksymattomat
    $lasku_where .= " AND futur_lasku.hyvaksytty = 0";
  }

  if (!empty($request['lasku_alku_pvm_rajoitin']) and !empty($request['lasku_loppu_pvm_rajoitin'])) {
    $lasku_where .= " AND futur_lasku.tapvm BETWEEN '".date('Y-m-d', strtotime($request['lasku_alku_pvm_rajoitin']))."' AND '".date('Y-m-d', strtotime($request['lasku_loppu_pvm_rajoitin'] . ' + 1 day'))."'";
  }

  //joinataan vain alviton summa tiliöinti laskuhin, koska alvitonta summaa käytetään neton ja alv-summan laskemiseen.
  $query = "SELECT futur_lasku.*,
            futur_tiliointi.summa as alviton_summa
            FROM futur_lasku
            LEFT JOIN futur_tiliointi
            ON ( futur_tiliointi.yhtio = futur_lasku.yhtio
              AND futur_tiliointi.ltunnus  = futur_lasku.tunnus
              AND futur_tiliointi.vero    != 0)
            WHERE futur_lasku.yhtio        = '{$kukarow['yhtio']}'
            {$lasku_where}
            ORDER BY hyvaksytty ASC, comments ASC";
  $result = pupe_query($query);

  $laskut = array();
  while ($lasku = mysql_fetch_assoc($result)) {

    //jos asiakasta ei ole ollut olemassa import hetkellä liitostunnus = 0
    if ($lasku['liitostunnus'] == 0) {
      $asiakas = hae_asiakas($lasku['ytunnus']);

      //asiakas on luotu importin jälkeen, päivitetään tiedot laskulle kantaan ja lasku-"olioon"
      if (!empty($asiakas)) {
        paivita_asiakkaan_tiedot_laskulle($lasku, $asiakas);
      }
    }

    //haetaan kassalippaan kaikki tiedot laskuun mukaan
    if ($request['toim'] == 'KATEINEN') {
      $lasku['kassalipas'] = $kassalippaat[$lasku['kassalipas']];
    }

    //kerätään laskut päiväkohtaisesti
    $laskut[$lasku['tapvm']]['laskut'][] = $lasku;
    $laskut[$lasku['tapvm']]['summa'] += $lasku['summa'];
    //jos alviton_summa = 0 tai ei asetettu tarkoittaa se, että lasku on myyty alvittomana. tällöin netto ja brutto ovat samat
    $laskut[$lasku['tapvm']]['netto_summa'] += (empty($lasku['alviton_summa']) ? $lasku['summa'] : $lasku['alviton_summa']) * -1;

    $laskut[$lasku['tapvm']]['alv_summa'] += (empty($lasku['alviton_summa']) ? 0 : $lasku['summa'] + $lasku['alviton_summa']);

    //VIRHEELLISET LASKUT
    if (($lasku['liitostunnus'] == 0 and $request['toim'] != 'KATEINEN') or !empty($lasku['comments'])) {
      if (!isset($laskut[$lasku['tapvm']]['virheellisten_laskujen_lkm'])) {
        $laskut[$lasku['tapvm']]['virheellisten_laskujen_lkm'] = 0;
      }
      $laskut[$lasku['tapvm']]['virheellisten_laskujen_lkm']++;

      if (!empty($lasku['viesti'])) {
        //jos viesti kentässä on jotai, käytännössä tämä tarkoittaa, että aineistossa on lasku, jolla on ollut virheellinen tilinumero
        //tällaisessa tilanteessa emme voi hyväksyä mitään aineiston laskua vaan tilinumero pitää käydä korjaamassa futurin puolella
        //aineistoa ei voi hyväksyä koska käyttöliittymä menee lockdowniin, jos aineistosta hyväksyy laskuja
        $laskut[$lasku['tapvm']]['koko_aineisto_pitaa_hylata'] = true;
      }
    }

    //LASKUJEN LKM
    if (!isset($laskut[$lasku['tapvm']]['laskujen_lkm'])) {
      $laskut[$lasku['tapvm']]['laskujen_lkm'] = 0;
    }
    $laskut[$lasku['tapvm']]['laskujen_lkm']++;

    //SUURIN LASKUNRO
    if (isset($laskut[$lasku['tapvm']]['max'])) {
      if ($laskut[$lasku['tapvm']]['max'] > $lasku['laskunro']) {
        $laskut[$lasku['tapvm']]['max'] = $lasku['laskunro'];
      }
    }
    else {
      $laskut[$lasku['tapvm']]['max'] = $lasku['laskunro'];
    }

    //PIENIN LASKUNRO
    if (isset($laskut[$lasku['tapvm']]['min'])) {
      if ($laskut[$lasku['tapvm']]['min'] < $lasku['laskunro']) {
        $laskut[$lasku['tapvm']]['min'] = $lasku['laskunro'];
      }
    }
    else {
      $laskut[$lasku['tapvm']]['min'] = $lasku['laskunro'];
    }

    if ($lasku['hyvaksytty']) {
      if (!isset($laskut[$lasku['tapvm']]['hyvaksyttyja'])) {
        $laskut[$lasku['tapvm']]['hyvaksyttyja'] = 0;
      }
      $laskut[$lasku['tapvm']]['hyvaksyttyja']++;
    }
  }

  return $laskut;
}


function paivita_asiakkaan_tiedot_laskulle(&$lasku, $asiakas) {
  global $kukarow, $yhtiorow;

  $query = "UPDATE futur_lasku
            SET nimi = '{$asiakas['nimi']}',
            osoite       = '{$asiakas['osoite']}',
            postino      = '{$asiakas['postino']}',
            postitp      = '{$asiakas['postitp']}',
            maa          = '{$asiakas['maa']}',
            toim_nimi    = '{$asiakas['toim_nimi']}',
            toim_osoite  = '{$asiakas['toim_osoite']}',
            toim_postino = '{$asiakas['toim_postino']}',
            toim_postitp = '{$asiakas['toim_postitp']}',
            toim_maa     = '{$asiakas['toim_maa']}',
            ytunnus      = '{$asiakas['ytunnus']}',
            liitostunnus = '{$asiakas['tunnus']}',
            comments     = ''
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND tunnus   = '{$lasku['tunnus']}'";
  pupe_query($query);

  $lasku['nimi'] = $asiakas['nimi'];
  $lasku['osoite'] = $asiakas['osoite'];
  $lasku['postino'] = $asiakas['postino'];
  $lasku['postitp'] = $asiakas['postitp'];
  $lasku['maa'] = $asiakas['maa'];
  $lasku['toim_nimi'] = $asiakas['toim_nimi'];
  $lasku['toim_osoite'] = $asiakas['toim_osoite'];
  $lasku['toim_postino'] = $asiakas['toim_postino'];
  $lasku['toim_postitp'] = $asiakas['toim_postitp'];
  $lasku['toim_maa'] = $asiakas['toim_maa'];
  $lasku['ytunnus'] = $asiakas['ytunnus'];
  $lasku['liitostunnus'] = $asiakas['tunnus'];
  $lasku['comments'] = '';
}


function echo_rajoitin_table($request) {
  $lasku_tyyppi_array = array(
    'vain_hyvaksymattomat'   => t("Näytä vain hyväksymättömät laskut"),
    'vain_hyvaksytyt'     => t("Näytä vain hyväksytyt laskut"),
  );
  echo "<form action='' method='POST'>";
  echo "<input type='hidden' name='tee' value='vaihda_rajoittimia' />";
  echo "<table id='rajoitin_table'>";
  echo "<tr>";
  echo "<th>".t("Lasku rajoitin")."</th>";
  echo "<td>";
  echo "<select name='lasku_tyyppi_rajoitin'>";
  foreach ($lasku_tyyppi_array as $lasku_tyyppi_index => $lasku_tyyppi) {
    $sel = "";
    if ($request['lasku_tyyppi_rajoitin'] == $lasku_tyyppi_index) {
      $sel = "SELECTED";
    }
    echo "<option value='{$lasku_tyyppi_index}' {$sel}>{$lasku_tyyppi}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>".t("Aika rajoitin")."</th>";
  echo "<td>";
  echo "<input type='text' name='lasku_alku_pvm_rajoitin' value='{$request['lasku_alku_pvm_rajoitin']}' />";
  echo " - ";
  echo "<input type='text' name='lasku_loppu_pvm_rajoitin' value='{$request['lasku_loppu_pvm_rajoitin']}' />";
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "<input type='submit' value='".t("Hae")."' />";
  echo "</form>";
}


function echo_laskuaineisto_table($laskut_paivakohtaisesti, $request) {
  global $palvelin2;

  echo "<input type='hidden' id='down_arrow' value='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
  echo "<input type='hidden' id='right_arrow' value='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />";

  echo "<table id='raportti_table'>";

  echo "<thead>";
  echo "<tr>";
  echo "<th>".t("Ajanjakso")."</th>";
  echo "<th>".t("Laskun numero")."</th>";
  if ($request['toim'] != 'KATEINEN') {
    echo "<th>".t("Asiakkaan nimi")."</th>";
  }
  if ($request['toim'] == 'KATEINEN') {
    echo "<th>".t("Kassalipas")."</th>";
  }
  echo "<th>".t("Laskun")." / ".t("laskujen summa brutto")."</th>";
  echo "<th>".t("Laskun")." / ".t("laskujen summa netto")."</th>";
  echo "<th>".t("Alv")."-".t("summa")."</th>";
  echo "<th>".t("Laskujen lukumäärä")."</th>";
  echo "<th>".t("Virheellisiä laskuja")."</th>";
  echo "<th>".t("Poista päivän laskuaineisto")."</th>";
  echo "</tr>";
  echo "</thead>";

  foreach ($laskut_paivakohtaisesti as $lasku_index => $lasku) {
    echo_table_first_layer($lasku_index, $lasku, $request);
  }

  echo "</table>";
}


function echo_table_first_layer($paiva_index, $paivan_laskut, $request) {
  global $palvelin2;

  echo "<tr class='paiva_tr_hidden aktiivi'>";

  echo "<td>";
  echo "<input type='hidden' class='paivittain_key' value='{$paiva_index}'/>";
  echo $paiva_index;
  echo "&nbsp";
  echo "<img class='porautumis_img' src='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
  echo "</td>";

  echo "<td>";
  echo abs($paivan_laskut['max']).' - '.abs($paivan_laskut['min']);
  echo "</td>";

  if ($request['toim'] != 'KATEINEN') {
    echo "<td>";
    echo "</td>";
  }

  if ($request['toim'] == 'KATEINEN') {
    echo "<td>";
    echo "</td>";
  }

  echo "<td>";
  echo $paivan_laskut['summa'];
  echo "</td>";

  echo "<td>";
  echo $paivan_laskut['netto_summa'];
  echo "</td>";

  echo "<td>";
  echo $paivan_laskut['alv_summa'];
  echo "</td>";

  echo "<td>";
  echo $paivan_laskut['laskujen_lkm'];
  echo "</td>";

  echo "<td>";
  echo $paivan_laskut['virheellisten_laskujen_lkm'];
  echo "</td>";

  echo "<td>";
  if (empty($paivan_laskut['hyvaksyttyja'])) {
    echo "<button class='poista_paivan_laskuaineisto'>".t("Poista")."</button>";
  }
  echo "</td>";

  echo "</tr>";

  //jos aineistossa on ollut virheellinen tilinumero, koko aineisto pitää hylätä
  if (!empty($paivan_laskut['koko_aineisto_pitaa_hylata'])) {
    $request['koko_aineisto_pitaa_hylata'] = $paivan_laskut['koko_aineisto_pitaa_hylata'];
  }

  echo_table_second_layer($paiva_index, $paivan_laskut['laskut'], $request);
}


function echo_table_second_layer($paiva_index, $laskut, $request) {
  global $palvelin2;

  foreach ($laskut as $lasku) {
    if ($lasku['liitostunnus'] == 0 and $request['toim'] != 'KATEINEN') {
      $onko_lasku_virheellinen_tai_hyvaksytty = "<font class='error'>".t("Asiakas puuttuu")."</font>";
    }
    elseif (!empty($lasku['comments'])) {
      $onko_lasku_virheellinen_tai_hyvaksytty = "<font class='error'>{$lasku['comments']}</font>";
    }
    else {
      if (empty($request['koko_aineisto_pitaa_hylata'])) {
        if ($lasku['hyvaksytty']) {
          $onko_lasku_virheellinen_tai_hyvaksytty = "<font color='green'>".t("Hyväksytty")."</font>";
        }
        else {
          $onko_lasku_virheellinen_tai_hyvaksytty = "";
        }
      }
      else {
        $onko_lasku_virheellinen_tai_hyvaksytty = "<font class='error'>".t("Aineistossa on virheellinen tilinumero").". ".t("Koko aineisto hylätään")."</font>";
      }
    }

    echo "<tr class='lasku_tr_hidden aktiivi {$paiva_index}'>";

    echo "<td>";
    if (empty($onko_lasku_virheellinen_tai_hyvaksytty) and empty($request['koko_aineisto_pitaa_hylata'])) {
      echo "<input type='hidden' class='lasku_tunnus' name='lasku_tunnukset[]' value='{$lasku['tunnus']}' />";
    }
    else {
      //tämä inputti on tässä sen takia, että kun poistetaan koko päivän aineisto niin osataan poistaa myös virheelliset laskut. tässä inputissa oleva tunnus ei saa ikinä lähteä requestin mukana
      echo "<input type='hidden' class='lasku_tunnus_virheellinen' value='{$lasku['tunnus']}' />";
    }
    echo "</td>";

    echo "<td>";
    echo $lasku['laskunro'];
    echo "</td>";

    if ($request['toim'] != 'KATEINEN') {
      echo "<td>";
      echo $lasku['nimi'];
      echo "</td>";
    }

    if ($request['toim'] == 'KATEINEN') {
      echo "<td>";
      echo $lasku['kassalipas']['nimi'];
      echo "</td>";
    }

    echo "<td>";
    echo $lasku['summa'];
    echo "</td>";

    echo "<td>";
    echo $lasku['alviton_summa'] * -1;
    echo "</td>";

    echo "<td>";
    echo $lasku['summa'] + $lasku['alviton_summa'];
    echo "</td>";

    echo "<td>";
    echo "</td>";

    echo "<td>";
    echo $onko_lasku_virheellinen_tai_hyvaksytty;
    echo "</td>";

    echo "<td>";
    echo "</td>";

    echo "</tr>";
  }
}


function hae_asiakas($asiakasnumero) {
  global $kukarow, $yhtiorow;

  $query = "SELECT *
            FROM asiakas
            WHERE yhtio     = '{$kukarow['yhtio']}'
            AND asiakasnro  = '{$asiakasnumero}'
            AND laji       != 'P'
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    return array();
  }

  return mysql_fetch_assoc($result);
}


function hae_kassalippaat() {
  global $kukarow, $yhtiorow;

  $query = "SELECT *
            FROM kassalipas
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);
  $kassalippaat = array();
  while ($kassalipas = mysql_fetch_assoc($result)) {
    $kassalippaat[$kassalipas['tunnus']] = $kassalipas;
  }

  return $kassalippaat;
}


function poista_laskut($tunnukset) {
  global $kukarow, $yhtiorow;

  $query = "DELETE
            FROM futur_lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  IN ('".implode("', '", $tunnukset)."')";
  pupe_query($query);
}


function poista_tilioinnit($ltunnukset) {
  global $kukarow, $yhtiorow;

  $query = "DELETE
            FROM futur_tiliointi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ltunnus IN ('".implode("', '", $ltunnukset)."')";
  pupe_query($query);
}


require "inc/footer.inc";
