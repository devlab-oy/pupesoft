<?php

require ("../inc/parametrit.inc");

if (isset($livesearch_tee) and $livesearch_tee == "ASIAKASHAKU") {
	livesearch_asiakashaku();
	exit;
}

if (isset($livesearch_tee) and $livesearch_tee == "TUOTEHAKU") {
	livesearch_tuotehaku();
	exit;
}

if ($ajax_request) {
	if ($rivi_kohde == 'asiakas') {
		if (isset($alisaanto_rivi_id)) {
			//function livesearch_kentta($formi, $tee = 'TUOTEHAKU', $nimi = 'liveseach_hakukentta', $width = '300', $value = '', $submit = '', $vero_field = '', $class = '') {
			$return = livesearch_kentta("maksaja", "ASIAKASHAKU", "kampanjarivit[{$saanto_rivi_id}][alisaanto_rivit][{$alisaanto_rivi_id}][arvo]", 140, '', '', '', '', '');
		}
		else {
			$return = livesearch_kentta("maksaja", "ASIAKASHAKU", "kampanjarivit[{$saanto_rivi_id}][arvo]", 140);
		}

		echo $return;
	}
	else if ($rivi_kohde == 'tuote') {
		if (isset($alisaanto_rivi_id)) {
			$return = livesearch_kentta("maksaja", "TUOTEHAKU", "kampanjarivit[{$saanto_rivi_id}][alisaanto_rivit][{$alisaanto_rivi_id}][arvo]", 140);
		}
		else {
			$return = livesearch_kentta("maksaja", "TUOTEHAKU", "kampanjarivit[{$saanto_rivi_id}][arvo]", 140);
		}
		echo $return;
	}
	exit;
}

enable_ajax();

echo "<font class='head'>".t("Kampanja")."</font><hr>";
?>
<style>
	#saanto_rivi_template {
		display: none;
	}
	#alisaanto_rivi_template {
		display: none;
	}

	#palkinto_table_template {
		display: none;
	}
