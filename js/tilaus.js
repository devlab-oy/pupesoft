$(document).ready(function() {
  bind_tarkista_tehtaan_saldot_click();
});

function bind_tarkista_tehtaan_saldot_click() {

  $('div.availability').css({
    'width': '15px',
    'height': '15px',
    'float': 'left',
    'background-color': 'transparent',
    'margin-left': '5px',
    'border-radius': '50%',
    '-webkit-border-radius': '50%',
    '-moz-border-radius': '50%'
  });

  var bgcolors = ['#E66','#FCF300','#5D2'];

  $('.tarkista_tehtaan_saldot_kaikki').on('click', function() {

    $id = $(this).attr('id');
    $toim = $('#toim').val();

    $.post('',
      {
      otunnus: $id,
      ajax_toiminto: 'tarkista_tehtaan_saldot_kaikki',
      toim: $toim,
      no_head: 'yes',
      ohje: 'off'
      },
      function(return_value) {
        var data = jQuery.parseJSON(return_value);

        for (var tun in data.id) {

          if (data.error) {
            $('.'+tun+'_availability')
            .css({'background-image': 'url(../pics/lullacons/alert.png)'});

            alert(data.error_msg);
            return;
          }

          if (data.saldo[tun] < 0) {
            $('.'+tun+'_availability')
            .css({'background-image': 'url(../pics/lullacons/alert.png)'});
          }
          else {
            $('.'+tun+'_availability')
            .css({
              'background-image': 'none',
              'background-color': bgcolors[data.saldo[tun]]})
            .show();
          }
        }
      }
    );
  });

  $('.tarkista_tehtaan_saldot').on('click', function() {

    $id = $(this).attr('id');
    $otunnus = $('#tilausnumero').val();
    $tuoteno = $('.'+$id+'_tuoteno').val();
    $myytavissa = $(this).siblings('.'+$id+'_myytavissa').val();

    $cust_id = $('.'+$id+'_custid').val();
    $username = $('.'+$id+'_username').val();
    $password = $('.'+$id+'_password').val();
    $suppliernumber = $('.'+$id+'_suppliernumber').val();

    $tt_tunnus = $('.'+$id+'_tt_tunnus').val();

    $toim = $('#toim').val();

    $('.'+$id+'_'+$myytavissa+'_loading').html("<img class='"+$id+"_"+$myytavissa+"_image' src='../pics/loading_blue_small.gif' />");

    $.post('',
      {
      id: $id,
      otunnus: $otunnus,
      tuoteno: $tuoteno,
      myytavissa: $myytavissa,
      cust_id: $cust_id,
      username: $username,
      password: $password,
      suppliernumber: $suppliernumber,
      tt_tunnus: $tt_tunnus,
      toim: $toim,
      ajax_toiminto: 'tarkista_tehtaan_saldot',
      no_head: 'yes',
      ohje: 'off'
      },
      function(return_value) {
        var data = jQuery.parseJSON(return_value);

        $('.'+data.id+'_'+$myytavissa+'_image').remove();

        if (data.error) {
          $('.'+data.id+'_'+$myytavissa+'_availability')
          .css({'background-image': 'url(../pics/lullacons/alert.png)'});

          alert(data.error_msg);
          return;
        }

        if (data.saldo < 0) {
          $('.'+data.id+'_'+$myytavissa+'_availability')
          .css({'background-image': 'url(../pics/lullacons/alert.png)'});
        }
        else {
          $('.'+data.id+'_'+$myytavissa+'_availability')
          .css({
            'background-image': 'none',
            'background-color': bgcolors[data.saldo]})
          .show();
        }

        $('.'+data.id+'_'+$myytavissa+'_tehdas_saldo_paivitetty').html(data.tehdas_saldo_paivitetty);
      }
    );
  });
}
