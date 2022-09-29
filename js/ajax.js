$(document).ready(function() {
  bind_palaute_painikkeet();
});

function bind_palaute_painikkeet() {
  $("form.palaute_lisaa").submit(function(e) {
    e.preventDefault();
    var formi = $(this);
    var kysymys = $(this).attr("data");
    var painiketeksti = $(this).attr("data-text");

    formi.find("input[type=submit]").removeClass("lisaa_btn");
    if(!confirm(kysymys) || formi.hasClass('lahetetty')) {
      formi.find("input[type=submit]").attr("value", painiketeksti);
      return false;
    }
    
    var palaute_lisaa = $(this).serialize();
    var palaute_lisaa_url = $(this).attr("action");
    $.ajax
    ({
      type: "POST",
      url: palaute_lisaa_url,
      data: palaute_lisaa,
      success: function (html) {
        var palaute = $("<div>", {id: "newDiv1", name: 'test', class: "aClass"});
        palaute.html(html);
        var vastaus = palaute.find(".vastaus").text();
        formi.find("input[type=submit]").attr("value", vastaus).css("background-color", "#073759").css("width", "auto").attr("disabled", "disabled").css("pointer-events", "none");
        formi.addClass("lahetetty");
      }
    });
  });
}
