<?php

if (isset($_COOKIE['inventointiaste_vain_saldoa']) === false or isset($_POST['vain_saldoa'])) {
  $vain_saldoa = true;
  $_POST['vain_saldoa'] = 'On';
  setcookie("inventointiaste_vain_saldoa", '1', strtotime('now + 10 years'));
}
else {
  if (isset($_POST['tee'])) {
    $vain_saldoa = false;
  }
  else {
    $vain_saldoa = $_COOKIE['inventointiaste_vain_saldoa'] === '1' ? true : false;
  }

  setcookie("inventointiaste_vain_saldoa", $vain_saldoa ? '1' : '0', strtotime('now + 10 years'));
}

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

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

if ($ajax_request) {
  if ($hae_varastot) {
    if ($yhtio == '') {
      //jos dropdownista valitaan tyhjä pitää tässä kohtaan yhtioon laittaa joku epävalidi yhtio, koska hae_varastot funkkarissa on empty(), jolloin käytetään kukarow:ta
      $yhtio = "EPAVALIDI";
    }
    $varastot = inventointiaste_hae_varastot(array(), $yhtio);
    array_walk_recursive($varastot, 'array_utf8_encode');

    echo json_encode($varastot);
    exit;
  }
  elseif ($hae_inventointilajit) {
    if ($yhtio == '') {
      //jos dropdownista valitaan tyhjä pitää tässä kohtaan yhtioon laittaa joku epävalidi yhtio, koska hae_varastot funkkarissa on empty(), jolloin käytetään kukarow:ta
      $yhtio = "EPAVALIDI";
    }
    $inventointi_lajit = hae_inventointilajit(array(), $yhtio);
    array_walk_recursive($inventointi_lajit, 'array_utf8_encode');

    echo json_encode($inventointi_lajit);
    exit;
  }
  elseif ($hae_tilikaudet) {
    if ($yhtio == '') {
      //jos dropdownista valitaan tyhjä pitää tässä kohtaan yhtioon laittaa joku epävalidi yhtio, koska hae_varastot funkkarissa on empty(), jolloin käytetään kukarow:ta
      $yhtio = "EPAVALIDI";
    }
    $tilikaudet = hae_tilikaudet(array(), $yhtio);
    array_walk_recursive($tilikaudet, 'array_utf8_encode');

    echo json_encode($tilikaudet);
    exit;
  }
  elseif ($hae_inventointien_lukumaara_paivan_perusteella) {

    $onko_validi = false;
    if (substr_count($paiva, '.') == 2) {
      list($d, $m, $y) = explode('.', $paiva);
      $onko_validi = checkdate($m, $d, sprintf('%04u', $y));
    }

    $request = array(
      'valittu_yhtio'     => $yhtio,
      'valitut_varastot'   => $valitut_varastot,
      'valittu_status'   => $valittu_status,
    );

    $paiva_1 = date('Y-m-d', strtotime("{$paiva}"));
    $paiva_1 = explode('-', $paiva_1);
    $request['vva'] = $paiva_1[0];
    $request['kka'] = $paiva_1[1];
    $request['ppa'] = $paiva_1[2];

    $paiva_1 = date('Y-m-d', strtotime("{$paiva} + 1 day"));
    $paiva_1 = explode('-', $paiva_1);
    $request['vvl'] = $paiva_1[0];
    $request['kkl'] = $paiva_1[1];
    $request['ppl'] = $paiva_1[2];

    if ($onko_validi) {
      //requestiin pitää setata pvm_inventointeja_yhteensa jossa queryssä osataan laskea mukaan myös inventoinnit joiden saldo ei muuttunut
      $request['pvm_inventointeja_yhteensa'] = true;
      $inventointien_lkm = count(hae_inventoinnit($request));
    }
    else {
      $inventointien_lkm = 0;
    }

    echo $inventointien_lkm;
    exit;
  }
}

echo "<font class='head'>".t("Inventointiaste")."</font><hr>";

gauge();
?>
<style>
  #wrapper {
    float: left;
  }

  #chart_div {
    display: block;
  }

  #table_div {
    display: block;
    clear: both;
  }

  #raportti_table {
    display: block;
    clear: both;
  }

  .kuukausittain_img {
    float: right;
  }

  .inventointilajeittain_tr {
    display: none;
  }

  .inventointilajeittain_tr_not_hidden {
    background: none repeat scroll 0 0 #DADDDE;
  }

  .inventointilajit_wrapper {
    display: none;
  }

  .inventointilajeittain_img {
    float: right;
  }

  .tapahtumat_table {
    display: none;
  }

  .tapahtumat_table_not_hidden {
  }

  .tapahtumat_wrapper {
    display: none;
  }

  .tapahtumat_wrapper_not_hidden {
    background: none repeat scroll 0 0 #DADDDE;
  }

  #footer {
    display: block;
    clear: both;
  }
