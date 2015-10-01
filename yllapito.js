$(function () {
  "use strict";

  $("#mainform").on('submit', function () {
    var valitutOperaattorit = $("#valitut_operaattorit").val();

    $("#verkkolaskuoperaattorit").val(valitutOperaattorit);
  });
});
