$(document).ready(function() {
	bind_uusi_ehto_button();
	bind_uusi_aliehto_button();

	bind_poista_ehto_button();
	bind_poista_aliehto_button();

	bind_uusi_palkinto_button();
	bind_poista_palkinto_button();

	if ($('#ehdot:has(div)').length === 0) {
		$('#uusi_ehto').click();
	}

	if ($('#palkinto_table tbody tr').length <= 1) {
		$('#uusi_palkinto').click();
	}

	bind_ehto_kohde_change();
	bind_aliehto_kohde_change();

	bind_kampanja_form_submit();
});

function bind_ehto_kohde_change() {
	$('.ehto_kohde').live('change', function() {
		var ehto_rivi = $(this).parent().parent();

		ehto_rivi.find('.kohteen_arvo_selite').html('');

		hae_arvo_input(ehto_rivi);

		if ($(this).val() === 'tuote'
				|| $(this).val() === 'tuotekategoria'
				|| $(this).val() === 'tuoteosasto'
				|| $(this).val() === 'tuoteryhma') {

			if ($(this).parent().parent().find('.uusi_aliehto').css('display') === 'none') {
				$(this).parent().parent().find('.uusi_aliehto').show();
			}
			var aliehto_rivit = $(this).parent().parent().find('.aliehto_rivi');
			filteroi_aliehdon_kohteet(aliehto_rivit);
		}
		else {
			//piilotetaan uusi aliehto-nappi
			$(this).parent().parent().find('.uusi_aliehto').hide();

			//poistetaan aliehdot jos niitä on

			poista_aliehto_rivit($(this).parent().parent());
		}
	});
}

function poista_aliehto_rivit($ehto_rivi) {
	var aliehto_rivi;
	while ($aliehto_rivi = $ehto_rivi.next()) {
		if ($aliehto_rivi.hasClass('aliehto_rivi')) {
			$aliehto_rivi.remove();
		}
		else {
			break;
		}
	}
}

function filteroi_aliehdon_kohteet($aliehto_rivit) {
	//haetaan jokainen aliehdon kohde dropdown ja looptaan ne läpi
	if ($aliehto_rivit.length > 0) {
		$aliehto_rivit.each(function(index, aliehto_rivi) {
			//poistetaan kaikki muut paitsi arvo ja kappaleet
			$(aliehto_rivi).find('.aliehto_kohde option').filter(function(index2, value) {
				return $(value).val() === 'asiakas'
						|| $(value).val() === 'asiakas_ytunnus'
						|| $(value).val() === 'asiakaskategoria'
						|| $(value).val() === 'tuote'
						|| $(value).val() === 'tuotekategoria'
						|| $(value).val() === 'tuoteosasto'
						|| $(value).val() === 'tuoteryhma';
			}).remove();

		});
	}
}

function hae_arvo_input(rivi) {
	var palkinto_rivi = false;
	var rivi_kohde, ehto_rivi_id, aliehto_rivi_id, arvo_class, rajoitin_class;
	if ($(rivi).hasClass('ehto_rivi')) {
		rivi_kohde = $(rivi).find('.ehto_kohde').val();
		ehto_rivi_id = $(rivi).find('.ehto_id').val();
		aliehto_rivi_id = undefined;
		arvo_class = '.ehto_arvo';
		rajoitin_class = '.ehto_rajoitin';
	}
	else if ($(rivi).hasClass('aliehto_rivi')) {
		rivi_kohde = $(rivi).find('.aliehto_kohde').val();
		ehto_rivi_id = aliehto_hae_ehto_id(rivi);
		aliehto_rivi_id = $(rivi).find('.aliehto_id').val();
		arvo_class = '.aliehto_arvo';
		rajoitin_class = '.aliehto_rajoitin';
	}
	else if ($(rivi).hasClass('palkinto_rivi')) {
		rivi_kohde = 'tuote';
		ehto_rivi_id = $(rivi).find('.palkinto_rivi_id').val();
		aliehto_rivi_id = undefined;
		palkinto_rivi = true;
		arvo_class = '.palkinto_rivi_nimi';
		rajoitin_class = 'td:first';
	}

	$.ajax({
		async: true,
		type: 'GET',
		data: {
			ajax_request: 1,
			no_head: 'yes',
			rivi_kohde: rivi_kohde,
			ehto_rivi_id: ehto_rivi_id,
			aliehto_rivi_id: aliehto_rivi_id,
			palkinto_rivi: palkinto_rivi
		},
		url: 'kampanja.php'
	}).done(function(data) {
		if (console && console.log) {
			console.log('Input kentän haku onnistui');
			//console.log(data);
		}
		if (data.length !== 0) {
			$(rivi).find(arvo_class).remove();
			$(rivi).find('.liveSearch').remove();
			if ($(rivi).hasClass('palkinto_rivi')) {
				$(rivi).find(rajoitin_class).append(data);
			}
			else {
				$(rivi).find(rajoitin_class).parent().next().append(data);
			}
		}
	});
}

