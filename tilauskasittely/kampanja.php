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
	if ($palkinto_rivi) {
		$return = hae_liveseach_kentta($rivi_kohde, 'palkinto', $saanto_rivi_id);
	}
	else {
		if (isset($alisaanto_rivi_id)) {
			$return = hae_liveseach_kentta($rivi_kohde, 'aliehto', $saanto_rivi_id, $alisaanto_rivi_id);
		}
		else {
			$return = hae_liveseach_kentta($rivi_kohde, 'ehto', $saanto_rivi_id);
		}
	}

	if (!empty($return)) {
		echo $return;
	}
	exit;
}

enable_ajax();

echo "<font class='head'>".t("Kampanjat")."</font><hr>";
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
	.saanto_arvo {
		width: 140px;
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

		if ($('#saannot:has(div)').length === 0) {
			$('#uusi_saanto').click();
		}

		if ($('#palkinto_table tbody tr').length <= 1) {
			$('#uusi_palkinto').click();
		}

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
				console.log(data);
			}
			if (data.length !== 0) {
				$(saanto_rivi).find('.saanto_arvo').remove();
				$(saanto_rivi).find('.liveSearch').remove();
				$(saanto_rivi).find('.saanto_rajoitin').after(data);

				//$(saanto_rivi).find('.break_all_div').remove();
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

		$(saanto_rivi).find('.saanto_kohde').attr('name', 'kampanja_ehdot[' + saanto_id + '][kohde]');
		$(saanto_rivi).find('.saanto_rajoitin').attr('name', 'kampanja_ehdot[' + saanto_id + '][rajoitin]');

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
				saanto_rivi_id: $(alisaanto_rivi).parent().parent().find('.saanto_id').val(),
				alisaanto_rivi_id: $(alisaanto_rivi).find('.alisaanto_id').val()
			},
			url: 'kampanja.php'
		}).done(function(data) {
			if (console && console.log) {
				console.log('Input kentän haku onnistui');
				//console.log(data);
			}
			if (data.length !== 0) {
				$(alisaanto_rivi).find('.alisaanto_arvo').remove();
				$(alisaanto_rivi).find('.liveSearch').remove();
				$(alisaanto_rivi).find('.alisaanto_rajoitin').after(data);

				//$(alisaanto_rivi).find('.break_all_div').remove();
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

		$(alisaanto_rivi).find('.alisaanto_kohde').attr('name', 'kampanja_ehdot[' + saanto_id + '][alisaanto_rivit][' + alisaanto_id + '][kohde]');
		$(alisaanto_rivi).find('.alisaanto_rajoitin').attr('name', 'kampanja_ehdot[' + saanto_id + '][alisaanto_rivit][' + alisaanto_id + '][rajoitin]');


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
				palkinto_rivi: true,
				saanto_rivi_id: $(palkinto_rivi).find('.palkinto_rivi_id').val()
			},
			url: 'kampanja.php'
		}).done(function(data) {
			if (console && console.log) {
				console.log('Input kentän haku onnistui');
				//console.log(data);
			}
			if (data.length !== 0) {
				$(palkinto_rivi).find('.palkinto_rivi_nimi').remove();
				$(palkinto_rivi).find('.liveSearch').remove();
				$(palkinto_rivi).find('td:first').append(data);

				//$(palkinto_rivi).find('.break_all_div').remove();
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
	'kampanja_tunnus'	 => $kampanja_tunnus,
	'tee'				 => $tee,
	'kampanja_nimi'		 => $kampanja_nimi,
	'kampanja_ehdot'	 => $kampanja_ehdot,
	'palkinto_rivit'	 => $palkinto_rivit,
);

if ($request['tee'] == 'uusi_kampanja') {
	//Purkka: Jos requestista tulee kampanjan nimi niin voidaan olettaa että halutaan luoda uusi kampanja
	//TODO make some sense into this
	if (!empty($request['kampanja_nimi'])) {
		luo_uusi_kampanja($request);
		
		$request['tee'] = 'nayta_kampanjat';
	}
	else {
		if (!empty($request['kampanja_tunnus'])) {
			$request['kampanja'] = hae_kampanja($request['kampanja_tunnus']);
		}
		echo_kayttoliittyma($request);
	}
}

if ($request['tee'] == 'muokkaa_kampanjaa') {
	if ($request['kampanja_tunnus']) {
		muokkaa_kampanjaa($request);
		$request['tee'] = 'nayta_kampanjat';
	}
}

if ($request['tee'] == 'nayta_kampanjat') {
	nayta_kampanjat();
}

function luo_uusi_kampanja($request) {
	global $kukarow, $yhtiorow;

	$kampanja_tunnus = luo_kampanja_otsikko($request['kampanja_nimi']);

	foreach ($request['kampanja_ehdot'] as $kampanja_ehto) {
		$kampanja_ehto_tunnus = luo_kampanja_ehto($kampanja_ehto, $kampanja_tunnus);

		foreach ($kampanja_ehto['alisaanto_rivit'] as $kampanja_aliehto) {
			luo_kampanja_aliehto($kampanja_aliehto, $kampanja_ehto_tunnus);
		}
	}

	foreach ($request['palkinto_rivit'] as $palkinto_rivi) {
		luo_palkinto_rivi($palkinto_rivi, $kampanja_tunnus);
	}
}

function luo_kampanja_otsikko($kampanja_nimi) {
	global $kukarow, $yhtiorow;

	$query = "	INSERT INTO kampanja
				SET yhtio = '{$kukarow['yhtio']}',
				nimi = '{$kampanja_nimi}',
				laatija = '{$kukarow['kuka']}',
				luontiaika = CURRENT_DATE";
	pupe_query($query);

	return mysql_insert_id();
}

function luo_kampanja_ehto($kampanja_ehto, $kampanja_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	INSERT INTO kampanja_ehto
				SET yhtio = '{$kukarow['yhtio']}',
				kampanja = '{$kampanja_tunnus}',
				isatunnus = 0,
				kohde = '{$kampanja_ehto['kohde']}',
				rajoitin = '{$kampanja_ehto['rajoitin']}',
				arvo = '{$kampanja_ehto['arvo']}',
				laatija = '{$kukarow['kuka']}',
				luontiaika = CURRENT_DATE";
	pupe_query($query);

	return mysql_insert_id();
}

function luo_kampanja_aliehto($kampanja_aliehto, $kampanja_ehto_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	INSERT INTO kampanja_ehto
				SET yhtio = '{$kukarow['yhtio']}',
				kampanja = 0,
				isatunnus = {$kampanja_ehto_tunnus},
				kohde = '{$kampanja_aliehto['kohde']}',
				rajoitin = '{$kampanja_aliehto['rajoitin']}',
				arvo = '{$kampanja_aliehto['arvo']}',
				laatija = '{$kukarow['kuka']}',
				luontiaika = CURRENT_DATE";
	pupe_query($query);

	return mysql_insert_id();
}

function luo_palkinto_rivi($palkinto_rivi, $kampanja_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	INSERT INTO kampanja_palkinto
				SET yhtio = '{$kukarow['yhtio']}',
				kampanja = '{$kampanja_tunnus}',
				tuoteno = '{$palkinto_rivi['tuoteno']}',
				kpl = '{$palkinto_rivi['kpl']}',
				laatija = '{$kukarow['kuka']}',
				luontiaika = CURRENT_DATE";
	pupe_query($query);
}

function muokkaa_kampanjaa($request) {
	global $kukarow, $yhtirow;

	$query = "	UPDATE kampanja
				SET nimi = '{$request['kampanja_nimi']}',
				muuttaja = '{$kukarow['kuka']}',
				muutospvm = CURRENT_DATE
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$request['kampanja_tunnus']}'";
	pupe_query($query);

	poista_kampanja_ehdot($request['kampanja_tunnus']);
	poista_kampanja_palkinnot($request['kampanja_tunnus']);

	foreach ($request['kampanja_ehdot'] as $kampanja_ehto) {
		$kampanja_ehto_tunnus = luo_kampanja_ehto($kampanja_ehto, $request['kampanja_tunnus']);

		foreach ($kampanja_ehto['alisaanto_rivit'] as $kampanja_aliehto) {
			luo_kampanja_aliehto($kampanja_aliehto, $kampanja_ehto_tunnus);
		}
	}

	foreach ($request['palkinto_rivit'] as $palkinto_rivi) {
		luo_palkinto_rivi($palkinto_rivi, $request['kampanja_tunnus']);
	}
}

function poista_kampanja_ehdot($kampanja_tunnus) {
	global $kukarow, $yhtiorow;

	//Poistetaan aliehdot
	$query = "	SELECT tunnus
				FROM kampanja_ehto
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND kampanja = '{$kampanja_tunnus}'";
	$result = pupe_query($query);
	while ($kampanja = mysql_fetch_assoc($result)) {
		$query = "	DELETE FROM kampanja_ehto
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND isatunnus = '{$kampanja['tunnus']}'";
		pupe_query($query);
	}

	//Poistetaan ehdot
	$query = "	DELETE FROM kampanja_ehto
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND kampanja = '{$kampanja_tunnus}'";
	pupe_query($query);
}

function poista_kampanja_palkinnot($kampanja_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	DELETE FROM kampanja_palkinto
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND kampanja = '{$kampanja_tunnus}'";
	pupe_query($query);
}

function echo_kayttoliittyma($request = array()) {
	//Templatet on javascriptiä varten, että voidaan kutsua .clone()
	echo_saanto_rivi_template();

	echo_saanto_alirivi_template();

	echo_palkinto_rivi_template();

	echo "<form name='kampanja_form' method='POST' action=''>";
	if (!empty($request['kampanja']['tunnus'])) {
		echo "<input type='hidden' name='tee' value='muokkaa_kampanjaa' />";
	}
	else {
		echo "<input type='hidden' name='tee' value='uusi_kampanja' />";
	}

	echo "<div id='kampanja_header'>";
	echo t("Kampanjan nimi").':';
	echo "<br/>";
	echo "<input type='hidden' size=50 name='kampanja_tunnus' value='{$request['kampanja']['tunnus']}'/>";
	echo "<input type='text' size=50 name='kampanja_nimi' value='{$request['kampanja']['nimi']}'/>";
	echo "</div>";

	echo "<br/>";
	echo "<hr/>";
	echo "<br/>";

	echo "<div id='saannot'>";
	echo "<button id='uusi_saanto'>".t("Uusi sääntö")."</button>";
	if (!empty($request['kampanja']['kampanja_ehdot'])) {
		foreach ($request['kampanja']['kampanja_ehdot'] as $index => $kampanja_ehto) {
			echo_kampanja_ehto($index, $kampanja_ehto);
		}
	}
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
	if (!empty($request['kampanja']['kampanja_palkinnot'])) {
		foreach ($request['kampanja']['kampanja_palkinnot'] as $palkinto_index => $palkinto) {
			echo_kampanja_palkinto($palkinto_index, $palkinto);
		}
	}
	echo "</table>";
	echo "</div>";

	echo "<br/>";

	echo "<input type='submit' value='".t("Luo")."' />";
	echo "</form>";
}

function echo_kampanja_ehto($index, $kampanja_ehto) {
	$ehdot = hae_ehdon_kohteet();
	$rajoittimet = hae_ehdon_rajoittimet();
	$arvo_input = hae_liveseach_kentta($kampanja_ehto['kohde'], 'ehto', $index, 0, $kampanja_ehto['arvo']);

	echo "<div id='saanto_rivi'>";

	echo "<input type='hidden' class='saanto_id' value='{$index}'/>";

	echo "<select class='saanto_kohde' name='kampanja_ehdot[{$index}][kohde]'>";
	foreach ($ehdot as $ehto) {
		$sel = "";
		if ($ehto['value'] == $kampanja_ehto['kohde']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$ehto['value']}' {$sel}>{$ehto['text']}</option>";
	}
	echo "</select>";

	echo "<select class='saanto_rajoitin' name='kampanja_ehdot[{$index}][rajoitin]'>";
	foreach ($rajoittimet as $rajoitin) {
		$sel = "";
		if ($rajoitin['value'] == $kampanja_ehto['rajoitin']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$rajoitin['value']}' {$sel}>{$rajoitin['text']}</option>";
	}
	echo "</select>";

	echo $arvo_input;
	//echo "<input type='text' class='saanto_arvo' value='{$kampanja_ehto['arvo']}' name='kampanja_ehdot[{$index}][arvo]'/>";

	echo "<button class='uusi_alisaanto'>".t("Uusi alisääntö")."</button>";
	echo "<button class='poista_saanto'>".t("Poista sääntö")."</button>";

	echo "<div class='alisaannot'>";
	foreach ($kampanja_ehto['aliehdot'] as $aliehto_index => $aliehto) {
		echo_kampanja_aliehto($index, $aliehto_index, $aliehto);
	}
	echo "</div>";

	echo "</div>";
}

function echo_kampanja_aliehto($ehto_index, $aliehto_index, $aliehto) {
	$ehdot = hae_ehdon_kohteet();
	$rajoittimet = hae_ehdon_rajoittimet();
	$arvo_input = hae_liveseach_kentta($aliehto['kohde'], 'aliehto', $ehto_index, $aliehto_index, $aliehto['arvo']);

	echo "<div id='alisaanto_rivi'>";

	echo "<input type='hidden' class='alisaanto_id' value='{$aliehto_index}'/>";

	echo "AND------>  ";
	echo "<select class='alisaanto_kohde' name='kampanja_ehdot[{$ehto_index}][alisaanto_rivit][{$aliehto_index}][kohde]'>";
	foreach ($ehdot as $ehto) {
		$sel = "";
		if ($ehto['value'] == $aliehto['kohde']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$ehto['value']}' {$sel}>{$ehto['text']}</option>";
	}
	echo "</select>";

	echo "<select class='alisaanto_rajoitin' name='kampanja_ehdot[{$ehto_index}][alisaanto_rivit][{$aliehto_index}][rajoitin]'>";
	foreach ($rajoittimet as $rajoitin) {
		$sel = "";
		if ($rajoitin['value'] == $aliehto['rajoitin']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$rajoitin['value']}' {$sel}>{$rajoitin['text']}</option>";
	}
	echo "</select>";

	echo $arvo_input;
	//echo "<input type='text' class='alisaanto_arvo' value='{$aliehto['arvo']}' name='kampanja_ehdot[{$ehto_index}][alisaanto_rivit][{$aliehto_index}][arvo]'/>";

	echo "<button class='poista_alisaanto'>".t("Poista alisääntö")."</button>";

	echo "</div>";
}

function echo_saanto_rivi_template() {
	$ehdot = hae_ehdon_kohteet();
	$rajoittimet = hae_ehdon_rajoittimet();

	echo "<div id='saanto_rivi_template'>";

	echo "<input type='hidden' class='saanto_id_template' />";

	echo "<select class='saanto_kohde'>";
	foreach ($ehdot as $ehto) {
		echo "<option value='{$ehto['value']}'>{$ehto['text']}</option>";
	}
	echo "</select>";

	echo "<select class='saanto_rajoitin'>";
	foreach ($rajoittimet as $rajoitin) {
		echo "<option value='{$rajoitin['value']}'>{$rajoitin['text']}</option>";
	}
	echo "</select>";

	echo "<input type='text' class='saanto_arvo' />";

	echo "<button class='uusi_alisaanto'>".t("Uusi alisääntö")."</button>";
	echo "<button class='poista_saanto'>".t("Poista sääntö")."</button>";

	echo "<div class='alisaannot'>";
	echo "</div>";

	echo "</div>";
}

function echo_saanto_alirivi_template() {
	$ehdot = hae_ehdon_kohteet();
	$rajoittimet = hae_ehdon_rajoittimet();

	echo "<div id='alisaanto_rivi_template'>";

	echo "<input type='hidden' class='alisaanto_id_template' />";

	echo "AND------>  ";
	echo "<select class='alisaanto_kohde'>";
	foreach ($ehdot as $ehto) {
		echo "<option value='{$ehto['value']}'>{$ehto['text']}</option>";
	}
	echo "</select>";

	echo "<select class='alisaanto_rajoitin'>";
	foreach ($rajoittimet as $rajoitin) {
		echo "<option value='{$rajoitin['value']}'>{$rajoitin['text']}</option>";
	}
	echo "</select>";

	echo "<input type='text' class='alisaanto_arvo' />";

	echo "<button class='poista_alisaanto'>".t("Poista alisääntö")."</button>";

	echo "</div>";
}

function echo_kampanja_palkinto($palkinto_index, $palkinto) {
	global $kukarow, $yhtiorow;

	$tuoteno_input = hae_liveseach_kentta('tuote', 'palkinto', $palkinto_index, 0, $palkinto['tuoteno']);

	echo "<tr id='palkinto_rivi'>";
	echo "<td>";
	echo "<input type='hidden' class='palkinto_rivi_id' value='{$palkinto_index}'/>";
	echo $tuoteno_input;
	echo "</td>";

	echo "<td>";
	echo "<input type='text' class='palkinto_rivi_kpl' value='{$palkinto['kpl']}' name='palkinto_rivit[{$palkinto_index}][kpl]'/>";
	echo "</td>";

	echo "<td>";
	echo "<button class='poista_palkinto'>".t("Poista palkinto")."</button>";
	echo "</td>";
	echo "</tr>";
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
	echo "<button class='poista_palkinto'>".t("Poista palkinto")."</button>";
	echo "</td>";
	echo "</tr>";

	echo "</table>";
}

function hae_liveseach_kentta($kohde, $tyyppi, $ehto_index, $aliehto_index = 0, $value = '') {
	if ($tyyppi == 'ehto') {
		if ($kohde == 'asiakas') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "ASIAKASHAKU", "kampanja_ehdot[{$ehto_index}][arvo]", 140, $value, '', '', 'saanto_arvo', 'ei_break_all');
		}
		else if ($kohde == 'tuote') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "TUOTEHAKU", "kampanja_ehdot[{$ehto_index}][arvo]", 140, $value, '', '', 'saanto_arvo', 'ei_break_all');
		}
		else {
			$return = "<input type='text' class='saanto_arvo' name='kampanja_ehdot[{$ehto_index}][arvo]' value='{$value}' />";
		}
	}
	else if ($tyyppi == 'aliehto') {
		if ($kohde == 'asiakas') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "ASIAKASHAKU", "kampanja_ehdot[{$ehto_index}][alisaanto_rivit][{$aliehto_index}][arvo]", 140, $value, '', '', 'alisaanto_arvo', 'ei_break_all');
		}
		else if ($kohde == 'tuote') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "TUOTEHAKU", "kampanja_ehdot[{$ehto_index}][alisaanto_rivit][{$aliehto_index}][arvo]", 140, $value, '', '', 'alisaanto_arvo', 'ei_break_all');
		}
		else {
			$return = "<input type='text' class='alisaanto_arvo' name='kampanja_ehdot[{$ehto_index}][alisaanto_rivit][{$aliehto_index}][arvo]' value='{$value}' />";
		}
	}
	else {
		//palkinto rivit
		$return = livesearch_kentta("eisaaollaoikeaforminnimi", "TUOTEHAKU", "palkinto_rivit[{$ehto_index}][tuoteno]", 140, $value, '', '', 'palkinto_rivi_nimi', 'ei_break_all');
	}

	return $return;
}

