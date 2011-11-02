<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	// Tarvitaan 3 parametria
	// 1 = Yhtio
	// 2 = Luottorajaprosentti
	// 3 = Sähkopostiosoite

	if (!isset($argv[1]) or $argv[1] == '') {
		echo "Anna yhtiö!!!\n";
		die;
	}

	if (!isset($argv[2]) or $argv[2] == '') {
		echo "Anna luottorajaprosentti!!!\n";
		die;
	}

	if (!isset($argv[3]) or $argv[3] == '') {
		echo "Anna sähkopostiosoite!!!\n";
		die;
	}

	// Otetaan parametrit
	$yhtiorow = hae_yhtion_parametrit($argv[1]);
	$luottorajaprosentti = (float) $argv[2];
	$email = trim($argv[3]);

	// Meilinlähetyksen oletustiedot
	$content_subject = "Luotonhallintaraportti ".date("d.m.y");
	$content_body = "";
	$ctype = "html";
	$kukarow["eposti"] = $email;
	$liite = array();
	$laskuri = 0;

	// Haetaan kaikki yrityksen asiakkaat
	$query  = "	SELECT ytunnus,
				group_concat(distinct tunnus) liitostunnukset,
				group_concat(distinct nimi ORDER BY nimi SEPARATOR '<br>') nimi,
				group_concat(distinct toim_nimi ORDER BY nimi SEPARATOR '<br>') toim_nimi,
				min(luottoraja) luottoraja,
				min(myyntikielto) myyntikielto
				FROM asiakas
				WHERE yhtio = '$yhtiorow[yhtio]'
				AND laji != 'P'
				GROUP BY ytunnus
				HAVING luottoraja > 0";
	$asiakasres = mysql_query($query) or pupe_error($query);

	$content_body .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01//EN\">";
	$content_body .= "<html>";
	$content_body .= "<head>";
	$content_body .= "<title>".htmlentities($content_subject)."</title>";
	$content_body .= "</head>";
	$content_body .= "<body>";

	$content_body .= "<h3>".htmlentities("Asiakkaat, jotka ovat käyttäneet yli $luottorajaprosentti% luottorajastaan")."</h3>";
	$content_body .= "<table summary='".htmlentities($content_subject)."'>";
	$content_body .= "<tr>";
	$content_body .= "<th style='text-align:left; padding:5px;'>Ytunnus</th>";
	$content_body .= "<th style='text-align:left; padding:5px;'>Nimi</th>";
	$content_body .= "<th style='text-align:left; padding:5px;'>Toim_nimi</th>";
	$content_body .= "<th style='text-align:left; padding:5px;'>Luottoraja</th>";
	$content_body .= "<th style='text-align:left; padding:5px;'>Summa</th>";
	$content_body .= "<th style='text-align:left; padding:5px;'>Myyntikielto</th>";
	$content_body .= "</tr>";

	while ($asiakasrow = mysql_fetch_array($asiakasres)) {

		// Haetaan asiakkaan avoimet laskut
		$query = "	SELECT ifnull(sum(summa), 0) summa
					FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
					WHERE lasku.yhtio = '$yhtiorow[yhtio]'
					AND lasku.tila = 'U'
					AND lasku.liitostunnus IN ($asiakasrow[liitostunnukset])
					AND lasku.mapvm = '0000-00-00'";
		$myyntires = mysql_query($query) or pupe_error($query);
		$myyntirow = mysql_fetch_array($myyntires);

		// Näytä vain asiakkaat, jotka ovat täyttäneet $luottorajaprosentti prosenttia luottorajasta tai sen yli
		if (($myyntirow["summa"] / $asiakasrow["luottoraja"] * 100) < $luottorajaprosentti) {
			continue;
		}

		$content_body .= "<tr>";
		$content_body .= "<td style='padding:5px;'>".htmlentities($asiakasrow["ytunnus"])."</td>";
		$content_body .= "<td style='padding:5px;'>".htmlentities($asiakasrow["nimi"])."</td>";
		$content_body .= "<td style='padding:5px;'>".htmlentities($asiakasrow["toim_nimi"])."</td>";
		$content_body .= "<td style='text-align:right; padding:5px;'>".htmlentities($asiakasrow["luottoraja"])."</td>";
		$content_body .= "<td style='text-align:right; padding:5px;'>".htmlentities($myyntirow["summa"])."</td>";
		$content_body .= "<td style='text-align:right; padding:5px;'>".htmlentities($asiakasrow["myyntikielto"])."</td>";
		$content_body .= "</tr>";

		$laskuri++;
	}

	$content_body .= "</table>";
	$content_body .= "</body>";
	$content_body .= "</html>";

	if ($laskuri > 0) {
		require ("../inc/sahkoposti.inc");
	}

?>