function aliehto_hae_ehto_id($aliehto_rivi) {
	var $ehto_rivi;
	while ($ehto_rivi = $aliehto_rivi.prev()) {
		if ($ehto_rivi.hasClass('ehto_rivi')) {
			return $ehto_rivi.find('.ehto_id').val();
		}
		$aliehto_rivi = $ehto_rivi;
	}
}

function bind_uusi_ehto_button() {
	$('#uusi_ehto').click(function(event) {
		event.preventDefault();

		var id = generoi_ehto_id();

		populoi_ehto_rivi(id);
	});
}

function populoi_ehto_rivi(ehto_id) {
	var ehto_rivi = $('#ehto_rivi_template').clone();

	$(ehto_rivi).css('display', '');
	$(ehto_rivi).removeAttr('id');
	$(ehto_rivi).attr('class', 'ehto_rivi');

	$(ehto_rivi).find('.ehto_kohde').attr('name', 'kampanja_ehdot[' + ehto_id + '][kohde]');
	$(ehto_rivi).find('.ehto_rajoitin').attr('name', 'kampanja_ehdot[' + ehto_id + '][rajoitin]');

	$(ehto_rivi).find('.ehto_id_template').attr('class', 'ehto_id');
	$(ehto_rivi).find('.ehto_id_template').removeClass('ehto_id_template');

	$(ehto_rivi).find('.ehto_id').val(ehto_id);

	$('#ehdot').append(ehto_rivi);

	if ($(ehto_rivi).find('.ehto_kohde').val() === 'tuote'
			|| $(ehto_rivi).find('.ehto_kohde').val() === 'tuotekategoria'
			|| $(ehto_rivi).find('.ehto_kohde').val() === 'tuoteosasto'
			|| $(ehto_rivi).find('.ehto_kohde').val() === 'tuoteryhma') {
		filteroi_aliehdon_kohteet($(ehto_rivi).parent().find('.aliehto_rivi'));
	}
	else {
		//piilotetaan uusi aliehto-nappi
		$(ehto_rivi).find('.uusi_aliehto').hide();
	}

	hae_arvo_input(ehto_rivi);
}

function generoi_ehto_id() {
	var ehto_rivit = $('.ehto_rivi');

	var max = 0;
	$(ehto_rivit).each(function(index, ehto_rivi) {
		if (max < $(ehto_rivi).find('.ehto_id').val()) {
			max = $(ehto_rivi).find('.ehto_id').val();
		}
	});

	max++;

	return max;
}

function bind_aliehto_kohde_change() {
	$('.aliehto_kohde').live('change', function() {
		var aliehto_rivi = $(this).parent();
		hae_arvo_input(aliehto_rivi);
	});
}

function bind_uusi_aliehto_button() {
	//Käytetään liveä, koska kyseessä on dynaaminen elementti.
	$('.uusi_aliehto').live('click', function(event) {
		event.preventDefault();

		//parametrinä tr.ehto_rivi
		var aliehto_id = generoi_aliehto_id($(this).parent().parent());
		//parametrinä tr.ehto_rivi
		populoi_aliehto_rivi($(this).parent().parent(), aliehto_id);
	});
}