function hae_ehdon_kohteet() {
	return array(
		0	 => array(
			'text'	 => t("Asiakas"),
			'value'	 => 'asiakas'
		),
		1	 => array(
			'text'	 => t("Asiakas ytunnus"),
			'value'	 => 'asiakas_ytunnus'
		),
		2	 => array(
			'text'	 => t("Asiakaskategoria"),
			'value'	 => 'asiakaskategoria'
		),
		3	 => array(
			'text'	 => t("Tuote"),
			'value'	 => 'tuote'
		),
		4	 => array(
			'text'	 => t("Tuotekategoria"),
			'value'	 => 'tuotekategoria'
		),
		5	 => array(
			'text'	 => t("Kappaleet"),
			'value'	 => 'kappaleet'
		),
		6	 => array(
			'text'	 => t("Arvo"),
			'value'	 => 'arvo'
		),
	);
}

function hae_ehdon_rajoittimet() {
	return array(
		0	 => array(
			'text'	 => t("on"),
			'value'	 => 'on'
		),
		1	 => array(
			'text'	 => t("Ei ole"),
			'value'	 => 'ei_ole'
		),
		2	 => array(
			'text'	 => t("on suurempi kuin"),
			'value'	 => 'suurempi_kuin'
		),
		3	 => array(
			'text'	 => t("on pienempi kuin"),
			'value'	 => 'pienempi_kuin'
		),
	);
}

