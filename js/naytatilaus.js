$(function() {
  "use strict";

  $(".nayta_asiakkaan_kuitti").on("click", function(e) {
    e.preventDefault();
    $(this).next().toggle();
  });

  $(".nayta_kauppiaan_kuitti").on("click", function(e) {
    e.preventDefault();
    $(this).next().toggle();
  });
});