</style>
<script>
  (function($) {
    var InventointiastePlugin = function(element) {
      //TODO varasto, inventointilaji ja tilikausi toiminnallisuudet pitää koodata omiin plugareihin.
      //TODO HTML generointi pitää kirjoittaa omaan plugariin, jolle passataan data.
      var element = $(element);
      var obj = this;
      var yhtio;

      this.populoi_varastot = function() {
        yhtio = parsi_yhtio_domista();
        var varastot_request_obj = hae_varastot(yhtio);

        $(element).html('');

        varastot_request_obj.done(function(varastot) {
          if (varastot.length > 0) {
            $.each(varastot, function(varasto_index, varasto) {
              appendaa_elementit(varasto, 'varastot');
            });
          }
          else {
            $(element).append($('#yhtio_ei_varasto_message').val());
          }
        });
      };

      this.populoi_inventointilajit = function() {
        yhtio = parsi_yhtio_domista();
        var inventointilajit_request_obj = hae_inventointi_lajit(yhtio);

        $(element).html('');

        inventointilajit_request_obj.done(function(inventointilajit) {
          if (inventointilajit.length > 0) {
            $.each(inventointilajit, function(inventointilaji_index, inventointilaji) {
              appendaa_elementit(inventointilaji, 'inventointilajit');
            });
          }
          else {
            $(element).append($('#yhtio_ei_inventointilajeja_message').val());
          }
        });
      };

      this.populoi_tilikaudet = function() {
        yhtio = parsi_yhtio_domista();
        var tilikaudet_request_obj = hae_tilikaudet(yhtio);

        $(element).html('');

        tilikaudet_request_obj.done(function(tilikaudet) {
          if (tilikaudet.length > 0) {
            var option = new Option($('#valitse_tilikausi_message').val(), '');
            $(element).append(option);
            $.each(tilikaudet, function(tilikausi_index, tilikausi) {
              var option = new Option(tilikausi.tilikausi, tilikausi.tunnus);
              $(element).append(option);
            });
          }
          else {
            var option = new Option($('#valitse_tilikausi_message').val(), '');
            $(element).append(option);
          }
        });
      };

      var parsi_yhtio_domista = function() {
        //TODO kaikki yhtiot listattu domiin checkbox vieressä

        //TODO kirjoita parsi yhtio, joka osaa hakea yhtion jotenkin järkevästi dropdownista
        //yhtio dropdown
        //custom case
        var yhtio = $(document).find('#yhtio').find(':selected').attr('data-yhtio');

        if (yhtio === undefined) {
          //yhtio kirjotettu esim divin sisään
          yhtio = $(document).find('#yhtio').html();
          if (yhtio === undefined) {
            if (console && console.log) {
              console.log('Varastoa ei löytynyt domista!!!!');

              return undefined;
            }
          }
        }

        return yhtio;
      };

      var appendaa_elementit = function(data, tyyppi) {
        var nimi, value;
        if (tyyppi === 'varastot') {
          nimi = data.nimitys;
          value = data.tunnus;
        }
        else {
          nimi = data.selite;
          value = data.selite;
        }
        var checkbox = document.createElement('input');
        $(checkbox).attr('type', 'checkbox').attr('class', tyyppi).attr('checked', 'checked').attr('name', tyyppi + '[]').val(value);

        var br = document.createElement('br');

        $(element).append(checkbox);
        $(element).append(nimi);
        $(element).append(br);
      };

      var hae_varastot = function(yhtio) {
        return $.ajax({
          async: true,
          dataType: 'json',
          type: 'GET',
          url: 'inventointiaste.php?ajax_request=1&hae_varastot=1&no_head=yes&yhtio=' + yhtio
        }).done(function(data) {
          if (console && console.log) {
            console.log('Varastojen haku onnistui');
            console.log(data);
          }
        });
      };

      var hae_inventointi_lajit = function(yhtio) {
        return $.ajax({
          async: true,
          dataType: 'json',
          type: 'GET',
          url: 'inventointiaste.php?ajax_request=1&hae_inventointilajit=1&no_head=yes&yhtio=' + yhtio
        }).done(function(data) {
          if (console && console.log) {
            console.log('Inventointilajien haku onnistui');
            console.log(data);
          }
        });
      };

      var hae_tilikaudet = function(yhtio) {
        return $.ajax({
          async: true,
          dataType: 'json',
          type: 'GET',
          url: 'inventointiaste.php?ajax_request=1&hae_tilikaudet=1&no_head=yes&yhtio=' + yhtio
        }).done(function(data) {
          if (console && console.log) {
            console.log('Tilikausien haku onnistui');
            console.log(data);
          }
        });
      };
    };

    $.fn.inventointiastePlugin = function() {
      return this.each(function() {
        var element = $(this);

        if (element.data('inventointiastePlugin')) {
          return;
        }

        var inventointiastePlugin = new InventointiastePlugin(this);

        element.data('inventointiastePlugin', inventointiastePlugin);
      });
    };

  })(jQuery);

  $(document).ready(function() {
    var gauge_types = [
      '12kk',
      'tilikausi'
    ];

    var gauges = {};
    for (var gauge_type in gauge_types) {
      gauges[gauge_types[gauge_type]] = init_and_draw_gauge(gauge_types[gauge_type]);
    }

    append_gauge_headers();

    bind_kuukausittain_tr();
    bind_inventointilajeittain_tr();

    bind_varasto_change();

    bind_valitse_kaikki_varastot_checkbox();

    bind_valitse_kaikki_inventointilajit_checkbox();

    bind_inventoitu_yhteensa_pvm_change();
  });

  function bind_kuukausittain_tr() {
    $('.kuukausittain_tr').click(function() {
      var kuukausittain_key = $(this).find('.kuukausittain_key').val();
      var children = $(this).parent().find('.' + kuukausittain_key);
      if ($(this).hasClass('not_hidden')) {
        $(children).addClass('inventointilajeittain_tr');
        $(children).removeClass('inventointilajeittain_tr_not_hidden');
        $(this).removeClass('not_hidden');
        $(this).find('.kuukausittain_img').attr('src', $('#down_arrow').val());
      }
      else {
        $(children).removeClass('inventointilajeittain_tr');
        $(children).addClass('inventointilajeittain_tr_not_hidden');
        $(this).addClass('not_hidden');
        $(this).find('.kuukausittain_img').attr('src', $('#right_arrow').val());
      }
    });
  }

  function bind_inventointilajeittain_tr() {
    $('.inventointilajeittain_tr').click(function() {
      var children = $(this).next();
      if ($(this).hasClass('not_hidden')) {
        $(children).addClass('tapahtumat_wrapper');
        $(children).removeClass('tapahtumat_wrapper_not_hidden');
        $(children).find('.tapahtumat_table').addClass('tapahtumat_table');
        $(children).find('.tapahtumat_table').removeClass('tapahtumat_table_not_hidden');
        $(this).removeClass('not_hidden');
        $(this).find('.inventointilajeittain_img').attr('src', $('#down_arrow').val());
      }
      else {
        $(children).removeClass('tapahtumat_wrapper');
        $(children).addClass('tapahtumat_wrapper_not_hidden');
        $(children).find('.tapahtumat_table').removeClass('tapahtumat_table');
        $(children).find('.tapahtumat_table').addClass('tapahtumat_table_not_hidden');
        $(this).addClass('not_hidden');
        $(this).find('.inventointilajeittain_img').attr('src', $('#right_arrow').val());
      }
    });
  }

  function init_and_draw_gauge(type) {
    var gauge = new Gauge();

    if (type === '12kk') {
      var args = {
        prosentti12kk: ['%', 0]
      };
    }
    else {
      var args = {
        prosenttitilikausi: ['%', 0]
      };
    }


    var options = {forceIFrame: false,
      width: 800,
      height: 220,
      min: 0,
      max: 100,
      redFrom: 70,
      redTo: 80,
      greenFrom: 90,
      greenTo: 100,
      yellowFrom: 80,
      yellowTo: 90,
      minorTicks: 5,
      majorTicks: ['0', '10', '20', '30', '40', '50', '60', '70', '80', '90', '100'],
      animation: {
        easing: 'out',
        duration: 4000
      }};

    gauge.init(args, options);

    var lukumaara_string = 'inventointien_lukumaara_' + type;
    if (!isNaN($('#' + lukumaara_string).val()) && $('#tuotepaikkojen_lukumaara').val() !== 0) {
      var draw_options = {
        max: options.max,
        type: 'custom_parseint'
      };
      var inventointiaste = ($('#' + lukumaara_string).val() / $('#tuotepaikkojen_lukumaara').val()) * 100;
      gauge.draw(inventointiaste, draw_options);
    }

    return gauge;
  }

  function append_gauge_headers() {
    $('#prosentti12kk').prepend('<div align="center"><font class="message">' + $('#12kk_gauge_message').val() + '</font></div>');
    $('#prosenttitilikausi').prepend('<div align="center"><font class="message">' + $('#tilikausi_gauge_message').val() + '</font></div>');
  }

  function bind_varasto_change() {
    $('#yhtio').change(function() {
      $('#varastot_td').inventointiastePlugin();
      var varastot_plugin = $('#varastot_td').data('inventointiastePlugin');
      varastot_plugin.populoi_varastot();

      $('#inventointilajit_td').inventointiastePlugin();
      var inventointilajit_plugin = $('#inventointilajit_td').data('inventointiastePlugin');
      inventointilajit_plugin.populoi_inventointilajit();

      $('#valittu_tilikausi').inventointiastePlugin();
      var tilikaudet_plugin = $('#valittu_tilikausi').data('inventointiastePlugin');
      tilikaudet_plugin.populoi_tilikaudet();
    });
  }

  function bind_valitse_kaikki_varastot_checkbox() {
    $('#valitse_kaikki_varastot').click(function() {
      var varasto_checkboxit = $(this).parent().next().find('.varastot');
      if ($(this).is(':checked')) {
        $(varasto_checkboxit).each(function(index, checkbox) {
          $(checkbox).attr('checked', 'checked');
        });
      }
      else {
        $(varasto_checkboxit).each(function(index, checkbox) {
          $(checkbox).removeAttr('checked');
        });
      }
    });
  }

  function bind_valitse_kaikki_inventointilajit_checkbox() {
    $('#valitse_kaikki_inventointilajit').click(function() {
      var inventointilaji_checkboxit = $(this).parent().next().find('.inventointilajit');
      if ($(this).is(':checked')) {
        $(inventointilaji_checkboxit).each(function(index, checkbox) {
          $(checkbox).attr('checked', 'checked');
        });
      }
      else {
        $(inventointilaji_checkboxit).each(function(index, checkbox) {
          $(checkbox).removeAttr('checked');
        });
      }
    });
  }

  function bind_inventoitu_yhteensa_pvm_change() {
    $('#inventoitu_yhteensa_pvm').keyup(function() {
      var valitut_varastot = $('.varastot:checked');
      var valitut_varasto_tunnukset = [];
      $(valitut_varastot).each(function(index, varasto) {
        valitut_varasto_tunnukset.push($(varasto).val());
      });

      $.ajax({
        async: true,
        type: 'GET',
        data: {
          yhtio: $('#yhtio').find(':selected').attr('data-yhtio'),
          paiva: $(this).val(),
          valittu_status: $('#status').val(),
          valitut_varastot: valitut_varasto_tunnukset
        },
        url: 'inventointiaste.php?ajax_request=1&hae_inventointien_lukumaara_paivan_perusteella=1&no_head=yes'
      }).done(function(data) {
        if (console && console.log) {
          console.log('Inventointien määrä onnistui');
          console.log(data);
        }
        $('#paiva_inventoitu_yhteensa_td').html(data);
      });
    });
  }

  function isValidDate(date) {
    var matches = /^(\d{2})[-\/](\d{2})[-\/](\d{4})$/.exec(date);
    if (matches === null) {
      return false;
    }

    var d = matches[2];
    var m = matches[1] - 1;
    var y = matches[3];
    var composedDate = new Date(y, m, d);
    return composedDate.getDate() === d &&
        composedDate.getMonth() === m &&
        composedDate.getFullYear() === y;
  }


  function tarkista() {
    var ok = true;

    if ($('.varastot:checked').length === 0) {
      ok = false;
      alert($('#valitse_varasto_error_message').val());
    }

    var aika_arvot = $(".alku_aika").map(function() {
      return $(this).val();
    }).get();
    var alku_aika_not_empty_values = aika_arvot.filter(function(v) {
      if (v === '') {
        return 0;
      }
      else {
        return 1;
      }
    });

    aika_arvot = $(".loppu_aika").map(function() {
      return $(this).val();
    }).get();
    var loppu_aika_not_empty_values = aika_arvot.filter(function(v) {
      if (v === '') {
        return 0;
      }
      else {
        return 1;
      }
    });

    if ($('#valittu_tilikausi').val() === '' && (alku_aika_not_empty_values.length !== 3 && loppu_aika_not_empty_values.length !== 3)) {
      ok = false;
      alert($('#valitse_aika_error_message').val());
    }

    return ok;
  }
