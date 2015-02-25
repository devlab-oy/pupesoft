$(function() {
  $('#naytaValinnat').on('click', function() {
    $('#valinnat').toggle();
    $(this).hide();
  });

  $('#tallennaNappi').on('click', function() {
    $('#myymyyjatTee').val('tallenna_haku');
    this.form.submit();
  });
});
