<?php

	require ("../inc/parametrit.inc");

	if (isset($_POST['ajax_toiminto']) and trim($_POST['ajax_toiminto']) != '') {
		$kuka_tunnus = (int) $_POST['kuka_tunnus'];
		$tunnukset = mysql_real_escape_string($_POST['tunnukset']);

		$query = "UPDATE kuka SET keraysvyohyke = '{$tunnukset}' WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$kuka_tunnus}'";
		$update_res = mysql_query($query) or pupe_error($query);

		exit;
	}

	js_popup();

	echo "<script type=\"text/javascript\" charset=\"utf-8\">
			$(document).ready(function() {
				$('input[type=\"checkbox\"]').click(function(){
					var classi = $(this).attr('class');

					var tmp = $(this).val().split(\"#\",2);
					var kuka = tmp[1];

					var tunnukset = '';

					$('.'+classi).each(function(){
						if ($(this).is(':checked')) {
							tmp = $(this).val().split(\"#\", 2);

							if (tunnukset == '') {
								tunnukset = tmp[0];
							}
							else {
								tunnukset = tmp[0] + ',' + tunnukset;
							}

							kuka = tmp[1];
						}
					});

					$.post('{$_SERVER['SCRIPT_NAME']}', 
						{ 	kuka_tunnus: kuka, 
							tunnukset: tunnukset, 
							ajax_toiminto: 'keraysvyohykkeiden_ruksit', 
							no_head: 'yes', 
							ohje: 'off'
						}
					);
				});
			});
		  </script>";

	echo "<font class='head'>",t("Keräysvyöhykkeiden kerääjät"),"</font><hr>";

	$query = "	SELECT kuka.nimi, kuka.keraysvyohyke, kuka.tunnus
				FROM kuka
				WHERE kuka.yhtio = '{$kukarow['yhtio']}'
				AND kuka.extranet = ''
				AND kuka.nimi != ''
				ORDER BY 1,2";
	$result = mysql_query($query) or pupe_error($query);

	// ulompi table
	echo "<table><tr><td class='back'>";

	echo "<table>";
	echo "<tr><th>",t("Kerääjä"),"</th><th>",t("Keräysvyöhykkeet"),"</th></tr>";

	while ($row = mysql_fetch_assoc($result)) {
		$query = "SELECT group_concat(concat(tunnus, '#', nimitys)) keraysvyohykkeet FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}'";
		$keraysvyohyke_result = mysql_query($query) or pupe_error($query);
		$keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result);

		echo "<tr><td>{$row['nimi']}</td>";

		echo "<td>";

		foreach(explode(",", $keraysvyohyke_row['keraysvyohykkeet']) as $k) {
			list($ker_tunnus, $ker_nimitys) = explode("#", $k);

			$checked = (trim($row['keraysvyohyke']) != '' and $row['keraysvyohyke'] != 0 and in_array($ker_tunnus, explode(",", $row['keraysvyohyke']))) ? " checked='checked'" : '';

			echo "<input class='{$row['tunnus']}' type='checkbox' name='keraysvyohyke[]' value='{$ker_tunnus}#{$row['tunnus']}'{$checked} /> {$ker_nimitys}<br />";
		}

		echo "</td>";
		echo "</tr>";
	}

	echo "</table>";

	echo "</td><td class='back'>";

	echo "<table>";
	echo "<tr><th>",t("Keräysvyöhyke"),"</th><th>",t("Tilauksia"),"</th><th>",t("Rivejä"),"</th></tr>";

	$query = "	SELECT *
				FROM keraysvyohyke
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$keraysvyohyke_result = mysql_query($query) or pupe_error($query);

	while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result)) {
		echo "<tr>";

		$query = "	SELECT count(DISTINCT lasku.tunnus) l_kpl, count(DISTINCT tilausrivi.tunnus) t_kpl
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus)
					JOIN varaston_hyllypaikat ON (varaston_hyllypaikat.yhtio = tilausrivi.yhtio 
												AND varaston_hyllypaikat.hyllyalue = tilausrivi.hyllyalue 
												AND varaston_hyllypaikat.hyllynro = tilausrivi.hyllynro 
												AND varaston_hyllypaikat.hyllyvali = tilausrivi.hyllyvali
												AND varaston_hyllypaikat.hyllytaso = tilausrivi.hyllytaso
												AND varaston_hyllypaikat.keraysvyohyke = '{$keraysvyohyke_row['tunnus']}')
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tila = 'N'
					AND lasku.alatila = 'A'";
		$lasku_kpl_res = mysql_query($query) or pupe_error($query);
		$lasku_kpl_row = mysql_fetch_assoc($lasku_kpl_res);

		echo "<td>{$keraysvyohyke_row['nimitys']}</td><td>{$lasku_kpl_row['l_kpl']}</td><td>{$lasku_kpl_row['t_kpl']}</td>";

		echo "</tr>";
	}

	echo "</table>";

	echo "</td></tr></table>";

	require ("inc/footer.inc");
