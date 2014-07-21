(function($) {
  var TyojonoPlugin = function(element) {
    var element = $(element);
    var obj = this;

    this.bind_kohde_tr_click = function() {
      var kohde_tr = $(element).find('.kohde_tr, .kohde_tr_hidden');
      $(kohde_tr).click(function(event) {
        if (event.target.nodeName !== 'INPUT' && event.target.nodeName !== 'SELECT') {
          var laite_table_tr = $(this).next();
          if ($(this).hasClass('kohde_tr_hidden')) {
            $(this).addClass('kohde_tr');
            $(this).removeClass('kohde_tr_hidden');

            $(laite_table_tr).addClass('laite_table_tr');
            $(laite_table_tr).removeClass('laite_table_tr_hidden');
          }
          else {
            $(this).removeClass('kohde_tr');
            $(this).addClass('kohde_tr_hidden');

            $(laite_table_tr).removeClass('laite_table_tr');
            $(laite_table_tr).addClass('laite_table_tr_hidden');
          }
        }
      });
    };

    this.bind_select_kaikki_checkbox = function() {
      var select_kaikki = $(element).find('.select_kaikki');
      $(select_kaikki).click(function() {
        if ($(this).is(':checked')) {
          $(this).parent().parent().parent().find('.laite_checkbox').attr('checked', 'checked');

          $(this).parent().parent().parent().find('.lasku_tunnus').attr('name', 'lasku_tunnukset[]');
        }
        else {
          $(this).parent().parent().parent().find('.laite_checkbox').removeAttr('checked');

          //otetaan riveiltä laskutunnukset name atribuutti pois ettei lähde requestin mukana
          $(this).parent().parent().parent().find('.lasku_tunnus').removeAttr('name');
        }
      });
    };

    this.bind_laite_checkbox = function() {
      var laite_checkbox = $(element).find('.laite_checkbox');
      $(laite_checkbox).click(function() {
        if ($(this).is(':checked')) {
          $(this).attr('checked', 'checked');

          $(this).parent().find('.lasku_tunnus').attr('name', 'lasku_tunnukset[]');
        }
        else {
          $(this).removeAttr('checked');

          //otetaan riviltä laskutunnukset name atribuutti pois ettei lähde requestin mukana
          $(this).parent().find('.lasku_tunnus').removeAttr('name');
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

    this.bind_tyojono_muutos_lasku_change = function() {
      $('.tyojono_muutos_lasku').on('change', function() {
        var lasku_tunnukset = [];
        lasku_tunnukset.push($(this).parent().parent().parent().find('input.lasku_tunnus').val());

        var tyojono_paivitys_request_obj = paivita_laskujen_tyojonot(lasku_tunnukset, $(this).val());

        tyojono_paivitys_request_obj.success(function() {
          $('#message_box_success').delay(500).fadeIn('normal', function() {
            $(this).delay(2500).fadeOut();
          });
        });
      });
    };

    this.bind_tyojono_muutos_kohde_change = function() {
      $('.tyojono_muutos_kohde').on('change', function() {
        var tyojono = $(this).val();
        var $kohde_tr = $(this).parent().parent();
        var lasku_tunnukset = $kohde_tr.find('input.lasku_tunnukset').val().split(',');

        var tyojono_paivitys_request_obj = paivita_laskujen_tyojonot(lasku_tunnukset, tyojono);

        tyojono_paivitys_request_obj.success(function() {
          $('#message_box_success').delay(500).fadeIn('normal', function() {
            $(this).delay(2500).fadeOut();
          });

          //Päivitetään myös "childien työjonot"
          var lasku_selectit = $kohde_tr.next().find('select.tyojono_muutos_lasku');
          lasku_selectit.each(function(index, select) {
            $(select).val(tyojono);
          });
        });

        tyojono_paivitys_request_obj.fail(function() {
          $('#message_box_fail').delay(500).fadeIn('normal', function() {
            $(this).delay(2500).fadeOut();
          });
        });
      });
    };

    var paivita_laskujen_tyojonot = function(lasku_tunnukset, tyojono) {
      return $.ajax({
        async: true,
        dataType: 'json',
        type: 'POST',
        data: {
          lasku_tunnukset: lasku_tunnukset,
          tyojono: tyojono
        },
        url: 'tyojono2.php?ajax_request=true&action=paivita_tyomaaraysten_tyojonot&no_head=yes'
      }).done(function(data) {
        if (console && console.log) {
          console.log('Päivitys onnistui');
          console.log(data);
        }
      });
    };

    this.bind_all_events = function() {
      this.bind_kohde_tr_click();
      this.bind_select_kaikki_checkbox();
      this.bind_laite_checkbox();
      this.bind_aineisto_submit_button_click();
      this.bind_tyojono_muutos_lasku_change();
      this.bind_tyojono_muutos_kohde_change();
    };

  };

  $.fn.tyojonoPlugin = function() {
    return this.each(function() {
      var element = $(this);

      if (element.data('tyojonoPlugin')) {
        return;
      }

      var tyojonoPlugin = new TyojonoPlugin(this);

      element.data('tyojonoPlugin', tyojonoPlugin);
    });
  };

})(jQuery);
