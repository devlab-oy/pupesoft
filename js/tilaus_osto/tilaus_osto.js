$(document).ready(function() {
  bind_toggle_korvaavat_vastaavat_click();
});

function bind_toggle_korvaavat_vastaavat_click() {
  $('.toggle_korvaavat_vastaavat').on('click', function() {
    var rivitunnus = $(this).attr('rivitunnus');

    var $korvaavat_vastaavat_table = $(this).parent().parent().parent().find('tr.' + rivitunnus);

    if ($korvaavat_vastaavat_table.hasClass('vastaavat_korvaavat_not_hidden')) {
      $korvaavat_vastaavat_table.addClass('vastaavat_korvaavat_hidden');
      $korvaavat_vastaavat_table.removeClass('vastaavat_korvaavat_not_hidden');
    }
    else {
      $korvaavat_vastaavat_table.addClass('vastaavat_korvaavat_not_hidden');
      $korvaavat_vastaavat_table.removeClass('vastaavat_korvaavat_hidden');
    }
  });
}

function tarkasta_ostotilauksen_tilausrivien_toimittajien_saldot(tilausnumero, alert_viesti) {
  saldot_request_obj = hae_ostotilauksen_tilausrivien_toimittajien_saldot(tilausnumero);

  var viesti = '';
  saldot_request_obj.done(function(saldot) {
    if (saldot.length > 0) {
      $.each(saldot, function(index, saldo) {
        alert_viesti_temp = alert_viesti.replace('*tuote*', saldo.tuoteno);
        alert_viesti_temp = alert_viesti_temp.replace('*kpl*', saldo.tehdas_saldo);
        viesti += alert_viesti_temp + '\n';
      });
    }
  });

  if (viesti) return confirm(viesti);

  return;
}

function hae_ostotilauksen_tilausrivien_toimittajien_saldot(tilausnumero) {
  return $.ajax({
    async: false,
    type: 'GET',
    dataType: 'JSON',
    data: {
      ajax_request: 1,
      no_head: 'yes',
      hae_toimittajien_saldot: 1,
      tilausnumero: tilausnumero
    },
    url: 'tilaus_osto.php'
  }).done(function(data) {
    if (console && console.log) {
      console.log('Saldojen haku onnistui');
      //console.log(data);
    }
  }).fail(function(data) {
    if (console && console.log) {
      console.log('Saldojen haku EPÄONNISTUI');
    }
  });
}
