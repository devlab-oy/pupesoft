$(document).ready(function() {
  $('.muokkaa_btn').on('click', function(e) {
    e.preventDefault();
    if (confirm($('#keratty_ja_ylitetty_warning').val())) {
      $(this).closest('form').submit();
    }
  });

  $('#kertakassa').on('change', function() {
    $('#kaikkyhtTee').val('PAIVITA_KASSALIPAS');
    $('#kaikkyht').submit();
  });

  $('#myyja_id').on('change', function () {
    $(this).siblings('#myyjanro_id').val('');
    $(this).closest('form').submit();
  });

  if ($('#tilausrivin_esisyotto_parametri').val() == 'K') {

    var toim = $('#toim').val(),
        rivitunnus_chk = $("form[name='tilaus']").find("input[name='rivitunnus']"),
        tilausrivi_alvillisuus = $("input[name='tilausrivi_alvillisuus']:checked").val();

    if (rivitunnus_chk.length == 0 || rivitunnus_chk.val() == '') {
      $("input[name='tuoteno']").on('keyup', function() {
        $("input[name='kpl']").val('');
        $("input[name='hinta']").val('');
        $("input[name='ale1']").val('');
        $("input[name='ale2']").val('');
        $("input[name='ale3']").val('');
        $('#kate_rivi_laskenta').html('');
        $('#ykshinta_rivi_laskenta').html('');
        $('#rivihinta_rivi_laskenta').html('');

        if (toim == 'PIKATILAUS') {
          $("input[name='netto']").val('');
        }
        else {
          $("select[name='netto']").val('');
        }
      });
    }

    $("input[name='kpl']").on('keyup', function() {

      var kplkentta = $(this)

      delay_ms(function(){

        if (toim == 'PIKATILAUS') {
          var netto = $("input[name='netto']");
        }
        else {
          var netto = $("select[name='netto']");
        }

        if ($("input[name='tuoteno']").val() != '') {
          if (kplkentta.val() == '' && (rivitunnus_chk.length == 0 || rivitunnus_chk.val() == '')) {
            $("input[name='hinta']").val('');
            $("input[name='ale1']").val('');
            $("input[name='ale2']").val('');
            $("input[name='ale3']").val('');
            $('#kate_rivi_laskenta').html('');
            $('#ykshinta_rivi_laskenta').html('');
            $('#rivihinta_rivi_laskenta').html('');

            if (toim == 'PIKATILAUS') {
              netto.val('');
            }
            else {
              netto.val('');
            }
          }
          else {
            $.ajax({
              async: false,
              type: 'POST',
              data: {
                tuoteno: $("input[name='tuoteno']").val(),
                kpl: $("input[name='kpl']").val(),
                hinta: $("input[name='hinta']").val(),
                hinta_esisyotetty: $("input[name='hinta']").attr('class'),
                ale1: $("input[name='ale1']").val(),
                ale2: $("input[name='ale2']").val(),
                ale3: $("input[name='ale3']").val(),
                alv: $("select[name='alv']").val(),
                netto: netto.val(),
                tilausnumero: $("input[name='tilausnumero']").val(),
                tilausrivi_alvillisuus: tilausrivi_alvillisuus,
                toim: toim,
                ajax_toiminto: 'esisyotto',
                no_head: 'yes',
                ohje: 'off'
              },
              url: '../tilauskasittely/tilaus_myynti.php'
            }).done(function(data) {

              var data = jQuery.parseJSON(data);

              $("input[name='hinta']").addClass('esisyotetty')

              if (data.hinta != '' && (rivitunnus_chk.length == 0 || rivitunnus_chk.val() == '' || $("input[name='hinta']").val() == '')) {
                $("input[name='hinta']").val(data.hinta);
              }

              if (data.netto != '') {

                if (toim == 'PIKATILAUS') {
                  $("input[name='netto']").val(data.netto);
                }
                else {
                  $("select[name='netto']").val(data.netto);
                }
              }

              $.each(data['ale'], function(index, value) {
                if (value != '') {
                  $("input[name='"+index+"']").val(value);
                }
              });

              if (data.kate && data.kate != '') {
                $('#kate_rivi_laskenta').html(data.kate+'%');
              }

              if (data.ykshinta != '') {
                $('#ykshinta_rivi_laskenta').html(data.ykshinta);
              }

              if (data.rivihinta != '') {
                $('#rivihinta_rivi_laskenta').html(data.rivihinta);
              }

            }).fail(function(data) {
              console.log('Esisyotossa virhe');
            });
          }
        }

      }, 500 );

    });

    $("input[name='ale1']").on('keyup', function() {
      setTimeout(() => { $("input[name='kpl']").trigger('keyup') }, 0)
    });

    $("input[name='ale2']").on('keyup', function() {
      setTimeout(() => { $("input[name='kpl']").trigger('keyup') }, 0)
    });

    $("input[name='ale3']").on('keyup', function() {
      setTimeout(() => { $("input[name='kpl']").trigger('keyup') }, 0)
    });

    $("select[name='alv']").on('change', function() {
      setTimeout(() => { $("input[name='kpl']").trigger('keyup') }, 0)
    });

    $("input[name='hinta']").on('keyup', function() {
      setTimeout(() => { $("input[name='kpl']").trigger('keyup') }, 0)
    });

    if (rivitunnus_chk.length != 0 && rivitunnus_chk.val() != '') {
      setTimeout(() => { $("input[name='kpl']").trigger('keyup') }, 0)
    }
  }

  $('#hintojen_vaihto').on('change', function() {
    $('.hv_hidden').val( this.checked ? 'JOO' : 'EI' );
  });

  $('#hae_asiakasta_hintavaihto_cb').on('change', function() {
    $('#hae_asiakasta_hv_hidden').val( this.checked ? 'JOO' : 'EI' );
  });

  $('#hae_asiakasta_linkki').on('click', function(e) {
    e.preventDefault();

    $('#hae_asiakasta_spani').hide();
    $('#hae_asiakasta_piilospan').show();
    $('#hae_asiakasta_boksi').focus();
  });

  $('#hae_asiakasta_boksi').on('keyup', function(e) {
    e.preventDefault();

    $('#syotetty_ytunnus').val($(this).val());
  });

  $('#hae_asiakasta_boksi').keypress(function(e) {
    if(e.keyCode == 13) {
      $('#hae_asiakasta_formi').submit();
    }
  });

  $('#hae_asiakasta_boksi_button').on('click', function(e) {
    e.preventDefault();
    $('#hae_asiakasta_formi').submit();
  });

  desimaalia = parseInt($('#desimaalia').val());

  bind_valitut_rivit_checkbox_click();

  var hinta_laskurit = $.parseJSON( $('#hinta_laskurit').val() );

  // Liipaise hintalaskurit käyntiin.
  if (hinta_laskurit) {
    $.each(hinta_laskurit, function (perheid, hinta_laskuri) {
      // Jos vain korkeintaan yksi valmiste tai ei raaka-aineita, ei laskuria tarvita.
      if (hinta_laskuri.valmisteet.length < 2 || hinta_laskuri.raakaaineiden_kehahinta_summa == 0)
        return true;

      new Hinta_laskuri(perheid, hinta_laskuri.raakaaineiden_kehahinta_summa, hinta_laskuri.valmisteiden_painoarvot);
    });
  }

  var korvamerkitse_ajax = function() {

    var korva_dd_id = $(this).attr("id");
    var rivitunnus = korva_dd_id.replace("korva_dd_", "");

    $.post("",
      {
        tila: 'KORVAMERKITSE_AJAX',
        toim: $('#toim').val(),
        tilausnumero: $('#tilausnumero').val(),
        rivitunnus: rivitunnus,
        korvamerkinta: $('#'+korva_dd_id).find('option:selected').val(),
        async: false,
        no_head: 'yes',
        ohje: 'off'
      },
      function(json) {
        var message = JSON && JSON.parse(json) || $.parseJSON(json);

        if (message != "OK") {
          $('#'+korva_dd_id).replaceWith("<font class='error'>FATAL ERROR!</font>");
        }
      }
    );

    return false;
  }

  $('.korva_dd').on("change", korvamerkitse_ajax);

  var yhteyshenkilon_puhelin = function() {

    var yhenkilo = $("option:selected", this).val();
    var ltunnus = $("#y_liitostunnus").html();

    $.post("",
      {
        tila: 'YHENKPUHELIN',
        toim: $('#toim').val(),
        tilausnumero: $('#tilausnumero').val(),
        yhenkilo: yhenkilo,
        ltunnus: ltunnus,
        async: false,
        no_head: 'yes',
        ohje: 'off'
      },
      function(json) {
        var message = JSON && JSON.parse(json) || $.parseJSON(json);

        if (message.PUH != "") {
          $('#tpuh').val(message.PUH);
        }

        if (message.EMAIL != "") {
          $('#temail').val(message.EMAIL);
        }

        // Nollataan käsinsyötetty yhteyshenkilö
        $('#manual_tilausyhteyshenkilo').val('');

      }
    );

    return false;
  }

  $('#yhteyshenkilo_tilaus').on("change", yhteyshenkilon_puhelin);
});

