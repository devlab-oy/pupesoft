(function($) {
  var LaitePuuPlugin = function(element) {
    var element = $(element);
    var obj = this;

    this.bind_kohde_tr_click = function() {
      var kohde_tr = $(element).find('.kohde_tr');
      $(kohde_tr).live('click', function(event) {
        if (event.target.nodeName === 'TD' || event.target.nodeName === 'IMG') {
          var paikat = '.paikat_' + $(this).find('.kohde_tunnus').val();

          if ($(this).hasClass('hidden')) {
            //TODO yhteinäistä bind_tr_click logiikka. ei yhdistä vaan _yhtenäistä_
            $(this).parent().find(paikat).removeClass('paikka_tr_hidden');
            $(this).parent().find(paikat).addClass('paikka_tr_not_hidden');

            $(this).addClass('not_hidden');
            $(this).removeClass('hidden');

            $(this).find('.porautumis_img').attr('src', $('#right_arrow').val());
          }
          else {
            $(this).parent().find(paikat).removeClass('paikka_tr_not_hidden');
            $(this).parent().find(paikat).addClass('paikka_tr_hidden');

            $(this).addClass('hidden');
            $(this).removeClass('not_hidden');

            $(this).find('.porautumis_img').attr('src', $('#down_arrow').val());
          }
        }


      });
    };

    this.bind_paikka_tr_click = function() {
      var paikat_tr = $(element).find('.paikat_tr');
      $(paikat_tr).bind('click', function(event) {
        //paikat_tr sisällä on buttoni uuden laitteen luomiseen paikalle. jos sitä klikataan, emme halua triggeröidä tätä.
        if (event.target.nodeName === 'TD' || event.target.nodeName === 'IMG') {
          if ($(this).children().find('.laitteet_table_hidden').length > 0) {
            $(this).children().find('.laitteet_table_hidden').addClass('laitteet_table_not_hidden');
            $(this).children().find('.laitteet_table_hidden').removeClass('laitteet_table_hidden');

            $(this).find('.porautumis_img').attr('src', $('#right_arrow').val());
          }
          else {
            $(this).children().find('.laitteet_table_not_hidden').addClass('laitteet_table_hidden');
            $(this).children().find('.laitteet_table_hidden').removeClass('laitteet_table_not_hidden');

            $(this).find('.porautumis_img').attr('src', $('#down_arrow').val());
          }
        }

      });
    };

    this.bind_poista_kohde_button = function() {
      var poista_kohde = $(element).find('.poista_kohde');
      $(poista_kohde).click(function() {
        var ok = confirm($('#oletko_varma_confirm_message').val());
        if (ok) {
          var $button = $(this);
          var kohde_tunnus = $button.parent().find('.kohde_tunnus').val();
          $.ajax({
            async: true,
            type: 'GET',
            url: 'yllapito.php?toim=kohde&del=3&del_relaatiot=1&tunnus=' + kohde_tunnus
          }).done(function() {
            if (console && console.log) {
              console.log('Kohteen poisto onnistui');
            }
            //poistetaan kohde_tr:n paikka_tr:t
            $button.parent().parent().parent().find('.paikat_' + kohde_tunnus).remove();

            //poistetaan itse kohde_tr
            $button.parent().parent().remove();

          }).fail(function() {
            if (console && console.log) {
              console.log('Kohteen poisto EPÄONNISTUI');
            }
            alert($('#poisto_epaonnistui_message').val());
          });
        }
      });
    };

    this.bind_poista_paikka_button = function() {
      var poista_paikka = $(element).find('.poista_paikka');
      $(poista_paikka).click(function() {
        var ok = confirm($('#oletko_varma_confirm_message').val());
        if (ok) {
          var $button = $(this);
          var paikka_tunnus = $button.parent().parent().find('.paikka_tunnus').val();
          $.ajax({
            async: true,
            type: 'GET',
            url: 'yllapito.php?toim=paikka&del=3&del_relaatiot=1&tunnus=' + paikka_tunnus
          }).done(function() {
            if (console && console.log) {
              console.log('Paikan poisto onnistui');
            }
            $button.parent().parent().remove();

          }).fail(function() {
            if (console && console.log) {
              console.log('Paikan poisto EPÄONNISTUI');
            }
            alert($('#poisto_epaonnistui_message').val());
          });
        }
      });
    };

    this.bind_poista_laite_button = function() {
      var poista_laite = $(element).find('.poista_laite');
      $(poista_laite).click(function() {
        var ok = confirm($('#oletko_varma_confirm_message').val());
        if (ok) {
          var $button = $(this);
          var laite_tunnus = $button.parent().find('.laite_tunnus').val();
          $.ajax({
            async: true,
            type: 'GET',
            url: 'yllapito.php?toim=laite&del=3&del_relaatiot=1&tunnus=' + laite_tunnus
          }).done(function() {
            if (console && console.log) {
              console.log('Laitteen poisto onnistui');
            }

            $button.parent().parent().find('.tila').html($('#poistettu_message').val());

          }).fail(function() {
            if (console && console.log) {
              console.log('Laitteen poisto EPÄONNISTUI');
            }
            alert($('#poisto_epaonnistui_message').val());
          });
        }
      });
    };

    this.bind_aineisto_submit_button_click = function() {
      var aineisto_tallennus_submit = $(element).find('#aineisto_tallennus_submit');
      $(aineisto_tallennus_submit).click(function() {
        $(this).parent().parent().parent().parent().toggle();
        $('#progressbar').toggle();
      });
    };

  };

  $.fn.laitePuuPlugin = function() {
    return this.each(function() {
      var element = $(this);

      if (element.data('laitePuuPlugin')) {
        return;
      }

      var laitePuuPlugin = new LaitePuuPlugin(this);

      element.data('laitePuuPlugin', laitePuuPlugin);
    });
  };

})(jQuery);
