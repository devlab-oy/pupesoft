(function($) {
  var TyojonoPlugin = function(element) {
    var element = $(element);
    var obj = this;

    this.bind_kohde_tr_click = function() {
      var kohde_tr = $(element).find('.kohde_tr, .kohde_tr_hidden');
      var suunta = null;
      $(kohde_tr).click(function(event) {
        if (event.target.nodeName !== 'INPUT' && event.target.nodeName !== 'SELECT') {
          var $laite_table_tr = $(this).next();
          if ($(this).hasClass('kohde_tr_hidden')) {
            $(this).addClass('kohde_tr');
            $(this).removeClass('kohde_tr_hidden');

            $laite_table_tr.addClass('laite_table_tr');
            $laite_table_tr.removeClass('laite_table_tr_hidden');
            suunta = 'in';
          }
          else {
            $(this).removeClass('kohde_tr');
            $(this).addClass('kohde_tr_hidden');

            $laite_table_tr.removeClass('laite_table_tr');
            $laite_table_tr.addClass('laite_table_tr_hidden');
            suunta = 'out';
          }

          kohteen_avaaminen_sessioon($(this).find('input.lasku_tunnukset').val(), suunta);
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

    this.bind_merkkaa_tehdyksi = function() {
      $('.merkkaa_tehdyksi').on('click', function() {
        var ok = tarkista();
        if (ok) {
          var $tr = $(this).parent().parent();
          $tr.find('input[name="ala_tee"]').val('merkkaa_tehdyksi');
          $tr.find('.tyomaarayshallinta').submit();
        }
      });
    };

    this.bind_muuta_paivamaaraa = function() {
      $('.muuta').on('click', function() {
        var ok = tarkista();
        if (ok) {
          var $tr = $(this).parent().parent();
          $tr.find('input[name="ala_tee"]').val('muuta');
          $tr.find('.tyomaarayshallinta').submit();
        }
      });
    };

    this.bind_poista = function() {
      $('.poista').on('click', function() {
        var ok = tarkista();
        if (ok) {
          var $tr = $(this).parent().parent();
          $tr.find('input[name="ala_tee"]').val('poista');
          $tr.find('.tyomaarayshallinta').submit();
        }
      });
    };

    this.bind_positio_keksiin = function() {
      $('.merkkaa_tehdyksi, .muuta, .poista, .laitteen_vaihto, .muu, .kateissa, .laite_link').on('click', function() {
        positio_keksiin();
      });
    };

    this.autoscroll = function() {
      var y = get_cookie('tyojono_positio');

      if (y > 0) {
        window.scrollTo(0,y);

        set_cookie('tyojono_positio', '', -1);
      }
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

    var kohteen_avaaminen_sessioon = function(lasku_tunnukset, suunta) {
      var tunnukset = lasku_tunnukset;
      if (typeof tunnukset == 'string' || tunnukset instanceof String) {
        tunnukset = tunnukset.split(',');
      }

      return $.ajax({
        async: true,
        dataType: 'json',
        type: 'POST',
        data: {
          lasku_tunnukset: tunnukset,
          suunta: suunta
        },
        url: 'tyojono2.php?ajax_request=true&no_head=yes&action=kohteen_avaaminen_sessioon'
      }).done(function(data) {
        if (console && console.log) {
          console.log('Päivitys onnistui');
          console.log(data);
        }
      });
    };

    var positio_keksiin = function() {
      var y = 0;

      y = window.pageYOffset;

      if (y === 0) {
        return false;
      }

      set_cookie('tyojono_positio', y, 14);
    };

    var set_cookie = function(cname, cvalue, exdays) {
      var d = new Date();
      d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
      var expires = "expires=" + d.toGMTString();
      document.cookie = cname + "=" + cvalue + "; " + expires;
    };

    var get_cookie = function(cname) {
      var name = cname + "=";
      var ca = document.cookie.split(';');
      for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
          c = c.substring(1);
        }
        if (c.indexOf(name) != -1) {
          return c.substring(name.length, c.length);
        }
      }
      return "";
    };

    var tarkista = function() {
      return confirm($('#oletko_varma').val());
    };

    this.bind_all_events = function() {
      this.bind_kohde_tr_click();
      this.bind_select_kaikki_checkbox();
      this.bind_laite_checkbox();
      this.bind_aineisto_submit_button_click();
      this.bind_tyojono_muutos_lasku_change();
      this.bind_tyojono_muutos_kohde_change();
      this.bind_merkkaa_tehdyksi();
      this.bind_muuta_paivamaaraa();
      this.bind_poista();
      this.bind_positio_keksiin();
      this.autoscroll();
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
