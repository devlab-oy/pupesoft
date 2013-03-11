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

	echo "	<script type='text/javascript'>

				$(function() {

			        var stickyHeaderTop = $('#keraajat thead').offset().top;
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

			        // $('#divi').hide();

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

						$.post('',
									{ 	kuka: name,
										keraysvyohyke: keraysvyohykkeet,
										ajax_toiminto: 'paivita_keraysvyohyke',
										yhtio: '{$kukarow['yhtio']}',
										no_head: 'yes',
										ohje: 'off' });
					});
				});

			</script>";

	echo "<font class='head'>",t("Keräysvyöhykeallokointi"),"</font><hr>";

	$query = "	SELECT *
				FROM keraysvyohyke
				WHERE yhtio = '{$kukarow['yhtio']}'
				ORDER BY nimitys";
	$keraysvyohyke_res = pupe_query($query);

	if (mysql_num_rows($keraysvyohyke_res) > 0) {

		echo "<div id='divi' style='display:none;'>";
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