function bind_valitut_rivit_checkbox_click() {
  $('.valitut_rivit').on('click', function() {

    if ($(this).is(':checked')) {
      if ($(this).val().indexOf(',') > -1) {
        tunnukset_array = $(this).val().split(',');
        for (var i in tunnukset_array) {
          $('#jyvita_valitut_form').append("<input type='hidden' name='valitut_tilausrivi_tunnukset[]' class='tilausrivi_tunnukset' value='" + tunnukset_array[i] + "'>");
        }
      }
      else {
        $('#jyvita_valitut_form').append("<input type='hidden' name='valitut_tilausrivi_tunnukset[]' class='tilausrivi_tunnukset' value='" + $(this).val() + "'>");
      }
    }
    else {
      if ($(this).val().indexOf(',') > -1) {
        tunnukset_array = $(this).val().split(',');
        for (var x in tunnukset_array) {
          $("#jyvita_valitut_form input[value='" + tunnukset_array[x] + "']").remove();
        }
      }
      else {
        $("#jyvita_valitut_form input[value='" + $(this).val() + "']").remove();
      }
    }
  });
}

function nappi_onclick_confirm(message) {
  ok = confirm(message);

  return ok;
};

// Hinta_kokoelmaa käytetään yhteenkuuluvien raaka-ainerivien ja valmisterivien hintojen hallintaan.
function Hinta_laskuri(perheid, raakaaineiden_kehahinta_summa, valmisteiden_painoarvot) {
  var me = this;

  // Kaikkien rivien perheid
  this.perheid = perheid;
  this.raakaaineiden_kehahinta_summa = raakaaineiden_kehahinta_summa;
  // Valmisteiden painoarvot, tunnus ja painoarvo avaimena ja arvona.
  this.valmisteiden_painoarvot = valmisteiden_painoarvot;

  // Valuuttainputit, tunnus ja jQuery elementti avaimena ja arvona.
  this.valmiste_hinta_inputit = {};
  $('input[name^="valmiste_valuutta"][data-perheid="'+ this.perheid +'"]').each(function(index, input) {
    var $input = $(input);
    me.valmiste_hinta_inputit[ $input.data('tunnus') ] = $input;
  });

  // Lukkocheckboxit, tunnus ja jQuery elementti avaimena ja arvona.
  this.valmiste_lukko_inputit = {};
  $('input.valmiste_lukko[data-perheid="'+ this.perheid +'"]').each(function(index, input) {
    var $input = $(input);
    me.valmiste_lukko_inputit[ $input.data('tunnus') ] = $input;
  });

  $.each(this.valmiste_hinta_inputit, function(tunnus, $input) {
    // Kun valmisteinput fokusoidaan, tallennetaan vanha arvo ja checkataan lukko päälle.
    $input.focus(function() {
      // Tallenna vanha arvo.
      $input.data('vanha-arvo', $input.val());
      // Ruksaa lukko.
      me.valmiste_lukko_inputit[tunnus].attr('checked', 'checked');
    });

    // Kun valmisteinputin arvo vaihtuu, jaetaan hinnan muutos muille lukottomille valmisteille.
    $input.change(function() {

      var vanha_arvo = $input.data('vanha-arvo'),
          _val = parseFloat($input.val().replace(',','.'));

      if (isNaN(_val)) _val = vanha_arvo;

      $input.val(_val);

      if ($input.val() < 0) {
        $input.val(vanha_arvo);
        alert('Hinta ei saa olla negatiivinen');
        return;
      }

      // Jos hinnat menevät yli tai ali, palauta vanha arvo ja infoa käyttäjää.
      if (me.tarkista_hinnat()===false) {
        $input.val(vanha_arvo);
        alert('Hinta on liian pieni tai suuri');
        return;
      }

      // Validit hinnat, jaa muutos lukottomille inputeille ja päivitä painoarvot.
      me.jaa_muutos_vapaille(vanha_arvo - $input.val());
      me.laske_painoarvot();
      $input.data('vanha-arvo', $input.val());
    });
  });

  // Jaa valmisteiden hinnan muutos tasaisesti lukottomien valmisteiden hintoihin.
  this.jaa_muutos_vapaille = function(muutos) {
    var lukottomien_lkm = $('input.valmiste_lukko[data-perheid="'+ this.perheid +'"]:not(:checked)').length;
    var muutos_per_lukoton = muutos / lukottomien_lkm;

    $.each(this.valmiste_hinta_inputit, function(tunnus, $input) {
      // Vain inputit, jotka eivät ole merkitty lukituiksi.
      if (me.valmiste_lukko_inputit[tunnus].attr('checked') != 'checked') {
        $input.val( (parseFloat($input.val()) + muutos_per_lukoton).toFixed(desimaalia) );
      }
    });
  };

  // Laske valmisteiden hintojen painoarvot suhteessa raaka-aineiden kehahintaan.
  this.laske_painoarvot = function() {
    var me = this, painoarvo;
    $.each(this.valmiste_hinta_inputit, function(tunnus, $input) {
      var painoarvo = $input.val() / me.raakaaineiden_kehahinta_summa;

      me.valmisteiden_painoarvot[tunnus] = painoarvo;
    });

    // Päivitä painoarvot kantaan aina kun ne päivittyvät.
    $.post('', {ajax_toiminto: 'tallenna_painoarvot', no_head: 'yes', painoarvot: this.valmisteiden_painoarvot});
  };

  // Laske valmisteiden hinnat painoarvojen mukaan.
  this.laske_hinnat = function() {
    var me = this;
    $.each(this.valmiste_hinta_inputit, function(tunnus, $input) {
      $input.val( (me.valmisteiden_painoarvot[tunnus] * me.raakaaineiden_kehahinta_summa).toFixed(desimaalia) );
    });
  };

  // Tarkista, ettei hinnat mene yli tai ali.
  this.tarkista_hinnat = function() {
    var lukollisten_hintojen_summa = 0, lukottomien_lkm = 0;

    $.each(this.valmiste_hinta_inputit, function(tunnus, $input) {
      // Lukitut hinnat.
      if (me.valmiste_lukko_inputit[tunnus].attr('checked') == 'checked') {
        lukollisten_hintojen_summa += parseFloat($input.val());
      }
      else {
        lukottomien_lkm++;
      }
    });

    // Tarkista, ettei lukitut hinnat ylitä raaka-aineiden hintoja.
    if (lukollisten_hintojen_summa > this.raakaaineiden_kehahinta_summa)
      return false;

    // Tarkista, ettei kaikki hinnat ole lukittuja samalla alittaen raaka-aineiden hinnat.
    if (lukottomien_lkm === 0 && lukollisten_hintojen_summa < this.raakaaineiden_kehahinta_summa)
      return false;

    return true;
  };

  // Init laskennat.
  this.laske_hinnat();
  this.laske_painoarvot();
};
