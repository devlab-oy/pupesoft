$(document).ready(function() {
  $('#kertakassa').on('change', function() {
    $('#kaikkyhtTee').val('PAIVITA_KASSALIPAS');
    $('#kaikkyht').submit();
  });

  $('#myyja_id').on('change', function () {
    $(this).siblings('#myyjanro_id').val('');
    $(this).closest('form').submit();
  });

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