function hae_kampanja($kampanja_tunnus) {
	global $kukarow, $yhtirow;

	$query = "	SELECT *
				FROM kampanja
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$kampanja_tunnus}'";
	$result = pupe_query($query);

	$kampanja = mysql_fetch_assoc($result);

	$kampanja['kampanja_ehdot'] = hae_kampanjan_ehdot($kampanja_tunnus);
	$kampanja['kampanja_palkinnot'] = hae_kampanjan_palkinnot($kampanja_tunnus);

	return $kampanja;
}

function hae_kampanjan_ehdot($kampanja_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM kampanja_ehto
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND kampanja = '{$kampanja_tunnus}'";
	$result = pupe_query($query);

	$kampanja_ehdot = array();
	while ($kampanja_ehto = mysql_fetch_assoc($result)) {
		$kampanja_ehto['aliehdot'] = hae_kampanja_ehdon_aliehdot($kampanja_ehto['tunnus']);
		$kampanja_ehdot[] = $kampanja_ehto;
	}

	return $kampanja_ehdot;
}

function hae_kampanja_ehdon_aliehdot($kampanja_ehto_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM kampanja_ehto
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND isatunnus = '{$kampanja_ehto_tunnus}'";
	$result = pupe_query($query);

	$kampanja_aliehdot = array();
	while ($kampanja_aliehto = mysql_fetch_assoc($result)) {
		$kampanja_aliehdot[] = $kampanja_aliehto;
	}

	return $kampanja_aliehdot;
}

