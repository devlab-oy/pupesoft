<?php

	//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
	$useslave = 1;

	// Kutsutaanko CLI:st‰
	if (php_sapi_name() != 'cli') {
		die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
	}

	require('inc/connect.inc');
	require('inc/functions.inc');

	if ($argv[1] != "") {
		// ja yhtio rivilt‰ ensimm‰inen arg
		$yhtio = mysql_real_escape_string($argv[1]);
		$yhtiorow = hae_yhtion_parametrit($yhtio);
	}
	else {
		echo "Anna yhtio!\n\n";
		exit;
	}

	if ($argv[2] != "") {
		$to_email = $argv[2];
	}
	else {
		echo "Anna email!\n\n";
		exit;
	}

	// tilausrivi.kerayspvm => poikkeava alkup‰iv‰
	// tilausrivi.toimaika => poikkeava loppup‰iv‰

	// Haetaan kaikki 30 p‰iv‰n p‰‰st‰ vanhenevat sopimukset/sopimusrivit
	$query = "	(SELECT distinct lasku.tunnus, lasku.ytunnus, lasku.nimi, lasku.asiakkaan_tilausnumero, lasku.valkoodi
				FROM lasku
				JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio
					AND laskun_lisatiedot.otunnus = lasku.tunnus
					AND datediff(now(), laskun_lisatiedot.sopimus_loppupvm) = 30)
				WHERE lasku.yhtio = '$yhtio'
				AND lasku.tila = '0'
				AND lasku.alatila != 'D')

				UNION

				(SELECT distinct lasku.tunnus, lasku.ytunnus, lasku.nimi, lasku.asiakkaan_tilausnumero, lasku.valkoodi
				FROM lasku
				JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
					AND tilausrivi.otunnus = lasku.tunnus
					AND tilausrivi.tyyppi = 'L'
					AND tilausrivi.toimaika != '0000-00-00'
					AND datediff(now(), tilausrivi.toimaika) = 30)
				WHERE lasku.yhtio = '$yhtio'
				AND lasku.tila = '0'
				AND lasku.alatila != 'D')

				ORDER BY 1 asc";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		exit;
	}

	// Tehd‰‰n email
	$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <{$yhtiorow["postittaja_email"]}>\n";
	$header .= "Content-type: text/html; charset=\"iso-8859-1\"\n";

	$out  = "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01//EN\">";
	$out .= "<html>";
	$out .= "<head>";
	$out .= "<meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-15'>";
	$out .= "<style type='text/css'>{$yhtiorow["css"]}</style>";
	$out .= "<title></title>";
	$out .= "</head>";

	$out .= "<body>";
	$out .= "<font class='head'>".t("Vanhenevat sopimukset")."</font><hr><br>";

	$out .= "<table summary='".t("Vanhenevat sopimukset")."'>";

	$out .= "<thead>\n";
	$out .= "<tr>";
	$out .= "<th width='1'>".t("Sopimus")."</th>";
	$out .= "<th width='1'>".t("Asiakkaan")."<br>".t("Tilausnumero")."</th>";
	$out .= "<th width='1'>".t("Ytunnus")."</th>";
	$out .= "<th width='1'>".t("Nimi")."</th>";
	$out .= "<th width='1'>".t("Tuoteno")."</th>";
	$out .= "<th width='1'>".t("Nimitys")."</th>";
	$out .= "<th width='1'>".t("Kommentti")."</th>";
	$out .= "<th width='1'>".t("Alku pvm")."</th>";
	$out .= "<th width='1'>".t("Loppu pvm")."</th>";
	$out .= "<th width='1'>".t("Kpl")."</th>";
	$out .= "<th width='1'>".t("Hinta")."</th>";
	$out .= "<th width='1'>".t("Rivihinta")."</th>";
	$out .= "</tr>\n";
	$out .= "</thead>\n";

	$out .= "<tbody>\n";

	$query_ale_lisa = generoi_alekentta('M');

	while ($laskurow = mysql_fetch_assoc($result)) {

		$query = "	SELECT tilausrivi.tuoteno,
					tilausrivi.nimitys,
					round(tilausrivi.hinta * tilausrivi.varattu * {$query_ale_lisa}, {$yhtiorow["hintapyoristys"]}) rivihinta,
					tilausrivi.varattu,
					tilausrivi.hinta,
					tilausrivi.kommentti,
					if (tilausrivi.kerayspvm = '0000-00-00', if(laskun_lisatiedot.sopimus_loppupvm = '0000-00-00', '', laskun_lisatiedot.sopimus_loppupvm), tilausrivi.kerayspvm) rivinsopimus_alku,
					if (tilausrivi.toimaika = '0000-00-00', if(laskun_lisatiedot.sopimus_alkupvm = '0000-00-00', '', laskun_lisatiedot.sopimus_loppupvm), tilausrivi.toimaika) rivinsopimus_loppu
					FROM tilausrivi
					JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = tilausrivi.yhtio and laskun_lisatiedot.otunnus = tilausrivi.otunnus)
					WHERE tilausrivi.yhtio = '$yhtio'
					AND tilausrivi.otunnus = {$laskurow["tunnus"]}
					AND tilausrivi.tyyppi = '0'";
		$riviresult = pupe_query($query);

		while ($rivirow = mysql_fetch_assoc($riviresult)) {
				$out .= "<tr class='aktiivi'>";
				$out .= "<td nowrap>{$laskurow["tunnus"]}</td>";
				$out .= "<td>".htmlentities($laskurow["asiakkaan_tilausnumero"])."</td>";
				$out .= "<td>".htmlentities($laskurow["ytunnus"])."</td>";
				$out .= "<td>".htmlentities($laskurow["nimi"])."</td>";
				$out .= "<td nowrap>".htmlentities($rivirow["tuoteno"])."</td>";
				$out .= "<td>".htmlentities($rivirow["nimitys"])."</td>";
				$out .= "<td>".htmlentities($rivirow["kommentti"])."</td>";
				$out .= "<td nowrap>{$rivirow["rivinsopimus_alku"]}</td>";
				$out .= "<td nowrap>{$rivirow["rivinsopimus_loppu"]}</td>";
				$out .= "<td nowrap>{$rivirow["varattu"]}</td>";
				$out .= "<td nowrap align='right'>".hintapyoristys($rivirow["hinta"])."</td>";
				$out .= "<td nowrap align='right'>{$rivirow["rivihinta"]}</td>";
				$out .= "</tr>\n";
		}

		$out .= "<tr><td colspan='12' class='back'>&nbsp;</td></tr>\n";
	}

	$out .= "</tbody>";
	$out .= "</table>";
	$out .= "</body>";
	$out .= "</html>";

	$postia = mail($to_email, mb_encode_mimeheader("{$yhtiorow["nimi"]} - ".t("Vanhenevat sopimukset", $kieli), "ISO-8859-1", "Q"), $out, $header, "-f {$yhtiorow["postittaja_email"]}");