</script>

<?php

$request = array(
  'tee'             => $tee,
  'tallenna_exceliin'       => $tallenna_exceliin,
  'vain_saldoa'         => $vain_saldoa,
  'ppa'             => $ppa,
  'kka'             => $kka,
  'vva'             => $vva,
  'ppl'             => $ppl,
  'kkl'             => $kkl,
  'vvl'             => $vvl,
  'valittu_tilikausi'       => $valittu_tilikausi,
  'yhtio'             => $yhtio,
  'valitut_varastot'       => $varastot,
  'valitut_inventointilajit'   => $inventointilajit,
  'valittu_status'       => $valittu_status,
  'ei_huomioida_tuotepaikkoja_avainsanoista' => (!isset($ei_huomioida_tuotepaikkoja_avainsanoista) or (is_array($ei_huomioida_tuotepaikkoja_avainsanoista) and count($ei_huomioida_tuotepaikkoja_avainsanoista) == 2)) ? true : false,
);

echo "<div id='wrapper'>";

init($request);

echo "<div id='table_div'>";

echo_arvot($request);
echo_kayttoliittyma($request);

echo "</div>";
echo "<br/>";
echo "<br/>";

if ($request['tee'] == 'aja_raportti') {

  $rivit = hae_inventoinnit($request);

  if ($request['tallenna_exceliin']) {
    $header_values = array(
      'vuosi'             => array(
        'header' => t('Vuosi'),
        'order'   => 1
      ),
      'kuukausi'           => array(
        'header' => t('Kuukausi'),
        'order'   => 2
      ),
      'paiva'             => array(
        'header' => t('Päivä'),
        'order'   => 3
      ),
      'kellon_aika'         => array(
        'header' => t('Kellon aika'),
        'order'   => 4
      ),
      'inventointi_poikkeama_eur'   => array(
        'header' => t('Inventointipoikkeama').' '.$yhtiorow['valkoodi'],
        'order'   => 60
      ),
      'selite'           => array(
        'header' => t('Selite'),
        'order'   => 80
      ),
      'inventointilaji'       => array(
        'header' => t('Inventointilaji'),
        'order'   => 70
      ),
      'tuoteno'           => array(
        'header' => t('Tuoteno'),
        'order'   => 10
      ),
      'tuote_nimitys'         => array(
        'header' => t('Tuotteen nimitys'),
        'order'   => 20
      ),
      'kpl'             => array(
        'header' => t('Inventointi määrä'),
        'order'   => 50
      ),
      'tuoteryhma'         => array(
        'header' => t('Tuoteryhmä'),
        'order'   => 21
      ),
      'hyllypaikka'         => array(
        'header' => t('Hyllypaikka'),
        'order'   => 30
      ),
      'laatija'           => array(
        'header' => t('Inventoija'),
        'order'   => 89
      ),
      'keraysvyohyke_nimitys'     => array(
        'header' => t('Keräysvyohykkeen nimitys'),
        'order'   => 40
      ),
    );
    $force_to_string = array(
      'tuoteno'
    );
    $sulje_pois = array(
      'laadittu_pvm',
      'laadittu',
    );

    $excel_filepath = generoi_excel_tiedosto($rivit, $header_values, $force_to_string, $sulje_pois);
  }
  $rivit = kasittele_rivit($rivit);

  if (!empty($excel_filepath)) {
    echo_tallennus_formi($excel_filepath);
  }

  echo_raportin_tulokset($rivit);
}

echo "</div>";

echo "<div id='footer'>";
require "../inc/footer.inc";
echo "</div>";

function init(&$request) {
  global $palvelin2;

  echo "<input type='hidden' id='valitse_varasto_error_message' value='".t("Valitse varasto")."' />";
  echo "<input type='hidden' id='valitse_aika_error_message' value='".t("Syötä validi aika")."' />";
  echo "<input type='hidden' id='12kk_gauge_message' value='".t("Juokseva 12kk")."' />";
  echo "<input type='hidden' id='tilikausi_gauge_message' value='".t("Kuluva tilikausi")."' />";
  echo "<input type='hidden' id='yhtio_ei_varasto_message' value='".t("Yhtiöllä ei ole varastoja")."' />";
  echo "<input type='hidden' id='yhtio_ei_inventointilajeja_message' value='".t("Yhtiöllä ei ole inventointilajeja")."' />";
  echo "<input type='hidden' id='valitse_tilikausi_message' value='".t("Valitse tilikausi tai syötä päivämäärä rajat")."' />";
  echo "<input type='hidden' id='down_arrow' value='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
  echo "<input type='hidden' id='right_arrow' value='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />";

  echo "<div id='chart_div'></div>";
  echo "<br/>";
  echo "<br/>";

  $request['yhtiot'] = hae_yhtiot($request);
  if (!empty($request['yhtiot'])) {
    array_unshift($request['yhtiot'], array('tunnus' => '', 'nimi'   => t("Valitse yhtiö")));
  }
  else {
    $request['yhtiot'] = array('tunnus' => '', 'nimi'   => t("Valitse yhtiö"));
  }

  $request['tilikaudet'] = hae_tilikaudet($request);
  if (!empty($request['tilikaudet'])) {
    array_unshift($request['tilikaudet'], array('tunnus'   => '', 'tilikausi'   => t("Valitse tilikausi tai syötä päivämäärä rajat")));
  }
  else {
    $request['tilikaudet'] = array('tunnus'   => '', 'tilikausi'   => t("Valitse tilikausi tai syötä päivämäärä rajat"));
  }

  foreach ($request['tilikaudet'] as $tilikausi) {
    if ($tilikausi['tilikausi_alku'] <= date('Y-m-d') and $tilikausi['tilikausi_loppu'] > date('Y-m-d')) {
      $request['tamanhetkinen_tilikausi'] = array(
        'tilikausi_alku'   => $tilikausi['tilikausi_alku'],
        'tilikausi_loppu'   => $tilikausi['tilikausi_loppu'],
      );
      break;
    }
  }
  if (empty($request['tamanhetkinen_tilikausi'])) {
    //fail safe, jos tilikautta ei ole asetettu kantaan
    $request['tamanhetkinen_tilikausi'] = array(
      'tilikausi_alku'   => date('Y-01-01'),
      'tilikausi_loppu'   => date('Y-12-31'),
    );
  }

  $request['varastot'] = inventointiaste_hae_varastot($request);

  if (empty($request['valitut_varastot'])) {
    //ensimmäinen sivulataus, requestista ei ole tullut valittuja varastoja, rajataan käyttöliittymään esivalittujen varastojen perusteella
    foreach ($request['varastot'] as $varasto) {
      if (!empty($varasto['checked'])) {
        $request['valitut_varastot'][] = $varasto['tunnus'];
      }
    }
  }

  $request['inventointilajit'] = hae_inventointilajit($request);

  if (empty($request['valitut_inventointilajit'])) {
    //ensimmäinen sivulataus, requestista ei ole tullut valittuja inventointilajeja, rajataan käyttöliittymään esivalittujen inventointilajien perusteella
    foreach ($request['inventointilajit'] as $inventointilaji) {
      if (!empty($inventointilaji['checked'])) {
        $request['valitut_inventointilajit'][] = $inventointilaji['selite'];
      }
    }
  }

  $request['statukset'] = hae_tuote_statukset($request);

  if (empty($request['valittu_status'])) {
    //ensimmäinen sivulataus, requestista ei ole tullut valittuja statuksia, rajataan käyttöliittymään esivalitun statuksen eli ensimmäisen statuksen perusteella
    $request['valittu_status'] = $request['statukset'][0]['selite'];
  }
}

