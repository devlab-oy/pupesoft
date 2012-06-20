<?php

	require("../inc/parametrit.inc");

	if ($_POST['ajax_toiminto'] == 'paivita_keraysvyohyke') {

		$kuka = $_POST['kuka'];
		$keraysvyohyke = $_POST['keraysvyohyke'];
		$yhtio = $_POST['yhtio'];

		if (trim($kuka) != '') {
			$query = "UPDATE kuka SET keraysvyohyke = '".implode(",", $keraysvyohyke)."' WHERE yhtio = '{$yhtio}' AND kuka = '{$kuka}'";
			$upd_res = mysql_query($query);
		}

		exit;
	}

	echo "	<script type='text/javascript' src='https://www.google.com/jsapi'></script>";
	echo "	<script type='text/javascript' language='JavaScript'>
				<!--

				google.load('visualization', '1', {packages:['corechart']});

				function drawChart() {
					var data = new google.visualization.DataTable();

					data.addColumn('string', 'Klo');
					data.addColumn('number', 'Kerätty');
					data.addColumn('number', 'Keräyksessä');
					data.addColumn('number', 'Aloittamatta');
					data.addColumn('number', 'Siirretty');

					// data.addRows([
					// 	['08.00', 36, 0, 0, 0],
					// 	['09.00', 119, 0, 0, 0],
					// 	['10.00', 122, 0, 0, 0],
					// 	['11.00', 53, 0, 0, 0],
					// 	['12.00', 295, 0, 0, 0],
					// 	['13.00', 31, 0, 0, 0],
					// 	['14.00', 160, 0, 0, 0],
					// 	['15.00', 7, 0, 0, 0],
					// 	['16.00', 617, 190, 0, 30],
					// 	['17.00', 289, 95, 6, 0],
					// 	['18.00', 455, 118, 109, 0],
					// 	['19.00', 398, 194, 299, 0]
					// ]);

					data.addRows(jQuery.parseJSON($('#chart_div_values').html()));

					var options = {
						width: 800,
						height: 320,
						title: '",t("Keräilyn kuormitus lähdön ajan mukaan"),"',
						hAxis: {
							title: '",t("Klo"),"',
							titleTextStyle: {color: 'black'}
						},
						vAxis: {
							title: $('#chart_div_volume').html(),
							titleTextStyle: {color: 'black'}
						},
						isStacked: true,
						backgroundColor: '#DDD',
						tooltip: {
							showColorCode: true
						},
						series: {
							0:{
								color: 'green',
								visibleInLegend: true
							},
							1:{
								color: 'blue',
								visibleInLegend: true
							},
							2:{
								color: 'red',
								visibleInLegend: true
							},
							3:{
								color: '#FE0',
								visibleInLegend: true
							}
						}
					};

					var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
					chart.draw(data, options);
				}

				$('.checkall').live('click', function() {
					$(this).is(':checked') ? $('input.'+$(this).attr('id')).attr('checked', true) : $('input.'+$(this).attr('id')).attr('checked', false);
				});

				function translate(element, x, y)
				{
				    var translation = 'translate(' + x + 'px,' + y + 'px)';
				    var translation = 'translateY('+y+'px)';

				    element.css({
				        'transform': translation,
				        '-ms-transform': translation,
				        '-webkit-transform': translation,
				        '-o-transform': translation,
				        '-moz-transform': translation
				    });
				}

				$(document).ready(function() {

			        var stickyHeaderTop = $('#keraajat thead').offset().top-25;
			        var stickyHeaderWidth = $('#keraajat').css('width');
			        var stickyHeaderPosition = $('#keraajat').position();

			        var ii = 0;

			        if ($('#chart_div_values').html() != undefined) {
			        	stickyHeaderTop += 300+21;
			        }

			        $('#keraajat th').each(function() {
			        	var widthi = $(this).css('width');
			        	$('#th_'+ii).css({width: widthi});
			        	ii++;
			        });

					$('#keraajat td').each(function() {
						$(this).css({position: 'static'});
					});

			        $('#divi').hide();

			        $(window).scroll(function(){

			                if ($(window).scrollTop() > stickyHeaderTop) {
			                        $('#keraajat').css({width: stickyHeaderWidth});
			                        $('#divi').show();
			                        $('#divi').css({position: 'fixed', top: '-1px', left: stickyHeaderPosition.left});
			                }
			                else {
		                        $('#divi').css({position: 'static', top: '0px'}).hide();
			                }
			        });

					$('.keraysvyohyke_checkbox').click(function() {
						var keraysvyohyke = $(this).val();
						var name = $(this).attr('name');

						keraysvyohykkeet = new Array();

						var i = 0;

						$('input[name=\"'+name+'\"]').each(function() {
							if ($(this).is(':checked')) {
								keraysvyohykkeet[i] = $(this).val();
								i++;
							}
						});

						// console.log(keraysvyohykkeet);

						$.post('',
									{ 	kuka: name,
										keraysvyohyke: keraysvyohykkeet,
										ajax_toiminto: 'paivita_keraysvyohyke',
										yhtio: '{$kukarow['yhtio']}',
										no_head: 'yes',
										ohje: 'off' });


					});

					if ($('#chart_div_values').html() != undefined) {
						google.setOnLoadCallback(drawChart);
					}

					$('th.keraysvyohyke').click(function() {
						var id = $(this).attr('id');

						if ($('tr[class^=\"asiakas_'+id+'\"]').is(':visible') === false && $('tr[class^=\"rivit_'+id+'\"]').is(':visible') === false) {
							$('tr.era_'+id).toggle();
						}
					});

					$('td.erat').click(function() {
						var id = this.id.split(\"_\");

						if ($('tr[class^=\"rivit_'+id[1]+'_'+id[2]+'\"]').is(':visible') === false) {
							$(this).toggleClass('back');
							$('tr.asiakas_'+id[1]+'_'+id[2]).toggle();
						}
					});

					$('td.asiakas').click(function() {
						var id = this.id.split(\"_\");

						if ($('tr.rivit_'+id[1]+'_'+id[2]+'_'+id[3]).length > 0) {
							$(this).toggleClass('back');
							$('tr.rivit_'+id[1]+'_'+id[2]+'_'+id[3]).toggle();
						}
					});
				});

				//-->
			</script>";

	echo "<font class='head'>",t("Keräysvyöhykekuormitus"),"</font><hr>";

	if (isset($nayta_valinnat) and count($nayta_valinnat) == 1) {
		echo "<br /><font class='error'>",t("VIRHE: Et valinnut mitään rajausta"),"!</font><br /><br />";
	}

	if (!isset($tilat)) $tilat = array('aloittamatta' => ' checked', 'aloitettu' => ' checked', 'keratty' => ' checked');
	if (!isset($volyymisuure)) $volyymisuure = "rivit";
	if (!isset($ajankohta)) $ajankohta = "present";
	if (!isset($future_date_pp_alku)) $future_date_pp_alku = date("d",mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));
	if (!isset($future_date_kk_alku)) $future_date_kk_alku = date("m",mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));
	if (!isset($future_date_vv_alku)) $future_date_vv_alku = date("Y",mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));
	if (!isset($future_date_pp_loppu)) $future_date_pp_loppu = date("d",mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));
	if (!isset($future_date_kk_loppu)) $future_date_kk_loppu = date("m",mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));
	if (!isset($future_date_vv_loppu)) $future_date_vv_loppu = date("Y",mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));
	if (!isset($past_date_pp_alku)) $past_date_pp_alku = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($past_date_kk_alku)) $past_date_kk_alku = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($past_date_vv_alku)) $past_date_vv_alku = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($past_date_pp_loppu)) $past_date_pp_loppu = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($past_date_kk_loppu)) $past_date_kk_loppu = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($past_date_vv_loppu)) $past_date_vv_loppu = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($nayta_valinnat) or count($nayta_valinnat) == 1) $nayta_valinnat = array('aloittamatta', 'aloitettu', 'keratty');

	$wherelisa = "";
	$kerayserat_tilalisa = "";

	foreach ($nayta_valinnat as $mita_naytetaan) {

		switch ($mita_naytetaan) {
			case 'aloittamatta':
				$wherelisa = trim($wherelisa) != "" ? "{$wherelisa} OR (lasku.tila = 'N' AND lasku.alatila = 'A')" : "(lasku.tila = 'N' AND lasku.alatila = 'A')";
				break;
			case 'aloitettu':
				$wherelisa = trim($wherelisa) != "" ? "{$wherelisa} OR (lasku.tila = 'L' AND lasku.alatila = 'A')" : "(lasku.tila = 'L' AND lasku.alatila = 'A')";
				$kerayserat_tilalisa = trim($kerayserat_tilalisa) != "" ? "{$kerayserat_tilalisa} OR kerayserat.tila IN ('K','X')" : "kerayserat.tila IN ('K','X')";
				break;
			case 'keratty':
				$wherelisa = trim($wherelisa) != "" ? "{$wherelisa} OR (lasku.tila = 'L' AND lasku.alatila IN ('B', 'C'))" : "(lasku.tila = 'L' AND lasku.alatila IN ('B', 'C'))";
				$kerayserat_tilalisa = trim($kerayserat_tilalisa) != "" ? "{$kerayserat_tilalisa} OR (kerayserat.tila IN ('T','R'))" : "(kerayserat.tila IN ('T','R'))";
				break;
		}

	}

	$wherelisa = "AND ({$wherelisa})";
	$kerayserat_tilalisa = $kerayserat_tilalisa != "" ? " AND ({$kerayserat_tilalisa})" : "";

	$query = "	SELECT keraysvyohyke.nimitys AS 'ker_nimitys',
				GROUP_CONCAT(DISTINCT lasku.tunnus) AS 'tilaukset',
				COUNT(DISTINCT lasku.tunnus) AS 'tilatut',
				COUNT(DISTINCT tilausrivi.tunnus) AS 'suunnittelussa',
				SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS 'keratyt',
				ROUND(SUM(tilausrivi.varattu * tuote.tuotemassa), 0) AS 'kg_suun',
				ROUND(SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', tilausrivi.varattu * tuote.tuotemassa, 0)), 0) AS 'kg_ker',
				ROUND(SUM(tilausrivi.varattu * (tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * 1000)), 0) AS 'litrat_suun',
				ROUND(SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', (tilausrivi.varattu * tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * 1000), 0)), 0) AS 'litrat_ker'
				FROM lasku
				JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D' AND tilausrivi.var NOT IN ('P', 'J') AND tilausrivi.varattu > 0)
				JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
				JOIN keraysvyohyke ON (keraysvyohyke.yhtio = vh.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
				JOIN lahdot ON (lahdot.yhtio = lasku.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto AND lahdot.aktiivi IN ('','P','T'))
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				{$wherelisa}
				GROUP BY keraysvyohyke.nimitys
				ORDER BY 1";
	$result = pupe_query($query);

	echo "<table>";

	echo "<tr>";
	echo "<th>",t("Valitse"),"</td>";
	echo "<td style='vertical-align:middle;'>";

	$chk = array_fill_keys($nayta_valinnat, " checked") + array('aloittamatta' => '', 'aloitettu' => '', 'keratty' => '');

	echo "<form method='post' action=''>";
	echo "<input type='hidden' name='nayta_valinnat[]' value='default' />";
	echo "<input type='checkbox' name='nayta_valinnat[]' value='aloittamatta' {$chk['aloittamatta']} /> ",t("Aloittamatta"),"&nbsp;&nbsp;";
	echo "<input type='checkbox' name='nayta_valinnat[]' value='aloitettu' {$chk['aloitettu']} /> ",t("Aloitettu"),"&nbsp;&nbsp;";
	echo "<input type='checkbox' name='nayta_valinnat[]' value='keratty' {$chk['keratty']} /> ",t("Kerätty"),"&nbsp;&nbsp;";
	echo "<input type='submit' value='",t("Näytä"),"' />";
	echo "</form>";
	echo "</td>";
	echo "<td class='back' colspan='5'></td>";
	echo "</tr>";

	echo "<tr><td class='back' colspan='7'>&nbsp;</td></tr>";

	echo "<tr>";
	echo "<th>",t("Keräysvyöhyke"),"</th>";
	echo "<th>",t("Tilaukset"),"<br />",t("Ker / Til"),"</th>";
	echo "<th>",t("Rivit"),"<br />",t("Ker / Suun"),"</th>";
	echo "<th>",t("Kilot"),"<br />",t("Ker / Suun"),"</th>";
	echo "<th>",t("Litrat"),"<br />",t("Ker / Suun"),"</th>";
	echo "<th>",t("Keräyserän aloitusaika"),"</th>";
	echo "<th>",t("Keräilykapasiteettitarve"),"</th>";
	echo "</tr>";

	$i = 1;
	$max_i = mysql_num_rows($result);

	while ($row = mysql_fetch_assoc($result)) {
		echo "<tr>";
		echo "<th class='keraysvyohyke' id='{$i}'>{$row['ker_nimitys']}&nbsp;<img title='",t("Näytä kerääjät"),"' alt='",t("Näytä kerääjät"),"' src='{$palvelin2}pics/lullacons/go-down.png' style='float:right;' /></th>";
		echo "<td>";

		$query = "	SELECT lasku.tunnus, SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 0, 1)) AS 'keratyt'
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D' AND tilausrivi.var NOT IN ('P', 'J') AND tilausrivi.varattu > 0)
					JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays = '')
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tunnus IN ({$row['tilaukset']})
					{$wherelisa}
					GROUP BY 1
					ORDER BY lasku.tunnus";
		$chk_res = pupe_query($query);

		$chk = 0;

		while ($chk_row = mysql_fetch_assoc($chk_res)) {
			if ($chk_row['keratyt'] == 0) {
				$chk++;
			}
		}

		echo "{$chk} / {$row['tilatut']}</td>";

		echo "<td>{$row['keratyt']} / {$row['suunnittelussa']}</td>";
		echo "<td>{$row['kg_ker']} / {$row['kg_suun']}</td>";

		echo "<td>{$row['litrat_ker']} / {$row['litrat_suun']}</td>";
		echo "<td></td>";

		$query = "	SELECT ROUND((COUNT(DISTINCT lasku.tunnus) * keraysvyohyke.tilauksen_tyoaikavakio_min_per_tilaus + COUNT(DISTINCT tilausrivi.tunnus) * keraysvyohyke.kerailyrivin_tyoaikavakio_min_per_rivi) / 60, 1) AS 'kapasiteettitarve'
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D' AND tilausrivi.var NOT IN ('P', 'J') AND tilausrivi.varattu > 0)
					JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
					JOIN keraysvyohyke ON (keraysvyohyke.yhtio = vh.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tunnus IN ({$row['tilaukset']})
					{$wherelisa}";
		$kap_res = pupe_query($query);
		$kap_row = mysql_fetch_assoc($kap_res);

		echo "<td>{$kap_row['kapasiteettitarve']} h</td>";
		echo "</tr>";

		$query = "	SELECT kuka.nimi AS 'keraaja',
					GROUP_CONCAT(DISTINCT kerayserat.nro) AS 'erat',
					GROUP_CONCAT(DISTINCT kerayserat.otunnus) AS 'otunnukset',
					MIN(SUBSTRING(kerayserat.luontiaika, 12, 5)) AS 'aloitusaika',
					ROUND(SUM(tilausrivi.varattu * tuote.tuotemassa), 0) AS 'kg',
					COUNT(DISTINCT kerayserat.tilausrivi) AS 'rivit',
					COUNT(DISTINCT kerayserat.otunnus) AS 'tilaukset'
					FROM kerayserat
					JOIN kuka ON (kuka.yhtio = kerayserat.yhtio AND kuka.kuka = kerayserat.laatija)
					JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi AND tilausrivi.tyyppi != 'D')
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
					WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
					AND kerayserat.otunnus IN ({$row['tilaukset']})
					{$kerayserat_tilalisa}
					GROUP BY 1
					ORDER BY 1";
		$era_res = pupe_query($query);

		if (mysql_num_rows($era_res)> 0) {

			$x = 1;

			$max_x = mysql_num_rows($era_res);

			while ($era_row = mysql_fetch_assoc($era_res)) {
				echo "<tr class='era_{$i}' style='display:none;'>";
				echo "<td class='erat' id='erat_{$i}_{$x}'>{$era_row['keraaja']}&nbsp;<img title='",t("Näytä keräyserät"),"' alt='",t("Näytä keräyserät"),"' src='{$palvelin2}pics/lullacons/go-down.png' style='float:right;' /></td>";
				echo "<td>{$era_row['tilaukset']}</td>";
				echo "<td>{$era_row['rivit']}</td>";
				echo "<td>{$era_row['kg']}</td>";
				echo "<td></td>";
				echo "<td>{$era_row['aloitusaika']}</td>";
				echo "<td></td>";
				echo "</tr>";

				echo "<tr class='asiakas_{$i}_{$x}' style='display:none;'>";
				echo "<th>",t("Tila"),"</th>";
				echo "<th>",t("Prio"),"</th>";
				echo "<th>",t("Keräyserä"),"</th>";
				echo "<th>",t("Toimitusasiakas"),"</th>";
				echo "<th>",t("Lähtö"),"</th>";
				echo "<th>",t("Toimitustapa"),"</th>";
				echo "<th></th>";
				echo "</tr>";

				$query = "	SELECT kerayserat.nro,
							GROUP_CONCAT(DISTINCT lasku.toimitustavan_lahto ORDER BY lasku.toimitustavan_lahto SEPARATOR '<br />') AS 'toimitustavan_lahto',
							GROUP_CONCAT(DISTINCT kerayserat.tila) AS 'tila',
							GROUP_CONCAT(DISTINCT CONCAT(lasku.nimi, ' ', lasku.nimitark) ORDER BY nimi, nimitark SEPARATOR '<br />') AS 'nimi',
							GROUP_CONCAT(DISTINCT lasku.toimitustapa ORDER BY toimitustapa SEPARATOR '<br />') AS 'toimitustapa',
							GROUP_CONCAT(DISTINCT lasku.prioriteettinro ORDER BY prioriteettinro SEPARATOR ', ') AS 'prioriteettinro',
							GROUP_CONCAT(DISTINCT lasku.tunnus) AS 'tunnus'
							FROM kerayserat
							JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus AND lasku.tunnus IN ({$row['tilaukset']}))
							WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
							AND kerayserat.nro IN ({$era_row['erat']})
							GROUP BY 1
							ORDER BY 1";

				$asiakas_res = pupe_query($query);

				$y = 1;
				$max_y = mysql_num_rows($asiakas_res);

				while ($asiakas_row = mysql_fetch_assoc($asiakas_res)) {
					echo "<tr class='asiakas_{$i}_{$x}' style='display:none;'>";

					echo "<td class='asiakas' id='asiakas_{$i}_{$x}_{$y}'>";

					if (strpos($asiakas_row['tila'], 'K') !== false) {
						echo t("Aloitettu");
					}
					else {
						echo t("Kerätty");
					}

					echo "&nbsp;<img title='",t("Näytä rivit"),"' alt='",t("Näytä rivit"),"' src='{$palvelin2}pics/lullacons/go-down.png' style='float:right;' /></td>";

					echo "<td>{$asiakas_row['prioriteettinro']}</td>";
					echo "<td>{$asiakas_row['nro']}</td>";
					echo "<td>{$asiakas_row['nimi']}</td>";
					echo "<td>{$asiakas_row['toimitustavan_lahto']}</td>";
					echo "<td>{$asiakas_row['toimitustapa']}</td>";
					echo "<td></td>";
					echo "</tr>";

					$query = "	SELECT tilausrivi.tuoteno,
								tuote.nimitys,
								CONCAT(tilausrivi.hyllyalue, '-', tilausrivi.hyllynro, '-', tilausrivi.hyllyvali, '-', tilausrivi.hyllytaso) AS 'kerayspaikka',
								tilausrivi.otunnus,
								CONCAT(lasku.nimi, ' ', lasku.nimitark) AS 'nimi',
								ROUND(SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', kerayserat.kpl_keratty, 0)), 0) AS 'keratty',
								ROUND(SUM(tilausrivi.varattu), 0) AS 'tilattu'
								FROM kerayserat
								JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi AND tilausrivi.tyyppi != 'D')
								JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
								JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
								WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
								AND kerayserat.otunnus IN ({$asiakas_row['tunnus']})
								GROUP BY 1,2,3,4,5
								ORDER BY vh.indeksi";
					$rivi_res = pupe_query($query);

					if (mysql_num_rows($rivi_res) > 0) {
						echo "<tr class='rivit_{$i}_{$x}_{$y}' style='display:none;'>";
						echo "<th>",t("Tuotenro"),"</th>";
						echo "<th>",t("Tuotekuvaus"),"</th>";
						echo "<th>",t("Keräyspaikka"),"</th>";
						echo "<th>",t("Asiakas"),"</th>";
						echo "<th>",t("Kerätty / Tilattu"),"</th>";
						echo "<th>",t("Tilaus"),"</th>";
						echo "<th></th>";
						echo "</tr>";

						while ($rivi_row = mysql_fetch_assoc($rivi_res)) {
							//{$palvelin2}tuote.php////tuoteno=$tuoteno//tee=Z
							echo "<tr class='rivit_{$i}_{$x}_{$y}' style='display:none;'>";
							echo "<td><a href='{$palvelin2}tuvar.php?toim=&tee=Z&tuoteno=".urlencode($rivi_row["tuoteno"])."&lopetus={$palvelin2}tilauskasittely/keraysvyohykkeiden_keraajat.php'>{$rivi_row['tuoteno']}</a></td>";
							echo "<td>{$rivi_row['nimitys']}</td>";
							echo "<td>{$rivi_row['kerayspaikka']}</td>";
							echo "<td>{$rivi_row['nimi']}</td>";
							echo "<td>{$rivi_row['keratty']} / {$rivi_row['tilattu']}</td>";
							echo "<td>{$rivi_row['otunnus']}</td>";
							echo "<td></td>";
							echo "</tr>";
						}

						if ($y != $max_y) {
							echo "<tr class='rivit_{$i}_{$x}_{$y}' style='display:none;'>";
							echo "<td colspan='7' class='back'>&nbsp;</td>";
							echo "</tr>";
						}
					}

					$y++;
				}

				if ($x != $max_x) {
					echo "<tr class='asiakas_{$i}_{$x}' style='display:none;'>";
					echo "<td colspan='6' class='back'>&nbsp;</td>";
					echo "</tr>";
				}

				$x++;
			}

			echo "<tr class='era_{$i}' style='display:none;'>";
			echo "<td colspan='7' class='back'>&nbsp;</td>";
			echo "</tr>";
		}

		$i++;
	}

	echo "</table>";

	echo "<br /><br />";

	echo "<form method='post' action=''>";
	echo "<table>";
	echo "<tr>";
	echo "<th><input type='checkbox' class='checkall' id='varasto' checked /> ",t("Varasto"),"</th>";
	echo "<th><input type='checkbox' class='checkall' id='keraysvyohyke' checked /> ",t("Keräysvyöhyke"),"</th>";
	echo "<th><input type='checkbox' class='checkall' id='toimitustapa' checked /> ",t("Kuljetusliike"),"</th>";
	echo "<th><input type='checkbox' class='checkall' id='prioriteetit' checked /> ",t("Prioriteetti"),"</th>";
	echo "<th><input type='checkbox' class='checkall' id='tilat' checked /> ",t("Tila"),"</th>";
	echo "<th>",t("Volyymisuure"),"</th>";
	echo "<th>",t("Ajankohta"),"</th>";
	echo "</tr>";

	echo "<tr>";

	echo "<td>";

	$query = "	SELECT DISTINCT varastopaikat.nimitys AS 'var_nimitys', varastopaikat.tunnus AS 'var_tunnus'
				FROM lasku
				JOIN varastopaikat ON (varastopaikat.yhtio = lasku.yhtio AND varastopaikat.tunnus = lasku.varasto)
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))
				ORDER BY 1";
	$res = pupe_query($query);

	echo "<input type='hidden' name='varasto[]' value='default' />";

	while ($varastorow = mysql_fetch_assoc($res)) {

		if (!isset($varasto)) {
			$chk = " checked";
		}
		else {
			$chk = in_array($varastorow['var_tunnus'], $varasto) ? " checked" : "";
		}

		echo "<input type='checkbox' class='varasto' name='varasto[]' value='{$varastorow['var_tunnus']}'{$chk} />&nbsp;{$varastorow['var_nimitys']}<br />";
	}

	if (!isset($varasto)) $varasto = array();

	echo "</td>";

	echo "<td>";

	$query = "	SELECT DISTINCT keraysvyohyke.nimitys AS 'ker_nimitys', keraysvyohyke.tunnus AS 'ker_tunnus'
				FROM lasku
				JOIN keraysvyohyke ON (keraysvyohyke.yhtio = lasku.yhtio AND keraysvyohyke.varasto = lasku.varasto)
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))
				ORDER BY 1";
	$res = pupe_query($query);

	echo "<input type='hidden' name='keraysvyohyke[]' value='default' />";

	while ($keraysvyohykerow = mysql_fetch_assoc($res)) {

		if (!isset($keraysvyohyke)) {
			$chk = " checked";
		}
		else {
			$chk = in_array($keraysvyohykerow['ker_tunnus'], $keraysvyohyke) ? " checked" : "";
		}

		echo "<input type='checkbox' class='keraysvyohyke' name='keraysvyohyke[]' value='{$keraysvyohykerow['ker_tunnus']}'{$chk} />&nbsp;{$keraysvyohykerow['ker_nimitys']}<br />";
	}

	if (!isset($keraysvyohyke)) $keraysvyohyke = array();

	echo "</td>";

	echo "<td>";

	$query = "	SELECT DISTINCT lasku.toimitustapa, toimitustapa.tunnus AS 'tt_tunnus'
				FROM lasku
				JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa AND toimitustapa.extranet != 'K')
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))
				ORDER BY 1";
	$res = pupe_query($query);

	echo "<input type='hidden' name='toimitustapa[]' value='default' />";

	while ($toimitustaparow = mysql_fetch_assoc($res)) {

		if (!isset($toimitustapa)) {
			$chk = " checked";
		}
		else {
			$chk = in_array($toimitustaparow['tt_tunnus'], $toimitustapa) ? " checked" : "";
		}

		echo "<input type='checkbox' class='toimitustapa' name='toimitustapa[]' value='{$toimitustaparow['tt_tunnus']}'{$chk} />&nbsp;{$toimitustaparow['toimitustapa']}<br />";
	}

	if (!isset($toimitustapa)) $toimitustapa = array();

	echo "</td>";

	echo "<td>";

	$query = "	SELECT DISTINCT lasku.prioriteettinro
				FROM lasku
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.prioriteettinro != 0
				AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))";
	$res = pupe_query($query);

	echo "<input type='hidden' name='prioriteetit[]' value='default' />";

	while ($priorow = mysql_fetch_assoc($res)) {

		if (!isset($prioriteetit)) {
			$chk = " checked";
		}
		else {
			$chk = in_array($priorow['prioriteettinro'], $prioriteetit) ? " checked" : "";
		}

		echo "<input type='checkbox' class='prioriteetit' name='prioriteetit[]' value='{$priorow['prioriteettinro']}'{$chk} />&nbsp;{$priorow['prioriteettinro']}<br />";
	}

	if (!isset($prioriteetit)) $prioriteetit = array();

	echo "</td>";

	echo "<td>";

	$chk = array_fill_keys(array_keys($tilat), " checked") + array('aloittamatta' => '', 'aloitettu' => '', 'keratty' => '');

	echo "<input type='checkbox' class='tilat' name='tilat[aloittamatta]'{$chk['aloittamatta']}/> ",t("Aloittamatta"),"<br />";
	echo "<input type='checkbox' class='tilat' name='tilat[aloitettu]'{$chk['aloitettu']} /> ",t("Aloitettu"),"<br />";
	echo "<input type='checkbox' class='tilat' name='tilat[keratty]'{$chk['keratty']} /> ",t("Kerätty");
	echo "</td>";

	$chk = array_fill_keys(array($volyymisuure), " checked") + array('rivit' => '', 'kg' => '', 'litrat' => '');

	echo "<td>";
	echo "<input type='radio' name='volyymisuure' value='rivit'{$chk['rivit']} /> ",t("Rivit"),"<br />";
	echo "<input type='radio' name='volyymisuure' value='kg'{$chk['kg']} /> ",t("Kg"),"<br />";
	echo "<input type='radio' name='volyymisuure' value='litrat'{$chk['litrat']} /> ",t("Litrat");
	echo "</td>";

	$chk = array_fill_keys(array($ajankohta), " checked") + array('past' => '', 'present' => '', 'future' => '');

	echo "<td>";
	echo "<input type='radio' name='ajankohta' value='past'{$chk['past']} /> ",t("Historia"),"<br />";
	echo "<input type='text' name='past_date_pp_alku' size='3' value='{$past_date_pp_alku}' />&nbsp;";
	echo "<input type='text' name='past_date_kk_alku' size='3' value='{$past_date_kk_alku}' />&nbsp;";
	echo "<input type='text' name='past_date_vv_alku' size='5' value='{$past_date_vv_alku}' /> - ";
	echo "<input type='text' name='past_date_pp_loppu' size='3' value='{$past_date_pp_loppu}' />&nbsp;";
	echo "<input type='text' name='past_date_kk_loppu' size='3' value='{$past_date_kk_loppu}' />&nbsp;";
	echo "<input type='text' name='past_date_vv_loppu' size='5' value='{$past_date_vv_loppu}' /><br /><br />";
	echo "<input type='radio' name='ajankohta' value='present'{$chk['present']} /> ",t("Tämä päivä"),"<br /><br />";
	echo "<input type='radio' name='ajankohta' value='future'{$chk['future']} /> ",t("Tulevat"),"<br />";
	echo "<input type='text' name='future_date_pp_alku' size='3' value='{$future_date_pp_alku}' />&nbsp;";
	echo "<input type='text' name='future_date_kk_alku' size='3' value='{$future_date_kk_alku}' />&nbsp;";
	echo "<input type='text' name='future_date_vv_alku' size='5' value='{$future_date_vv_alku}' /> - ";
	echo "<input type='text' name='future_date_pp_loppu' size='3' value='{$future_date_pp_loppu}' />&nbsp;";
	echo "<input type='text' name='future_date_kk_loppu' size='3' value='{$future_date_kk_loppu}' />&nbsp;";
	echo "<input type='text' name='future_date_vv_loppu' size='5' value='{$future_date_vv_loppu}' /><br />";
	echo "</td>";

	echo "</tr>";

	echo "<tr><td class='back' colspan='6'><input type='submit' name='submit_form' value='",t("Näytä"),"' /></td></tr>";

	echo "</table>";
	echo "</form>";

	if (isset($submit_form)) {

		if ($volyymisuure != '' and count($tilat) > 0 and (count($varasto) > 1 or count($keraysvyohyke) > 1 or count($toimitustapa) > 0 or count($prioriteetit) > 0 )) {

			$selectlisa = $volyymisuure == 'kg' ? " ROUND(tilausrivi.varattu * tuote.tuotemassa, 0)" : ($volyymisuure == 'litrat' ? " ROUND(tilausrivi.varattu * (tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * 1000), 0)" : " 1");

			$select_aloittamatta = ", 0 AS 'aloittamatta'";
			$select_aloitettu = ", 0 AS 'aloitettu'";
			$select_keratty = ", 0 AS 'keratty'";

			$groupbylisa = "";

			$tilalisa = "";

			if (isset($tilat['aloittamatta'])) {

				if ($volyymisuure == 'rivit') {
					$selectlisa = "1";
					$groupbylisa = "GROUP BY 1";
				}

				$tilalisa = "(lasku.tila = 'N' AND lasku.alatila = 'A')";
				$select_aloittamatta = ", SUM(IF((lasku.tila = 'N' AND lasku.alatila = 'A'), {$selectlisa}, 0)) AS 'aloittamatta'";
			}

			if (isset($tilat['aloitettu'])) {

				if ($tilalisa != "") {
					$tilalisa .= " OR ";
				}

				$tilalisa .= "(lasku.tila = 'L' AND lasku.alatila = 'A')";

				if ($volyymisuure == 'rivit') {
					$selectlisa = "1";
					$groupbylisa = "GROUP BY 1";
				}

				$select_aloitettu = ", SUM(IF((lasku.tila = 'L' AND lasku.alatila = 'A'), {$selectlisa}, 0)) AS 'aloitettu'";
			}

			if (isset($tilat['keratty'])) {

				if ($tilalisa != "") {
					$tilalisa .= " OR ";
				}

				$tilalisa .= "(lasku.tila = 'L' AND lasku.alatila IN ('B', 'C'))";

				if ($volyymisuure == 'rivit') {
					$selectlisa = "COUNT(IF(tilausrivi.kerattyaika = '0000-00-00 00:00:00', 0, 1))";
					$groupbylisa = "GROUP BY 1";
				}

				$select_keratty = ", IF((lasku.tila = 'L' AND lasku.alatila IN ('B', 'C')), {$selectlisa}, 0) AS 'keratty'";
			}

			// poistetaan defaultit
			array_shift($varasto);
			array_shift($keraysvyohyke);
			array_shift($toimitustapa);
			array_shift($prioriteetit);

			$varastolisa = count($varasto) > 0 ? " AND lasku.varasto IN (".implode(",", $varasto).") " : "";
			$keraysvyohykelisa = count($keraysvyohyke) > 0 ? " JOIN keraysvyohyke ON (keraysvyohyke.yhtio = lasku.yhtio AND keraysvyohyke.varasto = lasku.varasto AND keraysvyohyke.tunnus IN (".implode(",", $keraysvyohyke).") AND keraysvyohyke.tunnus = vh.keraysvyohyke) " : "";
			$vhlisa = $keraysvyohykelisa != "" ? " AND vh.keraysvyohyke IN (".implode(",", $keraysvyohyke).") " : "";
			$toimitustapalisa = count($toimitustapa) > 0 ? " JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa AND toimitustapa.extranet != 'K' AND toimitustapa.tunnus IN (".implode(",", $toimitustapa).") AND toimitustapa.selite = lasku.toimitustapa) " : "";
			$prioriteettilisa = count($prioriteetit) > 0 ? " AND lasku.prioriteettinro IN (".implode(",", $prioriteetit).") " : "";

			if ($ajankohta == 'past') {
				$past_date_pp_alku = (int) $past_date_pp_alku;
				$past_date_kk_alku = (int) $past_date_kk_alku;
				$past_date_vv_alku = (int) $past_date_vv_alku;

				$past_date_pp_loppu = (int) $past_date_pp_loppu;
				$past_date_kk_loppu = (int) $past_date_kk_loppu;
				$past_date_vv_loppu = (int) $past_date_vv_loppu;

				$luontiaikalisa = " AND lahdot.pvm >= '{$past_date_vv_alku}-{$past_date_kk_alku}-{$past_date_pp_alku} 00:00:00' AND lahdot.pvm <= '{$past_date_vv_loppu}-{$past_date_kk_loppu}-{$past_date_pp_loppu} 23:59:59' ";
			}
			elseif ($ajankohta == 'future') {
				$future_date_pp_alku = (int) $future_date_pp_alku;
				$future_date_kk_alku = (int) $future_date_kk_alku;
				$future_date_vv_alku = (int) $future_date_vv_alku;

				$future_date_pp_loppu = (int) $future_date_pp_loppu;
				$future_date_kk_loppu = (int) $future_date_kk_loppu;
				$future_date_vv_loppu = (int) $future_date_vv_loppu;

				$luontiaikalisa = " AND lahdot.pvm >= '{$future_date_vv_alku}-{$future_date_kk_alku}-{$future_date_pp_alku} 00:00:00' AND lahdot.pvm <= '{$future_date_vv_loppu}-{$future_date_kk_loppu}-{$future_date_pp_loppu} 23:59:59' ";
			}
			else {
				$luontiaikalisa = " AND lahdot.pvm >= '".date("Y-m-d")." 00:00:00' AND lahdot.pvm <= '".date("Y-m-d")." 23:59:59' ";
			}

			$query = "	SELECT SUBSTRING(lahdot.lahdon_kellonaika, 1, 5) AS 'klo', GROUP_CONCAT(lasku.tunnus) tunnukset
						{$select_aloittamatta}
						{$select_aloitettu}
						{$select_keratty}
						FROM lasku
						JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D' AND tilausrivi.var NOT IN ('P', 'J') AND tilausrivi.varattu > 0)
						JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso {$vhlisa})
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						JOIN lahdot ON (lahdot.yhtio = lasku.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto AND lahdot.aktiivi IN ('','P','T') {$luontiaikalisa})
						{$keraysvyohykelisa}
						{$toimitustapalisa}
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.prioriteettinro != 0
						{$varastolisa}
						{$prioriteettilisa}
						AND ({$tilalisa})
						{$groupbylisa}
						ORDER BY 1";
			$res = pupe_query($query);

			$data = array();

			$arr = array();

			while ($row = mysql_fetch_assoc($res)) {

				if (!isset($arr[$row['klo']]['aloittamatta'])) $arr[$row['klo']]['aloittamatta'] = 0;
				if (!isset($arr[$row['klo']]['keratty'])) $arr[$row['klo']]['keratty'] = 0;
				if (!isset($arr[$row['klo']]['aloitettu'])) $arr[$row['klo']]['aloitettu'] = 0;

				$arr[$row['klo']]['aloittamatta'] += $row['aloittamatta'];
				$arr[$row['klo']]['keratty'] += $row['keratty'];
				$arr[$row['klo']]['aloitettu'] += $row['aloitettu'];
			}

			if (count($arr) > 0) {

				foreach ($arr as $klo => $summat) {
					$data[] = array($klo, $summat['keratty'], $summat['aloitettu'], $summat['aloittamatta'], 0);
				}

			}

			echo "<div id='chart_div_values' style='display:none;'>",json_encode($data),"</div>";
			echo "<div id='chart_div_volume' style='display:none;'>",ucwords($volyymisuure),"</div>";
		}
		else {
			echo "<font class='error'>",t("Tarkista valinnat"),"!</font>";
		}
	}

	echo "<br /><br />";

	echo "<div id='chart_div'></div>";

	echo "<br /><br />";

	$query = "	SELECT *
				FROM keraysvyohyke
				WHERE yhtio = '{$kukarow['yhtio']}'
				ORDER BY nimitys";
	$keraysvyohyke_res = pupe_query($query);

	if (mysql_num_rows($keraysvyohyke_res) > 0) {

		echo "<div id='divi'>";
		echo "<table>";
		echo "<tr>";
		echo "<th id='th_0'>",t("Kerääjä"),"</th>";

		$id = 1;
		while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_res)) {
			echo "<th id='th_$id'>{$keraysvyohyke_row['nimitys']}</th>";
			$id++;
		}

		echo "</tr>";
		echo "</table>";
		echo "</div>";

		echo "<table id='keraajat'>";

		echo "<thead>";
		echo "<tr>";
		echo "<th id='thead_keraaja'>",t("Kerääjä"),"</th>";

		mysql_data_seek($keraysvyohyke_res, 0);

		while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_res)) {
			echo "<th>{$keraysvyohyke_row['nimitys']}</th>";
		}

		echo "</tr>";
		echo "</thead>";

		$query = "	SELECT *
					FROM kuka
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND extranet = ''
					AND keraajanro != 0
					ORDER BY nimi";
		$kuka_res = pupe_query($query);

		while ($kuka_row = mysql_fetch_assoc($kuka_res)) {
			mysql_data_seek($keraysvyohyke_res, 0);

			echo "<tr>";
			echo "<td>{$kuka_row['nimi']}</td>";

			while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_res)) {
				$chk = strpos($kuka_row['keraysvyohyke'], $keraysvyohyke_row['tunnus']) !== false ? " checked" : "";
				echo "<td><input class='keraysvyohyke_checkbox' type='checkbox' name='{$kuka_row['kuka']}' value='{$keraysvyohyke_row['tunnus']}' {$chk} /></td>";
			}

			echo "</tr>";
		}

		echo "</table>";
	}


	require ("inc/footer.inc");
