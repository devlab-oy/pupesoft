<?php

	require ("../inc/parametrit.inc");

	enable_jquery();

	echo "	<script type='text/javascript' language='JavaScript'>
				$(document).ready(function() {

					$('td').css({'padding': '0px', 'white-space': 'nowrap'});

					$('.center').css({'text-align': 'center', 'padding-left': '7px', 'padding-right': '7px'});
					$('.data').css({'padding-left': '7px', 'padding-right': '7px', 'padding-bottom': '0px', 'padding-top': '0px'});

					$(':checkbox').click(function(event){
						event.stopPropagation();
					});

					$('.vihrea').css({'background-image': 'url(\"{$palvelin2}pics/vaaleanvihrea.png\")'});
					$('.keltainen').css({'background-image': 'url(\"{$palvelin2}pics/keltainen.png\")'});
					$('.punainen').css({'background-image': 'url(\"{$palvelin2}pics/punainen.png\")'});

					$('.row_direction_order').attr('src', '{$palvelin2}pics/lullacons/arrow-double-up-green.png');

					$('.toggleable').live('click', function(event){

						var id = this.id.split(\"__\", 2);

						if ($('#toggleable_'+id[0]+'__'+id[1]).is(':visible')) {

							$('.toggleable_row_child').hide();
							$('#toggleable_'+id[0]+'__'+id[1]).hide();

							$('.toggleable_row_order').removeClass('tumma');
							$('.toggleable_row_sscc').removeClass('tumma');

							$('.toggleable_row_tr').hide();

							$('tr[id!=\"toggleable_parent_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_parent\"]').show();
							$('tr[id!=\"toggleable_tr_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_tr\"]').stop().show();

							$(':checkbox').attr('checked', false);
						}
						else {
							var parent_element = $('#toggleable_'+id[0]+'__'+id[1]).parent();

							$('.toggleable_row_tr').show();

							$('tr[id!=\"toggleable_parent_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_parent\"]').hide();
							$('tr[id!=\"toggleable_tr_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_tr\"]').hide();

							$('#toggleable_'+id[0]+'__'+id[1]).css({'width': parent_element.width()+'px', 'padding-top': '15px'}).show();
						}
					});

					$('.toggleable_row_order').live('click', function(event){

						var id = this.id.split(\"__\", 2);

						if ($('#toggleable_row_order_'+id[0]+'__'+id[1]).is(':visible')) {

							$(this).removeClass('tumma');

							$('#toggleable_row_order_'+id[0]+'__'+id[1]).slideUp('fast');

							if ($('.toggleable_row_child:visible').length == 1) {
								$('tr[id!=\"toggleable_row_tr_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_row_tr\"]').show();
							}
						}
						else {

							$(this).addClass('tumma');

							var parent_element = $('#toggleable_row_order_'+id[0]+'__'+id[1]).parent();

							$('tr[id!=\"toggleable_row_tr_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_row_tr\"]').hide();

							$('#toggleable_row_order_'+id[0]+'__'+id[1]).css({'width': parent_element.width()+'px', 'padding-bottom': '15px'}).delay(1).slideDown('fast');
						}
					});

					$('.toggleable_row_sscc').live('click', function(event){

						if ($(this).html() != '') {
							var id = this.id.split(\"__\", 3);

							if ($('#toggleable_row_sscc_'+id[0]+'_'+id[2]).is(':visible')) {
								$(this).removeClass('tumma');

								$('#toggleable_row_sscc_'+id[0]+'_'+id[2]).slideUp('fast');

								if ($('.toggleable_row_child:visible').length == 1) {
									$('tr[id!=\"toggleable_row_tr_'+id[1]+'__'+id[2]+'\"][class=\"toggleable_row_tr\"]').show();
								}
							}
							else {

								$(this).addClass('tumma');

								$('tr[id!=\"toggleable_row_tr_'+id[1]+'__'+id[2]+'\"][class=\"toggleable_row_tr\"]').hide();

								var parent_element = $('#toggleable_row_sscc_'+id[0]+'_'+id[2]).parent();

								$('#toggleable_row_sscc_'+id[0]+'_'+id[2]).css({'width': parent_element.width()+'px', 'padding-bottom': '15px'}).delay(1).slideDown('fast');
							}
						}

					});

					function compareId(a, b) {
						return b.id - a.id;
					}

					function compareName(a, b) {
						if (b.id.toLowerCase() > a.id.toLowerCase()) {
							return 1;
						}
						else if (b.id.toLowerCase() < a.id.toLowerCase()) {
							return -1;
						}
						else {
							return 0;
						}
					}

					$('.sort_row_by').each(function() {
						var title_sort = this.id.substring(4);

						window['sort_row_direction_'+title_sort] = false;

					});

					$('.sort_parent_row_by').each(function() {
						var title_sort = this.id.substring(11);
						window['sort_parent_row_direction_'+title_sort] = false;
					});

					$('.sort_row_by, .sort_parent_row_by').click(function() {

						var parent_sort = $(this).hasClass('sort_parent_row_by') ? true : false;

						var title = parent_sort ? this.id.substring(11) : this.id.substring(4);

						if (parent_sort) {

							var arr = $('.toggleable_parent:visible');
							var _arr = new Array();

							var _arrChild = new Array();

							for (i = 0; i < arr.length; i++) {
								var row = arr[i];

								var id = $(row).children('.toggleable_parent_row_'+title).attr('id');

								if (title == 'departure') {
									var id_temp = id.split(\"__\", 2);
									id = id_temp[0];
									counter = id_temp[1];
								}
								else {
									var id_temp = id.split(\"__\", 3);
									id = id_temp[0];
									counter = id_temp[2];
								}

								var temp = {'id': id, 'row': row, 'counter': counter, 'link': id_temp[0]};
								_arr.push(temp);

								var rowChild = $('#toggleable_tr_'+id_temp[1]+'__'+counter);
								var tempChild = {'id': id, 'row': rowChild, 'counter': counter};
								_arrChild.push(tempChild);	
							}

							$('.toggleable_parent:visible').remove();

							for (i = 0; i < _arr.length; i++) {
								$('.toggleable_tr_'+_arr[i].link+'__'+_arr[i].counter).remove();
							}

							if (window['sort_parent_row_direction_'+title]) {

								if (title == 'delivery' || title == 'date' || title == 'time1') {
									_arr.sort(compareName);
									_arrChild.sort(compareName);
								}
								else {
									_arr.sort(compareId);
									_arrChild.sort(compareId);
								}

								for (i = 0; i < _arr.length; i++) {
									$('.header_parent').after(_arrChild[i].row);
									$('.header_parent').after(_arr[i].row);
								}

								$('.parent_row_direction_'+title).attr('src', '{$palvelin2}pics/lullacons/arrow-double-up-green.png');

								window['sort_parent_row_direction_'+title] = false;
							}
							else {

								if (title == 'delivery' || title == 'date' || title == 'time1') {
									_arr.sort(compareName).reverse();
									_arrChild.sort(compareName).reverse();
								}
								else {
									_arr.sort(compareId).reverse();
									_arrChild.sort(compareId).reverse();
								}

								for (i = 0; i < _arr.length; i++) {
									$('.header_parent').after(_arrChild[i].row);
									$('.header_parent').after(_arr[i].row);
								}

								$('.parent_row_direction_'+title).attr('src', '{$palvelin2}pics/lullacons/arrow-double-down-green.png');

								window['sort_parent_row_direction_'+title] = true;
							}

						}
						else {

							var arr = $('.toggleable_row_tr:visible');
							var _arr = new Array();

							var _arrChildOrder = new Array();
							var _arrChildSscc = new Array();

							for (i = 0; i < arr.length; i++) {
								var row = arr[i];

								var id = $(row).children('.toggleable_row_'+title).attr('id');
								var counter = 0;

								if (title == 'status' || title == 'prio' || title == 'client' || title == 'locality' || title == 'picking_zone' || title == 'batch' || title == 'sscc' || title == 'package' || title == 'weight') {
									var id_temp = id.split(\"__\", 3);
									id = id_temp[0];
									counter = id_temp[2];
								}
								else {
									var id_temp = id.split(\"__\", 2);
									id = id_temp[0];
									counter = id_temp[1];
								}

								var temp = {'id': id, 'row': row, 'counter': counter};
								_arr.push(temp);

								var rowChildOrder = $('.toggleable_row_child_order_'+id+'__'+counter);
								var tempChildOrder = {'id': id, 'row': rowChildOrder, 'counter': counter};
								_arrChildOrder.push(tempChildOrder);

								var rowChildSscc = $('.toggleable_row_child_sscc_'+id+'__'+counter);
								var tempChildSscc = {'id': id, 'row': rowChildSscc, 'counter': counter};
								_arrChildSscc.push(tempChildSscc);
							}

							$('.toggleable_row_tr:visible').remove();

							for (i = 0; i < _arr.length; i++) {
								$('.toggleable_row_child_order_'+_arr[i].id+'__'+_arr[i].counter).remove();
								$('.toggleable_row_child_sscc_'+_arr[i].id+'__'+_arr[i].counter).remove();
							}

							var header_id = $('.toggleable_tr:visible').attr('id').substring(14).split(\"__\", 2);

							if (window['sort_row_direction_'+title]) {

								if (title == 'client' || title == 'locality' || title == 'picking_zone' || title == 'package') {
									_arr.sort(compareName);
									_arrChildOrder.sort(compareName);
									_arrChildSscc.sort(compareName);
								}
								else {
									_arr.sort(compareId);
									_arrChildOrder.sort(compareId);
									_arrChildSscc.sort(compareId);
								}

								for (i = 0; i < _arr.length; i++) {
									$('.header_row_'+header_id[0]+'__'+header_id[1]).after(_arrChildSscc[i].row);
									$('.header_row_'+header_id[0]+'__'+header_id[1]).after(_arrChildOrder[i].row);
									$('.header_row_'+header_id[0]+'__'+header_id[1]).after(_arr[i].row);
								}

								$('.row_direction_'+title).attr('src', '{$palvelin2}pics/lullacons/arrow-double-up-green.png');

								window['sort_row_direction_'+title] = false;
							}
							else {

								if (title == 'client' || title == 'locality' || title == 'picking_zone' || title == 'package') {
									_arr.sort(compareName).reverse();
									_arrChildOrder.sort(compareName).reverse();
									_arrChildSscc.sort(compareName).reverse();
								}
								else {
									_arr.sort(compareId).reverse();
									_arrChildOrder.sort(compareId).reverse();
									_arrChildSscc.sort(compareId).reverse();
								}

								for (i = 0; i < _arr.length; i++) {
									$('.header_row_'+header_id[0]+'__'+header_id[1]).after(_arrChildSscc[i].row);
									$('.header_row_'+header_id[0]+'__'+header_id[1]).after(_arrChildOrder[i].row);
									$('.header_row_'+header_id[0]+'__'+header_id[1]).after(_arr[i].row);
								}

								$('.row_direction_'+title).attr('src', '{$palvelin2}pics/lullacons/arrow-double-down-green.png');

								window['sort_row_direction_'+title] = true;
							}
						}
					});

					$('.checkall').click(function(){

						var id = $(this).attr('name');

						if ($('.checkbox_'+id).is(':checked')) {
							$('.checkbox_'+id).attr('checked', false);
						}
						else {
							$('.checkbox_'+id).attr('checked', true);
						}

					});
				});
			</script>";

	echo "<font class='head'>",t("Lähtöjen hallinta"),"</font><hr>";

	$query = "	SELECT lahdot.tunnus AS 'lahdon_tunnus',
				lahdot.pvm AS 'lahdon_pvm',
				lahdot.vakisin_kerays,
				SUBSTRING(lahdot.viimeinen_tilausaika, 1, 5) AS 'viimeinen_tilausaika',
				SUBSTRING(lahdot.lahdon_kellonaika, 1, 5) AS 'lahdon_kellonaika',
				SUBSTRING(lahdot.kerailyn_aloitusaika, 1, 5) AS 'kerailyn_aloitusaika',
				lasku.prioriteettinro AS 'prioriteetti',
				toimitustapa.selite AS 'toimitustapa',
				COUNT(DISTINCT lasku.tunnus) AS 'tilatut',
				SUM(IF((lasku.tila = 'L' AND lasku.alatila IN ('B', 'C')), 1, 0)) AS 'valmiina',
				COUNT(DISTINCT tilausrivi.tunnus) AS 'suunnittelussa',
				SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS 'keratyt',
				COUNT(DISTINCT lasku.liitostunnus) AS 'asiakkaita',
				GROUP_CONCAT(DISTINCT lasku.tunnus) AS 'tilaukset',
				ROUND(SUM(tilausrivi.varattu * tuote.tuotemassa), 0) AS 'kg_suun',
				ROUND(SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', tilausrivi.varattu * tuote.tuotemassa, 0)), 0) AS 'kg_ker',
				ROUND(SUM(tilausrivi.varattu * (tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * 1000)), 0) AS 'litrat_suun',
				ROUND(SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', (tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * 1000), 0)), 0) AS 'litrat_ker'
				FROM lasku
				JOIN lahdot ON (lahdot.yhtio = lasku.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto AND lahdot.aktiivi = '')
				JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa)
				JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus)
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))
				GROUP BY 1,2,3,4,5,6,7,8
				ORDER BY lahdot.pvm, lahdot.lahdon_kellonaika, lahdot.tunnus";
	$result = pupe_query($query);

	echo "<table>";
	echo "<tr class='header_parent'>";
	echo "<th class='sort_parent_row_by' id='parent_row_status'>",t("Status")," <img class='parent_row_direction_status' /></th>";
	echo "<th></th>";
	echo "<th class='sort_parent_row_by' id='parent_row_departure'>",t("Lähtö")," <img class='parent_row_direction_departure' /></th>";
	echo "<th class='sort_parent_row_by' id='parent_row_prio'>",t("Prio")," <img class='parent_row_direction_prio' /></th>";
	echo "<th class='sort_parent_row_by' id='parent_row_delivery'>",t("Toimitustapa")," <img class='parent_row_direction_delivery' /></th>";
	echo "<th class='sort_parent_row_by' id='parent_row_date'>",t("Pvm")," <img class='parent_row_direction_date' /></th>";
	echo "<th class='sort_parent_row_by' id='parent_row_time1'>",t("Viim til klo")," <img class='parent_row_direction_time1' /></th>";
	echo "<th>",t("Lähtöaika"),"</th>";
	echo "<th>",t("Ker. alku klo"),"</th>";
	echo "<th>",t("Til / valm"),"</th>";
	echo "<th>",t("Rivit suun / ker"),"</th>";
	echo "<th>",t("Kg suun / ker"),"</th>";
	echo "<th>",t("Litrat suun / ker"),"</th>";
	echo "</tr>";

	$y = 0;

	while ($row = mysql_fetch_assoc($result)) {

		echo "<tr class='toggleable_parent' id='toggleable_parent_{$row['lahdon_tunnus']}__{$y}'>";

		$exp_date = strtotime($row['lahdon_pvm']);
		$exp_date_klo = strtotime($row['kerailyn_aloitusaika'].':00');
		$todays_date = strtotime(date('Y-m-d'));
		$todays_date_klo = strtotime(date('H:i:s'));

		if ($todays_date > $exp_date and $todays_date_klo > $exp_date_klo) {
			echo "<td class='punainen toggleable_parent_row_status' id='1__{$row['lahdon_tunnus']}__{$y}'></td>";
		}
		else if (($todays_date == $exp_date and $todays_date_klo > $exp_date_klo) or $row['vakisin_kerays'] != '') {
			echo "<td class='vihrea toggleable_parent_row_status' id='2__{$row['lahdon_tunnus']}__{$y}'></td>";
		}
		else {
			echo "<td class='keltainen toggleable_parent_row_status' id='3__{$row['lahdon_tunnus']}__{$y}'></td>";
		}

		echo "<td><input type='checkbox' class='checkall' name='{$row['lahdon_tunnus']}'></td>";
		echo "<td class='toggleable center toggleable_parent_row_departure' id='{$row['lahdon_tunnus']}__{$y}'><button type='button'>{$row['lahdon_tunnus']}</button></td>";
		echo "<td class='center toggleable_parent_row_prio' id='{$row['prioriteetti']}__{$row['lahdon_tunnus']}__{$y}'>{$row['prioriteetti']}</td>";
		echo "<td class='toggleable_parent_row_delivery' id='{$row['toimitustapa']}__{$row['lahdon_tunnus']}__{$y}'>{$row['toimitustapa']}</td>";
		echo "<td class='center toggleable_parent_row_date' id='{$row['lahdon_pvm']}__{$row['lahdon_tunnus']}__{$y}'>",tv1dateconv($row['lahdon_pvm']),"</td>";
		echo "<td class='center toggleable_parent_row_time1' id='{$row['viimeinen_tilausaika']}__{$row['lahdon_tunnus']}__{$y}'>{$row['viimeinen_tilausaika']}</td>";

		echo "<td class='center'>";

		$exp_date = strtotime($row['lahdon_pvm'].' '.$row['lahdon_kellonaika'].':00');
		$todays_date = strtotime(date('Y-m-d H:i:s'));

		if ($todays_date > $exp_date) {
			echo "<font class='error'>{$row['lahdon_kellonaika']}</font>";
		}
		else {
			echo $row['lahdon_kellonaika'];
		}

		echo "</td>";

		echo "<td class='center'>{$row['kerailyn_aloitusaika']}</td>";
		echo "<td class='center'>{$row['tilatut']} / {$row['valmiina']}</td>";
		echo "<td class='center'>{$row['suunnittelussa']} / {$row['keratyt']}</td>";
		echo "<td class='center'>{$row['kg_suun']} / {$row['kg_ker']}</td>";
		echo "<td class='center'>{$row['litrat_suun']} / {$row['litrat_ker']}</td>";

		echo "</tr>";

		$query = "	SELECT lasku.tunnus AS 'tilauksen_tunnus',
					lasku.nimi AS 'asiakas_nimi',
					lasku.toim_nimi AS 'asiakas_toim_nimi',
					lasku.toim_postitp AS 'asiakas_toim_postitp',
					lasku.prioriteettinro AS 'prioriteetti',
					kerayserat.nro AS 'erat',
					kerayserat.sscc,
					kerayserat.pakkausnro AS 'pakkausnumerot',
					GROUP_CONCAT(DISTINCT kerayserat.tila) AS 'tilat',
					COUNT(kerayserat.tunnus) AS 'keraysera_rivi_count',
					SUM(IF((kerayserat.tila = 'T' OR kerayserat.tila = 'R'), 1, 0)) AS 'keraysera_rivi_valmis'
					FROM lasku
					LEFT JOIN kerayserat ON (kerayserat.yhtio = lasku.yhtio AND kerayserat.otunnus = lasku.tunnus)
					LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
					JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tunnus IN ({$row['tilaukset']})
					GROUP BY 1,2,3,4,5,6,7,8";
		$lahto_res = pupe_query($query);

		echo "<tr class='toggleable_tr' id='toggleable_tr_{$row['lahdon_tunnus']}__{$y}'>";
		echo "<td colspan='13' class='back'>";
		echo "<div id='toggleable_{$row['lahdon_tunnus']}__{$y}' style='display:none;'>";

		echo "<table style='width:100%; padding:0px; margin:0px; border:0px;'>";

		echo "<tr class='header_row_{$row['lahdon_tunnus']}__{$y}'>";
		echo "<th></th>";
		echo "<th class='sort_row_by' id='row_status'>",t("Status")," <img class='row_direction_status' /></th>";
		echo "<th class='sort_row_by' id='row_prio'>",t("Prio")," <img class='row_direction_prio' /></th>";
		echo "<th class='sort_row_by' id='row_order'>",t("Tilausnumero")," <img class='row_direction_order' /></th>";
		echo "<th class='sort_row_by' id='row_client'>",t("Asiakas")," <img class='row_direction_client' /></th>";
		echo "<th class='sort_row_by' id='row_locality'>",t("Paikkakunta")," <img class='row_direction_locality' /></th>";
		echo "<th class='sort_row_by' id='row_picking_zone'>",t("Keräysvyöhyke")," <img class='row_direction_picking_zone' /></th>";
		echo "<th class='sort_row_by' id='row_batch'>",t("Erä")," <img class='row_direction_batch' /></th>";
		echo "<th class='sort_row_by' id='row_rows'>",t("Rivit")," / ",t("Kerätyt")," <img class='row_direction_rows' /></th>";
		echo "<th class='sort_row_by' id='row_sscc'>",t("SSCC")," <img class='row_direction_sscc' /></th>";
		echo "<th class='sort_row_by' id='row_package'>",t("Pakkaus")," <img class='row_direction_package' /></th>";
		echo "<th class='sort_row_by' id='row_weight'>",t("Paino")," <img class='row_direction_weight' /></th>";
		echo "</tr>";

		$x = 0;

		while ($lahto_row = mysql_fetch_assoc($lahto_res)) {

			echo "<tr class='toggleable_row_tr' id='toggleable_row_tr_{$lahto_row['tilauksen_tunnus']}__{$x}'>";

			echo "<td><input type='checkbox' class='checkbox_{$row['lahdon_tunnus']}'></td>";

			$status = $status_text = '';

			if (strpos($lahto_row['tilat'], "K") !== FALSE) {
				$status_text = t("Aloitettu");
				$status = 2;
			}
			elseif ($lahto_row['keraysera_rivi_count'] > 0 and $lahto_row['keraysera_rivi_count'] == $lahto_row['keraysera_rivi_valmis']) {
				$status_text = t("Kerätty");
				$status = 3;
			}
			else {
				$status_text = t("Aloittamatta");
				$status = 1;
			}

			echo "<td class='data toggleable_row_status' id='{$status}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$status_text}</td>";

			echo "<td class='center toggleable_row_prio' id='{$lahto_row['prioriteetti']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['prioriteetti']}</td>";
			echo "<td class='toggleable_row_order' id='{$lahto_row['tilauksen_tunnus']}__{$x}'><button type='button'>{$lahto_row['tilauksen_tunnus']}</button></td>";

			echo "<td class='data toggleable_row_client' id='{$lahto_row['asiakas_nimi']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['asiakas_nimi']}";
			if ($lahto_row['asiakas_nimi'] != $lahto_row['asiakas_toim_nimi']) echo "<br>{$lahto_row['asiakas_toim_nimi']}";
			echo "</td>";

			echo "<td class='toggleable_row_locality' id='{$lahto_row['asiakas_toim_postitp']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['asiakas_toim_postitp']}</td>";

			$query = "	SELECT keraysvyohyke.nimitys AS 'keraysvyohyke',
						COUNT(tilausrivi.tunnus) AS 'rivit',
						SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS 'keratyt',
						ROUND(SUM(tilausrivi.varattu * tuote.tuotemassa), 0) AS 'kg'
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						JOIN keraysvyohyke ON (keraysvyohyke.yhtio = tuote.yhtio AND keraysvyohyke.tunnus = tuote.keraysvyohyke)
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.otunnus = '{$lahto_row['tilauksen_tunnus']}'
						GROUP BY 1";
			$til_res = pupe_query($query);
			$til_row = mysql_fetch_assoc($til_res);

			echo "<td class='data toggleable_row_picking_zone' id='{$til_row['keraysvyohyke']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$til_row['keraysvyohyke']}</td>";

			echo "<td class='data toggleable_row_batch' id='{$lahto_row['erat']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['erat']}</td>";

			echo "<td class='toggleable_row_rows' id='{$til_row['rivit']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$til_row['rivit']} / {$til_row['keratyt']}</td>";

			echo "<td class='data toggleable_row_sscc' id='{$lahto_row['sscc']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>";

			if ($lahto_row['sscc'] != '') {
				echo "<button type='button'>{$lahto_row['sscc']}</button>";
			}
			echo "</td>";

			if ($lahto_row['pakkausnumerot'] != '') {

				$query = "	SELECT pakkaus.pakkauskuvaus
							FROM kerayserat
							JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
							WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
							AND kerayserat.sscc IN ({$lahto_row['sscc']})
							AND kerayserat.pakkausnro = '{$lahto_row['pakkausnumerot']}'
							ORDER BY kerayserat.sscc";
				$pakkauskuvaus_res = pupe_query($query);
				$pakkauskuvaus_row = mysql_fetch_assoc($pakkauskuvaus_res);

				echo "<td class='data toggleable_row_package' id='{$pakkauskuvaus_row['pakkauskuvaus']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$pakkauskuvaus_row['pakkauskuvaus']}</td>";
			}
			else {
				echo "<td class='data toggleable_row_package' id='!__{$lahto_row['tilauksen_tunnus']}__{$x}'></td>";
			}

			echo "<td class='toggleable_row_weight' id='{$til_row['kg']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$til_row['kg']}</td>";

			echo "</tr>";

			$query = "	SELECT tilausrivi.tuoteno,
						kerayserat.sscc,
						tuote.nimitys,
						kerayserat.kpl,
						tilausrivi.yksikko,
						CONCAT(tilausrivi.hyllyalue,'-',tilausrivi.hyllynro,'-',tilausrivi.hyllyvali,'-',tilausrivi.hyllytaso) AS 'hyllypaikka',
						kerayserat.laatija AS 'keraaja',
						SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS 'keratyt'
						FROM tilausrivi
						LEFT JOIN kerayserat ON (kerayserat.yhtio = tilausrivi.yhtio AND kerayserat.tilausrivi = tilausrivi.tunnus)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.otunnus = '{$lahto_row['tilauksen_tunnus']}'
						GROUP BY 1,2,3,4,5,6,7";
			$rivi_res = pupe_query($query);
			
			echo "<tr class='toggleable_row_child_order_{$lahto_row['tilauksen_tunnus']}__{$x}'>";
			echo "<td colspan='12' class='back'>";
			echo "<div class='toggleable_row_child' id='toggleable_row_order_{$lahto_row['tilauksen_tunnus']}__{$x}' style='display:none;'>";

			echo "<table style='width:100%;'>";
	
			echo "<tr>";
			echo "<th>",t("SSCC"),"</th>";
			echo "<th>",t("Tuotenumero"),"</th>";
			echo "<th>",t("Nimitys"),"</th>";
			echo "<th>",t("Suunniteltu määrä"),"</th>";
			echo "<th>",t("Kerätty määrä"),"</th>";
			echo "<th>",t("Yksikkö"),"</th>";
			echo "<th>",t("Hyllypaikka"),"</th>";
			echo "<th>",t("Kerääjä"),"</th>";
			echo "</tr>";
			
			while ($rivi_row = mysql_fetch_assoc($rivi_res)) {
				echo "<tr>";
				echo "<td class='tumma'>{$rivi_row['sscc']}</td>";
				echo "<td class='tumma'>{$rivi_row['tuoteno']}</td>";
				echo "<td class='tumma'>{$rivi_row['nimitys']}</td>";
				echo "<td class='tumma'>{$rivi_row['kpl']}</td>";
				echo "<td class='tumma'>{$rivi_row['keratyt']}</td>";
				echo "<td class='tumma'>",t_avainsana("Y", "", " and avainsana.selite='{$rivi_row['yksikko']}'", "", "", "selite"),"</td>";
				echo "<td class='tumma'>{$rivi_row['hyllypaikka']}</td>";
				echo "<td class='tumma'>{$rivi_row['keraaja']}</td>";
				echo "</tr>";
			}
			
			echo "</table>";
			
			echo "</div>";
			echo "</td>";
			echo "</tr>";

			reset($arr);

			$query = "	SELECT tilausrivi.tuoteno,
						kerayserat.otunnus,
						tuote.nimitys,
						kerayserat.kpl,
						tilausrivi.yksikko,
						CONCAT(tilausrivi.hyllyalue,'-',tilausrivi.hyllynro,'-',tilausrivi.hyllyvali,'-',tilausrivi.hyllytaso) AS hyllypaikka,
						kerayserat.laatija AS keraaja,
						SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS keratyt
						FROM kerayserat
						JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
						AND kerayserat.sscc = '{$lahto_row['sscc']}'
						GROUP BY 1,2,3,4,5,6,7";
			$rivi_res = pupe_query($query);

			echo "<tr class='toggleable_row_child_sscc_{$lahto_row['tilauksen_tunnus']}__{$x}'>";
			echo "<td colspan='12' class='back'>";
			echo "<div class='toggleable_row_child' id='toggleable_row_sscc_{$lahto_row['sscc']}_{$x}' style='display:none;'>";

			echo "<table style='width:100%;'>";

			echo "<tr>";
			echo "<th>",t("Tilausnumero"),"</th>";
			echo "<th>",t("Tuotenumero"),"</th>";
			echo "<th>",t("Nimitys"),"</th>";
			echo "<th>",t("Suunniteltu määrä"),"</th>";
			echo "<th>",t("Kerätty määrä"),"</th>";
			echo "<th>",t("Yksikkö"),"</th>";
			echo "<th>",t("Hyllypaikka"),"</th>";
			echo "<th>",t("Kerääjä"),"</th>";
			echo "</tr>";

			while ($rivi_row = mysql_fetch_assoc($rivi_res)) {
				echo "<tr>";
				echo "<td class='tumma'>{$rivi_row['otunnus']}</td>";
				echo "<td class='tumma'>{$rivi_row['tuoteno']}</td>";
				echo "<td class='tumma'>{$rivi_row['nimitys']}</td>";
				echo "<td class='tumma'>{$rivi_row['kpl']}</td>";
				echo "<td class='tumma'>{$rivi_row['keratyt']}</td>";
				echo "<td class='tumma'>",t_avainsana("Y", "", " and avainsana.selite='{$rivi_row['yksikko']}'", "", "", "selite"),"</td>";
				echo "<td class='tumma'>{$rivi_row['hyllypaikka']}</td>";
				echo "<td class='tumma'>{$rivi_row['keraaja']}</td>";
				echo "</tr>";
			}

			echo "</table>";

			echo "</div>";
			echo "</td>";
			echo "</tr>";

			$x++;
		}

		echo "</table>";

		echo "</div>";
		echo "</td>";
		echo "</tr>";

		$y++;
	}

	echo "</table>";

	require ("inc/footer.inc");