function echo_arvot(&$request) {

  //haetaan tämän tilikauden jäljellä olevien työpäivien lukumäärä
  $tyopaivien_lukumaara = hae_tyopaivien_lukumaara($request['tamanhetkinen_tilikausi']['tilikausi_alku'], $request['tamanhetkinen_tilikausi']['tilikausi_loppu']);

  $inventointien_lukumaara_12kk = hae_inventoitavien_lukumaara($request, '12kk');
  $inventointien_lukumaara_tilikausi = hae_inventoitavien_lukumaara($request, 'tilikausi');
  $tuotepaikkojen_lukumaara = hae_tuotepaikkojen_lukumaara($request);

  echo "<input type='hidden' id='inventointien_lukumaara_12kk' value='{$inventointien_lukumaara_12kk}' />";
  echo "<input type='hidden' id='inventointien_lukumaara_tilikausi' value='{$inventointien_lukumaara_tilikausi}' />";
  echo "<input type='hidden' id='tuotepaikkojen_lukumaara' value='{$tuotepaikkojen_lukumaara}' />";

  $inventointeja_per_paiva = ($tuotepaikkojen_lukumaara - $inventointien_lukumaara_tilikausi) / $tyopaivien_lukumaara;

  //Hae_inventoinnit hakee valitun tilikauden tai annetun päivämäärä välin perusteella inventoinnit.
  //Koska hae_inventoinnit funktiota kutsutaan tämän funktion jälkeen uudestaan pitää tilikausi ja päivämäärät laittaa talteen, jotta ohjelma toimii oikein.
  $valittu_tilikausi_temp = $request['valittu_tilikausi'];
  $vva_temp = $request['vva'];
  $kka_temp = $request['kka'];
  $ppa_temp = $request['ppa'];

  $vvl_temp = $request['vvl'];
  $kkl_temp = $request['kkl'];
  $ppl_temp = $request['ppl'];
  //unsetataan valittu_tilikausi jotta parsi_paivat-funktiossa käytetään vva kka jne.
  unset($request['valittu_tilikausi']);
  //Defaulttina halutaan eilisen päivän inventoinnit. Koska queryssä on BETWEEN alku AND loppu niin ajat pitää antaa allaolevan mukaisella tavalla
  $eilen = date('d.m.Y', strtotime('now - 1 day'));

  $eilen_array = explode('.', $eilen);
  $request['vva'] = $eilen_array[2];
  $request['kka'] = $eilen_array[1];
  $request['ppa'] = $eilen_array[0];

  $tanaan = date('Y-m-d', strtotime('now'));
  $tanaan = explode('-', $tanaan);
  $request['vvl'] = $tanaan[0];
  $request['kkl'] = $tanaan[1];
  $request['ppl'] = $tanaan[2];

  //requestiin pitää setata pvm_inventointeja_yhteensa jossa queryssä osataan laskea mukaan myös inventoinnit joiden saldo ei muuttunut
  $request['pvm_inventointeja_yhteensa'] = true;
  $eilen_inventoitu_yhteensa = count(hae_inventoinnit($request));

  //asetetaan tempistä value takaisin omille paikoilleen.
  $request['valittu_tilikausi'] = $valittu_tilikausi_temp;
  $request['vva'] = $vva_temp;
  $request['kka'] = $kka_temp;
  $request['ppa'] = $ppa_temp;

  $request['vvl'] = $vvl_temp;
  $request['kkl'] = $kkl_temp;
  $request['ppl'] = $ppl_temp;

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Tuotteiden Inventointeja Pitää Suorittaa Per Päivä")."</th>";
  echo "<td>".round($inventointeja_per_paiva, 0)."</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Tuotteita Valituissa Varastoissa")."</th>";
  echo "<td>{$tuotepaikkojen_lukumaara}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><input type='text' id='inventoitu_yhteensa_pvm' size=10 value='{$eilen}'/> ".t("inventoitu yhteensä")."</th>";
  echo "<td id='paiva_inventoitu_yhteensa_td'>{$eilen_inventoitu_yhteensa}</td>";
  echo "</tr>";

  echo "</table>";
}

function echo_raportin_tulokset($rivit) {
  global $kukarow, $yhtiorow;

  echo "<table id='raportti_table'>";

  echo "<tr>";
  echo "<th>".t("Ajanjakso")."</th>";
  echo "<th>".t("Inventointilajit")."</th>";
  echo "<th>".t("Inventoitu positiivista")." {$yhtiorow['valkoodi']}</th>";
  echo "<th>".t("Inventoitu negatiivista")." {$yhtiorow['valkoodi']}</th>";
  echo "<th>".t("Inventointi erotus")." {$yhtiorow['valkoodi']}</th>";
  echo "</tr>";

  foreach ($rivit as $rivi_index => $rivi) {
    echo_table_first_layer($rivi_index, $rivi);
  }

  echo "</table>";
}

function echo_table_first_layer($rivi_index, $rivi) {
  global $palvelin2, $kukarow;

  echo "<tr class='kuukausittain_tr aktiivi'>";

  echo "<td>";
  echo "<input type='hidden' class='kuukausittain_key' value='{$rivi_index}'/>";
  echo $rivi_index;
  echo "&nbsp";
  echo "<img class='kuukausittain_img' src='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
  echo "</td>";

  echo "<td></td>";

  echo "<td>";
  echo isset($rivi['kuukausittain_luvut']['pos']) ? round($rivi['kuukausittain_luvut']['pos'], 2) : 0;
  echo "</td>";

  echo "<td>";
  echo isset($rivi['kuukausittain_luvut']['neg']) ? round($rivi['kuukausittain_luvut']['neg'], 2) : 0;
  echo "</td>";

  echo "<td>";
  echo isset($rivi['kuukausittain_luvut']['ero']) ? round($rivi['kuukausittain_luvut']['ero'], 2) : 0;
  echo "</td>";

  echo "</tr>";

  echo_table_second_layer($rivi, $rivi_index);
}

