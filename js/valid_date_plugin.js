(function($) {
  var ValidDatePlugin = function(element) {
    var $element = $(element);
    var obj = this;

    this.is_valid = function(date) {
      var currVal = date;
      if (currVal == '') {
        return false;
      }

      var rxDatePattern = /^(\d{1,2})[.-\/|](\d{1,2})[.-\/|](\d{4})$/;
      var dtArray = currVal.match(rxDatePattern);

      if (dtArray == null)
        return false;

      //Checks for dd/mm/yyyy format.
      dtMonth = dtArray[2];
      dtDay = dtArray[1];
      dtYear = dtArray[3];

      if (dtMonth < 1 || dtMonth > 12) {
        return false;
      }
      else if (dtDay < 1 || dtDay > 31) {
        return false;
      }
      else if ((dtMonth == 4 || dtMonth == 6 || dtMonth == 9 || dtMonth == 11) && dtDay == 31) {
        return false;
      }
      else if (dtMonth == 2) {
        var isleap = (dtYear % 4 == 0 && (dtYear % 100 != 0 || dtYear % 400 == 0));
        if (dtDay > 29 || (dtDay == 29 && !isleap)) {
          return false;
        }
      }
      return true;
    };

    this.validate_date = function() {
      $element.on('change input paste', function() {
        var onko_validi = obj.is_valid($(this).val());

        var $error_element = $(this).parent().find('.error_element');
        if ($error_element.length === 0) {
          //@TODO n‰in ei varmaan voida tehd‰. Jos error elementti‰ ei ole setattu niin pit‰isi
          //keksi‰ parempi tapa n‰ytt‰‰ virhe
          $error_element = $(this);
        }

        if (!onko_validi) {
          $error_element.html($('#paiva_ei_validi').val());
        }
        else {
          $error_element.html('');
        }
      });
    };
  };

  $.fn.validDatePlugin = function() {
    return this.each(function() {
      var element = $(this);

      if (element.data('validDatePlugin')) {
        return;
      }

      var validDatePlugin = new ValidDatePlugin(this);

      element.data('validDatePlugin', validDatePlugin);
    });
  };

})(jQuery);
