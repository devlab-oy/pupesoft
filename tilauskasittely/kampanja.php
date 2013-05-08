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
		$return = hae_liveseach_kentta($rivi_kohde, 'palkinto', $ehto_rivi_id);
	}
	else {
		if (isset($aliehto_rivi_id)) {
			$return = hae_liveseach_kentta($rivi_kohde, 'aliehto', $ehto_rivi_id, $aliehto_rivi_id);
		}
		else {
			$return = hae_liveseach_kentta($rivi_kohde, 'ehto', $ehto_rivi_id);
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
	#ehto_rivi_template {
		display: none;
	}
	#aliehto_rivi_template {
		display: none;
	}

	#palkinto_table_template {
		display: none;
	}
	.ehto_arvo {
		width: 140px;
	}
</style>
<script>

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
	});

	function bind_ehto_kohde_change() {
		$('.ehto_kohde').live('change', function() {
			var ehto_rivi = $(this).parent();
			hae_ehdon_arvo_input(ehto_rivi);

			filteroi_aliehdon_kohteet($(this).val());
		});
	}

	function hae_ehdon_arvo_input(ehto_rivi) {
		$.ajax({
			async: true,
			type: 'GET',
			data: {
				ajax_request: 1,
				no_head: 'yes',
				rivi_kohde: $(ehto_rivi).find('.ehto_kohde').val(),
				ehto_rivi_id: $(ehto_rivi).find('.ehto_id').val()
			},
			url: 'kampanja.php'
		}).done(function(data) {
			if (console && console.log) {
				console.log('Input kentän haku onnistui');
				console.log(data);
			}
			if (data.length !== 0) {
				$(ehto_rivi).find('.ehto_arvo').remove();
				$(ehto_rivi).find('.liveSearch').remove();
				$(ehto_rivi).find('.ehto_rajoitin').after(data);
			}
		});
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

		$(ehto_rivi).css('display', 'block');
		$(ehto_rivi).removeAttr('id');
		$(ehto_rivi).attr('class', 'ehto_rivi');

		$(ehto_rivi).find('.ehto_kohde').attr('name', 'kampanja_ehdot[' + ehto_id + '][kohde]');
		$(ehto_rivi).find('.ehto_rajoitin').attr('name', 'kampanja_ehdot[' + ehto_id + '][rajoitin]');

		$(ehto_rivi).find('.ehto_id_template').attr('class', 'ehto_id');
		$(ehto_rivi).find('.ehto_id_template').removeClass('ehto_id_template');

		$(ehto_rivi).find('.ehto_id').val(ehto_id);

		$('#ehdot').append(ehto_rivi);

		hae_ehdon_arvo_input(ehto_rivi);
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
			hae_alikohteen_arvo_input(aliehto_rivi);
		});
	}

	function hae_alikohteen_arvo_input(aliehto_rivi) {
		$.ajax({
			async: true,
			type: 'GET',
			data: {
				ajax_request: 1,
				no_head: 'yes',
				rivi_kohde: $(aliehto_rivi).find('.aliehto_kohde').val(),
				ehto_rivi_id: $(aliehto_rivi).parent().parent().find('.ehto_id').val(),
				aliehto_rivi_id: $(aliehto_rivi).find('.aliehto_id').val()
			},
			url: 'kampanja.php'
		}).done(function(data) {
			if (console && console.log) {
				console.log('Input kentän haku onnistui');
				//console.log(data);
			}
			if (data.length !== 0) {
				$(aliehto_rivi).find('.aliehto_arvo').remove();
				$(aliehto_rivi).find('.liveSearch').remove();
				$(aliehto_rivi).find('.aliehto_rajoitin').after(data);
			}
		});
	}

	function bind_uusi_aliehto_button() {
		//Käytetään liveä, koska kyseessä on dynaaminen elementti.
		$('.uusi_aliehto').live('click', function(event) {
			event.preventDefault();

			//parametrinä div.aliehdot
			var aliehto_id = generoi_aliehto_id($(this).parent().find('.aliehdot'));
			//parametrinä div.ehto_rivi
			populoi_aliehto_rivi($(this).parent(), aliehto_id);
		});
	}

	function populoi_aliehto_rivi(ehto_rivi, aliehto_id) {
		var aliehto_rivi = $('#aliehto_rivi_template').clone();
		var ehto_id = $(ehto_rivi).find('.ehto_id').val();

		$(aliehto_rivi).css('display', 'block');
		$(aliehto_rivi).removeAttr('id');
		$(aliehto_rivi).attr('class', 'aliehto_rivi');

		$(aliehto_rivi).find('.aliehto_kohde').attr('name', 'kampanja_ehdot[' + ehto_id + '][aliehto_rivit][' + aliehto_id + '][kohde]');
		$(aliehto_rivi).find('.aliehto_rajoitin').attr('name', 'kampanja_ehdot[' + ehto_id + '][aliehto_rivit][' + aliehto_id + '][rajoitin]');


		$(aliehto_rivi).find('.aliehto_id_template').attr('class', 'aliehto_id');
		$(aliehto_rivi).find('.aliehto_id_template').removeClass('aliehto_id_template');

		$(aliehto_rivi).find('.aliehto_id').val(aliehto_id);

		$(ehto_rivi).find('.aliehdot').append(aliehto_rivi);

		hae_alikohteen_arvo_input(aliehto_rivi);
	}

	function generoi_aliehto_id(aliehto_rivit) {
		var max = 0;
		$(aliehto_rivit).each(function(index, aliehto_rivi) {
			if (max < $(aliehto_rivi).find('.aliehto_id').val()) {
				max = $(aliehto_rivi).find('.aliehto_id').val();
			}
		});

		max++;

		return max;
	}

	function bind_poista_ehto_button() {
		$('.poista_ehto').live('click', function(event) {
			event.preventDefault();

			$(this).parent().remove();
		});
	}

	function bind_poista_aliehto_button() {
		$('.poista_aliehto').live('click', function(event) {
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
				ehto_rivi_id: $(palkinto_rivi).find('.palkinto_rivi_id').val()
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

		foreach ($kampanja_ehto['aliehto_rivit'] as $kampanja_aliehto) {
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

		foreach ($kampanja_ehto['aliehto_rivit'] as $kampanja_aliehto) {
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
	echo_ehto_rivi_template();

	echo_ehto_alirivi_template();

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

	echo "<div id='ehdot'>";
	echo "<button id='uusi_ehto'>".t("Uusi ehto")."</button>";
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

	echo "<div id='ehto_rivi'>";

	echo "<input type='hidden' class='ehto_id' value='{$index}'/>";

	echo "<select class='ehto_kohde' name='kampanja_ehdot[{$index}][kohde]'>";
	foreach ($ehdot as $ehto) {
		$sel = "";
		if ($ehto['value'] == $kampanja_ehto['kohde']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$ehto['value']}' {$sel}>{$ehto['text']}</option>";
	}
	echo "</select>";

	echo "<select class='ehto_rajoitin' name='kampanja_ehdot[{$index}][rajoitin]'>";
	foreach ($rajoittimet as $rajoitin) {
		$sel = "";
		if ($rajoitin['value'] == $kampanja_ehto['rajoitin']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$rajoitin['value']}' {$sel}>{$rajoitin['text']}</option>";
	}
	echo "</select>";

	echo $arvo_input;
	//echo "<input type='text' class='ehto_arvo' value='{$kampanja_ehto['arvo']}' name='kampanja_ehdot[{$index}][arvo]'/>";

	echo "<button class='uusi_aliehto'>".t("Uusi aliehto")."</button>";
	echo "<button class='poista_ehto'>".t("Poista ehto")."</button>";

	echo "<div class='aliehdot'>";
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

	echo "<div id='aliehto_rivi'>";

	echo "<input type='hidden' class='aliehto_id' value='{$aliehto_index}'/>";

	echo "AND------>  ";
	echo "<select class='aliehto_kohde' name='kampanja_ehdot[{$ehto_index}][aliehto_rivit][{$aliehto_index}][kohde]'>";
	foreach ($ehdot as $ehto) {
		$sel = "";
		if ($ehto['value'] == $aliehto['kohde']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$ehto['value']}' {$sel}>{$ehto['text']}</option>";
	}
	echo "</select>";

	echo "<select class='aliehto_rajoitin' name='kampanja_ehdot[{$ehto_index}][aliehto_rivit][{$aliehto_index}][rajoitin]'>";
	foreach ($rajoittimet as $rajoitin) {
		$sel = "";
		if ($rajoitin['value'] == $aliehto['rajoitin']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$rajoitin['value']}' {$sel}>{$rajoitin['text']}</option>";
	}
	echo "</select>";

	echo $arvo_input;

	echo "<button class='poista_aliehto'>".t("Poista aliehto")."</button>";

	echo "</div>";
}

function echo_ehto_rivi_template() {
	$ehdot = hae_ehdon_kohteet();
	$rajoittimet = hae_ehdon_rajoittimet();

	echo "<div id='ehto_rivi_template'>";

	echo "<input type='hidden' class='ehto_id_template' />";

	echo "<select class='ehto_kohde'>";
	foreach ($ehdot as $ehto) {
		echo "<option value='{$ehto['value']}'>{$ehto['text']}</option>";
	}
	echo "</select>";

	echo "<select class='ehto_rajoitin'>";
	foreach ($rajoittimet as $rajoitin) {
		echo "<option value='{$rajoitin['value']}'>{$rajoitin['text']}</option>";
	}
	echo "</select>";

	echo "<input type='text' class='ehto_arvo' />";

	echo "<button class='uusi_aliehto'>".t("Uusi aliehto")."</button>";
	echo "<button class='poista_ehto'>".t("Poista ehto")."</button>";

	echo "<div class='aliehdot'>";
	echo "</div>";

	echo "</div>";
}

function echo_ehto_alirivi_template() {
	$ehdot = hae_ehdon_kohteet();
	$rajoittimet = hae_ehdon_rajoittimet();

	echo "<div id='aliehto_rivi_template'>";

	echo "<input type='hidden' class='aliehto_id_template' />";

	echo "AND------>  ";
	echo "<select class='aliehto_kohde'>";
	foreach ($ehdot as $ehto) {
		echo "<option value='{$ehto['value']}'>{$ehto['text']}</option>";
	}
	echo "</select>";

	echo "<select class='aliehto_rajoitin'>";
	foreach ($rajoittimet as $rajoitin) {
		echo "<option value='{$rajoitin['value']}'>{$rajoitin['text']}</option>";
	}
	echo "</select>";

	echo "<input type='text' class='aliehto_arvo' />";

	echo "<button class='poista_aliehto'>".t("Poista aliehto")."</button>";

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
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "ASIAKASHAKU", "kampanja_ehdot[{$ehto_index}][arvo]", 140, $value, '', '', 'ehto_arvo', 'ei_break_all');
		}
		else if ($kohde == 'tuote') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "TUOTEHAKU", "kampanja_ehdot[{$ehto_index}][arvo]", 140, $value, '', '', 'ehto_arvo', 'ei_break_all');
		}
		else {
			$return = "<input type='text' class='ehto_arvo' name='kampanja_ehdot[{$ehto_index}][arvo]' value='{$value}' />";
		}
	}
	else if ($tyyppi == 'aliehto') {
		if ($kohde == 'asiakas') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "ASIAKASHAKU", "kampanja_ehdot[{$ehto_index}][aliehto_rivit][{$aliehto_index}][arvo]", 140, $value, '', '', 'aliehto_arvo', 'ei_break_all');
		}
		else if ($kohde == 'tuote') {
			$return = livesearch_kentta("eisaaollaoikeaforminnimi", "TUOTEHAKU", "kampanja_ehdot[{$ehto_index}][aliehto_rivit][{$aliehto_index}][arvo]", 140, $value, '', '', 'aliehto_arvo', 'ei_break_all');
		}
		else {
			$return = "<input type='text' class='aliehto_arvo' name='kampanja_ehdot[{$ehto_index}][aliehto_rivit][{$aliehto_index}][arvo]' value='{$value}' />";
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

function nayta_kampanjat() {
	global $kukarow, $yhtiorow;

	$kampanjat = hae_kampanjat();

	echo_kampanjat($kampanjat);
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