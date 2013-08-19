(function($) {
	var TyojonoPlugin = function(element) {
		var element = $(element);
		var obj = this;

		this.bind_kohde_tr_click = function() {
			var kohde_tr = $(element).find('.kohde_tr, .kohde_tr_hidden');
			$(kohde_tr).click(function(event) {
				if (console && console.log) {
					console.log(event.target.nodeName);
				}
				if (event.target.nodeName !== 'INPUT') {
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