</style>
<script>

	$(document).ready(function() {
		bind_uusi_saanto_button();
		bind_uusi_alisaanto_button();

		bind_poista_saanto_button();
		bind_poista_alisaanto_button();

		bind_uusi_palkinto_button();
		bind_poista_palkinto_button();

		$('#uusi_saanto').click();
		$('#uusi_palkinto').click();

		bind_saanto_kohde_change();
		bind_alisaanto_kohde_change();
	});

	function bind_saanto_kohde_change() {
		$('.saanto_kohde').live('change', function() {
			var saanto_rivi = $(this).parent();
			hae_saannon_arvo_input(saanto_rivi);
		});
	}

	function hae_saannon_arvo_input(saanto_rivi) {
		$.ajax({
			async: true,
			type: 'GET',
			data: {
				ajax_request: 1,
				no_head: 'yes',
				rivi_kohde: $(saanto_rivi).find('.saanto_kohde').val(),
				saanto_rivi_id: $(saanto_rivi).find('.saanto_id').val()
			},
			url: 'kampanja.php'
		}).done(function(data) {
			if (console && console.log) {
				console.log('Input kentän haku onnistui');
				//console.log(data);
			}
			if (data.length !== 0) {
				$(saanto_rivi).find('.saanto_arvo').after(data);
				$(saanto_rivi).find('.saanto_arvo').remove();

				//päivitetään ajaxista tulleelle inputille classi
				$(saanto_rivi).find('input[name*="arvo"]').attr('class', 'saanto_arvo');
			}
		});
	}

	function bind_uusi_saanto_button() {
		$('#uusi_saanto').click(function(event) {
			event.preventDefault();

			var id = generoi_saanto_id();

			populoi_saanto_rivi(id);
		});
	}

	function populoi_saanto_rivi(saanto_id) {
		var saanto_rivi = $('#saanto_rivi_template').clone();

		$(saanto_rivi).css('display', 'block');
		$(saanto_rivi).removeAttr('id');
		$(saanto_rivi).attr('class', 'saanto_rivi');

		$(saanto_rivi).find('.saanto_kohde').attr('name', 'saanto_rivit[' + saanto_id + '][kohde]');
		$(saanto_rivi).find('.saanto_rajoitin').attr('name', 'saanto_rivit[' + saanto_id + '][rajoitin]');


		$(saanto_rivi).find('.saanto_id_template').attr('class', 'saanto_id');
		$(saanto_rivi).find('.saanto_id_template').removeClass('saanto_id_template');

		$(saanto_rivi).find('.saanto_id').val(saanto_id);

		$('#saannot').append(saanto_rivi);

		hae_saannon_arvo_input(saanto_rivi);
	}

	function generoi_saanto_id() {
		var saanto_rivit = $('.saanto_rivi');

		var max = 0;
		$(saanto_rivit).each(function(index, saanto_rivi) {
			if (max < $(saanto_rivi).find('.saanto_id').val()) {
				max = $(saanto_rivi).find('.saanto_id').val();
			}
		});

		max++;

		return max;
	}

	function bind_alisaanto_kohde_change() {
		$('.alisaanto_kohde').live('change', function() {
			var alisaanto_rivi = $(this).parent();
			hae_alikohteen_arvo_input(alisaanto_rivi);
		});
	}

	function hae_alikohteen_arvo_input(alisaanto_rivi) {
		$.ajax({
			async: true,
			type: 'GET',
			data: {
				ajax_request: 1,
				no_head: 'yes',
				rivi_kohde: $(alisaanto_rivi).find('.alisaanto_kohde').val(),
				saanto_rivi_id: $(alisaanto_rivi).parent().find('.saanto_id').val(),
				alisaanto_rivi_id: $(alisaanto_rivi).find('.alisaanto_id').val()
			},
			url: 'kampanja.php'
		}).done(function(data) {
			if (console && console.log) {
				console.log('Input kentän haku onnistui');
				//console.log(data);
			}
			if (data.length !== 0) {
				$(alisaanto_rivi).find('.alisaanto_arvo').after(data);
				$(alisaanto_rivi).find('.alisaanto_arvo').remove();
			}
		});
	}

	function bind_uusi_alisaanto_button() {
		//Käytetään liveä, koska kyseessä on dynaaminen elementti.
		$('.uusi_alisaanto').live('click', function(event) {
			event.preventDefault();

			//parametrinä div.alisaannot
			var alisaanto_id = generoi_alisaanto_id($(this).parent().find('.alisaannot'));
			//parametrinä div.saanto_rivi
			populoi_alisaanto_rivi($(this).parent(), alisaanto_id);
		});
	}

	function populoi_alisaanto_rivi(saanto_rivi, alisaanto_id) {
		var alisaanto_rivi = $('#alisaanto_rivi_template').clone();
		var saanto_id = $(saanto_rivi).find('.saanto_id').val();

		$(alisaanto_rivi).css('display', 'block');
		$(alisaanto_rivi).removeAttr('id');
		$(alisaanto_rivi).attr('class', 'alisaanto_rivi');

		$(alisaanto_rivi).find('.alisaanto_kohde').attr('name', 'saanto_rivit[' + saanto_id + '][alisaanto_rivit][' + alisaanto_id + '][kohde]');
		$(alisaanto_rivi).find('.alisaanto_rajoitin').attr('name', 'saanto_rivit[' + saanto_id + '][alisaanto_rivit][' + alisaanto_id + '][rajoitin]');


		$(alisaanto_rivi).find('.alisaanto_id_template').attr('class', 'alisaanto_id');
		$(alisaanto_rivi).find('.alisaanto_id_template').removeClass('alisaanto_id_template');

		$(alisaanto_rivi).find('.alisaanto_id').val(alisaanto_id);

		$(saanto_rivi).find('.alisaannot').append(alisaanto_rivi);

		hae_alikohteen_arvo_input(alisaanto_rivi);
	}

	function generoi_alisaanto_id(alisaanto_rivit) {
		var max = 0;
		$(alisaanto_rivit).each(function(index, alisaanto_rivi) {
			if (max < $(alisaanto_rivi).find('.alisaanto_id').val()) {
				max = $(alisaanto_rivi).find('.alisaanto_id').val();
			}
		});

		max++;

		return max;
	}

	function bind_poista_saanto_button() {
		$('.poista_saanto').live('click', function(event) {
			event.preventDefault();

			$(this).parent().remove();
		});
	}

	function bind_poista_alisaanto_button() {
		$('.poista_alisaanto').live('click', function(event) {
			event.preventDefault();

			$(this).parent().remove();
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

		hae_palkinto_rivi_input(palkinto_rivi);
		$(palkinto_rivi).find('.palkinto_rivi_nimi').attr('name', 'palkinto_rivit[' + palkinto_rivi_id + '][tuoteno]');
		$(palkinto_rivi).find('.palkinto_rivi_kpl').attr('name', 'palkinto_rivit[' + palkinto_rivi_id + '][kpl]');

		$('#palkinto_table tbody').append(palkinto_rivi);
	}

	function generoi_palkinto_rivi_id() {
		var palkinto_rivit = $('.palkinto_rivi');

		console.log(palkinto_rivit);
		var max = 0;
		$(palkinto_rivit).each(function(index, palkinto_rivi) {
			if (max < $(palkinto_rivi).find('.palkinto_rivi_id').val()) {
				max = $(palkinto_rivi).find('.palkinto_rivi_id').val();
			}
		});

		max++;

		return max;
	}

	function hae_palkinto_rivi_input(palkinto_rivi) {
		$.ajax({
			async: true,
			type: 'GET',
			data: {
				ajax_request: 1,
				no_head: 'yes',
				rivi_kohde: 'tuote',
				saanto_rivi_id: $(palkinto_rivi).find('.palkinto_rivi_id').val()
			},
			url: 'kampanja.php'
		}).done(function(data) {
			if (console && console.log) {
				console.log('Input kentän haku onnistui');
				//console.log(data);
			}
			if (data.length !== 0) {
				$(palkinto_rivi).find('.palkinto_rivi_nimi').after(data);
				$(palkinto_rivi).find('.palkinto_rivi_nimi').remove();
			}
		});
	}

	function bind_poista_palkinto_button() {
		$('.poista_palkinto').live('click', function(event) {
			event.preventDefault();

			$(this).parent().parent().remove();
		});
	}

</script>
<?php

$request = array(
	'tee'			 => $tee,
	'kampanja_nimi'	 => $kampanja_nimi,
	'saanto_rivit'	 => $saanto_rivit,
	'palkinto_rivit' => $palkinto_rivit,
);

if ($request['tee'] == 'uusi_kampanja') {
	luo_uusi_kampanja($request);
}
else {
	echo_kayttoliittyma();
}

function luo_uusi_kampanja($request) {
	global $kukarow, $yhtiorow;
	$mur = $request;
}

function echo_kayttoliittyma() {
	//Templatet on javascriptiä varten, että voidaan kutsua .clone()
	echo_saanto_rivi_template();

	echo_saanto_alirivi_template();

	echo_palkinto_rivi_template();

	echo "<form name='kampanja_form' method='POST' action=''>";
	echo "<input type='hidden' name='tee' value='uusi_kampanja' />";

	echo "<div id='kampanja_header'>";
	echo t("Kampanjan nimi").':';
	echo "<br/>";
	echo "<input type='text' size=50 name='kampanja_nimi' />";
	echo "</div>";

	echo "<br/>";
	echo "<hr/>";
	echo "<br/>";

	echo "<div id='saannot'>";
	echo "<button id='uusi_saanto'>".t("Uusi sääntö")."</button>";
	echo "</div>";

	echo "<br/>";
	echo "<hr/>";
	echo "<br/>";

	echo "<div id='palkinnot'>";
	echo "<button id='uusi_palkinto'>".t("Uusi palkinto")."</button>";

	echo "<table id='palkinto_table'>";
	echo "<tr>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Kpl")."</th>";
	echo "<th></th>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";

	echo "<br/>";

	echo "<input type='submit' value='".t("Luo")."' />";
	echo "</form>";
}

function echo_saanto_rivi_template() {
	echo "<div id='saanto_rivi_template'>";

	echo "<input type='hidden' class='saanto_id_template' />";

	echo "<select class='saanto_kohde'>";
	echo "<option value='asiakas'>".t("Asiakas")."</option>";
	echo "<option value='asiakas_ytunnus'>".t("Asiakas ytunnus")."</option>";
	echo "<option value='asiakaskategoria'>".t("Asiakaskategoria")."</option>";
	echo "<option value='tuote'>".t("Tuote")."</option>";
	echo "<option value='tuotekategoria'>".t("Tuotekategoria")."</option>";
	echo "<option value='kappaleet'>".t("Kappaleet")."</option>";
	echo "<option value='arvo'>".t("Arvo")."</option>";
	echo "</select>";

	echo "<select class='saanto_rajoitin'>";
	echo "<option value='on'>".t("on")."</option>";
	echo "<option value='ei_ole'>".t("Ei ole")."</option>";
	echo "<option value='suurempi_kuin'>".t("on suurempi kuin")."</option>";
	echo "<option value='pienempi_kuin'>".t("on pienempi kuin")."</option>";
	echo "</select>";

	echo "<input type='text' class='saanto_arvo' />";
	
	echo "<button class='uusi_alisaanto'>".t("Uusi alisääntö")."</button>";
	echo "<button class='poista_saanto'>".t("Poista sääntö")."</button>";

	echo "<div class='alisaannot'>";
	echo "</div>";

	echo "</div>";
}

function echo_saanto_alirivi_template() {
	echo "<div id='alisaanto_rivi_template'>";

	echo "<input type='hidden' class='alisaanto_id_template' />";

	echo "AND------>  ";
	echo "<select class='alisaanto_kohde'>";
	echo "<option value='asiakas'>".t("Asiakas")."</option>";
	echo "<option value='asiakas_ytunnus'>".t("Asiakas ytunnus")."</option>";
	echo "<option value='asiakaskategoria'>".t("Asiakaskategoria")."</option>";
	echo "<option value='tuote'>".t("Tuote")."</option>";
	echo "<option value='tuotekategoria'>".t("Tuotekategoria")."</option>";
	echo "<option value='kappaleet'>".t("Kappaleet")."</option>";
	echo "<option value='arvo'>".t("Arvo")."</option>";
	echo "</select>";

	echo "<select class='alisaanto_rajoitin'>";
	echo "<option value='on'>".t("on")."</option>";
	echo "<option value='ei_ole'>".t("Ei ole")."</option>";
	echo "<option value='suurempi_kuin'>".t("on suurempi kuin")."</option>";
	echo "<option value='pienempi_kuin'>".t("on pienempi kuin")."</option>";
	echo "</select>";

	echo "<input type='text' class='alisaanto_arvo' />";

	echo "<button class='poista_alisaanto'>".t("Poista alisääntö")."</button>";

	echo "</div>";
}

function echo_palkinto_rivi_template() {
	echo "<table id='palkinto_table_template'>";
	echo "<tr id='palkinto_rivi_template'>";
	echo "<td>";
	echo "<input type='hidden' class='palkinto_rivi_id' />";
	echo "<input type='text' class='palkinto_rivi_nimi' />";
	echo "</td>";

	echo "<td>";
	echo "<input type='text' class='palkinto_rivi_kpl' />";
	echo "</td>";

	echo "<td>";
	echo "<button class='poista_palkinto'>".t("Poista palkinto")."</button";
	echo "</td>";
	echo "</tr>";

	echo "</table>";
}