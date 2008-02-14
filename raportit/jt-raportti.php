<?php
	if ($argc == 0) {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	// otetaan tietokanta connect
	require ("../inc/connect.inc");
	require ("../inc/functions.inc");

	$query = "SELECT DISTINCT yhtio FROM yhtio";
	$yhtio_result = mysql_query($query) or die($query);

//	$laskuri = 1;
	
	while ($yrow = mysql_fetch_array($yhtio_result)) {
		$query = "SELECT * FROM yhtio WHERE yhtio='{$yrow['yhtio']}'";
		$result = mysql_query($query) or die($query);

		if (mysql_num_rows($result) == 1) {
			$yhtiorow = mysql_fetch_array($result);

			$query = "	SELECT *
						FROM yhtion_parametrit
						WHERE yhtio='$yhtiorow[yhtio]'";
			$result = mysql_query($query)
					or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_array($result);
				// lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
				foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
					$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
				}
			}			
		}

		$toimquery = "SELECT * FROM yhtion_toimipaikat WHERE yhtio='{$yhtiorow['yhtio']}' AND toim_automaattinen_jtraportti != ''";
		$toimresult = mysql_query($toimquery) or die ("Kysely ei onnistu yhtio $query");
	
		while ($toimrow = mysql_fetch_array($toimresult)) {
			if ($toimrow["toim_automaattinen_jtraportti"] == "pv") {
				// annetaan mennä läpi, koska ajetaan joka päivä
			}
			else if ($toimrow["toim_automaattinen_jtraportti"] == "vk") {
				// ajetaan joka sunnuntai
				if (date('N') != 7) {
					continue;
				}
			}
			else if ($toimrow["toim_automaattinen_jtraportti"] == "kk") {
				// ajetaan kuun 1. pv
				if (date('j') != 1) {
					continue;
				}
			}
			else {
				continue;
			}

			$lisavarattu = "";
			$laskulisa = "";

			if ($yhtiorow["varaako_jt_saldoa"] != "") {
				$lisavarattu = " + tilausrivi.varattu";
			}
			else {
				$lisavarattu = "";
			}

			$liitostunnus_query = "	SELECT DISTINCT lasku.liitostunnus FROM tilausrivi
									JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
									WHERE tilausrivi.yhtio = '{$yhtiorow['yhtio']}'
									AND tilausrivi.tyyppi 			= 'L'
									AND tilausrivi.var 				= 'J'
									AND tilausrivi.keratty 			= ''
									AND tilausrivi.uusiotunnus 		= 0
									AND tilausrivi.kpl 				= 0
									AND tilausrivi.jt $lisavarattu	> 0
									AND lasku.yhtio_toimipaikka = $toimrow[tunnus]";
			$liitostunnus_result = mysql_query($liitostunnus_query) or die($liitostunnus_query);

			while ($liitostunnus_row = mysql_fetch_array($liitostunnus_result)) {

				$asiakasquery = "SELECT nimi, osoite, postino, postitp, maa, ytunnus, email, kieli, tunnus FROM asiakas WHERE yhtio='{$yhtiorow['yhtio']}' AND tunnus=$liitostunnus_row[liitostunnus]";
				$asiakasresult = mysql_query($asiakasquery) or pupe_error($asiakasquery);
				$asiakasrow = mysql_fetch_array($asiakasresult);
	
				if ($asiakasrow["email"] != "") {

					$jtquery = "	SELECT tilausrivi.nimitys, tilausrivi.otunnus, tilausrivi.tuoteno, tilausrivi.laadittu, tilausrivi.tilkpl
									FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
									JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.osatoimitus = '' AND lasku.liitostunnus = '{$asiakasrow['tunnus']}')
									WHERE tilausrivi.yhtio 	= '{$yhtiorow['yhtio']}'
									AND tilausrivi.tyyppi 			= 'L'
									AND tilausrivi.var 				= 'J'
									AND tilausrivi.keratty 			= ''
									AND tilausrivi.uusiotunnus 		= 0
									AND tilausrivi.kpl 				= 0
									AND tilausrivi.jt $lisavarattu	> 0
									AND lasku.yhtio_toimipaikka 	= $toimrow[tunnus]
									ORDER BY tilausrivi.otunnus";

					$jtresult = mysql_query($jtquery) or pupe_error($jtquery);

					if (mysql_num_rows($jtresult) > 0) {

						require_once('../pdflib/phppdflib.class.php');
						require("jt-raportti_pdf.inc");

						$pdf = new pdffile();

						$pdf->set_default('margin-top',    0);
						$pdf->set_default('margin-bottom', 0);
						$pdf->set_default('margin-left',   0);
						$pdf->set_default('margin-right',  0);

						list($page[$sivu], $kalakorkeus) = alku($pdf);

						while ($jtrow = mysql_fetch_array($jtresult)) {
							list($page[$sivu],$kalakorkeus) = rivi($pdf, $page[$sivu], $kalakorkeus, $jtrow);
						}
//						echo "$laskuri ";
						print_pdf($pdf, 1);
//						$laskuri++;
					}
				}
			}
		}
	}
?>