function echo_table_second_layer($rivi, $rivi_index) {
  global $palvelin2, $kukarow;

  foreach ($rivi['inventointilajit'] as $inventointilaji) {
    echo "<tr class='inventointilajeittain_tr aktiivi {$rivi_index}'>";

    echo "<td></td>";
    echo "<td>";
    //$inventointilaji pitää sisällään ainoastaan tietyn inventointilajin inventointeja, tällöin voimme printata lajin nimityksen ensimmäisestä alkiosta
    echo $inventointilaji['tapahtumat'][0]['inventointilaji'];
    echo "&nbsp";
    echo "<img class='inventointilajeittain_img' src='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
    echo "</td>";

    echo "<td>";
    echo isset($inventointilaji['inventointilajeittain_luvut']['pos']) ? round($inventointilaji['inventointilajeittain_luvut']['pos'], 2) : 0;
    echo "</td>";

    echo "<td>";
    echo isset($inventointilaji['inventointilajeittain_luvut']['neg']) ? round($inventointilaji['inventointilajeittain_luvut']['neg'], 2) : 0;
    echo "</td>";

    echo "<td>";
    echo isset($inventointilaji['inventointilajeittain_luvut']['ero']) ? round($inventointilaji['inventointilajeittain_luvut']['ero'], 2) : 0;
    echo "</td>";

    echo "</tr>";

    echo "<tr class='tapahtumat_wrapper'>";
    echo "<td></td>";
    echo "<td></td>";
    echo "<td colspan='3'>";
    echo_table_third_layer($inventointilaji);
    echo "</td>";
    echo "</tr>";
  }
}

function echo_table_third_layer($inventointilaji) {
  global $kukarow;

  echo "<table class='tapahtumat_table'>";

  echo "<tr>";
  echo "<th>".t("Tuoteno")."</th>";
  echo "<th>".t("Nimitys")."</th>";
  echo "<th>".t("Tuoteryhmä")."</th>";
  echo "<th>".t("Hyllypaikka")."</th>";
  echo "<th>".t("Keräysvyöhyke")."</th>";
  echo "<th>".t("Kpl")."</th>";
  echo "<th>".t("Rahavaikutus")."</th>";
  echo "<th>".t("Selite")."</th>";
  echo "<th>".t("Kuka inventoi")."</th>";
  echo "<th>".t("Koska inventoitiin")."</th>";
  echo "</tr>";
  $tuoteryhma_array = array();
  foreach ($inventointilaji['tapahtumat'] as $tapahtuma) {
    if (empty($tuoteryhma_array[$tapahtuma['tuoteryhma']])) {
      $tuoteryhma_array[$tapahtuma['tuoteryhma']] = mysql_fetch_assoc(t_avainsana('TRY', $kukarow['kieli'], "AND avainsana.selite = '{$tapahtuma['tuoteryhma']}'"));
    }
    echo "<tr class='tapahtuma_tr'>";
    echo "<td>{$tapahtuma['tuoteno']}</td>";
    echo "<td>{$tapahtuma['tuote_nimitys']}</td>";
    echo "<td>{$tuoteryhma_array[$tapahtuma['tuoteryhma']]['selite']} - {$tuoteryhma_array[$tapahtuma['tuoteryhma']]['selitetark']}</td>";
    echo "<td>{$tapahtuma['hyllypaikka']}</td>";
    echo "<td>{$tapahtuma['keraysvyohyke_nimitys']}</td>";
    echo "<td>{$tapahtuma['kpl']}</td>";
    echo "<td>".round($tapahtuma['inventointi_poikkeama_eur'], 2)."</td>";
    echo "<td>{$tapahtuma['selite']}</td>";
    echo "<td>{$tapahtuma['laatija']}</td>";
    echo "<td>{$tapahtuma['laadittu']}</td>";
    echo "</tr>";
  }

  echo "</table>";
}

function echo_kayttoliittyma($request) {
  global $kukarow;

  echo "<form method='POST' action='' name='inventointiaste_form'>";
  echo "<input type='hidden' action = '' name='tee' value='aja_raportti' />";

  echo "<br><br><table>";

  echo "<tr>";
  echo "<th>".t("Tallenna exceliin")."</th>";
  echo "<td><input type='checkbox' name='tallenna_exceliin' ".(!empty($request['tallenna_exceliin']) ? 'checked="checked"' : '')."/></td>";
  echo "</tr>";

  echo '<tr>';
  echo '<th>'.t('Näytä vain tuotepaikat, joilla on saldoa').'</th>';
  echo '<td><input type="checkbox" name="vain_saldoa" '.(!empty($request['vain_saldoa'])
    ? 'checked="checked"' : '').' /></td>';
  echo '</tr>';

  echo "<tr>";
  echo "<th>", t("Syötä alkupäivämäärä"), " (", t("pp-kk-vvvv"), ")</th>";
  echo "<td><input type='text' name='ppa' id='ppa' class='alku_aika' value='{$request['ppa']}' size='3'>";
  echo "<input type='text' name='kka' id='kka' class='alku_aika' value='{$request['kka']}' size='3'>";
  echo "<input type='text' name='vva' id='vva' class='alku_aika' value='{$request['vva']}' size='5'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>", t("Syötä loppupäivämäärä"), " (", t("pp-kk-vvvv"), ")</th>";
  echo "<td><input type='text' name='ppl' id='ppl' class='loppu_aika' value='{$request['ppl']}' size='3'>";
  echo "<input type='text' name='kkl' id='kkl' class='loppu_aika' value='{$request['kkl']}' size='3'>";
  echo "<input type='text' name='vvl' id='vvl' class='loppu_aika' value='{$request['vvl']}' size='5'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Tilikausi")."</th>";
  echo "<td>";
  echo "<select id='valittu_tilikausi' name='valittu_tilikausi'>";
  foreach ($request['tilikaudet'] as $tilikausi) {
    echo "<option value='{$tilikausi['tunnus']}' {$tilikausi['selected']}>{$tilikausi['tilikausi']}</option>";
  }
  echo "</select>";
  echo t("Tilikauden valinta yliajaa ylläolevan päivämäärä valinnan");
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Statukset")."</th>";
  echo "<td>";
  echo "<select id='status' name='valittu_status'>";
  foreach ($request['statukset'] as $status) {
    echo "<option value='{$status['selite']}' {$status['selected']}>{$status['selitetark']}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Valitse yhtiö")."</th>";
  echo "<td>";
  echo "<select id='yhtio' name='yhtio'>";
  foreach ($request['yhtiot'] as $yhtio) {
    echo "<option data-yhtio='{$yhtio['yhtio']}' value='{$yhtio['tunnus']}' {$yhtio['selected']}>{$yhtio['nimi']}</option>";
  }
  echo "</select>";
  echo "</td>";

  $invaste_tuotepaikat_result = t_avainsana("INVASTEPAIKKA");

  if (mysql_num_rows($invaste_tuotepaikat_result) > 0) {

    $chk = $request['ei_huomioida_tuotepaikkoja_avainsanoista'] ? 'checked' : '';

    echo "<tr>";
    echo "<th>", t("Ei huomioida avainsanoihin määriteltyjä tuotepaikkoja"), "</th>";
    echo "<td>";
    echo "<input type='hidden' name='ei_huomioida_tuotepaikkoja_avainsanoista[]' value='default' /><br />";
    echo "<input type='checkbox' name='ei_huomioida_tuotepaikkoja_avainsanoista[]' {$chk} /><br />";

    while ($invaste_tuotepaikat_row = mysql_fetch_assoc($invaste_tuotepaikat_result)) {
      echo "{$invaste_tuotepaikat_row['selite']}<br />";
    }

    echo "</td>";
    echo "</tr>";
  }

  echo "<tr>";
  echo "<th>";
  echo t("Varastot");
  echo "<br/>";
  echo t("Valitse kaikki");
  echo "<input type='checkbox' id='valitse_kaikki_varastot' checked='checked' />";
  echo "</th>";
  echo "<td id='varastot_td'>";
  foreach ($request['varastot'] as $varasto) {
    echo "<input class='varastot' type='checkbox' name='varastot[]' value='{$varasto['tunnus']}' {$varasto['checked']} />";
    echo " {$varasto['nimitys']}";
    echo "<br/>";
  }
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>";
  echo t("Inventointilajit");
  echo "<br/>";
  echo t("Valitse kaikki");
  echo "<input type='checkbox' id='valitse_kaikki_inventointilajit' checked='checked' />";
  echo "</th>";
  echo "<td id='inventointilajit_td'>";
  foreach ($request['inventointilajit'] as $inventointilaji) {
    echo "<input class='inventointilajit' type='checkbox' name='inventointilajit[]' value='{$inventointilaji['selite']}' {$inventointilaji['checked']} />";
    echo " {$inventointilaji['selite']}";
    echo "<br/>";
  }
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<input type='submit' class='hae_btn' value='".t("Hae")."' onclick='return tarkista();' />";

  echo "</form>";
}

