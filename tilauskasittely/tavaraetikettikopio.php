<?php

	if (@include("../inc/parametrit.inc"));
	elseif (@include("parametrit.inc"));
	else exit;

	echo "<font class='head'>",t("Tavaraetikettikopio"),"</font><hr>";

	$sscc 	= !isset($sscc)	? "" : $sscc;
	$tee 	= !isset($tee)	? "" : $tee;
	$otunnus = !isset($otunnus) ? "" : $otunnus;
	$suuntalavan_tunnus = !isset($suuntalavan_tunnus) ? "" : $suuntalavan_tunnus;
	$komento = !isset($komento) ? array() : $komento;
	$error_sscc	= "";

	if (isset($toiminto) and (int) $toiminto > 0 and $suuntalavan_tunnus == "") {
		$suuntalavan_tunnus = $toiminto;
	}

	if ($tee == "etsi_sscc" and $sscc == "") {
		$error_sscc = "&larr;&nbsp;".t("Syötä SSCC-koodi").".";
	}

	echo "<form action='' method='post'>";
	echo "<table>";
	echo "<tr>";
	echo "<th>",t("SSCC"),"</th>";
	echo "<td><input type='text' name='sscc' value='{$sscc}' /></td>";
	echo "<td class='back' nowrap><input type='submit' value='",t("Etsi"),"' />&nbsp;&nbsp;<font class='error'>{$error_sscc}</font></td></tr>";
	echo "<input type='hidden' name='tee' value='etsi_sscc' />";
	echo "</table>";
	echo "</form>";

	if ($tee == 'tavaraetiketti' and $otunnus != "" and $suuntalavan_tunnus != "") {

		if (count($komento) == 0) {

			// laitetaan suuntalavan tunnus talteen muuttujaan
			$toiminto = $suuntalavan_tunnus;

			$tulostimet[] = "Tavaraetiketti";
			require('inc/valitse_tulostin.inc');
		}

		$otunnus = (int) $otunnus;
		$suuntalavat = array((int) $suuntalavan_tunnus);

		require('tilauskasittely/tulosta_tavaraetiketti.inc');

	}

	if ($tee == 'etsi_sscc' and $sscc != "") {

		$sscc = mysql_real_escape_string($sscc);

		$query = "	SELECT suuntalavat.tunnus,
					toimi.toimittajanro,
					lasku.ytunnus, lasku.tunnus AS otunnus,
					TRIM(CONCAT(lasku.nimi, ' ', lasku.nimitark)) AS nimi,
					suuntalavat_saapuminen.saapuminen, lasku.laskunro,
					lasku.luontiaika, kuka.nimi AS laatija
					FROM suuntalavat
					JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus)
					JOIN lasku ON (lasku.yhtio = suuntalavat_saapuminen.yhtio AND lasku.tunnus = suuntalavat_saapuminen.saapuminen AND lasku.tila = 'K')
					JOIN toimi ON (toimi.yhtio = lasku.yhtio AND toimi.tunnus = lasku.liitostunnus)
					JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = lasku.laatija)
					WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
					AND suuntalavat.tila = 'P'
					AND suuntalavat.sscc = '{$sscc}'
					ORDER BY nimi, lasku.luontiaika";
		$suuntalava_res = pupe_query($query);

		if (mysql_num_rows($suuntalava_res) > 0) {

			echo "<br />";
			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Toimittajanro"),"</th>";
			echo "<th>",t("Ytunnus"),"</th>";
			echo "<th>",t("Nimi"),"</th>";
			echo "<th>",t("Saapuminen"),"</th>";
			echo "<th>",t("Luontiaika"),"</th>";
			echo "<th>",t("Laatija"),"</th>";
			echo "<th>&nbsp;</th>";
			echo "</tr>";

			while ($suuntalava_row = mysql_fetch_assoc($suuntalava_res)) {

				echo "<tr class='aktiivi'>";
				echo "<td>{$suuntalava_row['toimittajanro']}</td>";
				echo "<td>{$suuntalava_row['ytunnus']}</td>";
				echo "<td>{$suuntalava_row['nimi']}</td>";
				echo "<td>{$suuntalava_row['laskunro']} ($suuntalava_row[tunnus])</td>";
				echo "<td>",tv1dateconv($suuntalava_row['luontiaika']),"</td>";
				echo "<td>{$suuntalava_row['laatija']}</td>";

				echo "<td>";
				echo "<form action='' method='post'>";
				echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalava_row['tunnus']}' />";
				echo "<input type='hidden' name='otunnus' value='{$suuntalava_row['otunnus']}' />";
				echo "<input type='hidden' name='tee' value='tavaraetiketti' />";
				echo "<input type='submit' value='",t("Valitse"),"' />";
				echo "</form>";
				echo "</td>";

				echo "</tr>";
			}

			echo "</table>";
		}
		else {
			echo "<br /><font class='message'>",t("Suuntalavaa ei löytynyt"),".</font><br /><br />";
		}

	}

	if (@include("inc/footer.inc"));
	elseif (@include("footer.inc"));
	else exit;
