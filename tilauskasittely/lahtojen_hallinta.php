<?php

	require ("../inc/parametrit.inc");

	enable_jquery();

	echo "	<script type='text/javascript' language='JavaScript'>
				$(document).ready(function() {

					//$('td').css({'padding': '0px', 'margin': '0px', 'border': '0px'});
					//$('td').css({'padding': '2px'});

					$(':checkbox').click(function(event){
						event.stopPropagation();
					});

					$('.toggleable').click(function(event){

						if ($('#toggleable_'+this.id).is(':visible')) {
							$('#toggleable_'+this.id).slideUp('fast');
						}
						else {
							var id = this.id;
							var parent_element = $('#toggleable_'+id).parent();

							window.setTimeout(function(){
								$('#toggleable_'+id).css('width', parent_element.width()+'px');
								$('#toggleable_'+id).slideDown('fast');
							}, 1);
						}
					});

					$('.toggleable_row').click(function(event){

						if ($('#toggleable_row_'+this.id).is(':visible')) {
							$('#toggleable_row_'+this.id).slideUp('fast');
						}
						else {
							var id = this.id;
							var parent_element = $('#toggleable_row_'+id).parent();

							window.setTimeout(function(){
								$('#toggleable_row_'+id).css('width', parent_element.width()+'px');
								$('#toggleable_row_'+id).slideDown('fast');
							}, 1);
						}
					});
				});
			</script>";

	echo "<font class='head'>",t("Lähtöjen hallinta"),"</font><hr>";

	$query = "	SELECT lahdot.tunnus AS lahdon_tunnus,
				lahdot.viimeinen_tilausaika,
				lahdot.lahdon_kellonaika,
				lahdot.kerailyn_aloitusaika,
				lasku.hyvaksynnanmuutos AS prioriteetti,
				toimitustapa.selite AS toimitustapa,
				COUNT(DISTINCT lasku.tunnus) AS tilatut,
				SUM(IF((lasku.tila = 'L' AND lasku.alatila IN ('B', 'C')), 1, 0)) AS valmiina,
				COUNT(DISTINCT tilausrivi.tunnus) AS suunnittelussa,
				SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS keratyt,
				GROUP_CONCAT(DISTINCT lasku.tunnus) AS tilaukset
				FROM lasku
				JOIN lahdot ON (lahdot.yhtio = lasku.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto AND lahdot.aktiivi = '')
				JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa)
				JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus)
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND ((lasku.tila = 'N' AND lasku.alatila = 'A') OR (lasku.tila = 'L' AND lasku.alatila IN ('A','B','C')))
				GROUP BY 1,2,3,4,5,6";
	$result = pupe_query($query);

	echo "<table style='padding:0px; margin:0px; border:0px;'>";
	echo "<tr>";
	echo "<th></th>";
	echo "<th>",t("Status"),"</th>";
	echo "<th>",t("Lähdön"),"<br>",t("Tunnus"),"</th>";
	echo "<th>",t("Prio"),"</th>";
	echo "<th>",t("Toimitustapa"),"</th>";
	echo "<th>",t("Viimeinen"),"<br>",t("Tilausaika"),"</th>";
	echo "<th>",t("Lähtöaika"),"</th>";
	echo "<th>",t("Tilaukset")," /<br>",t("Valmiina"),"</th>";
	echo "<th>",t("Rivikuorma"),"<br>",t("Suunnittelussa")," / ",t("Kerätyt"),"</th>";
	echo "<th>",t("Keräyksen"),"<br>",t("Aloitusaika"),"</th>";
	echo "</tr>";

	while ($row = mysql_fetch_assoc($result)) {

		echo "<tr>";

		echo "<td><input type='checkbox'></td>";
		echo "<td></td>";
		echo "<td class='toggleable' id='{$row['lahdon_tunnus']}'>{$row['lahdon_tunnus']}</td>";
		echo "<td>{$row['prioriteetti']}</td>";
		echo "<td>{$row['toimitustapa']}</td>";
		echo "<td>{$row['viimeinen_tilausaika']}</td>";
		echo "<td>{$row['lahdon_kellonaika']}</td>";
		echo "<td>{$row['tilatut']} / {$row['valmiina']}</td>";
		echo "<td>{$row['suunnittelussa']} / {$row['keratyt']}</td>";
		echo "<td>{$row['kerailyn_aloitusaika']}</td>";

		echo "</tr>";

		$query = "	SELECT lasku.tunnus AS tilauksen_tunnus,
					lasku.nimi AS asiakas_nimi,
					lasku.toim_nimi AS asiakas_toim_nimi,
					lasku.toim_postitp AS asiakas_toim_postitp,
					GROUP_CONCAT(pakkaus.pakkauskuvaus) AS pakkauskuvaukset,
					GROUP_CONCAT(kerayserat.sscc) AS sscc
					FROM lasku
					JOIN kerayserat ON (kerayserat.yhtio = lasku.yhtio AND kerayserat.otunnus = lasku.tunnus)
					JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
					JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tunnus IN ({$row['tilaukset']})
					GROUP BY 1,2,3,4";
		$lahto_res = pupe_query($query);

		echo "<tr>";
		echo "<td colspan='10' class='back'>";
		echo "<div id='toggleable_{$row['lahdon_tunnus']}' style='display:none;'>";

		echo "<table style='width:100%; padding:0px; margin:0px; border:0px;'>";

		echo "<tr>";
		echo "<th></th>";
		echo "<th>",t("Tilausnumero"),"</th>";
		echo "<th>",t("Asiakas"),"</th>";
		echo "<th>",t("Paikkakunta"),"</th>";
		echo "<th>",t("Keräysvyöhyke"),"</th>";
		echo "<th>",t("Rivit")," / ",t("Kerätyt"),"</th>";
		echo "<th>",t("SSCC"),"</th>";
		echo "<th>",t("Pakkaus"),"</th>";
		echo "<th>",t("Kg"),"</th>";
		echo "</tr>";

		while ($lahto_row = mysql_fetch_assoc($lahto_res)) {


			echo "<tr>";

			echo "<td><input type='checkbox'></td>";
			echo "<td class='toggleable_row' id='{$lahto_row['tilauksen_tunnus']}'>{$lahto_row['tilauksen_tunnus']}</td>";
			echo "<td>{$lahto_row['asiakas_nimi']}";
			if ($lahto_row['asiakas_nimi'] != $lahto_row['asiakas_toim_nimi']) echo "<br>{$lahto_row['asiakas_toim_nimi']}";
			echo "</td>";
			echo "<td>{$lahto_row['asiakas_toim_postitp']}</td>";

			$query = "	SELECT keraysvyohyke.nimitys AS keraysvyohyke,
						COUNT(tilausrivi.tunnus) AS rivit,
						SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS keratyt,
						ROUND(SUM(tilausrivi.varattu * tuote.tuotemassa), 0) AS kg
						FROM tilausrivi
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						JOIN keraysvyohyke ON (keraysvyohyke.yhtio = tuote.yhtio AND keraysvyohyke.tunnus = tuote.keraysvyohyke)
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.otunnus = '{$lahto_row['tilauksen_tunnus']}'
						GROUP BY 1";
			$til_res = pupe_query($query);
			$til_row = mysql_fetch_assoc($til_res);

			echo "<td>{$til_row['keraysvyohyke']}</td>";
			echo "<td>{$til_row['rivit']} / {$til_row['keratyt']}</td>";
			echo "<td>",str_replace(",", "<br>", $lahto_row['sscc']),"</td>";
			echo "<td nowrap>",str_replace(",", "<br>", $lahto_row['pakkauskuvaukset']),"</td>";
			echo "<td>{$til_row['kg']}</td>";

			echo "</tr>";

			$query = "	SELECT tilausrivi.tuoteno,
						kerayserat.sscc,
						tuote.nimitys,
						kerayserat.kpl,
						tilausrivi.yksikko,
						CONCAT(tilausrivi.hyllyalue,'-',tilausrivi.hyllynro,'-',tilausrivi.hyllyvali,'-',tilausrivi.hyllytaso) AS hyllypaikka,
						kerayserat.laatija AS keraaja,
						SUM(IF(tilausrivi.kerattyaika != '0000-00-00 00:00:00', 1, 0)) AS keratyt
						FROM tilausrivi
						JOIN kerayserat ON (kerayserat.yhtio = tilausrivi.yhtio AND kerayserat.tilausrivi = tilausrivi.tunnus)
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.otunnus = '{$lahto_row['tilauksen_tunnus']}'
						GROUP BY 1,2,3,4,5,6,7";
			$rivi_res = pupe_query($query);
			
			echo "<tr>";
			echo "<td colspan='9' class='back'>";
			echo "<div id='toggleable_row_{$lahto_row['tilauksen_tunnus']}' style='display:none;'>";

			echo "<table style='width:100%;'>";
	
			echo "<tr>";
			echo "<th>",t("SSCC"),"</th>";
			echo "<th>",t("Tuotenumero"),"</th>";
			echo "<th>",t("Nimitys"),"</th>";
			echo "<th>",t("Suunniteltu"),"<br>",t("Määrä"),"</th>";
			echo "<th>",t("Kerätty"),"<br>",t("Määrä"),"</th>";
			echo "<th>",t("Yksikkö"),"</th>";
			echo "<th>",t("Hyllypaikka"),"</th>";
			echo "<th>",t("Kerääjä"),"</th>";
			echo "</tr>";
			
			while ($rivi_row = mysql_fetch_assoc($rivi_res)) {
				echo "<tr>";
				echo "<td>{$rivi_row['sscc']}</td>";
				echo "<td>{$rivi_row['tuoteno']}</td>";
				echo "<td>{$rivi_row['nimitys']}</td>";
				echo "<td>{$rivi_row['kpl']}</td>";
				echo "<td>{$rivi_row['keratyt']}</td>";
				echo "<td>",t_avainsana("Y", "", " and avainsana.selite='{$rivi_row['yksikko']}'", "", "", "selite"),"</td>";
				echo "<td>{$rivi_row['hyllypaikka']}</td>";
				echo "<td>{$rivi_row['keraaja']}</td>";
				echo "</tr>";
			}
			
			echo "</table>";
			
			echo "</div>";
			echo "</td>";
			echo "</tr>";
		}

		echo "</table>";

		echo "</div>";
		echo "</td>";
		echo "</tr>";

	}

	echo "</table>";

	require ("inc/footer.inc");