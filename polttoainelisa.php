<?php

	require("inc/parametrit.inc");

	echo "<font class='head'>",t("Rahtimaksujen polttoainelisä"),"</font><hr>";
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<table>";

	if (isset($lisaa) and isset($polttoainelisa) and trim($polttoainelisa) != '') {

		$polttoainelisa = str_replace(",",".",$polttoainelisa);
		if ($polttoainelisa == '0' or (float)$polttoainelisa == '0') {
			echo "<font class='error'>",t("Polttoainelisä ei saa olla nolla"),"!</font><br/><br/>";
		}
		else {

			$toimitustapa = mysql_real_escape_string($toimitustapa);

			$query = "	SELECT *
						FROM rahtimaksut
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND rahtihinta != ''
						AND toimitustapa = '$toimitustapa'";
			$rahtimaksut_res = mysql_query($query) or pupe_error($query);

			$rahtimaksu = '';

			while ($rahtimaksut_row = mysql_fetch_assoc($rahtimaksut_res)) {
				$rahtimaksu = $rahtimaksut_row['rahtihinta'] * $polttoainelisa;
				
				$query = "	UPDATE rahtimaksut SET 
							rahtihinta = '$rahtimaksu',
							muutospvm = now(),
							muuttaja = '{$kukarow['kuka']}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$rahtimaksut_row['tunnus']}'";
				$update_res = mysql_query($query) or pupe_error($query);
			}

			echo "<font class='message'>",t("Toimitustavan")," $toimitustapa ",t("rahtihinnat kerrottiin kertoimella")," $polttoainelisa</font><br/><br/>";
		}
	}

	if (isset($lisaa) and $lisaa != '' and isset($polttoainelisa) and trim($polttoainelisa) == '') {
		echo "<font class='error'>",t("Polttoainelisä ei saa olla tyhjä"),"!</font><br/><br/>";
	}

	$query = "	SELECT DISTINCT rahtimaksut.toimitustapa
				FROM rahtimaksut
				JOIN toimitustapa ON (toimitustapa.yhtio = rahtimaksut.yhtio AND toimitustapa.selite = rahtimaksut.toimitustapa)
				WHERE rahtimaksut.yhtio = '{$kukarow['yhtio']}'
				AND rahtimaksut.rahtihinta != ''
				ORDER BY rahtimaksut.toimitustapa ASC";
	$toimitustapa_res = mysql_query($query) or pupe_error($query);
	echo "<tr><th>",t("Toimitustapa"),":</th><td class='back'>";
	echo "<select name='toimitustapa' id='toimitustapa'>";

	while ($toimitustapa_row = mysql_fetch_array($toimitustapa_res)) {
		echo "<option value='{$toimitustapa_row["toimitustapa"]}'>{$toimitustapa_row["toimitustapa"]}</option>";
	}

	echo "</select></td></tr>";
	
	echo "<tr><th>",t("Polttoainelisän hintakerroin"),":</th><td class='back'><input type='text' name='polttoainelisa' id='polttoainelisa' value='' size='5'><input type='submit' name='lisaa' id='lisaa' value='",t("Lisää"),"'></td></tr>";

	echo "</table>";
	echo "</form>";

	require("inc/footer.inc");

?>