function populoi_aliehto_rivi(ehto_rivi, aliehto_id) {
	var aliehto_rivi = $('#aliehto_rivi_template').clone();
	var ehto_id = $(ehto_rivi).find('.ehto_id').val();

	$(aliehto_rivi).css('display', '');
	$(aliehto_rivi).removeAttr('id');
	$(aliehto_rivi).attr('class', 'aliehto_rivi');

	$(aliehto_rivi).find('.aliehto_kohde').attr('name', 'kampanja_ehdot[' + ehto_id + '][aliehdot][' + aliehto_id + '][kohde]');
	$(aliehto_rivi).find('.aliehto_rajoitin').attr('name', 'kampanja_ehdot[' + ehto_id + '][aliehdot][' + aliehto_id + '][rajoitin]');


	$(aliehto_rivi).find('.aliehto_id_template').attr('class', 'aliehto_id');
	$(aliehto_rivi).find('.aliehto_id_template').removeClass('aliehto_id_template');

	$(aliehto_rivi).find('.aliehto_id').val(aliehto_id);

	var ehto_kohde_value = $(ehto_rivi).find('.ehto_kohde').val();
	if (ehto_kohde_value === 'tuote' || ehto_kohde_value === 'tuotekategoria' || ehto_kohde_value === 'tuoteosasto' || ehto_kohde_value === 'tuoteryhma') {
		filteroi_aliehdon_kohteet(aliehto_rivi);
	}

	$(ehto_rivi).after(aliehto_rivi);

	hae_arvo_input(aliehto_rivi);
}

function generoi_aliehto_id($ehto_rivi) {
	var max = 0;
	var aliehto_rivi;
	while ($aliehto_rivi = $ehto_rivi.next()) {
		if ($aliehto_rivi.hasClass('aliehto_rivi')) {
			if (max < $aliehto_rivi.find('.aliehto_id').val()) {
				max = $aliehto_rivi.find('.aliehto_id').val();
			}

			$ehto_rivi = $aliehto_rivi;
		}
		else {
			break;
		}
	}

	max++;

	return max;
}

function bind_poista_ehto_button() {
	$('.poista_ehto').live('click', function(event) {
		event.preventDefault();

		$(this).parent().parent().remove();
	});
}

function bind_poista_aliehto_button() {
	$('.poista_aliehto').live('click', function(event) {
		event.preventDefault();

		$(this).parent().parent().remove();
	});
}

function bind_uusi_palkinto_button() {
	$('#uusi_palkinto').live('click', function(event) {
		event.preventDefault();

		populoi_uusi_palkinto_rivi();
	});
}

function populoi_uusi_palkinto_rivi() {
	var palkinto_rivi = $('#palkinto_rivi_template').clone();

	var palkinto_rivi_id = generoi_palkinto_rivi_id();

	$(palkinto_rivi).attr('class', 'palkinto_rivi');
	$(palkinto_rivi).removeAttr('id');

	$(palkinto_rivi).find('.palkinto_rivi_id').val(palkinto_rivi_id);

	hae_arvo_input(palkinto_rivi);
	$(palkinto_rivi).find('.palkinto_rivi_nimi').attr('name', 'palkinto_rivit[' + palkinto_rivi_id + '][tuoteno]');
	$(palkinto_rivi).find('.palkinto_rivi_kpl').attr('name', 'palkinto_rivit[' + palkinto_rivi_id + '][kpl]');

	$('#palkinto_table tbody').append(palkinto_rivi);
}

function generoi_palkinto_rivi_id() {
	var palkinto_rivit = $('.palkinto_rivi');

	var max = 0;
	$(palkinto_rivit).each(function(index, palkinto_rivi) {
		if (max < $(palkinto_rivi).find('.palkinto_rivi_id').val()) {
			max = $(palkinto_rivi).find('.palkinto_rivi_id').val();
		}
	});

	max++;

	return max;
}

function bind_poista_palkinto_button() {
	$('.poista_palkinto').live('click', function(event) {
		event.preventDefault();

		$(this).parent().parent().remove();
	});
}

function bind_kampanja_form_submit() {
	$('form[name=kampanja_form]').submit(function() {
		return tarkista();
	});
}

function tarkista() {
	var ok = true;
	if ($('#kampanja_nimi').val() === '') {
		alert($('#nimi_tyhja_message').val());
		ok = false;
	}

	$('#ehdot .ehto_arvo').each(function(index, ehto_input) {
		if ($(ehto_input).val() === '') {
			alert($('#ehto_arvo_tyhja_message').val());
			ok = false;
		}
	});

	$('#ehdot .aliehto_arvo').each(function(index, aliehto_input) {
		if ($(aliehto_input).val() === '') {
			alert($('#aliehto_arvo_tyhja_message').val());
			ok = false;
		}
	});

	if ($('#ehdot .ehto_rivi').length < 1) {
		alert($('#ehto_minimi_message').val());
		ok = false;
	}

	return ok;
}