<?php

	if (@include("../inc/parametrit.inc"));
	elseif (@include("parametrit.inc"));
	else exit;

	echo "<font class='head'>",t("Tavaraetikettikopio"),"</font><hr>";

	$sscc 	= !isset($sscc)	? "" : $sscc;
	$tee 	= !isset($tee)	? "" : $tee;
	$saapuminen = !isset($saapuminen) ? "" : $saapuminen;
	$otunnus = !isset($otunnus) ? "" : $otunnus;
	$suuntalavan_tunnus = !isset($suuntalavan_tunnus) ? "" : $suuntalavan_tunnus;
	$komento = !isset($komento) ? array() : $komento;
	$error_sscc	= $error_saapuminen = "";

	if (isset($toiminto) and (int) $toiminto > 0 and $suuntalavan_tunnus == "") {
		$suuntalavan_tunnus = $toiminto;
	}

	if ($tee == "etsi_sscc" and $sscc == "") {
		$error_sscc = "&larr;&nbsp;".t("Syötä SSCC-koodi").".";
	}

	if ($tee == "etsi_saapuminen" and $saapuminen == "") {
		$error_saapuminen = "&larr;&nbsp;".t("Syötä saapumisen numero").".";
	}

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

		$tee = '';
	}

	if (in_array($tee, array('', 'etsi_sscc', 'etsi_saapuminen'))) {

		echo "<form action='' method='post'>";
		echo "<table>";
		echo "<tr>";
		echo "<th>",t("SSCC"),"</th>";
		echo "<td><input type='text' name='sscc' value='{$sscc}' /></td>";
		echo "<td class='back' nowrap><input type='submit' value='",t("Etsi"),"' />&nbsp;&nbsp;<font class='error'>{$error_sscc}</font></td></tr>";
		echo "<input type='hidden' name='tee' value='etsi_sscc' />";
		echo "</table>";
		echo "</form>";

		echo "<br />";

		echo "<form action='' method='post'>";
		echo "<table>";
		echo "<tr>";
		echo "<th>",t("Saapumisen nro"),"</th>";
		echo "<td><input type='text' name='saapuminen' value='{$saapuminen}' /></td>";
		echo "<td class='back' nowrap><input type='submit' value='",t("Etsi"),"' />&nbsp;&nbsp;<font class='error'>{$error_saapuminen}</font></td></tr>";
		echo "<input type='hidden' name='tee' value='etsi_saapuminen' />";
		echo "</table>";
		echo "</form>";
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

	if ($tee == 'etsi_saapuminen' and $saapuminen != "") {

		$saapuminen = (int) $saapuminen;

		$query = "	SELECT suuntalavat.*, IF(suuntalavat.kaytettavyys = 'Y', '".t("Yksityinen")."', '".t("Yleinen")."') kaytettavyys,
					laskun_lisatiedot.laskutus_nimi,
					laskun_lisatiedot.laskutus_nimitark,
					laskun_lisatiedot.laskutus_osoite,
					laskun_lisatiedot.laskutus_postino,
					laskun_lisatiedot.laskutus_postitp,
					laskun_lisatiedot.laskutus_maa,
					lasku.*, lasku.luontiaika lasku_luontiaika, lasku.laatija lasku_laatija, lasku.tunnus otunnus,
					pakkaus.pakkaus, pakkaus.pakkauskuvaus, keraysvyohyke.nimitys ker_nimitys,
					suuntalavat.laatija, suuntalavat.luontiaika, suuntalavat.muuttaja, suuntalavat.muutospvm, suuntalavat.tunnus
					FROM suuntalavat
					JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus)
					JOIN lasku ON (lasku.yhtio = suuntalavat_saapuminen.yhtio AND lasku.tunnus = suuntalavat_saapuminen.saapuminen AND lasku.tila = 'K' AND lasku.laskunro = '{$saapuminen}')
					JOIN keraysvyohyke ON (keraysvyohyke.yhtio = suuntalavat.yhtio AND keraysvyohyke.tunnus = suuntalavat.keraysvyohyke)
					LEFT JOIN pakkaus ON (pakkaus.yhtio = suuntalavat.yhtio AND pakkaus.tunnus = suuntalavat.tyyppi)
					LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
					WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
					AND suuntalavat.tila = 'P'
					ORDER BY suuntalavat.sscc";
		$res = pupe_query($query);

		if (mysql_num_rows($res) > 0) {

			$row = mysql_fetch_assoc($res);
			mysql_data_seek($res, 0);

			if (trim($row['laskutus_nimi']) == '') {
				$row['laskutus_nimi'] 		= $row['nimi'];
				$row['laskutus_nimitark'] 	= $row['nimitark'];
				$row['laskutus_osoite'] 	= $row['osoite'];
				$row['laskutus_postino'] 	= $row['postino'];
				$row['laskutus_postitp'] 	= $row['postitp'];
				$row['laskutus_maa'] 		= $row['maa'];
			}

			echo "<br />";

			echo "<table>";

			echo "<tr>";
			echo "<th>",t("Ytunnus"),"</th>";
			echo "<th>",t("Ostaja"),"</th>";
			echo "<th>",t("Toimitusosoite"),"</th>";
			echo "<th>",t("Laskutusosoite"),"</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td>{$row['ytunnus']}</td>";
			echo "<td>{$row['nimi']}<br />{$row['osoite']}<br />{$row['postino']} {$row['postitp']}<br />{$row['maa']}</td>";
			echo "<td>{$row['toim_nimi']}<br />{$row['toim_osoite']}<br />{$row['toim_postino']} {$row['toim_postitp']}<br />{$row['toim_maa']}</td>";
			echo "<td>{$row['laskutus_nimi']}<br />{$row['laskutus_osoite']}<br />{$row['laskutus_postino']} {$row['laskutus_postitp']}<br />{$row['laskutus_maa']}</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>",t("Saapuminen"),"</th>";
			echo "<th>",t("Luontiaika"),"</th>";
			echo "<th>",t("Laatija"),"</th>";
			echo "<th>&nbsp;</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td>{$row['laskunro']}</td>";
			echo "<td>",tv1dateconv($row['lasku_luontiaika']),"</td>";
			echo "<td>{$row['lasku_laatija']}</td>";
			echo "<td>&nbsp;</td>";
			echo "</tr>";

			echo "</table>";

			echo "<br />";
			echo "<table>";
			echo "<tr>";
			echo "<th>",t("SSCC"),"</th>";
			echo "<th>",t("Tyyppi"),"</th>";
			echo "<th>",t("Keräysvyöhyke"),"</th>";
			echo "<th>",t("Käytettävyys"),"</th>";
			echo "<th>",t("Terminaalialue"),"</th>";
			echo "<th>",t("Korkeus"),"</th>";
			echo "<th>",t("Paino"),"</th>";
			echo "<th>",t("Laatija"),"</th>";
			echo "<th>",t("Luontiaika"),"</th>";
			echo "<th>",t("Muuttaja"),"</th>";
			echo "<th>",t("Muutospvm"),"</th>";
			echo "<th>&nbsp;</th>";
			echo "</tr>";

			while ($suuntalava_row = mysql_fetch_assoc($res)) {

				echo "<tr class='aktiivi'>";
				echo "<td>{$suuntalava_row['sscc']}</td>";
				echo "<td>{$suuntalava_row['pakkaus']} {$suuntalava_row['pakkauskuvaus']}</td>";
				echo "<td>{$suuntalava_row['ker_nimitys']}</td>";
				echo "<td>{$suuntalava_row['kaytettavyys']}</td>";
				echo "<td>{$suuntalava_row['terminaalialue']}</td>";
				echo "<td>{$suuntalava_row['korkeus']}</td>";
				echo "<td>{$suuntalava_row['paino']}</td>";
				echo "<td>{$suuntalava_row['laatija']}</td>";
				echo "<td>",tv1dateconv($suuntalava_row['luontiaika']),"</td>";
				echo "<td>{$suuntalava_row['muuttaja']}</td>";
				echo "<td>",tv1dateconv($suuntalava_row['muutospvm']),"</td>";

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