function kasittele_rivit($rivit) {

  $rivit_temp = array();
  foreach ($rivit as &$rivi) {
    if (!empty($rivi['inventointilaji'])) {
      $inventointilaji = preg_replace('/[^a-zA-Z0-9]/', '_', $rivi['inventointilaji']);
    }
    else {
      $inventointilaji = "tuntematon";
    }
    $aika = date('Y-m', strtotime($rivi['laadittu_pvm']));

    $eilinen = date('Y-m-d', strtotime('now - 1 day'));
    if (date('Y-m-d', strtotime($rivi['laadittu'])) == $eilinen) {
      $aika = $eilinen;
    }

    //kerätään kuukausittain luvut suoraan kuukauden alle
    keraa_kuukausittain_luvut($rivi, $rivit_temp, $inventointilaji, $aika);

    //kerätään inventointilajeittain luvut suoraan inventointilajin alle
    keraa_inventointilajeittain_luvut($rivi, $rivit_temp, $inventointilaji, $aika);

    $rivit_temp[$aika]['inventointilajit'][$inventointilaji]['tapahtumat'][] = $rivi;
  }

  krsort($rivit_temp);

  return $rivit_temp;
}

function keraa_kuukausittain_luvut(&$rivi, &$rivit_temp, $inventointilaji, $aika) {
  if ($rivi['inventointi_poikkeama_eur'] > 0) {
    if (!isset($rivit_temp[$aika]['kuukausittain_luvut']['pos'])) {
      $rivit_temp[$aika]['kuukausittain_luvut']['pos'] = $rivi['inventointi_poikkeama_eur'];
    }
    else {
      $rivit_temp[$aika]['kuukausittain_luvut']['pos'] += $rivi['inventointi_poikkeama_eur'];
    }
  }
  else {
    if (!isset($rivit_temp[$aika]['kuukausittain_luvut']['neg'])) {
      $rivit_temp[$aika]['kuukausittain_luvut']['neg'] = $rivi['inventointi_poikkeama_eur'];
    }
    else {
      $rivit_temp[$aika]['kuukausittain_luvut']['neg'] += $rivi['inventointi_poikkeama_eur'];
    }
  }

  if (!isset($rivit_temp[$aika]['kuukausittain_luvut']['ero'])) {
    $rivit_temp[$aika]['kuukausittain_luvut']['ero'] = $rivi['inventointi_poikkeama_eur'];
  }
  else {
    $rivit_temp[$aika]['kuukausittain_luvut']['ero'] += $rivi['inventointi_poikkeama_eur'];
  }
}

function keraa_inventointilajeittain_luvut(&$rivi, &$rivit_temp, $inventointilaji, $aika) {
  if ($rivi['inventointi_poikkeama_eur'] > 0) {
    if (!isset($rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['pos'])) {
      $rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['pos'] = $rivi['inventointi_poikkeama_eur'];
    }
    else {
      $rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['pos'] += $rivi['inventointi_poikkeama_eur'];
    }
  }
  else {
    if (!isset($rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['neg'])) {
      $rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['neg'] = $rivi['inventointi_poikkeama_eur'];
    }
    else {
      $rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['neg'] += $rivi['inventointi_poikkeama_eur'];
    }
  }

  if (!isset($rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['ero'])) {
    $rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['ero'] = $rivi['inventointi_poikkeama_eur'];
  }
  else {
    $rivit_temp[$aika]['inventointilajit'][$inventointilaji]['inventointilajeittain_luvut']['ero'] += $rivi['inventointi_poikkeama_eur'];
  }
}

function parsi_paivat(&$request) {

  if (!empty($request['valittu_tilikausi'])) {
    $tilikausi_temp = search_array_key_for_value_recursive($request['tilikaudet'], 'tunnus', $request['valittu_tilikausi']);
    //funktion on tarkoitus palauttaa ainoastaan yksi tilikausi, siksi voimme viitata indeksillä
    $request['alku_aika'] = $tilikausi_temp[0]['tilikausi_alku'];
    $request['loppu_aika'] = $tilikausi_temp[0]['tilikausi_loppu'];
  }
  else {
    $request['alku_aika'] = $request['vva'].'-'.$request['kka'].'-'.$request['ppa'];
    $request['loppu_aika'] = date('Y-m-d', strtotime($request['vvl'].'-'.$request['kkl'].'-'.$request['ppl'] . ' + 1 day'));
  }
}

function hae_inventoitavien_lukumaara(&$request, $aikavali_tyyppi = '') {
  global $kukarow;

  if ($aikavali_tyyppi == '12kk') {
    $request['alku_aika'] = date('Y-m-d', strtotime('now - 12 month'));
    $request['loppu_aika'] = date('Y-m-d');
  }
  elseif ($aikavali_tyyppi == 'tilikausi') {
    $request['alku_aika'] = $request['tamanhetkinen_tilikausi']['tilikausi_alku'];
    $request['loppu_aika'] = $request['tamanhetkinen_tilikausi']['tilikausi_loppu'];
  }

  if (!empty($request['valittu_yhtio'])) {
    //jos requestista on tullut yhtio käytetään sitä
    $yhtio = $request['valittu_yhtio'];
  }
  else {
    $yhtio = $kukarow['yhtio'];
  }

  if ($request['valittu_status'] == 'EIPOISTETTUJA') {
    $status_where = "AND tuote.status != 'P'";
  }
  else {
    $status_where = "AND tuote.status = '{$request['valittu_status']}'";
  }

  $ei_huomioida_lisa = ei_huomioida_tuotepaikkoja_avainsanoista($request['ei_huomioida_tuotepaikkoja_avainsanoista'], 'tapahtuma');

  $vain_saldoa_join = '';
  if ( $request['vain_saldoa'] ) {
    $vain_saldoa_join = '
      JOIN tuotepaikat
      ON ( tuotepaikat.yhtio = tapahtuma.yhtio
        AND tuotepaikat.tuoteno = tuote.tuoteno
        AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
        AND tuotepaikat.hyllynro = tapahtuma.hyllynro
        AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
        AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
        AND tuotepaikat.saldo <> 0 )';
  }

  $query = "SELECT tapahtuma.hyllyalue AS hyllyalue, tapahtuma.hyllynro AS hyllynro, COUNT(DISTINCT CONCAT(tapahtuma.tuoteno, tapahtuma.hyllyalue, tapahtuma.hyllynro, tapahtuma.hyllyvali, tapahtuma.hyllytaso)) AS kpl
            FROM tapahtuma USE INDEX (yhtio_laji_laadittu)
            JOIN tuote
            ON ( tuote.yhtio = tapahtuma.yhtio
              AND tuote.tuoteno   = tapahtuma.tuoteno
              AND tuote.ei_saldoa = ''
              {$status_where} )
            {$vain_saldoa_join}
            WHERE tapahtuma.yhtio = '{$kukarow['yhtio']}'
            AND tapahtuma.laadittu BETWEEN '{$request['alku_aika']}' AND '{$request['loppu_aika']}'
            AND tapahtuma.laji    = 'Inventointi'
            {$ei_huomioida_lisa}
            GROUP BY 1,2";
  $result = pupe_query($query);

  $count = 0;
  while ($varasto_row = mysql_fetch_assoc($result)) {
    $count += kuuluuko_hylly_varastoon($request, $varasto_row);
  }

  return $count;
}

