<?php

	$toimitusvahvistus_email_lahetys = true;

	if (!isset($rakir_row) or !isset($rakir_row['toimitusvahvistus']) or !isset($rakir_row['asiakas_email']) or !isset($tunnukset) or !isset($otunnukset)) {
		$toimitusvahvistus_email_lahetys = false;
	}

	if ($rakir_row['toimitusvahvistus'] != 'toimitusvahvistus_email.inc' or $rakir_row['asiakas_email'] == '' or $tunnukset == '' or $otunnukset == '') {
		$toimitusvahvistus_email_lahetys = false;
	}

	if ($toimitusvahvistus_email_lahetys) {

		$body = t("Hei").",\n\n".t("Seuraavat tilaamanne tuotteet ovat toimituksessa").":\n\n".t("Tuotenumero")." ".t("Määrä")." ".t("Nimitys")." ".t("Hinta")."\n";

		$query = "	SELECT tilausrivi.tuoteno, tilausrivi.varattu+tilausrivi.kpl as maara, tilausrivi.nimitys, tilausrivi.rivihinta, lasku.valkoodi
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tunnus IN ({$otunnukset})";
		$rivi_loop_res = pupe_query($query);

		while ($rivi_loop_row = mysql_fetch_assoc($rivi_loop_res)) {
			$body .= "{$rivi_loop_row['tuoteno']} {$rivi_loop_row['maara']} {$rivi_loop_row['nimitys']} {$rivi_loop_row['hinta']} {$rivi_loop_row['valkoodi']}\n";
		}

		$body .= "\n";

		$query = "	SELECT rahtikirjanro
					FROM rahtikirjat
					WHERE tunnus IN ({$tunnukset})
					AND yhtio = '{$kukarow['yhtio']}'";
		$seurantakoodit_res = pupe_query($query);

		$body .= t("Seurantakoodit").":\n";

		while ($seurantakoodit_row = mysql_fetch_assoc($seurantakoodit_res)) {
			$body .= "{$seurantakoodit_row['rahtikirjanro']}\n";
		}

		$body .= "\n";

		$body .= t("Toimituksen toimitusosoite").":\n\n";

		$body .= "{$rakir_row['toim_nimi']} {$rakir_row['toim_nimitark']}\n";
		$body .= "{$rakir_row['toim_osoite']}\n";
		$body .= "{$rakir_row['toim_postino']} {$rakir_row['toim_postitp']}\n";
		$body .= "{$rakir_row['toim_maa']}\n";

		// Sähköpostin lähetykseen parametrit
		$parametri = array( "to" 			=> $rakir_row['asiakas_email'],
							"cc" 			=> "",
							"subject"		=> t("Toimitusvahvistus"),
							"ctype"			=> "text",
							"body"			=> $body,
						);

		if (pupesoft_sahkoposti($parametri)) {
			echo t("Sähköpostin lähetys onnitstui").": {$rakir_row['asiakas_email']}<br />";
		}
	}
