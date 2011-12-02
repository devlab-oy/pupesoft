<?php

	require ("../inc/parametrit.inc");

	enable_jquery();

	echo "	<script type='text/javascript' language='JavaScript'>
				<!--

				$.expr[':'].containsi = function(a,i,m){
				    return $(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
				};

				$(document).ready(function() {

					// laitetaan jokaiselle TD:lle padding 0, jotta saadaan mahdollisimman paljon tietoa näkyviin samaan aikaan, nowrap kuitenkin jotta tekstit olisivat luettavammassa muodossa
					$('td').css({'padding': '0px', 'white-space': 'nowrap'});

					// tilaustyyppi-kentässä halutaan wrapata teksti, koska ne voivat olla tosi pitkiä
					$('.toggleable_row_type').css({'white-space': 'pre-wrap'});

					// jos asiakkaan nimi on yli 30 merkkiä pitkä, wrapataan TD
					$('.toggleable_row_client').each(function() {
						if ($(this).html().length > 30) {
							$(this).css({'white-space': 'pre-wrap'});
						}
					});

					// laitetaan pikkasen paddingia vasemmalle ja oikealle puolelle data ja center sarakkeisiin.
					$('.center').css({'text-align': 'center', 'padding-left': '7px', 'padding-right': '7px'});
					$('.data').css({'padding-left': '7px', 'padding-right': '7px', 'padding-bottom': '0px', 'padding-top': '0px'});

					$('.vihrea').css({'background-image': 'url(\"{$palvelin2}pics/vaaleanvihrea.png\")'});
					$('.keltainen').css({'background-image': 'url(\"{$palvelin2}pics/keltainen.png\")'});
					$('.punainen').css({'background-image': 'url(\"{$palvelin2}pics/punainen.png\")'});

					// oletuksena ollaan sortattu 2. tason rivit nousevaan järjestykseen tilausnumeron mukaan
					$('.row_direction_order').attr('src', '{$palvelin2}pics/lullacons/arrow-double-up-green.png');

					// nappien click eventti
					$(':checkbox').live('click', function(event){
						event.stopPropagation();

						$(this).is(':checked') ? $(this).parent().parent().addClass('tumma') : $(this).parent().parent().removeClass('tumma');
					});

					// numeroiden vertailu
					function compareId(a, b) {
						return b.id - a.id;
					}

					// tekstin vertailu
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

					// uniikit arvot arrayssa
					function sort_unique(arr) {
						arr = arr.sort(function (a, b) { return a*1 - b*1; });
						var ret = [arr[0]];
						for (var i = 1; i < arr.length; i++) { // start loop at 1 as element 0 can never be a duplicate
							if (arr[i-1] !== arr[i]) {
								ret.push(arr[i]);
							}
						}

						return ret;
					}

					// 2. tason alasvetovalikolla filteröinti
					$('.filter_row_by_select').live('change', function(event) {

						event.stopPropagation();

						$('.toggleable_row_tr').hide();

						var empty_all = true;

						var selected = '';

						$('.filter_row_by_select option:selected').each(function() {

							if ($(this).val() != '') {
								empty_all = false;

								var title = $(this).parent().attr('id').substring(17);

								if (selected != '') {
									selected = $(selected).children().filter('td[id^=\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\"][class~=\"toggleable_row_'+title+'\"]').parent();
								}
								else {
									selected = $('td[id^=\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\"][class~=\"toggleable_row_'+title+'\"]').parent();
								}
							}
						});

						if (empty_all) {
							$('.toggleable_row_tr').show();
						}
						else {
							$(selected).show();
							$(this).attr('selected', true);
						}
					});

					// 2. tason tekstikentällä rajaaminen
					$('.filter_row_by_text').live('keyup', function(event) {
						event.stopPropagation();

						$('.toggleable_row_tr').hide();

						var empty_all = true;

						var selectedx = '';

						$('.filter_row_by_select option:selected').each(function() {

							if ($(this).val() != '') {
								empty_all = false;

								var title = $(this).parent().attr('id').substring(17);

								if (selectedx != '') {
									selectedx = $(selectedx).children().filter('td[id^=\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\"][class~=\"toggleable_row_'+title+'\"]').parent();
								}
								else {
									selectedx = $('td[id^=\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\"][class~=\"toggleable_row_'+title+'\"]').parent();
								}
							}
						});

						$('.filter_row_by_text').each(function() {

							if (selectedx != '' && $(this).val() != '') {
								var tmp = $(this).attr('id').substring(15).split(\"__\", 3);
								var title = tmp[0];
								selectedx = $(selectedx).children().filter('.toggleable_row_'+title+':containsi(\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\")').parent();

								if (selectedx != '') {
									empty_all = false;
								}
							}
							else if (selectedx == '' && $(this).val() != '') {
								var tmp = $(this).attr('id').substring(15).split(\"__\", 3);
								var title = tmp[0];
								selectedx = $('.toggleable_row_'+title+':containsi(\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\")').parent();

								if (selectedx != '') {
									empty_all = false;
								}
							}
							else if (selectedx != '' && $(this).val() == '') {
								empty_all = false;
							}
							else {
								//$('.toggleable_row_tr').show();
								//return true;
								// empty_all = true;
							}
						})

						if (empty_all) {
							$('.toggleable_row_tr').show();
						}
						else {
							$(selectedx).show();
						}
					});

					// 1. tason lähdön napin eventti
					$('.toggleable').live('click', function(event){

						var id = this.id.split(\"__\", 2);

						if ($('#toggleable_'+id[0]+'__'+id[1]).is(':visible')) {

							$('.filter_row_by_text:visible').val('');
							$('.filter_row_by_select:visible').each(function() {
								$(this).children('option:first').attr('selected', true);
							});

							$('.toggleable_row_child_div_order:visible, .toggleable_row_child_div_sscc:visible').hide();
							$('#toggleable_'+id[0]+'__'+id[1]).hide();

							$('.toggleable_row_order').removeClass('tumma');
							$('.toggleable_row_sscc').removeClass('tumma');

							$('.toggleable_row_tr').hide();

							$('tr[id!=\"toggleable_parent_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_parent\"]').show();
							$('tr[id!=\"toggleable_tr_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_tr\"]').stop().show();

							$('.filter_parent_row_by').attr('disabled', false).trigger('change');

							$(':checkbox').attr('checked', false).parent().parent().removeClass('tumma');
						}
						else {

							$('.filter_parent_row_by').attr('disabled', true);

							var parent_element = $('#toggleable_'+id[0]+'__'+id[1]).parent();

							$('.toggleable_row_tr').show();

							$('tr[id!=\"toggleable_parent_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_parent\"]').hide();
							$('tr[id!=\"toggleable_tr_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_tr\"]').hide();

							$('div[id!=\"toggleable_row_order_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_row_child_div_order\"]')
								.parent()
								.parent()
								.hide()
								.next()
								.hide();

							$('#toggleable_'+id[0]+'__'+id[1]).css({'width': parent_element.width()+'px', 'padding-top': '15px'}).show();
						}
					});

					// 2. tason tilausnumeronapin eventti
					$('.toggleable_row_order').live('click', function(event){

						var parent = $(this).parent().parent().parent().parent();
						var parent_id = $(parent).attr('id');

						var id = this.id.split(\"__\", 2);

						if ($('#toggleable_row_order_'+id[0]+'__'+id[1]).is(':visible')) {

							$(this).removeClass('tumma');

							$('#toggleable_row_order_'+id[0]+'__'+id[1]).slideUp('fast');
							$('#toggleable_row_order_'+id[0]+'__'+id[1]).parent().hide().parent().hide();

							if ($('.toggleable_row_child_div_order:visible, .toggleable_row_child_div_sscc:visible').length == 0) {

								$('#'+parent_id).children().children().children('tr[id!=\"toggleable_row_tr_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_row_tr\"]').show().next().hide().next().hide();

								var text_search = false;

								$('.filter_row_by_text:visible').attr('disabled', false).each(function() {
									if ($(this).val() != '') {
										$(this).trigger('keyup');
										text_search = true;
									}
								});

								$('.filter_row_by_select:visible').attr('disabled', false);

								if (!text_search) {
									$('.filter_row_by_select:visible').trigger('change');
								}
							}
						}
						else {

							$('.filter_row_by_select:visible, .filter_row_by_text:visible').attr('disabled', true);

							$(this).addClass('tumma');

							var parent_element = $('#toggleable_row_order_'+id[0]+'__'+id[1]).parent();

							$('#'+parent_id).children().children().children('tr[id!=\"toggleable_row_tr_'+id[0]+'__'+id[1]+'\"][class=\"toggleable_row_tr\"]').hide().next().hide().next().hide();

							$('#toggleable_row_order_'+id[0]+'__'+id[1]).parent().parent().show();
							$('#toggleable_row_order_'+id[0]+'__'+id[1]).parent().show();
							$('#toggleable_row_order_'+id[0]+'__'+id[1]).css({'width': parent_element.width()+'px', 'padding-bottom': '15px'}).delay(1).slideDown('fast');
						}
					});

					// 2. tason sscc-napin eventti
					$('.toggleable_row_sscc').live('click', function(event){

						if ($(this).html() != '') {

							var parent = $(this).parent().parent().parent().parent();
							var parent_id = $(parent).attr('id');

							var id = this.id.split(\"__\", 3);

							if ($('#toggleable_row_sscc_'+id[0]+'__'+id[2]).is(':visible')) {
								$(this).removeClass('tumma');

								$('#toggleable_row_sscc_'+id[0]+'__'+id[2]).slideUp('fast');
								$('#toggleable_row_sscc_'+id[0]+'__'+id[2]).parent().hide().parent().hide();

								if ($('.toggleable_row_child_div_order:visible, .toggleable_row_child_div_sscc:visible').length == 0) {

									$('#'+parent_id).children().children().children('tr[id!=\"toggleable_row_tr_'+id[1]+'__'+id[2]+'\"][class=\"toggleable_row_tr\"]').show().next().hide().next().hide();

									var text_search = false;

									$('.filter_row_by_text:visible').attr('disabled', false).each(function() {
										if ($(this).val() != '') {
											$(this).trigger('keyup');
											text_search = true;
										}
									});

									$('.filter_row_by_select:visible').attr('disabled', false);

									if (!text_search) {
										$('.filter_row_by_select:visible').trigger('change');
									}
								}
							}
							else {

								$('.filter_row_by_select:visible, .filter_row_by_text:visible').attr('disabled', true);

								$(this).addClass('tumma');

								var parent_element = $('#toggleable_row_sscc_'+id[0]+'__'+id[2]).parent();

								$('#'+parent_id).children().children().children('tr[id!=\"toggleable_row_tr_'+id[1]+'__'+id[2]+'\"][class=\"toggleable_row_tr\"]').hide().next().hide().next().hide();

								$('#toggleable_row_sscc_'+id[0]+'__'+id[2]).parent().parent().show();
								$('#toggleable_row_sscc_'+id[0]+'__'+id[2]).parent().show();
								$('#toggleable_row_sscc_'+id[0]+'__'+id[2]).css({'width': parent_element.width()+'px', 'padding-bottom': '15px'}).delay(1).slideDown('fast');
							}
						}

					});

					// 1. tason alasvetovalikolla filteröinti
					$('.filter_parent_row_by').live('change', function(event) {

						event.stopPropagation();

						$('.toggleable_parent').hide();

						var empty_all = true;

						var selected = '';

						$('.filter_parent_row_by option:selected').each(function() {

							if ($(this).val() != '') {
								empty_all = false;

								var title = $(this).parent().attr('id').substring(18);

								if (selected != '') {
									selected = $(selected).children().filter('td[id^=\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\"][class~=\"toggleable_parent_row_'+title+'\"]').parent();
								}
								else {
									selected = $('td[id^=\"'+$(this).val().replace(/(:|\.)/g,'\\$1')+'\"][class~=\"toggleable_parent_row_'+title+'\"]').parent();
								}
							}
						});

						if (empty_all) {
							$('.toggleable_parent').show();
						}
						else {
							$(selected).show();
							$(this).attr('selected', true);
						}
					});

					// tehdään 2. tason sorttausnuolista globaalit muuttujat dynaamisesti, jotta muistetaan missä asennoissa ne oli
					$('.sort_row_by').each(function() {
						var title_sort = this.id.substring(4);

						window['sort_row_direction_'+title_sort] = false;

					});

					// tehdään 1. tason sorttausnuolista globaalit muuttujat dynaamisesti, jotta muistetaan missä asennoissa ne oli
					$('.sort_parent_row_by').each(function() {
						var title_sort = this.id.substring(11);
						window['sort_parent_row_direction_'+title_sort] = false;
					});

					// 1. ja 2. tason sarakkeiden sorttaus
					$('.sort_row_by, .sort_parent_row_by').click(function(event) {

						if (event.target != this) {
							return true;
						}

						var parent_sort = $(this).hasClass('sort_parent_row_by') ? true : false;

						var title = parent_sort ? this.id.substring(11) : this.id.substring(4);

						if (parent_sort) {

							var arr = $('.toggleable_parent:visible');
							var _arr = new Array();

							var _arrChild = new Array();

							for (i = 0; i < arr.length; i++) {
								var row = arr[i];

								var id = $(row).children('.toggleable_parent_row_'+title).attr('id').replace(/(:|\.)/g,'\\$1');

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

								if (title == 'delivery' || title == 'date' || title == 'time1' || title == 'time2' || title == 'time3') {
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

								$('.parent_row_direction_'+title).attr('src', '{$palvelin2}pics/lullacons/arrow-double-up-green.png').show();
								$('.sort_parent_row_by').children('img[class!=\"parent_row_direction_'+title+'\"]').hide();

								window['sort_parent_row_direction_'+title] = false;
							}
							else {

								if (title == 'delivery' || title == 'date' || title == 'time1' || title == 'time2' || title == 'time3') {
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

								$('.parent_row_direction_'+title).attr('src', '{$palvelin2}pics/lullacons/arrow-double-down-green.png').show();
								$('.sort_parent_row_by').children('img[class!=\"parent_row_direction_'+title+'\"]').hide();

								window['sort_parent_row_direction_'+title] = true;
							}

						}
						else {

							var tmp = title.split(\"__\", 3);
							title = tmp[0];
							title_id = tmp[1];
							title_counter = tmp[2];

							var arr = $('.toggleable_row_tr:visible');
							var _arr = new Array();

							var _arrChildOrder = new Array();
							var _arrChildSscc = new Array();

							for (i = 0; i < arr.length; i++) {
								var row = arr[i];

								var id = $(row).children('.toggleable_row_'+title).attr('id');
								var counter = 0;

								if (title == 'status' || title == 'prio' || title == 'client' || title == 'locality' || title == 'picking_zone' || title == 'batch' || title == 'sscc' || title == 'package' || title == 'weight' || title == 'type') {
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

								if (title == 'client' || title == 'locality' || title == 'picking_zone' || title == 'package' || title == 'type') {
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

								$('.row_direction_'+title).attr('src', '{$palvelin2}pics/lullacons/arrow-double-up-green.png').show();
								$('.sort_row_by').children('img[class!=\"row_direction_'+title+'\"]').hide();

								window['sort_row_direction_'+title] = false;
							}
							else {

								if (title == 'client' || title == 'locality' || title == 'picking_zone' || title == 'package' || title == 'type') {
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

								$('.row_direction_'+title).attr('src', '{$palvelin2}pics/lullacons/arrow-double-down-green.png').show();
								$('.sort_row_by').children('img[class!=\"row_direction_'+title+'\"]').hide();

								window['sort_row_direction_'+title] = true;
							}
						}
					});

					// 1. tason checkboxin eventti
					$('.checkall').click(function(){

						var id = $(this).attr('name');

						$(this).is(':checked') ? $('.checkbox_'+id).attr('checked', true).parent().parent().addClass('tumma') : $('.checkbox_'+id).attr('checked', false).parent().parent().removeClass('tumma');
					});
				});

				//-->
			</script>";

	echo "<font class='head'>",t("Lähtöjen hallinta"),"</font><hr>";

	$query = "	SELECT lahdot.tunnus AS 'lahdon_tunnus',
				lahdot.pvm AS 'lahdon_pvm',
				lahdot.vakisin_kerays,
				SUBSTRING(lahdot.viimeinen_tilausaika, 1, 5) AS 'viimeinen_tilausaika',
				SUBSTRING(lahdot.lahdon_kellonaika, 1, 5) AS 'lahdon_kellonaika',
				SUBSTRING(lahdot.kerailyn_aloitusaika, 1, 5) AS 'kerailyn_aloitusaika',
				avainsana.selitetark_3 AS 'prioriteetti',
				toimitustapa.selite AS 'toimitustapa',
				toimitustapa.rahdinkuljettaja,
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
				JOIN avainsana ON (avainsana.yhtio = lahdot.yhtio AND avainsana.laji = 'ASIAKASLUOKKA' AND avainsana.kieli = '{$yhtiorow['kieli']}' AND avainsana.selite = lahdot.asiakasluokka)
				JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa)
				JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus)
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))
				GROUP BY 1,2,3,4,5,6,7,8,9
				ORDER BY lahdot.pvm, lahdot.lahdon_kellonaika, lahdot.tunnus";
	$result = pupe_query($query);

	$deliveries = $dates = $priorities = $carriers = array();

	while ($row = mysql_fetch_assoc($result)) {
		$deliveries[$row['toimitustapa']] = $row['toimitustapa'];
		$dates[$row['lahdon_pvm']] = $row['lahdon_pvm'];
		$priorities[$row['prioriteetti']] = $row['prioriteetti'];
		$carriers[$row['rahdinkuljettaja']] = $row['rahdinkuljettaja'];
	}

	echo "<table>";
	echo "<tr class='header_parent'>";

	echo "<th class='sort_parent_row_by' id='parent_row_status'>",t("Status")," <img class='parent_row_direction_status' />";
	echo "<br />";
	echo "<select class='filter_parent_row_by' id='parent_row_select_status'>";
	echo "<option value=''>",t("Valitse"),"</option>";
	echo "<option value='3'>",t("Aloittamatta"),"</option>";
	echo "<option value='2'>",t("Aloitettu"),"</option>";
	echo "<option value='1'>",t("Aika ylitetty"),"</option>";
	echo "</select>";
	echo "</th>";

	echo "<th></th>";
	echo "<th class='sort_parent_row_by' id='parent_row_departure'>",t("Lähtö")," <img class='parent_row_direction_departure' /></th>";

	echo "<th class='sort_parent_row_by' id='parent_row_prio'>",t("Prio")," <img class='parent_row_direction_prio' />";
	echo "<br />";
	echo "<select class='filter_parent_row_by' id='parent_row_select_prio'>";
	echo "<option value=''>",t("Valitse"),"</option>";

	sort($priorities);

	foreach ($priorities AS $prio) {
		echo "<option value='{$prio}'>{$prio}</option>";
	}

	echo "</select>";
	echo "</th>";

	echo "<th class='sort_parent_row_by' id='parent_row_carrier'>",t("Rahdinkuljettaja")," <img class='parent_row_direction_carrier' />";
	echo "<br />";
	echo "<select class='filter_parent_row_by' id='parent_row_select_carrier'>";
	echo "<option value=''>",t("Valitse"),"</option>";

	sort($carriers);

	foreach ($carriers AS $carr) {
		echo "<option value='{$carr}'>{$carr}</option>";
	}

	echo "</select>";
	echo "</th>";

	echo "<th class='sort_parent_row_by' id='parent_row_delivery'>",t("Toimitustapa")," <img class='parent_row_direction_delivery' />";
	echo "<br />";
	echo "<select class='filter_parent_row_by' id='parent_row_select_delivery'>";
	echo "<option value=''>",t("Valitse"),"</option>";

	sort($deliveries);

	foreach ($deliveries AS $deli) {
		echo "<option value='{$deli}'>{$deli}</option>";
	}

	echo "</select>";
	echo "</th>";

	echo "<th class='sort_parent_row_by' id='parent_row_date'>",t("Pvm")," <img class='parent_row_direction_date' />";
	echo "<br />";
	echo "<select class='filter_parent_row_by' id='parent_row_select_date'>";
	echo "<option value=''>",t("Valitse"),"</option>";

	sort($dates);

	foreach ($dates AS $pvm) {
		echo "<option value='",tv1dateconv($pvm),"'>",tv1dateconv($pvm),"</option>";
	}

	echo "</select>";
	echo"</th>";

	echo "<th class='sort_parent_row_by' id='parent_row_time1'>",t("Viim til klo")," <img class='parent_row_direction_time1' /></th>";
	echo "<th class='sort_parent_row_by' id='parent_row_time2'>",t("Lähtöaika")," <img class='parent_row_direction_time2' /></th>";
	echo "<th class='sort_parent_row_by' id='parent_row_time3'>",t("Ker. alku klo")," <img class='parent_row_direction_time3' /></th>";
	echo "<th class='sort_parent_row_by' id='parent_row_orders'>",t("Til / valm")," <img class='parent_row_direction_orders' /></th>";
	echo "<th class='sort_parent_row_by' id='parent_row_rows'>",t("Rivit suun / ker")," <img class='parent_row_direction_rows' /></th>";
	echo "<th class='sort_parent_row_by' id='parent_row_weight'>",t("Kg suun / ker")," <img class='parent_row_direction_weight' /></th>";
	echo "<th class='sort_parent_row_by' id='parent_row_liters'>",t("Litrat suun / ker")," <img class='parent_row_direction_liters' /></th>";
	echo "</tr>";

	mysql_data_seek($result, 0);

	$y = 0;

	while ($row = mysql_fetch_assoc($result)) {

		echo "<tr class='toggleable_parent' id='toggleable_parent_{$row['lahdon_tunnus']}__{$y}'>";

		$exp_date = strtotime($row['lahdon_pvm']);
		$exp_date_klo = strtotime($row['kerailyn_aloitusaika'].':00');
		$todays_date = strtotime(date('Y-m-d'));
		$todays_date_klo = strtotime(date('H:i:s'));

		if (($todays_date == $exp_date and $todays_date_klo > $exp_date_klo) or $row['vakisin_kerays'] != '') {
			echo "<td class='vihrea toggleable_parent_row_status' id='2__{$row['lahdon_tunnus']}__{$y}'></td>";
		}
		else if ($todays_date >= $exp_date and $todays_date_klo > $exp_date_klo) {
			echo "<td class='punainen toggleable_parent_row_status' id='1__{$row['lahdon_tunnus']}__{$y}'></td>";
		}
		else {
			echo "<td class='keltainen toggleable_parent_row_status' id='3__{$row['lahdon_tunnus']}__{$y}'></td>";
		}

		echo "<td><input type='checkbox' class='checkall' name='{$row['lahdon_tunnus']}'></td>";
		echo "<td class='toggleable center toggleable_parent_row_departure' id='{$row['lahdon_tunnus']}__{$y}'><button type='button'>{$row['lahdon_tunnus']}</button></td>";
		echo "<td class='center toggleable_parent_row_prio' id='{$row['prioriteetti']}__{$row['lahdon_tunnus']}__{$y}'>{$row['prioriteetti']}</td>";
		echo "<td class='toggleable_parent_row_carrier' id='{$row['rahdinkuljettaja']}__{$row['lahdon_tunnus']}__{$y}'>{$row['rahdinkuljettaja']}</td>";
		echo "<td class='toggleable_parent_row_delivery' id='{$row['toimitustapa']}__{$row['lahdon_tunnus']}__{$y}'>{$row['toimitustapa']}</td>";
		echo "<td class='center toggleable_parent_row_date' id='",tv1dateconv($row['lahdon_pvm']),"__{$row['lahdon_tunnus']}__{$y}'>",tv1dateconv($row['lahdon_pvm']),"</td>";
		echo "<td class='center toggleable_parent_row_time1' id='{$row['viimeinen_tilausaika']}__{$row['lahdon_tunnus']}__{$y}'>{$row['viimeinen_tilausaika']}</td>";

		echo "<td class='center toggleable_parent_row_time2' id='{$row['lahdon_kellonaika']}__{$row['lahdon_tunnus']}__{$y}'>";

		$exp_date = strtotime($row['lahdon_pvm'].' '.$row['lahdon_kellonaika'].':00');
		$todays_date = strtotime(date('Y-m-d H:i:s'));

		if ($todays_date > $exp_date) {
			echo "<font class='error'>{$row['lahdon_kellonaika']}</font>";
		}
		else {
			echo $row['lahdon_kellonaika'];
		}

		echo "</td>";

		echo "<td class='center toggleable_parent_row_time3' id='{$row['kerailyn_aloitusaika']}__{$row['lahdon_tunnus']}__{$y}'>{$row['kerailyn_aloitusaika']}</td>";
		echo "<td class='center toggleable_parent_row_orders' id='{$row['tilatut']}__{$row['lahdon_tunnus']}__{$y}'>{$row['tilatut']} / {$row['valmiina']}</td>";
		echo "<td class='center toggleable_parent_row_rows' id='{$row['suunnittelussa']}__{$row['lahdon_tunnus']}__{$y}'>{$row['suunnittelussa']} / {$row['keratyt']}</td>";
		echo "<td class='center toggleable_parent_row_weight' id='{$row['kg_suun']}__{$row['lahdon_tunnus']}__{$y}'>{$row['kg_suun']} / {$row['kg_ker']}</td>";
		echo "<td class='center toggleable_parent_row_liters' id='{$row['litrat_suun']}__{$row['lahdon_tunnus']}__{$y}'>{$row['litrat_suun']} / {$row['litrat_ker']}</td>";

		echo "</tr>";

		$query = "	SELECT lasku.tunnus AS 'tilauksen_tunnus',
					lasku.vanhatunnus AS 'tilauksen_vanhatunnus',
					lasku.tilaustyyppi AS 'tilauksen_tilaustyyppi',
					lasku.nimi AS 'asiakas_nimi',
					lasku.toim_nimi AS 'asiakas_toim_nimi',
					lasku.toim_postitp AS 'asiakas_toim_postitp',
					lasku.prioriteettinro AS 'prioriteetti',
					lasku.ohjausmerkki,
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
					GROUP BY 1,2,3,4,5,6,7,8,9,10";
		$lahto_res = pupe_query($query);

		echo "<tr class='toggleable_tr' id='toggleable_tr_{$row['lahdon_tunnus']}__{$y}'>";
		echo "<td colspan='14' class='back'>";
		echo "<div id='toggleable_{$row['lahdon_tunnus']}__{$y}' style='display:none;'>";

		echo "<table style='width:100%; padding:0px; margin:0px; border:0px;'>";

		$priorities = array();
		
		while ($lahto_row = mysql_fetch_assoc($lahto_res)) {
			$priorities[$lahto_row['prioriteetti']] = $lahto_row['prioriteetti'];
		}

		echo "<tr class='header_row_{$row['lahdon_tunnus']}__{$y}'>";
		echo "<th></th>";

		echo "<th class='sort_row_by' id='row_status__{$row['lahdon_tunnus']}__{$y}'>",t("Status")," <img class='row_direction_status' />";
		echo "<br />";
		echo "<select class='filter_row_by_select' id='child_row_select_status'>";
		echo "<option value=''>",t("Valitse"),"</option>";
		echo "<option value='1'>",t("Aloittamatta"),"</option>";
		echo "<option value='2'>",t("Aloitettu"),"</option>";
		echo "<option value='3'>",t("Kerätty"),"</option>";
		echo "</select>";
		echo "</th>";

		sort($priorities);

		echo "<th class='sort_row_by' id='row_prio__{$row['lahdon_tunnus']}__{$y}'>",t("Prio")," <img class='row_direction_prio' />";
		echo "<br />";
		echo "<select class='filter_row_by_select' id='child_row_select_prio'>";
		echo "<option value=''>",t("Valitse"),"</option>";

		foreach ($priorities as $prio) {
			echo "<option value='{$prio}'>{$prio}</option>";
		}

		echo "</select>";
		echo "</th>";

		echo "<th class='sort_row_by' id='row_order__{$row['lahdon_tunnus']}__{$y}'>",t("Tilausnumero")," <img class='row_direction_order' />";
		echo "<br />";
		echo "<input type='text' class='filter_row_by_text' id='child_row_text_order__{$row['lahdon_tunnus']}__{$y}' value='' size='10' />";
		echo "</th>";

		echo "<th class='sort_row_by' id='row_orderold__{$row['lahdon_tunnus']}__{$y}'>",t("Vanhatunnus")," <img class='row_direction_orderold' />";
		echo "<br />";
		echo "<input type='text' class='filter_row_by_text' id='child_row_text_orderold__{$row['lahdon_tunnus']}__{$y}' value='' size='10' />";
		echo "</th>";

		echo "<th class='sort_row_by' id='row_type__{$row['lahdon_tunnus']}__{$y}'>",t("Tilaustyyppi")," <img class='row_direction_type' /></th>";
		echo "<th class='sort_row_by' id='row_control__{$row['lahdon_tunnus']}__{$y}'>",t("Ohjausmerkki")," <img class='row_direction_control' /></th>";

		echo "<th class='sort_row_by' id='row_client__{$row['lahdon_tunnus']}__{$y}'>",t("Asiakas")," <img class='row_direction_client' />";
		echo "<br />";
		echo "<input type='text' class='filter_row_by_text' id='child_row_text_client__{$row['lahdon_tunnus']}__{$y}' value='' />";
		echo "</th>";

		echo "<th class='sort_row_by' id='row_locality__{$row['lahdon_tunnus']}__{$y}'>",t("Paikkakunta")," <img class='row_direction_locality' /></th>";

		$query = "	SELECT DISTINCT nimitys
					FROM keraysvyohyke
					WHERE yhtio = '{$kukarow['yhtio']}'";
		$keraysvyohyke_result = pupe_query($query);

		echo "<th class='sort_row_by' id='row_picking_zone__{$row['lahdon_tunnus']}__{$y}'>",t("Keräysvyöhyke")," <img class='row_direction_picking_zone' />";
		echo "<br />";
		// echo "<select class='filter_row_by_select' id='child_row_select_picking_zone' multiple='multiple' size='4'>";
		echo "<select class='filter_row_by_select' id='child_row_select_picking_zone'>";
		echo "<option value=''>",t("Valitse"),"</option>";

		while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result)) {
			echo "<option value='{$keraysvyohyke_row['nimitys']}'>{$keraysvyohyke_row['nimitys']}</option>";
		}

		echo "</select>";
		echo "</th>";

		echo "<th class='sort_row_by' id='row_batch__{$row['lahdon_tunnus']}__{$y}'>",t("Erä")," <img class='row_direction_batch' />";
		echo "<br />";
		echo "<input type='text' class='filter_row_by_text' id='child_row_text_batch__{$row['lahdon_tunnus']}__{$y}' value='' size='6' />";
		echo "</th>";

		echo "<th class='sort_row_by' id='row_rows__{$row['lahdon_tunnus']}__{$y}'>",t("Rivit")," / ",t("Kerätyt")," <img class='row_direction_rows' /></th>";
		echo "<th class='sort_row_by' id='row_sscc__{$row['lahdon_tunnus']}__{$y}'>",t("SSCC")," <img class='row_direction_sscc' /></th>";
		echo "<th class='sort_row_by' id='row_package__{$row['lahdon_tunnus']}__{$y}'>",t("Pakkaus")," <img class='row_direction_package' /></th>";
		echo "<th class='sort_row_by' id='row_weight__{$row['lahdon_tunnus']}__{$y}'>",t("Paino")," <img class='row_direction_weight' /></th>";
		echo "</tr>";

		mysql_data_seek($lahto_res, 0);

		$x = 0;

		$type_array = array(
			"N" => t("Normaalitilaus"),
			"E" => t("Ennakkotilaus"),
			"T" => t("Tarjoustilaus"),
			"2" => t("Varastotäydennys"),
			"7" => t("Tehdastilaus"),
			"8" => t("Muiden mukana"),
			"A" => t("Työmääräys")
		);

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
			echo "<td class='toggleable_row_orderold' id='{$lahto_row['tilauksen_vanhatunnus']}__{$x}'>{$lahto_row['tilauksen_vanhatunnus']}</td>";

			echo "<td class='data toggleable_row_type' id='{$lahto_row['tilauksen_tilaustyyppi']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$type_array[$lahto_row['tilauksen_tilaustyyppi']]}</td>";

			echo "<td class='toggleable_row_control' id='{$lahto_row['ohjausmerkki']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['ohjausmerkki']}</td>";

			echo "<td class='data toggleable_row_client' id='{$lahto_row['asiakas_nimi']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['asiakas_nimi']}";
			if ($lahto_row['asiakas_nimi'] != $lahto_row['asiakas_toim_nimi']) echo " {$lahto_row['asiakas_toim_nimi']}";
			echo "</td>";

			echo "<td class='toggleable_row_locality' id='{$lahto_row['asiakas_toim_postitp']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$lahto_row['asiakas_toim_postitp']}</td>";

			if ($lahto_row['sscc'] != '') {
				$query = "	SELECT keraysvyohyke.nimitys AS 'keraysvyohyke',
							tuoteperhe.ohita_kerays AS 'ohitakerays',
							COUNT(kerayserat.tunnus) AS 'rivit',
							SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS 'keratyt'
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							JOIN keraysvyohyke ON (keraysvyohyke.yhtio = tuote.yhtio AND keraysvyohyke.tunnus = tuote.keraysvyohyke)
							JOIN kerayserat ON (kerayserat.yhtio = tilausrivi.yhtio AND kerayserat.tilausrivi = tilausrivi.tunnus AND kerayserat.sscc = '{$lahto_row['sscc']}' AND kerayserat.nro = '{$lahto_row['erat']}')
							LEFT JOIN tuoteperhe ON (tuoteperhe.yhtio = tilausrivi.yhtio AND tuoteperhe.tuoteno = tilausrivi.tuoteno AND tuoteperhe.tyyppi = 'P' AND tuoteperhe.ohita_kerays != '')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.otunnus = '{$lahto_row['tilauksen_tunnus']}'
							GROUP BY 1,2
							HAVING ohitakerays IS NULL";
			}
			else {
				$query = "	SELECT keraysvyohyke.nimitys AS 'keraysvyohyke',
							tuoteperhe.ohita_kerays AS 'ohitakerays',
							COUNT(tilausrivi.tunnus) AS 'rivit',
							SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS 'keratyt'
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							JOIN keraysvyohyke ON (keraysvyohyke.yhtio = tuote.yhtio AND keraysvyohyke.tunnus = tuote.keraysvyohyke)
							LEFT JOIN tuoteperhe ON (tuoteperhe.yhtio = tilausrivi.yhtio AND tuoteperhe.tuoteno = tilausrivi.tuoteno AND tuoteperhe.tyyppi = 'P' AND tuoteperhe.ohita_kerays != '')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.otunnus = '{$lahto_row['tilauksen_tunnus']}'
							GROUP BY 1,2
							HAVING ohitakerays IS NULL";
			}

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

				$query = "	SELECT pakkaus.pakkauskuvaus,
							pakkaus.oma_paino,
							(kerayserat.kpl * tuote.tuotemassa) AS 'kg'
							FROM kerayserat
							JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
							JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
							AND kerayserat.sscc = '{$lahto_row['sscc']}'
							AND kerayserat.pakkausnro = '{$lahto_row['pakkausnumerot']}'
							ORDER BY kerayserat.sscc";
				$pakkauskuvaus_res = pupe_query($query);
				$pakkauskuvaus_row = mysql_fetch_assoc($pakkauskuvaus_res);

				echo "<td class='data toggleable_row_package' id='{$pakkauskuvaus_row['pakkauskuvaus']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$pakkauskuvaus_row['pakkauskuvaus']}</td>";

				$kg = $pakkauskuvaus_row['kg'] + $pakkauskuvaus_row['oma_paino'];

				while ($pakkauskuvaus_row = mysql_fetch_assoc($pakkauskuvaus_res)) {
					$kg += $pakkauskuvaus_row['kg'];
				}

				$kg = round($kg, 0);

				echo "<td class='toggleable_row_weight' id='{$kg}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$kg}</td>";
			}
			else {
				echo "<td class='data toggleable_row_package' id='!__{$lahto_row['tilauksen_tunnus']}__{$x}'></td>";

				$query = "	SELECT ROUND(SUM(tilausrivi.varattu * tuote.tuotemassa), 0) AS 'kg'
							FROM tilausrivi
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.otunnus = '{$lahto_row['tilauksen_tunnus']}'";
				$paino_res = pupe_query($query);
				$paino_row = mysql_fetch_assoc($paino_res);

				echo "<td class='toggleable_row_weight' id='{$paino_row['kg']}__{$lahto_row['tilauksen_tunnus']}__{$x}'>{$paino_row['kg']}</td>";
			}

			echo "</tr>";

			$query = "	SELECT tilausrivi.tuoteno,
						kerayserat.sscc,
						tuote.nimitys,
						tilausrivi.tilkpl AS 'suunniteltu',
						tilausrivi.yksikko,
						CONCAT(tilausrivi.hyllyalue,'-',tilausrivi.hyllynro,'-',tilausrivi.hyllyvali,'-',tilausrivi.hyllytaso) AS 'hyllypaikka',
						kerayserat.laatija AS 'keraaja',
						tilausrivi.kerattyaika,
						IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', kerayserat.kpl, 0) AS 'keratyt'
						FROM tilausrivi
						LEFT JOIN kerayserat ON (kerayserat.yhtio = tilausrivi.yhtio AND kerayserat.tilausrivi = tilausrivi.tunnus)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.otunnus = '{$lahto_row['tilauksen_tunnus']}'
						ORDER BY kerayserat.sscc, tilausrivi.tuoteno";
			$rivi_res = pupe_query($query);
			
			echo "<tr class='toggleable_row_child_order_{$lahto_row['tilauksen_tunnus']}__{$x}'>";
			echo "<td colspan='15' class='back' style='display:none;'>";
			echo "<div class='toggleable_row_child_div_order' id='toggleable_row_order_{$lahto_row['tilauksen_tunnus']}__{$x}' style='display:none;'>";

			echo "<table style='width:100%;'>";
	
			echo "<tr>";
			echo "<th>",t("SSCC"),"</th>";
			echo "<th>",t("Tuotenumero"),"</th>";
			echo "<th>",t("Nimitys"),"</th>";
			echo "<th>",t("Suunniteltu määrä"),"</th>";
			echo "<th>",t("Kerätty määrä"),"</th>";
			echo "<th>",t("Poikkeama määrä"),"</th>";
			echo "<th>",t("Yksikkö"),"</th>";
			echo "<th>",t("Hyllypaikka"),"</th>";
			echo "<th>",t("Kerääjä"),"</th>";
			echo "</tr>";
			
			while ($rivi_row = mysql_fetch_assoc($rivi_res)) {
				echo "<tr>";
				echo "<td class='tumma'>{$rivi_row['sscc']}</td>";
				echo "<td class='tumma'>{$rivi_row['tuoteno']}</td>";
				echo "<td class='tumma'>{$rivi_row['nimitys']}</td>";
				echo "<td class='tumma'>{$rivi_row['suunniteltu']}</td>";
				echo "<td class='tumma'>{$rivi_row['keratyt']}</td>";
				echo "<td class='tumma'>";

				if ($rivi_row['kerattyaika'] != '0000-00-00 00:00:00' and $rivi_row['keratyt'] - $rivi_row['suunniteltu'] != 0) {
					echo ($rivi_row['keratyt'] - $rivi_row['suunniteltu']);
				}

				echo "</td>";
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
						tilausrivi.tilkpl AS 'suunniteltu',
						tilausrivi.yksikko,
						CONCAT(tilausrivi.hyllyalue,'-',tilausrivi.hyllynro,'-',tilausrivi.hyllyvali,'-',tilausrivi.hyllytaso) AS hyllypaikka,
						kerayserat.laatija AS keraaja,
						tilausrivi.kerattyaika,
						IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', kerayserat.kpl, 0) AS 'keratyt'
						FROM kerayserat
						JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
						AND kerayserat.sscc = '{$lahto_row['sscc']}'
						ORDER BY kerayserat.otunnus, tilausrivi.tuoteno";
			$rivi_res = pupe_query($query);

			echo "<tr class='toggleable_row_child_sscc_{$lahto_row['tilauksen_tunnus']}__{$x}'>";
			echo "<td colspan='15' class='back' style='display:none;'>";
			echo "<div class='toggleable_row_child_div_sscc' id='toggleable_row_sscc_{$lahto_row['sscc']}__{$x}' style='display:none;'>";

			echo "<table style='width:100%;'>";

			echo "<tr>";
			echo "<th>",t("Tilausnumero"),"</th>";
			echo "<th>",t("Tuotenumero"),"</th>";
			echo "<th>",t("Nimitys"),"</th>";
			echo "<th>",t("Suunniteltu määrä"),"</th>";
			echo "<th>",t("Kerätty määrä"),"</th>";
			echo "<th>",t("Poikkeama määrä"),"</th>";
			echo "<th>",t("Yksikkö"),"</th>";
			echo "<th>",t("Hyllypaikka"),"</th>";
			echo "<th>",t("Kerääjä"),"</th>";
			echo "</tr>";

			while ($rivi_row = mysql_fetch_assoc($rivi_res)) {
				echo "<tr>";
				echo "<td class='tumma'>{$rivi_row['otunnus']}</td>";
				echo "<td class='tumma'>{$rivi_row['tuoteno']}</td>";
				echo "<td class='tumma'>{$rivi_row['nimitys']}</td>";
				echo "<td class='tumma'>{$rivi_row['suunniteltu']}</td>";
				echo "<td class='tumma'>{$rivi_row['keratyt']}</td>";

				echo "<td class='tumma'>";

				if ($rivi_row['kerattyaika'] != '0000-00-00 00:00:00' and $rivi_row['keratyt'] - $rivi_row['suunniteltu'] != 0) {
					echo ($rivi_row['keratyt'] - $rivi_row['suunniteltu']);
				}

				echo "</td>";

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