<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>",t("Liitä tilaus laskun liitetiedostoksi"),"</font><hr>";

	if (!isset($laskunro)) $laskunro = '';

	echo "<table>";
	echo "<form method='post' action=''>";
	echo "<tr><th>",t("Laskunumero"),"</th><td><input type='text' name='laskunro' value='{$laskunro}' /></td><td><input type='submit' value='",t("Etsi"),"' /></td></tr>";
	echo "</form>";
	echo "</table>";

	if (trim($laskunro) != '' and is_numeric($laskunro)) {

		$query = "	SELECT lasku.tunnus, liitetiedostot.kayttotarkoitus, lasku.asiakkaan_tilausnumero
					FROM lasku
					JOIN liitetiedostot ON (liitetiedostot.yhtio = lasku.yhtio and liitetiedostot.liitos = 'lasku' AND liitetiedostot.liitostunnus = lasku.tunnus AND liitetiedostot.kayttotarkoitus IN ('FINVOICE', 'EDI'))
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.laskunro = '$laskunro'";
		$lasku_res = pupe_query($query);
		$lasku_row = mysql_fetch_assoc($lasku_res);

		if (mysql_num_rows($lasku_res) == 1 and $lasku_row['kayttotarkoitus'] == 'FINVOICE') {

			$onnistuiko = liita_tilaus_laskun_liitetiedostoksi($kukarow, $yhtiorow, $liitetaanko_editilaus_laskulle_hakemisto, $lasku_row['tunnus'], $lasku_row['asiakkaan_tilausnumero']);

			if ($onnistuiko) echo "<br/>",t("Laskun")," {$laskunro} ",t("liitetiedostoksi lisättin tilaus")," {$lasku_row['asiakkaan_tilausnumero']}.<br/>";
			else echo "<br/>",t("Liitetiedoston lisääminen epäonnistui"),". ",t("Tilausta ei löytynyt"),"!<br/>";

		}
		elseif (mysql_num_rows($lasku_res) == 2) {
			echo "<br/><font class='error'>",t("Laskuun on jo liitetty tilaus"),"!</font><br/>";
		}
		else {
			echo "<br/><font class='error'>",t("Finvoice-laskuaineistoa ei ole laskun liitetiedostona"),"!</font><br/>";
		}
	}

	require ("inc/footer.inc");

?>