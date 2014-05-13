$(document).ready(function() {
  bind_valitut_rivit_checkbox_click();
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
}