function hae_tuotepaikkojen_lukumaara(&$request) {
  global $kukarow;

  if (!empty($request['valittu_yhtio'])) {
    //jos requestista on tullut yhtio käytetään sitä
    $yhtio = $request['valittu_yhtio'];
  }
  else {
    $yhtio = $kukarow['yhtio'];
  }

  if ($request['valittu_status'] == 'EIPOISTETTUJA') {
    $status_where = "AND tuote.status != 'P'";
  }
  else {
    $status_where = "AND tuote.status = '{$request['valittu_status']}'";
  }

  $ei_huomioida_lisa = ei_huomioida_tuotepaikkoja_avainsanoista($request['ei_huomioida_tuotepaikkoja_avainsanoista'], 'tuotepaikat');

  $vain_saldoa_where = '';
  if ( $request['vain_saldoa'] ) {
    $vain_saldoa_where = 'AND tuotepaikat.saldo <> 0.00';
  }

  $query = "SELECT tuotepaikat.hyllyalue as hyllyalue, tuotepaikat.hyllynro as hyllynro, count(*) as kpl
            FROM tuote
            JOIN tuotepaikat
            USING (yhtio, tuoteno)
            WHERE tuote.yhtio   = '{$kukarow['yhtio']}'
            {$vain_saldoa_where}
            AND tuote.ei_saldoa = ''
            {$status_where}
            {$ei_huomioida_lisa}
            GROUP BY 1,2";
  $result = pupe_query($query);

  $count = 0;
  while ($varasto_row = mysql_fetch_assoc($result)) {
    $count += kuuluuko_hylly_varastoon($request, $varasto_row);
  }

  return $count;
}

function kuuluuko_hylly_varastoon($request, $varasto_row) {
  global $kukarow;

  $query = "SELECT varastopaikat.tunnus
            FROM varastopaikat
            WHERE yhtio              = '{$kukarow['yhtio']}'
            AND concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'),lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper('{$varasto_row['hyllyalue']}'), 5, '0'),lpad(upper('{$varasto_row['hyllynro']}'), 5, '0'))
            AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper('{$varasto_row['hyllyalue']}'), 5, '0'),lpad(upper('{$varasto_row['hyllynro']}'), 5, '0'))
            AND varastopaikat.tunnus IN (".implode(', ', $request['valitut_varastot']).")";

  $count = 0;
  //jos ollaan valittu kaikki varastot tiedetään, että se kuuluu varmasti valittuihin varastoihin
  if (count($request['varastot']) == count($request['valitut_varastot'])) {
    $count = $varasto_row['kpl'];
  }
  else {
    $varasto_result = pupe_query($query);
    if (mysql_num_rows($varasto_result) != 0) {
      $count = $varasto_row['kpl'];
    }
  }


  return $count;
}

function hae_inventoinnit(&$request) {
  global $kukarow, $yhtiorow;

  parsi_paivat($request);
  //kun inventointeja haetaan päivän perusteella Inventointeja yhteensä kenttään, halutaan näyttää myös inventoinnit joiden inventoitu saldo on ollut 0
  if (empty($request['pvm_inventointeja_yhteensa'])) {
    $tapahtuma_where = "AND tapahtuma.kpl != 0";
  }
  $group = "";

  if (!empty($request['valitut_inventointilajit'])) {
    $inventointilaji_rajaus = "AND ( ";
    foreach ($request['valitut_inventointilajit'] as $inventointilaji) {
      $inventointilaji_rajaus .= " tapahtuma.selite LIKE '%$inventointilaji' OR";
    }
    //viimenen "OR " pois
    $inventointilaji_rajaus = substr($inventointilaji_rajaus, 0, -3);
    $inventointilaji_rajaus .= " )";
  }

  if (!empty($request['valittu_yhtio'])) {
    //jos requestista on tullut yhtio käytetään sitä
    $yhtio = $request['valittu_yhtio'];
  }
  else {
    $yhtio = $kukarow['yhtio'];
  }

  if ($request['valittu_status'] == 'EIPOISTETTUJA') {
    $tuote_join = "AND tuote.status != 'P'";
  }
  else {
    $tuote_join = "AND tuote.status = '{$request['valittu_status']}'";
  }

  $ei_huomioida_lisa = ei_huomioida_tuotepaikkoja_avainsanoista($request['ei_huomioida_tuotepaikkoja_avainsanoista'], 'tapahtuma');

  $vain_saldoa_join = '';
  if ($request['vain_saldoa']) {
    $vain_saldoa_join = '
      JOIN tuotepaikat
      ON ( tuotepaikat.yhtio = tapahtuma.yhtio
        AND tuotepaikat.tuoteno = tuote.tuoteno
        AND tuotepaikat.hyllyalue = tapahtuma.hyllyalue
        AND tuotepaikat.hyllynro = tapahtuma.hyllynro
        AND tuotepaikat.hyllytaso = tapahtuma.hyllytaso
        AND tuotepaikat.hyllyvali = tapahtuma.hyllyvali
        AND tuotepaikat.saldo <> 0 )';
  }

  $query = "SELECT DATE(tapahtuma.laadittu) laadittu_pvm,
            tapahtuma.laadittu,
            YEAR(tapahtuma.laadittu) as vuosi,
            MONTH(tapahtuma.laadittu) as kuukausi,
            DAY(tapahtuma.laadittu) as paiva,
            TIME(tapahtuma.laadittu) as kellon_aika,
            ( tapahtuma.kpl * tapahtuma.hinta ) AS inventointi_poikkeama_eur,
            tapahtuma.selite,
            substring( tapahtuma.selite, ( length(tapahtuma.selite)-locate( '>rb<',reverse(tapahtuma.selite)) ) +2 ) AS inventointilaji,
            tapahtuma.tuoteno,
            tuote.nimitys AS tuote_nimitys,
            tuote.try AS tuoteryhma,
            tapahtuma.kpl,
            Concat_ws('-', tapahtuma.hyllyalue, tapahtuma.hyllynro, tapahtuma.hyllytaso, tapahtuma.hyllyvali) AS hyllypaikka,
            IFNULL(kuka.nimi, '".t("Poistettu käyttäjä")."') as laatija,
            IFNULL(keraysvyohyke.nimitys, '".t("Poistettu")."') AS keraysvyohyke_nimitys
            FROM tapahtuma USE INDEX (yhtio_laji_laadittu)
            JOIN tuote
            ON ( tuote.yhtio = tapahtuma.yhtio
              AND tuote.tuoteno        = tapahtuma.tuoteno
              AND tuote.ei_saldoa      = ''
              {$tuote_join} )
            LEFT JOIN avainsana
            ON ( avainsana.yhtio = tuote.yhtio
              AND avainsana.selite     = tuote.try
              AND avainsana.laji       = 'TRY'
              AND avainsana.kieli      = '{$yhtiorow['kieli']}')
            LEFT JOIN kuka
            ON ( kuka.yhtio = tapahtuma.yhtio
              AND kuka.kuka            = tapahtuma.laatija )
            LEFT JOIN varaston_hyllypaikat AS vh
            ON ( vh.yhtio = tapahtuma.yhtio
              AND vh.hyllyalue         = tapahtuma.hyllyalue
              AND vh.hyllynro          = tapahtuma.hyllynro
              AND vh.hyllytaso         = tapahtuma.hyllytaso
              AND vh.hyllyvali         = tapahtuma.hyllyvali )
            LEFT JOIN keraysvyohyke
            ON ( keraysvyohyke.yhtio = vh.yhtio
              AND keraysvyohyke.tunnus = vh.keraysvyohyke )
            {$vain_saldoa_join}
            WHERE tapahtuma.yhtio      = '{$yhtio}'
              AND tapahtuma.laadittu BETWEEN '{$request['alku_aika']}' AND '{$request['loppu_aika']}'
              AND tapahtuma.laji       = 'Inventointi'
              AND tapahtuma.varasto    IN (".implode(', ', $request['valitut_varastot']).")
            {$tapahtuma_where}
            {$inventointilaji_rajaus}
            {$ei_huomioida_lisa}
            ORDER BY inventointilaji ASC
            {$group}";
  $result = pupe_query($query);

  $rivit = array();
  while ($rivi = mysql_fetch_assoc($result)) {
    $rivit[] = $rivi;
  }

  return $rivit;
}

