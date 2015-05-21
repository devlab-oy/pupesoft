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

  $(".hae_asiakkaan_kuitti").on("click", function(e) {
    e.preventDefault();

    $(this).next().submit();
  });

  $(".hae_kauppiaan_kuitti").on("click", function(e) {
    e.preventDefault();

    $(this).next().submit();
  });
});
