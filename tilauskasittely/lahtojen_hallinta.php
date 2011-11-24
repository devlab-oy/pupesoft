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

					$('.row_order_direction').attr('src', '{$palvelin2}pics/lullacons/arrow-double-up-green.png');

					$('.toggleable').click(function(event){

						if ($('#toggleable_'+this.id).is(':visible')) {

							$('.toggleable_row_child').hide();
							$('#toggleable_'+this.id).hide();

							$('.toggleable_row_order').removeClass('tumma');
							$('.toggleable_row_sscc').removeClass('tumma');

							$('.toggleable_row_tr').hide();

							$('tr[id!=\"toggleable_parent_'+id+'\"][class=\"toggleable_parent\"]').show();
							$('tr[id!=\"toggleable_tr_'+id+'\"][class=\"toggleable_tr\"]').stop().show();

							$(':checkbox').attr('checked', false);
						}
						else {
							var id = this.id;
							var parent_element = $('#toggleable_'+id).parent();

							$('.toggleable_row_tr').show();

							$('tr[id!=\"toggleable_parent_'+id+'\"][class=\"toggleable_parent\"]').hide();
							$('tr[id!=\"toggleable_tr_'+id+'\"][class=\"toggleable_tr\"]').hide();

							$('#toggleable_'+id).css({'width': parent_element.width()+'px', 'padding-top': '15px'}).show();
						}
					});

					$('.toggleable_row_order').live('click', function(event){

						if ($('#toggleable_row_order_'+this.id).is(':visible')) {

							$(this).removeClass('tumma');

							$('#toggleable_row_order_'+this.id).slideUp('fast');

							if ($('.toggleable_row_child:visible').length == 1) {
								$('tr[id!=\"toggleable_row_tr_'+this.id+'\"][class=\"toggleable_row_tr\"]').show();
							}
						}
						else {

							$(this).addClass('tumma');

							var parent_element = $('#toggleable_row_order_'+this.id).parent();

							$('tr[id!=\"toggleable_row_tr_'+this.id+'\"][class=\"toggleable_row_tr\"]').hide();

							$('#toggleable_row_order_'+this.id).css({'width': parent_element.width()+'px', 'padding-bottom': '15px'}).delay(1).slideDown('fast');
						}
					});

					$('.toggleable_row_sscc').live('click', function(event){

						var id = this.id.split(\"#\", 3);

						if ($('#toggleable_row_sscc_'+id[0]+'_'+id[1]).is(':visible')) {
							$(this).removeClass('tumma');

							$('#toggleable_row_sscc_'+id[0]+'_'+id[1]).slideUp('fast');

							if ($('.toggleable_row_child:visible').length == 1) {
								$('tr[id!=\"toggleable_row_tr_'+id[2]+'\"][class=\"toggleable_row_tr\"]').show();
							}
						}
						else {

							$(this).addClass('tumma');

							$('tr[id!=\"toggleable_row_tr_'+id[2]+'\"][class=\"toggleable_row_tr\"]').hide();

							var parent_element = $('#toggleable_row_sscc_'+id[0]+'_'+id[1]).parent();

							$('#toggleable_row_sscc_'+id[0]+'_'+id[1]).css({'width': parent_element.width()+'px', 'padding-bottom': '15px'}).delay(1).slideDown('fast');
						}
					});

					function compareId(a, b) {
						return b.id - a.id;
					}

					var sort_row_by_order_direction = false;

					$('.sort_row_by_order').click(function(){
						var arr = $('.toggleable_row_tr:visible');
						var _arr = new Array();
						var _arrChildOrder = new Array();
						var _arrChildSscc = new Array();

						for (i = 0; i < arr.length; i++) {
							var row = arr[i];

							var id = $(row).attr('id').substring(18);

							var temp = {'id': id, 'row': row};
							_arr.push(temp);

							var rowChildOrder = $('.toggleable_row_child_order_'+id);
							var tempChildOrder = {'id': id, 'row': rowChildOrder};
							_arrChildOrder.push(tempChildOrder);

							var rowChildSscc = $('.toggleable_row_child_sscc_'+id);
							var tempChildSscc = {'id': id, 'row': rowChildSscc};
							_arrChildSscc.push(tempChildSscc);
						}

						$('.toggleable_row_tr:visible').remove();

						for (i = 0; i < _arr.length; i++) {
							$('.toggleable_row_child_order_'+_arr[i].id).remove();
							$('.toggleable_row_child_sscc_'+_arr[i].id).remove();
						}

						var header_id = $('.toggleable_tr:visible').attr('id').substring(14);

						if (sort_row_by_order_direction) {

							_arr.sort(compareId);
							_arrChildOrder.sort(compareId);
							_arrChildSscc.sort(compareId);

							for (i = 0; i < _arr.length; i++) {
								$('.header_row_'+header_id).after(_arrChildSscc[i].row);
								$('.header_row_'+header_id).after(_arrChildOrder[i].row);
								$('.header_row_'+header_id).after(_arr[i].row);
							}

							$('.row_order_direction').attr('src', '{$palvelin2}pics/lullacons/arrow-double-up-green.png');

							sort_row_by_order_direction = false;
						}
						else {
							_arr.sort(compareId).reverse();
							_arrChildOrder.sort(compareId).reverse();
							_arrChildSscc.sort(compareId).reverse();

							for (i = 0; i < _arr.length; i++) {
								$('.header_row_'+header_id).after(_arrChildSscc[i].row);
								$('.header_row_'+header_id).after(_arrChildOrder[i].row);
								$('.header_row_'+header_id).after(_arr[i].row);
							}

							$('.row_order_direction').attr('src', '{$palvelin2}pics/lullacons/arrow-double-down-green.png');

							sort_row_by_order_direction = true;
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
				#avainsana.selitetark_3 AS 'prioriteetti',
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
				#JOIN avainsana ON (avainsana.yhtio = lasku.yhtio AND avainsana.laji = 'ASIAKASLUOKKA' AND avainsana.kieli = '{$yhtiorow['kieli']}' AND avainsana.selite = lasku.hyvaksynnanmuutos)
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))
				GROUP BY 1,2,3,4,5,6,7,8
				ORDER BY lahdot.pvm, lahdot.lahdon_kellonaika, lahdot.tunnus";
	$result = pupe_query($query);

	echo "<table>";
	echo "<tr>";
	echo "<th>",t("Status"),"</th>";
	echo "<th></th>";
	echo "<th>",t("Lähtö"),"</th>";
	echo "<th>",t("Prio"),"</th>";
	echo "<th>",t("Toimitustapa"),"</th>";
	echo "<th>",t("Pvm"),"</th>";
	echo "<th>",t("Viim til klo"),"</th>";
	echo "<th>",t("Lähtöaika"),"</th>";
	echo "<th>",t("Ker. alku klo"),"</th>";
	echo "<th>",t("Til / valm"),"</th>";
	echo "<th>",t("Rivit suun / ker"),"</th>";
	echo "<th>",t("Kg suun / ker"),"</th>";
	echo "<th>",t("Litrat suun / ker"),"</th>";
	echo "</tr>";

	while ($row = mysql_fetch_assoc($result)) {

		echo "<tr class='toggleable_parent' id='toggleable_parent_{$row['lahdon_tunnus']}'>";

		$exp_date = strtotime($row['lahdon_pvm'].' '.$row['lahdon_kellonaika'].':00');
		$todays_date = strtotime(date('Y-m-d H:i:s'));

		if ($todays_date >= $exp_date or $row['vakisin_kerays'] != '') {
			echo "<td class='vihrea'></td>";
		}
		else {
			echo "<td class='keltainen'></td>";
		}

		echo "<td><input type='checkbox' class='checkall' name='{$row['lahdon_tunnus']}'></td>";
		echo "<td class='toggleable center' id='{$row['lahdon_tunnus']}'><a class='td'>{$row['lahdon_tunnus']}</a></td>";
		echo "<td class='center'>{$row['prioriteetti']}</td>";
		echo "<td>{$row['toimitustapa']}</td>";
		echo "<td class='center'>",tv1dateconv($row['lahdon_pvm']),"</td>";
		echo "<td class='center'>{$row['viimeinen_tilausaika']}</td>";

		echo "<td class='center'>";

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
					#avainsana.selitetark_3 AS 'prioriteetti',
					lasku.prioriteettinro AS 'prioriteetti',
					GROUP_CONCAT(DISTINCT kerayserat.nro) AS 'erat',
					GROUP_CONCAT(DISTINCT kerayserat.pakkausnro) AS 'pakkausnumerot',
					GROUP_CONCAT(DISTINCT kerayserat.sscc) AS 'sscc',
					GROUP_CONCAT(DISTINCT kerayserat.tila) AS 'tilat',
					COUNT(kerayserat.tunnus) AS 'keraysera_rivi_count',
					SUM(IF((kerayserat.tila = 'T' OR kerayserat.tila = 'R'), 1, 0)) AS 'keraysera_rivi_valmis'
					FROM lasku
					LEFT JOIN kerayserat ON (kerayserat.yhtio = lasku.yhtio AND kerayserat.otunnus = lasku.tunnus)
					LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
					JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
					#JOIN avainsana ON (avainsana.yhtio = lasku.yhtio AND avainsana.laji = 'ASIAKASLUOKKA' AND avainsana.kieli = '{$yhtiorow['kieli']}' AND avainsana.selite = lasku.hyvaksynnanmuutos)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tunnus IN ({$row['tilaukset']})
					GROUP BY 1,2,3,4,5";
		$lahto_res = pupe_query($query);

		echo "<tr class='toggleable_tr' id='toggleable_tr_{$row['lahdon_tunnus']}'>";
		echo "<td colspan='13' class='back'>";
		echo "<div id='toggleable_{$row['lahdon_tunnus']}' style='display:none;'>";

		echo "<table style='width:100%; padding:0px; margin:0px; border:0px;'>";

		echo "<tr class='header_row_{$row['lahdon_tunnus']}'>";
		echo "<th></th>";
		echo "<th>",t("Status"),"</th>";
		echo "<th>",t("Prio"),"</th>";
		echo "<th class='sort_row_by_order'>",t("Tilausnumero")," <img class='row_order_direction' /></th>";
		echo "<th>",t("Asiakas"),"</th>";
		echo "<th>",t("Paikkakunta"),"</th>";
		echo "<th>",t("Keräysvyöhyke"),"</th>";
		echo "<th>",t("Erä"),"</th>";
		echo "<th>",t("Rivit")," / ",t("Kerätyt"),"</th>";
		echo "<th>",t("SSCC"),"</th>";
		echo "<th>",t("Pakkaus"),"</th>";
		echo "<th>",t("Kg"),"</th>";
		echo "</tr>";

		while ($lahto_row = mysql_fetch_assoc($lahto_res)) {

			echo "<tr class='toggleable_row_tr' id='toggleable_row_tr_{$lahto_row['tilauksen_tunnus']}'>";

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

			echo "<td class='data status_{$status}'>{$status_text}</td>";

			echo "<td class='center'>{$lahto_row['prioriteetti']}</td>";
			echo "<td class='toggleable_row_order' id='{$lahto_row['tilauksen_tunnus']}'><a class='td'>{$lahto_row['tilauksen_tunnus']}</a></td>";
			echo "<td class='data'>{$lahto_row['asiakas_nimi']}";
			if ($lahto_row['asiakas_nimi'] != $lahto_row['asiakas_toim_nimi']) echo "<br>{$lahto_row['asiakas_toim_nimi']}";
			echo "</td>";
			echo "<td>{$lahto_row['asiakas_toim_postitp']}</td>";

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

			echo "<td class='data'>{$til_row['keraysvyohyke']}</td>";

			echo "<td class='data'>",str_replace(",", "<br />", $lahto_row['erat']),"</td>";

			echo "<td>{$til_row['rivit']} / {$til_row['keratyt']}</td>";
			echo "<td class='data'>";

			$arr = explode(",", $lahto_row['sscc']);
			$cnt = count($arr);
			$i = 1;

			foreach ($arr as $sscc) {
				echo "<a class='td toggleable_row_sscc' id='{$sscc}#{$i}#{$lahto_row['tilauksen_tunnus']}'>{$sscc}</a>";

				if ($i < $cnt) echo " ";

				$i++;
			}

			echo "</td>";
			echo "<td class='data'>";

			if ($lahto_row['pakkausnumerot'] != '') {

				$query = "	SELECT pakkaus.pakkauskuvaus
							FROM kerayserat
							JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
							WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
							AND kerayserat.sscc IN ({$lahto_row['sscc']})
							AND kerayserat.pakkausnro IN ({$lahto_row['pakkausnumerot']})
							GROUP BY kerayserat.pakkausnro
							ORDER BY kerayserat.sscc";
				$pakkauskuvaus_res = pupe_query($query);

				$num = mysql_num_rows($pakkauskuvaus_res);

				$i = 0;

				while ($pakkauskuvaus_row = mysql_fetch_assoc($pakkauskuvaus_res)) {
					echo "{$pakkauskuvaus_row['pakkauskuvaus']}";

					if ($i < $num) echo "<br>";

				}
			}

			echo "</td>";
			echo "<td>{$til_row['kg']}</td>";

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
			
			echo "<tr class='toggleable_row_child_order_{$lahto_row['tilauksen_tunnus']}'>";
			echo "<td colspan='12' class='back'>";
			echo "<div class='toggleable_row_child' id='toggleable_row_order_{$lahto_row['tilauksen_tunnus']}' style='display:none;'>";

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

			$i = 1;

			foreach ($arr as $sscc) {

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
							AND kerayserat.sscc = '{$sscc}'
							GROUP BY 1,2,3,4,5,6,7";
				$rivi_res = pupe_query($query);

				echo "<tr class='toggleable_row_child_sscc_{$lahto_row['tilauksen_tunnus']}'>";
				echo "<td colspan='12' class='back'>";
				echo "<div class='toggleable_row_child' id='toggleable_row_sscc_{$sscc}_{$i}' style='display:none;'>";

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

				$i++;

			}
		}

		echo "</table>";

		echo "</div>";
		echo "</td>";
		echo "</tr>";

	}

	echo "</table>";

	require ("inc/footer.inc");