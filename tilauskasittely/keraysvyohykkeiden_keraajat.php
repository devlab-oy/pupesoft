<?php

	require ("../inc/parametrit.inc");

	// if (isset($_POST['ajax_toiminto']) and trim($_POST['ajax_toiminto']) != '') {
	// 	$kuka_tunnus = (int) $_POST['kuka_tunnus'];
	// 	$tunnukset = mysql_real_escape_string($_POST['tunnukset']);
	// 
	// 	$query = "UPDATE kuka SET keraysvyohyke = '{$tunnukset}' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$kuka_tunnus}'";
	// 	$update_res = mysql_query($query) or pupe_error($query);
	// 
	// 	exit;
	// }

	// js_popup();

	// echo "<script type=\"text/javascript\" charset=\"utf-8\">
	// 		$(document).ready(function() {
	// 			$('input[type=\"checkbox\"]').click(function(){
	// 				var classi = $(this).attr('class');
	// 
	// 				var tmp = $(this).val().split(\"#\",2);
	// 				var kuka = tmp[1];
	// 
	// 				var tunnukset = '';
	// 
	// 				$('.'+classi).each(function(){
	// 					if ($(this).is(':checked')) {
	// 						tmp = $(this).val().split(\"#\", 2);
	// 
	// 						if (tunnukset == '') {
	// 							tunnukset = tmp[0];
	// 						}
	// 						else {
	// 							tunnukset = tmp[0] + ',' + tunnukset;
	// 						}
	// 
	// 						kuka = tmp[1];
	// 					}
	// 				});
	// 
	// 				$.post('{$_SERVER['SCRIPT_NAME']}', 
	// 					{ 	kuka_tunnus: kuka, 
	// 						tunnukset: tunnukset, 
	// 						ajax_toiminto: 'keraysvyohykkeiden_ruksit', 
	// 						no_head: 'yes', 
	// 						ohje: 'off'
	// 					}
	// 				);
	// 			});
	// 		});
	// 	  </script>";

	enable_jquery();

	echo "	<script type='text/javascript' language='JavaScript'>
				<!--

				$(document).ready(function() {

					$('th.keraysvyohyke').click(function() {
						var id = $(this).attr('id');

						if ($('tr[class^=\"asiakas_'+id+'\"]').is(':visible') === false && $('tr[class^=\"rivit_'+id+'\"]').is(':visible') === false) {
							$('tr.era_'+id).toggle();
						}
					});

					$('td.erat').click(function() {
						var id = this.id.split(\"_\");

						if ($('tr[class^=\"rivit_'+id[1]+'_'+id[2]+'\"]').is(':visible') === false) {
							console.log();
							$('tr.asiakas_'+id[1]+'_'+id[2]).toggle();
						}
					});

					$('td.asiakas').click(function() {
						var id = this.id.split(\"_\");
						$('tr.rivit_'+id[1]+'_'+id[2]+'_'+id[3]).toggle();
					});
				});

				//-->
			</script>";	

	echo "<font class='head'>",t("Keräysvyöhykkeiden kerääjät"),"</font><hr>";

	$query = "	SELECT keraysvyohyke.nimitys AS 'ker_nimitys',
				GROUP_CONCAT(DISTINCT lasku.tunnus) AS 'tilaukset',
				COUNT(DISTINCT lasku.tunnus) AS 'tilatut',
				COUNT(DISTINCT tilausrivi.tunnus) AS 'suunnittelussa',
				SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS 'keratyt',
				ROUND(SUM(tilausrivi.varattu * tuote.tuotemassa), 0) AS 'kg_suun',
				ROUND(SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', tilausrivi.varattu * tuote.tuotemassa, 0)), 0) AS 'kg_ker'
				FROM lasku
				JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
				JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
				JOIN keraysvyohyke ON (keraysvyohyke.yhtio = vh.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)
				JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
				JOIN lahdot ON (lahdot.yhtio = lasku.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto AND lahdot.aktiivi IN ('', 'P'))
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))
				GROUP BY keraysvyohyke.nimitys
				ORDER BY 1";
	$result = pupe_query($query);

	echo "<table>";
	echo "<tr>";
	echo "<th>",t("Keräysvyöhyke"),"</th>";
	echo "<th>",t("Tilaukset"),"</th>";
	echo "<th>",t("Rivit"),"</th>";
	echo "<th>",t("Kilot"),"</th>";
	echo "<th>",t("Keräyserän aloitusaika"),"</th>";
	echo "</tr>";

	$i = 0;

	while ($row = mysql_fetch_assoc($result)) {
		echo "<tr>";
		echo "<th class='keraysvyohyke' id='{$i}'>{$row['ker_nimitys']}</th>";
		echo "<td>";

		$query = "	SELECT SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS 'keratyt'
					FROM lasku 
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tunnus IN ({$row['tilaukset']})";
		$chk_res = pupe_query($query);

		$chk = 0;

		while ($chk_row = mysql_fetch_assoc($chk_res)) {
			if ($chk_row['keratyt'] != 0) {
				$chk++;
			}
		}

		echo "{$chk} / {$row['tilatut']}";
		echo "</td>";

		echo "<td>{$row['keratyt']} / {$row['suunnittelussa']}</td>";
		echo "<td>{$row['kg_ker']} / {$row['kg_suun']}</td>";

		echo "<td></td>";
		echo "</tr>";

		$query = "	SELECT kuka.nimi AS 'keraaja', 
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
					GROUP BY 1
					ORDER BY 1";
		$era_res = pupe_query($query);

		if (mysql_num_rows($era_res)> 0) {

			$x = 0;

			while ($era_row = mysql_fetch_assoc($era_res)) {
				echo "<tr class='era_{$i}' style='display:none;'>";
				echo "<td class='erat' id='erat_{$i}_{$x}'>{$era_row['keraaja']}</td>";
				echo "<td>{$era_row['tilaukset']}</td>";
				echo "<td>{$era_row['rivit']}</td>";
				echo "<td>{$era_row['kg']}</td>";
				echo "<td>{$era_row['aloitusaika']}</td>";
				echo "</tr>";

				echo "<tr class='asiakas_{$i}_{$x}' style='display:none;'>";
				echo "<th>",t("Tila"),"</th>";
				echo "<th colspan='2'>",t("Toimitusasiakas"),"</th>";
				echo "<th>",t("Lähtö"),"</th>";
				echo "<th>",t("Toimitustapa"),"</th>";
				echo "</tr>";

				$query = "	SELECT CONCAT(lasku.nimi, ' ', lasku.nimitark) AS 'nimi',
							lasku.toimitustavan_lahto,
							lasku.toimitustapa,
							lasku.tunnus,
							lasku.tila, lasku.alatila
							FROM lasku
							WHERE lasku.yhtio = '{$kukarow['yhtio']}'
							AND lasku.tunnus IN ({$row['tilaukset']})
							ORDER BY 1,2,3";
				$asiakas_res = pupe_query($query);

				$y = 0;

				while ($asiakas_row = mysql_fetch_assoc($asiakas_res)) {
					echo "<tr class='asiakas_{$i}_{$x}' style='display:none;'>";

					echo "<td>";

					if ($asiakas_row['tila'] == 'N' and $asiakas_row['alatila'] == 'A') {
						echo t("Aloittamatta");
					}
					else if ($asiakas_row['tila'] == 'L' and $asiakas_row['alatila'] == 'A') {
						echo t("Aloitettu");
					}
					else {
						echo t("Kerätty");
					}

					echo "</td>";

					echo "<td colspan='2' class='asiakas' id='asiakas_{$i}_{$x}_{$y}'>{$asiakas_row['nimi']}</td>";
					echo "<td>{$asiakas_row['toimitustavan_lahto']}</td>";
					echo "<td>{$asiakas_row['toimitustapa']}</td>";
					echo "</tr>";

					$query = "	SELECT tilausrivi.tuoteno, 
								tuote.nimitys, 
								CONCAT(tilausrivi.hyllyalue, '-', tilausrivi.hyllynro, '-', tilausrivi.hyllyvali, '-', tilausrivi.hyllytaso) AS 'kerayspaikka',
								ROUND(SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', tilausrivi.varattu, 0)), 0) AS 'keratty',
								ROUND(SUM(tilausrivi.varattu), 0) AS 'tilattu'
								FROM kerayserat
								JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi AND tilausrivi.tyyppi != 'D')
								JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
								WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
								AND kerayserat.otunnus = '{$asiakas_row['tunnus']}'
								GROUP BY 1,2,3";
					$rivi_res = pupe_query($query);

					if (mysql_num_rows($rivi_res) > 0) {
						echo "<tr class='rivit_{$i}_{$x}_{$y}' style='display:none;'>";
						echo "<th>",t("Tuotenro"),"</th>";
						echo "<th>",t("Tuotekuvaus"),"</th>";
						echo "<th>",t("Keräyspaikka"),"</th>";
						echo "<th>",t("Kerätty / Tilattu"),"</th>";
						echo "<th></th>";
						echo "</tr>";

						while ($rivi_row = mysql_fetch_assoc($rivi_res)) {
							echo "<tr class='rivit_{$i}_{$x}_{$y}' style='display:none;'>";
							echo "<td>{$rivi_row['tuoteno']}</td>";
							echo "<td>{$rivi_row['nimitys']}</td>";
							echo "<td>{$rivi_row['kerayspaikka']}</td>";
							echo "<td>{$rivi_row['keratty']} / {$rivi_row['tilattu']}</td>";
							echo "<td></td>";
							echo "</tr>";
						}						

						echo "<tr class='rivit_{$i}_{$x}_{$y}' style='display:none;'>";
						echo "<td colspan='5' class='back'>&nbsp;</td>";
						echo "</tr>";
					}

					$y++;
				}

				echo "<tr class='asiakas_{$i}_{$x}' style='display:none;'>";
				echo "<td colspan='5' class='back'>&nbsp;</td>";
				echo "</tr>";

				$x++;
			}

			echo "<tr class='era_{$i}' style='display:none;'>";
			echo "<td colspan='5' class='back'>&nbsp;</td>";
			echo "</tr>";
		}

		$i++;
	}

	echo "</table>";

	// $query = "	SELECT kuka.nimi, kuka.keraysvyohyke, kuka.tunnus
	// 			FROM kuka
	// 			WHERE kuka.yhtio = '{$kukarow['yhtio']}'
	// 			AND kuka.extranet = ''
	// 			AND kuka.nimi != ''
	// 			ORDER BY 1,2";
	// $result = mysql_query($query) or pupe_error($query);
	// 
	// // ulompi table
	// echo "<table><tr><td class='back'>";
	// 
	// echo "<table>";
	// echo "<tr><th>",t("Kerääjä"),"</th><th>",t("Keräysvyöhykkeet"),"</th></tr>";
	// 
	// while ($row = mysql_fetch_assoc($result)) {
	// 	$query = "SELECT group_concat(concat(tunnus, '#', nimitys)) keraysvyohykkeet FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}'";
	// 	$keraysvyohyke_result = mysql_query($query) or pupe_error($query);
	// 	$keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result);
	// 
	// 	echo "<tr><td>{$row['nimi']}</td>";
	// 
	// 	echo "<td>";
	// 
	// 	foreach(explode(",", $keraysvyohyke_row['keraysvyohykkeet']) as $k) {
	// 		list($ker_tunnus, $ker_nimitys) = explode("#", $k);
	// 
	// 		$checked = (trim($row['keraysvyohyke']) != '' and $row['keraysvyohyke'] != 0 and in_array($ker_tunnus, explode(",", $row['keraysvyohyke']))) ? " checked='checked'" : '';
	// 
	// 		echo "<input class='{$row['tunnus']}' type='checkbox' name='keraysvyohyke[]' value='{$ker_tunnus}#{$row['tunnus']}'{$checked} /> {$ker_nimitys}<br />";
	// 	}
	// 
	// 	echo "</td>";
	// 	echo "</tr>";
	// }
	// 
	// echo "</table>";
	// 
	// echo "</td><td class='back'>";
	// 
	// echo "<table>";
	// echo "<tr><th>",t("Keräysvyöhyke"),"</th><th>",t("Tilauksia"),"</th><th>",t("Rivejä"),"</th></tr>";
	// 
	// $query = "	SELECT *
	// 			FROM keraysvyohyke
	// 			WHERE yhtio = '{$kukarow['yhtio']}'";
	// $keraysvyohyke_result = mysql_query($query) or pupe_error($query);
	// 
	// while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result)) {
	// 	echo "<tr>";
	// 
	// 	$query = "	SELECT count(DISTINCT lasku.tunnus) l_kpl, count(DISTINCT tilausrivi.tunnus) t_kpl
	// 				FROM lasku
	// 				JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus)
	// 				JOIN varaston_hyllypaikat ON (varaston_hyllypaikat.yhtio = tilausrivi.yhtio 
	// 											AND varaston_hyllypaikat.hyllyalue = tilausrivi.hyllyalue 
	// 											AND varaston_hyllypaikat.hyllynro = tilausrivi.hyllynro 
	// 											AND varaston_hyllypaikat.hyllyvali = tilausrivi.hyllyvali
	// 											AND varaston_hyllypaikat.hyllytaso = tilausrivi.hyllytaso
	// 											AND varaston_hyllypaikat.keraysvyohyke = '{$keraysvyohyke_row['tunnus']}')
	// 				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
	// 				AND lasku.tila = 'N'
	// 				AND lasku.alatila = 'A'";
	// 	$lasku_kpl_res = mysql_query($query) or pupe_error($query);
	// 	$lasku_kpl_row = mysql_fetch_assoc($lasku_kpl_res);
	// 
	// 	echo "<td>{$keraysvyohyke_row['nimitys']}</td><td>{$lasku_kpl_row['l_kpl']}</td><td>{$lasku_kpl_row['t_kpl']}</td>";
	// 
	// 	echo "</tr>";
	// }
	// 
	// echo "</table>";
	// 
	// echo "</td></tr></table>";

	require ("inc/footer.inc");
