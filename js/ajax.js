$(document).ready(function() {
  $('.palaute_lisaa').unbind('submit');
});

function bind_palaute_painikkeet(e, formi) {
  e.preventDefault();
  var formi = $(formi);
  var kysymys = $(formi).attr("data");
  var painiketeksti = $(formi).attr("data-text");

  formi.find("input[type=submit]").removeClass("lisaa_btn");
  if (!confirm(kysymys) || formi.hasClass('lahetetty')) {
    formi.find("input[type=submit]").attr("value", painiketeksti);
    return false;
  }

  var palaute_lisaa = $(formi).serialize();
  var palaute_lisaa_url = $(formi).attr("action");
  $.ajax
    ({
      type: "POST",
      url: palaute_lisaa_url,
      data: palaute_lisaa,
      success: function (html) {
        html = $("<div/>").html(html).text();
        formi.find("input[type=submit]").attr("value", html).css("background-color", "#073759").css("width", "auto").attr("disabled", "disabled").css("pointer-events", "none");
        formi.addClass("lahetetty");
      }
    });
  return false;
}