function hae_varaston_hyllypaikkojen_lukumaara($request) {
  global $kukarow;

  if (!empty($request['valittu_yhtio'])) {
    //jos requestista on tullut yhtio käytetään sitä
    $yhtio = $request['valittu_yhtio'];
  }
  else {
    $yhtio = $kukarow['yhtio'];
  }

  $query = "SELECT count(*) as varaston_hyllypaikkojen_lukumaara
            FROM varaston_hyllypaikat
            JOIN varastopaikat
            ON ( varastopaikat.yhtio = varaston_hyllypaikat.yhtio
              AND varastopaikat.tunnus       = varaston_hyllypaikat.varasto
              AND varastopaikat.tunnus       IN (".implode(', ', $request['valitut_varastot']).") )
            WHERE varaston_hyllypaikat.yhtio = '{$yhtio}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function hae_yhtiot(&$request = array()) {
  global $kukarow;

  $query = "SELECT *
            FROM yhtio";
  $result = pupe_query($query);

  $yhtiot = array();
  while ($yhtio = mysql_fetch_assoc($result)) {
    if (!empty($request) and !empty($request['yhtio'])) {
      if ($request['yhtio'] == $yhtio['tunnus']) {
        //jos requestista tulee valittu yhtio valitaan se
        $yhtio['selected'] = 'selected';

        //laitetaan requestista tullut valittu yhtio talteen myöhempää käyttöä varten
        $request['valittu_yhtio'] = $yhtio['yhtio'];
      }
      else {
        $yhtio['selected'] = '';
      }
    }
    else {
      //jos requestista ei tule valittua yhtiota esivalitaan käyttäjän yhtiö
      if ($yhtio['yhtio'] == $kukarow['yhtio']) {
        $yhtio['selected'] = 'selected';
      }
      else {
        $yhtio['selected'] = '';
      }
    }

    $yhtiot[] = $yhtio;
  }

  return $yhtiot;
}

function hae_tilikaudet($request = array(), $yhtio = '') {
  global $kukarow;

  //tämä on ajax_requestia varten
  if (empty($yhtio)) {
    $yhtio = $kukarow['yhtio'];
  }

  //jos requestista tulee yhtiö käytetään sitä
  if (!empty($request['yhtio'])) {
    $yhtio = $request['valittu_yhtio'];
  }

  $query = "SELECT *
            FROM tilikaudet
            WHERE yhtio = '{$yhtio}'
            ORDER BY tilikausi_alku DESC";
  $result = pupe_query($query);

  $tilikaudet = array();
  while ($tilikausi = mysql_fetch_assoc($result)) {
    if (!empty($request) and $request['tilikausi'] == $tilikausi['tunnus']) {
      $tilikausi['selected'] = 'selected';
    }
    else {
      //jos requestista ei tule valittua tilikautta, esivalitaan tämän hetkinen tilikausi, mutta jos requestissa tulee ppa, kka, vva, ppl, kkl tai vvl tarkoittaa tämä, että kyseessä ei ole ensimmäinen sivu lataus, jolloin tilikautta ei kuulu esivalita
      //huom! js estää epävalidin ajan syöttämisen, tällöin riittää, että tarkastamme vva:n
      if ($tilikausi['tilikausi_alku'] <= date('Y-m-d') and $tilikausi['tilikausi_loppu'] > date('Y-m-d') and empty($request['vva'])) {
        $tilikausi['selected'] = 'selected';
      }
      else {
        $tilikausi['selected'] = '';
      }
    }
    $tilikausi['tilikausi'] = date('d.m.Y', strtotime($tilikausi['tilikausi_alku'])).' - '.date('d.m.Y', strtotime($tilikausi['tilikausi_loppu']));
    $tilikaudet[] = $tilikausi;
  }

  return $tilikaudet;
}

//tarvitaan uusi yhtio parametri ajax_requestia varten
function inventointiaste_hae_varastot($request = array(), $yhtio = '') {
  global $kukarow;

  //ajax_requestia varten
  if (empty($yhtio)) {
    $yhtio = $kukarow['yhtio'];
  }

  //jos requestista on tullut valittu_yhtio käytetään sitä
  if (!empty($request['valittu_yhtio'])) {
    $yhtio = $request['valittu_yhtio'];
  }

  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio  = '{$yhtio}'
            AND tyyppi  != 'P'";
  $result = pupe_query($query);

  $varastot = array();
  while ($varasto = mysql_fetch_assoc($result)) {
    //jos requestista tulee valittuja varastoja valitaan ne
    if (!empty($request) and !empty($request['valitut_varastot'])) {
      if (in_array($varasto['tunnus'], $request['valitut_varastot'])) {
        $varasto['checked'] = 'checked';
      }
      else {
        $varasto['checked'] = '';
      }
    }
    else {
      //requesista ei tullut valittuja varastoja
      //valitaan käyttäjän oletusvarasto jos asetettu
      if (!empty($kukarow['oletus_varasto'])) {
        if ($kukarow['oletus_varasto'] == $varasto['tunnus']) {
          $varasto['checked'] = 'checked';
        }
        else {
          $varasto['checked'] = '';
        }
      }
      else {
        //jos ei oletus varastoa ja requestista ei tule valittuja varastoja, esivalitaan kaikki varastot
        $varasto['checked'] = 'checked';
      }
    }


    $varastot[] = $varasto;
  }

  return $varastot;
}

function hae_inventointilajit($request = array(), $yhtio = '') {
  global $kukarow;

  //ajax_requestia varten
  if (empty($yhtio)) {
    $yhtio = $kukarow['yhtio'];
  }

  //jos requestista on tullut valittu_yhtio käytetään sitä
  if (!empty($request['valittu_yhtio'])) {
    $yhtio = $request['valittu_yhtio'];
  }

  $query = "SELECT *
            FROM avainsana
            WHERE yhtio = '{$yhtio}'
            AND laji    = 'INVEN_LAJI'";
  $result = pupe_query($query);

  $inventointilajit = array();
  while ($inventointilaji = mysql_fetch_assoc($result)) {
    //jos requestista tulee valittuja inventointilajeja valitaan ne
    if (!empty($request)) {
      if (!empty($request['valitut_inventointilajit'])) {
        if (in_array($inventointilaji['selite'], $request['valitut_inventointilajit'])) {
          $inventointilaji['checked'] = 'checked';
        }
        else {
          $inventointilaji['checked'] = '';
        }
      }
      else {
        //oletuksena valitaan kaikki
        $inventointilaji['checked'] = 'checked';
      }
    }

    $inventointilaji['array_key'] = preg_replace('/[^a-zA-Z0-9]/', '_', $inventointilaji['selite']);
    $inventointilajit[] = $inventointilaji;
  }

  return $inventointilajit;
}

function hae_tuote_statukset($request = array()) {
  global $kukarow, $yhtiorow;

  $statukset = array();
  foreach (product_statuses() as $key => $value) {
    $statukset[] = array(
      "selite"     => $key,
      "selected"   => $request["valittu_status"] == $key ? 'selected' : '',
      "selitetark" => $value,
    );
  }

  $selected = "";
  if (!empty($request['valittu_status']) and $request['valittu_status'] == 'EIPOISTETTUJA') {
    $selected = "selected";
  }
  $statukset[] = array(
    'selite'   => 'EIPOISTETTUJA',
    'selected'   => $selected,
    'selitetark' => t('Ei poistettuja')
  );

  return $statukset;
}

function echo_tallennus_formi($xls_filename) {
  echo "<form method='post' class='multisubmit'>";
  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Tallenna excel aineisto").":</th>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='lataa_tiedosto' value='1'>";
  echo "<input type='hidden' name='kaunisnimi' value='".t('Inventoinnit').".xlsx'>";
  echo "<input type='hidden' name='tmpfilenimi' value='{$xls_filename}'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";
  echo "<br/>";
}

function array_utf8_encode(&$item, $key) {
  $item = utf8_encode($item);
}