function hae_kampanjan_palkinnot($kampanja_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM kampanja_palkinto
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND kampanja = '{$kampanja_tunnus}'";
	$result = pupe_query($query);

	$kampanja_palkinnot = array();
	while ($kampanja_palkinto = mysql_fetch_assoc($result)) {
		$kampanja_palkinnot[] = $kampanja_palkinto;
	}

	return $kampanja_palkinnot;
}

function nayta_kampanjat() {
	global $kukarow, $yhtiorow;

	$kampanjat = hae_kampanjat();

	echo_kampanjat($kampanjat);
}

function hae_kampanjat() {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM kampanja
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	$kampanjat = array();
	while ($kampanja = mysql_fetch_assoc($result)) {
		$kampanjat[] = $kampanja;
	}

	return $kampanjat;
}

function echo_kampanjat($kampanjat) {
	global $kukarow, $yhtiorow;

	echo "<div id='kampanjat'>";
	foreach ($kampanjat as $kampanja) {
		echo "<div id='kampanja'>";
		echo $kampanja['nimi']." <a href='kampanja.php?tee=uusi_kampanja&kampanja_tunnus={$kampanja['tunnus']}'>".t("Muokkaa")."</a>";
		echo "</div>";
		echo "<br/>";
		echo "<br/>";
	}

	echo "<a href='kampanja.php?tee=uusi_kampanja'>".t("Uusi kampanja")."</a>";

	echo "</div>";
}